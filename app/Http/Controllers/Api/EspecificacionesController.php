<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Producto;

class EspecificacionesController extends Controller
{
    /**
     * Obtiene las especificaciones internas de un producto
     * 
     * GET /api/especificaciones/{productoId}
     * Headers: X-Auth-Token, X-Fingerprint
     */
    public function index(Request $request, $productoId)
    {
        try {
            $producto = Producto::with('categoria')->findOrFail($productoId);
            
            // Obtener especificaciones internas del producto
            $especificaciones = $producto->categoria_especificaciones_internas_elegidas ?? null;
            
            // Obtener grupos de ofertas si existen
            $gruposDeOfertas = $producto->grupos_de_ofertas ?? null;
            
            // Generar columnasData igual que en la vista blade
            $columnasData = null;
            $esUnidadUnica = ($producto->unidadDeMedida === 'unidadUnica');
            
            if ($esUnidadUnica && $producto->categoria_id_especificaciones_internas && $producto->categoria_especificaciones_internas_elegidas) {
                $categoriaEspecificaciones = \App\Models\Categoria::find($producto->categoria_id_especificaciones_internas);
                $especificacionesElegidas = $producto->categoria_especificaciones_internas_elegidas;
                
                // Combinar filtros de categoría y producto
                $filtrosCombinados = [];
                
                // Añadir filtros de categoría
                if ($categoriaEspecificaciones && $categoriaEspecificaciones->especificaciones_internas && 
                    isset($categoriaEspecificaciones->especificaciones_internas['filtros'])) {
                    $filtrosCombinados = array_merge($filtrosCombinados, $categoriaEspecificaciones->especificaciones_internas['filtros']);
                }
                
                // Añadir filtros del producto (si existen)
                if (isset($especificacionesElegidas['_producto']['filtros']) && 
                    is_array($especificacionesElegidas['_producto']['filtros'])) {
                    $filtrosCombinados = array_merge($filtrosCombinados, $especificacionesElegidas['_producto']['filtros']);
                }
                
                if (count($filtrosCombinados) > 0 && isset($especificacionesElegidas['_columnas'])) {
                    $columnasIds = $especificacionesElegidas['_columnas'] ?? [];
                    $filtros = $filtrosCombinados;
                    
                    // Crear mapa de líneas principales con sus datos
                    $columnasData = [];
                    foreach ($filtros as $filtro) {
                        if (in_array($filtro['id'], $columnasIds)) {
                            // Procesar sublíneas para añadir texto alternativo si existe
                            $subprincipales = [];
                            foreach ($filtro['subprincipales'] ?? [] as $sub) {
                                // Buscar datos de la sublínea en las elegidas
                                $sublineaData = null;
                                if (isset($especificacionesElegidas[$filtro['id']]) && is_array($especificacionesElegidas[$filtro['id']])) {
                                    $sublineaData = collect($especificacionesElegidas[$filtro['id']])->first(function($item) use ($sub) {
                                        if (!is_array($item) || !isset($item['id'])) return false;
                                        return (string)$item['id'] === (string)$sub['id'];
                                    });
                                }
                                
                                // Si tiene texto alternativo, usarlo
                                if (is_array($sublineaData) && isset($sublineaData['textoAlternativo']) && !empty($sublineaData['textoAlternativo'])) {
                                    $sub['texto'] = $sublineaData['textoAlternativo'];
                                }
                                
                                $subprincipales[] = $sub;
                            }
                            
                            $columnasData[] = [
                                'id' => $filtro['id'],
                                'texto' => $filtro['texto'],
                                'subprincipales' => $subprincipales
                            ];
                        }
                    }
                    
                    // Ordenar según _orden si existe
                    if (isset($especificacionesElegidas['_orden']) && is_array($especificacionesElegidas['_orden'])) {
                        $orden = $especificacionesElegidas['_orden'];
                        usort($columnasData, function($a, $b) use ($orden) {
                            $posA = array_search($a['id'], $orden);
                            $posB = array_search($b['id'], $orden);
                            if ($posA === false) $posA = 999;
                            if ($posB === false) $posB = 999;
                            return $posA - $posB;
                        });
                    }
                }
            }
            
            return response()->json([
                'success' => true,
                'especificaciones' => $especificaciones,
                'grupos_de_ofertas' => $gruposDeOfertas,
                'columnas_data' => $columnasData, // Añadir columnas_data procesado
                'producto_id' => $producto->id,
            ]);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Producto no encontrado',
                'code' => 'PRODUCT_NOT_FOUND'
            ], 404);
        } catch (\Exception $e) {
            \Log::error('Error obteniendo especificaciones', [
                'producto_id' => $productoId,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Error al obtener especificaciones',
                'code' => 'SERVER_ERROR'
            ], 500);
        }
    }
}

