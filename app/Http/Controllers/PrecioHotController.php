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
use App\Services\SacarPrimeraOfertaDeUnProductoAplicadoDescuentosYChollos;
use Carbon\Carbon;

class PrecioHotController extends Controller
{
    /** IDs de producto con análisis detallado en la respuesta del navegador */
    private const PRODUCTOS_DEPURACION_FOCALIZADA = [107];
    /** @var bool Incluir detalle de depuración solo en la respuesta JSON del navegador */
    private bool $incluirDetalleDepuracionEnRespuesta = false;

    /** @var array Resultado del listado global de esta ejecución (top 60) */
    private array $ultimoListadoGlobalPreciosHot = [];

    /** @var bool Recopilar motivos de rechazo durante el paso global (solo navegador) */
    private bool $recopilarDepuracionGlobal = false;

    /** @var array Informe de depuración para la respuesta JSON */
    private array $depuracionGlobal = [];

    private const DEPURACION_MAX_MUESTRAS_POR_MOTIVO = 8;

    public function index()
    {
        $preciosHot = PrecioHot::orderBy('created_at', 'desc')->get();
        return view('admin.precios-hot.index', compact('preciosHot'));
    }

    public function ejecutarSegundoPlano(Request $request)
    {
        @set_time_limit(300);

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
            $this->incluirDetalleDepuracionEnRespuesta = true;

            $this->procesarPreciosHotCompleto($ejecucion);
            
            $ejecucion->update([
                'fin' => now()
            ]);

            // Recargar la ejecución para obtener los logs actualizados
            $ejecucion->refresh();

            $productosHotDepuracion = $this->incluirDetalleDepuracionEnRespuesta
                ? $this->construirDetalleDepuracionDesdeListadoGlobal()
                : [];

            if ($this->incluirDetalleDepuracionEnRespuesta) {
                $this->depuracionGlobal['productos_focalizados'] = [];
                foreach (self::PRODUCTOS_DEPURACION_FOCALIZADA as $productoFocalId) {
                    $this->depuracionGlobal['productos_focalizados'][$productoFocalId] =
                        $this->generarAnalisisProductoFocalizado($productoFocalId);
                }
            }

            return response()->json([
                'status' => 'ok',
                'inserciones' => $ejecucion->total_guardado,
                'errores' => $ejecucion->total_errores,
                'total_categorias' => $ejecucion->total,
                'total_inserciones' => $ejecucion->total_guardado,
                'total_errores' => $ejecucion->total_errores,
                'log' => $ejecucion->log ?? [],
                'productos_hot' => $productosHotDepuracion,
                'total_productos_hot' => count($productosHotDepuracion),
                'depuracion' => $this->depuracionGlobal,
                'ejecucion_id' => $ejecucion->id,
            ]);
        } catch (\Exception $e) {
            $ejecucion->update([
                'fin' => now(),
                'total_errores' => ($ejecucion->total_errores ?? 0) + 1
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Error en el proceso: ' . $e->getMessage()
            ]);
        } finally {
            $this->incluirDetalleDepuracionEnRespuesta = false;
            $this->recopilarDepuracionGlobal = false;
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
        @set_time_limit(300);

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
                if ($this->incluirDetalleDepuracionEnRespuesta) {
                    $this->recopilarDepuracionGlobal = true;
                }
                $productosHotGlobal = $this->obtenerProductosHotGlobal(60, $log);
                $this->recopilarDepuracionGlobal = false;
                $this->ultimoListadoGlobalPreciosHot = $productosHotGlobal;

                PrecioHot::updateOrCreate(
                    ['nombre' => 'Precios Hot'],
                    ['datos' => $productosHotGlobal]
                );

                if (!empty($productosHotGlobal)) {
                    $totalInserciones++;
                    $log[] = "✅ Categoría global 'Precios Hot': " . count($productosHotGlobal) . " productos hot en listado (top 60)";
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

        if ($this->recopilarDepuracionGlobal) {
            $this->inicializarDepuracionGlobal($productos->count());
        }

        $resultado = $this->calcularProductosHot($productos, $limite, $log);

        if ($this->recopilarDepuracionGlobal) {
            $this->finalizarDepuracionGlobal(count($resultado));
            foreach ($this->depuracionGlobal['resumen_texto'] ?? [] as $linea) {
                $log[] = $linea;
            }
        }

        return $resultado;
    }

    private function tieneEspecificacionesUnidadUnica(Producto $producto): bool
    {
        return $producto->unidadDeMedida === 'unidadUnica'
            && $producto->categoria_id_especificaciones_internas
            && $producto->categoria_especificaciones_internas_elegidas;
    }

    private function calcularProductosHot($productos, $limite, &$log = [])
    {
        $productosHot = [];
        $servicioOfertas = new SacarPrimeraOfertaDeUnProductoAplicadoDescuentosYChollos();

        foreach ($productos as $producto) {
            if ($this->tieneEspecificacionesUnidadUnica($producto)) {
                $this->incrementarDepuracion('productos_unidad_unica');
                $hotSpecs = $this->calcularPreciosHotEspecificacionesInternas($producto);
                $productosHot = array_merge($productosHot, $hotSpecs);
                if ($this->recopilarDepuracionGlobal && count($hotSpecs) > 0) {
                    $this->incrementarDepuracion('hot_especificacion', count($hotSpecs));
                }
                continue;
            }

            $this->incrementarDepuracion('evaluados_general');

            $mejorOferta = $servicioOfertas->obtener($producto);

            if (!$mejorOferta || $mejorOferta->precio_unidad <= 0) {
                Producto::where('id', $producto->id)->update(['rebajado' => null]);
                $this->registrarMuestraDepuracion('general_sin_oferta', [
                    'producto_id' => $producto->id,
                    'nombre' => $producto->nombre,
                ]);
                continue;
            }

            $precioOferta = $mejorOferta->precio_unidad;

            $precioMinimoHistorico = HistoricoPrecioProducto::precioMinimoReferenciaUltimosMeses(
                $producto->id,
                null,
                3,
                $precioOferta
            );
            $filasHistoricoGeneral = $this->contarFilasHistorico($producto->id, null, $precioOferta);

            if ($precioMinimoHistorico === null || $precioMinimoHistorico <= 0) {
                Producto::where('id', $producto->id)->update(['rebajado' => null]);
                $this->registrarMuestraDepuracion('general_sin_historico_3m', [
                    'producto_id' => $producto->id,
                    'nombre' => $producto->nombre,
                    'filas_historico_3m' => $filasHistoricoGeneral,
                    'precio_producto' => $producto->precio,
                    'precio_oferta' => round($precioOferta, 3),
                ]);
                continue;
            }

            if ($precioOferta > $precioMinimoHistorico) {
                Producto::where('id', $producto->id)->update(['rebajado' => null]);
                $this->registrarMuestraDepuracion('general_precio_superior_al_minimo', [
                    'producto_id' => $producto->id,
                    'nombre' => $producto->nombre,
                    'precio_minimo_historico' => round($precioMinimoHistorico, 3),
                    'precio_oferta' => round($precioOferta, 3),
                    'oferta_id' => $mejorOferta->id,
                ]);
                continue;
            }

            $diferencia = (($precioMinimoHistorico - $precioOferta) / $precioMinimoHistorico) * 100;

            if ($diferencia < HistoricoPrecioProducto::REBAJA_MINIMA_PCT_HOT) {
                Producto::where('id', $producto->id)->update(['rebajado' => null]);
                $this->registrarMuestraDepuracion('general_rebaja_menor_umbral', [
                    'producto_id' => $producto->id,
                    'nombre' => $producto->nombre,
                    'precio_minimo_historico' => round($precioMinimoHistorico, 3),
                    'precio_oferta' => round($precioOferta, 3),
                    'rebaja_pct' => round($diferencia, 2),
                    'oferta_id' => $mejorOferta->id,
                ]);
                continue;
            }

            $diasRachaPrecioBajo = HistoricoPrecioProducto::diasConsecutivosPrecioActualAntesDeHoy(
                $producto->id,
                null,
                $precioOferta
            );
            if ($diasRachaPrecioBajo > HistoricoPrecioProducto::DIAS_RACHA_OFERTA_PASADA_HOT) {
                Producto::where('id', $producto->id)->update(['rebajado' => null]);
                $this->registrarMuestraDepuracion('general_oferta_pasada_racha', [
                    'producto_id' => $producto->id,
                    'nombre' => $producto->nombre,
                    'precio_minimo_historico' => round($precioMinimoHistorico, 3),
                    'precio_oferta' => round($precioOferta, 3),
                    'rebaja_pct' => round($diferencia, 2),
                    'dias_racha_precio_bajo' => $diasRachaPrecioBajo,
                    'umbral_dias' => HistoricoPrecioProducto::DIAS_RACHA_OFERTA_PASADA_HOT,
                    'oferta_id' => $mejorOferta->id,
                ]);
                continue;
            }

            $porcentajeRebajado = (int) round($diferencia);
            Producto::where('id', $producto->id)->update(['rebajado' => $porcentajeRebajado]);

            $tienda = $mejorOferta->tienda;
            if (!$tienda) {
                $this->registrarMuestraDepuracion('general_sin_tienda', [
                    'producto_id' => $producto->id,
                    'nombre' => $producto->nombre,
                    'oferta_id' => $mejorOferta->id,
                    'rebaja_pct' => round($diferencia, 2),
                ]);
                continue;
            }

            $this->incrementarDepuracion('hot_general');
            $this->registrarMuestraDepuracion('general_hot_ok', [
                'producto_id' => $producto->id,
                'nombre' => $producto->nombre,
                'precio_minimo_historico' => round($precioMinimoHistorico, 3),
                'precio_oferta' => round($precioOferta, 3),
                'rebaja_pct' => round($diferencia, 2),
                'oferta_id' => $mejorOferta->id,
            ], false);

            $unidadMedida = $producto->unidadDeMedida ?? 'unidad';
            $decimalesPrecio = ($unidadMedida === 'unidadMilesima') ? 3 : 2;
            $precioFormateado = number_format($precioOferta, $decimalesPrecio, ',', '.') . ' €';

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
            } elseif ($unidadMedida !== 'unidadUnica') {
                $precioFormateado .= '/Und.';
            }

            $productosHot[] = [
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
                // Bajada de precio justo hoy (ayer no estaba a este precio), aunque hace semanas sí lo estuviera
                'nuevo_hoy' => $diasRachaPrecioBajo === 0,
                'url_oferta' => route('click.redirigir', ['ofertaId' => $mejorOferta->id]),
                'url_producto' => $this->generarUrlProducto($producto),
                'producto_nombre' => $producto->nombre,
                'tienda_nombre' => $tienda->nombre ?? 'Tienda desconocida',
                'unidades' => $mejorOferta->unidades ?? 1,
                'unidad_medida' => $unidadMedida,
                'num_imagenes' => 0,
                'orden_especificacion' => -1,
            ];
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

    /**
     * Detalle solo para la respuesta del navegador: mismo listado global guardado (top 60)
     * con columnas extra de depuración, sin modificar lo persistido en precios_hot.
     */
    private function construirDetalleDepuracionDesdeListadoGlobal(): array
    {
        $detalle = [];
        foreach ($this->ultimoListadoGlobalPreciosHot as $entry) {
            $detalle[] = $this->enriquecerEntradaParaDepuracion($entry);
        }

        return $detalle;
    }

    private function enriquecerEntradaParaDepuracion(array $entry): array
    {
        $productoId = (int) ($entry['producto_id'] ?? 0);
        if ($productoId <= 0) {
            return $entry;
        }

        $producto = Producto::find($productoId);
        if (!$producto) {
            return array_merge($entry, [
                'tipo' => 'desconocido',
                'precio_minimo_historico' => null,
            ]);
        }

        $urlBase = $this->generarUrlProducto($producto);
        $urlProducto = $entry['url_producto'] ?? '';
        $especificacionInternaId = null;
        $tipo = 'general';

        if ($urlProducto !== $urlBase && str_starts_with($urlProducto, $urlBase . '/')) {
            $tipo = 'especificacion';
            $slugEspec = substr($urlProducto, strlen($urlBase) + 1);
            $busqueda = $producto->especificaciones_busqueda ?? [];
            if (isset($busqueda[$slugEspec]['id'])) {
                $especificacionInternaId = (string) $busqueda[$slugEspec]['id'];
            }
        }

        $precioOfertaEntrada = isset($entry['precio_oferta']) ? (float) $entry['precio_oferta'] : null;
        $precioMinimoHistorico = HistoricoPrecioProducto::precioMinimoReferenciaUltimosMeses(
            $productoId,
            $especificacionInternaId,
            3,
            $precioOfertaEntrada
        );

        return array_merge($entry, [
            'tipo' => $tipo,
            'precio_minimo_historico' => $precioMinimoHistorico !== null ? round($precioMinimoHistorico, 3) : null,
        ]);
    }

    private function inicializarDepuracionGlobal(int $totalProductosConPrecio): void
    {
        $this->depuracionGlobal = [
            'criterios' => [
                'ventana' => 'últimos 3 meses',
                'referencia' => 'mínimo de precio_minimo en histórico (excluye 0, hoy y racha de días previos con el mismo precio que la oferta actual)',
                'rebaja_minima_pct' => HistoricoPrecioProducto::REBAJA_MINIMA_PCT_HOT,
                'oferta_pasada_racha' => 'si el precio bajo lleva más de ' . HistoricoPrecioProducto::DIAS_RACHA_OFERTA_PASADA_HOT . ' días consecutivos (sin contar hoy), no entra en hot',
                'precio_actual' => 'mejor oferta con descuentos/chollos',
                'unidad_unica' => 'solo evalúa variantes marcadas como mostrar',
            ],
            'productos_con_precio_en_cola' => $totalProductosConPrecio,
            'contadores' => [],
            'muestras' => [],
            'resumen_texto' => [],
        ];
    }

    private function finalizarDepuracionGlobal(int $hotEnListadoTop60): void
    {
        $c = $this->depuracionGlobal['contadores'] ?? [];
        $this->depuracionGlobal['hot_en_listado_top60'] = $hotEnListadoTop60;
        $this->depuracionGlobal['resumen_texto'] = [
            '📊 Depuración global — productos en cola: ' . ($this->depuracionGlobal['productos_con_precio_en_cola'] ?? 0),
            '   · unidadUnica (solo variantes): ' . ($c['productos_unidad_unica'] ?? 0),
            '   · evaluados como general: ' . ($c['evaluados_general'] ?? 0),
            '   · variantes evaluadas (mostrar): ' . ($c['variantes_evaluadas'] ?? 0),
            '   · HOT general detectados: ' . ($c['hot_general'] ?? 0),
            '   · HOT variante detectados: ' . ($c['hot_especificacion'] ?? 0),
            '   · En listado final (top 60): ' . $hotEnListadoTop60,
            '   · Rechazos general sin histórico 3m: ' . ($c['rechazos_general_sin_historico_3m'] ?? 0),
            '   · Rechazos general sin oferta: ' . ($c['rechazos_general_sin_oferta'] ?? 0),
            '   · Rechazos general precio > mínimo: ' . ($c['rechazos_general_precio_superior_al_minimo'] ?? 0),
            '   · Rechazos general rebaja <' . HistoricoPrecioProducto::REBAJA_MINIMA_PCT_HOT . '%: ' . ($c['rechazos_general_rebaja_menor_umbral'] ?? $c['rechazos_general_rebaja_menor_5'] ?? 0),
            '   · Rechazos general oferta pasada (racha >' . HistoricoPrecioProducto::DIAS_RACHA_OFERTA_PASADA_HOT . ' días): ' . ($c['rechazos_general_oferta_pasada_racha'] ?? 0),
            '   · Variantes omitidas (no mostrar): ' . ($c['rechazos_spec_omitida_no_mostrar'] ?? 0),
            '   · Rechazos variante sin histórico 3m: ' . ($c['rechazos_spec_sin_historico_3m'] ?? 0),
            '   · Rechazos variante sin oferta: ' . ($c['rechazos_spec_sin_oferta'] ?? 0),
            '   · Rechazos variante precio > mínimo: ' . ($c['rechazos_spec_precio_superior_al_minimo'] ?? 0),
            '   · Rechazos variante rebaja <' . HistoricoPrecioProducto::REBAJA_MINIMA_PCT_HOT . '%: ' . ($c['rechazos_spec_rebaja_menor_umbral'] ?? $c['rechazos_spec_rebaja_menor_5'] ?? 0),
            '   · Rechazos variante oferta pasada (racha >' . HistoricoPrecioProducto::DIAS_RACHA_OFERTA_PASADA_HOT . ' días): ' . ($c['rechazos_spec_oferta_pasada_racha'] ?? 0),
        ];
    }

    private function incrementarDepuracion(string $clave, int $cantidad = 1): void
    {
        if (!$this->recopilarDepuracionGlobal) {
            return;
        }
        $this->depuracionGlobal['contadores'][$clave] = ($this->depuracionGlobal['contadores'][$clave] ?? 0) + $cantidad;
    }

    private function registrarMuestraDepuracion(string $motivo, array $fila, bool $esRechazo = true): void
    {
        if (!$this->recopilarDepuracionGlobal) {
            return;
        }
        if ($esRechazo) {
            $this->incrementarDepuracion('rechazos_' . $motivo);
        }
        if (!isset($this->depuracionGlobal['muestras'][$motivo])) {
            $this->depuracionGlobal['muestras'][$motivo] = [];
        }
        if (count($this->depuracionGlobal['muestras'][$motivo]) >= self::DEPURACION_MAX_MUESTRAS_POR_MOTIVO) {
            return;
        }
        $this->depuracionGlobal['muestras'][$motivo][] = $fila;
    }

    private function contarFilasHistorico(int $productoId, ?string $especificacionInternaId, ?float $precioActual = null): int
    {
        return HistoricoPrecioProducto::calcularReferenciaHistorica(
            $productoId,
            $especificacionInternaId,
            3,
            $precioActual
        )['filas_usadas_en_referencia'];
    }

    private function obtenerDetalleHistorico3m(int $productoId, ?string $especificacionInternaId, ?float $precioActual = null): array
    {
        $desde = Carbon::now()->subMonths(3)->startOfDay();
        $precioPorFecha = HistoricoPrecioProducto::mapaPrecioMinimoPorFecha(
            $productoId,
            $especificacionInternaId,
            $desde
        );
        $fechasExcluidas = HistoricoPrecioProducto::fechasExcluidasDesdeMapa(
            $precioPorFecha,
            $desde,
            $precioActual
        );
        $excluidasLookup = array_fill_keys($fechasExcluidas, true);

        $precioMinimo = null;
        $filasUsadas = 0;
        $preciosReferencia = [];

        foreach ($precioPorFecha as $fecha => $precio) {
            if (isset($excluidasLookup[$fecha])) {
                continue;
            }
            $filasUsadas++;
            $preciosReferencia[] = $precio;
            if ($precioMinimo === null || $precio < $precioMinimo) {
                $precioMinimo = $precio;
            }
        }

        $filasTotales = collect($precioPorFecha)
            ->sortKeysDesc()
            ->map(fn ($precio, $fecha) => (object) ['fecha' => $fecha, 'precio_minimo' => $precio]);

        return [
            'desde' => $desde->toDateString(),
            'hasta' => now()->toDateString(),
            'especificacion_interna_id' => $especificacionInternaId,
            'precio_actual_oferta' => $precioActual !== null ? round($precioActual, 3) : null,
            'fechas_excluidas_racha' => $fechasExcluidas,
            'filas_con_precio' => count($precioPorFecha),
            'filas_usadas_en_referencia' => $filasUsadas,
            'precio_minimo_calculado' => $precioMinimo !== null ? round($precioMinimo, 3) : null,
            'precio_maximo_en_ventana' => $preciosReferencia !== [] ? round(max($preciosReferencia), 3) : null,
            'ultimas_filas' => $filasTotales->take(20)->map(fn ($f) => [
                'fecha' => $f->fecha instanceof \DateTimeInterface ? $f->fecha->format('Y-m-d') : (string) $f->fecha,
                'precio_minimo' => round((float) $f->precio_minimo, 3),
                'excluida_racha' => isset($excluidasLookup[
                    $f->fecha instanceof \DateTimeInterface ? $f->fecha->format('Y-m-d') : (string) $f->fecha
                ]),
            ])->values()->all(),
        ];
    }

    /**
     * Análisis paso a paso de un producto (solo respuesta navegador).
     */
    private function generarAnalisisProductoFocalizado(int $productoId): array
    {
        $producto = Producto::with(['categoria', 'categoriaEspecificaciones'])->find($productoId);

        if (!$producto) {
            return ['error' => 'Producto no encontrado', 'producto_id' => $productoId];
        }

        $enListadoGlobal = collect($this->ultimoListadoGlobalPreciosHot)->contains(
            fn ($e) => (int) ($e['producto_id'] ?? 0) === $productoId
        );

        $analisis = [
            'producto_id' => $productoId,
            'nombre' => $producto->nombre,
            'slug' => $producto->slug,
            'precio_campo_producto' => $producto->precio,
            'unidadDeMedida' => $producto->unidadDeMedida,
            'obsoleto' => $producto->obsoleto ?? null,
            'en_cola_global_precio_mayor_0' => $producto->precio !== null && $producto->precio > 0,
            'tiene_especificaciones_unidad_unica' => $this->tieneEspecificacionesUnidadUnica($producto),
            'entro_en_listado_hot_top60_esta_ejecucion' => $enListadoGlobal,
            'historico_general_3m' => null,
            'variantes' => [],
            'general' => null,
            'conclusion' => null,
        ];

        $servicioOfertas = new SacarPrimeraOfertaDeUnProductoAplicadoDescuentosYChollos();

        if (!$analisis['en_cola_global_precio_mayor_0']) {
            $analisis['conclusion'] = 'Excluido del paso global: precio del producto null o 0.';
            return $analisis;
        }

        if ($analisis['tiene_especificaciones_unidad_unica']) {
            $especificacionesElegidas = $producto->categoria_especificaciones_internas_elegidas;
            $categoriaEspecificaciones = $producto->categoriaEspecificaciones;
            $filtrosCombinados = [];

            if ($categoriaEspecificaciones && $categoriaEspecificaciones->especificaciones_internas) {
                $filtros = $categoriaEspecificaciones->especificaciones_internas['filtros'] ?? [];
                $filtrosProducto = $especificacionesElegidas['_producto']['filtros'] ?? [];
                $filtrosCombinados = array_merge($filtros, is_array($filtrosProducto) ? $filtrosProducto : []);
            }

            $todasLasOfertas = $servicioOfertas->obtenerTodas($producto);
            $analisis['total_ofertas_producto'] = $todasLasOfertas->count();

            foreach ($especificacionesElegidas as $lineaId => $sublineasProducto) {
                if (strpos((string) $lineaId, '_') === 0) {
                    continue;
                }

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

                $sublineasArray = is_array($sublineasProducto) ? $sublineasProducto : [$sublineasProducto];

                foreach ($sublineasArray as $sublineaProducto) {
                    $analisis['variantes'][] = $this->evaluarVarianteProductoFocalizado(
                        $producto,
                        $lineaId,
                        $sublineaProducto,
                        $lineaPrincipal,
                        $todasLasOfertas
                    );
                }
            }

            $variantesHot = array_filter($analisis['variantes'], fn ($v) => ($v['es_hot'] ?? false) === true);
            $analisis['conclusion'] = count($variantesHot) > 0
                ? 'Hay ' . count($variantesHot) . ' variante(s) que cumplen criterio hot en este análisis.'
                : 'Ninguna variante con mostrar cumple hot (rebaja >= ' . HistoricoPrecioProducto::REBAJA_MINIMA_PCT_HOT . '% bajo mínimo 3m). Revisa motivo en cada variante.';

            return $analisis;
        }

        $mejorOferta = $servicioOfertas->obtener($producto);
        $precioOfertaGeneral = ($mejorOferta && $mejorOferta->precio_unidad > 0)
            ? (float) $mejorOferta->precio_unidad
            : null;
        $analisis['historico_general_3m'] = $this->obtenerDetalleHistorico3m($productoId, null, $precioOfertaGeneral);

        $precioMinimoHistorico = HistoricoPrecioProducto::precioMinimoReferenciaUltimosMeses(
            $productoId,
            null,
            3,
            $precioOfertaGeneral
        );
        $general = [
            'precio_minimo_historico_3m' => $precioMinimoHistorico !== null ? round($precioMinimoHistorico, 3) : null,
            'mejor_oferta_id' => $mejorOferta?->id,
            'precio_oferta' => $precioOfertaGeneral !== null ? round($precioOfertaGeneral, 3) : null,
            'es_hot' => false,
            'motivo' => null,
            'rebaja_pct' => null,
        ];

        if (!$mejorOferta || $mejorOferta->precio_unidad <= 0) {
            $general['motivo'] = 'sin_oferta';
        } elseif ($precioMinimoHistorico === null || $precioMinimoHistorico <= 0) {
            $general['motivo'] = 'sin_historico_3m';
        } else {
            $precioOferta = (float) $mejorOferta->precio_unidad;
            if ($precioOferta > $precioMinimoHistorico) {
                $general['motivo'] = 'precio_superior_al_minimo';
                $general['rebaja_pct'] = round((($precioMinimoHistorico - $precioOferta) / $precioMinimoHistorico) * 100, 2);
            } else {
                $diferencia = (($precioMinimoHistorico - $precioOferta) / $precioMinimoHistorico) * 100;
                $general['rebaja_pct'] = round($diferencia, 2);
                if ($diferencia < HistoricoPrecioProducto::REBAJA_MINIMA_PCT_HOT) {
                    $general['motivo'] = 'rebaja_menor_umbral';
                } elseif (HistoricoPrecioProducto::esOfertaPasadaDeModaParaHot($productoId, null, $precioOferta)) {
                    $general['motivo'] = 'oferta_pasada_racha';
                    $general['dias_racha_precio_bajo'] = HistoricoPrecioProducto::diasConsecutivosPrecioActualAntesDeHoy(
                        $productoId,
                        null,
                        $precioOferta
                    );
                } elseif (!$mejorOferta->tienda) {
                    $general['motivo'] = 'sin_tienda';
                } else {
                    $general['motivo'] = 'hot_ok';
                    $general['es_hot'] = true;
                }
            }
        }

        $analisis['general'] = $general;
        $analisis['conclusion'] = $general['es_hot']
            ? 'Producto general cumple hot.'
            : 'Producto general NO hot: ' . ($general['motivo'] ?? 'desconocido');

        return $analisis;
    }

    private function evaluarVarianteProductoFocalizado(
        Producto $producto,
        $lineaId,
        $sublineaProducto,
        array $lineaPrincipal,
        $todasLasOfertas
    ): array {
        $esMostrar = false;
        $sublineaId = null;

        if (is_array($sublineaProducto)) {
            $sublineaId = $sublineaProducto['id'] ?? null;
            $mValue = $sublineaProducto['m'] ?? null;
            if ($mValue !== null) {
                $esMostrar = ($mValue === 1 || $mValue === '1' || $mValue === true || $mValue === 'true');
            }
            if (!$esMostrar && isset($sublineaProducto['mostrar'])) {
                $esMostrar = ($sublineaProducto['mostrar'] === true || $sublineaProducto['mostrar'] === 'true'
                    || $sublineaProducto['mostrar'] === 1 || $sublineaProducto['mostrar'] === '1');
            }
        } else {
            $sublineaId = strval($sublineaProducto);
        }

        $sublineaTexto = null;
        $subprincipales = $lineaPrincipal['subprincipales'] ?? [];
        foreach ($subprincipales as $subprincipal) {
            $subprincipalId = is_array($subprincipal) ? ($subprincipal['id'] ?? null) : strval($subprincipal);
            if (strval($subprincipalId) === strval($sublineaId)) {
                if (is_array($subprincipal)) {
                    $sublineaTexto = $subprincipal['texto'] ?? $subprincipal['slug'] ?? strval($subprincipalId);
                } else {
                    $sublineaTexto = strval($subprincipal);
                }
                if (is_array($sublineaProducto) && !empty($sublineaProducto['textoAlternativo'])) {
                    $sublineaTexto = $sublineaProducto['textoAlternativo'];
                }
                break;
            }
        }

        $resultado = [
            'variante' => $sublineaTexto,
            'especificacion_id' => $sublineaId,
            'linea_id' => $lineaId,
            'marcada_mostrar' => $esMostrar,
            'es_hot' => false,
            'motivo' => null,
            'rebaja_pct' => null,
            'precio_minimo_historico_3m' => null,
            'precio_oferta' => null,
            'oferta_id' => null,
            'ofertas_coincidentes' => 0,
            'historico_3m' => null,
        ];

        if (!$esMostrar || !$sublineaId) {
            $resultado['motivo'] = 'omitida_no_mostrar';
            return $resultado;
        }

        if (!$sublineaTexto) {
            $resultado['motivo'] = 'sin_texto_variante';
            return $resultado;
        }

        $specId = (string) $sublineaId;

        $ofertasFiltradas = $todasLasOfertas->filter(function ($oferta) use ($lineaId, $sublineaId) {
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

        $resultado['ofertas_coincidentes'] = $ofertasFiltradas->count();

        if ($ofertasFiltradas->isEmpty()) {
            $resultado['motivo'] = 'sin_oferta_para_variante';
            return $resultado;
        }

        $mejorOferta = $ofertasFiltradas->first();
        $precioOferta = (float) $mejorOferta->precio_unidad;
        $resultado['precio_oferta'] = round($precioOferta, 3);
        $resultado['oferta_id'] = $mejorOferta->id;

        if ($precioOferta <= 0) {
            $resultado['motivo'] = 'oferta_precio_invalido';
            return $resultado;
        }

        $historico = $this->obtenerDetalleHistorico3m($producto->id, $specId, $precioOferta);
        $resultado['historico_3m'] = $historico;
        $precioMinimoHistorico = $historico['precio_minimo_calculado'];
        $resultado['precio_minimo_historico_3m'] = $precioMinimoHistorico;

        if ($precioMinimoHistorico === null || $precioMinimoHistorico <= 0) {
            $resultado['motivo'] = 'sin_historico_3m';
            return $resultado;
        }

        if ($precioOferta > $precioMinimoHistorico) {
            $resultado['motivo'] = 'precio_superior_al_minimo';
            $resultado['rebaja_pct'] = round((($precioMinimoHistorico - $precioOferta) / $precioMinimoHistorico) * 100, 2);
            return $resultado;
        }

        $diferencia = (($precioMinimoHistorico - $precioOferta) / $precioMinimoHistorico) * 100;
        $resultado['rebaja_pct'] = round($diferencia, 2);

        if ($diferencia < HistoricoPrecioProducto::REBAJA_MINIMA_PCT_HOT) {
            $resultado['motivo'] = 'rebaja_menor_umbral';
            return $resultado;
        }

        if (HistoricoPrecioProducto::esOfertaPasadaDeModaParaHot($producto->id, $specId, $precioOferta)) {
            $resultado['motivo'] = 'oferta_pasada_racha';
            $resultado['dias_racha_precio_bajo'] = HistoricoPrecioProducto::diasConsecutivosPrecioActualAntesDeHoy(
                $producto->id,
                $specId,
                $precioOferta
            );
            return $resultado;
        }

        if (!$mejorOferta->tienda) {
            $resultado['motivo'] = 'sin_tienda';
            return $resultado;
        }

        $resultado['motivo'] = 'hot_ok';
        $resultado['es_hot'] = true;

        return $resultado;
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
    private function calcularPreciosHotEspecificacionesInternas($producto)
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
                    $this->registrarMuestraDepuracion('spec_omitida_no_mostrar', [
                        'producto_id' => $producto->id,
                        'nombre' => $producto->nombre,
                        'especificacion_id' => $sublineaId,
                        'linea_id' => $lineaId,
                    ]);
                    continue;
                }

                $this->incrementarDepuracion('variantes_evaluadas');
                
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
                    $this->registrarMuestraDepuracion('spec_sin_texto', [
                        'producto_id' => $producto->id,
                        'nombre' => $producto->nombre,
                        'especificacion_id' => $sublineaId,
                    ]);
                    continue;
                }
                
                // Convertir el texto a slug para usarlo en la URL
                $sublineaTextoSlug = \Illuminate\Support\Str::slug($sublineaTexto);
                
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
                    $this->registrarMuestraDepuracion('spec_sin_oferta', [
                        'producto_id' => $producto->id,
                        'nombre' => $producto->nombre,
                        'variante' => $sublineaTexto,
                        'especificacion_id' => $sublineaId,
                        'ofertas_coincidentes' => $ofertasFiltradas->count(),
                    ]);
                    continue;
                }
                
                $precioOfertaEspecificacion = $mejorOfertaEspecificacion->precio_unidad;

                $precioMinimoHistoricoEspecificacion = HistoricoPrecioProducto::precioMinimoReferenciaUltimosMeses(
                    $producto->id,
                    (string) $sublineaId,
                    3,
                    $precioOfertaEspecificacion
                );
                $filasHistoricoSpec = $this->contarFilasHistorico($producto->id, (string) $sublineaId, $precioOfertaEspecificacion);

                if ($precioMinimoHistoricoEspecificacion === null || $precioMinimoHistoricoEspecificacion <= 0) {
                    $this->registrarMuestraDepuracion('spec_sin_historico_3m', [
                        'producto_id' => $producto->id,
                        'nombre' => $producto->nombre,
                        'variante' => $sublineaTexto,
                        'especificacion_id' => $sublineaId,
                        'filas_historico_3m' => $filasHistoricoSpec,
                        'precio_oferta' => round($precioOfertaEspecificacion, 3),
                    ]);
                    continue;
                }
                
                if ($precioOfertaEspecificacion > $precioMinimoHistoricoEspecificacion) {
                    $this->registrarMuestraDepuracion('spec_precio_superior_al_minimo', [
                        'producto_id' => $producto->id,
                        'nombre' => $producto->nombre,
                        'variante' => $sublineaTexto,
                        'especificacion_id' => $sublineaId,
                        'precio_minimo_historico' => round($precioMinimoHistoricoEspecificacion, 3),
                        'precio_oferta' => round($precioOfertaEspecificacion, 3),
                        'oferta_id' => $mejorOfertaEspecificacion->id,
                    ]);
                    continue;
                }

                $diferencia = (($precioMinimoHistoricoEspecificacion - $precioOfertaEspecificacion) / $precioMinimoHistoricoEspecificacion) * 100;

                if ($diferencia < HistoricoPrecioProducto::REBAJA_MINIMA_PCT_HOT) {
                    $this->registrarMuestraDepuracion('spec_rebaja_menor_umbral', [
                        'producto_id' => $producto->id,
                        'nombre' => $producto->nombre,
                        'variante' => $sublineaTexto,
                        'especificacion_id' => $sublineaId,
                        'precio_minimo_historico' => round($precioMinimoHistoricoEspecificacion, 3),
                        'precio_oferta' => round($precioOfertaEspecificacion, 3),
                        'rebaja_pct' => round($diferencia, 2),
                        'oferta_id' => $mejorOfertaEspecificacion->id,
                    ]);
                }

                $diasRachaPrecioBajoSpec = HistoricoPrecioProducto::diasConsecutivosPrecioActualAntesDeHoy(
                    $producto->id,
                    (string) $sublineaId,
                    $precioOfertaEspecificacion
                );
                $ofertaPasadaDeModaSpec = $diasRachaPrecioBajoSpec > HistoricoPrecioProducto::DIAS_RACHA_OFERTA_PASADA_HOT;
                
                // Actualizar campo rebajado en especificaciones_busqueda
                $umbralHot = HistoricoPrecioProducto::REBAJA_MINIMA_PCT_HOT;
                $rebajado = 0;
                if ($ofertaPasadaDeModaSpec) {
                    $rebajado = 0;
                } elseif ($diferencia >= $umbralHot) {
                    $rebajado = (int) round($diferencia);
                } elseif ($diferencia > 0 && $diferencia < $umbralHot) {
                    $rebajado = 0;
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
                
                if ($diferencia >= HistoricoPrecioProducto::REBAJA_MINIMA_PCT_HOT) {
                    if ($ofertaPasadaDeModaSpec) {
                        $this->registrarMuestraDepuracion('spec_oferta_pasada_racha', [
                            'producto_id' => $producto->id,
                            'nombre' => $producto->nombre,
                            'variante' => $sublineaTexto,
                            'especificacion_id' => $sublineaId,
                            'precio_minimo_historico' => round($precioMinimoHistoricoEspecificacion, 3),
                            'precio_oferta' => round($precioOfertaEspecificacion, 3),
                            'rebaja_pct' => round($diferencia, 2),
                            'dias_racha_precio_bajo' => $diasRachaPrecioBajoSpec,
                            'umbral_dias' => HistoricoPrecioProducto::DIAS_RACHA_OFERTA_PASADA_HOT,
                            'oferta_id' => $mejorOfertaEspecificacion->id,
                        ]);
                        continue;
                    }

                    $tienda = $mejorOfertaEspecificacion->tienda;
                    if (!$tienda) {
                        $this->registrarMuestraDepuracion('spec_sin_tienda', [
                            'producto_id' => $producto->id,
                            'nombre' => $producto->nombre,
                            'variante' => $sublineaTexto,
                            'oferta_id' => $mejorOfertaEspecificacion->id,
                            'rebaja_pct' => round($diferencia, 2),
                        ]);
                        continue;
                    }

                    $this->registrarMuestraDepuracion('spec_hot_ok', [
                        'producto_id' => $producto->id,
                        'nombre' => $producto->nombre . ' ' . $sublineaTexto,
                        'variante' => $sublineaTexto,
                        'especificacion_id' => $sublineaId,
                        'precio_minimo_historico' => round($precioMinimoHistoricoEspecificacion, 3),
                        'precio_oferta' => round($precioOfertaEspecificacion, 3),
                        'rebaja_pct' => round($diferencia, 2),
                        'oferta_id' => $mejorOfertaEspecificacion->id,
                    ], false);
                    
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
                        // Bajada de precio justo hoy (ayer no estaba a este precio), aunque hace semanas sí lo estuviera
                        'nuevo_hoy' => $diasRachaPrecioBajoSpec === 0,
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

                    $precioMinimoHistoricoEspecificacion = HistoricoPrecioProducto::precioMinimoReferenciaUltimosMeses(
                        $producto->id,
                        (string) $especificacionId,
                        3,
                        $precioOfertaEspecificacion
                    );

                    if ($precioMinimoHistoricoEspecificacion === null || $precioMinimoHistoricoEspecificacion <= 0) {
                        if (isset($especificacionesBusqueda[$sublineaTextoSlug]['rebajado']) &&
                            $especificacionesBusqueda[$sublineaTextoSlug]['rebajado'] != 0) {
                            $especificacionesBusqueda[$sublineaTextoSlug]['rebajado'] = 0;
                            $necesitaActualizarEspecificacionesBusqueda = true;
                        }
                        continue;
                    }
                    
                    if ($precioOfertaEspecificacion <= $precioMinimoHistoricoEspecificacion) {
                        $diferencia = (($precioMinimoHistoricoEspecificacion - $precioOfertaEspecificacion) / $precioMinimoHistoricoEspecificacion) * 100;
                        
                        if ($diferencia >= HistoricoPrecioProducto::REBAJA_MINIMA_PCT_HOT) {
                            $rebajado = (int) round($diferencia);
                        } else {
                            $rebajado = 0;
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