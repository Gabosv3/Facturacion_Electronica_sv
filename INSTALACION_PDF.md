# Instalación Manual de Librería PDF

## Problema Detectado

El servidor tiene un problema con los certificados SSL de PHP que impide descargar paquetes desde Packagist mediante Composer.

## Solución: Instalación Manual de barryvdh/laravel-dompdf

### Opción 1: Instalar mediante Composer (Recomendado)

Si logras resolver el problema de certificados SSL, ejecuta:

```bash
composer require barryvdh/laravel-dompdf
```

### Opción 2: Instalación Manual (Si Composer no funciona)

#### Paso 1: Descargar los paquetes necesarios

Descarga manualmente los siguientes paquetes desde un equipo con internet:

1. **dompdf/dompdf** (https://github.com/dompdf/dompdf/releases)
   - Descarga la última versión (v2.x o superior)

2. **barryvdh/laravel-dompdf** (https://github.com/barryvdh/laravel-dompdf/releases)
   - Descarga la última versión compatible con Laravel 10

3. **phenx/php-font-lib** (https://github.com/dompdf/php-font-lib/releases)

4. **phenx/php-svg-lib** (https://github.com/dompdf/php-svg-lib/releases)

#### Paso 2: Copiar a vendor

1. Extrae cada paquete descargado
2. Copia cada carpeta a `vendor/` siguiendo esta estructura:

```
vendor/
├── dompdf/
│   └── dompdf/
├── barryvdh/
│   └── laravel-dompdf/
├── phenx/
│   ├── php-font-lib/
│   └── php-svg-lib/
```

#### Paso 3: Registrar el autoloader

Edita `composer.json` y agrega en la sección "autoload":

```json
"autoload": {
    "psr-4": {
        "App\\": "app/",
        "Database\\Factories\\": "database/factories/",
        "Database\\Seeders\\": "database/seeders/",
        "Dompdf\\": "vendor/dompdf/dompdf/src/",
        "Barryvdh\\DomPDF\\": "vendor/barryvdh/laravel-dompdf/src/"
    }
}
```

Luego ejecuta:

```bash
composer dump-autoload
```

#### Paso 4: Publicar configuración

```bash
php artisan vendor:publish --provider="Barryvdh\DomPDF\ServiceProvider"
```

### Opción 3: Usar mPDF (Alternativa)

Si prefieres usar mPDF, descarga manualmente desde:
- https://github.com/mpdf/mpdf/releases

Y sigue los mismos pasos de instalación manual.

### Opción 4: Solucionar problema de SSL

Edita `php.ini` de XAMPP (usualmente en `C:\xampp\php\php.ini`):

1. Busca la línea: `;curl.cainfo =`
2. Descarga el archivo cacert.pem desde: https://curl.se/ca/cacert.pem
3. Guárdalo en `C:\xampp\php\cacert.pem`
4. Modifica php.ini:
   ```ini
   curl.cainfo = "C:\xampp\php\cacert.pem"
   ```
5. Reinicia Apache
6. Ejecuta: `composer require barryvdh/laravel-dompdf`

## Verificar Instalación

Después de instalar, verifica ejecutando:

```bash
php artisan tinker
```

Y dentro de tinker:

```php
class_exists('\Dompdf\Dompdf');
// Debería devolver: true

class_exists('\Barryvdh\DomPDF\Facade\Pdf');
// Debería devolver: true
```

## Uso del Sistema

Una vez instalada la librería PDF, el sistema ya está configurado para:

1. **Enviar facturas por correo como PDF** (en lugar de HTML)
2. El servicio `PdfService` detectará automáticamente la librería instalada
3. Si la generación de PDF falla, enviará HTML como fallback

## Notas Importantes

- El sistema ya está modificado para generar PDFs
- La clase `FacturaElectronicaMail` usa el servicio `PdfService`
- El servicio `PdfService` soporta múltiples librerías PDF:
  - barryvdh/laravel-dompdf (Recomendada para Laravel)
  - mpdf/mpdf
  - tecnickcom/tcpdf
  - dompdf/dompdf (nativa)

## Comandos de Prueba

Para probar que el PDF se genera correctamente:

```bash
php artisan tinker
```

```php
$factura = App\Models\Factura::where('estado', 'enviado_mh')->first();
$pdfService = app(\App\Services\PdfService::class);
$pdf = $pdfService->generateFromView('facturas.pdf', ['factura' => $factura]);
echo 'PDF generado: ' . strlen($pdf) . ' bytes';
```

Si ves un número de bytes (no un error), el PDF se generó correctamente.
