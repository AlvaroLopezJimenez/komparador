<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EjecucionActualizacionClicks extends Model
{
    protected $table = 'ejecuciones_actualizacion_clicks';

    protected $fillable = [
        'tipo',
        'estado',
        'resultado',
        'inicio',
        'fin',
        'total_productos',
        'total_actualizados',
        'total_errores',
        'log',
    ];

    protected $casts = [
        'inicio' => 'datetime',
        'fin' => 'datetime',
        'log' => 'array',
    ];
} 