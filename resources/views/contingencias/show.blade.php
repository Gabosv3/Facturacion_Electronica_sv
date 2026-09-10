<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contingencia #{{ $evento->id }} - DTE El Salvador</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50">
    @include('components.navbar')

    <div class="container mx-auto px-4 py-8">
        <div class="max-w-7xl mx-auto">
            <!-- Header -->
            <div class="bg-white rounded-xl shadow-[0_1px_3px_0_rgb(0_0_0/0.06),0_4px_12px_-2px_rgb(0_0_0/0.06)] border border-slate-100 p-6 mb-6">
                <div class="flex justify-between items-start">
                    <div>
                        <h1 class="text-3xl font-bold text-slate-800">Evento de Contingencia #{{ $evento->id }}</h1>
                        <p class="text-slate-500 mt-2">{{ $evento->tipo_contingencia_descripcion }}</p>
                    </div>
                    <a href="{{ route('contingencias.index') }}" class="bg-gray-600 hover:bg-gray-700 text-white font-semibold px-6 py-3 rounded-lg">
                        ← Volver
                    </a>
                </div>
            </div>

            <!-- Mensajes -->
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

            @if(session('info'))
            <div class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded mb-4">
                {{ session('info') }}
            </div>
            @endif

            @if(session('warning'))
            <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded mb-4">
                {{ session('warning') }}
            </div>
            @endif

            <!-- Información del Evento -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <!-- Detalles Generales -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-xl font-bold text-slate-800 mb-4">📋 Detalles del Evento</h2>
                    <div class="space-y-3">
                        <div class="flex justify-between">
                            <span class="text-slate-500">Código del Evento:</span>
                            <span class="font-mono text-sm">{{ $evento->codigo_evento }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">Tipo de Contingencia:</span>
                            <span class="font-semibold">{{ $evento->tipo_contingencia }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">Estado:</span>
                            <span>
                                @if($evento->estado == 'activo')
                                    <span class="px-2 py-1 bg-yellow-100 text-yellow-800 text-xs font-semibold rounded">🚨 ACTIVO</span>
                                @elseif($evento->estado == 'cerrado')
                                    <span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs font-semibold rounded">🔒 CERRADO</span>
                                @elseif($evento->estado == 'enviado')
                                    <span class="px-2 py-1 bg-purple-100 text-purple-800 text-xs font-semibold rounded">📤 ENVIADO</span>
                                @elseif($evento->estado == 'procesado')
                                    <span class="px-2 py-1 bg-green-100 text-green-800 text-xs font-semibold rounded">✅ PROCESADO</span>
                                @endif
                            </span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">Fecha/Hora Inicio:</span>
                            <span class="font-semibold">{{ $evento->fecha_hora_inicio->format('d/m/Y H:i:s') }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">Fecha/Hora Fin:</span>
                            <span class="font-semibold">
                                @if($evento->fecha_hora_fin)
                                    {{ $evento->fecha_hora_fin->format('d/m/Y H:i:s') }}
                                @else
                                    <span class="text-yellow-600">En curso</span>
                                @endif
                            </span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">Cantidad de DTEs:</span>
                            <span class="font-bold text-2xl text-blue-600">
                                {{ $evento->facturas->count() }}
                                @if($evento->estado == 'activo')
                                    <span class="text-sm text-slate-500 font-normal">(en curso)</span>
                                @endif
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Motivo y Respuesta MH -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-xl font-bold text-slate-800 mb-4">📝 Motivo de Contingencia</h2>
                    <p class="text-slate-700">{{ $evento->motivo_contingencia }}</p>

                    @if($evento->respuesta_mh)
                    <div class="mt-6 pt-6 border-t">
                        <h3 class="text-lg font-semibold text-slate-800 mb-2">📨 Respuesta del MH</h3>
                        @php
                            $respuestaMH = is_string($evento->respuesta_mh) ? json_decode($evento->respuesta_mh, true) : $evento->respuesta_mh;
                        @endphp

                        @if($respuestaMH)
                            <div class="space-y-2 text-sm">
                                @if(isset($respuestaMH['estado']))
                                <div class="flex justify-between">
                                    <span class="text-slate-500">Estado:</span>
                                    <span class="font-semibold">{{ $respuestaMH['estado'] }}</span>
                                </div>
                                @endif

                                @if(isset($respuestaMH['selloRecibido']))
                                <div class="flex flex-col">
                                    <span class="text-slate-500 mb-1">Sello Recibido:</span>
                                    <span class="font-mono text-xs bg-slate-100 p-2 rounded break-all">{{ $respuestaMH['selloRecibido'] }}</span>
                                </div>
                                @endif

                                @if(isset($respuestaMH['fhProcesamiento']))
                                <div class="flex justify-between">
                                    <span class="text-slate-500">Fecha/Hora Procesamiento:</span>
                                    <span class="font-semibold">{{ $respuestaMH['fhProcesamiento'] }}</span>
                                </div>
                                @endif

                                @if(isset($respuestaMH['clasificaMsg']))
                                <div class="flex justify-between">
                                    <span class="text-slate-500">Clasificación:</span>
                                    <span class="font-semibold">{{ $respuestaMH['clasificaMsg'] }}</span>
                                </div>
                                @endif

                                @if(isset($respuestaMH['codigoMsg']))
                                <div class="flex justify-between">
                                    <span class="text-slate-500">Código Mensaje:</span>
                                    <span class="font-semibold">{{ $respuestaMH['codigoMsg'] }}</span>
                                </div>
                                @endif

                                @if(isset($respuestaMH['descripcionMsg']))
                                <div class="flex flex-col">
                                    <span class="text-slate-500 mb-1">Descripción:</span>
                                    <span class="bg-green-50 p-2 rounded">{{ $respuestaMH['descripcionMsg'] }}</span>
                                </div>
                                @endif

                                @if(isset($respuestaMH['observaciones']) && is_array($respuestaMH['observaciones']) && count($respuestaMH['observaciones']) > 0)
                                <div class="flex flex-col">
                                    <span class="text-slate-500 mb-1">Observaciones:</span>
                                    <ul class="list-disc list-inside bg-yellow-50 p-2 rounded">
                                        @foreach($respuestaMH['observaciones'] as $obs)
                                        <li>{{ $obs }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                                @endif
                            </div>
                        @endif
                    </div>
                    @endif

                    @if($evento->codigo_lote)
                    <div class="mt-6 pt-6 border-t">
                        <h3 class="text-lg font-semibold text-slate-800 mb-2">📦 Código de Lote</h3>
                        <p class="font-mono text-sm bg-slate-100 p-3 rounded">{{ $evento->codigo_lote }}</p>
                    </div>
                    @endif

                    @if($evento->estado == 'procesado' && $evento->evento_json)
                    <div class="mt-6 pt-6 border-t">
                        <h3 class="text-lg font-semibold text-slate-800 mb-2">📋 JSON del Evento Enviado al MH</h3>
                        <details class="bg-slate-50 rounded-lg p-4">
                            <summary class="cursor-pointer font-semibold text-slate-700 hover:text-gray-900">
                                Ver JSON del evento de contingencia
                            </summary>
                            <pre class="text-xs text-slate-500 overflow-x-auto mt-3 bg-white p-3 rounded border">{{ json_encode($evento->evento_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                        </details>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Acciones -->
            @if($evento->estado != 'procesado')
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <h2 class="text-xl font-bold text-slate-800 mb-4">⚡ Acciones Disponibles</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    @if($evento->estado == 'activo')
                        <form action="{{ route('contingencias.cerrar', $evento) }}" method="POST">
                            @csrf
                            <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold px-6 py-4 rounded-lg" onclick="return confirm('¿Estás seguro de cerrar esta contingencia?')">
                                🔒 Cerrar Contingencia
                            </button>
                        </form>
                        <div class="col-span-2 bg-yellow-50 border border-yellow-300 rounded-lg p-4">
                            <p class="text-sm text-yellow-800"><strong>Nota:</strong> Cierra la contingencia cuando se restablezca la conexión con el MH.</p>
                        </div>
                    @elseif($evento->estado == 'cerrado')
                        <form action="{{ route('contingencias.procesar', $evento) }}" method="POST">
                            @csrf
                            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold px-6 py-4 rounded-lg">
                                📤 Enviar al MH
                            </button>
                        </form>
                        <div class="col-span-2 bg-blue-50 border border-blue-300 rounded-lg p-4">
                            <p class="text-sm text-blue-800"><strong>Enviar al MH:</strong> Esto enviará el evento de contingencia y el lote de DTEs al Ministerio de Hacienda.</p>
                        </div>
                    @elseif($evento->estado == 'enviado' && $evento->codigo_lote)
                        <form action="{{ route('contingencias.consultar-lote', $evento) }}" method="POST">
                            @csrf
                            <button type="submit" class="w-full bg-purple-600 hover:bg-purple-700 text-white font-semibold px-6 py-4 rounded-lg">
                                🔍 Consultar Estado
                            </button>
                        </form>
                        <div class="col-span-2 bg-purple-50 border border-purple-300 rounded-lg p-4">
                            <p class="text-sm text-purple-800"><strong>Consultar estado:</strong> El MH procesa lotes de 100 DTEs en 2-3 minutos. Consulta periódicamente hasta que esté "PROCESADO".</p>
                        </div>
                    @endif
                </div>
            </div>
            @endif

            <!-- Lista de DTEs -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-bold text-slate-800 mb-4">📄 DTEs en Contingencia ({{ $evento->facturas->count() }})</h2>

                @if($evento->facturas->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-slate-500">Tipo</th>
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-slate-500">Número Control</th>
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-slate-500">Código Generación</th>
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-slate-500">Fecha</th>
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-slate-500">Cliente</th>
                                    <th class="px-4 py-3 text-right text-sm font-semibold text-slate-500">Total</th>
                                    <th class="px-4 py-3 text-center text-sm font-semibold text-slate-500">Estado</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach($evento->facturas as $factura)
                                <tr class="hover:bg-slate-50">
                                    <td class="px-4 py-3 text-sm">
                                        @if($factura->tipo_dte == '01')
                                            <span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs font-semibold rounded">DTE-01</span>
                                        @elseif($factura->tipo_dte == '03')
                                            <span class="px-2 py-1 bg-green-100 text-green-800 text-xs font-semibold rounded">CCF-03</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-sm font-mono">
                                        <a href="{{ route('facturas.show', $factura) }}" class="text-blue-600 hover:underline">
                                            {{ $factura->numero_control }}
                                        </a>
                                    </td>
                                    <td class="px-4 py-3 text-sm font-mono text-xs">{{ substr($factura->codigo_generacion, 0, 20) }}...</td>
                                    <td class="px-4 py-3 text-sm">{{ \Carbon\Carbon::parse($factura->fecha_emision)->format('d/m/Y') }}</td>
                                    <td class="px-4 py-3 text-sm">{{ $factura->receptor_nombre }}</td>
                                    <td class="px-4 py-3 text-sm text-right font-semibold">${{ number_format($factura->total_pagar, 2) }}</td>
                                    <td class="px-4 py-3 text-center text-sm">
                                        @if($factura->estado == 'firmado_contingencia')
                                            <span class="px-2 py-1 bg-yellow-100 text-yellow-800 text-xs font-semibold rounded">⏳ Contingencia</span>
                                        @elseif($factura->estado == 'enviado_mh')
                                            <span class="px-2 py-1 bg-green-100 text-green-800 text-xs font-semibold rounded">✅ Enviado</span>
                                        @elseif($factura->estado_lote == 'PROCESADO')
                                            <span class="px-2 py-1 bg-green-100 text-green-800 text-xs font-semibold rounded">✅ Procesado</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-8">
                        <p class="text-slate-500">No hay DTEs asociados a este evento de contingencia</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</body>
</html>
