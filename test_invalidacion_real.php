<?php

/**
 * Script de prueba REAL para invalidación
 *
 * Uso:
 * php test_invalidacion_real.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Factura;
use App\Services\InvalidacionService;

echo "=== PRUEBA REAL DE INVALIDACIÓN ===\n\n";

// 1. Buscar factura
echo "1. Buscando factura para invalidar...\n";
$factura = Factura::where('estado', 'enviado_mh')
    ->where('invalidada', false)
    ->first();

if (!$factura) {
    echo "❌ No hay facturas disponibles\n";
    exit(1);
}

echo "✅ Factura ID: {$factura->id}\n";
echo "   Número: {$factura->numero_control}\n";
echo "   Tipo DTE: {$factura->tipo_dte}\n\n";

// 2. Confirmar
echo "¿Estás seguro de querer invalidar esta factura? (escribe 'SI' para continuar): ";
$handle = fopen("php://stdin", "r");
$line = trim(fgets($handle));

if ($line !== 'SI') {
    echo "Operación cancelada.\n";
    exit(0);
}

echo "\n2. Procesando invalidación...\n\n";

// 3. Ejecutar invalidación
$service = app(InvalidacionService::class);

$resultado = $service->procesarInvalidacion([
    'factura_id' => $factura->id,
    'tipo_invalidacion' => '2',  // Rescindir operación
    'motivo_invalidacion' => 'Prueba de invalidación - Test del sistema',
    'factura_reemplazo_id' => null,
    'codigo_generacion_reemplazo' => null,
    'responsable_nombre' => 'Juan Pérez López',
    'responsable_tipo_doc' => '13',  // DUI
    'responsable_num_doc' => '012345678',
    'solicitante_nombre' => 'María García Martínez',
    'solicitante_tipo_doc' => '13',
    'solicitante_num_doc' => '087654321',
    'fecha_evento' => now(),
]);

echo "\n=== RESULTADO ===\n";
if ($resultado['success']) {
    echo "✅ ÉXITO!\n\n";
    $evento = $resultado['evento'];
    echo "Evento ID: {$evento->id}\n";
    echo "Código Invalidación: {$evento->codigo_invalidacion}\n";
    echo "Estado: {$evento->estado}\n";
    echo "Sello Recepción: " . ($evento->sello_recepcion ?? 'N/A') . "\n";

    // Refrescar factura
    $factura->refresh();
    echo "\nFactura invalidada: " . ($factura->invalidada ? 'SÍ' : 'NO') . "\n";

    if (isset($resultado['data'])) {
        echo "\nRespuesta del MH:\n";
        print_r($resultado['data']);
    }
} else {
    echo "❌ ERROR!\n\n";
    echo "Mensaje: {$resultado['message']}\n";

    if (isset($resultado['evento'])) {
        $evento = $resultado['evento'];
        echo "\nEvento creado pero con error:\n";
        echo "Estado: {$evento->estado}\n";
        echo "Respuesta MH:\n";
        print_r($evento->respuesta_mh);
    }
}

echo "\n=== FIN ===\n";
