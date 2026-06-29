<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TiendaCategoriaApi extends Model
{
    protected $table = 'tienda_categoria_api';

    protected $fillable = [
        'tienda_id',
        'categoria_id',
        'api',
        'scrapear',
        'mostrar',
        'frecuencia_minima_minutos',
        'frecuencia_maxima_minutos',
    ];

    public function tienda()
    {
        return $this->belongsTo(Tienda::class);
    }

    public function categoria()
    {
        return $this->belongsTo(Categoria::class);
    }
}
