<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ajuste extends Model
{
    protected $table = 'ajustes';

    protected $fillable = [
        'clave',
        'valor',
        'grupo',
        'descripcion'
    ];

    /**
     * Obtener el valor de un ajuste.
     */
    public static function getVal(string $clave, $default = null)
    {
        $ajuste = self::where('clave', $clave)->first();
        return $ajuste ? $ajuste->valor : $default;
    }

    /**
     * Guardar o actualizar el valor de un ajuste.
     */
    public static function setVal(string $clave, $valor, string $grupo = 'general')
    {
        return self::updateOrCreate(
            ['clave' => $clave],
            ['valor' => $valor, 'grupo' => $grupo]
        );
    }
}
