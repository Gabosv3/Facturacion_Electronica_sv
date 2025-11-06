# Estructura de Invalidación - FINAL VERIFICADA

## Fecha: 2025-11-05 21:54

## ✅ ESTRUCTURA COMPLETA Y VERIFICADA - PROCESADA POR EL MH

Después de 16 iteraciones, la estructura fue **PROCESADA exitosamente** por el MH.

---

## 🎯 Corrección Final Crítica

**Error (Iteración 15):**
```json
{
  "documento": {
    "codigoGeneracionR": "6871C033-B9EC-4BC6-9B12-BEC84F52C860"  // Mismo UUID
  }
}
```
**Resultado:** ❌ "Campo codigoGeneracionR contiene un valor inválido"

**Solución (Iteración 16):**
```json
{
  "documento": {
    "codigoGeneracionR": null  // null para tipo 2/3
  }
}
```
**Resultado:** ✅ **PROCESADO por el MH**

---

## JSON Definitivo que Funciona

```json
{
  "identificacion": {
    "version": 2,
    "ambiente": "00",
    "codigoGeneracion": "2FC8AD51-D030-4652-A04E-B59FCD95D5AE",
    "fecAnula": "2025-11-05",
    "horAnula": "21:54:42"
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
    "codigoGeneracionR": null,
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

## Análisis del Campo `codigoGeneracionR`

### Valores Según Tipo de Invalidación

| Tipo | Descripción | codigoGeneracionR |
|------|-------------|-------------------|
| **1** | Error de información (con reemplazo) | UUID del documento de reemplazo |
| **2** | Rescindir operación (sin reemplazo) | `null` |
| **3** | Otros (sin reemplazo) | `null` |

### ❌ INCORRECTO

```json
{
  "documento": {
    "codigoGeneracionR": "6871C033-..."  // ❌ Mismo UUID del DTE
  }
}
```

### ✅ CORRECTO

```json
{
  "documento": {
    "codigoGeneracionR": null  // ✅ null para tipo 2/3
  }
}
```

---

## Código PHP Final Correcto

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

// codigoGeneracionR: REQUERIDO pero con valor null si no hay reemplazo
if ($evento->tipo_invalidacion == '1' && !empty($evento->codigo_generacion_reemplazo)) {
    $documento['codigoGeneracionR'] = $evento->codigo_generacion_reemplazo;
} else {
    $documento['codigoGeneracionR'] = null; // ✅ null para tipo 2/3
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

## Estructura Completa Verificada

### 1. identificacion (5 campos)
```json
{
  "version": 2,
  "ambiente": "00",
  "codigoGeneracion": "UUID-EVENTO",
  "fecAnula": "YYYY-MM-DD",
  "horAnula": "HH:mm:ss"
}
```

### 2. emisor (8 campos)
```json
{
  "nit": "06142302881449",
  "nombre": "Taller González",
  "tipoEstablecimiento": "01",
  "codEstable": "0000",
  "codPuntoVenta": "0000",
  "nomEstablecimiento": "Taller González",
  "correo": "email@example.com",
  "telefono": "64404895"
}
```

### 3. documento (10 campos)
```json
{
  "tipoDte": "01",
  "codigoGeneracion": "UUID-DTE-A-ANULAR",
  "selloRecibido": "SELLO-DEL-MH",
  "numeroControl": "DTE-01-...",
  "fecEmi": "YYYY-MM-DD",
  "montoIva": 2.3,
  "codigoGeneracionR": null,        // ✅ null si tipo 2/3
  "tipoDocumento": "13",
  "numDocumento": "06062101-0",
  "nombre": "Nombre del receptor"
}
```

### 4. motivo (8 campos)
```json
{
  "tipoAnulacion": 2,               // integer
  "motivoAnulacion": "Texto...",    // string
  "nombreResponsable": "Juan...",
  "tipDocResponsable": "13",
  "numDocResponsable": "012345678",
  "nombreSolicita": "María...",
  "tipDocSolicita": "13",
  "numDocSolicita": "087654321"
}
```

---

## Lógica de `codigoGeneracionR`

```php
if (tipo_invalidacion == 1 AND hay_codigo_reemplazo) {
    codigoGeneracionR = UUID del nuevo DTE
} else {
    codigoGeneracionR = null
}
```

**Casos:**
- ✅ Tipo 1 con reemplazo → UUID del nuevo documento
- ✅ Tipo 1 sin reemplazo → `null`
- ✅ Tipo 2 (rescindir) → `null`
- ✅ Tipo 3 (otros) → `null`

---

## Historial de `codigoGeneracionR`

| Iteración | Valor | Resultado |
|-----------|-------|-----------|
| 9 | Solo si tipo 1 | ❌ Siempre requerido |
| 10-13 | Siempre mismo UUID | ❌ Valor inválido |
| 14 | NO incluir | ❌ Campo requerido |
| 15 | Mismo UUID del DTE | ❌ Valor inválido |
| **16** | `null` | ✅ **PROCESADO** |

---

## Respuesta del MH (Éxito)

```json
{
  "version": 2,
  "ambiente": "00",
  "versionApp": 2,
  "estado": "PROCESADO",
  "codigoGeneracion": "2FC8AD51-D030-4652-A04E-B59FCD95D5AE",
  "selloRecibido": "2025ABC...",
  "fhProcesamiento": "05/11/2025 21:54:42",
  "clasificaMsg": "10",
  "codigoMsg": "001",
  "descripcionMsg": "RECIBIDO",
  "observaciones": []
}
```

---

## Ejemplo Completo de Tipo 1 (Con Reemplazo)

```json
{
  "identificacion": {...},
  "emisor": {...},
  "documento": {
    "tipoDte": "01",
    "codigoGeneracion": "6871C033-B9EC-4BC6-9B12-BEC84F52C860",  // DTE original
    "selloRecibido": "2025EFBAB6BE...",
    "numeroControl": "DTE-01-...",
    "fecEmi": "2025-11-04",
    "montoIva": 2.3,
    "codigoGeneracionR": "D1234567-89AB-CDEF-0123-456789ABCDEF",  // DTE nuevo
    "tipoDocumento": "13",
    "numDocumento": "06062101-0",
    "nombre": "miguel angel ayala bejarano"
  },
  "motivo": {
    "tipoAnulacion": 1,
    "motivoAnulacion": "Error en datos del cliente",
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

## Ejemplo Completo de Tipo 2 (Sin Reemplazo)

```json
{
  "identificacion": {...},
  "emisor": {...},
  "documento": {
    "tipoDte": "01",
    "codigoGeneracion": "6871C033-B9EC-4BC6-9B12-BEC84F52C860",
    "selloRecibido": "2025EFBAB6BE...",
    "numeroControl": "DTE-01-...",
    "fecEmi": "2025-11-04",
    "montoIva": 2.3,
    "codigoGeneracionR": null,  // ✅ null
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

## Resumen de Campos por Sección

| Sección | Campos | Total |
|---------|--------|-------|
| identificacion | version, ambiente, codigoGeneracion, fecAnula, horAnula | 5 |
| emisor | nit, nombre, tipoEstablecimiento, codEstable, codPuntoVenta, nomEstablecimiento, correo, telefono | 8 |
| documento | tipoDte, codigoGeneracion, selloRecibido, numeroControl, fecEmi, montoIva, codigoGeneracionR, tipoDocumento, numDocumento, nombre | 10 |
| motivo | tipoAnulacion, motivoAnulacion, nombreResponsable, tipDocResponsable, numDocResponsable, nombreSolicita, tipDocSolicita, numDocSolicita | 8 |
| **TOTAL** | | **31** |

---

## Validaciones Finales

```php
✅ factura.estado == 'enviado_mh'
✅ factura.invalidada == false
✅ factura.en_contingencia == false
✅ respuesta_mh.envio_mh.selloRecibido != null
✅ evento.responsable_nombre != null
✅ evento.responsable_tipo_doc != null
✅ evento.responsable_num_doc != null
✅ evento.solicitante_nombre != null
✅ evento.solicitante_tipo_doc != null
✅ evento.solicitante_num_doc != null
✅ evento.motivo_invalidacion != null
✅ documento.codigoGeneracionR = null (si tipo 2/3)
✅ documento.codigoGeneracionR = UUID (si tipo 1 con reemplazo)
```

---

## Comparación Final

### ❌ Iteración 15 (Última Incorrecta)

```php
if (tipo 1 con reemplazo) {
    codigoGeneracionR = UUID de reemplazo
} else {
    codigoGeneracionR = UUID del DTE original  // ❌ Incorrecto
}
```

### ✅ Iteración 16 (CORRECTA - FUNCIONA)

```php
if (tipo 1 con reemplazo) {
    codigoGeneracionR = UUID de reemplazo
} else {
    codigoGeneracionR = null  // ✅ Correcto
}
```

---

## Conclusión

**El campo `codigoGeneracionR` es obligatorio en el JSON, pero:**
- Para tipo 1 CON documento de reemplazo: UUID del nuevo documento
- Para tipo 2, 3, o tipo 1 SIN reemplazo: `null`

**NO usar:**
- ❌ Mismo UUID del DTE a anular
- ❌ Omitir el campo
- ❌ String vacío `""`

**Usar:**
- ✅ `null` (para tipos sin reemplazo)
- ✅ UUID del documento nuevo (para tipo 1 con reemplazo)

---

## Lecciones Aprendidas

1. El MH requiere que el campo esté presente en el JSON
2. El valor `null` es válido y correcto para casos sin reemplazo
3. Usar el mismo UUID del DTE original es inválido
4. Los errores del MH se revelan progresivamente (solo después de corregir errores previos)
5. Tomó 16 iteraciones para encontrar la estructura correcta

---

**Última actualización**: 2025-11-05 21:54
**Estado**: ✅ ESTRUCTURA FINAL VERIFICADA Y PROCESADA POR EL MH
**Archivo**: `InvalidacionService.php` líneas 142-149
**Iteración**: 16 (FINAL - FUNCIONA)
**Resultado**: PROCESADO exitosamente por el Ministerio de Hacienda
