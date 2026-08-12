@extends('layouts.app')

@section('title', 'Reporte de Clientes')
@section('header', 'Reporte de Clientes')

@section('content')

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-6">
    <div class="bg-white rounded-xl shadow-sm p-5 flex items-center gap-4">
        <div class="w-12 h-12 bg-brand-100 text-brand-700 rounded-xl flex items-center justify-center text-xl">
            <i class="fas fa-users"></i>
        </div>
        <div>
            <p class="text-2xl font-bold text-gray-800">{{ $resumen['total'] }}</p>
            <p class="text-sm text-gray-500">Total clientes</p>
        </div>
    </div>
    <div class="bg-white rounded-xl shadow-sm p-5 flex items-center gap-4">
        <div class="w-12 h-12 bg-green-100 text-green-600 rounded-xl flex items-center justify-center text-xl">
            <i class="fas fa-user-check"></i>
        </div>
        <div>
            <p class="text-2xl font-bold text-gray-800">{{ $resumen['activos'] }}</p>
            <p class="text-sm text-gray-500">Activos</p>
        </div>
    </div>
    <div class="bg-white rounded-xl shadow-sm p-5 flex items-center gap-4">
        <div class="w-12 h-12 bg-cyan-100 text-cyan-600 rounded-xl flex items-center justify-center text-xl">
            <i class="fas fa-user"></i>
        </div>
        <div>
            <p class="text-2xl font-bold text-gray-800">{{ $resumen['personas_fisicas'] }}</p>
            <p class="text-sm text-gray-500">Personas físicas</p>
        </div>
    </div>
    <div class="bg-white rounded-xl shadow-sm p-5 flex items-center gap-4">
        <div class="w-12 h-12 bg-purple-100 text-purple-600 rounded-xl flex items-center justify-center text-xl">
            <i class="fas fa-building"></i>
        </div>
        <div>
            <p class="text-2xl font-bold text-gray-800">{{ $resumen['personas_juridicas'] }}</p>
            <p class="text-sm text-gray-500">Personas jurídicas</p>
        </div>
    </div>
</div>

<form method="GET" class="bg-white rounded-xl shadow-sm p-4 mb-6 flex flex-wrap gap-3 items-end">
    <div>
        <label class="block text-xs text-gray-500 mb-1">Buscar</label>
        <input type="text" name="buscar" value="{{ request('buscar') }}" placeholder="Nombre, C.I/NIT, email..."
               class="border border-gray-300 rounded-lg px-3 py-2 text-sm w-56">
    </div>
    <div>
        <label class="block text-xs text-gray-500 mb-1">Tipo</label>
        <select name="tipo" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
            <option value="">Todos</option>
            <option value="persona_fisica" {{ request('tipo') === 'persona_fisica' ? 'selected' : '' }}>Persona física</option>
            <option value="persona_juridica" {{ request('tipo') === 'persona_juridica' ? 'selected' : '' }}>Persona jurídica</option>
        </select>
    </div>
    <div>
        <label class="block text-xs text-gray-500 mb-1">Estado</label>
        <select name="estado" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
            <option value="">Todos</option>
            <option value="1" {{ request('estado') === '1' ? 'selected' : '' }}>Activo</option>
            <option value="0" {{ request('estado') === '0' ? 'selected' : '' }}>Inactivo</option>
        </select>
    </div>
    <button type="submit" class="bg-brand-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-brand-700">
        <i class="fas fa-search"></i> Filtrar
    </button>
    <a href="{{ route('reportes.clientes') }}" class="text-gray-500 text-sm py-2 hover:text-gray-700">
        <i class="fas fa-times"></i> Limpiar
    </a>
    <a href="{{ route('reportes.clientes.pdf', request()->query()) }}"
       class="ml-auto bg-red-700 hover:bg-red-800 text-white px-4 py-2 rounded-lg text-sm flex items-center gap-2">
        <i class="fas fa-file-pdf"></i> Descargar PDF
    </a>
</form>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200 text-sm">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">Cliente</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">Tipo</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">C.I/NIT</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">Contacto</th>
                <th class="px-4 py-3 text-center font-semibold text-gray-600">Expedientes</th>
                <th class="px-4 py-3 text-center font-semibold text-gray-600">Trámites</th>
                <th class="px-4 py-3 text-center font-semibold text-gray-600">Estado</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse($clientes as $cliente)
            <tr class="hover:bg-gray-50 transition">
                <td class="px-4 py-3">
                    <a href="{{ route('clientes.show', $cliente) }}" class="font-medium text-brand-800 hover:underline">
                        {{ $cliente->nombre_completo }}
                    </a>
                </td>
                <td class="px-4 py-3 text-gray-600">
                    {{ $cliente->tipo === 'persona_juridica' ? 'Jurídica' : 'Física' }}
                </td>
                <td class="px-4 py-3 text-gray-600 font-mono text-xs">
                    {{ $cliente->dni }}
                </td>
                <td class="px-4 py-3 text-gray-600 text-xs">
                    {{ $cliente->email ?? '—' }}<br>{{ $cliente->telefono ?? '' }}
                </td>
                <td class="px-4 py-3 text-center text-gray-600">{{ $cliente->expedientes_count }}</td>
                <td class="px-4 py-3 text-center text-gray-600">{{ $cliente->tramites_count }}</td>
                <td class="px-4 py-3 text-center">
                    <span class="px-2 py-0.5 rounded text-xs font-medium
                        {{ $cliente->activo ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                        {{ $cliente->activo ? 'Activo' : 'Inactivo' }}
                    </span>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="px-4 py-10 text-center text-gray-400">
                    <i class="fas fa-users text-3xl mb-2"></i>
                    <p>No se encontraron clientes.</p>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
    </div>
    <div class="px-4 py-3 border-t">{{ $clientes->links() }}</div>
</div>
@endsection
