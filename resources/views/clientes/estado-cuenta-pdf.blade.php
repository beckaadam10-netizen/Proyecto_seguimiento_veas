<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Estado de cuenta - {{ $cliente->nombre_completo }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #1a1a1a;
            font-size: 12px;
            margin: 0;
            padding: 0;
        }
        .pagina { padding: 30px 42px; }

        .fecha-hora {
            font-size: 11px;
            color: #1a1a1a;
            margin-bottom: 14px;
            line-height: 1.5;
        }

        table.encabezado-firma {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        table.encabezado-firma td { vertical-align: middle; }
        .texto-firma { text-align: center; }
        .texto-firma h1 {
            font-size: 14.5px;
            color: #1F3864;
            margin: 0 0 5px 0;
            letter-spacing: 0.01em;
        }
        .texto-firma p {
            font-size: 10.5px;
            color: #1F3864;
            margin: 1.5px 0;
        }
        .logo-firma { width: 95px; text-align: right; }
        .logo-firma img { width: 80px; height: auto; }

        h2.titulo-doc {
            text-align: center;
            text-decoration: underline;
            font-size: 14px;
            margin: 20px 0 22px 0;
            color: #1a1a1a;
        }

        table.datos-doc {
            width: 100%;
            margin-bottom: 20px;
            font-size: 11.5px;
        }
        table.datos-doc td {
            padding: 2px 0;
        }
        table.datos-doc strong { color: #1a1a1a; }

        table.tabla-items {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
        }
        table.tabla-items th {
            background: #44546A;
            color: #ffffff;
            font-size: 10.5px;
            text-transform: uppercase;
            padding: 9px 10px;
            text-align: left;
        }
        table.tabla-items td {
            font-size: 11px;
            padding: 9px 10px;
            border-bottom: 1px solid #e2e2e2;
            vertical-align: top;
        }
        table.tabla-items tr:nth-child(even) td {
            background: #f7f8fa;
        }
        table.tabla-items td.num { text-align: right; }

        .total-doc {
            text-align: right;
            font-size: 13.5px;
            font-weight: 700;
            margin-top: 8px;
        }

        .salto-pagina { page-break-before: always; }

        h3.subtitulo-registro {
            font-size: 12px;
            color: #1F3864;
            margin: 18px 0 6px 0;
        }
        h3.subtitulo-registro:first-of-type { margin-top: 0; }

        table.tabla-detalle {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
        }
        table.tabla-detalle th {
            background: #eef1f5;
            color: #44546A;
            font-size: 10px;
            text-transform: uppercase;
            padding: 6px 8px;
            text-align: left;
        }
        table.tabla-detalle td {
            font-size: 10.5px;
            padding: 6px 8px;
            border-bottom: 1px solid #e2e2e2;
        }
        table.tabla-detalle td.num { text-align: right; }
        .subtotal-registro {
            text-align: right;
            font-size: 10.5px;
            font-weight: 700;
            margin: 0 0 4px 0;
        }
    </style>
</head>
<body>
<div class="pagina">

    @php
        $diasSemana = [0=>'domingo',1=>'lunes',2=>'martes',3=>'miércoles',4=>'jueves',5=>'viernes',6=>'sábado'];
        $meses = [1=>'enero',2=>'febrero',3=>'marzo',4=>'abril',5=>'mayo',6=>'junio',7=>'julio',8=>'agosto',9=>'septiembre',10=>'octubre',11=>'noviembre',12=>'diciembre'];
        $ahora = now();
        $fechaTexto = $diasSemana[(int) $ahora->format('w')] . ' ' . $ahora->day . ' de ' . $meses[$ahora->month] . ' del ' . $ahora->year;
    @endphp

    <div class="fecha-hora">
        {{ ucfirst($fechaTexto) }}<br>
        {{ $ahora->format('H:i:s') }}
    </div>

    <table class="encabezado-firma">
        <tr>
            <td class="texto-firma">
                <h1>CONSULTORIO JURIDICO "VIDAL-ESCALANTE &amp; ASOCIADOS"</h1>
                <p>Calle Mario Flores, esq. Yapacani Nro. 105</p>
                <p>Telefonos: 62131838 (oficina central)</p>
                <p>Santa Cruz - Bolivia</p>
            </td>
            <td class="logo-firma">
                <img src="{{ public_path('imagen/logo-icono.png') }}">
            </td>
        </tr>
    </table>

    <h2 class="titulo-doc">ESTADO DE CUENTA</h2>

    <table class="datos-doc">
        <tr><td><strong>Cliente:</strong> {{ $cliente->nombre_completo }}</td></tr>
        <tr><td><strong>C.I/NIT:</strong> {{ $cliente->dni }}</td></tr>
    </table>

    <table class="tabla-items">
        <thead>
            <tr>
                <th style="width:80px">Código</th>
                <th>Expediente / Trámite</th>
                <th style="width:90px" class="num">Gastos (Bs)</th>
                <th style="width:90px" class="num">Cobrado (Bs)</th>
                <th style="width:90px" class="num">Saldo (Bs)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($registros as $registro)
            <tr>
                <td>{{ $registro->codigo_display }}</td>
                <td>{{ $registro->titulo_display }}</td>
                <td class="num">{{ number_format($registro->total_gastos, 2) }}</td>
                <td class="num">{{ number_format($registro->total_cobrado, 2) }}</td>
                <td class="num">{{ number_format($registro->saldo_pendiente, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <p class="total-doc">Saldo total pendiente Bs: {{ number_format($totalPendiente, 2) }}</p>

    @if($registros->contains(fn ($r) => $r->gastos->isNotEmpty()))
    <div class="salto-pagina">
        <h2 class="titulo-doc">DESGLOSE POR EXPEDIENTE / TRÁMITE</h2>

        @foreach($registros as $registro)
            @continue($registro->gastos->isEmpty())
            <h3 class="subtitulo-registro">{{ $registro->codigo_display }} · {{ $registro->titulo_display }}</h3>
            <table class="tabla-detalle">
                <thead>
                    <tr>
                        <th style="width:80px">Fecha</th>
                        <th>Concepto</th>
                        <th style="width:80px" class="num">Monto (Bs)</th>
                        <th style="width:80px" class="num">Cobrado (Bs)</th>
                        <th style="width:80px" class="num">Saldo (Bs)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($registro->gastos as $gasto)
                    <tr>
                        <td>{{ $gasto->fecha?->format('d-m-Y') }}</td>
                        <td>{{ $gasto->concepto }}</td>
                        <td class="num">{{ number_format($gasto->monto, 2) }}</td>
                        <td class="num">{{ number_format($gasto->total_cobrado, 2) }}</td>
                        <td class="num">{{ number_format($gasto->monto - $gasto->total_cobrado, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            <p class="subtotal-registro">Saldo de este registro Bs: {{ number_format($registro->saldo_pendiente, 2) }}</p>
        @endforeach
    </div>
    @endif

</div>
</body>
</html>
