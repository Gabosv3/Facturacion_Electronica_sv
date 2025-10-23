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

## Arquitectura del Sistema

### Servicios Principales

**DTEService** (`app/Services/DTEService.php`)
- Genera códigos únicos de documentos (UUID v4)
- Genera números de control con formato `DTE-{tipo}-{establecimiento}{puntoVenta}-{correlativo}`
- Calcula totales e IVA según tipo de documento
- Construye estructura JSON según especificaciones del MH

**MHApiService** (`app/Services/MHApiService.php`)
- Gestiona autenticación con el MH (tokens con renovación automática cada 24h)
- Implementa política de reintentos oficial del MH
- Envía documentos firmados y consulta estado
- Maneja comunicación con todos los endpoints del MH

**ContingenciaService** (`app/Services/ContingenciaService.php`)
- Gestiona eventos de contingencia por fuerza mayor
- Construye lotes de DTEs para envío diferido
- Procesa envíos cuando se restablece la conexión

### Modelos Principales

- **Factura**: Cabecera de facturas con todos los datos del DTE, estados y respuestas del MH
- **FacturaItem**: Ítems de línea con precios, cantidades e IVA
- **TokenMH**: Tokens de autenticación del MH con validación de expiración
- **EventoContingencia**: Eventos de fuerza mayor con lotes de DTEs asociados

### Flujo de Procesamiento

```
Usuario crea factura
    ↓
DTEService genera JSON del DTE
    ↓
Se guarda en base de datos
    ↓
Se envía a API de firma local
    ↓
Se recibe documento firmado (JWT)
    ↓
MHApiService envía al MH (con política de reintentos)
    ↓
Se actualiza estado según respuesta MH
    ↓
Estados finales: enviado_mh | firmado_sin_enviar | rechazado
```

### Flujo de Contingencia

```
Evento de fuerza mayor detectado
    ↓
Se crea evento de contingencia
    ↓
Facturas se firman localmente (no se envían al MH)
    ↓
Se marcan como firmado_contingencia
    ↓
Se asocian con el evento de contingencia
    ↓
Cuando se restablece la conexión:
    ↓
Se envía evento de contingencia al MH
    ↓
Se envía lote con todas las facturas pendientes
    ↓
Se consulta estado del lote
    ↓
MH procesa el lote (2-3 minutos)
```

## Funcionalidades del Sistema

### Creación de Facturas (DTE-01)
- Interfaz web para ingreso de datos del receptor
- Soporte para múltiples tipos de documentos (NIT, DUI, pasaporte, etc.)
- Agregar ítems con cálculo automático de totales
- Generación, firma y envío automático al MH

### Creación de Comprobantes de Crédito Fiscal (DTE-03)
- Validación estricta: solo acepta NIT como documento del receptor
- Campos requeridos: NRC y código de actividad económica del receptor
- Validación de que el NIT del receptor existe en la base del MH
- IVA del 13% (deducible como crédito fiscal)

### Gestión de Tokens MH
- Renovación automática de tokens antes de expiración
- Comandos artisan para renovación manual y diagnóstico
- Almacenamiento seguro en base de datos

### Sistema de Contingencia
- Activación manual de eventos de contingencia
- Detección automática de contingencia activa al crear facturas
- Procesamiento de lotes vía comando artisan o interfaz web
- Seguimiento de estado de lotes enviados

## Notas Técnicas Importantes

### IVA por Tipo de Documento
- **DTE-01**: IVA del 11.5%
- **DTE-03**: IVA del 13%

### Formato de Documentos de Identificación
- **DUI**: 9 dígitos sin guión al enviar al MH (ej: `012345678`)
- **NIT**: 14 dígitos sin guiones (ej: `06142302881449`)

### Catálogos Municipios vs Distritos
- **CAT-13** (Municipios): Actualizado a 47 municipios consolidados
- **CAT-12** (Distritos): NO actualizado, sigue usando estructura de 262 municipios antiguos
- **Importante**: No existe mapeo oficial entre los nuevos municipios y los códigos de distrito antiguos

### Política de Reintentos del MH
- Timeout de 8 segundos por intento
- Máximo 2 reintentos (3 intentos totales)
- Consulta de estado entre reintentos si hay timeout
- Espera de 1 segundo entre reintentos
- Registro de todos los intentos en base de datos

### Validaciones CCF Importantes
- El NIT del receptor NO puede ser el mismo que el del emisor
- El MH valida que el NIT existe y está registrado para IVA
- Los campos NRC y código de actividad económica son obligatorios

## Tecnologías Utilizadas

- **Laravel 10**: Framework PHP para el backend
- **Guzzle HTTP**: Cliente HTTP para comunicación con APIs del MH
- **MySQL/MariaDB**: Base de datos relacional
- **API de Firma Digital**: Servicio local para firma de documentos con certificados digitales

## Documentación Adicional

- `CLAUDE.md`: Guía técnica completa para desarrollo y arquitectura detallada

---

**Desarrollado para cumplir con las especificaciones del Ministerio de Hacienda de El Salvador**
