<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OfertaProducto extends Model
{
    protected $table = 'ofertas_producto';
    
    public $timestamps = true;

    protected $fillable = [
        'producto_id',
        'tienda_id',
        'chollo_id',
        'unidades',
        'precio_total',
        'frecuencia_actualizar_precio_minutos',
        'precio_unidad',
        'url',
        'variante',
        'mostrar',
        'como_scrapear',
        'descuentos',
        'especificaciones_internas',
        'anotaciones_internas',
        'aviso',
        'fecha_inicio',
        'fecha_final',
        'comprobada',
        'frecuencia_comprobacion_chollos_min',
        'clicks',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'especificaciones_internas' => 'array',
        'aviso' => 'datetime',
        'fecha_inicio' => 'datetime',
        'fecha_final' => 'datetime',
        'comprobada' => 'datetime',
    ];

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    public function tienda()
    {
        return $this->belongsTo(Tienda::class);
    }

    public function chollo()
    {
        return $this->belongsTo(Chollo::class);
    }
}
