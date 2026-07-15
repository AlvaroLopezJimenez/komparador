<?php

namespace App\Http\Controllers\Scraping;

use App\Http\Controllers\Controller;
use App\Models\OfertaProducto;
use App\Services\Scraping as ScrapingService;
use App\Services\TiendaScrapingConfigResolver;
use Carbon\Carbon;

abstract class ScraperBaseController extends Controller
{
    /** @deprecated Usar ScrapingService::API_NAVEGADOR_LOCAL */
    public const API_NAVEGADOR_LOCAL = ScrapingService::API_NAVEGADOR_LOCAL;

    protected function scraping(): ScrapingService
    {
        return app(ScrapingService::class);
    }

    /**
     * Clasifica el resultado de procesarOferta para estadísticas del cron.
     *
     * @return 'actualizada'|'ocultada'|'error'
     */
    protected function clasificarResultadoScraping(array $resultado): string
    {
        if (empty($resultado['success'])) {
            return 'error';
        }

        return !empty($resultado['oferta_oculta']) ? 'ocultada' : 'actualizada';
    }

    /**
     * Procesar una oferta individual para scraping
     */
    protected function procesarOfertaScraper(OfertaProducto $oferta): array
    {
        return $this->scraping()->procesarOferta($oferta);
    }

    /**
     * Calcular el precio real por unidad considerando descuentos
     */
    protected function calcularPrecioRealPorUnidad($oferta): ?float
    {
        return $this->scraping()->calcularPrecioRealPorUnidad($oferta);
    }

    /**
     * Obtener la oferta más barata considerando descuentos
     */
    protected function obtenerOfertaMasBarata($productoId)
    {
        $ofertas = OfertaProducto::where('producto_id', $productoId)
            ->where('mostrar', 'si')
            ->whereHas('tienda', function ($query) {
                $query->where('mostrar_tienda', 'si');
            })
            ->get(['id', 'precio_unidad', 'precio_total', 'unidades', 'descuentos']);

        $ofertaMasBarata = null;
        $precioRealMasBajo = null;

        foreach ($ofertas as $oferta) {
            $precioReal = $this->calcularPrecioRealPorUnidad($oferta);
            if ($precioRealMasBajo === null || $precioReal < $precioRealMasBajo) {
                $precioRealMasBajo = $precioReal;
                $ofertaMasBarata = $oferta;
            }
        }

        return $ofertaMasBarata ? collect([$ofertaMasBarata]) : collect();
    }

    protected function obtenerOfertasElegibles($limit = 50)
    {
        return $this->scraping()->obtenerOfertasElegibles((int) $limit);
    }

    /**
     * @param  array<string, mixed>  $resultado
     * @return array<string, mixed>
     */
    protected function enriquecerResultadoScraperLog(OfertaProducto $oferta, array $resultado): array
    {
        $resultado['hora_peticion'] = now()->format('Y-m-d H:i:s');
        $resultado['es_peticion_api'] = false;
        $resultado['api_usada'] = null;

        if (!$oferta->exists) {
            return $resultado;
        }

        $oferta->loadMissing(['tienda', 'producto']);

        if (!$oferta->tienda) {
            return $resultado;
        }

        $resolver = app(TiendaScrapingConfigResolver::class);
        $categoriaId = $oferta->producto->categoria_id ?? null;
        $api = $resolver->resolverApi($oferta->tienda, $categoriaId);

        if ($api !== null && $api !== '') {
            $resultado['api_usada'] = $api;
            $resultado['es_peticion_api'] = $api !== TiendaScrapingConfigResolver::API_CSV_AWIN;
        }

        return $resultado;
    }

    /**
     * Completa api_usada en logs antiguos y recalcula métricas antes de mostrar en la vista.
     *
     * @param  array<string, mixed>  $logEstructurado
     * @return array<string, mixed>
     */
    public function prepararLogEjecucionScraperParaVista(array $logEstructurado): array
    {
        $resultados = $logEstructurado['resultados'] ?? null;
        if (!is_array($resultados)) {
            return $logEstructurado;
        }

        $logEstructurado['resultados'] = $this->completarApiUsadaEnResultadosLog($resultados);

        return $this->mergeMetricasPeticionesApiEnLog($logEstructurado);
    }

    /**
     * @param  array<int, array<string, mixed>>  $resultados
     * @return array<int, array<string, mixed>>
     */
    protected function completarApiUsadaEnResultadosLog(array $resultados): array
    {
        $resolver = app(TiendaScrapingConfigResolver::class);
        $tiendasApi = \App\Models\Tienda::pluck('api', 'nombre')->toArray();

        $ofertaIds = [];
        foreach ($resultados as $resultado) {
            $ofertaId = $resultado['oferta_id'] ?? null;
            if (is_numeric($ofertaId)) {
                $ofertaIds[] = (int) $ofertaId;
            }
        }

        $ofertas = $ofertaIds === []
            ? collect()
            : OfertaProducto::with(['tienda', 'producto'])
                ->whereIn('id', array_values(array_unique($ofertaIds)))
                ->get()
                ->keyBy('id');

        foreach ($resultados as $indice => $resultado) {
            if (!empty($resultado['api_usada'])) {
                continue;
            }

            $ofertaId = $resultado['oferta_id'] ?? null;
            if (is_numeric($ofertaId)) {
                $oferta = $ofertas->get((int) $ofertaId);
                if ($oferta !== null && $oferta->tienda) {
                    $api = $resolver->resolverApiParaOferta($oferta);
                    if ($api !== null && $api !== '') {
                        $resultados[$indice]['api_usada'] = $api;
                        $resultados[$indice]['es_peticion_api'] = $api !== TiendaScrapingConfigResolver::API_CSV_AWIN;
                        continue;
                    }
                }
            }

            $tiendaNombre = $resultado['tienda_nombre'] ?? null;
            if ($tiendaNombre && !empty($tiendasApi[$tiendaNombre])) {
                $api = $tiendasApi[$tiendaNombre];
                $resultados[$indice]['api_usada'] = $api;
                $resultados[$indice]['es_peticion_api'] = $api !== TiendaScrapingConfigResolver::API_CSV_AWIN;
            }
        }

        return $resultados;
    }

    /**
     * @param  array<string, mixed>  $logEstructurado
     * @return array<string, mixed>
     */
    protected function mergeMetricasPeticionesApiEnLog(array $logEstructurado): array
    {
        $resultados = $logEstructurado['resultados'] ?? [];

        if (!is_array($resultados)) {
            unset($logEstructurado['metricas_peticiones_api'], $logEstructurado['conteo_por_api']);

            return $logEstructurado;
        }

        $logEstructurado['conteo_por_api'] = $this->calcularConteoPorApiEnLog($resultados);

        $metricas = $this->calcularMetricasPeticionesApiEnLog($resultados);
        if ($metricas !== null) {
            $logEstructurado['metricas_peticiones_api'] = $metricas;
        } else {
            unset($logEstructurado['metricas_peticiones_api']);
        }

        return $logEstructurado;
    }

    /**
     * @param  array<int, array<string, mixed>>  $resultados
     * @return array<string, int>
     */
    protected function calcularConteoPorApiEnLog(array $resultados): array
    {
        $conteo = [];

        foreach ($resultados as $resultado) {
            $api = $resultado['api_usada'] ?? null;
            if (!$api) {
                continue;
            }

            $apiBase = explode(';', $api, 2)[0];
            $conteo[$apiBase] = ($conteo[$apiBase] ?? 0) + 1;
        }

        arsort($conteo);

        return $conteo;
    }

    /**
     * @param  array<int, array<string, mixed>>  $resultados
     * @return array<string, mixed>|null
     */
    protected function calcularMetricasPeticionesApiEnLog(array $resultados): ?array
    {
        $porTienda = [];

        foreach ($resultados as $resultado) {
            if (empty($resultado['es_peticion_api'])) {
                continue;
            }

            $tiendaNombre = $resultado['tienda_nombre'] ?? null;
            $horaPeticion = $resultado['hora_peticion'] ?? null;

            if (!$tiendaNombre || !$horaPeticion) {
                continue;
            }

            $porTienda[$tiendaNombre][] = $horaPeticion;
        }

        if ($porTienda === []) {
            return null;
        }

        uasort($porTienda, fn (array $a, array $b): int => count($b) <=> count($a));
        $tiendaMasRepetida = (string) array_key_first($porTienda);
        $horas = $porTienda[$tiendaMasRepetida];
        $peticiones = count($horas);

        if ($peticiones === 0) {
            return null;
        }

        return [
            'tienda_mas_repetida' => $tiendaMasRepetida,
            'peticiones_tienda' => $peticiones,
            'peticiones_por_minuto' => $this->calcularPeticionesPorMinutoRealEnLog($horas),
        ];
    }

    /**
     * Máximo de peticiones en un mismo minuto natural (dato real, sin extrapolar).
     *
     * @param  array<int, string>  $horas
     */
    private function calcularPeticionesPorMinutoRealEnLog(array $horas): int
    {
        $porMinuto = [];

        foreach ($horas as $hora) {
            $minuto = Carbon::parse($hora)->format('Y-m-d H:i');
            $porMinuto[$minuto] = ($porMinuto[$minuto] ?? 0) + 1;
        }

        return (int) max($porMinuto);
    }

    public function obtenerOfertasElegiblesNavegadorLocal(int $limit = 50)
    {
        return $this->scraping()->obtenerOfertasElegiblesNavegadorLocal($limit);
    }
}
