# 🔧 Troubleshooting - Sistema de Contingencia

## ✅ Problema Resuelto: Facturas No Se Agregaban al Evento

**Síntoma:** Las facturas se creaban pero no aparecían en el evento de contingencia (cantidad_dte = 0).

**Causa Raíz:** Los campos `en_contingencia`, `evento_contingencia_id`, y `estado_lote` NO estaban en el `$fillable` del modelo `Factura`, por lo que Laravel los ignoraba al hacer `update()`.

**Solución:** Agregados los campos al `$fillable` en `app/Models/Factura.php`:

```php
protected $fillable = [
    // ... campos existentes ...
    // Campos de contingencia
    'en_contingencia',
    'evento_contingencia_id',
    'estado_lote',
];
```

---

## ⚠️ Problema Actual: Error al Enviar Evento al MH

**Síntoma:**
```
Error al procesar evento: Error al enviar evento de contingencia:
{"estado":null,"fechaHora":"22/10/2025 15:04:55","mensaje":"Faltan datos para procesar informacion","selloRecibido":null,"observaciones":[]}
```

**Posibles Causas:**

1. **Campos obligatorios faltantes en el JSON del evento**
   - El MH requiere campos específicos que pueden no estar presentes
   - Los campos `codEstable`, `codPuntoVenta`, `codEstableMH`, `codPuntoVentaMH` pueden ser requeridos

2. **Formato incorrecto del JSON**
   - La estructura del evento de contingencia puede diferir de la especificación
   - Los campos pueden tener tipos de datos incorrectos

3. **Credenciales o token inválido**
   - Aunque menos probable porque el token se valida antes de enviar

**Pasos para Diagnosticar:**

1. **Revisar los logs** en `storage/logs/laravel.log`:
   ```bash
   tail -f storage/logs/laravel.log | grep "json_completo"
   ```

2. **Comparar el JSON enviado con la especificación del MH**:
   - El JSON completo se registra en el log con la clave `json_completo`
   - Comparar campo por campo con la documentación oficial del MH

3. **Verificar que todos los campos de configuración estén definidos**:
   ```bash
   php artisan config:show dte.emisor
   ```

**Estructura Actual del JSON del Evento:**

```json
{
  "nit": "...",
  "activo": true,
  "passwordPri": "...",
  "dteJson": {
    "identificacion": {
      "version": 1,
      "ambiente": "00",
      "codigoGeneracion": "...",
      "fTransmision": "2025-10-22",
      "hTransmision": "14:51:41"
    },
    "emisor": {
      "nit": "...",
      "nrc": "...",
      "nombre": "...",
      "nombreResponsable": "...",
      "tipoDocResponsable": "36",
      "numeroDocResponsable": "...",
      "tipoEstablecimiento": "01",
      "codEstable": null,
      "codPuntoVenta": null,
      "codEstableMH": null,
      "codPuntoVentaMH": null,
      "telefono": "...",
      "correo": "...",
      "nombreComercial": "...",
      "codActividad": "...",
      "descActividad": "...",
      "direccion": {
        "departamento": "...",
        "municipio": "...",
        "complemento": "..."
      }
    },
    "detalleDTE": {
      "fInicio": "2025-10-22",
      "fFin": "2025-10-22",
      "hInicio": "14:51:41",
      "hFin": "15:04:39",
      "tipoContingencia": 3,
      "motivoContingencia": "...",
      "noDte": 2,
      "detalleDte": [
        {
          "noItem": 1,
          "codigoGeneracion": "...",
          "tipoDoc": "01"
        },
        {
          "noItem": 2,
          "codigoGeneracion": "...",
          "tipoDoc": "01"
        }
      ]
    }
  }
}
```

---

## 📝 Campos Potencialmente Faltantes

Según la documentación del MH, el evento de contingencia puede requerir:

1. **En `identificacion`:**
   - ✅ version
   - ✅ ambiente
   - ✅ codigoGeneracion
   - ✅ fTransmision
   - ✅ hTransmision

2. **En `emisor`:**
   - ✅ nit
   - ✅ nrc
   - ✅ nombre
   - ✅ nombreComercial
   - ✅ nombreResponsable
   - ✅ tipoDocResponsable
   - ✅ numeroDocResponsable
   - ✅ tipoEstablecimiento
   - ⚠️ codEstable (actualmente null)
   - ⚠️ codPuntoVenta (actualmente null)
   - ⚠️ codEstableMH (actualmente null)
   - ⚠️ codPuntoVentaMH (actualmente null)
   - ✅ telefono
   - ✅ correo
   - ✅ codActividad
   - ✅ descActividad
   - ✅ direccion.departamento
   - ✅ direccion.municipio
   - ✅ direccion.complemento

3. **En `detalleDTE`:**
   - ✅ fInicio
   - ✅ fFin
   - ✅ hInicio
   - ✅ hFin
   - ✅ tipoContingencia
   - ✅ motivoContingencia
   - ✅ noDte
   - ✅ detalleDte[] (array de DTEs)

---

## 🔍 Próximos Pasos

1. **Consultar la documentación oficial del MH** para eventos de contingencia
2. **Verificar ejemplos de JSON de eventos de contingencia** aceptados por el MH
3. **Si tienes acceso al ambiente de certificación**, probar con diferentes combinaciones de campos
4. **Contactar al soporte del MH** si el error persiste con el formato correcto

---

## 📚 Referencias

- Archivo de configuración: `config/dte.php`
- Servicio de contingencia: `app/Services/ContingenciaService.php`
- Servicio de API MH: `app/Services/MHApiService.php`
- Logs del sistema: `storage/logs/laravel.log`
