<?php

namespace App\Http\Controllers\Scraping\Tiendas;

use App\Http\Controllers\Scraping\Tiendas\PlantillaTiendaController;
use Illuminate\Http\JsonResponse;

/**
 * Zooplus:
 * - Precio en span[data-zta="reducedPriceAmount"] dentro de .z-product-price__price-wrap.
 * - Categorías paginadas con ?p=N; fin cuando redirige a la primera o paginación indica última página.
 * - URLs de producto en listado: a[data-zta="product-info"] con activeVariant.
 */
class ZooplusController extends PlantillaTiendaController
{
    public function obtenerPrecio($url, $variante = null, $tienda = null, $oferta = null): JsonResponse
    {
        $resultado = $this->apiHTML->obtenerHTML($url, null, $tienda ? $tienda->api : null);

        if (!is_array($resultado) || empty($resultado['success']) || empty($resultado['html'])) {
            $msg = is_array($resultado) ? ($resultado['error'] ?? 'Error desconocido') : 'Respuesta inválida de la API';

            return response()->json(['success' => false, 'error' => 'No se pudo obtener el HTML: ' . $msg]);
        }

        $html = html_entity_decode((string) $resultado['html'], ENT_QUOTES | ENT_HTML5, 'UTF-8');

        $precio = $this->extraerPrecioDesdeReducedPriceAmount($html, $url, $variante);
        if ($precio === null) {
            return response()->json([
                'success' => false,
                'error'   => 'No se encontró data-zta="reducedPriceAmount" en la página',
            ]);
        }

        return response()->json(['success' => true, 'precio' => $precio]);
    }

    /**
     * <span class="z-product-price__amount" data-zta="reducedPriceAmount">47,99 €</span>
     */
    private function extraerPrecioDesdeReducedPriceAmount(string $html, string $url, ?string $variante = null): ?float
    {
        $activeVariant = $this->extraerActiveVariantDesdeUrl($url);
        if ($activeVariant !== null) {
            $precio = $this->extraerPrecioPorActiveVariant($html, $activeVariant);
            if ($precio !== null) {
                return $precio;
            }
        }

        $varianteId = trim((string) ($variante ?? ''));
        if ($varianteId !== '') {
            $precio = $this->extraerPrecioPorVarianteId($html, $varianteId);
            if ($precio !== null) {
                return $precio;
            }
        }

        if (
            preg_match(
                '~data-zta=(["\'])SelectedArticleBox\1[\s\S]*?z-product-price__price-wrap">\s*<span[^>]*\bdata-zta=(["\'])reducedPriceAmount\2[^>]*>\s*(?<p>\d{1,3}(?:[.\s]\d{3})*(?:[.,]\d{2}))\s*(?:€|&euro;)?~iu',
                $html,
                $mSelected
            )
        ) {
            return $this->normalizarImporte($mSelected['p']);
        }

        if (
            preg_match(
                '~Variant_activeVariant[\s\S]*?z-product-price__price-wrap">\s*<span[^>]*\bdata-zta=(["\'])reducedPriceAmount\1[^>]*>\s*(?<p>\d{1,3}(?:[.\s]\d{3})*(?:[.,]\d{2}))\s*(?:€|&euro;)?~iu',
                $html,
                $mActive
            )
        ) {
            return $this->normalizarImporte($mActive['p']);
        }

        if (
            preg_match(
                '~z-product-price__price-wrap">\s*<span[^>]*\bdata-zta=(["\'])reducedPriceAmount\1[^>]*>\s*(?<p>\d{1,3}(?:[.\s]\d{3})*(?:[.,]\d{2}))\s*(?:€|&euro;)?~iu',
                $html,
                $mFirst
            )
        ) {
            return $this->normalizarImporte($mFirst['p']);
        }

        return null;
    }

    private function extraerActiveVariantDesdeUrl(string $url): ?string
    {
        $query = (string) (parse_url($url, PHP_URL_QUERY) ?? '');
        if ($query === '') {
            return null;
        }

        parse_str($query, $params);
        $activeVariant = trim((string) ($params['activeVariant'] ?? ''));

        return $activeVariant !== '' ? $activeVariant : null;
    }

    private function extraerPrecioPorActiveVariant(string $html, string $activeVariant): ?float
    {
        if (
            preg_match(
                '~data-zta=(["\'])variant-id\1[^>]*>\s*' . preg_quote($activeVariant, '~') . '\s*</span>[\s\S]*?z-product-price__price-wrap">\s*<span[^>]*\bdata-zta=(["\'])reducedPriceAmount\2[^>]*>\s*(?<p>\d{1,3}(?:[.\s]\d{3})*(?:[.,]\d{2}))\s*(?:€|&euro;)?~iu',
                $html,
                $m
            )
        ) {
            return $this->normalizarImporte($m['p']);
        }

        if (!preg_match('~^(?<shop>\d+)\.(?<variant>\d+)$~', $activeVariant, $parts)) {
            return null;
        }

        $shop = $parts['shop'];
        $variant = $parts['variant'];

        if (
            preg_match(
                '~\bdata-shop-identifier=(["\'])' . preg_quote($shop, '~') . '\1[^>]*\bdata-variant-id=(["\'])' . preg_quote($variant, '~') . '\2[\s\S]*?z-product-price__price-wrap">\s*<span[^>]*\bdata-zta=(["\'])reducedPriceAmount\3[^>]*>\s*(?<p>\d{1,3}(?:[.\s]\d{3})*(?:[.,]\d{2}))\s*(?:€|&euro;)?~iu',
                $html,
                $mCart
            )
        ) {
            return $this->normalizarImporte($mCart['p']);
        }

        return null;
    }

    private function extraerPrecioPorVarianteId(string $html, string $varianteId): ?float
    {
        if (
            preg_match(
                '~\bdata-variant-id=(["\'])' . preg_quote($varianteId, '~') . '\1[\s\S]*?z-product-price__price-wrap">\s*<span[^>]*\bdata-zta=(["\'])reducedPriceAmount\2[^>]*>\s*(?<p>\d{1,3}(?:[.\s]\d{3})*(?:[.,]\d{2}))\s*(?:€|&euro;)?~iu',
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
    // Cron Neo Objetivos - listado de categoría por paginación (?p=N)
    // -------------------------------------------------------------------------

    public function tipoListadoCategoria(): ?string
    {
        return 'paginacion';
    }

    /**
     * Paginación: https://www.zooplus.es/shop/tienda_perros/pienso_perros?p=2
     * Para cuando redirige a la primera página o la paginación indica última página.
     */
    public function extraerProductosYSiguientePagina(string $html, string $urlPeticionActual): array
    {
        if ($this->esPaginaRedirigidaAPrimera($html, $urlPeticionActual)) {
            return [
                'urls_productos' => [],
                'siguiente_url'  => null,
            ];
        }

        $urlsProductos = $this->extraerUrlsProductosDesdeListado($html, $urlPeticionActual);
        $paginacion = $this->extraerPaginacionDesdeHtml($html, $urlPeticionActual);

        $siguienteUrl = null;
        if (count($urlsProductos) > 0 && !$this->esUltimaPagina($html, $urlPeticionActual, $paginacion)) {
            $paginaSiguiente = $paginacion !== null
                ? $paginacion['actual'] + 1
                : $this->extraerNumeroPaginaActual($urlPeticionActual) + 1;
            $siguienteUrl = $this->construirUrlSiguientePagina($urlPeticionActual, $paginaSiguiente);
        }

        return [
            'urls_productos' => $urlsProductos,
            'siguiente_url'  => $siguienteUrl,
        ];
    }

    /**
     * Si pedimos ?p=71 pero el HTML trae canonical de la primera página, hubo redirección.
     */
    private function esPaginaRedirigidaAPrimera(string $html, string $urlPeticionActual): bool
    {
        $paginaSolicitada = $this->extraerNumeroPaginaActual($urlPeticionActual);
        if ($paginaSolicitada <= 1) {
            return false;
        }

        $paginaCanonica = $this->extraerPaginaDesdeCanonical($html);

        return $paginaCanonica < $paginaSolicitada;
    }

    private function extraerPaginaDesdeCanonical(string $html): int
    {
        if (
            !preg_match(
                '~<link[^>]+rel=(["\'])canonical\1[^>]+href=(["\'])(?<u>[^"\']+)\2~i',
                $html,
                $m
            )
        ) {
            return 1;
        }

        return $this->extraerNumeroPaginaActual($m['u']);
    }

    /**
     * @return array{actual: int, total: int}|null
     */
    private function extraerPaginacionDesdeHtml(string $html, string $urlPeticionActual): ?array
    {
        $actual = null;
        $total = 1;

        if (
            preg_match(
                '~<a[^>]*\bclass=(["\'])[^"\']*\bz-pagination__page--current\b[^"\']*\1[^>]*\baria-current=(["\'])page\2[^>]*>\s*(?<n>\d+)\s*</a>~i',
                $html,
                $mActual
            )
        ) {
            $actual = max(1, (int) $mActual['n']);
        }

        if (
            preg_match_all(
                '~data-zta=(["\'])paginationListAnchor\1[^>]*\bhref=(["\'])[^"\']*[?&]p=(?<n>\d+)\2~i',
                $html,
                $mPaginas
            )
        ) {
            foreach ($mPaginas['n'] as $n) {
                $total = max($total, (int) $n);
            }
        }

        if (
            preg_match_all(
                '~<a[^>]*\bz-pagination__page\b[^>]*>\s*(?<n>\d+)\s*</a>~i',
                $html,
                $mNumeros
            )
        ) {
            foreach ($mNumeros['n'] as $n) {
                $total = max($total, (int) $n);
            }
        }

        if ($actual === null) {
            $actual = $this->extraerNumeroPaginaActual($urlPeticionActual);
        }

        if ($total <= 1 && $actual <= 1 && !preg_match('~data-zta=(["\'])PaginationUIC\1~i', $html)) {
            return null;
        }

        return [
            'actual' => $actual,
            'total'  => max($actual, $total),
        ];
    }

    /**
     * @param array{actual: int, total: int}|null $paginacion
     */
    private function esUltimaPagina(string $html, string $urlPeticionActual, ?array $paginacion): bool
    {
        if ($paginacion !== null && $paginacion['actual'] >= $paginacion['total']) {
            return true;
        }

        if (!preg_match('~data-zta=(["\'])paginationNext\1[^>]*\bhref=(["\'])[^"\']+\2~i', $html)) {
            $paginaSolicitada = $this->extraerNumeroPaginaActual($urlPeticionActual);

            return $paginaSolicitada > 1 || ($paginacion !== null && $paginacion['actual'] >= $paginacion['total']);
        }

        return false;
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
                '~<a[^>]*\bdata-zta=(["\'])product-info\1[^>]*\bhref=(["\'])(?<u>/shop/[^"\']+)\2~i',
                $html,
                $mInfo
            )
        ) {
            foreach ($mInfo['u'] as $u) {
                $u = trim((string) $u);
                if ($u !== '') {
                    $urls[] = $this->normalizarUrlCorta($u, $base);
                }
            }
        }

        if (
            preg_match_all(
                '~<a[^>]*\bhref=(["\'])(?<u>/shop/[^"\']+)\1[^>]*\bdata-zta=(["\'])product-info\3~i',
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

        $urls = array_values(array_unique(array_filter($urls)));

        return array_values(array_filter($urls, function (string $u) {
            return preg_match('~zooplus\.[a-z.]+/shop/~i', $u)
                && preg_match('~activeVariant=~i', $u);
        }));
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
        unset($params['page'], $params['pagina'], $params['pag']);
        $params['p'] = max(1, $paginaSiguiente);
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
        $host = $pu['host'] ?? 'www.zooplus.es';

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
