<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Invalidación - {{ $evento->codigo_invalidacion }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 11px;
            line-height: 1.4;
            color: #333;
            padding: 30px;
            background: white;
        }
        .header-table { width: 100%; margin-bottom: 20px; border-bottom: 2px solid #b91c1c; padding-bottom: 10px; }
        .company-name { font-size: 16px; font-weight: bold; color: #b91c1c; text-transform: uppercase; margin-bottom: 4px; }
        .company-details { font-size: 10px; color: #4b5563; }
        .doc-box {
            width: 280px; border: 1px solid #e5e7eb; border-radius: 4px; padding: 10px;
            background-color: #fef2f2; vertical-align: top;
        }
        .doc-title { font-size: 14px; font-weight: bold; color: #b91c1c; text-align: center; margin-bottom: 5px; text-transform: uppercase; }
        .doc-row { font-size: 10px; margin: 3px 0; }
        .doc-row strong { color: #1f2937; }
        .section { margin: 15px 0; }
        .section-title {
            font-size: 12px; font-weight: bold; color: #b91c1c; text-transform: uppercase;
            border-bottom: 1px solid #e5e7eb; padding-bottom: 4px; margin-bottom: 8px;
        }
        table.data { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        table.data td { padding: 4px 6px; font-size: 10px; vertical-align: top; }
        table.data td.label { color: #6b7280; width: 160px; }
        table.data td.value { font-weight: bold; color: #1f2937; }
        .motivo-box { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 4px; padding: 10px; font-size: 11px; }
        .seal-box {
            margin-top: 20px; border: 1px dashed #b91c1c; border-radius: 4px; padding: 10px;
            font-size: 9px; color: #6b7280; word-break: break-all;
        }
        .footer { margin-top: 25px; text-align: center; font-size: 9px; color: #9ca3af; }
    </style>
</head>
<body>
    <table class="header-table">
        <tr>
            <td style="vertical-align: top;">
                <div class="company-name">{{ config('dte.emisor.nombre') }}</div>
                <div class="company-details">
                    NIT: {{ config('dte.emisor.nit') }} | NRC: {{ config('dte.emisor.nrc') }}<br>
                    {{ config('dte.emisor.direccion.complemento') }}<br>
                    Tel: {{ config('dte.emisor.telefono') }} | {{ config('dte.emisor.correo') }}
                </div>
            </td>
            <td style="width: 280px;">
                <div class="doc-box">
                    <div class="doc-title">Invalidación DTE</div>
                    <div class="doc-row"><strong>Código:</strong><br>{{ $evento->codigo_invalidacion }}</div>
                    <div class="doc-row"><strong>Fecha:</strong> {{ $evento->fecha_evento->format('d/m/Y H:i:s') }}</div>
                    <div class="doc-row"><strong>Ambiente:</strong> {{ $evento->ambiente == '00' ? 'Pruebas' : 'Producción' }}</div>
                </div>
            </td>
        </tr>
    </table>

    <div class="section">
        <div class="section-title">Documento Invalidado</div>
        <table class="data">
            <tr>
                <td class="label">Número de Control:</td>
                <td class="value">{{ $evento->factura->numero_control }}</td>
                <td class="label">Tipo DTE:</td>
                <td class="value">DTE-{{ $evento->factura->tipo_dte }}</td>
            </tr>
            <tr>
                <td class="label">Código de Generación:</td>
                <td class="value">{{ $evento->factura->codigo_generacion }}</td>
                <td class="label">Fecha de Emisión:</td>
                <td class="value">{{ $evento->factura->fecha_emision->format('d/m/Y') }}</td>
            </tr>
            <tr>
                <td class="label">Receptor:</td>
                <td class="value">{{ $evento->factura->receptor_nombre }}</td>
                <td class="label">Total:</td>
                <td class="value">${{ number_format($evento->factura->total_pagar, 2) }}</td>
            </tr>
        </table>
    </div>

    @if($evento->facturaReemplazo)
    <div class="section">
        <div class="section-title">Documento de Reemplazo</div>
        <table class="data">
            <tr>
                <td class="label">Número de Control:</td>
                <td class="value">{{ $evento->facturaReemplazo->numero_control }}</td>
                <td class="label">Código de Generación:</td>
                <td class="value">{{ $evento->codigo_generacion_reemplazo }}</td>
            </tr>
        </table>
    </div>
    @endif

    <div class="section">
        <div class="section-title">Motivo de Invalidación</div>
        <div class="motivo-box">
            <strong>Tipo:</strong> {{ $evento->tipo_invalidacion_nombre }}<br>
            <strong>Motivo:</strong> {{ $evento->motivo_invalidacion }}
        </div>
    </div>

    <div class="section">
        <div class="section-title">Responsable y Solicitante</div>
        <table class="data">
            <tr>
                <td class="label">Responsable (Emisor):</td>
                <td class="value">{{ $evento->responsable_nombre }}<br>{{ $evento->responsable_tipo_doc }}: {{ $evento->responsable_num_doc }}</td>
                <td class="label">Solicitante:</td>
                <td class="value">{{ $evento->solicitante_nombre }}<br>{{ $evento->solicitante_tipo_doc }}: {{ $evento->solicitante_num_doc }}</td>
            </tr>
        </table>
    </div>

    @if($evento->sello_recepcion)
    <div class="seal-box">
        <strong>Sello de Recepción MH:</strong><br>
        {{ $evento->sello_recepcion }}
    </div>
    @endif

    <div class="footer">
        Documento generado electrónicamente conforme a las especificaciones del Ministerio de Hacienda de El Salvador.
    </div>
</body>
</html>
