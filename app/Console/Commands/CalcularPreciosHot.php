<?php

namespace App\Console\Commands;

use App\Models\PrecioHot;
use App\Models\EjecucionGlobal;
use App\Models\Categoria;
use App\Models\Producto;
use App\Models\OfertaProducto;
use App\Models\HistoricoPrecioProducto;
use App\Services\SacarPrimeraOfertaDeUnProductoAplicadoDescuentosYChollos;
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
            // PASO 1: Actualizar precios de productos antes de calcular precios hot
            $this->info('💰 Paso 1/2: Actualizando precios de productos...');
            $preciosActualizados = $this->actualizarPreciosProductos();
            $this->info("✅ Precios actualizados: {$preciosActualizados} productos");
            
            // PASO 2: Calcular precios hot
            $this->info('🔥 Paso 2/2: Calculando precios hot...');
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

    /**
     * Actualiza el precio de todos los productos usando el servicio para obtener ofertas con descuentos y chollos
     * 
     * @return int Número de precios actualizados
     */
    private function actualizarPreciosProductos()
    {
        $productos = Producto::all();
        $totalProductos = $productos->count();
        $preciosActualizados = 0;
        $servicioOfertas = new SacarPrimeraOfertaDeUnProductoAplicadoDescuentosYChollos();

        $this->line("📋 Procesando {$totalProductos} productos...");

        foreach ($productos as $producto) {
            try {
                // Verificar primero si hay ofertas disponibles para este producto
                $tieneOfertas = $producto->ofertas()
                    ->where('mostrar', 'si')
                    ->whereHas('tienda', function($query) {
                        $query->where('mostrar_tienda', 'si');
                    })
                    ->exists();
                
                // Si no hay ofertas disponibles, poner precio a 0
                if (!$tieneOfertas) {
                    if ($producto->precio != 0) {
                        $producto->precio = 0;
                        $producto->save();
                        $preciosActualizados++;
                    }
                    continue;
                }
                
                // Usar el servicio para obtener la oferta más barata con descuentos y chollos aplicados
                $mejorOferta = $servicioOfertas->obtener($producto);

                // Si el servicio devuelve una oferta válida con precio_unidad
                if ($mejorOferta && $mejorOferta->precio_unidad !== null && $mejorOferta->precio_unidad > 0) {
                    // El precio_unidad ya viene con descuentos y chollos aplicados del servicio
                    $precioRealMasBajo = $mejorOferta->precio_unidad;
                    
                    // Si el producto tiene unidadDeMedida = unidadMilesima, redondear a 3 decimales
                    if ($producto->unidadDeMedida === 'unidadMilesima') {
                        $precioNuevo = round($precioRealMasBajo, 3);
                    } else {
                        $precioNuevo = $precioRealMasBajo;
                    }
                    
                    // Validar que el precio nuevo es válido
                    if ($precioNuevo !== null && $precioNuevo > 0) {
                        // Comparar si el precio es diferente
                        if ($producto->precio != $precioNuevo) {
                            // Actualizar el precio del producto
                            $producto->precio = $precioNuevo;
                            $producto->save();
                            $preciosActualizados++;
                        }
                    } else {
                        // Si el precio calculado es inválido pero hay ofertas, mantener precio actual
                        // (no poner a 0 porque sabemos que hay ofertas)
                    }
                } else {
                    // Si el servicio no devuelve oferta válida pero sabemos que hay ofertas,
                    // mantener el precio actual (no poner a 0)
                }
            } catch (\Exception $e) {
                // Continuar con el siguiente producto si hay error
                \Log::warning("Error al actualizar precio del producto {$producto->id}: " . $e->getMessage());
            }
        }

        return $preciosActualizados;
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
        $servicioOfertas = new SacarPrimeraOfertaDeUnProductoAplicadoDescuentosYChollos();

        foreach ($productos as $producto) {
            // Calcular precio medio del último mes, excluyendo valores 0 y NULL
            $precioMedio = HistoricoPrecioProducto::where('producto_id', $producto->id)
                ->where('fecha', '>=', $haceUnMes)
                ->where('precio_minimo', '>', 0)
                ->whereNotNull('precio_minimo')
                ->avg('precio_minimo');

            // Validar que el precio medio sea válido (mayor que 0)
            if (!$precioMedio || $precioMedio <= 0) {
                // Limpiar rebajado si no hay precio medio válido
                Producto::where('id', $producto->id)->update(['rebajado' => null]);
                continue;
            }
            
            // Usar el servicio para obtener la oferta más barata con descuentos y chollos aplicados
            $mejorOferta = $servicioOfertas->obtener($producto);
            
            if (!$mejorOferta || $mejorOferta->precio_unidad <= 0) {
                // Limpiar rebajado si no hay oferta válida
                Producto::where('id', $producto->id)->update(['rebajado' => null]);
                continue;
            }

            // Usar el precio_unidad de la oferta procesada (con descuentos y chollos aplicados)
            $precioOferta = $mejorOferta->precio_unidad;
            
            // Validar que el precio de la oferta no sea mayor que el precio medio (evitar descuentos negativos)
            if ($precioOferta > $precioMedio) {
                // Limpiar rebajado si el precio actual es mayor que la media (no es descuento)
                Producto::where('id', $producto->id)->update(['rebajado' => null]);
                continue;
            }
            
            // Calcular porcentaje de diferencia usando el precio de la oferta más barata
            $diferencia = (($precioMedio - $precioOferta) / $precioMedio) * 100;

            // Solo incluir si el precio de la oferta es menor que la media y la diferencia es del 5% o más
            if ($diferencia >= 5) {
                // Actualizar campo rebajado del producto SOLO si entra en precios hot
                // Si la diferencia es >= 5%, guardar el porcentaje redondeado a entero
                $porcentajeRebajado = (int) round($diferencia);
                Producto::where('id', $producto->id)->update(['rebajado' => $porcentajeRebajado]);
                
                // Verificar que la tienda existe y tiene los datos necesarios
                $tienda = $mejorOferta->tienda;
                if (!$tienda) {
                    continue; // Saltar si no hay tienda
                }
                
                $productosHot[] = [
                    'producto_id' => $producto->id,
                    'oferta_id' => $mejorOferta->id,
                    'tienda_id' => $mejorOferta->tienda_id,
                    'precio_oferta' => $precioOferta,
                    'porcentaje_diferencia' => round($diferencia, 2),
                    'url_oferta' => $mejorOferta->url,
                    'url_producto' => $this->generarUrlProducto($producto),
                    'producto_nombre' => $producto->nombre,
                    'tienda_nombre' => $mejorOferta->tienda ? $mejorOferta->tienda->nombre : 'Tienda desconocida'
                ];
            } else {
                // Si la diferencia es menor al 5%, limpiar rebajado (poner a null)
                Producto::where('id', $producto->id)->update(['rebajado' => null]);
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