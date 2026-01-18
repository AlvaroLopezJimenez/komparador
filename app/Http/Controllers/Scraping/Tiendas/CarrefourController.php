<?php

namespace App\Http\Controllers\Scraping\Tiendas;

use App\Http\Controllers\Scraping\Tiendas\PlantillaTiendaController;
use Illuminate\Http\JsonResponse;

class CarrefourController extends PlantillaTiendaController
{
    /**
     * Extrae el precio de Carrefour.
     *
     * PDP (R-XXXX/p) — SOLO fuentes del producto principal:
     *   1) buybox__price (texto) o data-price/data-amount en el contenedor buybox.
     *   2) <meta itemprop="price"> (schema.org en PDP).
     *   3) JSON-LD Product → offers.price.
     *   4) JSON embebido que referencie la URL /R-XXXX/p → app_price | price.
     *   5) Scripts de estado (window.__*, dataLayer, etc.) que contengan /R-XXXX/p → app_price | price.
     *
     * PLP (listado):
     *   1) Tarjeta cuyo <a href> apunta a /R-XXXX/p → .product-card__price.
     *   2) JSON embebido con esa URL → app_price | price.
     *   3) Nodo con data-origin="list" y app_price.
     *   4) Container cercano / global .product-card__price.
     */
    public function obtenerPrecio($url, $variante = null, $tienda = null, $oferta = null): JsonResponse
    {
        // Forzar canónica /p
        if (!preg_match('~/(p|pd)$~i', rtrim($url, '/'))) {
            $url = rtrim($url, '/') . '/p';
        }

        $resultado = $this->apiHTML->obtenerHTML($url, null, $tienda ? $tienda->api : null);
        if (!is_array($resultado) || empty($resultado['success']) || empty($resultado['html'])) {
            $msg = is_array($resultado) ? ($resultado['error'] ?? 'Error desconocido') : 'Respuesta inválida de la API';
            return response()->json(['success' => false, 'error' => 'No se pudo obtener el HTML de Carrefour: ' . $msg]);
        }

        $html = html_entity_decode((string)$resultado['html'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $esPdp = $this->esPdp($html, $url);

        if ($esPdp) {
            // === MODO PDP (sin capturar precios de listado/cross-sell) ===
            $precio = $this->extraerBuyboxPrice($html);
            if ($precio === null) $precio = $this->extraerMetaPrice($html);
            if ($precio === null) $precio = $this->extraerPrecioDesdeJsonLdProduct($html);
            if ($precio === null) $precio = $this->extraerPrecioDesdeJsonPorCodigo($html, $url);
            if ($precio === null) $precio = $this->extraerPrecioDesdeScriptsEstado($html, $url);
            if ($precio === null) $precio = $this->extraerPrecioDesdeOffersJson($html);

            if ($precio === null) {
                return response()->json(['success' => false, 'error' => 'No se encontró el precio en PDP.']);
            }

            // Detectar ofertas especiales (solo detectar, no modificar precios)
            if ($oferta) {
                $this->detectarYGuardarDescuentos($html, $oferta);
            }

            return response()->json(['success' => true, 'precio' => $precio]);
        }

        // === MODO PLP ===
        $precio = $this->extraerPrecioDeTarjetaPorCodigo($html, $url);
        if ($precio === null) $precio = $this->extraerPrecioDesdeJsonPorCodigo($html, $url);
        if ($precio === null) $precio = $this->extraerAppPriceListAttr($html);
        if ($precio === null) $precio = $this->extraerPrecioDesdeContainer($html);
        if ($precio === null) $precio = $this->extraerDosPrimerosProductCardGlobal($html);
        if ($precio === null) $precio = $this->extraerPrecioDesdeOffersJson($html);

        if ($precio === null) {
            return response()->json(['success' => false, 'error' => 'No se encontró el precio en PLP.']);
        }

        // Detectar ofertas especiales en PLP también (solo detectar, no modificar precios)
        if ($oferta) {
            $this->detectarYGuardarDescuentos($html, $oferta);
        }

        return response()->json(['success' => true, 'precio' => $precio]);
    }

    /** Detecta PDP por URL /R-XXXX/p o por <link rel="canonical"> hacia /R-XXXX/p */
    private function esPdp(string $html, string $url): bool
    {
        if (preg_match('~/R-[^/]+/p$~i', rtrim($url, '/'))) return true;
        if (preg_match('~<link[^>]+rel=(["\'])canonical\1[^>]+href=(["\'])([^"\']*/R-[^/\']*/p)\2~i', $html)) return true;
        return false;
    }

    /** PDP: buybox (texto visible o atributos data-*) */
    private function extraerBuyboxPrice(string $html): ?float
    {
        // Buscar dos buybox__price seguidos y devolver el menor
        $regexSeguidos = '~<span[^>]*class=("|\')[^"\']*buybox__price[^"\']*\1[^>]*>\s*([0-9\s\.\,]+)\s*(?:€|&euro;)?\s*</span>\s*<span[^>]*class=("|\')[^"\']*buybox__price[^"\']*\3[^>]*>\s*([0-9\s\.\,]+)\s*(?:€|&euro;)?\s*</span>~si';
        if (preg_match($regexSeguidos, $html, $m)) {
            $n1 = $this->normalizarImporte($m[2]);
            $n2 = $this->normalizarImporte($m[4]);
            if ($n1 !== null && $n2 !== null) {
                return min($n1, $n2);
            } elseif ($n1 !== null) {
                return $n1;
            } elseif ($n2 !== null) {
                return $n2;
            }
        }

        // Texto visible en .buybox__price (permite espacios/decimales)
        if (preg_match('~<span[^>]*class=(["\'])[^"\']*buybox__price[^"\']*\1[^>]*>\s*([0-9\s\.\,]+)\s*(?:€|&euro;)?\s*</span>~si', $html, $m) && !empty($m[2])) {
            $n = $this->normalizarImporte($m[2]);
            if ($n !== null) return $n;
        }
        // Atributos data- en el contenedor buybox
        if (preg_match('~<[^>]*class=(["\'])[^"\']*buybox[^"\']*\1[^>]*\bdata-(?:price|amount)=(["\'])(.*?)\2~si', $html, $m2)) {
            $n = $this->normalizarImporte($m2[3]);
            if ($n !== null) return $n;
        }
        // Variantes de nombre de clase que Carrefour usa a veces (ej. buybox-price)
        if (preg_match('~<span[^>]*class=(["\'])[^"\']*buybox[-_]?price[^"\']*\1[^>]*>\s*([0-9\s\.\,]+)\s*(?:€|&euro;)?\s*</span>~si', $html, $m3) && !empty($m3[2])) {
            $n = $this->normalizarImporte($m3[2]);
            if ($n !== null) return $n;
        }
        return null;
    }

    /** PDP: <meta itemprop="price" content="29.99"> o variantes de schema.org */
    private function extraerMetaPrice(string $html): ?float
    {
        if (preg_match('~<meta[^>]+itemprop=(["\'])price\1[^>]+content=(["\'])([^"\']+)\2~i', $html, $m)) {
            $n = $this->normalizarImporte($m[3]);
            if ($n !== null) return $n;
        }
        // priceCurrency + content visible en meta alternativos
        if (preg_match('~<meta[^>]+property=(["\'])product:price:amount\1[^>]+content=(["\'])([^"\']+)\2~i', $html, $m2)) {
            $n = $this->normalizarImporte($m2[3]);
            if ($n !== null) return $n;
        }
        return null;
    }

    /** PDP: JSON-LD Product → offers.price */
    private function extraerPrecioDesdeJsonLdProduct(string $html): ?float
    {
        if (preg_match_all('~<script[^>]+type=(["\'])application/ld\+json\1[^>]*>(.*?)</script>~si', $html, $blocks)) {
            foreach ($blocks[2] as $json) {
                $data = json_decode(trim($json), true);
                if (!is_array($data)) continue;
                $objs = isset($data[0]) ? $data : [$data];

                foreach ($objs as $obj) {
                    if (!is_array($obj)) continue;

                    // @graph → buscar Product dentro del grafo
                    if (isset($obj['@graph']) && is_array($obj['@graph'])) {
                        foreach ($obj['@graph'] as $node) {
                            if (is_array($node) && ($node['@type'] ?? null) === 'Product') {
                                $n = $this->leerPrecioDeOffers($node['offers'] ?? null);
                                if ($n !== null) return $n;
                            }
                        }
                        continue;
                    }

                    if (($obj['@type'] ?? null) === 'Product') {
                        $n = $this->leerPrecioDeOffers($obj['offers'] ?? null);
                        if ($n !== null) return $n;
                    }
                }
            }
        }
        return null;
    }

    /** Aux: obtiene price de offers (objeto o array) */
    private function leerPrecioDeOffers($offers): ?float
    {
        if (!$offers) return null;

        if (isset($offers['price'])) {
            $n = $this->normalizarImporte((string)$offers['price']);
            if ($n !== null) return $n;
        }
        if (is_array($offers)) {
            foreach ($offers as $of) {
                if (!is_array($of)) continue;
                if (isset($of['price'])) {
                    $n = $this->normalizarImporte((string)$of['price']);
                    if ($n !== null) return $n;
                }
            }
        }
        return null;
    }

    /** JSON embebido (PDP/PLP): objeto cuyo "url" contiene /R-XXXX/p → app_price | price */
    private function extraerPrecioDesdeJsonPorCodigo(string $html, string $url): ?float
    {
        if (!preg_match('~/((R-[^/]+))/p~i', $url, $m)) return null;
        $codigo = $m[1];

        // Objeto compacto con "url":".../R-XXXX/p"
        if (preg_match('~\{[^{}]*"url"\s*:\s*"[^"]*/' . preg_quote($codigo, '~') . '/p"[^{}]*\}~si', $html, $objMatch)) {
            $obj = $objMatch[0];
            if (preg_match('~"app_price"\s*:\s*"([^"]+)"~i', $obj, $pm)) {
                $n = $this->normalizarImporte($pm[1]); if ($n !== null) return $n;
            }
            if (preg_match('~"price"\s*:\s*"([^"]+)"~i', $obj, $pm2)) {
                $n = $this->normalizarImporte($pm2[1]); if ($n !== null) return $n;
            }
        }

        // Arrays JSON: escanear bloques más amplios si no se capturó en una sola llave
        if (preg_match_all('~\{[^{}]+\}~si', $html, $objs)) {
            foreach ($objs[0] as $obj) {
                if (stripos($obj, "/$codigo/p") === false) continue;
                if (preg_match('~"app_price"\s*:\s*"([^"]+)"~i', $obj, $pm)) {
                    $n = $this->normalizarImporte($pm[1]); if ($n !== null) return $n;
                }
                if (preg_match('~"price"\s*:\s*"([^"]+)"~i', $obj, $pm2)) {
                    $n = $this->normalizarImporte($pm2[1]); if ($n !== null) return $n;
                }
            }
        }
        return null;
    }

    /** PDP: scripts de estado que referencien la URL /R-XXXX/p → app_price | price */
    private function extraerPrecioDesdeScriptsEstado(string $html, string $url): ?float
    {
        if (!preg_match('~/((R-[^/]+))/p~i', $url, $m)) return null;
        $codigo = preg_quote($m[1], '~');

        if (preg_match_all('~<script\b[^>]*>(.*?)</script>~si', $html, $scripts)) {
            foreach ($scripts[1] as $js) {
                if (!preg_match('~/'.$codigo.'/p~i', $js)) continue;
                if (preg_match('~"app_price"\s*:\s*"([^"]+)"~i', $js, $m1)) {
                    $n = $this->normalizarImporte($m1[1]); if ($n !== null) return $n;
                }
                if (preg_match('~"price"\s*:\s*"([^"]+)"~i', $js, $m2)) {
                    $n = $this->normalizarImporte($m2[1]); if ($n !== null) return $n;
                }
                if (preg_match('~\bprice\s*[:=]\s*(["\']?)([0-9\.,\s]+)\1~i', $js, $m3)) {
                    $n = $this->normalizarImporte($m3[2]); if ($n !== null) return $n;
                }
            }
        }
        return null;
    }

    /** PLP: tarjeta por código → .product-card__price */
    private function extraerPrecioDeTarjetaPorCodigo(string $html, string $url): ?float
    {
        if (!preg_match('~/((R-[^/]+))/p~i', $url, $m)) return null;
        $codigo = preg_quote($m[1], '~');

        $regex = '~<div[^>]*class=(["\'])[^\1>]*product-card__info-container[^\1>]*\1[^>]*>.*?' .
                 '<a[^>]*href=(["\'])[^\2"]*\/' . $codigo . '\/p\2[^>]*>.*?<\/a>.*?' .
                 '<div[^>]*class=(["\'])[^\3>]*product-card__prices-container[^\3>]*\3[^>]*>.*?' .
                 '<span[^>]*class=(["\'])[^\4>]*product-card__price[^\4>]*\4[^>]*>\s*([\d\s\.,]+)\s*(?:€|&euro;)?\s*<\/span>' .
                 '~si';
        if (preg_match($regex, $html, $mm)) {
            return $this->normalizarImporte($mm[5]);
        }
        return null;
    }

    /** PLP: data-origin="list" app_price="29,99 €" */
    private function extraerAppPriceListAttr(string $html): ?float
    {
        // Acepta comillas simples/dobles y espacios/entidades dentro del precio
        $regex = '~<[^>]*\bdata-origin=(["\'])list\1[^>]*\bapp_price=(["\'])([^"\']+)\2[^>]*>~si';
        if (preg_match($regex, $html, $m) && isset($m[3])) {
            return $this->normalizarImporte($m[3]);
        }
        return null;
    }

    /** PLP: container cercano con dos primeros precios → menor */
    private function extraerPrecioDesdeContainer(string $html): ?float
    {
        $regexContainerOpen = '~<div[^>]*class=(["\'])[^"\']*product-card__prices-container[^"\']*\1[^>]*>~si';
        if (!preg_match($regexContainerOpen, $html, $m, PREG_OFFSET_CAPTURE)) {
            return null;
        }
        $start = $m[0][1];
        $slice = substr($html, $start, 8000);
        $dos = $this->dosPrimerosPreciosEnHtml($slice);
        return $dos ? min($dos) : null;
    }

    /** PLP: global .product-card__price → menor de los 2 primeros */
    private function extraerDosPrimerosProductCardGlobal(string $html): ?float
    {
        $regexPrice = '~<span[^>]*class=(["\'])[^"\']*product-card__price[^"\']*\1[^>]*>\s*([\d\s\.\,]+)\s*(?:€|&euro;)?\s*</span>~si';
        preg_match_all($regexPrice, $html, $matches);
        if (empty($matches[2]) || count($matches[2]) < 2) return null;

        $precios = [];
        foreach (array_slice($matches[2], 0, 2) as $crudo) {
            $n = $this->normalizarImporte($crudo);
            if ($n !== null) $precios[] = $n;
        }
        return count($precios) >= 2 ? $precios : null;
    }

    /** Extrae precio desde JSON embebido con patrón "offers":{"price":"9.38"," */
    private function extraerPrecioDesdeOffersJson(string $html): ?float
    {
        // Buscar el patrón "offers":{"price":"[cualquier número], en el HTML
        if (preg_match('~"offers"\s*:\s*\{\s*"price"\s*:\s*"([0-9\.,\s]+)",~i', $html, $m)) {
            $n = $this->normalizarImporte($m[1]);
            if ($n !== null) return $n;
        }
        
        return null;
    }

    /** Detecta ofertas 3x2 en el HTML */
    private function detectarOferta3x2(string $html): bool
    {
        // Buscar title="3x2" en el HTML
        return (bool) preg_match('/title\s*=\s*["\']3x2["\']/i', $html);
    }

    /** Detecta ofertas 2x1 Acumula en el HTML */
    private function detectarOferta2x1(string $html): bool
    {
        // Buscar title="2x1 Acumula" en el HTML
        return (bool) preg_match('/title\s*=\s*["\']2x1\s+Acumula["\']/i', $html);
    }

    /** Detecta ofertas 2ª unidad al 70% en el HTML */
    private function detectarOferta2aUnidad70(string $html): bool
    {
        // Buscar "unidad -70" en cualquier parte del HTML (sin restricción de atributo title ni símbolo %)
        return (bool) preg_match('/unidad\s*-70/i', $html);
    }

    /** Detecta y guarda los descuentos en la oferta sin modificar precios */
    private function detectarYGuardarDescuentos(string $html, $oferta): void
    {
        if (!$oferta || !($oferta instanceof \App\Models\OfertaProducto)) {
            return;
        }

        $tieneOferta3x2 = $this->detectarOferta3x2($html);
        $tieneOferta2x1 = $this->detectarOferta2x1($html);
        $tieneOferta2a70 = $this->detectarOferta2aUnidad70($html);
        $descuentoAnterior = $oferta->descuentos;
        $descuentoNuevo = null;

        // Determinar qué descuento aplicar (prioridad 3x2 > 2x1 > 2a al 70)
        if ($tieneOferta3x2) {
            $descuentoNuevo = '3x2';
        } elseif ($tieneOferta2x1) {
            $descuentoNuevo = '2x1 - SoloCarrefour';
        } elseif ($tieneOferta2a70) {
            $descuentoNuevo = '2a al 70';
        }

        // Si hay descuento nuevo
        if ($descuentoNuevo !== null) {
            \Log::info('CarrefourController - DESCUENTO DETECTADO:', [
                'oferta_id' => $oferta->id,
                'descuento_nuevo' => $descuentoNuevo,
                'descuento_anterior' => $descuentoAnterior
            ]);

            // Actualizar el campo descuentos de la oferta
            $oferta->update(['descuentos' => $descuentoNuevo]);

            // Solo crear aviso si el descuento es nuevo o ha cambiado
            if ($descuentoAnterior !== $descuentoNuevo) {
                $textoAviso = match($descuentoNuevo) {
                    '3x2' => 'DETECTADO DESCUENTO 3X2 - GENERADO AUTOMÁTICAMENTE',
                    '2x1 - SoloCarrefour' => 'DETECTADO DESCUENTO 2X1 ACUMULA - GENERADO AUTOMÁTICAMENTE',
                    '2a al 70' => 'DETECTADO DESCUENTO 2ª UNIDAD AL 70% - GENERADO AUTOMÁTICAMENTE',
                    default => 'DETECTADO DESCUENTO - GENERADO AUTOMÁTICAMENTE'
                };

                $avisoId = \DB::table('avisos')->insertGetId([
                    'texto_aviso'     => $textoAviso,
                    'fecha_aviso'     => now(),
                    'user_id'         => 1,
                    'avisoable_type'  => \App\Models\OfertaProducto::class,
                    'avisoable_id'    => $oferta->id,
                    'oculto'          => 0,
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ]);

                \Log::info('CarrefourController - Aviso descuento creado:', [
                    'aviso_id' => $avisoId,
                    'oferta_id' => $oferta->id,
                    'descuento' => $descuentoNuevo
                ]);
            }
        } else {
            // Si no hay descuentos pero la oferta tenía descuentos de Carrefour, limpiar el campo
            if ($descuentoAnterior === '3x2' || $descuentoAnterior === '2x1 - SoloCarrefour' || $descuentoAnterior === '2a al 70') {
                \Log::info('CarrefourController - Descuentos ya no disponibles, limpiando campo descuentos:', [
                    'oferta_id' => $oferta->id,
                    'descuentos_anterior' => $descuentoAnterior
                ]);

                $oferta->update(['descuentos' => null]);

                \Log::info('CarrefourController - Campo descuentos limpiado:', [
                    'oferta_id' => $oferta->id
                ]);
            }
        }
    }

    /** Normaliza "1.234,56 €" / "23,60" / "23.60" a float */
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
