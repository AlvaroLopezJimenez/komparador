<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tienda extends Model
{
    protected $fillable = [
        'nombre',
        'envio_gratis',
        'envio_normal',
        'url',
        'url_imagen',
        'opiniones',
        'puntuacion',
        'url_opiniones',
        'anotaciones_internas',
        'aviso',
        'avisos_sin_stock_scrapear_automatico',
        'api',
        'url_csv',
        'mostrar_tienda',
        'scrapear',
        'como_scrapear',
        'frecuencia_minima_minutos',
        'frecuencia_maxima_minutos',
    ];

    protected $casts = [
        'url_csv' => 'array',
    ];

    public function ofertas()
    {
        return $this->hasMany(\App\Models\OfertaProducto::class);
    }
    public function comisiones()
    {
        return $this->hasMany(\App\Models\ComisionCategoriaTienda::class);
    }

    public function scrapingPorCategoria()
    {
        return $this->hasMany(\App\Models\TiendaCategoriaApi::class);
    }
}
