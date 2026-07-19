<?php

namespace App\Console\Commands;

use App\Http\Controllers\Crons\CronDescargaCSVTiendasController;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CronDescargaCSVTiendas extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cron:descarga-csv-tiendas
                            {--token=}
                            {--detached : Proceso hijo: ejecuta el trabajo real}
                            {--sync : Forzar ejecución en primer plano (debug)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Descarga feeds CSV-Awin (.gz) de tiendas con url_csv configurada';

    /**
     * Lanza un proceso PHP independiente que no muere cuando el cron/schedule del hosting
     * corta a ~300s. Devuelve true si el spawn parece correcto.
     */
    public static function lanzarDetached(?string $token = null): bool
    {
        $token = $token ?: (string) env('TOKEN_ACTUALIZAR_PRECIOS');
        if ($token === '') {
            Log::error('CronDescargaCSVTiendas: no hay token para lanzar proceso detached');

            return false;
        }

        $php = PHP_BINARY ?: 'php';
        $artisan = base_path('artisan');
        $logFile = storage_path('logs/cron-descarga-csv-tiendas-detached.log');
        $base = base_path();

        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $cmd = sprintf(
                'start /B "" %s -d max_execution_time=0 %s cron:descarga-csv-tiendas --token=%s --detached >> %s 2>&1',
                escapeshellarg($php),
                escapeshellarg($artisan),
                escapeshellarg($token),
                escapeshellarg($logFile)
            );
            pclose(popen('cd /d ' . escapeshellarg($base) . ' && ' . $cmd, 'r'));

            return true;
        }

        $cmd = sprintf(
            'cd %s && nohup %s -d max_execution_time=0 %s cron:descarga-csv-tiendas --token=%s --detached >> %s 2>&1 & echo $!',
            escapeshellarg($base),
            escapeshellarg($php),
            escapeshellarg($artisan),
            escapeshellarg($token),
            escapeshellarg($logFile)
        );

        $pid = trim((string) exec($cmd, $output, $exitCode));

        if ($pid === '' || !ctype_digit($pid)) {
            Log::warning('CronDescargaCSVTiendas: spawn detached sin PID claro', [
                'exit_code' => $exitCode,
                'output' => $output,
                'cmd' => $cmd,
            ]);

            // Algunos hostings no devuelven el PID pero sí lanzan el proceso
            return $exitCode === 0;
        }

        Log::info('CronDescargaCSVTiendas: proceso detached lanzado', ['pid' => (int) $pid]);

        return true;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $token = $this->option('token') ?: env('TOKEN_ACTUALIZAR_PRECIOS');

        // El schedule del hosting suele matar schedule:run a ~5 min. Desacoplamos el trabajo
        // a un proceso hijo con nohup para que la descarga pueda durar horas.
        if (!$this->option('detached') && !$this->option('sync')) {
            $this->info('Lanzando descarga CSV en segundo plano (evita corte ~300s del hosting)...');

            if (!self::lanzarDetached(is_string($token) ? $token : null)) {
                $this->error('No se pudo lanzar el proceso en segundo plano.');

                return Command::FAILURE;
            }

            $this->info('Proceso lanzado. La importación continúa aunque este comando termine ya.');

            return Command::SUCCESS;
        }

        $this->info('Iniciando descarga e importación de CSV de tiendas...');

        $request = new Request(['token' => $token]);

        /** @var CronDescargaCSVTiendasController $controller */
        $controller = app(CronDescargaCSVTiendasController::class);

        $response = $controller($request);
        $data = json_decode($response->getContent(), true);

        if (isset($data['status']) && $data['status'] === 'ok') {
            $this->info($data['message'] ?? 'Cron ejecutado correctamente.');
            if (isset($data['contadores'])) {
                $this->table(
                    ['Métrica', 'Valor'],
                    array_map(
                        fn ($k, $v) => [ucwords(str_replace('_', ' ', $k)), $v],
                        array_keys($data['contadores']),
                        array_values($data['contadores'])
                    )
                );
            }

            return Command::SUCCESS;
        }

        $this->error($data['message'] ?? 'Error desconocido durante la ejecución.');

        return Command::FAILURE;
    }
}
