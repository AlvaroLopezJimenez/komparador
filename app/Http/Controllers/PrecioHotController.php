<?php

namespace App\Http\Controllers;

use App\Models\PrecioHot;
use App\Models\EjecucionPrecioHot;
use App\Models\Categoria;
use App\Models\Producto;
use App\Models\OfertaProducto;
use App\Models\HistoricoPrecioProducto;
use App\Models\Tienda;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PrecioHotController extends Controller
{
    public function index()
    {
        $preciosHot = PrecioHot::orderBy('created_at', 'desc')->get();
        return view('admin.precios-hot.index', compact('preciosHot'));
    }

    public function ejecutarSegundoPlano(Request $request)
    {
        $token = $request->get('token');
        if ($token !== env('TOKEN_ACTUALIZAR_PRECIOS')) {
            return response()->json(['status' => 'error', 'message' => 'Token inválido']);
        }

        // Crear registro de ejecución en la tabla global
        $ejecucion = \App\Models\EjecucionGlobal::create([
            'inicio' => now(),
            'nombre' => 'precios_hot',
            'log' => []
        ]);

        try {
            $this->procesarPreciosHotCompleto($ejecucion);
            
            $ejecucion->update([
                'fin' => now()
            ]);

            return response()->json([
                'status' => 'ok',
                'message' => 'Proceso completado',
                'total_categorias' => $ejecucion->total,
                'total_inserciones' => $ejecucion->total_guardado,
                'total_errores' => $ejecucion->total_errores
            ]);
        } catch (\Exception $e) {
            $ejecucion->update([
                'fin' => now(),
                'total_errores' => $ejecucion->total_errores + 1
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Error en el proceso: ' . $e->getMessage()
            ]);
        }
    }

    public function verEjecucion()
    {
        $tokenScraper = env('TOKEN_ACTUALIZAR_PRECIOS');
        return view('admin.precios-hot.ejecucion', compact('tokenScraper'));
    }

    public function ejecuciones()
    {
        $ejecuciones = \App\Models\EjecucionGlobal::where('nombre', 'precios_hot')
            ->orderBy('created_at', 'desc')
            ->get();
        return view('admin.precios-hot.ejecuciones', compact('ejecuciones'));
    }

    // Método principal que procesa todos los precios hot (usado por ambas ejecuciones)
    private function procesarPreciosHotCompleto($ejecucion)
    {
        $log = [];
        $totalCategorias = 0;
        $totalInserciones = 0;
        $totalErrores = 0;

        try {
            // Obtener todas las categorías
            $categorias = Categoria::all();
            $totalCategorias = $categorias->count();
            $log[] = "📊 Total de categorías encontradas: {$totalCategorias}";

            foreach ($categorias as $categoria) {
                try {
                    $log[] = "🔄 Procesando categoría: {$categoria->nombre}";
                    $productosHot = $this->obtenerProductosHotPorCategoria($categoria, 20);
                    
                    if (!empty($productosHot)) {
                        // Guardar o actualizar en la tabla precios_hot
                        PrecioHot::updateOrCreate(
                            ['nombre' => $categoria->nombre],
                            ['datos' => $productosHot]
                        );
                        $totalInserciones++;
                        $log[] = "✅ Categoría '{$categoria->nombre}': " . count($productosHot) . " productos hot encontrados";
                    } else {
                        $log[] = "⚠️ Categoría '{$categoria->nombre}': No se encontraron productos hot";
                    }
                } catch (\Exception $e) {
                    $totalErrores++;
                    $log[] = "❌ Error en categoría '{$categoria->nombre}': " . $e->getMessage();
                    $log[] = "📍 Stack trace: " . $e->getTraceAsString();
                }
            }

            // Procesar categoría global "Precios Hot"
            try {
                $log[] = "🔄 Procesando categoría global: Precios Hot";
                $productosHotGlobal = $this->obtenerProductosHotGlobal(60);
                
                if (!empty($productosHotGlobal)) {
                    PrecioHot::updateOrCreate(
                        ['nombre' => 'Precios Hot'],
                        ['datos' => $productosHotGlobal]
                    );
                    $totalInserciones++;
                    $log[] = "✅ Categoría global 'Precios Hot': " . count($productosHotGlobal) . " productos hot encontrados";
                } else {
                    $log[] = "⚠️ Categoría global 'Precios Hot': No se encontraron productos hot";
                }
            } catch (\Exception $e) {
                $totalErrores++;
                $log[] = "❌ Error en categoría global 'Precios Hot': " . $e->getMessage();
                $log[] = "📍 Stack trace: " . $e->getTraceAsString();
            }

            $log[] = "🎉 Proceso completado. Total inserciones: {$totalInserciones}, Total errores: {$totalErrores}";

        } catch (\Exception $e) {
            $totalErrores++;
            $log[] = "❌ Error general en el proceso: " . $e->getMessage();
            $log[] = "📍 Stack trace: " . $e->getTraceAsString();
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
            ->select('id', 'nombre', 'imagen_pequena', 'categoria_id', 'slug', 'precio', 'unidadDeMedida')
            ->where('obsoleto', 'no')
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
            ->select('id', 'nombre', 'imagen_pequena', 'categoria_id', 'slug', 'precio', 'unidadDeMedida')
            ->whereNotNull('precio')
            ->where('precio', '>', 0)
            ->get();
        
        return $this->calcularProductosHot($productos, $limite);
    }

    private function calcularProductosHot($productos, $limite)
    {
        $productosHot = [];
        $haceUnMes = Carbon::now()->subMonth();
        $descuentosController = new \App\Http\Controllers\DescuentosController();

        foreach ($productos as $producto) {
            // Calcular precio medio del último mes
            $precioMedio = HistoricoPrecioProducto::where('producto_id', $producto->id)
                ->where('fecha', '>=', $haceUnMes)
                ->avg('precio_minimo');

            if (!$precioMedio) {
                continue; // Saltar productos sin historial de precios
            }
            
            // Obtener todas las ofertas válidas del producto
            $ofertasValidas = OfertaProducto::with(['tienda' => function($query) {
                    $query->select('id', 'nombre', 'url_imagen');
                }])
                ->where('producto_id', $producto->id)
                ->where('mostrar', 'si')
                ->whereHas('tienda', function($query) {
                    $query->where('mostrar_tienda', 'si');
                })
                ->get();

            if ($ofertasValidas->isEmpty()) {
                continue; // Saltar productos sin ofertas válidas
            }

            // Procesar ofertas con descuentos
            $ofertasProcesadas = collect();
            foreach ($ofertasValidas as $oferta) {
                if (!empty($oferta->descuentos)) {
                    // Si tiene descuentos, procesarla con DescuentosController
                    $ofertaProcesada = $descuentosController->aplicarDescuento($oferta);
                    $ofertasProcesadas->push($ofertaProcesada);
                } else {
                    // Si no tiene descuentos, usar la oferta tal como está
                    $ofertasProcesadas->push($oferta);
                }
            }

            // Ordenar por precio_unidad (menor a mayor) y tomar la más barata
            $mejorOferta = $ofertasProcesadas->sortBy('precio_unidad')->first();
            
            if (!$mejorOferta || $mejorOferta->precio_unidad <= 0) {
                continue; // Saltar si no hay precio válido
            }

            // Usar el precio_unidad más bajo de todas las ofertas procesadas
            $precioOferta = $mejorOferta->precio_unidad;
            
            // Calcular porcentaje de diferencia usando el precio de la oferta más barata
            $diferencia = (($precioMedio - $precioOferta) / $precioMedio) * 100;

            // Solo incluir si el precio de la oferta es menor que la media y la diferencia es del 5% o más
            if ($diferencia >= 5) {
                // Actualizar campo rebajado del producto (funcionalidad adicional)
                // Si la diferencia es >= 5%, guardar el porcentaje redondeado a entero
                $porcentajeRebajado = (int) round($diferencia);
                Producto::where('id', $producto->id)->update(['rebajado' => $porcentajeRebajado]);
                
                // Verificar que la tienda existe y tiene los datos necesarios
                $tienda = $mejorOferta->tienda;
                if (!$tienda) {
                    continue; // Saltar si no hay tienda
                }
                
                // Obtener unidad de medida del producto
                $unidadMedida = $producto->unidadDeMedida ?? 'unidad';
                
                // Formatear precio según la unidad de medida
                $decimalesPrecio = ($unidadMedida === 'unidadMilesima') ? 3 : 2;
                $precioFormateado = number_format($precioOferta, $decimalesPrecio, ',', '.') . ' €';
                
                // Añadir sufijo según la unidad de medida
                if ($unidadMedida === 'unidad') {
                    $precioFormateado .= '/Und.';
                } elseif ($unidadMedida === 'kilos') {
                    $precioFormateado .= '/Kg.';
                } elseif ($unidadMedida === 'litros') {
                    $precioFormateado .= '/L.';
                } elseif ($unidadMedida === 'unidadMilesima') {
                    $precioFormateado .= '/Und.';
                } elseif ($unidadMedida === '800gramos') {
                    $precioFormateado .= '/800gr.';
                } elseif ($unidadMedida === '100ml') {
                    $precioFormateado .= '/100ml.';
                } elseif ($unidadMedida === 'unidadUnica') {
                    // No añadir sufijo para unidadUnica
                } else {
                    $precioFormateado .= '/Und.';
                }
                
                $productoHotData = [
                    'producto_id' => $producto->id,
                    'oferta_id' => $mejorOferta->id,
                    'tienda_id' => $mejorOferta->tienda_id,
                    'img_tienda' => $tienda->url_imagen ?? null,
                    'img_producto' => (!empty($producto->imagen_pequena) && is_array($producto->imagen_pequena) && isset($producto->imagen_pequena[0]))
    ? $producto->imagen_pequena[0]
    : null,
                    'precio_oferta' => $precioOferta,
                    'precio_formateado' => $precioFormateado,
                    'porcentaje_diferencia' => round($diferencia, 2),
                    'url_oferta' => route('click.redirigir', ['ofertaId' => $mejorOferta->id]),
                    'url_producto' => $this->generarUrlProducto($producto),
                    'producto_nombre' => $producto->nombre,
                    'tienda_nombre' => $tienda->nombre ?? 'Tienda desconocida',
                    'unidades' => $mejorOferta->unidades ?? 1,
                    'unidad_medida' => $unidadMedida
                ];
                
                $productosHot[] = $productoHotData;
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