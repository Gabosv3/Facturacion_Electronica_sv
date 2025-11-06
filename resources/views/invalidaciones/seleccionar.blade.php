<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seleccionar Factura para Invalidar - DTE El Salvador</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    @include('components.navbar')

    <div class="container mx-auto px-4 py-8">
        <div class="max-w-7xl mx-auto">
            <div class="bg-white rounded-lg shadow-lg p-6">
                <div class="mb-6">
                    <h1 class="text-3xl font-bold text-gray-800">Seleccionar Factura para Invalidar</h1>
                    <p class="text-gray-600 mt-2">Seleccione la factura que desea invalidar</p>
                </div>

                @if(session('error'))
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                        {{ session('error') }}
                    </div>
                @endif

                <!-- Tabla de facturas -->
                <div class="overflow-x-auto">
                    <table class="min-w-full table-auto">
                        <thead class="bg-gray-200">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Número de Control</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Tipo DTE</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Receptor</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Fecha Emisión</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Total</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Acción</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($facturas as $factura)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        <div class="text-sm font-mono font-semibold text-gray-900">
                                            {{ $factura->numero_control }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        @php
                                            $tipoLabels = [
                                                '01' => 'DTE-01',
                                                '03' => 'DTE-03',
                                                '05' => 'DTE-05',
                                                '14' => 'DTE-14',
                                            ];
                                        @endphp
                                        <span class="px-2 py-1 text-xs font-semibold rounded bg-blue-100 text-blue-800">
                                            {{ $tipoLabels[$factura->tipo_dte] ?? $factura->tipo_dte }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-4">
                                        <div class="text-sm font-semibold text-gray-900">
                                            {{ $factura->receptor_nombre }}
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            {{ $factura->receptor_num_documento }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">
                                            {{ $factura->fecha_emision->format('d/m/Y') }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        <div class="text-sm font-semibold text-gray-900">
                                            ${{ number_format($factura->total_pagar, 2) }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm font-medium">
                                        <a href="{{ route('invalidaciones.create', ['factura_id' => $factura->id]) }}"
                                           class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg transition">
                                            Invalidar
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-8 text-center text-gray-500">
                                        <p>No hay facturas disponibles para invalidar.</p>
                                        <p class="text-sm mt-2">Solo se pueden invalidar facturas enviadas exitosamente al MH.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Paginación -->
                <div class="mt-6">
                    {{ $facturas->links() }}
                </div>

                <!-- Botón para volver -->
                <div class="mt-6 flex justify-end">
                    <a href="{{ route('invalidaciones.index') }}"
                       class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg transition">
                        Volver
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
