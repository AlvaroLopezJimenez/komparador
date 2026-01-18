<?php

namespace App\Http\Controllers\Scraping\Tiendas;

use App\Http\Controllers\Scraping\PlantillaTiendaController;

/**
 * Controlador de scraping para Primor
 * 
 * Este es un ejemplo de cómo implementar el scraping específico de una tienda.
 * Cada tienda puede tener diferentes estructuras HTML, por lo que necesitarás
 * ajustar los selectores y patrones según la tienda específica.
 */
class PrimorController extends PlantillaTiendaController
{
    public function obtenerPrecio($url, $variante = null, $tienda = null)
    {
        // Obtener HTML de la página
        $resultado = $this->apiHTML->obtenerHTML($url, null, $tienda ? $tienda->api : null);
        
        if (!$resultado['success']) {
            return response()->json([
                'success' => false,
                'error' => $resultado['error']
            ]);
        }
        
        $html = $resultado['html'];
        
        // PATRONES ESPECÍFICOS PARA PRIMOR
        // Estos patrones deben ajustarse según la estructura real de la página de Primor
        
        $patrones = [
            // Buscar precio en formato "12,99 €"
            '/class="[^"]*price[^"]*"[^>]*>([0-9,]+)\s*€/i',
            // Buscar precio en atributo data-price
            '/data-price="([0-9,]+)"/',
            // Buscar precio en span con clase específica
            '/<span[^>]*class="[^"]*precio[^"]*"[^>]*>([0-9,]+)/i',
            // Buscar precio en div con clase price
            '/<div[^>]*class="[^"]*price[^"]*"[^>]*>([0-9,]+)/i',
            // Buscar precio genérico con símbolo de euro
            '/>([0-9,]+)\s*€/',
            // Buscar precio en formato decimal
            '/class="[^"]*price[^"]*"[^>]*>([0-9]+[.,][0-9]+)/i'
        ];
        
        $precio = null;
        foreach ($patrones as $patron) {
            if (preg_match($patron, $html, $matches)) {
                $precio = $matches[1];
                break;
            }
        }
        
        // Si no se encontró con regex, intentar con DOM
        if (!$precio) {
            $precio = $this->extraerPrecioConDOM($html);
        }
        
        if (!$precio) {
            return response()->json([
                'success' => false,
                'error' => 'No se pudo encontrar el precio en la página de Primor. Revisa los selectores.'
            ]);
        }
        
        // Normalizar precio
        $precio = str_replace(',', '.', $precio);
        $precio = preg_replace('/[^0-9.]/', '', $precio);
        
        // Validar que el precio es numérico y razonable
        if (!is_numeric($precio) || $precio <= 0 || $precio > 1000) {
            return response()->json([
                'success' => false,
                'error' => 'Precio extraído no válido: ' . $precio
            ]);
        }
        
        return response()->json([
            'success' => true,
            'precio' => (float) $precio
        ]);
    }
    
    /**
     * Método alternativo usando DOM para extraer precio
     */
    private function extraerPrecioConDOM($html)
    {
        try {
            $dom = new \DOMDocument();
            @$dom->loadHTML($html);
            $xpath = new \DOMXPath($dom);
            
            // Selectores específicos para Primor
            $selectores = [
                '//span[@class="price"]',
                '//div[@class="price"]',
                '//span[contains(@class, "precio")]',
                '//div[contains(@class, "precio")]',
                '//span[@data-price]',
                '//div[@data-price]',
                '//span[contains(@class, "amount")]',
                '//div[contains(@class, "amount")]'
            ];
            
            foreach ($selectores as $selector) {
                $elementos = $xpath->query($selector);
                if ($elementos->length > 0) {
                    $texto = trim($elementos->item(0)->textContent);
                    if (preg_match('/([0-9,]+)/', $texto, $matches)) {
                        return $matches[1];
                    }
                }
            }
            
            return null;
            
        } catch (\Exception $e) {
            return null;
        }
    }
}


