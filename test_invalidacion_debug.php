<?php

/**
 * Script de prueba para diagnosticar problemas con la invalidación
 *
 * Uso:
 * php test_invalidacion_debug.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Factura;
use App\Models\EventoInvalidacion;
use App\Services\InvalidacionService;

echo "=== DEBUG INVALIDACIÓN ===\n\n";

// 1. Buscar una factura para invalidar
echo "1. Buscando facturas invalidables...\n";
$factura = Factura::where('estado', 'enviado_mh')
    ->where('invalidada', false)
    ->first();

if (!$factura) {
    echo "❌ No hay facturas con estado 'enviado_mh' disponibles para invalidar.\n";
    echo "   Crea una factura primero en la interfaz web.\n";
    exit(1);
}

echo "✅ Factura encontrada:\n";
echo "   ID: {$factura->id}\n";
echo "   Número Control: {$factura->numero_control}\n";
echo "   Código Generación: {$factura->codigo_generacion}\n";
echo "   Tipo DTE: {$factura->tipo_dte}\n";
echo "   Estado: {$factura->estado}\n";
echo "   Fecha Emisión: {$factura->fecha_emision}\n";
echo "   Días desde emisión: " . $factura->fecha_emision->diffInDays(now()) . "\n";

// 2. Verificar sello de recepción
echo "\n2. Verificando sello de recepción del MH...\n";
if (!$factura->respuesta_mh) {
    echo "⚠️  No hay respuesta_mh guardada\n";
} else {
    $selloRecibido = null;

    if (isset($factura->respuesta_mh['selloRecibido'])) {
        $selloRecibido = $factura->respuesta_mh['selloRecibido'];
    } elseif (isset($factura->respuesta_mh['data']['selloRecibido'])) {
        $selloRecibido = $factura->respuesta_mh['data']['selloRecibido'];
    } elseif (isset($factura->respuesta_mh['body']['selloRecibido'])) {
        $selloRecibido = $factura->respuesta_mh['body']['selloRecibido'];
    }

    if ($selloRecibido) {
        echo "✅ Sello recibido encontrado: " . substr($selloRecibido, 0, 50) . "...\n";
    } else {
        echo "❌ No se encontró sello recibido en respuesta_mh\n";
        echo "   Tipo de respuesta_mh: " . gettype($factura->respuesta_mh) . "\n";
        if (is_array($factura->respuesta_mh)) {
            echo "   Estructura de respuesta_mh:\n";
            print_r(array_keys($factura->respuesta_mh));
        } else {
            echo "   Valor de respuesta_mh: " . substr($factura->respuesta_mh, 0, 200) . "...\n";
        }
    }
}

// 3. Validar si puede invalidarse
echo "\n3. Validando si puede invalidarse...\n";
$service = app(InvalidacionService::class);
$validacion = $service->validarInvalidacion($factura->id);

if ($validacion['puede_invalidarse']) {
    echo "✅ {$validacion['mensaje']}\n";
} else {
    echo "❌ {$validacion['mensaje']}\n";
    exit(1);
}

// 4. Construir JSON de evento de prueba (sin guardar)
echo "\n4. Construyendo JSON de evento de prueba...\n";

$eventoTemp = new EventoInvalidacion([
    'factura_id' => $factura->id,
    'codigo_invalidacion' => 'TEST-' . strtoupper(Illuminate\Support\Str::uuid()->toString()),
    'version' => 1,
    'ambiente' => config('dte.ambiente'),
    'fecha_evento' => now(),
    'tipo_invalidacion' => '2',
    'motivo_invalidacion' => 'Prueba de invalidación - Script de debug',
    'responsable_nombre' => 'Test Usuario',
    'responsable_tipo_doc' => '13',
    'responsable_num_doc' => '012345678',
    'solicitante_nombre' => 'Test Solicitante',
    'solicitante_tipo_doc' => '13',
    'solicitante_num_doc' => '087654321',
]);

$eventoTemp->factura = $factura;

try {
    $jsonEvento = $service->construirJsonEvento($eventoTemp);
    echo "✅ JSON construido correctamente\n";
    echo "   Estructura:\n";
    print_r(array_keys($jsonEvento));

    echo "\n   Identificación:\n";
    print_r($jsonEvento['identificacion']);

    echo "\n   Documento:\n";
    print_r($jsonEvento['documento']);

} catch (Exception $e) {
    echo "❌ Error al construir JSON: {$e->getMessage()}\n";
    exit(1);
}

// 5. Verificar configuración de API de firma
echo "\n5. Verificando configuración de API de firma...\n";
$apiUrl = config('dte.api_url');
$nit = config('dte.nit');
$passwordPri = config('dte.password_pri');

echo "   API URL: {$apiUrl}\n";
echo "   NIT: {$nit}\n";
echo "   Password PRI: " . (empty($passwordPri) ? '❌ NO CONFIGURADO' : '✅ Configurado') . "\n";

// 6. Verificar eventos de invalidación previos
echo "\n6. Verificando eventos de invalidación previos...\n";
$eventosCount = EventoInvalidacion::count();
echo "   Total eventos: {$eventosCount}\n";

if ($eventosCount > 0) {
    $ultimoEvento = EventoInvalidacion::latest()->first();
    echo "   Último evento:\n";
    echo "   - ID: {$ultimoEvento->id}\n";
    echo "   - Código: {$ultimoEvento->codigo_invalidacion}\n";
    echo "   - Estado: {$ultimoEvento->estado}\n";
    echo "   - Tipo evento_firmado: " . gettype($ultimoEvento->evento_firmado) . "\n";

    if (is_array($ultimoEvento->evento_firmado)) {
        echo "   ⚠️  PROBLEMA DETECTADO: evento_firmado es un array (debería ser string)\n";
        echo "   - Contenido:\n";
        print_r($ultimoEvento->evento_firmado);
    } elseif (is_string($ultimoEvento->evento_firmado)) {
        echo "   ✅ evento_firmado es string (JWT)\n";
        echo "   - Longitud: " . strlen($ultimoEvento->evento_firmado) . " caracteres\n";
        echo "   - Primeros 100 caracteres: " . substr($ultimoEvento->evento_firmado, 0, 100) . "...\n";
    }
}

echo "\n=== FIN DEBUG ===\n";
echo "\nPara proceder con la invalidación real, usa:\n";
echo "php artisan tinker\n";
echo "O accede a: http://localhost:8000/invalidaciones/seleccionar\n";
