<?php

namespace App\Http\Controllers\Scraping\Tiendas;

use App\Http\Controllers\Scraping\Tiendas\PlantillaTiendaController;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Models\OfertaProducto;

/**
 * Pcbox: extrae el precio desde el HTML en divs con clases
 * ticnova-commons-components-0-x-price o ticnova-crosselling-1-x-pricePdp.
 * Etiqueta data-highlight-name="JUEGO REGALO" fuera del slider de relacionados → descuentos +Juego.
 */
class PcboxController extends PlantillaTiendaController
{
    public function obtenerPrecio($url, $variante = null, $tienda = null, $oferta = null): JsonResponse
    {
        $resultado = $this->apiHTML->obtenerHTML($url, null, $tienda ? $tienda->api : null);

        if (!is_array($resultado) || empty($resultado['success']) || empty($resultado['html'])) {
            $msg = is_array($resultado) ? ($resultado['error'] ?? 'Error desconocido') : 'Respuesta inválida de la API';
            return response()->json(['success' => false, 'error' => 'No se pudo obtener el HTML de Pcbox: ' . $msg]);
        }

        $html = html_entity_decode((string)$resultado['html'], ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Comprobar si el producto no está disponible en Pcbox
        if (strpos($html, 'Este producto no está disponible actualmente</div>') !== false) {
            // Si tenemos oferta, ocultarla y generar aviso a 4 días vista
            if ($oferta && $oferta instanceof OfertaProducto) {
                $oferta->update(['mostrar' => 'no']);

                DB::table('avisos')->insertGetId([
                    'texto_aviso'     => 'SIN STOCK 1A VEZ - GENERADO AUTOMÁTICAMENTE',
                    'fecha_aviso'     => now()->addDays(4),
                    'user_id'         => 1,
                    'avisoable_type'  => \App\Models\OfertaProducto::class,
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
            return response()->json(['success' => false, 'error' => 'No se encontró el precio en el HTML de Pcbox.']);
        }

        if ($oferta && $oferta instanceof OfertaProducto) {
            $this->detectarYGuardarDescuentoJuegoPcbox($html, $oferta);
        }

        return response()->json(['success' => true, 'precio' => $precio]);
    }

    /**
     * data-highlight-name="JUEGO REGALO" en highlights del producto; se ignoran apariciones
     * dentro del carrusel de relacionados (section ticnova-slider-layout-0-x-sliderLayoutContainer).
     */
    private function detectarYGuardarDescuentoJuegoPcbox(string $html, OfertaProducto $oferta): void
    {
        $regaloDetectado = $this->detectarJuegoRegaloFueraDeSliderRelacionados($html);

        $descuentoAnterior = $oferta->descuentos;
        $descuentoNuevo = $regaloDetectado ? '+Juego' : null;

        if ($descuentoNuevo !== null) {
            \Log::info('PcboxController - JUEGO REGALO detectado fuera de slider (+Juego):', [
                'oferta_id' => $oferta->id,
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

                \Log::info('PcboxController - Aviso +Juego creado:', [
                    'aviso_id' => $avisoId,
                    'oferta_id' => $oferta->id,
                ]);
            }
        } else {
            if ($descuentoAnterior === '+Juego') {
                \Log::info('PcboxController - Ya no hay JUEGO REGALO en PDP; limpiando descuentos +Juego:', [
                    'oferta_id' => $oferta->id,
                ]);
                $oferta->update(['descuentos' => null]);
            }
        }
    }

    /**
     * Quita el HTML del slider de productos relacionados y busca el highlight en el resto.
     */
    private function detectarJuegoRegaloFueraDeSliderRelacionados(string $html): bool
    {
        $htmlSinSlider = $this->htmlSinSeccionesSliderRelacionadosPcbox($html);

        return (bool) preg_match(
            '/data-highlight-name\s*=\s*["\']JUEGO\s+REGALO["\']/iu',
            $htmlSinSlider
        );
    }

    /** Elimina las section con clase ticnova-slider-layout-0-x-sliderLayoutContainer (carrusel de relacionados). */
    private function htmlSinSeccionesSliderRelacionadosPcbox(string $html): string
    {
        $prev = libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $loaded = @$dom->loadHTML('<?xml encoding="UTF-8">' . $html);
        libxml_clear_errors();
        libxml_use_internal_errors($prev);

        if (!$loaded) {
            return $html;
        }

        $xp = new \DOMXPath($dom);
        $nodes = $xp->query("//section[contains(concat(' ', normalize-space(@class), ' '), ' ticnova-slider-layout-0-x-sliderLayoutContainer ')]");
        if ($nodes !== false) {
            foreach (iterator_to_array($nodes) as $node) {
                if ($node->parentNode) {
                    $node->parentNode->removeChild($node);
                }
            }
        }

        $body = $dom->getElementsByTagName('body')->item(0);
        if ($body) {
            $out = '';
            foreach ($body->childNodes as $child) {
                $out .= $dom->saveHTML($child);
            }

            return $out;
        }

        return $html;
    }

    /**
     * Extrae el precio probando los dos selectores conocidos de Pcbox.
     * Ej: "73,42&nbsp;€" dentro de ticnova-commons-components-0-x-price o ticnova-crosselling-1-x-pricePdp.
     */
    private function extraerPrecioDesdeHtml(string $html): ?float
    {
        // Primero: div con clase ticnova-commons-components-0-x-price
        if (preg_match('/ticnova-commons-components-0-x-price[^>]*>([^<]+)</', $html, $m) && isset($m[1])) {
            $precio = $this->normalizarImporte(trim($m[1]));
            if ($precio !== null) {
                return $precio;
            }
        }

        // Segundo: div con clase ticnova-crosselling-1-x-pricePdp
        if (preg_match('/ticnova-crosselling-1-x-pricePdp[^>]*>([^<]+)</', $html, $m) && isset($m[1])) {
            $precio = $this->normalizarImporte(trim($m[1]));
            if ($precio !== null) {
                return $precio;
            }
        }

        return null;
    }

    /** Normaliza el valor a float (acepta "73,42", "1.234,56", "669.95", etc.) */
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
        return (float)$s;
    }

    // -------------------------------------------------------------------------
    // Cron Neo Objetivos - listado VTEX por paginación (?page=N); sin enlaces "siguiente" en HTML
    // -------------------------------------------------------------------------

    public function tipoListadoCategoria(): ?string
    {
        return 'paginacion';
    }

    /**
     * Productos en application/ld+json: ItemList → itemListElement[].item (@type Product) → @id.
     * Paginación: https://www.pcbox.com/.../tarjetas-graficas?page=3
     * La tienda no expone botón siguiente fiable: si hay productos, la siguiente URL es page+1.
     */
    public function extraerProductosYSiguientePagina(string $html, string $urlPeticionActual): array
    {
        $urlsProductos = $this->extraerUrlsProductosDesdeListadoPcbox($html);

        $base = $this->obtenerBaseUrlDesdeUrlPeticion($urlPeticionActual);
        foreach ($urlsProductos as $i => $u) {
            $u = $this->normalizarUrlCorta($u, $base);
            $urlsProductos[$i] = $this->codificarBarrasVerticalesEnPathPcbox($u);
        }
        $urlsProductos = array_values(array_unique(array_filter($urlsProductos)));

        $siguienteUrl = $this->extraerSiguientePaginaUrl($html, $urlPeticionActual, $base);

        if ($siguienteUrl === null && count($urlsProductos) > 0) {
            $siguienteUrl = $this->construirUrlPaginaPcbox($urlPeticionActual, $this->extraerNumeroPaginaActual($urlPeticionActual) + 1);
        }

        return [
            'urls_productos' => $urlsProductos,
            'siguiente_url'  => $siguienteUrl,
        ];
    }

    /**
     * @return array<int, string>
     */
    private function extraerUrlsProductosDesdeListadoPcbox(string $html): array
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
                $this->recolectarUrlsItemListProductoPcbox($data, $urls);
            }
        }

        $urls = array_values(array_unique(array_filter($urls)));

        if ($urls !== []) {
            return array_values(array_filter($urls, fn (string $u) => $this->esUrlProductoPcbox($u)));
        }

        return $this->extraerUrlsProductosRegexFallbackPcbox($html);
    }

    /**
     * @param  array<string, mixed>  $node
     * @param  array<int, string>  $urls
     */
    private function recolectarUrlsItemListProductoPcbox($node, array &$urls): void
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
                $this->recolectarUrlsItemListProductoPcbox($v, $urls);
            }
        }
    }

    /**
     * Si el JSON está truncado o mal formado: @id de productos (rutas VTEX terminan en /p).
     *
     * @return array<int, string>
     */
    private function extraerUrlsProductosRegexFallbackPcbox(string $html): array
    {
        $urls = [];
        if (preg_match_all('~"@id"\s*:\s*"(https://www\.pcbox\.com[^"]+)"~i', $html, $m)) {
            foreach ($m[1] as $u) {
                $u = trim((string) $u);
                if ($this->esUrlProductoPcbox($u)) {
                    $urls[] = $u;
                }
            }
        }

        return array_values(array_unique($urls));
    }

    private function esUrlProductoPcbox(string $url): bool
    {
        if (!str_contains(strtolower($url), 'pcbox.com')) {
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

    private function construirUrlPaginaPcbox(string $urlPeticionActual, int $pagina): ?string
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
        $host = $pu['host'] ?? 'www.pcbox.com';

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

    /**
     * El JSON-LD devuelve a veces "|" en el slug (\u007c tras json_decode) mientras el enlace canónico
     * usa "%7C"; unifica al formulario codificado para no duplicar la misma ficha en neo/ofertas.
     * Si la URL ya traía %7C, no cambia (no hay "|" en el path).
     */
    private function codificarBarrasVerticalesEnPathPcbox(string $url): string
    {
        $parts = parse_url($url);
        if ($parts === false) {
            return $url;
        }

        $path = $parts['path'] ?? '';
        if ($path === '' || !str_contains($path, '|')) {
            return $url;
        }

        $parts['path'] = str_replace('|', '%7C', $path);

        return $this->reconstruirUrlDesdePartesParseUrl($parts);
    }

    /**
     * @param  array<string, mixed>  $parts  Salida de parse_url()
     */
    private function reconstruirUrlDesdePartesParseUrl(array $parts): string
    {
        $scheme = isset($parts['scheme']) ? $parts['scheme'] . '://' : '';
        $user = $parts['user'] ?? '';
        $pass = isset($parts['pass']) ? ':' . $parts['pass'] : '';
        $auth = ($user !== '' || ($parts['pass'] ?? '') !== '')
            ? $user . $pass . '@'
            : '';
        $host = $parts['host'] ?? '';
        $port = isset($parts['port']) ? ':' . $parts['port'] : '';
        $path = $parts['path'] ?? '';
        $query = isset($parts['query']) && $parts['query'] !== ''
            ? '?' . $parts['query']
            : '';
        $fragment = isset($parts['fragment']) && $parts['fragment'] !== ''
            ? '#' . $parts['fragment']
            : '';

        return $scheme . $auth . $host . $port . $path . $query . $fragment;
    }
}
