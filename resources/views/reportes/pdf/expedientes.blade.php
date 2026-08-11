@extends('pdf.layout')

@section('titulo', 'Reporte de Expedientes')
@section('subtitulo', 'Expedientes por estado, tipo de causa y abogado')
@section('meta', 'Total: ' . $expedientes->count() . ' expedientes<br>')

@section('cuerpo')

<table class="resumen">
    <tr>
        <td>
            <span class="cantidad">{{ $resumen['total'] }}</span>
            <span class="etiqueta">Total</span>
        </td>
        @foreach($resumen['por_estado'] as $estado => $cantidad)
        <td>
            <span class="cantidad">{{ $cantidad }}</span>
            <span class="etiqueta">{{ ucfirst($estado) }}</span>
        </td>
        @endforeach
    </tr>
</table>

<h2 class="seccion">Expedientes ({{ $expedientes->count() }})</h2>
@if($expedientes->isEmpty())
    <p class="vacio">No se encontraron expedientes con los filtros aplicados.</p>
@else
    <table class="lista">
        <thead>
            <tr>
                <th style="width:90px">NUREJ</th>
                <th>Carátula</th>
                <th>Cliente</th>
                <th style="width:90px">Tipo</th>
                <th>Abogado</th>
                <th style="width:75px">Recepción</th>
                <th style="width:80px" class="centro">Estado</th>
            </tr>
        </thead>
        <tbody>
            @foreach($expedientes as $exp)
            <tr>
                <td>{{ $exp->numero }}</td>
                <td>{{ $exp->caratula }}</td>
                <td>{{ $exp->cliente->nombre_completo }}</td>
                <td>{{ ucfirst($exp->tipo_causa) }}</td>
                <td>{{ $exp->abogado?->nombre ?? '—' }}</td>
                <td>{{ $exp->fecha_recepcion?->format('d/m/Y') ?? '—' }}</td>
                <td class="centro">{{ $exp->estado_label }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
@endif

@endsection
