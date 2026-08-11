<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Reporte de gastos - {{ $usuario->name }}</title>
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
            margin: 20px 0 6px 0;
            color: #1a1a1a;
        }

        p.periodo-doc {
            text-align: center;
            font-size: 11.5px;
            color: #1a1a1a;
            margin: 0 0 22px 0;
        }

        p.intro-doc {
            font-size: 11.5px;
            line-height: 1.6;
            text-align: justify;
            margin: 0 0 22px 0;
        }

        p.narrativa-caso {
            font-size: 11.5px;
            line-height: 1.6;
            text-align: justify;
            margin: 0 0 10px 0;
        }
        p.narrativa-caso strong { color: #1a1a1a; }

        table.tabla-items {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
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

        .bloque-caso { margin-bottom: 26px; }

        .total-doc {
            text-align: right;
            font-size: 13.5px;
            font-weight: 700;
            margin-top: 8px;
        }

        .vacio { text-align: center; color: #777; margin: 30px 0; }
    </style>
</head>
<body>
<div class="pagina">

    @php
        $diasSemana = [0=>'domingo',1=>'lunes',2=>'martes',3=>'miércoles',4=>'jueves',5=>'viernes',6=>'sábado'];
        $meses = [1=>'enero',2=>'febrero',3=>'marzo',4=>'abril',5=>'mayo',6=>'junio',7=>'julio',8=>'agosto',9=>'septiembre',10=>'octubre',11=>'noviembre',12=>'diciembre'];
        $ahora = now()->setTimezone('America/La_Paz');
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

    <h2 class="titulo-doc">INFORME DE RENDICIÓN DE CUENTAS</h2>
    <p class="periodo-doc">
        <strong>Pasante:</strong> {{ $usuario->name }}
        @if($desde && $hasta)
            &nbsp;·&nbsp; <strong>Período reportado:</strong> {{ $desde->format('d/m/Y') }} al {{ $hasta->format('d/m/Y') }}
        @endif
    </p>

    <p class="intro-doc">
        Este informe tiene como objetivo proporcionar una visión detallada y transparente de los gastos
        incurridos durante el último período. La rendición de cuentas es crucial para garantizar la
        transparencia y la responsabilidad en la gestión de los recursos. También proporciona una visión
        detallada de cómo se han utilizado los fondos, permitiendo una evaluación completa y justa de
        nuestra gestión financiera.
    </p>

    @if($grupos->isEmpty())
        <p class="vacio">No hay gastos registrados para los filtros aplicados.</p>
    @else
        @foreach($grupos as $grupo)
        <div class="bloque-caso">
            <p class="narrativa-caso">
                Según registro de gastos extrajudiciales con código <strong>{{ $grupo->codigo_display }}</strong>,
                el cliente <strong>{{ $grupo->cliente?->nombre_completo ?? '—' }}</strong>
                @if($grupo->tipo === 'expediente')
                    con número de proceso <strong>{{ $grupo->titulo_display }}</strong>
                    @if($grupo->instancia) se encuentra en instancia <strong>{{ $grupo->instancia }}</strong> @endif
                @else
                    correspondiente al trámite <strong>{{ $grupo->titulo_display }}</strong>
                    @if($grupo->instancia) se encuentra en la institución <strong>{{ $grupo->instancia }}</strong> @endif
                @endif
                @if($grupo->abogado) a cargo del abogado <strong>{{ $grupo->abogado }}</strong> @endif
                los cuales se encuentran en estado <strong>Por Cobrar</strong>, detallados a continuación:
            </p>

            <table class="tabla-items">
                <thead>
                    <tr>
                        <th style="width:80px">Fecha</th>
                        <th>Concepto</th>
                        <th style="width:90px" class="num">Precio (Bs)</th>
                        <th style="width:70px" class="centro">Cantidad</th>
                        <th style="width:90px" class="num">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($grupo->gastos as $gasto)
                    <tr>
                        <td>{{ $gasto->fecha->format('d-m-Y') }}</td>
                        <td>{{ $gasto->concepto }}</td>
                        <td class="num">{{ number_format($gasto->monto, 2) }}</td>
                        <td class="centro">1</td>
                        <td class="num">{{ number_format($gasto->monto, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            <p class="total-doc" style="font-size:11.5px">Subtotal: {{ number_format($grupo->total, 2) }} Bs</p>
        </div>
        @endforeach
    @endif

    <p class="total-doc">Total Bs: {{ number_format($resumen['total_monto'], 2) }}</p>

</div>
</body>
</html>
