<?php

/**
 * Script para probar la API de firma directamente
 *
 * Uso:
 * php test_api_firma.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Factura;
use App\Services\InvalidacionService;
use Illuminate\Support\Facades\Http;

echo "=== TEST API DE FIRMA ===\n\n";

// 1. Obtener factura
echo "1. Obteniendo factura...\n";
$factura = \App\Models\Factura::where('estado', 'enviado_mh')
    ->where('invalidada', false)
    ->first();

if (!$factura) {
    echo "❌ No hay facturas disponibles\n";
    exit(1);
}

echo "✅ Factura ID: {$factura->id}\n";
echo "   Número: {$factura->numero_control}\n\n";

// 2. Construir evento
echo "2. Construyendo evento de invalidación...\n";

$service = app(InvalidacionService::class);

$eventoTemp = new \App\Models\EventoInvalidacion([
    'factura_id' => $factura->id,
    'codigo_invalidacion' => 'TEST-' . strtoupper(\Illuminate\Support\Str::uuid()->toString()),
    'version' => 1,
    'ambiente' => config('dte.ambiente'),
    'fecha_evento' => now(),
    'tipo_invalidacion' => '2',
    'motivo_invalidacion' => 'Prueba de invalidación - Test API',
    'responsable_nombre' => 'Test Usuario',
    'responsable_tipo_doc' => '13',
    'responsable_num_doc' => '012345678',
    'solicitante_nombre' => 'Test Solicitante',
    'solicitante_tipo_doc' => '13',
    'solicitante_num_doc' => '087654321',
]);

$eventoTemp->factura = $factura;

// Construir JSON usando reflection para acceder al método privado
$reflection = new ReflectionClass($service);
$method = $reflection->getMethod('construirJsonEvento');
$method->setAccessible(true);
$jsonEvento = $method->invoke($service, $eventoTemp);

echo "✅ JSON construido\n";
echo "   Tamaño: " . strlen(json_encode($jsonEvento)) . " bytes\n\n";

// 3. Mostrar JSON
echo "3. JSON del evento:\n";
echo json_encode($jsonEvento, JSON_PRETTY_PRINT) . "\n\n";

// 4. Enviar a API de firma
echo "4. Enviando a API de firma...\n";

$apiUrl = config('dte.api_url');
$nit = config('dte.nit');
$passwordPri = config('dte.password_pri');

echo "   URL: {$apiUrl}/firmardocumento/\n";
echo "   NIT: {$nit}\n\n";

$payload = [
    'nit' => $nit,
    'activo' => true,  // Boolean, no string
    'passwordPri' => $passwordPri,
    'dteJson' => $jsonEvento,  // Array directo, NO json_encode()
];

echo "5. Payload enviado:\n";
echo "   - nit: {$nit}\n";
echo "   - activo: true (boolean)\n";
echo "   - passwordPri: " . (empty($passwordPri) ? 'VACÍO' : 'OK') . "\n";
echo "   - dteJson (tipo): " . gettype($payload['dteJson']) . "\n";
echo "   - dteJson (tamaño JSON): " . strlen(json_encode($payload['dteJson'])) . " bytes\n\n";

try {
    $response = Http::timeout(30)
        ->withOptions([
            'verify' => config('app.env') === 'production',
        ])
        ->post($apiUrl . '/firmardocumento/', $payload);

    echo "6. Respuesta de API:\n";
    echo "   Status: {$response->status()}\n";
    echo "   Successful: " . ($response->successful() ? 'SÍ' : 'NO') . "\n\n";

    if ($response->successful()) {
        $data = $response->json();

        echo "7. Datos de respuesta:\n";
        echo "   Keys: " . implode(', ', array_keys($data)) . "\n";
        echo "   Tiene 'body': " . (isset($data['body']) ? 'SÍ' : 'NO') . "\n";

        if (isset($data['body'])) {
            echo "   Tipo de 'body': " . gettype($data['body']) . "\n";

            if (is_array($data['body'])) {
                echo "\n   ⚠️ PROBLEMA: body es un ARRAY\n";
                echo "   Contenido de body:\n";
                print_r($data['body']);
            } elseif (is_string($data['body'])) {
                echo "\n   ✅ CORRECTO: body es un STRING (JWT)\n";
                echo "   Longitud: " . strlen($data['body']) . " caracteres\n";
                echo "   Primeros 200 caracteres:\n";
                echo "   " . substr($data['body'], 0, 200) . "\n";

                // Verificar si es un JWT válido (formato xxx.yyy.zzz)
                $parts = explode('.', $data['body']);
                echo "\n   Partes del JWT: " . count($parts) . " (esperado: 3)\n";
            }
        }

        echo "\n8. Respuesta completa:\n";
        echo json_encode($data, JSON_PRETTY_PRINT) . "\n";
    } else {
        echo "❌ Error HTTP {$response->status()}\n";
        echo "Respuesta:\n";
        echo $response->body() . "\n";
    }
} catch (Exception $e) {
    echo "❌ Excepción: {$e->getMessage()}\n";
    echo "Trace:\n{$e->getTraceAsString()}\n";
}

echo "\n=== FIN ===\n";
