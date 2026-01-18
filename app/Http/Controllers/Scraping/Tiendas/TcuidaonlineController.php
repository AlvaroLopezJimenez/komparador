<?php

namespace App\Http\Controllers\Scraping\Tiendas;

use App\Http\Controllers\Scraping\Tiendas\PlantillaTiendaController;
use Illuminate\Http\JsonResponse;

class TcuidaonlineController extends PlantillaTiendaController
{
    /**
     * Extrae el precio de una página de Tcuidaonline.
     * - Prioriza <meta property="product:price:amount" content="…">.
     * - Luego <span itemprop="price" class="product-price" content="…"> (dentro de .current-price).
     * - Después <div id="pepper-product-price">…</div> (oculto).
     * - Luego <span class="unit_price">…</span>.
     * - Respaldo JSON: patrones tipo `value : 15.77` y `"value":{"label":"value","value":15.77}` (evitando shipping/envío).
     * - Fallback visible: texto dentro de .product-price (ej. “15,77 €”).
     * - Devuelve número sin símbolo €; normaliza coma/punto y entidades (&nbsp;).
     */
    public function obtenerPrecio($url, $variante = null, $tienda = null): JsonResponse
    {
        $r = $this->apiHTML->obtenerHTML($url, null, $tienda ? $tienda->api : null);

        if (!is_array($r) || empty($r['success']) || empty($r['html'])) {
            return response()->json([
                'success' => false,
                'error'   => is_array($r) ? ($r['error'] ?? 'No se pudo obtener el HTML') : 'Respuesta inválida de la API',
            ]);
        }

        $html = html_entity_decode((string)$r['html'], ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // 1) <meta property="product:price:amount" content="15.77">
        if (preg_match(
            '~<meta[^>]*\bproperty\s*=\s*["\']product:price:amount["\'][^>]*\bcontent\s*=\s*["\'](?<p>[\d\.,]+)["\'][^>]*>~i',
            $html, $m1
        ) && !empty($m1['p'])) {
            if (($p = $this->normalizarImporte($m1['p'])) !== null) {
                return response()->json(['success' => true, 'precio' => $p]);
            }
        }

        // 2) <span itemprop="price" class="product-price" content="15.77">
        if (preg_match(
            '~<span[^>]*\bitemprop=["\']price["\'][^>]*\bclass=["\'][^"\']*\bproduct-price\b[^"\']*["\'][^>]*\bcontent=["\'](?<p>[\d\.,]+)["\']~i',
            $html, $m2
        ) && !empty($m2['p'])) {
            if (($p = $this->normalizarImporte($m2['p'])) !== null) {
                return response()->json(['success' => true, 'precio' => $p]);
            }
        }

        // 3) <div id="pepper-product-price">15.77</div>
        if (preg_match(
            '~<div[^>]*\bid=["\']pepper-product-price["\'][^>]*>\s*(?<p>[\d\.,]+)\s*</div>~i',
            $html, $m3
        ) && !empty($m3['p'])) {
            if (($p = $this->normalizarImporte($m3['p'])) !== null) {
                return response()->json(['success' => true, 'precio' => $p]);
            }
        }

        // 4) <span class="unit_price">15.77</span>
        if (preg_match(
            '~<span[^>]*\bclass=["\'][^"\']*\bunit_price\b[^"\']*["\'][^>]*>\s*(?<p>\d{1,3}(?:[.\s]\d{3})*(?:[.,]\d{2}))\s*</span>~i',
            $html, $m4
        ) && !empty($m4['p'])) {
            if (($p = $this->normalizarImporte($m4['p'])) !== null) {
                return response()->json(['success' => true, 'precio' => $p]);
            }
        }

        // 5) JSON estilo: value : 15.77
        if (preg_match_all('~\bvalue\s*:\s*(?<p>\d+(?:[.,]\d{2}))\b~i', $html, $mv, PREG_OFFSET_CAPTURE)) {
            foreach ($mv['p'] as [$val, $off]) {
                $ctx = substr($html, max(0, $off - 120), 240);
                if (stripos($ctx, 'shipping') !== false || stripos($ctx, 'envio') !== false) continue;
                if (($p = $this->normalizarImporte($val)) !== null) {
                    return response()->json(['success' => true, 'precio' => $p]);
                }
            }
        }

        // 6) JSON estilo: "value":{"label":"value","value":15.77}
        if (preg_match_all('~"value"\s*:\s*\{\s*"label"\s*:\s*"value"\s*,\s*"value"\s*:\s*(?<p>\d+(?:[.,]\d{2}))~i', $html, $mm, PREG_OFFSET_CAPTURE)) {
            foreach ($mm['p'] as [$val, $off]) {
                $ctx = substr($html, max(0, $off - 120), 240);
                if (stripos($ctx, 'shipping') !== false || stripos($ctx, 'envio') !== false) continue;
                if (($p = $this->normalizarImporte($val)) !== null) {
                    return response()->json(['success' => true, 'precio' => $p]);
                }
            }
        }

        // 7) Fallback visible: <span class="product-price">15,77 €</span>
        if (preg_match(
            '~<span[^>]*\bclass=["\'][^"\']*\bproduct-price\b[^"\']*["\'][^>]*>\s*(?<p>\d{1,3}(?:[.\s]\d{3})*(?:[.,]\d{2}))\s*(?:€|&euro;)?~i',
            $html, $m7
        ) && !empty($m7['p'])) {
            if (($p = $this->normalizarImporte($m7['p'])) !== null) {
                return response()->json(['success' => true, 'precio' => $p]);
            }
        }

        return response()->json([
            'success' => false,
            'error'   => 'No se pudo encontrar el precio en la página de Tcuidaonline',
        ]);
    }

    /**
     * Convierte "15,77", "15.77" o "1.234,56" a float con punto decimal.
     */
    private function normalizarImporte(string $importe): ?float
    {
        $s = preg_replace('/[^\d\.,]/u', '', $importe);
        if ($s === null || $s === '') return null;

        $tieneComa  = strpos($s, ',') !== false;
        $tienePunto = strpos($s, '.') !== false;

        if ($tieneComa && $tienePunto) {
            $lastComma = strrpos($s, ',');
            $lastDot   = strrpos($s, '.');
            if ($lastComma !== false && ($lastDot === false || $lastComma > $lastDot)) {
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
}


