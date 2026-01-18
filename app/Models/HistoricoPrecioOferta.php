<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class HistoricoPrecioOferta extends Model
{
    use HasFactory;

    protected $table = 'historico_precios_ofertas';

    protected $fillable = [
        'oferta_producto_id',
        'fecha',
        'precio_unidad',
    ];

    public function oferta()
    {
        return $this->belongsTo(OfertaProducto::class, 'oferta_producto_id');
    }
}
