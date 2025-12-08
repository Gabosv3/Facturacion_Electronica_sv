<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Generar Factura - DTE El Salvador</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="{{ asset('js/catalogo-municipios.js') }}"></script>
</head>
<body class="bg-gray-100">
    @include('components.navbar')

    <div class="container mx-auto px-4 py-8">
        <div class="max-w-5xl mx-auto">
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h1 class="text-3xl font-bold text-gray-800 mb-6">Generar Factura Electrónica</h1>

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
                    <input type="hidden" name="tipo_dte" value="01">
                    <input type="hidden" name="cliente_id" id="clienteIdField" value="">
                    <input type="hidden" name="motor_clientes_ids" id="motorClientesIds" value="">

                    <!-- Checkbox Clientes Varios -->
                    <div class="mb-6 p-4 bg-yellow-50 border-2 border-yellow-300 rounded-lg">
                        <div class="flex items-center gap-3">
                            <input type="checkbox" id="clientesVarios" name="clientes_varios" value="1" class="w-5 h-5 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <label for="clientesVarios" class="text-lg font-semibold text-gray-800 cursor-pointer">
                                Factura de Clientes Varios
                            </label>
                        </div>
                        <p class="text-sm text-gray-600 mt-2 ml-8">
                            📌 Active esta opción para generar una factura sin datos específicos del cliente. Solo necesitará agregar los productos/servicios.
                        </p>
                    </div>

                    <!-- Datos del Receptor -->
                    <div class="mb-8" id="datosClienteSection">
                        <h2 class="text-xl font-semibold text-gray-700 mb-4 border-b pb-2">Datos del Cliente</h2>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de Documento *</label>
                                <select name="receptor_tipo_documento" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500" required>
                                    <option value="">Seleccione...</option>
                                    <option value="36">NIT - Registro de contribuyente IVA</option>
                                    <option value="13">DUI - Persona natural sin IVA</option>
                                    <option value="02">Carnet de residente</option>
                                    <option value="03">Pasaporte</option>
                                    <option value="37">Otro</option>
                                </select>
                                <p class="text-xs text-gray-500 mt-1">DUI solo para personas naturales NO inscritas en IVA</p>
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
                                <label class="block text-sm font-medium text-gray-700 mb-2">NRC (opcional)</label>
                                <input type="text" name="receptor_nrc" pattern="[0-9]*" inputmode="numeric" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500" value="{{ old('receptor_nrc') }}" oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Teléfono</label>
                                <input type="text" name="receptor_telefono" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500" value="{{ old('receptor_telefono') }}" placeholder="7777-7777" maxlength="9" oninput="formatearTelefono(this)">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Código Actividad Económica</label>
                                <input type="text" name="receptor_cod_actividad" placeholder="Ej: 62010" maxlength="10" pattern="[0-9]*" inputmode="numeric" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500" value="{{ old('receptor_cod_actividad') }}" oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                                <p class="text-xs text-gray-500 mt-1">Opcional</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Descripción Actividad</label>
                                <input type="text" name="receptor_desc_actividad" placeholder="Ej: PROGRAMACIÓN INFORMÁTICA" minlength="5" maxlength="150" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500" value="{{ old('receptor_desc_actividad') }}">
                                <p class="text-xs text-gray-500 mt-1">Opcional (obligatorio solo para DTE-03 CCF)</p>
                            </div>

                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Correo Electrónico</label>
                                <input type="email" name="receptor_correo" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500" value="{{ old('receptor_correo') }}">
                            </div>
                        </div>
                    </div>

                    <!-- Dirección del Receptor -->
                    <div class="mb-8" id="direccionClienteSection">
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
                            <div class="flex items-center gap-2">
                                <input type="checkbox" id="precioIncluyeIva" name="precio_incluye_iva" value="1" checked class="rounded">
                                <label for="precioIncluyeIva" class="text-sm text-gray-700">Precio incluye IVA</label>
                            </div>
                        </div>
                        
                        <div id="itemsContainer">
                            <div class="item-row bg-gray-50 p-4 rounded-lg mb-4">
                                <input type="hidden" name="items[0][producto_id]" class="producto-id" value="">
                                <div class="grid grid-cols-1 md:grid-cols-6 gap-4">
                                    <div class="md:col-span-1">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Código *</label>
                                        <div class="relative">
                                            <input type="text" name="items[0][codigo]" data-item-index="0" class="codigo-producto w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500" required>
                                            <div class="loader-producto hidden absolute right-3 top-3">
                                                <svg class="animate-spin h-5 w-5 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                            </div>
                                        </div>
                                        <p class="resultado-producto text-xs mt-1 hidden"></p>
                                    </div>

                                    <div class="md:col-span-2">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Descripción *</label>
                                        <input type="text" name="items[0][descripcion]" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500" required>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Cantidad *</label>
                                        <input type="number" step="1" name="items[0][cantidad]" class="cantidad w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500" required min="1">
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Precio Unit. *</label>
                                        <input type="number" step="0.01" name="items[0][precio_unitario]" class="precio w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500" required min="0">
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Descuento</label>
                                        <input type="number" step="0.01" name="items[0][monto_descuento]" value="0" class="descuento w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500" min="0">
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
                                    <option value="2">Crédito</option>
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
                            <div class="flex justify-between">
                                <span class="text-gray-600">Subtotal (sin IVA):</span>
                                <span class="font-semibold" id="subtotal">$0.00</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">IVA (13%):</span>
                                <span class="font-semibold" id="iva">$0.00</span>
                            </div>
                            <div class="flex justify-between text-xl font-bold text-blue-600 border-t pt-2">
                                <span>Total:</span>
                                <span id="total">$0.00</span>
                            </div>
                        </div>
                    </div>

                    <!-- Botones -->
                    <div class="flex gap-4">
                        <button type="submit" id="submitBtn" class="flex-1 bg-green-600 hover:bg-green-700 text-white font-semibold px-6 py-3 rounded-lg">
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

        // ==========================================
        // Manejo de Clientes Varios
        // ==========================================
        const clientesVariosCheckbox = document.getElementById('clientesVarios');
        const datosClienteSection = document.getElementById('datosClienteSection');
        const direccionClienteSection = document.getElementById('direccionClienteSection');

        // Función para alternar secciones de cliente
        function toggleClientesVarios() {
            const esClientesVarios = clientesVariosCheckbox.checked;

            if (esClientesVarios) {
                // Ocultar secciones de cliente
                datosClienteSection.style.display = 'none';
                direccionClienteSection.style.display = 'none';

                // Deshabilitar validación required en campos de cliente
                datosClienteSection.querySelectorAll('input[required], select[required], textarea[required]').forEach(field => {
                    field.removeAttribute('required');
                    field.disabled = true;
                });
                direccionClienteSection.querySelectorAll('input[required], select[required], textarea[required]').forEach(field => {
                    field.removeAttribute('required');
                    field.disabled = true;
                });

                // Limpiar valores de los campos
                datosClienteSection.querySelectorAll('input, select, textarea').forEach(field => {
                    if (field.name !== 'receptor_nombre') {
                        field.value = '';
                    }
                });
                direccionClienteSection.querySelectorAll('input, select, textarea').forEach(field => {
                    field.value = '';
                });

            } else {
                // Mostrar secciones de cliente
                datosClienteSection.style.display = 'block';
                direccionClienteSection.style.display = 'block';

                // Re-habilitar campos y validación required
                datosClienteSection.querySelectorAll('input, select').forEach(field => {
                    field.disabled = false;
                    // Restaurar required en campos obligatorios
                    if (field.name === 'receptor_tipo_documento' ||
                        field.name === 'receptor_num_documento' ||
                        field.name === 'receptor_nombre') {
                        field.setAttribute('required', 'required');
                    }
                });
                direccionClienteSection.querySelectorAll('input, select, textarea').forEach(field => {
                    field.disabled = false;
                    if (field.name === 'receptor_departamento' ||
                        field.name === 'receptor_municipio' ||
                        field.name === 'receptor_complemento') {
                        field.setAttribute('required', 'required');
                    }
                });
            }
        }

        // Event listener para el checkbox
        clientesVariosCheckbox.addEventListener('change', toggleClientesVarios);

        // Estado inicial
        toggleClientesVarios();

        // Agregar nuevo item
        document.getElementById('addItem').addEventListener('click', function() {
            const container = document.getElementById('itemsContainer');
            const newItem = document.createElement('div');
            newItem.className = 'item-row bg-gray-50 p-4 rounded-lg mb-4';
            newItem.innerHTML = `
                <input type="hidden" name="items[${itemCount}][producto_id]" class="producto-id" value="">
                <div class="grid grid-cols-1 md:grid-cols-6 gap-4">
                    <div class="md:col-span-1">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Código *</label>
                        <div class="relative">
                            <input type="text" name="items[${itemCount}][codigo]" data-item-index="${itemCount}" class="codigo-producto w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500" required>
                            <div class="loader-producto hidden absolute right-3 top-3">
                                <svg class="animate-spin h-5 w-5 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </div>
                        </div>
                        <p class="resultado-producto text-xs mt-1 hidden"></p>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Descripción *</label>
                        <input type="text" name="items[${itemCount}][descripcion]" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Cantidad *</label>
                        <input type="number" step="1" name="items[${itemCount}][cantidad]" class="cantidad w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500" required min="1">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Precio Unit. *</label>
                        <input type="number" step="0.01" name="items[${itemCount}][precio_unitario]" class="precio w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500" required min="0">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Descuento</label>
                        <input type="number" step="0.01" name="items[${itemCount}][monto_descuento]" value="0" class="descuento w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500" min="0">
                    </div>
                </div>
                <button type="button" class="removeItem mt-2 text-red-500 hover:text-red-700 text-sm">Eliminar</button>
            `;
            container.appendChild(newItem);
            
            const newCodigoInput = newItem.querySelector('.codigo-producto');
            setupProductSearch(newCodigoInput);
            
            itemCount++;
            attachCalculateEvents();
            verificarEstadoFormulario();
        });

        // Eliminar item
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('removeItem')) {
                e.target.closest('.item-row').remove();
                calcularTotales();
                verificarEstadoFormulario();
            }
        });

        // Calcular totales según fórmula del Ministerio de Hacienda
        // DTE-01 ahora usa 13% de IVA (igual que DTE-03)
        function calcularTotales() {
            const precioIncluyeIva = document.getElementById('precioIncluyeIva').checked;
            const tasaIva = 0.13; // DTE-01 Factura = 13%

            if (precioIncluyeIva) {
                // PRECIO INCLUYE IVA:
                // - Subtotal = precio CON IVA (lo que ingresaste)
                // - IVA = extraído del precio (informativo)
                // - Total = Subtotal (NO sumar IVA otra vez)
                let totalConIva = 0;

                document.querySelectorAll('.item-row').forEach(row => {
                    const cantidad = parseFloat(row.querySelector('.cantidad').value) || 0;
                    const precio = parseFloat(row.querySelector('.precio').value) || 0;
                    const descuento = parseFloat(row.querySelector('.descuento').value) || 0;
                    totalConIva += (cantidad * precio) - descuento;
                });

                // Subtotal = precio CON IVA
                const subtotal = totalConIva;
                // IVA = extraído (informativo)
                const totalSinIva = totalConIva / (1 + tasaIva);
                const totalIva = totalConIva - totalSinIva;
                // Total = Subtotal (NO sumar IVA)
                const total = subtotal;

                document.getElementById('subtotal').textContent = '$' + subtotal.toFixed(2);
                document.getElementById('iva').textContent = '$' + totalIva.toFixed(2);
                document.getElementById('total').textContent = '$' + total.toFixed(2);
            } else {
                // Precio NO incluye IVA - calcular normalmente
                let subtotal = 0;

                document.querySelectorAll('.item-row').forEach(row => {
                    const cantidad = parseFloat(row.querySelector('.cantidad').value) || 0;
                    const precio = parseFloat(row.querySelector('.precio').value) || 0;
                    const descuento = parseFloat(row.querySelector('.descuento').value) || 0;
                    const ventaGravada = (cantidad * precio) - descuento;
                    subtotal += ventaGravada;
                });

                const totalIva = subtotal * tasaIva;
                const total = subtotal + totalIva;

                document.getElementById('subtotal').textContent = '$' + subtotal.toFixed(2);
                document.getElementById('iva').textContent = '$' + totalIva.toFixed(2);
                document.getElementById('total').textContent = '$' + total.toFixed(2);
            }
        }

        function attachCalculateEvents() {
            document.querySelectorAll('.cantidad, .precio, .descuento').forEach(input => {
                input.removeEventListener('input', calcularTotales);
                input.addEventListener('input', calcularTotales);
            });
            
            document.querySelectorAll('.cantidad').forEach(input => {
                input.removeEventListener('change', validarCantidad);
                input.addEventListener('change', validarCantidad);
            });
            
            document.getElementById('precioIncluyeIva').addEventListener('change', calcularTotales);
            verificarEstadoFormulario();
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
        
        // Obtener token de autenticación del servidor
        const authToken = @json(session('auth_token'));

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
        });

        // Función para formatear teléfono
        function formatearTelefono(input) {
            // Obtener solo números
            let valor = input.value.replace(/\D/g, '');

            // Limitar a 8 dígitos
            if (valor.length > 8) {
                valor = valor.substring(0, 8);
            }

            // Aplicar formato 7777-7777
            if (valor.length > 4) {
                valor = valor.substring(0, 4) + '-' + valor.substring(4);
            }

            // Actualizar el valor del input
            input.value = valor;
        }

        // Búsqueda de cliente por DUI/NIT
        const docInput = document.getElementById('receptor_num_documento');
        const busquedaLoader = document.getElementById('busquedaLoader');
        const resultadoBusqueda = document.getElementById('resultadoBusqueda');
        let timeoutBusqueda;

        docInput.addEventListener('blur', function() {
            clearTimeout(timeoutBusqueda);
            const documento = this.value.trim();

            if (!documento || documento.length < 8) {
                resultadoBusqueda.classList.add('hidden');
                return;
            }

            busquedaCliente(documento);
        });

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

            // Consumir directamente el endpoint de Motors González con autenticación
            fetch(`https://motorsgonzalez.com/api/clientes/buscar/${documentoLimpio}`, {
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
                    // Capturar cliente_id si existe
                    if (cliente.id || cliente.cliente_id) {
                        const clienteId = cliente.id || cliente.cliente_id;
                        document.getElementById('clienteIdField').value = clienteId;
                    }

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

                    // Capturar NRC si existe en la API
                    if (cliente.nrc) {
                        document.querySelector('input[name="receptor_nrc"]').value = cliente.nrc;
                    }

                    // Capturar categoría económica si existe en la API
                    if (cliente.categoria_economica) {
                        // Si es un objeto con codigo y descripcion
                        if (typeof cliente.categoria_economica === 'object') {
                            if (cliente.categoria_economica.codigo) {
                                document.querySelector('input[name="receptor_cod_actividad"]').value = cliente.categoria_economica.codigo;
                            }
                            if (cliente.categoria_economica.descripcion) {
                                document.querySelector('input[name="receptor_desc_actividad"]').value = cliente.categoria_economica.descripcion;
                            }
                        }
                        // Si es solo un string (código)
                        else if (typeof cliente.categoria_economica === 'string') {
                            document.querySelector('input[name="receptor_cod_actividad"]').value = cliente.categoria_economica;
                        }
                    }

                    // Rellenar departamento si es posible
                    if (cliente.departamento) {
                        const depSelect = document.querySelector('select[name="receptor_departamento"]');
                        // Intentar mapear el código del departamento
                        for (let option of depSelect.options) {
                            if (option.textContent.toLowerCase().includes(cliente.departamento.toLowerCase())) {
                                depSelect.value = option.value;
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
        // Validación de cantidad vs stock
        // ==========================================
        function validarCantidad(event) {
            const cantidadInput = event.target;
            const cantidad = parseFloat(cantidadInput.value) || 0;
            const stock = parseFloat(cantidadInput.getAttribute('data-stock')) || 0;
            const itemRow = cantidadInput.closest('.item-row');
            
            // Buscar TODOS los mensajes de error en el item y limpiarlos
            const mensajesError = itemRow.querySelectorAll('.mensaje-stock-error');
            mensajesError.forEach(msg => {
                msg.textContent = '';
                msg.classList.add('hidden');
            });

            if (cantidad > stock && stock > 0) {
                cantidadInput.classList.add('border-red-500', 'border-2');
                cantidadInput.value = stock;
                itemRow.classList.add('error-stock');
                
                // Crear y mostrar mensaje
                let mensajeError = itemRow.querySelector('.mensaje-stock-error');
                if (!mensajeError) {
                    mensajeError = document.createElement('p');
                    mensajeError.className = 'mensaje-stock-error text-xs mt-1 font-medium';
                    cantidadInput.closest('div').parentElement.appendChild(mensajeError);
                }
                mensajeError.textContent = `⚠️ Stock insuficiente. Disponible: ${stock}`;
                mensajeError.className = 'text-xs mt-1 font-medium text-red-600';
                mensajeError.classList.remove('hidden');
            } else if (cantidad > 0) {
                cantidadInput.classList.remove('border-red-500', 'border-2');
                itemRow.classList.remove('error-stock');
            }

            verificarEstadoFormulario();
        }

        function verificarEstadoFormulario() {
            const submitBtn = document.getElementById('submitBtn');
            const itemsConError = document.querySelectorAll('.item-row.error-stock');
            
            if (itemsConError.length > 0) {
                submitBtn.disabled = true;
                submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
                submitBtn.title = 'Hay items con stock insuficiente';
            } else {
                submitBtn.disabled = false;
                submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                submitBtn.title = '';
            }
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
            const authToken = '{{ session("auth_token") }}';

            fetch(`https://motorsgonzalez.com/api/productos/buscar/${codigo}`, {
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
                    
                    const descInput = itemRow.querySelector('input[name="items[' + indexAttr + '][descripcion]"]');
                    if (descInput) {
                        descInput.value = producto.nombre_completo || producto.nombre || '';
                    }

                    const precioInput = itemRow.querySelector('input[name="items[' + indexAttr + '][precio_unitario]"]');
                    if (precioInput && producto.Precio_unitario) {
                        precioInput.value = producto.Precio_unitario;
                    }

                    const cantidadInput = itemRow.querySelector('input[name="items[' + indexAttr + '][cantidad]"]');
                    if (cantidadInput) {
                        const stockDisponible = producto.stock_actual || 0;
                        cantidadInput.setAttribute('data-stock', stockDisponible);
                        cantidadInput.setAttribute('max', stockDisponible);
                        cantidadInput.value = '';
                        cantidadInput.removeEventListener('change', validarCantidad);
                        cantidadInput.addEventListener('change', validarCantidad);
                    }

                    calcularTotales();
                } else {
                    // Limpiar el campo si no encuentra el producto
                    inputElement.value = '';
                    inputElement.focus();
                }
            })
            .catch(error => {
                // Limpiar el campo en caso de error
                inputElement.value = '';
                inputElement.focus();
            });
        }

        setupProductSearch(document.querySelector('.codigo-producto'));

        // Función para recolectar producto_ids antes de enviar
        function recolectarProductoIds() {
            const productos = [];
            document.querySelectorAll('.item-row').forEach(row => {
                const productoIdInput = row.querySelector('.producto-id');
                const codigoInput = row.querySelector('.codigo-producto');
                const cantidadInput = row.querySelector('.cantidad');
                const precioInput = row.querySelector('.precio');
                
                if (productoIdInput && codigoInput.value && cantidadInput.value && precioInput && precioInput.value) {
                    productos.push({
                        producto_id: productoIdInput.value || 0,
                        codigo: codigoInput.value,
                        cantidad: cantidadInput.value,
                        precio_unitario: precioInput.value
                    });
                }
            });
            return productos;
        }

        // Agregar listener al submit del formulario - ANTES de cualquier otra cosa
        document.getElementById('facturaForm').addEventListener('submit', function(e) {
            // Recolectar producto_ids INMEDIATAMENTE
            const productos = recolectarProductoIds();
            
            if (productos.length > 0) {
                const jsonProductos = JSON.stringify(productos);
                document.getElementById('motorClientesIds').value = jsonProductos;
            } else {
            }
        }, true); // Usar captura en lugar de burbuja
    </script>
</body>
</html>