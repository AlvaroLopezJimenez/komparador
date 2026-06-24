<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VisitaUsuario extends Model
{
    protected $table = 'visitas_usuario';

    protected $fillable = [
        'visitor_id',
        'session_id',
        'producto_id',
        'categoria_id',
        'origen',
    ];

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    public function categoria()
    {
        return $this->belongsTo(Categoria::class);
    }
}
