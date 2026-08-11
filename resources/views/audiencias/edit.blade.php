@extends('layouts.app')

@section('title', 'Editar Audiencia')
@section('header', 'Editar Audiencia')

@section('content')
<div class="max-w-2xl">
    <form method="POST" action="{{ route('audiencias.update', $audiencia) }}" class="space-y-6">
        @csrf @method('PUT')

        <div class="bg-white rounded-xl shadow-sm p-6 space-y-4">
            <h3 class="font-semibold text-gray-700 border-b pb-2">Datos de la audiencia</h3>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Expediente *</label>
                <select name="expediente_id" required
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                    @foreach($expedientes as $exp)
                        <option value="{{ $exp->id }}" {{ old('expediente_id', $audiencia->expediente_id) == $exp->id ? 'selected' : '' }}>
                            {{ $exp->numero }} — {{ $exp->cliente->nombre_completo }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Título *</label>
                <input type="text" name="titulo" value="{{ old('titulo', $audiencia->titulo) }}" required
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tipo *</label>
                    <select name="tipo_audiencia_id" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                        <option value="">— Seleccioná un tipo —</option>
                        @foreach($tiposAudiencia as $t)
                            <option value="{{ $t->id }}" {{ old('tipo_audiencia_id', $audiencia->tipo_audiencia_id) == $t->id ? 'selected' : '' }}>{{ $t->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Estado *</label>
                    <select name="estado" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                        @foreach($estados as $e)
                            <option value="{{ $e }}" {{ old('estado', $audiencia->estado) === $e ? 'selected' : '' }}>{{ ucfirst($e) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fecha y hora *</label>
                    <input type="datetime-local" name="fecha_hora"
                           value="{{ old('fecha_hora', $audiencia->fecha_hora->format('Y-m-d\TH:i')) }}" required
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Duración (minutos) *</label>
                    <input type="number" name="duracion_estimada"
                           value="{{ old('duracion_estimada', $audiencia->duracion_estimada) }}"
                           min="15" max="480" required
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Lugar</label>
                    <input type="text" name="lugar" value="{{ old('lugar', $audiencia->lugar) }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Sala</label>
                    <input type="text" name="sala" value="{{ old('sala', $audiencia->sala) }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Abogado responsable</label>
                <div class="flex gap-2">
                    <select name="abogado_id" id="select-abogado-audiencia-editar"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                        <option value="">— Sin asignar —</option>
                        @foreach($abogados as $a)
                            <option value="{{ $a->id }}" {{ old('abogado_id', $audiencia->abogado_id) == $a->id ? 'selected' : '' }}>{{ $a->nombre }}</option>
                        @endforeach
                    </select>
                    @if(auth()->user()->puede('abogados', 'crear'))
                    <button type="button" title="Nuevo abogado"
                            onclick="abrirAltaRapida({titulo: 'Nuevo abogado', etiqueta: 'Nombre', placeholder: 'Ej: Juan Pérez', url: '{{ route('abogados.store') }}', selectId: 'select-abogado-audiencia-editar'})"
                            class="shrink-0 border border-gray-300 rounded-lg w-9 h-9 flex items-center justify-center text-gray-500 hover:text-brand-700 hover:border-brand-400">
                        <i class="fas fa-plus"></i>
                    </button>
                    @endif
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Resultado / Acta</label>
                <textarea name="resultado" rows="3"
                          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400"
                          placeholder="Resultado de la audiencia, acuerdos alcanzados...">{{ old('resultado', $audiencia->resultado) }}</textarea>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Próxima fecha</label>
                <input type="date" name="proxima_fecha" value="{{ old('proxima_fecha', $audiencia->proxima_fecha?->format('Y-m-d')) }}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
            </div>

            <div class="flex items-center gap-2">
                <input type="checkbox" name="notificado_cliente" id="notificado" value="1"
                       {{ old('notificado_cliente', $audiencia->notificado_cliente) ? 'checked' : '' }}
                       class="w-4 h-4 text-brand-700 rounded">
                <label for="notificado" class="text-sm text-gray-700">Cliente notificado</label>
            </div>
        </div>

        <div class="flex gap-3">
            <button type="submit" class="bg-brand-600 hover:bg-brand-700 text-white px-6 py-2 rounded-lg font-medium">
                <i class="fas fa-save mr-1"></i> Guardar cambios
            </button>
            <a href="{{ route('expedientes.show', $audiencia->expediente_id) }}"
               class="text-gray-600 hover:text-gray-800 px-4 py-2 rounded-lg border hover:bg-gray-50">
                Cancelar
            </a>
        </div>
    </form>
</div>
@endsection
