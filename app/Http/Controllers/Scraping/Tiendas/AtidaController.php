<?php

namespace App\Http\Controllers\Scraping\Tiendas;

use App\Http\Controllers\Scraping\Tiendas\PlantillaTiendaController;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\OfertaProducto;

class AtidaController extends PlantillaTiendaController
{
    /**
     * Extrae el precio de una página de Atida.
     * - Mantiene las heurísticas actuales: price-wrapper, getFormattedFinalPrice(), <span class="price">,
     *   .clerk-price y bloque "PVP".
     * - Caso especial aplicado: si hay más de un <span class="price">…</span>, se devuelve **el segundo**;
     *   si solo hay uno, ese.
     * - Paso previo añadido: si existe bloque <div class="final-price">…</div>, se usa ese precio.
     * - Devuelve número sin símbolo €; normaliza coma/punto y entidades (&nbsp;).
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

        // Normalizamos entidades para facilitar regex (ej. &nbsp;)
        $html = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // ——— VERIFICACIÓN 404: Detectar si la página no existe
        if (preg_match('~<meta[^>]*name=["\']title["\'][^>]*content=["\']404 Not Found["\']~i', $html)) {
            // DEBUG: Log para verificar si tenemos oferta
            Log::info('AtidaController - Página 404 detectada:', [
                'oferta_id' => $oferta ? $oferta->id : 'null',
                'oferta_tipo' => $oferta ? get_class($oferta) : 'null',
                'url' => $url
            ]);

            // Si tenemos oferta, actualizar para no mostrar y crear aviso
            if ($oferta && $oferta instanceof OfertaProducto) {
                Log::info('AtidaController - ENLACE 404 DETECTADO:', [
                    'oferta_id' => $oferta->id,
                    'oferta_tipo' => get_class($oferta)
                ]);
                
                // Actualizar oferta para no mostrar
                $oferta->update(['mostrar' => 'no']);
                
                // Crear aviso con fecha a 4 días vista
                $avisoId = DB::table('avisos')->insertGetId([
                    'texto_aviso'     => 'ENLACE 404 1A VEZ - GENERADO AUTOMÁTICAMENTE',
                    'fecha_aviso'     => now()->addDays(4), // 4 días vista
                    'user_id'         => 1,                 // usuario sistema
                    'avisoable_type'  => \App\Models\OfertaProducto::class,
                    'avisoable_id'    => $oferta->id,
                    'oculto'          => 0,                 // visible
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ]);
                
                Log::info('AtidaController - Aviso enlace 404 creado:', [
                    'aviso_id' => $avisoId,
                    'oferta_id' => $oferta->id
                ]);
            }

            return response()->json([
                'success' => false,
                'error'   => 'Página 404 - Enlace no encontrado',
            ]);
        }

        // ——— Paso 0: si hay bloque FINAL explícito, úsalo y termina.
        //    <div class="final-price ..."><span class="price">6,99 €</span>…
        if (preg_match(
            '~<div[^>]*class=["\'][^"\']*\bfinal-price\b[^"\']*["\'][^>]*>.*?<span[^>]*class=["\'][^"\']*\bprice\b[^"\']*["\'][^>]*>\s*(?<p>\d{1,3}(?:[.\s]\d{3})*(?:[.,]\d{2}))\s*(?:€|&euro;)?~si',
            $html,
            $mf
        ) && !empty($mf['p'])) {
            $precio = $this->normalizarImporte($mf['p']);
            if ($precio !== null) {
                return response()->json(['success' => true, 'precio' => $precio]);
            }
        }

        // (Opcional) Paso 0-bis: meta en la cabecera: <meta property="product:price:amount" content="6.99">
        // if (preg_match('~<meta[^>]*\bproperty=["\']product:price:amount["\'][^>]*\bcontent=["\'](?<p>[\d\.,]+)["\']~i', $html, $mm) && !empty($mm['p'])) {
        //     $precio = $this->normalizarImporte($mm['p']);
        //     if ($precio !== null) {
        //         return response()->json(['success' => true, 'precio' => $precio]);
        //     }
        // }

        // Patrones posibles detectados en Atida (se mantiene el orden original)
        $patrones = [
            // <span class="price-wrapper ..."><span class="price" x-html="getFormattedFinalPrice()"><span class="price">23,99 €</span></span></span>
            '~<span[^>]*class=["\']price-wrapper[^"\']*["\'][^>]*>.*?(?:<span[^>]*class=["\']price[^"\']*["\'][^>]*>.*?)?(?<p>\d{1,3}(?:[.\s]\d{3})*(?:[.,]\d{2})?)\s*(?:€|&euro;)?~si',
            // <span class="price font-semibold" x-html="getFormattedFinalPrice()">23,99 €</span>
            '~<span[^>]*x-html=["\']getFormattedFinalPrice\(\)["\'][^>]*>\s*(?<p>\d{1,3}(?:[.\s]\d{3})*(?:[.,]\d{2})?)\s*(?:€|&euro;)?\s*</span>~si',
            // <span class="price">23,99 €</span>
            '~<span[^>]*class=["\']price["\'][^>]*>\s*(?<p>\d{1,3}(?:[.\s]\d{3})*(?:[.,]\d{2})?)\s*(?:€|&euro;)?\s*</span>~si',
            // <span class="clerk-price"> 23,99 </span>
            '~<span[^>]*class=["\']clerk-price["\'][^>]*>\s*(?<p>\d{1,3}(?:[.\s]\d{3})*(?:[.,]\d{2})?)\s*</span>~si',
            // <li class="flex flex-col"><span class="font-semibold">PVP</span> 23,99 </li>
            '~<li[^>]*>\s*<span[^>]*>\s*PVP\s*</span>\s*(?<p>\d{1,3}(?:[.\s]\d{3})*(?:[.,]\d{2})?)\b~si',
        ];

        $precio = null;

        foreach ($patrones as $regex) {
            // Caso especial: patrón genérico de <span class="price"> -> si hay 2, usar el segundo
            if (strpos($regex, 'class=["\']price["\']') !== false) {
                if (preg_match_all($regex, $html, $matches) && !empty($matches['p'])) {
                    $indice = (count($matches['p']) >= 2) ? 1 : 0; // 2º si hay, si no el primero
                    $precio = $this->normalizarImporte($matches['p'][$indice]);
                    if ($precio !== null) {
                        break;
                    }
                }
            } else {
                if (preg_match($regex, $html, $m) && !empty($m['p'])) {
                    $precio = $this->normalizarImporte($m['p']);
                    if ($precio !== null) {
                        break;
                    }
                }
            }
        }

        if ($precio === null) {
            return response()->json([
                'success' => false,
                'error'   => 'No se pudo encontrar el precio en la página',
            ]);
        }

        return response()->json([
            'success' => true,
            'precio'  => $precio, // número sin símbolo €; con decimales solo si existen
        ]);
    }

    /**
     * Convierte una cadena de precio europea/española a float (sin símbolo €).
     * - Acepta formatos: "23,99", "1.234,56", "23.99", "1234", etc.
     * - Devuelve null si no se puede interpretar.
     */
    private function normalizarImporte(string $importe): ?float
    {
        // Eliminar cualquier cosa que no sea dígito, coma o punto
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
        } // else: solo dígitos o ya con punto decimal

        if (!preg_match('/^\d+(\.\d+)?$/', $s)) {
            return null;
        }

        return (float)$s;
    }
}


