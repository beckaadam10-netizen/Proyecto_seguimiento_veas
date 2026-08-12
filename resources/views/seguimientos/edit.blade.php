@extends('layouts.app')

@section('title', 'Editar Actuación')
@section('header', 'Editar Actuación')

@section('content')
<div class="max-w-2xl">
    <form method="POST" action="{{ route('seguimientos.update', $seguimiento) }}" class="space-y-6">
        @csrf @method('PUT')

        <div class="bg-white rounded-xl shadow-sm p-6 space-y-4">
            <h3 class="font-semibold text-gray-700 border-b pb-2">Datos de la actuación</h3>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Vincular a *</label>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <select name="expediente_id"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                            <option value="">— Ningún expediente —</option>
                            @foreach($expedientes as $exp)
                                <option value="{{ $exp->id }}" {{ old('expediente_id', $seguimiento->expediente_id) == $exp->id ? 'selected' : '' }}>
                                    {{ $exp->numero }} — {{ $exp->cliente->nombre_completo }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <select name="tramite_id"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                            <option value="">— Ningún trámite —</option>
                            @foreach($tramites as $tra)
                                <option value="{{ $tra->id }}" {{ old('tramite_id', $seguimiento->tramite_id) == $tra->id ? 'selected' : '' }}>
                                    {{ $tra->codigo }} — {{ $tra->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <p class="text-xs text-gray-400 mt-1">Elegí un expediente o un trámite (no ambos).</p>
                @error('expediente_id')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tipo *</label>
                    <div class="flex gap-2">
                        <select name="tipo_actuacion_id" required id="select-tipo-actuacion-editar"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                            <option value="">— Seleccioná un tipo —</option>
                            @foreach($tiposActuacion as $t)
                                <option value="{{ $t->id }}" {{ old('tipo_actuacion_id', $seguimiento->tipo_actuacion_id) == $t->id ? 'selected' : '' }}>
                                    {{ $t->nombre }}
                                </option>
                            @endforeach
                        </select>
                        @if(auth()->user()->puede('parametros', 'crear'))
                        <button type="button" title="Nuevo tipo de actuación"
                                onclick="abrirAltaRapida({titulo: 'Nuevo tipo de actuación', etiqueta: 'Nombre', placeholder: 'Ej: Presentación de escrito', url: '{{ route('parametros.tipos-actuacion.store') }}', selectId: 'select-tipo-actuacion-editar'})"
                                class="shrink-0 border border-gray-300 rounded-lg w-9 h-9 flex items-center justify-center text-gray-500 hover:text-brand-700 hover:border-brand-400">
                            <i class="fas fa-plus"></i>
                        </button>
                        @endif
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Prioridad *</label>
                    <select name="prioridad" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                        @foreach($prioridades as $p)
                            <option value="{{ $p }}" {{ old('prioridad', $seguimiento->prioridad) === $p ? 'selected' : '' }}>{{ ucfirst($p) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Título *</label>
                <input type="text" name="titulo" value="{{ old('titulo', $seguimiento->titulo) }}" required
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Descripción *</label>
                <textarea name="descripcion" rows="4" required
                          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">{{ old('descripcion', $seguimiento->descripcion) }}</textarea>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fecha de actuación *</label>
                    @if(auth()->user()->esAdmin())
                    <input type="date" name="fecha_actuacion" value="{{ old('fecha_actuacion', $seguimiento->fecha_actuacion->format('Y-m-d')) }}" required
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                    @else
                    <input type="date" name="fecha_actuacion" value="{{ old('fecha_actuacion', $seguimiento->fecha_actuacion->format('Y-m-d')) }}" readonly required
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-gray-100 text-gray-500 cursor-not-allowed">
                    @endif
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fecha de vencimiento</label>
                    <input type="date" name="fecha_vencimiento" value="{{ old('fecha_vencimiento', $seguimiento->fecha_vencimiento?->format('Y-m-d')) }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                </div>
            </div>

            @if($seguimiento->archivo_adjunto)
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de documento adjunto</label>
                <div class="flex gap-2">
                    <select name="tipo_documento_id" id="select-tipo-documento-editar"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                        <option value="">— Seleccioná un tipo —</option>
                        @foreach($tiposDocumento as $t)
                            <option value="{{ $t->id }}" {{ old('tipo_documento_id', $seguimiento->tipo_documento_id) == $t->id ? 'selected' : '' }}>{{ $t->nombre }}</option>
                        @endforeach
                    </select>
                    @if(auth()->user()->puede('parametros', 'crear'))
                    <button type="button" title="Nuevo tipo de documento"
                            onclick="abrirAltaRapida({titulo: 'Nuevo tipo de documento', etiqueta: 'Nombre', placeholder: 'Ej: Memorial', url: '{{ route('parametros.tipos-documento.store') }}', selectId: 'select-tipo-documento-editar'})"
                            class="shrink-0 border border-gray-300 rounded-lg w-9 h-9 flex items-center justify-center text-gray-500 hover:text-brand-700 hover:border-brand-400">
                        <i class="fas fa-plus"></i>
                    </button>
                    @endif
                </div>
            </div>
            @endif

            <div class="flex items-center gap-4">
                <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                    <input type="checkbox" name="requiere_respuesta" value="1"
                           {{ old('requiere_respuesta', $seguimiento->requiere_respuesta) ? 'checked' : '' }}
                           class="w-4 h-4 text-brand-700 rounded">
                    Requiere respuesta
                </label>
                <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                    <input type="checkbox" name="respondido" value="1"
                           {{ old('respondido', $seguimiento->respondido) ? 'checked' : '' }}
                           class="w-4 h-4 text-green-600 rounded">
                    Respondido
                </label>
            </div>

            @if($seguimiento->respondido || old('respondido'))
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Fecha de respuesta</label>
                <input type="date" name="fecha_respuesta" value="{{ old('fecha_respuesta', $seguimiento->fecha_respuesta?->format('Y-m-d')) }}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
            </div>
            @endif

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Observaciones</label>
                <textarea name="observaciones" rows="2"
                          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">{{ old('observaciones', $seguimiento->observaciones) }}</textarea>
            </div>
        </div>

        <div class="flex gap-3">
            <button type="submit" class="bg-brand-600 hover:bg-brand-700 text-white px-6 py-2 rounded-lg font-medium">
                <i class="fas fa-save mr-1"></i> Guardar cambios
            </button>
            <a href="{{ $seguimiento->tramite_id ? route('tramites.show', $seguimiento->tramite_id) : route('expedientes.show', $seguimiento->expediente_id) }}"
               class="text-gray-600 hover:text-gray-800 px-4 py-2 rounded-lg border hover:bg-gray-50">
                Cancelar
            </a>
        </div>
    </form>
</div>
@endsection
