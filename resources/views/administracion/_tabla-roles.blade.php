<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200 text-sm">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">Nombre</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">Descripción</th>
                <th class="px-4 py-3 text-center font-semibold text-gray-600">Usuarios</th>
                <th class="px-4 py-3 text-center font-semibold text-gray-600">Estado</th>
                <th class="px-4 py-3 text-right font-semibold text-gray-600">Acciones</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse($roles as $rol)
            <tr class="hover:bg-gray-50 transition">
                <td class="px-4 py-3 font-medium text-gray-800">{{ $rol->nombre }}</td>
                <td class="px-4 py-3 text-gray-600">{{ $rol->descripcion }}</td>
                <td class="px-4 py-3 text-center text-gray-600">{{ $rol->usuarios_count }}</td>
                <td class="px-4 py-3 text-center">
                    <span class="px-2 py-1 rounded text-xs font-medium
                        {{ $rol->activo ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                        {{ $rol->activo ? 'Activo' : 'Inactivo' }}
                    </span>
                </td>
                <td class="px-4 py-3 text-right">
                    @if(auth()->user()->puede('administracion', 'modificar'))
                    <button type="button" onclick="abrirModal('modal-rol-editar-{{ $rol->id }}')" class="text-gray-500 hover:text-brand-700 mr-3">
                        <i class="fas fa-edit"></i>
                    </button>
                    @endif
                    @if(auth()->user()->puede('administracion', 'eliminar'))
                    <form method="POST" action="{{ route('administracion.roles.destroy', $rol) }}" class="inline"
                          onsubmit="return confirm('¿Eliminar este rol? Los usuarios que lo tengan quedarán sin rol asignado.')">
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
                <td colspan="5" class="px-4 py-10 text-center text-gray-400">
                    <i class="fas fa-user-shield text-3xl mb-2"></i>
                    <p>No hay roles registrados.</p>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
    </div>
</div>
