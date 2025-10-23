# Sistema de Facturación Electrónica - El Salvador

Sistema web desarrollado en Laravel 10 para la generación, firma y envío de Documentos Tributarios Electrónicos (DTE) al Ministerio de Hacienda de El Salvador.

## Descripción del Proyecto

Este sistema permite a empresas y negocios en El Salvador cumplir con las regulaciones del Ministerio de Hacienda (MH) para la emisión de documentos tributarios electrónicos. El sistema maneja todo el ciclo de vida de los documentos: generación, firma digital, envío a MH, seguimiento de estado y modo de contingencia.

### Tipos de DTE Soportados

- **DTE-01**: Factura (Consumidor Final) - Versión 2
- **DTE-03**: Comprobante de Crédito Fiscal (CCF) - Versión 3

## Características Principales

### Gestión de Documentos Tributarios
- Generación de facturas con estructura JSON según especificaciones del MH
- Numeración automática de documentos con control de correlativos
- Cálculo automático de IVA (11.5% para DTE-01, 13% para DTE-03)
- Conversión de montos numéricos a texto en español
- Validación de datos según catálogos oficiales del MH

### Integración con Ministerio de Hacienda
- Autenticación automática con API del MH (tokens con renovación automática)
- Envío de DTE firmados con política de reintentos oficial del MH:
  - Timeout de 8 segundos por intento
  - Hasta 2 reintentos (3 intentos totales)
  - Consulta de estado entre reintentos
- Seguimiento de estado de documentos en tiempo real

### Modo de Contingencia
- Soporte completo para eventos de fuerza mayor (CAT-024):
  - Fallas de energía eléctrica
  - Fallas del sistema de emisión
  - Fallas del servicio de internet
  - Fallas de servicios del MH
  - Otros eventos de fuerza mayor
- Procesamiento por lotes de hasta 100 DTEs
- Envío diferido cuando se restablece la conexión

### Firma Digital
- Integración con API local de firma digital
- Generación de JWT firmados
- Almacenamiento de documentos firmados

### Catálogos Oficiales
- **CAT-13**: Municipios consolidados (47 municipios actualizados)
- **CAT-12**: Distritos (estructura antigua de 262 municipios)
- **CAT-022**: Tipos de documentos de identificación
- **CAT-024**: Tipos de contingencia

## Requisitos del Sistema

- PHP >= 8.1
- Composer
- MySQL/MariaDB
- Servidor web (Apache/Nginx) o `php artisan serve`
- API local de firma digital en funcionamiento
- Credenciales del Ministerio de Hacienda

## Instalación

### 1. Clonar el repositorio e instalar dependencias

```bash
composer install
```

### 2. Configurar variables de entorno

```bash
cp .env.example .env
php artisan key:generate
```

**IMPORTANTE**: Debes agregar manualmente las siguientes variables al archivo `.env`:

#### Configuración DTE
```bash
DTE_NIT=06142302881449
DTE_PASSWORD_PRI=tu_password_clave_privada
DTE_AMBIENTE=00                           # 00=pruebas, 01=producción
DTE_ESTABLECIMIENTO=S001
DTE_PUNTO_VENTA=P001
DTE_API_URL=http://127.0.0.1:8113        # URL de API de firma local
DTE_USER_AGENT=SistemaFacturacion/1.0
```

#### Configuración API del MH
```bash
MH_AUTH_URL=https://apitest.dtes.mh.gob.sv/seguridad/auth
MH_RECEPCION_URL=https://apitest.dtes.mh.gob.sv/fesv/recepciondte
MH_CONSULTA_URL=https://apitest.dtes.mh.gob.sv/fesv/recepcion/consultadte
MH_USER=06142302881449
MH_PASSWORD=tu_password_mh
```

#### Política de Reintentos
```bash
DTE_TIMEOUT_ENVIO=8                      # Segundos (según política MH)
DTE_MAX_REINTENTOS=2                     # Reintentos máximos
```

#### Configuración del Emisor
```bash
DTE_EMISOR_NIT=06142302881449
DTE_EMISOR_NRC=3391583
DTE_EMISOR_NOMBRE=Nombre de tu Empresa
DTE_EMISOR_NOMBRE_COMERCIAL=Nombre Comercial
DTE_EMISOR_COD_ACTIVIDAD=33120
DTE_EMISOR_DESC_ACTIVIDAD=VENTA AL POR MAYOR DE PRODUCTOS ALIMENTICIOS
DTE_EMISOR_COD_ESTABLE=0000
DTE_EMISOR_COD_PUNTO_VENTA=0000
DTE_EMISOR_DEPARTAMENTO=06
DTE_EMISOR_MUNICIPIO=23
DTE_EMISOR_DISTRITO=03
DTE_EMISOR_COMPLEMENTO=Colonia Centro
DTE_EMISOR_TELEFONO=64404895
DTE_EMISOR_CORREO=email@example.com
```

#### URLs de Contingencia
```bash
MH_CONTINGENCIA_URL=https://apitest.dtes.mh.gob.sv/fesv/contingencia
MH_RECEPCION_LOTE_URL=https://apitest.dtes.mh.gob.sv/fesv/recepcionlote
MH_CONSULTA_LOTE_URL=https://apitest.dtes.mh.gob.sv/fesv/consultarlote
```

### 3. Configurar base de datos

Edita el `.env` con tus credenciales de base de datos:

```bash
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=factura_electronica
DB_USERNAME=tu_usuario
DB_PASSWORD=tu_password
```

### 4. Ejecutar migraciones

```bash
php artisan migrate
```

### 5. Iniciar el servidor

```bash
php artisan serve
```

El sistema estará disponible en `http://localhost:8000`

## Comandos Artisan Personalizados

### Gestión de Tokens MH

```bash
# Renovar token del MH manualmente
php artisan mh:renovar-token

# Diagnosticar problemas con el token
php artisan mh:diagnosticar-token

# Verificar formato de NIT para CCF
php artisan mh:verificar-nit {nit}
```

### Modo de Contingencia

```bash
# Procesar eventos de contingencia pendientes
php artisan contingencia:procesar

# Procesar un evento específico
php artisan contingencia:procesar --evento_id=1
```

## Uso del Sistema

### Crear una Factura (DTE-01)

1. Acceder a `/facturas/crear`
2. Llenar los datos del receptor (acepta NIT, DUI, pasaporte, etc.)
3. Agregar los ítems de la factura
4. El sistema automáticamente:
   - Genera el JSON del DTE
   - Firma el documento
   - Envía al MH
   - Actualiza el estado

### Crear un Comprobante de Crédito Fiscal (DTE-03)

1. Acceder a `/facturas/crear-ccf`
2. Llenar los datos del receptor (**solo acepta NIT**)
3. Ingresar NRC y código de actividad económica (requeridos)
4. Agregar los ítems
5. El sistema procesa igual que DTE-01 pero con validaciones específicas de CCF

**Nota importante para CCF**: El NIT del receptor NO puede ser el mismo que el del emisor. El MH validará que el NIT existe y está registrado para IVA.

### Activar Modo de Contingencia

1. Acceder a `/contingencias/crear`
2. Seleccionar el tipo de contingencia (1-5)
3. Describir el motivo
4. Mientras la contingencia esté activa:
   - Las facturas se firman localmente
   - Se marcan como `firmado_contingencia`
   - NO se envían al MH en tiempo real
5. Cuando se restablezca la conexión:
   - Cerrar el evento de contingencia
   - Procesar el lote (envía todas las facturas pendientes)
   - El MH procesa el lote en 2-3 minutos

## Arquitectura del Sistema

### Servicios Principales

**DTEService** (`app/Services/DTEService.php`)
- Genera códigos únicos de documentos (UUID v4)
- Genera números de control
- Calcula totales e IVA
- Construye estructura JSON según especificaciones del MH

**MHApiService** (`app/Services/MHApiService.php`)
- Gestiona autenticación con el MH
- Implementa política de reintentos oficial
- Envía documentos firmados
- Consulta estado de DTEs

**ContingenciaService** (`app/Services/ContingenciaService.php`)
- Gestiona eventos de contingencia
- Construye lotes de DTEs
- Procesa envíos diferidos

### Modelos Principales

- **Factura**: Cabecera de facturas con todos los datos del DTE
- **FacturaItem**: Ítems de línea con precios e IVA
- **TokenMH**: Tokens de autenticación del MH
- **EventoContingencia**: Eventos de fuerza mayor

### Flujo de Procesamiento

```
Usuario crea factura
    ↓
DTEService genera JSON
    ↓
Se guarda en base de datos
    ↓
Se envía a API de firma local
    ↓
Se recibe documento firmado (JWT)
    ↓
MHApiService envía al MH (con reintentos)
    ↓
Se actualiza estado según respuesta MH
    ↓
Estados finales: enviado_mh | firmado_sin_enviar | rechazado
```

## Pruebas

```bash
# Ejecutar todas las pruebas
php artisan test

# Ejecutar una prueba específica
php artisan test --filter=NombreDeLaPrueba
```

## Formato de Código

```bash
# Formatear código con Laravel Pint
vendor/bin/pint
```

## Documentación Adicional

- `CLAUDE.md`: Guía técnica completa para desarrollo
- `GUIA_CONTINGENCIA.md`: Documentación detallada del modo de contingencia (si existe)
- `DATOS_PRUEBA_CCF.md`: Datos de prueba para CCF (si existe)

## Notas Importantes

### IVA por Tipo de Documento
- **DTE-01**: IVA del 11.5%
- **DTE-03**: IVA del 13%

### Formato de Documentos de Identificación
- **DUI**: 9 dígitos sin guión (ej: `012345678`)
- **NIT**: 14 dígitos sin guiones (ej: `06142302881449`)

### Catálogos Municipios vs Distritos
- **CAT-13** (Municipios): Actualizado a 47 municipios consolidados
- **CAT-12** (Distritos): NO actualizado, sigue usando estructura de 262 municipios antiguos
- **Importante**: No existe mapeo oficial entre los nuevos municipios y los códigos de distrito antiguos

### Política de Reintentos del MH
- Timeout de 8 segundos por intento
- Máximo 2 reintentos (3 intentos totales)
- Consulta de estado entre reintentos
- Espera de 1 segundo entre reintentos

## Tecnologías Utilizadas

- **Laravel 10**: Framework PHP
- **Guzzle HTTP**: Cliente HTTP para comunicación con MH
- **MySQL/MariaDB**: Base de datos
- **Bootstrap**: Frontend (si aplica)
- **API de Firma Digital**: Servicio externo para firma de documentos

## Soporte

Para reportar problemas o solicitar funcionalidades, por favor crea un issue en el repositorio del proyecto.

## Licencia

Este proyecto es software propietario. Todos los derechos reservados.

---

**Desarrollado para cumplir con las especificaciones del Ministerio de Hacienda de El Salvador**
