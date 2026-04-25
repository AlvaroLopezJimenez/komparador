<?php

namespace App\Http\Controllers\Scraping\Tiendas;

use App\Http\Controllers\Scraping\Tiendas\PlantillaTiendaController;
use App\Models\OfertaProducto;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class BlackmartsController extends PlantillaTiendaController
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

        $precio = $this->extraerPrecioDesdeMetaProductPriceAmount($html);
        if ($precio === null) {
            return response()->json([
                'success' => false,
                'error'   => 'No se pudo encontrar el precio en la pagina de Blackmarts',
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
     * Extrae el precio desde:
     * <meta property="product:price:amount" content="263.77">
     */
    private function extraerPrecioDesdeMetaProductPriceAmount(string $html): ?float
    {
        if (
            preg_match(
                '~<meta[^>]*\bproperty=(["\'])product:price:amount\1[^>]*\bcontent=(["\'])(?<p>[0-9]+(?:[.,][0-9]+)?)\2~i',
                $html,
                $m
            )
        ) {
            $p = $m['p'] ?? null;
            if ($p !== null) {
                return $this->normalizarImporte($p);
            }
        }

        // Fallback por si cambian el orden de atributos en meta.
        if (
            preg_match(
                '~<meta[^>]*\bcontent=(["\'])(?<p>[0-9]+(?:[.,][0-9]+)?)\1[^>]*\bproperty=(["\'])product:price:amount\3~i',
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
     * Normaliza "263.77", "263,77" o "1.263,77" a float.
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
     * Extrae URLs de producto y construye la siguiente URL como page+1
     * mientras en la pagina actual haya productos.
     */
    public function extraerProductosYSiguientePagina(string $html, string $urlPeticionActual): array
    {
        // Si la pagina ya es 404, detener paginacion.
        if (preg_match('~<h2[^>]*\btitle120\b[^>]*>\s*404\s*</h2>~i', $html)) {
            return [
                'urls_productos' => [],
                'siguiente_url'  => null,
            ];
        }

        $urlsProductos = [];
        $base = $this->obtenerBaseUrlDesdeUrlPeticion($urlPeticionActual);

        // Principal: enlace del título del producto.
        if (
            preg_match_all(
                '~<h3[^>]*\bproduct-title\b[^>]*>\s*<a[^>]*\bhref=(["\'])(?<u>[^"\']+)\1~i',
                $html,
                $mTitle
            )
        ) {
            foreach ($mTitle['u'] as $u) {
                $u = trim((string) $u);
                if ($u === '') {
                    continue;
                }
                $urlsProductos[] = $this->normalizarUrlCorta($u, $base);
            }
        }

        // Fallback: thumbnail principal del producto.
        if (
            preg_match_all(
                '~<a[^>]*\bhref=(["\'])(?<u>[^"\']+)\1[^>]*\bclass=(["\'])[^"\']*\bproduct-thumb-link\b[^"\']*\3~i',
                $html,
                $mThumb
            )
        ) {
            foreach ($mThumb['u'] as $u) {
                $u = trim((string) $u);
                if ($u === '') {
                    continue;
                }
                $urlsProductos[] = $this->normalizarUrlCorta($u, $base);
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

        $paginaActual = $this->extraerNumeroPaginaActual($urlPeticionActual);
        $siguiente = max(1, $paginaActual + 1);

        $scheme = $parts['scheme'] ?? 'https';
        $host = $parts['host'];
        $port = isset($parts['port']) ? ':' . (int) $parts['port'] : '';
        $path = $parts['path'] ?? '/';
        $query = $parts['query'] ?? '';
        $fragment = $parts['fragment'] ?? '';

        // Si ya viene en /page/N/, reemplazar; si no, añadir /page/2/.
        if (preg_match('~/page/\d+/?$~i', $path)) {
            $path = preg_replace('~/page/\d+/?$~i', '/page/' . $siguiente . '/', $path);
        } else {
            $path = rtrim($path, '/') . '/page/' . $siguiente . '/';
        }

        $url = $scheme . '://' . $host . $port . $path;
        if ($query !== '') {
            $url .= '?' . $query;
        }
        if ($fragment !== '') {
            $url .= '#' . $fragment;
        }

        return $url;
    }

    private function extraerNumeroPaginaActual(string $urlPeticionActual): int
    {
        $path = (string) (parse_url($urlPeticionActual, PHP_URL_PATH) ?? '');
        if (preg_match('~/page/(\d+)/?$~i', $path, $m)) {
            return max(1, (int) ($m[1] ?? 1));
        }
        return 1;
    }

    private function obtenerBaseUrlDesdeUrlPeticion(string $urlPeticionActual): string
    {
        $pu = parse_url($urlPeticionActual);
        $scheme = $pu['scheme'] ?? 'https';
        $host = $pu['host'] ?? 'blackmarts.es';

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

