@extends('layouts.app')

@section('title', 'Seguimientos')
@section('header', 'Seguimientos / Actuaciones')

@section('header-actions')
    @if(auth()->user()->puede('seguimientos', 'crear'))
    <button type="button" onclick="abrirModal('modal-seguimiento-nuevo')"
       class="bg-brand-600 hover:bg-brand-700 text-white px-4 py-2 rounded-lg text-sm font-medium flex items-center gap-2">
        <i class="fas fa-plus"></i> Nueva Acuación
    </button>
    @endif
@endsection

@section('content')

<form method="GET" id="form-filtros-seguimientos" class="bg-white rounded-xl shadow-sm p-4 mb-6 flex flex-wrap gap-3 items-end">
    <div class="flex-1 min-w-56">
        <label class="block text-xs text-gray-500 mb-1">Buscar expediente (NUREJ o carátula)</label>
        <input type="text" name="buscar" value="{{ request('buscar') }}" oninput="buscarExpedientesEnVivo()"
               {{ request('buscar') ? 'autofocus' : '' }}
               placeholder="Ej: 120/25 o 70595894"
               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
    </div>
    <div>
        <label class="block text-xs text-gray-500 mb-1">Tipo</label>
        <select name="tipo_actuacion_id" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
            <option value="">Todos</option>
            @foreach($tiposActuacion as $t)
                <option value="{{ $t->id }}" {{ request('tipo_actuacion_id') == $t->id ? 'selected' : '' }}>{{ $t->nombre }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-xs text-gray-500 mb-1">Pasante</label>
        <select name="pasante_id" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
            <option value="">Todos</option>
            @foreach($pasantes as $p)
                <option value="{{ $p->id }}" {{ request('pasante_id') == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-xs text-gray-500 mb-1">Orden (Expedientes)</label>
        <select name="orden" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
            <option value="recientes" {{ $ordenExpedientes === 'recientes' ? 'selected' : '' }}>Más recientes primero</option>
            <option value="antiguos" {{ $ordenExpedientes === 'antiguos' ? 'selected' : '' }}>Más antiguos primero</option>
        </select>
    </div>
    <button type="submit" class="bg-brand-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-brand-700">
        <i class="fas fa-search"></i> Filtrar
    </button>
    <a href="{{ route('seguimientos.index') }}" class="text-gray-500 text-sm py-2 hover:text-gray-700">
        <i class="fas fa-times"></i> Limpiar
    </a>
</form>

<div id="contenedor-tabla-expedientes">
    @include('seguimientos._tabla-expedientes')
</div>

<div>
    <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-2">Actuaciones de trámites</h2>
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 w-4"></th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Actuación</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Trámite</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Registrado por</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Fecha</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Vencimiento</th>
                    <th class="px-4 py-3 text-center font-semibold text-gray-600">Estado</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-600">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($seguimientos as $seg)
                <tr class="hover:bg-gray-50 transition {{ $seg->estaVencido() ? 'bg-red-50' : '' }}">
                    <td class="px-4 py-3">
                        <div class="w-2.5 h-2.5 rounded-full
                            {{ $seg->prioridad === 'urgente' ? 'bg-red-500' : ($seg->prioridad === 'alta' ? 'bg-orange-400' : ($seg->prioridad === 'media' ? 'bg-yellow-400' : 'bg-green-400')) }}">
                        </div>
                    </td>
                    <td class="px-4 py-3">
                        <p class="font-medium text-gray-800">{{ $seg->titulo }}</p>
                        <p class="text-xs text-gray-500">{{ $seg->tipoActuacion?->nombre ?? '—' }}</p>
                    </td>
                    <td class="px-4 py-3">
                        <a href="{{ route('tramites.show', $seg->tramite) }}" class="text-cyan-700 hover:underline text-xs font-mono">
                            {{ $seg->tramite->codigo }}
                        </a>
                        <p class="text-xs text-gray-500">{{ $seg->tramite->cliente->nombre_completo }}</p>
                    </td>
                    <td class="px-4 py-3">
                        <p class="text-sm text-gray-700">{{ $seg->usuario?->name ?? '—' }}</p>
                        @if($seg->usuario?->rol)
                            <span class="inline-block px-1.5 py-0.5 rounded text-[10px] font-medium bg-indigo-100 text-indigo-700">
                                {{ $seg->usuario->rol->nombre }}
                            </span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-gray-600 text-sm">{{ $seg->fecha_actuacion->format('d/m/Y') }}</td>
                    <td class="px-4 py-3 text-sm">
                        @if($seg->fecha_vencimiento)
                            <span class="{{ $seg->estaVencido() ? 'text-red-600 font-medium' : 'text-gray-600' }}">
                                {{ $seg->fecha_vencimiento->format('d/m/Y') }}
                                @if($seg->estaVencido()) <i class="fas fa-exclamation-circle ml-1"></i> @endif
                            </span>
                        @else
                            <span class="text-gray-400">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-center">
                        @if(!$seg->requiere_respuesta)
                            <span class="px-2 py-0.5 bg-gray-100 text-gray-600 rounded text-xs">Sin respuesta requerida</span>
                        @elseif($seg->respondido)
                            <span class="px-2 py-0.5 bg-green-100 text-green-700 rounded text-xs">Respondido</span>
                        @elseif($seg->estaVencido())
                            <span class="px-2 py-0.5 bg-red-100 text-red-700 rounded text-xs font-medium">Vencido</span>
                        @else
                            <span class="px-2 py-0.5 bg-orange-100 text-orange-700 rounded text-xs">Pendiente</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-right flex items-center justify-end gap-2">
                        @if(auth()->user()->puede('seguimientos', 'modificar'))
                            @if($seg->requiere_respuesta && !$seg->respondido)
                                <form method="POST" action="{{ route('seguimientos.responder', $seg) }}">
                                    @csrf @method('PATCH')
                                    <button class="text-xs text-green-600 hover:text-green-800 font-medium border border-green-300 rounded px-2 py-0.5">
                                        <i class="fas fa-check mr-1"></i>Responder
                                    </button>
                                </form>
                            @endif
                        @endif
                        <a href="{{ route('seguimientos.show', $seg) }}" class="text-gray-400 hover:text-brand-700" title="Ver">
                            <i class="fas fa-eye"></i>
                        </a>
                        @if(auth()->user()->puede('seguimientos', 'modificar'))
                        <button type="button" onclick="abrirModal('modal-seguimiento-editar-{{ $seg->id }}')" class="text-gray-400 hover:text-brand-700" title="Editar">
                            <i class="fas fa-edit"></i>
                        </button>
                        @endif
                        @if(auth()->user()->puede('seguimientos', 'eliminar'))
                        <form method="POST" action="{{ route('seguimientos.destroy', $seg) }}"
                              onsubmit="return confirm('¿Eliminar esta actuación?')">
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
                        <i class="fas fa-list-check text-3xl mb-2"></i>
                        <p>No se encontraron actuaciones de trámites.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        </div>
        <div class="px-4 py-3 border-t">{{ $seguimientos->links() }}</div>
    </div>
</div>

{{-- Modal flotante: Nueva Actuación --}}
<div id="modal-seguimiento-nuevo" class="hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="fixed inset-0 bg-black/50" onclick="cerrarModal('modal-seguimiento-nuevo')"></div>
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="relative bg-white rounded-xl shadow-lg w-full max-w-2xl max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between px-6 py-4 border-b sticky top-0 bg-white rounded-t-xl">
                <h3 class="font-semibold text-gray-800 text-lg">Nueva Actuación</h3>
                <button type="button" onclick="cerrarModal('modal-seguimiento-nuevo')" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>
            <form method="POST" action="{{ route('seguimientos.store') }}" enctype="multipart/form-data" class="p-6 space-y-4"
                  id="form-seguimiento-nuevo">
                @csrf
                <input type="hidden" name="_modal" value="modal-seguimiento-nuevo">

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Vincular a *</label>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <select name="expediente_id" id="select-expediente-nuevo" onchange="actualizarCamposGasto('nuevo'); actualizarDemandados('nuevo')"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                                <option value="">— Ningún expediente —</option>
                                @foreach($expedientes as $exp)
                                    <option value="{{ $exp->id }}" {{ old('expediente_id', request('expediente_id')) == $exp->id ? 'selected' : '' }}
                                            data-cliente-nombre="{{ $exp->cliente->nombre_completo }}"
                                            data-cliente-whatsapp="{{ $exp->cliente->telefono_whatsapp }}"
                                            data-demandados="{{ $exp->demandados->pluck('nombre')->implode(', ') }}">
                                        {{ $exp->numero }} — {{ $exp->cliente->nombre_completo }} | {{ Str::limit($exp->caratula, 40) }} ({{ $exp->abogado->nombre ?? 'sin abogado' }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <select name="tramite_id" id="select-tramite-nuevo" onchange="actualizarCamposGasto('nuevo'); actualizarDemandados('nuevo')"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                                <option value="">— Ningún trámite —</option>
                                @foreach($tramites as $tra)
                                    <option value="{{ $tra->id }}" {{ old('tramite_id', request('tramite_id')) == $tra->id ? 'selected' : '' }}
                                            data-cliente-nombre="{{ $tra->cliente->nombre_completo }}"
                                            data-cliente-whatsapp="{{ $tra->cliente->telefono_whatsapp }}">
                                        {{ $tra->codigo }} — {{ $tra->nombre }} | {{ $tra->cliente->nombre_completo }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <p class="text-xs text-gray-400 mt-1">Elegí un expediente o un trámite (no ambos).</p>
                    @error('expediente_id')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>

                <div id="demandados-wrap-nuevo" class="hidden">
                    <div class="flex flex-wrap items-center gap-1.5">
                        <span class="text-xs font-medium text-gray-500">Demandados:</span>
                        <div id="demandados-nuevo" class="flex flex-wrap gap-1.5"></div>
                    </div>
                </div>

                <input type="hidden" name="prioridad" value="media">

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de Acción *</label>
                    <div class="flex gap-2">
                        <select name="tipo_actuacion_id" required id="select-tipo-actuacion-nuevo"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                            <option value="">— Seleccioná un tipo —</option>
                            @foreach($tiposActuacion as $t)
                                <option value="{{ $t->id }}" {{ old('tipo_actuacion_id') == $t->id ? 'selected' : '' }}>{{ $t->nombre }}</option>
                            @endforeach
                        </select>
                        @if(auth()->user()->puede('parametros', 'crear'))
                        <button type="button" title="Nuevo tipo de actuación"
                                onclick="abrirAltaRapida({titulo: 'Nuevo tipo de actuación', etiqueta: 'Nombre', placeholder: 'Ej: Presentación de escrito', url: '{{ route('parametros.tipos-actuacion.store') }}', selectId: 'select-tipo-actuacion-nuevo'})"
                                class="shrink-0 border border-gray-300 rounded-lg w-9 h-9 flex items-center justify-center text-gray-500 hover:text-brand-700 hover:border-brand-400">
                            <i class="fas fa-plus"></i>
                        </button>
                        @endif
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Fecha de actuación</label>
                        @if(auth()->user()->puede('seguimientos', 'modificar_fecha'))
                        <input type="date" name="fecha_actuacion" value="{{ old('fecha_actuacion', date('Y-m-d')) }}" required
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                        <p class="text-xs text-gray-400 mt-1">Como administrador, podés modificarla.</p>
                        @else
                        <input type="date" name="fecha_actuacion" value="{{ old('fecha_actuacion', date('Y-m-d')) }}" readonly required
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-gray-100 text-gray-500 cursor-not-allowed">
                        <p class="text-xs text-gray-400 mt-1">Se registra automáticamente con la fecha de hoy.</p>
                        @endif
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Fecha de vencimiento</label>
                        <input type="date" name="fecha_vencimiento" value="{{ old('fecha_vencimiento') }}"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <input type="checkbox" name="requiere_respuesta" id="requiere_respuesta-nuevo" value="1"
                           {{ old('requiere_respuesta') ? 'checked' : '' }}
                           class="w-4 h-4 text-brand-700 rounded">
                    <label for="requiere_respuesta-nuevo" class="text-sm text-gray-700">
                        Esta actuación requiere respuesta / seguimiento
                    </label>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Archivo adjunto *</label>
                    <input type="file" name="archivo_adjunto" required
                           accept=".pdf,.doc,.docx,.jpg,.png"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                    <p class="text-xs text-gray-400 mt-1">PDF, Word o imagen. Máx. 10MB.</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Observaciones</label>
                    <textarea name="observaciones" rows="2"
                              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">{{ old('observaciones') }}</textarea>
                </div>

                <div id="gastos-wrap-nuevo" class="hidden">
                    <div class="flex items-center justify-between mb-2">
                        <label class="block text-sm font-medium text-gray-700">Gastos de actuación (opcional)</label>
                        <button type="button" onclick="agregarGasto('gastos-filas-nuevo')"
                                class="text-xs text-brand-700 hover:underline">+ Agregar gasto</button>
                    </div>
                    <div id="gastos-filas-nuevo" class="space-y-2"></div>
                </div>

                <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                    <input type="checkbox" name="notificar_cliente" value="1" {{ old('notificar_cliente') ? 'checked' : '' }}>
                    <i class="fab fa-whatsapp text-emerald-600"></i>
                    Notificar al cliente por WhatsApp
                </label>

                <div class="flex gap-3 pt-2">
                    <button type="submit" class="bg-brand-600 hover:bg-brand-700 text-white px-6 py-2 rounded-lg font-medium">
                        <i class="fas fa-save mr-1"></i> Registrar Actuación
                    </button>
                    <button type="button" onclick="cerrarModal('modal-seguimiento-nuevo')"
                            class="text-gray-600 hover:text-gray-800 px-4 py-2 rounded-lg border hover:bg-gray-50">
                        Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modales flotantes: Editar Actuación --}}
@foreach($seguimientosParaModales as $seg)
<div id="modal-seguimiento-editar-{{ $seg->id }}" class="hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="fixed inset-0 bg-black/50" onclick="cerrarModal('modal-seguimiento-editar-{{ $seg->id }}')"></div>
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="relative bg-white rounded-xl shadow-lg w-full max-w-2xl max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between px-6 py-4 border-b sticky top-0 bg-white rounded-t-xl">
                <h3 class="font-semibold text-gray-800 text-lg">Editar: {{ $seg->titulo }}</h3>
                <button type="button" onclick="cerrarModal('modal-seguimiento-editar-{{ $seg->id }}')" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>
            <form method="POST" action="{{ route('seguimientos.update', $seg) }}" class="p-6 space-y-4">
                @csrf @method('PUT')
                <input type="hidden" name="_modal" value="modal-seguimiento-editar-{{ $seg->id }}">

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Vincular a *</label>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <select name="expediente_id" id="select-expediente-editar-{{ $seg->id }}" onchange="actualizarCamposGasto('editar-{{ $seg->id }}')"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                                <option value="">— Ningún expediente —</option>
                                @foreach($expedientes as $exp)
                                    <option value="{{ $exp->id }}" {{ $seg->expediente_id == $exp->id ? 'selected' : '' }}>
                                        {{ $exp->numero }} — {{ $exp->cliente->nombre_completo }} ({{ $exp->abogado->nombre ?? 'sin abogado' }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <select name="tramite_id" id="select-tramite-editar-{{ $seg->id }}" onchange="actualizarCamposGasto('editar-{{ $seg->id }}')"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                                <option value="">— Ningún trámite —</option>
                                @foreach($tramites as $tra)
                                    <option value="{{ $tra->id }}" {{ $seg->tramite_id == $tra->id ? 'selected' : '' }}>
                                        {{ $tra->codigo }} — {{ $tra->nombre }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <p class="text-xs text-gray-400 mt-1">Elegí un expediente o un trámite (no ambos).</p>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tipo *</label>
                        <div class="flex gap-2">
                            <select name="tipo_actuacion_id" required id="select-tipo-actuacion-editar-{{ $seg->id }}"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                                <option value="">— Seleccioná un tipo —</option>
                                @foreach($tiposActuacion as $t)
                                    <option value="{{ $t->id }}" {{ $seg->tipo_actuacion_id == $t->id ? 'selected' : '' }}>
                                        {{ $t->nombre }}
                                    </option>
                                @endforeach
                            </select>
                            @if(auth()->user()->puede('parametros', 'crear'))
                            <button type="button" title="Nuevo tipo de actuación"
                                    onclick="abrirAltaRapida({titulo: 'Nuevo tipo de actuación', etiqueta: 'Nombre', placeholder: 'Ej: Presentación de escrito', url: '{{ route('parametros.tipos-actuacion.store') }}', selectId: 'select-tipo-actuacion-editar-{{ $seg->id }}'})"
                                    class="shrink-0 border border-gray-300 rounded-lg w-9 h-9 flex items-center justify-center text-gray-500 hover:text-brand-700 hover:border-brand-400">
                                <i class="fas fa-plus"></i>
                            </button>
                            @endif
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Prioridad *</label>
                        <select name="prioridad" required
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                            @foreach($prioridades as $p)
                                <option value="{{ $p }}" {{ $seg->prioridad === $p ? 'selected' : '' }}>{{ ucfirst($p) }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Título *</label>
                    <input type="text" name="titulo" value="{{ $seg->titulo }}" required
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                </div>
                <div id="gastos-wrap-editar-{{ $seg->id }}" class="hidden">
                    <div class="flex items-center justify-between mb-2">
                        <label class="block text-sm font-medium text-gray-700">Gastos de actuación (opcional)</label>
                        <button type="button" onclick="agregarGasto('gastos-filas-editar-{{ $seg->id }}')"
                                class="text-xs text-brand-700 hover:underline">+ Agregar gasto</button>
                    </div>
                    <div id="gastos-filas-editar-{{ $seg->id }}" class="space-y-2"></div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Fecha de actuación *</label>
                        @if(auth()->user()->puede('seguimientos', 'modificar_fecha'))
                        <input type="date" name="fecha_actuacion" value="{{ $seg->fecha_actuacion->format('Y-m-d') }}" required
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                        @else
                        <input type="date" name="fecha_actuacion" value="{{ $seg->fecha_actuacion->format('Y-m-d') }}" readonly required
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-gray-100 text-gray-500 cursor-not-allowed">
                        @endif
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Fecha de vencimiento</label>
                        <input type="date" name="fecha_vencimiento" value="{{ $seg->fecha_vencimiento?->format('Y-m-d') }}"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                    </div>
                </div>

                @if($seg->archivo_adjunto)
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de documento adjunto</label>
                    <div class="flex gap-2">
                        <select name="tipo_documento_id" id="select-tipo-documento-editar-{{ $seg->id }}"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                            <option value="">— Seleccioná un tipo —</option>
                            @foreach($tiposDocumento as $t)
                                <option value="{{ $t->id }}" {{ $seg->tipo_documento_id == $t->id ? 'selected' : '' }}>{{ $t->nombre }}</option>
                            @endforeach
                        </select>
                        @if(auth()->user()->puede('parametros', 'crear'))
                        <button type="button" title="Nuevo tipo de documento"
                                onclick="abrirAltaRapida({titulo: 'Nuevo tipo de documento', etiqueta: 'Nombre', placeholder: 'Ej: Memorial', url: '{{ route('parametros.tipos-documento.store') }}', selectId: 'select-tipo-documento-editar-{{ $seg->id }}'})"
                                class="shrink-0 border border-gray-300 rounded-lg w-9 h-9 flex items-center justify-center text-gray-500 hover:text-brand-700 hover:border-brand-400">
                            <i class="fas fa-plus"></i>
                        </button>
                        @endif
                    </div>
                </div>
                @endif

                <div class="flex items-center gap-4">
                    <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                        <input type="checkbox" name="requiere_respuesta" value="1"
                               {{ $seg->requiere_respuesta ? 'checked' : '' }}
                               class="w-4 h-4 text-brand-700 rounded">
                        Requiere respuesta
                    </label>
                    <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                        <input type="checkbox" name="respondido" value="1"
                               {{ $seg->respondido ? 'checked' : '' }}
                               class="w-4 h-4 text-green-600 rounded">
                        Respondido
                    </label>
                </div>

                @if($seg->respondido)
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fecha de respuesta</label>
                    <input type="date" name="fecha_respuesta" value="{{ $seg->fecha_respuesta?->format('Y-m-d') }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                </div>
                @endif

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Observaciones</label>
                    <textarea name="observaciones" rows="2"
                              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">{{ $seg->observaciones }}</textarea>
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="submit" class="bg-brand-600 hover:bg-brand-700 text-white px-6 py-2 rounded-lg font-medium">
                        <i class="fas fa-save mr-1"></i> Guardar cambios
                    </button>
                    <button type="button" onclick="cerrarModal('modal-seguimiento-editar-{{ $seg->id }}')"
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
    // Búsqueda en vivo de expedientes: reemplaza solo la tabla (sin recargar toda la
    // página), para que no se pierda el foco del campo ni la posición del scroll.
    let buscarExpedientesTimeout = null;
    let buscarExpedientesController = null;
    const contenedorTablaExpedientes = document.getElementById('contenedor-tabla-expedientes');
    const urlBaseSeguimientos = @json(route('seguimientos.index'));

    function buscarExpedientesEnVivo() {
        clearTimeout(buscarExpedientesTimeout);
        buscarExpedientesTimeout = setTimeout(() => {
            const params = new URLSearchParams(window.location.search);
            params.set('buscar', document.querySelector('input[name="buscar"]').value);
            params.delete('page');
            cargarTablaExpedientes(urlBaseSeguimientos + '?' + params.toString());
        }, 300);
    }

    function cargarTablaExpedientes(url) {
        if (buscarExpedientesController) buscarExpedientesController.abort();
        buscarExpedientesController = new AbortController();

        fetch(url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            signal: buscarExpedientesController.signal,
        })
            .then(r => r.text())
            .then(html => {
                contenedorTablaExpedientes.innerHTML = html;
                history.replaceState(null, '', url);
            })
            .catch(err => {
                if (err.name !== 'AbortError') console.error(err);
            });
    }

    // Los links de paginación de la tabla de expedientes (dentro de un <nav>, como
    // los genera Laravel) también se cargan en vivo, sin recargar la página.
    contenedorTablaExpedientes.addEventListener('click', function (e) {
        const link = e.target.closest('a');
        if (!link || !link.closest('nav')) return;
        e.preventDefault();
        cargarTablaExpedientes(link.href);
    });

    @if($errors->any() && old('_modal'))
        abrirModal(@json(old('_modal')));
    @endif

    @if(request()->filled('nuevo'))
        abrirModal('modal-seguimiento-nuevo');
    @endif

    @if(request()->filled('editar') && ctype_digit((string) request('editar')))
        abrirModal(@json('modal-seguimiento-editar-' . request('editar')));
    @endif

    // Gastos de la actuación: lista de filas opcional (varios gastos),
    // se muestra tanto si se vinculó un expediente como un trámite.
    function actualizarCamposGasto(sufijo) {
        const selectExp = document.getElementById('select-expediente-' + sufijo);
        const selectTra = document.getElementById('select-tramite-' + sufijo);
        const wrap = document.getElementById('gastos-wrap-' + sufijo);
        if (!selectTra || !wrap) return;

        const tieneVinculo = !!selectTra.value || !!(selectExp && selectExp.value);
        wrap.classList.toggle('hidden', !tieneVinculo);
    }

    function actualizarDemandados(sufijo) {
        const selectExp = document.getElementById('select-expediente-' + sufijo);
        const wrap = document.getElementById('demandados-wrap-' + sufijo);
        const contenedor = document.getElementById('demandados-' + sufijo);
        if (!selectExp || !wrap || !contenedor) return;

        const opcion = selectExp.options[selectExp.selectedIndex];
        const nombres = opcion && opcion.dataset.demandados
            ? opcion.dataset.demandados.split(',').map(n => n.trim()).filter(Boolean)
            : [];

        contenedor.innerHTML = nombres.length
            ? nombres.map(n => `<span class="text-xs font-medium text-gray-700 bg-gray-100 border border-gray-200 rounded-full px-2.5 py-1">${n.replace(/</g, '&lt;')}</span>`).join('')
            : '<span class="text-xs text-gray-400">Sin demandados registrados.</span>';
        wrap.classList.toggle('hidden', !selectExp.value);
    }

    let gastoContador = 0;

    function crearFilaGasto(concepto, monto) {
        const idx = gastoContador++;
        const fila = document.createElement('div');
        fila.className = 'grid grid-cols-3 gap-2 items-start';
        fila.innerHTML = `
            <input type="text" name="gastos[${idx}][concepto]" placeholder="Concepto" value="${concepto ? String(concepto).replace(/"/g, '&quot;') : ''}"
                   class="col-span-2 w-full border border-gray-300 rounded-lg px-2 py-1.5 text-sm focus:ring-2 focus:ring-brand-400">
            <div class="flex gap-1">
                <input type="number" name="gastos[${idx}][monto]" placeholder="Monto" step="0.01" min="0.01" value="${monto ? String(monto) : ''}"
                       class="w-full border border-gray-300 rounded-lg px-2 py-1.5 text-sm focus:ring-2 focus:ring-brand-400">
                <button type="button" onclick="this.closest('div.grid').remove()" class="text-red-400 hover:text-red-600 px-2 flex-shrink-0" title="Quitar">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        `;
        return fila;
    }

    function agregarGasto(containerId, concepto, monto) {
        const contenedor = document.getElementById(containerId);
        if (!contenedor) return;
        contenedor.appendChild(crearFilaGasto(concepto, monto));
    }

    actualizarCamposGasto('nuevo');
    actualizarDemandados('nuevo');
    @foreach($seguimientosParaModales as $seg)
        actualizarCamposGasto('editar-{{ $seg->id }}');
        @foreach($seg->gastos as $g)
            agregarGasto('gastos-filas-editar-{{ $seg->id }}', @json($g->concepto), @json($g->monto));
        @endforeach
    @endforeach

    @if(old('gastos'))
        @foreach(old('gastos') as $g)
            agregarGasto('gastos-filas-nuevo', @json($g['concepto'] ?? ''), @json($g['monto'] ?? ''));
        @endforeach
    @endif

    (function () {
        const form = document.getElementById('form-seguimiento-nuevo');
        if (!form) return;

        form.addEventListener('submit', function (e) {
            const checkbox = form.querySelector('input[name=notificar_cliente]');
            if (!checkbox || !checkbox.checked || form.dataset.whatsappConfirmado === '1') {
                return;
            }

            const selectExp = document.getElementById('select-expediente-nuevo');
            const selectTra = document.getElementById('select-tramite-nuevo');
            const opcionExp = selectExp.options[selectExp.selectedIndex];
            const opcionTra = selectTra.options[selectTra.selectedIndex];
            const opcion = opcionExp.value ? opcionExp : (opcionTra.value ? opcionTra : null);

            if (!opcion || !opcion.dataset.clienteWhatsapp) {
                return;
            }

            e.preventDefault();

            const selectTipo = form.querySelector('select[name=tipo_actuacion_id]');
            const tipoTexto = selectTipo && selectTipo.selectedIndex > -1
                ? selectTipo.options[selectTipo.selectedIndex].text.trim()
                : 'una actuación';
            const observaciones = form.querySelector('textarea[name=observaciones]').value.trim();

            let texto = `Hola ${opcion.dataset.clienteNombre}, se registró una nueva actuación en tu caso: "${tipoTexto}".`;
            if (observaciones) {
                texto += `\n\n${observaciones}`;
            }

            mostrarPreviaWhatsapp(form, texto, opcion.dataset.clienteWhatsapp);
        });
    })();
</script>
@endpush
@endsection
