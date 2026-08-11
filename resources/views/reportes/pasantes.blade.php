@extends('layouts.app')

@section('title', 'Reporte Pasantes')
@section('header', 'Reporte Pasantes')

@php $esPasante = auth()->user()->rol?->nombre === 'Pasante'; @endphp

@section('header-actions')
    @if($esPasante)
    <div class="flex items-center gap-2">
        <a href="{{ route('reportes.pasantes.pdf', request()->query()) }}"
           onclick="setTimeout(() => location.reload(), 1000)"
           class="bg-red-700 hover:bg-red-800 text-white px-4 py-2 rounded-lg text-sm flex items-center gap-2"
           title="Elegí Desde y Hasta antes de generar: ese período queda bloqueado y no se puede volver a generar.">
            <i class="fas fa-file-pdf"></i> Descargar PDF
        </a>
    </div>
    @endif
@endsection

@section('content')

@if($esPasante)
<div class="bg-white rounded-xl shadow-sm p-5 mb-6">
    <p class="text-sm text-gray-500 mb-3">Mis gastos registrados — {{ auth()->user()->name }}</p>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-amber-100 text-amber-600 rounded-xl flex items-center justify-center text-xl flex-shrink-0">
                <i class="fas fa-coins"></i>
            </div>
            <div>
                <p class="text-2xl font-bold text-gray-800">{{ number_format($resumen['total_monto'], 2) }} Bs</p>
                <p class="text-sm text-gray-500">Total gastado (filtro actual)</p>
            </div>
        </div>
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-brand-100 text-brand-700 rounded-xl flex items-center justify-center text-xl flex-shrink-0">
                <i class="fas fa-list-check"></i>
            </div>
            <div>
                <p class="text-2xl font-bold text-gray-800">{{ $resumen['total_gastos'] }}</p>
                <p class="text-sm text-gray-500">Gastos registrados</p>
            </div>
        </div>
    </div>
</div>

<form method="GET" class="bg-white rounded-xl shadow-sm p-4 mb-6 flex flex-wrap gap-3 items-end">
    <div>
        <label class="block text-xs text-gray-500 mb-1">Tipo de gasto</label>
        <select name="tipo_gasto_id" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
            <option value="">Todos</option>
            @foreach($tiposGasto as $t)
                <option value="{{ $t->id }}" {{ request('tipo_gasto_id') == $t->id ? 'selected' : '' }}>{{ $t->nombre }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-xs text-gray-500 mb-1">Desde</label>
        <input type="date" name="desde" value="{{ request('desde') }}" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
    </div>
    <div>
        <label class="block text-xs text-gray-500 mb-1">Hasta</label>
        <input type="date" name="hasta" value="{{ request('hasta') }}" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
    </div>
    <button type="submit" class="bg-brand-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-brand-700">
        <i class="fas fa-search"></i> Filtrar
    </button>
    <a href="{{ route('reportes.pasantes') }}" class="text-gray-500 text-sm py-2 hover:text-gray-700">
        <i class="fas fa-times"></i> Limpiar
    </a>
</form>

<div class="space-y-6">
    @forelse($grupos as $grupo)
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="p-5 border-b flex items-center justify-between flex-wrap gap-2">
            <div>
                @if($grupo->ruta_show)
                <a href="{{ $grupo->ruta_show }}" class="font-semibold text-gray-800 hover:text-brand-700">
                    <span class="font-mono text-xs {{ $grupo->tipo === 'expediente' ? 'text-brand-800' : 'text-cyan-700' }} mr-2">{{ $grupo->codigo_display }}</span>
                    {{ $grupo->titulo_display }}
                </a>
                @else
                <span class="font-semibold text-gray-400">{{ $grupo->titulo_display }}</span>
                @endif
                <p class="text-xs text-gray-500 mt-0.5">{{ $grupo->cliente?->nombre_completo ?? '—' }}</p>
            </div>
            <span class="text-sm font-bold text-amber-800">{{ number_format($grupo->total, 2) }} Bs</span>
        </div>
        <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-2 text-left font-semibold text-gray-600">Fecha</th>
                    <th class="px-4 py-2 text-left font-semibold text-gray-600">Concepto</th>
                    <th class="px-4 py-2 text-left font-semibold text-gray-600">Tipo de gasto</th>
                    <th class="px-4 py-2 text-right font-semibold text-gray-600">Monto</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($grupo->gastos as $gasto)
                <tr class="hover:bg-gray-50 transition">
                    <td class="px-4 py-2 text-gray-600 whitespace-nowrap">{{ $gasto->fecha->format('d/m/Y') }}</td>
                    <td class="px-4 py-2 text-gray-700">{{ $gasto->concepto }}</td>
                    <td class="px-4 py-2 text-gray-600">{{ $gasto->tipoGasto?->nombre ?? '—' }}</td>
                    <td class="px-4 py-2 text-right text-amber-700 font-medium">{{ number_format($gasto->monto, 2) }} Bs</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        </div>
    </div>
    @empty
    <div class="bg-white rounded-xl shadow-sm p-10 text-center text-gray-400">
        <i class="fas fa-coins text-3xl mb-2"></i>
        <p>No registraste gastos para los filtros aplicados.</p>
    </div>
    @endforelse
</div>
@endif

@if(!$esPasante)
<form method="GET" class="bg-white rounded-xl shadow-sm p-4 mb-6 flex flex-wrap gap-3 items-end">
    <div>
        <label class="block text-xs text-gray-500 mb-1">Pasante</label>
        <select name="usuario_id" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
            <option value="">Todos</option>
            @foreach($usuariosPasantes as $u)
                <option value="{{ $u->id }}" {{ request('usuario_id') == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-xs text-gray-500 mb-1">Desde</label>
        <input type="date" name="desde" value="{{ request('desde') }}" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
    </div>
    <div>
        <label class="block text-xs text-gray-500 mb-1">Hasta</label>
        <input type="date" name="hasta" value="{{ request('hasta') }}" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
    </div>
    <button type="submit" class="bg-brand-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-brand-700">
        <i class="fas fa-search"></i> Filtrar
    </button>
    <a href="{{ route('reportes.pasantes') }}" class="text-gray-500 text-sm py-2 hover:text-gray-700">
        <i class="fas fa-times"></i> Limpiar
    </a>
</form>
@endif

@php
    $columnas = ($esPasante ? 4 : 5) + 1; // + Acciones
@endphp

<div class="bg-white rounded-xl shadow-sm overflow-hidden mt-6">
    <div class="p-5 border-b">
        <h3 class="font-semibold text-gray-700">
            <i class="fas fa-clock-rotate-left text-orange-500 mr-2"></i>
            Pendientes de revisar ({{ $periodosPendientes->count() }})
        </h3>
    </div>
    <div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200 text-sm">
        <thead class="bg-gray-50">
            <tr>
                @if(!$esPasante)
                <th class="px-4 py-3 text-left font-semibold text-gray-600">Pasante</th>
                @endif
                <th class="px-4 py-3 text-left font-semibold text-gray-600">Expediente / Trámite</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">Período cubierto</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">Generado</th>
                <th class="px-4 py-3 text-right font-semibold text-gray-600">Total</th>
                <th class="px-4 py-3 text-right font-semibold text-gray-600">Acciones</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse($periodosPendientes as $periodo)
            <tr class="hover:bg-gray-50 transition">
                @if(!$esPasante)
                <td class="px-4 py-3 text-gray-700 whitespace-nowrap">{{ $periodo->usuario?->name ?? '—' }}</td>
                @endif
                <td class="px-4 py-3 text-gray-700">
                    @forelse($periodo->casos as $caso)
                        <p>{{ $caso }}</p>
                    @empty
                        <span class="text-gray-400">—</span>
                    @endforelse
                </td>
                <td class="px-4 py-3 text-gray-600 whitespace-nowrap">
                    {{ $periodo->desde->format('d/m/Y') }} — {{ $periodo->hasta->format('d/m/Y') }}
                </td>
                <td class="px-4 py-3 text-gray-500 whitespace-nowrap">{{ $periodo->created_at->format('d/m/Y H:i') }}</td>
                <td class="px-4 py-3 text-right font-bold text-amber-800 whitespace-nowrap">{{ number_format($periodo->total, 2) }} Bs</td>
                <td class="px-4 py-3 text-right whitespace-nowrap">
                    <a href="{{ route('reportes.pasantes.ver', $periodo) }}" target="_blank"
                       class="text-xs text-red-700 hover:underline font-medium mr-3">
                        <i class="fas fa-file-pdf"></i> Ver PDF
                    </a>
                    @if(!$esPasante)
                    <a href="{{ route('reportes.pasantes.ver-cliente', $periodo) }}" target="_blank"
                       class="text-xs text-brand-700 hover:underline font-medium mr-3">
                        <i class="fas fa-file-invoice"></i> Generar PDF cliente
                    </a>
                    <form method="POST" action="{{ route('reportes.pasantes.revisado', $periodo) }}" class="inline">
                        @csrf @method('PATCH')
                        <button type="submit" class="text-xs text-green-600 hover:text-green-800 font-medium">
                            <i class="fas fa-check"></i> Marcar revisado
                        </button>
                    </form>
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="{{ $columnas }}" class="px-4 py-10 text-center text-gray-400">
                    <i class="fas fa-clock-rotate-left text-3xl mb-2"></i>
                    <p>{{ $esPasante ? 'Todavía no generaste ningún PDF.' : 'No hay PDFs pendientes de revisar.' }}</p>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm overflow-hidden mt-6">
    <div class="p-5 border-b">
        <h3 class="font-semibold text-gray-700">
            <i class="fas fa-check-double text-green-600 mr-2"></i>
            Revisados ({{ $periodosRevisados->count() }})
        </h3>
    </div>
    <div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200 text-sm">
        <thead class="bg-gray-50">
            <tr>
                @if(!$esPasante)
                <th class="px-4 py-3 text-left font-semibold text-gray-600">Pasante</th>
                @endif
                <th class="px-4 py-3 text-left font-semibold text-gray-600">Expediente / Trámite</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">Período cubierto</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">Revisado</th>
                <th class="px-4 py-3 text-right font-semibold text-gray-600">Total</th>
                <th class="px-4 py-3 text-right font-semibold text-gray-600">Acciones</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse($periodosRevisados as $periodo)
            <tr class="hover:bg-gray-50 transition">
                @if(!$esPasante)
                <td class="px-4 py-3 text-gray-700 whitespace-nowrap">{{ $periodo->usuario?->name ?? '—' }}</td>
                @endif
                <td class="px-4 py-3 text-gray-700">
                    @forelse($periodo->casos as $caso)
                        <p>{{ $caso }}</p>
                    @empty
                        <span class="text-gray-400">—</span>
                    @endforelse
                </td>
                <td class="px-4 py-3 text-gray-600 whitespace-nowrap">
                    {{ $periodo->desde->format('d/m/Y') }} — {{ $periodo->hasta->format('d/m/Y') }}
                </td>
                <td class="px-4 py-3 text-gray-500 whitespace-nowrap">{{ $periodo->revisado_at?->format('d/m/Y H:i') }}</td>
                <td class="px-4 py-3 text-right font-bold text-amber-800 whitespace-nowrap">{{ number_format($periodo->total, 2) }} Bs</td>
                <td class="px-4 py-3 text-right whitespace-nowrap">
                    <a href="{{ route('reportes.pasantes.ver', $periodo) }}" target="_blank"
                       class="text-xs text-red-700 hover:underline font-medium {{ $esPasante ? '' : 'mr-3' }}">
                        <i class="fas fa-file-pdf"></i> Ver PDF
                    </a>
                    @if(!$esPasante)
                    <a href="{{ route('reportes.pasantes.ver-cliente', $periodo) }}" target="_blank"
                       class="text-xs text-brand-700 hover:underline font-medium">
                        <i class="fas fa-file-invoice"></i> Generar PDF cliente
                    </a>
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="{{ $columnas }}" class="px-4 py-10 text-center text-gray-400">
                    <i class="fas fa-check-double text-3xl mb-2"></i>
                    <p>Todavía no hay PDFs revisados.</p>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
    </div>
</div>

@endsection
