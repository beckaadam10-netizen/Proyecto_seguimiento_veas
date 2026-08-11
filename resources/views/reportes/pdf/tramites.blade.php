@extends('pdf.layout')

@section('titulo', 'Reporte de Trámites')
@section('subtitulo', 'Trámites por estado, prioridad e institución pública')
@section('meta', 'Total: ' . $tramites->count() . ' trámites<br>')

@section('cuerpo')

<table class="resumen">
    <tr>
        <td>
            <span class="cantidad">{{ $resumen['total'] }}</span>
            <span class="etiqueta">Total</span>
        </td>
        @foreach(array_slice($resumen['por_estado'], 0, 4, true) as $estado => $cantidad)
        <td>
            <span class="cantidad">{{ $cantidad }}</span>
            <span class="etiqueta">{{ ucfirst(str_replace('_', ' ', $estado)) }}</span>
        </td>
        @endforeach
    </tr>
    <tr>
        @foreach(array_slice($resumen['por_estado'], 4, 4, true) as $estado => $cantidad)
        <td>
            <span class="cantidad">{{ $cantidad }}</span>
            <span class="etiqueta">{{ ucfirst(str_replace('_', ' ', $estado)) }}</span>
        </td>
        @endforeach
    </tr>
</table>

<h2 class="seccion">Trámites ({{ $tramites->count() }})</h2>
@if($tramites->isEmpty())
    <p class="vacio">No se encontraron trámites con los filtros aplicados.</p>
@else
    <table class="lista">
        <thead>
            <tr>
                <th style="width:85px">Código</th>
                <th>Nombre</th>
                <th>Cliente</th>
                <th>Responsable</th>
                <th>Institución</th>
                <th style="width:75px">Inicio</th>
                <th style="width:65px" class="centro">Prioridad</th>
                <th style="width:75px" class="centro">Estado</th>
            </tr>
        </thead>
        <tbody>
            @foreach($tramites as $tramite)
            <tr>
                <td>{{ $tramite->codigo }}</td>
                <td>{{ $tramite->nombre }}</td>
                <td>{{ $tramite->cliente->nombre_completo }}</td>
                <td>{{ $tramite->responsable?->nombre ?? '—' }}</td>
                <td>{{ $tramite->institucionPublica?->nombre ?? '—' }}</td>
                <td>{{ $tramite->fecha_inicio->format('d/m/Y') }}</td>
                <td class="centro">{{ ucfirst($tramite->prioridad) }}</td>
                <td class="centro">{{ $tramite->estado_label }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
@endif

@endsection
