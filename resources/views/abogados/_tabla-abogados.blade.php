{{-- Requiere: $abogados. Se usa tanto en la carga normal de la página como en la
     respuesta AJAX del buscador en vivo (AbogadoController@index). Incluye el modal
     de edición por fila porque tiene que viajar junto con la fila que lo abre. --}}
<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200 text-sm">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">Nombre</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">Matrícula</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">Contacto</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">Especialidad</th>
                <th class="px-4 py-3 text-center font-semibold text-gray-600">Asignados</th>
                <th class="px-4 py-3 text-center font-semibold text-gray-600">Estado</th>
                <th class="px-4 py-3 text-right font-semibold text-gray-600">Acciones</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse($abogados as $abogado)
            <tr class="hover:bg-gray-50 transition">
                <td class="px-4 py-3">
                    <a href="{{ route('abogados.show', $abogado) }}" class="font-medium text-brand-800 hover:underline">
                        {{ $abogado->nombre }}
                    </a>
                </td>
                <td class="px-4 py-3 text-gray-600">{{ $abogado->matricula ?? '—' }}</td>
                <td class="px-4 py-3 text-gray-600">
                    <div>{{ $abogado->email ?? '—' }}</div>
                    <div class="text-xs text-gray-400">{{ $abogado->telefono }}</div>
                </td>
                <td class="px-4 py-3 text-gray-600">{{ $abogado->especialidad ?? '—' }}</td>
                <td class="px-4 py-3 text-center text-gray-600">
                    {{ $abogado->expedientes_count + $abogado->audiencias_count + $abogado->tramites_count }}
                </td>
                <td class="px-4 py-3 text-center">
                    <span class="px-2 py-1 rounded text-xs font-medium
                        {{ $abogado->activo ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                        {{ $abogado->activo ? 'Activo' : 'Inactivo' }}
                    </span>
                </td>
                <td class="px-4 py-3 text-right">
                    @if(auth()->user()->puede('abogados', 'modificar'))
                    <button type="button" onclick="abrirModal('modal-abogado-editar-{{ $abogado->id }}')" class="text-gray-500 hover:text-brand-700 mr-3">
                        <i class="fas fa-edit"></i>
                    </button>
                    @endif
                    @if(auth()->user()->puede('abogados', 'eliminar'))
                    <form method="POST" action="{{ route('abogados.destroy', $abogado) }}" class="inline"
                          onsubmit="return confirm('¿Eliminar este abogado?')">
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
                <td colspan="7" class="px-4 py-10 text-center text-gray-400">
                    <i class="fas fa-user-tie text-3xl mb-2"></i>
                    <p>No se encontraron abogados.</p>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
    </div>

    <div class="px-4 py-3 border-t border-gray-100">
        {{ $abogados->links() }}
    </div>
</div>

{{-- Modales flotantes: Editar Abogado --}}
@foreach($abogados as $abogado)
<div id="modal-abogado-editar-{{ $abogado->id }}" class="hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="fixed inset-0 bg-black/50" onclick="cerrarModal('modal-abogado-editar-{{ $abogado->id }}')"></div>
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="relative bg-white rounded-xl shadow-lg w-full max-w-xl max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between px-6 py-4 border-b sticky top-0 bg-white rounded-t-xl">
                <h3 class="font-semibold text-gray-800 text-lg">Editar: {{ $abogado->nombre }}</h3>
                <button type="button" onclick="cerrarModal('modal-abogado-editar-{{ $abogado->id }}')" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>
            <form method="POST" action="{{ route('abogados.update', $abogado) }}" class="p-6 space-y-4">
                @csrf @method('PUT')
                <input type="hidden" name="_modal" value="modal-abogado-editar-{{ $abogado->id }}">

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nombre completo *</label>
                    <input type="text" name="nombre" value="{{ $abogado->nombre }}" required
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Matrícula / Registro profesional</label>
                        <input type="text" name="matricula" value="{{ $abogado->matricula }}"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Especialidad</label>
                        <input type="text" name="especialidad" value="{{ $abogado->especialidad }}"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input type="email" name="email" value="{{ $abogado->email }}"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Teléfono</label>
                        <input type="text" name="telefono" value="{{ $abogado->telefono }}"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <input type="hidden" name="activo" value="0">
                    <input type="checkbox" name="activo" id="activo-{{ $abogado->id }}" value="1" {{ $abogado->activo ? 'checked' : '' }}
                           class="rounded border-gray-300 text-brand-700 focus:ring-brand-400">
                    <label for="activo-{{ $abogado->id }}" class="text-sm text-gray-700">Activo</label>
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="submit" class="bg-brand-600 hover:bg-brand-700 text-white px-6 py-2 rounded-lg font-medium">
                        <i class="fas fa-save mr-1"></i> Guardar cambios
                    </button>
                    <button type="button" onclick="cerrarModal('modal-abogado-editar-{{ $abogado->id }}')"
                            class="text-gray-600 hover:text-gray-800 px-4 py-2 rounded-lg border hover:bg-gray-50">
                        Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach
