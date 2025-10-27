<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\MHApiService;
use App\Models\Factura;
use Illuminate\Support\Facades\Log;

class ConsultaController extends Controller
{
    protected $mhApiService;

    public function __construct(MHApiService $mhApiService)
    {
        $this->mhApiService = $mhApiService;
    }

    /**
     * Mostrar formulario de consulta de DTE
     */
    public function index()
    {
        return view('consultas.index');
    }

    /**
     * Consultar DTE en el Ministerio de Hacienda
     */
    public function consultarDTE(Request $request)
    {
        $validated = $request->validate([
            'nit_emisor' => 'required|string|max:14',
            'tipo_dte' => 'required|string|in:01,03,05,06,07,08,09,11,14,15',
            'codigo_generacion' => 'required|string|size:36',
        ]);

        // Limpiar NIT (quitar guiones)
        $nitEmisor = str_replace('-', '', $validated['nit_emisor']);

        Log::info('Consultando DTE en MH', [
            'nit_emisor' => $nitEmisor,
            'tipo_dte' => $validated['tipo_dte'],
            'codigo_generacion' => $validated['codigo_generacion'],
        ]);

        $resultado = $this->mhApiService->consultarDTE(
            $nitEmisor,
            $validated['tipo_dte'],
            $validated['codigo_generacion']
        );

        if ($resultado['success']) {
            return view('consultas.resultado_dte', [
                'resultado' => $resultado,
                'parametros' => $validated,
            ]);
        } else {
            // Mostrar error detallado
            $errorDetallado = $resultado['message'];

            if (isset($resultado['status'])) {
                $errorDetallado .= ' (HTTP ' . $resultado['status'] . ')';
            }

            if (isset($resultado['raw_body'])) {
                $errorDetallado .= ' - Respuesta: ' . $resultado['raw_body'];
            }

            Log::error('Error en consulta DTE desde controlador', [
                'parametros' => $validated,
                'resultado_completo' => $resultado,
            ]);

            return back()
                ->withInput()
                ->with('error', $errorDetallado)
                ->with('error_detalle', $resultado);
        }
    }

    /**
     * Buscar DTE local y consultar en MH
     */
    public function buscarYConsultar(Request $request)
    {
        $validated = $request->validate([
            'factura_id' => 'required|exists:facturas,id',
        ]);

        $factura = Factura::findOrFail($validated['factura_id']);

        // Obtener NIT del emisor desde la configuración
        $nitEmisor = config('dte.nit');

        Log::info('Consultando DTE local en MH', [
            'factura_id' => $factura->id,
            'numero_control' => $factura->numero_control,
            'codigo_generacion' => $factura->codigo_generacion,
        ]);

        $resultado = $this->mhApiService->consultarDTE(
            $nitEmisor,
            $factura->tipo_dte,
            $factura->codigo_generacion
        );

        if ($resultado['success']) {
            return view('consultas.resultado_dte', [
                'resultado' => $resultado,
                'factura' => $factura,
                'parametros' => [
                    'nit_emisor' => $nitEmisor,
                    'tipo_dte' => $factura->tipo_dte,
                    'codigo_generacion' => $factura->codigo_generacion,
                ],
            ]);
        } else {
            // Mostrar error detallado
            $errorDetallado = $resultado['message'];

            if (isset($resultado['status'])) {
                $errorDetallado .= ' (HTTP ' . $resultado['status'] . ')';
            }

            if (isset($resultado['raw_body'])) {
                $errorDetallado .= ' - Respuesta: ' . $resultado['raw_body'];
            }

            Log::error('Error en consulta DTE desde factura local', [
                'factura_id' => $factura->id,
                'numero_control' => $factura->numero_control,
                'resultado_completo' => $resultado,
            ]);

            return back()
                ->with('error', $errorDetallado)
                ->with('error_detalle', $resultado);
        }
    }

    /**
     * Consultar lote de DTEs en el Ministerio de Hacienda
     */
    public function consultarLote(Request $request)
    {
        $validated = $request->validate([
            'codigo_lote' => 'required|string|max:100',
        ]);

        Log::info('Consultando Lote DTE en MH', [
            'codigo_lote' => $validated['codigo_lote'],
        ]);

        $resultado = $this->mhApiService->consultarDTELote($validated['codigo_lote']);

        if ($resultado['success']) {
            return view('consultas.resultado_lote', [
                'resultado' => $resultado,
                'codigo_lote' => $validated['codigo_lote'],
            ]);
        } else {
            // Mostrar error detallado
            $errorDetallado = $resultado['message'];

            if (isset($resultado['status'])) {
                $errorDetallado .= ' (HTTP ' . $resultado['status'] . ')';
            }

            if (isset($resultado['raw_body'])) {
                $errorDetallado .= ' - Respuesta: ' . $resultado['raw_body'];
            }

            Log::error('Error en consulta Lote', [
                'codigo_lote' => $validated['codigo_lote'],
                'resultado_completo' => $resultado,
            ]);

            return back()
                ->withInput()
                ->with('error', $errorDetallado)
                ->with('error_detalle', $resultado);
        }
    }

    /**
     * Mostrar formulario de consulta rápida desde una factura
     */
    public function consultaRapida($facturaId)
    {
        $factura = Factura::findOrFail($facturaId);

        return view('consultas.rapida', compact('factura'));
    }
}
