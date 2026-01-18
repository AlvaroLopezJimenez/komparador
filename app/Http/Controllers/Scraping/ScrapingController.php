<?php

namespace App\Http\Controllers\Scraping;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ScrapingController extends Controller
{
    /**
     * Punto de entrada principal para scraping de ofertas
     */
    public function obtenerPrecio(Request $request, $oferta = null)
    {
        try {
            // Validar datos de entrada
            $request->validate([
                'url' => 'required|url',
                'tienda' => 'required|string',
                'variante' => 'nullable|string'
            ]);

            $url = $request->input('url');
            $tienda = $request->input('tienda');
            $variante = $request->input('variante');

            // Obtener información de la tienda desde la base de datos
            $tiendaModel = \App\Models\Tienda::where('nombre', $tienda)->first();
            
            if (!$tiendaModel) {
                return response()->json([
                    'success' => false,
                    'error' => "Tienda no encontrada en la base de datos: {$tienda}"
                ]);
            }

            // Normalizar nombre de tienda para buscar el controlador
            $nombreControlador = $this->normalizarNombreTienda($tienda);
            
            // Construir nombre de la clase del controlador
            $claseControlador = "App\\Http\\Controllers\\Scraping\\Tiendas\\{$nombreControlador}Controller";
            
            // Verificar si existe el controlador
            if (!class_exists($claseControlador)) {
                return response()->json([
                    'success' => false,
                    'error' => "Controlador no encontrado para la tienda: {$tienda}"
                ]);
            }

            // Instanciar el controlador de la tienda
            $controladorTienda = new $claseControlador();
            
            // Llamar al método obtenerPrecio del controlador de la tienda
            $response = $controladorTienda->obtenerPrecio($url, $variante, $tiendaModel, $oferta);
            
            // Verificar que la respuesta sea válida
            if (!$response || !method_exists($response, 'getData')) {
                return response()->json([
                    'success' => false,
                    'error' => 'Respuesta inválida del controlador de tienda'
                ]);
            }
            
            return $response;

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Datos de entrada inválidos: ' . implode(', ', $e->errors())
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error en ScrapingController: ' . $e->getMessage(), [
                'url' => $request->input('url'),
                'tienda' => $request->input('tienda'),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Error en el scraping: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Normalizar nombre de tienda para buscar el controlador correspondiente
     * Ejemplo: "EL Corte Inglés" -> "Elcorteingles"
     */
    private function normalizarNombreTienda($tienda)
    {
        // Convertir a minúsculas
        $normalizado = strtolower($tienda);
        
        // Eliminar espacios, acentos y caracteres especiales
        $normalizado = Str::ascii($normalizado);
        $normalizado = preg_replace('/[^a-z0-9]/', '', $normalizado);
        
        // Capitalizar primera letra
        $normalizado = ucfirst($normalizado);
        
        return $normalizado;
    }
}
