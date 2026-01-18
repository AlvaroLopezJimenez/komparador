<?php

namespace App\Http\Controllers\Scraping\Tiendas;

use App\Http\Controllers\Scraping\Tiendas\PlantillaTiendaController;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\OfertaProducto;

class OkfarmaController extends PlantillaTiendaController
{
    /**
     * Extrae el precio de la nueva tienda.
     * Prioridad:
     *   1) <span class="price_product" data-price="7.78">
     *   2) <span class="price_with_tax price_pvp" content="6.429752">
     *   3) Texto interno de esos spans (p.ej. "7,78 €" con NBSP)
     *   4) Meta fallback (itemprop/property)
     */
    public function obtenerPrecio($url, $variante = null, $tienda = null, $oferta = null): JsonResponse
    {
        $resultado = $this->apiHTML->obtenerHTML($url, null, $tienda ? $tienda->api : null);

        if (!is_array($resultado) || empty($resultado['success'])) {
            return response()->json([
                'success' => false,
                'error'   => $resultado['error'] ?? 'No se pudo obtener el HTML',
            ]);
        }

        $html = (string)($resultado['html'] ?? '');
        if ($html === '') {
            return response()->json([
                'success' => false,
                'error'   => 'HTML vacío recibido',
            ]);
        }

        // Normaliza entidades y NBSP (U+00A0) a espacio normal
        $html = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $html = preg_replace('/\x{00A0}/u', ' ', $html); // NBSP -> ' '

        // DEBUG: Log para verificar si tenemos oferta
        Log::info('OkfarmaController - Oferta recibida:', [
            'oferta_id' => $oferta ? $oferta->id : 'null',
            'oferta_tipo' => $oferta ? get_class($oferta) : 'null',
            'url' => $url
        ]);

        // DETECCIÓN DE ERRORES EN LA PÁGINA
        // Verificar si hay mensaje de sin stock en el HTML
        $textoSinStock = 'Temporalmente sin stock';
        $conteoSinStock = substr_count($html, $textoSinStock);
        
        Log::info('OkfarmaController - Búsqueda de errores:', [
            'texto_sin_stock' => $textoSinStock,
            'conteo_sin_stock' => $conteoSinStock,
            'html_length' => strlen($html),
            'oferta_id' => $oferta ? $oferta->id : 'null'
        ]);
        
        // Manejar sin stock - solo si aparece más de una vez
        if ($conteoSinStock > 1) {
            Log::info('OkfarmaController - SIN STOCK DETECTADO:', [
                'oferta_id' => $oferta ? $oferta->id : 'null',
                'oferta_tipo' => $oferta ? get_class($oferta) : 'null',
                'conteo_sin_stock' => $conteoSinStock
            ]);
            
            // Si hay oferta asociada, actualizar para no mostrar y crear aviso
            if ($oferta && $oferta instanceof OfertaProducto) {
                Log::info('OkfarmaController - Actualizando oferta sin stock:', [
                    'oferta_id' => $oferta->id,
                    'mostrar_anterior' => $oferta->mostrar
                ]);
                
                // Actualizar oferta para no mostrar
                $oferta->update(['mostrar' => 'no']);
                
                // Crear aviso con fecha a una semana vista
                $avisoId = DB::table('avisos')->insertGetId([
                    'texto_aviso'     => 'SIN STOCK 1A VEZ - AVISO GENERADO AUTOMATICAMENTE',
                    'fecha_aviso'     => now()->addWeek(), // Una semana vista
                    'user_id'         => 1,                 // usuario sistema
                    'avisoable_type'  => \App\Models\OfertaProducto::class,
                    'avisoable_id'    => $oferta->id,
                    'oculto'          => 0,                 // visible
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ]);
                
                Log::info('OkfarmaController - Aviso creado:', [
                    'aviso_id' => $avisoId,
                    'oferta_id' => $oferta->id
                ]);
            } else {
                Log::warning('OkfarmaController - Sin stock detectado pero no hay oferta válida:', [
                    'oferta' => $oferta,
                    'oferta_tipo' => $oferta ? get_class($oferta) : 'null'
                ]);
            }
        }

        // 1) data-price en .price_product (con o sin comillas)
        if (preg_match(
            '~<span[^>]*class=["\'][^"\']*\bprice_product\b[^"\']*["\'][^>]*\bdata-price=\s*(?:"|\')?(?<p>[\d\.,]+)(?:"|\')?~siu',
            $html,
            $m1
        ) && !empty($m1['p'])) {
            $precio = $this->normalizarImporte($m1['p']);
            if ($precio !== null) {
                return response()->json(['success' => true, 'precio' => $precio]);
            }
        }

        // 2) content en .price_with_tax.price_pvp (con o sin comillas; el orden de clases no importa)
        if (preg_match(
            '~<span[^>]*class=["\'][^"\']*(?:\bprice_with_tax\b[^"\']*\bprice_pvp\b|\bprice_pvp\b[^"\']*\bprice_with_tax\b)[^"\']*["\'][^>]*\bcontent=\s*(?:"|\')?(?<p>[\d\.,]+)(?:"|\')?~siu',
            $html,
            $m2
        ) && !empty($m2['p'])) {
            $precio = $this->normalizarImporte($m2['p']);
            if ($precio !== null) {
                return response()->json(['success' => true, 'precio' => $precio]);
            }
        }

        // 3) Texto interno de price_product / price_with_tax / price_pvp
        //    Permitimos espacio normal o NBSP antes del símbolo €
        if (preg_match(
            '~<span[^>]*class=["\'][^"\']*\b(?:price_product|price_with_tax|price_pvp)\b[^"\']*["\'][^>]*>\s*(?<p>\d{1,3}(?:[.\s]\d{3})*(?:[.,]\d{2}))\s*(?:€|&euro;)?\s*</span>~siu',
            $html,
            $m3
        ) && !empty($m3['p'])) {
            $precio = $this->normalizarImporte($m3['p']);
            if ($precio !== null) {
                return response()->json(['success' => true, 'precio' => $precio]);
            }
        }

        // 4) Fallbacks de metadatos (por si existen)
        if (preg_match('~<meta[^>]*\bitemprop=["\']price["\'][^>]*\bcontent=["\'](?<p>[\d\.,]+)["\']~siu', $html, $mm1) && !empty($mm1['p'])) {
            $precio = $this->normalizarImporte($mm1['p']);
            if ($precio !== null) {
                return response()->json(['success' => true, 'precio' => $precio]);
            }
        }
        if (preg_match('~<meta[^>]*\bproperty=["\']product:price:amount["\'][^>]*\bcontent=["\'](?<p>[\d\.,]+)["\']~siu', $html, $mm2) && !empty($mm2['p'])) {
            $precio = $this->normalizarImporte($mm2['p']);
            if ($precio !== null) {
                return response()->json(['success' => true, 'precio' => $precio]);
            }
        }

        return response()->json([
            'success' => false,
            'error'   => 'No se pudo encontrar el precio en la página',
        ]);
    }

    /**
     * Convierte una cadena de precio europea/española a float (sin símbolo €).
     */
    private function normalizarImporte(string $importe): ?float
    {
        // Eliminar todo excepto dígitos, coma o punto
        $s = preg_replace('/[^\d\,\.]/u', '', $importe);
        if ($s === null || $s === '') {
            return null;
        }

        $tieneComa  = strpos($s, ',') !== false;
        $tienePunto = strpos($s, '.') !== false;

        if ($tieneComa && $tienePunto) {
            $lastComma = strrpos($s, ',');
            $lastDot   = strrpos($s, '.');

            if ($lastComma !== false && ($lastDot === false || $lastComma > $lastDot)) {
                $s = str_replace('.', '', $s);   // puntos como miles
                $s = str_replace(',', '.', $s);  // coma decimal -> punto
            } else {
                $s = str_replace(',', '', $s);   // comas como miles
            }
        } elseif ($tieneComa) {
            $s = str_replace(',', '.', $s);
        }

        if (!preg_match('/^\d+(\.\d+)?$/u', $s)) {
            return null;
        }

        return (float)$s;
    }
}
