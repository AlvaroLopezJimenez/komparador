<?php

namespace App\Services;

use App\Models\Categoria;
use App\Models\OfertaProducto;
use App\Models\Tienda;
use App\Models\TiendaCategoriaApi;
use Illuminate\Support\Collection;

class TiendaScrapingConfigResolver
{
    public const API_CSV_AWIN = 'CSV-Awin';

    public const APIS_VALIDAS = [
        'miVpsHtml;1',
        'miVpsHtml;2',
        'miVpsHtml;3',
        'miVpsHtml;4',
        'miVpsHtml;5',
        'scrapingAnt',
        'brightData;false',
        'brightData;true',
        'aliexpressOpen',
        'amazonApi',
        'amazonProductInfo',
        'amazonPricing',
        'navegadorLocal',
        self::API_CSV_AWIN,
    ];

    public function resolverApi(Tienda $tienda, ?int $categoriaId): ?string
    {
        if ($categoriaId !== null) {
            $config = $this->obtenerConfig($tienda->id, $categoriaId);
            if ($config !== null && $config->api !== null && $config->api !== '') {
                return $config->api;
            }
        }

        return $tienda->api;
    }

    /**
     * API para obtener HTML de listados/sitemaps de categoría (productos nuevos en neo).
     * Usa api_productos de la tienda; si no está definida, hereda resolverApi().
     */
    public function resolverApiProductos(Tienda $tienda, ?int $categoriaId = null): ?string
    {
        $apiProductos = $tienda->api_productos;
        if ($apiProductos !== null && $apiProductos !== '') {
            return $apiProductos;
        }

        return $this->resolverApi($tienda, $categoriaId);
    }

    public function resolverScrapear(Tienda $tienda, ?int $categoriaId): string
    {
        if ($categoriaId !== null) {
            $config = $this->obtenerConfig($tienda->id, $categoriaId);
            if ($config !== null && $config->scrapear !== null && $config->scrapear !== '') {
                return $config->scrapear;
            }
        }

        return $tienda->scrapear ?? 'si';
    }

    public function resolverMostrar(Tienda $tienda, ?int $categoriaId): string
    {
        if ($categoriaId !== null) {
            $config = $this->obtenerConfig($tienda->id, $categoriaId);
            if ($config !== null && $config->mostrar !== null && $config->mostrar !== '') {
                return $config->mostrar;
            }
        }

        return $tienda->mostrar_tienda ?? 'si';
    }

    /**
     * Scrapear efectivo respetando el bloqueo global de la tienda (si tienda.scrapear=no, no hay override por categoría).
     */
    public function resolverScrapearFinal(Tienda $tienda, ?int $categoriaId): string
    {
        if (($tienda->scrapear ?? 'si') === 'no') {
            return 'no';
        }

        return $this->resolverScrapear($tienda, $categoriaId);
    }

    /**
     * Mostrar efectivo respetando el bloqueo global de la tienda (si tienda.mostrar_tienda=no, no hay override por categoría).
     */
    public function resolverMostrarFinal(Tienda $tienda, ?int $categoriaId): string
    {
        if (($tienda->mostrar_tienda ?? 'si') === 'no') {
            return 'no';
        }

        return $this->resolverMostrar($tienda, $categoriaId);
    }

    /**
     * Subconsulta SQL: scrapear efectivo (categoría si existe fila, si no tienda).
     */
    public static function sqlScrapearEfectivo(): string
    {
        return "(
            SELECT COALESCE(NULLIF(tca.scrapear, ''), t.scrapear)
            FROM productos p
            INNER JOIN tiendas t ON t.id = ofertas_producto.tienda_id
            LEFT JOIN tienda_categoria_api tca
                ON tca.tienda_id = ofertas_producto.tienda_id
                AND tca.categoria_id = p.categoria_id
            WHERE p.id = ofertas_producto.producto_id
            LIMIT 1
        )";
    }

    /**
     * Subconsulta SQL: mostrar efectivo en comparador (categoría si existe fila, si no tienda).
     */
    public static function sqlMostrarEfectivo(): string
    {
        return "(
            SELECT COALESCE(NULLIF(tca.mostrar, ''), t.mostrar_tienda)
            FROM productos p
            INNER JOIN tiendas t ON t.id = ofertas_producto.tienda_id
            LEFT JOIN tienda_categoria_api tca
                ON tca.tienda_id = ofertas_producto.tienda_id
                AND tca.categoria_id = p.categoria_id
            WHERE p.id = ofertas_producto.producto_id
            LIMIT 1
        )";
    }

    public function resolverFrecuenciaMinima(Tienda $tienda, ?int $categoriaId): int
    {
        if ($categoriaId !== null) {
            $config = $this->obtenerConfig($tienda->id, $categoriaId);
            if ($config?->frecuencia_minima_minutos !== null) {
                return (int) $config->frecuencia_minima_minutos;
            }
        }

        return (int) ($tienda->frecuencia_minima_minutos ?? 1440);
    }

    public function resolverFrecuenciaMaxima(Tienda $tienda, ?int $categoriaId): int
    {
        if ($categoriaId !== null) {
            $config = $this->obtenerConfig($tienda->id, $categoriaId);
            if ($config?->frecuencia_maxima_minutos !== null) {
                return (int) $config->frecuencia_maxima_minutos;
            }
        }

        return (int) ($tienda->frecuencia_maxima_minutos ?? 10080);
    }

    /**
     * Frecuencia inicial sugerida al crear una oferta: mín de categoría si está configurado, si no mín de tienda.
     */
    public function resolverFrecuenciaInicialOferta(Tienda $tienda, ?int $categoriaId): int
    {
        if ($categoriaId !== null) {
            $config = $this->obtenerConfig($tienda->id, $categoriaId);
            if ($config?->frecuencia_minima_minutos !== null) {
                return (int) $config->frecuencia_minima_minutos;
            }
        }

        return (int) ($tienda->frecuencia_minima_minutos ?? 1440);
    }

    public function obtenerConfig(?int $tiendaId, int $categoriaId): ?TiendaCategoriaApi
    {
        if ($tiendaId === null) {
            return null;
        }

        return TiendaCategoriaApi::where('tienda_id', $tiendaId)
            ->where('categoria_id', $categoriaId)
            ->first();
    }

    /**
     * Categorías con ofertas directas en la tienda sin fila de configuración de API.
     *
     * @return Collection<int, Categoria>
     */
    public function categoriasConOfertasSinApiConfigurada(Tienda $tienda): Collection
    {
        $categoriasConOfertas = OfertaProducto::query()
            ->where('tienda_id', $tienda->id)
            ->join('productos', 'ofertas_producto.producto_id', '=', 'productos.id')
            ->whereNotNull('productos.categoria_id')
            ->selectRaw('productos.categoria_id as cat_id')
            ->distinct()
            ->pluck('cat_id');

        if ($categoriasConOfertas->isEmpty()) {
            return collect();
        }

        $categoriasConfiguradas = TiendaCategoriaApi::where('tienda_id', $tienda->id)
            ->whereIn('categoria_id', $categoriasConOfertas)
            ->whereNotNull('api')
            ->where('api', '!=', '')
            ->pluck('categoria_id');

        $sinConfig = $categoriasConOfertas->diff($categoriasConfiguradas)->values();

        if ($sinConfig->isEmpty()) {
            return collect();
        }

        return Categoria::whereIn('id', $sinConfig)->orderBy('nombre')->get();
    }

    /**
     * Ofertas activas cuya categoría no tiene API configurada (tienda con scrapear=si).
     *
     * @return list<array{tienda: string, categoria: string, ofertas: int}>
     */
    public function resumenOfertasActivasSinApiPorCategoria(): array
    {
        $filas = OfertaProducto::query()
            ->join('tiendas', 'ofertas_producto.tienda_id', '=', 'tiendas.id')
            ->join('productos', 'ofertas_producto.producto_id', '=', 'productos.id')
            ->join('categorias', 'productos.categoria_id', '=', 'categorias.id')
            ->leftJoin('tienda_categoria_api as tcs', function ($join) {
                $join->on('tcs.tienda_id', '=', 'ofertas_producto.tienda_id')
                    ->on('tcs.categoria_id', '=', 'productos.categoria_id');
            })
            ->where('ofertas_producto.mostrar', 'si')
            ->where('tiendas.scrapear', 'si')
            ->whereNotNull('productos.categoria_id')
            ->where(function ($q) {
                $q->whereNull('tcs.id')
                    ->orWhereNull('tcs.api')
                    ->orWhere('tcs.api', '=', '');
            })
            ->selectRaw('tiendas.nombre as tienda_nombre, categorias.nombre as categoria_nombre, COUNT(*) as total')
            ->groupBy('tiendas.nombre', 'categorias.nombre')
            ->orderBy('tiendas.nombre')
            ->orderBy('categorias.nombre')
            ->get();

        return $filas->map(fn ($f) => [
            'tienda'    => $f->tienda_nombre,
            'categoria' => $f->categoria_nombre,
            'ofertas'   => (int) $f->total,
        ])->all();
    }

    public function resolverApiParaOferta(OfertaProducto $oferta): ?string
    {
        $oferta->loadMissing(['tienda', 'producto']);
        $tienda = $oferta->tienda;
        if ($tienda === null) {
            return null;
        }

        $categoriaId = $oferta->producto?->categoria_id;

        return $this->resolverApi($tienda, $categoriaId !== null ? (int) $categoriaId : null);
    }

    public static function reglaValidacionApi(): string
    {
        return 'in:' . implode(',', self::APIS_VALIDAS);
    }

    public static function apiBase(?string $api): ?string
    {
        if ($api === null || $api === '') {
            return null;
        }

        return explode(';', $api, 2)[0];
    }

    public static function esApiVps(?string $api): bool
    {
        return self::apiBase($api) === 'miVpsHtml';
    }

    /**
     * API configurada solo en la categoría (sin heredar la de tienda).
     */
    public function resolverApiSoloCategoria(Tienda $tienda, ?int $categoriaId): ?string
    {
        if ($categoriaId === null) {
            return null;
        }

        $config = $this->obtenerConfig($tienda->id, $categoriaId);
        if ($config === null || $config->api === null || $config->api === '') {
            return null;
        }

        return $config->api;
    }

    /**
     * Metadatos para el badge de API (2 letras + color), como en diagnóstico scraping.
     *
     * @return array{label: string, icon_bg: string, title: string, base: ?string}
     */
    public static function metaIconoApi(?string $api, bool $categoriaSinApiAsignada = false): array
    {
        if ($categoriaSinApiAsignada) {
            return [
                'label'       => '—',
                'icon_bg'     => 'bg-gray-400',
                'pulse_color' => '#9ca3af',
                'pulse_glow'  => '#d1d5db',
                'nombre'      => 'Sin API asignada',
                'title'       => 'Categoría con ofertas sin API asignada',
                'base'        => null,
            ];
        }

        if ($api === null || $api === '') {
            return [
                'label'       => '?',
                'icon_bg'     => 'bg-gray-400',
                'pulse_color' => '#9ca3af',
                'pulse_glow'  => '#d1d5db',
                'nombre'      => 'Sin API',
                'title'       => 'Sin API',
                'base'        => null,
            ];
        }

        $base = self::apiBase($api);
        $colores = [
            'miVpsHtml'         => ['bg' => 'bg-blue-600',   'pulse' => '#3b82f6', 'glow' => '#60a5fa'],
            'scrapingAnt'       => ['bg' => 'bg-green-600',  'pulse' => '#22c55e', 'glow' => '#4ade80'],
            'brightData'        => ['bg' => 'bg-purple-600', 'pulse' => '#a855f7', 'glow' => '#c084fc'],
            'aliexpressOpen'    => ['bg' => 'bg-amber-600',  'pulse' => '#f59e0b', 'glow' => '#fbbf24'],
            'amazonApi'         => ['bg' => 'bg-rose-600',   'pulse' => '#f43f5e', 'glow' => '#fb7185'],
            'amazonProductInfo' => ['bg' => 'bg-indigo-600', 'pulse' => '#6366f1', 'glow' => '#818cf8'],
            'amazonPricing'     => ['bg' => 'bg-cyan-600',   'pulse' => '#06b6d4', 'glow' => '#22d3ee'],
            'navegadorLocal'    => ['bg' => 'bg-teal-600',   'pulse' => '#14b8a6', 'glow' => '#2dd4bf'],
            self::API_CSV_AWIN  => ['bg' => 'bg-orange-600', 'pulse' => '#f97316', 'glow' => '#fb923c'],
        ];
        $nombres = [
            'miVpsHtml'         => 'Mi VPS HTML',
            'scrapingAnt'       => 'ScrapingAnt',
            'brightData'        => 'Bright Data',
            'aliexpressOpen'    => 'AliExpress Open',
            'amazonApi'         => 'Amazon API',
            'amazonProductInfo' => 'Amazon Product Info',
            'amazonPricing'     => 'Amazon Pricing',
            'navegadorLocal'    => 'Navegador local',
            self::API_CSV_AWIN  => 'CSV Awin',
        ];
        $palette = $colores[$base] ?? ['bg' => 'bg-gray-600', 'pulse' => '#64748b', 'glow' => '#94a3b8'];

        return [
            'label'       => strtoupper(substr($base, 0, 2)),
            'icon_bg'     => $palette['bg'],
            'pulse_color' => $palette['pulse'],
            'pulse_glow'  => $palette['glow'],
            'nombre'      => $nombres[$base] ?? $base,
            'title'       => $api,
            'base'        => $base,
        ];
    }

    /**
     * Resumen por tienda para el listado admin (categorías con ofertas).
     *
     * @param  Collection<int, Tienda>  $tiendas
     * @return array<int, array{
     *     cat_mos: array{si: int, total: int},
     *     cat_scraping: array{si: int, total: int},
     *     api_icon: array{label: string, icon_bg: string, title: string, base: ?string},
     *     cat_api: list<array{base: string, count: int, icon: array}>,
     *     cat_sin_api: int
     * }>
     */
    public function resumenIndexPorTiendas(Collection $tiendas): array
    {
        if ($tiendas->isEmpty()) {
            return [];
        }

        $tiendaIds = $tiendas->pluck('id');

        $categoriasPorTienda = OfertaProducto::query()
            ->whereIn('tienda_id', $tiendaIds)
            ->join('productos', 'ofertas_producto.producto_id', '=', 'productos.id')
            ->whereNotNull('productos.categoria_id')
            ->select('ofertas_producto.tienda_id', 'productos.categoria_id')
            ->distinct()
            ->get()
            ->groupBy('tienda_id')
            ->map(fn ($grupo) => $grupo->pluck('categoria_id')->map(fn ($id) => (int) $id)->unique()->values());

        $configsPorTienda = TiendaCategoriaApi::whereIn('tienda_id', $tiendaIds)
            ->get()
            ->groupBy('tienda_id')
            ->map(fn ($grupo) => $grupo->keyBy('categoria_id'));

        $resumenes = [];

        foreach ($tiendas as $tienda) {
            $catIds = $categoriasPorTienda->get($tienda->id, collect());
            $configsTienda = $configsPorTienda->get($tienda->id, collect());

            $mosSi = 0;
            $scrapeSi = 0;
            $apiPorBase = [];
            $sinApiSenalada = 0;
            $total = $catIds->count();

            foreach ($catIds as $catId) {
                $config = $configsTienda->get($catId);

                if ($this->resolverMostrarConConfig($tienda, $config) === 'si') {
                    $mosSi++;
                }

                if ($this->resolverScrapearConConfig($tienda, $config) === 'si') {
                    $scrapeSi++;
                }

                $apiExplicita = $config !== null && $config->api !== null && $config->api !== '';
                if (! $apiExplicita) {
                    $sinApiSenalada++;
                }

                $apiEfectiva = $apiExplicita ? $config->api : $tienda->api;
                $base = self::apiBase($apiEfectiva);
                if ($base !== null) {
                    $apiPorBase[$base] = ($apiPorBase[$base] ?? 0) + 1;
                }
            }

            arsort($apiPorBase);

            $resumenes[$tienda->id] = [
                'cat_mos'      => ['si' => $mosSi, 'total' => $total],
                'cat_scraping' => ['si' => $scrapeSi, 'total' => $total],
                'api_icon'     => self::metaIconoApi($tienda->api),
                'cat_api'      => collect($apiPorBase)->map(fn ($count, $base) => [
                    'base'  => $base,
                    'count' => $count,
                    'icon'  => self::metaIconoApi($base),
                ])->values()->all(),
                'cat_sin_api'  => $sinApiSenalada,
            ];
        }

        return $resumenes;
    }

    private function resolverScrapearConConfig(Tienda $tienda, ?TiendaCategoriaApi $config): string
    {
        if ($config !== null && $config->scrapear !== null && $config->scrapear !== '') {
            return $config->scrapear;
        }

        return $tienda->scrapear ?? 'si';
    }

    private function resolverMostrarConConfig(Tienda $tienda, ?TiendaCategoriaApi $config): string
    {
        if ($config !== null && $config->mostrar !== null && $config->mostrar !== '') {
            return $config->mostrar;
        }

        return $tienda->mostrar_tienda ?? 'si';
    }

    /**
     * Formato JSON para el cliente (diagnóstico / listado tiendas).
     *
     * @param  array{cat_mos: array, cat_scraping: array, cat_api: list, cat_sin_api: int, api_icon?: array}  $resumen
     * @return array<string, mixed>
     */
    public function serializarResumenParaCliente(array $resumen): array
    {
        return [
            'cat_mos' => $resumen['cat_mos'],
            'cat_scraping' => $resumen['cat_scraping'],
            'cat_sin_api' => $resumen['cat_sin_api'] ?? 0,
            'cat_api' => collect($resumen['cat_api'] ?? [])->map(fn ($fila) => [
                'base'  => $fila['base'],
                'count' => $fila['count'],
                'icon'  => $fila['icon'] ?? self::metaIconoApi($fila['base']),
            ])->values()->all(),
            'apis_bases' => collect($resumen['cat_api'] ?? [])->pluck('base')->values()->all(),
        ];
    }

    /**
     * @param  Collection<int, int|string>  $catIdsConOfertas
     * @return Collection<int, int>
     */
    private function expandirIdsConAncestros(Collection $catIdsConOfertas): Collection
    {
        $ids = $catIdsConOfertas->map(fn ($id) => (int) $id)->unique()->values();
        $pendientes = $ids->all();

        while ($pendientes !== []) {
            $parentIds = Categoria::query()
                ->whereIn('id', $pendientes)
                ->whereNotNull('parent_id')
                ->pluck('parent_id')
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values();

            $nuevos = $parentIds->diff($ids)->values();
            if ($nuevos->isEmpty()) {
                break;
            }
            $ids = $ids->merge($nuevos)->unique()->values();
            $pendientes = $nuevos->all();
        }

        return $ids;
    }

    /**
     * Datos para el diagrama de flujo scrapear/mostrar en el formulario de tienda.
     *
     * @param  Collection<int, int|string>  $categoriasConOfertas
     * @param  Collection<int, TiendaCategoriaApi>  $scrapingPorCategoria
     * @param  array<int|string, int>  $conteoDirectoOfertas
     * @param  array<int|string, int>  $conteoTotalOfertas
     * @return array<string, mixed>
     */
    public function construirFlujoParaTienda(
        Tienda $tienda,
        Collection $categoriasConOfertas,
        Collection $scrapingPorCategoria,
        array $conteoDirectoOfertas = [],
        array $conteoTotalOfertas = []
    ): array {
        $tiendaScrapear = $tienda->scrapear ?? 'si';
        $tiendaMostrar = $tienda->mostrar_tienda ?? 'si';
        $catIds = $categoriasConOfertas->map(fn ($id) => (int) $id)->unique()->values();
        $idsExpandidos = $this->expandirIdsConAncestros($catIds);

        $categoriasDb = $idsExpandidos->isEmpty()
            ? collect()
            : Categoria::whereIn('id', $idsExpandidos)->get(['id', 'nombre', 'parent_id'])->keyBy('id');

        $itemPorId = [];
        foreach ($idsExpandidos as $catId) {
            $catDb = $categoriasDb->get($catId);
            if ($catDb === null) {
                continue;
            }

            $config = $scrapingPorCategoria->get($catId);
            $scrapearEfectivo = $this->resolverScrapear($tienda, $catId);
            $mostrarEfectivo = $this->resolverMostrar($tienda, $catId);
            $scrapearExplicito = $config !== null && $config->scrapear !== null && $config->scrapear !== '';
            $mostrarExplicito = $config !== null && $config->mostrar !== null && $config->mostrar !== '';
            $scrapearFinal = $tiendaScrapear === 'no' ? 'no' : $scrapearEfectivo;
            $mostrarFinal = $tiendaMostrar === 'no' ? 'no' : $mostrarEfectivo;
            $ofertas = (int) ($conteoDirectoOfertas[$catId] ?? 0);
            $ofertasTotal = (int) ($conteoTotalOfertas[$catId] ?? $ofertas);
            $parentId = $catDb->parent_id ? (int) $catDb->parent_id : null;
            if ($parentId !== null && ! $idsExpandidos->contains($parentId)) {
                $parentId = null;
            }

            $apiEfectiva = $this->resolverApi($tienda, $catId);

            $itemPorId[$catId] = [
                'id' => $catId,
                'nombre' => (string) $catDb->nombre,
                'parent_id' => $parentId,
                'con_ofertas' => $catIds->contains($catId),
                'ofertas' => $ofertas,
                'ofertas_total' => $ofertasTotal,
                'scrapear' => $scrapearEfectivo,
                'mostrar' => $mostrarEfectivo,
                'scrapear_final' => $scrapearFinal,
                'mostrar_final' => $mostrarFinal,
                'scrapear_explicito' => $scrapearExplicito,
                'mostrar_explicito' => $mostrarExplicito,
                'api' => $apiEfectiva,
                'api_base' => self::apiBase($apiEfectiva),
                'api_icon' => self::metaIconoApi($apiEfectiva),
            ];
        }

        $categorias = array_values($itemPorId);
        $statsScrapear = [
            'total' => 0,
            'activas' => 0,
            'bloqueadas' => 0,
            'override_activas' => 0,
            'override_bloqueadas' => 0,
            'heredadas_activas' => 0,
            'heredadas_bloqueadas' => 0,
        ];
        $statsMostrar = [
            'total' => 0,
            'activas' => 0,
            'bloqueadas' => 0,
            'override_activas' => 0,
            'override_bloqueadas' => 0,
            'heredadas_activas' => 0,
            'heredadas_bloqueadas' => 0,
        ];
        $listasScrapearActivas = [];
        $listasScrapearBloqueadas = [];
        $listasMostrarActivas = [];
        $listasMostrarBloqueadas = [];

        foreach ($catIds as $catId) {
            $item = $itemPorId[$catId] ?? null;
            if ($item === null) {
                continue;
            }

            $scrapearFinal = $item['scrapear_final'];
            $mostrarFinal = $item['mostrar_final'];
            $scrapearExplicito = $item['scrapear_explicito'];
            $mostrarExplicito = $item['mostrar_explicito'];
            $scrapearEfectivo = $item['scrapear'];
            $mostrarEfectivo = $item['mostrar'];

            $statsScrapear['total']++;
            $statsMostrar['total']++;

            if ($scrapearFinal === 'si') {
                $statsScrapear['activas']++;
                if ($scrapearExplicito) {
                    $statsScrapear['override_activas']++;
                } else {
                    $statsScrapear['heredadas_activas']++;
                }
                $listasScrapearActivas[] = $item;
            } else {
                $statsScrapear['bloqueadas']++;
                if ($tiendaScrapear === 'no') {
                    // bloqueo por tienda: cuenta como heredada bloqueada si no hay override explícito no
                } elseif ($scrapearExplicito && $scrapearEfectivo === 'no') {
                    $statsScrapear['override_bloqueadas']++;
                } else {
                    $statsScrapear['heredadas_bloqueadas']++;
                }
                $listasScrapearBloqueadas[] = $item;
            }

            if ($mostrarFinal === 'si') {
                $statsMostrar['activas']++;
                if ($mostrarExplicito) {
                    $statsMostrar['override_activas']++;
                } else {
                    $statsMostrar['heredadas_activas']++;
                }
                $listasMostrarActivas[] = $item;
            } else {
                $statsMostrar['bloqueadas']++;
                if ($tiendaMostrar === 'no') {
                    //
                } elseif ($mostrarExplicito && $mostrarEfectivo === 'no') {
                    $statsMostrar['override_bloqueadas']++;
                } else {
                    $statsMostrar['heredadas_bloqueadas']++;
                }
                $listasMostrarBloqueadas[] = $item;
            }
        }

        $ordenar = fn (array $a, array $b) => ($b['ofertas'] <=> $a['ofertas']) ?: strcmp($a['nombre'], $b['nombre']);
        usort($listasScrapearActivas, $ordenar);
        usort($listasScrapearBloqueadas, $ordenar);
        usort($listasMostrarActivas, $ordenar);
        usort($listasMostrarBloqueadas, $ordenar);

        return [
            'tienda' => [
                'nombre' => $tienda->nombre,
                'scrapear' => $tiendaScrapear,
                'mostrar' => $tiendaMostrar,
                'api' => self::apiBase($tienda->api),
                'api_icon' => self::metaIconoApi($tienda->api),
            ],
            'categorias' => $categorias,
            'stats' => [
                'scrapear' => $statsScrapear,
                'mostrar' => $statsMostrar,
            ],
            'listas' => [
                'scrapear_activas' => array_slice($listasScrapearActivas, 0, 12),
                'scrapear_bloqueadas' => array_slice($listasScrapearBloqueadas, 0, 12),
                'mostrar_activas' => array_slice($listasMostrarActivas, 0, 12),
                'mostrar_bloqueadas' => array_slice($listasMostrarBloqueadas, 0, 12),
            ],
        ];
    }

    /**
     * Ofertas elegibles para forzar actualización, agrupadas por categoría del producto y API efectiva.
     *
     * @return array{
     *     por_categoria: array<int, array{ofertas: int, api_base: ?string, api_icon: array}>,
     *     parent_por_categoria: array<int, ?int>,
     *     total_ofertas: int,
     *     por_api: list<array{base: string, count: int, icon: array}>
     * }
     */
    public function resumenForzarActualizacionPorCategoria(Tienda $tienda): array
    {
        $conteosDirectos = OfertaProducto::query()
            ->where('ofertas_producto.tienda_id', $tienda->id)
            ->where('ofertas_producto.mostrar', 'si')
            ->where('ofertas_producto.como_scrapear', 'automatico')
            ->whereNull('ofertas_producto.chollo_id')
            ->join('productos', 'ofertas_producto.producto_id', '=', 'productos.id')
            ->whereNotNull('productos.categoria_id')
            ->groupBy('productos.categoria_id')
            ->selectRaw('productos.categoria_id as cat_id, COUNT(*) as total')
            ->pluck('total', 'cat_id');

        $porCategoria = [];
        $porApi = [];
        $totalOfertas = 0;

        foreach ($conteosDirectos as $catId => $total) {
            $catId = (int) $catId;
            $ofertas = (int) $total;
            $apiEfectiva = $this->resolverApi($tienda, $catId);
            $apiBase = self::apiBase($apiEfectiva) ?? '__sin_api__';
            $apiIcon = self::metaIconoApi($apiEfectiva);

            $porCategoria[$catId] = [
                'ofertas' => $ofertas,
                'api_base' => $apiBase === '__sin_api__' ? null : $apiBase,
                'api_icon' => $apiIcon,
            ];

            $porApi[$apiBase] = ($porApi[$apiBase] ?? 0) + $ofertas;
            $totalOfertas += $ofertas;
        }

        arsort($porApi);

        $porApiListado = collect($porApi)->map(function ($count, $base) {
            $apiParaIcono = $base === '__sin_api__' ? null : $base;

            return [
                'base' => $base === '__sin_api__' ? 'sin_api' : $base,
                'count' => (int) $count,
                'icon' => self::metaIconoApi($apiParaIcono),
            ];
        })->values()->all();

        $parentPorCategoria = Categoria::query()
            ->pluck('parent_id', 'id')
            ->map(fn ($parentId) => $parentId !== null ? (int) $parentId : null)
            ->all();

        return [
            'por_categoria' => $porCategoria,
            'parent_por_categoria' => $parentPorCategoria,
            'total_ofertas' => $totalOfertas,
            'por_api' => $porApiListado,
        ];
    }
}
