<?php

namespace App\Http\Controllers\Scraping\Tiendas;

use App\Http\Controllers\Scraping\Tiendas\PlantillaTiendaController;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\OfertaProducto;

class KidinnController extends PlantillaTiendaController
{
    /**
     * Extrae el precio de una página de Kidinn.
     * - Prioriza precio visible: <p id="js-precio" class="txt-precio">6.99 €</p>.
     * - Luego JSON-LD: offers[].price dentro de "@type":"Offer".
     * - Después cualquier atributo data-price="6.99".
     * - Fallback visible: <p class="txt-precio">6.99 €</p>.
     * - Devuelve número sin símbolo €; normaliza coma/punto y entidades (&nbsp;).
     */
    public function obtenerPrecio($url, $variante = null, $tienda = null, $oferta = null): JsonResponse
    {
        $r = $this->apiHTML->obtenerHTML($url, null, $tienda ? $tienda->api : null);

        if (!is_array($r) || empty($r['success']) || empty($r['html'])) {
            return response()->json([
                'success' => false,
                'error'   => is_array($r) ? ($r['error'] ?? 'No se pudo obtener el HTML') : 'Respuesta inválida de la API',
            ]);
        }

        $html = html_entity_decode((string)$r['html'], ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // DEBUG: Log para verificar si tenemos oferta
        Log::info('KidinnController - Oferta recibida:', [
            'oferta_id' => $oferta ? $oferta->id : 'null',
            'oferta_tipo' => $oferta ? get_class($oferta) : 'null',
            'url' => $url
        ]);

        // DETECCIÓN DE SIN STOCK
        $this->detectarSinStock($html, $oferta);

        // 1) <p id="js-precio" ...>6.99 €</p>
        if (preg_match(
            '~<p[^>]*\bid=["\']js-precio["\'][^>]*>\s*(?<p>\d{1,3}(?:[.\s]\d{3})*(?:[.,]\d{2}))\s*(?:€|&euro;)?\s*</p>~i',
            $html,
            $m1
        ) && !empty($m1['p'])) {
            if (($p = $this->normalizarImporte($m1['p'])) !== null) {
                return response()->json(['success' => true, 'precio' => $p]);
            }
        }

        // 2) JSON-LD offers[].price (evitar shipping/envío por si acaso)
        if (preg_match_all(
            '~"offers"\s*:\s*\[(?<blk>\{.*?\})\]~is',
            $html,
            $offersBlocks
        )) {
            foreach ($offersBlocks['blk'] as $blk) {
                // Buscar price dentro del bloque Offer
                if (stripos($blk, '"Offer"') === false) continue;
                if (preg_match('~"price"\s*:\s*"?(\d+(?:[.,]\d{2}))"?~i', $blk, $pm) && !empty($pm[1])) {
                    $p = $this->normalizarImporte($pm[1]);
                    if ($p !== null) {
                        return response()->json(['success' => true, 'precio' => $p]);
                    }
                }
            }
        }

        // 3) Atributo data-price="6.99"
        if (preg_match('~\bdata-price\s*=\s*["\'](?<p>\d+(?:[.,]\d{2}))["\']~i', $html, $m3) && !empty($m3['p'])) {
            if (($p = $this->normalizarImporte($m3['p'])) !== null) {
                return response()->json(['success' => true, 'precio' => $p]);
            }
        }

        // 4) Fallback: <p class="txt-precio">6.99 €</p>
        if (preg_match(
            '~<p[^>]*\bclass=["\'][^"\']*\btxt-precio\b[^"\']*["\'][^>]*>\s*(?<p>\d{1,3}(?:[.\s]\d{3})*(?:[.,]\d{2}))\s*(?:€|&euro;)?\s*</p>~i',
            $html,
            $m4
        ) && !empty($m4['p'])) {
            if (($p = $this->normalizarImporte($m4['p'])) !== null) {
                return response()->json(['success' => true, 'precio' => $p]);
            }
        }

        return response()->json([
            'success' => false,
            'error'   => 'No se pudo encontrar el precio en la página de Kidinn',
        ]);
    }

    /**
     * Convierte "6,99", "6.99" o "1.234,56" a float con punto decimal.
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

    /* ===================== Detección de Sin Stock ===================== */

    private function detectarSinStock(string $html, $oferta): void
    {
        if (!$oferta || !($oferta instanceof OfertaProducto)) {
            return;
        }

        // Detectar "¡Lo sentimos! En este momento no disponemos del artículo."
        if (strpos($html, '¡Lo sentimos! En este momento no disponemos del artículo.') !== false) {
            Log::info('KidinnController - SIN STOCK DETECTADO:', [
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
            
            Log::info('KidinnController - Aviso sin stock creado:', [
                'aviso_id' => $avisoId,
                'oferta_id' => $oferta->id
            ]);
        }
    }
}


