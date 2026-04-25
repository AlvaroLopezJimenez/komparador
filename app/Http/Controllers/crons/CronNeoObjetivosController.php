<?php

namespace App\Http\Controllers\Crons;

use App\Http\Controllers\Controller;
use App\Models\Aviso;
use App\Models\EjecucionGlobal;
use App\Models\Neo;
use App\Models\Neoobjetivo;
use App\Models\OfertaProducto;
use App\Models\Tienda;
use App\Models\UrlDescartada;
use App\Models\User;
use App\Http\Controllers\Scraping\PeticionApiHTMLController;
use App\Services\LimpiarUrlDeTiendas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Cron neo objetivos:
 * - Busca en neoobjetivo filas con visitada > 7 días
 * - Si la URL contiene la marca de rama Neo comparador (VPS) y NEO_CRON_PETICIONES_VPS_HABILITADAS es true, POST al VPS /sacar-ofertas-idea
 * - Si NEO_CRON_REDIRECCION_HABILITADA es false, no se llama al VPS /redireccion y se guarda en neo sin URL final
 * - Si es false, la rama Neo comparador solo actualiza visitada (sin peticiones) hasta reactivar el flujo completo
 * - Muestra en vista el resultado de la(s) petición(es)
 */
class CronNeoObjetivosController extends Controller
{
    /**
     * false: rama Neo comparador del cron no llama al VPS; solo marca visitada=now para no reelegir la fila 7 días.
     * true: comportamiento completo (sacar-ofertas + redirecciones).
     */
    private const NEO_CRON_PETICIONES_VPS_HABILITADAS = true;
    /**
     * false: tras extraer URLs del comparador no se intenta resolver /redireccion; se guarda en neo con url vacía.
     * true: para cada URL del comparador se llama a /redireccion y se usa final_url para decidir guardado/actualización.
     */
    private const NEO_CRON_REDIRECCION_HABILITADA = false;

    private const VPS_URL = 'http://51.38.184.245/sacar-ofertas-idea';
    private const VPS_REDIRECCION_URL = 'http://51.38.184.245/redireccion';
    private const NEO_CRON_TIMEOUT_VPS_SEGUNDOS = 20;
    /** Timeout solo para prueba manual cuando la URL es directamente un relocator (no afecta al cron). */
    private const TIMEOUT_REDIRECCION_PRUEBA_SEGUNDOS = 900;
    private const RETENCION_EJECUCIONES_DIAS = 30;

    /** Marca en neoobjetivo.url para la rama que delega en el VPS (valor en base64 para no exponer el literal en código). */
    private static function neoObjetivoMarcaRamaUrl(): string
    {
        return (string) base64_decode('aWRlYWxv', true);
    }

    private static function neoComparadorHostWww(): string
    {
        return (string) base64_decode('d3d3LmlkZWFsby5lcw==', true);
    }

    private static function neoComparadorOrigenHttps(): string
    {
        return (string) base64_decode('aHR0cHM6Ly93d3cuaWRlYWxvLmVz', true);
    }

    /** Patrón en minúsculas para detectar URL relocator del comparador. */
    private static function neoComparadorRelocatorMarcadorMinusculas(): string
    {
        return strtolower((string) base64_decode('aWRlYWxvLmVzL3JlbG9jYXRvcg==', true));
    }

    public function __invoke(Request $request)
    {
        $nombreEjecucion = 'cron_neo_objetivos';
        $ejecuciones = EjecucionGlobal::where('nombre', $nombreEjecucion)
            ->orderByDesc('id')
            ->limit(50)
            ->get(['id', 'inicio', 'fin', 'total', 'total_guardado', 'total_errores']);

        if ($request->filled('ejecucion_id')) {
            $ejecucion = EjecucionGlobal::where('nombre', $nombreEjecucion)
                ->find($request->input('ejecucion_id'));
            if (!$ejecucion || !is_array($ejecucion->log)) {
                abort(404, 'Ejecución no encontrada');
            }
            $log = $ejecucion->log;
            return view('admin.crons.cron_neo_objetivos_resultado', [
                'total_filas_neo'              => $log['total_filas_neo'] ?? 0,
                'resultados'                   => $log['resultados'] ?? [],
                'total_filas_categoria_tienda' => $log['total_filas_categoria_tienda'] ?? 0,
                'filas_sin_tienda_aviso'       => $log['filas_sin_tienda_aviso'] ?? 0,
                'resultados_categoria'         => $log['resultados_categoria'] ?? [],
                'resultados_categoria_tienda_detalle' => $log['resultados_categoria_tienda_detalle'] ?? [],
                'resultados_categoria_tienda'  => $log['resultados_categoria_tienda'] ?? [],
                'ejecucion_id'                 => $ejecucion->id,
                'ejecucion'                    => $ejecucion,
                'ejecuciones'                  => $ejecuciones,
            ]);
        }

        $inicio = now();
        $ejecucion = EjecucionGlobal::create([
            'inicio'         => $inicio,
            'fin'            => null,
            'nombre'         => $nombreEjecucion,
            'total'          => 0,
            'total_guardado' => 0,
            'total_errores'  => 0,
            'log'            => [
                'estado'      => 'running',
                'paso_actual' => 'inicio',
                'pasos'       => [
                    ['momento' => now()->toDateTimeString(), 'paso' => 'inicio', 'detalle' => 'Ejecución creada'],
                ],
            ],
        ]);
        $this->actualizarEjecucionPaso($ejecucion, 'consulta_neoobjetivo', [
            'detalle' => 'Buscando filas visitadas hace más de 7 días',
        ]);

        try {
        // Cargar todas las candidatas antiguas, normalizar "No encontrado" y luego escoger 5 más antiguas válidas.
        $filasNeoCandidatas = Neoobjetivo::query()
            ->where('visitada', '<', now()->subDays(7))
            ->get();

        $fechaNoEncontrado = now()->setYear(3999);
        $noEncontradoMarcadas = 0;
        $filasNeoValidas = $filasNeoCandidatas->filter(function (Neoobjetivo $n) use ($fechaNoEncontrado, &$noEncontradoMarcadas) {
            $url = trim((string) $n->url);
            if ($url === '' || strtolower($url) === 'no encontrado') {
                $n->visitada = $fechaNoEncontrado;
                $n->save();
                $noEncontradoMarcadas++;
                return false;
            }

            return true;
        })->values();

        $filasNeo = $filasNeoValidas
            ->sortBy('visitada')
            ->take(5)
            ->values();

        $this->actualizarEjecucionPaso($ejecucion, 'filtrado_neo', [
            'total_filas_candidatas' => $filasNeoCandidatas->count(),
            'total_no_encontrado_marcadas_3999' => $noEncontradoMarcadas,
            'total_filas_validas' => $filasNeoValidas->count(),
            'total_filas_seleccionadas' => $filasNeo->count(),
        ]);

        $filas = $filasNeo->filter(function (Neoobjetivo $n) {
            return stripos($n->url, self::neoObjetivoMarcaRamaUrl()) !== false;
        });
        $this->actualizarEjecucionPaso($ejecucion, 'procesando_neo', [
            'total_filas_neo' => $filas->count(),
        ]);

        $resultados = [];
        $urlsRedireccionProcesadas = 0;
        foreach ($filas as $neo) {
            $url = trim($neo->url);
            if ($url === '') {
                continue;
            }
            try {
                if (!self::NEO_CRON_PETICIONES_VPS_HABILITADAS) {
                    $neo->visitada = now();
                    $neo->save();
                    $resultados[] = [
                        'neoobjetivo_id'     => $neo->id,
                        'url'                => $url,
                        'http_status'        => null,
                        'body_raw'           => '',
                        'body_json'          => null,
                        'hrefs'              => [],
                        'redirecciones'      => [],
                        'neo_cron_sin_peticion_vps' => true,
                        'nota'               => 'Neo comparador: peticiones VPS desactivadas (NEO_CRON_PETICIONES_VPS_HABILITADAS); solo actualizado visitada.',
                    ];
                    continue;
                }

                $resp = Http::timeout(self::NEO_CRON_TIMEOUT_VPS_SEGUNDOS)
                    ->withHeaders([
                        'Content-Type' => 'application/json',
                        'Accept'      => 'application/json',
                    ])
                    ->post(self::VPS_URL, ['url' => $url]);

                $body = $resp->body();
                $decoded = null;
                try {
                    $decoded = json_decode($body, true);
                } catch (\Throwable $e) {
                    // leave decoded null, we'll show raw body
                }
                $hrefs = [];
                if (is_array($decoded) && !empty($decoded['success']) && !empty($decoded['html_b64'])) {
                    $hrefsBrutos = $this->extraerHrefsOfertasNeoComparador($decoded['html_b64']);
                    foreach ($hrefsBrutos as $h) {
                        $limpia = $this->limpiarUrlNeoComparadorRelocate($h);
                        if ($limpia !== '') {
                            $hrefs[] = $limpia;
                        }
                    }
                }

                $redirecciones = [];
                if (count($hrefs) > 0) {
                    foreach ($hrefs as $hrefLimpia) {
                        $urlsRedireccionProcesadas++;
                        $redirecciones[] = $this->procesarUrlRedireccion($hrefLimpia, $neo, $urlsRedireccionProcesadas);
                    }
                }

                $resultados[] = [
                    'neoobjetivo_id' => $neo->id,
                    'url'            => $url,
                    'http_status'   => $resp->status(),
                    'body_raw'      => $body,
                    'body_json'     => $decoded,
                    'hrefs'         => $hrefs,
                    'redirecciones' => $redirecciones,
                ];
                $neo->visitada = now();
                $neo->save();
            } catch (\Throwable $e) {
                $this->crearAvisoCronNeoSiNoExiste($neo->id, $e->getMessage());
                $resultados[] = [
                    'neoobjetivo_id' => $neo->id,
                    'url'            => $url,
                    'http_status'    => 0,
                    'body_raw'       => '',
                    'body_json'      => null,
                    'hrefs'          => [],
                    'redirecciones'  => [],
                    'error'          => $e->getMessage(),
                ];
            }
        }

        $totalGuardado = 0;
        $totalErrores = 0;
        foreach ($resultados as $r) {
            foreach ($r['redirecciones'] ?? [] as $red) {
                if (!empty($red['skipped']) || !empty($red['error'])) {
                    $totalErrores++;
                } else {
                    $totalGuardado++;
                }
            }
        }

        $resultadosParaLog = array_map(function ($r) {
            $copia = $r;

            if (!empty($r['neo_cron_sin_peticion_vps'])) {
                unset($copia['body_raw'], $copia['body_json']);
                return $copia;
            }

            $sinHrefs = empty($r['hrefs']) || count($r['hrefs']) === 0;
            $esErrorServidor = isset($r['http_status']) && (int) $r['http_status'] >= 500;
            if ($sinHrefs || $esErrorServidor) {
                // Guardar detalle de la primera petición para debug cuando no se extraen URLs
                // o cuando el VPS devuelve error de servidor.
                $rawRespuesta = (string) ($r['body_raw'] ?? '');
                $maxChars = 12000;
                if (strlen($rawRespuesta) > $maxChars) {
                    $rawRespuesta = substr($rawRespuesta, 0, $maxChars)
                        . "\n...[recortado, longitud_original=" . strlen((string) ($r['body_raw'] ?? '')) . "]";
                }
                $copia['debug_primera_peticion'] = [
                    'endpoint'      => self::VPS_URL,
                    'payload'       => ['url' => $r['url'] ?? null],
                    'http_status'   => $r['http_status'] ?? null,
                    'respuesta_raw' => $rawRespuesta,
                    'respuesta_json' => $r['body_json'] ?? null,
                ];
            }

            unset($copia['body_raw'], $copia['body_json']);
            return $copia;
        }, $resultados);

        // Rama categoría/tienda: mismas filas que arriba, sin marca de rama Neo comparador en la URL
        $filasRamaCategoriaTienda = $filasNeo->filter(function (Neoobjetivo $n) {
            return stripos($n->url, self::neoObjetivoMarcaRamaUrl()) === false;
        });
        $this->actualizarEjecucionPaso($ejecucion, 'procesando_categoria_tienda', [
            'total_filas_categoria_tienda' => $filasRamaCategoriaTienda->count(),
        ]);
        $todasLasTiendas = Tienda::select('id', 'nombre', 'url')->orderBy('nombre')->get();
        $filasSinTiendaAviso = 0;
        $resultadosCategoria = [];
        $resultadosRamaCategoriaTiendaDetalle = [];
        $resultadosRamaCategoriaTienda = [];
        foreach ($filasRamaCategoriaTienda as $neo) {
            $url = trim($neo->url ?? '');
            if ($url === '') {
                $this->crearAvisoUrlVaciaNeoObjetivo($neo->id);
                $resultadosRamaCategoriaTiendaDetalle[] = [
                    'neoobjetivo_id' => $neo->id,
                    'url'           => $neo->url,
                    'paso'          => 'url_vacia',
                    'mensaje'       => 'URL vacía tras trim',
                ];
                continue;
            }

            $tienda = $this->detectarTiendaPorUrl($url, $todasLasTiendas);
            if (!$tienda) {
                $this->crearAvisoTiendaNoEncontradaNeoObjetivo($neo->id);
                $filasSinTiendaAviso++;
                $resultadosRamaCategoriaTiendaDetalle[] = [
                    'neoobjetivo_id' => $neo->id,
                    'url'           => $url,
                    'paso'          => 'sin_tienda',
                    'mensaje'       => 'No se encontró tienda para esta URL (aviso interno creado)',
                ];
                continue;
            }

            $nombreControlador = $this->normalizarNombreTienda($tienda->nombre);
            $claseControlador = "App\\Http\\Controllers\\Scraping\\Tiendas\\{$nombreControlador}Controller";
            if (!class_exists($claseControlador)) {
                $this->crearAvisoControladorNoEncontradoNeoObjetivo($neo->id, $tienda->nombre);
                $resultadosRamaCategoriaTiendaDetalle[] = [
                    'neoobjetivo_id' => $neo->id,
                    'url'           => $url,
                    'paso'          => 'controlador_no_encontrado',
                    'tienda_nombre' => $tienda->nombre,
                    'clase_buscada' => $nombreControlador . 'Controller',
                    'mensaje'       => 'No existe controlador para la tienda "' . $tienda->nombre . '" (aviso interno creado)',
                ];
                continue;
            }

            $controladorTienda = new $claseControlador();
            $tipoListado = $controladorTienda->tipoListadoCategoria();
            if ($tipoListado === null || !in_array($tipoListado, ['sitemap', 'paginacion', 'mostrar_mas'], true)) {
                $this->crearAvisoSinTipoListadoNeoObjetivo($neo->id, $tienda->nombre, $tipoListado);
                $resultadosRamaCategoriaTiendaDetalle[] = [
                    'neoobjetivo_id' => $neo->id,
                    'url'           => $url,
                    'paso'          => 'sin_tipo_listado',
                    'tienda_nombre' => $tienda->nombre,
                    'tipo_listado'  => $tipoListado,
                    'mensaje'       => $tipoListado === null
                        ? 'La tienda no define tipo de listado de categoría (tipoListadoCategoria() devuelve null)'
                        : 'Tipo de listado no soportado: "' . $tipoListado . '"',
                ];
                continue;
            }

            $resultadosCategoria[] = [
                'neoobjetivo_id'   => $neo->id,
                'url'              => $url,
                'tienda_id'        => $tienda->id,
                'tienda_nombre'    => $tienda->nombre,
                'tipo_listado'     => $tipoListado,
            ];

            // Ejecutar petición(es) y extracción de URLs según tipo listado; guardar en log como en la rama Neo comparador
            $peticionesRamaCategoriaTienda = [];
            $todasLasUrlsExtraidas = [];
            try {
                if ($tipoListado === 'sitemap') {
                    $apiHTML = app(PeticionApiHTMLController::class);
                    $resultado = $apiHTML->obtenerHTML($url, null, $tienda->api ?? null);
                    $ok = !empty($resultado['success']);
                    $body = $resultado['html'] ?? '';
                    $err = $resultado['error'] ?? null;
                    $status = $ok ? 200 : 0;
                    $urlsExtraidas = $ok ? $controladorTienda->urlsProductosDesdeSitemap($body) : [];
                    $todasLasUrlsExtraidas = $urlsExtraidas;
                    $peticionesRamaCategoriaTienda[] = [
                        'url_peticion'    => $url,
                        'http_status'     => $status,
                        'error'           => $err,
                        'urls_extraidas'  => $urlsExtraidas,
                        'tipo'            => 'sitemap',
                        'api_utilizada'   => $resultado['proveedor'] ?? null,
                    ];
                } elseif ($tipoListado === 'paginacion') {
                    $apiHTML = app(PeticionApiHTMLController::class);
                    $currentUrl = $url;
                    $maxPaginas = 50;
                    while ($currentUrl !== null && $currentUrl !== '' && count($peticionesRamaCategoriaTienda) < $maxPaginas) {
                        $resultado = $apiHTML->obtenerHTML($currentUrl, null, $tienda->api ?? null);
                        $ok = !empty($resultado['success']);
                        $html = $resultado['html'] ?? '';
                        $err = $resultado['error'] ?? null;
                        $status = $ok ? 200 : 0;
                        $extraccion = $controladorTienda->extraerProductosYSiguientePagina($html, $currentUrl);
                        $urlsPagina = $extraccion['urls_productos'] ?? [];
                        $siguienteUrl = isset($extraccion['siguiente_url']) && $extraccion['siguiente_url'] !== '' ? $extraccion['siguiente_url'] : null;
                        if ($ok) {
                            $todasLasUrlsExtraidas = array_merge($todasLasUrlsExtraidas, $urlsPagina);
                        }
                        $peticionesRamaCategoriaTienda[] = [
                            'url_peticion'    => $currentUrl,
                            'http_status'     => $status,
                            'error'           => $err,
                            'urls_extraidas'  => $urlsPagina,
                            'siguiente_url'   => $siguienteUrl,
                            'tipo'            => 'paginacion',
                            'api_utilizada'  => $resultado['proveedor'] ?? null,
                        ];
                        $currentUrl = $siguienteUrl;
                    }
                } else {
                    // mostrar_mas: una petición HTML (VPS puede usar cargar_mas_selector para pulsar "Cargar más")
                    $apiHTML = app(PeticionApiHTMLController::class);
                    $cargarMasSelector = $controladorTienda->selectorCargarMasParaVps();
                    $resultado = $apiHTML->obtenerHTML($url, null, $tienda->api ?? null, $cargarMasSelector);
                    $ok = !empty($resultado['success']);
                    $html = $resultado['html'] ?? '';
                    $err = $resultado['error'] ?? null;
                    $status = $ok ? 200 : 0;
                    $urlsExtraidas = $ok ? $controladorTienda->urlsProductosDesdeHtmlMostrarMas($html) : [];
                    $todasLasUrlsExtraidas = $urlsExtraidas;
                    $peticionesRamaCategoriaTienda[] = [
                        'url_peticion'          => $url,
                        'http_status'           => $status,
                        'error'                 => $err,
                        'urls_extraidas'        => $urlsExtraidas,
                        'tipo'                  => 'mostrar_mas',
                        'api_utilizada'         => $resultado['proveedor'] ?? null,
                        'cargar_mas_selector'   => $cargarMasSelector,
                    ];
                }
            } catch (\Throwable $e) {
                $peticionesRamaCategoriaTienda[] = [
                    'url_peticion'   => $url,
                    'http_status'    => 0,
                    'error'         => $e->getMessage(),
                    'urls_extraidas' => [],
                    'tipo'           => $tipoListado,
                    'api_utilizada' => null,
                ];
            }

            // Procesar cada URL extraída: limpiar, comprobar neo/ofertas/urls_descartadas, guardar en neo (sin neo.neo)
            $redireccionesCategoria = [];
            $numeroUrlCategoria = 0;
            foreach ($todasLasUrlsExtraidas as $urlProducto) {
                $numeroUrlCategoria++;
                $redireccionesCategoria[] = $this->procesarUrlCategoria($urlProducto, $neo, $numeroUrlCategoria);
            }
            $neo->update(['visitada' => now()]);

            if (count($todasLasUrlsExtraidas) === 0) {
                $this->crearAvisoNoProductosCategoriaNeoObjetivo($neo->id, $tienda->nombre, $neo->categoria?->nombre, $url);
            }

            $resultadosRamaCategoriaTienda[] = [
                'neoobjetivo_id'        => $neo->id,
                'url'                   => $url,
                'tienda_nombre'         => $tienda->nombre,
                'tipo_listado'          => $tipoListado,
                'peticiones'            => $peticionesRamaCategoriaTienda,
                'urls_extraidas_total'  => count($todasLasUrlsExtraidas),
                'redirecciones'         => $redireccionesCategoria,
            ];
            $resultadosRamaCategoriaTiendaDetalle[] = [
                'neoobjetivo_id'        => $neo->id,
                'url'                   => $url,
                'paso'                  => 'ok',
                'tienda_nombre'         => $tienda->nombre,
                'tipo_listado'          => $tipoListado,
                'mensaje'               => 'Listo para procesar (tipo: ' . $tipoListado . '). Peticiones: ' . count($peticionesRamaCategoriaTienda) . ', URLs extraídas: ' . count($todasLasUrlsExtraidas),
                'peticiones_count'      => count($peticionesRamaCategoriaTienda),
                'urls_extraidas_total'  => count($todasLasUrlsExtraidas),
            ];
        }

        // Contar guardados y errores de la rama categoría/tienda (redirecciones categoría)
        foreach ($resultadosRamaCategoriaTienda as $rno) {
            foreach ($rno['redirecciones'] ?? [] as $red) {
                if (!empty($red['skipped']) || !empty($red['error'])) {
                    $totalErrores++;
                } else {
                    $totalGuardado++;
                }
            }
        }

        $logEjecucion = [
            'estado'                       => 'ok',
            'paso_actual'                  => 'finalizado',
            'total_filas_neo'              => $filas->count(),
            'resultados'                   => $resultadosParaLog,
            'total_filas_categoria_tienda' => $filasRamaCategoriaTienda->count(),
            'filas_sin_tienda_aviso'      => $filasSinTiendaAviso,
            'resultados_categoria'        => $resultadosCategoria,
            'resultados_categoria_tienda_detalle' => $resultadosRamaCategoriaTiendaDetalle,
            'resultados_categoria_tienda' => $resultadosRamaCategoriaTienda,
        ];
        $logEjecucion = $this->sanitizarLogUrls($logEjecucion);
        $totalFilas = $filas->count() + $filasRamaCategoriaTienda->count();
        $ejecucion->update([
            'fin'             => now(),
            'total'           => $totalFilas,
            'total_guardado'  => $totalGuardado,
            'total_errores'   => $totalErrores,
            'log'             => $logEjecucion,
        ]);

        $mensaje = sprintf(
            "Cron neo objetivos ejecutado.\nTotal filas: %d\nGuardados: %d\nErrores: %d",
            $totalFilas,
            $totalGuardado,
            $totalErrores
        );

        $this->eliminarEjecucionesAntiguas($nombreEjecucion);
        return response($mensaje, 200, [
            'Content-Type' => 'text/plain; charset=utf-8',
        ]);
        } catch (\Throwable $e) {
            $this->actualizarEjecucionError($ejecucion, $e);
            $this->eliminarEjecucionesAntiguas($nombreEjecucion);
            throw $e;
        }
    }

    /**
     * Vista de pruebas manuales para ejecutar una única URL sin persistir datos.
     */
    public function pruebaNeoobjetivosForm(Request $request)
    {
        $mostrarPendientes = $request->boolean('ver_pendientes');
        $limitePendientes = (int) $request->input('limite_pendientes', 50);
        $limitePendientes = max(1, min($limitePendientes, 200));

        return view('admin.neo.prueba-neoobjetivos', [
            'tiendas' => Tienda::orderBy('nombre')->get(['id', 'nombre']),
            'resultado' => null,
            'form' => [],
            'mostrar_pendientes' => $mostrarPendientes,
            'limite_pendientes' => $limitePendientes,
            'filas_pendientes' => $mostrarPendientes ? $this->obtenerFilasPendientesVisitar($limitePendientes) : collect(),
        ]);
    }

    /**
     * Ejecuta una prueba puntual de neo objetivos (1 URL) sin guardar cambios en BD.
     */
    public function pruebaNeoobjetivosEjecutar(Request $request)
    {
        $data = $request->validate([
            'url' => ['required', 'string', 'max:2000'],
            'tienda_id' => ['nullable', 'integer', 'exists:tiendas,id'],
        ]);

        $url = trim($data['url']);
        $esRamaNeoComparador = stripos($url, self::neoObjetivoMarcaRamaUrl()) !== false;
        $esSoloRedireccionRelocator = $this->esUrlNeoComparadorRelocatorDirecta($url);
        $tienda = null;
        if (!$esRamaNeoComparador) {
            if (empty($data['tienda_id'])) {
                return back()
                    ->withErrors(['tienda_id' => 'La tienda es obligatoria cuando la URL no es de la rama Neo comparador.'])
                    ->withInput();
            }
            $tienda = Tienda::findOrFail((int) $data['tienda_id']);
        }
        $neo = new Neoobjetivo([
            'id' => 0,
            'url' => $url,
            'tienda_id' => $tienda?->id,
            'visitada' => now()->subDays(8),
        ]);

        $pasos = [];
        $resultados = [];
        $resultadosRamaCategoriaTienda = [];
        $resultadosRamaCategoriaTiendaDetalle = [];
        $resultadosCategoria = [];
        $totalGuardado = 0;
        $totalErrores = 0;

        try {
            $this->agregarPasoPrueba($pasos, 'inicio', 'Iniciando prueba manual (sin persistencia)', [
                'url' => $url,
                'tienda_id' => $tienda?->id,
                'tienda_nombre' => $tienda?->nombre,
            ]);

            if ($esSoloRedireccionRelocator) {
                $this->agregarPasoPrueba($pasos, 'solo_redireccion', 'URL relocator: solo petición al VPS /redireccion', [
                    'endpoint' => self::VPS_REDIRECCION_URL,
                    'timeout_segundos' => self::TIMEOUT_REDIRECCION_PRUEBA_SEGUNDOS,
                ]);

                $respRed = null;
                $bodyRed = '';
                $decodedRed = null;
                $errorRed = null;
                try {
                    $respRed = Http::timeout(self::TIMEOUT_REDIRECCION_PRUEBA_SEGUNDOS)
                        ->withHeaders([
                            'Content-Type' => 'application/json',
                            'Accept'       => 'application/json',
                        ])
                        ->post(self::VPS_REDIRECCION_URL, ['url' => $url]);

                    $bodyRed = $respRed->body();
                    try {
                        $decodedRed = json_decode($bodyRed, true);
                    } catch (\Throwable $e) {
                        $decodedRed = null;
                    }
                } catch (\Throwable $e) {
                    $errorRed = $e->getMessage();
                }

                $this->agregarPasoPrueba($pasos, 'respuesta_vps_redireccion', 'Respuesta VPS /redireccion', [
                    'endpoint' => self::VPS_REDIRECCION_URL,
                    'http_status' => $respRed?->status() ?? 0,
                    'payload' => ['url' => $url],
                    'error' => $errorRed,
                ]);

                $this->agregarPasoPrueba($pasos, 'finalizado', 'Prueba solo redirección completada.', [
                    'http_status' => $respRed?->status() ?? 0,
                ]);

                $resultado = [
                    'log' => [
                        'estado' => collect($pasos)->contains(fn($p) => ($p['paso'] ?? '') === 'error') ? 'error' : 'ok',
                        'paso_actual' => 'finalizado',
                        'pasos' => $pasos,
                    ],
                    'solo_redireccion' => true,
                    'respuesta_solo_redireccion' => [
                        'endpoint' => self::VPS_REDIRECCION_URL,
                        'http_status' => $respRed?->status() ?? 0,
                        'request_payload' => ['url' => $url],
                        'body_raw' => $bodyRed,
                        'body_json' => is_array($decodedRed) ? $decodedRed : null,
                        'error_excepcion' => $errorRed,
                        'timeout_segundos' => self::TIMEOUT_REDIRECCION_PRUEBA_SEGUNDOS,
                    ],
                    'total_filas_neo_comparador' => 0,
                    'total_filas_categoria_tienda_prueba' => 0,
                    'resultados' => [],
                    'resultados_categoria_tienda' => [],
                    'resultados_categoria_tienda_detalle' => [],
                    'resultados_categoria' => [],
                    'filas_sin_tienda_aviso' => 0,
                    'totales' => [
                        'total_guardado' => 0,
                        'total_errores' => 0,
                    ],
                ];

                return view('admin.neo.prueba-neoobjetivos', [
                    'tiendas' => Tienda::orderBy('nombre')->get(['id', 'nombre']),
                    'resultado' => $resultado,
                    'form' => [
                        'url' => $url,
                        'tienda_id' => $tienda?->id,
                    ],
                    'mostrar_pendientes' => false,
                    'limite_pendientes' => 50,
                    'filas_pendientes' => collect(),
                ]);
            }

            if ($esRamaNeoComparador) {
                if (!self::NEO_CRON_PETICIONES_VPS_HABILITADAS) {
                    $this->agregarPasoPrueba($pasos, 'procesando_rama_neo_comparador', 'Rama Neo comparador: peticiones VPS desactivadas (NEO_CRON_PETICIONES_VPS_HABILITADAS)');
                    $this->agregarPasoPrueba($pasos, 'sin_peticiones_vps', 'Sin llamadas al VPS. En el cron real se actualizaría visitada en neoobjetivo.');
                    $resultados[] = [
                        'neoobjetivo_id' => 0,
                        'url' => $url,
                        'http_status' => null,
                        'body_raw' => '',
                        'body_json' => null,
                        'hrefs' => [],
                        'redirecciones' => [],
                        'neo_cron_sin_peticion_vps' => true,
                        'nota' => 'Prueba manual alineada con cron: sin peticiones mientras NEO_CRON_PETICIONES_VPS_HABILITADAS es false.',
                    ];
                    $this->agregarPasoPrueba($pasos, 'fin_rama_neo_comparador', 'Finalizada rama Neo comparador (sin peticiones)', [
                        'hrefs_extraidos' => 0,
                        'redirecciones' => 0,
                    ]);
                } else {
                $this->agregarPasoPrueba($pasos, 'procesando_rama_neo_comparador', 'La URL pertenece a rama Neo comparador');
                $resp = null;
                $body = '';
                $decoded = null;
                $errorPrimeraPeticion = null;
                try {
                    $resp = Http::timeout(self::NEO_CRON_TIMEOUT_VPS_SEGUNDOS)
                        ->withHeaders([
                            'Content-Type' => 'application/json',
                            'Accept'      => 'application/json',
                        ])
                        ->post(self::VPS_URL, ['url' => $url]);

                    $body = $resp->body();
                    try {
                        $decoded = json_decode($body, true);
                    } catch (\Throwable $e) {
                        $decoded = null;
                    }
                } catch (\Throwable $e) {
                    $errorPrimeraPeticion = $this->normalizarMensajeTimeout($e->getMessage());
                }

                $this->agregarPasoPrueba($pasos, 'respuesta_vps_sacar_ofertas', 'Respuesta VPS sacar-ofertas-idea', [
                    'endpoint' => self::VPS_URL,
                    'http_status' => $resp?->status() ?? 0,
                    'payload' => ['url' => $url],
                    'success_json' => is_array($decoded) ? ($decoded['success'] ?? null) : null,
                    'tiene_html_b64' => is_array($decoded) && !empty($decoded['html_b64']),
                    'error' => $errorPrimeraPeticion,
                ]);

                $hrefs = [];
                if (is_array($decoded) && !empty($decoded['success']) && !empty($decoded['html_b64'])) {
                    $hrefsBrutos = $this->extraerHrefsOfertasNeoComparador($decoded['html_b64']);
                    foreach ($hrefsBrutos as $h) {
                        $limpia = $this->limpiarUrlNeoComparadorRelocate($h);
                        if ($limpia !== '') {
                            $hrefs[] = $limpia;
                        }
                    }
                }

                $redirecciones = [];
                // Transacción solo durante escrituras a BD (evita "MySQL server has gone away" por esperas HTTP largas).
                DB::reconnect();
                DB::beginTransaction();
                try {
                    $numero = 0;
                    foreach ($hrefs as $hrefLimpia) {
                        $numero++;
                        try {
                            // Refrescamos conexión antes de cada URL por si la anterior tardó mucho en red.
                            DB::reconnect();
                            $redirecciones[] = $this->procesarUrlRedireccion($hrefLimpia, $neo, $numero);
                        } catch (\Throwable $eRedireccion) {
                            $mensajeError = (string) $eRedireccion->getMessage();

                            // Si MySQL cerró la conexión, reintentamos una vez esta URL.
                            if (stripos($mensajeError, 'server has gone away') !== false) {
                                try {
                                    DB::reconnect();
                                    $redirecciones[] = $this->procesarUrlRedireccion($hrefLimpia, $neo, $numero);
                                    continue;
                                } catch (\Throwable $eRetry) {
                                    $mensajeError = (string) $eRetry->getMessage();
                                }
                            }

                            $redirecciones[] = [
                                'success' => false,
                                'error' => $mensajeError,
                                'url_solicitada' => $hrefLimpia,
                                'log_pasos' => [
                                    ['paso' => 1, 'texto' => "URL limpiada (#{$numero}):", 'valor' => $hrefLimpia],
                                    ['paso' => 2, 'texto' => 'Error en procesarUrlRedireccion()', 'decision' => $mensajeError],
                                ],
                                'accion_final' => 'Error en prueba; se continúa con la siguiente URL',
                            ];
                        }
                    }
                } finally {
                    DB::rollBack();
                }

                $resultados[] = [
                    'neoobjetivo_id' => 0,
                    'url' => $url,
                    'http_status' => $resp?->status() ?? 0,
                    'body_raw' => $body,
                    'body_json' => $decoded,
                    'hrefs' => $hrefs,
                    'redirecciones' => $redirecciones,
                    'error' => $errorPrimeraPeticion,
                    'vps_log' => [
                        'tipo' => 'sacar_ofertas_idea',
                        'endpoint' => self::VPS_URL,
                        'http_status' => $resp?->status() ?? 0,
                        'request_payload' => ['url' => $url],
                        'response_json' => $this->sanitizarJsonVpsSacarOfertasParaLog($decoded),
                        'error' => $errorPrimeraPeticion,
                        'raw_body_preview' => strlen($body) > 12000
                            ? mb_substr($body, 0, 12000) . "\n...[recortado, longitud_original=" . strlen($body) . "]"
                            : $body,
                    ],
                ];

                $this->agregarPasoPrueba($pasos, 'fin_rama_neo_comparador', 'Finalizada rama Neo comparador', [
                    'http_status' => $resp?->status() ?? 0,
                    'hrefs_extraidos' => count($hrefs),
                    'redirecciones' => count($redirecciones),
                    'error_primera_peticion' => $errorPrimeraPeticion,
                ]);
                }
            } else {
                $this->agregarPasoPrueba($pasos, 'procesando_rama_categoria_tienda', 'La URL pertenece a rama categoría/tienda');
                $nombreControlador = $this->normalizarNombreTienda($tienda->nombre);
                $claseControlador = "App\\Http\\Controllers\\Scraping\\Tiendas\\{$nombreControlador}Controller";

                if (!class_exists($claseControlador)) {
                    $resultadosRamaCategoriaTiendaDetalle[] = [
                        'neoobjetivo_id' => 0,
                        'url' => $url,
                        'paso' => 'controlador_no_encontrado',
                        'tienda_nombre' => $tienda->nombre,
                        'clase_buscada' => $nombreControlador . 'Controller',
                        'mensaje' => 'No existe controlador para la tienda seleccionada',
                    ];
                } else {
                    $controladorTienda = new $claseControlador();
                    $tipoListado = $controladorTienda->tipoListadoCategoria();

                    if ($tipoListado === null || !in_array($tipoListado, ['sitemap', 'paginacion', 'mostrar_mas'], true)) {
                        $resultadosRamaCategoriaTiendaDetalle[] = [
                            'neoobjetivo_id' => 0,
                            'url' => $url,
                            'paso' => 'sin_tipo_listado',
                            'tienda_nombre' => $tienda->nombre,
                            'tipo_listado' => $tipoListado,
                            'mensaje' => $tipoListado === null
                                ? 'La tienda no define tipo de listado de categoría'
                                : 'Tipo de listado no soportado: "' . $tipoListado . '"',
                        ];
                    } else {
                        $resultadosCategoria[] = [
                            'neoobjetivo_id' => 0,
                            'url' => $url,
                            'tienda_id' => $tienda->id,
                            'tienda_nombre' => $tienda->nombre,
                            'tipo_listado' => $tipoListado,
                        ];

                        $peticionesRamaCategoriaTienda = [];
                        $todasLasUrlsExtraidas = [];
                        $apiHTML = app(PeticionApiHTMLController::class);

                        if ($tipoListado === 'sitemap') {
                            $resultado = $apiHTML->obtenerHTML($url, null, $tienda->api ?? null);
                            $ok = !empty($resultado['success']);
                            $body = $resultado['html'] ?? '';
                            $urlsExtraidas = $ok ? $controladorTienda->urlsProductosDesdeSitemap($body) : [];
                            $todasLasUrlsExtraidas = $urlsExtraidas;
                            $peticionesRamaCategoriaTienda[] = [
                                'url_peticion' => $url,
                                'http_status' => $ok ? 200 : 0,
                                'error' => $resultado['error'] ?? null,
                                'urls_extraidas' => $urlsExtraidas,
                                'tipo' => 'sitemap',
                                'api_utilizada' => $resultado['proveedor'] ?? null,
                                'vps_log' => $resultado['vps_log'] ?? null,
                            ];
                            $this->agregarPasoPrueba($pasos, 'peticion_html_sitemap', 'Petición HTML (sitemap)', [
                                'url' => $url,
                                'ok' => $ok,
                                'api' => $resultado['proveedor'] ?? null,
                                'urls_extraidas_count' => count($urlsExtraidas),
                                'html_length' => strlen($body),
                            ]);
                        } elseif ($tipoListado === 'paginacion') {
                            $currentUrl = $url;
                            $maxPaginas = 50;
                            $pagIdx = 0;
                            while ($currentUrl !== null && $currentUrl !== '' && count($peticionesRamaCategoriaTienda) < $maxPaginas) {
                                $pagIdx++;
                                $resultado = $apiHTML->obtenerHTML($currentUrl, null, $tienda->api ?? null);
                                $ok = !empty($resultado['success']);
                                $html = $resultado['html'] ?? '';
                                $extraccion = $controladorTienda->extraerProductosYSiguientePagina($html, $currentUrl);
                                $urlsPagina = $extraccion['urls_productos'] ?? [];
                                $siguienteUrl = isset($extraccion['siguiente_url']) && $extraccion['siguiente_url'] !== '' ? $extraccion['siguiente_url'] : null;
                                if ($ok) {
                                    $todasLasUrlsExtraidas = array_merge($todasLasUrlsExtraidas, $urlsPagina);
                                }
                                $peticionesRamaCategoriaTienda[] = [
                                    'url_peticion' => $currentUrl,
                                    'http_status' => $ok ? 200 : 0,
                                    'error' => $resultado['error'] ?? null,
                                    'urls_extraidas' => $urlsPagina,
                                    'siguiente_url' => $siguienteUrl,
                                    'tipo' => 'paginacion',
                                    'api_utilizada' => $resultado['proveedor'] ?? null,
                                    'vps_log' => $resultado['vps_log'] ?? null,
                                ];
                                $this->agregarPasoPrueba($pasos, 'peticion_html_pagina_' . $pagIdx, 'Petición HTML (paginación, página ' . $pagIdx . ')', [
                                    'url_peticion' => $currentUrl,
                                    'ok' => $ok,
                                    'api' => $resultado['proveedor'] ?? null,
                                    'urls_en_pagina' => count($urlsPagina),
                                    'siguiente_url' => $siguienteUrl,
                                    'html_length' => strlen($html),
                                ]);
                                $currentUrl = $siguienteUrl;
                            }
                        } else {
                            $cargarMasSelector = $controladorTienda->selectorCargarMasParaVps();
                            $resultado = $apiHTML->obtenerHTML($url, null, $tienda->api ?? null, $cargarMasSelector);
                            $ok = !empty($resultado['success']);
                            $html = $resultado['html'] ?? '';
                            $urlsExtraidas = $ok ? $controladorTienda->urlsProductosDesdeHtmlMostrarMas($html) : [];
                            $todasLasUrlsExtraidas = $urlsExtraidas;
                            $peticionesRamaCategoriaTienda[] = [
                                'url_peticion' => $url,
                                'http_status' => $ok ? 200 : 0,
                                'error' => $resultado['error'] ?? null,
                                'urls_extraidas' => $urlsExtraidas,
                                'tipo' => 'mostrar_mas',
                                'api_utilizada' => $resultado['proveedor'] ?? null,
                                'cargar_mas_selector' => $cargarMasSelector,
                                'vps_log' => $resultado['vps_log'] ?? null,
                            ];
                            $this->agregarPasoPrueba($pasos, 'peticion_html_mostrar_mas', 'Petición HTML (mostrar_más)', [
                                'url' => $url,
                                'ok' => $ok,
                                'api' => $resultado['proveedor'] ?? null,
                                'cargar_mas_selector' => $cargarMasSelector,
                                'urls_extraidas_count' => count($urlsExtraidas),
                                'html_length' => strlen($html),
                            ]);
                        }

                        $redireccionesCategoria = [];
                        DB::reconnect();
                        DB::beginTransaction();
                        try {
                            $numero = 0;
                            foreach ($todasLasUrlsExtraidas as $urlProducto) {
                                $numero++;
                                $redireccionesCategoria[] = $this->procesarUrlCategoria($urlProducto, $neo, $numero);
                            }
                        } finally {
                            DB::rollBack();
                        }

                        $resultadosRamaCategoriaTienda[] = [
                            'neoobjetivo_id' => 0,
                            'url' => $url,
                            'tienda_nombre' => $tienda->nombre,
                            'tipo_listado' => $tipoListado,
                            'peticiones' => $peticionesRamaCategoriaTienda,
                            'urls_extraidas_total' => count($todasLasUrlsExtraidas),
                            'redirecciones' => $redireccionesCategoria,
                        ];

                        $resultadosRamaCategoriaTiendaDetalle[] = [
                            'neoobjetivo_id' => 0,
                            'url' => $url,
                            'paso' => 'ok',
                            'tienda_nombre' => $tienda->nombre,
                            'tipo_listado' => $tipoListado,
                            'mensaje' => 'Listo para procesar (tipo: ' . $tipoListado . '). Peticiones: ' . count($peticionesRamaCategoriaTienda) . ', URLs extraídas: ' . count($todasLasUrlsExtraidas),
                            'peticiones_count' => count($peticionesRamaCategoriaTienda),
                            'urls_extraidas_total' => count($todasLasUrlsExtraidas),
                        ];

                        $this->agregarPasoPrueba($pasos, 'fin_rama_categoria_tienda', 'Finalizada rama categoría/tienda', [
                            'tipo_listado' => $tipoListado,
                            'peticiones' => count($peticionesRamaCategoriaTienda),
                            'urls_extraidas_total' => count($todasLasUrlsExtraidas),
                        ]);
                    }
                }
            }

            foreach ($resultados as $r) {
                foreach ($r['redirecciones'] ?? [] as $red) {
                    if (!empty($red['skipped']) || !empty($red['error'])) {
                        $totalErrores++;
                    } else {
                        $totalGuardado++;
                    }
                }
            }
            foreach ($resultadosRamaCategoriaTienda as $rno) {
                foreach ($rno['redirecciones'] ?? [] as $red) {
                    if (!empty($red['skipped']) || !empty($red['error'])) {
                        $totalErrores++;
                    } else {
                        $totalGuardado++;
                    }
                }
            }

            $this->agregarPasoPrueba($pasos, 'finalizado', 'Prueba completada. Las escrituras a BD se revirtieron en transacciones cortas (sin persistir).', [
                'total_guardado_simulado' => $totalGuardado,
                'total_errores_simulado' => $totalErrores,
            ]);
        } catch (\Throwable $e) {
            $this->agregarPasoPrueba($pasos, 'error', 'Error durante la prueba', [
                'mensaje' => $e->getMessage(),
                'tipo' => get_class($e),
            ]);
            if (DB::transactionLevel() > 0) {
                try {
                    DB::rollBack();
                } catch (\Throwable $ignored) {
                    // Conexión ya cerrada u otra causa; no bloquear la vista de resultado.
                }
            }
        }

        $resultado = [
            'log' => [
                'estado' => collect($pasos)->contains(fn($p) => ($p['paso'] ?? '') === 'error') ? 'error' : 'ok',
                'paso_actual' => !empty($pasos) ? ($pasos[count($pasos) - 1]['paso'] ?? 'finalizado') : 'finalizado',
                'pasos' => $pasos,
            ],
            'total_filas_neo_comparador' => $esRamaNeoComparador ? 1 : 0,
            'total_filas_categoria_tienda_prueba' => $esRamaNeoComparador ? 0 : 1,
            'resultados' => $resultados,
            'resultados_categoria_tienda' => $resultadosRamaCategoriaTienda,
            'resultados_categoria_tienda_detalle' => $resultadosRamaCategoriaTiendaDetalle,
            'resultados_categoria' => $resultadosCategoria,
            'filas_sin_tienda_aviso' => 0,
            'totales' => [
                'total_guardado' => $totalGuardado,
                'total_errores' => $totalErrores,
            ],
        ];

        return view('admin.neo.prueba-neoobjetivos', [
            'tiendas' => Tienda::orderBy('nombre')->get(['id', 'nombre']),
            'resultado' => $resultado,
            'form' => [
                'url' => $url,
                'tienda_id' => $tienda?->id,
            ],
            'mostrar_pendientes' => false,
            'limite_pendientes' => 50,
            'filas_pendientes' => collect(),
        ]);
    }

    private function obtenerFilasPendientesVisitar(int $limite)
    {
        return Neoobjetivo::query()
            ->where('visitada', '<', now()->subDays(7))
            ->whereNotNull('url')
            ->orderBy('visitada')
            ->limit($limite)
            ->get()
            ->map(function (Neoobjetivo $neo) {
                $urlDescifrada = trim((string) $neo->url);
                return [
                    'id' => $neo->id,
                    'visitada' => optional($neo->visitada)->toDateTimeString(),
                    'producto_id' => $neo->producto_id,
                    'categoria_id' => $neo->categoria_id,
                    'tienda_id' => $neo->tienda_id,
                    'url_cifrada' => (string) $neo->getRawOriginal('url'),
                    'url_descifrada' => $urlDescifrada,
                    'es_url_neo' => $urlDescifrada !== '' && stripos($urlDescifrada, self::neoObjetivoMarcaRamaUrl()) !== false,
                ];
            });
    }

    private function agregarPasoPrueba(array &$pasos, string $paso, string $detalle, array $contexto = []): void
    {
        $pasos[] = [
            'momento' => now()->toDateTimeString(),
            'paso' => $paso,
            'detalle' => $detalle,
            'contexto' => $contexto,
        ];
    }

    /**
     * Para la vista de prueba: JSON del VPS sacar-ofertas sin volcar html_b64 entero.
     *
     * @param  array<string, mixed>|null  $decoded
     * @return array<string, mixed>|null
     */
    private function sanitizarJsonVpsSacarOfertasParaLog(?array $decoded): ?array
    {
        if ($decoded === null) {
            return null;
        }
        $out = $decoded;
        if (isset($out['html_b64']) && is_string($out['html_b64'])) {
            $len = strlen($out['html_b64']);
            $raw = base64_decode($out['html_b64'], true);
            $htmlLen = is_string($raw) ? strlen($raw) : 0;
            $out['html_b64'] = '[omitido en log: base64 de ' . $len . ' caracteres → HTML ~' . $htmlLen . ' bytes]';
        }

        return $out;
    }

    /**
     * Actualiza el paso actual de la ejecución para saber en qué punto va el cron.
     */
    private function actualizarEjecucionPaso(EjecucionGlobal $ejecucion, string $paso, array $contexto = []): void
    {
        try {
            $log = is_array($ejecucion->log) ? $ejecucion->log : [];
            $pasos = isset($log['pasos']) && is_array($log['pasos']) ? $log['pasos'] : [];
            $detalle = $contexto['detalle'] ?? null;
            unset($contexto['detalle']);
            $contexto = $this->sanitizarLogUrls($contexto);

            $pasos[] = [
                'momento' => now()->toDateTimeString(),
                'paso'    => $paso,
                'detalle' => $detalle,
                'contexto' => $contexto,
            ];

            $log['estado'] = 'running';
            $log['paso_actual'] = $paso;
            $log['pasos'] = $pasos;

            $ejecucion->update(['log' => $log]);
        } catch (\Throwable $e) {
            // No bloquear el cron si falla solo el log de seguimiento.
        }
    }

    /**
     * Sustituye URLs en el log por valor cifrado encv1 (mismo algoritmo que columna neo) si hay secreto,
     * para poder descifrarlas en el panel con la misma clave. Sin secreto, solo hash corto sin dominio.
     */
    private function sanitizarLogUrls(mixed $data): mixed
    {
        if (is_array($data)) {
            $sanitizado = [];
            foreach ($data as $key => $value) {
                $keyStr = is_string($key) ? strtolower($key) : '';
                if (is_string($value) && $this->esClaveUrlEnLog($keyStr)) {
                    $sanitizado[$key] = $this->enmascararUrlParaLog($value);
                    continue;
                }
                $sanitizado[$key] = $this->sanitizarLogUrls($value);
            }
            return $sanitizado;
        }

        if (is_string($data)) {
            // Si por cualquier motivo se cuela una URL en texto libre, se enmascara también.
            return preg_replace_callback(
                '~https?://[^\s\'"<>()]+~i',
                fn ($m) => $this->enmascararUrlParaLog($m[0]),
                $data
            );
        }

        return $data;
    }

    private function esClaveUrlEnLog(string $key): bool
    {
        if ($key === '') {
            return false;
        }

        return str_contains($key, 'url') || str_contains($key, 'href');
    }

    private function enmascararUrlParaLog(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return $url;
        }

        $secret = (string) config('anti-scraping.neoobjetivo_url_secret', '');
        if ($secret !== '') {
            return Neo::encryptedNeoForLookup($url);
        }

        $hash = substr(hash('sha256', $url), 0, 12);

        return '[oculto hash=' . $hash . ']';
    }

    /**
     * Guarda error final del cron para identificar en qué paso reventó.
     */
    private function actualizarEjecucionError(EjecucionGlobal $ejecucion, \Throwable $e): void
    {
        try {
            $log = is_array($ejecucion->log) ? $ejecucion->log : [];
            $pasoActual = $log['paso_actual'] ?? 'desconocido';
            $pasos = isset($log['pasos']) && is_array($log['pasos']) ? $log['pasos'] : [];
            $pasos[] = [
                'momento' => now()->toDateTimeString(),
                'paso'    => 'error',
                'detalle' => 'Excepción no controlada',
                'contexto' => [
                    'paso_en_el_que_fallo' => $pasoActual,
                    'error' => $e->getMessage(),
                ],
            ];

            $log['estado'] = 'error';
            $log['paso_actual'] = 'error';
            $log['error'] = [
                'mensaje' => $e->getMessage(),
                'tipo'    => get_class($e),
            ];
            $log['pasos'] = $pasos;

            $ejecucion->update([
                'fin'          => now(),
                'total_errores' => (int) $ejecucion->total_errores + 1,
                'log'          => $log,
            ]);
        } catch (\Throwable $inner) {
            // Si incluso esto falla, evitamos cascada de errores.
        }
    }

    /**
     * Limpia ejecuciones antiguas del cron para evitar crecimiento indefinido.
     */
    private function eliminarEjecucionesAntiguas(string $nombreEjecucion): void
    {
        try {
            EjecucionGlobal::query()
                ->where('nombre', $nombreEjecucion)
                ->where('created_at', '<', now()->subDays(self::RETENCION_EJECUCIONES_DIAS))
                ->delete();
        } catch (\Throwable $e) {
            // No bloquear el cron por fallo en limpieza histórica.
        }
    }

    /**
     * Del HTML en base64 devuelto por el VPS, extrae los href de cada <a class="productOffers-listItemOfferCtaLeadout ...">.
     *
     * @return array<int, string>
     */
    private function extraerHrefsOfertasNeoComparador(string $htmlB64): array
    {
        $html = base64_decode($htmlB64, true);
        if ($html === false || $html === '') {
            return [];
        }

        $hrefs = [];
        $useErrors = libxml_use_internal_errors(true);
        try {
            $dom = new \DOMDocument();
            if (@$dom->loadHTML($html) === false) {
                return [];
            }
            $anchors = $dom->getElementsByTagName('a');
            foreach ($anchors as $a) {
                $class = $a->getAttribute('class');
                if (strpos($class, 'productOffers-listItemOfferCtaLeadout') === false) {
                    continue;
                }
                $href = trim($a->getAttribute('href'));
                if ($href !== '') {
                    $hrefs[] = $href;
                }
            }
        } finally {
            libxml_use_internal_errors($useErrors);
        }

        return $hrefs;
    }

    /**
     * Pasa la URL a absoluta (origen del comparador si es relativa) y deja solo offerKey y type en la query.
     */
    private function limpiarUrlNeoComparadorRelocate(string $href): string
    {
        $href = trim($href);
        if ($href === '') {
            return '';
        }
        if (!str_starts_with($href, 'http')) {
            $base = self::neoComparadorOrigenHttps();
            $href = $base . (str_starts_with($href, '/') ? '' : '/') . $href;
        }
        $parts = parse_url($href);
        if ($parts === false) {
            return $href;
        }
        $scheme = $parts['scheme'] ?? 'https';
        $host = $parts['host'] ?? self::neoComparadorHostWww();
        $path = $parts['path'] ?? '/';
        $query = $parts['query'] ?? '';
        parse_str($query, $params);
        $paramsLimpios = array_intersect_key($params, array_flip(['offerKey', 'type']));
        $queryLimpia = http_build_query($paramsLimpios, '', '&', \PHP_QUERY_RFC3986);
        $base = $scheme . '://' . $host . $path;
        return $queryLimpia !== '' ? $base . '?' . $queryLimpia : $base;
    }

    /**
     * Procesa una URL limpia: comprueba neo.neo, llama a /redireccion si no existe,
     * luego según final_url decide si guardar en neo (desde oferta o desde neoobjetivo).
     * Devuelve el array para redireccion con log_pasos y accion_final.
     */
    private function procesarUrlRedireccion(string $urlLimpia, Neoobjetivo $neoobjetivo, int $numeroUrl): array
    {
        $log = [];
        $log[] = ['paso' => count($log) + 1, 'texto' => "URL limpiada (#{$numeroUrl}):", 'valor' => $urlLimpia];

        $neoCifradaLookup = Neo::encryptedNeoForLookup($urlLimpia);
        if (Neo::where(function ($q) use ($urlLimpia, $neoCifradaLookup) {
            $q->where('neo', $urlLimpia)
              ->orWhere('neo', $neoCifradaLookup);
        })->exists()) {
            $log[] = ['paso' => count($log) + 1, 'texto' => '¿Existe en tabla neo (campo neo)?', 'decision' => 'Sí → No se hace petición a /redireccion'];
            return [
                'skipped'      => true,
                'reason'       => 'URL ya existe en neo.neo',
                'url_limpiada' => $urlLimpia,
                'log_pasos'    => $log,
                'accion_final' => 'Ninguna (URL ya estaba en neo)',
            ];
        }

        $log[] = ['paso' => count($log) + 1, 'texto' => '¿Existe en tabla neo (campo neo)?', 'decision' => 'No'];

        if (!self::NEO_CRON_REDIRECCION_HABILITADA) {
            Neo::create([
                'oferta_id'    => $neoobjetivo->oferta_id,
                'producto_id'  => $neoobjetivo->producto_id,
                'categoria_id' => $neoobjetivo->categoria_id,
                'tienda_id'    => $neoobjetivo->tienda_id,
                'url'          => '',
                'neo'          => $urlLimpia,
                'aniadida'     => 'no',
            ]);
            $log[] = ['paso' => count($log) + 1, 'texto' => '¿Redirección Neo comparador habilitada?', 'decision' => 'No'];
            $log[] = ['paso' => count($log) + 1, 'texto' => 'Acción', 'decision' => 'Insertado en neo sin url (vacía) y con datos del neoobjetivo; columna neo=URL limpiada'];
            return [
                'success'                       => true,
                'sin_llamada_redireccion_vps'   => true,
                'url_solicitada'                => $urlLimpia,
                'final_url'                     => null,
                'log_pasos'                     => $log,
                'accion_final'                  => 'Insertado en tabla neo sin redirección (url vacía, neo con URL limpiada)',
            ];
        }

        $respuesta = $this->llamarRedireccionVps($urlLimpia);

        $log[] = ['paso' => count($log) + 1, 'texto' => 'Respuesta VPS /redireccion', 'decision' => !empty($respuesta['success']) ? 'Éxito' : 'Error: ' . ($respuesta['error'] ?? 'desconocido')];

        if (empty($respuesta['success']) || empty($respuesta['final_url'])) {
            $errorMsg = $respuesta['error'] ?? '';
            $esSelenium = $errorMsg !== '' && stripos($errorMsg, 'selenium') !== false;
            $neoobjetivoId = $neoobjetivo->getKey();
            if (!$esSelenium && $neoobjetivoId !== null) {
                $this->crearAvisoInternoRedireccionNeo((int) $neoobjetivoId, $numeroUrl);
            }
            $respuesta['log_pasos'] = $log;
            $respuesta['accion_final'] = 'No se guardó nada (error o sin final_url)';
            return $respuesta;
        }

        $finalUrl = $respuesta['final_url'];
        $log[] = ['paso' => count($log) + 1, 'texto' => 'URL final recibida:', 'valor' => $finalUrl];

        $finalUrl = app(LimpiarUrlDeTiendas::class)->limpiar($finalUrl);
        $log[] = ['paso' => count($log) + 1, 'texto' => 'URL final (tras LimpiarUrlDeTiendas):', 'valor' => $finalUrl];

        $oferta = OfertaProducto::where('url', $finalUrl)->with('producto', 'tienda')->first();
        if ($oferta) {
            $log[] = ['paso' => count($log) + 1, 'texto' => '¿Existe en ofertas_producto.url?', 'decision' => 'Sí'];

            $neoExistente = Neo::where('url', $finalUrl)->first();
            if ($neoExistente) {
                // Existe en neo (campo url). Comparamos neo.neo con urlLimpia (la que mandamos al VPS en la segunda petición).
                if ((string) $neoExistente->neo === (string) $urlLimpia) {
                    // Igual: copiar a neo los campos (oferta_id, producto_id, categoria_id, tienda_id) que neoobjetivo tiene y neo no.
                    $actualizado = false;
                    if ($neoExistente->oferta_id === null && $neoobjetivo->oferta_id !== null) {
                        $neoExistente->oferta_id = $neoobjetivo->oferta_id;
                        $actualizado = true;
                    }
                    if ($neoExistente->producto_id === null && $neoobjetivo->producto_id !== null) {
                        $neoExistente->producto_id = $neoobjetivo->producto_id;
                        $actualizado = true;
                    }
                    if ($neoExistente->categoria_id === null && $neoobjetivo->categoria_id !== null) {
                        $neoExistente->categoria_id = $neoobjetivo->categoria_id;
                        $actualizado = true;
                    }
                    if ($neoExistente->tienda_id === null && $neoobjetivo->tienda_id !== null) {
                        $neoExistente->tienda_id = $neoobjetivo->tienda_id;
                        $actualizado = true;
                    }
                    if ($actualizado) {
                        $neoExistente->save();
                    }
                    $log[] = ['paso' => count($log) + 1, 'texto' => '¿Existe en neo.url?', 'decision' => 'Sí'];
                    $log[] = ['paso' => count($log) + 1, 'texto' => '¿neo.neo coincide con URL limpiada?', 'decision' => 'Sí → Copiados a neo los campos (oferta_id/producto_id/categoria_id/tienda_id) que neoobjetivo tenía y neo no'];
                    $log[] = ['paso' => count($log) + 1, 'texto' => 'Acción', 'decision' => $actualizado ? 'Actualizada fila neo (id=' . $neoExistente->id . ') con datos del neoobjetivo' : 'Sin cambios en neo (ya tenía todos los datos)'];
                    $respuesta['log_pasos'] = $log;
                    $respuesta['accion_final'] = 'Fila neo ya existía con misma neo; complementados datos desde neoobjetivo si faltaban';
                    return $respuesta;
                }

                // No igual: actualizar neo.neo = urlLimpia y copiar campos que neo no tiene y neoobjetivo sí.
                $neoExistente->neo = $urlLimpia;
                $actualizado = false;
                if ($neoExistente->oferta_id === null && $neoobjetivo->oferta_id !== null) {
                    $neoExistente->oferta_id = $neoobjetivo->oferta_id;
                    $actualizado = true;
                }
                if ($neoExistente->producto_id === null && $neoobjetivo->producto_id !== null) {
                    $neoExistente->producto_id = $neoobjetivo->producto_id;
                    $actualizado = true;
                }
                if ($neoExistente->categoria_id === null && $neoobjetivo->categoria_id !== null) {
                    $neoExistente->categoria_id = $neoobjetivo->categoria_id;
                    $actualizado = true;
                }
                if ($neoExistente->tienda_id === null && $neoobjetivo->tienda_id !== null) {
                    $neoExistente->tienda_id = $neoobjetivo->tienda_id;
                    $actualizado = true;
                }
                $neoExistente->save();
                $log[] = ['paso' => count($log) + 1, 'texto' => '¿Existe en neo.url?', 'decision' => 'Sí'];
                $log[] = ['paso' => count($log) + 1, 'texto' => '¿neo.neo coincide con URL limpiada?', 'decision' => 'No → Actualizado neo.neo a URL limpiada y copiados a neo los campos que neoobjetivo tenía y neo no'];
                $log[] = ['paso' => count($log) + 1, 'texto' => 'Acción', 'decision' => 'Actualizada fila neo (id=' . $neoExistente->id . '): neo.neo=' . $urlLimpia . ($actualizado ? '; datos complementados desde neoobjetivo' : '')];
                $respuesta['log_pasos'] = $log;
                $respuesta['accion_final'] = 'Actualizada fila existente en neo: columna neo a URL limpiada y datos del neoobjetivo donde neo estaba vacío';
                return $respuesta;
            }

            // No existe en neo: crear desde oferta como hasta ahora.
            Neo::create([
                'oferta_id'   => $oferta->id,
                'producto_id' => $oferta->producto_id,
                'categoria_id' => $oferta->producto ? $oferta->producto->categoria_id : null,
                'tienda_id'   => $oferta->tienda_id,
                'url'         => $finalUrl,
                'neo'         => $urlLimpia,
                'aniadida'    => 'si',
            ]);
            $log[] = ['paso' => count($log) + 1, 'texto' => '¿Existe en neo.url?', 'decision' => 'No'];
            $log[] = ['paso' => count($log) + 1, 'texto' => 'Acción', 'decision' => 'Guardado en neo con oferta_id=' . $oferta->id . ', producto_id=' . ($oferta->producto_id ?? 'null') . ', categoria_id=' . ($oferta->producto->categoria_id ?? 'null') . ', tienda_id=' . $oferta->tienda_id . ', aniadida=si'];
            $respuesta['log_pasos'] = $log;
            $respuesta['accion_final'] = 'Insertado en tabla neo (aniadida=si) desde oferta existente';
            return $respuesta;
        }

        $log[] = ['paso' => count($log) + 1, 'texto' => '¿Existe en ofertas_producto.url?', 'decision' => 'No'];

        if (UrlDescartada::where('url', $finalUrl)->exists()) {
            Neo::create([
                'oferta_id'   => null,
                'producto_id' => $neoobjetivo->producto_id,
                'categoria_id' => $neoobjetivo->categoria_id,
                'tienda_id'   => $neoobjetivo->tienda_id,
                'url'         => $finalUrl,
                'neo'         => $urlLimpia,
                'aniadida'    => 'si',
            ]);
            $log[] = ['paso' => count($log) + 1, 'texto' => '¿Existe en urls_descartadas.url?', 'decision' => 'Sí'];
            $log[] = ['paso' => count($log) + 1, 'texto' => 'Acción', 'decision' => 'Guardado en neo (URL descartada; aniadida=si) con producto_id/categoria_id/tienda_id del neoobjetivo'];
            $respuesta['log_pasos'] = $log;
            $respuesta['accion_final'] = 'Insertado en tabla neo (aniadida=si) desde url descartada';
            return $respuesta;
        }

        $log[] = ['paso' => count($log) + 1, 'texto' => '¿Existe en urls_descartadas.url?', 'decision' => 'No'];

        $neoExistente = Neo::where('url', $finalUrl)->first();
        if ($neoExistente) {
            $actualizado = false;
            $actualizadoNeo = false;
            if ($neoExistente->producto_id === null && $neoobjetivo->producto_id !== null) {
                $neoExistente->producto_id = $neoobjetivo->producto_id;
                $actualizado = true;
            }
            if ($neoExistente->categoria_id === null && $neoobjetivo->categoria_id !== null) {
                $neoExistente->categoria_id = $neoobjetivo->categoria_id;
                $actualizado = true;
            }
            if ((string) $neoExistente->neo !== (string) $urlLimpia) {
                $neoExistente->neo = $urlLimpia;
                $actualizadoNeo = true;
                $actualizado = true;
            }
            if ($actualizado) {
                $neoExistente->save();
            }
            $log[] = ['paso' => count($log) + 1, 'texto' => '¿Existe en tabla neo (campo url)?', 'decision' => 'Sí'];
            if ($actualizadoNeo) {
                $log[] = ['paso' => count($log) + 1, 'texto' => '¿Columna neo coincide con URL limpiada?', 'decision' => 'No → Actualizada columna neo a la URL limpiada actual'];
                $log[] = ['paso' => count($log) + 1, 'texto' => 'URL limpiada (ahora en neo.neo):', 'valor' => $urlLimpia];
            } else {
                $log[] = ['paso' => count($log) + 1, 'texto' => '¿Columna neo coincide con URL limpiada?', 'decision' => 'Sí'];
            }
            $accionTexto = $actualizado ? ($actualizadoNeo ? 'Actualizado producto_id/categoria_id desde neoobjetivo (fila id=' . $neoobjetivo->id . '); columna neo actualizada a la URL limpiada' : 'Actualizado producto_id/categoria_id desde neoobjetivo (fila id=' . $neoobjetivo->id . ')') : ($actualizadoNeo ? 'Columna neo actualizada a la URL limpiada' : 'Sin cambios (ya tenía producto_id y categoria_id)');
            $log[] = ['paso' => count($log) + 1, 'texto' => 'Acción', 'decision' => $accionTexto];
            $respuesta['log_pasos'] = $log;
            $respuesta['accion_final'] = $actualizado ? ($actualizadoNeo ? 'Actualizada fila existente en neo (producto_id/categoria_id y columna neo)' : 'Actualizada fila existente en neo con datos del neoobjetivo') : ($actualizadoNeo ? 'Actualizada columna neo a la URL limpiada' : 'Fila en neo ya tenía producto_id y categoria_id');
            return $respuesta;
        }

        Neo::create([
            'oferta_id'   => $neoobjetivo->oferta_id,
            'producto_id' => $neoobjetivo->producto_id,
            'categoria_id' => $neoobjetivo->categoria_id,
            'tienda_id'   => $neoobjetivo->tienda_id,
            'url'         => $finalUrl,
            'neo'         => $urlLimpia,
            'aniadida'    => 'no',
        ]);
        $log[] = ['paso' => count($log) + 1, 'texto' => '¿Existe en tabla neo (campo url)?', 'decision' => 'No'];
        $log[] = ['paso' => count($log) + 1, 'texto' => 'Acción', 'decision' => 'Insertado en neo con oferta_id/producto_id/categoria_id/tienda_id del neoobjetivo (id=' . $neoobjetivo->id . '), aniadida=no'];
        $respuesta['log_pasos'] = $log;
        $respuesta['accion_final'] = 'Insertado en tabla neo (aniadida=no) con datos del neoobjetivo';
        return $respuesta;
    }

    /**
     * Procesa una URL de producto extraída de categoría (rama categoría/tienda): limpia la URL, comprueba
     * neo/ofertas/urls_descartadas y crea o actualiza neo. No usa VPS ni campo neo.neo; solo datos del neoobjetivo.
     * aniadida = si cuando se crea desde oferta o url descartada, no cuando es URL nueva.
     *
     * @return array{skipped?: bool, reason?: string, url_final?: string, log_pasos: array, accion_final: string, success?: bool, error?: string}
     */
    private function procesarUrlCategoria(string $urlProducto, Neoobjetivo $neoobjetivo, int $numeroUrl): array
    {
        $log = [];
        $log[] = ['paso' => count($log) + 1, 'texto' => "URL producto (#{$numeroUrl}):", 'valor' => $urlProducto];

        $finalUrl = app(LimpiarUrlDeTiendas::class)->limpiar($urlProducto);
        $log[] = ['paso' => count($log) + 1, 'texto' => 'URL final (tras LimpiarUrlDeTiendas):', 'valor' => $finalUrl];

        if (Neo::where('url', $finalUrl)->exists()) {
            $log[] = ['paso' => count($log) + 1, 'texto' => '¿Existe en tabla neo (campo url)?', 'decision' => 'Sí → No se guarda'];
            return [
                'skipped'      => true,
                'reason'       => 'URL ya existe en neo.url',
                'url_final'    => $finalUrl,
                'log_pasos'    => $log,
                'accion_final' => 'Ninguna (URL ya estaba en neo)',
            ];
        }

        $log[] = ['paso' => count($log) + 1, 'texto' => '¿Existe en tabla neo (campo url)?', 'decision' => 'No'];

        $oferta = OfertaProducto::where('url', $finalUrl)->with('producto', 'tienda')->first();
        if ($oferta) {
            $log[] = ['paso' => count($log) + 1, 'texto' => '¿Existe en ofertas_producto.url?', 'decision' => 'Sí'];
            Neo::create([
                'oferta_id'    => $oferta->id,
                'producto_id'  => $oferta->producto_id,
                'categoria_id' => $oferta->producto ? $oferta->producto->categoria_id : null,
                'tienda_id'    => $oferta->tienda_id,
                'url'          => $finalUrl,
                'neo'          => null,
                'aniadida'     => 'si',
            ]);
            $log[] = ['paso' => count($log) + 1, 'texto' => 'Acción', 'decision' => 'Insertado en neo desde oferta (oferta_id=' . $oferta->id . '), aniadida=si'];
            return [
                'success'      => true,
                'url_final'    => $finalUrl,
                'log_pasos'    => $log,
                'accion_final' => 'Insertado en neo (aniadida=si) desde oferta existente',
            ];
        }

        $log[] = ['paso' => count($log) + 1, 'texto' => '¿Existe en ofertas_producto.url?', 'decision' => 'No'];

        if (UrlDescartada::where('url', $finalUrl)->exists()) {
            Neo::create([
                'oferta_id'   => null,
                'producto_id' => $neoobjetivo->producto_id,
                'categoria_id' => $neoobjetivo->categoria_id,
                'tienda_id'   => $neoobjetivo->tienda_id,
                'url'         => $finalUrl,
                'neo'         => null,
                'aniadida'    => 'si',
            ]);
            $log[] = ['paso' => count($log) + 1, 'texto' => '¿Existe en urls_descartadas.url?', 'decision' => 'Sí'];
            $log[] = ['paso' => count($log) + 1, 'texto' => 'Acción', 'decision' => 'Insertado en neo (URL descartada; aniadida=si) con datos del neoobjetivo'];
            return [
                'success'      => true,
                'url_final'    => $finalUrl,
                'log_pasos'    => $log,
                'accion_final' => 'Insertado en neo (aniadida=si) desde url descartada',
            ];
        }

        $log[] = ['paso' => count($log) + 1, 'texto' => '¿Existe en urls_descartadas.url?', 'decision' => 'No'];

        Neo::create([
            'oferta_id'   => $neoobjetivo->oferta_id,
            'producto_id' => $neoobjetivo->producto_id,
            'categoria_id' => $neoobjetivo->categoria_id,
            'tienda_id'   => $neoobjetivo->tienda_id,
            'url'         => $finalUrl,
            'neo'         => null,
            'aniadida'    => 'no',
        ]);
        $log[] = ['paso' => count($log) + 1, 'texto' => '¿Existe en neo (campo url)?', 'decision' => 'No'];
        $log[] = ['paso' => count($log) + 1, 'texto' => 'Acción', 'decision' => 'Insertado en neo con datos del neoobjetivo (aniadida=no)'];
        return [
            'success'      => true,
            'url_final'    => $finalUrl,
            'log_pasos'    => $log,
            'accion_final' => 'Insertado en neo (aniadida=no) con datos del neoobjetivo',
        ];
    }

    /**
     * user_id para crear un solo aviso interno (evitar N avisos por usuario que bloquea el cron).
     * Se usa el primer usuario por ID; en Avisos se puede ver con "Mostrar avisos de todos los usuarios".
     */
    private function userIdParaAvisosInternos(): int
    {
        return (int) (User::orderBy('id')->value('id') ?? 1);
    }

    /**
     * Comprueba si ya existe un aviso interno con el mismo texto (misma fila neoobjetivo + mismo error)
     * con fecha_aviso anterior a la actual y dentro de los últimos 7 días (para no duplicar avisos recientes).
     */
    private function existeAvisoInternoIgual(string $textoAviso): bool
    {
        return Aviso::where('avisoable_type', 'Interno')
            ->where('texto_aviso', $textoAviso)
            ->where('fecha_aviso', '<=', now())
            ->where('fecha_aviso', '>=', now()->subDays(7))
            ->exists();
    }

    /**
     * Crea un único aviso interno (user_id del primer usuario) cuando falla el cron o una petición al VPS
     * (timeout, excepción, etc.) para una fila de neoobjetivo. No crea si ya existe un aviso
     * igual (mismo neoobjetivo + mismo error) con fecha en los últimos 7 días.
     */
    private function crearAvisoCronNeoSiNoExiste(int $neoobjetivoId, string $errorMessage): void
    {
        $textoAviso = 'Ejecucion NEO neoobjetivo ' . $neoobjetivoId . ' error: ' . mb_substr($errorMessage, 0, 500);
        if ($this->existeAvisoInternoIgual($textoAviso)) {
            return;
        }

        $fechaAviso = now();
        Aviso::create([
            'texto_aviso'    => $textoAviso,
            'fecha_aviso'    => $fechaAviso,
            'user_id'        => $this->userIdParaAvisosInternos(),
            'avisoable_type' => 'Interno',
            'avisoable_id'   => 0,
            'oculto'         => false,
        ]);
    }

    /**
     * Crea un aviso interno cuando la petición a /redireccion no devuelve URL
     * (y el error no es por Selenium). Texto: "Ejecucion NEO id {neoobjetivoId} error en la peticion URL {numeroUrl}".
     * No crea si ya existe un aviso igual para la misma fila y misma URL (fecha en los últimos 7 días).
     */
    private function crearAvisoInternoRedireccionNeo(int $neoobjetivoId, int $numeroUrl): void
    {
        $textoAviso = "Ejecucion NEO id {$neoobjetivoId} error en la peticion URL {$numeroUrl}";
        if ($this->existeAvisoInternoIgual($textoAviso)) {
            return;
        }

        $fechaAviso = now();
        Aviso::create([
            'texto_aviso'    => $textoAviso,
            'fecha_aviso'    => $fechaAviso,
            'user_id'        => $this->userIdParaAvisosInternos(),
            'avisoable_type' => 'Interno',
            'avisoable_id'   => 0,
            'oculto'         => false,
        ]);
    }

    /**
     * Normaliza el nombre de la tienda para resolver la clase del controlador (misma lógica que ScrapingController).
     */
    private function normalizarNombreTienda(string $tienda): string
    {
        $normalizado = strtolower($tienda);
        $normalizado = Str::ascii($normalizado);
        $normalizado = preg_replace('/[^a-z0-9]/', '', $normalizado);

        return ucfirst($normalizado);
    }

    /**
     * Crea un aviso interno cuando no existe controlador para la tienda del neoobjetivo.
     * No crea si ya existe un aviso igual (mismo texto, fecha en los últimos 7 días).
     */
    private function crearAvisoControladorNoEncontradoNeoObjetivo(int $neoobjetivoId, string $tiendaNombre): void
    {
        $textoAviso = 'Neo objetivo id ' . $neoobjetivoId . ': no existe controlador para la tienda ' . $tiendaNombre;
        if ($this->existeAvisoInternoIgual($textoAviso)) {
            return;
        }

        $fechaAviso = now();
        Aviso::create([
            'texto_aviso'    => $textoAviso,
            'fecha_aviso'    => $fechaAviso,
            'user_id'        => $this->userIdParaAvisosInternos(),
            'avisoable_type' => 'Interno',
            'avisoable_id'   => 0,
            'oculto'         => false,
        ]);
    }

    /**
     * Crea un aviso interno cuando la URL del neoobjetivo está vacía (rama categoría/tienda).
     * No crea si ya existe un aviso igual (mismo texto, fecha en los últimos 7 días).
     */
    private function crearAvisoUrlVaciaNeoObjetivo(int $neoobjetivoId): void
    {
        $textoAviso = 'Neo objetivo id ' . $neoobjetivoId . ': URL vacía';
        if ($this->existeAvisoInternoIgual($textoAviso)) {
            return;
        }

        $fechaAviso = now();
        Aviso::create([
            'texto_aviso'    => $textoAviso,
            'fecha_aviso'    => $fechaAviso,
            'user_id'        => $this->userIdParaAvisosInternos(),
            'avisoable_type' => 'Interno',
            'avisoable_id'   => 0,
            'oculto'         => false,
        ]);
    }

    /**
     * Crea un aviso interno cuando la tienda no define tipo de listado o no es soportado (rama categoría/tienda).
     * No crea si ya existe un aviso igual (mismo texto, fecha en los últimos 7 días).
     *
     * @param int $neoobjetivoId
     * @param string $tiendaNombre
     * @param string|null $tipoListado valor devuelto por tipoListadoCategoria() (null o no sitemap/paginacion/mostrar_mas)
     */
    private function crearAvisoSinTipoListadoNeoObjetivo(int $neoobjetivoId, string $tiendaNombre, $tipoListado): void
    {
        $tipoStr = $tipoListado === null ? 'null' : (string) $tipoListado;
        $textoAviso = 'Neo objetivo id ' . $neoobjetivoId . ': la tienda ' . $tiendaNombre . ' no tiene tipo de listado soportado (tipoListadoCategoria=' . $tipoStr . ')';
        if ($this->existeAvisoInternoIgual($textoAviso)) {
            return;
        }

        $fechaAviso = now();
        Aviso::create([
            'texto_aviso'    => $textoAviso,
            'fecha_aviso'    => $fechaAviso,
            'user_id'        => $this->userIdParaAvisosInternos(),
            'avisoable_type' => 'Interno',
            'avisoable_id'   => 0,
            'oculto'         => false,
        ]);
    }

    /**
     * Detecta la tienda a partir del host de la URL (misma lógica que crear-masivo / analizarUrls).
     *
     * @param string $url URL de la categoría o producto
     * @param \Illuminate\Support\Collection|\App\Models\Tienda[] $todasLasTiendas
     * @return \App\Models\Tienda|null
     */
    private function detectarTiendaPorUrl(string $url, $todasLasTiendas)
    {
        try {
            $parsed = parse_url($url);
            $hostUser = strtolower($parsed['host'] ?? '');
            $hostUser = preg_replace('/^www\./', '', $hostUser);
            if (empty($hostUser)) {
                return null;
            }
            foreach ($todasLasTiendas as $t) {
                $tu = trim($t->url ?? '');
                if (empty($tu)) {
                    continue;
                }
                $tu = preg_replace('#^https?://#i', '', $tu);
                $tu = preg_replace('/^www\./i', '', strtolower($tu));
                $tu = preg_replace('#/.*$#', '', $tu);
                if ($tu && ($hostUser === $tu || str_ends_with($hostUser, '.' . $tu) || str_ends_with($tu, '.' . $hostUser))) {
                    return $t;
                }
            }
        } catch (\Throwable $e) {
            //
        }
        return null;
    }

    /**
     * Crea un aviso interno cuando se ejecuta el sacar productos de una categoría (rama categoría/tienda)
     * y no se encuentran productos. No crea si ya existe un aviso igual (mismo texto, fecha en los últimos 7 días).
     *
     * @param int $neoobjetivoId
     * @param string $tiendaNombre
     * @param string|null $categoriaNombre nombre de la categoría, o null para usar la URL
     * @param string $url URL de la categoría
     */
    private function crearAvisoNoProductosCategoriaNeoObjetivo(int $neoobjetivoId, string $tiendaNombre, ?string $categoriaNombre, string $url): void
    {
        $categoriaIdentificador = $categoriaNombre !== null && $categoriaNombre !== ''
            ? $categoriaNombre
            : mb_substr($url, 0, 500);
        $textoAviso = sprintf(
            'La tienda %s se ha ejecutado el sacar los productos de la categoría %s y no se han encontrado productos.',
            $tiendaNombre,
            $categoriaIdentificador
        );
        if ($this->existeAvisoInternoIgual($textoAviso)) {
            return;
        }

        $fechaAviso = now();
        Aviso::create([
            'texto_aviso'    => $textoAviso,
            'fecha_aviso'    => $fechaAviso,
            'user_id'        => $this->userIdParaAvisosInternos(),
            'avisoable_type' => 'Interno',
            'avisoable_id'   => 0,
            'oculto'         => false,
        ]);
    }

    /**
     * Crea un aviso interno cuando no se encuentra tienda para un neoobjetivo (rama categoría/tienda).
     * No crea si ya existe un aviso igual para ese neoobjetivo (mismo texto, fecha en los últimos 7 días).
     */
    private function crearAvisoTiendaNoEncontradaNeoObjetivo(int $neoobjetivoId): void
    {
        $textoAviso = 'Neo objetivo id ' . $neoobjetivoId . ': no se encontró tienda para la URL';
        if ($this->existeAvisoInternoIgual($textoAviso)) {
            return;
        }

        $fechaAviso = now();
        Aviso::create([
            'texto_aviso'    => $textoAviso,
            'fecha_aviso'    => $fechaAviso,
            'user_id'        => $this->userIdParaAvisosInternos(),
            'avisoable_type' => 'Interno',
            'avisoable_id'   => 0,
            'oculto'         => false,
        ]);
    }

    /**
     * POST al VPS /redireccion con una URL de relocator; devuelve la respuesta (final_url o error).
     * Entrada: JSON con clave "url" (relocators del comparador).
     *
     * @return array{success: bool, final_url?: string, error?: string, url_solicitada?: string, intentos?: int, proxies_intentados?: int, ips_intentadas?: array, detalle_por_intento?: array}
     */
    private function llamarRedireccionVps(string $urlRelocator): array
    {
        try {
            $resp = Http::timeout(self::NEO_CRON_TIMEOUT_VPS_SEGUNDOS)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept'       => 'application/json',
                ])
                ->post(self::VPS_REDIRECCION_URL, ['url' => $urlRelocator]);

            $body = $resp->body();
            $decoded = json_decode($body, true);
            if (is_array($decoded)) {
                return $decoded;
            }
            return [
                'success' => false,
                'error'   => 'Respuesta no JSON: ' . substr($body, 0, 200),
                'url_solicitada' => $urlRelocator,
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'error'   => $this->normalizarMensajeTimeout($e->getMessage()),
                'url_solicitada' => $urlRelocator,
            ];
        }
    }

    /**
     * Prueba manual: URL ya es un relocator del comparador → solo POST a /redireccion.
     */
    private function esUrlNeoComparadorRelocatorDirecta(string $url): bool
    {
        return stripos(strtolower($url), self::neoComparadorRelocatorMarcadorMinusculas()) !== false;
    }

    /**
     * Convierte errores de timeout en un mensaje legible para log/vista.
     */
    private function normalizarMensajeTimeout(string $mensaje): string
    {
        $m = strtolower($mensaje);
        if (str_contains($m, 'timed out') || str_contains($m, 'timeout') || str_contains($m, 'curl error 28')) {
            return 'Timeout superado (' . self::NEO_CRON_TIMEOUT_VPS_SEGUNDOS . 's) al contactar con el VPS';
        }

        return $mensaje;
    }
}
