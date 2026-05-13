<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Models\Tienda;
use App\Models\Categoria;
use App\Models\Neoobjetivo;
use App\Models\OfertaProducto;

class TiendaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $perPage = $request->input('perPage', 25);

        $query = \App\Models\Tienda::withCount('ofertas');

        if ($request->filled('buscar')) {
            $query->where('nombre', 'like', '%' . $request->buscar . '%');
        }

        $tiendas = $query->paginate($perPage)->appends($request->query());

        return view('admin.tiendas.index', compact('tiendas', 'perPage'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $categorias = Categoria::with('children.children')->whereNull('parent_id')->get();
        return view('admin.tiendas.formulario', [
            'tienda' => new Tienda(),
            'categorias' => $categorias,
            'urlsCategoria' => collect(),
            'visitadasCategoria' => collect(),
            'tipoListadoCategoria' => null,
            'mensajeControlador' => null,
            'mensajeTipoListado' => null,
            'categoriasSinNeoobjetivo' => collect(),
            'categoriasAncestrosSinNeo' => collect(),
            'conteoTotalOfertas' => [],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|unique:tiendas,nombre',
            'envio_gratis' => 'nullable|string',
            'envio_normal' => 'nullable|string',
            'url' => 'nullable|string',
            'url_imagen' => 'nullable|string',
            'opiniones' => 'nullable|integer',
            'puntuacion' => 'nullable|numeric|min:0|max:5',
            'url_opiniones' => 'nullable|string',
            'anotaciones_internas' => 'nullable|string',
            'aviso' => 'nullable|date',
            'api' => 'required|string|in:miVpsHtml;1,miVpsHtml;2,miVpsHtml;3,miVpsHtml;4,miVpsHtml;5,scrapingAnt,brightData;false,brightData;true,aliexpressOpen,amazonApi,amazonProductInfo,amazonPricing',
            'mostrar_tienda' => 'required|in:si,no',
            'scrapear' => 'required|in:si,no',
            'avisos_sin_stock_scrapear_automatico' => 'required|in:si,no',
            'como_scrapear' => 'required|in:automatico,manual,ambos',
            'frecuencia_minima_valor' => 'required|numeric|min:0.1',
            'frecuencia_minima_unidad' => 'required|in:minutos,horas,dias',
            'frecuencia_maxima_valor' => 'required|numeric|min:0.1',
            'frecuencia_maxima_unidad' => 'required|in:minutos,horas,dias',
            'visitada_categoria.*' => 'nullable|date',
        ]);
        
        // Convertir frecuencia mínima a minutos
        $frecuenciaMinimaMinutos = $this->convertirAMinutos(
            $request->input('frecuencia_minima_valor'),
            $request->input('frecuencia_minima_unidad')
        );
        
        // Convertir frecuencia máxima a minutos
        $frecuenciaMaximaMinutos = $this->convertirAMinutos(
            $request->input('frecuencia_maxima_valor'),
            $request->input('frecuencia_maxima_unidad')
        );
        
        // Validar que la frecuencia mínima sea menor o igual a la máxima
        if ($frecuenciaMinimaMinutos > $frecuenciaMaximaMinutos) {
            return redirect()->back()
                ->withErrors(['frecuencia_minima_valor' => 'La frecuencia mínima no puede ser mayor que la frecuencia máxima'])
                ->withInput();
        }
        
        // Validar que la frecuencia mínima sea al menos 15 minutos
        if ($frecuenciaMinimaMinutos < 15) {
            return redirect()->back()
                ->withErrors(['frecuencia_minima_valor' => 'La frecuencia mínima debe ser al menos 15 minutos'])
                ->withInput();
        }

        $avisoFecha = null;
        if ($request->filled('eliminar_aviso')) {
            $avisoFecha = null;
        } elseif ($request->filled('aviso_cantidad') && $request->filled('aviso_unidad')) {
            $avisoFecha = now()->add($request->input('aviso_unidad'), (int)$request->input('aviso_cantidad'))->setTime(0, 1);
        }


        $tienda = \App\Models\Tienda::create([
            ...$request->only(['nombre', 'envio_gratis', 'envio_normal', 'url', 'url_imagen', 'url_opiniones', 'api', 'mostrar_tienda', 'scrapear', 'avisos_sin_stock_scrapear_automatico', 'como_scrapear']),
            'opiniones' => $request->filled('opiniones') ? $request->input('opiniones') : 0,
            'puntuacion' => $request->filled('puntuacion') ? $request->input('puntuacion') : 0,
            'anotaciones_internas' => $request->input('anotaciones_internas'),
            'aviso' => $avisoFecha,
            'frecuencia_minima_minutos' => $frecuenciaMinimaMinutos,
            'frecuencia_maxima_minutos' => $frecuenciaMaximaMinutos,
        ]);

        $this->guardarUrlsCategoriaNeoobjetivo($request, $tienda);

        return redirect()->route('admin.tiendas.index')->with('success', 'Tienda creada correctamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Obtener tienda por ID en formato JSON
     */
    public function obtener($id)
    {
        $tienda = Tienda::find($id);
        
        if (!$tienda) {
            return response()->json(['error' => 'Tienda no encontrada'], 404);
        }
        
        return response()->json([
            'id' => $tienda->id,
            'nombre' => $tienda->nombre,
            'url_imagen' => $tienda->url_imagen,
            'como_scrapear' => $tienda->como_scrapear
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Tienda $tienda)
    {
        $tienda->loadCount('ofertas');
        $categorias = Categoria::with('children.children.children')->whereNull('parent_id')->get();

        // URLs de categoría desde Neoobjetivo (solo registros tienda+categoria, sin oferta/producto)
        $neoobjetivosCategoria = Neoobjetivo::where('tienda_id', $tienda->id)
            ->whereNull('oferta_id')
            ->whereNull('producto_id')
            ->get();

        $urlsCategoria = $neoobjetivosCategoria->pluck('url', 'categoria_id');
        $visitadasCategoria = $neoobjetivosCategoria
            ->mapWithKeys(function ($neo) {
                return [$neo->categoria_id => optional($neo->visitada)?->format('Y-m-d\TH:i')];
            });

        $mensajeControlador = null;
        $tipoListadoCategoria = null;
        $mensajeTipoListado = null;

        $nombreControlador = $this->normalizarNombreTienda($tienda->nombre);
        $claseControlador = "App\\Http\\Controllers\\Scraping\\Tiendas\\{$nombreControlador}Controller";

        if (!class_exists($claseControlador)) {
            $mensajeControlador = 'No se ha encontrado el controlador para esta tienda.';
        } else {
            $controladorTienda = new $claseControlador();
            if (!method_exists($controladorTienda, 'tipoListadoCategoria')) {
                $mensajeTipoListado = 'Esta tienda no tiene el método tipoListadoCategoria.';
            } else {
                $tipoListadoCategoria = $controladorTienda->tipoListadoCategoria();
                if ($tipoListadoCategoria === null) {
                    $mensajeTipoListado = 'Esta tienda no tiene tipo de listado de categoría configurado.';
                }
            }
        }

        // Categorías que tienen ofertas de esta tienda pero no tienen Neoobjetivo (URL) para esa categoría
        $categoriasConOfertas = OfertaProducto::where('tienda_id', $tienda->id)
            ->join('productos', 'ofertas_producto.producto_id', '=', 'productos.id')
            ->whereNotNull('productos.categoria_id')
            ->selectRaw('productos.categoria_id as cat_id')
            ->distinct()
            ->pluck('cat_id');
        $categoriasConNeoobjetivo = Neoobjetivo::where('tienda_id', $tienda->id)
            ->whereNull('oferta_id')
            ->whereNull('producto_id')
            ->pluck('categoria_id');
        $categoriasSinNeoobjetivo = $categoriasConOfertas->diff($categoriasConNeoobjetivo)->values();

        // IDs de categorías que son padre/abuelo de alguna con problema (solo se marcará el nombre en rojo)
        $categoriasAncestrosSinNeo = collect();
        foreach ($categoriasSinNeoobjetivo as $catId) {
            $categoria = Categoria::find($catId);
            while ($categoria && $categoria->parent_id) {
                $categoria = $categoria->parent;
                if ($categoria) {
                    $categoriasAncestrosSinNeo->push($categoria->id);
                }
            }
        }
        $categoriasAncestrosSinNeo = $categoriasAncestrosSinNeo->unique()->values();

        // Conteo de ofertas por categoría (esta tienda): directo por categoria_id
        $conteoDirectoOfertas = OfertaProducto::where('tienda_id', $tienda->id)
            ->join('productos', 'ofertas_producto.producto_id', '=', 'productos.id')
            ->whereNotNull('productos.categoria_id')
            ->groupBy('productos.categoria_id')
            ->selectRaw('productos.categoria_id as cat_id, count(*) as total')
            ->pluck('total', 'cat_id');

        // Por cada categoría del árbol: total = ofertas propias + suma de ofertas de todas las hijas (recursivo)
        $conteoTotalOfertas = [];
        $computeTotalOfertas = function ($categoria) use (&$computeTotalOfertas, $conteoDirectoOfertas, &$conteoTotalOfertas) {
            $id = $categoria->id;
            $directo = $conteoDirectoOfertas->get($id, 0);
            $sumaHijos = 0;
            if ($categoria->relationLoaded('children') && $categoria->children->isNotEmpty()) {
                foreach ($categoria->children as $hijo) {
                    $computeTotalOfertas($hijo);
                    $sumaHijos += $conteoTotalOfertas[$hijo->id] ?? 0;
                }
            }
            $conteoTotalOfertas[$id] = $directo + $sumaHijos;
        };
        foreach ($categorias as $c) {
            $computeTotalOfertas($c);
        }

        return view('admin.tiendas.formulario', compact(
            'tienda', 'categorias', 'urlsCategoria',
            'visitadasCategoria',
            'tipoListadoCategoria', 'mensajeControlador', 'mensajeTipoListado',
            'categoriasSinNeoobjetivo', 'categoriasAncestrosSinNeo',
            'conteoTotalOfertas'
        ));
    }



    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, \App\Models\Tienda $tienda)
    {
        $request->validate([
            'nombre' => 'required|unique:tiendas,nombre,' . $tienda->id,
            'envio_gratis' => 'nullable|string',
            'envio_normal' => 'nullable|string',
            'url' => 'nullable|string',
            'url_imagen' => 'nullable|string',
            'opiniones' => 'nullable|integer',
            'puntuacion' => 'nullable|numeric|min:0|max:5',
            'url_opiniones' => 'nullable|string',
            'anotaciones_internas' => 'nullable|string',
            'aviso' => 'nullable|date',
            'api' => 'required|string|in:miVpsHtml;1,miVpsHtml;2,miVpsHtml;3,miVpsHtml;4,miVpsHtml;5,scrapingAnt,brightData;false,brightData;true,aliexpressOpen,amazonApi,amazonProductInfo,amazonPricing',
            'mostrar_tienda' => 'required|in:si,no',
            'scrapear' => 'required|in:si,no',
            'avisos_sin_stock_scrapear_automatico' => 'required|in:si,no',
            'como_scrapear' => 'required|in:automatico,manual,ambos',
            'frecuencia_minima_valor' => 'required|numeric|min:0.1',
            'frecuencia_minima_unidad' => 'required|in:minutos,horas,dias',
            'frecuencia_maxima_valor' => 'required|numeric|min:0.1',
            'frecuencia_maxima_unidad' => 'required|in:minutos,horas,dias',
            'visitada_categoria.*' => 'nullable|date',
        ]);
        
        // Convertir frecuencia mínima a minutos
        $frecuenciaMinimaMinutos = $this->convertirAMinutos(
            $request->input('frecuencia_minima_valor'),
            $request->input('frecuencia_minima_unidad')
        );
        
        // Convertir frecuencia máxima a minutos
        $frecuenciaMaximaMinutos = $this->convertirAMinutos(
            $request->input('frecuencia_maxima_valor'),
            $request->input('frecuencia_maxima_unidad')
        );
        
        // Validar que la frecuencia mínima sea menor o igual a la máxima
        if ($frecuenciaMinimaMinutos > $frecuenciaMaximaMinutos) {
            return redirect()->back()
                ->withErrors(['frecuencia_minima_valor' => 'La frecuencia mínima no puede ser mayor que la frecuencia máxima'])
                ->withInput();
        }
        
        // Validar que la frecuencia mínima sea al menos 15 minutos
        if ($frecuenciaMinimaMinutos < 15) {
            return redirect()->back()
                ->withErrors(['frecuencia_minima_valor' => 'La frecuencia mínima debe ser al menos 15 minutos'])
                ->withInput();
        }

        $avisoFecha = $tienda->aviso;

        if ($request->filled('eliminar_aviso')) {
            $avisoFecha = null;
        } elseif ($request->filled('aviso_cantidad') && $request->filled('aviso_unidad')) {
            $avisoFecha = now()->add($request->input('aviso_unidad'), (int)$request->input('aviso_cantidad'))->setTime(0, 1);
        }



        $tienda->update([
            ...$request->only([
                'nombre',
                'envio_gratis',
                'envio_normal',
                'url',
                'url_imagen',
                'opiniones',
                'puntuacion',
                'url_opiniones',
                'anotaciones_internas',
                'api',
                'mostrar_tienda',
                'scrapear',
                'avisos_sin_stock_scrapear_automatico',
                'como_scrapear',
            ]),
            'aviso' => $avisoFecha,
            'frecuencia_minima_minutos' => $frecuenciaMinimaMinutos,
            'frecuencia_maxima_minutos' => $frecuenciaMaximaMinutos,
        ]);

        // Validar: si hay categorías con ofertas sin URL de categoría, no permitir guardar salvo que marquen "sin listado"
        $categoriasConOfertas = OfertaProducto::where('tienda_id', $tienda->id)
            ->join('productos', 'ofertas_producto.producto_id', '=', 'productos.id')
            ->whereNotNull('productos.categoria_id')
            ->selectRaw('productos.categoria_id as cat_id')
            ->distinct()
            ->pluck('cat_id');
        $urlsEnviadas = $request->has('urls_categoria') ? collect(array_keys(array_filter($request->input('urls_categoria', [])))) : collect();
        $categoriasConNeoobjetivoActual = Neoobjetivo::where('tienda_id', $tienda->id)
            ->whereNull('oferta_id')
            ->whereNull('producto_id')
            ->pluck('categoria_id');
        $categoriasQueTendranNeo = $categoriasConNeoobjetivoActual->merge($urlsEnviadas)->unique();
        $categoriasSiguenSinNeo = $categoriasConOfertas->diff($categoriasQueTendranNeo);

        if ($categoriasSiguenSinNeo->isNotEmpty() && !$request->boolean('sin_listado_categoria')) {
            return redirect()->back()
                ->withErrors(['urls_categoria' => 'Hay categorías con ofertas de esta tienda que no tienen URL de listado. Rellena la URL para cada categoría en roja o marca "Esta tienda no tiene listado por categoría".'])
                ->withInput();
        }

        $this->guardarUrlsCategoriaNeoobjetivo($request, $tienda);

        return redirect()->route('admin.tiendas.index')->with('success', 'Tienda actualizada correctamente.');
    }

    /**
     * Normaliza el nombre de la tienda para resolver la clase del controlador (misma lógica que ScrapingController).
     */
    private function normalizarNombreTienda(string $nombre): string
    {
        $normalizado = strtolower($nombre);
        $normalizado = Str::ascii($normalizado);
        $normalizado = preg_replace('/[^a-z0-9]/', '', $normalizado);
        return ucfirst($normalizado);
    }

    /**
     * Guarda o actualiza en Neoobjetivo las URLs por categoría (solo tienda_id + categoria_id, resto null).
     * visitada = 7 días antes del momento de guardar; si ya existe el registro se actualiza, no se crea uno nuevo.
     */
    private function guardarUrlsCategoriaNeoobjetivo(Request $request, Tienda $tienda): void
    {
        $urls = $request->input('urls_categoria', []);
        $visitadas = $request->input('visitada_categoria', []);
        if (!is_array($urls)) {
            return;
        }
        foreach ($urls as $categoriaId => $url) {
            $url = is_string($url) ? trim($url) : '';
            if ($url === '') {
                continue;
            }

            $visitada = now()->subDays(7);
            $visitadaInput = $visitadas[$categoriaId] ?? null;
            if (is_string($visitadaInput) && trim($visitadaInput) !== '') {
                try {
                    $visitada = Carbon::parse($visitadaInput);
                } catch (\Throwable $e) {
                    $visitada = now()->subDays(7);
                }
            }

            Neoobjetivo::updateOrCreate(
                [
                    'tienda_id' => $tienda->id,
                    'categoria_id' => (int) $categoriaId,
                    'oferta_id' => null,
                    'producto_id' => null,
                ],
                [
                    'url' => $url,
                    'visitada' => $visitada,
                    'oferta_id' => null,
                    'producto_id' => null,
                ]
            );
        }
    }

    /**
     * Convertir valor y unidad a minutos
     */
    private function convertirAMinutos($valor, $unidad)
    {
        $valor = (float) $valor;
        
        switch ($unidad) {
            case 'dias':
                return (int) round($valor * 1440);
            case 'horas':
                return (int) round($valor * 60);
            case 'minutos':
            default:
                return (int) round($valor);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    // MÉTODO DESTROY COMENTADO POR SEGURIDAD - No se permite eliminar tiendas desde el panel
    /*
    public function destroy(\App\Models\Tienda $tienda)
    {
        $tienda->delete();
        return redirect()->route('admin.tiendas.index')->with('success', 'Tienda eliminada correctamente.');
    }
    */

    // Mostrar las ofertas asociadas a esta tienda

    public function ofertas(Tienda $tienda)
    {
        $perPage = request('perPage', 20);

        $query = $tienda->ofertas()->with('producto')->orderByDesc('created_at');

        if (request('buscar')) {
            $query->whereHas('producto', function ($q) {
                $q->where('nombre', 'like', '%' . request('buscar') . '%');
            });
        }

        $ofertas = $query->paginate($perPage)->appends(request()->query());

        return view('admin.tiendas.ofertas', compact('tienda', 'ofertas'));
    }

    /**
     * JSON: desglose de valores de envío (columna envio) por número de ofertas de la tienda.
     */
    public function resumenEnvioOfertasTienda(Tienda $tienda)
    {
        $filas = $tienda->ofertas()
            ->selectRaw('envio, COUNT(*) as total')
            ->groupBy('envio')
            ->orderByRaw('envio IS NULL ASC')
            ->orderBy('envio')
            ->get();

        $grupos = $filas->map(function ($row) {
            $total = (int) $row->total;
            $ofertaTxt = $total === 1 ? 'oferta' : 'ofertas';

            if ($row->envio === null) {
                $clave = '__null__';
                $textoLinea = 'Sin gasto definido (' . $total . ' ' . $ofertaTxt . ')';
            } else {
                $valor = (float) $row->envio;
                $clave = number_format($valor, 2, '.', '');
                $parteEuro = $valor === 1.0 ? '1 euro' : number_format($valor, 2, ',', '') . ' euros';
                $textoLinea = $parteEuro . ' (' . $total . ' ' . $ofertaTxt . ')';
            }

            return [
                'clave' => $clave,
                'texto' => $textoLinea,
                'total' => $total,
            ];
        })->values();

        return response()->json(['grupos' => $grupos]);
    }

    /**
     * Actualiza el campo envio (y fecha_actualizacion_envio) en bloque para ofertas
     * cuyo envío actual coincide con los grupos seleccionados.
     */
    public function modificarEnvioOfertasMasivo(Request $request, Tienda $tienda)
    {
        $validated = $request->validate([
            'nuevo_envio' => ['required', 'numeric', 'min:0', 'max:99.99'],
            'claves_envio' => ['required', 'array', 'min:1'],
            'claves_envio.*' => ['required', 'string', 'max:32'],
        ]);

        $clavesPermitidas = $tienda->ofertas()
            ->selectRaw('envio')
            ->groupBy('envio')
            ->get()
            ->pluck('envio')
            ->map(function ($v) {
                return $v === null ? '__null__' : number_format((float) $v, 2, '.', '');
            })
            ->all();

        $claves = array_values(array_intersect($validated['claves_envio'], $clavesPermitidas));

        if ($claves === []) {
            return response()->json([
                'ok' => false,
                'mensaje' => 'Ninguna de las claves enviadas coincide con los gastos de envío actuales de esta tienda.',
                'actualizadas' => 0,
                'errores' => [],
            ], 422);
        }

        $incluyeNull = in_array('__null__', $claves, true);
        $valoresNumericos = [];
        foreach ($claves as $c) {
            if ($c === '__null__') {
                continue;
            }
            $valoresNumericos[] = round((float) $c, 2);
        }

        $nuevoEnvio = round((float) $validated['nuevo_envio'], 2);
        $ahora = now();

        try {
            $afectadas = $tienda->ofertas()->where(function ($q) use ($incluyeNull, $valoresNumericos) {
                if ($valoresNumericos !== [] && $incluyeNull) {
                    $q->whereIn('envio', $valoresNumericos)->orWhereNull('envio');
                } elseif ($valoresNumericos !== []) {
                    $q->whereIn('envio', $valoresNumericos);
                } elseif ($incluyeNull) {
                    $q->whereNull('envio');
                }
            })->update([
                'envio' => $nuevoEnvio,
                'fecha_actualizacion_envio' => $ahora,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'ok' => false,
                'mensaje' => 'Error al actualizar las ofertas.',
                'actualizadas' => 0,
                'errores' => [$e->getMessage()],
            ], 500);
        }

        return response()->json([
            'ok' => true,
            'mensaje' => 'Se ha actualizado el gasto de envío a ' . number_format($nuevoEnvio, 2, ',', '') . ' € en ' . $afectadas . ' oferta(s).',
            'actualizadas' => $afectadas,
            'errores' => [],
        ]);
    }

    /**
     * Mostrar vista para gestionar tiempos de actualización de ofertas por tienda
     */
    public function tiemposActualizacion()
    {
        $tiendas = Tienda::withCount(['ofertas' => function ($query) {
                $query->whereNull('chollo_id');
            }])
            ->having('ofertas_count', '>', 0)
            ->orderBy('nombre')
            ->get()
            ->map(function ($tienda) {
                // Obtener el tiempo más común (moda) de actualización
                $tiempoComunMinutos = $tienda->ofertas()
                    ->whereNull('chollo_id') // Excluir ofertas con chollo_id
                    ->selectRaw('frecuencia_actualizar_precio_minutos, COUNT(*) as cantidad')
                    ->groupBy('frecuencia_actualizar_precio_minutos')
                    ->orderByDesc('cantidad')
                    ->first()
                    ?->frecuencia_actualizar_precio_minutos ?? 1440; // Default 24 horas si no hay ofertas
                
                return [
                    'id' => $tienda->id,
                    'nombre' => $tienda->nombre,
                    'total_ofertas' => $tienda->ofertas_count,
                    'tiempo_comun_minutos' => $tiempoComunMinutos,
                    'tiempo_comun_formateado' => $this->formatearTiempo($tiempoComunMinutos)
                ];
            });

        return view('admin.tiendas.tiempos-actualizacion', compact('tiendas'));
    }

    /**
     * Obtener desglose de ofertas por tiempo de actualización para una tienda específica
     */
    public function obtenerDesgloseTiempos(Tienda $tienda)
    {
        $desglose = $tienda->ofertas()
            ->whereNull('chollo_id') // Excluir ofertas con chollo_id
            ->selectRaw('frecuencia_actualizar_precio_minutos, COUNT(*) as cantidad')
            ->groupBy('frecuencia_actualizar_precio_minutos')
            ->orderBy('frecuencia_actualizar_precio_minutos')
            ->get()
            ->map(function ($item) {
                $horas = round($item->frecuencia_actualizar_precio_minutos / 60, 1);
                $dias = round($item->frecuencia_actualizar_precio_minutos / (24 * 60), 1);
                
                return [
                    'minutos' => $item->frecuencia_actualizar_precio_minutos,
                    'cantidad' => $item->cantidad,
                    'horas' => $horas,
                    'dias' => $dias,
                    'formato' => $this->formatearTiempo($item->frecuencia_actualizar_precio_minutos)
                ];
            });

        return response()->json([
            'tienda' => $tienda->nombre,
            'frecuencia_minima_minutos' => $tienda->frecuencia_minima_minutos ?? 0,
            'frecuencia_maxima_minutos' => $tienda->frecuencia_maxima_minutos ?? 1440,
            'desglose' => $desglose
        ]);
    }

    /**
     * Actualizar tiempo de actualización de todas las ofertas de una tienda
     */
    public function actualizarTiempos(Request $request, Tienda $tienda)
    {
        $request->validate([
            'tiempo_cantidad' => 'required|integer|min:1',
            'tiempo_unidad' => 'required|in:minutos,horas,dias'
        ]);

        // Convertir a minutos
        $minutos = $request->tiempo_cantidad;
        switch ($request->tiempo_unidad) {
            case 'horas':
                $minutos *= 60;
                break;
            case 'dias':
                $minutos *= 24 * 60;
                break;
        }

        // Actualizar solo las ofertas de la tienda que NO tengan chollo_id
        $ofertasActualizadas = $tienda->ofertas()
            ->whereNull('chollo_id') // Excluir ofertas con chollo_id
            ->update([
                'frecuencia_actualizar_precio_minutos' => $minutos
            ]);

        return response()->json([
            'success' => true,
            'message' => "Se actualizaron {$ofertasActualizadas} ofertas de {$tienda->nombre}",
            'tiempo_formateado' => $this->formatearTiempo($minutos)
        ]);
    }

    /**
     * Gestionar URLs de listado por categoría para todas las tiendas.
     */
    public function urlsPorCategoria(Request $request)
    {
        $categoriaSeleccionada = null;
        $tiendas = collect();
        $categoriaId = $request->input('categoria_id');

        if ($categoriaId) {
            $categoriaSeleccionada = Categoria::find($categoriaId);

            if ($categoriaSeleccionada) {
                $neoPorTienda = Neoobjetivo::query()
                    ->where('categoria_id', $categoriaId)
                    ->whereNull('oferta_id')
                    ->whereNull('producto_id')
                    ->get()
                    ->keyBy('tienda_id');

                $tiendas = Tienda::query()
                    ->orderBy('tiendas.nombre')
                    ->get([
                        'tiendas.id',
                        'tiendas.nombre',
                        'tiendas.url',
                        'tiendas.mostrar_tienda',
                        'tiendas.scrapear',
                        'tiendas.anotaciones_internas',
                    ])
                    ->map(function ($tienda) use ($neoPorTienda) {
                        $neo = $neoPorTienda->get($tienda->id);
                        $tienda->url_categoria = $neo?->url ?? '';
                        $tienda->visitada_categoria = $neo?->visitada;
                        return $tienda;
                    });
            }
        }

        return view('admin.tiendas.urls-por-categoria', [
            'categoriaSeleccionada' => $categoriaSeleccionada,
            'categoriaId' => $categoriaSeleccionada?->id,
            'categoriaNombre' => $categoriaSeleccionada?->nombre,
            'tiendas' => $tiendas,
        ]);
    }

    /**
     * Guardar URL de categoría para una tienda concreta.
     */
    public function guardarUrlPorCategoria(Request $request)
    {
        $validated = $request->validate([
            'tienda_id' => 'required|exists:tiendas,id',
            'categoria_id' => 'required|exists:categorias,id',
            'url_categoria' => 'nullable|string',
            'visitada_categoria' => 'nullable|date',
        ]);

        $urlCategoria = trim((string) ($validated['url_categoria'] ?? ''));
        $visitadaCategoria = $validated['visitada_categoria'] ?? null;

        if ($urlCategoria === '') {
            Neoobjetivo::where('tienda_id', (int) $validated['tienda_id'])
                ->where('categoria_id', (int) $validated['categoria_id'])
                ->whereNull('oferta_id')
                ->whereNull('producto_id')
                ->delete();

            return redirect()
                ->route('admin.tiendas.urls-por-categoria', ['categoria_id' => $validated['categoria_id']])
                ->with('success', 'URL eliminada para esta tienda en la categoría seleccionada.');
        }

        $visitada = now()->subDays(7);
        if (is_string($visitadaCategoria) && trim($visitadaCategoria) !== '') {
            try {
                $visitada = Carbon::parse($visitadaCategoria);
            } catch (\Throwable $e) {
                $visitada = now()->subDays(7);
            }
        }

        Neoobjetivo::updateOrCreate(
            [
                'tienda_id' => (int) $validated['tienda_id'],
                'categoria_id' => (int) $validated['categoria_id'],
                'oferta_id' => null,
                'producto_id' => null,
            ],
            [
                'url' => $urlCategoria,
                'visitada' => $visitada,
                'oferta_id' => null,
                'producto_id' => null,
            ]
        );

        return redirect()
            ->route('admin.tiendas.urls-por-categoria', ['categoria_id' => $validated['categoria_id']])
            ->with('success', 'URL guardada correctamente.');
    }

    /**
     * Formatear tiempo en minutos a texto legible
     */
    private function formatearTiempo($minutos)
    {
        if ($minutos < 60) {
            return "{$minutos} min";
        } elseif ($minutos < 24 * 60) {
            $horas = round($minutos / 60, 1);
            return "{$horas} horas";
        } else {
            $dias = round($minutos / (24 * 60), 1);
            return "{$dias} días";
        }
    }

}
