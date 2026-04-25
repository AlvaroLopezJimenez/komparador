<?php

namespace App\Http\Controllers\Scraping\Tiendas;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\OfertaProducto;

class AmazonController extends PlantillaTiendaController
{
    /**
     * Obtiene el precio usando la Amazon Product Info API o Amazon Product Advertising API
     */
    public function obtenerPrecio($url, $variante = null, $tienda = null, $oferta = null): JsonResponse
    {
        $apiTienda = $tienda ? $tienda->api : null;
        $resultado = $this->apiHTML->obtenerHTML($url, null, $apiTienda);

        if (!is_array($resultado) || empty($resultado['success'])) {
            return response()->json([
                'success' => false,
                'error'   => is_array($resultado) ? ($resultado['error'] ?? 'No se pudo obtener el precio') : 'Respuesta inválida de la API',
            ]);
        }

        // DEBUG: Log para verificar si tenemos oferta
        Log::info('AmazonController - Oferta recibida:', [
            'oferta_id' => $oferta ? $oferta->id : 'null',
            'oferta_tipo' => $oferta ? get_class($oferta) : 'null',
            'url' => $url,
            'api_tienda' => $apiTienda
        ]);

        $precio = null;

        // =================== NUEVA API: AMAZON PRICING AND PRODUCT INFO ===================
        // Manejo de la nueva API Amazon Pricing And Product Info (RapidAPI)
        if ($apiTienda === 'amazonPricing' || ($resultado['proveedor'] ?? '') === 'AMAZON_PRICING') {
            $precio = $this->extraerPrecioAmazonPricing($resultado, $oferta);
            
            // Si el precio es negativo o null, ya se generó el aviso en extraerPrecioAmazonPricing
            if ($precio !== null && $precio > 0) {
                Log::info('AmazonController - Precio encontrado (Amazon Pricing):', [
                    'precio' => $precio,
                    'oferta_id' => $oferta ? $oferta->id : 'null',
                    'api_tienda' => $apiTienda
                ]);
                
                return response()->json(['success' => true, 'precio' => $precio]);
            } else {
                // Precio negativo o null - el aviso ya fue generado
                return response()->json(['success' => false, 'error' => 'Producto sin stock (precio negativo o nulo)']);
            }
        }
        // =================== FIN NUEVA API: AMAZON PRICING ===================

        // Determinar qué API se está usando y extraer el precio según corresponda
        if ($apiTienda === 'amazonApi' || ($resultado['proveedor'] ?? '') === 'AMAZON_API') {
            // Amazon Creators API (o PA-API legacy) - extraer precio del Buy Box del JSON crudo
            $precio = $this->extraerPrecioAmazonApi($resultado['raw'] ?? []);
        } elseif ($apiTienda === 'amazonProductInfo' || ($resultado['proveedor'] ?? '') === 'AMAZON_PRODUCT_INFO') {
            // Amazon Product Info API - el precio ya viene extraído en $resultado['precio']
            $precio = $resultado['precio'] ?? null;
        } else {
            // Fallback: intentar ambos métodos
            if (isset($resultado['precio']) && $resultado['precio'] !== null) {
                $precio = $resultado['precio'];
            } elseif (isset($resultado['raw'])) {
                $precio = $this->extraerPrecioAmazonApi($resultado['raw']);
            }
        }

        // Verificar si tenemos precio en la respuesta
        if ($precio !== null) {
            // Amazon API: si el Buy Box es "Amazon Segunda mano", no mostrar oferta y generar aviso
            $raw = $resultado['raw'] ?? [];
            if (($apiTienda === 'amazonApi' || ($resultado['proveedor'] ?? '') === 'AMAZON_API') && $this->esBuyBoxSegundaMano($raw)) {
                if ($oferta && $oferta instanceof OfertaProducto) {
                    $oferta->update(['mostrar' => 'no']);
                    DB::table('avisos')->insertGetId([
                        'texto_aviso'     => 'Segunda mano - 1a vez GENERADO AUTOMATICAMENTE',
                        'fecha_aviso'     => now()->addDays(4),
                        'user_id'         => 1,
                        'avisoable_type'  => \App\Models\OfertaProducto::class,
                        'avisoable_id'    => $oferta->id,
                        'oculto'          => 0,
                        'created_at'      => now(),
                        'updated_at'      => now(),
                    ]);
                }
                return response()->json(['success' => false, 'error' => 'Buy Box es Segunda mano; oferta ocultada y aviso generado']);
            }

            Log::info('AmazonController - Precio encontrado:', [
                'precio' => $precio,
                'oferta_id' => $oferta ? $oferta->id : 'null',
                'api_tienda' => $apiTienda
            ]);
            
            return response()->json(['success' => true, 'precio' => $precio]);
        }

        // Si no hay precio (producto sin stock), generar aviso
        if ($oferta && $oferta instanceof OfertaProducto) {
            Log::info('AmazonController - SIN STOCK DETECTADO:', [
                'oferta_id' => $oferta->id,
                'oferta_tipo' => get_class($oferta),
                'api_tienda' => $apiTienda
            ]);
            
            // Actualizar oferta para no mostrar
            $oferta->update(['mostrar' => 'no']);
            
            // Crear aviso con fecha a una hora vista
            $avisoId = DB::table('avisos')->insertGetId([
                'texto_aviso'     => 'SIN STOCK 1A VEZ - GENERADO AUTOMÁTICAMENTE',
                'fecha_aviso'     => now()->addHour(), // Una hora vista
                'user_id'         => 1,                 // usuario sistema
                'avisoable_type'  => \App\Models\OfertaProducto::class,
                'avisoable_id'    => $oferta->id,
                'oculto'          => 0,                 // visible
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
            
            Log::info('AmazonController - Aviso sin stock creado:', [
                'aviso_id' => $avisoId,
                'oferta_id' => $oferta->id
            ]);
        }

        return response()->json(['success' => false, 'error' => 'No se pudo encontrar el precio en la API de Amazon']);
    }

    /**
     * Extrae el precio de la respuesta de Amazon API (Creators API o PA-API legacy).
     * Soporta formato camelCase (Creators) y PascalCase (PA-API).
     * Usa el precio del listing con isBuyBoxWinner (oferta destacada en la ficha); si no hay, el primer listing.
     *
     * @param array $raw Respuesta JSON de la API
     * @return float|null Precio del Buy Box o del primer listing, o null
     */
    private function extraerPrecioAmazonApi(array $raw): ?float
    {
        try {
            $items = $raw['itemsResult']['items'] ?? $raw['ItemsResult']['Items'] ?? null;
            if (!is_array($items) || empty($items)) {
                Log::warning('AmazonController - No se encontraron items en la respuesta:', [
                    'raw_keys' => array_keys($raw),
                ]);
                return null;
            }

            $item = $items[0];

            // Creators API (camelCase): offersV2.listings
            $listings = $item['offersV2']['listings'] ?? null;
            $isCamel = is_array($listings);

            if (!$isCamel) {
                $listings = $item['OffersV2']['Listings'] ?? $item['Offers']['Listings'] ?? null;
            }

            if (!is_array($listings) || empty($listings)) {
                Log::warning('AmazonController - No se encontró precio en la respuesta de amazonApi:', [
                    'has_offersV2' => isset($item['offersV2']) || isset($item['OffersV2']),
                    'item_keys' => array_keys($item),
                ]);
                return null;
            }

            $buyBoxListing = null;
            foreach ($listings as $listing) {
                $isWinner = $listing['isBuyBoxWinner'] ?? $listing['IsBuyBoxWinner'] ?? false;
                if ($isWinner === true) {
                    $buyBoxListing = $listing;
                    break;
                }
            }
            if ($buyBoxListing === null) {
                $buyBoxListing = $listings[0];
            }

            $amount = null;
            if ($isCamel) {
                $amount = $buyBoxListing['price']['money']['amount'] ?? null;
            } else {
                $amount = $buyBoxListing['Price']['Money']['Amount'] ?? $buyBoxListing['Price']['Amount'] ?? null;
            }

            if ($amount === null || $amount === '') {
                return null;
            }

            $precio = $this->normalizarPrecioAmazon($amount);
            return $precio !== null && $precio > 0 ? (float) $precio : null;
        } catch (\Throwable $e) {
            Log::error('AmazonController - Error al extraer precio de amazonApi:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        return null;
    }

    /**
     * Indica si el listing que gana el Buy Box es "Amazon Segunda mano" (u otro vendedor de segunda mano).
     * Soporta respuesta camelCase (Creators API) y PascalCase (PA-API).
     */
    private function esBuyBoxSegundaMano(array $raw): bool
    {
        $items = $raw['itemsResult']['items'] ?? $raw['ItemsResult']['Items'] ?? null;
        if (!is_array($items) || empty($items)) {
            return false;
        }
        $item = $items[0];
        $listings = $item['offersV2']['listings'] ?? $item['OffersV2']['Listings'] ?? $item['Offers']['Listings'] ?? null;
        if (!is_array($listings)) {
            return false;
        }
        foreach ($listings as $listing) {
            $isWinner = $listing['isBuyBoxWinner'] ?? $listing['IsBuyBoxWinner'] ?? false;
            if ($isWinner !== true) {
                continue;
            }
            $name = $listing['merchantInfo']['name'] ?? $listing['MerchantInfo']['Name'] ?? '';
            $name = is_string($name) ? $name : '';
            return stripos($name, 'segunda mano') !== false;
        }
        return false;
    }

    /**
     * Normaliza un valor de precio de la API de Amazon a float (limpia comas, símbolos, etc.).
     */
    private function normalizarPrecioAmazon($valor): ?float
    {
        if ($valor === null || $valor === '') {
            return null;
        }
        $s = str_replace(',', '.', trim((string) $valor));
        $s = preg_replace('/[^0-9.-]/', '', $s);
        if ($s === '' || !is_numeric($s)) {
            return null;
        }
        $f = (float) $s;
        return $f > 0 ? $f : null;
    }

    /**
     * =================== NUEVA API: AMAZON PRICING AND PRODUCT INFO ===================
     * Extrae el precio de la respuesta de Amazon Pricing And Product Info API
     * Busca en responseData.buyBoxPrice
     * Si el precio es < 0, genera un aviso de sin stock
     * 
     * @param array $resultado Respuesta completa de la API
     * @param mixed $oferta Objeto OfertaProducto o null
     * @return float|null Precio encontrado o null si es negativo
     */
    private function extraerPrecioAmazonPricing(array $resultado, $oferta = null): ?float
    {
        try {
            $raw = $resultado['raw'] ?? [];
            
            // Verificar que existe responseData
            if (!isset($raw['responseData']) || !is_array($raw['responseData'])) {
                Log::warning('AmazonController - No se encontró responseData en la respuesta de Amazon Pricing:', [
                    'raw_keys' => array_keys($raw),
                    'has_response_data' => isset($raw['responseData'])
                ]);
                return null;
            }

            $responseData = $raw['responseData'];
            
            // Extraer buyBoxPrice
            if (!isset($responseData['buyBoxPrice'])) {
                Log::warning('AmazonController - No se encontró buyBoxPrice en la respuesta:', [
                    'response_data_keys' => array_keys($responseData)
                ]);
                return null;
            }

            $buyBoxPrice = $responseData['buyBoxPrice'];
            
            // Convertir a float
            $precio = is_numeric($buyBoxPrice) ? (float) $buyBoxPrice : null;
            
            // Si el precio es negativo o cero, generar aviso de sin stock
            if ($precio !== null && $precio < 0) {
                Log::info('AmazonController - PRECIO NEGATIVO DETECTADO (Amazon Pricing):', [
                    'precio' => $precio,
                    'oferta_id' => $oferta ? $oferta->id : 'null',
                ]);
                
                // Generar aviso si tenemos oferta
                if ($oferta && $oferta instanceof OfertaProducto) {
                    // Actualizar oferta para no mostrar
                    $oferta->update(['mostrar' => 'no']);
                    
                    // Crear aviso con fecha a una hora vista
                    $avisoId = DB::table('avisos')->insertGetId([
                        'texto_aviso'     => 'Sin Stock 1a vez - Generado Automaticamente',
                        'fecha_aviso'     => now()->addHour(), // Una hora vista
                        'user_id'         => 1,                 // usuario sistema
                        'avisoable_type'  => \App\Models\OfertaProducto::class,
                        'avisoable_id'    => $oferta->id,
                        'oculto'          => 0,                 // visible
                        'created_at'      => now(),
                        'updated_at'      => now(),
                    ]);
                    
                    Log::info('AmazonController - Aviso sin stock creado (Amazon Pricing):', [
                        'aviso_id' => $avisoId,
                        'oferta_id' => $oferta->id,
                        'precio' => $precio
                    ]);
                }
                
                return null; // Devolver null para indicar sin stock
            }
            
            // Si el precio es válido (> 0), devolverlo
            if ($precio !== null && $precio > 0) {
                Log::info('AmazonController - Precio extraído de Amazon Pricing:', [
                    'precio_original' => $buyBoxPrice,
                    'precio_procesado' => $precio
                ]);
                
                return $precio;
            }
            
            return null;
            
        } catch (\Throwable $e) {
            Log::error('AmazonController - Error al extraer precio de Amazon Pricing:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }

        return null;
    }
    // =================== FIN NUEVA API: AMAZON PRICING ===================
}
