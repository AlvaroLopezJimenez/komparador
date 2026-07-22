<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class GuardarHistoricoPreciosProductos extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'historico:guardar-productos {--token=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Guarda el histórico de precios de todos los productos (incluye especificaciones internas)';

    /**
     * Execute the console command.
     *
     * Delega en ProductoController::guardarHistoricoPrecios() para que
     * la ejecución automática del cron sea idéntica a la manual desde ajustes.
     */
    public function handle()
    {
        $token = $this->option('token');
        if (!$token || $token !== env('TOKEN_ACTUALIZAR_PRECIOS')) {
            $this->error('❌ Token inválido');
            return 1;
        }

        $this->info('🔄 Iniciando guardado de histórico de precios de productos...');

        try {
            $response = app(\App\Http\Controllers\ProductoController::class)->guardarHistoricoPrecios();
            $data = method_exists($response, 'getData') ? $response->getData(true) : (array) $response;

            $total = $data['total_productos'] ?? 0;
            $guardados = $data['guardados'] ?? 0;
            $errores = $data['errores'] ?? 0;

            $this->info('✅ Proceso completado exitosamente');
            $this->info("📊 Resumen: {$total} productos, {$guardados} guardados, {$errores} errores");

            return 0;
        } catch (\Throwable $e) {
            $this->error('❌ Error en el proceso: ' . $e->getMessage());
            return 1;
        }
    }
}
