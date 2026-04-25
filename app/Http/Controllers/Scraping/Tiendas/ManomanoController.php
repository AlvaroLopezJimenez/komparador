<?php

namespace App\Http\Controllers\Scraping\Tiendas;

use App\Http\Controllers\Scraping\Tiendas\PlantillaTiendaController;
use App\Models\OfertaProducto;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ManomanoController extends PlantillaTiendaController
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

        $precio = $this->extraerPrecioDesdeAriaLabelTitulo($html);
        if ($precio === null) {
            return response()->json([
                'success' => false,
                'error'   => 'No se pudo encontrar el precio en la pagina de ManoMano',
            ]);
        }

        return response()->json(['success' => true, 'precio' => $precio]);
    }

    /**
     * Stub de sin stock (pendiente de definir patron exacto).
     */
    private function esSinStock(string $html): bool
    {
        return false;
    }

    /**
     * Precio principal en ficha:
     * <div class="... text-title2 ... font-bold ..." aria-label="68,58€" role="group">
     */
    private function extraerPrecioDesdeAriaLabelTitulo(string $html): ?float
    {
        if (
            preg_match(
                '~<div[^>]*\bclass=(["\'])(?=[^"\']*\btext-title2\b)(?=[^"\']*\bfont-bold\b)[^"\']*\1[^>]*\baria-label=(["\'])(?<lbl>[^"\']+)\2~i',
                $html,
                $m
            )
        ) {
            $p = $this->parsePrecioDesdeEtiqueta($m['lbl'] ?? '');
            if ($p !== null) {
                return $p;
            }
        }

        // Misma estructura con text-title3 (listado / variaciones de tema).
        if (
            preg_match(
                '~<div[^>]*\bclass=(["\'])(?=[^"\']*\btext-title3\b)(?=[^"\']*\bfont-bold\b)[^"\']*\1[^>]*\baria-label=(["\'])(?<lbl>[^"\']+)\2~i',
                $html,
                $m2
            )
        ) {
            $p = $this->parsePrecioDesdeEtiqueta($m2['lbl'] ?? '');
            if ($p !== null) {
                return $p;
            }
        }

        // Fallback: primer aria-label con formato XX,XX€ o XX.XX€ en bloque de precio visible.
        if (
            preg_match(
                '~\baria-label=(["\'])(?<lbl>[0-9][0-9\.,\s]*[0-9])\s*€\1~iu',
                $html,
                $m3
            )
        ) {
            return $this->normalizarImporte($m3['lbl'] ?? '');
        }

        return null;
    }

    private function parsePrecioDesdeEtiqueta(string $lbl): ?float
    {
        $lbl = html_entity_decode(trim($lbl), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $lbl = preg_replace('/\s*€\s*$/u', '', $lbl);
        $lbl = trim((string) $lbl);

        return $lbl !== '' ? $this->normalizarImporte($lbl) : null;
    }

    /**
     * Normaliza "68,58", "68.58" o "1.068,58" a float.
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
     * URLs de producto en listado: una por tarjeta.
     * Prioridad: cortar por data-testid="productCardVertical" y primer enlace /p/ por bloque;
     * si no hay bloques, varios patrones de <a> (href relativo o absoluto, orden de atributos).
     */
    public function extraerProductosYSiguientePagina(string $html, string $urlPeticionActual): array
    {
        $urlsProductos = [];
        $base = $this->obtenerBaseUrlDesdeUrlPeticion($urlPeticionActual);

        // Tras el primer <div data-testid="productCardVertical">, cada fragmento (salvo [0]) es una tarjeta.
        $partes = preg_split(
            '~<div[^>]*\bdata-testid=(["\'])productCardVertical\1[^>]*>~i',
            $html,
            -1
        );

        if (is_array($partes) && count($partes) > 1) {
            foreach ($partes as $idx => $fragmento) {
                if ($idx === 0) {
                    continue;
                }
                $u = $this->primerHrefProductoManoManoEnFragmento($fragmento);
                if ($u !== null && $u !== '') {
                    $urlsProductos[] = $this->normalizarUrlCorta($u, $base);
                }
            }
        }

        if (count($urlsProductos) === 0) {
            $urlsProductos = $this->extraerUrlsProductoPorPatronesGlobales($html, $base);
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
     * Primer enlace de producto dentro del HTML de una tarjeta (relativo /p/... o URL absoluta manomano).
     */
    private function primerHrefProductoManoManoEnFragmento(string $fragmento): ?string
    {
        // Enlace título / overlay (lo más estable en el snippet original).
        if (
            preg_match(
                '~<a[^>]*\bclass=(["\'])[^"\']*\bafter:absolute\b[^"\']*\1[^>]*\bhref=(["\'])(?<u>[^"\']+)\2~i',
                $fragmento,
                $m
            )
        ) {
            $u = $this->normalizarPathProductoManoMano($m['u'] ?? '');
            if ($u !== null) {
                return $u;
            }
        }

        if (
            preg_match(
                '~<a[^>]*\bhref=(["\'])(?<u>[^"\']+)\1[^>]*\bclass=(["\'])[^"\']*\bafter:absolute\b~i',
                $fragmento,
                $m2
            )
        ) {
            $u = $this->normalizarPathProductoManoMano($m2['u'] ?? '');
            if ($u !== null) {
                return $u;
            }
        }

        // Cualquier href a /p/ o dominio manomano en la tarjeta.
        if (
            preg_match(
                '~\bhref=(["\'])(?<u>(?:https?://(?:www\.)?manomano\.es)?/p/[^"\']+)\1~i',
                $fragmento,
                $m3
            )
        ) {
            return trim((string) ($m3['u'] ?? ''));
        }

        if (
            preg_match(
                '~\bhref=(["\'])(?<u>https?://(?:www\.)?manomano\.es/p/[^"\']+)\1~i',
                $fragmento,
                $m4
            )
        ) {
            return trim((string) ($m4['u'] ?? ''));
        }

        return null;
    }

    private function normalizarPathProductoManoMano(string $href): ?string
    {
        $href = trim($href);
        if ($href === '') {
            return null;
        }

        if (preg_match('~^(?:https?://(?:www\.)?manomano\.es)?(/p/.+)$~i', $href, $m)) {
            return $m[1];
        }

        return null;
    }

    /**
     * Fallback si no hay data-testid o cambia el marcado: mismos patrones sobre todo el HTML.
     *
     * @return string[]
     */
    private function extraerUrlsProductoPorPatronesGlobales(string $html, string $base): array
    {
        $out = [];

        $patrones = [
            '~<a[^>]*\bclass=(["\'])[^"\']*\bafter:absolute\b[^"\']*\1[^>]*\bhref=(["\'])(?<u>[^"\']+)\2~i',
            '~<a[^>]*\bhref=(["\'])(?<u>[^"\']+)\1[^>]*\bclass=(["\'])[^"\']*\bafter:absolute\b~i',
            '~<a[^>]*\bclass=(["\'])[^"\']*\bjs-prod-link\b[^"\']*\1[^>]*\bhref=(["\'])(?<u>[^"\']+)\2~i',
            '~<a[^>]*\bhref=(["\'])(?<u>[^"\']+)\1[^>]*\bclass=(["\'])[^"\']*\bjs-prod-link\b~i',
        ];

        foreach ($patrones as $pat) {
            if (preg_match_all($pat, $html, $m, PREG_SET_ORDER)) {
                foreach ($m as $row) {
                    $raw = trim((string) ($row['u'] ?? ''));
                    $path = $this->normalizarPathProductoManoMano($raw);
                    if ($path === null || $path === '') {
                        continue;
                    }
                    $out[] = $this->normalizarUrlCorta($path, $base);
                }
            }
            if (count($out) > 0) {
                break;
            }
        }

        if (count($out) === 0) {
            if (
                preg_match_all(
                    '~\bhref=(["\'])(?<u>(?:https?://(?:www\.)?manomano\.es)?/p/[^"\']+)\1~i',
                    $html,
                    $m2
                )
            ) {
                foreach ($m2['u'] as $u) {
                    $u = trim((string) $u);
                    if ($u !== '') {
                        $out[] = $this->normalizarUrlCorta($u, $base);
                    }
                }
            }
        }

        return $out;
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
        $host = $pu['host'] ?? 'www.manomano.es';

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
