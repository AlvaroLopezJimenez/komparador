<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrecioHot extends Model
{
    protected $table = 'precios_hot';

    protected $fillable = [
        'nombre',
        'datos',
    ];

    protected $casts = [
        'datos' => 'array',
    ];
} 