# Implementación de Política de Reintentos del Ministerio de Hacienda

## ✅ Cumplimiento Completo

Este proyecto ahora cumple **100%** con la política de reintentos oficial del Ministerio de Hacienda de El Salvador.

## Cambios Implementados

### 1. Configuración (`config/dte.php`)
- ✅ Agregado endpoint de consulta: `mh_consulta_url`
- ✅ Timeout de 8 segundos: `timeout_envio`
- ✅ Máximo 2 reintentos: `max_reintentos`

### 2. Servicio MHApiService (`app/Services/MHApiService.php`)

#### Nuevo método: `consultarEstadoDTE()`
- Consulta el estado de un DTE en el MH
- Verifica si el documento ya fue recibido
- Estados reconocidos: PROCESADO, RECIBIDO, APROBADO

#### Método actualizado: `enviarDTE()`
- **Timeout de 8 segundos** por intento (según política MH)
- **Hasta 3 intentos totales** (1 inicial + 2 reintentos)
- **Flujo implementado**:
  1. Intenta enviar DTE con timeout de 8s
  2. Si falla o hace timeout → consulta estado del DTE
  3. Si DTE no fue recibido → espera 1s y reintenta
  4. Repite hasta máximo 2 reintentos adicionales
  5. Si el DTE ya fue recibido (detectado en consulta) → retorna éxito

#### Nuevo método privado: `intentarEnviarDTE()`
- Realiza un único intento de envío
- Maneja timeouts con `ConnectionException`
- Retorna indicador de timeout para el flujo de reintentos

### 3. Controlador (`app/Http/Controllers/FacturaController.php`)
- ✅ Pasa `codigo_generacion` en metadata (necesario para consultar estado)
- ✅ Guarda número de intentos realizados
- ✅ Muestra mensajes informativos al usuario con número de intentos
- ✅ Almacena en `respuesta_mh` si el DTE fue detectado en consulta

### 4. Base de Datos
- ✅ Nueva migración: `2025_10_20_000000_add_intentos_envio_to_facturas_table.php`
- ✅ Nuevo campo: `facturas.intentos_envio` (integer, default 0)
- ✅ Modelo actualizado: `Factura::$fillable` incluye `intentos_envio`

### 5. Documentación
- ✅ CLAUDE.md actualizado con sección "MH Retry Policy"
- ✅ Este documento creado con detalles de implementación

## Cumplimiento de Requisitos MH

### Requisito 1: Timeout de 8 segundos
✅ **CUMPLE** - Configurado en `config/dte.php:64` y aplicado en `MHApiService.php:359`

### Requisito 2: Consultar estado si no responde
✅ **CUMPLE** - Método `consultarEstadoDTE()` implementado y se ejecuta automáticamente después de cada fallo

### Requisito 3: Reintentar si no fue recibido
✅ **CUMPLE** - Lógica de reintentos en `enviarDTE()` con máximo 2 reintentos (líneas 224-330)

### Requisito 4: Máximo 2 reintentos
✅ **CUMPLE** - Configurado en `max_reintentos = 2`, total de 3 intentos (1 inicial + 2 reintentos)

## Configuración Requerida

Agregar al archivo `.env`:

```bash
# Política de Reintentos MH
DTE_TIMEOUT_ENVIO=8           # 8 segundos según política oficial
DTE_MAX_REINTENTOS=2          # Máximo 2 reintentos
MH_CONSULTA_URL=https://apitest.dtes.mh.gob.sv/fesv/consultadte
```

## Ejecutar Migración

Para activar el tracking de intentos en la base de datos:

```bash
php artisan migrate
```

Esto agregará el campo `intentos_envio` a la tabla `facturas`.

## Comportamiento del Sistema

### Escenario 1: Envío exitoso en primer intento
- Se envía el DTE
- MH responde exitosamente
- `intentos_envio = 1`
- Mensaje: "Factura enviada exitosamente"

### Escenario 2: Timeout → Reintento exitoso
- Intento 1: Timeout después de 8s
- Consulta estado: No recibido
- Intento 2: Envío exitoso
- `intentos_envio = 2`
- Mensaje: "Factura enviada exitosamente (después de 2 intentos)"

### Escenario 3: Timeout → DTE ya recibido
- Intento 1: Timeout después de 8s
- Consulta estado: **Ya fue recibido** (el timeout fue después de que MH lo recibió)
- No reintenta
- `intentos_envio = 1`
- `recibido_en_consulta = true`
- Mensaje: "Factura enviada exitosamente"

### Escenario 4: Todos los intentos fallan
- Intento 1: Falla
- Consulta: No recibido → Intento 2: Falla
- Consulta: No recibido → Intento 3: Falla
- `intentos_envio = 3`
- Estado: `firmado_sin_enviar`
- Mensaje: "Error después de 3 intentos: [detalle]"

## Logs de Monitoreo

El sistema genera logs detallados para cada operación:

- 📤 `Intento X de Y - Enviando DTE al MH`
- ✅ `DTE enviado exitosamente en intento X`
- ⚠️ `Intento X falló`
- 🔍 `Consultando estado antes de reintentar`
- ↻ `DTE no fue recibido, reintentando...`
- ⏱️ `Timeout o error de conexión al enviar DTE`

## Testing

Para probar la política de reintentos:

1. **Simular timeout**: Reducir temporalmente el timeout a 1 segundo
2. **Simular error de red**: Desconectar temporalmente la conexión
3. **Verificar logs**: Revisar `storage/logs/laravel.log` para ver el flujo completo
4. **Verificar BD**: Consultar campo `intentos_envio` en la tabla `facturas`

## Beneficios de esta Implementación

✅ Cumplimiento normativo con MH
✅ Mayor resiliencia ante problemas de red
✅ Evita duplicación de DTEs
✅ Tracking completo de intentos
✅ Logs detallados para auditoría
✅ Configuración flexible (puede ajustar timeout y reintentos)
✅ Manejo inteligente de timeouts tardíos

---

**Fecha de Implementación**: 2025-10-20
**Versión del Sistema**: Laravel 10
**Normativa**: Ministerio de Hacienda de El Salvador
