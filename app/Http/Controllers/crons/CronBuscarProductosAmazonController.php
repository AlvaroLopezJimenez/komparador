<?php

declare(strict_types=1);

namespace App\Http\Controllers\Crons;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Scraping\PeticionApiHTMLController;
use App\Models\EjecucionGlobal;
use App\Models\Neo;
use App\Models\OfertaProducto;
use App\Models\Producto;
use App\Models\UrlDescartada;
use App\Services\ConsultarNeoCifrado;
use App\Services\LimpiarUrlDeTiendas;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class CronBuscarProductosAmazonController extends Controller
{
    public const NOMBRE_EJECUCION_GLOBAL = 'cron_buscar_amazon_productos';

    private const ESPERA_ENTRE_PRODUCTOS_SEGUNDOS = 2;

    /** Pausa entre búsqueda Amazon y AliExpress del mismo producto. */
    private const ESPERA_ENTRE_TIENDAS_SEGUNDOS = 2;
    private const RETENCION_EJECUCIONES_DIAS = 30;
    /** Productos elegibles por llamada al cron (≈4 min; evita timeout ~300s del hosting). */
    private const LIMITE_PRODUCTOS_POR_EJECUCION = 12;

    private static ?int $ejecucionEnCursoId = null;

    /** @var array<string, int>|null */
    private static ?array $contadoresEnCurso = null;

    /** @var array<int, array<string, mixed>>|null */
    private static ?array $resultadosEnCurso = null;

    private static bool $ejecucionFinalizada = false;

    public function __construct(
        private readonly PeticionApiHTMLController $peticionApiHtml,
        private readonly LimpiarUrlDeTiendas $limpiarUrlDeTiendas,
        private readonly ConsultarNeoCifrado $consultarNeoCifrado,
    ) {}

    /**
     * Vista de búsqueda de productos en Amazon (Creators API SearchItems).
     */
    public function buscarAmazon()
    {
        return view('admin.productos.buscar-amazon');
    }

    /**
     * API: buscar productos en Amazon por texto, filtrar por palabras coincidentes en el título y devolver items para la vista.
     */
    public function buscarAmazonApi(Request $request): JsonResponse
    {
        $request->validate([
            'q'                     => 'required|string|min:2|max:500',
            'palabras_coincidentes' => 'nullable|string|max:500',
            'aliexpress'            => 'nullable|boolean',
        ]);

        if ($request->boolean('aliexpress')) {
            $resultado = $this->peticionApiHtml->buscarProductosAliexpress(
                (string) $request->input('q'),
                1,
                50
            );

            if (!($resultado['success'] ?? false)) {
                return response()->json([
                    'success'   => false,
                    'proveedor' => 'aliexpress',
                    'error'     => $resultado['error'] ?? 'No se pudo completar la búsqueda en AliExpress',
                    'raw'       => $resultado['raw'] ?? null,
                ], 400);
            }

            $raw = $resultado['raw'] ?? [];
            $itemsRaw = $this->extraerItemsBusquedaAliexpress($raw);
            $palabrasCoincidentes = trim((string) $request->input('palabras_coincidentes', ''));
            $items = $this->obtenerItemsAliexpressFiltrados($itemsRaw, $palabrasCoincidentes);
            $items = $this->enriquecerItemsConEstadoUrl($items);

            return response()->json([
                'success'               => true,
                'proveedor'             => 'aliexpress',
                'keywords'              => $resultado['keywords'] ?? $request->input('q'),
                'palabras_coincidentes' => $palabrasCoincidentes,
                'page_no'               => $resultado['page_no'] ?? 1,
                'page_size'             => $resultado['page_size'] ?? 50,
                'total_aliexpress'      => count($itemsRaw),
                'total_filtrados'       => count($items),
                'items'                 => $items,
                'raw'                   => $raw,
            ]);
        }

        $resultado = $this->peticionApiHtml->buscarProductosAmazonMultipagina(
            $request->input('q'),
            5,
            2
        );

        if (!($resultado['success'] ?? false)) {
            return response()->json([
                'success' => false,
                'error'   => $resultado['error'] ?? 'No se pudo completar la búsqueda',
                'raw'     => $resultado['raw'] ?? null,
            ], 400);
        }

        $raw = $resultado['raw'] ?? [];
        $itemsRaw = $this->extraerItemsBusquedaAmazon($raw);
        $palabrasCoincidentes = trim((string) $request->input('palabras_coincidentes', ''));
        $items = $this->obtenerItemsAmazonFiltrados($itemsRaw, $palabrasCoincidentes);
        $items = $this->enriquecerItemsConEstadoUrl($items);

        return response()->json([
            'success'               => true,
            'proveedor'             => 'amazon',
            'keywords'              => $resultado['keywords'] ?? $request->input('q'),
            'palabras_coincidentes' => $palabrasCoincidentes,
            'paginas_consultadas'   => $resultado['paginas_consultadas'] ?? 1,
            'total_amazon'          => $resultado['total_items_amazon'] ?? count($itemsRaw),
            'total_filtrados'       => count($items),
            'items'                 => $items,
        ]);
    }

    /**
     * Cron: por cada producto elegible busca en Amazon y AliExpress, filtra por palabras_exigidas e inserta URLs en Neo.
     * Reanuda desde el último producto_id y, si hace falta, da la vuelta desde id 1 hasta completar el lote (máx. 25).
     *
     * @return array<string, mixed> Resumen de la ejecución al terminar.
     */
    public function ejecutarCron(): array
    {
        @set_time_limit(0);
        @ini_set('max_execution_time', '0');
        ignore_user_abort(true);

        $this->cerrarEjecucionesColgadas();

        $ultimoProductoId = $this->obtenerUltimoProductoIdProcesado();
        $productosElegiblesTotal = $this->contarProductosElegiblesTotales();
        $productos = $this->obtenerLoteProductosElegibles($ultimoProductoId, self::LIMITE_PRODUCTOS_POR_EJECUCION);

        $inicio = now();
        $ejecucion = EjecucionGlobal::create([
            'inicio'         => $inicio,
            'fin'            => null,
            'nombre'         => self::NOMBRE_EJECUCION_GLOBAL,
            'total'          => 0,
            'total_guardado' => 0,
            'total_errores'  => 0,
            'log'            => [
                'estado'      => 'running',
                'paso_actual' => 'consulta',
                'pasos'       => [
                    ['momento' => now()->toDateTimeString(), 'paso' => 'inicio', 'detalle' => 'Ejecución creada', 'contexto' => []],
                ],
            ],
        ]);

        self::$ejecucionEnCursoId = (int) $ejecucion->id;
        self::$ejecucionFinalizada = false;
        register_shutdown_function([self::class, 'shutdownMarcarEjecucionInterrumpida']);

        $contadores = [
            'productos_en_query'           => $productos->count(),
            'productos_elegibles_total'    => $productosElegiblesTotal,
            'ultimo_producto_id_previo'    => $ultimoProductoId,
            'omitido_sin_nombre'           => 0,
            'omitido_sin_palabras'         => 0,
            'productos_procesados'         => 0,
            'productos_error_amazon'       => 0,
            'productos_error_aliexpress'   => 0,
            'urls_amazon_filtradas'        => 0,
            'urls_aliexpress_filtradas'    => 0,
            'urls_omitida_oferta'          => 0,
            'urls_omitida_descartada'      => 0,
            'urls_omitida_neo'             => 0,
            'urls_insertadas_neo'          => 0,
            'limite_por_ejecucion'         => self::LIMITE_PRODUCTOS_POR_EJECUCION,
        ];
        $resultados = [];
        self::$contadoresEnCurso = &$contadores;
        self::$resultadosEnCurso = &$resultados;
        $ultimoProductoIdActual = $ultimoProductoId;

        try {
            $this->actualizarEjecucionPaso($ejecucion, 'consulta', [
                'detalle' => 'Lote circular desde id>' . $ultimoProductoId . ' (mostrar=si, obsoleto=no, nombre y palabras_exigidas)',
                'productos_en_lote' => $productos->count(),
                'productos_elegibles_total' => $productosElegiblesTotal,
                'ultimo_producto_id_previo' => $ultimoProductoId,
                'limite_por_ejecucion' => self::LIMITE_PRODUCTOS_POR_EJECUCION,
            ]);

            if ($productos->isEmpty()) {
                $this->finalizarEjecucionCron($ejecucion, $contadores, $resultados, $ultimoProductoId, 'ok', 'sin_elegibles');

                return $this->buildResumenEjecucion($ejecucion);
            }

            $totalProductos = $productos->count();
            $indiceProducto = 0;

            foreach ($productos as $producto) {
                $indiceProducto++;
                $ultimoProductoIdActual = (int) $producto->id;
                $nombre = trim((string) $producto->nombre);
                $palabrasExigidas = trim((string) ($producto->palabras_exigidas ?? ''));

                if ($nombre === '') {
                    $contadores['omitido_sin_nombre']++;
                    continue;
                }

                if ($palabrasExigidas === '') {
                    $contadores['omitido_sin_palabras']++;
                    continue;
                }

                $resultadoProducto = [
                    'producto_id'        => $producto->id,
                    'nombre'             => $nombre,
                    'palabras_exigidas'  => $palabrasExigidas,
                    'paginas_amazon'     => 0,
                    'urls_amazon'        => 0,
                    'urls_aliexpress'    => 0,
                    'urls_insertadas'    => 0,
                    'urls_omitidas'      => 0,
                    'detalle_urls'       => [],
                    'error_amazon'       => null,
                    'error_aliexpress'   => null,
                ];

                $statsAmazon = $this->procesarBusquedaAmazonEnCron($producto, $nombre, $palabrasExigidas, $contadores);
                $resultadoProducto['paginas_amazon'] = $statsAmazon['paginas'];
                $resultadoProducto['urls_amazon'] = $statsAmazon['urls_filtradas'];
                if ($statsAmazon['error'] !== null) {
                    $resultadoProducto['error_amazon'] = $statsAmazon['error'];
                    $this->actualizarEjecucionPaso($ejecucion, 'producto_error_amazon', [
                        'detalle' => 'Error Amazon para producto #' . $producto->id,
                        'producto_id' => $producto->id,
                        'error' => $statsAmazon['error'],
                    ]);
                } else {
                    $resultadoProducto['urls_insertadas'] += $statsAmazon['insertadas'];
                    $resultadoProducto['urls_omitidas'] += $statsAmazon['omitidas'];
                    $resultadoProducto['detalle_urls'] = array_merge(
                        $resultadoProducto['detalle_urls'],
                        $statsAmazon['detalle_urls']
                    );
                }

                sleep(self::ESPERA_ENTRE_TIENDAS_SEGUNDOS);

                $statsAli = $this->procesarBusquedaAliexpressEnCron($producto, $nombre, $palabrasExigidas, $contadores);
                $resultadoProducto['urls_aliexpress'] = $statsAli['urls_filtradas'];
                if ($statsAli['error'] !== null) {
                    $resultadoProducto['error_aliexpress'] = $statsAli['error'];
                    $this->actualizarEjecucionPaso($ejecucion, 'producto_error_aliexpress', [
                        'detalle' => 'Error AliExpress para producto #' . $producto->id,
                        'producto_id' => $producto->id,
                        'error' => $statsAli['error'],
                    ]);
                } else {
                    $resultadoProducto['urls_insertadas'] += $statsAli['insertadas'];
                    $resultadoProducto['urls_omitidas'] += $statsAli['omitidas'];
                    $resultadoProducto['detalle_urls'] = array_merge(
                        $resultadoProducto['detalle_urls'],
                        $statsAli['detalle_urls']
                    );
                }

                $contadores['productos_procesados']++;
                $resultados[] = $resultadoProducto;

                $this->actualizarEjecucionPaso($ejecucion, 'producto', [
                    'detalle' => 'Producto #' . $producto->id . ' procesado (Amazon + AliExpress)',
                    'producto_id' => $producto->id,
                    'paginas_amazon' => $resultadoProducto['paginas_amazon'],
                    'urls_amazon' => $resultadoProducto['urls_amazon'],
                    'urls_aliexpress' => $resultadoProducto['urls_aliexpress'],
                    'urls_insertadas' => $resultadoProducto['urls_insertadas'],
                    'urls_omitidas' => $resultadoProducto['urls_omitidas'],
                    'error_amazon' => $resultadoProducto['error_amazon'],
                    'error_aliexpress' => $resultadoProducto['error_aliexpress'],
                ]);

                $this->persistirProgresoEjecucion($ejecucion, $contadores, $resultados, $ultimoProductoIdActual);
                $this->pulsoConexionHttp();

                if ($indiceProducto < $totalProductos) {
                    sleep(self::ESPERA_ENTRE_PRODUCTOS_SEGUNDOS);
                }
            }

            $estadoFinal = $productosElegiblesTotal > $productos->count()
                ? 'ok_parcial'
                : 'ok';

            $this->finalizarEjecucionCron($ejecucion, $contadores, $resultados, $ultimoProductoIdActual, $estadoFinal, null);

            return $this->buildResumenEjecucion($ejecucion);
        } catch (\Throwable $e) {
            Log::error('Cron buscar productos Amazon/AliExpress: ' . $e->getMessage(), ['exception' => $e]);
            self::$ejecucionFinalizada = true;
            $this->actualizarEjecucionError(
                $ejecucion,
                $e,
                $contadores,
                $resultados,
                $ultimoProductoIdActual ?? $ultimoProductoId
            );
            $this->eliminarEjecucionesAntiguas();
            self::$ejecucionEnCursoId = null;

            return $this->buildResumenEjecucion($ejecucion, $e);
        }
    }

    /**
     * Ejecuta el cron y redirige al historial de ejecuciones (misma vista del panel).
     */
    public function ejecutarCronConResumen(Request $request): HttpResponse|RedirectResponse|View|Response
    {
        if ($request->get('token') !== env('TOKEN_ACTUALIZAR_PRECIOS')) {
            abort(403, 'Token inválido');
        }

        $this->iniciarSalidaHttpMantenimientoConexion();

        try {
            $resumen = $this->ejecutarCron();
            $ejecucion = $resumen['ejecucion'] ?? null;

            if ($ejecucion === null) {
                return $this->cerrarSalidaHttpCronConMensaje('No se creó ninguna ejecución.', null);
            }

            $params = [
                'ejecucion_id' => $ejecucion->id,
                'fecha'        => $ejecucion->inicio?->format('Y-m-d'),
                'mes'          => $ejecucion->inicio?->format('Y-m'),
            ];
            $urlPanel = route('admin.ejecuciones.buscar-amazon-productos', $params);

            return $this->cerrarSalidaHttpCronConMensaje(
                'Cron finalizado correctamente.',
                $urlPanel
            );
        } catch (\Throwable $e) {
            Log::error('Cron buscar productos (HTTP): ' . $e->getMessage(), ['exception' => $e]);

            return $this->cerrarSalidaHttpCronConMensaje(
                'Error: ' . $e->getMessage(),
                auth()->check() ? route('admin.ejecuciones.buscar-amazon-productos') : null
            );
        }
    }

    /**
     * Mantiene viva la conexión HTTP (sin mostrar progreso) para evitar timeout del proxy ~5 min.
     */
    private function iniciarSalidaHttpMantenimientoConexion(): void
    {
        if (headers_sent()) {
            return;
        }

        while (ob_get_level() > 0) {
            @ob_end_clean();
        }

        header('Content-Type: text/html; charset=utf-8');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('X-Accel-Buffering: no');
        echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Procesando cron…</title></head><body style="font-family:sans-serif;padding:16px;color:#666">';
        echo '<p>Procesando lote de productos (Amazon + AliExpress). No cierres esta pestaña…</p>';
        echo str_repeat(' ', 2048);
        $this->pulsoConexionHttp();
    }

    private function pulsoConexionHttp(): void
    {
        echo "\n";
        if (ob_get_level() > 0) {
            @ob_flush();
        }
        @flush();
    }

    private function cerrarSalidaHttpCronConMensaje(string $mensaje, ?string $urlDestino): Response
    {
        $html = '<p style="font-family:sans-serif;padding:16px"><strong>' . htmlspecialchars($mensaje) . '</strong></p>';
        if ($urlDestino !== null) {
            $html .= '<p><a href="' . htmlspecialchars($urlDestino) . '">Ver ejecución en el panel</a></p>';
            $html .= '<script>setTimeout(function(){location.replace(' . json_encode($urlDestino) . ');},500);</script>';
        }
        $html .= '</body></html>';

        return response($html, 200)->header('Content-Type', 'text/html; charset=utf-8');
    }

    /**
     * Vista de historial y detalle de ejecuciones (compartida con panel admin y URL del cron).
     */
    public function vistaHistorialEjecucionesBuscarAmazon(Request $request): View
    {
        $nombreEjecucion = self::NOMBRE_EJECUCION_GLOBAL;
        $hoy = Carbon::today();
        $mesSeleccionado = $hoy->copy()->startOfMonth();
        $fechaSeleccionada = $hoy->copy()->startOfDay();

        if ($request->filled('mes') && preg_match('/^\d{4}-\d{2}$/', (string) $request->input('mes'))) {
            try {
                $mesSeleccionado = Carbon::createFromFormat('Y-m', (string) $request->input('mes'))->startOfMonth();
            } catch (\Throwable $e) {
                //
            }
        }
        if ($request->filled('fecha') && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $request->input('fecha'))) {
            try {
                $fechaSeleccionada = Carbon::createFromFormat('Y-m-d', (string) $request->input('fecha'))->startOfDay();
            } catch (\Throwable $e) {
                //
            }
        }

        $ejecucion = null;
        if ($request->filled('ejecucion_id')) {
            $ejecucion = EjecucionGlobal::query()
                ->where('nombre', $nombreEjecucion)
                ->find($request->input('ejecucion_id'));
            if (!$ejecucion) {
                abort(404, 'Ejecución no encontrada');
            }
            if ($ejecucion->inicio) {
                $fechaSeleccionada = $ejecucion->inicio->copy()->startOfDay();
                $mesSeleccionado = $ejecucion->inicio->copy()->startOfMonth();
            }
        }

        $inicioMes = $mesSeleccionado->copy()->startOfMonth();
        $finMes = $mesSeleccionado->copy()->endOfMonth();

        $fechasConEjecuciones = EjecucionGlobal::query()
            ->where('nombre', $nombreEjecucion)
            ->whereBetween('inicio', [$inicioMes, $finMes])
            ->selectRaw('DATE(inicio) as fecha, COUNT(*) as total')
            ->groupBy('fecha')
            ->pluck('total', 'fecha')
            ->toArray();

        $ejecuciones = EjecucionGlobal::query()
            ->where('nombre', $nombreEjecucion)
            ->whereDate('inicio', $fechaSeleccionada->toDateString())
            ->orderByDesc('id')
            ->get(['id', 'inicio', 'fin', 'total', 'total_guardado', 'total_errores']);

        return view('admin.crons.cron_buscar_amazon_productos_resultado', [
            'ejecucion_id'          => $ejecucion?->id,
            'ejecucion'             => $ejecucion,
            'ejecuciones'           => $ejecuciones,
            'fechaSeleccionada'     => $fechaSeleccionada,
            'mesSeleccionado'       => $mesSeleccionado,
            'inicioMes'             => $inicioMes,
            'finMes'                => $finMes,
            'fechasConEjecuciones'  => $fechasConEjecuciones,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildResumenEjecucion(EjecucionGlobal $ejecucion, ?\Throwable $excepcion = null): array
    {
        $ejecucion->refresh();
        $log = is_array($ejecucion->log) ? $ejecucion->log : [];

        return [
            'ejecucion'  => $ejecucion,
            'estado'     => $log['estado'] ?? 'desconocido',
            'detalle_final' => $log['detalle_final'] ?? null,
            'contadores' => $log['contadores'] ?? [],
            'resultados' => $log['resultados'] ?? [],
            'error_log'  => is_array($log['error'] ?? null) ? $log['error'] : null,
            'excepcion'  => $excepcion,
        ];
    }

    public static function shutdownMarcarEjecucionInterrumpida(): void
    {
        if (self::$ejecucionFinalizada || self::$ejecucionEnCursoId === null) {
            return;
        }

        try {
            $ejecucion = EjecucionGlobal::query()->find(self::$ejecucionEnCursoId);
            if (!$ejecucion || $ejecucion->fin !== null) {
                return;
            }

            $log = is_array($ejecucion->log) ? $ejecucion->log : [];
            if (($log['estado'] ?? '') === 'ok' || ($log['estado'] ?? '') === 'ok_parcial') {
                return;
            }

            $log['estado'] = 'interrumpido';
            $log['paso_actual'] = 'interrumpido';
            $log['error'] = [
                'mensaje' => 'La ejecución se interrumpió (timeout HTTP, límite del servidor o cierre de proceso).',
                'tipo'    => 'shutdown',
            ];
            if (self::$contadoresEnCurso !== null) {
                $log['contadores'] = self::$contadoresEnCurso;
            }
            if (self::$resultadosEnCurso !== null) {
                $log['resultados'] = self::$resultadosEnCurso;
                $ultimo = end(self::$resultadosEnCurso);
                if (is_array($ultimo) && isset($ultimo['producto_id'])) {
                    $log['ultimo_producto_id'] = (int) $ultimo['producto_id'];
                }
            }

            $contadores = self::$contadoresEnCurso ?? [];

            $ejecucion->update([
                'fin'            => now(),
                'total'          => (int) ($contadores['productos_procesados'] ?? 0),
                'total_guardado' => (int) ($contadores['urls_insertadas_neo'] ?? 0),
                'total_errores'  => self::contarErroresTiendasCron($contadores),
                'log'            => $log,
            ]);
        } catch (\Throwable $e) {
            Log::error('Cron buscar productos shutdown: ' . $e->getMessage());
        } finally {
            self::$ejecucionEnCursoId = null;
        }
    }

    private function cerrarEjecucionesColgadas(): void
    {
        try {
            EjecucionGlobal::query()
                ->where('nombre', self::NOMBRE_EJECUCION_GLOBAL)
                ->whereNull('fin')
                ->where('inicio', '<', now()->subMinutes(5))
                ->orderBy('id')
                ->each(function (EjecucionGlobal $ejecucion) {
                    $log = is_array($ejecucion->log) ? $ejecucion->log : [];
                    $log['estado'] = 'interrumpido';
                    $log['paso_actual'] = 'interrumpido';
                    $log['error'] = [
                        'mensaje' => 'Ejecución cerrada automáticamente (quedó colgada sin fecha fin).',
                        'tipo'    => 'auto_cierre',
                    ];
                    $ejecucion->update([
                        'fin' => now(),
                        'log' => $log,
                    ]);
                });
        } catch (\Throwable $e) {
            //
        }
    }

    private function queryProductosElegiblesBusqueda(): \Illuminate\Database\Eloquent\Builder
    {
        return Producto::query()
            ->where('mostrar', 'si')
            ->where('obsoleto', 'no')
            ->whereNotNull('palabras_exigidas')
            ->where('palabras_exigidas', '!=', '')
            ->whereNotNull('nombre')
            ->where('nombre', '!=', '');
    }

    /**
     * Siguientes productos elegibles desde $ultimoProductoId; si no alcanza el límite, continúa desde id 1.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Producto>
     */
    private function obtenerLoteProductosElegibles(int $ultimoProductoId, int $limite): \Illuminate\Database\Eloquent\Collection
    {
        $columnas = ['id', 'nombre', 'categoria_id', 'palabras_exigidas'];
        $lote = new \Illuminate\Database\Eloquent\Collection();

        $faltan = $limite;
        if ($faltan > 0) {
            $tramoAlto = $this->queryProductosElegiblesBusqueda()
                ->where('id', '>', $ultimoProductoId)
                ->orderBy('id')
                ->limit($faltan)
                ->get($columnas);
            foreach ($tramoAlto as $producto) {
                $lote->push($producto);
            }
            $faltan = $limite - $lote->count();
        }

        if ($faltan > 0 && $ultimoProductoId > 0) {
            $idsEnLote = $lote->pluck('id')->all();
            $queryBajo = $this->queryProductosElegiblesBusqueda()
                ->where('id', '<=', $ultimoProductoId)
                ->orderBy('id')
                ->limit($faltan);

            if ($idsEnLote !== []) {
                $queryBajo->whereNotIn('id', $idsEnLote);
            }

            foreach ($queryBajo->get($columnas) as $producto) {
                $lote->push($producto);
            }
        }

        return $lote;
    }

    private function contarProductosElegiblesTotales(): int
    {
        return (int) $this->queryProductosElegiblesBusqueda()->count();
    }

    private function obtenerUltimoProductoIdProcesado(): int
    {
        $ejecuciones = EjecucionGlobal::query()
            ->where('nombre', self::NOMBRE_EJECUCION_GLOBAL)
            ->orderByDesc('id')
            ->limit(20)
            ->get(['log']);

        foreach ($ejecuciones as $ejecucion) {
            $log = is_array($ejecucion->log) ? $ejecucion->log : [];
            $ultimo = (int) ($log['ultimo_producto_id'] ?? 0);
            if ($ultimo > 0) {
                return $ultimo;
            }
        }

        return 0;
    }

    /**
     * @param array<string, int> $contadores
     * @param array<int, array<string, mixed>> $resultados
     */
    private function finalizarEjecucionCron(
        EjecucionGlobal $ejecucion,
        array $contadores,
        array $resultados,
        int $ultimoProductoId,
        string $estado,
        ?string $detalleExtra
    ): void {
        $ejecucion->refresh();
        $logBase = is_array($ejecucion->log) ? $ejecucion->log : [];
        $logBase['estado'] = $estado;
        $logBase['paso_actual'] = 'finalizado';
        $logBase['contadores'] = $contadores;
        $logBase['resultados'] = $resultados;
        $logBase['ultimo_producto_id'] = $ultimoProductoId;
        if ($detalleExtra !== null) {
            $logBase['detalle_final'] = $detalleExtra;
        }

        $ejecucion->update([
            'fin'            => now(),
            'total'          => $contadores['productos_procesados'],
            'total_guardado' => $contadores['urls_insertadas_neo'],
            'total_errores'  => self::contarErroresTiendasCron($contadores),
            'log'            => $logBase,
        ]);

        self::$ejecucionFinalizada = true;
        self::$ejecucionEnCursoId = null;
        $this->eliminarEjecucionesAntiguas();
    }

    /**
     * @param array<int, array<string, mixed>> $itemsRaw
     * @return array<int, array<string, mixed>>
     */
    private function obtenerItemsAmazonFiltrados(array $itemsRaw, string $palabrasCoincidentes): array
    {
        $itemsFiltrados = $this->filtrarItemsAmazonPorPalabrasCoincidentes($itemsRaw, $palabrasCoincidentes);
        $items = array_map(fn (array $item) => $this->mapearItemAmazonBusqueda($item), $itemsFiltrados);

        return array_values(array_filter($items, fn (array $item) => !empty($item['asin'])));
    }

    /**
     * Devuelve el motivo de omisión si la URL ya existe en ofertas, descartadas o neo; null si se puede insertar.
     */
    /**
     * @param array<string, int> $contadores
     */
    private static function contarErroresTiendasCron(array $contadores): int
    {
        return (int) ($contadores['productos_error_amazon'] ?? 0)
            + (int) ($contadores['productos_error_aliexpress'] ?? 0);
    }

    /**
     * Guarda en BD el progreso parcial (por si el proceso se corta por timeout del servidor).
     *
     * @param array<string, int> $contadores
     * @param array<int, array<string, mixed>> $resultados
     */
    private function persistirProgresoEjecucion(
        EjecucionGlobal $ejecucion,
        array $contadores,
        array $resultados,
        int $ultimoProductoId
    ): void {
        try {
            $ejecucion->refresh();
            $log = is_array($ejecucion->log) ? $ejecucion->log : [];
            $log['estado'] = 'running';
            $log['paso_actual'] = 'producto';
            $log['contadores'] = $contadores;
            $log['resultados'] = $resultados;
            $log['ultimo_producto_id'] = $ultimoProductoId;

            $ejecucion->update([
                'total'          => (int) ($contadores['productos_procesados'] ?? 0),
                'total_guardado' => (int) ($contadores['urls_insertadas_neo'] ?? 0),
                'total_errores'  => self::contarErroresTiendasCron($contadores),
                'log'            => $log,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Cron buscar productos: no se pudo persistir progreso parcial: ' . $e->getMessage());
        }
    }

    /**
     * @param array<string, int> $contadores
     * @return array{
     *     error: ?string,
     *     paginas: int,
     *     urls_filtradas: int,
     *     insertadas: int,
     *     omitidas: int,
     *     detalle_urls: array<int, array<string, mixed>>
     * }
     */
    private function procesarBusquedaAmazonEnCron(Producto $producto, string $nombre, string $palabrasExigidas, array &$contadores): array
    {
        $vacio = [
            'error'          => null,
            'paginas'        => 0,
            'urls_filtradas' => 0,
            'insertadas'     => 0,
            'omitidas'       => 0,
            'detalle_urls'   => [],
        ];

        $resultadoAmazon = $this->peticionApiHtml->buscarProductosAmazonMultipagina($nombre, 5, 2);

        if (!($resultadoAmazon['success'] ?? false)) {
            $contadores['productos_error_amazon']++;

            return array_merge($vacio, [
                'error' => $resultadoAmazon['error'] ?? 'Error desconocido en Amazon',
            ]);
        }

        $itemsRaw = $this->extraerItemsBusquedaAmazon($resultadoAmazon['raw'] ?? []);
        $items = $this->obtenerItemsAmazonFiltrados($itemsRaw, $palabrasExigidas);
        $contadores['urls_amazon_filtradas'] += count($items);

        $insercion = $this->insertarItemsEnNeoDesdeCron($producto, $items, 'amazon', $contadores);

        return [
            'error'          => null,
            'paginas'        => (int) ($resultadoAmazon['paginas_consultadas'] ?? 1),
            'urls_filtradas' => count($items),
            'insertadas'     => $insercion['insertadas'],
            'omitidas'       => $insercion['omitidas'],
            'detalle_urls'   => $insercion['detalle_urls'],
        ];
    }

    /**
     * @param array<string, int> $contadores
     * @return array{
     *     error: ?string,
     *     urls_filtradas: int,
     *     insertadas: int,
     *     omitidas: int,
     *     detalle_urls: array<int, array<string, mixed>>
     * }
     */
    private function procesarBusquedaAliexpressEnCron(Producto $producto, string $nombre, string $palabrasExigidas, array &$contadores): array
    {
        $vacio = [
            'error'          => null,
            'urls_filtradas' => 0,
            'insertadas'     => 0,
            'omitidas'       => 0,
            'detalle_urls'   => [],
        ];

        $resultadoAli = $this->peticionApiHtml->buscarProductosAliexpress($nombre, 1, 50);

        if (!($resultadoAli['success'] ?? false)) {
            $contadores['productos_error_aliexpress']++;

            return array_merge($vacio, [
                'error' => $resultadoAli['error'] ?? 'Error desconocido en AliExpress',
            ]);
        }

        $itemsRaw = $this->extraerItemsBusquedaAliexpress($resultadoAli['raw'] ?? []);
        $items = $this->obtenerItemsAliexpressFiltrados($itemsRaw, $palabrasExigidas);
        $contadores['urls_aliexpress_filtradas'] += count($items);

        $insercion = $this->insertarItemsEnNeoDesdeCron($producto, $items, 'aliexpress', $contadores);

        return [
            'error'          => null,
            'urls_filtradas' => count($items),
            'insertadas'     => $insercion['insertadas'],
            'omitidas'       => $insercion['omitidas'],
            'detalle_urls'   => $insercion['detalle_urls'],
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $items
     * @param array<string, int> $contadores
     * @return array{insertadas: int, omitidas: int, detalle_urls: array<int, array<string, mixed>>}
     */
    private function insertarItemsEnNeoDesdeCron(Producto $producto, array $items, string $tienda, array &$contadores): array
    {
        $insertadas = 0;
        $omitidas = 0;
        $detalleUrls = [];

        foreach ($items as $item) {
            $urlLimpia = $this->limpiarUrlDeTiendas->limpiar((string) ($item['url'] ?? ''));
            if ($urlLimpia === '') {
                continue;
            }

            $motivoOmision = $this->motivoOmisionUrlEnSistema($urlLimpia);
            if ($motivoOmision !== null) {
                $contadores['urls_omitida_' . $motivoOmision]++;
                $omitidas++;
                $detalleUrls[] = [
                    'url'    => $urlLimpia,
                    'tienda' => $tienda,
                    'accion' => 'omitida',
                    'motivo' => $motivoOmision,
                ];
                continue;
            }

            Neo::create([
                'producto_id'  => $producto->id,
                'categoria_id' => $producto->categoria_id,
                'url'          => $urlLimpia,
                'aniadida'     => 'no',
            ]);

            $contadores['urls_insertadas_neo']++;
            $insertadas++;
            $detalleUrls[] = [
                'url'    => $urlLimpia,
                'tienda' => $tienda,
                'accion' => 'insertada',
            ];
        }

        return [
            'insertadas'   => $insertadas,
            'omitidas'     => $omitidas,
            'detalle_urls' => $detalleUrls,
        ];
    }

    private function motivoOmisionUrlEnSistema(string $urlLimpia): ?string
    {
        $lookup = $this->consultarNeoCifrado->hashLookup($urlLimpia);

        if ($lookup !== '' && OfertaProducto::query()->where('url_lookup', $lookup)->exists()) {
            return 'oferta';
        }

        if (UrlDescartada::query()->where('url', $urlLimpia)->exists()) {
            return 'descartada';
        }

        if ($lookup !== '' && Neo::query()->where('url_lookup', $lookup)->exists()) {
            return 'neo';
        }

        return null;
    }

    private function actualizarEjecucionPaso(EjecucionGlobal $ejecucion, string $paso, array $contexto = []): void
    {
        try {
            $log = is_array($ejecucion->log) ? $ejecucion->log : [];
            $pasos = isset($log['pasos']) && is_array($log['pasos']) ? $log['pasos'] : [];
            $detalle = $contexto['detalle'] ?? null;
            unset($contexto['detalle']);

            $pasos[] = [
                'momento'  => now()->toDateTimeString(),
                'paso'     => $paso,
                'detalle'  => $detalle,
                'contexto' => $contexto,
            ];

            $log['estado'] = 'running';
            $log['paso_actual'] = $paso;
            $log['pasos'] = $pasos;

            $ejecucion->update(['log' => $log]);
        } catch (\Throwable $e) {
            //
        }
    }

    /**
     * @param array<string, int>|null $contadores
     * @param array<int, array<string, mixed>>|null $resultados
     */
    private function actualizarEjecucionError(
        EjecucionGlobal $ejecucion,
        \Throwable $e,
        ?array $contadores = null,
        ?array $resultados = null,
        int $ultimoProductoId = 0
    ): void {
        try {
            $log = is_array($ejecucion->log) ? $ejecucion->log : [];
            $pasoActual = $log['paso_actual'] ?? 'desconocido';
            $pasos = isset($log['pasos']) && is_array($log['pasos']) ? $log['pasos'] : [];
            $pasos[] = [
                'momento'  => now()->toDateTimeString(),
                'paso'     => 'error',
                'detalle'  => 'Excepción no controlada',
                'contexto' => ['paso_en_el_que_fallo' => $pasoActual, 'error' => $e->getMessage()],
            ];

            $log['estado'] = 'error';
            $log['paso_actual'] = 'error';
            $log['error'] = ['mensaje' => $e->getMessage(), 'tipo' => get_class($e)];
            $log['pasos'] = $pasos;
            if ($contadores !== null) {
                $log['contadores'] = $contadores;
            }
            if ($resultados !== null) {
                $log['resultados'] = $resultados;
            }
            if ($ultimoProductoId > 0) {
                $log['ultimo_producto_id'] = $ultimoProductoId;
            }

            $ejecucion->update([
                'fin'            => now(),
                'total'          => (int) ($contadores['productos_procesados'] ?? $ejecucion->total),
                'total_guardado' => (int) ($contadores['urls_insertadas_neo'] ?? $ejecucion->total_guardado),
                'total_errores'  => self::contarErroresTiendasCron($contadores ?? []) + 1,
                'log'            => $log,
            ]);
        } catch (\Throwable $inner) {
            //
        }
    }

    private function eliminarEjecucionesAntiguas(): void
    {
        try {
            EjecucionGlobal::query()
                ->where('nombre', self::NOMBRE_EJECUCION_GLOBAL)
                ->where('created_at', '<', now()->subDays(self::RETENCION_EJECUCIONES_DIAS))
                ->delete();
        } catch (\Throwable $e) {
            //
        }
    }

    /**
     * @param array<int, array<string, mixed>> $itemsRaw
     * @return array<int, array<string, mixed>>
     */
    private function obtenerItemsAliexpressFiltrados(array $itemsRaw, string $palabrasCoincidentes): array
    {
        $items = array_map(fn (array $item) => $this->mapearItemAliexpressBusqueda($item), $itemsRaw);
        $items = array_values(array_filter($items, fn (array $item) => ($item['url'] ?? '') !== ''));

        return $this->filtrarItemsMapeadosPorPalabrasCoincidentes($items, $palabrasCoincidentes);
    }

    /**
     * @param array<int, array<string, mixed>> $items
     * @return array<int, array<string, mixed>>
     */
    private function filtrarItemsMapeadosPorPalabrasCoincidentes(array $items, string $palabrasCoincidentes): array
    {
        $palabras = $this->parsearPalabrasCoincidentesAmazon($palabrasCoincidentes);
        if ($palabras === []) {
            return $items;
        }

        return array_values(array_filter($items, function (array $item) use ($palabras) {
            $titulo = (string) ($item['titulo'] ?? '');
            if ($titulo === '') {
                return false;
            }

            foreach ($palabras as $palabra) {
                if (!$this->palabraCoincideEnTituloAmazon($titulo, $palabra)) {
                    return false;
                }
            }

            return true;
        }));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function extraerItemsBusquedaAliexpress(array $raw): array
    {
        $product = $raw['aliexpress_affiliate_product_query_response']['resp_result']['result']['products']['product']
            ?? $raw['resp_result']['result']['products']['product']
            ?? null;

        if ($product === null || !is_array($product)) {
            return [];
        }

        if ($this->esObjetoProductoAliexpress($product)) {
            return [$product];
        }

        return array_values(array_filter($product, 'is_array'));
    }

    private function esObjetoProductoAliexpress(array $item): bool
    {
        return isset($item['product_id']) || isset($item['product_title']);
    }

    /**
     * @return array{product_id: string, asin: string, titulo: string, imagen: ?string, url: string}
     */
    private function mapearItemAliexpressBusqueda(array $item): array
    {
        $productId = (string) ($item['product_id'] ?? '');
        $url = trim((string) ($item['product_detail_url'] ?? ''));
        if ($url === '' && $productId !== '') {
            $url = 'https://es.aliexpress.com/item/' . $productId . '.html';
        }

        return [
            'product_id' => $productId,
            'asin'       => $productId,
            'titulo'     => trim((string) ($item['product_title'] ?? '')),
            'imagen'     => $this->extraerImagenItemAliexpress($item),
            'url'        => $url,
        ];
    }

    private function extraerImagenItemAliexpress(array $item): ?string
    {
        $main = trim((string) ($item['product_main_image_url'] ?? ''));
        if ($main !== '') {
            return $main;
        }

        $smallUrls = $item['product_small_image_urls']['string'] ?? null;
        if (is_array($smallUrls) && isset($smallUrls[0]) && is_string($smallUrls[0])) {
            return trim($smallUrls[0]) !== '' ? trim($smallUrls[0]) : null;
        }

        if (is_string($smallUrls) && trim($smallUrls) !== '') {
            return trim($smallUrls);
        }

        return null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function extraerItemsBusquedaAmazon(array $raw): array
    {
        $items = $raw['searchResult']['items']
            ?? $raw['SearchResult']['Items']
            ?? [];

        return is_array($items) ? $items : [];
    }

    /**
     * @param array<int, array<string, mixed>> $items
     * @return array<int, array<string, mixed>>
     */
    private function filtrarItemsAmazonPorPalabrasCoincidentes(array $items, string $palabrasCoincidentes): array
    {
        $palabras = $this->parsearPalabrasCoincidentesAmazon($palabrasCoincidentes);
        if ($palabras === []) {
            return $items;
        }

        return array_values(array_filter($items, function (array $item) use ($palabras) {
            $titulo = $this->extraerTituloItemAmazon($item);
            if ($titulo === '') {
                return false;
            }

            foreach ($palabras as $palabra) {
                if (!$this->palabraCoincideEnTituloAmazon($titulo, $palabra)) {
                    return false;
                }
            }

            return true;
        }));
    }

    /**
     * @return array<int, string>
     */
    private function parsearPalabrasCoincidentesAmazon(string $palabras): array
    {
        $palabras = trim($palabras);
        if ($palabras === '') {
            return [];
        }

        $partes = preg_split('/[\s,;]+/u', $palabras, -1, PREG_SPLIT_NO_EMPTY);

        return is_array($partes) ? array_values($partes) : [];
    }

    private function extraerTituloItemAmazon(array $item): string
    {
        return (string) (
            $item['itemInfo']['title']['displayValue']
            ?? $item['ItemInfo']['Title']['DisplayValue']
            ?? ''
        );
    }

    /**
     * Coincidencia flexible: sin distinguir mayúsculas; ignora guiones y espacios (9060 XT = 9060XT, E589 = E-589);
     * permite sufijos (E589 en E589DAS…).
     */
    private function palabraCoincideEnTituloAmazon(string $titulo, string $palabra): bool
    {
        $palabra = trim($palabra);
        if ($palabra === '') {
            return true;
        }

        $tituloNorm = $this->normalizarTextoCoincidenciaAmazon($titulo);
        $palabraNorm = $this->normalizarTextoCoincidenciaAmazon($palabra);

        if ($palabraNorm === '') {
            return true;
        }

        return str_contains($tituloNorm, $palabraNorm);
    }

    private function normalizarTextoCoincidenciaAmazon(string $texto): string
    {
        $texto = mb_strtolower($texto, 'UTF-8');
        $texto = str_replace('-', '', $texto);
        $texto = preg_replace('/\s+/u', '', $texto) ?? $texto;

        return $texto;
    }

    /**
     * @return array{asin: string, titulo: string, imagen: ?string, url: string}
     */
    private function mapearItemAmazonBusqueda(array $item): array
    {
        $asin = (string) ($item['asin'] ?? $item['ASIN'] ?? '');
        $titulo = $this->extraerTituloItemAmazon($item);
        $imagen = $item['images']['primary']['large']['url']
            ?? $item['images']['primary']['medium']['url']
            ?? $item['images']['primary']['small']['url']
            ?? $item['Images']['Primary']['Large']['URL']
            ?? $item['Images']['Primary']['Medium']['URL']
            ?? $item['Images']['Primary']['Small']['URL']
            ?? null;

        return [
            'asin'   => $asin,
            'titulo' => $titulo,
            'imagen' => $imagen,
            'url'    => $asin !== '' ? 'https://www.amazon.es/dp/' . $asin : '',
        ];
    }

    /**
     * Comprueba si la URL (limpia) ya está en ofertas o en urls descartadas.
     *
     * @param array<int, array<string, mixed>> $items
     * @return array<int, array<string, mixed>>
     */
    private function enriquecerItemsConEstadoUrl(array $items): array
    {
        if ($items === []) {
            return [];
        }

        $lookupsUnicos = [];
        $urlsLimpiasUnicas = [];
        $metaPorIndice = [];

        foreach ($items as $index => $item) {
            $urlLimpia = $this->limpiarUrlDeTiendas->limpiar((string) ($item['url'] ?? ''));
            $lookup = $urlLimpia !== '' ? $this->consultarNeoCifrado->hashLookup($urlLimpia) : '';

            $metaPorIndice[$index] = ['url_limpia' => $urlLimpia, 'lookup' => $lookup];

            if ($lookup !== '') {
                $lookupsUnicos[$lookup] = true;
            }
            if ($urlLimpia !== '') {
                $urlsLimpiasUnicas[$urlLimpia] = true;
            }
        }

        $ofertasPorLookup = [];
        $neoPorLookup = [];
        if ($lookupsUnicos !== []) {
            $lookups = array_keys($lookupsUnicos);

            foreach (OfertaProducto::query()
                ->whereIn('url_lookup', $lookups)
                ->get(['id', 'url_lookup']) as $oferta) {
                $ofertasPorLookup[(string) $oferta->url_lookup] = (int) $oferta->id;
            }

            foreach (Neo::query()
                ->whereIn('url_lookup', $lookups)
                ->get(['url_lookup', 'aniadida']) as $filaNeo) {
                $lookupNeo = (string) $filaNeo->url_lookup;
                if (!isset($neoPorLookup[$lookupNeo])) {
                    $neoPorLookup[$lookupNeo] = ['existe' => true, 'aniadida_si' => false];
                }
                if ($filaNeo->aniadida === 'si') {
                    $neoPorLookup[$lookupNeo]['aniadida_si'] = true;
                }
            }
        }

        $urlsEnDescartadas = [];
        if ($urlsLimpiasUnicas !== []) {
            $urlsEnDescartadas = array_fill_keys(
                UrlDescartada::query()
                    ->whereIn('url', array_keys($urlsLimpiasUnicas))
                    ->pluck('url')
                    ->all(),
                true
            );
        }

        foreach ($items as $index => $item) {
            $urlLimpia = (string) ($metaPorIndice[$index]['url_limpia'] ?? '');
            $lookup = (string) ($metaPorIndice[$index]['lookup'] ?? '');

            $ofertaId = ($lookup !== '' && isset($ofertasPorLookup[$lookup]))
                ? $ofertasPorLookup[$lookup]
                : null;
            $neoExiste = $lookup !== '' && isset($neoPorLookup[$lookup]);
            $neoAniadidaSi = $neoExiste && ($neoPorLookup[$lookup]['aniadida_si'] ?? false);

            if ($ofertaId !== null) {
                $items[$index]['estado'] = 'anadida';
                $items[$index]['estado_label'] = 'Añadida';
            } elseif ($urlLimpia !== '' && isset($urlsEnDescartadas[$urlLimpia])) {
                $items[$index]['estado'] = 'descartada';
                $items[$index]['estado_label'] = 'Descartada';
            } else {
                $items[$index]['estado'] = 'no_encontrada';
                $items[$index]['estado_label'] = 'No encontrada';
            }

            $items[$index]['url_limpia'] = $urlLimpia;
            $items[$index]['oferta_id'] = $ofertaId;

            if ($neoExiste) {
                $items[$index]['neo'] = 'si';
                $items[$index]['neo_aniadida'] = $neoAniadidaSi ? 'si' : 'no';
                $items[$index]['neo_label'] = 'Neo: si Añadida: ' . ($neoAniadidaSi ? 'Si' : 'No');
            } else {
                $items[$index]['neo'] = 'no';
                $items[$index]['neo_aniadida'] = null;
                $items[$index]['neo_label'] = 'Neo: no';
            }

            $mostrarEditar = $ofertaId !== null && $neoExiste && $neoAniadidaSi;
            $items[$index]['accion'] = $mostrarEditar ? 'editar' : 'anadir';
            $items[$index]['accion_url'] = $mostrarEditar
                ? route('admin.ofertas.edit', $ofertaId)
                : route('admin.ofertas.create.formularioGeneral', ['url' => $urlLimpia !== '' ? $urlLimpia : ($item['url'] ?? '')]);
        }

        return $items;
    }
}
