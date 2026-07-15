<?php

namespace App\Http\Controllers\Scraping\Tiendas;

use App\Http\Controllers\DescuentosController;
use App\Http\Controllers\Scraping\Tiendas\PlantillaTiendaController;
use App\Models\OfertaProducto;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

/**
 * TiendaAnimal (tiendanimal.es, Demandware):
 * - Precio en span[data-afi="view-pdp-action-price-current"] (.product-page-action__price).
 * - Categorías paginadas con ?start=N&page=M (24 productos por página).
 * - Fin de paginación: cuando no aparece el texto "Ver más productos" en el HTML.
 * - URLs de producto en listado: div.isk-product-card[data-url] (excluye banners).
 */
class TiendaanimalController extends PlantillaTiendaController
{
    private const PRODUCTOS_POR_PAGINA = 24;

    public function obtenerPrecio($url, $variante = null, $tienda = null, $oferta = null): JsonResponse
    {
        $resultado = $this->apiHTML->obtenerHTML($url, null, $tienda ? $tienda->api : null);

        if (!is_array($resultado) || empty($resultado['success']) || empty($resultado['html'])) {
            $msg = is_array($resultado) ? ($resultado['error'] ?? 'Error desconocido') : 'Respuesta inválida de la API';

            return response()->json(['success' => false, 'error' => 'No se pudo obtener el HTML: ' . $msg]);
        }

        $html = html_entity_decode((string) $resultado['html'], ENT_QUOTES | ENT_HTML5, 'UTF-8');

        $precio = $this->extraerPrecioDesdeProductPageAction($html);
        if ($precio === null) {
            return response()->json([
                'success' => false,
                'error'   => 'No se encontró span[data-afi="view-pdp-action-price-current"] en la página',
            ]);
        }

        $descuentosDetectados = $this->extraerDescuentosPromociones($html);

        if ($oferta) {
            $descuentosDetectados = $this->elegirMejorDescuentoTiendanimal(
                $descuentosDetectados,
                $oferta,
                $precio
            );
            $this->detectarYGuardarDescuentos($html, $oferta, $descuentosDetectados);
        }

        return response()->json(['success' => true, 'precio' => $precio]);
    }

    /**
     * Detecta cupones en product-page-promotions y guarda en la oferta.
     *
     * @param  array<int, string>|null  $descuentosDetectados
     */
    private function detectarYGuardarDescuentos(string $html, $oferta, ?array $descuentosDetectados = null): void
    {
        if (!$oferta || !($oferta instanceof OfertaProducto)) {
            return;
        }

        $descuentosDetectados = $descuentosDetectados ?? $this->extraerDescuentosPromociones($html);

        $descuentosAnteriores = DescuentosController::parseDescuentos($oferta->descuentos);
        $descuentosNoTiendanimal = DescuentosController::filtrarDescuentosNoTiendanimal($descuentosAnteriores);

        $descuentoNuevo = DescuentosController::joinDescuentos(
            array_merge($descuentosNoTiendanimal, $descuentosDetectados)
        );

        $descuentoAnterior = $oferta->descuentos;
        if ($descuentoNuevo !== $descuentoAnterior) {
            $oferta->update(['descuentos' => $descuentoNuevo]);

            foreach ($descuentosDetectados as $descuentoDetectado) {
                if (in_array($descuentoDetectado, $descuentosAnteriores, true)) {
                    continue;
                }

                $textoAviso = $this->textoAvisoDescuentoDetectado($descuentoDetectado);

                DB::table('avisos')->insert([
                    'texto_aviso'    => $textoAviso,
                    'fecha_aviso'    => now(),
                    'user_id'        => 1,
                    'avisoable_type' => OfertaProducto::class,
                    'avisoable_id'   => $oferta->id,
                    'oculto'         => 0,
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ]);
            }
        }
    }

    private function textoAvisoDescuentoDetectado(string $descuento): string
    {
        $parsed2a = DescuentosController::parseDescuento2aCupon($descuento);
        if ($parsed2a !== null) {
            return sprintf(
                'DETECTADO DESCUENTO 2ª UNIDAD AL %d%% CON CUPÓN %s - GENERADO AUTOMÁTICAMENTE',
                $parsed2a['porcentaje'],
                $parsed2a['codigo']
            );
        }

        if (preg_match('/^cupon;([^;]+);%(\d+)$/i', $descuento, $m)) {
            return sprintf(
                'DETECTADO CUPÓN %s (-%d%%) - GENERADO AUTOMÁTICAMENTE',
                $m[1],
                (int) $m[2]
            );
        }

        return 'DETECTADO DESCUENTO TIENDANIMAL - GENERADO AUTOMÁTICAMENTE';
    }

    /**
     * Tiendanimal solo permite un cupón en carrito: nos quedamos con el de mayor ahorro.
     *
     * @param  array<int, string>  $descuentosDetectados
     * @return array<int, string>
     */
    private function elegirMejorDescuentoTiendanimal(array $descuentosDetectados, OfertaProducto $oferta, float $precioTotal): array
    {
        if (count($descuentosDetectados) <= 1) {
            return $descuentosDetectados;
        }

        $descuentosController = new DescuentosController();
        $mejorDescuento = null;
        $menorPrecioUnidad = null;

        foreach ($descuentosDetectados as $descuento) {
            $ofertaPrueba = clone $oferta;
            $ofertaPrueba->precio_total = $precioTotal;
            $ofertaPrueba->precio_unidad = $oferta->unidades > 0
                ? round($precioTotal / $oferta->unidades, 4)
                : $precioTotal;
            $ofertaPrueba->descuentos = $descuento;

            $resultado = $descuentosController->aplicarDescuento($ofertaPrueba);
            $precioUnidad = (float) $resultado->precio_unidad;

            if ($menorPrecioUnidad === null || $precioUnidad < $menorPrecioUnidad) {
                $menorPrecioUnidad = $precioUnidad;
                $mejorDescuento = $descuento;
            }
        }

        if ($mejorDescuento === null) {
            return [];
        }

        return [$mejorDescuento];
    }

    /**
     * @return array<int, string>
     */
    private function extraerDescuentosPromociones(string $html): array
    {
        $descuentos = [];

        if (!preg_match_all(
            '~<article(?=[^>]*\bisk-promo-callout\b)(?=[^>]*\bis-coupon\b)[^>]*>(?<bloque>.*?)</article>~is',
            $html,
            $bloques
        )) {
            return [];
        }

        foreach ($bloques['bloque'] as $bloque) {
            $titulo = null;
            if (preg_match(
                '~<span[^>]*\bclass=(["\'])[^"\']*\bisk-promo-callout__title\b[^"\']*\1[^>]*>\s*(?<t>[^<]+)~i',
                $bloque,
                $mTitulo
            )) {
                $titulo = html_entity_decode(trim($mTitulo['t']), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            }

            $codigo = null;
            if (preg_match(
                '~\bdata-coupon-code=(["\'])(?<code>[^"\']+)\1~i',
                $bloque,
                $mCodigo
            )) {
                $codigo = trim($mCodigo['code']);
            }

            if ($codigo === null || $codigo === '' || $titulo === null || $titulo === '') {
                continue;
            }

            if (preg_match('/-(\d+)\s*%\s*en\s*2\s*[ªa]/iu', $titulo, $m2a)) {
                $porcentaje = (int) $m2a[1];
                if ($porcentaje > 0 && $porcentaje <= 100) {
                    $descuentos[] = '2a al ' . $porcentaje . ' - cupon;' . $codigo;
                }
                continue;
            }

            if (preg_match('/-(\d+)\s*%/u', $titulo, $mPct)) {
                $porcentaje = (int) $mPct[1];
                if ($porcentaje > 0 && $porcentaje <= 100) {
                    $descuentos[] = 'cupon;' . $codigo . ';%' . $porcentaje;
                }
            }
        }

        return array_values(array_unique($descuentos));
    }

    /**
     * <span class="product-page-action__price" data-afi="view-pdp-action-price-current">90.99€</span>
     */
    private function extraerPrecioDesdeProductPageAction(string $html): ?float
    {
        if (
            preg_match(
                '~<span[^>]*\bdata-afi=(["\'])view-pdp-action-price-current\1[^>]*>\s*(?<p>[0-9]+(?:[.,][0-9]{2})?)\s*€~i',
                $html,
                $m
            )
        ) {
            return $this->normalizarImporte($m['p']);
        }

        if (
            preg_match(
                '~<span[^>]*\bclass=(["\'])[^"\']*\bproduct-page-action__price\b[^"\']*\1[^>]*\bdata-afi=(["\'])view-pdp-action-price-current\2[^>]*>\s*(?<p>[0-9]+(?:[.,][0-9]{2})?)\s*€~i',
                $html,
                $m2
            )
        ) {
            return $this->normalizarImporte($m2['p']);
        }

        if (
            preg_match(
                '~id=(["\'])product-page-action\1[^>]*>\s*Precio\s+(?<p>[0-9]+(?:[.,][0-9]{2})?)\s*€~i',
                $html,
                $mSr
            )
        ) {
            return $this->normalizarImporte($mSr['p']);
        }

        return null;
    }

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
            $lastComa = strrpos($s, ',');
            $lastDot = strrpos($s, '.');
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
    // Cron Neo Objetivos - listado de categoría por paginación (?start=N&page=M)
    // -------------------------------------------------------------------------

    public function tipoListadoCategoria(): ?string
    {
        return 'paginacion';
    }

    /**
     * Paginación: https://www.tiendanimal.es/perros/pienso-para-perros/?start=24&page=2
     * Fin cuando no aparece "Ver más productos" (p. ej. Ver m&aacute;s productos).
     */
    public function extraerProductosYSiguientePagina(string $html, string $urlPeticionActual): array
    {
        $urlsProductos = $this->extraerUrlsProductosDesdeListado($html, $urlPeticionActual);

        $siguienteUrl = null;
        if (count($urlsProductos) > 0 && $this->hayPaginaSiguiente($html)) {
            $paginaActual = $this->extraerNumeroPaginaActual($urlPeticionActual);
            $siguienteUrl = $this->construirUrlSiguientePagina($urlPeticionActual, $paginaActual + 1);
        }

        return [
            'urls_productos' => $urlsProductos,
            'siguiente_url'  => $siguienteUrl,
        ];
    }

    /**
     * @return array<int, string>
     */
    private function extraerUrlsProductosDesdeListado(string $html, string $urlPeticionActual): array
    {
        $urls = [];
        $base = $this->obtenerBaseUrlDesdeUrlPeticion($urlPeticionActual);

        if (
            preg_match_all(
                '~<div[^>]*\bclass=(["\'])[^"\']*\bisk-product-card\b[^"\']*\1[^>]*\bdata-afi=(["\'])common-product-card\2[^>]*\bdata-url=(["\'])(?<u>[^"\']+)\3~i',
                $html,
                $mDataUrl
            )
        ) {
            foreach ($mDataUrl['u'] as $u) {
                $u = trim((string) $u);
                if ($u !== '') {
                    $urls[] = $this->normalizarUrlCorta($u, $base);
                }
            }
        }

        if ($urls === []) {
            if (
                preg_match_all(
                    '~<div[^>]*\bdata-afi=(["\'])common-product-card\1[^>]*\bdata-url=(["\'])(?<u>[^"\']+)\2~i',
                    $html,
                    $mFallback
                )
            ) {
                foreach ($mFallback['u'] as $u) {
                    $u = trim((string) $u);
                    if ($u !== '') {
                        $urls[] = $this->normalizarUrlCorta($u, $base);
                    }
                }
            }
        }

        $urls = array_values(array_unique(array_filter($urls)));

        return array_values(array_filter($urls, fn (string $u) => $this->esUrlProductoTiendanimal($u)));
    }

    private function esUrlProductoTiendanimal(string $url): bool
    {
        if (!preg_match('~^https?://(?:www\.)?tiendanimal\.es/~i', $url)) {
            return false;
        }

        $path = parse_url($url, PHP_URL_PATH) ?? '';

        return (bool) preg_match('~/[A-Za-z0-9_-]+/[A-Za-z0-9_-]+_M\.html$~i', $path);
    }

    private function hayPaginaSiguiente(string $html): bool
    {
        if (preg_match('~Ver\s+m(?:&aacute;|á)s\s+productos~iu', $html)) {
            return true;
        }

        $htmlDecodificado = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return stripos($htmlDecodificado, 'Ver más productos') !== false;
    }

    private function construirUrlSiguientePagina(string $urlPeticionActual, int $paginaSiguiente): ?string
    {
        $parts = parse_url($urlPeticionActual);
        if ($parts === false || empty($parts['host'])) {
            return null;
        }

        $paginaSiguiente = max(1, $paginaSiguiente);
        $start = ($paginaSiguiente - 1) * self::PRODUCTOS_POR_PAGINA;

        $scheme = $parts['scheme'] ?? 'https';
        $host = $parts['host'];
        $port = isset($parts['port']) ? ':' . (int) $parts['port'] : '';
        $path = $parts['path'] ?? '/';
        $fragment = $parts['fragment'] ?? '';

        $query = $parts['query'] ?? '';
        parse_str($query, $params);
        unset($params['p'], $params['pagina'], $params['counter'], $params['sz']);
        $params['start'] = $start;
        if ($paginaSiguiente > 1) {
            $params['page'] = $paginaSiguiente;
        } else {
            unset($params['page'], $params['start']);
        }

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

        if (preg_match('~[?&]start=(\d+)~i', $urlPeticionActual, $mStart)) {
            $start = max(0, (int) ($mStart[1] ?? 0));

            return max(1, (int) floor($start / self::PRODUCTOS_POR_PAGINA) + 1);
        }

        return 1;
    }

    private function obtenerBaseUrlDesdeUrlPeticion(string $urlPeticionActual): string
    {
        $pu = parse_url($urlPeticionActual);
        $scheme = $pu['scheme'] ?? 'https';
        $host = $pu['host'] ?? 'www.tiendanimal.es';

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
