<?php

namespace App\Http\Controllers\Scraping\Tiendas;

use App\Http\Controllers\Scraping\Tiendas\PlantillaTiendaController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\OfertaProducto;

class PrimorController extends PlantillaTiendaController
{
    /**
     * Devuelve JSON: { success: bool, precio?: float, error?: string }
     */
    public function obtenerPrecio($url, $variante = null, $tienda = null, $oferta = null)
    {
        $resultado = $this->apiHTML->obtenerHTML($url, null, $tienda ? $tienda->api : null);
        if (!$resultado['success']) {
            return response()->json([
                'success' => false,
                'error'   => $resultado['error'] ?? 'Error obteniendo HTML'
            ]);
        }

        $html = $resultado['html'];

        // DEBUG: Log para verificar si tenemos oferta
        Log::info('PrimorController - Oferta recibida:', [
            'oferta_id' => $oferta ? $oferta->id : 'null',
            'oferta_tipo' => $oferta ? get_class($oferta) : 'null',
            'url' => $url
        ]);

        // DETECCIÓN DE ERRORES EN LA PÁGINA
        // Verificar si hay mensaje de sin stock en el HTML
        $textoSinStock = '<div class="outstock-info">';
        $conteoSinStock = substr_count($html, $textoSinStock);
        
        Log::info('PrimorController - Búsqueda de errores:', [
            'texto_sin_stock' => $textoSinStock,
            'conteo_sin_stock' => $conteoSinStock,
            'html_length' => strlen($html),
            'oferta_id' => $oferta ? $oferta->id : 'null'
        ]);
        
        // Manejar sin stock - con que aparezca 1 vez es suficiente
        if ($conteoSinStock >= 1) {
            Log::info('PrimorController - SIN STOCK DETECTADO:', [
                'oferta_id' => $oferta ? $oferta->id : 'null',
                'oferta_tipo' => $oferta ? get_class($oferta) : 'null',
                'conteo_sin_stock' => $conteoSinStock
            ]);
            
            // Si hay oferta asociada, actualizar para no mostrar y crear aviso
            if ($oferta && $oferta instanceof OfertaProducto) {
                Log::info('PrimorController - Actualizando oferta sin stock:', [
                    'oferta_id' => $oferta->id,
                    'mostrar_anterior' => $oferta->mostrar
                ]);
                
                // Actualizar oferta para no mostrar
                $oferta->update(['mostrar' => 'no']);
                
                // Crear aviso con fecha a una semana vista
                $avisoId = DB::table('avisos')->insertGetId([
                    'texto_aviso'     => 'Sin stock 1a vez - AVISO GENERADO AUTOMATICAMENTE',
                    'fecha_aviso'     => now()->addWeek(), // Una semana vista
                    'user_id'         => 1,                 // usuario sistema
                    'avisoable_type'  => \App\Models\OfertaProducto::class,
                    'avisoable_id'    => $oferta->id,
                    'oculto'          => 0,                 // visible
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ]);
                
                Log::info('PrimorController - Aviso creado:', [
                    'aviso_id' => $avisoId,
                    'oferta_id' => $oferta->id
                ]);
            } else {
                Log::warning('PrimorController - Sin stock detectado pero no hay oferta válida:', [
                    'oferta' => $oferta,
                    'oferta_tipo' => $oferta ? get_class($oferta) : 'null'
                ]);
            }
        }

        // 0) PRIMERA OPCIÓN: Buscar "initialFinalPrice: " (con espacio) en el HTML
        $precioDesdeInitialFinalPrice = $this->precioDesdeInitialFinalPrice($html);
        if ($precioDesdeInitialFinalPrice !== null) {
            return response()->json(['success' => true, 'precio' => $precioDesdeInitialFinalPrice]);
        }

        // 1) Identificar el productId principal desde el formulario de la PDP.
        // <form id="product_addtocart_form"> ... <input type="hidden" name="product" value="49728">
        $productId = $this->extraerProductIdPrincipal($html);

        // 2) Si tenemos productId, intentamos leer el precio SOLO de su bloque.
        if ($productId !== null) {

            // 2.a) Bloque principal: <span id="product-price-{ID}" class="price-wrapper"> ... <meta itemprop="price" content="15.99">
            $precio = $this->precioDesdeMetaItempropPrice($html, $productId);
            if ($precio !== null) {
                return response()->json(['success' => true, 'precio' => $precio]);
            }

            // 2.b) Mismo bloque por data-price-amount (id o :id con \u002D):
            $precio = $this->precioDesdePriceWrapperPorId($html, $productId);
            if ($precio !== null) {
                return response()->json(['success' => true, 'precio' => $precio]);
            }
        }

        // 3) Fallbacks de la propia PDP (globales pero unívocos del producto principal)
        // 3.a) Open Graph product:price:amount
        if (preg_match('/<meta[^>]+property=["\']product:price:amount["\'][^>]+content=["\']\s*([\d]+[.,]\d{1,2})\s*["\']/i', $html, $m)) {
            $n = $this->toNumber($m[1]);
            if ($n !== null) {
                return response()->json(['success' => true, 'precio' => $n]);
            }
        }

        // 3.b) JSON-LD offers.price
        $jsonPrice = $this->extraerPrecioDesdeJsonLD($html);
        if ($jsonPrice !== null) {
            return response()->json(['success' => true, 'precio' => $jsonPrice]);
        }

        // 4) Último recurso: primer data-price-amount de la página (puede fallar si hay relacionados).
        if (preg_match('/data-price-amount=["\']\s*([\d]+[.,]\d{1,2})\s*["\']/i', $html, $m2)) {
            $n = $this->toNumber($m2[1]);
            if ($n !== null) {
                return response()->json(['success' => true, 'precio' => $n]);
            }
        }

        // 5) Última opción: buscar patrón "price: 31.000000" (puede estar en scripts o texto plano)
        $precioDesdePricePattern = $this->precioDesdePricePattern($html);
        if ($precioDesdePricePattern !== null) {
            return response()->json(['success' => true, 'precio' => $precioDesdePricePattern]);
        }

        return response()->json([
            'success' => false,
            'error'   => 'No se pudo encontrar el precio en la página de Primor'
        ]);
    }

    /**
     * Busca el patrón "initialFinalPrice: " (con espacio) en el HTML.
     * Puede estar en scripts, JSON o texto plano.
     */
    private function precioDesdeInitialFinalPrice(string $html): ?float
    {
        // Buscar patrón: initialFinalPrice: 31 o initialFinalPrice: 31.00 (con espacio después de los dos puntos)
        // Acepta números enteros o con punto/coma como separador decimal
        if (preg_match('/initialFinalPrice:\s+([\d]+(?:[.,]\d+)?)/i', $html, $m)) {
            Log::info('PrimorController - initialFinalPrice encontrado:', [
                'match' => $m[0],
                'precio_raw' => $m[1]
            ]);
            $n = $this->toNumber($m[1]);
            if ($n !== null) {
                Log::info('PrimorController - initialFinalPrice procesado:', [
                    'precio_final' => $n
                ]);
                return $n;
            }
        } else {
            Log::info('PrimorController - initialFinalPrice NO encontrado en el HTML');
        }

        return null;
    }

    /**
     * <form id="product_addtocart_form"> ... <input type="hidden" name="product" value="(\d+)">
     */
    private function extraerProductIdPrincipal(string $html): ?string
    {
        // Buscamos el form de la PDP por id para evitar confundir con forms de carruseles.
        if (preg_match('/<form[^>]+id=["\']product_addtocart_form["\'][\s\S]*?<input[^>]+name=["\']product["\'][^>]+value=["\'](\d+)["\']/i', $html, $m)) {
            return $m[1];
        }

        // Fallback: si no existe id en el form (raro), probamos un name="product" aislado.
        if (preg_match('/name=["\']product["\'][^>]+value=["\'](\d+)["\']/i', $html, $m2)) {
            return $m2[1];
        }

        return null;
    }

    /**
     * Busca <span id="product-price-{ID}" ...> ... <meta itemprop="price" content="15.99">
     */
    private function precioDesdeMetaItempropPrice(string $html, string $productId): ?float
    {
        // Capturamos el bloque de price-wrapper por su id y leemos el meta itemprop=price cercano.
        $re = '/<span[^>]+id=["\']product-price-'.preg_quote($productId,'/').'["\'][\s\S]*?(?:<meta[^>]+itemprop=["\']price["\'][^>]+content=["\']\s*([\d]+[.,]\d{1,2})\s*["\'][^>]*>)/i';
        if (preg_match($re, $html, $m)) {
            return $this->toNumber($m[1]);
        }
        return null;
    }

    /**
     * Lee data-price-amount desde:
     *  - id="product-price{ID}" o id="product-price-{ID}"
     *  - :id="$id('product\u002Dprice{ID}')" (Alpine)
     */
    private function precioDesdePriceWrapperPorId(string $html, string $productId): ?float
    {
        // id="product-price-49728" (bloque principal visible)
        $reIdDash = '/<span[^>]*id=["\']product-price-'.preg_quote($productId,'/').'["\'][\s\S]*?<span[^>]*class="[^"]*\bprice\b[^"]*"[^>]*>\s*([^<%]+)\s*<\/span>/i';
        if (preg_match($reIdDash, $html, $mDash)) {
            $n = $this->toNumber($mDash[1]);
            if ($n !== null) return $n;
        }

        // data-price-amount en el mismo bloque (por si existe)
        $reIdDashAmount = '/<span[^>]*id=["\']product-price-'.preg_quote($productId,'/').'["\'][^>]*>[\s\S]*?data-price-amount=["\']\s*([\d]+[.,]\d{1,2})\s*["\']/i';
        if (preg_match($reIdDashAmount, $html, $mAmt1)) {
            $n = $this->toNumber($mAmt1[1]);
            if ($n !== null) return $n;
        }

        // Variante Alpine: :id="$id('product\u002Dprice{ID}')"
        $reXid = '/<span[^>]*:(?:id|x-bind:id)\s*=\s*"\$id\([\'"]product\\\\u002Dprice'.preg_quote($productId,'/').'[\'"]\)"[^>]*data-price-amount=["\']\s*([\d]+[.,]\d{1,2})\s*["\']/i';
        if (preg_match($reXid, $html, $mX)) {
            $n = $this->toNumber($mX[1]);
            if ($n !== null) return $n;
        }

        return null;
    }

    /**
     * Lee el precio de JSON-LD (offers.price) si existe.
     */
    private function extraerPrecioDesdeJsonLD(string $html): ?float
    {
        if (preg_match_all('/<script[^>]+type=["\']application\/ld\+json["\'][^>]*>\s*([\s\S]*?)\s*<\/script>/i', $html, $scripts)) {
            foreach ($scripts[1] as $blob) {
                if (preg_match('/"offers"\s*:\s*{[^}]*"price"\s*:\s*"?(?<p>[\d]+[.,]\d{1,2})"?/i', $blob, $m)) {
                    $n = $this->toNumber($m['p']);
                    if ($n !== null) return $n;
                }
            }
        }
        return null;
    }

    /**
     * Busca el patrón "price: 31.000000" en el HTML (puede estar en scripts, JSON o texto plano).
     * Acepta variantes como: price: 31.000000, "price": 31.000000, price:31.000000, etc.
     */
    private function precioDesdePricePattern(string $html): ?float
    {
        // Buscar patrón: price: 31.000000 (con o sin comillas, con o sin espacio después de :)
        // Acepta números con punto o coma como separador decimal
        if (preg_match('/["\']?price["\']?\s*:\s*([\d]+[.,]\d+)/i', $html, $m)) {
            $n = $this->toNumber($m[1]);
            if ($n !== null) {
                return $n;
            }
        }

        return null;
    }

    /**
     * Normaliza texto de precio a float (sin símbolo €, sin %).
     */
    private function toNumber(string $raw): ?float
    {
        $txt = html_entity_decode($raw, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Descarta si contiene % (descuentos)
        if (strpos($txt, '%') !== false) return null;

        // Mantener solo dígitos y separadores , .
        $txt = preg_replace('/[^\d.,]/u', '', $txt);
        if ($txt === '' || $txt === null) return null;

        // Sin separadores -> entero
        if (strpos($txt, ',') === false && strpos($txt, '.') === false) {
            return (float) $txt;
        }

        // Último separador como decimal
        $lastComma = strrpos($txt, ',');
        $lastDot   = strrpos($txt, '.');
        $decPos    = max($lastComma !== false ? $lastComma : -1, $lastDot !== false ? $lastDot : -1);

        $intPart = substr($txt, 0, $decPos);
        $decPart = substr($txt, $decPos + 1);

        $intPart = preg_replace('/[^\d]/', '', $intPart);
        $decPart = preg_replace('/[^\d]/', '', $decPart);

        if ($intPart === '') return null;

        $norm = $decPart === '' ? $intPart . '.00' : $intPart . '.' . substr($decPart, 0, 2);
        return (float) $norm;
    }
}


