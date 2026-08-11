<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Reporte {{ $tramite->codigo }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #27272a;
            font-size: 12px;
            margin: 0;
            padding: 0;
            background: #f4f4f5;
        }
        .pagina {
            max-width: 800px;
            margin: 0 auto;
            background: #ffffff;
            padding: 40px;
        }
        .barra-acciones {
            max-width: 800px;
            margin: 16px auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .barra-acciones a, .barra-acciones button {
            display: inline-block;
            text-decoration: none;
            font-size: 13px;
            padding: 8px 14px;
            border-radius: 8px;
            border: 1px solid #d4d4d8;
            color: #3f3f46;
            background: #fff;
            cursor: pointer;
            font-family: inherit;
        }
        .barra-acciones .btn-pdf {
            background: #b91c4c;
            border-color: #b91c4c;
            color: #fff;
            font-weight: 600;
        }
        .encabezado {
            border-bottom: 3px solid #b91c4c;
            padding-bottom: 16px;
            margin-bottom: 24px;
            width: 100%;
        }
        .encabezado td { vertical-align: top; }
        .encabezado h1 {
            font-size: 20px;
            margin: 0 0 4px 0;
            color: #18181b;
        }
        .encabezado .subtitulo {
            font-size: 13px;
            color: #71717a;
        }
        .encabezado .meta {
            text-align: right;
            font-size: 11px;
            color: #71717a;
        }
        h2.seccion {
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #b91c4c;
            border-bottom: 1px solid #e4e4e7;
            padding-bottom: 6px;
            margin: 28px 0 12px 0;
        }
        table.datos {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 4px;
        }
        table.datos td {
            padding: 4px 0;
            font-size: 12px;
            vertical-align: top;
        }
        table.datos td.etiqueta {
            color: #71717a;
            width: 130px;
        }
        table.datos td.valor {
            color: #18181b;
            font-weight: 600;
        }
        table.resumen {
            width: 100%;
            border-collapse: collapse;
        }
        table.resumen td {
            width: 33.33%;
            border: 1px solid #e4e4e7;
            border-radius: 8px;
            padding: 12px 16px;
            text-align: center;
        }
        table.resumen .monto {
            font-size: 18px;
            font-weight: 700;
            display: block;
        }
        table.resumen .etiqueta {
            font-size: 11px;
            color: #71717a;
        }
        .monto-gasto { color: #b45309; }
        .monto-cobro { color: #047857; }
        .monto-saldo { color: #b91c1c; }
        table.lista {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }
        table.lista th {
            text-align: left;
            font-size: 10px;
            text-transform: uppercase;
            color: #71717a;
            border-bottom: 1px solid #d4d4d8;
            padding: 6px 8px;
        }
        table.lista td {
            font-size: 11.5px;
            padding: 7px 8px;
            border-bottom: 1px solid #f1f1f2;
            vertical-align: top;
        }
        table.lista td.num { text-align: right; }
        .badge {
            display: inline-block;
            font-size: 9.5px;
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
            margin-top: 36px;
            padding-top: 12px;
            border-top: 1px solid #e4e4e7;
            font-size: 10px;
            color: #a1a1aa;
            text-align: center;
        }
        @media print {
            body { background: #fff; }
            .barra-acciones { display: none; }
            .pagina { padding: 0; }
        }
    </style>
</head>
<body>

@unless($esPdf)
<div class="barra-acciones">
    <a href="{{ route('tramites.show', $tramite) }}">&larr; Volver al trámite</a>
    <div>
        <a href="#" onclick="window.print(); return false;">Imprimir</a>
        <a href="{{ route('tramites.reporte.pdf', $tramite) }}" class="btn-pdf">Descargar PDF</a>
    </div>
</div>
@endunless

<div class="pagina">

    <table class="encabezado">
        <tr>
            <td>
                <h1>Estudio Jurídico</h1>
                <div class="subtitulo">Estado de cuenta y actuaciones del trámite</div>
            </td>
            <td class="meta">
                Código: <strong>{{ $tramite->codigo }}</strong><br>
                Generado el {{ now()->format('d/m/Y H:i') }}h
            </td>
        </tr>
    </table>

    <h2 class="seccion">Datos del trámite</h2>
    <table class="datos">
        <tr>
            <td class="etiqueta">Trámite</td>
            <td class="valor">{{ $tramite->nombre }}</td>
            <td class="etiqueta">Cliente</td>
            <td class="valor">{{ $tramite->cliente->nombre_completo }}</td>
        </tr>
        <tr>
            <td class="etiqueta">Tipo</td>
            <td class="valor">{{ $tramite->tipoTramite?->nombre ?? '—' }}</td>
            <td class="etiqueta">Institución</td>
            <td class="valor">{{ $tramite->institucionPublica?->nombre ?? '—' }}</td>
        </tr>
        <tr>
            <td class="etiqueta">Responsable</td>
            <td class="valor">{{ $tramite->responsable?->nombre ?? '—' }}</td>
            <td class="etiqueta">Estado actual</td>
            <td class="valor">{{ $tramite->estado_label }}</td>
        </tr>
        <tr>
            <td class="etiqueta">Fecha de inicio</td>
            <td class="valor">{{ $tramite->fecha_inicio->format('d/m/Y') }}</td>
            <td class="etiqueta">Fecha fin aproximada</td>
            <td class="valor">{{ $tramite->fecha_fin_aproximada?->format('d/m/Y') ?? '—' }}</td>
        </tr>
    </table>

    <h2 class="seccion">Resumen financiero</h2>
    <table class="resumen">
        <tr>
            <td>
                <span class="monto monto-gasto">{{ number_format($tramite->total_gastos, 2) }} Bs</span>
                <span class="etiqueta">Total gastos</span>
            </td>
            <td>
                <span class="monto monto-cobro">{{ number_format($tramite->total_cobrado, 2) }} Bs</span>
                <span class="etiqueta">Total cobrado</span>
            </td>
            <td>
                <span class="monto monto-saldo">{{ number_format($tramite->saldo_pendiente, 2) }} Bs</span>
                <span class="etiqueta">Saldo pendiente</span>
            </td>
        </tr>
    </table>

    <h2 class="seccion">Gastos ({{ $tramite->gastos->count() }})</h2>
    @if($tramite->gastos->isEmpty())
        <p class="vacio">No se registraron gastos.</p>
    @else
        <table class="lista">
            <thead>
                <tr>
                    <th>Concepto</th>
                    <th>Tipo</th>
                    <th>Fecha</th>
                    <th style="text-align:right">Monto</th>
                </tr>
            </thead>
            <tbody>
                @foreach($tramite->gastos->sortBy('fecha') as $gasto)
                <tr>
                    <td>{{ $gasto->concepto }}</td>
                    <td>{{ $gasto->tipoGasto?->nombre ?? '—' }}</td>
                    <td>{{ $gasto->fecha->format('d/m/Y') }}</td>
                    <td class="num">{{ number_format($gasto->monto, 2) }} Bs</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <h2 class="seccion">Cobros ({{ $tramite->cobros->count() }})</h2>
    @if($tramite->cobros->isEmpty())
        <p class="vacio">No se registraron cobros.</p>
    @else
        <table class="lista">
            <thead>
                <tr>
                    <th>Concepto</th>
                    <th>Método</th>
                    <th>Fecha</th>
                    <th style="text-align:right">Monto</th>
                </tr>
            </thead>
            <tbody>
                @foreach($tramite->cobros->sortBy('fecha') as $cobro)
                <tr>
                    <td>{{ $cobro->gasto?->concepto ?? 'General' }}</td>
                    <td style="text-transform:uppercase">{{ $cobro->metodo_pago }}</td>
                    <td>{{ $cobro->fecha->format('d/m/Y') }}</td>
                    <td class="num">{{ number_format($cobro->monto, 2) }} Bs</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <h2 class="seccion">Historial de actuaciones y cambios de estado ({{ $tramite->seguimientos->count() }})</h2>
    @if($tramite->seguimientos->isEmpty())
        <p class="vacio">No se registraron actuaciones.</p>
    @else
        <table class="lista">
            <thead>
                <tr>
                    <th style="width:75px">Fecha</th>
                    <th style="width:110px">Tipo</th>
                    <th>Detalle</th>
                    <th style="width:110px">Registrado por</th>
                </tr>
            </thead>
            <tbody>
                @foreach($tramite->seguimientos->sortBy('fecha_actuacion') as $seg)
                <tr>
                    <td>{{ $seg->fecha_actuacion->format('d/m/Y') }}</td>
                    <td><span class="badge">{{ $seg->tipoActuacion?->nombre ?? '—' }}</span></td>
                    <td>
                        <strong>{{ $seg->titulo }}</strong><br>
                        {{ $seg->descripcion }}
                    </td>
                    <td>{{ $seg->usuario?->name ?? '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="pie">
        Documento generado automáticamente por el sistema de gestión del Estudio Jurídico &middot; {{ now()->format('d/m/Y H:i') }}h
    </div>

</div>
</body>
</html>
