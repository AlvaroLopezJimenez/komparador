<?php

namespace App\Http\Controllers\Scraping\Tiendas;

use App\Http\Controllers\Scraping\Tiendas\PlantillaTiendaController;
use App\Models\OfertaProducto;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class SeneticController extends PlantillaTiendaController
{
    /**
     * Devuelve JSON: { success: bool, precio?: float, error?: string }
     */
    public function obtenerPrecio($url, $variante = null, $tienda = null, $oferta = null): JsonResponse
    {
        $resultado = $this->apiHTML->obtenerHTML($url, null, $tienda ? $tienda->api : null);

        if (!is_array($resultado) || empty($resultado['success']) || empty($resultado['html'])) {
            $msg = is_array($resultado) ? ($resultado['error'] ?? 'Error desconocido') : 'Respuesta invalida de la API';
            return response()->json(['success' => false, 'error' => 'No se pudo obtener el HTML: ' . $msg]);
        }

        $html = html_entity_decode((string) $resultado['html'], ENT_QUOTES | ENT_HTML5, 'UTF-8');

        if ($this->esSinStock($html)) {
            if ($oferta && $oferta instanceof OfertaProducto) {
                $oferta->update(['mostrar' => 'no']);

                DB::table('avisos')->insert([
                    'texto_aviso'    => 'SIN STOCK 1A VEZ - GENERADO AUTOMATICAMENTE',
                    'fecha_aviso'    => now()->addDay(),
                    'user_id'        => 1,
                    'avisoable_type' => OfertaProducto::class,
                    'avisoable_id'   => $oferta->id,
                    'oculto'         => 0,
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ]);
            }

            return response()->json(['success' => false, 'error' => 'Producto sin stock']);
        }

        $precio = $this->extraerPrecio($html);
        if ($precio === null) {
            return response()->json([
                'success' => false,
                'error'   => 'No se pudo encontrar el precio en la pagina de Senetic',
            ]);
        }

        return response()->json(['success' => true, 'precio' => $precio]);
    }

    /**
     * Senetic muestra este mensaje en ficha cuando el producto no está disponible.
     */
    private function esSinStock(string $html): bool
    {
        $marcador = 'No esta en el stock';
        $marcadorNotificar = 'Notificarme cuando el producto esté disponible';

        // Ignora textos inyectados en bloques JS (p.ej. globalThis.addToCartVars.lang.contactInfo).
        $htmlSinScripts = preg_replace('~<script\b[^>]*>[\s\S]*?</script>~i', '', $html);
        if (!is_string($htmlSinScripts)) {
            $htmlSinScripts = $html;
        }

        return str_contains($htmlSinScripts, $marcador)
            || str_contains($htmlSinScripts, $marcadorNotificar);
    }

    private function extraerPrecio(string $html): ?float
    {
        $precioGross = $this->extraerPrecioDesdeGrossPrice($html);
        if ($precioGross !== null) {
            return $precioGross;
        }

        // Fallback por si cambia el HTML y no existe gross-price.
        return $this->extraerPrecioDesdeItempropPrice($html);
    }

    /**
     * Prioriza el precio IVA incluido de:
     * <div class="gross-price">...<span itemprop="price">2.613,89</span>...</div>
     */
    private function extraerPrecioDesdeGrossPrice(string $html): ?float
    {
        if (
            preg_match(
                '~<div[^>]*\bclass=(["\'])[^"\']*\bgross-price\b[^"\']*\1[^>]*>[\s\S]*?<span[^>]*\bitemprop=(["\'])price\2[^>]*>\s*(?<p>[0-9][0-9\.\,\s]*[0-9])\s*</span>~i',
                $html,
                $m
            )
        ) {
            $p = $m['p'] ?? null;
            if ($p !== null) {
                return $this->normalizarImporte($p);
            }
        }

        return null;
    }

    /**
     * Fallback: extrae precio desde itemprop="price"
     */
    private function extraerPrecioDesdeItempropPrice(string $html): ?float
    {
        if (
            preg_match(
                '~<span[^>]*\bitemprop=(["\'])price\1[^>]*\bcontent=(["\'])(?<p>[0-9]+(?:[.,][0-9]+)?)\2~i',
                $html,
                $m
            )
        ) {
            $p = $m['p'] ?? null;
            if ($p !== null) {
                return $this->normalizarImporte($p);
            }
        }

        // Fallback por si cambian orden de atributos.
        if (
            preg_match(
                '~<span[^>]*\bcontent=(["\'])(?<p>[0-9]+(?:[.,][0-9]+)?)\1[^>]*\bitemprop=(["\'])price\3~i',
                $html,
                $m2
            )
        ) {
            $p = $m2['p'] ?? null;
            if ($p !== null) {
                return $this->normalizarImporte($p);
            }
        }

        // Fallback texto visible en ese mismo span.
        if (
            preg_match(
                '~<span[^>]*\bitemprop=(["\'])price\1[^>]*>\s*(?<p>[0-9][0-9\.\,\s]*[0-9])\s*</span>~i',
                $html,
                $m3
            )
        ) {
            $p = $m3['p'] ?? null;
            if ($p !== null) {
                return $this->normalizarImporte($p);
            }
        }

        return null;
    }

    /**
     * Normaliza "867.76", "867,76" o "1.867,76" a float.
     */
    private function normalizarImporte(string $importe): ?float
    {
        $importe = str_replace(["\xc2\xa0", "\xe2\x80\xaf", '&nbsp;', ' '], '', $importe);
        $s = preg_replace('/[^\d\.,]/u', '', $importe);
        if ($s === null || $s === '') {
            return null;
        }

        $tieneComa = strpos($s, ',') !== false;
        $tienePunto = strpos($s, '.') !== false;

        if ($tieneComa && $tienePunto) {
            $lastComma = strrpos($s, ',');
            $lastDot = strrpos($s, '.');
            $decPos = max($lastComma, $lastDot);
            $intPart = substr($s, 0, $decPos);
            $decPart = substr($s, $decPos + 1);
        } elseif ($tieneComa) {
            $parts = explode(',', $s, 2);
            $intPart = $parts[0];
            $decPart = $parts[1] ?? '';
        } else {
            $parts = explode('.', $s, 2);
            $intPart = $parts[0];
            $decPart = $parts[1] ?? '';
        }

        $intPart = preg_replace('/[^\d]/', '', $intPart);
        $decPart = preg_replace('/[^\d]/', '', $decPart);
        if ($intPart === '') {
            return null;
        }

        $decPart = $decPart === '' ? '00' : str_pad($decPart, 2, '0', STR_PAD_RIGHT);
        $norm = $intPart . '.' . substr($decPart, 0, 2);

        return (float) $norm;
    }

    // -------------------------------------------------------------------------
    // Cron Neo Objetivos - listado de categoria por paginacion (?f_page=N)
    // -------------------------------------------------------------------------

    public function tipoListadoCategoria(): ?string
    {
        return 'paginacion';
    }

    /**
     * Extrae URLs de producto y construye siguiente URL con f_page+1
     * manteniendo f_size siempre en 1000.
     */
    public function extraerProductosYSiguientePagina(string $html, string $urlPeticionActual): array
    {
        $urlsProductos = [];
        $base = $this->obtenerBaseUrlDesdeUrlPeticion($urlPeticionActual);

        // Producto en listado SOLO dentro de class="product-details":
        // <div class="product-details"> ... <a ... href="https://www.senetic.es/product/...">
        if (
            preg_match_all(
                '~<div[^>]*\bclass=(["\'])[^"\']*\bproduct-details\b[^"\']*\1[^>]*>[\s\S]*?<a[^>]*\bhref=(["\'])(?<u>(?:https?://[^"\']+)?/product/[^"\']+)\2~i',
                $html,
                $mProduct
            )
        ) {
            foreach ($mProduct['u'] as $u) {
                $u = trim((string) $u);
                if ($u === '') {
                    continue;
                }
                $urlsProductos[] = $this->normalizarUrlCorta($u, $base);
            }
        }

        $urlsProductos = array_values(array_unique(array_filter($urlsProductos)));

        $siguienteUrl = null;
        if (count($urlsProductos) > 0) {
            $siguienteUrl = $this->construirUrlSiguientePagina($urlPeticionActual);
        }

        return [
            'urls_productos' => $urlsProductos,
            'siguiente_url'  => $siguienteUrl,
        ];
    }

    private function construirUrlSiguientePagina(string $urlPeticionActual): ?string
    {
        $parts = parse_url($urlPeticionActual);
        if ($parts === false || empty($parts['host'])) {
            return null;
        }

        $paginaActual = $this->extraerNumeroPaginaActual($urlPeticionActual);
        $siguiente = max(1, $paginaActual + 1);

        $scheme = $parts['scheme'] ?? 'https';
        $host = $parts['host'];
        $port = isset($parts['port']) ? ':' . (int) $parts['port'] : '';
        $path = $parts['path'] ?? '/';
        $fragment = $parts['fragment'] ?? '';

        $query = $parts['query'] ?? '';
        parse_str($query, $params);
        $params['f_page'] = $siguiente;
        $params['f_size'] = 1000;
        $queryString = http_build_query($params, '', '&', PHP_QUERY_RFC3986);

        $url = $scheme . '://' . $host . $port . $path;
        if ($queryString !== '') {
            $url .= '?' . $queryString;
        }
        if ($fragment !== '') {
            $url .= '#' . $fragment;
        }

        return $url;
    }

    private function extraerNumeroPaginaActual(string $urlPeticionActual): int
    {
        if (preg_match('~[?&]f_page=(\d+)~i', $urlPeticionActual, $m)) {
            return max(1, (int) ($m[1] ?? 1));
        }
        return 1;
    }

    private function obtenerBaseUrlDesdeUrlPeticion(string $urlPeticionActual): string
    {
        $pu = parse_url($urlPeticionActual);
        $scheme = $pu['scheme'] ?? 'https';
        $host = $pu['host'] ?? 'www.senetic.es';

        return $scheme . '://' . $host;
    }

    private function normalizarUrlCorta(string $url, string $base): string
    {
        $url = trim($url);
        if ($url === '') {
            return $url;
        }

        if (str_starts_with($url, '//')) {
            $pu = parse_url($base);
            $scheme = $pu['scheme'] ?? 'https';
            return $scheme . ':' . $url;
        }

        if (preg_match('~^https?://~i', $url)) {
            return $url;
        }

        if (str_starts_with($url, '/')) {
            return $base . $url;
        }

        return $base . '/' . $url;
    }
}

