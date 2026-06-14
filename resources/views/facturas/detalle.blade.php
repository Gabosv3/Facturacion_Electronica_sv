<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle Factura - {{ $factura->numero_control }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-gray-100">
    @include('components.navbar')

    <div class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto">
            </div>

            <div class="bg-white rounded-lg shadow-lg p-8">
                <!-- Header -->
                <div class="border-b pb-6 mb-6">
                    <div class="flex justify-between items-start">
                        <div>
                            @if($factura->tipo_dte == '03')
                                <h1 class="text-3xl font-bold text-green-800">COMPROBANTE DE CRÉDITO FISCAL</h1>
                                <p class="text-sm text-green-600 font-semibold mt-1">DTE-03 (CCF)</p>
                            @elseif($factura->tipo_dte == '05')
                                <h1 class="text-3xl font-bold text-purple-800">NOTA DE CRÉDITO</h1>
                                <p class="text-sm text-purple-600 font-semibold mt-1">DTE-05</p>
                            @elseif($factura->tipo_dte == '14')
                                <h1 class="text-3xl font-bold text-orange-800">FACTURA DE SUJETO EXCLUIDO</h1>
                                <p class="text-sm text-orange-600 font-semibold mt-1">DTE-14</p>
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
                                @elseif($factura->tipo_dte == '05')
                                    <span class="inline-block px-2 py-1 bg-purple-100 text-purple-800 text-xs font-semibold rounded">Nota de Crédito (DTE-05)</span>
                                @elseif($factura->tipo_dte == '14')
                                    <span class="inline-block px-2 py-1 bg-orange-100 text-orange-800 text-xs font-semibold rounded">Sujeto Excluido (DTE-14)</span>
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

                <!-- Estado de Nota de Crédito (si aplica) -->
                @if($factura->nota_credito_estado)
                    <div class="mb-6 p-4 border-2 rounded-lg
                        @if($factura->nota_credito_estado == 'anulacion')
                            border-red-300 bg-red-50
                        @elseif($factura->nota_credito_estado == 'ajuste_precio')
                            border-orange-300 bg-orange-50
                        @elseif($factura->nota_credito_estado == 'devolucion')
                            border-blue-300 bg-blue-50
                        @else
                            border-gray-300 bg-gray-50
                        @endif
                    ">
                        <div class="flex items-center gap-3">
                            <svg class="w-6 h-6
                                @if($factura->nota_credito_estado == 'anulacion')
                                    text-red-600
                                @elseif($factura->nota_credito_estado == 'ajuste_precio')
                                    text-orange-600
                                @elseif($factura->nota_credito_estado == 'devolucion')
                                    text-blue-600
                                @else
                                    text-gray-600
                                @endif
                            " fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <div class="flex-1">
                                <p class="font-semibold
                                    @if($factura->nota_credito_estado == 'anulacion')
                                        text-red-800
                                    @elseif($factura->nota_credito_estado == 'ajuste_precio')
                                        text-orange-800
                                    @elseif($factura->nota_credito_estado == 'devolucion')
                                        text-blue-800
                                    @else
                                        text-gray-800
                                    @endif
                                ">
                                    @if($factura->nota_credito_estado == 'anulacion')
                                        Este documento ha sido ANULADO mediante Nota de Crédito
                                    @elseif($factura->nota_credito_estado == 'ajuste_precio')
                                        Este documento tiene un AJUSTE DE PRECIO mediante Nota de Crédito
                                    @elseif($factura->nota_credito_estado == 'devolucion')
                                        Este documento tiene una DEVOLUCIÓN mediante Nota de Crédito
                                    @else
                                        Este documento tiene una Nota de Crédito asociada
                                    @endif
                                </p>
                                @if($factura->nota_credito_id)
                                    @php
                                        $notaCredito = \App\Models\Factura::find($factura->nota_credito_id);
                                    @endphp
                                    @if($notaCredito)
                                        <p class="text-sm mt-1
                                            @if($factura->nota_credito_estado == 'anulacion')
                                                text-red-700
                                            @elseif($factura->nota_credito_estado == 'ajuste_precio')
                                                text-orange-700
                                            @elseif($factura->nota_credito_estado == 'devolucion')
                                                text-blue-700
                                            @else
                                                text-gray-700
                                            @endif
                                        ">
                                            Nota de Crédito: <a href="{{ route('facturas.show', $notaCredito) }}" class="underline font-medium hover:opacity-80">{{ $notaCredito->numero_control }}</a>
                                            <span class="text-xs">({{ $notaCredito->fecha_emision->format('d/m/Y') }})</span>
                                        </p>
                                    @endif
                                @endif
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Documento Relacionado (solo para Nota de Crédito) -->
                @if($factura->tipo_dte == '05' && $factura->documento_relacionado_numero_control)
                    <div class="bg-purple-50 border border-purple-200 rounded-lg p-4 mb-6">
                        <h3 class="text-sm font-semibold text-purple-800 mb-3">📄 Documento Relacionado</h3>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm"><span class="font-medium text-gray-700">Tipo de Documento:</span>
                                    @if($factura->documento_relacionado_tipo_dte == '01')
                                        <span class="inline-block px-2 py-1 bg-blue-100 text-blue-800 text-xs font-semibold rounded ml-1">Factura (DTE-01)</span>
                                    @elseif($factura->documento_relacionado_tipo_dte == '03')
                                        <span class="inline-block px-2 py-1 bg-green-100 text-green-800 text-xs font-semibold rounded ml-1">CCF (DTE-03)</span>
                                    @endif
                                </p>
                                <p class="text-sm mt-1"><span class="font-medium text-gray-700">Número Control:</span> {{ $factura->documento_relacionado_numero_control }}</p>
                            </div>
                            <div>
                                <p class="text-sm"><span class="font-medium text-gray-700">Código Generación:</span> {{ $factura->documento_relacionado_codigo_generacion }}</p>
                                <p class="text-sm mt-1"><span class="font-medium text-gray-700">Fecha Emisión:</span> {{ \Carbon\Carbon::parse($factura->documento_relacionado_fecha_emision)->format('d/m/Y') }}</p>
                            </div>
                        </div>
                        @if($factura->tipo_documento)
                            <div class="mt-3 pt-3 border-t border-purple-200">
                                <p class="text-sm"><span class="font-medium text-gray-700">Motivo:</span>
                                    @if($factura->tipo_documento == '1')
                                        <span class="text-purple-700">Anulación - Documento emitido por error</span>
                                    @elseif($factura->tipo_documento == '2')
                                        <span class="text-purple-700">Ajuste de Precio - Corrección de monto</span>
                                    @elseif($factura->tipo_documento == '3')
                                        <span class="text-purple-700">Devolución - Cliente devuelve productos</span>
                                    @endif
                                </p>
                            </div>
                        @endif
                    </div>
                @endif

                <!-- Información de Invalidación -->
                @php
                    $invalidacionActiva = $factura->getInvalidacionActiva();
                    $tieneInvalidacionPendiente = $factura->tieneInvalidacionPendiente();
                @endphp
                @if($factura->invalidada || $invalidacionActiva || $tieneInvalidacionPendiente)
                    <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
                        <h3 class="text-sm font-semibold text-red-800 mb-3">
                            @if($invalidacionActiva && $invalidacionActiva->estado == 'procesado')
                                ⚠️ Documento Invalidado
                            @elseif($invalidacionActiva && $invalidacionActiva->estado == 'enviado')
                                ⏳ Invalidación en Proceso
                            @elseif($tieneInvalidacionPendiente)
                                📝 Invalidación Pendiente
                            @else
                                ⚠️ Documento Invalidado
                            @endif
                        </h3>

                        @if($invalidacionActiva)
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <p class="text-sm"><span class="font-medium text-gray-700">Código Invalidación:</span> {{ $invalidacionActiva->codigo_invalidacion }}</p>
                                    <p class="text-sm mt-1"><span class="font-medium text-gray-700">Fecha Evento:</span> {{ \Carbon\Carbon::parse($invalidacionActiva->fecha_evento)->format('d/m/Y H:i:s') }}</p>
                                    <p class="text-sm mt-1"><span class="font-medium text-gray-700">Tipo:</span>
                                        @if($invalidacionActiva->tipo_invalidacion == '1')
                                            <span class="text-red-700">Tipo 1 - Errores formales (se emite documento de reemplazo)</span>
                                        @else
                                            <span class="text-red-700">Tipo 2 - Rescisión de operación</span>
                                        @endif
                                    </p>
                                </div>
                                <div>
                                    <p class="text-sm"><span class="font-medium text-gray-700">Estado:</span>
                                        @if($invalidacionActiva->estado == 'procesado')
                                            <span class="px-2 py-1 bg-red-100 text-red-800 text-xs font-semibold rounded-full ml-1">✓ Procesado por MH</span>
                                        @elseif($invalidacionActiva->estado == 'enviado')
                                            <span class="px-2 py-1 bg-yellow-100 text-yellow-800 text-xs font-semibold rounded-full ml-1">⏳ Enviado al MH</span>
                                        @endif
                                    </p>
                                    @if($invalidacionActiva->sello_recepcion)
                                        <p class="text-sm mt-1"><span class="font-medium text-gray-700">Sello MH:</span> {{ substr($invalidacionActiva->sello_recepcion, 0, 30) }}...</p>
                                    @endif
                                    @if($invalidacionActiva->factura_reemplazo_id)
                                        <p class="text-sm mt-1"><span class="font-medium text-gray-700">Documento de Reemplazo:</span>
                                            <a href="{{ route('facturas.show', $invalidacionActiva->factura_reemplazo_id) }}" class="text-blue-600 hover:text-blue-800 underline">
                                                Ver DTE reemplazo
                                            </a>
                                        </p>
                                    @endif
                                </div>
                            </div>
                            <div class="mt-3 pt-3 border-t border-red-200">
                                <p class="text-sm"><span class="font-medium text-gray-700">Motivo:</span> <span class="text-red-700">{{ $invalidacionActiva->motivo_invalidacion }}</span></p>
                            </div>
                            <div class="mt-3 pt-3 border-t border-red-200">
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <p class="text-xs font-medium text-gray-600 mb-1">Responsable (Emisor)</p>
                                        <p class="text-sm">{{ $invalidacionActiva->responsable_nombre }}</p>
                                        <p class="text-xs text-gray-600">{{ $invalidacionActiva->responsable_num_doc }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs font-medium text-gray-600 mb-1">Solicitante</p>
                                        <p class="text-sm">{{ $invalidacionActiva->solicitante_nombre }}</p>
                                        <p class="text-xs text-gray-600">{{ $invalidacionActiva->solicitante_num_doc }}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-3">
                                <a href="{{ route('invalidaciones.show', $invalidacionActiva->id) }}" class="text-sm text-blue-600 hover:text-blue-800 underline">
                                    Ver detalles completos del evento de invalidación →
                                </a>
                            </div>
                        @elseif($tieneInvalidacionPendiente)
                            @php
                                $invalidacionPendiente = $factura->eventosInvalidacion()->whereIn('estado', ['pendiente', 'firmado'])->first();
                            @endphp
                            @if($invalidacionPendiente)
                                <p class="text-sm text-gray-700">Este documento tiene una invalidación en estado: <span class="font-semibold">{{ ucfirst($invalidacionPendiente->estado) }}</span></p>
                                <p class="text-sm text-gray-600 mt-2">Fecha evento: {{ \Carbon\Carbon::parse($invalidacionPendiente->fecha_evento)->format('d/m/Y H:i:s') }}</p>
                            @endif
                        @else
                            <p class="text-sm text-gray-700">Este documento ha sido marcado como invalidado.</p>
                        @endif
                    </div>
                @endif

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
                                    <td class="px-4 py-3 text-sm text-right font-semibold text-gray-700">
                                        @if($factura->tipo_dte === '01' || $factura->tipo_dte === '14')
                                            {{-- DTE-01 y DTE-14: Mostrar total CON IVA (lo que se cobra) --}}
                                            ${{ number_format(($item->cantidad * $item->precio_unitario) - $item->monto_descuento, 2) }}
                                        @else
                                            {{-- DTE-03 y otros: Mostrar venta_gravada (sin IVA) --}}
                                            ${{ number_format($item->venta_gravada, 2) }}
                                        @endif
                                    </td>
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
                                
                                @if($factura->tipo_dte !== '01' && $factura->tipo_dte !== '14')
                                <div class="flex justify-between">
                                    <span class="text-gray-600">IVA
                                        @if($factura->tipo_dte === '03' || $factura->tipo_dte === '05')
                                            (13%)
                                        @else
                                            (13%)
                                        @endif:
                                    </span>
                                    <span class="font-semibold">${{ number_format($factura->total_iva, 2) }}</span>
                                </div>
                                @endif

                                @if($factura->tipo_dte === '14')
                                    @php
                                        $retencionRenta = $factura->dte_json['dteJson']['resumen']['reteRenta'] ?? 0;
                                    @endphp
                                    @if($retencionRenta > 0)
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">(-) Retención Renta (10%):</span>
                                        <span class="font-semibold text-red-600">-${{ number_format($retencionRenta, 2) }}</span>
                                    </div>
                                    @endif
                                @endif

                                @php
                                    $dteResumen = is_array($factura->dte_json) ? ($factura->dte_json['dteJson']['resumen'] ?? []) : (json_decode($factura->dte_json, true)['dteJson']['resumen'] ?? []);
                                    $ivaRete1 = floatval($dteResumen['ivaRete1'] ?? 0);
                                    $montoTotalOperacion = floatval($dteResumen['montoTotalOperacion'] ?? 0);
                                    $totalPagarFinal = isset($dteResumen['totalPagar']) ? floatval($dteResumen['totalPagar']) : $factura->total_pagar;
                                @endphp
                                @if($ivaRete1 > 0)
                                <div class="flex justify-between border-t pt-2">
                                    <span class="text-gray-600">Monto Total Operación:</span>
                                    <span class="font-semibold">${{ number_format($montoTotalOperacion, 2) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-orange-700 font-medium flex items-center gap-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        Retención 1% (Gran Contribuyente):
                                    </span>
                                    <span class="font-semibold text-orange-700">-${{ number_format($ivaRete1, 2) }}</span>
                                </div>
                                @endif

                                <div class="flex justify-between text-xl font-bold text-blue-600 border-t pt-2">
                                    <span>TOTAL{{ $ivaRete1 > 0 ? ' A PAGAR' : '' }}:</span>
                                    <span>${{ number_format($totalPagarFinal, 2) }}</span>
                                </div>
                                <div class="text-sm text-gray-600 italic mt-2">
                                    Son: {{ $factura->total_letras }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Código QR para Consulta Pública -->
                @if($factura->estado == 'enviado_mh')
                    <div class="mt-8 pt-6 border-t">
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
                            <div class="flex flex-col md:flex-row items-center gap-6">
                                <div class="flex-shrink-0">
                                    <img src="{{ $factura->getQrCodeUrl(200) }}"
                                         alt="Código QR de Consulta Pública"
                                         class="border-4 border-white shadow-lg rounded-lg"
                                         width="200"
                                         height="200">
                                </div>
                                <div class="flex-1 text-center md:text-left">
                                    <h3 class="text-lg font-semibold text-blue-800 mb-2">📱 Código QR de Consulta Pública</h3>
                                    <p class="text-sm text-blue-700 mb-3">
                                        Escanee este código QR para verificar la validez de este documento en el portal del Ministerio de Hacienda.
                                    </p>
                                    <div class="bg-white rounded p-3 border border-blue-200">
                                        <p class="text-xs text-gray-600 font-medium mb-1">URL de consulta:</p>
                                        <p class="text-xs text-blue-600 break-all font-mono">{{ $factura->getUrlConsultaPublica() }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Botones de acción -->
                <div class="flex flex-wrap gap-4 mt-8 pt-6 border-t">
                    <a href="{{ route('facturas.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white font-semibold px-6 py-2 rounded-lg transition-colors">
                        ← Volver al Listado
                    </a>
                    <button onclick="window.print()" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-6 py-2 rounded-lg">
                        🖨️ Imprimir
                    </button>
                    <button onclick="descargarJSON()" class="bg-green-600 hover:bg-green-700 text-white font-semibold px-6 py-2 rounded-lg">
                        📥 Descargar JSON
                    </button>
                    <button onclick="toggleJSON()" class="bg-purple-600 hover:bg-purple-700 text-white font-semibold px-6 py-2 rounded-lg">
                        👁️ Ver JSON
                    </button>

                    @if($factura->receptor_correo && $factura->estado == 'enviado_mh')
                        <form action="{{ route('facturas.enviarEmail', $factura) }}" method="POST" class="inline-block" id="form-enviar-email">
                            @csrf
                            <button type="button"
                                    onclick="confirmarEnvioEmail('{{ $factura->receptor_correo }}', '{{ $factura->numero_control }}')"
                                    class="bg-teal-600 hover:bg-teal-700 text-white font-semibold px-6 py-2 rounded-lg transition-colors inline-flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                                Enviar por Email
                            </button>
                        </form>
                    @endif

                    @if($factura->estado === 'enviado_mh')
                        <form action="{{ route('facturas.enviarJSON', $factura) }}" method="POST" class="inline-block">
                            @csrf
                            <button type="submit"
                                    class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-6 py-2 rounded-lg transition-colors inline-flex items-center gap-2"
                                    onclick="return confirm('¿Enviar JSON para revisión interna?')">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2m0 0v-8m0 8l-6-4m6 4l6-4" />
                                </svg>
                                Enviar JSON
                            </button>
                        </form>

                        <!-- Botón para consultar estado en MH -->
                        <form action="{{ route('consultas.buscar') }}" method="POST" class="inline">
                            @csrf
                            <input type="hidden" name="factura_id" value="{{ $factura->id }}">
                            <button type="submit" class="bg-orange-600 hover:bg-orange-700 text-white font-semibold px-6 py-2 rounded-lg transition-colors">
                                🔍 Consultar en MH
                            </button>
                        </form>
                    @endif
                </div>

                <!-- JSON Viewer (oculto por defecto) -->
                <div id="jsonViewer" style="display: none;" class="mt-6 bg-gray-900 text-green-400 p-6 rounded-lg overflow-auto max-h-96 font-mono text-xs">
                    <div class="flex justify-between items-center mb-3 text-white">
                        <h4 class="font-semibold">JSON enviado al Ministerio de Hacienda:</h4>
                        <button onclick="copiarJSON()" class="bg-gray-700 hover:bg-gray-600 px-3 py-1 rounded text-xs">
                            📋 Copiar
                        </button>
                    </div>
                    <pre id="jsonContent">{{ json_encode($factura->dte_json['dteJson'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                </div>

                <!-- Respuesta del Ministerio (si existe) -->
                @if($factura->respuesta_mh)
                    @php
                        // Si respuesta_mh ya es array (por cast), usarlo directamente
                        // Si es string (facturas antiguas), decodificarlo
                        $respuestaMH = is_string($factura->respuesta_mh)
                            ? json_decode($factura->respuesta_mh, true)
                            : $factura->respuesta_mh;
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

        function toggleJSON() {
            const viewer = document.getElementById('jsonViewer');
            if (viewer.style.display === 'none') {
                viewer.style.display = 'block';
            } else {
                viewer.style.display = 'none';
            }
        }

        function copiarJSON() {
            const jsonContent = document.getElementById('jsonContent').textContent;
            navigator.clipboard.writeText(jsonContent).then(() => {
                alert('JSON copiado al portapapeles');
            }).catch(err => {
                console.error('Error al copiar:', err);
            });
        }

        // Estilos para impresión
        const style = document.createElement('style');
        style.textContent = `
            @media print {
                body * { visibility: hidden; }
                .container, .container * { visibility: visible; }
                .container { position: absolute; left: 0; top: 0; }
                button { display: none !important; }
                a[href*="facturas.index"] { display: none !important; }
                form { display: none !important; }
                #jsonViewer { display: none !important; }
                /* Asegurar que el QR se imprima */
                img[alt*="QR"] {
                    visibility: visible !important;
                    display: block !important;
                    -webkit-print-color-adjust: exact;
                    print-color-adjust: exact;
                }
            }
        `;
        document.head.appendChild(style);

        function confirmarEnvioEmail(email, numeroControl) {
            Swal.fire({
                title: '📧 Enviar Factura por Email',
                html: `
                    <div class="text-left space-y-3 mt-4">
                        <div class="bg-blue-50 p-4 rounded-lg border border-blue-200">
                            <p class="text-sm text-gray-700">
                                <span class="font-semibold text-blue-800">Factura:</span><br>
                                <span class="text-lg font-bold text-gray-900">${numeroControl}</span>
                            </p>
                        </div>
                        <div class="bg-green-50 p-4 rounded-lg border border-green-200">
                            <p class="text-sm text-gray-700">
                                <span class="font-semibold text-green-800">Destinatario:</span><br>
                                <span class="font-mono text-gray-900">${email}</span>
                            </p>
                        </div>
                        <div class="bg-amber-50 p-4 rounded-lg border border-amber-200">
                            <p class="text-sm text-gray-700">
                                <span class="font-semibold text-amber-800">Se enviarán los siguientes archivos:</span>
                            </p>
                            <ul class="list-disc list-inside text-sm text-gray-600 mt-2 ml-2">
                                <li>📄 Archivo JSON con los datos del DTE</li>
                                <li>📑 Factura en formato HTML (imprimible como PDF)</li>
                            </ul>
                        </div>
                    </div>
                `,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#0d9488',
                cancelButtonColor: '#6b7280',
                confirmButtonText: '<i class="mr-2">✉️</i> Sí, enviar ahora',
                cancelButtonText: 'Cancelar',
                width: '600px',
                customClass: {
                    confirmButton: 'px-6 py-3 text-white font-semibold rounded-lg shadow-lg',
                    cancelButton: 'px-6 py-3 text-white font-semibold rounded-lg'
                },
                showLoaderOnConfirm: true,
                preConfirm: () => {
                    return new Promise((resolve) => {
                        // Mostrar loading
                        Swal.fire({
                            title: 'Enviando email...',
                            html: `
                                <div class="text-center">
                                    <div class="animate-spin rounded-full h-16 w-16 border-b-2 border-teal-600 mx-auto mb-4"></div>
                                    <p class="text-gray-600">Por favor espere mientras se envía el correo electrónico</p>
                                </div>
                            `,
                            allowOutsideClick: false,
                            allowEscapeKey: false,
                            showConfirmButton: false,
                            didOpen: () => {
                                // Enviar el formulario
                                document.getElementById('form-enviar-email').submit();
                            }
                        });
                    });
                }
            });
        }

        // Mostrar alertas de SweetAlert para los mensajes de sesión
        @if(session('success'))
            Swal.fire({
                icon: 'success',
                title: '¡Éxito!',
                html: `
                    <div class="text-center">
                        <div class="text-lg font-semibold text-gray-800 mb-2">
                            {{ session('success') }}
                        </div>
                        <div class="mt-4 p-3 bg-green-50 rounded-lg border border-green-200">
                            <p class="text-sm text-green-800">
                                ✅ El correo ha sido enviado exitosamente
                            </p>
                        </div>
                    </div>
                `,
                confirmButtonColor: '#10b981',
                confirmButtonText: 'Entendido',
                timer: 5000,
                timerProgressBar: true,
            });
        @endif

        @if(session('error'))
            Swal.fire({
                icon: 'error',
                title: 'Error al enviar',
                html: `
                    <div class="text-center">
                        <div class="text-lg text-gray-800 mb-4">
                            {{ session('error') }}
                        </div>
                        <div class="mt-4 p-3 bg-red-50 rounded-lg border border-red-200 text-left">
                            <p class="text-sm text-red-800 font-semibold mb-2">Posibles soluciones:</p>
                            <ul class="text-sm text-red-700 list-disc list-inside space-y-1">
                                <li>Verifique la configuración de correo en .env</li>
                                <li>Asegúrese de que la factura tenga un email válido</li>
                                <li>Revise los logs en storage/logs/laravel.log</li>
                            </ul>
                        </div>
                    </div>
                `,
                confirmButtonColor: '#dc2626',
                confirmButtonText: 'Entendido',
            });
        @endif

        @if(session('warning'))
            Swal.fire({
                icon: 'warning',
                title: 'Advertencia',
                text: '{{ session('warning') }}',
                confirmButtonColor: '#f59e0b',
                confirmButtonText: 'Entendido',
            });
        @endif
    </script>
</body>
</html>
