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
        // Limpiar logs antiguos semanalmente de forma incondicional
        $schedule->command('log:clear')->weekly();
        
        try {
            if (\Schema::hasTable('ajustes')) {
                $token = env('TOKEN_ACTUALIZAR_PRECIOS');
                
                $crons = [
                    'clicks_actualizar_ofertas' => ['type' => 'command', 'target' => 'clicks:actualizar-ofertas --token=' . $token],
                    'historico_guardar_productos' => [
                        'type' => 'callback',
                        'target' => function() {
                            // Misma lógica que la ejecución manual desde ajustes
                            // (incluye histórico de especificaciones internas)
                            app(\App\Http\Controllers\ProductoController::class)->guardarHistoricoPrecios();
                        }
                    ],
                    'productos_actualizar_oferta_mas_barata' => [
                        'type' => 'callback',
                        'target' => function() use ($token) {
                            app(\App\Http\Controllers\ProductoController::class)->actualizarOfertaMasBarataPorProducto(new \Illuminate\Http\Request(['token' => $token]));
                        }
                    ],
                    'precios_hot_calcular' => ['type' => 'command', 'target' => 'precios-hot:calcular --token=' . $token],
                    'categorias_actualizar_clicks' => [
                        'type' => 'callback',
                        'target' => function() {
                            app(\App\Http\Controllers\CategoriaClicksController::class)->procesar();
                        }
                    ],
                    'productos_actualizar_clicks' => [
                        'type' => 'callback',
                        'target' => function() {
                            app(\App\Http\Controllers\ProductoController::class)->actualizarClicks();
                        }
                    ],
                    'ofertas_historico_precios' => [
                        'type' => 'callback',
                        'target' => function() {
                            app(\App\Http\Controllers\OfertaProductoController::class)->ejecutarHistoricoPrecios();
                        }
                    ],
                    'ofertas_comprobar_gastos_envio' => [
                        'type' => 'callback',
                        'target' => function() {
                            app(\App\Http\Controllers\OfertaProductoController::class)->comprobarGastosEnvioOfertas();
                        }
                    ],
                    'clicks_procesar_geolocalizacion' => [
                        'type' => 'callback',
                        'target' => function() use ($token) {
                            app(\App\Http\Controllers\ClickController::class)->procesarGeolocalizacion(new \Illuminate\Http\Request(['token' => $token]));
                        }
                    ],
                    'ofertas_actualizar_contador_spec' => ['type' => 'command', 'target' => 'ofertas:actualizar-contador-especificaciones'],
                    'cron_avisos_sin_stock_scrapear' => ['type' => 'command', 'target' => 'avisos:scrapear-sin-stock --token=' . $token],
                    'cron_avisos_generar_correo_precio' => ['type' => 'command', 'target' => 'avisos:generar-correo-precio --token=' . $token],
                    'cron_neo_objetivos' => [
                        'type' => 'callback',
                        'target' => function() use ($token) {
                            app(\App\Http\Controllers\Crons\CronNeoObjetivosController::class)(new \Illuminate\Http\Request(['token' => $token]));
                        }
                    ],
                    'cron_descarga_csv_tiendas' => ['type' => 'command', 'target' => 'cron:descarga-csv-tiendas --token=' . $token . ' --sync'],
                    'cron_buscar_amazon_productos' => [
                        'type' => 'callback',
                        'target' => function() {
                            app(\App\Http\Controllers\Crons\CronBuscarProductosAmazonController::class)->ejecutarCron();
                        }
                    ],
                    'ofertas_scraper_segundo_plano' => [
                        'type' => 'callback',
                        'target' => function() use ($token) {
                            app(\App\Http\Controllers\OfertaProductoController::class)->ejecutarScraperOfertasSegundoPlano(new \Illuminate\Http\Request(['token' => $token]));
                        }
                    ],
                    'actualizar_primera_oferta_segundo_plano' => [
                        'type' => 'callback',
                        'target' => function() use ($token) {
                            app(\App\Http\Controllers\Scraping\ActualizarPrimeraOfertaController::class)->ejecutarSegundoPlano(new \Illuminate\Http\Request(['token' => $token]));
                        }
                    ],
                    'chollos_comprobar_finalizados' => [
                        'type' => 'callback',
                        'target' => function() use ($token) {
                            app(\App\Http\Controllers\CholloController::class)->comprobarChollosYOfertasFinalizadas(new \Illuminate\Http\Request(['token' => $token]));
                        }
                    ]
                ];

                foreach ($crons as $key => $config) {
                    $activo = \App\Models\Ajuste::getVal("cron_{$key}_activo") === '1';
                    if (!$activo) {
                        continue;
                    }

                    // Definir la tarea programada
                    if ($config['type'] === 'command') {
                        $task = $schedule->command($config['target']);
                    } else {
                        $task = $schedule->call($config['target'])->name('cron_run_' . $key);
                    }

                    // Obtener los 5 campos del cron expression
                    $minuto = \App\Models\Ajuste::getVal("cron_{$key}_minuto") ?? '*';
                    $hora = \App\Models\Ajuste::getVal("cron_{$key}_hora") ?? '*';
                    $dia = \App\Models\Ajuste::getVal("cron_{$key}_dia") ?? '*';
                    $mes = \App\Models\Ajuste::getVal("cron_{$key}_mes") ?? '*';
                    $diaSemana = \App\Models\Ajuste::getVal("cron_{$key}_dia_semana") ?? '*';

                    $expression = "{$minuto} {$hora} {$dia} {$mes} {$diaSemana}";
                    $task->cron($expression);
                }
            } else {
                $this->scheduleDefaults($schedule);
            }
        } catch (\Throwable $e) {
            $this->scheduleDefaults($schedule);
        }
    }

    private function applyInterval($task, string $horario): void
    {
        switch ($horario) {
            case '10m':
                $task->everyTenMinutes();
                break;
            case '30m':
                $task->everyThirtyMinutes();
                break;
            case '1h':
                $task->hourly();
                break;
            case '2h':
                $task->everyTwoHours();
                break;
            case '6h':
                $task->everySixHours();
                break;
            case '12h':
                $task->everyTwelveHours();
                break;
        }
    }

    /**
     * Fallback por defecto si no existe la tabla de ajustes.
     */
    protected function scheduleDefaults(Schedule $schedule): void
    {
        $schedule->command('precios-hot:calcular')->dailyAt('06:00');
        $schedule->command('clicks:actualizar-ofertas')->hourly();
        $schedule->command('historico:guardar-productos')->everyFourHours();
        $schedule->command('avisos:scrapear-sin-stock')->hourly();
        $schedule->command('avisos:generar-correo-precio')->hourly();
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
