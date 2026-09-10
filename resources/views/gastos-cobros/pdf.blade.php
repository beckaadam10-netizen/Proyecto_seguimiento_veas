@extends('pdf.layout')

@php
    $porcentajeCobrado = $resumen['total_gastos'] > 0
        ? min(100, round(($resumen['total_cobrado'] / $resumen['total_gastos']) * 100))
        : ($resumen['total_cobrado'] > 0 ? 100 : 0);
@endphp

@section('titulo', 'Gastos y Cobros')
@section('subtitulo', 'Saldo por trámite/expediente: gastado, cobrado y pendiente')
@section('meta', 'Total: ' . $registros->count() . ' registros<br>')

@section('cuerpo')

<table class="resumen">
    <tr>
        <td>
            <span class="monto">{{ number_format($resumen['total_gastos'], 2) }} Bs</span>
            <span class="etiqueta">Total gastado</span>
        </td>
        <td>
            <span class="monto">{{ number_format($resumen['total_cobrado'], 2) }} Bs</span>
            <span class="etiqueta">Total cobrado</span>
        </td>
        <td>
            <span class="monto">{{ number_format($resumen['saldo_pendiente'], 2) }} Bs</span>
            <span class="etiqueta">Saldo pendiente</span>
        </td>
        <td>
            <span class="monto">{{ $porcentajeCobrado }}%</span>
            <span class="etiqueta">Cobrado sobre lo gastado</span>
        </td>
    </tr>
</table>

<h2 class="seccion">Estado de pago ({{ $resumen['total_registros'] }} registros)</h2>
<table class="resumen">
    <tr>
        <td>
            <span class="monto">{{ $resumen['pendientes'] }}</span>
            <span class="etiqueta"><span class="badge badge-rojo">Pendiente</span></span>
        </td>
        <td>
            <span class="monto">{{ $resumen['parciales'] }}</span>
            <span class="etiqueta"><span class="badge badge-ambar">Parcial</span></span>
        </td>
        <td>
            <span class="monto">{{ $resumen['pagados'] }}</span>
            <span class="etiqueta"><span class="badge badge-verde">Pagado</span></span>
        </td>
    </tr>
</table>

@if($porTipoGasto->isNotEmpty())
<h2 class="seccion">Top tipos de gasto</h2>
<table class="lista">
    <thead>
        <tr>
            <th>Tipo de gasto</th>
            <th style="width:100px" class="num">Total</th>
        </tr>
    </thead>
    <tbody>
        @foreach($porTipoGasto as $t)
        <tr>
            <td>{{ $t->nombre }}</td>
            <td class="num">{{ number_format($t->total, 2) }} Bs</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

<h2 class="seccion">Trámites y expedientes ({{ $registros->count() }})</h2>
@if($registros->isEmpty())
    <p class="vacio">No hay trámites ni expedientes para los filtros aplicados.</p>
@else
    <table class="lista">
        <thead>
            <tr>
                <th style="width:65px">Tipo</th>
                <th style="width:80px">Código</th>
                <th>Trámite / Expediente</th>
                <th>Cliente</th>
                <th style="width:75px">Estado</th>
                <th style="width:75px" class="num">Gastado</th>
                <th style="width:75px" class="num">Cobrado</th>
                <th style="width:75px" class="num">Saldo</th>
            </tr>
        </thead>
        <tbody>
            @foreach($registros as $registro)
            @php
                $badge = match ($registro->estado_pago) {
                    'pendiente' => 'badge-rojo',
                    'parcial'   => 'badge-ambar',
                    'pagado'    => 'badge-verde',
                    default     => 'badge-gris',
                };
            @endphp
            <tr>
                <td>{{ $registro->tipo_registro === 'expediente' ? 'Expediente' : 'Trámite' }}</td>
                <td>{{ $registro->codigo_display }}</td>
                <td>{{ $registro->titulo_display }}</td>
                <td>{{ $registro->cliente->nombre_completo }}</td>
                <td><span class="badge {{ $badge }}">{{ $registro->estado_pago_label }}</span></td>
                <td class="num">{{ number_format($registro->total_gastos, 2) }} Bs</td>
                <td class="num">{{ number_format($registro->total_cobrado, 2) }} Bs</td>
                <td class="num">{{ number_format($registro->saldo_pendiente, 2) }} Bs</td>
            </tr>
            @endforeach
        </tbody>
    </table>
@endif

@endsection
