<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Aviso;
use App\Models\Tienda;
use App\Models\Categoria;
use App\Models\ComisionCategoriaTienda;
use App\Models\Producto;
use App\Models\Chollo;
use App\Models\OfertaProducto;
use App\Models\ProductoOfertaMasBarataPorProducto;
use App\Models\User;
use Carbon\Carbon;

class AvisoController extends Controller
{
    public function index(Request $request)
    {
        $userId = auth()->id();
        $perPage = $request->get('perPage', 20);
        
        // Validar perPage
        if (!in_array($perPage, [10, 20, 50, 100])) {
            $perPage = 20;
        }
        
        // Verificar si el usuario ID 1 quiere ver todos los avisos
        $mostrarTodos = false;
        if ($userId === 1 && session('avisos_mostrar_todos', false)) {
            $mostrarTodos = true;
        }
        
        // Obtener avisos vencidos (solo visibles) con paginación
        $avisosVencidosQuery = Aviso::with(['user'])
            ->vencidos()
            ->visibles();
        
        // Aplicar filtro por usuario solo si no se está mostrando todos
        if (!$mostrarTodos) {
            $avisosVencidosQuery->visiblesPorUsuario($userId);
        }
        
        $avisosVencidosQuery->orderBy('fecha_aviso', 'desc');
        
        $totalVencidos = $avisosVencidosQuery->count();
        $avisosVencidos = $avisosVencidosQuery->paginate($perPage, ['*'], 'vencidos_page');
        $avisosVencidos->appends(['perPage' => $perPage]);

        // Obtener avisos pendientes (solo visibles) con paginación
        $avisosPendientesQuery = Aviso::with(['user'])
            ->pendientes()
            ->visibles();
        
        // Aplicar filtro por usuario solo si no se está mostrando todos
        if (!$mostrarTodos) {
            $avisosPendientesQuery->visiblesPorUsuario($userId);
        }
        
        $avisosPendientesQuery->orderBy('fecha_aviso', 'asc');
        
        $totalPendientes = $avisosPendientesQuery->count();
        $avisosPendientes = $avisosPendientesQuery->paginate($perPage, ['*'], 'pendientes_page');
        $avisosPendientes->appends(['perPage' => $perPage]);

        // Obtener avisos ocultos con paginación
        $avisosOcultosQuery = Aviso::with(['user'])
            ->ocultos();
        
        // Aplicar filtro por usuario solo si no se está mostrando todos
        if (!$mostrarTodos) {
            $avisosOcultosQuery->visiblesPorUsuario($userId);
        }
        
        $avisosOcultosQuery->orderBy('fecha_aviso', 'desc');
        
        $totalOcultos = $avisosOcultosQuery->count();
        $avisosOcultos = $avisosOcultosQuery->paginate($perPage, ['*'], 'ocultos_page');
        $avisosOcultos->appends(['perPage' => $perPage]);

        // Cargar relaciones avisoable solo para avisos que no sean internos
        foreach ([$avisosVencidos, $avisosPendientes, $avisosOcultos] as $avisos) {
            foreach ($avisos as $aviso) {
                // Solo cargar avisoable si no es un aviso interno
                if ($aviso->avisoable_type !== 'Interno' && $aviso->avisoable_id !== 0) {
                    try {
                        // Solo cargar categoria para productos
                        if ($aviso->avisoable_type === 'App\Models\Producto') {
                            $aviso->load('avisoable.categoria');
                        } elseif ($aviso->avisoable_type === 'App\Models\OfertaProducto') {
                            // Para ofertas, cargar también el producto para acceder a unidadDeMedida
                            $aviso->load('avisoable.producto', 'avisoable.tienda');
                        } elseif ($aviso->avisoable_type === 'App\Models\Chollo') {
                            $aviso->load('avisoable.producto', 'avisoable.tienda', 'avisoable.categoria');
                        } else {
                            $aviso->load('avisoable');
                        }
                    } catch (\Exception $e) {
                        // Si hay error al cargar la relación, continuar sin ella
                        \Log::warning('Error al cargar relación avisoable para aviso ID: ' . $aviso->id . ' - ' . $e->getMessage());
                    }
                }
            }
        }

        // Detectar avisos de productos con precio NULL
        $avisosProductoPrecioNullQuery = Aviso::query()
            ->leftJoin('productos', 'productos.id', '=', 'avisos.avisoable_id')
            ->where('avisos.avisoable_type', 'App\Models\Producto')
            ->where('avisos.texto_aviso', 'like', '%Precio actualizado producto%')
            ->whereNull('productos.precio');
        
        // Aplicar filtro por usuario solo si no se está mostrando todos
        if (!$mostrarTodos) {
            $avisosProductoPrecioNullQuery->visiblesPorUsuario($userId);
        }
        
        $avisosProductoPrecioNullQuery->select([
                'avisos.id as aviso_id',
                'avisos.avisoable_id as producto_id',
                'avisos.fecha_aviso as fecha_aviso',
                'avisos.oculto as oculto',
                'productos.nombre as producto_nombre',
            ])
            ->orderBy('avisos.fecha_aviso', 'desc');
        
        $avisosProductoPrecioNullCount = (clone $avisosProductoPrecioNullQuery)->count('avisos.id');
        $avisosProductoPrecioNull = $avisosProductoPrecioNullQuery->limit(50)->get();

        // Obtener todos los usuarios para el desplegable de crear aviso interno
        $usuarios = User::orderBy('name')->get();

        return view('admin.avisos.index', compact('avisosVencidos', 'avisosPendientes', 'avisosOcultos', 'perPage', 'totalVencidos', 'totalPendientes', 'totalOcultos', 'avisosProductoPrecioNullCount', 'avisosProductoPrecioNull', 'mostrarTodos', 'usuarios'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'texto_aviso' => 'required|string|max:1000',
            'fecha_aviso' => 'required|date',
            'avisoable_type' => 'nullable|string|in:App\Models\Producto,App\Models\OfertaProducto,App\Models\Chollo,App\Models\Tienda',
            'avisoable_id' => 'nullable|integer',
            'oculto' => 'boolean'
        ]);

        $aviso = Aviso::create([
            'texto_aviso' => $request->texto_aviso,
            'fecha_aviso' => $request->fecha_aviso,
            'user_id' => auth()->id(),
            'avisoable_type' => $request->avisoable_type,
            'avisoable_id' => $request->avisoable_id,
            'oculto' => $request->oculto ?? false
        ]);

        return response()->json([
            'success' => true,
            'aviso' => $aviso->load('user'),
            'message' => 'Aviso creado correctamente'
        ]);
    }

    public function storeInterno(Request $request)
    {
        try {
            // Validar datos de entrada
            $request->validate([
                'texto_aviso' => 'required|string|max:1000',
                'fecha_aviso' => 'required|date',
                'oculto' => 'boolean',
                'user_ids' => 'nullable|array',
                'user_ids.*' => 'integer|exists:users,id',
                // Mantener compatibilidad con el formato anterior
                'user_id' => 'nullable|integer|exists:users,id'
            ]);

            // Verificar que el usuario está autenticado
            if (!auth()->check()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no autenticado'
                ], 401);
            }

            $textoAviso = $request->texto_aviso;
            $fechaAviso = $request->fecha_aviso;
            $oculto = $request->oculto ?? false;
            
            // Obtener los IDs de usuarios seleccionados
            $userIds = $request->user_ids;
            
            // Compatibilidad con el formato anterior (user_id único)
            if ($userIds === null && $request->has('user_id')) {
                $userIdSeleccionado = $request->user_id;
                if ($userIdSeleccionado === null || $userIdSeleccionado === 'todos' || $userIdSeleccionado === '') {
                    $userIds = null; // null significa "todos"
                } else {
                    $userIds = [$userIdSeleccionado];
                }
            }

            // Si user_ids es null, crear aviso para todos los usuarios
            if ($userIds === null) {
                // Obtener todos los usuarios
                $usuarios = User::all();
                $avisosCreados = [];

                foreach ($usuarios as $usuario) {
                    $aviso = Aviso::create([
                        'texto_aviso' => $textoAviso,
                        'fecha_aviso' => $fechaAviso,
                        'user_id' => $usuario->id,
                        'avisoable_type' => 'Interno',
                        'avisoable_id' => 0,
                        'oculto' => $oculto
                    ]);
                    $avisosCreados[] = $aviso;
                }

                return response()->json([
                    'success' => true,
                    'avisos' => $avisosCreados,
                    'message' => 'Aviso interno creado correctamente para ' . count($avisosCreados) . ' usuario(s)'
                ]);
            } else {
                // Crear aviso para cada usuario seleccionado
                $avisosCreados = [];

                foreach ($userIds as $userId) {
                    $aviso = Aviso::create([
                        'texto_aviso' => $textoAviso,
                        'fecha_aviso' => $fechaAviso,
                        'user_id' => $userId,
                        'avisoable_type' => 'Interno',
                        'avisoable_id' => 0,
                        'oculto' => $oculto
                    ]);
                    $avisosCreados[] = $aviso;
                }

                return response()->json([
                    'success' => true,
                    'avisos' => $avisosCreados,
                    'message' => 'Aviso interno creado correctamente para ' . count($avisosCreados) . ' usuario(s)'
                ]);
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::warning('Error de validación al crear aviso interno: ' . json_encode($e->errors()));
            return response()->json([
                'success' => false,
                'message' => 'Error de validación: ' . implode(', ', $e->errors())
            ], 422);
        } catch (\Illuminate\Database\QueryException $e) {
            \Log::error('Error de base de datos al crear aviso interno: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error de base de datos: ' . $e->getMessage()
            ], 500);
        } catch (\Exception $e) {
            \Log::error('Error inesperado al crear aviso interno: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor'
            ], 500);
        }
    }

    public function update(Request $request, Aviso $aviso)
    {
        // Verificar que el usuario puede editar este aviso
        if ($aviso->user_id !== auth()->id() && auth()->id() !== 1) {
            abort(403, 'No tienes permisos para editar este aviso');
        }

        $request->validate([
            'texto_aviso' => 'required|string|max:1000',
            'fecha_aviso' => 'required|date',
            'oculto' => 'boolean'
        ]);

        $aviso->update([
            'texto_aviso' => $request->texto_aviso,
            'fecha_aviso' => $request->fecha_aviso,
            'oculto' => $request->oculto ?? false
        ]);

        // Eliminar avisos duplicados vencidos después de gestionar este aviso
        // Solo para avisos de tipo Producto u OfertaProducto
        if (in_array($aviso->avisoable_type, ['App\Models\Producto', 'App\Models\OfertaProducto'])) {
            $this->eliminarAvisosDuplicadosVencidos($aviso);
        }

        return response()->json([
            'success' => true,
            'aviso' => $aviso->load('user'),
            'message' => 'Aviso actualizado correctamente'
        ]);
    }

    public function getTextoAviso(Aviso $aviso)
    {
        // Verificar que el usuario puede ver este aviso
        if ($aviso->user_id !== auth()->id() && auth()->id() !== 1) {
            abort(403, 'No tienes permisos para ver este aviso');
        }

        return response()->json([
            'success' => true,
            'texto_aviso' => $aviso->texto_aviso
        ]);
    }

    public function destroy(Aviso $aviso)
    {
        // Verificar que el usuario puede eliminar este aviso
        if ($aviso->user_id !== auth()->id() && auth()->id() !== 1) {
            abort(403, 'No tienes permisos para eliminar este aviso');
        }

        // Guardar información del aviso antes de eliminarlo para buscar duplicados
        $avisoableType = $aviso->avisoable_type;
        $avisoableId = $aviso->avisoable_id;
        $textoAviso = $aviso->texto_aviso;
        $avisoId = $aviso->id;

        $aviso->delete();

        // Eliminar avisos duplicados vencidos después de gestionar este aviso
        // Solo para avisos de tipo Producto u OfertaProducto
        if (in_array($avisoableType, ['App\Models\Producto', 'App\Models\OfertaProducto'])) {
            // Crear un objeto temporal para pasar a la función
            $avisoTemporal = new Aviso();
            $avisoTemporal->id = $avisoId;
            $avisoTemporal->avisoable_type = $avisoableType;
            $avisoTemporal->avisoable_id = $avisoableId;
            $avisoTemporal->texto_aviso = $textoAviso;
            $this->eliminarAvisosDuplicadosVencidos($avisoTemporal);
        }

        return response()->json([
            'success' => true,
            'message' => 'Aviso eliminado correctamente'
        ]);
    }

    public function getAvisosElemento(Request $request)
    {
        $request->validate([
            'avisoable_type' => 'required|string|in:App\Models\Producto,App\Models\OfertaProducto,App\Models\Chollo,App\Models\Tienda',
            'avisoable_id' => 'required|integer'
        ]);

        $userId = auth()->id();
        
        $avisos = Aviso::with('user')
            ->where('avisoable_type', $request->avisoable_type)
            ->where('avisoable_id', $request->avisoable_id)
            ->visiblesPorUsuario($userId)
            ->orderBy('fecha_aviso', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'avisos' => $avisos
        ]);
    }

    // Ejecuciones automáticas de comprobaciones
    public function ejecutarComprobacionComisiones()
    {
        $userId = auth()->id();
        
        // Obtener todas las tiendas
        $tiendas = Tienda::all();
        $categorias = Categoria::all();
        
        foreach ($tiendas as $tienda) {
            foreach ($categorias as $categoria) {
                // Verificar si existe comisión para esta tienda y categoría
                $comision = ComisionCategoriaTienda::where('tienda_id', $tienda->id)
                    ->where('categoria_id', $categoria->id)
                    ->first();

                if (!$comision || $comision->comision == 0) {
                    // Verificar si ya existe un aviso para esta tienda y categoría (incluyendo ocultos)
                    $avisoExistente = Aviso::where('avisoable_type', 'App\Models\Tienda')
                        ->where('avisoable_id', $tienda->id)
                        ->where('texto_aviso', 'like', '%comisión%' . $categoria->nombre . '%')
                        ->where('user_id', $userId)
                        ->first();

                    if (!$avisoExistente) {
                        // Crear aviso
                        Aviso::create([
                            'texto_aviso' => "La tienda {$tienda->nombre} tiene la comisión a 0 en la categoría {$categoria->nombre}",
                            'fecha_aviso' => now(),
                            'user_id' => $userId,
                            'avisoable_type' => 'App\Models\Tienda',
                            'avisoable_id' => $tienda->id,
                            'oculto' => false
                        ]);
                    }
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Comprobación de comisiones ejecutada correctamente'
        ]);
    }

    public function ejecutarComprobacionProductosSinOfertas()
    {
        $userId = auth()->id();
        
        // Obtener productos que no tienen ofertas activas
        $productosSinOfertas = Producto::whereDoesntHave('ofertas', function($query) {
            $query->where('mostrar', 'si');
        })->get();

        foreach ($productosSinOfertas as $producto) {
            // Verificar si ya existe un aviso para este producto (incluyendo ocultos)
            $avisoExistente = Aviso::where('avisoable_type', 'App\Models\Producto')
                ->where('avisoable_id', $producto->id)
                ->where('texto_aviso', 'like', '%sin ofertas%')
                ->where('user_id', $userId)
                ->first();

            if (!$avisoExistente) {
                // Crear aviso
                Aviso::create([
                    'texto_aviso' => "El producto {$producto->nombre} no tiene ofertas activas para mostrar",
                    'fecha_aviso' => now(),
                    'user_id' => $userId,
                    'avisoable_type' => 'App\Models\Producto',
                    'avisoable_id' => $producto->id,
                    'oculto' => false
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Comprobación de productos sin ofertas ejecutada correctamente'
        ]);
    }

    public function ejecutarComprobacionOfertasVencidas()
    {
        $userId = auth()->id();
        
        // Obtener ofertas que tienen avisos vencidos (campo aviso antiguo)
        $ofertasConAvisosVencidos = OfertaProducto::whereNotNull('aviso')
            ->where('aviso', '<=', now())
            ->get();

        foreach ($ofertasConAvisosVencidos as $oferta) {
            // Verificar si ya existe un aviso en la nueva tabla (incluyendo ocultos)
            $avisoExistente = Aviso::where('avisoable_type', 'App\Models\OfertaProducto')
                ->where('avisoable_id', $oferta->id)
                ->where('texto_aviso', 'like', '%revisar%')
                ->where('user_id', $userId)
                ->first();

            if (!$avisoExistente) {
                // Crear aviso
                Aviso::create([
                    'texto_aviso' => "Revisar oferta: {$oferta->producto->nombre} - {$oferta->tienda->nombre}",
                    'fecha_aviso' => now(),
                    'user_id' => $userId,
                    'avisoable_type' => 'App\Models\OfertaProducto',
                    'avisoable_id' => $oferta->id,
                    'oculto' => false
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Comprobación de ofertas vencidas ejecutada correctamente'
        ]);
    }

    /**
     * Obtener información de alertas para un producto (para mostrar en modal)
     */
    public function obtenerInfoAlertasProducto(Request $request)
    {
        $request->validate([
            'producto_id' => 'required|exists:productos,id',
            'precio_actual' => 'required|numeric|min:0'
        ]);

        try {
            $producto = Producto::findOrFail($request->producto_id);
            
            // Obtener alertas que cumplen las condiciones
            $alertas = \App\Models\CorreoAvisoPrecio::where('producto_id', $request->producto_id)
                ->where('precio_limite', '>=', $request->precio_actual)
                ->where(function($query) {
                    $query->whereNull('ultimo_envio_correo')
                          ->orWhere('ultimo_envio_correo', '<', now()->subWeek());
                })
                ->get();
            
            // Agrupar por precio_limite y contar
            $preciosAgrupados = $alertas->groupBy('precio_limite')->map(function($group) {
                return $group->count();
            })->toArray();
            
            // Ordenar por precio (mayor a menor)
            krsort($preciosAgrupados);
            
            return response()->json([
                'success' => true,
                'precio_producto' => $producto->precio,
                'precios_limites' => $preciosAgrupados,
                'total_alertas' => $alertas->count()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener información: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Enviar correos de alerta para un producto específico
     */
    public function enviarAlertasProducto(Request $request)
    {
        $request->validate([
            'producto_id' => 'required|exists:productos,id',
            'precio_actual' => 'required|numeric|min:0'
        ]);

        try {
            $alertaController = new \App\Http\Controllers\AlertaPrecioController();
            $resultado = $alertaController->enviarAlertasPrecio($request->producto_id, $request->precio_actual);

            return response()->json($resultado);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al enviar alertas: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Aplazar un aviso según el patrón de texto
     */
    public function aplazar(Aviso $aviso)
    {
        try {
            // Verificar que el usuario puede aplazar este aviso
            if ($aviso->user_id !== auth()->id() && auth()->id() !== 1) {
                abort(403, 'No tienes permisos para aplazar este aviso');
            }

            // Buscar el número en el texto usando regex
            $textoAviso = $aviso->texto_aviso;
            $numeroActual = null;
            
            // Buscar patrones como "1a vez", "1 vez", "1vez", etc.
            if (preg_match('/(\d+)\s*(?:a\s*)?vez/i', $textoAviso, $matches)) {
                $numeroActual = (int)$matches[1];
            } else {
                // Si no se encuentra el patrón, empezar desde 0
                $numeroActual = 0;
            }

            // Calcular nuevas fecha y número según la lógica
            $fechaActual = Carbon::parse($aviso->fecha_aviso);
            $nuevaFecha = null;
            $nuevoNumero = null;

            switch ($numeroActual) {
                case 0:
                    $nuevaFecha = $fechaActual->addDay();
                    $nuevoNumero = 1;
                    break;
                case 1:
                    $nuevaFecha = $fechaActual->addDays(3);
                    $nuevoNumero = 2;
                    break;
                case 2:
                    $nuevaFecha = $fechaActual->addWeek();
                    $nuevoNumero = 3;
                    break;
                case 3:
                    $nuevaFecha = $fechaActual->addWeek();
                    $nuevoNumero = 4;
                    break;
                case 4:
                    $nuevaFecha = $fechaActual->addWeeks(2);
                    $nuevoNumero = 5;
                    break;
                default: // 5 o mayor
                    $nuevaFecha = $fechaActual->addMonth();
                    $nuevoNumero = $numeroActual + 1;
                    break;
            }

            // Actualizar el texto del aviso reemplazando el número
            $nuevoTexto = preg_replace('/(\d+)\s*(?:a\s*)?vez/i', $nuevoNumero . 'a vez', $textoAviso, 1);
            
            // Si no había número, agregar "1a vez" al final
            if ($numeroActual === 0 && $nuevoTexto === $textoAviso) {
                $nuevoTexto = $textoAviso . ' - 1a vez';
            }

            // Actualizar el aviso
            $aviso->update([
                'texto_aviso' => $nuevoTexto,
                'fecha_aviso' => $nuevaFecha
            ]);

            // Eliminar avisos duplicados vencidos después de gestionar este aviso
            // Solo para avisos de tipo Producto u OfertaProducto
            if (in_array($aviso->avisoable_type, ['App\Models\Producto', 'App\Models\OfertaProducto'])) {
                $this->eliminarAvisosDuplicadosVencidos($aviso);
            }

            return response()->json([
                'success' => true,
                'message' => 'Aviso aplazado correctamente',
                'aviso' => $aviso->load('user')
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al aplazar el aviso: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtiene la oferta más barata de un producto para carga lazy
     */
    public function obtenerOfertaMasBarata(Request $request)
    {
        $request->validate([
            'producto_id' => 'required|integer|exists:productos,id'
        ]);

        try {
            // Consultar la tabla producto_oferta_mas_barata_por_producto para obtener el oferta_id
            $ofertaMasBarataTabla = ProductoOfertaMasBarataPorProducto::where('producto_id', $request->producto_id)->first();
            
            if (!$ofertaMasBarataTabla || !$ofertaMasBarataTabla->oferta_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró oferta más barata para este producto'
                ], 404);
            }
            
            // Obtener todos los datos de la oferta desde la tabla de ofertas
            $oferta = OfertaProducto::with('tienda')->find($ofertaMasBarataTabla->oferta_id);
            
            if (!$oferta) {
                return response()->json([
                    'success' => false,
                    'message' => 'La oferta no existe'
                ], 404);
            }

            // Obtener el producto para acceder a unidadDeMedida
            $producto = Producto::find($request->producto_id);

            return response()->json([
                'success' => true,
                'oferta' => [
                    'id' => $oferta->id,
                    'url' => $oferta->url,
                    'precio_total' => $oferta->precio_total,
                    'precio_unidad' => $oferta->precio_unidad,
                    'unidades' => $oferta->unidades,
                    'tienda' => [
                        'id' => $oferta->tienda->id ?? null,
                        'nombre' => $oferta->tienda->nombre ?? 'Tienda ID: ' . $oferta->tienda_id
                    ],
                    'unidad_de_medida' => $producto->unidadDeMedida ?? 'unidades'
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('Error al obtener oferta más barata: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener la oferta más barata'
            ], 500);
        }
    }

    /**
     * Elimina avisos vencidos duplicados basándose en las 3 primeras palabras del texto
     * Solo para avisos de tipo Producto u OfertaProducto
     * 
     * @param Aviso $avisoGestionado El aviso que se acaba de gestionar
     */
    private function eliminarAvisosDuplicadosVencidos(Aviso $avisoGestionado)
    {
        try {
            // Solo procesar avisos de tipo Producto u OfertaProducto
            if (!in_array($avisoGestionado->avisoable_type, ['App\Models\Producto', 'App\Models\OfertaProducto'])) {
                return;
            }

            // Obtener las 3 primeras palabras del texto del aviso gestionado
            $textoAviso = trim($avisoGestionado->texto_aviso);
            $palabras = preg_split('/\s+/', $textoAviso);
            
            // Si no hay al menos 3 palabras, no hacer nada
            if (count($palabras) < 3) {
                return;
            }

            // Obtener las 3 primeras palabras
            $primerasTresPalabras = array_slice($palabras, 0, 3);
            $prefijo = implode(' ', $primerasTresPalabras);
            
            // Escapar caracteres especiales para LIKE
            $prefijoEscapado = str_replace(['%', '_'], ['\%', '\_'], $prefijo);

            // Buscar avisos vencidos duplicados:
            // - Mismo tipo (Producto u OfertaProducto)
            // - Mismo avisoable_id
            // - Que empiecen con las mismas 3 palabras
            // - Que estén vencidos
            // - Mismo usuario (solo eliminar duplicados del mismo usuario)
            // - Excluyendo el aviso gestionado
            $avisosDuplicados = Aviso::where('avisoable_type', $avisoGestionado->avisoable_type)
                ->where('avisoable_id', $avisoGestionado->avisoable_id)
                ->where('texto_aviso', 'like', $prefijoEscapado . '%')
                ->where('user_id', $avisoGestionado->user_id)
                ->vencidos()
                ->where('id', '!=', $avisoGestionado->id)
                ->get();

            // Eliminar los avisos duplicados encontrados
            if ($avisosDuplicados->count() > 0) {
                $avisosDuplicados->each->delete();
                \Log::info('Eliminados ' . $avisosDuplicados->count() . ' avisos duplicados vencidos para ' . $avisoGestionado->avisoable_type . ' ID: ' . $avisoGestionado->avisoable_id);
            }

        } catch (\Exception $e) {
            \Log::error('Error al eliminar avisos duplicados vencidos: ' . $e->getMessage());
            // No lanzar excepción para no interrumpir el flujo principal
        }
    }

    /**
     * Guardar el estado de "mostrar todos los avisos" en la sesión
     * Solo disponible para el usuario ID 1
     */
    public function toggleMostrarTodos(Request $request)
    {
        // Solo permitir al usuario ID 1
        if (auth()->id() !== 1) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permisos para realizar esta acción'
            ], 403);
        }

        $request->validate([
            'mostrar_todos' => 'required|boolean'
        ]);

        // Guardar en la sesión
        session(['avisos_mostrar_todos' => $request->mostrar_todos]);

        return response()->json([
            'success' => true,
            'message' => $request->mostrar_todos ? 'Mostrando avisos de todos los usuarios' : 'Mostrando solo tus avisos',
            'mostrar_todos' => $request->mostrar_todos
        ]);
    }
}
