<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facturas - DTE El Salvador</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-7xl mx-auto">
            <div class="bg-white rounded-lg shadow-lg p-6">
                <div class="flex justify-between items-center mb-6">
                    <h1 class="text-3xl font-bold text-gray-800">Facturas Electrónicas</h1>
                    <a href="{{ route('facturas.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-6 py-3 rounded-lg">
                        + Nueva Factura
                    </a>
                </div>

                @if(session('success'))
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                        {{ session('success') }}
                    </div>
                @endif

                @if($facturas->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600">Número Control</th>
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600">Fecha</th>
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600">Cliente</th>
                                    <th class="px-4 py-3 text-right text-sm font-semibold text-gray-600">Total</th>
                                    <th class="px-4 py-3 text-center text-sm font-semibold text-gray-600">Estado</th>
                                    <th class="px-4 py-3 text-center text-sm font-semibold text-gray-600">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach($facturas as $factura)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ $factura->numero_control }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ $factura->fecha_emision->format('d/m/Y') }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ $factura->receptor_nombre }}</td>
                                    <td class="px-4 py-3 text-sm text-right font-semibold text-gray-700">${{ number_format($factura->total_pagar, 2) }}</td>
                                    <td class="px-4 py-3 text-center">
                                        @if($factura->estado == 'enviado_mh')
                                            <span class="px-3 py-1 bg-green-100 text-green-800 text-xs font-semibold rounded-full">Enviada MH</span>
                                        @elseif($factura->estado == 'firmado')
                                            <span class="px-3 py-1 bg-blue-100 text-blue-800 text-xs font-semibold rounded-full">Firmada</span>
                                        @elseif($factura->estado == 'firmado_sin_enviar')
                                            <span class="px-3 py-1 bg-yellow-100 text-yellow-800 text-xs font-semibold rounded-full">Sin enviar</span>
                                        @elseif($factura->estado == 'error_firma')
                                            <span class="px-3 py-1 bg-red-100 text-red-800 text-xs font-semibold rounded-full">Error Firma</span>
                                        @elseif($factura->estado == 'rechazado')
                                            <span class="px-3 py-1 bg-red-100 text-red-800 text-xs font-semibold rounded-full">Rechazado</span>
                                        @else
                                            <span class="px-3 py-1 bg-gray-100 text-gray-800 text-xs font-semibold rounded-full">Pendiente</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <a href="{{ route('facturas.show', $factura) }}" class="text-blue-600 hover:text-blue-800 font-medium text-sm">
                                            Ver Detalle
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-6">
                        {{ $facturas->links() }}
                    </div>
                @else
                    <div class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <p class="mt-4 text-gray-600 text-lg">No hay facturas registradas</p>
                        <a href="{{ route('facturas.create') }}" class="mt-4 inline-block bg-blue-600 hover:bg-blue-700 text-white font-semibold px-6 py-2 rounded-lg">
                            Crear Primera Factura
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</body>
</html>