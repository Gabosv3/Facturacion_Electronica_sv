<?php

namespace App\Http\Controllers;

use App\Services\KardexService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;

class KardexController extends Controller
{
    protected KardexService $kardexService;

    public function __construct(KardexService $kardexService)
    {
        $this->kardexService = $kardexService;
    }

    public function index(Request $request)
    {
        $from            = $request->input('from', now()->subYear()->toDateString());
        $to              = $request->input('to', now()->toDateString());
        $codigoProducto  = $request->input('codigo_producto');
        $proveedorId     = $request->input('proveedor_id');
        $tipoItem        = $request->input('tipo_item', 'ambos'); // ambos | producto | servicio
        $vista           = $request->input('vista', 'movimientos');

        $movimientos    = $this->kardexService->construirKardex($from, $to, $codigoProducto, $proveedorId, $tipoItem);
        $errorEntradas  = $this->kardexService->errorEntradas;
        $resumen        = $this->kardexService->resumenPorProducto($movimientos);

        $totalEntradas = collect($movimientos)->where('tipo', 'entrada')->sum('total');
        $totalSalidas  = collect($movimientos)->where('tipo', 'salida')->sum('total');

        return view('kardex.index', compact(
            'movimientos', 'resumen', 'from', 'to',
            'codigoProducto', 'proveedorId', 'tipoItem', 'vista',
            'totalEntradas', 'totalSalidas', 'errorEntradas'
        ));
    }

    public function debugApi()
    {
        $token  = Session::get('auth_token');
        $apiUrl = config('services.motor_api.url') . '/compras/kardex';
        $from   = now()->subYear()->toDateString();
        $to     = now()->toDateString();

        $resultado = [
            'token_en_sesion' => $token ? substr($token, 0, 10) . '...' : 'NO HAY TOKEN',
            'url_llamada'     => $apiUrl . '?from=' . $from . '&to=' . $to . '&estado=completado',
        ];

        if (!$token) {
            return response()->json($resultado);
        }

        try {
            $response = Http::timeout(15)
                ->withoutVerifying()
                ->withToken($token)
                ->get($apiUrl, ['from' => $from, 'to' => $to, 'estado' => 'completado']);

            $resultado['http_status'] = $response->status();
            $resultado['respuesta']   = $response->json() ?? $response->body();
        } catch (\Exception $e) {
            $resultado['excepcion'] = $e->getMessage();
        }

        return response()->json($resultado, 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    public function exportar(Request $request)
    {
        $from           = $request->input('from', now()->startOfMonth()->toDateString());
        $to             = $request->input('to', now()->toDateString());
        $codigoProducto = $request->input('codigo_producto');
        $proveedorId    = $request->input('proveedor_id');

        $movimientos = $this->kardexService->construirKardex($from, $to, $codigoProducto, $proveedorId);

        $filename = 'kardex_' . $from . '_' . $to . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($movimientos) {
            $output = fopen('php://output', 'w');
            // BOM para Excel
            fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($output, [
                'Tipo', 'Fecha', 'Referencia', 'Código Generación', 'Tipo Documento',
                'Proveedor/Cliente', 'Código Producto', 'Descripción',
                'Unidad', 'Cantidad', 'Precio Unitario', 'Total', 'Es Servicio',
            ], ';');

            foreach ($movimientos as $mov) {
                fputcsv($output, [
                    strtoupper($mov['tipo']),
                    $mov['fecha'],
                    $mov['referencia'],
                    $mov['codigo_generacion'] ?? '',
                    $mov['tipo_documento'],
                    $mov['proveedor'] ?? $mov['cliente'] ?? '-',
                    $mov['codigo'],
                    $mov['descripcion'],
                    $mov['unidad_medida'],
                    number_format($mov['cantidad'], 2, '.', ''),
                    number_format($mov['precio_unitario'], 2, '.', ''),
                    number_format($mov['total'], 2, '.', ''),
                    ($mov['es_servicio'] ?? false) ? 'Sí' : 'No',
                ], ';');
            }

            fclose($output);
        };

        return response()->stream($callback, 200, $headers);
    }
}
