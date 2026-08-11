@extends('layouts.app')

@section('title', 'Actuación')
@section('header', $seguimiento->titulo)

@section('header-actions')
    <a href="{{ $seguimiento->tramite ? route('tramites.show', $seguimiento->tramite) : ($seguimiento->expediente ? route('expedientes.show', $seguimiento->expediente) : route('seguimientos.index')) }}"
       class="bg-white border hover:bg-gray-50 text-gray-700 px-3 py-2 rounded-lg text-sm flex items-center gap-2">
        <i class="fas fa-arrow-left"></i> Volver
    </a>
    @if(auth()->user()->puede('seguimientos', 'modificar'))
    <a href="{{ route('seguimientos.index', ['editar' => $seguimiento->id, 'id' => $seguimiento->id]) }}"
       class="bg-brand-600 hover:bg-brand-700 text-white px-3 py-2 rounded-lg text-sm flex items-center gap-2">
        <i class="fas fa-edit"></i> Editar
    </a>
    @endif
@endsection

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- Columna izquierda: datos de la actuación --}}
    <div class="col-span-2 space-y-6">
        <div class="bg-white rounded-xl shadow-sm p-6">
            <div class="flex items-center gap-2 mb-4">
                <span class="px-2 py-1 rounded text-xs font-medium bg-brand-100 text-brand-800">
                    {{ $seguimiento->tipoActuacion?->nombre ?? '—' }}
                </span>
                <span class="px-2 py-1 rounded text-xs font-medium
                    bg-{{ $seguimiento->prioridad_color }}-100 text-{{ $seguimiento->prioridad_color }}-700">
                    Prioridad {{ ucfirst($seguimiento->prioridad) }}
                </span>
                @if($seguimiento->requiere_respuesta)
                    @if($seguimiento->respondido)
                        <span class="px-2 py-1 rounded text-xs font-medium bg-green-100 text-green-700">Respondido</span>
                    @elseif($seguimiento->estaVencido())
                        <span class="px-2 py-1 rounded text-xs font-medium bg-red-100 text-red-700">Vencido</span>
                    @else
                        <span class="px-2 py-1 rounded text-xs font-medium bg-orange-100 text-orange-700">Pendiente</span>
                    @endif
                @endif
            </div>

            <dl class="space-y-3 text-sm">
                @if($seguimiento->gastos->isNotEmpty())
                <div>
                    <dt class="text-gray-500 mb-1">Gastos de actuación</dt>
                    <dd class="text-gray-800">
                        <ul class="space-y-1">
                            @foreach($seguimiento->gastos as $g)
                            <li>
                                <span class="font-medium">{{ $g->concepto }}</span>
                                — <span class="text-amber-700 font-semibold">{{ number_format($g->monto, 2) }} Bs</span>
                            </li>
                            @endforeach
                        </ul>
                    </dd>
                </div>
                @else
                <div>
                    <dt class="text-gray-500 mb-1">Descripción</dt>
                    <dd class="text-gray-800 whitespace-pre-line">{{ $seguimiento->descripcion }}</dd>
                </div>
                @endif

                <div class="grid grid-cols-2 gap-4 pt-2 border-t">
                    <div>
                        <dt class="text-gray-500">Fecha de actuación</dt>
                        <dd class="font-medium">{{ $seguimiento->fecha_actuacion->format('d/m/Y') }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Fecha de vencimiento</dt>
                        <dd class="font-medium {{ $seguimiento->estaVencido() ? 'text-red-600' : '' }}">
                            {{ $seguimiento->fecha_vencimiento?->format('d/m/Y') ?? '—' }}
                        </dd>
                    </div>
                </div>

                @if($seguimiento->respondido)
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <dt class="text-gray-500">Fecha de respuesta</dt>
                        <dd class="font-medium">{{ $seguimiento->fecha_respuesta?->format('d/m/Y') ?? '—' }}</dd>
                    </div>
                </div>
                @endif

                @if($seguimiento->observaciones)
                <div class="pt-2 border-t">
                    <dt class="text-gray-500 mb-1">Observaciones</dt>
                    <dd class="text-gray-800 whitespace-pre-line">{{ $seguimiento->observaciones }}</dd>
                </div>
                @endif
            </dl>
        </div>

        {{-- Documento adjunto --}}
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="font-semibold text-gray-700 mb-3">
                <i class="fas fa-paperclip text-amber-600 mr-2"></i> Documento adjunto
            </h3>

            @if($seguimiento->archivo_adjunto)
                <div class="flex items-center gap-4 border rounded-lg p-4">
                    <div class="w-12 h-12 rounded-lg bg-amber-100 text-amber-600 flex items-center justify-center text-xl flex-shrink-0">
                        <i class="fas {{ $seguimiento->icono_archivo }}"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="font-medium text-sm text-gray-800 truncate">{{ $seguimiento->titulo }}</p>
                        <p class="text-xs text-gray-500">
                            {{ strtoupper(pathinfo($seguimiento->archivo_adjunto, PATHINFO_EXTENSION)) }}
                            @if($seguimiento->tipoDocumento)
                                · {{ $seguimiento->tipoDocumento->nombre }}
                            @endif
                        </p>
                    </div>
                    <div class="flex gap-2 flex-shrink-0">
                        @if(auth()->user()->puede('documentos', 'ver'))
                        <a href="{{ route('documentos.ver', $seguimiento) }}" target="_blank"
                           class="bg-brand-600 hover:bg-brand-700 text-white px-3 py-2 rounded-lg text-xs font-medium flex items-center gap-1">
                            <i class="fas fa-eye"></i> Ver
                        </a>
                        @endif
                        @if(auth()->user()->puede('documentos', 'descargar'))
                        <a href="{{ route('documentos.descargar', $seguimiento) }}"
                           class="bg-white border hover:bg-gray-50 text-gray-700 px-3 py-2 rounded-lg text-xs font-medium flex items-center gap-1">
                            <i class="fas fa-download"></i> Descargar
                        </a>
                        @endif
                    </div>
                </div>
            @else
                <p class="text-sm text-gray-400">Esta actuación no tiene documentos adjuntos.</p>
            @endif
        </div>
    </div>

    {{-- Columna derecha: metadatos --}}
    <div class="space-y-4">
        <div class="bg-white rounded-xl shadow-sm p-5">
            @if($seguimiento->expediente)
                <h3 class="font-semibold text-gray-700 mb-3">Expediente</h3>
                <a href="{{ route('expedientes.show', $seguimiento->expediente) }}" class="text-brand-800 hover:underline text-sm font-mono block mb-1">
                    {{ $seguimiento->expediente->numero }}
                </a>
                <p class="text-xs text-gray-500">{{ $seguimiento->expediente->cliente->nombre_completo }}</p>
            @elseif($seguimiento->tramite)
                <h3 class="font-semibold text-gray-700 mb-3">Trámite</h3>
                <a href="{{ route('tramites.show', $seguimiento->tramite) }}" class="text-cyan-700 hover:underline text-sm font-mono block mb-1">
                    {{ $seguimiento->tramite->codigo }}
                </a>
                <p class="text-xs text-gray-500">{{ $seguimiento->tramite->nombre }}</p>
                <p class="text-xs text-gray-500">{{ $seguimiento->tramite->cliente->nombre_completo }}</p>
            @endif
        </div>

        <div class="bg-white rounded-xl shadow-sm p-5">
            <h3 class="font-semibold text-gray-700 mb-3">Registrado por</h3>
            <p class="text-sm text-gray-800">{{ $seguimiento->usuario?->name ?? '—' }}</p>
            @if($seguimiento->usuario?->rol)
                <span class="inline-block mt-1 px-2 py-0.5 rounded text-xs font-medium bg-indigo-100 text-indigo-700">
                    {{ $seguimiento->usuario->rol->nombre }}
                </span>
            @endif
            <p class="text-xs text-gray-500 mt-1">{{ $seguimiento->created_at->format('d/m/Y H:i') }}h</p>
        </div>

        @if($seguimiento->gastos->isNotEmpty())
        <div class="bg-white rounded-xl shadow-sm p-5">
            <h3 class="font-semibold text-gray-700 mb-3">
                <i class="fas fa-coins text-amber-600 mr-2"></i> Resumen de gastos
            </h3>
            <ul class="space-y-2 text-sm">
                @foreach($seguimiento->gastos as $g)
                <li class="flex items-center justify-between gap-2">
                    <span class="text-gray-600 truncate">{{ $g->concepto }}</span>
                    <span class="font-medium text-amber-700 flex-shrink-0">{{ number_format($g->monto, 2) }} Bs</span>
                </li>
                @endforeach
            </ul>
            <div class="flex items-center justify-between pt-3 mt-3 border-t">
                <span class="text-sm font-semibold text-gray-700">Total gastado</span>
                <span class="text-base font-bold text-amber-800">{{ number_format($seguimiento->gastos->sum('monto'), 2) }} Bs</span>
            </div>
        </div>
        @endif
    </div>

</div>
@endsection
