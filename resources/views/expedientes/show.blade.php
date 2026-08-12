@extends('layouts.app')

@section('title', 'Expediente ' . $expediente->caratula)
@section('header', $expediente->caratula . ' — ' . Str::limit($expediente->numero, 40))

@section('header-actions')
    @if(auth()->user()->puede('seguimientos', 'crear'))
    <button type="button" onclick="abrirModal('modal-seguimiento-nuevo')"
       class="bg-green-600 hover:bg-green-700 text-white px-3 py-2 rounded-lg text-sm flex items-center gap-2">
        <i class="fas fa-plus"></i> Seguimiento
    </button>
    @endif
    @if(auth()->user()->puede('audiencias', 'crear'))
    <button type="button" onclick="abrirModal('modal-audiencia-nuevo')"
       class="bg-purple-600 hover:bg-purple-700 text-white px-3 py-2 rounded-lg text-sm flex items-center gap-2">
        <i class="fas fa-gavel"></i> Audiencia
    </button>
    @endif
    @if(auth()->user()->puede('gastos_cobros', 'crear') || auth()->user()->puede('gastos_cobros', 'cobrar'))
    <button type="button" onclick="abrirModal('modal-gasto-cobro-nuevo')"
       class="bg-amber-600 hover:bg-amber-700 text-white px-3 py-2 rounded-lg text-sm flex items-center gap-2">
        <i class="fas fa-coins"></i> Gasto / Cobro
    </button>
    @endif
    <a href="{{ route('expedientes.reporte', $expediente) }}" target="_blank"
       class="bg-white border hover:bg-gray-50 text-gray-700 px-3 py-2 rounded-lg text-sm flex items-center gap-2">
        <i class="fas fa-file-invoice"></i> Reporte
    </a>
    @if(auth()->user()->puede('expedientes', 'modificar'))
    <a href="{{ route('expedientes.index', ['editar' => $expediente->id, 'buscar' => $expediente->numero]) }}"
       class="bg-white border hover:bg-gray-50 text-gray-700 px-3 py-2 rounded-lg text-sm flex items-center gap-2">
        <i class="fas fa-edit"></i> Editar
    </a>
    @endif
@endsection

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- Columna izquierda: datos del expediente --}}
    <div class="space-y-4">
        <div class="bg-white rounded-xl shadow-sm p-5">
            <h3 class="font-semibold text-gray-700 mb-3">Datos del Expediente</h3>
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <dt class="text-gray-500">NUREJ</dt>
                    <dd class="font-mono font-medium">{{ $expediente->numero }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500">Estado</dt>
                    <dd><span class="px-2 py-0.5 rounded text-xs font-medium bg-{{ $expediente->estado_color }}-100 text-{{ $expediente->estado_color }}-700">{{ $expediente->estado_label }}</span></dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500">Materia</dt>
                    <dd>{{ ucfirst($expediente->tipo_causa) }}</dd>
                </div>
                @if($expediente->tipo_proceso)
                <div class="flex justify-between">
                    <dt class="text-gray-500">Tipo de proceso</dt>
                    <dd class="text-right">{{ $expediente->tipo_proceso }}</dd>
                </div>
                @endif
                @if($expediente->procedimiento)
                <div class="flex justify-between">
                    <dt class="text-gray-500">Procedimiento</dt>
                    <dd class="text-right">{{ $expediente->procedimiento }}</dd>
                </div>
                @endif
                <div class="flex justify-between">
                    <dt class="text-gray-500">Lugar asignado en el reparto</dt>
                    <dd class="text-right">{{ $expediente->juzgado ?? '—' }}</dd>
                </div>
                @if($expediente->fecha_recepcion)
                <div class="flex justify-between">
                    <dt class="text-gray-500">Fecha de recepción</dt>
                    <dd>{{ $expediente->fecha_recepcion->format('d/m/Y') }}</dd>
                </div>
                @endif
                @if($expediente->monto_reclamado)
                <div class="flex justify-between">
                    <dt class="text-gray-500">Monto reclamado</dt>
                    <dd class="font-medium">{{ number_format($expediente->monto_reclamado, 2) }} Bs</dd>
                </div>
                @endif
                <div class="flex justify-between">
                    <dt class="text-gray-500">Abogado</dt>
                    <dd>{{ $expediente->abogado?->nombre ?? '—' }}</dd>
                </div>

                <div class="pt-2 border-t space-y-1.5">
                    <div class="flex justify-between items-center">
                        <dt class="text-gray-500">Total gastado</dt>
                        <dd class="font-medium text-amber-700">{{ number_format($expediente->total_gastos, 2) }} Bs</dd>
                    </div>
                    <div class="flex justify-between items-center">
                        <dt class="text-gray-500">Total cobrado</dt>
                        <dd class="font-medium text-emerald-700">{{ number_format($expediente->total_cobrado, 2) }} Bs</dd>
                    </div>
                    <div class="flex justify-between items-center pt-1.5 border-t">
                        <dt class="text-gray-600 font-medium">Saldo pendiente</dt>
                        <dd class="font-semibold {{ $expediente->saldo_pendiente > 0 ? 'text-red-600' : 'text-gray-800' }}">
                            {{ number_format($expediente->saldo_pendiente, 2) }} Bs
                        </dd>
                    </div>
                    @if($expediente->estado_pago !== 'sin_gastos')
                    <div class="flex justify-between items-center">
                        <dt class="text-gray-600 font-medium">Estado de la cuenta</dt>
                        <dd>
                            <span class="px-2 py-0.5 rounded text-xs font-medium bg-{{ $expediente->estado_pago_color }}-100 text-{{ $expediente->estado_pago_color }}-700">
                                {{ $expediente->estado_pago_label }}
                            </span>
                        </dd>
                    </div>
                    @endif
                </div>
            </dl>
        </div>

        {{-- Cliente + Partes del proceso --}}
        <div class="bg-white rounded-xl shadow-sm p-5">
            <h3 class="font-semibold text-gray-700 mb-3">Cliente</h3>
            @if(auth()->user()->puede('clientes', 'ver'))
            <a href="{{ route('clientes.show', $expediente->cliente) }}"
               class="flex items-center gap-3 hover:bg-gray-50 -mx-2 px-2 py-2 rounded-lg transition">
                <div class="w-10 h-10 bg-brand-100 text-brand-700 rounded-full flex items-center justify-center font-bold">
                    {{ strtoupper(substr($expediente->cliente->nombre_completo, 0, 1)) }}
                </div>
                <div>
                    <p class="font-medium text-gray-800">{{ $expediente->cliente->nombre_completo }}</p>
                    <p class="text-xs text-gray-500">{{ $expediente->cliente->email }}</p>
                    <p class="text-xs text-gray-500">{{ $expediente->cliente->telefono }}</p>
                </div>
            </a>
            @else
            <div class="flex items-center gap-3 -mx-2 px-2 py-2">
                <div class="w-10 h-10 bg-brand-100 text-brand-700 rounded-full flex items-center justify-center font-bold">
                    {{ strtoupper(substr($expediente->cliente->nombre_completo, 0, 1)) }}
                </div>
                <div>
                    <p class="font-medium text-gray-800">{{ $expediente->cliente->nombre_completo }}</p>
                    <p class="text-xs text-gray-500">{{ $expediente->cliente->email }}</p>
                    <p class="text-xs text-gray-500">{{ $expediente->cliente->telefono }}</p>
                </div>
            </div>
            @endif

            @if($expediente->cliente->tipo === 'persona_juridica' && ($expediente->representante_cliente || !empty($expediente->abogados_cliente)))
            <div class="mt-3 pt-3 border-t text-sm">
                @if($expediente->representante_cliente)
                <p><span class="text-gray-500">Representante:</span> {{ $expediente->representante_cliente }}</p>
                @endif
                @if(!empty($expediente->abogados_cliente))
                <p class="text-gray-500 mt-1">Abogados:</p>
                <ul class="list-disc list-inside text-gray-700">
                    @foreach($expediente->abogados_cliente as $abogado)
                        <li>{{ $abogado }}</li>
                    @endforeach
                </ul>
                @endif
            </div>
            @endif

            @if($expediente->partes->isNotEmpty())
            <div class="border-t my-4"></div>
            @if($expediente->demandantes->isNotEmpty())
            <div class="mb-3">
                <p class="text-xs font-semibold text-gray-500 uppercase mb-1">Demandantes</p>
                <ul class="space-y-2">
                    @foreach($expediente->demandantes as $d)
                    <li class="text-sm border rounded-lg p-2">
                        <p class="font-medium text-gray-800">{{ $d->nombre }}</p>
                        @if($d->representante)
                            <p class="text-xs text-gray-500">Representante: {{ $d->representante }}</p>
                        @endif
                        @if($d->abogados_registrados)
                            <p class="text-xs text-gray-500">Abog. Reg.: {{ $d->abogados_registrados }}</p>
                        @endif
                    </li>
                    @endforeach
                </ul>
            </div>
            @endif
            @if($expediente->demandados->isNotEmpty())
            <div>
                <p class="text-xs font-semibold text-gray-500 uppercase mb-1">Demandados</p>
                <ul class="space-y-2">
                    @foreach($expediente->demandados as $d)
                    <li class="text-sm border rounded-lg p-2">
                        <p class="font-medium text-gray-800">{{ $d->nombre }}</p>
                        @if($d->representante)
                            <p class="text-xs text-gray-500">Representante: {{ $d->representante }}</p>
                        @endif
                        @if($d->abogados_registrados)
                            <p class="text-xs text-gray-500">Abog. Reg.: {{ $d->abogados_registrados }}</p>
                        @endif
                    </li>
                    @endforeach
                </ul>
            </div>
            @endif
            @endif
        </div>
    </div>

    {{-- Columna central y derecha: seguimientos y audiencias --}}
    <div class="col-span-2 space-y-6">

        {{-- Seguimientos --}}
        @include('partials._actuaciones', ['seguimientos' => $expediente->seguimientos])

        {{-- Actualizaciones del caso --}}
        @if(auth()->user()->puede('actualizaciones', 'ver'))
        <div class="bg-white rounded-xl shadow-sm">
            <div class="flex items-center justify-between p-5 border-b">
                <h3 class="font-semibold text-gray-700">
                    <i class="fas fa-comment-dots text-blue-500 mr-2"></i>
                    Actualizaciones del caso ({{ $expediente->actualizaciones->count() }})
                </h3>
                <a href="{{ route('expedientes.actualizaciones.index', $expediente) }}" class="text-xs text-brand-700 hover:underline">
                    Ver todo
                </a>
            </div>
            @if(auth()->user()->puede('actualizaciones', 'crear'))
            <form method="POST" action="{{ route('expedientes.actualizaciones.store', $expediente) }}" class="p-5 border-b flex gap-2 items-start">
                @csrf
                <textarea name="texto" required rows="2" placeholder="Escribí una novedad del caso (ej. estado en el juzgado, próximos pasos)..."
                          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400"></textarea>
                <button type="submit" class="shrink-0 bg-brand-600 hover:bg-brand-700 text-white px-4 py-2 rounded-lg text-sm font-medium">
                    Agregar
                </button>
            </form>
            @endif
        </div>
        @endif

        {{-- Audiencias --}}
        <div class="bg-white rounded-xl shadow-sm">
            <div class="flex items-center justify-between p-5 border-b">
                <h3 class="font-semibold text-gray-700">
                    <i class="fas fa-gavel text-purple-600 mr-2"></i>
                    Audiencias ({{ $expediente->audiencias->count() }})
                </h3>
                @if(auth()->user()->puede('audiencias', 'crear'))
                <button type="button" onclick="abrirModal('modal-audiencia-nuevo')"
                        class="text-xs text-purple-600 hover:underline">+ Nueva audiencia</button>
                @endif
            </div>
            <div class="divide-y divide-gray-100">
                @forelse($expediente->audiencias as $aud)
                <div class="px-5 py-3 hover:bg-gray-50 flex items-center gap-4">
                    <div class="text-center w-14 flex-shrink-0">
                        <p class="text-lg font-bold text-gray-800">{{ $aud->fecha_hora->format('d') }}</p>
                        <p class="text-xs text-gray-500 uppercase">{{ $aud->fecha_hora->format('M') }}</p>
                    </div>
                    <div class="flex-1">
                        <p class="font-medium text-sm text-gray-800">{{ $aud->titulo }}</p>
                        <p class="text-xs text-gray-500">
                            {{ $aud->fecha_hora->format('H:i') }}h · {{ $aud->lugar ?? 'Sin lugar' }}
                        </p>
                    </div>
                    <span class="px-2 py-1 rounded text-xs font-medium
                        bg-{{ $aud->estado_color }}-100 text-{{ $aud->estado_color }}-700">
                        {{ ucfirst($aud->estado) }}
                    </span>
                    @if(auth()->user()->puede('audiencias', 'modificar'))
                        @if(!in_array($aud->estado, ['realizada', 'cancelada']))
                            <form method="POST" action="{{ route('audiencias.finalizar', $aud) }}"
                                  onsubmit="return confirm('¿Marcar esta audiencia como realizada?')">
                                @csrf @method('PATCH')
                                <button type="submit" class="text-gray-400 hover:text-green-600" title="Marcar como realizada">
                                    <i class="fas fa-check-circle"></i>
                                </button>
                            </form>
                        @endif
                        <a href="{{ route('audiencias.index', ['editar' => $aud->id, 'id' => $aud->id]) }}" class="text-gray-400 hover:text-brand-700" title="Editar">
                            <i class="fas fa-edit"></i>
                        </a>
                    @endif
                </div>
                @empty
                <p class="p-5 text-sm text-gray-400 text-center">Sin audiencias registradas.</p>
                @endforelse
            </div>
        </div>

        {{-- Documentos: cargados directo contra el expediente, sin pasar por un seguimiento --}}
        <div class="bg-white rounded-xl shadow-sm">
            <div class="flex items-center justify-between p-5 border-b flex-wrap gap-2">
                <h3 class="font-semibold text-gray-700">
                    <i class="fas fa-folder-open text-brand-600 mr-2"></i>
                    Documentos ({{ $expediente->documentos->count() }})
                </h3>
                <div class="flex items-center gap-3">
                    @if($expediente->seguimientos->whereNotNull('archivo_adjunto')->isNotEmpty() && auth()->user()->puede('documentos', 'descargar'))
                    <a href="{{ route('expedientes.documentos.pdf', $expediente) }}"
                       class="text-xs text-brand-600 hover:underline font-medium flex items-center gap-1">
                        <i class="fas fa-file-pdf"></i> Descargar expediente en PDF
                    </a>
                    <a href="{{ route('expedientes.documentos.zip', $expediente) }}"
                       class="text-xs text-brand-600 hover:underline font-medium flex items-center gap-1">
                        <i class="fas fa-file-zipper"></i> Exportar ZIP
                    </a>
                    @endif
                    @if(auth()->user()->puede('documentos', 'crear'))
                    <button type="button" onclick="abrirModal('modal-documento-nuevo')"
                            class="text-xs text-green-600 hover:underline font-medium flex items-center gap-1">
                        <i class="fas fa-plus"></i> Nuevo documento
                    </button>
                    @endif
                </div>
            </div>
            <div class="divide-y divide-gray-100">
                @forelse($expediente->documentos as $doc)
                <div class="px-5 py-3 hover:bg-gray-50 flex items-center gap-3">
                    <div class="w-9 h-9 rounded-lg bg-amber-100 text-amber-600 flex items-center justify-center flex-shrink-0">
                        <i class="fas {{ $doc->icono_archivo }}"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="font-medium text-sm text-gray-800 truncate">{{ $doc->titulo }}</p>
                        <p class="text-xs text-gray-500">
                            {{ $doc->created_at->format('d/m/Y') }}
                            @if($doc->fojas) · Fs. {{ $doc->fojas }} @endif
                        </p>
                    </div>
                    <div class="flex gap-3 text-xs flex-shrink-0">
                        @if(auth()->user()->puede('documentos', 'ver'))
                        <a href="{{ route('documentos.archivo.ver', $doc) }}" target="_blank"
                           class="text-gray-400 hover:text-brand-700" title="Ver">
                            <i class="fas fa-eye"></i>
                        </a>
                        @endif
                        @if(auth()->user()->puede('documentos', 'descargar'))
                        <a href="{{ route('documentos.archivo.descargar', $doc) }}"
                           class="text-gray-400 hover:text-brand-700" title="Descargar">
                            <i class="fas fa-download"></i>
                        </a>
                        @endif
                        @if(auth()->user()->puede('documentos', 'eliminar'))
                        <form method="POST" action="{{ route('documentos.archivo.eliminar', $doc) }}"
                              onsubmit="return confirm('¿Eliminar este documento?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-gray-400 hover:text-red-600" title="Eliminar">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                        @endif
                    </div>
                </div>
                @empty
                <p class="p-5 text-sm text-gray-400 text-center">Sin documentos subidos.</p>
                @endforelse
            </div>
        </div>


    </div>

{{-- Modal flotante: Nuevo documento (independiente de los seguimientos) --}}
<div id="modal-documento-nuevo" class="hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="fixed inset-0 bg-black/50" onclick="cerrarModal('modal-documento-nuevo')"></div>
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="relative bg-white rounded-xl shadow-lg w-full max-w-lg max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between px-6 py-4 border-b sticky top-0 bg-white rounded-t-xl">
                <h3 class="font-semibold text-gray-800 text-lg">Nuevo documento</h3>
                <button type="button" onclick="cerrarModal('modal-documento-nuevo')" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>
            <form method="POST" action="{{ route('documentos.store') }}" enctype="multipart/form-data" class="p-6 space-y-4">
                @csrf
                <input type="hidden" name="_modal" value="modal-documento-nuevo">
                <input type="hidden" name="expediente_id" value="{{ $expediente->id }}">

                <p class="text-sm text-gray-500 -mt-2">
                    Expediente: <span class="font-medium text-gray-700">{{ $expediente->caratula }} — {{ $expediente->cliente->nombre_completo }}</span>
                </p>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Título del documento *</label>
                    <input type="text" name="titulo" value="{{ old('titulo') }}" required
                           placeholder="Ej: Memorial de contestación"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fojas</label>
                    <input type="text" name="fojas" value="{{ old('fojas') }}"
                           placeholder="Ej: 1-15"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Archivo *</label>
                    <input type="file" name="archivo" required
                           accept=".pdf,.doc,.docx,.jpg,.png"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                    <p class="text-xs text-gray-400 mt-1">PDF, Word o imagen. Máx. 10MB.</p>
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="submit" class="bg-brand-600 hover:bg-brand-700 text-white px-6 py-2 rounded-lg font-medium">
                        <i class="fas fa-save mr-1"></i> Cargar documento
                    </button>
                    <button type="button" onclick="cerrarModal('modal-documento-nuevo')"
                            class="text-gray-600 hover:text-gray-800 px-4 py-2 rounded-lg border hover:bg-gray-50">
                        Cancelar
                    </button>
                </div>
            </form>
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
                  id="form-seguimiento-nuevo-expediente" data-cliente-nombre="{{ $expediente->cliente->nombre_completo }}"
                  data-cliente-whatsapp="{{ $expediente->cliente->telefono_whatsapp }}">
                @csrf
                <input type="hidden" name="_modal" value="modal-seguimiento-nuevo">
                <input type="hidden" name="expediente_id" value="{{ $expediente->id }}">

                <p class="text-sm text-gray-500 -mt-2">
                    Expediente: <span class="font-medium text-gray-700">{{ $expediente->caratula }} — {{ $expediente->cliente->nombre_completo }}</span>
                </p>

                @if($expediente->demandados->isNotEmpty())
                <div class="flex flex-wrap items-center gap-1.5 -mt-1">
                    <span class="text-xs font-medium text-gray-500">Demandados:</span>
                    @foreach($expediente->demandados as $demandado)
                        <span class="text-xs font-medium text-gray-700 bg-gray-100 border border-gray-200 rounded-full px-2.5 py-1">{{ $demandado->nombre }}</span>
                    @endforeach
                </div>
                @endif

                <input type="hidden" name="prioridad" value="media">

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de Acción *</label>
                    <div class="flex gap-2">
                        <select name="tipo_actuacion_id" id="select-tipo-actuacion-expediente" required
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                            <option value="">— Seleccioná un tipo —</option>
                            @foreach($tiposActuacion as $t)
                                <option value="{{ $t->id }}" {{ old('tipo_actuacion_id') == $t->id ? 'selected' : '' }}>{{ $t->nombre }}</option>
                            @endforeach
                        </select>
                        @if(auth()->user()->puede('parametros', 'crear'))
                        <button type="button" onclick="abrirModal('modal-tipo-actuacion-rapido-expediente')" title="Nuevo tipo de actuación"
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
                    <input type="checkbox" name="requiere_respuesta" id="requiere_respuesta-seguimiento-expediente" value="1"
                           {{ old('requiere_respuesta') ? 'checked' : '' }}
                           class="w-4 h-4 text-brand-700 rounded">
                    <label for="requiere_respuesta-seguimiento-expediente" class="text-sm text-gray-700">
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
                        <button type="button" onclick="agregarGastoActuacionExpediente()"
                                class="text-xs text-brand-700 hover:underline">+ Agregar gasto</button>
                    </div>
                    <div id="gastos-filas-seguimiento-expediente" class="space-y-2"></div>
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

{{-- Modal flotante: Nueva Audiencia --}}
<div id="modal-audiencia-nuevo" class="hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="fixed inset-0 bg-black/50" onclick="cerrarModal('modal-audiencia-nuevo')"></div>
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="relative bg-white rounded-xl shadow-lg w-full max-w-2xl max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between px-6 py-4 border-b sticky top-0 bg-white rounded-t-xl">
                <h3 class="font-semibold text-gray-800 text-lg">Nueva Audiencia</h3>
                <button type="button" onclick="cerrarModal('modal-audiencia-nuevo')" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>
            <form method="POST" action="{{ route('audiencias.store') }}" class="p-6 space-y-4" id="form-audiencia-nuevo-expediente">
                @csrf
                <input type="hidden" name="_modal" value="modal-audiencia-nuevo">
                <input type="hidden" name="expediente_id" value="{{ $expediente->id }}">

                <p class="text-sm text-gray-500 -mt-2">
                    Expediente: <span class="font-medium text-gray-700">{{ $expediente->numero }} — {{ $expediente->cliente->nombre_completo }}</span>
                </p>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Título *</label>
                    <input type="text" name="titulo" value="{{ old('titulo') }}" required
                           placeholder="Ej: Audiencia preliminar"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                    @error('titulo')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tipo *</label>
                        <div class="flex gap-2">
                            <select name="tipo_audiencia_id" id="select-tipo-audiencia-expediente" required
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                                <option value="">— Seleccioná un tipo —</option>
                                @foreach($tiposAudiencia as $t)
                                    <option value="{{ $t->id }}" {{ old('tipo_audiencia_id') == $t->id ? 'selected' : '' }}>{{ $t->nombre }}</option>
                                @endforeach
                            </select>
                            @if(auth()->user()->puede('parametros', 'crear'))
                            <button type="button" onclick="abrirModal('modal-tipo-audiencia-rapido-expediente')" title="Nuevo tipo de audiencia"
                                    class="shrink-0 border border-gray-300 rounded-lg w-9 h-9 flex items-center justify-center text-gray-500 hover:text-brand-700 hover:border-brand-400">
                                <i class="fas fa-plus"></i>
                            </button>
                            @endif
                        </div>
                        @error('tipo_audiencia_id')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Estado *</label>
                        <select name="estado" required
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                            @foreach(['programada', 'confirmada', 'realizada', 'suspendida', 'reprogramada', 'cancelada'] as $e)
                                <option value="{{ $e }}" {{ old('estado', 'programada') === $e ? 'selected' : '' }}>{{ ucfirst($e) }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Fecha y hora *</label>
                        <input type="datetime-local" name="fecha_hora" value="{{ old('fecha_hora') }}" required
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Modalidad *</label>
                        <select name="modalidad" id="select-modalidad-expediente" required
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                            <option value="presencial" {{ old('modalidad', 'presencial') === 'presencial' ? 'selected' : '' }}>Presencial</option>
                            <option value="virtual" {{ old('modalidad') === 'virtual' ? 'selected' : '' }}>Virtual</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4" id="campos-presencial-expediente">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Lugar</label>
                        <input type="text" name="lugar" value="{{ old('lugar') }}"
                               placeholder="Ej: Tribunales Civiles, Edificio A"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Sala</label>
                        <input type="text" name="sala" value="{{ old('sala') }}" placeholder="Ej: Sala 3"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Abogado responsable</label>
                    <div class="flex gap-2">
                        <select name="abogado_id" id="select-abogado-audiencia-expediente"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                            <option value="">— Sin asignar —</option>
                            @foreach($abogados as $a)
                                <option value="{{ $a->id }}" {{ old('abogado_id') == $a->id ? 'selected' : '' }}>{{ $a->nombre }}</option>
                            @endforeach
                        </select>
                        @if(auth()->user()->puede('abogados', 'crear'))
                        <button type="button" title="Nuevo abogado"
                                onclick="abrirAltaRapida({titulo: 'Nuevo abogado', etiqueta: 'Nombre', placeholder: 'Ej: Juan Pérez', url: '{{ route('abogados.store') }}', selectId: 'select-abogado-audiencia-expediente'})"
                                class="shrink-0 border border-gray-300 rounded-lg w-9 h-9 flex items-center justify-center text-gray-500 hover:text-brand-700 hover:border-brand-400">
                            <i class="fas fa-plus"></i>
                        </button>
                        @endif
                    </div>
                </div>

                <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                    <input type="checkbox" name="notificar_cliente" value="1" {{ old('notificar_cliente') ? 'checked' : '' }}>
                    <i class="fab fa-whatsapp text-emerald-600"></i>
                    Notificar al cliente por WhatsApp
                </label>

                <div class="flex gap-3 pt-2">
                    <button type="submit" class="bg-brand-600 hover:bg-brand-700 text-white px-6 py-2 rounded-lg font-medium">
                        <i class="fas fa-save mr-1"></i> Registrar Audiencia
                    </button>
                    <button type="button" onclick="cerrarModal('modal-audiencia-nuevo')"
                            class="text-gray-600 hover:text-gray-800 px-4 py-2 rounded-lg border hover:bg-gray-50">
                        Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal flotante: alta rápida de tipo de audiencia --}}
<div id="modal-tipo-audiencia-rapido-expediente" class="hidden fixed inset-0 z-[60] overflow-y-auto">
    <div class="fixed inset-0 bg-black/50" onclick="cerrarModal('modal-tipo-audiencia-rapido-expediente')"></div>
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="relative bg-white rounded-xl shadow-lg w-full max-w-sm">
            <div class="flex items-center justify-between px-6 py-4 border-b">
                <h3 class="font-semibold text-gray-800 text-lg">Nuevo tipo de audiencia</h3>
                <button type="button" onclick="cerrarModal('modal-tipo-audiencia-rapido-expediente')" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>
            <div class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nombre *</label>
                    <input type="text" id="input-tipo-audiencia-rapido-nombre-expediente" placeholder="Ej: Audiencia de conciliación"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                    <p id="error-tipo-audiencia-rapido-expediente" class="text-xs text-red-600 mt-1 hidden"></p>
                </div>
                <div class="flex gap-3">
                    <button type="button" onclick="crearTipoAudienciaRapidoExpediente()"
                            class="bg-brand-600 hover:bg-brand-700 text-white px-4 py-2 rounded-lg text-sm font-medium">
                        Guardar
                    </button>
                    <button type="button" onclick="cerrarModal('modal-tipo-audiencia-rapido-expediente')"
                            class="text-gray-600 hover:text-gray-800 px-4 py-2 rounded-lg border hover:bg-gray-50 text-sm">
                        Cancelar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modal flotante: alta rápida de tipo de gasto --}}
<div id="modal-tipo-gasto-rapido-expediente" class="hidden fixed inset-0 z-[60] overflow-y-auto">
    <div class="fixed inset-0 bg-black/50" onclick="cerrarModal('modal-tipo-gasto-rapido-expediente')"></div>
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="relative bg-white rounded-xl shadow-lg w-full max-w-sm">
            <div class="flex items-center justify-between px-6 py-4 border-b">
                <h3 class="font-semibold text-gray-800 text-lg">Nuevo tipo de gasto</h3>
                <button type="button" onclick="cerrarModal('modal-tipo-gasto-rapido-expediente')" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>
            <div class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nombre *</label>
                    <input type="text" id="input-tipo-gasto-rapido-nombre-expediente" placeholder="Ej: Arancel de inscripción"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                    <p id="error-tipo-gasto-rapido-expediente" class="text-xs text-red-600 mt-1 hidden"></p>
                </div>
                <div class="flex gap-3">
                    <button type="button" onclick="crearTipoGastoRapidoExpediente()"
                            class="bg-brand-600 hover:bg-brand-700 text-white px-4 py-2 rounded-lg text-sm font-medium">
                        Guardar
                    </button>
                    <button type="button" onclick="cerrarModal('modal-tipo-gasto-rapido-expediente')"
                            class="text-gray-600 hover:text-gray-800 px-4 py-2 rounded-lg border hover:bg-gray-50 text-sm">
                        Cancelar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modal flotante: alta rápida de tipo de actuación --}}
<div id="modal-tipo-actuacion-rapido-expediente" class="hidden fixed inset-0 z-[60] overflow-y-auto">
    <div class="fixed inset-0 bg-black/50" onclick="cerrarModal('modal-tipo-actuacion-rapido-expediente')"></div>
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="relative bg-white rounded-xl shadow-lg w-full max-w-sm">
            <div class="flex items-center justify-between px-6 py-4 border-b">
                <h3 class="font-semibold text-gray-800 text-lg">Nuevo tipo de actuación</h3>
                <button type="button" onclick="cerrarModal('modal-tipo-actuacion-rapido-expediente')" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>
            <div class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nombre *</label>
                    <input type="text" id="input-tipo-actuacion-rapido-nombre-expediente" placeholder="Ej: Presentación de escrito"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                    <p id="error-tipo-actuacion-rapido-expediente" class="text-xs text-red-600 mt-1 hidden"></p>
                </div>
                <div class="flex gap-3">
                    <button type="button" onclick="crearTipoActuacionRapidoExpediente()"
                            class="bg-brand-600 hover:bg-brand-700 text-white px-4 py-2 rounded-lg text-sm font-medium">
                        Guardar
                    </button>
                    <button type="button" onclick="cerrarModal('modal-tipo-actuacion-rapido-expediente')"
                            class="text-gray-600 hover:text-gray-800 px-4 py-2 rounded-lg border hover:bg-gray-50 text-sm">
                        Cancelar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modal flotante: alta rápida de tipo de documento --}}
<div id="modal-tipo-documento-rapido-expediente" class="hidden fixed inset-0 z-[60] overflow-y-auto">
    <div class="fixed inset-0 bg-black/50" onclick="cerrarModal('modal-tipo-documento-rapido-expediente')"></div>
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="relative bg-white rounded-xl shadow-lg w-full max-w-sm">
            <div class="flex items-center justify-between px-6 py-4 border-b">
                <h3 class="font-semibold text-gray-800 text-lg">Nuevo tipo de documento</h3>
                <button type="button" onclick="cerrarModal('modal-tipo-documento-rapido-expediente')" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>
            <div class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nombre *</label>
                    <input type="text" id="input-tipo-documento-rapido-nombre-expediente" placeholder="Ej: Memorial"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                    <p id="error-tipo-documento-rapido-expediente" class="text-xs text-red-600 mt-1 hidden"></p>
                </div>
                <div class="flex gap-3">
                    <button type="button" onclick="crearTipoDocumentoRapidoExpediente()"
                            class="bg-brand-600 hover:bg-brand-700 text-white px-4 py-2 rounded-lg text-sm font-medium">
                        Guardar
                    </button>
                    <button type="button" onclick="cerrarModal('modal-tipo-documento-rapido-expediente')"
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
                    <input type="hidden" name="expediente_id" value="{{ $expediente->id }}">

                    @if($expediente->gastos->isNotEmpty())
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
                            <select name="tipo_gasto_id" id="select-tipo-gasto-expediente" required
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                                <option value="">— Seleccioná un tipo —</option>
                                @foreach($tiposGasto as $t)
                                    <option value="{{ $t->id }}" {{ old('tipo_gasto_id') == $t->id ? 'selected' : '' }}>{{ $t->nombre }}</option>
                                @endforeach
                            </select>
                            @if(auth()->user()->puede('parametros', 'crear'))
                            <button type="button" onclick="abrirModal('modal-tipo-gasto-rapido-expediente')" title="Nuevo tipo de gasto"
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
                                <dd class="font-medium text-amber-700">{{ number_format($expediente->total_gastos, 2) }} Bs</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-gray-500">Total cobrado</dt>
                                <dd class="font-medium text-emerald-700">{{ number_format($expediente->total_cobrado, 2) }} Bs</dd>
                            </div>
                            <div class="flex justify-between pt-1.5 border-t">
                                <dt class="text-gray-600 font-medium">Saldo pendiente</dt>
                                <dd class="font-semibold {{ $expediente->saldo_pendiente > 0 ? 'text-red-600' : 'text-gray-800' }}">
                                    {{ number_format($expediente->saldo_pendiente, 2) }} Bs
                                </dd>
                            </div>
                            @if($expediente->estado_pago !== 'sin_gastos')
                            <div class="flex justify-between items-center pt-1.5 border-t">
                                <dt class="text-gray-600 font-medium">Estado de la cuenta</dt>
                                <dd>
                                    <span class="px-2 py-0.5 rounded text-xs font-medium bg-{{ $expediente->estado_pago_color }}-100 text-{{ $expediente->estado_pago_color }}-700">
                                        {{ $expediente->estado_pago_label }}
                                    </span>
                                </dd>
                            </div>
                            @endif
                        </dl>
                    </div>
                </div>

                <form method="POST" action="{{ route('cobros.store') }}" class="p-6 pt-2 space-y-4"
                      id="form-cobro-nuevo"
                      data-cliente-nombre="{{ $expediente->cliente->nombre_completo }}"
                      data-cliente-whatsapp="{{ $expediente->cliente->telefono_whatsapp }}"
                      data-expediente-numero="{{ $expediente->numero }}"
                      data-expediente-caratula="{{ $expediente->caratula }}"
                      data-saldo-pendiente="{{ $expediente->saldo_pendiente }}">
                    @csrf
                    <input type="hidden" name="_modal" value="modal-gasto-cobro-nuevo">
                    <input type="hidden" name="expediente_id" value="{{ $expediente->id }}">
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
                    @php $gastosPendientes = $expediente->gastos->where('cubierto', false); @endphp
                    <div id="panel-total" class="modo-panel">
                        @if($expediente->saldo_pendiente > 0)
                            <p class="text-sm text-gray-600 bg-gray-50 rounded-lg p-3">
                                Se va a registrar un cobro por el saldo pendiente completo:
                                <span class="font-semibold text-red-600">{{ number_format($expediente->saldo_pendiente, 2) }} Bs</span>
                            </p>
                        @else
                            <p class="text-sm text-gray-400 bg-gray-50 rounded-lg p-3">Este expediente no tiene saldo pendiente.</p>
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
                                    <a href="{{ route('expedientes.items-cobro.pdf', $expediente) }}?{{ http_build_query(['gastos' => $gastosPendientes->pluck('id')->all()]) }}"
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
                        <input type="number" name="monto" id="input-monto-abono" value="{{ old('monto') }}" step="0.01" min="0.01" max="{{ $expediente->saldo_pendiente }}"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                        <p class="text-xs text-gray-400 mt-1">Máximo: {{ number_format($expediente->saldo_pendiente, 2) }} Bs</p>
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
                                {{ $expediente->cliente->telefono_whatsapp ? '' : 'disabled title="El cliente no tiene teléfono registrado"' }}
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
                    Todos los gastos ({{ $expediente->gastos->count() }})
                </h3>
                <button type="button" onclick="cerrarModal('modal-gastos-todos')" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>
            <div class="divide-y divide-gray-100">
                @foreach($expediente->gastos as $gasto)
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
                <span class="font-semibold text-amber-700">{{ number_format($expediente->total_gastos, 2) }} Bs</span>
            </div>
        </div>
    </div>
</div>

{{-- Modales flotantes: Editar Gasto --}}
@foreach($expediente->gastos as $gasto)
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
                    Todos los cobros ({{ $expediente->cobros->count() }})
                </h3>
                <button type="button" onclick="cerrarModal('modal-cobros-todos')" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>
            <div class="divide-y divide-gray-100">
                @foreach($expediente->cobros as $cobro)
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
                <span class="font-semibold text-emerald-700">{{ number_format($expediente->total_cobrado, 2) }} Bs</span>
            </div>
        </div>
    </div>
</div>

{{-- Modales flotantes: Editar Cobro --}}
@foreach($expediente->cobros as $cobro)
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
                    $saldoGeneralCobro = $expediente->saldo_pendiente + (float) $cobro->monto;
                    $maxPorGastoOpcion = [];
                    foreach ($expediente->gastos as $gastoOpcion) {
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
                        @foreach($expediente->gastos as $gastoOpcion)
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
            detalle = `Te solicitamos el pago del saldo pendiente de tu expediente: ${saldo.toFixed(2)} Bs`;
        }

        const texto = `Hola ${form.dataset.clienteNombre}, te escribimos de Vidal Escalante & Asociados respecto a tu expediente `
            + `${form.dataset.expedienteNumero} (${form.dataset.expedienteCaratula}).\n\n`
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

    (function () {
        const selectModalidad = document.getElementById('select-modalidad-expediente');
        const camposPresencial = document.getElementById('campos-presencial-expediente');
        if (!selectModalidad || !camposPresencial) return;

        function actualizarModalidad() {
            const esVirtual = selectModalidad.value === 'virtual';
            camposPresencial.classList.toggle('hidden', esVirtual);
            if (esVirtual) {
                camposPresencial.querySelectorAll('input').forEach(input => input.value = '');
            }
        }

        selectModalidad.addEventListener('change', actualizarModalidad);
        actualizarModalidad();
    })();

    function crearTipoAudienciaRapidoExpediente() {
        const input = document.getElementById('input-tipo-audiencia-rapido-nombre-expediente');
        const error = document.getElementById('error-tipo-audiencia-rapido-expediente');
        const nombre = input.value.trim();

        error.classList.add('hidden');

        if (!nombre) {
            error.textContent = 'El nombre es obligatorio.';
            error.classList.remove('hidden');
            return;
        }

        fetch('{{ route('parametros.tipos-audiencia.store') }}', {
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
                    throw new Error(body.errors?.nombre?.[0] ?? 'No se pudo crear el tipo de audiencia.');
                }
                return body;
            })
            .then((tipo) => {
                const select = document.getElementById('select-tipo-audiencia-expediente');
                const opcion = new Option(tipo.nombre, tipo.id, true, true);
                select.add(opcion);
                input.value = '';
                cerrarModal('modal-tipo-audiencia-rapido-expediente');
            })
            .catch((err) => {
                error.textContent = err.message;
                error.classList.remove('hidden');
            });
    }

    function crearTipoGastoRapidoExpediente() {
        const input = document.getElementById('input-tipo-gasto-rapido-nombre-expediente');
        const error = document.getElementById('error-tipo-gasto-rapido-expediente');
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
                const select = document.getElementById('select-tipo-gasto-expediente');
                const opcion = new Option(tipo.nombre, tipo.id, true, true);
                select.add(opcion);
                input.value = '';
                cerrarModal('modal-tipo-gasto-rapido-expediente');
            })
            .catch((err) => {
                error.textContent = err.message;
                error.classList.remove('hidden');
            });
    }

    function crearTipoActuacionRapidoExpediente() {
        const input = document.getElementById('input-tipo-actuacion-rapido-nombre-expediente');
        const error = document.getElementById('error-tipo-actuacion-rapido-expediente');
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
                const select = document.getElementById('select-tipo-actuacion-expediente');
                const opcion = new Option(tipo.nombre, tipo.id, true, true);
                select.add(opcion);
                input.value = '';
                cerrarModal('modal-tipo-actuacion-rapido-expediente');
            })
            .catch((err) => {
                error.textContent = err.message;
                error.classList.remove('hidden');
            });
    }

    function crearTipoDocumentoRapidoExpediente() {
        const input = document.getElementById('input-tipo-documento-rapido-nombre-expediente');
        const error = document.getElementById('error-tipo-documento-rapido-expediente');
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
                const select = document.getElementById('select-tipo-documento-expediente');
                const opcion = new Option(tipo.nombre, tipo.id, true, true);
                select.add(opcion);
                input.value = '';
                cerrarModal('modal-tipo-documento-rapido-expediente');
            })
            .catch((err) => {
                error.textContent = err.message;
                error.classList.remove('hidden');
            });
    }

    (function () {
        const form = document.getElementById('form-audiencia-nuevo-expediente');
        if (!form) return;

        const clienteNombre = @json($expediente->cliente->nombre_completo);
        const clienteWhatsapp = @json($expediente->cliente->telefono_whatsapp);

        form.addEventListener('submit', function (e) {
            const checkbox = form.querySelector('input[name=notificar_cliente]');
            if (!checkbox || !checkbox.checked || form.dataset.whatsappConfirmado === '1' || !clienteWhatsapp) {
                return;
            }

            e.preventDefault();

            const titulo = form.querySelector('input[name=titulo]').value.trim();
            const fecha = form.querySelector('input[name=fecha_hora]').value;
            const lugar = form.querySelector('input[name=lugar]').value.trim();
            const sala = form.querySelector('input[name=sala]').value.trim();

            let fechaTexto = '';
            if (fecha) {
                const [fechaParte, horaParte] = fecha.split('T');
                fechaTexto = `${fechaParte.split('-').reverse().join('/')} a las ${horaParte}`;
            }

            let texto = `Hola ${clienteNombre}, te confirmamos que se programó una audiencia: "${titulo}", el ${fechaTexto}.`;
            if (lugar && sala) {
                texto += ` Lugar: ${lugar} (Sala ${sala}).`;
            } else if (lugar) {
                texto += ` Lugar: ${lugar}.`;
            } else if (sala) {
                texto += ` Sala: ${sala}.`;
            }

            mostrarPreviaWhatsapp(form, texto, clienteWhatsapp);
        });
    })();

    let gastoActuacionContadorExpediente = 0;

    function agregarGastoActuacionExpediente(concepto, monto) {
        const contenedor = document.getElementById('gastos-filas-seguimiento-expediente');
        if (!contenedor) return;

        const idx = gastoActuacionContadorExpediente++;
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
            agregarGastoActuacionExpediente(@json($g['concepto'] ?? ''), @json($g['monto'] ?? ''));
        @endforeach
    @endif

    (function () {
        const form = document.getElementById('form-seguimiento-nuevo-expediente');
        if (!form) return;

        form.addEventListener('submit', function (e) {
            const checkbox = form.querySelector('input[name=notificar_cliente]');
            const telefono = form.dataset.clienteWhatsapp;
            if (!checkbox || !checkbox.checked || form.dataset.whatsappConfirmado === '1' || !telefono) {
                return;
            }

            e.preventDefault();

            const selectTipo = document.getElementById('select-tipo-actuacion-expediente');
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
