@extends('layouts.app')

@section('title', 'Historial de seguimiento')
@section('header', 'Historial de seguimiento')

@section('header-actions')
    <button type="button" onclick="history.back()"
       class="bg-white border hover:bg-gray-50 text-gray-700 px-3 py-2 rounded-lg text-sm flex items-center gap-2">
        <i class="fas fa-arrow-left"></i> Volver
    </button>
@endsection

@section('content')

<div class="bg-white rounded-xl shadow-sm p-5 mb-6">
    <p class="text-sm text-gray-500">Expediente</p>
    <p class="font-semibold text-gray-800">{{ $expediente->caratula }} <span class="text-gray-400 font-normal font-mono text-sm">— NUREJ {{ $expediente->numero }}</span></p>
    <p class="text-sm text-gray-500 mb-4">{{ $expediente->cliente->nombre_completo }}</p>

    <dl class="grid grid-cols-2 md:grid-cols-3 gap-x-6 gap-y-3 text-sm border-t pt-4">
        <div>
            <dt class="text-gray-400 text-xs uppercase tracking-wide">Demandante</dt>
            <dd class="text-gray-700">{{ $expediente->demandantes->pluck('nombre')->implode(', ') ?: '—' }}</dd>
        </div>
        <div>
            <dt class="text-gray-400 text-xs uppercase tracking-wide">Demandado</dt>
            <dd class="text-gray-700">{{ $expediente->demandados->pluck('nombre')->implode(', ') ?: '—' }}</dd>
        </div>
        <div>
            <dt class="text-gray-400 text-xs uppercase tracking-wide">Tipo de proceso</dt>
            <dd class="text-gray-700">{{ $expediente->tipo_proceso ?: '—' }}</dd>
        </div>
        <div>
            <dt class="text-gray-400 text-xs uppercase tracking-wide">Juzgado</dt>
            <dd class="text-gray-700">{{ $expediente->juzgado ?: '—' }}</dd>
        </div>
        <div>
            <dt class="text-gray-400 text-xs uppercase tracking-wide">Piso</dt>
            <dd class="text-gray-700">{{ $expediente->piso ?: '—' }}</dd>
        </div>
        <div>
            <dt class="text-gray-400 text-xs uppercase tracking-wide">Dirección</dt>
            <dd class="text-gray-700">{{ $expediente->direccion ?: '—' }}</dd>
        </div>
        <div>
            <dt class="text-gray-400 text-xs uppercase tracking-wide">Abogado responsable</dt>
            <dd class="text-gray-700">{{ $expediente->abogado?->nombre ?? '—' }}</dd>
        </div>
        <div>
            <dt class="text-gray-400 text-xs uppercase tracking-wide">Encargado actual</dt>
            <dd class="text-gray-700">{{ $expediente->encargado_actual ?? '—' }}</dd>
        </div>
        <div>
            <dt class="text-gray-400 text-xs uppercase tracking-wide">Encargado anterior</dt>
            <dd class="text-gray-700">{{ $expediente->enc_anterior ?? '—' }}</dd>
        </div>
        <div class="col-span-2 md:col-span-3">
            <dt class="text-gray-400 text-xs uppercase tracking-wide">Seguimiento (encargados)</dt>
            <dd class="text-gray-700">{{ $expediente->seguidores->pluck('name')->implode(', ') ?: '—' }}</dd>
        </div>
        @if($expediente->descripcion)
        <div class="col-span-2 md:col-span-3">
            <dt class="text-gray-400 text-xs uppercase tracking-wide">Estado del proceso</dt>
            <dd class="text-gray-700 whitespace-pre-line">{{ $expediente->descripcion }}</dd>
        </div>
        @endif
    </dl>
</div>

<div class="bg-white rounded-xl shadow-sm overflow-hidden mb-6">
    <div class="px-4 py-3 border-b bg-gray-50">
        <h3 class="font-semibold text-gray-700">
            <i class="fas fa-inbox text-brand-600 mr-2"></i>
            Recientes, sin revisar ({{ $seguimientosSinRevisar->count() }})
        </h3>
    </div>
    <div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200 text-sm">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-4 py-3 text-left font-semibold text-gray-600 w-4"></th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">Fecha</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">Acción realizada</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">Descripción</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">Registrado por</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">Vencimiento</th>
                <th class="px-4 py-3 text-right font-semibold text-gray-600">Acciones</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse($seguimientosSinRevisar as $seg)
                @include('seguimientos._fila-historial', ['seg' => $seg, 'conBotonRevisar' => true])
            @empty
            <tr>
                <td colspan="7" class="px-4 py-10 text-center text-gray-400">
                    <i class="fas fa-list-check text-3xl mb-2"></i>
                    <p>No hay seguimiento pendiente de revisión.</p>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="px-4 py-3 border-b bg-gray-50">
        <h3 class="font-semibold text-gray-700">
            <i class="fas fa-check-double text-gray-400 mr-2"></i>
            Ya revisados ({{ $seguimientosRevisados->count() }})
        </h3>
    </div>
    <div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200 text-sm">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-4 py-3 text-left font-semibold text-gray-600 w-4"></th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">Fecha</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">Tipo de Acción</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">Descripción</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">Registrado por</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">Vencimiento</th>
                <th class="px-4 py-3 text-right font-semibold text-gray-600">Revisado</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse($seguimientosRevisados as $seg)
                @include('seguimientos._fila-historial', ['seg' => $seg, 'conBotonRevisar' => false])
            @empty
            <tr>
                <td colspan="7" class="px-4 py-10 text-center text-gray-400">
                    <i class="fas fa-check-double text-3xl mb-2"></i>
                    <p>Todavía no hay seguimiento revisado.</p>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
    </div>
</div>

@endsection
