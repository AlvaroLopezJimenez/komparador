<?php

namespace App\Http\Controllers\Scraping\Tiendas;

use App\Http\Controllers\Scraping\Tiendas\PlantillaTiendaController;
use Illuminate\Http\JsonResponse;

class ClarelController extends PlantillaTiendaController
{
    /**
     * Extrae el precio de Clarel.
     *
     * El precio se encuentra en:
     * <span class="unit_price">8.49</span>
     *
     * Se busca primero el precio como texto dentro del span con clase unit_price.
     * También se intenta buscar variantes con atributos data-* si existe.
     */
    public function obtenerPrecio($url, $variante = null, $tienda = null, $oferta = null): JsonResponse
    {
        $resultado = $this->apiHTML->obtenerHTML($url, null, $tienda ? $tienda->api : null);
        if (!is_array($resultado) || empty($resultado['success']) || empty($resultado['html'])) {
            $msg = is_array($resultado) ? ($resultado['error'] ?? 'Error desconocido') : 'Respuesta inválida de la API';
            return response()->json(['success' => false, 'error' => 'No se pudo obtener el HTML de Clarel: ' . $msg]);
        }

        $html = html_entity_decode((string)$resultado['html'], ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Intentar extraer el precio del span con clase unit_price
        $precio = $this->extraerPrecioUnitPrice($html);
        
        if ($precio === null) {
            return response()->json(['success' => false, 'error' => 'No se encontró el precio en Clarel.']);
        }

        return response()->json(['success' => true, 'precio' => $precio]);
    }

    /**
     * Extrae el precio del span con clase unit_price.
     * Busca: <span class="unit_price">8.49</span>
     */
    private function extraerPrecioUnitPrice(string $html): ?float
    {
        // Buscar el span con clase unit_price y el precio como texto interno
        if (preg_match('~<span[^>]*class=(["\'])[^"\']*unit_price[^"\']*\1[^>]*>\s*([0-9\s\.\,]+)\s*(?:€|&euro;)?\s*</span>~si', $html, $m) && !empty($m[2])) {
            $n = $this->normalizarImporte($m[2]);
            if ($n !== null) return $n;
        }

        // Variante: buscar con atributo data-price o data-amount si existe
        if (preg_match('~<span[^>]*class=(["\'])[^"\']*unit_price[^"\']*\1[^>]*\bdata-(?:price|amount)=(["\'])(.*?)\2~si', $html, $m2)) {
            $n = $this->normalizarImporte($m2[3]);
            if ($n !== null) return $n;
        }

        // Variante: buscar sin restricción de comillas exactas (flexible)
        if (preg_match('~<span[^>]*class=["\'][^"\']*unit_price[^"\']*["\'][^>]*>\s*([0-9\s\.\,]+)\s*~si', $html, $m3) && !empty($m3[1])) {
            $n = $this->normalizarImporte($m3[1]);
            if ($n !== null) return $n;
        }

        return null;
    }

    /**
     * Normaliza "1.234,56 €" / "8.49" / "23,60" a float.
     * Maneja diferentes formatos de números (punto o coma como separador decimal).
     */
    private function normalizarImporte(string $importe): ?float
    {
        // Limpia NBSP (&nbsp; y U+00A0/U+202F)
        $importe = str_replace(["\xc2\xa0", "\xe2\x80\xaf", '&nbsp;'], ' ', $importe);

        // Deja sólo dígitos y separadores
        $s = preg_replace('/[^\d\,\.]/u', '', $importe);
        if ($s === null || $s === '') return null;

        $tieneComa  = strpos($s, ',') !== false;
        $tienePunto = strpos($s, '.') !== false;

        if ($tieneComa && $tienePunto) {
            $lastComma = strrpos($s, ',');
            $lastDot   = strrpos($s, '.');
            if ($lastComma !== false && ($lastDot === false || $lastComma > $lastDot)) {
                // La coma es el separador decimal (formato español)
                $s = str_replace('.', '', $s);
                $s = str_replace(',', '.', $s);
            } else {
                // El punto es el separador decimal (formato inglés)
                $s = str_replace(',', '', $s);
            }
        } elseif ($tieneComa) {
            $s = str_replace(',', '.', $s);
        }

        if (!preg_match('/^\d+(\.\d+)?$/', $s)) return null;
        return (float)$s;
    }
}
