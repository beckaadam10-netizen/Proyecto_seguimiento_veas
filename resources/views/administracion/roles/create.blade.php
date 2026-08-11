@extends('layouts.app')

@section('title', 'Nuevo Rol')
@section('header', 'Administración / Nuevo Rol')

@section('content')
<div class="max-w-2xl">
    <form method="POST" action="{{ route('administracion.roles.store') }}" class="space-y-6">
        @csrf

        <div class="bg-white rounded-xl shadow-sm p-6 space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nombre *</label>
                <input type="text" name="nombre" value="{{ old('nombre') }}" required
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                @error('nombre')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Permisos</label>
                @include('administracion.roles._matriz-permisos', [
                    'modulosPermisos' => $modulosPermisos,
                    'seleccionados'   => old('permisos', []),
                    'idPrefix'        => 'crear',
                ])
            </div>
        </div>

        <div class="flex gap-3">
            <button type="submit" class="bg-brand-600 hover:bg-brand-700 text-white px-6 py-2 rounded-lg font-medium">
                <i class="fas fa-save mr-1"></i> Guardar
            </button>
            <a href="{{ route('administracion.index', ['abrir' => 'roles']) }}" class="text-gray-600 hover:text-gray-800 px-4 py-2 rounded-lg border hover:bg-gray-50">
                Cancelar
            </a>
        </div>
    </form>
</div>
@endsection
