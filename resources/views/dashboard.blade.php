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

<div class="grid grid-cols-1 gap-6">

    {{-- Carga de abogados --}}
    <div class="bg-white rounded-xl shadow-sm">
        <div class="p-5 border-b flex items-center justify-between">
            <h3 class="font-semibold text-gray-700">
                <i class="fas fa-user-tie text-indigo-600 mr-2"></i> Carga de Abogados
            </h3>
            <a href="{{ route('abogados.index') }}" class="text-xs text-brand-700 hover:underline">Ver todos</a>
        </div>
        <div class="p-5">
            @include('partials._bar-chart-horizontal', [
                'items' => $carga_abogados,
                'emptyMessage' => 'No hay abogados con expedientes o trámites asignados.',
            ])
        </div>
    </div>

</div>
@endsection
