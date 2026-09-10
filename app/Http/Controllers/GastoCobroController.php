<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Expediente;
use App\Models\TipoGasto;
use App\Models\Tramite;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

class GastoCobroController extends Controller
{
    public function index(Request $request): View
    {
        $registros = $this->registrosFiltrados($request);

        // Las estadísticas se calculan sobre TODOS los registros que matchean el filtro
        // (no solo la página actual), para que reflejen el filtro aplicado y no el recorte
        // de paginación.
        $resumen        = $this->resumenEstadisticas($registros);
        $porTipoGasto   = $this->gastosPorTipo($registros);

        $porPagina = 15;
        $pagina    = (int) $request->input('page', 1);

        $paginado = new LengthAwarePaginator(
            $registros->forPage($pagina, $porPagina)->values(),
            $registros->count(),
            $porPagina,
            $pagina,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('gastos-cobros.index', [
            'registros'    => $paginado,
            'tiposGasto'   => TipoGasto::activos()->orderBy('nombre')->get(),
            'clientes'     => Cliente::activos()->orderBy('nombre')->get(),
            'tipo'         => $request->input('tipo'),
            'resumen'      => $resumen,
            'porTipoGasto' => $porTipoGasto,
        ]);
    }

    public function pdf(Request $request): Response
    {
        $registros = $this->registrosFiltrados($request);

        $pdf = Pdf::loadView('gastos-cobros.pdf', [
            'registros'    => $registros,
            'resumen'      => $this->resumenEstadisticas($registros),
            'porTipoGasto' => $this->gastosPorTipo($registros),
            'filtros'      => $this->etiquetasFiltros($request),
        ])->setPaper('a4');

        return $pdf->download('reporte-gastos-cobros.pdf');
    }

    private function registrosFiltrados(Request $request)
    {
        $tipo = $request->input('tipo');

        $registros = collect();

        if ($tipo !== 'expediente') {
            $registros = $registros->concat(
                $this->queryTramites($request)->get()->map(fn (Tramite $t) => $this->normalizarTramite($t))
            );
        }

        if ($tipo !== 'tramite') {
            $registros = $registros->concat(
                $this->queryExpedientes($request)->get()->map(fn (Expediente $e) => $this->normalizarExpediente($e))
            );
        }

        return $registros->sortByDesc(fn ($r) => $r->created_at)->values();
    }

    private function etiquetasFiltros(Request $request): array
    {
        $cliente = $request->filled('cliente_id') ? Cliente::find($request->cliente_id) : null;

        return array_filter([
            'Buscar'          => $request->buscar,
            'Tipo'            => $request->tipo === 'tramite' ? 'Trámites' : ($request->tipo === 'expediente' ? 'Expedientes' : null),
            'Cliente'         => $cliente?->nombre_completo,
            'Estado de pago'  => match ($request->estado_pago) {
                'pendiente' => 'Pendiente',
                'parcial'   => 'Parcialmente pagado',
                'pagado'    => 'Pagado',
                default     => null,
            },
        ]);
    }

    private function resumenEstadisticas($registros): array
    {
        $totalGastos  = (float) $registros->sum('total_gastos');
        $totalCobrado = (float) $registros->sum('total_cobrado');

        return [
            'total_registros' => $registros->count(),
            'total_gastos'    => $totalGastos,
            'total_cobrado'   => $totalCobrado,
            'saldo_pendiente' => $totalGastos - $totalCobrado,
            'pendientes'      => $registros->where('estado_pago', 'pendiente')->count(),
            'parciales'       => $registros->where('estado_pago', 'parcial')->count(),
            'pagados'         => $registros->where('estado_pago', 'pagado')->count(),
        ];
    }

    // Top 5 tipos de gasto por monto, entre todos los gastos de los registros filtrados.
    private function gastosPorTipo($registros)
    {
        return $registros
            ->flatMap(fn ($r) => $r->gastos)
            ->groupBy(fn ($g) => $g->tipo_gasto_id ?? 0)
            ->map(fn ($gastos) => (object) [
                'nombre' => $gastos->first()->tipoGasto?->nombre ?? 'Sin tipo',
                'total'  => (float) $gastos->sum('monto'),
            ])
            ->sortByDesc('total')
            ->values()
            ->take(5);
    }

    private function queryTramites(Request $request): Builder
    {
        return Tramite::with(['cliente', 'gastos.cobros', 'gastos.seguimiento', 'gastos.usuario', 'gastos.tipoGasto', 'cobros.gasto', 'cobros.usuario'])
            ->withSum('gastos as total_gastos_sum', 'monto')
            ->withSum('cobros as total_cobros_sum', 'monto')
            ->when($request->filled('buscar'), fn ($q) => $q->where(function ($qq) use ($request) {
                $qq->where('codigo', 'like', '%' . $request->buscar . '%')
                   ->orWhere('nombre', 'like', '%' . $request->buscar . '%');
            }))
            ->when($request->filled('cliente_id'), fn ($q) => $q->where('cliente_id', $request->cliente_id))
            ->when($request->filled('estado_pago'), fn ($q) => $this->aplicarFiltroEstadoPago($q, $request->estado_pago));
    }

    private function queryExpedientes(Request $request): Builder
    {
        return Expediente::with(['cliente', 'gastos.cobros', 'gastos.seguimiento', 'gastos.usuario', 'gastos.tipoGasto', 'cobros.gasto', 'cobros.usuario'])
            ->withSum('gastos as total_gastos_sum', 'monto')
            ->withSum('cobros as total_cobros_sum', 'monto')
            ->when($request->filled('buscar'), fn ($q) => $q->where(function ($qq) use ($request) {
                $qq->where('numero', 'like', '%' . $request->buscar . '%')
                   ->orWhere('caratula', 'like', '%' . $request->buscar . '%');
            }))
            ->when($request->filled('cliente_id'), fn ($q) => $q->where('cliente_id', $request->cliente_id))
            ->when($request->filled('estado_pago'), fn ($q) => $this->aplicarFiltroEstadoPago($q, $request->estado_pago));
    }

    private function aplicarFiltroEstadoPago(Builder $query, string $valor): Builder
    {
        return match ($valor) {
            'pendiente' => $query->havingRaw('COALESCE(total_cobros_sum, 0) = 0 AND COALESCE(total_gastos_sum, 0) > 0'),
            'parcial'   => $query->havingRaw('COALESCE(total_cobros_sum, 0) > 0 AND COALESCE(total_gastos_sum, 0) - COALESCE(total_cobros_sum, 0) > 0'),
            'pagado'    => $query->havingRaw('COALESCE(total_gastos_sum, 0) > 0 AND COALESCE(total_gastos_sum, 0) - COALESCE(total_cobros_sum, 0) <= 0'),
            default     => $query,
        };
    }

    private function normalizarTramite(Tramite $t): Tramite
    {
        $t->tipo_registro  = 'tramite';
        $t->codigo_display = $t->codigo;
        $t->titulo_display = $t->nombre;
        $t->ruta_show      = route('tramites.show', $t);

        return $t;
    }

    private function normalizarExpediente(Expediente $e): Expediente
    {
        $e->tipo_registro  = 'expediente';
        $e->codigo_display = $e->numero;
        $e->titulo_display = $e->caratula;
        $e->ruta_show      = route('expedientes.show', $e);

        return $e;
    }
}
