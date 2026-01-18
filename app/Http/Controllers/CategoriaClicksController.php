<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Categoria;
use App\Models\Producto;
use App\Models\OfertaProducto;

use Carbon\Carbon;

class CategoriaClicksController extends Controller
{
    public function ejecutar()
    {
        return view('admin.categorias.actualizar-clicks');
    }

    public function procesar(Request $request = null)
    {
        // Crear registro de ejecución
        $ejecucion = \App\Models\EjecucionGlobal::create([
            'inicio' => now(),
            'nombre' => 'ejecuciones_actualizar_clicks_categoria',
            'log' => [],
        ]);

        try {
            // Número de días para buscar clicks (configurable)
            $diasBusqueda = 7; // Este número se puede modificar más adelante
            
            // Obtener todas las categorías
            $categorias = Categoria::all();
            $totalCategorias = $categorias->count();
            $categoriasProcesadas = 0;

            foreach ($categorias as $categoria) {
                // Obtener todos los IDs de categorías hijas (recursivamente)
                $categoriaIds = $this->obtenerCategoriaIds($categoria->id);
                
                // Obtener productos de esta categoría y sus hijas
                $productos = Producto::whereIn('categoria_id', $categoriaIds)->get();
                
                $totalClicks = 0;
                
                foreach ($productos as $producto) {
                    // Obtener clicks de las ofertas de este producto de los últimos X días
                    $fechaInicio = Carbon::now()->subDays($diasBusqueda);
                    
                    $clicksOfertas = \App\Models\Click::whereHas('oferta', function($query) use ($producto) {
                        $query->where('producto_id', $producto->id);
                    })
                    ->where('created_at', '>=', $fechaInicio)
                    ->count();
                    
                    $totalClicks += $clicksOfertas;
                }
                
                // Actualizar clicks de la categoría
                $categoria->update(['clicks' => $totalClicks]);
                
                $categoriasProcesadas++;
                
                // Enviar progreso al frontend si es necesario
                if ($request && $request->ajax()) {
                    $progreso = round(($categoriasProcesadas / $totalCategorias) * 100);
                    return response()->json([
                        'progreso' => $progreso,
                        'categorias_procesadas' => $categoriasProcesadas,
                        'total_categorias' => $totalCategorias,
                        'categoria_actual' => $categoria->nombre
                    ]);
                }
            }
            
            // Actualizar ejecución como completada
            $ejecucion->update([
                'fin' => now(),
                'total' => $totalCategorias,
                'total_guardado' => $categoriasProcesadas,
                'total_errores' => 0,
                'log' => [
                    'resultado' => "Se procesaron {$categoriasProcesadas} categorías exitosamente"
                ],
            ]);
            
            return response()->json([
                'success' => true,
                'message' => "Se actualizaron los clicks de {$categoriasProcesadas} categorías exitosamente"
            ]);
            
        } catch (\Exception $e) {
            // Actualizar ejecución como fallida
            $ejecucion->update([
                'fin' => now(),
                'total' => 0,
                'total_guardado' => 0,
                'total_errores' => 1,
                'log' => [
                    'error' => $e->getMessage()
                ],
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar clicks: ' . $e->getMessage()
            ], 500);
        }
    }

    public function ejecuciones()
    {
        $ejecuciones = \App\Models\EjecucionGlobal::where('nombre', 'ejecuciones_actualizar_clicks_categoria')
            ->orderBy('created_at', 'desc')
            ->paginate(20);
            
        return view('admin.categorias.ejecuciones-clicks', compact('ejecuciones'));
    }

    private function obtenerCategoriaIds($categoriaId)
    {
        $ids = [$categoriaId];
        
        $subcategorias = Categoria::where('parent_id', $categoriaId)->get();
        
        foreach ($subcategorias as $subcategoria) {
            $ids = array_merge($ids, $this->obtenerCategoriaIds($subcategoria->id));
        }
        
        return $ids;
    }
} 