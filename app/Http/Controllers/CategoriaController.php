<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Helpers\CategoriaHelper;

class CategoriaController extends Controller
{
    public function index()
    {
        $todasCategorias = Categoria::orderBy('nombre')->get();

        // Cargar toda la jerarquía de forma recursiva
        $categoriasRaiz = Categoria::with(['subcategorias' => function ($query) {
            $query->orderBy('nombre');
        }])
            ->whereNull('parent_id')
            ->orderBy('nombre')
            ->get();

        // Función recursiva para cargar subcategorías y contar productos
        $this->cargarSubcategoriasRecursivamente($categoriasRaiz);

        return view('admin.categorias.index', compact('todasCategorias', 'categoriasRaiz'));
    }

    /**
     * Carga subcategorías de forma recursiva y cuenta productos
     */
    private function cargarSubcategoriasRecursivamente($categorias)
    {
        foreach ($categorias as $categoria) {
            // Cargar subcategorías
            $categoria->load(['subcategorias' => function ($query) {
                $query->orderBy('nombre');
            }]);
            
            // Contar productos directos
            $categoria->productos_count = $categoria->productos()->count();
            
            // Recursivamente cargar subcategorías y sumar productos
            if ($categoria->subcategorias->count() > 0) {
                $this->cargarSubcategoriasRecursivamente($categoria->subcategorias);
                
                // Sumar productos de subcategorías
                foreach ($categoria->subcategorias as $sub) {
                    $categoria->productos_count += $sub->productos_count;
                }
            }
        }
    }

    public function create()
    {
        // Cargar categorías raíz para el selector de categoría padre
        $categoriasRaiz = Categoria::whereNull('parent_id')
            ->orderBy('nombre')
            ->get();

        return view('admin.categorias.formulario', [
            'categoria' => null,
            'categoriasRaiz' => $categoriasRaiz
        ]);
    }

    public function edit(Categoria $categoria)
    {
        // Cargar categorías raíz para el selector de categoría padre
        $categoriasRaiz = Categoria::whereNull('parent_id')
            ->orderBy('nombre')
            ->get();

        // Calcular conteos de productos por sublínea
        $conteosProductos = $this->calcularConteosProductos($categoria);

        return view('admin.categorias.formulario', [
            'categoria' => $categoria,
            'categoriasRaiz' => $categoriasRaiz,
            'conteosProductos' => $conteosProductos
        ]);
    }

    public function store(Request $request)
    {
        // Si el slug viene vacío, generarlo del nombre
        if (empty($request->slug)) {
            $slug = Str::slug($request->nombre);
        } else {
            $slug = Str::slug($request->slug);
        }

        $request->validate([
            'nombre' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'parent_id' => 'nullable|exists:categorias,id',
            'imagen' => 'nullable|string|max:255',
            'especificaciones_internas' => 'nullable|string',
            'info_adicional_chatgpt' => 'nullable|string',
        ], [
            'slug.unique' => 'El slug ya existe. Por favor, elige otro.',
        ]);

        // Verificar que el slug sea único - NO permitir duplicados
        if (Categoria::where('slug', $slug)->exists()) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['slug' => 'El slug "' . $slug . '" ya existe. Por favor, elige otro slug.']);
        }

        // Procesar especificaciones internas
        $especificacionesInternas = null;
        if ($request->has('especificaciones_internas') && !empty($request->especificaciones_internas)) {
            $especificacionesInternas = json_decode($request->especificaciones_internas, true);
        }

        // Crear la categoría
        Categoria::create([
            'nombre' => $request->nombre,
            'slug' => $slug,
            'parent_id' => $request->parent_id,
            'imagen' => $request->imagen,
            'especificaciones_internas' => $especificacionesInternas,
            'info_adicional_chatgpt' => $request->info_adicional_chatgpt,
        ]);

        return redirect()->route('admin.categorias.index')->with('success', 'Categoría creada correctamente.');
    }

    public function update(Request $request, Categoria $categoria)
    {
        // Si el slug viene vacío, generarlo del nombre
        if (empty($request->slug)) {
            $slug = Str::slug($request->nombre);
        } else {
            $slug = Str::slug($request->slug);
        }

        $request->validate([
            'nombre' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'parent_id' => 'nullable|exists:categorias,id',
            'imagen' => 'nullable|string|max:255',
            'especificaciones_internas' => 'nullable|string',
            'info_adicional_chatgpt' => 'nullable|string',
        ]);

        // Si el slug es diferente al actual, verificar que no exista en otra categoría
        if ($slug !== $categoria->slug) {
            if (Categoria::where('slug', $slug)->where('id', '!=', $categoria->id)->exists()) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['slug' => 'El slug "' . $slug . '" ya existe en otra categoría. Por favor, elige otro slug.']);
            }
        }

        // Procesar especificaciones internas
        $especificacionesInternas = null;
        if ($request->has('especificaciones_internas') && !empty($request->especificaciones_internas)) {
            $especificacionesInternas = json_decode($request->especificaciones_internas, true);
        }

        // Actualizar la categoría
        $categoria->update([
            'nombre' => $request->nombre,
            'slug' => $slug,
            'parent_id' => $request->parent_id,
            'imagen' => $request->imagen,
            'especificaciones_internas' => $especificacionesInternas,
            'info_adicional_chatgpt' => $request->info_adicional_chatgpt,
        ]);

        return redirect()->route('admin.categorias.index')->with('success', 'Categoría actualizada correctamente.');
    }

    public function subcategorias($parentId)
    {
        return Categoria::where('parent_id', $parentId)->select('id', 'nombre')->get();
    }
    
    public function jerarquia($categoriaId)
    {
        $categoria = Categoria::findOrFail($categoriaId);
        $jerarquia = [];
        
        // Construir la jerarquía desde la raíz hasta la categoría actual
        $categoriaActual = $categoria;
        while ($categoriaActual) {
            array_unshift($jerarquia, [
                'id' => $categoriaActual->id,
                'nombre' => $categoriaActual->nombre
            ]);
            $categoriaActual = $categoriaActual->parent;
        }
        
        return response()->json([
            'jerarquia' => $jerarquia
        ]);
    }
    
    // Método deshabilitado para evitar eliminaciones accidentales de categorías
    // que podrían eliminar muchos productos y ofertas asociadas
    /*
    public function destroy(Categoria $categoria)
    {
        $tieneHijas = $categoria->subcategorias()->exists();
        $tieneProductos = $categoria->productos()->exists();

        if ($tieneHijas || $tieneProductos) {
            return back()->with('error', 'No se puede eliminar: tiene subcategorías o productos asociados.');
        }

        $categoria->delete();

        return back()->with('success', 'Categoría eliminada correctamente.');
    }
    */

    //ACTUALIZAR NOMBRE E IMAGEN DE LA CATEGORIA
    public function updateNombre(Request $request, Categoria $categoria)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'imagen' => 'nullable|string|max:255'
        ]);

        $nombre = $request->input('nombre');
        
        // Si se proporciona slug, usarlo; si no, generar del nombre
        if ($request->has('slug') && !empty($request->input('slug'))) {
            $slug = Str::slug($request->input('slug'));
        } else {
            $slug = Str::slug($nombre);
        }

        // Si el slug es diferente al actual, verificar que no exista en otra categoría
        if ($slug !== $categoria->slug) {
            // Verificar si el slug ya existe en otra categoría
            if (Categoria::where('slug', $slug)->where('id', '!=', $categoria->id)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'El slug "' . $slug . '" ya existe en otra categoría. Por favor, elige otro slug.'
                ], 422);
            }
        }

        $categoria->update([
            'nombre' => $nombre,
            'slug' => $slug,
            'imagen' => $request->input('imagen')
        ]);

        return response()->json(['success' => true]);
    }

    // Verificar si un slug existe
    public function verificarSlug(Request $request)
    {
        $slug = Str::slug($request->slug);
        $existe = Categoria::where('slug', $slug)->exists();
        
        if ($request->has('categoria_id')) {
            // Si estamos editando, excluir la categoría actual
            $existe = Categoria::where('slug', $slug)
                ->where('id', '!=', $request->categoria_id)
                ->exists();
        }

        return response()->json([
            'existe' => $existe,
            'slug' => $slug
        ]);
    }

    // Obtener información adicional para ChatGPT
    public function obtenerInfoChatgpt($categoriaId)
    {
        $categoria = Categoria::find($categoriaId);
        
        if (!$categoria) {
            return response()->json([
                'success' => false,
                'message' => 'Categoría no encontrada'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'info_adicional' => $categoria->info_adicional_chatgpt ?? ''
        ]);
    }

    /**
     * Calcula el número de productos que tienen cada sublínea marcada
     */
    private function calcularConteosProductos(Categoria $categoria)
    {
        $conteos = [];
        
        // Si la categoría no tiene especificaciones internas, retornar array vacío
        if (!$categoria->especificaciones_internas || !isset($categoria->especificaciones_internas['filtros'])) {
            return $conteos;
        }
        
        // Obtener todos los productos que usan esta categoría para especificaciones internas
        $productos = \App\Models\Producto::where('categoria_id_especificaciones_internas', $categoria->id)
            ->whereNotNull('categoria_especificaciones_internas_elegidas')
            ->where('mostrar', 'si')
            ->get(['id', 'categoria_especificaciones_internas_elegidas']);
        
        // Recorrer cada línea principal
        foreach ($categoria->especificaciones_internas['filtros'] as $filtro) {
            $lineaId = (string) ($filtro['id'] ?? '');
            
            if (empty($lineaId)) {
                continue;
            }
            
            // Inicializar array para esta línea
            if (!isset($conteos[$lineaId])) {
                $conteos[$lineaId] = [];
            }
            
            // Recorrer cada sublínea
            foreach ($filtro['subprincipales'] ?? [] as $sub) {
                $sublineaId = (string) ($sub['id'] ?? '');
                
                if (empty($sublineaId)) {
                    continue;
                }
                
                // Contar productos que tienen esta sublínea
                $contador = 0;
                foreach ($productos as $producto) {
                    $especificaciones = $producto->categoria_especificaciones_internas_elegidas;
                    
                    if (!$especificaciones || !is_array($especificaciones)) {
                        continue;
                    }
                    
                    $productoLinea = $especificaciones[$lineaId] ?? null;
                    if (!$productoLinea) {
                        continue;
                    }
                    
                    $productoSublineas = is_array($productoLinea) ? $productoLinea : [$productoLinea];
                    
                    foreach ($productoSublineas as $item) {
                        $itemId = (is_array($item) && isset($item['id'])) ? strval($item['id']) : strval($item);
                        
                        if ($sublineaId === $itemId) {
                            $contador++;
                            break; // Evitar contar el mismo producto dos veces
                        }
                    }
                }
                
                $conteos[$lineaId][$sublineaId] = $contador;
            }
        }
        
        return $conteos;
    }
}
