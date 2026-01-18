<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PalabraClaveProducto extends Model
{
    protected $table = 'palabras_clave_productos';

    protected $fillable = ['producto_id', 'palabra', 'codigo', 'activa'];

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }
}
