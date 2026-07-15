<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CsvOferta extends Model
{
    protected $table = 'csv_ofertas';

    protected $fillable = [
        'tienda_id',
        'url',
        'url_lookup',
        'nombre',
        'ean',
        'isbn',
        'upc',
        'mpn',
        'gtin',
        'precio',
        'envio',
        'stock',
        'aniadida_neo',
    ];

    protected $casts = [
        'precio' => 'decimal:2',
        'envio' => 'decimal:2',
        'stock' => 'integer',
    ];

    public function tienda()
    {
        return $this->belongsTo(Tienda::class);
    }
}
