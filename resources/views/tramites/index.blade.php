@extends('layouts.app')

@section('title', 'Trámites')
@section('header', 'Trámites')

@section('header-actions')
    @if(auth()->user()->puede('tramites', 'crear'))
    <button type="button" onclick="abrirModal('modal-tramite-nuevo')"
       class="bg-brand-600 hover:bg-brand-700 text-white px-4 py-2 rounded-lg text-sm font-medium flex items-center gap-2">
        <i class="fas fa-plus"></i> Nuevo Trámite
    </button>
    @endif
@endsection

@section('content')

<form method="GET" class="bg-white rounded-xl shadow-sm p-4 mb-6 flex flex-wrap gap-3 items-end">
    @unless(auth()->user()->esCliente())
    <div>
        <label class="block text-xs text-gray-500 mb-1">Cliente</label>
        <select name="cliente_id" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
            <option value="">Todos</option>
            @foreach($clientes as $cli)
                <option value="{{ $cli->id }}" {{ request('cliente_id') == $cli->id ? 'selected' : '' }}>{{ $cli->nombre_completo }}</option>
            @endforeach
        </select>
    </div>
    @endunless
    <div>
        <label class="block text-xs text-gray-500 mb-1">Tipo</label>
        <select name="tipo_tramite_id" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
            <option value="">Todos</option>
            @foreach($tiposTramite as $t)
                <option value="{{ $t->id }}" {{ request('tipo_tramite_id') == $t->id ? 'selected' : '' }}>{{ $t->nombre }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-xs text-gray-500 mb-1">Estado</label>
        <select name="estado" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
            <option value="">Todos</option>
            @foreach($estados as $e)
                <option value="{{ $e }}" {{ request('estado') === $e ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $e)) }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-xs text-gray-500 mb-1">Prioridad</label>
        <select name="prioridad" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
            <option value="">Todas</option>
            @foreach($prioridades as $p)
                <option value="{{ $p }}" {{ request('prioridad') === $p ? 'selected' : '' }}>{{ ucfirst($p) }}</option>
            @endforeach
        </select>
    </div>
    <button type="submit" class="bg-brand-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-brand-700">
        <i class="fas fa-search"></i> Filtrar
    </button>
    <a href="{{ route('tramites.index') }}" class="text-gray-500 text-sm py-2 hover:text-gray-700">
        <i class="fas fa-times"></i> Limpiar
    </a>
</form>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200 text-sm">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">Código</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">Trámite</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">Cliente</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">Institución</th>
                <th class="px-4 py-3 text-center font-semibold text-gray-600">Estado</th>
                <th class="px-4 py-3 text-center font-semibold text-gray-600">Prioridad</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">Inicio</th>
                <th class="px-4 py-3 text-right font-semibold text-gray-600">Acciones</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse($tramites as $tra)
            <tr class="hover:bg-gray-50 transition">
                <td class="px-4 py-3 text-xs font-mono text-gray-600">{{ $tra->codigo }}</td>
                <td class="px-4 py-3">
                    <a href="{{ route('tramites.show', $tra) }}" class="font-medium text-gray-800 hover:text-brand-700">{{ $tra->nombre }}</a>
                    <p class="text-xs text-gray-500">{{ $tra->tipoTramite?->nombre ?? '—' }}</p>
                </td>
                <td class="px-4 py-3 text-gray-600">{{ $tra->cliente->nombre_completo }}</td>
                <td class="px-4 py-3 text-gray-600">{{ $tra->institucionPublica?->nombre ?? '—' }}</td>
                <td class="px-4 py-3 text-center">
                    <span class="px-2 py-0.5 rounded text-xs font-medium bg-{{ $tra->estado_color }}-100 text-{{ $tra->estado_color }}-700">
                        {{ ucfirst(str_replace('_', ' ', $tra->estado)) }}
                    </span>
                </td>
                <td class="px-4 py-3 text-center">
                    <span class="px-2 py-0.5 rounded text-xs font-medium bg-{{ $tra->prioridad_color }}-100 text-{{ $tra->prioridad_color }}-700">
                        {{ ucfirst($tra->prioridad) }}
                    </span>
                </td>
                <td class="px-4 py-3 text-gray-600 text-xs">{{ $tra->fecha_inicio->format('d/m/Y') }}</td>
                <td class="px-4 py-3 text-right">
                    <a href="{{ route('tramites.show', $tra) }}" class="text-gray-400 hover:text-brand-700 mr-2" title="Ver">
                        <i class="fas fa-eye"></i>
                    </a>
                    @if(auth()->user()->puede('tramites', 'modificar'))
                    <button type="button" onclick="abrirModal('modal-tramite-editar-{{ $tra->id }}')" class="text-gray-400 hover:text-brand-700 mr-2" title="Editar">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button type="button" onclick="abrirModal('modal-tramite-estado-{{ $tra->id }}')" class="text-gray-400 hover:text-indigo-600 mr-2" title="Cambiar estado">
                        <i class="fas fa-right-left"></i>
                    </button>
                    @endif
                    @if(auth()->user()->puede('tramites', 'eliminar'))
                    <form method="POST" action="{{ route('tramites.destroy', $tra) }}" class="inline"
                          onsubmit="return confirm('¿Eliminar este trámite?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-gray-400 hover:text-red-600">
                            <i class="fas fa-trash"></i>
                        </button>
                    </form>
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="8" class="px-4 py-10 text-center text-gray-400">
                    <i class="fas fa-file-circle-check text-3xl mb-2"></i>
                    <p>No se encontraron trámites.</p>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
    </div>
    <div class="px-4 py-3 border-t">{{ $tramites->links() }}</div>
</div>

{{-- Modal flotante: Nuevo Trámite --}}
<div id="modal-tramite-nuevo" class="hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="fixed inset-0 bg-black/50" onclick="cerrarModal('modal-tramite-nuevo')"></div>
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="relative bg-white rounded-xl shadow-lg w-full max-w-2xl max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between px-6 py-4 border-b sticky top-0 bg-white rounded-t-xl">
                <h3 class="font-semibold text-gray-800 text-lg">Nuevo Trámite</h3>
                <button type="button" onclick="cerrarModal('modal-tramite-nuevo')" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>
            <form method="POST" action="{{ route('tramites.store') }}" class="p-6 space-y-4">
                @csrf
                <input type="hidden" name="_modal" value="modal-tramite-nuevo">
                <p class="text-xs text-gray-400">El código se genera automáticamente al guardar.</p>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nombre del trámite *</label>
                    <input type="text" name="nombre" value="{{ old('nombre') }}" required
                           placeholder="Ej: Inscripción de escritura"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-sm font-medium text-gray-700">Tipo de trámite *</label>
                            <button type="button" onclick="abrirModalRapido('tipo-tramite', 'select-tipo-tramite-nuevo')" class="text-xs text-brand-600 hover:underline">+ Nuevo</button>
                        </div>
                        <select name="tipo_tramite_id" id="select-tipo-tramite-nuevo" required
                                class="select-tipo-tramite w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                            <option value="">— Seleccioná un tipo —</option>
                            @foreach($tiposTramite as $t)
                                <option value="{{ $t->id }}" {{ old('tipo_tramite_id') == $t->id ? 'selected' : '' }}>{{ $t->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Cliente *</label>
                        <div class="flex gap-2">
                            <select name="cliente_id" required id="select-cliente-tramite-nuevo"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                                <option value="">— Seleccioná un cliente —</option>
                                @foreach($clientes as $cli)
                                    <option value="{{ $cli->id }}" {{ old('cliente_id') == $cli->id ? 'selected' : '' }}>{{ $cli->nombre_completo }}</option>
                                @endforeach
                            </select>
                            @if(auth()->user()->puede('clientes', 'crear'))
                            <button type="button" onclick="abrirClienteRapido('select-cliente-tramite-nuevo')" title="Nuevo cliente"
                                    class="shrink-0 border border-gray-300 rounded-lg w-9 h-9 flex items-center justify-center text-gray-500 hover:text-brand-700 hover:border-brand-400">
                                <i class="fas fa-plus"></i>
                            </button>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Responsable</label>
                        <select name="responsable_id"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                            <option value="">— Sin asignar —</option>
                            @foreach($responsables as $r)
                                <option value="{{ $r->id }}" {{ old('responsable_id') == $r->id ? 'selected' : '' }}>{{ $r->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-sm font-medium text-gray-700">Institución pública</label>
                            <button type="button" onclick="abrirModalRapido('institucion', 'select-institucion-nuevo')" class="text-xs text-brand-600 hover:underline">+ Nueva</button>
                        </div>
                        <select name="institucion_publica_id" id="select-institucion-nuevo"
                                class="select-institucion-publica w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                            <option value="">— Sin especificar —</option>
                            @foreach($institucionesPublicas as $ip)
                                <option value="{{ $ip->id }}" {{ old('institucion_publica_id') == $ip->id ? 'selected' : '' }}>{{ $ip->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Estado *</label>
                        <select name="estado" required
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                            @foreach($estados as $e)
                                <option value="{{ $e }}" {{ old('estado', 'iniciado') === $e ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $e)) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Prioridad *</label>
                        <select name="prioridad" required
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                            @foreach($prioridades as $p)
                                <option value="{{ $p }}" {{ old('prioridad', 'media') === $p ? 'selected' : '' }}>{{ ucfirst($p) }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Fecha de inicio *</label>
                        <input type="date" name="fecha_inicio" value="{{ old('fecha_inicio', date('Y-m-d')) }}" required
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Fecha de finalización aproximada</label>
                        <input type="date" name="fecha_fin_aproximada" value="{{ old('fecha_fin_aproximada') }}"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Observaciones</label>
                    <textarea name="observaciones" rows="3"
                              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">{{ old('observaciones') }}</textarea>
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="submit" class="bg-brand-600 hover:bg-brand-700 text-white px-6 py-2 rounded-lg font-medium">
                        <i class="fas fa-save mr-1"></i> Registrar Trámite
                    </button>
                    <button type="button" onclick="cerrarModal('modal-tramite-nuevo')"
                            class="text-gray-600 hover:text-gray-800 px-4 py-2 rounded-lg border hover:bg-gray-50">
                        Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modales flotantes: Editar Trámite --}}
@foreach($tramites as $tra)
<div id="modal-tramite-editar-{{ $tra->id }}" class="hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="fixed inset-0 bg-black/50" onclick="cerrarModal('modal-tramite-editar-{{ $tra->id }}')"></div>
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="relative bg-white rounded-xl shadow-lg w-full max-w-2xl max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between px-6 py-4 border-b sticky top-0 bg-white rounded-t-xl">
                <h3 class="font-semibold text-gray-800 text-lg">Editar: {{ $tra->codigo }}</h3>
                <button type="button" onclick="cerrarModal('modal-tramite-editar-{{ $tra->id }}')" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>
            <form method="POST" action="{{ route('tramites.update', $tra) }}" class="p-6 space-y-4">
                @csrf @method('PUT')
                <input type="hidden" name="_modal" value="modal-tramite-editar-{{ $tra->id }}">

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nombre del trámite *</label>
                    <input type="text" name="nombre" value="{{ $tra->nombre }}" required
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-sm font-medium text-gray-700">Tipo de trámite *</label>
                            <button type="button" onclick="abrirModalRapido('tipo-tramite', 'select-tipo-tramite-editar-{{ $tra->id }}')" class="text-xs text-brand-600 hover:underline">+ Nuevo</button>
                        </div>
                        <select name="tipo_tramite_id" id="select-tipo-tramite-editar-{{ $tra->id }}" required
                                class="select-tipo-tramite w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                            <option value="">— Seleccioná un tipo —</option>
                            @foreach($tiposTramite as $t)
                                <option value="{{ $t->id }}" {{ $tra->tipo_tramite_id == $t->id ? 'selected' : '' }}>{{ $t->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Cliente *</label>
                        <div class="flex gap-2">
                            <select name="cliente_id" required id="select-cliente-tramite-editar-{{ $tra->id }}"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                                <option value="">— Seleccioná un cliente —</option>
                                @foreach($clientes as $cli)
                                    <option value="{{ $cli->id }}" {{ $tra->cliente_id == $cli->id ? 'selected' : '' }}>{{ $cli->nombre_completo }}</option>
                                @endforeach
                            </select>
                            @if(auth()->user()->puede('clientes', 'crear'))
                            <button type="button" onclick="abrirClienteRapido('select-cliente-tramite-editar-{{ $tra->id }}')" title="Nuevo cliente"
                                    class="shrink-0 border border-gray-300 rounded-lg w-9 h-9 flex items-center justify-center text-gray-500 hover:text-brand-700 hover:border-brand-400">
                                <i class="fas fa-plus"></i>
                            </button>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Responsable</label>
                        <select name="responsable_id"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                            <option value="">— Sin asignar —</option>
                            @foreach($responsables as $r)
                                <option value="{{ $r->id }}" {{ $tra->responsable_id == $r->id ? 'selected' : '' }}>{{ $r->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-sm font-medium text-gray-700">Institución pública</label>
                            <button type="button" onclick="abrirModalRapido('institucion', 'select-institucion-editar-{{ $tra->id }}')" class="text-xs text-brand-600 hover:underline">+ Nueva</button>
                        </div>
                        <select name="institucion_publica_id" id="select-institucion-editar-{{ $tra->id }}"
                                class="select-institucion-publica w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                            <option value="">— Sin especificar —</option>
                            @foreach($institucionesPublicas as $ip)
                                <option value="{{ $ip->id }}" {{ $tra->institucion_publica_id == $ip->id ? 'selected' : '' }}>{{ $ip->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Estado *</label>
                        <select name="estado" required
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                            @foreach($estados as $e)
                                <option value="{{ $e }}" {{ $tra->estado === $e ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $e)) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Prioridad *</label>
                        <select name="prioridad" required
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                            @foreach($prioridades as $p)
                                <option value="{{ $p }}" {{ $tra->prioridad === $p ? 'selected' : '' }}>{{ ucfirst($p) }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Fecha de inicio *</label>
                        <input type="date" name="fecha_inicio" value="{{ $tra->fecha_inicio->format('Y-m-d') }}" required
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Fecha de finalización aproximada</label>
                        <input type="date" name="fecha_fin_aproximada" value="{{ $tra->fecha_fin_aproximada?->format('Y-m-d') }}"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Observaciones</label>
                    <textarea name="observaciones" rows="3"
                              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">{{ $tra->observaciones }}</textarea>
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="submit" class="bg-brand-600 hover:bg-brand-700 text-white px-6 py-2 rounded-lg font-medium">
                        <i class="fas fa-save mr-1"></i> Guardar cambios
                    </button>
                    <button type="button" onclick="cerrarModal('modal-tramite-editar-{{ $tra->id }}')"
                            class="text-gray-600 hover:text-gray-800 px-4 py-2 rounded-lg border hover:bg-gray-50">
                        Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach

{{-- Modales flotantes: Cambiar estado --}}
@foreach($tramites as $tra)
<div id="modal-tramite-estado-{{ $tra->id }}" class="hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="fixed inset-0 bg-black/50" onclick="cerrarModal('modal-tramite-estado-{{ $tra->id }}')"></div>
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="relative bg-white rounded-xl shadow-lg w-full max-w-lg max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between px-6 py-4 border-b sticky top-0 bg-white rounded-t-xl">
                <h3 class="font-semibold text-gray-800 text-lg">
                    Cambiar estado <span class="text-xs font-normal text-gray-400 font-mono">{{ $tra->codigo }}</span>
                </h3>
                <button type="button" onclick="cerrarModal('modal-tramite-estado-{{ $tra->id }}')" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>
            <form method="POST" action="{{ route('tramites.cambiar-estado', $tra) }}" class="p-6 space-y-4"
                  id="form-tramite-estado-{{ $tra->id }}"
                  data-cliente-nombre="{{ $tra->cliente->nombre_completo }}"
                  data-cliente-whatsapp="{{ $tra->cliente->telefono_whatsapp }}"
                  data-tramite-codigo="{{ $tra->codigo }}"
                  data-tramite-nombre="{{ $tra->nombre }}"
                  data-estado-actual="{{ $tra->estado_label }}">
                @csrf @method('PATCH')
                <input type="hidden" name="_modal" value="modal-tramite-estado-{{ $tra->id }}">

                <p class="text-sm text-gray-600 bg-gray-50 rounded-lg p-3">
                    Estado actual:
                    <span class="px-2 py-0.5 rounded text-xs font-medium bg-{{ $tra->estado_color }}-100 text-{{ $tra->estado_color }}-700">
                        {{ $tra->estado_label }}
                    </span>
                </p>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nuevo estado *</label>
                    <select name="estado" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                        @foreach(\App\Models\Tramite::estadosLabels() as $valor => $label)
                            @continue($valor === $tra->estado)
                            <option value="{{ $valor }}" {{ $tra->estado_siguiente === $valor ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Descripción</label>
                    <textarea name="descripcion" rows="3" placeholder="Detalle del cambio (opcional)"
                              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400"></textarea>
                </div>

                <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                    <input type="checkbox" name="notificar_cliente" value="1">
                    <i class="fab fa-whatsapp text-emerald-600"></i>
                    Notificar al cliente por WhatsApp
                    @if(!$tra->cliente->telefono)
                        <span class="text-xs text-gray-400">(sin teléfono registrado)</span>
                    @endif
                </label>

                <div class="flex gap-3 pt-2">
                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2 rounded-lg font-medium">
                        <i class="fas fa-right-left mr-1"></i> Cambiar estado
                    </button>
                    <button type="button" onclick="cerrarModal('modal-tramite-estado-{{ $tra->id }}')"
                            class="text-gray-600 hover:text-gray-800 px-4 py-2 rounded-lg border hover:bg-gray-50">
                        Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach

{{-- Modal flotante: Registro rápido de Tipo de trámite --}}
<div id="modal-quick-tipo-tramite" class="hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="fixed inset-0 bg-black/50" onclick="cerrarModal('modal-quick-tipo-tramite')"></div>
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="relative bg-white rounded-xl shadow-lg w-full max-w-md">
            <div class="flex items-center justify-between px-6 py-4 border-b">
                <h3 class="font-semibold text-gray-800 text-lg">Nuevo tipo de trámite</h3>
                <button type="button" onclick="cerrarModal('modal-quick-tipo-tramite')" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>
            <form id="form-quick-tipo-tramite" class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nombre *</label>
                    <input type="text" name="nombre" required
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                    <p class="quick-error text-xs text-red-600 mt-1 hidden"></p>
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="submit" class="bg-brand-600 hover:bg-brand-700 text-white px-6 py-2 rounded-lg font-medium">
                        <i class="fas fa-save mr-1"></i> Guardar
                    </button>
                    <button type="button" onclick="cerrarModal('modal-quick-tipo-tramite')"
                            class="text-gray-600 hover:text-gray-800 px-4 py-2 rounded-lg border hover:bg-gray-50">
                        Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal flotante: Registro rápido de Institución pública --}}
<div id="modal-quick-institucion" class="hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="fixed inset-0 bg-black/50" onclick="cerrarModal('modal-quick-institucion')"></div>
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="relative bg-white rounded-xl shadow-lg w-full max-w-md">
            <div class="flex items-center justify-between px-6 py-4 border-b">
                <h3 class="font-semibold text-gray-800 text-lg">Nueva institución pública</h3>
                <button type="button" onclick="cerrarModal('modal-quick-institucion')" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>
            <form id="form-quick-institucion" class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nombre *</label>
                    <input type="text" name="nombre" required
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                    <p class="quick-error text-xs text-red-600 mt-1 hidden"></p>
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="submit" class="bg-brand-600 hover:bg-brand-700 text-white px-6 py-2 rounded-lg font-medium">
                        <i class="fas fa-save mr-1"></i> Guardar
                    </button>
                    <button type="button" onclick="cerrarModal('modal-quick-institucion')"
                            class="text-gray-600 hover:text-gray-800 px-4 py-2 rounded-lg border hover:bg-gray-50">
                        Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    let selectObjetivoRapido = null;

    function abrirModalRapido(tipo, selectId) {
        selectObjetivoRapido = selectId;
        const modalId = tipo === 'tipo-tramite' ? 'modal-quick-tipo-tramite' : 'modal-quick-institucion';
        const form = document.getElementById('form-quick-' + tipo);
        form.reset();
        form.querySelector('.quick-error').classList.add('hidden');
        abrirModal(modalId);
    }

    function registrarRapido(tipo, url, claseSelect) {
        const modalId = tipo === 'tipo-tramite' ? 'modal-quick-tipo-tramite' : 'modal-quick-institucion';
        const form = document.getElementById('form-quick-' + tipo);
        const errorEl = form.querySelector('.quick-error');
        const nombre = form.querySelector('input[name=nombre]').value.trim();

        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
            },
            body: JSON.stringify({ nombre: nombre }),
        })
        .then(async function (resp) {
            const data = await resp.json();

            if (!resp.ok) {
                const mensaje = (data.errors && data.errors.nombre && data.errors.nombre[0]) || data.message || 'No se pudo guardar.';
                errorEl.textContent = mensaje;
                errorEl.classList.remove('hidden');
                return;
            }

            document.querySelectorAll('.' + claseSelect).forEach(function (select) {
                const opcion = document.createElement('option');
                opcion.value = data.id;
                opcion.textContent = data.nombre;
                select.appendChild(opcion);
            });

            if (selectObjetivoRapido) {
                const destino = document.getElementById(selectObjetivoRapido);
                if (destino) destino.value = data.id;
            }

            cerrarModal(modalId);
        })
        .catch(function () {
            errorEl.textContent = 'Error de conexión. Intentá de nuevo.';
            errorEl.classList.remove('hidden');
        });
    }

    document.getElementById('form-quick-tipo-tramite').addEventListener('submit', function (e) {
        e.preventDefault();
        registrarRapido('tipo-tramite', '{{ route('parametros.tipos-tramite.store') }}', 'select-tipo-tramite');
    });

    document.getElementById('form-quick-institucion').addEventListener('submit', function (e) {
        e.preventDefault();
        registrarRapido('institucion', '{{ route('parametros.instituciones-publicas.store') }}', 'select-institucion-publica');
    });

    @if($errors->any() && old('_modal'))
        abrirModal(@json(old('_modal')));
    @endif

    @if(request()->filled('nuevo'))
        abrirModal('modal-tramite-nuevo');
    @endif

    @if(request()->filled('editar') && ctype_digit((string) request('editar')))
        abrirModal(@json('modal-tramite-editar-' . request('editar')));
    @endif

    document.querySelectorAll('form[id^="form-tramite-estado-"]').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            const checkbox = form.querySelector('input[name=notificar_cliente]');
            if (!checkbox || !checkbox.checked || form.dataset.whatsappConfirmado === '1' || !form.dataset.clienteWhatsapp) {
                return;
            }

            e.preventDefault();

            const select = form.querySelector('select[name=estado]');
            const nuevoLabel = select.options[select.selectedIndex].text;
            const descripcion = form.querySelector('textarea[name=descripcion]').value.trim();

            let texto = `Hola ${form.dataset.clienteNombre}, tu trámite ${form.dataset.tramiteCodigo} (${form.dataset.tramiteNombre}) cambió de estado: ${form.dataset.estadoActual} → ${nuevoLabel}.`;
            if (descripcion) {
                texto += `\n\n${descripcion}`;
            }

            mostrarPreviaWhatsapp(form, texto, form.dataset.clienteWhatsapp);
        });
    });
</script>
@endpush
@endsection
