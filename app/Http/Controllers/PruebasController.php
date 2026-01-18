<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Services\SacarPrimeraOfertaDeUnProductoAplicadoDescuentosYChollos;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PruebasController extends Controller
{
    /**
     * Mostrar la vista de listar ofertas de productos
     */
    public function index()
    {
        return view('admin.pruebas.listar-ofertas');
    }

    /**
     * Buscar productos para el autocompletado
     */
    public function buscarProductos(Request $request)
    {
        $query = $request->input('q', '');
        
        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $productos = Producto::where(function($q) use ($query) {
                $q->where('nombre', 'like', '%' . $query . '%')
                  ->orWhere('marca', 'like', '%' . $query . '%')
                  ->orWhere('modelo', 'like', '%' . $query . '%')
                  ->orWhere('talla', 'like', '%' . $query . '%');
            })
            ->limit(20)
            ->get()
            ->map(function($producto) {
                return [
                    'id' => $producto->id,
                    'texto_completo' => $producto->nombre . ' - ' . $producto->marca . ' - ' . $producto->modelo . ' - ' . $producto->talla
                ];
            });

        return response()->json($productos);
    }

    /**
     * Obtener todas las ofertas de un producto (después de aplicar descuentos y chollos)
     */
    public function obtenerOfertasProducto(Request $request)
    {
        $productoId = $request->input('producto_id');

        if (!$productoId) {
            return response()->json([
                'success' => false,
                'message' => 'Debes seleccionar un producto'
            ], 400);
        }

        $producto = Producto::find($productoId);

        if (!$producto) {
            return response()->json([
                'success' => false,
                'message' => 'Producto no encontrado'
            ], 404);
        }

        // Usar el servicio para obtener todas las ofertas procesadas
        $servicioOfertas = new SacarPrimeraOfertaDeUnProductoAplicadoDescuentosYChollos();
        $ofertas = $servicioOfertas->obtenerTodas($producto);

        if ($ofertas->isEmpty()) {
            return response()->json([
                'success' => true,
                'ofertas' => [],
                'message' => 'No se encontraron ofertas para este producto'
            ]);
        }

        // Formatear las ofertas para la respuesta
        // Las ofertas ya vienen ordenadas por precio_unidad del servicio
        $ofertasFormateadas = $ofertas->map(function($oferta) {
            // Asegurar que tenemos acceso a la tienda
            $tiendaNombre = 'Tienda desconocida';
            if (isset($oferta->tienda)) {
                $tiendaNombre = $oferta->tienda->nombre;
            } elseif (isset($oferta->tienda_id)) {
                // Si no está cargada la relación, intentar obtenerla
                $tienda = \App\Models\Tienda::find($oferta->tienda_id);
                if ($tienda) {
                    $tiendaNombre = $tienda->nombre;
                }
            }
            
            return [
                'id' => $oferta->id,
                'tienda_nombre' => $tiendaNombre,
                'unidades' => number_format($oferta->unidades, 3, ',', '.'),
                'precio_total' => number_format($oferta->precio_total, 2, ',', '.') . ' €',
                'precio_unidad' => number_format($oferta->precio_unidad, 4, ',', '.') . ' €',
                'precio_unidad_raw' => $oferta->precio_unidad, // Para ordenar
                'url' => $oferta->url ?? '#',
                'mostrar' => $oferta->mostrar ?? 'si'
            ];
        })->values();

        return response()->json([
            'success' => true,
            'ofertas' => $ofertasFormateadas,
            'total' => $ofertasFormateadas->count(),
            'producto' => [
                'id' => $producto->id,
                'nombre' => $producto->nombre,
                'marca' => $producto->marca,
                'modelo' => $producto->modelo,
                'talla' => $producto->talla
            ]
        ]);
    }
}

