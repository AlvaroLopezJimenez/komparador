<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\Producto;
use App\Models\PrecioHot;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Procesa los datos de precios hot para asegurar estructura correcta
     */
    private function procesarDatosPreciosHot($preciosHot)
    {
        if (!$preciosHot || empty($preciosHot->datos)) {
            return $preciosHot;
        }
        
        $preciosHot->datos = collect($preciosHot->datos)
            ->map(function ($item) {
                // Si el item no es un array, saltarlo
                if (!is_array($item)) {
                    return null;
                }
                
                return [
                    'producto_id' => $item['producto_id'] ?? null,
                    'oferta_id' => $item['oferta_id'] ?? null,
                    'tienda_id' => $item['tienda_id'] ?? null,
                    'img_tienda' => $item['img_tienda'] ?? 'tiendas/carrefour.png',
                    'img_producto' => $item['img_producto'] ?? 'panales/chelino-nature-talla-1.jpg',
                    'precio_oferta' => $item['precio_oferta'] ?? 0,
                    'precio_formateado' => $item['precio_formateado'] ?? number_format($item['precio_oferta'] ?? 0, 2, ',', '.') . ' €/Und.',
                    'porcentaje_diferencia' => $item['porcentaje_diferencia'] ?? 0,
                    'url_oferta' => $item['url_oferta'] ?? '#',
                    'url_producto' => $item['url_producto'] ?? '#',
                    'producto_nombre' => $item['producto_nombre'] ?? 'Producto desconocido',
                    'tienda_nombre' => $item['tienda_nombre'] ?? 'Tienda desconocida',
                    'unidades' => $item['unidades'] ?? 1,
                    'unidades_formateadas' => $item['unidades_formateadas'] ?? number_format($item['unidades'] ?? 1, 0, ',', '.') . ' Unidades',
                    'unidad_medida' => $item['unidad_medida'] ?? 'unidad'
                ];
            })
            ->filter() // Remover elementos nulos
            ->toArray();
            
        return $preciosHot;
    }

    public function index()
    {
        // $categoriasTop -> $d2, $productosTop -> $d1, $ultimosProductos -> $d4, $preciosHot -> $d3
        // Obtener categorías top (con más clicks) - máximo 10
        $d2 = Categoria::whereNull('parent_id')
            ->orderBy('clicks', 'desc')
            ->take(10)
            ->get();

        // Obtener productos top (con más clicks) - máximo 8
        $d1 = Producto::with(['categoria.padre.padre'])
            ->where('mostrar', 'si')
            ->orderBy('clicks', 'desc')
            ->take(8)
            ->get();

        // Obtener últimos productos añadidos - máximo 10
        $d4 = Producto::with(['categoria.padre.padre'])
            ->where('mostrar', 'si')
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        // Obtener ofertas destacadas (con mejor precio) - máximo 6
        $ofertasDestacadas = \App\Models\OfertaProducto::with(['producto.categoria.padre.padre', 'tienda'])
            ->where('mostrar', 'si')
            ->orderBy('precio_unidad', 'asc')
            ->take(6)
            ->get();

        $d3 = PrecioHot::where('nombre', 'Precios Hot')->first();
        $d3 = $this->procesarDatosPreciosHot($d3);

        return view('index', compact('d1', 'd2', 'd3', 'd4', 'ofertasDestacadas'));
    }

    public function categoria($slug)
    {
        $categoria = Categoria::where('slug', $slug)->firstOrFail();
        
        // Obtener subcategorías
        $subcategorias = Categoria::where('parent_id', $categoria->id)
            ->with(['subcategorias' => function ($query) {
                $query->withCount('productos');
            }])
            ->get();

        // Calcular productos en cascada para subcategorías
        foreach ($subcategorias as $sub) {
            $sub->productos_count = $sub->productos()->count();

            foreach ($sub->subcategorias as $subsub) {
                $subsub->productos_count = $subsub->productos()->count();
                // Sumar hacia arriba
                $sub->productos_count += $subsub->productos_count;
            }
        }

        // Obtener productos de esta categoría y sus subcategorías
        $productos = $this->getProductosCategoria($categoria->id);

        return view('categoria', compact('categoria', 'subcategorias', 'productos'));
    }

public function todasCategorias()
{
    // $categoriasPadre -> $c1, $subcategorias -> $sc1, $productos -> $pr1, $ultimosProductos -> $up1, $preciosHot -> $ph1
    // categoriaActual -> ca1, breadcrumb -> b1
    $c1 = Categoria::whereNull('parent_id')
        ->with(['subcategorias' => function ($query) {
            $query->orderBy('nombre');
        }])
        ->orderBy('nombre')
        ->get();

    // Generar lista completa de subcategorías hijas para la vista
    $sc1 = Categoria::whereIn('parent_id', $c1->pluck('id'))
        ->with(['subcategorias' => function ($query) {
            $query->orderBy('nombre');
        }])
        ->orderBy('clicks', 'desc')
        ->get();

    // Obtener todos los productos de todas las categorías y subcategorías
    $pr1 = Producto::with(['categoria.padre.padre'])
        ->where('mostrar', 'si')
        ->orderBy('clicks', 'desc')
        ->paginate(20);

    $up1 = Producto::where('mostrar', 'si')
        ->orderBy('created_at', 'desc')
        ->take(10)
        ->get();

    // Obtener precios hot globales
    $ph1 = PrecioHot::where('nombre', 'Precios Hot')->first();
    $ph1 = $this->procesarDatosPreciosHot($ph1);

    return view('categorias.show', [
        'ca1' => (object)[
            'nombre' => 'Todas las categorías',
        ],
        'c1' => $c1,
        'sc1' => $sc1,
        'pr1' => $pr1,
        'up1' => $up1,
        'b1' => [],
        'ph1' => $ph1,
    ]);
}

    public function showCategoria($slug, Request $request, $filtros = null)
{
    // $categoriaActual -> $ca1
    $ca1 = Categoria::where('slug', $slug)->first();
    if (!$ca1) {
        return redirect()->route('categorias.todas');
    }

    // BREADCRUMB
    // $breadcrumb -> $b1
    $b1 = [];
    $categoria = $ca1;
    while ($categoria) {
        array_unshift($b1, [
            'nombre' => $categoria->nombre,
            'url' => route('categoria.show', $categoria->slug)
        ]);
        $categoria = $categoria->padre;
    }

    // SUBCATEGORÍAS
    // $subcategorias -> $sc1
    $sc1 = Categoria::where('parent_id', $ca1->id)
        ->with(['subcategorias' => function ($query) {
            $query->orderBy('nombre');
        }])
        ->orderBy('clicks', 'desc')
        ->get();

    // ID de todas las subcategorías y sub-subcategorías
    $categoriaIds = [$ca1->id];
    foreach ($sc1 as $sub) {
        $categoriaIds[] = $sub->id;

        $subsubcategorias = Categoria::where('parent_id', $sub->id)->get();
        foreach ($subsubcategorias as $subsub) {
            $categoriaIds[] = $subsub->id;
        }
    }

    // Últimos productos añadidos (de cualquier nivel de esta categoría)
    // $ultimosProductos -> $up1
    $up1 = Producto::whereIn('categoria_id', $categoriaIds)
        ->where('mostrar', 'si')
        ->orderBy('created_at', 'desc')
        ->take(10)
        ->get();

    // Precios Hot específicos de la categoría
    // $preciosHot -> $ph1
    $ph1 = PrecioHot::where('nombre', $ca1->nombre)->first();
    $ph1 = $this->procesarDatosPreciosHot($ph1);

    // Calcular el total de productos de toda la jerarquía (categoría + todas sus hijas)
    // $totalProductosDisponibles -> $tpd1
    $tpd1 = $ca1->obtenerTotalProductos();

    // Verificar si la categoría tiene especificaciones_internas
    $tieneEspecificacionesInternas = $ca1->especificaciones_internas && 
                                     is_array($ca1->especificaciones_internas) && 
                                     isset($ca1->especificaciones_internas['filtros']) &&
                                     count($ca1->especificaciones_internas['filtros']) > 0;

    if ($tieneEspecificacionesInternas) {
        // Parsear filtros de la URL (ignorar los que no existen)
        // $filtrosAplicados -> $fa1, $precioMin -> $pm1, $precioMax -> $pm2, $orden -> $o1, $rebajado -> $r1
        $fa1 = [];
        $pm1 = null;
        $pm2 = null;
        $o1 = $request->input('orden', 'relevancia');
        $r1 = $request->has('rebajado');
        
        if ($filtros) {
            $fa1 = $this->parsearFiltrosUrl($filtros, $ca1);
            $pm1 = $fa1['precio_min'] ?? null;
            $pm2 = $fa1['precio_max'] ?? null;
            unset($fa1['precio_min'], $fa1['precio_max']);
        }

        // Construir query base
        $query = Producto::whereIn('categoria_id', $categoriaIds)
            ->where('mostrar', 'si')
            ->with(['categoria.padre.padre']);

        // Aplicar filtros de especificaciones internas
        if (!empty($fa1)) {
            $query = $this->aplicarFiltrosEspecificaciones($query, $fa1, $categoriaIds);
        }

        // Aplicar filtro de precio
        if ($pm1 !== null || $pm2 !== null) {
            $query->whereBetween('precio', [
                $pm1 ?? 0,
                $pm2 ?? 999999
            ]);
        }

        // Aplicar filtro de rebajado
        if ($r1) {
            $query->whereNotNull('rebajado');
        }

        // Aplicar ordenación
        if ($o1 === 'precio') {
            $query->orderBy('precio', 'asc');
        } elseif ($o1 === 'rebajado') {
            // Ordenar por rebajado de mayor a menor, considerando NULL como 0
            $query->orderByRaw('COALESCE(rebajado, 0) DESC');
        } else {
            $query->orderBy('clicks', 'desc');
        }

        // Aplicar orden natural por nombre
        $query->orderByRaw("
            CASE
                WHEN SUBSTRING_INDEX(nombre, ' ', -1) REGEXP '^[0-9]+' 
                    THEN CAST(SUBSTRING_INDEX(nombre, ' ', -1) AS UNSIGNED)
                ELSE 999999
            END ASC
        ")
        ->orderBy('nombre', 'asc');

        // Paginación (36 productos por página)
        // $productos -> $pr1 (se mantiene localmente pero se pasa como pr1 a la vista)
        $pr1 = $query->paginate(36)->withQueryString();

        // Recalcular precios de productos basándose en ofertas que coinciden con los filtros
        if (!empty($fa1)) {
            $pr1 = $this->recalcularPreciosConFiltros($pr1, $fa1);
        }

        // Obtener todos los productos de la categoría (para calcular contadores y rango de precios)
        $productosTodos = Producto::whereIn('categoria_id', $categoriaIds)
            ->where('mostrar', 'si')
            ->get(['id', 'precio', 'categoria_especificaciones_internas_elegidas']);
        
        // Calcular rango de precios de todos los productos de la categoría (para el slider)
        $precios = $productosTodos->pluck('precio')->filter()->map(function($p) { return (float)$p; });
        $pmg1 = $precios->min() ?? 0;
        $pmg2 = $precios->max() ?? 100;

        // Filtrar solo las líneas principales marcadas como importantes y calcular contadores
        // $filtrosImportantes -> $fi1
        $fi1 = collect($ca1->especificaciones_internas['filtros'])
            ->filter(function($filtro) {
                return isset($filtro['importante']) && $filtro['importante'] === true;
            })
            ->map(function($filtro) use ($productosTodos, $fa1, $pm1, $pm2) {
                $lineaId = $filtro['id'];
                
                // Calcular contadores para cada sublínea
                $subprincipales = collect($filtro['subprincipales'] ?? [])
                    ->map(function($sub) use ($lineaId, $productosTodos, $fa1, $pm1, $pm2) {
                        $sublineaId = $sub['id'];
                        
                        // Contar productos que tienen esta sublínea (aplicando filtros actuales excepto esta línea)
                        $filtrosTemp = is_array($fa1) ? $fa1 : [];
                        if (isset($filtrosTemp[$lineaId])) {
                            unset($filtrosTemp[$lineaId]);
                        }
                        
                        $contador = $productosTodos->filter(function($producto) use ($lineaId, $sublineaId, $filtrosTemp, $pm1, $pm2) {
                            // Aplicar filtros de precio
                            if ($pm1 !== null && $producto->precio < $pm1) return false;
                            if ($pm2 !== null && $producto->precio > $pm2) return false;
                            
                            // Aplicar otros filtros (excepto la línea actual)
                            if (!empty($filtrosTemp)) {
                                foreach ($filtrosTemp as $tempLineaId => $tempSublineasIds) {
                                    if ($tempLineaId === 'precio_min' || $tempLineaId === 'precio_max') continue;
                                    if (empty($tempSublineasIds) || !is_array($tempSublineasIds)) continue;
                                    
                                    $especificaciones = $producto->categoria_especificaciones_internas_elegidas;
                                    if (!$especificaciones || !is_array($especificaciones)) return false;
                                    
                                    $productoLinea = $especificaciones[$tempLineaId] ?? null;
                                    if (!$productoLinea) return false;
                                    
                                    $productoSublineas = is_array($productoLinea) ? $productoLinea : [$productoLinea];
                                    $coincide = false;
                                    foreach ($productoSublineas as $item) {
                                        $itemId = (is_array($item) && isset($item['id'])) ? strval($item['id']) : strval($item);
                                        if (in_array(strval($itemId), array_map('strval', $tempSublineasIds))) {
                                            if (is_array($item) && isset($item['c']) && $item['c'] > 0) {
                                                $coincide = true;
                                                break;
                                            } elseif (!isset($item['c'])) {
                                                $coincide = true;
                                                break;
                                            }
                                        }
                                    }
                                    if (!$coincide) return false;
                                }
                            }
                            
                            // Verificar que el producto tenga esta sublínea específica
                            $especificaciones = $producto->categoria_especificaciones_internas_elegidas;
                            if (!$especificaciones || !is_array($especificaciones)) return false;
                            
                            $productoLinea = $especificaciones[$lineaId] ?? null;
                            if (!$productoLinea) return false;
                            
                            $productoSublineas = is_array($productoLinea) ? $productoLinea : [$productoLinea];
                            foreach ($productoSublineas as $item) {
                                $itemId = (is_array($item) && isset($item['id'])) ? strval($item['id']) : strval($item);
                                $sublineaIdStr = strval($sublineaId);
                                if ($sublineaIdStr === $itemId) {
                                    if (is_array($item) && isset($item['c'])) {
                                        return $item['c'] > 0;
                                    }
                                    return true;
                                }
                            }
                            return false;
                        })->count();
                        
                        $sub['contador'] = $contador;
                        return $sub;
                    })
                    ->values()
                    ->toArray();
                
                // Calcular contador de línea principal (suma de todos los contadores de sublíneas)
                $filtro['contador'] = collect($subprincipales)->sum('contador');
                $filtro['subprincipales'] = $subprincipales;
                
                return $filtro;
            })
            ->filter(function($filtro) {
                return count($filtro['subprincipales'] ?? []) > 0;
            })
            ->values();

        // $categoriaActual -> $ca1, $breadcrumb -> $b1, $productos -> $pr1, $filtrosImportantes -> $fi1
        // $totalProductosDisponibles -> $tpd1, $filtrosAplicados -> $fa1, $precioMin -> $pm1, $precioMax -> $pm2
        // $precioMinGlobal -> $pmg1, $precioMaxGlobal -> $pmg2, $orden -> $o1, $rebajado -> $r1
        return view('categorias.showConFiltros', compact(
            'ca1', // categoriaActual
            'b1',  // breadcrumb
            'pr1', // productos
            'fi1', // filtrosImportantes
            'tpd1', // totalProductosDisponibles
            'fa1', // filtrosAplicados
            'pm1', // precioMin
            'pm2', // precioMax
            'pmg1', // precioMinGlobal
            'pmg2', // precioMaxGlobal
            'o1',  // orden
            'categoriaIds',
            'r1',  // rebajado
        ));
    }

    // Si no tiene especificaciones internas, obtener productos normalmente
    // $productos -> $pr1
    $pr1 = Producto::whereIn('categoria_id', $categoriaIds)
        ->where('mostrar', 'si')
        ->with(['categoria.padre.padre'])
        ->orderBy('clicks', 'desc')
        ->paginate(20);

    // $categoriaActual -> $ca1, $subcategorias -> $sc1, $ultimosProductos -> $up1, $breadcrumb -> $b1, $preciosHot -> $ph1, $totalProductosDisponibles -> $tpd1
    return view('categorias.show', [
        'ca1' => $ca1,
        'sc1' => $sc1,
        'pr1' => $pr1,
        'up1' => $up1,
        'b1' => $b1,
        'ph1' => $ph1,
        'tpd1' => $tpd1,
        'c1' => null, // Para compatibilidad con todasCategorias
    ]);
}


    public function subcategoria($categoriaSlug, $subcategoriaSlug)
    {
        $categoria = Categoria::where('slug', $categoriaSlug)->firstOrFail();
        $subcategoria = Categoria::where('slug', $subcategoriaSlug)
            ->where('parent_id', $categoria->id)
            ->firstOrFail();

        // Obtener sub-subcategorías
        $subsubcategorias = Categoria::where('parent_id', $subcategoria->id)
            ->get();

        // Calcular productos para sub-subcategorías
        foreach ($subsubcategorias as $subsub) {
            $subsub->productos_count = $subsub->productos()->count();
        }

        // Obtener productos de esta subcategoría y sus sub-subcategorías
        $productos = $this->getProductosCategoria($subcategoria->id);

        return view('subcategoria', compact('categoria', 'subcategoria', 'subsubcategorias', 'productos'));
    }

    public function subsubcategoria($categoriaSlug, $subcategoriaSlug, $subsubcategoriaSlug)
    {
        $categoria = Categoria::where('slug', $categoriaSlug)->firstOrFail();
        $subcategoria = Categoria::where('slug', $subcategoriaSlug)
            ->where('parent_id', $categoria->id)
            ->firstOrFail();
        $subsubcategoria = Categoria::where('slug', $subsubcategoriaSlug)
            ->where('parent_id', $subcategoria->id)
            ->firstOrFail();

        // Obtener productos de esta sub-subcategoría
        $productos = Producto::where('categoria_id', $subsubcategoria->id)
            ->where('mostrar', 'si')
            ->with(['categoria.padre.padre']) // Cargar relaciones de categoría
            ->orderBy('clicks', 'desc')
            ->paginate(20);

        // Si solo hay un producto, redirigir a la ruta correcta del producto
        if ($productos->count() === 1) {
            $producto = $productos->first();
            return redirect()->route('admin.producto.detalle', [
                'cat1' => $categoria->slug,
                'cat2' => $subcategoria->slug,
                'cat3' => $subsubcategoria->slug,
                'slug' => $producto->slug
            ]);
        }

        return view('subsubcategoria', compact('categoria', 'subcategoria', 'subsubcategoria', 'productos'));
    }

    private function getProductosCategoria($categoriaId)
    {
        // Obtener todos los IDs de subcategorías y sub-subcategorías
        $categoriaIds = [$categoriaId];
    
        $subcategorias = Categoria::where('parent_id', $categoriaId)->get();
        foreach ($subcategorias as $sub) {
            $categoriaIds[] = $sub->id;
            $subsubcategorias = Categoria::where('parent_id', $sub->id)->get();
            foreach ($subsubcategorias as $subsub) {
                $categoriaIds[] = $subsub->id;
            }
        }
    
        // Orden natural: si el último token empieza por número, ordenar por ese número (numérico),
        // si no, se asigna un valor alto para que los no-numéricos queden después de los numéricos.
        // Luego ordenar por nombre alfabéticamente para diferenciar "5" vs "5+"
        $productos = Producto::whereIn('categoria_id', $categoriaIds)
            ->where('mostrar', 'si')
            ->with(['categoria.padre.padre'])
            ->orderByRaw("
                CASE
                    WHEN SUBSTRING_INDEX(nombre, ' ', -1) REGEXP '^[0-9]+' 
                        THEN CAST(SUBSTRING_INDEX(nombre, ' ', -1) AS UNSIGNED)
                    ELSE 999999
                END ASC
            ")
            ->orderBy('nombre', 'asc')
            ->paginate(20);
    
        return $productos;
    }

    /**
     * Genera un slug desde un texto
     */
    private function generarSlug($texto)
    {
        // DEBUG: Log para ver qué contiene $texto
        error_log('DEBUG HomeController generarSlug - $texto tipo: ' . gettype($texto));
        error_log('DEBUG HomeController generarSlug - $texto valor: ' . var_export($texto, true));
        
        $slug = \Illuminate\Support\Str::slug($texto);
        
        error_log('DEBUG HomeController generarSlug - slug generado: ' . $slug);
        
        return $slug;
    }

    /**
     * Encuentra todos los slugs válidos en un segmento concatenado
     * Ejemplo: "talla-1-talla-2" → encuentra ["talla-1", "talla-2"]
     */
    private function encontrarSlugsEnSegmento($segmento, $mapaSlugs)
    {
        $slugsEncontrados = [];
        $partes = explode('-', $segmento);
        $longitud = count($partes);
        
        // Intentar encontrar todos los slugs válidos usando un enfoque greedy
        $posicion = 0;
        while ($posicion < $longitud) {
            $slugEncontrado = null;
            $longitudSlug = 0;
            
            // Intentar encontrar el slug más largo posible desde la posición actual
            for ($i = $longitud; $i > $posicion; $i--) {
                $candidato = implode('-', array_slice($partes, $posicion, $i - $posicion));
                if (isset($mapaSlugs[$candidato])) {
                    $slugEncontrado = $candidato;
                    $longitudSlug = $i - $posicion;
                    break;
                }
            }
            
            if ($slugEncontrado) {
                $slugsEncontrados[] = $mapaSlugs[$slugEncontrado];
                $posicion += $longitudSlug;
            } else {
                // Si no se encuentra ningún slug válido, avanzar una posición para evitar bucle infinito
                $posicion++;
            }
        }
        
        return $slugsEncontrados;
    }

    /**
     * Parsea los filtros de la URL e ignora los que no existen
     * Retorna array con estructura: ['id_linea_principal' => ['id_sublinea1', 'id_sublinea2'], 'precio_min' => X, 'precio_max' => Y]
     */
    private function parsearFiltrosUrl($filtros, $categoria)
    {
        $filtrosAplicados = [];
        $precioMin = null;
        $precioMax = null;

        // Obtener estructura de filtros actual
        $estructuraFiltros = $categoria->especificaciones_internas['filtros'] ?? [];
        
        // Crear mapa de slugs a IDs (solo filtros que existen AHORA)
        $mapaSlugs = [];
        foreach ($estructuraFiltros as $filtro) {
            $lineaId = $filtro['id'] ?? null;
            if (!$lineaId) continue;
            
            foreach ($filtro['subprincipales'] ?? [] as $sub) {
                // DEBUG: Log para ver qué contiene $sub
                error_log('DEBUG HomeController parsearFiltrosUrl - $sub completo: ' . json_encode($sub));
                error_log('DEBUG HomeController parsearFiltrosUrl - $sub[texto] tipo: ' . gettype($sub['texto'] ?? 'NO_EXISTE'));
                error_log('DEBUG HomeController parsearFiltrosUrl - $sub[texto] valor: ' . var_export($sub['texto'] ?? 'NO_EXISTE', true));
                
                $sublineaId = $sub['id'] ?? null;
                $slug = $sub['slug'] ?? null;
                
                // Si no tiene slug, generarlo desde el texto
                if (!$slug && isset($sub['texto'])) {
                    $slug = $this->generarSlug($sub['texto']);
                }
                
                error_log('DEBUG HomeController parsearFiltrosUrl - slug final: ' . ($slug ?? 'NULL'));
                
                if ($slug && $sublineaId) {
                    $mapaSlugs[$slug] = [
                        'id' => $sublineaId,
                        'linea_principal_id' => $lineaId,
                        'texto' => $sub['texto'] ?? ''
                    ];
                }
            }
        }

        // Parsear segmentos de la URL
        $segmentos = explode('/', trim($filtros, '/'));
        
        foreach ($segmentos as $segmento) {
            // Detectar precio: precio-10-50
            if (strpos($segmento, 'precio-') === 0) {
                $precio = str_replace('precio-', '', $segmento);
                $precios = explode('-', $precio);
                $precioMin = isset($precios[0]) ? (float)$precios[0] : null;
                $precioMax = isset($precios[1]) ? (float)$precios[1] : null;
                continue;
            }
            
            // Buscar el slug en el mapa actual
            // Intentar coincidencia exacta primero
            if (isset($mapaSlugs[$segmento])) {
                $info = $mapaSlugs[$segmento];
                $lineaId = $info['linea_principal_id'];
                
                if (!isset($filtrosAplicados[$lineaId])) {
                    $filtrosAplicados[$lineaId] = [];
                }
                
                $filtrosAplicados[$lineaId][] = $info['id'];
            } else {
                // Intentar con múltiples valores concatenados: talla-1-talla-2
                $slugsEncontrados = $this->encontrarSlugsEnSegmento($segmento, $mapaSlugs);
                
                foreach ($slugsEncontrados as $info) {
                    $lineaId = $info['linea_principal_id'];
                    
                    if (!isset($filtrosAplicados[$lineaId])) {
                        $filtrosAplicados[$lineaId] = [];
                    }
                    
                    $filtrosAplicados[$lineaId][] = $info['id'];
                }
            }
        }
        
        // Eliminar duplicados
        foreach ($filtrosAplicados as $lineaId => $sublineas) {
            $filtrosAplicados[$lineaId] = array_unique($sublineas);
        }
        
        $filtrosAplicados['precio_min'] = $precioMin;
        $filtrosAplicados['precio_max'] = $precioMax;
        
        return $filtrosAplicados;
    }

    /**
     * Aplica filtros de especificaciones internas a la query
     * Usa filtrado en memoria para mayor flexibilidad con estructuras JSON variables
     */
    private function aplicarFiltrosEspecificaciones($query, $filtrosAplicados, $categoriaIds)
    {
        if (empty($categoriaIds)) {
            return $query->whereRaw('1 = 0'); // No hay categorías, retornar vacío
        }
        
        // Obtener todos los productos de las categorías
        $productos = Producto::whereIn('categoria_id', $categoriaIds)
            ->where('mostrar', 'si')
            ->get(['id', 'categoria_especificaciones_internas_elegidas']);
        
        // Filtrar productos que cumplan con los filtros
        $productosFiltrados = $productos->filter(function($producto) use ($filtrosAplicados) {
            $especificaciones = $producto->categoria_especificaciones_internas_elegidas;
            if (!$especificaciones || !is_array($especificaciones)) {
                return false;
            }
            
            // Verificar cada línea principal seleccionada
            foreach ($filtrosAplicados as $lineaId => $sublineasIds) {
                if ($lineaId === 'precio_min' || $lineaId === 'precio_max') {
                    continue;
                }
                
                if (empty($sublineasIds) || !is_array($sublineasIds)) {
                    continue;
                }
                
                $productoLinea = $especificaciones[$lineaId] ?? null;
                if (!$productoLinea) {
                    return false;
                }
                
                // Estructura optimizada: array de objetos {id, m, o, c} o array de IDs
                $productoSublineas = is_array($productoLinea) ? $productoLinea : [$productoLinea];
                $sublineasSet = array_flip(array_map('strval', $sublineasIds));
                
                // Verificar si alguna sublínea del producto coincide con las seleccionadas
                $coincide = false;
                foreach ($productoSublineas as $item) {
                    $itemId = (is_array($item) && isset($item['id'])) ? strval($item['id']) : strval($item);
                    if (isset($sublineasSet[$itemId])) {
                        // Verificar que tenga ofertas disponibles si tiene el campo 'c'
                        if (is_array($item) && isset($item['c'])) {
                            if ($item['c'] > 0) {
                                $coincide = true;
                                break;
                            }
                        } else {
                            $coincide = true;
                            break;
                        }
                    }
                }
                
                if (!$coincide) {
                    return false;
                }
            }
            
            return true;
        })
        ->pluck('id')
        ->toArray();
        
        if (empty($productosFiltrados)) {
            return $query->whereRaw('1 = 0'); // No hay productos que cumplan, retornar vacío
        }
        
        return $query->whereIn('id', array_values($productosFiltrados));
    }

    /**
     * Recalcula los precios de los productos basándose en las ofertas que coinciden con los filtros aplicados
     * Elimina productos que no tienen ofertas disponibles para la combinación de filtros marcados
     * 
     * @param \Illuminate\Contracts\Pagination\LengthAwarePaginator $productos Colección paginada de productos
     * @param array $filtrosAplicados Filtros aplicados (formato: [lineaId => [sublineaId1, sublineaId2, ...]])
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator Productos con precios recalculados y filtrados
     */
    private function recalcularPreciosConFiltros($productos, $filtrosAplicados)
    {
        if (empty($filtrosAplicados)) {
            return $productos;
        }

        $servicioOfertas = new \App\Services\SacarPrimeraOfertaDeUnProductoAplicadoDescuentosYChollos();

        // Filtrar y procesar cada producto
        $productosFiltrados = $productos->getCollection()->filter(function($producto) use ($filtrosAplicados, $servicioOfertas) {
            // Verificar si el producto tiene las sublineas marcadas en sus especificaciones internas
            $especificacionesProducto = $producto->categoria_especificaciones_internas_elegidas;
            
            if (!$especificacionesProducto || !is_array($especificacionesProducto)) {
                // Si no tiene especificaciones, mantener el producto con precio original
                return true;
            }

            // Verificar si el producto tiene TODAS las sublineas seleccionadas (para múltiples filtros)
            // Necesitamos verificar que el producto tenga al menos una sublinea de cada línea principal seleccionada
            // El checkbox principal (antes del texto) determina si el producto "conviene" para ese filtro
            $tieneTodasLasLineas = true;
            $debeActualizarPrecio = false; // Solo se actualiza si al menos una sublinea tiene m: 1 o m: true
            $filtrosConMostrar = []; // Solo los filtros que tienen m: true necesitan buscar ofertas
            
            foreach ($filtrosAplicados as $lineaId => $sublineasIds) {
                if ($lineaId === 'precio_min' || $lineaId === 'precio_max') {
                    continue;
                }
                
                if (empty($sublineasIds) || !is_array($sublineasIds)) {
                    continue;
                }

                $productoLinea = $especificacionesProducto[$lineaId] ?? null;
                if (!$productoLinea) {
                    // Si el producto no tiene esta línea principal, no cumple con el filtro
                    $tieneTodasLasLineas = false;
                    break;
                }

                $productoSublineas = is_array($productoLinea) ? $productoLinea : [$productoLinea];
                
                // Verificar si alguna sublínea del producto coincide con las seleccionadas de esta línea
                $tieneSublineaDeEstaLinea = false;
                $tieneMostrarEnEstaLinea = false;
                foreach ($productoSublineas as $item) {
                    $itemId = (is_array($item) && isset($item['id'])) ? strval($item['id']) : strval($item);
                    if (in_array(strval($itemId), array_map('strval', $sublineasIds))) {
                        // El producto tiene esta sublinea marcada (conviene para este filtro)
                        $tieneSublineaDeEstaLinea = true;
                        
                        // Verificar si tiene el campo 'm' (mostrar) para actualizar precio
                        // m: 1, m: true, o mostrar: true significa que se debe actualizar el precio
                        if (is_array($item)) {
                            $tieneMostrar = isset($item['m']) && ($item['m'] === 1 || $item['m'] === true) ||
                                          (isset($item['mostrar']) && $item['mostrar'] === true);
                            if ($tieneMostrar) {
                                $debeActualizarPrecio = true;
                                $tieneMostrarEnEstaLinea = true;
                            }
                        }
                        break;
                    }
                }
                
                // Si no tiene ninguna sublinea de esta línea, el producto no cumple
                if (!$tieneSublineaDeEstaLinea) {
                    $tieneTodasLasLineas = false;
                    break;
                }
                
                // Si esta línea tiene m: true, añadirla a los filtros que requieren buscar ofertas
                if ($tieneMostrarEnEstaLinea) {
                    $filtrosConMostrar[$lineaId] = $sublineasIds;
                }
            }

            // Si el producto no tiene todas las líneas requeridas, no mostrarlo
            if (!$tieneTodasLasLineas) {
                return false;
            }

            // Si el producto conviene para todos los filtros pero no tiene ninguna sublinea con m: true,
            // se muestra con su precio original (no se buscan ofertas)
            if (!$debeActualizarPrecio || empty($filtrosConMostrar)) {
                // El producto se muestra pero con su precio original
                return true;
            }

            // Si debe actualizar precio, buscar ofertas que coincidan SOLO con los filtros que tienen m: true
            $ofertas = $servicioOfertas->obtenerTodas($producto);

            if ($ofertas->isEmpty()) {
                // Si no hay ofertas, no mostrar el producto (porque necesita actualizar precio pero no hay ofertas)
                return false;
            }

            // Filtrar ofertas que coincidan SOLO con los filtros que tienen m: true (no con todos)
            $ofertasFiltradas = $ofertas->filter(function($oferta) use ($filtrosConMostrar) {
                $especificacionesOferta = $oferta->especificaciones_internas;
                
                // Si la oferta no tiene especificaciones internas, no coincide
                if (!$especificacionesOferta || !is_array($especificacionesOferta)) {
                    return false;
                }

                // Verificar que la oferta cumpla con TODAS las líneas principales que tienen m: true
                foreach ($filtrosConMostrar as $lineaId => $sublineasIds) {
                    if (empty($sublineasIds) || !is_array($sublineasIds)) {
                        continue;
                    }

                    $ofertaLinea = $especificacionesOferta[$lineaId] ?? null;
                    if (!$ofertaLinea) {
                        // Si la oferta no tiene esta línea principal, no coincide
                        return false;
                    }

                    $ofertaSublineas = is_array($ofertaLinea) ? $ofertaLinea : [$ofertaLinea];
                    
                    // Verificar si alguna sublínea de la oferta coincide con las seleccionadas de esta línea
                    $coincideEstaLinea = false;
                    foreach ($ofertaSublineas as $item) {
                        $itemId = (is_array($item) && isset($item['id'])) ? strval($item['id']) : strval($item);
                        if (in_array(strval($itemId), array_map('strval', $sublineasIds))) {
                            $coincideEstaLinea = true;
                            break;
                        }
                    }
                    
                    // Si no coincide con esta línea, la oferta no cumple con los filtros que requieren actualizar precio
                    if (!$coincideEstaLinea) {
                        return false;
                    }
                }

                // Si llegamos aquí, la oferta cumple con todos los filtros que tienen m: true
                return true;
            });

            // Si no hay ofertas que coincidan con los filtros que tienen m: true, no mostrar el producto
            if ($ofertasFiltradas->isEmpty()) {
                return false;
            }

            // Si hay ofertas que coinciden, usar el precio_unidad más bajo
            $precioMasBajo = $ofertasFiltradas->min('precio_unidad');
            if ($precioMasBajo !== null) {
                // Asignar el precio más bajo temporalmente (sin modificar la BD)
                $producto->precio = $precioMasBajo;
            }

            return true;
        });

        // Reemplazar la colección con los productos filtrados
        $productos->setCollection($productosFiltrados);

        return $productos;
    }

} 