<?php

namespace App\Http\Controllers\Scraping\Tiendas;

use App\Http\Controllers\Scraping\Tiendas\PlantillaTiendaController;
use Illuminate\Http\JsonResponse;

/**
 * Mimascota (miscota.es):
 * - Precio en #price_ref (.product-new-price) con parte entera + span.price-decimal.
 * - Categorías paginadas con ?pag=N.
 * - Fin de paginación: page-indicator "Página X / Y" (para en la última).
 * - URLs de producto en listado: data-url en .prod__box--content o enlace en .prod__box--prod-name.
 */
class MimascotaController extends PlantillaTiendaController
{
    public function obtenerPrecio($url, $variante = null, $tienda = null, $oferta = null): JsonResponse
    {
        $resultado = $this->apiHTML->obtenerHTML($url, null, $tienda ? $tienda->api : null);

        if (!is_array($resultado) || empty($resultado['success']) || empty($resultado['html'])) {
            $msg = is_array($resultado) ? ($resultado['error'] ?? 'Error desconocido') : 'Respuesta inválida de la API';

            return response()->json(['success' => false, 'error' => 'No se pudo obtener el HTML: ' . $msg]);
        }

        $html = html_entity_decode((string) $resultado['html'], ENT_QUOTES | ENT_HTML5, 'UTF-8');

        $precio = $this->extraerPrecioDesdePriceRef($html);
        if ($precio === null) {
            return response()->json([
                'success' => false,
                'error'   => 'No se encontró #price_ref (.product-new-price) en la página',
            ]);
        }

        return response()->json(['success' => true, 'precio' => $precio]);
    }

    /**
     * <div class="product-new-price" id="price_ref">17<span class="price-decimal">,00</span>...
     */
    private function extraerPrecioDesdePriceRef(string $html): ?float
    {
        if (
            preg_match(
                '~<div[^>]*\bid=(["\'])price_ref\1[^>]*>\s*(?<entero>\d+)\s*<span[^>]*\bclass=(["\'])[^"\']*\bprice-decimal\b[^"\']*\2[^>]*>\s*(?<decimal>[,.]\d{2})\s*</span>~i',
                $html,
                $m
            )
        ) {
            return $this->normalizarImporte($m['entero'] . $m['decimal']);
        }

        if (
            preg_match(
                '~<div[^>]*\bclass=(["\'])[^"\']*\bproduct-new-price\b[^"\']*\1[^>]*\bid=(["\'])price_ref\2[^>]*>\s*(?<entero>\d+)\s*<span[^>]*\bclass=(["\'])[^"\']*\bprice-decimal\b[^"\']*\4[^>]*>\s*(?<decimal>[,.]\d{2})\s*</span>~i',
                $html,
                $m2
            )
        ) {
            return $this->normalizarImporte($m2['entero'] . $m2['decimal']);
        }

        if (
            preg_match(
                '~<div[^>]*\bid=(["\'])js-old-price\1[^>]*>\s*(?<p>[0-9]+(?:[.,][0-9]{2})?)\s*€~i',
                $html,
                $mOld
            )
        ) {
            return $this->normalizarImporte($mOld['p']);
        }

        return null;
    }

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
            $lastComa = strrpos($s, ',');
            $lastDot = strrpos($s, '.');
            if ($lastComa !== false && ($lastDot === false || $lastComa > $lastDot)) {
                $s = str_replace('.', '', $s);
                $s = str_replace(',', '.', $s);
            } else {
                $s = str_replace(',', '', $s);
            }
        } elseif ($tieneComa) {
            $s = str_replace(',', '.', $s);
        }

        if (!preg_match('/^\d+(\.\d+)?$/', $s)) {
            return null;
        }

        return (float) $s;
    }

    // -------------------------------------------------------------------------
    // Cron Neo Objetivos - listado de categoría por paginación (?pag=N)
    // -------------------------------------------------------------------------

    public function tipoListadoCategoria(): ?string
    {
        return 'paginacion';
    }

    /**
     * Paginación: https://www.miscota.es/perros/s_pienso?pag=6
     * Fin cuando page-indicator indica Página X / X (última página).
     */
    public function extraerProductosYSiguientePagina(string $html, string $urlPeticionActual): array
    {
        $urlsProductos = $this->extraerUrlsProductosDesdeListado($html, $urlPeticionActual);
        $indicador = $this->extraerIndicadorPagina($html);

        $siguienteUrl = null;
        if (count($urlsProductos) > 0 && !$this->esUltimaPagina($indicador)) {
            $paginaSiguiente = $indicador !== null
                ? $indicador['actual'] + 1
                : $this->extraerNumeroPaginaActual($urlPeticionActual) + 1;
            $siguienteUrl = $this->construirUrlSiguientePagina($urlPeticionActual, $paginaSiguiente);
        }

        return [
            'urls_productos' => $urlsProductos,
            'siguiente_url'  => $siguienteUrl,
        ];
    }

    /**
     * @return array{actual: int, total: int}|null
     */
    private function extraerIndicadorPagina(string $html): ?array
    {
        if (
            preg_match(
                '~page-indicator[\s\S]*?<span[^>]*>\s*P[aá]gina\s+(?<actual>\d+)\s*/\s*(?<total>\d+)\s*</span>~iu',
                $html,
                $m
            )
        ) {
            return [
                'actual' => max(1, (int) $m['actual']),
                'total'  => max(1, (int) $m['total']),
            ];
        }

        if (
            preg_match(
                '~<span[^>]*>\s*P[aá]gina\s+(?<actual>\d+)\s*/\s*(?<total>\d+)\s*</span>~iu',
                $html,
                $m2
            )
        ) {
            return [
                'actual' => max(1, (int) $m2['actual']),
                'total'  => max(1, (int) $m2['total']),
            ];
        }

        return null;
    }

    private function esUltimaPagina(?array $indicador): bool
    {
        if ($indicador === null) {
            return false;
        }

        return $indicador['actual'] >= $indicador['total'];
    }

    /**
     * @return array<int, string>
     */
    private function extraerUrlsProductosDesdeListado(string $html, string $urlPeticionActual): array
    {
        $urls = [];
        $base = $this->obtenerBaseUrlDesdeUrlPeticion($urlPeticionActual);

        if (
            preg_match_all(
                '~<li[^>]*\bclass=(["\'])[^"\']*\bprod__box\b[^"\']*\1[^>]*>(?<bloque>[\s\S]*?)</li>~i',
                $html,
                $bloques
            )
        ) {
            foreach ($bloques['bloque'] as $bloque) {
                $url = $this->extraerUrlProductoDesdeBloqueProdBox((string) $bloque);
                if ($url !== null) {
                    $urls[] = $this->normalizarUrlCorta($url, $base);
                }
            }
        }

        if ($urls === []) {
            if (
                preg_match_all(
                    '~<div[^>]*\bclass=(["\'])[^"\']*\bprod__box--content\b[^"\']*\1[^>]*\bdata-url=(["\'])(?<u>[^"\']+)\2~i',
                    $html,
                    $mDataUrl
                )
            ) {
                foreach ($mDataUrl['u'] as $u) {
                    $u = trim((string) $u);
                    if ($u !== '') {
                        $urls[] = $this->normalizarUrlCorta($u, $base);
                    }
                }
            }
        }

        $urls = array_values(array_unique(array_filter($urls)));

        return array_values(array_filter($urls, fn (string $u) => $this->esUrlProductoMiscota($u)));
    }

    private function extraerUrlProductoDesdeBloqueProdBox(string $bloque): ?string
    {
        if (
            preg_match(
                '~<div[^>]*\bclass=(["\'])[^"\']*\bprod__box--content\b[^"\']*\1[^>]*\bdata-url=(["\'])(?<u>[^"\']+)\2~i',
                $bloque,
                $mDataUrl
            )
        ) {
            return trim($mDataUrl['u']);
        }

        if (
            preg_match(
                '~prod__box--prod-name[\s\S]*?<a[^>]*\bhref=(["\'])(?<u>[^"\']+)\1~i',
                $bloque,
                $mHref
            )
        ) {
            return trim($mHref['u']);
        }

        return null;
    }

    /**
     * Acepta slugs (/perros/marca/producto), pr-12345 y slugs con id al final.
     * Excluye URLs de categoría (/perros/s_pienso).
     */
    private function esUrlProductoMiscota(string $url): bool
    {
        if (!preg_match('~^https?://(?:www\.)?miscota\.es/~i', $url)) {
            return false;
        }

        $path = parse_url($url, PHP_URL_PATH) ?? '';
        $segmentos = array_values(array_filter(explode('/', trim($path, '/'))));

        if (count($segmentos) < 3) {
            return false;
        }

        foreach ($segmentos as $segmento) {
            if (preg_match('~^s_~i', $segmento)) {
                return false;
            }
        }

        return true;
    }

    private function construirUrlSiguientePagina(string $urlPeticionActual, int $paginaSiguiente): ?string
    {
        $parts = parse_url($urlPeticionActual);
        if ($parts === false || empty($parts['host'])) {
            return null;
        }

        $scheme = $parts['scheme'] ?? 'https';
        $host = $parts['host'];
        $port = isset($parts['port']) ? ':' . (int) $parts['port'] : '';
        $path = $parts['path'] ?? '/';
        $fragment = $parts['fragment'] ?? '';

        $query = $parts['query'] ?? '';
        parse_str($query, $params);
        unset($params['page'], $params['p'], $params['counter']);
        $params['pag'] = max(1, $paginaSiguiente);
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
        if (preg_match('~[?&]pag=(\d+)~i', $urlPeticionActual, $m)) {
            return max(1, (int) ($m[1] ?? 1));
        }

        return 1;
    }

    private function obtenerBaseUrlDesdeUrlPeticion(string $urlPeticionActual): string
    {
        $pu = parse_url($urlPeticionActual);
        $scheme = $pu['scheme'] ?? 'https';
        $host = $pu['host'] ?? 'www.miscota.es';

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
