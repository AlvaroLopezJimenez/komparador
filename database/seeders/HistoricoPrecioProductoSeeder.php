<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Producto;
use App\Models\HistoricoPrecioProducto;
use Illuminate\Support\Carbon;

class HistoricoPrecioProductoSeeder extends Seeder
{
    public function run(): void
    {
        $producto = Producto::where('slug', 'dodot-bebe-seco-talla-4')->firstOrFail();

        $hoy = Carbon::today();
        $precioBase = 0.16;

        for ($i = 89; $i >= 0; $i--) {
            $fecha = $hoy->copy()->subDays($i);
            $variacion = rand(-2, 2) / 100; // -0.02 a +0.02
            $precio = round(max(0.14, $precioBase + $variacion), 2);

            HistoricoPrecioProducto::updateOrCreate(
                [
                    'producto_id' => $producto->id,
                    'fecha' => $fecha,
                ],
                [
                    'precio_minimo' => $precio,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
