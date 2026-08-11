{{--
    Matriz de permisos: una fila por módulo, una columna por acción (Ver / Gestionar / Eliminar).
    Requiere: $modulosPermisos (Permiso::agrupadosPorModulo()), $seleccionados (array de ids marcados)
    Opcional: $idPrefix (para que los checkbox tengan data-matriz únicos si hay varias matrices en la misma página)
--}}
@php
    $idPrefix = $idPrefix ?? 'permisos';
    $seleccionados = $seleccionados ?? [];
@endphp

<div class="border border-gray-200 rounded-lg overflow-hidden">
    <div class="flex items-center justify-between px-4 py-3 bg-gray-50 border-b text-sm">
        <span class="text-gray-500">Marcá qué puede hacer este rol en cada módulo del sistema.</span>
        <div class="flex gap-4">
            <button type="button" onclick="marcarTodosPermisos('{{ $idPrefix }}', true)" class="text-brand-700 hover:underline font-medium">
                Marcar todo
            </button>
            <button type="button" onclick="marcarTodosPermisos('{{ $idPrefix }}', false)" class="text-gray-500 hover:underline font-medium">
                Ninguno
            </button>
        </div>
    </div>
    <div class="max-h-[32rem] overflow-y-auto overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-xs text-gray-500 uppercase tracking-wide sticky top-0">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold">Módulo</th>
                    @foreach(\App\Models\Permiso::ACCIONES as $accion => $accionLabel)
                        <th class="px-4 py-3 text-center font-semibold">
                            {{ $accionLabel }}
                            <button type="button" title="Marcar toda la columna"
                                    onclick="marcarColumnaPermisos('{{ $idPrefix }}', '{{ $accion }}', true)"
                                    class="block mx-auto mt-1 text-[11px] text-gray-400 hover:text-brand-700 normal-case">
                                todos
                            </button>
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($modulosPermisos as $modulo => $grupo)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 text-gray-700 whitespace-nowrap text-base">
                        <i class="fas {{ $grupo['icon'] }} text-gray-400 w-4 text-center mr-2"></i>{{ $grupo['label'] }}
                    </td>
                    @foreach(\App\Models\Permiso::ACCIONES as $accion => $accionLabel)
                        <td class="px-4 py-3 text-center">
                            @if(isset($grupo['permisos'][$accion]))
                                @php $permiso = $grupo['permisos'][$accion]; @endphp
                                <input type="checkbox" name="permisos[]" value="{{ $permiso->id }}"
                                       data-matriz="{{ $idPrefix }}" data-accion="{{ $accion }}"
                                       {{ in_array($permiso->id, $seleccionados) ? 'checked' : '' }}
                                       title="{{ $permiso->descripcion }}"
                                       class="w-5 h-5 rounded border-gray-300 text-brand-700 focus:ring-brand-400 cursor-pointer">
                            @else
                                <span class="text-gray-300">—</span>
                            @endif
                        </td>
                    @endforeach
                </tr>
                @empty
                <tr>
                    <td colspan="{{ count(\App\Models\Permiso::ACCIONES) + 1 }}" class="px-4 py-8 text-center text-gray-400 text-sm">
                        No hay permisos cargados todavía.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@once
@push('scripts')
<script>
    function marcarTodosPermisos(prefix, marcar) {
        document.querySelectorAll('input[data-matriz="' + prefix + '"]').forEach(function (cb) {
            cb.checked = marcar;
        });
    }

    function marcarColumnaPermisos(prefix, accion, marcar) {
        document.querySelectorAll('input[data-matriz="' + prefix + '"][data-accion="' + accion + '"]').forEach(function (cb) {
            cb.checked = marcar;
        });
    }
</script>
@endpush
@endonce
