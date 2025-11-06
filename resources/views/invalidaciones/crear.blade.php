<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Evento de Invalidación - DTE El Salvador</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-gray-100">
    @include('components.navbar')

    <div class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto">
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h1 class="text-3xl font-bold text-gray-800 mb-6">Crear Evento de Invalidación</h1>

                @if(session('error'))
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                        {{ session('error') }}
                    </div>
                @endif

                @if($errors->any())
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                        <ul class="list-disc list-inside">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('invalidaciones.store') }}" method="POST">
                    @csrf

                    <!-- Información de la Factura a Invalidar -->
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                        <h2 class="text-lg font-semibold text-blue-900 mb-3">Factura a Invalidar</h2>

                        @if($factura)
                            <input type="hidden" name="factura_id" value="{{ $factura->id }}">

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <span class="text-sm text-gray-600">Número de Control:</span>
                                    <p class="font-mono font-semibold">{{ $factura->numero_control }}</p>
                                </div>
                                <div>
                                    <span class="text-sm text-gray-600">Tipo DTE:</span>
                                    <p class="font-semibold">DTE-{{ $factura->tipo_dte }}</p>
                                </div>
                                <div>
                                    <span class="text-sm text-gray-600">Receptor:</span>
                                    <p class="font-semibold">{{ $factura->receptor_nombre }}</p>
                                </div>
                                <div>
                                    <span class="text-sm text-gray-600">Total:</span>
                                    <p class="font-semibold">${{ number_format($factura->total_pagar, 2) }}</p>
                                </div>
                                <div>
                                    <span class="text-sm text-gray-600">Fecha Emisión:</span>
                                    <p class="font-semibold">{{ $factura->fecha_emision->format('d/m/Y') }}</p>
                                </div>
                                <div>
                                    <span class="text-sm text-gray-600">Código de Generación:</span>
                                    <p class="font-mono text-xs">{{ $factura->codigo_generacion }}</p>
                                </div>
                            </div>
                        @else
                            <div>
                                <label for="factura_id" class="block text-sm font-medium text-gray-700 mb-2">
                                    Seleccionar Factura *
                                </label>
                                <select name="factura_id" id="factura_id" required
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                    <option value="">Seleccione una factura...</option>
                                    @foreach($facturasInvalidables as $f)
                                        <option value="{{ $f->id }}">
                                            {{ $f->numero_control }} - {{ $f->receptor_nombre }} - ${{ number_format($f->total_pagar, 2) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                    </div>

                    <!-- Tipo de Invalidación según CAT-24 -->
                    <div class="mb-6">
                        <label for="tipo_invalidacion" class="block text-sm font-medium text-gray-700 mb-2">
                            Tipo de Invalidación (CAT-24) *
                        </label>
                        <select name="tipo_invalidacion" id="tipo_invalidacion" required
                                onchange="toggleFacturaReemplazo()"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                            <option value="">Seleccione...</option>
                            <option value="1">1 - Error de información del DTE a invalidar (requiere factura de reemplazo)</option>
                            <option value="2">2 - Rescindir operación realizada</option>
                            <option value="3">3 - Otros motivos</option>
                        </select>
                        <p class="text-xs text-gray-500 mt-1">
                            Tipo 1: Errores en datos, requiere documento correcto. Tipo 2: Cancelación total. Tipo 3: Otros casos.
                        </p>
                    </div>

                    <!-- Factura de Reemplazo (solo para tipo 1) -->
                    <div id="reemplazo-section" class="mb-6 hidden">
                        <label for="factura_reemplazo_id" class="block text-sm font-medium text-gray-700 mb-2">
                            Factura de Reemplazo *
                        </label>
                        <select name="factura_reemplazo_id" id="factura_reemplazo_id"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                            <option value="">Seleccione la nueva factura que reemplaza...</option>
                            @foreach($facturasInvalidables as $f)
                                <option value="{{ $f->id }}">
                                    {{ $f->numero_control }} - {{ $f->receptor_nombre }} - ${{ number_format($f->total_pagar, 2) }}
                                </option>
                            @endforeach
                        </select>
                        <p class="text-xs text-gray-500 mt-1">
                            Esta factura debe haber sido emitida y enviada al MH correctamente antes de crear el evento de invalidación.
                        </p>
                    </div>

                    <!-- Motivo de Invalidación -->
                    <div class="mb-6">
                        <label for="motivo_invalidacion" class="block text-sm font-medium text-gray-700 mb-2">
                            Motivo de Invalidación *
                        </label>
                        <textarea name="motivo_invalidacion" id="motivo_invalidacion" required rows="3"
                                  maxlength="500"
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                                  placeholder="Describa el motivo de la invalidación (máx. 500 caracteres)"></textarea>
                    </div>

                    <!-- Responsable de Invalidar -->
                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 mb-6">
                        <h2 class="text-lg font-semibold text-gray-900 mb-3">Responsable de Invalidar (Emisor)</h2>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="md:col-span-2">
                                <label for="responsable_nombre" class="block text-sm font-medium text-gray-700 mb-2">
                                    Nombre Completo *
                                </label>
                                <input type="text" name="responsable_nombre" id="responsable_nombre" required
                                       maxlength="200"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                            </div>

                            <div>
                                <label for="responsable_tipo_doc" class="block text-sm font-medium text-gray-700 mb-2">
                                    Tipo Documento *
                                </label>
                                <select name="responsable_tipo_doc" id="responsable_tipo_doc" required
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                    <option value="36">NIT</option>
                                    <option value="13">DUI</option>
                                    <option value="03">Pasaporte</option>
                                </select>
                            </div>

                            <div class="md:col-span-3">
                                <label for="responsable_num_doc" class="block text-sm font-medium text-gray-700 mb-2">
                                    Número de Documento *
                                </label>
                                <input type="text" name="responsable_num_doc" id="responsable_num_doc" required
                                       maxlength="20"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                            </div>
                        </div>
                    </div>

                    <!-- Solicitante de Invalidación -->
                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 mb-6">
                        <h2 class="text-lg font-semibold text-gray-900 mb-3">Quien Solicita la Invalidación</h2>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="md:col-span-2">
                                <label for="solicitante_nombre" class="block text-sm font-medium text-gray-700 mb-2">
                                    Nombre Completo *
                                </label>
                                <input type="text" name="solicitante_nombre" id="solicitante_nombre" required
                                       maxlength="200"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                            </div>

                            <div>
                                <label for="solicitante_tipo_doc" class="block text-sm font-medium text-gray-700 mb-2">
                                    Tipo Documento *
                                </label>
                                <select name="solicitante_tipo_doc" id="solicitante_tipo_doc" required
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                    <option value="36">NIT</option>
                                    <option value="13">DUI</option>
                                    <option value="03">Pasaporte</option>
                                </select>
                            </div>

                            <div class="md:col-span-3">
                                <label for="solicitante_num_doc" class="block text-sm font-medium text-gray-700 mb-2">
                                    Número de Documento *
                                </label>
                                <input type="text" name="solicitante_num_doc" id="solicitante_num_doc" required
                                       maxlength="20"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                            </div>
                        </div>
                    </div>

                    <!-- Advertencia importante -->
                    <div class="bg-yellow-50 border border-yellow-300 rounded-lg p-4 mb-6">
                        <h3 class="text-sm font-semibold text-yellow-900 mb-2">⚠️ Importante:</h3>
                        <ul class="text-xs text-yellow-800 list-disc list-inside space-y-1">
                            <li>Al procesar esta invalidación, el evento será firmado y enviado automáticamente al MH.</li>
                            <li>La factura quedará marcada como INVALIDADA en el sistema.</li>
                            <li>Verifique los plazos: DTE-03/05 (máx. 1 día), DTE-01/14 (máx. 3 meses).</li>
                            <li>Si es por errores formales, debe emitir primero la factura de reemplazo.</li>
                        </ul>
                    </div>

                    <!-- Botones -->
                    <div class="flex justify-end space-x-4">
                        <a href="{{ route('invalidaciones.index') }}"
                           class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg transition">
                            Cancelar
                        </a>
                        <button type="submit"
                                class="bg-red-600 hover:bg-red-700 text-white px-6 py-2 rounded-lg transition">
                            Procesar Invalidación
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function toggleFacturaReemplazo() {
            const tipo = document.getElementById('tipo_invalidacion').value;
            const reemplazoSection = document.getElementById('reemplazo-section');
            const reemplazoSelect = document.getElementById('factura_reemplazo_id');

            if (tipo === '1') {
                reemplazoSection.classList.remove('hidden');
                reemplazoSelect.required = true;
            } else {
                reemplazoSection.classList.add('hidden');
                reemplazoSelect.required = false;
                reemplazoSelect.value = '';
            }
        }
    </script>
</body>
</html>
