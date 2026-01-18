<?php

namespace App\Http\Controllers\Scraping\Tiendas;

use App\Http\Controllers\Scraping\Tiendas\PlantillaTiendaController;
use Illuminate\Http\JsonResponse;

class AminhafarmaciaController extends PlantillaTiendaController
{
    /**
     * Extrae el precio de Aminhafarmacia.
     *
     * El precio se encuentra en:
     *   1) <span class="money">21,99€</span>
     *   2) <span class="price-current badge rounded-pill text-bg-success">€21,99</span>
     */
    public function obtenerPrecio($url, $variante = null, $tienda = null, $oferta = null): JsonResponse
    {
        $resultado = $this->apiHTML->obtenerHTML($url, null, $tienda ? $tienda->api : null);
        if (!is_array($resultado) || empty($resultado['success']) || empty($resultado['html'])) {
            $msg = is_array($resultado) ? ($resultado['error'] ?? 'Error desconocido') : 'Respuesta inválida de la API';
            return response()->json(['success' => false, 'error' => 'No se pudo obtener el HTML de Aminhafarmacia: ' . $msg]);
        }

        $html = html_entity_decode((string)$resultado['html'], ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Intentar extraer el precio de los dos lugares posibles
        $precio = $this->extraerPrecioDesdeMoney($html);
        if ($precio === null) {
            $precio = $this->extraerPrecioDesdePriceCurrent($html);
        }

        if ($precio === null) {
            return response()->json(['success' => false, 'error' => 'No se encontró el precio en la página.']);
        }

        return response()->json(['success' => true, 'precio' => $precio]);
    }

    /**
     * Extrae el precio desde <span class="money">21,99€</span>
     */
    private function extraerPrecioDesdeMoney(string $html): ?float
    {
        // Buscar <span class="money"> con el precio dentro (puede tener espacios y el símbolo €)
        if (preg_match('~<span[^>]*class=(["\'])money\1[^>]*>\s*([0-9\s\.\,]+)\s*(?:€|&euro;)?\s*</span>~si', $html, $m)) {
            $n = $this->normalizarImporte($m[2]);
            if ($n !== null) return $n;
        }
        return null;
    }

    /**
     * Extrae el precio desde <span class="price-current badge rounded-pill text-bg-success">€21,99</span>
     */
    private function extraerPrecioDesdePriceCurrent(string $html): ?float
    {
        // Buscar <span class="price-current" con el precio (puede tener € antes o después)
        if (preg_match('~<span[^>]*class=(["\'])[^"\']*price-current[^"\']*\1[^>]*>\s*(?:€|&euro;)?\s*([0-9\s\.\,]+)\s*(?:€|&euro;)?\s*</span>~si', $html, $m)) {
            $n = $this->normalizarImporte($m[2]);
            if ($n !== null) return $n;
        }
        return null;
    }

    /**
     * Normaliza "21,99" / "21.99" / "21,99 €" a float
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
}















