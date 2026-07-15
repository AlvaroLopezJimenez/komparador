<?php

namespace App\Http\Controllers\Scraping;

use App\Http\Controllers\Controller;
use App\Http\Controllers\DescuentosController;
use App\Models\OfertaProducto;
use App\Services\CsvAwinOfertaService;
use App\Services\TiendaScrapingConfigResolver;
use App\Support\UrlOfertaValidacion;
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
                'url' => UrlOfertaValidacion::rules(),
                'tienda' => 'required|string',
                'variante' => 'nullable|string',
                'producto_id' => 'nullable|integer|exists:productos,id',
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

            $apiForzada = $request->input('api_forzada');
            if (is_string($apiForzada) && $apiForzada !== '') {
                $apiEfectiva = $apiForzada;
            } else {
                $apiEfectiva = $tiendaModel->api;

                // API efectiva: categoría del producto si está configurada, si no la de la tienda
                $categoriaId = null;
                if ($oferta !== null) {
                    $oferta->loadMissing('producto');
                    $categoriaId = $oferta->producto?->categoria_id;
                } elseif ($request->filled('producto_id')) {
                    $categoriaId = \App\Models\Producto::whereKey($request->input('producto_id'))->value('categoria_id');
                }

                if ($categoriaId !== null || $oferta !== null) {
                    $apiEfectiva = app(TiendaScrapingConfigResolver::class)->resolverApi(
                        $tiendaModel,
                        $categoriaId !== null ? (int) $categoriaId : null
                    ) ?? $apiEfectiva;
                }
            }

            if ($apiEfectiva === TiendaScrapingConfigResolver::API_CSV_AWIN) {
                $response = app(CsvAwinOfertaService::class)->obtenerPrecioJson($url, $tiendaModel, $oferta);

                return $this->respuestaConDescuentosOfertaSiCorresponde($response, $oferta);
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

            if ($apiEfectiva !== null && $apiEfectiva !== $tiendaModel->api) {
                $tiendaModel = clone $tiendaModel;
                $tiendaModel->api = $apiEfectiva;
            }
            
            // Llamar al método obtenerPrecio del controlador de la tienda
            $response = $controladorTienda->obtenerPrecio($url, $variante, $tiendaModel, $oferta);

            return $this->respuestaConDescuentosOfertaSiCorresponde($response, $oferta);

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
     * Tras un scraping con oferta, los controladores de tienda pueden haber actualizado
     * ofertas_producto.descuentos. Devolvemos ese estado al formulario sin tocar cada tienda.
     *
     * @param  mixed  $response
     */
    private function respuestaConDescuentosOfertaSiCorresponde($response, $oferta)
    {
        if (!$response || !method_exists($response, 'getData')) {
            return response()->json([
                'success' => false,
                'error' => 'Respuesta inválida del controlador de tienda',
            ]);
        }

        $data = $response->getData(true);

        if ($oferta instanceof OfertaProducto && !empty($data['success'])) {
            $oferta->refresh();
            $descuentos = $oferta->descuentos ?? '';

            $data['descuentos'] = $descuentos;
            $data['descuentos_detectados'] = DescuentosController::parseDescuentos($descuentos);
            $data['descuentos_sincronizados'] = true;
        }

        return response()->json($data);
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
