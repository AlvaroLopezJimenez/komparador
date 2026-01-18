<?php

namespace App\Http\Controllers\Scraping;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Controllers\Scraping\PeticionApiHTMLController;

class TestScrapingController extends Controller
{
    private $apiHTML;

    public function __construct()
    {
        $this->apiHTML = new PeticionApiHTMLController();
    }

    /**
     * Mostrar vista de testing de scraping
     */
    public function index()
    {
        return view('admin.scraping.test');
    }

    /**
     * Procesar URL de testing y devolver HTML
     */
    public function procesarUrl(Request $request)
    {
        $request->validate([
            'url' => 'required|url',
            'api' => 'nullable|string'
        ]);

        $url = $request->input('url');
        $api = $request->input('api');
        
        try {
            // Si se especifica una API, usarla directamente
            if ($api) {
                $resultado = $this->apiHTML->obtenerHTML($url, null, $api);
            } else {
                // Si no se especifica, usar el proveedor por defecto
                $resultado = $this->apiHTML->obtenerHTML($url);
            }
            
            if ($resultado['success']) {
                return response()->json([
                    'success' => true,
                    'html' => $resultado['html'],
                    'url' => $url,
                    'proveedor' => $resultado['proveedor'] ?? 'desconocido'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => $resultado['error'],
                    'url' => $url
                ]);
            }
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Error de conexión: ' . $e->getMessage(),
                'url' => $url
            ]);
        }
    }
}
