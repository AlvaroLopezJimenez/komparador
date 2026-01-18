<?php

namespace App\Http\Controllers\Scraping\Tiendas;

use App\Http\Controllers\Scraping\Tiendas\PlantillaTiendaController;
use Illuminate\Http\JsonResponse;

class HolaprincesaController extends PlantillaTiendaController
{
    /**
     * Extrae el precio de una página de HolaPrincesa.
     * - Prioriza atributo de datos actual: <span class="money" data-price="">19,36 €</span>.
     * - Luego JSON/texto embebido: price: '19,36'.
     * - Después visible .money EXCLUYENDO compare-at (precio tachado/antiguo).
     * - Fallback (último recurso): .price__compare-at--single (precio de comparación).
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

        // 1) <span class="money" data-price="">19,36 €</span>
        if (preg_match(
            '~<span[^>]*\bclass=["\'][^"\']*\bmoney\b[^"\']*["\'][^>]*\bdata-price\b[^>]*>\s*(?<p>\d{1,3}(?:[.\s]\d{3})*(?:[.,]\d{2}))\s*(?:€|&euro;)?\s*</span>~i',
            $html, $m1
        ) && !empty($m1['p'])) {
            if (($p = $this->normalizarImporte($m1['p'])) !== null) {
                return response()->json(['success' => true, 'precio' => $p]);
            }
        }

        // 2) JSON/texto embebido: price: '19,36'
        if (preg_match(
            "~\\bprice\\s*:\\s*['\\\"](?<p>\\d{1,3}(?:[.\\s]\\d{3})*(?:[.,]\\d{2}))['\\\"]~i",
            $html, $m2
        ) && !empty($m2['p'])) {
            if (($p = $this->normalizarImporte($m2['p'])) !== null) {
                return response()->json(['success' => true, 'precio' => $p]);
            }
        }

        // 3) Visible .money SIN clase de compare-at
        if (preg_match(
            '~<span[^>]*\bclass=["\'](?:(?!price__compare-at--single)[^"\'])*\bmoney\b(?:(?!price__compare-at--single)[^"\'])*["\'][^>]*>\s*(?<p>\d{1,3}(?:[.\s]\d{3})*(?:[.,]\d{2}))\s*(?:€|&euro;)?\s*</span>~i',
            $html, $m3
        ) && !empty($m3['p'])) {
            if (($p = $this->normalizarImporte($m3['p'])) !== null) {
                return response()->json(['success' => true, 'precio' => $p]);
            }
        }

        // 4) Fallback (precio tachado/antiguo): .price__compare-at--single
        if (preg_match(
            '~<span[^>]*\bclass=["\'][^"\']*\bprice__compare-at--single\b[^"\']*["\'][^>]*>\s*(?<p>\d{1,3}(?:[.\s]\d{3})*(?:[.,]\d{2}))\s*(?:€|&euro;)?\s*</span>~i',
            $html, $m4
        ) && !empty($m4['p'])) {
            if (($p = $this->normalizarImporte($m4['p'])) !== null) {
                return response()->json(['success' => true, 'precio' => $p]);
            }
        }

        return response()->json([
            'success' => false,
            'error'   => 'No se pudo encontrar el precio en la página de HolaPrincesa',
        ]);
    }

    /**
     * Convierte "19,36", "19.36" o "1.234,56" a float con punto decimal.
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


