<?php

/**
 * Script de prueba para verificar la estructura JSON COMPLETA de invalidación
 * con TODOS los campos requeridos según errores del MH
 *
 * ERRORES CORREGIDOS:
 * - Campo montoIva es requerido en #/documento
 * - Campo codigoGeneracionR es requerido en #/documento (si tipo 1)
 * - Campo tipoDocumento es requerido en #/documento
 * - Campo numDocumento es requerido en #/documento
 * - Campo nombre es requerido en #/documento
 * - Campo #/documento/selloRecibido contiene un valor inválido (debe ser string, no null)
 * - Campo #/motivo contiene un valor inválido (debe ser 1, 2 o 3 según CAT-24)
 */

// Ejemplo de datos
$codigoInvalidacion = '111E7C94-7F4E-4F4E-B899-780F3BE492BD';
$codigoGeneracionFactura = 'C0072834-026D-4402-99D2-D221495113FC';
$codigoGeneracionReemplazo = 'D1234567-89AB-CDEF-0123-456789ABCDEF'; // Solo si tipo 1
$numeroControlFactura = 'DTE-01-S001P001-000000000000097';
$fechaEmisionFactura = '2025-11-04';
$fechaEvento = '2025-11-05';
$horaEvento = '20:29:51';

// Datos del receptor de la factura original
$receptorTipoDocumento = '13'; // DUI
$receptorNumDocumento = '060062101'; // Sin guión
$receptorNombre = 'carlos posada';

// Montos de la factura
$montoIva = 1.15; // Ejemplo: IVA de la factura

// Sello recibido del MH (DEBE SER STRING, NO NULL)
$selloRecibido = 'SELLO-EJEMPLO-MH-12345'; // Si no tiene, usar string vacío ''

// Tipo de invalidación según CAT-24
$tipoInvalidacion = '2'; // 1=Error info, 2=Rescindir, 3=Otros

// Estructura COMPLETA y CORRECTA
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
        'selloRecibido' => $selloRecibido, // STRING, NO NULL
        'tipoDte' => '01',
        'numeroControl' => $numeroControlFactura,
        'fecEmi' => $fechaEmisionFactura,
        'montoIva' => $montoIva, // ✅ NUEVO CAMPO REQUERIDO
        // Datos del receptor de la factura original
        'tipoDocumento' => $receptorTipoDocumento, // ✅ NUEVO CAMPO REQUERIDO
        'numDocumento' => $receptorNumDocumento, // ✅ NUEVO CAMPO REQUERIDO
        'nombre' => $receptorNombre, // ✅ NUEVO CAMPO REQUERIDO
    ],
    'motivo' => $tipoInvalidacion, // ✅ Código 1, 2 o 3 según CAT-24
];

// Si es tipo 1 (error de información), agregar código de generación de reemplazo
if ($tipoInvalidacion === '1') {
    $jsonInvalidacion['documento']['codigoGeneracionR'] = $codigoGeneracionReemplazo;
}

echo "=== JSON DE INVALIDACIÓN (Estructura COMPLETA y Correcta) ===\n\n";
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

echo "=== CAMPOS NUEVOS AGREGADOS (según errores MH) ===\n\n";
echo "✅ documento.montoIva (float): Monto del IVA de la factura\n";
echo "✅ documento.tipoDocumento (string): Tipo de documento del receptor (13, 36, etc.)\n";
echo "✅ documento.numDocumento (string): Número de documento del receptor\n";
echo "✅ documento.nombre (string): Nombre del receptor\n";
echo "✅ documento.codigoGeneracionR (string): Código de reemplazo (solo si tipo 1)\n";
echo "✅ documento.selloRecibido (string): Debe ser STRING, no null\n";
echo "✅ motivo (string): Código según CAT-24: '1', '2' o '3'\n";
echo "\n";

echo "=== CATÁLOGO CAT-24: TIPO DE INVALIDACIÓN ===\n\n";
echo "Según 'Catalogos- Sistema de transmision - 1.2' del MH:\n\n";
echo "1 = Error de información del documento tributario electrónico a invalidar\n";
echo "    - Requiere documento de reemplazo\n";
echo "    - Debe incluir codigoGeneracionR en documento\n";
echo "    - Ejemplo: Error en datos del cliente, error en precio\n\n";
echo "2 = Rescindir operación realizada\n";
echo "    - NO requiere documento de reemplazo\n";
echo "    - NO incluir codigoGeneracionR\n";
echo "    - Ejemplo: Cancelación total de venta\n\n";
echo "3 = Otros motivos\n";
echo "    - Casos no cubiertos por 1 o 2\n";
echo "    - Verificar si requiere documento de reemplazo según caso\n\n";

echo "=== VALIDACIÓN DE SELLO RECIBIDO ===\n\n";
echo "El campo 'selloRecibido' debe ser un STRING.\n";
echo "Si la factura no tiene sello (no debería pasar si estado='enviado_mh'):\n";
echo "  ❌ NO usar: null\n";
echo "  ✅ Usar: '' (string vacío)\n";
echo "  ✅ Mejor: Obtener el sello real de respuesta_mh.envio_mh.selloRecibido\n\n";

echo "=== DATOS DEL RECEPTOR EN DOCUMENTO ===\n\n";
echo "El objeto 'documento' DEBE incluir los datos del receptor de la factura original:\n";
echo "  - tipoDocumento: Mismo tipo que en la factura (13=DUI, 36=NIT, etc.)\n";
echo "  - numDocumento: Mismo número (SIN GUIÓN si es DUI)\n";
echo "  - nombre: Mismo nombre del receptor\n";
echo "  - montoIva: IVA de la factura original\n\n";

echo "=== DIFERENCIA CON ESTRUCTURA ANTERIOR ===\n\n";
echo "ANTES (incompleta):\n";
echo "  documento: {\n";
echo "    codigoGeneracion, selloRecibido, tipoDte, numeroControl, fecEmi\n";
echo "  }\n\n";
echo "AHORA (completa):\n";
echo "  documento: {\n";
echo "    codigoGeneracion, selloRecibido, tipoDte, numeroControl, fecEmi,\n";
echo "    montoIva,                    ← NUEVO\n";
echo "    tipoDocumento,               ← NUEVO (del receptor)\n";
echo "    numDocumento,                ← NUEVO (del receptor)\n";
echo "    nombre,                      ← NUEVO (del receptor)\n";
echo "    codigoGeneracionR (si tipo 1) ← NUEVO (documento reemplazo)\n";
echo "  }\n\n";

echo "=== EJEMPLO DE CADA TIPO ===\n\n";

// Tipo 1: Error de información
$ejemploTipo1 = $jsonInvalidacion;
$ejemploTipo1['motivo'] = '1';
$ejemploTipo1['documento']['codigoGeneracionR'] = $codigoGeneracionReemplazo;

echo "TIPO 1 - Error de información (requiere reemplazo):\n";
echo json_encode($ejemploTipo1, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
echo "\n\n";

// Tipo 2: Rescindir
$ejemploTipo2 = $jsonInvalidacion;
$ejemploTipo2['motivo'] = '2';
unset($ejemploTipo2['documento']['codigoGeneracionR']); // No incluir si es tipo 2

echo "TIPO 2 - Rescindir operación (sin reemplazo):\n";
echo json_encode($ejemploTipo2, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
echo "\n\n";

// Tipo 3: Otros
$ejemploTipo3 = $jsonInvalidacion;
$ejemploTipo3['motivo'] = '3';
unset($ejemploTipo3['documento']['codigoGeneracionR']);

echo "TIPO 3 - Otros motivos:\n";
echo json_encode($ejemploTipo3, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
echo "\n\n";
