<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Producto extends Model
{
    protected $table = 'productos';

    protected $fillable = [
        'nombre',
        'marca',
        'modelo',
        'talla',
        'precio',
        'rebajado',
        'unidadDeMedida',
        'imagen_grande',
        'imagen_pequena',
        'titulo',
        'subtitulo',
        'descripcion_corta',
        'descripcion_larga',
        'caracteristicas',
        'pros',
        'contras',
        'faq',
        'slug',
        'categoria_id',
        'meta_titulo',
        'meta_description',
        'obsoleto',
        'mostrar',
        'clicks',
        'keys_relacionados',
        'id_categoria_productos_relacionados',
        'anotaciones_internas',
        'descuentos',
        'aviso',
        'categoria_id_especificaciones_internas',
        'categoria_especificaciones_internas_elegidas',
        'grupos_de_ofertas',
    ];

    protected $casts = [
        'caracteristicas' => 'array',
        'pros' => 'array',
        'contras' => 'array',
        'faq' => 'array',
        'keys_relacionados' => 'array',
        'categoria_especificaciones_internas_elegidas' => 'array',
        'grupos_de_ofertas' => 'array',
        'imagen_grande' => 'array',
        'imagen_pequena' => 'array',
        'aviso' => 'datetime',
    ];

    public function ofertas()
    {
        return $this->hasMany(OfertaProducto::class);
    }

    public function historico()
    {
        return $this->hasMany(\App\Models\HistoricoPrecioProducto::class);
    }

    public function categoria()
    {
        return $this->belongsTo(Categoria::class, 'categoria_id');
    }

    public function alertasPrecio()
    {
        return $this->hasMany(CorreoAvisoPrecio::class);
    }

    // Sacamos la ruta completa de categorias de un producto con su slug
    public function getRutaCompletaAttribute()
{
    $categorias = [];
    $categoria = $this->categoria;

    while ($categoria) {
        array_unshift($categorias, $categoria->slug);
        $categoria = $categoria->parent;
    }

    return implode('/', $categorias) . '/' . $this->slug;
}
}
