# Sistema de Facturación Electrónica - El Salvador

Sistema web desarrollado en Laravel 10 para la generación, firma y envío de Documentos Tributarios Electrónicos (DTE) al Ministerio de Hacienda de El Salvador.

![Laravel](https://img.shields.io/badge/Laravel-10-red.svg)
![PHP](https://img.shields.io/badge/PHP-8.1+-blue.svg)
![License](https://img.shields.io/badge/License-MIT-green.svg)

## 📋 Descripción del Proyecto

Sistema completo de facturación electrónica que permite a empresas y negocios en El Salvador cumplir con las regulaciones del Ministerio de Hacienda (MH) para la emisión de documentos tributarios electrónicos. El sistema maneja todo el ciclo de vida de los documentos: generación, firma digital, envío al MH, seguimiento de estado, modo de contingencia, invalidación y consultas.

## 🎯 Tipos de DTE Soportados

| Código | Tipo de Documento | Versión JSON | IVA | Descripción |
|--------|-------------------|--------------|-----|-------------|
| **DTE-01** | Factura (Consumidor Final) | 1 | 11.5% | Ventas generales al público |
| **DTE-03** | Comprobante de Crédito Fiscal (CCF) | 3 | 13% | Ventas a contribuyentes IVA (deducible como crédito fiscal) |
| **DTE-05** | Nota de Crédito | 3 | 13% | Cancelar/ajustar facturas anteriores (DTE-01 o DTE-03) |
| **DTE-14** | Factura de Sujeto Excluido | 1 | 0% | Ventas a entidades exentas de IVA (diplomáticos, ONGs) |

## ✨ Características Principales

### 🧾 Gestión de Documentos Tributarios
- ✅ Generación de facturas con estructura JSON según especificaciones del MH
- ✅ Numeración automática con control de correlativos por tipo de DTE
- ✅ Cálculo automático de IVA según tipo de documento
- ✅ Conversión de montos numéricos a letras en español
- ✅ Validación de datos según catálogos oficiales del MH (CAT-12, CAT-13, CAT-022, CAT-024, CAT-025, CAT-026)
- ✅ **Facturas de "Clientes Varios"** - DTE-01 sin datos específicos del cliente
- ✅ Soporte para múltiples tipos de documentos (NIT, DUI, Pasaporte, etc.)

### 🔐 Firma Digital y Envío al MH
- ✅ Integración con API local de firma digital
- ✅ Generación de JWT firmados con certificados digitales
- ✅ Autenticación automática con API del MH (tokens con renovación automática cada 24h)
- ✅ **Política de reintentos oficial del MH**:
  - Timeout de 8 segundos por intento
  - Hasta 2 reintentos (3 intentos totales)
  - Consulta de estado automática entre reintentos
  - Registro de todos los intentos en base de datos

### 📊 Sistema de Consultas
- ✅ Consulta individual de DTE por código de generación
- ✅ Consulta de lotes completos por código de lote
- ✅ Búsqueda rápida por número de control o código de generación
- ✅ Actualización automática del estado local según respuesta del MH
- ✅ Interfaz web para consultas manuales

### 🚨 Modo de Contingencia
- ✅ Soporte completo para eventos de fuerza mayor (CAT-024):
  - Fallas de energía eléctrica
  - Fallas del sistema de emisión
  - Fallas del servicio de internet
  - Fallas de servicios del MH
  - Otros eventos de fuerza mayor
- ✅ Firma local de documentos sin enviar al MH
- ✅ Procesamiento por lotes de hasta 100 DTEs
- ✅ Envío diferido cuando se restablece la conexión
- ✅ **Actualización automática de sellos** cuando el lote es procesado
- ✅ Disponible 24/7/365 (sin restricciones de horario)

### ❌ Sistema de Invalidación
- ✅ Invalidación de DTEs previamente enviados al MH
- ✅ Dos tipos de invalidación según CAT-026:
  - **Tipo 1**: Errores formales (requiere documento de reemplazo)
  - **Tipo 2**: Rescisión de operación (sin reemplazo)
- ✅ Validación de plazos según tipo de DTE:
  - DTE-03, DTE-05: Máximo 1 día
  - DTE-01, DTE-14: Máximo 3 meses
- ✅ Gestión de documentos de reemplazo
- ✅ Registro de responsables y solicitantes

### 📧 Envío por Correo Electrónico
- ✅ Envío automático de facturas al correo del receptor
- ✅ Adjunta JSON del DTE y HTML/PDF de la factura
- ✅ **Protección de credenciales sensibles** (nit, activo, passwordPri eliminados del JSON adjunto)
- ✅ Plantilla HTML profesional con diseño responsive
- ✅ Incluye QR code para consulta pública

### 📱 Códigos QR
- ✅ Generación automática de códigos QR según especificaciones del MH
- ✅ Enlace directo al portal de consulta pública del MH
- ✅ QR incluye: ambiente, código de generación y fecha de emisión
- ✅ Optimizado para impresión en facturas
- ✅ Compatible con todos los lectores QR estándar

## 🏗️ Arquitectura del Sistema

### Servicios Principales

**DTEService** (`app/Services/DTEService.php`)
- Genera códigos únicos de documentos (UUID v4 para codigoGeneracion)
- Genera números de control: `DTE-{tipo}-{establecimiento}{puntoVenta}-{correlativo}`
- Calcula totales e IVA según tipo de documento y configuración
- Construye estructura JSON completa según especificaciones del MH
- Maneja "Clientes Varios" con estructura especial (todos los campos NULL)

**MHApiService** (`app/Services/MHApiService.php`)
- Gestiona autenticación con tokens auto-renovables (23h 50min de vida)
- Implementa política de reintentos oficial del MH
- Envía documentos firmados a endpoints del MH
- Consulta estado de DTEs individuales y lotes
- Envía eventos de contingencia e invalidación

**ContingenciaService** (`app/Services/ContingenciaService.php`)
- Gestiona eventos de contingencia por fuerza mayor
- Construye lotes de DTEs para envío diferido
- Procesa envíos cuando se restablece la conexión
- Actualiza facturas con sellos de recepción automáticamente

**InvalidacionService** (`app/Services/InvalidacionService.php`)
- Crea eventos de invalidación según tipo
- Valida plazos y requisitos según tipo de DTE
- Firma eventos de invalidación
- Envía al MH y actualiza estado de facturas

### Modelos Principales

| Modelo | Descripción |
|--------|-------------|
| **Factura** | Cabecera de facturas con datos del DTE, estados y respuestas del MH |
| **FacturaItem** | Ítems de línea con precios, cantidades e IVA |
| **TokenMH** | Tokens de autenticación del MH con validación de expiración |
| **EventoContingencia** | Eventos de fuerza mayor con lotes de DTEs asociados |
| **EventoInvalidacion** | Eventos de invalidación con referencias a DTEs originales y de reemplazo |

### Flujo de Procesamiento Normal

```
Usuario crea factura en interfaz web
    ↓
DTEService genera JSON del DTE según especificaciones MH
    ↓
Se guarda en base de datos (estado: pendiente)
    ↓
Se envía a API de firma local para firma digital
    ↓
Se recibe documento firmado (JWT)
    ↓
Actualiza estado a 'firmado'
    ↓
MHApiService envía al MH con política de reintentos
    ↓
MH valida y devuelve sello de recepción
    ↓
Se actualiza estado a 'enviado_mh' y se guarda respuesta
    ↓
Se genera código QR automáticamente
    ↓
Disponible para envío por correo o descarga
```

### Flujo de Contingencia

```
Evento de fuerza mayor detectado/creado
    ↓
Sistema marca evento como 'activo'
    ↓
Nuevas facturas se firman localmente (no se envían al MH)
    ↓
Se marcan como 'firmado_contingencia' y se asocian al evento
    ↓
Se acumulan hasta 100 DTEs por lote
    ↓
Cuando se restablece la conexión:
    ↓
Se cierra el evento de contingencia
    ↓
Se envía evento de contingencia al MH (/fesv/contingencia)
    ↓
Se envía lote con todas las facturas pendientes (/fesv/recepcionlote)
    ↓
MH procesa el lote (2-3 minutos para 100 DTEs)
    ↓
Se consulta estado del lote (/fesv/consultarlote)
    ↓
Sistema actualiza automáticamente todas las facturas con sus sellos
    ↓
Estado cambia de 'firmado_contingencia' a 'enviado_mh'
```

## 🚀 Instalación

### Requisitos Previos

- PHP 8.1 o superior
- Composer
- MySQL 5.7+ o MariaDB 10.3+
- API de firma digital local configurada
- Certificado digital válido emitido por el MH

### Pasos de Instalación

1. **Clonar el repositorio**
```bash
git clone <repository-url>
cd factura-electronica
```

2. **Instalar dependencias**
```bash
composer install
```

3. **Configurar variables de entorno**
```bash
cp .env.example .env
php artisan key:generate
```

4. **Configurar base de datos**

Editar `.env` con tus credenciales de base de datos:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=factura_electronica
DB_USERNAME=root
DB_PASSWORD=
```

5. **Configurar credenciales del MH y firma**

Agregar al `.env`:
```env
# DTE Configuration
DTE_NIT=06142302881449
DTE_PASSWORD_PRI=tu_password_certificado
DTE_AMBIENTE=00  # 00=pruebas, 01=producción
DTE_API_URL=http://127.0.0.1:8113  # URL de tu API de firma local

# MH API Configuration
MH_AUTH_URL=https://apitest.dtes.mh.gob.sv/seguridad/auth
MH_RECEPCION_URL=https://apitest.dtes.mh.gob.sv/fesv/recepciondte
MH_CONSULTA_URL=https://apitest.dtes.mh.gob.sv/fesv/recepcion/consultadte
MH_USER=06142302881449
MH_PASSWORD=tu_password_mh

# Configuración del Emisor
DTE_EMISOR_NIT=06142302881449
DTE_EMISOR_NRC=3391583
DTE_EMISOR_NOMBRE=Tu Empresa SA de CV
DTE_EMISOR_NOMBRE_COMERCIAL=Tu Empresa
DTE_EMISOR_COD_ACTIVIDAD=62010
DTE_EMISOR_DESC_ACTIVIDAD=TU ACTIVIDAD ECONOMICA
```

6. **Ejecutar migraciones**
```bash
php artisan migrate
```

7. **Iniciar servidor de desarrollo**
```bash
php artisan serve
```

El sistema estará disponible en `http://localhost:8000`

## 📖 Uso del Sistema

### Crear Factura DTE-01 (Consumidor Final)

1. Ir a `/facturas/crear`
2. Llenar datos del cliente (o activar checkbox "Clientes Varios")
3. Agregar productos/servicios
4. Seleccionar condiciones de pago
5. Click en "Generar y Firmar Factura"

### Crear CCF DTE-03

1. Ir a `/facturas/crear-ccf`
2. Ingresar datos del receptor (solo NIT válido)
3. Campos obligatorios: NRC y código de actividad económica
4. Agregar productos/servicios
5. El sistema valida que el NIT existe en base del MH

### Crear Nota de Crédito DTE-05

1. Ir a `/facturas/crear-nota-credito`
2. Seleccionar factura a cancelar/ajustar (solo facturas con NIT)
3. Elegir tipo: Anulación, Ajuste de Precio o Devolución
4. Agregar productos (puede copiar de la factura original)
5. Sistema aplica IVA del 13% automáticamente

### Crear Factura de Sujeto Excluido DTE-14

1. Ir a `/facturas/crear-sujeto-excluido`
2. Ingresar datos del sujeto excluido
3. Agregar productos/servicios
4. Sistema aplica IVA 0% automáticamente

### Facturas de "Clientes Varios"

Para ventas rápidas sin datos específicos del cliente:

1. Ir a `/facturas/crear`
2. ✅ **Activar checkbox "Factura de Clientes Varios"**
3. Las secciones de datos del cliente se ocultan automáticamente
4. Solo llenar productos/servicios y condiciones de pago
5. Sistema genera factura con receptor = "CLIENTES VARIOS"

**Estructura enviada al MH:**
```json
{
  "receptor": {
    "tipoDocumento": null,
    "numDocumento": null,
    "nrc": null,
    "nombre": "CLIENTES VARIOS",
    "codActividad": null,
    "descActividad": null,
    "direccion": null,
    "telefono": null,
    "correo": null
  }
}
```

### Modo de Contingencia

**Activar contingencia:**
1. Ir a `/contingencias/crear`
2. Seleccionar tipo de contingencia
3. Ingresar descripción del motivo
4. El sistema marca todas las nuevas facturas como contingencia

**Procesar lote de contingencia:**
```bash
php artisan contingencia:procesar
```
O desde la interfaz web: `/contingencias/{id}`

### Invalidar Factura

1. Ir a `/invalidaciones/seleccionar`
2. Seleccionar factura a invalidar (debe estar enviada al MH)
3. Elegir tipo de invalidación:
   - **Tipo 1**: Error formal (requiere documento de reemplazo)
   - **Tipo 2**: Rescisión de operación
4. Llenar datos de responsable y solicitante
5. Sistema valida plazos y envía al MH

### Consultar Estado de DTE

**Consulta individual:**
1. Ir a `/consultas/dte`
2. Ingresar código de generación
3. Sistema consulta al MH y actualiza estado local

**Consulta rápida:**
- Desde lista de facturas, click en "Consultar" junto a la factura

### Enviar Factura por Correo

1. Ir al detalle de la factura
2. Click en "Enviar por Correo"
3. Sistema envía automáticamente a correo del receptor
4. Incluye JSON (sin credenciales) y HTML de la factura

## 🛠️ Comandos Artisan Disponibles

### Gestión de Tokens MH

```bash
# Renovar token manualmente
php artisan mh:renovar-token

# Diagnosticar problemas de token
php artisan mh:diagnosticar-token

# Verificar formato de NIT
php artisan mh:verificar-nit 06142302881449
```

### Contingencia

```bash
# Procesar eventos de contingencia pendientes
php artisan contingencia:procesar
```

## 📊 Catálogos Oficiales del MH

| Catálogo | Descripción | Ubicación |
|----------|-------------|-----------|
| **CAT-12** | Distritos (estructura antigua - 262 municipios) | `public/js/catalogo-distritos.js` |
| **CAT-13** | Municipios consolidados (47 municipios) | `public/js/catalogo-municipios.js` |
| **CAT-022** | Tipos de documentos de identificación | Hardcoded en forms |
| **CAT-024** | Tipos de contingencia | Hardcoded en forms |
| **CAT-025** | Tipos de documento relacionado | Code logic |
| **CAT-026** | Motivos de invalidación | Hardcoded en forms |

### ⚠️ Nota Importante sobre Municipios

- **DTE-01**: Usa JSON versión 1 (sin campo distrito)
- **DTE-03**: Usa JSON versión 3 (sin campo distrito)
- **DTE-05**: Usa JSON versión 3 (sin campo distrito)
- **DTE-14**: Usa JSON versión 1 (CON campo distrito requerido)

El campo `distrito` solo es requerido en DTE-14 (Sujeto Excluido).

## 📝 Notas Técnicas Importantes

### Formato de Documentos de Identificación

| Tipo | Código | Formato | Ejemplo |
|------|--------|---------|---------|
| NIT | 36 | 14 dígitos sin guiones | `06142302881449` |
| DUI | 13 | 9 dígitos CON guión | `01234567-8` |
| Carnet Residente | 02 | Alfanumérico | Variable |
| Pasaporte | 03 | Alfanumérico | Variable |

**Importante:**
- DUI en JSON versión 1 **REQUIERE guión**: `########-#`
- NIT siempre sin guiones
- El sistema formatea automáticamente según el tipo

### Validaciones Críticas por Tipo de DTE

**DTE-03 (CCF):**
- ❌ El NIT del receptor NO puede ser igual al del emisor
- ✅ El MH valida que el NIT existe y está registrado para IVA
- ✅ NRC y código de actividad económica son obligatorios
- ✅ Solo acepta `tipoDocumento = 36` (NIT)

**DTE-05 (Nota de Crédito):**
- ❌ Solo se puede crear para facturas con receptor NIT (no DUI)
- ✅ Debe referenciar un DTE enviado exitosamente al MH
- ✅ Siempre usa IVA del 13% (incluso si el documento original era DTE-01 con 11.5%)
- ✅ Estructura JSON completamente diferente a DTE-01/03
- ✅ Siempre usar `precioIncluyeIva = false`

**DTE-14 (Sujeto Excluido):**
- ✅ Campo `distrito` es OBLIGATORIO
- ✅ IVA siempre 0%
- ✅ Campo `nrc` debe ser OMITIDO (no permitido en esquema)
- ✅ Usa `ventaExenta` en lugar de `ventaGravada`

### Tasas de IVA

| DTE | Tasa | Fórmula | Uso |
|-----|------|---------|-----|
| DTE-01 | 11.5% | `ventaGravada * 0.115` | Factura consumidor final |
| DTE-03 | 13% | `ventaGravada * 0.13` | Crédito fiscal |
| DTE-05 | **13%** | `ventaGravada * 0.13` | **SIEMPRE** 13% (incluso si el doc original era DTE-01) |
| DTE-14 | 0% | N/A | Sujeto excluido (exento) |

## 🔒 Seguridad

### Protección de Credenciales en Emails

El sistema **automáticamente elimina** las siguientes credenciales sensibles del JSON cuando se envía por correo:

- `nit` - Credencial de autenticación
- `activo` - Estado de credencial
- `passwordPri` - Contraseña de clave privada

**Implementación:** `app/Mail/FacturaElectronicaMail.php`

### SSL/TLS

- Deshabilitado en ambiente de pruebas
- **DEBE** habilitarse en producción
- Configurar en `MHApiService.php`

## 🧪 Ambiente de Pruebas vs Producción

### URLs del MH

**Pruebas** (`DTE_AMBIENTE=00`):
```
AUTH: https://apitest.dtes.mh.gob.sv/seguridad/auth
RECEPCION: https://apitest.dtes.mh.gob.sv/fesv/recepciondte
CONSULTA: https://apitest.dtes.mh.gob.sv/fesv/recepcion/consultadte
```

**Producción** (`DTE_AMBIENTE=01`):
```
AUTH: https://api.dtes.mh.gob.sv/seguridad/auth
RECEPCION: https://api.dtes.mh.gob.sv/fesv/recepciondte
CONSULTA: https://api.dtes.mh.gob.sv/fesv/recepcion/consultadte
```

## 🐛 Troubleshooting

### Token expirado o inválido
```bash
php artisan mh:renovar-token
php artisan mh:diagnosticar-token
```

### Factura no enviada
1. Verificar estado en `/facturas`
2. Si está `firmado_sin_enviar`, consultar logs
3. Revisar respuesta del MH en detalle de factura
4. Verificar conectividad con MH

### Error de validación del MH
1. Ver respuesta completa del MH en detalle de factura
2. Consultar `CLAUDE.md` para errores comunes
3. Verificar estructura JSON generada

## 📚 Documentación Adicional

- **CLAUDE.md**: Guía técnica completa para desarrollo y arquitectura detallada
- **GUIA_CONTINGENCIA.md**: Guía específica del sistema de contingencia
- **DATOS_PRUEBA_CCF.md**: NITs de prueba para ambiente de testing

## 🛣️ Rutas Principales

| Ruta | Descripción |
|------|-------------|
| `/facturas` | Lista de todas las facturas |
| `/facturas/crear` | Crear DTE-01 (Factura) |
| `/facturas/crear-ccf` | Crear DTE-03 (CCF) |
| `/facturas/crear-nota-credito` | Crear DTE-05 (Nota de Crédito) |
| `/facturas/crear-sujeto-excluido` | Crear DTE-14 (Sujeto Excluido) |
| `/facturas/{id}` | Detalle de factura |
| `/contingencias` | Gestión de contingencias |
| `/invalidaciones` | Gestión de invalidaciones |
| `/consultas` | Sistema de consultas |
| `/admin/tokens` | Gestión de tokens MH |

## 💻 Tecnologías Utilizadas

- **Laravel 10.x** - Framework PHP
- **PHP 8.1+** - Lenguaje de programación
- **MySQL/MariaDB** - Base de datos
- **Guzzle HTTP** - Cliente HTTP para APIs
- **TailwindCSS** - Framework CSS
- **API de Firma Digital Local** - Firma con certificados digitales

## 📄 Licencia

Este proyecto está bajo la Licencia MIT.

## 🤝 Contribuciones

Las contribuciones son bienvenidas. Por favor, abre un issue o pull request.

---

**Desarrollado para cumplir con las especificaciones oficiales del Ministerio de Hacienda de El Salvador**

**Última actualización:** 12 de Noviembre de 2025
