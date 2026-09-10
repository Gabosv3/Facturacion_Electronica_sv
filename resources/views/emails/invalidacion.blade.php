<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f3f4f6;
        }
        .container { background-color: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .header { background-color: #b91c1c; color: white; padding: 30px 20px; text-align: center; }
        .header h1 { margin: 0; font-size: 24px; }
        .header p { margin: 10px 0 0 0; font-size: 14px; opacity: 0.9; }
        .content { padding: 30px 20px; }
        .greeting { font-size: 16px; margin-bottom: 20px; }
        .info-section { background-color: #f9fafb; padding: 20px; border-radius: 6px; margin: 20px 0; }
        .info-row { margin: 12px 0; padding: 12px; background-color: white; border-radius: 4px; border-left: 3px solid #b91c1c; }
        .label { font-weight: bold; color: #4b5563; display: inline-block; min-width: 160px; }
        .value { color: #1f2937; }
        .attachments { background-color: #fef3c7; border: 1px solid #fbbf24; padding: 15px; border-radius: 6px; margin: 20px 0; }
        .attachments h3 { margin: 0 0 10px 0; color: #92400e; font-size: 14px; }
        .attachments ul { margin: 0; padding-left: 20px; }
        .attachments li { color: #78350f; margin: 5px 0; }
        .notice { background-color: #fee2e2; border-left: 4px solid #b91c1c; padding: 15px; border-radius: 4px; margin: 20px 0; }
        .notice p { margin: 0; color: #7f1d1d; font-size: 14px; }
        .footer { background-color: #f9fafb; padding: 20px; text-align: center; border-top: 1px solid #e5e7eb; }
        .footer p { margin: 5px 0; font-size: 13px; color: #6b7280; }
        .footer .company-name { font-weight: bold; color: #1f2937; font-size: 14px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Invalidación de Documento Tributario Electrónico</h1>
            <p>{{ config('dte.emisor.nombre_comercial') }}</p>
        </div>

        <div class="content">
            <div class="greeting">
                <strong>Estimado/a {{ $evento->factura->receptor_nombre }}</strong>
            </div>

            <div class="notice">
                <p>
                    <strong>⚠ Este documento ha sido invalidado.</strong><br>
                    El documento tributario electrónico detallado abajo fue anulado ante el Ministerio de Hacienda y ya no tiene validez fiscal.
                </p>
            </div>

            <div class="info-section">
                <div class="info-row">
                    <span class="label">Documento Invalidado:</span>
                    <span class="value">{{ $evento->factura->numero_control }} (DTE-{{ $evento->factura->tipo_dte }})</span>
                </div>
                <div class="info-row">
                    <span class="label">Código de Invalidación:</span>
                    <span class="value" style="font-size: 11px;">{{ $evento->codigo_invalidacion }}</span>
                </div>
                <div class="info-row">
                    <span class="label">Fecha del Evento:</span>
                    <span class="value">{{ $evento->fecha_evento->format('d/m/Y H:i:s') }}</span>
                </div>
                <div class="info-row">
                    <span class="label">Motivo:</span>
                    <span class="value">{{ $evento->motivo_invalidacion }}</span>
                </div>
                @if($evento->facturaReemplazo)
                <div class="info-row">
                    <span class="label">Documento de Reemplazo:</span>
                    <span class="value">{{ $evento->facturaReemplazo->numero_control }}</span>
                </div>
                @endif
            </div>

            <div class="attachments">
                <h3>📎 Archivos Adjuntos</h3>
                <p style="margin: 0 0 10px 0; color: #78350f; font-size: 13px;">
                    Adjunto a este correo encontrará:
                </p>
                <ul>
                    <li><strong>{{ $evento->codigo_invalidacion }}.json</strong> - JSON del evento de invalidación</li>
                    <li><strong>invalidacion_{{ $evento->factura->numero_control }}.pdf</strong> - Representación gráfica del evento de invalidación</li>
                    <li><strong>{{ $evento->factura->numero_control }}.pdf</strong> - Representación gráfica del documento original invalidado</li>
                </ul>
            </div>

            <p style="margin-top: 20px; font-size: 14px; color: #6b7280;">
                Si tiene alguna consulta sobre esta invalidación, no dude en contactarnos.
            </p>
        </div>

        <div class="footer">
            <p class="company-name">{{ config('dte.emisor.nombre') }}</p>
            <p>NIT: {{ config('dte.emisor.nit') }} | NRC: {{ config('dte.emisor.nrc') }}</p>
            <p>{{ config('dte.emisor.telefono') }} | {{ config('dte.emisor.correo') }}</p>
            <p style="margin-top: 15px; font-size: 11px; color: #9ca3af;">
                Este es un correo electrónico automático, por favor no responda a este mensaje.
            </p>
        </div>
    </div>
</body>
</html>
