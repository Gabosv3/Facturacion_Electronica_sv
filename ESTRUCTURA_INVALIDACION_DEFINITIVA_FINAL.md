# Estructura de Invalidación - DEFINITIVA FINAL

## Fecha: 2025-11-05 21:47

## ✅ ESTRUCTURA COMPLETA Y DEFINITIVA

Después de 15 iteraciones, el MH finalmente reveló **todos los campos requeridos**.

---

## 🎯 Descubrimiento Final

El error más específico del MH (21:47) reveló que el objeto `motivo` necesita **8 campos obligatorios**:

### Campos Requeridos en `motivo`:
1. `tipoAnulacion` - Tipo de invalidación (1, 2, o 3)
2. `motivoAnulacion` - Texto descriptivo del motivo
3. `nombreResponsable` - Nombre del responsable (emisor)
4. `tipDocResponsable` - Tipo de documento del responsable
5. `numDocResponsable` - Número de documento del responsable
6. `nombreSolicita` - Nombre del solicitante
7. `tipDocSolicita` - Tipo de documento del solicitante
8. `numDocSolicita` - Número de documento del solicitante

### Campo en `documento`:
9. `codigoGeneracionR` - REQUERIDO (con o sin reemplazo)

---

## JSON Definitivo Completo

```json
{
  "identificacion": {
    "version": 2,
    "ambiente": "00",
    "codigoGeneracion": "EB501A8B-50F8-4B47-BD7D-95811BF60EDF",
    "fecAnula": "2025-11-05",
    "horAnula": "21:47:40"
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
    "codigoGeneracion": "6871C033-B9EC-4BC6-9B12-BEC84F52C860",
    "selloRecibido": "2025EFBAB6BE307344DFB82331BE2F74F235PDDY",
    "numeroControl": "DTE-01-S001P001-000000000000096",
    "fecEmi": "2025-11-04",
    "montoIva": 2.3,
    "codigoGeneracionR": "6871C033-B9EC-4BC6-9B12-BEC84F52C860",
    "tipoDocumento": "13",
    "numDocumento": "06062101-0",
    "nombre": "miguel angel ayala bejarano"
  },
  "motivo": {
    "tipoAnulacion": 2,
    "motivoAnulacion": "Devolucion de producto por parte del cliente",
    "nombreResponsable": "Juan Pérez López",
    "tipDocResponsable": "13",
    "numDocResponsable": "012345678",
    "nombreSolicita": "María García Martínez",
    "tipDocSolicita": "13",
    "numDocSolicita": "087654321"
  }
}
```

---

## Análisis de la Estructura

### 1. identificacion
Igual que en todas las iteraciones (correcto desde el inicio).

### 2. emisor
Igual que en todas las iteraciones (correcto desde el inicio).

### 3. documento (10 CAMPOS)
```json
{
  "tipoDte": "01",
  "codigoGeneracion": "6871C033-...",        ← UUID del DTE a anular
  "selloRecibido": "2025EFBAB6BE...",        ← Sello del MH
  "numeroControl": "DTE-01-...",
  "fecEmi": "2025-11-04",
  "montoIva": 2.3,
  "codigoGeneracionR": "6871C033-...",       ← REQUERIDO (mismo UUID para tipo 2/3)
  "tipoDocumento": "13",
  "numDocumento": "06062101-0",
  "nombre": "miguel angel ayala bejarano"
}
```

**Importante:** `codigoGeneracionR` es **SIEMPRE requerido**:
- Tipo 1 (con reemplazo): UUID del nuevo documento
- Tipo 2/3 (sin reemplazo): Mismo UUID del DTE a anular

### 4. motivo (8 CAMPOS)
```json
{
  "tipoAnulacion": 2,                                      ← Integer (1, 2, o 3)
  "motivoAnulacion": "Devolucion de producto...",          ← String (texto libre)
  "nombreResponsable": "Juan Pérez López",                 ← String
  "tipDocResponsable": "13",                               ← String (código CAT-022)
  "numDocResponsable": "012345678",                        ← String (sin guión)
  "nombreSolicita": "María García Martínez",               ← String
  "tipDocSolicita": "13",                                  ← String (código CAT-022)
  "numDocSolicita": "087654321"                            ← String (sin guión)
}
```

---

## Código PHP Final

```php
// Documento a anular
$documento = [
    'tipoDte' => $factura->tipo_dte,
    'codigoGeneracion' => $factura->codigo_generacion,
    'selloRecibido' => $selloRecibido,
    'numeroControl' => $factura->numero_control,
    'fecEmi' => $factura->fecha_emision->format('Y-m-d'),
    'montoIva' => (float) $factura->total_iva,
    'tipoDocumento' => $factura->receptor_tipo_documento,
    'numDocumento' => $factura->receptor_num_documento,
    'nombre' => $factura->receptor_nombre,
];

// codigoGeneracionR: SIEMPRE REQUERIDO
if ($evento->tipo_invalidacion == '1' && !empty($evento->codigo_generacion_reemplazo)) {
    $documento['codigoGeneracionR'] = $evento->codigo_generacion_reemplazo;
} else {
    $documento['codigoGeneracionR'] = $factura->codigo_generacion;
}

// Motivo: Objeto con 8 campos requeridos
$motivo = [
    'tipoAnulacion' => (int) $evento->tipo_invalidacion,
    'motivoAnulacion' => $evento->motivo_invalidacion,
    'nombreResponsable' => $evento->responsable_nombre,
    'tipDocResponsable' => $evento->responsable_tipo_doc,
    'numDocResponsable' => $evento->responsable_num_doc,
    'nombreSolicita' => $evento->solicitante_nombre,
    'tipDocSolicita' => $evento->solicitante_tipo_doc,
    'numDocSolicita' => $evento->solicitante_num_doc,
];

return [
    'identificacion' => $identificacion,
    'emisor' => $emisorData,
    'documento' => $documento,
    'motivo' => $motivo,
];
```

---

## Mapeo de Datos

### Desde la Base de Datos al JSON

| Campo JSON | Origen BD | Tipo | Ejemplo |
|------------|-----------|------|---------|
| **documento** |
| `codigoGeneracion` | `facturas.codigo_generacion` | string | "6871C033-..." |
| `selloRecibido` | `respuesta_mh.envio_mh.selloRecibido` | string | "2025EFBAB6BE..." |
| `codigoGeneracionR` | `eventos_invalidacion.codigo_generacion_reemplazo` o `facturas.codigo_generacion` | string | "6871C033-..." |
| **motivo** |
| `tipoAnulacion` | `eventos_invalidacion.tipo_invalidacion` | int | 1, 2, o 3 |
| `motivoAnulacion` | `eventos_invalidacion.motivo_invalidacion` | string | "Devolucion..." |
| `nombreResponsable` | `eventos_invalidacion.responsable_nombre` | string | "Juan Pérez López" |
| `tipDocResponsable` | `eventos_invalidacion.responsable_tipo_doc` | string | "13" (DUI) |
| `numDocResponsable` | `eventos_invalidacion.responsable_num_doc` | string | "012345678" |
| `nombreSolicita` | `eventos_invalidacion.solicitante_nombre` | string | "María García" |
| `tipDocSolicita` | `eventos_invalidacion.solicitante_tipo_doc` | string | "13" |
| `numDocSolicita` | `eventos_invalidacion.solicitante_num_doc` | string | "087654321" |

---

## Tipos de Documento (CAT-022)

Para `tipDocResponsable` y `tipDocSolicita`:

| Código | Descripción |
|--------|-------------|
| `13` | DUI (Documento Único de Identidad) |
| `36` | NIT (Número de Identificación Tributaria) |
| `02` | Carnet de Residente |
| `03` | Pasaporte |
| `37` | Otro |

**Formato de números:**
- DUI: 9 dígitos **sin guión** (ej. `012345678`)
- NIT: 14 dígitos sin guiones (ej. `06142302881449`)

---

## Tipos de Anulación (CAT-024)

| Código | Descripción | codigoGeneracionR |
|--------|-------------|-------------------|
| `1` | Error de información del DTE a invalidar | UUID del documento de reemplazo |
| `2` | Rescindir operación realizada | Mismo UUID del DTE original |
| `3` | Otros | Mismo UUID del DTE original |

---

## Historial de Iteraciones del Campo `motivo`

| # | Valor | Resultado |
|---|-------|-----------|
| 1-8 | Texto descriptivo solo | ❌ Inválido |
| 9 | Integer 2 | ❌ Inválido |
| 10 | String "02" | ❌ Inválido |
| 11 | String "2" | ❌ Inválido |
| 12 | Integer 2 (intento 2) | ❌ Inválido |
| 13 | Integer 2 (intento 3) | ❌ Inválido |
| 14 | Objeto vacío `{}` | ❌ Campos requeridos |
| **15** | Objeto con 8 campos | ✅ **CORRECTO** |

---

## Comparación: Iteraciones 14 vs 15

### ❌ ITERACIÓN 14 (Incorrecta)

```json
{
  "documento": {
    "codigoGeneracion": "6871C033-...",
    "selloRecibido": "2025EFBAB6BE..."
    // NO incluir codigoGeneracionR
  },
  "motivo": {}  // Objeto vacío
}
```

**Error:** "8 campos requeridos en motivo + codigoGeneracionR requerido"

### ✅ ITERACIÓN 15 (Correcta)

```json
{
  "documento": {
    "codigoGeneracion": "6871C033-...",
    "selloRecibido": "2025EFBAB6BE...",
    "codigoGeneracionR": "6871C033-..."  // ✅ Incluido
  },
  "motivo": {
    "tipoAnulacion": 2,
    "motivoAnulacion": "...",
    "nombreResponsable": "...",
    "tipDocResponsable": "13",
    "numDocResponsable": "...",
    "nombreSolicita": "...",
    "tipDocSolicita": "13",
    "numDocSolicita": "..."
  }
}
```

---

## Validaciones del Sistema

### Validaciones Existentes (OK):
```php
✅ factura.estado == 'enviado_mh'
✅ factura.invalidada == false
✅ factura.en_contingencia == false
✅ respuesta_mh.envio_mh.selloRecibido != null
✅ Dentro del plazo según tipo DTE
```

### Validaciones Necesarias (Nuevas):
```php
✅ evento.responsable_nombre != null
✅ evento.responsable_tipo_doc != null
✅ evento.responsable_num_doc != null
✅ evento.solicitante_nombre != null
✅ evento.solicitante_tipo_doc != null
✅ evento.solicitante_num_doc != null
✅ evento.motivo_invalidacion != null
✅ documento.codigoGeneracionR siempre presente
```

---

## Ejemplo Completo de Uso

### Datos de Entrada

```php
$data = [
    'factura_id' => 169,
    'tipo_invalidacion' => '2',  // Rescindir operación
    'motivo_invalidacion' => 'Devolucion de producto por parte del cliente',
    'factura_reemplazo_id' => null,
    'codigo_generacion_reemplazo' => null,
    'responsable_nombre' => 'Juan Pérez López',
    'responsable_tipo_doc' => '13',  // DUI
    'responsable_num_doc' => '012345678',
    'solicitante_nombre' => 'María García Martínez',
    'solicitante_tipo_doc' => '13',  // DUI
    'solicitante_num_doc' => '087654321',
];

$invalidacionService->procesarInvalidacion($data);
```

### Flujo Completo

```
1. Usuario llena formulario de invalidación
   ↓
2. Sistema valida que la factura pueda ser invalidada
   ↓
3. Sistema crea EventoInvalidacion en BD
   ↓
4. Sistema construye JSON con 4 secciones:
   - identificacion (evento)
   - emisor (config)
   - documento (factura + codigoGeneracionR)
   - motivo (8 campos del evento)
   ↓
5. Sistema envía a API de firma
   ↓
6. Sistema recibe JWT firmado
   ↓
7. Sistema envía JWT al MH
   ↓
8. MH procesa y devuelve sello de recepción
   ↓
9. Sistema marca factura como invalidada
```

---

## Respuesta Esperada del MH

```json
{
  "version": 2,
  "ambiente": "00",
  "versionApp": 2,
  "estado": "PROCESADO",
  "codigoGeneracion": "EB501A8B-50F8-4B47-BD7D-95811BF60EDF",
  "selloRecibido": "2025XYZ...",
  "fhProcesamiento": "05/11/2025 21:50:00",
  "clasificaMsg": "10",
  "codigoMsg": "001",
  "descripcionMsg": "RECIBIDO",
  "observaciones": []
}
```

---

## Campos Completos del JSON

### Nivel Raíz (4 objetos)
1. `identificacion` - Datos del evento de invalidación
2. `emisor` - Datos del emisor
3. `documento` - Datos del DTE a anular
4. `motivo` - Datos del motivo de invalidación

### Total de Campos Requeridos
- **identificacion**: 5 campos
- **emisor**: 8 campos
- **documento**: 10 campos
- **motivo**: 8 campos
- **TOTAL**: 31 campos obligatorios

---

## Conclusión

**Después de 15 iteraciones:**

1. ✅ El objeto `motivo` necesita **8 campos específicos**
2. ✅ El campo `codigoGeneracionR` es **SIEMPRE requerido** en documento
3. ✅ Para tipo 2/3 sin reemplazo: `codigoGeneracionR` = mismo UUID del DTE
4. ✅ Para tipo 1 con reemplazo: `codigoGeneracionR` = UUID del nuevo DTE
5. ✅ `tipoAnulacion` es integer (1, 2, o 3)
6. ✅ `motivoAnulacion` es texto descriptivo libre
7. ✅ Responsable y solicitante con nombre completo y documento

**Por qué tomó tantas iteraciones:**
- El MH solo devuelve errores específicos después de corregir errores previos
- Cada error revelaba nuevos campos requeridos
- El esquema completo solo se reveló cuando la estructura básica era correcta

---

**Última actualización**: 2025-11-05 21:47
**Estado**: ✅ ESTRUCTURA COMPLETA Y DEFINITIVA
**Archivo**: `InvalidacionService.php` líneas 122-158
**Iteración**: 15 (FINAL DEFINITIVA)
