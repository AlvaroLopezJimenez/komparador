<?php

namespace App\Http\Controllers\Scraping\Tiendas;

use Illuminate\Http\JsonResponse;

class ToysrusController extends PlantillaTiendaController
{
    /**
     * Toys“R”Us:
     * Fuentes de precio (prioridad):
     *  - data-productcart="17,99 €"  (soporta comillas simples/dobles y NBSP)
     *  - <meta itemprop="price" content="17.99"> (dentro del bloque de oferta)
     *  - <div class="swogo-price">17,99 €</div>      [fallback histórico]
     *  - <div class="cn_element_products_2_unit_price">17,99 € ...</div>  [fallback histórico]
     *  - Captura genérica de “n.nn €” como último recurso
     *
     * Sin bucles de reintento.
     * Devuelve número sin símbolo € y con punto decimal (17.99).
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

        $html = (string) $resultado['html'];
        $html = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // 1) data-productcart='17,99 €'  (comillas simples/dobles y NBSP U+00A0)
        if (preg_match(
            '~data-productcart\s*=\s*["\']\s*(?<p>\d{1,3}(?:[.,]\d{2}))\s*(?:€|&euro;)?[\s\x{00A0}]*["\']~iu',
            $html,
            $m
        ) && !empty($m['p'])) {
            $precio = $this->aNumero($m['p']);
            if ($precio !== null) {
                return response()->json(['success' => true, 'precio' => $precio]);
            }
        }

        // 2) <meta itemprop="price" content="17.99"/> (dentro del bloque Offer o en general)
        if (preg_match(
            '~<meta[^>]*\bitemprop\s*=\s*["\']price["\'][^>]*\bcontent\s*=\s*["\'](?<p>[\d\.,]+)["\'][^>]*\/?>~i',
            $html,
            $mMeta
        ) && !empty($mMeta['p'])) {
            $precio = $this->aNumero($mMeta['p']);
            if ($precio !== null) {
                return response()->json(['success' => true, 'precio' => $precio]);
            }
        }

        // 3) Fallbacks históricos por si siguieran presentes
        // 3a) <div class="swogo-price">17,99 €</div>
        if (preg_match(
            '~<div[^>]*class=["\']swogo-price["\'][^>]*>\s*(?<p>\d{1,3}(?:[.,]\d{2}))\s*(?:€|&euro;)?\s*</div>~i',
            $html,
            $m2
        ) && !empty($m2['p'])) {
            $precio = $this->aNumero($m2['p']);
            if ($precio !== null) {
                return response()->json(['success' => true, 'precio' => $precio]);
            }
        }

        // 3b) <div class="cn_element_products_2_unit_price">17,99 € ...</div>
        if (preg_match(
            '~<div[^>]*class=["\']cn_element_products_2_unit_price["\'][^>]*>\s*(?<p>\d{1,3}(?:[.,]\d{2}))\s*(?:€|&euro;)?~i',
            $html,
            $m3
        ) && !empty($m3['p'])) {
            $precio = $this->aNumero($m3['p']);
            if ($precio !== null) {
                return response()->json(['success' => true, 'precio' => $precio]);
            }
        }

        // 4) Fallback genérico por si cambian clases pero mantienen el formato
        if (preg_match('~(?<!\d)(?<p>\d{1,3}(?:[.,]\d{2}))\s*(?:€|&euro;)~', $html, $mg) && !empty($mg['p'])) {
            $precio = $this->aNumero($mg['p']);
            if ($precio !== null) {
                return response()->json(['success' => true, 'precio' => $precio]);
            }
        }

        return response()->json([
            'success' => false,
            'error'   => 'No se pudo encontrar el precio en la página de Toysrus',
        ]);
    }

    /**
     * Convierte "17,99" o "17.99" a float con punto decimal.
     */
    private function aNumero($raw): ?float
    {
        if (!is_string($raw) && !is_numeric($raw)) return null;
        $s = trim((string)$raw);

        // Si hay coma y punto, asumimos formato ES con posible punto de miles
        if (strpos($s, ',') !== false && strpos($s, '.') !== false) {
            $s = str_replace('.', '', $s);
        }
        $s = str_replace(',', '.', $s);

        if (!is_numeric($s)) return null;
        return (float)$s;
    }
}


