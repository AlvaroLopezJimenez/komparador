<?php

namespace App\Http\Controllers\Scraping\Tiendas;

use App\Http\Controllers\Scraping\Tiendas\PlantillaTiendaController;
use App\Models\OfertaProducto;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

/**
 * Coolmod: extrae el precio desde el atributo data-itemprice en el HTML devuelto por la API.
 * Si detecta "Artículo no disponible", pone la oferta en mostrar-no y crea aviso a 4 días.
 * Si detecta "Consigue un regalo con la compra de este artículo", puede marcar +Juego (y aviso),
 * salvo que en el bloque de ficha titulado exactamente "Promociones" (no "Promociones y descuentos"
 * del menú) haya enlaces href con cupon-eneba o textos como "te la instalamos gratis",
 * "Consigue hasta 25 euros", "cartera de Steam".
 */
class CoolmodController extends PlantillaTiendaController
{
    /** Subcadenas en el bloque "Promociones" (mismo contenedor que cupon-eneba) que impiden +Juego. */
    private const FRAGMENTOS_PROMOCIONES_IMPIDEN_PLUS_JUEGO = [
        'te la instalamos gratis',
        'consigue hasta 25 euros',
        'cartera de steam',
    ];

    public function obtenerPrecio($url, $variante = null, $tienda = null, $oferta = null): JsonResponse
    {
        $resultado = $this->apiHTML->obtenerHTML($url, null, $tienda ? $tienda->api : null);

        if (!is_array($resultado) || empty($resultado['success']) || empty($resultado['html'])) {
            $msg = is_array($resultado) ? ($resultado['error'] ?? 'Error desconocido') : 'Respuesta inválida de la API';
            return response()->json(['success' => false, 'error' => 'No se pudo obtener el HTML de Coolmod: ' . $msg]);
        }

        $html = html_entity_decode((string)$resultado['html'], ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Detección sin stock: "Artículo no disponible"
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
            return response()->json(['success' => false, 'error' => 'Artículo no disponible en Coolmod.']);
        }

        $precio = $this->extraerPrecioDesdeDataItemprice($html);

        if ($precio === null) {
            return response()->json(['success' => false, 'error' => 'No se encontró data-itemprice en el HTML.']);
        }

        if ($oferta && $oferta instanceof OfertaProducto) {
            $this->detectarYGuardarDescuentoJuegoCoolmod($html, $oferta);
        }

        return response()->json(['success' => true, 'precio' => $precio]);
    }

    /**
     * Texto "Consigue un regalo con la compra de este artículo" + no descarte por sección Promociones.
     */
    private function detectarYGuardarDescuentoJuegoCoolmod(string $html, OfertaProducto $oferta): void
    {
        $tieneFraseRegalo = $this->detectarTextoRegaloConCompraCoolmod($html);
        $descartarPorSeccionPromociones = $this->promocionesSectionImpidePlusJuego($html);
        $regaloDetectado = $tieneFraseRegalo && !$descartarPorSeccionPromociones;

        $descuentoAnterior = $oferta->descuentos;
        $descuentoNuevo = $regaloDetectado ? '+Juego' : null;

        if ($descuentoNuevo !== null) {
            \Log::info('CoolmodController - Regalo con compra detectado (+Juego):', [
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

                \Log::info('CoolmodController - Aviso +Juego creado:', [
                    'aviso_id' => $avisoId,
                    'oferta_id' => $oferta->id,
                ]);
            }
        } else {
            if ($descuentoAnterior === '+Juego') {
                \Log::info('CoolmodController - Ya no hay texto de regalo con compra; limpiando descuentos +Juego:', [
                    'oferta_id' => $oferta->id,
                ]);
                $oferta->update(['descuentos' => null]);
            }
        }
    }

    /** Detecta la frase del span (espacios/saltos flexibles, punto final opcional). */
    private function detectarTextoRegaloConCompraCoolmod(string $html): bool
    {
        return (bool) preg_match(
            '/Consigue\s+un\s+regalo\s+con\s+la\s+compra\s+de\s+este\s+art[ií]culo\.?/iu',
            $html
        );
    }

    /**
     * Solo el bloque de producto con <p> cuyo texto es exactamente "Promociones" (excluye el menú
     * "Promociones y descuentos"). En ese contenedor padre: enlace con cupon-eneba en la URL, o ciertos
     * textos promocionales (instalación gratis, "Consigue hasta 25 euros", etc.) → no se marca +Juego.
     *
     * Nota: algunos textos promocionales en Coolmod solo aparecen en atributos (ej. alt de <img>),
     * por eso además de textContent se revisan atributos comunes del bloque.
     */
    private function promocionesSectionImpidePlusJuego(string $html): bool
    {
        $prev = libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        @$dom->loadHTML('<?xml encoding="UTF-8">' . $html);
        libxml_clear_errors();
        libxml_use_internal_errors($prev);

        $xp = new \DOMXPath($dom);
        $titulosPromo = $xp->query("//p[normalize-space(.) = 'Promociones']");
        if ($titulosPromo === false || $titulosPromo->length === 0) {
            return false;
        }

        for ($i = 0; $i < $titulosPromo->length; $i++) {
            $p = $titulosPromo->item($i);
            $contenedor = $p->parentNode;
            if (!$contenedor instanceof \DOMElement) {
                continue;
            }
            $textoBloque = (string) $contenedor->textContent;
            $atributosBloque = $this->extraerTextoAtributosPromociones($xp, $contenedor);
            $textoBusqueda = $textoBloque . ' ' . $atributosBloque;
            foreach (self::FRAGMENTOS_PROMOCIONES_IMPIDEN_PLUS_JUEGO as $frag) {
                if (stripos($textoBusqueda, $frag) !== false) {
                    return true;
                }
            }
            $links = $xp->query('.//a[@href]', $contenedor);
            if ($links === false) {
                continue;
            }
            foreach ($links as $a) {
                $href = $a->getAttribute('href');
                if ($href !== '' && stripos($href, 'cupon-eneba') !== false) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Extrae texto de atributos relevantes dentro del bloque de promociones
     * (alt/title/aria-label y href), para detectar promociones que no están en textContent.
     */
    private function extraerTextoAtributosPromociones(\DOMXPath $xp, \DOMElement $contenedor): string
    {
        $partes = [];
        $nodos = $xp->query('.//*[@alt or @title or @aria-label or @href]', $contenedor);
        if ($nodos === false) {
            return '';
        }

        foreach ($nodos as $nodo) {
            if (!$nodo instanceof \DOMElement) {
                continue;
            }
            foreach (['alt', 'title', 'aria-label', 'href'] as $attr) {
                $valor = trim((string) $nodo->getAttribute($attr));
                if ($valor !== '') {
                    $partes[] = $valor;
                }
            }
        }

        return implode(' ', $partes);
    }

    /**
     * Detecta si la página indica que el artículo no está disponible.
     */
    private function esSinStock(string $html): bool
    {
        return str_contains($html, 'Artículo no disponible');
    }

    /**
     * Extrae el precio del atributo data-itemprice (ej: data-itemprice="669.95")
     */
    private function extraerPrecioDesdeDataItemprice(string $html): ?float
    {
        if (preg_match('~data-itemprice\s*=\s*"([^"]+)"~i', $html, $m) && isset($m[1])) {
            return $this->normalizarImporte($m[1]);
        }
        if (preg_match("~data-itemprice\s*=\s*'([^']+)'~i", $html, $m) && isset($m[1])) {
            return $this->normalizarImporte($m[1]);
        }
        return null;
    }

    /** Normaliza el valor a float (acepta "669.95", "1.234,56", etc.) */
    private function normalizarImporte(string $importe): ?float
    {
        $importe = str_replace(["\xc2\xa0", "\xe2\x80\xaf", '&nbsp;'], ' ', $importe);
        $s = preg_replace('/[^\d\,\.]/u', '', $importe);
        if ($s === null || $s === '') return null;

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

        if (!preg_match('/^\d+(\.\d+)?$/', $s)) return null;
        return (float)$s;
    }

    // -------------------------------------------------------------------------
    // Cron Neo Objetivos - listado de categoría por paginación (?pagina=N)
    // -------------------------------------------------------------------------

    public function tipoListadoCategoria(): ?string
    {
        return 'paginacion';
    }

    /**
     * Productos en JSON-LD: "itemListElement":[ { "@type":"ListItem", "url":"https://..." }, ... ]
     * Paginación: https://www.coolmod.com/tarjetas-graficas/?pagina=2
     */
    public function extraerProductosYSiguientePagina(string $html, string $urlPeticionActual): array
    {
        $urlsProductos = $this->extraerUrlsProductosDesdeListadoCoolmod($html);

        $base = $this->obtenerBaseUrlDesdeUrlPeticion($urlPeticionActual);
        foreach ($urlsProductos as $i => $u) {
            $urlsProductos[$i] = $this->normalizarUrlCorta($u, $base);
        }
        $urlsProductos = array_values(array_unique(array_filter($urlsProductos)));

        $siguienteUrl = $this->extraerSiguientePaginaUrl($html, $urlPeticionActual, $base);

        // Coolmod suele paginar con ?pagina=N pero el HTML del listado no siempre trae <a href="...pagina=">
        // (o el VPS no lo devuelve). Si hay productos y no hay enlace siguiente, se construye la URL oficial.
        if ($siguienteUrl === null && count($urlsProductos) > 0) {
            $siguienteUrl = $this->construirUrlPaginaCoolmod($urlPeticionActual, $this->extraerNumeroPaginaActual($urlPeticionActual) + 1);
        }

        return [
            'urls_productos' => $urlsProductos,
            'siguiente_url'  => $siguienteUrl,
        ];
    }

    /**
     * URLs desde ListItem (schema ItemList) en el HTML de categoría.
     *
     * @return array<int, string>
     */
    private function extraerUrlsProductosDesdeListadoCoolmod(string $html): array
    {
        $urls = [];

        // Orden típico: @type ListItem y luego "url".
        if (
            preg_match_all(
                '~\{[^}]*"@type"\s*:\s*"ListItem"[^}]*"url"\s*:\s*"(https://[^"]+)"~is',
                $html,
                $m
            )
        ) {
            foreach ($m[1] as $u) {
                $urls[] = trim((string) $u);
            }
        }

        // Mismo objeto con "url" antes que "@type":"ListItem".
        if (
            preg_match_all(
                '~\{[^}]*"url"\s*:\s*"(https://[^"]+)"[^}]*"@type"\s*:\s*"ListItem"~is',
                $html,
                $m2
            )
        ) {
            foreach ($m2[1] as $u) {
                $urls[] = trim((string) $u);
            }
        }

        $urls = array_values(array_unique(array_filter($urls)));

        return array_values(array_filter($urls, function (string $u) {
            return str_contains(strtolower($u), 'coolmod.com');
        }));
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

        // Variante: href antes que rel="next"
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

    /**
     * Construye la URL de categoría con ?pagina=N (y conserva el resto de query params si los hubiera).
     * Ej.: https://www.coolmod.com/tarjetas-graficas → .../?pagina=2
     */
    private function construirUrlPaginaCoolmod(string $urlPeticionActual, int $pagina): ?string
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
        unset($params['pagina'], $params['page'], $params['paged'], $params['product-page']);
        $params['pagina'] = $pagina;

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
        $host = $pu['host'] ?? 'www.coolmod.com';

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
