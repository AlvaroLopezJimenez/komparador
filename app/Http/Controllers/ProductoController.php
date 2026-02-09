<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;
use Carbon\Carbon;
use App\Models\HistoricoPrecioProducto;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use App\Models\Categoria;
use App\Models\ProductoOfertaMasBarataPorProducto;
use App\Services\SacarPrimeraOfertaDeUnProductoAplicadoDescuentosYChollos;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\RateLimiter;
use App\Models\Click;
use App\Models\OfertaProducto;


class ProductoController extends Controller
{
    public function index(Request $request)
    {
        $busqueda = $request->input('buscar');
        $mostrarFiltro = $request->input('mostrar', 'todos'); // 'todos', 'si', 'no'

        $query = Producto::with('categoria')
            ->withCount([
                'ofertas',
                'ofertas as ofertas_visibles_count' => fn($q) => $q->where('mostrar', 'si'),
                'ofertas as clicks_ultimos_30_dias' => fn($q) =>
                $q->join('clicks', 'clicks.oferta_id', '=', 'ofertas_producto.id')
                    ->where('clicks.created_at', '>=', now()->subDays(30))
            ]);

        if ($busqueda) {
            $query->where(function ($q) use ($busqueda) {
                $q->where('nombre', 'like', '%' . $busqueda . '%')
                    ->orWhere('marca', 'like', '%' . $busqueda . '%')
                    ->orWhere('modelo', 'like', '%' . $busqueda . '%')
                    ->orWhere('talla', 'like', '%' . $busqueda . '%');
            });
        }

        // Filtro por mostrar
        if ($mostrarFiltro === 'si') {
            $query->where('mostrar', 'si');
        } elseif ($mostrarFiltro === 'no') {
            $query->where('mostrar', 'no');
        }
        // Si es 'todos', no aplicamos filtro

        $productos = $query->latest()->paginate(20);

        return view('admin.productos.index', compact('productos', 'busqueda', 'mostrarFiltro'));
    }



    public function create()
    {
        $categoriasRaiz = Categoria::with(['subcategorias' => function ($query) {
            $query->orderBy('nombre');
        }])
        ->whereNull('parent_id')
        ->orderBy('nombre')
        ->get();

        // Cargar subcategorías de forma recursiva
        $this->cargarSubcategoriasRecursivamente($categoriasRaiz);

        return view('admin.productos.formulario', [
            'producto' => null,
            'categoriasRaiz' => $categoriasRaiz,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string',
            'slug' => 'required|string|unique:productos,slug',
            'marca' => 'required|string',
            'modelo' => 'required|string',
            'talla' => 'nullable|string',
            'unidadDeMedida' => 'required|in:total,unidad,kilos,litros,unidadMilesima,unidadUnica,800gramos,100ml',
            'precio' => 'nullable|numeric',
            'imagenes_grandes' => 'nullable',
            'imagenes_pequenas' => 'nullable',
            'caracteristicas' => 'nullable|string',
            'pros' => 'nullable|array',
            'contras' => 'nullable|array',
            'faq' => 'nullable|array',
            'keys_relacionados' => 'nullable|array',
            'id_categoria_productos_relacionados' => 'required|exists:categorias,id',
            'titulo' => 'required|string',
            'subtitulo' => 'nullable|string',
            'descripcion_corta' => 'nullable|string',
            'descripcion_larga' => 'nullable|string',
            'meta_titulo' => 'nullable|string',
            'meta_description' => 'nullable|string',
            'categoria_id' => 'required|exists:categorias,id',
            'categoria_id_especificaciones_internas' => 'nullable|exists:categorias,id',
            'categoria_especificaciones_internas_elegidas' => 'nullable|string',
            'obsoleto' => 'required|in:si,no',
            'mostrar' => 'required|in:si,no',
            'anotaciones_internas' => 'nullable|string',
            'aviso' => 'nullable|date',
        ]);

        // Procesar imágenes: convertir JSON strings a arrays si vienen como strings
        $imagenesGrandes = $validated['imagenes_grandes'] ?? [];
        $imagenesPequenas = $validated['imagenes_pequenas'] ?? [];
        
        // Si vienen como string JSON, decodificarlas
        if (is_string($imagenesGrandes)) {
            $imagenesGrandes = json_decode($imagenesGrandes, true) ?? [];
        }
        if (is_string($imagenesPequenas)) {
            $imagenesPequenas = json_decode($imagenesPequenas, true) ?? [];
        }
        
        // Asegurar que sean arrays
        $imagenesGrandes = is_array($imagenesGrandes) ? $imagenesGrandes : [];
        $imagenesPequenas = is_array($imagenesPequenas) ? $imagenesPequenas : [];

        $avisoFecha = null;

        if ($request->filled('eliminar_aviso')) {
            $avisoFecha = null;
        } elseif ($request->filled('aviso_cantidad') && $request->filled('aviso_unidad')) {
            $avisoFecha = now()->add($request->input('aviso_unidad'), (int)$request->input('aviso_cantidad'))->setTime(0, 1);
        }

        $caracteristicas = array_filter(array_map('trim', explode("\n", $validated['caracteristicas'] ?? '')));

        // Procesar especificaciones internas elegidas (incluye las del producto)
        $especificacionesElegidas = null;
        if ($request->has('categoria_especificaciones_internas_elegidas') && !empty($request->categoria_especificaciones_internas_elegidas)) {
            $especificacionesElegidas = json_decode($request->categoria_especificaciones_internas_elegidas, true);
        }
        
        // Si el checkbox "No añadir" está marcado, guardar null en ambos campos
        if ($request->has('no_anadir_especificaciones') && $request->boolean('no_anadir_especificaciones')) {
            $validated['categoria_id_especificaciones_internas'] = null;
            $especificacionesElegidas = null;
        }

        $producto = Producto::create([
            'nombre' => $validated['nombre'],
            'slug' => $validated['slug'],
            'marca' => $validated['marca'],
            'modelo' => $validated['modelo'],
            'talla' => $validated['talla'],
            'unidadDeMedida' => $validated['unidadDeMedida'],
            'precio' => $validated['precio'],
            'imagen_grande' => $imagenesGrandes,
            'imagen_pequena' => $imagenesPequenas,
            'titulo' => $validated['titulo'],
            'subtitulo' => $validated['subtitulo'],
            'descripcion_corta' => $validated['descripcion_corta'],
            'descripcion_larga' => $validated['descripcion_larga'],
            'caracteristicas' => $caracteristicas,
            'pros' => $validated['pros'] ?? [],
            'contras' => $validated['contras'] ?? [],
            'faq' => $validated['faq'] ?? [],
            'keys_relacionados' => $validated['keys_relacionados'] ?? [],
            'id_categoria_productos_relacionados' => $validated['id_categoria_productos_relacionados'] ?? null,
            'categoria_id' => $validated['categoria_id'],
            'categoria_id_especificaciones_internas' => $validated['categoria_id_especificaciones_internas'] ?? null,
            'categoria_especificaciones_internas_elegidas' => $especificacionesElegidas,
            'meta_titulo' => $validated['meta_titulo'],
            'meta_description' => $validated['meta_description'],
            'obsoleto' => $validated['obsoleto'],
            'mostrar' => $validated['mostrar'],
            'anotaciones_internas' => $validated['anotaciones_internas'] ?? null,
            'aviso' => $avisoFecha,
        ]);

        // Registrar actividad de usuario
        if (auth()->check()) {
            \App\Models\UserActivity::create([
                'user_id' => auth()->id(),
                'action_type' => \App\Models\UserActivity::ACTION_PRODUCTO_CREADO,
                'producto_id' => $producto->id,
            ]);
        }

        return redirect()->route('admin.productos.index')->with('success', 'Producto guardado correctamente.');
    }


    public function edit(Producto $producto)
    {
        $categoriasRaiz = Categoria::with(['subcategorias' => function ($query) {
            $query->orderBy('nombre');
        }])
        ->whereNull('parent_id')
        ->orderBy('nombre')
        ->get();

        // Cargar subcategorías de forma recursiva
        $this->cargarSubcategoriasRecursivamente($categoriasRaiz);

        // Obtener información de la categoría relacionada si existe
        $categoriaRelacionadosNombre = null;
        if ($producto->id_categoria_productos_relacionados) {
            $categoriaRelacionada = Categoria::find($producto->id_categoria_productos_relacionados);
            if ($categoriaRelacionada) {
                $categoriaRelacionadosNombre = $categoriaRelacionada->nombre;
            }
        }
        
        // Obtener información de la categoría de especificaciones internas si existe
        $categoriaEspecificacionesNombre = null;
        if ($producto->categoria_id_especificaciones_internas) {
            $categoriaEspecificaciones = Categoria::find($producto->categoria_id_especificaciones_internas);
            if ($categoriaEspecificaciones) {
                $categoriaEspecificacionesNombre = $categoriaEspecificaciones->nombre;
            }
        }
        
        // Debug: verificar que se está cargando correctamente
        \Log::info('Editando producto', [
            'producto_id' => $producto->id,
            'id_categoria_productos_relacionados' => $producto->id_categoria_productos_relacionados,
            'categoria_relacionados_nombre' => $categoriaRelacionadosNombre,
            'categoria_id_especificaciones_internas' => $producto->categoria_id_especificaciones_internas,
            'categoria_especificaciones_nombre' => $categoriaEspecificacionesNombre
        ]);

        return view('admin.productos.formulario', compact('producto', 'categoriasRaiz', 'categoriaRelacionadosNombre', 'categoriaEspecificacionesNombre'));
    }


    public function update(Request $request, Producto $producto)
    {
        $validated = $request->validate([
            'nombre' => 'required|string',
            'slug' => 'required|string|unique:productos,slug,' . $producto->id,
            'marca' => 'required|string',
            'modelo' => 'required|string',
            'talla' => 'nullable|string',
            'unidadDeMedida' => 'required|in:total,unidad,kilos,litros,unidadMilesima,unidadUnica,800gramos,100ml',
            'precio' => 'nullable|numeric',
            'imagenes_grandes' => 'nullable',
            'imagenes_pequenas' => 'nullable',
            'caracteristicas' => 'nullable|string',
            'pros' => 'nullable|array',
            'contras' => 'nullable|array',
            'faq' => 'nullable|array',
            'keys_relacionados' => 'nullable|array',
            'id_categoria_productos_relacionados' => 'required|exists:categorias,id',
            'titulo' => 'required|string',
            'subtitulo' => 'nullable|string',
            'descripcion_corta' => 'nullable|string',
            'descripcion_larga' => 'nullable|string',
            'meta_titulo' => 'nullable|string',
            'meta_description' => 'nullable|string',
            'categoria_id' => 'required|exists:categorias,id',
            'categoria_id_especificaciones_internas' => 'nullable|exists:categorias,id',
            'categoria_especificaciones_internas_elegidas' => 'nullable|string',
            'obsoleto' => 'required|in:si,no',
            'mostrar' => 'required|in:si,no',
            'anotaciones_internas' => 'nullable|string',
            'aviso' => 'nullable|date',
        ]);

        // Procesar imágenes: convertir JSON strings a arrays si vienen como strings
        $imagenesGrandes = $validated['imagenes_grandes'] ?? [];
        $imagenesPequenas = $validated['imagenes_pequenas'] ?? [];
        
        // Si vienen como string JSON, decodificarlas
        if (is_string($imagenesGrandes)) {
            $imagenesGrandes = json_decode($imagenesGrandes, true) ?? [];
        }
        if (is_string($imagenesPequenas)) {
            $imagenesPequenas = json_decode($imagenesPequenas, true) ?? [];
        }
        
        // Asegurar que sean arrays
        $imagenesGrandes = is_array($imagenesGrandes) ? $imagenesGrandes : [];
        $imagenesPequenas = is_array($imagenesPequenas) ? $imagenesPequenas : [];

        $avisoFecha = $producto->aviso; // valor anterior por defecto

        if ($request->filled('eliminar_aviso')) {
            $avisoFecha = null;
        } elseif ($request->filled('aviso_cantidad') && $request->filled('aviso_unidad')) {
            $avisoFecha = now()->add($request->input('aviso_unidad'), (int)$request->input('aviso_cantidad'))->setTime(0, 1);
        }


        $caracteristicas = array_filter(array_map('trim', explode("\n", $validated['caracteristicas'] ?? '')));

        // Procesar especificaciones internas elegidas
        $especificacionesElegidas = null;
        if ($request->has('categoria_especificaciones_internas_elegidas') && !empty($request->categoria_especificaciones_internas_elegidas)) {
            $especificacionesElegidas = json_decode($request->categoria_especificaciones_internas_elegidas, true);
            
            // Validar que si el formato es "imagen", todas las sublíneas marcadas como mostrar tienen imágenes
            if (is_array($especificacionesElegidas)) {
                foreach ($especificacionesElegidas as $lineaId => $sublineas) {
                    // Verificar si es un array de sublíneas y si tiene formato
                    if (is_array($sublineas) && isset($sublineas['_formato']) && $sublineas['_formato'] === 'imagen') {
                        // Obtener todas las sublíneas marcadas como mostrar
                        foreach ($sublineas as $item) {
                            if (is_array($item) && isset($item['id']) && isset($item['m']) && $item['m'] === 1) {
                                // Verificar si tiene imágenes
                                if (!isset($item['img']) || !is_array($item['img']) || count($item['img']) === 0) {
                                    return back()->withErrors([
                                        'categoria_especificaciones_internas_elegidas' => "La línea principal con ID {$lineaId} tiene formato 'Imagen' pero la sublínea con ID {$item['id']} marcada como 'Mostrar' no tiene imágenes."
                                    ])->withInput();
                                }
                            }
                        }
                    }
                }
            }
        }
        
        // Si el checkbox "No añadir" está marcado, guardar null en ambos campos
        if ($request->has('no_anadir_especificaciones') && $request->boolean('no_anadir_especificaciones')) {
            $validated['categoria_id_especificaciones_internas'] = null;
            $especificacionesElegidas = null;
        }

        // Debug: verificar datos antes de guardar
        \Log::info('Actualizando producto', [
            'producto_id' => $producto->id,
            'id_categoria_productos_relacionados' => $validated['id_categoria_productos_relacionados'] ?? null,
            'request_data' => $request->all()
        ]);

        $producto->update([
            'nombre' => $validated['nombre'],
            'slug' => $validated['slug'],
            'marca' => $validated['marca'],
            'modelo' => $validated['modelo'],
            'talla' => $validated['talla'],
            'unidadDeMedida' => $validated['unidadDeMedida'],
            'precio' => $validated['precio'],
            'imagen_grande' => $imagenesGrandes,
            'imagen_pequena' => $imagenesPequenas,
            'titulo' => $validated['titulo'],
            'subtitulo' => $validated['subtitulo'],
            'descripcion_corta' => $validated['descripcion_corta'],
            'descripcion_larga' => $validated['descripcion_larga'],
            'caracteristicas' => $caracteristicas,
            'pros' => $validated['pros'] ?? [],
            'contras' => $validated['contras'] ?? [],
            'faq' => $validated['faq'] ?? [],
            'keys_relacionados' => $validated['keys_relacionados'] ?? [],
            'id_categoria_productos_relacionados' => $validated['id_categoria_productos_relacionados'] ?? null,
            'categoria_id' => $validated['categoria_id'],
            'categoria_id_especificaciones_internas' => $validated['categoria_id_especificaciones_internas'] ?? null,
            'categoria_especificaciones_internas_elegidas' => $especificacionesElegidas,
            'meta_titulo' => $validated['meta_titulo'],
            'meta_description' => $validated['meta_description'],
            'obsoleto' => $validated['obsoleto'],
            'mostrar' => $validated['mostrar'],
            'anotaciones_internas' => $validated['anotaciones_internas'] ?? null,
            'aviso' => $avisoFecha,
        ]);

        // Refrescar el modelo para asegurar que se carguen los datos actualizados
        $producto->refresh();
        
        // Registrar actividad de usuario
        if (auth()->check()) {
            \App\Models\UserActivity::create([
                'user_id' => auth()->id(),
                'action_type' => \App\Models\UserActivity::ACTION_PRODUCTO_MODIFICADO,
                'producto_id' => $producto->id,
            ]);
        }
        
        // Debug: verificar que se guardó correctamente
        \Log::info('Producto actualizado', [
            'producto_id' => $producto->id,
            'id_categoria_productos_relacionados' => $producto->id_categoria_productos_relacionados
        ]);

        return redirect()->route('admin.productos.index')->with('success', 'Producto actualizado correctamente.');
    }


    // MÉTODO DESTROY COMENTADO POR SEGURIDAD - No se permite eliminar productos desde el panel
    /*
    public function destroy(Producto $producto)
    {
        $producto->delete();
        return redirect()->route('admin.productos.index')->with('success', 'Producto eliminado correctamente.');
    }
    */

    public function datosHistorico(Request $request, Producto $producto)
    {
        $dias = (int) $request->query('dias', 90);
        $desde = Carbon::today()->subDays($dias - 1); // incluye hoy

        // Obtiene todos los registros desde la fecha indicada
        $historico = HistoricoPrecioProducto::where('producto_id', $producto->id)
            ->where('fecha', '>=', $desde)
            ->orderBy('fecha')
            ->get()
            ->keyBy('fecha');

        // Obtener el rango de precios de las 5 ofertas más baratas del producto
        $servicioOfertas = new SacarPrimeraOfertaDeUnProductoAplicadoDescuentosYChollos();
        $ofertasProducto = $servicioOfertas->obtenerTodas($producto);
        $primeras5Ofertas = $ofertasProducto->take(5);
        
        $rangoPrecio5Ofertas = [
            'min' => null,
            'max' => null,
        ];
        
        if ($primeras5Ofertas->count() > 0) {
            $precioPrimera = $primeras5Ofertas->first()->precio_unidad ?? null;
            $precioQuinta = $primeras5Ofertas->last()->precio_unidad ?? null;
            
            $rangoPrecio5Ofertas = [
                'min' => $precioPrimera !== null ? (float) $precioPrimera : null,
                'max' => $precioQuinta !== null ? (float) $precioQuinta : null,
            ];
        }

        $labels = [];
        $valores = [];

        for ($i = 0; $i < $dias; $i++) {
            $fecha = Carbon::today()->subDays($dias - 1 - $i)->toDateString();
            $labels[] = Carbon::parse($fecha)->format('d/m');
            $valores[] = isset($historico[$fecha]) ? (float) $historico[$fecha]->precio_minimo : null;
        }

        return response()->json([
            'labels' => $labels,
            'valores' => $valores,
            'rango_5_ofertas' => $rangoPrecio5Ofertas,
        ]);
    }
    
    /**
     * Obtener información adicional para las estadísticas de un producto
     * - Rango de precio_unidad de las 5 ofertas más baratas del producto
     * - Máximo y mínimo del historial de precios del producto en el rango de días
     */
    public function estadisticasInfo(Producto $producto, Request $request)
    {
        try {
            $dias = (int) $request->query('dias', 90);
            $desde = Carbon::today()->subDays($dias - 1);
            
            // Obtener todas las ofertas del producto usando el servicio
            $servicioOfertas = new SacarPrimeraOfertaDeUnProductoAplicadoDescuentosYChollos();
            $ofertasProducto = $servicioOfertas->obtenerTodas($producto);
            
            // Obtener las 5 primeras ofertas (las más baratas)
            $primeras5Ofertas = $ofertasProducto->take(5);
            
            $rangoPrecioUnidad = [
                'min' => null,
                'max' => null,
            ];
            
            if ($primeras5Ofertas->count() > 0) {
                $precioPrimera = $primeras5Ofertas->first()->precio_unidad ?? null;
                $precioQuinta = $primeras5Ofertas->last()->precio_unidad ?? null;
                
                $rangoPrecioUnidad = [
                    'min' => $precioPrimera !== null ? (float) $precioPrimera : null,
                    'max' => $precioQuinta !== null ? (float) $precioQuinta : null,
                ];
            }
            
            // Obtener máximo y mínimo del historial de precios del producto
            $historicoProducto = HistoricoPrecioProducto::where('producto_id', $producto->id)
                ->where('fecha', '>=', $desde)
                ->get();
            
            $preciosHistoricos = $historicoProducto->pluck('precio_minimo')->filter()->toArray();
            
            $rangoHistorico = [
                'min' => !empty($preciosHistoricos) ? (float) min($preciosHistoricos) : null,
                'max' => !empty($preciosHistoricos) ? (float) max($preciosHistoricos) : null,
            ];
            
            return response()->json([
                'rango_precio_unidad' => $rangoPrecioUnidad,
                'rango_historico' => $rangoHistorico,
            ]);
        } catch (\Exception $e) {
            \Log::error('Error en estadisticasInfo producto: ' . $e->getMessage(), [
                'producto_id' => $producto->id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'error' => 'Error al obtener información: ' . $e->getMessage(),
                'rango_precio_unidad' => ['min' => null, 'max' => null],
                'rango_historico' => ['min' => null, 'max' => null],
            ], 500);
        }
    }
    
    /**
     * Ocultar todas las ofertas de un producto y añadir anotación
     */
    public function ocultarOfertasPrecioElevado(Producto $producto)
    {
        $anotacionActual = $producto->anotaciones_internas ?? '';
        
        // Añadir salto de línea solo si ya hay contenido
        if (!empty(trim($anotacionActual))) {
            $nuevaAnotacion = $anotacionActual . "\nPRECIO MUY ELEVADO CONSTANTE EN EL TIEMPO";
        } else {
            $nuevaAnotacion = "PRECIO MUY ELEVADO CONSTANTE EN EL TIEMPO";
        }
        
        // Ocultar todas las ofertas del producto
        $ofertasActualizadas = OfertaProducto::where('producto_id', $producto->id)
            ->update(['mostrar' => 'no']);
        
        // Actualizar anotaciones del producto
        $producto->update([
            'anotaciones_internas' => $nuevaAnotacion,
        ]);
        
        return response()->json([
            'success' => true,
            'message' => "Se ocultaron {$ofertasActualizadas} oferta(s) y se añadió la anotación correctamente"
        ]);
    }


    public function estadisticas(Request $request, Producto $producto)
    {
        // Manejar filtros rápidos
        $filtroRapido = $request->input('filtro_rapido', '90dias');
        $hoy = now();
        
        // Calcular fechas según filtro rápido
        switch($filtroRapido) {
            case 'hoy':
                $desde = $hasta = $hoy->toDateString();
                break;
            case 'ayer':
                $desde = $hasta = $hoy->copy()->subDay()->toDateString();
                break;
            case '7dias':
                $desde = $hoy->copy()->subDays(7)->toDateString();
                $hasta = $hoy->toDateString();
                break;
            case '30dias':
                $desde = $hoy->copy()->subDays(30)->toDateString();
                $hasta = $hoy->toDateString();
                break;
            case '90dias':
                $desde = $hoy->copy()->subDays(90)->toDateString();
                $hasta = $hoy->toDateString();
                break;
            case '180dias':
                $desde = $hoy->copy()->subDays(180)->toDateString();
                $hasta = $hoy->toDateString();
                break;
            case '1año':
                $desde = $hoy->copy()->subYear()->toDateString();
                $hasta = $hoy->toDateString();
                break;
            case 'siempre':
                $desde = null;
                $hasta = null;
                break;
            default:
                // Si hay fechas manuales, usarlas
                $desde = $request->input('desde', $hoy->copy()->subDays(90)->toDateString());
                $hasta = $request->input('hasta', $hoy->toDateString());
                break;
        }
        
        // Si hay fechas manuales en el request, priorizarlas
        if ($request->has('desde') && $request->has('hasta') && !$request->has('filtro_rapido')) {
            $desde = $request->input('desde');
            $hasta = $request->input('hasta');
        }
        
        $campana = $request->input('campana');

        // Obtener palabras clave únicas desde los clicks del producto
        $palabrasClave = DB::table('clicks')
            ->join('ofertas_producto', 'clicks.oferta_id', '=', 'ofertas_producto.id')
            ->where('ofertas_producto.producto_id', $producto->id)
            ->whereNotNull('clicks.campaña')
            ->where('clicks.campaña', '!=', '')
            ->distinct()
            ->pluck('clicks.campaña')
            ->map(function ($codigo) {
                return (object) [
                    'codigo' => $codigo,
                    'palabra' => $codigo, // Usamos el código como palabra si no hay otra fuente
                    'activa' => 'si'
                ];
            });

        $query = DB::table('clicks')
            ->join('ofertas_producto', 'clicks.oferta_id', '=', 'ofertas_producto.id')
            ->join('tiendas', 'ofertas_producto.tienda_id', '=', 'tiendas.id')
            ->where('ofertas_producto.producto_id', $producto->id);
        
        if ($desde && $hasta) {
            $query->whereBetween('clicks.created_at', [$desde . ' 00:00:00', $hasta . ' 23:59:59']);
        }

        if ($campana) {
            $query->where('clicks.campaña', $campana);
        }

        $agrupado = $query
            ->selectRaw('tiendas.id, tiendas.nombre, tiendas.url_imagen, tiendas.opiniones, tiendas.puntuacion, COUNT(*) as total_clicks, MIN(clicks.precio_unidad) as min, MAX(clicks.precio_unidad) as max')
            ->groupBy('tiendas.id', 'tiendas.nombre', 'tiendas.url_imagen', 'tiendas.opiniones', 'tiendas.puntuacion')
            ->orderByDesc('total_clicks')
            ->get()
            ->keyBy('id');

        $visibilidad = DB::table('ofertas_producto')
            ->selectRaw('tienda_id, COUNT(*) as total, SUM(CASE WHEN mostrar = "no" THEN 1 ELSE 0 END) as ocultas')
            ->where('producto_id', $producto->id)
            ->groupBy('tienda_id')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->tienda_id => $item->ocultas >= $item->total];
            });

        $clicsPaginados = Click::with(['oferta.tienda'])
            ->whereHas('oferta', function ($q) use ($producto) {
                $q->where('producto_id', $producto->id);
            })
            ->when($campana, fn($q) => $q->where('campaña', $campana))
            ->when($desde && $hasta, fn($q) => $q->whereBetween('created_at', [$desde . ' 00:00:00', $hasta . ' 23:59:59']))
            ->orderByDesc('created_at')
            ->paginate(20);
        
        // Calcular días para los gráficos de precios
        $dias = 90; // Por defecto
        if ($desde && $hasta) {
            $fechaDesde = \Carbon\Carbon::parse($desde);
            $fechaHasta = \Carbon\Carbon::parse($hasta);
            $dias = $fechaDesde->diffInDays($fechaHasta) + 1;
        }

        return view('admin.productos.estadisticas', compact(
            'producto',
            'desde',
            'hasta',
            'campana',
            'palabrasClave',
            'agrupado',
            'visibilidad',
            'clicsPaginados',
            'filtroRapido',
            'dias',
        ));
    }

    // Guardar el precio del dia en el historial de todos los productos
    public function indexEjecucionesHistorico(Request $request)
    {
        $busqueda = $request->input('buscar');

        $query = \App\Models\EjecucionGlobal::where('nombre', 'ejecuciones_historico_precios_productos');

        if ($busqueda) {
            $query->where(function($q) use ($busqueda) {
                $q->whereDate('inicio', 'like', "%$busqueda%")
                  ->orWhereDate('fin', 'like', "%$busqueda%");
            });
        }

        $ejecuciones = $query->orderByDesc('inicio')->paginate(15)->withQueryString();
        $totalEjecuciones = \App\Models\EjecucionGlobal::where('nombre', 'ejecuciones_historico_precios_productos')->count();

        return view('admin.productos.listadoEjecucionesGuardadoPrecios', compact('ejecuciones', 'busqueda', 'totalEjecuciones'));
    }

    //Eliminar x cantidad de registros de actualizaciones de precios de productos
    public function eliminarAntiguas(Request $request)
    {
        $cantidad = $request->input('cantidad');

        \App\Models\EjecucionGlobal::where('nombre', 'ejecuciones_historico_precios_productos')
            ->orderBy('inicio')
            ->limit($cantidad)
            ->delete();

        return redirect()->back()->with('success', "$cantidad ejecuciones eliminadas.");
    }

    //Eliminar solo un registro de actualizaciones de precios de productos
    public function eliminar($id)
    {
        $ejecucion = \App\Models\EjecucionGlobal::where('nombre', 'ejecuciones_historico_precios_productos')
            ->findOrFail($id);
        $ejecucion->delete();

        return redirect()->back()->with('success', 'Ejecución eliminada.');
    }

    //MODIFICAR HISTORIAL DE PRECIO DE PRODUCTOS
    public function historialMes(Request $request, Producto $producto)
    {
        $mes = (int) $request->query('mes');
        $anio = (int) $request->query('anio');

        $desde = Carbon::createFromDate($anio, $mes, 1);
        $hasta = $desde->copy()->endOfMonth();

        $registros = HistoricoPrecioProducto::where('producto_id', $producto->id)
            ->whereBetween('fecha', [$desde->toDateString(), $hasta->toDateString()])
            ->get();

        $resultado = [];
        foreach ($registros as $registro) {
            $fechaFormateada = Carbon::parse($registro['fecha'])->format('Y-m-d');
            $resultado[$fechaFormateada] = (float) $registro['precio_minimo'];
        }

        return response()->json($resultado);
    }

    public function historialGuardar(Request $request, Producto $producto)
    {
        $cambios = $request->input('cambios', []);

        foreach ($cambios as $fecha => $precio) {
            if ($precio === null || $precio === '') continue;

            HistoricoPrecioProducto::updateOrCreate(
                ['producto_id' => $producto->id, 'fecha' => $fecha],
                ['precio_minimo' => $precio]
            );
        }

        return response()->json(['success' => true]);
    }

    //BUSCAR PRODUCTOS RELACIONADOS CON LAS PALABRAS CLAVES QUE TENGO ESCRITAS EN EL FORMULARIO DE PRODUCTO
    public function buscarRelacionados(Request $request)
    {
        $keywords = $request->input('keywords', []);
        $productoId = $request->input('producto_id');

        $productos = Producto::query()
            ->where(function ($q) use ($keywords) {
                foreach ($keywords as $palabra) {
                    $q->orWhere('marca', 'like', "%$palabra%")
                        ->orWhere('modelo', 'like', "%$palabra%")
                        ->orWhere('talla', 'like', "%$palabra%");
                }
            })
            ->when($productoId, fn($q) => $q->where('id', '!=', $productoId))
            ->count();

        return response()->json(['total' => $productos]);
    }

    //GRAFICA ESTADISITICAS PARA VER LOS CLICKS Y PODER MARCAR SI 30,90,180 O UN AÑO
    public function datosClicks(Request $request, Producto $producto)
    {
        $dias = (int) $request->query('dias', 90);
        $desde = now()->subDays($dias - 1)->startOfDay();

        $clicksPorDia = DB::table('clicks')
            ->join('ofertas_producto', 'clicks.oferta_id', '=', 'ofertas_producto.id')
            ->where('ofertas_producto.producto_id', $producto->id)
            ->where('clicks.created_at', '>=', $desde)
            ->selectRaw('DATE(clicks.created_at) as fecha, COUNT(*) as total')
            ->groupBy('fecha')
            ->orderBy('fecha')
            ->get()
            ->keyBy('fecha');

        $labels = [];
        $valores = [];

        for ($i = 0; $i < $dias; $i++) {
            $fecha = now()->subDays($dias - 1 - $i)->toDateString();
            $labels[] = \Carbon\Carbon::parse($fecha)->format('d/m');
            $valores[] = isset($clicksPorDia[$fecha]) ? $clicksPorDia[$fecha]->total : 0;
        }

        return response()->json([
            'labels' => $labels,
            'valores' => $valores,
        ]);
    }

    // Actualizar clicks de todos los productos
    public function actualizarClicks(Request $request = null)
    {
        // Crear ejecución en la tabla global
        $ejecucion = \App\Models\EjecucionGlobal::create([
            'inicio' => now(),
            'nombre' => 'ejecuciones_clicks_producto',
            'log' => [],
        ]);

        $productos = Producto::all();
        $actualizados = 0;
        $errores = 0;
        $log = [];

        foreach ($productos as $producto) {
            try {
                // Contar clicks del último mes para este producto
                $clicksUltimoMes = \App\Models\Click::whereHas('oferta', function($query) use ($producto) {
                    $query->where('producto_id', $producto->id);
                })
                ->where('created_at', '>=', now()->subMonth())
                ->count();

                // Actualizar el campo clicks del producto
                $producto->update(['clicks' => $clicksUltimoMes]);

                $actualizados++;
                $log[] = [
                    'producto_id' => $producto->id,
                    'nombre' => $producto->nombre,
                    'clicks' => $clicksUltimoMes,
                    'status' => 'actualizado'
                ];
            } catch (\Throwable $e) {
                $errores++;
                $log[] = [
                    'producto_id' => $producto->id,
                    'nombre' => $producto->nombre,
                    'error' => $e->getMessage(),
                    'status' => 'error'
                ];
            }
        }

        $ejecucion->update([
            'fin' => now(),
            'total' => count($productos),
            'total_guardado' => $actualizados,
            'total_errores' => $errores,
            'log' => $log,
        ]);

        return response()->json([
            'status' => 'ok',
            'actualizados' => $actualizados,
            'errores' => $errores,
            'ejecucion_id' => $ejecucion->id
        ]);
    }

    // Vista para ejecutar la actualización de clicks
    public function ejecucionActualizarClicks()
    {
        return view('admin.productos.ejecucionActualizarClicks');
    }

    // Listar ejecuciones de actualizaciones de clicks de productos
    public function indexEjecucionesClicks()
    {
        $ejecuciones = \App\Models\EjecucionGlobal::where('nombre', 'ejecuciones_clicks_producto')
            ->orderBy('created_at', 'desc')
            ->paginate(10);
        return view('admin.productos.ejecucionesClicks', compact('ejecuciones'));
    }

    // Ver detalles de una ejecución específica
    public function obtenerJsonEjecucionClicks($id)
    {
        $ejecucion = \App\Models\EjecucionGlobal::where('nombre', 'ejecuciones_clicks_producto')
            ->findOrFail($id);
        return response()->json($ejecucion);
    }

    // Eliminar una ejecución específica de actualizaciones de clicks
    public function eliminarEjecucionClicks($id)
    {
        $ejecucion = \App\Models\EjecucionGlobal::where('nombre', 'ejecuciones_clicks_producto')
            ->findOrFail($id);
        $ejecucion->delete();

        return redirect()->back()->with('success', 'Ejecución eliminada correctamente.');
    }

/**
 * Genera contenido optimizado usando OpenAI devolviendo SOLO lo que pidas desde el front.
 * NO construye prompt en servidor: usa el prompt recibido en la request.
 */
public function generarContenido(Request $request)
{
    \Log::info('Método generarContenido llamado', [
        'user' => auth()->user()->id ?? 'no autenticado',
        'request_data' => $request->except(['_token'])
    ]);

    try {
        // Validación de entrada
        $request->validate([
            'nombre' => 'required|string',
            'marca' => 'required|string',
            'modelo' => 'required|string',
            'talla' => 'required|string',
            'prompt' => 'required|string',
            'categoria_id' => 'nullable|exists:categorias,id'
        ]);

        $nombre = $request->input('nombre');
        $marca  = $request->input('marca');
        $modelo = $request->input('modelo');
        $talla  = $request->input('talla');
        $prompt = $request->input('prompt');
        $categoriaId = $request->input('categoria_id');
        
        // Obtener información adicional de la categoría si existe
        $infoAdicionalCategoria = '';
        if ($categoriaId) {
            $categoria = Categoria::find($categoriaId);
            if ($categoria && !empty($categoria->info_adicional_chatgpt)) {
                $infoAdicionalCategoria = "\n\n" . $categoria->info_adicional_chatgpt;
            }
        }
        
        // Añadir la información adicional al prompt si existe
        if (!empty($infoAdicionalCategoria)) {
            $prompt .= $infoAdicionalCategoria;
        }

        $apiKey = config('services.openai.api_key');

        // Fallback de pruebas si no hay API key
        // if (!$apiKey) {
//             $data = [
//                 'titulo' => 'Comparador de ' . $nombre,
//                 'subtitulo' => 'Descripción corta del producto ' . $marca . ' ' . $modelo,
//                 'descripcion_corta' => 'Descripción más detallada del producto ' . $nombre . ' de la marca ' . $marca,
//                 'descripcion_larga' => '<h3>Características principales</h3><p>Descripción larga de ejemplo.</p>',
//                 'caracteristicas' => "Característica 1\nCaracterística 2\nCaracterística 3\nCaracterística 4\nCaracterística 5\nCaracterística 6",
//                 'meta_titulo' => 'Meta título para ' . $nombre,
//                 'meta_descripcion' => 'Meta descripción para ' . $nombre,
//                 'pros' => ['Pro 1', 'Pro 2', 'Pro 3', 'Pro 4', 'Pro 5'],
//                 'contras' => ['Contra 1', 'Contra 2', 'Contra 3'],
//                 'preguntas_frecuentes' => [
//                     ['pregunta' => 'Pregunta 1', 'respuesta' => 'Respuesta 1'],
//                     ['pregunta' => 'Pregunta 2', 'respuesta' => 'Respuesta 2'],
//                     ['pregunta' => 'Pregunta 3', 'respuesta' => 'Respuesta 3'],
//                 ],
//             ];
//             return response()->json($data);
//         }

        // Cliente OpenAI
        try {
            $client = \OpenAI::client($apiKey);
        } catch (\Exception $e) {
            \Log::error('Error al obtener cliente OpenAI: ' . $e->getMessage());
            return response()->json(['error' => 'Error inicializando cliente OpenAI'], 500);
        }

        // Llamada: se usa EXACTAMENTE el prompt que envía el front
        $response = $client->chat()->create([
            'model' => 'gpt-4o-mini',
            'messages' => [
                // Mantengo una instrucción mínima para obligar JSON, sin tocar tu prompt
                [
                    'role' => 'system',
                    'content' => 'Responde SIEMPRE únicamente con un JSON válido (objeto). No añadas texto fuera del JSON.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ],
            ],
            'temperature' => 0.2,
            'max_tokens' => 2000,
            'response_format' => ['type' => 'json_object'], // si el SDK no lo soporta, se ignora
        ]);

        $content = $response->choices[0]->message->content ?? '';
        \Log::info('Respuesta OpenAI (raw):', ['content' => $content]);

        // Parseo robusto del JSON
        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
            // Fallback 1: bloque ```json ... ```
            if (preg_match('/```json\s*(\{.*?\})\s*```/s', $content, $m)) {
                $data = json_decode($m[1], true);
            }
        }

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
            // Fallback 2: primer objeto { ... } con regex recursiva
            if (preg_match('/\{(?:[^{}]|(?R))*\}/s', $content, $m)) {
                $data = json_decode($m[0], true);
            }
        }

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
            \Log::error('JSON inválido en respuesta IA', [
                'json_error' => json_last_error_msg(),
                'content' => $content,
            ]);
            return response()->json(['error' => 'JSON inválido en la respuesta de IA'], 500);
        }

        // Normalizaciones (tu esquema)
        if (isset($data['caracteristicas']) && is_string($data['caracteristicas'])) {
            $caracteristicas = array_map('trim', explode(',', $data['caracteristicas']));
            $data['caracteristicas'] = implode("\n", $caracteristicas);
        }
        if (isset($data['pros']) && is_string($data['pros'])) {
            $data['pros'] = array_map('trim', explode(',', $data['pros']));
        }
        if (isset($data['contras']) && is_string($data['contras'])) {
            $data['contras'] = array_map('trim', explode(',', $data['contras']));
        }

        return response()->json($data);

    } catch (\Illuminate\Validation\ValidationException $e) {
        \Log::error('Error de validación en generarContenido: ' . json_encode($e->errors()));
        $flat = [];
        foreach ($e->errors() as $k => $msgs) {
            $flat[] = $k . ': ' . implode(' | ', $msgs);
        }
        return response()->json(['error' => 'Datos de entrada inválidos: ' . implode(', ', $flat)], 422);
    } catch (\Exception $e) {
        \Log::error('Error al generar contenido con ChatGPT: ' . $e->getMessage());
        return response()->json(['error' => 'Error al generar contenido: ' . $e->getMessage()], 500);
    }
}


    /**
     * Carga subcategorías de forma recursiva
     */
    private function cargarSubcategoriasRecursivamente($categorias)
    {
        foreach ($categorias as $categoria) {
            $categoria->load(['subcategorias' => function ($query) {
                $query->orderBy('nombre');
            }]);
            
            if ($categoria->subcategorias->count() > 0) {
                $this->cargarSubcategoriasRecursivamente($categoria->subcategorias);
            }
        }
    }

    /**
     * Calcular el precio real por unidad considerando descuentos
     * DEPRECADO: Usar DescuentosController en su lugar
     */
    private function calcularPrecioRealPorUnidad($oferta)
    {
        // Usar el controlador centralizado de descuentos
        $descuentosController = new \App\Http\Controllers\DescuentosController();
        $ofertaConDescuento = $descuentosController->aplicarDescuento($oferta);
        
        return $ofertaConDescuento->precio_unidad;
    }

    /**
     * Mostrar vista de ejecución en tiempo real para guardar precio más bajo
     */
    public function ejecucionPrecioBajo()
    {
        return view('admin.productos.ejecucionPrecioBajo');
    }

    /**
     * Procesar productos para guardar precio más bajo
     */
    public function procesarPrecioBajo(Request $request)
    {
        try {
            $accion = $request->input('accion');

            if ($accion === 'iniciar') {
                // Obtener total de productos
                $totalProductos = Producto::count();
                
                // Guardar en sesión para el procesamiento
                session(['precio_bajo_total_productos' => $totalProductos]);
                session(['precio_bajo_productos_procesados' => 0]);
                session(['precio_bajo_precios_actualizados' => 0]);

                return response()->json([
                    'success' => true,
                    'total_productos' => $totalProductos
                ]);
            }

            if ($accion === 'procesar_producto') {
                $productosProcesados = session('precio_bajo_productos_procesados', 0);
                $totalProductos = session('precio_bajo_total_productos', 0);
                $preciosActualizados = session('precio_bajo_precios_actualizados', 0);

                // Obtener el siguiente producto a procesar
                $producto = Producto::skip($productosProcesados)->first();

                if (!$producto) {
                    // Proceso completado
                    return response()->json([
                        'success' => true,
                        'finalizado' => true,
                        'total_productos' => $totalProductos,
                        'precios_actualizados' => $preciosActualizados
                    ]);
                }

                // Verificar primero si hay ofertas disponibles para este producto
                $tieneOfertas = $producto->ofertas()
                    ->where('mostrar', 'si')
                    ->whereHas('tienda', function($query) {
                        $query->where('mostrar_tienda', 'si');
                    })
                    ->exists();
                
                $precioActualizado = false;
                $precioAnterior = $producto->precio;
                $precioNuevo = null;

                // Si no hay ofertas disponibles, poner precio a 0
                if (!$tieneOfertas) {
                    if ($producto->precio != 0) {
                        $producto->precio = 0;
                        $producto->save();
                        $precioActualizado = true;
                        $preciosActualizados++;
                    }
                } else {
                    // Usar el servicio para obtener la oferta más barata con descuentos y chollos aplicados
                    $servicioOfertas = new SacarPrimeraOfertaDeUnProductoAplicadoDescuentosYChollos();
                    $mejorOferta = $servicioOfertas->obtener($producto);

                    // Si el servicio devuelve una oferta válida con precio_unidad
                    if ($mejorOferta && $mejorOferta->precio_unidad !== null && $mejorOferta->precio_unidad > 0) {
                        // Obtener la oferta original de la base de datos para guardar los datos originales
                        $ofertaOriginal = OfertaProducto::find($mejorOferta->id);
                        
                        if ($ofertaOriginal) {
                            // Actualizar o crear el registro en la tabla producto_oferta_mas_barata_por_producto
                            ProductoOfertaMasBarataPorProducto::updateOrCreate(
                                ['producto_id' => $producto->id],
                                [
                                    'oferta_id' => $ofertaOriginal->id,
                                    'tienda_id' => $ofertaOriginal->tienda_id,
                                    'precio_total' => $mejorOferta->precio_total, // Precio con descuentos aplicados
                                    'precio_unidad' => $mejorOferta->precio_unidad, // Precio con descuentos aplicados
                                    'unidades' => $ofertaOriginal->unidades,
                                    'url' => $ofertaOriginal->url,
                                ]
                            );
                        }
                        
                        // El precio_unidad ya viene con descuentos y chollos aplicados del servicio
                        $precioRealMasBajo = $mejorOferta->precio_unidad;
                        
                        // Si el producto tiene unidadDeMedida = unidadMilesima, redondear a 3 decimales
                        if ($producto->unidadDeMedida === 'unidadMilesima') {
                            $precioNuevo = round($precioRealMasBajo, 3);
                        } else {
                            $precioNuevo = $precioRealMasBajo;
                        }
                        
                        // Validar que el precio nuevo es válido
                        if ($precioNuevo !== null && $precioNuevo > 0) {
                            // Comparar si el precio es diferente
                            if ($producto->precio != $precioNuevo) {
                                // Actualizar el precio del producto
                                $producto->precio = $precioNuevo;
                                $producto->save();
                                $precioActualizado = true;
                                $preciosActualizados++;
                                
                                \Log::info("Precio actualizado para producto {$producto->nombre}: {$precioAnterior}€ -> {$precioNuevo}€");
                            }
                        } else {
                            // Si el precio calculado es inválido pero hay ofertas, mantener precio actual
                            // (no poner a 0 porque sabemos que hay ofertas)
                        }
                    } else {
                        // Si el servicio no devuelve oferta válida pero sabemos que hay ofertas,
                        // mantener el precio actual (no poner a 0)
                    }
                }

                // Incrementar contador de productos procesados
                $productosProcesados++;
                session(['precio_bajo_productos_procesados' => $productosProcesados]);
                session(['precio_bajo_precios_actualizados' => $preciosActualizados]);

                return response()->json([
                    'success' => true,
                    'producto_procesado' => true,
                    'nombre_producto' => $producto->nombre,
                    'precio_actualizado' => $precioActualizado,
                    'precio_anterior' => $precioAnterior,
                    'precio_nuevo' => $precioNuevo,
                    'precio_actual' => $producto->precio,
                    'finalizado' => $productosProcesados >= $totalProductos
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Acción no válida'
            ]);

        } catch (\Exception $e) {
            \Log::error('Error en procesarPrecioBajo: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Ejecutar proceso de guardar precio más bajo en segundo plano
     */
    public function ejecutarPrecioBajoSegundoPlano(Request $request)
    {
        try {
            // Verificar token de seguridad
            if ($request->get('token') !== env('TOKEN_ACTUALIZAR_PRECIOS')) {
                abort(403, 'Token inválido');
            }

            $productos = Producto::all();
            $totalProductos = $productos->count();
            $preciosActualizados = 0;
            $servicioOfertas = new SacarPrimeraOfertaDeUnProductoAplicadoDescuentosYChollos();

            \Log::info("Iniciando proceso de guardar precio más bajo para {$totalProductos} productos");

            foreach ($productos as $producto) {
                try {
                    // Verificar primero si hay ofertas disponibles para este producto
                    $tieneOfertas = $producto->ofertas()
                        ->where('mostrar', 'si')
                        ->whereHas('tienda', function($query) {
                            $query->where('mostrar_tienda', 'si');
                        })
                        ->exists();
                    
                    // Si no hay ofertas disponibles, poner precio a 0
                    if (!$tieneOfertas) {
                        if ($producto->precio != 0) {
                            $producto->precio = 0;
                            $producto->save();
                            $preciosActualizados++;
                        }
                        continue;
                    }
                    
                    // Usar el servicio para obtener la oferta más barata con descuentos y chollos aplicados
                    $mejorOferta = $servicioOfertas->obtener($producto);

                    // Si el servicio devuelve una oferta válida con precio_unidad
                    if ($mejorOferta && $mejorOferta->precio_unidad !== null && $mejorOferta->precio_unidad > 0) {
                        // Obtener la oferta original de la base de datos para guardar los datos originales
                        $ofertaOriginal = OfertaProducto::find($mejorOferta->id);
                        
                        if ($ofertaOriginal) {
                            // Actualizar o crear el registro en la tabla producto_oferta_mas_barata_por_producto
                            ProductoOfertaMasBarataPorProducto::updateOrCreate(
                                ['producto_id' => $producto->id],
                                [
                                    'oferta_id' => $ofertaOriginal->id,
                                    'tienda_id' => $ofertaOriginal->tienda_id,
                                    'precio_total' => $mejorOferta->precio_total, // Precio con descuentos aplicados
                                    'precio_unidad' => $mejorOferta->precio_unidad, // Precio con descuentos aplicados
                                    'unidades' => $ofertaOriginal->unidades,
                                    'url' => $ofertaOriginal->url,
                                ]
                            );
                        }
                        
                        // El precio_unidad ya viene con descuentos y chollos aplicados del servicio
                        $precioRealMasBajo = $mejorOferta->precio_unidad;
                        
                        // Si el producto tiene unidadDeMedida = unidadMilesima, redondear a 3 decimales
                        if ($producto->unidadDeMedida === 'unidadMilesima') {
                            $precioNuevo = round($precioRealMasBajo, 3);
                        } else {
                            $precioNuevo = $precioRealMasBajo;
                        }
                        
                        // Validar que el precio nuevo es válido
                        if ($precioNuevo !== null && $precioNuevo > 0) {
                            // Comparar si el precio es diferente
                            if ($producto->precio != $precioNuevo) {
                                // Actualizar el precio del producto
                                $producto->precio = $precioNuevo;
                                $producto->save();
                                $preciosActualizados++;
                                
                                \Log::info("Precio actualizado para producto {$producto->nombre}: {$producto->precio}€ -> {$precioNuevo}€");
                            }
                        } else {
                            // Si el precio calculado es inválido pero hay ofertas, mantener precio actual
                            // (no poner a 0 porque sabemos que hay ofertas)
                        }
                    } else {
                        // Si el servicio no devuelve oferta válida pero sabemos que hay ofertas,
                        // mantener el precio actual (no poner a 0)
                    }
                } catch (\Exception $e) {
                    // Continuar con el siguiente producto si hay error
                    \Log::warning("Error al actualizar precio del producto {$producto->id}: " . $e->getMessage());
                }
            }

            \Log::info("Proceso completado: {$preciosActualizados} precios actualizados de {$totalProductos} productos");

            return response()->json([
                'success' => true,
                'message' => "Proceso completado: {$preciosActualizados} precios actualizados de {$totalProductos} productos",
                'total_productos' => $totalProductos,
                'precios_actualizados' => $preciosActualizados
            ]);

        } catch (\Exception $e) {
            \Log::error('Error en ejecutarPrecioBajoSegundoPlano: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Guardar histórico de precios de productos (para cron jobs)
     */
    public function guardarHistoricoPrecios()
    {
        // Crear ejecución en la tabla global
        $ejecucion = \App\Models\EjecucionGlobal::create([
            'inicio' => now(),
            'nombre' => 'ejecuciones_historico_precios_productos',
            'log' => [],
        ]);

        $productos = Producto::all();
        $guardados = 0;
        $errores = 0;
        $log = [];

        foreach ($productos as $producto) {
            try {
                // Obtener el precio actual del producto
                $precioActual = $producto->precio ?? 0;
                
                // Si el precio es 0, buscar el precio del día anterior en el historial
                if ($precioActual == 0) {
                    $historicoAnterior = HistoricoPrecioProducto::where('producto_id', $producto->id)
                        ->where('fecha', '<', now()->toDateString())
                        ->orderBy('fecha', 'desc')
                        ->first();
                    
                    if ($historicoAnterior) {
                        $precioActual = $historicoAnterior->precio_minimo ?? $historicoAnterior->precio_maximo ?? 0;
                    }
                }
                
                // Buscar la oferta con precio más bajo para este producto considerando descuentos
                $ofertas = \App\Models\OfertaProducto::where('producto_id', $producto->id)
                    ->where('mostrar', 'si')
                    ->get(['id', 'precio_unidad', 'precio_total', 'unidades', 'descuentos']);

                $ofertaMasBarata = null;
                $precioRealMasBajo = null;

                foreach ($ofertas as $oferta) {
                    $precioReal = $this->calcularPrecioRealPorUnidad($oferta);
                    if ($precioRealMasBajo === null || $precioReal < $precioRealMasBajo) {
                        $precioRealMasBajo = $precioReal;
                        $ofertaMasBarata = $oferta;
                    }
                }
                
                // Si hay oferta, usar el precio de la oferta, si no, usar el precio actual (que puede ser del historial)
                $precioMinimo = $ofertaMasBarata ? $precioRealMasBajo : $precioActual;
                
                // Guardar en el histórico
                HistoricoPrecioProducto::updateOrCreate(
                    [
                        'producto_id' => $producto->id,
                        'fecha' => now()->toDateString()
                    ],
                    [
                        'precio_minimo' => $precioMinimo,
                        'precio_maximo' => $precioActual
                    ]
                );
                
                $guardados++;
                $log[] = [
                    'producto_id' => $producto->id,
                    'nombre' => $producto->nombre,
                    'precio_minimo' => $precioMinimo,
                    'precio_maximo' => $precioActual,
                    'status' => 'guardado'
                ];
            } catch (\Throwable $e) {
                $errores++;
                $log[] = [
                    'producto_id' => $producto->id,
                    'nombre' => $producto->nombre,
                    'error' => $e->getMessage(),
                    'status' => 'error'
                ];
            }
        }

        $ejecucion->update([
            'fin' => now(),
            'total' => count($productos),
            'total_guardado' => $guardados,
            'total_errores' => $errores,
            'log' => $log,
        ]);

        return response()->json([
            'status' => 'ok',
            'guardados' => $guardados,
            'errores' => $errores,
            'ejecucion_id' => $ejecucion->id
        ]);
    }

    /**
     * Calcular precios hot (para cron jobs)
     */
    public function calcularPreciosHot()
    {
        // Crear ejecución en la tabla global
        $ejecucion = \App\Models\EjecucionGlobal::create([
            'inicio' => now(),
            'nombre' => 'precios_hot',
            'log' => [],
        ]);

        $categorias = \App\Models\Categoria::all();
        $totalInserciones = 0;
        $errores = 0;
        $log = [];

        foreach ($categorias as $categoria) {
            try {
                $productosHot = $this->obtenerProductosHotPorCategoria($categoria, 20);
                
                if (!empty($productosHot)) {
                    // Guardar o actualizar en la tabla precios_hot
                    \App\Models\PrecioHot::updateOrCreate(
                        ['nombre' => $categoria->nombre],
                        ['datos' => $productosHot]
                    );
                    $totalInserciones++;
                    $log[] = [
                        'categoria' => $categoria->nombre,
                        'productos_hot' => count($productosHot),
                        'status' => 'guardado'
                    ];
                } else {
                    $log[] = [
                        'categoria' => $categoria->nombre,
                        'productos_hot' => 0,
                        'status' => 'sin_productos'
                    ];
                }
            } catch (\Throwable $e) {
                $errores++;
                $log[] = [
                    'categoria' => $categoria->nombre,
                    'error' => $e->getMessage(),
                    'status' => 'error'
                ];
            }
        }

        // Procesar categoría global "Precios Hot"
        try {
            $productosHotGlobal = $this->obtenerProductosHotGlobal(60);
            
            if (!empty($productosHotGlobal)) {
                \App\Models\PrecioHot::updateOrCreate(
                    ['nombre' => 'Precios Hot'],
                    ['datos' => $productosHotGlobal]
                );
                $totalInserciones++;
                $log[] = [
                    'categoria' => 'Precios Hot',
                    'productos_hot' => count($productosHotGlobal),
                    'status' => 'guardado'
                ];
            } else {
                $log[] = [
                    'categoria' => 'Precios Hot',
                    'productos_hot' => 0,
                    'status' => 'sin_productos'
                ];
            }
        } catch (\Throwable $e) {
            $errores++;
            $log[] = [
                'categoria' => 'Precios Hot',
                'error' => $e->getMessage(),
                'status' => 'error'
            ];
        }

        $ejecucion->update([
            'fin' => now(),
            'total' => count($categorias) + 1, // +1 por la categoría global
            'total_guardado' => $totalInserciones,
            'total_errores' => $errores,
            'log' => $log,
        ]);

        return response()->json([
            'status' => 'ok',
            'inserciones' => $totalInserciones,
            'errores' => $errores,
            'ejecucion_id' => $ejecucion->id
        ]);
    }

    /**
     * Obtener productos hot por categoría
     */
    private function obtenerProductosHotPorCategoria($categoria, $limite = 20)
    {
        // Obtener todas las categorías hijas (incluyendo la actual)
        $categoriaIds = $this->obtenerCategoriaIdsIncluyendoHijas($categoria->id);
        
        // Obtener productos de estas categorías con sus relaciones
        $productos = Producto::with('categoria')
            ->whereIn('categoria_id', $categoriaIds)
            ->where('mostrar', 'si')
            ->get();
        
        return $this->calcularProductosHot($productos, $limite);
    }

    /**
     * Obtener productos hot globales
     */
    private function obtenerProductosHotGlobal($limite = 60)
    {
        // Obtener todos los productos con sus relaciones
        $productos = Producto::with('categoria')
            ->where('mostrar', 'si')
            ->get();
        
        return $this->calcularProductosHot($productos, $limite);
    }

    /**
     * Calcular productos hot
     */
    private function calcularProductosHot($productos, $limite)
    {
        $productosHot = [];
        $haceUnMes = Carbon::now()->subMonth();

        foreach ($productos as $producto) {
            // Calcular precio medio del último mes
            $precioMedio = HistoricoPrecioProducto::where('producto_id', $producto->id)
                ->where('fecha', '>=', $haceUnMes)
                ->avg('precio_minimo');

            if (!$precioMedio) {
                continue; // Saltar productos sin historial de precios
            }

            // Buscar la oferta con precio más bajo considerando descuentos
            $ofertas = \App\Models\OfertaProducto::with('tienda')
                ->where('producto_id', $producto->id)
                ->where('mostrar', 'si')
                ->whereHas('tienda', function($query) {
                    $query->where('mostrar_tienda', 'si');
                })
                ->get(['id', 'precio_unidad', 'precio_total', 'unidades', 'descuentos']);

            $mejorOferta = null;
            $precioRealMasBajo = null;

            foreach ($ofertas as $oferta) {
                $precioReal = $this->calcularPrecioRealPorUnidad($oferta);
                if ($precioRealMasBajo === null || $precioReal < $precioRealMasBajo) {
                    $precioRealMasBajo = $precioReal;
                    $mejorOferta = $oferta;
                }
            }

            if (!$mejorOferta) {
                continue; // Saltar productos sin ofertas
            }

            // Calcular porcentaje de diferencia usando el precio real
            $diferencia = (($precioMedio - $precioRealMasBajo) / $precioMedio) * 100;

            // Solo incluir si el precio de la oferta es menor que la media
            if ($diferencia > 0) {
                $productosHot[] = [
                    'producto_id' => $producto->id,
                    'oferta_id' => $mejorOferta->id,
                    'tienda_id' => $mejorOferta->tienda_id,
                    'precio_oferta' => $mejorOferta->precio_unidad,
                    'porcentaje_diferencia' => round($diferencia, 2),
                    'url_oferta' => $mejorOferta->url,
                    'url_producto' => $this->generarUrlProducto($producto),
                    'producto_nombre' => $producto->nombre,
                    'tienda_nombre' => $mejorOferta->tienda ? $mejorOferta->tienda->nombre : 'Tienda desconocida'
                ];
            }
        }

        // Ordenar por porcentaje de diferencia (mayor a menor) y tomar los primeros
        usort($productosHot, function($a, $b) {
            return $b['porcentaje_diferencia'] <=> $a['porcentaje_diferencia'];
        });

        return array_slice($productosHot, 0, $limite);
    }

    /**
     * Obtener IDs de categorías incluyendo hijas
     */
    private function obtenerCategoriaIdsIncluyendoHijas($categoriaId)
    {
        $categoriaIds = [$categoriaId];
        
        // Obtener categorías hijas directas
        $hijas = \App\Models\Categoria::where('parent_id', $categoriaId)->get();
        
        foreach ($hijas as $hija) {
            $categoriaIds = array_merge($categoriaIds, $this->obtenerCategoriaIdsIncluyendoHijas($hija->id));
        }
        
        return $categoriaIds;
    }

    /**
     * Generar URL de producto
     */
    private function generarUrlProducto($producto)
    {
        // Cargar la relación de categoría si no está cargada
        if (!$producto->relationLoaded('categoria')) {
            $producto->load('categoria');
        }
        
        $categoria = $producto->categoria;
        if (!$categoria) {
            return '/productos/' . $producto->slug;
        }

        $urlParts = [$categoria->slug];
        
        // Construir la URL completa con la jerarquía de categorías
        $categoriaActual = $categoria;
        while ($categoriaActual->parent_id) {
            $categoriaPadre = \App\Models\Categoria::find($categoriaActual->parent_id);
            if ($categoriaPadre) {
                array_unshift($urlParts, $categoriaPadre->slug);
                $categoriaActual = $categoriaPadre;
            } else {
                break;
            }
        }
        
        return '/' . implode('/', $urlParts) . '/' . $producto->slug;
    }

    /**
     * Verificar si un slug ya existe en la tabla productos
     */
    public function verificarSlugExistente(Request $request)
    {
        $slug = $request->input('slug');
        $productoId = $request->input('producto_id'); // Para excluir el producto actual en caso de edición
        
        if (empty($slug)) {
            return response()->json([
                'tipo' => 'vacio',
                'mensaje' => 'Slug vacío'
            ]);
        }
        
        $query = Producto::where('slug', $slug);
        
        // Si estamos editando un producto, excluirlo de la búsqueda
        if ($productoId) {
            $query->where('id', '!=', $productoId);
        }
        
        $productoExistente = $query->first();
        
        if (!$productoExistente) {
            return response()->json([
                'tipo' => 'disponible',
                'mensaje' => 'Este slug está disponible'
            ]);
        } else {
            return response()->json([
                'tipo' => 'duplicado',
                'mensaje' => 'Este slug ya existe en otro producto',
                'producto_existente' => [
                    'id' => $productoExistente->id,
                    'nombre' => $productoExistente->nombre,
                    'marca' => $productoExistente->marca,
                    'modelo' => $productoExistente->modelo,
                    'talla' => $productoExistente->talla
                ]
            ]);
        }
    }

    /**
     * Buscar categorías para el formulario de productos
     */
    public function buscarCategorias(Request $request)
    {
        $query = $request->input('q', '');
        
        // Si no hay query, devolver todas las categorías (para auto-selección)
        if (empty($query)) {
            $categorias = Categoria::orderBy('nombre')
                ->get(['id', 'nombre']);
            return response()->json($categorias);
        }
        
        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $categorias = Categoria::where('nombre', 'like', '%' . $query . '%')
            ->orderBy('nombre')
            ->limit(10)
            ->get(['id', 'nombre']);

        return response()->json($categorias);
    }

    public function obtenerEspecificacionesInternas($categoriaId)
    {
        $categoria = Categoria::find($categoriaId);
        
        if (!$categoria) {
            return response()->json(['error' => 'Categoría no encontrada'], 404);
        }
        
        return response()->json([
            'especificaciones_internas' => $categoria->especificaciones_internas
        ]);
    }

    /**
     * Obtiene todas las palabras clave de productos relacionados de una categoría y sus subcategorías
     */
    public function obtenerPalabrasClaveRelacionadas(Request $request, $categoriaId)
    {
        $productoId = $request->input('producto_id');
        
        $categoria = Categoria::find($categoriaId);
        
        if (!$categoria) {
            return response()->json(['error' => 'Categoría no encontrada'], 404);
        }
        
        // Obtener todas las categorías hijas recursivamente
        $categoriasHijas = $categoria->obtenerTodasLasHijas();
        $categoriasIds = collect([$categoria->id])->merge($categoriasHijas->pluck('id'))->unique()->toArray();
        
        // Buscar TODOS los productos de estas categorías (incluyendo el producto actual)
        $productos = Producto::whereIn('categoria_id', $categoriasIds)
            ->whereNotNull('keys_relacionados')
            ->get();
        
        // Obtener palabras clave del producto actual si existe
        $productoActual = null;
        $palabrasClaveProductoActual = [];
        if ($productoId) {
            $productoActual = Producto::find($productoId);
            if ($productoActual && $productoActual->keys_relacionados) {
                $palabrasClaveProductoActual = array_map('trim', array_filter($productoActual->keys_relacionados));
            }
        }
        
        // Recopilar todas las palabras clave y contar productos (incluyendo el producto actual)
        $palabrasClaveContador = [];
        
        foreach ($productos as $producto) {
            if (!$producto->keys_relacionados || !is_array($producto->keys_relacionados)) {
                continue;
            }
            
            $palabras = array_map('trim', array_filter($producto->keys_relacionados));
            
            foreach ($palabras as $palabra) {
                if (empty($palabra)) {
                    continue;
                }
                
                if (!isset($palabrasClaveContador[$palabra])) {
                    $palabrasClaveContador[$palabra] = [
                        'palabra' => $palabra,
                        'count' => 0,
                        'tiene_producto_actual' => false
                    ];
                }
                
                $palabrasClaveContador[$palabra]['count']++;
            }
        }
        
        // Asegurar que todas las palabras clave del producto actual estén incluidas
        // (por si alguna solo existe en el producto actual)
        foreach ($palabrasClaveProductoActual as $palabra) {
            if (empty($palabra)) {
                continue;
            }
            
            if (!isset($palabrasClaveContador[$palabra])) {
                $palabrasClaveContador[$palabra] = [
                    'palabra' => $palabra,
                    'count' => 0,
                    'tiene_producto_actual' => true
                ];
            }
        }
        
        // Marcar cuáles tiene el producto actual
        foreach ($palabrasClaveContador as $palabra => &$data) {
            $data['tiene_producto_actual'] = in_array($palabra, $palabrasClaveProductoActual);
        }
        
        // Ordenar por cantidad de productos (descendente) y luego alfabéticamente
        usort($palabrasClaveContador, function($a, $b) {
            // Primero por cantidad de productos (descendente)
            if ($b['count'] !== $a['count']) {
                return $b['count'] - $a['count'];
            }
            // Si tienen la misma cantidad, ordenar alfabéticamente (case-insensitive)
            return strcasecmp($a['palabra'], $b['palabra']);
        });
        
        return response()->json([
            'success' => true,
            'palabras_clave' => array_values($palabrasClaveContador)
        ]);
    }

    /**
     * Obtiene todos los productos que tienen una palabra clave específica en una categoría
     */
    public function obtenerProductosPorPalabraClave(Request $request, $categoriaId, $palabraClave)
    {
        $productoId = $request->input('producto_id');
        
        $categoria = Categoria::find($categoriaId);
        
        if (!$categoria) {
            return response()->json(['error' => 'Categoría no encontrada'], 404);
        }
        
        // Obtener todas las categorías hijas recursivamente
        $categoriasHijas = $categoria->obtenerTodasLasHijas();
        $categoriasIds = collect([$categoria->id])->merge($categoriasHijas->pluck('id'))->unique()->toArray();
        
        // Buscar todos los productos de estas categorías que tengan la palabra clave
        $productos = Producto::whereIn('categoria_id', $categoriasIds)
            ->whereNotNull('keys_relacionados')
            ->get()
            ->filter(function($producto) use ($palabraClave) {
                if (!$producto->keys_relacionados || !is_array($producto->keys_relacionados)) {
                    return false;
                }
                $palabras = array_map('trim', array_filter($producto->keys_relacionados));
                return in_array(trim($palabraClave), $palabras);
            });
        
        // Preparar datos de los productos
        $productosData = $productos->map(function($producto) {
            // Cargar categoría si no está cargada
            if (!$producto->relationLoaded('categoria')) {
                $producto->load('categoria');
            }
            
            // Construir URL de categorías usando el método del modelo
            $urlProducto = '/';
            if ($producto->categoria) {
                $urlProducto = $producto->categoria->construirUrlCategorias($producto->slug);
            } else {
                $urlProducto = '/productos/' . $producto->slug;
            }
            
            // URL de edición
            $urlEditar = route('admin.productos.edit', $producto);
            
            return [
                'id' => $producto->id,
                'nombre' => $producto->nombre,
                'slug' => $producto->slug,
                'url_producto' => $urlProducto,
                'url_editar' => $urlEditar
            ];
        })->values();
        
        return response()->json([
            'success' => true,
            'productos' => $productosData
        ]);
    }

    public function obtenerProducto($id)
    {
        $producto = Producto::find($id);
        
        if (!$producto) {
            return response()->json(['error' => 'Producto no encontrado'], 404);
        }
        
        return response()->json([
            'id' => $producto->id,
            'nombre' => $producto->nombre,
            'unidadDeMedida' => $producto->unidadDeMedida,
            // Imágenes del producto (para "usarImagenesProducto" en especificaciones internas, etc.)
            'imagen_grande' => $producto->imagen_grande ?? [],
            'imagen_pequena' => $producto->imagen_pequena ?? [],
            'categoria_id_especificaciones_internas' => $producto->categoria_id_especificaciones_internas,
            'categoria_especificaciones_internas_elegidas' => $producto->categoria_especificaciones_internas_elegidas,
            'grupos_de_ofertas' => $producto->grupos_de_ofertas
        ]);
    }

    /**
     * Actualizar grupos_de_ofertas de un producto
     */
    public function actualizarGruposOfertas(Request $request, $id)
    {
        $producto = Producto::find($id);
        
        if (!$producto) {
            return response()->json(['success' => false, 'message' => 'Producto no encontrado'], 404);
        }
        
        $validated = $request->validate([
            'grupos_de_ofertas' => 'nullable|array'
        ]);
        
        $producto->grupos_de_ofertas = $validated['grupos_de_ofertas'] ?? null;
        $producto->save();
        
        return response()->json([
            'success' => true,
            'message' => 'Grupos actualizados correctamente'
        ]);
    }

    /**
     * Obtener todas las categorías hijas de una categoría de forma recursiva
     */
    private function obtenerCategoriasHijasRecursivamente($categoriaId)
    {
        $categoriaIds = [$categoriaId];
        
        // Obtener categorías hijas directas
        $hijas = Categoria::where('parent_id', $categoriaId)->get();
        
        foreach ($hijas as $hija) {
            // Llamada recursiva para obtener las hijas de esta categoría
            $categoriaIds = array_merge($categoriaIds, $this->obtenerCategoriasHijasRecursivamente($hija->id));
        }
        
        return $categoriaIds;
    }

    /**
     * Obtener precios históricos de un producto según el rango de tiempo
     * 
     * Compatible con ambos sistemas:
     * - Sistema antiguo: requiere token MD5 en query param
     * - Sistema nuevo: usa token JWT en header X-Auth-Token (manejado por middleware)
     */
    public function obtenerPreciosHistoricos(Request $request, $productoId)
    {
        // Validación del período
        $request->validate([
            'periodo' => 'required|in:3m,6m,9m,12m,1y',
        ]);

        // Verificar token en header (sistema nuevo con HMAC)
        $tokenHeader = $request->header('X-Auth-Token');
        
        if (!$tokenHeader) {
            return response()->json(['error' => 'Token requerido'], 401);
        }
        
        // Validar token usando el servicio
        $tokenService = app(\App\Services\PreciosHistoricosTokenService::class);
        if (!$tokenService->validarToken($tokenHeader, $productoId)) {
            return response()->json(['error' => 'Token inválido o expirado'], 403);
        }
        
        // Rate limiting por IP (el middleware anti-scraping también aplica rate limiting)
        $key = 'precios_historicos_' . $request->ip();
        $maxAttempts = 30; // Aumentado porque ahora es más seguro
        $decayMinutes = 1;
        
        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            return response()->json(['error' => 'Demasiadas solicitudes'], 429);
        }
        RateLimiter::hit($key, $decayMinutes * 60);

        // Verificar que el producto existe
        $producto = Producto::find($productoId);
        if (!$producto) {
            return response()->json(['error' => 'Producto no encontrado'], 404);
        }

        // Calcular fechas según el período
        $periodos = [
            '3m' => 90,
            '6m' => 180,
            '9m' => 270,
            '12m' => 365,
            '1y' => 365
        ];
        
        $dias = $periodos[$request->periodo];
        $desde = \Carbon\Carbon::today()->subDays($dias - 1);

        // Obtener datos históricos
        $historico = \App\Models\HistoricoPrecioProducto::where('producto_id', $productoId)
            ->where('fecha', '>=', $desde)
            ->orderBy('fecha')
            ->get()
            ->mapWithKeys(fn($item) => [
                \Carbon\Carbon::parse($item->fecha)->toDateString() => (float) $item->precio_minimo
            ]);

        // Generar array de precios
        $precios = [];
        $datosConPrecio = 0;
        for ($i = 0; $i < $dias; $i++) {
            $fechaObj = \Carbon\Carbon::today()->subDays($dias - 1 - $i);
            $fechaYMD = $fechaObj->toDateString();
            $fechaDM = $fechaObj->format('d/m');
            $precio = isset($historico[$fechaYMD]) ? (float) $historico[$fechaYMD] : 0;
            
            if ($precio > 0) {
                $datosConPrecio++;
            }

            $precios[] = [
                'fecha' => $fechaDM,
                'precio' => $precio,
            ];
        }

        // Calcular densidad de datos (porcentaje de días con datos)
        $densidadDatos = $dias > 0 ? ($datosConPrecio / $dias) * 100 : 0;

        return response()->json([
            'precios' => $precios,
            'periodo' => $request->periodo,
            'dias' => $dias,
            'datosConPrecio' => $datosConPrecio,
            'densidadDatos' => round($densidadDatos, 1)
        ]);
    }

    /**
     * Actualizar la oferta más barata de cada producto en la tabla producto_oferta_mas_barata_por_producto
     * Este método se ejecuta desde un cron job cada 10 minutos
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function actualizarOfertaMasBarataPorProducto(Request $request)
    {
        try {
            // Verificar token de seguridad
            if ($request->get('token') !== env('TOKEN_ACTUALIZAR_PRECIOS')) {
                abort(403, 'Token inválido');
            }

            $servicioOfertas = new SacarPrimeraOfertaDeUnProductoAplicadoDescuentosYChollos();
            $productos = Producto::all();
            $totalProductos = $productos->count();
            $actualizados = 0;
            $errores = 0;
            $sinOfertas = 0;

            \Log::info("Iniciando actualización de ofertas más baratas para {$totalProductos} productos");

            foreach ($productos as $producto) {
                try {
                    // Obtener la oferta más barata usando el servicio
                    $ofertaMasBarata = $servicioOfertas->obtener($producto);

                    if (!$ofertaMasBarata) {
                        // Si no hay oferta, eliminar el registro si existe
                        ProductoOfertaMasBarataPorProducto::where('producto_id', $producto->id)->delete();
                        $sinOfertas++;
                        continue;
                    }

                    // Obtener la oferta original de la base de datos para guardar los datos originales
                    $ofertaOriginal = \App\Models\OfertaProducto::find($ofertaMasBarata->id);

                    if (!$ofertaOriginal) {
                        $errores++;
                        \Log::warning("No se encontró la oferta original con ID {$ofertaMasBarata->id} para el producto {$producto->id}");
                        continue;
                    }

                    // Guardar o actualizar en la tabla
                    // IMPORTANTE: Usar precio_total y precio_unidad de $ofertaMasBarata (con descuentos aplicados)
                    // en lugar de $ofertaOriginal (sin descuentos)
                    ProductoOfertaMasBarataPorProducto::updateOrCreate(
                        ['producto_id' => $producto->id],
                        [
                            'oferta_id' => $ofertaOriginal->id,
                            'tienda_id' => $ofertaOriginal->tienda_id,
                            'precio_total' => $ofertaMasBarata->precio_total, // Precio con descuentos aplicados
                            'precio_unidad' => $ofertaMasBarata->precio_unidad, // Precio con descuentos aplicados
                            'unidades' => $ofertaOriginal->unidades,
                            'url' => $ofertaOriginal->url,
                        ]
                    );

                    $actualizados++;

                } catch (\Exception $e) {
                    $errores++;
                    \Log::error("Error al actualizar oferta más barata para producto {$producto->id}: " . $e->getMessage());
                }
            }

            \Log::info("Proceso completado: {$actualizados} actualizados, {$sinOfertas} sin ofertas, {$errores} errores de {$totalProductos} productos");

            return response()->json([
                'success' => true,
                'message' => "Proceso completado: {$actualizados} actualizados, {$sinOfertas} sin ofertas, {$errores} errores",
                'total_productos' => $totalProductos,
                'actualizados' => $actualizados,
                'sin_ofertas' => $sinOfertas,
                'errores' => $errores
            ]);

        } catch (\Exception $e) {
            \Log::error('Error en actualizarOfertaMasBarataPorProducto: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mostrar vista de ejecución en tiempo real para actualizar oferta más barata
     */
    public function ejecucionOfertaMasBarata()
    {
        return view('admin.productos.ejecucionOfertaMasBarata');
    }

    /**
     * Procesar productos para actualizar oferta más barata
     */
    public function procesarOfertaMasBarata(Request $request)
    {
        try {
            $accion = $request->input('accion');

            if ($accion === 'iniciar') {
                // Obtener total de productos
                $totalProductos = Producto::count();
                
                // Guardar en sesión para el procesamiento
                session(['oferta_mas_barata_total_productos' => $totalProductos]);
                session(['oferta_mas_barata_productos_procesados' => 0]);
                session(['oferta_mas_barata_actualizados' => 0]);
                session(['oferta_mas_barata_sin_ofertas' => 0]);
                session(['oferta_mas_barata_errores' => 0]);

                return response()->json([
                    'success' => true,
                    'total_productos' => $totalProductos
                ]);
            }

            if ($accion === 'procesar_producto') {
                $productosProcesados = session('oferta_mas_barata_productos_procesados', 0);
                $totalProductos = session('oferta_mas_barata_total_productos', 0);
                $actualizados = session('oferta_mas_barata_actualizados', 0);
                $sinOfertas = session('oferta_mas_barata_sin_ofertas', 0);
                $errores = session('oferta_mas_barata_errores', 0);

                // Obtener el siguiente producto a procesar
                $producto = Producto::skip($productosProcesados)->first();

                if (!$producto) {
                    // Proceso completado
                    return response()->json([
                        'success' => true,
                        'finalizado' => true,
                        'total_productos' => $totalProductos,
                        'actualizados' => $actualizados,
                        'sin_ofertas' => $sinOfertas,
                        'errores' => $errores,
                        'productos_procesados' => $productosProcesados
                    ]);
                }

                $ofertaMasBarata = null;
                $actualizado = false;
                
                try {
                    // Usar el servicio para obtener la oferta más barata
                    $servicioOfertas = new SacarPrimeraOfertaDeUnProductoAplicadoDescuentosYChollos();
                    $ofertaMasBarata = $servicioOfertas->obtener($producto);

                    if ($ofertaMasBarata) {
                        // Actualizar o crear el registro en la tabla
                        ProductoOfertaMasBarataPorProducto::updateOrCreate(
                            ['producto_id' => $producto->id],
                            [
                                'oferta_id' => $ofertaMasBarata->id,
                                'tienda_id' => $ofertaMasBarata->tienda_id,
                                'precio_total' => $ofertaMasBarata->precio_total,
                                'precio_unidad' => $ofertaMasBarata->precio_unidad,
                                'unidades' => $ofertaMasBarata->unidades,
                                'url' => $ofertaMasBarata->url,
                            ]
                        );
                        $actualizados++;
                        $actualizado = true;
                    } else {
                        $sinOfertas++;
                    }
                } catch (\Exception $e) {
                    $errores++;
                    \Log::error("Error al actualizar oferta más barata para producto {$producto->id}: " . $e->getMessage());
                }

                // Incrementar contador de productos procesados
                $productosProcesados++;
                session(['oferta_mas_barata_productos_procesados' => $productosProcesados]);
                session(['oferta_mas_barata_actualizados' => $actualizados]);
                session(['oferta_mas_barata_sin_ofertas' => $sinOfertas]);
                session(['oferta_mas_barata_errores' => $errores]);

                $finalizado = $productosProcesados >= $totalProductos;
                
                $response = [
                    'success' => true,
                    'producto_procesado' => true,
                    'nombre_producto' => $producto->nombre,
                    'actualizado' => $actualizado,
                    'finalizado' => $finalizado
                ];
                
                if ($finalizado) {
                    $response['total_productos'] = $totalProductos;
                    $response['actualizados'] = $actualizados;
                    $response['sin_ofertas'] = $sinOfertas;
                    $response['errores'] = $errores;
                    $response['productos_procesados'] = $productosProcesados;
                }
                
                return response()->json($response);
            }

            return response()->json([
                'success' => false,
                'message' => 'Acción no válida'
            ]);

        } catch (\Exception $e) {
            \Log::error('Error en procesarOfertaMasBarata: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Ejecutar proceso de actualizar oferta más barata en segundo plano
     */
    public function ejecutarOfertaMasBarataSegundoPlano(Request $request)
    {
        try {
            // Verificar token de seguridad
            if ($request->get('token') !== env('TOKEN_ACTUALIZAR_PRECIOS')) {
                abort(403, 'Token inválido');
            }

            $productos = Producto::all();
            $totalProductos = $productos->count();
            $actualizados = 0;
            $sinOfertas = 0;
            $errores = 0;

            \Log::info("Iniciando proceso de actualizar oferta más barata para {$totalProductos} productos");

            // Usar el servicio para obtener ofertas más baratas
            $servicioOfertas = new SacarPrimeraOfertaDeUnProductoAplicadoDescuentosYChollos();

            foreach ($productos as $producto) {
                try {
                    // Obtener la oferta más barata usando el servicio
                    $ofertaMasBarata = $servicioOfertas->obtener($producto);

                    if ($ofertaMasBarata) {
                        // Actualizar o crear el registro en la tabla
                        ProductoOfertaMasBarataPorProducto::updateOrCreate(
                            ['producto_id' => $producto->id],
                            [
                                'oferta_id' => $ofertaMasBarata->id,
                                'tienda_id' => $ofertaMasBarata->tienda_id,
                                'precio_total' => $ofertaMasBarata->precio_total,
                                'precio_unidad' => $ofertaMasBarata->precio_unidad,
                                'unidades' => $ofertaMasBarata->unidades,
                                'url' => $ofertaMasBarata->url,
                            ]
                        );
                        $actualizados++;
                    } else {
                        $sinOfertas++;
                    }
                } catch (\Exception $e) {
                    $errores++;
                    \Log::error("Error al actualizar oferta más barata para producto {$producto->id}: " . $e->getMessage());
                }
            }

            \Log::info("Proceso completado: {$actualizados} actualizados, {$sinOfertas} sin ofertas, {$errores} errores de {$totalProductos} productos");

            return response()->json([
                'success' => true,
                'message' => "Proceso completado: {$actualizados} actualizados, {$sinOfertas} sin ofertas, {$errores} errores",
                'total_productos' => $totalProductos,
                'actualizados' => $actualizados,
                'sin_ofertas' => $sinOfertas,
                'errores' => $errores
            ]);

        } catch (\Exception $e) {
            \Log::error('Error en ejecutarOfertaMasBarataSegundoPlano: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor: ' . $e->getMessage()
            ], 500);
        }
    }
}
