<?php

namespace App\Http\Controllers\Scraping\Tiendas;

use App\Http\Controllers\Scraping\Tiendas\PlantillaTiendaController;
use Illuminate\Http\JsonResponse;

/**
 * Petsworldmarket (PrestaShop):
 * - Precio en span[itemprop="price"][content] en ficha de producto.
 * - Categorías paginadas con ?page=N.
 * - Fin de paginación: section#page-not-found / page-content page-not-found (404).
 * - URLs de producto en listado: a.thumbnail.product-thumbnail o h3.product-title a.
 */
class PetsworldmarketController extends PlantillaTiendaController
{
    public function obtenerPrecio($url, $variante = null, $tienda = null, $oferta = null): JsonResponse
    {
        $resultado = $this->apiHTML->obtenerHTML($url, null, $tienda ? $tienda->api : null);

        if (!is_array($resultado) || empty($resultado['success']) || empty($resultado['html'])) {
            $msg = is_array($resultado) ? ($resultado['error'] ?? 'Error desconocido') : 'Respuesta inválida de la API';

            return response()->json(['success' => false, 'error' => 'No se pudo obtener el HTML: ' . $msg]);
        }

        $html = html_entity_decode((string) $resultado['html'], ENT_QUOTES | ENT_HTML5, 'UTF-8');

        $precio = $this->extraerPrecioDesdeItempropPrice($html, $url, $variante);
        if ($precio === null) {
            return response()->json([
                'success' => false,
                'error'   => 'No se encontró span[itemprop="price"] con content en la página',
            ]);
        }

        return response()->json(['success' => true, 'precio' => $precio]);
    }

    /**
     * <span itemprop="price" content="15.65">15,65&nbsp;€</span>
     */
    private function extraerPrecioDesdeItempropPrice(string $html, string $url, ?string $variante = null): ?float
    {
        $varianteId = trim((string) ($variante ?? ''));
        if ($varianteId !== '' && preg_match('~\bid_product_attribute=(\d+)~i', $url, $mAttr)) {
            $varianteId = $mAttr[1];
        }

        if ($varianteId !== '') {
            $precio = $this->extraerPrecioPorVariante($html, $varianteId);
            if ($precio !== null) {
                return $precio;
            }
        }

        if (
            preg_match_all(
                '~<span[^>]*\bitemprop=(["\'])price\1[^>]*\bcontent=(["\'])(?<p>[0-9]+(?:[.,][0-9]+)?)\2~i',
                $html,
                $matches,
                PREG_SET_ORDER
            )
        ) {
            foreach ($matches as $m) {
                $precio = $this->normalizarImporte($m['p']);
                if ($precio !== null && $precio > 0) {
                    return $precio;
                }
            }
        }

        if (
            preg_match_all(
                '~<span[^>]*\bcontent=(["\'])(?<p>[0-9]+(?:[.,][0-9]+)?)\1[^>]*\bitemprop=(["\'])price\3~i',
                $html,
                $matches2,
                PREG_SET_ORDER
            )
        ) {
            foreach ($matches2 as $m) {
                $precio = $this->normalizarImporte($m['p']);
                if ($precio !== null && $precio > 0) {
                    return $precio;
                }
            }
        }

        return null;
    }

    private function extraerPrecioPorVariante(string $html, string $varianteId): ?float
    {
        if (
            preg_match(
                '~\bdata-id-product-attribute=(["\'])' . preg_quote($varianteId, '~') . '\1[\s\S]*?<span[^>]*\bitemprop=(["\'])price\2[^>]*\bcontent=(["\'])(?<p>[0-9]+(?:[.,][0-9]+)?)\3~i',
                $html,
                $m
            )
        ) {
            return $this->normalizarImporte($m['p']);
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
    // Cron Neo Objetivos - listado de categoría por paginación (?page=N)
    // -------------------------------------------------------------------------

    public function tipoListadoCategoria(): ?string
    {
        return 'paginacion';
    }

    /**
     * Paginación: https://www.petsworldmarket.com/271-comida-para-perros?page=2
     * Para cuando aparece section.page-not-found (404).
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
            '~<section[^>]*\bid=(["\'])content\1[^>]*\bclass=(["\'])[^"\']*\bpage-not-found\b[^"\']*\2~i',
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

        $patrones = [
            '~<a[^>]*\bclass=(["\'])[^"\']*\bproduct-thumbnail\b[^"\']*\1[^>]*\bhref=(["\'])(?<u>[^"\']+)\2~i',
            '~<a[^>]*\bhref=(["\'])(?<u>[^"\']+)\1[^>]*\bclass=(["\'])[^"\']*\bproduct-thumbnail\b[^"\']*\3~i',
            '~<h3[^>]*\bclass=(["\'])[^"\']*\bproduct-title\b[^"\']*\1[^>]*>[\s\S]*?<a[^>]*\bhref=(["\'])(?<u>[^"\']+)\2~i',
        ];

        foreach ($patrones as $patron) {
            if (preg_match_all($patron, $html, $m)) {
                foreach ($m['u'] as $u) {
                    $u = trim((string) $u);
                    if ($u !== '') {
                        $urls[] = $this->normalizarUrlCorta($u, $base);
                    }
                }
            }
        }

        $urls = array_values(array_unique(array_filter($urls)));

        return array_values(array_filter($urls, function (string $u) {
            return str_contains(strtolower($u), 'petsworldmarket.com')
                && preg_match('~-\d+\.html(?:#|$|\?)~i', $u);
        }));
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
        unset($params['p'], $params['pagina'], $params['counter']);
        $params['page'] = $siguiente;
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
        if (preg_match('~[?&]page=(\d+)~i', $urlPeticionActual, $m)) {
            return max(1, (int) ($m[1] ?? 1));
        }

        return 1;
    }

    private function obtenerBaseUrlDesdeUrlPeticion(string $urlPeticionActual): string
    {
        $pu = parse_url($urlPeticionActual);
        $scheme = $pu['scheme'] ?? 'https';
        $host = $pu['host'] ?? 'www.petsworldmarket.com';

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
