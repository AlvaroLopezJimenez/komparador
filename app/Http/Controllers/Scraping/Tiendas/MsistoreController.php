<?php

namespace App\Http\Controllers\Scraping\Tiendas;

use App\Http\Controllers\Scraping\Tiendas\PlantillaTiendaController;
use App\Models\OfertaProducto;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

/**
 * MSI Store (Shopify, es-store.msi.com):
 * - Precio en ficha: <strong class="price__current">1.599,90€</strong> dentro de product-info__price.
 * - Sin stock: bloque price__no-variant visible con texto "Agotado".
 * - Listado categoría: data-product-url="/products/..." en cada product-card.
 * - Paginación Shopify: ?page=N (ej. /collections/tarjetas-graficas?page=2).
 */
class MsistoreController extends PlantillaTiendaController
{
    public function obtenerPrecio($url, $variante = null, $tienda = null, $oferta = null): JsonResponse
    {
        $resultado = $this->apiHTML->obtenerHTML($url, null, $tienda ? $tienda->api : null);

        if (!is_array($resultado) || empty($resultado['success']) || empty($resultado['html'])) {
            $msg = is_array($resultado) ? ($resultado['error'] ?? 'Error desconocido') : 'Respuesta inválida de la API';

            return response()->json(['success' => false, 'error' => 'No se pudo obtener el HTML de MSI Store: ' . $msg]);
        }

        $html = html_entity_decode((string) $resultado['html'], ENT_QUOTES | ENT_HTML5, 'UTF-8');

        if ($this->esSinStock($html)) {
            if ($oferta && $oferta instanceof OfertaProducto) {
                $oferta->update(['mostrar' => 'no']);
                DB::table('avisos')->insert([
                    'texto_aviso'     => 'Sin stock 1a vez - Generado automaticamente',
                    'fecha_aviso'     => now()->addDays(4),
                    'user_id'         => 1,
                    'avisoable_type'  => OfertaProducto::class,
                    'avisoable_id'    => $oferta->id,
                    'oculto'          => 0,
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ]);
            }

            return response()->json(['success' => false, 'error' => 'Producto agotado en MSI Store.']);
        }

        $precio = $this->extraerPrecioDesdeBloqueProducto($html);
        if ($precio === null) {
            $precio = $this->extraerPrecioDesdeOgMeta($html);
        }

        if ($precio === null) {
            return response()->json(['success' => false, 'error' => 'No se encontró price__current en el HTML de MSI Store.']);
        }

        return response()->json(['success' => true, 'precio' => $precio]);
    }

    /**
     * Agotado cuando el bloque price__no-variant de la ficha está visible (sin hidden).
     */
    private function esSinStock(string $html): bool
    {
        $bloqueProducto = $this->extraerBloquePrecioProducto($html);
        if ($bloqueProducto === null || $bloqueProducto === '') {
            return false;
        }

        if (
            preg_match(
                '~<div[^>]*\bclass=(["\'])[^"\']*\bprice__no-variant\b[^"\']*\1(?![^>]*\bhidden\b)[^>]*>[\s\S]*?<strong[^>]*\bclass=(["\'])[^"\']*\bprice__current\b[^"\']*\2[^>]*>\s*Agotado\s*<~i',
                $bloqueProducto
            )
        ) {
            return true;
        }

        if (
            preg_match(
                '~<div[^>]*\bclass=(["\'])[^"\']*\bprice__default\b[^"\']*\1[^>]*\bhidden\b[^>]*>~i',
                $bloqueProducto
            )
            && preg_match(
                '~<div[^>]*\bclass=(["\'])[^"\']*\bprice__no-variant\b[^"\']*\1(?![^>]*\bhidden\b)[^>]*>~i',
                $bloqueProducto
            )
        ) {
            return true;
        }

        return false;
    }

    /**
     * Precio solo del bloque product-info__price (evita recomendaciones y listados embebidos).
     */
    private function extraerPrecioDesdeBloqueProducto(string $html): ?float
    {
        $bloqueProducto = $this->extraerBloquePrecioProducto($html);
        if ($bloqueProducto === null || $bloqueProducto === '') {
            return null;
        }

        $precioBase = '<div[^>]*\bclass=(["\'])[^"\']*\bprice__default\b[^"\']*\1(?![^>]*\bhidden\b)[^>]*>[\s\S]*?';

        if (
            preg_match(
                '~' . $precioBase . '<strong[^>]*\bclass=(["\'])[^"\']*\bprice__current\b[^"\']*\2[^>]*>\s*(?<p>[0-9][0-9\.,\s]*[0-9])\s*€?~i',
                $bloqueProducto,
                $mStrong
            )
        ) {
            $p = $this->normalizarImporte($mStrong['p'] ?? '');
            if ($p !== null) {
                return $p;
            }
        }

        return null;
    }

    private function extraerBloquePrecioProducto(string $html): ?string
    {
        if (
            preg_match(
                '~<div[^>]*\bclass=(["\'])[^"\']*\bproduct-info__price\b[^"\']*\1[^>]*>(?<b>[\s\S]*?)</div>\s*(?:<div[^>]*\bclass=(["\'])[^"\']*\bproduct-info__block\b|\s*<product-form\b|\s*<form\b)~i',
                $html,
                $m
            )
        ) {
            return $m['b'] ?? null;
        }

        if (
            preg_match(
                '~<div[^>]*\bclass=(["\'])[^"\']*\bproduct-info__block\b[^"\']*\bproduct-price\b[^"\']*\1[^>]*>(?<b>[\s\S]*?)</div>\s*</div>~i',
                $html,
                $m2
            )
        ) {
            return $m2['b'] ?? null;
        }

        return null;
    }

    private function extraerPrecioDesdeOgMeta(string $html): ?float
    {
        if (
            preg_match(
                '~<meta[^>]*\bproperty\s*=\s*["\']og:price:amount["\'][^>]*\bcontent\s*=\s*["\'](?<p>[\d\.,]+)["\'][^>]*>~i',
                $html,
                $m
            )
        ) {
            return $this->normalizarImporte($m['p'] ?? '');
        }

        if (
            preg_match(
                '~<meta[^>]*\bcontent\s*=\s*["\'](?<p>[\d\.,]+)["\'][^>]*\bproperty\s*=\s*["\']og:price:amount["\'][^>]*>~i',
                $html,
                $m2
            )
        ) {
            return $this->normalizarImporte($m2['p'] ?? '');
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
    // Cron Neo Objetivos - listado de categoría por paginación (?page=N)
    // -------------------------------------------------------------------------

    public function tipoListadoCategoria(): ?string
    {
        return 'paginacion';
    }

    /**
     * URLs desde data-product-url="/products/..." en cada product-card.
     * Paginación Shopify: ?page=N mientras haya productos en la página actual.
     */
    public function extraerProductosYSiguientePagina(string $html, string $urlPeticionActual): array
    {
        if ($this->esListadoVacio($html)) {
            return [
                'urls_productos' => [],
                'siguiente_url'  => null,
            ];
        }

        $urlsProductos = $this->extraerUrlsProductosDesdeListado($html);

        $base = $this->obtenerBaseUrlDesdeUrlPeticion($urlPeticionActual);
        foreach ($urlsProductos as $i => $u) {
            $urlsProductos[$i] = $this->normalizarUrlCorta($u, $base);
        }
        $urlsProductos = array_values(array_unique(array_filter($urlsProductos)));

        $siguienteUrl = $this->extraerSiguientePaginaUrl($html, $urlPeticionActual, $base);

        if ($siguienteUrl === null && count($urlsProductos) > 0) {
            $siguienteUrl = $this->construirUrlPagina($urlPeticionActual, $this->extraerNumeroPaginaActual($urlPeticionActual) + 1);
        }

        return [
            'urls_productos' => $urlsProductos,
            'siguiente_url'  => $siguienteUrl,
        ];
    }

    private function esListadoVacio(string $html): bool
    {
        return $this->extraerUrlsProductosDesdeListado($html) === [];
    }

    /**
     * @return array<int, string>
     */
    private function extraerUrlsProductosDesdeListado(string $html): array
    {
        $urls = [];

        if (
            preg_match_all(
                '~\bdata-product-url=(["\'])(?<u>/products/[^"\']+)\1~i',
                $html,
                $m
            )
        ) {
            foreach ($m['u'] as $u) {
                $u = trim((string) $u);
                if ($u !== '') {
                    $urls[] = $u;
                }
            }
        }

        if ($urls === []) {
            if (
                preg_match_all(
                    '~<product-card\b[^>]*>[\s\S]*?\bhref=(["\'])(?<u>/products/[^"\']+)\1~i',
                    $html,
                    $mCard
                )
            ) {
                foreach ($mCard['u'] as $u) {
                    $u = trim((string) $u);
                    if ($u !== '') {
                        $urls[] = $u;
                    }
                }
            }
        }

        return array_values(array_unique(array_filter($urls, function (string $u) {
            return preg_match('~^/products/[^/]+/?$~i', $u) === 1;
        })));
    }

    private function extraerSiguientePaginaUrl(string $html, string $urlPeticionActual, string $base): ?string
    {
        $paginaActual = $this->extraerNumeroPaginaActual($urlPeticionActual);

        if (preg_match('~<link[^>]+rel=(["\'])next\1[^>]+href=(["\'])(?<u>[^"\']+)\2~i', $html, $m)) {
            $u = trim((string) ($m['u'] ?? ''));
            if ($u !== '') {
                $u = $this->normalizarUrlCorta($u, $base);
                $n = $this->extraerNumeroPaginaActual($u);
                if ($n > $paginaActual) {
                    return $u;
                }
            }
        }

        if (preg_match('~<link[^>]+href=(["\'])(?<u>[^"\']+)\1[^>]+rel\s*=\s*["\']next["\']~i', $html, $m2)) {
            $u = trim((string) ($m2['u'] ?? ''));
            if ($u !== '') {
                $u = $this->normalizarUrlCorta($u, $base);
                $n = $this->extraerNumeroPaginaActual($u);
                if ($n > $paginaActual) {
                    return $u;
                }
            }
        }

        if (
            preg_match_all(
                '~href=(["\'])(?<u>[^"\']*[?&]page=(?<n>\d+)[^"\']*)\1~i',
                $html,
                $mm,
                PREG_SET_ORDER
            )
        ) {
            $candidatas = [];
            foreach ($mm as $row) {
                $u = trim((string) ($row['u'] ?? ''));
                $n = isset($row['n']) ? (int) $row['n'] : null;
                if ($u === '' || !$n || $n <= $paginaActual) {
                    continue;
                }
                $candidatas[] = ['page' => $n, 'url' => $u];
            }

            if ($candidatas !== []) {
                usort($candidatas, fn ($a, $b) => $a['page'] <=> $b['page']);

                return $this->normalizarUrlCorta($candidatas[0]['url'], $base);
            }
        }

        return null;
    }

    private function construirUrlPagina(string $urlPeticionActual, int $pagina): ?string
    {
        if ($pagina < 1) {
            return null;
        }

        $parts = parse_url($urlPeticionActual);
        if ($parts === false || empty($parts['host'])) {
            return null;
        }

        $scheme = $parts['scheme'] ?? 'https';
        $host = $parts['host'];
        $port = isset($parts['port']) ? ':' . (int) $parts['port'] : '';
        $path = $parts['path'] ?? '/';

        $query = $parts['query'] ?? '';
        parse_str($query, $params);
        unset($params['page']);
        $params['page'] = $pagina;

        $queryString = http_build_query($params, '', '&', PHP_QUERY_RFC3986);

        $url = $scheme . '://' . $host . $port . $path . ($queryString !== '' ? '?' . $queryString : '');
        if (!empty($parts['fragment'])) {
            $url .= '#' . $parts['fragment'];
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
        $host = $pu['host'] ?? 'es-store.msi.com';

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
