<?php

namespace App\Http\Controllers\Scraping\Tiendas;

use App\Http\Controllers\Scraping\Tiendas\PlantillaTiendaController;
use App\Models\OfertaProducto;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

/**
 * Pixmania: precio solo si la variante es "Nuevo" (variation-grade-name → variation-grade-price).
 * Categoría: ?page=N — listado: enlaces producttile href /es/es/…-ID.html
 *
 * @see https://www.pixmania.com/
 */
class PixmaniaController extends PlantillaTiendaController
{
    public function obtenerPrecio($url, $variante = null, $tienda = null, $oferta = null): JsonResponse
    {
        $resultado = $this->apiHTML->obtenerHTML($url, null, $tienda ? $tienda->api : null);

        if (!is_array($resultado) || empty($resultado['success']) || empty($resultado['html'])) {
            $msg = is_array($resultado) ? ($resultado['error'] ?? 'Error desconocido') : 'Respuesta inválida de la API';

            return response()->json(['success' => false, 'error' => 'No se pudo obtener el HTML de Pixmania: ' . $msg]);
        }

        $html = html_entity_decode((string) $resultado['html'], ENT_QUOTES | ENT_HTML5, 'UTF-8');

        if ($this->esSinStock($html)) {
            if ($oferta && $oferta instanceof OfertaProducto) {
                $oferta->update(['mostrar' => 'no']);
                DB::table('avisos')->insertGetId([
                    'texto_aviso'     => 'SIN STOCK 1A VEZ - GENERADO AUTOMÁTICAMENTE',
                    'fecha_aviso'     => now()->addDays(4),
                    'user_id'         => 1,
                    'avisoable_type'  => OfertaProducto::class,
                    'avisoable_id'    => $oferta->id,
                    'oculto'          => 0,
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ]);
            }

            return response()->json(['success' => false, 'error' => 'Producto sin stock en Pixmania.']);
        }

        $precio = $this->extraerPrecioDesdeHtml($html);

        if ($precio === null) {
            return response()->json([
                'success' => false,
                'error'   => 'No se encontró el precio "Nuevo" en el HTML de Pixmania (u otras condiciones).',
            ]);
        }

        return response()->json(['success' => true, 'precio' => $precio]);
    }

    private function esSinStock(string $html): bool
    {
        return preg_match(
            '~<div[^>]*>\s*Este artículo ya no está disponible para su compra\s*</div>~iu',
            $html
        ) === 1;
    }

    /**
     * Solo variante con texto exacto "Nuevo" en variation-grade-name; precio en variation-grade-price.
     */
    private function extraerPrecioDesdeHtml(string $html): ?float
    {
        if (
            preg_match(
                '~<div[^>]*\bvariation-grade-name\b[^>]*>\s*Nuevo\s*</div>\s*<div[^>]*\bvariation-grade-price\b[^>]*>\s*([^<]+?)\s*</div>~isu',
                $html,
                $m
            )
        ) {
            return $this->normalizarImporteTexto(trim($m[1]));
        }

        return null;
    }

    private function normalizarImporteTexto(string $importe): ?float
    {
        $importe = str_replace(["\xc2\xa0", "\xe2\x80\xaf", '&nbsp;'], ' ', $importe);
        $importe = preg_replace('/\s+/u', '', $importe);
        $s = preg_replace('/[^\d\,\.]/u', '', $importe);
        if ($s === null || $s === '') {
            return null;
        }

        $tieneComa  = strpos($s, ',') !== false;
        $tienePunto = strpos($s, '.') !== false;

        if ($tieneComa && $tienePunto) {
            $lastComa = strrpos($s, ',');
            $lastDot  = strrpos($s, '.');
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
    // Cron Neo Objetivos — ?page=N
    // -------------------------------------------------------------------------

    public function tipoListadoCategoria(): ?string
    {
        return 'paginacion';
    }

    /**
     * Paginación guiada por la propia página de Pixmania:
     * - <link rel="canonical"> = URL actual
     * - <link rel="next">      = URL siguiente
     *
     * Solo seguimos paginando cuando rel="next" apunta a una página estrictamente posterior
     * a rel="canonical". Si no hay next válido, se detiene.
     *
     * @see https://www.pixmania.com/es/es/informatica/componentes-y-piezas-de-cambio/tarjetas-graficas.html
     */
    public function extraerProductosYSiguientePagina(string $html, string $urlPeticionActual): array
    {
        $urlsProductos = $this->extraerUrlsProductosListado($html);

        $base = $this->obtenerBaseUrlDesdeUrlPeticion($urlPeticionActual);
        foreach ($urlsProductos as $i => $u) {
            $urlsProductos[$i] = $this->normalizarUrlCorta($u, $base);
        }
        $urlsProductos = array_values(array_unique(array_filter($urlsProductos)));

        $siguienteUrl = $this->resolverSiguientePaginaDesdeCanonicalYNext($html, $urlPeticionActual, $base, $urlsProductos);

        return [
            'urls_productos' => $urlsProductos,
            'siguiente_url'  => $siguienteUrl,
        ];
    }

    /**
     * @return array<int, string>
     */
    private function extraerUrlsProductosListado(string $html): array
    {
        $urls = [];

        $patrones = [
            '~href="(/es/es/[^"]+\.html)"[^>]*\bclass="[^"]*\bproducttile\b~i',
            '~\bclass="[^"]*\bproducttile\b[^"]*"[^>]*href="(/es/es/[^"]+\.html)"~i',
        ];

        foreach ($patrones as $pat) {
            if (preg_match_all($pat, $html, $m)) {
                foreach ($m[1] as $rel) {
                    $rel = trim((string) $rel);
                    if ($rel !== '' && $this->esUrlFichaProductoPixmania($rel)) {
                        $urls[] = $rel;
                    }
                }
            }
        }

        return array_values(array_unique($urls));
    }

    /** Excluye URLs de categoría sin sufijo numérico de producto. */
    private function esUrlFichaProductoPixmania(string $path): bool
    {
        if (!str_ends_with(strtolower($path), '.html')) {
            return false;
        }

        return (bool) preg_match('~^/es/es/.+-\d+\.html$~i', $path);
    }

    /**
     * @param array<int, string> $urlsProductos
     */
    private function resolverSiguientePaginaDesdeCanonicalYNext(
        string $html,
        string $urlPeticionActual,
        string $base,
        array $urlsProductos
    ): ?string {
        if (count($urlsProductos) === 0) {
            return null;
        }

        $canonical = $this->extraerHrefLinkRel($html, 'canonical');
        $next = $this->extraerHrefLinkRel($html, 'next');
        if ($next === null) {
            return null;
        }

        $canonicalUrl = $this->normalizarUrlCorta($canonical ?? $urlPeticionActual, $base);
        $nextUrl = $this->normalizarUrlCorta($next, $base);

        if ($nextUrl === '' || $canonicalUrl === '' || $nextUrl === $canonicalUrl) {
            return null;
        }

        $paginaCanonical = $this->extraerNumeroPaginaActual($canonicalUrl);
        $paginaNext = $this->extraerNumeroPaginaActual($nextUrl);

        return $paginaNext > $paginaCanonical ? $nextUrl : null;
    }

    private function extraerHrefLinkRel(string $html, string $rel): ?string
    {
        $relEscaped = preg_quote($rel, '~');
        $patrones = [
            '~<link[^>]+rel=(["\'])' . $relEscaped . '\1[^>]+href=(["\'])(?<u>[^"\']+)\2~i',
            '~<link[^>]+href=(["\'])(?<u>[^"\']+)\1[^>]+rel\s*=\s*(["\'])' . $relEscaped . '\3~i',
        ];

        foreach ($patrones as $pat) {
            if (preg_match($pat, $html, $m)) {
                $u = trim((string) ($m['u'] ?? ''));
                if ($u !== '') {
                    return $u;
                }
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
        unset($params['page'], $params['paged'], $params['pagina'], $params['product-page']);
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
        if (preg_match('~[?&](?:paged|pagina|product-page)=(\d+)~i', $urlPeticionActual, $m)) {
            return max(1, (int) ($m[1] ?? 1));
        }
        if (preg_match('~/page/(\d+)/?~i', $urlPeticionActual, $m)) {
            return max(1, (int) ($m[1] ?? 1));
        }

        return 1;
    }

    private function obtenerBaseUrlDesdeUrlPeticion(string $urlPeticionActual): string
    {
        $pu = parse_url($urlPeticionActual);
        $scheme = $pu['scheme'] ?? 'https';
        $host = $pu['host'] ?? 'www.pixmania.com';

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
