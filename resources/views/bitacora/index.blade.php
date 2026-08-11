@extends('layouts.app')

@section('title', 'Bitácora')
@section('header', 'Bitácora de Auditoría')

@section('header-actions')
    <form method="POST" action="{{ route('bitacora.limpiar') }}"
          onsubmit="return confirm('¿Eliminar todos los registros de bitácora anteriores a la antigüedad indicada? Esta acción no se puede deshacer.');"
          class="flex items-center gap-2">
        @csrf
        @method('DELETE')
        <label class="text-xs text-gray-500">Eliminar anteriores a</label>
        <input type="number" name="dias" value="180" min="1" max="3650"
               class="w-20 border border-gray-300 rounded-lg px-2 py-2 text-sm">
        <span class="text-xs text-gray-500">días</span>
        <button type="submit" class="bg-red-700 hover:bg-red-800 text-white px-4 py-2 rounded-lg text-sm flex items-center gap-2">
            <i class="fas fa-broom"></i> Limpiar
        </button>
    </form>
@endsection

@section('content')

<form method="GET" class="bg-white rounded-xl shadow-sm p-4 mb-6 flex flex-wrap gap-3 items-end">
    <div>
        <label class="block text-xs text-gray-500 mb-1">Usuario</label>
        <select name="usuario_id" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
            <option value="">Todos</option>
            @foreach($usuarios as $u)
                <option value="{{ $u->id }}" {{ request('usuario_id') == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-xs text-gray-500 mb-1">Acción</label>
        <select name="accion" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
            <option value="">Todas</option>
            @foreach(\App\Models\Bitacora::ACCIONES as $valor => $etiqueta)
                <option value="{{ $valor }}" {{ request('accion') === $valor ? 'selected' : '' }}>{{ $etiqueta }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-xs text-gray-500 mb-1">Módulo</label>
        <select name="modelo" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
            <option value="">Todos</option>
            @foreach(\App\Models\Bitacora::MODELOS as $valor => $etiqueta)
                <option value="{{ $valor }}" {{ request('modelo') === $valor ? 'selected' : '' }}>{{ $etiqueta }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-xs text-gray-500 mb-1">Buscar</label>
        <input type="text" name="buscar" value="{{ request('buscar') }}" placeholder="Descripción..."
               class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
    </div>
    <div>
        <label class="block text-xs text-gray-500 mb-1">Desde</label>
        <input type="date" name="desde" value="{{ request('desde') }}" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
    </div>
    <div>
        <label class="block text-xs text-gray-500 mb-1">Hasta</label>
        <input type="date" name="hasta" value="{{ request('hasta') }}" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
    </div>
    <button type="submit" class="bg-brand-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-brand-700">
        <i class="fas fa-search"></i> Filtrar
    </button>
    <a href="{{ route('bitacora.index') }}" class="text-gray-500 text-sm py-2 hover:text-gray-700">
        <i class="fas fa-times"></i> Limpiar
    </a>
</form>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="p-5 border-b flex items-center justify-between">
        <h3 class="font-semibold text-gray-700">
            <i class="fas fa-clipboard-list text-brand-700 mr-2"></i>
            Registros ({{ $registros->total() }})
        </h3>
        <span class="text-xs text-gray-400">Página {{ $registros->currentPage() }} de {{ $registros->lastPage() }}</span>
    </div>
    <div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200 text-sm">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-4 py-3 text-left font-semibold text-gray-600 whitespace-nowrap">Fecha</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">Usuario</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">Acción</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">Módulo</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">Descripción</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">IP</th>
                <th class="px-4 py-3 text-right font-semibold text-gray-600">Detalle</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse($registros as $registro)
            @php
                $colorAccion = match($registro->accion) {
                    'creado'        => 'bg-green-100 text-green-700',
                    'actualizado'   => 'bg-amber-100 text-amber-700',
                    'eliminado'     => 'bg-red-100 text-red-700',
                    'inicio_sesion' => 'bg-cyan-100 text-cyan-700',
                    'cierre_sesion' => 'bg-gray-100 text-gray-600',
                    default         => 'bg-gray-100 text-gray-600',
                };
                $tieneDetalle = $registro->datos_anteriores || $registro->datos_nuevos;
            @endphp
            <tr class="hover:bg-gray-50 transition">
                <td class="px-4 py-3 text-gray-600 whitespace-nowrap">{{ $registro->created_at->format('d/m/Y H:i') }}</td>
                <td class="px-4 py-3 text-gray-700">{{ $registro->usuario?->name ?? '—' }}</td>
                <td class="px-4 py-3">
                    <span class="px-2 py-0.5 rounded text-xs font-medium {{ $colorAccion }}">{{ $registro->accion_label }}</span>
                </td>
                <td class="px-4 py-3 text-gray-600">{{ $registro->modelo_label ?? '—' }}</td>
                <td class="px-4 py-3 text-gray-700">{{ $registro->descripcion }}</td>
                <td class="px-4 py-3 text-gray-400 font-mono text-xs whitespace-nowrap">{{ $registro->ip ?? '—' }}</td>
                <td class="px-4 py-3 text-right">
                    @if($tieneDetalle)
                    <button type="button"
                            onclick="document.getElementById('detalle-{{ $registro->id }}').classList.toggle('hidden')"
                            class="text-xs text-gray-500 hover:text-brand-700 font-medium whitespace-nowrap">
                        <i class="fas fa-eye"></i> Ver
                    </button>
                    @else
                    <span class="text-xs text-gray-300">—</span>
                    @endif
                </td>
            </tr>
            @if($tieneDetalle)
            <tr id="detalle-{{ $registro->id }}" class="hidden bg-gray-50">
                <td colspan="7" class="px-4 py-3">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                        @if($registro->datos_anteriores_legibles)
                        <div>
                            <p class="font-semibold text-gray-500 uppercase tracking-wide mb-1">Antes</p>
                            <table class="w-full">
                                @foreach($registro->datos_anteriores_legibles as $campo => $valor)
                                <tr class="border-b border-gray-200">
                                    <td class="py-1 pr-2 text-gray-500 align-top whitespace-nowrap">{{ $campo }}</td>
                                    <td class="py-1 text-gray-700 break-words">{{ is_array($valor) ? json_encode($valor) : ($valor ?? '—') }}</td>
                                </tr>
                                @endforeach
                            </table>
                        </div>
                        @endif
                        @if($registro->datos_nuevos_legibles)
                        <div>
                            <p class="font-semibold text-gray-500 uppercase tracking-wide mb-1">Después</p>
                            <table class="w-full">
                                @foreach($registro->datos_nuevos_legibles as $campo => $valor)
                                <tr class="border-b border-gray-200">
                                    <td class="py-1 pr-2 text-gray-500 align-top whitespace-nowrap">{{ $campo }}</td>
                                    <td class="py-1 text-gray-700 break-words">{{ is_array($valor) ? json_encode($valor) : ($valor ?? '—') }}</td>
                                </tr>
                                @endforeach
                            </table>
                        </div>
                        @endif
                    </div>
                </td>
            </tr>
            @endif
            @empty
            <tr>
                <td colspan="7" class="px-4 py-10 text-center text-gray-400">
                    <i class="fas fa-clipboard-list text-3xl mb-2"></i>
                    <p>No hay registros para los filtros aplicados.</p>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
    </div>
    <div class="px-4 py-3 border-t">{{ $registros->links() }}</div>
</div>

@endsection
