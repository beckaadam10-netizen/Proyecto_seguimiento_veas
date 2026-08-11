<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200 text-sm">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">Nombre</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">Email</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">Rol</th>
                <th class="px-4 py-3 text-right font-semibold text-gray-600">Acciones</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse($usuarios as $usuario)
            <tr class="hover:bg-gray-50 transition">
                <td class="px-4 py-3 font-medium text-gray-800">
                    {{ $usuario->name }}
                    @if($usuario->id === auth()->id())
                        <span class="text-xs text-gray-400">(vos)</span>
                    @endif
                    @unless($usuario->activo)
                        <span class="ml-1 px-1.5 py-0.5 rounded text-[10px] font-medium bg-gray-100 text-gray-500">Inactivo</span>
                    @endunless
                </td>
                <td class="px-4 py-3 text-gray-600">{{ $usuario->email }}</td>
                <td class="px-4 py-3">
                    @if($usuario->rol)
                        <span class="px-2 py-1 rounded text-xs font-medium bg-indigo-100 text-indigo-700">{{ $usuario->rol->nombre }}</span>
                    @else
                        <span class="text-xs text-gray-400">Sin rol asignado</span>
                    @endif
                </td>
                <td class="px-4 py-3 text-right">
                    @if(auth()->user()->puede('administracion', 'modificar'))
                    <button type="button" onclick="abrirModal('modal-usuario-editar-{{ $usuario->id }}')" class="text-gray-500 hover:text-brand-700 mr-3">
                        <i class="fas fa-edit"></i>
                    </button>
                    @endif
                    @if($usuario->id !== auth()->id() && auth()->user()->puede('administracion', 'eliminar'))
                    <form method="POST" action="{{ route('administracion.usuarios.destroy', $usuario) }}" class="inline"
                          onsubmit="return confirm('¿Eliminar este usuario?')">
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
                <td colspan="4" class="px-4 py-10 text-center text-gray-400">
                    <i class="fas fa-users text-3xl mb-2"></i>
                    <p>No hay usuarios registrados.</p>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
    </div>
</div>
