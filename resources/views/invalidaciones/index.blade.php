<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eventos de Invalidación - DTE El Salvador</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-gray-100">
    @include('components.navbar')

    <div class="container mx-auto px-4 py-8">
        <div class="max-w-7xl mx-auto">
            <div class="bg-white rounded-lg shadow-lg p-6">
                <div class="flex justify-between items-center mb-6">
                    <h1 class="text-3xl font-bold text-gray-800">Eventos de Invalidación</h1>
                    <a href="{{ route('invalidaciones.seleccionar') }}"
                       class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-6 py-3 rounded-lg transition duration-150 ease-in-out shadow-md">
                        Nueva Invalidación
                    </a>
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

                <!-- Tabla de eventos -->
                <div class="overflow-x-auto">
                    <table class="min-w-full table-auto">
                        <thead class="bg-gray-200">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Código</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Factura</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Tipo</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Fecha Evento</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Estado</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($eventos as $evento)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        <div class="text-sm font-mono text-gray-900">
                                            {{ substr($evento->codigo_invalidacion, 0, 8) }}...
                                        </div>
                                    </td>
                                    <td class="px-4 py-4">
                                        <div class="text-sm font-semibold text-gray-900">
                                            {{ $evento->factura->numero_control }}
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            {{ $evento->factura->receptor_nombre }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">
                                            {{ $evento->tipo_invalidacion == '1' ? 'Errores formales' : 'Rescindir operación' }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">
                                            {{ $evento->fecha_evento->format('d/m/Y H:i') }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        @php
                                            $estadoClasses = [
                                                'pendiente' => 'bg-yellow-100 text-yellow-800',
                                                'firmado' => 'bg-blue-100 text-blue-800',
                                                'enviado' => 'bg-purple-100 text-purple-800',
                                                'procesado' => 'bg-green-100 text-green-800',
                                                'error' => 'bg-red-100 text-red-800',
                                            ];
                                        @endphp
                                        <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full {{ $estadoClasses[$evento->estado] ?? 'bg-gray-100 text-gray-800' }}">
                                            {{ ucfirst($evento->estado) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex space-x-2">
                                            <a href="{{ route('invalidaciones.show', $evento->id) }}"
                                               class="text-blue-600 hover:text-blue-900">
                                                Ver detalles
                                            </a>

                                            @if($evento->puedeEnviarse())
                                                <form action="{{ route('invalidaciones.reintentar', $evento->id) }}" method="POST" class="inline">
                                                    @csrf
                                                    <button type="submit"
                                                            class="text-orange-600 hover:text-orange-900"
                                                            onclick="return confirm('¿Reintentar envío de este evento?')">
                                                        Reintentar
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-8 text-center text-gray-500">
                                        No hay eventos de invalidación registrados.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Paginación -->
                <div class="mt-6">
                    {{ $eventos->links() }}
                </div>
            </div>
        </div>
    </div>
</body>
</html>
