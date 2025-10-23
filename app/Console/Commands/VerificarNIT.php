<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VerificarNIT extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mh:verificar-nit {nit : NIT a verificar (14 dígitos)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verifica si un NIT es válido para usar como receptor en CCF';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $nit = $this->argument('nit');

        // Limpiar el NIT (quitar guiones, espacios, etc.)
        $nitLimpio = preg_replace('/[^0-9]/', '', $nit);

        // Validar formato
        if (strlen($nitLimpio) !== 14) {
            $this->error("❌ El NIT debe tener exactamente 14 dígitos");
            $this->info("   NIT ingresado: {$nit}");
            $this->info("   NIT limpio: {$nitLimpio} (" . strlen($nitLimpio) . " dígitos)");
            return 1;
        }

        // Validar que no sea el mismo NIT del emisor
        $nitEmisor = config('dte.emisor.nit');
        if ($nitLimpio === preg_replace('/[^0-9]/', '', $nitEmisor)) {
            $this->error("❌ El NIT del receptor NO puede ser el mismo que el del emisor");
            $this->info("   NIT emisor: {$nitEmisor}");
            $this->info("   NIT receptor: {$nitLimpio}");
            return 1;
        }

        $this->info("🔍 Verificando NIT: {$nitLimpio}");
        $this->newLine();

        // Mostrar información
        $this->table(
            ['Campo', 'Valor'],
            [
                ['NIT Original', $nit],
                ['NIT Limpio', $nitLimpio],
                ['Longitud', strlen($nitLimpio) . ' dígitos'],
                ['Formato válido', '✅ Sí'],
                ['Diferente al emisor', '✅ Sí'],
            ]
        );

        $this->newLine();
        $this->info("✅ El NIT tiene el formato correcto para usar en CCF");
        $this->newLine();

        $this->warn("⚠️  IMPORTANTE:");
        $this->line("   - Este comando solo verifica el FORMATO del NIT");
        $this->line("   - El MH validará si el NIT existe en su base de datos");
        $this->line("   - Si el MH rechaza el NIT, deberás obtener uno válido");
        $this->newLine();

        // Sugerencia de datos de prueba
        if ($this->confirm('¿Deseas ver datos de prueba sugeridos?', true)) {
            $this->newLine();
            $this->info("📋 Datos de Prueba Sugeridos:");
            $this->newLine();

            $this->line("Opción 1:");
            $this->line("  NIT: 06141307841010");
            $this->line("  NRC: 12345-6");
            $this->line("  Nombre: EMPRESA DE PRUEBA SA DE CV");
            $this->line("  Código Actividad: 46510");
            $this->newLine();

            $this->line("Opción 2:");
            $this->line("  NIT: 02010101010101");
            $this->line("  NRC: 98765-4");
            $this->line("  Nombre: COMERCIAL DE PRUEBAS SA DE CV");
            $this->line("  Código Actividad: 47190");
            $this->newLine();

            $this->info("Para más detalles, consulta: DATOS_PRUEBA_CCF.md");
        }

        return 0;
    }
}
