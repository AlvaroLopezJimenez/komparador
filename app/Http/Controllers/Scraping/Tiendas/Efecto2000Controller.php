<?php

namespace App\Http\Controllers\Scraping\Tiendas;

use App\Http\Controllers\Scraping\Tiendas\PlantillaTiendaController;
use App\Models\OfertaProducto;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

/**
 * Efecto 2000 (PrestaShop): precio en #our_price_display[itemprop=price][content].
 * Categoría: paginación por hash #/page-N (ej. .../static/...-tarjetas-graficas#/page-2).
 */
class Efecto2000Controller extends PlantillaTiendaController
{
    public function obtenerPrecio($url, $variante = null, $tienda = null, $oferta = null): JsonResponse
    {
        $resultado = $this->apiHTML->obtenerHTML($url, null, $tienda ? $tienda->api : null);

        if (!is_array($resultado) || empty($resultado['success']) || empty($resultado['html'])) {
            $msg = is_array($resultado) ? ($resultado['error'] ?? 'Error desconocido') : 'Respuesta inválida de la API';
            return response()->json(['success' => false, 'error' => 'No se pudo obtener el HTML de Efecto 2000: ' . $msg]);
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

            return response()->json(['success' => false, 'error' => 'Producto sin stock en Efecto 2000.']);
        }

        $precio = $this->extraerPrecioDesdeHtml($html);

        if ($precio === null) {
            return response()->json(['success' => false, 'error' => 'No se encontró el precio en el HTML de Efecto 2000.']);
        }

        return response()->json(['success' => true, 'precio' => $precio]);
    }

    private function esSinStock(string $html): bool
    {
        $h = strtolower($html);

        return str_contains($h, 'producto no disponible')
            || str_contains($h, 'ya no está disponible')
            || str_contains($h, 'out of stock')
            || preg_match('~itemprop="availability"[^>]*href="[^"]*OutOfStock~i', $html);
    }

    /**
     * PrestaShop (bloque típico):
     * div.price_box[price_src="#our_price_display"] > p.our_price_display[itemprop=offers] >
     *   span#our_price_display[itemprop=price][content] — texto "693,99 €" fiable; content a veces FP.
     *
     * Se ignora la subcadena suelta "our_price_display" (17× en página): solo span con id exacto
     * id="our_price_display" (no our_price_display_0).
     */
    private function extraerPrecioDesdeHtml(string $html): ?float
    {
        // span con id + itemprop=price (microdata Offer). Lookaheads: orden de atributos irrelevante.
        $spanPrecioOferta = '<span\b(?=[^>]*\bid\s*=\s*["\']our_price_display["\'])(?=[^>]*\bitemprop\s*=\s*["\']price["\'])';

        // 1) div.price_box — NO usar <div[^>]*\bclass: [^>]* se come todo el tag y \bclass nunca casa.
        $divPriceBox = '<div\b(?=[^>]*\bclass\s*=\s*["\'][^"\']*\bprice_box\b[^"\']*["\'])';
        if (preg_match(
            '~' . $divPriceBox . '[^>]*>.*?' . $spanPrecioOferta . '[^>]*>([^<]+)</span>~is',
            $html,
            $m
        )) {
            $t = $this->precioDesdeTextoSpan($m[1]);
            if ($t !== null) {
                return $t;
            }
        }

        // 2) Mismo span (id + itemprop price), sin depender del div (plantillas raras)
        if (preg_match('~' . $spanPrecioOferta . '[^>]*>([^<]+)</span>~iu', $html, $m)) {
            $t = $this->precioDesdeTextoSpan($m[1]);
            if ($t !== null) {
                return $t;
            }
        }

        // 3) content microdata en ese span (redondear ruido FP)
        if (preg_match(
            '~' . $spanPrecioOferta . '[^>]*\bcontent\s*=\s*["\']([\d.]+)["\']~iu',
            $html,
            $m
        )) {
            return round((float) $m[1], 2);
        }

        // 4) Fallback: solo id="our_price_display" en span (sigue excluyendo …_0 por la comilla)
        if (preg_match('~<span\b(?=[^>]*\bid\s*=\s*["\']our_price_display["\'])[^>]*>([^<]+)</span>~iu', $html, $m)) {
            $t = $this->precioDesdeTextoSpan($m[1]);
            if ($t !== null) {
                return $t;
            }
        }

        if (preg_match('~<span\b(?=[^>]*\bid\s*=\s*["\']our_price_display["\'])[^>]*\bcontent\s*=\s*["\']([\d.]+)["\']~iu', $html, $m)) {
            return round((float) $m[1], 2);
        }

        return null;
    }

    private function precioDesdeTextoSpan(string $raw): ?float
    {
        $t = $this->normalizarImporteTexto(trim(html_entity_decode($raw, ENT_QUOTES | ENT_HTML5, 'UTF-8')));

        return ($t !== null && $t > 0) ? $t : null;
    }

    private function normalizarImporteTexto(string $importe): ?float
    {
        $importe = str_replace(["\xc2\xa0", "\xe2\x80\xaf", '&nbsp;'], ' ', $importe);
        // "1 234,99 €" → quitar espacios de miles
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
    // Cron Neo Objetivos — paginación #/page-N
    // -------------------------------------------------------------------------

    public function tipoListadoCategoria(): ?string
    {
        return 'paginacion';
    }

    /**
     * Listado: https://www.efecto2000.es/static/4283388845-tarjetas-graficas#/page-2
     */
    public function extraerProductosYSiguientePagina(string $html, string $urlPeticionActual): array
    {
        $urlsProductos = $this->extraerUrlsProductosListado($html);

        $base = $this->obtenerBaseUrlDesdeUrlPeticion($urlPeticionActual);
        foreach ($urlsProductos as $i => $u) {
            $urlsProductos[$i] = $this->normalizarUrlCorta($u, $base);
        }
        $urlsProductos = array_values(array_unique(array_filter($urlsProductos)));

        $siguienteUrl = $this->extraerSiguientePaginaUrl($html, $urlPeticionActual, $base);

        if ($siguienteUrl === null && count($urlsProductos) > 0) {
            $siguienteUrl = $this->construirUrlConFragmentoPagina($urlPeticionActual, $this->extraerNumeroPaginaActual($urlPeticionActual) + 1);
        }

        return [
            'urls_productos' => $urlsProductos,
            'siguiente_url'  => $siguienteUrl,
        ];
    }

    /**
     * Enlaces de ficha: a.product_img_link o a.product-name bajo /prod/
     *
     * @return array<int, string>
     */
    private function extraerUrlsProductosListado(string $html): array
    {
        $urls = [];

        $patrones = [
            '~<a[^>]+class="[^"]*\bproduct_img_link\b[^"]*"[^>]+href=["\'](https?://www\.efecto2000\.es/prod/[^"\']+\.html)["\']~i',
            '~<a[^>]+href=["\'](https?://www\.efecto2000\.es/prod/[^"\']+\.html)["\'][^>]+class="[^"]*\bproduct_img_link\b~i',
            '~<a[^>]+class="[^"]*\bproduct-name\b[^"]*"[^>]+href=["\'](https?://www\.efecto2000\.es/prod/[^"\']+\.html)["\']~i',
            '~<a[^>]+href=["\'](https?://www\.efecto2000\.es/prod/[^"\']+\.html)["\'][^>]+class="[^"]*\bproduct-name\b~i',
        ];

        foreach ($patrones as $pat) {
            if (preg_match_all($pat, $html, $m)) {
                foreach ($m[1] as $u) {
                    $urls[] = trim((string) $u);
                }
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
                if ($this->extraerNumeroPaginaDesdeUrlCompleta($u) > $paginaActual) {
                    return $u;
                }
            }
        }

        if (preg_match_all('~href=["\']([^"\']*#/page-(\d+))["\']~i', $html, $mm, PREG_SET_ORDER)) {
            $candidatas = [];
            foreach ($mm as $row) {
                $n = (int) ($row[2] ?? 0);
                if ($n > $paginaActual) {
                    $candidatas[] = ['page' => $n, 'url' => $this->normalizarUrlCorta($row[1], $base)];
                }
            }
            if ($candidatas !== []) {
                usort($candidatas, fn ($a, $b) => $a['page'] <=> $b['page']);

                return $candidatas[0]['url'];
            }
        }

        return null;
    }

    private function extraerNumeroPaginaDesdeUrlCompleta(string $url): int
    {
        return $this->extraerNumeroPaginaActual($url);
    }

    private function construirUrlConFragmentoPagina(string $urlPeticionActual, int $pagina): ?string
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
        $query = isset($parts['query']) ? '?' . $parts['query'] : '';

        $base = $scheme . '://' . $host . $port . $path . $query;

        if ($pagina <= 1) {
            return $base;
        }

        return $base . '#/page-' . $pagina;
    }

    private function extraerNumeroPaginaActual(string $urlPeticionActual): int
    {
        if (preg_match('~#/page-(\d+)~', $urlPeticionActual, $m)) {
            return max(1, (int) ($m[1] ?? 1));
        }

        if (preg_match('~[?&]page=(\d+)~i', $urlPeticionActual, $m)) {
            return max(1, (int) ($m[1] ?? 1));
        }

        return 1;
    }

    private function obtenerBaseUrlDesdeUrlPeticion(string $urlPeticionActual): string
    {
        $pu = parse_url($urlPeticionActual);
        $scheme = $pu['scheme'] ?? 'https';
        $host = $pu['host'] ?? 'www.efecto2000.es';

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
