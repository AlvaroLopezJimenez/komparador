<?php

namespace App\Http\Controllers\Scraping\Tiendas;

use App\Http\Controllers\Scraping\Tiendas\PlantillaTiendaController;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\OfertaProducto;

class NotinoController extends PlantillaTiendaController
{
    /**
     * Extrae el precio de una página de Notino.
     * - PRIORIDAD: span con data-testid="pd-price-wrapper" (precio rebajado/oferta).
     * - Fallback: span con data-testid="pd-price" (precio normal).
     * - Fallback adicional: otros patrones comunes de precios.
     * - Normaliza decimales (coma a punto).
     */
    public function obtenerPrecio($url, $variante = null, $tienda = null, $oferta = null): JsonResponse
    {
        $html = null;
        $ultimoError = null;

        // Obtener HTML de la página
        $resultado = $this->apiHTML->obtenerHTML($url, null, $tienda ? $tienda->api : null);

        if (is_array($resultado) && !empty($resultado['success']) && !empty($resultado['html'])) {
            $html = (string)$resultado['html'];
        } else {
            $ultimoError = is_array($resultado) ? ($resultado['error'] ?? 'Error desconocido') : 'Respuesta inválida de la API';
        }

        if ($html === null || $html === '') {
            return response()->json([
                'success' => false,
                'error'   => 'No se pudo obtener el HTML de Notino' . ($ultimoError ? (': ' . $ultimoError) : ''),
            ]);
        }

        $html = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // DEBUG: Log para verificar si tenemos oferta
        Log::info('NotinoController - Oferta recibida:', [
            'oferta_id' => $oferta ? $oferta->id : 'null',
            'oferta_tipo' => $oferta ? get_class($oferta) : 'null',
            'url' => $url
        ]);

        // DETECCIÓN DE AGOTADO
        $this->detectarAgotado($html, $oferta);

        // ---- 1) PRIORIDAD: Extraer precio rebajado del span con data-testid="pd-price-wrapper" ----
        // Este suele contener el precio de oferta (17,85) antes que el precio normal (21,00)
        $precio = $this->extraerPrecioDePdPriceWrapper($html);
        if ($precio !== null) {
            return response()->json(['success' => true, 'precio' => $precio]);
        }

        // ---- 2) Fallback: extraer precio normal del span con data-testid="pd-price" ----
        $precio = $this->extraerPrecioDePdPrice($html);
        if ($precio !== null) {
            return response()->json(['success' => true, 'precio' => $precio]);
        }

        // ---- 3) Fallback adicional: buscar otros patrones comunes de precios ----
        $precio = $this->extraerPrecioFallback($html);
        if ($precio !== null) {
            return response()->json(['success' => true, 'precio' => $precio]);
        }

        return response()->json([
            'success' => false,
            'error'   => 'No se pudo encontrar el precio en la página de Notino',
        ]);
    }

    /**
     * Busca el precio en <span data-testid="pd-price" content="21,00">21,00</span>
     * Prioriza el atributo content sobre el contenido del span.
     */
    private function extraerPrecioDePdPrice(string $html): ?float
    {
        // Múltiples patrones para diferentes formatos de pd-price
        $regexes = [
            // Patrón principal: <span data-testid="pd-price" content="21,00">21,00</span>
            '~<span[^>]*\bdata-testid\s*=\s*["\']pd-price["\'][^>]*\bcontent\s*=\s*["\'](?<p>[\d,]+)["\'][^>]*>.*?</span>~i',
            // Variante sin comillas en content
            '~<span[^>]*\bdata-testid\s*=\s*["\']pd-price["\'][^>]*\bcontent\s*=\s*(?<p>[\d,]+)[^>]*>.*?</span>~i',
            // Solo el contenido del span sin content
            '~<span[^>]*\bdata-testid\s*=\s*["\']pd-price["\'][^>]*>\s*(?<p>[\d,]+)\s*</span>~i',
            // Patrón más flexible para cualquier span con pd-price
            '~<span[^>]*\bdata-testid\s*=\s*["\']pd-price["\'][^>]*>.*?(?<p>[\d,]+).*?</span>~i',
        ];

        foreach ($regexes as $regex) {
            if (preg_match($regex, $html, $matches) && !empty($matches['p'])) {
                $precio = $this->normalizarImporte($matches['p']);
                if ($precio !== null) {
                    return $precio;
                }
            }
        }

        return null;
    }

    /**
     * Fallback: busca el precio en <span data-testid="pd-price-wrapper" class="w10nmds9">
     * <span content="17,85">17,85</span>
     */
    private function extraerPrecioDePdPriceWrapper(string $html): ?float
    {
        // Múltiples patrones para pd-price-wrapper
        $regexes = [
            // Patrón principal: span wrapper con span interno que tiene content
            '~<span[^>]*\bdata-testid\s*=\s*["\']pd-price-wrapper["\'][^>]*>.*?<span[^>]*\bcontent\s*=\s*["\'](?<p>[\d,]+)["\'][^>]*>.*?</span>~is',
            // Variante sin comillas en content
            '~<span[^>]*\bdata-testid\s*=\s*["\']pd-price-wrapper["\'][^>]*>.*?<span[^>]*\bcontent\s*=\s*(?<p>[\d,]+)[^>]*>.*?</span>~is',
            // Buscar el contenido del span interno sin content
            '~<span[^>]*\bdata-testid\s*=\s*["\']pd-price-wrapper["\'][^>]*>.*?<span[^>]*>\s*(?<p>[\d,]+)\s*</span>~is',
            // Patrón más flexible para cualquier contenido dentro del wrapper
            '~<span[^>]*\bdata-testid\s*=\s*["\']pd-price-wrapper["\'][^>]*>.*?(?<p>[\d,]+).*?</span>~is',
        ];

        foreach ($regexes as $regex) {
            if (preg_match($regex, $html, $matches) && !empty($matches['p'])) {
                $precio = $this->normalizarImporte($matches['p']);
                if ($precio !== null) {
                    return $precio;
                }
            }
        }

        return null;
    }

    /**
     * Fallback adicional: busca otros patrones comunes de precios en Notino
     */
    private function extraerPrecioFallback(string $html): ?float
    {
        // Patrones adicionales para diferentes formatos de precios
        $regexes = [
            // Buscar cualquier span con "price" en el data-testid
            '~<span[^>]*\bdata-testid\s*=\s*["\'][^"\']*price[^"\']*["\'][^>]*>\s*(?<p>[\d,]+)\s*</span>~i',
            // Buscar divs con clases que contengan "price"
            '~<div[^>]*\bclass\s*=\s*["\'][^"\']*price[^"\']*["\'][^>]*>\s*(?<p>[\d,]+)\s*</div>~i',
            // Buscar spans con clases que contengan "price"
            '~<span[^>]*\bclass\s*=\s*["\'][^"\']*price[^"\']*["\'][^>]*>\s*(?<p>[\d,]+)\s*</span>~i',
            // Buscar cualquier elemento con content que contenga números y comas
            '~<[^>]*\bcontent\s*=\s*["\'](?<p>[\d,]+)["\'][^>]*>~i',
            // Buscar elementos con itemprop="price"
            '~<[^>]*\bitemprop\s*=\s*["\']price["\'][^>]*>\s*(?<p>[\d,]+)\s*</[^>]*>~i',
        ];

        foreach ($regexes as $regex) {
            if (preg_match($regex, $html, $matches) && !empty($matches['p'])) {
                $precio = $this->normalizarImporte($matches['p']);
                if ($precio !== null) {
                    return $precio;
                }
            }
        }

        return null;
    }

    /**
     * Convierte una cadena de precio a float (sin símbolo €).
     * Acepta "21,00", "17,85", etc. Devuelve null si no es interpretable.
     * Convierte comas a puntos para el decimal.
     */
    private function normalizarImporte(string $importe): ?float
    {
        // Mantener solo dígitos y coma
        $s = preg_replace('/[^\d,]/u', '', $importe);
        if ($s === null || $s === '') {
            return null;
        }

        // Cambiar coma por punto para el decimal
        $s = str_replace(',', '.', $s);

        if (!preg_match('/^\d+(\.\d+)?$/', $s)) {
            return null;
        }

        return (float)$s;
    }

    /* ===================== Detección de Agotado ===================== */

    private function detectarAgotado(string $html, $oferta): void
    {
        if (!$oferta || !($oferta instanceof OfertaProducto)) {
            return;
        }

        // Detectar "AGOTADO" o "OUT OF STOCK" en Notino
        $agotadoPatterns = [
            'AGOTADO',
            'OUT OF STOCK',
            'SIN STOCK',
            'NO DISPONIBLE',
            'TEMPORALMENTE AGOTADO'
        ];

        foreach ($agotadoPatterns as $pattern) {
            if (stripos($html, $pattern) !== false) {
                Log::info('NotinoController - AGOTADO DETECTADO:', [
                    'oferta_id' => $oferta->id,
                    'oferta_tipo' => get_class($oferta),
                    'patron_detectado' => $pattern
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
                
                Log::info('NotinoController - Aviso agotado creado:', [
                    'aviso_id' => $avisoId,
                    'oferta_id' => $oferta->id
                ]);
                
                // Salir del bucle al encontrar el primer patrón
                break;
            }
        }
    }
}
