<?php

namespace App\Console\Commands;

use App\Models\OfertaProducto;
use App\Models\Click;
use App\Models\EjecucionGlobal;
use Illuminate\Console\Command;
use Carbon\Carbon;

class ActualizarClicksOfertas extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clicks:actualizar-ofertas {--token=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Actualiza los clicks de todas las ofertas';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Verificar token de seguridad
        $token = $this->option('token');
        if (!$token || $token !== env('TOKEN_ACTUALIZAR_PRECIOS')) {
            $this->error('❌ Token inválido');
            return 1;
        }

        $this->info('🔄 Iniciando actualización de clicks de ofertas...');

        // Crear registro de ejecución
        $ejecucion = EjecucionGlobal::create([
            'inicio' => now(),
            'nombre' => 'ejecuciones_actualizar_clicks_ofertas',
            'log' => []
        ]);

        try {
            $this->procesarClicksOfertas($ejecucion);
            
            $ejecucion->update([
                'fin' => now()
            ]);

            $this->info('✅ Proceso completado exitosamente');
            $this->info("📊 Resumen: {$ejecucion->total} ofertas, {$ejecucion->total_guardado} actualizadas, {$ejecucion->total_errores} errores");
            
        } catch (\Exception $e) {
            $ejecucion->update([
                'fin' => now(),
                'total_errores' => $ejecucion->total_errores + 1
            ]);

            $this->error('❌ Error en el proceso: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }

    private function procesarClicksOfertas($ejecucion)
    {
        $log = [];
        $totalOfertas = 0;
        $actualizadas = 0;
        $errores = 0;

        // Número de días configurables
        $diasBusqueda = 7;

        // Obtener todas las ofertas
        $ofertas = OfertaProducto::all();
        $totalOfertas = $ofertas->count();

        $this->info("📋 Procesando {$totalOfertas} ofertas...");

        foreach ($ofertas as $oferta) {
            try {
                $this->line("🔄 Procesando oferta: {$oferta->producto->nombre} - {$oferta->tienda->nombre}");
                
                $fechaInicio = Carbon::now()->subDays($diasBusqueda);

                $totalClicks = Click::where('oferta_id', $oferta->id)
                    ->where('created_at', '>=', $fechaInicio)
                    ->count();

                $oferta->update(['clicks' => $totalClicks]);
                
                $actualizadas++;
                $log[] = "✅ Oferta '{$oferta->producto->nombre} - {$oferta->tienda->nombre}': {$totalClicks} clicks actualizados";
                $this->info("✅ Oferta '{$oferta->producto->nombre} - {$oferta->tienda->nombre}': {$totalClicks} clicks actualizados");
                
            } catch (\Exception $e) {
                $errores++;
                $log[] = "❌ Error en oferta '{$oferta->producto->nombre} - {$oferta->tienda->nombre}': " . $e->getMessage();
                $this->error("❌ Error en oferta '{$oferta->producto->nombre} - {$oferta->tienda->nombre}': " . $e->getMessage());
            }
        }

        $ejecucion->update([
            'total' => $totalOfertas,
            'total_guardado' => $actualizadas,
            'total_errores' => $errores,
            'log' => $log
        ]);
    }
} 