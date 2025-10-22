# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a Laravel 10 application for generating and managing electronic invoices (DTE - Documentos Tributarios Electrónicos) according to El Salvador's Ministerio de Hacienda (MH) specifications. The system generates invoices, signs them via a local API, and submits them to the government's tax authority API.

**Supported DTE Types:**
- **DTE-01**: Factura (Consumer Invoice) - Version 2
- **DTE-03**: Comprobante de Crédito Fiscal (CCF - Tax Credit Certificate) - Version 3

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
```

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

### Important Notes

- **IVA Calculation**: The system uses 11.5% IVA rate. Formula: `ventaGravada * 0.115` (see DTEService:71)
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

### Database Schema

**facturas table**: Stores invoice headers with totals, receptor info, and MH response
**factura_items table**: Line items with pricing, quantities, and tax calculations
**tokens_mh table**: Active MH authentication tokens with expiration tracking

### Routes Structure

- `/facturas` - List all invoices (both DTE-01 and DTE-03)
- `/facturas/crear` - Invoice creation form (DTE-01)
- `/facturas/crear-ccf` - CCF creation form (DTE-03)
- `/facturas/{id}` - Invoice details
- `/admin/tokens` - Token management interface

### DTE-03 (CCF) vs DTE-01 (Factura) Key Differences

| Feature | DTE-01 (Factura) | DTE-03 (CCF) |
|---------|------------------|--------------|
| **JSON Version** | 2 | 3 |
| **Receptor Document Type** | NIT, DUI, Passport, etc. | **Only NIT (code 36)** |
| **Receptor NRC** | Optional | **Required** |
| **Receptor Economic Activity Code** | Optional | **Required** |
| **Purpose** | General sales | Sales to IVA-registered taxpayers (tax credit) |
| **IVA Treatment** | Standard | Deductible tax credit for buyer |

**Implementation Notes:**
- The system automatically uses `version: 3` for DTE-03 and `version: 2` for DTE-01 (see DTEService.php:250)
- Validation rules change dynamically based on `tipo_dte` (see FacturaController.php:57-58)
- Each DTE type has independent numbering (correlativo) managed per `tipo_dte`

### Environment Variables Required

Key `.env` variables (see `.env.example` and `config/dte.php`):
- `DTE_NIT`, `DTE_PASSWORD_PRI` - Signing credentials
- `DTE_AMBIENTE` - Environment (00=test, 01=production)
- `DTE_API_URL` - Local signing API endpoint
- `MH_AUTH_URL`, `MH_RECEPCION_URL` - MH API endpoints
- `MH_USER`, `MH_PASSWORD` - MH authentication credentials
- `DTE_EMISOR_*` - Complete issuer business information
