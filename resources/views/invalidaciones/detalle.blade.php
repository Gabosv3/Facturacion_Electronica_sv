<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle Evento de Invalidación - DTE El Salvador</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    @include('components.navbar')

    <div class="container mx-auto px-4 py-8">
        <div class="max-w-5xl mx-auto">
            <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
                <div class="flex justify-between items-start mb-6">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-800">Evento de Invalidación</h1>
                        <p class="text-gray-600 mt-1 font-mono text-sm">{{ $evento->codigo_invalidacion }}</p>
                    </div>
                    @php
                        $estadoClasses = [
                            'pendiente' => 'bg-yellow-100 text-yellow-800',
                            'firmado' => 'bg-blue-100 text-blue-800',
                            'enviado' => 'bg-purple-100 text-purple-800',
                            'procesado' => 'bg-green-100 text-green-800',
                            'error' => 'bg-red-100 text-red-800',
                        ];
                    @endphp
                    <span class="px-4 py-2 text-lg font-semibold rounded {{ $estadoClasses[$evento->estado] ?? 'bg-gray-100 text-gray-800' }}">
                        {{ ucfirst($evento->estado) }}
                    </span>
                </div>

                @if(session('success'))
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                        {{ session('success') }}
                    </div>
                @endif

                @if(session('error'))
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                        {{ session('error') }}
                    </div>
                @endif

                <!-- Información del Evento -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div class="bg-gray-50 rounded-lg p-4">
                        <h2 class="text-lg font-semibold text-gray-900 mb-3">Información del Evento</h2>
                        <dl class="space-y-2">
                            <div>
                                <dt class="text-sm text-gray-600">Fecha del Evento:</dt>
                                <dd class="font-semibold">{{ $evento->fecha_evento->format('d/m/Y H:i:s') }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm text-gray-600">Tipo de Invalidación:</dt>
                                <dd class="font-semibold">
                                    {{ $evento->tipo_invalidacion == '1' ? 'Anulación por errores formales' : 'Rescindir operación' }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm text-gray-600">Ambiente:</dt>
                                <dd class="font-semibold">{{ $evento->ambiente == '00' ? 'Pruebas' : 'Producción' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm text-gray-600">Versión:</dt>
                                <dd class="font-semibold">{{ $evento->version }}</dd>
                            </div>
                        </dl>
                    </div>

                    <div class="bg-gray-50 rounded-lg p-4">
                        <h2 class="text-lg font-semibold text-gray-900 mb-3">Procesamiento</h2>
                        <dl class="space-y-2">
                            <div>
                                <dt class="text-sm text-gray-600">Creado:</dt>
                                <dd class="font-semibold">{{ $evento->created_at->format('d/m/Y H:i:s') }}</dd>
                            </div>
                            @if($evento->fecha_procesamiento)
                                <div>
                                    <dt class="text-sm text-gray-600">Procesado:</dt>
                                    <dd class="font-semibold">{{ $evento->fecha_procesamiento->format('d/m/Y H:i:s') }}</dd>
                                </div>
                            @endif
                            @if($evento->sello_recepcion)
                                <div>
                                    <dt class="text-sm text-gray-600">Sello de Recepción:</dt>
                                    <dd class="font-mono text-xs">{{ substr($evento->sello_recepcion, 0, 40) }}...</dd>
                                </div>
                            @endif
                        </dl>
                    </div>
                </div>

                <!-- Factura Invalidada -->
                <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
                    <h2 class="text-lg font-semibold text-red-900 mb-3">Factura Invalidada</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <span class="text-sm text-gray-600">Número de Control:</span>
                            <p class="font-mono font-semibold">{{ $evento->factura->numero_control }}</p>
                        </div>
                        <div>
                            <span class="text-sm text-gray-600">Tipo DTE:</span>
                            <p class="font-semibold">DTE-{{ $evento->factura->tipo_dte }}</p>
                        </div>
                        <div>
                            <span class="text-sm text-gray-600">Receptor:</span>
                            <p class="font-semibold">{{ $evento->factura->receptor_nombre }}</p>
                        </div>
                        <div>
                            <span class="text-sm text-gray-600">Total:</span>
                            <p class="font-semibold">${{ number_format($evento->factura->total_pagar, 2) }}</p>
                        </div>
                        <div class="md:col-span-2">
                            <a href="{{ route('facturas.show', $evento->factura->id) }}"
                               class="text-blue-600 hover:text-blue-900 text-sm font-semibold">
                                Ver detalles de la factura →
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Factura de Reemplazo (si existe) -->
                @if($evento->facturaReemplazo)
                    <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
                        <h2 class="text-lg font-semibold text-green-900 mb-3">Factura de Reemplazo</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <span class="text-sm text-gray-600">Número de Control:</span>
                                <p class="font-mono font-semibold">{{ $evento->facturaReemplazo->numero_control }}</p>
                            </div>
                            <div>
                                <span class="text-sm text-gray-600">Código de Generación:</span>
                                <p class="font-mono text-xs">{{ $evento->codigo_generacion_reemplazo }}</p>
                            </div>
                            <div class="md:col-span-2">
                                <a href="{{ route('facturas.show', $evento->facturaReemplazo->id) }}"
                                   class="text-blue-600 hover:text-blue-900 text-sm font-semibold">
                                    Ver detalles de la factura de reemplazo →
                                </a>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Motivo -->
                <div class="mb-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-2">Motivo de Invalidación</h2>
                    <div class="bg-gray-50 rounded-lg p-4">
                        <p class="text-gray-800">{{ $evento->motivo_invalidacion }}</p>
                    </div>
                </div>

                <!-- Responsables -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div class="bg-gray-50 rounded-lg p-4">
                        <h2 class="text-lg font-semibold text-gray-900 mb-3">Responsable (Emisor)</h2>
                        <dl class="space-y-2">
                            <div>
                                <dt class="text-sm text-gray-600">Nombre:</dt>
                                <dd class="font-semibold">{{ $evento->responsable_nombre }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm text-gray-600">Documento:</dt>
                                <dd class="font-semibold">{{ $evento->responsable_tipo_doc }}: {{ $evento->responsable_num_doc }}</dd>
                            </div>
                        </dl>
                    </div>

                    <div class="bg-gray-50 rounded-lg p-4">
                        <h2 class="text-lg font-semibold text-gray-900 mb-3">Solicitante</h2>
                        <dl class="space-y-2">
                            <div>
                                <dt class="text-sm text-gray-600">Nombre:</dt>
                                <dd class="font-semibold">{{ $evento->solicitante_nombre }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm text-gray-600">Documento:</dt>
                                <dd class="font-semibold">{{ $evento->solicitante_tipo_doc }}: {{ $evento->solicitante_num_doc }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>

                <!-- Respuesta del MH -->
                @if($evento->respuesta_mh)
                    @php
                        $respuestaMH = is_string($evento->respuesta_mh)
                            ? json_decode($evento->respuesta_mh, true)
                            : $evento->respuesta_mh;
                    @endphp

                    <div class="mb-6">
                        <h2 class="text-lg font-semibold text-gray-900 mb-3">Respuesta del Ministerio de Hacienda</h2>

                        @if(isset($respuestaMH['estado']) && $respuestaMH['estado'] === 'RECHAZADO')
                            <!-- Error del MH -->
                            <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-4">
                                <div class="flex items-start">
                                    <svg class="h-6 w-6 text-red-600 mr-3 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                    </svg>
                                    <div class="flex-1">
                                        <h5 class="font-semibold text-red-800 mb-2">Error al enviar al Ministerio de Hacienda</h5>

                                        <p class="text-sm text-red-700 mb-3">
                                            <span class="font-medium">Motivo:</span> Error al enviar evento de invalidación: {{ json_encode($respuestaMH, JSON_UNESCAPED_UNICODE) }}
                                        </p>

                                        @if(isset($respuestaMH['descripcionMsg']))
                                            <p class="text-sm text-red-700 mb-2">{{ $respuestaMH['descripcionMsg'] }}</p>
                                        @endif

                                        @if(isset($respuestaMH['observaciones']) && is_array($respuestaMH['observaciones']) && count($respuestaMH['observaciones']) > 0)
                                            <div class="mt-3 space-y-1">
                                                <p class="text-sm font-medium text-red-800">Observaciones:</p>
                                                @foreach($respuestaMH['observaciones'] as $observacion)
                                                    <p class="text-sm text-red-700">• {{ $observacion }}</p>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @elseif(isset($respuestaMH['estado']) && $respuestaMH['estado'] === 'PROCESADO')
                            <!-- Éxito -->
                            <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-4">
                                <div class="flex items-start">
                                    <svg class="h-6 w-6 text-green-600 mr-3 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <div class="flex-1">
                                        <h5 class="font-semibold text-green-800 mb-2">Evento de Invalidación Procesado Exitosamente</h5>

                                        @if(isset($respuestaMH['selloRecibido']))
                                            <p class="text-sm text-green-700"><span class="font-medium">Sello MH:</span> {{ $respuestaMH['selloRecibido'] }}</p>
                                        @endif

                                        @if(isset($respuestaMH['fhProcesamiento']))
                                            <p class="text-sm text-green-700"><span class="font-medium">Procesado:</span> {{ $respuestaMH['fhProcesamiento'] }}</p>
                                        @endif

                                        @if(isset($respuestaMH['descripcionMsg']))
                                            <p class="text-sm text-green-700"><span class="font-medium">Mensaje:</span> {{ $respuestaMH['descripcionMsg'] }}</p>
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

                <!-- JSON del Evento -->
                @if($evento->evento_json)
                    <div class="mb-6">
                        <h2 class="text-lg font-semibold text-gray-900 mb-2">JSON del Evento</h2>
                        <div class="bg-gray-50 rounded-lg p-4">
                            <pre class="text-xs overflow-x-auto">{{ json_encode($evento->evento_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                        </div>
                    </div>
                @endif

                <!-- Botones de Acción -->
                <div class="flex justify-between items-center mt-6 pt-6 border-t">
                    <a href="{{ route('invalidaciones.index') }}"
                       class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg transition">
                        Volver a Lista
                    </a>

                    @if($evento->puedeEnviarse())
                        <form action="{{ route('invalidaciones.reintentar', $evento->id) }}" method="POST">
                            @csrf
                            <button type="submit"
                                    class="bg-orange-600 hover:bg-orange-700 text-white px-6 py-2 rounded-lg transition"
                                    onclick="return confirm('¿Reintentar el envío de este evento de invalidación?')">
                                Reintentar Envío
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</body>
</html>
