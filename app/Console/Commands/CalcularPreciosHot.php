<?php

namespace App\Console\Commands;

use App\Models\PrecioHot;
use App\Models\EjecucionGlobal;
use App\Models\Categoria;
use App\Models\Producto;
use App\Models\OfertaProducto;
use App\Models\HistoricoPrecioProducto;
use App\Services\SacarPrimeraOfertaDeUnProductoAplicadoDescuentosYChollos;
use Illuminate\Console\Command;
use Carbon\Carbon;

class CalcularPreciosHot extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'precios-hot:calcular {--token=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calcula los precios hot para todas las categorías';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Verificar token de seguridad
        $token = $this->option('token');
        if (!$token || $token !== env('TOKEN_ACTUALIZAR_PRECIOS')) {
            $this->error('❌ Token inválido');
            return 1;
        }

        $this->info('🔥 Iniciando cálculo de precios hot...');

        // Crear registro de ejecución
        $ejecucion = EjecucionGlobal::create([
            'inicio' => now(),
            'nombre' => 'precios_hot',
            'log' => []
        ]);

        try {
            // PASO 1: Actualizar precios de productos antes de calcular precios hot
            $this->info('💰 Paso 1/2: Actualizando precios de productos...');
            $preciosActualizados = $this->actualizarPreciosProductos();
            $this->info("✅ Precios actualizados: {$preciosActualizados} productos");
            
            // PASO 2: Calcular precios hot usando PrecioHotController
            $this->info('🔥 Paso 2/2: Calculando precios hot...');
            $precioHotController = new \App\Http\Controllers\PrecioHotController();
            $precioHotController->procesarPreciosHotCompleto($ejecucion);
            
            $ejecucion->update([
                'fin' => now()
            ]);

            $ejecucion->refresh();
            $this->info('✅ Proceso completado exitosamente');
            $this->info("📊 Resumen: {$ejecucion->total} categorías, {$ejecucion->total_guardado} inserciones, {$ejecucion->total_errores} errores");
            
        } catch (\Exception $e) {
            $ejecucion->update([
                'fin' => now(),
                'total_errores' => ($ejecucion->total_errores ?? 0) + 1
            ]);

            $this->error('❌ Error en el proceso: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }

    /**
     * Actualiza el precio de todos los productos usando el servicio para obtener ofertas con descuentos y chollos
     * 
     * @return int Número de precios actualizados
     */
    private function actualizarPreciosProductos()
    {
        $productos = Producto::all();
        $totalProductos = $productos->count();
        $preciosActualizados = 0;
        $servicioOfertas = new SacarPrimeraOfertaDeUnProductoAplicadoDescuentosYChollos();

        $this->line("📋 Procesando {$totalProductos} productos...");

        foreach ($productos as $producto) {
            try {
                // Verificar primero si hay ofertas disponibles para este producto
                $tieneOfertas = $producto->ofertas()
                    ->where('mostrar', 'si')
                    ->whereHas('tienda', function($query) {
                        $query->where('mostrar_tienda', 'si');
                    })
                    ->exists();
                
                // Si no hay ofertas disponibles, poner precio a 0
                if (!$tieneOfertas) {
                    if ($producto->precio != 0) {
                        $producto->precio = 0;
                        $producto->save();
                        $preciosActualizados++;
                    }
                    continue;
                }
                
                // Usar el servicio para obtener la oferta más barata con descuentos y chollos aplicados
                $mejorOferta = $servicioOfertas->obtener($producto);

                // Si el servicio devuelve una oferta válida con precio_unidad
                if ($mejorOferta && $mejorOferta->precio_unidad !== null && $mejorOferta->precio_unidad > 0) {
                    // El precio_unidad ya viene con descuentos y chollos aplicados del servicio
                    $precioRealMasBajo = $mejorOferta->precio_unidad;
                    
                    // Si el producto tiene unidadDeMedida = unidadMilesima, redondear a 3 decimales
                    if ($producto->unidadDeMedida === 'unidadMilesima') {
                        $precioNuevo = round($precioRealMasBajo, 3);
                    } else {
                        $precioNuevo = $precioRealMasBajo;
                    }
                    
                    // Validar que el precio nuevo es válido
                    if ($precioNuevo !== null && $precioNuevo > 0) {
                        // Comparar si el precio es diferente
                        if ($producto->precio != $precioNuevo) {
                            // Actualizar el precio del producto
                            $producto->precio = $precioNuevo;
                            $producto->save();
                            $preciosActualizados++;
                        }
                    } else {
                        // Si el precio calculado es inválido pero hay ofertas, mantener precio actual
                        // (no poner a 0 porque sabemos que hay ofertas)
                    }
                } else {
                    // Si el servicio no devuelve oferta válida pero sabemos que hay ofertas,
                    // mantener el precio actual (no poner a 0)
                }
            } catch (\Exception $e) {
                // Continuar con el siguiente producto si hay error
                \Log::warning("Error al actualizar precio del producto {$producto->id}: " . $e->getMessage());
            }
        }

        return $preciosActualizados;
    }

    private function procesarPreciosHot($ejecucion)
    {
        $log = [];
        $totalCategorias = 0;
        $totalInserciones = 0;
        $totalErrores = 0;

        // Obtener todas las categorías
        $categorias = Categoria::all();
        $totalCategorias = $categorias->count();

        $this->info("📋 Procesando {$totalCategorias} categorías...");

        foreach ($categorias as $categoria) {
            try {
                $this->line("🔄 Procesando categoría: {$categoria->nombre}");
                
                $productosHot = $this->obtenerProductosHotPorCategoria($categoria, 20);
                
                if (!empty($productosHot)) {
                    // Guardar o actualizar en la tabla precios_hot
                    PrecioHot::updateOrCreate(
                        ['nombre' => $categoria->nombre],
                        ['datos' => $productosHot]
                    );
                    $totalInserciones++;
                    $log[] = "✅ Categoría '{$categoria->nombre}': " . count($productosHot) . " productos hot encontrados";
                    $this->info("✅ Categoría '{$categoria->nombre}': " . count($productosHot) . " productos hot encontrados");
                } else {
                    $log[] = "⚠️ Categoría '{$categoria->nombre}': No se encontraron productos hot";
                    $this->warn("⚠️ Categoría '{$categoria->nombre}': No se encontraron productos hot");
                }
            } catch (\Exception $e) {
                $totalErrores++;
                $log[] = "❌ Error en categoría '{$categoria->nombre}': " . $e->getMessage();
                $this->error("❌ Error en categoría '{$categoria->nombre}': " . $e->getMessage());
            }
        }

        // Procesar categoría global "Precios Hot"
        try {
            $this->line("🔄 Procesando categoría global: Precios Hot");
            
            $productosHotGlobal = $this->obtenerProductosHotGlobal(60);
            
            if (!empty($productosHotGlobal)) {
                PrecioHot::updateOrCreate(
                    ['nombre' => 'Precios Hot'],
                    ['datos' => $productosHotGlobal]
                );
                $totalInserciones++;
                $log[] = "✅ Categoría global 'Precios Hot': " . count($productosHotGlobal) . " productos hot encontrados";
                $this->info("✅ Categoría global 'Precios Hot': " . count($productosHotGlobal) . " productos hot encontrados");
            } else {
                $log[] = "⚠️ Categoría global 'Precios Hot': No se encontraron productos hot";
                $this->warn("⚠️ Categoría global 'Precios Hot': No se encontraron productos hot");
            }
        } catch (\Exception $e) {
            $totalErrores++;
            $log[] = "❌ Error en categoría global 'Precios Hot': " . $e->getMessage();
            $this->error("❌ Error en categoría global 'Precios Hot': " . $e->getMessage());
        }

        $ejecucion->update([
            'total' => $totalCategorias,
            'total_guardado' => $totalInserciones,
            'total_errores' => $totalErrores,
            'log' => $log
        ]);
    }

    private function obtenerProductosHotPorCategoria($categoria, $limite = 20)
    {
        // Obtener todas las categorías hijas (incluyendo la actual)
        $categoriaIds = $this->obtenerCategoriaIdsIncluyendoHijas($categoria->id);
        
        // Obtener productos de estas categorías con sus relaciones
        $productos = Producto::with(['categoria', 'categoriaEspecificaciones'])
            ->select('id', 'nombre', 'imagen_pequena', 'categoria_id', 'slug', 'precio', 'unidadDeMedida', 'categoria_id_especificaciones_internas', 'categoria_especificaciones_internas_elegidas')
            ->whereIn('categoria_id', $categoriaIds)
            ->whereNotNull('precio')
            ->where('precio', '>', 0)
            ->get();
        
        return $this->calcularProductosHot($productos, $limite);
    }

    private function obtenerProductosHotGlobal($limite = 60)
    {
        // Obtener todos los productos con sus relaciones
        $productos = Producto::with(['categoria', 'categoriaEspecificaciones'])
            ->select('id', 'nombre', 'imagen_pequena', 'categoria_id', 'slug', 'precio', 'unidadDeMedida', 'categoria_id_especificaciones_internas', 'categoria_especificaciones_internas_elegidas')
            ->whereNotNull('precio')
            ->where('precio', '>', 0)
            ->get();
        
        return $this->calcularProductosHot($productos, $limite);
    }

    private function calcularProductosHot($productos, $limite)
    {
        $productosHot = [];
        $haceUnMes = Carbon::now()->subMonth();
        $servicioOfertas = new SacarPrimeraOfertaDeUnProductoAplicadoDescuentosYChollos();

        foreach ($productos as $producto) {
            // Calcular precio medio del último mes para el producto general (sin especificacion_interna_id)
            $precioMedio = HistoricoPrecioProducto::where('producto_id', $producto->id)
                ->whereNull('especificacion_interna_id')
                ->where('fecha', '>=', $haceUnMes)
                ->where('precio_minimo', '>', 0)
                ->whereNotNull('precio_minimo')
                ->avg('precio_minimo');

            // Validar que el precio medio sea válido (mayor que 0)
            if (!$precioMedio || $precioMedio <= 0) {
                // Limpiar rebajado si no hay precio medio válido
                Producto::where('id', $producto->id)->update(['rebajado' => null]);
            } else {
                // Usar el servicio para obtener la oferta más barata con descuentos y chollos aplicados
                $mejorOferta = $servicioOfertas->obtener($producto);
                
                if ($mejorOferta && $mejorOferta->precio_unidad > 0) {
                    // Usar el precio_unidad de la oferta procesada (con descuentos y chollos aplicados)
                    $precioOferta = $mejorOferta->precio_unidad;
                    
                    // Validar que el precio de la oferta no sea mayor que el precio medio (evitar descuentos negativos)
                    if ($precioOferta <= $precioMedio) {
                        // Calcular porcentaje de diferencia usando el precio de la oferta más barata
                        $diferencia = (($precioMedio - $precioOferta) / $precioMedio) * 100;

                        // Solo incluir si el precio de la oferta es menor que la media y la diferencia es del 5% o más
                        if ($diferencia >= 5) {
                            // Actualizar campo rebajado del producto SOLO si entra en precios hot
                            // Si la diferencia es >= 5%, guardar el porcentaje redondeado a entero
                            $porcentajeRebajado = (int) round($diferencia);
                            Producto::where('id', $producto->id)->update(['rebajado' => $porcentajeRebajado]);
                            
                            // Verificar que la tienda existe y tiene los datos necesarios
                            $tienda = $mejorOferta->tienda;
                            if ($tienda) {
                                // Obtener unidad de medida del producto
                                $unidadMedida = $producto->unidadDeMedida ?? 'unidad';
                                
                                // Formatear precio según la unidad de medida
                                $decimalesPrecio = ($unidadMedida === 'unidadMilesima') ? 3 : 2;
                                $precioFormateado = number_format($precioOferta, $decimalesPrecio, ',', '.') . ' €';
                                
                                // Añadir sufijo según la unidad de medida
                                if ($unidadMedida === 'unidad') {
                                    $precioFormateado .= '/Und.';
                                } elseif ($unidadMedida === 'kilos') {
                                    $precioFormateado .= '/Kg.';
                                } elseif ($unidadMedida === 'litros') {
                                    $precioFormateado .= '/L.';
                                } elseif ($unidadMedida === 'unidadMilesima') {
                                    $precioFormateado .= '/Und.';
                                } elseif ($unidadMedida === '800gramos') {
                                    $precioFormateado .= '/800gr.';
                                } elseif ($unidadMedida === '100ml') {
                                    $precioFormateado .= '/100ml.';
                                } elseif ($unidadMedida === 'unidadUnica') {
                                    $precioFormateado .= '';
                                }
                                
                                $productosHot[] = [
                                    'producto_id' => $producto->id,
                                    'oferta_id' => $mejorOferta->id,
                                    'tienda_id' => $mejorOferta->tienda_id,
                                    'img_tienda' => $tienda->imagen ?? null,
                                    'img_producto' => $producto->imagen_pequena ?? null,
                                    'precio_oferta' => $precioOferta,
                                    'precio_formateado' => $precioFormateado,
                                    'porcentaje_diferencia' => round($diferencia, 2),
                                    'url_oferta' => $mejorOferta->url,
                                    'url_producto' => $this->generarUrlProducto($producto),
                                    'producto_nombre' => $producto->nombre,
                                    'tienda_nombre' => $tienda->nombre ?? 'Tienda desconocida',
                                    'unidades' => $mejorOferta->unidades ?? '1.00',
                                    'unidad_medida' => $unidadMedida
                                ];
                            }
                        } else {
                            // Si la diferencia es menor al 5%, limpiar rebajado (poner a null)
                            Producto::where('id', $producto->id)->update(['rebajado' => null]);
                        }
                    } else {
                        // Limpiar rebajado si el precio actual es mayor que la media (no es descuento)
                        Producto::where('id', $producto->id)->update(['rebajado' => null]);
                    }
                } else {
                    // Limpiar rebajado si no hay oferta válida
                    Producto::where('id', $producto->id)->update(['rebajado' => null]);
                }
            }
            
            // Calcular precios hot de especificaciones internas si el producto las tiene
            $productosHotEspecificaciones = $this->calcularPreciosHotEspecificacionesInternas($producto, $haceUnMes);
            $productosHot = array_merge($productosHot, $productosHotEspecificaciones);
        }

        // Ordenar por porcentaje de diferencia (mayor a menor) y tomar los primeros
        usort($productosHot, function($a, $b) {
            return $b['porcentaje_diferencia'] <=> $a['porcentaje_diferencia'];
        });

        return array_slice($productosHot, 0, $limite);
    }

    private function obtenerCategoriaIdsIncluyendoHijas($categoriaId)
    {
        $categoriaIds = [$categoriaId];
        
        // Obtener categorías hijas directas
        $hijas = Categoria::where('parent_id', $categoriaId)->get();
        
        foreach ($hijas as $hija) {
            $categoriaIds = array_merge($categoriaIds, $this->obtenerCategoriaIdsIncluyendoHijas($hija->id));
        }
        
        return $categoriaIds;
    }

    /**
     * Calcula precios hot para las especificaciones internas de un producto
     */
    private function calcularPreciosHotEspecificacionesInternas($producto, $haceUnMes)
    {
        $productosHot = [];
        
        // Solo procesar si el producto tiene especificaciones internas y está marcado como unidadUnica
        if (!$producto->categoria_id_especificaciones_internas || 
            !$producto->categoria_especificaciones_internas_elegidas ||
            $producto->unidadDeMedida !== 'unidadUnica') {
            return $productosHot;
        }
        
        $especificacionesElegidas = $producto->categoria_especificaciones_internas_elegidas;
        $servicioOfertas = new SacarPrimeraOfertaDeUnProductoAplicadoDescuentosYChollos();
        
        // Obtener TODAS las ofertas del producto una sola vez (más eficiente)
        $todasLasOfertas = $servicioOfertas->obtenerTodas($producto);
        
        // Obtener la categoría de especificaciones internas para acceder a los filtros
        $categoriaEspecificaciones = $producto->categoriaEspecificaciones;
        if (!$categoriaEspecificaciones || !$categoriaEspecificaciones->especificaciones_internas) {
            return $productosHot;
        }
        
        $especificacionesCategoria = $categoriaEspecificaciones->especificaciones_internas;
        $filtros = $especificacionesCategoria['filtros'] ?? [];
        
        // También obtener filtros de producto si existen
        $filtrosProducto = [];
        if (isset($especificacionesElegidas['_producto']['filtros']) && 
            is_array($especificacionesElegidas['_producto']['filtros'])) {
            $filtrosProducto = $especificacionesElegidas['_producto']['filtros'];
        }
        
        // Combinar filtros de categoría y producto
        $filtrosCombinados = array_merge($filtros, $filtrosProducto);
        
        // Cargar especificaciones_busqueda del producto para actualizarlo
        $especificacionesBusqueda = $producto->especificaciones_busqueda ?? [];
        $necesitaActualizarEspecificacionesBusqueda = false;
        
        // Iterar sobre cada línea principal en las especificaciones elegidas
        foreach ($especificacionesElegidas as $lineaId => $sublineasProducto) {
            // Saltar claves especiales como '_producto', '_formatos', '_columnas', etc.
            if (strpos($lineaId, '_') === 0) {
                continue;
            }
            
            // Buscar la línea principal en los filtros combinados
            $lineaPrincipal = null;
            foreach ($filtrosCombinados as $filtro) {
                if (isset($filtro['id']) && strval($filtro['id']) === strval($lineaId)) {
                    $lineaPrincipal = $filtro;
                    break;
                }
            }
            
            if (!$lineaPrincipal) {
                continue;
            }
            
            // Convertir sublíneas del producto a array si no lo es
            $sublineasArray = is_array($sublineasProducto) ? $sublineasProducto : [$sublineasProducto];
            
            // Iterar sobre cada sublínea del producto
            foreach ($sublineasArray as $sublineaProducto) {
                // Verificar si está marcada como "mostrar"
                $esMostrar = false;
                $sublineaId = null;
                
                if (is_array($sublineaProducto)) {
                    $sublineaId = $sublineaProducto['id'] ?? null;
                    
                    // Verificar si está marcada como "mostrar"
                    $mValue = $sublineaProducto['m'] ?? null;
                    if ($mValue !== null) {
                        $esMostrar = ($mValue === 1 || $mValue === '1' || $mValue === true || $mValue === 'true');
                    }
                    
                    // También verificar el campo 'mostrar' como alternativa
                    if (!$esMostrar && isset($sublineaProducto['mostrar'])) {
                        $esMostrar = ($sublineaProducto['mostrar'] === true || $sublineaProducto['mostrar'] === 'true' || $sublineaProducto['mostrar'] === 1 || $sublineaProducto['mostrar'] === '1');
                    }
                } else {
                    $sublineaId = strval($sublineaProducto);
                    $esMostrar = false; // Si no es array, no tiene flag de mostrar
                }
                
                if (!$esMostrar || !$sublineaId) {
                    continue;
                }
                
                // Obtener la primera imagen de la sublínea si tiene imágenes
                $primeraImagen = null;
                if (is_array($sublineaProducto)) {
                    // Verificar si tiene imágenes y no está usando imágenes del producto
                    $usarImagenesProducto = $sublineaProducto['usarImagenesProducto'] ?? false;
                    if (!$usarImagenesProducto && isset($sublineaProducto['img']) && is_array($sublineaProducto['img']) && !empty($sublineaProducto['img'])) {
                        $primeraImagen = $sublineaProducto['img'][0] ?? null;
                    }
                }
                
                // Buscar el texto de la sublínea en la línea principal
                $sublineaTexto = null;
                $subprincipales = $lineaPrincipal['subprincipales'] ?? [];
                foreach ($subprincipales as $subprincipal) {
                    $subprincipalId = is_array($subprincipal) ? ($subprincipal['id'] ?? null) : strval($subprincipal);
                    if (strval($subprincipalId) === strval($sublineaId)) {
                        // Obtener el texto de la sublínea
                        if (is_array($subprincipal)) {
                            // Buscar primero en 'texto', luego en 'slug'
                            $sublineaTexto = $subprincipal['texto'] ?? $subprincipal['slug'] ?? null;
                            
                            // Si no tiene texto ni slug, generar slug desde el texto si existe
                            if (!$sublineaTexto && isset($subprincipal['texto'])) {
                                $sublineaTexto = \Illuminate\Support\Str::slug($subprincipal['texto']);
                            }
                            
                            // Si aún no tiene, usar el ID como último recurso
                            if (!$sublineaTexto) {
                                $sublineaTexto = strval($subprincipalId);
                            }
                        } else {
                            $sublineaTexto = strval($subprincipal);
                        }
                        
                        // Verificar si hay texto alternativo
                        if (is_array($sublineaProducto) && isset($sublineaProducto['textoAlternativo']) && !empty($sublineaProducto['textoAlternativo'])) {
                            $sublineaTexto = $sublineaProducto['textoAlternativo'];
                        }
                        break;
                    }
                }
                
                if (!$sublineaTexto) {
                    continue;
                }
                
                // Convertir el texto a slug para usarlo en la URL
                $sublineaTextoSlug = \Illuminate\Support\Str::slug($sublineaTexto);
                
                // Calcular precio medio del histórico de esta especificación interna
                $precioMedioEspecificacion = HistoricoPrecioProducto::where('producto_id', $producto->id)
                    ->where('especificacion_interna_id', $sublineaId)
                    ->where('fecha', '>=', $haceUnMes)
                    ->where('precio_minimo', '>', 0)
                    ->whereNotNull('precio_minimo')
                    ->avg('precio_minimo');
                
                // Si no hay precio medio válido, continuar con la siguiente especificación
                if (!$precioMedioEspecificacion || $precioMedioEspecificacion <= 0) {
                    continue;
                }
                
                // Filtrar ofertas que coincidan con esta especificación específica
                $ofertasFiltradas = $todasLasOfertas->filter(function($oferta) use ($lineaId, $sublineaId) {
                    $especificacionesOferta = $oferta->especificaciones_internas;
                    
                    if (!$especificacionesOferta || !is_array($especificacionesOferta)) {
                        return false;
                    }
                    
                    $ofertaLinea = $especificacionesOferta[$lineaId] ?? null;
                    if (!$ofertaLinea) {
                        return false;
                    }
                    
                    $ofertaSublineas = is_array($ofertaLinea) ? $ofertaLinea : [$ofertaLinea];
                    
                    foreach ($ofertaSublineas as $item) {
                        $itemId = (is_array($item) && isset($item['id'])) ? strval($item['id']) : strval($item);
                        if (strval($itemId) === strval($sublineaId)) {
                            return true;
                        }
                    }
                    
                    return false;
                });
                
                // Obtener la oferta más barata de las filtradas
                $mejorOfertaEspecificacion = null;
                if ($ofertasFiltradas->isNotEmpty()) {
                    // Las ofertas ya están ordenadas por precio_unidad (más barata primero)
                    $mejorOfertaEspecificacion = $ofertasFiltradas->first();
                }
                
                if (!$mejorOfertaEspecificacion || $mejorOfertaEspecificacion->precio_unidad <= 0) {
                    continue;
                }
                
                $precioOfertaEspecificacion = $mejorOfertaEspecificacion->precio_unidad;
                
                // Validar que el precio de la oferta no sea mayor que el precio medio
                if ($precioOfertaEspecificacion > $precioMedioEspecificacion) {
                    continue;
                }
                
                // Calcular porcentaje de diferencia
                $diferencia = (($precioMedioEspecificacion - $precioOfertaEspecificacion) / $precioMedioEspecificacion) * 100;
                
                // Actualizar campo rebajado en especificaciones_busqueda
                // Si la diferencia es >= 5%, guardar el porcentaje redondeado a entero
                // Si la diferencia está entre 0.1% y 4.99%, guardar 0
                // Si no hay rebaja o el precio es mayor que la media, guardar 0
                $rebajado = 0;
                if ($diferencia >= 5) {
                    $rebajado = (int) round($diferencia);
                } elseif ($diferencia > 0 && $diferencia < 5) {
                    $rebajado = 0; // Rebajas menores al 5% se guardan como 0
                }
                
                // Actualizar especificaciones_busqueda si existe esta especificación
                if (isset($especificacionesBusqueda[$sublineaTextoSlug])) {
                    $especificacionActual = $especificacionesBusqueda[$sublineaTextoSlug];
                    $rebajadoAnterior = $especificacionActual['rebajado'] ?? 0;
                    
                    // Solo actualizar si ha cambiado
                    if ($rebajadoAnterior != $rebajado) {
                        $especificacionesBusqueda[$sublineaTextoSlug]['rebajado'] = $rebajado;
                        $necesitaActualizarEspecificacionesBusqueda = true;
                    }
                }
                
                // Solo incluir en precios hot si la diferencia es del 5% o más
                if ($diferencia >= 5) {
                    $tienda = $mejorOfertaEspecificacion->tienda;
                    if (!$tienda) {
                        continue;
                    }
                    
                    // Obtener unidad de medida del producto
                    $unidadMedida = $producto->unidadDeMedida ?? 'unidadUnica';
                    
                    // Formatear precio según la unidad de medida
                    $decimalesPrecio = ($unidadMedida === 'unidadMilesima') ? 3 : 2;
                    $precioFormateado = number_format($precioOfertaEspecificacion, $decimalesPrecio, ',', '.') . ' €';
                    
                    // Generar URL del producto con el slug de la especificación
                    $urlProducto = $this->generarUrlProducto($producto) . '/' . $sublineaTextoSlug;
                    
                    // Preparar imagen del producto (usar imagen de especificación si existe, sino la del producto)
                    $imagenProducto = $primeraImagen ?? $producto->imagen_pequena ?? null;
                    
                    // Nombre del producto con la especificación
                    $nombreCompleto = $producto->nombre . ' ' . $sublineaTexto;
                    
                    $productosHot[] = [
                        'producto_id' => $producto->id,
                        'oferta_id' => $mejorOfertaEspecificacion->id,
                        'tienda_id' => $mejorOfertaEspecificacion->tienda_id,
                        'img_tienda' => $tienda->imagen ?? null,
                        'img_producto' => $imagenProducto,
                        'precio_oferta' => $precioOfertaEspecificacion,
                        'precio_formateado' => $precioFormateado,
                        'porcentaje_diferencia' => round($diferencia, 2),
                        'url_oferta' => $mejorOfertaEspecificacion->url,
                        'url_producto' => $urlProducto,
                        'producto_nombre' => $nombreCompleto,
                        'tienda_nombre' => $tienda->nombre ?? 'Tienda desconocida',
                        'unidades' => $mejorOfertaEspecificacion->unidades ?? '1.00',
                        'unidad_medida' => $unidadMedida
                    ];
                }
            }
        }
        
        // Procesar solo las especificaciones marcadas como "mostrar" para actualizar rebajado
        // Iterar sobre las especificaciones elegidas que están marcadas como "mostrar"
        foreach ($especificacionesElegidas as $lineaId => $sublineasProducto) {
            // Saltar claves especiales como '_producto', '_formatos', '_columnas', etc.
            if (strpos($lineaId, '_') === 0) {
                continue;
            }
            
            // Buscar la línea principal en los filtros combinados
            $lineaPrincipal = null;
            foreach ($filtrosCombinados as $filtro) {
                if (isset($filtro['id']) && strval($filtro['id']) === strval($lineaId)) {
                    $lineaPrincipal = $filtro;
                    break;
                }
            }
            
            if (!$lineaPrincipal) {
                continue;
            }
            
            // Convertir sublíneas del producto a array si no lo es
            $sublineasArray = is_array($sublineasProducto) ? $sublineasProducto : [$sublineasProducto];
            
            // Iterar sobre cada sublínea del producto
            foreach ($sublineasArray as $sublineaProducto) {
                // Verificar si está marcada como "mostrar"
                $esMostrar = false;
                $sublineaId = null;
                
                if (is_array($sublineaProducto)) {
                    $sublineaId = $sublineaProducto['id'] ?? null;
                    
                    // Verificar si está marcada como "mostrar"
                    $mValue = $sublineaProducto['m'] ?? null;
                    if ($mValue !== null) {
                        $esMostrar = ($mValue === 1 || $mValue === '1' || $mValue === true || $mValue === 'true');
                    }
                    
                    // También verificar el campo 'mostrar' como alternativa
                    if (!$esMostrar && isset($sublineaProducto['mostrar'])) {
                        $esMostrar = ($sublineaProducto['mostrar'] === true || $sublineaProducto['mostrar'] === 'true' || $sublineaProducto['mostrar'] === 1 || $sublineaProducto['mostrar'] === '1');
                    }
                } else {
                    $sublineaId = strval($sublineaProducto);
                    $esMostrar = false; // Si no es array, no tiene flag de mostrar
                }
                
                // Solo procesar si está marcada como "mostrar"
                if (!$esMostrar || !$sublineaId) {
                    continue;
                }
                
                // Buscar el texto de la sublínea para obtener el slug
                $sublineaTexto = null;
                $subprincipales = $lineaPrincipal['subprincipales'] ?? [];
                foreach ($subprincipales as $subprincipal) {
                    $subprincipalId = is_array($subprincipal) ? ($subprincipal['id'] ?? null) : strval($subprincipal);
                    if (strval($subprincipalId) === strval($sublineaId)) {
                        // Obtener el texto de la sublínea
                        if (is_array($subprincipal)) {
                            $sublineaTexto = $subprincipal['texto'] ?? $subprincipal['slug'] ?? null;
                            
                            if (!$sublineaTexto && isset($subprincipal['texto'])) {
                                $sublineaTexto = \Illuminate\Support\Str::slug($subprincipal['texto']);
                            }
                            
                            if (!$sublineaTexto) {
                                $sublineaTexto = strval($subprincipalId);
                            }
                        } else {
                            $sublineaTexto = strval($subprincipal);
                        }
                        
                        // Verificar si hay texto alternativo
                        if (is_array($sublineaProducto) && isset($sublineaProducto['textoAlternativo']) && !empty($sublineaProducto['textoAlternativo'])) {
                            $sublineaTexto = $sublineaProducto['textoAlternativo'];
                        }
                        break;
                    }
                }
                
                if (!$sublineaTexto) {
                    continue;
                }
                
                // Convertir el texto a slug para buscar en especificaciones_busqueda
                $sublineaTextoSlug = \Illuminate\Support\Str::slug($sublineaTexto);
                
                // Verificar si esta especificación existe en especificaciones_busqueda
                if (!isset($especificacionesBusqueda[$sublineaTextoSlug])) {
                    continue;
                }
                
                $especificacionId = $sublineaId;
            
                // Calcular precio medio del histórico de esta especificación interna
                $precioMedioEspecificacion = HistoricoPrecioProducto::where('producto_id', $producto->id)
                    ->where('especificacion_interna_id', $especificacionId)
                    ->where('fecha', '>=', $haceUnMes)
                    ->where('precio_minimo', '>', 0)
                    ->whereNotNull('precio_minimo')
                    ->avg('precio_minimo');
                
                // Si no hay precio medio válido, poner rebajado a 0
                if (!$precioMedioEspecificacion || $precioMedioEspecificacion <= 0) {
                    if (isset($especificacionesBusqueda[$sublineaTextoSlug]['rebajado']) && 
                        $especificacionesBusqueda[$sublineaTextoSlug]['rebajado'] != 0) {
                        $especificacionesBusqueda[$sublineaTextoSlug]['rebajado'] = 0;
                        $necesitaActualizarEspecificacionesBusqueda = true;
                    }
                    continue;
                }
                
                // Filtrar ofertas que coincidan con esta especificación específica
                $ofertasFiltradas = $todasLasOfertas->filter(function($oferta) use ($lineaId, $especificacionId) {
                    $especificacionesOferta = $oferta->especificaciones_internas;
                    
                    if (!$especificacionesOferta || !is_array($especificacionesOferta)) {
                        return false;
                    }
                    
                    $ofertaLinea = $especificacionesOferta[$lineaId] ?? null;
                    if (!$ofertaLinea) {
                        return false;
                    }
                    
                    $ofertaSublineas = is_array($ofertaLinea) ? $ofertaLinea : [$ofertaLinea];
                    
                    foreach ($ofertaSublineas as $item) {
                        $itemId = (is_array($item) && isset($item['id'])) ? strval($item['id']) : strval($item);
                        if (strval($itemId) === strval($especificacionId)) {
                            return true;
                        }
                    }
                    
                    return false;
                });
                
                // Obtener la oferta más barata de las filtradas
                $mejorOfertaEspecificacion = null;
                if ($ofertasFiltradas->isNotEmpty()) {
                    $mejorOfertaEspecificacion = $ofertasFiltradas->first();
                }
                
                // Calcular diferencia y actualizar rebajado
                $rebajado = 0;
                if ($mejorOfertaEspecificacion && $mejorOfertaEspecificacion->precio_unidad > 0) {
                    $precioOfertaEspecificacion = $mejorOfertaEspecificacion->precio_unidad;
                    
                    if ($precioOfertaEspecificacion <= $precioMedioEspecificacion) {
                        $diferencia = (($precioMedioEspecificacion - $precioOfertaEspecificacion) / $precioMedioEspecificacion) * 100;
                        
                        // Si la diferencia es >= 5%, guardar el porcentaje redondeado a entero
                        // Si la diferencia está entre 0.1% y 4.99%, guardar 0
                        if ($diferencia >= 5) {
                            $rebajado = (int) round($diferencia);
                        } else {
                            $rebajado = 0; // Rebajas menores al 5% se guardan como 0
                        }
                    }
                }
                
                // Actualizar rebajado si ha cambiado
                $rebajadoAnterior = $especificacionesBusqueda[$sublineaTextoSlug]['rebajado'] ?? 0;
                if ($rebajadoAnterior != $rebajado) {
                    $especificacionesBusqueda[$sublineaTextoSlug]['rebajado'] = $rebajado;
                    $necesitaActualizarEspecificacionesBusqueda = true;
                }
            }
        }
        
        // Actualizar especificaciones_busqueda del producto si hubo cambios
        if ($necesitaActualizarEspecificacionesBusqueda) {
            $producto->especificaciones_busqueda = $especificacionesBusqueda;
            $producto->save();
        }
        
        return $productosHot;
    }

    private function generarUrlProducto($producto)
    {
        // Cargar la relación de categoría si no está cargada
        if (!$producto->relationLoaded('categoria')) {
            $producto->load('categoria');
        }
        
        $categoria = $producto->categoria;
        if (!$categoria) {
            return '/productos/' . $producto->slug;
        }

        $urlParts = [$categoria->slug];
        
        // Construir la URL completa con la jerarquía de categorías
        $categoriaActual = $categoria;
        while ($categoriaActual->parent_id) {
            $categoriaPadre = Categoria::find($categoriaActual->parent_id);
            if ($categoriaPadre) {
                array_unshift($urlParts, $categoriaPadre->slug);
                $categoriaActual = $categoriaPadre;
            } else {
                break;
            }
        }
        
        return '/' . implode('/', $urlParts) . '/' . $producto->slug;
    }
} 