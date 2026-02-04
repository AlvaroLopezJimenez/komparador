<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HistoricoTiempoActualizacionPrecioOferta extends Model
{
    protected $table = 'historico_tiempos_de_actualizacion_precios_ofertas';
    
    protected $fillable = [
        'oferta_id',
        'precio_total',
        'tipo_actualizacion',
        'frecuencia_aplicada_minutos',
        'frecuencia_calculada_minutos',
    ];
    
    protected $casts = [
        'precio_total' => 'decimal:2',
        'frecuencia_aplicada_minutos' => 'integer',
        'frecuencia_calculada_minutos' => 'integer',
    ];
    
    public function oferta()
    {
        return $this->belongsTo(OfertaProducto::class);
    }
}







