@extends('layouts.app')

@section('title', 'Trámite')
@section('header', $tramite->nombre)

@section('header-actions')
    <a href="{{ route('tramites.index') }}"
       class="bg-white border hover:bg-gray-50 text-gray-700 px-3 py-2 rounded-lg text-sm flex items-center gap-2">
        <i class="fas fa-arrow-left"></i> Volver
    </a>
    @if(auth()->user()->puede('seguimientos', 'crear'))
    <button type="button" onclick="abrirModal('modal-seguimiento-nuevo')"
       class="bg-green-600 hover:bg-green-700 text-white px-3 py-2 rounded-lg text-sm flex items-center gap-2">
        <i class="fas fa-plus"></i> Seguimiento
    </button>
    @endif
    @if(auth()->user()->puede('gastos_cobros', 'crear') || auth()->user()->puede('gastos_cobros', 'cobrar'))
    <button type="button" onclick="abrirModal('modal-gasto-cobro-nuevo')"
       class="bg-amber-600 hover:bg-amber-700 text-white px-3 py-2 rounded-lg text-sm flex items-center gap-2">
        <i class="fas fa-coins"></i> Gasto / Cobro
    </button>
    @endif
    <a href="{{ route('tramites.reporte', $tramite) }}" target="_blank"
       class="bg-white border hover:bg-gray-50 text-gray-700 px-3 py-2 rounded-lg text-sm flex items-center gap-2">
        <i class="fas fa-file-invoice"></i> Reporte
    </a>
    @if(auth()->user()->puede('tramites', 'modificar'))
    <a href="{{ route('tramites.index', ['editar' => $tramite->id, 'codigo' => $tramite->codigo]) }}"
       class="bg-brand-600 hover:bg-brand-700 text-white px-3 py-2 rounded-lg text-sm flex items-center gap-2">
        <i class="fas fa-edit"></i> Editar
    </a>
    @endif
@endsection

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- Columna 1: Detalles del trámite --}}
    <div class="space-y-4">
        <div class="bg-white rounded-xl shadow-sm p-6">
            <div class="flex flex-wrap items-center gap-2 mb-4">
                <span class="text-xs font-mono text-gray-500">{{ $tramite->codigo }}</span>
                <span class="px-2 py-1 rounded text-xs font-medium bg-brand-100 text-brand-800">
                    {{ $tramite->tipoTramite?->nombre ?? '—' }}
                </span>
                <span class="px-2 py-1 rounded text-xs font-medium bg-{{ $tramite->estado_color }}-100 text-{{ $tramite->estado_color }}-700">
                    {{ ucfirst(str_replace('_', ' ', $tramite->estado)) }}
                </span>
                <span class="px-2 py-1 rounded text-xs font-medium bg-{{ $tramite->prioridad_color }}-100 text-{{ $tramite->prioridad_color }}-700">
                    Prioridad {{ ucfirst($tramite->prioridad) }}
                </span>
            </div>

            <dl class="space-y-3 text-sm">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <dt class="text-gray-500">Fecha de inicio</dt>
                        <dd class="font-medium">{{ $tramite->fecha_inicio->format('d/m/Y') }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Fecha de finalización aproximada</dt>
                        <dd class="font-medium">{{ $tramite->fecha_fin_aproximada?->format('d/m/Y') ?? '—' }}</dd>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4 pt-2 border-t">
                    <div>
                        <dt class="text-gray-500">Institución pública</dt>
                        <dd class="font-medium">{{ $tramite->institucionPublica?->nombre ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Responsable</dt>
                        <dd class="font-medium">{{ $tramite->responsable?->nombre ?? '—' }}</dd>
                    </div>
                </div>

                <div class="pt-2 border-t space-y-1.5">
                    <div class="flex justify-between items-center">
                        <dt class="text-gray-500">Total gastado</dt>
                        <dd class="font-medium text-amber-700">{{ number_format($tramite->total_gastos, 2) }} Bs</dd>
                    </div>
                    <div class="flex justify-between items-center">
                        <dt class="text-gray-500">Total cobrado</dt>
                        <dd class="font-medium text-emerald-700">{{ number_format($tramite->total_cobrado, 2) }} Bs</dd>
                    </div>
                    <div class="flex justify-between items-center pt-1.5 border-t">
                        <dt class="text-gray-600 font-medium">Saldo pendiente</dt>
                        <dd class="font-semibold {{ $tramite->saldo_pendiente > 0 ? 'text-red-600' : 'text-gray-800' }}">
                            {{ number_format($tramite->saldo_pendiente, 2) }} Bs
                        </dd>
                    </div>
                    @if($tramite->estado_pago !== 'sin_gastos')
                    <div class="flex justify-between items-center">
                        <dt class="text-gray-600 font-medium">Estado de la cuenta</dt>
                        <dd>
                            <span class="px-2 py-0.5 rounded text-xs font-medium bg-{{ $tramite->estado_pago_color }}-100 text-{{ $tramite->estado_pago_color }}-700">
                                {{ $tramite->estado_pago_label }}
                            </span>
                        </dd>
                    </div>
                    @endif
                </div>

                @if($tramite->observaciones)
                <div class="pt-2 border-t">
                    <dt class="text-gray-500 mb-1">Observaciones</dt>
                    <dd class="text-gray-800 whitespace-pre-line">{{ $tramite->observaciones }}</dd>
                </div>
                @endif
            </dl>
        </div>
    </div>

    {{-- Columna 2: Actuaciones --}}
    <div class="space-y-4">
        @include('partials._actuaciones', ['seguimientos' => $tramite->seguimientos])
    </div>

    {{-- Columna 3: Documentos --}}
    <div class="space-y-4">
        @php $documentos = $tramite->seguimientos->whereNotNull('archivo_adjunto'); @endphp
        <div class="bg-white rounded-xl shadow-sm">
            <div class="flex items-center justify-between p-5 border-b">
                <h3 class="font-semibold text-gray-700">
                    <i class="fas fa-folder-open text-brand-600 mr-2"></i>
                    Documentos ({{ $documentos->count() }})
                </h3>
                @if($documentos->isNotEmpty() && auth()->user()->puede('documentos', 'descargar'))
                <div class="flex items-center gap-3">
                    <a href="{{ route('tramites.documentos.pdf', $tramite) }}"
                       class="text-xs text-brand-600 hover:underline font-medium flex items-center gap-1">
                        <i class="fas fa-file-pdf"></i> Descargar trámite en PDF
                    </a>
                    <a href="{{ route('tramites.documentos.zip', $tramite) }}"
                       class="text-xs text-brand-600 hover:underline font-medium flex items-center gap-1">
                        <i class="fas fa-file-zipper"></i> Exportar ZIP
                    </a>
                </div>
                @endif
            </div>
            <div class="divide-y divide-gray-100">
                @forelse($documentos as $doc)
                <div class="px-5 py-3 hover:bg-gray-50 flex items-center gap-3">
                    <div class="w-9 h-9 rounded-lg bg-amber-100 text-amber-600 flex items-center justify-center flex-shrink-0">
                        <i class="fas {{ $doc->icono_archivo }}"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <a href="{{ route('seguimientos.show', $doc) }}" class="font-medium text-sm text-gray-800 hover:text-brand-700 truncate block">
                            {{ $doc->titulo }}
                        </a>
                        <p class="text-xs text-gray-500">
                            {{ $doc->fecha_actuacion->format('d/m/Y') }}
                            @if($doc->tipoDocumento) · {{ $doc->tipoDocumento->nombre }} @endif
                        </p>
                    </div>
                    <div class="flex gap-3 text-xs flex-shrink-0">
                        @if(auth()->user()->puede('documentos', 'ver'))
                        <a href="{{ route('documentos.ver', $doc) }}" target="_blank"
                           class="text-gray-400 hover:text-brand-700" title="Ver">
                            <i class="fas fa-eye"></i>
                        </a>
                        @endif
                        @if(auth()->user()->puede('documentos', 'descargar'))
                        <a href="{{ route('documentos.descargar', $doc) }}"
                           class="text-gray-400 hover:text-brand-700" title="Descargar">
                            <i class="fas fa-download"></i>
                        </a>
                        @endif
                    </div>
                </div>
                @empty
                <p class="p-5 text-sm text-gray-400 text-center">Sin documentos subidos.</p>
                @endforelse
            </div>
        </div>
    </div>

</div>

{{-- Modal flotante: Nueva Actuación --}}
<div id="modal-seguimiento-nuevo" class="hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="fixed inset-0 bg-black/50" onclick="cerrarModal('modal-seguimiento-nuevo')"></div>
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="relative bg-white rounded-xl shadow-lg w-full max-w-2xl max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between px-6 py-4 border-b sticky top-0 bg-white rounded-t-xl">
                <h3 class="font-semibold text-gray-800 text-lg">Nuevo Seguimiento</h3>
                <button type="button" onclick="cerrarModal('modal-seguimiento-nuevo')" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>
            <form method="POST" action="{{ route('seguimientos.store') }}" enctype="multipart/form-data" class="p-6 space-y-4"
                  id="form-seguimiento-nuevo-tramite" data-cliente-nombre="{{ $tramite->cliente->nombre_completo }}"
                  data-cliente-whatsapp="{{ $tramite->cliente->telefono_whatsapp }}">
                @csrf
                <input type="hidden" name="_modal" value="modal-seguimiento-nuevo">
                <input type="hidden" name="tramite_id" value="{{ $tramite->id }}">

                <p class="text-sm text-gray-500 -mt-2">
                    Trámite: <span class="font-medium text-gray-700">{{ $tramite->codigo }} — {{ $tramite->nombre }} | {{ $tramite->cliente->nombre_completo }}</span>
                </p>

                <input type="hidden" name="prioridad" value="media">

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de Acción *</label>
                    <div class="flex gap-2">
                        <select name="tipo_actuacion_id" id="select-tipo-actuacion-tramite" required
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                            <option value="">— Seleccioná un tipo —</option>
                            @foreach($tiposActuacion as $t)
                                <option value="{{ $t->id }}" {{ old('tipo_actuacion_id') == $t->id ? 'selected' : '' }}>{{ $t->nombre }}</option>
                            @endforeach
                        </select>
                        @if(auth()->user()->puede('parametros', 'crear'))
                        <button type="button" onclick="abrirModal('modal-tipo-actuacion-rapido-tramite')" title="Nuevo tipo de actuación"
                                class="shrink-0 border border-gray-300 rounded-lg w-9 h-9 flex items-center justify-center text-gray-500 hover:text-brand-700 hover:border-brand-400">
                            <i class="fas fa-plus"></i>
                        </button>
                        @endif
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Fecha de actuación</label>
                        @if(auth()->user()->esAdmin())
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
                    <input type="checkbox" name="requiere_respuesta" id="requiere_respuesta-seguimiento-tramite" value="1"
                           {{ old('requiere_respuesta') ? 'checked' : '' }}
                           class="w-4 h-4 text-brand-700 rounded">
                    <label for="requiere_respuesta-seguimiento-tramite" class="text-sm text-gray-700">
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

                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label class="block text-sm font-medium text-gray-700">Gastos de actuación (opcional)</label>
                        <button type="button" onclick="agregarGastoActuacionTramite()"
                                class="text-xs text-brand-700 hover:underline">+ Agregar gasto</button>
                    </div>
                    <div id="gastos-filas-seguimiento-tramite" class="space-y-2"></div>
                </div>

                <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                    <input type="checkbox" name="notificar_cliente" value="1" {{ old('notificar_cliente') ? 'checked' : '' }}>
                    <i class="fab fa-whatsapp text-emerald-600"></i>
                    Notificar al cliente por WhatsApp
                </label>

                <div class="flex gap-3 pt-2">
                    <button type="submit" class="bg-brand-600 hover:bg-brand-700 text-white px-6 py-2 rounded-lg font-medium">
                        <i class="fas fa-save mr-1"></i> Registrar Seguimiento
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

{{-- Modal flotante: alta rápida de tipo de gasto --}}
<div id="modal-tipo-gasto-rapido-tramite" class="hidden fixed inset-0 z-[60] overflow-y-auto">
    <div class="fixed inset-0 bg-black/50" onclick="cerrarModal('modal-tipo-gasto-rapido-tramite')"></div>
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="relative bg-white rounded-xl shadow-lg w-full max-w-sm">
            <div class="flex items-center justify-between px-6 py-4 border-b">
                <h3 class="font-semibold text-gray-800 text-lg">Nuevo tipo de gasto</h3>
                <button type="button" onclick="cerrarModal('modal-tipo-gasto-rapido-tramite')" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>
            <div class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nombre *</label>
                    <input type="text" id="input-tipo-gasto-rapido-nombre-tramite" placeholder="Ej: Arancel de inscripción"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                    <p id="error-tipo-gasto-rapido-tramite" class="text-xs text-red-600 mt-1 hidden"></p>
                </div>
                <div class="flex gap-3">
                    <button type="button" onclick="crearTipoGastoRapidoTramite()"
                            class="bg-brand-600 hover:bg-brand-700 text-white px-4 py-2 rounded-lg text-sm font-medium">
                        Guardar
                    </button>
                    <button type="button" onclick="cerrarModal('modal-tipo-gasto-rapido-tramite')"
                            class="text-gray-600 hover:text-gray-800 px-4 py-2 rounded-lg border hover:bg-gray-50 text-sm">
                        Cancelar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modal flotante: alta rápida de tipo de actuación --}}
<div id="modal-tipo-actuacion-rapido-tramite" class="hidden fixed inset-0 z-[60] overflow-y-auto">
    <div class="fixed inset-0 bg-black/50" onclick="cerrarModal('modal-tipo-actuacion-rapido-tramite')"></div>
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="relative bg-white rounded-xl shadow-lg w-full max-w-sm">
            <div class="flex items-center justify-between px-6 py-4 border-b">
                <h3 class="font-semibold text-gray-800 text-lg">Nuevo tipo de actuación</h3>
                <button type="button" onclick="cerrarModal('modal-tipo-actuacion-rapido-tramite')" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>
            <div class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nombre *</label>
                    <input type="text" id="input-tipo-actuacion-rapido-nombre-tramite" placeholder="Ej: Presentación de escrito"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                    <p id="error-tipo-actuacion-rapido-tramite" class="text-xs text-red-600 mt-1 hidden"></p>
                </div>
                <div class="flex gap-3">
                    <button type="button" onclick="crearTipoActuacionRapidoTramite()"
                            class="bg-brand-600 hover:bg-brand-700 text-white px-4 py-2 rounded-lg text-sm font-medium">
                        Guardar
                    </button>
                    <button type="button" onclick="cerrarModal('modal-tipo-actuacion-rapido-tramite')"
                            class="text-gray-600 hover:text-gray-800 px-4 py-2 rounded-lg border hover:bg-gray-50 text-sm">
                        Cancelar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modal flotante: alta rápida de tipo de documento --}}
<div id="modal-tipo-documento-rapido-tramite" class="hidden fixed inset-0 z-[60] overflow-y-auto">
    <div class="fixed inset-0 bg-black/50" onclick="cerrarModal('modal-tipo-documento-rapido-tramite')"></div>
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="relative bg-white rounded-xl shadow-lg w-full max-w-sm">
            <div class="flex items-center justify-between px-6 py-4 border-b">
                <h3 class="font-semibold text-gray-800 text-lg">Nuevo tipo de documento</h3>
                <button type="button" onclick="cerrarModal('modal-tipo-documento-rapido-tramite')" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>
            <div class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nombre *</label>
                    <input type="text" id="input-tipo-documento-rapido-nombre-tramite" placeholder="Ej: Memorial"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                    <p id="error-tipo-documento-rapido-tramite" class="text-xs text-red-600 mt-1 hidden"></p>
                </div>
                <div class="flex gap-3">
                    <button type="button" onclick="crearTipoDocumentoRapidoTramite()"
                            class="bg-brand-600 hover:bg-brand-700 text-white px-4 py-2 rounded-lg text-sm font-medium">
                        Guardar
                    </button>
                    <button type="button" onclick="cerrarModal('modal-tipo-documento-rapido-tramite')"
                            class="text-gray-600 hover:text-gray-800 px-4 py-2 rounded-lg border hover:bg-gray-50 text-sm">
                        Cancelar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modal flotante: Gasto / Cobro (combinado) --}}
<div id="modal-gasto-cobro-nuevo" class="hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="fixed inset-0 bg-black/50" onclick="cerrarModal('modal-gasto-cobro-nuevo')"></div>
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="relative bg-white rounded-xl shadow-lg w-full max-w-2xl max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between px-6 py-4 border-b sticky top-0 bg-white rounded-t-xl">
                <h3 class="font-semibold text-gray-800 text-lg">Gasto / Cobro</h3>
                <button type="button" onclick="cerrarModal('modal-gasto-cobro-nuevo')" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>

            @php
                $puedeGestionarGastos = auth()->user()->puede('gastos_cobros', 'crear');
                $puedeCobrar = auth()->user()->puede('gastos_cobros', 'cobrar');
            @endphp

            @if($puedeGestionarGastos && $puedeCobrar)
            <div class="px-6 pt-4">
                <div class="flex gap-2">
                    <button type="button" data-tab="gasto" class="tab-principal-btn flex-1 px-3 py-2 rounded-lg text-sm font-medium border border-amber-600 bg-amber-600 text-white">
                        <i class="fas fa-coins mr-1"></i> Gasto
                    </button>
                    <button type="button" data-tab="cobro" class="tab-principal-btn flex-1 px-3 py-2 rounded-lg text-sm font-medium border border-gray-300 text-gray-700">
                        <i class="fas fa-hand-holding-dollar mr-1"></i> Cobro
                    </button>
                </div>
            </div>
            @endif

            {{-- Tab: Gasto --}}
            @if($puedeGestionarGastos)
            <div id="tab-panel-gasto" class="tab-panel-principal">
                <form method="POST" action="{{ route('gastos.store') }}" class="p-6 space-y-4">
                    @csrf
                    <input type="hidden" name="_modal" value="modal-gasto-cobro-nuevo">
                    <input type="hidden" name="tramite_id" value="{{ $tramite->id }}">

                    @if($tramite->gastos->isNotEmpty())
                    <div class="flex justify-end -mb-2">
                        <button type="button" onclick="abrirModal('modal-gastos-todos')"
                           class="text-xs text-amber-600 hover:underline font-medium flex items-center gap-1">
                            <i class="fas fa-clock-rotate-left"></i> Historial de gastos
                        </button>
                    </div>
                    @endif

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de gasto *</label>
                        <div class="flex gap-2">
                            <select name="tipo_gasto_id" id="select-tipo-gasto-tramite" required
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                                <option value="">— Seleccioná un tipo —</option>
                                @foreach($tiposGasto as $t)
                                    <option value="{{ $t->id }}" {{ old('tipo_gasto_id') == $t->id ? 'selected' : '' }}>{{ $t->nombre }}</option>
                                @endforeach
                            </select>
                            @if(auth()->user()->puede('parametros', 'crear'))
                            <button type="button" onclick="abrirModal('modal-tipo-gasto-rapido-tramite')" title="Nuevo tipo de gasto"
                                    class="shrink-0 border border-gray-300 rounded-lg w-9 h-9 flex items-center justify-center text-gray-500 hover:text-brand-700 hover:border-brand-400">
                                <i class="fas fa-plus"></i>
                            </button>
                            @endif
                        </div>
                        @error('tipo_gasto_id')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Concepto *</label>
                        <input type="text" name="concepto" value="{{ old('concepto') }}" required
                               placeholder="Ej: Arancel de inscripción"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                        @error('concepto')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Monto *</label>
                            <input type="number" name="monto" value="{{ old('monto') }}" step="0.01" min="0" required
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                            @error('monto')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Fecha *</label>
                            <input type="date" name="fecha" value="{{ old('fecha', date('Y-m-d')) }}" required
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                        </div>
                    </div>

                    <div class="flex gap-3 pt-2">
                        <button type="submit" class="bg-amber-600 hover:bg-amber-700 text-white px-6 py-2 rounded-lg font-medium">
                            <i class="fas fa-save mr-1"></i> Guardar Gasto
                        </button>
                        <button type="button" onclick="cerrarModal('modal-gasto-cobro-nuevo')"
                                class="text-gray-600 hover:text-gray-800 px-4 py-2 rounded-lg border hover:bg-gray-50">
                            Cancelar
                        </button>
                    </div>
                </form>
            </div>
            @endif

            {{-- Tab: Cobro --}}
            @if($puedeCobrar)
            <div id="tab-panel-cobro" class="tab-panel-principal {{ $puedeGestionarGastos ? 'hidden' : '' }}">
                <div class="px-6 pt-2">
                    <div class="bg-gray-50 rounded-lg p-4 mb-2">
                        <div class="flex items-center justify-between mb-1.5">
                            <span class="text-xs font-semibold text-gray-500 uppercase">Resumen</span>
                            @if(auth()->user()->puede('gastos_cobros', 'ver'))
                            <button type="button" onclick="abrirModal('modal-cobros-todos')"
                               class="text-xs text-brand-600 hover:underline font-medium flex items-center gap-1">
                                <i class="fas fa-clock-rotate-left"></i> Historial de pagos
                            </button>
                            @endif
                        </div>
                        <dl class="space-y-1.5 text-sm">
                            <div class="flex justify-between">
                                <dt class="text-gray-500">Total gastado</dt>
                                <dd class="font-medium text-amber-700">{{ number_format($tramite->total_gastos, 2) }} Bs</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-gray-500">Total cobrado</dt>
                                <dd class="font-medium text-emerald-700">{{ number_format($tramite->total_cobrado, 2) }} Bs</dd>
                            </div>
                            <div class="flex justify-between pt-1.5 border-t">
                                <dt class="text-gray-600 font-medium">Saldo pendiente</dt>
                                <dd class="font-semibold {{ $tramite->saldo_pendiente > 0 ? 'text-red-600' : 'text-gray-800' }}">
                                    {{ number_format($tramite->saldo_pendiente, 2) }} Bs
                                </dd>
                            </div>
                            @if($tramite->estado_pago !== 'sin_gastos')
                            <div class="flex justify-between items-center pt-1.5 border-t">
                                <dt class="text-gray-600 font-medium">Estado de la cuenta</dt>
                                <dd>
                                    <span class="px-2 py-0.5 rounded text-xs font-medium bg-{{ $tramite->estado_pago_color }}-100 text-{{ $tramite->estado_pago_color }}-700">
                                        {{ $tramite->estado_pago_label }}
                                    </span>
                                </dd>
                            </div>
                            @endif
                        </dl>
                    </div>
                </div>

                <form method="POST" action="{{ route('cobros.store') }}" class="p-6 pt-2 space-y-4"
                      id="form-cobro-nuevo"
                      data-cliente-nombre="{{ $tramite->cliente->nombre_completo }}"
                      data-cliente-whatsapp="{{ $tramite->cliente->telefono_whatsapp }}"
                      data-tramite-codigo="{{ $tramite->codigo }}"
                      data-tramite-nombre="{{ $tramite->nombre }}"
                      data-saldo-pendiente="{{ $tramite->saldo_pendiente }}">
                    @csrf
                    <input type="hidden" name="_modal" value="modal-gasto-cobro-nuevo">
                    <input type="hidden" name="tramite_id" value="{{ $tramite->id }}">
                    <input type="hidden" name="modo" id="modo" value="total">

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">¿Cómo querés cobrar?</label>
                        <div class="flex gap-2">
                            <button type="button" data-modo="total" class="modo-btn flex-1 px-3 py-2 rounded-lg text-sm font-medium border border-brand-600 bg-brand-600 text-white">
                                Cobro total
                            </button>
                            <button type="button" data-modo="abono" class="modo-btn flex-1 px-3 py-2 rounded-lg text-sm font-medium border border-gray-300 text-gray-700">
                                Abono
                            </button>
                        </div>
                    </div>

                    {{-- Modo: total --}}
                    @php $gastosPendientes = $tramite->gastos->where('cubierto', false); @endphp
                    <div id="panel-total" class="modo-panel">
                        @if($tramite->saldo_pendiente > 0)
                            <p class="text-sm text-gray-600 bg-gray-50 rounded-lg p-3">
                                Se va a registrar un cobro por el saldo pendiente completo:
                                <span class="font-semibold text-red-600">{{ number_format($tramite->saldo_pendiente, 2) }} Bs</span>
                            </p>
                        @else
                            <p class="text-sm text-gray-400 bg-gray-50 rounded-lg p-3">Este trámite no tiene saldo pendiente.</p>
                        @endif

                        @if($gastosPendientes->count() > 0)
                            <div class="mt-3">
                                <p class="text-xs font-semibold text-gray-500 uppercase mb-1.5">Ítems de gasto incluidos</p>
                                <div class="space-y-1 max-h-56 overflow-y-auto border rounded-lg p-3">
                                    @foreach($gastosPendientes as $gasto)
                                        @php $pendiente = (float) $gasto->monto - $gasto->total_cobrado; @endphp
                                        <div class="flex items-center justify-between gap-3 text-sm py-1">
                                            <span class="min-w-0">
                                                <span class="truncate block">{{ $gasto->concepto }}</span>
                                                @if($gasto->seguimiento)
                                                <span class="text-[11px] text-gray-400 block truncate">{{ $gasto->seguimiento->titulo }}</span>
                                                @endif
                                                @if(auth()->user()->rol?->nombre === 'Administrador')
                                                <span class="text-[11px] text-gray-400 block truncate">
                                                    <i class="fas fa-user-pen mr-0.5"></i>{{ $gasto->usuario?->name ?? '—' }}
                                                </span>
                                                @endif
                                            </span>
                                            <span class="text-gray-500 flex-shrink-0">{{ number_format($pendiente, 2) }} Bs</span>
                                        </div>
                                    @endforeach
                                </div>
                                <div class="flex justify-end mt-2">
                                    <a href="{{ route('tramites.items-cobro.pdf', $tramite) }}?{{ http_build_query(['gastos' => $gastosPendientes->pluck('id')->all()]) }}"
                                       target="_blank"
                                       class="text-xs text-gray-600 hover:text-brand-700 flex items-center gap-1.5 border border-gray-300 rounded-lg px-3 py-1.5 hover:bg-gray-50">
                                        <i class="fas fa-file-pdf text-red-500"></i> Generar PDF del detalle
                                    </a>
                                </div>
                            </div>
                        @endif
                    </div>

                    {{-- Modo: abono --}}
                    <div id="panel-abono" class="modo-panel hidden">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Monto del abono *</label>
                        <input type="number" name="monto" id="input-monto-abono" value="{{ old('monto') }}" step="0.01" min="0.01" max="{{ $tramite->saldo_pendiente }}"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                        <p class="text-xs text-gray-400 mt-1">Máximo: {{ number_format($tramite->saldo_pendiente, 2) }} Bs</p>
                        @error('monto')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div class="grid grid-cols-2 gap-4 pt-2 border-t">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Fecha *</label>
                            <input type="date" name="fecha" id="input-fecha-cobro" value="{{ old('fecha', date('Y-m-d')) }}" required
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Método de pago *</label>
                            <select name="metodo_pago" id="select-metodo-pago" required
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                                <option value="efectivo" {{ old('metodo_pago') === 'efectivo' ? 'selected' : '' }}>Efectivo</option>
                                <option value="qr" {{ old('metodo_pago') === 'qr' ? 'selected' : '' }}>QR</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-3 pt-2">
                        <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white px-6 py-2 rounded-lg font-medium">
                            <i class="fas fa-hand-holding-dollar mr-1"></i> Registrar Cobro
                        </button>
                        <button type="button" onclick="enviarInformeCobroWhatsapp()"
                                {{ $tramite->cliente->telefono_whatsapp ? '' : 'disabled title="El cliente no tiene teléfono registrado"' }}
                                class="bg-[#25D366] hover:bg-[#1ebe57] disabled:opacity-50 disabled:cursor-not-allowed text-white px-4 py-2 rounded-lg font-medium flex items-center gap-2">
                            <i class="fab fa-whatsapp text-lg"></i> Enviar informe
                        </button>
                        <button type="button" onclick="cerrarModal('modal-gasto-cobro-nuevo')"
                                class="text-gray-600 hover:text-gray-800 px-4 py-2 rounded-lg border hover:bg-gray-50">
                            Cancelar
                        </button>
                    </div>
                </form>
            </div>
            @endif
        </div>
    </div>
</div>

{{-- Modal flotante: Ver todos los gastos --}}
<div id="modal-gastos-todos" class="hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="fixed inset-0 bg-black/50" onclick="cerrarModal('modal-gastos-todos')"></div>
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="relative bg-white rounded-xl shadow-lg w-full max-w-lg max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between px-6 py-4 border-b sticky top-0 bg-white rounded-t-xl">
                <h3 class="font-semibold text-gray-800 text-lg">
                    <i class="fas fa-coins text-amber-600 mr-2"></i>
                    Todos los gastos ({{ $tramite->gastos->count() }})
                </h3>
                <button type="button" onclick="cerrarModal('modal-gastos-todos')" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>
            <div class="divide-y divide-gray-100">
                @foreach($tramite->gastos as $gasto)
                <div class="px-5 py-3 hover:bg-gray-50 flex items-start gap-3">
                    <div class="flex-1 min-w-0">
                        <p class="font-medium text-sm text-gray-800">{{ $gasto->concepto }}</p>
                        <p class="text-xs text-gray-500">
                            {{ $gasto->tipoGasto?->nombre ?? 'Sin tipo' }} · {{ $gasto->fecha->format('d/m/Y') }}
                            @if($gasto->usuario) · {{ $gasto->usuario->name }} @endif
                        </p>
                        @if($gasto->seguimiento)
                        <p class="text-[11px] text-gray-400 mt-0.5">
                            <i class="fas fa-diagram-project mr-1"></i>{{ $gasto->seguimiento->titulo }}
                        </p>
                        @endif
                        <span class="inline-block mt-1 px-1.5 py-0.5 rounded text-[10px] font-medium
                            {{ $gasto->cubierto ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-500' }}">
                            {{ $gasto->cubierto ? 'Cobrado' : 'Pendiente' }}
                        </span>
                    </div>
                    <span class="text-sm font-semibold text-amber-700 flex-shrink-0">{{ number_format($gasto->monto, 2) }} Bs</span>
                    <div class="flex gap-2 text-xs flex-shrink-0">
                        @if(auth()->user()->puede('gastos_cobros', 'modificar'))
                        <button type="button" onclick="cerrarModal('modal-gastos-todos'); abrirModal('modal-gasto-editar-{{ $gasto->id }}')" class="text-gray-400 hover:text-brand-700" title="Editar">
                            <i class="fas fa-edit"></i>
                        </button>
                        @endif
                        @if(auth()->user()->puede('gastos_cobros', 'eliminar'))
                        <form method="POST" action="{{ route('gastos.destroy', $gasto) }}"
                              onsubmit="return confirm('¿Eliminar este gasto?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-gray-400 hover:text-red-600" title="Eliminar">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
            <div class="px-5 py-3 border-t bg-gray-50 flex justify-between items-center text-sm rounded-b-xl">
                <span class="text-gray-600 font-medium">Total</span>
                <span class="font-semibold text-amber-700">{{ number_format($tramite->total_gastos, 2) }} Bs</span>
            </div>
        </div>
    </div>
</div>

{{-- Modales flotantes: Editar Gasto --}}
@foreach($tramite->gastos as $gasto)
<div id="modal-gasto-editar-{{ $gasto->id }}" class="hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="fixed inset-0 bg-black/50" onclick="cerrarModal('modal-gasto-editar-{{ $gasto->id }}')"></div>
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="relative bg-white rounded-xl shadow-lg w-full max-w-lg max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between px-6 py-4 border-b sticky top-0 bg-white rounded-t-xl">
                <h3 class="font-semibold text-gray-800 text-lg">Editar Gasto</h3>
                <button type="button" onclick="cerrarModal('modal-gasto-editar-{{ $gasto->id }}')" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>
            <form method="POST" action="{{ route('gastos.update', $gasto) }}" class="p-6 space-y-4">
                @csrf @method('PUT')
                <input type="hidden" name="_modal" value="modal-gasto-editar-{{ $gasto->id }}">

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de gasto *</label>
                    <div class="flex gap-2">
                        <select name="tipo_gasto_id" required id="select-tipo-gasto-editar-{{ $gasto->id }}"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                            <option value="">— Seleccioná un tipo —</option>
                            @foreach($tiposGasto as $t)
                                <option value="{{ $t->id }}" {{ $gasto->tipo_gasto_id == $t->id ? 'selected' : '' }}>{{ $t->nombre }}</option>
                            @endforeach
                        </select>
                        @if(auth()->user()->puede('parametros', 'crear'))
                        <button type="button" title="Nuevo tipo de gasto"
                                onclick="abrirAltaRapida({titulo: 'Nuevo tipo de gasto', etiqueta: 'Nombre', placeholder: 'Ej: Arancel de inscripción', url: '{{ route('parametros.tipos-gasto.store') }}', selectId: 'select-tipo-gasto-editar-{{ $gasto->id }}'})"
                                class="shrink-0 border border-gray-300 rounded-lg w-9 h-9 flex items-center justify-center text-gray-500 hover:text-brand-700 hover:border-brand-400">
                            <i class="fas fa-plus"></i>
                        </button>
                        @endif
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Concepto *</label>
                    <input type="text" name="concepto" value="{{ $gasto->concepto }}" required
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Monto *</label>
                        <input type="number" name="monto" value="{{ $gasto->monto }}" step="0.01" min="0" required
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Fecha *</label>
                        <input type="date" name="fecha" value="{{ $gasto->fecha->format('Y-m-d') }}" required
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                    </div>
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="submit" class="bg-amber-600 hover:bg-amber-700 text-white px-6 py-2 rounded-lg font-medium">
                        <i class="fas fa-save mr-1"></i> Guardar cambios
                    </button>
                    <button type="button" onclick="cerrarModal('modal-gasto-editar-{{ $gasto->id }}')"
                            class="text-gray-600 hover:text-gray-800 px-4 py-2 rounded-lg border hover:bg-gray-50">
                        Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach

{{-- Modal flotante: Ver todos los cobros --}}
<div id="modal-cobros-todos" class="hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="fixed inset-0 bg-black/50" onclick="cerrarModal('modal-cobros-todos')"></div>
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="relative bg-white rounded-xl shadow-lg w-full max-w-lg max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between px-6 py-4 border-b sticky top-0 bg-white rounded-t-xl">
                <h3 class="font-semibold text-gray-800 text-lg">
                    <i class="fas fa-hand-holding-dollar text-emerald-600 mr-2"></i>
                    Todos los cobros ({{ $tramite->cobros->count() }})
                </h3>
                <button type="button" onclick="cerrarModal('modal-cobros-todos')" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>
            <div class="divide-y divide-gray-100">
                @foreach($tramite->cobros as $cobro)
                <div class="px-5 py-3 hover:bg-gray-50 flex items-start gap-3">
                    <div class="flex-1 min-w-0">
                        <p class="font-medium text-sm text-gray-800">
                            {{ $cobro->gasto?->concepto ?? 'General' }}
                        </p>
                        <p class="text-xs text-gray-500">
                            {{ $cobro->fecha->format('d/m/Y') }} ·
                            <span class="uppercase">{{ $cobro->metodo_pago }}</span>
                            @if($cobro->usuario) · {{ $cobro->usuario->name }} @endif
                        </p>
                    </div>
                    <span class="text-sm font-semibold text-emerald-700 flex-shrink-0">{{ number_format($cobro->monto, 2) }} Bs</span>
                    <div class="flex gap-2 text-xs flex-shrink-0">
                        @if(auth()->user()->puede('gastos_cobros', 'cobrar'))
                        <button type="button" onclick="cerrarModal('modal-cobros-todos'); abrirModal('modal-cobro-editar-{{ $cobro->id }}')" class="text-gray-400 hover:text-brand-700" title="Editar">
                            <i class="fas fa-edit"></i>
                        </button>
                        @endif
                        @if(auth()->user()->puede('gastos_cobros', 'eliminar'))
                        <form method="POST" action="{{ route('cobros.destroy', $cobro) }}"
                              onsubmit="return confirm('¿Eliminar este cobro?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-gray-400 hover:text-red-600" title="Eliminar">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
            <div class="px-5 py-3 border-t bg-gray-50 flex justify-between items-center text-sm rounded-b-xl">
                <span class="text-gray-600 font-medium">Total</span>
                <span class="font-semibold text-emerald-700">{{ number_format($tramite->total_cobrado, 2) }} Bs</span>
            </div>
        </div>
    </div>
</div>

{{-- Modales flotantes: Editar Cobro --}}
@foreach($tramite->cobros as $cobro)
<div id="modal-cobro-editar-{{ $cobro->id }}" class="hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="fixed inset-0 bg-black/50" onclick="cerrarModal('modal-cobro-editar-{{ $cobro->id }}')"></div>
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="relative bg-white rounded-xl shadow-lg w-full max-w-lg max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between px-6 py-4 border-b sticky top-0 bg-white rounded-t-xl">
                <h3 class="font-semibold text-gray-800 text-lg">Editar Cobro</h3>
                <button type="button" onclick="cerrarModal('modal-cobro-editar-{{ $cobro->id }}')" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>
            <form method="POST" action="{{ route('cobros.update', $cobro) }}" class="p-6 space-y-4">
                @csrf @method('PUT')
                <input type="hidden" name="_modal" value="modal-cobro-editar-{{ $cobro->id }}">

                @php
                    $saldoGeneralCobro = $tramite->saldo_pendiente + (float) $cobro->monto;
                    $maxPorGastoOpcion = [];
                    foreach ($tramite->gastos as $gastoOpcion) {
                        $otrosCobrosDelGastoOpcion = $gastoOpcion->cobros->where('id', '!=', $cobro->id)->sum('monto');
                        $maxPorGastoOpcion[$gastoOpcion->id] = (float) $gastoOpcion->monto - (float) $otrosCobrosDelGastoOpcion;
                    }
                    $saldoDisponibleCobro = $cobro->gasto_id
                        ? ($maxPorGastoOpcion[$cobro->gasto_id] ?? $saldoGeneralCobro)
                        : $saldoGeneralCobro;
                @endphp

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Vincular a</label>
                    <select name="gasto_id" id="gasto_id-editar-{{ $cobro->id }}"
                            onchange="actualizarMaxCobro('{{ $cobro->id }}')"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                        <option value="" data-max="{{ $saldoGeneralCobro }}" {{ ! $cobro->gasto_id ? 'selected' : '' }}>
                            — General (no vinculado a un gasto puntual) —
                        </option>
                        @foreach($tramite->gastos as $gastoOpcion)
                            <option value="{{ $gastoOpcion->id }}" data-max="{{ $maxPorGastoOpcion[$gastoOpcion->id] }}"
                                    {{ $cobro->gasto_id == $gastoOpcion->id ? 'selected' : '' }}>
                                {{ $gastoOpcion->concepto }} ({{ number_format($gastoOpcion->monto, 2) }} Bs — {{ $gastoOpcion->fecha->format('d/m/Y') }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Monto *</label>
                        <input type="number" name="monto" id="monto-editar-{{ $cobro->id }}" value="{{ $cobro->monto }}" step="0.01" min="0.01"
                               max="{{ $saldoDisponibleCobro }}"
                               required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                        <p id="hint-max-editar-{{ $cobro->id }}" class="text-xs text-gray-400 mt-1">
                            Máximo: {{ number_format($saldoDisponibleCobro, 2) }} Bs
                        </p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Fecha *</label>
                        <input type="date" name="fecha" value="{{ $cobro->fecha->format('Y-m-d') }}" required
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Método de pago *</label>
                    <select name="metodo_pago" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                        <option value="efectivo" {{ $cobro->metodo_pago === 'efectivo' ? 'selected' : '' }}>Efectivo</option>
                        <option value="qr" {{ $cobro->metodo_pago === 'qr' ? 'selected' : '' }}>QR</option>
                    </select>
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="submit" class="bg-brand-600 hover:bg-brand-700 text-white px-6 py-2 rounded-lg font-medium">
                        <i class="fas fa-save mr-1"></i> Guardar cambios
                    </button>
                    <button type="button" onclick="cerrarModal('modal-cobro-editar-{{ $cobro->id }}')"
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
    function seleccionarTabPrincipal(tab) {
        document.querySelectorAll('.tab-panel-principal').forEach(function (panel) {
            panel.classList.add('hidden');
        });
        document.getElementById('tab-panel-' + tab).classList.remove('hidden');

        document.querySelectorAll('.tab-principal-btn').forEach(function (btn) {
            const activo = btn.dataset.tab === tab;
            btn.classList.toggle('bg-amber-600', activo && tab === 'gasto');
            btn.classList.toggle('border-amber-600', activo && tab === 'gasto');
            btn.classList.toggle('bg-emerald-600', activo && tab === 'cobro');
            btn.classList.toggle('border-emerald-600', activo && tab === 'cobro');
            btn.classList.toggle('text-white', activo);
            btn.classList.toggle('border-gray-300', !activo);
            btn.classList.toggle('text-gray-700', !activo);
        });
    }

    document.querySelectorAll('.tab-principal-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            seleccionarTabPrincipal(btn.dataset.tab);
        });
    });

    function seleccionarModoCobro(modo) {
        document.getElementById('modo').value = modo;

        document.querySelectorAll('.modo-panel').forEach(function (panel) {
            panel.classList.add('hidden');
        });
        document.getElementById('panel-' + modo).classList.remove('hidden');

        document.querySelectorAll('.modo-btn').forEach(function (btn) {
            const activo = btn.dataset.modo === modo;
            btn.classList.toggle('bg-brand-600', activo);
            btn.classList.toggle('text-white', activo);
            btn.classList.toggle('border-brand-600', activo);
            btn.classList.toggle('border-gray-300', !activo);
            btn.classList.toggle('text-gray-700', !activo);
        });
    }

    document.querySelectorAll('.modo-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            seleccionarModoCobro(btn.dataset.modo);
        });
    });

    function enviarInformeCobroWhatsapp() {
        const form = document.getElementById('form-cobro-nuevo');
        const telefono = form.dataset.clienteWhatsapp;
        if (!telefono) return;

        const modo = document.getElementById('modo').value;
        const fecha = document.getElementById('input-fecha-cobro').value;
        const fechaTexto = fecha ? fecha.split('-').reverse().join('/') : '';

        let detalle = '';
        if (modo === 'abono') {
            const monto = parseFloat(document.getElementById('input-monto-abono').value || '0');
            detalle = `Te solicitamos el pago de un abono de: ${monto.toFixed(2)} Bs`;
        } else {
            const saldo = parseFloat(form.dataset.saldoPendiente || '0');
            detalle = `Te solicitamos el pago del saldo pendiente de tu trámite: ${saldo.toFixed(2)} Bs`;
        }

        const texto = `Hola ${form.dataset.clienteNombre}, te escribimos de Vidal Escalante & Asociados respecto a tu trámite `
            + `${form.dataset.tramiteCodigo} (${form.dataset.tramiteNombre}).\n\n`
            + `${detalle}\n\n`
            + `Fecha: ${fechaTexto}\n\n`
            + `Quedamos atentos a la confirmación del pago. Gracias por tu confianza.`;

        window.open('https://wa.me/' + telefono + '?text=' + encodeURIComponent(texto), '_blank');
    }

    function actualizarMaxCobro(cobroId) {
        const select = document.getElementById('gasto_id-editar-' + cobroId);
        const input = document.getElementById('monto-editar-' + cobroId);
        const hint = document.getElementById('hint-max-editar-' + cobroId);
        if (!select || !input || !hint) return;

        const max = parseFloat(select.options[select.selectedIndex].dataset.max);
        input.max = max;
        hint.textContent = 'Máximo: $' + max.toFixed(2);
    }

    let gastoActuacionContador = 0;

    function agregarGastoActuacionTramite(concepto, monto) {
        const contenedor = document.getElementById('gastos-filas-seguimiento-tramite');
        if (!contenedor) return;

        const idx = gastoActuacionContador++;
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
        contenedor.appendChild(fila);
    }

    @if(old('gastos') && old('_modal') === 'modal-seguimiento-nuevo')
        @foreach(old('gastos') as $g)
            agregarGastoActuacionTramite(@json($g['concepto'] ?? ''), @json($g['monto'] ?? ''));
        @endforeach
    @endif

    (function () {
        const form = document.getElementById('form-seguimiento-nuevo-tramite');
        if (!form) return;

        form.addEventListener('submit', function (e) {
            const checkbox = form.querySelector('input[name=notificar_cliente]');
            const telefono = form.dataset.clienteWhatsapp;
            if (!checkbox || !checkbox.checked || form.dataset.whatsappConfirmado === '1' || !telefono) {
                return;
            }

            e.preventDefault();

            const selectTipo = document.getElementById('select-tipo-actuacion-tramite');
            const tipoTexto = selectTipo && selectTipo.selectedIndex > -1
                ? selectTipo.options[selectTipo.selectedIndex].text.trim()
                : 'una actuación';
            const observaciones = form.querySelector('textarea[name=observaciones]').value.trim();

            let texto = `Hola ${form.dataset.clienteNombre}, se registró una nueva actuación en tu caso: "${tipoTexto}".`;
            if (observaciones) {
                texto += `\n\n${observaciones}`;
            }

            mostrarPreviaWhatsapp(form, texto, telefono);
        });
    })();

    function crearTipoActuacionRapidoTramite() {
        const input = document.getElementById('input-tipo-actuacion-rapido-nombre-tramite');
        const error = document.getElementById('error-tipo-actuacion-rapido-tramite');
        const nombre = input.value.trim();

        error.classList.add('hidden');

        if (!nombre) {
            error.textContent = 'El nombre es obligatorio.';
            error.classList.remove('hidden');
            return;
        }

        fetch('{{ route('parametros.tipos-actuacion.store') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({ nombre }),
        })
            .then(async (res) => {
                const body = await res.json();
                if (!res.ok) {
                    throw new Error(body.errors?.nombre?.[0] ?? 'No se pudo crear el tipo de actuación.');
                }
                return body;
            })
            .then((tipo) => {
                const select = document.getElementById('select-tipo-actuacion-tramite');
                const opcion = new Option(tipo.nombre, tipo.id, true, true);
                select.add(opcion);
                input.value = '';
                cerrarModal('modal-tipo-actuacion-rapido-tramite');
            })
            .catch((err) => {
                error.textContent = err.message;
                error.classList.remove('hidden');
            });
    }

    function crearTipoDocumentoRapidoTramite() {
        const input = document.getElementById('input-tipo-documento-rapido-nombre-tramite');
        const error = document.getElementById('error-tipo-documento-rapido-tramite');
        const nombre = input.value.trim();

        error.classList.add('hidden');

        if (!nombre) {
            error.textContent = 'El nombre es obligatorio.';
            error.classList.remove('hidden');
            return;
        }

        fetch('{{ route('parametros.tipos-documento.store') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({ nombre }),
        })
            .then(async (res) => {
                const body = await res.json();
                if (!res.ok) {
                    throw new Error(body.errors?.nombre?.[0] ?? 'No se pudo crear el tipo de documento.');
                }
                return body;
            })
            .then((tipo) => {
                const select = document.getElementById('select-tipo-documento-tramite');
                const opcion = new Option(tipo.nombre, tipo.id, true, true);
                select.add(opcion);
                input.value = '';
                cerrarModal('modal-tipo-documento-rapido-tramite');
            })
            .catch((err) => {
                error.textContent = err.message;
                error.classList.remove('hidden');
            });
    }

    function crearTipoGastoRapidoTramite() {
        const input = document.getElementById('input-tipo-gasto-rapido-nombre-tramite');
        const error = document.getElementById('error-tipo-gasto-rapido-tramite');
        const nombre = input.value.trim();

        error.classList.add('hidden');

        if (!nombre) {
            error.textContent = 'El nombre es obligatorio.';
            error.classList.remove('hidden');
            return;
        }

        fetch('{{ route('parametros.tipos-gasto.store') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({ nombre }),
        })
            .then(async (res) => {
                const body = await res.json();
                if (!res.ok) {
                    throw new Error(body.errors?.nombre?.[0] ?? 'No se pudo crear el tipo de gasto.');
                }
                return body;
            })
            .then((tipo) => {
                const select = document.getElementById('select-tipo-gasto-tramite');
                const opcion = new Option(tipo.nombre, tipo.id, true, true);
                select.add(opcion);
                input.value = '';
                cerrarModal('modal-tipo-gasto-rapido-tramite');
            })
            .catch((err) => {
                error.textContent = err.message;
                error.classList.remove('hidden');
            });
    }

    @if($errors->any() && old('_modal'))
        abrirModal(@json(old('_modal')));
        @if(old('_modal') === 'modal-gasto-cobro-nuevo' && old('modo'))
            seleccionarTabPrincipal('cobro');
            seleccionarModoCobro(@json(old('modo')));
        @endif
    @endif
</script>
@endpush
@endsection
