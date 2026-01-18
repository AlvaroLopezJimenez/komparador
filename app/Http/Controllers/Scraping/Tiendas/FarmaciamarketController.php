<?php

namespace App\Http\Controllers\Scraping\Tiendas;

use App\Http\Controllers\Scraping\Tiendas\PlantillaTiendaController;
use Illuminate\Http\JsonResponse;

class FarmaciamarketController extends PlantillaTiendaController
{
    /**
     * Extrae el precio de Farmaciamarket.
     *
     * El precio se encuentra en:
     *   1) "price": "24.67" (JSON embebido)
     *   2) <meta content="24.67" property="product:price:amount"> (meta tag)
     *   3) <span class="current-price-value" content="24.67"> (atributo content)
     *   4) "price_amount":24.67 (JSON embebido)
     *   5) <span class="unit_price">24.67</span> (texto del span)
     */
    public function obtenerPrecio($url, $variante = null, $tienda = null, $oferta = null): JsonResponse
    {
        $resultado = $this->apiHTML->obtenerHTML($url, null, $tienda ? $tienda->api : null);
        if (!is_array($resultado) || empty($resultado['success']) || empty($resultado['html'])) {
            $msg = is_array($resultado) ? ($resultado['error'] ?? 'Error desconocido') : 'Respuesta inválida de la API';
            return response()->json(['success' => false, 'error' => 'No se pudo obtener el HTML de Farmaciamarket: ' . $msg]);
        }

        $html = html_entity_decode((string)$resultado['html'], ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Intentar extraer el precio de todos los lugares posibles (en orden de prioridad)
        $precio = $this->extraerPrecioDesdeJsonPrice($html);
        if ($precio === null) $precio = $this->extraerPrecioDesdeMetaPrice($html);
        if ($precio === null) $precio = $this->extraerPrecioDesdeCurrentPriceValue($html);
        if ($precio === null) $precio = $this->extraerPrecioDesdeJsonPriceAmount($html);
        if ($precio === null) $precio = $this->extraerPrecioDesdeUnitPrice($html);

        if ($precio === null) {
            return response()->json(['success' => false, 'error' => 'No se encontró el precio en la página.']);
        }

        return response()->json(['success' => true, 'precio' => $precio]);
    }

    /**
     * Extrae el precio desde "price": "24.67" (JSON embebido)
     */
    private function extraerPrecioDesdeJsonPrice(string $html): ?float
    {
        // Buscar "price": "24.67" o "price":"24.67" (con o sin espacios, con comillas)
        if (preg_match('~"price"\s*:\s*"([0-9]+(?:\.[0-9]+)?)"~i', $html, $m)) {
            $n = $this->normalizarImporte($m[1]);
            if ($n !== null) return $n;
        }
        // También buscar sin comillas: "price": 24.67
        if (preg_match('~"price"\s*:\s*([0-9]+(?:\.[0-9]+)?)~i', $html, $m)) {
            $n = $this->normalizarImporte($m[1]);
            if ($n !== null) return $n;
        }
        return null;
    }

    /**
     * Extrae el precio desde <meta content="24.67" property="product:price:amount">
     */
    private function extraerPrecioDesdeMetaPrice(string $html): ?float
    {
        // Buscar meta tag con property="product:price:amount" y content con el precio
        if (preg_match('~<meta[^>]+property=(["\'])product:price:amount\1[^>]+content=(["\'])([0-9]+(?:\.[0-9]+)?)\2~i', $html, $m)) {
            $n = $this->normalizarImporte($m[3]);
            if ($n !== null) return $n;
        }
        // También buscar en orden inverso: content primero, property después
        if (preg_match('~<meta[^>]+content=(["\'])([0-9]+(?:\.[0-9]+)?)\1[^>]+property=(["\'])product:price:amount\3~i', $html, $m)) {
            $n = $this->normalizarImporte($m[2]);
            if ($n !== null) return $n;
        }
        return null;
    }

    /**
     * Extrae el precio desde <span class="current-price-value" content="24.67">
     */
    private function extraerPrecioDesdeCurrentPriceValue(string $html): ?float
    {
        // Buscar span con class="current-price-value" y atributo content
        if (preg_match('~<span[^>]*class=(["\'])[^"\']*current-price-value[^"\']*\1[^>]*\bcontent=(["\'])([0-9]+(?:\.[0-9]+)?)\2~si', $html, $m)) {
            $n = $this->normalizarImporte($m[3]);
            if ($n !== null) return $n;
        }
        // También buscar el precio en el texto interno del span
        if (preg_match('~<span[^>]*class=(["\'])[^"\']*current-price-value[^"\']*\1[^>]*>\s*([0-9\s\.\,]+)\s*(?:€|&euro;)?\s*</span>~si', $html, $m)) {
            $n = $this->normalizarImporte($m[2]);
            if ($n !== null) return $n;
        }
        return null;
    }

    /**
     * Extrae el precio desde "price_amount":24.67 (JSON embebido)
     */
    private function extraerPrecioDesdeJsonPriceAmount(string $html): ?float
    {
        // Buscar "price_amount":24.67 o "price_amount": "24.67"
        if (preg_match('~"price_amount"\s*:\s*"?([0-9]+(?:\.[0-9]+)?)"?~i', $html, $m)) {
            $n = $this->normalizarImporte($m[1]);
            if ($n !== null) return $n;
        }
        return null;
    }

    /**
     * Extrae el precio desde <span class="unit_price">24.67</span>
     */
    private function extraerPrecioDesdeUnitPrice(string $html): ?float
    {
        // Buscar <span class="unit_price"> con el precio dentro
        if (preg_match('~<span[^>]*class=(["\'])[^"\']*unit_price[^"\']*\1[^>]*>\s*([0-9]+(?:\.[0-9]+)?)\s*</span>~si', $html, $m)) {
            $n = $this->normalizarImporte($m[2]);
            if ($n !== null) return $n;
        }
        return null;
    }

    /**
     * Normaliza "24.67" / "24,67" / "24.67 €" a float
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















