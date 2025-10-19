<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class DiagnosticarTokenMH extends Command
{
    protected $signature = 'mh:diagnosticar';
    protected $description = 'Diagnosticar problemas con la autenticación del MH';

    public function handle()
    {
        $this->info('=== DIAGNÓSTICO DE AUTENTICACIÓN MH ===');
        $this->newLine();

        // 1. Verificar configuración
        $this->info('1. Verificando configuración...');
        $authUrl = config('dte.mh_auth_url');
        $user = config('dte.mh_user');
        $password = config('dte.mh_password');

        $this->line("   URL Auth: " . ($authUrl ?: '❌ NO CONFIGURADA'));
        $this->line("   Usuario: " . ($user ?: '❌ NO CONFIGURADO'));
        $this->line("   Password: " . ($password ? '✓ Configurada (' . strlen($password) . ' caracteres)' : '❌ NO CONFIGURADA'));
        $this->newLine();

        if (!$authUrl || !$user || !$password) {
            $this->error('❌ Faltan variables de configuración en .env');
            $this->info('Agrega estas variables:');
            $this->line('MH_AUTH_URL=https://apitest.dtes.mh.gob.sv/seguridad/auth');
            $this->line('MH_USER=06142302881449');
            $this->line('MH_PASSWORD="G/3Z{16:W71%c0eNPR?z"');
            return Command::FAILURE;
        }

        // 2. Probar conectividad
        $this->info('2. Probando conectividad con el MH...');
        try {
            $testResponse = Http::timeout(10)->get($authUrl);
            $this->line("   Estado HTTP: " . $testResponse->status());
            
            if ($testResponse->status() === 405) {
                $this->line("   ✓ Servidor responde (405 es normal para GET)");
            } else {
                $this->line("   Respuesta: " . substr($testResponse->body(), 0, 100));
            }
        } catch (\Exception $e) {
            $this->error("   ❌ Error de conectividad: " . $e->getMessage());
            $this->newLine();
            $this->warn('Posibles causas:');
            $this->line('- Sin conexión a internet');
            $this->line('- Firewall bloqueando');
            $this->line('- URL incorrecta');
            return Command::FAILURE;
        }
        $this->newLine();

        // 3. Intentar autenticación
        $this->info('3. Intentando autenticación...');
        $this->line("   Enviando a: $authUrl");
        $this->line("   Usuario: $user");
        
        try {
            $response = Http::asForm()
                ->timeout(30)
                ->post($authUrl, [
                    'user' => $user,
                    'pwd' => $password,
                ]);

            $this->line("   Estado HTTP: " . $response->status());
            
            if ($response->successful()) {
                $data = $response->json();
                $this->newLine();
                $this->info('✓ Respuesta exitosa del MH:');
                $this->line(json_encode($data, JSON_PRETTY_PRINT));
                
                if (isset($data['body']['token'])) {
                    $token = $data['body']['token'];
                    $this->newLine();
                    $this->info('✓ TOKEN RECIBIDO:');
                    $this->line('   ' . substr($token, 0, 50) . '...');
                    $this->line('   Longitud: ' . strlen($token) . ' caracteres');
                    return Command::SUCCESS;
                } else {
                    $this->error('❌ Respuesta sin token');
                    $this->line('Estructura recibida:');
                    $this->line(json_encode($data, JSON_PRETTY_PRINT));
                    return Command::FAILURE;
                }
            } else {
                $this->error('❌ Error en la autenticación');
                $this->line("   Código: " . $response->status());
                $this->line("   Respuesta: " . $response->body());
                $this->newLine();
                
                if ($response->status() === 401) {
                    $this->warn('Credenciales incorrectas. Verifica:');
                    $this->line('- Usuario (NIT): ' . $user);
                    $this->line('- Password en .env debe estar entre comillas si tiene caracteres especiales');
                } elseif ($response->status() === 500) {
                    $this->warn('Error en el servidor del MH. Intenta más tarde.');
                }
                
                return Command::FAILURE;
            }
        } catch (\Exception $e) {
            $this->error('❌ Excepción: ' . $e->getMessage());
            $this->line('Detalles: ' . $e->getTraceAsString());
            return Command::FAILURE;
        }
    }
}