@extends('layouts.app')

@section('title', 'Audiencias')
@section('header', 'Agenda de Audiencias')

@section('header-actions')
    @if(auth()->user()->puede('audiencias', 'crear'))
    <button type="button" onclick="abrirModal('modal-audiencia-nuevo')"
       class="bg-brand-600 hover:bg-brand-700 text-white px-4 py-2 rounded-lg text-sm font-medium flex items-center gap-2">
        <i class="fas fa-plus"></i> Nueva Audiencia
    </button>
    @endif
@endsection

@section('content')

<form method="GET" class="bg-white rounded-xl shadow-sm p-4 mb-6 flex flex-wrap gap-3 items-end">
    <div>
        <label class="block text-xs text-gray-500 mb-1">Estado</label>
        <select name="estado" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
            <option value="">Todos</option>
            @foreach($estados as $e)
                <option value="{{ $e }}" {{ request('estado') === $e ? 'selected' : '' }}>{{ ucfirst($e) }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-xs text-gray-500 mb-1">Abogado</label>
        <select name="abogado_id" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
            <option value="">Todos</option>
            @foreach($abogados as $a)
                <option value="{{ $a->id }}" {{ request('abogado_id') == $a->id ? 'selected' : '' }}>{{ $a->nombre }}</option>
            @endforeach
        </select>
    </div>
    <div class="flex items-end gap-2">
        <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer border rounded-lg px-3 py-2 {{ request('hoy') ? 'bg-brand-50 border-brand-300' : '' }}">
            <input type="checkbox" name="hoy" value="1" {{ request('hoy') ? 'checked' : '' }}>
            Solo hoy
        </label>
        <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer border rounded-lg px-3 py-2 {{ request('proximas') ? 'bg-purple-50 border-purple-300' : '' }}">
            <input type="checkbox" name="proximas" value="1" {{ request('proximas') ? 'checked' : '' }}>
            Próximos 30 días
        </label>
    </div>
    <button type="submit" class="bg-brand-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-brand-700">
        <i class="fas fa-search"></i> Filtrar
    </button>
    <a href="{{ route('audiencias.index') }}" class="text-gray-500 text-sm py-2 hover:text-gray-700">
        <i class="fas fa-times"></i> Limpiar
    </a>
</form>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200 text-sm">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">Fecha y hora</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">Audiencia</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">Expediente / Cliente</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">Lugar</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">Abogado</th>
                <th class="px-4 py-3 text-center font-semibold text-gray-600">Estado</th>
                <th class="px-4 py-3 text-right font-semibold text-gray-600">Acciones</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse($audiencias as $aud)
            @php
                $hoy = $aud->fecha_hora->isToday();
                $pasada = $aud->fecha_hora->isPast() && $aud->estado === 'programada';
            @endphp
            <tr class="hover:bg-gray-50 transition {{ $hoy ? 'bg-brand-50' : ($pasada ? 'bg-red-50' : '') }}">
                <td class="px-4 py-3">
                    <div class="flex items-center gap-2">
                        @if($hoy)
                            <span class="w-2 h-2 bg-brand-500 rounded-full flex-shrink-0 animate-pulse"></span>
                        @endif
                        <div>
                            <p class="font-medium {{ $hoy ? 'text-brand-800' : 'text-gray-800' }}">
                                {{ $aud->fecha_hora->format('d/m/Y') }}
                            </p>
                            <p class="text-xs text-gray-500">{{ $aud->fecha_hora->format('H:i') }}h · {{ $aud->duracion_estimada }} min</p>
                        </div>
                    </div>
                </td>
                <td class="px-4 py-3">
                    <p class="font-medium text-gray-800">{{ $aud->titulo }}</p>
                    <p class="text-xs text-gray-500">{{ $aud->tipoAudiencia?->nombre ?? '—' }}</p>
                </td>
                <td class="px-4 py-3">
                    @if($aud->expediente)
                    <a href="{{ route('expedientes.show', $aud->expediente) }}" class="text-brand-800 hover:underline text-xs font-mono">
                        {{ $aud->expediente->numero }}
                    </a>
                    <p class="text-xs text-gray-500">{{ $aud->expediente->cliente->nombre_completo }}</p>
                    @else
                    <span class="text-xs text-gray-400 italic">Expediente eliminado</span>
                    @endif
                </td>
                <td class="px-4 py-3 text-gray-600 text-xs">
                    {{ $aud->lugar ?? '—' }}
                    @if($aud->sala) <span class="text-gray-400">· Sala {{ $aud->sala }}</span> @endif
                </td>
                <td class="px-4 py-3 text-gray-600 text-sm">{{ $aud->abogado?->nombre ?? '—' }}</td>
                <td class="px-4 py-3 text-center">
                    @php
                        $colores = ['programada'=>'purple','confirmada'=>'green','realizada'=>'gray','suspendida'=>'yellow','reprogramada'=>'orange','cancelada'=>'red'];
                        $c = $colores[$aud->estado] ?? 'gray';
                    @endphp
                    <span class="px-2 py-0.5 rounded text-xs font-medium bg-{{ $c }}-100 text-{{ $c }}-700">
                        {{ ucfirst($aud->estado) }}
                    </span>
                </td>
                <td class="px-4 py-3 text-right">
                    @if(auth()->user()->puede('audiencias', 'modificar'))
                        @if(!in_array($aud->estado, ['realizada', 'cancelada']))
                            <form method="POST" action="{{ route('audiencias.finalizar', $aud) }}" class="inline"
                                  onsubmit="return confirm('¿Marcar esta audiencia como realizada?')">
                                @csrf @method('PATCH')
                                <button type="submit" class="text-gray-400 hover:text-green-600 mr-2" title="Marcar como realizada">
                                    <i class="fas fa-check-circle"></i>
                                </button>
                            </form>
                        @endif
                        <button type="button" onclick="abrirModal('modal-audiencia-editar-{{ $aud->id }}')" class="text-gray-400 hover:text-brand-700 mr-2" title="Editar">
                            <i class="fas fa-edit"></i>
                        </button>
                    @endif
                    @if(auth()->user()->puede('audiencias', 'eliminar'))
                    <form method="POST" action="{{ route('audiencias.destroy', $aud) }}" class="inline"
                          onsubmit="return confirm('¿Eliminar esta audiencia?')">
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
                <td colspan="7" class="px-4 py-10 text-center text-gray-400">
                    <i class="fas fa-gavel text-3xl mb-2"></i>
                    <p>No se encontraron audiencias.</p>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
    </div>
    <div class="px-4 py-3 border-t">{{ $audiencias->links() }}</div>
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
            <form method="POST" action="{{ route('audiencias.store') }}" class="p-6 space-y-4" id="form-audiencia-nuevo">
                @csrf
                <input type="hidden" name="_modal" value="modal-audiencia-nuevo">

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Expediente *</label>
                    <select name="expediente_id" id="select-expediente-audiencia-nuevo" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                        <option value="">— Seleccioná un expediente —</option>
                        @foreach($expedientes as $exp)
                            <option value="{{ $exp->id }}" {{ old('expediente_id', request('expediente_id')) == $exp->id ? 'selected' : '' }}
                                    data-cliente-nombre="{{ $exp->cliente->nombre_completo }}"
                                    data-cliente-whatsapp="{{ $exp->cliente->telefono_whatsapp }}">
                                {{ $exp->numero }} — {{ $exp->cliente->nombre_completo }} | {{ Str::limit($exp->caratula, 45) }} ({{ $exp->abogado->nombre ?? 'sin abogado' }})
                            </option>
                        @endforeach
                    </select>
                    @error('expediente_id')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Título *</label>
                    <input type="text" name="titulo" value="{{ old('titulo') }}" required
                           placeholder="Ej: Audiencia preliminar"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tipo *</label>
                        <div class="flex gap-2">
                            <select name="tipo_audiencia_id" id="select-tipo-audiencia-nuevo" required
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                                <option value="">— Seleccioná un tipo —</option>
                                @foreach($tiposAudiencia as $t)
                                    <option value="{{ $t->id }}" {{ old('tipo_audiencia_id') == $t->id ? 'selected' : '' }}>{{ $t->nombre }}</option>
                                @endforeach
                            </select>
                            @if(auth()->user()->puede('parametros', 'crear'))
                            <button type="button" onclick="abrirModal('modal-tipo-audiencia-rapido-nuevo')" title="Nuevo tipo de audiencia"
                                    class="shrink-0 border border-gray-300 rounded-lg w-9 h-9 flex items-center justify-center text-gray-500 hover:text-brand-700 hover:border-brand-400">
                                <i class="fas fa-plus"></i>
                            </button>
                            @endif
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Estado *</label>
                        <select name="estado" required
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                            @foreach($estados as $e)
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
                        <select name="modalidad" id="select-modalidad-nuevo" required
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                            <option value="presencial" {{ old('modalidad', 'presencial') === 'presencial' ? 'selected' : '' }}>Presencial</option>
                            <option value="virtual" {{ old('modalidad') === 'virtual' ? 'selected' : '' }}>Virtual</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4" id="campos-presencial-nuevo">
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
                        <select name="abogado_id" id="select-abogado-audiencia-nuevo"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                            <option value="">— Sin asignar —</option>
                            @foreach($abogados as $a)
                                <option value="{{ $a->id }}" {{ old('abogado_id') == $a->id ? 'selected' : '' }}>{{ $a->nombre }}</option>
                            @endforeach
                        </select>
                        @if(auth()->user()->puede('abogados', 'crear'))
                        <button type="button" title="Nuevo abogado"
                                onclick="abrirAltaRapida({titulo: 'Nuevo abogado', etiqueta: 'Nombre', placeholder: 'Ej: Juan Pérez', url: '{{ route('abogados.store') }}', selectId: 'select-abogado-audiencia-nuevo'})"
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

{{-- Modales flotantes: Editar Audiencia --}}
@foreach($audiencias as $aud)
<div id="modal-audiencia-editar-{{ $aud->id }}" class="hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="fixed inset-0 bg-black/50" onclick="cerrarModal('modal-audiencia-editar-{{ $aud->id }}')"></div>
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="relative bg-white rounded-xl shadow-lg w-full max-w-2xl max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between px-6 py-4 border-b sticky top-0 bg-white rounded-t-xl">
                <h3 class="font-semibold text-gray-800 text-lg">Editar: {{ $aud->titulo }}</h3>
                <button type="button" onclick="cerrarModal('modal-audiencia-editar-{{ $aud->id }}')" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>
            <form method="POST" action="{{ route('audiencias.update', $aud) }}" class="p-6 space-y-4">
                @csrf @method('PUT')
                <input type="hidden" name="_modal" value="modal-audiencia-editar-{{ $aud->id }}">

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Expediente *</label>
                    <select name="expediente_id" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                        @foreach($expedientes as $exp)
                            <option value="{{ $exp->id }}" {{ $aud->expediente_id == $exp->id ? 'selected' : '' }}>
                                {{ $exp->numero }} — {{ $exp->cliente->nombre_completo }} ({{ $exp->abogado->nombre ?? 'sin abogado' }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Título *</label>
                    <input type="text" name="titulo" value="{{ $aud->titulo }}" required
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tipo *</label>
                        <select name="tipo_audiencia_id" required
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                            <option value="">— Seleccioná un tipo —</option>
                            @foreach($tiposAudiencia as $t)
                                <option value="{{ $t->id }}" {{ $aud->tipo_audiencia_id == $t->id ? 'selected' : '' }}>{{ $t->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Estado *</label>
                        <select name="estado" required
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                            @foreach($estados as $e)
                                <option value="{{ $e }}" {{ $aud->estado === $e ? 'selected' : '' }}>{{ ucfirst($e) }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Fecha y hora *</label>
                        <input type="datetime-local" name="fecha_hora"
                               value="{{ $aud->fecha_hora->format('Y-m-d\TH:i') }}" required
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Duración (minutos) *</label>
                        <input type="number" name="duracion_estimada" value="{{ $aud->duracion_estimada }}"
                               min="15" max="480" required
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Lugar</label>
                        <input type="text" name="lugar" value="{{ $aud->lugar }}"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Sala</label>
                        <input type="text" name="sala" value="{{ $aud->sala }}"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Abogado responsable</label>
                    <div class="flex gap-2">
                        <select name="abogado_id" id="select-abogado-audiencia-editar-{{ $aud->id }}"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                            <option value="">— Sin asignar —</option>
                            @foreach($abogados as $a)
                                <option value="{{ $a->id }}" {{ $aud->abogado_id == $a->id ? 'selected' : '' }}>{{ $a->nombre }}</option>
                            @endforeach
                        </select>
                        @if(auth()->user()->puede('abogados', 'crear'))
                        <button type="button" title="Nuevo abogado"
                                onclick="abrirAltaRapida({titulo: 'Nuevo abogado', etiqueta: 'Nombre', placeholder: 'Ej: Juan Pérez', url: '{{ route('abogados.store') }}', selectId: 'select-abogado-audiencia-editar-{{ $aud->id }}'})"
                                class="shrink-0 border border-gray-300 rounded-lg w-9 h-9 flex items-center justify-center text-gray-500 hover:text-brand-700 hover:border-brand-400">
                            <i class="fas fa-plus"></i>
                        </button>
                        @endif
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Resultado / Acta</label>
                    <textarea name="resultado" rows="3"
                              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400"
                              placeholder="Resultado de la audiencia, acuerdos alcanzados...">{{ $aud->resultado }}</textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Próxima fecha</label>
                    <input type="date" name="proxima_fecha" value="{{ $aud->proxima_fecha?->format('Y-m-d') }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                </div>

                <div class="flex items-center gap-2">
                    <input type="checkbox" name="notificado_cliente" id="notificado-editar-{{ $aud->id }}" value="1"
                           {{ $aud->notificado_cliente ? 'checked' : '' }}
                           class="w-4 h-4 text-brand-700 rounded">
                    <label for="notificado-editar-{{ $aud->id }}" class="text-sm text-gray-700">Cliente notificado</label>
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="submit" class="bg-brand-600 hover:bg-brand-700 text-white px-6 py-2 rounded-lg font-medium">
                        <i class="fas fa-save mr-1"></i> Guardar cambios
                    </button>
                    <button type="button" onclick="cerrarModal('modal-audiencia-editar-{{ $aud->id }}')"
                            class="text-gray-600 hover:text-gray-800 px-4 py-2 rounded-lg border hover:bg-gray-50">
                        Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach

{{-- Modal flotante: alta rápida de tipo de audiencia --}}
<div id="modal-tipo-audiencia-rapido-nuevo" class="hidden fixed inset-0 z-[60] overflow-y-auto">
    <div class="fixed inset-0 bg-black/50" onclick="cerrarModal('modal-tipo-audiencia-rapido-nuevo')"></div>
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="relative bg-white rounded-xl shadow-lg w-full max-w-sm">
            <div class="flex items-center justify-between px-6 py-4 border-b">
                <h3 class="font-semibold text-gray-800 text-lg">Nuevo tipo de audiencia</h3>
                <button type="button" onclick="cerrarModal('modal-tipo-audiencia-rapido-nuevo')" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>
            <div class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nombre *</label>
                    <input type="text" id="input-tipo-audiencia-rapido-nombre-nuevo" placeholder="Ej: Audiencia de conciliación"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                    <p id="error-tipo-audiencia-rapido-nuevo" class="text-xs text-red-600 mt-1 hidden"></p>
                </div>
                <div class="flex gap-3">
                    <button type="button" onclick="crearTipoAudienciaRapidoNuevo()"
                            class="bg-brand-600 hover:bg-brand-700 text-white px-4 py-2 rounded-lg text-sm font-medium">
                        Guardar
                    </button>
                    <button type="button" onclick="cerrarModal('modal-tipo-audiencia-rapido-nuevo')"
                            class="text-gray-600 hover:text-gray-800 px-4 py-2 rounded-lg border hover:bg-gray-50 text-sm">
                        Cancelar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    @if($errors->any() && old('_modal'))
        abrirModal(@json(old('_modal')));
    @endif

    @if(request()->filled('nuevo'))
        abrirModal('modal-audiencia-nuevo');
    @endif

    @if(request()->filled('editar') && ctype_digit((string) request('editar')))
        abrirModal(@json('modal-audiencia-editar-' . request('editar')));
    @endif

    (function () {
        const selectModalidad = document.getElementById('select-modalidad-nuevo');
        const camposPresencial = document.getElementById('campos-presencial-nuevo');
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

    function crearTipoAudienciaRapidoNuevo() {
        const input = document.getElementById('input-tipo-audiencia-rapido-nombre-nuevo');
        const error = document.getElementById('error-tipo-audiencia-rapido-nuevo');
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
                const select = document.getElementById('select-tipo-audiencia-nuevo');
                const opcion = new Option(tipo.nombre, tipo.id, true, true);
                select.add(opcion);
                input.value = '';
                cerrarModal('modal-tipo-audiencia-rapido-nuevo');
            })
            .catch((err) => {
                error.textContent = err.message;
                error.classList.remove('hidden');
            });
    }

    (function () {
        const form = document.getElementById('form-audiencia-nuevo');
        if (!form) return;

        form.addEventListener('submit', function (e) {
            const checkbox = form.querySelector('input[name=notificar_cliente]');
            if (!checkbox || !checkbox.checked || form.dataset.whatsappConfirmado === '1') {
                return;
            }

            const select = document.getElementById('select-expediente-audiencia-nuevo');
            const opcion = select.options[select.selectedIndex];

            if (!opcion || !opcion.dataset.clienteWhatsapp) {
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

            let texto = `Hola ${opcion.dataset.clienteNombre}, te confirmamos que se programó una audiencia: "${titulo}", el ${fechaTexto}.`;
            if (lugar && sala) {
                texto += ` Lugar: ${lugar} (Sala ${sala}).`;
            } else if (lugar) {
                texto += ` Lugar: ${lugar}.`;
            } else if (sala) {
                texto += ` Sala: ${sala}.`;
            }

            mostrarPreviaWhatsapp(form, texto, opcion.dataset.clienteWhatsapp);
        });
    })();
</script>
@endpush
@endsection
