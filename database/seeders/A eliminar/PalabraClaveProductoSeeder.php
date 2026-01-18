<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Producto;
use App\Models\PalabraClaveProducto;
use Illuminate\Support\Str;

class PalabraClaveProductoSeeder extends Seeder
{
    public function run(): void
    {
        $productos = Producto::all();

        foreach ($productos as $producto) {
            $palabras = [$producto->marca, $producto->modelo, $producto->talla];

            foreach ($palabras as $palabra) {
                if (empty($palabra)) continue;

                $codigo = substr(base_convert(crc32(Str::ascii(strtolower(trim($palabra)))), 10, 36), 0, 6);

                PalabraClaveProducto::firstOrCreate(
                    ['codigo' => $codigo],
                    [
                        'producto_id' => $producto->id,
                        'palabra' => $palabra,
                        'activa' => 'si',
                    ]
                );
            }
        }
    }
}
