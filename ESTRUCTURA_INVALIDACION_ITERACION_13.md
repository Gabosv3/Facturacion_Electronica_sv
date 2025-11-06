# Estructura de Invalidación - ITERACIÓN 13

## Fecha: 2025-11-05 21:36

## ✅ CORRECCIONES APLICADAS

### Error del MH (21:36):
```json
{
  "observaciones": [
    "Campo #/motivo contiene un valor inválido",
    "Campo codigoGeneracionR es requerido en #/documento"
  ]
}
```

---

## Cambios de Iteración 12 → 13

### 1. ✅ `codigoGeneracionR` ahora SIEMPRE incluido

**Iteración 12 (incorrecto):**
```php
// Solo si tipo 1
if ($evento->tipo_invalidacion == '1' && ...) {
    $documento['codigoGeneracionR'] = ...;
}
```

**Iteración 13 (correcto):**
```php
// SIEMPRE incluido
if ($evento->tipo_invalidacion == '1' && !empty($evento->codigo_generacion_reemplazo)) {
    $documento['codigoGeneracionR'] = $evento->codigo_generacion_reemplazo;
} else {
    $documento['codigoGeneracionR'] = $factura->codigo_generacion; // Mismo UUID
}
```

### 2. ✅ `motivo` ahora como integer

**Iteración 12 (incorrecto):**
```php
$motivo = (string) $evento->tipo_invalidacion; // "2"
```

**Iteración 13 (correcto):**
```php
$motivo = (int) $evento->tipo_invalidacion; // 2
```

---

## JSON Esperado (Tipo 2 - Rescindir)

```json
{
  "identificacion": {
    "version": 2,
    "ambiente": "00",
    "codigoGeneracion": "BBA773C2-C471-4760-AFD5-A5C527EAAD37",
    "fecAnula": "2025-11-05",
    "horAnula": "21:36:43"
  },
  "emisor": {...},
  "documento": {
    "tipoDte": "01",
    "codigoGeneracion": "6871C033-B9EC-4BC6-9B12-BEC84F52C860",
    "selloRecibido": "2025EFBAB6BE307344DFB82331BE2F74F235PDDY",
    "numeroControl": "DTE-01-S001P001-000000000000096",
    "fecEmi": "2025-11-04",
    "montoIva": 2.3,
    "codigoGeneracionR": "6871C033-B9EC-4BC6-9B12-BEC84F52C860",  ← Mismo UUID
    "tipoDocumento": "13",
    "numDocumento": "060062101",
    "nombre": "miguel angel ayala bejarano"
  },
  "motivo": 2  ← Integer
}
```

---

## Lógica de `codigoGeneracionR`

### Tipo 1 (Error con reemplazo):
```php
if (tipo == 1 && hay_reemplazo) {
    codigoGeneracionR = UUID del documento de reemplazo
}
```

### Tipo 2 (Rescindir) / Tipo 3 (Otros):
```php
else {
    codigoGeneracionR = Mismo UUID del DTE a anular
}
```

**Conclusión:** `codigoGeneracionR` es **SIEMPRE requerido**, pero su valor cambia:
- Con reemplazo → UUID nuevo
- Sin reemplazo → Mismo UUID del DTE original

---

## Tabla de Campos (Iteración 13)

| Campo | Tipo | Valor |
|-------|------|-------|
| `codigoGeneracion` | string | UUID del DTE a anular |
| `selloRecibido` | string | Sello del MH |
| `codigoGeneracionR` | string | UUID (reemplazo o mismo) |
| `motivo` | **integer** | 1, 2, o 3 |

---

## Historial de `motivo`

| Iteración | Valor | Resultado |
|-----------|-------|-----------|
| 9 | Texto descriptivo | ❌ |
| 10 | Integer `2` | ❌ |
| 11 | String `"02"` | ❌ |
| 12 | String `"2"` | ❌ |
| **13** | Integer `2` | ⏳ **Probar** |

---

**Última actualización**: 2025-11-05 21:36
**Estado**: ⏳ Esperando resultado de iteración 13
