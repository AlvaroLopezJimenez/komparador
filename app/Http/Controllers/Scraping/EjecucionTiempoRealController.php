<?php

namespace App\Http\Controllers\Scraping;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\EjecucionGlobal;
use App\Models\OfertaProducto;
use Carbon\Carbon;
use App\Http\Controllers\Scraping\ScraperBaseController;

class EjecucionTiempoRealController extends ScraperBaseController
{
    /**
     * Vista principal de ejecución en tiempo real
     */
    public function index()
    {
        return view('admin.scraping.ejecucion-tiempo-real');
    }

    /**
     * Iniciar una nueva ejecución
     */
    public function iniciar(Request $request)
    {
        // Obtener ofertas elegibles
        $ofertasElegibles = parent::obtenerOfertasElegibles(50);
        
        if ($ofertasElegibles->isEmpty()) {
            // No crear ejecución si no hay ofertas para procesar
            return response()->json([
                'success' => true,
                'completada' => true,
                'ejecucion_id' => null,
                'total_ofertas' => 0,
                'ofertas' => [],
                'total_guardado' => 0,
                'total_errores' => 0,
                'message' => 'No hay ofertas para actualizar',
                'progreso' => [
                    'actualizadas' => 0,
                    'errores' => 0,
                    'procesadas' => 0
                ]
            ]);
        }

        // Crear registro de ejecución solo si hay ofertas para procesar
        $ejecucion = EjecucionGlobal::create([
            'inicio' => now(),
            'nombre' => 'ejecuciones_scrapear_ofertas',
            'total' => $ofertasElegibles->count(),
            'total_guardado' => 0,
            'total_errores' => 0,
            'log' => [
                'token' => 'srtocoque',
                'estado' => 'iniciada',
                'total_ofertas' => $ofertasElegibles->count(),
                'ofertas' => $ofertasElegibles->pluck('id')->toArray(),
                'actualizadas' => 0,
                'errores' => 0,
                'procesadas' => 0,
                'resultados' => []
            ]
        ]);

        return response()->json([
            'success' => true,
            'ejecucion_id' => $ejecucion->id,
            'total_ofertas' => $ofertasElegibles->count(),
            'ofertas' => $ofertasElegibles->pluck('id')->toArray()
        ]);
    }

    /**
     * Procesar la siguiente oferta
     */
    public function procesarSiguiente(Request $request)
    {
        $ejecucionId = $request->input('ejecucion_id');
        $indiceActual = $request->input('indice_actual', 0);
        
        $ejecucion = EjecucionGlobal::find($ejecucionId);
        if (!$ejecucion) {
            return response()->json([
                'success' => false,
                'error' => 'Ejecución no encontrada'
            ]);
        }

        $log = $ejecucion->log;
        $ofertasIds = $log['ofertas'] ?? [];
        
        if ($indiceActual >= count($ofertasIds)) {
            // Ejecución completada
            $log['estado'] = 'completada';
            
            $ejecucion->update([
                'fin' => now(),
                'total_guardado' => $log['actualizadas'] ?? 0,
                'total_errores' => $log['errores'] ?? 0,
                'log' => $log
            ]);

            return response()->json([
                'success' => true,
                'completada' => true,
                'total_guardado' => $log['actualizadas'] ?? 0,
                'total_errores' => $log['errores'] ?? 0,
                'progreso' => [
                    'actualizadas' => $log['actualizadas'] ?? 0,
                    'errores' => $log['errores'] ?? 0,
                    'procesadas' => $log['procesadas'] ?? 0
                ]
            ]);
        }

        $ofertaId = $ofertasIds[$indiceActual];
        $oferta = OfertaProducto::with(['producto', 'tienda'])->find($ofertaId);
        
        if (!$oferta) {
            $log['errores']++;
            $log['procesadas']++;
            $log['resultados'][] = [
                'oferta_id' => $ofertaId,
                'tienda_nombre' => 'Desconocida',
                'url' => '',
                'variante' => '',
                'precio_anterior' => null,
                'precio_nuevo' => null,
                'success' => false,
                'error' => 'Oferta no encontrada',
                'cambios_detectados' => false,
                'url_notificacion_llamada' => false
            ];
            
            $ejecucion->update(['log' => $log]);
            
            return response()->json([
                'success' => true,
                'completada' => false,
                'oferta_actual' => [
                    'id' => $ofertaId,
                    'nombre' => 'Oferta no encontrada',
                    'error' => 'Oferta no encontrada'
                ],
                'progreso' => [
                    'actualizadas' => $log['actualizadas'] ?? 0,
                    'errores' => $log['errores'] ?? 0,
                    'procesadas' => $log['procesadas'] ?? 0
                ]
            ]);
        }

        // Procesar la oferta usando el método heredado
        $resultado = $this->procesarOfertaScraper($oferta);

        // Actualizar log
        $log['procesadas']++;
        if ($resultado['success']) {
            $log['actualizadas']++;
        } else {
            $log['errores']++;
        }
        $log['resultados'][] = $resultado;
        
        $ejecucion->update(['log' => $log]);

        return response()->json([
            'success' => true,
            'completada' => false,
            'oferta_actual' => [
                'id' => $oferta->id,
                'nombre' => $oferta->producto->nombre . ' - ' . $oferta->tienda->nombre,
                'resultado' => $resultado
            ],
            'progreso' => [
                'actualizadas' => $log['actualizadas'] ?? 0,
                'errores' => $log['errores'] ?? 0,
                'procesadas' => $log['procesadas'] ?? 0
            ]
        ]);
    }

    /**
     * Obtener estado de la ejecución
     */
    public function obtenerEstado(Request $request)
    {
        $ejecucionId = $request->input('ejecucion_id');
        
        $ejecucion = EjecucionGlobal::find($ejecucionId);
        if (!$ejecucion) {
            return response()->json([
                'success' => false,
                'error' => 'Ejecución no encontrada'
            ]);
        }

        $log = $ejecucion->log;
        
        return response()->json([
            'success' => true,
            'ejecucion' => [
                'id' => $ejecucion->id,
                'total_ofertas' => $log['total_ofertas'] ?? 0,
                'actualizadas' => $log['actualizadas'] ?? 0,
                'errores' => $log['errores'] ?? 0,
                'procesadas' => $log['procesadas'] ?? 0,
                'completada' => $ejecucion->fin !== null
            ]
        ]);
    }

    /**
     * Marcar ejecución como completada (método de emergencia)
     */
    public function marcarCompletada(Request $request)
    {
        $ejecucionId = $request->input('ejecucion_id');
        
        $ejecucion = EjecucionGlobal::find($ejecucionId);
        if (!$ejecucion) {
            return response()->json([
                'success' => false,
                'error' => 'Ejecución no encontrada'
            ]);
        }

        $log = $ejecucion->log;
        $log['estado'] = 'completada';
        
        $ejecucion->update([
            'fin' => now(),
            'total_guardado' => $log['actualizadas'] ?? 0,
            'total_errores' => $log['errores'] ?? 0,
            'log' => $log
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Ejecución marcada como completada'
        ]);
    }




}
