<?php

namespace App\Http\Controllers;

use App\Models\Abogado;
use App\Models\Cliente;
use App\Models\InstitucionPublica;
use App\Models\Tramite;
use App\Models\TipoActuacion;
use App\Models\TipoDocumento;
use App\Models\TipoGasto;
use App\Models\TipoTramite;
use App\Services\ConsolidadorDocumentosService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\View\View;
use ZipArchive;

class TramiteController extends Controller
{
    private array $estados = [
        'iniciado', 'en_proceso', 'presentado', 'observado', 'aprobado', 'rechazado', 'finalizado', 'cancelado',
    ];

    private array $prioridades = ['baja', 'media', 'alta', 'urgente'];

    public function index(Request $request): View
    {
        $tramites = Tramite::with(['cliente', 'responsable', 'tipoTramite', 'institucionPublica'])
            ->when($request->user()->esCliente(), fn ($q) => $q->where('cliente_id', $request->user()->cliente_id))
            ->when($request->filled('estado'), fn ($q) => $q->where('estado', $request->estado))
            ->when($request->filled('prioridad'), fn ($q) => $q->where('prioridad', $request->prioridad))
            ->when($request->filled('tipo_tramite_id'), fn ($q) => $q->where('tipo_tramite_id', $request->tipo_tramite_id))
            ->when($request->filled('cliente_id'), fn ($q) => $q->where('cliente_id', $request->cliente_id))
            ->when($request->filled('codigo'), fn ($q) => $q->where('codigo', $request->codigo))
            ->orderByDesc('fecha_inicio')
            ->paginate(20)
            ->withQueryString();

        return view('tramites.index', ['tramites' => $tramites] + $this->datosFormulario());
    }

    public function create(): View
    {
        return view('tramites.create', $this->datosFormulario());
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'tipo_tramite_id'         => 'required|exists:tipos_tramite,id',
            'nombre'                  => 'required|string|max:200',
            'cliente_id'              => 'required|exists:clientes,id',
            'responsable_id'          => 'nullable|exists:abogados,id',
            'institucion_publica_id'  => 'nullable|exists:instituciones_publicas,id',
            'estado'                  => 'required|in:' . implode(',', $this->estados),
            'prioridad'               => 'required|in:' . implode(',', $this->prioridades),
            'fecha_inicio'            => 'required|date',
            'fecha_fin_aproximada'    => 'nullable|date|after_or_equal:fecha_inicio',
            'observaciones'           => 'nullable|string',
        ]);

        $data['codigo'] = Tramite::generarCodigo();

        $tramite = Tramite::create($data);

        return redirect()
            ->route('tramites.show', $tramite)
            ->with('success', 'Trámite registrado correctamente.');
    }

    public function show(Request $request, Tramite $tramite): View
    {
        $this->autorizarPropioCliente($request, $tramite);

        $tramite->load([
            'cliente',
            'responsable',
            'tipoTramite',
            'institucionPublica',
            'seguimientos.tipoActuacion',
            'seguimientos.usuario.rol',
            'seguimientos.tipoDocumento',
            'gastos.usuario',
            'gastos.tipoGasto',
            'gastos.cobros',
            'gastos.seguimiento',
            'cobros.gasto',
            'cobros.usuario',
        ]);

        $tiposGasto     = TipoGasto::activos()->orderBy('nombre')->get();
        $tiposActuacion = TipoActuacion::activos()->orderBy('nombre')->get();
        $tiposDocumento = TipoDocumento::activos()->orderBy('nombre')->get();

        return view('tramites.show', compact('tramite', 'tiposGasto', 'tiposActuacion', 'tiposDocumento'));
    }

    public function reporte(Request $request, Tramite $tramite): View
    {
        $this->autorizarPropioCliente($request, $tramite);

        return view('tramites.reporte', ['tramite' => $this->cargarDatosReporte($tramite), 'esPdf' => false]);
    }

    public function reportePdf(Request $request, Tramite $tramite): Response
    {
        $this->autorizarPropioCliente($request, $tramite);

        $pdf = Pdf::loadView('tramites.reporte', ['tramite' => $this->cargarDatosReporte($tramite), 'esPdf' => true])
            ->setPaper('a4');

        return $pdf->download("reporte-{$tramite->codigo}.pdf");
    }

    public function itemsCobroPdf(Request $request, Tramite $tramite): Response
    {
        $this->autorizarPropioCliente($request, $tramite);

        $ids = collect($request->query('gastos', []))->map(fn ($v) => (int) $v)->filter()->values();

        $gastos = $tramite->gastos()
            ->whereIn('id', $ids)
            ->with('usuario')
            ->get()
            ->reject(fn ($g) => $g->cubierto)
            ->values();

        abort_if($gastos->isEmpty(), 404);

        $total = (float) $gastos->sum('monto');

        $pdf = Pdf::loadView('tramites.cobro-items-pdf', [
            'tramite' => $tramite->load('cliente', 'institucionPublica', 'responsable'),
            'gastos'  => $gastos,
            'total'   => $total,
        ])->setPaper('a4');

        return $pdf->download("detalle-cobro-{$tramite->codigo}.pdf");
    }

    public function documentosZip(Request $request, Tramite $tramite): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $this->autorizarPropioCliente($request, $tramite);

        $documentos = $tramite->seguimientos()->whereNotNull('archivo_adjunto')->get();

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

        $rutaZip = $carpetaTmp . '/documentos-' . $tramite->codigo . '-' . uniqid() . '.zip';

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

        return response()->download($rutaZip, "documentos-{$tramite->codigo}.zip")->deleteFileAfterSend(true);
    }

    public function documentosPdf(Request $request, Tramite $tramite, ConsolidadorDocumentosService $consolidador): \Symfony\Component\HttpFoundation\BinaryFileResponse|RedirectResponse
    {
        $this->autorizarPropioCliente($request, $tramite);

        $documentos = $tramite->seguimientos()->whereNotNull('archivo_adjunto')->get();

        abort_if($documentos->isEmpty(), 404);

        $nombreBase = 'Tramite_' . preg_replace('/[^A-Za-z0-9_-]/', '', $tramite->codigo);
        $resultado  = $consolidador->generar($documentos, $nombreBase);

        if (! $resultado['path']) {
            return back()->with('error', 'No se pudo convertir ningún documento a PDF.');
        }

        return response()->download($resultado['path'], $nombreBase . '.pdf')->deleteFileAfterSend(true);
    }

    private function cargarDatosReporte(Tramite $tramite): Tramite
    {
        return $tramite->load([
            'cliente',
            'responsable',
            'tipoTramite',
            'institucionPublica',
            'gastos.tipoGasto',
            'cobros.gasto',
            'seguimientos.tipoActuacion',
            'seguimientos.usuario',
        ]);
    }

    public function edit(Request $request, Tramite $tramite): View
    {
        $this->autorizarPropioCliente($request, $tramite);

        return view('tramites.edit', $this->datosFormulario() + ['tramite' => $tramite]);
    }

    public function update(Request $request, Tramite $tramite): RedirectResponse
    {
        $this->autorizarPropioCliente($request, $tramite);

        $data = $request->validate([
            'tipo_tramite_id'         => 'required|exists:tipos_tramite,id',
            'nombre'                  => 'required|string|max:200',
            'cliente_id'              => 'required|exists:clientes,id',
            'responsable_id'          => 'nullable|exists:abogados,id',
            'institucion_publica_id'  => 'nullable|exists:instituciones_publicas,id',
            'estado'                  => 'required|in:' . implode(',', $this->estados),
            'prioridad'               => 'required|in:' . implode(',', $this->prioridades),
            'fecha_inicio'            => 'required|date',
            'fecha_fin_aproximada'    => 'nullable|date|after_or_equal:fecha_inicio',
            'observaciones'           => 'nullable|string',
        ]);

        $tramite->update($data);

        return redirect()
            ->route('tramites.show', $tramite)
            ->with('success', 'Trámite actualizado correctamente.');
    }

    public function cambiarEstado(Request $request, Tramite $tramite): RedirectResponse
    {
        $data = $request->validate([
            'estado'            => 'required|in:' . implode(',', $this->estados),
            'descripcion'       => 'nullable|string|max:1000',
            'notificar_cliente' => 'boolean',
        ]);

        $estadoAnterior = $tramite->estado;

        if ($estadoAnterior === $data['estado']) {
            return back()->with('error', 'El trámite ya se encuentra en ese estado.');
        }

        $tramite->update(['estado' => $data['estado']]);

        $tipoActuacion = TipoActuacion::firstOrCreate(
            ['nombre' => 'Cambio de estado'],
            ['descripcion' => 'Actuación generada automáticamente al cambiar el estado de un trámite.', 'activo' => true]
        );

        $labels = Tramite::estadosLabels();

        $tramite->seguimientos()->create([
            'usuario_id'        => auth()->id(),
            'tipo_actuacion_id' => $tipoActuacion->id,
            'titulo'            => 'Cambio de estado: ' . $labels[$estadoAnterior] . ' → ' . $labels[$data['estado']],
            'descripcion'       => $data['descripcion'] ?: 'Sin descripción adicional.',
            'fecha_actuacion'   => today(),
            'prioridad'         => 'media',
        ]);

        $notificar = $request->boolean('notificar_cliente');
        $whatsappUrl = null;

        if ($notificar && $tramite->cliente->telefono_whatsapp) {
            $texto = "Hola {$tramite->cliente->nombre_completo}, tu trámite {$tramite->codigo} ({$tramite->nombre}) cambió de estado: "
                . "{$labels[$estadoAnterior]} → {$labels[$data['estado']]}.";

            if ($data['descripcion']) {
                $texto .= "\n\n{$data['descripcion']}";
            }

            $whatsappUrl = 'https://wa.me/' . $tramite->cliente->telefono_whatsapp . '?text=' . rawurlencode($texto);
        }

        $mensaje = 'Estado actualizado a "' . $labels[$data['estado']] . '".';

        if ($notificar) {
            $mensaje .= $whatsappUrl
                ? ' Si WhatsApp no se abrió automáticamente, hacé clic en el botón.'
                : ' No se pudo notificar: el cliente no tiene teléfono registrado.';
        }

        return back()->with('success', $mensaje)->with('whatsapp_url', $whatsappUrl);
    }

    public function destroy(Tramite $tramite): RedirectResponse
    {
        $tramite->delete();

        return redirect()
            ->route('tramites.index')
            ->with('success', 'Trámite eliminado.');
    }

    private function datosFormulario(): array
    {
        return [
            'clientes'               => Cliente::activos()->orderBy('nombre')->get(),
            'responsables'           => Abogado::activos()->orderBy('nombre')->get(),
            'tiposTramite'           => TipoTramite::activos()->orderBy('nombre')->get(),
            'institucionesPublicas'  => InstitucionPublica::activos()->orderBy('nombre')->get(),
            'estados'                => $this->estados,
            'prioridades'            => $this->prioridades,
        ];
    }
}
