# Cambios Realizados: Envío de Facturas por Email como PDF

## Resumen

Se ha modificado el sistema para que cuando se envíe una factura por correo electrónico, se adjunte un **PDF** en lugar de un archivo HTML.

## Archivos Modificados

### 1. `app/Mail/FacturaElectronicaMail.php`

**Cambio principal:** Se modificó el método `attachments()` para generar y adjuntar un PDF en lugar de HTML.

**Antes:**
```php
// Adjuntar HTML/PDF
$attachments[] = Attachment::fromData(function () {
    return view('facturas.pdf', ['factura' => $this->factura])->render();
}, $this->factura->numero_control . '.html')
    ->withMime('text/html');
```

**Después:**
```php
// Adjuntar PDF
try {
    $pdfService = app(\App\Services\PdfService::class);
    $pdfContent = $pdfService->generateFromView('facturas.pdf', ['factura' => $this->factura]);

    $attachments[] = Attachment::fromData(function () use ($pdfContent) {
        return $pdfContent;
    }, $this->factura->numero_control . '.pdf')
        ->withMime('application/pdf');
} catch (\Exception $e) {
    // Si falla la generación del PDF, adjuntar HTML como fallback
    \Log::warning('Error al generar PDF para factura, usando HTML como fallback', [
        'factura_id' => $this->factura->id,
        'error' => $e->getMessage()
    ]);

    $attachments[] = Attachment::fromData(function () {
        return view('facturas.pdf', ['factura' => $this->factura])->render();
    }, $this->factura->numero_control . '.html')
        ->withMime('text/html');
}
```

**Características:**
- Genera PDF usando el servicio `PdfService`
- Si falla la generación del PDF, envía HTML como fallback
- Registra advertencias en el log si ocurre algún error

### 2. `app/Services/PdfService.php` (NUEVO)

**Archivo creado:** Servicio especializado para generar PDFs desde vistas Blade.

**Funcionalidades:**
- Detecta automáticamente qué librería PDF está instalada
- Soporta múltiples librerías PDF:
  - `barryvdh/laravel-dompdf` (Recomendada para Laravel)
  - `dompdf/dompdf` (nativa)
  - `mpdf/mpdf`
  - `tecnickcom/tcpdf`
- Método principal: `generateFromView($view, $data)`
- Métodos privados para cada librería específica
- Manejo de errores con mensaje descriptivo si no hay librería instalada

**Uso:**
```php
$pdfService = app(\App\Services\PdfService::class);
$pdfContent = $pdfService->generateFromView('facturas.pdf', ['factura' => $factura]);
```

### 3. `composer.json`

**Cambio:** Se agregó la dependencia `barryvdh/laravel-dompdf` en la sección `require`:

```json
"require": {
    "php": "^8.1",
    "barryvdh/laravel-dompdf": "^2.0",
    "guzzlehttp/guzzle": "^7.8",
    "laravel/framework": "^10.10",
    "laravel/sanctum": "^3.3",
    "laravel/tinker": "^2.8",
    "phpoffice/phpspreadsheet": "^1.29"
},
```

**Nota:** Debido a problemas con certificados SSL en el servidor, la librería no se pudo instalar automáticamente. Ver `INSTALACION_PDF.md` para instrucciones de instalación manual.

### 4. `INSTALACION_PDF.md` (NUEVO)

**Archivo creado:** Guía completa para instalar manualmente la librería PDF.

**Contenido:**
- Explicación del problema de certificados SSL
- 4 opciones de solución:
  1. Instalar vía Composer (si se resuelve el problema SSL)
  2. Instalación manual de paquetes
  3. Usar mPDF como alternativa
  4. Solucionar problema de SSL en php.ini
- Comandos de verificación
- Comandos de prueba

## Flujo de Funcionamiento

### Flujo Normal (Con librería PDF instalada)

```
1. Usuario hace clic en "Enviar por Correo"
   ↓
2. FacturaController::enviarEmail($factura)
   ↓
3. Se crea instancia de FacturaElectronicaMail
   ↓
4. El método attachments() se ejecuta:
   - Adjunta JSON del DTE (sin credenciales sensibles)
   - Llama a PdfService->generateFromView()
   ↓
5. PdfService detecta librería PDF instalada
   ↓
6. Genera PDF desde la vista 'facturas.pdf'
   ↓
7. Adjunta PDF al correo (.pdf)
   ↓
8. Envía correo con PDF adjunto
```

### Flujo Fallback (Sin librería PDF o con error)

```
1-4. [Mismo flujo anterior]
   ↓
5. PdfService lanza excepción (no hay librería)
   ↓
6. catch() en FacturaElectronicaMail captura el error
   ↓
7. Registra advertencia en log
   ↓
8. Genera HTML desde vista 'facturas.pdf'
   ↓
9. Adjunta HTML al correo (.html)
   ↓
10. Envía correo con HTML adjunto (fallback)
```

## Instalación Requerida

**IMPORTANTE:** Para que el sistema funcione completamente, es necesario instalar una librería PDF.

### Opción Recomendada (Más fácil si funciona):

```bash
composer require barryvdh/laravel-dompdf
```

### Si Composer no funciona por SSL:

Ver instrucciones detalladas en `INSTALACION_PDF.md`

## Verificación

### 1. Verificar que la librería está instalada:

```bash
php artisan tinker
```

```php
class_exists('\Dompdf\Dompdf');
// Debe devolver: true
```

### 2. Probar generación de PDF:

```bash
php artisan tinker
```

```php
$factura = App\Models\Factura::where('estado', 'enviado_mh')->first();
$pdfService = app(\App\Services\PdfService::class);
$pdf = $pdfService->generateFromView('facturas.pdf', ['factura' => $factura]);
echo 'PDF generado: ' . strlen($pdf) . ' bytes';
```

Si muestra el tamaño en bytes, el PDF se generó correctamente.

### 3. Probar envío de correo:

1. Asegúrate de que la factura tenga un correo electrónico válido
2. Ve al detalle de la factura en el navegador
3. Haz clic en "Enviar por Correo"
4. Verifica que el correo se envíe
5. Revisa el archivo adjunto - debe ser un `.pdf`

## Logs

Si hay algún problema, revisa los logs de Laravel:

```bash
tail -f storage/logs/laravel.log
```

Busca entradas que contengan:
- `Error al generar PDF para factura`
- `Factura enviada por email`

## Mantenimiento

### Si se quiere cambiar de librería PDF:

El sistema está diseñado para soportar múltiples librerías. Simplemente instala la librería deseada y `PdfService` la detectará automáticamente en este orden de prioridad:

1. barryvdh/laravel-dompdf (Laravel wrapper)
2. dompdf/dompdf (nativa)
3. mpdf/mpdf
4. tecnickcom/tcpdf

### Si se quiere personalizar el PDF:

Edita la vista `resources/views/facturas/pdf.blade.php` - ya está optimizada para impresión y conversión a PDF.

## Beneficios

✅ Los receptores reciben un PDF profesional en lugar de HTML
✅ Mayor compatibilidad con clientes de correo
✅ Mejor presentación en dispositivos móviles
✅ Archivo más profesional para archivado
✅ Fallback automático a HTML si hay problemas
✅ Sistema flexible que soporta múltiples librerías PDF

## Próximos Pasos

1. **Instalar librería PDF** (Ver `INSTALACION_PDF.md`)
2. **Verificar instalación** (comandos arriba)
3. **Probar envío de correo** con una factura de prueba
4. **(Opcional)** Configurar DomPDF si es necesario (ver `config/dompdf.php` después de publicar)

## Notas Técnicas

- El JSON del DTE se sigue enviando adjunto (con credenciales sensibles removidas)
- El correo en sí usa la vista `resources/views/emails/factura_electronica.blade.php`
- La vista del PDF está en `resources/views/facturas/pdf.blade.php`
- El servicio PDF es reutilizable para otros casos de uso en el futuro

## Soporte

Si tienes problemas:
1. Revisa `INSTALACION_PDF.md`
2. Verifica los logs (`storage/logs/laravel.log`)
3. Ejecuta los comandos de verificación mostrados arriba
4. Si el fallback está funcionando, verás archivos `.html` adjuntos en lugar de `.pdf`
