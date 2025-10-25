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