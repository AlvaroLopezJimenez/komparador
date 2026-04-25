<?php

namespace App\Http\Controllers\Scraping\Tiendas;

use App\Http\Controllers\Scraping\Tiendas\PlantillaTiendaController;
use Illuminate\Http\JsonResponse;

/**
 * Tienda oficial Acer (store.acer.com, Magento).
 *
 * Precio: data-price-amount en wrapper finalPrice, o span.price / aria-label.
 * Categorías: paginación ?p=2 (etc.). URLs de producto en enlaces product-item-photo.
 * Sin stock: pendiente de patrones reales en PDP (esSinStock devuelve false por ahora).
 */
class AcerController extends PlantillaTiendaController
{
    public function obtenerPrecio($url, $variante = null, $tienda = null, $oferta = null): JsonResponse
    {
        $resultado = $this->apiHTML->obtenerHTML($url, null, $tienda ? $tienda->api : null);

        if (!is_array($resultado) || empty($resultado['success']) || empty($resultado['html'])) {
            $msg = is_array($resultado) ? ($resultado['error'] ?? 'Error desconocido') : 'Respuesta inválida de la API';
            return response()->json(['success' => false, 'error' => 'No se pudo obtener el HTML de Acer: ' . $msg]);
        }

        $html = html_entity_decode((string) $resultado['html'], ENT_QUOTES | ENT_HTML5, 'UTF-8');

        if ($this->esSinStock($html)) {
            // Cuando se conozca el HTML sin stock: avisos + ocultar oferta (ver Primor/Boticas23).
            return response()->json(['success' => false, 'error' => 'Producto sin stock']);
        }

        $precio = $this->extraerPrecio($html);
        if ($precio === null) {
            return response()->json([
                'success' => false,
                'error'   => 'No se pudo encontrar el precio en la página de Acer',
            ]);
        }

        return response()->json(['success' => true, 'precio' => $precio]);
    }

    /**
     * Patrones de sin stock en PDP Acer: por definir cuando tengamos HTML real.
     */
    private function esSinStock(string $html): bool
    {
        return false;
    }

    private function extraerPrecio(string $html): ?float
    {
        $p = $this->extraerPrecioDesdeDataFinalPrice($html);
        if ($p !== null) {
            return $p;
        }

        $p = $this->extraerPrecioDesdeAriaLabelSellingPrice($html);
        if ($p !== null) {
            return $p;
        }

        return $this->extraerPrecioDesdeSpanPrice($html);
    }

    /**
     * Ej: <span data-price-amount="269.9" data-price-type="finalPrice" ...>
     */
    private function extraerPrecioDesdeDataFinalPrice(string $html): ?float
    {
        if (preg_match(
            '/data-price-type\s*=\s*["\']finalPrice["\'][^>]*data-price-amount\s*=\s*["\'](?<p>\d+(?:\.\d+)?)["\']/i',
            $html,
            $m
        )) {
            $v = (float) $m['p'];
            return $v > 0 ? $v : null;
        }
        if (preg_match(
            '/data-price-amount\s*=\s*["\'](?<p>\d+(?:\.\d+)?)["\'][^>]*data-price-type\s*=\s*["\']finalPrice["\']/i',
            $html,
            $m2
        )) {
            $v = (float) $m2['p'];
            return $v > 0 ? $v : null;
        }

        return null;
    }

    /**
     * Ej: aria-label="Selling Price Is 269,90 €"
     */
    private function extraerPrecioDesdeAriaLabelSellingPrice(string $html): ?float
    {
        if (preg_match(
            '/aria-label\s*=\s*["\']Selling Price Is\s+(?<p>[\d\.,]+)\s*€/iu',
            $html,
            $m
        )) {
            return $this->normalizarImporteEuropeo($m['p']);
        }

        return null;
    }

    /**
     * Ej: <span class="price" ...>269,90 €</span> dentro de bloque final_price (evita precio tachado si existe).
     */
    private function extraerPrecioDesdeSpanPrice(string $html): ?float
    {
        if (preg_match(
            '/class=(["\'])[^"\']*price-final_price[^"\']*\1[^>]*>[\s\S]*?<span[^>]*\bclass=(["\'])[^"\']*\bprice\b[^"\']*\2[^>]*>\s*(?<p>[\d\.,]+)\s*€/iu',
            $html,
            $m
        )) {
            return $this->normalizarImporteEuropeo($m['p']);
        }

        if (preg_match(
            '/<span[^>]*\bclass=(["\'])[^"\']*\bprice\b[^"\']*\1[^>]*>\s*(?<p>[\d\.,]+)\s*€\s*<\/span>/iu',
            $html,
            $m2
        )) {
            return $this->normalizarImporteEuropeo($m2['p']);
        }

        return null;
    }

    private function normalizarImporteEuropeo(string $importe): ?float
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
        $v = (float) $norm;

        return $v > 0 ? $v : null;
    }

    // -------------------------------------------------------------------------
    // Cron Neo Objetivos – categorías con ?p=N (Magento)
    // -------------------------------------------------------------------------

    public function tipoListadoCategoria(): ?string
    {
        return 'paginacion';
    }

    /**
     * URLs de ficha: enlaces con clase product-item-photo (evita duplicados del carrusel slick).
     * Siguiente página: incrementa parámetro p mientras haya productos en la página actual.
     */
    public function extraerProductosYSiguientePagina(string $html, string $urlPeticionActual): array
    {
        $urlsProductos = [];
        $base = $this->obtenerBaseUrlDesdeUrlPeticion($urlPeticionActual);

        if (preg_match_all(
            '~<a\s[^>]*\bhref=(["\'])(?<href>[^"\']+)\1[^>]*\bproduct-item-photo~i',
            $html,
            $m
        )) {
            foreach ($m['href'] as $href) {
                $href = trim((string) $href);
                if ($href === '' || str_starts_with($href, '#')) {
                    continue;
                }
                $urlsProductos[] = $this->normalizarUrlCorta($href, $base);
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
        $params['p'] = $siguiente;
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
        if (preg_match('~[?&]p=(\d+)~i', $urlPeticionActual, $m)) {
            return max(1, (int) ($m[1] ?? 1));
        }

        return 1;
    }

    private function obtenerBaseUrlDesdeUrlPeticion(string $urlPeticionActual): string
    {
        $pu = parse_url($urlPeticionActual);
        $scheme = $pu['scheme'] ?? 'https';
        $host = $pu['host'] ?? 'store.acer.com';

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
