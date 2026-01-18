<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Categoria;
use Illuminate\Support\Str;

class CategoriaSeeder extends Seeder
{
    public function run(): void
    {
        // Categoría raíz
        $panales = Categoria::create([
            'nombre' => 'Pañales',
            'slug' => 'panales',
            'imagen' => 'panales/dodot-seco-talla-4.jpg',
            'parent_id' => null,
        ]);

        // Categorías raíz
$toallitas = Categoria::create([
    'nombre' => 'Toallitas',
    'slug' => 'toallitas',
    'imagen' => 'panales/dodot-seco-talla-4.jpg',
    'parent_id' => null,
]);

Categoria::create([
    'nombre' => 'Cremas para el cambio',
    'slug' => 'cremas-cambio',
    'imagen' => 'panales/dodot-seco-talla-4.jpg',
    'parent_id' => null,
]);

Categoria::create([
    'nombre' => 'Braguitas de aprendizaje',
    'slug' => 'braguitas-aprendizaje',
    'imagen' => 'panales/dodot-seco-talla-4.jpg',
    'parent_id' => null,
]);

Categoria::create([
    'nombre' => 'Pañales para agua',
    'slug' => 'panales-agua',
    'imagen' => 'panales/dodot-seco-talla-4.jpg',
    'parent_id' => null,
]);

Categoria::create([
    'nombre' => 'Pañales de tela',
    'slug' => 'panales-tela',
    'imagen' => 'panales/dodot-seco-talla-4.jpg',
    'parent_id' => null,
]);

Categoria::create([
    'nombre' => 'Almacenamiento de pañales',
    'slug' => 'almacenamiento-panales',
    'imagen' => 'panales/dodot-seco-talla-4.jpg',
    'parent_id' => null,
]);

Categoria::create([
    'nombre' => 'Accesorios para el cambio',
    'slug' => 'accesorios-cambio',
    'imagen' => 'panales/dodot-seco-talla-4.jpg',
    'parent_id' => null,
]);

Categoria::create([
    'nombre' => 'Suscripciones y packs',
    'slug' => 'suscripciones-packs',
    'imagen' => 'panales/dodot-seco-talla-4.jpg',
    'parent_id' => null,
]);

Categoria::create([
    'nombre' => 'Comparativas de marcas',
    'slug' => 'comparativas-marcas',
    'imagen' => 'panales/dodot-seco-talla-4.jpg',
    'parent_id' => null,
]);

Categoria::create([
    'nombre' => 'Pañales económicos',
    'slug' => 'panales-economicos',
    'imagen' => 'panales/dodot-seco-talla-4.jpg',
    'parent_id' => null,
]);

Categoria::create([
    'nombre' => 'Pañales premium',
    'slug' => 'panales-premium',
    'imagen' => 'panales/dodot-seco-talla-4.jpg',
    'parent_id' => null,
]);

Categoria::create([
    'nombre' => 'Ofertas destacadas',
    'slug' => 'ofertas-destacadas',
    'imagen' => 'panales/dodot-seco-talla-4.jpg',
    'parent_id' => null,
]);


        // Subcategoría: Talla 4
        $talla4 = Categoria::create([
            'nombre' => 'Talla 4',
            'slug' => 'talla-4',
            'imagen' => 'panales/dodot-seco-talla-4.jpg',
            'parent_id' => $panales->id,
        ]);

        // Subcategorías
$talla1 = Categoria::create([
    'nombre' => 'Talla 1',
    'slug' => 'talla-1',
    'imagen' => 'panales/dodot-seco-talla-4.jpg',
    'parent_id' => $panales->id,
]);

$talla2 = Categoria::create([
    'nombre' => 'Talla 2',
    'slug' => 'talla-2',
    'imagen' => 'panales/dodot-seco-talla-4.jpg',
    'parent_id' => $panales->id,
]);

$talla3 = Categoria::create([
    'nombre' => 'Talla 3',
    'slug' => 'talla-3',
    'imagen' => 'panales/dodot-seco-talla-4.jpg',
    'parent_id' => $panales->id,
]);

$talla5 = Categoria::create([
    'nombre' => 'Talla 5',
    'slug' => 'talla-5',
    'imagen' => 'panales/dodot-seco-talla-4.jpg',
    'parent_id' => $panales->id,
]);

$talla6 = Categoria::create([
    'nombre' => 'Talla 6',
    'slug' => 'talla-6',
    'imagen' => 'panales/dodot-seco-talla-4.jpg',
    'parent_id' => $panales->id,
]);

Categoria::create([
    'nombre' => 'Pañales de Noche',
    'slug' => 'panales-de-noche',
    'imagen' => 'panales/dodot-seco-talla-4.jpg',
    'parent_id' => $panales->id,
]);

Categoria::create([
    'nombre' => 'Pañales de Día',
    'slug' => 'panales-de-dia',
    'imagen' => 'panales/dodot-seco-talla-4.jpg',
    'parent_id' => $panales->id,
]);

Categoria::create([
    'nombre' => 'Pañales Ecológicos',
    'slug' => 'panales-ecologicos',
    'imagen' => 'panales/dodot-seco-talla-4.jpg',
    'parent_id' => $panales->id,
]);

Categoria::create([
    'nombre' => 'Pañales Lavables',
    'slug' => 'panales-lavables',
    'imagen' => 'panales/dodot-seco-talla-4.jpg',
    'parent_id' => $panales->id,
]);

Categoria::create([
    'nombre' => 'Pañales para Piscina',
    'slug' => 'panales-para-piscina',
    'imagen' => 'panales/dodot-seco-talla-4.jpg',
    'parent_id' => $panales->id,
]);

Categoria::create([
    'nombre' => 'Pañales Prematuros',
    'slug' => 'panales-prematuros',
    'imagen' => 'panales/dodot-seco-talla-4.jpg',
    'parent_id' => $panales->id,
]);


        // Sub-subcategoría: Ecológicos
        $ecologicos = Categoria::create([
            'nombre' => 'Ecológicos',
            'slug' => 'ecologicos',
            'imagen' => 'panales/dodot-seco-talla-4.jpg',
            'parent_id' => $talla4->id,
        ]);

        // Añadir especificaciones internas a la categoría Ecológicos
        $filtros = [];
        $baseTimestamp = (int)(microtime(true) * 1000); // Similar a Date.now() en JS
        
        for ($i = 1; $i <= 10; $i++) {
            // Generar ID único similar al formato JavaScript: id_timestamp_random
            $idPrincipal = 'id_' . ($baseTimestamp + $i) . '_' . bin2hex(random_bytes(4));
            $subprincipales = [];
            
            for ($j = 1; $j <= 10; $j++) {
                // Generar ID único para cada sublínea
                $idSublinea = 'id_' . ($baseTimestamp + ($i * 100) + $j) . '_' . bin2hex(random_bytes(4));
                $textoSublinea = 'Sublínea ' . $j;
                $subprincipales[] = [
                    'id' => $idSublinea,
                    'texto' => $textoSublinea,
                    'slug' => Str::slug($textoSublinea),
                ];
            }
            
            $filtros[] = [
                'id' => $idPrincipal,
                'texto' => 'Opción ' . $i,
                'importante' => true,
                'subprincipales' => $subprincipales,
            ];
        }
        
        $ecologicos->update([
            'especificaciones_internas' => [
                'filtros' => $filtros,
            ],
        ]);

Categoria::create([
    'nombre' => 'Hipoalergénicos',
    'slug' => 'hipoalergenicos',
    'imagen' => 'panales/dodot-seco-talla-4.jpg',
    'parent_id' => $talla4->id,
]);

Categoria::create([
    'nombre' => 'Recién nacido',
    'slug' => 'recien-nacido',
    'imagen' => 'panales/dodot-seco-talla-4.jpg',
    'parent_id' => $talla4->id,
]);

Categoria::create([
    'nombre' => 'Extra Absorción',
    'slug' => 'extra-absorcion',
    'imagen' => 'panales/dodot-seco-talla-4.jpg',
    'parent_id' => $talla4->id,
]);

Categoria::create([
    'nombre' => 'Pañales Nocturnos',
    'slug' => 'panales-nocturnos',
    'imagen' => 'panales/dodot-seco-talla-4.jpg',
    'parent_id' => $talla4->id,
]);

Categoria::create([
    'nombre' => 'Con Indicador de Humedad',
    'slug' => 'indicador-humedad',
    'imagen' => 'panales/dodot-seco-talla-4.jpg',
    'parent_id' => $talla4->id,
]);

Categoria::create([
    'nombre' => 'Diseños Infantiles',
    'slug' => 'disenos-infantiles',
    'imagen' => 'panales/dodot-seco-talla-4.jpg',
    'parent_id' => $talla4->id,
]);

Categoria::create([
    'nombre' => 'Ultrafinos',
    'slug' => 'ultrafinos',
    'imagen' => 'panales/dodot-seco-talla-4.jpg',
    'parent_id' => $talla4->id,
]);

Categoria::create([
    'nombre' => 'De Tela Lavables',
    'slug' => 'tela-lavables',
    'imagen' => 'panales/dodot-seco-talla-4.jpg',
    'parent_id' => $talla4->id,
]);

Categoria::create([
    'nombre' => 'Para Piscina',
    'slug' => 'para-piscina',
    'imagen' => 'panales/dodot-seco-talla-4.jpg',
    'parent_id' => $talla4->id,
]);

Categoria::create([
    'nombre' => 'Pack Ahorro',
    'slug' => 'pack-ahorro',
    'imagen' => 'panales/dodot-seco-talla-4.jpg',
    'parent_id' => $talla4->id,
]);

Categoria::create([
    'nombre' => 'Edición Limitada',
    'slug' => 'edicion-limitada',
    'imagen' => 'panales/dodot-seco-talla-4.jpg',
    'parent_id' => $talla4->id,
]);

        // Categoría raíz: Tenis
        $tenis = Categoria::create([
            'nombre' => 'Tenis',
            'slug' => 'tenis',
            'imagen' => 'panales/1012B775_700_SR_RT_GLB.webp',
            'parent_id' => null,
        ]);

        // Añadir especificaciones internas a la categoría Tenis
        $filtrosTenis = [];
        $baseTimestampTenis = (int)(microtime(true) * 1000);
        
        // Filtro 1: Marca
        $idMarca = 'id_' . ($baseTimestampTenis + 1) . '_' . bin2hex(random_bytes(4));
        $marcas = ['Nike', 'Adidas', 'Puma', 'Reebok', 'New Balance', 'Asics', 'Converse', 'Vans'];
        $subprincipalesMarca = [];
        foreach ($marcas as $index => $marca) {
            $idSublinea = 'id_' . ($baseTimestampTenis + 100 + $index) . '_' . bin2hex(random_bytes(4));
            $subprincipalesMarca[] = [
                'id' => $idSublinea,
                'texto' => $marca,
                'slug' => Str::slug($marca),
            ];
        }
        $filtrosTenis[] = [
            'id' => $idMarca,
            'texto' => 'Marca',
            'importante' => true,
            'subprincipales' => $subprincipalesMarca,
        ];

        // Filtro 2: Tipo
        $idTipo = 'id_' . ($baseTimestampTenis + 2) . '_' . bin2hex(random_bytes(4));
        $tipos = ['Running', 'Basketball', 'Casual', 'Trekking', 'Fútbol', 'Tenis', 'Cross Training', 'Skateboarding'];
        $subprincipalesTipo = [];
        foreach ($tipos as $index => $tipo) {
            $idSublinea = 'id_' . ($baseTimestampTenis + 200 + $index) . '_' . bin2hex(random_bytes(4));
            $subprincipalesTipo[] = [
                'id' => $idSublinea,
                'texto' => $tipo,
                'slug' => Str::slug($tipo),
            ];
        }
        $filtrosTenis[] = [
            'id' => $idTipo,
            'texto' => 'Tipo',
            'importante' => true,
            'subprincipales' => $subprincipalesTipo,
        ];

        // Filtro 3: Color (con imágenes)
        $idColor = 'id_' . ($baseTimestampTenis + 3) . '_' . bin2hex(random_bytes(4));
        $colores = [
            ['nombre' => 'Blanco', 'imagen' => 'panales/1012B775_101_SR_RT_GLB.webp'],
            ['nombre' => 'Negro', 'imagen' => 'panales/1012B775_003_SR_RT_GLB.jpeg'],
            ['nombre' => 'Morado', 'imagen' => 'panales/1012B775_502_SR_RT_GLB.jpeg'],
        ];
        $subprincipalesColor = [];
        foreach ($colores as $index => $color) {
            $idSublinea = 'id_' . ($baseTimestampTenis + 300 + $index) . '_' . bin2hex(random_bytes(4));
            $subprincipalesColor[] = [
                'id' => $idSublinea,
                'texto' => $color['nombre'],
                'slug' => Str::slug($color['nombre']),
                'imagen' => $color['imagen'],
            ];
        }
        $filtrosTenis[] = [
            'id' => $idColor,
            'texto' => 'Color',
            'importante' => true,
            'subprincipales' => $subprincipalesColor,
        ];

        // Filtro 4: Talla
        $idTalla = 'id_' . ($baseTimestampTenis + 4) . '_' . bin2hex(random_bytes(4));
        $tallas = ['36', '37', '38', '39', '40', '41', '42', '43', '44', '45'];
        $subprincipalesTalla = [];
        foreach ($tallas as $index => $talla) {
            $idSublinea = 'id_' . ($baseTimestampTenis + 400 + $index) . '_' . bin2hex(random_bytes(4));
            $subprincipalesTalla[] = [
                'id' => $idSublinea,
                'texto' => $talla,
                'slug' => Str::slug($talla),
            ];
        }
        $filtrosTenis[] = [
            'id' => $idTalla,
            'texto' => 'Talla',
            'importante' => true,
            'subprincipales' => $subprincipalesTalla,
        ];
        
        $tenis->update([
            'especificaciones_internas' => [
                'filtros' => $filtrosTenis,
            ],
        ]);

    }
}
