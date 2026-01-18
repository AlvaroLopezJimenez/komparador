<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EjecucionGlobal extends Model
{
    use HasFactory;

    protected $table = 'ejecuciones_global';

    protected $fillable = [
        'inicio',
        'fin',
        'nombre',
        'total',
        'total_guardado',
        'total_errores',
        'log',
    ];

    protected $casts = [
        'inicio' => 'datetime',
        'fin' => 'datetime',
        'log' => 'array',
    ];
}
