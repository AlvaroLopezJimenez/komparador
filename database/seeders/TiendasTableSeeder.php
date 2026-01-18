<?php

namespace Database\Seeders;


use Illuminate\Database\Seeder;
use App\Models\Tienda;
use Carbon\Carbon;

class TiendasTableSeeder extends Seeder
{
    public function run(): void
    {
        $tiendas = [
            [
                'nombre' => 'Carrefour',
                'envio_gratis' => 'Gratis > 40€',
                'envio_normal' => '2,99 € < 40€',
                'url' => 'https://www.carrefour.es/',
                'url_imagen' => 'tiendas/carrefour.png',
                'opiniones' => 0,
                'puntuacion' => 0,
                'url_opiniones' => 'google.com',
                'anotaciones_internas' => 'Texto interno de prueba',
                'aviso' => Carbon::tomorrow()->setTime(0, 1),
            ],
            [
                'nombre' => 'Primor',
                'envio_gratis' => 'Gratis > 50€',
                'envio_normal' => '3,95 € < 50€',
                'url' => 'https://www.primor.es/',
                'url_imagen' => 'tiendas/primor.png',
                'opiniones' => 0,
                'puntuacion' => 0,
                'url_opiniones' => 'google.com',
                'anotaciones_internas' => 'Texto interno de prueba',
                'aviso' => Carbon::tomorrow()->setTime(0, 1),
            ],
            [
                'nombre' => 'Miravia',
                'envio_gratis' => 'Gratis > 50€',
                'envio_normal' => '3,95 € < 50€',
                'url' => 'https://www.miravia.com/',
                'url_imagen' => 'tiendas/miravia.png',
                'opiniones' => 0,
                'puntuacion' => 0,
                'url_opiniones' => 'google.com',
                'anotaciones_internas' => 'Texto interno de prueba',
                'aviso' => Carbon::tomorrow()->setTime(0, 1),
            ],
            // Añade más tiendas si quieres
        ];

        foreach ($tiendas as $tienda) {
            Tienda::create($tienda);
        }
    }
}
