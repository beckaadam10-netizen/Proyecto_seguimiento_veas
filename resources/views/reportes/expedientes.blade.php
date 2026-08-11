@extends('layouts.app')

@section('title', 'Reporte de Expedientes')
@section('header', 'Reporte de Expedientes')

@section('content')

@php
    // Los colores vienen del catálogo (editable); el ícono es solo cosmético y no tiene
    // equivalente en el catálogo, así que para los 6 estados originales usamos uno fijo y
    // el resto (estados nuevos que agregue el usuario) cae en un ícono genérico.
    $estadoIconos = [
        'Activo'      => 'fa-folder-open',
        'Suspendido'  => 'fa-pause',
        'Archivado'   => 'fa-box-archive',
        'Cerrado'     => 'fa-lock',
        'Ganado'      => 'fa-trophy',
        'Perdido'     => 'fa-circle-xmark',
    ];
@endphp

<div class="grid grid-cols-4 md:grid-cols-7 gap-4 mb-6">
    <div class="bg-white rounded-xl shadow-sm p-4 flex items-center gap-3">
        <div class="w-10 h-10 bg-indigo-100 text-indigo-600 rounded-lg flex items-center justify-center text-base flex-shrink-0">
            <i class="fas fa-folder-open"></i>
        </div>
        <div>
            <p class="text-xl font-bold text-gray-800">{{ $resumen['total'] }}</p>
            <p class="text-xs text-gray-500">Total</p>
        </div>
    </div>
    @foreach($estados as $estado)
    @php $icono = $estadoIconos[$estado->nombre] ?? 'fa-circle'; @endphp
    <div class="bg-white rounded-xl shadow-sm p-4 flex items-center gap-3">
        <div class="w-10 h-10 bg-{{ $estado->color }}-100 text-{{ $estado->color }}-600 rounded-lg flex items-center justify-center text-base flex-shrink-0">
            <i class="fas {{ $icono }}"></i>
        </div>
        <div>
            <p class="text-xl font-bold text-gray-800">{{ $resumen['por_estado'][$estado->nombre] ?? 0 }}</p>
            <p class="text-xs text-gray-500">{{ $estado->nombre }}</p>
        </div>
    </div>
    @endforeach
</div>

<form method="GET" class="bg-white rounded-xl shadow-sm p-4 mb-6 flex flex-wrap gap-3 items-end">
    <div>
        <label class="block text-xs text-gray-500 mb-1">Estado</label>
        <select name="estado_expediente_id" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
            <option value="">Todos</option>
            @foreach($estados as $e)
                <option value="{{ $e->id }}" {{ (string) request('estado_expediente_id') === (string) $e->id ? 'selected' : '' }}>{{ $e->nombre }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-xs text-gray-500 mb-1">Tipo de causa</label>
        <select name="tipo_causa" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
            <option value="">Todos</option>
            @foreach($tipos as $t)
                <option value="{{ $t }}" {{ request('tipo_causa') === $t ? 'selected' : '' }}>{{ ucfirst($t) }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-xs text-gray-500 mb-1">Abogado</label>
        <select name="abogado_id" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
            <option value="">Todos</option>
            @foreach($abogados as $a)
                <option value="{{ $a->id }}" {{ request('abogado_id') == $a->id ? 'selected' : '' }}>{{ $a->nombre }}</option>
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
    <a href="{{ route('reportes.expedientes') }}" class="text-gray-500 text-sm py-2 hover:text-gray-700">
        <i class="fas fa-times"></i> Limpiar
    </a>
    <a href="{{ route('reportes.expedientes.pdf', request()->query()) }}"
       class="ml-auto bg-red-700 hover:bg-red-800 text-white px-4 py-2 rounded-lg text-sm flex items-center gap-2">
        <i class="fas fa-file-pdf"></i> Descargar PDF
    </a>
</form>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200 text-sm">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">NUREJ</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">Carátula</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">Cliente</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">Tipo</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">Abogado</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">Fecha recepción</th>
                <th class="px-4 py-3 text-center font-semibold text-gray-600">Estado</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse($expedientes as $exp)
            <tr class="hover:bg-gray-50 transition">
                <td class="px-4 py-3">
                    <a href="{{ route('expedientes.show', $exp) }}" class="font-mono text-xs text-brand-800 hover:underline">
                        {{ $exp->numero }}
                    </a>
                </td>
                <td class="px-4 py-3 text-gray-700">{{ Str::limit($exp->caratula, 45) }}</td>
                <td class="px-4 py-3 text-gray-600">{{ $exp->cliente->nombre_completo }}</td>
                <td class="px-4 py-3 text-gray-600">{{ ucfirst($exp->tipo_causa) }}</td>
                <td class="px-4 py-3 text-gray-600">{{ $exp->abogado?->nombre ?? '—' }}</td>
                <td class="px-4 py-3 text-gray-600">{{ $exp->fecha_recepcion?->format('d/m/Y') ?? '—' }}</td>
                <td class="px-4 py-3 text-center">
                    <span class="px-2 py-0.5 rounded text-xs font-medium bg-{{ $exp->estado_color }}-100 text-{{ $exp->estado_color }}-700">
                        {{ $exp->estado_label }}
                    </span>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="px-4 py-10 text-center text-gray-400">
                    <i class="fas fa-folder-open text-3xl mb-2"></i>
                    <p>No se encontraron expedientes.</p>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
    </div>
    <div class="px-4 py-3 border-t">{{ $expedientes->links() }}</div>
</div>
@endsection
