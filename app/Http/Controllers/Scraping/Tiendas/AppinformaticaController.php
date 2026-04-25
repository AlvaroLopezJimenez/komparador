<?php

namespace App\Http\Controllers\Scraping\Tiendas;

use App\Http\Controllers\Scraping\Tiendas\PlantillaTiendaController;
use App\Models\OfertaProducto;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

/**
 * App Informática (VTEX): precio en div.ticnova-commons-components-0-x-price.
 * Listado de categoría: ?page=N y JSON-LD ItemList → Product @id (misma pauta que Pcbox).
 * Si en la ficha hay highlight VTEX data-highlight-name="JUEGO REGALO" (ticnova-product-highlights),
 * se marca la oferta con descuentos +Juego y aviso, igual que CoolmodController.
 */
class AppinformaticaController extends PlantillaTiendaController
{
    public function obtenerPrecio($url, $variante = null, $tienda = null, $oferta = null): JsonResponse
    {
        $resultado = $this->apiHTML->obtenerHTML($url, null, $tienda ? $tienda->api : null);

        if (!is_array($resultado) || empty($resultado['success']) || empty($resultado['html'])) {
            $msg = is_array($resultado) ? ($resultado['error'] ?? 'Error desconocido') : 'Respuesta inválida de la API';
            return response()->json(['success' => false, 'error' => 'No se pudo obtener el HTML de App Informática: ' . $msg]);
        }

        $html = html_entity_decode((string) $resultado['html'], ENT_QUOTES | ENT_HTML5, 'UTF-8');

        if (strpos($html, 'Este producto no está disponible actualmente</div>') !== false) {
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

            return response()->json([
                'success' => false,
                'error'   => 'Producto sin stock',
            ]);
        }

        $precio = $this->extraerPrecioDesdeHtml($html);

        if ($precio === null) {
            return response()->json(['success' => false, 'error' => 'No se encontró el precio en el HTML de App Informática.']);
        }

        if ($oferta && $oferta instanceof OfertaProducto) {
            $this->detectarYGuardarDescuentoJuegoAppinformatica($html, $oferta);
        }

        return response()->json(['success' => true, 'precio' => $precio]);
    }

    /**
     * Highlight VTEX "JUEGO REGALO" (data-highlight-name) → mismo flujo que Coolmod (+Juego, aviso, limpieza).
     */
    private function detectarYGuardarDescuentoJuegoAppinformatica(string $html, OfertaProducto $oferta): void
    {
        $juegoRegaloDetectado = $this->detectarHighlightJuegoRegaloAppinformatica($html);

        $descuentoAnterior = $oferta->descuentos;
        $descuentoNuevo = $juegoRegaloDetectado ? '+Juego' : null;

        if ($descuentoNuevo !== null) {
            \Log::info('AppinformaticaController - JUEGO REGALO detectado (+Juego):', [
                'oferta_id'          => $oferta->id,
                'descuento_anterior' => $descuentoAnterior,
            ]);

            $oferta->update(['descuentos' => $descuentoNuevo]);

            if ($descuentoAnterior !== $descuentoNuevo) {
                $avisoId = DB::table('avisos')->insertGetId([
                    'texto_aviso'     => 'DETECTADO REGALO INCLUIDO (+Juego) - GENERADO AUTOMÁTICAMENTE',
                    'fecha_aviso'     => now(),
                    'user_id'         => 1,
                    'avisoable_type'  => OfertaProducto::class,
                    'avisoable_id'    => $oferta->id,
                    'oculto'          => 0,
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ]);

                \Log::info('AppinformaticaController - Aviso +Juego creado:', [
                    'aviso_id'  => $avisoId,
                    'oferta_id' => $oferta->id,
                ]);
            }
        } else {
            if ($descuentoAnterior === '+Juego') {
                \Log::info('AppinformaticaController - Ya no hay highlight JUEGO REGALO; limpiando descuentos +Juego:', [
                    'oferta_id' => $oferta->id,
                ]);
                $oferta->update(['descuentos' => null]);
            }
        }
    }

    /**
     * Ej.: <span data-highlight-name="JUEGO REGALO" ... class="ticnova-product-highlights-2-x-productHighlightText">...
     * No dependemos de data-highlight-id (puede cambiar).
     */
    private function detectarHighlightJuegoRegaloAppinformatica(string $html): bool
    {
        if (preg_match('/data-highlight-name\s*=\s*["\']\s*JUEGO\s+REGALO\s*["\']/iu', $html)) {
            return true;
        }

        return (bool) preg_match(
            '/<span\b[^>]*\bticnova-product-highlights-\d+-x-productHighlightText\b[^>]*>[\s\n]*JUEGO\s+REGALO\s*</ius',
            $html
        );
    }

    /**
     * Precio en <div class="ticnova-commons-components-0-x-price">365,04&nbsp;€</div>
     * La clase también aparece en CSS (.ticnova-...-price{...}); solo coincidimos con div/span y clase en atributo.
     * Reserva: mismo patrón VTEX que Pcbox (crosselling PDP).
     */
    private function extraerPrecioDesdeHtml(string $html): ?float
    {
        $htmlLimpio = preg_replace('~<style\b[^>]*>[\s\S]*?</style>~i', '', $html);

        $patronPrincipal = '~<(?:div|span)\b[^>]*\bclass="[^"]*\bticnova-commons-components-0-x-price\b[^"]*"[^>]*>([^<]+)~i';
        if (preg_match_all($patronPrincipal, $htmlLimpio, $bloques, PREG_SET_ORDER)) {
            foreach ($bloques as $row) {
                $precio = $this->normalizarImporte(trim($row[1] ?? ''));
                if ($precio !== null && $precio > 0) {
                    return $precio;
                }
            }
        }

        if (preg_match('/ticnova-crosselling-1-x-pricePdp[^>]*>([^<]+)</', $htmlLimpio, $m) && isset($m[1])) {
            $precio = $this->normalizarImporte(trim($m[1]));
            if ($precio !== null) {
                return $precio;
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
    // Cron Neo Objetivos - VTEX ?page=N
    // -------------------------------------------------------------------------

    public function tipoListadoCategoria(): ?string
    {
        return 'paginacion';
    }

    /**
     * Paginación: https://www.appinformatica.com/componentes-de-ordenador/tarjetas-graficas?page=3
     */
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
        $urls = [];

        if (preg_match_all(
            '~<script[^>]+type=["\']application/ld\+json["\'][^>]*>(?<json>[\s\S]*?)</script>~i',
            $html,
            $blocks
        )) {
            foreach ($blocks['json'] as $jsonStr) {
                $jsonStr = trim(html_entity_decode((string) $jsonStr, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                $data = json_decode($jsonStr, true);
                if (!is_array($data)) {
                    continue;
                }
                $this->recolectarUrlsItemListProducto($data, $urls);
            }
        }

        $urls = array_values(array_unique(array_filter($urls)));

        if ($urls !== []) {
            return array_values(array_filter($urls, fn (string $u) => $this->esUrlProducto($u)));
        }

        return $this->extraerUrlsProductosRegexFallback($html);
    }

    /**
     * @param  array<string, mixed>  $node
     * @param  array<int, string>  $urls
     */
    private function recolectarUrlsItemListProducto($node, array &$urls): void
    {
        if (!is_array($node)) {
            return;
        }

        if (($node['@type'] ?? null) === 'ItemList' && !empty($node['itemListElement']) && is_array($node['itemListElement'])) {
            foreach ($node['itemListElement'] as $el) {
                if (!is_array($el)) {
                    continue;
                }
                $item = $el['item'] ?? null;
                if (is_array($item) && ($item['@type'] ?? '') === 'Product' && !empty($item['@id'])) {
                    $urls[] = (string) $item['@id'];
                }
            }
        }

        foreach ($node as $v) {
            if (is_array($v)) {
                $this->recolectarUrlsItemListProducto($v, $urls);
            }
        }
    }

    /**
     * @return array<int, string>
     */
    private function extraerUrlsProductosRegexFallback(string $html): array
    {
        $urls = [];
        if (preg_match_all('~"@id"\s*:\s*"(https://www\.appinformatica\.com[^"]+)"~i', $html, $m)) {
            foreach ($m[1] as $u) {
                $u = trim((string) $u);
                if ($this->esUrlProducto($u)) {
                    $urls[] = $u;
                }
            }
        }

        return array_values(array_unique($urls));
    }

    private function esUrlProducto(string $url): bool
    {
        if (!str_contains(strtolower($url), 'appinformatica.com')) {
            return false;
        }

        return (bool) preg_match('#/p$#', $url);
    }

    private function extraerSiguientePaginaUrl(string $html, string $urlPeticionActual, string $base): ?string
    {
        $paginaActual = $this->extraerNumeroPaginaActual($urlPeticionActual);

        if (preg_match('~<link[^>]+rel=(["\'])next\1[^>]+href=(["\'])(?<u>[^"\']+)\2~i', $html, $m)) {
            $u = trim((string) ($m['u'] ?? ''));
            if ($u !== '') {
                $u = $this->normalizarUrlCorta($u, $base);
                if ($this->extraerNumeroPaginaActual($u) > $paginaActual) {
                    return $u;
                }
            }
        }

        if (preg_match('~<link[^>]+href=(["\'])(?<u>[^"\']+)\1[^>]+rel\s*=\s*["\']next["\']~i', $html, $m)) {
            $u = trim((string) ($m['u'] ?? ''));
            if ($u !== '') {
                $u = $this->normalizarUrlCorta($u, $base);
                if ($this->extraerNumeroPaginaActual($u) > $paginaActual) {
                    return $u;
                }
            }
        }

        $candidatas = [];
        if (
            preg_match_all(
                '~href=(["\'])(?<u>[^"\']*[?&](?:page|paged|pagina|product-page)=(?<n>\d+)[^"\']*)\1~i',
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

        if ($candidatas === []) {
            return null;
        }

        $siguientes = array_filter($candidatas, fn ($c) => $c['page'] > $paginaActual);
        if ($siguientes === []) {
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
        if (preg_match('~/page\/(\d+)\/?~i', $urlPeticionActual, $m)) {
            return max(1, (int) ($m[1] ?? 1));
        }

        return 1;
    }

    private function obtenerBaseUrlDesdeUrlPeticion(string $urlPeticionActual): string
    {
        $pu = parse_url($urlPeticionActual);
        $scheme = $pu['scheme'] ?? 'https';
        $host = $pu['host'] ?? 'www.appinformatica.com';

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
