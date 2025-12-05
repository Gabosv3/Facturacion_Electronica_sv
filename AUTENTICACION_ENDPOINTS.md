# 📚 Documentación de Endpoints - Sistema de Autenticación

## 🔐 Endpoints de Autenticación

### 1. **Login**
- **Método:** `POST`
- **Ruta:** `/login`
- **Descripción:** Inicia sesión con credenciales de Motors González
- **Consumo:** Endpoint externo: `https://motorsgonzalez.com/api/login`
- **Parámetros:**
  ```json
  {
    "email": "usuario@example.com",
    "password": "password123"
  }
  ```
- **Respuesta (Éxito):**
  ```json
  {
    "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
    "user": {
      "id": 1,
      "name": "Gabriel",
      "email": "gabriel@example.com",
      "created_at": "2025-12-04T21:59:43.000000Z",
      "updated_at": "2025-12-04T21:59:43.000000Z"
    }
  }
  ```
- **Respuesta (Error):**
  ```json
  {
    "status": 401,
    "message": "Email o contraseña incorrectos"
  }
  ```

---

### 2. **Logout**
- **Método:** `POST`
- **Ruta:** `/logout`
- **Descripción:** Cierra la sesión actual del usuario
- **Autenticación:** Requerida (middleware: `auth.check`)
- **Parámetros:** Ninguno
- **Respuesta:** Redirige a `/login` con mensaje de éxito

---

## 👤 Endpoints de Perfil

### 3. **Ver Perfil** (NUEVO)
- **Método:** `GET`
- **Ruta:** `/profile`
- **Nombre de ruta:** `profile.show`
- **Descripción:** Muestra la información del perfil del usuario autenticado
- **Autenticación:** Requerida (middleware: `auth.check`)
- **Parámetros:** Ninguno
- **Respuesta:** Vista HTML con los datos del usuario desde la sesión
- **Datos mostrados:**
  - Nombre completo
  - Correo electrónico
  - Teléfono (si existe)
  - ID de usuario
  - Fecha de creación de cuenta
  - Rol/Roles
  - Última actualización

---

### 4. **Editar Perfil** (NUEVO)
- **Método:** `GET`
- **Ruta:** `/profile/editar`
- **Nombre de ruta:** `profile.edit`
- **Descripción:** Muestra el formulario para editar el perfil
- **Autenticación:** Requerida (middleware: `auth.check`)
- **Parámetros:** Ninguno
- **Respuesta:** Formulario HTML con campos editables

---

### 5. **Actualizar Perfil** (NUEVO)
- **Método:** `POST`
- **Ruta:** `/profile/actualizar`
- **Nombre de ruta:** `profile.update`
- **Descripción:** Actualiza los datos del perfil del usuario
- **Autenticación:** Requerida (middleware: `auth.check`)
- **Parámetros:**
  ```json
  {
    "name": "Nuevo Nombre",
    "email": "newemail@example.com",
    "phone": "+503 XXXX-XXXX" (opcional)
  }
  ```
- **Validación:**
  - `name`: Requerido, máximo 255 caracteres
  - `email`: Requerido, formato de email válido, máximo 255 caracteres
  - `phone`: Opcional, máximo 20 caracteres
- **Respuesta (Éxito):** Redirige a `/profile` con mensaje de éxito
- **Respuesta (Error):** Vuelve atrás con errores de validación

---

## 🔐 Autenticación y Seguridad

### Sistema de Tokens
- Los tokens se almacenan en la **sesión del usuario**
- Cada solicitud protegida valida la presencia del token en sesión
- El middleware `auth.check` verifica la autenticación antes de acceder a rutas protegidas

### Flujo de Autenticación
```
1. Usuario ingresa credenciales en /login
2. Sistema envía solicitud a: https://motorsgonzalez.com/api/login
3. Si es exitoso, se recibe el token y datos del usuario
4. Token se almacena en Session::put('auth_token', ...)
5. Datos del usuario se almacenan en Session::put('user_data', ...)
6. Usuario es redirigido a /facturas (dashboard)
```

### Logout
```
1. Usuario hace clic en "Cerrar Sesión"
2. Se ejecuta POST /logout
3. Session::flush() limpia toda la sesión
4. Usuario es redirigido a /login
```

---

## 📋 Rutas Protegidas (Requieren Autenticación)

Todas las siguientes rutas requieren que el usuario esté autenticado:

### Facturas
- `GET /facturas` - Listar facturas
- `GET /facturas/crear` - Crear factura
- `GET /facturas/crear-ccf` - Crear CCF
- `GET /facturas/crear-nota-credito` - Crear nota de crédito
- `POST /facturas/guardar` - Guardar factura
- `GET /facturas/{id}` - Ver factura
- `DELETE /facturas/{id}` - Eliminar factura

### Consultas
- `GET /consultas` - Listar consultas
- `POST /consultas/dte` - Consultar DTE
- `POST /consultas/buscar` - Buscar y consultar
- `POST /consultas/lote` - Consultar lote

### Invalidaciones
- `GET /invalidaciones` - Listar invalidaciones
- `GET /invalidaciones/crear` - Crear invalidación
- `POST /invalidaciones/guardar` - Guardar invalidación

### Contingencias
- `GET /contingencias` - Listar contingencias
- `GET /contingencias/crear` - Crear contingencia
- `POST /contingencias/guardar` - Guardar contingencia

### Administración
- `GET /admin/tokens` - Gestionar tokens MH
- `POST /admin/tokens/renovar` - Renovar token

---

## 🔑 Middleware de Autenticación

### CheckAuthentication
- **Ubicación:** `app/Http/Middleware/CheckAuthentication.php`
- **Alias:** `auth.check`
- **Función:** Verifica que el usuario tenga un token válido en sesión
- **Acción si no autenticado:** Redirige a `/login` con mensaje de advertencia

### Uso en rutas
```php
Route::middleware(['auth.check'])->group(function () {
    // Rutas protegidas aquí
});
```

---

## 🛠️ Servicios

### AuthService
- **Ubicación:** `app/Services/AuthService.php`
- **Métodos:**
  - `authenticate($email, $password)` - Autentica contra servidor externo
  - `validateToken($token)` - Valida un token
  - `refreshToken($token)` - Refresca un token
  - `getUserData($token)` - Obtiene datos del usuario

---

## 📱 Vista de Login

- **Ruta:** `/login`
- **Template:** `resources/views/auth/login.blade.php`
- **Características:**
  - Formulario responsive
  - Validación en cliente y servidor
  - Mensajes de error personalizados
  - Diseño moderno con gradientes
  - Indica cambio de estado (spinner) al enviar

---

## 📱 Vista de Perfil

- **Ruta:** `/profile`
- **Template:** `resources/views/auth/profile/show.blade.php`
- **Características:**
  - Muestra toda la información del usuario
  - Botón para editar perfil
  - Opción para cerrar sesión

---

## ✏️ Vista de Editar Perfil

- **Ruta:** `/profile/editar`
- **Template:** `resources/views/auth/profile/edit.blade.php`
- **Características:**
  - Formulario para actualizar datos
  - Validación de campos
  - Campos opcionales y requeridos
  - Nota sobre sincronización con Motors González

---

## 🧭 Navegación

### Navbar (Componente)
- **Ubicación:** `resources/views/components/navbar.blade.php`
- **Características:**
  - Menú de navegación principal
  - Botones para crear nuevos documentos
  - Dropdown de usuario con opciones de perfil y logout
  - Responsive design

---

## 🎨 Layout Principal

- **Ubicación:** `resources/views/layouts/app.blade.php`
- **Características:**
  - Navbar pegajoso (sticky)
  - Alertas automáticas
  - Footer
  - Estructura base para todas las páginas

---

## ⚠️ Notas Importantes

1. **Tokens en Sesión:** Los tokens se almacenan en la sesión del servidor, no en cookies
2. **Expiración:** La sesión expira según la configuración de Laravel (por defecto 120 minutos)
3. **Cierre de Sesión:** Al cerrar sesión, toda la información se limpia
4. **Datos de Usuario:** Se almacenan desde la respuesta del API de Motors González
5. **Sincronización:** Algunos campos del perfil pueden estar vinculados al sistema externo

---

## 🐛 Códigos de Error Comunes

| Código | Descripción |
|--------|-------------|
| 401 | No autenticado - Credenciales inválidas |
| 422 | Validación fallida - Parámetros incorrectos |
| 500 | Error del servidor de autenticación |

---

**Última actualización:** 4 de Diciembre de 2025
**Versión:** 1.0.0
