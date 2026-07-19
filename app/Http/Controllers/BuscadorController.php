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

        $queryNorm = $this->normalizarTextoBusqueda($query);
        $palabras = $this->tokenizarConsultaBusqueda($queryNorm);
        
        if (empty($palabras)) {
            return response()->json([]);
        }

        try {
            $sinVariantes = $request->boolean('sin_variantes');

            // Buscar categorías (mejorado) — en admin sin variantes no hacen falta
            $categorias = $sinVariantes
                ? collect()
                : $this->buscarCategorias($palabras, $queryNorm, 3);

            // Buscar productos (mejorado); sin_variantes=1 = solo producto base (p. ej. crear-masivo)
            $productos = $this->buscarProductos($palabras, $queryNorm, 7, $sinVariantes);

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

        $queryNorm = $this->normalizarTextoBusqueda($query);
        $palabras = $this->tokenizarConsultaBusqueda($queryNorm);

        $categoriaCoincidente = $this->resolverCategoriaPorConsulta($queryNorm, $palabras);

        if ($categoriaCoincidente && !$request->boolean('debug')) {
            $url = route('categoria.show', $categoriaCoincidente->slug);
            if ($request->has('cam')) {
                $url .= '?cam=' . urlencode($request->get('cam'));
            }
            return redirect($url);
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
        
        // Usar el mismo método común que buscarProductos() para garantizar resultados idénticos
        $productosConVariantes = $this->obtenerProductosOrdenadosPorRelevancia($palabras, $queryNorm, $maxProductos * 2);
        
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
                    'img_producto' => $item['img_producto'] ?? null, // Imagen específica de precios hot
                    'producto_nombre' => $item['producto_nombre'] ?? null, // Nombre específico de precios hot
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
     * Normaliza texto para comparar búsquedas (sin acentos, apóstrofos intermedios, minúsculas).
     */
    private function normalizarTextoBusqueda(string $texto): string
    {
        $texto = mb_strtolower(trim($texto), 'UTF-8');
        $texto = Str::ascii($texto);
        $texto = str_replace($this->caracteresApostrofoBusqueda(), '', $texto);
        $texto = preg_replace('/\s+/', ' ', $texto);

        return trim($texto);
    }

    /**
     * Caracteres de apóstrofo/accento suelto que interrumpen la búsqueda (L´Or, L'Oréal…).
     *
     * @return array<int, string>
     */
    private function caracteresApostrofoBusqueda(): array
    {
        return [
            "\u{00B4}",
            "'",
            "\u{2019}",
            "\u{2018}",
            '`',
            "\u{02BC}",
            "\u{2032}",
            "\u{201B}",
            "\u{2035}",
            "\u{FF07}",
        ];
    }

    /**
     * Expresión SQL que normaliza un campo de producto igual que normalizarTextoBusqueda().
     */
    private function expresionCampoTextoBusqueda(string $columna): string
    {
        $columnasPermitidas = ['nombre', 'marca', 'modelo', 'talla', 'especificaciones_busqueda_texto'];
        if (!in_array($columna, $columnasPermitidas, true)) {
            throw new \InvalidArgumentException("Columna no permitida para búsqueda: {$columna}");
        }

        $expr = "LOWER({$columna})";

        foreach ($this->caracteresApostrofoBusqueda() as $char) {
            $expr = "REPLACE({$expr}, " . DB::getPdo()->quote($char) . ", '')";
        }

        $reemplazos = [
            'á' => 'a', 'à' => 'a', 'ä' => 'a', 'â' => 'a',
            'é' => 'e', 'è' => 'e', 'ë' => 'e', 'ê' => 'e',
            'í' => 'i', 'ì' => 'i', 'ï' => 'i', 'î' => 'i',
            'ó' => 'o', 'ò' => 'o', 'ö' => 'o', 'ô' => 'o',
            'ú' => 'u', 'ù' => 'u', 'ü' => 'u', 'û' => 'u',
            'ñ' => 'n', 'ç' => 'c',
        ];
        foreach ($reemplazos as $desde => $hacia) {
            $expr = "REPLACE({$expr}, " . DB::getPdo()->quote($desde) . ", " . DB::getPdo()->quote($hacia) . ")";
        }

        return $expr;
    }

    /**
     * Añade condición: el término aparece en nombre, marca, modelo o talla (comparación normalizada).
     */
    private function agregarCoincidenciaTerminoEnCamposProducto($query, string $termino, string $metodoGrupo = 'orWhere'): void
    {
        $like = '%' . $this->normalizarTextoBusqueda($termino) . '%';

        $query->{$metodoGrupo}(function ($q) use ($like) {
            foreach (['nombre', 'marca', 'modelo', 'talla'] as $campo) {
                $q->orWhereRaw($this->expresionCampoTextoBusqueda($campo) . ' LIKE ?', [$like]);
            }
        });
    }

    /**
     * Si la consulta coincide con una categoría visible, devuelve la más específica para redirigir.
     */
    private function resolverCategoriaPorConsulta(string $queryCompleta, array $palabras): ?Categoria
    {
        $queryNorm = $this->normalizarTextoBusqueda($queryCompleta);
        $palabrasNorm = array_values(array_filter(
            array_map(fn($p) => $this->normalizarTextoBusqueda($p), $palabras)
        ));

        if ($queryNorm === '') {
            return null;
        }

        $candidatas = collect();
        $idsIncluidos = [];

        foreach (Categoria::visibles()->get() as $categoria) {
            $nombreNorm = $this->normalizarTextoBusqueda($categoria->nombre);

            if ($nombreNorm === $queryNorm) {
                $candidatas->push([
                    'categoria' => $categoria,
                    'prioridad' => 100,
                    'nivel' => $categoria->obtenerNivel(),
                    'clicks' => $categoria->clicks ?? 0,
                ]);
                $idsIncluidos[] = $categoria->id;
            }
        }

        // Definir stopwords en español a excluir de la búsqueda de categorías
        $stopWords = ['de', 'la', 'el', 'en', 'y', 'para', 'con', 'del', 'los', 'las', 'un', 'una', 'unos', 'unas', 'al', 'o', 'por', 'sobre'];
        $palabrasFiltradas = array_values(array_filter($palabrasNorm, fn($p) => !in_array($p, $stopWords)));

        if (count($palabrasFiltradas) >= 1) {
            foreach ($palabrasFiltradas as $palabra) {
                $categorias = Categoria::visibles()
                    ->whereRaw('LOWER(nombre) LIKE ?', ['%' . $palabra . '%'])
                    ->get();

                foreach ($categorias as $categoria) {
                    if (in_array($categoria->id, $idsIncluidos)) {
                        continue;
                    }

                    $jerarquia = CategoriaHelper::obtenerJerarquiaCompleta($categoria->id);
                    $textoJerarquia = $this->normalizarTextoBusqueda(
                        collect($jerarquia)->pluck('nombre')->implode(' ')
                    );

                    $todasLasPalabrasCoinciden = collect($palabrasFiltradas)->every(
                        fn($pal) => str_contains($textoJerarquia, $pal)
                    );

                    if ($todasLasPalabrasCoinciden || str_contains($textoJerarquia, $queryNorm)) {
                        $candidatas->push([
                            'categoria' => $categoria,
                            'prioridad' => 80,
                            'nivel' => $categoria->obtenerNivel(),
                            'clicks' => $categoria->clicks ?? 0,
                        ]);
                        $idsIncluidos[] = $categoria->id;
                    }
                }
            }
        }

        if ($candidatas->isEmpty()) {
            return null;
        }

        // Si tras filtrar las stopwords la consulta queda con una sola palabra significativa
        // y no hay coincidencia exacta de prioridad 100, solo redirigimos si hay una
        // única categoría candidata de prioridad 80.
        // Esto evita redirecciones incorrectas para palabras genéricas como "cafe" o "tela".
        if (count($palabrasFiltradas) === 1) {
            $tieneCoincidenciaExacta = $candidatas->contains('prioridad', 100);
            if (!$tieneCoincidenciaExacta && $candidatas->count() > 1) {
                return null;
            }
        }

        return $candidatas
            ->sortBy([
                ['prioridad', 'desc'],
                ['nivel', 'desc'],
                ['clicks', 'desc'],
            ])
            ->first()['categoria'];
    }

    /**
     * Divide la consulta en tokens válidos para búsqueda.
     * Mantiene dígitos de un solo carácter (p. ej. tallas "3", "4") que antes se descartaban.
     */
    private function tokenizarConsultaBusqueda(string $queryLower): array
    {
        return array_values(array_filter(
            explode(' ', $queryLower),
            fn($palabra) => strlen($palabra) >= 2 || (strlen($palabra) === 1 && ctype_digit($palabra))
        ));
    }

    /**
     * Comprueba si un token aparece en un texto, usando límite de palabra para dígitos cortos.
     */
    private function coincidePalabraEnTexto(string $texto, string $palabra): bool
    {
        $texto = $this->normalizarTextoBusqueda($texto);
        $palabra = $this->normalizarTextoBusqueda($palabra);

        if (strlen($palabra) === 1 && ctype_digit($palabra)) {
            return (bool) preg_match('/\b' . preg_quote($palabra, '/') . '(?:\+|\b)/u', $texto);
        }

        return str_contains($texto, $palabra);
    }

    /**
     * Comprueba si todos los tokens de búsqueda aparecen en el texto dado.
     */
    private function coincidenTodasLasPalabrasEnTexto(string $texto, array $palabras): bool
    {
        foreach ($palabras as $palabra) {
            if (!$this->coincidePalabraEnTexto($texto, strtolower($palabra))) {
                return false;
            }
        }

        return true;
    }

    /**
     * Texto normalizado de la jerarquía de categorías de un producto (para búsquedas tipo "cerveza mahou").
     */
    private function obtenerTextoJerarquiaProducto($producto): string
    {
        if (empty($producto->categoria_id)) {
            return '';
        }

        $jerarquia = CategoriaHelper::obtenerJerarquiaCompleta($producto->categoria_id);

        return $this->normalizarTextoBusqueda(
            collect($jerarquia)->pluck('nombre')->implode(' ')
        );
    }

    /**
     * IDs de categorías (y descendientes) cuyo nombre contiene la palabra.
     *
     * @return list<int>
     */
    private function obtenerCategoriaIdsPorPalabraIndividual(string $palabra): array
    {
        static $cache = [];

        $palabraNorm = $this->normalizarTextoBusqueda($palabra);
        if ($palabraNorm === '') {
            return [];
        }

        if (array_key_exists($palabraNorm, $cache)) {
            return $cache[$palabraNorm];
        }

        $ids = collect();
        $categorias = Categoria::visibles()
            ->whereRaw('LOWER(nombre) LIKE ?', ['%' . $palabraNorm . '%'])
            ->get(['id']);

        foreach ($categorias as $categoria) {
            foreach (Categoria::idsSelfAndDescendants($categoria->id) as $id) {
                $ids->push($id);
            }
        }

        return $cache[$palabraNorm] = $ids->unique()->values()->all();
    }

    /**
     * Comprueba si un producto coincide con la búsqueda en campos principales, categoría o especificaciones.
     * Alineado con los criterios SQL de obtenerProductosOrdenadosPorRelevancia.
     */
    private function productoCoincideBusqueda($producto, array $palabras): bool
    {
        $campos = $this->normalizarTextoBusqueda(trim(implode(' ', array_filter([
            $producto->nombre,
            $producto->marca,
            $producto->modelo,
            $producto->talla,
        ]))));

        $textoCompleto = trim($campos . ' ' . $this->obtenerTextoJerarquiaProducto($producto));

        if ($this->coincidenTodasLasPalabrasEnTexto($textoCompleto, $palabras)) {
            return true;
        }

        if (!empty($producto->especificaciones_busqueda_texto)) {
            return $this->coincidenTodasLasPalabrasEnTexto(
                $this->normalizarTextoBusqueda($producto->especificaciones_busqueda_texto),
                $palabras
            );
        }

        return false;
    }

    /**
     * Prioriza en SQL productos que coinciden en nombre/marca/modelo/talla antes que solo en specs.
     */
    private function aplicarOrdenRelevanciaBusquedaSql($query, string $queryCompleta, array $palabras)
    {
        $likeCompleta = '%' . $this->normalizarTextoBusqueda($queryCompleta) . '%';
        $bindings = [$likeCompleta, $likeCompleta, $likeCompleta, $likeCompleta];

        $condicionesPalabras = [];
        foreach ($palabras as $palabra) {
            $like = '%' . $this->normalizarTextoBusqueda($palabra) . '%';
            $condicionesPalabras[] = '('
                . $this->expresionCampoTextoBusqueda('nombre') . ' LIKE ? OR '
                . $this->expresionCampoTextoBusqueda('marca') . ' LIKE ? OR '
                . $this->expresionCampoTextoBusqueda('modelo') . ' LIKE ? OR '
                . $this->expresionCampoTextoBusqueda('talla') . ' LIKE ?)';
            array_push($bindings, $like, $like, $like, $like);
        }

        $sql = 'CASE WHEN ('
            . $this->expresionCampoTextoBusqueda('nombre') . ' LIKE ? OR '
            . $this->expresionCampoTextoBusqueda('marca') . ' LIKE ? OR '
            . $this->expresionCampoTextoBusqueda('modelo') . ' LIKE ? OR '
            . $this->expresionCampoTextoBusqueda('talla') . ' LIKE ?) THEN 0';
        if (!empty($condicionesPalabras)) {
            $sql .= ' WHEN (' . implode(' AND ', $condicionesPalabras) . ') THEN 1';
        }
        $sql .= ' ELSE 2 END';

        return $query->orderByRaw($sql, $bindings)->orderBy('clicks', 'desc');
    }

    /**
     * Busca categorías considerando la jerarquía y palabras múltiples
     * Prioriza categorías más específicas (hijas) sobre generales (padres)
     */
    private function buscarCategorias(array $palabras, string $queryCompleta, int $limite)
    {
        $categoriasEncontradas = collect();
        $idsYaIncluidos = [];

        // 1. Búsqueda directa: categorías visibles cuyo nombre coincida con la búsqueda completa
        $categoriasDirectas = Categoria::visibles()
            ->whereRaw('LOWER(nombre) LIKE ?', ['%' . $queryCompleta . '%'])
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
                $categorias = Categoria::visibles()
                    ->whereRaw('LOWER(nombre) LIKE ?', ['%' . $palabra . '%'])
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
     * Método común que obtiene productos ordenados por relevancia
     * Usado tanto por buscar() como por productos() para garantizar resultados idénticos
     */
    private function obtenerProductosOrdenadosPorRelevancia(array $palabras, string $queryCompleta, int $limiteProductos = null, bool $sinVariantes = false)
    {
        $query = Producto::where('obsoleto', 'no')
            ->where('mostrar', 'si')
            ->where(function($q) use ($palabras, $queryCompleta) {
                // Cada palabra debe coincidir en nombre/marca/modelo/talla, categoría o especificaciones
                foreach ($palabras as $palabra) {
                    $q->where(function($wordQ) use ($palabra) {
                        $this->agregarCoincidenciaTerminoEnCamposProducto($wordQ, $palabra, 'where');

                        $categoriaIds = $this->obtenerCategoriaIdsPorPalabraIndividual($palabra);
                        if (!empty($categoriaIds)) {
                            $wordQ->orWhereIn('categoria_id', $categoriaIds);
                        }

                        $wordQ->orWhere(function($specQ) use ($palabra) {
                            $specQ->whereNotNull('especificaciones_busqueda_texto');
                            $like = '%' . $this->normalizarTextoBusqueda($palabra) . '%';
                            $specQ->whereRaw(
                                $this->expresionCampoTextoBusqueda('especificaciones_busqueda_texto') . ' LIKE ?',
                                [$like]
                            );
                        });
                    });
                }

                // También aceptar coincidencia de la frase completa en campos del producto
                if (count($palabras) > 1) {
                    $q->orWhere(function($fullQ) use ($queryCompleta) {
                        $this->agregarCoincidenciaTerminoEnCamposProducto($fullQ, $queryCompleta, 'where');
                    });
                }
            });

        try {
            $limiteQuery = $limiteProductos ?? 200;
            $productos = $this->aplicarOrdenRelevanciaBusquedaSql($query, $queryCompleta, $palabras)
                ->limit($limiteQuery)
                ->with('categoria.parent.parent')
                ->get();

            if ($sinVariantes) {
                // Misma búsqueda, sin expandir sublíneas/especificaciones como resultados distintos
                $productosConVariantes = $productos->map(function ($producto) {
                    return [
                        'producto' => $producto,
                        'variante' => null,
                        'precio_variante' => null,
                        'es_variante' => false,
                    ];
                });
            } else {
                $productosConVariantes = $this->generarVariantesProductosParaBuscar($productos, $palabras, $queryCompleta);
            }
        } catch (\Exception $e) {
            \Log::error('Error en obtenerProductosOrdenadosPorRelevancia (consulta): ' . $e->getMessage());
            return collect();
        }

        try {
            $productosConVariantes = $productosConVariantes->sortByDesc(function($item) use ($queryCompleta, $palabras) {
                $relevancia = 0;
                $queryNorm = $this->normalizarTextoBusqueda($queryCompleta);
                $producto = $item['producto'];
                $variante = $item['variante'] ?? null;
                
                // Construir nombre completo para comparar (igual que en buscar())
                $nombreCompleto = $this->normalizarTextoBusqueda($producto->nombre);
                $marcaCompleta = $this->normalizarTextoBusqueda($producto->marca ?? '');
                if ($variante) {
                    $partesNombre = [];
                    if (!empty($producto->marca)) {
                        $partesNombre[] = $this->normalizarTextoBusqueda($producto->marca);
                    }
                    if (!empty($producto->modelo)) {
                        $partesNombre[] = $this->normalizarTextoBusqueda($producto->modelo);
                    }
                    if (!empty($variante)) {
                        $partesNombre[] = $this->normalizarTextoBusqueda($variante);
                    }
                    $nombreCompleto = !empty($partesNombre) ? implode(' ', $partesNombre) : $this->normalizarTextoBusqueda($producto->nombre);
                } else {
                    $nombreCompleto = $this->normalizarTextoBusqueda($producto->nombre . ' ' . $producto->marca . ' ' . $producto->modelo);
                }
                
                // 1. Coincidencia exacta con la búsqueda completa en nombre (máxima relevancia)
                if ($nombreCompleto === $queryNorm) {
                    $relevancia += 10000;
                }
                // 2. Coincidencia exacta con la búsqueda completa en marca (alta relevancia)
                elseif ($marcaCompleta === $queryNorm) {
                    $relevancia += 9000;
                }
                // 3. La búsqueda completa está contenida en el nombre
                elseif (str_contains($nombreCompleto, $queryNorm)) {
                    $relevancia += 5000;
                }
                // 4. La búsqueda completa está contenida en la marca
                elseif (str_contains($marcaCompleta, $queryNorm)) {
                    $relevancia += 4500;
                }
                // 5. El nombre está contenido en la búsqueda
                elseif (str_contains($queryNorm, $nombreCompleto)) {
                    $relevancia += 4000;
                }
                
                // 6. Contar cuántas palabras de la búsqueda están en el nombre, marca, talla o categoría
                $palabrasCoincidentes = 0;
                $textoProducto = $nombreCompleto . ' ' . $marcaCompleta . ' ' . $this->normalizarTextoBusqueda($producto->talla ?? '')
                    . ' ' . $this->obtenerTextoJerarquiaProducto($producto);
                foreach ($palabras as $palabra) {
                    if ($this->coincidePalabraEnTexto($textoProducto, $palabra)) {
                        $palabrasCoincidentes++;
                    }
                }
                $relevancia += $palabrasCoincidentes * 1000;

                // 6b. Bonus fuerte si coinciden TODAS las palabras de la búsqueda
                if (count($palabras) > 1 && $palabrasCoincidentes === count($palabras)) {
                    $relevancia += 3000;
                }
                
                // 7. Verificar si el nombre del producto (sin variante) coincide
                $nombreProductoNorm = $this->normalizarTextoBusqueda($producto->nombre . ' ' . $producto->marca . ' ' . $producto->modelo);
                if (str_contains($nombreProductoNorm, $queryNorm)) {
                    $relevancia += 500;
                }
                
                // 8. Añadir clicks como factor secundario (dividido por 1000 para que no sobrescriba la relevancia)
                $relevancia += ($producto->clicks ?? 0) / 1000;
                
                return $relevancia;
            })->values();

            return $productosConVariantes;
        } catch (\Exception $e) {
            \Log::error('Error en obtenerProductosOrdenadosPorRelevancia (ordenación): ' . $e->getMessage());
            return $productosConVariantes;
        }
    }

    /**
     * Busca productos para las sugerencias del header (API)
     * Usa el mismo método que buscar() pero convierte el formato
     *
     * @param bool $sinVariantes Si true, no expande sublíneas como resultados (admin crear-masivo, etc.)
     */
    private function buscarProductos(array $palabras, string $queryCompleta, int $limite, bool $sinVariantes = false)
    {
        try {
            // Usar el mismo método que buscar() para obtener productos ordenados por relevancia
            // IMPORTANTE: Obtener el mismo número de productos que la vista de búsqueda (200) 
            // para garantizar que el ordenamiento por relevancia sea idéntico
            $productosConVariantes = $this->obtenerProductosOrdenadosPorRelevancia($palabras, $queryCompleta, 200, $sinVariantes);
            
            // Limitar resultados y convertir al formato de sugerencias
            $resultados = $productosConVariantes->take($limite)->map(function($item) {
                $producto = $item['producto'];
                $variante = $item['variante'] ?? null;
                $precioVariante = $item['precio_variante'] ?? null;
                $imagenVariante = $item['imagen_variante'] ?? null;
                
                // Usar formatearResultadoProducto para mantener compatibilidad con el formato de sugerencias
                return $this->formatearResultadoProducto($producto, $variante, $precioVariante, $imagenVariante);
            });

            return $resultados;
        } catch (\Exception $e) {
            \Log::error('Error en buscarProductos: ' . $e->getMessage());
            return collect();
        }
    }

    /**
     * Genera variantes de productos basadas en especificaciones internas
     */
    private function generarVariantesProductos($productos, array $palabras, string $queryCompleta)
    {
        $resultados = collect();

        foreach ($productos as $producto) {
            $especificacionesBusqueda = $producto->especificaciones_busqueda;

            if (!$especificacionesBusqueda || !is_array($especificacionesBusqueda) || empty($especificacionesBusqueda)) {
                if ($this->productoCoincideBusqueda($producto, $palabras)) {
                    $resultados->push($this->formatearResultadoProducto($producto, null, null));
                }
                continue;
            }

            $coincidencias = [];
            $queryNorm = $this->normalizarTextoBusqueda($queryCompleta);
            $textoBaseProducto = trim($this->normalizarTextoBusqueda($producto->nombre . ' ' . $producto->marca . ' ' . $producto->modelo)
                . ' ' . $this->obtenerTextoJerarquiaProducto($producto));

            foreach ($especificacionesBusqueda as $textoEspecificacion => $datos) {
                $textoOriginal = $datos['texto'] ?? $textoEspecificacion;
                $textoCompletoVariante = trim($textoBaseProducto . ' ' . $this->normalizarTextoBusqueda($textoOriginal));

                if ($this->coincidenTodasLasPalabrasEnTexto($textoCompletoVariante, $palabras)
                    || $this->coincidenTodasLasPalabrasEnTexto($textoCompletoVariante, [$queryNorm])) {
                    $varianteId = $datos['id'] ?? null;
                    $claveUnica = $varianteId ? 'v_' . $varianteId : $textoEspecificacion . '_' . md5($textoOriginal);

                    if (!isset($coincidencias[$claveUnica])) {
                        $coincidencias[$claveUnica] = [
                            'texto_especificacion' => $textoEspecificacion,
                            'datos' => $datos
                        ];
                    }
                }
            }

            if (empty($coincidencias)) {
                if ($this->productoCoincideBusqueda($producto, $palabras)) {
                    $resultados->push($this->formatearResultadoProducto($producto, null, null));
                }
                continue;
            }

            foreach ($coincidencias as $item) {
                $datos = $item['datos'];
                $resultados->push($this->formatearResultadoProducto(
                    $producto,
                    $datos['texto'] ?? $item['texto_especificacion'],
                    $datos['precio_unidad'] ?? $producto->precio,
                    $datos['imagen'] ?? null
                ));
            }
        }

        return $resultados;
    }

    /**
     * Genera variantes de productos para la búsqueda principal (buscar.blade.php)
     */
    private function generarVariantesProductosParaBuscar($productos, array $palabras, string $queryCompleta)
    {
        $resultados = collect();
        $servicioOfertas = new SacarPrimeraOfertaDeUnProductoAplicadoDescuentosYChollos();

        foreach ($productos as $producto) {
            $especificacionesBusqueda = $producto->especificaciones_busqueda;

            if (!$especificacionesBusqueda || !is_array($especificacionesBusqueda) || empty($especificacionesBusqueda)) {
                if ($this->productoCoincideBusqueda($producto, $palabras)) {
                    $resultados->push([
                        'producto' => $producto,
                        'variante' => null,
                        'precio_variante' => null,
                        'es_variante' => false
                    ]);
                }
                continue;
            }

            $coincidencias = [];
            $queryNorm = $this->normalizarTextoBusqueda($queryCompleta);
            $textoBaseProducto = trim($this->normalizarTextoBusqueda($producto->nombre . ' ' . $producto->marca . ' ' . $producto->modelo)
                . ' ' . $this->obtenerTextoJerarquiaProducto($producto));

            foreach ($especificacionesBusqueda as $textoEspecificacion => $datos) {
                $textoOriginal = $datos['texto'] ?? $textoEspecificacion;
                $textoCompletoVariante = trim($textoBaseProducto . ' ' . $this->normalizarTextoBusqueda($textoOriginal));

                if ($this->coincidenTodasLasPalabrasEnTexto($textoCompletoVariante, $palabras)
                    || $this->coincidenTodasLasPalabrasEnTexto($textoCompletoVariante, [$queryNorm])) {
                    $varianteId = $datos['id'] ?? null;
                    $claveUnica = $varianteId ? 'v_' . $varianteId : $textoEspecificacion . '_' . md5($textoOriginal);

                    if (!isset($coincidencias[$claveUnica])) {
                        $coincidencias[$claveUnica] = [
                            'texto_especificacion' => $textoEspecificacion,
                            'datos' => $datos
                        ];
                    }
                }
            }

            if (empty($coincidencias)) {
                if ($this->productoCoincideBusqueda($producto, $palabras)) {
                    $resultados->push([
                        'producto' => $producto,
                        'variante' => null,
                        'precio_variante' => null,
                        'es_variante' => false
                    ]);
                }
                continue;
            }

            $especificacionesElegidas = $producto->categoria_especificaciones_internas_elegidas;
            $categoriaEspecificaciones = $producto->categoriaEspecificaciones;

            if ($categoriaEspecificaciones && $especificacionesElegidas) {
                $todasLasOfertas = $servicioOfertas->obtenerTodas($producto);

                foreach ($coincidencias as $item) {
                    $textoEspecificacion = $item['texto_especificacion'];
                    $datos = $item['datos'];
                    $textoParaMostrar = $datos['texto'] ?? $textoEspecificacion;
                    $sublineaId = $datos['id'] ?? null;
                    $lineaId = null;

                    foreach ($especificacionesElegidas as $lineaIdKey => $sublineasProducto) {
                        if (strpos($lineaIdKey, '_') === 0) {
                            continue;
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

                            foreach ($ofertaSublineas as $ofertaItem) {
                                $itemId = (is_array($ofertaItem) && isset($ofertaItem['id'])) ? strval($ofertaItem['id']) : strval($ofertaItem);
                                if (strval($itemId) === strval($sublineaId)) {
                                    return true;
                                }
                            }

                            return false;
                        });

                        $precioMasBarato = $ofertasVariante->isNotEmpty()
                            ? $ofertasVariante->min('precio_unidad')
                            : ($datos['precio_unidad'] ?? $producto->precio);
                    } else {
                        $precioMasBarato = $datos['precio_unidad'] ?? $producto->precio;
                    }

                    $resultados->push([
                        'producto' => $producto,
                        'variante' => $textoParaMostrar,
                        'precio_variante' => $precioMasBarato,
                        'imagen_variante' => $datos['imagen'] ?? null,
                        'es_variante' => true
                    ]);
                }
            } else {
                foreach ($coincidencias as $item) {
                    $datos = $item['datos'];
                    $textoParaMostrar = $datos['texto'] ?? $item['texto_especificacion'];

                    $resultados->push([
                        'producto' => $producto,
                        'variante' => $textoParaMostrar,
                        'precio_variante' => $datos['precio_unidad'] ?? $producto->precio,
                        'imagen_variante' => $datos['imagen'] ?? null,
                        'es_variante' => true
                    ]);
                }
            }
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

    /**
     * Búsqueda unificada de productos para administración (sin variantes/especificaciones)
     *
     * @param string $query
     * @param int|string|null $categoriaId
     * @param int $limite
     * @return \Illuminate\Support\Collection
     */
    public function buscarProductosParaAdmin(string $query, $categoriaId = null, int $limite = 20)
    {
        $queryNorm = $this->normalizarTextoBusqueda($query);
        $palabras = $this->tokenizarConsultaBusqueda($queryNorm);

        if (empty($palabras)) {
            return collect();
        }

        $idsCategorias = null;
        if ($categoriaId !== null && $categoriaId !== '' && is_numeric($categoriaId)) {
            $idsCategorias = \App\Models\Categoria::idsSelfAndDescendants((int) $categoriaId);
        }

        $dbQuery = Producto::where('obsoleto', 'no')
            ->when($idsCategorias !== null, function ($q) use ($idsCategorias) {
                $q->whereIn('categoria_id', $idsCategorias);
            })
            ->where(function($q) use ($palabras, $queryNorm) {
                foreach ($palabras as $palabra) {
                    $q->where(function($wordQ) use ($palabra) {
                        $this->agregarCoincidenciaTerminoEnCamposProducto($wordQ, $palabra, 'where');

                        $categoriaIds = $this->obtenerCategoriaIdsPorPalabraIndividual($palabra);
                        if (!empty($categoriaIds)) {
                            $wordQ->orWhereIn('categoria_id', $categoriaIds);
                        }

                        $wordQ->orWhere(function($specQ) use ($palabra) {
                            $specQ->whereNotNull('especificaciones_busqueda_texto');
                            $like = '%' . $this->normalizarTextoBusqueda($palabra) . '%';
                            $specQ->whereRaw(
                                $this->expresionCampoTextoBusqueda('especificaciones_busqueda_texto') . ' LIKE ?',
                                [$like]
                            );
                        });
                    });
                }

                if (count($palabras) > 1) {
                    $q->orWhere(function($fullQ) use ($queryNorm) {
                        $this->agregarCoincidenciaTerminoEnCamposProducto($fullQ, $queryNorm, 'where');
                    });
                }
            });

        try {
            $productos = $this->aplicarOrdenRelevanciaBusquedaSql($dbQuery, $queryNorm, $palabras)
                ->limit($limite)
                ->with('categoria.parent.parent')
                ->get();

            // Ordenamos por relevancia exactamente igual que en el front pero sin variantes
            $productosOrdenados = $productos->sortByDesc(function($producto) use ($queryNorm, $palabras) {
                $relevancia = 0;
                
                $nombreCompleto = $this->normalizarTextoBusqueda($producto->nombre . ' ' . $producto->marca . ' ' . $producto->modelo);
                $marcaCompleta = $this->normalizarTextoBusqueda($producto->marca ?? '');
                
                if ($nombreCompleto === $queryNorm) {
                    $relevancia += 10000;
                }
                elseif ($marcaCompleta === $queryNorm) {
                    $relevancia += 9000;
                }
                elseif (str_contains($nombreCompleto, $queryNorm)) {
                    $relevancia += 5000;
                }
                elseif (str_contains($marcaCompleta, $queryNorm)) {
                    $relevancia += 4500;
                }
                elseif (str_contains($queryNorm, $nombreCompleto)) {
                    $relevancia += 4000;
                }
                
                $palabrasCoincidentes = 0;
                $textoProducto = $nombreCompleto . ' ' . $marcaCompleta . ' ' . $this->normalizarTextoBusqueda($producto->talla ?? '')
                    . ' ' . $this->obtenerTextoJerarquiaProducto($producto);
                foreach ($palabras as $palabra) {
                    if ($this->coincidePalabraEnTexto($textoProducto, $palabra)) {
                        $palabrasCoincidentes++;
                    }
                }
                $relevancia += $palabrasCoincidentes * 1000;

                if (count($palabras) > 1 && $palabrasCoincidentes === count($palabras)) {
                    $relevancia += 3000;
                }
                
                $relevancia += ($producto->clicks ?? 0) / 1000;
                
                return $relevancia;
            })->values();

            return $productosOrdenados;
        } catch (\Exception $e) {
            \Log::error('Error en buscarProductosParaAdmin: ' . $e->getMessage());
            return collect();
        }
    }
}
