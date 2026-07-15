<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Crons\CronNeoObjetivosController;
use App\Models\Neo;
use App\Models\Neoobjetivo;
use App\Models\OfertaProducto;
use App\Models\Tienda;
use App\Models\UrlDescartada;
use App\Services\ConsultarNeoCifrado;
use App\Services\LimpiarUrlDeTiendas;
use App\Services\NeoProgramaExternoRamaNeoUrlNormalizer;
use DateTimeInterface;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

/**
 * API exclusiva del programa externo del flujo Neo (rama Neo Idealo + categorías en tienda + sincronización).
 * No ejecuta el cron; delega la lógica de categoría/tienda en CronNeoObjetivosController::procesarHtmlCategoriaTiendaProgramaExterno.
 */
class NeoApiProgramaExternoController extends Controller
{
    /**
     * GET neoobjetivos de rama Neo con visitada &gt; {@see Neoobjetivo::DIAS_SIN_REVISAR} días (URLs en claro).
     *
     * Query: limite (1–500, default 200)
     */
    public function neoobjetivosRamaNeoPendientes(Request $request): JsonResponse
    {
        $limite = min(500, max(1, (int) $request->query('limite', 200)));

        $candidatas = Neoobjetivo::query()
            ->where('visitada', '<', Neoobjetivo::fechaLimiteVisitadaPendiente())
            ->whereNotNull('url_cipher')
            ->where('url_cipher', '!=', '')
            ->orderBy('visitada')
            ->limit(max($limite * 5, 500))
            ->get();

        $out = [];
        foreach ($candidatas as $n) {
            if (count($out) >= $limite) {
                break;
            }
            $url = trim((string) $n->url);
            if ($url === '' || strtolower($url) === 'no encontrado') {
                continue;
            }
            if (!NeoProgramaExternoRamaNeoUrlNormalizer::esRamaNeoObjetivoUrl($url)) {
                continue;
            }
            $out[] = [
                'id'            => $n->id,
                'url'           => $url,
                'visitada'      => $n->visitada?->format(DateTimeInterface::ATOM),
                'oferta_id'     => $n->oferta_id,
                'producto_id'   => $n->producto_id,
                'categoria_id'  => $n->categoria_id,
                'tienda_id'     => $n->tienda_id,
            ];
        }

        return response()->json([
            'ok'           => true,
            'total'        => count($out),
            'neoobjetivos' => $out,
        ]);
    }

    /**
     * GET neoobjetivos de categoría en tienda: tienda_id rellenado, visitada &gt; {@see Neoobjetivo::DIAS_SIN_REVISAR} días, no rama Neo (Idealo).
     *
     * Query: limite (1–500, default 200)
     */
    public function neoobjetivosCategoriaTiendaPendientes(Request $request): JsonResponse
    {
        $limite = min(500, max(1, (int) $request->query('limite', 200)));

        $candidatas = Neoobjetivo::query()
            ->with('tienda')
            ->where('visitada', '<', Neoobjetivo::fechaLimiteVisitadaPendiente())
            ->whereNotNull('tienda_id')
            ->whereNotNull('url_cipher')
            ->where('url_cipher', '!=', '')
            ->orderBy('visitada')
            ->limit(max($limite * 5, 500))
            ->get();

        $out = [];
        foreach ($candidatas as $n) {
            if (count($out) >= $limite) {
                break;
            }
            $url = trim((string) $n->url);
            if ($url === '' || strtolower($url) === 'no encontrado') {
                continue;
            }
            if (NeoProgramaExternoRamaNeoUrlNormalizer::esRamaNeoObjetivoUrl($url)) {
                continue;
            }
            $metaListado = $this->metadatosListadoCategoriaDesdeTienda($n->tienda);
            $out[] = array_merge([
                'id'            => $n->id,
                'url'           => $url,
                'visitada'      => $n->visitada?->format(DateTimeInterface::ATOM),
                'oferta_id'     => $n->oferta_id,
                'producto_id'   => $n->producto_id,
                'categoria_id'  => $n->categoria_id,
                'tienda_id'     => $n->tienda_id,
            ], $metaListado);
        }

        return response()->json([
            'ok'           => true,
            'total'        => count($out),
            'neoobjetivos' => $out,
        ]);
    }

    /**
     * POST: HTML de una página de categoría (obtenido en el navegador del programa externo).
     * Extrae URLs de producto y siguiente página con el controlador de la tienda; guarda en neo como el cron.
     *
     * Body: neoobjetivo_id (int), url_pagina (string), html (string),
     * opcional urls_producto_acumulado_antes (int ≥0): URLs de producto ya extraídas en páginas anteriores de la misma sesión (paginación en el programa externo), para alinear con el cron si toda la sesión queda en 0.
     */
    public function procesarHtmlCategoriaTienda(Request $request): JsonResponse
    {
        $data = $request->validate([
            'neoobjetivo_id' => ['required', 'integer', 'min:1'],
            'url_pagina'     => ['required', 'string', 'max:2048'],
            'html'           => ['required', 'string', 'max:12000000'],
            'urls_producto_acumulado_antes' => ['sometimes', 'integer', 'min:0', 'max:500000'],
        ]);

        $result = app(CronNeoObjetivosController::class)->procesarHtmlCategoriaTiendaProgramaExterno(
            (int) $data['neoobjetivo_id'],
            trim((string) $data['url_pagina']),
            (string) $data['html'],
            (int) ($data['urls_producto_acumulado_antes'] ?? 0),
        );

        $http = (int) ($result['http_code'] ?? 500);
        unset($result['http_code']);

        $ok = !empty($result['ok']);

        return response()->json($result, $ok ? 200 : $http);
    }

    /**
     * POST: al terminar una corrida del programa externo (rama Neo o categoría/tienda), guarda log y contadores
     * en ejecuciones_global con el mismo nombre que el cron, para el panel «Ejecuciones / Neo objetivos».
     *
     * Body: modo (rama_neo|categoria_tienda), lineas_log (array de strings, máx. 1500),
     * opcional estadisticas (fichas_procesadas, leadouts_encontrados, …), estado (ok|error),
     * inicio_unix (epoch segundos al iniciar la corrida), error_mensaje.
     */
    public function registrarEjecucionFin(Request $request): JsonResponse
    {
        // ConvertEmptyStringsToNull convierte "" en null y rompe lineas_log.* string; normalizar antes de validar.
        $rawLineas = $request->input('lineas_log');
        if (!is_array($rawLineas)) {
            return response()->json([
                'ok'    => false,
                'error' => 'lineas_log debe ser un array.',
            ], 422);
        }
        $lineasNorm = [];
        foreach ($rawLineas as $ln) {
            if ($ln === null) {
                $lineasNorm[] = '';
            } elseif (is_string($ln)) {
                $lineasNorm[] = $ln;
            } elseif (is_scalar($ln)) {
                $lineasNorm[] = (string) $ln;
            } else {
                $lineasNorm[] = json_encode($ln, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
            }
        }
        $request->merge(['lineas_log' => $lineasNorm]);

        $data = $request->validate([
            'modo'            => ['required', 'string', 'in:rama_neo,categoria_tienda'],
            'lineas_log'      => ['required', 'array', 'max:1500'],
            'lineas_log.*'    => ['string', 'max:1000'],
            'estadisticas'    => ['sometimes', 'array', 'max:30'],
            'estado'          => ['sometimes', 'string', 'in:ok,error'],
            'inicio_unix'     => ['sometimes', 'numeric', 'min:946684800', 'max:4102444800'],
            'error_mensaje'   => ['sometimes', 'string', 'max:2000'],
        ]);
        $estadisticasRaw = $request->input('estadisticas', []);
        $estadisticas = [];
        if (is_array($estadisticasRaw)) {
            $allowed = [
                'fichas_procesadas', 'leadouts_encontrados', 'urls_visitadas', 'urls_guardadas', 'urls_actualizadas', 'errores',
                'paginas_listado_visitadas', 'urls_producto_extraidas', 'urls_ya_en_neo', 'urls_insertadas_neo',
                'urls_actualizadas_neo_tienda', 'urls_error_procesamiento_neo',
            ];
            foreach ($allowed as $k) {
                if (!array_key_exists($k, $estadisticasRaw)) {
                    continue;
                }
                $v = $estadisticasRaw[$k];
                if (is_numeric($v)) {
                    $estadisticas[$k] = (int) $v;
                }
            }
        }

        $payload = [
            'modo'         => $data['modo'],
            'lineas_log'   => $data['lineas_log'],
            'estadisticas' => $estadisticas,
            'estado'       => $data['estado'] ?? 'ok',
            'inicio_unix'  => $data['inicio_unix'] ?? null,
            'error_mensaje' => $data['error_mensaje'] ?? null,
        ];

        $result = app(CronNeoObjetivosController::class)->registrarEjecucionProgramaExternoNeo($payload);

        $http = (int) ($result['http_code'] ?? 500);
        unset($result['http_code']);

        $ok = !empty($result['ok']);

        return response()->json($result, $ok ? 200 : $http);
    }

    /**
     * POST comprobar si URLs (leadout / sucias) ya tienen fila neo con neo.neo y URL destino rellena.
     *
     * Body: { "urls": ["..."] } (máx. 100)
     */
    public function comprobarExisteNeoNeo(Request $request): JsonResponse
    {
        $data = $request->validate([
            'urls'   => ['required', 'array', 'min:1', 'max:100'],
            'urls.*' => ['required', 'string', 'max:2048'],
        ]);

        $resultados = [];
        foreach ($data['urls'] as $urlRaw) {
            $limpia = NeoProgramaExternoRamaNeoUrlNormalizer::limpiarHrefRelocatorLeadout($urlRaw);
            if ($limpia === '') {
                $limpia = app(LimpiarUrlDeTiendas::class)->limpiar(trim((string) $urlRaw));
            }

            $neoLookup = $limpia !== '' ? Neo::encryptedNeoForLookup($limpia) : '';
            $fila = ($neoLookup !== '') ? Neo::where('neo_lookup', $neoLookup)->first() : null;

            $tieneUrlDestino = $fila !== null && trim((string) $fila->url) !== '';
            $respuestaSi = $fila !== null && $tieneUrlDestino;

            $resultados[] = [
                'url_enviada'               => $urlRaw,
                'url_neo_neo_normalizada' => $limpia,
                'existe_en_neo_neo'         => $fila !== null,
                'tiene_url_destino'         => $tieneUrlDestino,
                'respuesta_si'              => $respuestaSi,
                'neo_id'                    => $fila?->id,
            ];
        }

        return response()->json([
            'ok'         => true,
            'resultados' => $resultados,
        ]);
    }

    /**
     * POST sincronizar fila neo con datos del programa externo (id neoobjetivo + relocator + URL final).
     *
     * Body completo: { "neoobjetivo_id": 123, "neo_neo": "...", "neo_url": "..." }
     *
     * Solo marcar visitada (sin tocar tabla neo): { "neoobjetivo_id": 123, "marcar_solo_visitada": true }
     * Útil cuando la ficha no tiene leadouts o ningún flujo llegó a guardar destino en neo.
     * neoobjetivo_url se acepta como respaldo para versiones antiguas del programa externo.
     *
     * Si la sincronización completa termina con éxito (HTTP 200, ok true), actualiza neoobjetivo.visitada a now().
     */
    public function sincronizarNeo(Request $request): JsonResponse
    {
        if ($request->boolean('marcar_solo_visitada')) {
            $data = $request->validate([
                'neoobjetivo_id'  => ['nullable', 'integer', 'min:1'],
                'neoobjetivo_url' => ['required_without:neoobjetivo_id', 'nullable', 'string', 'max:2048'],
            ]);

            return $this->marcarSoloVisitadaNeoobjetivo(
                isset($data['neoobjetivo_id']) ? (int) $data['neoobjetivo_id'] : null,
                isset($data['neoobjetivo_url']) ? trim((string) $data['neoobjetivo_url']) : null
            );
        }

        $data = $request->validate([
            'neoobjetivo_id'  => ['nullable', 'integer', 'min:1'],
            'neoobjetivo_url' => ['required_without:neoobjetivo_id', 'nullable', 'string', 'max:2048'],
            'neo_neo'          => ['required', 'string', 'max:2048'],
            'neo_url'          => ['required', 'string', 'max:2048'],
        ]);

        $neoNeoLimpia = NeoProgramaExternoRamaNeoUrlNormalizer::limpiarHrefRelocatorLeadout($data['neo_neo']);
        $finalLimpia = app(LimpiarUrlDeTiendas::class)->limpiar($data['neo_url']);

        if ($neoNeoLimpia === '') {
            return response()->json([
                'ok'    => false,
                'error' => 'neo_neo quedó vacío tras normalizar.',
            ], 422);
        }
        if ($finalLimpia === '') {
            return response()->json([
                'ok'    => false,
                'error' => 'neo_url quedó vacía tras LimpiarUrlDeTiendas.',
            ], 422);
        }

        // URL destino no puede seguir apuntando a Idealo (p. ej. redirección no resuelta).
        if (str_contains(mb_strtolower($finalLimpia), 'idealo.es')) {
            return response()->json([
                'ok'                    => false,
                'error'                 => 'La URL destino (neo_url) tras limpiar sigue siendo de idealo.es; no se guarda en el sistema.',
                'neo_url_aceptada'      => false,
                'codigo_rechazo'        => 'neo_url_contiene_idealo',
                'neo_url_limpia'        => $finalLimpia,
            ], 422);
        }

        $neoobjetivo = $this->resolverNeoobjetivoProgramaExterno(
            isset($data['neoobjetivo_id']) ? (int) $data['neoobjetivo_id'] : null,
            isset($data['neoobjetivo_url']) ? trim((string) $data['neoobjetivo_url']) : null
        );
        if (!$neoobjetivo) {
            return response()->json([
                'ok'    => false,
                'error' => 'No se encontró neoobjetivo para el id o URL de listado indicada.',
            ], 422);
        }

        $neoNeoLookup = Neo::encryptedNeoForLookup($neoNeoLimpia);
        $finalLookup = Neo::encryptedNeoForLookup($finalLimpia);
        if ($neoNeoLookup === '' || $finalLookup === '') {
            return response()->json([
                'ok'    => false,
                'error' => 'No se pudo calcular lookup neo (revisa NEO_LOOKUP_KEY).',
            ], 503);
        }

        $rowNeo = Neo::where('neo_lookup', $neoNeoLookup)->first();
        $rowUrl = Neo::where('url_lookup', $finalLookup)->first();

        if ($rowNeo && $rowUrl && (int) $rowNeo->id !== (int) $rowUrl->id) {
            return response()->json([
                'ok'                 => false,
                'error'              => 'Conflicto: neo_neo y neo_url corresponden a filas neo distintas.',
                'neo_por_neo_neo_id' => $rowNeo->id,
                'neo_por_url_id'     => $rowUrl->id,
            ], 409);
        }

        if ($rowNeo) {
            $payload = $this->sincronizarCuandoExistePorNeoNeo($rowNeo, $neoobjetivo, $neoNeoLimpia, $finalLimpia);
            $payload['neoobjetivo_id'] = $neoobjetivo->id;
            if (!empty($payload['ok'])) {
                $this->marcarNeoobjetivoVisitadaAhora($neoobjetivo);
            }

            return response()->json($payload, !empty($payload['ok']) ? 200 : 422);
        }

        if ($rowUrl) {
            $payload = $this->sincronizarCuandoExisteSoloPorUrl($rowUrl, $neoobjetivo, $neoNeoLimpia, $finalLimpia);
            $payload['neoobjetivo_id'] = $neoobjetivo->id;
            $this->marcarNeoobjetivoVisitadaAhora($neoobjetivo);

            return response()->json($payload);
        }

        $payload = $this->sincronizarCrearFila($neoobjetivo, $neoNeoLimpia, $finalLimpia);
        $payload['neoobjetivo_id'] = $neoobjetivo->id;
        $this->marcarNeoobjetivoVisitadaAhora($neoobjetivo);

        return response()->json($payload);
    }

    /**
     * Marca el neoobjetivo como visitado en el instante actual (misma semántica que el cron al cerrar el flujo).
     */
    private function marcarNeoobjetivoVisitadaAhora(Neoobjetivo $neoobjetivo): void
    {
        $neoobjetivo->visitada = now();
        $neoobjetivo->save();
    }

    /**
     * Solo actualiza visitada del neoobjetivo (sin crear/actualizar filas en neo).
     */
    private function marcarSoloVisitadaNeoobjetivo(?int $neoobjetivoId, ?string $neoobjetivoUrl): JsonResponse
    {
        $neoobjetivo = $this->resolverNeoobjetivoProgramaExterno($neoobjetivoId, $neoobjetivoUrl);
        if (!$neoobjetivo) {
            return response()->json([
                'ok'    => false,
                'error' => 'No se encontró neoobjetivo para el id o URL de listado indicada.',
            ], 422);
        }

        $this->marcarNeoobjetivoVisitadaAhora($neoobjetivo);
        $neoobjetivo->refresh();

        return response()->json([
            'ok'             => true,
            'accion'         => 'solo_visitada',
            'neoobjetivo_id' => $neoobjetivo->id,
            'visitada'       => $neoobjetivo->visitada?->format(DateTimeInterface::ATOM),
        ]);
    }

    private function resolverNeoobjetivoProgramaExterno(?int $neoobjetivoId, ?string $neoobjetivoUrl): ?Neoobjetivo
    {
        if ($neoobjetivoId !== null && $neoobjetivoId > 0) {
            return Neoobjetivo::find($neoobjetivoId);
        }

        $neoobjetivoUrl = trim((string) $neoobjetivoUrl);
        if ($neoobjetivoUrl === '') {
            return null;
        }

        $lookupObj = app(ConsultarNeoCifrado::class)->hashLookup($neoobjetivoUrl);
        if ($lookupObj === '') {
            return null;
        }

        return Neoobjetivo::where('url_lookup', $lookupObj)->first();
    }

    /**
     * @return array{ok: bool, accion?: string, neo_id?: int, aniadida?: string, error?: string, url_destino_actual?: string}
     */
    private function sincronizarCuandoExistePorNeoNeo(
        Neo $row,
        Neoobjetivo $neoobjetivo,
        string $neoNeoLimpia,
        string $finalLimpia
    ): array {
        $urlDestinoVacia = trim((string) $row->url) === '';

        if ($urlDestinoVacia) {
            $row->url = $finalLimpia;
            $row->neo = $neoNeoLimpia;
            $this->complementarIdsNeoDesdeNeoobjetivo($row, $neoobjetivo);
            $row->aniadida = $this->resolverAniadida($finalLimpia);
            $row->save();

            return [
                'ok'       => true,
                'accion'   => 'actualizado_url_destino_vacia',
                'neo_id'   => $row->id,
                'aniadida' => $row->aniadida,
            ];
        }

        $urlActual = trim((string) $row->url);
        if ($urlActual !== $finalLimpia) {
            return [
                'ok'                  => false,
                'error'               => 'La fila neo ya tiene otra URL destino; no se sobrescribe.',
                'neo_id'              => $row->id,
                'url_destino_actual'  => $urlActual,
            ];
        }

        $row->neo = $neoNeoLimpia;
        $this->complementarIdsNeoDesdeNeoobjetivo($row, $neoobjetivo);
        $row->aniadida = $this->resolverAniadida($finalLimpia);
        $row->save();

        return [
            'ok'       => true,
            'accion'   => 'complementado_misma_url_destino',
            'neo_id'   => $row->id,
            'aniadida' => $row->aniadida,
        ];
    }

    /**
     * @return array{ok: bool, accion?: string, neo_id?: int, aniadida?: string}
     */
    private function sincronizarCuandoExisteSoloPorUrl(
        Neo $row,
        Neoobjetivo $neoobjetivo,
        string $neoNeoLimpia,
        string $finalLimpia
    ): array {
        $row->neo = $neoNeoLimpia;
        $this->complementarIdsNeoDesdeNeoobjetivo($row, $neoobjetivo);
        $row->aniadida = $this->resolverAniadida($finalLimpia);
        $row->save();

        return [
            'ok'       => true,
            'accion'   => 'actualizado_por_url_destino_existente',
            'neo_id'   => $row->id,
            'aniadida' => $row->aniadida,
        ];
    }

    /**
     * @return array{ok: bool, accion?: string, neo_id?: int, aniadida?: string}
     */
    private function sincronizarCrearFila(
        Neoobjetivo $neoobjetivo,
        string $neoNeoLimpia,
        string $finalLimpia
    ): array {
        $aniadida = $this->resolverAniadida($finalLimpia);
        $neo = Neo::create([
            'oferta_id'    => $neoobjetivo->oferta_id,
            'producto_id'  => $neoobjetivo->producto_id,
            'categoria_id' => $neoobjetivo->categoria_id,
            'tienda_id'    => $neoobjetivo->tienda_id,
            'url'          => $finalLimpia,
            'neo'          => $neoNeoLimpia,
            'aniadida'     => $aniadida,
        ]);

        return [
            'ok'       => true,
            'accion'   => 'creado',
            'neo_id'   => $neo->id,
            'aniadida' => $aniadida,
        ];
    }

    private function complementarIdsNeoDesdeNeoobjetivo(Neo $neo, Neoobjetivo $no): void
    {
        foreach (['oferta_id', 'producto_id', 'categoria_id', 'tienda_id'] as $campo) {
            if ($neo->{$campo} === null && $no->{$campo} !== null) {
                $neo->{$campo} = $no->{$campo};
            }
        }
    }

    private function resolverAniadida(string $finalUrlLimpia): string
    {
        $lookup = app(ConsultarNeoCifrado::class)->hashLookup($finalUrlLimpia);
        if ($lookup !== '' && OfertaProducto::where('url_lookup', $lookup)->exists()) {
            return 'si';
        }
        if (UrlDescartada::where('url', $finalUrlLimpia)->exists()) {
            return 'si';
        }

        return 'no';
    }

    /**
     * Misma normalización que CronNeoObjetivosController::normalizarNombreTienda().
     */
    private function normalizarNombreTiendaParaClaseScraping(string $tienda): string
    {
        $normalizado = strtolower($tienda);
        $normalizado = Str::ascii($normalizado);
        $normalizado = preg_replace('/[^a-z0-9]/', '', $normalizado);

        return ucfirst($normalizado);
    }

    /**
     * @return array{
     *     tipo_listado_categoria: 'paginacion'|'sitemap'|'mostrar_mas'|null,
     *     selector_cargar_mas: string|null,
     *     tienda_nombre: string|null
     * }
     */
    private function metadatosListadoCategoriaDesdeTienda(?Tienda $tienda): array
    {
        $base = [
            'tipo_listado_categoria' => null,
            'selector_cargar_mas'   => null,
            'tienda_nombre'         => null,
        ];
        if ($tienda === null) {
            return $base;
        }
        $nombreTienda = trim((string) $tienda->nombre);
        $base['tienda_nombre'] = $nombreTienda !== '' ? $nombreTienda : null;
        if ($nombreTienda === '') {
            return $base;
        }
        $nombreControlador = $this->normalizarNombreTiendaParaClaseScraping($nombreTienda);
        $clase = "App\\Http\\Controllers\\Scraping\\Tiendas\\{$nombreControlador}Controller";
        if (!class_exists($clase)) {
            return $base;
        }
        try {
            $ctrl = new $clase();
            $tipo = $ctrl->tipoListadoCategoria();
            if ($tipo === null || !in_array($tipo, ['sitemap', 'paginacion', 'mostrar_mas'], true)) {
                return $base;
            }
            $base['tipo_listado_categoria'] = $tipo;
            if ($tipo === 'mostrar_mas') {
                $sel = $ctrl->selectorCargarMasParaVps();
                $trim = $sel !== null ? trim($sel) : '';
                $base['selector_cargar_mas'] = $trim !== '' ? $trim : null;
            }
        } catch (\Throwable) {
            return $base;
        }

        return $base;
    }
}
