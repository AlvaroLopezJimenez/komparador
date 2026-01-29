<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Producto;
use App\Models\Categoria;
use App\Models\PrecioHot;
use App\Models\Click;
use App\Helpers\CategoriaHelper;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use App\Services\SacarPrimeraOfertaDeUnProductoAplicadoDescuentosYChollos;

class BuscadorController extends Controller
{
    public function productos(Request $request)
    {
        $request->validate([
            'q' => 'required|string|min:2|max:50'
        ]);

        $query = $request->get('q', '');
        
        if (empty($query)) {
            return response()->json([]);
        }

        // Normalizar la consulta: convertir a minúsculas y dividir en palabras
        $queryLower = strtolower(trim($query));
        $palabras = array_filter(
            explode(' ', $queryLower),
            fn($palabra) => strlen($palabra) >= 2
        );
        
        if (empty($palabras)) {
            return response()->json([]);
        }

        try {
            // Buscar categorías (mejorado)
            $categorias = $this->buscarCategorias($palabras, $queryLower, 3);

            // Buscar productos (mejorado)
            $productos = $this->buscarProductos($palabras, $queryLower, 7);

            // Combinar resultados: categorías primero, luego productos
            $resultados = $categorias->concat($productos);

            return response()->json($resultados->values()->all());
        } catch (\Exception $e) {
            \Log::error('Error en productos search: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json([]);
        }
    }

    public function tiendas(Request $request)
    {
        $query = $request->get('q', '');
        
        if (empty($query)) {
            return response()->json([]);
        }

        $tiendas = \App\Models\Tienda::whereRaw('LOWER(nombre) LIKE ?', ['%' . strtolower($query) . '%'])
            ->limit(10)
            ->get(['id', 'nombre']);

        return response()->json($tiendas);
    }

    public function buscar(Request $request)
    {
        $query = $request->get('q', '');
        
        // Si la query está vacía o solo tiene espacios, redirigir a precios hot
        if (empty(trim($query))) {
            $params = ['q' => 'precios hot'];
            // Preservar el parámetro 'cam' si existe
            if ($request->has('cam')) {
                $params['cam'] = $request->get('cam');
            }
            return redirect()->route('buscar', $params);
        }

        // Normalizar la consulta: convertir a minúsculas y dividir en palabras
        $queryLower = strtolower(trim($query));
        
        // Detectar si se busca "precios hot"
        $esPreciosHot = in_array($queryLower, ['precios hot', 'precioshot', 'precio hot', 'preciohot']);
        
        if ($esPreciosHot) {
            return $this->buscarPreciosHot($request);
        }
        
        // Detectar si se busca "más vendidos"
        $esMasVendidos = in_array($queryLower, ['más vendidos', 'mas vendidos', 'masvendidos', 'másvendidos', 'mas vendido', 'más vendido']);
        
        if ($esMasVendidos) {
            return $this->buscarMasVendidos($request);
        }

        $palabras = array_filter(
            explode(' ', $queryLower),
            fn($palabra) => strlen($palabra) >= 2
        );

        $queryBuilder = Producto::where('obsoleto', 'no')
            ->where('mostrar', 'si');

        if (!empty($palabras)) {
            // Obtener IDs de categorías que coinciden con las palabras
            $categoriaIds = $this->obtenerCategoriaIdsPorPalabras($palabras, $queryLower);

            $queryBuilder->where(function($q) use ($palabras, $queryLower, $categoriaIds) {
                // 1. Búsqueda directa: nombre del producto contiene la búsqueda completa
                $q->whereRaw('LOWER(nombre) LIKE ?', ['%' . $queryLower . '%'])
                  ->orWhereRaw('LOWER(marca) LIKE ?', ['%' . $queryLower . '%'])
                  ->orWhereRaw('LOWER(modelo) LIKE ?', ['%' . $queryLower . '%'])
                  ->orWhereRaw('LOWER(talla) LIKE ?', ['%' . $queryLower . '%']);

                // 2. Búsqueda por palabras individuales (si hay más de una palabra)
                if (count($palabras) > 1) {
                    $q->orWhere(function($subQ) use ($palabras) {
                        // Todas las palabras deben aparecer en algún campo
                        foreach ($palabras as $palabra) {
                            $subQ->where(function($palabraQ) use ($palabra) {
                                $palabraQ->whereRaw('LOWER(nombre) LIKE ?', ['%' . $palabra . '%'])
                                         ->orWhereRaw('LOWER(marca) LIKE ?', ['%' . $palabra . '%'])
                                         ->orWhereRaw('LOWER(modelo) LIKE ?', ['%' . $palabra . '%'])
                                         ->orWhereRaw('LOWER(talla) LIKE ?', ['%' . $palabra . '%']);
                            });
                        }
                    });
                }

                // 3. Búsqueda por categorías en la jerarquía
                if (!empty($categoriaIds)) {
                    $q->orWhereIn('categoria_id', $categoriaIds);
                }

                // 4. Búsqueda en especificaciones internas (igual que en buscarProductos)
                $q->orWhere(function($specQ) use ($palabras) {
                    $specQ->whereNotNull('especificaciones_busqueda_texto');
                    // Al menos una palabra debe estar en especificaciones_busqueda_texto
                    foreach ($palabras as $palabra) {
                        $specQ->orWhereRaw('LOWER(especificaciones_busqueda_texto) LIKE ?', ['%' . $palabra . '%']);
                    }
                });
            });
        } else {
            // Fallback a búsqueda original si no hay palabras válidas
            $queryBuilder->where(function($q) use ($queryLower) {
                $q->whereRaw('LOWER(nombre) LIKE ?', ['%' . $queryLower . '%'])
                  ->orWhereRaw('LOWER(marca) LIKE ?', ['%' . $queryLower . '%'])
                  ->orWhereRaw('LOWER(modelo) LIKE ?', ['%' . $queryLower . '%'])
                  ->orWhereRaw('LOWER(talla) LIKE ?', ['%' . $queryLower . '%'])
                  ->orWhere(function($specQ) use ($queryLower) {
                      $specQ->whereNotNull('especificaciones_busqueda_texto')
                            ->whereRaw('LOWER(especificaciones_busqueda_texto) LIKE ?', ['%' . $queryLower . '%']);
                  });
            });
        }

        // Limitar a máximo 5 páginas (100 productos)
        $maxProductos = 100;
        $perPage = 20;
        $currentPage = Paginator::resolveCurrentPage() ?: 1;
        $maxPages = 5;
        
        // Validar que la página no sea mayor a 5 - si lo es, redirigir a página 1
        if ($currentPage > $maxPages) {
            $queryParams = $request->query();
            $queryParams['page'] = 1;
            return redirect()->route('buscar', $queryParams);
        }
        
        // Obtener productos limitados
        $productos = $queryBuilder
            ->orderBy('clicks', 'desc')
            ->limit($maxProductos * 2) // Obtener más productos para generar variantes
            ->with('categoria.parent.parent')
            ->get();
        
        // Generar variantes de productos (igual que en buscarProductos)
        $productosConVariantes = $this->generarVariantesProductosParaBuscar($productos, $palabras, $queryLower);
        
        // Ordenar por relevancia (coincidencia con la búsqueda) y luego por clicks
        $productosConVariantes = $productosConVariantes->sortByDesc(function($item) use ($queryLower, $palabras) {
            $relevancia = 0;
            $producto = $item['producto'];
            $variante = $item['variante'] ?? null;
            
            // Construir nombre completo para comparar
            $nombreCompleto = strtolower($producto->nombre);
            if ($variante) {
                $partesNombre = [];
                if (!empty($producto->marca)) {
                    $partesNombre[] = strtolower($producto->marca);
                }
                if (!empty($producto->modelo)) {
                    $partesNombre[] = strtolower($producto->modelo);
                }
                if (!empty($variante)) {
                    $partesNombre[] = strtolower($variante);
                }
                $nombreCompleto = !empty($partesNombre) ? implode(' ', $partesNombre) : strtolower($producto->nombre);
            } else {
                $nombreCompleto = strtolower($producto->nombre . ' ' . $producto->marca . ' ' . $producto->modelo);
            }
            
            // 1. Coincidencia exacta con la búsqueda completa (máxima relevancia)
            if ($nombreCompleto === $queryLower) {
                $relevancia += 10000;
            }
            // 2. La búsqueda completa está contenida en el nombre
            elseif (str_contains($nombreCompleto, $queryLower)) {
                $relevancia += 5000;
            }
            // 3. El nombre está contenido en la búsqueda
            elseif (str_contains($queryLower, $nombreCompleto)) {
                $relevancia += 4000;
            }
            
            // 4. Contar cuántas palabras de la búsqueda están en el nombre
            $palabrasCoincidentes = 0;
            foreach ($palabras as $palabra) {
                if (str_contains($nombreCompleto, strtolower($palabra))) {
                    $palabrasCoincidentes++;
                }
            }
            $relevancia += $palabrasCoincidentes * 1000;
            
            // 5. Verificar si el nombre del producto (sin variante) coincide
            $nombreProductoLower = strtolower($producto->nombre . ' ' . $producto->marca . ' ' . $producto->modelo);
            if (str_contains($nombreProductoLower, $queryLower)) {
                $relevancia += 500;
            }
            
            // 6. Añadir clicks como factor secundario (dividido por 1000 para que no sobrescriba la relevancia)
            $relevancia += ($producto->clicks ?? 0) / 1000;
            
            return $relevancia;
        })->values();
        
        // Paginación manual
        $items = $productosConVariantes->slice(($currentPage - 1) * $perPage, $perPage)->values();
        $p4X7 = new LengthAwarePaginator(
            $items,
            $productosConVariantes->count(),
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $q1X2 = $query;

        return view('buscar', compact('p4X7', 'q1X2'));
    }

    /**
     * Busca productos de Precios Hot
     */
    private function buscarPreciosHot(Request $request)
    {
        $precioHot = PrecioHot::where('nombre', 'Precios Hot')->first();
        
        if (!$precioHot || empty($precioHot->datos)) {
            // Si no hay precios hot, devolver búsqueda vacía
            $p4X7 = new LengthAwarePaginator([], 0, 20, 1);
            $q1X2 = 'precios hot';
            return view('buscar', compact('p4X7', 'q1X2'));
        }

        // Procesar datos de precios hot
        $datos = collect($precioHot->datos)
            ->map(function ($item) {
                if (!is_array($item)) {
                    return null;
                }
                
                // Obtener el producto
                $producto = Producto::find($item['producto_id'] ?? null);
                if (!$producto) {
                    return null;
                }
                
                return [
                    'producto' => $producto,
                    'porcentaje_diferencia' => (int) floatval(str_replace(',', '.', $item['porcentaje_diferencia'] ?? 0)),
                    'precio_oferta' => $item['precio_oferta'] ?? 0,
                    'unidad_medida' => $item['unidad_medida'] ?? $producto->unidadDeMedida,
                    'url_producto' => $item['url_producto'] ?? ($producto->categoria ? $producto->categoria->construirUrlCategorias($producto->slug) : '#'),
                ];
            })
            ->filter()
            ->sortByDesc('porcentaje_diferencia') // Ordenar por % de descuento descendente
            ->values();

        // Limitar a máximo 5 páginas (100 productos)
        $maxProductos = 100;
        $datosLimitados = $datos->take($maxProductos);

        // Paginación manual
        $perPage = 20;
        $currentPage = Paginator::resolveCurrentPage() ?: 1;
        
        // Validar que la página no sea mayor a 5 - si lo es, redirigir a página 1
        $maxPages = 5;
        if ($currentPage > $maxPages) {
            $queryParams = $request->query();
            $queryParams['page'] = 1;
            return redirect()->route('buscar', $queryParams);
        }
        
        $items = $datosLimitados->slice(($currentPage - 1) * $perPage, $perPage)->values();
        $p4X7 = new LengthAwarePaginator(
            $items,
            $datosLimitados->count(),
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $q1X2 = 'precios hot';

        return view('buscar', compact('p4X7', 'q1X2'));
    }

    /**
     * Busca productos más vendidos ordenados por clicks
     */
    private function buscarMasVendidos(Request $request)
    {
        // Obtener rango de días (hoy=1, 7 días=7, 30 días=30)
        $dias = (int) $request->get('dias', 7);
        
        // Validar que sea uno de los valores permitidos - si no, redirigir a 7 días
        if (!in_array($dias, [1, 7, 30])) {
            $queryParams = $request->query();
            $queryParams['dias'] = 7;
            $queryParams['q'] = 'más vendidos';
            return redirect()->route('buscar', $queryParams);
        }
        
        // Calcular fecha de inicio según los días
        $fechaInicio = $dias === 1 
            ? now()->startOfDay() 
            : now()->subDays($dias - 1)->startOfDay();
        
        // Obtener productos con sus clicks en el rango de días
        $productos = Producto::where('obsoleto', 'no')
            ->where('mostrar', 'si')
            ->with(['categoria', 'ofertas'])
            ->get()
            ->map(function ($producto) use ($fechaInicio) {
                // Contar clicks de las ofertas de este producto en el rango
                $clicks = Click::whereHas('oferta', function($query) use ($producto) {
                    $query->where('producto_id', $producto->id);
                })
                ->where('created_at', '>=', $fechaInicio)
                ->count();
                
                return [
                    'producto' => $producto,
                    'clicks' => $clicks
                ];
            })
            ->filter(function ($item) {
                // Solo productos con clicks > 0
                return $item['clicks'] > 0;
            })
            ->sortByDesc('clicks')
            ->values();
        
        // Limitar a máximo 5 páginas (100 productos)
        $maxProductos = 100;
        $productosLimitados = $productos->take($maxProductos);
        
        // Paginación manual
        $perPage = 20;
        $currentPage = Paginator::resolveCurrentPage() ?: 1;
        
        // Validar que la página no sea mayor a 5 - si lo es, redirigir a página 1
        $maxPages = 5;
        if ($currentPage > $maxPages) {
            $queryParams = $request->query();
            $queryParams['page'] = 1;
            return redirect()->route('buscar', $queryParams);
        }
        
        $items = $productosLimitados->slice(($currentPage - 1) * $perPage, $perPage)->values();
        
        // Extraer solo los productos para la vista
        $itemsProductos = $items->map(function ($item) {
            return $item['producto'];
        });
        
        $p4X7 = new LengthAwarePaginator(
            $itemsProductos,
            $productosLimitados->count(),
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );
        
        $q1X2 = 'más vendidos';
        $diasSeleccionado = $dias;

        return view('buscar', compact('p4X7', 'q1X2', 'diasSeleccionado'));
    }

    /**
     * Busca categorías considerando la jerarquía y palabras múltiples
     * Prioriza categorías más específicas (hijas) sobre generales (padres)
     */
    private function buscarCategorias(array $palabras, string $queryCompleta, int $limite)
    {
        $categoriasEncontradas = collect();
        $idsYaIncluidos = [];

        // 1. Búsqueda directa: categorías cuyo nombre coincida con la búsqueda completa
        $categoriasDirectas = Categoria::whereRaw('LOWER(nombre) LIKE ?', ['%' . $queryCompleta . '%'])
            ->get();

        foreach ($categoriasDirectas as $categoria) {
            if (!in_array($categoria->id, $idsYaIncluidos)) {
                $nivel = $categoria->obtenerNivel();
                $categoriasEncontradas->push([
                    'categoria' => $categoria,
                    'nivel' => $nivel,
                    'clicks' => $categoria->clicks ?? 0,
                ]);
                $idsYaIncluidos[] = $categoria->id;
            }
        }

        // 2. Búsqueda por palabras individuales y jerarquía (para búsquedas como "pañales dodot")
        if (count($palabras) > 1) {
            foreach ($palabras as $palabra) {
                $categorias = Categoria::whereRaw('LOWER(nombre) LIKE ?', ['%' . $palabra . '%'])
                    ->get();

                foreach ($categorias as $categoria) {
                    if (in_array($categoria->id, $idsYaIncluidos)) {
                        continue;
                    }

                    // Obtener toda la jerarquía de esta categoría
                    $jerarquia = CategoriaHelper::obtenerJerarquiaCompleta($categoria->id);
                    $nombresJerarquia = collect($jerarquia)->pluck('nombre')->map(fn($n) => strtolower($n))->toArray();
                    $textoJerarquia = implode(' ', $nombresJerarquia);

                    // Verificar si todas las palabras están en la jerarquía completa
                    $todasLasPalabrasCoinciden = collect($palabras)->every(
                        fn($pal) => str_contains($textoJerarquia, $pal)
                    );

                    // También verificar coincidencia de la consulta completa en la jerarquía
                    $coincidenciaEnJerarquia = str_contains($textoJerarquia, $queryCompleta);

                    // Incluir la categoría si todas las palabras están en la jerarquía
                    if ($todasLasPalabrasCoinciden || $coincidenciaEnJerarquia) {
                        $nivel = $categoria->obtenerNivel();
                        
                        $categoriasEncontradas->push([
                            'categoria' => $categoria,
                            'nivel' => $nivel,
                            'clicks' => $categoria->clicks ?? 0,
                        ]);
                        $idsYaIncluidos[] = $categoria->id;
                    }
                }
            }
        }

        return $categoriasEncontradas
            ->sortBy([
                ['nivel', 'desc'], // Primero las más específicas (mayor nivel)
                ['clicks', 'desc'] // Luego por clicks
            ])
            ->take($limite)
            ->map(function ($item) {
                $categoria = $item['categoria'];
                return [
                    'id' => $categoria->id,
                    'nombre' => $categoria->nombre,
                    'slug' => $categoria->slug,
                    'imagen' => $categoria->imagen,
                    'url' => '/categoria/' . $categoria->slug,
                    'tipo' => 'categoria'
                ];
            });
    }

    /**
     * Busca productos considerando nombre, marca, modelo, talla, jerarquía de categorías
     * y especificaciones internas (usando el índice de búsqueda)
     */
    private function buscarProductos(array $palabras, string $queryCompleta, int $limite)
    {
        // Obtener IDs de categorías que coinciden con las palabras
        $categoriaIds = $this->obtenerCategoriaIdsPorPalabras($palabras, $queryCompleta);

        $query = Producto::where('obsoleto', 'no')
            ->where('mostrar', 'si')
            ->where(function($q) use ($palabras, $queryCompleta, $categoriaIds) {
                // 1. Búsqueda directa: nombre del producto contiene la búsqueda completa
                $q->whereRaw('LOWER(nombre) LIKE ?', ['%' . $queryCompleta . '%'])
                  ->orWhereRaw('LOWER(marca) LIKE ?', ['%' . $queryCompleta . '%'])
                  ->orWhereRaw('LOWER(modelo) LIKE ?', ['%' . $queryCompleta . '%'])
                  ->orWhereRaw('LOWER(talla) LIKE ?', ['%' . $queryCompleta . '%']);

                // 2. Búsqueda por palabras individuales (si hay más de una palabra)
                if (count($palabras) > 1) {
                    $q->orWhere(function($subQ) use ($palabras) {
                        // Todas las palabras deben aparecer en algún campo
                        foreach ($palabras as $palabra) {
                            $subQ->where(function($palabraQ) use ($palabra) {
                                $palabraQ->whereRaw('LOWER(nombre) LIKE ?', ['%' . $palabra . '%'])
                                         ->orWhereRaw('LOWER(marca) LIKE ?', ['%' . $palabra . '%'])
                                         ->orWhereRaw('LOWER(modelo) LIKE ?', ['%' . $palabra . '%'])
                                         ->orWhereRaw('LOWER(talla) LIKE ?', ['%' . $palabra . '%']);
                            });
                        }
                    });
                }

                // 3. Búsqueda por categorías en la jerarquía
                if (!empty($categoriaIds)) {
                    $q->orWhereIn('categoria_id', $categoriaIds);
                }

                // 4. Búsqueda en especificaciones internas
                // Buscar en especificaciones_busqueda_texto usando LIKE (más compatible)
                $q->orWhere(function($specQ) use ($palabras) {
                    $specQ->whereNotNull('especificaciones_busqueda_texto');
                    // Al menos una palabra debe estar en especificaciones_busqueda_texto
                    foreach ($palabras as $palabra) {
                        $specQ->orWhereRaw('LOWER(especificaciones_busqueda_texto) LIKE ?', ['%' . $palabra . '%']);
                    }
                });
            });

        try {
            $productos = $query
                ->orderBy('clicks', 'desc')
                ->limit($limite * 2) // Obtener más productos para generar variantes
                ->with('categoria.parent.parent')
                ->get();

            // Generar variantes de productos
            $resultados = $this->generarVariantesProductos($productos, $palabras, $queryCompleta);

            // Ordenar por relevancia (coincidencia con la búsqueda) y luego por clicks
            $resultados = $resultados->sortByDesc(function($item) use ($queryCompleta, $palabras) {
                $relevancia = 0;
                $queryLower = strtolower($queryCompleta);
                
                // Obtener el nombre del producto o variante
                $nombreBusqueda = strtolower($item['nombre'] ?? '');
                
                // 1. Coincidencia exacta con la búsqueda completa (máxima relevancia)
                if ($nombreBusqueda === $queryLower) {
                    $relevancia += 1000;
                }
                // 2. La búsqueda completa está contenida en el nombre
                elseif (str_contains($nombreBusqueda, $queryLower)) {
                    $relevancia += 500;
                }
                // 3. El nombre está contenido en la búsqueda
                elseif (str_contains($queryLower, $nombreBusqueda)) {
                    $relevancia += 400;
                }
                
                // 4. Contar cuántas palabras de la búsqueda están en el nombre
                $palabrasCoincidentes = 0;
                foreach ($palabras as $palabra) {
                    if (str_contains($nombreBusqueda, strtolower($palabra))) {
                        $palabrasCoincidentes++;
                    }
                }
                $relevancia += $palabrasCoincidentes * 100;
                
                // 5. Añadir clicks como factor secundario (dividido por 1000 para que no sobrescriba la relevancia)
                $relevancia += ($item['clicks'] ?? 0) / 1000;
                
                return $relevancia;
            })->values();

            // Limitar resultados finales
            return $resultados->take($limite);
        } catch (\Exception $e) {
            // Si hay un error, devolver colección vacía
            \Log::error('Error en buscarProductos: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            return collect();
        }
    }

    /**
     * Genera variantes de productos basadas en especificaciones internas
     */
    private function generarVariantesProductos($productos, array $palabras, string $queryCompleta)
    {
        $resultados = collect();
        $servicioOfertas = new SacarPrimeraOfertaDeUnProductoAplicadoDescuentosYChollos();

        foreach ($productos as $producto) {
            $especificacionesBusqueda = $producto->especificaciones_busqueda;
            $especificacionesTexto = $producto->especificaciones_busqueda_texto;

            // Si el producto no tiene especificaciones de búsqueda, añadirlo como resultado normal
            if (!$especificacionesBusqueda || !is_array($especificacionesBusqueda) || empty($especificacionesBusqueda)) {
                $resultados->push($this->formatearResultadoProducto($producto, null, null));
                continue;
            }

            // Verificar si la búsqueda coincide con alguna especificación
            $coincidencias = [];
            $queryLower = strtolower($queryCompleta);
            $palabrasLower = array_map('strtolower', $palabras);
            
            // Obtener el nombre completo del producto en minúsculas para comparar
            $nombreProductoLower = strtolower($producto->nombre . ' ' . $producto->marca . ' ' . $producto->modelo);
            
            // Identificar qué palabras están en el nombre y cuáles no
            $palabrasEnNombre = [];
            $palabrasNoEnNombre = [];
            
            foreach ($palabrasLower as $palabra) {
                if (str_contains($nombreProductoLower, $palabra)) {
                    $palabrasEnNombre[] = $palabra;
                } else {
                    $palabrasNoEnNombre[] = $palabra;
                }
            }
            
            // Buscar coincidencias con especificaciones
            // Mostrar todas las variantes que contengan alguna palabra de la búsqueda
            foreach ($especificacionesBusqueda as $textoEspecificacion => $datos) {
                $textoLower = strtolower($textoEspecificacion);
                $textoOriginal = $datos['texto'] ?? $textoEspecificacion;
                $textoOriginalLower = strtolower($textoOriginal);
                
                // Verificar si la variante coincide con la búsqueda de alguna forma
                $varianteCoincide = false;
                
                // 1. Verificar si la búsqueda completa está contenida en la variante
                if (str_contains($textoOriginalLower, $queryLower) || str_contains($textoLower, $queryLower)) {
                    $varianteCoincide = true;
                }
                // 2. Verificar si la variante está contenida en la búsqueda
                elseif (str_contains($queryLower, $textoOriginalLower) || str_contains($queryLower, $textoLower)) {
                    $varianteCoincide = true;
                }
                // 3. Verificar si alguna palabra de la búsqueda está en la variante
                // También verificar si la variante contiene todas las palabras (aunque no en orden)
                if (!$varianteCoincide) {
                    $palabrasEnVariante = 0;
                    foreach ($palabrasLower as $palabra) {
                        if (str_contains($textoOriginalLower, $palabra) || str_contains($textoLower, $palabra)) {
                            $palabrasEnVariante++;
                        }
                    }
                    // Si al menos una palabra está en la variante, considerarla coincidencia
                    if ($palabrasEnVariante > 0) {
                        $varianteCoincide = true;
                    }
                }
                
                // Si la variante coincide, añadirla a las coincidencias usando el ID como clave única
                if ($varianteCoincide) {
                    // Usar el ID de la variante como clave (siempre único)
                    $varianteId = $datos['id'] ?? null;
                    if ($varianteId) {
                        $claveUnica = 'v_' . $varianteId;
                    } else {
                        // Si no hay ID, usar el texto de especificación con hash del texto original
                        $claveUnica = $textoEspecificacion . '_' . md5($textoOriginal);
                    }
                    
                    // Solo añadir si no existe ya (evitar duplicados)
                    if (!isset($coincidencias[$claveUnica])) {
                        $coincidencias[$claveUnica] = [
                            'texto_especificacion' => $textoEspecificacion,
                            'datos' => $datos
                        ];
                    }
                }
            }

            // Si no hay coincidencias, añadir el producto normal
            if (empty($coincidencias)) {
                $resultados->push($this->formatearResultadoProducto($producto, null, null));
                continue;
            }

            // Generar una variante para cada coincidencia (mostrar todas las variantes que coincidan)
            foreach ($coincidencias as $claveUnica => $item) {
                $textoEspecificacion = $item['texto_especificacion'];
                $datos = $item['datos'];
                $precio = $datos['precio_unidad'] ?? $producto->precio;
                
                // Usar el texto original si está disponible, si no usar el slug como fallback
                $textoParaMostrar = $datos['texto'] ?? $textoEspecificacion;
                
                // Obtener la imagen de la variante si está disponible
                $imagenVariante = $datos['imagen'] ?? null;
                
                $resultados->push($this->formatearResultadoProducto(
                    $producto, 
                    $textoParaMostrar, 
                    $precio,
                    $imagenVariante
                ));
            }
            
            // Después de mostrar las variantes, también mostrar el producto sin variante
            $resultados->push($this->formatearResultadoProducto($producto, null, null));
        }

        return $resultados;
    }

    /**
     * Genera variantes de productos para la búsqueda principal (buscar.blade.php)
     * Similar a generarVariantesProductos pero adaptado para mostrar en la vista de búsqueda
     */
    private function generarVariantesProductosParaBuscar($productos, array $palabras, string $queryCompleta)
    {
        $resultados = collect();
        $servicioOfertas = new SacarPrimeraOfertaDeUnProductoAplicadoDescuentosYChollos();

        foreach ($productos as $producto) {
            $especificacionesBusqueda = $producto->especificaciones_busqueda;
            $especificacionesTexto = $producto->especificaciones_busqueda_texto;

            // Si el producto no tiene especificaciones de búsqueda, añadirlo como resultado normal
            if (!$especificacionesBusqueda || !is_array($especificacionesBusqueda) || empty($especificacionesBusqueda)) {
                $resultados->push([
                    'producto' => $producto,
                    'variante' => null,
                    'precio_variante' => null,
                    'es_variante' => false
                ]);
                continue;
            }

            // Verificar si la búsqueda coincide con alguna especificación
            $coincidencias = [];
            $queryLower = strtolower($queryCompleta);
            $palabrasLower = array_map('strtolower', $palabras);
            
            // Obtener el nombre completo del producto en minúsculas para comparar
            $nombreProductoLower = strtolower($producto->nombre . ' ' . $producto->marca . ' ' . $producto->modelo);
            
            // Identificar qué palabras están en el nombre y cuáles no
            $palabrasEnNombre = [];
            $palabrasNoEnNombre = [];
            
            foreach ($palabrasLower as $palabra) {
                if (str_contains($nombreProductoLower, $palabra)) {
                    $palabrasEnNombre[] = $palabra;
                } else {
                    $palabrasNoEnNombre[] = $palabra;
                }
            }
            
            // Buscar coincidencias con especificaciones
            // Mostrar todas las variantes que contengan alguna palabra de la búsqueda
            foreach ($especificacionesBusqueda as $textoEspecificacion => $datos) {
                $textoLower = strtolower($textoEspecificacion);
                $textoOriginal = $datos['texto'] ?? $textoEspecificacion;
                $textoOriginalLower = strtolower($textoOriginal);
                
                // Verificar si la variante coincide con la búsqueda de alguna forma
                $varianteCoincide = false;
                
                // 1. Verificar si la búsqueda completa está contenida en la variante
                if (str_contains($textoOriginalLower, $queryLower) || str_contains($textoLower, $queryLower)) {
                    $varianteCoincide = true;
                }
                // 2. Verificar si la variante está contenida en la búsqueda
                elseif (str_contains($queryLower, $textoOriginalLower) || str_contains($queryLower, $textoLower)) {
                    $varianteCoincide = true;
                }
                // 3. Verificar si alguna palabra de la búsqueda está en la variante
                // También verificar si la variante contiene todas las palabras (aunque no en orden)
                if (!$varianteCoincide) {
                    $palabrasEnVariante = 0;
                    foreach ($palabrasLower as $palabra) {
                        if (str_contains($textoOriginalLower, $palabra) || str_contains($textoLower, $palabra)) {
                            $palabrasEnVariante++;
                        }
                    }
                    // Si al menos una palabra está en la variante, considerarla coincidencia
                    if ($palabrasEnVariante > 0) {
                        $varianteCoincide = true;
                    }
                }
                
                // Si la variante coincide, añadirla a las coincidencias usando el ID como clave única
                if ($varianteCoincide) {
                    // Usar el ID de la variante como clave (siempre único)
                    $varianteId = $datos['id'] ?? null;
                    if ($varianteId) {
                        $claveUnica = 'v_' . $varianteId;
                    } else {
                        // Si no hay ID, usar el texto de especificación con hash del texto original
                        $claveUnica = $textoEspecificacion . '_' . md5($textoOriginal);
                    }
                    
                    // Solo añadir si no existe ya (evitar duplicados)
                    if (!isset($coincidencias[$claveUnica])) {
                        $coincidencias[$claveUnica] = [
                            'texto_especificacion' => $textoEspecificacion,
                            'datos' => $datos
                        ];
                    }
                }
            }

            // Si no hay coincidencias, añadir el producto normal
            if (empty($coincidencias)) {
                $resultados->push([
                    'producto' => $producto,
                    'variante' => null,
                    'precio_variante' => null,
                    'es_variante' => false
                ]);
                continue;
            }

            // Generar una variante para cada coincidencia (mostrar todas las variantes que coincidan)
            // Para cada variante, buscar la oferta más barata usando el servicio
            // Necesitamos obtener el lineaId y sublineaId para filtrar correctamente
            $especificacionesElegidas = $producto->categoria_especificaciones_internas_elegidas;
            $categoriaEspecificaciones = $producto->categoriaEspecificaciones;
            
            if ($categoriaEspecificaciones && $especificacionesElegidas) {
                $filtros = $categoriaEspecificaciones->especificaciones_internas['filtros'] ?? [];
                
                // Obtener todas las ofertas del producto una sola vez
                $todasLasOfertas = $servicioOfertas->obtenerTodas($producto);
                
                foreach ($coincidencias as $claveUnica => $item) {
                    $textoEspecificacion = $item['texto_especificacion'];
                    $datos = $item['datos'];
                    // Usar el texto original si está disponible
                    $textoParaMostrar = $datos['texto'] ?? $textoEspecificacion;
                    $sublineaId = $datos['id'] ?? null;
                    
                    // Buscar el lineaId en las especificaciones elegidas
                    $lineaId = null;
                    foreach ($especificacionesElegidas as $lineaIdKey => $sublineasProducto) {
                        if (strpos($lineaIdKey, '_') === 0) {
                            continue; // Saltar claves especiales
                        }
                        
                        $sublineasArray = is_array($sublineasProducto) ? $sublineasProducto : [$sublineasProducto];
                        foreach ($sublineasArray as $sublineaProducto) {
                            $sublineaIdProducto = is_array($sublineaProducto) ? ($sublineaProducto['id'] ?? null) : strval($sublineaProducto);
                            if (strval($sublineaIdProducto) === strval($sublineaId)) {
                                $lineaId = $lineaIdKey;
                                break 2;
                            }
                        }
                    }
                    
                    // Filtrar ofertas que coincidan con esta variante específica
                    if ($lineaId && $sublineaId) {
                        $ofertasVariante = $todasLasOfertas->filter(function($oferta) use ($lineaId, $sublineaId) {
                            $especificacionesOferta = $oferta->especificaciones_internas;
                            
                            if (!$especificacionesOferta || !is_array($especificacionesOferta)) {
                                return false;
                            }

                            $ofertaLinea = $especificacionesOferta[$lineaId] ?? null;
                            if (!$ofertaLinea) {
                                return false;
                            }

                            $ofertaSublineas = is_array($ofertaLinea) ? $ofertaLinea : [$ofertaLinea];
                            
                            foreach ($ofertaSublineas as $item) {
                                $itemId = (is_array($item) && isset($item['id'])) ? strval($item['id']) : strval($item);
                                if (strval($itemId) === strval($sublineaId)) {
                                    return true;
                                }
                            }
                            
                            return false;
                        });
                        
                        // Obtener el precio más barato de las ofertas filtradas
                        if ($ofertasVariante->isNotEmpty()) {
                            $precioMasBarato = $ofertasVariante->min('precio_unidad');
                        } else {
                            // Si no hay ofertas específicas, usar el precio del JSON o del producto
                            $precioMasBarato = $datos['precio_unidad'] ?? $producto->precio;
                        }
                    } else {
                        // Si no encontramos lineaId/sublineaId, usar el precio del JSON
                        $precioMasBarato = $datos['precio_unidad'] ?? $producto->precio;
                    }
                    
                    // Obtener la imagen de la variante si está disponible
                    $imagenVariante = $datos['imagen'] ?? null;
                    
                    $resultados->push([
                        'producto' => $producto,
                        'variante' => $textoParaMostrar,
                        'precio_variante' => $precioMasBarato,
                        'imagen_variante' => $imagenVariante,
                        'es_variante' => true
                    ]);
                }
            } else {
                // Si no hay especificaciones elegidas, usar el precio del JSON
                foreach ($coincidencias as $claveUnica => $item) {
                    $textoEspecificacion = $item['texto_especificacion'];
                    $datos = $item['datos'];
                    $textoParaMostrar = $datos['texto'] ?? $textoEspecificacion;
                    $precio = $datos['precio_unidad'] ?? $producto->precio;
                    
                    // Obtener la imagen de la variante si está disponible
                    $imagenVariante = $datos['imagen'] ?? null;
                    
                    $resultados->push([
                        'producto' => $producto,
                        'variante' => $textoParaMostrar,
                        'precio_variante' => $precio,
                        'imagen_variante' => $imagenVariante,
                        'es_variante' => true
                    ]);
                }
            }
            
            // Después de mostrar las variantes, también mostrar el producto sin variante
            $resultados->push([
                'producto' => $producto,
                'variante' => null,
                'precio_variante' => null,
                'es_variante' => false
            ]);
        }

        return $resultados;
    }

    /**
     * Formatea un resultado de producto para el buscador
     */
    private function formatearResultadoProducto($producto, $variante = null, $precioVariante = null, $imagenVariante = null)
    {
        // Si hay variante, usar su precio; si no, usar el precio del producto
        $precio = $precioVariante !== null ? $precioVariante : $producto->precio;
        
        // Construir URL: si hay variante, añadir segmento
        $urlBase = '/' . $producto->ruta_completa;
        if ($variante) {
            // Convertir el texto de la variante a slug para la URL
            $varianteSlug = Str::slug($variante);
            $urlBase .= '/' . $varianteSlug;
        }

        // Si hay variante, construir nombre como "marca + modelo + texto sublínea"
        $nombreMostrar = $producto->nombre;
        if ($variante) {
            $partesNombre = [];
            if (!empty($producto->marca)) {
                $partesNombre[] = $producto->marca;
            }
            if (!empty($producto->modelo)) {
                $partesNombre[] = $producto->modelo;
            }
            if (!empty($variante)) {
                $partesNombre[] = $variante;
            }
            $nombreMostrar = !empty($partesNombre) ? implode(' ', $partesNombre) : $producto->nombre;
        }

        // Usar la imagen de la variante si está disponible, si no usar la del producto
        $imagen = $imagenVariante;
        if (!$imagen) {
            $imagen = is_array($producto->imagen_pequena) 
                ? ($producto->imagen_pequena[0] ?? 'placeholder.jpg')
                : $producto->imagen_pequena;
        }

        return [
            'id' => $producto->id,
            'nombre' => $nombreMostrar,
            'marca' => $producto->marca,
            'modelo' => $producto->modelo,
            'talla' => $producto->talla,
            'slug' => $producto->slug,
            'variante' => $variante,
            'imagen_pequena' => $imagen,
            'precio' => $producto->unidadDeMedida === 'unidadMilesima' 
                ? number_format($precio, 3, ',', '.')
                : number_format($precio, 2, ',', '.'),
            'unidadDeMedida' => $producto->unidadDeMedida,
            'url' => $urlBase,
            'tipo' => 'producto'
        ];
    }

    /**
     * Obtiene IDs de categorías que coinciden con las palabras (incluyendo jerarquía completa)
     * Para una sola palabra: incluye categorías cuyo nombre coincida
     * Para múltiples palabras: incluye categorías cuya jerarquía contiene todas las palabras
     */
    private function obtenerCategoriaIdsPorPalabras(array $palabras, string $queryCompleta)
    {
        $categoriaIds = collect();

        // Si hay una sola palabra, buscar directamente por nombre
        if (count($palabras) === 1) {
            $categorias = Categoria::whereRaw('LOWER(nombre) LIKE ?', ['%' . $queryCompleta . '%'])->get();
            foreach ($categorias as $categoria) {
                // Incluir la categoría y toda su jerarquía
                $jerarquia = CategoriaHelper::obtenerJerarquiaCompleta($categoria->id);
                foreach ($jerarquia as $cat) {
                    $categoriaIds->push($cat->id);
                }
            }
        } else {
            // Para múltiples palabras, buscar categorías cuya jerarquía contiene todas las palabras
            foreach ($palabras as $palabra) {
                $categorias = Categoria::whereRaw('LOWER(nombre) LIKE ?', ['%' . $palabra . '%'])->get();

                foreach ($categorias as $categoria) {
                    // Obtener toda la jerarquía de esta categoría
                    $jerarquia = CategoriaHelper::obtenerJerarquiaCompleta($categoria->id);
                    $nombresJerarquia = collect($jerarquia)->pluck('nombre')->map(fn($n) => strtolower($n))->toArray();
                    $textoJerarquia = implode(' ', $nombresJerarquia);

                    // Verificar si TODAS las palabras están en la jerarquía completa
                    $todasLasPalabrasCoinciden = collect($palabras)->every(
                        fn($pal) => str_contains($textoJerarquia, $pal)
                    );

                    // También verificar coincidencia exacta de la consulta completa
                    $coincidenciaExacta = str_contains($textoJerarquia, $queryCompleta);

                    if ($todasLasPalabrasCoinciden || $coincidenciaExacta) {
                        // Incluir todas las categorías de la jerarquía completa
                        foreach ($jerarquia as $cat) {
                            $categoriaIds->push($cat->id);
                        }
                    }
                }
            }
        }

        return $categoriaIds->unique()->toArray();
    }
}
