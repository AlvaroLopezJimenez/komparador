<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EjecucionScrapearOferta extends Model
{
    protected $table = 'ejecuciones_scrapear_ofertas';

    protected $fillable = [
        'inicio',
        'fin',
        'total_ofertas',
        'total_actualizadas',
        'total_errores',
        'log',
    ];

    protected $casts = [
        'inicio' => 'datetime',
        'fin' => 'datetime',
        'log' => 'array',
    ];
}
