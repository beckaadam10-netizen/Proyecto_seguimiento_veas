@extends('pdf.layout')

@section('titulo', 'Reporte de Gastos y Cobros')
@section('subtitulo', 'Saldo por trámite/expediente: gastado, cobrado y pendiente')
@section('meta', 'Total: ' . $registros->count() . ' registros<br>')

@section('cuerpo')

<table class="resumen">
    <tr>
        <td>
            <span class="monto">{{ number_format($resumen['total_gastos'], 2) }} Bs</span>
            <span class="etiqueta">Total gastos</span>
        </td>
        <td>
            <span class="monto">{{ number_format($resumen['total_cobrado'], 2) }} Bs</span>
            <span class="etiqueta">Total cobrado</span>
        </td>
        <td>
            <span class="monto">{{ number_format($resumen['saldo_pendiente'], 2) }} Bs</span>
            <span class="etiqueta">Saldo pendiente</span>
        </td>
    </tr>
</table>

<h2 class="seccion">Trámites y expedientes con movimientos ({{ $registros->count() }})</h2>
@if($registros->isEmpty())
    <p class="vacio">No hay trámites ni expedientes con gastos o cobros para los filtros aplicados.</p>
@else
    <table class="lista">
        <thead>
            <tr>
                <th style="width:70px">Tipo</th>
                <th style="width:85px">Código</th>
                <th>Trámite / Expediente</th>
                <th>Cliente</th>
                <th style="width:80px" class="num">Gastado</th>
                <th style="width:80px" class="num">Cobrado</th>
                <th style="width:80px" class="num">Por cobrar</th>
            </tr>
        </thead>
        <tbody>
            @foreach($registros as $registro)
            @php
                $porCobrar = $registro->gastado - $registro->cobrado;
            @endphp
            <tr>
                <td>{{ $registro->tipo_registro === 'expediente' ? 'Expediente' : 'Trámite' }}</td>
                <td>{{ $registro->codigo_display }}</td>
                <td>{{ $registro->titulo_display }}</td>
                <td>{{ $registro->cliente->nombre_completo }}</td>
                <td class="num">{{ number_format($registro->gastado, 2) }} Bs</td>
                <td class="num">{{ number_format($registro->cobrado, 2) }} Bs</td>
                <td class="num">{{ number_format($porCobrar, 2) }} Bs</td>
            </tr>
            @endforeach
        </tbody>
    </table>
@endif

@endsection
