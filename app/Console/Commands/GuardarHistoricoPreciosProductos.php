<?php

namespace App\Console\Commands;

use App\Models\Producto;
use App\Models\HistoricoPrecioProducto;
use App\Models\EjecucionGlobal;
use Illuminate\Console\Command;
use Carbon\Carbon;

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
    protected $description = 'Guarda el histórico de precios de todos los productos';

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

        $this->info('🔄 Iniciando guardado de histórico de precios de productos...');

        // Crear registro de ejecución
        $ejecucion = EjecucionGlobal::create([
            'inicio' => now(),
            'nombre' => 'ejecuciones_historico_precios_productos',
            'log' => []
        ]);

        try {
            $this->procesarHistoricoProductos($ejecucion);
            
            $ejecucion->update([
                'fin' => now()
            ]);

            $this->info('✅ Proceso completado exitosamente');
            $this->info("📊 Resumen: {$ejecucion->total} productos, {$ejecucion->total_guardado} guardados, {$ejecucion->total_errores} errores");
            
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

    private function procesarHistoricoProductos($ejecucion)
    {
        $log = [];
        $totalProductos = 0;
        $guardados = 0;
        $errores = 0;

        // Obtener todos los productos
        $productos = Producto::all();
        $totalProductos = $productos->count();

        $this->info("📋 Procesando {$totalProductos} productos...");

        foreach ($productos as $producto) {
            try {
                $this->line("🔄 Procesando producto: {$producto->nombre}");
                
                // Obtener el precio mínimo actual del producto
                $precioMinimo = $producto->precio_minimo ?? 0;
                $precioMaximo = $producto->precio_maximo ?? 0;
                
                // Guardar en el histórico
                HistoricoPrecioProducto::updateOrCreate(
                    [
                        'producto_id' => $producto->id,
                        'fecha' => now()->toDateString()
                    ],
                    [
                        'precio_minimo' => $precioMinimo,
                        'precio_maximo' => $precioMaximo
                    ]
                );
                
                $guardados++;
                $log[] = "✅ Producto '{$producto->nombre}': histórico guardado (min: {$precioMinimo}, max: {$precioMaximo})";
                $this->info("✅ Producto '{$producto->nombre}': histórico guardado (min: {$precioMinimo}, max: {$precioMaximo})");
                
            } catch (\Exception $e) {
                $errores++;
                $log[] = "❌ Error en producto '{$producto->nombre}': " . $e->getMessage();
                $this->error("❌ Error en producto '{$producto->nombre}': " . $e->getMessage());
            }
        }

        $ejecucion->update([
            'total' => $totalProductos,
            'total_guardado' => $guardados,
            'total_errores' => $errores,
            'log' => $log
        ]);
    }
} 