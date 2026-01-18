<?php

namespace App\Http\Controllers\Scraping\Tiendas;

use App\Http\Controllers\Scraping\Tiendas\PlantillaTiendaController;

class AlcampoController extends PlantillaTiendaController
{
    public function obtenerPrecio($url, $variante = null, $tienda = null)
    {
        $resultado = $this->apiHTML->obtenerHTML($url, null, $tienda ? $tienda->api : null);
        if (!$resultado['success']) {
            return response()->json([
                'success' => false,
                'error'   => $resultado['error'] ?? 'Error obteniendo HTML'
            ]);
        }

        $html = $resultado['html'];

        // 1) data-test="fop-price": coger el segundo si existe
        if (preg_match_all('/<span[^>]*data-test=["\']fop-price["\'][^>]*>\s*([^<%]+?)\s*<\/span>/i', $html, $matches)) {
            if (count($matches[1]) >= 2) {
                $n = $this->toNumber($matches[1][1]); // segundo match (índice 1)
                if ($n !== null) {
                    return response()->json(['success' => true, 'precio' => $n]);
                }
            } elseif (!empty($matches[1])) {
                $n = $this->toNumber($matches[1][0]);
                if ($n !== null) {
                    return response()->json(['success' => true, 'precio' => $n]);
                }
            }
        }

        // 2) clase _display_xy0eg_1
        if (preg_match_all('/<span[^>]*class=["\'][^"\']*\b_display_xy0eg_1\b[^"\']*["\'][^>]*>\s*([^<%]+?)\s*<\/span>/i', $html, $matches2)) {
            if (count($matches2[1]) >= 2) {
                $n = $this->toNumber($matches2[1][1]); // segundo
                if ($n !== null) {
                    return response()->json(['success' => true, 'precio' => $n]);
                }
            } elseif (!empty($matches2[1])) {
                $n = $this->toNumber($matches2[1][0]);
                if ($n !== null) {
                    return response()->json(['success' => true, 'precio' => $n]);
                }
            }
        }

        // 3) Contexto "Precio" + span
        if (preg_match_all('/Precio<\/span>\s*<span[^>]*>\s*([^<%]+?)\s*<\/span>/i', $html, $matches3)) {
            if (count($matches3[1]) >= 2) {
                $n = $this->toNumber($matches3[1][1]); // segundo
                if ($n !== null) {
                    return response()->json(['success' => true, 'precio' => $n]);
                }
            } elseif (!empty($matches3[1])) {
                $n = $this->toNumber($matches3[1][0]);
                if ($n !== null) {
                    return response()->json(['success' => true, 'precio' => $n]);
                }
            }
        }

        // 4) Fallback genérico
        if (preg_match_all('/<span[^>]*>\s*([\d][\d\.\,]*[.,]\d{1,2})\s*(?:€|&euro;|&nbsp;€)?\s*<\/span>/i', $html, $matches4)) {
            if (count($matches4[1]) >= 2) {
                $txt = trim($matches4[1][1]);
                if (strpos($txt, '%') === false) {
                    $n = $this->toNumber($txt);
                    if ($n !== null) {
                        return response()->json(['success' => true, 'precio' => $n]);
                    }
                }
            } elseif (!empty($matches4[1])) {
                $txt = trim($matches4[1][0]);
                if (strpos($txt, '%') === false) {
                    $n = $this->toNumber($txt);
                    if ($n !== null) {
                        return response()->json(['success' => true, 'precio' => $n]);
                    }
                }
            }
        }

        return response()->json([
            'success' => false,
            'error'   => 'No se pudo encontrar el precio en la página de Alcampo'
        ]);
    }

    private function toNumber(string $raw): ?float
    {
        $txt = html_entity_decode($raw, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        if (strpos($txt, '%') !== false) return null;

        $txt = preg_replace('/[^\d.,]/u', '', $txt);
        if ($txt === '' || $txt === null) return null;

        if (strpos($txt, ',') === false && strpos($txt, '.') === false) {
            return (float) $txt;
        }

        $lastComma = strrpos($txt, ',');
        $lastDot   = strrpos($txt, '.');
        $decPos    = max($lastComma !== false ? $lastComma : -1, $lastDot !== false ? $lastDot : -1);

        $intPart = substr($txt, 0, $decPos);
        $decPart = substr($txt, $decPos + 1);

        $intPart = preg_replace('/[^\d]/', '', $intPart);
        $decPart = preg_replace('/[^\d]/', '', $decPart);

        if ($intPart === '') return null;

        $norm = $decPart === '' ? $intPart . '.00' : $intPart . '.' . substr($decPart, 0, 2);
        return (float) $norm;
    }
}


