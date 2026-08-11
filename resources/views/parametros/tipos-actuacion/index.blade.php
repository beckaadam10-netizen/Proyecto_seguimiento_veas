@extends('layouts.app')

@section('title', 'Tipos de Actuación')
@section('header', 'Parámetros / Tipos de Actuación')

@section('header-actions')
    <a href="{{ route('parametros.tipos-actuacion.create') }}"
       class="bg-brand-600 hover:bg-brand-700 text-white px-4 py-2 rounded-lg text-sm font-medium flex items-center gap-2">
        <i class="fas fa-plus"></i> Nuevo Tipo
    </a>
@endsection

@section('content')

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200 text-sm">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">Nombre</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">Descripción</th>
                <th class="px-4 py-3 text-center font-semibold text-gray-600">Estado</th>
                <th class="px-4 py-3 text-right font-semibold text-gray-600">Acciones</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse($tipos as $tipo)
            <tr class="hover:bg-gray-50 transition">
                <td class="px-4 py-3 font-medium text-gray-800">{{ $tipo->nombre }}</td>
                <td class="px-4 py-3 text-gray-600">{{ $tipo->descripcion }}</td>
                <td class="px-4 py-3 text-center">
                    <span class="px-2 py-1 rounded text-xs font-medium
                        {{ $tipo->activo ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                        {{ $tipo->activo ? 'Activo' : 'Inactivo' }}
                    </span>
                </td>
                <td class="px-4 py-3 text-right">
                    <a href="{{ route('parametros.tipos-actuacion.edit', $tipo) }}" class="text-gray-500 hover:text-brand-700 mr-3">
                        <i class="fas fa-edit"></i>
                    </a>
                    <form method="POST" action="{{ route('parametros.tipos-actuacion.destroy', $tipo) }}" class="inline"
                          onsubmit="return confirm('¿Eliminar este tipo de actuación?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-gray-500 hover:text-red-600">
                            <i class="fas fa-trash"></i>
                        </button>
                    </form>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="4" class="px-4 py-10 text-center text-gray-400">
                    <i class="fas fa-list-check text-3xl mb-2"></i>
                    <p>No hay tipos de actuación registrados.</p>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="px-4 py-3 border-t border-gray-100">
        {{ $tipos->links() }}
    </div>
</div>
@endsection
