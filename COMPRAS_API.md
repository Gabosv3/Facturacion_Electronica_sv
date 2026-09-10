# 📦 API de Compras para Kardex - TallerGonzalez

## Descripción

Endpoint de lectura que expone las órdenes de compra del sistema con toda la información necesaria para generar el Kardex en el sistema externo. No crea ni modifica datos — solo lee lo que ya existe en `pedidos`.

---

## Autenticación

Todos los endpoints requieren **Bearer Token** (Sanctum).

```
Authorization: Bearer {tu_token}
```

**Obtener token:**

```bash
curl -X POST http://localhost/api/login \
  -H "Content-Type: application/json" \
  -d '{"email": "usuario@example.com", "password": "tu_contraseña"}'
```

**Respuesta:**

```json
{
  "user": { "id": 1, "name": "Gabriel Alegría" },
  "token": "1|abc123...",
  "message": "Sesión iniciada correctamente"
}
```

---

## Endpoints

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| `GET` | `/api/compras/kardex` | Listar órdenes por rango de fechas |
| `GET` | `/api/compras/kardex/{id}` | Detalle de una orden específica |

---

## GET /api/compras/kardex

Lista las órdenes de compra filtradas por rango de fechas. Incluye proveedor, productos, cantidades y precios.

### Parámetros

| Parámetro | Tipo | Requerido | Descripción |
|-----------|------|-----------|-------------|
| `from` | date | ✅ | Fecha inicial `YYYY-MM-DD` |
| `to` | date | ✅ | Fecha final `YYYY-MM-DD` (≥ from) |
| `proveedor_id` | integer | ❌ | Filtrar por proveedor |
| `estado` | string | ❌ | `pendiente`, `confirmado`, `en_camino`, `parcial`, `completado`, `cancelado` |

### Ejemplo de Request

```bash
curl -X GET "http://localhost/api/compras/kardex?from=2024-06-01&to=2024-06-30&estado=completado" \
  -H "Authorization: Bearer TOKEN"
```

### Respuesta (200)

```json
{
  "data": [
    {
      "id": 15,
      "numero_orden": "PED-20240614-0001",
      "fecha_orden": "2024-06-14",
      "fecha_esperada": "2024-06-21",
      "fecha_entrega": "2024-06-19",
      "estado": "completado",
      "proveedor": {
        "id": 1,
        "codigo": "PROV-001",
        "nombre": "Distribuidora ABC",
        "telefono": "2234-5678",
        "email": "contacto@distribuidora.com"
      },
      "subtotal": 750.00,
      "monto_impuesto": 0.00,
      "total": 750.00,
      "observaciones": null,
      "creado_por": "Gabriel Alegría",
      "items": [
        {
          "id": 45,
          "producto_id": 1,
          "codigo": "ACEITE-001",
          "nombre": "Aceite Motor 5W30",
          "unidad_medida": "LT",
          "variante": "5W30 Galon",
          "cantidad": 10,
          "cantidad_recibida": 10,
          "precio_unitario": 50.00,
          "subtotal": 500.00,
          "completado": true
        },
        {
          "id": 46,
          "producto_id": 3,
          "codigo": "FILT-003",
          "nombre": "Filtro de Aceite",
          "unidad_medida": "UND",
          "variante": null,
          "cantidad": 5,
          "cantidad_recibida": 5,
          "precio_unitario": 50.00,
          "subtotal": 250.00,
          "completado": true
        }
      ]
    }
  ],
  "total": 1,
  "periodo": {
    "from": "2024-06-01",
    "to": "2024-06-30"
  }
}
```

### Campos de la Respuesta

**Orden:**

| Campo | Descripción |
|-------|-------------|
| `id` | ID interno de la orden |
| `numero_orden` | Número de la orden (ej: `PED-20240614-0001`) |
| `fecha_orden` | Fecha en que se creó la orden |
| `fecha_esperada` | Fecha de entrega pactada con el proveedor |
| `fecha_entrega` | Fecha real de entrega (null si no ha llegado) |
| `estado` | Estado actual de la orden |
| `proveedor` | Datos del proveedor |
| `subtotal` | Suma de items sin impuesto |
| `monto_impuesto` | Monto de impuesto |
| `total` | Total final |
| `creado_por` | Nombre del usuario que creó la orden |

**Items:**

| Campo | Descripción |
|-------|-------------|
| `producto_id` | ID del producto |
| `codigo` | Código SKU del producto |
| `nombre` | Nombre del producto |
| `unidad_medida` | Unidad (LT, UND, KG, etc.) |
| `variante` | Viscosidad + presentación (solo aceites con variante, sino `null`) |
| `cantidad` | Cantidad ordenada |
| `cantidad_recibida` | Cantidad físicamente recibida |
| `precio_unitario` | Precio de compra unitario |
| `subtotal` | `cantidad × precio_unitario` |
| `completado` | `true` si `cantidad_recibida >= cantidad` |

---

## GET /api/compras/kardex/{id}

Devuelve el detalle completo de una sola orden de compra.

### Parámetros

| Parámetro | Tipo | Descripción |
|-----------|------|-------------|
| `id` | integer | ID de la orden de compra |

### Ejemplo de Request

```bash
curl -X GET "http://localhost/api/compras/kardex/15" \
  -H "Authorization: Bearer TOKEN"
```

### Respuesta (200)

```json
{
  "data": {
    "id": 15,
    "numero_orden": "PED-20240614-0001",
    "fecha_orden": "2024-06-14",
    "fecha_esperada": "2024-06-21",
    "fecha_entrega": "2024-06-19",
    "estado": "completado",
    "proveedor": {
      "id": 1,
      "codigo": "PROV-001",
      "nombre": "Distribuidora ABC",
      "telefono": "2234-5678",
      "email": "contacto@distribuidora.com"
    },
    "subtotal": 750.00,
    "monto_impuesto": 0.00,
    "total": 750.00,
    "observaciones": null,
    "creado_por": "Gabriel Alegría",
    "items": [...]
  }
}
```

### Respuesta (404)

```json
{
  "message": "No query results for model [App\\Models\\Pedido] 999"
}
```

---

## Estados de las Órdenes

| Estado | Significado |
|--------|-------------|
| `pendiente` | Orden creada, no confirmada con proveedor |
| `confirmado` | Proveedor confirmó la orden |
| `en_camino` | Mercadería en tránsito |
| `parcial` | Recepción parcial de productos |
| `completado` | Todos los productos recibidos ✅ |
| `cancelado` | Orden cancelada ❌ |

> Para el Kardex, normalmente solo interesan las órdenes con estado `completado`.

---

## Casos de Uso para Kardex

### 1. Obtener todas las órdenes completadas del mes

```bash
GET /api/compras/kardex?from=2024-06-01&to=2024-06-30&estado=completado
```

### 2. Órdenes de un proveedor específico

```bash
GET /api/compras/kardex?from=2024-06-01&to=2024-06-30&proveedor_id=1
```

### 3. Ver una orden puntual

```bash
GET /api/compras/kardex/15
```

---

## Errores Comunes

### 422 — Fechas faltantes

```json
{
  "errors": {
    "from": ["El campo from es obligatorio."],
    "to": ["El campo to es obligatorio."]
  }
}
```

### 422 — Rango inválido

```json
{
  "errors": {
    "to": ["El campo to debe ser una fecha posterior o igual a from."]
  }
}
```

### 401 — Sin token

```json
{
  "message": "Unauthenticated."
}
```

---

## Archivos del Sistema

| Archivo | Descripción |
|---------|-------------|
| `app/Http/Controllers/Api/ComprasKardexController.php` | Controller del endpoint |
| `app/Models/Pedido.php` | Modelo de órdenes de compra |
| `app/Models/PedidoDetalle.php` | Modelo de items de la orden |
| `routes/api.php` | Definición de rutas |
| `docs/openapi.yaml` | Swagger/OpenAPI actualizado |

---

**Versión:** 1.0  
**Fecha:** 2024-06-14  
**Autor:** Gabriel Alegría
