@extends('layouts.app')

@section('title', 'Reporte Pasantes')
@section('header', 'Reporte Pasantes')

@php $puedeAdministrar = auth()->user()->rol?->nombre !== 'Pasante'; @endphp

@section('header-actions')
    @if($tienePropios)
    <div class="flex items-center gap-2">
        <a href="{{ route('reportes.pasantes.pdf', array_filter(request()->only('tipo_gasto_id', 'desde'))) }}"
           onclick="setTimeout(() => location.reload(), 1000)"
           class="bg-red-700 hover:bg-red-800 text-white px-4 py-2 rounded-lg text-sm flex items-center gap-2"
           title="El PDF cubre hasta hoy: ese período queda bloqueado y no se puede volver a generar.">
            <i class="fas fa-file-pdf"></i> Descargar PDF
        </a>
    </div>
    @endif
@endsection

@section('content')

@if($tienePropios)
<div class="flex items-center gap-3 mb-4">
    <h2 class="text-lg font-semibold text-gray-800 flex items-center gap-2 whitespace-nowrap">
        <i class="fas fa-coins text-amber-600"></i> Mis gastos
    </h2>
    <div class="flex-1 border-t border-gray-200"></div>
</div>

<div class="bg-white rounded-xl shadow-sm p-5 mb-6">
    <p class="text-sm text-gray-500 mb-3">Mis gastos registrados — {{ auth()->user()->name }}</p>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-amber-100 text-amber-600 rounded-xl flex items-center justify-center text-xl flex-shrink-0">
                <i class="fas fa-coins"></i>
            </div>
            <div>
                <p class="text-2xl font-bold text-gray-800">{{ number_format($resumen['total_monto'], 2) }} Bs</p>
                <p class="text-sm text-gray-500">Total gastado (filtro actual)</p>
            </div>
        </div>
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-brand-100 text-brand-700 rounded-xl flex items-center justify-center text-xl flex-shrink-0">
                <i class="fas fa-list-check"></i>
            </div>
            <div>
                <p class="text-2xl font-bold text-gray-800">{{ $resumen['total_gastos'] }}</p>
                <p class="text-sm text-gray-500">Gastos registrados</p>
            </div>
        </div>
    </div>
</div>

<form method="GET" class="bg-white rounded-xl shadow-sm p-4 mb-6 flex flex-wrap gap-3 items-end">
    <div>
        <label class="block text-xs text-gray-500 mb-1">Tipo de gasto</label>
        <select name="tipo_gasto_id" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
            <option value="">Todos</option>
            @foreach($tiposGasto as $t)
                <option value="{{ $t->id }}" {{ request('tipo_gasto_id') == $t->id ? 'selected' : '' }}>{{ $t->nombre }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-xs text-gray-500 mb-1">Desde</label>
        @if($siguienteDesde)
        <input type="date" name="desde" value="{{ $siguienteDesde->toDateString() }}" readonly
               title="Fijo: día siguiente al último PDF generado"
               class="border border-gray-300 rounded-lg px-3 py-2 text-sm bg-gray-100 text-gray-600 cursor-not-allowed">
        @else
        <input type="date" name="desde" value="{{ request('desde') }}"
               onchange="if (this.value && this.form.hasta.value) this.form.submit()"
               class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
        @endif
    </div>
    <div>
        <label class="block text-xs text-gray-500 mb-1">Hasta</label>
        <input type="date" name="hasta" value="{{ $hastaFijo->toDateString() }}" readonly
               title="Fijo: el PDF siempre cubre hasta hoy"
               class="border border-gray-300 rounded-lg px-3 py-2 text-sm bg-gray-100 text-gray-600 cursor-not-allowed">
    </div>
    <button type="submit" class="bg-brand-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-brand-700">
        <i class="fas fa-search"></i> Filtrar
    </button>
    <a href="{{ route('reportes.pasantes') }}" class="text-gray-500 text-sm py-2 hover:text-gray-700">
        <i class="fas fa-times"></i> Limpiar
    </a>
    @if($siguienteDesde)
    <p class="w-full text-xs text-gray-500">
        <i class="fas fa-circle-info mr-1"></i>
        @if($siguienteDesde->gt($hastaFijo))
            Ya generaste un PDF que cubre hasta hoy. El próximo empieza el {{ $siguienteDesde->format('d/m/Y') }}.
        @else
            El próximo PDF cubre del {{ $siguienteDesde->format('d/m/Y') }} (día siguiente al último que generaste) hasta hoy, {{ $hastaFijo->format('d/m/Y') }}.
        @endif
    </p>
    @endif
</form>

<div class="space-y-6">
    @forelse($grupos as $grupo)
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="p-5 border-b flex items-center justify-between flex-wrap gap-2">
            <div>
                @if($grupo->ruta_show)
                <a href="{{ $grupo->ruta_show }}" class="font-semibold text-gray-800 hover:text-brand-700">
                    <span class="font-mono text-xs {{ $grupo->tipo === 'expediente' ? 'text-brand-800' : 'text-cyan-700' }} mr-2">{{ $grupo->codigo_display }}</span>
                    {{ $grupo->titulo_display }}
                </a>
                @else
                <span class="font-semibold text-gray-400">{{ $grupo->titulo_display }}</span>
                @endif
                <p class="text-xs text-gray-500 mt-0.5">{{ $grupo->cliente?->nombre_completo ?? '—' }}</p>
            </div>
            <span class="text-sm font-bold text-amber-800">{{ number_format($grupo->total, 2) }} Bs</span>
        </div>
        <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-2 text-left font-semibold text-gray-600">Fecha</th>
                    <th class="px-4 py-2 text-left font-semibold text-gray-600">Concepto</th>
                    <th class="px-4 py-2 text-left font-semibold text-gray-600">Tipo de gasto</th>
                    <th class="px-4 py-2 text-right font-semibold text-gray-600">Monto</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($grupo->gastos as $gasto)
                <tr class="hover:bg-gray-50 transition">
                    <td class="px-4 py-2 text-gray-600 whitespace-nowrap">{{ $gasto->fecha->format('d/m/Y') }}</td>
                    <td class="px-4 py-2 text-gray-700">{{ $gasto->concepto }}</td>
                    <td class="px-4 py-2 text-gray-600">{{ $gasto->tipoGasto?->nombre ?? '—' }}</td>
                    <td class="px-4 py-2 text-right text-amber-700 font-medium">{{ number_format($gasto->monto, 2) }} Bs</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        </div>
    </div>
    @empty
    <div class="bg-white rounded-xl shadow-sm p-10 text-center text-gray-400">
        <i class="fas fa-coins text-3xl mb-2"></i>
        <p>No registraste gastos para los filtros aplicados.</p>
    </div>
    @endforelse
</div>
@endif

<div class="flex items-center gap-3 mt-8 mb-4">
    <h2 class="text-lg font-semibold text-gray-800 flex items-center gap-2 whitespace-nowrap">
        <i class="fas fa-file-pdf text-red-700"></i> Reportes en PDF
    </h2>
    <div class="flex-1 border-t border-gray-200"></div>
</div>

@if($puedeAdministrar)
<form method="GET" class="bg-white rounded-xl shadow-sm p-4 mb-6 flex flex-wrap gap-3 items-end">
    <div>
        <label class="block text-xs text-gray-500 mb-1">Pasante</label>
        <select name="usuario_id" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
            <option value="">Todos</option>
            @foreach($usuariosPasantes as $u)
                <option value="{{ $u->id }}" {{ request('usuario_id') == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
            @endforeach
        </select>
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
    <a href="{{ route('reportes.pasantes') }}" class="text-gray-500 text-sm py-2 hover:text-gray-700">
        <i class="fas fa-times"></i> Limpiar
    </a>
</form>
@endif

@php
    $columnas = ($puedeAdministrar ? 5 : 4) + 1; // + Acciones
@endphp

<div class="bg-white rounded-xl shadow-sm overflow-hidden mt-6">
    <div class="p-5 border-b">
        <h3 class="font-semibold text-gray-700">
            <i class="fas fa-clock-rotate-left text-orange-500 mr-2"></i>
            Pendientes de revisar ({{ $periodosPendientes->count() }})
        </h3>
    </div>
    <div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200 text-sm">
        <thead class="bg-gray-50">
            <tr>
                @if($puedeAdministrar)
                <th class="px-4 py-3 text-left font-semibold text-gray-600">Pasante</th>
                @endif
                <th class="px-4 py-3 text-left font-semibold text-gray-600">Expediente / Trámite</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">Período cubierto</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">Generado</th>
                <th class="px-4 py-3 text-right font-semibold text-gray-600">Total</th>
                <th class="px-4 py-3 text-right font-semibold text-gray-600">Acciones</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse($periodosPendientes as $periodo)
            <tr class="hover:bg-gray-50 transition">
                @if($puedeAdministrar)
                <td class="px-4 py-3 text-gray-700 whitespace-nowrap">{{ $periodo->usuario?->name ?? '—' }}</td>
                @endif
                <td class="px-4 py-3 text-gray-700">
                    @forelse($periodo->casos as $caso)
                        <p>{{ $caso }}</p>
                    @empty
                        <span class="text-gray-400">—</span>
                    @endforelse
                </td>
                <td class="px-4 py-3 text-gray-600 whitespace-nowrap">
                    {{ $periodo->desde->format('d/m/Y') }} — {{ $periodo->hasta->format('d/m/Y') }}
                    @if($periodo->corregido_at)
                    <p class="text-xs text-gray-400" title="&quot;Hasta&quot; original: {{ $periodo->hasta_original?->format('d/m/Y') }}">
                        Corregido por {{ $periodo->corregidoPor?->name ?? '—' }} el {{ $periodo->corregido_at->format('d/m/Y') }}
                        (antes hasta {{ $periodo->hasta_original?->format('d/m/Y') }})
                    </p>
                    @elseif($puedeAdministrar && $periodo->corregible)
                    <p class="text-xs text-amber-600" title="Cubre días posteriores al día en que se generó">
                        <i class="fas fa-triangle-exclamation"></i> Termina después de generarse
                    </p>
                    @endif
                </td>
                <td class="px-4 py-3 text-gray-500 whitespace-nowrap">{{ $periodo->created_at->format('d/m/Y H:i') }}</td>
                <td class="px-4 py-3 text-right font-bold text-amber-800 whitespace-nowrap">{{ number_format($periodo->total, 2) }} Bs</td>
                <td class="px-4 py-3 text-right whitespace-nowrap">
                    <a href="{{ route('reportes.pasantes.ver', $periodo) }}" target="_blank"
                       class="text-xs text-red-700 hover:underline font-medium mr-3">
                        <i class="fas fa-file-pdf"></i> Ver PDF
                    </a>
                    @if($periodo->usuario_id === auth()->id())
                    <button type="button" onclick="abrirModal('modal-editar-conceptos-{{ $periodo->id }}')"
                            class="text-xs text-gray-600 hover:underline font-medium">
                        <i class="fas fa-pen"></i> Editar concepto
                    </button>
                    @endif
                    @if($puedeAdministrar)
                    @if($periodo->corregible)
                    <button type="button" onclick="abrirModal('modal-corregir-periodo-{{ $periodo->id }}')"
                            class="text-xs text-amber-700 hover:underline font-medium mr-3">
                        <i class="fas fa-calendar-days"></i> Corregir período
                    </button>
                    @endif
                    <a href="{{ route('reportes.pasantes.ver-cliente', $periodo) }}" target="_blank"
                       class="text-xs text-brand-700 hover:underline font-medium mr-3">
                        <i class="fas fa-file-invoice"></i> Generar PDF cliente
                    </a>
                    <form method="POST" action="{{ route('reportes.pasantes.revisado', $periodo) }}" class="inline">
                        @csrf @method('PATCH')
                        <button type="submit" class="text-xs text-green-600 hover:text-green-800 font-medium">
                            <i class="fas fa-check"></i> Marcar revisado
                        </button>
                    </form>
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="{{ $columnas }}" class="px-4 py-10 text-center text-gray-400">
                    <i class="fas fa-clock-rotate-left text-3xl mb-2"></i>
                    <p>{{ $puedeAdministrar ? 'No hay PDFs pendientes de revisar.' : 'Todavía no generaste ningún PDF.' }}</p>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
    </div>
</div>

    @foreach($periodosPendientes as $periodo)
    @continue($periodo->usuario_id !== auth()->id())
    {{-- Modal: corregir el concepto de los gastos de este período. El monto y la fecha
         no son editables acá — el total ya quedó fijado al generar el PDF. --}}
    <div id="modal-editar-conceptos-{{ $periodo->id }}" class="hidden fixed inset-0 z-50 overflow-y-auto">
        <div class="fixed inset-0 bg-black/50" onclick="cerrarModal('modal-editar-conceptos-{{ $periodo->id }}')"></div>
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative bg-white rounded-xl shadow-lg w-full max-w-lg max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between px-6 py-4 border-b sticky top-0 bg-white rounded-t-xl">
                    <h3 class="font-semibold text-gray-800 text-lg">
                        <i class="fas fa-pen text-gray-600 mr-2"></i> Corregir concepto
                    </h3>
                    <button type="button" onclick="cerrarModal('modal-editar-conceptos-{{ $periodo->id }}')" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-lg"></i>
                    </button>
                </div>
                <form method="POST" action="{{ route('reportes.pasantes.editar-conceptos', $periodo) }}" class="p-6 space-y-4">
                    @csrf @method('PATCH')
                    @foreach($periodo->gastosLista as $gasto)
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">
                            {{ $gasto->tipoGasto?->nombre ?? '—' }} · {{ number_format($gasto->monto, 2) }} Bs · {{ $gasto->fecha->format('d/m/Y') }}
                        </label>
                        <input type="text" name="conceptos[{{ $gasto->id }}]" value="{{ old('conceptos.' . $gasto->id, $gasto->concepto) }}"
                               maxlength="200" required
                               class="w-full rounded-lg border-gray-300 text-sm focus:ring-brand-500 focus:border-brand-500">
                    </div>
                    @endforeach
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" onclick="cerrarModal('modal-editar-conceptos-{{ $periodo->id }}')" class="text-sm text-gray-600 px-3 py-2">Cancelar</button>
                        <button type="submit" class="text-sm bg-brand-600 hover:bg-brand-700 text-white px-4 py-2 rounded-lg font-medium">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endforeach

<div class="bg-white rounded-xl shadow-sm overflow-hidden mt-6">
    <div class="p-5 border-b flex items-center justify-between flex-wrap gap-2">
        <h3 class="font-semibold text-gray-700">
            <i class="fas fa-check-double text-green-600 mr-2"></i>
            Revisados ({{ $periodosRevisados->count() }})
        </h3>
        @if($puedeAdministrar)
        <div class="flex items-center gap-2">
            <button type="button" id="btn-pdf-combinado-estudio" onclick="generarPdfCombinado('estudio')" disabled
                    class="text-xs bg-gray-700 hover:bg-gray-800 disabled:bg-gray-300 disabled:cursor-not-allowed text-white px-3 py-2 rounded-lg font-medium flex items-center gap-2"
                    title="Elegí dos o más períodos con el check de la izquierda para juntarlos en un solo informe interno (con pasante y estado 'Por Cobrar')">
                <i class="fas fa-file-lines"></i> PDF combinado para el estudio (<span id="contador-pdf-combinado-estudio">0</span>)
            </button>
            <button type="button" id="btn-pdf-combinado" onclick="generarPdfCombinado('cliente')" disabled
                    class="text-xs bg-brand-600 hover:bg-brand-700 disabled:bg-gray-300 disabled:cursor-not-allowed text-white px-3 py-2 rounded-lg font-medium flex items-center gap-2"
                    title="Elegí dos o más períodos con el check de la izquierda para juntarlos en un solo PDF para el cliente">
                <i class="fas fa-file-invoice"></i> Generar PDF combinado (<span id="contador-pdf-combinado">0</span>)
            </button>
        </div>
        @endif
    </div>
    <div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200 text-sm">
        <thead class="bg-gray-50">
            <tr>
                @if($puedeAdministrar)
                <th class="px-4 py-3 w-4"></th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">Pasante</th>
                @endif
                <th class="px-4 py-3 text-left font-semibold text-gray-600">Expediente / Trámite</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">Período cubierto</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">Revisado</th>
                <th class="px-4 py-3 text-right font-semibold text-gray-600">Total</th>
                <th class="px-4 py-3 text-right font-semibold text-gray-600">Acciones</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse($periodosRevisados as $periodo)
            <tr class="hover:bg-gray-50 transition">
                @if($puedeAdministrar)
                <td class="px-4 py-3">
                    <input type="checkbox" class="chk-periodo-revisado" value="{{ $periodo->id }}" onchange="actualizarBotonPdfCombinado()">
                </td>
                <td class="px-4 py-3 text-gray-700 whitespace-nowrap">{{ $periodo->usuario?->name ?? '—' }}</td>
                @endif
                <td class="px-4 py-3 text-gray-700">
                    @forelse($periodo->casos as $caso)
                        <p>{{ $caso }}</p>
                    @empty
                        <span class="text-gray-400">—</span>
                    @endforelse
                </td>
                <td class="px-4 py-3 text-gray-600 whitespace-nowrap">
                    {{ $periodo->desde->format('d/m/Y') }} — {{ $periodo->hasta->format('d/m/Y') }}
                    @if($periodo->corregido_at)
                    <p class="text-xs text-gray-400" title="&quot;Hasta&quot; original: {{ $periodo->hasta_original?->format('d/m/Y') }}">
                        Corregido por {{ $periodo->corregidoPor?->name ?? '—' }} el {{ $periodo->corregido_at->format('d/m/Y') }}
                        (antes hasta {{ $periodo->hasta_original?->format('d/m/Y') }})
                    </p>
                    @elseif($puedeAdministrar && $periodo->corregible)
                    <p class="text-xs text-amber-600" title="Cubre días posteriores al día en que se generó">
                        <i class="fas fa-triangle-exclamation"></i> Termina después de generarse
                    </p>
                    @endif
                </td>
                <td class="px-4 py-3 text-gray-500 whitespace-nowrap">{{ $periodo->revisado_at?->format('d/m/Y H:i') }}</td>
                <td class="px-4 py-3 text-right font-bold text-amber-800 whitespace-nowrap">{{ number_format($periodo->total, 2) }} Bs</td>
                <td class="px-4 py-3 text-right whitespace-nowrap">
                    <a href="{{ route('reportes.pasantes.ver', $periodo) }}" target="_blank"
                       class="text-xs text-red-700 hover:underline font-medium {{ $puedeAdministrar ? 'mr-3' : '' }}">
                        <i class="fas fa-file-pdf"></i> Ver PDF
                    </a>
                    @if($puedeAdministrar)
                    @if($periodo->corregible)
                    <button type="button" onclick="abrirModal('modal-corregir-periodo-{{ $periodo->id }}')"
                            class="text-xs text-amber-700 hover:underline font-medium mr-3">
                        <i class="fas fa-calendar-days"></i> Corregir período
                    </button>
                    @endif
                    <a href="{{ route('reportes.pasantes.ver-cliente', $periodo) }}" target="_blank"
                       class="text-xs text-brand-700 hover:underline font-medium mr-3">
                        <i class="fas fa-file-invoice"></i> Generar PDF cliente
                    </a>
                    <form method="POST" action="{{ route('reportes.pasantes.destroy', $periodo) }}" class="inline" onsubmit="return confirm('¿Eliminar este reporte? Esta acción no se puede deshacer.')">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-xs text-red-600 hover:text-red-800 font-medium">
                            <i class="fas fa-trash"></i> Eliminar
                        </button>
                    </form>
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="{{ $columnas + ($puedeAdministrar ? 1 : 0) }}" class="px-4 py-10 text-center text-gray-400">
                    <i class="fas fa-check-double text-3xl mb-2"></i>
                    <p>Todavía no hay PDFs revisados.</p>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
    </div>
</div>

@if($puedeAdministrar)
    @foreach($periodosPendientes->concat($periodosRevisados)->where('corregible', true) as $periodo)
    @php
        $generado    = $periodo->created_at->copy()->startOfDay();
        $sinArreglo  = $periodo->desde->gt($generado);
    @endphp
    {{-- Modal: acortar el "Hasta" de un PDF que se generó cubriendo días futuros. Los
         gastos del PDF no cambian; el que quede después del nuevo "Hasta" se mueve de
         fecha en el mismo paso (ver ReporteController::pasantesCorregirPeriodo). --}}
    <div id="modal-corregir-periodo-{{ $periodo->id }}" class="hidden fixed inset-0 z-50 overflow-y-auto">
        <div class="fixed inset-0 bg-black/50" onclick="cerrarModal('modal-corregir-periodo-{{ $periodo->id }}')"></div>
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative bg-white rounded-xl shadow-lg w-full max-w-2xl max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between px-6 py-4 border-b sticky top-0 bg-white rounded-t-xl">
                    <h3 class="font-semibold text-gray-800 text-lg">
                        <i class="fas fa-calendar-days text-amber-600 mr-2"></i> Corregir período — {{ $periodo->usuario?->name }}
                    </h3>
                    <button type="button" onclick="cerrarModal('modal-corregir-periodo-{{ $periodo->id }}')" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-lg"></i>
                    </button>
                </div>

                <div class="p-6 space-y-4">
                    <p class="text-sm text-gray-600">
                        Este PDF se generó el <strong>{{ $periodo->created_at->format('d/m/Y') }}</strong>, pero cubre hasta el
                        <strong>{{ $periodo->hasta->format('d/m/Y') }}</strong>. Mientras tanto, los gastos de esos días no pueden
                        entrar en el próximo PDF del pasante.
                    </p>

                    @if($sinArreglo)
                    <p class="text-sm text-red-700 bg-red-50 rounded-lg p-3">
                        El período empieza el {{ $periodo->desde->format('d/m/Y') }}, después del día en que se generó, así que no se puede
                        corregir acortando el "Hasta".
                    </p>
                    @else
                    <form method="POST" action="{{ route('reportes.pasantes.corregir-periodo', $periodo) }}" class="space-y-4 form-corregir-periodo">
                        @csrf @method('PATCH')
                        <div class="flex flex-wrap gap-4 items-end">
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">Desde</label>
                                <input type="date" value="{{ $periodo->desde->toDateString() }}" readonly
                                       class="border border-gray-300 rounded-lg px-3 py-2 text-sm bg-gray-100 text-gray-600 cursor-not-allowed">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">Nuevo "Hasta"</label>
                                <input type="date" name="hasta" required data-hasta
                                       value="{{ $generado->toDateString() }}"
                                       min="{{ $periodo->desde->toDateString() }}" max="{{ $generado->toDateString() }}"
                                       class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                            </div>
                            <p class="text-xs text-gray-500 pb-2">
                                Entre el {{ $periodo->desde->format('d/m/Y') }} y el {{ $generado->format('d/m/Y') }}.
                                El próximo PDF del pasante empieza el día siguiente.
                            </p>
                        </div>

                        <div data-sin-fuera class="text-sm text-green-700 bg-green-50 rounded-lg p-3">
                            <i class="fas fa-check"></i> Todos los gastos de este PDF quedan dentro del nuevo período.
                        </div>
                        <div data-con-fuera class="hidden space-y-2">
                            <p class="text-sm text-amber-700">
                                Estos gastos tienen fecha posterior al nuevo "Hasta". Elegí la fecha correcta para cada uno; también
                                se corrige la fecha de la actuación donde se cargaron.
                            </p>
                            <table class="min-w-full text-sm divide-y divide-gray-100">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-3 py-2 text-left font-semibold text-gray-600">Gasto</th>
                                        <th class="px-3 py-2 text-left font-semibold text-gray-600">Fecha actual</th>
                                        <th class="px-3 py-2 text-left font-semibold text-gray-600">Fecha correcta</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($periodo->gastosLista as $gasto)
                                    <tr data-gasto data-fecha="{{ $gasto->fecha->toDateString() }}" data-seguimiento="{{ $gasto->seguimiento_id }}">
                                        <td class="px-3 py-2 text-gray-700">
                                            {{ $gasto->concepto }} · {{ number_format($gasto->monto, 2) }} Bs
                                            <span class="block text-xs text-gray-400">{{ $gasto->expediente?->numero ?? $gasto->tramite?->codigo }}</span>
                                        </td>
                                        <td class="px-3 py-2 text-red-700 whitespace-nowrap">{{ $gasto->fecha->format('d/m/Y') }}</td>
                                        <td class="px-3 py-2">
                                            <input type="date" name="fechas[{{ $gasto->id }}]" min="{{ $periodo->desde->toDateString() }}"
                                                   class="border border-gray-300 rounded-lg px-2 py-1 text-sm focus:ring-2 focus:ring-brand-400">
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="flex justify-end gap-2 pt-2">
                            <button type="button" onclick="cerrarModal('modal-corregir-periodo-{{ $periodo->id }}')" class="text-sm text-gray-600 px-3 py-2">Cancelar</button>
                            <button type="submit" class="text-sm bg-amber-600 hover:bg-amber-700 text-white px-4 py-2 rounded-lg font-medium"
                                    onclick="return confirm('¿Corregir el período de este PDF? Queda registrado quién lo corrigió.')">Guardar corrección</button>
                        </div>
                    </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endforeach
@endif

@endsection

@push('scripts')
<script>
// Corregir período: solo se muestran (y se exigen) los gastos cuya fecha queda después del
// nuevo "Hasta"; los gastos de una misma actuación se mueven juntos.
document.querySelectorAll('.form-corregir-periodo').forEach(form => {
    const hasta = form.querySelector('[data-hasta]');
    const filas = form.querySelectorAll('[data-gasto]');

    function actualizar() {
        let fuera = 0;
        filas.forEach(fila => {
            const input = fila.querySelector('input');
            const queda = hasta.value && fila.dataset.fecha > hasta.value;
            fila.classList.toggle('hidden', !queda);
            input.disabled = !queda;
            input.required = queda;
            input.max = hasta.value;
            if (queda) fuera++;
        });
        form.querySelector('[data-con-fuera]').classList.toggle('hidden', fuera === 0);
        form.querySelector('[data-sin-fuera]').classList.toggle('hidden', fuera > 0);
    }

    filas.forEach(fila => fila.querySelector('input').addEventListener('change', e => {
        if (!fila.dataset.seguimiento) return;
        filas.forEach(otra => {
            if (otra !== fila && otra.dataset.seguimiento === fila.dataset.seguimiento) otra.querySelector('input').value = e.target.value;
        });
    }));

    hasta.addEventListener('change', actualizar);
    actualizar();
});
</script>
@endpush

@push('scripts')
<script>
function actualizarBotonPdfCombinado() {
    const marcados = document.querySelectorAll('.chk-periodo-revisado:checked').length;

    [
        ['btn-pdf-combinado', 'contador-pdf-combinado'],
        ['btn-pdf-combinado-estudio', 'contador-pdf-combinado-estudio'],
    ].forEach(([botonId, contadorId]) => {
        const boton = document.getElementById(botonId);
        const contador = document.getElementById(contadorId);
        if (!boton) return;

        contador.textContent = marcados;
        boton.disabled = marcados < 2;
    });
}

function generarPdfCombinado(formato) {
    const ids = Array.from(document.querySelectorAll('.chk-periodo-revisado:checked')).map(c => c.value);
    if (ids.length < 2) return;

    const ruta = formato === 'estudio'
        ? '{{ route('reportes.pasantes.ver-combinado') }}'
        : '{{ route('reportes.pasantes.ver-cliente-combinado') }}';

    window.open(ruta + '?periodos=' + ids.join(','), '_blank');
}
</script>
@endpush
