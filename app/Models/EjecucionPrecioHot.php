<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EjecucionPrecioHot extends Model
{
    protected $table = 'ejecuciones_precios_hot';

    protected $fillable = [
        'inicio',
        'fin',
        'total_categorias',
        'total_inserciones',
        'total_errores',
        'log',
    ];

    protected $casts = [
        'inicio' => 'datetime',
        'fin' => 'datetime',
        'log' => 'array',
    ];
} 