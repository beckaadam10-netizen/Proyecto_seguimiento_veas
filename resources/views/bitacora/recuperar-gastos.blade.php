@extends('layouts.app')

@section('title', 'Reparar gastos borrados')
@section('header', 'Reparar gastos borrados')

@section('header-actions')
    <a href="{{ route('bitacora.index') }}" class="text-gray-600 hover:text-gray-800 px-4 py-2 rounded-lg border hover:bg-gray-50 text-sm">
        <i class="fas fa-arrow-left mr-1"></i> Volver a la bitácora
    </a>
@endsection

@section('content')
<div class="max-w-4xl space-y-6">

    <div class="bg-white rounded-xl shadow-sm p-5 text-sm text-gray-600 space-y-2">
        <p>
            Antes de una corrección del sistema, editar una actuación borraba sus gastos y sus cobros quedaban sueltos:
            el caso aparecía <strong>"Pagado" con saldo negativo</strong>, o con gastos <strong>pendientes</strong> que el cliente ya había pagado.
        </p>
        <p>
            Esta reparación usa la bitácora para devolver cada cobro a su gasto. Si el gasto se volvió a crear, se le vincula su cobro
            (y recupera su dueño y fecha de alta); si no, se restaura el gasto original, con sus mismos datos. No se crean gastos nuevos.
        </p>
        <p class="text-amber-700">
            <i class="fas fa-triangle-exclamation mr-1"></i>
            No limpies la bitácora antes de aplicar esto: de ahí salen los datos para recuperar los gastos.
        </p>
    </div>

    <form method="GET" class="bg-white rounded-xl shadow-sm p-4 flex flex-wrap gap-3 items-end">
        <div class="flex-1 min-w-[16rem]">
            <label class="block text-xs text-gray-500 mb-1">Caso a reparar</label>
            <select name="caso" onchange="this.form.submit()" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                <option value="">Todos los casos afectados ({{ count($casos) }})</option>
                @foreach($casos as $clave => $etiqueta)
                    <option value="{{ $clave }}" {{ $caso === $clave ? 'selected' : '' }}>{{ $etiqueta }}</option>
                @endforeach
            </select>
        </div>
    </form>

    @if($aplicado)
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="px-5 py-3 border-b bg-emerald-50 text-emerald-800 font-semibold text-sm">
            <i class="fas fa-circle-check mr-1"></i> Resultado de la reparación aplicada
        </div>
        <pre class="p-5 text-xs text-gray-700 whitespace-pre-wrap">{{ $aplicado }}</pre>
    </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="px-5 py-3 border-b font-semibold text-gray-700 text-sm">
            <i class="fas fa-eye mr-1"></i> Vista previa: lo que se haría ahora (todavía no se cambió nada)
        </div>
        <pre class="p-5 text-xs text-gray-700 whitespace-pre-wrap">{{ $salida }}</pre>
    </div>

    @unless(str_contains($salida, 'no hay nada que recuperar'))
    <form method="POST" action="{{ route('bitacora.recuperar-gastos.aplicar') }}"
          onsubmit="return confirm('¿Aplicar la reparación mostrada en la vista previa{{ $caso ? ', solo para el caso elegido' : ', para todos los casos' }}? Conviene tener una copia de la base de datos antes.');">
        @csrf
        <input type="hidden" name="caso" value="{{ $caso }}">
        <button type="submit" class="bg-brand-600 hover:bg-brand-700 text-white px-6 py-2 rounded-lg font-medium">
            <i class="fas fa-screwdriver-wrench mr-1"></i>
            {{ $caso ? 'Aplicar reparación solo a este caso' : 'Aplicar reparación a todos los casos' }}
        </button>
    </form>
    @endunless

    @if($caso && $cobrosSueltos->isNotEmpty())
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="px-5 py-3 border-b font-semibold text-gray-700 text-sm">
            <i class="fas fa-hand-pointer mr-1"></i> Reparación manual de este caso
        </div>
        <div class="p-5 text-sm text-gray-600 space-y-1 border-b">
            <p>Para los cobros que la bitácora no puede resolver, elegí qué era cada uno:</p>
            <ul class="list-disc pl-5 space-y-0.5">
                <li><strong>Pagó un gasto pendiente:</strong> el gasto se borró y se volvió a crear, y ahora figura pendiente aunque ya se cobró.</li>
                <li><strong>Restaurar el gasto borrado:</strong> el gasto ya no existe. Se vuelve a cargar con el monto del cobro y la fecha de alta del cobro (no aparece como gasto nuevo en el Reporte de Pasantes).</li>
                <li><strong>Dejar como está:</strong> era un cobro general de verdad, o todavía no sabés qué era.</li>
            </ul>
            <p class="text-xs text-gray-400">El concepto lo podés sacar del comprobante que se le dio al cliente ese día o de la bitácora.</p>
        </div>

        <form method="POST" action="{{ route('bitacora.recuperar-gastos.manual') }}"
              onsubmit="return confirm('¿Aplicar la reparación manual elegida? Conviene tener una copia de la base de datos antes.');">
            @csrf
            <input type="hidden" name="caso" value="{{ $caso }}">

            <div class="divide-y">
                @foreach($cobrosSueltos as $cobro)
                <div class="p-5 grid grid-cols-1 md:grid-cols-3 gap-3 items-start">
                    <div class="text-sm">
                        <p class="font-semibold text-gray-800">Cobro #{{ $cobro->id }} — {{ number_format($cobro->monto, 2) }} Bs</p>
                        <p class="text-xs text-gray-500">{{ $cobro->fecha->format('d/m/Y') }} · {{ strtoupper($cobro->metodo_pago) }}</p>
                    </div>
                    <div class="md:col-span-2 space-y-2">
                        <select name="cobros[{{ $cobro->id }}][accion]"
                                onchange="this.closest('div').querySelector('.restaurar').classList.toggle('hidden', this.value !== 'restaurar')"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            <option value="">Dejar como está</option>
                            @foreach($gastosPendientes as $g)
                                <option value="gasto-{{ $g->id }}" @disabled((float) $cobro->monto > $g->pendiente + 0.004)>
                                    Pagó: {{ $g->concepto }} · {{ $g->fecha->format('d/m/Y') }}{{ $g->seguimiento ? ' · ' . $g->seguimiento->titulo : '' }} · falta {{ number_format($g->pendiente, 2) }} Bs
                                </option>
                            @endforeach
                            <option value="restaurar">Restaurar el gasto borrado ({{ number_format($cobro->monto, 2) }} Bs)</option>
                        </select>
                        <div class="restaurar hidden grid grid-cols-1 sm:grid-cols-2 gap-2">
                            <input type="text" name="cobros[{{ $cobro->id }}][concepto]" maxlength="200" placeholder="Concepto del gasto borrado"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            <select name="cobros[{{ $cobro->id }}][seguimiento_id]" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                <option value="">Sin actuación</option>
                                @foreach($actuaciones as $a)
                                    <option value="{{ $a->id }}">{{ $a->titulo }} · {{ $a->fecha_actuacion->format('d/m/Y') }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>

            <div class="p-5 border-t">
                <button type="submit" class="bg-brand-600 hover:bg-brand-700 text-white px-6 py-2 rounded-lg font-medium">
                    <i class="fas fa-check mr-1"></i> Aplicar reparación manual
                </button>
            </div>
        </form>
    </div>
    @endif
</div>
@endsection
