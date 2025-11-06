# Corrección de la Estructura de Invalidación

## Fecha: 2025-11-05

## Problema Detectado

El sistema de invalidación estaba enviando una estructura JSON **incorrecta** al Ministerio de Hacienda, lo que causaba el error:

```json
{
    "estado": "RECHAZADO",
    "codigoMsg": "096",
    "descripcionMsg": "DOCUMENTO NO CUMPLE ESQUEMA JSON",
    "observaciones": [
        "Campo documento es requerido en #",
        "Campo motivo es requerido en #",
        "Campo receptor no esta permitido en #",
        "Campo documentoAnulado no esta permitido en #",
        "Campo fecAnula es requerido en #/identificacion",
        "Campo horAnula es requerido en #/identificacion",
        ...
    ]
}
```

## Cambios Realizados

### 1. InvalidacionService.php - Método `construirJsonEvento()`

**ANTES (Estructura Incorrecta):**
```php
return [
    'identificacion' => [
        'version' => 1,
        'codigoGeneracionDocumento' => ...,
        'tipoEvento' => ...,
        'descripcionEvento' => ...,
        'motivoAnulacion' => ...,
        'fecEmiAnulacion' => ...,
        'horEmiAnulacion' => ...,
    ],
    'emisor' => [
        'nit' => ...,
        'nombre' => ...,
        'nrc' => ...,
        'nombreComercial' => ...,
        'correo' => ...,
        'telefono' => ...,
    ],
    'documentoAnulado' => [...],
    'receptor' => [...],
];
```

**DESPUÉS (Estructura Correcta):**
```php
return [
    'identificacion' => [
        'version' => 2,              // ✅ Versión 2
        'ambiente' => '00',
        'codigoGeneracion' => ...,
        'fecAnula' => ...,           // ✅ fecAnula (NO fecEmiAnulacion)
        'horAnula' => ...,           // ✅ horAnula (NO horEmiAnulacion)
    ],
    'emisor' => [
        'nit' => ...,
        'nombre' => ...,
        'tipoEstablecimiento' => '01',  // ✅ NUEVO campo requerido
        'codEstable' => '0000',         // ✅ NUEVO campo requerido
        'codPuntoVenta' => '0000',      // ✅ NUEVO campo requerido
        'nomEstablecimiento' => ...,    // ✅ NUEVO campo requerido
        'correo' => ...,
        'telefono' => ...,
        // ❌ REMOVIDO: nrc, nombreComercial
    ],
    'documento' => [...],           // ✅ documento (NO documentoAnulado)
    'motivo' => '...',             // ✅ motivo (NO motivoAnulacion)
    // ❌ REMOVIDO: receptor
];
```

### 2. InvalidacionService.php - Método `crearEvento()`

**Cambio de versión:**
```php
// ANTES
'version' => 1,

// DESPUÉS
'version' => 2, // Versión 2 para eventos de invalidación según MH
```

## Campos de la Estructura Correcta

### identificacion
```json
{
    "version": 2,
    "ambiente": "00",
    "codigoGeneracion": "UUID-DEL-EVENTO",
    "fecAnula": "2025-11-04",
    "horAnula": "22:12:47"
}
```

**Campos requeridos:**
- ✅ `version` (int): Siempre 2
- ✅ `ambiente` (string): "00" o "01"
- ✅ `codigoGeneracion` (string): UUID del evento de invalidación
- ✅ `fecAnula` (string): Fecha de invalidación "YYYY-MM-DD"
- ✅ `horAnula` (string): Hora de invalidación "HH:MM:SS"

**Campos NO permitidos:**
- ❌ `tipoEvento`
- ❌ `descripcionEvento`
- ❌ `motivoAnulacion`
- ❌ `fecEmiAnulacion`
- ❌ `horEmiAnulacion`
- ❌ `codigoGeneracionDocumento`

### emisor
```json
{
    "nit": "06142302881449",
    "nombre": "Taller González",
    "tipoEstablecimiento": "01",
    "codEstable": "0000",
    "codPuntoVenta": "0000",
    "nomEstablecimiento": "Taller González",
    "correo": "jonagozalez23@gmail.com",
    "telefono": "64404895"
}
```

**Campos requeridos:**
- ✅ `nit` (string): NIT del emisor
- ✅ `nombre` (string): Nombre del emisor
- ✅ `tipoEstablecimiento` (string): "01" = Casa Matriz, "02" = Sucursal, "04" = Punto de Venta
- ✅ `codEstable` (string): Código del establecimiento
- ✅ `codPuntoVenta` (string): Código del punto de venta
- ✅ `nomEstablecimiento` (string): Nombre del establecimiento
- ✅ `correo` (string): Email del emisor
- ✅ `telefono` (string): Teléfono del emisor

**Campos NO permitidos:**
- ❌ `nombreComercial`
- ❌ `nrc`

### documento
```json
{
    "codigoGeneracion": "C0072834-026D-4402-99D2-D221495113FC",
    "selloRecibido": null,
    "tipoDte": "01",
    "numeroControl": "DTE-01-S001P001-000000000000097",
    "fecEmi": "2025-11-04"
}
```

**Nombre del campo:** `documento` (NO `documentoAnulado`)

**Campos requeridos:**
- ✅ `codigoGeneracion` (string): UUID de la factura a anular
- ✅ `selloRecibido` (string|null): Sello recibido del MH al enviar la factura
- ✅ `tipoDte` (string): "01", "03", "05", "14"
- ✅ `numeroControl` (string): Número de control de la factura
- ✅ `fecEmi` (string): Fecha de emisión de la factura "YYYY-MM-DD"

### motivo
```json
"motivo": "devolucion"
```

**Nombre del campo:** `motivo` (NO `motivoAnulacion`)

**Tipo:** String con la descripción del motivo de invalidación

### receptor
❌ **NO SE DEBE INCLUIR** - El MH no permite este campo en eventos de invalidación

## Flujo de Proceso Corregido

### 1. Construcción del JSON
```php
$jsonEvento = $invalidacionService->construirJsonEvento($evento);
// Genera estructura correcta con version=2
```

### 2. Firma del Evento
```
POST http://localhost:8113/firmardocumento/

Body:
{
    "nit": "06142302881449",
    "activo": true,
    "passwordPri": "...",
    "dteJson": {
        "identificacion": {...},
        "emisor": {...},
        "documento": {...},
        "motivo": "..."
    }
}

Response:
{
    "body": "eyJhbGc...JWT-FIRMADO..."
}
```

### 3. Envío al MH
```
POST https://apitest.dtes.mh.gob.sv/fesv/anulardte

Headers:
  Authorization: Bearer {TOKEN-MH}
  Content-Type: application/json

Body:
{
    "ambiente": "00",
    "idEnvio": 1,
    "version": 2,
    "documento": "eyJhbGc...JWT-FIRMADO..."
}

Response Exitosa:
{
    "version": 2,
    "ambiente": "00",
    "versionApp": 2,
    "estado": "PROCESADO",
    "codigoGeneracion": "...",
    "selloRecibido": "...",
    "fhProcesamiento": "05/11/2025 20:14:33"
}
```

## Archivos Modificados

1. **app/Services/InvalidacionService.php**
   - Método `crearEvento()`: Versión cambiada de 1 a 2
   - Método `construirJsonEvento()`: Estructura completamente reescrita

2. **test_invalidacion_estructura.php** (NUEVO)
   - Script de prueba para verificar estructura correcta

3. **CAMBIOS_INVALIDACION.md** (ESTE ARCHIVO)
   - Documentación de los cambios

## Verificación

Para verificar que la estructura es correcta, ejecutar:

```bash
php test_invalidacion_estructura.php
```

Esto mostrará:
- ✅ JSON con estructura correcta
- ✅ Payload para API de firma
- ✅ Estructura para envío al MH
- ✅ Lista de cambios realizados
- ✅ Campos requeridos y no permitidos

## Próximos Pasos

1. ✅ Probar la invalidación de una factura de prueba
2. ✅ Verificar que el MH acepta el formato
3. ✅ Verificar que se marca correctamente la factura como invalidada
4. ✅ Actualizar documentación en CLAUDE.md

## Diferencias Clave

| Aspecto | Antes (Incorrecto) | Después (Correcto) |
|---------|-------------------|-------------------|
| **Versión** | 1 | 2 |
| **Fecha/Hora** | `fecEmiAnulacion`, `horEmiAnulacion` | `fecAnula`, `horAnula` |
| **Documento** | `documentoAnulado` | `documento` |
| **Motivo** | `motivoAnulacion` | `motivo` |
| **Receptor** | Incluido ✓ | No incluido ✗ |
| **Emisor NRC** | Incluido ✓ | No incluido ✗ |
| **Emisor Comercial** | Incluido ✓ | No incluido ✗ |
| **Tipo Establecimiento** | No incluido ✗ | Incluido ✓ |
| **Cod Estable** | No incluido ✗ | Incluido ✓ |
| **Cod Punto Venta** | No incluido ✗ | Incluido ✓ |
| **Nom Establecimiento** | No incluido ✗ | Incluido ✓ |

## Notas Importantes

1. **Versión siempre 2**: Los eventos de invalidación usan versión 2, independientemente de la versión del DTE original
2. **Sin receptor**: El MH no requiere ni permite información del receptor en eventos de invalidación
3. **Establecimiento requerido**: A diferencia de las facturas, los eventos de invalidación requieren información completa del establecimiento
4. **Sello opcional**: El campo `selloRecibido` puede ser `null` si la factura no tiene sello (aunque debería tenerlo si fue enviada al MH)
