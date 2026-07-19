<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UrlDescartada extends Model
{
    protected $table = 'urls_descartadas';

    public $timestamps = true;

    protected $fillable = [
        'url',
        'categoria_id',
        'producto_id',
        'tienda_id',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function categoria()
    {
        return $this->belongsTo(Categoria::class);
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    public function tienda()
    {
        return $this->belongsTo(Tienda::class);
    }
}
