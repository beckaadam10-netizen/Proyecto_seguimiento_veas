<?php

namespace App\Http\Controllers;

use App\Models\Abogado;
use App\Models\Audiencia;
use App\Models\Bitacora;
use App\Models\Cliente;
use App\Models\Cobro;
use App\Models\Expediente;
use App\Models\Gasto;
use App\Models\Tramite;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    // Paleta categórica validada (orden fijo — ver skill de dataviz / palette.md)
    private const PALETA_CATEGORICA = [
        '#2a78d6', '#eb6834', '#1baf7a', '#eda100', '#e87ba4', '#008300', '#4a3aa7', '#e34948',
    ];

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
        $carga_abogados      = $this->cargaAbogados();

        // La bitácora es un módulo de auditoría (solo Administrador por defecto): si el
        // usuario no tiene permiso para verla, no le mostramos su contenido acá tampoco.
        $actividad_reciente = $request->user()->puede('bitacora')
            ? Bitacora::with('usuario')->orderByDesc('created_at')->take(5)->get()
            : null;

        return view('dashboard', compact(
            'stats',
            'resumen_financiero',
            'carga_abogados',
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

    /**
     * Ranking de carga de trabajo: es una magnitud (cuánto), no una identidad,
     * así que usa un solo hue (secuencial) en vez de colores categóricos.
     */
    private function cargaAbogados()
    {
        return Abogado::activos()
            ->withCount(['expedientes', 'tramites'])
            ->get()
            ->map(fn ($a) => (object) [
                'label' => $a->nombre,
                'value' => $a->expedientes_count + $a->tramites_count,
                'color' => self::PALETA_CATEGORICA[0],
            ])
            ->filter(fn ($item) => $item->value > 0)
            ->sortByDesc('value')
            ->take(6)
            ->values();
    }
}
