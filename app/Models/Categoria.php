<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Helpers\CategoriaHelper;

class Categoria extends Model
{
    protected $fillable = [
        'nombre',
        'imagen',
        'clicks',
        'slug',
        'parent_id',
        'especificaciones_internas',
        'info_adicional_chatgpt'
    ];

    protected $casts = [
        'especificaciones_internas' => 'array',
    ];

    public function padre()
    {
        return $this->belongsTo(Categoria::class, 'parent_id');
    }

    public function subcategorias()
    {
        return $this->hasMany(Categoria::class, 'parent_id');
    }

    public function productos()
    {
        return $this->hasMany(\App\Models\Producto::class, 'categoria_id');
    }
    
    public function children()
    {
        return $this->hasMany(Categoria::class, 'parent_id');
    }
    
    public function parent()
    {
        return $this->belongsTo(Categoria::class, 'parent_id');
    }
    
    /**
     * Obtiene toda la jerarquía de categorías hacia arriba
     */
    public function obtenerJerarquiaCompleta()
    {
        return CategoriaHelper::obtenerJerarquiaCompleta($this->id);
    }
    
    /**
     * Obtiene los slugs de la jerarquía completa
     */
    public function obtenerSlugsJerarquia()
    {
        return CategoriaHelper::obtenerSlugsJerarquia($this->id);
    }
    
    /**
     * Construye la URL de categorías para un producto
     */
    public function construirUrlCategorias($slugProducto)
    {
        return CategoriaHelper::construirUrlCategorias($this->id, $slugProducto);
    }
    
    /**
     * Obtiene el breadcrumb completo
     */
    public function obtenerBreadcrumb()
    {
        return CategoriaHelper::obtenerBreadcrumb($this->id);
    }
    
    /**
     * Verifica si es una categoría raíz
     */
    public function esRaiz()
    {
        return is_null($this->parent_id);
    }
    
    /**
     * Obtiene el nivel de profundidad de la categoría
     */
    public function obtenerNivel()
    {
        $nivel = 0;
        $categoria = $this;
        
        while ($categoria->parent_id) {
            $nivel++;
            $categoria = $categoria->padre;
            if (!$categoria) break;
        }
        
        return $nivel;
    }
    
    /**
     * Obtiene todas las categorías hijas recursivamente
     */
    public function obtenerTodasLasHijas()
    {
        $hijas = collect();
        
        foreach ($this->subcategorias as $subcategoria) {
            $hijas->push($subcategoria);
            $hijas = $hijas->merge($subcategoria->obtenerTodasLasHijas());
        }
        
        return $hijas;
    }
    
    /**
     * Obtiene el conteo total de productos incluyendo subcategorías
     */
    public function obtenerTotalProductos()
    {
        $total = $this->productos()->count();
        
        foreach ($this->subcategorias as $subcategoria) {
            $total += $subcategoria->obtenerTotalProductos();
        }
        
        return $total;
    }

    /**
     * IDs de la categoría y todas sus descendientes (para filtrar productos por rama).
     *
     * @return list<int>
     */
    public static function idsSelfAndDescendants(int $categoriaId): array
    {
        $ids = [$categoriaId];
        $parents = [$categoriaId];
        while (true) {
            $children = static::query()->whereIn('parent_id', $parents)->pluck('id')->all();
            if ($children === []) {
                break;
            }
            $ids = array_merge($ids, $children);
            $parents = $children;
        }

        return array_values(array_unique(array_map('intval', $ids)));
    }

    /**
     * Mismo árbol que en admin/categorías index: raíz con subcategorías recursivas y productos_count agregado.
     *
     * @return \Illuminate\Support\Collection<int, Categoria>
     */
    public static function categoriasRaizConConteosAdministracion()
    {
        $categoriasRaiz = static::with(['subcategorias' => function ($query) {
            $query->orderBy('nombre');
        }])
            ->whereNull('parent_id')
            ->orderBy('nombre')
            ->get();

        static::cargarSubcategoriasYConteosAdministracionRecursivo($categoriasRaiz);

        return $categoriasRaiz;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Categoria>|iterable<int, Categoria>  $categorias
     */
    private static function cargarSubcategoriasYConteosAdministracionRecursivo($categorias): void
    {
        foreach ($categorias as $categoria) {
            $categoria->load(['subcategorias' => function ($query) {
                $query->orderBy('nombre');
            }]);

            $categoria->productos_count = $categoria->productos()->count();

            if ($categoria->subcategorias->count() > 0) {
                static::cargarSubcategoriasYConteosAdministracionRecursivo($categoria->subcategorias);

                foreach ($categoria->subcategorias as $sub) {
                    $categoria->productos_count += $sub->productos_count;
                }
            }
        }
    }
}
