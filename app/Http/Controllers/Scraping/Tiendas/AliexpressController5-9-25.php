<?php

namespace App\Http\Controllers\Scraping\Tiendas;

use Illuminate\Http\JsonResponse;

class AliexpressController extends PlantillaTiendaController
{
    /**
     * Selector de modo:
     *  - true  -> usar RapidAPI (como hasta ahora).
     *  - false -> usar apiHTML (scraping) y extraer el precio visible actual.
     *
     * Puedes cambiarlo en caliente según necesites.
     */
    protected $usarRapidApi = false;

    /**
     * Obtener precio de un producto de AliExpress.
     *
     * MODO RapidAPI:
     *  - Usa la API "aliexpress-datahub" para obtener SKUs y precios.
     *  - Si $variante (skuId) viene, devuelve su promotionPrice|price.
     *  - Si no hay $variante, devuelve el mínimo entre todos los SKUs.
     *
     * MODO apiHTML (scraping):
     *  - No llama a RapidAPI. Descarga el HTML con $this->apiHTML.
     *  - Extrae el **precio actual** desde elementos tipo:
     *      <div class="price-default--currentWrap--...">
     *        <span class="price-default--current--...">36,99€</span>
     *      </div>
     *    (los sufijos cambian, por eso buscamos por los prefijos
     *     "price-default--current--" y "price-default--currentWrap--").
     *  - Ignora explícitamente el “original” (tachado): "price-default--original--".
     *  - Tolera variaciones reales del texto de precio:
     *      • 0–2 decimales: "53", "53,1", "53,10"
     *      • Separadores de miles repetidos: "1.234", "1.234.567"
     *      • Etiquetas internas dentro del span (p.ej. <bdi>)
     *      • Espacios duros/fino (NBSP \x{00A0}, NNBSP \x{202F})
     *
     * Respuesta: número sin símbolo €, con punto decimal (float).
     */
    public function obtenerPrecio($url, $variante = null, $tienda = null): JsonResponse
    {
        if ($this->usarRapidApi) {
            return $this->obtenerPrecioPorRapidApi($url, $variante);
        }

        return $this->obtenerPrecioPorHtml($url, $tienda);
    }

    /* ====================== MODO RapidAPI ====================== */

    private function obtenerPrecioPorRapidApi(string $url, $variante = null): JsonResponse
    {
        // 1) Extraer itemId de la URL
        $itemId = $this->extraerItemId($url);
        if ($itemId === null) {
            return response()->json([
                'success' => false,
                'error'   => 'No se pudo extraer el itemId de la URL de AliExpress',
            ]);
        }

        // 2) Llamada a la API de RapidAPI (deja tu key)
        $apiKey   = 'e7641b483cmshcac145d48569584p1de82ejsn452206de102d'; // <-- tu API KEY aquí
        $endpoint = "https://aliexpress-datahub.p.rapidapi.com/item_detail_2?itemId={$itemId}&currency=EUR&region=ES&locale=es_ES";

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $endpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => '',
            CURLOPT_MAXREDIRS      => 5,
            CURLOPT_TIMEOUT        => 25,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CUSTOMREQUEST  => 'GET',
            CURLOPT_HTTPHEADER     => [
                'x-rapidapi-host: aliexpress-datahub.p.rapidapi.com',
                'x-rapidapi-key: ' . $apiKey,
            ],
        ]);

        $response = curl_exec($ch);
        $err      = curl_error($ch);
        $status   = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        if ($err) {
            return response()->json([
                'success' => false,
                'error'   => 'Error en cURL: ' . $err,
            ]);
        }

        if ($status < 200 || $status >= 300) {
            return response()->json([
                'success' => false,
                'error'   => 'HTTP ' . $status . ' al llamar a la API de AliExpress',
                'raw'     => $response,
            ]);
        }

        // 3) Parsear JSON
        $data = json_decode($response, true);
        if (!is_array($data) || empty($data['result']['item']['sku']['base'])) {
            return response()->json([
                'success' => false,
                'error'   => 'Respuesta inesperada de la API (sin sku.base)',
                'raw'     => $response,
            ]);
        }

        $base = $data['result']['item']['sku']['base']; // array de SKUs
        if (!is_array($base) || empty($base)) {
            return response()->json([
                'success' => false,
                'error'   => 'Sin SKUs en la respuesta',
            ]);
        }

        // 4) Selección
        $precio = null;

        if ($variante !== null && $variante !== '') {
            $skuIdWanted = (string) $variante;
            foreach ($base as $sku) {
                if (!is_array($sku)) continue;
                if (!isset($sku['skuId'])) continue;

                if ((string)$sku['skuId'] === $skuIdWanted) {
                    $raw    = $sku['promotionPrice'] ?? $sku['price'] ?? null;
                    $precio = $this->aNumero($raw);
                    break;
                }
            }

            if ($precio === null) {
                return response()->json([
                    'success' => false,
                    'error'   => 'No se encontró el skuId indicado en los SKUs base',
                ]);
            }
        } else {
            $candidatos = [];
            foreach ($base as $sku) {
                if (!is_array($sku)) continue;
                $raw = $sku['promotionPrice'] ?? $sku['price'] ?? null;
                $n   = $this->aNumero($raw);
                if ($n !== null) $candidatos[] = $n;
            }

            if (empty($candidatos)) {
                return response()->json([
                    'success' => false,
                    'error'   => 'No hay precios en los SKUs base',
                ]);
            }

            sort($candidatos, SORT_NUMERIC);
            $precio = (float) $candidatos[0];
        }

        return response()->json([
            'success' => true,
            'precio'  => $precio,
        ]);
    }

    /* ====================== MODO apiHTML (scraping) ====================== */

    private function obtenerPrecioPorHtml(string $url, $tienda = null): JsonResponse
    {
        $apiTienda = $tienda ? $tienda->api : null;
        $resultado = $this->apiHTML->obtenerHTML($url, null, $apiTienda);

        if (!is_array($resultado) || empty($resultado['success']) || empty($resultado['html'])) {
            return response()->json([
                'success' => false,
                'error'   => is_array($resultado) ? ($resultado['error'] ?? 'No se pudo obtener el HTML') : 'Respuesta inválida de la API',
            ]);
        }

        // Normalización de HTML y espacios no separables
        $html = html_entity_decode((string)$resultado['html'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $html = preg_replace('/\x{00A0}|\x{202F}/u', ' ', $html); // NBSP y NNBSP -> espacio normal

        // 1) <span class="price-default--current--XXXX"> ... 53,1€ ... </span>
        if (preg_match(
            '~<span[^>]*class=["\'][^"\']*\bprice-default--current--[A-Za-z0-9_-]+\b[^"\']*["\'][^>]*>.*?(?<p>\d{1,3}(?:[.\s]\d{3})*(?:[.,]\d{1,2})?)\s*(?:€|&euro;)?~us',
            $html,
            $m1
        ) && !empty($m1['p'])) {
            $p = $this->aNumero($m1['p']);
            if ($p !== null) {
                return response()->json(['success' => true, 'precio' => $p]);
            }
        }

        // 2) <div class="price-default--currentWrap--XXXX"> ... <span class="price-default--current--XXXX">53,1€</span>
        if (preg_match(
            '~<div[^>]*class=["\'][^"\']*\bprice-default--currentWrap--[A-Za-z0-9_-]+\b[^"\']*["\'][^>]*>.*?<span[^>]*class=["\'][^"\']*\bprice-default--current--[A-Za-z0-9_-]+\b[^"\']*["\'][^>]*>.*?(?<p>\d{1,3}(?:[.\s]\d{3})*(?:[.,]\d{1,2})?)\s*(?:€|&euro;)?~us',
            $html,
            $m2
        ) && !empty($m2['p'])) {
            $p = $this->aNumero($m2['p']);
            if ($p !== null) {
                return response()->json(['success' => true, 'precio' => $p]);
            }
        }

        // 3) Fallback genérico evitando "original"
        if (preg_match(
            '~<(?:span|div)[^>]*class=["\'][^"\']*\bprice-default--current\b(?![^"\']*original)[^"\']*["\'][^>]*>.*?(?<p>\d{1,3}(?:[.\s]\d{3})*(?:[.,]\d{1,2})?)\s*(?:€|&euro;)?~ius',
            $html,
            $m3
        ) && !empty($m3['p'])) {
            $p = $this->aNumero($m3['p']);
            if ($p !== null) {
                return response()->json(['success' => true, 'precio' => $p]);
            }
        }

        return response()->json([
            'success' => false,
            'error'   => 'No se pudo encontrar el precio actual en el HTML de AliExpress',
        ]);
    }

    /* ====================== Helpers comunes ====================== */

    /**
     * Extrae el itemId de URLs tipo:
     *  - https://es.aliexpress.com/item/1005005244562338.html
     *  - https://www.aliexpress.com/item/1005005244562338.html?spm=...
     *  - También acepta que le pasen directamente el ID (sólo dígitos).
     */
    private function extraerItemId(string $url): ?string
    {
        $url = trim($url);

        if (preg_match('/^\d{6,}$/', $url)) {
            return $url;
        }

        if (preg_match('~/(?:item/)?(\d{6,})\.html~i', $url, $m)) {
            return $m[1];
        }

        if (preg_match('/(\d{6,})/', $url, $m2)) {
            return $m2[1];
        }

        return null;
    }

    /**
     * Convierte "36,99", "36.99" o "53,1" a float con punto decimal.
     */
    private function aNumero($raw): ?float
    {
        if ($raw === null) return null;
        if (!is_string($raw) && !is_numeric($raw)) return null;

        $s = trim((string)$raw);

        // Si hay coma y punto, asumimos formato ES con punto de miles
        if (strpos($s, ',') !== false && strpos($s, '.') !== false) {
            $s = str_replace('.', '', $s);
        }
        $s = str_replace(',', '.', $s);

        if (!is_numeric($s)) return null;
        return (float)$s;
        }
}
