<?php

namespace App\Http\Controllers\Scraping\Tiendas;

use App\Http\Controllers\Scraping\Tiendas\PlantillaTiendaController;
use Illuminate\Http\JsonResponse;

/**
 * RED Computer (PrestaShop): precio en <span class="product-price current-price-value" content="115.91">.
 * Listado: enlaces <a class="thumbnail product-thumbnail" href="...-id.html">.
 * Paginación: ?page=2 (ej. /tarjetas-graficas-108?page=2).
 */
class RedcomputerController extends PlantillaTiendaController
{
    public function obtenerPrecio($url, $variante = null, $tienda = null, $oferta = null): JsonResponse
    {
        $resultado = $this->apiHTML->obtenerHTML($url, null, $tienda ? $tienda->api : null);

        if (!is_array($resultado) || empty($resultado['success']) || empty($resultado['html'])) {
            $msg = is_array($resultado) ? ($resultado['error'] ?? 'Error desconocido') : 'Respuesta inválida de la API';

            return response()->json(['success' => false, 'error' => 'No se pudo obtener el HTML de RED Computer: ' . $msg]);
        }

        $html = html_entity_decode((string) $resultado['html'], ENT_QUOTES | ENT_HTML5, 'UTF-8');

        $precio = $this->extraerPrecioFicha($html);
        if ($precio === null) {
            return response()->json(['success' => false, 'error' => 'No se encontró el precio (current-price-value / content) en el HTML.']);
        }

        return response()->json(['success' => true, 'precio' => $precio]);
    }

    /** Ficha: solo <span class="... current-price-value ..." content="..."> */
    private function extraerPrecioFicha(string $html): ?float
    {
        if (
            preg_match(
                '~<span\b[^>]*\bclass\s*=\s*["\'][^"\']*\bcurrent-price-value\b[^"\']*["\'][^>]*\bcontent\s*=\s*["\'](?<p>[\d.,]+)["\']~i',
                $html,
                $m
            )
        ) {
            $p = $this->normalizarImporte($m['p'] ?? '');
            if ($p !== null) {
                return $p;
            }
        }

        if (
            preg_match(
                '~<span\b[^>]*\bcontent\s*=\s*["\'](?<p>[\d.,]+)["\'][^>]*\bclass\s*=\s*["\'][^"\']*\bcurrent-price-value\b~i',
                $html,
                $m2
            )
        ) {
            $p = $this->normalizarImporte($m2['p'] ?? '');
            if ($p !== null) {
                return $p;
            }
        }

        return null;
    }

    private function normalizarImporte(string $importe): ?float
    {
        $importe = str_replace(["\xc2\xa0", "\xe2\x80\xaf", '&nbsp;'], ' ', $importe);
        $s = preg_replace('/[^\d\,\.]/u', '', $importe);
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

    public function tipoListadoCategoria(): ?string
    {
        return 'paginacion';
    }

    public function extraerProductosYSiguientePagina(string $html, string $urlPeticionActual): array
    {
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

    /**
     * @return array<int, string>
     */
    private function extraerUrlsProductosDesdeListado(string $html): array
    {
        $prev = libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        @$dom->loadHTML('<?xml encoding="UTF-8">' . $html);
        libxml_clear_errors();
        libxml_use_internal_errors($prev);

        $xp = new \DOMXPath($dom);
        $urls = [];
        $nodes = $xp->query("//a[contains(concat(' ', normalize-space(@class), ' '), ' product-thumbnail ')]");
        if ($nodes !== false) {
            foreach ($nodes as $a) {
                if (!$a instanceof \DOMElement) {
                    continue;
                }
                $href = trim($a->getAttribute('href'));
                if ($href === '') {
                    continue;
                }
                if (!preg_match('~^https?://(www\.)?redcomputer\.es/.+-\d+\.html$~i', $href)) {
                    continue;
                }
                $urls[] = $href;
            }
        }

        return array_values(array_unique($urls));
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

        if (preg_match('~<link[^>]+href=(["\'])(?<u>[^"\']+)\1[^>]+rel\s*=\s*["\']next["\']~i', $html, $m)) {
            $u = trim((string) ($m['u'] ?? ''));
            if ($u !== '') {
                $u = $this->normalizarUrlCorta($u, $base);
                $n = $this->extraerNumeroPaginaActual($u);
                if ($n > $paginaActual) {
                    return $u;
                }
            }
        }

        $candidatas = [];

        if (
            preg_match_all(
                '~href=(["\'])(?<u>[^"\']*[?&](?:pagina|page|paged|product-page)=(?<n>\d+)[^"\']*)\1~i',
                $html,
                $mm,
                PREG_SET_ORDER
            )
        ) {
            foreach ($mm as $row) {
                $u = trim((string) ($row['u'] ?? ''));
                $n = isset($row['n']) ? (int) $row['n'] : null;
                if ($u === '' || !$n) {
                    continue;
                }
                $candidatas[] = ['page' => $n, 'url' => $u];
            }
        }

        if (preg_match_all('~href=(["\'])(?<u>[^"\']*\/page\/(?<n>\d+)\/?[^"\']*)\1~i', $html, $m2, PREG_SET_ORDER)) {
            foreach ($m2 as $row) {
                $u = trim((string) ($row['u'] ?? ''));
                $n = isset($row['n']) ? (int) $row['n'] : null;
                if ($u === '' || !$n) {
                    continue;
                }
                $candidatas[] = ['page' => $n, 'url' => $u];
            }
        }

        if (empty($candidatas)) {
            return null;
        }

        $siguientes = array_filter($candidatas, function ($c) use ($paginaActual) {
            return $c['page'] > $paginaActual;
        });

        if (empty($siguientes)) {
            return null;
        }

        usort($siguientes, fn ($a, $b) => $a['page'] <=> $b['page']);

        return $this->normalizarUrlCorta($siguientes[0]['url'], $base);
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
        foreach (['page', 'pagina', 'paged', 'product-page', 'p'] as $k) {
            unset($params[$k]);
        }
        if ($pagina > 1) {
            $params['page'] = $pagina;
        }

        $queryString = http_build_query($params, '', '&', PHP_QUERY_RFC3986);

        $url = $scheme . '://' . $host . $port . $path . ($queryString !== '' ? '?' . $queryString : '');
        if (!empty($parts['fragment'])) {
            $url .= '#' . $parts['fragment'];
        }

        return $url;
    }

    private function extraerNumeroPaginaActual(string $urlPeticionActual): int
    {
        if (preg_match('~[?&]pagina=(\d+)~i', $urlPeticionActual, $m)) {
            return max(1, (int) ($m[1] ?? 1));
        }
        if (preg_match('~[?&](?:page|paged|product-page|p)=(\d+)~i', $urlPeticionActual, $m)) {
            return max(1, (int) ($m[1] ?? 1));
        }
        if (preg_match('~/page\/(\d+)\/?~i', $urlPeticionActual, $m)) {
            return max(1, (int) ($m[1] ?? 1));
        }

        return 1;
    }

    private function obtenerBaseUrlDesdeUrlPeticion(string $urlPeticionActual): string
    {
        $pu = parse_url($urlPeticionActual);
        $scheme = $pu['scheme'] ?? 'https';
        $host = $pu['host'] ?? 'www.redcomputer.es';

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
