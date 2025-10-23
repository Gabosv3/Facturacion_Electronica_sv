# Guía de Uso del Sistema de Contingencia

## 📋 ¿Qué es la Contingencia?

La contingencia permite seguir facturando cuando existe un **evento de fuerza mayor** que imposibilita enviar los DTEs al Ministerio de Hacienda en tiempo real. Los DTEs se almacenan localmente y se envían posteriormente cuando se restablece la conexión.

## 🚨 Tipos de Contingencia (CAT-024)

Según el Ministerio de Hacienda, los tipos de contingencia son:

1. **Falla en el suministro de energía eléctrica del emisor**
2. **Falla del sistema de emisión del contribuyente**
3. **Falla en el servicio de internet**
4. **Falla en los servicios de la Administración Tributaria**
5. **Otros eventos de fuerza mayor**

## 🔄 Proceso Completo de Contingencia

### Fase 1: Durante la Contingencia

1. **Activar el modo contingencia** (ver sección "Activar Contingencia")
2. **Emitir facturas normalmente** - Se marcarán automáticamente como "en contingencia"
3. **Los DTEs se firman localmente** pero NO se envían al MH

### Fase 2: Al Restablecer la Conexión

1. **Cerrar el evento de contingencia**
2. **Enviar el evento al MH** (informando los DTEs emitidos)
3. **Enviar el lote de DTEs** firmados durante la contingencia
4. **Consultar el estado** del procesamiento

---

## 🛠️ Comandos Disponibles

### 1. Procesar Contingencias Pendientes

```bash
php artisan contingencia:procesar
```

Este comando ejecuta automáticamente los 3 pasos:
- Envía el evento de contingencia al MH
- Envía el lote de DTEs
- Consulta el estado del procesamiento

**Cuándo usar**: Después de cerrar un evento de contingencia y restablecer la conexión.

---

## 📝 Uso Paso a Paso

### Escenario: Falla de Internet

#### Paso 1: Activar Modo Contingencia

1. Ve al panel de administración: `/admin/contingencias`
2. Haz clic en **"Crear Evento de Contingencia"**
3. Selecciona el tipo: **"3 - Falla en el servicio de internet"**
4. Escribe el motivo: "Caída del servicio de internet por mantenimiento del proveedor"
5. Haz clic en **"Activar Contingencia"**

✅ **Resultado**: El sistema ahora está en modo contingencia activo.

#### Paso 2: Facturar Normalmente

Durante la contingencia, sigue emitiendo facturas normalmente:

1. Ve a `/facturas/crear` o `/facturas/crear-ccf`
2. Llena el formulario como siempre
3. Al guardar, la factura se marcará automáticamente como **"en contingencia"**
4. El DTE se firma localmente pero **NO se envía al MH**

💡 **Nota**: Puedes emitir hasta **100 DTEs por lote** (máximo permitido por el MH).

#### Paso 3: Cerrar Contingencia

Cuando se restablezca la conexión:

1. Ve a `/admin/contingencias`
2. Busca el evento activo
3. Haz clic en **"Cerrar Contingencia"**

✅ **Resultado**: El evento cambia a estado "cerrado" y está listo para enviar al MH.

#### Paso 4: Procesar y Enviar al MH

Ejecuta el comando:

```bash
php artisan contingencia:procesar
```

**Salida esperada**:

```
🚀 Procesando eventos de contingencia...

📋 Encontrados 1 eventos de contingencia listos para procesar

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
📦 Procesando Evento #1
   Código: A1B2C3D4-E5F6-7890-ABCD-EF1234567890
   Tipo: Falla en el servicio de internet
   Cantidad DTEs: 15
   Estado actual: cerrado

📤 Paso 1: Enviando evento de contingencia al MH...
   ✅ Evento enviado exitosamente

📦 Paso 2: Enviando lote de DTEs...
   ✅ Lote enviado exitosamente
   📋 Código de lote: LOTE-1234-ABCD-5678

🔍 Paso 3: Consultando estado del lote...
   ⏳ El lote aún está siendo procesado por el MH
   💡 Ejecuta este comando nuevamente en unos minutos

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
✅ Procesamiento completado
```

#### Paso 5: Verificar Procesamiento

Espera 2-3 minutos y vuelve a ejecutar:

```bash
php artisan contingencia:procesar
```

**Salida esperada cuando está procesado**:

```
🔍 Paso 3: Consultando estado del lote...
   ✅ Estado del lote: PROCESADO
   🎉 Lote procesado completamente por el MH
```

---

## 🕐 Horarios y Límites (según MH)

### Ambiente de Pruebas (ambiente: "00")
- **Máximo por lote**: 100 DTEs
- **Máximo de lotes**: 300
- **Tiempo de procesamiento**: 2-3 minutos por lote de 100 DTEs
- **Horario de recepción**: 08:00 AM a 05:00 PM
- **Horario para contingencia**: 24 horas, 365 días

### Ambiente Productivo (ambiente: "01")
- **Máximo por lote**: 100 DTEs
- **Máximo de lotes**: 400
- **Tiempo de procesamiento**: 1-3 minutos por lote de 100 DTEs
- **Horario de recepción**: 10:00 PM a 05:00 AM
- **Horario para contingencia**: 24 horas, 365 días

---

## ⚠️ Consideraciones Importantes

### ✅ Recomendaciones

1. **Cierra la contingencia lo antes posible** después de restablecer la conexión
2. **Procesa los lotes inmediatamente** después de cerrar la contingencia
3. **Verifica el estado de los lotes** ejecutando el comando cada 3-5 minutos
4. **Mantén logs de las contingencias** para auditoría

### ❌ Restricciones

1. **No puedes tener múltiples contingencias activas** simultáneamente
2. **Máximo 100 DTEs por lote** - Si tienes más, se procesarán en múltiples lotes
3. **No puedes modificar** un evento de contingencia después de enviarlo al MH
4. **Los DTEs en contingencia deben estar firmados** antes de enviar el lote

---

## 🔍 Verificar Estado de Contingencias

### Ver Eventos de Contingencia

1. Ve a `/admin/contingencias`
2. Verás una lista con todos los eventos:
   - **Activo**: Contingencia en curso, puedes seguir facturando
   - **Cerrado**: Listo para enviar al MH
   - **Enviado**: Evento enviado, esperando procesar lote
   - **Procesado**: Completado exitosamente

### Logs del Sistema

Revisa los logs para diagnóstico:

```bash
tail -f storage/logs/laravel.log | grep -i contingencia
```

---

## 📊 Base de Datos

### Tablas Involucradas

**`eventos_contingencia`**: Almacena los eventos de contingencia
- `codigo_evento`: UUID del evento
- `tipo_contingencia`: Tipo 1-5 según CAT-024
- `fecha_hora_inicio` / `fecha_hora_fin`: Rango de la contingencia
- `cantidad_dte`: Cantidad de DTEs emitidos
- `estado`: activo, cerrado, enviado, procesado
- `codigo_lote`: Código retornado por el MH

**`facturas`** (campos agregados):
- `en_contingencia`: Boolean, indica si fue emitida en contingencia
- `evento_contingencia_id`: Relación con el evento
- `estado_lote`: Estado del procesamiento en el lote

### Consultas Útiles

```sql
-- Ver evento activo actual
SELECT * FROM eventos_contingencia
WHERE estado = 'activo'
ORDER BY fecha_hora_inicio DESC
LIMIT 1;

-- Ver facturas de una contingencia
SELECT * FROM facturas
WHERE evento_contingencia_id = 1;

-- Contar DTEs por estado de contingencia
SELECT estado, COUNT(*)
FROM facturas
WHERE en_contingencia = 1
GROUP BY estado;
```

---

## 🐛 Solución de Problemas

### Problema: "No se pudo enviar el evento de contingencia"

**Causa**: Falla de conexión o token inválido

**Solución**:
```bash
# Renovar token
php artisan mh:renovar-token

# Intentar de nuevo
php artisan contingencia:procesar
```

### Problema: "El lote no se procesa"

**Causa**: El MH está procesando (puede tardar hasta 3 minutos)

**Solución**:
- Espera 3-5 minutos
- Vuelve a ejecutar `php artisan contingencia:procesar`

### Problema: "No hay DTEs firmados para enviar"

**Causa**: Las facturas no se firmaron correctamente

**Solución**:
1. Revisa los logs: `storage/logs/laravel.log`
2. Verifica que la API de firma esté funcionando
3. Vuelve a intentar firmar las facturas manualmente si es necesario

---

## 📚 Referencias

- **Manual MH**: Documentación oficial del Ministerio de Hacienda
- **CAT-024**: Catálogo de tipos de contingencia
- **Portal**: https://factura.gob.sv/

---

## ✅ Checklist de Contingencia

- [ ] Evento de contingencia creado y activado
- [ ] Facturas emitidas durante la contingencia
- [ ] Todas las facturas tienen estado "firmado_contingencia"
- [ ] Contingencia cerrada después de restablecer conexión
- [ ] Comando `contingencia:procesar` ejecutado
- [ ] Evento enviado al MH exitosamente
- [ ] Lote de DTEs enviado exitosamente
- [ ] Lote marcado como "PROCESADO" por el MH
- [ ] Todas las facturas tienen sello de recepción del MH
