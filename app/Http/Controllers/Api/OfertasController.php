<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Producto;
use App\Services\SacarPrimeraOfertaDeUnProductoAplicadoDescuentosYChollos;
use App\Services\SignedUrlService;

class OfertasController extends Controller
{
    protected $signedUrlService;

    public function __construct(SignedUrlService $signedUrlService)
    {
        $this->signedUrlService = $signedUrlService;
    }
    /**
     * Obtiene las ofertas de un producto
     * 
     * GET /api/ofertas/{productoId}
     * Headers: X-Auth-Token, X-Fingerprint
     */
    public function index(Request $request, $productoId)
    {
        try {
            $producto = Producto::with('categoria')->findOrFail($productoId);
            
            // Obtener ofertas aplicando descuentos y chollos
            $servicioOfertas = new SacarPrimeraOfertaDeUnProductoAplicadoDescuentosYChollos();
            $ofertas = $servicioOfertas->obtenerTodas($producto);
            
            // Formatear ofertas igual que en la vista blade
            $ofertasArray = $this->formatearOfertas($ofertas, $producto);
            
            // Obtener especificaciones internas del producto (igual que EspecificacionesController)
            $especificaciones = $producto->categoria_especificaciones_internas_elegidas ?? null;
            $gruposDeOfertas = $producto->grupos_de_ofertas ?? null;
            
            // Generar columnasData igual que en EspecificacionesController
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
                'ofertas' => $ofertasArray,
                'especificaciones' => $especificaciones,
                'grupos_de_ofertas' => $gruposDeOfertas,
                'columnas_data' => $columnasData,
                'producto_id' => $producto->id,
                'producto_nombre' => $producto->nombre,
            ]);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Producto no encontrado',
                'code' => 'PRODUCT_NOT_FOUND'
            ], 404);
        } catch (\Exception $e) {
            \Log::error('Error obteniendo ofertas', [
                'producto_id' => $productoId,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Error al obtener ofertas',
                'code' => 'SERVER_ERROR'
            ], 500);
        }
    }
    
    /**
     * Formatea las ofertas igual que en unidades.blade.php
     */
    private function formatearOfertas($ofertas, $producto)
    {
        // Función recursiva para ofuscar especificaciones (igual que en blade)
        $ofuscarEspecificaciones = function($especificaciones) use (&$ofuscarEspecificaciones) {
            // Si no es array ni objeto, devolver tal cual
            if (!is_array($especificaciones) && !is_object($especificaciones)) {
                return $especificaciones;
            }
            
            // Convertir objeto a array si es necesario
            if (is_object($especificaciones)) {
                $especificaciones = (array) $especificaciones;
            }
            
            // Si está vacío, devolver array vacío
            if (empty($especificaciones)) {
                return [];
            }
            
            // Verificar si es un array numérico (índices secuenciales desde 0)
            $keys = array_keys($especificaciones);
            $isNumericArray = !empty($keys) && $keys === range(0, count($especificaciones) - 1);
            
            // Si es un array numérico, mantener la estructura y procesar recursivamente cada elemento
            if ($isNumericArray) {
                return array_map(function($item) use ($ofuscarEspecificaciones) {
                    return $ofuscarEspecificaciones($item);
                }, $especificaciones);
            }
            
            // Para arrays asociativos, ofuscar las claves específicas
            $resultado = [];
            foreach ($especificaciones as $clave => $valor) {
                // Ofuscar solo las claves descriptivas específicas (igual que en blade)
                $claveOfuscada = $clave;
                if ($clave === '_formatos') {
                    $claveOfuscada = '_f';
                } elseif ($clave === '_columnas') {
                    $claveOfuscada = '_c';
                } elseif ($clave === '_orden') {
                    $claveOfuscada = '_o';
                }
                
                // Recursivamente ofuscar valores que sean arrays u objetos
                if (is_array($valor) || is_object($valor)) {
                    $resultado[$claveOfuscada] = $ofuscarEspecificaciones($valor);
                } else {
                    $resultado[$claveOfuscada] = $valor;
                }
            }
            
            return $resultado;
        };
        
        return $ofertas->map(function($item) use ($producto, $ofuscarEspecificaciones) {
            try {
                // Formatear unidades
                $unidadesFormateadas = ($item->unidades == intval($item->unidades)) ? 
                    intval($item->unidades) : 
                    floatval($item->unidades);
                
                // Formatear precio_unidad según la unidad de medida
                $decimalesPrecioUnidad = ($producto->unidadDeMedida === 'unidadMilesima') ? 3 : 2;
                
                // Verificar que la relación tienda esté disponible
                if (!isset($item->tienda) || !$item->tienda) {
                    \Log::error('Oferta sin tienda:', ['oferta_id' => $item->id ?? 'N/A']);
                    return null;
                }
                
                // Verificar que precio_unidad sea válido
                if (!isset($item->precio_unidad) || $item->precio_unidad === null || $item->precio_unidad < 0) {
                    \Log::error('Oferta con precio_unidad inválido:', [
                        'oferta_id' => $item->id ?? 'N/A',
                        'precio_unidad' => $item->precio_unidad ?? 'null'
                    ]);
                    return null;
                }
                
                // Ofuscar especificaciones_internas
                $especificacionesOfuscadas = null;
                if ($item->especificaciones_internas !== null && !empty($item->especificaciones_internas)) {
                    try {
                        // Convertir a array si es string JSON
                        $especificaciones = is_string($item->especificaciones_internas) 
                            ? json_decode($item->especificaciones_internas, true) 
                            : $item->especificaciones_internas;
                        
                        if (is_array($especificaciones) || is_object($especificaciones)) {
                            $especificacionesOfuscadas = $ofuscarEspecificaciones($especificaciones);
                            // Asegurar que sea un array/objeto válido
                            if (!is_array($especificacionesOfuscadas) && !is_object($especificacionesOfuscadas)) {
                                $especificacionesOfuscadas = null;
                            }
                        } else {
                            $especificacionesOfuscadas = $especificaciones;
                        }
                    } catch (\Exception $e) {
                        \Log::error('Error al ofuscar especificaciones:', [
                            'oferta_id' => $item->id ?? 'N/A',
                            'error' => $e->getMessage(),
                            'especificaciones_raw' => $item->especificaciones_internas
                        ]);
                        // En caso de error, enviar sin ofuscar para no romper la funcionalidad
                        $especificacionesOfuscadas = is_string($item->especificaciones_internas) 
                            ? json_decode($item->especificaciones_internas, true) 
                            : $item->especificaciones_internas;
                    }
                }
                
                // Función para añadir parámetro cam a URLs
                $añadirCam = function($url) {
                    $request = request();
                    if (!$request->has('cam')) {
                        return $url;
                    }
                    
                    $cam = $request->get('cam');
                    if (!preg_match('/^[a-zA-Z0-9\-_]+$/', $cam)) {
                        return $url;
                    }
                    
                    $separator = strpos($url, '?') !== false ? '&' : '?';
                    return $url . $separator . 'cam=' . urlencode($cam);
                };
                
                // Generar URL firmada (solo para usuarios no autenticados)
                // Los usuarios autenticados pueden usar URLs directas
                $urlRedireccion = auth()->check() 
                    ? route('click.redirigir', ['ofertaId' => $item->id])
                    : $this->signedUrlService->generarUrlFirmada($item->id);
                
                return [
                    "id" => $item->id,
                    "nombre" => $item->tienda->nombre ?? 'N/A',
                    "tienda" => $item->tienda->nombre ?? 'N/A',
                    "logo" => asset('images/' . ($item->tienda->url_imagen ?? '')),
                    "envio_gratis" => $item->tienda->envio_gratis ?? '',
                    "envio_normal" => $item->tienda->envio_normal ?? '',
                    "unidades" => $unidadesFormateadas,
                    "unidades_originales" => $unidadesFormateadas,
                    "precio_total" => number_format($item->precio_total ?? 0, 2, ',', ''),
                    "precio_unidad" => number_format($item->precio_unidad ?? 0, $decimalesPrecioUnidad, ',', ''),
                    "descuentos" => $item->descuentos ?? '',
                    "url" => $añadirCam($urlRedireccion),
                    "especificaciones_internas" => $especificacionesOfuscadas,
                ];
            } catch (\Exception $e) {
                \Log::error('Error al procesar oferta:', [
                    'oferta_id' => $item->id ?? 'N/A',
                    'error' => $e->getMessage()
                ]);
                return null;
            }
        })->filter()->values();
    }
}

