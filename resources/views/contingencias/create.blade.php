<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activar Contingencia - DTE El Salvador</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50">
    @include('components.navbar')

    <div class="container mx-auto px-4 py-8">
        <div class="max-w-3xl mx-auto">
            <!-- Header -->
            <div class="bg-red-600 text-white rounded-t-lg p-6">
                <div class="flex items-center gap-4">
                    <div class="text-5xl">🚨</div>
                    <div>
                        <h1 class="text-3xl font-bold">Activar Modo Contingencia</h1>
                        <p class="mt-2">Evento de fuerza mayor que impide el envío de DTEs al MH</p>
                    </div>
                </div>
            </div>

            <!-- Form -->
            <div class="bg-white rounded-b-lg shadow-lg p-8">
                <div class="bg-yellow-50 border-l-4 border-yellow-500 p-4 mb-6">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-yellow-700">
                                <strong>IMPORTANTE:</strong> Al activar la contingencia, todas las facturas nuevas se guardarán localmente y NO se enviarán al MH hasta que cierres la contingencia y las proceses.
                            </p>
                        </div>
                    </div>
                </div>

                <form action="{{ route('contingencias.store') }}" method="POST">
                    @csrf

                    <!-- Tipo de Contingencia -->
                    <div class="mb-6">
                        <label class="block text-sm font-semibold text-slate-700 mb-2">
                            Tipo de Contingencia <span class="text-red-600">*</span>
                        </label>
                        <select name="tipo_contingencia" required class="w-full border border-slate-200 rounded-lg px-4 py-2 focus:ring-2 focus:ring-red-500 focus:border-red-500">
                            <option value="">Seleccionar tipo...</option>
                            <option value="1">1 - Falla en el suministro de energía eléctrica del emisor</option>
                            <option value="2">2 - Falla del sistema de emisión del contribuyente</option>
                            <option value="3">3 - Falla en el servicio de internet</option>
                            <option value="4">4 - Falla en los servicios de la Administración Tributaria</option>
                            <option value="5">5 - Otros eventos de fuerza mayor</option>
                        </select>
                        @error('tipo_contingencia')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Motivo -->
                    <div class="mb-6">
                        <label class="block text-sm font-semibold text-slate-700 mb-2">
                            Motivo / Descripción <span class="text-red-600">*</span>
                        </label>
                        <textarea
                            name="motivo_contingencia"
                            rows="4"
                            required
                            minlength="10"
                            maxlength="500"
                            class="w-full border border-slate-200 rounded-lg px-4 py-2 focus:ring-2 focus:ring-red-500 focus:border-red-500"
                            placeholder="Describe el motivo de la contingencia (mínimo 10 caracteres, máximo 500)"
                        >{{ old('motivo_contingencia') }}</textarea>
                        <p class="text-sm text-slate-500 mt-1">Ejemplo: "Caída del servicio de internet por mantenimiento programado del proveedor"</p>
                        @error('motivo_contingencia')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Información adicional -->
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                        <h3 class="text-sm font-semibold text-blue-900 mb-2">📋 ¿Qué sucederá al activar la contingencia?</h3>
                        <ul class="text-sm text-blue-800 space-y-1 list-disc list-inside">
                            <li>Se creará un evento de contingencia con fecha y hora de inicio</li>
                            <li>Todas las facturas nuevas se marcarán como "en contingencia"</li>
                            <li>Los DTEs se firmarán localmente pero NO se enviarán al MH</li>
                            <li>Podrás seguir facturando normalmente (hasta 100 DTEs por lote)</li>
                            <li>Al restablecer la conexión, deberás cerrar y procesar la contingencia</li>
                        </ul>
                    </div>

                    <!-- Botones -->
                    <div class="flex gap-3">
                        <a href="{{ route('contingencias.index') }}" class="flex-1 bg-slate-200 hover:bg-gray-300 text-slate-800 font-semibold px-6 py-3 rounded-lg text-center">
                            Cancelar
                        </a>
                        <button type="submit" class="flex-1 bg-red-600 hover:bg-red-700 text-white font-semibold px-6 py-3 rounded-lg flex items-center justify-center gap-2">
                            <span>🚨</span>
                            <span>Activar Contingencia</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
