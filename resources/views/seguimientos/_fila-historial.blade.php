{{--
    Fila de la tabla de historial de seguimiento.
    Requiere: $seg (Seguimiento), $conBotonRevisar (bool: true = lista "sin revisar", muestra el botón;
    false = lista "ya revisados", muestra la fecha en que se revisó).
--}}
<tr class="hover:bg-gray-50 transition {{ $seg->estaVencido() ? 'bg-red-50' : '' }}">
    <td class="px-4 py-3">
        <div class="w-2.5 h-2.5 rounded-full
            {{ $seg->prioridad === 'urgente' ? 'bg-red-500' : ($seg->prioridad === 'alta' ? 'bg-orange-400' : ($seg->prioridad === 'media' ? 'bg-yellow-400' : 'bg-green-400')) }}">
        </div>
    </td>
    <td class="px-4 py-3 text-gray-600 text-sm whitespace-nowrap">{{ $seg->fecha_actuacion->format('d/m/Y') }}</td>
    <td class="px-4 py-3 text-gray-600 text-sm">{{ $seg->tipoActuacion?->nombre ?? '—' }}</td>
    <td class="px-4 py-3">
        @if($seg->observaciones)
            <p class="text-xs text-gray-500">{{ Str::limit($seg->observaciones, 60) }}</p>
        @endif
    </td>
    <td class="px-4 py-3">
        <p class="text-sm text-gray-700">{{ $seg->usuario?->name ?? '—' }}</p>
        @if($seg->usuario?->rol)
            <span class="inline-block px-1.5 py-0.5 rounded text-[10px] font-medium bg-indigo-100 text-indigo-700">
                {{ $seg->usuario->rol->nombre }}
            </span>
        @endif
    </td>
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
    <td class="px-4 py-3 text-right">
        <div class="flex items-center justify-end gap-3">
            @if($seg->archivo_adjunto && auth()->user()->puede('documentos', 'ver'))
            <a href="{{ route('documentos.ver', $seg) }}" target="_blank" class="text-gray-400 hover:text-brand-700" title="Ver archivo adjunto">
                <i class="fas {{ $seg->icono_archivo }} text-lg"></i>
            </a>
            @endif
            @if($conBotonRevisar)
                @if(in_array(auth()->user()->rol?->nombre, ['Abogado', 'Administrador']))
                <form method="POST" action="{{ route('seguimientos.revisado', $seg) }}">
                    @csrf @method('PATCH')
                    <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-3 py-1.5 rounded-lg text-xs font-medium flex items-center gap-1">
                        <i class="fas fa-check"></i> Revisado
                    </button>
                </form>
                @endif
            @else
                <span class="text-xs text-gray-400 whitespace-nowrap">
                    <i class="fas fa-check-circle text-green-500 mr-1"></i>
                    {{ $seg->revisado_at?->format('d/m/Y H:i') }}
                </span>
            @endif
        </div>
    </td>
</tr>
