<?php

namespace App\Http\Controllers\Scraping\Tiendas;

use Illuminate\Http\JsonResponse;

class AliexpressController extends PlantillaTiendaController
{
    /**
     * ÚNICO modo: AliExpress Affiliate API (sin OAuth) y SOLO EUR.
     */
    public function obtenerPrecio($url, $variante = null, $tienda = null): JsonResponse
    {
        try {
            /** @var \App\Http\Controllers\Scraping\PeticionApiHTMLController $peti */
            $peti = app(\App\Http\Controllers\Scraping\PeticionApiHTMLController::class);

            // Pedimos SOLO el JSON crudo del proveedor 'aliexpress_open'
            $resultado = $peti->obtenerHTML($url, 'aliexpress_open', $tienda ? $tienda->api : 'aliexpressOpen');

            if (!is_array($resultado) || empty($resultado['success']) || empty($resultado['raw']) || !is_array($resultado['raw'])) {
                return response()->json([
                    'success' => false,
                    'error'   => is_array($resultado) ? ($resultado['error'] ?? 'Fallo al llamar a AliExpress Open Platform') : 'Respuesta inválida de la API',
                ]);
            }

            $raw      = $resultado['raw'];
            $products = $this->aliAffiliateExtractProducts($raw);
            if (empty($products)) {
                return response()->json(['success' => false, 'error' => 'AliOpen: no se encontraron productos en la respuesta']);
            }

            // ---- Construimos mapa de SKUs EUR ----
            [$skuMap, $idsDisponibles] = $this->buildSkuMapEUR($products);

            if (empty($skuMap)) {
                return response()->json(['success' => false, 'error' => 'AliOpen: la API no contiene precios en EUR']);
            }

            // ---- Variante concreta ----
            if ($variante !== null && $variante !== '') {
                $wanted = $this->normalizeId($variante);

                // Coincidencia exacta por sku_id
                if (isset($skuMap[$wanted])) {
                    return response()->json(['success' => true, 'precio' => (float)$skuMap[$wanted]]);
                }

                // Si no hay match pero solo hay 1 SKU en EUR, devolvemos ese (típico en respuestas sin sku_list)
                if (count($skuMap) === 1) {
                    $onlyPrice = (float)array_values($skuMap)[0];
                    return response()->json(['success' => true, 'precio' => $onlyPrice]);
                }

                // Error informativo con los ids EUR encontrados
                $lista = implode(', ', array_slice($idsDisponibles, 0, 10));
                return response()->json([
                    'success' => false,
                    'error'   => 'No se encontró el skuId indicado en la API (en EUR). Disponibles: ' . ($lista ?: '—'),
                ]);
            }

            // ---- Sin variante: mínimo precio EUR ----
            $min = min(array_values($skuMap));
            return response()->json(['success' => true, 'precio' => (float)$min]);

        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'error' => 'Excepción en AliExpressController: ' . $e->getMessage()]);
        }
    }

    /* ====================== Core helpers ====================== */

    /**
     * Crea un mapa id -> precio (solo EUR) a partir de la lista de productos de Affiliate.
     * - Usa sku_list (si existe) con moneda EUR.
     * - Siempre añade también el "SKU del producto" (sku_id o product_id) con su precio EUR preferente.
     * - Si hay duplicados de id, se queda con el precio mínimo > 0.
     *
     * @return array [array $skuMap, array $idsDisponibles]
     */
    private function buildSkuMapEUR(array $products): array
    {
        $map = [];           // id => price
        $idsDisponibles = []; // ids en EUR vistos (para debug de errores)

        foreach ($products as $p) {
            if (!is_array($p)) continue;

            // 1) sku_list detallado (si la API lo trajo)
            if (!empty($p['sku_list']) && is_array($p['sku_list'])) {
                foreach ($p['sku_list'] as $sku) {
                    $id = $this->normalizeId($sku['sku_id'] ?? $sku['skuId'] ?? $sku['id'] ?? null);
                    $pr = $this->getSkuPriceEUR($sku);
                    if ($id !== '' && $pr !== null && $pr > 0) {
                        $idsDisponibles[] = $id;
                        if (!isset($map[$id]) || $pr < $map[$id]) {
                            $map[$id] = (float)$pr;
                        }
                    }
                }
            }

            // 2) SKU del propio producto (frecuente cuando no hay sku_list)
            $productSkuId = $this->normalizeId($p['sku_id'] ?? $p['skuId'] ?? $p['skuIdStr'] ?? $p['product_id'] ?? null);
            $productPrice = $this->aliPreferredPriceFromProductEUR($p);
            if ($productSkuId !== '' && $productPrice !== null && $productPrice > 0) {
                $idsDisponibles[] = $productSkuId;
                if (!isset($map[$productSkuId]) || $productPrice < $map[$productSkuId]) {
                    $map[$productSkuId] = (float)$productPrice;
                }
            }
        }

        // Deduplicar idsDisponibles en orden de aparición
        $idsDisponibles = array_values(array_unique($idsDisponibles));

        return [$map, $idsDisponibles];
    }

    /**
     * Extrae el array de "product" desde la forma común del JSON de Affiliate.
     * Forma típica:
     * aliexpress_affiliate_productdetail_get_response.resp_result.result.products.product
     */
    private function aliAffiliateExtractProducts(array $raw): array
    {
        $p = $raw['aliexpress_affiliate_productdetail_get_response']['resp_result']['result']['products']['product'] ?? null;
        if ($p !== null) {
            if (is_array($p) && $this->esObjeto($p)) return [$p];
            return (array)$p;
        }

        // Otras rutas defensivas
        $p = $raw['resp_result']['result']['products']['product'] ?? null;
        if ($p !== null) {
            if (is_array($p) && $this->esObjeto($p)) return [$p];
            return (array)$p;
        }

        return [];
    }

    /**
     * Precio preferente SOLO en EUR, en este orden:
     * 1) sale_price (EUR)
     * 2) app_sale_price (EUR)
     * 3) original_price (EUR)
     * *Nunca* usamos target_* porque suelen ser USD.
     */
    private function aliPreferredPriceFromProductEUR(array $p): ?float
    {
        $try = [
            ['sale_price',     'sale_price_currency'],
            ['app_sale_price', 'app_sale_price_currency'],
            ['original_price', 'original_price_currency'],
        ];

        foreach ($try as [$amountKey, $currencyKey]) {
            $n = $this->getPriceIfEUR($p, $amountKey, $currencyKey);
            if ($n !== null && $n > 0) return $n;
        }
        return null;
    }

    /**
     * Precio EUR desde un SKU (si la API trae sku_list con moneda por SKU).
     * Intenta 'sku_price' (EUR) y luego 'promotion_price' (EUR) y luego 'price' (EUR).
     */
    private function getSkuPriceEUR(array $sku): ?float
    {
        $pairs = [
            ['sku_price',        'sku_price_currency'],
            ['promotion_price',  'promotion_price_currency'],
            ['price',            'price_currency'],
        ];
        foreach ($pairs as [$kAmt, $kCur]) {
            $cur = strtoupper((string)($sku[$kCur] ?? ''));
            if ($cur === 'EUR') {
                $n = $this->aNumero($sku[$kAmt] ?? null);
                if ($n !== null && $n > 0) return $n;
            }
        }
        return null;
    }

    private function getPriceIfEUR(array $p, string $amountKey, string $currencyKey): ?float
    {
        $curr = strtoupper((string)($p[$currencyKey] ?? ''));
        if ($curr !== 'EUR') return null;
        return $this->aNumero($p[$amountKey] ?? null);
    }

    /* ====================== Utilidades básicas ====================== */

    private function esObjeto(array $a): bool
    {
        return count(array_filter(array_keys($a), 'is_string')) > 0;
    }

    /**
     * Normaliza IDs a string numérica (evita notación científica / separadores).
     * Requiere que el JSON se haya decodificado con JSON_BIGINT_AS_STRING en PeticionApi.
     */
    private function normalizeId($v): string
    {
        if ($v === null) return '';
        $s = trim((string)$v);
        if ($s === '') return '';
        $digits = preg_replace('/\D+/', '', $s);
        return $digits ?? '';
    }

    /** Convierte "1.234,56" / "1234,56" / "1234.56" -> float con punto decimal. */
    private function aNumero($raw): ?float
    {
        if ($raw === null) return null;
        if (!is_string($raw) && !is_numeric($raw)) return null;

        $s = trim((string)$raw);
        if ($s === '') return null;

        // coma + punto => quitamos puntos (miles) y usamos coma como decimal
        if (strpos($s, ',') !== false && strpos($s, '.') !== false) {
            $s = str_replace('.', '', $s);
        }
        $s = str_replace(',', '.', $s);

        if (!is_numeric($s)) return null;
        return (float)$s;
    }
}
