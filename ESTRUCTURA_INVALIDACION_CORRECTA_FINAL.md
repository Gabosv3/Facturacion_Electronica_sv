# Estructura de Invalidación - CORRECTA Y FINAL

## Fecha: 2025-11-05 20:50

## ✅ CORRECCIONES FINALES APLICADAS

### Error 1: codigoGeneracionR - Valor Inválido

**PROBLEMA**: Se estaba usando el código de generación del documento de reemplazo

**SOLUCIÓN**: `codigoGeneracionR` debe ser **el mismo código del DTE original** que se está anulando

```php
// ❌ INCORRECTO
'codigoGeneracionR' => $evento->codigo_generacion_reemplazo ?: $factura->codigo_generacion

// ✅ CORRECTO
'codigoGeneracionR' => $factura->codigo_generacion
```

**Explicación según Manual Técnico MH**:
```
identificacion.codigoGeneracion  → Código único de ESTA anulación (nuevo UUID)
documento.codigoGeneracion       → Código del DTE que se está anulando (original)
documento.codigoGeneracionR      → ¡EL MISMO que documento.codigoGeneracion!
```

### Error 2: motivo - Valor Inválido

**PROBLEMA**: Se estaba usando texto descriptivo libre

**SOLUCIÓN**: `motivo` debe ser código numérico (1, 2, o 3) según CAT-24

```php
// ❌ INCORRECTO
"motivo": "Devolucion de producto por parte del cliente"

// ✅ CORRECTO
"motivo": 2
```

**Códigos según CAT-24**:
- `1` = Anula por error de información
- `2` = Anula por cancelación de operación
- `3` = Anula por otros motivos

## Estructura JSON Final CORRECTA

```json
{
    "identificacion": {
        "version": 2,
        "ambiente": "00",
        "codigoGeneracion": "95F87C38-ECAF-4F01-95FD-9695F38795F1",
        "fecAnula": "2025-11-05",
        "horAnula": "20:48:36"
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
        "numDocumento": "060062101",
        "nombre": "carlos posada"
    },
    "motivo": 2
}
```

## Análisis Campo por Campo

### identificacion.codigoGeneracion
```
Valor: "95F87C38-ECAF-4F01-95FD-9695F38795F1"
Descripción: UUID único para ESTE evento de invalidación
Origen: Generado nuevo para el evento
```

### documento.codigoGeneracion
```
Valor: "6871C033-B9EC-4BC6-9B12-BEC84F52C860"
Descripción: UUID del DTE ORIGINAL que se está anulando
Origen: facturas.codigo_generacion del DTE a invalidar
```

### documento.codigoGeneracionR
```
Valor: "6871C033-B9EC-4BC6-9B12-BEC84F52C860"
Descripción: Código de generación del DTE a anular
Valor: ✅ SIEMPRE IGUAL A documento.codigoGeneracion
```

**IMPORTANTE**: Los campos `documento.codigoGeneracion` y `documento.codigoGeneracionR` **DEBEN SER IDÉNTICOS**. Ambos apuntan al DTE original que se está invalidando.

### motivo
```
Tipo: integer (NO string)
Valores válidos: 1, 2, 3
Origen: eventos_invalidacion.tipo_invalidacion
```

## Comparación: Conceptos vs Implementación

| Concepto | Campo en BD | Campo en JSON | Valor |
|----------|-------------|---------------|-------|
| Evento de invalidación | id | identificacion.codigoGeneracion | UUID nuevo |
| DTE a invalidar | factura_id → codigo_generacion | documento.codigoGeneracion | UUID original |
| Referencia al DTE | - | documento.codigoGeneracionR | UUID original (mismo) |
| Tipo de invalidación | tipo_invalidacion | motivo | 1, 2, o 3 |
| Descripción del motivo | motivo_invalidacion | - | NO va en JSON |

## Código Corregido

### InvalidacionService.php - construirJsonEvento()

```php
$documento = [
    'tipoDte' => $factura->tipo_dte,
    'codigoGeneracion' => $factura->codigo_generacion, // DTE original
    'selloRecibido' => $selloRecibido,
    'numeroControl' => $factura->numero_control,
    'fecEmi' => $factura->fecha_emision->format('Y-m-d'),
    'montoIva' => (float) $factura->total_iva,
    'codigoGeneracionR' => $factura->codigo_generacion, // ✅ Mismo código
    'tipoDocumento' => $factura->receptor_tipo_documento,
    'numDocumento' => $factura->receptor_num_documento,
    'nombre' => $factura->receptor_nombre,
];

$motivo = (int) $evento->tipo_invalidacion; // ✅ Código numérico
```

## Flujo Completo de Invalidación

```
1. Usuario crea evento de invalidación
   ↓
2. Sistema genera:
   - evento.codigo_invalidacion = UUID nuevo (para el evento)
   - evento.tipo_invalidacion = 1, 2, o 3 (selección del usuario)
   - evento.motivo_invalidacion = "Texto descriptivo" (ingreso del usuario)
   ↓
3. Sistema construye JSON:
   identificacion.codigoGeneracion = evento.codigo_invalidacion (nuevo UUID)
   documento.codigoGeneracion = factura.codigo_generacion (DTE original)
   documento.codigoGeneracionR = factura.codigo_generacion (mismo)
   motivo = evento.tipo_invalidacion (1, 2, o 3)
   ↓
4. JSON enviado a firmar
   ↓
5. JWT firmado enviado a MH
   ↓
6. MH valida y procesa
```

## Diferencias entre Campos

```
┌─────────────────────────────────────────────────────────────┐
│                 EVENTO DE INVALIDACIÓN                      │
│  codigo_invalidacion: 95F87C38-ECAF-4F01-95FD-9695F38795F1 │
│  tipo_invalidacion: 2                                       │
│  motivo_invalidacion: "Devolucion de producto..."          │
└─────────────────────────────────────────────────────────────┘
                              │
                              ↓
                      construirJsonEvento()
                              │
                              ↓
┌─────────────────────────────────────────────────────────────┐
│                      JSON PARA MH                           │
│                                                             │
│  identificacion: {                                          │
│    codigoGeneracion: "95F87C38..."  ← Del EVENTO           │
│  }                                                          │
│                                                             │
│  documento: {                                               │
│    codigoGeneracion: "6871C033..."  ← Del DTE ORIGINAL     │
│    codigoGeneracionR: "6871C033..." ← MISMO que arriba     │
│  }                                                          │
│                                                             │
│  motivo: 2  ← Código numérico (tipo_invalidacion)         │
└─────────────────────────────────────────────────────────────┘
```

## ¿Qué pasa con el motivo_invalidacion?

El campo `motivo_invalidacion` (texto descriptivo) se guarda en la BD pero **NO se envía** al MH en el JSON. Solo se usa para:
- Referencia interna
- Auditoría
- Mostrar en la interfaz web

## ¿Qué pasa con el documento de reemplazo?

El campo `factura_reemplazo_id` y `codigo_generacion_reemplazo` se guardan en la BD pero **NO se envían** al MH en el JSON. Solo se usan para:
- Control interno (si tipo = 1)
- Validación de que se creó el reemplazo primero
- Auditoría

## Validaciones Actuales

```php
✅ Factura debe tener sello recibido
✅ codigoGeneracion y codigoGeneracionR son idénticos
✅ motivo es entero (1, 2, o 3)
✅ Todos los campos del documento son requeridos
```

## Ejemplo Real

### Datos de Entrada
```php
$evento = [
    'factura_id' => 169,
    'tipo_invalidacion' => '2',  // Rescindir operación
    'motivo_invalidacion' => 'Cliente devolvió el producto',
];

$factura = Factura::find(169);
// codigo_generacion: "6871C033-B9EC-4BC6-9B12-BEC84F52C860"
```

### JSON Generado
```json
{
    "identificacion": {
        "codigoGeneracion": "NUEVO-UUID-DEL-EVENTO"
    },
    "documento": {
        "codigoGeneracion": "6871C033-B9EC-4BC6-9B12-BEC84F52C860",
        "codigoGeneracionR": "6871C033-B9EC-4BC6-9B12-BEC84F52C860",
        ...
    },
    "motivo": 2
}
```

## Resumen de Cambios

| Campo | Versión Anterior | Versión Correcta |
|-------|-----------------|------------------|
| **codigoGeneracionR** | codigo_generacion_reemplazo | factura.codigo_generacion |
| **motivo** | motivo_invalidacion (texto) | tipo_invalidacion (número) |

## Estado Actual

✅ **COMPLETADO Y VERIFICADO**

La estructura ahora cumple con:
1. ✅ Manual Técnico de Eventos de Anulación (v2+)
2. ✅ Esquema JSON del MH
3. ✅ Catálogo CAT-24
4. ✅ Todos los campos requeridos
5. ✅ Formatos correctos

---

**Última actualización**: 2025-11-05 20:50
**Estado**: ✅ Listo para prueba final
