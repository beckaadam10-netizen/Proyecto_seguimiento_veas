<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Comprobante de cobros - {{ $expediente->numero }}</title>
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
        table.tabla-items td.centro { text-align: center; }

        .badge-metodo {
            display: inline-block;
            font-size: 9.5px;
            font-weight: 700;
            padding: 2px 7px;
            border-radius: 4px;
            text-transform: uppercase;
        }
        .badge-efectivo { background: #d1fae5; color: #047857; }
        .badge-qr { background: #dbeafe; color: #1d4ed8; }

        .total-doc {
            text-align: right;
            font-size: 13.5px;
            font-weight: 700;
            margin-top: 8px;
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

    <h2 class="titulo-doc">COMPROBANTE DE COBROS</h2>

    <table class="datos-doc">
        <tr><td><strong>NUREJ:</strong> {{ $expediente->numero }}</td></tr>
        <tr><td><strong>Cliente:</strong> {{ $expediente->cliente->nombre_completo }}</td></tr>
        <tr><td><strong>Carátula:</strong> {{ $expediente->caratula }}</td></tr>
    </table>

    <table class="tabla-items">
        <thead>
            <tr>
                <th style="width:80px">Fecha</th>
                <th>Concepto</th>
                <th style="width:80px" class="centro">Método</th>
                <th style="width:90px" class="num">Monto (Bs)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($cobros as $cobro)
            <tr>
                <td>{{ $cobro->fecha?->format('d-m-Y') }}</td>
                <td>{{ $cobro->gasto?->concepto ?? 'Cobro general' }}</td>
                <td class="centro">
                    <span class="badge-metodo {{ $cobro->metodo_pago === 'qr' ? 'badge-qr' : 'badge-efectivo' }}">
                        {{ strtoupper($cobro->metodo_pago) }}
                    </span>
                </td>
                <td class="num">{{ number_format($cobro->monto, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <p class="total-doc">Total cobrado Bs: {{ number_format($total, 2) }}</p>

</div>
</body>
</html>
