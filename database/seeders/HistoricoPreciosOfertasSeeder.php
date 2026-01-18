<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\HistoricoPrecioOferta;
use App\Models\OfertaProducto;
use Carbon\Carbon;

class HistoricoPreciosOfertasSeeder extends Seeder
{
    public function run(): void
    {
        $ofertas = OfertaProducto::all();
        $hoy = Carbon::today();

        foreach ($ofertas as $oferta) {
            $precioBase = $oferta->precio_unidad;

            // Generar solo 15 registros por oferta
            $cantidadRegistros = 15;
            
            for ($i = 0; $i < $cantidadRegistros; $i++) {
                $fecha = $hoy->copy()->subDays($i);
                $variacion = rand(-10, 10) / 100; // ±0.10€
                $precioDia = round(max(0.01, $precioBase + $variacion), 4); // mínimo 0.01

                HistoricoPrecioOferta::updateOrCreate(
                    [
                        'oferta_producto_id' => $oferta->id,
                        'fecha' => $fecha,
                    ],
                    [
                        'precio_unidad' => $precioDia,
                    ]
                );
            }
        }
    }
}
