<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CorreoAvisoPrecio extends Model
{
    use HasFactory;

    protected $table = 'correos_aviso_precio';

    protected $fillable = [
        'correo',
        'precio_limite',
        'producto_id',
        'token_cancelacion',
        'ultimo_envio_correo',
        'veces_enviado',
    ];

    protected $casts = [
        'ultimo_envio_correo' => 'datetime',
        'precio_limite' => 'decimal:2',
        'veces_enviado' => 'integer',
    ];

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }
}