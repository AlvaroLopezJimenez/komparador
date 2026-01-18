<?php

namespace App\Http\Controllers\Scraping\Tiendas;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\OfertaProducto;

class MiraviaController extends PlantillaTiendaController
{
    /**
     * Miravia: el precio correcto está en <div id="pdp_countUp"> o, como
     * alternativa, en un <div> con la clase "-u0r8Ritzn".
     *
     * Se busca primero por el atributo id y, si no se encuentra, se
     * intenta por la clase.
     *
     * Devuelve número sin símbolo € y con punto decimal.
     */
    public function obtenerPrecio($url, $variante = null, $tienda = null, $oferta = null): JsonResponse
    {
        $resultado = $this->apiHTML->obtenerHTML($url, null, $tienda ? $tienda->api : null);

        $proxyIp = null;
        if (is_array($resultado)) {
            $proxyIp = $resultado['proxy_ip'] ?? null;
            if (!$proxyIp && !empty($resultado['html']) && is_string($resultado['html'])) {
                if (preg_match('/PROXY\s+USADO:\s*([0-9]{1,3}(?:\.[0-9]{1,3}){3})/i', $resultado['html'], $match)) {
                    $proxyIp = trim($match[1]);
                }
            }
        }

        if (!is_array($resultado) || empty($resultado['success']) || empty($resultado['html'])) {
            return response()->json([
                'success' => false,
                'error'   => is_array($resultado) ? ($resultado['error'] ?? 'No se pudo obtener el HTML') : 'Respuesta inválida de la API',
                'proxy_ip' => $proxyIp,
            ]);
        }

        $html = (string) $resultado['html'];
        $html = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // DEBUG: Log para verificar si tenemos oferta
        Log::info('MiraviaController - Oferta recibida:', [
            'oferta_id' => $oferta ? $oferta->id : 'null',
            'oferta_tipo' => $oferta ? get_class($oferta) : 'null',
            'url' => $url
        ]);

        // DETECCIÓN DE ERRORES EN LA PÁGINA
        // Verificar si hay mensaje de sin stock en el HTML
        $textoSinStock = '<span class="_6p6YOL0XCy">Añadir a Mi wishlist</span>';
        $conteoSinStock = substr_count($html, $textoSinStock);
        
        // Verificar si hay mensaje de página no encontrada
        $textoPaginaNoEncontrada = 'Vaya... No se ha encontrado la página que buscabas. ¿Quieres probar con otra búsqueda?';
        $conteoPaginaNoEncontrada = substr_count($html, $textoPaginaNoEncontrada);
        
        Log::info('MiraviaController - Búsqueda de errores:', [
            'texto_sin_stock' => $textoSinStock,
            'conteo_sin_stock' => $conteoSinStock,
            'texto_pagina_no_encontrada' => $textoPaginaNoEncontrada,
            'conteo_pagina_no_encontrada' => $conteoPaginaNoEncontrada,
            'html_length' => strlen($html),
            'oferta_id' => $oferta ? $oferta->id : 'null'
        ]);
        
        // Manejar sin stock - con que aparezca 1 vez es suficiente
        if ($conteoSinStock >= 1) {
            Log::info('MiraviaController - SIN STOCK DETECTADO:', [
                'oferta_id' => $oferta ? $oferta->id : 'null',
                'oferta_tipo' => $oferta ? get_class($oferta) : 'null',
                'conteo_sin_stock' => $conteoSinStock
            ]);
            
            // Si hay oferta asociada, actualizar para no mostrar y crear aviso
            if ($oferta && $oferta instanceof OfertaProducto) {
                Log::info('MiraviaController - Actualizando oferta sin stock:', [
                    'oferta_id' => $oferta->id,
                    'mostrar_anterior' => $oferta->mostrar
                ]);
                
                // Actualizar oferta para no mostrar
                $oferta->update(['mostrar' => 'no']);
                
                // Crear aviso con fecha a una semana vista
                $avisoId = DB::table('avisos')->insertGetId([
                    'texto_aviso'     => 'SIN STOCK 1 VEZ - AVISO GENERADO AUTOMATICAMENTE',
                    'fecha_aviso'     => now()->addWeek(), // Una semana vista
                    'user_id'         => 1,                 // usuario sistema
                    'avisoable_type'  => \App\Models\OfertaProducto::class,
                    'avisoable_id'    => $oferta->id,
                    'oculto'          => 0,                 // visible
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ]);
                
                Log::info('MiraviaController - Aviso creado:', [
                    'aviso_id' => $avisoId,
                    'oferta_id' => $oferta->id
                ]);
            } else {
                Log::warning('MiraviaController - Sin stock detectado pero no hay oferta válida:', [
                    'oferta' => $oferta,
                    'oferta_tipo' => $oferta ? get_class($oferta) : 'null'
                ]);
            }
        }
        
        // Manejar página no encontrada - solo si aparece más de una vez
        if ($conteoPaginaNoEncontrada > 1) {
            Log::info('MiraviaController - PÁGINA NO ENCONTRADA DETECTADO:', [
                'oferta_id' => $oferta ? $oferta->id : 'null',
                'oferta_tipo' => $oferta ? get_class($oferta) : 'null',
                'conteo_pagina_no_encontrada' => $conteoPaginaNoEncontrada
            ]);
            
            // Si hay oferta asociada, actualizar para no mostrar y crear aviso
            if ($oferta && $oferta instanceof OfertaProducto) {
                Log::info('MiraviaController - Actualizando oferta página no encontrada:', [
                    'oferta_id' => $oferta->id,
                    'mostrar_anterior' => $oferta->mostrar
                ]);
                
                // Actualizar oferta para no mostrar
                $oferta->update(['mostrar' => 'no']);
                
                // Crear aviso con fecha a una semana vista
                $avisoId = DB::table('avisos')->insertGetId([
                    'texto_aviso'     => 'EL ENLACE HA DESAPARECIDO 0VEZ- GENERADO AUTOMATICAMENTE',
                    'fecha_aviso'     => now()->addWeek(), // Una semana vista
                    'user_id'         => 1,                 // usuario sistema
                    'avisoable_type'  => \App\Models\OfertaProducto::class,
                    'avisoable_id'    => $oferta->id,
                    'oculto'          => 0,                 // visible
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ]);
                
                Log::info('MiraviaController - Aviso página no encontrada creado:', [
                    'aviso_id' => $avisoId,
                    'oferta_id' => $oferta->id
                ]);
            } else {
                Log::warning('MiraviaController - Página no encontrada detectado pero no hay oferta válida:', [
                    'oferta' => $oferta,
                    'oferta_tipo' => $oferta ? get_class($oferta) : 'null'
                ]);
            }
        }

        // 1) Intentar por id="pdp_countUp"
        if (preg_match(
            '~<div[^>]*\bid\s*=\s*["\']pdp_countUp["\'][^>]*>\s*(?<p>\d+(?:[.,]\d{2})?)\s*(?:€|&euro;|&nbsp;€)?\s*</div>~i',
            $html,
            $m
        ) && !empty($m['p'])) {
            $precio = $this->aNumero($m['p']);
            if ($precio !== null) {
                return response()->json([
                    'success' => true,
                    'precio' => $precio,
                    'proxy_ip' => $proxyIp,
                ]);
            }
        }
        
        // 2) Fallback por clase específica (p.ej. "-u0r8Ritzn")
        if (preg_match(
            '~<div[^>]*\bclass\s*=\s*["\'][^"\']*\b-u0r8Ritzn\b[^"\']*["\'][^>]*>\s*(?<p>\d+(?:[.,]\d{2})?)\s*(?:€|&euro;|&nbsp;€)?\s*</div>~i',
            $html,
            $m2
        ) && !empty($m2['p'])) {
            $precio = $this->aNumero($m2['p']);
            if ($precio !== null) {
                return response()->json([
                    'success' => true,
                    'precio' => $precio,
                    'proxy_ip' => $proxyIp,
                ]);
            }
        }
        
        return response()->json([
            'success' => false,
            'error'   => 'No se pudo encontrar el precio en Miravia (pdp_countUp / clase -u0r8Ritzn).',
            'proxy_ip' => $proxyIp,
        ]);

    }

    /**
     * Convierte "44,29" o "44.29" a float con punto decimal.
     */
    private function aNumero($raw): ?float
    {
        if (!is_string($raw) && !is_numeric($raw)) return null;
        $s = trim((string)$raw);

        // Si hay coma y punto, asumimos formato ES con miles en punto (poco probable aquí)
        if (strpos($s, ',') !== false && strpos($s, '.') !== false) {
            $s = str_replace('.', '', $s);
        }
        $s = str_replace(',', '.', $s);

        if (!is_numeric($s)) return null;
        return (float)$s;
    }
}


