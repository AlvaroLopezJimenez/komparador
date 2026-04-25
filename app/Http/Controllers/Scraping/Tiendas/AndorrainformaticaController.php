<?php

namespace App\Http\Controllers\Scraping\Tiendas;

use App\Http\Controllers\Scraping\Tiendas\PlantillaTiendaController;
use App\Models\OfertaProducto;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class AndorrainformaticaController extends PlantillaTiendaController
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
                    'texto_aviso'    => 'Sin stock 1a vez - Generado automaticamente',
                    'fecha_aviso'    => now()->addDays(4),
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

        $precio = $this->extraerPrecioDesdeMetaPretax($html);
        if ($precio === null) {
            return response()->json([
                'success' => false,
                'error'   => 'No se pudo encontrar el precio en la pagina de Andorra Informatica',
            ]);
        }

        return response()->json(['success' => true, 'precio' => $precio]);
    }

    /**
     * Detecta "sin stock" cuando la ficha contiene:
     * <li>Este producto ya no esta disponible.</li>
     */
    private function esSinStock(string $html): bool
    {
        return (bool) preg_match(
            '~<li>\s*Este\s+producto\s+ya\s+no\s+esta\s+disponible\.\s*</li>~iu',
            $html
        );
    }

    /**
     * Extrae el precio desde:
     * <meta property="product:pretax_price:amount" content="431.19">
     */
    private function extraerPrecioDesdeMetaPretax(string $html): ?float
    {
        if (
            preg_match(
                '~<meta[^>]*\bproperty=(["\'])product:pretax_price:amount\1[^>]*\bcontent=(["\'])(?<p>[0-9]+(?:[.,][0-9]+)?)\2~i',
                $html,
                $m
            )
        ) {
            $p = $m['p'] ?? null;
            if ($p !== null) {
                return $this->normalizarImporte($p);
            }
        }

        if (
            preg_match(
                '~<meta[^>]*\bcontent=(["\'])(?<p>[0-9]+(?:[.,][0-9]+)?)\1[^>]*\bproperty=(["\'])product:pretax_price:amount\3~i',
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
     * Normaliza "431.19", "431,19" o "1.431,19" a float.
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
     * URLs SOLO dentro de <article class="product-miniature ..."> (listado).
     * Paginación: ?moved&page=2 — conserva query params y sube page +1.
     *
     * Si la categoría no tiene más resultados, PrestaShop muestra page-not-found dentro de
     * #products; el HTML sigue trayendo "Los más vendidos" con product-miniature — hay que
     * cortar aquí y no seguir paginando.
     */
    public function extraerProductosYSiguientePagina(string $html, string $urlPeticionActual): array
    {
        if ($this->esListadoCategoriaPageNotFound($html)) {
            return [
                'urls_productos' => [],
                'siguiente_url'  => null,
            ];
        }

        $urlsProductos = [];
        $base = $this->obtenerBaseUrlDesdeUrlPeticion($urlPeticionActual);

        if (preg_match_all('~<article[^>]*\bproduct-miniature\b[^>]*>(?<block>[\s\S]*?)</article>~i', $html, $articles)) {
            foreach ($articles['block'] as $block) {
                $u = null;
                if (
                    preg_match(
                        '~<h3[^>]*\bproduct-title\b[^>]*>\s*<a[^>]*\bhref=(["\'])(?<u>[^"\']+)\1~i',
                        $block,
                        $m
                    )
                ) {
                    $u = trim((string) ($m['u'] ?? ''));
                }
                if ($u === null || $u === '') {
                    if (
                        preg_match(
                            '~<a[^>]*\bclass=(["\'])[^"\']*\bthumbnail\b[^"\']*\bproduct-thumbnail\b[^"\']*\1[^>]*\bhref=(["\'])(?<u>[^"\']+)\2~i',
                            $block,
                            $m2
                        )
                    ) {
                        $u = trim((string) ($m2['u'] ?? ''));
                    }
                }
                if ($u !== '') {
                    $urlsProductos[] = $this->normalizarUrlCorta($u, $base);
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

    /**
     * Página de categoría sin productos: #products contiene #content.page-not-found
     * ("Lamentamos las molestias..."), no el grid de artículos.
     */
    private function esListadoCategoriaPageNotFound(string $html): bool
    {
        return (bool) preg_match(
            '~<section[^>]*\bid=["\']products["\'][^>]*>[\s\S]*?<section[^>]*\bid=["\']content["\'][^>]*\bpage-not-found\b[^>]*>~i',
            $html
        );
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

        // PrestaShop: la paginación de categoría usa ?moved&page=2; si la URL inicial no
        // trae "moved", se añade al pasar a la página siguiente (y luego solo sube page).
        if (!array_key_exists('moved', $params)) {
            $params['moved'] = '';
        }

        $params['page'] = $siguiente;
        $queryString = $this->construirQueryStringAndorra($params);

        $url = $scheme . '://' . $host . $port . $path;
        if ($queryString !== '') {
            $url .= '?' . $queryString;
        }
        if ($fragment !== '') {
            $url .= '#' . $fragment;
        }

        return $url;
    }

    /**
     * Query tipo PrestaShop: moved sin "=" como flag y el resto con http_build_query.
     * Ej.: moved&page=2 o moved&page=3&otro=filtro
     */
    private function construirQueryStringAndorra(array $params): string
    {
        $movedEmptyFlag = false;
        $movedValor = null;

        if (array_key_exists('moved', $params)) {
            $mv = $params['moved'];
            unset($params['moved']);
            if ($mv === '' || $mv === null) {
                $movedEmptyFlag = true;
            } else {
                $movedValor = (string) $mv;
            }
        }

        $rest = http_build_query($params, '', '&', PHP_QUERY_RFC3986);

        if ($movedValor !== null) {
            return 'moved=' . rawurlencode($movedValor) . ($rest !== '' ? '&' . $rest : '');
        }

        if ($movedEmptyFlag) {
            return $rest !== '' ? 'moved&' . $rest : 'moved';
        }

        return $rest;
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
        $host = $pu['host'] ?? 'www.andorrainformatica.com';

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
