<?php

namespace App\Http\Controllers\Scraping\Tiendas;

use App\Http\Controllers\Scraping\Tiendas\PlantillaTiendaController;
use Illuminate\Http\JsonResponse;

class FarmaciaencasaController extends PlantillaTiendaController
{
    /**
     * Extrae el precio de una página de Farmaciaencasa.
     * - Prioriza <meta itemprop="price" content="…">.
     * - Fallback visible: <span class="price">3,93 €</span>.
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

        // 1) <meta itemprop="price" content="3.93">
        if (preg_match(
            '~<meta[^>]*\bitemprop\s*=\s*["\']price["\'][^>]*\bcontent\s*=\s*["\'](?<p>[\d\.,]+)["\'][^>]*>~i',
            $html,
            $m1
        ) && !empty($m1['p'])) {
            if (($p = $this->normalizarImporte($m1['p'])) !== null) {
                return response()->json(['success' => true, 'precio' => $p]);
            }
        }

        // 2) Visible: <span class="price">3,93 €</span>
        if (preg_match(
            '~<span[^>]*\bclass=["\'][^"\']*\bprice\b[^"\']*["\'][^>]*>\s*(?<p>\d{1,3}(?:[.\s]\d{3})*(?:[.,]\d{2}))\s*(?:€|&euro;)?\s*</span>~i',
            $html,
            $m2
        ) && !empty($m2['p'])) {
            if (($p = $this->normalizarImporte($m2['p'])) !== null) {
                return response()->json(['success' => true, 'precio' => $p]);
            }
        }

        return response()->json([
            'success' => false,
            'error'   => 'No se pudo encontrar el precio en la página de Farmaciaencasa',
        ]);
    }

    /**
     * Convierte "3,93", "3.93" o "1.234,56" a float con punto decimal.
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


