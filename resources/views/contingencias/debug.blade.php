<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DEBUG - Evento #{{ $evento->id }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    @include('components.navbar')

    <div class="max-w-4xl mx-auto p-8">
        <h1 class="text-2xl font-bold mb-6">🔍 DEBUG - Evento de Contingencia #{{ $evento->id }}</h1>

        <!-- Información del Evento -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h2 class="text-xl font-bold mb-4">Datos del Evento</h2>
            <pre class="bg-gray-100 p-4 rounded overflow-x-auto">{{ json_encode($evento->toArray(), JSON_PRETTY_PRINT) }}</pre>
        </div>

        <!-- Facturas Cargadas -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h2 class="text-xl font-bold mb-4">Facturas Cargadas en la Relación</h2>
            <p class="mb-2"><strong>Count:</strong> {{ $evento->facturas->count() }}</p>
            <pre class="bg-gray-100 p-4 rounded overflow-x-auto">{{ json_encode($evento->facturas->toArray(), JSON_PRETTY_PRINT) }}</pre>
        </div>

        <!-- Facturas desde Query Fresco -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h2 class="text-xl font-bold mb-4">Facturas desde Query Fresco</h2>
            <p class="mb-2"><strong>Count:</strong> {{ $facturasFrescas->count() }}</p>
            <pre class="bg-gray-100 p-4 rounded overflow-x-auto">{{ json_encode($facturasFrescas->toArray(), JSON_PRETTY_PRINT) }}</pre>
        </div>

        <!-- Query SQL Raw -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-bold mb-4">Query SQL Directo</h2>
            <p class="mb-2"><strong>Count:</strong> {{ $countDirecto }}</p>
            <p class="text-sm text-gray-600 mb-2">SELECT COUNT(*) FROM facturas WHERE evento_contingencia_id = {{ $evento->id }}</p>
        </div>

        <div class="mt-6">
            <a href="{{ route('contingencias.show', $evento) }}" class="bg-blue-600 text-white px-6 py-3 rounded-lg inline-block">
                ← Volver a Vista Normal
            </a>
        </div>
    </div>
</body>
</html>
