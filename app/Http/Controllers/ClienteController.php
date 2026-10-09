<?php

namespace App\Http\Controllers;

use App\Models\Factura;
use Illuminate\Http\Request;

class ClienteController extends Controller
{
    private const ESTADOS_VALIDOS = ['enviado_mh', 'firmado', 'firmado_contingencia'];

    /**
     * Listado de clientes con el total consumido, agrupados por número de documento.
     */
    public function index(Request $request)
    {
        $query = Factura::whereIn('estado', self::ESTADOS_VALIDOS)
            ->whereNotNull('receptor_num_documento');

        if ($request->filled('buscar')) {
            $buscar = $request->input('buscar');
            $query->where(function ($q) use ($buscar) {
                $q->where('receptor_nombre', 'LIKE', "%{$buscar}%")
                  ->orWhere('receptor_num_documento', 'LIKE', "%{$buscar}%");
            });
        }

        $clientes = $query->selectRaw('
                receptor_num_documento,
                MAX(receptor_nombre) as receptor_nombre,
                MAX(receptor_correo) as receptor_correo,
                MAX(receptor_telefono) as receptor_telefono,
                COUNT(*) as total_documentos,
                SUM(total_pagar) as total_consumido,
                MAX(fecha_emision) as ultima_compra
            ')
            ->groupBy('receptor_num_documento')
            ->orderByDesc('total_consumido')
            ->paginate(20)
            ->withQueryString();

        // Consumo de "Clientes Varios" (sin documento), agrupado aparte
        $totalClientesVarios = Factura::whereIn('estado', self::ESTADOS_VALIDOS)
            ->whereNull('receptor_num_documento')
            ->selectRaw('COUNT(*) as total_documentos, SUM(total_pagar) as total_consumido')
            ->first();

        return view('clientes.index', compact('clientes', 'totalClientesVarios'));
    }

    /**
     * Detalle de consumo de un cliente: todos sus documentos y, dentro de
     * cada uno, el detalle línea por línea de productos/servicios.
     */
    public function show(Request $request, string $documento)
    {
        $facturas = Factura::with('items')
            ->where('receptor_num_documento', $documento)
            ->whereIn('estado', self::ESTADOS_VALIDOS)
            ->orderByDesc('fecha_emision')
            ->get();

        if ($facturas->isEmpty()) {
            abort(404, 'Cliente no encontrado o sin documentos emitidos.');
        }

        $primera = $facturas->first();

        $totalConsumido = $facturas->sum('total_pagar');
        $totalDocumentos = $facturas->count();

        // Agrupar el consumo por producto/servicio (código + descripción)
        $productos = [];
        foreach ($facturas as $factura) {
            foreach ($factura->items as $item) {
                $clave = $item->codigo . '|' . $item->descripcion;
                if (!isset($productos[$clave])) {
                    $productos[$clave] = [
                        'codigo' => $item->codigo,
                        'descripcion' => $item->descripcion,
                        'cantidad_total' => 0,
                        'monto_total' => 0,
                        'veces_comprado' => 0,
                    ];
                }
                $productos[$clave]['cantidad_total'] += (float) $item->cantidad;
                $productos[$clave]['monto_total'] += (float) $item->venta_gravada + (float) $item->venta_exenta + (float) $item->venta_no_sujeta;
                $productos[$clave]['veces_comprado']++;
            }
        }
        // Ordenar por monto gastado en ese producto, de mayor a menor
        usort($productos, fn($a, $b) => $b['monto_total'] <=> $a['monto_total']);

        return view('clientes.show', compact(
            'documento', 'primera', 'facturas', 'totalConsumido', 'totalDocumentos', 'productos'
        ));
    }
}
