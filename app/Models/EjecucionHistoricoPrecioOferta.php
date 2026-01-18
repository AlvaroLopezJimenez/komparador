<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EjecucionHistoricoPrecioOferta extends Model
{
    protected $table = 'ejecuciones_historico_precios_ofertas';

    protected $fillable = [
        'inicio',
        'fin',
        'total_ofertas',
        'total_guardados',
        'total_errores',
        'log'
    ];

    protected $casts = [
        'log' => 'array',
        'inicio' => 'datetime',
        'fin' => 'datetime',
    ];
}
