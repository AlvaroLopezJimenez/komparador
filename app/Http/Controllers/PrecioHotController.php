<?php

namespace App\Http\Controllers;

use App\Models\PrecioHot;
use App\Models\EjecucionPrecioHot;
use App\Models\Categoria;
use App\Models\Producto;
use App\Models\OfertaProducto;
use App\Models\HistoricoPrecioProducto;
use App\Models\Tienda;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Services\SacarPrimeraOfertaDeUnProductoAplicadoDescuentosYChollos;

class PrecioHotController extends Controller
{
    public function index()
    {
        $preciosHot = PrecioHot::orderBy('created_at', 'desc')->get();
        return view('admin.precios-hot.index', compact('preciosHot'));
    }

    public function ejecutarSegundoPlano(Request $request)
    {
        $token = $request->get('token');
        if ($token !== env('TOKEN_ACTUALIZAR_PRECIOS')) {
            return response()->json(['status' => 'error', 'message' => 'Token inválido']);
        }

        // Crear registro de ejecución en la tabla global
        $ejecucion = \App\Models\EjecucionGlobal::create([
            'inicio' => now(),
            'nombre' => 'precios_hot',
            'log' => []
        ]);

        try {
            $this->procesarPreciosHotCompleto($ejecucion);
            
            $ejecucion->update([
                'fin' => now()
            ]);

            // Recargar la ejecución para obtener los logs actualizados
            $ejecucion->refresh();

            return response()->json([
                'status' => 'ok',
                'inserciones' => $ejecucion->total_guardado,
                'errores' => $ejecucion->total_errores,
                'ejecucion_id' => $ejecucion->id
            ]);
        } catch (\Exception $e) {
            $ejecucion->update([
                'fin' => now(),
                'total_errores' => $ejecucion->total_errores + 1
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Error en el proceso: ' . $e->getMessage()
            ]);
        }
    }

    public function verEjecucion()
    {
        $tokenScraper = env('TOKEN_ACTUALIZAR_PRECIOS');
        return view('admin.precios-hot.ejecucion', compact('tokenScraper'));
    }

    public function ejecuciones()
    {
        $ejecuciones = \App\Models\EjecucionGlobal::where('nombre', 'precios_hot')
            ->orderBy('created_at', 'desc')
            ->get();
        return view('admin.precios-hot.ejecuciones', compact('ejecuciones'));
    }

    // Método principal que procesa todos los precios hot (usado por ambas ejecuciones)
    public function procesarPreciosHotCompleto($ejecucion)
    {
        $log = [];
        $totalCategorias = 0;
        $totalInserciones = 0;
        $totalErrores = 0;

        try {
            // Actualizar índice de búsqueda de especificaciones internas para todos los productos
            try {
                $log[] = "🔄 Actualizando índice de búsqueda de especificaciones internas...";
                $productos = Producto::whereNotNull('categoria_especificaciones_internas_elegidas')
                    ->whereNotNull('categoria_id_especificaciones_internas')
                    ->get();
                
                $totalProductos = $productos->count();
                $productosActualizados = 0;
                $erroresActualizacion = 0;
                
                foreach ($productos as $producto) {
                    try {
                        $this->actualizarEspecificacionesBusqueda($producto);
                        $productosActualizados++;
                    } catch (\Exception $e) {
                        $erroresActualizacion++;
                        \Log::warning("Error al actualizar especificaciones de búsqueda para producto {$producto->id}: " . $e->getMessage());
                    }
                }
                
                $log[] = "✅ Índice de búsqueda actualizado: {$productosActualizados} productos de {$totalProductos} procesados, {$erroresActualizacion} errores";
            } catch (\Exception $e) {
                $log[] = "⚠️ Error al actualizar índice de búsqueda: " . $e->getMessage();
            }

            // Obtener todas las categorías
            $categorias = Categoria::all();
            $totalCategorias = $categorias->count();
            $log[] = "📊 Total de categorías encontradas: {$totalCategorias}";

            foreach ($categorias as $categoria) {
                try {
                    $log[] = "🔄 Procesando categoría: {$categoria->nombre}";
                    $productosHot = $this->obtenerProductosHotPorCategoria($categoria, 20, $log);
                    
                    if (!empty($productosHot)) {
                        // Guardar o actualizar en la tabla precios_hot
                        PrecioHot::updateOrCreate(
                            ['nombre' => $categoria->nombre],
                            ['datos' => $productosHot]
                        );
                        $totalInserciones++;
                        $log[] = "✅ Categoría '{$categoria->nombre}': " . count($productosHot) . " productos hot encontrados";
                    } else {
                        $log[] = "⚠️ Categoría '{$categoria->nombre}': No se encontraron productos hot";
                    }
                } catch (\Exception $e) {
                    $totalErrores++;
                    $log[] = "❌ Error en categoría '{$categoria->nombre}': " . $e->getMessage();
                    $log[] = "📍 Stack trace: " . $e->getTraceAsString();
                }
            }

            // Procesar categoría global "Precios Hot"
            try {
                $log[] = "🔄 Procesando categoría global: Precios Hot";
                $productosHotGlobal = $this->obtenerProductosHotGlobal(60, $log);
                
                if (!empty($productosHotGlobal)) {
                    PrecioHot::updateOrCreate(
                        ['nombre' => 'Precios Hot'],
                        ['datos' => $productosHotGlobal]
                    );
                    $totalInserciones++;
                    $log[] = "✅ Categoría global 'Precios Hot': " . count($productosHotGlobal) . " productos hot encontrados";
                } else {
                    $log[] = "⚠️ Categoría global 'Precios Hot': No se encontraron productos hot";
                }
            } catch (\Exception $e) {
                $totalErrores++;
                $log[] = "❌ Error en categoría global 'Precios Hot': " . $e->getMessage();
                $log[] = "📍 Stack trace: " . $e->getTraceAsString();
            }

            $log[] = "🎉 Proceso completado. Total inserciones: {$totalInserciones}, Total errores: {$totalErrores}";

        } catch (\Exception $e) {
            $totalErrores++;
            $log[] = "❌ Error general en el proceso: " . $e->getMessage();
            $log[] = "📍 Stack trace: " . $e->getTraceAsString();
        }

        $ejecucion->update([
            'total' => $totalCategorias,
            'total_guardado' => $totalInserciones,
            'total_errores' => $totalErrores,
            'log' => $log
        ]);
    }

    private function obtenerProductosHotPorCategoria($categoria, $limite = 20, &$log = [])
    {
        // Obtener todas las categorías hijas (incluyendo la actual)
        $categoriaIds = $this->obtenerCategoriaIdsIncluyendoHijas($categoria->id);
        
        // Obtener productos de estas categorías con sus relaciones
        $productos = Producto::with(['categoria', 'categoriaEspecificaciones'])
            ->select('id', 'nombre', 'imagen_pequena', 'categoria_id', 'slug', 'precio', 'unidadDeMedida', 'rebajado', 'categoria_id_especificaciones_internas', 'categoria_especificaciones_internas_elegidas', 'especificaciones_busqueda')
            ->where('obsoleto', 'no')
            ->whereIn('categoria_id', $categoriaIds)
            ->whereNotNull('precio')
            ->where('precio', '>', 0)
            ->get();
        
        return $this->calcularProductosHot($productos, $limite, $log);
    }

    private function obtenerProductosHotGlobal($limite = 60, &$log = [])
    {
        // Obtener todos los productos con sus relaciones
        $productos = Producto::with(['categoria', 'categoriaEspecificaciones'])
            ->select('id', 'nombre', 'imagen_pequena', 'categoria_id', 'slug', 'precio', 'unidadDeMedida', 'rebajado', 'categoria_id_especificaciones_internas', 'categoria_especificaciones_internas_elegidas', 'especificaciones_busqueda')
            ->whereNotNull('precio')
            ->where('precio', '>', 0)
            ->get();
        
        return $this->calcularProductosHot($productos, $limite, $log);
    }

    private function calcularProductosHot($productos, $limite, &$log = [])
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
                continue;
            }
            
            // Usar el servicio para obtener la oferta más barata con descuentos y chollos aplicados
            $mejorOferta = $servicioOfertas->obtener($producto);
            
            if (!$mejorOferta || $mejorOferta->precio_unidad <= 0) {
                // Limpiar rebajado si no hay oferta válida
                Producto::where('id', $producto->id)->update(['rebajado' => null]);
                continue;
            }

            // Usar el precio_unidad de la oferta procesada (con descuentos y chollos aplicados)
            $precioOferta = $mejorOferta->precio_unidad;
            
            // Validar que el precio de la oferta no sea mayor que el precio medio (evitar descuentos negativos)
            if ($precioOferta > $precioMedio) {
                // Limpiar rebajado si el precio actual es mayor que la media (no es descuento)
                Producto::where('id', $producto->id)->update(['rebajado' => null]);
                continue;
            }
            
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
                if (!$tienda) {
                    continue; // Saltar si no hay tienda
                }
                
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
                    // No añadir sufijo para unidadUnica
                } else {
                    $precioFormateado .= '/Und.';
                }
                
                $productoHotData = [
                    'producto_id' => $producto->id,
                    'oferta_id' => $mejorOferta->id,
                    'tienda_id' => $mejorOferta->tienda_id,
                    'img_tienda' => $tienda->url_imagen ?? null,
                    'img_producto' => (!empty($producto->imagen_pequena) && is_array($producto->imagen_pequena) && isset($producto->imagen_pequena[0]))
    ? $producto->imagen_pequena[0]
    : null,
                    'precio_oferta' => $precioOferta,
                    'precio_formateado' => $precioFormateado,
                    'porcentaje_diferencia' => round($diferencia, 2),
                    'url_oferta' => route('click.redirigir', ['ofertaId' => $mejorOferta->id]),
                    'url_producto' => $this->generarUrlProducto($producto),
                    'producto_nombre' => $producto->nombre,
                    'tienda_nombre' => $tienda->nombre ?? 'Tienda desconocida',
                    'unidades' => $mejorOferta->unidades ?? 1,
                    'unidad_medida' => $unidadMedida,
                    'num_imagenes' => 0,
                    'orden_especificacion' => -1
                ];
                
                $productosHot[] = $productoHotData;
            } else {
                // Si la diferencia es menor al 5%, limpiar rebajado (poner a null)
                Producto::where('id', $producto->id)->update(['rebajado' => null]);
            }
            
            // Calcular precios hot de especificaciones internas si el producto las tiene
            $productosHotEspecificaciones = $this->calcularPreciosHotEspecificacionesInternas($producto, $haceUnMes);
            $productosHot = array_merge($productosHot, $productosHotEspecificaciones);
        }

        // Desduplicar por oferta_id: una oferta solo puede aparecer una vez
        // Prioridad: especificación > producto general; entre especificaciones: más imágenes; empate: orden de especificaciones
        $porOferta = [];
        foreach ($productosHot as $entry) {
            $oid = $entry['oferta_id'];
            $esEspecificacion = ($entry['orden_especificacion'] ?? -1) >= 0;
            $numImagenes = $entry['num_imagenes'] ?? 0;
            $ordenSpec = $entry['orden_especificacion'] ?? PHP_INT_MAX;

            if (!isset($porOferta[$oid])) {
                $porOferta[$oid] = $entry;
            } else {
                $actual = $porOferta[$oid];
                $actualEsSpec = ($actual['orden_especificacion'] ?? -1) >= 0;
                $actualNumImg = $actual['num_imagenes'] ?? 0;
                $actualOrden = $actual['orden_especificacion'] ?? PHP_INT_MAX;

                $reemplazar = false;
                if ($esEspecificacion && !$actualEsSpec) {
                    $reemplazar = true;
                } elseif ($esEspecificacion && $actualEsSpec) {
                    if ($numImagenes > $actualNumImg) {
                        $reemplazar = true;
                    } elseif ($numImagenes === $actualNumImg && $ordenSpec < $actualOrden) {
                        $reemplazar = true;
                    }
                }
                if ($reemplazar) {
                    $porOferta[$oid] = $entry;
                }
            }
        }
        $productosHot = array_values($porOferta);

        // Eliminar campos auxiliares usados solo para desduplicación
        $productosHot = array_map(function ($entry) {
            unset($entry['num_imagenes'], $entry['orden_especificacion']);
            return $entry;
        }, $productosHot);

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

    /**
     * Actualizar índice de búsqueda de especificaciones internas
     * Genera especificaciones_busqueda (JSON) y especificaciones_busqueda_texto (TEXT)
     */
    private function actualizarEspecificacionesBusqueda(Producto $producto)
    {
        // Verificar si el producto tiene especificaciones internas elegidas
        $especificacionesElegidas = $producto->categoria_especificaciones_internas_elegidas;
        if (!$especificacionesElegidas || !is_array($especificacionesElegidas)) {
            // Si no tiene especificaciones, limpiar los campos
            $producto->especificaciones_busqueda = null;
            $producto->especificaciones_busqueda_texto = null;
            $producto->save();
            return;
        }

        // Obtener la categoría de especificaciones internas para acceder a los textos
        $categoriaEspecificaciones = $producto->categoriaEspecificaciones;
        if (!$categoriaEspecificaciones || !$categoriaEspecificaciones->especificaciones_internas) {
            $producto->especificaciones_busqueda = null;
            $producto->especificaciones_busqueda_texto = null;
            $producto->save();
            return;
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

        // Obtener todas las ofertas del producto usando el servicio
        $servicioOfertas = new SacarPrimeraOfertaDeUnProductoAplicadoDescuentosYChollos();
        $todasLasOfertas = $servicioOfertas->obtenerTodas($producto);

        // IMPORTANTE: Preservar los valores de rebajado existentes antes de regenerar
        $rebajadosExistentes = [];
        $especificacionesBusquedaActuales = $producto->especificaciones_busqueda ?? [];
        foreach ($especificacionesBusquedaActuales as $clave => $especificacion) {
            if (isset($especificacion['id']) && isset($especificacion['rebajado'])) {
                $rebajadosExistentes[strval($especificacion['id'])] = $especificacion['rebajado'];
            }
        }

        $especificacionesBusqueda = [];
        $textosBusqueda = [];

        // Iterar sobre cada línea principal en las especificaciones elegidas
        foreach ($especificacionesElegidas as $lineaId => $sublineasProducto) {
            // Saltar claves especiales como '_producto', '_formatos', '_columnas', etc.
            if (strpos($lineaId, '_') === 0) {
                continue;
            }

            // Buscar la línea principal en los filtros combinados (categoría + producto)
            $lineaPrincipal = null;
            foreach ($filtrosCombinados as $filtro) {
                if (isset($filtro['id']) && $filtro['id'] === $lineaId) {
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
                // Verificar si está marcada como "mostrar" (m: 1, m: true, o mostrar: true)
                $esMostrar = false;
                $sublineaId = null;
                $sublineaTexto = null;

                if (is_array($sublineaProducto)) {
                    $sublineaId = $sublineaProducto['id'] ?? null;
                    
                    // Verificar si está marcada como "mostrar"
                    // Acepta: m: 1, m: "1", m: true, mostrar: true
                    $mValue = $sublineaProducto['m'] ?? null;
                    $esMostrar = false;
                    
                    if ($mValue !== null) {
                        // Aceptar 1, "1", true, "true"
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
                
                // Obtener la primera imagen de la sublínea si tiene imágenes
                $primeraImagen = null;
                if ($esMostrar && is_array($sublineaProducto)) {
                    // Verificar si tiene imágenes y no está usando imágenes del producto
                    $usarImagenesProducto = $sublineaProducto['usarImagenesProducto'] ?? false;
                    if (!$usarImagenesProducto && isset($sublineaProducto['img']) && is_array($sublineaProducto['img']) && !empty($sublineaProducto['img'])) {
                        $primeraImagen = $sublineaProducto['img'][0] ?? null;
                    }
                }

                if (!$esMostrar || !$sublineaId) {
                    continue;
                }

                // Buscar el texto de la sublínea en la línea principal
                // Buscar en 'subprincipales' que es donde están las sublíneas
                $subprincipales = $lineaPrincipal['subprincipales'] ?? [];
                foreach ($subprincipales as $subprincipal) {
                    $subprincipalId = is_array($subprincipal) ? ($subprincipal['id'] ?? null) : strval($subprincipal);
                    if (strval($subprincipalId) === strval($sublineaId)) {
                        // Obtener el texto de la sublínea
                        if (is_array($subprincipal)) {
                            // Buscar primero en 'texto', luego en 'slug', luego en 'id'
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
                        break;
                    }
                }

                if (!$sublineaTexto) {
                    continue;
                }
                
                // IMPORTANTE: Convertir el texto a slug para usarlo como clave
                // Esto asegura que "blanco blanco" se convierta a "blanco-blanco"
                // para que coincida con la URL
                $sublineaTextoSlug = \Illuminate\Support\Str::slug($sublineaTexto);

                // Filtrar ofertas que coincidan con esta sublínea específica
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

                // Calcular precio más barato de las ofertas filtradas
                if ($ofertasFiltradas->isNotEmpty()) {
                    $precioMasBarato = $ofertasFiltradas->min('precio_unidad');
                    
                    if ($precioMasBarato !== null && $precioMasBarato > 0) {
                        // IMPORTANTE: Usar el slug normalizado como clave
                        // Esto asegura que "blanco blanco" se guarde como "blanco-blanco"
                        // para que coincida con la URL
                        $claveSlug = $sublineaTextoSlug;
                        
                        // Guardar en JSON usando el slug normalizado como clave
                        // Incluir el texto original para mostrarlo correctamente en el header
                        // Incluir la primera imagen si está disponible
                        $datosEspecificacion = [
                            'id' => strval($sublineaId),
                            'precio_unidad' => round($precioMasBarato, 3),
                            'texto' => $sublineaTexto  // Texto original para mostrar (sin guiones)
                        ];
                        
                        // Preservar el campo rebajado si existe
                        if (isset($rebajadosExistentes[strval($sublineaId)])) {
                            $datosEspecificacion['rebajado'] = $rebajadosExistentes[strval($sublineaId)];
                        }
                        
                        // Añadir la primera imagen si está disponible
                        // Añadir "-thumbnail" antes de la extensión para usar la versión pequeña
                        if ($primeraImagen) {
                            // Procesar la imagen para añadir "-thumbnail" antes de la extensión
                            $imagenConThumbnail = $primeraImagen;
                            $extension = pathinfo($primeraImagen, PATHINFO_EXTENSION);
                            if ($extension) {
                                // Si tiene extensión, reemplazar la extensión con "-thumbnail.extension"
                                $imagenConThumbnail = preg_replace('/\.' . preg_quote($extension, '/') . '$/', '-thumbnail.' . $extension, $primeraImagen);
                            } else {
                                // Si no tiene extensión, añadir "-thumbnail" al final
                                $imagenConThumbnail = $primeraImagen . '-thumbnail';
                            }
                            $datosEspecificacion['imagen'] = $imagenConThumbnail;
                        }
                        
                        $especificacionesBusqueda[$claveSlug] = $datosEspecificacion;

                        // Añadir el slug normalizado a la lista para concatenar (para búsqueda fulltext)
                        $textosBusqueda[] = $claveSlug;
                    }
                }
            }
        }

        // Guardar en el producto
        $producto->especificaciones_busqueda = !empty($especificacionesBusqueda) ? $especificacionesBusqueda : null;
        $producto->especificaciones_busqueda_texto = !empty($textosBusqueda) ? implode(' ', $textosBusqueda) : null;
        $producto->save();
    }

    /**
     * Calcula precios hot para las especificaciones internas de un producto
     */
    private function calcularPreciosHotEspecificacionesInternas($producto, $haceUnMes)
    {
        $productosHot = [];
        
        // Solo procesar si el producto tiene especificaciones internas y está marcado como unidadUnica
        if (
            !$producto->categoria_id_especificaciones_internas || 
            !$producto->categoria_especificaciones_internas_elegidas ||
            $producto->unidadDeMedida !== 'unidadUnica'
        ) {
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

        $ordenEspecificacion = 0;

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
                // Buscar por ID primero (más confiable), luego por slug como fallback
                // El ID que buscamos es $sublineaId (ya disponible en este punto)
                $claveEncontrada = null;
                foreach ($especificacionesBusqueda as $clave => $especificacion) {
                    if (isset($especificacion['id']) && strval($especificacion['id']) === strval($sublineaId)) {
                        $claveEncontrada = $clave;
                        break;
                    }
                }
                
                // Si no se encontró por ID, intentar por slug como fallback
                if (!$claveEncontrada && isset($especificacionesBusqueda[$sublineaTextoSlug])) {
                    $claveEncontrada = $sublineaTextoSlug;
                }
                
                // Actualizar especificaciones_busqueda si existe esta especificación
                if ($claveEncontrada) {
                    $especificacionActual = $especificacionesBusqueda[$claveEncontrada];
                    $rebajadoAnterior = $especificacionActual['rebajado'] ?? 0;
                    
                    // Solo actualizar si ha cambiado
                    if ($rebajadoAnterior != $rebajado) {
                        $especificacionesBusqueda[$claveEncontrada]['rebajado'] = $rebajado;
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
                    $imagenProducto = $primeraImagen ?? (is_array($producto->imagen_pequena) ? ($producto->imagen_pequena[0] ?? null) : $producto->imagen_pequena) ?? null;
                    
                    // Nombre del producto con la especificación
                    $nombreCompleto = $producto->nombre . ' ' . $sublineaTexto;

                    // Contar imágenes para desduplicación: si usa imag. producto, contar las del producto
                    $usarImagenesProducto = is_array($sublineaProducto) && ($sublineaProducto['usarImagenesProducto'] ?? false);
                    if ($usarImagenesProducto) {
                        $imagenesProducto = $producto->imagen_pequena ?? [];
                        $numImagenes = is_array($imagenesProducto) ? count($imagenesProducto) : (!empty($imagenesProducto) ? 1 : 0);
                    } else {
                        $numImagenes = is_array($sublineaProducto) && isset($sublineaProducto['img']) && is_array($sublineaProducto['img'])
                            ? count($sublineaProducto['img'])
                            : 0;
                    }
                    
                    $productosHot[] = [
                        'producto_id' => $producto->id,
                        'oferta_id' => $mejorOfertaEspecificacion->id,
                        'tienda_id' => $mejorOfertaEspecificacion->tienda_id,
                        'img_tienda' => $tienda->url_imagen ?? null,
                        'img_producto' => $imagenProducto,
                        'precio_oferta' => $precioOfertaEspecificacion,
                        'precio_formateado' => $precioFormateado,
                        'porcentaje_diferencia' => round($diferencia, 2),
                        'url_oferta' => route('click.redirigir', ['ofertaId' => $mejorOfertaEspecificacion->id]),
                        'url_producto' => $urlProducto,
                        'producto_nombre' => $nombreCompleto,
                        'tienda_nombre' => $tienda->nombre ?? 'Tienda desconocida',
                        'unidades' => $mejorOfertaEspecificacion->unidades ?? 1,
                        'unidad_medida' => $unidadMedida,
                        'num_imagenes' => $numImagenes,
                        'orden_especificacion' => $ordenEspecificacion++
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
                // Buscar por ID primero (más confiable), luego por slug como fallback
                // El ID que buscamos es $especificacionId (que es $sublineaId)
                $claveEncontrada = null;
                foreach ($especificacionesBusqueda as $clave => $especificacion) {
                    if (isset($especificacion['id']) && strval($especificacion['id']) === strval($especificacionId)) {
                        $claveEncontrada = $clave;
                        break;
                    }
                }
                
                // Si no se encontró por ID, intentar por slug como fallback
                if (!$claveEncontrada && isset($especificacionesBusqueda[$sublineaTextoSlug])) {
                    $claveEncontrada = $sublineaTextoSlug;
                }
                
                if ($claveEncontrada) {
                    $rebajadoAnterior = $especificacionesBusqueda[$claveEncontrada]['rebajado'] ?? 0;
                    if ($rebajadoAnterior != $rebajado) {
                        $especificacionesBusqueda[$claveEncontrada]['rebajado'] = $rebajado;
                        $necesitaActualizarEspecificacionesBusqueda = true;
                    }
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
} 