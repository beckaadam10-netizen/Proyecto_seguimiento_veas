<?php

namespace App\Http\Controllers;

use App\Models\Abogado;
use App\Models\Cliente;
use App\Models\Cobro;
use App\Models\EstadoExpediente;
use App\Models\Expediente;
use App\Models\Gasto;
use App\Models\InstitucionPublica;
use App\Models\ReportePasanteGenerado;
use App\Models\TipoGasto;
use App\Models\TipoTramite;
use App\Models\Tramite;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ReporteController extends Controller
{
    private array $tiposCausa = ['civil', 'comercial', 'laboral', 'penal', 'familia', 'administrativo', 'constitucional', 'otro'];
    private array $estadosTramite = ['iniciado', 'en_proceso', 'presentado', 'observado', 'aprobado', 'rechazado', 'finalizado', 'cancelado'];
    private array $prioridades = ['baja', 'media', 'alta', 'urgente'];

    public function index(): View
    {
        $resumen = [
            'clientes'    => Cliente::count(),
            'expedientes' => Expediente::count(),
            'tramites'    => Tramite::count(),
            'saldo_pendiente' => (float) Gasto::sum('monto') - (float) Cobro::sum('monto'),
        ];

        return view('reportes.index', compact('resumen'));
    }

    // ── Clientes ─────────────────────────────────────────────────────

    public function clientes(Request $request): View
    {
        $clientes = $this->queryClientes($request)->paginate(25)->withQueryString();

        return view('reportes.clientes', ['clientes' => $clientes] + $this->resumenClientes());
    }

    public function clientesPdf(Request $request): Response
    {
        $clientes = $this->queryClientes($request)->get();

        $pdf = Pdf::loadView('reportes.pdf.clientes', [
            'clientes' => $clientes,
            'filtros'  => array_filter([
                'Buscar' => $request->buscar,
                'Tipo'   => $request->tipo === 'persona_juridica' ? 'Persona jurídica' : ($request->tipo === 'persona_fisica' ? 'Persona física' : null),
                'Estado' => $request->estado === '1' ? 'Activo' : ($request->estado === '0' ? 'Inactivo' : null),
            ]),
        ] + $this->resumenClientes())->setPaper('a4');

        return $pdf->download('reporte-clientes.pdf');
    }

    private function queryClientes(Request $request): Builder
    {
        return Cliente::withCount(['expedientes', 'tramites'])
            ->when($request->filled('tipo'), fn ($q) => $q->where('tipo', $request->tipo))
            ->when($request->filled('estado'), fn ($q) => $q->where('activo', $request->estado))
            ->when($request->filled('buscar'), fn ($q) => $q->buscar($request->buscar))
            ->orderBy('nombre');
    }

    private function resumenClientes(): array
    {
        return [
            'resumen' => [
                'total'               => Cliente::count(),
                'activos'             => Cliente::where('activo', true)->count(),
                'personas_fisicas'    => Cliente::where('tipo', 'persona_fisica')->count(),
                'personas_juridicas'  => Cliente::where('tipo', 'persona_juridica')->count(),
            ],
        ];
    }

    // ── Expedientes ──────────────────────────────────────────────────

    public function expedientes(Request $request): View
    {
        $expedientes = $this->queryExpedientes($request)->paginate(25)->withQueryString();
        $abogados    = Abogado::orderBy('nombre')->get();

        return view('reportes.expedientes', [
            'expedientes' => $expedientes,
            'estados'     => EstadoExpediente::orderBy('nombre')->get(),
            'tipos'       => $this->tiposCausa,
            'abogados'    => $abogados,
        ] + $this->resumenExpedientes());
    }

    public function expedientesPdf(Request $request): Response
    {
        $expedientes = $this->queryExpedientes($request)->get();

        $pdf = Pdf::loadView('reportes.pdf.expedientes', [
            'expedientes' => $expedientes,
            'filtros'     => $this->etiquetasFiltrosExpedientes($request),
        ] + $this->resumenExpedientes())->setPaper('a4', 'landscape');

        return $pdf->download('reporte-expedientes.pdf');
    }

    private function queryExpedientes(Request $request): Builder
    {
        return Expediente::with(['cliente', 'abogado', 'estadoExpediente'])
            ->when($request->filled('estado_expediente_id'), fn ($q) => $q->where('estado_expediente_id', $request->estado_expediente_id))
            ->when($request->filled('tipo_causa'), fn ($q) => $q->where('tipo_causa', $request->tipo_causa))
            ->when($request->filled('abogado_id'), fn ($q) => $q->where('abogado_id', $request->abogado_id))
            ->when($request->filled('desde'), fn ($q) => $q->whereDate('fecha_recepcion', '>=', $request->desde))
            ->when($request->filled('hasta'), fn ($q) => $q->whereDate('fecha_recepcion', '<=', $request->hasta))
            ->orderByDesc('created_at');
    }

    private function resumenExpedientes(): array
    {
        $resumen = ['total' => Expediente::count()];
        foreach (EstadoExpediente::orderBy('nombre')->get() as $estado) {
            $resumen['por_estado'][$estado->nombre] = Expediente::where('estado_expediente_id', $estado->id)->count();
        }

        return compact('resumen');
    }

    private function etiquetasFiltrosExpedientes(Request $request): array
    {
        $abogado = $request->filled('abogado_id') ? Abogado::find($request->abogado_id) : null;
        $estado  = $request->filled('estado_expediente_id') ? EstadoExpediente::find($request->estado_expediente_id) : null;

        return array_filter([
            'Estado'         => $estado?->nombre,
            'Tipo de causa'  => $request->filled('tipo_causa') ? ucfirst($request->tipo_causa) : null,
            'Abogado'        => $abogado?->nombre,
            'Desde'          => $request->filled('desde') ? Carbon::parse($request->desde)->format('d/m/Y') : null,
            'Hasta'          => $request->filled('hasta') ? Carbon::parse($request->hasta)->format('d/m/Y') : null,
        ]);
    }

    // ── Trámites ─────────────────────────────────────────────────────

    public function tramites(Request $request): View
    {
        $tramites = $this->queryTramites($request)->paginate(25)->withQueryString();

        $tiposTramite  = TipoTramite::orderBy('nombre')->get();
        $instituciones = InstitucionPublica::orderBy('nombre')->get();

        return view('reportes.tramites', [
            'tramites'      => $tramites,
            'estados'       => $this->estadosTramite,
            'prioridades'   => $this->prioridades,
            'tiposTramite'  => $tiposTramite,
            'instituciones' => $instituciones,
        ] + $this->resumenTramites());
    }

    public function tramitesPdf(Request $request): Response
    {
        $tramites = $this->queryTramites($request)->get();

        $pdf = Pdf::loadView('reportes.pdf.tramites', [
            'tramites' => $tramites,
            'filtros'  => $this->etiquetasFiltrosTramites($request),
        ] + $this->resumenTramites())->setPaper('a4', 'landscape');

        return $pdf->download('reporte-tramites.pdf');
    }

    private function queryTramites(Request $request): Builder
    {
        return Tramite::with(['cliente', 'responsable', 'tipoTramite', 'institucionPublica'])
            ->when($request->filled('estado'), fn ($q) => $q->where('estado', $request->estado))
            ->when($request->filled('prioridad'), fn ($q) => $q->where('prioridad', $request->prioridad))
            ->when($request->filled('tipo_tramite_id'), fn ($q) => $q->where('tipo_tramite_id', $request->tipo_tramite_id))
            ->when($request->filled('institucion_publica_id'), fn ($q) => $q->where('institucion_publica_id', $request->institucion_publica_id))
            ->when($request->filled('desde'), fn ($q) => $q->whereDate('fecha_inicio', '>=', $request->desde))
            ->when($request->filled('hasta'), fn ($q) => $q->whereDate('fecha_inicio', '<=', $request->hasta))
            ->orderByDesc('fecha_inicio');
    }

    private function resumenTramites(): array
    {
        $resumen = ['total' => Tramite::count()];
        foreach ($this->estadosTramite as $estado) {
            $resumen['por_estado'][$estado] = Tramite::where('estado', $estado)->count();
        }

        return compact('resumen');
    }

    private function etiquetasFiltrosTramites(Request $request): array
    {
        $tipoTramite = $request->filled('tipo_tramite_id') ? TipoTramite::find($request->tipo_tramite_id) : null;
        $institucion = $request->filled('institucion_publica_id') ? InstitucionPublica::find($request->institucion_publica_id) : null;

        return array_filter([
            'Estado'          => $request->filled('estado') ? ucfirst(str_replace('_', ' ', $request->estado)) : null,
            'Prioridad'       => $request->filled('prioridad') ? ucfirst($request->prioridad) : null,
            'Tipo de trámite' => $tipoTramite?->nombre,
            'Institución'     => $institucion?->nombre,
            'Desde'           => $request->filled('desde') ? Carbon::parse($request->desde)->format('d/m/Y') : null,
            'Hasta'           => $request->filled('hasta') ? Carbon::parse($request->hasta)->format('d/m/Y') : null,
        ]);
    }

    // ── Gastos y Cobros ──────────────────────────────────────────────

    public function gastosCobros(Request $request): View
    {
        $todos = $this->registrosGastosCobros($request);

        $porPagina = 15;
        $pagina    = (int) $request->input('page', 1);

        $registros = new LengthAwarePaginator(
            $todos->forPage($pagina, $porPagina)->values(),
            $todos->count(),
            $porPagina,
            $pagina,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $tiposGasto = TipoGasto::orderBy('nombre')->get();

        return view('reportes.gastos-cobros', compact('registros', 'tiposGasto') + $this->resumenGastosCobros($request));
    }

    public function gastosCobrosPdf(Request $request): Response
    {
        $registros = $this->registrosGastosCobros($request);

        $pdf = Pdf::loadView('reportes.pdf.gastos-cobros', [
            'registros' => $registros,
            'filtros'   => $this->etiquetasFiltrosGastosCobros($request),
        ] + $this->resumenGastosCobros($request))->setPaper('a4');

        return $pdf->download('reporte-gastos-cobros.pdf');
    }

    // Un trámite/expediente entra en el listado si tiene al menos un gasto o un cobro que
    // matchee los filtros. No se puede paginar con una sola query SQL porque son dos
    // modelos distintos, así que se traen enteros, se mezclan y se pagina en memoria
    // (mismo patrón que GastoCobroController::index).
    private function registrosGastosCobros(Request $request)
    {
        $tramites    = $this->queryResumenTramites($request)->get()->map(fn (Tramite $t) => $this->normalizarRegistroGastoCobro($t, 'tramite'));
        $expedientes = $this->queryResumenExpedientes($request)->get()->map(fn (Expediente $e) => $this->normalizarRegistroGastoCobro($e, 'expediente'));

        return $tramites->concat($expedientes)->sortByDesc(fn ($r) => $r->created_at)->values();
    }

    private function normalizarRegistroGastoCobro(Tramite|Expediente $registro, string $tipo): object
    {
        $esExpediente = $tipo === 'expediente';

        return (object) [
            'tipo_registro'  => $tipo,
            'codigo_display' => $esExpediente ? $registro->numero : $registro->codigo,
            'titulo_display' => $esExpediente ? $registro->caratula : $registro->nombre,
            'cliente'        => $registro->cliente,
            'ruta_show'      => $esExpediente ? route('expedientes.show', $registro) : route('tramites.show', $registro),
            'ruta_pdf'       => $esExpediente ? route('expedientes.reporte.pdf', $registro) : route('tramites.reporte.pdf', $registro),
            'gastado'        => (float) ($registro->gastado_filtro ?? 0),
            'cobrado'        => (float) ($registro->cobrado_filtro ?? 0),
            'created_at'     => $registro->created_at,
        ];
    }

    private function queryGastos(Request $request): Builder
    {
        return Gasto::with(['tramite', 'tipoGasto', 'usuario'])
            ->when($request->filled('tipo_gasto_id'), fn ($q) => $q->where('tipo_gasto_id', $request->tipo_gasto_id))
            ->when($request->filled('desde'), fn ($q) => $q->whereDate('fecha', '>=', $request->desde))
            ->when($request->filled('hasta'), fn ($q) => $q->whereDate('fecha', '<=', $request->hasta));
    }

    private function queryCobros(Request $request): Builder
    {
        return Cobro::with(['tramite', 'gasto', 'usuario'])
            ->when($request->filled('metodo_pago'), fn ($q) => $q->where('metodo_pago', $request->metodo_pago))
            ->when($request->filled('desde'), fn ($q) => $q->whereDate('fecha', '>=', $request->desde))
            ->when($request->filled('hasta'), fn ($q) => $q->whereDate('fecha', '<=', $request->hasta));
    }

    // Un trámite/expediente entra en el listado si tiene al menos un gasto o un cobro que matchee
    // los filtros. "Gastado" / "Cobrado" muestran solo la suma de los movimientos filtrados, no el
    // total histórico del registro.
    private function queryResumenTramites(Request $request): Builder
    {
        return Tramite::with('cliente')
            ->where(function (Builder $q) use ($request) {
                $q->whereHas('gastos', fn ($sub) => $this->aplicarFiltrosGasto($sub, $request))
                  ->orWhereHas('cobros', fn ($sub) => $this->aplicarFiltrosCobro($sub, $request));
            })
            ->withSum(['gastos as gastado_filtro' => fn ($sub) => $this->aplicarFiltrosGasto($sub, $request)], 'monto')
            ->withSum(['cobros as cobrado_filtro' => fn ($sub) => $this->aplicarFiltrosCobro($sub, $request)], 'monto');
    }

    private function queryResumenExpedientes(Request $request): Builder
    {
        return Expediente::with('cliente')
            ->where(function (Builder $q) use ($request) {
                $q->whereHas('gastos', fn ($sub) => $this->aplicarFiltrosGasto($sub, $request))
                  ->orWhereHas('cobros', fn ($sub) => $this->aplicarFiltrosCobro($sub, $request));
            })
            ->withSum(['gastos as gastado_filtro' => fn ($sub) => $this->aplicarFiltrosGasto($sub, $request)], 'monto')
            ->withSum(['cobros as cobrado_filtro' => fn ($sub) => $this->aplicarFiltrosCobro($sub, $request)], 'monto');
    }

    private function aplicarFiltrosGasto(Builder $query, Request $request): Builder
    {
        return $query
            ->when($request->filled('tipo_gasto_id'), fn ($q) => $q->where('tipo_gasto_id', $request->tipo_gasto_id))
            ->when($request->filled('desde'), fn ($q) => $q->whereDate('fecha', '>=', $request->desde))
            ->when($request->filled('hasta'), fn ($q) => $q->whereDate('fecha', '<=', $request->hasta));
    }

    private function aplicarFiltrosCobro(Builder $query, Request $request): Builder
    {
        return $query
            ->when($request->filled('metodo_pago'), fn ($q) => $q->where('metodo_pago', $request->metodo_pago))
            ->when($request->filled('desde'), fn ($q) => $q->whereDate('fecha', '>=', $request->desde))
            ->when($request->filled('hasta'), fn ($q) => $q->whereDate('fecha', '<=', $request->hasta));
    }

    private function resumenGastosCobros(Request $request): array
    {
        $totalGastosFiltro  = $this->queryGastos($request)->sum('monto');
        $totalCobradoFiltro = $this->queryCobros($request)->sum('monto');

        return [
            'resumen' => [
                'total_gastos'      => (float) $totalGastosFiltro,
                'total_cobrado'     => (float) $totalCobradoFiltro,
                'saldo_pendiente'   => (float) $totalGastosFiltro - (float) $totalCobradoFiltro,
            ],
        ];
    }

    private function etiquetasFiltrosGastosCobros(Request $request): array
    {
        $tipoGasto = $request->filled('tipo_gasto_id') ? TipoGasto::find($request->tipo_gasto_id) : null;

        return array_filter([
            'Tipo de gasto'  => $tipoGasto?->nombre,
            'Método de pago' => $request->filled('metodo_pago') ? strtoupper($request->metodo_pago) : null,
            'Desde'          => $request->filled('desde') ? Carbon::parse($request->desde)->format('d/m/Y') : null,
            'Hasta'          => $request->filled('hasta') ? Carbon::parse($request->hasta)->format('d/m/Y') : null,
        ]);
    }

    // ── Reporte Pasantes ─────────────────────────────────────────────
    // Cada usuario ve únicamente sus propios gastos, agrupados por el
    // expediente o trámite al que quedaron cargados.

    public function pasantes(Request $request): View
    {
        // El listado de "mis gastos" para generar un PDF nuevo es solo del pasante: un
        // administrador ya ve y gestiona todo desde la tabla de "PDFs generados por el
        // equipo" más abajo, no necesita este listado ni sus filtros.
        $gastos     = $this->esPasante() ? $this->queryGastosPasante($request)->get() : collect();
        $grupos     = $this->agruparGastosPorCaso($gastos);
        $tiposGasto = $this->esPasante() ? TipoGasto::orderBy('nombre')->get() : collect();

        // Un pasante ve el historial de sus propios PDFs generados. Cualquier otro rol
        // (ej. Administrador) ve el de todo el equipo, para poder revisarlos y generar la
        // versión para el cliente sin que cada pasante tenga que mandársela por su cuenta.
        $periodosGenerados = ReportePasanteGenerado::with('usuario')
            ->when($this->esPasante(), fn ($q) => $q->where('usuario_id', auth()->id()))
            ->when(! $this->esPasante() && $request->filled('usuario_id'), fn ($q) => $q->where('usuario_id', $request->usuario_id))
            ->when($request->filled('desde'), fn ($q) => $q->whereDate('created_at', '>=', $request->desde))
            ->when($request->filled('hasta'), fn ($q) => $q->whereDate('created_at', '<=', $request->hasta))
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (ReportePasanteGenerado $p) => $this->conCasosDelPeriodo($p));

        $periodosPendientes = $periodosGenerados->where('revisado', false)->values();
        $periodosRevisados  = $periodosGenerados->where('revisado', true)->values();

        $usuariosPasantes = $this->esPasante()
            ? collect()
            : User::whereIn('id', ReportePasanteGenerado::select('usuario_id')->distinct())->orderBy('name')->get();

        return view('reportes.pasantes', compact('grupos', 'tiposGasto', 'periodosPendientes', 'periodosRevisados', 'usuariosPasantes') + $this->resumenGastosPasante($gastos));
    }

    // Solo un administrador (o cualquier rol que no sea Pasante) puede marcar un período
    // como revisado — es una confirmación de que administración ya lo controló.
    public function pasantesMarcarRevisado(ReportePasanteGenerado $reportePasanteGenerado): RedirectResponse
    {
        abort_if($this->esPasante(), 403);

        $reportePasanteGenerado->update(['revisado' => true, 'revisado_at' => now()]);

        return back()->with('success', 'PDF marcado como revisado.');
    }

    // Solo administración puede borrar un período generado (el Pasante no debería poder
    // hacer desaparecer su propio reporte). No borra gastos: el período es solo el
    // "recorte" administrativo de un rango de fechas, los gastos siguen intactos.
    public function pasantesDestroy(ReportePasanteGenerado $reportePasanteGenerado): RedirectResponse
    {
        abort_if($this->esPasante(), 403);

        $reportePasanteGenerado->delete();

        return back()->with('success', 'Reporte eliminado.');
    }

    // El bloqueo de períodos (para no cobrar dos veces a administración) rige solo para
    // el rol Pasante. Un administrador puede filtrar y generar el reporte libremente,
    // sin necesidad de elegir un rango de fechas ni riesgo de que se le bloquee.
    public function pasantesPdf(Request $request): Response|RedirectResponse
    {
        if ($this->esPasante()) {
            $request->validate([
                'desde' => 'required|date',
                'hasta' => 'required|date|after_or_equal:desde',
            ], [
                'desde.required' => 'Para generar el reporte final elegí un rango de fechas (Desde/Hasta).',
                'hasta.required' => 'Para generar el reporte final elegí un rango de fechas (Desde/Hasta).',
            ]);
        }

        // queryGastosPasante() ya excluye los gastos que existían al momento de generarse
        // un reporte anterior que los cubría; lo que quede acá es siempre nuevo (aunque su
        // fecha caiga dentro de un período ya generado), así que no hace falta bloquear por
        // solapamiento de fechas — alcanza con avisar si no hay nada nuevo para facturar.
        $gastos = $this->queryGastosPasante($request)->get();

        if ($this->esPasante() && $gastos->isEmpty()) {
            return redirect()->route('reportes.pasantes', $request->query())->with('error',
                'No hay gastos nuevos para facturar en ese rango de fechas (o ya fueron incluidos en un reporte anterior).'
            );
        }

        $grupos = $this->agruparGastosPorCaso($gastos);

        if ($this->esPasante()) {
            ReportePasanteGenerado::create([
                'usuario_id' => auth()->id(),
                'desde'      => $request->desde,
                'hasta'      => $request->hasta,
                'total'      => $gastos->sum('monto'),
            ]);
        }

        $pdf = Pdf::loadView('reportes.pdf.pasantes', [
            'grupos'  => $grupos,
            'usuario' => auth()->user(),
            'desde'   => $request->filled('desde') ? Carbon::parse($request->desde) : null,
            'hasta'   => $request->filled('hasta') ? Carbon::parse($request->hasta) : null,
        ] + $this->resumenGastosPasante($gastos))->setPaper('a4');

        return $pdf->download('reporte-gastos-' . Str::slug(auth()->user()->name) . '.pdf');
    }

    // Re-genera (no queda guardado en disco) el PDF de un período ya generado, para que
    // el pasante pueda volver a verlo desde su historial. También puede verlo cualquier
    // otro rol (ej. Administrador); lo que no puede es un pasante ver el de otro pasante.
    public function pasantesVerPdf(ReportePasanteGenerado $reportePasanteGenerado): Response
    {
        abort_if($this->esPasante() && $reportePasanteGenerado->usuario_id !== auth()->id(), 403);

        $gastos = $this->gastosDelPeriodo($reportePasanteGenerado);
        $grupos = $this->agruparGastosPorCaso($gastos);

        $pdf = Pdf::loadView('reportes.pdf.pasantes', [
            'grupos'  => $grupos,
            'usuario' => $reportePasanteGenerado->usuario,
            'desde'   => $reportePasanteGenerado->desde,
            'hasta'   => $reportePasanteGenerado->hasta,
        ] + $this->resumenGastosPasante($gastos))->setPaper('a4');

        return $pdf->stream("reporte-gastos-{$reportePasanteGenerado->id}.pdf");
    }

    // Re-genera el mismo período, pero como "Detalle Gastos Judiciales" para mandarle al
    // cliente: sin ningún dato de qué pasante cargó cada gasto (eso es interno). A
    // diferencia de pasantesVerPdf(), esto es exclusivo de administración — ningún
    // pasante lo genera, ni siquiera para sus propios períodos.
    public function pasantesVerPdfCliente(ReportePasanteGenerado $reportePasanteGenerado): Response
    {
        abort_if($this->esPasante(), 403);

        $gastos = $this->gastosDelPeriodo($reportePasanteGenerado);
        $grupos = $this->agruparGastosPorCaso($gastos);

        $pdf = Pdf::loadView('reportes.pdf.gastos-cliente', [
            'grupos' => $grupos,
            'total'  => $gastos->sum('monto'),
        ])->setPaper('a4');

        return $pdf->stream("detalle-gastos-{$reportePasanteGenerado->id}.pdf");
    }

    // Los gastos que formaron un período ya generado, exactamente como quedaron en ese
    // momento. No alcanza con "fecha en el rango y creado antes de este período": un
    // gasto puede cumplir eso y aun así ya haber sido facturado en OTRO período anterior
    // (mismo pasante, rango de fechas superpuesto) — hay que excluir esos también, igual
    // que hace queryGastosPasante() al generar un período nuevo.
    private function gastosDelPeriodo(ReportePasanteGenerado $periodo)
    {
        $periodosAnteriores = ReportePasanteGenerado::where('usuario_id', $periodo->usuario_id)
            ->where('created_at', '<', $periodo->created_at)
            ->get(['desde', 'hasta', 'created_at']);

        return Gasto::with([
                'expediente' => fn ($q) => $q->withTrashed()->with(['cliente', 'abogado']),
                'tramite.cliente',
                'tramite.responsable',
                'tramite.institucionPublica',
                'tipoGasto',
            ])
            ->where('usuario_id', $periodo->usuario_id)
            ->whereBetween('fecha', [$periodo->desde, $periodo->hasta])
            ->where('created_at', '<=', $periodo->created_at)
            ->when($periodosAnteriores->isNotEmpty(), function ($q) use ($periodosAnteriores) {
                foreach ($periodosAnteriores as $anterior) {
                    $q->where(function ($sub) use ($anterior) {
                        $sub->whereNotBetween('fecha', [$anterior->desde, $anterior->hasta])
                            ->orWhere('created_at', '>', $anterior->created_at);
                    });
                }
            })
            ->orderByDesc('fecha')
            ->get();
    }

    // Adjunta al período la lista de expedientes/trámites cuyos gastos cubrió, para
    // mostrarla en el historial (un período puede abarcar más de un caso).
    private function conCasosDelPeriodo(ReportePasanteGenerado $periodo): ReportePasanteGenerado
    {
        $gastos = $this->gastosDelPeriodo($periodo);

        $periodo->casos = $gastos
            ->map(fn (Gasto $g) => $g->expediente_id
                ? ($g->expediente ? "{$g->expediente->numero} — {$g->expediente->caratula}" : 'Expediente eliminado')
                : ($g->tramite ? "{$g->tramite->codigo} — {$g->tramite->nombre}" : 'Trámite eliminado'))
            ->unique()
            ->values();

        return $periodo;
    }

    private function queryGastosPasante(Request $request): Builder
    {
        // Los gastos que ya quedaron dentro de un período con PDF generado no tienen que
        // volver a aparecer en el listado (ya fueron cobrados a administración). Pero un
        // gasto cargado DESPUÉS de generar ese PDF, aunque su fecha caiga dentro del rango,
        // nunca llegó a facturarse — si lo excluyéramos solo por fecha, quedaría invisible
        // para siempre. Por eso el corte es por cuándo se creó el gasto, no por su fecha.
        $periodosGenerados = $this->esPasante()
            ? ReportePasanteGenerado::where('usuario_id', auth()->id())->get(['desde', 'hasta', 'created_at'])
            : collect();

        // withTrashed(): un gasto de un expediente archivado/eliminado más tarde
        // sigue siendo un gasto real del pasante y tiene que seguir apareciendo acá.
        return Gasto::with([
                'expediente' => fn ($q) => $q->withTrashed()->with(['cliente', 'abogado']),
                'tramite.cliente',
                'tramite.responsable',
                'tramite.institucionPublica',
                'tipoGasto',
                'usuario',
            ])
            // Un pasante solo puede ver sus propios gastos. Cualquier otro rol ve los de
            // todos, o los de un pasante puntual si lo elige en el filtro.
            ->when($this->esPasante(), fn ($q) => $q->where('usuario_id', auth()->id()))
            ->when(! $this->esPasante() && $request->filled('usuario_id'), fn ($q) => $q->where('usuario_id', $request->usuario_id))
            ->when($periodosGenerados->isNotEmpty(), function ($q) use ($periodosGenerados) {
                foreach ($periodosGenerados as $periodo) {
                    $q->where(function ($sub) use ($periodo) {
                        $sub->whereNotBetween('fecha', [$periodo->desde, $periodo->hasta])
                            ->orWhere('created_at', '>', $periodo->created_at);
                    });
                }
            })
            ->when($request->filled('tipo_gasto_id'), fn ($q) => $q->where('tipo_gasto_id', $request->tipo_gasto_id))
            ->when($request->filled('desde'), fn ($q) => $q->whereDate('fecha', '>=', $request->desde))
            ->when($request->filled('hasta'), fn ($q) => $q->whereDate('fecha', '<=', $request->hasta))
            ->orderByDesc('fecha');
    }

    private function esPasante(): bool
    {
        return auth()->user()->rol?->nombre === 'Pasante';
    }

    // Un gasto pertenece a un expediente o a un trámite, nunca a ambos (ver Gasto::expediente()/tramite()).
    private function agruparGastosPorCaso($gastos)
    {
        return $gastos
            ->groupBy(fn ($g) => $g->expediente_id ? 'expediente-' . $g->expediente_id : 'tramite-' . $g->tramite_id)
            ->map(function ($grupoGastos) {
                $primero = $grupoGastos->first();
                $esExpediente = (bool) $primero->expediente_id;
                $caso = $esExpediente ? $primero->expediente : $primero->tramite;

                // Caso extremo: el expediente/trámite ya no existe (dato huérfano). No debe
                // tirar abajo el reporte, solo mostrarse como "no disponible".
                if (! $caso) {
                    return (object) [
                        'tipo'           => $esExpediente ? 'expediente' : 'tramite',
                        'codigo_display' => '—',
                        'titulo_display' => 'Registro eliminado',
                        'cliente'        => null,
                        'ruta_show'      => null,
                        'gastos'         => $grupoGastos->sortByDesc('fecha')->values(),
                        'total'          => $grupoGastos->sum('monto'),
                    ];
                }

                return (object) [
                    'tipo'           => $esExpediente ? 'expediente' : 'tramite',
                    'codigo_display' => $esExpediente ? $caso->numero : $caso->codigo,
                    'titulo_display' => $esExpediente ? $caso->caratula : $caso->nombre,
                    'cliente'        => $caso->cliente,
                    'ruta_show'      => $esExpediente ? route('expedientes.show', $caso) : route('tramites.show', $caso),
                    'instancia'      => $esExpediente ? $caso->juzgado : $caso->institucionPublica?->nombre,
                    'abogado'        => ($esExpediente ? $caso->abogado : $caso->responsable)?->nombre,
                    'gastos'         => $grupoGastos->sortByDesc('fecha')->values(),
                    'total'          => $grupoGastos->sum('monto'),
                ];
            })
            ->sortByDesc(fn ($g) => $g->gastos->max('fecha'))
            ->values();
    }

    private function resumenGastosPasante($gastos): array
    {
        return [
            'resumen' => [
                'total_gastos' => $gastos->count(),
                'total_monto'  => (float) $gastos->sum('monto'),
            ],
        ];
    }

}
