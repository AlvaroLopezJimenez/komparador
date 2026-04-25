<?php

namespace App\Http\Controllers\Scraping\Tiendas;

use App\Http\Controllers\Scraping\Tiendas\PlantillaTiendaController;
use App\Models\OfertaProducto;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class BeepController extends PlantillaTiendaController
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

            return response()->json(['success' => false, 'error' => 'Artículo no disponible en Beep.']);
        }

        if ($this->esPagina404($html)) {
            if ($oferta && $oferta instanceof OfertaProducto) {
                $oferta->update(['mostrar' => 'no']);

                DB::table('avisos')->insert([
                    'texto_aviso'    => 'PRODUCTO NO ENCONTRADO (404) - 1a vez Generado Automáticamente',
                    'fecha_aviso'    => now()->addHour(),
                    'user_id'        => 1,
                    'avisoable_type' => OfertaProducto::class,
                    'avisoable_id'   => $oferta->id,
                    'oculto'         => 0,
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ]);
            }

            return response()->json([
                'success' => false,
                'error'   => 'Producto no encontrado (página 404)',
            ]);
        }

        $precio = $this->extraerPrecioDesdeProductPriceAmount($html);
        if ($precio === null) {
            return response()->json([
                'success' => false,
                'error'   => 'No se pudo encontrar el precio en la pagina de Beep',
            ]);
        }

        return response()->json(['success' => true, 'precio' => $precio]);
    }

    private function esSinStock(string $html): bool
    {
        return str_contains($html, 'Sin Stock')
            && str_contains($html, 'Producto no disponible actualmente');
    }

    /**
     * Página de error 404 de Beep (producto eliminado o URL inválida).
     */
    private function esPagina404(string $html): bool
    {
        return strpos($html, '<h1 class="mb-4 text-6xl font-bold">404</h1>') !== false;
    }

    /**
     * Extrae precio desde:
     * - product:price:amount\",\"content\":\"358.49\"}
     * - <meta name="product:price:amount" content="358.49">
     */
    private function extraerPrecioDesdeProductPriceAmount(string $html): ?float
    {
        if (
            preg_match(
                '~product:price:amount\\\\",\\\\\"content\\\\":\\\\\"(?<p>[0-9]+(?:[.,][0-9]+)?)\\\\\"~i',
                $html,
                $mEscaped
            )
        ) {
            $p = $mEscaped['p'] ?? null;
            if ($p !== null) {
                return $this->normalizarImporte($p);
            }
        }

        if (
            preg_match(
                '~<meta[^>]*\bname=(["\'])product:price:amount\1[^>]*\bcontent=(["\'])(?<p>[0-9]+(?:[.,][0-9]+)?)\2~i',
                $html,
                $mMeta
            )
        ) {
            $p = $mMeta['p'] ?? null;
            if ($p !== null) {
                return $this->normalizarImporte($p);
            }
        }

        // Fallback por si cambian el orden de atributos.
        if (
            preg_match(
                '~<meta[^>]*\bcontent=(["\'])(?<p>[0-9]+(?:[.,][0-9]+)?)\1[^>]*\bname=(["\'])product:price:amount\3~i',
                $html,
                $mMeta2
            )
        ) {
            $p = $mMeta2['p'] ?? null;
            if ($p !== null) {
                return $this->normalizarImporte($p);
            }
        }

        return null;
    }

    /**
     * Normaliza "358.49", "358,49" o "1.358,49" a float.
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
    // Cron Neo Objetivos - listado de categoria por paginacion (/page-N)
    // -------------------------------------------------------------------------

    public function tipoListadoCategoria(): ?string
    {
        return 'paginacion';
    }

    /**
     * Extrae URLs de producto y construye la siguiente URL como /page-N+1
     * mientras en la pagina actual haya productos.
     */
    public function extraerProductosYSiguientePagina(string $html, string $urlPeticionActual): array
    {
        $urlsProductos = [];
        $base = $this->obtenerBaseUrlDesdeUrlPeticion($urlPeticionActual);

        // Producto en listado: <a data-testid="product-card" href="/es/product/...">
        if (
            preg_match_all(
                '~<a[^>]*\bdata-testid=(["\'])product-card\1[^>]*\bhref=(["\'])(?<u>[^"\']+)\2~i',
                $html,
                $mCard
            )
        ) {
            foreach ($mCard['u'] as $u) {
                $u = trim((string) $u);
                if ($u === '') {
                    continue;
                }
                $urlsProductos[] = $this->normalizarUrlCorta($u, $base);
            }
        }

        // Fallback: cualquier href que sea /es/product/...
        if (
            preg_match_all(
                '~<a[^>]*\bhref=(["\'])(?<u>/es/product/[^"\']+)\1~i',
                $html,
                $mProduct
            )
        ) {
            foreach ($mProduct['u'] as $u) {
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

        // Elimina posible sufijo /page-N previo y construye el nuevo.
        $pathBase = preg_replace('~/page-\d+/?$~i', '', $path);
        $pathBase = rtrim((string) $pathBase, '/');
        $nuevoPath = $pathBase . '/page-' . $siguiente;

        $url = $scheme . '://' . $host . $port . $nuevoPath;
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
        if (preg_match('~/page-(\d+)/?$~i', $path, $m)) {
            return max(1, (int) ($m[1] ?? 1));
        }
        return 1;
    }

    private function obtenerBaseUrlDesdeUrlPeticion(string $urlPeticionActual): string
    {
        $pu = parse_url($urlPeticionActual);
        $scheme = $pu['scheme'] ?? 'https';
        $host = $pu['host'] ?? 'www.beep.es';

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

