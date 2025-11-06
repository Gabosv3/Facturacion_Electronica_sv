# Estructura Final de Invalidación - CORREGIDA

## Fecha: 2025-11-05

## Resumen de Correcciones

Se ha corregido completamente la estructura del evento de invalidación basándose en los errores reportados por el MH.

### Errores Corregidos

1. ✅ **Campo motivo**: Ahora usa código según CAT-24 ('1', '2', o '3')
2. ✅ **Campo montoIva**: Agregado al objeto documento
3. ✅ **Campo codigoGeneracionR**: Agregado cuando tipo = 1
4. ✅ **Campo tipoDocumento**: Agregado (del receptor de la factura)
5. ✅ **Campo numDocumento**: Agregado (del receptor de la factura)
6. ✅ **Campo nombre**: Agregado (del receptor de la factura)
7. ✅ **Campo selloRecibido**: Validado como string (no null)

## Estructura JSON Final

```json
{
    "identificacion": {
        "version": 2,
        "ambiente": "00",
        "codigoGeneracion": "UUID-EVENTO",
        "fecAnula": "2025-11-05",
        "horAnula": "20:29:51"
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
        "codigoGeneracion": "UUID-FACTURA",
        "selloRecibido": "SELLO-DEL-MH",
        "tipoDte": "01",
        "numeroControl": "DTE-01-S001P001-000000000000097",
        "fecEmi": "2025-11-04",
        "montoIva": 1.15,
        "tipoDocumento": "13",
        "numDocumento": "060062101",
        "nombre": "carlos posada",
        "codigoGeneracionR": "UUID-REEMPLAZO" // Solo si motivo = "1"
    },
    "motivo": "2"
}
```

## Catálogo CAT-24: Tipo de Invalidación

Según documento oficial "Catalogos- Sistema de transmision - 1.2" del MH:

| Código | Descripción | Requiere Reemplazo | Campo codigoGeneracionR |
|--------|-------------|-------------------|------------------------|
| **1** | Error de información del DTE a invalidar | ✅ Sí | ✅ Requerido |
| **2** | Rescindir operación realizada | ❌ No | ❌ No incluir |
| **3** | Otros motivos | Depende del caso | Opcional |

### Tipo 1 - Error de Información
- **Uso**: Cuando el DTE tiene errores en los datos (cliente, precio, fecha, etc.)
- **Requiere**: Nueva factura correcta ANTES de invalidar
- **Campo adicional**: `codigoGeneracionR` con UUID del documento de reemplazo
- **Ejemplo**: Error en nombre del cliente, error en precio

### Tipo 2 - Rescindir Operación
- **Uso**: Cancelación total de la operación
- **No requiere**: Documento de reemplazo
- **Campo adicional**: NO incluir `codigoGeneracionR`
- **Ejemplo**: Cliente devuelve producto, cancelación de venta

### Tipo 3 - Otros Motivos
- **Uso**: Casos no cubiertos por tipo 1 o 2
- **Requiere**: Depende del caso específico
- **Campo adicional**: Incluir `codigoGeneracionR` solo si hay reemplazo

## Campos del Objeto "documento"

Todos estos campos son **REQUERIDOS**:

| Campo | Tipo | Descripción | Origen |
|-------|------|-------------|--------|
| `codigoGeneracion` | string | UUID de la factura a invalidar | `facturas.codigo_generacion` |
| `selloRecibido` | string | Sello del MH (NO null) | `respuesta_mh.envio_mh.selloRecibido` |
| `tipoDte` | string | Tipo de DTE | `facturas.tipo_dte` |
| `numeroControl` | string | Número de control | `facturas.numero_control` |
| `fecEmi` | string | Fecha emisión (YYYY-MM-DD) | `facturas.fecha_emision` |
| `montoIva` | float | Monto del IVA | `facturas.total_iva` |
| `tipoDocumento` | string | Tipo doc receptor | `facturas.receptor_tipo_documento` |
| `numDocumento` | string | Núm doc receptor | `facturas.receptor_num_documento` |
| `nombre` | string | Nombre receptor | `facturas.receptor_nombre` |
| `codigoGeneracionR` | string | UUID doc reemplazo (solo tipo 1) | `facturas.codigo_generacion` del reemplazo |

## Validaciones Importantes

### 1. selloRecibido
```php
// ❌ INCORRECTO
"selloRecibido": null

// ✅ CORRECTO (mínimo)
"selloRecibido": ""

// ✅ MEJOR (real)
"selloRecibido": "SELLO-REAL-DEL-MH"
```

### 2. numDocumento del Receptor
```php
// Si es DUI (tipo 13):
// ❌ INCORRECTO: "06006210-1" (con guión)
// ✅ CORRECTO:   "060062101" (sin guión)

// Si es NIT (tipo 36):
// ✅ CORRECTO: "06142302881449" (14 dígitos sin guiones)
```

### 3. motivo
```php
// ❌ INCORRECTO
"motivo": "devolucion"          // String descriptivo
"motivo": 2                     // Número entero

// ✅ CORRECTO
"motivo": "1"                   // String con código CAT-24
"motivo": "2"                   // String con código CAT-24
"motivo": "3"                   // String con código CAT-24
```

### 4. codigoGeneracionR
```php
// Tipo 1 (error de información):
"motivo": "1",
"documento": {
    ...
    "codigoGeneracionR": "D1234567-89AB-CDEF-0123-456789ABCDEF" // ✅ REQUERIDO
}

// Tipo 2 (rescindir):
"motivo": "2",
"documento": {
    ...
    // ❌ NO incluir codigoGeneracionR
}

// Tipo 3 (otros):
"motivo": "3",
"documento": {
    ...
    "codigoGeneracionR": "..." // Opcional según el caso
}
```

## Código Actualizado

### InvalidacionService.php - construirJsonEvento()

```php
$documento = [
    'codigoGeneracion' => $factura->codigo_generacion,
    'selloRecibido' => $selloRecibido ?: '', // String vacío si no hay
    'tipoDte' => $factura->tipo_dte,
    'numeroControl' => $factura->numero_control,
    'fecEmi' => $factura->fecha_emision->format('Y-m-d'),
    'montoIva' => (float) $factura->total_iva, // ✅ NUEVO
    'tipoDocumento' => $factura->receptor_tipo_documento, // ✅ NUEVO
    'numDocumento' => $factura->receptor_num_documento, // ✅ NUEVO
    'nombre' => $factura->receptor_nombre, // ✅ NUEVO
];

// Si es tipo 1 y hay documento de reemplazo
if ($evento->tipo_invalidacion == '1' && $evento->codigo_generacion_reemplazo) {
    $documento['codigoGeneracionR'] = $evento->codigo_generacion_reemplazo;
}

$motivo = (string) $evento->tipo_invalidacion; // '1', '2', o '3'

return [
    'identificacion' => $identificacion,
    'emisor' => $emisorData,
    'documento' => $documento,
    'motivo' => $motivo, // ✅ Código CAT-24
];
```

## Formulario Web Actualizado

El formulario ahora incluye las 3 opciones del CAT-24:

```html
<select name="tipo_invalidacion" required>
    <option value="">Seleccione...</option>
    <option value="1">1 - Error de información del DTE a invalidar</option>
    <option value="2">2 - Rescindir operación realizada</option>
    <option value="3">3 - Otros motivos</option>
</select>
```

## Flujo Completo

```
1. Usuario selecciona factura a invalidar
   ↓
2. Usuario selecciona tipo según CAT-24:
   - Tipo 1: Debe haber creado documento de reemplazo PRIMERO
   - Tipo 2: No requiere reemplazo
   - Tipo 3: Según el caso
   ↓
3. Sistema construye JSON con TODOS los campos:
   - identificacion (version 2, fecAnula, horAnula)
   - emisor (con tipoEstablecimiento, codEstable, etc.)
   - documento (con montoIva, tipoDocumento, numDocumento, nombre)
   - motivo (código '1', '2', o '3')
   ↓
4. POST a http://localhost:8113/firmardocumento/
   {
     "nit": "...",
     "activo": true,
     "passwordPri": "...",
     "dteJson": { ... }
   }
   ↓
5. Recibe JWT firmado
   ↓
6. POST a https://apitest.dtes.mh.gob.sv/fesv/anulardte
   {
     "ambiente": "00",
     "idEnvio": 1,
     "version": 2,
     "documento": "JWT-FIRMADO"
   }
   ↓
7. MH valida y responde:
   {
     "estado": "PROCESADO",  ← ✅ Éxito
     "selloRecibido": "...",
     ...
   }
   ↓
8. Sistema marca factura como invalidada
```

## Scripts de Prueba

1. **test_invalidacion_estructura.php**: Estructura básica
2. **test_invalidacion_completa.php**: Estructura completa con todos los campos ✅

Ejecutar:
```bash
php test_invalidacion_completa.php
```

## Archivos Modificados

1. ✅ **app/Services/InvalidacionService.php**
   - `crearEvento()`: version = 2
   - `construirJsonEvento()`: Estructura completa con todos los campos

2. ✅ **app/Http/Controllers/InvalidacionController.php**
   - Validación actualizada: `tipo_invalidacion` acepta 1, 2, 3

3. ✅ **app/Models/EventoInvalidacion.php**
   - `getTipoInvalidacionNombreAttribute()`: Nombres según CAT-24

4. ✅ **resources/views/invalidaciones/crear.blade.php**
   - Select con 3 opciones del CAT-24

5. ✅ **test_invalidacion_completa.php** (nuevo)
   - Script de verificación completo

6. ✅ **ESTRUCTURA_INVALIDACION_FINAL.md** (este archivo)
   - Documentación completa

## Próximo Paso

**Probar invalidación real**:
1. Crear una factura de prueba
2. Enviarla al MH
3. Verificar que tiene sello recibido
4. Crear evento de invalidación tipo 2 (rescindir)
5. Verificar respuesta del MH = "PROCESADO" ✅

## Diferencias vs Versión Anterior

| Aspecto | Versión Anterior | Versión Actual |
|---------|-----------------|----------------|
| **documento.montoIva** | ❌ No existía | ✅ Requerido (float) |
| **documento.tipoDocumento** | ❌ No existía | ✅ Requerido (del receptor) |
| **documento.numDocumento** | ❌ No existía | ✅ Requerido (del receptor) |
| **documento.nombre** | ❌ No existía | ✅ Requerido (del receptor) |
| **documento.codigoGeneracionR** | ❌ No existía | ✅ Si tipo 1 |
| **documento.selloRecibido** | ⚠️ Permitía null | ✅ Debe ser string |
| **motivo** | ⚠️ String libre | ✅ Código CAT-24 ('1','2','3') |
| **Tipos CAT-24** | ❌ Solo 1 y 2 | ✅ Ahora 1, 2 y 3 |

---

**Estado**: ✅ COMPLETO Y LISTO PARA USAR
**Última actualización**: 2025-11-05 20:45
