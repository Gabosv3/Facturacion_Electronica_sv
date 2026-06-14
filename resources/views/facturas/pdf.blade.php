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
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 11px;
            line-height: 1.3;
            color: #333;
            padding: 30px;
            background: white;
        }

        .container {
            width: 100%;
            margin: 0 auto;
        }

        /* Header Styles */
        .header-table {
            width: 100%;
            margin-bottom: 20px;
            border-bottom: 2px solid #1e3a8a; /* Dark Blue */
            padding-bottom: 10px;
        }

        .company-logo-box {
            width: 120px;
            height: 80px;
            background-color: #f3f4f6;
            text-align: center;
            line-height: 80px;
            color: #9ca3af;
            font-weight: bold;
            border: 1px dashed #d1d5db;
        }

        .company-info {
            padding-left: 15px;
            vertical-align: top;
        }

        .company-name {
            font-size: 16px;
            font-weight: bold;
            color: #1e3a8a;
            text-transform: uppercase;
            margin-bottom: 4px;
        }

        .company-details {
            font-size: 10px;
            color: #4b5563;
        }

        .invoice-box {
            width: 280px;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
            padding: 10px;
            background-color: #f9fafb;
            vertical-align: top;
        }

        .invoice-title {
            font-size: 14px;
            font-weight: bold;
            color: #1e3a8a;
            text-align: center;
            margin-bottom: 5px;
            text-transform: uppercase;
        }

        .invoice-subtitle {
            font-size: 10px;
            text-align: center;
            color: #6b7280;
            margin-bottom: 8px;
            font-family: monospace;
        }

        .invoice-details-table {
            width: 100%;
            font-size: 9px;
        }

        .invoice-details-table td {
            padding: 2px 0;
        }

        .label {
            font-weight: bold;
            color: #4b5563;
        }

        .value {
            text-align: right;
            font-family: monospace;
            color: #111827;
        }

        /* Client Section */
        .section-box {
            border: 1px solid #e5e7eb;
            border-radius: 4px;
            padding: 10px;
            margin-bottom: 15px;
        }

        .section-title {
            font-size: 10px;
            font-weight: bold;
            color: #1e3a8a;
            text-transform: uppercase;
            margin-bottom: 8px;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 2px;
        }

        .client-table {
            width: 100%;
            font-size: 10px;
        }

        .client-table td {
            padding: 2px 5px;
            vertical-align: top;
        }

        /* Items Table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 10px;
        }

        .items-table th {
            background-color: #1e3a8a;
            color: white;
            padding: 6px 4px;
            text-align: left;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 9px;
        }

        .items-table th.text-right {
            text-align: right;
        }

        .items-table th.text-center {
            text-align: center;
        }

        .items-table td {
            border-bottom: 1px solid #e5e7eb;
            padding: 6px 4px;
            color: #374151;
        }

        .items-table tr:nth-child(even) {
            background-color: #f9fafb;
        }

        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-mono { font-family: monospace; }

        /* Totals Section */
        .totals-container {
            width: 100%;
            margin-top: 10px;
            page-break-inside: avoid;
        }

        .totals-table {
            width: 100%;
            border-collapse: collapse;
        }

        .totals-table td {
            padding: 3px 0;
            vertical-align: top;
        }

        .amount-words {
            font-size: 9px;
            font-style: italic;
            color: #4b5563;
            padding-right: 20px;
            width: 50%;
        }

        .totals-box {
            width: 50%;
        }

        /* Reemplazamos flex por tabla interna para mejor compatibilidad PDF */
        .totals-inner-table {
            width: 100%;
            border-collapse: collapse;
        }

        .totals-inner-table td {
            padding: 2px 0;
            font-size: 10px;
        }

        .totals-label {
            color: #4b5563;
            text-align: right;
            padding-right: 10px;
            width: 70%;
        }

        .totals-value {
            text-align: right;
            font-family: monospace;
            font-weight: 600;
            width: 30%;
        }

        .totals-row-final td {
            border-top: 2px solid #1e3a8a;
            padding-top: 5px;
            font-weight: bold;
            font-size: 12px;
            color: #1e3a8a;
        }

        /* Footer */
        .footer {
            margin-top: 30px;
            border-top: 1px solid #e5e7eb;
            padding-top: 10px;
            font-size: 8px;
            color: #9ca3af;
            text-align: center;
        }

        .qr-container {
            text-align: center;
            margin-top: 10px;
        }

        .qr-img {
            width: 100px;
            height: 100px;
        }
    </style>
</head>
<body>
    @php
        $resumen = $factura->dte_json['dteJson']['resumen'] ?? [];
        
        // Extracción segura de valores del JSON
        $totalGravada = $resumen['totalGravada'] ?? 0;
        $totalExenta = $resumen['totalExenta'] ?? 0;
        $totalNoSujeta = $resumen['totalNoSujeta'] ?? 0;
        
        // Suma total de operaciones (Gravadas + Exentas + No Sujetas)
        $sumaOperaciones = $totalGravada + $totalExenta + $totalNoSujeta;
        
        $descuentoNoSujeta = $resumen['descuentoNoSujeta'] ?? 0;
        $descuentoExenta = $resumen['descuentoExenta'] ?? 0;
        $descuentoGravada = $resumen['descuentoGravada'] ?? 0;
        
        $subTotal = $resumen['subTotal'] ?? 0;
        $ivaPercibido = $resumen['ivaPerci1'] ?? 0;
        $ivaRetenido = $resumen['ivaRete1'] ?? 0;
        $reteRenta = $resumen['reteRenta'] ?? 0;
        $montoTotalOperacion = $resumen['montoTotalOperacion'] ?? 0;
        $totalNoGravado = $resumen['totalNoGravado'] ?? 0;
        $totalPagar = $resumen['totalPagar'] ?? 0;
        
        // IVA (puede venir en tributos o calculado)
        $totalIva = $factura->total_iva;

        // Datos adicionales del JSON
        $condicionOperacion = $resumen['condicionOperacion'] ?? 1;
        $condicionTxt = match((int)$condicionOperacion) {
            1 => 'Contado',
            2 => 'Crédito',
            3 => 'Otro',
            default => 'Contado'
        };
        
        $pagos = $factura->dte_json['dteJson']['pagos'] ?? [];
        $formaPagoCodigo = count($pagos) > 0 ? ($pagos[0]['codigo'] ?? '') : '';

        // Catálogos MH
        $catDepartamentos = [
            '00' => 'OTRO (PARA EXTRANJEROS)',
            '01' => 'AHUACHAPÁN',
            '02' => 'SANTA ANA',
            '03' => 'SONSONATE',
            '04' => 'CHALATENANGO',
            '05' => 'LA LIBERTAD',
            '06' => 'SAN SALVADOR',
            '07' => 'CUSCATLÁN',
            '08' => 'LA PAZ',
            '09' => 'CABAÑAS',
            '10' => 'SAN VICENTE',
            '11' => 'USULUTÁN',
            '12' => 'SAN MIGUEL',
            '13' => 'MORAZÁN',
            '14' => 'LA UNIÓN',
        ];

        $catMunicipios = [
            '01' => [ // Ahuachapán
                '13' => 'AHUACHAPAN NORTE',
                '14' => 'AHUACHAPAN CENTRO',
                '15' => 'AHUACHAPAN SUR',
            ],
            '02' => [ // Santa Ana
                '14' => 'SANTA ANA NORTE',
                '15' => 'SANTA ANA CENTRO',
                '16' => 'SANTA ANA ESTE',
                '17' => 'SANTA ANA OESTE',
            ],
            '03' => [ // Sonsonate
                '17' => 'SONSONATE NORTE',
                '18' => 'SONSONATE CENTRO',
                '19' => 'SONSONATE ESTE',
                '20' => 'SONSONATE OESTE',
            ],
            '04' => [ // Chalatenango
                '34' => 'CHALATENANGO NORTE',
                '35' => 'CHALATENANGO CENTRO',
                '36' => 'CHALATENANGO SUR',
            ],
            '05' => [ // La Libertad
                '23' => 'LA LIBERTAD NORTE',
                '24' => 'LA LIBERTAD CENTRO',
                '25' => 'LA LIBERTAD OESTE',
                '26' => 'LA LIBERTAD ESTE',
                '27' => 'LA LIBERTAD COSTA',
                '28' => 'LA LIBERTAD SUR',
            ],
            '06' => [ // San Salvador
                '20' => 'SAN SALVADOR NORTE',
                '21' => 'SAN SALVADOR OESTE',
                '22' => 'SAN SALVADOR ESTE',
                '23' => 'SAN SALVADOR CENTRO',
                '24' => 'SAN SALVADOR SUR',
            ],
            '07' => [ // Cuscatlán
                '17' => 'CUSCATLAN NORTE',
                '18' => 'CUSCATLAN SUR',
            ],
            '08' => [ // La Paz
                '23' => 'LA PAZ OESTE',
                '24' => 'LA PAZ CENTRO',
                '25' => 'LA PAZ ESTE',
            ],
            '09' => [ // Cabañas
                '10' => 'CABAÑAS OESTE',
                '11' => 'CABAÑAS ESTE',
            ],
            '10' => [ // San Vicente
                '14' => 'SAN VICENTE NORTE',
                '15' => 'SAN VICENTE SUR',
            ],
            '11' => [ // Usulután
                '24' => 'USULUTAN NORTE',
                '25' => 'USULUTAN ESTE',
                '26' => 'USULUTAN OESTE',
            ],
            '12' => [ // San Miguel
                '21' => 'SAN MIGUEL NORTE',
                '22' => 'SAN MIGUEL CENTRO',
                '23' => 'SAN MIGUEL OESTE',
            ],
            '13' => [ // Morazán
                '27' => 'MORAZAN NORTE',
                '28' => 'MORAZAN SUR',
            ],
            '14' => [ // La Unión
                '19' => 'LA UNION NORTE',
                '20' => 'LA UNION SUR',
            ],
        ];

        $catEstablecimiento = [
            '01' => 'Sucursal',
            '02' => 'Casa Matriz',
            '04' => 'Bodega',
            '07' => 'Patio',
        ];
    @endphp

    <div class="container">
        <!-- Header -->
        <table class="header-table">
            <tr>
                <td style="width: 130px; vertical-align: top;">
                    <div class="company-logo-box" style="background: transparent; border: none;">
                        <img src="{{ public_path('images/logo.jpg') }}" alt="LOGO" style="max-width: 100%; max-height: 80px;">
                    </div>
                </td>
                <td class="company-info">
                    @php
                        $emisor = $factura->dte_json['dteJson']['emisor'] ?? [];
                        $direccionEmisor = $emisor['direccion'] ?? [];
                        
                        $deptEmisor = $direccionEmisor['departamento'] ?? '';
                        $muniEmisor = $direccionEmisor['municipio'] ?? '';
                        $nombreDeptEmisor = $catDepartamentos[$deptEmisor] ?? $deptEmisor;
                        $nombreMuniEmisor = $catMunicipios[$deptEmisor][$muniEmisor] ?? $muniEmisor;
                        
                        $tipoEstablecimiento = $emisor['tipoEstablecimiento'] ?? '';
                        $nombreTipoEstablecimiento = $catEstablecimiento[$tipoEstablecimiento] ?? 'Sucursal';
                    @endphp
                    <div class="company-name">{{ $emisor['nombreComercial'] ?? config('dte.emisor.nombre_comercial') }}</div>
                    <div class="company-details">
                        {{ $emisor['nombre'] ?? config('dte.emisor.nombre') }}<br>
                        <strong>NIT:</strong> {{ $emisor['nit'] ?? config('dte.emisor.nit') }} | <strong>NRC:</strong> {{ $emisor['nrc'] ?? config('dte.emisor.nrc') }}<br>
                        {{ $direccionEmisor['complemento'] ?? config('dte.emisor.direccion.complemento') }}<br>
                        {{ $nombreMuniEmisor }}, {{ $nombreDeptEmisor }}<br>
                        <strong>Tel:</strong> {{ $emisor['telefono'] ?? config('dte.emisor.telefono') }} | <strong>Email:</strong> {{ $emisor['correo'] ?? config('dte.emisor.correo') }}<br>
                        {{ $emisor['descActividad'] ?? config('dte.emisor.actividad_economica_desc') }}<br>
                        <strong>{{ $nombreTipoEstablecimiento }}:</strong> {{ $emisor['codEstableMH'] ?? $emisor['codEstable'] ?? '' }} | <strong>Punto Venta:</strong> {{ $emisor['codPuntoVentaMH'] ?? $emisor['codPuntoVenta'] ?? '' }}
                    </div>
                </td>
                <td style="width: 290px; vertical-align: top;">
                    <div class="invoice-box">
                        <div class="invoice-title">
                            @if($factura->tipo_dte == '01') FACTURA ELECTRÓNICA
                            @elseif($factura->tipo_dte == '03') COMPROBANTE DE CRÉDITO FISCAL
                            @elseif($factura->tipo_dte == '05') NOTA DE CRÉDITO
                            @elseif($factura->tipo_dte == '14') FACTURA DE SUJETO EXCLUIDO
                            @else DOCUMENTO TRIBUTARIO ELECTRÓNICO @endif
                        </div>
                        <div class="invoice-subtitle">DTE-{{ $factura->tipo_dte }}</div>
                        
                        <table class="invoice-details-table">
                            <tr>
                                <td class="label">CÓDIGO GENERACIÓN:</td>
                            </tr>
                            <tr>
                                <td class="value" style="font-size: 8px; padding-bottom: 4px;">{{ $factura->codigo_generacion }}</td>
                            </tr>
                            <tr>
                                <td class="label">NÚMERO DE CONTROL:</td>
                                <td class="value">{{ $factura->numero_control }}</td>
                            </tr>
                            <tr>
                                <td class="label">SELLO DE RECEPCIÓN:</td>
                            </tr>
                            <tr>
                                <td class="value" style="font-size: 8px;">
                                    @php
                                        $selloRecepcion = 'PENDIENTE';
                                        if ($factura->respuesta_mh) {
                                            $respuestaMH = json_decode($factura->respuesta_mh, true);
                                            if (is_array($respuestaMH)) {
                                                $selloRecepcion = $respuestaMH['envio_mh']['selloRecibido'] 
                                                    ?? $respuestaMH['selloRecibido'] 
                                                    ?? $respuestaMH['data']['selloRecibido'] 
                                                    ?? 'PENDIENTE';
                                            }
                                        }
                                    @endphp
                                    {{ $selloRecepcion }}
                                </td>
                            </tr>
                            <tr>
                                <td class="label">FECHA EMISIÓN:</td>
                                <td class="value">{{ $factura->fecha_emision->format('d/m/Y') }}</td>
                            </tr>
                            <tr>
                                <td class="label">HORA EMISIÓN:</td>
                                <td class="value">{{ $factura->hora_emision }}</td>
                            </tr>
                            <tr>
                                <td class="label">CONDICIÓN OPERACIÓN:</td>
                                <td class="value">{{ $condicionTxt }}</td>
                            </tr>
                            @if($formaPagoCodigo)
                            <tr>
                                <td class="label">FORMA DE PAGO:</td>
                                <td class="value">{{ $formaPagoCodigo }}</td>
                            </tr>
                            @endif
                        </table>
                    </div>
                </td>
            </tr>
        </table>

        <!-- Client Info -->
        <div class="section-box">
            <div class="section-title">DATOS DEL RECEPTOR</div>
            @php
                $receptor = $factura->dte_json['dteJson']['receptor'] ?? [];
                $direccionReceptor = $receptor['direccion'] ?? [];
                // Decodificar si viene como string JSON (caso legacy)
                if (is_string($direccionReceptor)) $direccionReceptor = json_decode($direccionReceptor, true);
                
                $deptReceptor = $direccionReceptor['departamento'] ?? '';
                $muniReceptor = $direccionReceptor['municipio'] ?? '';
                $nombreDeptReceptor = $catDepartamentos[$deptReceptor] ?? $deptReceptor;
                $nombreMuniReceptor = $catMunicipios[$deptReceptor][$muniReceptor] ?? $muniReceptor;
            @endphp
            <table class="client-table">
                <tr>
                    <td style="width: 15%; font-weight: bold; color: #4b5563;">NOMBRE:</td>
                    <td style="width: 45%;">{{ $receptor['nombre'] ?? $factura->receptor_nombre }}</td>
                    <td style="width: 15%; font-weight: bold; color: #4b5563;">DOCUMENTO:</td>
                    <td style="width: 25%;">{{ $receptor['numDocumento'] ?? $factura->receptor_num_documento }}</td>
                </tr>
                <tr>
                    <td style="font-weight: bold; color: #4b5563;">DIRECCIÓN:</td>
                    <td>
                        {{ $direccionReceptor['complemento'] ?? $factura->receptor_direccion }}
                        @if($nombreMuniReceptor || $nombreDeptReceptor)
                            <br><span style="font-size: 9px; color: #666;">{{ $nombreMuniReceptor }}, {{ $nombreDeptReceptor }}</span>
                        @endif
                    </td>
                    <td style="font-weight: bold; color: #4b5563;">TELÉFONO:</td>
                    <td>{{ $receptor['telefono'] ?? $factura->receptor_telefono }}</td>
                </tr>
                <tr>
                    <td style="font-weight: bold; color: #4b5563;">NRC:</td>
                    <td>{{ $receptor['nrc'] ?? $factura->receptor_nrc }}</td>
                    <td style="font-weight: bold; color: #4b5563;">EMAIL:</td>
                    <td>{{ $receptor['correo'] ?? $factura->receptor_correo }}</td>
                </tr>
                <tr>
                    <td style="font-weight: bold; color: #4b5563;">GIRO:</td>
                    <td colspan="3">
                        {{ $receptor['descActividad'] ?? $factura->receptor_actividad_economica }}
                        @if(isset($receptor['codActividad']))
                            <span style="color: #6b7280;">(Cód: {{ $receptor['codActividad'] }})</span>
                        @endif
                    </td>
                </tr>
            </table>
        </div>

        <!-- Items Table -->
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 5%;" class="text-center">CANT</th>
                    <th style="width: 10%;">UNIDAD</th>
                    <th style="width: 45%;">DESCRIPCIÓN</th>
                    <th style="width: 10%;" class="text-right">PRECIO UNIT.</th>
                    <th style="width: 10%;" class="text-right">DESC.</th>
                    <th style="width: 10%;" class="text-right">NO SUJETA</th>
                    <th style="width: 10%;" class="text-right">EXENTA</th>
                    <th style="width: 10%;" class="text-right">GRAVADA</th>
                </tr>
            </thead>
            <tbody>
                @foreach($factura->items as $item)
                <tr>
                    <td class="text-center font-mono">{{ number_format($item->cantidad, 2) }}</td>
                    <td style="font-size: 9px;">{{ $item->unidad_medida ?? 'Unidad' }}</td>
                    <td>
                        {{ $item->descripcion }}
                        @if($item->codigo)<br><span style="color: #6b7280; font-size: 8px;">COD: {{ $item->codigo }}</span>@endif
                    </td>
                    <td class="text-right font-mono">${{ number_format($item->precio_unitario, 2) }}</td>
                    <td class="text-right font-mono">{{ $item->monto_descuento > 0 ? '$'.number_format($item->monto_descuento, 2) : '' }}</td>
                    <td class="text-right font-mono">{{ $item->venta_nosujeta > 0 ? '$'.number_format($item->venta_nosujeta, 2) : '' }}</td>
                    <td class="text-right font-mono">{{ $item->venta_exenta > 0 ? '$'.number_format($item->venta_exenta, 2) : '' }}</td>
                    <td class="text-right font-mono font-bold">
                        @if($factura->tipo_dte === '01' || $factura->tipo_dte === '14')
                            ${{ number_format(($item->cantidad * $item->precio_unitario) - $item->monto_descuento, 2) }}
                        @else
                            ${{ number_format($item->venta_gravada, 2) }}
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Totals Section -->
        <table class="totals-table">
            <tr>
                <td class="amount-words">
                    <strong>SON:</strong><br>
                    {{ $factura->total_letras }}
                    
                    <div class="qr-container" style="margin-top: 15px;">
                        @php
                            // Intentar obtener QR localmente primero (SimpleQRCode), luego API externa
                            $qrBase64 = $factura->getQrCodeLocalBase64(150);
                            if (!$qrBase64) {
                                $qrBase64 = $factura->getQrCodeBase64(150);
                            }
                        @endphp
                        @if($qrBase64)
                            {{-- Usamos una etiqueta img simple sin clases para evitar conflictos --}}
                            <img src="data:image/png;base64,{{ $qrBase64 }}" width="100" height="100" style="display: block; margin: 0 auto;">
                            <div style="font-size: 7px; margin-top: 4px; color: #666;">Sello de Validación Electrónica</div>
                        @else
                            <div style="border: 1px dashed #ccc; width: 100px; height: 100px; margin: 0 auto; line-height: 100px; font-size: 8px; color: #999; background: #f9fafb; text-align: center;">
                                QR NO DISPONIBLE
                            </div>
                        @endif
                        <div style="font-size: 8px; margin-top: 5px; font-family: monospace;">{{ $factura->codigo_generacion }}</div>
                    </div>
                </td>
                <td class="totals-box">
                    <table class="totals-inner-table">
                        <tr>
                            <td class="totals-label">Suma total de operaciones:</td>
                            <td class="totals-value">${{ number_format($sumaOperaciones, 2) }}</td>
                        </tr>
                        <tr>
                            <td class="totals-label">Monto global desc., rebajas y otros a Ventas No Sujetas:</td>
                            <td class="totals-value">${{ number_format($descuentoNoSujeta, 2) }}</td>
                        </tr>
                        <tr>
                            <td class="totals-label">Monto global desc., rebajas y otros a V. Ex.:</td>
                            <td class="totals-value">${{ number_format($descuentoExenta, 2) }}</td>
                        </tr>
                        <tr>
                            <td class="totals-label">Monto global desc., rebajas y otros a V. Gr.:</td>
                            <td class="totals-value">${{ number_format($descuentoGravada, 2) }}</td>
                        </tr>
                        
                        @if($factura->tipo_dte !== '01' && $factura->tipo_dte !== '14')
                        <tr>
                            <td class="totals-label">Impuesto al Valor Agregado 13%:</td>
                            <td class="totals-value">${{ number_format($totalIva, 2) }}</td>
                        </tr>
                        @else
                        <tr>
                            <td class="totals-label">Impuesto al Valor Agregado 13%:</td>
                            <td class="totals-value">$0.00</td>
                        </tr>
                        @endif

                        <tr style="border-top: 1px solid #e5e7eb;">
                            <td class="totals-label" style="font-weight: bold; padding-top: 4px;">Sub-total:</td>
                            <td class="totals-value" style="font-weight: bold; padding-top: 4px;">${{ number_format($subTotal, 2) }}</td>
                        </tr>

                        <tr>
                            <td class="totals-label">IVA percibido:</td>
                            <td class="totals-value">${{ number_format($ivaPercibido, 2) }}</td>
                        </tr>
                        <tr>
                            <td class="totals-label">IVA retenido:</td>
                            <td class="totals-value">${{ number_format($ivaRetenido, 2) }}</td>
                        </tr>
                        <tr>
                            <td class="totals-label">Retención renta:</td>
                            <td class="totals-value">${{ number_format($reteRenta, 2) }}</td>
                        </tr>
                        <tr>
                            <td class="totals-label">Monto total de la operación:</td>
                            <td class="totals-value">${{ number_format($montoTotalOperacion, 2) }}</td>
                        </tr>
                        <tr>
                            <td class="totals-label">Total otros montos no afectos:</td>
                            <td class="totals-value">${{ number_format($totalNoGravado, 2) }}</td>
                        </tr>
                        
                        <tr class="totals-row-final">
                            <td class="totals-label" style="color: #1e3a8a; font-weight: bold;">Total a pagar (USD):</td>
                            <td class="totals-value" style="color: #1e3a8a; font-weight: bold;">${{ number_format($totalPagar, 2) }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <!-- Footer -->
        <div class="footer">
            <p>Este documento es una representación gráfica de un Documento Tributario Electrónico (DTE) conforme a la legislación de El Salvador.</p>
            <p>Responsable de la emisión: {{ config('dte.emisor.nombre') }}</p>
        </div>
    </div>
</body>
</html>
