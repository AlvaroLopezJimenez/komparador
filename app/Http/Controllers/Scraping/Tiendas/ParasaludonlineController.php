<?php

namespace App\Http\Controllers\Scraping\Tiendas;

use App\Http\Controllers\Scraping\Tiendas\PlantillaTiendaController;
use Illuminate\Http\JsonResponse;

class ParasaludonlineController extends PlantillaTiendaController
{
    /**
     * Extrae el precio de la página.
     * - Prioriza metadatos: <meta property="product:price:amount" content="…"> y <meta itemprop="price" content="…">.
     * - Luego span con itemprop="price": <span class="current-price-value" itemprop="price" content="…">.
     * - Después valores visibles: <span class="current-price-value-rounded">…</span>.
     * - Como respaldo, JSON embebido: ecomm_totalvalue, "price":"…", y por último "value":"…"
     *   (ignorando coincidencias cercanas a “shipping”/“envio”).
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

        // 1) <meta property="product:price:amount" content="6.55">
        if (preg_match(
            '~<meta[^>]*\bproperty\s*=\s*["\']product:price:amount["\'][^>]*\bcontent\s*=\s*["\'](?<p>[\d\.,]+)["\'][^>]*>~i',
            $html,
            $m1
        ) && !empty($m1['p'])) {
            if (($p = $this->normalizarImporte($m1['p'])) !== null) {
                return response()->json(['success' => true, 'precio' => $p]);
            }
        }

        // 2) <span class="current-price-value" itemprop="price" content="6.55">
        if (preg_match(
            '~<span[^>]*\bclass=["\'][^"\']*\bcurrent-price-value\b[^"\']*["\'][^>]*\bitemprop=["\']price["\'][^>]*\bcontent=["\'](?<p>[\d\.,]+)["\'][^>]*>~i',
            $html,
            $m2
        ) && !empty($m2['p'])) {
            if (($p = $this->normalizarImporte($m2['p'])) !== null) {
                return response()->json(['success' => true, 'precio' => $p]);
            }
        }

        // 3) <meta itemprop="price" content="6.55">
        if (preg_match(
            '~<meta[^>]*\bitemprop\s*=\s*["\']price["\'][^>]*\bcontent\s*=\s*["\'](?<p>[\d\.,]+)["\'][^>]*>~i',
            $html,
            $m3
        ) && !empty($m3['p'])) {
            if (($p = $this->normalizarImporte($m3['p'])) !== null) {
                return response()->json(['success' => true, 'precio' => $p]);
            }
        }

        // 4) <span class="current-price-value-rounded" style="display:none">6.55</span>
        if (preg_match(
            '~<span[^>]*\bclass=["\'][^"\']*\bcurrent-price-value-rounded\b[^"\']*["\'][^>]*>\s*(?<p>\d{1,3}(?:[.\s]\d{3})*(?:[.,]\d{2}))~i',
            $html,
            $m4
        ) && !empty($m4['p'])) {
            if (($p = $this->normalizarImporte($m4['p'])) !== null) {
                return response()->json(['success' => true, 'precio' => $p]);
            }
        }

        // 5) JSON embebido: ecomm_totalvalue
        if (preg_match('~["\']ecomm_totalvalue["\']\s*:\s*(?<p>\d+(?:\.\d+)?)~i', $html, $m5) && !empty($m5['p'])) {
            if (($p = $this->normalizarImporte($m5['p'])) !== null) {
                return response()->json(['success' => true, 'precio' => $p]);
            }
        }

        // 6) JSON embebido: "price":"6.55" (evitar bloques cercanos a shipping/envio)
        if (preg_match_all('~["\']price["\']\s*:\s*["\']?(?<p>\d+(?:[.,]\d{2}))["\']?~i', $html, $mm, PREG_OFFSET_CAPTURE)) {
            foreach ($mm['p'] as [$val, $off]) {
                $ctx = substr($html, max(0, $off - 120), 120);
                if (stripos($ctx, 'shipping') !== false || stripos($ctx, 'envio') !== false) {
                    continue; // ignorar precios en contexto de envío
                }
                if (($p = $this->normalizarImporte($val)) !== null) {
                    return response()->json(['success' => true, 'precio' => $p]);
                }
            }
        }

        // 7) JSON embebido: "value":"6.55" (último recurso, con la misma salvaguarda)
        if (preg_match_all('~["\']value["\']\s*:\s*["\']?(?<p>\d+(?:[.,]\d{2}))["\']?~i', $html, $mv, PREG_OFFSET_CAPTURE)) {
            foreach ($mv['p'] as [$val, $off]) {
                $ctx = substr($html, max(0, $off - 120), 120);
                if (stripos($ctx, 'shipping') !== false || stripos($ctx, 'envio') !== false) {
                    continue;
                }
                if (($p = $this->normalizarImporte($val)) !== null) {
                    return response()->json(['success' => true, 'precio' => $p]);
                }
            }
        }

        return response()->json([
            'success' => false,
            'error'   => 'No se pudo encontrar el precio en la página',
        ]);
    }

    /**
     * Convierte "6,55", "6.55" o "1.234,56" a float con punto decimal.
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


