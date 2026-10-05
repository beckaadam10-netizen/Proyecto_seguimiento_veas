<?php

namespace App\Http\Controllers;

use App\Models\Expediente;
use App\Models\Gasto;
use App\Models\Seguimiento;
use App\Models\TipoActuacion;
use App\Models\TipoDocumento;
use App\Models\Tramite;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SeguimientoController extends Controller
{
    public function index(Request $request): View
    {
        $filtroSeguimiento = $request->tipo_actuacion_id || $request->pasante_id;
        $ordenExpedientes = $request->input('orden') === 'antiguos' ? 'antiguos' : 'recientes';

        $expedientesListado = Expediente::with(['cliente', 'abogado', 'demandantes', 'demandados', 'seguimientos.tipoActuacion'])
            ->when($request->filled('buscar'), fn ($q) => $q->buscar($request->buscar))
            ->when($filtroSeguimiento, fn ($q) => $q->whereHas('seguimientos', function ($qq) use ($request) {
                $qq->when($request->tipo_actuacion_id,fn($q2) => $q2->where('tipo_actuacion_id', $request->tipo_actuacion_id))
                   ->when($request->pasante_id,       fn($q2) => $q2->where('usuario_id', $request->pasante_id));
            }))
            ->orderBy('created_at', $ordenExpedientes === 'antiguos' ? 'asc' : 'desc')
            ->paginate(15, ['*'], 'expedientes_page')
            ->withQueryString();

        // Búsqueda en vivo: el JS de la vista pide esto por fetch() y reemplaza solo
        // la tabla, así que alcanza con la tabla sola, sin cargar el resto de la pantalla.
        if ($request->ajax()) {
            return view('seguimientos._tabla-expedientes', compact('expedientesListado'));
        }

        $seguimientos = Seguimiento::with(['tramite.cliente', 'usuario.rol', 'tipoActuacion', 'gasto', 'gastos'])
            ->whereNotNull('tramite_id')
            ->when($request->tipo_actuacion_id,fn($q) => $q->where('tipo_actuacion_id', $request->tipo_actuacion_id))
            ->when($request->pasante_id,       fn($q) => $q->where('usuario_id', $request->pasante_id))
            ->orderByDesc('fecha_actuacion')
            ->paginate(25, ['*'], 'page')
            ->withQueryString();

        // El link "Editar" desde la ficha de un expediente/trámite (partials/_actuaciones.blade.php)
        // apunta acá con ?editar=X. Si esa actuación es de expediente, no está en $seguimientos
        // (que ahora solo trae trámites), así que su modal se agrega aparte para que igual exista en el DOM.
        $seguimientoEditar = null;
        if ($request->filled('editar') && ctype_digit((string) $request->editar)) {
            $seguimientoEditar = Seguimiento::with('gastos')->find($request->editar);
        }

        $seguimientosParaModales = $seguimientoEditar && ! $seguimientos->contains('id', $seguimientoEditar->id)
            ? $seguimientos->concat([$seguimientoEditar])
            : $seguimientos;

        $expedientes    = Expediente::with(['cliente', 'demandados', 'abogado'])->activos()->orderByDesc('created_at')->get();
        $tramites       = Tramite::with('cliente')->activos()->orderByDesc('created_at')->get();
        $tiposActuacion = TipoActuacion::activos()->orderBy('nombre')->get();
        $tiposDocumento = TipoDocumento::activos()->orderBy('nombre')->get();
        $prioridades    = ['baja', 'media', 'alta', 'urgente'];
        $pasantes       = User::whereHas('rol', fn ($q) => $q->where('nombre', 'Pasante'))
            ->where('activo', true)->orderBy('name')->get();

        return view('seguimientos.index', compact(
            'expedientesListado', 'seguimientos', 'seguimientosParaModales', 'ordenExpedientes',
            'expedientes', 'tramites', 'tiposActuacion', 'tiposDocumento', 'prioridades', 'pasantes'
        ));
    }

    public function create(Request $request): View
    {
        $expediente_id  = $request->expediente_id;
        $tramite_id     = $request->tramite_id;
        $expedientes    = Expediente::with(['cliente', 'demandados', 'abogado'])->activos()->orderByDesc('created_at')->get();
        $tramites       = Tramite::with('cliente')->activos()->orderByDesc('created_at')->get();
        $tiposActuacion = TipoActuacion::activos()->orderBy('nombre')->get();
        $tiposDocumento = TipoDocumento::activos()->orderBy('nombre')->get();
        $prioridades    = ['baja', 'media', 'alta', 'urgente'];

        return view('seguimientos.create', compact(
            'expedientes', 'expediente_id', 'tramites', 'tramite_id', 'tiposActuacion', 'tiposDocumento', 'prioridades'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validarDatos($request, [
            'archivo_adjunto'   => 'required|file|max:1048576|mimes:pdf,doc,docx,jpg,png',
            'tipo_documento_id' => 'nullable|exists:tipos_documento,id',
            'notificar_cliente' => 'boolean',
        ]);

        if (! auth()->user()->puede('seguimientos', 'modificar_fecha')) {
            $data['fecha_actuacion'] = today()->format('Y-m-d');
        }

        if ($request->hasFile('archivo_adjunto')) {
            $data['archivo_adjunto'] = $request->file('archivo_adjunto')
                ->store('seguimientos', 'public');
        }

        $notificar = $request->boolean('notificar_cliente');
        unset($data['notificar_cliente']);

        $gastos = $this->extraerDatosGasto($data);

        $this->validarFechaContraReporte(auth()->user(), $data['fecha_actuacion'], $gastos);

        $data['descripcion'] = $this->generarDescripcion($request, $gastos);
        $data['usuario_id']          = auth()->id();
        $data['requiere_respuesta']  = $request->boolean('requiere_respuesta');

        if (empty($data['titulo'])) {
            $data['titulo'] = optional(TipoActuacion::find($data['tipo_actuacion_id']))->nombre ?? 'Actuación';
        }

        $seguimiento = Seguimiento::create($data);

        $this->sincronizarGasto($seguimiento, $gastos);

        $whatsappUrl = null;
        $cliente     = $seguimiento->cliente;

        if ($notificar && $cliente?->telefono_whatsapp) {
            $texto = "Hola {$cliente->nombre_completo}, se registró una nueva actuación en tu caso: \"{$seguimiento->titulo}\".";

            if ($seguimiento->observaciones) {
                $texto .= "\n\n{$seguimiento->observaciones}";
            }

            $whatsappUrl = 'https://wa.me/' . $cliente->telefono_whatsapp . '?text=' . rawurlencode($texto);
        }

        $mensaje = 'Actuación registrada correctamente.';

        if ($notificar) {
            $mensaje .= $whatsappUrl
                ? ' Si WhatsApp no se abrió automáticamente, hacé clic en el botón.'
                : ' No se pudo notificar: el cliente no tiene teléfono registrado.';
        }

        // Este formulario se usa desde 3 pantallas distintas (Seguimientos/Actuaciones,
        // ficha de expediente, ficha de trámite): volver a la anterior en vez de siempre
        // al expediente/trámite, para no sacar a quien lo cargó desde el listado general.
        return redirect()
            ->to($this->urlAnteriorSinModal())
            ->with('success', $mensaje)
            ->with('whatsapp_url', $whatsappUrl);
    }

    // La URL anterior puede traer "?nuevo=1" o "?editar=X" (lo que hizo que se abriera
    // el modal). Si volvemos ahí tal cual, el JS de la página vuelve a abrirlo apenas
    // carga — hay que sacarlos para que el formulario quede cerrado tras guardar.
    private function urlAnteriorSinModal(): string
    {
        $anterior = url()->previous();
        $partes   = parse_url($anterior);
        parse_str($partes['query'] ?? '', $query);
        unset($query['nuevo'], $query['editar']);

        $queryString = http_build_query($query);

        return $partes['path'] . ($queryString ? "?{$queryString}" : '');
    }

    public function historial(Expediente $expediente): View
    {
        $expediente->load([
            'cliente', 'abogado', 'demandantes', 'demandados', 'seguidores',
        ]);

        $seguimientosSinRevisar = $expediente->seguimientos()
            ->with(['tipoActuacion', 'usuario.rol', 'gastos'])
            ->where('revisado', false)
            ->orderByDesc('fecha_actuacion')
            ->get();

        $seguimientosRevisados = $expediente->seguimientos()
            ->with(['tipoActuacion', 'usuario.rol', 'gastos'])
            ->where('revisado', true)
            ->orderByDesc('revisado_at')
            ->get();

        return view('seguimientos.historial', compact('expediente', 'seguimientosSinRevisar', 'seguimientosRevisados'));
    }

    public function marcarRevisado(Seguimiento $seguimiento): RedirectResponse
    {
        abort_unless(auth()->user()->puede('seguimientos', 'revisar'), 403);

        $seguimiento->update(['revisado' => true, 'revisado_at' => now()]);

        return back()->with('success', 'Seguimiento marcado como revisado.');
    }

    // Historial de revisión global: igual que historial(), pero sin limitarse a un
    // expediente — junta lo de todos los expedientes y trámites en un solo lugar.
    public function historialGlobal(Request $request): View
    {
        $filtrar = function ($query) use ($request) {
            $query
                ->when($request->filled('pasante_id'), fn ($q) => $q->where('usuario_id', $request->pasante_id))
                ->when($request->filled('tipo_actuacion_id'), fn ($q) => $q->where('tipo_actuacion_id', $request->tipo_actuacion_id))
                ->when($request->filled('buscar'), function ($q) use ($request) {
                    $termino = $request->buscar;
                    $q->where(function ($qq) use ($termino) {
                        $qq->whereHas('expediente', fn ($e) => $e->buscar($termino))
                           ->orWhereHas('tramite', fn ($t) => $t->where('codigo', 'like', "%{$termino}%")
                                                                 ->orWhere('nombre', 'like', "%{$termino}%"));
                    });
                });
        };

        $seguimientosSinRevisar = Seguimiento::with(['expediente.cliente', 'tramite.cliente', 'tipoActuacion', 'usuario.rol', 'gastos'])
            ->where('revisado', false)
            ->tap($filtrar)
            ->orderByDesc('fecha_actuacion')
            ->paginate(20, ['*'], 'sin_revisar_page')
            ->withQueryString();

        $seguimientosRevisados = Seguimiento::with(['expediente.cliente', 'tramite.cliente', 'tipoActuacion', 'usuario.rol', 'gastos'])
            ->where('revisado', true)
            ->tap($filtrar)
            ->orderByDesc('revisado_at')
            ->paginate(20, ['*'], 'revisados_page')
            ->withQueryString();

        $tiposActuacion = TipoActuacion::activos()->orderBy('nombre')->get();
        $pasantes = User::whereIn('id', Seguimiento::whereNotNull('usuario_id')->distinct()->pluck('usuario_id'))
            ->orderBy('name')->get();

        return view('seguimientos.historial-global', compact(
            'seguimientosSinRevisar', 'seguimientosRevisados', 'tiposActuacion', 'pasantes'
        ));
    }

    public function show(Request $request, Seguimiento $seguimiento): View
    {
        $this->autorizarPropioCliente($request, $seguimiento->expediente ?? $seguimiento->tramite ?? abort(403));

        $seguimiento->load(['expediente.cliente', 'tramite.cliente', 'usuario.rol', 'tipoActuacion', 'tipoDocumento', 'gasto', 'gastos']);
        return view('seguimientos.show', compact('seguimiento'));
    }

    public function edit(Seguimiento $seguimiento): View
    {
        $expedientes    = Expediente::with(['cliente', 'demandados', 'abogado'])->activos()->orderByDesc('created_at')->get();
        $tramites       = Tramite::with('cliente')->activos()->orderByDesc('created_at')->get();
        $tiposActuacion = TipoActuacion::activos()->orderBy('nombre')->get();
        $tiposDocumento = TipoDocumento::activos()->orderBy('nombre')->get();
        $prioridades    = ['baja', 'media', 'alta', 'urgente'];

        return view('seguimientos.edit', compact(
            'seguimiento', 'expedientes', 'tramites', 'tiposActuacion', 'tiposDocumento', 'prioridades'
        ));
    }

    public function update(Request $request, Seguimiento $seguimiento): RedirectResponse
    {
        $data = $this->validarDatos($request, [
            'respondido'        => 'boolean',
            'fecha_respuesta'   => 'nullable|date',
            'tipo_documento_id' => 'nullable|exists:tipos_documento,id',
        ]);

        if (! auth()->user()->puede('seguimientos', 'modificar_fecha')) {
            $data['fecha_actuacion'] = $seguimiento->fecha_actuacion->format('Y-m-d');
        }

        $gastos = $this->extraerDatosGasto($data);

        // Solo el modal de edición del listado trae la sección de gastos (marcada con
        // "gastos_presentes"). La página de edición suelta no la tiene: si se tomara su
        // envío como "la actuación quedó sin gastos", se borrarían los gastos y sus cobros
        // quedarían sueltos (cobrado > gastado, saldo negativo). Sin la sección, los gastos
        // existentes se conservan tal cual.
        if (! $request->boolean('gastos_presentes')) {
            $gastos = Gasto::where('seguimiento_id', $seguimiento->id)->orderBy('id')->get()
                ->map(fn (Gasto $g) => ['id' => $g->id, 'concepto' => $g->concepto, 'monto' => $g->monto])
                ->all();
        }

        // Si la fecha no cambia no se valida: la actuación puede ser de un período cuyo
        // PDF ya se generó, y corregir otro dato no tiene por qué obligar a moverla.
        if ($data['fecha_actuacion'] !== $seguimiento->fecha_actuacion->format('Y-m-d')) {
            $this->validarFechaContraReporte($this->duenioGastos($seguimiento), $data['fecha_actuacion'], $gastos);
        }

        $data['descripcion'] = $this->generarDescripcion($request, $gastos);
        $data['requiere_respuesta'] = $request->boolean('requiere_respuesta');
        $data['respondido']         = $request->boolean('respondido');

        // En una transacción: si sincronizarGasto() rechaza un cambio (ej. quitar un
        // gasto que ya tiene cobros), tampoco queda guardado el resto de la edición.
        DB::transaction(function () use ($seguimiento, $data, $gastos) {
            $seguimiento->update($data);
            $this->sincronizarGasto($seguimiento, $gastos);
        });

        return redirect()
            ->to($this->urlContexto($seguimiento))
            ->with('success', 'Actuación actualizada correctamente.');
    }

    public function destroy(Seguimiento $seguimiento): RedirectResponse
    {
        $url = $this->urlContexto($seguimiento);

        // Los gastos que se cargaron junto con esta actuación (sincronizarGasto) no se
        // borran solos con la actuación (la FK es nullOnDelete, para no perder cobros ya
        // hechos si el gasto quedara huérfano) — así que sin este chequeo, el gasto seguía
        // apareciendo en Gastos y Cobros / Reporte de Pasantes aunque la actuación ya no
        // existiera. Si ya tiene un cobro registrado, no se borra: hay que sacar el cobro
        // primero para no perder ese rastro de dinero.
        $seguimiento->loadMissing('gastos.cobros');

        if ($seguimiento->gastos->contains(fn ($g) => $g->cobros->isNotEmpty())) {
            return redirect()
                ->to($url)
                ->with('error', 'No se puede eliminar: tiene un gasto con un cobro ya registrado. Eliminá primero ese cobro.');
        }

        $seguimiento->gastos()->delete();
        $seguimiento->delete();

        return redirect()
            ->to($url)
            ->with('success', 'Actuación eliminada.');
    }

    // Marcar como respondido rápidamente
    public function marcarRespondido(Seguimiento $seguimiento): RedirectResponse
    {
        $seguimiento->update([
            'respondido'     => true,
            'fecha_respuesta'=> today(),
        ]);

        return back()->with('success', 'Marcado como respondido.');
    }

    // Una actuación con gastos no puede tener fecha dentro de un período cuyo PDF del
    // Reporte de Pasantes ya se generó: esos gastos nunca entrarían en el próximo PDF.
    private function validarFechaContraReporte(?User $duenio, string $fecha, array $gastos): void
    {
        $tieneGastos = collect($gastos)->contains(fn ($g) => ! empty($g['concepto']) && ! empty($g['monto']));
        $minima      = $duenio?->siguienteDesdeReporte();

        if ($tieneGastos && $minima && Carbon::parse($fecha)->lt($minima)) {
            throw ValidationException::withMessages([
                'fecha_actuacion' => 'Los gastos hasta el ' . $minima->copy()->subDay()->format('d/m/Y')
                    . ' ya se incluyeron en un PDF del Reporte de Pasantes. La fecha de actuación tiene que ser desde el '
                    . $minima->format('d/m/Y') . '.',
            ]);
        }
    }

    // A quién le pertenecen los gastos de una actuación (ver sincronizarGasto()).
    private function duenioGastos(Seguimiento $seguimiento): ?User
    {
        $usuarioId = Gasto::where('seguimiento_id', $seguimiento->id)->orderBy('id')->value('usuario_id')
            ?? $seguimiento->usuario_id;

        return $usuarioId ? User::find($usuarioId) : null;
    }

    private function extraerDatosGasto(array &$data): array
    {
        $gastos = $data['gastos'] ?? [];
        unset($data['gastos']);

        return $gastos;
    }

    private function generarDescripcion(Request $request, array $gastos): string
    {
        $resumen = collect($gastos)
            ->filter(fn ($g) => ! empty($g['concepto']) && ! empty($g['monto']))
            ->map(fn ($g) => "{$g['concepto']} (" . number_format((float) $g['monto'], 2) . ' Bs)')
            ->implode(', ');

        if ($resumen) {
            return "Gastos de actuación: {$resumen}";
        }

        return $request->input('observaciones') ?: 'Sin gastos registrados.';
    }

    // Los gastos existentes se actualizan en el lugar en vez de borrarse y volver a crearse:
    // así un administrador que corrige el monto de un gasto de un pasante no se queda con
    // ese gasto a su nombre (usuario_id), el gasto conserva su created_at (que es lo que
    // usa el reporte de pasantes para saber si ya fue rendido) y sus cobros no quedan
    // huérfanos.
    //
    // Cada fila del formulario trae el id del gasto que representa (las filas nuevas no
    // traen id). Un gasto que ya tiene cobros no se puede quitar ni bajar por debajo de lo
    // cobrado: eso dejaría cobrado > gastado y el caso aparecería como "Pagado" con saldo
    // negativo, como si el estudio le debiera al cliente.
    private function sincronizarGasto(Seguimiento $seguimiento, array $gastos): void
    {
        $filas = collect($gastos)->filter(fn ($g) => ! empty($g['concepto']) && ! empty($g['monto']));

        $existentes = Gasto::with('cobros')->where('seguimiento_id', $seguimiento->id)->orderBy('id')->get()->keyBy('id');

        $conId = $filas->filter(fn ($g) => ! empty($g['id']) && $existentes->has((int) $g['id']))
            ->unique(fn ($g) => (int) $g['id']);

        // Si el mismo concepto+monto aparece más de una vez entre las filas nuevas (ej.
        // doble clic en "+ Agregar gasto" cargó la misma línea dos veces sin que se note
        // en el formulario), no se duplica el gasto real — se guarda una sola vez.
        $nuevas = $filas->reject(fn ($g) => ! empty($g['id']) && $existentes->has((int) $g['id']))
            ->unique(fn ($g) => strtolower(trim($g['concepto'])) . '|' . (float) $g['monto']);

        $quitados = $existentes->except($conId->map(fn ($g) => (int) $g['id'])->all());

        if ($quitado = $quitados->first(fn (Gasto $g) => $g->cobros->isNotEmpty())) {
            throw ValidationException::withMessages([
                'gastos' => "No se puede quitar el gasto \"{$quitado->concepto}\": ya tiene un cobro registrado. Eliminá primero ese cobro.",
            ]);
        }

        foreach ($conId as $g) {
            $gasto = $existentes->get((int) $g['id']);

            if ((float) $g['monto'] + 0.004 < $gasto->total_cobrado) {
                throw ValidationException::withMessages([
                    'gastos' => "El gasto \"{$gasto->concepto}\" ya tiene cobrados " . number_format($gasto->total_cobrado, 2)
                        . ' Bs: su monto no puede ser menor a eso.',
                ]);
            }
        }

        $datosComunes = [
            'tramite_id'    => $seguimiento->tramite_id,
            'expediente_id' => $seguimiento->expediente_id,
            'fecha'         => $seguimiento->fecha_actuacion,
        ];

        foreach ($conId as $g) {
            $existentes->get((int) $g['id'])->update($datosComunes + [
                'concepto' => $g['concepto'],
                'monto'    => $g['monto'],
            ]);
        }

        // Un gasto nuevo agregado al editar le pertenece a quien registró la actuación,
        // no a quien la está editando.
        $duenio = $existentes->first()?->usuario_id ?? $seguimiento->usuario_id ?? auth()->id();

        foreach ($nuevas as $g) {
            Gasto::create($datosComunes + [
                'seguimiento_id' => $seguimiento->id,
                'usuario_id'     => $duenio,
                'concepto'       => $g['concepto'],
                'monto'          => $g['monto'],
            ]);
        }

        $quitados->each->delete();
    }

    private function urlContexto(Seguimiento $seguimiento): string
    {
        return $seguimiento->tramite_id
            ? route('tramites.show', $seguimiento->tramite_id)
            : route('expedientes.show', $seguimiento->expediente_id);
    }

    private function validarDatos(Request $request, array $reglasExtra = []): array
    {
        // Normalizamos los <select> vacíos ("— Ninguno —") a null antes de validar,
        // para que "nullable" y "exists" se comporten como se espera.
        $request->merge([
            'expediente_id' => $request->filled('expediente_id') ? $request->expediente_id : null,
            'tramite_id'    => $request->filled('tramite_id') ? $request->tramite_id : null,
        ]);

        $reglas = array_merge([
            'expediente_id'     => 'nullable|exists:expedientes,id',
            'tramite_id'        => 'nullable|exists:tramites,id',
            'tipo_actuacion_id' => 'required|exists:tipos_actuacion,id',
            'titulo'            => 'nullable|string|max:200',
            'gastos'                 => 'nullable|array',
            'gastos.*.id'            => 'nullable|integer',
            'gastos.*.concepto'      => 'nullable|string|max:255',
            'gastos.*.monto'         => 'nullable|numeric|min:0.01',
            'fecha_actuacion'   => 'required|date',
            'fecha_vencimiento' => 'nullable|date',
            'requiere_respuesta'=> 'boolean',
            'prioridad'         => 'required|in:baja,media,alta,urgente',
            'observaciones'     => 'nullable|string',
        ], $reglasExtra);

        $validador = Validator::make($request->all(), $reglas);

        $validador->after(function ($validador) use ($request) {
            if ($request->filled('expediente_id') === $request->filled('tramite_id')) {
                $validador->errors()->add('expediente_id', 'Elegí un expediente o un trámite (no ambos).');
            }
        });

        return $validador->validate();
    }
}
