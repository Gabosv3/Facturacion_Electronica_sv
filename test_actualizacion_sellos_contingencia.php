<?php

/**
 * Script de prueba para verificar actualización de sellos en contingencias
 *
 * Uso:
 * php test_actualizacion_sellos_contingencia.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\EventoContingencia;
use App\Models\Factura;

echo "=== VERIFICACIÓN DE ACTUALIZACIÓN DE SELLOS EN CONTINGENCIAS ===\n\n";

// 1. Buscar evento de contingencia procesado más reciente
echo "1. Buscando evento de contingencia procesado...\n";
$evento = EventoContingencia::where('estado', 'procesado')
    ->orderBy('id', 'desc')
    ->first();

if (!$evento) {
    echo "❌ No hay eventos de contingencia procesados\n";
    echo "💡 Primero procesa una contingencia con: php artisan contingencia:procesar\n";
    exit(1);
}

echo "✅ Evento encontrado:\n";
echo "   ID: {$evento->id}\n";
echo "   Código: {$evento->codigo_evento}\n";
echo "   Estado: {$evento->estado}\n";
echo "   Código de lote: {$evento->codigo_lote}\n\n";

// 2. Obtener facturas del evento
echo "2. Verificando facturas del evento...\n";
$facturas = $evento->facturas;

if ($facturas->isEmpty()) {
    echo "❌ El evento no tiene facturas asociadas\n";
    exit(1);
}

echo "✅ Facturas encontradas: {$facturas->count()}\n\n";

// 3. Verificar cada factura
echo "3. Verificando estado y sellos de cada factura:\n\n";

$conSello = 0;
$sinSello = 0;
$estadoEnviado = 0;

foreach ($facturas as $factura) {
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "📄 Factura ID: {$factura->id}\n";
    echo "   Número de control: {$factura->numero_control}\n";
    echo "   Código de generación: {$factura->codigo_generacion}\n";
    echo "   Estado: {$factura->estado}\n";

    // Verificar estado
    if ($factura->estado === 'enviado_mh') {
        echo "   ✅ Estado correcto: enviado_mh\n";
        $estadoEnviado++;
    } else {
        echo "   ❌ Estado incorrecto: {$factura->estado} (debería ser 'enviado_mh')\n";
    }

    // Verificar sello de recepción
    $respuestaMh = $factura->respuesta_mh;

    if (is_string($respuestaMh)) {
        $respuestaMh = json_decode($respuestaMh, true);
    }

    if (isset($respuestaMh['envio_mh']['selloRecibido'])) {
        $sello = $respuestaMh['envio_mh']['selloRecibido'];
        echo "   ✅ Sello de recepción: " . substr($sello, 0, 30) . "...\n";

        // Verificar metadata adicional
        if (isset($respuestaMh['envio_mh']['via_contingencia'])) {
            echo "   ✅ Marcado como vía contingencia\n";
        }

        if (isset($respuestaMh['envio_mh']['codigo_lote'])) {
            echo "   ✅ Código de lote: {$respuestaMh['envio_mh']['codigo_lote']}\n";
        }

        $conSello++;
    } else {
        echo "   ❌ Sin sello de recepción\n";
        $sinSello++;
    }

    echo "\n";
}

// 4. Resumen
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "📊 RESUMEN\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "Total de facturas: {$facturas->count()}\n";
echo "Con sello de recepción: {$conSello}\n";
echo "Sin sello de recepción: {$sinSello}\n";
echo "Con estado 'enviado_mh': {$estadoEnviado}\n\n";

if ($conSello === $facturas->count() && $estadoEnviado === $facturas->count()) {
    echo "✅ ÉXITO: Todas las facturas se actualizaron correctamente\n";
    echo "🎉 La funcionalidad está funcionando perfectamente\n";
} else {
    echo "⚠️ ADVERTENCIA: Algunas facturas no se actualizaron correctamente\n";
    echo "💡 Verifica los logs en storage/logs/laravel.log para más detalles\n";
}

// 5. Verificar detalle del lote
echo "\n5. Verificando detalle del lote guardado...\n";
if ($evento->detalle_lote) {
    $detalle = $evento->detalle_lote;

    if (isset($detalle['respuestaDTE'])) {
        $cantidadRespuestas = count($detalle['respuestaDTE']);
        echo "✅ Detalle del lote contiene {$cantidadRespuestas} respuestas\n";

        // Mostrar primera respuesta como ejemplo
        if ($cantidadRespuestas > 0) {
            echo "\n📋 Ejemplo de respuesta del MH (primera del lote):\n";
            $primeraRespuesta = $detalle['respuestaDTE'][0];
            echo "   Código generación: {$primeraRespuesta['codigoGeneracion']}\n";
            echo "   Sello recibido: " . substr($primeraRespuesta['selloRecibido'] ?? 'N/A', 0, 30) . "...\n";
            echo "   Estado: " . ($primeraRespuesta['estado'] ?? 'N/A') . "\n";
            echo "   Fecha procesamiento: " . ($primeraRespuesta['fhProcesamiento'] ?? 'N/A') . "\n";
        }
    } else {
        echo "⚠️ El detalle del lote no tiene estructura esperada\n";
        echo "   Keys disponibles: " . implode(', ', array_keys($detalle)) . "\n";
    }
} else {
    echo "❌ El evento no tiene detalle del lote guardado\n";
}

echo "\n=== FIN DE LA VERIFICACIÓN ===\n";