<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Comprobante de Crédito Fiscal - DTE El Salvador</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="{{ asset('js/catalogo-municipios.js') }}"></script>
</head>
<body class="bg-slate-50">
    @include('components.navbar')

    <div class="container mx-auto px-4 py-8">
        <div class="max-w-5xl mx-auto">
            <div class="bg-white rounded-xl shadow-[0_1px_3px_0_rgb(0_0_0/0.06),0_4px_12px_-2px_rgb(0_0_0/0.06)] border border-slate-100 p-6">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h1 class="text-3xl font-bold text-slate-800">Comprobante de Crédito Fiscal (CCF)</h1>
                        <p class="text-sm text-slate-500 mt-2">DTE-03 - Solo para contribuyentes con NIT registrado en IVA</p>
                    </div>
                    <div class="bg-blue-100 text-blue-800 px-4 py-2 rounded-lg font-semibold">
                        DTE-03
                    </div>
                </div>

                <!-- Alerta informativa -->
                <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-yellow-700">
                                <strong>Importante:</strong> El CCF solo puede emitirse a contribuyentes registrados en IVA.
                                <br>• El receptor DEBE tener NIT (no DUI)
                                <br>• El NRC es OBLIGATORIO
                                <br>• La actividad económica es OBLIGATORIA
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
                    <div class="bg-amber-50 border border-amber-200 text-amber-700 px-4 py-3 rounded-xl mb-4 text-sm">
                        {{ session('warning') }}
                    </div>
                @endif

                <form action="{{ route('facturas.store') }}" method="POST" id="facturaForm">
                    @csrf
                    <input type="hidden" name="tipo_dte" value="03">
                    <input type="hidden" name="cliente_id" id="clienteIdField" value="">
                    <input type="hidden" name="motor_clientes_ids" id="motorClientesIds" value="">

                    <!-- Datos del Receptor -->
                    <div class="mb-8">
                        <h2 class="text-sm font-bold text-slate-700 mb-4 pb-2 border-b border-slate-100 uppercase tracking-wide">Datos del Cliente (Contribuyente)</h2>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-2">Tipo de Documento *</label>
                                <select name="receptor_tipo_documento" class="w-full border border-slate-200 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 bg-slate-50" required readonly>
                                    <option value="36" selected>NIT - Registro de contribuyente IVA</option>
                                </select>
                                <p class="text-xs text-blue-600 mt-1 font-medium">Solo NIT para CCF (crédito fiscal)</p>
                            </div>

                            <div>
                                <div class="flex justify-between items-center mb-2">
                                    <label class="block text-sm font-medium text-slate-700" id="labelNit">NIT (14 dígitos) *</label>
                                    <div class="flex items-center gap-2 bg-blue-50 px-2 py-1 rounded border border-blue-100">
                                        <input type="checkbox" id="nitHomologado" class="rounded text-blue-600 focus:ring-blue-500 h-4 w-4">
                                        <label for="nitHomologado" class="text-xs text-blue-800 cursor-pointer select-none font-medium">NIT Homologado (DUI)</label>
                                    </div>
                                </div>
                                <div class="relative">
                                    <input type="text" id="receptor_num_documento" name="receptor_num_documento" placeholder="06142302881449" class="w-full border border-slate-200 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500" required value="{{ old('receptor_num_documento') }}">
                                    <div id="busquedaLoader" style="display:none;" class="absolute right-3 top-3">
                                        <svg class="animate-spin h-5 w-5 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                    </div>
                                </div>
                                <p class="text-xs text-slate-500 mt-1" id="nitHelp">NIT: 14 dígitos sin guiones</p>
                                <p id="resultadoBusqueda" class="text-xs mt-1 hidden"></p>
                            </div>

                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-slate-700 mb-2">Razón Social / Nombre Completo *</label>
                                <input type="text" name="receptor_nombre" class="w-full border border-slate-200 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500" required value="{{ old('receptor_nombre') }}" style="text-transform: uppercase;" oninput="this.value = this.value.toUpperCase()">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-2">NRC (Obligatorio para CCF) *</label>
                                <input type="text" name="receptor_nrc" pattern="[0-9]*" inputmode="numeric" class="w-full border border-slate-200 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500" required value="{{ old('receptor_nrc') }}" oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                                <p class="text-xs text-red-600 mt-1 font-medium">⚠️ Campo obligatorio para CCF</p>
                            </div>

                            <!-- Gran Contribuyente -->
                            <div class="md:col-span-2">
                                <input type="hidden" name="gran_contribuyente" value="0">
                                <div class="flex items-start gap-4 bg-orange-50 border border-orange-200 rounded-lg p-4">
                                    <div class="flex items-center gap-2 mt-0.5">
                                        <input type="checkbox" id="granContribuyente" name="gran_contribuyente" value="1" class="rounded w-5 h-5 text-orange-600" {{ old('gran_contribuyente') ? 'checked' : '' }}>
                                        <label for="granContribuyente" class="text-sm font-semibold text-orange-800 cursor-pointer select-none">
                                            Gran Contribuyente
                                        </label>
                                    </div>
                                    <div class="text-xs text-orange-700">
                                        <p>Cuando el receptor es <strong>Gran Contribuyente</strong> (clasificado por el MH), retiene el <strong>1% de la venta gravada</strong> directamente al fisco.</p>
                                        <p class="mt-1">Reduce el monto que recibes: <code class="bg-orange-100 px-1 rounded">Total a pagar = Total − Retención 1%</code></p>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-2">Teléfono</label>
                                <input type="text" name="receptor_telefono" class="w-full border border-slate-200 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500" value="{{ old('receptor_telefono') }}" placeholder="7777-7777" maxlength="9" oninput="formatearTelefono(this)">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-2">Código Actividad Económica *</label>
                                <input type="text" name="receptor_cod_actividad" placeholder="Ej: 62010" maxlength="10" pattern="[0-9]*" inputmode="numeric" class="w-full border border-slate-200 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500" required value="{{ old('receptor_cod_actividad') }}" oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                                <p class="text-xs text-red-600 mt-1 font-medium">⚠️ Campo obligatorio para CCF</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-2">Descripción Actividad *</label>
                                <input type="text" name="receptor_desc_actividad" placeholder="Ej: PROGRAMACIÓN INFORMÁTICA" minlength="5" maxlength="150" class="w-full border border-slate-200 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500" required value="{{ old('receptor_desc_actividad') }}">
                                <p class="text-xs text-slate-500 mt-1">Mínimo 5 caracteres</p>
                            </div>

                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-slate-700 mb-2">Correo Electrónico</label>
                                <input type="email" name="receptor_correo" class="w-full border border-slate-200 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500" value="{{ old('receptor_correo') }}">
                            </div>
                        </div>
                    </div>

                    <!-- Dirección del Receptor -->
                    <div class="mb-8">
                        <h2 class="text-sm font-bold text-slate-700 mb-4 pb-2 border-b border-slate-100 uppercase tracking-wide">Dirección del Cliente</h2>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-2">Departamento *</label>
                                <select name="receptor_departamento" class="w-full border border-slate-200 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500" required>
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
                                <label class="block text-sm font-medium text-slate-700 mb-2">Municipio *</label>
                                <select name="receptor_municipio" id="municipioSelect" class="w-full border border-slate-200 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500" required>
                                    <option value="">Seleccione primero un departamento</option>
                                </select>
                                <p class="text-xs text-slate-500 mt-1" id="municipioHelp">Seleccione un departamento para ver los municipios</p>
                            </div>

                            <div class="md:col-span-3">
                                <label class="block text-sm font-medium text-slate-700 mb-2">Dirección Completa *</label>
                                <textarea name="receptor_complemento" rows="2" class="w-full border border-slate-200 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500" required>{{ old('receptor_complemento') }}</textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Items de la Factura -->
                    <div class="mb-8">
                        <div class="flex justify-between items-center mb-4">
                            <h2 class="text-xl font-semibold text-slate-700">Productos/Servicios</h2>
                            <div class="flex items-center gap-2">
                                <input type="checkbox" id="precioIncluyeIva" name="precio_incluye_iva" value="1" checked class="rounded">
                                <label for="precioIncluyeIva" class="text-sm text-slate-700">Precio incluye IVA</label>
                            </div>
                        </div>

                        <div id="itemsContainer">
                            <div class="item-row bg-slate-50 border border-slate-100 p-4 rounded-xl mb-4">
                                <input type="hidden" name="items[0][producto_id]" class="producto-id" value="">
                                <div class="grid grid-cols-1 md:grid-cols-7 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 mb-2">Tipo *</label>
                                        <select name="items[0][tipo_item]" class="tipo-item w-full border border-slate-200 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500" required>
                                            <option value="">Seleccionar</option>
                                            <option value="1">Bien</option>
                                            <option value="2">Servicio</option>
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 mb-2">Código *</label>
                                        <div class="relative">
                                            <input type="text" name="items[0][codigo]" data-item-index="0" class="codigo-producto w-full border border-slate-200 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500" required>
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
                                        <label class="block text-sm font-medium text-slate-700 mb-2">Descripción *</label>
                                        <input type="text" name="items[0][descripcion]" class="w-full border border-slate-200 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500" required>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 mb-2">Cantidad *</label>
                                        <input type="number" step="1" name="items[0][cantidad]" class="cantidad w-full border border-slate-200 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500" required min="1">
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 mb-2">Precio Unit. *</label>
                                        <input type="number" step="0.01" name="items[0][precio_unitario]" class="precio w-full border border-slate-200 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500" required min="0">
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 mb-2">Descuento</label>
                                        <input type="number" step="0.01" name="items[0][monto_descuento]" value="0" class="descuento w-full border border-slate-200 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500" min="0">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <button type="button" id="addItem" class="flex items-center gap-1.5 bg-white hover:bg-slate-50 text-blue-600 font-medium text-sm px-4 py-2 rounded-lg border border-blue-200 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            Agregar Item
                        </button>
                    </div>

                    <!-- Resumen y Condiciones -->
                    <div class="mb-8">
                        <h2 class="text-sm font-bold text-slate-700 mb-4 pb-2 border-b border-slate-100 uppercase tracking-wide">Condiciones de Pago</h2>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-2">Condición *</label>
                                <select name="condicion_operacion" class="w-full border border-slate-200 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500" required>
                                    <option value="1">Contado</option>
                                    <!-- <option value="2">Crédito</option> -->
                                    <option value="3">Otro</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-2">Forma de Pago *</label>
                                <select name="forma_pago" class="w-full border border-slate-200 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500" required>
                                    <option value="01">Efectivo</option>
                                    <option value="02">Cheque</option>
                                    <option value="03">Transferencia</option>
                                    <option value="04">Tarjeta Crédito</option>
                                    <option value="05">Tarjeta Débito</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-2">Referencia de Pago</label>
                                <input type="text" name="referencia_pago" class="w-full border border-slate-200 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500" value="{{ old('referencia_pago') }}">
                            </div>

                            <div class="md:col-span-3">
                                <label class="block text-sm font-medium text-slate-700 mb-2">Observaciones</label>
                                <textarea name="observaciones" rows="2" class="w-full border border-slate-200 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">{{ old('observaciones') }}</textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Resumen de Totales -->
                    <div class="bg-slate-50 border border-slate-100 p-5 rounded-xl mb-6">
                        <h3 class="text-lg font-semibold text-slate-700 mb-4">Resumen del CCF</h3>
                        <div class="space-y-2">
                            <div class="flex justify-between">
                                <span class="text-slate-500">Subtotal (sin IVA):</span>
                                <span class="font-semibold" id="subtotal">$0.00</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-500">IVA (13%) - Crédito Fiscal:</span>
                                <span class="font-semibold text-green-600" id="iva">$0.00</span>
                            </div>
                            <div class="flex justify-between text-base border-t pt-2">
                                <span class="text-slate-500">Monto Total Operación:</span>
                                <span class="font-semibold" id="montoTotal">$0.00</span>
                            </div>
                            <div class="flex justify-between" id="ivaRete1Row" style="display:none">
                                <span class="text-orange-700 font-medium">Retención IVA 1% (Gran Contribuyente):</span>
                                <span class="font-semibold text-orange-600" id="ivaRete1">-$0.00</span>
                            </div>
                            <div class="flex justify-between text-xl font-bold text-blue-600 border-t pt-2">
                                <span>Total a Pagar:</span>
                                <span id="total">$0.00</span>
                            </div>
                        </div>
                    </div>

                    <!-- Botones -->
                    <div class="flex gap-4">
                        <button type="submit" id="submitBtn" class="flex-1 bg-green-600 hover:bg-green-700 text-white font-semibold px-6 py-3 rounded-lg">
                            Generar y Firmar CCF
                        </button>
                        <a href="{{ route('facturas.index') }}" class="bg-slate-500 hover:bg-gray-600 text-white font-semibold px-6 py-3 rounded-lg">
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

        // Agregar nuevo item
        document.getElementById('addItem').addEventListener('click', function() {
            const container = document.getElementById('itemsContainer');
            const newItem = document.createElement('div');
            newItem.className = 'item-row bg-slate-50 border border-slate-100 p-4 rounded-xl mb-4';
            
            newItem.innerHTML = `
                <input type="hidden" name="items[${itemCount}][producto_id]" class="producto-id" value="">
                <div class="grid grid-cols-1 md:grid-cols-7 gap-4\">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Tipo *</label>
                        <select name="items[${itemCount}][tipo_item]" class="tipo-item w-full border border-slate-200 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500" data-item-index="${itemCount}" required>
                            <option value="">Seleccionar</option>
                            <option value="1">Bien</option>
                            <option value="2">Servicio</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Código *</label>
                        <div class="relative">
                            <input type="text" name="items[${itemCount}][codigo]" data-item-index="${itemCount}" class="codigo-producto w-full border border-slate-200 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500" required>
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
                        <label class="block text-sm font-medium text-slate-700 mb-2">Descripción *</label>
                        <input type="text" name="items[${itemCount}][descripcion]" class="w-full border border-slate-200 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Cantidad *</label>
                        <input type="number" step="1" name="items[${itemCount}][cantidad]" class="cantidad w-full border border-slate-200 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500" required min="1">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Precio Unit. *</label>
                        <input type="number" step="0.01" name="items[${itemCount}][precio_unitario]" class="precio w-full border border-slate-200 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500" required min="0">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Descuento</label>
                        <input type="number" step="0.01" name="items[${itemCount}][monto_descuento]" value="0" class="descuento w-full border border-slate-200 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500" min="0">
                    </div>
                </div>
                <button type="button" class="removeItem mt-2 text-red-500 hover:text-red-700 text-sm">Eliminar</button>
            `;
            container.appendChild(newItem);
            
            const tipoItemSelect = newItem.querySelector('.tipo-item');
            const codigoInput = newItem.querySelector('.codigo-producto');
            
            // Listener para cambios en tipo_item
            tipoItemSelect.addEventListener('change', function() {
                aplicarLogicaTipo(this, codigoInput);
            });
            
            // Si es edición inicial, setup búsqueda
            setupProductSearch(codigoInput);
            
            itemCount++;
            attachCalculateEvents();
            attachItemValidation();  // AGREGAR VALIDACIÓN AL NUEVO ITEM
            verificarEstadoFormulario();
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
                codigoInput.classList.add('bg-slate-200');
                codigoInput.style.cursor = 'not-allowed';
                
                // Remover evento de búsqueda
                codigoInput.removeEventListener('blur', function() {});
            } else if (tipo === '1') {
                // BIEN: código manual con búsqueda
                codigoInput.value = '';
                codigoInput.readOnly = false;
                codigoInput.classList.remove('bg-slate-200');
                codigoInput.style.cursor = 'auto';
                
                // Re-agregar búsqueda si no está
                setupProductSearch(codigoInput);
            }
        }

        // Eliminar item
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('removeItem')) {
                e.target.closest('.item-row').remove();
                calcularTotales();
                verificarEstadoFormulario();
            }
        });

        // Calcular totales según fórmula del Ministerio de Hacienda
        function calcularTotales() {
            const precioIncluyeIva = document.getElementById('precioIncluyeIva').checked;
            const esGranContribuyente = document.getElementById('granContribuyente').checked;
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

            const montoTotal = subtotal + totalIva;
            const ivaRete1 = esGranContribuyente ? Math.round(subtotal * 0.01 * 100) / 100 : 0;
            const totalPagar = montoTotal - ivaRete1;

            document.getElementById('subtotal').textContent = '$' + subtotal.toFixed(2);
            document.getElementById('iva').textContent = '$' + totalIva.toFixed(2);
            document.getElementById('montoTotal').textContent = '$' + montoTotal.toFixed(2);
            document.getElementById('ivaRete1').textContent = '-$' + ivaRete1.toFixed(2);
            document.getElementById('total').textContent = '$' + totalPagar.toFixed(2);
            document.getElementById('ivaRete1Row').style.display = esGranContribuyente ? 'flex' : 'none';
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

            document.querySelectorAll('.descuento').forEach(input => {
                input.removeEventListener('change', validarDescuento);
                input.addEventListener('change', validarDescuento);
                input.removeEventListener('blur', validarDescuento);
                input.addEventListener('blur', validarDescuento);
            });

            document.getElementById('precioIncluyeIva').addEventListener('change', calcularTotales);
            document.getElementById('granContribuyente').addEventListener('change', calcularTotales);
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
                municipioHelp.className = 'text-xs text-slate-500 mt-1';
                municipioSelect.disabled = true;
            }
        });

        // Deshabilitar municipio inicialmente
        municipioSelect.disabled = true;

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

        // Validación de NIT / DUI Homologado
        const numDocInput = document.querySelector('input[name="receptor_num_documento"]');
        const nitHelp = document.getElementById('nitHelp');
        const nitHomologadoCheck = document.getElementById('nitHomologado');
        const labelNit = document.getElementById('labelNit');

        // Listener para el checkbox de NIT Homologado
        nitHomologadoCheck.addEventListener('change', function() {
            const isHomologado = this.checked;
            
            // Limpiar input y resetear estado
            numDocInput.value = '';
            nitHelp.textContent = isHomologado ? 'DUI: 9 dígitos sin guiones' : 'NIT: 14 dígitos sin guiones';
            nitHelp.className = 'text-xs text-slate-500 mt-1';
            
            // Actualizar label y placeholder
            if (isHomologado) {
                labelNit.textContent = 'NIT Homologado (9 dígitos) *';
                numDocInput.placeholder = '000000000';
                numDocInput.maxLength = 9;
            } else {
                labelNit.textContent = 'NIT (14 dígitos) *';
                numDocInput.placeholder = '06142302881449';
                numDocInput.maxLength = 14;
            }
            
            // Ocultar resultados de búsqueda previos
            document.getElementById('resultadoBusqueda').classList.add('hidden');
        });

        numDocInput.addEventListener('input', function() {
            let valor = this.value.replace(/[^0-9]/g, ''); // Solo números
            const isHomologado = nitHomologadoCheck.checked;
            const maxLen = isHomologado ? 9 : 14;

            if (valor.length > maxLen) {
                valor = valor.substring(0, maxLen);
            }
            this.value = valor;

            // Validación visual
            if (valor.length === maxLen) {
                nitHelp.textContent = isHomologado ? '✓ NIT Homologado válido' : '✓ NIT válido';
                nitHelp.className = 'text-xs text-green-600 mt-1 font-medium';
            } else if (valor.length > 0) {
                nitHelp.textContent = `Faltan ${maxLen - valor.length} dígitos`;
                nitHelp.className = 'text-xs text-orange-600 mt-1 font-medium';
            } else {
                nitHelp.textContent = isHomologado ? 'DUI: 9 dígitos sin guiones' : 'NIT: 14 dígitos sin guiones';
                nitHelp.className = 'text-xs text-slate-500 mt-1';
            }
        });

        // Validación antes de enviar
        document.getElementById('facturaForm').addEventListener('submit', function(e) {
            const numDoc = numDocInput.value.replace(/[^0-9]/g, '');
            const isHomologado = document.getElementById('nitHomologado').checked;
            const requiredLen = isHomologado ? 9 : 14;

            if (numDoc.length !== requiredLen) {
                e.preventDefault();
                const tipoDoc = isHomologado ? 'NIT Homologado' : 'NIT';
                alert(`El ${tipoDoc} debe tener exactamente ${requiredLen} dígitos.\n\nActual: ${numDoc.length} dígitos\nRequerido: ${requiredLen} dígitos`);
                numDocInput.focus();
                return false;
            }

            // Validar NRC
            const nrc = document.querySelector('input[name="receptor_nrc"]').value.trim();
            if (!nrc) {
                e.preventDefault();
                alert('El NRC es obligatorio para el Comprobante de Crédito Fiscal (CCF)');
                document.querySelector('input[name="receptor_nrc"]').focus();
                return false;
            }

            // Validar código de actividad
            const codActividad = document.querySelector('input[name="receptor_cod_actividad"]').value.trim();
            if (!codActividad) {
                e.preventDefault();
                alert('El Código de Actividad Económica es obligatorio para el Comprobante de Crédito Fiscal (CCF)');
                document.querySelector('input[name="receptor_cod_actividad"]').focus();
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

        // Búsqueda de cliente por NIT
        const docInput = document.getElementById('receptor_num_documento');
        const busquedaLoader = document.getElementById('busquedaLoader');
        const resultadoBusqueda = document.getElementById('resultadoBusqueda');
        let timeoutBusqueda;

        docInput.addEventListener('blur', function() {
            clearTimeout(timeoutBusqueda);
            const documento = this.value.trim();
            const isHomologado = document.getElementById('nitHomologado').checked;
            const minLen = isHomologado ? 9 : 14;

            if (!documento || documento.length < minLen) {
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
            const isHomologado = document.getElementById('nitHomologado').checked;
            const requiredLen = isHomologado ? 9 : 14;

            // Validar que no esté vacío después de limpiar
            if (!documentoLimpio || documentoLimpio.length < requiredLen) {
                busquedaLoader.style.display = 'none';
                resultadoBusqueda.textContent = `✗ ${isHomologado ? 'NIT Homologado' : 'NIT'} inválido (debe tener ${requiredLen} dígitos)`;
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
                    // Capturar cliente_id si existe
                    if (cliente.id || cliente.cliente_id) {
                        const clienteId = cliente.id || cliente.cliente_id;
                        document.getElementById('clienteIdField').value = clienteId;
                    }

                    // Rellenar razón social (nombre + apellido)
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
        // Validación de descuento vs precio total
        // ==========================================
        function validarDescuento(event) {
            const descuentoInput = event.target;
            const descuento = parseFloat(descuentoInput.value) || 0;
            const itemRow = descuentoInput.closest('.item-row');
            
            const cantidadInput = itemRow.querySelector('.cantidad');
            const precioInput = itemRow.querySelector('.precio');
            
            const cantidad = parseFloat(cantidadInput.value) || 0;
            const precio = parseFloat(precioInput.value) || 0;
            const precioTotal = cantidad * precio;

            const mensajesError = itemRow.querySelectorAll('.mensaje-descuento-error');
            mensajesError.forEach(msg => {
                msg.textContent = '';
                msg.classList.add('hidden');
            });

            if (descuento > precioTotal && precioTotal > 0) {
                descuentoInput.classList.add('border-red-500', 'border-2');
                descuentoInput.value = precioTotal;
                itemRow.classList.add('error-descuento');
                
                let mensajeError = itemRow.querySelector('.mensaje-descuento-error');
                if (!mensajeError) {
                    mensajeError = document.createElement('p');
                    mensajeError.className = 'mensaje-descuento-error text-xs mt-1 font-medium';
                    descuentoInput.closest('div').parentElement.appendChild(mensajeError);
                }
                mensajeError.textContent = `⚠️ Descuento no puede ser mayor a $${precioTotal.toFixed(2)} (cantidad × precio)`;
                mensajeError.className = 'text-xs mt-1 font-medium text-red-600';
                mensajeError.classList.remove('hidden');
            } else if (descuento >= 0) {
                descuentoInput.classList.remove('border-red-500', 'border-2');
                itemRow.classList.remove('error-descuento');
            }

            verificarEstadoFormulario();
            calcularTotales();
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
            const itemsConError = document.querySelectorAll('.item-row.error-stock, .item-row.error-descuento');
            
            if (itemsConError.length > 0) {
                submitBtn.disabled = true;
                submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
                submitBtn.title = 'Hay items con errores (stock insuficiente o descuento inválido)';
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

        setupProductSearch(document.querySelector('.codigo-producto'));

        // VALIDACIÓN EN TIEMPO REAL DE ITEMS (IDÉNTICA A DTE-01)
        // Validar campo de item
        function validarCampoItem(input, tipoValidacion = 'default') {
            const itemRow = input.closest('.item-row');
            const campoNombre = input.name.match(/\[([^\]]+)\]/)?.[1] || '';
            const valor = input.value.trim();
            let esValido = true;
            let mensaje = '';

            // Campos obligatorios no deben estar vacíos
            const camposObligatorios = ['tipo_item', 'codigo', 'descripcion', 'cantidad', 'precio_unitario'];
            
            if (camposObligatorios.includes(campoNombre)) {
                if (!valor) {
                    esValido = false;
                    mensaje = 'Este campo es obligatorio';
                }
            }

            // Campos de moneda (precio y descuento) deben ser positivos
            const camposMoneda = ['precio_unitario', 'monto_descuento'];
            if (camposMoneda.includes(campoNombre) && valor) {
                const numeroValor = parseFloat(valor);
                if (isNaN(numeroValor) || numeroValor < 0) {
                    esValido = false;
                    mensaje = 'Solo se permiten números positivos';
                } else if (numeroValor === 0 && campoNombre === 'precio_unitario') {
                    esValido = false;
                    mensaje = 'El precio debe ser mayor a 0';
                }
            }

            // Campo cantidad debe ser positivo
            if (campoNombre === 'cantidad' && valor) {
                const numeroValor = parseInt(valor);
                if (isNaN(numeroValor) || numeroValor <= 0) {
                    esValido = false;
                    mensaje = 'La cantidad debe ser mayor a 0';
                }
            }

            // Validar descuento no sea mayor al subtotal
            if (campoNombre === 'monto_descuento' && valor) {
                const cantidadInput = itemRow.querySelector('input[name*="cantidad"]');
                const precioInput = itemRow.querySelector('input[name*="precio_unitario"]');
                
                if (cantidadInput && precioInput && cantidadInput.value && precioInput.value) {
                    const subtotal = parseFloat(cantidadInput.value) * parseFloat(precioInput.value);
                    const descuento = parseFloat(valor);
                    
                    if (descuento > subtotal) {
                        esValido = false;
                        mensaje = `Descuento no puede ser mayor al subtotal ($${subtotal.toFixed(2)})`;
                    }
                }
            }

            // Aplicar estilos visual
            if (esValido && valor) {
                input.classList.remove('border-red-500', 'bg-red-50', 'error-field');
                input.classList.add('border-green-500');
                // Remover mensaje de error si existe
                const existentMsg = itemRow.querySelector(`[data-error-for="${input.name}"]`);
                if (existentMsg) existentMsg.remove();
            } else if (!esValido) {
                input.classList.remove('border-green-500');
                input.classList.add('border-red-500', 'bg-red-50', 'error-field');
                
                // Mostrar/actualizar mensaje de error
                let msgElement = itemRow.querySelector(`[data-error-for="${input.name}"]`);
                if (!msgElement) {
                    msgElement = document.createElement('p');
                    msgElement.setAttribute('data-error-for', input.name);
                    msgElement.className = 'text-red-600 text-xs mt-1 font-semibold';
                    input.parentElement.appendChild(msgElement);
                }
                msgElement.textContent = mensaje;
            } else {
                input.classList.remove('border-red-500', 'bg-red-50', 'error-field', 'border-green-500');
            }

            return esValido;
        }

        // Attach validación a todos los inputs de items existentes
        function attachItemValidation() {
            document.querySelectorAll('.item-row input, .item-row select').forEach(input => {
                // Remover listeners anteriores (si existen)
                input.removeEventListener('blur', handleItemBlur);
                input.removeEventListener('change', handleItemChange);
                input.removeEventListener('input', handleItemInput);
                
                // Agregar nuevos listeners
                input.addEventListener('blur', handleItemBlur);
                input.addEventListener('change', handleItemChange);
                input.addEventListener('input', handleItemInput);
            });
        }

        // Handlers para validación
        function handleItemBlur(e) {
            validarCampoItem(e.target, 'blur');
            calcularTotales();
        }

        function handleItemChange(e) {
            validarCampoItem(e.target, 'change');
            calcularTotales();
        }

        function handleItemInput(e) {
            // Solo validar en tiempo real para campos numéricos
            if (['precio_unitario', 'monto_descuento', 'cantidad'].includes(e.target.name.match(/\[([^\]]+)\]/)?.[1])) {
                validarCampoItem(e.target, 'input');
            }
        }

        // Setup del primer item (item 0)
        const primerTipoItem = document.querySelector('select[name="items[0][tipo_item]"]');
        const primerCodigoInput = document.querySelector('input[name="items[0][codigo]"]');
        
        if (primerTipoItem) {
            primerTipoItem.addEventListener('change', function() {
                aplicarLogicaTipo(this, primerCodigoInput);
            });
        }

        if (primerCodigoInput) {
            setupProductSearch(primerCodigoInput);
        }

        // Inicializar validación en items existentes
        attachItemValidation();

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

        // Agregar listener al submit del formulario
        document.getElementById('facturaForm').addEventListener('submit', function(e) {
            if (e.defaultPrevented) return;

            console.log('=== SUBMIT INICIADO ===');
            
            // Validar todos los items antes de enviar
            let hayErrores = false;
            document.querySelectorAll('.item-row').forEach(itemRow => {
                // Validar campos obligatorios
                const tipoInput = itemRow.querySelector('select[name*="tipo_item"]');
                const codigoInput = itemRow.querySelector('input[name*="codigo"]');
                const descInput = itemRow.querySelector('input[name*="descripcion"]');
                const cantidadInput = itemRow.querySelector('input[name*="cantidad"]');
                const precioInput = itemRow.querySelector('input[name*="precio_unitario"]');
                
                [tipoInput, codigoInput, descInput, cantidadInput, precioInput].forEach(input => {
                    if (input && !validarCampoItem(input)) {
                        hayErrores = true;
                    }
                });
                
                // Validar descuento si existe
                const descuentoInput = itemRow.querySelector('input[name*="monto_descuento"]');
                if (descuentoInput && descuentoInput.value) {
                    if (!validarCampoItem(descuentoInput)) {
                        hayErrores = true;
                    }
                }
            });
            
            if (hayErrores) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Errores en el formulario',
                    text: 'Por favor corrija los errores en los items antes de continuar.',
                    confirmButtonColor: '#d33'
                });
                return false;
            }
            
            // Recolectar producto_ids INMEDIATAMENTE
            const productos = recolectarProductoIds();
            
            if (productos.length > 0) {
                const jsonProductos = JSON.stringify(productos);
                document.getElementById('motorClientesIds').value = jsonProductos;
            }

            // Confirmación
            e.preventDefault();
            Swal.fire({
                title: '¿Estás seguro?',
                text: "Estás a punto de emitir un Comprobante de Crédito Fiscal. Verifica que todos los datos sean correctos.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Sí, emitir CCF',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    this.submit();
                }
            });
        });
    </script>
</body>
</html>
