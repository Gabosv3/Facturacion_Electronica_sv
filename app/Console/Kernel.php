<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Log;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Renovar token del MH cada 23 horas
        $schedule->command('mh:renovar-token')
            ->dailyAt('02:00') // A las 2:00 AM
            ->timezone('America/El_Salvador')
            ->onSuccess(function () {
                Log::info('Token MH renovado automáticamente');
            })
            ->onFailure(function () {
                Log::error('Error al renovar token MH automáticamente');
            });
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}