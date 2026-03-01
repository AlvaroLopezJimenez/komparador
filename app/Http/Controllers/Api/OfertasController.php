<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Producto;
use App\Models\OfertaProducto;
use App\Models\Tienda;
use App\Models\ProductoOfertaMasBarataPorProducto;
use App\Services\SacarPrimeraOfertaDeUnProductoAplicadoDescuentosYChollos;
use App\Services\SignedUrlService;
use App\Services\CalcularPrecioUnidad;

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
                
                // Obtener el valor de envío de la oferta si existe
                $envioOferta = null;
                if (isset($item->envio) && $item->envio !== null && $item->envio > 0) {
                    $envioOferta = number_format($item->envio, 2, ',', '');
                }
                
                return [
                    "id" => $item->id,
                    "nombre" => $item->tienda->nombre ?? 'N/A',
                    "tienda" => $item->tienda->nombre ?? 'N/A',
                    "logo" => asset('images/' . ($item->tienda->url_imagen ?? '')),
                    "envio_gratis" => $item->tienda->envio_gratis ?? '',
                    "envio_normal" => $item->tienda->envio_normal ?? '',
                    "envio" => $envioOferta,
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

    /**
     * Vista para crear ofertas en masa a partir de una lista de URLs
     */
    public function crearMasivoVista()
    {
        return view('admin.ofertas.crear-masivo');
    }

    /**
     * Recarga las especificaciones internas de un producto (para crear-masivo, sin recargar página)
     * GET /panel-privado/ofertas/crear-masivo/recargar-especificaciones/{producto}
     */
    public function recargarEspecificaciones($productoId)
    {
        $especificaciones = $this->obtenerEspecificacionesProducto($productoId);
        $tieneEspecificaciones = $especificaciones && !empty($especificaciones['filtros'] ?? []);

        return response()->json([
            'success' => true,
            'especificaciones' => $especificaciones,
            'tiene_especificaciones' => $tieneEspecificaciones,
        ]);
    }

    /**
     * Analiza una lista de URLs: producto candidato, tienda, si existe, especificaciones
     * POST /panel-privado/ofertas/crear-masivo/analizar
     */
    public function analizarUrls(Request $request)
    {
        $request->validate(['urls' => 'required|string']);

        $lineas = array_filter(array_map('trim', explode("\n", $request->urls)));
        $urlsParaProcesar = [];
        foreach ($lineas as $url) {
            if (empty($url)) continue;
            $urlParaValidar = preg_match('/^https?:\/\//', $url) ? $url : 'https://' . $url;
            if (!filter_var($urlParaValidar, FILTER_VALIDATE_URL)) {
                $urlsParaProcesar[] = ['url' => $url, 'normalizada' => null, 'error' => 'URL no válida'];
                continue;
            }
            $normalizada = $this->normalizarUrl($url);
            if (!collect($urlsParaProcesar)->contains('normalizada', $normalizada)) {
                $urlsParaProcesar[] = ['url' => $url, 'normalizada' => $normalizada];
            }
        }

        $todasLasTiendas = Tienda::select('id', 'nombre', 'url', 'envio_gratis', 'envio_normal', 'como_scrapear')
            ->orderBy('nombre')
            ->get();

        $resultados = [];
        foreach ($urlsParaProcesar as $data) {
            if (isset($data['error'])) {
                $resultados[] = [
                    'url' => $data['url'],
                    'url_normalizada' => $data['url'],
                    'existe' => false,
                    'existe_mismo_producto' => false,
                    'existe_otros_productos' => false,
                    'ofertas_existentes' => [],
                    'tienda' => null,
                    'producto' => null,
                    'productos_candidatos' => [],
                    'especificaciones' => null,
                    'tiene_especificaciones' => false,
                    'error' => $data['error'],
                ];
                continue;
            }
            $url = $data['normalizada'];

            $item = [
                'url' => $data['url'],
                'url_normalizada' => $url,
                'existe' => false,
                'existe_mismo_producto' => false,
                'existe_otros_productos' => false,
                'ofertas_existentes' => [],
                'tienda' => null,
                'producto' => null,
                'productos_candidatos' => [],
                'especificaciones' => null,
                'tiene_especificaciones' => false,
                'error' => null,
            ];

            $verificacion = $this->verificarUrlExistente($url);
            if ($verificacion['existe_mismo_producto']) {
                $item['existe'] = true;
                $item['existe_mismo_producto'] = true;
                $item['ofertas_existentes'] = $verificacion['ofertas'];
            } elseif ($verificacion['existe_otros_productos']) {
                $item['existe'] = true;
                $item['existe_otros_productos'] = true;
                $item['ofertas_existentes'] = $verificacion['ofertas'];
            }

            $tienda = $this->detectarTiendaPorUrl($url, $todasLasTiendas);
            if ($tienda) {
                $item['tienda'] = [
                    'id' => $tienda->id,
                    'nombre' => $tienda->nombre,
                    'envio_gratis' => $tienda->envio_gratis ?? null,
                    'envio_normal' => $tienda->envio_normal ?? null,
                    'como_scrapear' => $tienda->como_scrapear ?? 'manual',
                ];
            } else {
                $item['error'] = ($item['error'] ?? '') . ' Tienda no detectada.';
            }

            if (!$item['existe']) {
                $productos = $this->buscarProductoPorUrl($url);
                if (!empty($productos)) {
                    $puntuacionMax = $productos[0]['puntuacion'] ?? 0;
                    $empatados = array_filter($productos, fn ($pr) => ($pr['puntuacion'] ?? 0) === $puntuacionMax);
                    foreach ($productos as &$pr) {
                        $especs = $this->obtenerEspecificacionesProducto($pr['id']);
                        $pr['especificaciones'] = $especs;
                        $pr['tiene_especificaciones'] = $especs && !empty($especs['filtros'] ?? []);
                    }
                    unset($pr);
                    $item['productos_candidatos'] = $productos;
                    $item['producto'] = $productos[0];
                    $item['especificaciones'] = $productos[0]['especificaciones'] ?? null;
                    $item['tiene_especificaciones'] = $productos[0]['tiene_especificaciones'] ?? false;
                    $item['hay_empate'] = count($empatados) > 1;
                    $item['candidatos_empatados'] = array_values($empatados);
                } else {
                    $item['error'] = ($item['error'] ?? '') . ' Producto no encontrado.';
                }
            }

            $resultados[] = $item;
        }

        return response()->json(['success' => true, 'resultados' => array_values($resultados)]);
    }

    /**
     * Crea una oferta desde el flujo masivo (obtiene precio, aplica envío/tienda, guarda)
     * POST /panel-privado/ofertas/crear-masivo/crear
     */
    public function crearOfertaBulk(Request $request)
    {
        try {
        $request->validate([
            'url' => 'required|url',
            'producto_id' => 'required|exists:productos,id',
            'tienda_id' => 'required|exists:tiendas,id',
            'especificaciones_internas' => 'nullable|string',
        ]);

        $url = trim($request->url);
        $productoId = (int) $request->producto_id;
        $tiendaId = (int) $request->tienda_id;

        $producto = Producto::findOrFail($productoId);
        $tienda = Tienda::findOrFail($tiendaId);

        $scrapingController = new \App\Http\Controllers\Scraping\ScrapingController();
        $scrapingRequest = new Request([
            'url' => $url,
            'tienda' => $tienda->nombre,
            'variante' => null,
        ]);
        $response = $scrapingController->obtenerPrecio($scrapingRequest);
        $data = $response->getData(true);

        if (empty($data['success']) || !isset($data['precio']) || !is_numeric($data['precio'])) {
            return response()->json([
                'success' => false,
                'error' => $data['error'] ?? 'No se pudo obtener el precio',
            ], 400);
        }

        $precioTotal = (float) str_replace(',', '.', (string) $data['precio']);
        $unidades = ($producto->unidadDeMedida === 'unidadUnica') ? 1.0 : 1.0;

        $calcularPrecioUnidad = new CalcularPrecioUnidad();
        $precioUnidad = $calcularPrecioUnidad->calcular(
            $producto->unidadDeMedida ?? 'unidad',
            $precioTotal,
            $unidades
        );
        if ($precioUnidad === null) {
            $precioUnidad = $precioTotal / max(0.01, $unidades);
        }

        [$envio, $envioPlaceholderGratis] = $this->calcularEnvioDesdeTienda($tienda);
        $comoScrapear = $this->obtenerComoScrapearTienda($tienda);
        $frecuenciaMinutos = $this->obtenerFrecuenciaMasComunTienda($tiendaId);

        $especificacionesInternas = null;
        if ($request->filled('especificaciones_internas')) {
            $decoded = json_decode($request->especificaciones_internas, true);
            if (is_array($decoded) && !empty($decoded)) {
                $especificacionesInternas = $decoded;
            }
        }

        $precioCero = $precioTotal < 0.0001 || $precioUnidad < 0.0001;
        $mostrar = $precioCero ? 'no' : 'si';

        $datos = [
            'producto_id' => $productoId,
            'tienda_id' => $tiendaId,
            'unidades' => $unidades,
            'precio_total' => $precioTotal,
            'precio_unidad' => $precioUnidad,
            'envio' => $envioPlaceholderGratis ? null : $envio,
            'url' => $url,
            'variante' => null,
            'descuentos' => '',
            'mostrar' => $mostrar,
            'como_scrapear' => $comoScrapear,
            'anotaciones_internas' => null,
            'especificaciones_internas' => $especificacionesInternas,
            'frecuencia_actualizar_precio_minutos' => $frecuenciaMinutos,
            'fecha_actualizacion_envio' => now(),
        ];

        $oferta = OfertaProducto::create($datos);

        if ($precioCero) {
            \App\Models\Aviso::create([
                'texto_aviso' => 'Sin stock - 1a vez',
                'fecha_aviso' => now()->addDays(4)->setTime(0, 1, 0),
                'user_id' => auth()->id() ?? 1,
                'avisoable_type' => OfertaProducto::class,
                'avisoable_id' => $oferta->id,
                'oculto' => false,
            ]);
        }

        try {
            $servicioOfertas = new SacarPrimeraOfertaDeUnProductoAplicadoDescuentosYChollos();
            $ofertaMasBarata = $servicioOfertas->obtener($producto);
            if ($ofertaMasBarata) {
                $ofertaOriginal = OfertaProducto::find($ofertaMasBarata->id);
                if ($ofertaOriginal) {
                    ProductoOfertaMasBarataPorProducto::updateOrCreate(
                        ['producto_id' => $producto->id],
                        [
                            'oferta_id' => $ofertaOriginal->id,
                            'tienda_id' => $ofertaOriginal->tienda_id,
                            'precio_total' => $ofertaMasBarata->precio_total,
                            'precio_unidad' => $ofertaMasBarata->precio_unidad,
                            'unidades' => $ofertaOriginal->unidades,
                            'url' => $ofertaOriginal->url,
                        ]
                    );
                    $precioReal = $ofertaMasBarata->precio_unidad;
                    if ($precioReal > 0) {
                        $producto->precio = $producto->unidadDeMedida === 'unidadMilesima' ? round($precioReal, 3) : $precioReal;
                        $producto->save();
                    }
                }
            }
        } catch (\Exception $e) {
            \Log::error('Error recalcular oferta mas barata bulk: ' . $e->getMessage());
        }

        if (auth()->check()) {
            \App\Models\UserActivity::create([
                'user_id' => auth()->id(),
                'action_type' => \App\Models\UserActivity::ACTION_OFERTA_CREADA,
                'oferta_id' => $oferta->id,
            ]);
        }

        return response()->json([
            'success' => true,
            'oferta_id' => $oferta->id,
            'oferta_edit_url' => route('admin.ofertas.edit', $oferta),
            'mensaje' => 'Oferta creada correctamente',
        ]);

        } catch (\Throwable $e) {
            \Log::error('CrearOfertaBulk error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return response()->json([
                'success' => false,
                'error' => config('app.debug') ? $e->getMessage() . ' (L' . $e->getLine() . ')' : 'Error al crear la oferta',
            ], 500);
        }
    }

    private function normalizarUrl($url)
    {
        $url = trim($url);
        if (!preg_match('/^https?:\/\//', $url)) {
            $url = 'https://' . $url;
        }
        $parsed = parse_url($url);
        if (!$parsed) {
            return $url;
        }
        $host = $parsed['host'] ?? '';
        $path = $parsed['path'] ?? '';
        $path = preg_replace('/\?.*$/', '', $path);
        $path = rtrim($path, '/');
        return ($parsed['scheme'] ?? 'https') . '://' . $host . $path;
    }

    private function verificarUrlExistente($urlNormalizada)
    {
        $ofertas = OfertaProducto::with(['producto.categoria', 'tienda'])
            ->get()
            ->filter(function ($o) use ($urlNormalizada) {
                $on = $this->normalizarUrl($o->url);
                return $on === $urlNormalizada || rtrim($on, '/') === rtrim($urlNormalizada, '/');
            });

        $arr = $ofertas->map(function ($o) {
            $urlProducto = null;
            if ($o->producto && $o->producto->categoria) {
                $path = \App\Helpers\CategoriaHelper::construirUrlCategorias($o->producto->categoria->id, $o->producto->slug);
                $urlProducto = url($path);
            }
            return [
                'id' => $o->id,
                'producto' => $o->producto ? $o->producto->nombre : null,
                'tienda' => $o->tienda ? $o->tienda->nombre : null,
                'url_producto' => $urlProducto,
                'oferta_edit_url' => route('admin.ofertas.edit', $o),
            ];
        })->values()->toArray();

        return [
            'existe_mismo_producto' => false,
            'existe_otros_productos' => $ofertas->isNotEmpty(),
            'ofertas' => $arr,
        ];
    }

    private function detectarTiendaPorUrl($url, $todasLasTiendas)
    {
        try {
            $parsed = parse_url($url);
            $hostUser = strtolower($parsed['host'] ?? '');
            $hostUser = preg_replace('/^www\./', '', $hostUser);
            if (empty($hostUser)) {
                return null;
            }
            foreach ($todasLasTiendas as $t) {
                $tu = trim($t->url ?? '');
                if (empty($tu)) continue;
                $tu = preg_replace('#^https?://#i', '', $tu);
                $tu = preg_replace('/^www\./i', '', strtolower($tu));
                $tu = preg_replace('#/.*$#', '', $tu);
                if ($tu && ($hostUser === $tu || str_ends_with($hostUser, '.' . $tu) || str_ends_with($tu, '.' . $hostUser))) {
                    return $t;
                }
            }
        } catch (\Throwable $e) {
            //
        }
        return null;
    }

    private function buscarProductoPorUrl($url)
    {
        $path = parse_url($url, PHP_URL_PATH) ?? '';
        $segmentos = array_filter(explode('/', $path));
        $slug = end($segmentos);
        if (empty($slug) || strlen($slug) < 3) {
            return [];
        }
        $todasLasPalabras = array_values(array_filter(explode('-', $slug), fn ($p) => strlen($p) >= 2));
        $palabras = array_slice($todasLasPalabras, 0, 12);
        if (empty($palabras)) {
            return [];
        }

        // Normalizar: quitar espacios para que "12gb" coincida con "12 GB"
        $normalizar = fn ($s) => str_replace(' ', '', strtolower($s));
        $palabrasNorm = array_map($normalizar, $palabras);

        // 1. Candidatos: productos que coincidan con AL MENOS una palabra
        $query = Producto::where('obsoleto', 'no');
        $query->where(function ($q) use ($palabras, $normalizar) {
            foreach ($palabras as $p) {
                $pNorm = $normalizar($p);
                $q->orWhere(function ($q2) use ($pNorm) {
                    $q2->whereRaw('LOWER(REPLACE(nombre, \' \', \'\')) LIKE ?', ['%' . $pNorm . '%'])
                        ->orWhereRaw('LOWER(REPLACE(marca, \' \', \'\')) LIKE ?', ['%' . $pNorm . '%'])
                        ->orWhereRaw('LOWER(REPLACE(modelo, \' \', \'\')) LIKE ?', ['%' . $pNorm . '%'])
                        ->orWhereRaw('LOWER(REPLACE(talla, \' \', \'\')) LIKE ?', ['%' . $pNorm . '%'])
                        ->orWhereRaw('LOWER(REPLACE(slug, \' \', \'\')) LIKE ?', ['%' . $pNorm . '%']);
                });
            }
        });

        $productos = $query->with('categoria')
            ->orderBy('clicks', 'desc')
            ->limit(800)
            ->get(['id', 'nombre', 'marca', 'modelo', 'talla', 'slug', 'categoria_id', 'imagen_grande', 'imagen_pequena', 'clicks']);

        // Incluir productos cuya marca coincida con alguna palabra del slug (asegura que Gigabyte esté si la URL dice gigabyte)
        $palabrasMarca = array_values(array_filter($palabrasNorm, fn ($p) => strlen($p) >= 3 && preg_match('/^\D+$/', $p)));
        if (!empty($palabrasMarca)) {
            $extra = Producto::where('obsoleto', 'no')
                ->where(function ($q) use ($palabrasMarca) {
                    foreach ($palabrasMarca as $pNorm) {
                        $q->orWhereRaw('LOWER(REPLACE(marca, \' \', \'\')) LIKE ?', ['%' . $pNorm . '%']);
                    }
                })
                ->with('categoria')
                ->get(['id', 'nombre', 'marca', 'modelo', 'talla', 'slug', 'categoria_id', 'imagen_grande', 'imagen_pequena', 'clicks']);
            $productos = $productos->merge($extra)->unique('id')->values();
        }

        // 2. Puntuación simple: más coincidencias = mejor; bonus por marca (en cualquier posición de la URL)
        $productosConPuntuacion = $productos->map(function ($p) use ($palabras, $palabrasNorm, $normalizar) {
            $textoProducto = $normalizar($p->nombre . ' ' . ($p->marca ?? '') . ' ' . ($p->modelo ?? '') . ' ' . ($p->talla ?? '') . ' ' . ($p->slug ?? ''));
            $marcaNorm = $normalizar($p->marca ?? '');
            $puntuacion = 0;
            foreach ($palabras as $i => $palabra) {
                $pNorm = $palabrasNorm[$i];
                $coincide = str_contains($textoProducto, $pNorm);
                if ($coincide) {
                    $puntuacion += 1;
                    if ($marcaNorm !== '' && (str_contains($marcaNorm, $pNorm) || str_contains($pNorm, $marcaNorm))) {
                        $puntuacion += 20; // marca en URL = producto correcto (da igual si va primera o no)
                    }
                    if (preg_match('/\d/', $palabra)) {
                        $puntuacion += 8; // 5080, 16gb muy decisivos
                    }
                } elseif (preg_match('/\d/', $palabra)) {
                    $puntuacion -= 20; // URL tiene número que el producto no tiene = descartar
                }
            }
            return ['producto' => $p, 'puntuacion' => max(0, $puntuacion)];
        });

        // 3. Excluir productos cuya marca NO aparece en la URL (Inno3D cuando la URL dice gigabyte)
        // Solo palabras alfabéticas 3+ chars (gigabyte, amd, msi) - no 5080, rtx, etc.
        $palabrasPosibleMarca = array_values(array_filter($palabrasNorm, fn ($p) => strlen($p) >= 3 && preg_match('/^[a-z]+$/', $p)));
        if (!empty($palabrasPosibleMarca)) {
            $tieneMarcaEnUrl = function ($item) use ($palabrasPosibleMarca, $normalizar) {
                $marcaNorm = $normalizar($item['producto']->marca ?? '');
                if ($marcaNorm === '') return true; // sin marca, mantener
                foreach ($palabrasPosibleMarca as $pNorm) {
                    if (str_contains($marcaNorm, $pNorm) || str_contains($pNorm, $marcaNorm)) {
                        return true;
                    }
                }
                return false;
            };
            if ($productosConPuntuacion->contains($tieneMarcaEnUrl)) {
                $productosConPuntuacion = $productosConPuntuacion->filter($tieneMarcaEnUrl)->values();
            }
        }

        // 4. Ordenar por puntuación y clicks, tomar los 5 mejores
        $productosFiltrados = $productosConPuntuacion
            ->filter(fn ($item) => $item['puntuacion'] >= 3)
            ->sortBy([fn ($i) => -$i['puntuacion'], fn ($i) => -($i['producto']->clicks ?? 0)])
            ->values();

        return $productosFiltrados->take(5)->map(function ($item) {
            $p = $item['producto'];
            $urlProducto = null;
            if ($p->categoria) {
                $path = \App\Helpers\CategoriaHelper::construirUrlCategorias($p->categoria->id, $p->slug);
                $urlProducto = url($path);
            }
            $imgs = [];
            foreach ([$p->imagen_grande ?? [], $p->imagen_pequena ?? []] as $val) {
                $imgs = array_merge($imgs, is_array($val) ? $val : ($val ? [$val] : []));
            }
            return [
                'id' => $p->id,
                'nombre' => $p->nombre,
                'marca' => $p->marca,
                'modelo' => $p->modelo,
                'talla' => $p->talla,
                'texto_completo' => $p->nombre . ' - ' . $p->marca . ' - ' . $p->modelo . ' - ' . $p->talla,
                'url_producto' => $urlProducto,
                'imagen_grande' => $p->imagen_grande,
                'imagen_pequena' => $p->imagen_pequena,
                'imagenes_producto' => array_values(array_unique(array_filter($imgs))),
                'puntuacion' => $item['puntuacion'],
            ];
        })->values()->toArray();
    }

    private function obtenerEspecificacionesProducto($productoId)
    {
        $producto = Producto::with('categoria')->find($productoId);
        if (!$producto || !$producto->categoria_id_especificaciones_internas || !$producto->categoria_especificaciones_internas_elegidas) {
            return null;
        }
        $categoria = \App\Models\Categoria::find($producto->categoria_id_especificaciones_internas);
        if (!$categoria || !isset($categoria->especificaciones_internas['filtros'])) {
            return null;
        }
        $especificacionesElegidas = $producto->categoria_especificaciones_internas_elegidas;
        $columnasIds = $especificacionesElegidas['_columnas'] ?? [];

        $filtrosCategoria = $categoria->especificaciones_internas['filtros'] ?? [];
        $filtrosProducto = [];
        if (isset($especificacionesElegidas['_producto']['filtros']) && is_array($especificacionesElegidas['_producto']['filtros'])) {
            $filtrosProducto = $especificacionesElegidas['_producto']['filtros'];
        }
        $filtrosCombinados = array_merge($filtrosCategoria, $filtrosProducto);

        $resultado = [
            'unidad_de_medida' => $producto->unidadDeMedida ?? 'unidad',
            'columnas_ids' => $columnasIds,
            'filtros' => [],
        ];

        foreach ($filtrosCombinados as $f) {
            $sublineasElegidas = $especificacionesElegidas[$f['id']] ?? [];
            $subprincipales = $f['subprincipales'] ?? [];
            $sublineasOferta = [];
            foreach ($subprincipales as $sub) {
                $subId = $sub['id'] ?? null;
                if (!$subId) continue;
                foreach ($sublineasElegidas as $item) {
                    $itemId = is_array($item) && isset($item['id']) ? $item['id'] : $item;
                    if ((string) $itemId === (string) $subId) {
                        $esOferta = is_array($item) && isset($item['o']) && (int) $item['o'] === 1;
                        if ($esOferta) {
                            $imagenes = [];
                            foreach ([$sub['imagenes'] ?? [], $sub['imagen'] ?? []] as $v) {
                                $imagenes = array_merge($imagenes, is_array($v) ? $v : ($v ? [$v] : []));
                            }
                            if (is_array($item) && isset($item['img'])) {
                                $imgs = is_array($item['img']) ? $item['img'] : [$item['img']];
                                $imagenes = array_merge($imagenes, $imgs);
                            }
                            if (is_array($item) && isset($item['imagenes'])) {
                                $imgs = is_array($item['imagenes']) ? $item['imagenes'] : [$item['imagenes']];
                                $imagenes = array_merge($imagenes, $imgs);
                            }
                            if (is_array($item) && isset($item['imagen'])) {
                                $imagenes[] = $item['imagen'];
                            }
                            $usarImagenesProducto = is_array($item) && !empty($item['usarImagenesProducto']);
                            $sublineasOferta[] = array_merge($sub, [
                                'imagenes' => array_values(array_unique(array_filter($imagenes))),
                                'usar_imagenes_producto' => $usarImagenesProducto,
                            ]);
                            break;
                        }
                    }
                }
            }
            if (!empty($sublineasOferta)) {
                $resultado['filtros'][] = [
                    'id' => $f['id'],
                    'texto' => $f['texto'] ?? '',
                    'subprincipales' => $sublineasOferta,
                ];
            }
        }

        return $resultado;
    }

    private function calcularEnvioDesdeTienda(Tienda $tienda)
    {
        // Priorizar envio_gratis como en formulario; si está vacío, usar envio_normal
        $texto = trim($tienda->envio_gratis ?? '');
        if ($texto === '') {
            $texto = trim($tienda->envio_normal ?? '');
        }
        if (empty($texto)) {
            return [null, false];
        }
        if (stripos($texto, 'gratis') !== false) {
            return [null, true];
        }
        if (preg_match('/(\d+[,.]?\d*)\s*€?/', $texto, $m)) {
            return [(float) str_replace(',', '.', $m[1]), false];
        }
        return [null, false];
    }

    private function obtenerComoScrapearTienda(Tienda $tienda)
    {
        $cs = strtolower(trim($tienda->como_scrapear ?? 'manual'));
        return in_array($cs, ['automatico', 'manual', 'ambos']) ? ($cs === 'ambos' ? 'automatico' : $cs) : 'manual';
    }

    private function obtenerFrecuenciaMasComunTienda($tiendaId)
    {
        $row = OfertaProducto::where('tienda_id', $tiendaId)
            ->whereNull('chollo_id')
            ->selectRaw('frecuencia_actualizar_precio_minutos, COUNT(*) as cnt')
            ->groupBy('frecuencia_actualizar_precio_minutos')
            ->orderByDesc('cnt')
            ->first();
        return $row ? (int) $row->frecuencia_actualizar_precio_minutos : 1440;
    }
}

