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
}
