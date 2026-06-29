<?php

namespace App\Http\Controllers\Scraping\Tiendas;

use App\Http\Controllers\Scraping\Tiendas\PlantillaTiendaController;
use App\Models\OfertaProducto;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

/**
 * Caseking (SFCC): precio en span.value[content] — preferir venta (.sales.js-sales), si no PVPR (.sales-original).
 * Categoría: ?page=N; enlaces de producto tipo /slug/SKU.html
 */
class CasekingController extends PlantillaTiendaController
{
    public function obtenerPrecio($url, $variante = null, $tienda = null, $oferta = null): JsonResponse
    {
        $resultado = $this->apiHTML->obtenerHTML($url, null, $tienda ? $tienda->api : null);

        if (!is_array($resultado) || empty($resultado['success']) || empty($resultado['html'])) {
            $msg = is_array($resultado) ? ($resultado['error'] ?? 'Error desconocido') : 'Respuesta inválida de la API';
            return response()->json(['success' => false, 'error' => 'No se pudo obtener el HTML de Caseking: ' . $msg]);
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

            return response()->json(['success' => false, 'error' => 'Producto sin stock en Caseking.']);
        }

        $precio = $this->extraerPrecioDesdeHtml($html);

        if ($precio === null) {
            return response()->json(['success' => false, 'error' => 'No se encontró el precio en el HTML de Caseking.']);
        }

        if ($oferta && $oferta instanceof OfertaProducto) {
            $this->detectarYGuardarDescuentoJuegoCaseking($html, $oferta);
        }

        return response()->json(['success' => true, 'precio' => $precio]);
    }

    /**
     * Bloque bonus-products + "Regalo incluido con este artículo" → +Juego si hay al menos un
     * ítem listado que no sea cupón digital genérico (tarjeta Steam, msi.com, etc.).
     * No se comprueba el nombre del juego (cambia cada mes).
     */
    private function detectarYGuardarDescuentoJuegoCaseking(string $html, OfertaProducto $oferta): void
    {
        $textoBonus = $this->extraerTextoBloqueBonusProducts($html);
        $regaloIncluidoDetectado = $this->detectarBloqueRegaloIncluido($html, $textoBonus);
        $tieneRegaloJuegoValido = $this->casekingBonusTieneAlgunRegaloJuegoNoExcluido($html);

        $descuentoAnterior = $oferta->descuentos;
        $descuentoNuevo = ($regaloIncluidoDetectado && $tieneRegaloJuegoValido) ? '+Juego' : null;

        if ($descuentoNuevo !== null) {
            \Log::info('CasekingController - Regalo incluido detectado (+Juego):', [
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

                \Log::info('CasekingController - Aviso +Juego creado:', [
                    'aviso_id' => $avisoId,
                    'oferta_id' => $oferta->id,
                ]);
            }
        } else {
            if ($descuentoAnterior === '+Juego') {
                \Log::info('CasekingController - Ya no hay regalo incluido; limpiando descuentos +Juego:', [
                    'oferta_id' => $oferta->id,
                ]);
                $oferta->update(['descuentos' => null]);
            }
        }
    }

    /**
     * Bloque bonus-products y frase tipo "Regalo incluido con este artículo por valor de € …".
     * Si el texto extraído por DOM no trae el h5 completo, se reintenta en todo el HTML.
     */
    private function detectarBloqueRegaloIncluido(string $html, string $textoBonusNormalizado): bool
    {
        if (!preg_match('/bonus-products/i', $html)) {
            return false;
        }

        $patron = '/Regalo\s+incluido\s+con\s+este\s+(?:art[ií]culo|articulo)/iu';

        if ($textoBonusNormalizado !== '' && preg_match($patron, $textoBonusNormalizado)) {
            return true;
        }

        return (bool) preg_match($patron, $html);
    }

    /** Texto visible del primer div.bonus-products (prioridad al buscar la frase "Regalo incluido…"). */
    private function extraerTextoBloqueBonusProducts(string $html): string
    {
        $xp = $this->crearXPathDesdeHtml($html);
        if ($xp === null) {
            return '';
        }

        $nodes = $xp->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' bonus-products ')]");
        if ($nodes === false || $nodes->length === 0) {
            return '';
        }

        $texto = $nodes->item(0)->textContent ?? '';

        return trim(preg_replace('/\s+/u', ' ', $texto));
    }

    /**
     * +Juego si algún ítem del bloque no encaja con patrones de cupón digital (monedero/tarjeta).
     * Si no hay líneas de producto, se evalúa el texto completo del bloque.
     */
    private function casekingBonusTieneAlgunRegaloJuegoNoExcluido(string $html): bool
    {
        $items = $this->extraerTextosItemsRegaloBonusProducts($html);

        if ($items !== []) {
            foreach ($items as $item) {
                if (!$this->esItemCuponDigitalExcluidoDeMasJuego($item)) {
                    return true;
                }
            }

            return false;
        }

        $textoBonus = $this->extraerTextoBloqueBonusProducts($html);
        if ($textoBonus === '') {
            return true;
        }

        return !$this->esItemCuponDigitalExcluidoDeMasJuego($textoBonus);
    }

    /**
     * Nombres de cada regalo listado en bonus-products (alt de imagen o último span de la fila).
     *
     * @return array<int, string>
     */
    private function extraerTextosItemsRegaloBonusProducts(string $html): array
    {
        $xp = $this->crearXPathDesdeHtml($html);
        if ($xp === null) {
            return [];
        }

        $items = [];
        $bonusQuery = "//*[contains(concat(' ', normalize-space(@class), ' '), ' bonus-products ')]";

        $imgs = $xp->query($bonusQuery . '//img[@alt]');
        if ($imgs !== false) {
            foreach ($imgs as $img) {
                $alt = trim((string) $img->getAttribute('alt'));
                if ($alt !== '') {
                    $items[] = $alt;
                }
            }
        }

        $rows = $xp->query(
            $bonusQuery
            . "//span[contains(concat(' ', normalize-space(@class), ' '), ' d-flex ')]"
            . "[contains(concat(' ', normalize-space(@class), ' '), ' align-items-center ')]"
        );
        if ($rows !== false) {
            foreach ($rows as $row) {
                $spans = $row->getElementsByTagName('span');
                for ($i = $spans->length - 1; $i >= 0; $i--) {
                    $txt = trim(preg_replace('/\s+/u', ' ', $spans->item($i)->textContent ?? ''));
                    if ($txt !== '' && !preg_match('/^\d+\s*x$/iu', $txt)) {
                        $items[] = $txt;
                        break;
                    }
                }
            }
        }

        $unicos = [];
        foreach ($items as $item) {
            $clave = mb_strtolower($item, 'UTF-8');
            if (!isset($unicos[$clave])) {
                $unicos[$clave] = $item;
            }
        }

        return array_values($unicos);
    }

    private function crearXPathDesdeHtml(string $html): ?\DOMXPath
    {
        $prev = libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        @$dom->loadHTML('<?xml encoding="UTF-8">' . $html);
        libxml_clear_errors();
        libxml_use_internal_errors($prev);

        return new \DOMXPath($dom);
    }

    /**
     * Patrones de cupón digital / monedero en Caseking (no dependen del título del juego).
     * Añadir aquí más regex si aparecen nuevos tipos de promo fijos.
     *
     * @return array<int, string>
     */
    private function patronesCasekingCuponDigitalSinJuego(): array
    {
        return [
            '/steam\s*card/iu',
            '/tarjeta\s+steam/iu',
            '/gift\s*card/iu',
            '/tarjeta\s+regalo/iu',
            '/wallet\s+steam/iu',
            '/monedero\s+steam/iu',
            '/msi\.com/iu',
            '/\beneba\b/iu',
            '/a\s+trav[eé]s\s+(?:de\s+)?msi/iu',
        ];
    }

    /** true = solo cupón digital; no cuenta para +Juego (el nombre del juego puede ser cualquiera). */
    private function esItemCuponDigitalExcluidoDeMasJuego(string $textoItem): bool
    {
        $textoItem = trim($textoItem);
        if ($textoItem === '') {
            return true;
        }

        foreach ($this->patronesCasekingCuponDigitalSinJuego() as $patron) {
            if (preg_match($patron, $textoItem)) {
                return true;
            }
        }

        return false;
    }

    private function esSinStock(string $html): bool
    {
        $h = strtolower($html);

        return str_contains($h, 'product-not-available')
            || str_contains($h, 'no disponible')
            || preg_match('~availability[^>]*>\s*agotado~i', $html)
            || preg_match('~data-available="false"~i', $html)
            || preg_match('~data-available="preorder"~i', $html)
            || str_contains($h, 'product-availability-message-preorder')
            || preg_match('~data-qa="pdp-availability-status-value"[^>]*>\s*en\s+tr[aá]nsito~iu', $html);
    }

    /**
     * Si existe bloque de venta (.sales.js-sales), usar su span.value[content];
     * si no, el de PVPR (.sales-original).
     */
    private function extraerPrecioDesdeHtml(string $html): ?float
    {
        if (
            preg_match(
                '~<span[^>]+class="[^"]*\bsales\b[^"]*\bjs-sales\b[^"]*"[^>]*>[\s\S]*?<span[^>]*\bclass="[^"]*\bvalue\b[^"]*"[^>]*\bcontent="([\d.]+)"~i',
                $html,
                $m
            )
        ) {
            $p = $this->normalizarPrecioContent($m[1]);
            if ($p !== null) {
                return $p;
            }
        }

        if (
            preg_match(
                '~<span[^>]+class="[^"]*\bsales-original\b[^"]*"[^>]*>[\s\S]*?<span[^>]*\bclass="[^"]*\bvalue\b[^"]*"[^>]*\bcontent="([\d.]+)"~i',
                $html,
                $m
            )
        ) {
            return $this->normalizarPrecioContent($m[1]);
        }

        return null;
    }

    /** Atributo content usa punto decimal (ej. 1652.50). */
    private function normalizarPrecioContent(string $valor): ?float
    {
        $valor = trim($valor);
        if ($valor === '' || !preg_match('/^\d+(\.\d+)?$/', $valor)) {
            return null;
        }

        return (float) $valor;
    }

    // -------------------------------------------------------------------------
    // Cron Neo Objetivos — paginación ?page=N
    // -------------------------------------------------------------------------

    public function tipoListadoCategoria(): ?string
    {
        return 'paginacion';
    }

    /**
     * Paginación: https://www.caseking.es/componentes/tarjetas-graficas?page=2
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
            $siguienteUrl = $this->construirUrlPagina($urlPeticionActual, $this->extraerNumeroPaginaActual($urlPeticionActual) + 1);
        }

        return [
            'urls_productos' => $urlsProductos,
            'siguiente_url'  => $siguienteUrl,
        ];
    }

    /**
     * Enlaces de ficha: href="/slug.../912-V531-005.html" (data-qa="product-tile-name-link" en el listado).
     *
     * @return array<int, string>
     */
    private function extraerUrlsProductosListado(string $html): array
    {
        $urls = [];

        $patrones = [
            '~href="(/[^"]+\.html)"[^>]*data-qa="product-tile-name-link"~i',
            '~data-qa="product-tile-name-link"[^>]*href="(/[^"]+\.html)"~i',
        ];

        foreach ($patrones as $pat) {
            if (preg_match_all($pat, $html, $m)) {
                foreach ($m[1] as $rel) {
                    $rel = trim((string) $rel);
                    if ($rel !== '' && $this->esRutaProductoCaseking($rel)) {
                        $urls[] = $rel;
                    }
                }
            }
        }

        return array_values(array_unique($urls));
    }

    /** Excluye listados de categoría (p. ej. /componentes/...). Ficha: /slug/SKU.html */
    private function esRutaProductoCaseking(string $path): bool
    {
        if (!str_ends_with(strtolower($path), '.html')) {
            return false;
        }
        if (preg_match('~^/componentes/~i', $path)) {
            return false;
        }

        return (bool) preg_match('~^/[^/]+/[^/]+\.html$~', $path);
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
        $host = $pu['host'] ?? 'www.caseking.es';

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
