<?php

namespace App\Services;

use App\Http\Controllers\DescuentosController;
use App\Http\Controllers\Scraping\ScrapingController;
use App\Models\CorreoAvisoPrecio;
use App\Models\HistoricoPrecioProducto;
use App\Models\OfertaProducto;
use App\Models\Producto;
use App\Services\CsvAwinOfertaService;
use App\Services\TiendaScrapingConfigResolver;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Lógica compartida de actualización de precios por scraping (cron web y programa externo).
 */
class Scraping
{
    public const API_NAVEGADOR_LOCAL = 'navegadorLocal';

    /** Nombre de ejecución del programa externo (independiente del cron web). */
    public const NOMBRE_EJECUCION_PROGRAMA_EXTERNO = 'ejecuciones_scrapear_ofertas_programa_externo';

    private const UMBRAL_CAMBIO_ANOMALO = 0.40;

    /** Precio centinela de oferta sin stock / no disponible. */
    private const PRECIO_CENTINELA_SIN_STOCK = 9999.0;

    /** Bajada mínima respecto al precio más bajo del último año (p. ej. 0,10 = al menos un 10 % por debajo). */
    private const UMBRAL_BAJADA_BAJO_PRECIO_MINIMO_HISTORICO = 0.10;

    public const TEXTO_AVISO_BAJADA_10_PCT_MINIMO = 'Bajada +10% de su precio mínimo';

    /**
     * Procesa una oferta: obtiene precio (vía tienda o HTML inyectado), valida, actualiza BD.
     *
     * @return array<string, mixed>
     */
    public function procesarOferta(OfertaProducto $oferta): array
    {
        $oferta->loadMissing(['tienda', 'producto']);

        $mostrarAntes = $oferta->mostrar;

        $serviceTiempos = new TiemposActualizacionOfertasDinamicos();
        $serviceTiempos->calcularFrecuencia($oferta->id);

        $producto = Producto::find($oferta->producto_id);
        $servicioOfertas = new SacarPrimeraOfertaDeUnProductoAplicadoDescuentosYChollos();
        $ofertaMasBarataAntes = $producto ? $servicioOfertas->obtener($producto) : null;
        $ofertasAntes = $ofertaMasBarataAntes ? collect([$ofertaMasBarataAntes]) : collect();

        $scrapingController = new ScrapingController();
        $request = new Request();
        $request->merge([
            'url'      => $oferta->url,
            'tienda'   => $oferta->tienda->nombre,
            'variante' => $oferta->variante ?? null,
        ]);

        $response = $scrapingController->obtenerPrecio($request, $oferta);
        $responseData = $response->getData(true);

        $precioAnterior = $oferta->precio_total;

        if (!$responseData['success']) {
            $oferta->refresh();
            if ($this->ofertaFueOcultadaTrasScraping($oferta, $mostrarAntes)) {
                return $this->resultadoOfertaOculta(
                    $oferta,
                    $precioAnterior,
                    $responseData['error'] ?? 'Oferta ocultada tras scraping'
                );
            }

            return $this->resultadoBase($oferta, $precioAnterior, null, false, 'Error en el scraping: ' . ($responseData['error'] ?? 'Error desconocido'));
        }

        if (!isset($responseData['precio']) || !is_numeric($responseData['precio'])) {
            return $this->resultadoBase($oferta, $precioAnterior, null, false, 'Respuesta inválida del scraping: ' . json_encode($responseData));
        }

        $precioNuevo = round((float) str_replace(',', '.', $responseData['precio']), 2);

        $calcularPrecioUnidad = new CalcularPrecioUnidad();
        $precioUnidadNuevo = $calcularPrecioUnidad->calcular(
            $producto->unidadDeMedida ?? 'unidad',
            $precioNuevo,
            $oferta->unidades
        );
        if ($precioUnidadNuevo === null) {
            $precioUnidadNuevo = round($precioNuevo / $oferta->unidades, 2);
        }

        $anomalo = $this->detectarYRegistrarCambioAnomalo($oferta, $precioAnterior, $precioNuevo);
        if ($anomalo !== null) {
            return $anomalo;
        }

        $precioUnidadParaComprobacionHistorico = $precioUnidadNuevo;
        if ($producto) {
            $ofertaParaComprobacionHistorico = clone $oferta;
            $ofertaParaComprobacionHistorico->precio_total = $precioNuevo;
            $ofertaParaComprobacionHistorico->precio_unidad = $precioUnidadNuevo;

            $ofertaParaComprobacionHistorico = $servicioOfertas->aplicarDescuentosEnvioYRecalcularPrecioUnidadAOferta(
                $ofertaParaComprobacionHistorico,
                $producto
            );

            $precioUnidadParaComprobacionHistorico = $ofertaParaComprobacionHistorico->precio_unidad ?? $precioUnidadNuevo;
        }

        $this->detectarYRegistrarBajadaCercaPrecioMinimoHistorico($oferta, (float) $precioUnidadParaComprobacionHistorico);

        $oferta->update([
            'precio_total'  => $precioNuevo,
            'precio_unidad' => $precioUnidadNuevo,
        ]);
        $oferta->touch();

        $serviceTiempos->registrarActualizacion($oferta->id, $precioNuevo, 'automatico');

        $ofertaMasBarataDespues = $producto ? $servicioOfertas->obtener($producto) : null;
        $ofertasDespues = $ofertaMasBarataDespues ? collect([$ofertaMasBarataDespues]) : collect();

        $cambiosDetectados = $this->aplicarCambiosProductoSiCorresponde(
            $oferta,
            $ofertasAntes,
            $ofertasDespues
        );

        return [
            'oferta_id'                => $oferta->id,
            'tienda_nombre'            => $oferta->tienda->nombre,
            'url'                      => $oferta->url,
            'variante'                 => $oferta->variante,
            'precio_anterior'          => $precioAnterior,
            'precio_nuevo'             => $precioNuevo,
            'success'                  => true,
            'precio_guardado'          => true,
            'error'                    => null,
            'cambios_detectados'       => $cambiosDetectados,
            'url_notificacion_llamada' => false,
            'ofertas_antes'            => $cambiosDetectados ? $ofertasAntes->toArray() : null,
            'ofertas_despues'          => $cambiosDetectados ? $ofertasDespues->toArray() : null,
        ];
    }

    /**
     * Segunda pasada silenciosa: tienda con API CSV-Awin pero categoría con otra API.
     * Tras un scraping normal exitoso, sustituye el precio por el de csv_ofertas si difiere.
     */
    public function aplicarPrecioCsvPostScrapingSiCorresponde(OfertaProducto $oferta): bool
    {
        $oferta->loadMissing(['tienda', 'producto']);
        $tienda = $oferta->tienda;
        if ($tienda === null || $tienda->api !== TiendaScrapingConfigResolver::API_CSV_AWIN) {
            return false;
        }

        $resolver = app(TiendaScrapingConfigResolver::class);
        $apiEfectiva = $resolver->resolverApi($tienda, $oferta->producto->categoria_id ?? null);
        if ($apiEfectiva === TiendaScrapingConfigResolver::API_CSV_AWIN) {
            return false;
        }

        return app(CsvAwinOfertaService::class)->actualizarPrecioOfertaDesdeCsvSilencioso($oferta);
    }

    /**
     * Aplica descuentos a una oferta cruda de BD (precio_unidad sin descuentos).
     * No usar con ofertas ya pasadas por SacarPrimeraOferta...->obtener().
     */
    public function calcularPrecioRealPorUnidad($oferta): ?float
    {
        $descuentosController = new DescuentosController();
        $ofertaConDescuento = $descuentosController->aplicarDescuento($oferta);

        return $ofertaConDescuento->precio_unidad;
    }

    public function obtenerOfertasElegibles(int $limit = 50): Collection
    {
        return $this->queryOfertasElegiblesBase($limit)
            ->whereHas('tienda', function ($query) {
                $query->where('scrapear', 'si')
                    ->where(function ($q) {
                        $q->whereNull('api')
                            ->orWhere('api', '!=', self::API_NAVEGADOR_LOCAL);
                    });
            })
            ->tap(fn (Builder $query) => $this->aplicarFiltroApiEfectivaCsvAwin($query, false))
            ->get();
    }

    /**
     * Ofertas elegibles cuya API efectiva es CSV-Awin (no cuentan para el límite del cron principal).
     */
    public function obtenerOfertasElegiblesCsvAwin(): Collection
    {
        return $this->queryOfertasElegiblesBase(null)
            ->whereHas('tienda', function ($query) {
                $query->where('scrapear', 'si');
            })
            ->tap(fn (Builder $query) => $this->aplicarFiltroApiEfectivaCsvAwin($query, true))
            ->get();
    }

    public function obtenerOfertasElegiblesNavegadorLocal(int $limit = 50): Collection
    {
        return $this->queryOfertasElegiblesBase($limit)
            ->whereHas('tienda', function ($query) {
                $query->where('scrapear', 'si')
                    ->where('api', self::API_NAVEGADOR_LOCAL);
            })
            ->get();
    }

    protected function queryOfertasElegiblesBase(?int $limit = null): Builder
    {
        $query = OfertaProducto::with(['producto', 'tienda'])
            ->where('mostrar', 'si')
            ->where('como_scrapear', 'automatico')
            ->whereNull('chollo_id')
            ->whereRaw('TIMESTAMPDIFF(MINUTE, updated_at, NOW()) >= frecuencia_actualizar_precio_minutos')
            ->whereRaw(TiendaScrapingConfigResolver::sqlScrapearEfectivo() . " = 'si'")
            ->orderByRaw('TIMESTAMPDIFF(MINUTE, updated_at, NOW()) DESC');

        if ($limit !== null) {
            $query->limit($limit);
        }

        return $query;
    }

    /**
     * Filtra por API efectiva (categoría si está configurada, si no la de tienda).
     *
     * @param  bool  $soloCsvAwin  true = solo CSV-Awin; false = excluir CSV-Awin
     */
    private function aplicarFiltroApiEfectivaCsvAwin(Builder $query, bool $soloCsvAwin): void
    {
        $csvApi = TiendaScrapingConfigResolver::API_CSV_AWIN;
        $subquery = "(
            SELECT COALESCE(NULLIF(tca.api, ''), t.api)
            FROM productos p
            INNER JOIN tiendas t ON t.id = ofertas_producto.tienda_id
            LEFT JOIN tienda_categoria_api tca
                ON tca.tienda_id = ofertas_producto.tienda_id
                AND tca.categoria_id = p.categoria_id
            WHERE p.id = ofertas_producto.producto_id
            LIMIT 1
        )";

        if ($soloCsvAwin) {
            $query->whereRaw("{$subquery} = ?", [$csvApi]);
        } else {
            $query->whereRaw("COALESCE({$subquery}, '') != ?", [$csvApi]);
        }
    }

    /**
     * @param  array<string, mixed>  $log
     * @param  array<string, int>  $estadisticas
     */
    public function registrarEjecucionProgramaExterno(
        array $log,
        array $estadisticas,
        \Carbon\Carbon $inicio,
        string $estado = 'completada',
        ?string $errorMensaje = null
    ): int {
        $totalOfertas = (int) ($estadisticas['total_ofertas'] ?? 0);
        $actualizadas = (int) ($estadisticas['actualizadas'] ?? 0);
        $errores = (int) ($estadisticas['errores'] ?? 0);
        $procesadas = (int) ($estadisticas['procesadas'] ?? $totalOfertas);

        $payloadLog = array_merge($log, [
            'origen'           => 'programa_externo_navegador',
            'estado'           => $estado,
            'estadisticas'     => $estadisticas,
            'programa_externo' => [
                'tipo'          => 'navegador_local',
                'error_mensaje' => $errorMensaje,
            ],
            'total_ofertas' => $totalOfertas,
            'actualizadas'  => $actualizadas,
            'errores'       => $errores,
            'procesadas'    => $procesadas,
        ]);

        $ejecucion = \App\Models\EjecucionGlobal::create([
            'inicio'         => $inicio,
            'fin'            => now(),
            'nombre'         => self::NOMBRE_EJECUCION_PROGRAMA_EXTERNO,
            'total'          => $procesadas,
            'total_guardado' => $actualizadas,
            'total_errores'  => $errores,
            'log'            => $payloadLog,
        ]);

        return $ejecucion->id;
    }

    /**
     * @return array<string, mixed>|null Si hay anomalía, devuelve el resultado y no se actualiza precio.
     */
    private function detectarYRegistrarCambioAnomalo(
        OfertaProducto $oferta,
        $precioAnterior,
        float $precioNuevo
    ): ?array {
        if (!is_numeric($precioAnterior)) {
            return null;
        }

        $precioAnterior = (float) $precioAnterior;

        if ($this->esPrecioCentinelaSinStock($precioAnterior) || $this->esPrecioCentinelaSinStock($precioNuevo)) {
            return null;
        }

        if (!$this->esPrecioCeroExacto($precioAnterior) && $precioAnterior > 0) {
            $bajadaRelativa = ($precioAnterior - $precioNuevo) / $precioAnterior;
            if ($bajadaRelativa >= self::UMBRAL_CAMBIO_ANOMALO) {
                $this->insertarAvisoAnomalo($oferta, $precioAnterior, $precioNuevo, $bajadaRelativa, true);
                $oferta->touch();

                return $this->resultadoAnomalo($oferta, $precioAnterior, $precioNuevo);
            }
        }

        if (!$this->esPrecioCeroExacto($precioAnterior)
            && !$this->esPrecioCeroExacto($precioNuevo)
            && $precioAnterior > 0) {
            $subidaRelativa = ($precioNuevo - $precioAnterior) / $precioAnterior;
            if ($subidaRelativa >= self::UMBRAL_CAMBIO_ANOMALO) {
                $this->insertarAvisoAnomalo($oferta, $precioAnterior, $precioNuevo, $subidaRelativa, false);
                $oferta->touch();

                return $this->resultadoAnomalo($oferta, $precioAnterior, $precioNuevo);
            }
        }

        return null;
    }

    /**
     * Cero estricto (0,000), no valores pequeños como 0,002.
     */
    private function esPrecioCeroExacto($precio): bool
    {
        return is_numeric($precio) && round((float) $precio, 3) === 0.0;
    }

    private function esPrecioCentinelaSinStock($precio): bool
    {
        return is_numeric($precio) && round((float) $precio, 2) === self::PRECIO_CENTINELA_SIN_STOCK;
    }

    /**
     * Aviso si el precio por unidad nuevo está al menos un 10 % por debajo del mínimo general del producto.
     */
    private function detectarYRegistrarBajadaCercaPrecioMinimoHistorico(
        OfertaProducto $oferta,
        float $precioUnidadNuevo
    ): void {
        if ($precioUnidadNuevo <= 0) {
            return;
        }

        $desde = Carbon::today()->subYear()->toDateString();
        $precioMinimo = HistoricoPrecioProducto::where('producto_id', $oferta->producto_id)
            ->whereNull('especificacion_interna_id')
            ->where('fecha', '>=', $desde)
            ->where('precio_minimo', '>', 0)
            ->min('precio_minimo');

        if ($precioMinimo === null || (float) $precioMinimo <= 0) {
            return;
        }

        $precioMinimo = (float) $precioMinimo;
        $umbral = $precioMinimo * (1 - self::UMBRAL_BAJADA_BAJO_PRECIO_MINIMO_HISTORICO);

        if ($precioUnidadNuevo > $umbral) {
            return;
        }

        $porcentajeBajada = (($precioMinimo - $precioUnidadNuevo) / $precioMinimo) * 100;
        $textoAviso = sprintf(
            '%s. Precio mínimo historico: %.2f€, %% de bajada: %s%%',
            self::TEXTO_AVISO_BAJADA_10_PCT_MINIMO,
            $precioMinimo,
            number_format($porcentajeBajada, 1, ',', '.')
        );

        DB::table('avisos')->insert([
            'texto_aviso'    => $textoAviso,
            'fecha_aviso'    => now(),
            'user_id'        => 1,
            'avisoable_type' => OfertaProducto::class,
            'avisoable_id'   => $oferta->id,
            'oculto'         => 0,
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);
    }

    private function insertarAvisoAnomalo(
        OfertaProducto $oferta,
        float $precioAnterior,
        float $precioNuevo,
        float $relativa,
        bool $esBajada
    ): void {
        $producto = Producto::find($oferta->producto_id);
        $nombreProducto = $producto ? $producto->nombre : ('Producto ID ' . $oferta->producto_id);
        $porcentajeStr = number_format($relativa * 100, 0);

        if ($esBajada) {
            $absoluta = max(0, $precioAnterior - $precioNuevo);
            $textoAviso = sprintf(
                "Bajada anómala (>40%%) en '%s': de %.2f€ a %.2f€ (−%.2f€, −%s%%). Tienda: %s | Variante: %s | URL: %s",
                $nombreProducto,
                $precioAnterior,
                $precioNuevo,
                $absoluta,
                $porcentajeStr,
                $oferta->tienda->nombre,
                $oferta->variante ?? '—',
                $oferta->url
            );
        } else {
            $absoluta = max(0, $precioNuevo - $precioAnterior);
            $textoAviso = sprintf(
                "Subida anómala (>40%%) en '%s': de %.2f€ a %.2f€ (+%.2f€, +%s%%). Tienda: %s | Variante: %s | URL: %s",
                $nombreProducto,
                $precioAnterior,
                $precioNuevo,
                $absoluta,
                $porcentajeStr,
                $oferta->tienda->nombre,
                $oferta->variante ?? '—',
                $oferta->url
            );
        }

        $this->actualizarTextoAvisoExistenteOInsertar(
            OfertaProducto::class,
            $oferta->id,
            $textoAviso,
            function ($query) {
                $query->where(function ($q) {
                    $q->where('texto_aviso', 'like', 'Bajada anómala (>40%')
                        ->orWhere('texto_aviso', 'like', 'Subida anómala (>40%');
                });
            }
        );
    }

    /**
     * Si ya existe un aviso del mismo tipo en la entidad, actualiza solo el texto.
     *
     * @param  callable(Builder): void|null  $filtroExistente
     */
    private function actualizarTextoAvisoExistenteOInsertar(
        string $avisoableType,
        int $avisoableId,
        string $textoAviso,
        ?callable $filtroExistente = null
    ): void {
        $query = DB::table('avisos')
            ->where('avisoable_type', $avisoableType)
            ->where('avisoable_id', $avisoableId);

        if ($filtroExistente !== null) {
            $filtroExistente($query);
        }

        $existente = $query->orderByDesc('created_at')->first();

        if ($existente) {
            DB::table('avisos')
                ->where('id', $existente->id)
                ->update([
                    'texto_aviso' => $textoAviso,
                    'updated_at'  => now(),
                ]);

            return;
        }

        DB::table('avisos')->insert([
            'texto_aviso'    => $textoAviso,
            'fecha_aviso'    => now(),
            'user_id'        => 1,
            'avisoable_type' => $avisoableType,
            'avisoable_id'   => $avisoableId,
            'oculto'         => 0,
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);
    }

    private function aplicarCambiosProductoSiCorresponde(
        OfertaProducto $oferta,
        Collection $ofertasAntes,
        Collection $ofertasDespues
    ): bool {
        $ofertaAntes = $ofertasAntes->first();
        $ofertaDespues = $ofertasDespues->first();

        // Las ofertas vienen de SacarPrimeraOferta...->obtener(): ya traen descuentos,
        // envío y precio_unidad recalculado. NO volver a pasar por DescuentosController
        // (calcularPrecioRealPorUnidad), porque reaplicaría 2x1/cupón y corrompería el precio.
        $precioRealAntes = $ofertaAntes && $ofertaAntes->precio_unidad !== null
            ? (float) $ofertaAntes->precio_unidad
            : null;
        $precioRealDespues = $ofertaDespues && $ofertaDespues->precio_unidad !== null
            ? (float) $ofertaDespues->precio_unidad
            : null;

        $precioRealAntesNormalizado = $precioRealAntes !== null ? round($precioRealAntes, 3) : null;
        $precioRealDespuesNormalizado = $precioRealDespues !== null ? round($precioRealDespues, 3) : null;

        if (($ofertaAntes ? $ofertaAntes->id : null) === ($ofertaDespues ? $ofertaDespues->id : null)
            && $precioRealAntesNormalizado === $precioRealDespuesNormalizado) {
            return false;
        }

        $precioMasBajo = $precioRealDespues;
        $producto = Producto::find($oferta->producto_id);
        if (!$producto) {
            return true;
        }

        $precioAntiguoProducto = Producto::where('id', $oferta->producto_id)->value('precio');
        $producto->update(['precio' => $precioMasBajo]);

        // Sincronizar la tabla producto_oferta_mas_barata_por_producto sin borrar la fila
        if ($ofertaDespues) {
            $ofertaOriginal = OfertaProducto::find($ofertaDespues->id);
            if ($ofertaOriginal) {
                \App\Models\ProductoOfertaMasBarataPorProducto::updateOrCreate(
                    ['producto_id' => $producto->id],
                    [
                        'oferta_id' => $ofertaOriginal->id,
                        'tienda_id' => $ofertaOriginal->tienda_id,
                        'precio_total' => $ofertaDespues->precio_total,
                        'precio_unidad' => $ofertaDespues->precio_unidad,
                        'unidades' => $ofertaOriginal->unidades,
                        'url' => $ofertaOriginal->url,
                    ]
                );
            }
        } else {
            $registroMasBarato = \App\Models\ProductoOfertaMasBarataPorProducto::where('producto_id', $producto->id)->first();
            if ($registroMasBarato) {
                $registroMasBarato->update([
                    'precio_total' => 0.00,
                    'precio_unidad' => 0.0000,
                ]);
            }
        }

        $textoAviso = 'Precio actualizado producto ' . $producto->nombre . ' precio antiguo: ' . $precioAntiguoProducto . ', precio Nuevo: ' . $precioMasBajo;

        $alertasPendientes = CorreoAvisoPrecio::where('producto_id', $producto->id)
            ->where('precio_limite', '>=', $precioMasBajo)
            ->count();

        if ($alertasPendientes > 0) {
            $textoAviso .= ' | Alertas pendientes: ' . $alertasPendientes . ' correos';
        }

        $this->actualizarTextoAvisoExistenteOInsertar(
            Producto::class,
            $producto->id,
            $textoAviso,
            function ($query) {
                $query->where('texto_aviso', 'like', '%Precio actualizado producto%');
            }
        );

        return true;
    }

    /**
     * Oferta ocultada (mostrar=no) por sin stock, 404, CSV, segunda mano, etc.
     *
     * @return array<string, mixed>
     */
    private function resultadoOfertaOculta(OfertaProducto $oferta, $precioAnterior, ?string $motivo): array
    {
        return array_merge(
            $this->resultadoBase($oferta, $precioAnterior, null, true, null),
            [
                'oferta_oculta'     => true,
                'motivo_ocultacion' => $motivo,
            ]
        );
    }

    private function ofertaFueOcultadaTrasScraping(OfertaProducto $oferta, ?string $mostrarAntes): bool
    {
        return $mostrarAntes === 'si' && $oferta->mostrar === 'no';
    }

    /**
     * @return array<string, mixed>
     */
    private function resultadoBase(
        OfertaProducto $oferta,
        $precioAnterior,
        ?float $precioNuevo,
        bool $success,
        ?string $error
    ): array {
        return [
            'oferta_id'                => $oferta->id,
            'tienda_nombre'            => $oferta->tienda->nombre,
            'url'                      => $oferta->url,
            'variante'                 => $oferta->variante,
            'precio_anterior'          => $precioAnterior,
            'precio_nuevo'             => $precioNuevo,
            'success'                  => $success,
            'error'                    => $error,
            'cambios_detectados'       => false,
            'url_notificacion_llamada' => false,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function resultadoAnomalo(OfertaProducto $oferta, $precioAnterior, float $precioNuevo): array
    {
        return [
            'oferta_id'                => $oferta->id,
            'tienda_nombre'            => $oferta->tienda->nombre,
            'url'                      => $oferta->url,
            'variante'                 => $oferta->variante,
            'precio_anterior'          => $precioAnterior,
            'precio_nuevo'             => $precioNuevo,
            'success'                  => true,
            'error'                    => null,
            'cambios_detectados'       => false,
            'url_notificacion_llamada' => false,
        ];
    }
}
