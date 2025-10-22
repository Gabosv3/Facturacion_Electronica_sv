<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle Factura - {{ $factura->numero_control }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto">
            <div class="mb-4">
                <a href="{{ route('facturas.index') }}" class="text-blue-600 hover:text-blue-800 font-medium">
                    ← Volver a Facturas
                </a>
            </div>

            <div class="bg-white rounded-lg shadow-lg p-8">
                <!-- Header -->
                <div class="border-b pb-6 mb-6">
                    <div class="flex justify-between items-start">
                        <div>
                            @if($factura->tipo_dte == '03')
                                <h1 class="text-3xl font-bold text-green-800">COMPROBANTE DE CRÉDITO FISCAL</h1>
                                <p class="text-sm text-green-600 font-semibold mt-1">DTE-03 (CCF)</p>
                            @else
                                <h1 class="text-3xl font-bold text-gray-800">FACTURA ELECTRÓNICA</h1>
                                <p class="text-sm text-blue-600 font-semibold mt-1">DTE-01</p>
                            @endif
                            <p class="text-gray-600 mt-2">{{ config('dte.emisor.nombre_comercial') }}</p>
                            <p class="text-sm text-gray-500">NIT: {{ config('dte.emisor.nit') }}</p>
                            <p class="text-sm text-gray-500">NRC: {{ config('dte.emisor.nrc') }}</p>
                        </div>
                        <div class="text-right">
                            @if($factura->estado == 'enviado_mh')
                                <span class="inline-block px-4 py-2 bg-green-100 text-green-800 font-semibold rounded-lg">✓ ENVIADA AL MH</span>
                            @elseif($factura->estado == 'firmado')
                                <span class="inline-block px-4 py-2 bg-blue-100 text-blue-800 font-semibold rounded-lg">✓ FIRMADA</span>
                            @elseif($factura->estado == 'firmado_sin_enviar')
                                <span class="inline-block px-4 py-2 bg-yellow-100 text-yellow-800 font-semibold rounded-lg">⚠ FIRMADA (Sin enviar)</span>
                            @elseif($factura->estado == 'error_firma')
                                <span class="inline-block px-4 py-2 bg-red-100 text-red-800 font-semibold rounded-lg">✗ ERROR AL FIRMAR</span>
                            @elseif($factura->estado == 'rechazado')
                                <span class="inline-block px-4 py-2 bg-red-100 text-red-800 font-semibold rounded-lg">✗ RECHAZADA</span>
                            @else
                                <span class="inline-block px-4 py-2 bg-gray-100 text-gray-800 font-semibold rounded-lg">⏱ PENDIENTE</span>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Información de la factura -->
                <div class="grid grid-cols-2 gap-6 mb-6">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-600 mb-2">Datos del Documento</h3>
                        <div class="space-y-1">
                            <p class="text-sm">
                                <span class="font-medium">Tipo:</span>
                                @if($factura->tipo_dte == '03')
                                    <span class="inline-block px-2 py-1 bg-green-100 text-green-800 text-xs font-semibold rounded">CCF (DTE-03)</span>
                                @else
                                    <span class="inline-block px-2 py-1 bg-blue-100 text-blue-800 text-xs font-semibold rounded">Factura (DTE-01)</span>
                                @endif
                            </p>
                            <p class="text-sm"><span class="font-medium">Número Control:</span> {{ $factura->numero_control }}</p>
                            <p class="text-sm"><span class="font-medium">Código Generación:</span> {{ $factura->codigo_generacion }}</p>
                            <p class="text-sm"><span class="font-medium">Fecha:</span> {{ $factura->fecha_emision->format('d/m/Y') }}</p>
                            <p class="text-sm"><span class="font-medium">Hora:</span> {{ $factura->hora_emision }}</p>
                            @if($factura->intentos_envio > 0)
                                <p class="text-sm"><span class="font-medium">Intentos de envío:</span>
                                    <span class="inline-block px-2 py-1 text-xs rounded {{ $factura->intentos_envio > 1 ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800' }}">
                                        {{ $factura->intentos_envio }}
                                    </span>
                                </p>
                            @endif
                        </div>
                    </div>

                    <div>
                        <h3 class="text-sm font-semibold text-gray-600 mb-2">Cliente</h3>
                        <div class="space-y-1">
                            <p class="text-sm font-semibold">{{ $factura->receptor_nombre }}</p>
                            <p class="text-sm">{{ $factura->receptor_num_documento }}</p>
                            @if($factura->receptor_nrc)
                                <p class="text-sm">NRC: {{ $factura->receptor_nrc }}</p>
                            @endif
                            @if($factura->receptor_telefono)
                                <p class="text-sm">Tel: {{ $factura->receptor_telefono }}</p>
                            @endif
                            @if($factura->receptor_correo)
                                <p class="text-sm">{{ $factura->receptor_correo }}</p>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Items -->
                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-gray-700 mb-4">Detalle de Productos/Servicios</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600">#</th>
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600">Código</th>
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600">Descripción</th>
                                    <th class="px-4 py-3 text-right text-sm font-semibold text-gray-600">Cant.</th>
                                    <th class="px-4 py-3 text-right text-sm font-semibold text-gray-600">P. Unit.</th>
                                    <th class="px-4 py-3 text-right text-sm font-semibold text-gray-600">Desc.</th>
                                    <th class="px-4 py-3 text-right text-sm font-semibold text-gray-600">Total</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach($factura->items as $item)
                                <tr>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ $item->num_item }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ $item->codigo }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ $item->descripcion }}</td>
                                    <td class="px-4 py-3 text-sm text-right text-gray-700">{{ number_format($item->cantidad, 2) }}</td>
                                    <td class="px-4 py-3 text-sm text-right text-gray-700">${{ number_format($item->precio_unitario, 2) }}</td>
                                    <td class="px-4 py-3 text-sm text-right text-gray-700">${{ number_format($item->monto_descuento, 2) }}</td>
                                    <td class="px-4 py-3 text-sm text-right font-semibold text-gray-700">${{ number_format($item->venta_gravada, 2) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Totales -->
                <div class="border-t pt-6">
                    <div class="flex justify-end">
                        <div class="w-80">
                            <div class="space-y-2">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Subtotal:</span>
                                    <span class="font-semibold">${{ number_format($factura->sub_total, 2) }}</span>
                                </div>
                                @if($factura->total_descuento > 0)
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Descuento:</span>
                                    <span class="font-semibold text-red-600">-${{ number_format($factura->total_descuento, 2) }}</span>
                                </div>
                                @endif
                                <div class="flex justify-between">
                                    <span class="text-gray-600">IVA (13%):</span>
                                    <span class="font-semibold">${{ number_format($factura->total_iva, 2) }}</span>
                                </div>
                                <div class="flex justify-between text-xl font-bold text-blue-600 border-t pt-2">
                                    <span>TOTAL:</span>
                                    <span>${{ number_format($factura->total_pagar, 2) }}</span>
                                </div>
                                <div class="text-sm text-gray-600 italic mt-2">
                                    Son: {{ $factura->total_letras }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Botones de acción -->
                <div class="flex gap-4 mt-8 pt-6 border-t">
                    <button onclick="window.print()" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-6 py-2 rounded-lg">
                        🖨️ Imprimir
                    </button>
                    <button onclick="descargarJSON()" class="bg-green-600 hover:bg-green-700 text-white font-semibold px-6 py-2 rounded-lg">
                        📥 Descargar JSON
                    </button>
                </div>

                <!-- Respuesta del Ministerio (si existe) -->
                @if($factura->respuesta_mh)
                    @php
                        $respuestaMH = json_decode($factura->respuesta_mh, true);
                    @endphp

                    <div class="mt-6">
                        <h4 class="font-semibold text-gray-700 mb-3">Información de Procesamiento:</h4>

                        @if(isset($respuestaMH['error_mh']) || isset($respuestaMH['ultimo_error']))
                            <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-4">
                                <div class="flex items-start">
                                    <svg class="h-6 w-6 text-red-600 mr-3 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                    </svg>
                                    <div class="flex-1">
                                        <h5 class="font-semibold text-red-800 mb-2">Error al enviar al Ministerio de Hacienda</h5>

                                        @if(isset($respuestaMH['ultimo_error']))
                                            <p class="text-sm text-red-700 mb-2">
                                                <span class="font-medium">Motivo:</span> {{ $respuestaMH['ultimo_error'] }}
                                            </p>
                                        @endif

                                        @if(isset($respuestaMH['error_mh']))
                                            <p class="text-sm text-red-700 mb-2">{{ $respuestaMH['error_mh'] }}</p>
                                        @endif

                                        @if(isset($respuestaMH['data_mh']['descripcionMsg']))
                                            <p class="text-sm text-red-700 font-medium">
                                                MH: {{ $respuestaMH['data_mh']['descripcionMsg'] }}
                                            </p>
                                        @endif

                                        @if(isset($respuestaMH['intentos']))
                                            <p class="text-xs text-red-600 mt-2">Intentos realizados: {{ $respuestaMH['intentos'] }}</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endif

                        @if(isset($respuestaMH['envio_mh']['estado']) && $respuestaMH['envio_mh']['estado'] === 'PROCESADO')
                            <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-4">
                                <div class="flex items-start">
                                    <svg class="h-6 w-6 text-green-600 mr-3 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <div class="flex-1">
                                        <h5 class="font-semibold text-green-800 mb-2">DTE Procesado Exitosamente</h5>
                                        @if(isset($respuestaMH['envio_mh']['selloRecibido']))
                                            <p class="text-sm text-green-700"><span class="font-medium">Sello MH:</span> {{ $respuestaMH['envio_mh']['selloRecibido'] }}</p>
                                        @endif
                                        @if(isset($respuestaMH['envio_mh']['fhProcesamiento']))
                                            <p class="text-sm text-green-700"><span class="font-medium">Procesado:</span> {{ $respuestaMH['envio_mh']['fhProcesamiento'] }}</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endif

                        <!-- Detalles técnicos (colapsables) -->
                        <details class="bg-gray-50 rounded-lg p-4">
                            <summary class="cursor-pointer font-semibold text-gray-700 hover:text-gray-900">
                                Ver detalles técnicos completos
                            </summary>
                            <pre class="text-xs text-gray-600 overflow-x-auto mt-3">{{ json_encode($respuestaMH, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                        </details>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <script>
        function descargarJSON() {
            const jsonData = @json($factura->dte_json);
            const dataStr = JSON.stringify(jsonData, null, 2);
            const dataUri = 'data:application/json;charset=utf-8,'+ encodeURIComponent(dataStr);

            const exportFileDefaultName = 'factura_{{ $factura->numero_control }}.json';

            const linkElement = document.createElement('a');
            linkElement.setAttribute('href', dataUri);
            linkElement.setAttribute('download', exportFileDefaultName);
            linkElement.click();
        }

        // Estilos para impresión
        const style = document.createElement('style');
        style.textContent = `
            @media print {
                body * { visibility: hidden; }
                .container, .container * { visibility: visible; }
                .container { position: absolute; left: 0; top: 0; }
                button { display: none !important; }
                a { display: none !important; }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>
