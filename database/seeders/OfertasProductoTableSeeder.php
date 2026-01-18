<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\OfertaProducto;
use App\Models\Producto;
use App\Models\Tienda;
use Carbon\Carbon;

class OfertasProductoTableSeeder extends Seeder
{
    public function run()
    {
        // Obtener tiendas
        $carrefour = Tienda::where('nombre', 'Carrefour')->first();
        $primor = Tienda::where('nombre', 'Primor')->first();
        $miravia = Tienda::where('nombre', 'Miravia')->first();

        // Obtener todos los productos del ProductoSeeder
        $slugsProductos = [
            'dodot-bebe-seco-talla-4',
            'chelino-nature-talla-4',
            'pingo-ecologico-talla-4',
            'prueba-milunidades-talla-4',
            'prueba-kilo-talla-4',
            'prueba-litro-talla-4',
            'prueba-unica-unidad-talla-4',
        ];

        // Datos de ofertas base para variar
        $ofertasBase = [
            [
                'tienda' => $carrefour,
                'unidades' => 150,
                'precio_total' => 21.00,
                'precio_unidad' => 0.14,
                'url' => 'https://www.carrefour.es/supermercado/panales-talla-4-150-ud',
                'frecuencia' => 1440,
                'anotaciones' => 'Oferta estándar Carrefour',
            ],
            [
                'tienda' => $primor,
                'unidades' => 176,
                'precio_total' => 23.50,
                'precio_unidad' => 0.1335,
                'url' => 'https://www.primor.eu/es_es/panales-talla-4-176',
                'frecuencia' => 720,
                'anotaciones' => 'Oferta estándar Primor',
            ],
            [
                'tienda' => $miravia,
                'unidades' => 132,
                'precio_total' => 19.99,
                'precio_unidad' => 0.1514,
                'url' => 'https://www.miravia.com/panales-talla-4-132',
                'frecuencia' => 1440,
                'anotaciones' => 'Oferta estándar Miravia',
            ],
            [
                'tienda' => $carrefour,
                'unidades' => 120,
                'precio_total' => 18.95,
                'precio_unidad' => 0.1579,
                'url' => 'https://www.carrefour.es/panales-talla-4-120',
                'frecuencia' => 720,
                'anotaciones' => 'Pack pequeño Carrefour',
            ],
            [
                'tienda' => $primor,
                'unidades' => 200,
                'precio_total' => 24.00,
                'precio_unidad' => 0.12,
                'url' => 'https://www.primor.eu/panales-talla-4-200',
                'frecuencia' => 1440,
                'anotaciones' => 'Pack grande Primor',
            ],
        ];

        // Crear ofertas para cada producto
        foreach ($slugsProductos as $slug) {
            $producto = Producto::where('slug', $slug)->first();
            
            if (!$producto) {
                continue; // Saltar si el producto no existe
            }

            // Obtener las especificaciones elegidas del producto
            $especificacionesElegidas = $producto->categoria_especificaciones_internas_elegidas ?? [];
            $esUnidadUnica = $producto->unidadDeMedida === 'unidadUnica';
            
            // Obtener las columnas del producto (líneas principales marcadas como columna)
            $columnasProducto = [];
            if ($esUnidadUnica && isset($especificacionesElegidas['_columnas'])) {
                // _columnas puede ser un array de idPrincipal o un objeto
                if (is_array($especificacionesElegidas['_columnas'])) {
                    $columnasProducto = $especificacionesElegidas['_columnas'];
                }
            }

            // Crear varias ofertas para cada producto
            foreach ($ofertasBase as $index => $ofertaBase) {
                // Crear especificaciones internas para esta oferta
                $especificacionesOferta = [];
                
                if (!empty($especificacionesElegidas)) {
                    // Para cada opción principal que tenga sublíneas marcadas como "oferta"
                    foreach ($especificacionesElegidas as $idPrincipal => $sublineasElegidas) {
                        // Saltar _columnas que es un campo especial
                        if ($idPrincipal === '_columnas') {
                            continue;
                        }
                        
                        // Filtrar solo las sublíneas marcadas como "oferta" (o === 1)
                        $sublineasOferta = array_filter($sublineasElegidas, function($item) {
                            return isset($item['o']) && $item['o'] === 1;
                        });
                        
                        if (!empty($sublineasOferta)) {
                            // Seleccionar una sublínea aleatoria de las marcadas como "oferta"
                            $sublineasOfertaArray = array_values($sublineasOferta);
                            $sublineaAleatoria = $sublineasOfertaArray[array_rand($sublineasOfertaArray)];
                            
                            $especificacionesOferta[$idPrincipal] = [$sublineaAleatoria['id']];
                        }
                    }
                    
                    // Si es unidadUnica, añadir _columnas solo para las líneas principales marcadas como columna en el producto
                    if ($esUnidadUnica && !empty($especificacionesOferta) && !empty($columnasProducto)) {
                        $columnas = [];
                        foreach ($columnasProducto as $idPrincipalColumna) {
                            // Verificar que esta línea principal tenga especificaciones en la oferta
                            if (isset($especificacionesOferta[$idPrincipalColumna]) && !empty($especificacionesOferta[$idPrincipalColumna])) {
                                // Seleccionar una sublínea aleatoria de las seleccionadas en la oferta como columna
                                $sublineasOferta = $especificacionesOferta[$idPrincipalColumna];
                                $columnas[$idPrincipalColumna] = $sublineasOferta[array_rand($sublineasOferta)];
                            }
                        }
                        if (!empty($columnas)) {
                            $especificacionesOferta['_columnas'] = $columnas;
                        }
                    }
                }

                OfertaProducto::create([
                    'producto_id' => $producto->id,
                    'tienda_id' => $ofertaBase['tienda']->id,
                    'unidades' => $ofertaBase['unidades'],
                    'precio_total' => $ofertaBase['precio_total'],
                    'precio_unidad' => $ofertaBase['precio_unidad'],
                    'url' => $ofertaBase['url'] . '-' . str_replace('talla-4', '', $slug),
                    'mostrar' => 'si',
                    'como_scrapear' => 'automatico',
                    'frecuencia_actualizar_precio_minutos' => $ofertaBase['frecuencia'],
                    'anotaciones_internas' => $ofertaBase['anotaciones'] . ' - ' . $producto->nombre,
                    'aviso' => Carbon::tomorrow()->setTime(0, 1 + ($index * 2)),
                    'especificaciones_internas' => !empty($especificacionesOferta) ? $especificacionesOferta : null,
                ]);
            }
        }

        // Crear ofertas para productos de tenis
        $productosTenis = Producto::whereHas('categoria', function($query) {
            $query->where('slug', 'tenis');
        })->get();

        if ($productosTenis->count() > 0) {
            // Obtener especificaciones de la categoría tenis
            $categoriaTenis = \App\Models\Categoria::where('slug', 'tenis')->first();
            if ($categoriaTenis) {
                $especificacionesTenis = $categoriaTenis->especificaciones_internas;
                $filtrosTenis = $especificacionesTenis['filtros'] ?? [];
                
                // Obtener IDs de talla y color
                $idTalla = null;
                $idColor = null;
                $subprincipalesTalla = [];
                $subprincipalesColor = [];
                
                foreach ($filtrosTenis as $filtro) {
                    if ($filtro['texto'] === 'Talla') {
                        $idTalla = $filtro['id'];
                        $subprincipalesTalla = $filtro['subprincipales'] ?? [];
                    }
                    if ($filtro['texto'] === 'Color') {
                        $idColor = $filtro['id'];
                        $subprincipalesColor = $filtro['subprincipales'] ?? [];
                    }
                }
                
                // Ofertas base para tenis
                $ofertasBaseTenis = [
                    ['precio_total' => 49.99, 'unidades' => 1, 'precio_unidad' => 49.99],
                    ['precio_total' => 45.99, 'unidades' => 1, 'precio_unidad' => 45.99],
                    ['precio_total' => 52.99, 'unidades' => 1, 'precio_unidad' => 52.99],
                    ['precio_total' => 47.50, 'unidades' => 1, 'precio_unidad' => 47.50],
                    ['precio_total' => 54.99, 'unidades' => 1, 'precio_unidad' => 54.99],
                    ['precio_total' => 43.99, 'unidades' => 1, 'precio_unidad' => 43.99],
                    ['precio_total' => 51.50, 'unidades' => 1, 'precio_unidad' => 51.50],
                    ['precio_total' => 48.99, 'unidades' => 1, 'precio_unidad' => 48.99],
                    ['precio_total' => 46.50, 'unidades' => 1, 'precio_unidad' => 46.50],
                    ['precio_total' => 50.99, 'unidades' => 1, 'precio_unidad' => 50.99],
                ];
                
                $tiendasTenis = [$carrefour, $primor, $miravia];
                
                foreach ($productosTenis as $productoTenis) {
                    // Obtener las especificaciones elegidas del producto
                    $especificacionesElegidas = $productoTenis->categoria_especificaciones_internas_elegidas ?? [];
                    
                    // Filtrar tallas que tienen m=1 (mostrar)
                    $tallasDisponibles = [];
                    if ($idTalla && isset($especificacionesElegidas[$idTalla])) {
                        foreach ($especificacionesElegidas[$idTalla] as $tallaItem) {
                            if (isset($tallaItem['m']) && $tallaItem['m'] === 1) {
                                // Buscar el texto de la talla en las subprincipales
                                foreach ($subprincipalesTalla as $talla) {
                                    if ($talla['id'] === $tallaItem['id']) {
                                        $tallasDisponibles[] = [
                                            'id' => $tallaItem['id'],
                                            'texto' => $talla['texto'],
                                        ];
                                        break;
                                    }
                                }
                            }
                        }
                    }
                    
                    // Filtrar colores que tienen o=1 (oferta)
                    $coloresDisponibles = [];
                    if ($idColor && isset($especificacionesElegidas[$idColor])) {
                        foreach ($especificacionesElegidas[$idColor] as $colorItem) {
                            if (isset($colorItem['o']) && $colorItem['o'] === 1) {
                                // Buscar el texto del color en las subprincipales
                                foreach ($subprincipalesColor as $color) {
                                    if ($color['id'] === $colorItem['id']) {
                                        $coloresDisponibles[] = [
                                            'id' => $colorItem['id'],
                                            'texto' => $color['texto'],
                                        ];
                                        break;
                                    }
                                }
                            }
                        }
                    }
                    
                    // Obtener las columnas del producto tenis (líneas principales marcadas como columna)
                    $columnasProductoTenis = [];
                    $especificacionesElegidasTenis = $productoTenis->categoria_especificaciones_internas_elegidas ?? [];
                    if (isset($especificacionesElegidasTenis['_columnas']) && is_array($especificacionesElegidasTenis['_columnas'])) {
                        $columnasProductoTenis = $especificacionesElegidasTenis['_columnas'];
                    }
                    
                    // Crear 10 ofertas para cada producto
                    for ($i = 0; $i < 10; $i++) {
                        // Seleccionar talla y color aleatoriamente de las disponibles
                        $tallaSeleccionada = !empty($tallasDisponibles) ? $tallasDisponibles[array_rand($tallasDisponibles)] : null;
                        $colorSeleccionado = !empty($coloresDisponibles) ? $coloresDisponibles[array_rand($coloresDisponibles)] : null;
                        
                        // Crear especificaciones internas para esta oferta
                        $especificacionesOfertaTenis = [];
                        
                        if ($idTalla && $tallaSeleccionada) {
                            $especificacionesOfertaTenis[$idTalla] = [$tallaSeleccionada['id']];
                        }
                        
                        if ($idColor && $colorSeleccionado) {
                            $especificacionesOfertaTenis[$idColor] = [$colorSeleccionado['id']];
                        }
                        
                        // Añadir _columnas solo para las líneas principales marcadas como columna en el producto
                        if (!empty($especificacionesOfertaTenis) && !empty($columnasProductoTenis)) {
                            $columnas = [];
                            foreach ($columnasProductoTenis as $idPrincipalColumna) {
                                // Verificar que esta línea principal tenga especificaciones en la oferta
                                if (isset($especificacionesOfertaTenis[$idPrincipalColumna]) && !empty($especificacionesOfertaTenis[$idPrincipalColumna])) {
                                    // Seleccionar la sublínea seleccionada en la oferta como columna
                                    $sublineasOferta = $especificacionesOfertaTenis[$idPrincipalColumna];
                                    $columnas[$idPrincipalColumna] = $sublineasOferta[array_rand($sublineasOferta)];
                                }
                            }
                            if (!empty($columnas)) {
                                $especificacionesOfertaTenis['_columnas'] = $columnas;
                            }
                        }
                        
                        $ofertaBase = $ofertasBaseTenis[$i];
                        $tiendaAleatoria = $tiendasTenis[array_rand($tiendasTenis)];
                        
                        $urlTalla = $tallaSeleccionada ? '-talla-' . $tallaSeleccionada['texto'] : '';
                        $urlColor = $colorSeleccionado ? '-color-' . strtolower($colorSeleccionado['texto']) : '';
                        
                        OfertaProducto::create([
                            'producto_id' => $productoTenis->id,
                            'tienda_id' => $tiendaAleatoria->id,
                            'unidades' => $ofertaBase['unidades'],
                            'precio_total' => $ofertaBase['precio_total'],
                            'precio_unidad' => $ofertaBase['precio_unidad'],
                            'url' => 'https://www.' . strtolower($tiendaAleatoria->nombre) . '.com/tenis/' . $productoTenis->slug . $urlTalla . $urlColor,
                            'mostrar' => 'si',
                            'como_scrapear' => 'automatico',
                            'frecuencia_actualizar_precio_minutos' => 1440,
                            'anotaciones_internas' => 'Oferta tenis - ' . $productoTenis->nombre . ($tallaSeleccionada ? ' - Talla ' . $tallaSeleccionada['texto'] : '') . ($colorSeleccionado ? ' - Color ' . $colorSeleccionado['texto'] : ''),
                            'aviso' => Carbon::tomorrow()->setTime(0, 1 + ($i * 2)),
                            'especificaciones_internas' => !empty($especificacionesOfertaTenis) ? $especificacionesOfertaTenis : null,
                        ]);
                    }
                }
            }
        }
    }
}
