<?php

namespace App\Http\Controllers\Scraping\Tiendas;

use App\Http\Controllers\Scraping\Tiendas\PlantillaTiendaController;
use Illuminate\Http\JsonResponse;

/**
 * AnimalZoo (IdoSell):
 * - Precio en #projector_price_value[data-price] dentro de .projector_prices__price_wrapper.
 * - Categorías paginadas con ?counter=N (counter=0 es la primera página sin parámetro).
 * - Fin de paginación: h3.return_label "No se ha encontrado el documento...".
 * - URLs de producto en listado: a.product__name → /product-spa-{id}-....html
 */
class AnimalzooController extends PlantillaTiendaController
{
    public function obtenerPrecio($url, $variante = null, $tienda = null, $oferta = null): JsonResponse
    {
        $resultado = $this->apiHTML->obtenerHTML($url, null, $tienda ? $tienda->api : null);

        if (!is_array($resultado) || empty($resultado['success']) || empty($resultado['html'])) {
            $msg = is_array($resultado) ? ($resultado['error'] ?? 'Error desconocido') : 'Respuesta inválida de la API';

            return response()->json(['success' => false, 'error' => 'No se pudo obtener el HTML: ' . $msg]);
        }

        $html = html_entity_decode((string) $resultado['html'], ENT_QUOTES | ENT_HTML5, 'UTF-8');

        $precio = $this->extraerPrecioDesdeProjector($html);
        if ($precio === null) {
            return response()->json([
                'success' => false,
                'error'   => 'No se encontró #projector_price_value con data-price en la página',
            ]);
        }

        return response()->json(['success' => true, 'precio' => $precio]);
    }

    /**
     * <strong class="projector_prices__price" id="projector_price_value" data-price="12.58">
     */
    private function extraerPrecioDesdeProjector(string $html): ?float
    {
        if (
            preg_match(
                '~<strong[^>]*\bid=(["\'])projector_price_value\1[^>]*\bdata-price=(["\'])(?<p>[0-9]+(?:[.,][0-9]+)?)\2~i',
                $html,
                $m
            )
        ) {
            return $this->normalizarImporte($m['p']);
        }

        if (
            preg_match(
                '~<strong[^>]*\bdata-price=(["\'])(?<p>[0-9]+(?:[.,][0-9]+)?)\1[^>]*\bid=(["\'])projector_price_value\3~i',
                $html,
                $m2
            )
        ) {
            return $this->normalizarImporte($m2['p']);
        }

        if (
            preg_match(
                '~projector_prices__price_wrapper[\s\S]*?<strong[^>]*\bdata-price=(["\'])(?<p>[0-9]+(?:[.,][0-9]+)?)\1~i',
                $html,
                $m3
            )
        ) {
            return $this->normalizarImporte($m3['p']);
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
    // Cron Neo Objetivos - listado de categoría por paginación (?counter=N)
    // -------------------------------------------------------------------------

    public function tipoListadoCategoria(): ?string
    {
        return 'paginacion';
    }

    /**
     * Paginación IdoSell: ?counter=1 es la página 2, ?counter=41 es la página 42.
     * Para cuando aparece h3.return_label de documento no encontrado.
     */
    public function extraerProductosYSiguientePagina(string $html, string $urlPeticionActual): array
    {
        if ($this->esPaginaNoEncontrada($html)) {
            return [
                'urls_productos' => [],
                'siguiente_url'  => null,
            ];
        }

        $urlsProductos = $this->extraerUrlsProductosDesdeListado($html, $urlPeticionActual);

        $siguienteUrl = null;
        if (count($urlsProductos) > 0) {
            $siguienteUrl = $this->construirUrlSiguientePagina($urlPeticionActual);
        }

        return [
            'urls_productos' => $urlsProductos,
            'siguiente_url'  => $siguienteUrl,
        ];
    }

    private function esPaginaNoEncontrada(string $html): bool
    {
        return (bool) preg_match(
            '~<h3\s+class=(["\'])return_label\1[^>]*>[\s\S]*?No\s+se\s+ha\s+encontrado\s+el\s+documento~iu',
            $html
        );
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
                '~<a[^>]*\bclass=(["\'])[^"\']*\bproduct__name\b[^"\']*\1[^>]*\bhref=(["\'])(?<u>[^"\']+)\2~i',
                $html,
                $mClassFirst
            )
        ) {
            foreach ($mClassFirst['u'] as $u) {
                $u = trim((string) $u);
                if ($u !== '') {
                    $urls[] = $this->normalizarUrlCorta($u, $base);
                }
            }
        }

        if (
            preg_match_all(
                '~<a[^>]*\bhref=(["\'])(?<u>[^"\']+)\1[^>]*\bclass=(["\'])[^"\']*\bproduct__name\b[^"\']*\3~i',
                $html,
                $mHrefFirst
            )
        ) {
            foreach ($mHrefFirst['u'] as $u) {
                $u = trim((string) $u);
                if ($u !== '') {
                    $urls[] = $this->normalizarUrlCorta($u, $base);
                }
            }
        }

        if (
            preg_match_all(
                '~<a[^>]*\bdata-product-id=(["\'])\d+\1[^>]*\bhref=(["\'])(?<u>[^"\']+)\2~i',
                $html,
                $mDataProduct
            )
        ) {
            foreach ($mDataProduct['u'] as $u) {
                $u = trim((string) $u);
                if ($u !== '') {
                    $urls[] = $this->normalizarUrlCorta($u, $base);
                }
            }
        }

        $urls = array_values(array_unique(array_filter($urls)));

        return array_values(array_filter($urls, function (string $u) {
            return str_contains(strtolower($u), 'animalzoo.es')
                && preg_match('~/product-spa-\d+-[^/]+\.html~i', $u);
        }));
    }

    private function construirUrlSiguientePagina(string $urlPeticionActual): ?string
    {
        $parts = parse_url($urlPeticionActual);
        if ($parts === false || empty($parts['host'])) {
            return null;
        }

        $counterActual = $this->extraerCounterActual($urlPeticionActual);
        $siguienteCounter = $counterActual + 1;

        $scheme = $parts['scheme'] ?? 'https';
        $host = $parts['host'];
        $port = isset($parts['port']) ? ':' . (int) $parts['port'] : '';
        $path = $parts['path'] ?? '/';
        $fragment = $parts['fragment'] ?? '';

        $query = $parts['query'] ?? '';
        parse_str($query, $params);
        unset($params['page'], $params['pagina'], $params['p']);
        $params['counter'] = $siguienteCounter;
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

    /**
     * counter=0 o sin parámetro = página 1; counter=1 = página 2, etc.
     */
    private function extraerCounterActual(string $urlPeticionActual): int
    {
        if (preg_match('~[?&]counter=(\d+)~i', $urlPeticionActual, $m)) {
            return max(0, (int) ($m[1] ?? 0));
        }

        return 0;
    }

    private function obtenerBaseUrlDesdeUrlPeticion(string $urlPeticionActual): string
    {
        $pu = parse_url($urlPeticionActual);
        $scheme = $pu['scheme'] ?? 'https';
        $host = $pu['host'] ?? 'animalzoo.es';

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
