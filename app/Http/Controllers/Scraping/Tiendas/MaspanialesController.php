<?php

namespace App\Http\Controllers\Scraping\Tiendas;

use Illuminate\Http\JsonResponse;

class MaspanialesController extends PlantillaTiendaController
{
    /**
     * Comportamiento:
     * - SIN reintentos.
     * - variante vacío/null  -> precio canónico del PDP.
     * - variante "0"/"1"/"2" -> precio por índice desde elementos HTML con clase "combination-price".
     * - Siempre número sin símbolo € y con punto decimal.
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

        // 1) Si variante es índice "0|1|2|..."
        $varianteIdx = $this->parseIdx($variante);
        if ($varianteIdx !== null) {
            $preciosPorIndice = $this->extraerPreciosDesdeDebug($html);

            if (!empty($preciosPorIndice) && isset($preciosPorIndice[$varianteIdx])) {
                return response()->json([
                    'success' => true,
                    'precio'  => $preciosPorIndice[$varianteIdx],
                ]);
            }

            // Si no existe ese índice, devolvemos error claro
            return response()->json([
                'success' => false,
                'error'   => 'No hay precio para el índice solicitado',
            ]);
        }

        // 2) Variante vacía u otro texto -> precio canónico del PDP
        $canonico = $this->extraerPrecioCanonico($html);
        if ($canonico !== null) {
            return response()->json([
                'success' => true,
                'precio'  => $canonico,
            ]);
        }

        return response()->json([
            'success' => false,
            'error'   => 'No se pudo determinar el precio',
        ]);
    }

    /* ====================== Helpers ====================== */

    /**
     * Si $v es "0", "1", "2"... devuelve int, si no, null.
     */
    private function parseIdx($v): ?int
    {
        if ($v === 0 || $v === '0') return 0;
        if (is_string($v) && preg_match('/^\d+$/', $v)) return (int) $v;
        if (is_int($v) && $v >= 0) return $v;
        return null;
    }

    /**
d     * Extrae precios desde elementos HTML con clase "combination-price".
     * Estructura esperada:
     *   <li class="input-container">
     *     <span class="combination-price fw-bold">15,38 €</span>
     *   </li>
     * Devuelve array indexado 0..N con los precios en el orden de aparición.
     */
    private function extraerPreciosDesdeDebug(string $html): array
    {
        $precios = [];

        // Buscar todos los elementos con clase "combination-price"
        // Puede tener otras clases como "fw-bold", así que buscamos que contenga "combination-price"
        if (!preg_match_all('~<span[^>]*class=["\'][^"\']*combination-price[^"\']*["\'][^>]*>(.*?)</span>~is', $html, $matches)) {
            return [];
        }

        foreach ($matches[1] as $precioRaw) {
            // Limpiar el contenido: puede tener espacios, &nbsp;, símbolo €, etc.
            $precioRaw = trim($precioRaw);
            // Eliminar &nbsp; y otros espacios
            $precioRaw = preg_replace('~&nbsp;|\s+~', ' ', $precioRaw);
            $precioRaw = trim($precioRaw);
            
            // Extraer el número (puede ser "15,38 €" o "12,04 €")
            if (preg_match('~(\d{1,3}(?:[.,]\d{3})*[.,]\d{2}|\d+[.,]\d{2})~', $precioRaw, $m)) {
                $precio = $this->aNumero($m[1]);
                if ($precio !== null) {
                    $precios[] = (float)$precio;
                }
            }
        }

        return $precios;
    }

/**
 * Precio "canónico" del PDP (vigente):
 *  1) <meta property="product:price:amount" content="xx.xx">
 *  2) <span itemprop="price" class="product-price" content="xx.xx"> (o texto)
 *  2b) <span class="product-price" ... content="xx.xx"> (o texto, con € / &nbsp;)
 *  3) JSON-LD Product.offers.price
 *  4) Formato JSON: "value":{"label":"value","value":33.17}
 * Ignora precios tachados (regular-price).
 */
private function extraerPrecioCanonico(string $html): ?float
{
    // 1) Meta product:price:amount
    if (preg_match('~<meta\s+property=["\']product:price:amount["\']\s+content=["\'](?<p>[\d.,]+)["\']~i', $html, $m)) {
        $v = $this->aNumero($m['p']);
        if ($v !== null) return $v;
    }

    // 2) itemprop="price" (atributo content o texto)
    if (preg_match('~itemprop=["\']price["\'][^>]*content=["\'](?<p>[\d.,]+)["\']~i', $html, $m2)) {
        $v = $this->aNumero($m2['p']);
        if ($v !== null) return $v;
    }
    if (preg_match('~itemprop=["\']price["\'][^>]*>\s*(?<p>\d{1,3}(?:[.,]\d{2}))~i', $html, $m3)) {
        $v = $this->aNumero($m3['p']);
        if ($v !== null) return $v;
    }

    // 2b) class="product-price" (con o sin itemprop), por atributo content o texto visible
    //    - Atributo content="33.17"
    if (preg_match('~class=["\'][^"\']*product-price[^"\']*["\'][^>]*content=["\'](?<p>[\d.,]+)["\']~i', $html, $m4)) {
        $v = $this->aNumero($m4['p']);
        if ($v !== null) return $v;
    }
    //    - Texto interno "33,17&nbsp;€" (ignorando €/nbsp y separadores)
    if (preg_match('~class=["\'][^"\']*product-price[^"\']*["\'][^>]*>\s*(?:[^<\d]+)?(?<p>\d{1,3}(?:[.,]\d{3})*[.,]\d{2})~is', $html, $m5)) {
        $v = $this->aNumero($m5['p']);
        if ($v !== null) return $v;
    }

    // 3) JSON-LD offers.price
    if (preg_match_all('~<script[^>]+type=["\']application/ld\+json["\'][^>]*>(?<json>.*?)</script>~is', $html, $blocks)) {
        foreach ($blocks['json'] as $raw) {
            if (stripos($raw, 'OfferShippingDetails') !== false) continue;

            $decoded = json_decode(trim($raw), true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $items = [];
                if (isset($decoded['@graph']) && is_array($decoded['@graph'])) {
                    $items = array_merge($items, $decoded['@graph']);
                }
                if (isset($decoded['@type'])) {
                    $items[] = $decoded;
                } elseif (is_array($decoded)) {
                    $items = array_merge($items, $decoded);
                }

                foreach ($items as $it) {
                    if (!is_array($it)) continue;
                    if (isset($it['@type']) && stripos((string)$it['@type'], 'Offer') !== false && isset($it['price'])) {
                        $v = $this->aNumero((string)$it['price']);
                        if ($v !== null) return $v;
                    }
                    if (isset($it['@type']) && stripos((string)$it['@type'], 'Product') !== false && isset($it['offers'])) {
                        $offers = $it['offers'];
                        $offers = (isset($offers['@type']) || isset($offers['price'])) ? [$offers] : (is_array($offers) ? $offers : []);
                        foreach ($offers as $of) {
                            if (isset($of['price'])) {
                                $v = $this->aNumero((string)$of['price']);
                                if ($v !== null) return $v;
                            }
                        }
                    }
                }
            } else {
                // Fallback regex simple dentro del bloque
                if (preg_match('~"price"\s*:\s*"?(?<p>\d+[.,]\d{2})"?\b~i', $raw, $mm)) {
                    $v = $this->aNumero($mm['p']);
                    if ($v !== null) return $v;
                }
            }
        }
    }

    // 4) Buscar formato JSON con "value":{"label":"value","value":33.17}
    if (preg_match_all('~"value"\s*:\s*\{[^}]*"value"\s*:\s*([0-9.]+)~i', $html, $matches)) {
        foreach ($matches[1] as $precio) {
            $v = $this->aNumero($precio);
            if ($v !== null) return $v;
        }
    }

    return null;
}


    /**
     * Convierte "16,92" o "16.92" a float (punto decimal).
     */
    private function aNumero($raw): ?float
    {
        if (!is_string($raw) && !is_numeric($raw)) return null;
        $s = trim((string)$raw);

        if (strpos($s, ',') !== false && strpos($s, '.') !== false) {
            // Formato europeo con miles en punto
            $s = str_replace('.', '', $s);
        }
        $s = str_replace(',', '.', $s);

        if (!is_numeric($s)) return null;
        return (float)$s;
    }
}


