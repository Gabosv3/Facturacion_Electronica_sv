<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Generar Nota de Crédito - DTE El Salvador</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="{{ asset('js/catalogo-municipios.js') }}"></script>
</head>
<body class="bg-gray-100">
    @include('components.navbar')

    <div class="container mx-auto px-4 py-8">
        <div class="max-w-5xl mx-auto">
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h1 class="text-3xl font-bold text-gray-800 mb-6">Generar Nota de Crédito (DTE-05)</h1>

                <div class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded mb-6">
                    <p class="font-semibold">ℹ️ Información sobre Notas de Crédito</p>
                    <p class="text-sm mt-2">Una Nota de Crédito se usa para:</p>
                    <ul class="text-sm mt-1 ml-4 list-disc">
                        <li><strong>Anular</strong> una factura o CCF emitido</li>
                        <li><strong>Ajustar el precio</strong> de productos/servicios facturados</li>
                        <li><strong>Devolver</strong> productos facturados</li>
                    </ul>
                </div>

                <div class="bg-yellow-100 border border-yellow-400 text-yellow-800 px-4 py-3 rounded mb-6">
                    <p class="font-semibold">⚠️ Importante - Limitación del MH</p>
                    <p class="text-sm mt-2">
                        Las Notas de Crédito <strong>solo se pueden emitir para facturas con NIT</strong> (no DUI).
                        Esto es una limitación del Ministerio de Hacienda, ya que el campo <code>receptor.nit</code>
                        es obligatorio en DTE-05 y el MH valida que el NIT exista en su base de datos.
                    </p>
                    <p class="text-sm mt-2">
                        Si no ves una factura en la lista, es porque fue emitida con DUI en lugar de NIT.
                    </p>
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
                    <input type="hidden" name="tipo_dte" value="05">

                    <!-- Selección de Documento a Relacionar -->
                    <div class="mb-8">
                        <h2 class="text-xl font-semibold text-gray-700 mb-4 border-b pb-2">Documento a Anular/Ajustar</h2>

                        <div class="grid grid-cols-1 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Seleccionar Factura o CCF *</label>
                                @if($facturasDisponibles->isEmpty())
                                    <div class="bg-orange-100 border border-orange-400 text-orange-700 px-4 py-3 rounded">
                                        <p class="font-semibold">⚠️ No hay facturas disponibles</p>
                                        <p class="text-sm mt-1">
                                            No existen facturas con NIT que hayan sido enviadas exitosamente al MH.
                                            Las Notas de Crédito solo pueden emitirse sobre facturas que tengan NIT (no DUI).
                                        </p>
                                        <p class="text-sm mt-2">
                                            <strong>Solución:</strong> Crea una nueva factura o CCF usando NIT como tipo de documento del receptor.
                                        </p>
                                    </div>
                                @else
                                    <select name="documento_relacionado_id" id="documentoRelacionado" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500" required>
                                        <option value="">Seleccione el documento a relacionar...</option>
                                        @foreach($facturasDisponibles as $factura)
                                            <option value="{{ $factura->id }}"
                                                    data-tipo-doc="{{ $factura->receptor_tipo_documento }}"
                                                    data-num-doc="{{ $factura->receptor_num_documento }}"
                                                    data-nombre="{{ $factura->receptor_nombre }}"
                                                    data-nrc="{{ $factura->receptor_nrc }}"
                                                    data-cod-actividad="{{ $factura->receptor_actividad }}"
                                                    data-direccion="{{ $factura->receptor_direccion }}"
                                                    data-telefono="{{ $factura->receptor_telefono }}"
                                                    data-correo="{{ $factura->receptor_correo }}"
                                                    data-items="{{ htmlspecialchars(json_encode($factura->items), ENT_QUOTES, 'UTF-8') }}"
                                                    data-nota-credito-estado="{{ $factura->nota_credito_estado }}"
                                                    data-nota-credito-numero="{{ $factura->notaCredito ? $factura->notaCredito->numero_control : '' }}"
                                                    data-nota-credito-fecha="{{ $factura->notaCredito ? $factura->notaCredito->fecha_emision->format('d/m/Y') : '' }}">
                                                {{ $factura->tipo_dte === '01' ? 'FACTURA' : 'CCF' }} -
                                                {{ $factura->numero_control }} -
                                                {{ $factura->receptor_nombre }} -
                                                ${{ number_format($factura->total_pagar, 2) }} -
                                                {{ $factura->fecha_emision }}
                                                @if($factura->nota_credito_estado)
                                                    - [⚠️
                                                    @if($factura->nota_credito_estado == 'anulacion')
                                                        ANULADO
                                                    @elseif($factura->nota_credito_estado == 'ajuste_precio')
                                                        AJUSTE
                                                    @elseif($factura->nota_credito_estado == 'devolucion')
                                                        DEVOLUCIÓN
                                                    @else
                                                        CON NC
                                                    @endif
                                                    ]
                                                @endif
                                            </option>
                                        @endforeach
                                    </select>
                                    <p class="text-xs text-gray-500 mt-1">
                                        Solo se muestran facturas con NIT enviadas exitosamente al MH.
                                        Total disponibles: {{ $facturasDisponibles->count() }}
                                    </p>

                                    <!-- Alerta cuando se selecciona un documento con nota de crédito existente -->
                                    <div id="alertaNotaCreditoExistente" class="hidden mt-4 p-4 border-2 rounded-lg">
                                        <div class="flex items-start gap-3">
                                            <svg class="w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                            </svg>
                                            <div class="flex-1">
                                                <p class="font-bold text-lg mb-2">⚠️ Este documento ya tiene una Nota de Crédito</p>
                                                <div id="detalleNotaCreditoExistente" class="text-sm space-y-1">
                                                    <!-- Se llenará con JavaScript -->
                                                </div>
                                                <div class="mt-3 p-3 bg-white bg-opacity-50 rounded">
                                                    <p class="text-sm font-semibold">💡 ¿Puedes crear otra Nota de Crédito?</p>
                                                    <p class="text-xs mt-1">
                                                        Técnicamente sí, el sistema te lo permite. Sin embargo, debes verificar:
                                                    </p>
                                                    <ul class="text-xs mt-1 ml-4 list-disc space-y-1">
                                                        <li>Si el documento fue <strong>anulado</strong>, generalmente no se debe emitir otra NC</li>
                                                        <li>Si fue un <strong>ajuste o devolución parcial</strong>, podrías necesitar otra NC para ajustes adicionales</li>
                                                        <li>Consulta con tu contador o las políticas de tu empresa</li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de Nota de Crédito *</label>
                                <select name="tipo_documento" id="tipoNotaCredito" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500" required>
                                    <option value="">Seleccione...</option>
                                    <option value="1">Anulación - Documento emitido por error</option>
                                    <option value="2">Ajuste de Precio - Corrección de monto</option>
                                    <option value="3">Devolución - Cliente devuelve productos</option>
                                </select>

                                <!-- Explicación dinámica según tipo -->
                                <div id="explicacionTipo" class="mt-3 p-3 rounded hidden"></div>
                            </div>

                            <div class="mt-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                                <label class="flex items-start cursor-pointer">
                                    <input type="checkbox" name="precio_incluye_iva" id="precioIncluyeIva" value="1" class="mt-1 h-5 w-5 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                    <div class="ml-3">
                                        <span class="text-sm font-medium text-gray-900">El precio del producto incluye IVA</span>
                                        <p class="text-xs text-gray-600 mt-1">
                                            Marca esta casilla si el precio unitario que ingresarás ya incluye el IVA (13%).
                                            El sistema extraerá automáticamente la venta gravada y el IVA.
                                        </p>
                                        <p class="text-xs text-blue-700 mt-1 font-medium">
                                            Ejemplo: Si ingresas $22.60 con IVA incluido → Venta Gravada: $20.00, IVA: $2.60
                                        </p>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Datos del Receptor (se llenan automáticamente) -->
                    <div class="mb-8">
                        <h2 class="text-xl font-semibold text-gray-700 mb-4 border-b pb-2">Datos del Cliente (automático)</h2>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de Documento *</label>
                                <select name="receptor_tipo_documento" id="receptor_tipo_documento" class="w-full border border-gray-300 rounded-lg px-4 py-2 bg-gray-100" readonly required>
                                    <option value="">Seleccione primero la factura...</option>
                                    <option value="36">NIT - Registro de contribuyente IVA</option>
                                    <option value="13">DUI - Persona natural sin IVA</option>
                                    <option value="02">Carnet de residente</option>
                                    <option value="03">Pasaporte</option>
                                    <option value="37">Otro</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Número de Documento *</label>
                                <input type="text" name="receptor_num_documento" id="receptor_num_documento" class="w-full border border-gray-300 rounded-lg px-4 py-2 bg-gray-100" readonly required>
                            </div>

                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Nombre Completo *</label>
                                <input type="text" name="receptor_nombre" id="receptor_nombre" class="w-full border border-gray-300 rounded-lg px-4 py-2 bg-gray-100" readonly required style="text-transform: uppercase;">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">NRC</label>
                                <input type="text" name="receptor_nrc" id="receptor_nrc" class="w-full border border-gray-300 rounded-lg px-4 py-2 bg-gray-100" readonly>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Teléfono</label>
                                <input type="text" name="receptor_telefono" id="receptor_telefono" class="w-full border border-gray-300 rounded-lg px-4 py-2 bg-gray-100" readonly placeholder="7777-7777">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Código Actividad Económica</label>
                                <input type="text" name="receptor_cod_actividad" id="receptor_cod_actividad" class="w-full border border-gray-300 rounded-lg px-4 py-2 bg-gray-100" readonly>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Descripción Actividad *</label>
                                <input type="text" name="receptor_desc_actividad" id="receptor_desc_actividad" value="ACTIVIDADES NO ESPECIFICADAS" class="w-full border border-gray-300 rounded-lg px-4 py-2" required>
                            </div>

                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Correo Electrónico</label>
                                <input type="email" name="receptor_correo" id="receptor_correo" class="w-full border border-gray-300 rounded-lg px-4 py-2 bg-gray-100" readonly>
                            </div>
                        </div>
                    </div>

                    <!-- Dirección del Receptor (automático) -->
                    <div class="mb-8">
                        <h2 class="text-xl font-semibold text-gray-700 mb-4 border-b pb-2">Dirección del Cliente (automático)</h2>

                        <input type="hidden" name="receptor_departamento" id="receptor_departamento" value="06">
                        <input type="hidden" name="receptor_municipio" id="receptor_municipio" value="23">
                        <input type="hidden" name="receptor_distrito" id="receptor_distrito" value="03">

                        <div class="grid grid-cols-1 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Complemento de Dirección *</label>
                                <input type="text" name="receptor_complemento" id="receptor_complemento" class="w-full border border-gray-300 rounded-lg px-4 py-2 bg-gray-100" readonly required>
                            </div>
                        </div>
                        <p class="text-xs text-gray-500 mt-2">La dirección se copia automáticamente del documento relacionado</p>
                    </div>

                    <!-- Items de la Nota de Crédito -->
                    <div class="mb-8">
                        <div class="flex justify-between items-center mb-4 border-b pb-2">
                            <h2 class="text-xl font-semibold text-gray-700">Items de la Nota de Crédito</h2>
                            <button type="button" id="autoLlenarItems" class="bg-purple-500 text-white px-4 py-2 rounded-lg hover:bg-purple-600 text-sm hidden">
                                ✨ Auto-llenar Items
                            </button>
                        </div>

                        <div id="items-container">
                            <div class="item-row bg-gray-50 p-4 rounded-lg mb-4">
                                <input type="hidden" name="items[0][producto_id]" class="producto-id" value="">
                                <div class="grid grid-cols-1 md:grid-cols-7 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Tipo *</label>
                                        <select name="items[0][tipo_item]" class="tipo-item w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500" data-item-index="0" required>
                                            <option value="">Seleccionar</option>
                                            <option value="1">Bien</option>
                                            <option value="2">Servicio</option>
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Código</label>
                                        <div class="relative">
                                            <input type="text" name="items[0][codigo]" data-item-index="0" class="codigo-producto w-full border border-gray-300 rounded-lg px-4 py-2" required>
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
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Descripción</label>
                                        <input type="text" name="items[0][descripcion]" class="w-full border border-gray-300 rounded-lg px-4 py-2" required>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Cantidad</label>
                                        <input type="number" name="items[0][cantidad]" step="1" min="1" class="w-full border border-gray-300 rounded-lg px-4 py-2" required>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Precio Unit.</label>
                                        <input type="number" name="items[0][precio_unitario]" step="0.01" min="0" class="w-full border border-gray-300 rounded-lg px-4 py-2" required>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Descuento</label>
                                        <input type="number" name="items[0][monto_descuento]" step="0.01" min="0" value="0" class="w-full border border-gray-300 rounded-lg px-4 py-2">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <button type="button" id="addItem" class="bg-green-500 text-white px-4 py-2 rounded-lg hover:bg-green-600">
                            + Agregar Item
                        </button>
                    </div>

                    <!-- Condiciones de Pago -->
                    <div class="mb-8">
                        <h2 class="text-xl font-semibold text-gray-700 mb-4 border-b pb-2">Condiciones de Pago</h2>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Condición de Operación *</label>
                                <select name="condicion_operacion" class="w-full border border-gray-300 rounded-lg px-4 py-2" required>
                                    <option value="1">Contado</option>
                                    <option value="2">Crédito</option>
                                    <option value="3">Otro</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Forma de Pago *</label>
                                <select name="forma_pago" class="w-full border border-gray-300 rounded-lg px-4 py-2" required>
                                    <option value="01">Billetes y monedas</option>
                                    <option value="02">Tarjeta débito/crédito</option>
                                    <option value="03">Cheque</option>
                                    <option value="04">Transferencia</option>
                                    <option value="05">Giro</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Referencia de Pago</label>
                                <input type="text" name="referencia_pago" class="w-full border border-gray-300 rounded-lg px-4 py-2">
                            </div>
                        </div>
                    </div>

                    <!-- Observaciones -->
                    <div class="mb-8">
                        <h2 class="text-xl font-semibold text-gray-700 mb-4 border-b pb-2">Observaciones</h2>
                        <textarea name="observaciones" rows="3" class="w-full border border-gray-300 rounded-lg px-4 py-2" placeholder="Motivo de la nota de crédito..."></textarea>
                    </div>

                    <!-- Botones de Acción -->
                    <div class="flex justify-between items-center">
                        <a href="{{ route('facturas.index') }}" class="bg-gray-500 text-white px-6 py-3 rounded-lg hover:bg-gray-600">
                            Cancelar
                        </a>
                        <button type="submit" class="bg-blue-500 text-white px-6 py-3 rounded-lg hover:bg-blue-600">
                            Generar Nota de Crédito
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        let itemCount = 1;
        let currentFacturaItems = [];

        // Auto-llenar datos del receptor cuando se selecciona un documento
        document.getElementById('documentoRelacionado')?.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];

            if (selectedOption.value) {
                // Llenar datos del receptor
                document.getElementById('receptor_tipo_documento').value = selectedOption.dataset.tipoDoc;
                document.getElementById('receptor_num_documento').value = selectedOption.dataset.numDoc;
                document.getElementById('receptor_nombre').value = selectedOption.dataset.nombre;
                document.getElementById('receptor_nrc').value = selectedOption.dataset.nrc || '';
                document.getElementById('receptor_cod_actividad').value = selectedOption.dataset.codActividad || '';

                // Formatear teléfono al cargar desde factura relacionada
                let telefono = selectedOption.dataset.telefono || '';
                if (telefono && telefono.length === 8 && !telefono.includes('-')) {
                    telefono = telefono.substring(0, 4) + '-' + telefono.substring(4);
                }
                document.getElementById('receptor_telefono').value = telefono;

                document.getElementById('receptor_correo').value = selectedOption.dataset.correo || '';

                // Parsear dirección
                try {
                    const direccion = JSON.parse(selectedOption.dataset.direccion);
                    document.getElementById('receptor_departamento').value = direccion.departamento || '06';
                    document.getElementById('receptor_municipio').value = direccion.municipio || '23';
                    document.getElementById('receptor_distrito').value = direccion.distrito || '03';
                    document.getElementById('receptor_complemento').value = direccion.complemento || '';
                } catch (e) {
                    console.error('Error parsing direccion:', e);
                }

                // Guardar items de la factura seleccionada
                try {
                    currentFacturaItems = JSON.parse(selectedOption.dataset.items || '[]');
                    // Mostrar botón de auto-llenado si hay items
                    if (currentFacturaItems.length > 0) {
                        document.getElementById('autoLlenarItems').classList.remove('hidden');
                    }
                } catch (e) {
                    console.error('Error parsing items:', e);
                    currentFacturaItems = [];
                }

                // ⚠️ VERIFICAR SI TIENE NOTA DE CRÉDITO EXISTENTE
                const notaCreditoEstado = selectedOption.dataset.notaCreditoEstado;
                const alertaDiv = document.getElementById('alertaNotaCreditoExistente');
                const detalleDiv = document.getElementById('detalleNotaCreditoExistente');

                if (notaCreditoEstado) {
                    // Determinar color según tipo
                    let colorClasses = '';
                    let tipoTexto = '';
                    let iconoTexto = '';

                    if (notaCreditoEstado === 'anulacion') {
                        colorClasses = 'border-red-400 bg-red-50 text-red-800';
                        tipoTexto = 'ANULADO';
                        iconoTexto = '🚫';
                    } else if (notaCreditoEstado === 'ajuste_precio') {
                        colorClasses = 'border-orange-400 bg-orange-50 text-orange-800';
                        tipoTexto = 'AJUSTE DE PRECIO';
                        iconoTexto = '💰';
                    } else if (notaCreditoEstado === 'devolucion') {
                        colorClasses = 'border-blue-400 bg-blue-50 text-blue-800';
                        tipoTexto = 'DEVOLUCIÓN';
                        iconoTexto = '📦';
                    } else {
                        colorClasses = 'border-gray-400 bg-gray-50 text-gray-800';
                        tipoTexto = 'NOTA DE CRÉDITO';
                        iconoTexto = '⚠️';
                    }

                    // Aplicar clases de color
                    alertaDiv.className = `mt-4 p-4 border-2 rounded-lg ${colorClasses}`;

                    // Llenar detalles
                    const notaCreditoNumero = selectedOption.dataset.notaCreditoNumero;
                    const notaCreditoFecha = selectedOption.dataset.notaCreditoFecha;

                    detalleDiv.innerHTML = `
                        <p class="font-semibold">${iconoTexto} Estado: <span class="uppercase">${tipoTexto}</span></p>
                        <p class="mt-1">📄 Nota de Crédito existente: <strong>${notaCreditoNumero || 'N/A'}</strong></p>
                        <p>📅 Fecha de emisión: <strong>${notaCreditoFecha || 'N/A'}</strong></p>
                    `;

                    // Mostrar alerta
                    alertaDiv.classList.remove('hidden');
                } else {
                    // Ocultar alerta si no tiene nota de crédito
                    alertaDiv.classList.add('hidden');
                }
            }
        });

        // Mostrar explicación según tipo de nota de crédito
        document.getElementById('tipoNotaCredito').addEventListener('change', function() {
            const tipo = this.value;
            const explicacionDiv = document.getElementById('explicacionTipo');

            const explicaciones = {
                '1': {
                    clase: 'bg-red-50 border border-red-200 text-red-800',
                    titulo: '📋 Anulación Total',
                    texto: '<strong>Usa esto cuando:</strong> La factura fue emitida por error y necesitas anularla completamente.<br><strong>Items a incluir:</strong> TODOS los items de la factura original con las mismas cantidades y precios.<br><strong>💡 Tip:</strong> Usa el botón "✨ Auto-llenar Items" para copiar automáticamente todos los items.'
                },
                '2': {
                    clase: 'bg-yellow-50 border border-yellow-200 text-yellow-800',
                    titulo: '💰 Ajuste de Precio',
                    texto: '<strong>Usa esto cuando:</strong> Necesitas corregir un precio que fue cobrado incorrectamente.<br><strong>Items a incluir:</strong> La DIFERENCIA del monto a ajustar.<br><strong>Ejemplo:</strong> Si facturaste $500 pero debió ser $450, crea una nota de crédito por $50.'
                },
                '3': {
                    clase: 'bg-blue-50 border border-blue-200 text-blue-800',
                    titulo: '📦 Devolución Parcial/Total',
                    texto: '<strong>Usa esto cuando:</strong> El cliente devuelve productos que ya fueron facturados.<br><strong>Items a incluir:</strong> Solo los productos devueltos con sus cantidades.<br><strong>Ejemplo:</strong> Si vendes 10 tornillos y el cliente devuelve 3, incluye solo 3 tornillos en la nota.'
                }
            };

            if (tipo && explicaciones[tipo]) {
                const exp = explicaciones[tipo];
                explicacionDiv.className = `mt-3 p-3 rounded ${exp.clase}`;
                explicacionDiv.innerHTML = `
                    <p class="font-semibold">${exp.titulo}</p>
                    <p class="text-sm mt-2">${exp.texto}</p>
                `;
                explicacionDiv.classList.remove('hidden');
            } else {
                explicacionDiv.classList.add('hidden');
            }
        });

        // Agregar nuevo item
        document.getElementById('addItem').addEventListener('click', function() {
            const container = document.getElementById('items-container');
            const newItem = `
                <div class="item-row bg-gray-50 p-4 rounded-lg mb-4">
                    <input type="hidden" name="items[${itemCount}][producto_id]" class="producto-id" value="">
                    <div class="grid grid-cols-1 md:grid-cols-7 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Tipo *</label>
                            <select name="items[${itemCount}][tipo_item]" class="tipo-item w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500" data-item-index="${itemCount}" required>
                                <option value="">Seleccionar</option>
                                <option value="1">Bien</option>
                                <option value="2">Servicio</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Código</label>
                            <div class="relative">
                                <input type="text" name="items[${itemCount}][codigo]" data-item-index="${itemCount}" class="codigo-producto w-full border border-gray-300 rounded-lg px-4 py-2" required>
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
                            <label class="block text-sm font-medium text-gray-700 mb-2">Descripción</label>
                            <input type="text" name="items[${itemCount}][descripcion]" class="w-full border border-gray-300 rounded-lg px-4 py-2" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Cantidad</label>
                            <input type="number" name="items[${itemCount}][cantidad]" step="1" min="1" class="cantidad w-full border border-gray-300 rounded-lg px-4 py-2" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Precio Unit.</label>
                            <input type="number" name="items[${itemCount}][precio_unitario]" step="0.01" min="0" class="precio w-full border border-gray-300 rounded-lg px-4 py-2" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Descuento</label>
                            <input type="number" name="items[${itemCount}][monto_descuento]" step="0.01" min="0" value="0" class="descuento w-full border border-gray-300 rounded-lg px-4 py-2">
                        </div>
                    </div>
                    <button type="button" class="remove-item text-red-500 hover:text-red-700 mt-2">Eliminar</button>
                </div>
            `;
            container.insertAdjacentHTML('beforeend', newItem);
            
            // Obtener referencias del nuevo item
            const lastItem = container.querySelector('.item-row:last-child');
            const tipoItemSelect = lastItem.querySelector('.tipo-item');
            const codigoInput = lastItem.querySelector('.codigo-producto');
            
            // Listener para cambios en tipo_item
            tipoItemSelect.addEventListener('change', function() {
                aplicarLogicaTipo(this, codigoInput);
            });
            
            // Setup búsqueda
            setupProductSearch(codigoInput);
            
            itemCount++;
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
                
                // Remover evento de búsqueda
                codigoInput.removeEventListener('blur', function() {});
            } else if (tipo === '1') {
                // BIEN: código manual con búsqueda
                codigoInput.value = '';
                codigoInput.readOnly = false;
                codigoInput.classList.remove('bg-gray-200');
                codigoInput.style.cursor = 'auto';
                
                // Re-agregar búsqueda si no está
                setupProductSearch(codigoInput);
            }
        }

        // Eliminar item
        document.getElementById('items-container').addEventListener('click', function(e) {
            if (e.target.classList.contains('remove-item')) {
                e.target.closest('.item-row').remove();
            }
        });

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

        // Auto-llenar items desde la factura original
        document.getElementById('autoLlenarItems')?.addEventListener('click', function() {
            if (currentFacturaItems.length === 0) {
                alert('No hay items disponibles para auto-llenar');
                return;
            }

            const tipoNota = document.getElementById('tipoNotaCredito').value;
            if (!tipoNota) {
                alert('Por favor, selecciona primero el Tipo de Nota de Crédito');
                return;
            }

            // Confirmar acción
            const confirmMsg = tipoNota === '1'
                ? `¿Auto-llenar con TODOS los ${currentFacturaItems.length} items de la factura original?\n(Anulación total)`
                : `¿Auto-llenar con los ${currentFacturaItems.length} items de la factura original?\n(Luego puedes modificar cantidades/precios según necesites)`;

            if (!confirm(confirmMsg)) {
                return;
            }

            // Limpiar items existentes
            document.getElementById('items-container').innerHTML = '';
            itemCount = 0;

            // Agregar cada item de la factura
            currentFacturaItems.forEach((item, index) => {
                const newItem = `
                    <div class="item-row bg-gray-50 p-4 rounded-lg mb-4">
                        <div class="grid grid-cols-1 md:grid-cols-6 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Código</label>
                                <input type="text" name="items[${index}][codigo]" value="${item.codigo || ''}" class="w-full border border-gray-300 rounded-lg px-4 py-2" required>
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Descripción</label>
                                <input type="text" name="items[${index}][descripcion]" value="${item.descripcion || ''}" class="w-full border border-gray-300 rounded-lg px-4 py-2" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Cantidad</label>
                                <input type="number" name="items[${index}][cantidad]" value="${item.cantidad || ''}" step="1" min="1" class="w-full border border-gray-300 rounded-lg px-4 py-2" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Precio Unit.</label>
                                <input type="number" name="items[${index}][precio_unitario]" value="${item.precio_unitario || ''}" step="0.01" min="0" class="w-full border border-gray-300 rounded-lg px-4 py-2" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Descuento</label>
                                <input type="number" name="items[${index}][monto_descuento]" value="${item.monto_descuento || 0}" step="0.01" min="0" class="w-full border border-gray-300 rounded-lg px-4 py-2">
                            </div>
                        </div>
                        <button type="button" class="remove-item text-red-500 hover:text-red-700 mt-2">Eliminar</button>
                    </div>
                `;
                document.getElementById('items-container').insertAdjacentHTML('beforeend', newItem);
                itemCount++;
            });

            // Mostrar mensaje de éxito
            const mensaje = tipoNota === '1'
                ? `✅ ${currentFacturaItems.length} items auto-llenados para ANULACIÓN TOTAL`
                : `✅ ${currentFacturaItems.length} items auto-llenados. Ajusta cantidades/precios según necesites.`;

            alert(mensaje);
        });
    </script>
</body>
</html>
