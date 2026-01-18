<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Chollo extends Model
{
    use HasFactory;

    protected $fillable = [
        'producto_id',
        'tienda_id',
        'categoria_id',
        'tipo',
        'titulo',
        'slug',
        'imagen_grande',
        'imagen_pequena',
        'unidades',
        'precio_antiguo',
        'precio_nuevo',
        'precio_unidad',
        'descuentos',
        'gastos_envio',
        'descripcion',
        'url',
        'finalizada',
        'mostrar',
        'fecha_inicio',
        'fecha_final',
        'comprobada',
        'frecuencia_comprobacion_min',
        'meta_titulo',
        'meta_descripcion',
        'descripcion_interna',
        'anotaciones_internas',
        'clicks',
        'me_gusta',
        'no_me_gusta',
    ];

    protected $casts = [
        'fecha_inicio' => 'datetime',
        'fecha_final' => 'datetime',
        'comprobada' => 'datetime',
        'frecuencia_comprobacion_min' => 'integer',
        'clicks' => 'integer',
        'me_gusta' => 'integer',
        'no_me_gusta' => 'integer',
    ];

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    public function tienda()
    {
        return $this->belongsTo(Tienda::class);
    }

    public function categoria()
    {
        return $this->belongsTo(Categoria::class);
    }

    public function avisos()
    {
        return $this->morphMany(Aviso::class, 'avisoable');
    }

    public function ofertas()
    {
        return $this->hasMany(OfertaProducto::class);
    }
}

