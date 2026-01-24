<?php

namespace App\Http\Controllers\Scraping\Tiendas;

use App\Http\Controllers\Scraping\Tiendas\PlantillaTiendaController;

class PccomponentesController extends PlantillaTiendaController
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

        // Extraer precio desde los spans específicos de PC Componentes
        $precio = $this->precioDesdeSpansPDP($html);
        if ($precio !== null) {
            return response()->json(['success' => true, 'precio' => $precio]);
        }

        return response()->json([
            'success' => false,
            'error'   => 'No se pudo encontrar el precio en la página de PC Componentes'
        ]);
    }

    /**
     * Extrae el precio desde los spans con IDs:
     * - pdp-price-current-integer: parte entera (ej: 332)
     * - pdp-price-current-decimals: parte decimal con separador (ej: ,47)
     * 
     * Formato real (anidado):
     * <span id="pdp-price-current-integer">332<span id="pdp-price-current-decimals">,47</span></span>
     * 
     * Resultado: 332.47
     */
    private function precioDesdeSpansPDP(string $html): ?float
    {
        // Método 1: Extraer parte entera - texto directo antes del siguiente tag
        $parteEntera = null;
        // Buscar el span y capturar el texto inmediatamente después de >
        if (preg_match('/<span[^>]*id=["\']pdp-price-current-integer["\'][^>]*>([^<]*?)(?:<|$)/i', $html, $mEntera)) {
            $parteEntera = preg_replace('/[^\d]/', '', trim($mEntera[1]));
        }
        
        // Si no encontramos con el método 1, intentar capturar todo el contenido y extraer solo números
        if (empty($parteEntera)) {
            if (preg_match('/<span[^>]*id=["\']pdp-price-current-integer["\'][^>]*>([\s\S]*?)<\/span>/i', $html, $mEntera2)) {
                // Extraer solo los números del inicio (antes del span anidado de decimales)
                $contenido = $mEntera2[1];
                // Buscar números al inicio antes de cualquier tag
                if (preg_match('/^([\d]+)/', $contenido, $mNum)) {
                    $parteEntera = $mNum[1];
                }
            }
        }

        // Extraer parte decimal: capturar todo el contenido del span (puede tener spans anidados)
        $parteDecimal = null;
        if (preg_match('/<span[^>]*id=["\']pdp-price-current-decimals["\'][^>]*>([\s\S]*?)<\/span>/i', $html, $mDecimal)) {
            // Extraer solo los números del contenido (puede tener spans, comentarios, símbolos, etc.)
            $parteDecimal = preg_replace('/[^\d]/', '', $mDecimal[1]);
        }

        // Si tenemos parte entera, procesar
        if ($parteEntera !== null && $parteEntera !== '') {
            // Si tenemos decimales, combinarlos; si no, usar .00
            if ($parteDecimal !== null && $parteDecimal !== '') {
                $precioCompleto = $parteEntera . '.' . $parteDecimal;
            } else {
                $precioCompleto = $parteEntera . '.00';
            }
            
            $precio = (float) $precioCompleto;
            return $precio > 0 ? $precio : null;
        }

        return null;
    }
}

