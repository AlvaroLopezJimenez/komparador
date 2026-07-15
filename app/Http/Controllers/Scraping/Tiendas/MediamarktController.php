<?php

namespace App\Http\Controllers\Scraping\Tiendas;

use Illuminate\Http\JsonResponse;

class MediamarktController extends PlantillaTiendaController
{
    /**
     * PDP MediaMarkt: precio en data-test="mms-price" → span.mms-ui-mBgaT (ej. 7,25€).
     */
    public function obtenerPrecio($url, $variante = null, $tienda = null, $oferta = null): JsonResponse
    {
        $resultado = $this->apiHTML->obtenerHTML($url, null, $tienda ? $tienda->api : null);

        if (!is_array($resultado) || empty($resultado['success']) || empty($resultado['html'])) {
            $msg = is_array($resultado) ? ($resultado['error'] ?? 'Error desconocido') : 'Respuesta inválida de la API';

            return response()->json(['success' => false, 'error' => 'No se pudo obtener el HTML de MediaMarkt: ' . $msg]);
        }

        $html = html_entity_decode((string) $resultado['html'], ENT_QUOTES | ENT_HTML5, 'UTF-8');

        $precio = $this->extraerPrecioDesdeJsonLdProduct($html);
        if ($precio === null) {
            $precio = $this->extraerPrecioDesdeBloqueMmsPrice($html);
        }
        if ($precio === null) {
            $precio = $this->extraerPrecioDesdeSpanMmsUiMBgaT($html);
        }

        if ($precio === null) {
            return response()->json([
                'success' => false,
                'error'   => 'No se pudo encontrar el precio en la página de MediaMarkt',
            ]);
        }

        return response()->json(['success' => true, 'precio' => $precio]);
    }

    // -------------------------------------------------------------------------
    // Cron Neo Objetivos - listado de categoría por paginación (?page=N)
    // -------------------------------------------------------------------------

    public function tipoListadoCategoria(): ?string
    {
        return 'paginacion';
    }

    /**
     * PLP categoría MediaMarkt:
     * - Tarjetas data-test="mms-product-card" con enlace /es/product/...html
     * - Paginación ?page=N mientras exista el botón data-test="mms-search-srp-loadmore"
     */
    public function extraerProductosYSiguientePagina(string $html, string $urlPeticionActual): array
    {
        $html = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $base = $this->obtenerBaseUrlDesdeUrlPeticion($urlPeticionActual);

        $urlsProductos = $this->extraerUrlsProductosDesdeListadoCategoria($html);
        foreach ($urlsProductos as $i => $u) {
            $urlsProductos[$i] = $this->normalizarUrlCorta($u, $base);
        }
        $urlsProductos = array_values(array_unique(array_filter($urlsProductos)));

        $siguienteUrl = null;
        if ($this->hayBotonCargarMasProductos($html)) {
            $siguienteUrl = $this->extraerUrlRelNext($html);
            if ($siguienteUrl === null) {
                $siguienteUrl = $this->construirUrlSiguientePagina($urlPeticionActual);
            }
            if ($siguienteUrl !== null) {
                $siguienteUrl = $this->normalizarUrlCorta($siguienteUrl, $base);
            }
        }

        return [
            'urls_productos' => $urlsProductos,
            'siguiente_url'  => $siguienteUrl,
        ];
    }

    /**
     * MediaMarkt muestra "Mostrar X productos más" (mms-search-srp-loadmore) mientras queden páginas.
     * Sin ese botón, la categoría está en la última página.
     */
    private function hayBotonCargarMasProductos(string $html): bool
    {
        return (bool) preg_match(
            '~\bdata-test=(["\'])mms-search-srp-loadmore\1~i',
            $html
        );
    }

    /**
     * @return array<int, string>
     */
    private function extraerUrlsProductosDesdeListadoCategoria(string $html): array
    {
        $urls = [];

        if (
            preg_match_all(
                '~<a[^>]*\bdata-test=(["\'])product-list-item-link\1[^>]*\bhref=(["\'])(?<u>[^"\']+)\2~i',
                $html,
                $mLink
            )
        ) {
            foreach ($mLink['u'] as $u) {
                $u = trim((string) $u);
                if ($u !== '' && $this->esUrlProductoMediamarkt($u)) {
                    $urls[] = $u;
                }
            }
        }

        if ($urls === []) {
            if (
                preg_match_all(
                    '~<a[^>]*\bdata-test=(["\'])mms-router-link-product-image-wrapper\1[^>]*\bhref=(["\'])(?<u>[^"\']+)\2~i',
                    $html,
                    $mImg
                )
            ) {
                foreach ($mImg['u'] as $u) {
                    $u = trim((string) $u);
                    if ($u !== '' && $this->esUrlProductoMediamarkt($u)) {
                        $urls[] = $u;
                    }
                }
            }
        }

        if ($urls === []) {
            if (preg_match_all('~<a[^>]*\bhref=(["\'])(?<u>/es/product/[^"\']+\.html)\1~i', $html, $mHref)) {
                foreach ($mHref['u'] as $u) {
                    $u = trim((string) $u);
                    if ($u !== '' && $this->esUrlProductoMediamarkt($u)) {
                        $urls[] = $u;
                    }
                }
            }
        }

        return array_values(array_unique($urls));
    }

    private function esUrlProductoMediamarkt(string $url): bool
    {
        $path = (string) (parse_url($url, PHP_URL_PATH) ?? $url);

        return (bool) preg_match('~/es/product/[^/]+\.html$~iu', $path);
    }

    private function extraerUrlRelNext(string $html): ?string
    {
        if (
            preg_match(
                '~<link[^>]*\brel=(["\'])next\1[^>]*\bhref=(["\'])(?<u>[^"\']+)\2~i',
                $html,
                $m
            )
        ) {
            $u = trim((string) ($m['u'] ?? ''));
            return $u !== '' ? $u : null;
        }

        if (
            preg_match(
                '~<a[^>]*\bhref=(["\'])(?<u>[^"\']+)\1[^>]*\brel=(["\'])next\3~i',
                $html,
                $m2
            )
        ) {
            $u = trim((string) ($m2['u'] ?? ''));
            return $u !== '' ? $u : null;
        }

        return null;
    }

    private function extraerPrecioDesdeBloqueMmsPrice(string $html): ?float
    {
        $bloques = [];
        if (preg_match_all('~<div[^>]*\bdata-test=(["\'])mms-price\1[^>]*>([\s\S]*?)</div>\s*</div>~i', $html, $m, PREG_SET_ORDER)) {
            foreach ($m as $match) {
                $bloques[] = (string) ($match[2] ?? '');
            }
        }
        if (preg_match_all('~<div[^>]*\bdata-test=(["\'])cofr-price product-price\1[^>]*>([\s\S]*?)</div>\s*</div>~i', $html, $m2, PREG_SET_ORDER)) {
            foreach ($m2 as $match) {
                $bloques[] = (string) ($match[2] ?? '');
            }
        }

        foreach ($bloques as $bloque) {
            $precio = $this->extraerPrecioDesdeSpanMmsUiMBgaT($bloque);
            if ($precio !== null) {
                return $precio;
            }
        }

        return null;
    }

    private function extraerPrecioDesdeSpanMmsUiMBgaT(string $html): ?float
    {
        if (
            preg_match_all(
                '~<span[^>]*\bmms-ui-mBgaT\b[^>]*>\s*(?<t>[^<]+?)\s*</span>~i',
                $html,
                $m
            )
        ) {
            foreach ($m['t'] as $texto) {
                $texto = html_entity_decode(trim((string) $texto), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                if ($this->esTextoPrecioMediamarkt($texto)) {
                    $precio = $this->normalizarImporte($texto);
                    if ($precio !== null) {
                        return $precio;
                    }
                }
            }
        }

        if (
            preg_match(
                '~<span[^>]*\baria-hidden=(["\'])true\1[^>]*>\s*(?<t>[0-9][0-9\s\.,&nbsp;]*(?:€|&euro;))\s*</span>~i',
                $html,
                $mAria
            )
        ) {
            $precio = $this->normalizarImporte((string) ($mAria['t'] ?? ''));
            if ($precio !== null) {
                return $precio;
            }
        }

        return null;
    }

    private function esTextoPrecioMediamarkt(string $texto): bool
    {
        if (!preg_match('/(?:€|&euro;)/u', $texto)) {
            return false;
        }

        $lower = mb_strtolower($texto, 'UTF-8');

        foreach (['valoracion', 'valoración', 'carrito', 'basado', 'iva incl', 'envío', 'envio'] as $excluir) {
            if (str_contains($lower, $excluir)) {
                return false;
            }
        }

        return (bool) preg_match('/\d/u', $texto);
    }

    private function extraerPrecioDesdeJsonLdProduct(string $html): ?float
    {
        if (!preg_match_all('~<script[^>]+type=(["\'])application/ld\+json\1[^>]*>(.*?)</script>~si', $html, $blocks)) {
            return null;
        }

        foreach ($blocks[2] as $json) {
            $data = json_decode(trim((string) $json), true);
            if (!is_array($data)) {
                continue;
            }

            $objs = isset($data[0]) ? $data : [$data];
            foreach ($objs as $obj) {
                if (!is_array($obj)) {
                    continue;
                }

                if (isset($obj['@graph']) && is_array($obj['@graph'])) {
                    foreach ($obj['@graph'] as $node) {
                        if (!is_array($node) || ($node['@type'] ?? null) !== 'Product') {
                            continue;
                        }
                        $precio = $this->leerPrecioDeOffers($node['offers'] ?? null);
                        if ($precio !== null) {
                            return $precio;
                        }
                    }
                    continue;
                }

                if (($obj['@type'] ?? null) === 'Product') {
                    $precio = $this->leerPrecioDeOffers($obj['offers'] ?? null);
                    if ($precio !== null) {
                        return $precio;
                    }
                }
            }
        }

        return null;
    }

    /**
     * @param mixed $offers
     */
    private function leerPrecioDeOffers($offers): ?float
    {
        if (!$offers) {
            return null;
        }

        if (isset($offers['price'])) {
            $precio = $this->normalizarImporte((string) $offers['price']);
            if ($precio !== null) {
                return $precio;
            }
        }

        if (is_array($offers)) {
            foreach ($offers as $of) {
                if (!is_array($of) || !isset($of['price'])) {
                    continue;
                }
                $precio = $this->normalizarImporte((string) $of['price']);
                if ($precio !== null) {
                    return $precio;
                }
            }
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
            $decPos = max(strrpos($s, ','), strrpos($s, '.'));
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

        return (float) ($intPart . '.' . substr($decPart, 0, 2));
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
        $host = $pu['host'] ?? 'www.mediamarkt.es';

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
