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
                
                // Obtener el precio actual del producto
                $precioActual = $producto->precio ?? 0;
                
                // Si el precio es 0, buscar el precio del día anterior en el historial
                if ($precioActual == 0) {
                    $historicoAnterior = HistoricoPrecioProducto::where('producto_id', $producto->id)
                        ->where('fecha', '<', now()->toDateString())
                        ->orderBy('fecha', 'desc')
                        ->first();
                    
                    if ($historicoAnterior) {
                        $precioActual = $historicoAnterior->precio_minimo ?? $historicoAnterior->precio_maximo ?? 0;
                    }
                }
                
                // Buscar la oferta con precio más bajo para este producto considerando descuentos
                $ofertas = \App\Models\OfertaProducto::where('producto_id', $producto->id)
                    ->where('mostrar', 'si')
                    ->get(['id', 'precio_unidad', 'precio_total', 'unidades', 'descuentos']);

                $ofertaMasBarata = null;
                $precioRealMasBajo = null;

                // Usar el servicio de descuentos para calcular el precio real
                $descuentosController = new \App\Http\Controllers\DescuentosController();
                
                foreach ($ofertas as $oferta) {
                    $ofertaConDescuento = $descuentosController->aplicarDescuento($oferta);
                    $precioReal = $ofertaConDescuento->precio_unidad;
                    if ($precioRealMasBajo === null || $precioReal < $precioRealMasBajo) {
                        $precioRealMasBajo = $precioReal;
                        $ofertaMasBarata = $oferta;
                    }
                }
                
                // Si hay oferta, usar el precio de la oferta, si no, usar el precio actual (que puede ser del historial)
                $precioMinimo = $ofertaMasBarata ? $precioRealMasBajo : $precioActual;
                
                // Guardar en el histórico
                HistoricoPrecioProducto::updateOrCreate(
                    [
                        'producto_id' => $producto->id,
                        'fecha' => now()->toDateString()
                    ],
                    [
                        'precio_minimo' => $precioMinimo,
                        'precio_maximo' => $precioActual
                    ]
                );
                
                $guardados++;
                $log[] = "✅ Producto '{$producto->nombre}': histórico guardado (min: {$precioMinimo}, max: {$precioActual})";
                $this->info("✅ Producto '{$producto->nombre}': histórico guardado (min: {$precioMinimo}, max: {$precioActual})");
                
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