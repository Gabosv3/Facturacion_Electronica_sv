<?php

namespace App\Services;

use App\Models\Factura;
use App\Models\FacturaItem;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class KardexService
{
    private string $apiUrl;

    public function __construct()
    {
        $this->apiUrl = config('services.motor_api.url');
    }

    /**
     * Obtener entradas (compras) desde la API de Motors González
     */
    public function obtenerEntradas(string $from, string $to, ?string $proveedorId = null): array
    {
        $token = Session::get('auth_token');

        if (!$token) {
            Log::warning('KardexService: No hay token de sesión para consultar compras');
            return [];
        }

        try {
            $params = ['from' => $from, 'to' => $to, 'estado' => 'completado'];
            if ($proveedorId) {
                $params['proveedor_id'] = $proveedorId;
            }

            $response = Http::timeout(15)
                ->withoutVerifying()
                ->withToken($token)
                ->get($this->apiUrl . '/compras/kardex', $params);

            // Si la respuesta es HTML, el token expiró y redirigió al login
            $contentType = $response->header('Content-Type') ?? '';
            if (str_contains($contentType, 'text/html') || str_starts_with(trim($response->body()), '<!DOCTYPE')) {
                return ['__error' => 'SESSION_EXPIRED'];
            }

            if ($response->successful()) {
                $data = $response->json();
                return $data['data'] ?? [];
            }

            Log::warning('KardexService: Error al obtener entradas', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);

            return ['__error' => 'HTTP ' . $response->status()];
        } catch (\Exception $e) {
            Log::error('KardexService: Excepción al obtener entradas', ['error' => $e->getMessage()]);
            return ['__error' => $e->getMessage()];
        }
    }

    /**
     * Obtener salidas (ventas facturadas) desde la base de datos local
     */
    public function obtenerSalidas(string $from, string $to, ?string $codigoProducto = null): array
    {
        $query = FacturaItem::with('factura')
            ->whereHas('factura', function ($q) use ($from, $to) {
                $q->whereBetween('fecha_emision', [$from, $to])
                  ->whereIn('estado', ['enviado_mh', 'firmado', 'firmado_contingencia']);
            });

        if ($codigoProducto) {
            $query->where('codigo', 'like', "%{$codigoProducto}%");
        }

        return $query->orderBy('created_at')->get()->map(function ($item) {
            return [
                'tipo'              => 'salida',
                'fecha'             => $item->factura->fecha_emision,
                'referencia'        => $item->factura->numero_control,
                'codigo_generacion' => $item->factura->codigo_generacion,
                'tipo_documento'    => 'DTE-' . $item->factura->tipo_dte,
                'cliente'           => $item->factura->receptor_nombre,
                'codigo'            => $item->codigo,
                'descripcion'       => $item->descripcion,
                'unidad_medida'     => $item->uni_medida,
                'es_servicio'       => (int) $item->uni_medida === 99,
                'cantidad'          => $item->cantidad,
                'precio_unitario'   => $item->precio_unitario,
                'total'             => $item->venta_gravada + $item->venta_exenta + $item->venta_no_sujeta,
                'factura_id'        => $item->factura_id,
            ];
        })->toArray();
    }

    /**
     * Construir movimientos unificados del kardex (entradas + salidas) por producto
     */
    public string $errorEntradas = '';

    public function construirKardex(string $from, string $to, ?string $codigoProducto = null, ?string $proveedorId = null, string $tipoItem = 'ambos'): array
    {
        $entradas = $this->obtenerEntradas($from, $to, $proveedorId);

        if (isset($entradas['__error'])) {
            $this->errorEntradas = $entradas['__error'];
            $entradas = [];
        }

        $salidas  = $this->obtenerSalidas($from, $to, $codigoProducto);

        $movimientos = [];

        // Procesar entradas
        foreach ($entradas as $orden) {
            foreach ($orden['items'] as $item) {
                if ($codigoProducto && stripos($item['codigo'], $codigoProducto) === false) {
                    continue;
                }
                $cantidad = (float) ($item['cantidad_recibida'] > 0 ? $item['cantidad_recibida'] : $item['cantidad']);
                $precio   = (float) $item['precio_unitario'];
                $movimientos[] = [
                    'tipo'            => 'entrada',
                    'fecha'           => isset($orden['fecha_entrega']) && $orden['fecha_entrega']
                                            ? substr($orden['fecha_entrega'], 0, 10)
                                            : substr($orden['fecha_orden'], 0, 10),
                    'referencia'      => $orden['numero_orden'],
                    'tipo_documento'  => 'Compra',
                    'proveedor'       => $orden['proveedor']['nombre'] ?? '-',
                    'codigo'          => $item['codigo'],
                    'descripcion'     => $item['nombre'] . ($item['variante'] ? ' - ' . $item['variante'] : ''),
                    'unidad_medida'   => $item['unidad_medida'],
                    'cantidad'          => $cantidad,
                    'precio_unitario'   => $precio,
                    'total'             => round($cantidad * $precio, 2),
                    'es_servicio'       => false,
                    'codigo_generacion' => null,
                ];
            }
        }

        // Agregar salidas
        foreach ($salidas as $salida) {
            $movimientos[] = $salida;
        }

        // Aplicar filtro tipo_item
        if ($tipoItem === 'producto') {
            $movimientos = array_filter($movimientos, fn($m) => !($m['es_servicio'] ?? false));
        } elseif ($tipoItem === 'servicio') {
            $movimientos = array_values(array_filter($movimientos, fn($m) => $m['es_servicio'] ?? false));
        }

        // Ordenar: productos primero (por código), servicios al final; dentro de cada grupo por fecha
        usort($movimientos, function ($a, $b) {
            $aServicio = $a['es_servicio'] ?? false;
            $bServicio = $b['es_servicio'] ?? false;

            if ($aServicio !== $bServicio) {
                return $aServicio ? 1 : -1;
            }

            $porCodigo = strcmp($a['codigo'], $b['codigo']);
            if ($porCodigo !== 0) {
                return $porCodigo;
            }

            return strcmp($a['fecha'], $b['fecha']);
        });

        return $movimientos;
    }

    /**
     * Agrupar movimientos por código de producto para resumen
     */
    public function resumenPorProducto(array $movimientos): array
    {
        $productos = [];

        foreach ($movimientos as $mov) {
            $codigo = $mov['codigo'];
            if (!isset($productos[$codigo])) {
                $productos[$codigo] = [
                    'codigo'      => $codigo,
                    'descripcion' => $mov['descripcion'],
                    'entradas'    => 0,
                    'salidas'     => 0,
                    'total_compras'  => 0,
                    'total_ventas'   => 0,
                ];
            }
            if ($mov['tipo'] === 'entrada') {
                $productos[$codigo]['entradas']      += $mov['cantidad'];
                $productos[$codigo]['total_compras'] += $mov['total'];
            } else {
                $productos[$codigo]['salidas']      += $mov['cantidad'];
                $productos[$codigo]['total_ventas'] += $mov['total'];
            }
        }

        return array_values($productos);
    }
}
