<?php

namespace App\Http\Controllers\Scraping\Tiendas;

use App\Http\Controllers\Scraping\Tiendas\PlantillaTiendaController;
use App\Models\OfertaProducto;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

/**
 * Neobyte: extrae el precio desde JSON-LD schema.org ("price": "199") o desde
 * datos con "affiliation":"NEOBYTE" y "price":199.
 * Bloque #promociones / "Promociones incluidas" / .promociones_banners: +Juego si queda algún banner
 * que no sea solo Eneba, la promo "Actualiza" ni banners de "descuento" (se filtran por enlace).
 */
class NeobyteController extends PlantillaTiendaController
{
    public function obtenerPrecio($url, $variante = null, $tienda = null, $oferta = null): JsonResponse
    {
        $resultado = $this->apiHTML->obtenerHTML($url, null, $tienda ? $tienda->api : null);

        if (!is_array($resultado) || empty($resultado['success']) || empty($resultado['html'])) {
            $msg = is_array($resultado) ? ($resultado['error'] ?? 'Error desconocido') : 'Respuesta inválida de la API';
            return response()->json(['success' => false, 'error' => 'No se pudo obtener el HTML de Neobyte: ' . $msg]);
        }

        $html = html_entity_decode((string)$resultado['html'], ENT_QUOTES | ENT_HTML5, 'UTF-8');

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
            return response()->json(['success' => false, 'error' => 'Artículo no disponible en Neobyte.']);
        }

        $precio = $this->extraerPrecio($html);

        if ($precio === null) {
            return response()->json(['success' => false, 'error' => 'No se encontró el precio en el HTML de Neobyte.']);
        }

        if ($oferta && $oferta instanceof OfertaProducto) {
            $this->detectarYGuardarDescuentoJuegoNeobyte($html, $oferta);
        }

        return response()->json(['success' => true, 'precio' => $precio]);
    }

    /**
     * #promociones + "Promociones incluidas" + .promociones_banners: al menos un &lt;a&gt; no excluido
     * (excl.: texto href/title/img con eneba, actualiza o descuento).
     */
    private function detectarYGuardarDescuentoJuegoNeobyte(string $html, OfertaProducto $oferta): void
    {
        $promoDetectada = $this->detectarBloquePromocionesIncluidasNeobyte($html);

        $descuentoAnterior = $oferta->descuentos;
        $descuentoNuevo = $promoDetectada ? '+Juego' : null;

        if ($descuentoNuevo !== null) {
            \Log::info('NeobyteController - Promociones incluidas con banner detectado (+Juego):', [
                'oferta_id' => $oferta->id,
                'descuento_anterior' => $descuentoAnterior,
            ]);

            $oferta->update(['descuentos' => $descuentoNuevo]);

            if ($descuentoAnterior !== $descuentoNuevo) {
                DB::table('avisos')->insertGetId([
                    'texto_aviso'     => 'DETECTADO REGALO INCLUIDO (+Juego) - GENERADO AUTOMÁTICAMENTE',
                    'fecha_aviso'     => now(),
                    'user_id'         => 1,
                    'avisoable_type'  => OfertaProducto::class,
                    'avisoable_id'    => $oferta->id,
                    'oculto'          => 0,
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ]);
            }
        } else {
            if ($descuentoAnterior === '+Juego') {
                \Log::info('NeobyteController - Ya no hay bloque promociones incluidas; limpiando descuentos +Juego:', [
                    'oferta_id' => $oferta->id,
                ]);
                $oferta->update(['descuentos' => null]);
            }
        }
    }

    private function detectarBloquePromocionesIncluidasNeobyte(string $html): bool
    {
        $prev = libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        @$dom->loadHTML('<?xml encoding="UTF-8">' . $html);
        libxml_clear_errors();
        libxml_use_internal_errors($prev);

        $xp = new \DOMXPath($dom);
        $nodosPromo = $xp->query("//*[@id='promociones']");
        if ($nodosPromo === false || $nodosPromo->length === 0) {
            return false;
        }

        $contenedor = $nodosPromo->item(0);
        if (!$contenedor instanceof \DOMElement) {
            return false;
        }

        $subtitulos = $xp->query(".//*[@id='promociones_subtitle']", $contenedor);
        if ($subtitulos === false || $subtitulos->length === 0) {
            return false;
        }

        $textoSub = trim($subtitulos->item(0)->textContent ?? '');
        if (!preg_match('/Promociones\s+incluidas/iu', $textoSub)) {
            return false;
        }

        $banners = $xp->query(".//*[contains(concat(' ', normalize-space(@class), ' '), ' promociones_banners ')]", $contenedor);
        if ($banners === false || $banners->length === 0) {
            return false;
        }

        $zonaBanners = $banners->item(0);
        $links = $xp->query('.//a[@href]', $zonaBanners);
        if ($links === false || $links->length === 0) {
            return false;
        }

        $hayAlgunoValido = false;
        for ($i = 0; $i < $links->length; $i++) {
            $a = $links->item($i);
            if (!$a instanceof \DOMElement) {
                continue;
            }
            if ($this->esBannerNeobyteExcluidoParaMasJuego($a, $xp)) {
                continue;
            }
            $hayAlgunoValido = true;
            break;
        }

        return $hayAlgunoValido;
    }

    /**
     * Banner Eneba, promo "Actualiza" o texto "descuento" no cuentan para +Juego.
     * Se revisa href, title del &lt;a&gt; y alt/title de &lt;img&gt; internas.
     */
    private function esBannerNeobyteExcluidoParaMasJuego(\DOMElement $a, \DOMXPath $xp): bool
    {
        $partes = [
            $a->getAttribute('href'),
            $a->getAttribute('title'),
        ];
        $imgs = $xp->query('.//img', $a);
        if ($imgs !== false) {
            foreach ($imgs as $img) {
                $partes[] = $img->getAttribute('alt');
                $partes[] = $img->getAttribute('title');
            }
        }

        $blob = mb_strtolower(implode("\n", array_filter($partes, static fn ($s) => $s !== '')), 'UTF-8');

        if (str_contains($blob, 'eneba')) {
            return true;
        }

        if (str_contains($blob, 'actualiza')) {
            return true;
        }

        if (str_contains($blob, 'descuento')) {
            return true;
        }

        return false;
    }

    /**
     * Detecta si la página indica que el artículo no está disponible (schema.org OutOfStock).
     */
    private function esSinStock(string $html): bool
    {
        return str_contains($html, 'schema.org/OutOfStock') ||
               preg_match('~"availability"\s*:\s*"[^"]*OutOfStock[^"]*"~i', $html);
    }

    /**
     * Extrae el precio: primero desde schema.org ("price": "199"), luego desde affiliation NEOBYTE ("price":199).
     */
    private function extraerPrecio(string $html): ?float
    {
        $precio = $this->extraerPrecioSchemaOrg($html);
        if ($precio !== null) {
            return $precio;
        }
        return $this->extraerPrecioAffiliation($html);
    }

    /**
     * Precio en schema.org: "availability": "https://schema.org/InStock", "price": "199"
     * Buscamos primero en un bloque que contenga InStock para evitar capturar otro producto.
     */
    private function extraerPrecioSchemaOrg(string $html): ?float
    {
        // Bloque con InStock y price juntos (schema.org producto en stock)
        if (preg_match('~InStock["\s]*[,}].*?"price"\s*:\s*"([^"]+)"~is', $html, $m) && isset($m[1])) {
            return $this->normalizarImporte($m[1]);
        }
        if (preg_match('~InStock["\s]*[,}].*?"price"\s*:\s*(\d+(?:[.,]\d+)?)~is', $html, $m) && isset($m[1])) {
            return $this->normalizarImporte($m[1]);
        }
        // Fallback: cualquier "price" en el HTML (por si el orden de campos es distinto)
        if (preg_match('~"price"\s*:\s*"([^"]+)"~i', $html, $m) && isset($m[1])) {
            return $this->normalizarImporte($m[1]);
        }
        if (preg_match('~"price"\s*:\s*(\d+(?:[.,]\d+)?)~i', $html, $m) && isset($m[1])) {
            return $this->normalizarImporte($m[1]);
        }
        return null;
    }

    /**
     * Precio en datos con affiliation NEOBYTE: "affiliation":"NEOBYTE","index":0,"price":199
     */
    private function extraerPrecioAffiliation(string $html): ?float
    {
        if (!preg_match('~"affiliation"\s*:\s*"NEOBYTE"~i', $html)) {
            return null;
        }
        // Buscar "price":199 o "price": 199 en el mismo bloque (cercano a NEOBYTE)
        if (preg_match('~"affiliation"\s*:\s*"NEOBYTE"[^}]*"price"\s*:\s*(\d+(?:[.,]\d+)?)~i', $html, $m) && isset($m[1])) {
            return $this->normalizarImporte($m[1]);
        }
        if (preg_match('~"price"\s*:\s*(\d+(?:[.,]\d+)?)[^}]*"affiliation"\s*:\s*"NEOBYTE"~i', $html, $m) && isset($m[1])) {
            return $this->normalizarImporte($m[1]);
        }
        // Cualquier "price": num tras NEOBYTE en un radio corto (mismo objeto JSON)
        if (preg_match('~NEOBYTE".*?"price"\s*:\s*(\d+(?:[.,]\d+)?)~is', $html, $m) && isset($m[1])) {
            return $this->normalizarImporte($m[1]);
        }
        return null;
    }

    /** Normaliza el valor a float (acepta "669.95", "1.234,56", etc.) */
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
    // Cron Neo Objetivos - listado de categoría por paginación (PrestaShop ?page=N)
    // -------------------------------------------------------------------------

    public function tipoListadoCategoria(): ?string
    {
        return 'paginacion';
    }

    /**
     * En el HTML del listado: <article class="product-miniature ..."> con enlace en
     * a.thumbnail.product-thumbnail o en span.product-title > a.
     * Paginación: https://www.neobyte.es/categoria-111?page=2
     */
    public function extraerProductosYSiguientePagina(string $html, string $urlPeticionActual): array
    {
        $urlsProductos = $this->extraerUrlsProductosDesdeListadoNeobyte($html);

        $base = $this->obtenerBaseUrlDesdeUrlPeticion($urlPeticionActual);
        foreach ($urlsProductos as $i => $u) {
            $urlsProductos[$i] = $this->normalizarUrlCorta($u, $base);
        }
        $urlsProductos = array_values(array_unique(array_filter($urlsProductos)));

        $siguienteUrl = $this->extraerSiguientePaginaUrl($html, $urlPeticionActual, $base);

        return [
            'urls_productos' => $urlsProductos,
            'siguiente_url'  => $siguienteUrl,
        ];
    }

    /**
     * URLs de producto desde miniaturas del listado Neobyte (PrestaShop / IQIT).
     *
     * @return array<int, string>
     */
    private function extraerUrlsProductosDesdeListadoNeobyte(string $html): array
    {
        $urls = [];

        // Orden típico: href antes que class (thumbnail product-thumbnail).
        if (
            preg_match_all(
                '~<a[^>]+href=(["\'])(?<u>https?://[^"\']+|//[^"\']+)\1[^>]*class="[^"]*\bthumbnail\b[^"]*\bproduct-thumbnail\b[^"]*"~i',
                $html,
                $m
            )
        ) {
            foreach ($m['u'] as $u) {
                $urls[] = trim((string) $u);
            }
        }

        // Mismo enlace con class antes que href.
        if (
            preg_match_all(
                '~<a[^>]+class="[^"]*\bthumbnail\b[^"]*\bproduct-thumbnail\b[^"]*"[^>]+href=(["\'])(?<u>https?://[^"\']+|//[^"\']+)\1~i',
                $html,
                $m2
            )
        ) {
            foreach ($m2['u'] as $u) {
                $urls[] = trim((string) $u);
            }
        }

        // Fallback: título del producto en el listado.
        if (
            preg_match_all(
                '~<span[^>]*\bproduct-title\b[^>]*>\s*<a[^>]+href=(["\'])(?<u>https?://[^"\']+|//[^"\']+)\1~i',
                $html,
                $m3
            )
        ) {
            foreach ($m3['u'] as $u) {
                $urls[] = trim((string) $u);
            }
        }

        $urls = array_values(array_filter(array_unique($urls)));

        return $urls;
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

        $block = $html;

        $candidatas = [];

        if (preg_match_all('~href=(["\'])(?<u>[^"\']*\?(?:page|paged|product-page)=(?<n>\d+)[^"\']*)\1~i', $block, $mm, PREG_SET_ORDER)) {
            foreach ($mm as $row) {
                $u = trim((string) ($row['u'] ?? ''));
                $n = isset($row['n']) ? (int) $row['n'] : null;
                if ($u === '' || !$n) {
                    continue;
                }
                $candidatas[] = ['page' => $n, 'url' => $u];
            }
        }

        if (preg_match_all('~href=(["\'])(?<u>[^"\']*\/page\/(?<n>\d+)\/?[^"\']*)\1~i', $block, $m2, PREG_SET_ORDER)) {
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

    private function extraerNumeroPaginaActual(string $urlPeticionActual): int
    {
        if (preg_match('~[?&](?:page|paged|product-page)=(\d+)~i', $urlPeticionActual, $m)) {
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
        $host = $pu['host'] ?? 'www.neobyte.es';

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
