{{-- Requiere: $seguimientos (colección de Seguimiento). Abre el modal #modal-seguimiento-nuevo definido en la vista que incluye este partial. --}}
<div class="bg-white rounded-xl shadow-sm">
    <div class="flex items-center justify-between p-5 border-b">
        <h3 class="font-semibold text-gray-700 flex items-center gap-2">
            <i class="fas fa-list-check text-green-600 mr-2"></i>
            Seguimiento ({{ $seguimientos->count() }})
            @if($seguimientos->count() > 2)
                <button type="button" onclick="abrirModal('modal-actuaciones-todas')" class="text-gray-400 hover:text-brand-700" title="Ver todo el seguimiento">
                    <i class="fas fa-eye"></i>
                </button>
            @endif
        </h3>
        @if(auth()->user()->puede('seguimientos', 'crear'))
        <button type="button" onclick="abrirModal('modal-seguimiento-nuevo')" class="text-xs text-green-600 hover:underline">+ Nuevo Seguimiento</button>
        @endif
    </div>
    <div class="divide-y divide-gray-100">
        @forelse($seguimientos->take(2) as $seg)
        <div class="px-5 py-3 hover:bg-gray-50 flex items-start gap-3">
            <div class="mt-1 w-2 h-2 rounded-full flex-shrink-0
                {{ $seg->prioridad === 'urgente' ? 'bg-red-500' : ($seg->prioridad === 'alta' ? 'bg-orange-400' : ($seg->prioridad === 'media' ? 'bg-yellow-400' : 'bg-green-400')) }}">
            </div>
            <div class="flex-1 min-w-0">
                <p class="font-medium text-sm text-gray-800">{{ $seg->titulo }}</p>
                <p class="text-xs text-gray-500">
                    {{ $seg->tipoActuacion?->nombre ?? '—' }} · {{ $seg->fecha_actuacion->format('d/m/Y') }}
                    @if($seg->requiere_respuesta && !$seg->respondido)
                        · <span class="{{ $seg->estaVencido() ? 'text-red-500' : 'text-orange-500' }} font-medium">
                            Pendiente {{ $seg->fecha_vencimiento ? 'vence ' . $seg->fecha_vencimiento->format('d/m/Y') : '' }}
                        </span>
                    @endif
                </p>
                <p class="text-xs text-gray-400 mt-0.5">
                    <i class="fas fa-user-pen mr-1"></i>{{ $seg->usuario?->name ?? '—' }}
                    @if($seg->usuario?->rol)
                        <span class="inline-block px-1.5 py-0.5 rounded text-[10px] font-medium bg-indigo-100 text-indigo-700 ml-1">
                            {{ $seg->usuario->rol->nombre }}
                        </span>
                    @endif
                </p>
            </div>
            <div class="flex gap-2 text-xs">
                @if(auth()->user()->puede('seguimientos', 'modificar'))
                    @if($seg->requiere_respuesta && !$seg->respondido)
                        <form method="POST" action="{{ route('seguimientos.responder', $seg) }}">
                            @csrf @method('PATCH')
                            <button class="text-green-600 hover:underline">Responder</button>
                        </form>
                    @endif
                @endif
                <a href="{{ route('seguimientos.show', $seg) }}" class="text-gray-400 hover:text-brand-700" title="Ver">
                    <i class="fas fa-eye"></i>
                </a>
                @if(auth()->user()->puede('seguimientos', 'modificar'))
                <a href="{{ route('seguimientos.index', ['editar' => $seg->id, 'id' => $seg->id]) }}" class="text-gray-400 hover:text-brand-700" title="Editar">
                    <i class="fas fa-edit"></i>
                </a>
                @endif
                @if(auth()->user()->puede('seguimientos', 'eliminar'))
                <form method="POST" action="{{ route('seguimientos.destroy', $seg) }}" onsubmit="return confirm('¿Eliminar esta actuación?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="text-gray-400 hover:text-red-600" title="Eliminar">
                        <i class="fas fa-trash"></i>
                    </button>
                </form>
                @endif
            </div>
        </div>
        @empty
        <p class="p-5 text-sm text-gray-400 text-center">Sin seguimiento registrado.</p>
        @endforelse
    </div>
</div>

{{-- Modal flotante: Ver todas las actuaciones --}}
<div id="modal-actuaciones-todas" class="hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="fixed inset-0 bg-black/50" onclick="cerrarModal('modal-actuaciones-todas')"></div>
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="relative bg-white rounded-xl shadow-lg w-full max-w-lg max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between px-6 py-4 border-b sticky top-0 bg-white rounded-t-xl">
                <h3 class="font-semibold text-gray-800 text-lg">
                    <i class="fas fa-list-check text-green-600 mr-2"></i>
                    Todo el seguimiento ({{ $seguimientos->count() }})
                </h3>
                <button type="button" onclick="cerrarModal('modal-actuaciones-todas')" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>
            <div class="divide-y divide-gray-100">
                @foreach($seguimientos as $seg)
                <div class="px-5 py-3 hover:bg-gray-50 flex items-start gap-3">
                    <div class="mt-1 w-2 h-2 rounded-full flex-shrink-0
                        {{ $seg->prioridad === 'urgente' ? 'bg-red-500' : ($seg->prioridad === 'alta' ? 'bg-orange-400' : ($seg->prioridad === 'media' ? 'bg-yellow-400' : 'bg-green-400')) }}">
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="font-medium text-sm text-gray-800">{{ $seg->titulo }}</p>
                        <p class="text-xs text-gray-500">
                            {{ $seg->tipoActuacion?->nombre ?? '—' }} · {{ $seg->fecha_actuacion->format('d/m/Y') }}
                            @if($seg->requiere_respuesta && !$seg->respondido)
                                · <span class="{{ $seg->estaVencido() ? 'text-red-500' : 'text-orange-500' }} font-medium">
                                    Pendiente {{ $seg->fecha_vencimiento ? 'vence ' . $seg->fecha_vencimiento->format('d/m/Y') : '' }}
                                </span>
                            @endif
                        </p>
                    </div>
                    <div class="flex gap-2 text-xs">
                        @if($seg->requiere_respuesta && !$seg->respondido)
                            <form method="POST" action="{{ route('seguimientos.responder', $seg) }}">
                                @csrf @method('PATCH')
                                <button class="text-green-600 hover:underline">Responder</button>
                            </form>
                        @endif
                        <a href="{{ route('seguimientos.show', $seg) }}" class="text-gray-400 hover:text-brand-700" title="Ver">
                            <i class="fas fa-eye"></i>
                        </a>
                        @if(auth()->user()->puede('seguimientos', 'modificar'))
                        <a href="{{ route('seguimientos.index', ['editar' => $seg->id, 'id' => $seg->id]) }}" class="text-gray-400 hover:text-brand-700" title="Editar">
                            <i class="fas fa-edit"></i>
                        </a>
                        @endif
                        @if(auth()->user()->puede('seguimientos', 'eliminar'))
                        <form method="POST" action="{{ route('seguimientos.destroy', $seg) }}" onsubmit="return confirm('¿Eliminar esta actuación?')">
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
        </div>
    </div>
</div>
