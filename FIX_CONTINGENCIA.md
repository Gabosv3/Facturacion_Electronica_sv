# 🔧 Correcciones Aplicadas al Sistema de Contingencia

## ❌ Problemas Encontrados

1. **Factura no se agregaba al evento de contingencia**:
   - La factura SÍ se marcaba en contingencia PERO el contador `cantidad_dte` quedaba en 0

2. **Error al enviar evento al MH**:
   - "Faltan datos para procesar informacion"
   - El JSON enviado no tenía todos los campos requeridos

## ✅ Correcciones Aplicadas

### 1. Corregido el conteo de DTEs al cerrar evento
**Archivo**: `app/Models/EventoContingencia.php`

**Problema**: El método `cerrar()` contaba las facturas DESPUÉS de actualizar el estado, resultando en 0.

**Solución**: Ahora cuenta las facturas ANTES de actualizar.

```php
public function cerrar(): void
{
    // Primero contar las facturas ANTES de actualizar
    $cantidadDte = $this->facturas()->count();

    $this->update([
        'fecha_hora_fin' => now(),
        'estado' => self::ESTADO_CERRADO,
        'cantidad_dte' => $cantidadDte,
    ]);
}
```

### 2. Agregados campos faltantes al JSON del evento
**Archivo**: `app/Services/ContingenciaService.php`

**Problema**: El JSON no incluía campos obligatorios como NRC, nombreComercial, codActividad, descActividad, y la dirección completa.

**Solución**: Ahora el JSON incluye TODOS los campos requeridos:

```php
'emisor' => [
    'nit' => config('dte.emisor.nit'),
    'nrc' => config('dte.emisor.nrc'),              // ✅ Agregado
    'nombre' => config('dte.emisor.nombre'),
    'nombreResponsable' => config('dte.emisor.nombre'),
    'tipoDocResponsable' => '36',
    'numeroDocResponsable' => config('dte.emisor.nit'),
    'tipoEstablecimiento' => '01',
    'codEstable' => config('dte.emisor.cod_estable'),
    'codPuntoVenta' => config('dte.emisor.cod_punto_venta'),
    'telefono' => config('dte.emisor.telefono'),
    'correo' => config('dte.emisor.correo'),
    'nombreComercial' => config('dte.emisor.nombre_comercial'),  // ✅ Agregado
    'codActividad' => config('dte.emisor.cod_actividad'),        // ✅ Agregado
    'descActividad' => config('dte.emisor.desc_actividad'),      // ✅ Agregado
    'direccion' => [                                              // ✅ Corregido
        'departamento' => $direccionEmisor['departamento'] ?? '06',
        'municipio' => $direccionEmisor['municipio'] ?? '23',
        'complemento' => $direccionEmisor['complemento'] ?? 'Direccion del establecimiento',
    ],
],
```

### 3. Corregido el número de item en el detalle
**Problema**: usaba el ID de la factura como `noItem`

**Solución**: Ahora usa número secuencial desde 1

```php
$detalleDte = $facturas->map(function ($factura, $index) {
    return [
        'noItem' => $index + 1, // ✅ Número secuencial desde 1
        'codigoGeneracion' => $factura->codigo_generacion,
        'tipoDoc' => $factura->tipo_dte,
    ];
})->values()->toArray();
```

### 4. Agregado logging detallado
**Archivo**: `app/Services/MHApiService.php`

**Solución**: Ahora se registra el JSON completo que se envía al MH para debugging:

```php
Log::info('📤 Enviando evento de contingencia al MH', [
    'url' => $contingenciaUrl,
    'codigo_evento' => $eventoJson['dteJson']['identificacion']['codigoGeneracion'],
    'json_completo' => json_encode($eventoJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), // ✅ Agregado
]);
```

---

## 🔄 Cómo Probar las Correcciones

### Paso 1: Limpiar el evento anterior (opcional)
```sql
-- Ver evento actual
SELECT * FROM eventos_contingencia WHERE id = 1;

-- Si quieres limpiarlo para empezar de nuevo
DELETE FROM facturas WHERE evento_contingencia_id = 1;
DELETE FROM eventos_contingencia WHERE id = 1;
```

### Paso 2: Crear nuevo evento de contingencia
1. Ve a: `http://localhost:8000/contingencias/crear`
2. Selecciona tipo: **3 - Falla en el servicio de internet**
3. Motivo: "Prueba del sistema de contingencia corregido"
4. Haz clic en **"Activar Contingencia"**

### Paso 3: Crear una factura en contingencia
1. Ve a: `http://localhost:8000/facturas/crear`
2. Llena el formulario con datos válidos
3. Guarda la factura
4. Verifica que aparezca el mensaje: **"⚠️ Factura generada en MODO CONTINGENCIA"**

### Paso 4: Verificar que la factura se agregó
```sql
-- Ver el evento y su cantidad de DTEs
SELECT id, codigo_evento, cantidad_dte, estado
FROM eventos_contingencia
ORDER BY id DESC LIMIT 1;

-- Ver facturas asociadas al evento
SELECT id, numero_control, estado, en_contingencia, evento_contingencia_id
FROM facturas
WHERE en_contingencia = 1
ORDER BY id DESC;
```

### Paso 5: Cerrar y procesar la contingencia
1. Ve a: `http://localhost:8000/contingencias`
2. Haz clic en **"Cerrar Contingencia"**
3. **VERIFICA**: El contador de DTEs debe mostrar 1 (o el número correcto)
4. Haz clic en **"Enviar al MH"**

### Paso 6: Revisar los logs
```bash
tail -f storage/logs/laravel.log
```

Deberías ver:
```
📤 Enviando evento de contingencia al MH
json_completo: {
  "nit": "...",
  "activo": true,
  "passwordPri": "...",
  "dteJson": {
    "identificacion": { ... },
    "emisor": {
      "nit": "...",
      "nrc": "...",          ← ✅ Debe estar presente
      "nombreComercial": "...", ← ✅ Debe estar presente
      "codActividad": "...",    ← ✅ Debe estar presente
      ...
    },
    "detalleDTE": {
      "noDte": 1,             ← ✅ Debe ser > 0
      "detalleDte": [
        {
          "noItem": 1,        ← ✅ Debe empezar en 1
          "codigoGeneracion": "...",
          "tipoDoc": "01"
        }
      ]
    }
  }
}
```

---

## 🎯 Resultado Esperado

### Si todo funciona correctamente:
1. ✅ La factura se marca en contingencia
2. ✅ Al cerrar el evento, `cantidad_dte` = 1 (o número correcto)
3. ✅ Al enviar al MH, el JSON incluye todos los campos
4. ✅ El MH acepta el evento (estado: RECIBIDO o PROCESADO)

### Si aún hay error:
Revisa los logs para ver el JSON completo enviado y compáralo con la especificación del MH.

```bash
# Ver el último JSON enviado
tail -100 storage/logs/laravel.log | grep "json_completo" -A 50
```

---

## 📝 Notas Importantes

1. **El evento anterior (ID 1) quedó con cantidad_dte = 0** porque se cerró con el bug. Puedes:
   - Eliminarlo manualmente de la BD
   - O ignorarlo y crear un nuevo evento

2. **El JSON ahora incluye TODOS los campos requeridos** según la especificación del MH

3. **El logging detallado** te permitirá ver exactamente qué se envía al MH

4. **Si el MH sigue rechazando**, compara el JSON en los logs con la especificación oficial del MH

---

## 🔍 Verificación Rápida

```sql
-- Ver eventos de contingencia
SELECT
    id,
    codigo_evento,
    tipo_contingencia,
    cantidad_dte,
    estado,
    fecha_hora_inicio,
    fecha_hora_fin
FROM eventos_contingencia
ORDER BY id DESC;

-- Ver facturas en contingencia
SELECT
    f.id,
    f.numero_control,
    f.estado,
    f.en_contingencia,
    f.evento_contingencia_id,
    e.codigo_evento
FROM facturas f
LEFT JOIN eventos_contingencia e ON f.evento_contingencia_id = e.id
WHERE f.en_contingencia = 1
ORDER BY f.id DESC;
```

---

¡Las correcciones están aplicadas! Ahora prueba creando un nuevo evento de contingencia desde cero. 🚀
