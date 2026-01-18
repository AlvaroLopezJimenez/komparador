<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // $schedule->command('inspire')->hourly();
        
        // Ejecutar cálculo de precios hot diariamente a las 6:00 AM
        $schedule->command('precios-hot:calcular')->dailyAt('06:00');
        
        // Actualizar clicks de ofertas cada hora
        $schedule->command('ofertas:actualizar-clicks')->hourly();
        
        // Guardar histórico de precios cada 4 horas
        $schedule->command('precios:guardar-historico')->everyFourHours();
        
        // Limpiar logs antiguos semanalmente
        $schedule->command('log:clear')->weekly();
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
