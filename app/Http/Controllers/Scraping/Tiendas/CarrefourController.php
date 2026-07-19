<?php

namespace App\Http\Controllers\Scraping\Tiendas;

use App\Http\Controllers\DescuentosController;
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

        // Sin stock: página de categoría (category-name__title) en lugar de PDP/PLP de producto
        if (str_contains($html, 'category-name__title') && $oferta && $oferta instanceof \App\Models\OfertaProducto) {
            $oferta->update(['mostrar' => 'no']);
            \DB::table('avisos')->insertGetId([
                'texto_aviso'     => 'sin stock 1a vez - GENERADO AUTOMATICAMENTE',
                'fecha_aviso'     => now()->addDays(4),
                'user_id'         => 1,
                'avisoable_type'  => \App\Models\OfertaProducto::class,
                'avisoable_id'    => $oferta->id,
                'oculto'          => 0,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
        }

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

    /** Detecta oferta "50% que vuelve" (cheque / 2ª al 50% - SoloCarrefour) en el HTML */
    private function detectarOferta50QueVuelve(string $html): bool
    {
        return (bool) preg_match('/50\s*%\s*que\s+vuelve/i', $html);
    }

    /** Detecta oferta Días Locos -20% en el HTML */
    private function detectarOferta20PorcientoDiasLocos(string $html): bool
    {
        // El modificador u es imprescindible: sin él, D[ií]as no coincide con "Días" en UTF-8
        $patrones = [
            // Badge PDP/PLP: title="-20% Días Locos" (mismo estilo que 3x2)
            '/title\s*=\s*(["\'])[-−]\s*20\s*%\s*D[ií]?as\s+Locos\1/iu',
            // Texto visible en badge
            '/[-−]\s*20\s*%\s*D[ií]?as\s+Locos/iu',
            // JSON embebido (__INITIAL_STATE__, badge_map, promotions)
            '/"name"\s*:\s*"[-−]20\s*%\s*D[ií]?as\s+Locos"/iu',
            // dataLayer: p_special_campaign con campaña 20-dias-locos del producto
            '/p_special_campaign\\?"\s*:\s*"[^"]*20-dias-locos/iu',
            // Enlace de campaña en badge PDP
            '/\/supermercado\/20-dias-locos\/\d+\/s/iu',
        ];

        foreach ($patrones as $patron) {
            if (preg_match($patron, $html)) {
                return true;
            }
        }

        return false;
    }

    /** Detecta oferta 20% en Cupón (Solo Carrefour) en el HTML */
    private function detectarOferta20CuponSoloCarrefour(string $html): bool
    {
        return (bool) preg_match('/title\s*=\s*(["\'])20%\s+en\s+Cup[oó]n\1/iu', $html)
            || (bool) preg_match('/20%\s+en\s+Cup[oó]n/iu', $html);
    }

    /** Detecta y guarda los descuentos en la oferta sin modificar precios */
    private function detectarYGuardarDescuentos(string $html, $oferta): void
    {
        if (!$oferta || !($oferta instanceof \App\Models\OfertaProducto)) {
            return;
        }

        $descuentosDetectados = [];
        if ($this->detectarOferta3x2($html)) {
            $descuentosDetectados[] = '3x2';
        }
        if ($this->detectarOferta2x1($html)) {
            $descuentosDetectados[] = '2x1 - SoloCarrefour';
        }
        if ($this->detectarOferta2aUnidad70($html)) {
            $descuentosDetectados[] = '2a al 70';
        }
        if ($this->detectarOferta50QueVuelve($html)) {
            $descuentosDetectados[] = '2a al 50 - cheque - SoloCarrefour';
        }
        if ($this->detectarOferta20PorcientoDiasLocos($html)) {
            $descuentosDetectados[] = DescuentosController::DESCUENTO_20_PORCIENTO;
        }
        if ($this->detectarOferta20CuponSoloCarrefour($html)) {
            $descuentosDetectados[] = DescuentosController::DESCUENTO_20_CUPON_CARREFOUR;
        }

        $descuentosAnteriores = DescuentosController::parseDescuentos($oferta->descuentos);
        $descuentosNoCarrefour = DescuentosController::filtrarDescuentosNoCarrefour($descuentosAnteriores);

        if ($descuentosDetectados !== []) {
            $descuentoNuevo = DescuentosController::joinDescuentos(array_merge($descuentosNoCarrefour, $descuentosDetectados));
        } else {
            $descuentoNuevo = DescuentosController::joinDescuentos($descuentosNoCarrefour);
        }

        $descuentoAnterior = $oferta->descuentos;

        if ($descuentoNuevo !== $descuentoAnterior) {
            \Log::info('CarrefourController - DESCUENTO DETECTADO:', [
                'oferta_id' => $oferta->id,
                'descuento_nuevo' => $descuentoNuevo,
                'descuento_anterior' => $descuentoAnterior,
                'descuentos_detectados' => $descuentosDetectados,
            ]);

            $oferta->update(['descuentos' => $descuentoNuevo]);

            $textosAviso = [
                '3x2' => 'DETECTADO DESCUENTO 3X2 - GENERADO AUTOMÁTICAMENTE',
                '2x1 - SoloCarrefour' => 'DETECTADO DESCUENTO 2X1 ACUMULA - GENERADO AUTOMÁTICAMENTE',
                '2a al 70' => 'DETECTADO DESCUENTO 2ª UNIDAD AL 70% - GENERADO AUTOMÁTICAMENTE',
                '2a al 50 - cheque - SoloCarrefour' => 'DETECTADO DESCUENTO 50% QUE VUELVE (2ª AL 50% - CHEQUE) - GENERADO AUTOMÁTICAMENTE',
                DescuentosController::DESCUENTO_20_PORCIENTO => 'DETECTADO DESCUENTO -20% DÍAS LOCOS - GENERADO AUTOMÁTICAMENTE',
                DescuentosController::DESCUENTO_20_CUPON_CARREFOUR => 'DETECTADO DESCUENTO -20% CUPÓN - GENERADO AUTOMÁTICAMENTE',
            ];

            foreach ($descuentosDetectados as $descuentoDetectado) {
                if (in_array($descuentoDetectado, $descuentosAnteriores, true)) {
                    continue;
                }

                $textoAviso = $textosAviso[$descuentoDetectado] ?? 'DETECTADO DESCUENTO - GENERADO AUTOMÁTICAMENTE';

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
                    'descuento' => $descuentoDetectado,
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

    // -------------------------------------------------------------------------
    // Cron Neo Objetivos - listado de categoría por paginación (?offset=N, +24)
    // -------------------------------------------------------------------------

    private const OFFSET_PAGINACION_CATEGORIA = 24;

    public function tipoListadoCategoria(): ?string
    {
        return 'paginacion';
    }

    /**
     * PLP categoría Carrefour:
     * - Productos solo en <li class="product-card-list__item"> (excluye carrusel "Productos patrocinados")
     * - Paginación: página 1 sin offset; página 2 ?offset=24; página 3 ?offset=48; etc.
     * - Fin de paginación: bloque nav "Ofertas" (cat20968591.jpg + nav-first-level-categories__image)
     */
    public function extraerProductosYSiguientePagina(string $html, string $urlPeticionActual): array
    {
        $html = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        $urlsProductos = $this->extraerUrlsProductosDesdeListadoCategoria($html);
        $base = $this->obtenerBaseUrlDesdeUrlPeticion($urlPeticionActual);
        foreach ($urlsProductos as $i => $u) {
            $urlsProductos[$i] = $this->normalizarUrlCorta($u, $base);
        }
        $urlsProductos = array_values(array_unique(array_filter($urlsProductos)));

        $siguienteUrl = null;
        if (count($urlsProductos) > 0 && !$this->debeDetenerPaginacionCategoria($html)) {
            $siguienteUrl = $this->construirUrlSiguientePaginaOffset($urlPeticionActual);
        }

        return [
            'urls_productos' => $urlsProductos,
            'siguiente_url'  => $siguienteUrl,
        ];
    }

    /**
     * @return array<int, string>
     */
    private function extraerUrlsProductosDesdeListadoCategoria(string $html): array
    {
        $urlsPatrocinados = $this->extraerUrlsProductosPatrocinados($html);
        $htmlListado = $this->aislarHtmlGridListado($html);

        $urls = [];
        $urls = array_merge($urls, $this->extraerUrlsDesdeItemsListado($htmlListado));
        $urls = array_merge($urls, $this->extraerUrlsProductoDesdeJsonEmbutido($htmlListado));

        $urls = array_values(array_unique(array_filter($urls, function (string $u) use ($urlsPatrocinados) {
            if (!$this->esUrlProductoCarrefour($u)) {
                return false;
            }

            return !in_array($u, $urlsPatrocinados, true);
        })));

        return $urls;
    }

    /**
     * Aísla el grid principal quitando el carrusel "Productos patrocinados".
     */
    private function aislarHtmlGridListado(string $html): string
    {
        $html = $this->quitarBloquesCitrusCarousel($html);

        if (preg_match('~<ul[^>]*\bproduct-card-list\b[^>]*>([\s\S]*?)</ul>~i', $html, $m)) {
            return (string) $m[1];
        }

        return $html;
    }

    private function quitarBloquesCitrusCarousel(string $html): string
    {
        $patrones = [
            '~<h2[^>]*\bcitrus-carousel__title\b[^>]*>[\s\S]*?(?=<ul[^>]*\bproduct-card-list\b)~i',
            '~<div[^>]*\bcitrus-carousel\b[^>]*>[\s\S]*?(?=<ul[^>]*\bproduct-card-list\b)~i',
            '~<div[^>]*\bcitrus-carousel\b[^>]*>[\s\S]*?</div>\s*</div>\s*</div>\s*</div>~i',
        ];

        foreach ($patrones as $patron) {
            $reemplazo = preg_replace($patron, '', $html, 1);
            if (is_string($reemplazo)) {
                $html = $reemplazo;
            }
        }

        return $html;
    }

    /**
     * @return array<int, string>
     */
    private function extraerUrlsProductosPatrocinados(string $html): array
    {
        $urls = [];
        $bloques = [];

        if (preg_match('~<h2[^>]*\bcitrus-carousel__title\b[^>]*>([\s\S]*?)(?=<ul[^>]*\bproduct-card-list\b)~i', $html, $m)) {
            $bloques[] = (string) $m[1];
        }
        if (preg_match_all('~<div[^>]*\bcitrus-carousel\b[^>]*>([\s\S]*?)</div>\s*</div>\s*</div>~i', $html, $m2)) {
            foreach ($m2[1] as $bloque) {
                $bloques[] = (string) $bloque;
            }
        }

        foreach ($bloques as $bloque) {
            $urls = array_merge($urls, $this->extraerUrlsHrefProductoCarrefour($bloque));
        }

        return array_values(array_unique(array_filter($urls, fn (string $u) => $this->esUrlProductoCarrefour($u))));
    }

    /**
     * Recorre cada <li class="product-card-list__item"> por offsets (evita cortes prematuros con .*?</li>).
     *
     * @return array<int, string>
     */
    private function extraerUrlsDesdeItemsListado(string $html): array
    {
        $urls = [];

        if (!preg_match_all('~<li\b[^>]*\bproduct-card-list__item\b[^>]*>~i', $html, $opens, PREG_OFFSET_CAPTURE)) {
            return $urls;
        }

        $total = count($opens[0]);
        for ($i = 0; $i < $total; $i++) {
            $start = (int) $opens[0][$i][1];
            $end = ($i + 1 < $total) ? (int) $opens[0][$i + 1][1] : strlen($html);
            $bloque = substr($html, $start, $end - $start);

            if ($this->bloqueEsPatrocinado($bloque)) {
                continue;
            }

            $url = $this->extraerUrlProductoDesdeBloqueTarjeta($bloque);
            if ($url !== null) {
                $urls[] = $url;
            }
        }

        return $urls;
    }

    /**
     * URLs embebidas en JSON/scripts del listado (Bright Data sin JS a veces solo trae esto completo).
     *
     * @return array<int, string>
     */
    private function extraerUrlsProductoDesdeJsonEmbutido(string $html): array
    {
        return $this->extraerUrlsHrefProductoCarrefour($html);
    }

    /**
     * @return array<int, string>
     */
    private function extraerUrlsHrefProductoCarrefour(string $html): array
    {
        $urls = [];

        if (preg_match_all('~<a[^>]*\bclass=(["\'])[^"\']*product-card__(?:title|media)-link[^"\']*\1[^>]*\bhref=(["\'])(?<u>[^"\']+)\2~i', $html, $m)) {
            foreach ($m['u'] as $u) {
                $u = trim((string) $u);
                if ($u !== '') {
                    $urls[] = $u;
                }
            }
        }

        if (preg_match_all('~(?:href|url|link)\s*[:=]\s*(["\'])(?<u>(?:https?://(?:www\.)?carrefour\.es)?/supermercado/[^"\']*/R-[^/"\']+/p/?)\1~i', $html, $mJson)) {
            foreach ($mJson['u'] as $u) {
                $urls[] = trim((string) $u);
            }
        }

        if (preg_match_all('~(?<u>/supermercado/[^"\'\s<>]+/R-[^/"\'\s<>]+/p)~i', $html, $mPath)) {
            foreach ($mPath['u'] as $u) {
                $urls[] = trim((string) $u);
            }
        }

        return array_values(array_unique($urls));
    }

    private function bloqueEsPatrocinado(string $bloque): bool
    {
        if (
            str_contains($bloque, 'citrus-carousel')
            || str_contains($bloque, 'citrus-carousel__item')
            || str_contains($bloque, 'citrus-carousel__mobile_alternative')
            || str_contains($bloque, 'Productos patrocinados')
        ) {
            return true;
        }

        // En carrusel el formulario de cesta usa added_from=nn; en grid de categoría, catalog.
        if (preg_match('~added_from=(["\'])nn\1~i', $bloque) && !preg_match('~added_from=(["\'])catalog\1~i', $bloque)) {
            return true;
        }

        return false;
    }

    private function extraerUrlProductoDesdeBloqueTarjeta(string $bloque): ?string
    {
        if ($this->bloqueEsPatrocinado($bloque)) {
            return null;
        }

        foreach ($this->extraerUrlsHrefProductoCarrefour($bloque) as $u) {
            if ($this->esUrlProductoCarrefour($u)) {
                return $u;
            }
        }

        return null;
    }

    private function esUrlProductoCarrefour(string $url): bool
    {
        return (bool) preg_match('~/R-[^/]+/p/?$~i', rtrim($url, '/'));
    }

    /** Fin de listado: aparece el bloque de navegación "Ofertas" al final de la categoría */
    private function debeDetenerPaginacionCategoria(string $html): bool
    {
        return str_contains($html, 'alt="Ofertas"')
            && str_contains($html, 'cat20968591.jpg')
            && str_contains($html, 'nav-first-level-categories__image');
    }

    private function construirUrlSiguientePaginaOffset(string $urlPeticionActual): ?string
    {
        $parts = parse_url($urlPeticionActual);
        if ($parts === false || empty($parts['host'])) {
            return null;
        }

        $offsetActual = $this->extraerOffsetActual($urlPeticionActual);
        $siguienteOffset = $offsetActual + self::OFFSET_PAGINACION_CATEGORIA;

        $scheme = $parts['scheme'] ?? 'https';
        $host = $parts['host'];
        $port = isset($parts['port']) ? ':' . (int) $parts['port'] : '';
        $path = $parts['path'] ?? '/';
        $fragment = $parts['fragment'] ?? '';

        $query = $parts['query'] ?? '';
        parse_str($query, $params);
        $params['offset'] = $siguienteOffset;
        $queryString = http_build_query($params, '', '&', PHP_QUERY_RFC3986);

        $url = $scheme . '://' . $host . $port . $path;
        if ($queryString !== '') {
            $url .= '?' . $queryString;
        }
        if ($fragment !== '') {
            $url .= '#' . $fragment;
        }

        return $url;
    }

    private function extraerOffsetActual(string $urlPeticionActual): int
    {
        if (preg_match('~[?&]offset=(\d+)~i', $urlPeticionActual, $m)) {
            return max(0, (int) ($m[1] ?? 0));
        }

        return 0;
    }

    private function obtenerBaseUrlDesdeUrlPeticion(string $urlPeticionActual): string
    {
        $pu = parse_url($urlPeticionActual);
        $scheme = $pu['scheme'] ?? 'https';
        $host = $pu['host'] ?? 'www.carrefour.es';

        return $scheme . '://' . $host;
    }

    private function normalizarUrlCorta(string $url, string $base): string
    {
        $url = trim($url);
        if ($url === '') {
            return $url;
        }

        if (str_starts_with($url, '//')) {
            $pu = parse_url($base);
            $scheme = $pu['scheme'] ?? 'https';

            return $scheme . ':' . $url;
        }

        if (preg_match('~^https?://~i', $url)) {
            return $url;
        }

        if (str_starts_with($url, '/')) {
            return $base . $url;
        }

        return $base . '/' . $url;
    }
}
