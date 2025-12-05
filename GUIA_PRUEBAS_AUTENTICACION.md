# 🧪 Guía de Prueba - Sistema de Autenticación

## 📋 Requisitos Previos

- Servidor Laravel corriendo en `http://localhost:8000` (o tu URL)
- Credenciales válidas de Motors González
- Postman o navegador web
- Base de datos configurada (aunque sea con sesiones en archivo)

---

## 🎯 Pruebas Step by Step

### ✅ Test 1: Acceder a la página de Login

**Paso 1:** Abre tu navegador y accede a:
```
http://localhost:8000/login
```

**Resultado esperado:**
- ✓ Ves un formulario de login con dos campos (email y password)
- ✓ Diseño con gradiente púrpura/azul
- ✓ Botón "Iniciar Sesión"
- ✓ Mensaje informativo sobre Motors González

**Si no funciona:**
```
- Verifica que Laravel esté corriendo
- Revisa que los archivos de vista existan:
  resources/views/auth/login.blade.php
  resources/views/layouts/app.blade.php
```

---

### ✅ Test 2: Intentar login sin credenciales

**Paso 1:** Deja los campos vacíos y presiona "Iniciar Sesión"

**Resultado esperado:**
- ✓ Aparecen mensajes de error:
  - "El email es requerido"
  - "La contraseña es requerida"
- ✓ Los campos se resaltan en rojo

**Si no funciona:**
```
- Verifica la validación en LoginController::login()
- Revisa que el formulario tenga @csrf
```

---

### ✅ Test 3: Login con credenciales inválidas

**Paso 1:** Ingresa:
```
Email: usuario.incorrecto@example.com
Password: password123
```

**Paso 2:** Presiona "Iniciar Sesión"

**Resultado esperado:**
- ✓ Aparece mensaje: "Email o contraseña incorrectos"
- ✓ La página vuelve a /login
- ✓ Los datos se mantienen en los campos (old input)

**Si no funciona:**
```
- Verifica que AuthService esté consumiendo correctamente
- Revisa los logs en storage/logs/laravel.log
- Confirma que el endpoint de Motors González es correcto:
  https://motorsgonzalez.com/api/login
```

---

### ✅ Test 4: Login exitoso

**Paso 1:** Ingresa credenciales válidas de Motors González:
```
Email: gabriel@example.com
Password: password123
```

**Paso 2:** Presiona "Iniciar Sesión"

**Resultado esperado:**
- ✓ Se muestra un spinner indicando "Iniciando sesión..."
- ✓ Se redirige a /facturas
- ✓ Aparece mensaje de éxito: "Sesión iniciada correctamente"
- ✓ Se puede ver el navbar con las opciones de navegación

**Si no funciona:**
```
- Verifica las credenciales con Motors González
- Revisa los logs de laravel.log
- Asegúrate de que el token se guarde en sesión:
  dd(Session::all()); en el LoginController
```

---

### ✅ Test 5: Ver Perfil de Usuario

**Paso 1:** Haz clic en el nombre del usuario en el navbar (arriba derecha)

**Resultado esperado:**
- ✓ Aparece un menú desplegable con opciones:
  - 👤 Mi Perfil
  - ⚙️ Configuración
  - 🚪 Cerrar Sesión

**Paso 2:** Haz clic en "Mi Perfil"

**Resultado esperado:**
- ✓ Se accede a `/profile`
- ✓ Se muestra la información del usuario:
  - Nombre completo
  - Correo electrónico
  - Teléfono (si existe)
  - ID de usuario
  - Fecha de creación
  - Rol/Roles
- ✓ Aparece botón "✏️ Editar Perfil"
- ✓ Aparecen botones "← Volver a Facturas" y "🚪 Cerrar Sesión"

**Si no funciona:**
```
- Verifica que la sesión contenga user_data:
  dd(Session::get('user_data'));
- Revisa que el archivo exista:
  resources/views/auth/profile/show.blade.php
```

---

### ✅ Test 6: Editar Perfil

**Paso 1:** En la página del perfil, haz clic en "✏️ Editar Perfil"

**Resultado esperado:**
- ✓ Se accede a `/profile/editar`
- ✓ Se muestra un formulario con campos:
  - Nombre Completo (requerido)
  - Correo Electrónico (requerido)
  - Teléfono (opcional)
- ✓ Los campos están pre-llenados con los datos actuales

**Paso 2:** Modifica el nombre:
```
Nombre actual: Gabriel
Nuevo nombre: Gabriel Actualizado
```

**Paso 3:** Presiona "✓ Guardar Cambios"

**Resultado esperado:**
- ✓ Se redirige a `/profile`
- ✓ Aparece mensaje: "Perfil actualizado correctamente"
- ✓ El nombre se actualiza en la visualización
- ✓ El nombre también se actualiza en el navbar

**Si no funciona:**
```
- Verifica la validación en LoginController::updateProfile()
- Revisa que la sesión se actualice correctamente
- Comprueba que no haya errores de validación
```

---

### ✅ Test 7: Protección de Rutas

**Paso 1:** Abre una nueva pestaña en privado (incógnito)

**Paso 2:** Intenta acceder directamente a:
```
http://localhost:8000/facturas
```

**Resultado esperado:**
- ✓ Se redirige automáticamente a `/login`
- ✓ Aparece mensaje: "Debes iniciar sesión para acceder"

**Paso 3:** Intenta acceder a otras rutas protegidas:
- http://localhost:8000/invalidaciones
- http://localhost:8000/consultas
- http://localhost:8000/contingencias
- http://localhost:8000/admin/tokens

**Resultado esperado:**
- ✓ Todas redirigen a `/login`
- ✓ Se ve el middleware funcionando

**Si no funciona:**
```
- Verifica que el middleware CheckAuthentication esté registrado:
  app/Http/Kernel.php → 'auth.check'
- Revisa que las rutas usen el middleware:
  Route::middleware(['auth.check'])->group(...)
```

---

### ✅ Test 8: Cerrar Sesión

**Método 1: Desde el Navbar**

**Paso 1:** Haz clic en el nombre del usuario (arriba derecha)

**Paso 2:** Selecciona "🚪 Cerrar Sesión"

**Resultado esperado:**
- ✓ Se redirige a `/login`
- ✓ Aparece mensaje: "Sesión cerrada correctamente"
- ✓ La sesión se limpia completamente

**Método 2: Desde el Perfil**

**Paso 1:** En la página del perfil, haz clic en "🚪 Cerrar Sesión"

**Resultado esperado:**
- ✓ Mismo resultado que el Método 1

**Si no funciona:**
```
- Verifica que la ruta POST /logout existe:
  Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
- Revisa que Session::flush() se ejecute
- Comprueba que no haya errores CSRF
```

---

### ✅ Test 9: Session Timeout (Opcional)

**Paso 1:** Inicia sesión correctamente

**Paso 2:** Espera más del tiempo configurado (por defecto 120 minutos en Laravel)

**Paso 3:** Intenta acceder a cualquier página protegida

**Resultado esperado:**
- ✓ Eres redirigido a `/login`
- ✓ Se requiere iniciar sesión de nuevo

---

### ✅ Test 10: Validación de Formularios

**Test 10a: Email Inválido**

**Paso 1:** En la página de login, ingresa:
```
Email: email_sin_arroba
Password: password123
```

**Resultado esperado:**
- ✓ Aparece error: "El email debe ser válido"

**Test 10b: Contraseña Muy Corta**

**Paso 1:** Ingresa:
```
Email: usuario@example.com
Password: 123
```

**Resultado esperado:**
- ✓ Aparece error: "La contraseña debe tener al menos 6 caracteres"

---

## 🔍 Testing en Postman

### 1. Test Login API Externo

```
POST https://motorsgonzalez.com/api/login
Content-Type: application/json

{
  "email": "gabriel@example.com",
  "password": "password123"
}
```

**Respuesta esperada:**
```json
200 OK
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

### 2. Test Get User

```
GET https://motorsgonzalez.com/api/user
Authorization: Bearer {token_del_paso_anterior}
```

### 3. Test Logout API Externo

```
POST https://motorsgonzalez.com/api/logout
Authorization: Bearer {token}
```

---

## 🐛 Debugging

### Habilitar Modo Debug

En `config/app.php`:
```php
'debug' => env('APP_DEBUG', true),
```

### Ver Logs

```bash
tail -f storage/logs/laravel.log
```

### Verificar Sesión

En LoginController, agrega temporalmente:
```php
dd(Session::all());
```

### Validar Token

En AuthService, agrega:
```php
Log::info('Token recibido:', ['token' => $response['token']]);
```

---

## ✅ Checklist de Verificación Final

- [ ] Login sin credenciales muestra errores
- [ ] Login con credenciales inválidas muestra "Email o contraseña incorrectos"
- [ ] Login exitoso redirige a /facturas
- [ ] Token se guarda en sesión
- [ ] Datos del usuario se muestran en navbar
- [ ] Rutas protegidas requieren autenticación
- [ ] Perfil muestra datos correctamente
- [ ] Editar perfil funciona
- [ ] Logout limpia la sesión
- [ ] Navbar es responsive
- [ ] Menú dropdown funciona en hover
- [ ] Validación de formularios funciona
- [ ] Mensajes de éxito/error aparecen

---

## 📞 Soporte

Si algo no funciona:

1. **Revisa los logs:**
   ```
   storage/logs/laravel.log
   ```

2. **Verifica la configuración:**
   ```
   config/app.php
   config/session.php
   ```

3. **Limpia caché:**
   ```bash
   php artisan cache:clear
   php artisan config:clear
   ```

4. **Reinicia sesión:**
   ```bash
   php artisan tinker
   >>> Session::flush()
   ```

---

**Documentación de Prueba Completada** ✅
**Fecha:** 4 de Diciembre de 2025
