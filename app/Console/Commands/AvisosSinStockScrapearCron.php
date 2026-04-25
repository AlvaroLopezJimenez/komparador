<?php

namespace App\Console\Commands;

use App\Http\Controllers\Crons\AvisosSinStockScrapearCronController;
use Illuminate\Console\Command;

class AvisosSinStockScrapearCron extends Command
{
    protected $signature = 'avisos:scrapear-sin-stock {--token=}';

    protected $description = 'Scrapea ofertas con aviso sin stock vencido (tiendas con avisos_sin_stock_scrapear_automatico=si)';

    public function handle(): int
    {
        $token = $this->option('token');
        if ($token && env('TOKEN_ACTUALIZAR_PRECIOS') && $token !== env('TOKEN_ACTUALIZAR_PRECIOS')) {
            $this->error('Token inválido');
            return 1;
        }

        $this->info('Procesando avisos sin stock (scrapeo automático)...');

        $controller = new AvisosSinStockScrapearCronController();
        $exitCode = $controller();

        if ($exitCode === 0) {
            $this->info('Cron avisos sin stock finalizado.');
        }

        return $exitCode;
    }
}
