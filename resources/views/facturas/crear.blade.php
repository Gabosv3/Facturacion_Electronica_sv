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

                    <!-- Datos del Receptor -->
                    <div class="mb-8">
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
                                <input type="text" name="receptor_num_documento" placeholder="DUI: 12345678-9 / NIT: 1234-567890-123-4" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500" required value="{{ old('receptor_num_documento') }}">
                                <p class="text-xs text-gray-500 mt-1">DUI: 8 dígitos-1 dígito / NIT: 14 dígitos con guiones</p>
                            </div>

                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Nombre Completo *</label>
                                <input type="text" name="receptor_nombre" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500" required value="{{ old('receptor_nombre') }}">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">NRC (opcional)</label>
                                <input type="text" name="receptor_nrc" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500" value="{{ old('receptor_nrc') }}">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Teléfono</label>
                                <input type="text" name="receptor_telefono" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500" value="{{ old('receptor_telefono') }}">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Código Actividad Económica</label>
                                <input type="text" name="receptor_cod_actividad" placeholder="Ej: 62010" maxlength="10" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500" value="{{ old('receptor_cod_actividad') }}">
                                <p class="text-xs text-gray-500 mt-1">Opcional</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Descripción Actividad *</label>
                                <input type="text" name="receptor_desc_actividad" placeholder="Ej: PROGRAMACIÓN INFORMÁTICA" minlength="5" maxlength="150" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500" required value="{{ old('receptor_desc_actividad') }}">
                                <p class="text-xs text-gray-500 mt-1">Mínimo 5 caracteres - Obligatorio</p>
                            </div>

                            <div class="md:col-span-2">
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

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Municipio *</label>
                                <select name="receptor_municipio" id="municipioSelect" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500" required>
                                    <option value="">Seleccione primero un departamento</option>
                                </select>
                                <p class="text-xs text-gray-500 mt-1" id="municipioHelp">Seleccione un departamento para ver los municipios</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Distrito *</label>
                                <input type="text" name="receptor_distrito" id="distritoInput" value="03" maxlength="2" pattern="[0-9]{2}" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500" required>
                                <p class="text-xs text-gray-500 mt-1">Código de distrito (2 dígitos). Por defecto: 03</p>
                                <p class="text-xs text-yellow-600 mt-1">⚠️ IMPORTANTE: El catálogo CAT-12 de distritos corresponde a los municipios ANTES de la consolidación. Usa el código que funcionaba anteriormente.</p>
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
                                <div class="grid grid-cols-1 md:grid-cols-6 gap-4">
                                    <div class="md:col-span-1">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Código *</label>
                                        <input type="text" name="items[0][codigo]" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500" required>
                                    </div>

                                    <div class="md:col-span-2">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Descripción *</label>
                                        <input type="text" name="items[0][descripcion]" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500" required>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Cantidad *</label>
                                        <input type="number" step="0.01" name="items[0][cantidad]" class="cantidad w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500" required min="0.01">
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

        // Agregar nuevo item
        document.getElementById('addItem').addEventListener('click', function() {
            const container = document.getElementById('itemsContainer');
            const newItem = document.createElement('div');
            newItem.className = 'item-row bg-gray-50 p-4 rounded-lg mb-4';
            newItem.innerHTML = `
                <div class="grid grid-cols-1 md:grid-cols-6 gap-4">
                    <div class="md:col-span-1">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Código *</label>
                        <input type="text" name="items[${itemCount}][codigo]" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500" required>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Descripción *</label>
                        <input type="text" name="items[${itemCount}][descripcion]" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Cantidad *</label>
                        <input type="number" step="0.01" name="items[${itemCount}][cantidad]" class="cantidad w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500" required min="0.01">
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
            itemCount++;
            attachCalculateEvents();
        });

        // Eliminar item
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('removeItem')) {
                e.target.closest('.item-row').remove();
                calcularTotales();
            }
        });

        // Calcular totales según fórmula del Ministerio de Hacienda
        function calcularTotales() {
            const precioIncluyeIva = document.getElementById('precioIncluyeIva').checked;
            let subtotal = 0;
            let totalIva = 0;
            
            document.querySelectorAll('.item-row').forEach(row => {
                const cantidad = parseFloat(row.querySelector('.cantidad').value) || 0;
                const precio = parseFloat(row.querySelector('.precio').value) || 0;
                const descuento = parseFloat(row.querySelector('.descuento').value) || 0;
                
                if (precioIncluyeIva) {
                    // Precio INCLUYE IVA - Aplicar fórmula del MH: [(total)/1.13*0.13]
                    const totalConIva = (cantidad * precio) - descuento;
                    const ventaGravada = totalConIva / 1.13;
                    const iva = ventaGravada * 0.13;
                    
                    subtotal += ventaGravada;
                    totalIva += iva;
                } else {
                    // Precio NO incluye IVA
                    const ventaGravada = (cantidad * precio) - descuento;
                    const iva = ventaGravada * 0.13;
                    
                    subtotal += ventaGravada;
                    totalIva += iva;
                }
            });
            
            const total = subtotal + totalIva;
            
            document.getElementById('subtotal').textContent = '$' + subtotal.toFixed(2);
            document.getElementById('iva').textContent = '$' + totalIva.toFixed(2);
            document.getElementById('total').textContent = '$' + total.toFixed(2);
        }

        function attachCalculateEvents() {
            document.querySelectorAll('.cantidad, .precio, .descuento').forEach(input => {
                input.removeEventListener('input', calcularTotales);
                input.addEventListener('input', calcularTotales);
            });
            
            document.getElementById('precioIncluyeIva').addEventListener('change', calcularTotales);
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
        });
    </script>
</body>
</html>