<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Generar Factura Sujeto Excluido - DTE-14</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="{{ asset('js/catalogo-municipios.js') }}"></script>
</head>
<body class="bg-gray-100">
    @include('components.navbar')

    <div class="container mx-auto px-4 py-8">
        <div class="max-w-5xl mx-auto">
            <div class="bg-white rounded-lg shadow-lg p-6">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h1 class="text-3xl font-bold text-orange-800">Factura de Sujeto Excluido (DTE-14)</h1>
                        <p class="text-sm text-gray-600 mt-1">Para personas o entidades excluidas del pago de IVA</p>
                    </div>
                    <div class="bg-orange-100 px-4 py-2 rounded-lg">
                        <p class="text-xs text-orange-700 font-semibold">SIN IVA</p>
                        <p class="text-xs text-orange-600">IVA = $0.00</p>
                    </div>
                </div>

                <div class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-6">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-blue-700">
                                <strong>Características del DTE-14:</strong> Este documento NO incluye IVA. Todo se registra como venta exenta.
                                Requiere datos completos del cliente incluyendo actividad económica.
                            </p>
                        </div>
                    </div>
                </div>

                @if(session('error'))
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                        {{ session('error') }}
                    </div>
                @endif

                @if(session('warning'))
                    <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded mb-4">
                        {{ session('warning') }}
                    </div>
                @endif

                <form action="{{ route('facturas.store') }}" method="POST" id="facturaForm">
                    @csrf
                    <input type="hidden" name="tipo_dte" value="14">

                    <!-- Datos del Receptor -->
                    <div class="mb-8">
                        <h2 class="text-xl font-semibold text-gray-700 mb-4 border-b pb-2">Datos del Cliente</h2>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de Documento *</label>
                                <select name="receptor_tipo_documento" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500" required>
                                    <option value="">Seleccione...</option>
                                    <option value="36">NIT - Registro de contribuyente IVA</option>
                                    <option value="13" selected>DUI - Persona natural sin IVA</option>
                                    <option value="02">Carnet de residente</option>
                                    <option value="03">Pasaporte</option>
                                    <option value="37">Otro</option>
                                </select>
                                <p class="text-xs text-gray-500 mt-1">DUI preseleccionado por defecto para sujetos excluidos</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Número de Documento *</label>
                                <div class="relative">
                                    <input type="text" id="receptor_num_documento" name="receptor_num_documento" placeholder="DUI: 12345678-9 / NIT: 1234-567890-123-4" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500" required value="{{ old('receptor_num_documento') }}">
                                    <div id="busquedaLoader" style="display:none;" class="absolute right-3 top-3">
                                        <svg class="animate-spin h-5 w-5 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                    </div>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">DUI: 8 dígitos-1 dígito / NIT: 14 dígitos con guiones</p>
                                <p id="resultadoBusqueda" class="text-xs mt-1 hidden"></p>
                            </div>

                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Nombre Completo *</label>
                                <input type="text" name="receptor_nombre" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500" required value="{{ old('receptor_nombre') }}" style="text-transform: uppercase;" oninput="this.value = this.value.toUpperCase()">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Código Actividad Económica (Opcional)</label>
                                <input type="text" name="receptor_cod_actividad" pattern="[0-9]*" inputmode="numeric" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-orange-500" value="{{ old('receptor_cod_actividad') }}" placeholder="Ej: 10010" oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                                <p class="text-xs text-gray-500 mt-1">Código de la actividad económica del sujeto excluido (opcional según MH)</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Descripción Actividad Económica (Opcional)</label>
                                <input type="text" name="receptor_desc_actividad" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-orange-500" value="{{ old('receptor_desc_actividad') }}" placeholder="Ej: CULTIVO DE CEREALES">
                                <p class="text-xs text-gray-500 mt-1">Mínimo 5 caracteres (opcional según MH)</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Teléfono</label>
                                <input type="text" name="receptor_telefono" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-orange-500" value="{{ old('receptor_telefono') }}" placeholder="7777-7777" maxlength="9" oninput="formatearTelefono(this)">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Correo Electrónico</label>
                                <input type="email" name="receptor_correo" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500" value="{{ old('receptor_correo') }}">
                            </div>
                        </div>
                    </div>

                    <!-- Dirección del Receptor -->
                    <div class="mb-8">
                        <h2 class="text-xl font-semibold text-gray-700 mb-4 border-b pb-2">Dirección del Cliente</h2>
                        
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Departamento *</label>
                                <select name="receptor_departamento" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500" required>
                                    <option value="">Seleccione...</option>
                                    <option value="01">Ahuachapán</option>
                                    <option value="02">Santa Ana</option>
                                    <option value="03">Sonsonate</option>
                                    <option value="04">Chalatenango</option>
                                    <option value="05">La Libertad</option>
                                    <option value="06">San Salvador</option>
                                    <option value="07">Cuscatlán</option>
                                    <option value="08">La Paz</option>
                                    <option value="09">Cabañas</option>
                                    <option value="10">San Vicente</option>
                                    <option value="11">Usulután</option>
                                    <option value="12">San Miguel</option>
                                    <option value="13">Morazán</option>
                                    <option value="14">La Unión</option>
                                </select>
                            </div>

                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Municipio *</label>
                                <select name="receptor_municipio" id="municipioSelect" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500" required>
                                    <option value="">Seleccione primero un departamento</option>
                                </select>
                                <p class="text-xs text-gray-500 mt-1" id="municipioHelp">Seleccione un departamento para ver los municipios</p>
                            </div>

                            <div class="md:col-span-3">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Dirección Completa *</label>
                                <textarea name="receptor_complemento" rows="2" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500" required>{{ old('receptor_complemento') }}</textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Items de la Factura -->
                    <div class="mb-8">
                        <div class="flex justify-between items-center mb-4">
                            <h2 class="text-xl font-semibold text-gray-700">Productos/Servicios</h2>
                            <div class="bg-orange-100 px-3 py-1 rounded-lg">
                                <p class="text-xs text-orange-700 font-semibold">SIN IVA</p>
                            </div>
                        </div>
                        
                        <div id="itemsContainer">
                            <div class="item-row bg-gray-50 p-4 rounded-lg mb-4">
                                <input type="hidden" name="items[0][producto_id]" class="producto-id" value="">
                                <div class="grid grid-cols-1 md:grid-cols-7 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Tipo *</label>
                                        <select name="items[0][tipo_item]" class="tipo-item tipo-select w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500" data-item-index="0" required>
                                            <option value="">Seleccionar</option>
                                            <option value="1">Bien</option>
                                            <option value="2">Servicio</option>
                                        </select>
                                        <p class="tipo-error text-xs text-red-600 mt-1 hidden">⚠️ Debe seleccionar un tipo</p>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Código *</label>
                                        <div class="relative">
                                            <input type="text" name="items[0][codigo]" class="codigo-item w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500" required>
                                            <div class="loader-producto hidden absolute right-3 top-3">
                                                <svg class="animate-spin h-5 w-5 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                            </div>
                                        </div>
                                        <p class="resultado-producto text-xs mt-1 hidden"></p>
                                        <p class="codigo-error text-xs text-red-600 mt-1 hidden">⚠️ El código no puede estar vacío</p>
                                    </div>

                                    <div class="md:col-span-2">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Descripción *</label>
                                        <input type="text" name="items[0][descripcion]" class="descripcion-item w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500" required>
                                        <p class="descripcion-error text-xs text-red-600 mt-1 hidden">⚠️ La descripción no puede estar vacía</p>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Cantidad *</label>
                                        <input type="number" step="1" name="items[0][cantidad]" class="cantidad w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500" required min="1">
                                        <p class="cantidad-error text-xs text-red-600 mt-1 hidden">⚠️ Debe ser un número entero positivo (ej: 1, 2, 3...)</p>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Precio Unit. *</label>
                                        <input type="number" step="0.01" name="items[0][precio_unitario]" class="precio w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500" required min="0">
                                        <p class="precio-error text-xs text-red-600 mt-1 hidden">⚠️ El precio debe ser un número positivo</p>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Descuento</label>
                                        <input type="number" step="0.01" name="items[0][monto_descuento]" value="0" class="descuento w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500" min="0">
                                        <p class="descuento-error text-xs text-red-600 mt-1 hidden">⚠️ El descuento no puede ser mayor que el precio unitario</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <button type="button" id="addItem" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg">
                            + Agregar Item
                        </button>
                    </div>

                    <!-- Resumen y Condiciones -->
                    <div class="mb-8">
                        <h2 class="text-xl font-semibold text-gray-700 mb-4 border-b pb-2">Condiciones de Pago</h2>
                        
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Condición *</label>
                                <select name="condicion_operacion" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500" required>
                                    <option value="1">Contado</option>
                                    <!-- <option value="2">Crédito</option> -->
                                    <option value="3">Otro</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Forma de Pago *</label>
                                <select name="forma_pago" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500" required>
                                    <option value="01">Efectivo</option>
                                    <option value="02">Cheque</option>
                                    <option value="03">Transferencia</option>
                                    <option value="04">Tarjeta Crédito</option>
                                    <option value="05">Tarjeta Débito</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Referencia de Pago</label>
                                <input type="text" name="referencia_pago" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500" value="{{ old('referencia_pago') }}">
                            </div>

                            <div class="md:col-span-3">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Observaciones</label>
                                <textarea name="observaciones" rows="2" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">{{ old('observaciones') }}</textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Resumen de Totales -->
                    <div class="bg-blue-50 p-6 rounded-lg mb-6">
                        <h3 class="text-lg font-semibold text-gray-700 mb-4">Resumen</h3>
                        <div class="space-y-2">
                            <div class="flex justify-between text-gray-600">
                                <span>Subtotal:</span>
                                <span id="subtotal">$0.00</span>
                            </div>
                            <div class="flex justify-between text-red-600">
                                <span>(-) Retención Renta (10%):</span>
                                <span id="retencion">$0.00</span>
                            </div>
                            <div class="flex justify-between text-xl font-bold text-blue-600 border-t pt-2">
                                <span>Total a Pagar:</span>
                                <span id="total">$0.00</span>
                            </div>
                        </div>
                    </div>

                    <!-- Botones -->
                    <div class="flex gap-4">
                        <button type="submit" class="flex-1 bg-green-600 hover:bg-green-700 text-white font-semibold px-6 py-3 rounded-lg">
                            Generar y Firmar Factura
                        </button>
                        <a href="{{ route('facturas.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white font-semibold px-6 py-3 rounded-lg">
                            Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        let itemCount = 1;
        let servicioCount = 1;
        
        // Obtener token de autenticación del servidor
        const authToken = @json(session('auth_token'));
        const motorApiUrl = @json(config('services.motor_api.url'));

        // Función para manejar errores de autenticación (Token expirado)
        function handleAuthError(error) {
            if (error.message.includes('HTTP 401') || error.message.includes('Token expirado')) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Sesión expirada',
                    text: 'Tu sesión de conexión con el API ha expirado. Por favor recarga la página.',
                    confirmButtonText: 'Recargar página',
                    allowOutsideClick: false
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.reload();
                    }
                });
                return true;
            }
            return false;
        }

        // Agregar nuevo item
        document.getElementById('addItem').addEventListener('click', function() {
            const container = document.getElementById('itemsContainer');
            const newItem = document.createElement('div');
            newItem.className = 'item-row bg-gray-50 p-4 rounded-lg mb-4';
            newItem.innerHTML = `
                <input type="hidden" name="items[${itemCount}][producto_id]" class="producto-id" value="">
                <div class="grid grid-cols-1 md:grid-cols-7 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tipo *</label>
                        <select name="items[${itemCount}][tipo_item]" class="tipo-item tipo-select w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500" data-item-index="${itemCount}" required>
                            <option value="">Seleccionar</option>
                            <option value="1">Bien</option>
                            <option value="2">Servicio</option>
                        </select>
                        <p class="tipo-error text-xs text-red-600 mt-1 hidden">⚠️ Debe seleccionar un tipo</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Código *</label>
                        <div class="relative">
                            <input type="text" name="items[${itemCount}][codigo]" class="codigo-item w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500" required>
                            <div class="loader-producto hidden absolute right-3 top-3">
                                <svg class="animate-spin h-5 w-5 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </div>
                        </div>
                        <p class="resultado-producto text-xs mt-1 hidden"></p>
                        <p class="codigo-error text-xs text-red-600 mt-1 hidden">⚠️ El código no puede estar vacío</p>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Descripción *</label>
                        <input type="text" name="items[${itemCount}][descripcion]" class="descripcion-item w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500" required>
                        <p class="descripcion-error text-xs text-red-600 mt-1 hidden">⚠️ La descripción no puede estar vacía</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Cantidad *</label>
                        <input type="number" step="1" name="items[${itemCount}][cantidad]" class="cantidad w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500" required min="1">
                        <p class="cantidad-error text-xs text-red-600 mt-1 hidden">⚠️ Debe ser un número entero positivo (ej: 1, 2, 3...)</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Precio Unit. *</label>
                        <input type="number" step="0.01" name="items[${itemCount}][precio_unitario]" class="precio w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500" required min="0">
                        <p class="precio-error text-xs text-red-600 mt-1 hidden">⚠️ El precio debe ser un número positivo</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Descuento</label>
                        <input type="number" step="0.01" name="items[${itemCount}][monto_descuento]" value="0" class="descuento w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500" min="0">
                        <p class="descuento-error text-xs text-red-600 mt-1 hidden">⚠️ El descuento no puede ser mayor que el precio unitario</p>
                    </div>
                </div>
                <button type="button" class="removeItem mt-2 text-red-500 hover:text-red-700 text-sm">Eliminar</button>
            `;
            container.appendChild(newItem);
            
            // Setup búsqueda y lógica de tipo
            const codigoInput = newItem.querySelector('.codigo-item');
            const tipoSelect = newItem.querySelector('.tipo-item');
            
            setupProductSearch(codigoInput);
            
            tipoSelect.addEventListener('change', function() {
                aplicarLogicaTipo(this, codigoInput);
            });
            
            itemCount++;
            attachCalculateEvents();
        });

        // Aplicar lógica según el tipo seleccionado
        function aplicarLogicaTipo(tipoSelect, codigoInput) {
            const tipo = tipoSelect.value;
            
            if (tipo === '2') {
                // SERVICIO: código auto-generado
                const servicioCode = 'SERV-' + String(servicioCount).padStart(3, '0');
                servicioCount++;
                
                codigoInput.value = servicioCode;
                codigoInput.readOnly = true;
                codigoInput.classList.add('bg-gray-200');
                codigoInput.style.cursor = 'not-allowed';
                
                // Remover evento de búsqueda (clonar para limpiar listeners)
                const newCodigoInput = codigoInput.cloneNode(true);
                codigoInput.parentNode.replaceChild(newCodigoInput, codigoInput);
                // Re-asignar referencia para que funcione el resto del código si fuera necesario
                // Pero en este caso, como es readOnly, no necesitamos re-asignar setupProductSearch
                
            } else if (tipo === '1') {
                // BIEN: código manual con búsqueda
                codigoInput.value = '';
                codigoInput.readOnly = false;
                codigoInput.classList.remove('bg-gray-200');
                codigoInput.style.cursor = 'auto';
                
                // Re-agregar búsqueda
                setupProductSearch(codigoInput);
            }
        }

        // Eliminar item
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('removeItem')) {
                e.target.closest('.item-row').remove();
                calcularTotales();
            }
        });

        // Calcular totales para DTE-14 (SIN IVA - Sujeto Excluido)
        function calcularTotales() {
            let subtotalGeneral = 0;
            
            document.querySelectorAll('.item-row').forEach(row => {
                const cantidad = parseFloat(row.querySelector('.cantidad').value) || 0;
                const precio = parseFloat(row.querySelector('.precio').value) || 0;
                const descuento = parseFloat(row.querySelector('.descuento').value) || 0;
                
                // Simple: cantidad * precio - descuento (SIN IVA)
                const subtotalItem = (cantidad * precio) - descuento;
                subtotalGeneral += subtotalItem;
            });

            // Calcular Retención del 10% sobre el subtotal
            const retencionRenta = subtotalGeneral * 0.10;
            const totalPagar = subtotalGeneral - retencionRenta;
            
            // Actualizar DOM
            if(document.getElementById('subtotal')) {
                document.getElementById('subtotal').textContent = '$' + subtotalGeneral.toFixed(2);
            }
            
            if(document.getElementById('retencion')) {
                document.getElementById('retencion').textContent = '$' + retencionRenta.toFixed(2);
            }

            document.getElementById('total').textContent = '$' + totalPagar.toFixed(2);
        }

        function attachCalculateEvents() {
            document.querySelectorAll('.cantidad, .precio, .descuento').forEach(input => {
                input.removeEventListener('input', calcularTotales);
                input.addEventListener('input', calcularTotales);
            });

            // Agregar validaciones en tiempo real
            attachFieldValidations();
            attachDiscountValidation();
        }

        // Validaciones en tiempo real para campos de producto/servicio
        function attachFieldValidations() {
            document.querySelectorAll('.item-row').forEach(row => {
                const tipoSelect = row.querySelector('.tipo-select');
                const codigoInput = row.querySelector('.codigo-item');
                const descripcionInput = row.querySelector('.descripcion-item');
                const cantidadInput = row.querySelector('.cantidad');
                const precioInput = row.querySelector('.precio');

                const tipoError = row.querySelector('.tipo-error');
                const codigoError = row.querySelector('.codigo-error');
                const descripcionError = row.querySelector('.descripcion-error');
                const cantidadError = row.querySelector('.cantidad-error');
                const precioError = row.querySelector('.precio-error');

                // Validar Tipo
                if (tipoSelect) {
                    tipoSelect.addEventListener('change', function() {
                        if (this.value.trim() === '') {
                            this.classList.add('border-red-500', 'bg-red-50');
                            this.classList.remove('border-gray-300');
                            tipoError.classList.remove('hidden');
                        } else {
                            this.classList.remove('border-red-500', 'bg-red-50');
                            this.classList.add('border-gray-300');
                            tipoError.classList.add('hidden');
                        }
                    });
                }

                // Validar Código
                if (codigoInput) {
                    codigoInput.addEventListener('blur', function() {
                        if (this.value.trim() === '') {
                            this.classList.add('border-red-500', 'bg-red-50');
                            this.classList.remove('border-gray-300');
                            codigoError.classList.remove('hidden');
                        } else {
                            this.classList.remove('border-red-500', 'bg-red-50');
                            this.classList.add('border-gray-300');
                            codigoError.classList.add('hidden');
                        }
                    });

                    codigoInput.addEventListener('input', function() {
                        if (this.value.trim() === '') {
                            this.classList.add('border-red-500', 'bg-red-50');
                            this.classList.remove('border-gray-300');
                            codigoError.classList.remove('hidden');
                        } else {
                            this.classList.remove('border-red-500', 'bg-red-50');
                            this.classList.add('border-gray-300');
                            codigoError.classList.add('hidden');
                        }
                    });
                }

                // Validar Descripción
                if (descripcionInput) {
                    descripcionInput.addEventListener('blur', function() {
                        if (this.value.trim() === '') {
                            this.classList.add('border-red-500', 'bg-red-50');
                            this.classList.remove('border-gray-300');
                            descripcionError.classList.remove('hidden');
                        } else {
                            this.classList.remove('border-red-500', 'bg-red-50');
                            this.classList.add('border-gray-300');
                            descripcionError.classList.add('hidden');
                        }
                    });

                    descripcionInput.addEventListener('input', function() {
                        if (this.value.trim() === '') {
                            this.classList.add('border-red-500', 'bg-red-50');
                            this.classList.remove('border-gray-300');
                            descripcionError.classList.remove('hidden');
                        } else {
                            this.classList.remove('border-red-500', 'bg-red-50');
                            this.classList.add('border-gray-300');
                            descripcionError.classList.add('hidden');
                        }
                    });
                }

                // Validar Cantidad (entero positivo)
                if (cantidadInput) {
                    cantidadInput.addEventListener('input', function() {
                        const valor = this.value;
                        const numero = parseFloat(valor);

                        // Verificar si es vacío o no es número
                        if (valor === '' || isNaN(numero)) {
                            this.classList.add('border-red-500', 'bg-red-50');
                            this.classList.remove('border-gray-300');
                            cantidadError.classList.remove('hidden');
                            return;
                        }

                        // Verificar si es positivo
                        if (numero <= 0) {
                            this.classList.add('border-red-500', 'bg-red-50');
                            this.classList.remove('border-gray-300');
                            cantidadError.classList.remove('hidden');
                            return;
                        }

                        // Verificar si es entero
                        if (!Number.isInteger(numero)) {
                            this.classList.add('border-red-500', 'bg-red-50');
                            this.classList.remove('border-gray-300');
                            cantidadError.classList.remove('hidden');
                            return;
                        }

                        // Si es válido
                        this.classList.remove('border-red-500', 'bg-red-50');
                        this.classList.add('border-gray-300');
                        cantidadError.classList.add('hidden');
                        calcularTotales();
                    });
                }

                // Validar Precio (número positivo)
                if (precioInput) {
                    precioInput.addEventListener('input', function() {
                        const valor = this.value;
                        const numero = parseFloat(valor);

                        // Verificar si es vacío o no es número
                        if (valor === '' || isNaN(numero)) {
                            this.classList.add('border-red-500', 'bg-red-50');
                            this.classList.remove('border-gray-300');
                            precioError.classList.remove('hidden');
                            return;
                        }

                        // Verificar si es positivo
                        if (numero <= 0) {
                            this.classList.add('border-red-500', 'bg-red-50');
                            this.classList.remove('border-gray-300');
                            precioError.classList.remove('hidden');
                            return;
                        }

                        // Si es válido
                        this.classList.remove('border-red-500', 'bg-red-50');
                        this.classList.add('border-gray-300');
                        precioError.classList.add('hidden');
                        calcularTotales();
                    });
                }
            });
        }

        // Validar que el descuento no sea mayor que el precio unitario
        function attachDiscountValidation() {
            document.querySelectorAll('.item-row').forEach(row => {
                const precioInput = row.querySelector('.precio');
                const descuentoInput = row.querySelector('.descuento');
                const errorMsg = row.querySelector('.descuento-error');

                function validateDiscount() {
                    const precio = parseFloat(precioInput.value) || 0;
                    const descuento = parseFloat(descuentoInput.value) || 0;

                    // Validación 1: Verificar que el descuento sea positivo
                    if (descuento < 0) {
                        descuentoInput.classList.add('border-red-500', 'bg-red-50');
                        descuentoInput.classList.remove('border-gray-300');
                        errorMsg.classList.remove('hidden');
                        errorMsg.textContent = '⚠️ El descuento debe ser un número positivo o cero';
                        return false;
                    }

                    // Validación 2: Verificar que no sea mayor que el precio
                    if (descuento > precio) {
                        // Descuento inválido
                        descuentoInput.classList.add('border-red-500', 'bg-red-50');
                        descuentoInput.classList.remove('border-gray-300');
                        errorMsg.classList.remove('hidden');
                        errorMsg.textContent = '⚠️ El descuento no puede ser mayor que el precio unitario';
                        return false;
                    } else {
                        // Descuento válido
                        descuentoInput.classList.remove('border-red-500', 'bg-red-50');
                        descuentoInput.classList.add('border-gray-300');
                        errorMsg.classList.add('hidden');
                        return true;
                    }
                }

                // Validar cuando cambia el precio o el descuento
                if (precioInput && descuentoInput) {
                    precioInput.addEventListener('input', validateDiscount);
                    descuentoInput.addEventListener('input', validateDiscount);
                }
            });
        }

        attachCalculateEvents();

        // ==========================================
        // Municipios dinámicos según departamento (CAT-13)
        // ==========================================
        const departamentoSelect = document.querySelector('select[name="receptor_departamento"]');
        const municipioSelect = document.getElementById('municipioSelect');
        const municipioHelp = document.getElementById('municipioHelp');

        departamentoSelect.addEventListener('change', function() {
            const departamento = this.value;

            // Limpiar el select de municipios
            municipioSelect.innerHTML = '<option value="">Seleccione un municipio</option>';

            if (departamento && catalogoMunicipios[departamento]) {
                const municipios = catalogoMunicipios[departamento];

                // Agregar los municipios correspondientes
                municipios.forEach(function(municipio) {
                    const option = document.createElement('option');
                    option.value = municipio.codigo;
                    option.textContent = municipio.nombre;
                    municipioSelect.appendChild(option);
                });

                municipioHelp.textContent = `${municipios.length} municipio(s) disponible(s)`;
                municipioHelp.className = 'text-xs text-green-600 mt-1 font-medium';
                municipioSelect.disabled = false;
            } else {
                municipioHelp.textContent = 'Seleccione un departamento para ver los municipios';
                municipioHelp.className = 'text-xs text-gray-500 mt-1';
                municipioSelect.disabled = true;
            }
        });

        // Deshabilitar municipio inicialmente
        municipioSelect.disabled = true;

        // Validación y formateo de documentos
        const tipoDocSelect = document.querySelector('select[name="receptor_tipo_documento"]');
        const numDocInput = document.querySelector('input[name="receptor_num_documento"]');
        const helpText = numDocInput.nextElementSibling;

        tipoDocSelect.addEventListener('change', function() {
            const tipo = this.value;
            numDocInput.value = '';

            switch(tipo) {
                case '13': // DUI - Persona natural sin IVA
                    numDocInput.placeholder = 'Ej: 12345678-9 o 123456789';
                    helpText.textContent = 'DUI: 9 dígitos (formato: ########-#) - Solo para personas naturales sin IVA';
                    helpText.className = 'text-xs text-blue-600 mt-1 font-medium';
                    break;
                case '36': // NIT - Contribuyente IVA
                    numDocInput.placeholder = 'Ej: 06142302881449';
                    helpText.textContent = 'NIT: 14 dígitos sin guiones - Para contribuyentes inscritos en IVA';
                    helpText.className = 'text-xs text-blue-600 mt-1 font-medium';
                    break;
                case '02': // Carnet de residente
                    numDocInput.placeholder = 'Número de carnet de residente';
                    helpText.textContent = 'Ingrese el número de carnet de residente';
                    helpText.className = 'text-xs text-gray-500 mt-1';
                    break;
                case '03': // Pasaporte
                    numDocInput.placeholder = 'Número de pasaporte';
                    helpText.textContent = 'Ingrese el número de pasaporte';
                    helpText.className = 'text-xs text-gray-500 mt-1';
                    break;
                case '37': // Otro
                    numDocInput.placeholder = 'Otro documento de identificación';
                    helpText.textContent = 'Ingrese el número de documento';
                    helpText.className = 'text-xs text-gray-500 mt-1';
                    break;
                default:
                    numDocInput.placeholder = 'Seleccione primero el tipo de documento';
                    helpText.textContent = 'Seleccione el tipo de documento';
                    helpText.className = 'text-xs text-gray-500 mt-1';
            }
        });

        // Formateo en tiempo real
        numDocInput.addEventListener('input', function() {
            const tipo = tipoDocSelect.value;
            let valor = this.value.replace(/[^0-9]/g, ''); // Solo números

            if (tipo === '13') { // DUI
                if (valor.length > 9) {
                    valor = valor.substring(0, 9);
                }
                // Mostrar formato visual mientras escribe
                if (valor.length >= 8) {
                    this.value = valor.substring(0, 8) + '-' + valor.substring(8);
                } else {
                    this.value = valor;
                }

                // Validación visual
                if (valor.length === 9) {
                    helpText.textContent = '✓ DUI válido';
                    helpText.className = 'text-xs text-green-600 mt-1 font-medium';
                } else if (valor.length > 0) {
                    helpText.textContent = `Faltan ${9 - valor.length} dígitos`;
                    helpText.className = 'text-xs text-orange-600 mt-1 font-medium';
                } else {
                    helpText.textContent = 'DUI: 9 dígitos (puede incluir o no el guión)';
                    helpText.className = 'text-xs text-blue-600 mt-1 font-medium';
                }
            } else if (tipo === '36') { // NIT
                if (valor.length > 14) {
                    valor = valor.substring(0, 14);
                }
                this.value = valor;

                // Validación visual
                if (valor.length === 14) {
                    helpText.textContent = '✓ NIT válido';
                    helpText.className = 'text-xs text-green-600 mt-1 font-medium';
                } else if (valor.length > 0) {
                    helpText.textContent = `Faltan ${14 - valor.length} dígitos`;
                    helpText.className = 'text-xs text-orange-600 mt-1 font-medium';
                } else {
                    helpText.textContent = 'NIT: 14 dígitos (puede incluir o no guiones)';
                    helpText.className = 'text-xs text-blue-600 mt-1 font-medium';
                }
            }
        });

        // Validación antes de enviar
        document.getElementById('facturaForm').addEventListener('submit', function(e) {
            const tipo = tipoDocSelect.value;
            const numDoc = numDocInput.value.replace(/[^0-9]/g, '');

            if (tipo === '13' && numDoc.length !== 9) {
                e.preventDefault();
                alert('El DUI debe tener exactamente 9 dígitos.\n\nActual: ' + numDoc.length + ' dígitos\nRequerido: 9 dígitos');
                numDocInput.focus();
                return false;
            }

            if (tipo === '36' && numDoc.length !== 14) {
                e.preventDefault();
                alert('El NIT debe tener exactamente 14 dígitos.\n\nActual: ' + numDoc.length + ' dígitos\nRequerido: 14 dígitos');
                numDocInput.focus();
                return false;
            }

            // Validar items de productos/servicios
            let erroresItems = [];

            document.querySelectorAll('.item-row').forEach((row, index) => {
                const tipoItem = row.querySelector('.tipo-select').value.trim();
                const codigo = row.querySelector('.codigo-item').value.trim();
                const descripcion = row.querySelector('.descripcion-item').value.trim();
                const cantidad = row.querySelector('.cantidad').value;
                const precio = row.querySelector('.precio').value;
                const descuento = parseFloat(row.querySelector('.descuento').value) || 0;

                let errorItem = `Item ${index + 1}:`;
                let tieneError = false;

                // Validar campos vacíos
                if (tipoItem === '') {
                    errorItem += '\n  - Debe seleccionar un tipo (Bien/Servicio)';
                    tieneError = true;
                }
                if (codigo === '') {
                    errorItem += '\n  - El código no puede estar vacío';
                    tieneError = true;
                }
                if (descripcion === '') {
                    errorItem += '\n  - La descripción no puede estar vacía';
                    tieneError = true;
                }

                // Validar cantidad (entero positivo)
                if (cantidad === '' || isNaN(cantidad)) {
                    errorItem += '\n  - La cantidad debe ser un número';
                    tieneError = true;
                } else if (parseFloat(cantidad) <= 0) {
                    errorItem += '\n  - La cantidad debe ser positiva';
                    tieneError = true;
                } else if (!Number.isInteger(parseFloat(cantidad))) {
                    errorItem += '\n  - La cantidad debe ser un número entero (sin decimales)';
                    tieneError = true;
                }

                // Validar precio (positivo)
                if (precio === '' || isNaN(precio)) {
                    errorItem += '\n  - El precio debe ser un número';
                    tieneError = true;
                } else if (parseFloat(precio) <= 0) {
                    errorItem += '\n  - El precio debe ser positivo';
                    tieneError = true;
                }

                // Validar descuento (positivo y no mayor que precio)
                if (descuento < 0) {
                    errorItem += '\n  - El descuento debe ser positivo o cero';
                    tieneError = true;
                } else if (parseFloat(precio) > 0 && descuento > parseFloat(precio)) {
                    errorItem += '\n  - El descuento no puede ser mayor que el precio unitario';
                    tieneError = true;
                }

                if (tieneError) {
                    erroresItems.push(errorItem);
                }
            });

            if (erroresItems.length > 0) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Errores en los Productos/Servicios',
                    html: '<pre style="text-align: left; font-size: 0.8rem;">' + erroresItems.join('\n\n') + '</pre>'
                });
                return false;
            }

            // Validar que al menos haya un item
            if (document.querySelectorAll('.item-row').length === 0) {
                e.preventDefault();
                Swal.fire('Error', 'Debe agregar al menos un producto o servicio', 'error');
                return false;
            }

            // Confirmación antes de enviar
            e.preventDefault();
            Swal.fire({
                title: '¿Estás seguro?',
                text: "Estás a punto de emitir una Factura de Sujeto Excluido. Verifica que todos los datos sean correctos.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Sí, emitir documento',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    this.submit();
                }
            });
        });

        // Función para formatear teléfono con guion 7777-7777
        function formatearTelefono(input) {
            // Eliminar todo lo que no sea número
            let valor = input.value.replace(/\D/g, '');

            // Limitar a 8 dígitos
            if (valor.length > 8) {
                valor = valor.substring(0, 8);
            }

            // Agregar el guion después del cuarto dígito
            if (valor.length > 4) {
                valor = valor.substring(0, 4) + '-' + valor.substring(4);
            }

            input.value = valor;
        }

        // ==========================================
        // Búsqueda de cliente por DUI/NIT
        // ==========================================
        const docInput = document.getElementById('receptor_num_documento');
        const busquedaLoader = document.getElementById('busquedaLoader');
        const resultadoBusqueda = document.getElementById('resultadoBusqueda');
        let timeoutBusqueda;

        if (docInput) {
            docInput.addEventListener('blur', function() {
                clearTimeout(timeoutBusqueda);
                const documento = this.value.trim();

                if (!documento || documento.length < 8) {
                    resultadoBusqueda.classList.add('hidden');
                    return;
                }

                busquedaCliente(documento);
            });
        }

        function busquedaCliente(documento) {
            busquedaLoader.style.display = 'block';
            resultadoBusqueda.classList.add('hidden');

            // Limpiar caracteres especiales del documento
            const documentoLimpio = documento.replace(/[^0-9]/g, '');

            // Validar que no esté vacío después de limpiar
            if (!documentoLimpio || documentoLimpio.length < 8) {
                busquedaLoader.style.display = 'none';
                resultadoBusqueda.textContent = '✗ Documento inválido';
                resultadoBusqueda.className = 'text-xs text-red-600 mt-1 font-medium';
                resultadoBusqueda.classList.remove('hidden');
                return;
            }

            // Consumir el endpoint de Motors González desde .env con autenticación
            fetch(`${motorApiUrl}/clientes/buscar/${documentoLimpio}`, {
                method: 'GET',
                headers: {
                    'Authorization': `Bearer ${authToken}`,
                    'Accept': 'application/json'
                }
            })
            .then(response => {
                // Verificar si la respuesta es válida
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                busquedaLoader.style.display = 'none';

                // La API puede devolver data directamente o dentro de data.data
                const cliente = data.data || data;

                // Verificar si hay datos del cliente
                if (cliente && cliente.nombre) {
                    // Rellenar nombre completo (nombre + apellido)
                    let nombreCompleto = cliente.nombre || '';
                    if (cliente.apellido) {
                        nombreCompleto += ' ' + cliente.apellido;
                    }
                    if (nombreCompleto) {
                        document.querySelector('input[name="receptor_nombre"]').value = nombreCompleto.toUpperCase();
                    }

                    // Rellenar teléfono
                    if (cliente.telefono) {
                        document.querySelector('input[name="receptor_telefono"]').value = cliente.telefono;
                    }

                    // Rellenar correo
                    if (cliente.email) {
                        document.querySelector('input[name="receptor_correo"]').value = cliente.email;
                    }

                    // Rellenar dirección
                    if (cliente.direccion) {
                        document.querySelector('textarea[name="receptor_complemento"]').value = cliente.direccion;
                    }

                    // Rellenar departamento si es posible
                    if (cliente.departamento) {
                        const depSelect = document.querySelector('select[name="receptor_departamento"]');
                        // Intentar mapear el código del departamento
                        for (let option of depSelect.options) {
                            if (option.textContent.toLowerCase().includes(cliente.departamento.toLowerCase())) {
                                depSelect.value = option.value;
                                // Disparar evento change para cargar municipios
                                depSelect.dispatchEvent(new Event('change'));
                                break;
                            }
                        }
                    }

                    resultadoBusqueda.textContent = '✓ Datos del cliente cargados correctamente';
                    resultadoBusqueda.className = 'text-xs text-green-600 mt-1 font-medium';
                    resultadoBusqueda.classList.remove('hidden');

                    // Limpiar mensaje después de 3 segundos
                    setTimeout(() => {
                        resultadoBusqueda.classList.add('hidden');
                    }, 3000);
                } else {
                    // Si no hay datos, mostrar el mensaje de error del servidor
                    const errorMsg = data.message || 'Cliente no encontrado';
                    resultadoBusqueda.textContent = '✗ ' + errorMsg;
                    resultadoBusqueda.className = 'text-xs text-orange-600 mt-1 font-medium';
                    resultadoBusqueda.classList.remove('hidden');
                }
            })
            .catch(error => {
                busquedaLoader.style.display = 'none';
                
                // Mostrar más detalles del error
                let errorMsg = 'Error al buscar cliente';
                if (error.message.includes('HTTP 401')) {
                    errorMsg = 'Token expirado o inválido';
                } else if (error.message.includes('HTTP 404')) {
                    errorMsg = 'Cliente no encontrado';
                } else if (error.message.includes('HTTP 500')) {
                    errorMsg = 'Error del servidor de Motors González';
                }
                
                resultadoBusqueda.textContent = '✗ ' + errorMsg;
                resultadoBusqueda.className = 'text-xs text-red-600 mt-1 font-medium';
                resultadoBusqueda.classList.remove('hidden');
            });
        }

        // ==========================================
        // Búsqueda de productos por código
        // ==========================================
        function setupProductSearch(codigoInput) {
            codigoInput.addEventListener('blur', function() {
                const codigo = this.value.trim();
                if (!codigo) {
                    return;
                }
                buscarProducto(codigo, this);
            });
        }

        function buscarProducto(codigo, inputElement) {
            const itemRow = inputElement.closest('.item-row');
            if (!itemRow) {
                return;
            }
            
            const indexAttr = inputElement.getAttribute('data-item-index');
            const loader = itemRow.querySelector('.loader-producto');
            const resultadoMsg = itemRow.querySelector('.resultado-producto');

            if (loader) loader.classList.remove('hidden');
            if (resultadoMsg) resultadoMsg.classList.add('hidden');

            fetch(`${motorApiUrl}/productos/buscar/${codigo}`, {
                method: 'GET',
                headers: {
                    'Authorization': `Bearer ${authToken}`,
                    'Accept': 'application/json'
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (loader) loader.classList.add('hidden');
                const producto = data.data || data;

                if (producto && producto.codigo) {
                    // Capturar producto_id si existe
                    if (producto.id || producto.producto_id) {
                        const productoId = producto.id || producto.producto_id;
                        const productoIdInput = itemRow.querySelector('.producto-id');
                        if (productoIdInput) {
                            productoIdInput.value = productoId;
                        }
                    }

                    inputElement.value = producto.codigo;
                    
                    const descInput = itemRow.querySelector('.descripcion-item');
                    if (descInput) {
                        descInput.value = producto.nombre_completo || producto.nombre || '';
                    }

                    const precioInput = itemRow.querySelector('.precio');
                    if (precioInput && producto.Precio_unitario) {
                        precioInput.value = producto.Precio_unitario;
                    }

                    calcularTotales();
                    
                    if (resultadoMsg) {
                        resultadoMsg.textContent = '✓ Producto encontrado';
                        resultadoMsg.className = 'resultado-producto text-xs mt-1 font-medium text-green-600';
                        resultadoMsg.classList.remove('hidden');
                        setTimeout(() => resultadoMsg.classList.add('hidden'), 3000);
                    }
                } else {
                    // NO LIMPIAR EL CAMPO, MOSTRAR MENSAJE TEMPORAL
                    if (resultadoMsg) {
                        resultadoMsg.textContent = '⚠️ Producto no encontrado (ingrese datos manualmente)';
                        resultadoMsg.className = 'resultado-producto text-xs mt-1 font-medium text-orange-600';
                        resultadoMsg.classList.remove('hidden');
                    }
                }
            })
            .catch(error => {
                if (loader) loader.classList.add('hidden');
                // NO LIMPIAR EL CAMPO, MOSTRAR ERROR
                if (resultadoMsg) {
                    resultadoMsg.textContent = '⚠️ Error al buscar producto';
                    resultadoMsg.className = 'resultado-producto text-xs mt-1 font-medium text-red-600';
                    resultadoMsg.classList.remove('hidden');
                }
            });
        }

        // Setup inicial
        const primerCodigoInput = document.querySelector('.codigo-item');
        const primerTipoSelect = document.querySelector('.tipo-item');
        
        setupProductSearch(primerCodigoInput);
        
        if (primerTipoSelect) {
            primerTipoSelect.addEventListener('change', function() {
                aplicarLogicaTipo(this, primerCodigoInput);
            });
        }
    </script>
</body>
</html>