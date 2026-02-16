<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserActivity extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'action_type',
        'producto_id',
        'oferta_id',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relación con el usuario
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relación con el producto
     */
    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    /**
     * Relación con la oferta
     */
    public function oferta(): BelongsTo
    {
        return $this->belongsTo(OfertaProducto::class, 'oferta_id');
    }

    /**
     * Tipos de acción disponibles
     */
    public const ACTION_PRODUCTO_CREADO = 'producto_creado';
    public const ACTION_PRODUCTO_MODIFICADO = 'producto_modificado';
    public const ACTION_OFERTA_CREADA = 'oferta_creada';
    public const ACTION_OFERTA_MODIFICADA = 'oferta_modificada';
    public const ACTION_LOGIN = 'login';
}








