# Estructura de Invalidación - ITERACIÓN 12

## Fecha: 2025-11-05 21:31

## ⚠️ NUEVOS ERRORES - EL MH CAMBIÓ LOS REQUISITOS

El MH ahora dice que **SÍ necesita** los campos que antes decía que NO:

### Error del MH (21:31):
```json
{
  "estado": "RECHAZADO",
  "descripcionMsg": "DOCUMENTO NO CUMPLE ESQUEMA JSON",
  "observaciones": [
    "Campo #/motivo contiene un valor inválido",
    "Campo codigoGeneracion es requerido en #/documento",
    "Campo selloRecibido es requerido en #/documento",
    "Campo #/documento/codigoGeneracionR contiene un valor inválido"
  ]
}
```

---

## Análisis del Error

### 1. ❌ Campo `motivo` inválido

**Lo que probamos:**
- Iteración 10: Integer `2` → Rechazado
- Iteración 11: String `"02"` → Rechazado

**Ahora probar:**
- Iteración 12: String `"2"` (sin cero adelante)

### 2. ❌ Campo `codigoGeneracion` REQUERIDO en documento

**Antes (iteración 11):** NO incluido → Rechazado

**Ahora (iteración 12):** SÍ incluir

### 3. ❌ Campo `selloRecibido` REQUERIDO en documento

**Antes (iteración 11):** NO incluido → Rechazado

**Ahora (iteración 12):** SÍ incluir

### 4. ❌ Campo `codigoGeneracionR` con valor inválido

**Hipótesis:**
- Tal vez NO debe estar presente para tipo 2 (rescindir)
- Tal vez solo debe estar para tipo 1 (con documento de reemplazo)

---

## Estructura Propuesta (Iteración 12)

### Para Tipo 2 (Rescindir - SIN reemplazo):

```json
{
  "identificacion": {...},
  "emisor": {...},
  "documento": {
    "tipoDte": "01",
    "codigoGeneracion": "6871C033-B9EC-4BC6-9B12-BEC84F52C860",
    "selloRecibido": "2025EFBAB6BE307344DFB82331BE2F74F235PDDY",
    "numeroControl": "DTE-01-S001P001-000000000000096",
    "fecEmi": "2025-11-04",
    "montoIva": 2.3,
    "tipoDocumento": "13",
    "numDocumento": "060062101",
    "nombre": "miguel angel ayala bejarano"
    // ⚠️ NO incluir codigoGeneracionR si no hay reemplazo
  },
  "motivo": "2"  // String sin cero adelante
}
```

### Para Tipo 1 (Error con reemplazo):

```json
{
  "identificacion": {...},
  "emisor": {...},
  "documento": {
    "tipoDte": "01",
    "codigoGeneracion": "6871C033-B9EC-4BC6-9B12-BEC84F52C860",
    "selloRecibido": "2025EFBAB6BE307344DFB82331BE2F74F235PDDY",
    "numeroControl": "DTE-01-S001P001-000000000000096",
    "fecEmi": "2025-11-04",
    "montoIva": 2.3,
    "codigoGeneracionR": "NUEVO-UUID-DE-REEMPLAZO",  // UUID del nuevo DTE
    "tipoDocumento": "13",
    "numDocumento": "060062101",
    "nombre": "miguel angel ayala bejarano"
  },
  "motivo": "1"
}
```

---

## Código PHP Corregido (Iteración 12)

```php
// Obtener sello recibido (REQUERIDO según error 21:31)
$selloRecibido = $this->obtenerSelloRecibido($factura);
if (empty($selloRecibido)) {
    throw new \Exception(
        "La factura no tiene sello recibido del MH."
    );
}

// Documento a anular
$documento = [
    'tipoDte' => $factura->tipo_dte,
    'codigoGeneracion' => $factura->codigo_generacion, // ✅ REQUERIDO
    'selloRecibido' => $selloRecibido, // ✅ REQUERIDO
    'numeroControl' => $factura->numero_control,
    'fecEmi' => $factura->fecha_emision->format('Y-m-d'),
    'montoIva' => (float) $factura->total_iva,
    'tipoDocumento' => $factura->receptor_tipo_documento,
    'numDocumento' => $factura->receptor_num_documento,
    'nombre' => $factura->receptor_nombre,
];

// codigoGeneracionR: Solo si tipo 1 Y hay documento de reemplazo
if ($evento->tipo_invalidacion == '1' && !empty($evento->codigo_generacion_reemplazo)) {
    $documento['codigoGeneracionR'] = $evento->codigo_generacion_reemplazo;
}

// Motivo: String simple "1", "2", "3"
$motivo = (string) $evento->tipo_invalidacion;
```

---

## Comparación de Iteraciones

| Iteración | codigoGeneracion | selloRecibido | codigoGeneracionR | motivo | Resultado |
|-----------|------------------|---------------|-------------------|--------|-----------|
| 9 | ✅ | ✅ | Solo tipo 1 | Texto | ❌ motivo inválido |
| 10 | ✅ | ✅ | Siempre | Integer 2 | ❌ motivo inválido |
| 11 | ❌ | ❌ | Siempre | "02" | ❌ Campos requeridos |
| **12** | ✅ | ✅ | Solo tipo 1 | "2" | ⏳ **Probar** |

---

## Hipótesis sobre `codigoGeneracionR`

### Opción A: Opcional según tipo
- **Tipo 1** (error): Debe incluir UUID de documento de reemplazo
- **Tipo 2** (rescindir): NO incluir
- **Tipo 3** (otros): NO incluir

### Opción B: Siempre el mismo
- Siempre incluir con el mismo UUID que `codigoGeneracion`

### Opción C: Siempre requerido pero diferente
- Tipo 1: UUID de reemplazo
- Tipo 2/3: UUID del documento original (mismo que codigoGeneracion)

**Implementación actual:** Opción A (solo tipo 1)

---

## Tabla de Campos (Iteración 12)

| Campo | Requerido | Tipo | Valor |
|-------|-----------|------|-------|
| `tipoDte` | ✅ | string | "01", "03", etc. |
| `codigoGeneracion` | ✅ | string | UUID del DTE a anular |
| `selloRecibido` | ✅ | string | Sello del MH |
| `numeroControl` | ✅ | string | Número de control |
| `fecEmi` | ✅ | string | YYYY-MM-DD |
| `montoIva` | ✅ | float | Monto IVA |
| `codigoGeneracionR` | ❓ | string | Solo tipo 1 con reemplazo |
| `tipoDocumento` | ✅ | string | Código CAT-022 |
| `numDocumento` | ✅ | string | Documento receptor |
| `nombre` | ✅ | string | Nombre receptor |
| `motivo` (raíz) | ✅ | string | "1", "2", "3" |

---

## Pregunta Crítica

**¿El JSON con estructura de iteración 11 que me proporcionaste realmente FUNCIONÓ?**

Porque si funcionó, entonces el problema puede ser:
1. Diferentes versiones del esquema según ambiente (test vs producción)
2. Diferentes requisitos según tipo de DTE (01 vs 03 vs 05)
3. Cambios recientes en el esquema del MH

**Si NO funcionó**, entonces necesitamos saber cuál es la estructura REAL que el MH acepta.

---

## Próximos Pasos

1. **Probar iteración 12** con:
   - ✅ `codigoGeneracion` en documento
   - ✅ `selloRecibido` en documento
   - ❌ `codigoGeneracionR` NO incluido (tipo 2)
   - ✅ `motivo` = "2" (string)

2. **Si falla**, probar con:
   - `codigoGeneracionR` = mismo UUID que `codigoGeneracion`

3. **Si sigue fallando**, probar:
   - `motivo` como integer: `2`
   - `motivo` con cero: `"02"`

---

**Última actualización**: 2025-11-05 21:31
**Estado**: ⏳ Esperando prueba de iteración 12
**Cambio principal**: Incluir codigoGeneracion y selloRecibido, excluir codigoGeneracionR para tipo 2
