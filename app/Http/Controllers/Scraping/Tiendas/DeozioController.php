<?php

namespace App\Http\Controllers\Scraping\Tiendas;

use App\Http\Controllers\Scraping\Tiendas\PlantillaTiendaController;
use App\Models\OfertaProducto;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DeozioController extends PlantillaTiendaController
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

        $precio = $this->extraerPrecioDesdeBloqueProducto($html);
        if ($precio === null) {
            return response()->json([
                'success' => false,
                'error'   => 'No se pudo encontrar el precio en la pagina de Deozio',
            ]);
        }

        return response()->json(['success' => true, 'precio' => $precio]);
    }

    /**
     * Stub de sin stock para Deozio (pendiente de definir patron exacto).
     */
    private function esSinStock(string $html): bool
    {
        return false;
    }

    /**
     * Extrae precio SOLO del bloque de ficha:
     * <div class="product-info__block product-info__block--sm product-price"> ... <strong class="price__current">€47,18</strong>
     * para evitar capturas de price__current en otros módulos.
     */
    private function extraerPrecioDesdeBloqueProducto(string $html): ?float
    {
        $bloqueProducto = null;

        if (
            preg_match(
                '~<div[^>]*\bclass=(["\'])[^"\']*\bproduct-info__block\b[^"\']*\bproduct-info__block--sm\b[^"\']*\bproduct-price\b[^"\']*\1[^>]*>(?<b>[\s\S]*?)</div>\s*</div>~i',
                $html,
                $m
            )
        ) {
            $bloqueProducto = $m['b'] ?? null;
        }

        // Fallback por si cambia el orden exacto de clases del bloque.
        if ($bloqueProducto === null && preg_match(
            '~<div[^>]*\bclass=(["\'])(?=[^"\']*\bproduct-info__block\b)(?=[^"\']*\bproduct-price\b)[^"\']*\1[^>]*>(?<b>[\s\S]*?)</div>\s*</div>~i',
            $html,
            $m2
        )) {
            $bloqueProducto = $m2['b'] ?? null;
        }

        if ($bloqueProducto === null || $bloqueProducto === '') {
            return null;
        }

        if (
            preg_match(
                '~<div[^>]*\bclass=(["\'])[^"\']*\bproduct-info__price\b[^"\']*\1[^>]*>[\s\S]*?<div[^>]*\bclass=(["\'])[^"\']*\bprice__default\b[^"\']*\2[^>]*>[\s\S]*?<strong[^>]*\bclass=(["\'])[^"\']*\bprice__current\b[^"\']*\3[^>]*>\s*€?\s*(?<p>[0-9][0-9\.,\s]*[0-9])~i',
                $bloqueProducto,
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
     * Normaliza "47,18", "47.18" o "1.047,18" a float.
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
    // Cron Neo Objetivos - listado de categoria por paginacion (?page=N)
    // -------------------------------------------------------------------------

    public function tipoListadoCategoria(): ?string
    {
        return 'paginacion';
    }

    /**
     * Extrae URLs de cada <li class="js-pagination-result"> tomando:
     * <a class="btn btn--secondary quick-add-view-btn" href="...">Ver información</a>
     */
    public function extraerProductosYSiguientePagina(string $html, string $urlPeticionActual): array
    {
        if ($this->esListadoVacio($html)) {
            return [
                'urls_productos' => [],
                'siguiente_url'  => null,
            ];
        }

        $urlsProductos = [];
        $base = $this->obtenerBaseUrlDesdeUrlPeticion($urlPeticionActual);

        if (
            preg_match_all(
                '~<li[^>]*\bjs-pagination-result\b[^>]*>(?<block>[\s\S]*?)</li>~i',
                $html,
                $items
            )
        ) {
            foreach ($items['block'] as $block) {
                if (
                    preg_match(
                        '~<a[^>]*\bhref=(["\'])(?<u>[^"\']+)\1[^>]*\bclass=(["\'])[^"\']*\bquick-add-view-btn\b[^"\']*\3~i',
                        $block,
                        $m
                    )
                ) {
                    $u = trim((string) ($m['u'] ?? ''));
                    if ($u !== '') {
                        $urlsProductos[] = $this->normalizarUrlCorta($u, $base);
                    }
                }
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

    /**
     * Shopify collection sin resultados.
     */
    private function esListadoVacio(string $html): bool
    {
        return (bool) preg_match('~No\s+se\s+han\s+encontrado\s+productos~iu', $html);
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
        $host = $pu['host'] ?? 'deozio.com';

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
