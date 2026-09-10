<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resultado Consulta Lote DTE</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50">
    @include('components.navbar')

    <div class="container mx-auto px-4 py-8">
        <div class="max-w-6xl mx-auto">
            <!-- Header -->
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-slate-800">Resultado de Consulta Lote DTE</h1>
                    <p class="text-slate-500 mt-1">Estado del lote en el Ministerio de Hacienda</p>
                </div>
                <a href="{{ route('consultas.index') }}"
                   class="bg-slate-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg transition-colors">
                    ← Volver a Consultas
                </a>
            </div>

            <!-- Código de Lote -->
            <div class="bg-white rounded-xl shadow-[0_1px_3px_0_rgb(0_0_0/0.06),0_4px_12px_-2px_rgb(0_0_0/0.06)] border border-slate-100 p-6 mb-6">
                <h2 class="text-xl font-bold text-slate-800 mb-4">Información del Lote</h2>
                <div>
                    <p class="text-sm font-medium text-slate-500">Código de Lote</p>
                    <p class="text-2xl font-bold text-slate-800 font-mono">{{ $codigo_lote }}</p>
                </div>
            </div>

            <!-- Resultado -->
            <div class="bg-white rounded-xl shadow-[0_1px_3px_0_rgb(0_0_0/0.06),0_4px_12px_-2px_rgb(0_0_0/0.06)] border border-slate-100 p-6">
                <h2 class="text-xl font-bold text-slate-800 mb-4">Respuesta del Ministerio de Hacienda</h2>

                @if(isset($resultado['data']['estado']))
                    <!-- Estado del Lote -->
                    <div class="mb-6">
                        <p class="text-sm font-medium text-slate-500 mb-2">Estado del Lote</p>
                        @php
                            $estado = $resultado['data']['estado'] ?? 'DESCONOCIDO';
                            $estadoClass = match(strtoupper($estado)) {
                                'PROCESADO', 'FINALIZADO', 'COMPLETADO' => 'bg-green-100 text-green-800',
                                'RECHAZADO', 'FALLIDO' => 'bg-red-100 text-red-800',
                                'PROCESANDO', 'EN_PROCESO' => 'bg-yellow-100 text-yellow-800',
                                default => 'bg-slate-100 text-slate-800',
                            };
                        @endphp
                        <span class="px-4 py-2 {{ $estadoClass }} text-lg font-bold rounded-lg inline-block">
                            {{ $estado }}
                        </span>
                    </div>
                @endif

                <!-- Detalle de DTEs en el lote -->
                @if(isset($resultado['data']['listado']) && is_array($resultado['data']['listado']))
                    <div class="mb-6">
                        <h3 class="text-lg font-bold text-slate-800 mb-3">DTEs en el Lote ({{ count($resultado['data']['listado']) }})</h3>
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-slate-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-sm font-semibold text-slate-500">#</th>
                                        <th class="px-4 py-3 text-left text-sm font-semibold text-slate-500">Código Generación</th>
                                        <th class="px-4 py-3 text-left text-sm font-semibold text-slate-500">Tipo DTE</th>
                                        <th class="px-4 py-3 text-center text-sm font-semibold text-slate-500">Estado</th>
                                        <th class="px-4 py-3 text-left text-sm font-semibold text-slate-500">Mensaje</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @foreach($resultado['data']['listado'] as $index => $dte)
                                        <tr class="hover:bg-slate-50">
                                            <td class="px-4 py-3 text-sm text-slate-700">{{ $index + 1 }}</td>
                                            <td class="px-4 py-3 text-xs font-mono text-slate-700">
                                                {{ $dte['codigoGeneracion'] ?? 'N/A' }}
                                            </td>
                                            <td class="px-4 py-3 text-sm">
                                                @php
                                                    $tipoDte = $dte['tipoDte'] ?? $dte['tipo'] ?? 'N/A';
                                                @endphp
                                                @if($tipoDte == '01')
                                                    <span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded">DTE-01</span>
                                                @elseif($tipoDte == '03')
                                                    <span class="px-2 py-1 bg-green-100 text-green-800 text-xs rounded">DTE-03</span>
                                                @elseif($tipoDte == '05')
                                                    <span class="px-2 py-1 bg-purple-100 text-purple-800 text-xs rounded">DTE-05</span>
                                                @else
                                                    <span class="px-2 py-1 bg-slate-100 text-slate-800 text-xs rounded">{{ $tipoDte }}</span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3 text-center">
                                                @php
                                                    $estadoDte = strtoupper($dte['estado'] ?? 'N/A');
                                                    $estadoDteClass = match($estadoDte) {
                                                        'PROCESADO', 'APROBADO' => 'bg-green-100 text-green-800',
                                                        'RECHAZADO' => 'bg-red-100 text-red-800',
                                                        default => 'bg-slate-100 text-slate-800',
                                                    };
                                                @endphp
                                                <span class="px-2 py-1 {{ $estadoDteClass }} text-xs font-semibold rounded-full">
                                                    {{ $estadoDte }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-xs text-slate-500">
                                                {{ $dte['descripcionMsg'] ?? $dte['mensaje'] ?? '-' }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

                <!-- Otros datos de la respuesta -->
                <div class="space-y-4">
                    <h3 class="text-lg font-bold text-slate-800 mb-3">Detalles Completos</h3>
                    @foreach($resultado['data'] as $key => $value)
                        @if($key !== 'listado')
                            <div class="border-b border-slate-200 pb-3">
                                <p class="text-sm font-medium text-slate-500 mb-1">{{ ucfirst(str_replace('_', ' ', $key)) }}</p>

                                @if(is_array($value) || is_object($value))
                                    <pre class="bg-slate-50 p-3 rounded text-xs overflow-x-auto">{{ json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                @else
                                    <p class="text-slate-800">{{ $value }}</p>
                                @endif
                            </div>
                        @endif
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

                <a href="{{ route('contingencias.index') }}"
                   class="bg-orange-600 hover:bg-orange-700 text-white px-6 py-3 rounded-lg font-semibold transition-colors">
                    Ver Contingencias
                </a>

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
