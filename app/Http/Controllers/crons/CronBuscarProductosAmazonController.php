<?php

declare(strict_types=1);

namespace App\Http\Controllers\Crons;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Scraping\PeticionApiHTMLController;
use App\Models\Categoria;
use App\Models\CsvOferta;
use App\Models\EjecucionGlobal;
use App\Models\Neo;
use App\Models\OfertaProducto;
use App\Models\Producto;
use App\Models\Tienda;
use App\Models\UrlDescartada;
use App\Services\ConsultarNeoCifrado;
use App\Services\CsvAwinOfertaService;
use App\Services\LimpiarUrlDeTiendas;
use App\Services\TiendaScrapingConfigResolver;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
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

    /** Mínimo de tokens de nombre + modelo que deben coincidir en el candidato. */
    private const UMBRAL_COINCIDENCIA_NOMBRE_MODELO = 0.8;

    /** Mínimo de palabras_exigidas que deben coincidir en el candidato. */
    private const UMBRAL_COINCIDENCIA_PALABRAS_EXIGIDAS = 0.6;

    /** Rutas de Carrefour / El Corte Inglés que no deben entrar en Neo desde csv_ofertas. */
    private const RUTAS_CSV_EXCLUIDAS_CARREFOUR_ECI = ['/musica/', '/libros/', '/cine/'];

    /** @var array<int, string> */
    private const HOSTS_CSV_RUTAS_EXCLUIDAS = ['carrefour.es', 'elcorteingles.es'];

    /** Máximo de filas nuevas insertadas en Neo por producto y ejecución. */
    private const LIMITE_INSERCIONES_NEO_POR_PRODUCTO = 100;

    private static ?int $ejecucionEnCursoId = null;

    /** @var array<string, int>|null */
    private static ?array $contadoresEnCurso = null;

    /** @var array<int, array<string, mixed>>|null */
    private static ?array $resultadosEnCurso = null;

    private static bool $ejecucionFinalizada = false;

    /** Fase actual al procesar un producto (panel/categoría); útil si hay error fatal PHP. */
    private ?string $faseBusquedaUrlsActual = null;

    /** @var array<string, mixed> */
    private array $contextoDiagnosticoBusquedaUrls = [];

    public function __construct(
        private readonly PeticionApiHTMLController $peticionApiHtml,
        private readonly LimpiarUrlDeTiendas $limpiarUrlDeTiendas,
        private readonly ConsultarNeoCifrado $consultarNeoCifrado,
        private readonly CsvAwinOfertaService $csvAwinOfertaService,
        private readonly TiendaScrapingConfigResolver $tiendaScrapingConfigResolver,
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
            'csv_filas_coincidentes'       => 0,
            'csv_filas_coincidentes_codigo' => 0,
            'csv_ofertas_consultadas_codigos' => 0,
            'csv_codigos_nuevos_en_producto' => 0,
            'csv_omitida_oferta'           => 0,
            'csv_ya_en_neo'                => 0,
            'csv_insertadas_neo'           => 0,
            'csv_insertadas_neo_codigo'    => 0,
            'csv_aniadida_neo_si'          => 0,
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
            $todasLasTiendas = Tienda::query()
                ->select('id', 'nombre', 'url')
                ->orderBy('nombre')
                ->get();

            foreach ($productos as $producto) {
                $indiceProducto++;
                $ultimoProductoIdActual = (int) $producto->id;

                $resultadoProducto = $this->procesarProductoBusquedaUrlsIndividual(
                    $producto,
                    $todasLasTiendas,
                    $contadores
                );

                if ($resultadoProducto === null) {
                    $this->persistirProgresoEjecucion($ejecucion, $contadores, $resultados, $ultimoProductoIdActual);
                    $this->pulsoConexionHttp();
                    if ($indiceProducto < $totalProductos) {
                        sleep(self::ESPERA_ENTRE_PRODUCTOS_SEGUNDOS);
                    }
                    continue;
                }

                $resultados[] = $resultadoProducto;

                $this->actualizarEjecucionPaso($ejecucion, 'producto', [
                    'detalle' => 'Producto #' . $producto->id . ' procesado (códigos CSV + Amazon + AliExpress + CSV)',
                    'producto_id' => $producto->id,
                    'paginas_amazon' => $resultadoProducto['paginas_amazon'],
                    'urls_amazon' => $resultadoProducto['urls_amazon'],
                    'urls_aliexpress' => $resultadoProducto['urls_aliexpress'],
                    'csv_coincidentes' => $resultadoProducto['csv_coincidentes'],
                    'csv_insertadas' => $resultadoProducto['csv_insertadas'],
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
     * Panel admin (p. ej. formulario de categoría): mismo cron, JSON por pasos.
     * accion: iniciar | procesar | detener | estado
     */
    public function ejecutarCronPanel(Request $request): JsonResponse
    {
        $request->validate([
            'accion'       => 'required|in:iniciar,procesar,detener,estado',
            'categoria_id' => 'nullable|integer|exists:categorias,id',
            'ejecucion_id' => 'nullable|integer|min:1',
            'producto_id'  => 'nullable|integer|min:1',
            'indice'       => 'nullable|integer|min:1',
        ]);

        return match ((string) $request->input('accion')) {
            'iniciar'  => $this->panelCronIniciarPorCategoria($request),
            'procesar' => $this->panelCronProcesarProducto($request),
            'detener'  => $this->panelCronDetener($request),
            'estado'   => $this->panelCronEstado($request),
            default    => response()->json(['ok' => false, 'error' => 'Acción no válida.'], 422),
        };
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
        $columnas = ['id', 'nombre', 'modelo', 'categoria_id', 'palabras_exigidas'];
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
        $this->finalizarEjecucionBusqueda(
            $ejecucion,
            $contadores,
            $resultados,
            $ultimoProductoId,
            $estado,
            $detalleExtra
        );

        self::$ejecucionFinalizada = true;
        self::$ejecucionEnCursoId = null;
        $this->eliminarEjecucionesAntiguas();
    }

    /**
     * @param array<string, int> $contadores
     * @param array<int, array<string, mixed>> $resultados
     * @param array<string, mixed> $logExtras
     */
    private function finalizarEjecucionBusqueda(
        EjecucionGlobal $ejecucion,
        array $contadores,
        array $resultados,
        int $ultimoProductoId,
        string $estado,
        ?string $detalleExtra = null,
        array $logExtras = []
    ): void {
        $ejecucion->refresh();
        $logBase = is_array($ejecucion->log) ? $ejecucion->log : [];
        $logBase['estado'] = $estado;
        $logBase['paso_actual'] = 'finalizado';
        $logBase['contadores'] = $contadores;
        $logBase['resultados'] = $this->compactarResultadosParaPersistencia($resultados);
        $logBase['ultimo_producto_id'] = $ultimoProductoId;
        if ($detalleExtra !== null) {
            $logBase['detalle_final'] = $detalleExtra;
        }
        $logBase = array_merge($logBase, $logExtras);

        $ejecucion->update([
            'fin'            => now(),
            'total'          => (int) ($contadores['productos_procesados'] ?? 0),
            'total_guardado' => (int) ($contadores['urls_insertadas_neo'] ?? 0),
            'total_errores'  => self::contarErroresTiendasCron($contadores),
            'log'            => $logBase,
        ]);
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
     * @param array<int, array<string, mixed>> $itemsRaw
     * @return array<int, array<string, mixed>>
     */
    private function obtenerItemsAmazonFiltradosPorProducto(
        array $itemsRaw,
        Producto $producto,
        string $palabrasExigidas
    ): array {
        $itemsFiltrados = $this->filtrarItemsAmazonPorUmbralProducto($itemsRaw, $producto, $palabrasExigidas);
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
        int $ultimoProductoId,
        array $logExtras = []
    ): void {
        try {
            $ejecucion->refresh();
            $log = is_array($ejecucion->log) ? $ejecucion->log : [];
            $log['estado'] = 'running';
            $log['paso_actual'] = 'producto';
            $log['contadores'] = $contadores;
            $log['resultados'] = $this->compactarResultadosParaPersistencia($resultados);
            $log['ultimo_producto_id'] = $ultimoProductoId;
            $log = array_merge($log, $logExtras);

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
        $items = $this->obtenerItemsAmazonFiltradosPorProducto($itemsRaw, $producto, $palabrasExigidas);
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
        $items = $this->obtenerItemsAliexpressFiltradosPorProducto($itemsRaw, $producto, $palabrasExigidas);
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
     * @param array<string, int> $contadores
     */
    private function limiteInsercionesNeoProductoAlcanzado(array $contadores): bool
    {
        return (int) ($contadores['neo_insertadas_en_producto'] ?? 0) >= self::LIMITE_INSERCIONES_NEO_POR_PRODUCTO;
    }

    /**
     * En el log de ejecución solo guardamos resúmenes; el detalle_urls va en la respuesta del producto actual.
     *
     * @param  array<int, array<string, mixed>>  $resultados
     * @return array<int, array<string, mixed>>
     */
    private function compactarResultadosParaPersistencia(array $resultados): array
    {
        return array_map(fn (array $resultado): array => $this->compactarResultadoProductoParaPersistencia($resultado), $resultados);
    }

    /**
     * @param array<string, mixed> $resultado
     * @return array<string, mixed>
     */
    private function compactarResultadoProductoParaPersistencia(array $resultado): array
    {
        unset($resultado['detalle_urls'], $resultado['diagnostico']);

        return $resultado;
    }

    private function marcarFaseBusquedaUrls(string $fase, array $contextoExtra = []): void
    {
        $this->faseBusquedaUrlsActual = $fase;
        if ($contextoExtra !== []) {
            $this->contextoDiagnosticoBusquedaUrls = array_merge($this->contextoDiagnosticoBusquedaUrls, $contextoExtra);
        }
    }

    /**
     * Tokens válidos para el prefiltro SQL de csv_ofertas (evita LIKE '%3%' u otros patrones masivos).
     *
     * @param  array<int, string>  $tokens
     * @return array<int, string>
     */
    private function filtrarTokensParaPrefiltroSqlCsv(array $tokens): array
    {
        return array_values(array_filter($tokens, static function (string $token): bool {
            $token = trim($token);
            if ($token === '') {
                return false;
            }
            if (preg_match('/^\d+$/u', $token) === 1) {
                return false;
            }

            return mb_strlen($token) >= 3;
        }));
    }

    /**
     * @param array<string, int> $contadores
     */
    private function registrarInsercionNeoEnProducto(array &$contadores): void
    {
        $contadores['neo_insertadas_en_producto'] = (int) ($contadores['neo_insertadas_en_producto'] ?? 0) + 1;
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
            if ($this->limiteInsercionesNeoProductoAlcanzado($contadores)) {
                break;
            }

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

            $this->registrarInsercionNeoEnProducto($contadores);
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
                $log['resultados'] = $this->compactarResultadosParaPersistencia($resultados);
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
     * @param array<int, array<string, mixed>> $itemsRaw
     * @return array<int, array<string, mixed>>
     */
    private function obtenerItemsAliexpressFiltradosPorProducto(
        array $itemsRaw,
        Producto $producto,
        string $palabrasExigidas
    ): array {
        $items = array_map(fn (array $item) => $this->mapearItemAliexpressBusqueda($item), $itemsRaw);
        $items = array_values(array_filter($items, fn (array $item) => ($item['url'] ?? '') !== ''));

        return $this->filtrarItemsMapeadosPorUmbralProducto($items, $producto, $palabrasExigidas);
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
     * @param array<int, array<string, mixed>> $items
     * @return array<int, array<string, mixed>>
     */
    private function filtrarItemsAmazonPorUmbralProducto(
        array $items,
        Producto $producto,
        string $palabrasExigidas
    ): array {
        return array_values(array_filter($items, function (array $item) use ($producto, $palabrasExigidas) {
            $titulo = $this->extraerTituloItemAmazon($item);

            return $titulo !== '' && $this->cumpleCriteriosCoincidenciaProducto([$titulo], $producto, $palabrasExigidas);
        }));
    }

    /**
     * @param array<int, array<string, mixed>> $items
     * @return array<int, array<string, mixed>>
     */
    private function filtrarItemsMapeadosPorUmbralProducto(
        array $items,
        Producto $producto,
        string $palabrasExigidas
    ): array {
        return array_values(array_filter($items, function (array $item) use ($producto, $palabrasExigidas) {
            $titulo = trim((string) ($item['titulo'] ?? ''));

            return $titulo !== '' && $this->cumpleCriteriosCoincidenciaProducto([$titulo], $producto, $palabrasExigidas);
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
     * Tokens únicos de nombre + modelo del producto (identidad del producto).
     *
     * @return array<int, string>
     */
    private function construirTokensNombreModeloProducto(Producto $producto): array
    {
        return $this->deduplicarTokensCoincidencia(array_merge(
            $this->extraerPalabrasClaveProductoParaCsv(trim((string) $producto->nombre)),
            $this->parsearModelosProductoParaCsv(trim((string) ($producto->modelo ?? '')))
        ));
    }

    /**
     * @param  array<int, string>  $tokens
     * @return array<int, string>
     */
    private function deduplicarTokensCoincidencia(array $tokens): array
    {
        $vistos = [];
        $resultado = [];

        foreach ($tokens as $token) {
            $token = trim($token);
            $norm = $this->normalizarTextoCoincidenciaAmazon($token);
            if ($norm === '' || isset($vistos[$norm])) {
                continue;
            }

            $vistos[$norm] = true;
            $resultado[] = $token;
        }

        return $resultado;
    }

    /**
     * Comprueba los dos criterios independientes: palabras_exigidas (≥60%, o 100% si palabra+número)
     * y nombre+modelo (≥80%).
     *
     * @param  array<int, string>  $textos
     */
    private function cumpleCriteriosCoincidenciaProducto(
        array $textos,
        Producto $producto,
        string $palabrasExigidas
    ): bool {
        $tokensExigidas = $this->deduplicarTokensCoincidencia(
            $this->parsearPalabrasCoincidentesAmazon($palabrasExigidas)
        );
        if ($tokensExigidas === []) {
            return false;
        }

        $umbralExigidas = $this->umbralCoincidenciaPalabrasExigidas($tokensExigidas);
        $modoPalabraYNumero = $umbralExigidas >= 1.0;

        if (!$this->alcanzaUmbralCoincidenciaTokens(
            $tokensExigidas,
            $textos,
            $umbralExigidas,
            $modoPalabraYNumero
        )) {
            return false;
        }

        $tokensNombreModelo = $this->construirTokensNombreModeloProducto($producto);
        if ($tokensNombreModelo === []) {
            return true;
        }

        return $this->alcanzaUmbralCoincidenciaTokens(
            $tokensNombreModelo,
            $textos,
            self::UMBRAL_COINCIDENCIA_NOMBRE_MODELO
        );
    }

    /**
     * Si palabras_exigidas tienen al menos una palabra y exactamente un número (p. ej. "Sensitive 3", "geforce 9060"), exige 100%.
     *
     * @param  array<int, string>  $tokensExigidas
     */
    private function umbralCoincidenciaPalabrasExigidas(array $tokensExigidas): float
    {
        if ($this->esPatronPalabrasExigidasPalabraYNumero($tokensExigidas)) {
            return 1.0;
        }

        return self::UMBRAL_COINCIDENCIA_PALABRAS_EXIGIDAS;
    }

    /**
     * Palabra(s) + un solo número: "Sensitive 3", "geforce 9060", "geforce rtx 9060".
     *
     * @param  array<int, string>  $tokens
     */
    private function esPatronPalabrasExigidasPalabraYNumero(array $tokens): bool
    {
        if (count($tokens) < 2) {
            return false;
        }

        $tokensNumericos = 0;
        $tokensPalabra = 0;

        foreach ($tokens as $token) {
            $token = trim($token);
            if ($token === '') {
                continue;
            }
            if (preg_match('/^\d+$/u', $token) === 1) {
                $tokensNumericos++;

                continue;
            }
            if (preg_match('/\p{L}/u', $token) === 1) {
                $tokensPalabra++;
            }
        }

        return $tokensPalabra >= 1 && $tokensNumericos === 1;
    }

    /**
     * @param  array<int, string>  $tokensReferencia
     * @param  array<int, string>  $textos
     */
    private function alcanzaUmbralCoincidenciaTokens(
        array $tokensReferencia,
        array $textos,
        float $umbral,
        bool $modoPalabraYNumero = false
    ): bool {
        if ($tokensReferencia === []) {
            return false;
        }

        $textos = array_values(array_filter(array_map('trim', $textos)));
        if ($textos === []) {
            return false;
        }

        $coincidencias = 0;
        foreach ($tokensReferencia as $token) {
            foreach ($textos as $texto) {
                $coincide = $modoPalabraYNumero
                    ? $this->tokenCoincideModoPalabraYNumero($texto, $token)
                    : $this->palabraCoincideEnTituloAmazon($texto, $token);
                if ($coincide) {
                    $coincidencias++;
                    break;
                }
            }
        }

        return ($coincidencias / count($tokensReferencia)) >= $umbral;
    }

    /**
     * Modo palabra(s)+número (100%): palabras en cualquier parte; número sin dígitos contiguos extra.
     * Número: sí en talla3, texto9060texto, asdf9060sdfasd; no en 90604656, 79060 (si buscas 9060), 13…
     */
    private function tokenCoincideModoPalabraYNumero(string $texto, string $token): bool
    {
        $token = trim($token);
        if ($token === '') {
            return true;
        }

        if (preg_match('/^\d+$/u', $token) === 1) {
            return $this->tokenNumericoCoincideEnTexto($texto, $token);
        }

        return $this->palabraCoincideEnTituloAmazon($texto, $token);
    }

    /**
     * El número debe aparecer tal cual, sin otro dígito pegado antes o después.
     * 9060 cuenta en "geforce9060", "asdf9060sdfasd"; no en "90604656" ni "19060" (subcadena con dígitos extra).
     */
    private function tokenNumericoCoincideEnTexto(string $texto, string $token): bool
    {
        $token = trim($token);
        if ($token === '' || preg_match('/^\d+$/u', $token) !== 1) {
            return false;
        }

        $pattern = '/(?<!\d)' . preg_quote($token, '/') . '(?!\d)/u';

        return preg_match($pattern, $texto) === 1;
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

    /**
     * Repasa filas de csv_ofertas que cumplen palabras_exigidas (≥60%, o 100% si palabra(s)+número)
     * y nombre+modelo (≥80%) con aniadida_neo=no.
     *
     * @param  \Illuminate\Support\Collection<int, Tienda>  $todasLasTiendas
     * @param  array<string, int>  $contadores
     * @return array{coincidentes: int, insertadas: int, omitidas: int, detalle_urls: array<int, array<string, mixed>>}
     */
    private function procesarCsvOfertasCoincidentes(
        Producto $producto,
        string $palabrasExigidas,
        $todasLasTiendas,
        array &$contadores
    ): array {
        $insertadas = 0;
        $omitidas = 0;
        $detalleUrls = [];
        $coincidentes = 0;
        $filasEscaneadasSql = 0;

        foreach ($this->iterarFilasCsvOfertasCoincidentes($producto, $palabrasExigidas) as $fila) {
            $filasEscaneadasSql++;

            if ($this->limiteInsercionesNeoProductoAlcanzado($contadores)) {
                break;
            }

            $coincidentes++;
            $contadores['csv_filas_coincidentes']++;
            $resultado = $this->procesarFilaCsvOfertaEnNeo($fila, $producto, $todasLasTiendas, $contadores);

            if (($resultado['accion'] ?? '') === 'insertada') {
                $insertadas++;
                $detalleUrls[] = $resultado;
            } elseif (($resultado['accion'] ?? '') === 'omitida') {
                $omitidas++;
            }
        }

        $this->marcarFaseBusquedaUrls('csv_palabras', [
            'csv_filas_escaneadas_sql' => $filasEscaneadasSql,
            'csv_coincidentes'         => $coincidentes,
        ]);

        return [
            'coincidentes' => $coincidentes,
            'insertadas'   => $insertadas,
            'omitidas'     => $omitidas,
            'detalle_urls' => $detalleUrls,
        ];
    }

    /**
     * @return \Generator<int, CsvOferta>
     */
    private function iterarFilasCsvOfertasCoincidentes(Producto $producto, string $palabrasExigidas): \Generator
    {
        $tokensExigidas = $this->deduplicarTokensCoincidencia(
            $this->parsearPalabrasCoincidentesAmazon($palabrasExigidas)
        );
        $tokensNombreModelo = $this->construirTokensNombreModeloProducto($producto);
        $tokensReferencia = $this->deduplicarTokensCoincidencia(array_merge($tokensExigidas, $tokensNombreModelo));
        $tokensSql = $this->filtrarTokensParaPrefiltroSqlCsv($tokensReferencia);

        if ($tokensSql === []) {
            $tokensSql = $this->filtrarTokensParaPrefiltroSqlCsv($tokensExigidas);
        }

        $this->marcarFaseBusquedaUrls('csv_palabras_prefiltro', [
            'csv_tokens_sql'        => $tokensSql,
            'csv_tokens_referencia' => $tokensReferencia,
        ]);

        if ($tokensSql === []) {
            return;
        }

        $query = CsvOferta::query()
            ->where('aniadida_neo', 'no')
            ->whereNotNull('nombre')
            ->where('nombre', '!=', '');

        $query->where(function ($sub) use ($tokensSql) {
            foreach ($tokensSql as $token) {
                $termino = mb_strtolower($token, 'UTF-8');
                $sub->orWhereRaw('LOWER(nombre) LIKE ?', ['%' . $termino . '%'])
                    ->orWhereRaw('LOWER(url) LIKE ?', ['%' . $termino . '%']);
            }
        });

        foreach ($query->cursor() as $fila) {
            if (!($fila instanceof CsvOferta)) {
                continue;
            }

            if ($this->filaCsvCoincideConProducto($fila, $producto, $palabrasExigidas)) {
                yield $fila;
            }
        }
    }

    /**
     * @return \Illuminate\Support\Collection<int, CsvOferta>
     */
    private function obtenerFilasCsvOfertasCoincidentes(Producto $producto, string $palabrasExigidas): \Illuminate\Support\Collection
    {
        return collect(iterator_to_array($this->iterarFilasCsvOfertasCoincidentes($producto, $palabrasExigidas), false));
    }

    private function filaCsvCoincideConProducto(
        CsvOferta $fila,
        Producto $producto,
        string $palabrasExigidas
    ): bool {
        if ($this->urlCsvExcluidaPorRutaCategoriaTienda((string) $fila->url)) {
            return false;
        }

        $textos = array_values(array_filter([
            trim((string) $fila->nombre),
            $this->extraerTextoSlugUrlParaCoincidenciaCsv((string) $fila->url),
        ]));

        return $this->cumpleCriteriosCoincidenciaProducto($textos, $producto, $palabrasExigidas);
    }

    private function urlCsvExcluidaPorRutaCategoriaTienda(string $url): bool
    {
        $url = trim($url);
        if ($url === '') {
            return false;
        }

        if (!preg_match('#^https?://#i', $url)) {
            $url = 'https://' . $url;
        }

        $host = mb_strtolower((string) (parse_url($url, PHP_URL_HOST) ?? ''), 'UTF-8');
        $path = mb_strtolower((string) (parse_url($url, PHP_URL_PATH) ?? ''), 'UTF-8');

        if ($host === '' || !$this->esHostCarrefourOElCorteIngles($host)) {
            return false;
        }

        foreach (self::RUTAS_CSV_EXCLUIDAS_CARREFOUR_ECI as $segmento) {
            if (str_contains($path, $segmento)) {
                return true;
            }
        }

        return false;
    }

    private function esHostCarrefourOElCorteIngles(string $host): bool
    {
        foreach (self::HOSTS_CSV_RUTAS_EXCLUIDAS as $dominio) {
            if ($host === $dominio || str_ends_with($host, '.' . $dominio)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<int, string>
     */
    private function parsearModelosProductoParaCsv(string $modelo): array
    {
        $modelo = trim($modelo);
        if ($modelo === '') {
            return [];
        }

        $partes = preg_split('/[\s,;+\/]+/u', $modelo, -1, PREG_SPLIT_NO_EMPTY);
        if (!is_array($partes)) {
            return [];
        }

        $modelos = [];
        foreach ($partes as $parte) {
            $parte = trim($parte);
            $norm = $this->normalizarTextoCoincidenciaAmazon($parte);
            if ($norm === '' || strlen($norm) < 3) {
                continue;
            }
            $modelos[] = $parte;
        }

        return array_values(array_unique($modelos));
    }

    /**
     * @return array<int, string>
     */
    private function extraerPalabrasClaveProductoParaCsv(string $nombreProducto): array
    {
        $nombreProducto = trim($nombreProducto);
        if ($nombreProducto === '') {
            return [];
        }

        $partes = preg_split('/[\s\/\-\_\.,;:+()\[\]]+/u', $nombreProducto, -1, PREG_SPLIT_NO_EMPTY);
        if (!is_array($partes)) {
            return [];
        }

        $stopwords = [
            'gb', 'mb', 'tb', 'un', 'una', 'uno', 'para', 'con', 'the', 'and',
            'de', 'la', 'el', 'en', 'del', 'los', 'las', 'new', 'pack',
        ];
        $claves = [];

        foreach ($partes as $parte) {
            $norm = $this->normalizarTextoCoincidenciaAmazon($parte);
            if ($norm === '' || strlen($norm) < 3) {
                continue;
            }
            if (in_array($norm, $stopwords, true)) {
                continue;
            }
            $claves[] = $parte;
        }

        return array_values(array_unique($claves));
    }

    private function extraerTextoSlugUrlParaCoincidenciaCsv(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }

        if (!preg_match('#^https?://#i', $url)) {
            $url = 'https://' . $url;
        }

        $path = (string) (parse_url($url, PHP_URL_PATH) ?? '');
        $path = rawurldecode($path);
        $path = str_replace(['-', '_', '/'], ' ', $path);

        return trim(preg_replace('/\s+/u', ' ', $path) ?? $path);
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Tienda>  $todasLasTiendas
     * @param  array<string, int>  $contadores
     * @return array<string, mixed>
     */
    private function procesarFilaCsvOfertaEnNeo(
        CsvOferta $fila,
        Producto $producto,
        $todasLasTiendas,
        array &$contadores
    ): array {
        $urlLimpia = $this->limpiarUrlDeTiendas->limpiar(trim((string) $fila->url));
        $base = [
            'csv_oferta_id' => $fila->id,
            'url'           => $urlLimpia,
            'origen'        => 'csv_ofertas',
            'nombre_csv'    => $fila->nombre,
        ];

        if ($urlLimpia === '') {
            return array_merge($base, ['accion' => 'omitida', 'motivo' => 'url_vacia']);
        }

        if ($this->urlCsvExcluidaPorRutaCategoriaTienda($urlLimpia)) {
            return array_merge($base, ['accion' => 'omitida', 'motivo' => 'ruta_excluida']);
        }

        if (UrlDescartada::query()->where('url', $urlLimpia)->exists()) {
            $contadores['urls_omitida_descartada']++;

            return array_merge($base, ['accion' => 'omitida', 'motivo' => 'descartada']);
        }

        $lookup = $this->consultarNeoCifrado->hashLookup($urlLimpia);

        if ($lookup !== '' && OfertaProducto::query()->where('url_lookup', $lookup)->exists()) {
            $fila->update(['aniadida_neo' => 'si']);
            $contadores['csv_omitida_oferta']++;
            $contadores['csv_aniadida_neo_si']++;

            return array_merge($base, [
                'accion'       => 'omitida',
                'motivo'       => 'oferta',
                'aniadida_neo' => 'si',
            ]);
        }

        if ($lookup !== '' && Neo::query()->where('url_lookup', $lookup)->exists()) {
            $fila->update(['aniadida_neo' => 'si']);
            $contadores['csv_ya_en_neo']++;
            $contadores['csv_aniadida_neo_si']++;

            return array_merge($base, [
                'accion'       => 'omitida',
                'motivo'       => 'neo',
                'aniadida_neo' => 'si',
            ]);
        }

        if ($this->limiteInsercionesNeoProductoAlcanzado($contadores)) {
            $contadores['urls_omitida_limite_neo'] = (int) ($contadores['urls_omitida_limite_neo'] ?? 0) + 1;

            return array_merge($base, [
                'accion' => 'omitida',
                'motivo' => 'limite_neo_producto',
            ]);
        }

        $tienda = $this->detectarTiendaPorUrl($urlLimpia, $todasLasTiendas);
        $tiendaId = $tienda?->id ?? $fila->tienda_id;

        Neo::create([
            'producto_id'  => $producto->id,
            'categoria_id' => $producto->categoria_id,
            'tienda_id'    => $tiendaId,
            'url'          => $urlLimpia,
            'aniadida'     => 'no',
        ]);

        $fila->update(['aniadida_neo' => 'si']);

        $this->registrarInsercionNeoEnProducto($contadores);
        $contadores['urls_insertadas_neo']++;
        $contadores['csv_insertadas_neo']++;
        $contadores['csv_aniadida_neo_si']++;

        return array_merge($base, [
            'accion'       => 'insertada',
            'tienda_id'    => $tiendaId,
            'aniadida_neo' => 'si',
        ]);
    }

    /**
     * Detecta la tienda a partir del host de la URL (misma lógica que formulario de ofertas / analizarUrls).
     *
     * @param  \Illuminate\Support\Collection<int, Tienda>|\App\Models\Tienda[]  $todasLasTiendas
     */
    private function detectarTiendaPorUrl(string $url, $todasLasTiendas): ?Tienda
    {
        try {
            $urlParaParsear = trim($url);
            if ($urlParaParsear === '') {
                return null;
            }
            if (!preg_match('#^https?://#i', $urlParaParsear)) {
                $urlParaParsear = 'https://' . $urlParaParsear;
            }
            $parsed = parse_url($urlParaParsear);
            $hostUser = strtolower($parsed['host'] ?? '');
            $hostUser = preg_replace('/^www\./', '', $hostUser);
            if ($hostUser === '') {
                return null;
            }

            foreach ($todasLasTiendas as $t) {
                $tu = trim($t->url ?? '');
                if ($tu === '') {
                    continue;
                }
                $tu = preg_replace('#^https?://#i', '', $tu);
                $tu = preg_replace('/^www\./i', '', strtolower($tu));
                $tu = preg_replace('#/.*$#', '', $tu);
                $tu = rtrim($tu, '/');
                if ($tu === '' || !str_contains($tu, '.')) {
                    continue;
                }
                if ($hostUser === $tu || str_ends_with($hostUser, '.' . $tu) || str_ends_with($tu, '.' . $hostUser)) {
                    return $t;
                }
            }

            $mejor = null;
            $mejorLongitud = 0;
            foreach ($todasLasTiendas as $t) {
                foreach ($this->clavesHostTiendaDetectar($t) as $clave) {
                    if (strlen($clave) < 4) {
                        continue;
                    }
                    if (str_contains($hostUser, $clave) && strlen($clave) > $mejorLongitud) {
                        $mejor = $t;
                        $mejorLongitud = strlen($clave);
                    }
                }
            }

            return $mejor instanceof Tienda ? $mejor : null;
        } catch (\Throwable $e) {
            //
        }

        return null;
    }

    /**
     * Antes del resto del cron: copia EAN/ISBN/UPC/MPN/GTIN desde csv_ofertas (por URL de oferta)
     * en tiendas con enlace CSV, mostrar=si y scrapear=si.
     *
     * @param  array<string, int>  $contadores
     * @return array{actualizado: bool, ofertas_consultadas: int, tiendas_csv: int}
     */
    private function sincronizarCodigosProductoDesdeOfertasCsv(Producto $producto, array &$contadores): array
    {
        $tiendaIds = $this->obtenerIdsTiendasCsvElegiblesDelProducto($producto);
        if ($tiendaIds === []) {
            return ['actualizado' => false, 'ofertas_consultadas' => 0, 'tiendas_csv' => 0];
        }

        $ofertas = OfertaProducto::query()
            ->where('producto_id', $producto->id)
            ->whereIn('tienda_id', $tiendaIds)
            ->get();

        $estructura = $this->csvAwinOfertaService->normalizarEstructuraCodigosProducto($producto->ean_isbn_etc);
        $ofertasConsultadas = 0;
        $huboCambios = false;

        foreach ($ofertas as $oferta) {
            $ofertasConsultadas++;
            $contadores['csv_ofertas_consultadas_codigos']++;

            $filaCsv = $this->csvAwinOfertaService->buscarFilaPorOferta($oferta);
            if ($filaCsv === null) {
                continue;
            }

            if ($this->csvAwinOfertaService->fusionarCodigosCsvEnEstructura($estructura, $filaCsv)) {
                $huboCambios = true;
                $contadores['csv_codigos_nuevos_en_producto']++;
            }
        }

        if ($huboCambios) {
            $producto->ean_isbn_etc = $estructura;
            $producto->save();
        }

        return [
            'actualizado' => $huboCambios,
            'ofertas_consultadas' => $ofertasConsultadas,
            'tiendas_csv' => count($tiendaIds),
        ];
    }

    /**
     * @return list<int>
     */
    private function obtenerIdsTiendasCsvElegiblesDelProducto(Producto $producto): array
    {
        $ofertas = OfertaProducto::query()
            ->where('producto_id', $producto->id)
            ->with('tienda')
            ->get();

        $ids = [];

        foreach ($ofertas as $oferta) {
            $tienda = $oferta->tienda;
            if ($tienda === null) {
                continue;
            }
            if (!$this->tiendaElegibleParaSincronizarCodigosCsv($tienda, $producto)) {
                continue;
            }
            $ids[$tienda->id] = true;
        }

        return array_map('intval', array_keys($ids));
    }

    private function tiendaElegibleParaSincronizarCodigosCsv(Tienda $tienda, Producto $producto): bool
    {
        if (!$this->tiendaTieneEnlaceDescargaCsv($tienda)) {
            return false;
        }

        $categoriaId = $producto->categoria_id !== null ? (int) $producto->categoria_id : null;

        return $this->tiendaScrapingConfigResolver->resolverScrapearFinal($tienda, $categoriaId) === 'si'
            && $this->tiendaScrapingConfigResolver->resolverMostrarFinal($tienda, $categoriaId) === 'si';
    }

    private function tiendaTieneEnlaceDescargaCsv(Tienda $tienda): bool
    {
        $urls = $tienda->url_csv;
        if (!is_array($urls)) {
            return false;
        }

        foreach ($urls as $url) {
            if (trim((string) $url) !== '') {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    private function listarCodigosPlanosProducto(Producto $producto): array
    {
        $estructura = $this->csvAwinOfertaService->normalizarEstructuraCodigosProducto($producto->ean_isbn_etc);
        $planos = [];

        foreach (CsvAwinOfertaService::CAMPOS_CODIGOS_IDENTIFICADOR as $campo) {
            foreach ($estructura[$campo] as $valor) {
                $planos[] = $valor;
            }
        }

        return array_values(array_unique($planos));
    }

    /**
     * Busca en csv_ofertas filas cuyos códigos coinciden con los del producto (aniadida_neo=no).
     *
     * @param  \Illuminate\Support\Collection<int, Tienda>  $todasLasTiendas
     * @param  array<string, int>  $contadores
     * @return array{coincidentes: int, insertadas: int, omitidas: int, detalle_urls: array<int, array<string, mixed>>}
     */
    private function procesarCsvOfertasCoincidentesPorCodigos(
        Producto $producto,
        $todasLasTiendas,
        array &$contadores
    ): array {
        $insertadas = 0;
        $omitidas = 0;
        $detalleUrls = [];
        $coincidentes = 0;

        foreach ($this->obtenerFilasCsvOfertasCoincidentesPorCodigos($producto) as $fila) {
            if ($this->limiteInsercionesNeoProductoAlcanzado($contadores)) {
                break;
            }

            $coincidentes++;
            $contadores['csv_filas_coincidentes']++;
            $contadores['csv_filas_coincidentes_codigo']++;

            $resultado = $this->procesarFilaCsvOfertaEnNeo($fila, $producto, $todasLasTiendas, $contadores);
            $resultado['origen_busqueda'] = 'codigo';
            $detalleUrls[] = $resultado;

            if (($resultado['accion'] ?? '') === 'insertada') {
                $insertadas++;
                $contadores['csv_insertadas_neo_codigo']++;
            } elseif (($resultado['accion'] ?? '') === 'omitida') {
                $omitidas++;
            }
        }

        return [
            'coincidentes' => $coincidentes,
            'insertadas'   => $insertadas,
            'omitidas'     => $omitidas,
            'detalle_urls' => $detalleUrls,
        ];
    }

    /**
     * @return \Illuminate\Support\Collection<int, CsvOferta>
     */
    private function obtenerFilasCsvOfertasCoincidentesPorCodigos(Producto $producto): \Illuminate\Support\Collection
    {
        $codigos = $this->listarCodigosPlanosProducto($producto);
        if ($codigos === []) {
            return collect();
        }

        $query = CsvOferta::query()
            ->where('aniadida_neo', 'no')
            ->where(function ($sub) use ($codigos) {
                $sub->whereIn('ean', $codigos)
                    ->orWhereIn('isbn', $codigos)
                    ->orWhereIn('upc', $codigos)
                    ->orWhereIn('mpn', $codigos)
                    ->orWhereIn('gtin', $codigos);
            });

        return $query->get()->unique('id')->values();
    }

    /**
     * Productos elegibles para buscar URLs desde el panel de categoría (incluye subcategorías).
     */
    public function contarProductosElegiblesBusquedaUrlsPorCategoria(int $categoriaId): int
    {
        $categoriaIds = $this->obtenerIdsCategoriasConHijas($categoriaId);

        return (int) $this->queryProductosElegiblesBusquedaPorCategoria($categoriaIds)->count();
    }

    /**
     * Panel: inicia búsqueda de URLs para todos los productos elegibles de una categoría.
     */
    private function panelCronIniciarPorCategoria(Request $request): JsonResponse
    {
        @set_time_limit(120);

        $request->validate(['categoria_id' => 'required|integer|exists:categorias,id']);
        $categoria = Categoria::query()->findOrFail((int) $request->input('categoria_id'));

        $categoriaIds = $this->obtenerIdsCategoriasConHijas((int) $categoria->id);
        $productos = $this->queryProductosElegiblesBusquedaPorCategoria($categoriaIds)
            ->orderBy('id')
            ->get(['id', 'nombre']);

        $productoIds = $productos->pluck('id')->map(fn ($id) => (int) $id)->values()->all();
        $contadores = $this->crearContadoresInicialesBusquedaUrls();
        $contadores['productos_en_query'] = count($productoIds);
        $contadores['productos_elegibles_total'] = count($productoIds);

        $ejecucion = EjecucionGlobal::create([
            'inicio'         => now(),
            'fin'            => null,
            'nombre'         => self::NOMBRE_EJECUCION_GLOBAL,
            'total'          => 0,
            'total_guardado' => 0,
            'total_errores'  => 0,
            'log'            => [
                'estado'           => 'running',
                'paso_actual'      => 'inicio',
                'origen'           => 'panel_categoria',
                'pasos'            => [
                    ['momento' => now()->toDateTimeString(), 'paso' => 'inicio', 'detalle' => 'Ejecución creada desde panel de categoría', 'contexto' => []],
                ],
                'categoria_id'     => (int) $categoria->id,
                'categoria_nombre' => (string) $categoria->nombre,
                'categoria_ids'    => $categoriaIds,
                'producto_ids'     => $productoIds,
                'contadores'       => $contadores,
                'resultados'       => [],
            ],
        ]);

        $this->actualizarEjecucionPaso($ejecucion, 'consulta', [
            'detalle' => 'Productos elegibles en categoría #' . $categoria->id . ' (incluye subcategorías)',
            'categoria_id' => (int) $categoria->id,
            'categoria_nombre' => (string) $categoria->nombre,
            'productos_en_lote' => count($productoIds),
            'productos_elegibles_total' => count($productoIds),
            'categoria_ids' => $categoriaIds,
        ]);

        if ($productoIds === []) {
            $this->actualizarEjecucionPaso($ejecucion, 'finalizado', [
                'detalle' => 'Sin productos elegibles en la categoría',
                'categoria_id' => (int) $categoria->id,
            ]);
            $this->finalizarEjecucionBusqueda(
                $ejecucion->fresh(),
                $contadores,
                [],
                0,
                'ok',
                'sin_elegibles',
                [
                    'origen' => 'panel_categoria',
                    'categoria_id' => (int) $categoria->id,
                    'categoria_nombre' => (string) $categoria->nombre,
                    'categoria_ids' => $categoriaIds,
                    'producto_ids' => [],
                ]
            );
        }

        return response()->json([
            'ok'            => true,
            'accion'        => 'iniciar',
            'ejecucion_id'  => (int) $ejecucion->id,
            'total'         => count($productoIds),
            'producto_ids'  => $productoIds,
            'categoria_id'  => (int) $categoria->id,
            'sin_productos' => $productoIds === [],
            'mensaje'       => count($productoIds) > 0
                ? count($productoIds) . ' productos cargados. Se procesarán uno a uno.'
                : 'Sin productos elegibles.',
        ]);
    }

    /**
     * Panel: procesa un producto del lote (misma lógica que ejecutarCron).
     */
    private function panelCronProcesarProducto(Request $request): JsonResponse
    {
        @set_time_limit(0);
        @ini_set('max_execution_time', '0');

        $this->faseBusquedaUrlsActual = 'inicio';
        $this->contextoDiagnosticoBusquedaUrls = [
            'producto_id'  => (int) $request->input('producto_id', 0),
            'ejecucion_id' => (int) $request->input('ejecucion_id', 0),
            'categoria_id' => (int) $request->input('categoria_id', 0),
            'indice'       => (int) $request->input('indice', 0),
        ];

        $faseRef = &$this->faseBusquedaUrlsActual;
        $contextoRef = &$this->contextoDiagnosticoBusquedaUrls;
        register_shutdown_function(static function () use ($request, &$faseRef, &$contextoRef): void {
            $err = error_get_last();
            if ($err === null || !in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR], true)) {
                return;
            }

            Log::error('panelCronProcesarProducto: error fatal PHP', [
                'producto_id'  => $request->input('producto_id'),
                'ejecucion_id' => $request->input('ejecucion_id'),
                'categoria_id' => $request->input('categoria_id'),
                'indice'       => $request->input('indice'),
                'fase'         => $faseRef ?? 'desconocida',
                'contexto'     => $contextoRef,
                'php_error'    => $err,
            ]);
        });

        try {
            return $this->panelCronProcesarProductoInterno($request);
        } catch (\Throwable $e) {
            Log::error('panelCronProcesarProducto: error procesando producto', [
                'categoria_id' => $request->input('categoria_id'),
                'ejecucion_id' => $request->input('ejecucion_id'),
                'producto_id'  => $request->input('producto_id'),
                'indice'       => $request->input('indice'),
                'fase'         => $this->faseBusquedaUrlsActual,
                'contexto'     => $this->contextoDiagnosticoBusquedaUrls,
                'message'      => $e->getMessage(),
                'exception'    => $e::class,
                'file'         => $e->getFile(),
                'line'         => $e->getLine(),
            ]);

            $payload = [
                'ok'          => false,
                'error'       => $e->getMessage(),
                'exception'   => class_basename($e),
                'accion'      => 'procesar',
                'producto_id' => $request->input('producto_id'),
                'indice'      => $request->input('indice'),
                'fase'        => $this->faseBusquedaUrlsActual,
                'diagnostico' => $this->contextoDiagnosticoBusquedaUrls,
            ];

            if (config('app.debug')) {
                $payload['file'] = $e->getFile();
                $payload['line'] = $e->getLine();
            }

            return response()->json($payload, 500);
        } finally {
            $this->faseBusquedaUrlsActual = null;
            $this->contextoDiagnosticoBusquedaUrls = [];
        }
    }

    private function panelCronProcesarProductoInterno(Request $request): JsonResponse
    {
        $request->validate([
            'categoria_id' => 'required|integer|exists:categorias,id',
            'ejecucion_id' => 'required|integer|min:1',
            'producto_id'  => 'required|integer|min:1',
            'indice'       => 'nullable|integer|min:1',
        ]);

        $this->faseBusquedaUrlsActual = 'validar_ejecucion';

        $categoria = Categoria::query()->findOrFail((int) $request->input('categoria_id'));
        $ejecucion = $this->buscarEjecucionPanelCron((int) $request->input('ejecucion_id'));

        $log = is_array($ejecucion->log) ? $ejecucion->log : [];
        if ((int) ($log['categoria_id'] ?? 0) !== (int) $categoria->id) {
            return response()->json(['ok' => false, 'error' => 'La ejecución no pertenece a esta categoría.'], 403);
        }

        if ($ejecucion->fin !== null || in_array((string) ($log['estado'] ?? ''), ['ok', 'interrumpido', 'error'], true)) {
            return response()->json(['ok' => false, 'error' => 'La ejecución ya está finalizada.'], 409);
        }

        $productoIds = is_array($log['producto_ids'] ?? null) ? $log['producto_ids'] : [];
        $productoId = (int) $request->input('producto_id');
        if (!in_array($productoId, array_map('intval', $productoIds), true)) {
            return response()->json(['ok' => false, 'error' => 'El producto no pertenece a este lote.'], 422);
        }

        $this->faseBusquedaUrlsActual = 'cargar_producto';
        $this->contextoDiagnosticoBusquedaUrls['producto_nombre'] = null;

        $producto = Producto::query()
            ->whereIn('categoria_id', $log['categoria_ids'] ?? [$categoria->id])
            ->findOrFail($productoId);

        $this->contextoDiagnosticoBusquedaUrls['producto_nombre'] = trim((string) $producto->nombre);
        $this->contextoDiagnosticoBusquedaUrls['palabras_exigidas'] = trim((string) ($producto->palabras_exigidas ?? ''));

        $contadores = is_array($log['contadores'] ?? null)
            ? $log['contadores']
            : $this->crearContadoresInicialesBusquedaUrls();
        $resultados = is_array($log['resultados'] ?? null) ? $log['resultados'] : [];

        $todasLasTiendas = Tienda::query()
            ->select('id', 'nombre', 'url')
            ->orderBy('nombre')
            ->get();

        $this->faseBusquedaUrlsActual = 'procesar_producto';

        $resultadoProducto = $this->procesarProductoBusquedaUrlsIndividual(
            $producto,
            $todasLasTiendas,
            $contadores
        );

        if ($resultadoProducto !== null) {
            $resultados[] = $this->compactarResultadoProductoParaPersistencia($resultadoProducto);

            if (($resultadoProducto['error_amazon'] ?? null) !== null) {
                $this->actualizarEjecucionPaso($ejecucion, 'producto_error_amazon', [
                    'detalle' => 'Error Amazon para producto #' . $producto->id,
                    'producto_id' => $producto->id,
                    'error' => $resultadoProducto['error_amazon'],
                ]);
            }

            if (($resultadoProducto['error_aliexpress'] ?? null) !== null) {
                $this->actualizarEjecucionPaso($ejecucion, 'producto_error_aliexpress', [
                    'detalle' => 'Error AliExpress para producto #' . $producto->id,
                    'producto_id' => $producto->id,
                    'error' => $resultadoProducto['error_aliexpress'],
                ]);
            }

            $this->actualizarEjecucionPaso($ejecucion, 'producto', [
                'detalle' => 'Producto #' . $producto->id . ' procesado (códigos CSV + Amazon + AliExpress + CSV)',
                'producto_id' => $producto->id,
                'paginas_amazon' => $resultadoProducto['paginas_amazon'],
                'urls_amazon' => $resultadoProducto['urls_amazon'],
                'urls_aliexpress' => $resultadoProducto['urls_aliexpress'],
                'csv_coincidentes' => $resultadoProducto['csv_coincidentes'],
                'csv_insertadas' => $resultadoProducto['csv_insertadas'],
                'urls_insertadas' => $resultadoProducto['urls_insertadas'],
                'urls_omitidas' => $resultadoProducto['urls_omitidas'],
                'error_amazon' => $resultadoProducto['error_amazon'],
                'error_aliexpress' => $resultadoProducto['error_aliexpress'],
            ]);
        } else {
            $this->actualizarEjecucionPaso($ejecucion, 'producto_omitido', [
                'detalle' => 'Producto #' . $producto->id . ' omitido (sin nombre o sin palabras exigidas)',
                'producto_id' => $producto->id,
            ]);
        }

        $indice = (int) ($request->input('indice') ?? (count($resultados) + (int) ($contadores['omitido_sin_nombre'] ?? 0) + (int) ($contadores['omitido_sin_palabras'] ?? 0)));
        $total = count($productoIds);
        $terminado = $indice >= $total;

        $logExtras = [
            'origen' => 'panel_categoria',
            'categoria_id' => (int) $categoria->id,
            'categoria_nombre' => (string) $categoria->nombre,
            'categoria_ids' => $log['categoria_ids'] ?? $this->obtenerIdsCategoriasConHijas((int) $categoria->id),
            'producto_ids' => $productoIds,
            'producto_actual' => [
                'producto_id' => $productoId,
                'nombre'      => trim((string) $producto->nombre),
                'indice'      => $indice,
                'total'       => $total,
            ],
        ];

        $this->faseBusquedaUrlsActual = 'persistir_progreso';

        if ($terminado) {
            $this->actualizarEjecucionPaso($ejecucion, 'finalizado', [
                'detalle' => 'Ejecución de categoría finalizada',
                'categoria_id' => (int) $categoria->id,
                'productos_procesados' => (int) ($contadores['productos_procesados'] ?? 0),
            ]);
            $this->finalizarEjecucionBusqueda(
                $ejecucion->fresh(),
                $contadores,
                $resultados,
                $productoId,
                'ok',
                null,
                $logExtras
            );
        } else {
            $this->persistirProgresoEjecucion($ejecucion, $contadores, $resultados, $productoId, $logExtras);
        }

        return response()->json($this->buildRespuestaProgresoPanelEjecucion(
            $ejecucion->fresh(),
            $resultadoProducto,
            $terminado
        ));
    }

    /**
     * Panel: detiene la ejecución en curso.
     */
    private function panelCronDetener(Request $request): JsonResponse
    {
        $request->validate([
            'categoria_id' => 'required|integer|exists:categorias,id',
            'ejecucion_id' => 'required|integer|min:1',
        ]);

        $categoria = Categoria::query()->findOrFail((int) $request->input('categoria_id'));
        $ejecucion = $this->buscarEjecucionPanelCron((int) $request->input('ejecucion_id'));

        $log = is_array($ejecucion->log) ? $ejecucion->log : [];
        if ((int) ($log['categoria_id'] ?? 0) !== (int) $categoria->id) {
            return response()->json(['ok' => false, 'error' => 'La ejecución no pertenece a esta categoría.'], 403);
        }

        if ($ejecucion->fin !== null) {
            return response()->json($this->buildRespuestaProgresoPanelEjecucion($ejecucion, null, true));
        }

        $contadores = is_array($log['contadores'] ?? null)
            ? $log['contadores']
            : $this->crearContadoresInicialesBusquedaUrls();
        $resultados = is_array($log['resultados'] ?? null) ? $log['resultados'] : [];
        $ultimoProductoId = (int) ($log['ultimo_producto_id'] ?? 0);

        $logExtras = [
            'origen' => 'panel_categoria',
            'categoria_id' => (int) $categoria->id,
            'categoria_nombre' => (string) $categoria->nombre,
            'categoria_ids' => $log['categoria_ids'] ?? $this->obtenerIdsCategoriasConHijas((int) $categoria->id),
            'producto_ids' => $log['producto_ids'] ?? [],
            'producto_actual' => $log['producto_actual'] ?? null,
            'error' => [
                'mensaje' => 'La ejecución se detuvo manualmente desde el panel de categoría.',
                'tipo'    => 'detenido_usuario',
            ],
        ];

        $this->actualizarEjecucionPaso($ejecucion, 'interrumpido', [
            'detalle' => 'Detenido por el usuario',
            'categoria_id' => (int) $categoria->id,
            'ultimo_producto_id' => $ultimoProductoId,
        ]);

        $this->finalizarEjecucionBusqueda(
            $ejecucion->fresh(),
            $contadores,
            $resultados,
            $ultimoProductoId,
            'interrumpido',
            'detenido_usuario',
            $logExtras
        );

        return response()->json($this->buildRespuestaProgresoPanelEjecucion($ejecucion->fresh(), null, true));
    }

    /**
     * Panel: estado de una ejecución (misma tabla que el historial del cron).
     */
    private function panelCronEstado(Request $request): JsonResponse
    {
        $request->validate([
            'categoria_id' => 'required|integer|exists:categorias,id',
            'ejecucion_id' => 'required|integer|min:1',
        ]);

        $categoria = Categoria::query()->findOrFail((int) $request->input('categoria_id'));
        $ejecucion = $this->buscarEjecucionPanelCron((int) $request->input('ejecucion_id'));

        $log = is_array($ejecucion->log) ? $ejecucion->log : [];
        if ((int) ($log['categoria_id'] ?? 0) !== (int) $categoria->id) {
            return response()->json(['ok' => false, 'error' => 'La ejecución no pertenece a esta categoría.'], 403);
        }

        return response()->json($this->buildRespuestaProgresoPanelEjecucion($ejecucion, null, $ejecucion->fin !== null));
    }

    private function buscarEjecucionPanelCron(int $ejecucionId): EjecucionGlobal
    {
        return EjecucionGlobal::query()
            ->where('nombre', self::NOMBRE_EJECUCION_GLOBAL)
            ->findOrFail($ejecucionId);
    }

    /**
     * @param \Illuminate\Support\Collection<int, Tienda> $todasLasTiendas
     * @param array<string, int> $contadores
     * @return array<string, mixed>|null
     */
    private function procesarProductoBusquedaUrlsIndividual(
        Producto $producto,
        \Illuminate\Support\Collection $todasLasTiendas,
        array &$contadores
    ): ?array {
        $nombre = trim((string) $producto->nombre);
        $palabrasExigidas = trim((string) ($producto->palabras_exigidas ?? ''));

        if ($nombre === '') {
            $contadores['omitido_sin_nombre']++;

            return null;
        }

        if ($palabrasExigidas === '') {
            $contadores['omitido_sin_palabras']++;

            return null;
        }

        $resultadoProducto = [
            'producto_id'                 => $producto->id,
            'nombre'                      => $nombre,
            'palabras_exigidas'           => $palabrasExigidas,
            'paginas_amazon'              => 0,
            'urls_amazon'                 => 0,
            'urls_aliexpress'             => 0,
            'urls_insertadas'             => 0,
            'urls_omitidas'               => 0,
            'detalle_urls'                => [],
            'error_amazon'                => null,
            'error_aliexpress'            => null,
            'csv_coincidentes'            => 0,
            'csv_coincidentes_codigo'     => 0,
            'csv_insertadas'              => 0,
            'csv_insertadas_codigo'       => 0,
            'csv_omitidas'                => 0,
            'codigos_sincronizados'       => false,
            'codigos_ofertas_consultadas' => 0,
        ];

        $contadores['neo_insertadas_en_producto'] = 0;

        $this->marcarFaseBusquedaUrls('sync_codigos');
        $statsSyncCodigos = $this->sincronizarCodigosProductoDesdeOfertasCsv($producto, $contadores);
        $resultadoProducto['codigos_sincronizados'] = $statsSyncCodigos['actualizado'];
        $resultadoProducto['codigos_ofertas_consultadas'] = $statsSyncCodigos['ofertas_consultadas'];
        if ($statsSyncCodigos['actualizado']) {
            $producto->refresh();
        }

        $this->marcarFaseBusquedaUrls('amazon');
        $statsAmazon = $this->procesarBusquedaAmazonEnCron($producto, $nombre, $palabrasExigidas, $contadores);
        $resultadoProducto['paginas_amazon'] = $statsAmazon['paginas'];
        $resultadoProducto['urls_amazon'] = $statsAmazon['urls_filtradas'];
        if ($statsAmazon['error'] !== null) {
            $resultadoProducto['error_amazon'] = $statsAmazon['error'];
        } else {
            $resultadoProducto['urls_insertadas'] += $statsAmazon['insertadas'];
            $resultadoProducto['urls_omitidas'] += $statsAmazon['omitidas'];
            $resultadoProducto['detalle_urls'] = array_merge(
                $resultadoProducto['detalle_urls'],
                $statsAmazon['detalle_urls']
            );
        }

        if (!$this->limiteInsercionesNeoProductoAlcanzado($contadores)) {
            sleep(self::ESPERA_ENTRE_TIENDAS_SEGUNDOS);

            $this->marcarFaseBusquedaUrls('aliexpress');
            $statsAli = $this->procesarBusquedaAliexpressEnCron($producto, $nombre, $palabrasExigidas, $contadores);
            $resultadoProducto['urls_aliexpress'] = $statsAli['urls_filtradas'];
            if ($statsAli['error'] !== null) {
                $resultadoProducto['error_aliexpress'] = $statsAli['error'];
            } else {
                $resultadoProducto['urls_insertadas'] += $statsAli['insertadas'];
                $resultadoProducto['urls_omitidas'] += $statsAli['omitidas'];
                $resultadoProducto['detalle_urls'] = array_merge(
                    $resultadoProducto['detalle_urls'],
                    $statsAli['detalle_urls']
                );
            }
        }

        if (!$this->limiteInsercionesNeoProductoAlcanzado($contadores)) {
            $this->marcarFaseBusquedaUrls('csv_codigos');
            $statsCsvCodigos = $this->procesarCsvOfertasCoincidentesPorCodigos(
                $producto,
                $todasLasTiendas,
                $contadores
            );
            $resultadoProducto['csv_coincidentes_codigo'] = $statsCsvCodigos['coincidentes'];
            $resultadoProducto['csv_insertadas_codigo'] = $statsCsvCodigos['insertadas'];
            $resultadoProducto['csv_coincidentes'] += $statsCsvCodigos['coincidentes'];
            $resultadoProducto['csv_insertadas'] += $statsCsvCodigos['insertadas'];
            $resultadoProducto['csv_omitidas'] += $statsCsvCodigos['omitidas'];
            $resultadoProducto['urls_insertadas'] += $statsCsvCodigos['insertadas'];
            $resultadoProducto['urls_omitidas'] += $statsCsvCodigos['omitidas'];
            $resultadoProducto['detalle_urls'] = array_merge(
                $resultadoProducto['detalle_urls'],
                $statsCsvCodigos['detalle_urls']
            );
        }

        if (!$this->limiteInsercionesNeoProductoAlcanzado($contadores)) {
            $this->marcarFaseBusquedaUrls('csv_palabras_inicio');
            $statsCsv = $this->procesarCsvOfertasCoincidentes(
                $producto,
                $palabrasExigidas,
                $todasLasTiendas,
                $contadores
            );
            $resultadoProducto['csv_coincidentes'] += $statsCsv['coincidentes'];
            $resultadoProducto['csv_insertadas'] += $statsCsv['insertadas'];
            $resultadoProducto['csv_omitidas'] += $statsCsv['omitidas'];
            $resultadoProducto['urls_insertadas'] += $statsCsv['insertadas'];
            $resultadoProducto['urls_omitidas'] += $statsCsv['omitidas'];
            $resultadoProducto['detalle_urls'] = array_merge(
                $resultadoProducto['detalle_urls'],
                $statsCsv['detalle_urls']
            );
        }

        $resultadoProducto['limite_neo_alcanzado'] = $this->limiteInsercionesNeoProductoAlcanzado($contadores);
        $resultadoProducto['neo_insertadas_en_producto'] = (int) ($contadores['neo_insertadas_en_producto'] ?? 0);
        $resultadoProducto['neo_insertadas_max'] = self::LIMITE_INSERCIONES_NEO_POR_PRODUCTO;
        $resultadoProducto['diagnostico'] = $this->contextoDiagnosticoBusquedaUrls;

        $contadores['productos_procesados']++;

        return $resultadoProducto;
    }

    /**
     * @return array<string, int>
     */
    private function crearContadoresInicialesBusquedaUrls(): array
    {
        return [
            'productos_en_query'              => 0,
            'productos_elegibles_total'       => 0,
            'omitido_sin_nombre'              => 0,
            'omitido_sin_palabras'            => 0,
            'productos_procesados'            => 0,
            'productos_error_amazon'          => 0,
            'productos_error_aliexpress'      => 0,
            'urls_amazon_filtradas'           => 0,
            'urls_aliexpress_filtradas'       => 0,
            'urls_omitida_oferta'             => 0,
            'urls_omitida_descartada'         => 0,
            'urls_omitida_neo'                => 0,
            'urls_omitida_limite_neo'         => 0,
            'urls_insertadas_neo'             => 0,
            'neo_insertadas_en_producto'      => 0,
            'csv_filas_coincidentes'          => 0,
            'csv_filas_coincidentes_codigo'   => 0,
            'csv_ofertas_consultadas_codigos' => 0,
            'csv_codigos_nuevos_en_producto'  => 0,
            'csv_omitida_oferta'              => 0,
            'csv_ya_en_neo'                   => 0,
            'csv_insertadas_neo'              => 0,
            'csv_insertadas_neo_codigo'       => 0,
            'csv_aniadida_neo_si'             => 0,
        ];
    }

    /**
     * @return array<int, int>
     */
    private function obtenerIdsCategoriasConHijas(int $categoriaId): array
    {
        $ids = [$categoriaId];

        $hijas = Categoria::query()
            ->where('parent_id', $categoriaId)
            ->get(['id']);

        foreach ($hijas as $hija) {
            $ids = array_merge($ids, $this->obtenerIdsCategoriasConHijas((int) $hija->id));
        }

        return array_values(array_unique($ids));
    }

    /**
     * @param array<int, int> $categoriaIds
     */
    private function queryProductosElegiblesBusquedaPorCategoria(array $categoriaIds): \Illuminate\Database\Eloquent\Builder
    {
        return $this->queryProductosElegiblesBusqueda()
            ->whereIn('categoria_id', $categoriaIds);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildRespuestaProgresoPanelEjecucion(
        EjecucionGlobal $ejecucion,
        ?array $resultadoProducto,
        bool $terminado
    ): array {
        $log = is_array($ejecucion->log) ? $ejecucion->log : [];
        $contadores = is_array($log['contadores'] ?? null) ? $log['contadores'] : [];
        $productoIds = is_array($log['producto_ids'] ?? null) ? $log['producto_ids'] : [];
        $total = count($productoIds);
        $procesados = (int) ($contadores['productos_procesados'] ?? 0)
            + (int) ($contadores['omitido_sin_nombre'] ?? 0)
            + (int) ($contadores['omitido_sin_palabras'] ?? 0);

        return [
            'ok'                 => true,
            'ejecucion_id'       => (int) $ejecucion->id,
            'estado'             => $log['estado'] ?? ($terminado ? 'ok' : 'running'),
            'terminado'          => $terminado,
            'indice'             => min($procesados, $total),
            'total'              => $total,
            'producto_actual'    => $log['producto_actual'] ?? null,
            'resultado_producto' => $resultadoProducto,
            'contadores'         => $contadores,
            'resumen_urls'       => $this->resumirUrlsDesdeContadores($contadores),
        ];
    }

    /**
     * @param  array<string, int>  $contadores
     * @return array{todas: int, existentes: int, insertadas_neo: int}
     */
    private function resumirUrlsDesdeContadores(array $contadores): array
    {
        $insertadasNeo = (int) ($contadores['urls_insertadas_neo'] ?? 0);
        $existentes = (int) ($contadores['urls_omitida_oferta'] ?? 0)
            + (int) ($contadores['urls_omitida_descartada'] ?? 0)
            + (int) ($contadores['urls_omitida_neo'] ?? 0)
            + (int) ($contadores['urls_omitida_limite_neo'] ?? 0)
            + (int) ($contadores['csv_omitida_oferta'] ?? 0)
            + (int) ($contadores['csv_ya_en_neo'] ?? 0);

        return [
            'todas'          => $insertadasNeo + $existentes,
            'existentes'     => $existentes,
            'insertadas_neo' => $insertadasNeo,
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $resultados
     * @return array{todas: int, existentes: int, insertadas_neo: int}
     */
    private function resumirUrlsResultadosBusqueda(array $resultados): array
    {
        $todas = 0;
        $existentes = 0;
        $insertadasNeo = 0;

        foreach ($resultados as $resultado) {
            foreach ($resultado['detalle_urls'] ?? [] as $detalle) {
                if (!is_array($detalle)) {
                    continue;
                }
                $todas++;
                if (($detalle['accion'] ?? '') === 'insertada') {
                    $insertadasNeo++;
                } else {
                    $existentes++;
                }
            }
        }

        return [
            'todas'          => $todas,
            'existentes'     => $existentes,
            'insertadas_neo' => $insertadasNeo,
        ];
    }

    private function normalizarClaveTiendaDetectar(string $texto): string
    {
        $s = Str::ascii(mb_strtolower(trim($texto)));

        return preg_replace('/[^a-z0-9]/', '', $s) ?? '';
    }

    /**
     * @return array<int, string>
     */
    private function clavesHostTiendaDetectar(Tienda $tienda): array
    {
        $claves = [];
        $nombre = $this->normalizarClaveTiendaDetectar((string) ($tienda->nombre ?? ''));
        if ($nombre !== '') {
            $claves[] = $nombre;
        }
        $urlCampo = trim((string) ($tienda->url ?? ''));
        if ($urlCampo !== '' && !str_contains($urlCampo, '.')) {
            $slug = $this->normalizarClaveTiendaDetectar($urlCampo);
            if ($slug !== '') {
                $claves[] = $slug;
            }
        }

        return array_values(array_unique(array_filter($claves)));
    }
}
