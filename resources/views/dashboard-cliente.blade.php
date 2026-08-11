@extends('layouts.app')

@section('title', 'Mi panel')
@section('header', 'Hola, ' . auth()->user()->name)

@section('content')

<div class="bg-white rounded-xl shadow-sm p-5 mb-6">
    <div class="flex items-center gap-4">
        <div class="w-12 h-12 {{ $saldo_pendiente > 0 ? 'bg-red-100 text-red-600' : 'bg-green-100 text-green-600' }} rounded-xl flex items-center justify-center text-xl flex-shrink-0">
            <i class="fas fa-file-invoice-dollar"></i>
        </div>
        <div class="flex-1">
            <p class="text-2xl font-bold {{ $saldo_pendiente > 0 ? 'text-red-600' : 'text-gray-800' }}">{{ number_format($saldo_pendiente, 2) }} Bs</p>
            <p class="text-sm text-gray-500">{{ $saldo_pendiente > 0 ? 'Saldo pendiente de pago' : 'No tenés saldo pendiente' }}</p>
        </div>
        @if($detalle_saldo_pendiente->isNotEmpty())
        <button type="button" onclick="document.getElementById('detalle-saldo-pendiente').classList.toggle('hidden')"
                class="text-sm text-brand-700 hover:text-brand-800 font-medium flex-shrink-0">
            <i class="fas fa-list-ul mr-1"></i> Ver detalles
        </button>
        @endif
    </div>

    @if($detalle_saldo_pendiente->isNotEmpty())
    <div id="detalle-saldo-pendiente" class="hidden mt-4 pt-4 border-t border-gray-100">
        <table class="min-w-full text-sm">
            <thead>
                <tr class="text-left text-xs text-gray-400 uppercase">
                    <th class="pb-2 pr-3">Título</th>
                    <th class="pb-2 pr-3">Descripción</th>
                    <th class="pb-2 text-right">Monto</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($detalle_saldo_pendiente as $item)
                <tr>
                    <td class="py-2 pr-3 font-medium text-gray-800">{{ $item->titulo }}</td>
                    <td class="py-2 pr-3 text-gray-500">{{ $item->descripcion ?: '—' }}</td>
                    <td class="py-2 text-right font-semibold text-red-600 whitespace-nowrap">{{ number_format($item->monto, 2) }} Bs</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

    <div class="bg-white rounded-xl shadow-sm">
        <div class="flex items-center justify-between p-5 border-b">
            <h3 class="font-semibold text-gray-700">
                <i class="fas fa-file-circle-check text-cyan-600 mr-2"></i>
                MIS TRAMITES Y EXPEDIENTES ({{ $tramites->count() }})
            </h3>
        </div>
        <div class="divide-y divide-gray-100">
            @forelse($tramites as $tra)
            <a href="{{ route('tramites.show', $tra) }}" class="px-5 py-4 hover:bg-gray-50 transition flex items-center gap-4">
                <div class="flex-1 min-w-0">
                    <p class="font-medium text-brand-800 truncate">{{ $tra->nombre }}</p>
                    <p class="text-xs text-gray-500 mt-0.5">
                        {{ $tra->codigo }} · {{ $tra->tipoTramite?->nombre ?? 'Sin tipo' }}
                    </p>
                    @if($tra->saldo_pendiente > 0)
                        <p class="text-xs text-red-600 font-medium mt-0.5">Debe {{ number_format($tra->saldo_pendiente, 2) }} Bs</p>
                    @endif
                </div>
                <span class="px-2 py-0.5 rounded text-xs font-medium bg-{{ $tra->estado_color }}-100 text-{{ $tra->estado_color }}-700 flex-shrink-0">
                    {{ $tra->estado_label }}
                </span>
                <i class="fas fa-arrow-right text-xs text-gray-400 flex-shrink-0"></i>
            </a>
            @empty
            <div class="px-5 py-10 text-center text-gray-400">
                <i class="fas fa-file-circle-check text-3xl mb-2"></i>
                <p class="text-sm">Todavía no tiene trámites registrados.</p>
            </div>
            @endforelse
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm">
        <div class="flex items-center justify-between p-5 border-b">
            <h3 class="font-semibold text-gray-700">
                <i class="fas fa-folder-open text-brand-600 mr-2"></i>
                Mis expedientes ({{ $expedientes->count() }})
            </h3>
        </div>
        <div class="divide-y divide-gray-100">
            @forelse($expedientes as $exp)
            <a href="{{ route('expedientes.show', $exp) }}" class="px-5 py-4 hover:bg-gray-50 transition flex items-center gap-4">
                <div class="flex-1 min-w-0">
                    <p class="font-medium text-brand-800 truncate">{{ $exp->caratula }}</p>
                    <p class="text-xs text-gray-500 mt-0.5">
                        {{ $exp->numero }} · {{ ucfirst($exp->tipo_causa) }}
                    </p>
                    @if($exp->saldo_pendiente > 0)
                        <p class="text-xs text-red-600 font-medium mt-0.5">Debe {{ number_format($exp->saldo_pendiente, 2) }} Bs</p>
                    @endif
                </div>
                <span class="px-2 py-0.5 rounded text-xs font-medium bg-{{ $exp->estado_color }}-100 text-{{ $exp->estado_color }}-700 flex-shrink-0">
                    {{ $exp->estado_label }}
                </span>
                <i class="fas fa-arrow-right text-xs text-gray-400 flex-shrink-0"></i>
            </a>
            @empty
            <div class="px-5 py-10 text-center text-gray-400">
                <i class="fas fa-folder-open text-3xl mb-2"></i>
                <p class="text-sm">Todavía no tenés expedientes registrados.</p>
            </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
