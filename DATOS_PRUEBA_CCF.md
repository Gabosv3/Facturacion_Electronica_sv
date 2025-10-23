# Datos de Prueba para CCF (DTE-03)

## ⚠️ Importante

Para que un CCF sea aceptado por el Ministerio de Hacienda, el **NIT del receptor DEBE**:
1. Existir en la base de datos del MH
2. Estar registrado como contribuyente IVA
3. Ser DIFERENTE al NIT del emisor

## ⚠️ ACTUALIZACIÓN IMPORTANTE

**El MH valida que los NITs EXISTAN en su base de datos**, incluso en ambiente de pruebas (`ambiente: "00"`).

**ERROR al usar NITs ficticios:**
```json
{
  "estado": "RECHAZADO",
  "codigoMsg": "009",
  "descripcionMsg": "[receptor.nit] NIT CONTRIBUYENTE NO EXISTE"
}
```

## ✅ Soluciones Reales para Pruebas

### Opción A: Usar tu propia empresa como receptor (si tienes otra)
Si tienes acceso a los datos de otra empresa registrada en El Salvador, úsalos.

### Opción B: Contactar al soporte del MH
Solicita al Ministerio de Hacienda NITs de prueba válidos para ambiente de desarrollo:
- **Portal MH**: https://www.mh.gob.sv/
- **Soporte Técnico**: Generalmente tienen NITs específicos para pruebas en ambiente `00`

### Opción C: Usar tu mismo NIT de emisor como receptor (SOLO PARA PRUEBAS)
⚠️ **No recomendado para producción**, pero puedes probar temporalmente:
- **NIT Receptor**: Usar el mismo que tu emisor (`06142302881449`)
- **Problema**: El MH podría rechazarlo con "[receptor.nit] VALOR NO ES PERMITIDO"
- **Ventaja**: Al menos sabrás si el resto de la estructura está correcta

### Opción D: Buscar en el Registro Público de Comercio
1. Visita el portal de registro de comercio de El Salvador
2. Busca empresas públicas/conocidas
3. Obtén su NIT público

## 🧪 NITs que PODRÍAN funcionar (verificar primero)

**IMPORTANTE**: Estos son ejemplos genéricos. Debes verificar que existan en el portal del MH antes de usarlos.

### Ejemplo 1 - Formato típico
- **NIT**: `06140000000000` (sustituye los ceros por números reales)
- **NRC**: `000000-0` (sustituye por el NRC real)
- **Nota**: Este es solo un formato de ejemplo, NO usar tal cual

## 📝 Cómo Obtener NITs Reales del Portal MH

### Paso 1: Consulta en línea
```
1. Visita: https://www.mh.gob.sv/
2. Busca "Consulta de NIT" o "Verificación de Contribuyentes"
3. Puedes buscar por:
   - Nombre de empresa conocida
   - NIT (si lo tienes)
```

### Paso 2: Verificar que esté activo
El contribuyente debe estar:
- ✅ Registrado en IVA
- ✅ Activo (no suspendido)
- ✅ Con NRC válido

## 🔍 Cómo Obtener Datos Reales

### Opción 1: Portal del Ministerio de Hacienda
1. Visita: https://www.mh.gob.sv/
2. Busca "Consulta de Contribuyentes"
3. Ingresa un NIT para verificar que existe y está activo

### Opción 2: Usar datos de empresas conocidas
Puedes usar datos de empresas públicas grandes de El Salvador (solo para pruebas en ambiente de desarrollo):

**Ejemplo - Empresa conocida (SOLO PARA DESARROLLO):**
- **NIT**: `06140210961013` (Ejemplo genérico)
- **NRC**: `123456-7`
- **Nota**: Verifica que los datos sean correctos en el portal del MH

## ⚠️ Advertencia sobre el NIT del Emisor

Tu emisor configurado actualmente:
- **NIT Emisor**: `06142302881449`

**NO puedes usar este mismo NIT como receptor** en un CCF. El MH lo rechazará.

## 📝 Formato de Datos para Ingresar en el Formulario

Cuando crees un CCF, asegúrate de ingresar:

1. **Tipo de Documento**: `36` (NIT) - Es el único permitido para CCF
2. **Número de Documento**: 14 dígitos sin guiones (ej: `06141307841010`)
3. **NRC**: Formato con guión (ej: `12345-6`) o sin guión (ej: `123456`)
4. **Código de Actividad**: 5 dígitos (ej: `46510`)
5. **Descripción de Actividad**: Mínimo 5 caracteres

## 🧪 Recomendación para Pruebas

1. **Primero**: Intenta con `06141307841010` / NRC `12345-6`
2. **Si falla**: Revisa los logs en `storage/logs/laravel.log` para ver el error exacto
3. **Si dice "NIT no existe"**: Necesitarás obtener un NIT real del portal MH

## 📋 Checklist antes de Enviar CCF

- [ ] NIT del receptor tiene 14 dígitos
- [ ] NIT del receptor es DIFERENTE al del emisor
- [ ] NRC está presente y no vacío
- [ ] Código de actividad está presente (5 dígitos)
- [ ] Descripción de actividad tiene al menos 5 caracteres
- [ ] Departamento y municipio están seleccionados correctamente
- [ ] Distrito tiene 2 dígitos (campo manual)

## 🔧 Comando para Ver Logs en Tiempo Real

```bash
# Ver logs en tiempo real
tail -f storage/logs/laravel.log

# Ver solo errores del MH
tail -f storage/logs/laravel.log | grep "error_mh"

# Ver logs del día actual
cat storage/logs/laravel-$(date +%Y-%m-%d).log
```
