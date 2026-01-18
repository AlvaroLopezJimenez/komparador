<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductoOfertaMasBarataPorProducto extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'producto_oferta_mas_barata_por_producto';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'producto_id',
        'oferta_id',
        'tienda_id',
        'precio_total',
        'precio_unidad',
        'unidades',
        'url',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'precio_total' => 'decimal:2',
        'precio_unidad' => 'decimal:4',
        'unidades' => 'decimal:3',
    ];

    /**
     * Obtiene el producto asociado
     */
    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    /**
     * Obtiene la oferta asociada
     */
    public function oferta()
    {
        return $this->belongsTo(OfertaProducto::class, 'oferta_id');
    }

    /**
     * Obtiene la tienda asociada
     */
    public function tienda()
    {
        return $this->belongsTo(Tienda::class);
    }
}
