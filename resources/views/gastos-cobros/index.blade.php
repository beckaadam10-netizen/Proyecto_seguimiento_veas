@extends('layouts.app')

@section('title', 'Gastos y Cobros')
@section('header', 'Gastos y Cobros')

@section('content')

<form method="GET" class="bg-white rounded-xl shadow-sm p-4 mb-6 flex flex-wrap gap-3 items-end">
    <div class="flex-1 min-w-48">
        <label class="block text-xs text-gray-500 mb-1">Buscar</label>
        <input type="text" name="buscar" value="{{ request('buscar') }}" placeholder="Código, NUREJ, nombre o carátula..."
               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
    </div>
    <div>
        <label class="block text-xs text-gray-500 mb-1">Tipo</label>
        <select name="tipo" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
            <option value="">Todos</option>
            <option value="tramite" {{ request('tipo') === 'tramite' ? 'selected' : '' }}>Trámites</option>
            <option value="expediente" {{ request('tipo') === 'expediente' ? 'selected' : '' }}>Expedientes</option>
        </select>
    </div>
    <div>
        <label class="block text-xs text-gray-500 mb-1">Cliente</label>
        <select name="cliente_id" class="border border-gray-300 rounded-lg px-3 py-2 text-sm max-w-56">
            <option value="">Todos</option>
            @foreach($clientes as $c)
                <option value="{{ $c->id }}" {{ request('cliente_id') == $c->id ? 'selected' : '' }}>{{ $c->nombre_completo }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-xs text-gray-500 mb-1">Estado de pago</label>
        <select name="estado_pago" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
            <option value="">Todos</option>
            <option value="pendiente" {{ request('estado_pago') === 'pendiente' ? 'selected' : '' }}>Pendiente</option>
            <option value="parcial" {{ request('estado_pago') === 'parcial' ? 'selected' : '' }}>Parcialmente pagado</option>
            <option value="pagado" {{ request('estado_pago') === 'pagado' ? 'selected' : '' }}>Pagado</option>
        </select>
    </div>
    <button type="submit" class="bg-brand-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-brand-700">
        <i class="fas fa-search"></i> Filtrar
    </button>
    <a href="{{ route('gastos-cobros.index') }}" class="text-gray-500 text-sm py-2 hover:text-gray-700">
        <i class="fas fa-times"></i> Limpiar
    </a>
</form>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200 text-sm">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">Tipo</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">Código</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">Nro Expediente</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">Cliente</th>
                <!--<th class="px-4 py-3 text-center font-semibold text-gray-600">Estado</th>-->
                <th class="px-4 py-3 text-center font-semibold text-gray-600">Estado de pago</th>
                <th class="px-4 py-3 text-right font-semibold text-gray-600">Gastado</th>
                <th class="px-4 py-3 text-right font-semibold text-gray-600">Cobrado</th>
                <th class="px-4 py-3 text-right font-semibold text-gray-600">Saldo</th>
                <th class="px-4 py-3 text-right font-semibold text-gray-600">Acciones</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse($registros as $item)
            <tr class="hover:bg-gray-50 transition">
                <td class="px-4 py-3">
                    <span class="px-2 py-0.5 rounded text-xs font-medium {{ $item->tipo_registro === 'tramite' ? 'bg-cyan-100 text-cyan-700' : 'bg-purple-100 text-purple-700' }}">
                        {{ $item->tipo_registro === 'tramite' ? 'Trámite' : 'Expediente' }}
                    </span>
                </td>
                <td class="px-4 py-3">
                    <a href="{{ $item->ruta_show }}" class="font-mono text-xs {{ $item->tipo_registro === 'tramite' ? 'text-cyan-700' : 'text-brand-800' }} hover:underline">
                        {{ $item->codigo_display }}
                    </a>
                </td>
                <td class="px-4 py-3 text-gray-700">{{ Str::limit($item->titulo_display, 30) }}</td>
                <td class="px-4 py-3 text-gray-600">{{ $item->cliente->nombre_completo }}</td>
                <!--<td class="px-4 py-3 text-center">
                    <span class="px-2 py-0.5 rounded text-xs font-medium bg-{{ $item->estado_color }}-100 text-{{ $item->estado_color }}-700">
                        {{ $item->estado_label }}
                    </span>
                </td>-->
                <td class="px-4 py-3 text-center">
                    <span class="px-2 py-0.5 rounded text-xs font-medium bg-{{ $item->estado_pago_color }}-100 text-{{ $item->estado_pago_color }}-700">
                        {{ $item->estado_pago_label }}
                    </span>
                </td>
                <td class="px-4 py-3 text-right text-amber-700 font-medium">{{ number_format($item->total_gastos, 2) }} Bs</td>
                <td class="px-4 py-3 text-right text-emerald-700 font-medium">{{ number_format($item->total_cobrado, 2) }} Bs</td>
                <td class="px-4 py-3 text-right font-semibold {{ $item->saldo_pendiente > 0 ? 'text-red-600' : 'text-gray-500' }}">
                    {{ number_format($item->saldo_pendiente, 2) }} Bs
                </td>
                <td class="px-4 py-3 text-right">
                    @php
                        $puedeGestionarGastos = auth()->user()->puede('gastos_cobros', 'crear');
                        $puedeCobrar = auth()->user()->puede('gastos_cobros', 'cobrar');
                    @endphp
                    @if($puedeGestionarGastos || $puedeCobrar)
                    <div class="relative inline-block text-left">
                        <button type="button" onclick="toggleAccionesMenu(event, 'acciones-{{ $item->tipo_registro }}-{{ $item->id }}')"
                                class="text-gray-400 hover:text-gray-700 hover:bg-gray-100 w-8 h-8 rounded-lg inline-flex items-center justify-center">
                            <i class="fas fa-ellipsis-vertical"></i>
                        </button>
                        <div id="acciones-{{ $item->tipo_registro }}-{{ $item->id }}"
                             class="menu-acciones hidden absolute right-0 z-20 mt-1 w-52 bg-white rounded-lg shadow-lg border border-gray-100 py-1 text-left">
                            @if($puedeGestionarGastos)
                            <button type="button" onclick="cerrarMenusAcciones(); abrirModal('modal-gasto-nuevo-{{ $item->tipo_registro }}-{{ $item->id }}')"
                                    class="w-full text-left px-4 py-2 text-sm text-amber-700 hover:bg-gray-50 flex items-center gap-2">
                                <i class="fas fa-coins w-4"></i> Gasto
                            </button>
                            @endif
                            @if($puedeCobrar)
                            <button type="button" onclick="cerrarMenusAcciones(); abrirModal('modal-cobro-nuevo-{{ $item->tipo_registro }}-{{ $item->id }}')"
                                    class="w-full text-left px-4 py-2 text-sm text-emerald-700 hover:bg-gray-50 flex items-center gap-2">
                                <i class="fas fa-hand-holding-dollar w-4"></i> Cobrar
                            </button>
                            @endif
                            <a href="{{ $item->ruta_show }}"
                               class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 flex items-center gap-2">
                                <i class="fas fa-clock-rotate-left w-4"></i> Ver historial de pago
                            </a>
                        </div>
                    </div>
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="10" class="px-4 py-10 text-center text-gray-400">
                    <i class="fas fa-file-circle-check text-3xl mb-2"></i>
                    <p>No se encontraron trámites ni expedientes.</p>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
    </div>
    <div class="px-4 py-3 border-t">{{ $registros->links() }}</div>
</div>

{{-- Modales por registro: Nuevo Gasto / Cobrar --}}
@foreach($registros as $item)
@php $campoId = $item->tipo_registro === 'tramite' ? 'tramite_id' : 'expediente_id'; @endphp

<div id="modal-gasto-nuevo-{{ $item->tipo_registro }}-{{ $item->id }}" class="hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="fixed inset-0 bg-black/50" onclick="cerrarModal('modal-gasto-nuevo-{{ $item->tipo_registro }}-{{ $item->id }}')"></div>
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="relative bg-white rounded-xl shadow-lg w-full max-w-lg max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between px-6 py-4 border-b sticky top-0 bg-white rounded-t-xl">
                <h3 class="font-semibold text-gray-800 text-lg">
                    Nuevo Gasto <span class="text-xs font-normal text-gray-400 font-mono">{{ $item->codigo_display }}</span>
                </h3>
                <button type="button" onclick="cerrarModal('modal-gasto-nuevo-{{ $item->tipo_registro }}-{{ $item->id }}')" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>
            <form method="POST" action="{{ route('gastos.store') }}" class="p-6 space-y-4">
                @csrf
                <input type="hidden" name="_modal" value="modal-gasto-nuevo-{{ $item->tipo_registro }}-{{ $item->id }}">
                <input type="hidden" name="{{ $campoId }}" value="{{ $item->id }}">

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de gasto *</label>
                    <div class="flex gap-2">
                        <select name="tipo_gasto_id" id="select-tipo-gasto-nuevo-{{ $item->tipo_registro }}-{{ $item->id }}" required
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                            <option value="">— Seleccioná un tipo —</option>
                            @foreach($tiposGasto as $t)
                                <option value="{{ $t->id }}">{{ $t->nombre }}</option>
                            @endforeach
                        </select>
                        @if(auth()->user()->puede('parametros', 'crear'))
                        <button type="button" onclick="abrirModalTipoGastoRapido('select-tipo-gasto-nuevo-{{ $item->tipo_registro }}-{{ $item->id }}')"
                                title="Nuevo tipo de gasto"
                                class="shrink-0 border border-gray-300 rounded-lg w-9 h-9 flex items-center justify-center text-gray-500 hover:text-brand-700 hover:border-brand-400">
                            <i class="fas fa-plus"></i>
                        </button>
                        @endif
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Concepto *</label>
                    <input type="text" name="concepto" required placeholder="Ej: Arancel de inscripción"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Monto *</label>
                        <input type="number" name="monto" step="0.01" min="0" required
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Fecha *</label>
                        <input type="date" name="fecha" value="{{ date('Y-m-d') }}" required
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                    </div>
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="submit" class="bg-amber-600 hover:bg-amber-700 text-white px-6 py-2 rounded-lg font-medium">
                        <i class="fas fa-save mr-1"></i> Guardar Gasto
                    </button>
                    <button type="button" onclick="cerrarModal('modal-gasto-nuevo-{{ $item->tipo_registro }}-{{ $item->id }}')"
                            class="text-gray-600 hover:text-gray-800 px-4 py-2 rounded-lg border hover:bg-gray-50">
                        Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="modal-cobro-nuevo-{{ $item->tipo_registro }}-{{ $item->id }}" class="hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="fixed inset-0 bg-black/50" onclick="cerrarModal('modal-cobro-nuevo-{{ $item->tipo_registro }}-{{ $item->id }}')"></div>
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="relative bg-white rounded-xl shadow-lg w-full max-w-lg max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between px-6 py-4 border-b sticky top-0 bg-white rounded-t-xl">
                <h3 class="font-semibold text-gray-800 text-lg">
                    Cobrar <span class="text-xs font-normal text-gray-400 font-mono">{{ $item->codigo_display }}</span>
                </h3>
                <button type="button" onclick="cerrarModal('modal-cobro-nuevo-{{ $item->tipo_registro }}-{{ $item->id }}')" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>

            <div class="px-6 pt-4">
                <div class="bg-gray-50 rounded-lg p-4">
                    <div class="flex items-center justify-between mb-1.5">
                        <span class="text-xs font-semibold text-gray-500 uppercase">Resumen</span>
                        @if(auth()->user()->puede('gastos_cobros', 'ver'))
                        <a href="{{ $item->ruta_show }}"
                           class="text-xs text-brand-600 hover:underline font-medium flex items-center gap-1">
                            <i class="fas fa-clock-rotate-left"></i> Historial de pagos
                        </a>
                        @endif
                    </div>
                    <dl class="space-y-1.5 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-gray-500">Total gastado</dt>
                            <dd class="font-medium text-amber-700">{{ number_format($item->total_gastos, 2) }} Bs</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-500">Total cobrado</dt>
                            <dd class="font-medium text-emerald-700">{{ number_format($item->total_cobrado, 2) }} Bs</dd>
                        </div>
                        <div class="flex justify-between pt-1.5 border-t">
                            <dt class="text-gray-600 font-medium">Saldo pendiente</dt>
                            <dd class="font-semibold {{ $item->saldo_pendiente > 0 ? 'text-red-600' : 'text-gray-800' }}">
                                {{ number_format($item->saldo_pendiente, 2) }} Bs
                            </dd>
                        </div>
                        @if($item->estado_pago !== 'sin_gastos')
                        <div class="flex justify-between items-center pt-1.5 border-t">
                            <dt class="text-gray-600 font-medium">Estado de la cuenta</dt>
                            <dd>
                                <span class="px-2 py-0.5 rounded text-xs font-medium bg-{{ $item->estado_pago_color }}-100 text-{{ $item->estado_pago_color }}-700">
                                    {{ $item->estado_pago_label }}
                                </span>
                            </dd>
                        </div>
                        @endif
                    </dl>
                </div>
            </div>

            <form method="POST" action="{{ route('cobros.store') }}" class="p-6 pt-3 space-y-4"
                  id="form-cobro-nuevo-{{ $item->tipo_registro }}-{{ $item->id }}"
                  data-cliente-nombre="{{ $item->cliente->nombre_completo }}"
                  data-cliente-whatsapp="{{ $item->cliente->telefono_whatsapp }}"
                  data-item-tipo="{{ $item->tipo_registro }}"
                  data-item-codigo="{{ $item->codigo_display }}"
                  data-item-titulo="{{ $item->titulo_display }}"
                  data-saldo-pendiente="{{ $item->saldo_pendiente }}">
                @csrf
                <input type="hidden" name="_modal" value="modal-cobro-nuevo-{{ $item->tipo_registro }}-{{ $item->id }}">
                <input type="hidden" name="{{ $campoId }}" value="{{ $item->id }}">
                <input type="hidden" name="modo" id="modo-{{ $item->tipo_registro }}-{{ $item->id }}" value="total">

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">¿Cómo querés cobrar?</label>
                    <div class="flex gap-2">
                        <button type="button" data-modo="total" onclick="seleccionarModoCobro('{{ $item->tipo_registro }}-{{ $item->id }}', 'total')"
                                class="modo-btn-{{ $item->tipo_registro }}-{{ $item->id }} flex-1 px-3 py-2 rounded-lg text-sm font-medium border border-brand-600 bg-brand-600 text-white">
                            Cobro total
                        </button>
                        <button type="button" data-modo="abono" onclick="seleccionarModoCobro('{{ $item->tipo_registro }}-{{ $item->id }}', 'abono')"
                                class="modo-btn-{{ $item->tipo_registro }}-{{ $item->id }} flex-1 px-3 py-2 rounded-lg text-sm font-medium border border-gray-300 text-gray-700">
                            Abono
                        </button>
                    </div>
                </div>

                @php $gastosPendientes = $item->gastos->where('cubierto', false); @endphp
                <div id="panel-total-{{ $item->tipo_registro }}-{{ $item->id }}" class="modo-panel">
                    @if($item->saldo_pendiente > 0)
                        <p class="text-sm text-gray-600 bg-gray-50 rounded-lg p-3">
                            Se va a registrar un cobro por el saldo pendiente completo:
                            <span class="font-semibold text-red-600">{{ number_format($item->saldo_pendiente, 2) }} Bs</span>
                        </p>
                    @else
                        <p class="text-sm text-gray-400 bg-gray-50 rounded-lg p-3">No tiene saldo pendiente.</p>
                    @endif

                    @if($gastosPendientes->count() > 0)
                        <div class="mt-3">
                            <p class="text-xs font-semibold text-gray-500 uppercase mb-1.5">Ítems de gasto incluidos</p>
                            <div class="space-y-1 max-h-56 overflow-y-auto border rounded-lg p-3">
                                @foreach($gastosPendientes as $gasto)
                                    @php $pendiente = (float) $gasto->monto - $gasto->total_cobrado; @endphp
                                    <div class="flex items-center justify-between gap-3 text-sm py-1">
                                        <span class="min-w-0">
                                            <span class="truncate block">{{ $gasto->concepto }}</span>
                                            @if($gasto->seguimiento)
                                            <span class="text-[11px] text-gray-400 block truncate">{{ $gasto->seguimiento->titulo }}</span>
                                            @endif
                                            @if(auth()->user()->rol?->nombre === 'Administrador')
                                            <span class="text-[11px] text-gray-400 block truncate">
                                                <i class="fas fa-user-pen mr-0.5"></i>{{ $gasto->usuario?->name ?? '—' }}
                                            </span>
                                            @endif
                                        </span>
                                        <span class="text-gray-500 flex-shrink-0">{{ number_format($pendiente, 2) }} Bs</span>
                                    </div>
                                @endforeach
                            </div>
                            <div class="flex justify-end mt-2">
                                <a href="{{ route($item->tipo_registro . 's.items-cobro.pdf', $item) }}?{{ http_build_query(['gastos' => $gastosPendientes->pluck('id')->all()]) }}"
                                   target="_blank"
                                   class="text-xs text-gray-600 hover:text-brand-700 flex items-center gap-1.5 border border-gray-300 rounded-lg px-3 py-1.5 hover:bg-gray-50">
                                    <i class="fas fa-file-pdf text-red-500"></i> Generar PDF del detalle
                                </a>
                            </div>
                        </div>
                    @endif
                </div>

                <div id="panel-abono-{{ $item->tipo_registro }}-{{ $item->id }}" class="modo-panel hidden">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Monto del abono *</label>
                    <input type="number" name="monto" id="input-monto-abono-{{ $item->tipo_registro }}-{{ $item->id }}" step="0.01" min="0.01" max="{{ $item->saldo_pendiente }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                    <p class="text-xs text-gray-400 mt-1">Máximo: {{ number_format($item->saldo_pendiente, 2) }} Bs</p>
                </div>

                <div class="grid grid-cols-2 gap-4 pt-2 border-t">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Fecha *</label>
                        <input type="date" name="fecha" id="input-fecha-cobro-{{ $item->tipo_registro }}-{{ $item->id }}" value="{{ date('Y-m-d') }}" required
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Método de pago *</label>
                        <select name="metodo_pago" id="select-metodo-pago-{{ $item->tipo_registro }}-{{ $item->id }}" required
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                            <option value="efectivo">Efectivo</option>
                            <option value="qr">QR</option>
                        </select>
                    </div>
                </div>

                <div class="flex flex-wrap gap-3 pt-2">
                    <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white px-6 py-2 rounded-lg font-medium">
                        <i class="fas fa-hand-holding-dollar mr-1"></i> Registrar Cobro
                    </button>
                    @if($item->cliente->telefono_whatsapp)
                    <button type="button" onclick="enviarInformeCobroWhatsappGeneral('{{ $item->tipo_registro }}-{{ $item->id }}')"
                            class="bg-emerald-100 hover:bg-emerald-200 text-emerald-700 px-4 py-2 rounded-lg font-medium flex items-center gap-2">
                        <i class="fab fa-whatsapp"></i> Enviar informe
                    </button>
                    @endif
                    <button type="button" onclick="cerrarModal('modal-cobro-nuevo-{{ $item->tipo_registro }}-{{ $item->id }}')"
                            class="text-gray-600 hover:text-gray-800 px-4 py-2 rounded-lg border hover:bg-gray-50">
                        Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endforeach

{{-- Modal flotante: alta rápida de tipo de gasto (compartido por todas las filas) --}}
<div id="modal-tipo-gasto-rapido" class="hidden fixed inset-0 z-[60] overflow-y-auto">
    <div class="fixed inset-0 bg-black/50" onclick="cerrarModal('modal-tipo-gasto-rapido')"></div>
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="relative bg-white rounded-xl shadow-lg w-full max-w-sm">
            <div class="flex items-center justify-between px-6 py-4 border-b">
                <h3 class="font-semibold text-gray-800 text-lg">Nuevo tipo de gasto</h3>
                <button type="button" onclick="cerrarModal('modal-tipo-gasto-rapido')" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>
            <div class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nombre *</label>
                    <input type="text" id="input-tipo-gasto-rapido-nombre" placeholder="Ej: Arancel de inscripción"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                    <p id="error-tipo-gasto-rapido" class="text-xs text-red-600 mt-1 hidden"></p>
                </div>
                <div class="flex gap-3">
                    <button type="button" onclick="crearTipoGastoRapido()"
                            class="bg-brand-600 hover:bg-brand-700 text-white px-4 py-2 rounded-lg text-sm font-medium">
                        Guardar
                    </button>
                    <button type="button" onclick="cerrarModal('modal-tipo-gasto-rapido')"
                            class="text-gray-600 hover:text-gray-800 px-4 py-2 rounded-lg border hover:bg-gray-50 text-sm">
                        Cancelar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    let selectTipoGastoObjetivo = null;

    function abrirModalTipoGastoRapido(selectId) {
        selectTipoGastoObjetivo = selectId;
        document.getElementById('input-tipo-gasto-rapido-nombre').value = '';
        document.getElementById('error-tipo-gasto-rapido').classList.add('hidden');
        abrirModal('modal-tipo-gasto-rapido');
    }

    function crearTipoGastoRapido() {
        const input = document.getElementById('input-tipo-gasto-rapido-nombre');
        const error = document.getElementById('error-tipo-gasto-rapido');
        const nombre = input.value.trim();

        error.classList.add('hidden');

        if (!nombre) {
            error.textContent = 'El nombre es obligatorio.';
            error.classList.remove('hidden');
            return;
        }

        fetch('{{ route('parametros.tipos-gasto.store') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({ nombre }),
        })
            .then(async (res) => {
                const body = await res.json();
                if (!res.ok) {
                    throw new Error(body.errors?.nombre?.[0] ?? 'No se pudo crear el tipo de gasto.');
                }
                return body;
            })
            .then((tipo) => {
                if (selectTipoGastoObjetivo) {
                    const select = document.getElementById(selectTipoGastoObjetivo);
                    const opcion = new Option(tipo.nombre, tipo.id, true, true);
                    select.add(opcion);
                }
                input.value = '';
                cerrarModal('modal-tipo-gasto-rapido');
            })
            .catch((err) => {
                error.textContent = err.message;
                error.classList.remove('hidden');
            });
    }

    function enviarInformeCobroWhatsappGeneral(clave) {
        const form = document.getElementById('form-cobro-nuevo-' + clave);
        const telefono = form.dataset.clienteWhatsapp;
        if (!telefono) return;

        const modo = document.getElementById('modo-' + clave).value;
        const fecha = document.getElementById('input-fecha-cobro-' + clave).value;
        const fechaTexto = fecha ? fecha.split('-').reverse().join('/') : '';
        const esTramite = form.dataset.itemTipo === 'tramite';

        let detalle = '';
        if (modo === 'abono') {
            const monto = parseFloat(document.getElementById('input-monto-abono-' + clave).value || '0');
            detalle = `Te solicitamos el pago de un abono de: ${monto.toFixed(2)} Bs`;
        } else {
            const saldo = parseFloat(form.dataset.saldoPendiente || '0');
            detalle = `Te solicitamos el pago del saldo pendiente de tu ${esTramite ? 'trámite' : 'expediente'}: ${saldo.toFixed(2)} Bs`;
        }

        const texto = `Hola ${form.dataset.clienteNombre}, te escribimos de Vidal Escalante & Asociados respecto a tu ${esTramite ? 'trámite' : 'expediente'} `
            + `${form.dataset.itemCodigo} (${form.dataset.itemTitulo}).\n\n`
            + `${detalle}\n\n`
            + `Fecha: ${fechaTexto}\n\n`
            + `Quedamos atentos a la confirmación del pago. Gracias por tu confianza.`;

        window.open('https://wa.me/' + telefono + '?text=' + encodeURIComponent(texto), '_blank');
    }

    function toggleAccionesMenu(event, id) {
        event.stopPropagation();
        const menu = document.getElementById(id);
        const yaAbierto = !menu.classList.contains('hidden');
        cerrarMenusAcciones();
        if (!yaAbierto) menu.classList.remove('hidden');
    }

    function cerrarMenusAcciones() {
        document.querySelectorAll('.menu-acciones').forEach(m => m.classList.add('hidden'));
    }

    document.addEventListener('click', cerrarMenusAcciones);

    function seleccionarModoCobro(clave, modo) {
        document.getElementById('modo-' + clave).value = modo;

        ['total', 'abono'].forEach(function (m) {
            const panel = document.getElementById('panel-' + m + '-' + clave);
            if (panel) panel.classList.toggle('hidden', m !== modo);
        });

        document.querySelectorAll('.modo-btn-' + clave).forEach(function (btn) {
            const activo = btn.dataset.modo === modo;
            btn.classList.toggle('bg-brand-600', activo);
            btn.classList.toggle('text-white', activo);
            btn.classList.toggle('border-brand-600', activo);
            btn.classList.toggle('border-gray-300', !activo);
            btn.classList.toggle('text-gray-700', !activo);
        });
    }

    @if($errors->any() && old('_modal'))
        abrirModal(@json(old('_modal')));
    @endif
</script>
@endpush
@endsection
