@extends('layouts.app')

@section('title', 'Clientes')
@section('header', 'Clientes')

@section('header-actions')
    @if(auth()->user()->puede('clientes', 'crear'))
    <button type="button" onclick="abrirModal('modal-cliente-nuevo')"
       class="bg-brand-600 hover:bg-brand-700 text-white px-4 py-2 rounded-lg text-sm font-medium flex items-center gap-2">
        <i class="fas fa-plus"></i> Nuevo Cliente
    </button>
    @endif
@endsection

@section('content')

{{-- Filtros --}}
<form method="GET" class="bg-white rounded-xl shadow-sm p-4 mb-6 flex flex-wrap gap-3 items-end">
    <div class="flex-1 min-w-48">
        <label class="block text-xs text-gray-500 mb-1">Buscar</label>
        <input type="text" name="buscar" value="{{ request('buscar') }}"
               placeholder="Nombre, C.I/NIT, email..."
               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-400">
    </div>
    <div>
        <label class="block text-xs text-gray-500 mb-1">Tipo</label>
        <select name="tipo" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
            <option value="">Todos</option>
            <option value="persona_fisica"   {{ request('tipo') === 'persona_fisica'   ? 'selected' : '' }}>Persona Física</option>
            <option value="persona_juridica" {{ request('tipo') === 'persona_juridica' ? 'selected' : '' }}>Persona Jurídica</option>
        </select>
    </div>
    <div>
        <label class="block text-xs text-gray-500 mb-1">Estado</label>
        <select name="activo" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
            <option value="">Todos</option>
            <option value="1" {{ request('activo') === '1' ? 'selected' : '' }}>Activos</option>
            <option value="0" {{ request('activo') === '0' ? 'selected' : '' }}>Inactivos</option>
        </select>
    </div>
    <button type="submit" class="bg-brand-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-brand-700">
        <i class="fas fa-search"></i> Filtrar
    </button>
    <a href="{{ route('clientes.index') }}" class="text-gray-500 hover:text-gray-700 text-sm py-2">
        <i class="fas fa-times"></i> Limpiar
    </a>
</form>

{{-- Tabla --}}
<div id="contenedor-tabla-clientes">
    @include('clientes._tabla-clientes')
</div>

{{-- Modal flotante: Nuevo Cliente --}}
<div id="modal-cliente-nuevo" class="hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="fixed inset-0 bg-black/50" onclick="cerrarModal('modal-cliente-nuevo')"></div>

    <div class="flex min-h-full items-center justify-center p-4">
        <div class="relative bg-white rounded-xl shadow-lg w-full max-w-2xl max-h-[90vh] overflow-y-auto">

            <div class="flex items-center justify-between px-6 py-4 border-b sticky top-0 bg-white rounded-t-xl">
                <h3 class="font-semibold text-gray-800 text-lg">Nuevo Cliente</h3>
                <button type="button" onclick="cerrarModal('modal-cliente-nuevo')" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>

            <form method="POST" action="{{ route('clientes.store') }}" class="p-6 space-y-6">
                @csrf
                <input type="hidden" name="_modal" value="modal-cliente-nuevo">

                <div class="space-y-4">
                    <h4 class="font-semibold text-gray-700 border-b pb-2">Datos personales</h4>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de cliente *</label>
                            <select name="tipo" id="tipo-nuevo" onchange="toggleTipoCliente('nuevo')"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                                <option value="persona_fisica"   {{ old('tipo','persona_fisica') === 'persona_fisica'   ? 'selected' : '' }}>Persona Física</option>
                                <option value="persona_juridica" {{ old('tipo') === 'persona_juridica' ? 'selected' : '' }}>Persona Jurídica</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">C.I/NIT *</label>
                            <input type="text" name="dni" value="{{ old('dni') }}" required
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                            @error('dni')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div id="campos-fisica-nuevo" class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nombre *</label>
                            <input type="text" name="nombre" value="{{ old('nombre') }}"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Apellido *</label>
                            <input type="text" name="apellido" value="{{ old('apellido') }}"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                        </div>
                    </div>

                    <div id="campos-juridica-nuevo" class="hidden">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Razón Social *</label>
                        <input type="text" name="razon_social" value="{{ old('razon_social') }}"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                    </div>
                </div>

                <div class="space-y-4">
                    <h4 class="font-semibold text-gray-700 border-b pb-2">Contacto</h4>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                            <input type="email" name="email" value="{{ old('email') }}"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Teléfono</label>
                            <input type="text" name="telefono" value="{{ old('telefono') }}"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Dirección</label>
                        <input type="text" name="direccion" value="{{ old('direccion') }}"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                    </div>
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="submit" class="bg-brand-600 hover:bg-brand-700 text-white px-6 py-2 rounded-lg font-medium">
                        <i class="fas fa-save mr-1"></i> Guardar Cliente
                    </button>
                    <button type="button" onclick="cerrarModal('modal-cliente-nuevo')"
                            class="text-gray-600 hover:text-gray-800 px-4 py-2 rounded-lg border hover:bg-gray-50">
                        Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>toggleTipoCliente('nuevo');</script>

@push('scripts')
<script>
    function toggleTipoCliente(sufijo) {
        const tipoEl = document.getElementById('tipo-' + sufijo);
        if (!tipoEl) return;
        document.getElementById('campos-fisica-' + sufijo).classList.toggle('hidden', tipoEl.value !== 'persona_fisica');
        document.getElementById('campos-juridica-' + sufijo).classList.toggle('hidden', tipoEl.value !== 'persona_juridica');
    }

    @if($errors->any() && old('_modal'))
        abrirModal(@json(old('_modal')));
    @endif

    @if(request()->filled('editar') && ctype_digit((string) request('editar')))
        abrirModal(@json('modal-cliente-editar-' . request('editar')));
    @endif

    iniciarBusquedaEnVivo({
        input: 'input[name="buscar"]',
        contenedor: 'contenedor-tabla-clientes',
        url: @json(route('clientes.index')),
    });
</script>
@endpush
@endsection
