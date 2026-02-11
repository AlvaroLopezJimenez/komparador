<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Tienda;
use App\Models\ComisionCategoriaTienda;
use App\Models\Categoria;

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
        'comisiones' => collect(), // vacío porque es nueva
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
            'como_scrapear' => 'required|in:automatico,manual,ambos',
            'frecuencia_minima_valor' => 'required|numeric|min:0.1',
            'frecuencia_minima_unidad' => 'required|in:minutos,horas,dias',
            'frecuencia_maxima_valor' => 'required|numeric|min:0.1',
            'frecuencia_maxima_unidad' => 'required|in:minutos,horas,dias',
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
            ...$request->only(['nombre', 'envio_gratis', 'envio_normal', 'url', 'url_imagen', 'url_opiniones', 'api', 'mostrar_tienda', 'scrapear', 'como_scrapear']),
            'opiniones' => $request->filled('opiniones') ? $request->input('opiniones') : 0,
            'puntuacion' => $request->filled('puntuacion') ? $request->input('puntuacion') : 0,
            'anotaciones_internas' => $request->input('anotaciones_internas'),
            'aviso' => $avisoFecha,
            'frecuencia_minima_minutos' => $frecuenciaMinimaMinutos,
            'frecuencia_maxima_minutos' => $frecuenciaMaximaMinutos,
        ]);

if ($request->has('comisiones')) {
    foreach ($request->input('comisiones') as $categoriaId => $comision) {
        if ($comision !== null && $comision !== '') {
            ComisionCategoriaTienda::create([
                'tienda_id' => $tienda->id,
                'categoria_id' => $categoriaId,
                'comision' => $comision,
            ]);
        }
    }
}

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
    $tienda->load('comisiones'); // <-- SOLUCIÓN
    $tienda->loadCount('ofertas'); // Cargar el conteo de ofertas

    $categorias = Categoria::with('children.children')->whereNull('parent_id')->get();
    $comisiones = $tienda->comisiones->pluck('comision', 'categoria_id');

    return view('admin.tiendas.formulario', compact('tienda', 'categorias', 'comisiones'));
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
            'como_scrapear' => 'required|in:automatico,manual,ambos',
            'frecuencia_minima_valor' => 'required|numeric|min:0.1',
            'frecuencia_minima_unidad' => 'required|in:minutos,horas,dias',
            'frecuencia_maxima_valor' => 'required|numeric|min:0.1',
            'frecuencia_maxima_unidad' => 'required|in:minutos,horas,dias',
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
                'como_scrapear',
            ]),
            'aviso' => $avisoFecha,
            'frecuencia_minima_minutos' => $frecuenciaMinimaMinutos,
            'frecuencia_maxima_minutos' => $frecuenciaMaximaMinutos,
        ]);

        
ComisionCategoriaTienda::where('tienda_id', $tienda->id)->delete();

if ($request->has('comisiones')) {
    foreach ($request->input('comisiones') as $categoriaId => $comision) {
        if ($comision !== null && $comision !== '') {
            ComisionCategoriaTienda::create([
                'tienda_id' => $tienda->id,
                'categoria_id' => $categoriaId,
                'comision' => $comision,
            ]);
        }
    }
}


        return redirect()->route('admin.tiendas.index')->with('success', 'Tienda actualizada correctamente.');
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
