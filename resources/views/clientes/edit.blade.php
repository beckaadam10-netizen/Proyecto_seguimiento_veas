@extends('layouts.app')

@section('title', 'Editar Cliente')
@section('header', 'Editar: ' . $cliente->nombre_completo)

@section('content')
<div class="max-w-2xl">
    <form method="POST" action="{{ route('clientes.update', $cliente) }}" class="space-y-6">
        @csrf @method('PUT')

        <div class="bg-white rounded-xl shadow-sm p-6 space-y-4">
            <h3 class="font-semibold text-gray-700 border-b pb-2">Datos personales</h3>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de cliente *</label>
                    <select name="tipo" id="tipo" onchange="toggleTipo()"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                        <option value="persona_fisica"   {{ $cliente->tipo === 'persona_fisica'   ? 'selected' : '' }}>Persona Física</option>
                        <option value="persona_juridica" {{ $cliente->tipo === 'persona_juridica' ? 'selected' : '' }}>Persona Jurídica</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">DNI / NIT *</label>
                    <input type="text" name="dni" value="{{ old('dni', $cliente->dni) }}" required
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                </div>
            </div>

            <div id="campos-fisica" class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nombre *</label>
                    <input type="text" name="nombre" value="{{ old('nombre', $cliente->nombre) }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Apellido *</label>
                    <input type="text" name="apellido" value="{{ old('apellido', $cliente->apellido) }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                </div>
            </div>

            <div id="campos-juridica" class="hidden">
                <label class="block text-sm font-medium text-gray-700 mb-1">Razón Social *</label>
                <input type="text" name="razon_social" value="{{ old('razon_social', $cliente->razon_social) }}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6 space-y-4">
            <h3 class="font-semibold text-gray-700 border-b pb-2">Contacto</h3>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" name="email" value="{{ old('email', $cliente->email) }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Teléfono</label>
                    <input type="text" name="telefono" value="{{ old('telefono', $cliente->telefono) }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Dirección</label>
                <input type="text" name="direccion" value="{{ old('direccion', $cliente->direccion) }}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
            </div>
            <div class="flex items-center gap-2">
                <input type="checkbox" name="activo" id="activo" value="1" {{ $cliente->activo ? 'checked' : '' }}
                       class="w-4 h-4 text-brand-700 rounded">
                <label for="activo" class="text-sm text-gray-700">Cliente activo</label>
            </div>
        </div>

        <div class="flex gap-3">
            <button type="submit" class="bg-brand-600 hover:bg-brand-700 text-white px-6 py-2 rounded-lg font-medium">
                <i class="fas fa-save mr-1"></i> Guardar cambios
            </button>
            <a href="{{ route('clientes.show', $cliente) }}" class="text-gray-600 hover:text-gray-800 px-4 py-2 rounded-lg border hover:bg-gray-50">
                Cancelar
            </a>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
function toggleTipo() {
    const tipo = document.getElementById('tipo').value;
    document.getElementById('campos-fisica').classList.toggle('hidden', tipo !== 'persona_fisica');
    document.getElementById('campos-juridica').classList.toggle('hidden', tipo !== 'persona_juridica');
}
toggleTipo();
</script>
@endpush
