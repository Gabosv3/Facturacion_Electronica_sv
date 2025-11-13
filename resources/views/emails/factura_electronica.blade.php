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
        .container {
            background-color: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header {
            background-color: #3b82f6;
            color: white;
            padding: 30px 20px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .header p {
            margin: 10px 0 0 0;
            font-size: 14px;
            opacity: 0.9;
        }
        .content {
            padding: 30px 20px;
        }
        .greeting {
            font-size: 16px;
            margin-bottom: 20px;
        }
        .info-section {
            background-color: #f9fafb;
            padding: 20px;
            border-radius: 6px;
            margin: 20px 0;
        }
        .info-row {
            margin: 12px 0;
            padding: 12px;
            background-color: white;
            border-radius: 4px;
            border-left: 3px solid #3b82f6;
        }
        .label {
            font-weight: bold;
            color: #4b5563;
            display: inline-block;
            min-width: 150px;
        }
        .value {
            color: #1f2937;
        }
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
        }
        .badge-blue {
            background-color: #dbeafe;
            color: #1e40af;
        }
        .badge-green {
            background-color: #d1fae5;
            color: #065f46;
        }
        .badge-purple {
            background-color: #e9d5ff;
            color: #6b21a8;
        }
        .badge-orange {
            background-color: #fed7aa;
            color: #92400e;
        }
        .total-box {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
            padding: 20px;
            border-radius: 6px;
            text-align: center;
            margin: 20px 0;
        }
        .total-box .label {
            color: rgba(255,255,255,0.9);
            font-size: 14px;
            margin-bottom: 5px;
        }
        .total-box .amount {
            font-size: 32px;
            font-weight: bold;
            color: white;
        }
        .attachments {
            background-color: #fef3c7;
            border: 1px solid #fbbf24;
            padding: 15px;
            border-radius: 6px;
            margin: 20px 0;
        }
        .attachments h3 {
            margin: 0 0 10px 0;
            color: #92400e;
            font-size: 14px;
        }
        .attachments ul {
            margin: 0;
            padding-left: 20px;
        }
        .attachments li {
            color: #78350f;
            margin: 5px 0;
        }
        .success-notice {
            background-color: #d1fae5;
            border-left: 4px solid #10b981;
            padding: 15px;
            border-radius: 4px;
            margin: 20px 0;
        }
        .success-notice p {
            margin: 0;
            color: #065f46;
            font-size: 14px;
        }
        .footer {
            background-color: #f9fafb;
            padding: 20px;
            text-align: center;
            border-top: 1px solid #e5e7eb;
        }
        .footer p {
            margin: 5px 0;
            font-size: 13px;
            color: #6b7280;
        }
        .footer .company-name {
            font-weight: bold;
            color: #1f2937;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Factura Electrónica</h1>
            <p>{{ config('dte.emisor.nombre_comercial') }}</p>
        </div>

        <div class="content">
            <div class="greeting">
                <strong>Estimado/a {{ $factura->receptor_nombre }}</strong>
            </div>

            <p>Le enviamos su factura electrónica con los siguientes datos:</p>

            <div class="info-section">
                <div class="info-row">
                    <span class="label">Tipo de Documento:</span>
                    <span class="value">
                        @if($factura->tipo_dte == '01')
                            <span class="badge badge-blue">Factura Electrónica (DTE-01)</span>
                        @elseif($factura->tipo_dte == '03')
                            <span class="badge badge-green">Comprobante de Crédito Fiscal (DTE-03)</span>
                        @elseif($factura->tipo_dte == '05')
                            <span class="badge badge-purple">Nota de Crédito (DTE-05)</span>
                        @elseif($factura->tipo_dte == '14')
                            <span class="badge badge-orange">Factura de Sujeto Excluido (DTE-14)</span>
                        @endif
                    </span>
                </div>

                <div class="info-row">
                    <span class="label">Número de Control:</span>
                    <span class="value">{{ $factura->numero_control }}</span>
                </div>

                <div class="info-row">
                    <span class="label">Código de Generación:</span>
                    <span class="value" style="font-size: 11px;">{{ $factura->codigo_generacion }}</span>
                </div>

                <div class="info-row">
                    <span class="label">Fecha de Emisión:</span>
                    <span class="value">{{ $factura->fecha_emision->format('d/m/Y') }} - {{ $factura->hora_emision }}</span>
                </div>
            </div>

            <div class="total-box">
                <div class="label">TOTAL A PAGAR</div>
                <div class="amount">${{ number_format($factura->total_pagar, 2) }}</div>
            </div>

            <div class="attachments">
                <h3>📎 Archivos Adjuntos</h3>
                <p style="margin: 0 0 10px 0; color: #78350f; font-size: 13px;">
                    Adjunto a este correo encontrará:
                </p>
                <ul>
                    <li><strong>{{ $factura->numero_control }}.json</strong> - Archivo JSON con los datos completos del DTE</li>
                    <li><strong>{{ $factura->numero_control }}.html</strong> - Factura en formato HTML (puede abrirlo en su navegador e imprimirlo como PDF)</li>
                </ul>
                <p style="margin: 10px 0 0 0; font-size: 12px; color: #92400e;">
                    💡 <strong>Tip:</strong> Para convertir el archivo HTML a PDF, ábralo en su navegador web y use la opción "Imprimir" → "Guardar como PDF"
                </p>
            </div>

            @if($factura->estado == 'enviado_mh')
            <div class="success-notice">
                <p>
                    <strong>✓ Documento Validado</strong><br>
                    Este documento ha sido recibido y validado por el Ministerio de Hacienda de El Salvador.
                </p>
            </div>
            @endif

            <p style="margin-top: 20px; font-size: 14px; color: #6b7280;">
                Si tiene alguna consulta sobre esta factura, no dude en contactarnos.
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
