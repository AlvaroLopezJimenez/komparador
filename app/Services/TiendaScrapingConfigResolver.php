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

    public function obtenerConfig(int $tiendaId, int $categoriaId): ?TiendaCategoriaApi
    {
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

    /**
     * Metadatos para el badge de API (2 letras + color), como en diagnóstico scraping.
     *
     * @return array{label: string, icon_bg: string, title: string, base: ?string}
     */
    public static function metaIconoApi(?string $api, bool $categoriaSinApiAsignada = false): array
    {
        if ($categoriaSinApiAsignada) {
            return [
                'label'   => '—',
                'icon_bg' => 'bg-gray-400',
                'title'   => 'Categoría con ofertas sin API asignada',
                'base'    => null,
            ];
        }

        if ($api === null || $api === '') {
            return [
                'label'   => '?',
                'icon_bg' => 'bg-gray-400',
                'title'   => 'Sin API',
                'base'    => null,
            ];
        }

        $base = self::apiBase($api);
        $colores = [
            'miVpsHtml'         => 'bg-blue-600',
            'scrapingAnt'       => 'bg-green-600',
            'brightData'        => 'bg-purple-600',
            'aliexpressOpen'    => 'bg-amber-600',
            'amazonApi'         => 'bg-rose-600',
            'amazonProductInfo' => 'bg-indigo-600',
            'amazonPricing'     => 'bg-cyan-600',
            'navegadorLocal'    => 'bg-teal-600',
            self::API_CSV_AWIN  => 'bg-orange-600',
        ];

        return [
            'label'   => strtoupper(substr($base, 0, 2)),
            'icon_bg' => $colores[$base] ?? 'bg-gray-600',
            'title'   => $api,
            'base'    => $base,
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
}
