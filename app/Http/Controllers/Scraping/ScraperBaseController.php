<?php

namespace App\Http\Controllers\Scraping;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OfertaProducto;
use App\Models\EjecucionGlobal;
use App\Models\Producto;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Services\CalcularPrecioUnidad;
use App\Services\SacarPrimeraOfertaDeUnProductoAplicadoDescuentosYChollos;
use App\Services\TiemposActualizacionOfertasDinamicos;

abstract class ScraperBaseController extends Controller
{
    /**
     * Procesar una oferta individual para scraping
     */
    protected function procesarOfertaScraper(OfertaProducto $oferta)
    {
        // Calcular nueva frecuencia basada en historial (antes de scrapear)
        $serviceTiempos = new TiemposActualizacionOfertasDinamicos();
        $serviceTiempos->calcularFrecuencia($oferta->id);
        
        // Obtener las 1 ofertas más baratas del mismo producto (antes del scraping)
        $producto = Producto::find($oferta->producto_id);
        $servicioOfertas = new SacarPrimeraOfertaDeUnProductoAplicadoDescuentosYChollos();
        $ofertaMasBarataAntes = $producto ? $servicioOfertas->obtener($producto) : null;
        $ofertasAntes = $ofertaMasBarataAntes ? collect([$ofertaMasBarataAntes]) : collect();

        // Usar el nuevo sistema de scraping interno
        $scrapingController = new ScrapingController();
        $request = new \Illuminate\Http\Request();
        $request->merge([
            'url' => $oferta->url,
            'tienda' => $oferta->tienda->nombre,
            'variante' => $oferta->variante ?? null
        ]);
        
        $response = $scrapingController->obtenerPrecio($request, $oferta);
        $responseData = $response->getData(true);
        
        // Guardar el precio anterior ANTES de cualquier procesamiento
        $precioAnterior = $oferta->precio_total;
        
        // Verificar si la respuesta contiene un error
        if (!$responseData['success']) {
            return [
                'oferta_id' => $oferta->id,
                'tienda_nombre' => $oferta->tienda->nombre,
                'url' => $oferta->url,
                'variante' => $oferta->variante,
                'precio_anterior' => $precioAnterior,
                'precio_nuevo' => null,
                'success' => false,
                'error' => 'Error en el scraping: ' . ($responseData['error'] ?? 'Error desconocido'),
                'cambios_detectados' => false,
                'url_notificacion_llamada' => false,
            ];
        }
        
        // Verificar si la respuesta contiene un precio válido
        if (!isset($responseData['precio']) || !is_numeric($responseData['precio'])) {
            return [
                'oferta_id' => $oferta->id,
                'tienda_nombre' => $oferta->tienda->nombre,
                'url' => $oferta->url,
                'variante' => $oferta->variante,
                'precio_anterior' => $precioAnterior,
                'precio_nuevo' => null,
                'success' => false,
                'error' => 'Respuesta inválida del scraping: ' . json_encode($responseData),
                'cambios_detectados' => false,
                'url_notificacion_llamada' => false,
            ];
        }

        // Convertir precio a formato decimal (cambiar coma por punto si es necesario)
        $precioNuevo = (float) str_replace(',', '.', $responseData['precio']);
        
        // Redondear precio total a 2 decimales
        $precioNuevo = round($precioNuevo, 2);
        
        // Calcular precio por unidad usando el servicio
        $producto = Producto::find($oferta->producto_id);
        $calcularPrecioUnidad = new CalcularPrecioUnidad();
        $precioUnidadNuevo = $calcularPrecioUnidad->calcular(
            $producto->unidadDeMedida ?? 'unidad',
            $precioNuevo,
            $oferta->unidades
        );
        
        // Si el servicio devuelve null, usar cálculo por defecto
        if ($precioUnidadNuevo === null) {
            $precioUnidadNuevo = round($precioNuevo / $oferta->unidades, 2);
        }
        
        // ================================
// DETECCIÓN BAJADA > 40% + AVISO
// ================================

// (Asegúrate de tener arriba del archivo:)
/// use Illuminate\Support\Facades\DB;
/// use App\Models\Producto; // si no lo tienes ya importado
/// use App\Models\OfertaProducto; // si no lo tienes ya importado

$umbralBajada = 0.40; // 40%

// Evitar divisiones/avisos si no hay precio anterior válido
if (is_numeric($precioAnterior) && $precioAnterior > 0) {
    // % de bajada respecto al precio anterior (0.00 - 1.00)
    $bajadaRelativa = ($precioAnterior - $precioNuevo) / $precioAnterior;

    if ($bajadaRelativa >= $umbralBajada) {
        // Construir texto del aviso
        $producto = Producto::find($oferta->producto_id);
        $nombreProducto = $producto ? $producto->nombre : ('Producto ID '.$oferta->producto_id);

        $bajadaAbsoluta = max(0, $precioAnterior - $precioNuevo); // por claridad
        $porcentajeStr = number_format($bajadaRelativa * 100, 0);  // ej: "41"
        $textoAviso = sprintf(
            "Bajada anómala (>40%%) en '%s': de %.2f€ a %.2f€ (−%.2f€, −%s%%). Tienda: %s | Variante: %s | URL: %s",
            $nombreProducto,
            $precioAnterior,
            $precioNuevo,
            $bajadaAbsoluta,
            $porcentajeStr,
            $oferta->tienda->nombre,
            $oferta->variante ?? '—',
            $oferta->url
        );

        // Guardar aviso
        DB::table('avisos')->insert([
            'texto_aviso'     => $textoAviso,
            'fecha_aviso'     => now(),                         // momento de detección
            'user_id'         => 1,                             // usuario sistema
            'avisoable_type'  => \App\Models\OfertaProducto::class,
            'avisoable_id'    => $oferta->id,
            'oculto'          => 0,                             // visible
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        // NO actualizamos precios en la oferta (posible outlier), solo tocamos updated_at
        $oferta->touch();

        // Devolvemos sin continuar con el flujo de actualización/comparación
        return [
            'oferta_id'               => $oferta->id,
            'tienda_nombre'           => $oferta->tienda->nombre,
            'url'                     => $oferta->url,
            'variante'                => $oferta->variante,
            'precio_anterior'         => $precioAnterior,
            'precio_nuevo'            => $precioNuevo, // detectado pero NO aplicado
            'success'                 => true,
            'error'                   => null,
            'cambios_detectados'      => false,        // no rehacemos rankings porque no aplicamos precio
            'url_notificacion_llamada'=> false,
            // opcionalmente puedes añadir una marca para logging interno:
            // 'aviso_creado'         => true,
            // 'motivo'               => 'Bajada > 40% (no aplicado)',
        ];
    }
}
// ===== FIN DETECCIÓN BAJADA > 40% =====


        // Actualizar la oferta
        $oferta->update([
            'precio_total' => $precioNuevo,
            'precio_unidad' => $precioUnidadNuevo,
        ]);
        
        // Forzar la actualización del updated_at
        $oferta->touch();
        
        // Registrar actualización exitosa en el historial de tiempos dinámicos
        $serviceTiempos->registrarActualizacion($oferta->id, $precioNuevo, 'automatico');

        // Obtener las 1 ofertas más baratas del mismo producto (después del scraping)
        // Reutilizar el producto y servicio ya obtenidos al inicio del método
        $ofertaMasBarataDespues = $producto ? $servicioOfertas->obtener($producto) : null;
        $ofertasDespues = $ofertaMasBarataDespues ? collect([$ofertaMasBarataDespues]) : collect();

        // Comparar si han cambiado las ofertas más baratas
        $cambiosDetectados = false;
        $urlNotificacionLlamada = false;

        $ofertaAntes = $ofertasAntes->first();
        $ofertaDespues = $ofertasDespues->first();
        
        $precioRealAntes = $ofertaAntes ? $this->calcularPrecioRealPorUnidad($ofertaAntes) : null;
        $precioRealDespues = $ofertaDespues ? $this->calcularPrecioRealPorUnidad($ofertaDespues) : null;

        if (($ofertaAntes ? $ofertaAntes->id : null) !== ($ofertaDespues ? $ofertaDespues->id : null) ||
            $precioRealAntes !== $precioRealDespues) {
            
            $cambiosDetectados = true;
            
            // Actualizar el precio del producto con el precio más bajo (considerando descuentos)
            $precioMasBajo = $precioRealDespues;
            $producto = Producto::find($oferta->producto_id);
            if ($producto) {
                
                // Guardamos el precio_unidad (precio actual del producto) antes de actualizarlo
                $precioAntiguoProducto = Producto::where('id', $oferta->producto_id)
                ->value('precio');
                
                
                //Actualizamos el precio del producto porque ha habido un cambio en las posiciones o en el precio
                $producto->update(['precio' => $precioMasBajo]);
                    
                    //Además guardamos un aviso para comprobación manual
                    // Guardar aviso
                    $textoAviso = 'Precio actualizado producto '.$producto->nombre.'precio antiguo: '. $precioAntiguoProducto.', precio Nuevo: '.$precioUnidadNuevo;
                    DB::table('avisos')->insert([
                        'texto_aviso'     => $textoAviso,
                        'fecha_aviso'     => now(),                         // momento de detección
                        'user_id'         => 1,                             // usuario sistema
                        'avisoable_type'  => \App\Models\Producto::class,
                        'avisoable_id'    => $producto->id,
                        'oculto'          => 0,                             // visible
                        'created_at'      => now(),
                        'updated_at'      => now(),
                    ]);
                    
                                         // NO enviar alertas automáticamente - se enviarán manualmente desde el panel de avisos
                     // Solo agregar información sobre alertas pendientes
                     $alertasPendientes = \App\Models\CorreoAvisoPrecio::where('producto_id', $producto->id)
                         ->where('precio_limite', '>=', $precioMasBajo)
                         ->count();
                     
                     if ($alertasPendientes > 0) {
                         $textoAviso .= ' | Alertas pendientes: ' . $alertasPendientes . ' correos';
                         DB::table('avisos')->where('avisoable_type', \App\Models\Producto::class)
                             ->where('avisoable_id', $producto->id)
                             ->where('texto_aviso', 'LIKE', '%Precio actualizado producto%')
                             ->orderBy('created_at', 'desc')
                             ->limit(1)
                             ->update(['texto_aviso' => $textoAviso]);
                     }
                
                
            }
            
            // ==========================================
            // ZONA DE NOTIFICACIONES Y ACTUALIZACIONES
            // ==========================================
            // 
            // Aquí se implementarán las siguientes funcionalidades:
            // 
            // 1. NOTIFICACIONES POR EMAIL:
            //    - Enviar email a usuarios suscritos cuando cambian los precios
            //    - Notificar cuando una oferta entra en el top 3 más barato
            //    - Alertas de precios que suben significativamente
            // 
            // 2. NOTIFICACIONES PUSH:
            //    - Enviar notificaciones push a dispositivos móviles
            //    - Alertas en tiempo real de cambios de precios
            // 
            // 3. INTEGRACIÓN CON REDES SOCIALES:
            //    - Publicar automáticamente en Twitter cuando hay ofertas destacadas
            //    - Compartir en Facebook ofertas con grandes descuentos
            //    - Notificar en Telegram a usuarios suscritos
            // 
            // 4. SISTEMA DE ALERTAS PERSONALIZADAS:
            //    - Alertas cuando el precio baja de un umbral específico
            //    - Notificaciones cuando una tienda específica tiene ofertas
            //    - Recordatorios de productos en lista de deseos
            // 
            // 5. INTEGRACIÓN CON ADS Y MARKETING:
            //    - Actualizar campañas de Google Ads con nuevos precios
            //    - Modificar anuncios de Facebook Ads automáticamente
            //    - Ajustar presupuestos de marketing basado en precios
            // 
            // 6. SISTEMA DE WEBHOOKS:
            //    - Notificar a sistemas externos de cambios de precios
            //    - Integración con CRMs y sistemas de gestión
            //    - Sincronización con marketplaces externos
            // 
            // 7. ANÁLISIS Y REPORTES:
            //    - Generar reportes automáticos de cambios de precios
            //    - Análisis de tendencias de precios por categoría
            //    - Estadísticas de efectividad de alertas
            // 
            // 8. OPTIMIZACIÓN SEO:
            //    - Actualizar meta tags con nuevos precios
            //    - Regenerar sitemaps cuando cambian precios importantes
            //    - Notificar a motores de búsqueda de cambios
            // 
            // 9. INTEGRACIÓN CON CHATBOTS:
            //    - Notificar a chatbots de cambios de precios
            //    - Actualizar respuestas automáticas con nuevos precios
            //    - Alertas en tiempo real para atención al cliente
            // 
            // 10. SISTEMA DE GAMIFICACIÓN:
            //     - Puntos por detectar cambios de precios
            //     - Badges por encontrar las mejores ofertas
            //     - Rankings de usuarios que más ahorran
            // 
            // ==========================================
            // FIN ZONA DE NOTIFICACIONES Y ACTUALIZACIONES
            // ==========================================
        }

        return [
            'oferta_id' => $oferta->id,
            'tienda_nombre' => $oferta->tienda->nombre,
            'url' => $oferta->url,
            'variante' => $oferta->variante,
            'precio_anterior' => $precioAnterior,
            'precio_nuevo' => $precioNuevo,
            'success' => true,
            'error' => null,
            'cambios_detectados' => $cambiosDetectados,
            'url_notificacion_llamada' => $urlNotificacionLlamada,
            'ofertas_antes' => $cambiosDetectados ? $ofertasAntes->toArray() : null,
            'ofertas_despues' => $cambiosDetectados ? $ofertasDespues->toArray() : null,
        ];
    }

    /**
     * Calcular el precio real por unidad considerando descuentos
     * DEPRECADO: Usar DescuentosController en su lugar
     */
    protected function calcularPrecioRealPorUnidad($oferta)
    {
        // Usar el controlador centralizado de descuentos
        $descuentosController = new \App\Http\Controllers\DescuentosController();
        $ofertaConDescuento = $descuentosController->aplicarDescuento($oferta);
        
        return $ofertaConDescuento->precio_unidad;
    }

    /**
     * Obtener la oferta más barata considerando descuentos
     */
    protected function obtenerOfertaMasBarata($productoId)
    {
        $ofertas = OfertaProducto::where('producto_id', $productoId)
            ->where('mostrar', 'si')
            ->whereHas('tienda', function($query) {
                $query->where('mostrar_tienda', 'si');
            })
            ->get(['id', 'precio_unidad', 'precio_total', 'unidades', 'descuentos']);

        $ofertaMasBarata = null;
        $precioRealMasBajo = null;

        foreach ($ofertas as $oferta) {
            $precioReal = $this->calcularPrecioRealPorUnidad($oferta);
            if ($precioRealMasBajo === null || $precioReal < $precioRealMasBajo) {
                $precioRealMasBajo = $precioReal;
                $ofertaMasBarata = $oferta;
            }
        }

        return $ofertaMasBarata ? collect([$ofertaMasBarata]) : collect();
    }

    /**
     * Obtener ofertas elegibles para procesar
     */
    protected function obtenerOfertasElegibles($limit = 50)
    {
        return OfertaProducto::with(['producto', 'tienda'])
            ->where('mostrar', 'si')
            ->where('como_scrapear', 'automatico')
            ->whereNull('chollo_id')
            ->whereHas('tienda', function($query) {
                $query->where('scrapear', 'si');
            })
            ->whereRaw('TIMESTAMPDIFF(MINUTE, updated_at, NOW()) >= frecuencia_actualizar_precio_minutos')
            ->orderByRaw('TIMESTAMPDIFF(MINUTE, updated_at, NOW()) DESC')
            ->limit($limit)
            ->get();
    }
}
