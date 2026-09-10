<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;
use App\Models\Factura;
use App\Services\DTEService;
use App\Services\MHApiService;
use App\Services\ContingenciaService;
use App\Mail\FacturaElectronicaMail;
use Barryvdh\DomPDF\Facade\Pdf;

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
     * Mostrar formulario de Nota de Crédito (DTE-05)
     */
    public function createNotaCredito()
    {
        // Obtener facturas emitidas (DTE-01 y DTE-03) para relacionar
        // IMPORTANTE: DTE-05 requiere NIT, por lo tanto excluimos facturas con DUI (tipo_documento = 13)
        $facturasDisponibles = Factura::with(['items', 'notaCredito']) // Cargar items y nota de crédito asociada
            ->whereIn('tipo_dte', ['01', '03'])
            ->where('estado', 'enviado_mh')
            ->where('receptor_tipo_documento', '!=', '13') // Excluir DUI
            ->orderBy('created_at', 'desc')
            ->get();

        return view('facturas.crear_nota_credito', compact('facturasDisponibles'));
    }

    /**
     * Mostrar formulario crear Factura de Sujeto Excluido (DTE-14)
     */
    public function createSujetoExcluido()
    {
        return view('facturas.crear_sujeto_excluido');
    }

    /**
     * Guardar y firmar factura (DTE-01, DTE-03, DTE-05, DTE-14)
     */
    public function store(Request $request)
    {
        // Determinar tipo de DTE
        $tipoDte = $request->input('tipo_dte', '01');

        // Verificar si es factura de clientes varios
        $esClientesVarios = $request->has('clientes_varios') && $request->input('clientes_varios') == '1';

        // Validaciones base
        $rules = [
            // Tipo DTE
            'tipo_dte' => 'required|string|in:01,03,05,14',

            // Clientes Varios
            'clientes_varios' => 'nullable|boolean',

            // Receptor - Ajustar según si es clientes varios
            'receptor_tipo_documento' => $esClientesVarios ? 'nullable' : 'required|string|size:2',
            'receptor_num_documento' => $esClientesVarios ? 'nullable' : 'required|string|max:20',
            'receptor_nombre' => 'nullable|string|max:255',
            'receptor_nrc' => $tipoDte === '03' ? 'required|string|max:20' : 'nullable|string|max:20',
            'receptor_cod_actividad' => $tipoDte === '03' ? 'required|string|max:10' : 'nullable|string|max:10',
            'receptor_desc_actividad' => $tipoDte === '03' ? 'required|string|min:5|max:150' : 'nullable|string|min:5|max:150',
            'receptor_departamento' => $esClientesVarios ? 'nullable' : 'required|string|size:2',
            'receptor_municipio' => $esClientesVarios ? 'nullable' : 'required|string|size:2',
            'receptor_complemento' => $esClientesVarios ? 'nullable' : 'required|string',
            'receptor_telefono' => 'nullable|string|max:20',
            'receptor_correo' => 'nullable|email',

            // Items
            'items' => 'required|array|min:1',
            'items.*.tipo_item' => 'required|integer|in:1,2',
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
            'gran_contribuyente' => 'nullable|boolean',
        ];

        // Validaciones específicas para Nota de Crédito (DTE-05)
        if ($tipoDte === '05') {
            $rules['documento_relacionado_id'] = 'required|exists:facturas,id';
            $rules['tipo_documento'] = 'required|string|in:1,2,3'; // CAT-026: 1=Anulación, 2=Ajuste Precio, 3=Devolución
            $rules['precio_incluye_iva'] = 'nullable|boolean'; // Checkbox para indicar si precio incluye IVA
        }

        // Validación especial para CCF (DTE-03): solo acepta NIT
        if ($tipoDte === '03') {
            $rules['receptor_tipo_documento'] .= '|in:36'; // Solo NIT para CCF
        }

        // Validaciones específicas para Factura de Sujeto Excluido (DTE-14)
        if ($tipoDte === '14') {
            // Según MH, codActividad y descActividad son OPCIONALES para FE y FSEE
            $rules['receptor_cod_actividad'] = 'nullable|string|max:10';
            $rules['receptor_desc_actividad'] = 'nullable|string|min:5|max:150';
        }

        // Validar datos
        $validated = $request->validate($rules);

        // Si es clientes varios, establecer valores NULL en la base de datos
        if ($esClientesVarios) {
            $validated['receptor_tipo_documento'] = null;
            $validated['receptor_num_documento'] = null;
            $validated['receptor_nombre'] = 'CLIENTES VARIOS';
            $validated['receptor_nrc'] = null;
            $validated['receptor_cod_actividad'] = null;
            $validated['receptor_desc_actividad'] = null;
            $validated['receptor_departamento'] = null;
            $validated['receptor_municipio'] = null;
            $validated['receptor_complemento'] = null;
            $validated['receptor_telefono'] = null;
            $validated['receptor_correo'] = null;
        } else {
            // Convertir nombre del receptor a mayúsculas solo si no es clientes varios
            if (isset($validated['receptor_nombre'])) {
                $validated['receptor_nombre'] = strtoupper($validated['receptor_nombre']);
            }
        }

        // Asegurar que los campos opcionales tengan valores por defecto
        // IMPORTANTE: precio_incluye_iva debe ser false por defecto para DTE-01
        // (el checkbox en el formulario está marcado, pero solo envía value="1" cuando está marcado)
        $validated = array_merge([
            'receptor_nrc' => null,
            'receptor_cod_actividad' => null,
            'receptor_telefono' => null,
            'receptor_correo' => null,
            'referencia_pago' => null,
            'observaciones' => null,
            'precio_incluye_iva' => false,  // false por defecto para DTE-01
            'gran_contribuyente' => false,
        ], $validated);

        // Convertir precio_incluye_iva a boolean si viene del formulario
        if (isset($validated['precio_incluye_iva'])) {
            $validated['precio_incluye_iva'] = (bool) $validated['precio_incluye_iva'];
        }

        try {
            DB::beginTransaction();

            // 🔍 LOG: Ver cuántos items llegaron del formulario
            Log::info('📦 Items recibidos del formulario', [
                'cantidad_items' => count($validated['items'] ?? []),
                'items' => $validated['items'] ?? [],
            ]);

            // Si es DTE-05, obtener información del documento relacionado
            $documentoRelacionado = null;
            if ($tipoDte === '05') {
                $facturaRelacionada = Factura::findOrFail($validated['documento_relacionado_id']);
                $documentoRelacionado = [
                    'tipo_dte' => $facturaRelacionada->tipo_dte,
                    'tipo_documento' => $validated['tipo_documento'],
                    'numero_control' => $facturaRelacionada->numero_control,
                    'codigo_generacion' => $facturaRelacionada->codigo_generacion,
                    'fecha_emision' => $facturaRelacionada->fecha_emision,
                ];
            }

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
    'complemento' => $validated['receptor_complemento'],
    'telefono' => $validated['receptor_telefono'],
    'correo' => $validated['receptor_correo'],
],
                'items' => $validated['items'],
                'condicion_operacion' => $validated['condicion_operacion'],
                'forma_pago' => $validated['forma_pago'],
                'referencia_pago' => $validated['referencia_pago'],
                'observaciones' => $validated['observaciones'],
                'gran_contribuyente' => (bool) ($validated['gran_contribuyente'] ?? false),
            ];

            // Agregar documento relacionado si es DTE-05
            if ($documentoRelacionado) {
                $datosFactura['documento_relacionado'] = $documentoRelacionado;
            }

            // Generar DTE completo
            // IMPORTANTE: Según normativa MH:
            // - DTE-03 (CCF): SIEMPRE true (precios incluyen IVA)
            // - DTE-05 (Nota Crédito): usar valor del checkbox
            // - DTE-01 y DTE-14: usar valor del checkbox
            if ($validated['tipo_dte'] === '03') {
                $precioIncluyeIva = true;  // OBLIGATORIO para CCF
            } else {
                $precioIncluyeIva = (bool) ($validated['precio_incluye_iva'] ?? false);
            }

            // 🔍 LOG: Ver valor de precioIncluyeIva
            Log::info('💰 Valor de precioIncluyeIva (normalizado)', [
                'tipo_dte' => $validated['tipo_dte'],
                'precio_incluye_iva_raw' => $validated['precio_incluye_iva'] ?? 'NO PRESENTE',
                'precio_incluye_iva_final' => $precioIncluyeIva,
                'razon' => $validated['tipo_dte'] === '03' ? 'CCF: FORZADO a true' : 'Valor del formulario',
            ]);

            $dteJson = $this->dteService->construirDteJson($datosFactura, $precioIncluyeIva);

            // 🔍 LOG: Ver DTE JSON completo generado
            // Todos los DTEs ahora usan 'receptor' en la estructura JSON
            Log::info('📋 DTE JSON COMPLETO GENERADO', [
                'tipo_dte' => $validated['tipo_dte'],
                'cuerpoDocumento_count' => count($dteJson['dteJson']['cuerpoDocumento'] ?? []),
                'cuerpoDocumento' => $dteJson['dteJson']['cuerpoDocumento'] ?? [],
                'dte_completo' => json_encode($dteJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
                'receptor_completo' => $dteJson['dteJson']['receptor'] ?? 'NO ENCONTRADO',
                'receptor_nit_enviado' => $dteJson['dteJson']['receptor']['nit'] ?? $dteJson['dteJson']['receptor']['numDocumento'] ?? 'NO ENCONTRADO',
                'tipo_documento_receptor' => $dteJson['dteJson']['receptor']['tipoDocumento'] ?? 'NO ENCONTRADO',
            ]);

            // Obtener totalIva (está en diferentes lugares según el tipo de DTE)
            // DTE-05 no tiene totalIva en resumen, hay que calcularlo
            if ($validated['tipo_dte'] === '05') {
                // Para DTE-05, SIEMPRE usar IVA del 13% según validación del MH
                // (Independientemente del tipo de documento relacionado)
                $totalIva = round($dteJson['dteJson']['resumen']['totalGravada'] * 0.13, 2);
            } elseif ($validated['tipo_dte'] === '14') {
                // Para DTE-14 (Sujeto Excluido), el IVA SIEMPRE es 0
                $totalIva = 0.00;
            } elseif ($validated['tipo_dte'] === '03') {
                $totalIva = $dteJson['dteJson']['resumen']['totalIva'] ?? 0;  // CCF: guardado para BD
            } else {
                $totalIva = $dteJson['dteJson']['resumen']['totalIva'];  // Factura: está directo
            }

            // Guardar factura en BD
            // Para DTE-14, usar 'sujetoExcluido' en lugar de 'receptor'
            if ($validated['tipo_dte'] === '14') {
                $receptorDireccion = $dteJson['dteJson']['sujetoExcluido']['direccion'];
            } else {
                $receptorDireccion = $dteJson['dteJson']['receptor']['direccion'];
            }

            // Para DTE-14, la estructura de resumen es diferente
            if ($validated['tipo_dte'] === '14') {
                $datosFacturaDB = [
                    'numero_control' => $dteJson['dteJson']['identificacion']['numeroControl'],
                    'codigo_generacion' => $dteJson['dteJson']['identificacion']['codigoGeneracion'],
                    'tipo_dte' => $validated['tipo_dte'],
                    'fecha_emision' => $dteJson['dteJson']['identificacion']['fecEmi'],
                    'hora_emision' => $dteJson['dteJson']['identificacion']['horEmi'],
                    'receptor_tipo_documento' => $validated['receptor_tipo_documento'],
                    'receptor_num_documento' => $validated['receptor_num_documento'],
                    'receptor_nombre' => $validated['receptor_nombre'],
                    'receptor_nrc' => $validated['receptor_nrc'] ?? null,
                    'receptor_actividad' => $validated['receptor_cod_actividad'] ?? null,
                    'receptor_direccion' => json_encode($receptorDireccion),
                    'receptor_telefono' => $validated['receptor_telefono'],
                    'receptor_correo' => $validated['receptor_correo'],
                    // DTE-14 usa totalCompra en lugar de totalGravada/totalExenta
                    'total_gravada' => 0.00,
                    'total_exenta' => $dteJson['dteJson']['resumen']['totalCompra'] ?? 0,
                    'total_no_sujeto' => 0.00,
                    'total_descuento' => $dteJson['dteJson']['resumen']['descu'] ?? 0,
                    'sub_total' => $dteJson['dteJson']['resumen']['totalCompra'] ?? 0,
                    'total_iva' => 0.00,  // DTE-14 nunca tiene IVA
                    'total_pagar' => $dteJson['dteJson']['resumen']['totalPagar'] ?? 0,
                    'total_letras' => $dteJson['dteJson']['resumen']['totalLetras'],
                    'condicion_operacion' => $validated['condicion_operacion'],
                    'dte_json' => $dteJson,
                    'estado' => 'pendiente',
                ];
            } else {
                $datosFacturaDB = [
                    'numero_control' => $dteJson['dteJson']['identificacion']['numeroControl'],
                    'codigo_generacion' => $dteJson['dteJson']['identificacion']['codigoGeneracion'],
                    'tipo_dte' => $validated['tipo_dte'],
                    'fecha_emision' => $dteJson['dteJson']['identificacion']['fecEmi'],
                    'hora_emision' => $dteJson['dteJson']['identificacion']['horEmi'],
                    'receptor_tipo_documento' => $validated['receptor_tipo_documento'],
                    'receptor_num_documento' => $validated['receptor_num_documento'],
                    'receptor_nombre' => $validated['receptor_nombre'],
                    'receptor_nrc' => $validated['receptor_nrc'] ?? null,
                    'receptor_actividad' => $validated['receptor_cod_actividad'] ?? null,
                    'receptor_direccion' => json_encode($receptorDireccion),
                    'receptor_telefono' => $validated['receptor_telefono'],
                    'receptor_correo' => $validated['receptor_correo'],
                    'total_gravada' => $dteJson['dteJson']['resumen']['totalGravada'] ?? 0,
                    'total_exenta' => $dteJson['dteJson']['resumen']['totalExenta'] ?? 0,
                    'total_no_sujeto' => $dteJson['dteJson']['resumen']['totalNoSuj'] ?? 0,
                    'total_descuento' => $dteJson['dteJson']['resumen']['totalDescu'] ?? 0,
                    'sub_total' => $dteJson['dteJson']['resumen']['subTotal'] ?? 0,
                    'total_iva' => $totalIva,
                    'total_pagar' => $dteJson['dteJson']['resumen']['montoTotalOperacion'] ?? $dteJson['dteJson']['resumen']['totalPagar'] ?? 0,
                    'total_letras' => $dteJson['dteJson']['resumen']['totalLetras'],
                    'condicion_operacion' => $validated['condicion_operacion'],
                    'dte_json' => $dteJson,
                    'estado' => 'pendiente',
                ];
            }

            // Agregar campos de documento relacionado si es DTE-05
            if ($tipoDte === '05' && $documentoRelacionado) {
                $datosFacturaDB['documento_relacionado_id'] = $validated['documento_relacionado_id'];  // ID para relación FK
                $datosFacturaDB['documento_relacionado_tipo_dte'] = $documentoRelacionado['tipo_dte'];
                $datosFacturaDB['documento_relacionado_numero_control'] = $documentoRelacionado['numero_control'];
                $datosFacturaDB['documento_relacionado_codigo_generacion'] = $documentoRelacionado['codigo_generacion'];
                $datosFacturaDB['documento_relacionado_fecha_emision'] = $documentoRelacionado['fecha_emision'];
                $datosFacturaDB['tipo_documento'] = $documentoRelacionado['tipo_documento'];
            }

            $factura = Factura::create($datosFacturaDB);

            // Guardar items
            foreach ($dteJson['dteJson']['cuerpoDocumento'] as $item) {
                // Para DTE-14, la estructura de items es diferente
                if ($validated['tipo_dte'] === '14') {
                    // DTE-14 usa 'compra' en lugar de ventaGravada/ventaExenta
                    $factura->items()->create([
                        'num_item' => $item['numItem'],
                        'codigo' => $item['codigo'] ?? null,
                        'descripcion' => $item['descripcion'],
                        'cantidad' => $item['cantidad'],
                        'uni_medida' => $item['uniMedida'],
                        'precio_unitario' => $item['precioUni'],
                        'monto_descuento' => $item['montoDescu'] ?? 0,
                        'venta_gravada' => 0.00,  // DTE-14 no tiene venta gravada
                        'venta_exenta' => $item['compra'],  // El campo 'compra' va como exenta
                        'venta_no_sujeta' => 0.00,
                        'iva_item' => 0.00,  // DTE-14 nunca tiene IVA
                    ]);
                } else {
                    // Para DTE-01, DTE-03, DTE-05 (estructura normal)
                    // Calcular IVA para guardar en BD
                    if ($validated['tipo_dte'] === '03' || $validated['tipo_dte'] === '05') {
                        // DTE-03 y DTE-05 SIEMPRE usan 13% según validación del MH
                        $ivaItem = round($item['ventaGravada'] * 0.13, 2);
                    } else {
                        $ivaItem = $item['ivaItem'];
                    }

                    $factura->items()->create([
                        'num_item' => $item['numItem'],
                        'codigo' => $item['codigo'],
                        'descripcion' => $item['descripcion'],
                        'cantidad' => $item['cantidad'],
                        'uni_medida' => $item['uniMedida'],
                        'precio_unitario' => $item['precioUni'],
                        'monto_descuento' => $item['montoDescu'],
                        'venta_gravada' => $item['ventaGravada'],
                        'venta_exenta' => $item['ventaExenta'] ?? 0,
                        'venta_no_sujeta' => $item['ventaNoSuj'] ?? 0,
                        'iva_item' => $ivaItem,
                    ]);
                }
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

            // 🔍 LOG: Verificar estructura del receptor antes de enviar a firmar
            Log::info('👤 RECEPTOR ANTES DE FIRMAR', [
                'receptor_completo' => $dteParaFirmar['dteJson']['receptor'] ?? 'NO ENCONTRADO',
                'tiene_nrc' => isset($dteParaFirmar['dteJson']['receptor']['nrc']),
                'tiene_codActividad' => isset($dteParaFirmar['dteJson']['receptor']['codActividad']),
                'tiene_descActividad' => isset($dteParaFirmar['dteJson']['receptor']['descActividad']),
                'tiene_telefono' => isset($dteParaFirmar['dteJson']['receptor']['telefono']),
                'tiene_correo' => isset($dteParaFirmar['dteJson']['receptor']['correo']),
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

                // 🔍 DECODIFICAR JWT PARA VER QUÉ CONTIENE
                if ($dteFirmado) {
                    $jwtParts = explode('.', $dteFirmado);
                    if (count($jwtParts) >= 2) {
                        $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $jwtParts[1])), true);
                        Log::info('🔓 JWT DECODIFICADO - Contenido del documento firmado', [
                            'cuerpoDocumento_en_jwt' => $payload['cuerpoDocumento'] ?? 'NO EXISTE',
                            'cuerpoDocumento_count' => count($payload['cuerpoDocumento'] ?? []),
                            'payload_completo' => json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
                        ]);
                    }
                }

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
                // DTE-01 = versión 1, DTE-03 = versión 3, DTE-05 = versión 3, DTE-14 = versión 1
                $versionDte = match($validated['tipo_dte']) {
                    '03' => 3,
                    '05' => 3,
                    '14' => 1,
                    default => 1, // DTE-01 usa versión 1
                };

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

                    // Si es una nota de crédito (DTE-05), actualizar el estado del documento relacionado
                    if ($tipoDte === '05' && isset($validated['documento_relacionado_id'])) {
                        $facturaRelacionada = Factura::find($validated['documento_relacionado_id']);
                        if ($facturaRelacionada) {
                            // Mapear tipo_documento a estado legible
                            $estadoNotaCredito = match($validated['tipo_documento']) {
                                '1' => 'anulacion',
                                '2' => 'ajuste_precio',
                                '3' => 'devolucion',
                                default => 'nota_credito',
                            };

                            $facturaRelacionada->update([
                                'nota_credito_estado' => $estadoNotaCredito,
                                'nota_credito_id' => $factura->id,
                            ]);

                            Log::info('📝 Estado de documento relacionado actualizado', [
                                'factura_relacionada_id' => $facturaRelacionada->id,
                                'numero_control' => $facturaRelacionada->numero_control,
                                'nota_credito_estado' => $estadoNotaCredito,
                                'nota_credito_id' => $factura->id,
                            ]);
                        }
                    }

                    // ====================================
                    // POST a Motors González para descargo de stock
                    // ====================================
                    try {
                        $clienteId = $request->input('cliente_id');
                        $motorProductos = $request->input('motor_clientes_ids'); // JSON string del formulario
                        $esClientesVarios = $request->has('clientes_varios') && $request->input('clientes_varios') == '1';

                        Log::info('====== INICIANDO ENVÍO A MOTORS GONZÁLEZ ======', [
                            'factura_id' => $factura->id,
                            'numero_control' => $factura->numero_control,
                            'tipo_dte' => $factura->tipo_dte,
                            'es_clientes_varios' => $esClientesVarios,
                            'cliente_id' => $clienteId,
                            'receptor_nombre' => $factura->receptor_nombre,
                            'motor_productos_presente' => !empty($motorProductos),
                        ]);

                        if ($motorProductos && ($clienteId || $esClientesVarios)) {
                            Log::info('✅ CONDICIÓN CUMPLIDA: Enviando a Motors González', [
                                'es_cliente_especifico' => !empty($clienteId),
                                'es_clientes_varios' => $esClientesVarios,
                            ]);
                            
                            // Si motorProductos viene como string JSON, decodificar
                            if (is_string($motorProductos)) {
                                $productosArray = json_decode($motorProductos, true);
                                Log::info('📋 JSON decodificado', [
                                    'productos' => $productosArray,
                                    'json_error' => json_last_error_msg(),
                                ]);
                            } else {
                                $productosArray = $motorProductos;
                                Log::info('📋 Motor productos ya era array', ['productos' => $productosArray]);
                            }
                            
                            if (is_array($productosArray) && !empty($productosArray)) {
                                Log::info('✅ Array válido con ' . count($productosArray) . ' items totales');
                                
                                // Construir items para Motors González (productos y servicios)
                                $itemsMotorsGonzalez = [];
                                
                                foreach ($productosArray as $prod) {
                                    // Enviar todos los items:
                                    // - Productos: producto_id es numérico (ej: 5)
                                    // - Servicios: producto_id es string (ej: "serv-001")
                                    $productoId = $prod['producto_id'] ?? null;
                                    
                                    if ($productoId !== null) {
                                        $itemsMotorsGonzalez[] = [
                                            'producto_id' => $productoId, // Se mantiene el tipo (numérico o string)
                                            'cantidad' => (float)($prod['cantidad'] ?? 0),
                                            'precio_unitario' => (float)($prod['precio_unitario'] ?? 0),
                                        ];
                                        
                                        $tipo = is_numeric($productoId) ? 'Producto' : 'Servicio';
                                        Log::info('✅ ' . $tipo . ' incluido en descargo', [
                                            'producto_id' => $productoId,
                                            'descripcion' => $prod['descripcion'] ?? 'N/A',
                                        ]);
                                    }
                                }

                                // Solo proceder si hay productos válidos (no solo servicios)
                                if (!empty($itemsMotorsGonzalez)) {
                                    Log::info('✅ Se enviarán ' . count($itemsMotorsGonzalez) . ' productos a Motors González');

                                    $payloadMotorsGonzalez = [
                                        'numero_factura' => $factura->numero_control,
                                        'cliente_id' => $esClientesVarios ? null : (int) $clienteId,
                                        'cliente' => $factura->receptor_nombre,
                                        'es_clientes_varios' => $esClientesVarios,
                                        'fecha' => $factura->fecha_emision->format('Y-m-d'),
                                        'items' => $itemsMotorsGonzalez,
                                    ];

                                    // Log detallado del payload
                                    Log::info('🚀 PAYLOAD MOTORS GONZÁLEZ', [
                                        'numero_factura' => $payloadMotorsGonzalez['numero_factura'],
                                        'cliente_id' => $payloadMotorsGonzalez['cliente_id'],
                                        'cliente' => $payloadMotorsGonzalez['cliente'],
                                        'es_clientes_varios' => $payloadMotorsGonzalez['es_clientes_varios'],
                                        'fecha' => $payloadMotorsGonzalez['fecha'],
                                        'cantidad_items' => count($itemsMotorsGonzalez),
                                        'items' => $itemsMotorsGonzalez,
                                    ]);

                                    Log::info('📊 JSON COMPLETO', ['payload' => json_encode($payloadMotorsGonzalez, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)]);

                                    // Hacer POST a Motors González
                                    $authToken = Session::get('auth_token');
                                    Log::info('🔐 Token disponible', ['tiene_token' => !empty($authToken), 'token_length' => strlen($authToken ?? '')]);
                                    
                                    if ($authToken) {
                                        $urlPost = config('services.motor_api.url') . '/facturas';
                                        Log::info('📤 Enviando POST', [
                                            'url' => $urlPost,
                                            'metodo' => 'POST',
                                            'headers' => [
                                                'Authorization' => 'Bearer ****' . substr($authToken, -10),
                                                'Accept' => 'application/json',
                                                'Content-Type' => 'application/json',
                                            ]
                                        ]);
                                        
                                        $response = Http::timeout(10)
                                            ->withHeaders([
                                                'Authorization' => 'Bearer ' . $authToken,
                                                'Accept' => 'application/json',
                                                'Content-Type' => 'application/json',
                                            ])
                                            ->post($urlPost, $payloadMotorsGonzalez);

                                        Log::info('📥 RESPUESTA RECIBIDA', [
                                            'status_code' => $response->status(),
                                            'successful' => $response->successful(),
                                            'headers' => $response->headers(),
                                            'body' => $response->body(),
                                        ]);

                                        if ($response->successful()) {
                                            $responseData = $response->json();
                                            Log::info('✅ RESPUESTA JSON PARSEADA', ['data' => $responseData]);
                                            
                                            if (isset($responseData['success']) && $responseData['success'] === true) {
                                                Log::info('✅ ✅ Descargo registrado exitosamente en Motors González', [
                                                    'factura_id' => $factura->id,
                                                    'numero_control' => $factura->numero_control,
                                                    'cliente_id' => $clienteId,
                                                ]);
                                            } else {
                                                Log::warning('⚠️ Respuesta no exitosa de Motors González', ['response' => $responseData]);
                                            }
                                        } else {
                                            Log::error('❌ ERROR HTTP en Motors González', [
                                                'status' => $response->status(),
                                                'body' => $response->body(),
                                            ]);
                                        }
                                    } else {
                                        Log::warning('⚠️ NO HAY TOKEN - No se envió a Motors González');
                                    }
                                } else {
                                    Log::info('ℹ️ No hay productos válidos para descargo (solo servicios)', [
                                        'items_solicitados' => count($productosArray),
                                        'productos_válidos' => count($itemsMotorsGonzalez),
                                    ]);
                                }
                            } else {
                                Log::warning('❌ Array vacío o inválido', [
                                    'es_array' => is_array($productosArray),
                                    'esta_vacio' => empty($productosArray),
                                ]);
                            }
                        } else {
                            Log::warning('❌ CONDICIÓN NO CUMPLIDA - No se envía a Motors González', [
                                'tiene_cliente_id' => !empty($clienteId),
                                'cliente_id_value' => $clienteId,
                                'tiene_motor_productos' => !empty($motorProductos),
                                'motor_productos_value' => $motorProductos,
                                'es_clientes_varios' => $esClientesVarios,
                                'razon' => $esClientesVarios ? 'Es Clientes Varios' : 'Falta cliente_id o motor_productos',
                            ]);
                        }
                    } catch (\Exception $e) {
                        Log::error('EXCEPCIÓN en descargo de stock', [
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString(),
                        ]);
                    }

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
            Log::error('Error al procesar factura: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            return back()->with('error', 'Error al procesar la factura: ' . $e->getMessage() . ' (Línea: ' . $e->getLine() . ')')
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
    public function index(Request $request)
    {
        $query = Factura::query();

        // Búsqueda por texto (número de control, nombre de cliente, número de documento)
        if ($request->filled('buscar')) {
            $buscar = $request->input('buscar');
            $query->where(function ($q) use ($buscar) {
                $q->where('numero_control', 'LIKE', "%{$buscar}%")
                  ->orWhere('receptor_nombre', 'LIKE', "%{$buscar}%")
                  ->orWhere('receptor_num_documento', 'LIKE', "%{$buscar}%")
                  ->orWhere('codigo_generacion', 'LIKE', "%{$buscar}%");
            });
        }

        // Filtro por tipo de DTE
        if ($request->filled('tipo_dte')) {
            $query->where('tipo_dte', $request->input('tipo_dte'));
        }

        // Filtro por estado
        if ($request->filled('estado')) {
            $query->where('estado', $request->input('estado'));
        }

        // Filtro por rango de fechas específicas
        if ($request->filled('fecha_desde')) {
            $query->whereDate('fecha_emision', '>=', $request->input('fecha_desde'));
        }

        if ($request->filled('fecha_hasta')) {
            $query->whereDate('fecha_emision', '<=', $request->input('fecha_hasta'));
        }

        // Filtro por mes y año
        if ($request->filled('mes') && $request->filled('anio')) {
            $mes = $request->input('mes');
            $anio = $request->input('anio');
            $query->whereMonth('fecha_emision', $mes)
                  ->whereYear('fecha_emision', $anio);
        } elseif ($request->filled('anio')) {
            // Solo filtro por año si no hay mes seleccionado
            $query->whereYear('fecha_emision', $request->input('anio'));
        }

        $facturas = $query->orderBy('created_at', 'desc')->paginate(20)->withQueryString();

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

    /**
     * Envía la factura electrónica por correo al receptor
     */
    public function enviarEmail(Factura $factura)
    {
        try {
            // Verificar que la factura tenga correo electrónico
            if (empty($factura->receptor_correo)) {
                return back()->with('error', 'Esta factura no tiene un correo electrónico registrado.');
            }

            // Verificar que la factura esté enviada al MH
            if ($factura->estado !== 'enviado_mh') {
                return back()->with('warning', 'Solo se pueden enviar por correo facturas que hayan sido enviadas al Ministerio de Hacienda.');
            }

            // Enviar el correo
            Mail::to($factura->receptor_correo)->send(new FacturaElectronicaMail($factura));

            Log::info('Factura enviada por email', [
                'factura_id' => $factura->id,
                'numero_control' => $factura->numero_control,
                'email_destino' => $factura->receptor_correo,
            ]);

            return back()->with('success', "Factura enviada correctamente a {$factura->receptor_correo}");
        } catch (\Exception $e) {
            Log::error('Error al enviar correo de factura: ' . $e->getMessage(), [
                'factura_id' => $factura->id,
                'numero_control' => $factura->numero_control,
                'error' => $e->getMessage(),
            ]);
            return back()->with('error', 'Error al enviar el correo: ' . $e->getMessage());
        }
    }

    /**
     * Genera el HTML del PDF de la factura
     */
    public function generarPdfHtml(Factura $factura)
    {
        return view('facturas.pdf', compact('factura'))->render();
    }

    /**
     * Descarga el PDF de la factura (el mismo que se adjunta por correo),
     * para poder enviarlo manualmente por WhatsApp u otro medio.
     */
    public function descargarPdf(Factura $factura)
    {
        try {
            $pdf = Pdf::loadView('facturas.pdf', ['factura' => $factura]);

            return $pdf->download($factura->numero_control . '.pdf');
        } catch (\Exception $e) {
            Log::error('Error al generar PDF de factura para descarga: ' . $e->getMessage(), [
                'factura_id' => $factura->id,
                'numero_control' => $factura->numero_control,
            ]);

            return back()->with('error', 'Error al generar el PDF: ' . $e->getMessage());
        }
    }

    /**
     * Exportar facturas filtradas a Excel (.xlsx), con el formato de columnas
     * usado por el negocio: Fecha, Tipo DTE (código y descripción), Número de
     * Control, Código de Generación, Sello MH, Serie, DUI/NIT, Nombre Cliente,
     * Exento, No Sujeto, Subtotal, IVA, Retención, Ventas, Comentarios.
     */
    public function exportarExcel(Request $request)
    {
        try {
            // Obtener los IDs de las facturas filtradas desde el request
            $facturaIds = $request->input('factura_ids', []);

            // Si no hay IDs, aplicar los mismos filtros que en el index
            if (empty($facturaIds)) {
                $query = Factura::query();

                // Aplicar los mismos filtros que en index()
                if ($request->filled('buscar')) {
                    $buscar = $request->input('buscar');
                    $query->where(function ($q) use ($buscar) {
                        $q->where('numero_control', 'LIKE', "%{$buscar}%")
                          ->orWhere('receptor_nombre', 'LIKE', "%{$buscar}%")
                          ->orWhere('receptor_num_documento', 'LIKE', "%{$buscar}%")
                          ->orWhere('codigo_generacion', 'LIKE', "%{$buscar}%");
                    });
                }

                if ($request->filled('tipo_dte')) {
                    $query->where('tipo_dte', $request->input('tipo_dte'));
                }

                if ($request->filled('estado')) {
                    $query->where('estado', $request->input('estado'));
                }

                if ($request->filled('fecha_desde')) {
                    $query->whereDate('fecha_emision', '>=', $request->input('fecha_desde'));
                }

                if ($request->filled('fecha_hasta')) {
                    $query->whereDate('fecha_emision', '<=', $request->input('fecha_hasta'));
                }

                if ($request->filled('mes') && $request->filled('anio')) {
                    $mes = $request->input('mes');
                    $anio = $request->input('anio');
                    $query->whereMonth('fecha_emision', $mes)
                          ->whereYear('fecha_emision', $anio);
                } elseif ($request->filled('anio')) {
                    $query->whereYear('fecha_emision', $request->input('anio'));
                }

                $facturas = $query->orderBy('fecha_emision', 'desc')->get();
            } else {
                // Obtener facturas por IDs específicos
                $facturas = Factura::whereIn('id', $facturaIds)
                    ->orderBy('fecha_emision', 'desc')
                    ->get();
            }

            // Verificar si hay facturas para exportar
            if ($facturas->isEmpty()) {
                return back()->with('warning', 'No hay facturas para exportar con los filtros aplicados.');
            }

            // Obtener el sello de recepción del JSON respuesta_mh.
            // La estructura varía según cómo se guardó la respuesta (envío directo,
            // contingencia, consulta posterior), así que se revisan todas las
            // ubicaciones posibles (mismo criterio que InvalidacionService).
            $getSelloRecibido = function($factura) {
                if (empty($factura->respuesta_mh)) {
                    return '';
                }

                $respuestaMh = is_string($factura->respuesta_mh)
                    ? json_decode($factura->respuesta_mh, true)
                    : $factura->respuesta_mh;

                if (!is_array($respuestaMh)) {
                    return '';
                }

                return $respuestaMh['envio_mh']['selloRecibido']
                    ?? $respuestaMh['selloRecibido']
                    ?? $respuestaMh['data']['selloRecibido']
                    ?? $respuestaMh['body']['selloRecibido']
                    ?? '';
            };

            // Nombre descriptivo del tipo de DTE (columna "Tipo de DTE" en texto)
            $tipoDteTexto = [
                '01' => 'Consumidor Final',
                '03' => 'Crédito Fiscal',
                '05' => 'Nota de Crédito',
                '14' => 'Sujeto Excluido',
            ];

            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            $headersFila = [
                'A1' => 'Fecha de Emisión',
                'B1' => 'Tipo de DTE',
                'C1' => 'Tipo de DTE',
                'D1' => 'Número de Control',
                'E1' => 'Código de Generación',
                'F1' => 'Sello MH',
                'G1' => 'Serie',
                'H1' => 'DUI/NIT Cliente',
                'I1' => 'Nombre Cliente',
                'J1' => 'Exento',
                'K1' => 'No Sujeto',
                'L1' => 'Subtotal',
                'M1' => 'IVA',
                'N1' => 'Retencion ',
                'O1' => 'Ventas',
                'P1' => 'Comentarios',
            ];

            foreach ($headersFila as $celda => $texto) {
                $sheet->setCellValue($celda, $texto);
            }
            $sheet->getStyle('A1:P1')->getFont()->setBold(true);

            $serie = config('dte.establecimiento', 'S001');
            $fila = 2;

            foreach ($facturas as $factura) {
                $dteJson = is_string($factura->dte_json)
                    ? json_decode($factura->dte_json, true)
                    : $factura->dte_json;
                $reteRenta = $dteJson['resumen']['reteRenta'] ?? 0;

                $sheet->setCellValue("A{$fila}", $factura->fecha_emision->format('d/m/Y'));
                $sheet->setCellValue("B{$fila}", (int) $factura->tipo_dte);
                $sheet->setCellValue("C{$fila}", $tipoDteTexto[$factura->tipo_dte] ?? $factura->tipo_dte);
                $sheet->setCellValue("D{$fila}", $factura->numero_control);
                $sheet->setCellValue("E{$fila}", $factura->codigo_generacion);
                $sheet->setCellValue("F{$fila}", $getSelloRecibido($factura));
                $sheet->setCellValue("G{$fila}", $serie);
                $sheet->setCellValueExplicit("H{$fila}", $factura->receptor_num_documento, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                $sheet->setCellValue("I{$fila}", $factura->receptor_nombre);
                $sheet->setCellValue("J{$fila}", (float) $factura->total_exenta);
                $sheet->setCellValue("K{$fila}", (float) $factura->total_no_sujeto);
                $sheet->setCellValue("L{$fila}", (float) $factura->sub_total);
                $sheet->setCellValue("M{$fila}", (float) $factura->total_iva);
                $sheet->setCellValue("N{$fila}", (float) $reteRenta);
                $sheet->setCellValue("O{$fila}", (float) $factura->total_pagar);
                $sheet->setCellValue("P{$fila}", '');

                $fila++;
            }

            $ultimaFila = $fila - 1;

            // Formato de moneda (2 decimales) para las columnas de montos
            $sheet->getStyle("J2:O{$ultimaFila}")
                ->getNumberFormat()
                ->setFormatCode('#,##0.00');

            // Ancho de columnas similar al archivo de referencia del negocio
            $anchos = ['A' => 14, 'B' => 12, 'C' => 16, 'D' => 32, 'E' => 39, 'F' => 45, 'G' => 8, 'H' => 18, 'I' => 36, 'J' => 10, 'K' => 10, 'L' => 12, 'M' => 10, 'N' => 12, 'O' => 12, 'P' => 38];
            foreach ($anchos as $col => $ancho) {
                $sheet->getColumnDimension($col)->setWidth($ancho);
            }

            $sheet->setAutoFilter("A1:P{$ultimaFila}");
            $sheet->freezePane('A2');

            $mesTexto = $request->filled('mes') && $request->filled('anio')
                ? '_' . str_pad($request->input('mes'), 2, '0', STR_PAD_LEFT) . '-' . $request->input('anio')
                : '';
            $timestamp = now()->format('Y-m-d_His');
            $filename = "Facturas{$mesTexto}_{$timestamp}.xlsx";

            Log::info('Facturas exportadas a Excel', [
                'cantidad' => $facturas->count(),
                'usuario' => auth()->user()->name ?? 'Sistema',
            ]);

            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

            return response()->streamDownload(function () use ($writer) {
                $writer->save('php://output');
            }, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]);

        } catch (\Exception $e) {
            Log::error('Error al exportar facturas: ' . $e->getMessage(), [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', 'Error al exportar facturas: ' . $e->getMessage());
        }
    }

    /**
     * Buscar cliente por DUI o NIT
     */
    public function buscarCliente(Request $request)
    {
        // Validar autenticación
        if (!Session::has('auth_token') || !Session::has('authenticated')) {
            return response()->json([
                'success' => false,
                'message' => 'No autenticado'
            ], 401);
        }

        $request->validate([
            'documento' => 'required|string|min:8|max:14',
        ]);

        try {
            $documento = preg_replace('/[^0-9]/', '', $request->documento);

            // Llamar al endpoint de Motors González
            $response = Http::timeout(10)->get(config('services.motor_api.url') . '/clientes/buscar/' . $documento);

            if ($response->successful()) {
                $data = $response->json();

                return response()->json([
                    'success' => true,
                    'cliente' => $data['cliente'] ?? $data['data'] ?? $data
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Cliente no encontrado'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error al buscar cliente:', [
                'documento' => $request->documento,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al buscar cliente: ' . $e->getMessage()
            ], 500);
        }
    }
}
