{{-- Requiere: $expedientes, $clientes, $tipos, $estados, $abogados, $usuarios. Se usa
     tanto en la carga normal de la página como en la respuesta AJAX del buscador en vivo
     (ExpedienteController@index). Incluye los modales de editar y cambiar-estado por
     fila (tienen que viajar junto con la fila que los abre), y sus scripts de
     inicialización (precarga de abogados/demandantes/demandados, listener del aviso
     de WhatsApp) — sin esto, las filas cargadas por AJAX abrirían modales sin datos
     precargados. --}}
<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200 text-sm">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">NUREJ</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">Carátula</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">Cliente</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">Tipo</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">Estado</th>
                <th class="px-4 py-3 text-center font-semibold text-gray-600">Actos</th>
                <th class="px-4 py-3 text-center font-semibold text-gray-600">Audiencias</th>
                <th class="px-4 py-3 text-right font-semibold text-gray-600">Acciones</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse($expedientes as $expediente)
            <tr class="hover:bg-gray-50 transition">
                <td class="px-4 py-3 font-mono text-xs text-gray-600">{{ $expediente->numero }}</td>
                <td class="px-4 py-3">
                    <a href="{{ route('expedientes.show', $expediente) }}" class="font-medium text-brand-800 hover:underline">
                        {{ Str::limit($expediente->caratula, 50) }}
                    </a>
                    @if($expediente->juzgado)
                        <p class="text-xs text-gray-400">{{ $expediente->juzgado }}</p>
                    @endif
                </td>
                <td class="px-4 py-3">
                    @if(auth()->user()->puede('clientes', 'ver'))
                    <a href="{{ route('clientes.show', $expediente->cliente) }}" class="text-gray-700 hover:text-brand-700">
                        {{ $expediente->cliente->nombre_completo }}
                    </a>
                    @else
                        {{ $expediente->cliente->nombre_completo }}
                    @endif
                </td>
                <td class="px-4 py-3">
                    <span class="px-2 py-1 bg-gray-100 text-gray-700 rounded text-xs">{{ ucfirst($expediente->tipo_causa) }}</span>
                </td>
                <td class="px-4 py-3">
                    <span class="px-2 py-1 rounded text-xs font-medium bg-{{ $expediente->estado_color }}-100 text-{{ $expediente->estado_color }}-700">
                        {{ $expediente->estado_label }}
                    </span>
                </td>
                <td class="px-4 py-3 text-center text-gray-600">{{ $expediente->seguimientos_count }}</td>
                <td class="px-4 py-3 text-center text-gray-600">{{ $expediente->audiencias_count }}</td>
                <td class="px-4 py-3 text-right">
                    @if(auth()->user()->puede('expedientes', 'modificar'))
                    <button type="button" onclick="abrirModal('modal-expediente-editar-{{ $expediente->id }}')" class="text-gray-500 hover:text-brand-700 mr-3">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button type="button" onclick="abrirModal('modal-expediente-estado-{{ $expediente->id }}')" class="text-gray-500 hover:text-indigo-600 mr-3" title="Cambiar estado">
                        <i class="fas fa-right-left"></i>
                    </button>
                    @endif
                    @if(auth()->user()->puede('expedientes', 'eliminar'))
                    <form method="POST" action="{{ route('expedientes.destroy', $expediente) }}" class="inline"
                          onsubmit="return confirm('¿Eliminar este expediente?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-gray-500 hover:text-red-600">
                            <i class="fas fa-trash"></i>
                        </button>
                    </form>
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="8" class="px-4 py-10 text-center text-gray-400">
                    <i class="fas fa-folder-open text-3xl mb-2"></i>
                    <p>No se encontraron expedientes.</p>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
    </div>
    <div class="px-4 py-3 border-t border-gray-100">
        {{ $expedientes->links() }}
    </div>
</div>

{{-- Modales flotantes: Editar Expediente --}}
@foreach($expedientes as $expediente)
<div id="modal-expediente-editar-{{ $expediente->id }}" class="hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="fixed inset-0 bg-black/50" onclick="cerrarModal('modal-expediente-editar-{{ $expediente->id }}')"></div>
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="relative bg-white rounded-xl shadow-lg w-full max-w-3xl max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between px-6 py-4 border-b sticky top-0 bg-white rounded-t-xl">
                <h3 class="font-semibold text-gray-800 text-lg">Editar: {{ $expediente->numero }}</h3>
                <button type="button" onclick="cerrarModal('modal-expediente-editar-{{ $expediente->id }}')" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>
            <form method="POST" action="{{ route('expedientes.update', $expediente) }}" class="p-6 space-y-6">
                @csrf @method('PUT')
                <input type="hidden" name="_modal" value="modal-expediente-editar-{{ $expediente->id }}">

                <div class="space-y-4">
                    <h4 class="font-semibold text-gray-700 border-b pb-2">Identificación</h4>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">NUREJ *</label>
                            <input type="text" name="numero" value="{{ $expediente->numero }}" required
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-mono focus:ring-2 focus:ring-brand-400">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Cliente *</label>
                            <div class="flex gap-2">
                                <select name="cliente_id" required onchange="toggleRepresentanteCliente('editar-{{ $expediente->id }}')" id="cliente-id-editar-{{ $expediente->id }}"
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                                    @foreach($clientes as $c)
                                        <option value="{{ $c->id }}" data-tipo="{{ $c->tipo }}"
                                                {{ $expediente->cliente_id == $c->id ? 'selected' : '' }}>
                                            {{ $c->nombre_completo }}
                                        </option>
                                    @endforeach
                                </select>
                                @if(auth()->user()->puede('clientes', 'crear'))
                                <button type="button" onclick="abrirClienteRapido('cliente-id-editar-{{ $expediente->id }}')" title="Nuevo cliente"
                                        class="shrink-0 border border-gray-300 rounded-lg w-9 h-9 flex items-center justify-center text-gray-500 hover:text-brand-700 hover:border-brand-400">
                                    <i class="fas fa-plus"></i>
                                </button>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Carátula *</label>
                        <input type="text" name="caratula" value="{{ $expediente->caratula }}" required
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                    </div>
                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Materia *</label>
                            <select name="tipo_causa" required
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                                @foreach($tipos as $t)
                                    <option value="{{ $t }}" {{ $expediente->tipo_causa === $t ? 'selected' : '' }}>{{ ucfirst($t) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Estado *</label>
                            <select name="estado_expediente_id" required
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                                @foreach($estados as $e)
                                    <option value="{{ $e->id }}" {{ $expediente->estado_expediente_id === $e->id ? 'selected' : '' }}>{{ $e->nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Abogado responsable</label>
                            <div class="flex gap-2">
                                <select name="abogado_id" id="abogado-id-editar-{{ $expediente->id }}"
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                                    <option value="">— Sin asignar —</option>
                                    @foreach($abogados as $a)
                                        <option value="{{ $a->id }}" {{ $expediente->abogado_id == $a->id ? 'selected' : '' }}>{{ $a->nombre }}</option>
                                    @endforeach
                                </select>
                                @if(auth()->user()->puede('abogados', 'crear'))
                                <button type="button" title="Nuevo abogado"
                                        onclick="abrirAltaRapida({titulo: 'Nuevo abogado', etiqueta: 'Nombre', placeholder: 'Ej: Juan Pérez', url: '{{ route('abogados.store') }}', selectId: 'abogado-id-editar-{{ $expediente->id }}'})"
                                        class="shrink-0 border border-gray-300 rounded-lg w-9 h-9 flex items-center justify-center text-gray-500 hover:text-brand-700 hover:border-brand-400">
                                    <i class="fas fa-plus"></i>
                                </button>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de proceso</label>
                            <input type="text" name="tipo_proceso" value="{{ $expediente->tipo_proceso }}"
                                   placeholder="Ej: Beneficios Sociales"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Procedimiento</label>
                            <input type="text" name="procedimiento" value="{{ $expediente->procedimiento }}"
                                   placeholder="Ej: Laboral Social"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                        </div>
                    </div>
                </div>

                <div class="space-y-4">
                    <h4 class="font-semibold text-gray-700 border-b pb-2">Datos judiciales</h4>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Lugar asignado en el reparto</label>
                            <input type="text" name="juzgado" value="{{ $expediente->juzgado }}"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Fecha de recepción</label>
                            <input type="date" name="fecha_recepcion" value="{{ $expediente->fecha_recepcion?->format('Y-m-d') }}"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Piso</label>
                            <input type="text" name="piso" value="{{ $expediente->piso }}"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Dirección (sede judicial)</label>
                            <input type="text" name="direccion" value="{{ $expediente->direccion }}"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Monto reclamado ($)</label>
                        <input type="number" name="monto_reclamado" value="{{ $expediente->monto_reclamado }}" min="0" step="0.01"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Estado del proceso</label>
                        <textarea name="descripcion" rows="3"
                                  class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">{{ $expediente->descripcion }}</textarea>
                    </div>
                </div>

                <div class="space-y-4">
                    <h4 class="font-semibold text-gray-700 border-b pb-2">Asignación interna</h4>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Encargado actual</label>
                            <input type="text" name="encargado_actual" value="{{ old('encargado_actual', $expediente->encargado_actual) }}"
                                   placeholder="Nombre de la persona encargada"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Encargado anterior</label>
                            <input type="text" name="enc_anterior" value="{{ old('enc_anterior', $expediente->enc_anterior) }}"
                                   placeholder="Nombre de la persona encargada anteriormente"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Seguimiento (puede ser más de una persona)</label>
                        <div class="flex flex-wrap gap-2 border border-gray-200 rounded-lg p-3">
                            @foreach($usuarios as $u)
                                <label class="flex items-center gap-1.5 text-sm bg-gray-50 border border-gray-200 rounded-full px-2.5 py-1 cursor-pointer">
                                    <input type="checkbox" name="seguidores[]" value="{{ $u->id }}"
                                           {{ $expediente->seguidores->contains('id', $u->id) ? 'checked' : '' }}
                                           class="w-3.5 h-3.5 text-brand-700 rounded">
                                    {{ $u->name }}
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="space-y-4">
                    <h4 class="font-semibold text-gray-700 border-b pb-2">Partes del proceso</h4>

                    <div id="representante-cliente-editar-{{ $expediente->id }}" class="hidden bg-gray-50 border rounded-lg p-3 space-y-2">
                        <p class="text-xs text-gray-500">El cliente es persona jurídica: podés indicar su representante y abogados.</p>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Representante</label>
                            <input type="text" name="representante_cliente" value="{{ $expediente->representante_cliente }}"
                                   placeholder="Nombre completo del representante"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                        </div>
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="block text-sm font-medium text-gray-700">Abogados</label>
                                <button type="button" onclick="agregarAbogadoCliente('abogados-cliente-editar-{{ $expediente->id }}')"
                                        class="text-xs text-brand-700 hover:underline">+ Agregar abogado</button>
                            </div>
                            <div id="abogados-cliente-editar-{{ $expediente->id }}" class="space-y-2"></div>
                        </div>
                    </div>

                    <div id="partes-fisica-editar-{{ $expediente->id }}" class="hidden">
                        <div class="flex items-center justify-between mb-2">
                            <label class="block text-sm font-medium text-gray-700">Demandantes</label>
                            <button type="button" onclick="agregarParteSimple('demandantes', 'partes-demandantes-editar-{{ $expediente->id }}')"
                                    class="text-xs text-brand-700 hover:underline">+ Agregar demandante</button>
                        </div>
                        <div id="partes-demandantes-editar-{{ $expediente->id }}" class="space-y-2"></div>
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="block text-sm font-medium text-gray-700">Demandados</label>
                            <button type="button" onclick="agregarParteSimple('demandados', 'partes-demandados-editar-{{ $expediente->id }}')"
                                    class="text-xs text-brand-700 hover:underline">+ Agregar demandado</button>
                        </div>
                        <div id="partes-demandados-editar-{{ $expediente->id }}" class="space-y-2"></div>
                    </div>
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="submit" class="bg-brand-600 hover:bg-brand-700 text-white px-6 py-2 rounded-lg font-medium">
                        <i class="fas fa-save mr-1"></i> Guardar cambios
                    </button>
                    <button type="button" onclick="cerrarModal('modal-expediente-editar-{{ $expediente->id }}')"
                            class="text-gray-600 hover:text-gray-800 px-4 py-2 rounded-lg border hover:bg-gray-50">
                        Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
    toggleRepresentanteCliente('editar-{{ $expediente->id }}');
    @foreach($expediente->abogados_cliente ?? [] as $abogado)
        agregarAbogadoCliente('abogados-cliente-editar-{{ $expediente->id }}', @json($abogado));
    @endforeach
    @foreach($expediente->demandantes as $d)
        agregarParteSimple('demandantes', 'partes-demandantes-editar-{{ $expediente->id }}', @json($d->nombre));
    @endforeach
    @foreach($expediente->demandados as $d)
        agregarParteSimple('demandados', 'partes-demandados-editar-{{ $expediente->id }}', @json($d->nombre));
    @endforeach
</script>
@endforeach

{{-- Modales flotantes: Cambiar estado --}}
@foreach($expedientes as $expediente)
<div id="modal-expediente-estado-{{ $expediente->id }}" class="hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="fixed inset-0 bg-black/50" onclick="cerrarModal('modal-expediente-estado-{{ $expediente->id }}')"></div>
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="relative bg-white rounded-xl shadow-lg w-full max-w-lg max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between px-6 py-4 border-b sticky top-0 bg-white rounded-t-xl">
                <h3 class="font-semibold text-gray-800 text-lg">
                    Cambiar estado <span class="text-xs font-normal text-gray-400 font-mono">{{ $expediente->numero }}</span>
                </h3>
                <button type="button" onclick="cerrarModal('modal-expediente-estado-{{ $expediente->id }}')" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>
            <form method="POST" action="{{ route('expedientes.cambiar-estado', $expediente) }}" class="p-6 space-y-4"
                  id="form-expediente-estado-{{ $expediente->id }}"
                  data-cliente-nombre="{{ $expediente->cliente->nombre_completo }}"
                  data-cliente-whatsapp="{{ $expediente->cliente->telefono_whatsapp }}"
                  data-expediente-numero="{{ $expediente->numero }}"
                  data-estado-actual="{{ $expediente->estado_label }}">
                @csrf @method('PATCH')
                <input type="hidden" name="_modal" value="modal-expediente-estado-{{ $expediente->id }}">

                <p class="text-sm text-gray-600 bg-gray-50 rounded-lg p-3">
                    Estado actual:
                    <span class="px-2 py-0.5 rounded text-xs font-medium bg-{{ $expediente->estado_color }}-100 text-{{ $expediente->estado_color }}-700">
                        {{ $expediente->estado_label }}
                    </span>
                </p>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nuevo estado *</label>
                    <select name="estado_expediente_id" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                        @foreach($estados as $e)
                            @continue($e->id === $expediente->estado_expediente_id)
                            <option value="{{ $e->id }}">{{ $e->nombre }}</option>
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
                    @if(!$expediente->cliente->telefono)
                        <span class="text-xs text-gray-400">(sin teléfono registrado)</span>
                    @endif
                </label>

                <div class="flex gap-3 pt-2">
                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2 rounded-lg font-medium">
                        <i class="fas fa-right-left mr-1"></i> Cambiar estado
                    </button>
                    <button type="button" onclick="cerrarModal('modal-expediente-estado-{{ $expediente->id }}')"
                            class="text-gray-600 hover:text-gray-800 px-4 py-2 rounded-lg border hover:bg-gray-50">
                        Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach
<script>inicializarFormulariosEstadoExpediente();</script>
