<?php

namespace App\Http\Controllers\Scraping;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Controllers\Scraping\PeticionApiHTMLController;

class TestController extends Controller
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
     * Procesar URL de testing y devolver:
     *  - Proveedores HTML: { success, html, url, proveedor }
     *  - AliExpress Open:  { success, raw, precio?, skus[], url, proveedor:'ALIEXPRESS_OPEN' }
     * En error, intentamos incluir 'raw' y claves para debug.
     */
    public function procesarUrl(Request $request)
    {
        $request->validate([
            'url' => 'required|url',
            'api' => 'nullable|string',
        ]);

        $url = (string) $request->input('url');
        $api = (string) $request->input('api', '');

        try {
            // Deja que PeticionApiHTMLController haga el mapeo según $api (incluye 'aliexpressOpen')
            $resultado = $api
                ? $this->apiHTML->obtenerHTML($url, null, $api)
                : $this->apiHTML->obtenerHTML($url);

            // Si falla la llamada, devolvemos error con detalles y raw si existiera
            if (!is_array($resultado) || empty($resultado['success'])) {
                return response()->json([
                    'success'    => false,
                    'url'        => $url,
                    'error'      => is_array($resultado) ? ($resultado['error'] ?? 'Error desconocido') : 'Respuesta inválida',
                    'proveedor'  => is_array($resultado) ? ($resultado['proveedor'] ?? 'desconocido') : 'desconocido',
                    'raw'        => is_array($resultado) ? ($resultado['raw'] ?? null) : null,
                    'debug_keys' => is_array($resultado) ? array_keys($resultado) : null,
                ]);
            }

            // Caso 1: Proveedores de SCRAPING tradicional (HTML disponible)
            if (isset($resultado['html']) && is_string($resultado['html'])) {
                return response()->json([
                    'success'   => true,
                    'url'       => $url,
                    'proveedor' => $resultado['proveedor'] ?? 'desconocido',
                    'html'      => (string) $resultado['html'],
                ]);
            }

            // Caso 2: AliExpress Open (JSON), Amazon API u otros proveedores JSON
            // Detectamos por presencia de raw/precio/skus o por proveedor explícito
            $prov = strtoupper((string)($resultado['proveedor'] ?? ''));
            if (isset($resultado['raw']) || isset($resultado['precio']) || isset($resultado['skus']) || $prov === 'ALIEXPRESS_OPEN' || $prov === 'AMAZON_API' || $prov === 'AMAZON_PRODUCT_INFO') {
                return response()->json([
                    'success'   => true,
                    'url'       => $url,
                    'proveedor' => $resultado['proveedor'] ?? 'ALIEXPRESS_OPEN',
                    'precio'    => (isset($resultado['precio']) && is_numeric($resultado['precio'])) ? (float)$resultado['precio'] : null,
                    'skus'      => (!empty($resultado['skus']) && is_array($resultado['skus'])) ? $resultado['skus'] : [],
                    'raw'       => $resultado['raw'] ?? null,
                ]);
            }

            // Si llegamos aquí, el proveedor respondió "success", pero no trajo ni html ni JSON utilizable
            return response()->json([
                'success'    => false,
                'url'        => $url,
                'error'      => 'Proveedor respondió sin html ni datos de API',
                'proveedor'  => $resultado['proveedor'] ?? 'desconocido',
                'debug_keys' => array_keys($resultado),
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error'   => 'Error de conexión: ' . $e->getMessage(),
                'url'     => $url,
            ]);
        }
    }
}
