<?php

namespace App\Http\Controllers\Scraping\Tiendas;

use Illuminate\Http\JsonResponse;

class PromofarmaController extends PlantillaTiendaController
{
    /**
     * Obtiene el precio REAL del producto en Promofarma, leyendo únicamente el bloque principal:
     *   .cont .prices > span#price[data-qa-ta="verPrice"]
     * Prioriza @data-price y, si falta, el texto interno del span.
     * Devuelve float con punto decimal (sin símbolo €).
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
        $this->detectarProblemasDisponibilidad($html, $oferta);

        // -------- SOLO DOM+XPath, sin regex globales que puedan tocar carruseles/listados ----------
        $precio = $this->extraerPrecioPrincipal($html);
        if ($precio !== null) {
            return response()->json(['success' => true, 'precio' => $precio]);
        }

        return response()->json([
            'success' => false,
            'error'   => 'No se pudo localizar el precio en el bloque principal de Promofarma',
        ]);
    }

    /**
     * Localiza el precio en el bloque principal del producto.
     * Estrategia:
     *  - Buscar dentro de <body id="product-profile"> y/o contenedores de precio principal
     *  - .cont .prices > span#price[data-qa-ta="verPrice"]
     *  - Prioriza @data-price; si no, texto interno.
     */
    private function extraerPrecioPrincipal(string $html): ?float
    {
        \libxml_use_internal_errors(true);

        $dom = new \DOMDocument();
        // Forzar UTF-8 para caracteres especiales
        $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html, LIBXML_NOWARNING | LIBXML_NOERROR);
        $xp = new \DOMXPath($dom);

        // XPaths ordenados de más a menos restrictivo, siempre en el perfil del producto
        $queries = [
            // 1) Dentro del perfil de producto, en el bloque de precio principal sticky/desktop
            "//*[@id='product-profile']" .
            "//div[contains(concat(' ', normalize-space(@class), ' '), ' cont ')]" .
            "//div[contains(concat(' ', normalize-space(@class), ' '), ' prices ')]" .
            "//span[@id='price' and @data-qa-ta='verPrice']",

            // 2) Variante: algunos layouts no ponen 'cont' arriba, pero sí .price-container
            "//*[@id='product-profile']" .
            "//div[contains(concat(' ', normalize-space(@class), ' '), ' price-container ')]" .
            "//div[contains(concat(' ', normalize-space(@class), ' '), ' prices ')]" .
            "//span[@id='price' and @data-qa-ta='verPrice']",

            // 3) Último recurso: cualquier span#price bajo .prices dentro de product-profile
            "//*[@id='product-profile']" .
            "//div[contains(concat(' ', normalize-space(@class), ' '), ' prices ')]" .
            "//span[@id='price']",
        ];

        foreach ($queries as $q) {
            $nodes = $xp->query($q);
            if (!$nodes || $nodes->length === 0) {
                continue;
            }

            // Tomar el primer match dentro del bloque principal encontrado
            /** @var \DOMElement $node */
            $node = $nodes->item(0);

            $raw = trim((string) $node->getAttribute('data-price'));
            if ($raw === '') {
                // Si no hay data-price, leer el contenido textual del span
                $raw = trim((string) $node->textContent);
            }

            $precio = $this->aNumero($raw);
            if ($precio !== null) {
                return $precio;
            }
        }

        return null;
    }
    
        /* ===================== Detección de Problemas de Disponibilidad ===================== */

    private function detectarProblemasDisponibilidad(string $html, $oferta): void
    {
        if (!$oferta || !($oferta instanceof OfertaProducto)) {
            return;
        }

        // Detectar "Producto agotado temporalmente"
        if (strpos($html, 'data-qa-ta="alertSoldOut') !== false) {
            Log::info('PromofarmaController - SIN STOCK DETECTADO:', [
                'oferta_id' => $oferta->id,
                'oferta_tipo' => get_class($oferta)
            ]);
            
            // Actualizar oferta para no mostrar
            $oferta->update(['mostrar' => 'no']);
            
            // Crear aviso con fecha a una semana vista
            $avisoId = DB::table('avisos')->insertGetId([
                'texto_aviso'     => 'SIN STOCK PROMOFARMA 1A VEZ - GENERADO AUTOMÁTICAMENTE',
                'fecha_aviso'     => now()->addDay(), // Una semana vista
                'user_id'         => 1,                 // usuario sistema
                'avisoable_type'  => \App\Models\OfertaProducto::class,
                'avisoable_id'    => $oferta->id,
                'oculto'          => 0,                 // visible
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
            
            Log::info('PromofarmaController - Aviso sin stock creado:', [
                'aviso_id' => $avisoId,
                'oferta_id' => $oferta->id
            ]);
        }
    }

    /**
     * Normaliza "25,99€" | "1.234,56" | "22.09" -> float (punto decimal).
     */
    private function aNumero($raw): ?float
    {
        if (!is_string($raw) && !is_numeric($raw)) return null;

        // Mantener sólo dígitos y separadores , .
        $s = preg_replace('~[^0-9.,]~u', '', (string) $raw);
        if ($s === '' || $s === null) return null;

        // Si aparecen ambos separadores, asumir miles europeos (1.234,56): quitar puntos de miles
        if (strpos($s, ',') !== false && strpos($s, '.') !== false) {
            $s = str_replace('.', '', $s);
        }

        // Coma decimal -> punto
        $s = str_replace(',', '.', $s);

        return is_numeric($s) ? (float) $s : null;
    }
}
