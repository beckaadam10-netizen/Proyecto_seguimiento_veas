@extends('layouts.app')

@section('title', 'Editar Tipo de Documento')
@section('header', 'Parámetros / Editar Tipo de Documento')

@section('content')
<div class="max-w-xl">
    <form method="POST" action="{{ route('parametros.tipos-documento.update', $tipo) }}" class="space-y-6">
        @csrf @method('PUT')

        <div class="bg-white rounded-xl shadow-sm p-6 space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nombre *</label>
                <input type="text" name="nombre" value="{{ old('nombre', $tipo->nombre) }}" required
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                @error('nombre')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Descripción</label>
                <textarea name="descripcion" rows="3"
                          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">{{ old('descripcion', $tipo->descripcion) }}</textarea>
            </div>
            <div class="flex items-center gap-2">
                <input type="hidden" name="activo" value="0">
                <input type="checkbox" name="activo" id="activo" value="1" {{ old('activo', $tipo->activo) ? 'checked' : '' }}
                       class="rounded border-gray-300 text-brand-700 focus:ring-brand-400">
                <label for="activo" class="text-sm text-gray-700">Activo</label>
            </div>
        </div>

        <div class="flex gap-3">
            <button type="submit" class="bg-brand-600 hover:bg-brand-700 text-white px-6 py-2 rounded-lg font-medium">
                <i class="fas fa-save mr-1"></i> Guardar cambios
            </button>
            <a href="{{ route('parametros.index', ['abrir' => 'documento']) }}" class="text-gray-600 hover:text-gray-800 px-4 py-2 rounded-lg border hover:bg-gray-50">
                Cancelar
            </a>
        </div>
    </form>
</div>
@endsection
