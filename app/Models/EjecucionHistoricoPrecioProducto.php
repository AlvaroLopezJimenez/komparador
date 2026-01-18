<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EjecucionHistoricoPrecioProducto extends Model
{
    protected $table = 'ejecuciones_historico_precios_productos';

    protected $fillable = [
        'inicio',
        'fin',
        'total_productos',
        'total_guardados',
        'total_errores',
        'log',
    ];

    protected $casts = [
        'inicio' => 'datetime',
        'fin' => 'datetime',
        'log' => 'array',
    ];
}
