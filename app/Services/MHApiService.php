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
    private $user;
    private $password;

    public function __construct()
    {
        $this->authUrl = config('dte.mh_auth_url');
        $this->recepcionUrl = config('dte.mh_recepcion_url');
        $this->user = config('dte.mh_user');
        $this->password = config('dte.mh_password');
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
     * Enviar DTE firmado al MH
     */
    public function enviarDTE(string $dteFirmado, array $metadata): array
    {
        try {
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

            // Obtener token válido
            $token = $this->obtenerToken();
            
            if (!$token) {
                return [
                    'success' => false,
                    'message' => 'No se pudo obtener token del Ministerio de Hacienda',
                ];
            }

            // Preparar el JSON para enviar
            $payload = [
                'ambiente' => $metadata['ambiente'] ?? '00',
                'idEnvio' => $metadata['id_envio'] ?? 1,
                'version' => $metadata['version'] ?? 2,
                'tipoDte' => $metadata['tipo_dte'] ?? '01',
                'documento' => $dteFirmado, // El JWT firmado como STRING
            ];

            // Validar que documento no esté vacío en el payload
            if (empty($payload['documento'])) {
                Log::error('❌ PAYLOAD CON DOCUMENTO VACÍO', [
                    'payload_keys' => array_keys($payload),
                    'documento_value' => $payload['documento']
                ]);
                return [
                    'success' => false,
                    'message' => 'Error interno: documento vacío en payload',
                ];
            }

            Log::info('📤 PAYLOAD COMPLETO QUE SE ENVIARÁ AL MH', [
                'ambiente' => $payload['ambiente'],
                'idEnvio' => $payload['idEnvio'],
                'version' => $payload['version'],
                'tipoDte' => $payload['tipoDte'],
                'documento_length' => strlen($payload['documento']),
                'documento_primeros_50' => substr($payload['documento'], 0, 50),
                'documento_ultimos_50' => substr($payload['documento'], -50),
                'documento_vacio' => empty($payload['documento']),
                'documento_es_string' => is_string($payload['documento']),
            ]);

            Log::info('Enviando DTE al MH', [
                'url' => $this->recepcionUrl,
                'ambiente' => $payload['ambiente'],
                'tipoDte' => $payload['tipoDte'],
            ]);

            // Enviar al MH
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $token,
                'User-Agent' => config('dte.user_agent', 'SistemaFacturacion/1.0'),
            ])
            ->withOptions([
                'verify' => config('app.env') === 'production', // Solo verificar SSL en producción
            ])
            ->timeout(60)
            ->post($this->recepcionUrl, $payload);

            if ($response->successful()) {
                $data = $response->json();
                
                Log::info('DTE enviado exitosamente al MH', [
                    'response' => $data
                ]);

                return [
                    'success' => true,
                    'message' => 'DTE enviado exitosamente al Ministerio de Hacienda',
                    'data' => $data,
                    'response' => $response->json(),
                ];
            } else {
                Log::error('Error al enviar DTE al MH', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);

                return [
                    'success' => false,
                    'message' => 'Error al enviar DTE al Ministerio: ' . $response->body(),
                    'status' => $response->status(),
                    'response' => $response->json(),
                ];
            }
        } catch (\Exception $e) {
            Log::error('Excepción al enviar DTE al MH: ' . $e->getMessage());
            
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
}