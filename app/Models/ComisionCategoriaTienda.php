<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ComisionCategoriaTienda extends Model
{
    protected $table = 'comisiones_categoria_tienda';

    protected $fillable = ['tienda_id', 'categoria_id', 'comision'];

    public function tienda()
    {
        return $this->belongsTo(Tienda::class);
    }

    public function categoria()
    {
        return $this->belongsTo(Categoria::class);
    }
}

