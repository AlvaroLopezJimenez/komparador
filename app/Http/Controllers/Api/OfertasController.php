<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Producto;
use App\Models\OfertaProducto;
use App\Models\Categoria;
use App\Models\Tienda;
use App\Models\ProductoOfertaMasBarataPorProducto;
use App\Models\UrlDescartada;
use App\Models\Neo;
use App\Services\SacarPrimeraOfertaDeUnProductoAplicadoDescuentosYChollos;
use App\Services\SignedUrlService;
use App\Services\CalcularPrecioUnidad;
use App\Services\LimpiarUrlDeTiendas;
use App\Services\ConsultarNeoCifrado;
use App\Support\UrlOfertaValidacion;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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
     * Vista para sacar ofertas desde una URL de Idealo (llamada al VPS sacar-ofertas-idea)
     */
    public function sacarOfertasIdealoVista()
    {
        return view('admin.ofertas.sacar-ofertas-idealo');
    }

    /**
     * POST: Llama al VPS 51.38.184.245/sacar-ofertas-idea con la URL indicada y devuelve la respuesta cruda.
     * Body esperado: { "url": "https://www.idealo.es/..." }
     */
    public function sacarOfertasIdealoRequest(Request $request)
    {
        $request->validate(['url' => UrlOfertaValidacion::rules()]);

        $vpsUrl = 'http://51.38.184.245/sacar-ofertas-idea';
        $payload = ['url' => $request->input('url')];

        try {
            $resp = Http::timeout(900)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept'       => 'application/json',
                ])
                ->post($vpsUrl, $payload);

            $status = $resp->status();
            $body = $resp->body();

            return response()->json([
                'success' => $resp->successful(),
                'status'  => $status,
                'body'    => $body,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'status'  => 0,
                'body'    => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Recarga las especificaciones internas de un producto (para crear-masivo, sin recargar página)
     * GET /panel-privado/ofertas/crear-masivo/recargar-especificaciones/{producto}
     */
    public function recargarEspecificaciones(Request $request, $productoId)
    {
        $producto = Producto::with('categoria')->find($productoId);
        $urlProducto = null;
        $imagenesProducto = [];
        if ($producto) {
            if ($producto->categoria) {
                $path = \App\Helpers\CategoriaHelper::construirUrlCategorias($producto->categoria->id, $producto->slug);
                $urlProducto = url($path);
            }
            foreach ([$producto->imagen_grande ?? [], $producto->imagen_pequena ?? []] as $val) {
                $imagenesProducto = array_merge($imagenesProducto, is_array($val) ? $val : ($val ? [$val] : []));
            }
            $imagenesProducto = array_values(array_unique(array_filter($imagenesProducto)));
        }

        $especificaciones = $this->obtenerEspecificacionesProducto($productoId);
        $tieneEspecificaciones = $especificaciones && !empty($especificaciones['filtros'] ?? []);

        $especificacionesMarcadas = null;
        $urlOferta = trim((string) $request->query('url', ''));
        if ($urlOferta !== '' && $tieneEspecificaciones) {
            $detectadas = $this->detectarEspecificacionesProductoDesdeUrl(
                ['especificaciones' => $especificaciones],
                $urlOferta
            );
            if ($detectadas !== []) {
                $especificacionesMarcadas = $detectadas;
            }
        }

        return response()->json([
            'success' => true,
            'especificaciones' => $especificaciones,
            'tiene_especificaciones' => $tieneEspecificaciones,
            'especificaciones_marcadas' => $especificacionesMarcadas,
            'url_producto' => $urlProducto,
            'imagenes_producto' => $imagenesProducto,
        ]);
    }

    /**
     * Añade una sublínea (opción) a un grupo de especificaciones internas desde crear-masivo:
     * actualiza la categoría (filtro del catálogo) o _producto en elegidas, y marca la opción
     * en categoria_especificaciones_internas_elegidas con o=1 y m=1 (oferta + mostrar).
     *
     * POST /panel-privado/ofertas/crear-masivo/anadir-opcion-especificacion
     */
    public function anadirOpcionEspecificacionCrearMasivo(Request $request)
    {
        $data = $request->validate([
            'producto_id' => 'required|integer|exists:productos,id',
            'principal_id' => 'required|string|max:191',
            'texto' => 'required|string|max:500',
            'after_sub_id' => 'nullable|string|max:191',
            'insert_first' => 'nullable|boolean',
            'imagenes' => 'nullable|array',
            'imagenes.*' => 'string|max:500',
            'usar_imagenes_producto' => 'nullable|boolean',
        ]);

        $producto = Producto::findOrFail((int) $data['producto_id']);
        if (!$producto->categoria_id_especificaciones_internas) {
            return response()->json([
                'success' => false,
                'error' => 'El producto no tiene categoría de especificaciones internas asignada.',
            ], 422);
        }

        $principalId = (string) $data['principal_id'];
        $texto = trim($data['texto']);
        if ($texto === '') {
            return response()->json(['success' => false, 'error' => 'El nombre de la opción es obligatorio.'], 422);
        }

        $usarImagenesProducto = $request->boolean('usar_imagenes_producto');
        $imagenesRaw = $data['imagenes'] ?? [];
        $imagenes = [];
        foreach (is_array($imagenesRaw) ? $imagenesRaw : [] as $img) {
            $img = trim((string) $img);
            if ($img === '' || str_contains($img, '..')) {
                continue;
            }
            $imagenes[] = $img;
        }
        $imagenes = array_values(array_unique($imagenes));

        if ($usarImagenesProducto && $imagenes !== []) {
            $imagenes = [];
        }

        $slug = Str::slug($texto);
        if ($slug === '') {
            $slug = 'opcion';
        }

        $subId = 'cm_' . bin2hex(random_bytes(8));

        $insertFirst = $request->boolean('insert_first');

        $afterSubId = isset($data['after_sub_id']) ? trim((string) $data['after_sub_id']) : '';
        $afterSubId = ($afterSubId !== '' && !$insertFirst) ? $afterSubId : null;

        $categoria = Categoria::find($producto->categoria_id_especificaciones_internas);
        if (!$categoria) {
            return response()->json(['success' => false, 'error' => 'Categoría de especificaciones no encontrada.'], 422);
        }

        $elegidasPre = $producto->categoria_especificaciones_internas_elegidas;
        if (!is_array($elegidasPre)) {
            $elegidasPre = [];
        }

        $specInternasPre = $categoria->especificaciones_internas;
        if (!is_array($specInternasPre)) {
            $specInternasPre = [];
        }
        $filtrosCatPre = $specInternasPre['filtros'] ?? [];
        $idxCat = null;
        foreach ($filtrosCatPre as $i => $f) {
            if ((string) ($f['id'] ?? '') === $principalId) {
                $idxCat = $i;
                break;
            }
        }

        $filtrosProdPre = $elegidasPre['_producto']['filtros'] ?? [];
        $idxProd = null;
        if ($idxCat === null) {
            foreach ($filtrosProdPre as $i => $f) {
                if ((string) ($f['id'] ?? '') === $principalId) {
                    $idxProd = $i;
                    break;
                }
            }
        }

        if ($idxCat === null && $idxProd === null) {
            return response()->json([
                'success' => false,
                'error' => 'No se encontró el grupo de especificación (ni en la categoría ni en líneas del producto).',
            ], 422);
        }

        $nuevaSub = [
            'id' => $subId,
            'texto' => $texto,
            'slug' => $slug,
        ];

        $entradaElegida = [
            'id' => $subId,
            'o' => 1,
            'm' => 1,
        ];
        if ($usarImagenesProducto) {
            $entradaElegida['usarImagenesProducto'] = true;
        } elseif ($imagenes !== []) {
            $entradaElegida['img'] = $imagenes;
        }

        try {
            DB::transaction(function () use (
                $producto,
                $categoria,
                $idxCat,
                $idxProd,
                $principalId,
                $nuevaSub,
                $entradaElegida,
                $afterSubId,
                $insertFirst
            ) {
                $producto->refresh();
                $categoria->refresh();

                $elegidas = $producto->categoria_especificaciones_internas_elegidas;
                if (!is_array($elegidas)) {
                    $elegidas = [];
                }

                if ($idxCat !== null) {
                    $specInternas = $categoria->especificaciones_internas;
                    if (!is_array($specInternas)) {
                        $specInternas = [];
                    }
                    $filtrosCat = $specInternas['filtros'] ?? [];
                    if (!isset($filtrosCat[$idxCat]['subprincipales']) || !is_array($filtrosCat[$idxCat]['subprincipales'])) {
                        $filtrosCat[$idxCat]['subprincipales'] = [];
                    }
                    if ($insertFirst) {
                        array_unshift($filtrosCat[$idxCat]['subprincipales'], $nuevaSub);
                    } else {
                        $this->insertarSubprincipalDespuesDeId($filtrosCat[$idxCat]['subprincipales'], $nuevaSub, $afterSubId);
                    }
                    $specInternas['filtros'] = $filtrosCat;
                    $categoria->especificaciones_internas = $specInternas;
                    $categoria->save();
                } else {
                    if (!isset($elegidas['_producto']) || !is_array($elegidas['_producto'])) {
                        $elegidas['_producto'] = [];
                    }
                    if (!isset($elegidas['_producto']['filtros']) || !is_array($elegidas['_producto']['filtros'])) {
                        $elegidas['_producto']['filtros'] = [];
                    }
                    $filtrosP = $elegidas['_producto']['filtros'];
                    if (!isset($filtrosP[$idxProd]['subprincipales']) || !is_array($filtrosP[$idxProd]['subprincipales'])) {
                        $filtrosP[$idxProd]['subprincipales'] = [];
                    }
                    if ($insertFirst) {
                        array_unshift($filtrosP[$idxProd]['subprincipales'], $nuevaSub);
                    } else {
                        $this->insertarSubprincipalDespuesDeId($filtrosP[$idxProd]['subprincipales'], $nuevaSub, $afterSubId);
                    }
                    $elegidas['_producto']['filtros'] = $filtrosP;
                }

                if (!isset($elegidas[$principalId]) || !is_array($elegidas[$principalId])) {
                    $elegidas[$principalId] = [];
                }
                $elegidas[$principalId][] = $entradaElegida;

                $producto->categoria_especificaciones_internas_elegidas = $elegidas;
                $producto->save();
            });
        } catch (\Throwable $e) {
            \Log::error('anadirOpcionEspecificacionCrearMasivo: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return response()->json([
                'success' => false,
                'error' => 'No se pudo guardar la opción: ' . $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'success' => true,
            'sub_id' => $subId,
            'principal_id' => $principalId,
        ]);
    }

    /**
     * Inserta una sublínea en $subprincipales justo después de la que tenga id === $afterSubId.
     * Si $afterSubId es null o no se encuentra, añade al final.
     *
     * @param  array<int, array<string, mixed>>  $subprincipales
     */
    private function insertarSubprincipalDespuesDeId(array &$subprincipales, array $nuevaSub, ?string $afterSubId): void
    {
        if ($afterSubId === null || $afterSubId === '') {
            $subprincipales[] = $nuevaSub;

            return;
        }
        $pos = null;
        foreach ($subprincipales as $i => $s) {
            if ((string) ($s['id'] ?? '') === $afterSubId) {
                $pos = (int) $i;
                break;
            }
        }
        if ($pos === null) {
            $subprincipales[] = $nuevaSub;

            return;
        }
        array_splice($subprincipales, $pos + 1, 0, [$nuevaSub]);
    }

    /**
     * Analiza una lista de URLs: producto candidato, tienda, si existe, especificaciones
     * POST /panel-privado/ofertas/crear-masivo/analizar
     */
    public function analizarUrls(Request $request)
    {
        $request->validate([
            'urls' => 'required|string',
            'usar_chatgpt' => 'nullable|boolean',
            'incluir_contenido_pagina' => 'nullable|boolean',
            'chatgpt_model' => 'nullable|string|max:64',
            'producto_id' => 'nullable|integer|exists:productos,id',
            'especificaciones_internas' => 'nullable|string',
            'categoria_id' => 'nullable|integer|exists:categorias,id',
            'no_productos_sugeridos' => 'nullable|boolean',
        ]);

        $usarChatgpt = $request->boolean('usar_chatgpt');
        $incluirContenidoPagina = $request->boolean('incluir_contenido_pagina');
        $noProductosSugeridos = $request->boolean('no_productos_sugeridos');
        $chatgptModel = $request->filled('chatgpt_model') ? trim($request->chatgpt_model) : null;
        $mismoProductoId = $request->filled('producto_id') ? (int) $request->input('producto_id') : null;
        $mismoProductoEspecsRaw = $request->input('especificaciones_internas');
        $mismoProductoEspecs = null;
        if ($mismoProductoId && $mismoProductoEspecsRaw !== null && $mismoProductoEspecsRaw !== '') {
            $decoded = json_decode($mismoProductoEspecsRaw, true);
            if (is_array($decoded) && !empty($decoded)) {
                $mismoProductoEspecs = $decoded;
            }
        }

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
            $urlParaMostrar = $normalizada; // URL original (con /p si lo tiene) para mostrarla en resultados y guardar en oferta
            // Quitar "/p" final (pcbox y otras tiendas): solo para búsqueda de candidatos por slug
            if (strlen($normalizada) > 2 && substr($normalizada, -2) === '/p') {
                $normalizada = substr($normalizada, 0, -2);
            }
            if (!collect($urlsParaProcesar)->contains('normalizada', $normalizada)) {
                $urlsParaProcesar[] = ['url' => $url, 'normalizada' => $normalizada, 'url_mostrar' => $urlParaMostrar];
            }
        }

        $todasLasTiendas = Tienda::select('id', 'nombre', 'url', 'envio_gratis', 'envio_normal', 'como_scrapear', 'mostrar_tienda', 'scrapear')
            ->orderBy('nombre')
            ->get();

        $categoriaCatalogoId = $request->filled('categoria_id') ? (int) $request->input('categoria_id') : null;
        $vocabularioCategoria = ($categoriaCatalogoId !== null)
            ? $this->construirVocabularioTokensDesdeCategoria($categoriaCatalogoId)
            : [];
        $categoriaNombresCache = [];

        $resultados = [];
        foreach ($urlsParaProcesar as $data) {
            if (isset($data['error'])) {
                $resultados[] = [
                    'url' => $data['url'],
                    'url_normalizada' => $data['url'],
                    'neo_id' => null,
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
            $urlParaMostrar = $data['url_mostrar'] ?? $data['normalizada'];
            $urlParaBuscar = $data['normalizada']; // sin /p para que buscarProductoPorUrl use el slug correcto

            $item = [
                'url' => $urlParaMostrar,
                'url_normalizada' => $urlParaMostrar,
                'neo_id' => null,
                'existe' => false,
                'existe_mismo_producto' => false,
                'existe_otros_productos' => false,
                'descartada' => false,
                'ofertas_existentes' => [],
                'tienda' => null,
                'producto' => null,
                'productos_candidatos' => [],
                'especificaciones' => null,
                'tiene_especificaciones' => false,
                'error' => null,
            ];

            $urlLookups = array_values(array_unique(array_filter([
                app(ConsultarNeoCifrado::class)->hashLookup($urlParaMostrar),
                app(ConsultarNeoCifrado::class)->hashLookup($urlParaBuscar),
            ])));
            $categoriaUrlId = $categoriaCatalogoId;
            $neoProductoId = null;
            if (!empty($urlLookups)) {
                $neoRow = Neo::query()
                    ->where('aniadida', 'no')
                    ->whereIn('url_lookup', $urlLookups)
                    ->orderBy('id')
                    ->first(['id', 'categoria_id', 'producto_id']);
                if ($neoRow) {
                    $item['neo_id'] = (int) $neoRow->id;
                    if ($neoRow->producto_id !== null) {
                        $neoProductoId = (int) $neoRow->producto_id;
                    }
                    if ($neoRow->categoria_id !== null) {
                        $catId = (int) $neoRow->categoria_id;
                        if (!isset($categoriaNombresCache[$catId])) {
                            $cat = Categoria::query()->find($catId, ['id', 'nombre']);
                            $categoriaNombresCache[$catId] = $cat ? $cat->nombre : ('Categoría #' . $catId);
                        }
                        $item['categoria_fila'] = [
                            'id' => $catId,
                            'nombre' => $categoriaNombresCache[$catId],
                        ];
                        $item['categoria_id'] = $catId;
                        if ($categoriaUrlId === null) {
                            $categoriaUrlId = $catId;
                        }
                    }
                }
            }
            $vocabularioUrl = ($categoriaUrlId !== null)
                ? $this->construirVocabularioTokensDesdeCategoria($categoriaUrlId)
                : $vocabularioCategoria;

            if (UrlDescartada::where('url', $urlParaMostrar)->exists()) {
                $item['existe'] = true;
                $item['descartada'] = true;
            } else {
                $verificacion = $this->verificarUrlExistente($urlParaMostrar);
                if ($verificacion['existe_mismo_producto']) {
                    $item['existe'] = true;
                    $item['existe_mismo_producto'] = true;
                    $item['ofertas_existentes'] = $verificacion['ofertas'];
                } elseif ($verificacion['existe_otros_productos']) {
                    $item['existe'] = true;
                    $item['existe_otros_productos'] = true;
                    $item['ofertas_existentes'] = $verificacion['ofertas'];
                }
            }

            $tienda = $this->detectarTiendaPorUrl($urlParaMostrar, $todasLasTiendas);
            $item['envio_sugerido'] = null;
            if ($tienda) {
                $item['tienda'] = [
                    'id' => $tienda->id,
                    'nombre' => $tienda->nombre,
                    'envio_gratis' => $tienda->envio_gratis ?? null,
                    'envio_normal' => $tienda->envio_normal ?? null,
                    'como_scrapear' => $tienda->como_scrapear ?? 'manual',
                    'mostrar_tienda' => $tienda->mostrar_tienda ?? 'si',
                    'scrapear' => $tienda->scrapear ?? 'si',
                ];
                [$envioCalc, $envioPlaceholderGratis] = $this->calcularEnvioDesdeTienda($tienda);
                if (! $envioPlaceholderGratis && is_numeric($envioCalc) && (float) $envioCalc > 0) {
                    $item['envio_sugerido'] = round((float) $envioCalc, 2);
                }
            } else {
                $item['error'] = ($item['error'] ?? '') . ' Tienda no detectada.';
            }

            if (!$item['existe']) {
                if ($mismoProductoId) {
                    $productoUnico = $this->construirProductoParaCrearMasivo($mismoProductoId);
                    if ($productoUnico) {
                        $item['producto'] = $productoUnico;
                        $item['especificaciones'] = $productoUnico['especificaciones'] ?? null;
                        $item['tiene_especificaciones'] = $productoUnico['tiene_especificaciones'] ?? false;
                        $item['productos_candidatos'] = [$productoUnico];
                        // Igual que sin producto fijo: pre-marcar desde el slug de la URL; lo del panel (especificaciones_internas) gana por grupo si ya viene marcado.
                        $especsMarcadas = $mismoProductoEspecs !== null ? $mismoProductoEspecs : [];
                        $especsDesdeUrl = $this->detectarEspecificacionesProductoDesdeUrl($productoUnico, $urlParaBuscar);
                        foreach ($especsDesdeUrl as $pid => $subs) {
                            $pid = (string) $pid;
                            if ($pid === '' || $pid === '_columnas' || !is_array($subs)) {
                                continue;
                            }
                            $yaMarcado = isset($especsMarcadas[$pid]) && is_array($especsMarcadas[$pid]) && $especsMarcadas[$pid] !== [];
                            if (!$yaMarcado) {
                                $especsMarcadas[$pid] = $subs;
                            }
                        }
                        if ($especsMarcadas !== []) {
                            $item['especificaciones_marcadas'] = $especsMarcadas;
                        }
                    } else {
                        $item['error'] = ($item['error'] ?? '') . ' Producto no encontrado.';
                    }
                } elseif ($neoProductoId) {
                    $productoUnico = $this->construirProductoParaCrearMasivo($neoProductoId);
                    if ($productoUnico) {
                        $item['producto'] = $productoUnico;
                        $item['especificaciones'] = $productoUnico['especificaciones'] ?? null;
                        $item['tiene_especificaciones'] = $productoUnico['tiene_especificaciones'] ?? false;
                        $item['productos_candidatos'] = [$productoUnico];
                        $item['producto_asignado_desde_neo'] = true;
                        $item['hay_empate'] = false;
                        $item['candidatos_empatados'] = [];
                    } else {
                        $item['error'] = ($item['error'] ?? '') . ' Producto neo no encontrado.';
                    }
                } elseif ($noProductosSugeridos) {
                    $item['sin_producto_sugerido'] = true;
                } else {
                    $productos = $this->buscarProductoPorUrl($urlParaBuscar, $categoriaUrlId, $vocabularioUrl);
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
                        $especsDetectadas = $this->detectarEspecificacionesProductoDesdeUrl($item['producto'], $urlParaBuscar);
                        if (!empty($especsDetectadas)) {
                            $item['especificaciones_marcadas'] = $especsDetectadas;
                        }
                    } else {
                        $item['error'] = ($item['error'] ?? '') . ' Producto no encontrado.';
                    }
                }
            }

            if ($incluirContenidoPagina && !empty($urlParaMostrar)) {
                try {
                    $item['contenido_pagina_extraido'] = $this->extraerContenidoPaginaDeUrl($urlParaMostrar);
                } catch (\Throwable $e) {
                    $item['contenido_pagina_extraido'] = '';
                }
            }

            $resultados[] = $item;
        }

        $chatgptRawResponse = null;
        if ($usarChatgpt) {
            [$resultados, $chatgptRawResponse] = $this->aplicarChatgptAnalisis($resultados, $incluirContenidoPagina, $chatgptModel);
            // Adjuntar a cada resultado su prompt y respuesta para "Ver prompt" en la vista
            if ($chatgptRawResponse && !empty($chatgptRawResponse['por_url'])) {
                foreach ($chatgptRawResponse['por_url'] as $idx => $datos) {
                    if (isset($resultados[$idx])) {
                        $resultados[$idx]['chatgpt_prompt'] = $datos['prompt'] ?? '';
                        $resultados[$idx]['chatgpt_respuesta_raw'] = $datos['raw_content'] ?? '';
                        $resultados[$idx]['chatgpt_parsed'] = $datos['parsed_resultados'] ?? null;
                    }
                }
            }
        }

        $response = ['success' => true, 'resultados' => array_values($resultados)];
        if ($chatgptRawResponse !== null) {
            $response['chatgpt_raw_response'] = $chatgptRawResponse;
        }
        return response()->json($response);
    }

    /**
     * Aplica análisis de ChatGPT para elegir producto y especificaciones en crear-masivo.
     * Una petición por URL (mejor precisión). No se envían las URLs que ya existen.
     *
     * @param string|null $chatgptModel Modelo OpenAI (ej. gpt-4o, gpt-4o-mini). Null = por defecto.
     * @return array{0: array, 1: array|null} [resultados, respuesta cruda de ChatGPT para depuración]
     */
    private function aplicarChatgptAnalisis(array $resultados, bool $incluirContenidoPagina, ?string $chatgptModel = null): array
    {
        $indicesParaChatgpt = [];
        foreach ($resultados as $idx => $r) {
            if ($r['existe'] ?? false) continue;
            if (!($r['tienda'] ?? null)) continue;
            if (!empty($r['producto_asignado_desde_neo'])) continue;
            $candidatos = $r['productos_candidatos'] ?? [];
            if (empty($candidatos)) continue;
            $indicesParaChatgpt[$idx] = $r;
        }

        if (empty($indicesParaChatgpt)) {
            return [$resultados, null];
        }

        $contenidoPorUrl = [];
        if ($incluirContenidoPagina) {
            foreach ($indicesParaChatgpt as $idx => $r) {
                $url = $r['url_normalizada'] ?? $r['url'] ?? '';
                if (empty($url)) continue;
                try {
                    $contenidoPorUrl[$idx] = $this->extraerContenidoPaginaDeUrl($url);
                } catch (\Throwable $e) {
                    \Log::warning('Error extrayendo contenido de URL para ChatGPT: ' . $e->getMessage());
                    $contenidoPorUrl[$idx] = '';
                }
            }
        }

        $chatgptRawResponse = ['por_url' => [], 'prompt' => '', 'raw_content' => '', 'parsed_resultados' => []];

        // Una petición por URL para afinar mejor
        foreach ($indicesParaChatgpt as $idx => $r) {
            $payload = $this->construirPayloadChatgptUnaUrl($r, $contenidoPorUrl[$idx] ?? '');
            [$respuestaChatgpt, $rawContent] = $this->llamarChatgptCrearMasivo($payload, $chatgptModel);

            $chatgptRawResponse['por_url'][$idx] = [
                'prompt' => $payload,
                'raw_content' => $rawContent,
                'parsed_resultados' => $respuestaChatgpt,
            ];

            if (!$respuestaChatgpt || empty($respuestaChatgpt)) {
                $resultados[$idx]['producto'] = null;
                $resultados[$idx]['no_entre_opciones'] = true;
                $resultados[$idx]['especificaciones'] = null;
                $resultados[$idx]['tiene_especificaciones'] = false;
                $resultados[$idx]['hay_empate'] = false;
                $resultados[$idx]['candidatos_empatados'] = [];
                continue;
            }

            $item = $respuestaChatgpt[0] ?? null;
            if (!$item) {
                continue;
            }

            $noEntreOpciones = filter_var($item['no_entre_opciones'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $productoId = $item['producto_id'] ?? null;
            if ($productoId === null || $productoId === '') {
                $noEntreOpciones = true;
            }

            if ($noEntreOpciones) {
                $resultados[$idx]['producto'] = null;
                $resultados[$idx]['no_entre_opciones'] = true;
                $resultados[$idx]['especificaciones'] = null;
                $resultados[$idx]['tiene_especificaciones'] = false;
                $resultados[$idx]['hay_empate'] = false;
                $resultados[$idx]['candidatos_empatados'] = [];
                continue;
            }

            $candidatos = $resultados[$idx]['productos_candidatos'] ?? [];
            $productoElegido = null;
            foreach ($candidatos as $c) {
                if ((int) ($c['id'] ?? 0) === (int) $productoId) {
                    $productoElegido = $c;
                    break;
                }
            }

            if ($productoElegido) {
                $resultados[$idx]['producto'] = $productoElegido;
                $resultados[$idx]['especificaciones'] = $productoElegido['especificaciones'] ?? null;
                $resultados[$idx]['tiene_especificaciones'] = $productoElegido['tiene_especificaciones'] ?? false;
                $resultados[$idx]['hay_empate'] = false;
                $resultados[$idx]['candidatos_empatados'] = [];

                $especsMarcadas = $item['especificaciones'] ?? null;
                if (is_array($especsMarcadas) && !empty($especsMarcadas)) {
                    $especsMarcadas = $this->convertirEspecificacionesNombresAIds($especsMarcadas, $productoElegido);
                    $columnasIds = $productoElegido['especificaciones']['columnas_ids'] ?? [];
                    $especsMarcadas = $this->normalizarEspecificacionesColumnas($especsMarcadas, $columnasIds);
                    if (!empty($especsMarcadas)) {
                        $resultados[$idx]['especificaciones_marcadas_chatgpt'] = $especsMarcadas;
                    }
                }
            }
        }

        // Para depuración: primer prompt/raw/parsed (compatible con lo que muestra la vista)
        $firstIdx = array_key_first($chatgptRawResponse['por_url']);
        if ($firstIdx !== null) {
            $first = $chatgptRawResponse['por_url'][$firstIdx];
            $chatgptRawResponse['prompt'] = $first['prompt'];
            $chatgptRawResponse['raw_content'] = $first['raw_content'];
            $chatgptRawResponse['parsed_resultados'] = $first['parsed_resultados'];
        }

        return [$resultados, $chatgptRawResponse];
    }

    /**
     * Convierte especificaciones devueltas por ChatGPT por nombre (grupo -> opción)
     * a la estructura por ids (principal_id -> [sub_id]) del producto elegido.
     */
    private function convertirEspecificacionesNombresAIds(array $especsPorNombre, array $productoElegido): array
    {
        $especsProducto = $productoElegido['especificaciones'] ?? null;
        if (!$especsProducto || empty($especsProducto['filtros'] ?? [])) {
            return [];
        }

        $out = [];
        foreach ($especsProducto['filtros'] as $f) {
            $principalId = (string) ($f['id'] ?? '');
            $nombreGrupo = $this->normalizarTextoParaComparacion((string) ($f['texto'] ?? ''));
            if ($principalId === '' || $nombreGrupo === '') continue;

            $valorChatgpt = null;
            foreach ($especsPorNombre as $key => $v) {
                if ($this->normalizarTextoParaComparacion((string) $key) === $nombreGrupo) {
                    $valorChatgpt = is_array($v) ? (reset($v) ?? null) : $v;
                    break;
                }
            }
            if ($valorChatgpt === null || $valorChatgpt === '') continue;

            $valorNorm = $this->normalizarTextoParaComparacion((string) $valorChatgpt);
            foreach ($f['subprincipales'] ?? [] as $sub) {
                $subTexto = $this->normalizarTextoParaComparacion((string) ($sub['texto'] ?? ''));
                if ($subTexto === $valorNorm) {
                    $subId = $sub['id'] ?? null;
                    if ($subId !== null) {
                        if (!isset($out[$principalId])) $out[$principalId] = [];
                        $out[$principalId][] = (string) $subId;
                    }
                    break;
                }
            }
        }
        return $out;
    }

    /**
     * ChatGPT a veces devuelve ids de especificaciones de otro candidato. Mapeamos por "texto"
     * de la opción: buscamos el texto en cualquier candidato y aplicamos el id correspondiente
     * del producto elegido.
     */
    private function sanitizarEspecificacionesChatgptParaProducto(
        array $especsMarcadas,
        array $productoElegido,
        array $candidatos
    ): array {
        $productoId = (int) ($productoElegido['id'] ?? 0);
        $especsProducto = $productoElegido['especificaciones'] ?? null;
        if (!$especsProducto || empty($especsProducto['filtros'] ?? [])) {
            return $especsMarcadas;
        }

        $textosQueridos = [];
        foreach ($especsMarcadas as $principalId => $subIds) {
            if (!is_array($subIds)) continue;
            foreach ($subIds as $subId) {
                $texto = $this->obtenerTextoOpcionEspecificacion($subId, $principalId, $candidatos);
                if ($texto !== null && $texto !== '') {
                    $textosQueridos[] = $this->normalizarTextoParaComparacion($texto);
                }
            }
        }
        $textosQueridos = array_unique($textosQueridos);

        $out = [];
        foreach ($especsProducto['filtros'] as $f) {
            $principalId = (string) ($f['id'] ?? '');
            if ($principalId === '') continue;
            $subprincipales = $f['subprincipales'] ?? [];
            foreach ($subprincipales as $sub) {
                $subId = $sub['id'] ?? null;
                $texto = $sub['texto'] ?? '';
                if ($subId === null) continue;
                $textoNorm = $this->normalizarTextoParaComparacion($texto);
                if (in_array($textoNorm, $textosQueridos, true)) {
                    if (!isset($out[$principalId])) $out[$principalId] = [];
                    $out[$principalId][] = (string) $subId;
                }
            }
        }

        return $out;
    }

    private function normalizarTextoParaComparacion(string $texto): string
    {
        return trim(mb_strtolower($texto, 'UTF-8'));
    }

    private function obtenerTextoOpcionEspecificacion(string $subId, string $principalId, array $candidatos): ?string
    {
        foreach ($candidatos as $c) {
            $especs = $c['especificaciones'] ?? null;
            if (!$especs || empty($especs['filtros'])) continue;
            foreach ($especs['filtros'] as $f) {
                if ((string) ($f['id'] ?? '') !== $principalId) continue;
                foreach ($f['subprincipales'] ?? [] as $sub) {
                    if ((string) ($sub['id'] ?? '') === (string) $subId) {
                        return $sub['texto'] ?? null;
                    }
                }
            }
        }
        return null;
    }

    /**
     * Para principals que son "columna" (una sola opción), deja solo el primer id si ChatGPT devolvió varios.
     */
    private function normalizarEspecificacionesColumnas(array $especs, array $columnasIds): array
    {
        if (empty($columnasIds)) {
            return $especs;
        }
        $columnasIds = array_map('strval', $columnasIds);
        $out = [];
        foreach ($especs as $principalId => $subIds) {
            $principalIdStr = (string) $principalId;
            if (in_array($principalIdStr, $columnasIds, true) && is_array($subIds) && count($subIds) > 1) {
                $out[$principalIdStr] = [array_values($subIds)[0]];
            } else {
                $out[$principalIdStr] = $subIds;
            }
        }
        return $out;
    }

    /**
     * Extrae title, h1 y meta description de una URL (scraping).
     */
    private function extraerContenidoPaginaDeUrl(string $url): string
    {
        $controller = new \App\Http\Controllers\Scraping\PeticionApiHTMLController();
        $resultado = $controller->obtenerHTML($url);

        if (empty($resultado['success']) || empty($resultado['html'] ?? '')) {
            return '';
        }

        $html = $resultado['html'];
        $partes = [];

        if (preg_match('/<title[^>]*>([^<]*)<\/title>/is', $html, $m)) {
            $partes[] = 'Título: ' . trim(html_entity_decode(strip_tags($m[1])));
        }
        if (preg_match('/<h1[^>]*>([^<]*)<\/h1>/is', $html, $m)) {
            $partes[] = 'H1: ' . trim(html_entity_decode(strip_tags($m[1])));
        }
        if (preg_match('/<meta[^>]+name=["\']description["\'][^>]+content=["\']([^"\']*)["\']/is', $html, $m)) {
            $partes[] = 'Meta descripción: ' . trim(html_entity_decode($m[1]));
        } elseif (preg_match('/<meta[^>]+content=["\']([^"\']*)["\'][^>]+name=["\']description["\']/is', $html, $m)) {
            $partes[] = 'Meta descripción: ' . trim(html_entity_decode($m[1]));
        }

        return implode("\n", $partes);
    }

    /**
     * Construye el payload (prompt) para ChatGPT para UNA sola URL (una petición por URL).
     */
    private function construirPayloadChatgptUnaUrl(array $r, string $contenidoPagina = ''): string
    {
        $url = $r['url_normalizada'] ?? $r['url'] ?? '';
        $candidatos = $r['productos_candidatos'] ?? [];
        $productosParaPrompt = [];
        foreach ($candidatos as $c) {
            $prod = [
                'id' => $c['id'],
                'nombre' => $c['nombre'] ?? '',
                'marca' => $c['marca'] ?? '',
                'modelo' => $c['modelo'] ?? '',
                'talla' => $c['talla'] ?? '',
                'texto_completo' => $c['texto_completo'] ?? '',
            ];
            $especs = $c['especificaciones'] ?? null;
            if ($especs && !empty($especs['filtros'] ?? [])) {
                $prod['especificaciones'] = [];
                foreach ($especs['filtros'] as $f) {
                    $nombreGrupo = trim((string) ($f['texto'] ?? $f['id'] ?? ''));
                    if ($nombreGrupo === '') continue;
                    $opciones = [];
                    foreach ($f['subprincipales'] ?? [] as $sub) {
                        $t = trim((string) ($sub['texto'] ?? ''));
                        if ($t !== '') $opciones[] = $t;
                    }
                    if (!empty($opciones)) {
                        $prod['especificaciones'][$nombreGrupo] = $opciones;
                    }
                }
            }
            $productosParaPrompt[] = $prod;
        }

        $bloque = "URL índice 0: $url\nProductos candidatos: " . json_encode($productosParaPrompt, JSON_UNESCAPED_UNICODE);
        if ($contenidoPagina !== '') {
            $bloque .= "\nContenido de la página:\n" . $contenidoPagina;
        }

        return "Para esta URL indica qué producto corresponde (de los candidatos) y qué variantes marcar.

" . $bloque . "

RESPONDE SOLO CON ESTE JSON (nada más):
{
  \"resultados\": [
    {
      \"url_index\": 0,
      \"producto_id\": 123,
      \"no_entre_opciones\": false,
      \"especificaciones\": { \"Nombre del grupo\": \"Nombre de la opción elegida\" }
    }
  ]
}

REGLAS:

1) producto_id: El producto elegido debe ser el que corresponda al MISMO modelo que describe la URL. Compara el slug de la URL con el nombre/modelo de cada candidato: el número de modelo y los sufijos de gama (Ti, Super, XT, Pro, etc.) deben coincidir; modelos con distinto número o sufijo son productos distintos (no intercambiables). El nombre del producto en la BD es el artículo base; la variante de diseño o refrigeración (ej. ROG Strix, Ventus, TUF) se elige en \"especificaciones\", no en producto_id. Si ningún candidato tiene el mismo modelo que la URL → producto_id null, no_entre_opciones true, especificaciones null. Excepción: si hay candidatos RX 9070 y RX 9070 XT, elige según si el slug contiene \"9070-xt\" o \"9070xt\" (XT) o no (9070 sin XT).

2) especificaciones: Del producto elegido, en \"especificaciones\" hay grupos (Color, Modelo, etc.) con opciones. Elige UNA opción de cada grupo que encaje con la URL (slug, título o H1 si se incluyeron). Usa el nombre exacto de la opción tal como aparece en la lista. Si la URL no indica variante para un grupo, no lo incluyas.

3) url_index: debe ser 0 (solo hay una URL en esta petición).

Responde ÚNICAMENTE con el JSON.";
    }

    /**
     * Construye el payload (prompt) para ChatGPT con varias URLs (legacy; ya no se usa, se hace una petición por URL).
     */
    private function construirPayloadChatgptCrearMasivo(array $indicesParaChatgpt, array $contenidoPorUrl): string
    {
        $entradas = [];
        foreach ($indicesParaChatgpt as $idxReal => $r) {
            $url = $r['url_normalizada'] ?? $r['url'] ?? '';
            $candidatos = $r['productos_candidatos'] ?? [];
            $productosParaPrompt = [];
            foreach ($candidatos as $c) {
                $prod = [
                    'id' => $c['id'],
                    'nombre' => $c['nombre'] ?? '',
                    'marca' => $c['marca'] ?? '',
                    'modelo' => $c['modelo'] ?? '',
                    'talla' => $c['talla'] ?? '',
                    'texto_completo' => $c['texto_completo'] ?? '',
                ];
                $especs = $c['especificaciones'] ?? null;
                if ($especs && !empty($especs['filtros'] ?? [])) {
                    $prod['especificaciones'] = [];
                    foreach ($especs['filtros'] as $f) {
                        $nombreGrupo = trim((string) ($f['texto'] ?? $f['id'] ?? ''));
                        if ($nombreGrupo === '') continue;
                        $opciones = [];
                        foreach ($f['subprincipales'] ?? [] as $sub) {
                            $t = trim((string) ($sub['texto'] ?? ''));
                            if ($t !== '') $opciones[] = $t;
                        }
                        if (!empty($opciones)) {
                            $prod['especificaciones'][$nombreGrupo] = $opciones;
                        }
                    }
                }
                $productosParaPrompt[] = $prod;
            }
            $i = count($entradas);
            $bloque = "URL índice $i: $url\nProductos candidatos: " . json_encode($productosParaPrompt, JSON_UNESCAPED_UNICODE);
            if (!empty($contenidoPorUrl[$idxReal] ?? '')) {
                $bloque .= "\nContenido de la página:\n" . $contenidoPorUrl[$idxReal];
            }
            $entradas[] = $bloque;
        }
        return "Para cada URL indica qué producto corresponde (de los candidatos) y qué variantes marcar.\n\n" . implode("\n\n---\n\n", $entradas);
    }

    /**
     * Llama a ChatGPT y devuelve [array de resultados parseado, contenido crudo].
     *
     * @param string|null $model Modelo OpenAI (ej. gpt-4o, gpt-4o-mini). Null = gpt-4o-mini.
     * @return array{0: array|null, 1: string|null}
     */
    private function llamarChatgptCrearMasivo(string $prompt, ?string $model = null): array
    {
        $apiKey = config('services.openai.api_key');
        if (!$apiKey) {
            \Log::warning('OpenAI API key no configurada para crear-masivo ChatGPT');
            return [null, null];
        }

        try {
            $client = \OpenAI::client($apiKey);
        } catch (\Exception $e) {
            \Log::error('Error cliente OpenAI crear-masivo: ' . $e->getMessage());
            return [null, null];
        }

        $modelId = $model && trim($model) !== '' ? trim($model) : 'gpt-4o';

        try {
            $systemMessage = 'Responde SIEMPRE únicamente con un JSON válido (objeto). No añadas texto fuera del JSON. Slug de la URL define el modelo: si no contiene "9070-xt" ni "9070xt", elige producto "RX 9070" (sin XT), nunca "9070 XT". Para especificaciones devuelve nombres: objeto con nombre del grupo como clave y nombre exacto de la opción elegida como valor (ej. {"Modelo": "TUF Gaming OC"}).';

            $response = $client->chat()->create([
                'model' => $modelId,
                'messages' => [
                    ['role' => 'system', 'content' => $systemMessage],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'temperature' => 0.1,
                'max_tokens' => 4000,
                'response_format' => ['type' => 'json_object'],
            ]);
        } catch (\Throwable $e) {
            \Log::error('Error llamada OpenAI crear-masivo: ' . $e->getMessage());
            return [null, null];
        }

        $content = $response->choices[0]->message->content ?? '';
        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
            if (preg_match('/```json\s*(\{.*?\})\s*```/s', $content, $m)) {
                $data = json_decode($m[1], true);
            }
        }
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
            if (preg_match('/\{(?:[^{}]|(?R))*\}/s', $content, $m)) {
                $data = json_decode($m[0], true);
            }
        }

        $resultados = $data['resultados'] ?? null;
        if (!is_array($resultados)) {
            \Log::warning('ChatGPT crear-masivo: respuesta sin resultados válidos');
            return [null, $content];
        }

        return [$resultados, $content];
    }

    /**
     * Crea una oferta desde el flujo masivo (obtiene precio, aplica envío/tienda, guarda)
     * POST /panel-privado/ofertas/crear-masivo/crear
     */
    public function crearOfertaBulk(Request $request)
    {
        try {
        $request->validate([
            'url' => UrlOfertaValidacion::rules(),
            'producto_id' => 'required|exists:productos,id',
            'tienda_id' => 'required|exists:tiendas,id',
            'especificaciones_internas' => 'nullable|string',
            'generar_sin_precio' => 'nullable|boolean',
            'envio' => 'nullable|numeric',
        ]);

        $url = trim($request->url);
        $productoId = (int) $request->producto_id;
        $tiendaId = (int) $request->tienda_id;
        $generarSinPrecio = $request->boolean('generar_sin_precio');

        $producto = Producto::findOrFail($productoId);
        $tienda = Tienda::findOrFail($tiendaId);

        $unidades = ($producto->unidadDeMedida === 'unidadUnica') ? 1.0 : 1.0;

        $tiendaNoVisibleNiScrapeable = ($tienda->mostrar_tienda === 'no' && $tienda->scrapear === 'no');

        if ($generarSinPrecio) {
            // No intentar obtener precio: crear con precio 0, no mostrar y aviso sin stock
            $precioTotal = 0.0;
            $precioUnidad = 0.0;
        } elseif ($tiendaNoVisibleNiScrapeable) {
            // Tienda marcada para no mostrar y no scrapear: no consultar precio y usar valor centinela.
            $precioTotal = 9999.0;
            $calcularPrecioUnidad = new CalcularPrecioUnidad();
            $precioUnidad = $calcularPrecioUnidad->calcular(
                $producto->unidadDeMedida ?? 'unidad',
                $precioTotal,
                $unidades
            );
            if ($precioUnidad === null) {
                $precioUnidad = $precioTotal / max(0.01, $unidades);
            }
        } else {
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

            $calcularPrecioUnidad = new CalcularPrecioUnidad();
            $precioUnidad = $calcularPrecioUnidad->calcular(
                $producto->unidadDeMedida ?? 'unidad',
                $precioTotal,
                $unidades
            );
            if ($precioUnidad === null) {
                $precioUnidad = $precioTotal / max(0.01, $unidades);
            }
        }

        if ($request->has('envio')) {
            $rawEnvio = $request->input('envio');
            if ($rawEnvio === null || $rawEnvio === '') {
                $envioFinal = null;
            } elseif (is_numeric($rawEnvio)) {
                $v = round((float) $rawEnvio, 2);
                $envioFinal = $v > 0 ? $v : null;
            } else {
                $envioFinal = null;
            }
        } else {
            [$envio, $envioPlaceholderGratis] = $this->calcularEnvioDesdeTienda($tienda);
            $envioFinal = $envioPlaceholderGratis ? null : (is_numeric($envio) ? round((float) $envio, 2) : null);
        }
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
            'envio' => $envioFinal,
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
            $textoAviso = $generarSinPrecio ? 'Sin stock 1a vez' : 'Sin stock - 1a vez';
            \App\Models\Aviso::create([
                'texto_aviso' => $textoAviso,
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

        // Si la URL viene de neo (crear-masivo), marcar aniadida = si
        $urlNorm = $this->normalizarUrl($url);
        $urlSinBarra = rtrim($urlNorm, '/');
        $urlConBarra = $urlSinBarra . '/';
        $variantes = array_unique([$url, trim($url), $urlNorm, $urlSinBarra, $urlConBarra]);
        $lookups = array_values(array_unique(array_filter(array_map(
            fn ($u) => app(ConsultarNeoCifrado::class)->hashLookup((string) $u),
            $variantes
        ))));
        if (!empty($lookups)) {
            Neo::where('aniadida', 'no')
                ->whereIn('url_lookup', $lookups)
                ->update(['aniadida' => 'si']);
        }

        return response()->json([
            'success' => true,
            'oferta_id' => $oferta->id,
            'oferta_edit_url' => route('admin.ofertas.edit', $oferta),
            'envio' => $oferta->envio,
            'precio_unidad' => $oferta->precio_unidad,
            'mensaje' => 'Oferta creada correctamente',
        ]);

        } catch (\Throwable $e) {
            $errorRef = (string) Str::uuid();

            $logMessage = sprintf(
                'CrearOfertaBulk [%s] %s | %s:%d',
                $errorRef,
                $e->getMessage(),
                $e->getFile(),
                $e->getLine()
            );

            \Log::error($logMessage, [
                'exception' => $e::class,
                'trace' => $e->getTraceAsString(),
            ]);

            // Respaldo: muchos entornos leen esto aunque storage/logs no sea escribible o LOG_CHANNEL apunte a null
            error_log($logMessage . ' | ' . $e::class);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage() . ' (L' . $e->getLine() . ')',
                'error_ref' => $errorRef,
                'error_debug' => [
                    'exception' => $e::class,
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace_head' => array_slice(explode("\n", $e->getTraceAsString()), 0, 12),
                ],
            ], 500);
        }
    }

    /**
     * Devuelve ofertas existentes para el mismo producto y tienda.
     * Si se envían especificaciones_internas (y no están vacías), solo devuelve las que coinciden.
     * Si no hay especificaciones (producto sin especs), devuelve todas las ofertas de ese producto en esa tienda.
     * Usado en crear-masivo para mostrar posibles duplicados bajo las especificaciones.
     */
    public function buscarOfertasMismasEspecificaciones(Request $request)
    {
        $request->validate([
            'producto_id' => 'required|exists:productos,id',
            'tienda_id' => 'required|exists:tiendas,id',
            'especificaciones_internas' => 'nullable|string',
        ]);

        $productoId = (int) $request->input('producto_id');
        $tiendaId = (int) $request->input('tienda_id');
        $raw = $request->input('especificaciones_internas');

        $decoded = $raw !== null && $raw !== '' ? json_decode($raw, true) : null;
        $filtrarPorEspecs = is_array($decoded) && !empty($decoded);

        $ofertas = OfertaProducto::with(['producto', 'tienda'])
            ->where('producto_id', $productoId)
            ->where('tienda_id', $tiendaId)
            ->get();

        if ($filtrarPorEspecs) {
            $targetJson = json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION);
            $ofertas = $ofertas->filter(function (OfertaProducto $o) use ($targetJson) {
                $current = $o->especificaciones_internas;
                if (!is_array($current)) return false;
                $json = json_encode($current, JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION);
                return $json === $targetJson;
            })->values();
        }

        $resultado = $ofertas->map(function (OfertaProducto $o) {
            return [
                'id' => $o->id,
                'url' => $o->url,
                'precio_total' => $o->precio_total,
                'precio_unidad' => $o->precio_unidad,
                'envio' => $o->envio,
                'producto' => $o->producto ? $o->producto->nombre : null,
                'tienda' => $o->tienda ? $o->tienda->nombre : null,
                'oferta_edit_url' => route('admin.ofertas.edit', $o),
            ];
        })->toArray();

        return response()->json([
            'success' => true,
            'ofertas' => $resultado,
        ]);
    }

    /**
     * Conteo global (todas las tiendas) de opciones de especificaciones para un producto.
     * Se usa para sugerir la opción más habitual por grupo según lo ya marcado en otros grupos.
     */
    public function contarOpcionesEspecificacionesProducto(Request $request)
    {
        $request->validate([
            'producto_id' => 'required|exists:productos,id',
            'especificaciones_internas' => 'nullable|string',
        ]);

        $productoId = (int) $request->input('producto_id');
        $raw = $request->input('especificaciones_internas');
        $decoded = $raw !== null && $raw !== '' ? json_decode($raw, true) : null;
        $seleccionadas = $this->normalizarEspecificacionesParaConteo(is_array($decoded) ? $decoded : []);

        $ofertas = OfertaProducto::query()
            ->where('producto_id', $productoId)
            ->get(['id', 'especificaciones_internas']);

        $conteos = [];
        $ofertasConSpecs = 0;

        foreach ($ofertas as $oferta) {
            $specsOferta = $this->normalizarEspecificacionesParaConteo(
                is_array($oferta->especificaciones_internas) ? $oferta->especificaciones_internas : []
            );
            if (empty($specsOferta)) {
                continue;
            }
            $ofertasConSpecs++;

            foreach ($specsOferta as $principalId => $subIds) {
                if ($principalId === '_columnas' || !is_array($subIds) || $subIds === []) {
                    continue;
                }
                if (!$this->ofertaCumpleSeleccionEnOtrosGrupos($specsOferta, $seleccionadas, (string) $principalId)) {
                    continue;
                }
                foreach ($subIds as $subId) {
                    $pid = (string) $principalId;
                    $sid = (string) $subId;
                    if (!isset($conteos[$pid])) {
                        $conteos[$pid] = [];
                    }
                    if (!isset($conteos[$pid][$sid])) {
                        $conteos[$pid][$sid] = 0;
                    }
                    $conteos[$pid][$sid]++;
                }
            }
        }

        return response()->json([
            'success' => true,
            'conteos' => $conteos,
            'total_ofertas_producto' => $ofertas->count(),
            'total_ofertas_con_especificaciones' => $ofertasConSpecs,
        ]);
    }

    /**
     * @param  array<string, mixed>  $especs
     * @return array<string, array<int, string>>
     */
    private function normalizarEspecificacionesParaConteo(array $especs): array
    {
        $out = [];
        foreach ($especs as $principalId => $subIds) {
            $pid = (string) $principalId;
            if ($pid === '_columnas') {
                continue;
            }
            if (is_array($subIds)) {
                $vals = array_values(array_unique(array_filter(array_map(fn ($v) => (string) $v, $subIds), fn ($v) => $v !== '')));
            } elseif ($subIds !== null && $subIds !== '') {
                $vals = [(string) $subIds];
            } else {
                $vals = [];
            }
            if ($vals !== []) {
                $out[$pid] = $vals;
            }
        }
        return $out;
    }

    /**
     * Valida si una oferta cumple lo seleccionado en todos los grupos salvo el excluido.
     *
     * @param array<string, array<int, string>> $specsOferta
     * @param array<string, array<int, string>> $seleccionadas
     */
    private function ofertaCumpleSeleccionEnOtrosGrupos(array $specsOferta, array $seleccionadas, string $grupoExcluido): bool
    {
        foreach ($seleccionadas as $principalId => $subIdsSel) {
            $pid = (string) $principalId;
            if ($pid === '_columnas' || $pid === $grupoExcluido) {
                continue;
            }
            if (!is_array($subIdsSel) || $subIdsSel === []) {
                continue;
            }
            $offerVals = $specsOferta[$pid] ?? [];
            if (!is_array($offerVals) || $offerVals === []) {
                return false;
            }
            $inter = array_intersect(
                array_map('strval', $offerVals),
                array_map('strval', $subIdsSel)
            );
            if (empty($inter)) {
                return false;
            }
        }
        return true;
    }

    private function normalizarUrl($url)
    {
        return app(LimpiarUrlDeTiendas::class)->limpiar((string) $url);
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
                'url' => $o->url,
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

    /**
     * Normaliza un fragmento de slug u opción de especificación para comparar con el vocabulario de categoría.
     */
    private function normalizarTokenParaVocabulario(string $texto): string
    {
        $s = mb_strtolower(trim($texto), 'UTF-8');
        $s = str_replace([' ', "\t", '-', '_', '/'], '', $s);
        if (preg_match('/^(\d+)g$/u', $s, $m)) {
            return $m[1] . 'gb';
        }
        return $s;
    }

    /**
     * Expande tokens mixtos letra+número para mejorar matching de slugs.
     * Ej: "rtx3050" => ["rtx3050", "rtx", "3050"].
     *
     * @return array<int, string>
     */
    private function expandirTokenMixtoParaBusqueda(string $token): array
    {
        $base = $this->normalizarTokenParaVocabulario($token);
        if ($base === '') {
            return [];
        }
        $out = [$base];
        if (preg_match('/^([a-z]+)(\d+[a-z0-9]*)$/u', $base, $m)) {
            $out[] = $m[1];
            $out[] = $m[2];
        } elseif (preg_match('/^(\d+)([a-z][a-z0-9]*)$/u', $base, $m)) {
            $out[] = $m[1];
            $out[] = $m[2];
        }
        return array_values(array_unique(array_filter($out, fn ($v) => $v !== '')));
    }

    /**
     * @param  array<int, string> $tokensBase
     * @return array<int, string>
     */
    private function expandirTokensMixtos(array $tokensBase): array
    {
        $out = [];
        foreach ($tokensBase as $t) {
            foreach ($this->expandirTokenMixtoParaBusqueda((string) $t) as $x) {
                $out[] = $x;
            }
        }
        return array_values(array_unique($out));
    }

    /**
     * Tokeniza texto libre (marca/modelo/nombre) para compararlo contra tokens de URL.
     *
     * @return array<int, string>
     */
    private function tokenizarTextoParaBusqueda(string $texto): array
    {
        $raw = preg_split('/[\s\-_\/]+/u', mb_strtolower(trim($texto), 'UTF-8'), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $base = array_values(array_filter(
            array_map(fn ($p) => $this->normalizarTokenParaVocabulario((string) $p), $raw),
            fn ($p) => $p !== ''
        ));
        return $this->expandirTokensMixtos($base);
    }

    /**
     * Extrae textos de opciones seleccionadas en categoria_especificaciones_internas_elegidas.
     * Incluye filtros de categoría y filtros de producto (_producto.filtros), solo opciones marcadas.
     *
     * @param  array<string, mixed>|null  $elegidas
     * @return array<int, string>
     */
    private function extraerTextosOpcionesSeleccionadasProducto(?array $elegidas): array
    {
        if (!is_array($elegidas) || $elegidas === []) {
            return [];
        }

        $textos = [];

        $agregarDesdeFiltros = function (array $filtros) use (&$textos, $elegidas): void {
            foreach ($filtros as $f) {
                $pid = (string) ($f['id'] ?? '');
                if ($pid === '') continue;
                $seleccionadas = $elegidas[$pid] ?? [];
                if (!is_array($seleccionadas) || $seleccionadas === []) continue;
                $idsSel = [];
                foreach ($seleccionadas as $item) {
                    $id = is_array($item) ? ($item['id'] ?? null) : $item;
                    if ($id !== null && $id !== '') $idsSel[(string) $id] = true;
                }
                if ($idsSel === []) continue;
                foreach (($f['subprincipales'] ?? []) as $sub) {
                    $sid = (string) ($sub['id'] ?? '');
                    if ($sid === '' || !isset($idsSel[$sid])) continue;
                    $t = trim((string) ($sub['texto'] ?? ''));
                    if ($t !== '') $textos[] = $t;
                }
            }
        };

        if (isset($elegidas['_producto']['filtros']) && is_array($elegidas['_producto']['filtros'])) {
            $agregarDesdeFiltros($elegidas['_producto']['filtros']);
        }
        if (isset($elegidas['filtros']) && is_array($elegidas['filtros'])) {
            $agregarDesdeFiltros($elegidas['filtros']);
        }

        return array_values(array_unique($textos));
    }

    /**
     * Tokens compactos presentes en las opciones (subprincipales) de especificaciones_internas de la categoría.
     *
     * @return array<string, true>
     */
    private function construirVocabularioTokensDesdeCategoria(int $categoriaId): array
    {
        $cat = Categoria::query()->find($categoriaId, ['id', 'especificaciones_internas']);
        if (!$cat) {
            return [];
        }
        $spec = $cat->especificaciones_internas;
        if (!is_array($spec) || empty($spec['filtros']) || !is_array($spec['filtros'])) {
            return [];
        }
        $set = [];
        foreach ($spec['filtros'] as $filtro) {
            foreach ($filtro['subprincipales'] ?? [] as $sub) {
                $t = trim((string) ($sub['texto'] ?? ''));
                if ($t === '') {
                    continue;
                }
                $full = $this->normalizarTokenParaVocabulario($t);
                if ($full !== '') {
                    $set[$full] = true;
                }
                foreach (preg_split('/[\s\-\/]+/u', $t, -1, PREG_SPLIT_NO_EMPTY) as $parte) {
                    if (mb_strlen($parte, 'UTF-8') < 2) {
                        continue;
                    }
                    $np = $this->normalizarTokenParaVocabulario($parte);
                    if ($np !== '') {
                        $set[$np] = true;
                    }
                }
            }
        }
        return $set;
    }

    /**
     * @param  array<int, string>   $palabras
     * @param  array<string, true>  $vocabSet
     * @return array<int, string>
     */
    private function filtrarPalabrasSlugPorVocabularioCategoria(array $palabras, array $vocabSet): array
    {
        if ($palabras === [] || $vocabSet === []) {
            return $palabras;
        }
        $n = count($palabras);
        $out = [];
        $i = 0;
        while ($i < $n) {
            $matchedLen = 0;
            $maxTry = min(5, $n - $i);
            for ($len = $maxTry; $len >= 2; $len--) {
                $chunk = '';
                for ($k = 0; $k < $len; $k++) {
                    $chunk .= $this->normalizarTokenParaVocabulario($palabras[$i + $k]);
                }
                if ($chunk !== '' && isset($vocabSet[$chunk])) {
                    $matchedLen = $len;
                    break;
                }
            }
            if ($matchedLen > 0) {
                for ($k = 0; $k < $matchedLen; $k++) {
                    $out[] = $palabras[$i + $k];
                }
                $i += $matchedLen;
                continue;
            }
            $one = $this->normalizarTokenParaVocabulario($palabras[$i]);
            if ($one !== '' && isset($vocabSet[$one])) {
                $out[] = $palabras[$i];
            }
            $i++;
        }
        return $out;
    }

    /**
     * Estructura de especificaciones para crear-masivo a partir de la categoría (sin producto).
     * Incluye todas las subopciones del catálogo de la categoría para poder marcarlas desde la URL.
     */
    private function obtenerEspecificacionesDesdeCategoria(int $categoriaId): ?array
    {
        $categoria = Categoria::query()->find($categoriaId, ['id', 'especificaciones_internas']);
        if (!$categoria || !isset($categoria->especificaciones_internas['filtros'])) {
            return null;
        }
        $filtrosCategoria = $categoria->especificaciones_internas['filtros'] ?? [];
        if (!is_array($filtrosCategoria) || $filtrosCategoria === []) {
            return null;
        }

        $resultado = [
            'unidad_de_medida' => 'unidad',
            'columnas_ids' => [],
            'filtros' => [],
        ];

        foreach ($filtrosCategoria as $f) {
            $subprincipales = $f['subprincipales'] ?? [];
            if (!is_array($subprincipales) || $subprincipales === []) {
                continue;
            }
            $sublineas = [];
            foreach ($subprincipales as $sub) {
                $subId = $sub['id'] ?? null;
                if (!$subId) {
                    continue;
                }
                $imagenes = [];
                foreach ([$sub['imagenes'] ?? [], $sub['imagen'] ?? []] as $v) {
                    $imagenes = array_merge($imagenes, is_array($v) ? $v : ($v ? [$v] : []));
                }
                $sublineas[] = array_merge($sub, [
                    'imagenes' => array_values(array_unique(array_filter($imagenes))),
                    'usar_imagenes_producto' => false,
                ]);
            }
            if ($sublineas !== []) {
                $resultado['filtros'][] = [
                    'id' => $f['id'],
                    'texto' => $f['texto'] ?? '',
                    'subprincipales' => $sublineas,
                ];
            }
        }

        return $resultado['filtros'] === [] ? null : $resultado;
    }

    /**
     * Marca especificaciones del producto cuando una opción aparece explícitamente en el slug.
     * - Evita marcar por prefijos ambiguos (ej: "windforce" no marca "windforce oc").
     * - Si hay empate de opciones en un grupo, no marca ninguna.
     *
     * @param array<string, mixed> $productoElegido
     * @return array<string, array<int, string>>
     */
    private function detectarEspecificacionesProductoDesdeUrl(array $productoElegido, string $url): array
    {
        $especs = $productoElegido['especificaciones'] ?? null;
        if (!is_array($especs) || empty($especs['filtros']) || !is_array($especs['filtros'])) {
            return [];
        }

        $path = parse_url($url, PHP_URL_PATH) ?? '';
        $segmentos = array_values(array_filter(explode('/', $path)));
        $slug = end($segmentos);
        if (!is_string($slug) || $slug === '') {
            return [];
        }
        $tokensBase = array_values(array_filter(
            array_map(fn ($p) => $this->normalizarTokenParaVocabulario((string) $p), explode('-', $slug)),
            fn ($p) => $p !== ''
        ));
        $tokens = $this->expandirTokensMixtos($tokensBase);
        if ($tokens === []) {
            return [];
        }

        $out = [];
        foreach ($especs['filtros'] as $f) {
            $principalId = (string) ($f['id'] ?? '');
            if ($principalId === '') {
                continue;
            }
            $candidatas = [];
            foreach (($f['subprincipales'] ?? []) as $sub) {
                $subId = (string) ($sub['id'] ?? '');
                $texto = trim((string) ($sub['texto'] ?? ''));
                if ($subId === '' || $texto === '') {
                    continue;
                }
                $opTokens = array_values(array_filter(
                    array_map(fn ($p) => $this->normalizarTokenParaVocabulario((string) $p), preg_split('/[\s\-\/]+/u', $texto, -1, PREG_SPLIT_NO_EMPTY) ?: []),
                    fn ($p) => $p !== ''
                ));
                if ($opTokens === []) {
                    $opTokens = [$this->normalizarTokenParaVocabulario($texto)];
                }
                $len = count($opTokens);
                for ($i = 0; $i <= count($tokens) - $len; $i++) {
                    if (array_slice($tokens, $i, $len) === $opTokens) {
                        $candidatas[] = ['sub_id' => $subId, 'len' => $len];
                        break;
                    }
                }
            }
            if ($candidatas === []) {
                continue;
            }
            usort($candidatas, fn ($a, $b) => $b['len'] <=> $a['len']);
            $maxLen = $candidatas[0]['len'];
            $top = array_values(array_filter($candidatas, fn ($c) => $c['len'] === $maxLen));
            if (count($top) !== 1) {
                continue; // Ambiguo: mejor no marcar.
            }
            $out[$principalId] = [(string) $top[0]['sub_id']];
        }

        return $out;
    }

    /**
     * @param  array<string, true>  $vocabularioTokens  vacío = no filtrar slug por vocabulario
     */
    private function buscarProductoPorUrl(string $url, ?int $categoriaCatalogoId = null, array $vocabularioTokens = []): array
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

        if ($vocabularioTokens !== []) {
            $filtradas = $this->filtrarPalabrasSlugPorVocabularioCategoria($palabras, $vocabularioTokens);
            if ($filtradas !== []) {
                // Mantener siempre señales fuertes del slug original (modelos/memoria),
                // aunque el vocabulario de categoría no las contenga.
                $fuertesOriginales = array_values(array_filter($palabras, function ($p) {
                    $pn = $this->normalizarTokenParaVocabulario((string) $p);
                    return (bool) preg_match('/^\d{3,4}$/', $pn) // 3050, 7900
                        || (bool) preg_match('/^\d+gb$/', $pn)   // 8gb, 24gb
                        || (bool) preg_match('/^[a-z]\d{3,4}$/', $pn); // t1000
                }));
                $palabras = array_slice(array_values(array_unique(array_merge($filtradas, $fuertesOriginales))), 0, 12);
            }
        }

        // Normalizar: quitar espacios para que "12gb" coincida con "12 GB"
        $normalizar = fn ($s) => str_replace(' ', '', strtolower($s));
        $palabrasNormBase = array_map($normalizar, $palabras);
        $palabrasNorm = $this->expandirTokensMixtos($palabrasNormBase);

        // Palabras que son solo números (3+ cifras): 4070, 7800, 3050… mejoran mucho las sugerencias
        $numerosModelo = array_values(array_filter($palabrasNorm, fn ($p) => preg_match('/^\d{3,}$/', $p)));

        // 1. Candidatos: si hay números de modelo en el slug, buscar por ellos; si no, por cualquier palabra
        $query = Producto::where('obsoleto', 'no');
        if ($categoriaCatalogoId !== null) {
            $query->where('categoria_id', $categoriaCatalogoId);
        }
        if (!empty($numerosModelo)) {
            $query->where(function ($q) use ($numerosModelo) {
                foreach ($numerosModelo as $num) {
                    $q->orWhere(function ($q2) use ($num) {
                        $q2->whereRaw('LOWER(REPLACE(nombre, \' \', \'\')) LIKE ?', ['%' . $num . '%'])
                            ->orWhereRaw('LOWER(REPLACE(modelo, \' \', \'\')) LIKE ?', ['%' . $num . '%']);
                    });
                }
            });
        } else {
            $query->where(function ($q) use ($palabrasNorm) {
                foreach ($palabrasNorm as $pNorm) {
                    $q->orWhere(function ($q2) use ($pNorm) {
                        $q2->whereRaw('LOWER(REPLACE(nombre, \' \', \'\')) LIKE ?', ['%' . $pNorm . '%'])
                            ->orWhereRaw('LOWER(REPLACE(marca, \' \', \'\')) LIKE ?', ['%' . $pNorm . '%'])
                            ->orWhereRaw('LOWER(REPLACE(modelo, \' \', \'\')) LIKE ?', ['%' . $pNorm . '%'])
                            ->orWhereRaw('LOWER(REPLACE(talla, \' \', \'\')) LIKE ?', ['%' . $pNorm . '%'])
                            ->orWhereRaw('LOWER(REPLACE(slug, \' \', \'\')) LIKE ?', ['%' . $pNorm . '%']);
                    });
                }
            });
        }

        $productos = $query->with('categoria')
            ->orderBy('clicks', 'desc')
            ->limit(800)
            ->get(['id', 'nombre', 'marca', 'modelo', 'talla', 'slug', 'categoria_id', 'imagen_grande', 'imagen_pequena', 'clicks', 'categoria_especificaciones_internas_elegidas']);

        // Incluir productos cuya marca coincida con alguna palabra del slug (asegura que Gigabyte esté si la URL dice gigabyte)
        $palabrasMarca = array_values(array_filter($palabrasNorm, fn ($p) => strlen($p) >= 3 && preg_match('/^\D+$/', $p)));
        if (!empty($palabrasMarca)) {
            $extra = Producto::where('obsoleto', 'no')
                ->when($categoriaCatalogoId !== null, fn ($q) => $q->where('categoria_id', $categoriaCatalogoId))
                ->where(function ($q) use ($palabrasMarca) {
                    foreach ($palabrasMarca as $pNorm) {
                        $q->orWhereRaw('LOWER(REPLACE(marca, \' \', \'\')) LIKE ?', ['%' . $pNorm . '%']);
                    }
                })
                ->with('categoria')
                ->get(['id', 'nombre', 'marca', 'modelo', 'talla', 'slug', 'categoria_id', 'imagen_grande', 'imagen_pequena', 'clicks', 'categoria_especificaciones_internas_elegidas']);
            $productos = $productos->merge($extra)->unique('id')->values();
        }

        // Si hay varias marcas detectables en la URL, priorizar la más específica (token más largo).
        // Ejemplo: "powercolor ... amd" => priorizar powercolor sobre amd.
        if ($productos->isNotEmpty()) {
            $marcasDetectadas = [];
            $marcasGenericas = ['nvidia', 'amd', 'intel'];
            foreach ($productos as $p) {
                $brand = $normalizar((string) ($p->marca ?? ''));
                if ($brand === '') {
                    continue;
                }
                if (in_array($brand, $palabrasNorm, true)) {
                    $esGenerica = in_array($brand, $marcasGenericas, true);
                    // Priorizar ensamblador (pny, zotac, powercolor...) frente a genérica (nvidia/amd/intel).
                    $peso = ($esGenerica ? 0 : 100) + strlen($brand);
                    $marcasDetectadas[$brand] = $peso;
                }
            }
            if (!empty($marcasDetectadas)) {
                arsort($marcasDetectadas);
                $marcaObjetivo = array_key_first($marcasDetectadas);
                $conMarcaObjetivo = $productos->filter(function ($p) use ($normalizar, $marcaObjetivo) {
                    return $normalizar((string) ($p->marca ?? '')) === $marcaObjetivo;
                })->values();
                if ($conMarcaObjetivo->isNotEmpty()) {
                    $productos = $conMarcaObjetivo;
                }
            }
        }

        // Si la URL trae memoria explícita (ej: 24gb), fijarla para evitar cruces 24gb -> 48gb.
        $memoriasUrl = array_values(array_unique(array_filter($palabrasNorm, fn ($p) => preg_match('/^\d+gb$/', $p) === 1)));
        if (!empty($memoriasUrl) && $productos->isNotEmpty()) {
            $conMemoria = $productos->filter(function ($p) use ($normalizar, $memoriasUrl) {
                $texto = $normalizar(($p->nombre ?? '') . ' ' . ($p->modelo ?? '') . ' ' . ($p->talla ?? '') . ' ' . ($p->slug ?? ''));
                foreach ($memoriasUrl as $mem) {
                    if (str_contains($texto, $mem)) {
                        return true;
                    }
                }
                return false;
            })->values();
            if ($conMemoria->isNotEmpty()) {
                $productos = $conMemoria;
            }
        }

        // Si en la URL aparece un modelo fuerte (3060, 7900, t1000...), exigir compatibilidad.
        // Preferimos "no encontrado" antes que asignar un modelo incorrecto.
        $modelosFuertes = array_values(array_unique(array_filter(
            $palabrasNorm,
            fn ($p) => preg_match('/^\d{3,4}$/', $p) === 1 || preg_match('/^[a-z]\d{3,4}$/', $p) === 1
        )));
        if (!empty($modelosFuertes)) {
            $conModeloFuerte = $productos->filter(function ($p) use ($normalizar, $modelosFuertes) {
                $texto = $normalizar(($p->nombre ?? '') . ' ' . ($p->modelo ?? '') . ' ' . ($p->slug ?? ''));
                foreach ($modelosFuertes as $mf) {
                    if (preg_match('/^\d{3,4}$/', $mf) === 1) {
                        // Para números puros, usar frontera numérica: 3050 no debe casar con 13050.
                        if (preg_match('/(^|[^0-9])' . preg_quote($mf, '/') . '([^0-9]|$)/u', $texto)) {
                            return true;
                        }
                    } else {
                        // Para tokens tipo t1000, basta coincidencia compacta.
                        if (str_contains($texto, $mf)) {
                            return true;
                        }
                    }
                }
                return false;
            })->values();

            if ($conModeloFuerte->isEmpty()) {
                return [];
            }
            $productos = $conModeloFuerte;
        }

        // Si el slug contiene tokens determinantes de variante (super/ti/xt/xtx),
        // descartar productos incompatibles para evitar cruces tipo "3060 ti" -> "3060".
        $tokensDeterminantes = ['super', 'ti', 'xt', 'xtx'];
        foreach ($tokensDeterminantes as $tk) {
            $enSlug = in_array($tk, $palabrasNorm, true);
            $compatibles = $productos->filter(function ($p) use ($normalizar, $tk, $enSlug) {
                $texto = $normalizar(($p->nombre ?? '') . ' ' . ($p->modelo ?? '') . ' ' . ($p->slug ?? ''));
                $enProducto = str_contains($texto, $tk);
                return $enSlug === $enProducto;
            })->values();
            if ($compatibles->isNotEmpty()) {
                $productos = $compatibles;
            }
        }

        // 2. Puntuación: coincidencias del slug + bonus por marca/modelo exacto + penalización por sufijos incompatibles.
        $tokensSlugSet = array_fill_keys($palabrasNorm, true);
        $productosConPuntuacion = $productos->map(function ($p) use ($palabrasNorm, $normalizar, $tokensDeterminantes, $tokensSlugSet) {
            $textoProducto = $normalizar($p->nombre . ' ' . ($p->marca ?? '') . ' ' . ($p->modelo ?? '') . ' ' . ($p->talla ?? '') . ' ' . ($p->slug ?? ''));
            $marcaNorm = $normalizar($p->marca ?? '');
            $modeloNorm = $normalizar($p->modelo ?? '');
            $puntuacion = 0;
            foreach ($palabrasNorm as $pNorm) {
                $coincide = str_contains($textoProducto, $pNorm);
                if ($coincide) {
                    $puntuacion += 1;
                    if ($marcaNorm !== '' && (str_contains($marcaNorm, $pNorm) || str_contains($pNorm, $marcaNorm))) {
                        $puntuacion += 20; // marca en URL = producto correcto
                    }
                    if (preg_match('/\d/', $pNorm)) {
                        $puntuacion += 8; // 5080, 16gb muy decisivos
                    }
                } else {
                    // Palabra no coincide: solo penalizar fuerte si es número de modelo (4070, 3050); variantes (gddr6x, 8gb) penalizan poco
                    $esNumeroClave = preg_match('/^\d{3,}$/', $pNorm);
                    if ($esNumeroClave) {
                        $puntuacion -= 15;
                    } elseif (preg_match('/\d/', $pNorm)) {
                        $puntuacion -= 2;
                    }
                }
            }
            if ($marcaNorm !== '' && isset($tokensSlugSet[$marcaNorm])) {
                $puntuacion += 14;
            }

            // Bonus por opciones seleccionadas del producto (modelo/color/memoria...) que aparecen en el slug.
            $opcionesSel = $this->extraerTextosOpcionesSeleccionadasProducto(
                is_array($p->categoria_especificaciones_internas_elegidas ?? null) ? $p->categoria_especificaciones_internas_elegidas : null
            );
            $tokensOpciones = [];
            foreach ($opcionesSel as $txtOpt) {
                foreach ($this->tokenizarTextoParaBusqueda((string) $txtOpt) as $tk) {
                    $tokensOpciones[$tk] = true;
                }
            }
            if (!empty($tokensOpciones)) {
                $matchesOpt = 0;
                foreach (array_keys($tokensOpciones) as $tk) {
                    if (isset($tokensSlugSet[$tk])) $matchesOpt++;
                }
                if ($matchesOpt > 0) {
                    $puntuacion += ($matchesOpt * 6);
                }
            }

            $modeloTokens = $this->tokenizarTextoParaBusqueda((string) ($p->modelo ?? ''));
            if ($modeloTokens !== []) {
                $matchedModelo = 0;
                foreach ($modeloTokens as $mt) {
                    if (isset($tokensSlugSet[$mt])) {
                        $matchedModelo++;
                    }
                }
                if ($matchedModelo > 0) {
                    $puntuacion += ($matchedModelo * 8);
                    if ($matchedModelo === count($modeloTokens) && count($modeloTokens) >= 2) {
                        $puntuacion += 20; // Modelo completo por tokens (ej: amd radeon rx 7900 xtx).
                    }
                }
            }
            foreach ($tokensDeterminantes as $tk) {
                $enSlug = isset($tokensSlugSet[$tk]);
                $enModelo = str_contains($modeloNorm, $tk);
                if ($enSlug !== $enModelo) {
                    $puntuacion -= 45;
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

        if ($productosFiltrados->isEmpty()) {
            return [];
        }
        $top = $productosFiltrados[0]['puntuacion'] ?? 0;
        $segundo = $productosFiltrados[1]['puntuacion'] ?? null;
        if ($top < 14) {
            return []; // Si no hay confianza mínima, mejor que no asigne automáticamente.
        }
        if ($segundo !== null && ($top - $segundo) < 5 && $top < 36) {
            return []; // Empate dudoso en zona media.
        }

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

    /**
     * Construye el array de producto para crear-masivo (mismo formato que un elemento de buscarProductoPorUrl).
     * Usado cuando "Mismo producto" está activo: todas las URLs usan este producto y sus especificaciones.
     */
    private function construirProductoParaCrearMasivo($productoId)
    {
        $p = Producto::with('categoria')->find($productoId);
        if (!$p) {
            return null;
        }
        $urlProducto = null;
        if ($p->categoria) {
            $path = \App\Helpers\CategoriaHelper::construirUrlCategorias($p->categoria->id, $p->slug);
            $urlProducto = url($path);
        }
        $imgs = [];
        foreach ([$p->imagen_grande ?? [], $p->imagen_pequena ?? []] as $val) {
            $imgs = array_merge($imgs, is_array($val) ? $val : ($val ? [$val] : []));
        }
        $imgs = array_values(array_unique(array_filter($imgs)));
        $especs = $this->obtenerEspecificacionesProducto($productoId);
        $tieneEspecs = $especs && !empty($especs['filtros'] ?? []);

        return [
            'id' => $p->id,
            'nombre' => $p->nombre,
            'marca' => $p->marca,
            'modelo' => $p->modelo,
            'talla' => $p->talla,
            'texto_completo' => trim($p->nombre . ' - ' . ($p->marca ?? '') . ' - ' . ($p->modelo ?? '') . ' - ' . ($p->talla ?? '')),
            'url_producto' => $urlProducto,
            'imagenes_producto' => $imgs,
            'especificaciones' => $especs,
            'tiene_especificaciones' => $tieneEspecs,
        ];
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
        $idsFiltrosProducto = [];
        foreach ($filtrosProducto as $fp) {
            if (!empty($fp['id'])) {
                $idsFiltrosProducto[(string) $fp['id']] = true;
            }
        }

        $resultado = [
            'unidad_de_medida' => $producto->unidadDeMedida ?? 'unidad',
            'columnas_ids' => $columnasIds,
            'formatos' => $this->normalizarFormatosEspecificacionesProducto($especificacionesElegidas),
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
                            $usarImagenesProducto = is_array($item) && !empty($item['usarImagenesProducto']);
                            $imagenes = [];
                            if (! $usarImagenesProducto) {
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
                            }
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
                    'es_producto' => isset($idsFiltrosProducto[(string) ($f['id'] ?? '')]),
                    'subprincipales' => $sublineasOferta,
                ];
            }
        }

        return $resultado;
    }

    /**
     * @return array<string, string>
     */
    private function normalizarFormatosEspecificacionesProducto(array $especificacionesElegidas): array
    {
        $formatosRaw = $especificacionesElegidas['_formatos'] ?? [];
        $formatos = [];
        if (!is_array($formatosRaw)) {
            return $formatos;
        }
        foreach ($formatosRaw as $k => $v) {
            $key = (string) $k;
            if ($key === '' || str_starts_with($key, '_')) {
                continue;
            }
            if (is_array($v) && isset($v['id']) && is_string($v['id'])) {
                $formatos[$key] = $v['id'];
            } elseif (is_string($v)) {
                $formatos[$key] = $v;
            }
        }

        return $formatos;
    }

    /**
     * Calcula el envío desde envio_gratis/envio_normal de la tienda.
     * Misma lógica que actualizarEnvioSegunTienda en formulario.blade.php.
     * Formatos: "Gratis"->null, "< 4,99 €"->4.99, "Gratis > Con Prime"->null, "3,94 < Punto recogida"->3.94
     */
    private function calcularEnvioDesdeTienda(Tienda $tienda)
    {
        $texto = trim((string) ($tienda->envio_gratis ?? ''));
        if ($texto === '') {
            $texto = trim((string) ($tienda->envio_normal ?? ''));
        }
        if ($texto === '') {
            return [null, false];
        }
        // Si contiene "gratis" -> envío gratis (null)
        if (stripos($texto, 'gratis') !== false) {
            return [null, true];
        }
        // Extraer precio: igual que formulario match(/(\d+[,.]?\d*)\s*€?/)
        $textoNorm = str_replace(['‚', '，', '٫', "\xC2\xA0"], [',', ',', ',', ' '], $texto);
        // Primero: patrón con decimal "3,94" o "4,99" (más fiable para "3,94 < Punto recogida")
        if (preg_match('/(\d+[.,]\d+)/', $textoNorm, $m) && isset($m[1])) {
            $valor = (float) str_replace(',', '.', $m[1]);
            return [$valor > 0 ? $valor : null, false];
        }
        // Fallback: "2" o "2€" sin decimales
        if (preg_match('/(\d+[,.]?\d*)\s*€?/', $textoNorm, $m) && isset($m[1]) && $m[1] !== '') {
            $valor = (float) str_replace(',', '.', $m[1]);
            return [$valor > 0 ? $valor : null, false];
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

