<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\MHApiService;

class RenovarTokenMH extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mh:renovar-token';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Renovar token de autenticación del Ministerio de Hacienda';

    protected $mhApiService;

    public function __construct(MHApiService $mhApiService)
    {
        parent::__construct();
        $this->mhApiService = $mhApiService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Renovando token del Ministerio de Hacienda...');

        if ($this->mhApiService->renovarToken()) {
            $this->info('✓ Token renovado exitosamente');
            return Command::SUCCESS;
        } else {
            $this->error('✗ Error al renovar el token');
            return Command::FAILURE;
        }
    }
}