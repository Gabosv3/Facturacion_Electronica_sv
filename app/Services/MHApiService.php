<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\TokenMH;
use Carbon\Carbon;

class MHApiService
{
    private $authUrl;
    private $recepcionUrl;
    private $consultaUrl;
    private $user;
    private $password;
    private $timeoutEnvio;
    private $maxReintentos;

    public function __construct()
    {
        $this->authUrl = config('dte.mh_auth_url');
        $this->recepcionUrl = config('dte.mh_recepcion_url');
        $this->consultaUrl = config('dte.mh_consulta_url');
        $this->user = config('dte.mh_user');
        $this->password = config('dte.mh_password');
        $this->timeoutEnvio = config('dte.timeout_envio', 8);
        $this->maxReintentos = config('dte.max_reintentos', 2);
    }

    /**
     * Obtener token válido del MH (genera nuevo si es necesario)
     */
    public function obtenerToken(): ?string
    {
        // Buscar token válido en BD
        $tokenActivo = TokenMH::where('is_active', true)
            ->where('expires_at', '>', Carbon::now())
            ->orderBy('created_at', 'desc')
            ->first();

        if ($tokenActivo && $tokenActivo->isValid()) {
            Log::info('Token MH válido encontrado en BD');
            return $tokenActivo->token;
        }

        // Si no hay token válido, generar uno nuevo
        return $this->generarNuevoToken();
    }

    /**
     * Generar nuevo token del MH
     */
    private function generarNuevoToken(): ?string
    {
        try {
            Log::info('Generando nuevo token del MH', [
                'url' => $this->authUrl,
                'user' => $this->user
            ]);

            // Desactivar tokens anteriores
            TokenMH::where('is_active', true)->update(['is_active' => false]);

            // Solicitar nuevo token
            $response = Http::asForm()
                ->withOptions([
                    'verify' => config('app.env') === 'production', // Solo verificar SSL en producción
                ])
                ->timeout(30)
                ->post($this->authUrl, [
                    'user' => $this->user,
                    'pwd' => $this->password,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                
                if (isset($data['body']['token'])) {
                    // Extraer solo el token JWT (sin "Bearer")
                    $token = $data['body']['token'];
                    
                    // Si el token viene con "Bearer " al inicio, quitarlo
                    if (strpos($token, 'Bearer ') === 0) {
                        $token = substr($token, 7);
                    }
                    
                    // Guardar token en BD (expira en 24 horas)
                    $tokenMH = TokenMH::create([
                        'token' => $token,
                        'expires_at' => Carbon::now()->addHours(23)->addMinutes(50), // 23h 50min para renovar antes
                        'is_active' => true,
                    ]);

                    Log::info('Token MH generado exitosamente', [
                        'expires_at' => $tokenMH->expires_at,
                        'token_length' => strlen($token)
                    ]);

                    return $token;
                } else {
                    Log::error('Respuesta del MH sin token', ['response' => $data]);
                    return null;
                }
            } else {
                Log::error('Error al generar token MH', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return null;
            }
        } catch (\Exception $e) {
            Log::error('Excepción al generar token MH: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Consultar estado de un DTE en el MH
     * Retorna true si el documento ya fue recibido, false si no
     */
    public function consultarEstadoDTE(string $ambiente, string $codigoGeneracion): array
    {
        try {
            $token = $this->obtenerToken();

            if (!$token) {
                return [
                    'recibido' => false,
                    'error' => 'No se pudo obtener token del Ministerio de Hacienda',
                ];
            }

            Log::info('🔍 Consultando estado de DTE en MH', [
                'codigo_generacion' => $codigoGeneracion,
                'ambiente' => $ambiente,
            ]);

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $token,
                'User-Agent' => config('dte.user_agent', 'SistemaFacturacion/1.0'),
            ])
            ->withOptions([
                'verify' => config('app.env') === 'production',
            ])
            ->timeout(10)
            ->post($this->consultaUrl, [
                'ambiente' => $ambiente,
                'codigoGeneracion' => $codigoGeneracion,
            ]);

            if ($response->successful()) {
                $data = $response->json();

                Log::info('✅ Consulta de estado exitosa', [
                    'response' => $data,
                ]);

                // Verificar si el documento ya fue recibido
                // El MH puede retornar diferentes estados
                $estado = $data['estado'] ?? null;
                $recibido = in_array($estado, ['PROCESADO', 'RECIBIDO', 'APROBADO']);

                return [
                    'recibido' => $recibido,
                    'estado' => $estado,
                    'data' => $data,
                ];
            } else {
                Log::warning('⚠️ Error al consultar estado DTE', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                // Si falla la consulta, asumimos que no fue recibido para reintentar
                return [
                    'recibido' => false,
                    'error' => 'Error al consultar estado: ' . $response->body(),
                ];
            }
        } catch (\Exception $e) {
            Log::error('❌ Excepción al consultar estado DTE: ' . $e->getMessage());

            return [
                'recibido' => false,
                'error' => 'Excepción al consultar estado: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Enviar DTE firmado al MH con política de reintentos
     * Implementa la política oficial del MH:
     * - Timeout de 8 segundos
     * - Si falla, consultar estado
     * - Reintentar hasta 2 veces si no fue recibido
     */
    public function enviarDTE(string $dteFirmado, array $metadata): array
    {
        // Validar que el JWT no esté vacío
        if (empty($dteFirmado) || strlen($dteFirmado) < 100) {
            Log::error('❌ JWT VACÍO O MUY CORTO', [
                'longitud' => strlen($dteFirmado),
                'contenido' => $dteFirmado
            ]);
            return [
                'success' => false,
                'message' => 'El documento firmado está vacío o es inválido',
            ];
        }

        // Preparar el JSON para enviar
        $payload = [
            'ambiente' => $metadata['ambiente'] ?? '00',
            'idEnvio' => $metadata['id_envio'] ?? 1,
            'version' => $metadata['version'] ?? 2,
            'tipoDte' => $metadata['tipo_dte'] ?? '01',
            'documento' => $dteFirmado,
        ];

        $codigoGeneracion = $metadata['codigo_generacion'] ?? null;

        // 🔍 LOG: Ver payload completo antes de enviar al MH
        Log::info('📤 PAYLOAD COMPLETO ANTES DE ENVIAR AL MH', [
            'ambiente' => $payload['ambiente'],
            'idEnvio' => $payload['idEnvio'],
            'version' => $payload['version'],
            'tipoDte' => $payload['tipoDte'],
            'documento_jwt_length' => strlen($payload['documento']),
            'documento_jwt_primeros_100' => substr($payload['documento'], 0, 100),
        ]);

        // Intentar enviar con reintentos (máximo 2 reintentos = 3 intentos totales)
        $intentos = 0;
        $maxIntentos = $this->maxReintentos + 1; // 2 reintentos + 1 intento inicial = 3
        $ultimoError = null;
        $ultimoResultado = null;

        while ($intentos < $maxIntentos) {
            $intentos++;

            Log::info('📤 Intento ' . $intentos . ' de ' . $maxIntentos . ' - Enviando DTE al MH', [
                'url' => $this->recepcionUrl,
                'ambiente' => $payload['ambiente'],
                'tipoDte' => $payload['tipoDte'],
                'codigoGeneracion' => $codigoGeneracion,
                'timeout' => $this->timeoutEnvio . ' segundos',
            ]);

            try {
                $resultado = $this->intentarEnviarDTE($payload);

                // Guardar el último resultado y error
                $ultimoResultado = $resultado;
                $ultimoError = $resultado['message'] ?? 'Error desconocido';

                // Si el envío fue exitoso, retornar
                if ($resultado['success']) {
                    Log::info('✅ DTE enviado exitosamente en intento ' . $intentos, [
                        'intentos_realizados' => $intentos,
                    ]);

                    $resultado['intentos'] = $intentos;
                    return $resultado;
                }

                // Si el envío falló, verificar si fue rechazado
                $rechazado = $resultado['rechazado'] ?? false;

                Log::warning('⚠️ Intento ' . $intentos . ' falló', [
                    'error' => $resultado['message'],
                    'fue_timeout' => $resultado['timeout'] ?? false,
                    'fue_rechazado' => $rechazado,
                ]);

                // Si fue rechazado por el MH, no reintentar (error de negocio)
                if ($rechazado) {
                    Log::error('❌ DTE rechazado por el MH, no se reintentará', [
                        'intentos_realizados' => $intentos,
                        'razon' => $resultado['message'],
                    ]);

                    return [
                        'success' => false,
                        'message' => 'DTE rechazado por el MH: ' . $resultado['message'],
                        'data' => $resultado['data'] ?? [],
                        'response' => $resultado['response'] ?? [],
                        'intentos' => $intentos,
                        'rechazado' => true,
                    ];
                }

                // Si aún quedan intentos, consultar estado antes de reintentar
                if ($intentos < $maxIntentos && $codigoGeneracion) {
                    Log::info('🔍 Consultando estado antes de reintentar...');

                    $estadoConsulta = $this->consultarEstadoDTE(
                        $payload['ambiente'],
                        $codigoGeneracion
                    );

                    // Si el documento ya fue recibido, no reintentar
                    if ($estadoConsulta['recibido']) {
                        Log::info('✅ DTE ya fue recibido por el MH (detectado en consulta)', [
                            'estado' => $estadoConsulta['estado'] ?? 'desconocido',
                            'intentos_realizados' => $intentos,
                        ]);

                        return [
                            'success' => true,
                            'message' => 'DTE ya fue recibido por el Ministerio de Hacienda',
                            'data' => $estadoConsulta['data'] ?? [],
                            'intentos' => $intentos,
                            'recibido_en_consulta' => true,
                        ];
                    }

                    Log::info('↻ DTE no fue recibido, reintentando...', [
                        'proximo_intento' => $intentos + 1,
                    ]);

                    // Esperar 1 segundo antes de reintentar
                    sleep(1);
                }

            } catch (\Exception $e) {
                Log::error('❌ Excepción en intento ' . $intentos . ': ' . $e->getMessage());

                // Guardar el último error
                $ultimoError = $e->getMessage();

                // Si es el último intento, retornar error
                if ($intentos >= $maxIntentos) {
                    return [
                        'success' => false,
                        'message' => 'Error después de ' . $intentos . ' intentos: ' . $e->getMessage(),
                        'ultimo_error' => $ultimoError,
                        'intentos' => $intentos,
                    ];
                }

                // Si aún quedan intentos, consultar estado
                if ($codigoGeneracion) {
                    $estadoConsulta = $this->consultarEstadoDTE(
                        $payload['ambiente'],
                        $codigoGeneracion
                    );

                    if ($estadoConsulta['recibido']) {
                        return [
                            'success' => true,
                            'message' => 'DTE ya fue recibido por el Ministerio de Hacienda',
                            'data' => $estadoConsulta['data'] ?? [],
                            'intentos' => $intentos,
                            'recibido_en_consulta' => true,
                        ];
                    }
                }

                sleep(1);
            }
        }

        // Si llegamos aquí, todos los intentos fallaron
        return [
            'success' => false,
            'message' => $ultimoError ?? 'Error al enviar DTE después de ' . $intentos . ' intentos',
            'ultimo_error' => $ultimoError,
            'ultimo_resultado' => $ultimoResultado,
            'intentos' => $intentos,
        ];
    }

    /**
     * Realizar un intento de envío de DTE al MH
     * Timeout de 8 segundos según política del MH
     */
    private function intentarEnviarDTE(array $payload): array
    {
        try {
            // Obtener token válido
            $token = $this->obtenerToken();

            if (!$token) {
                return [
                    'success' => false,
                    'message' => 'No se pudo obtener token del Ministerio de Hacienda',
                ];
            }

            // Enviar al MH con timeout de 8 segundos
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $token,
                'User-Agent' => config('dte.user_agent', 'SistemaFacturacion/1.0'),
            ])
            ->withOptions([
                'verify' => config('app.env') === 'production',
            ])
            ->timeout($this->timeoutEnvio)
            ->post($this->recepcionUrl, $payload);

            if ($response->successful()) {
                $data = $response->json();

                // El MH puede retornar 200 pero con estado RECHAZADO
                $estadoMH = $data['estado'] ?? null;

                if ($estadoMH === 'RECHAZADO') {
                    Log::warning('⚠️ DTE rechazado por el MH', [
                        'codigo' => $data['codigoMsg'] ?? 'N/A',
                        'mensaje' => $data['descripcionMsg'] ?? 'Sin descripción',
                        'response' => $data
                    ]);

                    return [
                        'success' => false,
                        'message' => $data['descripcionMsg'] ?? 'DTE rechazado por el MH',
                        'data' => $data,
                        'response' => $data,
                        'rechazado' => true, // Indica que fue rechazado, no debe reintentar
                    ];
                }

                Log::info('✅ Respuesta exitosa del MH', [
                    'response' => $data
                ]);

                return [
                    'success' => true,
                    'message' => 'DTE enviado exitosamente al Ministerio de Hacienda',
                    'data' => $data,
                    'response' => $data,
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Error al enviar DTE: ' . $response->body(),
                    'status' => $response->status(),
                    'response' => $response->json(),
                ];
            }
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            // Timeout o error de conexión
            Log::error('⏱️ Timeout o error de conexión al enviar DTE', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Timeout al enviar DTE (>' . $this->timeoutEnvio . 's)',
                'timeout' => true,
            ];
        } catch (\Exception $e) {
            Log::error('❌ Excepción al enviar DTE: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Error al enviar DTE: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Renovar token manualmente (comando artisan)
     */
    public function renovarToken(): bool
    {
        $token = $this->generarNuevoToken();
        return $token !== null;
    }

    /*
    |--------------------------------------------------------------------------
    | Métodos Públicos de Consulta según especificaciones MH
    |--------------------------------------------------------------------------
    */

    /**
     * Consulta de DTE según especificaciones del Ministerio de Hacienda
     * POST https://apitest.dtes.mh.gob.sv/fesv/recepcion/consultadte/
     *
     * @param string $nitEmisor NIT del emisor sin guiones
     * @param string $tdte Tipo de DTE (01, 03, 05, etc.)
     * @param string $codigoGeneracion Código de generación del documento
     * @return array
     */
    public function consultarDTE(string $nitEmisor, string $tdte, string $codigoGeneracion): array
    {
        try {
            $token = $this->obtenerToken();

            if (!$token) {
                return [
                    'success' => false,
                    'message' => 'No se pudo obtener token del Ministerio de Hacienda',
                ];
            }

            $consultaDteUrl = config('dte.mh_consulta_dte_url');

            Log::info('🔍 Consultando DTE en MH', [
                'url' => $consultaDteUrl,
                'nitEmisor' => $nitEmisor,
                'tdte' => $tdte,
                'codigoGeneracion' => $codigoGeneracion,
            ]);

            // Construir el payload
            $payload = [
                'nitEmisor' => $nitEmisor,
                'tdte' => $tdte,
                'codigoGeneracion' => $codigoGeneracion,
            ];

            Log::info('📤 Payload enviado a MH para consulta DTE', [
                'payload' => $payload,
                'headers' => [
                    'Authorization' => 'Bearer ' . substr($token, 0, 20) . '...',
                    'User-Agent' => config('dte.user_agent', 'SistemaFacturacion/1.0'),
                ],
            ]);

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $token, // IMPORTANTE: Agregar "Bearer " al token
                'User-Agent' => config('dte.user_agent', 'SistemaFacturacion/1.0'),
            ])
            ->withOptions([
                'verify' => config('app.env') === 'production',
            ])
            ->timeout(30)
            ->post($consultaDteUrl, $payload);

            // Log de respuesta cruda
            Log::info('📥 Respuesta cruda del MH', [
                'status' => $response->status(),
                'headers' => $response->headers(),
                'body' => $response->body(),
                'json' => $response->json(),
            ]);

            if ($response->successful()) {
                $data = $response->json();

                Log::info('✅ Consulta de DTE exitosa', [
                    'response' => $data,
                ]);

                return [
                    'success' => true,
                    'message' => 'DTE consultado exitosamente',
                    'data' => $data,
                ];
            }

            // Intentar obtener JSON de error
            $error = $response->json();
            $errorBody = $response->body();

            Log::error('❌ Error al consultar DTE', [
                'status' => $response->status(),
                'error_json' => $error,
                'error_body' => $errorBody,
                'headers' => $response->headers(),
            ]);

            return [
                'success' => false,
                'message' => 'Error al consultar DTE: ' . ($error['descripcionMsg'] ?? $error['mensaje'] ?? $errorBody),
                'data' => $error,
                'status' => $response->status(),
                'raw_body' => $errorBody,
            ];
        } catch (\Exception $e) {
            Log::error('❌ Excepción al consultar DTE', [
                'exception' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Error al consultar DTE: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Consulta de Lote DTE según especificaciones del Ministerio de Hacienda
     * GET https://apitest.dtes.mh.gob.sv/fesv/recepcion/consultadtelote/{codigoLote}
     *
     * @param string $codigoLote Código de lote asignado durante transmisión
     * @return array
     */
    public function consultarDTELote(string $codigoLote): array
    {
        try {
            $token = $this->obtenerToken();

            if (!$token) {
                return [
                    'success' => false,
                    'message' => 'No se pudo obtener token del Ministerio de Hacienda',
                ];
            }

            $consultaDteLoteUrl = config('dte.mh_consulta_dte_lote_url') . '/' . $codigoLote;

            Log::info('🔍 Consultando Lote DTE en MH', [
                'url' => $consultaDteLoteUrl,
                'codigoLote' => $codigoLote,
                'headers' => [
                    'Authorization' => 'Bearer ' . substr($token, 0, 20) . '...',
                    'User-Agent' => config('dte.user_agent', 'SistemaFacturacion/1.0'),
                ],
            ]);

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $token, // IMPORTANTE: Agregar "Bearer " al token
                'User-Agent' => config('dte.user_agent', 'SistemaFacturacion/1.0'),
            ])
            ->withOptions([
                'verify' => config('app.env') === 'production',
            ])
            ->timeout(30)
            ->get($consultaDteLoteUrl); // GET según especificación MH

            // Log de respuesta cruda
            Log::info('📥 Respuesta cruda del MH (Lote)', [
                'status' => $response->status(),
                'headers' => $response->headers(),
                'body' => $response->body(),
                'json' => $response->json(),
            ]);

            if ($response->successful()) {
                $data = $response->json();

                Log::info('✅ Consulta de Lote DTE exitosa', [
                    'codigoLote' => $codigoLote,
                    'response' => $data,
                ]);

                return [
                    'success' => true,
                    'message' => 'Lote DTE consultado exitosamente',
                    'data' => $data,
                ];
            }

            // Intentar obtener JSON de error
            $error = $response->json();
            $errorBody = $response->body();

            Log::error('❌ Error al consultar Lote DTE', [
                'status' => $response->status(),
                'error_json' => $error,
                'error_body' => $errorBody,
                'headers' => $response->headers(),
            ]);

            return [
                'success' => false,
                'message' => 'Error al consultar Lote DTE: ' . ($error['descripcionMsg'] ?? $error['mensaje'] ?? $errorBody),
                'data' => $error,
                'status' => $response->status(),
                'raw_body' => $errorBody,
            ];
        } catch (\Exception $e) {
            Log::error('❌ Excepción al consultar Lote DTE', [
                'exception' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Error al consultar Lote DTE: ' . $e->getMessage(),
            ];
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Métodos para Contingencia
    |--------------------------------------------------------------------------
    */

    /**
     * Enviar evento de contingencia al MH
     */
    public function enviarEventoContingencia(array $eventoJson, string $eventoFirmado): array
    {
        try {
            $token = $this->obtenerToken();

            if (!$token) {
                return [
                    'success' => false,
                    'message' => 'No se pudo obtener token del Ministerio de Hacienda',
                ];
            }

            $contingenciaUrl = config('dte.mh_contingencia_url');

            Log::info('📤 Enviando evento de contingencia al MH', [
                'url' => $contingenciaUrl,
                'codigo_evento' => $eventoJson['dteJson']['identificacion']['codigoGeneracion'],
                'json_completo' => json_encode($eventoJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
                'jwt_firmado_length' => strlen($eventoFirmado),
            ]);

            // Según documentación MH: enviar nit y documento (JWT firmado)
            $payload = [
                'nit' => config('dte.nit'),
                'documento' => $eventoFirmado, // JWT firmado
            ];

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $token,
                'User-Agent' => config('dte.user_agent', 'SistemaFacturacion/1.0'),
            ])
            ->withOptions([
                'verify' => config('app.env') === 'production',
            ])
            ->timeout(30)
            ->post($contingenciaUrl, $payload);

            if ($response->successful()) {
                $data = $response->json();

                Log::info('✅ Evento de contingencia enviado exitosamente', [
                    'response' => $data,
                ]);

                return [
                    'success' => true,
                    'message' => 'Evento de contingencia enviado exitosamente',
                    'data' => $data,
                ];
            }

            $error = $response->json();
            Log::error('❌ Error al enviar evento de contingencia', [
                'status' => $response->status(),
                'error' => $error,
            ]);

            return [
                'success' => false,
                'message' => 'Error al enviar evento de contingencia: ' . json_encode($error),
                'data' => $error,
            ];
        } catch (\Exception $e) {
            Log::error('❌ Excepción al enviar evento de contingencia', [
                'exception' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Error al enviar evento de contingencia: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Enviar lote de DTEs al MH
     */
    public function enviarLote(array $loteJson): array
    {
        try {
            $token = $this->obtenerToken();

            if (!$token) {
                return [
                    'success' => false,
                    'message' => 'No se pudo obtener token del Ministerio de Hacienda',
                ];
            }

            $loteUrl = config('dte.mh_recepcion_lote_url');

            Log::info('📦 Enviando lote de DTEs al MH', [
                'url' => $loteUrl,
                'cantidad_dtes' => count($loteJson['documento']['listadoDte'] ?? []),
                'codigo_lote' => $loteJson['documento']['codigoLote'] ?? 'N/A',
            ]);

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $token,
                'User-Agent' => config('dte.user_agent', 'SistemaFacturacion/1.0'),
            ])
            ->withOptions([
                'verify' => config('app.env') === 'production',
            ])
            ->timeout(60) // Mayor timeout para lotes
            ->post($loteUrl, $loteJson);

            if ($response->successful()) {
                $data = $response->json();

                Log::info('✅ Lote enviado exitosamente', [
                    'response' => $data,
                    'estado' => $data['estado'] ?? 'N/A',
                    'codigo_lote' => $data['codigoLote'] ?? 'N/A',
                ]);

                return [
                    'success' => true,
                    'message' => 'Lote enviado exitosamente',
                    'data' => $data,
                    'codigo_lote' => $data['codigoLote'] ?? null,
                ];
            }

            $error = $response->json();
            Log::error('❌ Error al enviar lote', [
                'status' => $response->status(),
                'error' => $error,
            ]);

            return [
                'success' => false,
                'message' => 'Error al enviar lote: ' . json_encode($error),
                'data' => $error,
            ];
        } catch (\Exception $e) {
            Log::error('❌ Excepción al enviar lote', [
                'exception' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Error al enviar lote: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Consultar estado de un lote
     */
    public function consultarLote(string $codigoLote): array
    {
        try {
            $token = $this->obtenerToken();

            if (!$token) {
                return [
                    'success' => false,
                    'message' => 'No se pudo obtener token del Ministerio de Hacienda',
                ];
            }

            $consultaLoteUrl = config('dte.mh_consulta_lote_url');

            Log::info('🔍 Consultando estado de lote', [
                'url' => $consultaLoteUrl,
                'codigo_lote' => $codigoLote,
            ]);

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $token,
                'User-Agent' => config('dte.user_agent', 'SistemaFacturacion/1.0'),
            ])
            ->withOptions([
                'verify' => config('app.env') === 'production',
            ])
            ->timeout(30)
            ->post($consultaLoteUrl, [
                'codigo' => $codigoLote,
            ]);

            if ($response->successful()) {
                $data = $response->json();

                Log::info('✅ Estado de lote consultado', [
                    'codigo_lote' => $codigoLote,
                    'estado' => $data['estado'] ?? 'N/A',
                ]);

                return [
                    'success' => true,
                    'message' => 'Lote consultado exitosamente',
                    'data' => $data,
                    'estado' => $data['estado'] ?? null,
                ];
            }

            $error = $response->json();
            Log::error('❌ Error al consultar lote', [
                'status' => $response->status(),
                'error' => $error,
            ]);

            return [
                'success' => false,
                'message' => 'Error al consultar lote: ' . json_encode($error),
                'data' => $error,
            ];
        } catch (\Exception $e) {
            Log::error('❌ Excepción al consultar lote', [
                'exception' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Error al consultar lote: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Enviar evento de invalidación al MH
     *
     * @param string $eventoFirmado JSON del evento de invalidación firmado
     * @param int $version Versión del JSON (default: 1)
     * @return array Respuesta del MH
     */
    public function enviarInvalidacion(string $eventoFirmado, int $version = 1): array
    {
        try {
            $token = $this->obtenerToken();

            if (!$token) {
                return [
                    'success' => false,
                    'message' => 'No se pudo obtener token del MH',
                ];
            }

            $ambiente = config('dte.ambiente');
            $invalidacionUrl = config('dte.mh_invalidacion_url');
            $userAgent = config('dte.user_agent', 'SistemaFacturacion/1.0');

            // ID de envío correlativo
            $idEnvio = time();

            Log::info('🔄 Enviando evento de invalidación al MH', [
                'url' => $invalidacionUrl,
                'ambiente' => $ambiente,
                'version' => $version,
                'idEnvio' => $idEnvio,
            ]);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'User-Agent' => $userAgent,
                'Content-Type' => 'application/json',
            ])
            ->withOptions([
                'verify' => config('app.env') === 'production',
            ])
            ->timeout(30)
            ->post($invalidacionUrl, [
                'ambiente' => $ambiente,
                'idEnvio' => $idEnvio,
                'version' => $version,
                'documento' => $eventoFirmado,
            ]);

            if ($response->successful()) {
                $data = $response->json();

                Log::info('✅ Evento de invalidación enviado exitosamente', [
                    'response' => $data,
                ]);

                return [
                    'success' => true,
                    'message' => 'Evento de invalidación enviado correctamente',
                    'data' => $data,
                    'sello_recepcion' => $data['selloRecibido'] ?? null,
                ];
            }

            $error = $response->json();
            Log::error('❌ Error al enviar evento de invalidación', [
                'status' => $response->status(),
                'error' => $error,
            ]);

            return [
                'success' => false,
                'message' => 'Error al enviar evento de invalidación: ' . ($error['descripcionMsg'] ?? json_encode($error)),
                'data' => $error,
            ];
        } catch (\Exception $e) {
            Log::error('❌ Excepción al enviar evento de invalidación', [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'Error al enviar evento de invalidación: ' . $e->getMessage(),
            ];
        }
    }
}