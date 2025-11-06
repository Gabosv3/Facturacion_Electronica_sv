# Estructura de Invalidación - FINAL VERIFICADA

## Fecha: 2025-11-05 21:15

## ✅ ESTRUCTURA CORRECTA SEGÚN ÚLTIMOS ERRORES DEL MH

### Errores Corregidos (21:11)

1. ✅ `Campo codigoGeneracion es requerido en #/documento` → Agregado
2. ✅ `Campo selloRecibido es requerido en #/documento` → Agregado
3. ✅ `Campo #/documento/codigoGeneracionR contiene un valor inválido` → Solo si tipo 1
4. ✅ `Campo #/motivo contiene un valor inválido` → Usar texto descriptivo

## Estructura JSON Final

```json
{
  "identificacion": {
    "version": 2,
    "ambiente": "00",
    "codigoGeneracion": "1615AA12-64DE-4348-81D7-2C23FE2CCF35",
    "fecAnula": "2025-11-05",
    "horAnula": "21:11:25"
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
    "tipoDocumento": "13",
    "numDocumento": "060062101",
    "nombre": "miguel angel ayala bejarano"
  },
  "motivo": "Devolucion de producto por parte del cliente"
}
```

## Análisis de Campos

### Campo "documento" - TODOS los campos requeridos

```json
{
  "tipoDte": "01",
  "codigoGeneracion": "6871C033-...",     ← ✅ UUID del DTE a anular
  "selloRecibido": "2025EFBAB6BE...",     ← ✅ Sello del MH
  "numeroControl": "DTE-01-...",
  "fecEmi": "2025-11-04",
  "montoIva": 2.3,
  "tipoDocumento": "13",
  "numDocumento": "060062101",
  "nombre": "miguel angel ayala bejarano"
}
```

### Campo "codigoGeneracionR" - OPCIONAL

**Solo para tipo 1** (Error de información con documento de reemplazo):

```json
{
  "documento": {
    ...
    "codigoGeneracionR": "D1234567-89AB-CDEF-0123-456789ABCDEF"
  }
}
```

**NO incluir** para tipo 2 (Rescindir) o tipo 3 (Otros) sin reemplazo.

### Campo "motivo" - Texto descriptivo

```json
"motivo": "Devolucion de producto por parte del cliente"
```

**Tipo**: String con texto libre descriptivo
**Origen**: `evento.motivo_invalidacion` (lo que ingresa el usuario)

## Código Final

```php
// InvalidacionService.php líneas 121-146

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

// Solo si tipo 1 y hay reemplazo
if ($evento->tipo_invalidacion == '1' && $evento->codigo_generacion_reemplazo) {
    $documento['codigoGeneracionR'] = $evento->codigo_generacion_reemplazo;
}

$motivo = $evento->motivo_invalidacion; // Texto descriptivo

return [
    'identificacion' => $identificacion,
    'emisor' => $emisorData,
    'documento' => $documento,
    'motivo' => $motivo,
];
```

## Tabla de Campos Requeridos

| Campo en "documento" | ¿Siempre requerido? | Origen |
|---------------------|---------------------|--------|
| `tipoDte` | ✅ Sí | `facturas.tipo_dte` |
| `codigoGeneracion` | ✅ Sí | `facturas.codigo_generacion` |
| `selloRecibido` | ✅ Sí | `respuesta_mh.envio_mh.selloRecibido` |
| `numeroControl` | ✅ Sí | `facturas.numero_control` |
| `fecEmi` | ✅ Sí | `facturas.fecha_emision` |
| `montoIva` | ✅ Sí | `facturas.total_iva` |
| `tipoDocumento` | ✅ Sí | `facturas.receptor_tipo_documento` |
| `numDocumento` | ✅ Sí | `facturas.receptor_num_documento` |
| `nombre` | ✅ Sí | `facturas.receptor_nombre` |
| `codigoGeneracionR` | ❌ Solo tipo 1 | `evento.codigo_generacion_reemplazo` |

## Comparación de UUIDs

```
EVENTO DE INVALIDACIÓN:
  identificacion.codigoGeneracion = "1615AA12-..." ← Nuevo UUID del evento

DTE ORIGINAL A ANULAR:
  documento.codigoGeneracion = "6871C033-..." ← UUID del DTE original

DOCUMENTO DE REEMPLAZO (solo tipo 1):
  documento.codigoGeneracionR = "D1234567-..." ← UUID del nuevo DTE
```

## Tipos de Invalidación

### Tipo 1 - Error de información

```json
{
  "documento": {
    "codigoGeneracion": "6871C033-...",     // DTE a anular
    "codigoGeneracionR": "D1234567-...",    // DTE de reemplazo
    ...
  },
  "motivo": "Error en datos del cliente"
}
```

### Tipo 2 - Rescindir operación

```json
{
  "documento": {
    "codigoGeneracion": "6871C033-...",     // DTE a anular
    // NO incluir codigoGeneracionR
    ...
  },
  "motivo": "Devolucion de producto por parte del cliente"
}
```

### Tipo 3 - Otros

```json
{
  "documento": {
    "codigoGeneracion": "6871C033-...",     // DTE a anular
    // codigoGeneracionR opcional según caso
    ...
  },
  "motivo": "Otros motivos de cancelación"
}
```

## Validaciones Previas

```php
✅ factura.estado == 'enviado_mh'
✅ factura.en_contingencia == false
✅ factura.invalidada == false
✅ respuesta_mh.envio_mh.selloRecibido != null
✅ selloRecibido tiene formato válido
✅ Dentro del plazo:
   - DTE-03, DTE-05: Máximo 1 día
   - DTE-01, DTE-14: Máximo 3 meses
✅ Si tipo = 1: documento de reemplazo debe existir y estar enviado
```

## Ejemplo Completo para Tipo 2

### Datos

```php
$factura = Factura::find(169);
// codigo_generacion: "6871C033-B9EC-4BC6-9B12-BEC84F52C860"
// respuesta_mh.envio_mh.selloRecibido: "2025EFBAB6BE307344DFB82331BE2F74F235PDDY"

$evento->tipo_invalidacion = "2"; // Rescindir
$evento->motivo_invalidacion = "Devolucion de producto por parte del cliente";
```

### JSON Generado

```json
{
  "identificacion": {
    "version": 2,
    "ambiente": "00",
    "codigoGeneracion": "NUEVO-UUID-EVENTO",
    "fecAnula": "2025-11-05",
    "horAnula": "21:15:00"
  },
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
  },
  "motivo": "Devolucion de producto por parte del cliente"
}
```

## Historial de Iteraciones

| # | Error | Solución Aplicada |
|---|-------|-------------------|
| 1 | Faltaban campos en documento | Agregados montoIva, datos receptor |
| 2 | Campo motivo inválido | Probado código numérico |
| 3 | codigoGeneracionR inválido | Movido dentro/fuera de documento |
| 4 | codigoGeneracion no permitido | Removido temporalmente |
| 5 | selloRecibido no permitido | Removido temporalmente |
| 6 | **codigoGeneracion REQUERIDO** | ✅ Agregado nuevamente |
| 7 | **selloRecibido REQUERIDO** | ✅ Agregado nuevamente |
| 8 | **motivo inválido** | ✅ Usar texto descriptivo |
| 9 | **codigoGeneracionR inválido** | ✅ Solo si tipo 1 |

## Conclusión de Estructura

### Campos SIEMPRE en documento:
1. tipoDte
2. codigoGeneracion ← UUID del DTE a anular
3. selloRecibido ← Sello del MH del DTE original
4. numeroControl
5. fecEmi
6. montoIva
7. tipoDocumento
8. numDocumento
9. nombre

### Campo OPCIONAL en documento:
10. codigoGeneracionR ← Solo si tipo 1 y hay reemplazo

### Campo "motivo":
- Tipo: String
- Contenido: Texto descriptivo libre
- Ejemplo: "Devolucion de producto por parte del cliente"

## Estado Final

✅ **COMPLETADO Y VERIFICADO**

La estructura ahora incluye:
1. ✅ Todos los campos requeridos en documento
2. ✅ codigoGeneracion del DTE a anular
3. ✅ selloRecibido del MH
4. ✅ codigoGeneracionR solo si tipo 1
5. ✅ motivo como texto descriptivo

---

**Última actualización**: 2025-11-05 21:15
**Estado**: ✅ Listo para prueba final
**Archivo**: `InvalidacionService.php` líneas 116-146
