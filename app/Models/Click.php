<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Click extends Model
{
    protected $fillable = ['oferta_id', 'campaña', 'ip', 'precio_unidad', 'posicion', 'ciudad', 'pais', 'latitud', 'longitud'];

    public function oferta()
    {
        return $this->belongsTo(OfertaProducto::class, 'oferta_id');
    }
}
