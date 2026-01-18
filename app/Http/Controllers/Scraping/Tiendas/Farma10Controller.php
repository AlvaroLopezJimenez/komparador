<?php

namespace App\Http\Controllers\Scraping\Tiendas;

use App\Http\Controllers\Scraping\Tiendas\PlantillaTiendaController;
use Illuminate\Http\JsonResponse;

class Farma10Controller extends PlantillaTiendaController
{
    /**
     * Extrae el precio de una página de Farma10.
     * - Prioriza <meta property="product:price:amount" content="…">.
     * - Luego <span class="product-price current-price-value" content="…"> (dentro de .current-price).
     * - Después <span class="unit_price">…</span>.
     * - Respaldo: JSON embebido "price": "…".
     * - Devuelve número sin símbolo €; normaliza coma/punto y entidades (&nbsp;).
     */
    public function obtenerPrecio($url, $variante = null, $tienda = null): JsonResponse
    {
        $resultado = $this->apiHTML->obtenerHTML($url, null, $tienda ? $tienda->api : null);

        if (!is_array($resultado) || empty($resultado['success']) || empty($resultado['html'])) {
            return response()->json([
                'success' => false,
                'error'   => is_array($resultado) ? ($resultado['error'] ?? 'No se pudo obtener el HTML') : 'Respuesta inválida de la API',
            ]);
        }

        $html = html_entity_decode((string)$resultado['html'], ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // 1) <meta property="product:price:amount" content="6.58">
        if (preg_match(
            '~<meta[^>]*\bproperty\s*=\s*["\']product:price:amount["\'][^>]*\bcontent\s*=\s*["\'](?<p>[\d\.,]+)["\'][^>]*>~i',
            $html,
            $m1
        ) && !empty($m1['p'])) {
            if (($p = $this->normalizarImporte($m1['p'])) !== null) {
                return response()->json(['success' => true, 'precio' => $p]);
            }
        }

        // 2) <span class="product-price current-price-value" content="6.58">
        if (preg_match(
            '~<span[^>]*\bclass=["\'][^"\']*\bproduct-price\b[^"\']*\bcurrent-price-value\b[^"\']*["\'][^>]*\bcontent=["\'](?<p>[\d\.,]+)["\'][^>]*>~i',
            $html,
            $m2
        ) && !empty($m2['p'])) {
            if (($p = $this->normalizarImporte($m2['p'])) !== null) {
                return response()->json(['success' => true, 'precio' => $p]);
            }
        }

        // 3) <span class="unit_price">6.58</span>
        if (preg_match(
            '~<span[^>]*\bclass=["\'][^"\']*\bunit_price\b[^"\']*["\'][^>]*>\s*(?<p>\d{1,3}(?:[.\s]\d{3})*(?:[.,]\d{2}))\s*<\/span>~i',
            $html,
            $m3
        ) && !empty($m3['p'])) {
            if (($p = $this->normalizarImporte($m3['p'])) !== null) {
                return response()->json(['success' => true, 'precio' => $p]);
            }
        }

        // 4) JSON embebido: "price": "6.58"  (evitar shipping/envío)
        if (preg_match_all('~["\']price["\']\s*:\s*["\']?(?<p>\d+(?:[.,]\d{2}))["\']?~i', $html, $mm, PREG_OFFSET_CAPTURE)) {
            foreach ($mm['p'] as [$val, $off]) {
                $ctx = substr($html, max(0, $off - 120), 240);
                if (stripos($ctx, 'shipping') !== false || stripos($ctx, 'envio') !== false) continue;
                if (($p = $this->normalizarImporte($val)) !== null) {
                    return response()->json(['success' => true, 'precio' => $p]);
                }
            }
        }

        return response()->json([
            'success' => false,
            'error'   => 'No se pudo encontrar el precio en la página de Farma10',
        ]);
    }

    /**
     * Convierte "6,58", "6.58" o "1.234,56" a float con punto decimal.
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


