# 🔍 Búsqueda Automática de Cliente por DUI/NIT

## 📋 Descripción

Se ha integrado una funcionalidad que permite buscar automáticamente los datos de un cliente utilizando solo su **DUI (8 dígitos)** o **NIT (14 dígitos)**, consumiendo el endpoint externo de Motors González.

---

## 🔗 Endpoint Consumido

```
GET /clientes/buscar/{documento}
Base URL: https://motorsgonzalez.com/api
```

**URL Completa:**
```
https://motorsgonzalez.com/api/clientes/buscar/{documento}
```

**Parámetro:**
- `documento` (requerido): DUI o NIT del cliente (sin guiones)

---

## 🔄 Flujo de Funcionamiento

```
┌─────────────────────────────────────────────────────────┐
│         BÚSQUEDA AUTOMÁTICA DE CLIENTE                  │
├─────────────────────────────────────────────────────────┤
│                                                         │
│  1. Usuario ingresa DUI o NIT en el formulario         │
│         │                                               │
│         ▼                                               │
│  2. Presiona botón "🔍 Buscar"                          │
│         │                                               │
│         ▼                                               │
│  3. Se hace petición AJAX al servidor                  │
│         │                                               │
│         ▼                                               │
│  4. POST /facturas/buscar-cliente                      │
│     + Headers: X-CSRF-TOKEN, Content-Type: JSON        │
│     + Body: { documento: "06142302881449" }            │
│         │                                               │
│         ▼                                               │
│  5. FacturaController@buscarCliente                    │
│         │                                               │
│         ▼                                               │
│  6. GET https://motorsgonzalez.com/api/clientes/buscar/06142302881449
│         │                                               │
│         ▼                                               │
│  7. Recibir respuesta con datos del cliente            │
│         │                                               │
│  ┌──────┴────────────┐                                  │
│  │                   │                                  │
│  ▼                   ▼                                  │
│ ÉXITO            ERROR                                  │
│  │                   │                                  │
│  ▼                   ▼                                  │
│ Llenar campos   Mostrar error                           │
│ Mostrar ✓       Limpiar loader                          │
│                                                         │
└─────────────────────────────────────────────────────────┘
```

---

## 📱 Implementación en Vistas

### Formularios Actualizados

1. **Crear Factura (DTE-01)**: `resources/views/facturas/crear.blade.php`
2. **Crear CCF (DTE-03)**: `resources/views/facturas/crear_ccf.blade.php`

### Componentes Añadidos

#### HTML
```html
<div class="flex gap-2">
    <input type="text" id="receptor_num_documento" name="receptor_num_documento" 
           placeholder="DUI: 12345678-9 / NIT: 1234-567890-123-4" 
           class="flex-1 border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500" 
           required value="{{ old('receptor_num_documento') }}">
    <button type="button" id="btnBuscarCliente" 
            class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-4 py-2 rounded-lg transition">
        🔍 Buscar
    </button>
</div>

<!-- Loader -->
<div id="clienteLoader" class="hidden mt-2 flex items-center gap-2">
    <div class="animate-spin h-4 w-4 border-2 border-blue-600 border-r-transparent rounded-full"></div>
    <span class="text-sm text-blue-600">Buscando cliente...</span>
</div>

<!-- Error -->
<div id="clienteError" class="hidden mt-2 bg-red-100 border border-red-400 text-red-700 px-3 py-2 rounded text-sm"></div>

<!-- Éxito -->
<div id="clienteExito" class="hidden mt-2 bg-green-100 border border-green-400 text-green-700 px-3 py-2 rounded text-sm">
    ✓ Cliente encontrado y datos cargados
</div>
```

---

## 🛠️ Backend - Endpoint

### Ruta
```php
Route::post('/facturas/buscar-cliente', [FacturaController::class, 'buscarCliente'])->name('facturas.buscar_cliente');
```

**Ubicación:** `routes/web.php`

### Controlador
```php
public function buscarCliente(Request $request)
{
    $request->validate([
        'documento' => 'required|string|min:8|max:14',
    ]);

    try {
        $documento = preg_replace('/[^0-9]/', '', $request->documento);

        // Llamar al endpoint de Motors González
        $response = Http::timeout(10)->get('https://motorsgonzalez.com/api/clientes/buscar/' . $documento);

        if ($response->successful()) {
            $data = $response->json();

            return response()->json([
                'success' => true,
                'cliente' => $data['cliente'] ?? $data['data'] ?? $data
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Cliente no encontrado'
        ], 404);
    } catch (\Exception $e) {
        Log::error('Error al buscar cliente:', [
            'documento' => $request->documento,
            'error' => $e->getMessage()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Error al buscar cliente: ' . $e->getMessage()
        ], 500);
    }
}
```

**Ubicación:** `app/Http/Controllers/FacturaController.php`

---

## 📊 Respuesta API

### Respuesta Exitosa

```json
{
  "success": true,
  "cliente": {
    "id": 1,
    "codigo_cliente": "06142302881449",
    "nombre": "EMPRESA XYZ",
    "nrc": "123456-7",
    "telefono": "7777-7777",
    "email": "info@empresa.com",
    "codigo_actividad": "62010",
    "descripcion_actividad": "PROGRAMACIÓN INFORMÁTICA",
    "direccion": "Calle Principal 123, Apt 4",
    "municipio": "San Salvador",
    "departamento": "San Salvador"
  }
}
```

### Respuesta Error

```json
{
  "success": false,
  "message": "Cliente no encontrado"
}
```

---

## 🎯 Campos Mapeados

Los siguientes campos se llenan automáticamente:

| Campo Formulario | Campo API | Descripción |
|-----------------|-----------|-------------|
| `receptor_num_documento` | `codigo_cliente` | DUI/NIT del cliente |
| `receptor_nombre` | `nombre` | Nombre del cliente |
| `receptor_nrc` | `nrc` | NRC (si aplica) |
| `receptor_telefono` | `telefono` | Teléfono de contacto |
| `receptor_correo` | `email` | Email del cliente |
| `receptor_cod_actividad` | `codigo_actividad` | Código actividad económica |
| `receptor_desc_actividad` | `descripcion_actividad` | Descripción actividad |
| `receptor_direccion` | `direccion` | Dirección del cliente |
| `receptor_municipio` | `municipio` | Municipio |
| `receptor_departamento` | `departamento` | Departamento |

---

## 💻 JavaScript/AJAX

### Búsqueda Automática

```javascript
document.getElementById('btnBuscarCliente').addEventListener('click', async function() {
    const documento = document.getElementById('receptor_num_documento').value.trim();
    
    if (!documento) {
        alert('Por favor ingresa un DUI o NIT');
        return;
    }

    const loader = document.getElementById('clienteLoader');
    const error = document.getElementById('clienteError');
    const exito = document.getElementById('clienteExito');

    // Limpiar mensajes previos
    error.classList.add('hidden');
    exito.classList.add('hidden');
    loader.classList.remove('hidden');

    try {
        const response = await fetch('{{ route("facturas.buscar_cliente") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ documento })
        });

        const data = await response.json();
        loader.classList.add('hidden');

        if (data.success && data.cliente) {
            const cliente = data.cliente;
            
            // Llenar los campos con los datos del cliente
            // ... (mapeo de campos)

            exito.classList.remove('hidden');
        } else {
            error.classList.remove('hidden');
            error.textContent = data.message || 'No se encontró cliente con ese documento';
        }
    } catch (e) {
        loader.classList.add('hidden');
        error.classList.remove('hidden');
        error.textContent = 'Error al buscar cliente: ' + e.message;
        console.error('Error:', e);
    }
});
```

### Búsqueda con Enter

```javascript
document.getElementById('receptor_num_documento').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        document.getElementById('btnBuscarCliente').click();
    }
});
```

---

## ✅ Casos de Uso

### Caso 1: Búsqueda Exitosa

```
1. Usuario ingresa: 06142302881449
2. Presiona botón "🔍 Buscar"
3. Se muestra "Buscando cliente..."
4. API devuelve datos
5. Se cargan todos los campos automáticamente
6. Se muestra mensaje: "✓ Cliente encontrado y datos cargados"
```

### Caso 2: Cliente No Encontrado

```
1. Usuario ingresa: 12341234123412
2. Presiona botón "🔍 Buscar"
3. Se muestra "Buscando cliente..."
4. API devuelve 404
5. Se muestra error: "Cliente no encontrado"
6. Los campos permanecen vacíos
```

### Caso 3: Error de Conexión

```
1. Usuario ingresa un DUI/NIT
2. Presiona botón "🔍 Buscar"
3. Se muestra "Buscando cliente..."
4. Hay error en la conexión
5. Se muestra error: "Error al buscar cliente: ..."
```

---

## 🔐 Seguridad

- ✅ Validación de datos en servidor
- ✅ CSRF Token incluido en petición AJAX
- ✅ Timeout de 10 segundos en HTTP request
- ✅ Manejo de excepciones
- ✅ Log de errores para debugging

---

## 📝 Validaciones

### Cliente
- DUI/NIT no vacío
- Mínimo 8 dígitos
- Máximo 14 dígitos
- Solo números (los guiones se limpian automáticamente)

### Servidor
- Documento requerido
- Longitud entre 8 y 14 caracteres
- Limpieza de caracteres especiales
- Timeout: 10 segundos

---

## 🐛 Debugging

### Ver en Consola
```javascript
// Abrir DevTools (F12) → Console
// Se pueden ver logs de:
- Request enviado
- Response recibido
- Errores de conexión
```

### Ver en Backend
```
storage/logs/laravel.log
```

---

## 📱 Compatibilidad

- ✅ Navegadores modernos (Chrome, Firefox, Safari, Edge)
- ✅ Mobile responsive
- ✅ AJAX sin recargar página
- ✅ Funciona con y sin JavaScript (con submit normal)

---

## 🚀 Próximas Mejoras

- [ ] Auto-complete al escribir
- [ ] Búsqueda por nombre de cliente
- [ ] Caché de clientes frecuentes
- [ ] Búsqueda avanzada
- [ ] Historial de búsquedas

---

**Funcionalidad Implementada** ✅
**Fecha:** 4 de Diciembre de 2025
