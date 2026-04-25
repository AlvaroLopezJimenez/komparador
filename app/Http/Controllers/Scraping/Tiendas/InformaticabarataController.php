<?php

namespace App\Http\Controllers\Scraping\Tiendas;

use App\Http\Controllers\Scraping\Tiendas\PlantillaTiendaController;
use App\Models\OfertaProducto;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class InformaticabarataController extends PlantillaTiendaController
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

        // Producto no disponible (mensaje en <li> en la PDP).
        if ($this->esSinStock($html)) {
            if ($oferta && $oferta instanceof OfertaProducto) {
                $oferta->update(['mostrar' => 'no']);

                DB::table('avisos')->insert([
                    'texto_aviso'     => '404 - 1a vez GENERADO AUTOMATICAMENTE',
                    'fecha_aviso'     => now()->addDay(),
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

        $precio = $this->extraerPrecioDesdeCurrentPriceValue($html);
        if ($precio === null) {
            return response()->json([
                'success' => false,
                'error'   => 'No se pudo encontrar el precio en la pagina de Informaticabarata',
            ]);
        }

        return response()->json(['success' => true, 'precio' => $precio]);
    }

    /**
     * Detecta el aviso de producto retirado en la PDP:
     * <li>Este producto ya no esta disponible.</li>
     * (tambien acepta "está" y <li> con atributos)
     */
    private function esSinStock(string $html): bool
    {
        if (preg_match('~<li\b[^>]*>\s*Este\s+producto\s+ya\s+no\s+est[áa]\s+disponible\.?\s*</li>~iu', $html)) {
            return true;
        }

        return false;
    }

    /**
     * Extrae el precio desde:
     * <span class="current-price-value" content="334.88">
     */
    private function extraerPrecioDesdeCurrentPriceValue(string $html): ?float
    {
        if (
            preg_match(
                '~<span[^>]*class=(["\'])[^"\']*current-price-value[^"\']*\1[^>]*\bcontent=(["\'])(?<p>[0-9]+(?:[.,][0-9]+)?)\2~i',
                $html,
                $m
            )
        ) {
            $p = $m['p'] ?? null;
            if ($p !== null) {
                return $this->normalizarImporte($p);
            }
        }

        // Fallback: leer el numero dentro del span (por si cambian el atributo content)
        if (
            preg_match(
                '~<span[^>]*class=(["\'])[^"\']*current-price-value[^"\']*\1[^>]*>\s*(?<p>[0-9][0-9\.\,\s]*[0-9])\s*(?:€|&euro;)?\s*</span>~i',
                $html,
                $m2
            )
        ) {
            $p = $m2['p'] ?? null;
            if ($p !== null) {
                return $this->normalizarImporte($p);
            }
        }

        // Fallback adicional (por si en la PDP usan markup tipo listing):
        // Ej del listado:
        // <span class="price">170,48&nbsp;€</span>
        if (
            preg_match(
                '~<span[^>]*\bclass=(["\'][^"\']*\bprice\b[^"\']*\1)[^>]*>\s*(?<p>[\d\.,]+)\s*(?:&nbsp;)?\s*(?:€|&euro;)?~i',
                $html,
                $m3
            )
        ) {
            $p = $m3['p'] ?? null;
            if ($p !== null) {
                return $this->normalizarImporte($p);
            }
        }

        return null;
    }

    /**
     * Normaliza "334.88" / "334,88" a float con punto decimal.
     */
    private function normalizarImporte(string $importe): ?float
    {
        $importe = str_replace(["\xc2\xa0", "\xe2\x80\xaf", '&nbsp;'], ' ', $importe);

        $s = preg_replace('/[^\d\,\.]/u', '', $importe);
        if ($s === null || $s === '') return null;

        $tieneComa = strpos($s, ',') !== false;
        $tienePunto = strpos($s, '.') !== false;

        if ($tieneComa && $tienePunto) {
            // Si hay ambos, asumimos que el ultimo separador es el decimal.
            $lastComma = strrpos($s, ',');
            $lastDot = strrpos($s, '.');
            $decPos = max($lastComma, $lastDot);
            $intPart = substr($s, 0, $decPos);
            $decPart = substr($s, $decPos + 1);

            $intPart = preg_replace('/[^\d]/', '', $intPart);
            $decPart = preg_replace('/[^\d]/', '', $decPart);

            if ($intPart === '') return null;
            $norm = $decPart === '' ? $intPart . '.00' : $intPart . '.' . substr($decPart, 0, 2);
            return (float) $norm;
        }

        if ($tieneComa) {
            $s = str_replace(',', '.', $s);
        }

        // Si ya viene con punto decimal o es entero
        if (!preg_match('/^\d+(\.\d+)?$/', $s)) return null;

        return (float) $s;
    }

    // -------------------------------------------------------------------------
    // Cron Neo Objetivos - listado de categoria por paginacion
    // -------------------------------------------------------------------------

    /**
     * Tipo de listado de categoria: paginacion (URLs por ?page=N).
     */
    public function tipoListadoCategoria(): ?string
    {
        return 'paginacion';
    }

    /**
     * Extrae URLs de productos desde el HTML de la categoria (pagina actual),
     * y devuelve la URL de la siguiente pagina si existe.
     *
     * @return array{urls_productos: string[], siguiente_url: string|null}
     */
    public function extraerProductosYSiguientePagina(string $html, string $urlPeticionActual): array
    {
        $urlsProductos = [];

        $base = $this->obtenerBaseUrlDesdeUrlPeticion($urlPeticionActual);
        $categoriaRuta = $this->obtenerCategoriaRutaDesdeUrlPeticion($urlPeticionActual); // ej: "tarjetas-graficas"

        // SOLO extraemos las urls de producto desde el anchor del thumbnail:
        // En el listado se ve como:
        //   <a href=".../<categoriaRuta>/<slug>" class="thumbnail product-thumbnail">
        // Si no encontramos esos anchors con esa clase, devolvemos 0 urls (sin inventar con imágenes).
        $pattern = '~<a[^>]*\bclass=(["\'])[^"\']*\bthumbnail\b[^"\']*\bproduct-thumbnail\b[^"\']*\1[^>]*\bhref=(["\'])(?<u>[^"\']+)\2~i';

        if (preg_match_all($pattern, $html, $mA, PREG_SET_ORDER)) {
            foreach ($mA as $row) {
                $u = trim($row['u'] ?? '');
                if ($u === '') continue;

                $uAbs = $this->normalizarUrlCorta($u, $base);
                $urlsProductos[] = $uAbs;
                }
        }

        $urlsProductos = array_values(array_unique($urlsProductos));

        $siguienteUrl = $this->extraerSiguientePaginaUrl($html, $urlPeticionActual, $base);

        return [
            'urls_productos' => $urlsProductos,
            'siguiente_url' => $siguienteUrl,
        ];
    }

    private function obtenerCategoriaRutaDesdeUrlPeticion(string $urlPeticionActual): string
    {
        $pu = parse_url($urlPeticionActual);
        $path = $pu['path'] ?? '';
        $path = trim($path);
        $path = trim($path, '/');

        return $path;
    }

    /**
     * Extrae la "siguiente_url" desde la pagina actual.
     *
     * Busca:
     * - <link rel="next" href="...">
     * - hrefs con ?page=N y devuelve la pagina inmediata posterior.
     */
    private function extraerSiguientePaginaUrl(string $html, string $urlPeticionActual, string $base): ?string
    {
        // 1) rel="next"
        if (preg_match('~<link[^>]+rel=(["\'])next\1[^>]+href=(["\'])(?<u>[^"\']+)\2~i', $html, $m)) {
            $u = trim($m['u'] ?? '');
            if ($u !== '') {
                return $this->normalizarUrlCorta($u, $base);
            }
        }

        // 2) Extraer todos los href con ?page=N y elegir el siguiente
        $pageActual = $this->extraerPageActual($urlPeticionActual);

        $pages = [];
        if (preg_match_all('~href=(["\'])(?<u>[^"\']*\?page=(?<p>\d+)[^"\']*)\1~i', $html, $mm, PREG_SET_ORDER)) {
            foreach ($mm as $row) {
                $p = isset($row['p']) ? (int)$row['p'] : null;
                $u = $row['u'] ?? '';
                if (!$p || $u === '') continue;
                $pages[] = ['page' => $p, 'url' => $u];
            }
        }

        if (!empty($pages)) {
            $siguientes = array_filter($pages, function ($row) use ($pageActual) {
                return $row['page'] > $pageActual;
            });

            if (!empty($siguientes)) {
                usort($siguientes, function ($a, $b) {
                    return $a['page'] <=> $b['page'];
                });

                return $this->normalizarUrlCorta($siguientes[0]['url'], $base);
            }
        }

        return null;
    }

    private function extraerPageActual(string $urlPeticionActual): int
    {
        $pageActual = 1;
        if (preg_match('~[?&]page=(\d+)~', $urlPeticionActual, $m)) {
            $pageActual = (int)($m[1] ?? 1);
            if ($pageActual <= 0) $pageActual = 1;
        }
        return $pageActual;
    }

    private function obtenerBaseUrlDesdeUrlPeticion(string $urlPeticionActual): string
    {
        $pu = parse_url($urlPeticionActual);
        $scheme = $pu['scheme'] ?? 'https';
        $host = $pu['host'] ?? 'informaticabarata.com';
        return $scheme . '://' . $host;
    }

    private function normalizarUrlCorta(string $url, string $base): string
    {
        $url = trim($url);
        if ($url === '') return $url;

        // Absoluta
        if (preg_match('~^https?://~i', $url)) {
            return $url;
        }

        // Relativa tipo /foo?bar=...
        if (str_starts_with($url, '/')) {
            return $base . $url;
        }

        // Relativa tipo foo?page=2
        return $base . '/' . $url;
    }
}

