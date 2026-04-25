<?php

namespace App\Http\Controllers\Scraping;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Tienda;

class TestPrecioController extends Controller
{
    /**
     * Mostrar la vista de testing de precios
     */
    public function index()
    {
        // Obtener lista de controladores disponibles en la carpeta Tiendas
        $tiendas = $this->obtenerTiendasDisponibles();
        
        return view('admin.scraping.test-precio', compact('tiendas'));
    }

    /**
     * Procesar una URL para obtener el precio
     */
    public function procesarUrl(Request $request)
    {
        $request->validate([
            'url' => 'required|url',
            'tienda' => 'required|string'
        ]);

        try {
            // Medir tiempo de inicio
            $tiempoInicio = microtime(true);
            
            // Usar el sistema de scraping interno
            $scrapingController = new ScrapingController();
            $scrapingRequest = new \Illuminate\Http\Request();
            $scrapingRequest->merge([
                'url' => $request->url,
                'tienda' => $request->tienda,
                'variante' => $request->variante ?? null
            ]);
            
            $response = $scrapingController->obtenerPrecio($scrapingRequest);
            $responseData = $response->getData(true);
            
            // Calcular tiempo de respuesta
            $tiempoFin = microtime(true);
            $tiempoRespuesta = round(($tiempoFin - $tiempoInicio) * 1000, 2); // Convertir a milisegundos
            
            // Añadir tiempo de respuesta a los datos
            $responseData['tiempo_respuesta'] = $tiempoRespuesta;
            
            return response()->json([
                'success' => true,
                'data' => $responseData
            ]);
            
        } catch (\Exception $e) {
            // Calcular tiempo incluso si hay error
            $tiempoFin = microtime(true);
            $tiempoRespuesta = round(($tiempoFin - $tiempoInicio) * 1000, 2);
            
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'tiempo_respuesta' => $tiempoRespuesta
            ], 500);
        }
    }

    /**
     * Obtener lista de tiendas disponibles
     */
    private function obtenerTiendasDisponibles()
    {
        // Usamos las tiendas existentes en BD para que el parámetro "tienda"
        // coincida con Tienda::nombre (tal como hace ScrapingController).
        return Tienda::query()
            ->select('nombre')
            ->orderBy('nombre', 'asc')
            ->pluck('nombre')
            ->toArray();
    }
}
