<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Factura;
use App\Services\DTEService;
use App\Services\MHApiService;
use App\Services\ContingenciaService;

class FacturaController extends Controller
{
    protected $dteService;
    protected $mhApiService;
    protected $contingenciaService;

    public function __construct(
        DTEService $dteService,
        MHApiService $mhApiService,
        ContingenciaService $contingenciaService
    ) {
        $this->dteService = $dteService;
        $this->mhApiService = $mhApiService;
        $this->contingenciaService = $contingenciaService;
    }

    /**
     * Mostrar formulario de facturación (DTE-01)
     */
    public function create()
    {
        return view('facturas.crear');
    }

    /**
     * Mostrar formulario de Comprobante de Crédito Fiscal (DTE-03)
     */
    public function createCCF()
    {
        return view('facturas.crear_ccf');
    }

    /**
     * Guardar y firmar factura (DTE-01 o DTE-03)
     */
    public function store(Request $request)
    {
        // Determinar tipo de DTE
        $tipoDte = $request->input('tipo_dte', '01');

        // Validaciones base
        $rules = [
            // Tipo DTE
            'tipo_dte' => 'required|string|in:01,03',

            // Receptor
            'receptor_tipo_documento' => 'required|string|size:2',
            'receptor_num_documento' => 'required|string|max:20',
            'receptor_nombre' => 'required|string|max:255',
            'receptor_nrc' => $tipoDte === '03' ? 'required|string|max:20' : 'nullable|string|max:20',
            'receptor_cod_actividad' => $tipoDte === '03' ? 'required|string|max:10' : 'nullable|string|max:10',
            'receptor_desc_actividad' => 'required|string|min:5|max:150',
            'receptor_departamento' => 'required|string|size:2',
            'receptor_municipio' => 'required|string|size:2',
            'receptor_distrito' => 'required|string|size:2',
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
        ];

        // Validación especial para CCF (DTE-03): solo acepta NIT
        if ($tipoDte === '03') {
            $rules['receptor_tipo_documento'] .= '|in:36'; // Solo NIT para CCF
        }

        // Validar datos
        $validated = $request->validate($rules);

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
                'tipo_dte' => $validated['tipo_dte'],
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

            // 🔍 LOG: Ver DTE JSON completo generado
            Log::info('📋 DTE JSON COMPLETO GENERADO', [
                'tipo_dte' => $validated['tipo_dte'],
                'dte_completo' => json_encode($dteJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
                'receptor_completo' => $dteJson['dteJson']['receptor'],
                'receptor_nit_enviado' => $dteJson['dteJson']['receptor']['nit'] ?? $dteJson['dteJson']['receptor']['numDocumento'] ?? 'NO ENCONTRADO',
                'tipo_documento_receptor' => $dteJson['dteJson']['receptor']['tipoDocumento'] ?? 'NO ENCONTRADO',
            ]);

            // Obtener totalIva (está en diferentes lugares según el tipo de DTE)
            $totalIva = $validated['tipo_dte'] === '03'
                ? ($dteJson['dteJson']['resumen']['totalIva'] ?? 0)  // CCF: guardado para BD
                : $dteJson['dteJson']['resumen']['totalIva'];         // Factura: está directo

            // Guardar factura en BD
            $factura = Factura::create([
                'numero_control' => $dteJson['dteJson']['identificacion']['numeroControl'],
                'codigo_generacion' => $dteJson['dteJson']['identificacion']['codigoGeneracion'],
                'tipo_dte' => $validated['tipo_dte'],
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
                'total_iva' => $totalIva,
                'total_pagar' => $dteJson['dteJson']['resumen']['totalPagar'],
                'total_letras' => $dteJson['dteJson']['resumen']['totalLetras'],
                'condicion_operacion' => $validated['condicion_operacion'],
                'dte_json' => $dteJson,
                'estado' => 'pendiente',
            ]);

            // Guardar items
            foreach ($dteJson['dteJson']['cuerpoDocumento'] as $item) {
                // Calcular IVA para guardar en BD (en CCF no viene en ivaItem)
                $ivaItem = $validated['tipo_dte'] === '03'
                    ? round($item['ventaGravada'] * 0.13, 2)
                    : $item['ivaItem'];

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
                    'iva_item' => $ivaItem,
                ]);
            }

            // Si es CCF, limpiar campos que no deben ir al MH
            $dteParaFirmar = $dteJson;
            if ($validated['tipo_dte'] === '03') {
                // Remover totalIva del resumen (solo va en tributos para CCF)
                unset($dteParaFirmar['dteJson']['resumen']['totalIva']);
            }

            // Enviar a firmar al API local
            $apiUrl = config('dte.api_url');

            Log::info('🔐 Enviando DTE a firmar', [
                'url' => $apiUrl . '/firmardocumento/',
                'factura_id' => $factura->id,
                'tipo_dte' => $validated['tipo_dte'],
            ]);

            $response = Http::timeout(30)->post($apiUrl . '/firmardocumento/', $dteParaFirmar);

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

                // Verificar si hay un evento de contingencia activo
                $eventoActivo = $this->contingenciaService->getEventoActivo();

                if ($eventoActivo) {
                    // MODO CONTINGENCIA: No enviar al MH, solo firmar y guardar
                    Log::warning('🚨 MODO CONTINGENCIA ACTIVO', [
                        'evento_id' => $eventoActivo->id,
                        'codigo_evento' => $eventoActivo->codigo_evento,
                        'tipo_contingencia' => $eventoActivo->tipo_contingencia,
                    ]);

                    $factura->update([
                        'estado' => 'firmado_contingencia',
                        'respuesta_mh' => json_encode([
                            'firma' => 'Documento firmado exitosamente',
                            'jwt_firmado' => $dteFirmado, // Guardar JWT completo para envío posterior
                            'jwt_preview' => substr($dteFirmado, 0, 100) . '...',
                            'modo_contingencia' => true,
                            'timestamp' => now()->toDateTimeString(),
                        ]),
                    ]);

                    // Marcar factura como parte del evento de contingencia
                    $this->contingenciaService->marcarFacturaEnContingencia($factura, $eventoActivo);

                    DB::commit();

                    return redirect()->route('facturas.show', $factura->id)
                        ->with('warning', '⚠️ Factura generada en MODO CONTINGENCIA. El DTE se enviará al MH cuando se restablezca la conexión.');
                }

                // MODO NORMAL: Enviar al MH inmediatamente
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

                // Determinar versión según tipo de DTE (igual que en DTEService)
                // DTE-01 = versión 2, DTE-03 = versión 3
                $versionDte = $validated['tipo_dte'] === '03' ? 3 : 2;

                // Enviar al Ministerio de Hacienda con política de reintentos
                $resultadoMH = $this->mhApiService->enviarDTE($jwtString, [
                    'ambiente' => config('dte.ambiente'),
                    'id_envio' => $factura->id,
                    'version' => $versionDte,
                    'tipo_dte' => $validated['tipo_dte'],
                    'codigo_generacion' => $factura->codigo_generacion, // Necesario para consultar estado
                ]);

                if ($resultadoMH['success']) {
                    // Actualizar con respuesta del MH
                    $factura->update([
                        'estado' => 'enviado_mh',
                        'intentos_envio' => $resultadoMH['intentos'] ?? 1,
                        'respuesta_mh' => json_encode([
                            'firma' => 'Documento firmado exitosamente',
                            'envio_mh' => $resultadoMH['data'] ?? $resultadoMH['response'],
                            'intentos' => $resultadoMH['intentos'] ?? 1,
                            'recibido_en_consulta' => $resultadoMH['recibido_en_consulta'] ?? false,
                            'timestamp' => now()->toDateTimeString(),
                        ]),
                    ]);

                    DB::commit();

                    $mensaje = 'Factura generada, firmada y enviada al Ministerio de Hacienda exitosamente';
                    if (($resultadoMH['intentos'] ?? 1) > 1) {
                        $mensaje .= ' (después de ' . $resultadoMH['intentos'] . ' intentos)';
                    }

                    return redirect()->route('facturas.show', $factura->id)
                        ->with('success', $mensaje);
                } else {
                    // Aunque el MH falle, la factura está firmada
                    $detalleError = [
                        'firma' => 'Documento firmado exitosamente',
                        'error_mh' => $resultadoMH['message'],
                        'intentos' => $resultadoMH['intentos'] ?? 1,
                        'timestamp' => now()->toDateTimeString(),
                    ];

                    // Agregar información adicional del error si existe
                    if (isset($resultadoMH['ultimo_error'])) {
                        $detalleError['ultimo_error'] = $resultadoMH['ultimo_error'];
                    }

                    if (isset($resultadoMH['response'])) {
                        $detalleError['respuesta_mh'] = $resultadoMH['response'];
                    }

                    if (isset($resultadoMH['data'])) {
                        $detalleError['data_mh'] = $resultadoMH['data'];
                    }

                    $factura->update([
                        'estado' => 'firmado_sin_enviar',
                        'intentos_envio' => $resultadoMH['intentos'] ?? 1,
                        'respuesta_mh' => json_encode($detalleError),
                    ]);

                    DB::commit();

                    // Construir mensaje de error detallado para el usuario
                    $mensajeError = 'Factura firmada pero no se pudo enviar al MH después de ' .
                                    ($resultadoMH['intentos'] ?? 1) . ' intentos.';

                    // Agregar el motivo del error si está disponible
                    if (isset($resultadoMH['ultimo_error'])) {
                        $mensajeError .= ' Motivo: ' . $resultadoMH['ultimo_error'];
                    } else {
                        $mensajeError .= ' ' . $resultadoMH['message'];
                    }

                    return redirect()->route('facturas.show', $factura->id)
                        ->with('warning', $mensajeError);
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

    /**
     * Eliminar factura (solo permitido para facturas no enviadas o rechazadas)
     */
    public function destroy(Factura $factura)
    {
        // Verificar que la factura NO esté en estado enviado_mh (exitosamente enviada)
        if ($factura->estado === 'enviado_mh') {
            return back()->with('error', 'No se puede eliminar una factura que fue enviada exitosamente al Ministerio de Hacienda.');
        }

        try {
            // Obtener información para el mensaje
            $numeroControl = $factura->numero_control;
            $estado = $factura->estado;

            // Eliminar la factura (los items se eliminan automáticamente por cascade)
            $factura->delete();

            Log::info('Factura eliminada', [
                'factura_id' => $factura->id,
                'numero_control' => $numeroControl,
                'estado' => $estado,
            ]);

            return redirect()->route('facturas.index')
                ->with('success', 'Factura ' . $numeroControl . ' eliminada exitosamente.');
        } catch (\Exception $e) {
            Log::error('Error al eliminar factura: ' . $e->getMessage());
            return back()->with('error', 'Error al eliminar la factura: ' . $e->getMessage());
        }
    }
}