# Solución al Problema: NIT CONTRIBUYENTE NO EXISTE

## 🔴 Problema Identificado

El Ministerio de Hacienda valida que **todos los NITs existan en su base de datos real**, incluso en ambiente de pruebas (`ambiente: "00"`).

```json
{
  "estado": "RECHAZADO",
  "codigoMsg": "009",
  "descripcionMsg": "[receptor.nit] NIT CONTRIBUYENTE NO EXISTE"
}
```

## ✅ Soluciones Inmediatas

### Opción 1: Solicitar NITs de Prueba Oficiales al MH

**Contacta al soporte técnico del MH para obtener NITs válidos para pruebas:**

**Portal oficial**: https://factura.gob.sv/

**Pasos**:
1. Registrarte en el portal de facturación electrónica
2. Solicitar acceso al ambiente de pruebas
3. Pedir NITs de contribuyentes de prueba válidos
4. El MH generalmente proporciona estos datos después del registro

**Teléfono DGII**: 2221-2000
**Correo**: facturacionelectronica@mh.gob.sv

---

### Opción 2: Usar Empresas Conocidas (Solo Desarrollo Local)

⚠️ **ADVERTENCIA**: Usar datos reales de empresas sin autorización puede tener implicaciones legales. Esta opción es **solo para desarrollo local** y **nunca** para producción.

Algunas empresas grandes y conocidas de El Salvador tienen sus datos públicos disponibles. Puedes buscar en:
- Sitio web corporativo de la empresa
- Registro de comercio público
- Documentos públicos de la empresa

**Recomendación**: Contacta directamente a empresas con las que tengas relación comercial y pide autorización para usar sus datos en ambiente de pruebas.

---

### Opción 3: Crear una Empresa de Prueba Real

Si vas a dedicarte al desarrollo de sistemas de facturación, considera:

1. **Registrar una segunda empresa/sucursal propia en el MH**
   - Costo: Variable según el tipo de empresa
   - Beneficio: Tendrás un NIT real válido para pruebas controladas
   - Proceso: A través del Centro Nacional de Registros (CNR)

2. **Usar datos de tu propia empresa principal**
   - Si tienes una empresa, podrías emitir CCF a ti mismo
   - El MH podría rechazar esto, pero al menos verificarás que la estructura del DTE es correcta

---

### Opción 4: Probar con DTE-01 (Factura) en lugar de DTE-03 (CCF)

**Diferencia clave**:
- **DTE-01 (Factura)** permite usar DUI, pasaporte, y otros documentos (no solo NIT)
- **DTE-03 (CCF)** SOLO acepta NIT de contribuyentes IVA

**Ventaja**: Podrías usar un DUI válido (tuyo o de alguien conocido) para probar el flujo completo con DTE-01.

**Ejemplo para DTE-01**:
```php
Tipo Documento: 13 (DUI)
Número: 012345678 (9 dígitos, sin guión)
Nombre: JUAN PEREZ
NRC: null (no requerido para DTE-01 con DUI)
```

---

## 🛠️ Alternativa Temporal: Mock/Simulación

Si necesitas continuar con el desarrollo sin esperar NITs válidos, puedes:

### 1. Crear un modo "desarrollo" que omita la validación del MH

Agrega en tu `.env`:
```bash
DTE_MODO_DESARROLLO=true
```

Luego modifica `MHApiService.php` para simular respuestas exitosas en modo desarrollo:

```php
public function enviarDTE(string $dteFirmado, array $metadata): array
{
    // Modo desarrollo: simular éxito sin enviar al MH
    if (config('app.debug') && env('DTE_MODO_DESARROLLO', false)) {
        Log::info('🧪 MODO DESARROLLO: Simulando envío exitoso al MH');

        return [
            'success' => true,
            'message' => 'DTE procesado (modo desarrollo)',
            'selloRecibido' => 'SELLO-DESARROLLO-' . time(),
            'codigoGeneracion' => $metadata['codigo_generacion'] ?? 'TEST',
            'estado' => 'PROCESADO',
            'observaciones' => ['Procesado en modo desarrollo (sin validación MH)'],
            'intentos' => 1,
            'modo_desarrollo' => true,
        ];
    }

    // Continuar con lógica normal...
}
```

**⚠️ IMPORTANTE**: Este modo SOLO para desarrollo local. NUNCA en producción.

---

## 📊 Resumen de Opciones

| Opción | Tiempo | Costo | Validez Real |
|--------|--------|-------|--------------|
| Solicitar NITs al MH | 2-7 días | Gratis | ✅ 100% |
| Usar empresa conocida | Inmediato | Gratis | ✅ Funciona pero riesgoso |
| Crear empresa prueba | 1-2 semanas | $100-500 | ✅ 100% |
| Probar con DTE-01 | Inmediato | Gratis | ✅ Para ese tipo DTE |
| Modo desarrollo | Inmediato | Gratis | ❌ Solo simulación |

---

## 🎯 Recomendación Final

**Para continuar ahora mismo**:
1. Prueba con **DTE-01 usando un DUI válido** (el tuyo o de alguien conocido)
2. Verifica que todo el flujo funcione correctamente
3. En paralelo, **contacta al MH** para obtener NITs de prueba oficiales
4. Una vez obtengas los NITs, prueba DTE-03 (CCF)

**Comando para crear DTE-01**:
```bash
# Ir a tu navegador
http://localhost:8000/facturas/crear

# Seleccionar:
- Tipo DTE: 01 (Factura)
- Tipo Documento: 13 (DUI)
- Número: Tu DUI sin guión (9 dígitos)
```

---

## 📞 Contactos Útiles

- **Portal Facturación Electrónica**: https://factura.gob.sv/
- **Ministerio de Hacienda**: https://www.mh.gob.sv/
- **Teléfono DGII**: 2221-2000
- **Email**: facturacionelectronica@mh.gob.sv
- **Soporte técnico**: A través del portal factura.gob.sv después de registrarte

---

## ✅ Siguiente Paso Inmediato

Ejecuta este comando para probar que tu sistema funciona con DTE-01:

```bash
# Ver los logs en tiempo real
tail -f storage/logs/laravel.log
```

Luego crea una **Factura (DTE-01)** con tu propio DUI como receptor para verificar que todo funciona correctamente.
