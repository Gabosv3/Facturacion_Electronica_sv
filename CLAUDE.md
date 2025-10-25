# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a Laravel 10 application for generating and managing electronic invoices (DTE - Documentos Tributarios Electrónicos) according to El Salvador's Ministerio de Hacienda (MH) specifications. The system generates invoices, signs them via a local API, and submits them to the government's tax authority API.

**Supported DTE Types:**
- **DTE-01**: Factura (Consumer Invoice) - Version 2
- **DTE-03**: Comprobante de Crédito Fiscal (CCF - Tax Credit Certificate) - Version 3
- **DTE-05**: Nota de Crédito (Credit Note) - Version 3

## Development Commands

### Initial Setup
```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
```

### Running the Application
```bash
php artisan serve
```

### Database Operations
```bash
php artisan migrate                    # Run migrations
php artisan migrate:fresh              # Fresh migration (drops all tables)
php artisan migrate:fresh --seed       # Fresh migration with seeding
```

### Testing
```bash
php artisan test                       # Run PHPUnit tests
php artisan test --filter=TestName     # Run specific test
vendor/bin/phpunit                     # Direct PHPUnit execution
```

### MH Token Management
```bash
php artisan mh:renovar-token          # Renew MH authentication token
php artisan mh:diagnosticar-token     # Diagnose token issues
php artisan mh:verificar-nit {nit}    # Verify NIT format for CCF receptor
```

### Contingency Mode (Eventos de Fuerza Mayor)
```bash
php artisan contingencia:procesar     # Process pending contingency events
                                      # - Send contingency event to MH
                                      # - Send batch of DTEs
                                      # - Query batch processing status
```

**Custom Artisan Commands:**
- `RenovarTokenMH` (`app/Console/Commands/RenovarTokenMH.php`) - Manually renew the MH authentication token
- `DiagnosticarTokenMH` (`app/Console/Commands/DiagnosticarTokenMH.php`) - Check token status and diagnose issues
- `VerificarNIT` (`app/Console/Commands/VerificarNIT.php`) - Verify NIT format and show test data suggestions
- `ProcesarContingencias` (`app/Console/Commands/ProcesarContingencias.php`) - Process contingency events and batches

### Code Quality
```bash
vendor/bin/pint                       # Laravel Pint code formatting
```

### Development Tools
```bash
php artisan tinker                    # Laravel REPL
php artisan route:list                # List all routes
php artisan config:clear              # Clear config cache
php artisan cache:clear               # Clear application cache
```

## Architecture

### Core Services

**DTEService** (`app/Services/DTEService.php`)
- Generates unique document identifiers (UUID v4 for `codigoGeneracion`)
- Generates control numbers with format: `DTE-{tipoDte}-{establecimiento}{puntoVenta}-{correlativo}`
- Calculates invoice totals and tax amounts (IVA calculations at 11.5%)
- Converts numbers to Spanish text for invoice amounts
- Constructs the complete DTE JSON structure per MH specifications

**MHApiService** (`app/Services/MHApiService.php`)
- Manages authentication tokens with the Ministerio de Hacienda API
- Handles token lifecycle (generation, storage, validation, renewal)
- Submits signed DTEs to MH's reception endpoint
- Tokens are stored in `tokens_mh` table and auto-renewed before expiration (23h 50min lifetime)

### Invoice Processing Flow

1. User submits invoice data via `FacturaController@store`
2. `DTEService` constructs the DTE JSON structure
3. Invoice and items saved to database (`facturas` and `factura_items` tables)
4. DTE sent to local signing API (`config('dte.api_url')/firmardocumento/`)
5. Signed JWT returned from signing API
6. `MHApiService` submits signed DTE to MH with valid authentication token
7. Invoice status updated based on MH response:
   - `pendiente` → `firmado` → `enviado_mh` (success)
   - `firmado_sin_enviar` (MH submission failed)
   - `error_firma` (signing failed)

### Key Models

**Factura** (`app/Models/Factura.php`)
- Main invoice model with all header information
- Stores complete DTE JSON in `dte_json` field
- Related to `FacturaItem` via `items()` relationship
- Tracks status through invoice lifecycle

**TokenMH** (`app/Models/TokenMH.php`)
- Stores MH authentication tokens with expiration
- `isValid()` method checks token validity
- Auto-deactivates old tokens when generating new ones

### Configuration

**config/dte.php**
- All DTE/MH configuration centralized here
- Emisor (issuer) configuration with NIT, NRC, business details
- API endpoints for local signing service and MH APIs
- Environment setting (`00` = testing, `01` = production)

**Frontend Catalogs** (`public/js/`)
- `catalogo-municipios.js` - Consolidated municipalities (CAT-13) loaded dynamically in forms
- `catalogo-distritos.js` - Districts catalog (CAT-12) for reference only, not loaded dynamically

### Important Notes

- **IVA Calculation**: The system uses different IVA rates per document type:
  - DTE-01 (Factura): 11.5% - Formula: `ventaGravada * 0.115`
  - DTE-03 (CCF): 13% - Formula: `ventaGravada * 0.13`
  - DTE-05 (Nota de Crédito): Inherits rate from related document (11.5% if DTE-01, 13% if DTE-03)
- **Document Numbering**: Control numbers auto-increment per document type, extracting last correlative from database
- **Token Renewal**: Tokens expire in ~24h; system auto-renews at 23h 50min to prevent gaps
- **SSL Verification**: Disabled in non-production environments (see MHApiService:62, 186)
- **Receptor District**: The `distrito` field is REQUIRED and must correspond to a valid district from CAT-12 for the selected municipality

### Receptor Document Types (CAT-022)

According to Ministerio de Hacienda specifications:

- **36 = NIT**: For taxpayers registered in IVA (14 digits, no dashes)
- **13 = DUI**: For natural persons NOT registered in IVA (9 digits, format: `########-#`)
- **02 = Carnet de Residente**: Resident card
- **03 = Pasaporte**: Passport (alphanumeric)
- **37 = Otro**: Other documents

**Important:** DUI (code 13) should ONLY be used for natural persons who are NOT registered in IVA. For IVA registered taxpayers, always use NIT (code 36).

**Formatting when sending to MH:**
- **DUI**: 9 digits **WITHOUT dash** (e.g., `012345678` not `01234567-8`)
- **NIT**: 14 digits without dashes (e.g., `06142302881449`)
- **Others**: Alphanumeric, as provided

**Note:** Even though users may see the DUI with dash in the UI for readability, it must be sent to MH without the dash (only 9 numeric digits).

### Municipalities Catalog (CAT-13)

The system implements the **updated** official CAT-13 catalog with El Salvador's consolidated municipalities:

- **Location**: `public/js/catalogo-municipios.js`
- **Structure**: 14 departments, each with their consolidated municipalities
- **Total municipalities**: 47 consolidated municipalities (reduced from the previous 262)
- **Special code**: `00` = "Otro (Para extranjeros)"
- **Implementation**: Dynamic dropdown that loads municipalities based on selected department
- **Usage**: When creating invoices, selecting a department automatically populates the municipality dropdown

**Examples of consolidated municipalities:**
- Ahuachapán: AHUACHAPAN NORTE, AHUACHAPAN CENTRO, AHUACHAPAN SUR (3 municipalities)
- San Salvador: SAN SALVADOR NORTE, SAN SALVADOR OESTE, SAN SALVADOR ESTE, SAN SALVADOR CENTRO, SAN SALVADOR SUR (5 municipalities)
- Santa Ana: SANTA ANA NORTE, SANTA ANA CENTRO, SANTA ANA ESTE, SANTA ANA OESTE (4 municipalities)

### Districts Catalog (CAT-12) - IMPORTANTE

⚠️ **CRITICAL**: The MH has NOT updated the CAT-12 district catalog to match the new consolidated municipalities (CAT-13). The district codes still correspond to the OLD 262 municipalities structure, not the new 47 consolidated municipalities.

**Current Implementation:**
- **Location**: `public/js/catalogo-distritos.js` (reference catalog, not dynamically loaded)
- **Manual input field** for district code (2 digits)
- **Default value**: `03`
- **Format**: 2-digit code (e.g., `01`, `02`, `03`)
- **Validation**: Required field, must be 2 digits

**Important Notes:**
- The district code must correspond to a district from the **OLD municipality structure** (before consolidation)
- Even though you select a NEW consolidated municipality in CAT-13, the district code must be from the old structure
- Example:
  - Department: `11` (Usulután)
  - Municipality: `25` (USULUTAN ESTE - consolidated)
  - District: `03` (from old structure) ✅ Works
  - District: `01` (assuming it's from new structure) ❌ Rejected by MH

**Why This Happens:**
- MH updated CAT-13 (municipalities) in 2024/2025
- MH did NOT update CAT-12 (districts) to match the new structure
- There is NO official mapping between new consolidated municipalities and old district codes
- Users must know which old district codes are valid for each case

**Future Solution:**
When MH provides the updated CAT-12 catalog or a mapping document, the system can be updated to:
1. Implement dynamic district dropdown based on correct CAT-12
2. Remove the manual input
3. Provide proper validation

### MH Retry Policy (Política de Reintentos)

The system implements the official Ministerio de Hacienda retry policy for DTE submissions:

**Implementation Details:**
- **Timeout**: 8 seconds per attempt (configurable via `DTE_TIMEOUT_ENVIO`)
- **Max Retries**: 2 retries (configurable via `DTE_MAX_REINTENTOS`) = 3 total attempts
- **Flow**:
  1. Attempt to send DTE with 8-second timeout
  2. If timeout or failure occurs, query DTE status via `consultarEstadoDTE()`
  3. If DTE was not received by MH, retry (up to 2 more times)
  4. Wait 1 second between retry attempts
  5. Track total attempts in database (`intentos_envio` field)

**Key Methods:**
- `MHApiService::enviarDTE()` - Main method with retry logic (MHApiService.php:199)
- `MHApiService::consultarEstadoDTE()` - Query DTE status on MH (MHApiService.php:122)
- `MHApiService::intentarEnviarDTE()` - Single send attempt with 8s timeout (MHApiService.php:337)

**Configuration:**
```php
// config/dte.php
'timeout_envio' => 8,        // Seconds (per MH policy)
'max_reintentos' => 2,       // Maximum retries (per MH policy)
'mh_consulta_url' => '...',  // MH status query endpoint
```

**Database Tracking:**
The `facturas.intentos_envio` field stores the number of attempts made. The `respuesta_mh` JSON includes:
- `intentos`: Number of attempts
- `recibido_en_consulta`: Whether DTE was found via status query
- Full MH response data

### Contingency Mode System (Sistema de Contingencias)

The system implements the official MH contingency process for force majeure events that prevent real-time DTE submission.

**Contingency Types (CAT-024):**
1. Power supply failure at issuer location
2. Issuer's emission system failure
3. Internet service failure
4. MH services failure
5. Other force majeure events

**Contingency Workflow:**

**Phase 1: During Contingency**
1. Create contingency event via `/contingencias/crear` or manually in database
2. System automatically detects active contingency in `FacturaController`
3. New invoices are signed locally but marked as `firmado_contingencia` (not sent to MH)
4. DTEs are associated with the active contingency event
5. Maximum 100 DTEs per batch (MH limit)

**Phase 2: After Connection is Restored**
1. Close the contingency event (changes status from `activo` to `cerrado`)
2. Run `php artisan contingencia:procesar` or use web interface
3. System sends:
   - **Step 1**: Contingency event to MH (`/fesv/contingencia`)
   - **Step 2**: Batch of signed DTEs (`/fesv/recepcionlote`)
   - **Step 3**: Query batch status (`/fesv/consultarlote`)
4. MH processes batch in 2-3 minutes (100 DTEs average)
5. System marks event as `procesado` when complete

**Key Services:**
- `ContingenciaService` (`app/Services/ContingenciaService.php`) - Business logic for contingencies
- `MHApiService` methods:
  - `enviarEventoContingencia()` - Send contingency event
  - `enviarLote()` - Send batch of DTEs
  - `consultarLote()` - Query batch processing status

**Controllers:**
- `ContingenciaController` (`app/Http/Controllers/ContingenciaController.php`) - Web interface management
- `FacturaController` - Auto-detects active contingency and marks invoices accordingly

**Important Notes:**
- Only ONE contingency event can be active at a time
- Contingency mode available 24/7/365 (no time restrictions unlike normal batches)
- Test environment: Max 300 batches, Production: Max 400 batches
- DTEs must be signed BEFORE sending in batch
- Complete workflow documented in `GUIA_CONTINGENCIA.md`

**Environment Variables:**
```bash
MH_CONTINGENCIA_URL=https://apitest.dtes.mh.gob.sv/fesv/contingencia
MH_RECEPCION_LOTE_URL=https://apitest.dtes.mh.gob.sv/fesv/recepcionlote
MH_CONSULTA_LOTE_URL=https://apitest.dtes.mh.gob.sv/fesv/consultarlote
```

### Database Schema

**facturas table**: Stores invoice headers with totals, receptor info, and MH response
- New fields: `en_contingencia` (boolean), `evento_contingencia_id` (FK), `estado_lote` (string)

**factura_items table**: Line items with pricing, quantities, and tax calculations

**tokens_mh table**: Active MH authentication tokens with expiration tracking

**eventos_contingencia table**: Contingency events for force majeure situations
- `codigo_evento`: UUID for the contingency event
- `tipo_contingencia`: Type 1-5 according to MH catalog CAT-024
- `motivo_contingencia`: Description of the contingency reason
- `fecha_hora_inicio` / `fecha_hora_fin`: Event time range
- `cantidad_dte`: Number of DTEs issued during contingency
- `estado`: activo, cerrado, enviado, procesado
- `codigo_lote`: Batch code returned by MH
- `detalle_lote`: Processing details from MH

### Routes Structure

- `/facturas` - List all invoices (DTE-01, DTE-03, and DTE-05)
- `/facturas/crear` - Invoice creation form (DTE-01)
- `/facturas/crear-ccf` - CCF creation form (DTE-03)
- `/facturas/crear-nota-credito` - Credit Note creation form (DTE-05)
- `/facturas/{id}` - Invoice details
- `/admin/tokens` - Token management interface
- `/contingencias` - Contingency events management
- `/contingencias/crear` - Create new contingency event
- `/contingencias/{id}` - Contingency event details and processing

### DTE Types Comparison

| Feature | DTE-01 (Factura) | DTE-03 (CCF) | DTE-05 (Nota de Crédito) |
|---------|------------------|--------------|--------------------------|
| **JSON Version** | 2 | 3 | 3 |
| **Receptor Document Type** | NIT, DUI, Passport, etc. | **Only NIT (code 36)** | Uses same as related document |
| **Receptor NRC** | Optional | **Required** | Copied from related document |
| **Receptor Economic Activity Code** | Optional | **Required** | Copied from related document |
| **Purpose** | General sales | Sales to IVA-registered taxpayers | Cancel/adjust DTE-01 or DTE-03 |
| **IVA Rate** | 11.5% | 13% | **Always 13%** (regardless of related doc) |
| **Requires Related Document** | No | No | **Yes (required)** |

**Implementation Notes:**
- The system automatically uses `version: 3` for DTE-03 and DTE-05, and `version: 2` for DTE-01 (see DTEService.php)
- Validation rules change dynamically based on `tipo_dte` (see FacturaController.php:71)
- Each DTE type has independent numbering (correlativo) managed per `tipo_dte`

**Critical Validations:**
- ⚠️ **DTE-03 (CCF)**: Receptor NIT CANNOT be the same as Emisor NIT - MH will reject with error "[receptor.nit] VALOR NO ES PERMITIDO"
- ⚠️ **DTE-05 (Nota de Crédito)**: Must reference a valid DTE-01 or DTE-03 that was successfully sent to MH
- ⚠️ **All types**: The MH validates that NITs exist in their database (even in test environment)
- For testing, see `DATOS_PRUEBA_CCF.md` for sample NITs or use command `php artisan mh:verificar-nit {nit}`

### DTE-05 (Nota de Crédito) - Credit Notes

**Purpose**: Credit Notes are used to cancel or adjust previously issued DTE-01 (Factura) or DTE-03 (CCF) documents.

**Use Cases:**
1. **Cancellation** (tipo_documento: 1) - Document issued in error
2. **Price Adjustment** (tipo_documento: 2) - Correction of amounts
3. **Returns** (tipo_documento: 3) - Customer returns products

**Key Schema Differences from DTE-01/DTE-03:**

The DTE-05 JSON schema is **completely different** from DTE-01 and DTE-03:

**Fields REQUIRED in DTE-05:**
- `documentoRelacionado` - Reference to the original DTE (tipo, numeroDocumento, fechaEmision)
- `extension` - With fields: nombEntrega, docuEntrega, nombRecibe, docuRecibe, observaciones
- `resumen.ivaPerci1` - IVA perceived (usually 0.0)
- `resumen.reteRenta` - Income tax retention (usually 0.0)
- `resumen.tributos` - Array with IVA object structure (codigo, descripcion, valor)
- `receptor.nit` - Uses NIT directly (not tipoDocumento/numDocumento)
- `receptor.nombreComercial` - Required
- `emisor.tipoEstablecimiento` - '01' = Casa Matriz, '02' = Sucursal, '04' = Punto de Venta
- `cuerpoDocumento[].numeroDocumento` - Number of related document
- `cuerpoDocumento[].tipoItem` - Must be 2 (for version 3)
- `cuerpoDocumento[].codTributo` - Tax code (null for non-taxed items)
- `cuerpoDocumento[].ventaNoSuj` - Non-subject sales (usually 0.0)
- `cuerpoDocumento[].ventaExenta` - Exempt sales (usually 0.0)
- `cuerpoDocumento[].tributos` - Simple array of tax codes (e.g., ['20'] for IVA)

**Fields NOT PERMITTED in DTE-05:**
- `receptor.tipoDocumento` / `receptor.numDocumento` (uses only `nit`)
- `receptor.direccion.distrito` / `emisor.direccion.distrito`
- `emisor.codEstable` / `emisor.codPuntoVenta`
- `resumen.totalIva`, `resumen.totalPagar`, `resumen.pagos`, `resumen.numPagoElectronico`
- `resumen.porcentajeDescuento`, `resumen.observaciones`, `resumen.totalNoGravado`, `resumen.saldoFavor`
- `cuerpoDocumento[].ivaItem`, `cuerpoDocumento[].psv`, `cuerpoDocumento[].noGravado`
- `otrosDocumentos`

**Document Related Type (CAT-025):**
- `03` = Factura (when canceling/adjusting DTE-01)
- `05` = Comprobante de Crédito Fiscal (when canceling/adjusting DTE-03)

**Implementation:**
- Auto-fills receptor data from the related document
- **CRITICAL**: DTE-05 ALWAYS uses 13% IVA rate, regardless of the related document type
  - Even if the related document is DTE-01 (11.5%), the credit note must use 13%
  - This is a MH requirement validated during submission
  - Enforced in `DTEService.php:561` with `$tasaIva = 0.13`
- Validates that the related document exists and was successfully sent to MH
- Observaciones moved from resumen to extension.observaciones
- **CRITICAL**: Always uses `precioIncluyeIva = false` to ensure correct IVA calculations
  - Credit note prices are copied from the original invoice which already has base amounts (venta gravada) separate from IVA
  - This is enforced in `FacturaController.php:185`
- `montoTotalOperacion` includes IVA (same as regular invoices): `totalGravada + IVA`

**Important Limitation:**
- ⚠️ **Credit Notes can ONLY be issued for invoices with NIT (not DUI)**
- The system filters out invoices with `receptor_tipo_documento = '13'` (DUI) from the available list
- This is because DTE-05 requires `receptor.nit` field and MH validates that the NIT exists in their database
- DUIs (9 digits) are not valid NITs (14 digits) and will be rejected by MH with error "[receptor.nit] NIT CONTRIBUYENTE NO EXISTE"
- Solution: Create new invoices using NIT (code 36) as document type for receptors who need credit notes

**Migration:**
Run `php artisan migrate` to add DTE-05 support fields:
- `documento_relacionado_tipo_dte`
- `documento_relacionado_numero_control`
- `documento_relacionado_codigo_generacion`
- `documento_relacionado_fecha_emision`
- `tipo_documento` (1=Anulación, 2=Ajuste, 3=Devolución)

**Common DTE-05 Errors and Solutions:**

1. **`[cuerpoDocumento.item.1] DEBE PROPORCIONAR UN VALOR`**
   - Cause: Missing required fields in item structure
   - Solution: Ensure `tipoItem`, `codTributo`, `ventaNoSuj`, `ventaExenta`, `tributos` are all present

2. **`Campo totalPagar no esta permitido en #/resumen`**
   - Cause: DTE-05 does not use `totalPagar` field (unlike DTE-01/DTE-03)
   - Solution: Use only `montoTotalOperacion` in resumen

3. **`Campo noGravado no esta permitido`, `Campo psv no esta permitido`**
   - Cause: These fields exist in DTE-03 but not in DTE-05
   - Solution: Remove `psv` and `noGravado` from cuerpoDocumento items

4. **`[resumen.tributos] FALTAN DATOS PARA VALIDAR INFORMACION`**
   - Cause: Missing `tributos` array in resumen
   - Solution: Add tributos array with IVA object: `[{codigo: '20', descripcion: 'IVA', valor: totalIva}]`

5. **`[resumen.tributos.codigo.20] CALCULO INCORRECTO`**
   - Cause: Using `precioIncluyeIva = true` when it should be `false`
   - Solution: DTE-05 must always use `precioIncluyeIva = false` because prices come from the original invoice
   - Implementation: See `FacturaController.php:185`

6. **`[receptor.nit] NIT CONTRIBUYENTE NO EXISTE`**
   - Cause: Trying to create credit note for invoice with DUI instead of NIT
   - Solution: Only create credit notes for invoices where receptor has NIT (code 36)

### Environment Variables Required

**IMPORTANT**: The `.env.example` file only contains standard Laravel variables. You must manually add all DTE/MH configuration variables below.

Key `.env` variables (see `config/dte.php` for defaults):

**DTE Configuration:**
```bash
DTE_NIT=06142302881449
DTE_PASSWORD_PRI=your_private_key_password
DTE_AMBIENTE=00                           # 00=test, 01=production
DTE_ESTABLECIMIENTO=S001
DTE_PUNTO_VENTA=P001
DTE_API_URL=http://127.0.0.1:8113        # Local signing API
DTE_USER_AGENT=SistemaFacturacion/1.0
```

**MH API Configuration:**
```bash
MH_AUTH_URL=https://apitest.dtes.mh.gob.sv/seguridad/auth
MH_RECEPCION_URL=https://apitest.dtes.mh.gob.sv/fesv/recepciondte
MH_CONSULTA_URL=https://apitest.dtes.mh.gob.sv/fesv/recepcion/consultadte
MH_USER=06142302881449
MH_PASSWORD=your_mh_password
```

**Retry Policy Configuration:**
```bash
DTE_TIMEOUT_ENVIO=8                      # Seconds (per MH policy)
DTE_MAX_REINTENTOS=2                     # Maximum retries
```

**Emisor (Issuer) Configuration:**
```bash
DTE_EMISOR_NIT=06142302881449
DTE_EMISOR_NRC=3391583
DTE_EMISOR_NOMBRE=Your Business Name
DTE_EMISOR_NOMBRE_COMERCIAL=Your Commercial Name
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
