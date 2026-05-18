<?php

namespace App\Http\Controllers\Scraping;

use App\Http\Controllers\Controller;
use App\Models\OfertaProducto;
use App\Services\Scraping as ScrapingService;

abstract class ScraperBaseController extends Controller
{
    /** @deprecated Usar ScrapingService::API_NAVEGADOR_LOCAL */
    public const API_NAVEGADOR_LOCAL = ScrapingService::API_NAVEGADOR_LOCAL;

    protected function scraping(): ScrapingService
    {
        return app(ScrapingService::class);
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

    public function obtenerOfertasElegiblesNavegadorLocal(int $limit = 50)
    {
        return $this->scraping()->obtenerOfertasElegiblesNavegadorLocal($limit);
    }
}
