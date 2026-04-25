<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UrlDescartada extends Model
{
    protected $table = 'urls_descartadas';

    public $timestamps = true;

    protected $fillable = [
        'url',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
