{{-- Requiere: $expedientesListado. Se usa tanto en la carga normal de la página como
     en la respuesta AJAX del buscador en vivo (SeguimientoController@index). --}}
<div class="mb-6">
    <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-2">Expedientes</h2>
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Expediente</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600">NUREJ</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Demandante</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Demandado</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Tipo de proceso</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Juzgado</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Piso</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Dirección</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Abogado encargado</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Seguimiento</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($expedientesListado as $exp)
                <tr class="hover:bg-gray-50 transition">
                    <td class="px-4 py-3">
                        <a href="{{ route('expedientes.show', $exp) }}" class="text-brand-800 hover:underline font-medium">
                            {{ $exp->caratula }}
                        </a>
                        <p class="text-xs text-gray-500">{{ $exp->cliente->nombre_completo }}</p>
                    </td>
                    <td class="px-4 py-3 text-gray-600 font-mono text-xs">{{ $exp->numero }}</td>
                    <td class="px-4 py-3 text-gray-600 text-xs">{{ $exp->demandantes->pluck('nombre')->implode(', ') ?: '—' }}</td>
                    <td class="px-4 py-3 text-gray-600 text-xs">{{ $exp->demandados->pluck('nombre')->implode(', ') ?: '—' }}</td>
                    <td class="px-4 py-3 text-gray-600 text-xs">{{ $exp->tipo_proceso ?: '—' }}</td>
                    <td class="px-4 py-3 text-gray-600 text-xs">{{ $exp->juzgado ?: '—' }}</td>
                    <td class="px-4 py-3 text-gray-600 text-xs">{{ $exp->piso ?: '—' }}</td>
                    <td class="px-4 py-3 text-gray-600 text-xs">{{ $exp->direccion ?: '—' }}</td>
                    <td class="px-4 py-3 text-gray-600 text-xs">{{ $exp->abogado?->nombre ?? '—' }}</td>
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-2">
                            @if(auth()->user()->puede('seguimientos', 'crear'))
                            <a href="{{ route('seguimientos.index', ['nuevo' => 1, 'expediente_id' => $exp->id]) }}" class="text-xs text-green-600 hover:underline">+ Agregar</a>
                            @endif
                            <a href="{{ route('seguimientos.historial', $exp) }}" class="text-xs text-brand-700 hover:underline">Ver historial</a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="10" class="px-4 py-10 text-center text-gray-400">
                        <i class="fas fa-folder-open text-3xl mb-2"></i>
                        <p>No se encontraron expedientes.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        </div>
        <div class="px-4 py-3 border-t">{{ $expedientesListado->links() }}</div>
    </div>
</div>
