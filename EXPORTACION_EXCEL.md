# Funcionalidad de Exportación a Excel

## Descripción

Se ha agregado una funcionalidad para exportar las facturas visibles en la tabla de la vista principal a un archivo Excel (CSV) con los campos requeridos.

## Características

### Campos Exportados

El archivo Excel generado incluye las siguientes columnas:

1. **Fecha de Emisión** - Fecha en formato DD/MM/YYYY
2. **Tipo de DTE** - Código del tipo de documento (01, 03, 05, 14)
3. **Número de Control** - Número de control del documento
4. **Sello del Documento** - Sello de recepción del MH (extraído de respuesta_mh)
5. **Número de Control Interno** - ID interno de la base de datos
6. **Código de Generación** - UUID del documento
7. **Ventas** - Total a pagar (monto de ventas)

### Funcionamiento

- **Botón de Exportación**: Se agregó un botón verde "Exportar a Excel" en la sección de filtros
- **Respeta Filtros**: La exportación respeta todos los filtros aplicados en la vista:
  - Búsqueda por texto
  - Tipo de DTE
  - Estado
  - Rango de fechas
  - Mes y año
- **Formato CSV**: El archivo se genera en formato CSV con delimitador punto y coma (;)
- **Codificación UTF-8**: El archivo incluye BOM UTF-8 para correcta visualización en Excel
- **Nombre del Archivo**: `facturas_export_YYYY-MM-DD_HHMMSS.csv`

## Uso

1. **Sin Filtros**: Click en "Exportar a Excel" exportará todas las facturas
2. **Con Filtros**:
   - Aplique los filtros deseados (tipo de DTE, estado, fechas, etc.)
   - Click en "Buscar" para aplicar filtros
   - Click en "Exportar a Excel"
   - Se exportarán solo las facturas que coincidan con los filtros

## Implementación Técnica

### Archivos Modificados

1. **app/Http/Controllers/FacturaController.php**
   - Método `exportarExcel()` agregado (líneas 796-933)
   - Genera CSV con los datos de las facturas filtradas
   - Extrae el sello de recepción del JSON respuesta_mh

2. **routes/web.php**
   - Ruta POST `/facturas/exportar-excel` agregada (línea 19)
   - Nombre de ruta: `facturas.exportar_excel`

3. **resources/views/facturas/index.blade.php**
   - Botón "Exportar a Excel" agregado (líneas 142-149)
   - Función JavaScript `exportarExcel()` (líneas 387-437)
   - Envía formulario con todos los filtros aplicados

### Notas Técnicas

- No se requirió instalar PhpSpreadsheet (se usa CSV nativo de PHP)
- El CSV es compatible con Excel, LibreOffice Calc y Google Sheets
- El sello de recepción se extrae automáticamente del campo `respuesta_mh`
- Si una factura no tiene sello, se deja el campo vacío
- La exportación registra log con la cantidad de facturas exportadas

## Ejemplo de Uso

### Caso 1: Exportar facturas de un mes específico

1. Seleccione "Mes: Noviembre" y "Año: 2025"
2. Click en "Buscar"
3. Click en "Exportar a Excel"
4. Se descargará un archivo con todas las facturas de Noviembre 2025

### Caso 2: Exportar solo DTE-01 enviadas al MH

1. Seleccione "Tipo DTE: DTE-01"
2. Seleccione "Estado: Enviada MH"
3. Click en "Buscar"
4. Click en "Exportar a Excel"
5. Se descargará un archivo solo con DTE-01 en estado enviado_mh

### Caso 3: Exportar facturas entre fechas

1. Ingrese "Desde: 01/11/2025"
2. Ingrese "Hasta: 15/11/2025"
3. Click en "Buscar"
4. Click en "Exportar a Excel"
5. Se descargará un archivo con facturas del rango especificado

## Formato del Archivo Excel

```csv
Fecha de Emisión;Tipo de DTE;Número de Control;Sello del Documento;Número de Control Interno;Código de Generación;Ventas
23/11/2025;01;DTE-01-S001P001-00001;ABC123XYZ...;1;550e8400-e29b-41d4-a716-446655440000;100.00
22/11/2025;03;DTE-03-S001P001-00001;DEF456UVW...;2;550e8400-e29b-41d4-a716-446655440001;250.50
```

## Soporte

Para cualquier problema con la exportación, verificar:
- Logs en `storage/logs/laravel.log`
- Permisos de escritura en el servidor
- Configuración de PHP para manejo de archivos CSV
