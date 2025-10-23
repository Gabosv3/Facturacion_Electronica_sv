<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ContingenciaService;
use App\Services\MHApiService;
use Illuminate\Support\Facades\Log;

class ProcesarContingencias extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'contingencia:procesar {--evento_id= : ID específico de evento a procesar}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Procesar eventos de contingencia pendientes: enviar evento al MH, enviar lote de DTEs y consultar estado';

    protected $contingenciaService;
    protected $mhApiService;

    public function __construct(ContingenciaService $contingenciaService, MHApiService $mhApiService)
    {
        parent::__construct();
        $this->contingenciaService = $contingenciaService;
        $this->mhApiService = $mhApiService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Procesando eventos de contingencia...');
        $this->newLine();

        // Obtener eventos listos para enviar
        $eventos = $this->contingenciaService->getEventosListosParaEnviar();

        if ($eventos->isEmpty()) {
            $this->warn('⚠️  No hay eventos de contingencia pendientes para procesar');
            return 0;
        }

        $this->info("📋 Encontrados {$eventos->count()} eventos de contingencia listos para procesar");
        $this->newLine();

        foreach ($eventos as $evento) {
            $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
            $this->info("📦 Procesando Evento #{$evento->id}");
            $this->line("   Código: {$evento->codigo_evento}");
            $this->line("   Tipo: {$evento->tipo_contingencia_descripcion}");
            $this->line("   Cantidad DTEs: {$evento->cantidad_dte}");
            $this->line("   Estado actual: {$evento->estado}");
            $this->newLine();

            // Paso 1: Enviar evento de contingencia al MH
            if ($evento->estado === 'cerrado') {
                $this->line("📤 Paso 1: Enviando evento de contingencia al MH...");

                $eventoJson = $this->contingenciaService->construirJsonEvento($evento);
                $resultado = $this->mhApiService->enviarEventoContingencia($eventoJson);

                if ($resultado['success']) {
                    $this->info("   ✅ Evento enviado exitosamente");
                    $evento->marcarEnviado($resultado['data']);
                } else {
                    $this->error("   ❌ Error al enviar evento: {$resultado['message']}");
                    continue; // Saltar al siguiente evento
                }
                $this->newLine();
            }

            // Paso 2: Enviar lote de DTEs
            if ($evento->estado === 'enviado' && !$evento->codigo_lote) {
                $this->line("📦 Paso 2: Enviando lote de DTEs...");

                $loteJson = $this->contingenciaService->prepararLote($evento);

                if (empty($loteJson)) {
                    $this->warn("   ⚠️  No hay DTEs firmados para enviar en lote");
                    continue;
                }

                $resultado = $this->mhApiService->enviarLote($loteJson);

                if ($resultado['success']) {
                    $codigoLote = $resultado['codigo_lote'];
                    $this->info("   ✅ Lote enviado exitosamente");
                    $this->line("   📋 Código de lote: {$codigoLote}");
                    $evento->actualizarCodigoLote($codigoLote);
                } else {
                    $this->error("   ❌ Error al enviar lote: {$resultado['message']}");
                    continue;
                }
                $this->newLine();
            }

            // Paso 3: Consultar estado del lote
            if ($evento->codigo_lote) {
                $this->line("🔍 Paso 3: Consultando estado del lote...");

                $resultado = $this->mhApiService->consultarLote($evento->codigo_lote);

                if ($resultado['success']) {
                    $estado = $resultado['estado'];
                    $this->info("   ✅ Estado del lote: {$estado}");

                    if ($estado === 'PROCESADO') {
                        $this->info("   🎉 Lote procesado completamente por el MH");
                        $evento->marcarProcesado($resultado['data']);
                    } else {
                        $this->warn("   ⏳ El lote aún está siendo procesado por el MH");
                        $this->line("   💡 Ejecuta este comando nuevamente en unos minutos");
                    }
                } else {
                    $this->error("   ❌ Error al consultar lote: {$resultado['message']}");
                }
                $this->newLine();
            }
        }

        $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->info('✅ Procesamiento completado');

        return 0;
    }
}
