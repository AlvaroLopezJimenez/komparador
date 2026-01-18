<?php

namespace App\Http\Controllers\Scraping\Tiendas;

use Illuminate\Http\JsonResponse;

class NappyController extends PlantillaTiendaController
{
    /**
     * Nappy: el precio correcto está en:
     * <span class="product-price current-price-value" content="90.1">
     *
     * Sin bucles de reintento.
     * Devuelve número sin símbolo € y con punto decimal.
     */
    public function obtenerPrecio($url, $variante = null, $tienda = null, $oferta = null): JsonResponse
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

        // DETECCIÓN DE SIN STOCK
        $this->detectarSinStock($html, $oferta);

        // 1) Buscar atributo content en la clase product-price current-price-value
        if (preg_match(
            '~class=["\']product-price\s+current-price-value["\'][^>]*\scontent=["\'](?<p>\d+(?:[.,]\d{1,2}))["\']~i',
            $html,
            $m
        ) && !empty($m['p'])) {
            $precio = $this->aNumero($m['p']);
            if ($precio !== null) {
                return response()->json(['success' => true, 'precio' => $precio]);
            }
        }

        // 2) Fallback: mismo span pero con el precio como texto interno
        if (preg_match(
            '~class=["\']product-price\s+current-price-value["\'][^>]*>\s*(?<p>\d{1,3}(?:[.,]\d{2}))\s*(?:€|&euro;)?~i',
            $html,
            $m2
        ) && !empty($m2['p'])) {
            $precio = $this->aNumero($m2['p']);
            if ($precio !== null) {
                return response()->json(['success' => true, 'precio' => $precio]);
            }
        }

        return response()->json([
            'success' => false,
            'error'   => 'No se pudo encontrar el precio en la página de Nappy',
        ]);
    }

    /**
     * Convierte "90,10" o "90.1" a float con punto decimal.
     */
    private function aNumero($raw): ?float
    {
        if (!is_string($raw) && !is_numeric($raw)) return null;
        $s = trim((string)$raw);

        if (strpos($s, ',') !== false && strpos($s, '.') !== false) {
            // Caso raro con miles y coma decimal
            $s = str_replace('.', '', $s);
        }
        $s = str_replace(',', '.', $s);

        if (!is_numeric($s)) return null;
        return (float)$s;
    }

    /** Detecta sin stock basándose en el mensaje específico de Nappy */
    private function detectarSinStock(string $html, $oferta): void
    {
        if (!$oferta || !($oferta instanceof \App\Models\OfertaProducto)) {
            return;
        }

        // Buscar el mensaje específico que indica que el producto no está disponible
        if (strpos($html, 'Este producto ya no esta disponible.') !== false) {
            // Actualizar oferta para no mostrar
            $oferta->update(['mostrar' => 'no']);
            
            // Crear aviso con fecha a 3 días
            \DB::table('avisos')->insertGetId([
                'texto_aviso'     => 'SIN STOCK 1A VEZ - GENERADO AUTOMATICAMENTE',
                'fecha_aviso'     => now()->addDays(3), // 3 días vista
                'user_id'         => 1,                 // usuario sistema
                'avisoable_type'  => \App\Models\OfertaProducto::class,
                'avisoable_id'    => $oferta->id,
                'oculto'          => 0,                 // visible
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
        }
    }
}


