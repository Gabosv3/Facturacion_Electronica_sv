# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a Laravel 10 application for generating and managing electronic invoices (DTE - Documentos Tributarios Electrónicos) according to El Salvador's Ministerio de Hacienda (MH) specifications. The system generates invoices, signs them via a local API, and submits them to the government's tax authority API.

**Supported DTE Types:**
- **DTE-01**: Factura (Consumer Invoice) - Version 1
- **DTE-03**: Comprobante de Crédito Fiscal (CCF - Tax Credit Certificate) - Version 3
- **DTE-05**: Nota de Crédito (Credit Note) - Version 3
- **DTE-14**: Factura de Sujeto Excluido (Exempt Subject Invoice) - Version 1

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
- Provides methods to query DTE status individually or in batches
- Tokens are stored in `tokens_mh` table and auto-renewed before expiration (23h 50min lifetime)

**ContingenciaService** (`app/Services/ContingenciaService.php`)
- Manages contingency events for force majeure situations
- Creates and processes batches of DTEs for deferred submission
- Handles contingency event lifecycle and batch tracking

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

**EventoContingencia** (`app/Models/EventoContingencia.php`)
- Stores contingency event information
- Tracks DTEs issued during contingency periods
- Manages batch submission status and results

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
  - DTE-05 (Nota de Crédito): Always 13% (regardless of related document type)
  - DTE-14 (Sujeto Excluido): 0% - No IVA (uses `ventaExenta` instead of `ventaGravada`)
- **Document Numbering**: Control numbers auto-increment per document type, extracting last correlative from database
- **Token Renewal**: Tokens expire in ~24h; system auto-renews at 23h 50min to prevent gaps
- **SSL Verification**: Disabled in non-production environments (see MHApiService:62, 186)
- **Receptor District**:
  - **DTE-01**: The `distrito` field is NOT included (JSON version 1 without distrito)
  - **DTE-03**: The `distrito` field is NOT included (JSON version 3 without distrito)
  - **DTE-14**: The `distrito` field IS required (JSON version 1 with distrito)
- **Receptor Optional Fields**: The following receptor fields are USER-OPTIONAL but must be present in JSON with `null` value when empty:
  - `nrc`: Tax registration number
    - **DTE-01**: Must be present with `null` if empty
    - **DTE-03**: Required with actual value (IVA-registered taxpayers)
    - **DTE-14**: Must be **OMITTED** (not permitted in schema, not even as `null`)
  - `codActividad`: Economic activity code (optional for DTE-01, DTE-14; required for DTE-03) - `null` if empty
  - `descActividad`: Economic activity description (optional for DTE-01, DTE-14; required for DTE-03) - `null` if empty
  - `telefono`: Phone number (optional for all DTE types) - `null` if empty
  - `correo`: Email address (optional for all DTE types) - `null` if empty
  - **Critical**: MH schema v2 requires these fields to be present in the JSON structure (except `nrc` in DTE-14). When empty, they must have `null` value, NOT omitted or empty strings
  - **Implementation**:
    - `construirReceptor($data, $omitirNrc)` handles field inclusion logic (DTEService.php:343)
    - DTE-01, DTE-03, DTE-05 call with `$omitirNrc = false` (default)
    - DTE-14 calls with `$omitirNrc = true` to exclude NRC field entirely
    - Form validation is conditional - these fields are required only for DTE-03 (FacturaController.php:90)
  - **Example DTE-01**: `"nrc": null`, `"telefono": null`, `"correo": null`
  - **Example DTE-14**: NRC field completely omitted from JSON structure

### Receptor Document Types (CAT-022)

According to Ministerio de Hacienda specifications:

- **36 = NIT**: For taxpayers registered in IVA (14 digits, no dashes)
- **13 = DUI**: For natural persons NOT registered in IVA (9 digits, format: `########-#`)
- **02 = Carnet de Residente**: Resident card
- **03 = Pasaporte**: Passport (alphanumeric)
- **37 = Otro**: Other documents

**Important:** DUI (code 13) should ONLY be used for natural persons who are NOT registered in IVA. For IVA registered taxpayers, always use NIT (code 36).

**Formatting when sending to MH:**
- **DUI**: 9 digits **WITH dash** in format `########-#` (e.g., `01234567-8`)
  - **IMPORTANT**: JSON version 1 requires DUI with dash
  - The system automatically formats: `012345678` → `01234567-8`
- **NIT**: 14 digits without dashes (e.g., `06142302881449`)
- **Others**: Alphanumeric, as provided

**Note:** The DUI format changed between JSON versions. Version 1 (DTE-01, DTE-14) requires the dash, while older versions did not.

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

⚠️ **UPDATE (2025-11-11)**: The `distrito` field has been **REMOVED** from DTE-01 (Factura) as the system now uses JSON version 1 which does not include this field.

**Current Implementation by DTE Type:**
- **DTE-01 (Factura)**: Does NOT use distrito field (version 1)
- **DTE-03 (CCF)**: Does NOT use distrito field (version 3)
- **DTE-05 (Nota de Crédito)**: Does NOT use distrito field (version 3)
- **DTE-14 (Sujeto Excluido)**: DOES use distrito field (version 1 with distrito)

**For DTE-14 Only:**
- **Location**: `public/js/catalogo-distritos.js` (reference catalog)
- **Manual input field** for district code (2 digits)
- **Default value**: `01`
- **Format**: 2-digit code (e.g., `01`, `02`, `03`)
- **Validation**: Required field for DTE-14 only

**Important Notes:**
- The district catalog issue with consolidated municipalities no longer affects DTE-01, DTE-03, or DTE-05
- Only DTE-14 (Sujeto Excluido) requires the distrito field
- This change aligns with MH's JSON version 1 specification for DTE-01

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

**Automatic Seal Updates (Added 2025-11-05):**
When a contingency is processed and MH returns status `PROCESADO`, the system automatically updates all invoices in the batch with:
- Reception seal (`selloRecibido`) extracted from MH's batch response
- Status changed from `firmado_contingencia` to `enviado_mh`
- Complete MH response data including processing timestamp
- Metadata marking it as processed via contingency (`via_contingencia: true`)

This automation eliminates the need for manual status queries and enables immediate use of features like invalidation that require the reception seal. Implementation in `EventoContingencia::marcarProcesado()` and `EventoContingencia::actualizarFacturasConSellos()` (EventoContingencia.php:119-231).

**Environment Variables:**
```bash
MH_CONTINGENCIA_URL=https://apitest.dtes.mh.gob.sv/fesv/contingencia
MH_RECEPCION_LOTE_URL=https://apitest.dtes.mh.gob.sv/fesv/recepcionlote
MH_CONSULTA_LOTE_URL=https://apitest.dtes.mh.gob.sv/fesv/consultarlote
```

### DTE Query System (Sistema de Consultas)

The system provides comprehensive DTE query functionality to check document status on the MH servers.

**Query Types Available:**

1. **Individual DTE Query** (`/consultas/dte`)
   - Query specific DTE by generation code
   - Returns current status, validation details, and processing information
   - Useful for tracking submission status and troubleshooting

2. **Batch Query** (`/consultas/lote`)
   - Query entire batch by batch code
   - Returns processing status for all DTEs in the batch
   - Used primarily for contingency batch tracking

3. **Quick Query** (`/consultas/rapida/{factura}`)
   - Quick status lookup for invoices in the local database
   - Automatically queries MH and updates local status
   - Convenient shortcut from invoice list view

4. **Search and Query** (`/consultas/buscar`)
   - Search by control number or generation code
   - Automatically queries MH if found in local database
   - Updates local status based on MH response

**Implementation:**
- `ConsultaController` (`app/Http/Controllers/ConsultaController.php`) - Handles all query operations
- `MHApiService::consultarEstadoDTE()` - Individual DTE status query (MHApiService.php:122)
- `MHApiService::consultarLote()` - Batch status query
- Results are automatically saved to `respuesta_mh` field in database

**Use Cases:**
- Verify DTE was received and processed by MH
- Check validation errors or rejection reasons
- Monitor contingency batch processing status
- Troubleshoot submission issues
- Audit DTE status for accounting reconciliation

**Environment Variables:**
```bash
MH_CONSULTA_DTE_URL=https://apitest.dtes.mh.gob.sv/fesv/recepcion/consultadte/
MH_CONSULTA_DTE_LOTE_URL=https://apitest.dtes.mh.gob.sv/fesv/recepcion/consultadtelote
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

- `/facturas` - List all invoices (DTE-01, DTE-03, DTE-05, and DTE-14)
- `/facturas/crear` - Invoice creation form (DTE-01)
- `/facturas/crear-ccf` - CCF creation form (DTE-03)
- `/facturas/crear-nota-credito` - Credit Note creation form (DTE-05)
- `/facturas/crear-sujeto-excluido` - Exempt Subject Invoice creation form (DTE-14)
- `/facturas/{id}` - Invoice details
- `/admin/tokens` - Token management interface
- `/contingencias` - Contingency events management
- `/contingencias/crear` - Create new contingency event
- `/contingencias/{id}` - Contingency event details and processing
- `/consultas` - DTE query interface
- `/consultas/dte` - Query individual DTE status
- `/consultas/lote` - Query batch status
- `/consultas/rapida/{factura}` - Quick query for specific invoice
- `/invalidaciones` - Invalidation events management
- `/invalidaciones/seleccionar` - Select invoice to invalidate
- `/invalidaciones/crear` - Create invalidation event
- `/invalidaciones/{id}` - Invalidation event details

### DTE Types Comparison

| Feature | DTE-01 (Factura) | DTE-03 (CCF) | DTE-05 (Nota de Crédito) | DTE-14 (Sujeto Excluido) |
|---------|------------------|--------------|--------------------------|---------------------------|
| **JSON Version** | 1 | 3 | 3 | 1 |
| **Distrito Field** | **No** | **No** | **No** | **Yes** |
| **Receptor Document Type** | NIT, DUI, Passport, etc. | **Only NIT (code 36)** | Uses same as related document | NIT, DUI, Passport, etc. |
| **Receptor NRC** | Optional | **Required** | Copied from related document | Optional |
| **Receptor Economic Activity Code** | Optional | **Required** | Copied from related document | Optional |
| **Purpose** | General sales | Sales to IVA-registered taxpayers | Cancel/adjust DTE-01 or DTE-03 | Sales to IVA-exempt entities |
| **IVA Rate** | 11.5% | 13% | **Always 13%** (regardless of related doc) | **0% (IVA exempt)** |
| **Requires Related Document** | No | No | **Yes (required)** | No |

**Implementation Notes:**
- The system automatically uses `version: 1` for DTE-01 and DTE-14, `version: 3` for DTE-03 and DTE-05 (see DTEService.php)
- Validation rules change dynamically based on `tipo_dte` (see FacturaController.php:82)
- Each DTE type has independent numbering (correlativo) managed per `tipo_dte`
- **IMPORTANT**: DTE-01 structure matches MH's JSON version 1 specification

**DTE-01 JSON Version 1 Key Requirements:**
- ✅ `identificacion.version: 1` in DTE JSON
- ✅ `version: 1` in envelope sent to MH
- ✅ NO `distrito` field in direccion (emisor and receptor)
- ✅ DUI format with dash: `########-#`
- ✅ Required emisor fields: `tipoEstablecimiento`, `codEstableMH`, `codEstable`, `codPuntoVentaMH`, `codPuntoVenta` (all can be null)
- ✅ Required in resumen: `reteRenta` (usually 0.0)
- ✅ Required: `extension` field (can be null)

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

### DTE-14 (Factura de Sujeto Excluido) - Exempt Subject Invoice

**Purpose**: Used for sales to IVA-exempt entities such as diplomatic missions, international organizations, NGOs with tax exemption status, and other legally exempt subjects.

**Key Characteristics:**
- **JSON Version**: 1 (same as original DTE-01 structure)
- **IVA Rate**: 0% (completely exempt from IVA)
- **Receptor Types**: Can use any document type (NIT, DUI, Passport, etc.)
- **Special Fields**: Similar structure to DTE-01 but with IVA exemption handling

**When to Use DTE-14:**
- Sales to foreign diplomatic missions
- Sales to international organizations (UN, OAS, etc.)
- Sales to NGOs with official tax exemption certificates
- Sales to entities with special tax exemption status granted by the MH

**Important Notes:**
- ⚠️ The receptor must have a valid tax exemption status recognized by the MH
- The system automatically sets IVA to 0% for all items in DTE-14
- Control number format remains the same: `DTE-14-{establecimiento}{puntoVenta}-{correlativo}`
- Independent correlativo counter separate from DTE-01, DTE-03, and DTE-05

**Implementation Details:**
- Uses JSON version 1 (see DTEService.php:352)
- No IVA calculations applied (ventaExenta used instead of ventaGravada)
- Validation rules ensure proper exempt subject documentation
- Follows same signing and submission flow as other DTE types

### Invalidation System (Sistema de Invalidación)

The system implements the official MH invalidation process to cancel or nullify DTEs that were previously sent and received a reception seal.

**Purpose**: Allows the issuing taxpayer to transmit the inactivation of a DTE previously received by the MH. Once invalidated, the DTE receives an "INVALIDADO" status but remains in the MH database for audit purposes.

**Valid Reasons for Invalidation (CAT-026):**
- Error in customer identification
- Error in product or service billed
- Error in operation date
- Error in price of goods or services (applies only to Factura and Export Invoice)
- Return of goods
- Total rescission of the operation

**Invalidation Types:**
1. **Type 1 - Formal Errors**: Document issued incorrectly and must be replaced
   - Requires: New corrected document must be issued FIRST
   - Must include: Code of the replacement document
   - Example: Invoice with wrong customer name

2. **Type 2 - Rescind Operation**: Cancel transaction completely
   - No replacement document needed
   - Example: Customer returns product for full refund

**Invalidation Workflow:**

```
1. User selects invoice to invalidate (must be estado='enviado_mh')
   ↓
2. System validates invalidation eligibility and deadlines
   ↓
3. User fills invalidation form:
   - Type of invalidation (1 or 2)
   - Reason/motive
   - Responsible person data (issuer side)
   - Requester person data
   - Replacement invoice (if type 1)
   ↓
4. System creates EventoInvalidacion record
   ↓
5. System builds JSON event structure
   ↓
6. Event sent to local signing API
   ↓
7. Signed event sent to MH (/fesv/anulardte)
   ↓
8. MH validates and assigns "INVALIDADO" status to original DTE
   ↓
9. Invoice marked as invalidada=true in local database
```

**Deadlines for Invalidation:**

The MH validates two dates:
1. **Event Date** vs. **DTE Generation Date** (fecha de generación)
2. **Transmission Date of Event** vs. **DTE Transmission Date**

**By DTE Type:**
- **DTE-03 (CCF), DTE-05 (Credit Note)**: Maximum 1 day after generation AND 1 day after transmission
- **DTE-01 (Factura), DTE-14 (Exempt Subject)**: Maximum 3 months after generation AND 3 months after transmission

**Key Services:**

**InvalidacionService** (`app/Services/InvalidacionService.php`)
- Creates invalidation events
- Builds JSON structure per MH specifications
- Signs events via local API
- Sends to MH
- Validates deadlines and eligibility
- Complete workflow: `procesarInvalidacion($data)`

**MHApiService** Methods:
- `enviarInvalidacion($eventoFirmado, $version)` - Sends invalidation event to MH

**Controllers:**
- `InvalidacionController` (`app/Http/Controllers/InvalidacionController.php`) - Web interface management

**Important Requirements:**

1. **Replacement Document Priority**: When invalidating for formal errors (type 1), the replacement document MUST be issued and sent to MH FIRST, then create the invalidation event referencing the new document.

2. **Required Fields**: All these fields are mandatory:
   - Responsible person name, document type, document number (issuer side)
   - Requester person name, document type, document number
   - Invalidation reason/motive
   - Original DTE sello recibido (obtained from respuesta_mh)

3. **Special Conditions for Facturas**:
   - When the receptor fields in the original factura are `null`, they must also be `null` in the invalidation event
   - This applies to: tipoDocumento, numDocumento, nombre

4. **Cannot Be Invalidated**:
   - DTEs not successfully sent to MH (estado != 'enviado_mh')
   - DTEs already invalidated
   - DTEs outside the allowed deadline window

**Database Schema:**

**eventos_invalidacion table**:
- `factura_id`: Foreign key to the DTE being invalidated
- `codigo_invalidacion`: UUID for the invalidation event
- `tipo_invalidacion`: 1 (formal errors) or 2 (rescind operation)
- `factura_reemplazo_id`: Foreign key to replacement invoice (null for type 2)
- `codigo_generacion_reemplazo`: Code of replacement document
- `responsable_*`: Responsible person data (issuer)
- `solicitante_*`: Requester person data
- `evento_json`: Complete event JSON structure
- `evento_firmado`: Signed JWT from signing API
- `estado`: pendiente, firmado, enviado, procesado, error
- `respuesta_mh`: Response from MH
- `sello_recepcion`: Reception seal from MH

**facturas table additions**:
- `invalidada`: Boolean flag indicating if invoice is invalidated
- `evento_invalidacion_id`: Foreign key to the invalidation event

**Environment Variables:**
```bash
MH_INVALIDACION_URL=https://apitest.dtes.mh.gob.sv/fesv/anulardte  # Test
MH_INVALIDACION_URL=https://api.dtes.mh.gob.sv/fesv/anulardte       # Production
```

**Common Errors and Solutions:**

1. **"Plazo excedido para invalidar"**
   - Cause: Attempting to invalidate outside the allowed deadline
   - Solution: Check if DTE is within 1 day (CCF) or 3 months (Factura) from both generation and transmission dates

2. **"Documento debe estar enviado al MH"**
   - Cause: Trying to invalidate a DTE that hasn't been successfully sent
   - Solution: Only invalidate DTEs with estado='enviado_mh'

3. **"Debe emitir documento de reemplazo primero"**
   - Cause: Type 1 invalidation without providing replacement document
   - Solution: Issue and send the corrected document first, then create invalidation event

4. **"Sello de recepción no encontrado"**
   - Cause: Missing sello_recibido in the original DTE respuesta_mh
   - Solution: Verify the DTE was properly processed by MH and has a valid seal

**Usage Example:**
```php
// Through web interface
1. Navigate to /invalidaciones/seleccionar
2. Select invoice to invalidate
3. Fill form with invalidation data
4. Submit - system processes automatically

// Programmatically
$invalidacionService->procesarInvalidacion([
    'factura_id' => $facturaId,
    'tipo_invalidacion' => '1', // or '2'
    'motivo_invalidacion' => 'Error in customer name',
    'factura_reemplazo_id' => $nuevaFacturaId, // null if type 2
    'codigo_generacion_reemplazo' => $codigo, // null if type 2
    'responsable_nombre' => 'Juan Perez',
    'responsable_tipo_doc' => '13', // DUI
    'responsable_num_doc' => '012345678',
    'solicitante_nombre' => 'Maria Lopez',
    'solicitante_tipo_doc' => '13',
    'solicitante_num_doc' => '087654321',
]);
```

**Migration:**
Run `php artisan migrate` to create the `eventos_invalidacion` table and add invalidation fields to `facturas` table.

### QR Code Generation System (Código QR de Consulta Pública)

The system automatically generates QR codes for all successfully sent invoices (estado = 'enviado_mh') according to MH specifications.

**MH Requirements:**
```
URL Format: https://admin.factura.gob.sv/consultaPublica?ambiente={ambiente}&codGen={cod_generacion}&fechaEmi={fechaEmi}

Parameters:
- ambiente: "00" (Test) or "01" (Production)
- codGen: Document generation code (UUID)
- fechaEmi: Document emission date (YYYY-MM-DD format)
```

**Implementation:**

The `Factura` model includes three methods for QR code generation (Factura.php:116-161):

1. **`getUrlConsultaPublica()`** - Generates the MH public consultation URL
   ```php
   $url = $factura->getUrlConsultaPublica();
   // Returns: https://admin.factura.gob.sv/consultaPublica?ambiente=00&codGen=...&fechaEmi=2025-10-17
   ```

2. **`getQrCodeUrl($size)`** - Generates URL to get QR code image from API
   ```php
   $qrImageUrl = $factura->getQrCodeUrl(200);
   // Returns URL to QR Server API with the encoded consultation URL
   ```

3. **`getQrCodeBase64($size)`** - Generates base64-encoded QR image (optional)
   ```php
   $qrBase64 = $factura->getQrCodeBase64(200);
   // Returns: data:image/png;base64,iVBORw0KGgoAAAANS...
   ```

**Display on Invoice:**
- QR code is automatically displayed on invoice detail page (`resources/views/facturas/detalle.blade.php:281-306`)
- Only shows for invoices with status `enviado_mh`
- Includes both QR image and full consultation URL
- QR code is included when printing the invoice (with print-specific CSS)

**QR Code Service:**
- Uses public API: `https://api.qrserver.com/v1/create-qr-code/`
- No external dependencies required (no Composer packages needed)
- Default size: 200x200 pixels (configurable)
- Generates standard PNG images compatible with all QR readers

**Usage Example:**
```blade
{{-- In Blade template --}}
@if($factura->estado == 'enviado_mh')
    <img src="{{ $factura->getQrCodeUrl(200) }}"
         alt="Código QR de Consulta Pública">
    <p>{{ $factura->getUrlConsultaPublica() }}</p>
@endif
```

**Important Notes:**
- QR codes are only generated for invoices successfully sent to MH
- The QR links directly to MH's public consultation portal
- Users can verify invoice authenticity by scanning the QR code
- QR code is optimized for printing with `print-color-adjust: exact`

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
