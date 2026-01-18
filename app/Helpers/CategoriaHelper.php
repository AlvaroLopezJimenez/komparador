<?php

namespace App\Helpers;

use App\Models\Categoria;
use Illuminate\Support\Collection;

class CategoriaHelper
{
    /**
     * Obtiene toda la jerarquía de categorías desde una categoría específica hacia arriba
     */
    public static function obtenerJerarquiaCompleta($categoriaId)
    {
        $jerarquia = [];
        $categoria = Categoria::find($categoriaId);
        
        if (!$categoria) {
            return $jerarquia;
        }
        
        // Agregar la categoría actual
        $jerarquia[] = $categoria;
        
        // Recorrer hacia arriba hasta encontrar la raíz
        $categoriaActual = $categoria;
        while ($categoriaActual->parent_id) {
            $categoriaActual = $categoriaActual->padre;
            if ($categoriaActual) {
                array_unshift($jerarquia, $categoriaActual);
            } else {
                break;
            }
        }
        
        return $jerarquia;
    }
    
    /**
     * Obtiene los slugs de la jerarquía completa
     */
    public static function obtenerSlugsJerarquia($categoriaId)
        {
            $jerarquia = self::obtenerJerarquiaCompleta($categoriaId);
            return collect($jerarquia)->pluck('slug')->toArray();
        }
    
    /**
     * Construye la URL de categorías para un producto
     */
    public static function construirUrlCategorias($categoriaId, $slugProducto)
        {
            $slugs = self::obtenerSlugsJerarquia($categoriaId);

            if (empty($slugs)) {
                // Devuelve solo el path relativo
                return '/categoria/' . $slugProducto;
            }

            $categorias = implode('/', $slugs);

            // Devuelve solo el path relativo
            return '/' . $categorias . '/' . $slugProducto;
        }
    
    /**
     * Obtiene el breadcrumb completo para un producto
     */
    public static function obtenerBreadcrumb($categoriaId)
{
    $jerarquia = self::obtenerJerarquiaCompleta($categoriaId);

    return collect($jerarquia)->map(function ($categoria) {
        return [
            'nombre' => $categoria->nombre,
            'slug' => $categoria->slug
        ];
    })->toArray();
}
    
    /**
     * Verifica si una categoría puede tener más niveles de profundidad
     */
    public static function puedeTenerMasNiveles($categoriaId)
    {
        // Por ahora no hay límite, pero se puede implementar lógica aquí
        return true;
    }
    
    /**
     * Obtiene todas las categorías hijas de una categoría específica
     */
    public static function obtenerCategoriasHijas($categoriaId)
    {
        return Categoria::where('parent_id', $categoriaId)
            ->orderBy('nombre')
            ->get();
    }
    
    /**
     * Obtiene todas las categorías raíz
     */
    public static function obtenerCategoriasRaiz()
    {
        return Categoria::whereNull('parent_id')
            ->orderBy('nombre')
            ->get();
    }
}
