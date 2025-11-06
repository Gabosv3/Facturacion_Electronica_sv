<?php

namespace App\Services;

use App\Models\EventoInvalidacion;
use App\Models\Factura;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;

class InvalidacionService
{
    private $mhApiService;
    private $apiUrl;

    public function __construct(MHApiService $mhApiService)
    {
        $this->mhApiService = $mhApiService;
        $this->apiUrl = config('dte.api_url');
    }

    /**
     * Crea un nuevo evento de invalidación
     *
     * @param array $data Datos del evento
     * @return EventoInvalidacion
     */
    public function crearEvento(array $data): EventoInvalidacion
    {
        // Generar código de invalidación único (UUID v4)
        $codigoInvalidacion = Str::uuid()->toString();

        $evento = EventoInvalidacion::create([
            'factura_id' => $data['factura_id'],
            'codigo_invalidacion' => strtoupper($codigoInvalidacion),
            'version' => 2, // Versión 2 para eventos de invalidación según MH
            'ambiente' => config('dte.ambiente'),
            'fecha_evento' => $data['fecha_evento'] ?? now(),
            'tipo_invalidacion' => $data['tipo_invalidacion'],
            'motivo_invalidacion' => $data['motivo_invalidacion'],
            'factura_reemplazo_id' => $data['factura_reemplazo_id'] ?? null,
            'codigo_generacion_reemplazo' => $data['codigo_generacion_reemplazo'] ?? null,
            'responsable_nombre' => $data['responsable_nombre'],
            'responsable_tipo_doc' => $data['responsable_tipo_doc'],
            'responsable_num_doc' => $data['responsable_num_doc'],
            'solicitante_nombre' => $data['solicitante_nombre'],
            'solicitante_tipo_doc' => $data['solicitante_tipo_doc'],
            'solicitante_num_doc' => $data['solicitante_num_doc'],
            'estado' => 'pendiente',
        ]);

        return $evento;
    }

    /**
     * Construye el JSON del evento de invalidación según especificaciones del MH
     * ESTRUCTURA DEFINITIVA CORRECTA (2025-11-05 21:47):
     *
     * CAMPOS REQUERIDOS EN "documento":
     * - tipoDte: Tipo de DTE
     * - codigoGeneracion: UUID del DTE a anular
     * - selloRecibido: Sello recibido del MH
     * - numeroControl: Número de control
     * - fecEmi: Fecha de emisión
     * - montoIva: Monto del IVA de la factura
     * - codigoGeneracionR: UUID (reemplazo o mismo del DTE)
     * - tipoDocumento: Tipo de documento del receptor
     * - numDocumento: Número de documento del receptor
     * - nombre: Nombre del receptor
     *
     * CAMPOS REQUERIDOS EN "motivo":
     * - tipoAnulacion: integer (1, 2, o 3)
     * - motivoAnulacion: string (texto descriptivo)
     * - nombreResponsable: string (nombre completo)
     * - tipDocResponsable: string (código CAT-022)
     * - numDocResponsable: string (número de documento)
     * - nombreSolicita: string (nombre completo)
     * - tipDocSolicita: string (código CAT-022)
     * - numDocSolicita: string (número de documento)
     *
     * IMPORTANTE:
     * - motivo es un OBJETO con 8 campos, NO un objeto vacío
     * - codigoGeneracionR: si tipo 1 con reemplazo → UUID nuevo, sino → mismo UUID
     * - Todos los campos son obligatorios
     *
     * @param EventoInvalidacion $evento
     * @return array
     */
    public function construirJsonEvento(EventoInvalidacion $evento): array
    {
        $factura = $evento->factura;
        $emisor = config('dte.emisor');

        // Identificación del evento
        $identificacion = [
            'version' => 2,
            'ambiente' => $evento->ambiente,
            'codigoGeneracion' => $evento->codigo_invalidacion,
            'fecAnula' => $evento->fecha_evento->format('Y-m-d'),
            'horAnula' => $evento->fecha_evento->format('H:i:s'),
        ];

        // Emisor
        $emisorData = [
            'nit' => $emisor['nit'],
            'nombre' => $emisor['nombre'],
            'tipoEstablecimiento' => '01', // 01=Casa Matriz, 02=Sucursal, 04=Punto de Venta
            'codEstable' => $emisor['cod_estable'] ?? '0000',
            'codPuntoVenta' => $emisor['cod_punto_venta'] ?? '0000',
            'nomEstablecimiento' => $emisor['nombre'],
            'correo' => $emisor['correo'],
            'telefono' => $emisor['telefono'],
        ];

        // Obtener sello recibido (REQUERIDO según error 21:31)
        $selloRecibido = $this->obtenerSelloRecibido($factura);
        if (empty($selloRecibido)) {
            throw new \Exception(
                "La factura {$factura->numero_control} no tiene sello recibido del MH. " .
                "Solo se pueden invalidar facturas que fueron procesadas exitosamente por el MH."
            );
        }

        // Documento a anular
        // Según errores del MH (21:47):
        // - codigoGeneracion: REQUERIDO
        // - selloRecibido: REQUERIDO
        // - codigoGeneracionR: REQUERIDO
        $documento = [
            'tipoDte' => $factura->tipo_dte,
            'codigoGeneracion' => $factura->codigo_generacion, // UUID del DTE a anular
            'selloRecibido' => $selloRecibido, // Sello del MH
            'numeroControl' => $factura->numero_control,
            'fecEmi' => $factura->fecha_emision->format('Y-m-d'),
            'montoIva' => (float) $factura->total_iva,
            'tipoDocumento' => $factura->receptor_tipo_documento,
            'numDocumento' => $factura->receptor_num_documento,
            'nombre' => $factura->receptor_nombre,
        ];

        // codigoGeneracionR: REQUERIDO pero con valor null si no hay reemplazo
        // - Si tipo 1 con reemplazo: UUID del documento de reemplazo
        // - Si tipo 2 o 3 sin reemplazo: null
        if ($evento->tipo_invalidacion == '1' && !empty($evento->codigo_generacion_reemplazo)) {
            $documento['codigoGeneracionR'] = $evento->codigo_generacion_reemplazo;
        } else {
            $documento['codigoGeneracionR'] = null; // ✅ null para tipo 2/3
        }

        // Motivo: Objeto con 8 campos requeridos según errores del MH (21:47)
        $motivo = [
            'tipoAnulacion' => (int) $evento->tipo_invalidacion, // 1, 2, o 3
            'motivoAnulacion' => $evento->motivo_invalidacion, // Texto descriptivo
            'nombreResponsable' => $evento->responsable_nombre,
            'tipDocResponsable' => $evento->responsable_tipo_doc,
            'numDocResponsable' => $evento->responsable_num_doc,
            'nombreSolicita' => $evento->solicitante_nombre,
            'tipDocSolicita' => $evento->solicitante_tipo_doc,
            'numDocSolicita' => $evento->solicitante_num_doc,
        ];

        return [
            'identificacion' => $identificacion,
            'emisor' => $emisorData,
            'documento' => $documento,
            'motivo' => $motivo,
        ];
    }

    /**
     * Obtiene el sello recibido del MH de la respuesta almacenada
     *
     * @param Factura $factura
     * @return string|null
     */
    private function obtenerSelloRecibido(Factura $factura): ?string
    {
        if (!$factura->respuesta_mh) {
            Log::warning('⚠️ No hay respuesta_mh para obtener sello', [
                'factura_id' => $factura->id,
                'numero_control' => $factura->numero_control,
            ]);
            return null;
        }

        // Buscar en diferentes ubicaciones posibles del sello
        // Estructura típica: {"firma":"...", "envio_mh":{"selloRecibido":"..."}}
        $respuesta = $factura->respuesta_mh;

        // Si respuesta_mh es string (facturas antiguas sin cast), decodificar
        if (is_string($respuesta)) {
            $respuesta = json_decode($respuesta, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('⚠️ Error al decodificar respuesta_mh', [
                    'factura_id' => $factura->id,
                    'error' => json_last_error_msg(),
                ]);
                return null;
            }
        }

        // Caso 1: envio_mh.selloRecibido (estructura actual)
        if (isset($respuesta['envio_mh']['selloRecibido'])) {
            return $respuesta['envio_mh']['selloRecibido'];
        }

        // Caso 2: selloRecibido directo
        if (isset($respuesta['selloRecibido'])) {
            return $respuesta['selloRecibido'];
        }

        // Caso 3: data.selloRecibido
        if (isset($respuesta['data']['selloRecibido'])) {
            return $respuesta['data']['selloRecibido'];
        }

        // Caso 4: body.selloRecibido
        if (isset($respuesta['body']['selloRecibido'])) {
            return $respuesta['body']['selloRecibido'];
        }

        Log::warning('⚠️ No se encontró selloRecibido en respuesta_mh', [
            'factura_id' => $factura->id,
            'numero_control' => $factura->numero_control,
            'tipo_respuesta' => gettype($respuesta),
            'keys_respuesta' => is_array($respuesta) ? array_keys($respuesta) : 'No es array',
        ]);

        return null;
    }

    /**
     * Firma el evento de invalidación
     *
     * @param EventoInvalidacion $evento
     * @return array
     */
    public function firmarEvento(EventoInvalidacion $evento): array
    {
        try {
            $jsonEvento = $this->construirJsonEvento($evento);

            // Guardar JSON en el evento
            $evento->evento_json = $jsonEvento;
            $evento->save();

            Log::info('📝 Firmando evento de invalidación', [
                'codigo_invalidacion' => $evento->codigo_invalidacion,
                'json' => $jsonEvento,
            ]);

            // Enviar a API de firma
            // La API espera la misma estructura que para facturas normales
            $payload = [
                'nit' => config('dte.nit'),
                'activo' => true,  // Boolean, no string
                'passwordPri' => config('dte.password_pri'),
                'dteJson' => $jsonEvento,  // Array directo, NO json_encode()
            ];

            $response = Http::timeout(30)
                ->withOptions([
                    'verify' => config('app.env') === 'production',
                ])
                ->post($this->apiUrl . '/firmardocumento/', $payload);

            if ($response->successful()) {
                $data = $response->json();

                Log::info('📥 Respuesta de API de firma recibida', [
                    'status' => $response->status(),
                    'tiene_body' => isset($data['body']),
                    'tipo_body' => isset($data['body']) ? gettype($data['body']) : 'N/A',
                    'keys' => array_keys($data),
                ]);

                if (isset($data['body'])) {
                    $eventoFirmado = $data['body'];

                    // Verificar que sea un string (JWT), no un array
                    if (is_array($eventoFirmado)) {
                        Log::error('❌ La API de firma devolvió un array en lugar de JWT', [
                            'codigo_invalidacion' => $evento->codigo_invalidacion,
                            'body' => $eventoFirmado,
                        ]);

                        $evento->estado = 'error';
                        $evento->save();

                        return [
                            'success' => false,
                            'message' => 'Error: La API de firma devolvió un formato inválido (array en lugar de JWT string)',
                        ];
                    }

                    Log::info('✅ Evento de invalidación firmado exitosamente', [
                        'codigo_invalidacion' => $evento->codigo_invalidacion,
                        'jwt_length' => strlen($eventoFirmado),
                    ]);

                    // Guardar evento firmado
                    $evento->evento_firmado = $eventoFirmado;
                    $evento->estado = 'firmado';
                    $evento->save();

                    return [
                        'success' => true,
                        'message' => 'Evento firmado correctamente',
                        'evento_firmado' => $eventoFirmado,
                    ];
                }

                // Si no hay 'body' en la respuesta
                Log::error('❌ Respuesta de firma sin campo "body"', [
                    'codigo_invalidacion' => $evento->codigo_invalidacion,
                    'response' => $data,
                ]);

                $evento->estado = 'error';
                $evento->save();

                return [
                    'success' => false,
                    'message' => 'Error: La respuesta de firma no contiene el campo "body"',
                ];
            }

            $error = $response->json();
            Log::error('❌ Error al firmar evento de invalidación', [
                'status' => $response->status(),
                'error' => $error,
            ]);

            $evento->estado = 'error';
            $evento->save();

            return [
                'success' => false,
                'message' => 'Error al firmar evento: ' . json_encode($error),
            ];
        } catch (\Exception $e) {
            Log::error('❌ Excepción al firmar evento de invalidación', [
                'exception' => $e->getMessage(),
            ]);

            $evento->estado = 'error';
            $evento->save();

            return [
                'success' => false,
                'message' => 'Error al firmar evento: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Envía el evento de invalidación al MH
     *
     * @param EventoInvalidacion $evento
     * @return array
     */
    public function enviarEvento(EventoInvalidacion $evento): array
    {
        if (!$evento->evento_firmado) {
            return [
                'success' => false,
                'message' => 'El evento debe estar firmado antes de enviarlo',
            ];
        }

        try {
            $resultado = $this->mhApiService->enviarInvalidacion(
                $evento->evento_firmado,
                $evento->version
            );

            // Guardar respuesta del MH
            $evento->respuesta_mh = $resultado['data'] ?? null;

            if ($resultado['success']) {
                $evento->estado = 'procesado';
                $evento->sello_recepcion = $resultado['sello_recepcion'];
                $evento->fecha_procesamiento = now();

                // Marcar la factura como invalidada
                $factura = $evento->factura;
                $factura->invalidada = true;
                $factura->evento_invalidacion_id = $evento->id;
                $factura->save();

                Log::info('✅ Evento de invalidación procesado exitosamente', [
                    'codigo_invalidacion' => $evento->codigo_invalidacion,
                    'factura_id' => $factura->id,
                    'numero_control' => $factura->numero_control,
                ]);
            } else {
                $evento->estado = 'error';

                Log::error('❌ Error al procesar evento de invalidación', [
                    'codigo_invalidacion' => $evento->codigo_invalidacion,
                    'error' => $resultado['message'],
                ]);
            }

            $evento->save();

            return $resultado;
        } catch (\Exception $e) {
            Log::error('❌ Excepción al enviar evento de invalidación', [
                'exception' => $e->getMessage(),
            ]);

            $evento->estado = 'error';
            $evento->save();

            return [
                'success' => false,
                'message' => 'Error al enviar evento: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Procesa el evento completo: crear, firmar y enviar
     *
     * @param array $data
     * @return array
     */
    public function procesarInvalidacion(array $data): array
    {
        try {
            // 1. Validar que la factura pueda ser invalidada
            $validacion = $this->validarInvalidacion($data['factura_id']);
            if (!$validacion['puede_invalidarse']) {
                return [
                    'success' => false,
                    'message' => $validacion['mensaje'],
                ];
            }

            // 2. Crear evento
            $evento = $this->crearEvento($data);

            // 3. Firmar evento
            $resultadoFirma = $this->firmarEvento($evento);
            if (!$resultadoFirma['success']) {
                return $resultadoFirma;
            }

            // 4. Enviar al MH
            $resultadoEnvio = $this->enviarEvento($evento);

            return array_merge($resultadoEnvio, [
                'evento' => $evento,
            ]);
        } catch (\Exception $e) {
            Log::error('❌ Error al procesar invalidación', [
                'exception' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Error al procesar invalidación: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Valida si una factura puede ser invalidada
     *
     * @param int $facturaId
     * @return array
     */
    public function validarInvalidacion(int $facturaId): array
    {
        $factura = Factura::find($facturaId);

        if (!$factura) {
            return [
                'puede_invalidarse' => false,
                'mensaje' => 'Factura no encontrada',
            ];
        }

        // Verificar que la factura fue enviada exitosamente al MH
        if ($factura->estado !== 'enviado_mh') {
            return [
                'puede_invalidarse' => false,
                'mensaje' => 'Solo se pueden invalidar facturas que fueron enviadas exitosamente al MH',
            ];
        }

        // Verificar que no esté ya invalidada
        if ($factura->invalidada) {
            return [
                'puede_invalidarse' => false,
                'mensaje' => 'Esta factura ya fue invalidada',
            ];
        }

        // Validar plazos según tipo de DTE
        $validacionPlazo = $this->validarPlazos($factura);
        if (!$validacionPlazo['valido']) {
            return [
                'puede_invalidarse' => false,
                'mensaje' => $validacionPlazo['mensaje'],
            ];
        }

        return [
            'puede_invalidarse' => true,
            'mensaje' => 'La factura puede ser invalidada',
        ];
    }

    /**
     * Valida los plazos para invalidación según tipo de DTE
     *
     * @param Factura $factura
     * @return array
     */
    private function validarPlazos(Factura $factura): array
    {
        $fechaEmision = $factura->fecha_emision;
        $fechaTransmision = $factura->created_at;
        $hoy = now();

        $diasDesdeEmision = $fechaEmision->diffInDays($hoy);
        $diasDesdeTransmision = $fechaTransmision->diffInDays($hoy);

        // Para DTE-03 (CCF), DTE-05 (Nota de Crédito): Máximo 1 día
        if (in_array($factura->tipo_dte, ['03', '05'])) {
            if ($diasDesdeEmision > 1 || $diasDesdeTransmision > 1) {
                return [
                    'valido' => false,
                    'mensaje' => 'El plazo para invalidar CCF o Nota de Crédito es de máximo 1 día desde la emisión y transmisión',
                ];
            }
        }

        // Para DTE-01 (Factura), DTE-14 (Sujeto Excluido): Máximo 3 meses
        if (in_array($factura->tipo_dte, ['01', '14'])) {
            if ($diasDesdeEmision > 90 || $diasDesdeTransmision > 90) {
                return [
                    'valido' => false,
                    'mensaje' => 'El plazo para invalidar Factura o Factura de Sujeto Excluido es de máximo 3 meses desde la emisión y transmisión',
                ];
            }
        }

        return [
            'valido' => true,
            'mensaje' => 'Dentro del plazo permitido',
        ];
    }
}
