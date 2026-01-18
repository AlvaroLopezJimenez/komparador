<?php

namespace App\Http\Controllers\Scraping\Tiendas;

use App\Http\Controllers\Scraping\Tiendas\PlantillaTiendaController;
use Illuminate\Http\JsonResponse;

class FarmaciasDirectController extends PlantillaTiendaController
{
    /**
     * Extrae el precio de una página de FarmadiasDirect.
     * - Prioriza <meta property="og:price:amount" content="…">.
     * - Fallback visible: <span class="price-item price-item--regular">5,66€</span>.
     * - Devuelve número sin símbolo €; normaliza coma/punto y entidades (&nbsp;).
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

        $html = html_entity_decode((string)$resultado['html'], ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // DETECCIÓN DE ENLACE 404
        $this->detectarEnlace404($html, $oferta);

        // DETECCIÓN DE DESCUENTO 2ª AL 70%
        // Hay productos que no tienen el descuento 2ª al 70%, pero si aparece. asi que lo quitamos
        // $this->detectarDescuento2aAl70($html, $oferta);

        // 1) <meta property="og:price:amount" content="5,66">
        if (preg_match(
            '~<meta[^>]*\bproperty\s*=\s*["\']og:price:amount["\'][^>]*\bcontent\s*=\s*["\'](?<p>[\d\.,]+)["\'][^>]*>~i',
            $html,
            $m1
        ) && !empty($m1['p'])) {
            if (($p = $this->normalizarImporte($m1['p'])) !== null) {
                return response()->json(['success' => true, 'precio' => $p]);
            }
        }

        // 2) Visible: <span class="price-item price-item--regular">5,66€</span>
        if (preg_match(
            '~<span[^>]*class=["\'][^"\']*\bprice-item\b[^"\']*\bprice-item--regular\b[^"\']*["\'][^>]*>\s*(?<p>\d{1,3}(?:[.\s]\d{3})*(?:[.,]\d{2}))\s*(?:€|&euro;)?\s*</span>~si',
            $html,
            $m2
        ) && !empty($m2['p'])) {
            if (($p = $this->normalizarImporte($m2['p'])) !== null) {
                return response()->json(['success' => true, 'precio' => $p]);
            }
        }

        return response()->json([
            'success' => false,
            'error'   => 'No se pudo encontrar el precio en la página de FarmadiasDirect',
        ]);
    }

    /**
     * Convierte "5,66", "5.66" o "1.234,56" a float con punto decimal.
     */
    private function normalizarImporte(string $importe): ?float
    {
        $s = preg_replace('/[^\d\.,]/u', '', $importe);
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

    /**
     * Detecta el descuento "70% dto. 2ª ud." y actualiza la oferta
     */
    private function detectarDescuento2aAl70(string $html, $oferta): void
    {
        if (!$oferta || !($oferta instanceof \App\Models\OfertaProducto)) {
            return;
        }

        // Detectar "70% dto. 2ª ud" - patrón más flexible
        // Acepta: 70%, dto., 2a/2ª, ud/ud.
        $tieneDescuento = (bool) preg_match('/70\s*%\s*dto\.\s*2\s*[aªº]\s*ud/iu', $html);

        if ($tieneDescuento) {
            \Log::info('FarmaciasDirectController - DESCUENTO 2ª AL 70% DETECTADO:', [
                'oferta_id' => $oferta->id,
                'oferta_tipo' => get_class($oferta)
            ]);

            $descuentoAnterior = $oferta->descuentos;

            // Actualizar el campo descuentos de la oferta
            $oferta->update(['descuentos' => '2a al 70']);

            // Solo crear aviso si el descuento es nuevo o ha cambiado
            if ($descuentoAnterior !== '2a al 70') {
                $avisoId = \DB::table('avisos')->insertGetId([
                    'texto_aviso'     => 'DETECTADO DESCUENTO 2ª AL 70% - GENERADO AUTOMÁTICAMENTE',
                    'fecha_aviso'     => now(),
                    'user_id'         => 1,
                    'avisoable_type'  => \App\Models\OfertaProducto::class,
                    'avisoable_id'    => $oferta->id,
                    'oculto'          => 0,
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ]);

                \Log::info('FarmaciasDirectController - Aviso descuento 2ª al 70% creado:', [
                    'aviso_id' => $avisoId,
                    'oferta_id' => $oferta->id
                ]);
            }
        } else {
            // Si no hay descuento pero la oferta tenía '2a al 70', limpiar el campo
            if ($oferta->descuentos === '2a al 70') {
                \Log::info('FarmaciasDirectController - Descuento 2ª al 70% ya no disponible, limpiando campo descuentos:', [
                    'oferta_id' => $oferta->id,
                    'descuentos_anterior' => $oferta->descuentos
                ]);

                $oferta->update(['descuentos' => null]);

                \Log::info('FarmaciasDirectController - Campo descuentos limpiado:', [
                    'oferta_id' => $oferta->id
                ]);
            }
        }
    }

    /**
     * Detecta enlaces 404 y actualiza la oferta a mostrar = no
     */
    private function detectarEnlace404(string $html, $oferta): void
    {
        if (!$oferta || !($oferta instanceof \App\Models\OfertaProducto)) {
            return;
        }

        // Detectar meta tag 404
        $tiene404 = (bool) preg_match('/<meta[^>]*property\s*=\s*["\']og:title["\'][^>]*content\s*=\s*["\']404\s+No\s+encontrado["\'][^>]*>/i', $html);

        if ($tiene404) {
            \Log::info('FarmaciasDirectController - ENLACE 404 DETECTADO:', [
                'oferta_id' => $oferta->id,
                'oferta_tipo' => get_class($oferta)
            ]);

            // Actualizar oferta para no mostrar
            $oferta->update(['mostrar' => 'no']);

            // Crear aviso con fecha a 4 días vista
            $avisoId = \DB::table('avisos')->insertGetId([
                'texto_aviso'     => 'Enlace 404 1A VEZ',
                'fecha_aviso'     => now()->addDays(4), // 4 días vista
                'user_id'         => 1,                 // usuario sistema
                'avisoable_type'  => \App\Models\OfertaProducto::class,
                'avisoable_id'    => $oferta->id,
                'oculto'          => 0,                 // visible
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);

            \Log::info('FarmaciasDirectController - Aviso 404 creado:', [
                'aviso_id' => $avisoId,
                'oferta_id' => $oferta->id
            ]);
        }
    }
}


