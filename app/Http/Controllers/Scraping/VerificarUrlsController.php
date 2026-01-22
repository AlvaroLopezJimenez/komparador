<?php

namespace App\Http\Controllers\Scraping;

use App\Http\Controllers\Controller;
use App\Models\Producto;
use App\Models\OfertaProducto;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class VerificarUrlsController extends Controller
{
    public function index()
    {
        $productos = Producto::orderBy('nombre')->get();
        return view('admin.scraping.verificar-urls', compact('productos'));
    }

    public function verificarUrls(Request $request)
    {
        try {
            $request->validate([
                'urls' => 'required|string',
                'producto_id' => 'nullable|exists:productos,id'
            ]);

            $urls = array_filter(explode("\n", $request->urls));
            $productoId = $request->producto_id;
            
            // Primero, normalizar todas las URLs y detectar duplicados
            $urlsConNormalizacion = [];
            $urlsNormalizadasContador = [];
            
            foreach ($urls as $url) {
                $url = trim($url);
                if (empty($url)) continue;
                
                try {
                    $urlNormalizada = $this->normalizarUrl($url);
                    $urlsConNormalizacion[] = [
                        'original' => $url,
                        'normalizada' => $urlNormalizada
                    ];
                    
                    // Contar cuántas veces aparece cada URL normalizada
                    if (!isset($urlsNormalizadasContador[$urlNormalizada])) {
                        $urlsNormalizadasContador[$urlNormalizada] = 0;
                    }
                    $urlsNormalizadasContador[$urlNormalizada]++;
                } catch (\Exception $e) {
                    // Si hay error normalizando, mantener la original
                    $urlsConNormalizacion[] = [
                        'original' => $url,
                        'normalizada' => $url
                    ];
                    if (!isset($urlsNormalizadasContador[$url])) {
                        $urlsNormalizadasContador[$url] = 0;
                    }
                    $urlsNormalizadasContador[$url]++;
                }
            }
            
            // Identificar URLs únicas para procesar (solo la primera de cada grupo)
            $urlsProcesadas = [];
            $urlsUnicasParaProcesar = [];
            
            foreach ($urlsConNormalizacion as $urlData) {
                $urlNormalizada = $urlData['normalizada'];
                
                // Si es la primera vez que vemos esta URL normalizada, procesarla
                if (!isset($urlsProcesadas[$urlNormalizada])) {
                    $urlsProcesadas[$urlNormalizada] = true;
                    $urlsUnicasParaProcesar[] = $urlData;
                }
            }
            
            // Procesar solo las URLs únicas
            $resultadosProcesados = [];
            foreach ($urlsUnicasParaProcesar as $urlData) {
                $urlNormalizada = $urlData['normalizada'];
                
                try {
                    $resultado = $this->verificarUrlIndividual($urlNormalizada, $productoId);
                    $resultadosProcesados[$urlNormalizada] = $resultado;
                } catch (\Exception $e) {
                    $resultadosProcesados[$urlNormalizada] = [
                        'existe_mismo_producto' => false,
                        'existe_otros_productos' => false,
                        'oferta_mismo_producto' => null,
                        'ofertas_otros_productos' => [],
                        'error' => 'Error al procesar la URL: ' . $e->getMessage()
                    ];
                }
            }
            
            // Construir resultados finales EXCLUYENDO duplicados
            $resultados = [];
            $contadorDuplicadas = 0;
            $urlsDuplicadas = []; // Guardar las URLs originales que son duplicadas
            $urlsNormalizadasVistas = [];
            
            foreach ($urlsConNormalizacion as $urlData) {
                $url = $urlData['original'];
                $urlNormalizada = $urlData['normalizada'];
                $esDuplicada = false;
                
                // Verificar si es duplicada (ya vimos esta URL normalizada antes)
                if (in_array($urlNormalizada, $urlsNormalizadasVistas)) {
                    $esDuplicada = true;
                    $contadorDuplicadas++;
                    $urlsDuplicadas[] = $url; // Guardar la URL original duplicada
                    // NO agregar a resultados, saltar esta URL
                    continue;
                } else {
                    $urlsNormalizadasVistas[] = $urlNormalizada;
                }
                
                // Obtener el resultado procesado (solo se procesó una vez por URL normalizada)
                $resultado = $resultadosProcesados[$urlNormalizada] ?? [
                    'existe_mismo_producto' => false,
                    'existe_otros_productos' => false,
                    'oferta_mismo_producto' => null,
                    'ofertas_otros_productos' => [],
                    'error' => null
                ];
                
                // Preparar datos de ofertas para JSON
                $ofertaMismoProductoData = null;
                if ($resultado['oferta_mismo_producto']) {
                    $oferta = $resultado['oferta_mismo_producto'];
                    $ofertaMismoProductoData = [
                        'id' => $oferta->id,
                        'precio_total' => $oferta->precio_total,
                        'precio_unidad' => $oferta->precio_unidad,
                        'producto' => [
                            'id' => $oferta->producto->id,
                            'nombre' => $oferta->producto->nombre,
                            'marca' => $oferta->producto->marca,
                            'modelo' => $oferta->producto->modelo,
                            'talla' => $oferta->producto->talla,
                            'slug' => $oferta->producto->slug,
                            'ruta_completa' => $oferta->producto->ruta_completa ?? null,
                            'categoria' => $oferta->producto->categoria ? [
                                'slug' => $oferta->producto->categoria->slug
                            ] : null
                        ],
                        'tienda' => [
                            'id' => $oferta->tienda->id,
                            'nombre' => $oferta->tienda->nombre
                        ]
                    ];
                }
                
                $ofertasOtrosProductosData = [];
                foreach ($resultado['ofertas_otros_productos'] as $oferta) {
                    $ofertasOtrosProductosData[] = [
                        'id' => $oferta->id,
                        'precio_total' => $oferta->precio_total,
                        'precio_unidad' => $oferta->precio_unidad,
                        'producto' => [
                            'id' => $oferta->producto->id,
                            'nombre' => $oferta->producto->nombre,
                            'marca' => $oferta->producto->marca,
                            'modelo' => $oferta->producto->modelo,
                            'talla' => $oferta->producto->talla,
                            'slug' => $oferta->producto->slug,
                            'ruta_completa' => $oferta->producto->ruta_completa ?? null,
                            'categoria' => $oferta->producto->categoria ? [
                                'slug' => $oferta->producto->categoria->slug
                            ] : null
                        ],
                        'tienda' => [
                            'id' => $oferta->tienda->id,
                            'nombre' => $oferta->tienda->nombre
                        ]
                    ];
                }
                
                // Solo agregar a resultados si NO es duplicada
                $resultados[] = [
                    'url_original' => $url,
                    'url_normalizada' => $urlNormalizada,
                    'existe_mismo_producto' => $resultado['existe_mismo_producto'],
                    'existe_otros_productos' => $resultado['existe_otros_productos'],
                    'oferta_mismo_producto' => $ofertaMismoProductoData,
                    'ofertas_otros_productos' => $ofertasOtrosProductosData,
                    'error' => $resultado['error'] ?? null
                ];
            }
            
            return response()->json([
                'success' => true,
                'resultados' => $resultados,
                'total_duplicadas' => $contadorDuplicadas,
                'urls_duplicadas' => $urlsDuplicadas // URLs originales que son duplicadas
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error en verificarUrls: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar las URLs: ' . $e->getMessage()
            ], 500);
        }
    }

    private function normalizarUrl($url)
    {
        // Eliminar espacios y caracteres extra
        $url = trim($url);
        
        // Si la URL no empieza con http, añadir https://
        if (!preg_match('/^https?:\/\//', $url)) {
            $url = 'https://' . $url;
        }
        
        // Parsear la URL
        $parsedUrl = parse_url($url);
        
        if (!$parsedUrl) {
            return $url;
        }
        
        $scheme = $parsedUrl['scheme'] ?? 'https';
        $host = $parsedUrl['host'] ?? '';
        $path = $parsedUrl['path'] ?? '';
        $query = $parsedUrl['query'] ?? '';
        
        // Casos especiales por tienda
        if (str_contains($host, 'amazon')) {
            // Para Amazon, extraer el ASIN del path o del query
            // Los ASINs de Amazon tienen 10 caracteres alfanuméricos
            $asin = null;
            
            // Buscar ASIN en el path (formato /dp/ASIN o /gp/product/ASIN)
            if (preg_match('/\/(?:dp|gp\/product)\/([A-Z0-9]{10})/', $path, $matches)) {
                $asin = $matches[1];
            }
            // Si no se encuentra en el path, buscar en la URL completa (por si el parse_url no funcionó bien)
            elseif (preg_match('/\/(?:dp|gp\/product)\/([A-Z0-9]{10})/', $url, $matches)) {
                $asin = $matches[1];
            }
            
            if ($asin) {
                // Construir la URL normalizada: https://www.amazon.es/dp/ASIN
                $path = '/dp/' . $asin;
                // Para Amazon, mantener algunos parámetros específicos como ?smid=
                if (preg_match('/smid=([^&]+)/', $query, $smidMatch)) {
                    $path .= '?smid=' . $smidMatch[1];
                }
            } else {
                // Si no se encuentra ASIN, mantener el path original pero sin query params
                $path = preg_replace('/\?.*$/', '', $path);
            }
        } elseif (str_contains($host, 'miravia')) {
            // Para Miravia, extraer solo el ID del producto
            if (preg_match('/i(\d+)\.html/', $path, $matches)) {
                $path = '/p/i' . $matches[1] . '.html';
            }
        } else {
            // Para otras tiendas, eliminar parámetros de query
            // Pero mantener la estructura básica del path
            // Eliminar query parameters excepto en casos especiales
            $path = preg_replace('/\?.*$/', '', $path);
        }
        
        // Eliminar barra final si existe
        $path = rtrim($path, '/');
        
        // Reconstruir URL
        $urlNormalizada = $scheme . '://' . $host . $path;
        
        return $urlNormalizada;
    }

    private function verificarUrlIndividual($urlNormalizada, $productoId = null)
    {
        // Buscar todas las ofertas que coincidan con esta URL
        $todasLasOfertas = OfertaProducto::with(['producto', 'tienda'])->get();
        $ofertasCoincidentes = [];
        
        foreach ($todasLasOfertas as $oferta) {
            $ofertaUrlNormalizada = $this->normalizarUrl($oferta->url);
            
            // Comparación exacta
            if ($ofertaUrlNormalizada === $urlNormalizada) {
                $ofertasCoincidentes[] = $oferta;
                continue;
            }
            
            // Comparación sin barra final
            $ofertaUrlSinBarra = rtrim($ofertaUrlNormalizada, '/');
            $urlSinBarra = rtrim($urlNormalizada, '/');
            if ($ofertaUrlSinBarra === $urlSinBarra) {
                $ofertasCoincidentes[] = $oferta;
            }
        }
        
        $resultado = [
            'existe_mismo_producto' => false,
            'existe_otros_productos' => false,
            'oferta_mismo_producto' => null,
            'ofertas_otros_productos' => []
        ];
        
        if (empty($ofertasCoincidentes)) {
            return $resultado;
        }
        
        // Separar ofertas del mismo producto y de otros productos
        $ofertasMismoProducto = [];
        $ofertasOtrosProductos = [];
        
        foreach ($ofertasCoincidentes as $oferta) {
            if ($productoId && $oferta->producto_id == $productoId) {
                $ofertasMismoProducto[] = $oferta;
            } else {
                $ofertasOtrosProductos[] = $oferta;
            }
        }
        
        $resultado['existe_mismo_producto'] = !empty($ofertasMismoProducto);
        $resultado['existe_otros_productos'] = !empty($ofertasOtrosProductos);
        $resultado['oferta_mismo_producto'] = !empty($ofertasMismoProducto) ? $ofertasMismoProducto[0] : null;
        $resultado['ofertas_otros_productos'] = $ofertasOtrosProductos;
        
        return $resultado;
    }
}
