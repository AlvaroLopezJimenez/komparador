<?php

namespace App\Http\Controllers\Scraping\Tiendas;

use App\Http\Controllers\Scraping\Tiendas\PlantillaTiendaController;
use App\Models\OfertaProducto;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class OcasioniaController extends PlantillaTiendaController
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
                    'texto_aviso'     => 'SIN STOCK 1A VEZ - GENERADO AUTOMATICAMENTE',
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

        $precio = $this->extraerPrecioDesdeWooAmount($html);
        if ($precio === null) {
            return response()->json([
                'success' => false,
                'error'   => 'No se pudo encontrar el precio en la pagina de Ocasionia',
            ]);
        }

        return response()->json(['success' => true, 'precio' => $precio]);
    }

    /**
     * WooCommerce: <p class="stock out-of-stock">Agotado</p>
     */
    private function esSinStock(string $html): bool
    {
        return (bool) preg_match(
            '~<p[^>]+class=(["\'])(?=[^"\']*\bout-of-stock\b)(?=[^"\']*\bstock\b)[^"\']*\1[^>]*>\s*Agotado\s*</p>~iu',
            $html
        );
    }

    /**
     * Extrae precio desde:
     * <span class="woocommerce-Price-amount amount">348,92&nbsp;
     */
    private function extraerPrecioDesdeWooAmount(string $html): ?float
    {
        // Captura el primer numero con coma/punto decimal dentro del span amount.
        if (
            preg_match(
                '~<span[^>]*\bwoocommerce-Price-amount\b[^>]*\bamount\b[^>]*>\s*(?<p>[\d\.,]+)~i',
                $html,
                $m
            ) &&
            !empty($m['p'])
        ) {
            return $this->normalizarImporte($m['p']);
        }

        return null;
    }

    /**
     * Normaliza "348,92" / "348.92" y tambien "1.234,56" a float.
     */
    private function normalizarImporte(string $importe): ?float
    {
        $importe = str_replace(["\xc2\xa0", "\xe2\x80\xaf", '&nbsp;', ' '], '', $importe);

        $s = preg_replace('/[^\d\.,]/u', '', $importe);
        if ($s === null || $s === '') return null;

        $tieneComa = strpos($s, ',') !== false;
        $tienePunto = strpos($s, '.') !== false;

        if ($tieneComa && $tienePunto) {
            // Si hay coma y punto, el ultimo separador es el decimal.
            $lastComma = strrpos($s, ',');
            $lastDot = strrpos($s, '.');
            $decPos = max($lastComma, $lastDot);

            $intPart = substr($s, 0, $decPos);
            $decPart = substr($s, $decPos + 1);
        } elseif ($tieneComa) {
            // Caso tipico ES: coma decimal
            $parts = explode(',', $s, 2);
            $intPart = $parts[0];
            $decPart = $parts[1] ?? '';
        } else {
            // Caso int o punto decimal
            $parts = explode('.', $s, 2);
            $intPart = $parts[0];
            $decPart = $parts[1] ?? '';
        }

        $intPart = preg_replace('/[^\d]/', '', $intPart);
        $decPart = preg_replace('/[^\d]/', '', $decPart);

        if ($intPart === '') return null;
        $decPart = $decPart === '' ? '00' : str_pad($decPart, 2, '0', STR_PAD_RIGHT);

        $norm = $intPart . '.' . substr($decPart, 0, 2);
        return (float) $norm;
    }

    // -------------------------------------------------------------------------
    // Cron Neo Objetivos - listado de categoria por paginacion
    // -------------------------------------------------------------------------

    public function tipoListadoCategoria(): ?string
    {
        return 'paginacion';
    }

    /**
     * En el HTML de la pagina de categoria se ven URLs de producto en:
     * <h3 class="product_item--title"><a href="https://ocasionia.com/...">...</a></h3>
     */
    public function extraerProductosYSiguientePagina(string $html, string $urlPeticionActual): array
    {
        $urlsProductos = [];

        $base = $this->obtenerBaseUrlDesdeUrlPeticion($urlPeticionActual);

        // 1) Extraer URLs desde el anchor del titulo del producto (estrictamente).
        if (
            preg_match_all(
                '~<h3[^>]*\bproduct_item--title\b[^>]*>\s*<a[^>]*\bhref=(["\'])(?<u>[^"\']+)\1~i',
                $html,
                $m
            )
        ) {
            foreach ($m['u'] as $u) {
                $u = trim((string) $u);
                if ($u === '') continue;
                $urlsProductos[] = $this->normalizarUrlCorta($u, $base);
            }
        }

        $urlsProductos = array_values(array_unique($urlsProductos));

        // 2) Siguiente pagina (si no existe, null para que el cron pare)
        $siguienteUrl = $this->extraerSiguientePaginaUrl($html, $urlPeticionActual, $base);

        return [
            'urls_productos' => $urlsProductos,
            'siguiente_url' => $siguienteUrl,
        ];
    }

    private function extraerSiguientePaginaUrl(string $html, string $urlPeticionActual, string $base): ?string
    {
        $paginaActual = $this->extraerNumeroPaginaActual($urlPeticionActual);

        // 1) Preferimos rel="next" si apunta a una página estrictamente posterior (evita bucles a /page/1/).
        //    Si el enlace next es inválido o no existe, seguimos con el bloque de paginación.
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

        // 2) Si no hay rel next válido, intentamos dentro de la paginacion WooCommerce (clase/DOM típico).
        // Capturamos un "bloque" de paginacion y luego extraemos href con parametros de pagina.
        $block = null;
        if (preg_match('~<nav[^>]*\bwoocommerce-pagination\b[^>]*>(?<b>[\s\S]*?)</nav>~i', $html, $mb)) {
            $block = $mb['b'] ?? null;
        }
        if ($block === null) {
            // Fallback: buscar links de paginacion sin depender del nav exacto.
            $block = $html;
        }

        $candidatas = [];

        // Captura href con ?page=, ?paged=, ?product-page=
        if (preg_match_all('~href=(["\'])(?<u>[^"\']*\?(?:page|paged|product-page)=(?<n>\d+)[^"\']*)\1~i', $block, $mm, PREG_SET_ORDER)) {
            foreach ($mm as $row) {
                $u = trim((string) ($row['u'] ?? ''));
                $n = isset($row['n']) ? (int) $row['n'] : null;
                if ($u === '' || !$n) continue;
                $candidatas[] = ['page' => $n, 'url' => $u];
            }
        }

        // Captura href con /page/N/
        if (preg_match_all('~href=(["\'])(?<u>[^"\']*\/page\/(?<n>\d+)\/?[^"\']*)\1~i', $block, $m2, PREG_SET_ORDER)) {
            foreach ($m2 as $row) {
                $u = trim((string) ($row['u'] ?? ''));
                $n = isset($row['n']) ? (int) $row['n'] : null;
                if ($u === '' || !$n) continue;
                $candidatas[] = ['page' => $n, 'url' => $u];
            }
        }

        if (empty($candidatas)) return null;

        // Elegir la candidata con el menor numero mayor que la actual.
        $siguientes = array_filter($candidatas, function ($c) use ($paginaActual) {
            return $c['page'] > $paginaActual;
        });

        if (empty($siguientes)) {
            // Última página (o solo enlaces a páginas ya visitadas): fin de listado — no volver a /page/1/ (bucle).
            return null;
        }

        usort($siguientes, fn($a, $b) => $a['page'] <=> $b['page']);
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
        $host = $pu['host'] ?? 'ocasionia.com';
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

