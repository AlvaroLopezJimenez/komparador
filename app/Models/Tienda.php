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
        'api',
        'mostrar_tienda',
        'scrapear',
        'como_scrapear',
    ];

    public function ofertas()
    {
        return $this->hasMany(\App\Models\OfertaProducto::class);
    }
    public function comisiones()
{
    return $this->hasMany(\App\Models\ComisionCategoriaTienda::class);
}
}
