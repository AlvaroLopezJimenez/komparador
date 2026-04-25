<?php

namespace App\Http\Controllers\Scraping\Tiendas;

use App\Models\OfertaProducto;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class TronooController extends PlantillaTiendaController
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

        $precio = $this->extraerPrecioDesdeBloquePrincipal($html);
        if ($precio === null) {
            return response()->json([
                'success' => false,
                'error'   => 'No se pudo encontrar el precio en la pagina de Tronoo',
            ]);
        }

        return response()->json(['success' => true, 'precio' => $precio]);
    }

    /**
     * Stub de sin stock (pendiente de definir patron exacto para Tronoo).
     */
    private function esSinStock(string $html): bool
    {
        return false;
    }

    /**
     * Precio SOLO del bloque principal del producto:
     * et_pb_wc_title + et_pb_wc_price -> p.price -> .woocommerce-Price-amount.amount -> bdi
     * para evitar capturar precios de "Productos relacionados".
     */
    private function extraerPrecioDesdeBloquePrincipal(string $html): ?float
    {
        if (
            preg_match(
                '~<div[^>]*\bclass=(["\'])[^"\']*\bet_pb_wc_title\b[^"\']*\1[^>]*>[\s\S]*?</div>\s*</div>\s*<div[^>]*\bclass=(["\'])[^"\']*\bet_pb_wc_price\b[^"\']*\2[^>]*>[\s\S]*?<p[^>]*\bclass=(["\'])[^"\']*\bprice\b[^"\']*\3[^>]*>[\s\S]*?<span[^>]*\bclass=(["\'])[^"\']*\bwoocommerce-Price-amount\b[^"\']*\bamount\b[^"\']*\4[^>]*>[\s\S]*?<bdi>\s*(?<p>[0-9][0-9\.,\s]*[0-9])~i',
                $html,
                $m
            )
        ) {
            return $this->normalizarImporte($m['p'] ?? '');
        }

        // Fallback: dentro de et_pb_wc_price (sin depender del bloque de título).
        if (
            preg_match(
                '~<div[^>]*\bclass=(["\'])[^"\']*\bet_pb_wc_price\b[^"\']*\1[^>]*>[\s\S]*?<p[^>]*\bclass=(["\'])[^"\']*\bprice\b[^"\']*\2[^>]*>[\s\S]*?<span[^>]*\bclass=(["\'])[^"\']*\bwoocommerce-Price-amount\b[^"\']*\bamount\b[^"\']*\3[^>]*>[\s\S]*?<bdi>\s*(?<p>[0-9][0-9\.,\s]*[0-9])~i',
                $html,
                $m2
            )
        ) {
            return $this->normalizarImporte($m2['p'] ?? '');
        }

        return null;
    }

    /**
     * Normaliza "365,00", "365.00" o "1.365,00" a float.
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
    // Cron Neo Objetivos - listado de categoria por paginacion (/page/N/)
    // -------------------------------------------------------------------------

    public function tipoListadoCategoria(): ?string
    {
        return 'paginacion';
    }

    /**
     * URLs de producto desde:
     * <li class="product ..."><a class="woocommerce-LoopProduct-link ..." href="...">
     */
    public function extraerProductosYSiguientePagina(string $html, string $urlPeticionActual): array
    {
        $urlsProductos = [];
        $base = $this->obtenerBaseUrlDesdeUrlPeticion($urlPeticionActual);

        if (
            preg_match_all(
                '~<li[^>]*\bclass=(["\'])[^"\']*\bproduct\b[^"\']*\1[^>]*>(?<block>[\s\S]*?)</li>~i',
                $html,
                $items
            )
        ) {
            foreach ($items['block'] as $block) {
                $u = null;

                // Caso típico: class antes de href.
                if (
                    preg_match(
                        '~<a[^>]*\bclass=(["\'])[^"\']*\bwoocommerce-LoopProduct-link\b[^"\']*\1[^>]*\bhref=(["\'])(?<u>[^"\']+)\2~i',
                        $block,
                        $m
                    )
                ) {
                    $u = trim((string) ($m['u'] ?? ''));
                }

                // Fallback: href antes de class.
                if (($u === null || $u === '') && preg_match(
                    '~<a[^>]*\bhref=(["\'])(?<u>[^"\']+)\1[^>]*\bclass=(["\'])[^"\']*\bwoocommerce-LoopProduct-link\b[^"\']*\3~i',
                    $block,
                    $m2
                )) {
                    $u = trim((string) ($m2['u'] ?? ''));
                }

                // Fallback ultra permisivo dentro del bloque del producto.
                if (($u === null || $u === '') && preg_match(
                    '~<a[^>]*\bhref=(["\'])(?<u>https?://www\.tronoo\.es/[^"\']+|/[^"\']+)\1~i',
                    $block,
                    $m3
                )) {
                    $u = trim((string) ($m3['u'] ?? ''));
                }

                if ($u !== null && $u !== '') {
                    $urlsProductos[] = $this->normalizarUrlCorta($u, $base);
                }
            }
        }

        // Si cambia el contenedor <li>, buscar globalmente el enlace de producto de WooCommerce.
        if (count($urlsProductos) === 0) {
            if (
                preg_match_all(
                    '~<a[^>]*\bhref=(["\'])(?<u>https?://www\.tronoo\.es/[^"\']+|/[^"\']+)\1[^>]*\bclass=(["\'])[^"\']*\bwoocommerce-LoopProduct-link\b[^"\']*\3~i',
                    $html,
                    $glob
                )
            ) {
                foreach ($glob['u'] as $u) {
                    $u = trim((string) $u);
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

    private function construirUrlSiguientePagina(string $urlPeticionActual): ?string
    {
        $parts = parse_url($urlPeticionActual);
        if ($parts === false || empty($parts['host'])) {
            return null;
        }

        $scheme = $parts['scheme'] ?? 'https';
        $host = $parts['host'];
        $port = isset($parts['port']) ? ':' . (int) $parts['port'] : '';
        $fragment = $parts['fragment'] ?? '';

        $path = $parts['path'] ?? '/';
        $path = preg_replace('~/+$~', '/', $path) ?: '/';

        $siguiente = $this->extraerNumeroPaginaActualDesdePath($path) + 1;
        $pathSinPage = preg_replace('~/page/\d+/?$~i', '/', $path) ?: '/';
        $pathSiguiente = rtrim($pathSinPage, '/') . '/page/' . $siguiente . '/';

        $query = $parts['query'] ?? '';
        $url = $scheme . '://' . $host . $port . $pathSiguiente;
        if ($query !== '') {
            $url .= '?' . $query;
        }
        if ($fragment !== '') {
            $url .= '#' . $fragment;
        }

        return $url;
    }

    private function extraerNumeroPaginaActualDesdePath(string $path): int
    {
        if (preg_match('~/page/(\d+)/?$~i', $path, $m)) {
            return max(1, (int) ($m[1] ?? 1));
        }
        return 1;
    }

    private function obtenerBaseUrlDesdeUrlPeticion(string $urlPeticionActual): string
    {
        $pu = parse_url($urlPeticionActual);
        $scheme = $pu['scheme'] ?? 'https';
        $host = $pu['host'] ?? 'www.tronoo.es';

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
