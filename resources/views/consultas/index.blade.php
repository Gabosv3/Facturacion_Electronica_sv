<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consultas DTE - Ministerio de Hacienda</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-gray-100">
    @include('components.navbar')

    <div class="container mx-auto px-4 py-8">
        <div class="max-w-6xl mx-auto">
            <div class="mb-6">
                <h1 class="text-3xl font-bold text-gray-800">Consultas DTE - Ministerio de Hacienda</h1>
                <p class="text-gray-600 mt-2">Consulta el estado de documentos tributarios electrónicos en el MH</p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Consulta de DTE Individual -->
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <div class="flex items-center mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-blue-600 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <h2 class="text-2xl font-bold text-gray-800">Consultar DTE</h2>
                    </div>

                    <p class="text-gray-600 mb-6">Consulta un DTE específico ingresando sus datos</p>

                    <form action="{{ route('consultas.dte') }}" method="POST" class="space-y-4">
                        @csrf

                        <div>
                            <label for="nit_emisor" class="block text-sm font-medium text-gray-700 mb-1">NIT del Emisor</label>
                            <input type="text"
                                   id="nit_emisor"
                                   name="nit_emisor"
                                   value="{{ old('nit_emisor', config('dte.nit')) }}"
                                   placeholder="06142302881449"
                                   maxlength="14"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                   required>
                            <p class="text-xs text-gray-500 mt-1">14 dígitos, sin guiones</p>
                        </div>

                        <div>
                            <label for="tipo_dte" class="block text-sm font-medium text-gray-700 mb-1">Tipo de DTE</label>
                            <select id="tipo_dte"
                                    name="tipo_dte"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    required>
                                <option value="01">01 - Factura</option>
                                <option value="03">03 - Comprobante de Crédito Fiscal</option>
                                <option value="05">05 - Nota de Crédito</option>
                                <option value="06">06 - Nota de Débito</option>
                                <option value="07">07 - Comprobante de Retención</option>
                                <option value="08">08 - Comprobante de Liquidación</option>
                                <option value="09">09 - Documento Contable de Liquidación</option>
                                <option value="11">11 - Factura de Exportación</option>
                                <option value="14">14 - Factura de Sujeto Excluido</option>
                                <option value="15">15 - Comprobante de Donación</option>
                            </select>
                        </div>

                        <div>
                            <label for="codigo_generacion" class="block text-sm font-medium text-gray-700 mb-1">Código de Generación</label>
                            <input type="text"
                                   id="codigo_generacion"
                                   name="codigo_generacion"
                                   value="{{ old('codigo_generacion') }}"
                                   placeholder="XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX"
                                   maxlength="36"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                   required>
                            <p class="text-xs text-gray-500 mt-1">UUID del documento (36 caracteres)</p>
                        </div>

                        <button type="submit"
                                class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 rounded-lg transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline-block mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            Consultar DTE
                        </button>
                    </form>
                </div>

                <!-- Consulta de Lote -->
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <div class="flex items-center mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-green-600 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                        <h2 class="text-2xl font-bold text-gray-800">Consultar Lote</h2>
                    </div>

                    <p class="text-gray-600 mb-6">Consulta el estado de un lote de DTEs</p>

                    <form action="{{ route('consultas.lote') }}" method="POST" class="space-y-4">
                        @csrf

                        <div>
                            <label for="codigo_lote" class="block text-sm font-medium text-gray-700 mb-1">Código de Lote</label>
                            <input type="text"
                                   id="codigo_lote"
                                   name="codigo_lote"
                                   value="{{ old('codigo_lote') }}"
                                   placeholder="LOTE-XXXXXXXX"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500"
                                   required>
                            <p class="text-xs text-gray-500 mt-1">Código asignado por el MH al enviar el lote</p>
                        </div>

                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                            <p class="text-sm text-blue-800">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline-block mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                El código de lote se obtiene al enviar un lote en modo contingencia.
                            </p>
                        </div>

                        <button type="submit"
                                class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold py-3 rounded-lg transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline-block mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            Consultar Lote
                        </button>
                    </form>
                </div>
            </div>

            <!-- Buscar desde facturas locales -->
            <div class="mt-6 bg-white rounded-lg shadow-lg p-6">
                <div class="flex items-center mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-purple-600 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                    </svg>
                    <h2 class="text-2xl font-bold text-gray-800">Consulta Rápida</h2>
                </div>

                <p class="text-gray-600 mb-4">Consulta directamente desde tus facturas registradas. Ve al listado de facturas y haz clic en "Ver Detalle" para consultar su estado en el MH.</p>

                <a href="{{ route('facturas.index') }}"
                   class="inline-flex items-center bg-purple-600 hover:bg-purple-700 text-white font-semibold px-6 py-3 rounded-lg transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                    Ver Facturas
                </a>
            </div>
        </div>
    </div>

    <script>
        @if(session('error'))
            @if(session('error_detalle'))
                Swal.fire({
                    icon: 'error',
                    title: 'Error en Consulta',
                    html: `
                        <div class="text-left">
                            <p class="mb-3 text-sm"><strong>Mensaje:</strong><br>{{ session('error') }}</p>

                            @if(session('error_detalle')['status'] ?? false)
                                <p class="mb-2 text-sm"><strong>Código HTTP:</strong> {{ session('error_detalle')['status'] }}</p>
                            @endif

                            @if(session('error_detalle')['raw_body'] ?? false)
                                <details class="mt-3 bg-gray-100 p-2 rounded">
                                    <summary class="cursor-pointer text-sm font-semibold">Ver respuesta completa del MH</summary>
                                    <pre class="text-xs mt-2 overflow-auto">{{ session('error_detalle')['raw_body'] }}</pre>
                                </details>
                            @endif

                            <div class="mt-4 p-3 bg-blue-50 border border-blue-200 rounded text-sm">
                                <p class="font-semibold mb-2">💡 Tip:</p>
                                <p>Revisa los logs en <code>storage/logs/laravel.log</code> para más detalles técnicos.</p>
                            </div>
                        </div>
                    `,
                    width: '600px',
                    confirmButtonColor: '#dc2626',
                });
            @else
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: '{{ session('error') }}',
                    confirmButtonColor: '#dc2626',
                });
            @endif
        @endif

        @if(session('success'))
            Swal.fire({
                icon: 'success',
                title: '¡Éxito!',
                text: '{{ session('success') }}',
                confirmButtonColor: '#10b981',
            });
        @endif
    </script>
</body>
</html>
