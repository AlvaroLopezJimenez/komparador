<?php

namespace App\Console\Commands;

use App\Http\Controllers\Crons\AvisosCorreoPrecioCronController;
use Illuminate\Console\Command;

class AvisosCorreoPrecioCron extends Command
{
    protected $signature = 'avisos:generar-correo-precio {--token=}';

    protected $description = 'Genera avisos de tipo correo para alertas de precio con 7+ dias';

    public function handle(): int
    {
        $token = $this->option('token');
        if ($token && env('TOKEN_ACTUALIZAR_PRECIOS') && $token !== env('TOKEN_ACTUALIZAR_PRECIOS')) {
            $this->error('Token inválido');
            return 1;
        }

        $controller = new AvisosCorreoPrecioCronController();
        return $controller();
    }
}
