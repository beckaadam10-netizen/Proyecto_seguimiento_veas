@extends('layouts.app')

@section('title', 'Administración')
@section('header', 'Administración')

@section('content')

<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">

    <button type="button" onclick="toggleAdmin('usuarios')"
            class="text-left bg-white rounded-xl shadow-sm p-6 hover:shadow-md transition flex items-center gap-4 w-full">
        <div class="w-12 h-12 rounded-lg bg-brand-100 text-brand-800 flex items-center justify-center text-xl">
            <i class="fas fa-users"></i>
        </div>
        <div>
            <h3 class="font-semibold text-gray-800">Usuarios</h3>
            <p class="text-sm text-gray-500">{{ $usuarios->count() }} registrados</p>
        </div>
    </button>

    <button type="button" onclick="toggleAdmin('roles')"
            class="text-left bg-white rounded-xl shadow-sm p-6 hover:shadow-md transition flex items-center gap-4 w-full">
        <div class="w-12 h-12 rounded-lg bg-indigo-100 text-indigo-700 flex items-center justify-center text-xl">
            <i class="fas fa-user-shield"></i>
        </div>
        <div>
            <h3 class="font-semibold text-gray-800">Roles</h3>
            <p class="text-sm text-gray-500">{{ $roles->count() }} registrados</p>
        </div>
    </button>

    <button type="button" onclick="toggleAdmin('permisos')"
            class="text-left bg-white rounded-xl shadow-sm p-6 hover:shadow-md transition flex items-center gap-4 w-full">
        <div class="w-12 h-12 rounded-lg bg-amber-100 text-amber-700 flex items-center justify-center text-xl">
            <i class="fas fa-key"></i>
        </div>
        <div>
            <h3 class="font-semibold text-gray-800">Permisos</h3>
            <p class="text-sm text-gray-500">{{ $permisos->count() }} registrados</p>
        </div>
    </button>

</div>

<div id="panel-usuarios" class="hidden mb-6">
    <div class="flex items-center justify-between mb-3">
        <h3 class="font-semibold text-gray-700">Usuarios</h3>
        @if(auth()->user()->puede('administracion', 'crear'))
        <button type="button" onclick="abrirModal('modal-usuario-nuevo')"
           class="bg-brand-600 hover:bg-brand-700 text-white px-4 py-2 rounded-lg text-sm font-medium flex items-center gap-2">
            <i class="fas fa-plus"></i> Nuevo Usuario
        </button>
        @endif
    </div>

    <div class="bg-white rounded-xl shadow-sm mb-4 overflow-hidden">
        <button type="button" onclick="toggleBusquedaUsuarios()"
                class="w-full flex items-center justify-between px-4 py-3 text-sm font-medium text-gray-700 hover:bg-gray-50">
            <span><i class="fas fa-filter mr-2 text-gray-400"></i> Búsqueda avanzada</span>
            <i id="icono-busqueda-usuarios" class="fas fa-chevron-up text-gray-400"></i>
        </button>
        <form method="GET" action="{{ route('administracion.index') }}" id="form-busqueda-usuarios"
              class="px-4 pb-4 pt-1 flex flex-wrap gap-3 items-end border-t">
            <input type="hidden" name="abrir" value="usuarios">
            <div>
                <label class="block text-xs text-gray-500 mb-1">Nombre</label>
                <input type="text" name="nombre" value="{{ request('nombre') }}" placeholder="Buscar por nombre"
                       class="border border-gray-300 rounded-lg px-3 py-2 text-sm w-48">
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">Rol</label>
                <select name="role_id" class="border border-gray-300 rounded-lg px-3 py-2 text-sm w-40">
                    <option value="">Seleccionar rol</option>
                    @foreach($rolesFiltro as $rolFiltro)
                        <option value="{{ $rolFiltro->id }}" {{ request('role_id') == $rolFiltro->id ? 'selected' : '' }}>{{ $rolFiltro->nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">Estado</label>
                <select name="estado" class="border border-gray-300 rounded-lg px-3 py-2 text-sm w-32">
                    <option value="">Todos</option>
                    <option value="1" {{ request('estado') === '1' ? 'selected' : '' }}>Activo</option>
                    <option value="0" {{ request('estado') === '0' ? 'selected' : '' }}>Inactivo</option>
                </select>
            </div>
            <button type="submit" class="bg-brand-600 hover:bg-brand-700 text-white px-4 py-2 rounded-lg text-sm font-medium">
                <i class="fas fa-search mr-1"></i> Buscar
            </button>
            <a href="{{ route('administracion.index', ['abrir' => 'usuarios']) }}"
               class="text-gray-600 hover:text-gray-800 border border-gray-300 px-4 py-2 rounded-lg text-sm font-medium">
                <i class="fas fa-rotate-right mr-1"></i> Limpiar
            </a>
        </form>
    </div>

    @include('administracion._tabla-usuarios', ['usuarios' => $usuarios])
</div>

<div id="panel-roles" class="hidden mb-6">
    <div class="flex items-center justify-between mb-3">
        <h3 class="font-semibold text-gray-700">Roles</h3>
        @if(auth()->user()->puede('administracion', 'crear'))
        <button type="button" onclick="abrirModal('modal-rol-nuevo')"
           class="bg-brand-600 hover:bg-brand-700 text-white px-4 py-2 rounded-lg text-sm font-medium flex items-center gap-2">
            <i class="fas fa-plus"></i> Nuevo Rol
        </button>
        @endif
    </div>
    @include('administracion._tabla-roles', ['roles' => $roles])
</div>

<div id="panel-permisos" class="hidden mb-6">
    <div class="mb-3">
        <h3 class="font-semibold text-gray-700">Permisos del sistema</h3>
        <p class="text-xs text-gray-400 mt-0.5">
            Catálogo de solo lectura: estos permisos están definidos por el sistema, uno por módulo y acción.
            Para asignarlos a un rol, andá a la pestaña <button type="button" onclick="toggleAdmin('roles')" class="text-brand-700 hover:underline font-medium">Roles</button>.
        </p>
    </div>
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Módulo</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Acción</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Qué permite</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($modulosPermisos as $modulo => $grupo)
                    @foreach($grupo['permisos'] as $accion => $permiso)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-4 py-3 text-gray-700 whitespace-nowrap">
                            @if($loop->first)
                                <i class="fas {{ $grupo['icon'] }} text-gray-400 w-4 text-center mr-1.5"></i>{{ $grupo['label'] }}
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600">
                                {{ \App\Models\Permiso::ACCIONES[$accion] }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-gray-500">{{ $permiso->descripcion }}</td>
                    </tr>
                    @endforeach
                @empty
                <tr>
                    <td colspan="3" class="px-4 py-10 text-center text-gray-400">
                        <i class="fas fa-key text-3xl mb-2"></i>
                        <p>No hay permisos cargados.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>
</div>

{{-- Modal flotante: Nuevo Usuario --}}
<div id="modal-usuario-nuevo" class="hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="fixed inset-0 bg-black/50" onclick="cerrarModal('modal-usuario-nuevo')"></div>
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="relative bg-white rounded-xl shadow-lg w-full max-w-xl max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between px-6 py-4 border-b sticky top-0 bg-white rounded-t-xl">
                <h3 class="font-semibold text-gray-800 text-lg">Nuevo Usuario</h3>
                <button type="button" onclick="cerrarModal('modal-usuario-nuevo')" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>
            <form method="POST" action="{{ route('administracion.usuarios.store') }}" class="p-6 space-y-4">
                @csrf
                <input type="hidden" name="_modal" value="modal-usuario-nuevo">

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nombre *</label>
                    <input type="text" name="name" value="{{ old('name') }}" required
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                    @error('name')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
                    <input type="email" name="email" value="{{ old('email') }}" required
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                    @error('email')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Contraseña *</label>
                        <input type="password" name="password" required
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                        @error('password')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Confirmar contraseña *</label>
                        <input type="password" name="password_confirmation" required
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Rol</label>
                    <select name="role_id"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                        <option value="">— Sin rol —</option>
                        @foreach($roles as $rol)
                            <option value="{{ $rol->id }}" {{ old('role_id') == $rol->id ? 'selected' : '' }}>{{ $rol->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-center gap-2">
                    <input type="hidden" name="activo" value="0">
                    <input type="checkbox" name="activo" id="activo-nuevo" value="1" {{ old('activo', true) ? 'checked' : '' }}
                           class="rounded border-gray-300 text-brand-700 focus:ring-brand-400">
                    <label for="activo-nuevo" class="text-sm text-gray-700">Activo</label>
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="submit" class="bg-brand-600 hover:bg-brand-700 text-white px-6 py-2 rounded-lg font-medium">
                        <i class="fas fa-save mr-1"></i> Guardar
                    </button>
                    <button type="button" onclick="cerrarModal('modal-usuario-nuevo')"
                            class="text-gray-600 hover:text-gray-800 px-4 py-2 rounded-lg border hover:bg-gray-50">
                        Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modales flotantes: Editar Usuario --}}
@foreach($usuarios as $usuario)
<div id="modal-usuario-editar-{{ $usuario->id }}" class="hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="fixed inset-0 bg-black/50" onclick="cerrarModal('modal-usuario-editar-{{ $usuario->id }}')"></div>
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="relative bg-white rounded-xl shadow-lg w-full max-w-xl max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between px-6 py-4 border-b sticky top-0 bg-white rounded-t-xl">
                <h3 class="font-semibold text-gray-800 text-lg">Editar: {{ $usuario->name }}</h3>
                <button type="button" onclick="cerrarModal('modal-usuario-editar-{{ $usuario->id }}')" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>
            <form method="POST" action="{{ route('administracion.usuarios.update', $usuario) }}" class="p-6 space-y-4">
                @csrf @method('PUT')
                <input type="hidden" name="_modal" value="modal-usuario-editar-{{ $usuario->id }}">

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nombre *</label>
                    <input type="text" name="name" value="{{ $usuario->name }}" required
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
                    <input type="email" name="email" value="{{ $usuario->email }}" required
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nueva contraseña</label>
                        <input type="password" name="password"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                        <p class="text-xs text-gray-400 mt-1">Dejar en blanco para no cambiarla.</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Confirmar contraseña</label>
                        <input type="password" name="password_confirmation"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Rol</label>
                    <select name="role_id"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                        <option value="">— Sin rol —</option>
                        @foreach($roles as $rol)
                            <option value="{{ $rol->id }}" {{ $usuario->role_id == $rol->id ? 'selected' : '' }}>{{ $rol->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-center gap-2">
                    <input type="hidden" name="activo" value="0">
                    <input type="checkbox" name="activo" id="activo-editar-{{ $usuario->id }}" value="1" {{ $usuario->activo ? 'checked' : '' }}
                           class="rounded border-gray-300 text-brand-700 focus:ring-brand-400">
                    <label for="activo-editar-{{ $usuario->id }}" class="text-sm text-gray-700">Activo</label>
                    @if($usuario->id === auth()->id())
                        <span class="text-xs text-gray-400">(no podés desactivar tu propio usuario)</span>
                    @endif
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="submit" class="bg-brand-600 hover:bg-brand-700 text-white px-6 py-2 rounded-lg font-medium">
                        <i class="fas fa-save mr-1"></i> Guardar cambios
                    </button>
                    <button type="button" onclick="cerrarModal('modal-usuario-editar-{{ $usuario->id }}')"
                            class="text-gray-600 hover:text-gray-800 px-4 py-2 rounded-lg border hover:bg-gray-50">
                        Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach

{{-- Modal flotante: Nuevo Rol --}}
<div id="modal-rol-nuevo" class="hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="fixed inset-0 bg-black/50" onclick="cerrarModal('modal-rol-nuevo')"></div>
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="relative bg-white rounded-xl shadow-lg w-full max-w-4xl max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between px-6 py-4 border-b sticky top-0 bg-white rounded-t-xl">
                <h3 class="font-semibold text-gray-800 text-lg">Nuevo Rol</h3>
                <button type="button" onclick="cerrarModal('modal-rol-nuevo')" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>
            <form method="POST" action="{{ route('administracion.roles.store') }}" class="p-6 space-y-4">
                @csrf
                <input type="hidden" name="_modal" value="modal-rol-nuevo">

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
                        'idPrefix'        => 'nuevo',
                    ])
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="submit" class="bg-brand-600 hover:bg-brand-700 text-white px-6 py-2 rounded-lg font-medium">
                        <i class="fas fa-save mr-1"></i> Guardar
                    </button>
                    <button type="button" onclick="cerrarModal('modal-rol-nuevo')"
                            class="text-gray-600 hover:text-gray-800 px-4 py-2 rounded-lg border hover:bg-gray-50">
                        Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modales flotantes: Editar Rol --}}
@foreach($roles as $rol)
<div id="modal-rol-editar-{{ $rol->id }}" class="hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="fixed inset-0 bg-black/50" onclick="cerrarModal('modal-rol-editar-{{ $rol->id }}')"></div>
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="relative bg-white rounded-xl shadow-lg w-full max-w-4xl max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between px-6 py-4 border-b sticky top-0 bg-white rounded-t-xl">
                <h3 class="font-semibold text-gray-800 text-lg">Editar: {{ $rol->nombre }}</h3>
                <button type="button" onclick="cerrarModal('modal-rol-editar-{{ $rol->id }}')" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>
            <form method="POST" action="{{ route('administracion.roles.update', $rol) }}" class="p-6 space-y-4">
                @csrf @method('PUT')
                <input type="hidden" name="_modal" value="modal-rol-editar-{{ $rol->id }}">

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nombre *</label>
                    <input type="text" name="nombre" value="{{ $rol->nombre }}" required
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Descripción</label>
                    <textarea name="descripcion" rows="3"
                              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">{{ $rol->descripcion }}</textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Permisos</label>
                    @include('administracion.roles._matriz-permisos', [
                        'modulosPermisos' => $modulosPermisos,
                        'seleccionados'   => $rol->permisos->pluck('id')->all(),
                        'idPrefix'        => 'editar-' . $rol->id,
                    ])
                </div>
                <div class="flex items-center gap-2">
                    <input type="hidden" name="activo" value="0">
                    <input type="checkbox" name="activo" id="rol-activo-editar-{{ $rol->id }}" value="1" {{ $rol->activo ? 'checked' : '' }}
                           class="rounded border-gray-300 text-brand-700 focus:ring-brand-400">
                    <label for="rol-activo-editar-{{ $rol->id }}" class="text-sm text-gray-700">Activo</label>
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="submit" class="bg-brand-600 hover:bg-brand-700 text-white px-6 py-2 rounded-lg font-medium">
                        <i class="fas fa-save mr-1"></i> Guardar cambios
                    </button>
                    <button type="button" onclick="cerrarModal('modal-rol-editar-{{ $rol->id }}')"
                            class="text-gray-600 hover:text-gray-800 px-4 py-2 rounded-lg border hover:bg-gray-50">
                        Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach

@push('scripts')
<script>
    function toggleAdmin(key) {
        ['usuarios', 'roles', 'permisos'].forEach(function (k) {
            const panel = document.getElementById('panel-' + k);
            if (k === key) {
                panel.classList.toggle('hidden');
            } else {
                panel.classList.add('hidden');
            }
        });
    }

    function toggleBusquedaUsuarios() {
        const form = document.getElementById('form-busqueda-usuarios');
        const icono = document.getElementById('icono-busqueda-usuarios');
        form.classList.toggle('hidden');
        icono.classList.toggle('fa-chevron-up');
        icono.classList.toggle('fa-chevron-down');
    }

    document.addEventListener('DOMContentLoaded', function () {
        const abrir = @json(request('abrir'));
        if (abrir) {
            const panel = document.getElementById('panel-' + abrir);
            if (panel) {
                panel.classList.remove('hidden');
                panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }
    });

    @if(($errors->any() || session('error')) && old('_modal'))
        (function () {
            const modalId = @json(old('_modal'));
            if (modalId.startsWith('modal-usuario')) {
                toggleAdmin('usuarios');
            } else if (modalId.startsWith('modal-rol')) {
                toggleAdmin('roles');
            }
            abrirModal(modalId);
        })();
    @endif
</script>
@endpush

@endsection
