@extends('layouts.app')

@section('title', 'Reporte de Trámites')
@section('header', 'Reporte de Trámites')

@section('content')

@php
    $estadoIconos = [
        'iniciado'   => ['fa-play', 'amber'],
        'en_proceso' => ['fa-spinner', 'indigo'],
        'presentado' => ['fa-paper-plane', 'purple'],
        'observado'  => ['fa-eye', 'yellow'],
        'aprobado'   => ['fa-check', 'green'],
        'rechazado'  => ['fa-xmark', 'red'],
        'finalizado' => ['fa-flag-checkered', 'gray'],
        'cancelado'  => ['fa-ban', 'red'],
    ];
@endphp

<div class="grid grid-cols-3 md:grid-cols-5 gap-4 mb-6">
    <div class="bg-white rounded-xl shadow-sm p-4 flex items-center gap-3">
        <div class="w-10 h-10 bg-cyan-100 text-cyan-600 rounded-lg flex items-center justify-center text-base flex-shrink-0">
            <i class="fas fa-file-circle-check"></i>
        </div>
        <div>
            <p class="text-xl font-bold text-gray-800">{{ $resumen['total'] }}</p>
            <p class="text-xs text-gray-500">Total</p>
        </div>
    </div>
    @foreach($estados as $estado)
    @php [$icono, $color] = $estadoIconos[$estado] ?? ['fa-circle', 'gray']; @endphp
    <div class="bg-white rounded-xl shadow-sm p-4 flex items-center gap-3">
        <div class="w-10 h-10 bg-{{ $color }}-100 text-{{ $color }}-600 rounded-lg flex items-center justify-center text-base flex-shrink-0">
            <i class="fas {{ $icono }}"></i>
        </div>
        <div>
            <p class="text-xl font-bold text-gray-800">{{ $resumen['por_estado'][$estado] }}</p>
            <p class="text-xs text-gray-500">{{ ucfirst(str_replace('_', ' ', $estado)) }}</p>
        </div>
    </div>
    @endforeach
</div>

<form method="GET" class="bg-white rounded-xl shadow-sm p-4 mb-6 flex flex-wrap gap-3 items-end">
    <div>
        <label class="block text-xs text-gray-500 mb-1">Estado</label>
        <select name="estado" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
            <option value="">Todos</option>
            @foreach($estados as $e)
                <option value="{{ $e }}" {{ request('estado') === $e ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $e)) }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-xs text-gray-500 mb-1">Prioridad</label>
        <select name="prioridad" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
            <option value="">Todas</option>
            @foreach($prioridades as $p)
                <option value="{{ $p }}" {{ request('prioridad') === $p ? 'selected' : '' }}>{{ ucfirst($p) }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-xs text-gray-500 mb-1">Tipo de trámite</label>
        <select name="tipo_tramite_id" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
            <option value="">Todos</option>
            @foreach($tiposTramite as $t)
                <option value="{{ $t->id }}" {{ request('tipo_tramite_id') == $t->id ? 'selected' : '' }}>{{ $t->nombre }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-xs text-gray-500 mb-1">Institución pública</label>
        <select name="institucion_publica_id" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
            <option value="">Todas</option>
            @foreach($instituciones as $i)
                <option value="{{ $i->id }}" {{ request('institucion_publica_id') == $i->id ? 'selected' : '' }}>{{ $i->nombre }}</option>
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
    <a href="{{ route('reportes.tramites') }}" class="text-gray-500 text-sm py-2 hover:text-gray-700">
        <i class="fas fa-times"></i> Limpiar
    </a>
    <a href="{{ route('reportes.tramites.pdf', request()->query()) }}"
       class="ml-auto bg-red-700 hover:bg-red-800 text-white px-4 py-2 rounded-lg text-sm flex items-center gap-2">
        <i class="fas fa-file-pdf"></i> Descargar PDF
    </a>
</form>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200 text-sm">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">Código</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">Nombre</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">Cliente</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">Responsable</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">Institución</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">Fecha inicio</th>
                <th class="px-4 py-3 text-center font-semibold text-gray-600">Prioridad</th>
                <th class="px-4 py-3 text-center font-semibold text-gray-600">Estado</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse($tramites as $tramite)
            <tr class="hover:bg-gray-50 transition">
                <td class="px-4 py-3">
                    <a href="{{ route('tramites.show', $tramite) }}" class="font-mono text-xs text-cyan-700 hover:underline">
                        {{ $tramite->codigo }}
                    </a>
                </td>
                <td class="px-4 py-3 text-gray-700">{{ Str::limit($tramite->nombre, 35) }}</td>
                <td class="px-4 py-3 text-gray-600">{{ $tramite->cliente->nombre_completo }}</td>
                <td class="px-4 py-3 text-gray-600">{{ $tramite->responsable?->nombre ?? '—' }}</td>
                <td class="px-4 py-3 text-gray-600">{{ $tramite->institucionPublica?->nombre ?? '—' }}</td>
                <td class="px-4 py-3 text-gray-600">{{ $tramite->fecha_inicio->format('d/m/Y') }}</td>
                <td class="px-4 py-3 text-center">
                    <span class="px-2 py-0.5 rounded text-xs font-medium bg-{{ $tramite->prioridad_color }}-100 text-{{ $tramite->prioridad_color }}-700">
                        {{ ucfirst($tramite->prioridad) }}
                    </span>
                </td>
                <td class="px-4 py-3 text-center">
                    <span class="px-2 py-0.5 rounded text-xs font-medium bg-{{ $tramite->estado_color }}-100 text-{{ $tramite->estado_color }}-700">
                        {{ ucfirst(str_replace('_', ' ', $tramite->estado)) }}
                    </span>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="8" class="px-4 py-10 text-center text-gray-400">
                    <i class="fas fa-file-circle-check text-3xl mb-2"></i>
                    <p>No se encontraron trámites.</p>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
    </div>
    <div class="px-4 py-3 border-t">{{ $tramites->links() }}</div>
</div>
@endsection
