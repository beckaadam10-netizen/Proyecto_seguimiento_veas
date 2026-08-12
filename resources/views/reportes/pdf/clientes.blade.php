@extends('pdf.layout')

@section('titulo', 'Reporte de Clientes')
@section('subtitulo', 'Listado de clientes registrados')
@section('meta', 'Total: ' . $clientes->count() . ' clientes<br>')

@section('cuerpo')

<table class="resumen">
    <tr>
        <td>
            <span class="cantidad">{{ $resumen['total'] }}</span>
            <span class="etiqueta">Total clientes</span>
        </td>
        <td>
            <span class="cantidad">{{ $resumen['activos'] }}</span>
            <span class="etiqueta">Activos</span>
        </td>
        <td>
            <span class="cantidad">{{ $resumen['personas_fisicas'] }}</span>
            <span class="etiqueta">Personas físicas</span>
        </td>
        <td>
            <span class="cantidad">{{ $resumen['personas_juridicas'] }}</span>
            <span class="etiqueta">Personas jurídicas</span>
        </td>
    </tr>
</table>

<h2 class="seccion">Clientes ({{ $clientes->count() }})</h2>
@if($clientes->isEmpty())
    <p class="vacio">No se encontraron clientes con los filtros aplicados.</p>
@else
    <table class="lista">
        <thead>
            <tr>
                <th>Cliente</th>
                <th style="width:70px">Tipo</th>
                <th style="width:90px">C.I/NIT</th>
                <th>Contacto</th>
                <th style="width:60px" class="centro">Exped.</th>
                <th style="width:60px" class="centro">Trámites</th>
                <th style="width:60px" class="centro">Estado</th>
            </tr>
        </thead>
        <tbody>
            @foreach($clientes as $cliente)
            <tr>
                <td>{{ $cliente->nombre_completo }}</td>
                <td>{{ $cliente->tipo === 'persona_juridica' ? 'Jurídica' : 'Física' }}</td>
                <td>{{ $cliente->dni }}</td>
                <td>{{ $cliente->email ?? '—' }} {{ $cliente->telefono ? '· ' . $cliente->telefono : '' }}</td>
                <td class="centro">{{ $cliente->expedientes_count }}</td>
                <td class="centro">{{ $cliente->tramites_count }}</td>
                <td class="centro">{{ $cliente->activo ? 'Activo' : 'Inactivo' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
@endif

@endsection
