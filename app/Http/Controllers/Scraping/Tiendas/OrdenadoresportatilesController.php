<?php

namespace App\Http\Controllers\Scraping\Tiendas;

use App\Http\Controllers\Scraping\Tiendas\PlantillaTiendaController;
use App\Models\OfertaProducto;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class OrdenadoresportatilesController extends PlantillaTiendaController
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
                    'texto_aviso'     => 'Sin stock 1a vez - GENERADO AUTOMATICAMENTE',
                    'fecha_aviso'     => now()->addDays(4),
                    'user_id'         => 1,
                    'avisoable_type'  => OfertaProducto::class,
                    'avisoable_id'    => $oferta->id,
                    'oculto'          => 0,
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ]);
            }

            return response()->json(['success' => false, 'error' => 'Producto sin stock']);
        }

        $precio = $this->extraerPrecioDesdePriceJson($html);
        if ($precio === null) {
            return response()->json([
                'success' => false,
                'error'   => 'No se pudo encontrar el precio en la pagina de Ordenadoresportatiles',
            ]);
        }

        return response()->json(['success' => true, 'precio' => $precio]);
    }

    private function esSinStock(string $html): bool
    {
        return preg_match(
            '~<li>\s*Este producto ya no esta disponible\.\s*</li>~i',
            $html
        ) === 1;
    }

    /**
     * Precio desde patrón JSON estricto:
     *   "price": "355.5",
     *
     * Buscamos exactamente "price": "..." para evitar otros usos de "price".
     */
    private function extraerPrecioDesdePriceJson(string $html): ?float
    {
        // Variante principal: "price": "355.5"
        if (preg_match('~"price"\s*:\s*"(?<p>\d+(?:[.,]\d+)?)"~i', $html, $m) && !empty($m['p'])) {
            return $this->normalizarImporte($m['p']);
        }

        // Fallback raro: por si en algún caso viene sin comillas en el valor.
        if (preg_match('~"price"\s*:\s*(?<p>\d+(?:[.,]\d+)?)~i', $html, $m2) && !empty($m2['p'])) {
            return $this->normalizarImporte($m2['p']);
        }

        return null;
    }

    private function normalizarImporte(string $importe): ?float
    {
        $importe = str_replace(["\xc2\xa0", "\xe2\x80\xaf", '&nbsp;', ' '], '', $importe);

        $s = preg_replace('/[^\d\.,]/u', '', $importe);
        if ($s === null || $s === '') return null;

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

        if ($intPart === '') return null;
        $decPart = $decPart === '' ? '00' : str_pad($decPart, 2, '0', STR_PAD_RIGHT);

        return (float) ($intPart . '.' . substr($decPart, 0, 2));
    }

    // -------------------------------------------------------------------------
    // Cron Neo Objetivos - listado de categoria por paginacion
    // -------------------------------------------------------------------------

    public function tipoListadoCategoria(): ?string
    {
        return 'paginacion';
    }

    /**
     * Extrae URLs de producto desde la página de listado.
     * Del HTML que has pasado, tomamos href en anchors de producto:
     * - a.thumbnail.product-thumbnail
     * - h3.product-title a
     *
     * @return array{urls_productos: string[], siguiente_url: string|null}
     */
    public function extraerProductosYSiguientePagina(string $html, string $urlPeticionActual): array
    {
        $urlsProductos = [];
        $base = $this->obtenerBaseUrlDesdeUrlPeticion($urlPeticionActual);

        // 1) Thumbnail principal del producto.
        if (preg_match_all(
            '~<a[^>]*\bclass=(["\'])[^"\']*\bthumbnail\b[^"\']*\bproduct-thumbnail\b[^"\']*\1[^>]*\bhref=(["\'])(?<u>[^"\']+)\2~i',
            $html,
            $mThumb,
            PREG_SET_ORDER
        )) {
            foreach ($mThumb as $row) {
                $u = trim((string) ($row['u'] ?? ''));
                if ($u === '') continue;
                $urlsProductos[] = $this->normalizarUrlCorta($u, $base);
            }
        }

        // 2) Título del producto (fallback/duplicado, luego deduplicamos).
        if (preg_match_all(
            '~<h3[^>]*\bproduct-title\b[^>]*>\s*<a[^>]*\bhref=(["\'])(?<u>[^"\']+)\1~i',
            $html,
            $mTitle,
            PREG_SET_ORDER
        )) {
            foreach ($mTitle as $row) {
                $u = trim((string) ($row['u'] ?? ''));
                if ($u === '') continue;
                $urlsProductos[] = $this->normalizarUrlCorta($u, $base);
            }
        }

        $urlsProductos = array_values(array_unique($urlsProductos));
        $siguienteUrl = $this->extraerSiguientePaginaUrl($html, $urlPeticionActual, $base);

        return [
            'urls_productos' => $urlsProductos,
            'siguiente_url' => $siguienteUrl,
        ];
    }

    private function extraerSiguientePaginaUrl(string $html, string $urlPeticionActual, string $base): ?string
    {
        // 1) rel="next" (si existe)
        if (preg_match('~<link[^>]+rel=(["\'])next\1[^>]+href=(["\'])(?<u>[^"\']+)\2~i', $html, $mNext)) {
            $u = trim((string) ($mNext['u'] ?? ''));
            if ($u !== '') return $this->normalizarUrlCorta($u, $base);
        }

        // 2) Buscar href con ?page=N y quedarnos con la siguiente a la actual.
        $pageActual = $this->extraerPageActual($urlPeticionActual);
        $candidatas = [];

        if (preg_match_all('~href=(["\'])(?<u>[^"\']*\?page=(?<p>\d+)[^"\']*)\1~i', $html, $mm, PREG_SET_ORDER)) {
            foreach ($mm as $row) {
                $u = trim((string) ($row['u'] ?? ''));
                $p = isset($row['p']) ? (int) $row['p'] : 0;
                if ($u === '' || $p <= 0) continue;
                $candidatas[] = ['page' => $p, 'url' => $u];
            }
        }

        if (empty($candidatas)) return null;

        $siguientes = array_filter($candidatas, function ($c) use ($pageActual) {
            return $c['page'] > $pageActual;
        });
        if (empty($siguientes)) return null;

        usort($siguientes, fn($a, $b) => $a['page'] <=> $b['page']);
        return $this->normalizarUrlCorta($siguientes[0]['url'], $base);
    }

    private function extraerPageActual(string $urlPeticionActual): int
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
        $host = $pu['host'] ?? 'ordenadores-portatiles.com';
        return $scheme . '://' . $host;
    }

    private function normalizarUrlCorta(string $url, string $base): string
    {
        $url = trim($url);
        if ($url === '') return $url;

        if (preg_match('~^https?://~i', $url)) {
            return $url;
        }

        if (str_starts_with($url, '/')) {
            return $base . $url;
        }

        return $base . '/' . $url;
    }
}

