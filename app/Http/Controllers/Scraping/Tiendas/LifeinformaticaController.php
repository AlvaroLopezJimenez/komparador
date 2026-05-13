<?php

namespace App\Http\Controllers\Scraping\Tiendas;

use App\Http\Controllers\Scraping\Tiendas\PlantillaTiendaController;
use App\Models\OfertaProducto;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class LifeinformaticaController extends PlantillaTiendaController
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

        if ($this->esPagina404($html)) {
            if ($oferta && $oferta instanceof OfertaProducto) {
                $oferta->update(['mostrar' => 'no']);

                DB::table('avisos')->insert([
                    'texto_aviso'     => '404 - 1a vez',
                    'fecha_aviso'     => now()->addHour(),
                    'user_id'         => 1,
                    'avisoable_type'  => OfertaProducto::class,
                    'avisoable_id'    => $oferta->id,
                    'oculto'          => 0,
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ]);
            }

            return response()->json([
                'success' => false,
                'error'   => 'Producto no encontrado (página 404)',
            ]);
        }

        if ($this->esReacondicionadoH1($html)) {
            if ($oferta && $oferta instanceof OfertaProducto) {
                $oferta->update(['mostrar' => 'no']);

                DB::table('avisos')->insert([
                    'texto_aviso'     => 'Reacondicionado 1a vez - Generado Automaticamente',
                    'fecha_aviso'     => now()->addDays(4),
                    'user_id'         => 1,
                    'avisoable_type'  => OfertaProducto::class,
                    'avisoable_id'    => $oferta->id,
                    'oculto'          => 0,
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ]);
            }

            return response()->json([
                'success' => false,
                'error'   => 'Producto reacondicionado',
            ]);
        }

        $precio = $this->extraerPrecioLifeinformatica($html);
        if ($precio === null) {
            return response()->json([
                'success' => false,
                'error'   => 'No se pudo encontrar el precio en la pagina de Lifeinformatica',
            ]);
        }

        return response()->json(['success' => true, 'precio' => $precio]);
    }

    private function esSinStock(string $html): bool
    {
        // Ej.: <p class="disponibilidad not_stock ..."> — "Pendiente de reposición" viene en el layout y no basta.
        return str_contains($html, 'disponibilidad not_stock');
    }

    /**
     * Página de error: <h1 class="text-xs-left">Página no encontrada</h1>
     */
    private function esPagina404(string $html): bool
    {
        return (bool) preg_match(
            '~<h1[^>]*\bclass=(["\'])[^"\']*\btext-xs-left\b[^"\']*\1[^>]*>\s*Página no encontrada\s*</h1>~iu',
            $html
        );
    }

    /**
     * WooCommerce: título en <h1 class="product_title entry-title">…</h1>.
     * Misma acción que PccomponentesController cuando el título indica reacondicionado.
     */
    private function esReacondicionadoH1(string $html): bool
    {
        if (!preg_match('~<h1[^>]*\bproduct_title\b[^>]*>(?<t>[\s\S]*?)</h1>~i', $html, $m)) {
            return false;
        }

        $texto = html_entity_decode(strip_tags((string) ($m['t'] ?? '')), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return stripos($texto, 'reacondicionado') !== false;
    }

    /**
     * Formatos a capturar:
     * - <p class="precio descuento"> 350 <span class="decimales_precio">,93</span> ...
     * - <p class="precio"> 545 <span class="decimales_precio">,61</span> ...
     *
     * Convierte a float: 350.93 / 545.61
     */
    private function extraerPrecioLifeinformatica(string $html): ?float
    {
        // 1) Preferir bloque con descuento si existe.
        if (preg_match('~<p[^>]*\bprecio\b[^>]*\bdescuento\b[^>]*>[\s\S]*?<\/p>~i', $html, $mDesc)) {
            $p = (string) $mDesc[0];
            if (($n = $this->precioDesdeBloque($p)) !== null) return $n;
        }

        // 2) Fallback: bloque sin descuento.
        if (preg_match('~<p[^>]*\bprecio\b[^>]*>[\s\S]*?<\/p>~i', $html, $mBase)) {
            $p = (string) $mBase[0];
            return $this->precioDesdeBloque($p);
        }

        return null;
    }

    private function precioDesdeBloque(string $bloquePrecio): ?float
    {
        if (!preg_match('~<span[^>]*\bentero\b[^>]*>\s*(?<ent>[\d\.\s]+)\s*<\/span>~i', $bloquePrecio, $mEnt)) {
            return null;
        }

        $entero = $mEnt['ent'] ?? '';
        $enteroDigits = preg_replace('/\D/', '', $entero);
        if ($enteroDigits === '') return null;

        // decimales_precio suele aparecer 2 veces: ",93" y "€".
        // Capturamos el primer decimales_precio que contenga algun numero.
        preg_match_all('~<span[^>]*\bdecimales_precio\b[^>]*>\s*(?<t>[^<]+)\s*<\/span>~i', $bloquePrecio, $mDecAll, PREG_SET_ORDER);
        $decValor = null;
        foreach ($mDecAll as $row) {
            $t = trim((string) ($row['t'] ?? ''));
            if ($t === '') continue;
            if (preg_match('/\d/', $t)) {
                $decValor = $t;
                break;
            }
        }

        if ($decValor === null) return null;

        // Ejemplo: ",93" -> "93"
        $decDigits = preg_replace('/\D/', '', $decValor);
        if ($decDigits === '') return null;
        $decDigits = substr($decDigits, -2); // nos quedamos con los ultimos 2
        $decDigits = str_pad($decDigits, 2, '0', STR_PAD_LEFT);

        $precio = (float) ($enteroDigits . '.' . $decDigits);
        return $precio > 0 ? $precio : null;
    }

    // -------------------------------------------------------------------------
    // Cron Neo Objetivos - listado de categoría por paginación (/page/N/)
    // -------------------------------------------------------------------------

    public function tipoListadoCategoria(): ?string
    {
        return 'paginacion';
    }

    /**
     * Listados WooCommerce (LIFE): productos en /tienda/... con
     * woocommerce-LoopProduct-link. Siguiente página: rel="next" o nav
     * woocommerce-pagination (ej. .../tarjetas-graficas/page/4/).
     */
    public function extraerProductosYSiguientePagina(string $html, string $urlPeticionActual): array
    {
        $urlsProductos = [];
        $base = $this->obtenerBaseUrlDesdeUrlPeticion($urlPeticionActual);

        if (preg_match_all(
            '~<a(?=[^>]*\b(?:woocommerce-LoopProduct-link|woocommerce-loop-product__link)\b)(?=[^>]*\bhref=)[^>]*\bhref=(["\'])(?<u>https?://[^"\']+)\1~i',
            $html,
            $m,
            PREG_SET_ORDER
        )) {
            foreach ($m as $row) {
                $u = trim((string) ($row['u'] ?? ''));
                if ($u === '') {
                    continue;
                }
                $u = $this->normalizarUrlCorta($u, $base);
                if (stripos($u, '/tienda/') === false) {
                    continue;
                }
                $urlsProductos[] = $u;
            }
        }

        $urlsProductos = array_values(array_unique($urlsProductos));
        $siguienteUrl = $this->extraerSiguientePaginaUrl($html, $urlPeticionActual, $base);

        return [
            'urls_productos' => $urlsProductos,
            'siguiente_url'  => $siguienteUrl,
        ];
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

        $block = null;
        if (preg_match('~<nav[^>]*\bwoocommerce-pagination\b[^>]*>(?<b>[\s\S]*?)</nav>~i', $html, $mb)) {
            $block = $mb['b'] ?? null;
        }
        if ($block === null) {
            $block = $html;
        }

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
        $host = $pu['host'] ?? 'lifeinformatica.com';

        return $scheme . '://' . $host;
    }

    private function normalizarUrlCorta(string $url, string $base): string
    {
        $url = trim($url);
        if ($url === '') {
            return $url;
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
