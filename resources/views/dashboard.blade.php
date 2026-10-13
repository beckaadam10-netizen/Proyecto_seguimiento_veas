@extends('layouts.app')

@section('title', 'Dashboard')
@section('header', 'Panel de Control')

@section('content')

{{-- Tarjetas de estadísticas --}}
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-6">
    <div class="bg-white rounded-xl shadow-sm p-5 flex items-center gap-4">
        <div class="w-12 h-12 bg-brand-100 text-brand-700 rounded-xl flex items-center justify-center text-xl">
            <i class="fas fa-users"></i>
        </div>
        <div>
            <p class="text-2xl font-bold text-gray-800">{{ $stats['clientes'] }}</p>
            <p class="text-sm text-gray-500">Clientes activos</p>
        </div>
    </div>
    <div class="bg-white rounded-xl shadow-sm p-5 flex items-center gap-4">
        <div class="w-12 h-12 bg-blue-100 text-blue-600 rounded-xl flex items-center justify-center text-xl">
            <i class="fas fa-folder-open"></i>
        </div>
        <div>
            <p class="text-2xl font-bold text-gray-800">{{ $stats['expedientes_cargados'] }}</p>
            <p class="text-sm text-gray-500">Expedientes cargados</p>
        </div>
    </div>
    <div class="bg-white rounded-xl shadow-sm p-5 flex items-center gap-4">
        <div class="w-12 h-12 bg-cyan-100 text-cyan-600 rounded-xl flex items-center justify-center text-xl">
            <i class="fas fa-file-circle-check"></i>
        </div>
        <div>
            <p class="text-2xl font-bold text-gray-800">{{ $stats['tramites_activos'] }}</p>
            <p class="text-sm text-gray-500">Trámites activos</p>
        </div>
    </div>
    <div class="bg-white rounded-xl shadow-sm p-5 flex items-center gap-4">
        <div class="w-12 h-12 {{ $stats['audiencias_programadas'] > 0 ? 'bg-purple-100 text-purple-600' : 'bg-gray-100 text-gray-500' }} rounded-xl flex items-center justify-center text-xl">
            <i class="fas fa-gavel"></i>
        </div>
        <div>
            <p class="text-2xl font-bold {{ $stats['audiencias_programadas'] > 0 ? 'text-purple-600' : 'text-gray-800' }}">{{ $stats['audiencias_programadas'] }}</p>
            <p class="text-sm text-gray-500">Audiencias programadas</p>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 {{ $actividad_reciente !== null ? 'lg:grid-cols-2' : '' }} gap-6 mb-6">

    @if($actividad_reciente !== null)
    {{-- Actividad reciente (bitácora) --}}
    <div class="bg-white rounded-xl shadow-sm">
        <div class="flex items-center justify-between p-5 border-b">
            <h3 class="font-semibold text-gray-700">
                <i class="fas fa-clock-rotate-left text-brand-600 mr-2"></i> Actividad Reciente
            </h3>
            <a href="{{ route('bitacora.index') }}"
               class="text-xs text-brand-700 hover:underline">Ver todas</a>
        </div>
        <div class="divide-y divide-gray-100">
            @php
                $iconosAccion = [
                    'creado'        => ['fa-plus', 'bg-green-100 text-green-600'],
                    'actualizado'   => ['fa-pen', 'bg-amber-100 text-amber-600'],
                    'eliminado'     => ['fa-trash', 'bg-red-100 text-red-600'],
                    'inicio_sesion' => ['fa-right-to-bracket', 'bg-cyan-100 text-cyan-600'],
                    'cierre_sesion' => ['fa-right-from-bracket', 'bg-gray-100 text-gray-500'],
                ];
            @endphp
            @forelse($actividad_reciente as $registro)
            @php [$icono, $colorIcono] = $iconosAccion[$registro->accion] ?? ['fa-circle', 'bg-gray-100 text-gray-500']; @endphp
            <div class="px-5 py-3 flex items-center gap-3">
                <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs flex-shrink-0 {{ $colorIcono }}">
                    <i class="fas {{ $icono }}"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm text-gray-800 truncate">{{ $registro->descripcion }}</p>
                    <p class="text-xs text-gray-500">{{ $registro->usuario?->name ?? '—' }} · {{ $registro->created_at->diffForHumans() }}</p>
                </div>
            </div>
            @empty
            <div class="px-5 py-8 text-center text-gray-400">
                <i class="fas fa-clock-rotate-left text-2xl mb-2"></i>
                <p class="text-sm">Todavía no hay actividad registrada.</p>
            </div>
            @endforelse
        </div>
    </div>
    @endif

    {{-- Resumen financiero del estudio --}}
    <div class="bg-white rounded-xl shadow-sm">
        <div class="p-5 border-b flex items-center justify-between">
            <h3 class="font-semibold text-gray-700">
                <i class="fas fa-scale-balanced text-brand-700 mr-2"></i> Resumen Financiero del Estudio
            </h3>
            @if(auth()->user()->puede('reportes'))
            <a href="{{ route('reportes.gastos-cobros') }}" class="text-xs text-brand-700 hover:underline">Ver detalle</a>
            @endif
        </div>
        <div class="p-5 space-y-4">
            <div class="flex items-center gap-4">
                <div class="w-11 h-11 bg-amber-100 text-amber-600 rounded-xl flex items-center justify-center text-lg flex-shrink-0">
                    <i class="fas fa-coins"></i>
                </div>
                <div>
                    <p class="text-xl font-bold text-gray-800">{{ number_format($resumen_financiero['total_gastos'], 2) }} Bs</p>
                    <p class="text-sm text-gray-500">Total gastado</p>
                </div>
            </div>
            <div class="flex items-center gap-4">
                <div class="w-11 h-11 bg-emerald-100 text-emerald-600 rounded-xl flex items-center justify-center text-lg flex-shrink-0">
                    <i class="fas fa-hand-holding-dollar"></i>
                </div>
                <div>
                    <p class="text-xl font-bold text-gray-800">{{ number_format($resumen_financiero['total_cobrado'], 2) }} Bs</p>
                    <p class="text-sm text-gray-500">Total cobrado</p>
                </div>
            </div>
            <div class="flex items-center gap-4">
                <div class="w-11 h-11 {{ $resumen_financiero['saldo_pendiente'] > 0 ? 'bg-red-100 text-red-600' : 'bg-green-100 text-green-600' }} rounded-xl flex items-center justify-center text-lg flex-shrink-0">
                    <i class="fas fa-file-invoice-dollar"></i>
                </div>
                <div>
                    <p class="text-xl font-bold {{ $resumen_financiero['saldo_pendiente'] > 0 ? 'text-red-600' : 'text-gray-800' }}">{{ number_format($resumen_financiero['saldo_pendiente'], 2) }} Bs</p>
                    <p class="text-sm text-gray-500">Saldo pendiente</p>
                </div>
            </div>
        </div>
    </div>

</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

    {{-- Top 5 clientes con más saldo pendiente por cobrar --}}
    <div class="bg-white rounded-xl shadow-sm">
        <div class="p-5 border-b flex items-center justify-between">
            <h3 class="font-semibold text-gray-700">
                <i class="fas fa-file-invoice-dollar text-red-600 mr-2"></i> Clientes con más deuda por cobrar
            </h3>
            @if(auth()->user()->puede('reportes'))
            <a href="{{ route('reportes.clientes') }}" class="text-xs text-brand-700 hover:underline">Ver todos</a>
            @endif
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-5 py-2.5 text-left font-semibold text-gray-600 w-10">#</th>
                        <th class="px-5 py-2.5 text-left font-semibold text-gray-600">Cliente</th>
                        <th class="px-5 py-2.5 text-right font-semibold text-gray-600">Saldo pendiente</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($clientes_deuda as $i => $fila)
                    <tr class="hover:bg-gray-50">
                        <td class="px-5 py-3 text-gray-400">{{ $i + 1 }}</td>
                        <td class="px-5 py-3">
                            @if(auth()->user()->puede('clientes'))
                            <a href="{{ route('clientes.show', $fila->cliente) }}" class="text-gray-800 hover:text-brand-700 font-medium">
                                {{ $fila->cliente->nombre_completo }}
                            </a>
                            @else
                            <span class="text-gray-800 font-medium">{{ $fila->cliente->nombre_completo }}</span>
                            @endif
                        </td>
                        <td class="px-5 py-3 text-right font-semibold text-red-600 whitespace-nowrap">{{ number_format($fila->saldo, 2) }} Bs</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="3" class="px-5 py-8 text-center text-gray-400">
                            <i class="fas fa-circle-check text-2xl mb-2"></i>
                            <p class="text-sm">Ningún cliente tiene saldo pendiente.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pasantes: cuánto llevan gastado desde su último reporte generado (pendiente de rendir) --}}
    <div class="bg-white rounded-xl shadow-sm">
        <div class="p-5 border-b flex items-center justify-between">
            <div>
                <h3 class="font-semibold text-gray-700">
                    <i class="fas fa-user-graduate text-indigo-600 mr-2"></i> Gastos de pasantes en seguimientos
                </h3>
                <p class="text-xs text-gray-400 mt-0.5">Pendiente de rendir: vuelve a 0 cuando el pasante genera su reporte.</p>
            </div>
            @if(auth()->user()->puede('reportes', 'pasantes'))
            <a href="{{ route('reportes.pasantes') }}" class="text-xs text-brand-700 hover:underline">Ver reporte</a>
            @endif
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-5 py-2.5 text-left font-semibold text-gray-600">Pasante</th>
                        <th class="px-5 py-2.5 text-center font-semibold text-gray-600">Gastos</th>
                        <th class="px-5 py-2.5 text-right font-semibold text-gray-600">Por rendir</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($gastos_pasantes as $pasante)
                    <tr class="hover:bg-gray-50">
                        <td class="px-5 py-3">
                            <p class="text-gray-800 font-medium">{{ $pasante->name }}</p>
                            <p class="text-xs text-gray-400">
                                {{ $pasante->ultimo_reporte ? 'Último reporte hasta el ' . $pasante->ultimo_reporte->format('d/m/Y') : 'Sin reportes generados' }}
                            </p>
                        </td>
                        <td class="px-5 py-3 text-center text-gray-600">{{ $pasante->gastos_pendientes }}</td>
                        <td class="px-5 py-3 text-right font-semibold whitespace-nowrap {{ $pasante->total_gastado > 0 ? 'text-amber-700' : 'text-gray-400' }}">{{ number_format($pasante->total_gastado, 2) }} Bs</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="3" class="px-5 py-8 text-center text-gray-400">
                            <i class="fas fa-user-graduate text-2xl mb-2"></i>
                            <p class="text-sm">No hay pasantes registrados.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
                @if($gastos_pasantes->isNotEmpty())
                <tfoot class="bg-gray-50 border-t">
                    <tr>
                        <td class="px-5 py-2.5 font-medium text-gray-600" colspan="2">Total</td>
                        <td class="px-5 py-2.5 text-right font-semibold text-amber-700 whitespace-nowrap">{{ number_format((float) $gastos_pasantes->sum('total_gastado'), 2) }} Bs</td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>

</div>
@endsection
