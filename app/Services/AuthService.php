<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AuthService
{
    private $loginUrl;

    public function __construct()
    {
        $this->loginUrl = config('services.motor_api.url') . '/login';
    }

    /**
     * Autenticar usuario contra el servidor externo
     */
    public function authenticate(string $email, string $password): array
    {
        try {
            $response = Http::timeout(10)->post($this->loginUrl, [
                'email' => $email,
                'password' => $password,
            ]);

            if ($response->successful()) {
                $data = $response->json();

                // Verificar que la respuesta tenga el token
                if (isset($data['token']) || isset($data['data']['token'])) {
                    $token = $data['token'] ?? $data['data']['token'];
                    $user = $data['user'] ?? $data['data'] ?? [];

                    Log::info('Autenticación exitosa', [
                        'email' => $email,
                        'user' => $user
                    ]);

                    return [
                        'success' => true,
                        'token' => $token,
                        'user' => $user,
                        'message' => 'Autenticación exitosa'
                    ];
                }

                Log::warning('Respuesta de autenticación sin token', [
                    'email' => $email,
                    'response' => $data
                ]);

                return [
                    'success' => false,
                    'message' => 'Respuesta inválida del servidor'
                ];
            }

            // Si el status es 401 o 422, las credenciales son inválidas
            if ($response->status() == 401 || $response->status() == 422) {
                Log::warning('Credenciales inválidas', [
                    'email' => $email,
                    'status' => $response->status()
                ]);

                return [
                    'success' => false,
                    'message' => 'Email o contraseña incorrectos'
                ];
            }

            Log::error('Error en autenticación', [
                'email' => $email,
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return [
                'success' => false,
                'message' => 'Error en el servidor de autenticación'
            ];
        } catch (\Exception $e) {
            Log::error('Excepción en autenticación', [
                'email' => $email,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Error de conexión: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Validar que el token sea válido
     */
    public function validateToken(string $token): bool
    {
        try {
            $response = Http::timeout(10)
                ->withToken($token)
                ->get($this->loginUrl . '/verify');

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Error validando token', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Refrescar el token
     */
    public function refreshToken(string $token): ?string
    {
        try {
            $response = Http::timeout(10)
                ->withToken($token)
                ->post($this->loginUrl . '/refresh');

            if ($response->successful()) {
                $data = $response->json();
                return $data['token'] ?? $data['data']['token'] ?? null;
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Error refrescando token', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Obtener datos del usuario autenticado
     */
    public function getUserData(string $token): ?array
    {
        try {
            $response = Http::timeout(10)
                ->withToken($token)
                ->get($this->loginUrl . '/me');

            if ($response->successful()) {
                return $response->json();
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Error obteniendo datos de usuario', ['error' => $e->getMessage()]);
            return null;
        }
    }
}
