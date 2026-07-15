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
        $tiendas = $this->obtenerTiendasDisponibles();
        $controladoresTiendas = $this->obtenerControladoresTiendas();

        return view('admin.scraping.test-precio', compact('tiendas', 'controladoresTiendas'));
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

    /**
     * Obtener lista de controladores de tiendas disponibles (misma lógica que DiagnosticoController).
     */
    private function obtenerControladoresTiendas()
    {
        $tiendasPath = app_path('Http/Controllers/Scraping/Tiendas');
        $controladores = [];

        if (file_exists($tiendasPath)) {
            $archivos = scandir($tiendasPath);

            foreach ($archivos as $archivo) {
                if (pathinfo($archivo, PATHINFO_EXTENSION) === 'php' &&
                    $archivo !== 'PlantillaTiendaController.php' &&
                    $archivo !== 'INSTRUCCIONES_TIENDAS.txt') {

                    $nombreTienda = str_replace('Controller.php', '', $archivo);
                    $controladores[] = $nombreTienda;
                }
            }
        }

        sort($controladores);
        return $controladores;
    }
}
