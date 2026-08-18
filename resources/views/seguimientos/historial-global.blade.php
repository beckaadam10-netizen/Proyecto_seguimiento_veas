@extends('layouts.app')

@section('title', 'Historial de Revisión')
@section('header', 'Historial de Revisión')

@section('content')

<form method="GET" class="bg-white rounded-xl shadow-sm p-4 mb-6 flex flex-wrap gap-3 items-end">
    <div class="flex-1 min-w-56">
        <label class="block text-xs text-gray-500 mb-1">Buscar expediente/trámite (NUREJ, carátula, código)</label>
        <input type="text" name="buscar" value="{{ request('buscar') }}"
               placeholder="Ej: 120/25 o 70595894"
               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
    </div>
    <div>
        <label class="block text-xs text-gray-500 mb-1">Tipo</label>
        <select name="tipo_actuacion_id" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
            <option value="">Todos</option>
            @foreach($tiposActuacion as $t)
                <option value="{{ $t->id }}" {{ request('tipo_actuacion_id') == $t->id ? 'selected' : '' }}>{{ $t->nombre }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-xs text-gray-500 mb-1">Pasante</label>
        <select name="pasante_id" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
            <option value="">Todos</option>
            @foreach($pasantes as $p)
                <option value="{{ $p->id }}" {{ request('pasante_id') == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
            @endforeach
        </select>
    </div>
    <button type="submit" class="bg-brand-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-brand-700">
        <i class="fas fa-search"></i> Filtrar
    </button>
    <a href="{{ route('historial-revision.index') }}" class="text-gray-500 text-sm py-2 hover:text-gray-700">
        <i class="fas fa-times"></i> Limpiar
    </a>
</form>

<div class="bg-white rounded-xl shadow-sm overflow-hidden mb-6">
    <div class="px-4 py-3 border-b bg-gray-50">
        <h3 class="font-semibold text-gray-700">
            <i class="fas fa-inbox text-brand-600 mr-2"></i>
            Recientes, sin revisar ({{ $seguimientosSinRevisar->total() }})
        </h3>
    </div>
    <div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200 text-sm">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-4 py-3 text-left font-semibold text-gray-600 w-4"></th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">Fecha</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">Expediente / Trámite</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">Acción realizada</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">Descripción</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">Registrado por</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">Vencimiento</th>
                <th class="px-4 py-3 text-right font-semibold text-gray-600">Acciones</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse($seguimientosSinRevisar as $seg)
                @include('seguimientos._fila-historial', ['seg' => $seg, 'conBotonRevisar' => true, 'conExpediente' => true])
            @empty
            <tr>
                <td colspan="8" class="px-4 py-10 text-center text-gray-400">
                    <i class="fas fa-list-check text-3xl mb-2"></i>
                    <p>No hay seguimiento pendiente de revisión.</p>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
    </div>
    @if($seguimientosSinRevisar->hasPages())
    <div class="px-4 py-3 border-t bg-gray-50">{{ $seguimientosSinRevisar->fragment('sin-revisar')->links() }}</div>
    @endif
</div>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="px-4 py-3 border-b bg-gray-50">
        <h3 class="font-semibold text-gray-700">
            <i class="fas fa-check-double text-gray-400 mr-2"></i>
            Ya revisados ({{ $seguimientosRevisados->total() }})
        </h3>
    </div>
    <div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200 text-sm">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-4 py-3 text-left font-semibold text-gray-600 w-4"></th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">Fecha</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">Expediente / Trámite</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">Tipo de Acción</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">Descripción</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">Registrado por</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">Vencimiento</th>
                <th class="px-4 py-3 text-right font-semibold text-gray-600">Revisado</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse($seguimientosRevisados as $seg)
                @include('seguimientos._fila-historial', ['seg' => $seg, 'conBotonRevisar' => false, 'conExpediente' => true])
            @empty
            <tr>
                <td colspan="8" class="px-4 py-10 text-center text-gray-400">
                    <i class="fas fa-check-double text-3xl mb-2"></i>
                    <p>Todavía no hay seguimiento revisado.</p>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
    </div>
    @if($seguimientosRevisados->hasPages())
    <div class="px-4 py-3 border-t bg-gray-50">{{ $seguimientosRevisados->fragment('revisados')->links() }}</div>
    @endif
</div>

@endsection
