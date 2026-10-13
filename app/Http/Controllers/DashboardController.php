<?php

namespace App\Http\Controllers;

use App\Models\Audiencia;
use App\Models\Bitacora;
use App\Models\Cliente;
use App\Models\Cobro;
use App\Models\Expediente;
use App\Models\Gasto;
use App\Models\Tramite;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        if ($request->user()->esCliente()) {
            return $this->indexCliente($request);
        }

        $stats = [
            'clientes'               => Cliente::activos()->count(),
            'expedientes_cargados'   => Expediente::count(),
            'tramites_activos'       => Tramite::activos()->count(),
            'audiencias_programadas' => Audiencia::proximas()->count(),
        ];

        $resumen_financiero = $this->resumenFinanciero();
        $clientes_deuda      = $this->clientesConMasDeuda();
        $gastos_pasantes     = $this->gastosPasantes();

        // La bitácora es un módulo de auditoría (solo Administrador por defecto): si el
        // usuario no tiene permiso para verla, no le mostramos su contenido acá tampoco.
        $actividad_reciente = $request->user()->puede('bitacora')
            ? Bitacora::with('usuario')->orderByDesc('created_at')->take(5)->get()
            : null;

        return view('dashboard', compact(
            'stats',
            'resumen_financiero',
            'clientes_deuda',
            'gastos_pasantes',
            'actividad_reciente'
        ));
    }

    // Vista mínima para el portal de clientes: solo sus propios trámites y expedientes,
    // nunca estadísticas globales del estudio (cantidad de clientes, carga de abogados, etc.).
    private function indexCliente(Request $request): View
    {
        $clienteId = $request->user()->cliente_id;

        $tramites = Tramite::with(['tipoTramite', 'gastos.seguimiento', 'gastos.cobros', 'cobros'])
            ->where('cliente_id', $clienteId)
            ->orderByDesc('fecha_inicio')
            ->get();

        $expedientes = Expediente::with(['gastos.seguimiento', 'gastos.cobros', 'cobros'])
            ->where('cliente_id', $clienteId)
            ->orderByDesc('created_at')
            ->get();

        $saldo_pendiente = $tramites->sum('saldo_pendiente') + $expedientes->sum('saldo_pendiente');

        // Detalle de a qué corresponde el saldo pendiente: solo lo que le puede importar
        // al cliente (qué es, de qué se trata, cuánto). Nunca quién lo cargó internamente.
        $detalle_saldo_pendiente = $tramites->pluck('gastos')->flatten()
            ->concat($expedientes->pluck('gastos')->flatten())
            ->reject(fn ($g) => $g->cubierto)
            ->map(fn ($g) => (object) [
                'titulo'      => $g->seguimiento?->titulo ?? $g->concepto,
                'descripcion' => $g->seguimiento?->descripcion,
                'monto'       => (float) $g->monto - $g->total_cobrado,
            ])
            ->sortByDesc('monto')
            ->values();

        return view('dashboard-cliente', compact('tramites', 'expedientes', 'saldo_pendiente', 'detalle_saldo_pendiente'));
    }

    // Gastos y cobros de TODO el estudio (no de un caso puntual): la foto financiera
    // global que hoy no se veía en ningún lado del sistema.
    private function resumenFinanciero(): array
    {
        $totalGastos  = (float) Gasto::sum('monto');
        $totalCobrado = (float) Cobro::sum('monto');

        return [
            'total_gastos'    => $totalGastos,
            'total_cobrado'   => $totalCobrado,
            'saldo_pendiente' => $totalGastos - $totalCobrado,
        ];
    }

    // Los 5 clientes con más saldo pendiente (gastos - cobros) sumando todos sus
    // trámites y expedientes, igual que el saldo que se ve en cada caso.
    private function clientesConMasDeuda()
    {
        $porCliente = function ($modelo) {
            $tabla = (new $modelo)->getTable();

            return $modelo::query()
                ->leftJoin('tramites', 'tramites.id', '=', "{$tabla}.tramite_id")
                ->leftJoin('expedientes', 'expedientes.id', '=', "{$tabla}.expediente_id")
                ->selectRaw("COALESCE(tramites.cliente_id, expedientes.cliente_id) AS cliente_id, SUM({$tabla}.monto) AS total")
                ->groupByRaw('COALESCE(tramites.cliente_id, expedientes.cliente_id)')
                ->pluck('total', 'cliente_id');
        };

        $gastos = $porCliente(Gasto::class);
        $cobros = $porCliente(Cobro::class);

        $saldos = $gastos
            ->map(fn ($total, $clienteId) => (float) $total - (float) ($cobros[$clienteId] ?? 0))
            ->filter(fn ($saldo, $clienteId) => $clienteId && $saldo > 0.009)
            ->sortDesc()
            ->take(5);

        $clientes = Cliente::whereIn('id', $saldos->keys())->get()->keyBy('id');

        return $saldos
            ->filter(fn ($saldo, $clienteId) => $clientes->has($clienteId))
            ->map(fn ($saldo, $clienteId) => (object) [
                'cliente' => $clientes[$clienteId],
                'saldo'   => $saldo,
            ])
            ->values();
    }

    // Cuánto lleva gastado cada pasante y todavía no rindió: mismo corte que el Reporte de
    // Pasantes (solo gastos con fecha posterior al "Hasta" de su último PDF generado), así
    // que al generar el reporte la cuenta del pasante vuelve a 0.
    private function gastosPasantes()
    {
        return User::whereHas('rol', fn ($q) => $q->where('nombre', 'Pasante'))
            ->where('activo', true)
            ->get()
            ->each(function (User $pasante) {
                $desde = $pasante->siguienteDesdeReporte();

                $pendientes = $pasante->gastos()
                    ->when($desde, fn ($q) => $q->whereDate('fecha', '>=', $desde))
                    ->selectRaw('COUNT(*) AS cantidad, COALESCE(SUM(monto), 0) AS total')
                    ->first();

                $pasante->gastos_pendientes = (int) $pendientes->cantidad;
                $pasante->total_gastado     = (float) $pendientes->total;
                $pasante->ultimo_reporte    = $desde?->copy()->subDay();
            })
            ->sortByDesc('total_gastado')
            ->values();
    }
}
