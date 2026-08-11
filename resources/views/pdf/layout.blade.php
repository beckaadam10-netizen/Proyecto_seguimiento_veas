<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>@yield('titulo')</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #27272a;
            font-size: 11.5px;
            margin: 0;
            padding: 0;
        }
        .pagina { padding: 32px 36px; }
        .encabezado {
            border-bottom: 3px solid #b91c4c;
            padding-bottom: 14px;
            margin-bottom: 18px;
            width: 100%;
        }
        .encabezado td { vertical-align: top; }
        .encabezado h1 {
            font-size: 19px;
            margin: 0 0 4px 0;
            color: #18181b;
        }
        .encabezado .subtitulo {
            font-size: 12px;
            color: #71717a;
        }
        .encabezado .meta {
            text-align: right;
            font-size: 10.5px;
            color: #71717a;
        }
        .filtros {
            background: #fafafa;
            border: 1px solid #e4e4e7;
            border-radius: 6px;
            padding: 8px 12px;
            margin-bottom: 18px;
            font-size: 10.5px;
            color: #52525b;
        }
        .filtros strong { color: #27272a; }
        table.resumen {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table.resumen td {
            border: 1px solid #e4e4e7;
            padding: 10px 12px;
            text-align: center;
        }
        table.resumen .monto, table.resumen .cantidad {
            font-size: 17px;
            font-weight: 700;
            display: block;
            color: #18181b;
        }
        table.resumen .etiqueta {
            font-size: 9.5px;
            color: #71717a;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }
        h2.seccion {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #b91c4c;
            border-bottom: 1px solid #e4e4e7;
            padding-bottom: 5px;
            margin: 22px 0 10px 0;
        }
        table.lista {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }
        table.lista th {
            text-align: left;
            font-size: 9.5px;
            text-transform: uppercase;
            color: #71717a;
            border-bottom: 1px solid #d4d4d8;
            padding: 6px 7px;
        }
        table.lista td {
            font-size: 10.5px;
            padding: 6px 7px;
            border-bottom: 1px solid #f1f1f2;
            vertical-align: top;
        }
        table.lista td.num { text-align: right; }
        table.lista td.centro { text-align: center; }
        .badge {
            display: inline-block;
            font-size: 9px;
            font-weight: 600;
            padding: 2px 6px;
            border-radius: 4px;
            background: #eef2ff;
            color: #4338ca;
        }
        .vacio {
            color: #a1a1aa;
            font-style: italic;
            padding: 10px 0;
        }
        .pie {
            margin-top: 30px;
            padding-top: 10px;
            border-top: 1px solid #e4e4e7;
            font-size: 9.5px;
            color: #a1a1aa;
            text-align: center;
        }
    </style>
</head>
<body>
<div class="pagina">

    <table class="encabezado">
        <tr>
            <td>
                <h1>Estudio Jurídico</h1>
                <div class="subtitulo">@yield('subtitulo')</div>
            </td>
            <td class="meta">
                @yield('meta')
                Generado el {{ now()->format('d/m/Y H:i') }}h
            </td>
        </tr>
    </table>

    @if(!empty($filtros))
    <div class="filtros">
        <strong>Filtros aplicados:</strong>
        @foreach($filtros as $etiqueta => $valor)
            {{ $etiqueta }}: <strong>{{ $valor }}</strong>@if(!$loop->last) &nbsp;&middot;&nbsp; @endif
        @endforeach
    </div>
    @endif

    @yield('cuerpo')

    <div class="pie">
        Documento generado automáticamente por el sistema de gestión del Estudio Jurídico &middot; {{ now()->format('d/m/Y H:i') }}h
    </div>

</div>
</body>
</html>
