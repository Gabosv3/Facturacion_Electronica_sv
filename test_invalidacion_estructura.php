<?php

/**
 * Script de prueba para verificar la estructura JSON de invalidación
 *
 * Este script genera el JSON de invalidación con la estructura CORRECTA
 * según las especificaciones del MH
 */

// Ejemplo de datos
$codigoInvalidacion = 'BCC0096C-7F1E-4A0D-AB25-CED07E2B8820';
$codigoGeneracionFactura = 'C0072834-026D-4402-99D2-D221495113FC';
$selloRecibidoFactura = null; // Normalmente viene de respuesta_mh
$numeroControlFactura = 'DTE-01-S001P001-000000000000097';
$fechaEmisionFactura = '2025-11-04';
$fechaEvento = '2025-11-04';
$horaEvento = '22:12:47';

// Estructura CORRECTA según errores del MH
$jsonInvalidacion = [
    'identificacion' => [
        'version' => 2,
        'ambiente' => '00',
        'codigoGeneracion' => $codigoInvalidacion,
        'fecAnula' => $fechaEvento,
        'horAnula' => $horaEvento,
    ],
    'emisor' => [
        'nit' => '06142302881449',
        'nombre' => 'Taller González',
        'tipoEstablecimiento' => '01', // 01=Casa Matriz
        'codEstable' => '0000',
        'codPuntoVenta' => '0000',
        'nomEstablecimiento' => 'Taller González',
        'correo' => 'jonagozalez23@gmail.com',
        'telefono' => '64404895',
    ],
    'documento' => [
        'codigoGeneracion' => $codigoGeneracionFactura,
        'selloRecibido' => $selloRecibidoFactura,
        'tipoDte' => '01',
        'numeroControl' => $numeroControlFactura,
        'fecEmi' => $fechaEmisionFactura,
    ],
    'motivo' => 'devolucion',
];

echo "=== JSON DE INVALIDACIÓN (Estructura Correcta) ===\n\n";
echo json_encode($jsonInvalidacion, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
echo "\n\n";

echo "=== PAYLOAD PARA API DE FIRMA ===\n\n";
$payloadFirma = [
    'nit' => '06142302881449',
    'activo' => true,
    'passwordPri' => "FSJ6|P.48'NpS&+n~]\\g",
    'dteJson' => $jsonInvalidacion,
];

echo json_encode($payloadFirma, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
echo "\n\n";

echo "=== CAMBIOS REALIZADOS ===\n\n";
echo "✅ 'identificacion.fecAnula' y 'horAnula' (NO fecEmiAnulacion/horEmiAnulacion)\n";
echo "✅ 'documento' (NO documentoAnulado)\n";
echo "✅ 'motivo' (NO motivoAnulacion)\n";
echo "✅ 'emisor' incluye: tipoEstablecimiento, codEstable, codPuntoVenta, nomEstablecimiento\n";
echo "✅ 'emisor' NO incluye: nombreComercial, nrc\n";
echo "✅ NO incluye campo 'receptor'\n";
echo "✅ NO incluye en identificacion: tipoEvento, descripcionEvento, codigoGeneracionDocumento\n";
echo "✅ version = 2 (NO 1)\n";
echo "\n";

echo "=== ESTRUCTURA PARA ENVÍO AL MH ===\n\n";
echo "URL: https://apitest.dtes.mh.gob.sv/fesv/anulardte\n";
echo "Método: POST\n";
echo "Headers:\n";
echo "  - Authorization: Bearer {TOKEN-MH}\n";
echo "  - Content-Type: application/json\n\n";

$payloadMH = [
    'ambiente' => '00',
    'idEnvio' => 1,
    'version' => 2,
    'documento' => 'eyJhbGc...JWT-FIRMADO...',
];

echo "Body:\n";
echo json_encode($payloadMH, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
echo "\n\n";

echo "=== CAMPOS REQUERIDOS SEGÚN MH ===\n\n";
echo "identificacion:\n";
echo "  ✅ version (int): 2\n";
echo "  ✅ ambiente (string): '00' o '01'\n";
echo "  ✅ codigoGeneracion (string): UUID del evento\n";
echo "  ✅ fecAnula (string): 'YYYY-MM-DD'\n";
echo "  ✅ horAnula (string): 'HH:MM:SS'\n\n";

echo "emisor:\n";
echo "  ✅ nit (string): '06142302881449'\n";
echo "  ✅ nombre (string)\n";
echo "  ✅ tipoEstablecimiento (string): '01', '02', '04'\n";
echo "  ✅ codEstable (string): '0000'\n";
echo "  ✅ codPuntoVenta (string): '0000'\n";
echo "  ✅ nomEstablecimiento (string)\n";
echo "  ✅ correo (string)\n";
echo "  ✅ telefono (string)\n\n";

echo "documento:\n";
echo "  ✅ codigoGeneracion (string): UUID de la factura a anular\n";
echo "  ✅ selloRecibido (string|null): Sello del MH\n";
echo "  ✅ tipoDte (string): '01', '03', '05', '14'\n";
echo "  ✅ numeroControl (string): Número de control de la factura\n";
echo "  ✅ fecEmi (string): 'YYYY-MM-DD'\n\n";

echo "motivo:\n";
echo "  ✅ motivo (string): Descripción del motivo de invalidación\n\n";

echo "=== CAMPOS NO PERMITIDOS ===\n\n";
echo "❌ receptor (todo el objeto)\n";
echo "❌ identificacion.tipoEvento\n";
echo "❌ identificacion.descripcionEvento\n";
echo "❌ identificacion.motivoAnulacion\n";
echo "❌ identificacion.fecEmiAnulacion\n";
echo "❌ identificacion.horEmiAnulacion\n";
echo "❌ identificacion.codigoGeneracionDocumento\n";
echo "❌ emisor.nombreComercial\n";
echo "❌ emisor.nrc\n";
echo "❌ documentoAnulado (usar 'documento')\n";
echo "\n";
