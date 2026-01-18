<?php

namespace App\Http\Controllers\Scraping\Tiendas;

use App\Http\Controllers\Scraping\Tiendas\PlantillaTiendaController;
use Illuminate\Http\JsonResponse;

class MarvimundoController extends PlantillaTiendaController
{
    /**
     * Extrae el precio de una página de Marvimundo.
     * - Prioriza atributo de Magento: cualquier nodo con data-price-amount="…".
     * - Luego metadatos: <meta property="product:price:amount" content="…">.
     * - Después <meta itemprop="price" content="…">.
     * - Luego <span class="unit_price">…</span>.
     * - Respaldo JSON/LD: bloques con "Offer" y "price": 14.99 (o "price":"14.99"), evitando contexto de envío.
     * - Fallback visible: <span class="price">14,99 €</span> (por si todo lo anterior fallara).
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

        // 1) Cualquier elemento con data-price-amount="14.99"
        if (preg_match(
            '~<[^>]+?\bdata-price-amount\s*=\s*["\'](?<p>\d+(?:[.,]\d{2}))["\']~i',
            $html, $m0
        ) && !empty($m0['p'])) {
            if (($p = $this->normalizarImporte($m0['p'])) !== null) {
                return response()->json(['success' => true, 'precio' => $p]);
            }
        }

        // 2) <meta property="product:price:amount" content="14.99">
        if (preg_match(
            '~<meta[^>]*\bproperty\s*=\s*["\']product:price:amount["\'][^>]*\bcontent\s*=\s*["\'](?<p>[\d\.,]+)["\'][^>]*>~i',
            $html, $m1
        ) && !empty($m1['p'])) {
            if (($p = $this->normalizarImporte($m1['p'])) !== null) {
                return response()->json(['success' => true, 'precio' => $p]);
            }
        }

        // 3) <meta itemprop="price" content="14.99">
        if (preg_match(
            '~<meta[^>]*\bitemprop\s*=\s*["\']price["\'][^>]*\bcontent\s*=\s*["\'](?<p>[\d\.,]+)["\'][^>]*>~i',
            $html, $m2
        ) && !empty($m2['p'])) {
            if (($p = $this->normalizarImporte($m2['p'])) !== null) {
                return response()->json(['success' => true, 'precio' => $p]);
            }
        }

        // 4) <span class="unit_price">14.99</span>
        if (preg_match(
            '~<span[^>]*\bclass=["\'][^"\']*\bunit_price\b[^"\']*["\'][^>]*>\s*(?<p>\d{1,3}(?:[.\s]\d{3})*(?:[.,]\d{2}))\s*</span>~i',
            $html, $m3
        ) && !empty($m3['p'])) {
            if (($p = $this->normalizarImporte($m3['p'])) !== null) {
                return response()->json(['success' => true, 'precio' => $p]);
            }
        }

        // 5) JSON/LD: … "Offer","price":14.99 …  o  "price":"14.99"
        //    (descartamos contextos de shipping/envío por seguridad)
        if (preg_match_all('~"Offer"[^}]*?"price"\s*:\s*("?)(?<p>\d+(?:[.,]\d{2}))\1~i', $html, $mo, PREG_OFFSET_CAPTURE)) {
            foreach ($mo['p'] as [$val, $off]) {
                $ctx = substr($html, max(0, $off - 160), 320);
                if (stripos($ctx, 'shipping') !== false || stripos($ctx, 'envio') !== false) continue;
                if (($p = $this->normalizarImporte($val)) !== null) {
                    return response()->json(['success' => true, 'precio' => $p]);
                }
            }
        }
        // Si no estaba en bloque Offer, probamos un "price":… genérico
        if (preg_match_all('~["\']price["\']\s*:\s*("?)(?<p>\d+(?:[.,]\d{2}))\1~i', $html, $mg, PREG_OFFSET_CAPTURE)) {
            foreach ($mg['p'] as [$val, $off]) {
                $ctx = substr($html, max(0, $off - 160), 320);
                if (stripos($ctx, 'shipping') !== false || stripos($ctx, 'envio') !== false) continue;
                if (($p = $this->normalizarImporte($val)) !== null) {
                    return response()->json(['success' => true, 'precio' => $p]);
                }
            }
        }

        // 6) Fallback visible: <span class="price">14,99 €</span>
        if (preg_match(
            '~<span[^>]*\bclass=["\'][^"\']*\bprice\b[^"\']*["\'][^>]*>\s*(?<p>\d{1,3}(?:[.\s]\d{3})*(?:[.,]\d{2}))\s*(?:€|&euro;)?~i',
            $html, $m6
        ) && !empty($m6['p'])) {
            if (($p = $this->normalizarImporte($m6['p'])) !== null) {
                return response()->json(['success' => true, 'precio' => $p]);
            }
        }

        return response()->json([
            'success' => false,
            'error'   => 'No se pudo encontrar el precio en la página de Marvimundo',
        ]);
    }

    /**
     * Convierte "14,99", "14.99" o "1.234,56" a float con punto decimal.
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


