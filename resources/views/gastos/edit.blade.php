@extends('layouts.app')

@section('title', 'Editar Gasto')
@section('header', 'Editar Gasto')

@section('content')
<div class="max-w-lg">
    <form method="POST" action="{{ route('gastos.update', $gasto) }}" class="space-y-6">
        @csrf @method('PUT')

        <div class="bg-white rounded-xl shadow-sm p-6 space-y-4">
            <h3 class="font-semibold text-gray-700 border-b pb-2">
                Gasto para <span class="font-mono text-xs text-gray-500">{{ $gasto->tramite->codigo }}</span> — {{ $gasto->tramite->nombre }}
            </h3>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de gasto *</label>
                <div class="flex gap-2">
                    <select name="tipo_gasto_id" required id="select-tipo-gasto-editar-standalone"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                        <option value="">— Seleccioná un tipo —</option>
                        @foreach($tiposGasto as $t)
                            <option value="{{ $t->id }}" {{ old('tipo_gasto_id', $gasto->tipo_gasto_id) == $t->id ? 'selected' : '' }}>{{ $t->nombre }}</option>
                        @endforeach
                    </select>
                    @if(auth()->user()->puede('parametros', 'crear'))
                    <button type="button" title="Nuevo tipo de gasto"
                            onclick="abrirAltaRapida({titulo: 'Nuevo tipo de gasto', etiqueta: 'Nombre', placeholder: 'Ej: Arancel de inscripción', url: '{{ route('parametros.tipos-gasto.store') }}', selectId: 'select-tipo-gasto-editar-standalone'})"
                            class="shrink-0 border border-gray-300 rounded-lg w-9 h-9 flex items-center justify-center text-gray-500 hover:text-brand-700 hover:border-brand-400">
                        <i class="fas fa-plus"></i>
                    </button>
                    @endif
                </div>
                @error('tipo_gasto_id')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Concepto *</label>
                <input type="text" name="concepto" value="{{ old('concepto', $gasto->concepto) }}" required
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                @error('concepto')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Monto *</label>
                    <input type="number" name="monto" value="{{ old('monto', $gasto->monto) }}" step="0.01" min="0" required
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                    @error('monto')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fecha *</label>
                    <input type="date" name="fecha" value="{{ old('fecha', $gasto->fecha->format('Y-m-d')) }}" required
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                </div>
            </div>
        </div>

        <div class="flex gap-3">
            <button type="submit" class="bg-brand-600 hover:bg-brand-700 text-white px-6 py-2 rounded-lg font-medium">
                <i class="fas fa-save mr-1"></i> Guardar cambios
            </button>
            <a href="{{ route('tramites.show', $gasto->tramite_id) }}" class="text-gray-600 hover:text-gray-800 px-4 py-2 rounded-lg border hover:bg-gray-50">
                Cancelar
            </a>
        </div>
    </form>
</div>
@endsection
