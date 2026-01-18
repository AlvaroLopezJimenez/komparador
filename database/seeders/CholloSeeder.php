<?php

namespace Database\Seeders;

use App\Models\Categoria;
use App\Models\Chollo;
use App\Models\Producto;
use App\Models\Tienda;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CholloSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categoria = Categoria::first();
        if (!$categoria) {
            $categoria = Categoria::create([
                'nombre' => 'Pañales',
                'slug' => 'panales',
            ]);
        }

        $producto = Producto::first();
        if (!$producto) {
            $producto = Producto::create([
                'nombre' => 'Pañales Eco Talla 3',
                'marca' => 'EcoSoft',
                'modelo' => 'Comfort',
                'talla' => '3',
                'precio' => 19.99,
                'unidadDeMedida' => 'unidad',
                'imagen_grande' => null,
                'imagen_pequena' => null,
                'titulo' => 'Pañales EcoSoft Talla 3',
                'descripcion_corta' => 'Pañales ecológicos con alta absorción.',
                'descripcion_larga' => 'Pañales ecológicos con alta absorción y materiales sostenibles.',
                'slug' => 'panales-ecosoft-talla-3-' . Str::random(5),
                'categoria_id' => $categoria->id,
                'meta_titulo' => 'Pañales EcoSoft Talla 3',
                'meta_description' => 'Compra los pañales EcoSoft Talla 3 al mejor precio.',
                'mostrar' => 'si',
            ]);
        }

        $tienda = Tienda::first();
        if (!$tienda) {
            $tienda = Tienda::create([
                'nombre' => 'BabyStore',
                'envio_gratis' => 'Desde 30€',
                'envio_normal' => '3.95€',
                'url' => 'https://babystore.example.com',
                'mostrar_tienda' => 'si',
                'scrapear' => 'no',
            ]);
        }

        $primerChollo = [
            'producto_id' => $producto->id,
            'tienda_id' => $tienda->id,
            'categoria_id' => $producto->categoria_id ?? $categoria->id,
            'tipo' => 'producto',
            'titulo' => 'Pack 2x Pañales EcoSoft Talla 3',
            'slug' => 'pack-2x-panales-ecosoft-talla-3',
            'imagen_grande' => 'panales/dodot-seco-talla-4.jpg',
            'imagen_pequena' => 'panales/dodot-seco-talla-4.jpg',
            'unidades' => 96,
            'precio_antiguo' => 39.99,
            'precio_nuevo' => '29,99',
            'precio_unidad' => 0.3124,
            'descuentos' => 'cupon;ECO10;10',
            'gastos_envio' => 'Gratis',
            'descripcion' => 'Pack de dos bolsas de pañales ecológicos EcoSoft Talla 3 con cupón de descuento.',
            'descripcion_interna' => 'prueba prueba',
            'url' => 'https://babystore.example.com/chollo-panales-ecosoft-pack',
            'finalizada' => 'no',
            'mostrar' => 'si',
            'fecha_inicio' => Carbon::now()->subDay(),
            'fecha_final' => null,
            'comprobada' => Carbon::now(),
            'frecuencia_comprobacion_min' => 360,
            'meta_titulo' => 'Pack pañales EcoSoft Talla 3 - Oferta',
            'meta_descripcion' => 'Aprovecha este pack de pañales EcoSoft Talla 3 con envío gratis y cupón.',
            'anotaciones_internas' => 'Añadido manualmente por seeder.',
            'clicks' => 0,
            'me_gusta' => 0,
            'no_me_gusta' => 0,
        ];

        $segundoChollo = [
            'producto_id' => $producto->id,
            'tienda_id' => $tienda->id,
            'categoria_id' => $producto->categoria_id ?? $categoria->id,
            'tipo' => 'producto',
            'titulo' => 'Toallitas EcoSoft 6x80 uds con envío gratis',
            'slug' => 'toallitas-ecosoft-6x80-uds-con-envio-gratis',
            'imagen_grande' => 'panales/dodot-seco-talla-4.jpg',
            'imagen_pequena' => 'panales/dodot-seco-talla-4.jpg',
            'unidades' => 480,
            'precio_antiguo' => 24.99,
            'precio_nuevo' => '19,50',
            'precio_unidad' => 0.0406,
            'descuentos' => null,
            'gastos_envio' => 'Gratis a partir de 25€',
            'descripcion' => 'Pack ahorro de toallitas EcoSoft con envío gratis, ideal para completar pedido.',
            'descripcion_interna' => 'prueba prueba',
            'url' => 'https://babystore.example.com/chollo-toallitas-ecosoft-pack',
            'finalizada' => 'no',
            'mostrar' => 'si',
            'fecha_inicio' => Carbon::now()->subHours(12),
            'fecha_final' => null,
            'comprobada' => Carbon::now(),
            'frecuencia_comprobacion_min' => 720,
            'meta_titulo' => 'Toallitas EcoSoft Pack 6x80',
            'meta_descripcion' => 'Pack de 6 paquetes de toallitas EcoSoft con envío gratis disponible.',
            'anotaciones_internas' => 'Probar integración con avisos automáticos.',
            'clicks' => 0,
            'me_gusta' => 0,
            'no_me_gusta' => 0,
        ];

        Chollo::updateOrCreate(
            ['url' => $primerChollo['url']],
            $primerChollo
        );

        Chollo::updateOrCreate(
            ['url' => $segundoChollo['url']],
            $segundoChollo
        );
    }
}

































