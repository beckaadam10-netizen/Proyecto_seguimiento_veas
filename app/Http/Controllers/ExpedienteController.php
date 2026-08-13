<?php

namespace App\Http\Controllers;

use App\Models\Abogado;
use App\Models\Cliente;
use App\Models\EstadoExpediente;
use App\Models\Expediente;
use App\Models\TipoActuacion;
use App\Models\TipoAudiencia;
use App\Models\TipoDocumento;
use App\Models\TipoGasto;
use App\Models\User;
use App\Services\ConsolidadorDocumentosService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\View\View;
use ZipArchive;

class ExpedienteController extends Controller
{
    public function index(Request $request): View
    {
        $orden = $request->input('orden') === 'antiguos' ? 'antiguos' : 'recientes';

        $expedientes = Expediente::with(['cliente', 'partes', 'seguidores', 'estadoExpediente'])
            ->when($request->user()->esCliente(), fn($q) => $q->where('cliente_id', $request->user()->cliente_id))
            ->when($request->buscar,    fn($q) => $q->buscar($request->buscar))
            ->when($request->estado_expediente_id, fn($q) => $q->porEstado((int) $request->estado_expediente_id))
            ->when($request->tipo,      fn($q) => $q->porTipo($request->tipo))
            ->when($request->cliente_id, fn($q) => $q->where('cliente_id', $request->cliente_id))
            ->withCount(['seguimientos', 'audiencias'])
            ->orderBy('created_at', $orden === 'antiguos' ? 'asc' : 'desc')
            ->paginate(20)
            ->withQueryString();

        $estados   = EstadoExpediente::orderBy('nombre')->get();
        $tipos     = ['civil', 'comercial', 'laboral', 'penal', 'familia', 'administrativo', 'constitucional', 'otro'];
        $clientes  = Cliente::activos()->orderBy('nombre')->get(['id', 'nombre', 'apellido', 'razon_social', 'tipo']);
        $abogados  = Abogado::activos()->orderBy('nombre')->get();
        $usuarios  = User::whereNull('cliente_id')->where('activo', true)->orderBy('name')->get(['id', 'name']);
        $numero    = Expediente::generarNumero();

        return view('expedientes.index', compact('expedientes', 'estados', 'tipos', 'clientes', 'abogados', 'usuarios', 'numero', 'orden'));
    }

    public function create(): View
    {
        $clientes  = Cliente::activos()->orderBy('nombre')->get(['id', 'nombre', 'apellido', 'razon_social', 'tipo']);
        $abogados  = Abogado::activos()->orderBy('nombre')->get();
        $numero    = Expediente::generarNumero();
        $estados   = EstadoExpediente::activos()->orderBy('nombre')->get();
        $tipos     = ['civil', 'comercial', 'laboral', 'penal', 'familia', 'administrativo', 'constitucional', 'otro'];

        return view('expedientes.create', compact('clientes', 'abogados', 'numero', 'estados', 'tipos'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate($this->reglasValidacion(), $this->mensajesValidacion());
        $data['abogados_cliente'] = $this->limpiarAbogadosCliente($data['abogados_cliente'] ?? []);
        unset($data['seguidores']);

        $expediente = Expediente::create($data);

        $this->sincronizarPartes($expediente, $request);
        $expediente->seguidores()->sync($request->input('seguidores', []));

        return redirect()
            ->route('expedientes.show', $expediente)
            ->with('success', 'Expediente creado correctamente.');
    }

    public function show(Request $request, Expediente $expediente): View
    {
        $this->autorizarPropioCliente($request, $expediente);

        $expediente->load([
            'cliente',
            'abogado',
            'partes',
            'actualizaciones.usuario',
            'seguimientos.usuario.rol',
            'seguimientos.tipoActuacion',
            'seguimientos.tipoDocumento',
            'documentos',
            'audiencias.abogado',
            'audiencias.tipoAudiencia',
            'gastos.tipoGasto',
            'gastos.usuario',
            'gastos.cobros',
            'gastos.seguimiento',
            'cobros.gasto',
            'cobros.usuario',
        ]);

        $tiposGasto     = TipoGasto::activos()->orderBy('nombre')->get();
        $tiposAudiencia = TipoAudiencia::activos()->orderBy('nombre')->get();
        $abogados       = Abogado::activos()->orderBy('nombre')->get();
        $tiposActuacion = TipoActuacion::activos()->orderBy('nombre')->get();
        $tiposDocumento = TipoDocumento::activos()->orderBy('nombre')->get();

        return view('expedientes.show', compact(
            'expediente', 'tiposGasto', 'tiposAudiencia', 'abogados', 'tiposActuacion', 'tiposDocumento'
        ));
    }

    public function reporte(Request $request, Expediente $expediente): View
    {
        $this->autorizarPropioCliente($request, $expediente);

        return view('expedientes.reporte', ['expediente' => $this->cargarDatosReporte($expediente), 'esPdf' => false]);
    }

    public function reportePdf(Request $request, Expediente $expediente): Response
    {
        $this->autorizarPropioCliente($request, $expediente);

        $pdf = Pdf::loadView('expedientes.reporte', ['expediente' => $this->cargarDatosReporte($expediente), 'esPdf' => true])
            ->setPaper('a4');

        return $pdf->download("reporte-{$expediente->numero}.pdf");
    }

    public function itemsCobroPdf(Request $request, Expediente $expediente): Response
    {
        $this->autorizarPropioCliente($request, $expediente);

        $ids = collect($request->query('gastos', []))->map(fn ($v) => (int) $v)->filter()->values();

        $gastos = $expediente->gastos()
            ->whereIn('id', $ids)
            ->with('usuario')
            ->get()
            ->reject(fn ($g) => $g->cubierto)
            ->values();

        abort_if($gastos->isEmpty(), 404);

        $total = (float) $gastos->sum('monto');

        $pdf = Pdf::loadView('expedientes.cobro-items-pdf', [
            'expediente' => $expediente->load('cliente', 'abogado'),
            'gastos'     => $gastos,
            'total'      => $total,
        ])->setPaper('a4');

        return $pdf->download("detalle-cobro-{$expediente->numero}.pdf");
    }

    public function documentosZip(Request $request, Expediente $expediente): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $this->autorizarPropioCliente($request, $expediente);

        $documentos = $expediente->seguimientos()->whereNotNull('archivo_adjunto')->get();

        abort_if($documentos->isEmpty(), 404);

        $carpetaTmp = storage_path('app/tmp');
        if (! is_dir($carpetaTmp)) {
            mkdir($carpetaTmp, 0755, true);
        }

        // deleteFileAfterSend() no siempre libera el archivo en Windows (el handle
        // puede seguir abierto), así que además limpiamos acá los .zip de descargas
        // anteriores que hayan quedado sin borrar.
        foreach (glob($carpetaTmp . '/documentos-*.zip') as $zipViejo) {
            if (time() - filemtime($zipViejo) > 300) {
                @unlink($zipViejo);
            }
        }

        $rutaZip = $carpetaTmp . '/documentos-' . $expediente->numero . '-' . uniqid() . '.zip';

        $zip = new ZipArchive();
        $zip->open($rutaZip, ZipArchive::CREATE);

        foreach ($documentos as $doc) {
            $rutaArchivo = storage_path('app/public/' . $doc->archivo_adjunto);

            if (! is_file($rutaArchivo)) {
                continue;
            }

            $extension    = pathinfo($rutaArchivo, PATHINFO_EXTENSION);
            $nombreEnZip  = $doc->fecha_actuacion->format('Y-m-d') . '_' . Str::slug($doc->titulo) . '.' . $extension;
            $zip->addFile($rutaArchivo, $nombreEnZip);
        }

        $zip->close();

        return response()->download($rutaZip, "documentos-{$expediente->numero}.zip")->deleteFileAfterSend(true);
    }

    public function documentosPdf(Request $request, Expediente $expediente, ConsolidadorDocumentosService $consolidador): \Symfony\Component\HttpFoundation\BinaryFileResponse|RedirectResponse
    {
        $this->autorizarPropioCliente($request, $expediente);

        $documentos = $expediente->seguimientos()->whereNotNull('archivo_adjunto')->get();

        abort_if($documentos->isEmpty(), 404);

        $nombreBase = 'Expediente_' . preg_replace('/[^A-Za-z0-9_-]/', '', $expediente->numero);
        $resultado  = $consolidador->generar($documentos, $nombreBase);

        if (! $resultado['path']) {
            return back()->with('error', 'No se pudo convertir ningún documento a PDF.');
        }

        return response()->download($resultado['path'], $nombreBase . '.pdf')->deleteFileAfterSend(true);
    }

    private function cargarDatosReporte(Expediente $expediente): Expediente
    {
        return $expediente->load([
            'cliente',
            'abogado',
            'partes',
            'gastos.tipoGasto',
            'cobros.gasto',
            'seguimientos.tipoActuacion',
            'seguimientos.usuario',
        ]);
    }

    public function edit(Request $request, Expediente $expediente): View
    {
        $this->autorizarPropioCliente($request, $expediente);

        $clientes = Cliente::activos()->orderBy('nombre')->get(['id', 'nombre', 'apellido', 'razon_social', 'tipo']);
        $abogados = Abogado::activos()->orderBy('nombre')->get();
        $estados  = EstadoExpediente::activos()->orderBy('nombre')->get();
        $tipos    = ['civil', 'comercial', 'laboral', 'penal', 'familia', 'administrativo', 'constitucional', 'otro'];

        return view('expedientes.edit', compact('expediente', 'clientes', 'abogados', 'estados', 'tipos'));
    }

    public function update(Request $request, Expediente $expediente): RedirectResponse
    {
        $this->autorizarPropioCliente($request, $expediente);

        $data = $request->validate($this->reglasValidacion($expediente->id), $this->mensajesValidacion());
        $data['abogados_cliente'] = $this->limpiarAbogadosCliente($data['abogados_cliente'] ?? []);
        unset($data['seguidores']);

        $expediente->update($data);

        $this->sincronizarPartes($expediente, $request);
        $expediente->seguidores()->sync($request->input('seguidores', []));

        return redirect()
            ->route('expedientes.show', $expediente)
            ->with('success', 'Expediente actualizado correctamente.');
    }

    public function destroy(Expediente $expediente): RedirectResponse
    {
        $expediente->delete();

        return redirect()
            ->route('expedientes.index')
            ->with('success', 'Expediente eliminado correctamente.');
    }

    public function cambiarEstado(Request $request, Expediente $expediente): RedirectResponse
    {
        $data = $request->validate([
            'estado_expediente_id' => 'required|exists:estados_expediente,id',
            'descripcion'          => 'nullable|string|max:1000',
            'notificar_cliente'    => 'boolean',
        ]);

        $estadoAnteriorId = $expediente->estado_expediente_id;

        if ($estadoAnteriorId === (int) $data['estado_expediente_id']) {
            return back()->with('error', 'El expediente ya se encuentra en ese estado.');
        }

        $labelAnterior = $expediente->estado_label;

        $expediente->update(['estado_expediente_id' => $data['estado_expediente_id']]);
        $expediente->refresh();

        $labelNuevo = $expediente->estado_label;

        $tipoActuacion = TipoActuacion::firstOrCreate(
            ['nombre' => 'Cambio de estado'],
            ['descripcion' => 'Actuación generada automáticamente al cambiar el estado de un expediente.', 'activo' => true]
        );

        $expediente->seguimientos()->create([
            'usuario_id'        => auth()->id(),
            'tipo_actuacion_id' => $tipoActuacion->id,
            'titulo'            => 'Cambio de estado: ' . $labelAnterior . ' → ' . $labelNuevo,
            'descripcion'       => $data['descripcion'] ?: 'Sin descripción adicional.',
            'fecha_actuacion'   => today(),
            'prioridad'         => 'media',
        ]);

        $notificar = $request->boolean('notificar_cliente');
        $whatsappUrl = null;

        if ($notificar && $expediente->cliente->telefono_whatsapp) {
            $texto = "Hola {$expediente->cliente->nombre_completo}, tu expediente {$expediente->numero} cambió de estado: "
                . "{$labelAnterior} → {$labelNuevo}.";

            if ($data['descripcion']) {
                $texto .= "\n\n{$data['descripcion']}";
            }

            $whatsappUrl = 'https://wa.me/' . $expediente->cliente->telefono_whatsapp . '?text=' . rawurlencode($texto);
        }

        $mensaje = 'Estado actualizado a "' . $labelNuevo . '".';

        if ($notificar) {
            $mensaje .= $whatsappUrl
                ? ' Si WhatsApp no se abrió automáticamente, hacé clic en el botón.'
                : ' No se pudo notificar: el cliente no tiene teléfono registrado.';
        }

        return back()->with('success', $mensaje)->with('whatsapp_url', $whatsappUrl);
    }

    private function reglasValidacion(?int $expedienteId = null): array
    {
        $reglaNumero = $expedienteId
            ? 'required|string|unique:expedientes,numero,' . $expedienteId
            : 'required|string|unique:expedientes,numero';

        return [
            'numero'                          => $reglaNumero,
            'caratula'                         => 'required|string|max:255',
            'cliente_id'                       => 'required|exists:clientes,id',
            'abogado_id'                       => 'nullable|exists:abogados,id',
            'tipo_causa'                       => 'required|in:civil,comercial,laboral,penal,familia,administrativo,constitucional,otro',
            'tipo_proceso'                     => 'nullable|string|max:150',
            'procedimiento'                    => 'nullable|string|max:150',
            'estado_expediente_id'             => 'required|exists:estados_expediente,id',
            'juzgado'                          => 'nullable|string|max:150',
            'piso'                             => 'nullable|string|max:50',
            'direccion'                        => 'nullable|string|max:255',
            'encargado_actual'                 => 'nullable|string|max:150',
            'enc_anterior'                     => 'nullable|string|max:150',
            'seguidores'                       => 'nullable|array',
            'seguidores.*'                     => 'exists:users,id',
            'fecha_recepcion'                  => 'nullable|date',
            'monto_reclamado'                  => 'nullable|numeric|min:0',
            'descripcion'                      => 'nullable|string',
            'representante_cliente'            => 'nullable|string|max:200',
            'abogados_cliente'                 => 'nullable|array',
            'abogados_cliente.*'               => 'nullable|string|max:150',
            'demandantes'                      => 'nullable|array',
            'demandantes.*.nombre'             => 'required_with:demandantes|string|max:255',
            'demandados'                       => 'nullable|array',
            'demandados.*.nombre'              => 'required_with:demandados|string|max:255',
        ];
    }

    private function mensajesValidacion(): array
    {
        return [
            'numero.required' => 'El NUREJ es obligatorio.',
            'numero.unique'   => 'Ya existe un expediente registrado con ese NUREJ.',
        ];
    }

    private function limpiarAbogadosCliente(array $abogados): array
    {
        return collect($abogados)
            ->map(fn ($nombre) => trim((string) $nombre))
            ->filter()
            ->values()
            ->all();
    }

    private function sincronizarPartes(Expediente $expediente, Request $request): void
    {
        $expediente->partes()->delete();

        foreach (['demandante' => 'demandantes', 'demandado' => 'demandados'] as $tipo => $campo) {
            foreach ($request->input($campo, []) as $orden => $parte) {
                if (empty($parte['nombre'])) {
                    continue;
                }

                $expediente->partes()->create([
                    'tipo'   => $tipo,
                    'nombre' => $parte['nombre'],
                    'orden'  => $orden,
                ]);
            }
        }
    }
}
