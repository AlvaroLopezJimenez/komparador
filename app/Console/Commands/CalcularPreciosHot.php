<?php

namespace App\Console\Commands;

use App\Models\PrecioHot;
use App\Models\EjecucionGlobal;
use App\Models\Categoria;
use App\Models\Producto;
use App\Models\OfertaProducto;
use App\Models\HistoricoPrecioProducto;
use Illuminate\Console\Command;
use Carbon\Carbon;

class CalcularPreciosHot extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'precios-hot:calcular {--token=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calcula los precios hot para todas las categorías';

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

        $this->info('🔥 Iniciando cálculo de precios hot...');

        // Crear registro de ejecución
        $ejecucion = EjecucionGlobal::create([
            'inicio' => now(),
            'nombre' => 'precios_hot',
            'log' => []
        ]);

        try {
            $this->procesarPreciosHot($ejecucion);
            
            $ejecucion->update([
                'fin' => now()
            ]);

            $this->info('✅ Proceso completado exitosamente');
            $this->info("📊 Resumen: {$ejecucion->total} categorías, {$ejecucion->total_guardado} inserciones, {$ejecucion->total_errores} errores");
            
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

    private function procesarPreciosHot($ejecucion)
    {
        $log = [];
        $totalCategorias = 0;
        $totalInserciones = 0;
        $totalErrores = 0;

        // Obtener todas las categorías
        $categorias = Categoria::all();
        $totalCategorias = $categorias->count();

        $this->info("📋 Procesando {$totalCategorias} categorías...");

        foreach ($categorias as $categoria) {
            try {
                $this->line("🔄 Procesando categoría: {$categoria->nombre}");
                
                $productosHot = $this->obtenerProductosHotPorCategoria($categoria, 20);
                
                if (!empty($productosHot)) {
                    // Guardar o actualizar en la tabla precios_hot
                    PrecioHot::updateOrCreate(
                        ['nombre' => $categoria->nombre],
                        ['datos' => $productosHot]
                    );
                    $totalInserciones++;
                    $log[] = "✅ Categoría '{$categoria->nombre}': " . count($productosHot) . " productos hot encontrados";
                    $this->info("✅ Categoría '{$categoria->nombre}': " . count($productosHot) . " productos hot encontrados");
                } else {
                    $log[] = "⚠️ Categoría '{$categoria->nombre}': No se encontraron productos hot";
                    $this->warn("⚠️ Categoría '{$categoria->nombre}': No se encontraron productos hot");
                }
            } catch (\Exception $e) {
                $totalErrores++;
                $log[] = "❌ Error en categoría '{$categoria->nombre}': " . $e->getMessage();
                $this->error("❌ Error en categoría '{$categoria->nombre}': " . $e->getMessage());
            }
        }

        // Procesar categoría global "Precios Hot"
        try {
            $this->line("🔄 Procesando categoría global: Precios Hot");
            
            $productosHotGlobal = $this->obtenerProductosHotGlobal(60);
            
            if (!empty($productosHotGlobal)) {
                PrecioHot::updateOrCreate(
                    ['nombre' => 'Precios Hot'],
                    ['datos' => $productosHotGlobal]
                );
                $totalInserciones++;
                $log[] = "✅ Categoría global 'Precios Hot': " . count($productosHotGlobal) . " productos hot encontrados";
                $this->info("✅ Categoría global 'Precios Hot': " . count($productosHotGlobal) . " productos hot encontrados");
            } else {
                $log[] = "⚠️ Categoría global 'Precios Hot': No se encontraron productos hot";
                $this->warn("⚠️ Categoría global 'Precios Hot': No se encontraron productos hot");
            }
        } catch (\Exception $e) {
            $totalErrores++;
            $log[] = "❌ Error en categoría global 'Precios Hot': " . $e->getMessage();
            $this->error("❌ Error en categoría global 'Precios Hot': " . $e->getMessage());
        }

        $ejecucion->update([
            'total' => $totalCategorias,
            'total_guardado' => $totalInserciones,
            'total_errores' => $totalErrores,
            'log' => $log
        ]);
    }

    private function obtenerProductosHotPorCategoria($categoria, $limite = 20)
    {
        // Obtener todas las categorías hijas (incluyendo la actual)
        $categoriaIds = $this->obtenerCategoriaIdsIncluyendoHijas($categoria->id);
        
        // Obtener productos de estas categorías con sus relaciones
        $productos = Producto::with('categoria')
            ->select('id', 'nombre', 'imagen_pequena', 'categoria_id', 'slug', 'precio')
            ->whereIn('categoria_id', $categoriaIds)
            ->whereNotNull('precio')
            ->where('precio', '>', 0)
            ->get();
        
        return $this->calcularProductosHot($productos, $limite);
    }

    private function obtenerProductosHotGlobal($limite = 60)
    {
        // Obtener todos los productos con sus relaciones
        $productos = Producto::with('categoria')
            ->select('id', 'nombre', 'imagen_pequena', 'categoria_id', 'slug', 'precio')
            ->whereNotNull('precio')
            ->where('precio', '>', 0)
            ->get();
        
        return $this->calcularProductosHot($productos, $limite);
    }

    private function calcularProductosHot($productos, $limite)
    {
        $productosHot = [];
        $haceUnMes = Carbon::now()->subMonth();

        foreach ($productos as $producto) {
            // Calcular precio medio del último mes
            $precioMedio = HistoricoPrecioProducto::where('producto_id', $producto->id)
                ->where('fecha', '>=', $haceUnMes)
                ->avg('precio_minimo');

            if (!$precioMedio) {
                continue; // Saltar productos sin historial de precios
            }

            // Buscar la oferta con precio más bajo y cargar la relación con tienda
            $mejorOferta = OfertaProducto::with('tienda')
                ->where('producto_id', $producto->id)
                ->where('mostrar', 'si')
                ->whereHas('tienda', function($query) {
                    $query->where('mostrar_tienda', 'si');
                })
                ->orderBy('precio_unidad', 'asc')
                ->first();

            if (!$mejorOferta) {
                continue; // Saltar productos sin ofertas
            }

            // Usar el precio del producto en lugar del precio_unidad de la oferta
            $precioProducto = $producto->precio;
            
            // Verificar que el precio del producto sea válido y mayor que 0
            if (!$precioProducto || $precioProducto <= 0) {
                continue; // Saltar productos sin precio válido
            }
            
            // Calcular porcentaje de diferencia usando el precio del producto
            $diferencia = (($precioMedio - $precioProducto) / $precioMedio) * 100;

            // Solo incluir si el precio del producto es menor que la media
            if ($diferencia > 0) {
                $productosHot[] = [
                    'producto_id' => $producto->id,
                    'oferta_id' => $mejorOferta->id,
                    'tienda_id' => $mejorOferta->tienda_id,
                    'precio_oferta' => $precioProducto,
                    'porcentaje_diferencia' => round($diferencia, 2),
                    'url_oferta' => $mejorOferta->url,
                    'url_producto' => $this->generarUrlProducto($producto),
                    'producto_nombre' => $producto->nombre,
                    'tienda_nombre' => $mejorOferta->tienda ? $mejorOferta->tienda->nombre : 'Tienda desconocida'
                ];
            }
        }

        // Ordenar por porcentaje de diferencia (mayor a menor) y tomar los primeros
        usort($productosHot, function($a, $b) {
            return $b['porcentaje_diferencia'] <=> $a['porcentaje_diferencia'];
        });

        return array_slice($productosHot, 0, $limite);
    }

    private function obtenerCategoriaIdsIncluyendoHijas($categoriaId)
    {
        $categoriaIds = [$categoriaId];
        
        // Obtener categorías hijas directas
        $hijas = Categoria::where('parent_id', $categoriaId)->get();
        
        foreach ($hijas as $hija) {
            $categoriaIds = array_merge($categoriaIds, $this->obtenerCategoriaIdsIncluyendoHijas($hija->id));
        }
        
        return $categoriaIds;
    }

    private function generarUrlProducto($producto)
    {
        // Cargar la relación de categoría si no está cargada
        if (!$producto->relationLoaded('categoria')) {
            $producto->load('categoria');
        }
        
        $categoria = $producto->categoria;
        if (!$categoria) {
            return '/productos/' . $producto->slug;
        }

        $urlParts = [$categoria->slug];
        
        // Construir la URL completa con la jerarquía de categorías
        $categoriaActual = $categoria;
        while ($categoriaActual->parent_id) {
            $categoriaPadre = Categoria::find($categoriaActual->parent_id);
            if ($categoriaPadre) {
                array_unshift($urlParts, $categoriaPadre->slug);
                $categoriaActual = $categoriaPadre;
            } else {
                break;
            }
        }
        
        return '/' . implode('/', $urlParts) . '/' . $producto->slug;
    }
} 