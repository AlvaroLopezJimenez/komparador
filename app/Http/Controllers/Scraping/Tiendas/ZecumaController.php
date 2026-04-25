<?php

namespace App\Http\Controllers\Scraping\Tiendas;

use App\Http\Controllers\Scraping\Tiendas\PlantillaTiendaController;
use App\Models\OfertaProducto;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ZecumaController extends PlantillaTiendaController
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

        if ($this->esProductoYaNoDisponible($html)) {
            if ($oferta && $oferta instanceof OfertaProducto) {
                $oferta->update(['mostrar' => 'no']);

                DB::table('avisos')->insert([
                    'texto_aviso'    => '404 - 1a vez GENERADO AUTOMATICAMENTE',
                    'fecha_aviso'    => now()->addDays(4),
                    'user_id'        => 1,
                    'avisoable_type' => OfertaProducto::class,
                    'avisoable_id'   => $oferta->id,
                    'oculto'         => 0,
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ]);
            }

            return response()->json(['success' => false, 'error' => 'Producto no disponible']);
        }

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

        $precio = $this->extraerPrecioDesdeCurrentPriceValue($html);
        if ($precio === null) {
            return response()->json([
                'success' => false,
                'error'   => 'No se pudo encontrar el precio en la pagina de Zecuma',
            ]);
        }

        return response()->json(['success' => true, 'precio' => $precio]);
    }

    /**
     * Ej.: <li>Este producto ya no esta disponible.</li>
     */
    private function esProductoYaNoDisponible(string $html): bool
    {
        return strpos($html, 'Este producto ya no esta disponible') !== false
            || strpos($html, 'Este producto ya no está disponible') !== false;
    }

    /**
     * Solo el mensaje en datos embebidos (p. ej. availability_message en JSON).
     * El badge .product-unavailable con texto "Fuera de stock" aparece en el HTML de todas las fichas.
     */
    private function esSinStock(string $html): bool
    {
        return (bool) preg_match(
            '/availability_message\s*(?:"|&quot;)\s*:\s*(?:"|&quot;)\s*Fuera de stock\s*(?:"|&quot;)/i',
            $html
        );
    }

    /**
     * Extrae precio desde:
     * <span class="current-price"><span class="product-price current-price-value" content="459.66">
     */
    private function extraerPrecioDesdeCurrentPriceValue(string $html): ?float
    {
        // Principal: atributo content en span con clase product-price current-price-value.
        if (
            preg_match(
                '~<span[^>]*\bclass=(["\'])[^"\']*\bproduct-price\b[^"\']*\bcurrent-price-value\b[^"\']*\1[^>]*\bcontent=(["\'])(?<p>[0-9]+(?:[.,][0-9]+)?)\2~i',
                $html,
                $m
            )
        ) {
            $p = $m['p'] ?? null;
            if ($p !== null) {
                return $this->normalizarImporte($p);
            }
        }

        // Fallback: número visible dentro del span current-price-value.
        if (
            preg_match(
                '~<span[^>]*\bclass=(["\'])[^"\']*\bcurrent-price-value\b[^"\']*\1[^>]*>\s*(?<p>[0-9][0-9\.\,\s]*[0-9])~i',
                $html,
                $m2
            )
        ) {
            $p = $m2['p'] ?? null;
            if ($p !== null) {
                return $this->normalizarImporte($p);
            }
        }

        return null;
    }

    /**
     * Normaliza "459.66", "459,66" o "1.459,66" a float.
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
     * Extrae URLs de producto y construye siguiente URL como page+1
     * mientras en la pagina actual haya productos.
     */
    public function extraerProductosYSiguientePagina(string $html, string $urlPeticionActual): array
    {
        $urlsProductos = [];
        $base = $this->obtenerBaseUrlDesdeUrlPeticion($urlPeticionActual);

        // Principal: URL de producto en el título:
        // <h2 class="h3 product-title"><a href="...">...</a></h2>
        if (
            preg_match_all(
                '~<h2[^>]*\bproduct-title\b[^>]*>\s*<a[^>]*\bhref=(["\'])(?<u>[^"\']+)\1~i',
                $html,
                $mTitle
            )
        ) {
            foreach ($mTitle['u'] as $u) {
                $u = trim((string) $u);
                if ($u === '') {
                    continue;
                }
                $urlsProductos[] = $this->normalizarUrlCorta($u, $base);
            }
        }

        // Fallback: thumbnail product-thumbnail.
        if (
            preg_match_all(
                '~<a[^>]*\bhref=(["\'])(?<u>[^"\']+)\1[^>]*\bclass=(["\'])[^"\']*\bthumbnail\b[^"\']*\bproduct-thumbnail\b[^"\']*\3~i',
                $html,
                $mThumb
            )
        ) {
            foreach ($mThumb['u'] as $u) {
                $u = trim((string) $u);
                if ($u === '') {
                    continue;
                }
                $urlsProductos[] = $this->normalizarUrlCorta($u, $base);
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
        $host = $pu['host'] ?? 'zecuma.com';

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

