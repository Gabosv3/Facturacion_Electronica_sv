<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facturas - DTE El Salvador</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-gray-100">
    @include('components.navbar')

    <div class="container mx-auto px-4 py-8">
        <div class="max-w-7xl mx-auto">
            <div class="bg-white rounded-lg shadow-lg p-6">
                <div class="flex justify-between items-center mb-6">
                    <h1 class="text-3xl font-bold text-gray-800">Facturas Electrónicas</h1>
                </div>

                <!-- Formulario de Búsqueda y Filtros -->
                <form method="GET" action="{{ route('facturas.index') }}" class="mb-6">
                    <div class="bg-gray-50 p-4 rounded-lg space-y-4">
                        <!-- Búsqueda por texto -->
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                            <div class="lg:col-span-2">
                                <label for="buscar" class="block text-sm font-medium text-gray-700 mb-1">Buscar</label>
                                <input type="text"
                                       id="buscar"
                                       name="buscar"
                                       value="{{ request('buscar') }}"
                                       placeholder="Número de control, cliente, documento..."
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <div>
                                <label for="tipo_dte" class="block text-sm font-medium text-gray-700 mb-1">Tipo DTE</label>
                                <select id="tipo_dte"
                                        name="tipo_dte"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">Todos</option>
                                    <option value="01" {{ request('tipo_dte') == '01' ? 'selected' : '' }}>DTE-01 (Factura)</option>
                                    <option value="03" {{ request('tipo_dte') == '03' ? 'selected' : '' }}>DTE-03 (CCF)</option>
                                    <option value="05" {{ request('tipo_dte') == '05' ? 'selected' : '' }}>DTE-05 (Nota Crédito)</option>
                                    <option value="14" {{ request('tipo_dte') == '14' ? 'selected' : '' }}>DTE-14 (Sujeto Excluido)</option>
                                </select>
                            </div>

                            <div>
                                <label for="estado" class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
                                <select id="estado"
                                        name="estado"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">Todos</option>
                                    <option value="enviado_mh" {{ request('estado') == 'enviado_mh' ? 'selected' : '' }}>Enviada MH</option>
                                    <option value="firmado" {{ request('estado') == 'firmado' ? 'selected' : '' }}>Firmada</option>
                                    <option value="firmado_sin_enviar" {{ request('estado') == 'firmado_sin_enviar' ? 'selected' : '' }}>Sin enviar</option>
                                    <option value="firmado_contingencia" {{ request('estado') == 'firmado_contingencia' ? 'selected' : '' }}>Contingencia</option>
                                    <option value="error_firma" {{ request('estado') == 'error_firma' ? 'selected' : '' }}>Error Firma</option>
                                    <option value="rechazado" {{ request('estado') == 'rechazado' ? 'selected' : '' }}>Rechazado</option>
                                    <option value="pendiente" {{ request('estado') == 'pendiente' ? 'selected' : '' }}>Pendiente</option>
                                </select>
                            </div>
                        </div>

                        <!-- Filtros de Fecha -->
                        <div class="border-t border-gray-200 pt-4">
                            <p class="text-sm font-medium text-gray-700 mb-3">Filtrar por Fecha</p>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                                <!-- Rango de fechas -->
                                <div>
                                    <label for="fecha_desde" class="block text-xs font-medium text-gray-600 mb-1">Desde</label>
                                    <input type="date"
                                           id="fecha_desde"
                                           name="fecha_desde"
                                           value="{{ request('fecha_desde') }}"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                </div>

                                <div>
                                    <label for="fecha_hasta" class="block text-xs font-medium text-gray-600 mb-1">Hasta</label>
                                    <input type="date"
                                           id="fecha_hasta"
                                           name="fecha_hasta"
                                           value="{{ request('fecha_hasta') }}"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                </div>

                                <!-- Mes y Año -->
                                <div>
                                    <label for="mes" class="block text-xs font-medium text-gray-600 mb-1">Mes</label>
                                    <select id="mes"
                                            name="mes"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                        <option value="">Todos</option>
                                        <option value="1" {{ request('mes') == '1' ? 'selected' : '' }}>Enero</option>
                                        <option value="2" {{ request('mes') == '2' ? 'selected' : '' }}>Febrero</option>
                                        <option value="3" {{ request('mes') == '3' ? 'selected' : '' }}>Marzo</option>
                                        <option value="4" {{ request('mes') == '4' ? 'selected' : '' }}>Abril</option>
                                        <option value="5" {{ request('mes') == '5' ? 'selected' : '' }}>Mayo</option>
                                        <option value="6" {{ request('mes') == '6' ? 'selected' : '' }}>Junio</option>
                                        <option value="7" {{ request('mes') == '7' ? 'selected' : '' }}>Julio</option>
                                        <option value="8" {{ request('mes') == '8' ? 'selected' : '' }}>Agosto</option>
                                        <option value="9" {{ request('mes') == '9' ? 'selected' : '' }}>Septiembre</option>
                                        <option value="10" {{ request('mes') == '10' ? 'selected' : '' }}>Octubre</option>
                                        <option value="11" {{ request('mes') == '11' ? 'selected' : '' }}>Noviembre</option>
                                        <option value="12" {{ request('mes') == '12' ? 'selected' : '' }}>Diciembre</option>
                                    </select>
                                </div>

                                <div>
                                    <label for="anio" class="block text-xs font-medium text-gray-600 mb-1">Año</label>
                                    <select id="anio"
                                            name="anio"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                        <option value="">Todos</option>
                                        @for($year = date('Y'); $year >= 2020; $year--)
                                            <option value="{{ $year }}" {{ request('anio') == $year ? 'selected' : '' }}>{{ $year }}</option>
                                        @endfor
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Botones de Acción -->
                        <div class="flex items-center gap-3 pt-2">
                            <button type="submit"
                                    class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-medium transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline-block mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                                Buscar
                            </button>

                            <a href="{{ route('facturas.index') }}"
                               class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg font-medium transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline-block mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                                Limpiar Filtros
                            </a>

                            @if(request()->hasAny(['buscar', 'tipo_dte', 'estado', 'fecha_desde', 'fecha_hasta', 'mes', 'anio']))
                                <span class="text-sm text-gray-600 ml-2">
                                    <span class="font-semibold text-blue-600">{{ $facturas->total() }}</span> resultado(s) encontrado(s)
                                </span>
                            @endif
                        </div>
                    </div>
                </form>

                @if($facturas->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600">Tipo</th>
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600">Número Control</th>
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600">Fecha</th>
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600">Cliente</th>
                                    <th class="px-4 py-3 text-right text-sm font-semibold text-gray-600">Total</th>
                                    <th class="px-4 py-3 text-center text-sm font-semibold text-gray-600">Estado</th>
                                    <th class="px-4 py-3 text-center text-sm font-semibold text-gray-600">Invalidación</th>
                                    <th class="px-4 py-3 text-center text-sm font-semibold text-gray-600">Nota Crédito</th>
                                    <th class="px-4 py-3 text-center text-sm font-semibold text-gray-600">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach($facturas as $factura)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 text-sm">
                                        @if($factura->tipo_dte == '01')
                                            <span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs font-semibold rounded">DTE-01</span>
                                        @elseif($factura->tipo_dte == '03')
                                            <span class="px-2 py-1 bg-green-100 text-green-800 text-xs font-semibold rounded">CCF-03</span>
                                        @elseif($factura->tipo_dte == '05')
                                            <span class="px-2 py-1 bg-purple-100 text-purple-800 text-xs font-semibold rounded">NC-05</span>
                                        @elseif($factura->tipo_dte == '14')
                                            <span class="px-2 py-1 bg-orange-100 text-orange-800 text-xs font-semibold rounded">FSE-14</span>
                                        @else
                                            <span class="px-2 py-1 bg-gray-100 text-gray-800 text-xs font-semibold rounded">DTE-{{ $factura->tipo_dte }}</span>
                                        @endif
                                    </td>
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
                                        @php
                                            $invalidacionActiva = $factura->getInvalidacionActiva();
                                        @endphp
                                        @if($factura->invalidada || $invalidacionActiva)
                                            @if($invalidacionActiva)
                                                @if($invalidacionActiva->estado == 'procesado')
                                                    <span class="px-2 py-1 bg-red-100 text-red-800 text-xs font-semibold rounded-full">
                                                        ✓ Invalidado
                                                    </span>
                                                @elseif($invalidacionActiva->estado == 'enviado')
                                                    <span class="px-2 py-1 bg-yellow-100 text-yellow-800 text-xs font-semibold rounded-full">
                                                        ⏳ En proceso
                                                    </span>
                                                @endif
                                            @else
                                                <span class="px-2 py-1 bg-red-100 text-red-800 text-xs font-semibold rounded-full">
                                                    ✓ Invalidado
                                                </span>
                                            @endif
                                        @elseif($factura->tieneInvalidacionPendiente())
                                            <span class="px-2 py-1 bg-gray-100 text-gray-800 text-xs font-semibold rounded-full">
                                                📝 Pendiente
                                            </span>
                                        @else
                                            <span class="text-gray-400 text-xs">-</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        @if($factura->nota_credito_estado)
                                            @if($factura->nota_credito_estado == 'anulacion')
                                                <span class="px-2 py-1 bg-red-100 text-red-800 text-xs font-semibold rounded-full">Anulado</span>
                                            @elseif($factura->nota_credito_estado == 'ajuste_precio')
                                                <span class="px-2 py-1 bg-orange-100 text-orange-800 text-xs font-semibold rounded-full">Ajuste</span>
                                            @elseif($factura->nota_credito_estado == 'devolucion')
                                                <span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs font-semibold rounded-full">Devolución</span>
                                            @else
                                                <span class="px-2 py-1 bg-gray-100 text-gray-800 text-xs font-semibold rounded-full">{{ ucfirst($factura->nota_credito_estado) }}</span>
                                            @endif
                                        @else
                                            <span class="text-gray-400 text-xs">-</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <div class="flex items-center justify-center gap-2">
                                            <a href="{{ route('facturas.show', $factura) }}" class="text-blue-600 hover:text-blue-800 font-medium text-sm">
                                                Ver Detalle
                                            </a>

                                            @if($factura->estado !== 'enviado_mh')
                                                <form id="delete-form-{{ $factura->id }}" action="{{ route('facturas.destroy', $factura) }}" method="POST" class="inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="button"
                                                            onclick="confirmarEliminacion({{ $factura->id }}, '{{ $factura->numero_control }}', '{{ addslashes($factura->receptor_nombre) }}', '{{ number_format($factura->total_pagar, 2) }}', '{{ ucfirst(str_replace('_', ' ', $factura->estado)) }}')"
                                                            class="text-red-600 hover:text-red-800 p-1 hover:bg-red-50 rounded transition-colors"
                                                            title="Eliminar factura">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                            <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                                        </svg>
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
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

    <script>
        function confirmarEliminacion(facturaId, numeroControl, cliente, total, estado) {
            Swal.fire({
                title: '¿Eliminar esta factura?',
                html: `
                    <div class="text-left space-y-2 mt-4">
                        <div class="bg-gray-50 p-3 rounded">
                            <p class="text-sm"><span class="font-semibold text-gray-700">Número de Control:</span><br>
                            <span class="text-gray-900">${numeroControl}</span></p>
                        </div>
                        <div class="bg-gray-50 p-3 rounded">
                            <p class="text-sm"><span class="font-semibold text-gray-700">Cliente:</span><br>
                            <span class="text-gray-900">${cliente}</span></p>
                        </div>
                        <div class="bg-gray-50 p-3 rounded">
                            <p class="text-sm"><span class="font-semibold text-gray-700">Total:</span><br>
                            <span class="text-gray-900 text-lg font-bold">$${total}</span></p>
                        </div>
                        <div class="bg-gray-50 p-3 rounded">
                            <p class="text-sm"><span class="font-semibold text-gray-700">Estado:</span><br>
                            <span class="text-gray-900">${estado}</span></p>
                        </div>
                    </div>
                    <div class="mt-4 p-3 bg-red-50 border border-red-200 rounded">
                        <p class="text-sm text-red-800 font-semibold">⚠️ Esta acción no se puede deshacer</p>
                    </div>
                `,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#6b7280',
                confirmButtonText: '<i class="mr-2">🗑️</i> Sí, eliminar',
                cancelButtonText: 'Cancelar',
                width: '500px',
                customClass: {
                    confirmButton: 'px-6 py-2 text-white font-semibold rounded-lg',
                    cancelButton: 'px-6 py-2 text-white font-semibold rounded-lg'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    // Mostrar loading mientras se procesa
                    Swal.fire({
                        title: 'Eliminando...',
                        text: 'Por favor espere',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    // Enviar el formulario
                    document.getElementById('delete-form-' + facturaId).submit();
                }
            });
        }

        // Mostrar alertas de SweetAlert para los mensajes de sesión
        @if(session('success'))
            Swal.fire({
                icon: 'success',
                title: '¡Éxito!',
                text: '{{ session('success') }}',
                confirmButtonColor: '#10b981',
                timer: 3000,
                timerProgressBar: true,
            });
        @endif

        @if(session('error'))
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: '{{ session('error') }}',
                confirmButtonColor: '#dc2626',
            });
        @endif

        @if(session('warning'))
            Swal.fire({
                icon: 'warning',
                title: 'Advertencia',
                text: '{{ session('warning') }}',
                confirmButtonColor: '#f59e0b',
            });
        @endif
    </script>
</body>
</html>