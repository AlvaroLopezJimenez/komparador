<?php

namespace App\Http\Controllers\Crons;

use App\Http\Controllers\Controller;
use App\Models\Aviso;
use App\Models\Categoria;
use App\Models\CorreoAvisoPrecio;
use App\Models\OfertaProducto;
use App\Models\Producto;
use App\Models\EjecucionGlobal;
use App\Services\SacarPrimeraOfertaDeUnProductoAplicadoDescuentosYChollos;

class AvisosCorreoPrecioCronController extends Controller
{
    public function __invoke(): int
    {
        $ejecucion = EjecucionGlobal::create([
            'inicio' => now(),
            'fin' => null,
            'nombre' => 'cron_avisos_generar_correo_precio',
            'total' => 0,
            'total_guardado' => 0,
            'total_errores' => 0,
            'log' => [
                'estado' => 'running',
                'paso_actual' => 'procesamiento',
                'pasos' => [
                    [
                        'momento' => now()->toDateTimeString(),
                        'paso' => 'inicio',
                        'detalle' => 'Ejecución iniciada'
                    ]
                ]
            ]
        ]);

        try {
            // Limpiar suscripciones no confirmadas caducadas (más de 1 hora).
            CorreoAvisoPrecio::query()
                ->where('confirmado', 'no')
                ->where('updated_at', '<=', now()->subHour())
                ->delete();

            $suscripciones = CorreoAvisoPrecio::query()
                ->with(['producto'])
                ->where('confirmado', 'si')
                ->where(function ($query) {
                    $query->whereNull('ultimo_envio_correo')
                        ->orWhere('ultimo_envio_correo', '<=', now()->subDays(7));
                })
                ->get();

            $avisosCreados = 0;

            foreach ($suscripciones as $suscripcion) {
                $precioMinimo = null;
                $tipoTexto = null;

                if ($this->esSuscripcionCategoria($suscripcion)) {
                    $meta = $this->metaSuscripcionCategoria($suscripcion);
                    $categoria = isset($meta['categoria_id']) ? Categoria::query()->find((int) $meta['categoria_id']) : null;
                    if (!$categoria) {
                        continue;
                    }

                    $productosCoincidentes = $this->buscarProductosCategoriaPorEspecificaciones($suscripcion, $categoria);
                    if ($productosCoincidentes === []) {
                        continue;
                    }

                    $seleccionCategoria = $this->normalizarSeleccion($suscripcion->especificaciones_internas_seleccionadas ?? []);
                    $productosConPrecioFiltrado = array_values(array_filter(array_map(function (Producto $producto) use ($seleccionCategoria) {
                        $precioFiltrado = $this->resolverPrecioProductoCategoriaSegunSeleccion($producto, $seleccionCategoria);
                        if ($precioFiltrado === null) {
                            return null;
                        }
                        $producto->precio_alerta = $precioFiltrado;
                        return $producto;
                    }, $productosCoincidentes)));
                    if ($productosConPrecioFiltrado === []) {
                        continue;
                    }

                    $precioMinimo = collect($productosConPrecioFiltrado)->min(fn (Producto $producto) => (float) ($producto->precio_alerta ?? $producto->precio ?? 0));
                    $tipoTexto = 'categoria';
                } elseif ($suscripcion->producto_id) {
                    $producto = $suscripcion->producto;
                    if (!$producto) {
                        continue;
                    }

                    $ofertas = OfertaProducto::query()
                        ->where('producto_id', $producto->id)
                        ->where('mostrar', 'si')
                        ->whereNotNull('precio_unidad')
                        ->get();

                    $ofertasFiltradas = $this->filtrarOfertasPorEspecificaciones(
                        $ofertas->all(),
                        is_array($suscripcion->especificaciones_internas_seleccionadas)
                            ? $suscripcion->especificaciones_internas_seleccionadas
                            : []
                    );

                    if (empty($ofertasFiltradas)) {
                        continue;
                    }

                    $precioMinimo = collect($ofertasFiltradas)
                        ->map(fn (OfertaProducto $oferta) => (float) $oferta->precio_unidad)
                        ->min();
                    $tipoTexto = 'producto';
                } else {
                    continue;
                }

                if ($precioMinimo === null || $precioMinimo > (float) $suscripcion->precio_limite) {
                    continue;
                }

                $existeAviso = Aviso::query()
                    ->where('avisoable_type', CorreoAvisoPrecio::class)
                    ->where('avisoable_id', $suscripcion->id)
                    ->where('oculto', false)
                    ->exists();

                if ($existeAviso) {
                    continue;
                }

                $texto = 'Aviso de correo pendiente (' . $tipoTexto . ') - '
                    . $suscripcion->correo
                    . ' - limite: '
                    . number_format((float) $suscripcion->precio_limite, 2, '.', '')
                    . ' - precio actual: '
                    . number_format((float) $precioMinimo, 2, '.', '');

                Aviso::create([
                    'texto_aviso' => $texto,
                    'fecha_aviso' => now(),
                    'user_id' => 1,
                    'avisoable_type' => CorreoAvisoPrecio::class,
                    'avisoable_id' => $suscripcion->id,
                    'oculto' => false,
                ]);

                $avisosCreados++;
            }

            $ejecucion->update([
                'fin' => now(),
                'total' => $suscripciones->count(),
                'total_guardado' => $avisosCreados,
                'log' => [
                    'estado' => 'ok',
                    'paso_actual' => 'finalizado',
                    'resumen' => $suscripciones->count() . ' suscripciones procesadas, ' . $avisosCreados . ' avisos generados.'
                ]
            ]);

            return 0;
        } catch (\Throwable $e) {
            $ejecucion->update([
                'fin' => now(),
                'total_errores' => 1,
                'log' => [
                    'estado' => 'error',
                    'paso_actual' => 'error',
                    'error' => $e->getMessage()
                ]
            ]);
            throw $e;
        }
    }

    /**
     * @param  OfertaProducto[]  $ofertas
     * @param  array<string, array<int, string|int>>  $seleccionadas
     * @return OfertaProducto[]
     */
    private function filtrarOfertasPorEspecificaciones(array $ofertas, array $seleccionadas): array
    {
        $seleccion = collect($seleccionadas)
            ->filter(function ($ids, $lineaId) {
                if (!is_array($ids) || $lineaId === 'precio_min' || $lineaId === 'precio_max' || str_starts_with((string) $lineaId, '_')) {
                    return false;
                }

                return count($ids) > 0;
            })
            ->map(function ($ids) {
                return array_values(array_map('strval', $ids));
            })
            ->toArray();

        if (empty($seleccion)) {
            return $ofertas;
        }

        return array_values(array_filter($ofertas, function (OfertaProducto $oferta) use ($seleccion) {
            $especificacionesOferta = is_array($oferta->especificaciones_internas)
                ? $oferta->especificaciones_internas
                : [];

            foreach ($seleccion as $lineaId => $sublineasSeleccionadas) {
                $sublineasOfertaRaw = $especificacionesOferta[$lineaId] ?? [];
                if (!is_array($sublineasOfertaRaw)) {
                    return false;
                }

                $sublineasOferta = array_map('strval', $sublineasOfertaRaw);
                if (count(array_intersect($sublineasSeleccionadas, $sublineasOferta)) === 0) {
                    return false;
                }
            }

            return true;
        }));
    }

    /**
     * @return Producto[]
     */
    private function buscarProductosCategoriaPorEspecificaciones(CorreoAvisoPrecio $suscripcion, Categoria $categoria): array
    {
        $categoriaIds = Categoria::idsSelfAndDescendants((int) $categoria->id);
        $seleccion = $this->normalizarSeleccion($suscripcion->especificaciones_internas_seleccionadas ?? []);

        $productos = Producto::query()
            ->whereIn('categoria_id', $categoriaIds)
            ->where('mostrar', 'si')
            ->where('precio', '>', 0)
            ->get(['id', 'categoria_especificaciones_internas_elegidas', 'precio']);

        if ($seleccion === []) {
            return $productos->all();
        }

        return $productos->filter(function (Producto $producto) use ($seleccion) {
            $esp = $producto->categoria_especificaciones_internas_elegidas;
            if (!is_array($esp)) {
                return false;
            }

            foreach ($seleccion as $lineaId => $sublineasIds) {
                $productoLinea = $esp[$lineaId] ?? null;
                if ($productoLinea === null) {
                    return false;
                }
                $productoSublineas = is_array($productoLinea) ? $productoLinea : [$productoLinea];
                $coincide = false;
                foreach ($productoSublineas as $item) {
                    $itemId = (is_array($item) && isset($item['id'])) ? strval($item['id']) : strval($item);
                    if (in_array($itemId, $sublineasIds, true)) {
                        if (is_array($item) && isset($item['c'])) {
                            if (($item['c'] ?? 0) > 0) {
                                $coincide = true;
                                break;
                            }
                        } else {
                            $coincide = true;
                            break;
                        }
                    }
                }
                if (!$coincide) {
                    return false;
                }
            }

            return true;
        })->values()->all();
    }

    /**
     * @param  array<string, mixed>  $seleccionadas
     * @return array<string, array<int, string>>
     */
    private function normalizarSeleccion(array $seleccionadas): array
    {
        return collect($seleccionadas)
            ->filter(function ($ids, $lineaId) {
                if (!is_array($ids) || $lineaId === 'precio_min' || $lineaId === 'precio_max' || str_starts_with((string) $lineaId, '_')) {
                    return false;
                }

                return count($ids) > 0;
            })
            ->map(function ($ids) {
                return array_values(array_map('strval', $ids));
            })
            ->toArray();
    }

    /**
     * @param array<string, array<int, string>> $seleccionNormalizada
     */
    private function resolverPrecioProductoCategoriaSegunSeleccion(Producto $producto, array $seleccionNormalizada): ?float
    {
        if ($seleccionNormalizada === []) {
            $precioBase = (float) ($producto->precio ?? 0);
            return $precioBase > 0 ? $precioBase : null;
        }

        $espProducto = is_array($producto->categoria_especificaciones_internas_elegidas)
            ? $producto->categoria_especificaciones_internas_elegidas
            : [];
        if ($espProducto === []) {
            return null;
        }

        $filtrosConMostrar = [];
        foreach ($seleccionNormalizada as $lineaId => $sublineasIds) {
            $productoLinea = $espProducto[$lineaId] ?? null;
            if ($productoLinea === null) {
                return null;
            }

            $productoSublineas = is_array($productoLinea) ? $productoLinea : [$productoLinea];
            $coincideLinea = false;
            $requiereOfertaEnLinea = false;
            foreach ($productoSublineas as $item) {
                $itemId = (is_array($item) && isset($item['id'])) ? strval($item['id']) : strval($item);
                if (!in_array($itemId, $sublineasIds, true)) {
                    continue;
                }

                if (is_array($item) && isset($item['c']) && ($item['c'] ?? 0) <= 0) {
                    continue;
                }

                $coincideLinea = true;
                if (is_array($item)) {
                    $mostrar = (isset($item['m']) && ($item['m'] === 1 || $item['m'] === true))
                        || (isset($item['mostrar']) && $item['mostrar'] === true);
                    if ($mostrar) {
                        $requiereOfertaEnLinea = true;
                    }
                }
                break;
            }

            if (!$coincideLinea) {
                return null;
            }

            if ($requiereOfertaEnLinea) {
                $filtrosConMostrar[$lineaId] = $sublineasIds;
            }
        }

        if ($filtrosConMostrar === []) {
            $precioBase = (float) ($producto->precio ?? 0);
            return $precioBase > 0 ? $precioBase : null;
        }

        $servicioOfertas = app(SacarPrimeraOfertaDeUnProductoAplicadoDescuentosYChollos::class);
        $ofertas = $servicioOfertas->obtenerTodas($producto);
        if ($ofertas->isEmpty()) {
            return null;
        }

        $ofertasFiltradas = $ofertas->filter(function ($oferta) use ($filtrosConMostrar) {
            $espOferta = is_array($oferta->especificaciones_internas) ? $oferta->especificaciones_internas : [];
            if ($espOferta === []) {
                return false;
            }

            foreach ($filtrosConMostrar as $lineaId => $sublineasIds) {
                $ofertaLinea = $espOferta[$lineaId] ?? null;
                if ($ofertaLinea === null) {
                    return false;
                }
                $ofertaSublineas = is_array($ofertaLinea) ? $ofertaLinea : [$ofertaLinea];

                $coincide = false;
                foreach ($ofertaSublineas as $item) {
                    $itemId = (is_array($item) && isset($item['id'])) ? strval($item['id']) : strval($item);
                    if (in_array($itemId, $sublineasIds, true)) {
                        $coincide = true;
                        break;
                    }
                }
                if (!$coincide) {
                    return false;
                }
            }
            return true;
        })->values();

        if ($ofertasFiltradas->isEmpty()) {
            return null;
        }

        $precioMin = (float) $ofertasFiltradas->min(fn ($oferta) => (float) ($oferta->precio_unidad ?? 0));
        return $precioMin > 0 ? $precioMin : null;
    }

    /**
     * @return array{categoria_id?: int}
     */
    private function metaSuscripcionCategoria(CorreoAvisoPrecio $suscripcion): array
    {
        $raw = is_array($suscripcion->especificaciones_internas_seleccionadas)
            ? $suscripcion->especificaciones_internas_seleccionadas
            : [];
        if (($raw['_alerta_tipo'] ?? null) !== 'categoria') {
            return [];
        }
        if (!isset($raw['_categoria_id']) || !is_numeric($raw['_categoria_id'])) {
            return [];
        }
        return ['categoria_id' => (int) $raw['_categoria_id']];
    }

    private function esSuscripcionCategoria(CorreoAvisoPrecio $suscripcion): bool
    {
        return $this->metaSuscripcionCategoria($suscripcion) !== [];
    }
}
