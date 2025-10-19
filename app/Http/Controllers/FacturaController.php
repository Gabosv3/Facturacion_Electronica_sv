<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Factura;
use App\Services\DTEService;
use App\Services\MHApiService;

class FacturaController extends Controller
{
    protected $dteService;
    protected $mhApiService;

    public function __construct(DTEService $dteService, MHApiService $mhApiService)
    {
        $this->dteService = $dteService;
        $this->mhApiService = $mhApiService;
    }

    /**
     * Mostrar formulario de facturación
     */
    public function create()
    {
        return view('facturas.crear');
    }

    /**
     * Guardar y firmar factura
     */
    public function store(Request $request)
    {
        // Validar datos
        $validated = $request->validate([
            // Receptor
            'receptor_tipo_documento' => 'required|string|size:2',
            'receptor_num_documento' => 'required|string|max:20',
            'receptor_nombre' => 'required|string|max:255',
            'receptor_nrc' => 'nullable|string|max:20',
            'receptor_cod_actividad' => 'nullable|string|max:10',
            'receptor_desc_actividad' => 'required|string|min:5|max:150',
            'receptor_departamento' => 'required|string|size:2',
            'receptor_municipio' => 'required|string|size:2',
            'receptor_distrito' => 'nullable|string|size:2',
            'receptor_complemento' => 'required|string',
            'receptor_telefono' => 'nullable|string|max:20',
            'receptor_correo' => 'nullable|email',
            
            // Items
            'items' => 'required|array|min:1',
            'items.*.codigo' => 'required|string|max:50',
            'items.*.descripcion' => 'required|string',
            'items.*.cantidad' => 'required|numeric|min:0.01',
            'items.*.precio_unitario' => 'required|numeric|min:0',
            'items.*.monto_descuento' => 'nullable|numeric|min:0',
            'items.*.uni_medida' => 'nullable|integer',
            
            // Otros
            'condicion_operacion' => 'required|integer|in:1,2,3',
            'forma_pago' => 'required|string|size:2',
            'referencia_pago' => 'nullable|string',
            'observaciones' => 'nullable|string',
            'precio_incluye_iva' => 'nullable|boolean',
        ]);

        // Asegurar que los campos opcionales tengan valores por defecto
        $validated = array_merge([
            'receptor_nrc' => null,
            'receptor_cod_actividad' => null,
            'receptor_telefono' => null,
            'receptor_correo' => null,
            'referencia_pago' => null,
            'observaciones' => null,
            'precio_incluye_iva' => true,
        ], $validated);

        try {
            DB::beginTransaction();

            // Preparar datos para el DTE
            $datosFactura = [
                'tipo_dte' => '01',
           'receptor' => [
    'tipo_documento' => $validated['receptor_tipo_documento'],
    'num_documento' => $validated['receptor_num_documento'],
    'nombre' => $validated['receptor_nombre'],
    'nrc' => $validated['receptor_nrc'],
    'cod_actividad' => $validated['receptor_cod_actividad'],
    'desc_actividad' => $validated['receptor_desc_actividad'],
    'departamento' => $validated['receptor_departamento'],
    'municipio' => $validated['receptor_municipio'],
    'distrito' => $validated['receptor_distrito'], // ← DEBE ESTAR AQUÍ
    'complemento' => $validated['receptor_complemento'],
    'telefono' => $validated['receptor_telefono'],
    'correo' => $validated['receptor_correo'],
],
                'items' => $validated['items'],
                'condicion_operacion' => $validated['condicion_operacion'],
                'forma_pago' => $validated['forma_pago'],
                'referencia_pago' => $validated['referencia_pago'],
                'observaciones' => $validated['observaciones'],
            ];

            // Generar DTE completo
            $dteJson = $this->dteService->construirDteJson($datosFactura, $validated['precio_incluye_iva']);
            
            // Guardar factura en BD
            $factura = Factura::create([
                'numero_control' => $dteJson['dteJson']['identificacion']['numeroControl'],
                'codigo_generacion' => $dteJson['dteJson']['identificacion']['codigoGeneracion'],
                'tipo_dte' => '01',
                'fecha_emision' => $dteJson['dteJson']['identificacion']['fecEmi'],
                'hora_emision' => $dteJson['dteJson']['identificacion']['horEmi'],
                'receptor_tipo_documento' => $validated['receptor_tipo_documento'],
                'receptor_num_documento' => $validated['receptor_num_documento'],
                'receptor_nombre' => $validated['receptor_nombre'],
                'receptor_nrc' => $validated['receptor_nrc'],
                'receptor_actividad' => $validated['receptor_cod_actividad'],
                'receptor_direccion' => json_encode($dteJson['dteJson']['receptor']['direccion']),
                'receptor_telefono' => $validated['receptor_telefono'],
                'receptor_correo' => $validated['receptor_correo'],
                'total_gravada' => $dteJson['dteJson']['resumen']['totalGravada'],
                'total_exenta' => $dteJson['dteJson']['resumen']['totalExenta'],
                'total_no_sujeto' => $dteJson['dteJson']['resumen']['totalNoSuj'],
                'total_descuento' => $dteJson['dteJson']['resumen']['totalDescu'],
                'sub_total' => $dteJson['dteJson']['resumen']['subTotal'],
                'total_iva' => $dteJson['dteJson']['resumen']['totalIva'],
                'total_pagar' => $dteJson['dteJson']['resumen']['totalPagar'],
                'total_letras' => $dteJson['dteJson']['resumen']['totalLetras'],
                'condicion_operacion' => $validated['condicion_operacion'],
                'dte_json' => $dteJson,
                'estado' => 'pendiente',
            ]);

            // Guardar items
            foreach ($dteJson['dteJson']['cuerpoDocumento'] as $item) {
                $factura->items()->create([
                    'num_item' => $item['numItem'],
                    'codigo' => $item['codigo'],
                    'descripcion' => $item['descripcion'],
                    'cantidad' => $item['cantidad'],
                    'uni_medida' => $item['uniMedida'],
                    'precio_unitario' => $item['precioUni'],
                    'monto_descuento' => $item['montoDescu'],
                    'venta_gravada' => $item['ventaGravada'],
                    'venta_exenta' => $item['ventaExenta'],
                    'venta_no_sujeta' => $item['ventaNoSuj'],
                    'iva_item' => $item['ivaItem'],
                ]);
            }

            // Enviar a firmar al API local
            $apiUrl = config('dte.api_url');
            
            Log::info('🔐 Enviando DTE a firmar', [
                'url' => $apiUrl . '/firmardocumento/',
                'factura_id' => $factura->id,
            ]);
            
            $response = Http::timeout(30)->post($apiUrl . '/firmardocumento/', $dteJson);

            Log::info('📨 Respuesta de API de firma', [
                'status' => $response->status(),
                'successful' => $response->successful(),
                'tiene_body' => $response->json() !== null,
                'response_keys' => $response->successful() ? array_keys($response->json()) : [],
            ]);

            if ($response->successful()) {
                $responseData = $response->json();
                $dteFirmado = $responseData['body'] ?? null;

                Log::info('✅ Extrayendo JWT firmado', [
                    'response_data_keys' => array_keys($responseData),
                    'tiene_body' => isset($responseData['body']),
                    'body_vacio' => empty($responseData['body']),
                    'body_length' => isset($responseData['body']) ? strlen($responseData['body']) : 0,
                    'body_primeros_50' => $dteFirmado ? substr($dteFirmado, 0, 50) : 'NULL',
                ]);

                if (!$dteFirmado) {
                    Log::error('❌ NO SE RECIBIÓ DOCUMENTO FIRMADO', [
                        'response_completa' => $responseData
                    ]);
                    throw new \Exception('No se recibió el documento firmado');
                }

                Log::info('DTE firmado recibido', [
                    'longitud_jwt' => strlen($dteFirmado),
                    'factura_id' => $factura->id,
                    'inicia_con' => substr($dteFirmado, 0, 20),
                ]);

                // Guardar estado inicial como firmado
                $factura->update([
                    'estado' => 'firmado',
                    'respuesta_mh' => json_encode([
                        'firma' => 'Documento firmado exitosamente',
                        'jwt_guardado' => substr($dteFirmado, 0, 100) . '...',
                        'timestamp' => now()->toDateTimeString(),
                    ]),
                ]);

                // IMPORTANTE: Asegurar que el JWT se pase como string, no como array
                $jwtString = is_string($dteFirmado) ? $dteFirmado : json_encode($dteFirmado);
                
                Log::info('🚀 Preparando envío al MH', [
                    'jwt_es_string' => is_string($jwtString),
                    'jwt_length' => strlen($jwtString),
                    'jwt_primeros_30' => substr($jwtString, 0, 30),
                ]);

                // Enviar al Ministerio de Hacienda
                $resultadoMH = $this->mhApiService->enviarDTE($jwtString, [
                    'ambiente' => config('dte.ambiente'),
                    'id_envio' => $factura->id,
                    'version' => 2,
                    'tipo_dte' => '01',
                ]);

                if ($resultadoMH['success']) {
                    // Actualizar con respuesta del MH
                    $factura->update([
                        'estado' => 'enviado_mh',
                        'respuesta_mh' => json_encode([
                            'firma' => 'Documento firmado exitosamente',
                            'envio_mh' => $resultadoMH['data'] ?? $resultadoMH['response'],
                            'timestamp' => now()->toDateTimeString(),
                        ]),
                    ]);

                    DB::commit();

                    return redirect()->route('facturas.show', $factura->id)
                        ->with('success', 'Factura generada, firmada y enviada al Ministerio de Hacienda exitosamente');
                } else {
                    // Aunque el MH falle, la factura está firmada
                    $factura->update([
                        'estado' => 'firmado_sin_enviar',
                        'respuesta_mh' => json_encode([
                            'firma' => 'Documento firmado exitosamente',
                            'error_mh' => $resultadoMH['message'],
                            'timestamp' => now()->toDateTimeString(),
                        ]),
                    ]);

                    DB::commit();

                    return redirect()->route('facturas.show', $factura->id)
                        ->with('warning', 'Factura firmada pero no se pudo enviar al MH: ' . $resultadoMH['message']);
                }

            } else {
                $factura->update([
                    'estado' => 'error_firma',
                    'respuesta_mh' => json_encode([
                        'error' => 'Error al firmar documento',
                        'detalle' => $response->body(),
                    ]),
                ]);

                DB::commit();

                return back()->with('error', 'Error al firmar la factura: ' . $response->body())
                    ->withInput();
            }

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al procesar factura: ' . $e->getMessage());
            return back()->with('error', 'Error al procesar la factura: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Mostrar detalle de factura
     */
    public function show(Factura $factura)
    {
        $factura->load('items');
        return view('facturas.detalle', compact('factura'));
    }

    /**
     * Listar facturas
     */
    public function index()
    {
        $facturas = Factura::orderBy('created_at', 'desc')->paginate(20);
        return view('facturas.index', compact('facturas'));
    }
}