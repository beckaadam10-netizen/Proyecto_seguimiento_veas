@extends('layouts.app')

@section('title', 'Editar Expediente')
@section('header', 'Editar: ' . $expediente->numero)

@section('content')
<div class="max-w-3xl">
    <form method="POST" action="{{ route('expedientes.update', $expediente) }}" class="space-y-6">
        @csrf @method('PUT')

        <div class="bg-white rounded-xl shadow-sm p-6 space-y-4">
            <h3 class="font-semibold text-gray-700 border-b pb-2">Identificación</h3>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">NUREJ *</label>
                    <input type="text" name="numero" value="{{ old('numero', $expediente->numero) }}" required
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-mono focus:ring-2 focus:ring-brand-400">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Cliente *</label>
                    <div class="flex gap-2">
                        <select name="cliente_id" required id="select-cliente-expediente-editar"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                            @foreach($clientes as $c)
                                <option value="{{ $c->id }}" {{ old('cliente_id', $expediente->cliente_id) == $c->id ? 'selected' : '' }}>
                                    {{ $c->nombre_completo }}
                                </option>
                            @endforeach
                        </select>
                        @if(auth()->user()->puede('clientes', 'crear'))
                        <button type="button" onclick="abrirClienteRapido('select-cliente-expediente-editar')" title="Nuevo cliente"
                                class="shrink-0 border border-gray-300 rounded-lg w-9 h-9 flex items-center justify-center text-gray-500 hover:text-brand-700 hover:border-brand-400">
                            <i class="fas fa-plus"></i>
                        </button>
                        @endif
                    </div>
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Carátula *</label>
                <input type="text" name="caratula" value="{{ old('caratula', $expediente->caratula) }}" required
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
            </div>
            <div class="grid grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de causa *</label>
                    <select name="tipo_causa" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                        @foreach($tipos as $t)
                            <option value="{{ $t }}" {{ old('tipo_causa', $expediente->tipo_causa) === $t ? 'selected' : '' }}>{{ ucfirst($t) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Estado *</label>
                    <select name="estado_expediente_id" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                        @foreach($estados as $e)
                            <option value="{{ $e->id }}" {{ (string) old('estado_expediente_id', (string) $expediente->estado_expediente_id) === (string) $e->id ? 'selected' : '' }}>{{ $e->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Abogado responsable</label>
                    <div class="flex gap-2">
                        <select name="abogado_id" id="select-abogado-expediente-editar"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                            <option value="">— Sin asignar —</option>
                            @foreach($abogados as $a)
                                <option value="{{ $a->id }}" {{ old('abogado_id', $expediente->abogado_id) == $a->id ? 'selected' : '' }}>{{ $a->nombre }}</option>
                            @endforeach
                        </select>
                        @if(auth()->user()->puede('abogados', 'crear'))
                        <button type="button" title="Nuevo abogado"
                                onclick="abrirAltaRapida({titulo: 'Nuevo abogado', etiqueta: 'Nombre', placeholder: 'Ej: Juan Pérez', url: '{{ route('abogados.store') }}', selectId: 'select-abogado-expediente-editar'})"
                                class="shrink-0 border border-gray-300 rounded-lg w-9 h-9 flex items-center justify-center text-gray-500 hover:text-brand-700 hover:border-brand-400">
                            <i class="fas fa-plus"></i>
                        </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6 space-y-4">
            <h3 class="font-semibold text-gray-700 border-b pb-2">Datos judiciales</h3>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Lugar asignado en el reparto</label>
                    <input type="text" name="juzgado" value="{{ old('juzgado', $expediente->juzgado) }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fecha de recepción</label>
                    <input type="date" name="fecha_recepcion" value="{{ old('fecha_recepcion', $expediente->fecha_recepcion?->format('Y-m-d')) }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Monto reclamado ($)</label>
                <input type="number" name="monto_reclamado" value="{{ old('monto_reclamado', $expediente->monto_reclamado) }}" min="0" step="0.01"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Descripción</label>
                <textarea name="descripcion" rows="3"
                          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">{{ old('descripcion', $expediente->descripcion) }}</textarea>
            </div>
        </div>

        <div class="flex gap-3">
            <button type="submit" class="bg-brand-600 hover:bg-brand-700 text-white px-6 py-2 rounded-lg font-medium">
                <i class="fas fa-save mr-1"></i> Guardar cambios
            </button>
            <a href="{{ route('expedientes.show', $expediente) }}" class="text-gray-600 hover:text-gray-800 px-4 py-2 rounded-lg border hover:bg-gray-50">
                Cancelar
            </a>
        </div>
    </form>
</div>
@endsection
