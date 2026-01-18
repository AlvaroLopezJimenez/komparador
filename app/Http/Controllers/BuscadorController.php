<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Producto;
use App\Models\Categoria;
use App\Helpers\CategoriaHelper;
use Illuminate\Support\Str;

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
        
        if (empty($query)) {
            return redirect()->route('welcome');
        }

        // Normalizar la consulta: convertir a minúsculas y dividir en palabras
        $queryLower = strtolower(trim($query));
        $palabras = array_filter(
            explode(' ', $queryLower),
            fn($palabra) => strlen($palabra) >= 2
        );

        $queryBuilder = Producto::where('obsoleto', 'no')
            ->where('mostrar', 'si');

        if (!empty($palabras)) {
            // Obtener IDs de categorías que coinciden con las palabras
            $categoriaIds = $this->obtenerCategoriaIdsPorPalabras($palabras, $queryLower);

            $queryBuilder->where(function($q) use ($palabras, $categoriaIds) {
                // Búsqueda por palabras individuales en nombre, marca, modelo, talla
                foreach ($palabras as $palabra) {
                    $q->where(function($subQ) use ($palabra) {
                        $subQ->whereRaw('LOWER(nombre) LIKE ?', ['%' . $palabra . '%'])
                             ->orWhereRaw('LOWER(marca) LIKE ?', ['%' . $palabra . '%'])
                             ->orWhereRaw('LOWER(modelo) LIKE ?', ['%' . $palabra . '%'])
                             ->orWhereRaw('LOWER(talla) LIKE ?', ['%' . $palabra . '%']);
                    });
                }

                // Búsqueda por categorías en la jerarquía
                if (!empty($categoriaIds)) {
                    $q->orWhereIn('categoria_id', $categoriaIds);
                }
            });
        } else {
            // Fallback a búsqueda original si no hay palabras válidas
            $queryBuilder->where(function($q) use ($queryLower) {
                $q->whereRaw('LOWER(nombre) LIKE ?', ['%' . $queryLower . '%'])
                  ->orWhereRaw('LOWER(marca) LIKE ?', ['%' . $queryLower . '%'])
                  ->orWhereRaw('LOWER(modelo) LIKE ?', ['%' . $queryLower . '%'])
                  ->orWhereRaw('LOWER(talla) LIKE ?', ['%' . $queryLower . '%']);
            });
        }

        $p4X7 = $queryBuilder
            ->orderBy('clicks', 'desc')
            ->paginate(20);

        $q1X2 = $query;

        return view('buscar', compact('p4X7', 'q1X2'));
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
     * Busca productos considerando nombre, marca, modelo, talla y jerarquía de categorías
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
            });

        try {
            $productos = $query
                ->orderBy('clicks', 'desc')
                ->limit($limite)
                ->with('categoria.parent.parent')
                ->get();

            return $productos->map(function ($producto) {
                return [
                    'id' => $producto->id,
                    'nombre' => $producto->nombre,
                    'marca' => $producto->marca,
                    'modelo' => $producto->modelo,
                    'talla' => $producto->talla,
                    'slug' => $producto->slug,
                    'imagen_pequena' => is_array($producto->imagen_pequena) 
                        ? ($producto->imagen_pequena[0] ?? 'placeholder.jpg')
                        : $producto->imagen_pequena,
                    'precio' => $producto->unidadDeMedida === 'unidadMilesima' 
                        ? number_format($producto->precio, 3, ',', '.')
                        : number_format($producto->precio, 2, ',', '.'),
                    'unidadDeMedida' => $producto->unidadDeMedida,
                    'url' => '/' . $producto->ruta_completa,
                    'tipo' => 'producto'
                ];
            });
        } catch (\Exception $e) {
            // Si hay un error, devolver colección vacía
            \Log::error('Error en buscarProductos: ' . $e->getMessage());
            return collect();
        }
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
