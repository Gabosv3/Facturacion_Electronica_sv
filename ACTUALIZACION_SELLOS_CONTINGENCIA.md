# Actualización de Sellos de Recepción en Contingencias

## Fecha: 2025-11-05

## 📋 Funcionalidad Implementada

Cuando se procesa una contingencia y el MH devuelve el estado `PROCESADO`, el sistema ahora **actualiza automáticamente** todas las facturas con:

1. ✅ Sello de recepción del MH
2. ✅ Estado cambiad a `enviado_mh`
3. ✅ Información completa de la respuesta del MH

---

## 🔄 Flujo de Actualización

### Antes (Sin Actualización Automática)
```
1. Contingencia procesada ✅
2. Facturas quedan en estado: firmado_contingencia ❌
3. Sin sello de recepción ❌
4. Usuario debe consultar manualmente cada factura 😓
```

### Ahora (Con Actualización Automática)
```
1. Contingencia procesada ✅
2. Sistema extrae sellos del detalle del lote ✅
3. Cada factura se actualiza automáticamente:
   - Estado → enviado_mh ✅
   - respuesta_mh.envio_mh.selloRecibido ✅
   - estado_lote → PROCESADO ✅
4. Listo para usar (incluyendo invalidaciones) 🎉
```

---

## 🛠️ Implementación Técnica

### Método Principal

**Archivo:** `app/Models/EventoContingencia.php`

```php
public function marcarProcesado(array $detalleLote): void
{
    // 1. Marcar evento como procesado
    $this->update([
        'estado' => self::ESTADO_PROCESADO,
        'detalle_lote' => $detalleLote,
    ]);

    // 2. Actualizar facturas con sellos
    $this->actualizarFacturasConSellos($detalleLote);
}
```

### Método de Actualización

```php
private function actualizarFacturasConSellos(array $detalleLote): void
{
    // Extrae respuestaDTE del detalle del lote
    $respuestaDTE = $detalleLote['respuestaDTE'];

    foreach ($respuestaDTE as $dteRespuesta) {
        // 1. Buscar factura por código de generación
        $factura = Factura::where('codigo_generacion', $codigoGeneracion)
            ->where('evento_contingencia_id', $this->id)
            ->first();

        // 2. Extraer sello y estado
        $selloRecibido = $dteRespuesta['selloRecibido'];
        $estado = $dteRespuesta['estado'];

        // 3. Actualizar respuesta_mh
        $respuestaMhActual['envio_mh'] = [
            'selloRecibido' => $selloRecibido,
            'estado' => $estado,
            'fhProcesamiento' => $dteRespuesta['fhProcesamiento'],
            'via_contingencia' => true,
            'codigo_lote' => $this->codigo_lote,
        ];

        // 4. Actualizar factura
        $factura->update([
            'estado' => 'enviado_mh',
            'estado_lote' => $estado,
            'respuesta_mh' => $respuestaMhActual,
        ]);
    }
}
```

---

## 📊 Estructura de Datos

### Respuesta del MH (consultarLote)

```json
{
  "success": true,
  "estado": "PROCESADO",
  "data": {
    "respuestaDTE": [
      {
        "codigoGeneracion": "6871C033-B9EC-4BC6-9B12-BEC84F52C860",
        "selloRecibido": "202529177DF5CE05475783A8879489DD27FFVHTD",
        "estado": "PROCESADO",
        "fhProcesamiento": "05/11/2025 22:00:00",
        "clasificaMsg": "10",
        "codigoMsg": "001",
        "descripcionMsg": "RECIBIDO",
        "observaciones": []
      },
      {
        "codigoGeneracion": "...",
        "selloRecibido": "...",
        ...
      }
    ]
  }
}
```

### Estructura de respuesta_mh Actualizada

```json
{
  "jwt_firmado": "eyJhbGc...",
  "firma": {
    "body": "eyJhbGc...",
    "status": "OK"
  },
  "envio_mh": {
    "selloRecibido": "202529177DF5CE05475783A8879489DD27FFVHTD",
    "estado": "PROCESADO",
    "fhProcesamiento": "05/11/2025 22:00:00",
    "clasificaMsg": "10",
    "codigoMsg": "001",
    "descripcionMsg": "RECIBIDO",
    "observaciones": [],
    "via_contingencia": true,
    "codigo_lote": "LOTE-UUID-123"
  }
}
```

---

## 🎯 Casos de Uso

### Caso 1: Contingencia Procesada Exitosamente

```bash
# 1. Procesar contingencia
php artisan contingencia:procesar

# Salida:
📦 Procesando Evento #1
📤 Paso 1: Enviando evento de contingencia al MH...
   ✅ Evento enviado exitosamente

📦 Paso 2: Enviando lote de DTEs...
   ✅ Lote enviado exitosamente
   📋 Código de lote: ABC123...

🔍 Paso 3: Consultando estado del lote...
   ✅ Estado del lote: PROCESADO
   🎉 Lote procesado completamente por el MH

# 🔄 Sistema actualiza automáticamente:
🔄 Actualizando facturas con sellos de recepción
✅ Factura actualizada con sello de recepción
   - factura_id: 97
   - numero_control: DTE-01-S001P001-000000000000097
   - sello_recibido: 202529177DF5CE054...
   - estado: PROCESADO

📊 Resumen de actualización de facturas
   - facturas_actualizadas: 5
   - facturas_sin_sello: 0
   - total_procesadas: 5
```

### Caso 2: Verificar Factura Después de Contingencia

```php
$factura = Factura::where('numero_control', 'DTE-01-S001P001-000000000000097')->first();

echo $factura->estado; // "enviado_mh"
echo $factura->respuesta_mh['envio_mh']['selloRecibido']; // "202529177DF5CE..."
```

### Caso 3: Invalidar Factura de Contingencia

Ahora se puede invalidar sin problemas porque tiene el sello:

```php
$invalidacionService->validarInvalidacion($facturaId);
// ✅ Validación exitosa: tiene sello recibido

$invalidacionService->construirJsonEvento($evento);
// ✅ Construye JSON correctamente con selloRecibido
```

---

## 📝 Logs Generados

El sistema genera logs detallados para tracking:

### Log de Inicio
```
🔄 Actualizando facturas con sellos de recepción
   evento_id: 1
   cantidad_respuestas: 5
```

### Log por Factura Actualizada
```
✅ Factura actualizada con sello de recepción
   factura_id: 97
   numero_control: DTE-01-S001P001-000000000000097
   sello_recibido: 202529177DF5CE054...
   estado: PROCESADO
```

### Log de Resumen
```
📊 Resumen de actualización de facturas
   evento_id: 1
   facturas_actualizadas: 5
   facturas_sin_sello: 0
   total_procesadas: 5
```

### Log de Advertencia (Si Faltan Sellos)
```
⚠️ DTE sin sello de recepción
   codigo_generacion: 6871C033-...
   estado: RECHAZADO
```

---

## ⚠️ Casos Especiales

### 1. DTEs Rechazados

Si un DTE fue rechazado por el MH:
- ❌ NO se actualiza el estado a `enviado_mh`
- ❌ NO se agrega sello (porque no existe)
- ✅ Se registra en logs como "factura_sin_sello"
- ✅ Permanece en estado `firmado_contingencia`

### 2. DTEs No Encontrados

Si el MH devuelve un código de generación que no existe en la BD:
- ⚠️ Se registra warning en logs
- ✅ Continúa con las demás facturas

### 3. Estructura Inesperada

Si `detalleLote` no tiene la estructura esperada:
- ⚠️ Warning: "No se encontró respuestaDTE en el detalle del lote"
- ✅ El evento se marca como procesado
- ❌ Facturas NO se actualizan automáticamente
- 💡 Se deben actualizar manualmente con consultas individuales

---

## 🔧 Troubleshooting

### Problema: Facturas no se actualizan

**Verificar 1: Estructura de respuesta**
```php
// En logs, buscar:
⚠️ No se encontró respuestaDTE en el detalle del lote
```

**Solución:** Ver estructura real de `$detalleLote` y ajustar código si el MH cambió el formato.

**Verificar 2: Estado del lote**
```bash
php artisan contingencia:procesar
```

Si dice "El lote aún está siendo procesado", esperar unos minutos y volver a ejecutar.

### Problema: Solo algunas facturas se actualizan

**Verificar logs:**
```
facturas_actualizadas: 3
facturas_sin_sello: 2
```

Las facturas sin sello fueron rechazadas por el MH. Revisar `detalle_lote` para ver errores.

---

## 🎁 Beneficios

1. ✅ **Automatización completa** - No requiere intervención manual
2. ✅ **Invalidaciones funcionales** - Facturas tienen el sello necesario
3. ✅ **Trazabilidad** - Logs detallados de cada actualización
4. ✅ **Robustez** - Maneja casos especiales (rechazos, errores, etc.)
5. ✅ **Consistencia** - Estado de facturas siempre actualizado

---

## 📖 Ejemplo Completo

### Paso 1: Crear Contingencia

```bash
# Usuario crea contingencia en el sistema
# Facturas se crean con estado: firmado_contingencia
```

### Paso 2: Cerrar Contingencia

```bash
# Usuario cierra contingencia cuando se restaura conexión
```

### Paso 3: Procesar Contingencia

```bash
php artisan contingencia:procesar
```

**Resultado:**
```
📦 Procesando Evento #1
   Código: ABC-123-DEF
   Tipo: Falla en el servicio de internet
   Cantidad DTEs: 5
   Estado actual: cerrado

📤 Paso 1: Enviando evento de contingencia al MH...
   ✅ Evento enviado exitosamente

📦 Paso 2: Enviando lote de DTEs...
   ✅ Lote enviado exitosamente
   📋 Código de lote: LOTE-456-GHI

🔍 Paso 3: Consultando estado del lote...
   ✅ Estado del lote: PROCESADO
   🎉 Lote procesado completamente por el MH

🔄 Actualizando facturas con sellos de recepción
✅ Factura actualizada con sello de recepción
   factura_id: 97
   numero_control: DTE-01-S001P001-000000000000097
   sello_recibido: 202529177DF5CE054...

[... 4 facturas más ...]

📊 Resumen de actualización de facturas
   facturas_actualizadas: 5
   facturas_sin_sello: 0
   total_procesadas: 5

✅ Procesamiento completado
```

### Paso 4: Verificar en la Interfaz

```
Ir a /facturas
- Factura #97: Estado = "Enviado al MH" ✅
- Ver detalle: Sello visible ✅
- Botón "Invalidar" habilitado ✅
```

---

**Última actualización**: 2025-11-05
**Archivo**: `app/Models/EventoContingencia.php` líneas 119-231
**Funcionalidad**: Actualización automática de sellos en contingencias
