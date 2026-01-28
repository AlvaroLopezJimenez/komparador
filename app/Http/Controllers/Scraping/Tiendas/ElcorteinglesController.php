<?php

namespace App\Http\Controllers\Scraping\Tiendas;

use App\Http\Controllers\Scraping\Tiendas\PlantillaTiendaController;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\OfertaProducto;

class ElcorteinglesController extends PlantillaTiendaController
{
    /**
     * Extrae el precio de una página de El Corte Inglés (Supermercado).
     * - Sin bucle de reintentos (comentado).
     * - Prioriza dataLayer / JSON embebido ("price.final").
     * - Luego JSON-LD (@type=Offer -> price), ignorando OfferShippingDetails.
     * - Fallback visible en HTML: incluye <span class="price-sale">…</span> y food-prices__price.
     * - Fallback adicional: microdatos en HTML (itemprop="lowPrice" o "price").
     *
     * NOTA: No se usa "shippingRate.value" porque es coste de envío, no precio del producto.
     */
    public function obtenerPrecio($url, $variante = null, $tienda = null, $oferta = null): JsonResponse
    {
        $html = null;
        $ultimoError = null;

        // Reintentos: hasta 3 llamadas a la API seguidas aun no hay controlador que si lo consigue que pare el bucle
        //POR ESO LO TENGO COMENTADO
        // for ($i = 1; $i <= 3; $i++) {
            $resultado = $this->apiHTML->obtenerHTML($url, null, $tienda ? $tienda->api : null);

            if (is_array($resultado) && !empty($resultado['success']) && !empty($resultado['html'])) {
                $html = (string)$resultado['html'];
                // break;
            }

            $ultimoError = is_array($resultado) ? ($resultado['error'] ?? 'Error desconocido') : 'Respuesta inválida de la API';
            usleep(700000); // ~0.7s
        // }

        if ($html === null || $html === '') {
            return response()->json([
                'success' => false,
                'error'   => 'No se pudo obtener el HTML de El Corte Inglés' . ($ultimoError ? (': ' . $ultimoError) : ''),
            ]);
        }

        $html = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // DEBUG: Log para verificar si tenemos oferta
        Log::info('ElcorteinglesController - Oferta recibida:', [
            'oferta_id' => $oferta ? $oferta->id : 'null',
            'oferta_tipo' => $oferta ? get_class($oferta) : 'null',
            'url' => $url
        ]);

        // DETECCIÓN DE AGOTADO
        $this->detectarAgotado($html, $oferta);

        // ---- 1) NUEVO: "price":{"original":"60.75","final":"42.39"} ----
        $precio = $this->extraerPrecioDeOriginalFinal($html);
        if ($precio !== null) {
            // Detectar ofertas especiales (solo detectar, no modificar precios)
            if ($oferta) {
                $this->detectarYGuardarDescuentos($html, $oferta);
            }
            return response()->json(['success' => true, 'precio' => $precio]);
        }

        // ---- 2) dataLayer / JSON embebido: "price":{"final":"31.55"} o "price":{"final":31.55} ----
        $precio = $this->extraerPrecioDePriceFinal($html);
        if ($precio !== null) {
            // Detectar ofertas especiales (solo detectar, no modificar precios)
            if ($oferta) {
                $this->detectarYGuardarDescuentos($html, $oferta);
            }
            return response()->json(['success' => true, 'precio' => $precio]);
        }

        // ---- 3) JSON-LD <script type="application/ld+json"> ... {"@type":"Offer","price":"31.55"} ... ----
        $precio = $this->extraerPrecioDeJsonLd($html);
        if ($precio !== null) {
            // Detectar ofertas especiales (solo detectar, no modificar precios)
            if ($oferta) {
                $this->detectarYGuardarDescuentos($html, $oferta);
            }
            return response()->json(['success' => true, 'precio' => $precio]);
        }

        // ---- 4) Fallback visible en HTML (supermercado) ----
        $precio = $this->extraerPrecioDeHtmlVisible($html);
        if ($precio !== null) {
            // Detectar ofertas especiales (solo detectar, no modificar precios)
            if ($oferta) {
                $this->detectarYGuardarDescuentos($html, $oferta);
            }
            return response()->json(['success' => true, 'precio' => $precio]);
        }

        // ---- 5) Microdatos (schema.org) en HTML: itemprop="lowPrice" o "price" ----
        $precio = $this->extraerPrecioDeMicrodata($html);
        if ($precio !== null) {
            // Detectar ofertas especiales (solo detectar, no modificar precios)
            if ($oferta) {
                $this->detectarYGuardarDescuentos($html, $oferta);
            }
            return response()->json(['success' => true, 'precio' => $precio]);
        }


        return response()->json([
            'success' => false,
            'error'   => 'No se pudo encontrar el precio en la página de El Corte Inglés',
        ]);
    }

    /**
     * Busca "price":{"original":"60.75","final":"42.39"} en blobs JSON embebidos.
     * Extrae el valor de "final" que es el precio correcto.
     */
    private function extraerPrecioDeOriginalFinal(string $html): ?float
    {
        // Buscar el patrón específico: "price":{"original":"XX.XX","final":"YY.YY"}
        $regexes = [
            // Con comillas en ambos valores
            '~"price"\s*:\s*\{\s*"original"\s*:\s*"[^"]*"\s*,\s*"final"\s*:\s*"(?<p>\d+(?:\.\d{2})?)"~i',
            // Sin comillas en los valores numéricos
            '~"price"\s*:\s*\{\s*"original"\s*:\s*[^,]*\s*,\s*"final"\s*:\s*(?<p>\d+(?:\.\d{2})?)~i',
        ];

        foreach ($regexes as $rx) {
            if (preg_match($rx, $html, $m) && !empty($m['p'])) {
                $p = $this->normalizarImporte($m['p']);
                if ($p !== null) return $p;
            }
        }

        return null;
    }

    /**
     * Busca "price.final" en blobs JSON embebidos (dataLayer, window.__STATE__, etc.)
     * Coincide tanto string como numérico y evita confundir con shipping.
     */
    private function extraerPrecioDePriceFinal(string $html): ?float
    {
        // Ejemplos:
        // "price":{"final":"31.55"}
        // "price":{"original":53.25,"final":53.25,"currency":"EUR"}
        $regexes = [
            // <span class="price-sale">4,90 €</span>
            '~<span[^>]*class=["\'][^"\']*\bprice-sale\b[^"\']*["\'][^>]*>\s*(?<p>\d{1,3}(?:[.\s]\d{3})*(?:[.,]\d{2}))\s*(?:€|&euro;)?\s*</span>~si',
            '~"price"\s*:\s*\{\s*"[^"]*final[^"]*"\s*:\s*"(?<p>\d+(?:\.\d{2})?)"~i',
            '~"price"\s*:\s*\{\s*"[^"]*final[^"]*"\s*:\s*(?<p>\d+(?:\.\d{2})?)\b~i',
        ];

        foreach ($regexes as $rx) {
            if (preg_match($rx, $html, $m) && !empty($m['p'])) {
                $p = $this->normalizarImporte($m['p']);
                if ($p !== null) return $p;
            }
        }

        return null;
    }

    /**
     * Busca <script type="application/ld+json"> y extrae el primer @type=Offer con "price".
     * Ignora objetos con "@type":"OfferShippingDetails".
     * Soporta JSON único u arrays de objetos.
     */
    private function extraerPrecioDeJsonLd(string $html): ?float
    {
        // Extraer cada bloque JSON-LD
        if (!preg_match_all('~<script[^>]+type=["\']application/ld\+json["\'][^>]*>(?<json>.*?)</script>~is', $html, $blocks)) {
            // Intento por regex directo cuando el script no está marcado correctamente
            return $this->extraerPrecioDeJsonLdPorRegex($html);
        }

        foreach ($blocks['json'] as $raw) {
            $json = trim($raw);
            if ($json === '') continue;

            // A veces incluyen comentarios o caracteres BOM; limpiamos lo básico
            $json = preg_replace('/\/\/.*$/m', '', $json);       // quitar // comentarios
            $json = preg_replace('/\/\*.*?\*\//s', '', $json);   // quitar /* ... */ comentarios

            // Intentar decodificar JSON
            $decoded = json_decode($json, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                // Si falla, último intento: regex dentro del bloque para Offer.price
                $p = $this->extraerPrecioDeJsonLdPorRegex($json);
                if ($p !== null) return $p;
                continue;
            }

            // Normalizar a lista de objetos
            $items = is_array($decoded) && isset($decoded['@type'])
                ? [$decoded]
                : (is_array($decoded) ? $decoded : []);

            // JSON-LD puede ser { "@graph": [ ... ] }
            if (isset($decoded['@graph']) && is_array($decoded['@graph'])) {
                $items = array_merge($items, $decoded['@graph']);
            }

            foreach ($items as $item) {
                if (!is_array($item)) continue;

                // Evitar ShippingDetails
                if (isset($item['@type']) && is_string($item['@type']) && stripos($item['@type'], 'OfferShippingDetails') !== false) {
                    continue;
                }

                // Si el item es un Product, puede traer "offers" -> Offer|Offer[]
                if (isset($item['@type']) && is_string($item['@type']) && stripos($item['@type'], 'Product') !== false) {
                    if (isset($item['offers'])) {
                        $offers = is_array($item['offers']) && isset($item['offers']['@type'])
                            ? [$item['offers']]
                            : (is_array($item['offers']) ? $item['offers'] : []);
                        foreach ($offers as $offer) {
                            if (!is_array($offer)) continue;
                            if (isset($offer['@type']) && stripos((string)$offer['@type'], 'Offer') === false) continue;
                            if (isset($offer['price'])) {
                                $p = $this->normalizarImporte((string)$offer['price']);
                                if ($p !== null) return $p;
                            }
                        }
                    }
                }

                // Directamente un Offer
                if (isset($item['@type']) && is_string($item['@type']) && stripos($item['@type'], 'Offer') !== false) {
                    if (isset($item['price'])) {
                        $p = $this->normalizarImporte((string)$item['price']);
                        if ($p !== null) return $p;
                    }
                }
            }
        }

        return null;
    }

    /**
     * Búsqueda por regex para bloques con "@type":"Offer" y "price":"31.55",
     * evitando "OfferShippingDetails".
     */
    private function extraerPrecioDeJsonLdPorRegex(string $text): ?float
    {
        // Buscar bloques que contengan @type":"Offer" (no OfferShippingDetails) y luego "price": "xx.xx" o xx.xx
        if (preg_match_all('~\{[^{}]*"@type"\s*:\s*"Offer"(?!ShippingDetails)[^{}]*\}~is', $text, $blocks)) {
            foreach ($blocks[0] as $block) {
                if (preg_match('~"price"\s*:\s*"?(?<p>\d+(?:\.\d{2})?)"?~i', $block, $m) && !empty($m['p'])) {
                    $p = $this->normalizarImporte($m['p']);
                    if ($p !== null) return $p;
                }
            }
        }
        return null;
    }

    /**
     * Fallback: precio visible en el HTML del súper.
     */
    private function extraerPrecioDeHtmlVisible(string $html): ?float
    {
        $regexes = [
            // <div class="food-prices__price">31,55 €</div>
            '~<div[^>]*class=["\']food-prices__price["\'][^>]*>\s*(?<p>\d{1,3}(?:[.\s]\d{3})*(?:[.,]\d{2})?)\s*(?:€|&euro;)?\s*</div>~si',
            // <span class="food-prices__price">31,55 €</span>
            '~<span[^>]*class=["\']food-prices__price["\'][^>]*>\s*(?<p>\d{1,3}(?:[.\s]\d{3})*(?:[.,]\d{2})?)\s*(?:€|&euro;)?\s*</span>~si',
        ];

        foreach ($regexes as $rx) {
            if (preg_match($rx, $html, $m) && !empty($m['p'])) {
                $p = $this->normalizarImporte($m['p']);
                if ($p !== null) return $p;
            }
        }
        return null;
    }

    /**
     * Convierte una cadena de precio a float (sin símbolo €).
     * Acepta "31.55", "31,55", "1.234,56", etc. Devuelve null si no es interpretable.
     */
    private function normalizarImporte(string $importe): ?float
    {
        // Mantener solo dígitos, coma o punto
        $s = preg_replace('/[^\d\,\.]/u', '', $importe);
        if ($s === null || $s === '') {
            return null;
        }

        $tieneComa  = strpos($s, ',') !== false;
        $tienePunto = strpos($s, '.') !== false;

        if ($tieneComa && $tienePunto) {
            // Determinar último separador como decimal
            $lastComma = strrpos($s, ',');
            $lastDot   = strrpos($s, '.');

            if ($lastComma !== false && ($lastDot === false || $lastComma > $lastDot)) {
                // Decimal con coma -> quitar puntos (miles) y cambiar coma por punto
                $s = str_replace('.', '', $s);
                $s = str_replace(',', '.', $s);
            } else {
                // Decimal con punto -> quitar comas (miles)
                $s = str_replace(',', '', $s);
            }
        } elseif ($tieneComa) {
            // Solo coma -> usar como decimal
            $s = str_replace(',', '.', $s);
        } else {
            // Solo dígitos o ya con punto decimal
        }

        if (!preg_match('/^\d+(\.\d+)?$/', $s)) {
            return null;
        }

        return (float)$s;
    }

    /**
 * Extrae precio desde microdatos en el HTML:
 * - <span class="hidden" itemprop="lowPrice">45.04</span>
 * - <meta itemprop="lowPrice" content="45.04">
 * - <span itemprop="price">45,04 €</span>  (por si no hubiera lowPrice)
 *
 * Prioriza lowPrice (AggregateOffer) y luego price.
 */
private function extraerPrecioDeMicrodata(string $html): ?float
{
    // 4.1) lowPrice en <meta content="...">
    if (preg_match(
        '~<meta[^>]*\bitemprop\s*=\s*["\']lowPrice["\'][^>]*\bcontent\s*=\s*["\'](?<p>[\d\.\,]+)["\'][^>]*>~i',
        $html,
        $m1
    ) && !empty($m1['p'])) {
        $p = $this->normalizarImporte($m1['p']);
        if ($p !== null) return $p;
    }

    // 4.2) lowPrice en contenido de <span> / <div> / <meta (sin content) por si acaso>
    if (preg_match(
        '~<(?:span|div|meta)[^>]*\bitemprop\s*=\s*["\']lowPrice["\'][^>]*>(?<p>[\d\.\,]+)\s*(?:€|&euro;)?\s*</(?:span|div)>~i',
        $html,
        $m2
    ) && !empty($m2['p'])) {
        $p = $this->normalizarImporte($m2['p']);
        if ($p !== null) return $p;
    }

    // 4.3) price en <meta content="...">
    if (preg_match(
        '~<meta[^>]*\bitemprop\s*=\s*["\']price["\'][^>]*\bcontent\s*=\s*["\'](?<p>[\d\.\,]+)["\'][^>]*>~i',
        $html,
        $m3
    ) && !empty($m3['p'])) {
        $p = $this->normalizarImporte($m3['p']);
        if ($p !== null) return $p;
    }

    // 4.4) price en contenido de <span>/<div>
    if (preg_match(
        '~<(?:span|div)[^>]*\bitemprop\s*=\s*["\']price["\'][^>]*>\s*(?<p>\d{1,3}(?:[.\s]\d{3})*(?:[.,]\d{2})?)\s*(?:€|&euro;)?\s*</(?:span|div)>~i',
        $html,
        $m4
    ) && !empty($m4['p'])) {
        $p = $this->normalizarImporte($m4['p']);
        if ($p !== null) return $p;
    }

    return null;
}

    /* ===================== Detección de Agotado ===================== */

    private function detectarAgotado(string $html, $oferta): void
    {
        if (!$oferta || !($oferta instanceof OfertaProducto)) {
            return;
        }

        // Detectar "AGOTADO"
        if (strpos($html, 'AGOTADO') !== false) {
            Log::info('ElcorteinglesController - AGOTADO DETECTADO:', [
                'oferta_id' => $oferta->id,
                'oferta_tipo' => get_class($oferta)
            ]);
            
            // Actualizar oferta para no mostrar
            $oferta->update(['mostrar' => 'no']);
            
            // Crear aviso con fecha a una semana vista
            $avisoId = DB::table('avisos')->insertGetId([
                'texto_aviso'     => 'SIN STOCK 1A VEZ - GENERADO AUTOMATICAMENTE',
                'fecha_aviso'     => now()->addWeek(), // Una semana vista
                'user_id'         => 1,                 // usuario sistema
                'avisoable_type'  => \App\Models\OfertaProducto::class,
                'avisoable_id'    => $oferta->id,
                'oculto'          => 0,                 // visible
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
            
            Log::info('ElcorteinglesController - Aviso agotado creado:', [
                'aviso_id' => $avisoId,
                'oferta_id' => $oferta->id
            ]);
        }
    }

    /* ===================== Detección de Descuentos ===================== */

    /**
     * Detecta ofertas "2ª unidad al 70%" en el HTML.
     * Busca el texto "Comprando 2, la 2ª unidad sale a" en el HTML.
     */
    private function detectarOferta2aUnidad70(string $html): bool
    {
        // Buscar "Comprando 2, la 2ª unidad sale a" en cualquier parte del HTML
        return (bool) preg_match('/Comprando\s+2[,\s]+la\s+2[ªa]\s+unidad\s+sale\s+a/i', $html);
    }

    /**
     * Detecta y guarda los descuentos en la oferta sin modificar precios.
     */
    private function detectarYGuardarDescuentos(string $html, $oferta): void
    {
        if (!$oferta || !($oferta instanceof OfertaProducto)) {
            return;
        }

        $tieneOferta2a70 = $this->detectarOferta2aUnidad70($html);
        $descuentoAnterior = $oferta->descuentos;
        $descuentoNuevo = null;

        // Determinar qué descuento aplicar
        if ($tieneOferta2a70) {
            $descuentoNuevo = '2a al 70';
        }

        // Si hay descuento nuevo
        if ($descuentoNuevo !== null) {
            Log::info('ElcorteinglesController - DESCUENTO DETECTADO:', [
                'oferta_id' => $oferta->id,
                'descuento_nuevo' => $descuentoNuevo,
                'descuento_anterior' => $descuentoAnterior
            ]);

            // Actualizar el campo descuentos de la oferta
            $oferta->update(['descuentos' => $descuentoNuevo]);

            // Solo crear aviso si el descuento es nuevo o ha cambiado
            if ($descuentoAnterior !== $descuentoNuevo) {
                $textoAviso = match($descuentoNuevo) {
                    '2a al 70' => 'DETECTADO DESCUENTO 2ª UNIDAD AL 70% - GENERADO AUTOMÁTICAMENTE',
                    default => 'DETECTADO DESCUENTO - GENERADO AUTOMÁTICAMENTE'
                };

                $avisoId = DB::table('avisos')->insertGetId([
                    'texto_aviso'     => $textoAviso,
                    'fecha_aviso'     => now(),
                    'user_id'         => 1,
                    'avisoable_type'  => \App\Models\OfertaProducto::class,
                    'avisoable_id'    => $oferta->id,
                    'oculto'          => 0,
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ]);

                Log::info('ElcorteinglesController - Aviso descuento creado:', [
                    'aviso_id' => $avisoId,
                    'oferta_id' => $oferta->id,
                    'descuento' => $descuentoNuevo
                ]);
            }
        } else {
            // Si no hay descuentos pero la oferta tenía descuentos de El Corte Inglés, limpiar el campo
            if ($descuentoAnterior === '2a al 70') {
                Log::info('ElcorteinglesController - Descuentos ya no disponibles, limpiando campo descuentos:', [
                    'oferta_id' => $oferta->id,
                    'descuentos_anterior' => $descuentoAnterior
                ]);

                $oferta->update(['descuentos' => null]);

                Log::info('ElcorteinglesController - Campo descuentos limpiado:', [
                    'oferta_id' => $oferta->id
                ]);
            }
        }
    }

}


