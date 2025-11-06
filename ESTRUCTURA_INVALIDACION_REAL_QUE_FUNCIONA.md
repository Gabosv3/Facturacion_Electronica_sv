# Estructura de Invalidación - REAL QUE FUNCIONA

## Fecha: 2025-11-05 21:23

## ✅ ESTRUCTURA DEFINITIVA VERIFICADA CON EJEMPLO REAL DEL MH

Después de **11 iteraciones**, esta es la estructura que **SÍ funciona** según ejemplo proporcionado.

---

## JSON Que Funciona (Ejemplo Real)

```json
{
  "identificacion": {
    "version": 2,
    "ambiente": "00",
    "codigoGeneracion": "FB1CA0FF-C95D-4555-8C31-0FC97741379B",
    "fecAnula": "2025-11-05",
    "horAnula": "21:23:01"
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
  "motivo": "02"
}
```

---

## Análisis de la Estructura Correcta

### 1. Campo "identificacion"

```json
{
  "version": 2,
  "ambiente": "00",
  "codigoGeneracion": "FB1CA0FF-C95D-4555-8C31-0FC97741379B",  ← UUID del EVENTO
  "fecAnula": "2025-11-05",
  "horAnula": "21:23:01"
}
```

- `codigoGeneracion`: UUID único para el evento de invalidación (NO del DTE)

### 2. Campo "emisor"

Estructura estándar del emisor (igual que en facturas normales).

### 3. Campo "documento" (8 CAMPOS)

```json
{
  "tipoDte": "01",
  "codigoGeneracionR": "6871C033-B9EC-4BC6-9B12-BEC84F52C860",  ← UUID del DTE a anular
  "numeroControl": "DTE-01-S001P001-000000000000096",
  "fecEmi": "2025-11-04",
  "montoIva": 2.3,
  "tipoDocumento": "13",
  "numDocumento": "06062101-0",
  "nombre": "miguel angel ayala bejarano"
}
```

**IMPORTANTE - Lo que NO está incluido:**
- ❌ `codigoGeneracion` - NO incluir (solo en identificacion)
- ❌ `selloRecibido` - NO incluir

**Solo hay UN código de generación en documento:**
- ✅ `codigoGeneracionR` - UUID del DTE que se está anulando

### 4. Campo "motivo"

```json
"motivo": "02"
```

**Tipo**: `string` de 2 dígitos

**Valores válidos:**
- `"01"` = Error de información del DTE a invalidar
- `"02"` = Rescindir operación realizada
- `"03"` = Otros

**NO usar:**
- ❌ `1`, `2`, `3` (integer)
- ❌ `"1"`, `"2"`, `"3"` (string de 1 dígito)
- ❌ Texto descriptivo

---

## Comparación: Estructura Incorrecta vs Correcta

### ❌ INCORRECTO (Iteración 10)

```json
{
  "documento": {
    "tipoDte": "01",
    "codigoGeneracion": "6871C033-...",       ← NO incluir
    "selloRecibido": "2025EFBAB6BE...",       ← NO incluir
    "codigoGeneracionR": "6871C033-...",
    "numeroControl": "...",
    "fecEmi": "2025-11-04",
    "montoIva": 2.3,
    "tipoDocumento": "13",
    "numDocumento": "060062101",
    "nombre": "miguel angel ayala bejarano"
  },
  "motivo": 2                                 ← NO usar integer
}
```

### ✅ CORRECTO (Iteración 11)

```json
{
  "documento": {
    "tipoDte": "01",
    "codigoGeneracionR": "6871C033-...",      ← Solo este UUID
    "numeroControl": "...",
    "fecEmi": "2025-11-04",
    "montoIva": 2.3,
    "tipoDocumento": "13",
    "numDocumento": "06062101-0",
    "nombre": "miguel angel ayala bejarano"
  },
  "motivo": "02"                              ← String de 2 dígitos
}
```

---

## Código PHP Corregido

### InvalidacionService.php (líneas 121-143)

```php
// Documento a anular
// Según ejemplo REAL que funciona del MH (21:23):
// - NO incluir codigoGeneracion
// - NO incluir selloRecibido
// - Solo codigoGeneracionR (apunta al DTE a anular)
$documento = [
    'tipoDte' => $factura->tipo_dte,
    'codigoGeneracionR' => $factura->codigo_generacion, // UUID del DTE a anular
    'numeroControl' => $factura->numero_control,
    'fecEmi' => $factura->fecha_emision->format('Y-m-d'),
    'montoIva' => (float) $factura->total_iva,
    'tipoDocumento' => $factura->receptor_tipo_documento,
    'numDocumento' => $factura->receptor_num_documento,
    'nombre' => $factura->receptor_nombre,
];

// Motivo: Debe ser string de 2 dígitos con formato "01", "02", "03"
$motivosCodigos = [
    '1' => '01', // Error de información
    '2' => '02', // Rescindir operación
    '3' => '03', // Otros
];
$motivo = $motivosCodigos[$evento->tipo_invalidacion] ?? '03';

return [
    'identificacion' => $identificacion,
    'emisor' => $emisorData,
    'documento' => $documento,
    'motivo' => $motivo,
];
```

---

## Flujo de Códigos UUID

```
┌──────────────────────────────────────────────────────────────┐
│                  TRES UBICACIONES DIFERENTES                 │
├──────────────────────────────────────────────────────────────┤
│                                                              │
│  1. EVENTO DE INVALIDACIÓN (nuevo UUID)                     │
│     identificacion.codigoGeneracion = "FB1CA0FF-..."        │
│                                                              │
│  2. DTE ORIGINAL A ANULAR (UUID existente)                  │
│     documento.codigoGeneracionR = "6871C033-..."            │
│                                                              │
│  3. NO HAY TERCERO                                          │
│     documento.codigoGeneracion = NO EXISTE ❌                │
│                                                              │
└──────────────────────────────────────────────────────────────┘
```

**Conclusión:**
- Solo **DOS** UUIDs en total
- `identificacion.codigoGeneracion` = Evento nuevo
- `documento.codigoGeneracionR` = DTE a anular
- **NO** hay `documento.codigoGeneracion`

---

## Tabla de Campos Requeridos

| # | Campo | Ubicación | Tipo | Valor | Origen |
|---|-------|-----------|------|-------|--------|
| 1 | `version` | identificacion | integer | 2 | Constante |
| 2 | `ambiente` | identificacion | string | "00"/"01" | config('dte.ambiente') |
| 3 | `codigoGeneracion` | identificacion | string | UUID | Nuevo (evento) |
| 4 | `fecAnula` | identificacion | string | YYYY-MM-DD | now() |
| 5 | `horAnula` | identificacion | string | HH:mm:ss | now() |
| 6 | `tipoDte` | documento | string | "01", "03", etc. | facturas.tipo_dte |
| 7 | `codigoGeneracionR` | documento | string | UUID | facturas.codigo_generacion |
| 8 | `numeroControl` | documento | string | DTE-XX-... | facturas.numero_control |
| 9 | `fecEmi` | documento | string | YYYY-MM-DD | facturas.fecha_emision |
| 10 | `montoIva` | documento | float | 2.3 | facturas.total_iva |
| 11 | `tipoDocumento` | documento | string | "13", "36", etc. | facturas.receptor_tipo_documento |
| 12 | `numDocumento` | documento | string | "06062101-0" | facturas.receptor_num_documento |
| 13 | `nombre` | documento | string | Nombre | facturas.receptor_nombre |
| 14 | `motivo` | raíz | string | "01", "02", "03" | Mapeado de tipo_invalidacion |

---

## Mapeo de Tipo de Invalidación

```php
$motivosCodigos = [
    '1' => '01', // tipo_invalidacion = 1 → motivo = "01"
    '2' => '02', // tipo_invalidacion = 2 → motivo = "02"
    '3' => '03', // tipo_invalidacion = 3 → motivo = "03"
];
```

**Base de datos → JSON:**
- `eventos_invalidacion.tipo_invalidacion` = "1", "2", "3" (string en BD)
- JSON `motivo` = "01", "02", "03" (string con cero adelante)

---

## Historial Completo de Iteraciones

| # | Hora | Error | Solución |
|---|------|-------|----------|
| 1 | 20:30 | Nombres de campos incorrectos | fecAnula/horAnula |
| 2 | 20:35 | Faltaban campos | Agregar receptor |
| 3 | 20:40 | motivo texto | Código numérico |
| 4 | 20:45 | motivo integer | String "02" |
| 5 | 20:50 | codigoGeneracionR ubicación | Dentro de documento |
| 6 | 20:55 | codigoGeneracionR valor | codigo_generacion |
| 7 | 21:00 | motivo "02" → "2" | String simple |
| 8 | 21:05 | Campos no permitidos | Remover codigoGeneracion/sello |
| 9 | 21:10 | Campos requeridos | Agregar codigoGeneracion/sello |
| 10 | 21:16 | motivo texto, codigoGeneracionR opcional | Integer, siempre incluir |
| **11** | **21:23** | **Estructura incorrecta** | **✅ Solo codigoGeneracionR, motivo "02"** |

---

## Diferencias Clave de la Iteración Final

### Lo que cambió de iteración 10 → 11:

1. **Campos removidos de documento:**
   - ❌ `codigoGeneracion` (iteración 10 lo tenía)
   - ❌ `selloRecibido` (iteración 10 lo tenía)

2. **Campo motivo:**
   - ❌ Integer `2` (iteración 10)
   - ✅ String `"02"` (iteración 11)

3. **Campo codigoGeneracionR:**
   - ✅ Se mantiene (único UUID en documento)
   - ✅ Apunta al DTE a anular

---

## Respuesta Esperada del MH

```json
{
  "version": 2,
  "ambiente": "00",
  "versionApp": 2,
  "estado": "PROCESADO",
  "codigoGeneracion": "FB1CA0FF-C95D-4555-8C31-0FC97741379B",
  "selloRecibido": "2025XYZ...",
  "fhProcesamiento": "05/11/2025 21:25:00",
  "clasificaMsg": "10",
  "codigoMsg": "001",
  "descripcionMsg": "RECIBIDO",
  "observaciones": []
}
```

---

## Ejemplo Completo

### Datos de Entrada

```php
$factura = Factura::find(169);
// codigo_generacion: "6871C033-B9EC-4BC6-9B12-BEC84F52C860"
// tipo_dte: "01"
// numero_control: "DTE-01-S001P001-000000000000096"
// fecha_emision: "2025-11-04"
// total_iva: 2.3
// receptor_tipo_documento: "13"
// receptor_num_documento: "06062101-0"
// receptor_nombre: "miguel angel ayala bejarano"

$evento->tipo_invalidacion = "2"; // Rescindir operación
```

### JSON Generado

```json
{
  "identificacion": {
    "version": 2,
    "ambiente": "00",
    "codigoGeneracion": "NUEVO-UUID-EVENTO",
    "fecAnula": "2025-11-05",
    "horAnula": "21:25:00"
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
  "motivo": "02"
}
```

---

## Validaciones del Sistema

```php
✅ factura.estado == 'enviado_mh'
✅ factura.invalidada == false
✅ factura.en_contingencia == false
✅ Dentro del plazo según tipo DTE
✅ documento solo tiene codigoGeneracionR (NO codigoGeneracion)
✅ documento NO tiene selloRecibido
✅ motivo es string de 2 dígitos ("01", "02", "03")
```

---

## Conclusión Final

**La estructura correcta es:**

1. ✅ Solo `codigoGeneracionR` en documento (sin codigoGeneracion ni selloRecibido)
2. ✅ `codigoGeneracionR` apunta al UUID del DTE a anular
3. ✅ `motivo` es string de 2 dígitos: "01", "02", "03"
4. ✅ 8 campos en documento (no 10)

**NO confundir con:**
- ❌ Incluir `codigoGeneracion` Y `codigoGeneracionR` juntos
- ❌ Incluir `selloRecibido` en documento
- ❌ Usar motivo como integer o string de 1 dígito

---

**Última actualización**: 2025-11-05 21:23
**Estado**: ✅ VERIFICADO CON EJEMPLO REAL QUE FUNCIONA
**Archivo**: `InvalidacionService.php` líneas 121-143
**Iteración**: 11 (FINAL)
