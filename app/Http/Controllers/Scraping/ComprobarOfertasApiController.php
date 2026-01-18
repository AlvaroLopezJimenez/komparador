<?php

namespace App\Http\Controllers\Scraping;

use App\Http\Controllers\Controller;
use App\Models\OfertaProducto;
use App\Models\Tienda;
use App\Models\Producto;
use App\Http\Controllers\Scraping\PeticionApiHTMLController;
use App\Http\Controllers\Scraping\Tiendas\AliexpressController;
use App\Services\CalcularPrecioUnidad;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ComprobarOfertasApiController extends Controller
{

    /**
     * Procesar una oferta individual con la API
     */
    public function procesarOferta(Request $request)
    {
        $request->validate([
            'oferta_id' => 'required|exists:ofertas_producto,id'
        ]);

        $oferta = OfertaProducto::with(['producto', 'tienda'])->findOrFail($request->oferta_id);
        
        try {
            // Verificar que la tienda tenga una API configurada
            if (!$oferta->tienda || !$oferta->tienda->api) {
                return response()->json([
                    'success' => false,
                    'error' => 'La tienda no tiene API configurada',
                    'oferta_id' => $oferta->id,
                    'url' => $oferta->url,
                    'producto_nombre' => $oferta->producto->nombre ?? 'Sin nombre',
                    'mostrar' => $oferta->mostrar
                ]);
            }

            // Usar la misma lógica que DiagnosticoController para encontrar el controlador
            $controladoresTiendas = $this->obtenerControladoresTiendas();
            $controladorExiste = $this->verificarControladorTienda($oferta->tienda->nombre, $controladoresTiendas);
            
            if (!$controladorExiste) {
                return response()->json([
                    'success' => false,
                    'error' => 'No existe controlador para la tienda: ' . $oferta->tienda->nombre,
                    'oferta_id' => $oferta->id,
                    'url' => $oferta->url,
                    'producto_nombre' => $oferta->producto->nombre ?? 'Sin nombre',
                    'mostrar' => $oferta->mostrar
                ]);
            }
            
            // Usar el mismo sistema que TestPrecioController con timeout
            $scrapingController = new ScrapingController();
            $scrapingRequest = new \Illuminate\Http\Request();
            $scrapingRequest->merge([
                'url' => $oferta->url,
                'tienda' => $oferta->tienda->nombre,
                'variante' => null
            ]);
            
            // Establecer timeout para evitar que se cuelgue
            set_time_limit(30); // 30 segundos máximo
            
            $response = $scrapingController->obtenerPrecio($scrapingRequest, $oferta);
            
            // Si la respuesta no es válida, devolver error
            if (!$response || !method_exists($response, 'getData')) {
                return response()->json([
                    'success' => false,
                    'error' => 'No se pudo obtener respuesta del sistema de scraping',
                    'oferta_id' => $oferta->id,
                    'url' => $oferta->url,
                    'producto_nombre' => $oferta->producto->nombre ?? 'Sin nombre',
                    'mostrar' => $oferta->mostrar
                ]);
            }
            
            $responseData = $response->getData(true);
            
            // Verificar si se obtuvo un precio válido
            $precio = $responseData['precio'] ?? null;
            $success = $responseData['success'] ?? false;
            $error = $responseData['error'] ?? null;
            
            // Si no hay precio pero tampoco hay error específico, considerar como "sin precio"
            if (!$success && !$error) {
                $error = 'No se pudo obtener el precio de la oferta';
            }
            
            // Adaptar la respuesta al formato esperado
            return response()->json([
                'success' => $success,
                'precio' => $precio,
                'error' => $error,
                'oferta_id' => $oferta->id,
                'url' => $oferta->url,
                'producto_nombre' => $oferta->producto->nombre ?? 'Sin nombre',
                'mostrar' => $oferta->mostrar
            ]);

        } catch (\Exception $e) {
            // Log del error para debugging
            \Log::error('Error procesando oferta API: ' . $e->getMessage(), [
                'oferta_id' => $oferta->id,
                'url' => $oferta->url,
                'tienda' => $oferta->tienda->nombre ?? 'Sin tienda'
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Error interno: ' . $e->getMessage(),
                'oferta_id' => $oferta->id,
                'url' => $oferta->url,
                'producto_nombre' => $oferta->producto->nombre ?? 'Sin nombre',
                'mostrar' => $oferta->mostrar
            ]);
        }
    }


    /**
     * Guardar precio y actualizar mostrar
     */
    public function guardarPrecio(Request $request)
    {
        $request->validate([
            'oferta_id' => 'required|exists:ofertas_producto,id',
            'precio' => 'nullable|numeric|min:0',
            'precio_total' => 'nullable|numeric|min:0'
        ]);

        try {
            $oferta = OfertaProducto::with('producto')->findOrFail($request->oferta_id);
            
            $precioUnidad = null;
            
            // Si se recibe precio_total, calcular precio_unidad usando el servicio
            if ($request->filled('precio_total')) {
                $producto = $oferta->producto;
                
                if ($producto) {
                    $calcularPrecioUnidad = new CalcularPrecioUnidad();
                    $precioUnidad = $calcularPrecioUnidad->calcular(
                        $producto->unidadDeMedida ?? 'unidad',
                        $request->precio_total,
                        $oferta->unidades
                    );
                    
                    if ($precioUnidad !== null) {
                        $oferta->precio_unidad = $precioUnidad;
                        $oferta->precio_total = $request->precio_total;
                    }
                }
            } elseif ($request->filled('precio')) {
                // Si solo se recibe precio (precio_unidad), usarlo directamente
                $precioUnidad = $request->precio;
                $oferta->precio_unidad = $precioUnidad;
            }
            
            // Actualizar mostrar
            $oferta->mostrar = 'si';
            $oferta->save();

            // También actualizar el precio del producto si es necesario
            if ($oferta->producto && $precioUnidad !== null) {
                $producto = $oferta->producto;
                // Solo actualizar si el nuevo precio es menor al actual o si no tiene precio
                if ($producto->precio === null || $precioUnidad < $producto->precio) {
                    $producto->precio = $precioUnidad;
                    $producto->save();
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Precio guardado y oferta marcada como visible',
                'precio_unidad' => $precioUnidad ?? $oferta->precio_unidad // Devolver el precio_unidad calculado
            ]);

        } catch (\Exception $e) {
            Log::error('Error guardando precio: ' . $e->getMessage(), [
                'oferta_id' => $request->oferta_id,
                'precio' => $request->precio,
                'precio_total' => $request->precio_total
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error al guardar: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Obtener lista de controladores de tiendas disponibles
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
        
        return $controladores;
    }

    /**
     * Normalizar nombre de tienda para comparación
     */
    private function normalizarNombreTienda($nombreTienda)
    {
        // Convertir a minúsculas y quitar espacios, guiones, puntos, etc.
        $normalizado = strtolower($nombreTienda);
        $normalizado = preg_replace('/[^a-z0-9]/', '', $normalizado);
        return $normalizado;
    }

    /**
     * Verificar si existe controlador para una tienda
     */
    private function verificarControladorTienda($nombreTienda, $controladoresTiendas)
    {
        $nombreNormalizado = $this->normalizarNombreTienda($nombreTienda);
        
        foreach ($controladoresTiendas as $controlador) {
            $controladorNormalizado = $this->normalizarNombreTienda($controlador);
            if ($nombreNormalizado === $controladorNormalizado) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Mostrar vista de testing de ofertas API
     */
    public function test(Request $request)
    {
        // Obtener tiendas disponibles
        $tiendas = \App\Models\Tienda::pluck('nombre')->toArray();
        
        return view('admin.scraping.comprobar-ofertas-api.test-bulk', compact('tiendas'));
    }

    /**
     * Buscar ofertas según criterios
     */
    public function buscarOfertas(Request $request)
    {
        $request->validate([
            'tienda' => 'required|string',
            'mostrar' => 'required|in:si,no',
            'limite_ofertas' => 'nullable|integer|min:1',
            'tiempo_entre_peticiones' => 'required|integer|min:1|max:60'
        ]);

        $tienda = $request->input('tienda');
        $mostrar = $request->input('mostrar');
        $limiteOfertas = $request->input('limite_ofertas');
        $tiempoEntrePeticiones = $request->input('tiempo_entre_peticiones');

        // Obtener ofertas según los criterios
        $query = OfertaProducto::with(['producto', 'tienda'])
            ->whereHas('tienda', function($q) use ($tienda) {
                $q->where('nombre', $tienda);
            })
            ->where('mostrar', $mostrar);

        if ($limiteOfertas) {
            $query->limit($limiteOfertas);
        }

        $ofertas = $query->get();

        // Añadir información de avisos a cada oferta
        foreach ($ofertas as $oferta) {
            $oferta->tiene_avisos = \App\Models\Aviso::where('avisoable_type', 'App\Models\OfertaProducto')
                ->where('avisoable_id', $oferta->id)
                ->where('oculto', false)
                ->exists();
        }

        return response()->json([
            'success' => true,
            'ofertas' => $ofertas,
            'total' => $ofertas->count(),
            'tienda' => $tienda,
            'mostrar' => $mostrar,
            'limite_ofertas' => $limiteOfertas,
            'tiempo_entre_peticiones' => $tiempoEntrePeticiones
        ]);
    }

    /**
     * Obtener avisos de una oferta específica
     */
    public function obtenerAvisosOferta(Request $request)
    {
        $request->validate([
            'oferta_id' => 'required|exists:ofertas_producto,id'
        ]);

        try {
            $oferta = OfertaProducto::findOrFail($request->oferta_id);
            
            // Obtener avisos de la oferta
            $avisos = \App\Models\Aviso::where('avisoable_type', 'App\Models\OfertaProducto')
                ->where('avisoable_id', $oferta->id)
                ->where('oculto', false)
                ->with('user')
                ->orderBy('fecha_aviso', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'avisos' => $avisos,
                'total' => $avisos->count(),
                'oferta_id' => $oferta->id,
                'producto_nombre' => $oferta->producto->nombre ?? 'Sin nombre'
            ]);

        } catch (\Exception $e) {
            Log::error('Error obteniendo avisos de oferta: ' . $e->getMessage(), [
                'oferta_id' => $request->oferta_id
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error al obtener avisos: ' . $e->getMessage()
            ]);
        }
    }
}
