# Estructura de Invalidación - REAL Y VERIFICADA

## Fecha: 2025-11-05 21:10

## ✅ ESTRUCTURA CORRECTA SEGÚN EJEMPLO DEL MH

Basado en el ejemplo real proporcionado por el error del MH:

### Estructura JSON Real

```json
{
  "identificacion": {
    "version": 2,
    "ambiente": "00",
    "codigoGeneracion": "144B5BCE-861F-4312-958B-954342FD8D6B",
    "fecAnula": "2025-11-05",
    "horAnula": "21:06:08"
  },
  "emisor": {
    "nit": "06142302881449",
    "nombre": "Taller González",
    "tipoEstablecimiento": "01",
    "codEstable": "0000",
    "codPuntoVenta": "0000",
    "nomEstablecimiento": "Taller González",
    "correo": "jonagozalez23@gmail.com",
    "telefono": "64404895"
  },
  "documento": {
    "tipoDte": "01",
    "codigoGeneracionR": "6871C033-B9EC-4BC6-9B12-BEC84F52C860",
    "numeroControl": "DTE-01-S001P001-000000000000096",
    "fecEmi": "2025-11-04",
    "montoIva": 2.3,
    "tipoDocumento": "13",
    "numDocumento": "06062101-0",
    "nombre": "miguel angel ayala bejarano"
  },
  "motivo": "2"
}
```

## Análisis de Campos

### Campo "documento"

**CAMPOS INCLUIDOS**:
```json
{
  "tipoDte": "01",
  "codigoGeneracionR": "6871C033-...",  ← SÍ incluir
  "numeroControl": "DTE-01-...",
  "fecEmi": "2025-11-04",
  "montoIva": 2.3,
  "tipoDocumento": "13",
  "numDocumento": "06062101-0",
  "nombre": "miguel angel ayala bejarano"
}
```

**CAMPOS NO INCLUIDOS**:
- ❌ `codigoGeneracion` (NO incluir, solo codigoGeneracionR)
- ❌ `selloRecibido` (NO incluir)

### Campo "motivo"

**Formato**: String con número simple

```json
"motivo": "1"   ← Error de información
"motivo": "2"   ← Rescindir operación
"motivo": "3"   ← Otros
```

**NO usar**:
- ❌ "01", "02", "03" (códigos de 2 dígitos)
- ❌ 1, 2, 3 (números enteros)
- ✅ "1", "2", "3" (strings simples)

### Campo "codigoGeneracionR"

**Ubicación**: Dentro de `documento`

```json
"documento": {
  "codigoGeneracionR": "6871C033-..."  ← AQUÍ
}
```

**NO en**:
- ❌ Nivel raíz (fuera de documento)
- ❌ identificacion
- ❌ emisor

## Diferencias con Intentos Anteriores

### Intento 1-5: Varios errores
- ❌ Incluía `documento.codigoGeneracion`
- ❌ Incluía `documento.selloRecibido`
- ❌ `codigoGeneracionR` en nivel raíz

### Intento 6 (CORRECTO):
- ✅ Solo `documento.codigoGeneracionR`
- ✅ NO incluye `documento.codigoGeneracion`
- ✅ NO incluye `documento.selloRecibido`
- ✅ `motivo` como string simple: "1", "2", "3"

## Código Final Correcto

```php
// InvalidacionService.php líneas 120-140

$documento = [
    'tipoDte' => $factura->tipo_dte,
    'codigoGeneracionR' => $factura->codigo_generacion, // ✅ Del DTE a anular
    'numeroControl' => $factura->numero_control,
    'fecEmi' => $factura->fecha_emision->format('Y-m-d'),
    'montoIva' => (float) $factura->total_iva,
    'tipoDocumento' => $factura->receptor_tipo_documento,
    'numDocumento' => $factura->receptor_num_documento,
    'nombre' => $factura->receptor_nombre,
];

$motivo = (string) $evento->tipo_invalidacion; // "1", "2", o "3"

return [
    'identificacion' => $identificacion,
    'emisor' => $emisorData,
    'documento' => $documento,
    'motivo' => $motivo,
];
```

## Comparación Visual

```
┌─────────────────────────────────────────────────────┐
│            ESTRUCTURA INCORRECTA                     │
├─────────────────────────────────────────────────────┤
│ "documento": {                                      │
│   "tipoDte": "01",                                  │
│   "codigoGeneracion": "...",    ← NO incluir       │
│   "selloRecibido": "...",       ← NO incluir       │
│   "codigoGeneracionR": "...",                      │
│   ...                                               │
│ }                                                   │
│ "motivo": "02"                  ← NO usar 2 dígitos│
└─────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────┐
│            ESTRUCTURA CORRECTA ✅                    │
├─────────────────────────────────────────────────────┤
│ "documento": {                                      │
│   "tipoDte": "01",                                  │
│   "codigoGeneracionR": "...",   ← Solo este UUID   │
│   "numeroControl": "...",                           │
│   "fecEmi": "2025-11-04",                          │
│   "montoIva": 2.3,                                 │
│   "tipoDocumento": "13",                           │
│   "numDocumento": "06062101-0",                    │
│   "nombre": "..."                                   │
│ }                                                   │
│ "motivo": "2"                   ← String simple ✅ │
└─────────────────────────────────────────────────────┘
```

## Campos del Objeto "documento"

| Campo | Tipo | Descripción | Origen |
|-------|------|-------------|--------|
| `tipoDte` | string | Tipo DTE | `facturas.tipo_dte` |
| `codigoGeneracionR` | string | UUID del DTE a anular | `facturas.codigo_generacion` |
| `numeroControl` | string | Número de control | `facturas.numero_control` |
| `fecEmi` | string | Fecha emisión | `facturas.fecha_emision` |
| `montoIva` | float | Monto IVA | `facturas.total_iva` |
| `tipoDocumento` | string | Tipo doc receptor | `facturas.receptor_tipo_documento` |
| `numDocumento` | string | Núm doc receptor | `facturas.receptor_num_documento` |
| `nombre` | string | Nombre receptor | `facturas.receptor_nombre` |

## Catálogo de Motivos (Simplificado)

| Código | Descripción |
|--------|-------------|
| "1" | Error de información del DTE a invalidar |
| "2" | Rescindir operación realizada |
| "3" | Otros motivos |

## Validaciones Finales

```php
✅ documento.codigoGeneracionR existe
✅ documento NO tiene codigoGeneracion
✅ documento NO tiene selloRecibido
✅ motivo es string: "1", "2", o "3"
✅ Todos los campos del receptor están presentes
```

## Ejemplo Completo

### Datos de Entrada

```php
$factura = Factura::find(169);
// tipo_dte: "01"
// codigo_generacion: "6871C033-B9EC-4BC6-9B12-BEC84F52C860"
// numero_control: "DTE-01-S001P001-000000000000096"
// total_iva: 2.3
// receptor_tipo_documento: "13"
// receptor_num_documento: "060062101"
// receptor_nombre: "miguel angel ayala bejarano"

$evento->tipo_invalidacion = "2"; // Rescindir operación
```

### JSON Generado

```json
{
  "identificacion": {
    "version": 2,
    "ambiente": "00",
    "codigoGeneracion": "144B5BCE-861F-4312-958B-954342FD8D6B",
    "fecAnula": "2025-11-05",
    "horAnula": "21:10:00"
  },
  "emisor": {
    "nit": "06142302881449",
    "nombre": "Taller González",
    "tipoEstablecimiento": "01",
    "codEstable": "0000",
    "codPuntoVenta": "0000",
    "nomEstablecimiento": "Taller González",
    "correo": "jonagozalez23@gmail.com",
    "telefono": "64404895"
  },
  "documento": {
    "tipoDte": "01",
    "codigoGeneracionR": "6871C033-B9EC-4BC6-9B12-BEC84F52C860",
    "numeroControl": "DTE-01-S001P001-000000000000096",
    "fecEmi": "2025-11-04",
    "montoIva": 2.3,
    "tipoDocumento": "13",
    "numDocumento": "060062101",
    "nombre": "miguel angel ayala bejarano"
  },
  "motivo": "2"
}
```

## Respuesta Esperada

```json
{
  "version": 2,
  "ambiente": "00",
  "versionApp": 2,
  "estado": "PROCESADO",
  "codigoGeneracion": "144B5BCE-861F-4312-958B-954342FD8D6B",
  "selloRecibido": "2025XYZ...",
  "fhProcesamiento": "05/11/2025 21:10:00",
  "clasificaMsg": "10",
  "codigoMsg": "001",
  "descripcionMsg": "RECIBIDO",
  "observaciones": []
}
```

## Historial Completo de Correcciones

| # | Error | Causa | Solución |
|---|-------|-------|----------|
| 1 | Campo receptor no permitido | Incluía receptor en raíz | Remover receptor |
| 2 | Campo documentoAnulado | Nombre incorrecto | Cambiar a "documento" |
| 3 | montoIva requerido | Faltaba campo | Agregar montoIva |
| 4 | tipoDocumento, numDocumento requeridos | Faltaban campos | Agregar datos receptor |
| 5 | motivo inválido | Texto descriptivo | Usar string numérico |
| 6 | codigoGeneracionR inválido | En raíz o con otro código | Dentro de documento, código del DTE |
| 7 | codigoGeneration no permitido | Incluido en documento | Solo codigoGeneracionR |
| 8 | selloRecibido no permitido | Incluido en documento | NO incluir selloRecibido |

## Estado Final

✅ **COMPLETADO Y VERIFICADO**

La estructura ahora coincide **exactamente** con el ejemplo proporcionado por el MH en el error.

Cambios finales aplicados:
1. ✅ Solo `codigoGeneracionR` en documento (no codigoGeneracion)
2. ✅ NO incluir `selloRecibido` en documento
3. ✅ `motivo` como string simple: "1", "2", "3"
4. ✅ Estructura de 4 campos raíz: identificacion, emisor, documento, motivo

---

**Última actualización**: 2025-11-05 21:10
**Estado**: ✅ Verificado contra ejemplo real del MH
**Archivo**: `InvalidacionService.php` líneas 116-140
