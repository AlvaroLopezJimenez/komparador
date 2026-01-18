<?php

namespace App\Http\Controllers\Scraping\Tiendas;

use App\Http\Controllers\Scraping\Tiendas\PlantillaTiendaController;
use Illuminate\Http\JsonResponse;

class AtuafarmaciaController extends PlantillaTiendaController
{
    /**
     * Extrae el precio de una página de Atuafarmacia.
     * - Prioriza <meta property="og:price:amount" content="…">.
     * - Luego busca en JSON/texto embebido: price:"6,49".
     * - Después intenta HTML visible: <span class="trans-money">€6,49</span>.
     * - Último recurso: <font …>6,49 €</font>.
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

        // 1) <meta property="og:price:amount" content="6,49">
        if (preg_match(
            '~<meta[^>]*\bproperty\s*=\s*["\']og:price:amount["\'][^>]*\bcontent\s*=\s*["\'](?<p>[\d\.,]+)["\'][^>]*>~i',
            $html,
            $m1
        ) && !empty($m1['p'])) {
            $p = $this->normalizarImporte($m1['p']);
            if ($p !== null) return response()->json(['success' => true, 'precio' => $p]);
        }

        // 2) JSON/texto embebido: price:"6,49"
        if (preg_match(
            '~["\']price["\']\s*:\s*["\'](?<p>\d{1,3}(?:[.\s]\d{3})*(?:[.,]\d{2}))["\']~i',
            $html,
            $m2
        ) && !empty($m2['p'])) {
            $p = $this->normalizarImporte($m2['p']);
            if ($p !== null) return response()->json(['success' => true, 'precio' => $p]);
        }

        // 3) Visible: <span class="trans-money" translate="no">€6,49</span> (dentro o no de data-price)
        if (preg_match(
            '~<span[^>]*class=["\'][^"\']*\btrans-money\b[^"\']*["\'][^>]*>\s*(?:€|&euro;)?\s*(?<p>\d{1,3}(?:[.\s]\d{3})*(?:[.,]\d{2}))~i',
            $html,
            $m3
        ) && !empty($m3['p'])) {
            $p = $this->normalizarImporte($m3['p']);
            if ($p !== null) return response()->json(['success' => true, 'precio' => $p]);
        }

        // 4) Último recurso: <font …>6,49 €</font>
        if (preg_match(
            '~<font[^>]*>\s*(?<p>\d{1,3}(?:[.\s]\d{3})*(?:[.,]\d{2}))\s*(?:€|&euro;)?\s*</font>~i',
            $html,
            $m4
        ) && !empty($m4['p'])) {
            $p = $this->normalizarImporte($m4['p']);
            if ($p !== null) return response()->json(['success' => true, 'precio' => $p]);
        }

        return response()->json([
            'success' => false,
            'error'   => 'No se pudo encontrar el precio en la página de Atuafarmacia',
        ]);
    }

    /**
     * Convierte "6,49", "6.49" o "1.234,56" a float con punto decimal.
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


