<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resultado Consulta DTE</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50">
    @include('components.navbar')

    <div class="container mx-auto px-4 py-8">
        <div class="max-w-5xl mx-auto">
            <!-- Header -->
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-slate-800">Resultado de Consulta DTE</h1>
                    <p class="text-slate-500 mt-1">Estado del documento en el Ministerio de Hacienda</p>
                </div>
                <a href="{{ route('consultas.index') }}"
                   class="bg-slate-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg transition-colors">
                    ← Volver a Consultas
                </a>
            </div>

            <!-- Parámetros de búsqueda -->
            <div class="bg-white rounded-xl shadow-[0_1px_3px_0_rgb(0_0_0/0.06),0_4px_12px_-2px_rgb(0_0_0/0.06)] border border-slate-100 p-6 mb-6">
                <h2 class="text-xl font-bold text-slate-800 mb-4">Parámetros de Consulta</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <p class="text-sm font-medium text-slate-500">NIT Emisor</p>
                        <p class="text-lg font-semibold text-slate-800">{{ $parametros['nit_emisor'] }}</p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-slate-500">Tipo DTE</p>
                        <p class="text-lg font-semibold text-slate-800">
                            @if($parametros['tipo_dte'] == '01')
                                <span class="px-2 py-1 bg-blue-100 text-blue-800 text-sm rounded">DTE-01 (Factura)</span>
                            @elseif($parametros['tipo_dte'] == '03')
                                <span class="px-2 py-1 bg-green-100 text-green-800 text-sm rounded">DTE-03 (CCF)</span>
                            @elseif($parametros['tipo_dte'] == '05')
                                <span class="px-2 py-1 bg-purple-100 text-purple-800 text-sm rounded">DTE-05 (Nota Crédito)</span>
                            @else
                                <span class="px-2 py-1 bg-slate-100 text-slate-800 text-sm rounded">DTE-{{ $parametros['tipo_dte'] }}</span>
                            @endif
                        </p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-slate-500">Código de Generación</p>
                        <p class="text-sm font-mono text-slate-800 break-all">{{ $parametros['codigo_generacion'] }}</p>
                    </div>
                </div>

                @if(isset($factura))
                    <div class="mt-4 pt-4 border-t border-slate-200">
                        <p class="text-sm font-medium text-slate-500">Factura Local</p>
                        <p class="text-lg font-semibold text-slate-800">{{ $factura->numero_control }}</p>
                        <a href="{{ route('facturas.show', $factura) }}"
                           class="text-blue-600 hover:text-blue-800 text-sm">
                            Ver detalle completo →
                        </a>
                    </div>
                @endif
            </div>

            <!-- Resultado -->
            <div class="bg-white rounded-xl shadow-[0_1px_3px_0_rgb(0_0_0/0.06),0_4px_12px_-2px_rgb(0_0_0/0.06)] border border-slate-100 p-6">
                <h2 class="text-xl font-bold text-slate-800 mb-4">Respuesta del Ministerio de Hacienda</h2>

                @if(isset($resultado['data']['estado']))
                    <!-- Estado -->
                    <div class="mb-6">
                        <p class="text-sm font-medium text-slate-500 mb-2">Estado del Documento</p>
                        @php
                            $estado = $resultado['data']['estado'] ?? 'DESCONOCIDO';
                            $estadoClass = match(strtoupper($estado)) {
                                'PROCESADO', 'APROBADO', 'RECIBIDO' => 'bg-green-100 text-green-800',
                                'RECHAZADO' => 'bg-red-100 text-red-800',
                                'PENDIENTE' => 'bg-yellow-100 text-yellow-800',
                                default => 'bg-slate-100 text-slate-800',
                            };
                        @endphp
                        <span class="px-4 py-2 {{ $estadoClass }} text-lg font-bold rounded-lg inline-block">
                            {{ $estado }}
                        </span>
                    </div>
                @endif

                <!-- Datos completos de la respuesta -->
                <div class="space-y-4">
                    @foreach($resultado['data'] as $key => $value)
                        <div class="border-b border-slate-200 pb-3">
                            <p class="text-sm font-medium text-slate-500 mb-1">{{ ucfirst(str_replace('_', ' ', $key)) }}</p>

                            @if(is_array($value) || is_object($value))
                                <pre class="bg-slate-50 p-3 rounded text-xs overflow-x-auto">{{ json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                            @else
                                <p class="text-slate-800">{{ $value }}</p>
                            @endif
                        </div>
                    @endforeach
                </div>

                <!-- JSON Completo (Colapsable) -->
                <div class="mt-6">
                    <button onclick="toggleJson()"
                            class="bg-slate-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline-block mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
                        </svg>
                        Ver JSON Completo
                    </button>

                    <div id="json-completo" class="hidden mt-4">
                        <pre class="bg-gray-900 text-green-400 p-4 rounded-lg text-xs overflow-x-auto">{{ json_encode($resultado['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                    </div>
                </div>
            </div>

            <!-- Acciones -->
            <div class="mt-6 flex gap-3">
                <a href="{{ route('consultas.index') }}"
                   class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-semibold transition-colors">
                    Nueva Consulta
                </a>

                @if(isset($factura))
                    <a href="{{ route('facturas.show', $factura) }}"
                       class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-3 rounded-lg font-semibold transition-colors">
                        Ver Factura Local
                    </a>
                @endif

                <a href="{{ route('facturas.index') }}"
                   class="bg-slate-500 hover:bg-gray-600 text-white px-6 py-3 rounded-lg font-semibold transition-colors">
                    Listado de Facturas
                </a>
            </div>
        </div>
    </div>

    <script>
        function toggleJson() {
            const jsonElement = document.getElementById('json-completo');
            jsonElement.classList.toggle('hidden');
        }
    </script>
</body>
</html>
