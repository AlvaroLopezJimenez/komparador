<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HistoricoPrecioProducto extends Model
{
    protected $table = 'historico_precios_productos';

    protected $fillable = [
        'producto_id',
        'especificacion_interna_id',
        'fecha',
        'precio_minimo',
    ];
}
