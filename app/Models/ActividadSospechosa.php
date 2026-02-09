<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActividadSospechosa extends Model
{
    protected $table = 'actividad_sospechosa';
    
    public $timestamps = false;
    
    protected $fillable = [
        'ip',
        'fingerprint',
        'user_agent',
        'endpoint',
        'method',
        'score',
        'accion_tomada',
        'detalles',
        'created_at',
    ];

    protected $casts = [
        'score' => 'integer',
        'detalles' => 'array',
        'created_at' => 'datetime',
    ];
}







