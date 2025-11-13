<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Factura - {{ $factura->numero_control }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            padding: 20px;
            background: white;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
        }

        .header {
            border-bottom: 3px solid #2563eb;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }

        .header-top {
            display: table;
            width: 100%;
            margin-bottom: 10px;
        }

        .header-left {
            display: table-cell;
            width: 60%;
            vertical-align: top;
        }

        .header-right {
            display: table-cell;
            width: 40%;
            text-align: right;
            vertical-align: top;
        }

        .company-name {
            font-size: 18px;
            font-weight: bold;
            color: #1f2937;
            margin-bottom: 5px;
        }

        .company-info {
            font-size: 10px;
            color: #6b7280;
            line-height: 1.6;
        }

        .document-title {
            font-size: 16px;
            font-weight: bold;
            color: #2563eb;
            margin-bottom: 5px;
        }

        .document-type {
            font-size: 11px;
            color: #6b7280;
        }

        .info-section {
            margin-bottom: 20px;
        }

        .section-title {
            font-size: 13px;
            font-weight: bold;
            color: #1f2937;
            margin-bottom: 8px;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 3px;
        }

        .info-grid {
            display: table;
            width: 100%;
        }

        .info-col {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            padding-right: 10px;
        }

        .info-row {
            margin-bottom: 5px;
        }

        .info-label {
            font-weight: bold;
            color: #4b5563;
            font-size: 10px;
        }

        .info-value {
            color: #1f2937;
            font-size: 11px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        table thead {
            background-color: #f3f4f6;
        }

        table th {
            padding: 8px 5px;
            text-align: left;
            font-size: 10px;
            font-weight: bold;
            color: #374151;
            border-bottom: 2px solid #d1d5db;
        }

        table th.right {
            text-align: right;
        }

        table td {
            padding: 6px 5px;
            font-size: 10px;
            border-bottom: 1px solid #e5e7eb;
        }

        table td.right {
            text-align: right;
        }

        .totals-section {
            margin-top: 15px;
            border-top: 2px solid #2563eb;
            padding-top: 10px;
        }

        .totals-grid {
            display: table;
            width: 100%;
        }

        .totals-left {
            display: table-cell;
            width: 60%;
            vertical-align: top;
        }

        .totals-right {
            display: table-cell;
            width: 40%;
            vertical-align: top;
        }

        .total-row {
            display: table;
            width: 100%;
            margin-bottom: 5px;
        }

        .total-label {
            display: table-cell;
            text-align: right;
            padding-right: 10px;
            font-size: 11px;
            color: #4b5563;
        }

        .total-value {
            display: table-cell;
            text-align: right;
            font-size: 11px;
            font-weight: bold;
            width: 100px;
        }

        .grand-total {
            background-color: #2563eb;
            color: white;
            padding: 8px;
            margin-top: 5px;
        }

        .grand-total .total-label {
            color: white;
            font-size: 12px;
            font-weight: bold;
        }

        .grand-total .total-value {
            color: white;
            font-size: 14px;
        }

        .total-words {
            font-size: 10px;
            color: #6b7280;
            font-style: italic;
            margin-top: 5px;
            padding: 5px;
            background-color: #f9fafb;
        }

        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            font-size: 9px;
            color: #6b7280;
        }

        .qr-section {
            margin-top: 20px;
            padding: 10px;
            background-color: #f9fafb;
            border: 1px solid #e5e7eb;
            text-align: center;
        }

        .qr-section img {
            max-width: 150px;
            margin: 10px auto;
        }

        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 3px;
            font-size: 10px;
            font-weight: bold;
        }

        .status-enviado {
            background-color: #d1fae5;
            color: #065f46;
        }

        .status-firmado {
            background-color: #dbeafe;
            color: #1e40af;
        }

        @media print {
            body {
                padding: 0;
            }
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="header-top">
                <div class="header-left">
                    <div class="company-name">{{ config('dte.emisor.nombre_comercial') }}</div>
                    <div class="company-info">
                        {{ config('dte.emisor.nombre') }}<br>
                        NIT: {{ config('dte.emisor.nit') }} | NRC: {{ config('dte.emisor.nrc') }}<br>
                        Tel: {{ config('dte.emisor.telefono') }} | Email: {{ config('dte.emisor.correo') }}
                    </div>
                </div>
                <div class="header-right">
                    <div class="document-title">
                        @if($factura->tipo_dte == '01')
                            FACTURA ELECTRÓNICA
                        @elseif($factura->tipo_dte == '03')
                            COMPROBANTE DE CRÉDITO FISCAL
                        @elseif($factura->tipo_dte == '05')
                            NOTA DE CRÉDITO
                        @elseif($factura->tipo_dte == '14')
                            FACTURA DE SUJETO EXCLUIDO
                        @endif
                    </div>
                    <div class="document-type">DTE-{{ $factura->tipo_dte }}</div>
                    @if($factura->estado == 'enviado_mh')
                        <div style="margin-top: 5px;">
                            <span class="status-badge status-enviado">✓ PROCESADO MH</span>
                        </div>
                    @elseif($factura->estado == 'firmado')
                        <div style="margin-top: 5px;">
                            <span class="status-badge status-firmado">✓ FIRMADO</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Document Info -->
        <div class="info-section">
            <div class="section-title">INFORMACIÓN DEL DOCUMENTO</div>
            <div class="info-grid">
                <div class="info-col">
                    <div class="info-row">
                        <span class="info-label">Número de Control:</span>
                        <span class="info-value">{{ $factura->numero_control }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Código de Generación:</span>
                        <span class="info-value" style="font-size: 9px;">{{ $factura->codigo_generacion }}</span>
                    </div>
                </div>
                <div class="info-col">
                    <div class="info-row">
                        <span class="info-label">Fecha de Emisión:</span>
                        <span class="info-value">{{ $factura->fecha_emision->format('d/m/Y') }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Hora:</span>
                        <span class="info-value">{{ $factura->hora_emision }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Client Info -->
        <div class="info-section">
            <div class="section-title">DATOS DEL CLIENTE</div>
            <div class="info-grid">
                <div class="info-col">
                    <div class="info-row">
                        <span class="info-label">Nombre:</span>
                        <span class="info-value">{{ $factura->receptor_nombre }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Documento:</span>
                        <span class="info-value">{{ $factura->receptor_num_documento }}</span>
                    </div>
                    @if($factura->receptor_nrc)
                    <div class="info-row">
                        <span class="info-label">NRC:</span>
                        <span class="info-value">{{ $factura->receptor_nrc }}</span>
                    </div>
                    @endif
                </div>
                <div class="info-col">
                    @if($factura->receptor_telefono)
                    <div class="info-row">
                        <span class="info-label">Teléfono:</span>
                        <span class="info-value">{{ $factura->receptor_telefono }}</span>
                    </div>
                    @endif
                    @if($factura->receptor_correo)
                    <div class="info-row">
                        <span class="info-label">Email:</span>
                        <span class="info-value">{{ $factura->receptor_correo }}</span>
                    </div>
                    @endif
                    @if($factura->receptor_direccion)
                    <div class="info-row">
                        <span class="info-label">Dirección:</span>
                        <span class="info-value">{{ $factura->receptor_direccion }}</span>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Items Table -->
        <div class="info-section">
            <div class="section-title">DETALLE DE PRODUCTOS/SERVICIOS</div>
            <table>
                <thead>
                    <tr>
                        <th style="width: 5%;">#</th>
                        <th style="width: 10%;">Código</th>
                        <th style="width: 40%;">Descripción</th>
                        <th class="right" style="width: 10%;">Cant.</th>
                        <th class="right" style="width: 12%;">P. Unit.</th>
                        <th class="right" style="width: 10%;">Desc.</th>
                        <th class="right" style="width: 13%;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($factura->items as $item)
                    <tr>
                        <td>{{ $item->num_item }}</td>
                        <td>{{ $item->codigo }}</td>
                        <td>{{ $item->descripcion }}</td>
                        <td class="right">{{ number_format($item->cantidad, 2) }}</td>
                        <td class="right">${{ number_format($item->precio_unitario, 2) }}</td>
                        <td class="right">${{ number_format($item->monto_descuento, 2) }}</td>
                        <td class="right"><strong>${{ number_format($item->venta_gravada, 2) }}</strong></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Totals -->
        <div class="totals-section">
            <div class="totals-grid">
                <div class="totals-left">
                    <div class="total-words">
                        <strong>Son:</strong> {{ $factura->total_letras }}
                    </div>
                </div>
                <div class="totals-right">
                    @if($factura->total_gravada > 0)
                    <div class="total-row">
                        <span class="total-label">Subtotal Gravado:</span>
                        <span class="total-value">${{ number_format($factura->total_gravada, 2) }}</span>
                    </div>
                    @endif

                    @if($factura->total_exenta > 0)
                    <div class="total-row">
                        <span class="total-label">Subtotal Exento:</span>
                        <span class="total-value">${{ number_format($factura->total_exenta, 2) }}</span>
                    </div>
                    @endif

                    @if($factura->total_descuento > 0)
                    <div class="total-row">
                        <span class="total-label">Descuento:</span>
                        <span class="total-value">-${{ number_format($factura->total_descuento, 2) }}</span>
                    </div>
                    @endif

                    @if($factura->total_iva > 0)
                    <div class="total-row">
                        <span class="total-label">IVA ({{ $factura->tipo_dte == '01' ? '11.5%' : '13%' }}):</span>
                        <span class="total-value">${{ number_format($factura->total_iva, 2) }}</span>
                    </div>
                    @endif

                    <div class="total-row grand-total">
                        <span class="total-label">TOTAL A PAGAR:</span>
                        <span class="total-value">${{ number_format($factura->total_pagar, 2) }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- QR Code (only if enviado_mh) -->
        @if($factura->estado == 'enviado_mh')
        <div class="qr-section">
            <div style="font-size: 11px; font-weight: bold; margin-bottom: 5px;">
                Código QR - Consulta Pública MH
            </div>
            <img src="{{ $factura->getQrCodeUrl(150) }}" alt="Código QR">
            <div style="font-size: 8px; color: #6b7280; margin-top: 5px;">
                Escanea para verificar en admin.factura.gob.sv
            </div>
        </div>
        @endif

        <!-- Footer -->
        <div class="footer">
            <p>Este documento es una representación impresa de la factura electrónica generada según Ley de Simplificación Aduanera.</p>
            <p>Documento generado electrónicamente - No requiere firma autógrafa</p>
        </div>
    </div>
</body>
</html>
