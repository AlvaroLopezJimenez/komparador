<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Producto;
use App\Models\Categoria;
use Carbon\Carbon;

class ProductoSeeder extends Seeder
{
    public function run(): void
    {
        $productos = [
            [
                'nombre' => 'Dodot Bebé Seco',
                'marca' => 'Dodot',
                'modelo' => 'Bebe seco',
                'slug' => 'dodot-bebe-seco-talla-4',
                'titulo' => 'Dodot Bebé Seco Talla 4 – Comparador de pañales',
                'subtitulo' => 'Comprar Dodot Bebé Seco Talla 4 al mejor precio',
                'unidadDeMedida' => 'unidad',
            ],
            [
                'nombre' => 'Chelino Nature',
                'marca' => 'Chelino',
                'modelo' => 'Nature Comfort',
                'slug' => 'chelino-nature-talla-4',
                'titulo' => 'Chelino Nature Talla 4 – Comparador de pañales',
                'subtitulo' => 'Comprar Chelino Nature Talla 4 al mejor precio',
                'unidadDeMedida' => 'unidad',
            ],
            [
                'nombre' => 'Pingo Ecológico',
                'marca' => 'Pingo',
                'modelo' => 'Eco Pack',
                'slug' => 'pingo-ecologico-talla-4',
                'titulo' => 'Pingo Ecológico Talla 4 – Comparador de pañales',
                'subtitulo' => 'Comprar Pingo Ecológico Talla 4 al mejor precio',
                'unidadDeMedida' => 'unidad',
            ],
            [
                'nombre' => 'PruebaMilunidades',
                'marca' => 'Pingo',
                'modelo' => 'Eco Pack',
                'slug' => 'prueba-milunidades-talla-4',
                'titulo' => 'Prueba Milunidades Talla 4 – Comparador de pañales',
                'subtitulo' => 'Comprar Prueba Milunidades Talla 4 al mejor precio',
                'unidadDeMedida' => 'unidadMilesima',
            ],
            [
                'nombre' => 'PruebaKilo',
                'marca' => 'Pingo',
                'modelo' => 'Eco Pack',
                'slug' => 'prueba-kilo-talla-4',
                'titulo' => 'Prueba Kilo Talla 4 – Comparador de pañales',
                'subtitulo' => 'Comprar Prueba Kilo Talla 4 al mejor precio',
                'unidadDeMedida' => 'kilos',
            ],
            [
                'nombre' => 'PruebaLitro',
                'marca' => 'Pingo',
                'modelo' => 'Eco Pack',
                'slug' => 'prueba-litro-talla-4',
                'titulo' => 'Prueba Litro Talla 4 – Comparador de pañales',
                'subtitulo' => 'Comprar Prueba Litro Talla 4 al mejor precio',
                'unidadDeMedida' => 'litros',
            ],
            [
                'nombre' => 'PruebaUnicaUnidad',
                'marca' => 'Pingo',
                'modelo' => 'Eco Pack',
                'slug' => 'prueba-unica-unidad-talla-4',
                'titulo' => 'Prueba Unica Unidad Talla 4 – Comparador de pañales',
                'subtitulo' => 'Comprar Prueba Unica Unidad Talla 4 al mejor precio',
                'unidadDeMedida' => 'unidadUnica',
            ],


        ];

        $subsubCategoria = Categoria::where('slug', 'ecologicos')->firstOrFail();
        
        // Obtener las especificaciones internas de la categoría
        $especificacionesCategoria = $subsubCategoria->especificaciones_internas;
        $filtrosCategoria = $especificacionesCategoria['filtros'] ?? [];

        // Crear arrays con la misma imagen repetida 10 veces
        $imagenRepetida = 'panales/dodot-seco-talla-4.jpg';
        $imagenesGrandes = array_fill(0, 10, $imagenRepetida);
        $imagenesPequenas = array_fill(0, 10, $imagenRepetida);

        foreach ($productos as $producto) {
            // Crear especificaciones elegidas aleatoriamente para este producto
            $especificacionesElegidas = [];
            
            if (!empty($filtrosCategoria)) {
                $esPrimerFiltro = true; // Contador para identificar el primer filtro
                
                foreach ($filtrosCategoria as $filtro) {
                    $idPrincipal = $filtro['id'];
                    $subprincipales = $filtro['subprincipales'] ?? [];
                    
                    // Marcar todas las opciones, pero no todas las sublíneas
                    $sublineasElegidas = [];
                    
                    foreach ($subprincipales as $sublinea) {
                        $idSublinea = $sublinea['id'];
                        
                        // Aleatoriamente decidir si incluir esta sublínea (70% de probabilidad)
                        $incluir = rand(1, 100) <= 70;
                        
                        if ($incluir) {
                            // Aleatoriamente marcar "mostrar" (60% de probabilidad)
                            $mostrar = rand(1, 100) <= 60;
                            
                            // Si se marca "mostrar", también se marca "oferta"
                            // Si no se marca "mostrar", aleatoriamente marcar "oferta" (40% de probabilidad)
                            $oferta = $mostrar ? true : (rand(1, 100) <= 40);
                            
                            $sublineaItem = [
                                'id' => $idSublinea,
                                'm' => $mostrar ? 1 : 0,
                                'o' => $oferta ? 1 : 0,
                            ];
                            
                            // Solo añadir imágenes a las sublíneas del primer filtro que tengan "mostrar" marcado
                            if ($mostrar && $esPrimerFiltro) {
                                $sublineaItem['img'] = $imagenesPequenas;
                            }
                            
                            $sublineasElegidas[] = $sublineaItem;
                        }
                    }
                    
                    // Si hay al menos una sublínea elegida, añadirla
                    if (!empty($sublineasElegidas)) {
                        $especificacionesElegidas[$idPrincipal] = $sublineasElegidas;
                    }
                    
                    // Marcar que ya no es el primer filtro
                    $esPrimerFiltro = false;
                }
            }
            
            // Generar campo rebajado aleatoriamente (50% de probabilidad de tener valor entre 5 y 50)
            $rebajado = rand(1, 100) <= 50 ? rand(5, 50) : null;
            
            Producto::create([
                ...$producto,
                'talla' => 'Talla 4',
                'precio' => 0.16,
                'rebajado' => $rebajado,
                'imagen_grande' => $imagenesGrandes,
                'imagen_pequena' => $imagenesPequenas,
                'descripcion_corta' => 'Pañales diseñados para mantener seco al bebé durante más tiempo. Talla 4 (9-14kg).',
                'descripcion_larga' => 'Estos pañales proporcionan máxima comodidad y absorción durante toda la noche.',
                'caracteristicas' => [
                    'Absorción instantánea hasta 12 horas',
                    'Indicador de humedad que cambia de color',
                    'Laterales elásticos y suaves',
                    'Dermatológicamente testado, sin perfume',
                    'Diseño fino y ergonómico'
                ],
                'pros' => [
                    'Buena absorción general',
                    'Cómodos y suaves para el bebé',
                    'Disponibles en muchos supermercados',
                    'Buena relación calidad-precio'
                ],
                'contras' => [
                    'Sin versión ecológica',
                    'Algunas marcas menos conocidas',
                    'No siempre hay tallas en stock'
                ],
                'faq' => [
                    ['pregunta' => '¿Cuántos pañales incluye cada paquete?', 'respuesta' => 'Desde 150 hasta 180 unidades.'],
                    ['pregunta' => '¿Es adecuado para pieles sensibles?', 'respuesta' => 'Sí, no contiene perfumes ni alérgenos.'],
                    ['pregunta' => '¿Se puede usar por la noche?', 'respuesta' => 'Sí, mantiene seco al bebé durante la noche.']
                ],
                'keys_relacionados' => [$producto['marca'], $producto['modelo'], 'Talla 4'],
                'id_categoria_productos_relacionados' => 1,
                'categoria_id' => $subsubCategoria->id,
                'categoria_id_especificaciones_internas' => $subsubCategoria->id,
                'categoria_especificaciones_internas_elegidas' => $especificacionesElegidas,
                'meta_titulo' => $producto['titulo'],
                'meta_description' => 'Compara precios y características del ' . $producto['nombre'] . '. Encuentra las mejores ofertas online.',
                'obsoleto' => 'no',
                'mostrar' => 'si',
                'anotaciones_internas' => 'Producto con alta rotación. Revisar cada 2 semanas.',
                'aviso' => Carbon::tomorrow()->setTime(0, 1),
            ]);
        }

        // Crear productos de tenis
        $categoriaTenis = Categoria::where('slug', 'tenis')->firstOrFail();
        $especificacionesTenis = $categoriaTenis->especificaciones_internas;
        $filtrosTenis = $especificacionesTenis['filtros'] ?? [];

        // Obtener IDs de marca, tipo, talla y color
        $idMarca = null;
        $idTipo = null;
        $idTalla = null;
        $idColor = null;
        $subprincipalesMarca = [];
        $subprincipalesTipo = [];
        $subprincipalesTalla = [];
        $subprincipalesColor = [];
        
        foreach ($filtrosTenis as $filtro) {
            if ($filtro['texto'] === 'Marca') {
                $idMarca = $filtro['id'];
                $subprincipalesMarca = $filtro['subprincipales'] ?? [];
            }
            if ($filtro['texto'] === 'Tipo') {
                $idTipo = $filtro['id'];
                $subprincipalesTipo = $filtro['subprincipales'] ?? [];
            }
            if ($filtro['texto'] === 'Talla') {
                $idTalla = $filtro['id'];
                $subprincipalesTalla = $filtro['subprincipales'] ?? [];
            }
            if ($filtro['texto'] === 'Color') {
                $idColor = $filtro['id'];
                $subprincipalesColor = $filtro['subprincipales'] ?? [];
            }
        }

        // Imagen única para todos los productos de tenis
        $imagenTenis = 'panales/1012B775_700_SR_RT_GLB.webp';
        $imagenesGrandesTenis = array_fill(0, 10, $imagenTenis);
        $imagenesPequenasTenis = array_fill(0, 10, $imagenTenis);

        // Crear 20 productos de tenis
        $marcas = ['Nike', 'Adidas', 'Puma', 'Reebok', 'New Balance', 'Asics', 'Converse', 'Vans'];
        $tipos = ['Running', 'Basketball', 'Casual', 'Trekking', 'Fútbol', 'Tenis', 'Cross Training', 'Skateboarding'];
        
        for ($i = 1; $i <= 20; $i++) {
            // Seleccionar marca y tipo aleatoriamente
            $marcaSeleccionada = $marcas[($i - 1) % count($marcas)];
            $tipoSeleccionado = $tipos[($i - 1) % count($tipos)];
            
            // Encontrar los IDs correspondientes
            $idMarcaSeleccionada = null;
            $idTipoSeleccionado = null;
            
            foreach ($subprincipalesMarca as $sub) {
                if ($sub['texto'] === $marcaSeleccionada) {
                    $idMarcaSeleccionada = $sub['id'];
                    break;
                }
            }
            
            foreach ($subprincipalesTipo as $sub) {
                if ($sub['texto'] === $tipoSeleccionado) {
                    $idTipoSeleccionado = $sub['id'];
                    break;
                }
            }
            
            // Crear especificaciones elegidas
            // Marca y tipo: solo elegir (sin m=1, o=1 porque es de categoría a producto)
            $especificacionesElegidasTenis = [];
            
            if ($idMarca && $idMarcaSeleccionada) {
                $especificacionesElegidasTenis[$idMarca] = [[
                    'id' => $idMarcaSeleccionada,
                ]];
            }
            
            if ($idTipo && $idTipoSeleccionado) {
                $especificacionesElegidasTenis[$idTipo] = [[
                    'id' => $idTipoSeleccionado,
                ]];
            }
            
            // Tallas: todas marcadas como mostrar (m=1) y oferta (o=1) para que las ofertas puedan elegir
            if ($idTalla && !empty($subprincipalesTalla)) {
                $tallasElegidas = [];
                foreach ($subprincipalesTalla as $talla) {
                    $tallasElegidas[] = [
                        'id' => $talla['id'],
                        'm' => 1,
                        'o' => 1,
                    ];
                }
                $especificacionesElegidasTenis[$idTalla] = $tallasElegidas;
            }
            
            // Colores: todos marcados como mostrar (m=1) y oferta (o=1) con sus imágenes
            if ($idColor && !empty($subprincipalesColor)) {
                $coloresElegidos = [];
                foreach ($subprincipalesColor as $color) {
                    $imagenColor = array_fill(0, 10, $color['imagen'] ?? '');
                    $coloresElegidos[] = [
                        'id' => $color['id'],
                        'm' => 1,
                        'o' => 1,
                        'img' => $imagenColor,
                    ];
                }
                $especificacionesElegidasTenis[$idColor] = $coloresElegidos;
            }
            
            // Añadir _columnas con Color y Talla (solo para unidadUnica)
            $columnas = [];
            if ($idColor) {
                $columnas[] = $idColor; // El ID de la línea principal de Color
            }
            if ($idTalla) {
                $columnas[] = $idTalla; // El ID de la línea principal de Talla
            }
            if (!empty($columnas)) {
                $especificacionesElegidasTenis['_columnas'] = $columnas;
            }
            
            $nombreProducto = $marcaSeleccionada . ' ' . $tipoSeleccionado;
            $slugProducto = 'tenis-' . strtolower(str_replace(' ', '-', $marcaSeleccionada)) . '-' . strtolower(str_replace(' ', '-', $tipoSeleccionado)) . '-' . $i;
            
            // Precio diferente para cada producto (entre 39.99 y 79.99)
            $precioBase = 39.99 + (($i - 1) * 2);
            
            // Generar campo rebajado aleatoriamente (50% de probabilidad de tener valor entre 5 y 50)
            $rebajadoTenis = rand(1, 100) <= 50 ? rand(5, 50) : null;
            
            Producto::create([
                'nombre' => $nombreProducto,
                'marca' => $marcaSeleccionada,
                'modelo' => $tipoSeleccionado,
                'slug' => $slugProducto,
                'titulo' => $nombreProducto . ' – Comparador de tenis',
                'subtitulo' => 'Comprar ' . $nombreProducto . ' al mejor precio',
                'unidadDeMedida' => 'unidadUnica',
                'talla' => null,
                'precio' => $precioBase,
                'rebajado' => $rebajadoTenis,
                'imagen_grande' => $imagenesGrandesTenis,
                'imagen_pequena' => $imagenesPequenasTenis,
                'descripcion_corta' => 'Zapatillas ' . $tipoSeleccionado . ' de la marca ' . $marcaSeleccionada . '. Diseño moderno y cómodo.',
                'descripcion_larga' => 'Estas zapatillas ' . $tipoSeleccionado . ' de ' . $marcaSeleccionada . ' ofrecen máximo confort y rendimiento. Perfectas para uso diario y deportivo.',
                'caracteristicas' => [
                    'Suela de alta calidad',
                    'Material transpirable',
                    'Diseño ergonómico',
                    'Amortiguación superior',
                    'Duradero y resistente'
                ],
                'pros' => [
                    'Excelente calidad',
                    'Cómodas para uso prolongado',
                    'Diseño atractivo',
                    'Buena relación calidad-precio'
                ],
                'contras' => [
                    'Precio elevado en algunas tallas',
                    'Stock limitado en colores populares'
                ],
                'faq' => [
                    ['pregunta' => '¿Qué tallas están disponibles?', 'respuesta' => 'Disponible en tallas del 36 al 45.'],
                    ['pregunta' => '¿Son adecuadas para correr?', 'respuesta' => 'Sí, están diseñadas para ' . strtolower($tipoSeleccionado) . '.'],
                    ['pregunta' => '¿Qué colores hay disponibles?', 'respuesta' => 'Disponible en blanco, negro y morado.']
                ],
                'keys_relacionados' => [$marcaSeleccionada, $tipoSeleccionado, 'Tenis'],
                'id_categoria_productos_relacionados' => 1,
                'categoria_id' => $categoriaTenis->id,
                'categoria_id_especificaciones_internas' => $categoriaTenis->id,
                'categoria_especificaciones_internas_elegidas' => $especificacionesElegidasTenis,
                'meta_titulo' => $nombreProducto . ' – Comparador de tenis',
                'meta_description' => 'Compara precios y características de ' . $nombreProducto . '. Encuentra las mejores ofertas online.',
                'obsoleto' => 'no',
                'mostrar' => 'si',
                'anotaciones_internas' => 'Producto de tenis. Revisar cada semana.',
                'aviso' => Carbon::tomorrow()->setTime(0, 1 + $i),
            ]);
        }
    }
}
