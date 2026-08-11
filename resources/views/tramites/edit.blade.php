@extends('layouts.app')

@section('title', 'Editar Trámite')
@section('header', 'Editar Trámite')

@section('content')
<div class="max-w-2xl">
    <form method="POST" action="{{ route('tramites.update', $tramite) }}" class="space-y-6">
        @csrf @method('PUT')

        <div class="bg-white rounded-xl shadow-sm p-6 space-y-4">
            <div class="flex items-center justify-between border-b pb-2">
                <h3 class="font-semibold text-gray-700">Datos del trámite</h3>
                <span class="text-xs font-mono text-gray-500">{{ $tramite->codigo }}</span>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nombre del trámite *</label>
                <input type="text" name="nombre" value="{{ old('nombre', $tramite->nombre) }}" required
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de trámite *</label>
                    <div class="flex gap-2">
                        <select name="tipo_tramite_id" required id="select-tipo-tramite-editar"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                            <option value="">— Seleccioná un tipo —</option>
                            @foreach($tiposTramite as $t)
                                <option value="{{ $t->id }}" {{ old('tipo_tramite_id', $tramite->tipo_tramite_id) == $t->id ? 'selected' : '' }}>{{ $t->nombre }}</option>
                            @endforeach
                        </select>
                        @if(auth()->user()->puede('parametros', 'crear'))
                        <button type="button" title="Nuevo tipo de trámite"
                                onclick="abrirAltaRapida({titulo: 'Nuevo tipo de trámite', etiqueta: 'Nombre', placeholder: 'Ej: Trámite ante SENASA', url: '{{ route('parametros.tipos-tramite.store') }}', selectId: 'select-tipo-tramite-editar'})"
                                class="shrink-0 border border-gray-300 rounded-lg w-9 h-9 flex items-center justify-center text-gray-500 hover:text-brand-700 hover:border-brand-400">
                            <i class="fas fa-plus"></i>
                        </button>
                        @endif
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Cliente *</label>
                    <div class="flex gap-2">
                        <select name="cliente_id" required id="select-cliente-tramite-editar"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                            <option value="">— Seleccioná un cliente —</option>
                            @foreach($clientes as $cli)
                                <option value="{{ $cli->id }}" {{ old('cliente_id', $tramite->cliente_id) == $cli->id ? 'selected' : '' }}>{{ $cli->nombre_completo }}</option>
                            @endforeach
                        </select>
                        @if(auth()->user()->puede('clientes', 'crear'))
                        <button type="button" onclick="abrirClienteRapido('select-cliente-tramite-editar')" title="Nuevo cliente"
                                class="shrink-0 border border-gray-300 rounded-lg w-9 h-9 flex items-center justify-center text-gray-500 hover:text-brand-700 hover:border-brand-400">
                            <i class="fas fa-plus"></i>
                        </button>
                        @endif
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Responsable</label>
                    <select name="responsable_id"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                        <option value="">— Sin asignar —</option>
                        @foreach($responsables as $r)
                            <option value="{{ $r->id }}" {{ old('responsable_id', $tramite->responsable_id) == $r->id ? 'selected' : '' }}>{{ $r->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Institución pública</label>
                    <div class="flex gap-2">
                        <select name="institucion_publica_id" id="select-institucion-tramite-editar"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                            <option value="">— Sin especificar —</option>
                            @foreach($institucionesPublicas as $ip)
                                <option value="{{ $ip->id }}" {{ old('institucion_publica_id', $tramite->institucion_publica_id) == $ip->id ? 'selected' : '' }}>{{ $ip->nombre }}</option>
                            @endforeach
                        </select>
                        @if(auth()->user()->puede('parametros', 'crear'))
                        <button type="button" title="Nueva institución pública"
                                onclick="abrirAltaRapida({titulo: 'Nueva institución pública', etiqueta: 'Nombre', placeholder: 'Ej: Ministerio de Trabajo', url: '{{ route('parametros.instituciones-publicas.store') }}', selectId: 'select-institucion-tramite-editar'})"
                                class="shrink-0 border border-gray-300 rounded-lg w-9 h-9 flex items-center justify-center text-gray-500 hover:text-brand-700 hover:border-brand-400">
                            <i class="fas fa-plus"></i>
                        </button>
                        @endif
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Estado *</label>
                    <select name="estado" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                        @foreach($estados as $e)
                            <option value="{{ $e }}" {{ old('estado', $tramite->estado) === $e ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $e)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Prioridad *</label>
                    <select name="prioridad" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                        @foreach($prioridades as $p)
                            <option value="{{ $p }}" {{ old('prioridad', $tramite->prioridad) === $p ? 'selected' : '' }}>{{ ucfirst($p) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fecha de inicio *</label>
                    <input type="date" name="fecha_inicio" value="{{ old('fecha_inicio', $tramite->fecha_inicio->format('Y-m-d')) }}" required
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fecha de finalización aproximada</label>
                    <input type="date" name="fecha_fin_aproximada" value="{{ old('fecha_fin_aproximada', $tramite->fecha_fin_aproximada?->format('Y-m-d')) }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Observaciones</label>
                <textarea name="observaciones" rows="3"
                          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">{{ old('observaciones', $tramite->observaciones) }}</textarea>
            </div>
        </div>

        <div class="flex gap-3">
            <button type="submit" class="bg-brand-600 hover:bg-brand-700 text-white px-6 py-2 rounded-lg font-medium">
                <i class="fas fa-save mr-1"></i> Guardar cambios
            </button>
            <a href="{{ route('tramites.show', $tramite) }}" class="text-gray-600 hover:text-gray-800 px-4 py-2 rounded-lg border hover:bg-gray-50">
                Cancelar
            </a>
        </div>
    </form>
</div>
@endsection
