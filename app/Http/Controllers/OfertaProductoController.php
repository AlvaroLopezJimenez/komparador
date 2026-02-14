<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Chollo;
use Illuminate\Http\Request;
use App\Models\Producto;
use App\Models\OfertaProducto;
use App\Models\Aviso;
use App\Models\HistoricoPrecioOferta;
use App\Models\HistoricoTiempoActualizacionPrecioOferta;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;
use App\Services\CalcularPrecioUnidad;
use App\Services\SacarPrimeraOfertaDeUnProductoAplicadoDescuentosYChollos;
use App\Models\ProductoOfertaMasBarataPorProducto;

class OfertaProductoController extends Controller
{
    private function parsePrecioToFloat($value): ?float
    {
        if ($value === null) {
            return null;
        }
        if (is_string($value)) {
            $value = trim($value);
            if ($value === '') {
                return null;
            }
            $value = str_replace(',', '.', $value);
        }
        if (!is_numeric($value)) {
            return null;
        }
        return (float) $value;
    }

    private function esPrecioCero($value): bool
    {
        $v = $this->parsePrecioToFloat($value);
        return $v !== null && abs($v) < 0.0000001;
    }

    private function crearAvisoSinStockSiNoExiste(OfertaProducto $oferta): void
    {
        $fechaAviso = now()->addDays(4)->setTime(0, 1, 0);
        $texto = 'Sin stock - 1a vez';
        $userId = auth()->id();

        $existe = Aviso::where('avisoable_type', OfertaProducto::class)
            ->where('avisoable_id', $oferta->id)
            ->where('texto_aviso', $texto)
            ->where('fecha_aviso', $fechaAviso)
            ->where('user_id', $userId)
            ->exists();

        if ($existe) {
            return;
        }

        Aviso::create([
            'texto_aviso' => $texto,
            'fecha_aviso' => $fechaAviso,
            'user_id' => auth()->id(),
            'avisoable_type' => OfertaProducto::class,
            'avisoable_id' => $oferta->id,
            'oculto' => false,
        ]);
    }
    public function index(Producto $producto, Request $request)
    {
        $perPage = $request->input('perPage', 20);
        $busqueda = $request->input('busqueda');
        $mostrar = $request->input('mostrar', ['si']); // Por defecto solo mostrar las que tienen 'si'
        
        // Si hay búsqueda y no se ha especificado manualmente el filtro 'no', incluirlo automáticamente
        if ($busqueda && !in_array('no', $mostrar)) {
            $mostrar[] = 'no';
        }
        
        // Crear variable para las vistas que refleje el estado real de los checkboxes
        $mostrarParaVista = $mostrar;

        $ofertas = OfertaProducto::with(['tienda', 'producto'])
            ->where('producto_id', $producto->id)
            ->when($busqueda, function ($query, $busqueda) {
                $busqueda = strtolower($busqueda);
                $query->where(function ($q) use ($busqueda) {
                    $q->whereRaw('LOWER(url) LIKE ?', ["%{$busqueda}%"])
                    ->orWhereRaw('LOWER(anotaciones_internas) LIKE ?', ["%{$busqueda}%"])
                    ->orWhereHas('tienda', function ($q2) use ($busqueda) {
                        $q2->whereRaw('LOWER(nombre) LIKE ?', ["%{$busqueda}%"]);
                    })
                    ->orWhereHas('producto', function ($q3) use ($busqueda) {
                        $q3->whereRaw('LOWER(nombre) LIKE ?', ["%{$busqueda}%"])
                            ->orWhereRaw('LOWER(modelo) LIKE ?', ["%{$busqueda}%"])
                            ->orWhereRaw('LOWER(talla) LIKE ?', ["%{$busqueda}%"]);
                    });
                });
            })
            ->when($mostrar, function ($query, $mostrar) {
                if (is_array($mostrar)) {
                    $query->whereIn('mostrar', $mostrar);
                } else {
                    $query->where('mostrar', $mostrar);
                }
            })
            ->orderBy('precio_unidad', 'asc')
            ->paginate($perPage)
            ->withQueryString();

        // Obtener información de tiendas para el producto
        $tiendasInfo = [];
        $tiendas = \App\Models\Tienda::orderBy('nombre', 'asc')->get();
        
        foreach ($tiendas as $tienda) {
            $ofertasTienda = OfertaProducto::where('producto_id', $producto->id)
                ->where('tienda_id', $tienda->id)
                ->get();
            
            $totalOfertas = $ofertasTienda->count();
            $ofertasActivas = $ofertasTienda->where('mostrar', 'si')->count();
            
            $tiendasInfo[] = [
                'tienda' => $tienda,
                'tiene_ofertas' => $totalOfertas > 0,
                'tiene_ofertas_activas' => $ofertasActivas > 0,
                'total_ofertas' => $totalOfertas,
                'ofertas_activas' => $ofertasActivas
            ];
        }

        return view('admin.ofertas.index', compact('producto', 'ofertas', 'perPage', 'tiendasInfo', 'mostrarParaVista'));
    }

    public function todas(Request $request)
    {
        $perPage = $request->input('perPage', 20);
        $busqueda = $request->input('busqueda');
        $mostrar = $request->input('mostrar', ['si']); // Por defecto solo mostrar las que tienen 'si'
        
        // Si hay búsqueda y no se ha especificado manualmente el filtro 'no', incluirlo automáticamente
        if ($busqueda && !in_array('no', $mostrar)) {
            $mostrar[] = 'no';
        }
        
        // Crear variable para las vistas que refleje el estado real de los checkboxes
        $mostrarParaVista = $mostrar;

        $ofertas = OfertaProducto::with(['tienda', 'producto'])
            ->when($busqueda, function ($query, $busqueda) {
                $busqueda = strtolower($busqueda);
                $query->where(function ($q) use ($busqueda) {
                    $q->whereRaw('LOWER(url) LIKE ?', ["%{$busqueda}%"])
                    ->orWhereRaw('LOWER(anotaciones_internas) LIKE ?', ["%{$busqueda}%"])
                    ->orWhereHas('tienda', function ($q2) use ($busqueda) {
                        $q2->whereRaw('LOWER(nombre) LIKE ?', ["%{$busqueda}%"]);
                    })
                    ->orWhereHas('producto', function ($q3) use ($busqueda) {
                        $q3->whereRaw('LOWER(nombre) LIKE ?', ["%{$busqueda}%"])
                            ->orWhereRaw('LOWER(modelo) LIKE ?', ["%{$busqueda}%"])
                            ->orWhereRaw('LOWER(talla) LIKE ?', ["%{$busqueda}%"]);
                    });
                });
            })
            ->when($mostrar, function ($query, $mostrar) {
                if (is_array($mostrar)) {
                    $query->whereIn('mostrar', $mostrar);
                } else {
                    $query->where('mostrar', $mostrar);
                }
            })
            ->orderBy('created_at', 'desc')
            ->paginate($perPage)
            ->withQueryString();

        return view('admin.ofertas.todas', compact('ofertas', 'perPage', 'mostrarParaVista'));
    }



    public function edit(OfertaProducto $oferta)
    {
        $oferta->load(['producto', 'chollo']);
        $producto = $oferta->producto; // relación inversa
        return view('admin.ofertas.formulario', compact('oferta', 'producto'));
    }


    public function update(Request $request, OfertaProducto $oferta)
    {
        // Reemplazar comas por puntos en los campos numéricos antes de validar
        $request->merge([
            'precio_total' => str_replace(',', '.', $request->precio_total),
            'precio_unidad' => str_replace(',', '.', $request->precio_unidad),
            'envio' => $request->filled('envio') ? str_replace(',', '.', $request->envio) : null,
        ]);

        $validated = $request->validate([
            'producto_id' => 'required|exists:productos,id',
            'tienda_id' => 'required|exists:tiendas,id',
            'unidades' => 'required|numeric|min:0.01',
            'precio_total' => 'required|numeric|min:0',
            'precio_unidad' => 'required|numeric|min:0',
            'envio' => 'nullable|numeric|min:0|max:99.99',
            'url' => 'required|url',
            'variante' => 'nullable|string|max:255',
            'descuentos' => 'nullable|string',
            'cupon_cantidad' => 'nullable|numeric|min:0',
            'mostrar' => 'required|string',
            'como_scrapear' => 'required|in:automatico,manual',
            'anotaciones_internas' => 'nullable|string',
            'aviso' => 'nullable|date',
            'fecha_actualizacion_manual' => 'nullable|date',
            'es_chollo' => 'nullable|boolean',
            'chollo_id' => 'nullable|exists:chollos,id',
            'fecha_inicio' => 'nullable|date',
            'fecha_final' => 'nullable|date',
            'comprobada' => 'nullable|date',
            'frecuencia_chollo_valor' => 'nullable|numeric|min:0.1',
            'frecuencia_chollo_unidad' => 'nullable|in:minutos,horas,dias',
        ]);

        $esOfertaChollo = $request->boolean('es_chollo') || $request->filled('chollo_id');

        if ($esOfertaChollo && !$request->filled('chollo_id')) {
            throw ValidationException::withMessages([
                'chollo_id' => 'Debes seleccionar un chollo para vincular esta oferta.',
            ]);
        }

        if ($esOfertaChollo && !$request->filled('fecha_inicio')) {
            throw ValidationException::withMessages([
                'fecha_inicio' => 'Debes indicar la fecha de inicio del chollo.',
            ]);
        }

        $frecuenciaCholloMin = $esOfertaChollo
            ? $this->calcularFrecuenciaCholloEnMinutos(
                $request->input('frecuencia_chollo_valor'),
                $request->input('frecuencia_chollo_unidad')
            )
            : null;

        if ($esOfertaChollo && $frecuenciaCholloMin === null) {
            $frecuenciaCholloMin = 1440;
        }

        $esOfertaChollo = $request->boolean('es_chollo') || $request->filled('chollo_id');

        if ($esOfertaChollo && !$request->filled('chollo_id')) {
            throw ValidationException::withMessages([
                'chollo_id' => 'Debes seleccionar un chollo para vincular esta oferta.',
            ]);
        }

        if ($esOfertaChollo && !$request->filled('fecha_inicio')) {
            throw ValidationException::withMessages([
                'fecha_inicio' => 'Debes indicar la fecha de inicio del chollo.',
            ]);
        }

        $frecuenciaCholloMin = $esOfertaChollo
            ? $this->calcularFrecuenciaCholloEnMinutos(
                $request->input('frecuencia_chollo_valor'),
                $request->input('frecuencia_chollo_unidad')
            )
            : null;

        if ($esOfertaChollo && $frecuenciaCholloMin === null) {
            $frecuenciaCholloMin = 1440;
        }

        $esOfertaChollo = $request->boolean('es_chollo') || $request->filled('chollo_id');

        if ($esOfertaChollo && !$request->filled('chollo_id')) {
            throw ValidationException::withMessages([
                'chollo_id' => 'Debes seleccionar un chollo para vincular esta oferta.',
            ]);
        }

        if ($esOfertaChollo && !$request->filled('fecha_inicio')) {
            throw ValidationException::withMessages([
                'fecha_inicio' => 'Debes indicar la fecha de inicio del chollo.',
            ]);
        }

        $frecuenciaCholloMin = $esOfertaChollo
            ? $this->calcularFrecuenciaCholloEnMinutos(
                $request->input('frecuencia_chollo_valor'),
                $request->input('frecuencia_chollo_unidad')
            )
            : null;

        if ($esOfertaChollo && $frecuenciaCholloMin === null) {
            $frecuenciaCholloMin = 1440;
        }

        $esOfertaChollo = $request->boolean('es_chollo') || $request->filled('chollo_id');

        if ($esOfertaChollo && !$request->filled('chollo_id')) {
            throw ValidationException::withMessages([
                'chollo_id' => 'Debes seleccionar un chollo para vincular esta oferta.',
            ]);
        }

        if ($esOfertaChollo && !$request->filled('fecha_inicio')) {
            throw ValidationException::withMessages([
                'fecha_inicio' => 'Debes indicar la fecha de inicio del chollo.',
            ]);
        }

        $frecuenciaCholloMin = $esOfertaChollo
            ? $this->calcularFrecuenciaCholloEnMinutos(
                $request->input('frecuencia_chollo_valor'),
                $request->input('frecuencia_chollo_unidad')
            )
            : null;

        if ($esOfertaChollo && $frecuenciaCholloMin === null) {
            $frecuenciaCholloMin = 1440;
        }

        $valor = (float) $request->input('frecuencia_valor');
        $unidad = $request->input('frecuencia_unidad');

        $minutos = match ($unidad) {
            'minutos' => $valor,
            'horas' => $valor * 60,
            'dias' => $valor * 1440,
            default => 1440,
        };

        // Normalizar el valor de aviso: convertir "NULL" (texto) a null real
        // Obtener el valor crudo de la base de datos para evitar que Laravel intente parsearlo como fecha
        $avisoRaw = $oferta->getAttributes()['aviso'] ?? $oferta->getOriginal('aviso') ?? null;
        
        // Si el valor crudo es "NULL" como texto, convertir a null
        if ($avisoRaw === 'NULL' || $avisoRaw === 'null' || (is_string($avisoRaw) && strtoupper(trim($avisoRaw)) === 'NULL')) {
            $avisoFecha = null;
        } else {
            // Si no es "NULL" como texto, intentar obtener el valor parseado (puede ser null, Carbon, etc.)
            try {
                $avisoFecha = $oferta->aviso;
            } catch (\Exception $e) {
                // Si falla al parsear, usar null
                $avisoFecha = null;
            }
        }

        if ($request->filled('eliminar_aviso')) {
            $avisoFecha = null;
        } elseif ($request->filled('aviso_cantidad') && $request->filled('aviso_unidad')) {
            $avisoFecha = now()->add($request->input('aviso_unidad'), (int)$request->input('aviso_cantidad'))->setTime(0, 1);
        }

        // Procesar descuentos para cupones
        $descuentos = isset($validated['descuentos']) ? trim($validated['descuentos']) : '';

        if ($descuentos === '2a al 50% - cheque - Solo Carrefour') {
            $descuentos = '2a al 50 - cheque - SoloCarrefour';
        }
        
        // Si el campo descuentos ya viene con formato construido (cupon;codigo;cantidad o cupon;cantidad), no modificarlo
        // Solo construir el formato si viene como 'cupon' sin formato (fallback por si el JavaScript no funciona)
        if ($descuentos === 'cupon') {
            $codigoCupon = $request->input('cupon_codigo');
            $cantidadCupon = $request->input('cupon_cantidad');
            
            if ($codigoCupon && $cantidadCupon) {
                // Formato nuevo: cupon;codigo;cantidad
                $descuentos = 'cupon;' . trim($codigoCupon) . ';' . $cantidadCupon;
            } elseif ($cantidadCupon) {
                // Formato antiguo: cupon;cantidad (solo cantidad, sin código)
                $descuentos = 'cupon;' . $cantidadCupon;
            }
        }
        // Si ya viene con formato cupon;codigo;cantidad o cupon;cantidad, no modificarlo

        $datosBase = Arr::except($validated, [
            'es_chollo',
            'frecuencia_chollo_valor',
            'frecuencia_chollo_unidad',
        ]);

        $precioCero = $this->esPrecioCero($request->input('precio_total')) || $this->esPrecioCero($request->input('precio_unidad'));
        if ($precioCero) {
            $datosBase['mostrar'] = 'no';
        }

        $datosBase['chollo_id'] = $esOfertaChollo ? $request->input('chollo_id') : null;
        $datosBase['fecha_inicio'] = $esOfertaChollo ? $this->parseNullableDateTime($request->input('fecha_inicio')) : null;
        $datosBase['fecha_final'] = $esOfertaChollo ? $this->parseNullableDateTime($request->input('fecha_final')) : null;
        $datosBase['comprobada'] = $esOfertaChollo ? $this->parseNullableDateTime($request->input('comprobada')) : null;
        $datosBase['frecuencia_comprobacion_chollos_min'] = $esOfertaChollo ? $frecuenciaCholloMin : null;

        // Guardar precio anterior antes de actualizar
        $precioAnterior = $oferta->precio_total;
        $precioNuevo = (float) $validated['precio_total'];
        $precioCambio = abs($precioAnterior - $precioNuevo) > 0.01; // Considerar cambio si diferencia > 1 céntimo

        // Verificar si el campo envio ha cambiado
        $envioAnterior = $oferta->envio;
        $envioNuevo = $request->filled('envio') ? (float) $validated['envio'] : null;
        $envioCambio = false;
        
        // Comparar valores (considerando null y 0)
        if (($envioAnterior === null && $envioNuevo !== null) || 
            ($envioAnterior !== null && $envioNuevo === null) ||
            ($envioAnterior !== null && $envioNuevo !== null && abs($envioAnterior - $envioNuevo) > 0.01)) {
            $envioCambio = true;
        }

        // Deshabilitar timestamps automáticos temporalmente
        $oferta->timestamps = false;

        // Procesar especificaciones internas
        $especificacionesInternas = null;
        if ($request->has('especificaciones_internas') && !empty($request->especificaciones_internas)) {
            $especificacionesInternas = json_decode($request->especificaciones_internas, true);
        }

        // Si se proporciona envio, establecer frecuencia_actualizar_envio_minutos con el valor por defecto
        $datosActualizar = [
            'frecuencia_actualizar_precio_minutos' => round($minutos),
            'aviso' => $avisoFecha,
            'descuentos' => $descuentos,
            'especificaciones_internas' => $especificacionesInternas,
        ];
        
        // Si el campo envio ha cambiado, actualizar fecha_actualizacion_envio
        if ($envioCambio) {
            $datosActualizar['fecha_actualizacion_envio'] = now();
        }

        $oferta->update(array_merge($datosBase, $datosActualizar));

        // Si se proporcionó una fecha de actualización manual, establecerla
        if ($request->filled('fecha_actualizacion_manual')) {
            $oferta->updated_at = \Carbon\Carbon::parse($request->input('fecha_actualizacion_manual'));
            $oferta->save();
        }

        // Si el precio cambió y la oferta no tiene chollo_id, registrar o actualizar en el historial
        if ($precioCambio && !$oferta->chollo_id) {
            $serviceTiempos = new \App\Services\TiemposActualizacionOfertasDinamicos();
            $serviceTiempos->registrarOActualizarActualizacion($oferta->id, $precioNuevo, 'manual');
        }

        // Si el precio final es 0, crear aviso de "Sin stock - 1a vez" a 4 días (después de guardar)
        if ($precioCero) {
            $this->crearAvisoSinStockSiNoExiste($oferta);
        }

        // Rehabilitar timestamps automáticos
        $oferta->timestamps = true;

        // Recalcular la oferta más barata del producto y actualizar precio del producto
        try {
            $producto = Producto::find($oferta->producto_id);
            
            if ($producto) {
                $servicioOfertas = new SacarPrimeraOfertaDeUnProductoAplicadoDescuentosYChollos();
                $ofertaMasBarata = $servicioOfertas->obtener($producto);

                if ($ofertaMasBarata) {
                    // Obtener la oferta original de la base de datos para guardar los datos originales
                    $ofertaOriginal = OfertaProducto::find($ofertaMasBarata->id);

                    if ($ofertaOriginal) {
                        // Actualizar o crear el registro en la tabla producto_oferta_mas_barata_por_producto
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

                        // Actualizar el precio del producto con el precio_unidad de la oferta más barata
                        $precioRealMasBajo = $ofertaMasBarata->precio_unidad;
                        
                        // Validar que el precio es válido (mayor que 0)
                        if ($precioRealMasBajo > 0) {
                            // Si el producto tiene unidadDeMedida = unidadMilesima, redondear a 3 decimales
                            if ($producto->unidadDeMedida === 'unidadMilesima') {
                                $precioNuevo = round($precioRealMasBajo, 3);
                            } else {
                                $precioNuevo = $precioRealMasBajo;
                            }
                            
                            // Actualizar el precio del producto solo si es diferente
                            if ($producto->precio != $precioNuevo) {
                                $producto->precio = $precioNuevo;
                                $producto->save();
                            }
                        }
                    }
                } else {
                    // Si no hay oferta más barata, eliminar el registro si existe
                    ProductoOfertaMasBarataPorProducto::where('producto_id', $producto->id)->delete();
                    // No actualizar el precio del producto (mantener el precio actual)
                }
            }
        } catch (\Exception $e) {
            // Log del error pero no interrumpir el flujo
            \Log::error('Error al recalcular oferta más barata después de actualizar oferta: ' . $e->getMessage());
        }

        // Registrar actividad de usuario
        if (auth()->check()) {
            \App\Models\UserActivity::create([
                'user_id' => auth()->id(),
                'action_type' => \App\Models\UserActivity::ACTION_OFERTA_MODIFICADA,
                'oferta_id' => $oferta->id,
            ]);
        }

        return redirect()->route('admin.ofertas.todas')->with('success', 'Oferta actualizada');
    }

    /**
     * Actualizar solo el campo mostrar de una oferta
     * Opcionalmente también puede actualizar precio_total y precio_unidad
     */
    public function actualizarMostrar(Request $request, OfertaProducto $oferta)
    {
        $validated = $request->validate([
            'mostrar' => 'required|in:si,no',
            'precio_total' => 'nullable|numeric|min:0',
            'precio_unidad' => 'nullable|numeric|min:0'
        ]);

        // Guardar precio anterior antes de actualizar
        $precioAnteriorOferta = $oferta->precio_total;
        $precioNuevo = isset($validated['precio_total']) ? (float)$validated['precio_total'] : null;

        // Si se recibe precio_total pero no precio_unidad, calcularlo usando el servicio
        if (isset($validated['precio_total']) && !isset($validated['precio_unidad'])) {
            $oferta->load('producto');
            $producto = $oferta->producto;
            
            if ($producto) {
                $calcularPrecioUnidad = new CalcularPrecioUnidad();
                $precioUnidad = $calcularPrecioUnidad->calcular(
                    $producto->unidadDeMedida ?? 'unidad',
                    $validated['precio_total'],
                    $oferta->unidades
                );
                
                if ($precioUnidad !== null) {
                    $validated['precio_unidad'] = $precioUnidad;
                }
            }
        }

        $oferta->update($validated);
        
        // Recargar la oferta para obtener los valores actualizados
        $oferta->refresh();

        // Si el precio total ha cambiado y la oferta no tiene chollo_id, registrar o actualizar en el historial
        if ($precioNuevo !== null && abs($precioAnteriorOferta - $precioNuevo) > 0.01 && !$oferta->chollo_id) {
            $serviceTiempos = new \App\Services\TiemposActualizacionOfertasDinamicos();
            $serviceTiempos->registrarOActualizarActualizacion($oferta->id, $precioNuevo, 'manual');
        }

        // Recalcular la oferta más barata del producto y actualizar precio del producto
        try {
            $producto = Producto::find($oferta->producto_id);
            
            if ($producto) {
                $servicioOfertas = new SacarPrimeraOfertaDeUnProductoAplicadoDescuentosYChollos();
                $ofertaMasBarata = $servicioOfertas->obtener($producto);

                if ($ofertaMasBarata) {
                    // Obtener la oferta original de la base de datos para guardar los datos originales
                    $ofertaOriginal = OfertaProducto::find($ofertaMasBarata->id);

                    if ($ofertaOriginal) {
                        // Actualizar o crear el registro en la tabla producto_oferta_mas_barata_por_producto
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

                        // Actualizar el precio del producto con el precio_unidad de la oferta más barata
                        $precioRealMasBajo = $ofertaMasBarata->precio_unidad;
                        
                        // Validar que el precio es válido (mayor que 0)
                        if ($precioRealMasBajo > 0) {
                            // Si el producto tiene unidadDeMedida = unidadMilesima, redondear a 3 decimales
                            if ($producto->unidadDeMedida === 'unidadMilesima') {
                                $precioNuevo = round($precioRealMasBajo, 3);
                            } else {
                                $precioNuevo = $precioRealMasBajo;
                            }
                            
                            // Actualizar el precio del producto solo si es diferente
                            if ($producto->precio != $precioNuevo) {
                                $producto->precio = $precioNuevo;
                                $producto->save();
                            }
                        }
                    }
                } else {
                    // Si no hay oferta más barata, eliminar el registro si existe
                    ProductoOfertaMasBarataPorProducto::where('producto_id', $producto->id)->delete();
                    // No actualizar el precio del producto (mantener el precio actual)
                }
            }
        } catch (\Exception $e) {
            // Log del error pero no interrumpir el flujo
            \Log::error('Error al recalcular oferta más barata después de actualizar mostrar: ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'precio_unidad' => $oferta->precio_unidad // Devolver el precio_unidad calculado
        ]);
    }

    /**
     * Obtener historial de tiempos de actualización de precios de una oferta
     */
    public function historialTiemposActualizacion(OfertaProducto $oferta, Request $request)
    {
        $dias = (int) $request->input('dias', 30);
        
        // Validar que los días sean válidos
        if (!in_array($dias, [30, 90, 180])) {
            $dias = 30;
        }
        
        $fechaInicio = Carbon::now()->subDays($dias);
        
        $historial = HistoricoTiempoActualizacionPrecioOferta::where('oferta_id', $oferta->id)
            ->where('created_at', '>=', $fechaInicio)
            ->orderBy('created_at', 'desc')
            ->get();
        
        return response()->json([
            'success' => true,
            'historial' => $historial->map(function ($registro) {
                return [
                    'id' => $registro->id,
                    'precio_total' => number_format($registro->precio_total, 2, ',', '.'),
                    'tipo_actualizacion' => $registro->tipo_actualizacion,
                    'frecuencia_aplicada_minutos' => $registro->frecuencia_aplicada_minutos,
                    'frecuencia_calculada_minutos' => $registro->frecuencia_calculada_minutos,
                    'created_at' => $registro->created_at->format('d/m/Y H:i:s'),
                    'created_at_timestamp' => $registro->created_at->timestamp,
                ];
            }),
            'total' => $historial->count()
        ]);
    }

    /**
     * Mostrar historial global de tiempos de actualización de precios
     */
    public function historialTiemposActualizacionGlobal(Request $request)
    {
        $perPage = $request->input('per_page', 50);
        
        $historial = HistoricoTiempoActualizacionPrecioOferta::with(['oferta.producto.categoria'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
        
        return view('admin.ofertas.historico_tiempos_de_actualizacion_precios_ofertas', [
            'historial' => $historial
        ]);
    }

    // MÉTODO DESTROY COMENTADO POR SEGURIDAD - No se permite eliminar ofertas desde el panel
    /*
    public function destroy(OfertaProducto $oferta)
    {
        $productoId = $oferta->producto_id;
        $oferta->delete();

        return redirect()->route('admin.ofertas.todas')->with('success', 'Oferta eliminada');
    }
    */

    //Nueva oferta pasandole producto al formulario
    public function create(Producto $producto)
    {
        $oferta = null;
        return view('admin.ofertas.formulario', compact('producto', 'oferta'));
    }

    //Nueva oferta sin pasarle producto al formulario
    public function createGeneral(Request $request)
    {
        $producto = null;
        $oferta = null;
        $url = $request->query('url', '');
        return view('admin.ofertas.formulario', compact('producto', 'oferta', 'url'));
    }


    public function store(Request $request)
    {
        // Debug: mostrar los datos recibidos
        \Log::info('Datos recibidos en store:', $request->all());
        
        // Reemplazar comas por puntos en los campos numéricos antes de validar
        $request->merge([
            'precio_total' => str_replace(',', '.', $request->precio_total),
            'precio_unidad' => str_replace(',', '.', $request->precio_unidad),
            'envio' => $request->filled('envio') ? str_replace(',', '.', $request->envio) : null,
        ]);

        try {
            $validated = $request->validate([
                'producto_id' => 'required|exists:productos,id',
                'tienda_id' => 'required|exists:tiendas,id',
                'unidades' => 'required|numeric|min:0.01',
                'precio_total' => 'required|numeric|min:0',
                'precio_unidad' => 'required|numeric|min:0',
                'envio' => 'nullable|numeric|min:0|max:99.99',
                'url' => 'required|url',
                'variante' => 'nullable|string|max:255',
                'descuentos' => 'nullable|string',
                'cupon_cantidad' => 'nullable|numeric|min:0',
                'mostrar' => 'required|in:si,no',
                'como_scrapear' => 'required|in:automatico,manual',
                'anotaciones_internas' => 'nullable|string',
                'especificaciones_internas' => 'nullable|string',
                'aviso' => 'nullable|date',
                'fecha_actualizacion_manual' => 'nullable|date',
                'es_chollo' => 'nullable|boolean',
                'chollo_id' => 'nullable|exists:chollos,id',
                'fecha_inicio' => 'nullable|date',
                'fecha_final' => 'nullable|date',
                'comprobada' => 'nullable|date',
                'frecuencia_chollo_valor' => 'nullable|numeric|min:0.1',
                'frecuencia_chollo_unidad' => 'nullable|in:minutos,horas,dias',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Error de validación:', $e->errors());
            throw $e;
        }

        $esOfertaChollo = $request->boolean('es_chollo') || $request->filled('chollo_id');

        if ($esOfertaChollo && !$request->filled('chollo_id')) {
            throw ValidationException::withMessages([
                'chollo_id' => 'Debes seleccionar un chollo para vincular esta oferta.',
            ]);
        }

        if ($esOfertaChollo && !$request->filled('fecha_inicio')) {
            throw ValidationException::withMessages([
                'fecha_inicio' => 'Debes indicar la fecha de inicio del chollo.',
            ]);
        }

        $frecuenciaCholloMin = $esOfertaChollo
            ? $this->calcularFrecuenciaCholloEnMinutos(
                $request->input('frecuencia_chollo_valor'),
                $request->input('frecuencia_chollo_unidad')
            )
            : null;

        if ($esOfertaChollo && $frecuenciaCholloMin === null) {
            $frecuenciaCholloMin = 1440;
        }

        $valor = (float) $request->input('frecuencia_valor');
        $unidad = $request->input('frecuencia_unidad');

        $minutos = match ($unidad) {
            'minutos' => $valor,
            'horas' => $valor * 60,
            'dias' => $valor * 1440,
            default => 1440,
        };

        $avisoFecha = null;
        if ($request->filled('aviso')) {
            $input = $request->input('aviso');
            if (preg_match('/^(\d+)\s*(día|dias|días|semana|semanas|mes|meses)$/i', $input, $matches)) {
                $cantidad = (int) $matches[1];
                $unidad = strtolower($matches[2]);

                $unidadMap = [
                    'día' => 'days',
                    'dias' => 'days',
                    'días' => 'days',
                    'semana' => 'weeks',
                    'semanas' => 'weeks',
                    'mes' => 'months',
                    'meses' => 'months',
                ];

                if (isset($unidadMap[$unidad])) {
                    $avisoFecha = now()->add($unidadMap[$unidad], $cantidad)->setTime(0, 1);
                }
            }
        }

        // Procesar descuentos para cupones
        $descuentos = isset($validated['descuentos']) ? trim($validated['descuentos']) : '';

        if ($descuentos === '2a al 50% - cheque - Solo Carrefour') {
            $descuentos = '2a al 50 - cheque - SoloCarrefour';
        }
        
        // Si el campo descuentos ya viene con formato construido (cupon;codigo;cantidad o cupon;cantidad), no modificarlo
        // Solo construir el formato si viene como 'cupon' sin formato (fallback por si el JavaScript no funciona)
        if ($descuentos === 'cupon') {
            $codigoCupon = $request->input('cupon_codigo');
            $cantidadCupon = $request->input('cupon_cantidad');
            
            if ($codigoCupon && $cantidadCupon) {
                // Formato nuevo: cupon;codigo;cantidad
                $descuentos = 'cupon;' . trim($codigoCupon) . ';' . $cantidadCupon;
            } elseif ($cantidadCupon) {
                // Formato antiguo: cupon;cantidad (solo cantidad, sin código)
                $descuentos = 'cupon;' . $cantidadCupon;
            }
        }
        // Si ya viene con formato cupon;codigo;cantidad o cupon;cantidad, no modificarlo

        // Crear la oferta
        $datosBase = Arr::except($validated, [
            'es_chollo',
            'frecuencia_chollo_valor',
            'frecuencia_chollo_unidad',
        ]);

        // Si el precio final es 0, forzar no mostrar (antes de crear)
        $precioCero = $this->esPrecioCero($request->input('precio_total')) || $this->esPrecioCero($request->input('precio_unidad'));
        if ($precioCero) {
            $datosBase['mostrar'] = 'no';
        }

        $datosBase['chollo_id'] = $esOfertaChollo ? $request->input('chollo_id') : null;
        $datosBase['fecha_inicio'] = $esOfertaChollo ? $this->parseNullableDateTime($request->input('fecha_inicio')) : null;
        $datosBase['fecha_final'] = $esOfertaChollo ? $this->parseNullableDateTime($request->input('fecha_final')) : null;
        $datosBase['comprobada'] = $esOfertaChollo ? $this->parseNullableDateTime($request->input('comprobada')) : null;
        $datosBase['frecuencia_comprobacion_chollos_min'] = $esOfertaChollo ? $frecuenciaCholloMin : null;

        // Procesar especificaciones internas
        $especificacionesInternas = null;
        if ($request->has('especificaciones_internas') && !empty($request->especificaciones_internas)) {
            $especificacionesInternas = json_decode($request->especificaciones_internas, true);
        }

        // Si se proporciona envio, establecer fecha_actualizacion_envio con la fecha actual
        $datosCrear = [
            'frecuencia_actualizar_precio_minutos' => round($minutos),
            'descuentos' => $descuentos,
            'aviso' => $avisoFecha,
            'especificaciones_internas' => $especificacionesInternas,
        ];
        
        // Al crear una oferta nueva, siempre establecer fecha_actualizacion_envio
        $datosCrear['fecha_actualizacion_envio'] = now();

        $oferta = OfertaProducto::create(array_merge($datosBase, $datosCrear));

        // Si se proporcionó una fecha de actualización manual, establecerla
        if ($request->filled('fecha_actualizacion_manual')) {
            $oferta->timestamps = false;
            $oferta->updated_at = \Carbon\Carbon::parse($request->input('fecha_actualizacion_manual'));
            $oferta->save();
            $oferta->timestamps = true;
        }

        // Si el precio final es 0, crear aviso de "Sin stock - 1a vez" a 4 días
        if ($precioCero) {
            $this->crearAvisoSinStockSiNoExiste($oferta);
        }

        // Recalcular la oferta más barata del producto y actualizar precio del producto
        try {
            $producto = Producto::find($oferta->producto_id);
            
            if ($producto) {
                $servicioOfertas = new SacarPrimeraOfertaDeUnProductoAplicadoDescuentosYChollos();
                $ofertaMasBarata = $servicioOfertas->obtener($producto);

                if ($ofertaMasBarata) {
                    // Obtener la oferta original de la base de datos para guardar los datos originales
                    $ofertaOriginal = OfertaProducto::find($ofertaMasBarata->id);

                    if ($ofertaOriginal) {
                        // Actualizar o crear el registro en la tabla producto_oferta_mas_barata_por_producto
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

                        // Actualizar el precio del producto con el precio_unidad de la oferta más barata
                        $precioRealMasBajo = $ofertaMasBarata->precio_unidad;
                        
                        // Validar que el precio es válido (mayor que 0)
                        if ($precioRealMasBajo > 0) {
                            // Si el producto tiene unidadDeMedida = unidadMilesima, redondear a 3 decimales
                            if ($producto->unidadDeMedida === 'unidadMilesima') {
                                $precioNuevo = round($precioRealMasBajo, 3);
                            } else {
                                $precioNuevo = $precioRealMasBajo;
                            }
                            
                            // Actualizar el precio del producto solo si es diferente
                            if ($producto->precio != $precioNuevo) {
                                $producto->precio = $precioNuevo;
                                $producto->save();
                            }
                        }
                    }
                } else {
                    // Si no hay oferta más barata, eliminar el registro si existe
                    ProductoOfertaMasBarataPorProducto::where('producto_id', $producto->id)->delete();
                    // No actualizar el precio del producto (mantener el precio actual)
                }
            }
        } catch (\Exception $e) {
            // Log del error pero no interrumpir el flujo
            \Log::error('Error al recalcular oferta más barata después de crear oferta: ' . $e->getMessage());
        }

        // Registrar actividad de usuario
        if (auth()->check()) {
            \App\Models\UserActivity::create([
                'user_id' => auth()->id(),
                'action_type' => \App\Models\UserActivity::ACTION_OFERTA_CREADA,
                'oferta_id' => $oferta->id,
            ]);
        }

        return redirect()->route('admin.ofertas.todas')->with('success', 'Oferta añadida correctamente');
    }

    /**
     * Obtener ofertas de un producto (para gestión de grupos)
     */
    public function obtenerOfertasPorProducto($productoId)
    {
        $ofertas = OfertaProducto::where('producto_id', $productoId)
            ->with(['tienda'])
            ->get(['id', 'producto_id', 'tienda_id', 'precio_unidad', 'url']);
        
        return response()->json([
            'ofertas' => $ofertas
        ]);
    }

    /**
     * Verificar si existe una oferta con chollo para el mismo producto y tienda
     */
    public function verificarOfertaCholloExistente(Request $request)
    {
        $productoId = $request->input('producto_id');
        $tiendaId = $request->input('tienda_id');
        $ofertaId = $request->input('oferta_id'); // ID de la oferta actual (si estamos editando)

        if (!$productoId || !$tiendaId) {
            return response()->json([
                'existe' => false
            ]);
        }

        // Buscar ofertas con chollo para el mismo producto y tienda
        $query = OfertaProducto::where('producto_id', $productoId)
            ->where('tienda_id', $tiendaId)
            ->whereNotNull('chollo_id');

        // Si estamos editando, excluir la oferta actual
        if ($ofertaId) {
            $query->where('id', '!=', $ofertaId);
        }

        $ofertaExistente = $query->with(['chollo', 'tienda'])
            ->first();

        if ($ofertaExistente) {
            return response()->json([
                'existe' => true,
                'oferta' => [
                    'id' => $ofertaExistente->id,
                    'url' => $ofertaExistente->url,
                    'precio_unidad' => $ofertaExistente->precio_unidad,
                    'chollo' => $ofertaExistente->chollo ? [
                        'id' => $ofertaExistente->chollo->id,
                        'titulo' => $ofertaExistente->chollo->titulo,
                    ] : null,
                    'tienda' => $ofertaExistente->tienda ? [
                        'id' => $ofertaExistente->tienda->id,
                        'nombre' => $ofertaExistente->tienda->nombre,
                    ] : null,
                ]
            ]);
        }

        return response()->json([
            'existe' => false
        ]);
    }

    //Mostrar el hitorial del precio de cada oferta
    public function estadisticas(OfertaProducto $oferta)
    {
        $oferta->load(['producto', 'tienda']); // carga relación para acceder a nombre
        return view('admin.ofertas.estadisticas', compact('oferta'));
    }

    public function estadisticasDatos(OfertaProducto $oferta, Request $request)
    {
        $dias = (int) $request->query('dias', 90);
        $desde = Carbon::today()->subDays($dias - 1); // incluye hoy

        // Obtiene todos los registros del histórico de la oferta desde la fecha indicada
        $historicoOferta = HistoricoPrecioOferta::where('oferta_producto_id', $oferta->id)
            ->where('fecha', '>=', $desde)
            ->orderBy('fecha')
            ->get()
            ->keyBy('fecha');

        // Obtiene todos los registros del histórico del producto desde la fecha indicada
        $producto = $oferta->producto;
        $historicoProducto = \App\Models\HistoricoPrecioProducto::where('producto_id', $producto->id)
            ->where('fecha', '>=', $desde)
            ->orderBy('fecha')
            ->get()
            ->keyBy('fecha');

        // Obtener el rango de precios de las 5 ofertas más baratas del producto
        $servicioOfertas = new \App\Services\SacarPrimeraOfertaDeUnProductoAplicadoDescuentosYChollos();
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
        $valoresOferta = [];
        $valoresProducto = [];

        for ($i = 0; $i < $dias; $i++) {
            $fecha = Carbon::today()->subDays($dias - 1 - $i)->toDateString();
            $labels[] = Carbon::parse($fecha)->format('d/m');
            $valoresOferta[] = isset($historicoOferta[$fecha]) ? (float) $historicoOferta[$fecha]->precio_unidad : null;
            $valoresProducto[] = isset($historicoProducto[$fecha]) ? (float) $historicoProducto[$fecha]->precio_minimo : null;
        }

        return response()->json([
            'labels' => $labels,
            'valores' => $valoresOferta,
            'valores_producto' => $valoresProducto,
            'rango_5_ofertas' => $rangoPrecio5Ofertas,
        ]);
    }

    /**
     * Obtener información adicional para las estadísticas de una oferta
     * - Rango de precio_unidad de las 5 ofertas más baratas del producto (precio de la 1 y la 5)
     * - Máximo y mínimo del historial de precios del producto en el rango de días
     */
    public function estadisticasInfo(OfertaProducto $oferta, Request $request)
    {
        try {
            $dias = (int) $request->query('dias', 90);
            $desde = Carbon::today()->subDays($dias - 1); // incluye hoy
            
            $producto = $oferta->producto;
            
            if (!$producto) {
                return response()->json([
                    'error' => 'Producto no encontrado',
                    'rango_precio_unidad' => ['min' => null, 'max' => null],
                    'rango_historico' => ['min' => null, 'max' => null],
                ], 404);
            }
            
            // Obtener todas las ofertas del producto usando el servicio (ya vienen ordenadas por precio_unidad)
            $servicioOfertas = new \App\Services\SacarPrimeraOfertaDeUnProductoAplicadoDescuentosYChollos();
            $ofertasProducto = $servicioOfertas->obtenerTodas($producto);
            
            // Obtener las 5 primeras ofertas (las más baratas)
            $primeras5Ofertas = $ofertasProducto->take(5);
            
            $rangoPrecioUnidad = [
                'min' => null,
                'max' => null,
            ];
            
            if ($primeras5Ofertas->count() > 0) {
                // Precio de la primera oferta (más barata)
                $precioPrimera = $primeras5Ofertas->first()->precio_unidad ?? null;
                
                // Precio de la quinta oferta (o la última si hay menos de 5)
                $precioQuinta = $primeras5Ofertas->last()->precio_unidad ?? null;
                
                $rangoPrecioUnidad = [
                    'min' => $precioPrimera !== null ? (float) $precioPrimera : null,
                    'max' => $precioQuinta !== null ? (float) $precioQuinta : null,
                ];
            }
            
            // Obtener máximo y mínimo del historial de precios del producto
            $historicoProducto = \App\Models\HistoricoPrecioProducto::where('producto_id', $producto->id)
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
            \Log::error('Error en estadisticasInfo: ' . $e->getMessage(), [
                'oferta_id' => $oferta->id,
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
     * Actualizar oferta para ocultarla y añadir anotación
     */
    public function ocultarOfertaPrecioElevado(OfertaProducto $oferta)
    {
        $anotacionActual = $oferta->anotaciones_internas ?? '';
        
        // Añadir salto de línea solo si ya hay contenido
        if (!empty(trim($anotacionActual))) {
            $nuevaAnotacion = $anotacionActual . "\nPRECIO MUY ELEVADO CONSTANTE EN EL TIEMPO";
        } else {
            $nuevaAnotacion = "PRECIO MUY ELEVADO CONSTANTE EN EL TIEMPO";
        }
        
        $oferta->update([
            'mostrar' => 'no',
            'anotaciones_internas' => $nuevaAnotacion,
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Oferta ocultada y anotación añadida correctamente'
        ]);
    }

    // GUARDAR HISTORICO PRECIOS OFERTAS
    public function listaOfertas()
    {
        $ofertas = \App\Models\OfertaProducto::select('id', 'producto_id', 'tienda_id', 'precio_unidad')
            ->with(['producto:id,nombre', 'tienda:id,nombre'])
            ->get()
            ->map(function ($oferta) {
                return [
                    'id' => $oferta->id,
                    'nombre' => $oferta->producto->nombre . ' - ' . $oferta->tienda->nombre,
                    'precio' => $oferta->precio_unidad,
                ];
            });

        return response()->json(['productos' => $ofertas]);
    }
    public function procesarOferta(Request $request)
    {
        $id = $request->input('id');
        $forzar = $request->input('forzar', false);

        $oferta = \App\Models\OfertaProducto::with(['producto', 'tienda'])->find($id);

        if (!$oferta) {
            return response()->json([
                'status' => 'error',
                'oferta_id' => $id,
                'error' => 'Oferta no encontrada',
            ]);
        }

        $fechaHoy = \Carbon\Carbon::today()->toDateString();

        $registroExistente = HistoricoPrecioOferta::where('oferta_producto_id', $oferta->id)
            ->where('fecha', $fechaHoy)
            ->first();

        if ($registroExistente && !$forzar) {
            return response()->json([
                'status' => 'existe',
                'oferta_id' => $oferta->id,
                'nombre' => $oferta->producto->nombre . ' - ' . $oferta->tienda->nombre,
            ]);
        }

        try {
            \App\Models\HistoricoPrecioOferta::updateOrCreate(
                ['oferta_producto_id' => $oferta->id, 'fecha' => $fechaHoy],
                ['precio_unidad' => $oferta->precio_unidad]
            );

            return response()->json([
                'status' => $registroExistente ? 'actualizado' : 'guardado',
                'oferta_id' => $oferta->id,
                'nombre' => $oferta->producto->nombre . ' - ' . $oferta->tienda->nombre,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'oferta_id' => $oferta->id,
                'nombre' => $oferta->producto->nombre . ' - ' . $oferta->tienda->nombre,
                'error' => $e->getMessage(),
            ]);
        }
    }
    public function finalizarEjecucion(Request $request)
    {
        \App\Models\EjecucionGlobal::create([
            'inicio' => now(),
            'fin' => now(),
            'nombre' => 'ejecuciones_historico_precios_ofertas',
            'total' => $request->input('total', 0),
            'total_guardado' => $request->input('correctos', 0),
            'total_errores' => $request->input('errores', 0),
            'log' => $request->input('log', []),
        ]);

        return response()->json(['success' => true]);
    }
    public function indexEjecucionesHistorico(Request $request)
    {
        $busqueda = $request->input('buscar');

        $query = \App\Models\EjecucionGlobal::where('nombre', 'ejecuciones_historico_precios_ofertas');

        if ($busqueda) {
            $query->where(function($q) use ($busqueda) {
                $q->whereDate('inicio', 'like', "%$busqueda%")
                  ->orWhereDate('fin', 'like', "%$busqueda%");
            });
        }

        $ejecuciones = $query->orderByDesc('inicio')->paginate(15)->withQueryString();
        $totalEjecuciones = \App\Models\EjecucionGlobal::where('nombre', 'ejecuciones_historico_precios_ofertas')->count();

        return view('admin.ofertas.listadoEjecucionesGuardadoPrecios', compact('ejecuciones', 'busqueda', 'totalEjecuciones'));
    }
    public function eliminarAntiguas(Request $request)
    {
        $cantidad = $request->input('cantidad');
        \App\Models\EjecucionGlobal::where('nombre', 'ejecuciones_historico_precios_ofertas')
            ->orderBy('inicio')
            ->limit($cantidad)
            ->delete();

        return redirect()->back()->with('success', "$cantidad ejecuciones eliminadas.");
    }
    public function eliminar($id)
    {
        $ejecucion = \App\Models\EjecucionGlobal::where('nombre', 'ejecuciones_historico_precios_ofertas')
            ->findOrFail($id);
        $ejecucion->delete();
        return redirect()->back()->with('success', 'Ejecución eliminada.');
    }

    public function segundoPlanoGuardarPrecioHistoricoHoy(Request $request)
    {
        if ($request->query('token') !== env('TOKEN_ACTUALIZAR_PRECIOS')) {
            abort(403, 'Token inválido');
        }

        $ejecucion = \App\Models\EjecucionGlobal::create([
            'inicio' => now(),
            'nombre' => 'ejecuciones_historico_precios_ofertas',
            'log' => [],
        ]);

        $ofertas = \App\Models\OfertaProducto::with(['producto', 'tienda'])->get();
        $guardados = 0;
        $errores = 0;
        $log = [];

        foreach ($ofertas as $oferta) {
            try {
                $precio = $oferta->precio_unidad ?? rand(10, 100);
                DB::table('historico_precios_ofertas')->updateOrInsert(
                    [
                        'oferta_producto_id' => $oferta->id,
                        'fecha' => now()->toDateString(),
                    ],
                    [
                        'precio_unidad' => $precio,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );

                $guardados++;
            } catch (\Throwable $e) {
                $errores++;
                $log[] = [
                    'oferta_id' => $oferta->id,
                    'nombre' => $oferta->producto->nombre . ' - ' . $oferta->tienda->nombre,
                    'error' => $e->getMessage(),
                ];
            }
        }

        $ejecucion->update([
            'fin' => now(),
            'total' => count($ofertas),
            'total_guardado' => $guardados,
            'total_errores' => $errores,
            'log' => $log,
        ]);

        return response()->json([
            'status' => 'ok',
            'guardados' => $guardados,
            'errores' => $errores,
        ]);
    }

    // MODIFICAR PRECIOS HISTORICO OFERTAS

    // MODIFICAR HISTORIAL DE PRECIO DE OFERTAS
    public function historialMes(Request $request, OfertaProducto $oferta)
    {
        $mes = (int) $request->query('mes');
        $anio = (int) $request->query('anio');

        $desde = Carbon::createFromDate($anio, $mes, 1);
        $hasta = $desde->copy()->endOfMonth();

        $registros = HistoricoPrecioOferta::where('oferta_producto_id', $oferta->id)
            ->whereBetween('fecha', [$desde->toDateString(), $hasta->toDateString()])
            ->get();

        $resultado = [];
        foreach ($registros as $registro) {
            $fechaFormateada = Carbon::parse($registro['fecha'])->format('Y-m-d');
            $resultado[$fechaFormateada] = (float) $registro['precio_unidad']; // ← CAMBIO AQUÍ
        }

        return response()->json($resultado);
    }

    public function historialGuardar(Request $request, OfertaProducto $oferta)
    {
        $cambios = $request->input('cambios', []);

        foreach ($cambios as $fecha => $precio) {
            if ($precio === null || $precio === '') continue;

            HistoricoPrecioOferta::updateOrCreate(
                ['oferta_producto_id' => $oferta->id, 'fecha' => $fecha],
                ['precio_unidad' => $precio]
            );
        }

        return response()->json(['success' => true]);
    }

    // ==========================================
    // MÉTODOS PARA SCRAPING DE OFERTAS
    // ==========================================



    /**
     * Ejecutar scraping de ofertas en segundo plano (para cron jobs)
     */
    public function ejecutarScraperOfertasSegundoPlano(Request $request)
    {
        // Usar el nuevo controlador de scraping en segundo plano
        $scraperController = new \App\Http\Controllers\Scraping\ScraperSegundoPlanoController();
        return $scraperController->ejecutarScraperOfertasSegundoPlano($request);
    }

    /**
     * Ejecutar scraping de ofertas de una tienda específica
     */
    public function ejecutarScraperOfertasTienda(Request $request)
    {
        // Usar el nuevo controlador de scraping en segundo plano
        $scraperController = new \App\Http\Controllers\Scraping\ScraperSegundoPlanoController();
        return $scraperController->ejecutarScraperOfertasTienda($request);
    }

    /**
     * Vista para listar ejecuciones de scraping
     */
    public function indexEjecucionesScraper(Request $request)
    {
        // Filtros de fecha
        $filtroRapido = $request->input('filtro_rapido', 'hoy');
        $fechaDesde = $request->input('fecha_desde');
        $fechaHasta = $request->input('fecha_hasta');
        $horaDesde = $request->input('hora_desde');
        $horaHasta = $request->input('hora_hasta');
        $busqueda = $request->input('buscar');

        // Aplicar filtros de fecha según el filtro rápido
        // Combinar ambas ejecuciones: ejecuciones_scrapear_ofertas y actualizar_primera_oferta
        $query = \App\Models\EjecucionGlobal::where(function($q) {
            $q->where('nombre', 'ejecuciones_scrapear_ofertas')
              ->orWhere('nombre', 'actualizar_primera_oferta');
        });
        
        if ($filtroRapido && $filtroRapido !== 'siempre') {
            $hoy = now();
            
            switch($filtroRapido) {
                case 'hoy':
                    $fechaDesde = $fechaHasta = $hoy->format('Y-m-d');
                    $query->whereDate('inicio', $hoy->toDateString());
                    break;
                case 'ayer':
                    $ayer = $hoy->copy()->subDay();
                    $fechaDesde = $fechaHasta = $ayer->format('Y-m-d');
                    $query->whereDate('inicio', $ayer->toDateString());
                    break;
                case '7dias':
                    $fechaDesde = $hoy->copy()->subDays(7)->format('Y-m-d');
                    $fechaHasta = $hoy->format('Y-m-d');
                    $query->where('inicio', '>=', $hoy->copy()->subDays(7));
                    break;
                case '30dias':
                    $fechaDesde = $hoy->copy()->subDays(30)->format('Y-m-d');
                    $fechaHasta = $hoy->format('Y-m-d');
                    $query->where('inicio', '>=', $hoy->copy()->subDays(30));
                    break;
                case '90dias':
                    $fechaDesde = $hoy->copy()->subDays(90)->format('Y-m-d');
                    $fechaHasta = $hoy->format('Y-m-d');
                    $query->where('inicio', '>=', $hoy->copy()->subDays(90));
                    break;
                case '180dias':
                    $fechaDesde = $hoy->copy()->subDays(180)->format('Y-m-d');
                    $fechaHasta = $hoy->format('Y-m-d');
                    $query->where('inicio', '>=', $hoy->copy()->subDays(180));
                    break;
                case '1año':
                    $fechaDesde = $hoy->copy()->subYear()->format('Y-m-d');
                    $fechaHasta = $hoy->format('Y-m-d');
                    $query->where('inicio', '>=', $hoy->copy()->subYear());
                    break;
            }
        } else {
            // Filtros manuales de fecha
            if ($fechaDesde) {
                $query->whereDate('inicio', '>=', $fechaDesde);
            }
            if ($fechaHasta) {
                $query->whereDate('inicio', '<=', $fechaHasta);
            }
        }

        // Filtros de hora
        if ($horaDesde) {
            $query->whereTime('inicio', '>=', $horaDesde);
        }
        if ($horaHasta) {
            $query->whereTime('inicio', '<=', $horaHasta);
        }

        // Búsqueda por texto
        if ($busqueda) {
            $query->where(function($q) use ($busqueda) {
                $q->whereDate('inicio', 'like', "%$busqueda%")
                  ->orWhereDate('fin', 'like', "%$busqueda%");
            });
        }

        // Calcular estadísticas generales y datos para gráficos (sin paginación)
        $estadisticasQuery = clone $query;
        $totalEjecuciones = $estadisticasQuery->count();
        $totalOfertas = $estadisticasQuery->sum('total');
        $totalActualizadas = $estadisticasQuery->sum('total_guardado');
        $totalErrores = $estadisticasQuery->sum('total_errores');

        // Calcular errores por tienda (sin verificar URLs resueltas para optimizar)
        $erroresPorTienda = [];
        $estadisticas = $estadisticasQuery->get();
        
        foreach ($estadisticas as $ejecucion) {
            $log = $ejecucion->log ?? [];
            $resultados = $log['resultados'] ?? [];
            
            foreach ($resultados as $resultado) {
                if (isset($resultado['success']) && !$resultado['success']) {
                    $tiendaNombre = $resultado['tienda_nombre'] ?? 'Tienda Desconocida';
                    
                    if (!isset($erroresPorTienda[$tiendaNombre])) {
                        $erroresPorTienda[$tiendaNombre] = 0;
                    }
                    $erroresPorTienda[$tiendaNombre]++;
                }
            }
        }
        
        // Ordenar por número de errores (descendente)
        arsort($erroresPorTienda);
        
        // Obtener información de las tiendas para mostrar avisos de coste
        $tiendasConApi = \App\Models\Tienda::whereIn('nombre', array_keys($erroresPorTienda))
            ->pluck('api', 'nombre')
            ->toArray();

        // Datos para gráficos
        $datosPorHora = [];
        $datosPorDia = [];
        $datosPorHoraPorTienda = [];
        $tiendasEnDatos = [];
        
        // Inicializar datos por hora (0-23)
        for ($i = 0; $i < 24; $i++) {
            $datosPorHora[$i] = [
                'total_ofertas' => 0,
                'total_correctas' => 0,
                'total_errores' => 0
            ];
        }
        
        // Si hay fechas de filtro, generar datos para todos los días del rango
        if ($fechaDesde && $fechaHasta) {
            $fechaInicio = \Carbon\Carbon::parse($fechaDesde);
            $fechaFin = \Carbon\Carbon::parse($fechaHasta);
            
            // Generar array con todos los días del rango
            for ($fecha = $fechaInicio->copy(); $fecha->lte($fechaFin); $fecha->addDay()) {
                $diaKey = $fecha->format('Y-m-d');
                $datosPorDia[$diaKey] = [
                    'total_ofertas' => 0,
                    'total_correctas' => 0,
                    'total_errores' => 0
                ];
            }
        }
        
        $estadisticas = $estadisticasQuery->get();
        
        foreach ($estadisticas as $ejecucion) {
            $hora = (int)$ejecucion->inicio->format('H'); // Convertir a entero para usar como índice numérico
            $dia = $ejecucion->inicio->format('Y-m-d');
            
            // Asegurar que existe la hora en el array
            if (!isset($datosPorHora[$hora])) {
                $datosPorHora[$hora] = [
                    'total_ofertas' => 0,
                    'total_correctas' => 0,
                    'total_errores' => 0
                ];
            }
            
            // Asegurar que existe el día en el array
            if (!isset($datosPorDia[$dia])) {
                $datosPorDia[$dia] = [
                    'total_ofertas' => 0,
                    'total_correctas' => 0,
                    'total_errores' => 0
                ];
            }
            
            $datosPorHora[$hora]['total_ofertas'] += $ejecucion->total ?? 0;
            $datosPorHora[$hora]['total_correctas'] += $ejecucion->total_guardado ?? 0;
            $datosPorHora[$hora]['total_errores'] += $ejecucion->total_errores ?? 0;
            
            $datosPorDia[$dia]['total_ofertas'] += $ejecucion->total ?? 0;
            $datosPorDia[$dia]['total_correctas'] += $ejecucion->total_guardado ?? 0;
            $datosPorDia[$dia]['total_errores'] += $ejecucion->total_errores ?? 0;
            
            // Procesar datos por tienda para el gráfico por horas
            $log = $ejecucion->log ?? [];
            $resultados = $log['resultados'] ?? [];
            
            foreach ($resultados as $resultado) {
                $tiendaNombre = $resultado['tienda_nombre'] ?? 'Tienda Desconocida';
                
                // Agregar tienda a la lista de tiendas encontradas
                if (!in_array($tiendaNombre, $tiendasEnDatos)) {
                    $tiendasEnDatos[] = $tiendaNombre;
                }
                
                // Inicializar datos por tienda si no existen
                if (!isset($datosPorHoraPorTienda[$tiendaNombre])) {
                    $datosPorHoraPorTienda[$tiendaNombre] = [];
                    for ($i = 0; $i < 24; $i++) {
                        $datosPorHoraPorTienda[$tiendaNombre][$i] = [
                            'total_ofertas' => 0,
                            'total_correctas' => 0,
                            'total_errores' => 0
                        ];
                    }
                }
                
                // Sumar datos por tienda y hora
                $datosPorHoraPorTienda[$tiendaNombre][$hora]['total_ofertas'] += 1;
                
                if (isset($resultado['success']) && $resultado['success']) {
                    $datosPorHoraPorTienda[$tiendaNombre][$hora]['total_correctas'] += 1;
                } else {
                    $datosPorHoraPorTienda[$tiendaNombre][$hora]['total_errores'] += 1;
                }
            }
        }

        // Obtener ejecuciones paginadas para la lista
        $ejecuciones = $query->orderByDesc('inicio')->paginate(15)->withQueryString();

        // Ordenar datos por hora (0-23) y convertir a arrays para JSON
        ksort($datosPorHora);
        ksort($datosPorDia);
        
        // Convertir a arrays para que se puedan serializar correctamente en JSON
        $datosPorHoraArray = [];
        foreach ($datosPorHora as $hora => $datos) {
            $datosPorHoraArray[$hora] = $datos;
        }
        
        $datosPorDiaArray = [];
        foreach ($datosPorDia as $dia => $datos) {
            $datosPorDiaArray[$dia] = $datos;
        }

        // Calcular estadísticas por tienda (intentos scraper normal + actualizar primera oferta)
        $estadisticasPorTienda = [];
        
        // 1. Obtener todas las tiendas
        $todasLasTiendas = \App\Models\Tienda::all();
        $tiendasConApiMap = [];
        foreach ($todasLasTiendas as $tienda) {
            $tiendasConApiMap[$tienda->nombre] = $tienda->api;
            $estadisticasPorTienda[$tienda->nombre] = [
                'nombre' => $tienda->nombre,
                'api' => $tienda->api,
                'intentos_scraper_normal' => 0,
                'intentos_primera_oferta' => 0,
                'total_intentos' => 0,
                'errores' => 0
            ];
        }
        
        // 2. Contar intentos separados por tipo de ejecución y errores
        foreach ($estadisticas as $ejecucion) {
            $log = $ejecucion->log ?? [];
            $resultados = $log['resultados'] ?? [];
            $esPrimeraOferta = $ejecucion->nombre === 'actualizar_primera_oferta';
            
            foreach ($resultados as $resultado) {
                $tiendaNombre = $resultado['tienda_nombre'] ?? 'Tienda Desconocida';
                if (!isset($estadisticasPorTienda[$tiendaNombre])) {
                    // Si la tienda no está en la lista, la añadimos
                    $estadisticasPorTienda[$tiendaNombre] = [
                        'nombre' => $tiendaNombre,
                        'api' => $tiendasConApiMap[$tiendaNombre] ?? null,
                        'intentos_scraper_normal' => 0,
                        'intentos_primera_oferta' => 0,
                        'total_intentos' => 0,
                        'errores' => 0
                    ];
                }
                
                // Contar intento según tipo
                if ($esPrimeraOferta) {
                    $estadisticasPorTienda[$tiendaNombre]['intentos_primera_oferta']++;
                } else {
                    $estadisticasPorTienda[$tiendaNombre]['intentos_scraper_normal']++;
                }
                
                // Contar errores
                if (isset($resultado['success']) && !$resultado['success']) {
                    $estadisticasPorTienda[$tiendaNombre]['errores']++;
                }
            }
        }
        
        // 4. Calcular totales y porcentajes
        $totalGlobalIntentos = 0;
        foreach ($estadisticasPorTienda as $tiendaNombre => &$stats) {
            $stats['total_intentos'] = $stats['intentos_scraper_normal'] + $stats['intentos_primera_oferta'];
            $totalGlobalIntentos += $stats['total_intentos'];
        }
        unset($stats); // Liberar referencia
        
        // Calcular porcentajes
        foreach ($estadisticasPorTienda as $tiendaNombre => &$stats) {
            $stats['porcentaje_total'] = $totalGlobalIntentos > 0 
                ? ($stats['total_intentos'] / $totalGlobalIntentos) * 100 
                : 0;
        }
        unset($stats); // Liberar referencia
        
        // Ordenar por total de intentos descendente
        uasort($estadisticasPorTienda, function($a, $b) {
            return $b['total_intentos'] <=> $a['total_intentos'];
        });

        // Calcular estadísticas por API basadas en ejecuciones reales del período filtrado
        $estadisticasPorAPI = [];
        $tiendasUsadasPorAPI = [];
        
        // Obtener todas las tiendas con sus APIs
        $todasLasTiendas = \App\Models\Tienda::all();
        $tiendaApiMap = [];
        foreach ($todasLasTiendas as $tienda) {
            $tiendaApiMap[$tienda->nombre] = $tienda->api;
        }
        
        // Contar peticiones por API basándose en las ejecuciones reales
        foreach ($estadisticas as $ejecucion) {
            $log = $ejecucion->log ?? [];
            $resultados = $log['resultados'] ?? [];
            
            foreach ($resultados as $resultado) {
                $tiendaNombre = $resultado['tienda_nombre'] ?? null;
                if (!$tiendaNombre || !isset($tiendaApiMap[$tiendaNombre])) {
                    continue;
                }
                
                $apiCompleta = $tiendaApiMap[$tiendaNombre];
                if (!$apiCompleta) {
                    continue;
                }
                
                // Obtener el nombre base de la API (antes del ';')
                $apiBase = explode(';', $apiCompleta, 2)[0];
                
                if (!isset($estadisticasPorAPI[$apiBase])) {
                    $estadisticasPorAPI[$apiBase] = [
                        'total_peticiones' => 0,
                        'tiendas' => []
                    ];
                }
                
                // Contar la petición
                $estadisticasPorAPI[$apiBase]['total_peticiones']++;
                
                // Agregar tienda única a la lista
                if (!in_array($tiendaNombre, $estadisticasPorAPI[$apiBase]['tiendas'])) {
                    $estadisticasPorAPI[$apiBase]['tiendas'][] = $tiendaNombre;
                }
            }
        }
        
        // Calcular porcentajes
        $totalGlobalPeticiones = array_sum(array_column($estadisticasPorAPI, 'total_peticiones'));
        foreach ($estadisticasPorAPI as $apiBase => &$stats) {
            $stats['porcentaje_total'] = $totalGlobalPeticiones > 0 
                ? round(($stats['total_peticiones'] / $totalGlobalPeticiones) * 100, 2) 
                : 0;
            $stats['cantidad_tiendas'] = count($stats['tiendas']);
        }
        unset($stats); // Liberar referencia
        
        // Ordenar por total de peticiones descendente
        uasort($estadisticasPorAPI, function($a, $b) {
            return $b['total_peticiones'] <=> $a['total_peticiones'];
        });

        // Pasar estadisticasPorTienda para usar los errores, pero no calcular API por defecto
        // estadisticasPorAPI se cargará dinámicamente

        return view('admin.ofertas.listadoEjecucionesScraper', compact(
            'ejecuciones', 
            'busqueda', 
            'totalEjecuciones',
            'filtroRapido',
            'fechaDesde',
            'fechaHasta',
            'horaDesde',
            'horaHasta',
            'totalOfertas',
            'totalActualizadas',
            'totalErrores',
            'erroresPorTienda',
            'tiendasEnDatos',
            'datosPorHoraPorTienda',
            'tiendasConApi',
            'estadisticasPorTienda'
        ))->with([
            'datosPorHora' => $datosPorHoraArray,
            'datosPorDia' => $datosPorDiaArray
        ]);
    }

    /**
     * Obtener detalles de errores por tienda
     */
    public function obtenerErroresPorTienda(Request $request)
    {
        $tienda = $request->input('tienda');
        $filtroRapido = $request->input('filtro_rapido');
        $fechaDesde = $request->input('fecha_desde');
        $fechaHasta = $request->input('fecha_hasta');
        $horaDesde = $request->input('hora_desde');
        $horaHasta = $request->input('hora_hasta');

        // Construir query base (incluir ambas ejecuciones)
        $query = \App\Models\EjecucionGlobal::where(function($q) {
            $q->where('nombre', 'ejecuciones_scrapear_ofertas')
              ->orWhere('nombre', 'actualizar_primera_oferta');
        });
        
        // Aplicar filtros de fecha (priorizar fechas manuales sobre filtro rápido)
        if (($filtroRapido && $filtroRapido !== 'siempre' && $filtroRapido !== '') && !$fechaDesde && !$fechaHasta) {
            // Solo usar filtro rápido si no hay fechas manuales
            $hoy = now();
            
            switch($filtroRapido) {
                case 'hoy':
                    $fechaDesde = $fechaHasta = $hoy->format('Y-m-d');
                    $query->whereDate('inicio', $hoy->toDateString());
                    break;
                case 'ayer':
                    $ayer = $hoy->copy()->subDay();
                    $fechaDesde = $fechaHasta = $ayer->format('Y-m-d');
                    $query->whereDate('inicio', $ayer->toDateString());
                    break;
                case '7dias':
                    $fechaDesde = $hoy->copy()->subDays(7)->format('Y-m-d');
                    $fechaHasta = $hoy->format('Y-m-d');
                    $query->where('inicio', '>=', $hoy->copy()->subDays(7));
                    break;
                case '30dias':
                    $fechaDesde = $hoy->copy()->subDays(30)->format('Y-m-d');
                    $fechaHasta = $hoy->format('Y-m-d');
                    $query->where('inicio', '>=', $hoy->copy()->subDays(30));
                    break;
                case '90dias':
                    $fechaDesde = $hoy->copy()->subDays(90)->format('Y-m-d');
                    $fechaHasta = $hoy->format('Y-m-d');
                    $query->where('inicio', '>=', $hoy->copy()->subDays(90));
                    break;
                case '180dias':
                    $fechaDesde = $hoy->copy()->subDays(180)->format('Y-m-d');
                    $fechaHasta = $hoy->format('Y-m-d');
                    $query->where('inicio', '>=', $hoy->copy()->subDays(180));
                    break;
                case '1año':
                    $fechaDesde = $hoy->copy()->subYear()->format('Y-m-d');
                    $fechaHasta = $hoy->format('Y-m-d');
                    $query->where('inicio', '>=', $hoy->copy()->subYear());
                    break;
            }
        } else {
            // Filtros manuales de fecha (tienen prioridad)
            if ($fechaDesde) {
                $query->whereDate('inicio', '>=', $fechaDesde);
            }
            if ($fechaHasta) {
                $query->whereDate('inicio', '<=', $fechaHasta);
            }
        }

        // Aplicar filtros de hora
        if ($horaDesde) {
            $query->whereTime('inicio', '>=', $horaDesde);
        }
        if ($horaHasta) {
            $query->whereTime('inicio', '<=', $horaHasta);
        }

        $ejecuciones = $query->orderBy('inicio', 'desc')->get();
        
        $erroresDetallados = [];
        $urlsResueltas = [];
        $urlsAgrupadas = []; // Para agrupar URLs duplicadas
        
        // Solo verificar URLs resueltas si se está filtrando por un día específico
        $esFiltroUnDia = ($fechaDesde && $fechaHasta && $fechaDesde === $fechaHasta);

        foreach ($ejecuciones as $ejecucion) {
            $log = $ejecucion->log ?? [];
            $resultados = $log['resultados'] ?? [];
            
            foreach ($resultados as $resultado) {
                if (isset($resultado['success']) && !$resultado['success'] && 
                    isset($resultado['tienda_nombre']) && $resultado['tienda_nombre'] === $tienda) {
                    
                    $url = $resultado['url'] ?? '';
                    $ofertaId = $resultado['oferta_id'] ?? null;
                    
                    if ($url && $ofertaId) {
                        // Obtener información del producto para construir la URL
                        $oferta = \App\Models\OfertaProducto::with('producto.categoria')->find($ofertaId);
                        $productoSlug = $oferta->producto->slug ?? null;
                        $productoCategoriaSlugs = null;
                        
                        if ($oferta && $oferta->producto && $oferta->producto->categoria) {
                            $productoCategoriaSlugs = implode('/', $oferta->producto->categoria->obtenerSlugsJerarquia());
                        }
                        
                        // Agrupar por URL
                        if (!isset($urlsAgrupadas[$url])) {
                            $urlsAgrupadas[$url] = [
                                'url' => $url,
                                'oferta_id' => $ofertaId,
                                'producto_slug' => $productoSlug,
                                'producto_categoria_slugs' => $productoCategoriaSlugs,
                                'error' => $resultado['error'] ?? 'Error desconocido',
                                'precio_anterior' => $resultado['precio_anterior'] ?? null,
                                'variante' => $resultado['variante'] ?? null,
                                'fecha_primera_error' => $ejecucion->inicio->format('Y-m-d H:i:s'),
                                'fecha_ultima_error' => $ejecucion->inicio->format('Y-m-d H:i:s'),
                                'peticiones' => 1,
                                'ejecuciones_ids' => [$ejecucion->id]
                            ];
                        } else {
                            $urlsAgrupadas[$url]['peticiones']++;
                            $urlsAgrupadas[$url]['fecha_ultima_error'] = $ejecucion->inicio->format('Y-m-d H:i:s');
                            $urlsAgrupadas[$url]['ejecuciones_ids'][] = $ejecucion->id;
                        }
                        
                        // Solo verificar si esta URL se resolvió posteriormente si es filtro de un día
                        if ($esFiltroUnDia) {
                            $urlResuelta = $this->verificarUrlResuelta($url, $ejecucion->inicio);
                            if ($urlResuelta) {
                                $urlsResueltas[$url] = $urlResuelta;
                            }
                        }
                    }
                }
            }
        }
        
        // Convertir URLs agrupadas a array de errores detallados
        foreach ($urlsAgrupadas as $urlData) {
            $erroresDetallados[] = $urlData;
        }

        return response()->json([
            'success' => true,
            'tienda' => $tienda,
            'errores' => $erroresDetallados,
            'urls_resueltas' => $urlsResueltas,
            'total_errores' => count($erroresDetallados),
            'es_filtro_un_dia' => $esFiltroUnDia,
            'fecha_filtro' => $esFiltroUnDia ? $fechaDesde : null
        ]);
    }

    /**
     * Verificar si una URL se resolvió posteriormente (en cualquier tipo de ejecución)
     */
    private function verificarUrlResuelta($url, $fechaError)
    {
        // Buscar ejecuciones posteriores donde esta URL se procesó exitosamente (en ambos tipos)
        $ejecucionPosterior = \App\Models\EjecucionGlobal::where(function($q) {
            $q->where('nombre', 'ejecuciones_scrapear_ofertas')
              ->orWhere('nombre', 'actualizar_primera_oferta');
        })
            ->where('inicio', '>', $fechaError)
            ->orderBy('inicio', 'asc')
            ->first();

        if ($ejecucionPosterior) {
            $log = $ejecucionPosterior->log ?? [];
            $resultados = $log['resultados'] ?? [];
            
            foreach ($resultados as $resultado) {
                if (isset($resultado['url']) && $resultado['url'] === $url && 
                    isset($resultado['success']) && $resultado['success']) {
                    return [
                        'fecha_resolucion' => $ejecucionPosterior->inicio->format('Y-m-d H:i:s'),
                        'precio_nuevo' => $resultado['precio_nuevo'] ?? null,
                        'ejecucion_id' => $ejecucionPosterior->id
                    ];
                }
            }
        }

        return null;
    }

    /**
     * Eliminar ejecución de scraping (tanto scraper normal como primera oferta)
     */
    public function eliminarEjecucionScraper($id)
    {
        // Buscar la ejecución por ID sin filtrar por nombre, ya que puede ser de cualquier tipo
        $ejecucion = \App\Models\EjecucionGlobal::where(function($q) {
            $q->where('nombre', 'ejecuciones_scrapear_ofertas')
              ->orWhere('nombre', 'actualizar_primera_oferta');
        })->findOrFail($id);
        $ejecucion->delete();
        return redirect()->back()->with('success', 'Ejecución eliminada.');
    }

    /**
     * Obtener estadísticas por API y por tienda según filtros
     */
    public function obtenerEstadisticasAvanzadas(Request $request)
    {
        // Obtener filtros de fecha
        $filtroRapido = $request->input('filtro_rapido');
        $fechaDesde = $request->input('fecha_desde');
        $fechaHasta = $request->input('fecha_hasta');
        $horaDesde = $request->input('hora_desde');
        $horaHasta = $request->input('hora_hasta');
        
        // Construir query con los mismos filtros que el método principal
        $query = \App\Models\EjecucionGlobal::where(function($q) {
            $q->where('nombre', 'ejecuciones_scrapear_ofertas')
              ->orWhere('nombre', 'actualizar_primera_oferta');
        });
        
        // Aplicar filtros de fecha (priorizar fechas manuales sobre filtro rápido)
        if (($filtroRapido && $filtroRapido !== 'siempre' && $filtroRapido !== '') && !$fechaDesde && !$fechaHasta) {
            // Solo usar filtro rápido si no hay fechas manuales
            $hoy = now();
            switch($filtroRapido) {
                case 'hoy':
                    $fechaDesde = $fechaHasta = $hoy->format('Y-m-d');
                    $query->whereDate('inicio', $hoy->toDateString());
                    break;
                case 'ayer':
                    $ayer = $hoy->copy()->subDay();
                    $fechaDesde = $fechaHasta = $ayer->format('Y-m-d');
                    $query->whereDate('inicio', $ayer->toDateString());
                    break;
                case '7dias':
                    $fechaDesde = $hoy->copy()->subDays(7)->format('Y-m-d');
                    $fechaHasta = $hoy->format('Y-m-d');
                    $query->where('inicio', '>=', $hoy->copy()->subDays(7));
                    break;
                case '30dias':
                    $fechaDesde = $hoy->copy()->subDays(30)->format('Y-m-d');
                    $fechaHasta = $hoy->format('Y-m-d');
                    $query->where('inicio', '>=', $hoy->copy()->subDays(30));
                    break;
                case '90dias':
                    $fechaDesde = $hoy->copy()->subDays(90)->format('Y-m-d');
                    $fechaHasta = $hoy->format('Y-m-d');
                    $query->where('inicio', '>=', $hoy->copy()->subDays(90));
                    break;
                case '180dias':
                    $fechaDesde = $hoy->copy()->subDays(180)->format('Y-m-d');
                    $fechaHasta = $hoy->format('Y-m-d');
                    $query->where('inicio', '>=', $hoy->copy()->subDays(180));
                    break;
                case '1año':
                    $fechaDesde = $hoy->copy()->subYear()->format('Y-m-d');
                    $fechaHasta = $hoy->format('Y-m-d');
                    $query->where('inicio', '>=', $hoy->copy()->subYear());
                    break;
            }
        } else {
            // Filtros manuales de fecha (tienen prioridad)
            if ($fechaDesde) {
                $query->whereDate('inicio', '>=', $fechaDesde);
            }
            if ($fechaHasta) {
                $query->whereDate('inicio', '<=', $fechaHasta);
            }
        }
        
        if ($horaDesde) {
            $query->whereTime('inicio', '>=', $horaDesde);
        }
        if ($horaHasta) {
            $query->whereTime('inicio', '<=', $horaHasta);
        }
        
        $estadisticas = $query->get();
        
        // Calcular estadísticas por tienda
        $estadisticasPorTienda = [];
        $todasLasTiendas = \App\Models\Tienda::all();
        $tiendasConApiMap = [];
        foreach ($todasLasTiendas as $tienda) {
            $tiendasConApiMap[$tienda->nombre] = $tienda->api;
            $estadisticasPorTienda[$tienda->nombre] = [
                'nombre' => $tienda->nombre,
                'api' => $tienda->api,
                'intentos_scraper_normal' => 0,
                'intentos_primera_oferta' => 0,
                'total_intentos' => 0,
                'errores' => 0
            ];
        }
        
        foreach ($estadisticas as $ejecucion) {
            $log = $ejecucion->log ?? [];
            $resultados = $log['resultados'] ?? [];
            $esPrimeraOferta = $ejecucion->nombre === 'actualizar_primera_oferta';
            
            foreach ($resultados as $resultado) {
                $tiendaNombre = $resultado['tienda_nombre'] ?? 'Tienda Desconocida';
                if (!isset($estadisticasPorTienda[$tiendaNombre])) {
                    $estadisticasPorTienda[$tiendaNombre] = [
                        'nombre' => $tiendaNombre,
                        'api' => $tiendasConApiMap[$tiendaNombre] ?? null,
                        'intentos_scraper_normal' => 0,
                        'intentos_primera_oferta' => 0,
                        'total_intentos' => 0,
                        'errores' => 0
                    ];
                }
                
                if ($esPrimeraOferta) {
                    $estadisticasPorTienda[$tiendaNombre]['intentos_primera_oferta']++;
                } else {
                    $estadisticasPorTienda[$tiendaNombre]['intentos_scraper_normal']++;
                }
                
                if (isset($resultado['success']) && !$resultado['success']) {
                    $estadisticasPorTienda[$tiendaNombre]['errores']++;
                }
            }
        }
        
        // Calcular totales y porcentajes
        $totalGlobalIntentos = 0;
        foreach ($estadisticasPorTienda as $tiendaNombre => &$stats) {
            $stats['total_intentos'] = $stats['intentos_scraper_normal'] + $stats['intentos_primera_oferta'];
            $totalGlobalIntentos += $stats['total_intentos'];
        }
        unset($stats);
        
        foreach ($estadisticasPorTienda as $tiendaNombre => &$stats) {
            $stats['porcentaje_total'] = $totalGlobalIntentos > 0 
                ? ($stats['total_intentos'] / $totalGlobalIntentos) * 100 
                : 0;
        }
        unset($stats);
        
        uasort($estadisticasPorTienda, function($a, $b) {
            return $b['total_intentos'] <=> $a['total_intentos'];
        });
        
        // Calcular estadísticas por API
        $estadisticasPorAPI = [];
        $tiendaApiMap = [];
        foreach ($todasLasTiendas as $tienda) {
            $tiendaApiMap[$tienda->nombre] = $tienda->api;
        }
        
        foreach ($estadisticas as $ejecucion) {
            $log = $ejecucion->log ?? [];
            $resultados = $log['resultados'] ?? [];
            
            foreach ($resultados as $resultado) {
                $tiendaNombre = $resultado['tienda_nombre'] ?? null;
                if (!$tiendaNombre || !isset($tiendaApiMap[$tiendaNombre])) {
                    continue;
                }
                
                $apiCompleta = $tiendaApiMap[$tiendaNombre];
                if (!$apiCompleta) {
                    continue;
                }
                
                $apiBase = explode(';', $apiCompleta, 2)[0];
                
                if (!isset($estadisticasPorAPI[$apiBase])) {
                    $estadisticasPorAPI[$apiBase] = [
                        'total_peticiones' => 0,
                        'errores' => 0,
                        'tiendas' => []
                    ];
                }
                
                $estadisticasPorAPI[$apiBase]['total_peticiones']++;
                
                // Contar errores
                if (isset($resultado['success']) && !$resultado['success']) {
                    $estadisticasPorAPI[$apiBase]['errores']++;
                }
                
                if (!in_array($tiendaNombre, $estadisticasPorAPI[$apiBase]['tiendas'])) {
                    $estadisticasPorAPI[$apiBase]['tiendas'][] = $tiendaNombre;
                }
            }
        }
        
        $totalGlobalPeticiones = array_sum(array_column($estadisticasPorAPI, 'total_peticiones'));
        foreach ($estadisticasPorAPI as $apiBase => &$stats) {
            $stats['porcentaje_total'] = $totalGlobalPeticiones > 0 
                ? round(($stats['total_peticiones'] / $totalGlobalPeticiones) * 100, 2) 
                : 0;
            $stats['cantidad_tiendas'] = count($stats['tiendas']);
            // Calcular porcentaje de errores sobre el total de peticiones de esta API
            $stats['porcentaje_errores'] = $stats['total_peticiones'] > 0 
                ? round(($stats['errores'] / $stats['total_peticiones']) * 100, 2) 
                : 0;
        }
        unset($stats);
        
        uasort($estadisticasPorAPI, function($a, $b) {
            return $b['total_peticiones'] <=> $a['total_peticiones'];
        });
        
        // Calcular errores por tienda usando la misma lógica del método principal
        $erroresPorTiendaCalculado = [];
        foreach ($estadisticas as $ejecucion) {
            $log = $ejecucion->log ?? [];
            $resultados = $log['resultados'] ?? [];
            
            foreach ($resultados as $resultado) {
                if (isset($resultado['success']) && !$resultado['success']) {
                    $tiendaNombre = $resultado['tienda_nombre'] ?? 'Tienda Desconocida';
                    if (!isset($erroresPorTiendaCalculado[$tiendaNombre])) {
                        $erroresPorTiendaCalculado[$tiendaNombre] = 0;
                    }
                    $erroresPorTiendaCalculado[$tiendaNombre]++;
                }
            }
        }
        
        // Combinar errores con estadísticas por tienda
        foreach ($estadisticasPorTienda as $tiendaNombre => &$stats) {
            $stats['errores'] = $erroresPorTiendaCalculado[$tiendaNombre] ?? 0;
            // Calcular porcentaje de errores respecto al total de intentos de esa tienda
            $stats['porcentaje_errores'] = $stats['total_intentos'] > 0 
                ? round(($stats['errores'] / $stats['total_intentos']) * 100, 2) 
                : 0;
        }
        unset($stats);
        
        return response()->json([
            'success' => true,
            'estadisticasPorTienda' => array_values($estadisticasPorTienda),
            'estadisticasPorAPI' => $estadisticasPorAPI
        ]);
    }

    /**
     * Obtener JSON de una ejecución específica (tanto scraper normal como primera oferta)
     */
    public function obtenerJsonEjecucionScraper($id)
    {
        // Buscar la ejecución por ID sin filtrar por nombre, ya que puede ser de cualquier tipo
        $ejecucion = \App\Models\EjecucionGlobal::where(function($q) {
            $q->where('nombre', 'ejecuciones_scrapear_ofertas')
              ->orWhere('nombre', 'actualizar_primera_oferta');
        })->findOrFail($id);
        
        $log = $ejecucion->log;
        
        // Si el log tiene el formato nuevo (con 'resultados'), devolver solo el array de resultados
        if (isset($log['resultados']) && is_array($log['resultados'])) {
            return response()->json($log['resultados']);
        }
        
        // Si es el formato antiguo (array directo), devolverlo tal como está
        if (is_array($log) && !isset($log['token'])) {
            return response()->json($log);
        }
        
        // Si es el formato nuevo pero sin 'resultados', devolver array vacío
        return response()->json([]);
    }



    /**
     * Obtener precio de una oferta individual desde el formulario
     */
    public function obtenerPrecioIndividual(Request $request)
    {
        // Validar datos de entrada
        $request->validate([
            'url' => 'required|url',
            'tienda' => 'required|string',
            'variante' => 'nullable|string'
        ]);

        try {
            // Usar el nuevo sistema de scraping interno
            $scrapingController = new \App\Http\Controllers\Scraping\ScrapingController();
            $response = $scrapingController->obtenerPrecio($request);
            $responseData = $response->getData(true);
            
            // Verificar si la respuesta contiene un error
            if (!$responseData['success']) {
                return response()->json([
                    'success' => false,
                    'error' => 'Error en el scraping: ' . ($responseData['error'] ?? 'Error desconocido')
                ]);
            }
            
            // Verificar si la respuesta contiene un precio válido
            if (!isset($responseData['precio']) || !is_numeric($responseData['precio'])) {
                return response()->json([
                    'success' => false,
                    'error' => 'Respuesta inválida del scraping: ' . json_encode($responseData)
                ]);
            }

            // Convertir precio a formato decimal (cambiar coma por punto si es necesario)
            $precio = (float) str_replace(',', '.', $responseData['precio']);

            return response()->json([
                'success' => true,
                'precio' => $precio
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Error de conexión: ' . $e->getMessage()
            ]);
        }
    }

    // ==========================================
    // MÉTODOS PARA ACTUALIZAR CLICKS DE OFERTAS
    // ==========================================

    /**
     * Vista para ejecutar actualización de clicks de ofertas en tiempo real
     */
    public function ejecutarActualizarClicksOfertas()
    {
        return view('admin.ofertas.actualizar-clicks');
    }

    /**
     * Actualizar clicks de ofertas (para cron jobs)
     */
    public function actualizarClicksOfertas()
    {
        // Crear ejecución en la tabla global
        $ejecucion = \App\Models\EjecucionGlobal::create([
            'inicio' => now(),
            'nombre' => 'ejecuciones_actualizar_clicks_ofertas',
            'log' => [],
        ]);

        $ofertas = OfertaProducto::all();
        $actualizadas = 0;
        $errores = 0;
        $log = [];

        foreach ($ofertas as $oferta) {
            try {
                // Contar clicks de los últimos 7 días para esta oferta
                $clicksUltimos7Dias = \App\Models\Click::where('oferta_id', $oferta->id)
                    ->where('created_at', '>=', now()->subDays(7))
                    ->count();

                // Actualizar el campo clicks de la oferta
                $oferta->update(['clicks' => $clicksUltimos7Dias]);

                $actualizadas++;
                $log[] = [
                    'oferta_id' => $oferta->id,
                    'producto' => $oferta->producto->nombre ?? 'Desconocido',
                    'tienda' => $oferta->tienda->nombre ?? 'Desconocida',
                    'clicks' => $clicksUltimos7Dias,
                    'status' => 'actualizada'
                ];
            } catch (\Throwable $e) {
                $errores++;
                $log[] = [
                    'oferta_id' => $oferta->id,
                    'error' => $e->getMessage(),
                    'status' => 'error'
                ];
            }
        }

        $ejecucion->update([
            'fin' => now(),
            'total' => count($ofertas),
            'total_guardado' => $actualizadas,
            'total_errores' => $errores,
            'log' => $log,
        ]);

        return response()->json([
            'status' => 'ok',
            'actualizadas' => $actualizadas,
            'errores' => $errores,
            'ejecucion_id' => $ejecucion->id
        ]);
    }

    /**
     * Procesar actualización de clicks de ofertas
     */
    public function procesarClicksOfertas(Request $request)
    {
        // Número de días configurables
        $diasBusqueda = 7;

        // Si no hay progreso iniciado en sesión, lo creamos
        if (!Session::has('ofertas_clicks_progreso')) {
            $ofertas = OfertaProducto::select('id')->get();
            Session::put('ofertas_clicks_progreso', [
                'ofertas' => $ofertas->pluck('id')->toArray(),
                'total' => $ofertas->count(),
                'actual' => 0,
                'ejecucion_id' => \App\Models\EjecucionGlobal::create([
                    'inicio' => now(),
                    'nombre' => 'ejecuciones_actualizar_clicks_ofertas',
                    'log' => [],
                ])->id,
            ]);
        }

        $progreso = Session::get('ofertas_clicks_progreso');
        $indiceActual = $progreso['actual'];
        $totalOfertas = $progreso['total'];
        $ofertasIds = $progreso['ofertas'];

        // Procesar una oferta si quedan por procesar
        if ($indiceActual < $totalOfertas) {
            $oferta = OfertaProducto::find($ofertasIds[$indiceActual]);

            if ($oferta) {
                $fechaInicio = Carbon::now()->subDays($diasBusqueda);

                $totalClicks = \App\Models\Click::where('oferta_id', $oferta->id)
                    ->where('created_at', '>=', $fechaInicio)
                    ->count();

                $oferta->update(['clicks' => $totalClicks]);
            }

            // Actualizar progreso
            $progreso['actual'] += 1;
            Session::put('ofertas_clicks_progreso', $progreso);

            // Si después de procesar esta oferta ya hemos terminado todas
            if ($progreso['actual'] >= $totalOfertas) {
                $ejecucion = \App\Models\EjecucionGlobal::find($progreso['ejecucion_id']);
                $ejecucion->update([
                    'fin' => now(),
                    'total' => $totalOfertas,
                    'total_guardado' => $totalOfertas,
                    'total_errores' => 0,
                    'log' => [
                        'resultado' => "Se procesaron {$totalOfertas} ofertas exitosamente"
                    ],
                ]);

                Session::forget('ofertas_clicks_progreso');

                return response()->json([
                    'success' => true,
                    'message' => "Se actualizaron los clicks de {$totalOfertas} ofertas exitosamente"
                ]);
            }

            return response()->json([
                'progreso' => round(($progreso['actual'] / $totalOfertas) * 100),
                'ofertas_procesadas' => $progreso['actual'],
                'total_ofertas' => $totalOfertas,
                'oferta_actual' => $oferta ? $oferta->producto->nombre . ' - ' . $oferta->tienda->nombre : 'Desconocida'
            ]);
        }
    }

    /**
     * Vista para listar ejecuciones de actualización de clicks de ofertas
     */
    public function ejecucionesClicksOfertas()
    {
        $ejecuciones = \App\Models\EjecucionGlobal::where('nombre', 'ejecuciones_actualizar_clicks_ofertas')
            ->orderBy('created_at', 'desc')
            ->paginate(20);
            
        return view('admin.ofertas.ejecuciones-clicks', compact('ejecuciones'));
    }

    /**
     * Obtener lista de tiendas disponibles para el formulario
     */
    public function obtenerTiendasDisponibles()
    {
        $tiendas = \App\Models\Tienda::select('id', 'nombre', 'url', 'envio_gratis', 'envio_normal')
            ->orderBy('nombre', 'asc')
            ->get()
            ->map(function ($tienda) {
                return [
                    'id' => $tienda->id,
                    'nombre' => $tienda->nombre,
                    'url' => $tienda->url,
                    'envio_gratis' => $tienda->envio_gratis ?? null,
                    'envio_normal' => $tienda->envio_normal ?? null
                ];
            });
        
        return response()->json($tiendas);
    }

    /**
     * Buscar productos en tiempo real para el formulario
     */
    public function buscarProductos(Request $request)
    {
        $query = $request->get('q', '');
        
        if (empty($query) || strlen($query) < 2) {
            return response()->json([]);
        }

        // Dividir la consulta en palabras
        $palabras = array_filter(explode(' ', strtolower(trim($query))));
        
        if (empty($palabras)) {
            return response()->json([]);
        }

        $productos = \App\Models\Producto::where('obsoleto', 'no')
            ->where(function($q) use ($palabras) {
                foreach ($palabras as $palabra) {
                    $q->where(function($subQ) use ($palabra) {
                        $subQ->whereRaw('LOWER(nombre) LIKE ?', ['%' . $palabra . '%'])
                             ->orWhereRaw('LOWER(marca) LIKE ?', ['%' . $palabra . '%'])
                             ->orWhereRaw('LOWER(modelo) LIKE ?', ['%' . $palabra . '%'])
                             ->orWhereRaw('LOWER(talla) LIKE ?', ['%' . $palabra . '%']);
                    });
                }
            })
            ->orderBy('clicks', 'desc')
            ->limit(10)
            ->get(['id', 'nombre', 'marca', 'modelo', 'talla'])
            ->map(function ($producto) {
                return [
                    'id' => $producto->id,
                    'nombre' => $producto->nombre,
                    'marca' => $producto->marca,
                    'modelo' => $producto->modelo,
                    'talla' => $producto->talla,
                    'texto_completo' => $producto->nombre . ' - ' . $producto->marca . ' - ' . $producto->modelo . ' - ' . $producto->talla
                ];
            });

        return response()->json($productos);
    }

    /**
     * Calcular precio por unidad usando el servicio CalcularPrecioUnidad
     */
    public function calcularPrecioUnidad(Request $request)
    {
        $request->validate([
            'producto_id' => 'required|exists:productos,id',
            'precio_total' => 'required|numeric|min:0',
            'unidades' => 'required|numeric|min:0.01',
        ]);

        $producto = Producto::findOrFail($request->producto_id);
        $calcularPrecioUnidad = new CalcularPrecioUnidad();
        
        $precioUnidad = $calcularPrecioUnidad->calcular(
            $producto->unidadDeMedida ?? 'unidad',
            (float) $request->precio_total,
            (float) $request->unidades
        );

        if ($precioUnidad === null) {
            return response()->json([
                'success' => false,
                'error' => 'Error al calcular el precio por unidad. Verifique que las unidades sean mayores que 0.'
            ], 400);
        }

        return response()->json([
            'success' => true,
            'precio_unidad' => $precioUnidad
        ]);
    }

    public function buscarChollos(Request $request)
    {
        $query = trim($request->get('q', ''));

        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $ahora = Carbon::now();
        $texto = mb_strtolower($query);

        $chollos = Chollo::with(['producto:id', 'tienda:id'])
            ->where('mostrar', 'si')
            ->where('finalizada', 'no')
            ->where('fecha_inicio', '<=', $ahora)
            ->where(function ($q) use ($ahora) {
                $q->whereNull('fecha_final')
                    ->orWhere('fecha_final', '>', $ahora);
            })
            ->whereRaw('LOWER(titulo) LIKE ?', ["%{$texto}%"])
            ->orderBy('fecha_inicio')
            ->limit(10)
            ->get()
            ->map(function ($chollo) {
                return [
                    'id' => $chollo->id,
                    'titulo' => $chollo->titulo,
                    'producto' => $chollo->producto?->nombre,
                    'tienda' => $chollo->tienda?->nombre,
                    'fecha_inicio' => optional($chollo->fecha_inicio)->format('Y-m-d H:i'),
                    'fecha_final' => optional($chollo->fecha_final)->format('Y-m-d H:i'),
                ];
            });

        return response()->json($chollos);
    }

    /**
     * Procesar oferta scraper desde el formulario
     */
    public function procesarOfertaScraper(Request $request)
    {
        $request->validate([
            'url' => 'required|url',
            'tienda' => 'required|string',
            'variante' => 'nullable|string'
        ]);

        try {
            // Usar el nuevo sistema de scraping interno
            $scrapingController = new \App\Http\Controllers\Scraping\ScrapingController();
            $response = $scrapingController->obtenerPrecio($request);
            $responseData = $response->getData(true);
            
            // Verificar si la respuesta contiene un error
            if (!$responseData['success']) {
                return response()->json([
                    'success' => false,
                    'error' => 'Error en el scraping: ' . ($responseData['error'] ?? 'Error desconocido')
                ]);
            }
            
            // Verificar si la respuesta contiene un precio válido
            if (!isset($responseData['precio']) || !is_numeric($responseData['precio'])) {
                return response()->json([
                    'success' => false,
                    'error' => 'Respuesta inválida del scraping: ' . json_encode($responseData)
                ]);
            }

            // Convertir precio a formato decimal (cambiar coma por punto si es necesario)
            $precio = (float) str_replace(',', '.', $responseData['precio']);

            return response()->json([
                'success' => true,
                'precio' => $precio
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Error de conexión: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Verificar si una URL ya existe en la tabla ofertas_producto
     */
    public function verificarUrlExistente(Request $request)
    {
        $url = $request->input('url');
        $ofertaId = $request->input('oferta_id'); // Para excluir la oferta actual en caso de edición
        $productoId = $request->input('producto_id'); // ID del producto actual
        
        if (empty($url)) {
            return response()->json([
                'tipo' => 'vacia',
                'mensaje' => 'URL vacía'
            ]);
        }
        
        $query = OfertaProducto::with('producto')->where('url', $url);
        
        // Si estamos editando una oferta, excluirla de la búsqueda
        if ($ofertaId) {
            $query->where('id', '!=', $ofertaId);
        }
        
        $ofertasExistentes = $query->get();
        
        if ($ofertasExistentes->isEmpty()) {
            return response()->json([
                'tipo' => 'disponible',
                'mensaje' => 'Esta URL no existe y está disponible'
            ]);
        }
        
        // Verificar si hay ofertas con el mismo producto_id
        $mismoProducto = $ofertasExistentes->where('producto_id', $productoId);
        $otrosProductos = $ofertasExistentes->where('producto_id', '!=', $productoId);
        
        if ($mismoProducto->isNotEmpty()) {
            // URL existe en el mismo producto
            return response()->json([
                'tipo' => 'mismo_producto',
                'mensaje' => 'Esta URL ya existe en otra oferta del mismo producto',
                'requiere_confirmacion' => true
            ]);
        } else {
            // URL existe en otros productos
            $productos = $otrosProductos->map(function($oferta) {
                return [
                    'id' => $oferta->producto->id,
                    'nombre' => $oferta->producto->nombre,
                    'marca' => $oferta->producto->marca,
                    'modelo' => $oferta->producto->modelo,
                    'talla' => $oferta->producto->talla
                ];
            })->values();
            
            return response()->json([
                'tipo' => 'otros_productos',
                'mensaje' => 'Esta URL existe en otros productos',
                'productos' => $productos,
                'requiere_confirmacion' => false
            ]);
        }
    }

    /**
     * Ejecutar histórico de precios de ofertas (para cron jobs)
     */
    public function ejecutarHistoricoPrecios()
    {
        // Crear ejecución en la tabla global
        $ejecucion = \App\Models\EjecucionGlobal::create([
            'inicio' => now(),
            'nombre' => 'ejecuciones_historico_precios_ofertas',
            'log' => [],
        ]);

        $ofertas = OfertaProducto::with(['producto', 'tienda'])->get();
        $guardados = 0;
        $errores = 0;
        $log = [];

        foreach ($ofertas as $oferta) {
            try {
                // Guardar el precio actual de la oferta en el histórico
                \App\Models\HistoricoPrecioOferta::updateOrCreate(
                    [
                        'oferta_producto_id' => $oferta->id,
                        'fecha' => now()->toDateString(),
                    ],
                    [
                        'precio_unidad' => $oferta->precio_unidad,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );

                $guardados++;
                $log[] = [
                    'oferta_id' => $oferta->id,
                    'producto' => $oferta->producto->nombre ?? 'Desconocido',
                    'tienda' => $oferta->tienda->nombre ?? 'Desconocida',
                    'precio' => $oferta->precio_unidad,
                    'status' => 'guardado'
                ];
            } catch (\Throwable $e) {
                $errores++;
                $log[] = [
                    'oferta_id' => $oferta->id,
                    'error' => $e->getMessage(),
                    'status' => 'error'
                ];
            }
        }

        $ejecucion->update([
            'fin' => now(),
            'total' => count($ofertas),
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
     * Obtener información de variantes para una tienda específica
     */
    public function obtenerVariantesTienda($tienda)
    {
        // Normalizar el nombre de la tienda para buscar el controlador
        $tiendaKey = strtolower(str_replace([' ', '-', '_'], '', $tienda));
        
        // Crear un mapeo de nombres de tiendas a nombres de archivos de controladores
        $mapeoTiendas = [
            'el corte inglés' => 'elcorteingles',
            'el corte ingles' => 'elcorteingles',
            'corte inglés' => 'elcorteingles',
            'corte ingles' => 'elcorteingles',
            'farmacias direct' => 'farmaciasdirect',
            'farmacia en casa' => 'farmaciaencasa',
            'farmacia mamirón' => 'farmaciamamiron',
            'farmacia mamiron' => 'farmaciamamiron',
            'a tu farmacia' => 'atuafarmacia',
            'para salud online' => 'parasaludonline',
            't cuida online' => 'tcuidaonline',
            'marvi mundo' => 'marvimundo',
            'hola princesa' => 'holaprincesa',
            'más pañales' => 'maspaniales',
            'mas paniales' => 'maspaniales',
            'toys r us' => 'toysrus',
            'outlet pc' => 'outletpc'
        ];
        
        // Buscar en el mapeo
        $tiendaNormalizada = strtolower(trim($tienda));
        if (isset($mapeoTiendas[$tiendaNormalizada])) {
            $tiendaKey = $mapeoTiendas[$tiendaNormalizada];
        }
        
        // Buscar el controlador correspondiente
        $controladorPath = null;
        $controladoresDir = app_path('Http/Controllers/Scraping/Tiendas');
        
        if (is_dir($controladoresDir)) {
            $archivos = scandir($controladoresDir);
            foreach ($archivos as $archivo) {
                if ($archivo === '.' || $archivo === '..') continue;
                
                // Extraer el nombre de la tienda del nombre del archivo
                $nombreArchivo = strtolower(str_replace(['Controller.php', 'Controller'], '', $archivo));
                $nombreArchivo = str_replace([' ', '-', '_'], '', $nombreArchivo);
                
                // Comparar con diferentes variaciones del nombre de la tienda
                $variacionesTienda = [
                    $tiendaKey,
                    strtolower(str_replace([' ', '-', '_'], '', $tienda)),
                    strtolower(str_replace([' ', '-', '_', 'el', 'la', 'los', 'las'], '', $tienda)),
                    strtolower(preg_replace('/[^a-z0-9]/', '', $tienda))
                ];
                
                foreach ($variacionesTienda as $variacion) {
                    if ($nombreArchivo === $variacion) {
                        $controladorPath = $controladoresDir . '/' . $archivo;
                        break 2;
                    }
                }
            }
        }
        
        // Debug: si no se encuentra, mostrar información de depuración
        if (!$controladorPath) {
            \Log::info("No se encontró controlador para tienda: {$tienda}");
            \Log::info("TiendaKey: {$tiendaKey}");
            \Log::info("Variaciones probadas: " . json_encode($variacionesTienda ?? []));
            
            // Listar todos los archivos disponibles para debug
            if (is_dir($controladoresDir)) {
                $archivos = scandir($controladoresDir);
                $archivosDisponibles = array_filter($archivos, function($archivo) {
                    return $archivo !== '.' && $archivo !== '..' && strpos($archivo, 'Controller.php') !== false;
                });
                \Log::info("Archivos disponibles: " . json_encode(array_values($archivosDisponibles)));
            }
        }
        
        if ($controladorPath && file_exists($controladorPath)) {
            // Leer el contenido del archivo
            $contenido = file_get_contents($controladorPath);
            
            // Buscar el comentario encima de la función obtenerPrecio
            $comentario = $this->extraerComentarioObtenerPrecio($contenido);
            
            if ($comentario) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'nombre' => $tienda,
                        'descripcion' => $comentario,
                        'ejemplo' => 'Información extraída del controlador de la tienda',
                        'requerida' => false
                    ]
                ]);
            }
        }
        
        // Si no se encuentra el controlador o no hay comentario, devolver información genérica
        return response()->json([
            'success' => true,
            'data' => [
                'nombre' => $tienda,
                'descripcion' => 'No se encontró información específica en el controlador de esta tienda.',
                'ejemplo' => 'Dejar vacío o usar para notas internas',
                'requerida' => false
            ]
        ]);
    }
    
    /**
     * Extraer el comentario encima de la función obtenerPrecio
     */
    private function extraerComentarioObtenerPrecio($contenido)
    {
        // Buscar la función obtenerPrecio con diferentes patrones de comentarios
        $patrones = [
            // Patrón estándar: /** comentario */ public function obtenerPrecio
            '/\/\*\*(.*?)\*\/\s*public\s+function\s+obtenerPrecio/s',
            
            // Patrón alternativo: /** comentario */ public function obtenerPrecio(
            '/\/\*\*(.*?)\*\/\s*public\s+function\s+obtenerPrecio\s*\(/s',
            
            // Patrón con espacios adicionales
            '/\/\*\*(.*?)\*\/\s*public\s+function\s+obtenerPrecio\s*\([^)]*\)\s*:\s*JsonResponse/s',
            
            // Patrón más flexible
            '/\/\*\*(.*?)\*\/\s*public\s+function\s+obtenerPrecio[^{]*{/s'
        ];
        
        foreach ($patrones as $patron) {
            if (preg_match($patron, $contenido, $matches)) {
                $comentario = trim($matches[1]);
                
                // Limpiar el comentario
                $comentario = preg_replace('/^\s*\*\s*/m', '', $comentario); // Remover asteriscos al inicio de cada línea
                $comentario = preg_replace('/\s*\*\s*$/m', '', $comentario); // Remover asteriscos al final de cada línea
                $comentario = preg_replace('/\s+/', ' ', $comentario); // Normalizar espacios
                $comentario = trim($comentario);
                
                return $comentario;
            }
        }
        
        return null;
    }

    /**
     * Mostrar formulario para reorganizar update_at de ofertas
     */
    public function reorganizarUpdateAt()
    {
        $tiendas = \App\Models\Tienda::withCount(['ofertas' => function($query) {
            $query->where('mostrar', 'si');
        }])->orderBy('nombre')->get();
        return view('admin.ofertas.reorganizar_update_at_ofertas', compact('tiendas'));
    }

    /**
     * Obtener datos de distribución de ofertas por hora para una tienda
     */
    public function obtenerDistribucionOfertas(Request $request)
    {
        $request->validate([
            'tienda_id' => 'required|exists:tiendas,id',
        ]);

        $tienda = \App\Models\Tienda::findOrFail($request->tienda_id);
        $ofertas = $tienda->ofertas()->where('mostrar', 'si')->get();
        
        if ($ofertas->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'No hay ofertas para esta tienda'
            ]);
        }

        // Inicializar array de horas (8:00 a 23:00)
        $distribucion = [];
        for ($hora = 8; $hora <= 23; $hora++) {
            $distribucion[$hora] = 0;
        }

        // Calcular próxima actualización de cada oferta
        foreach ($ofertas as $oferta) {
            $proximaHoraActualizacion = $this->calcularProximaHoraActualizacion($oferta);
            if ($proximaHoraActualizacion >= 8 && $proximaHoraActualizacion <= 23) {
                $distribucion[$proximaHoraActualizacion]++;
            }
        }

        return response()->json([
            'status' => 'ok',
            'distribucion' => $distribucion,
            'total_ofertas' => $ofertas->count()
        ]);
    }

    /**
     * Calcular la próxima hora en que se debe actualizar una oferta
     */
    private function calcularProximaHoraActualizacion($oferta)
    {
        $ahora = now();
        $ultimaActualizacion = $oferta->updated_at ?? $ahora;
        $frecuenciaMinutos = $oferta->frecuencia_actualizar_precio_minutos ?? 1440;

        // Calcular cuándo sería la próxima actualización
        $proximaActualizacion = $ultimaActualizacion->copy()->addMinutes($frecuenciaMinutos);

        // Si la próxima actualización es antes de las 8 AM o después de las 11 PM,
        // ajustarla a las 8 AM del día correspondiente
        $hora = $proximaActualizacion->hour;
        $minuto = $proximaActualizacion->minute;

        if ($hora < 8) {
            // Si es antes de las 8 AM, mover a las 8 AM del mismo día
            $proximaActualizacion->setTime(8, 0, 0);
        } elseif ($hora > 23 || ($hora == 23 && $minuto > 0)) {
            // Si es después de las 11 PM, mover a las 8 AM del día siguiente
            $proximaActualizacion->addDay()->setTime(8, 0, 0);
        }

        // Si la próxima actualización ya pasó, calcular el siguiente ciclo
        while ($proximaActualizacion->lessThan($ahora)) {
            $proximaActualizacion->addMinutes($frecuenciaMinutos);
            
            $hora = $proximaActualizacion->hour;
            $minuto = $proximaActualizacion->minute;

            if ($hora < 8) {
                $proximaActualizacion->setTime(8, 0, 0);
            } elseif ($hora > 23 || ($hora == 23 && $minuto > 0)) {
                $proximaActualizacion->addDay()->setTime(8, 0, 0);
            }
        }

        return $proximaActualizacion->hour;
    }

    /**
     * Obtener distribución DESPUÉS de reorganizar (basada en updated_at actual)
     */
    public function obtenerDistribucionDespues(Request $request)
    {
        $request->validate([
            'tienda_id' => 'required|exists:tiendas,id',
        ]);

        $tienda = \App\Models\Tienda::findOrFail($request->tienda_id);
        $ofertas = $tienda->ofertas()->where('mostrar', 'si')->get();
        
        if ($ofertas->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'No hay ofertas para esta tienda'
            ]);
        }

        // Inicializar array de horas (8:00 a 23:00)
        $distribucion = [];
        for ($hora = 8; $hora <= 23; $hora++) {
            $distribucion[$hora] = 0;
        }

        // Para la distribución DESPUÉS, simplemente contar por la hora del updated_at actual
        foreach ($ofertas as $oferta) {
            $horaActualizacion = $oferta->updated_at->hour;
            if ($horaActualizacion >= 8 && $horaActualizacion <= 23) {
                $distribucion[$horaActualizacion]++;
            }
        }

        return response()->json([
            'status' => 'ok',
            'distribucion' => $distribucion,
            'total_ofertas' => $ofertas->count()
        ]);
    }

    /**
     * Ejecutar reorganización de update_at de ofertas
     */
    public function ejecutarReorganizarUpdateAt(Request $request)
    {
        $request->validate([
            'tienda_id' => 'required|exists:tiendas,id',
            'hora_inicio' => 'nullable|integer|min:0|max:23',
            'hora_fin' => 'nullable|integer|min:0|max:23',
        ]);

        $tienda = \App\Models\Tienda::findOrFail($request->tienda_id);
        $ofertas = $tienda->ofertas()->where('mostrar', 'si')->get();
        
        if ($ofertas->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'No hay ofertas para esta tienda'
            ]);
        }

        // Obtener horas de inicio y fin (por defecto 8:00 y 22:59)
        $horaInicio = $request->input('hora_inicio', 8);
        $horaFin = $request->input('hora_fin', 22);
        $minutoFin = 59; // Siempre terminar a los 59 minutos
        
        $totalOfertas = $ofertas->count();
        
        // Calcular minutos totales disponibles (desde inicio hasta fin)
        $minutosTotales = ($horaFin - $horaInicio) * 60 + $minutoFin + 1; // +1 para incluir el minuto final
        
        // Generar horarios aleatorios para cada oferta
        $horariosGenerados = [];
        
        if ($totalOfertas == 1) {
            // Si solo hay una oferta, ponerla en el medio del rango
            $horariosGenerados[] = $minutosTotales / 2;
        } else {
            // Generar horarios aleatorios con separación mínima
            $separacionMinima = max(30, $minutosTotales / ($totalOfertas * 3)); // Mínimo 30 minutos o 1/3 del rango por oferta
            
            for ($i = 0; $i < $totalOfertas; $i++) {
                $intentos = 0;
                $maxIntentos = 100; // Evitar bucle infinito
                
                do {
                    $minutosAleatorios = rand(0, $minutosTotales - 1);
                    $intentos++;
                } while ($intentos < $maxIntentos && $this->horarioMuyCerca($minutosAleatorios, $horariosGenerados, $separacionMinima));
                
                $horariosGenerados[] = $minutosAleatorios;
            }
            
            // Ordenar los horarios para que queden en orden cronológico
            sort($horariosGenerados);
        }

        $actualizadas = 0;
        $errores = 0;
        $log = [];

        foreach ($ofertas as $index => $oferta) {
            try {
                // Usar el horario aleatorio generado para esta oferta
                $minutosOffsetActual = $horariosGenerados[$index];
                
                $hora = $horaInicio + floor($minutosOffsetActual / 60);
                $minuto = $minutosOffsetActual % 60;
                
                // Asegurar que no exceda la hora fin
                if ($hora > $horaFin || ($hora == $horaFin && $minuto > $minutoFin)) {
                    $hora = $horaFin;
                    $minuto = $minutoFin;
                }

                // IMPORTANTE: Mantener el día original de updated_at pero cambiar solo la hora
                $fechaOriginal = $oferta->updated_at ?? now();
                // Crear nueva fecha manteniendo el mismo día, mes y año, solo cambiando hora y minuto
                $nuevaFecha = $fechaOriginal->copy()->setTime($hora, $minuto, 0);

                // Actualizar solo el updated_at sin tocar otros campos
                $oferta->timestamps = false; // Deshabilitar timestamps automáticos
                $oferta->updated_at = $nuevaFecha;
                $oferta->save();
                $oferta->timestamps = true; // Rehabilitar timestamps

                $actualizadas++;
                $log[] = [
                    'oferta_id' => $oferta->id,
                    'producto' => $oferta->producto->nombre ?? 'Sin producto',
                    'fecha_original' => $fechaOriginal->format('Y-m-d H:i:s'),
                    'nueva_fecha' => $nuevaFecha->format('Y-m-d H:i:s'),
                    'status' => 'actualizada'
                ];

            } catch (\Exception $e) {
                $errores++;
                $log[] = [
                    'oferta_id' => $oferta->id,
                    'producto' => $oferta->producto->nombre ?? 'Sin producto',
                    'error' => $e->getMessage(),
                    'status' => 'error'
                ];
            }
        }

        return response()->json([
            'status' => 'ok',
            'total_ofertas' => $totalOfertas,
            'actualizadas' => $actualizadas,
            'errores' => $errores,
            'log' => $log,
            'hora_inicio' => $horaInicio,
            'hora_fin' => $horaFin
        ]);
    }

    private function parseNullableDateTime(?string $valor): ?Carbon
    {
        if (!$valor) {
            return null;
        }

        try {
            return Carbon::parse($valor);
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function calcularFrecuenciaCholloEnMinutos($valor, ?string $unidad): ?int
    {
        if ($valor === null || $unidad === null) {
            return null;
        }

        $valorNumerico = (float) $valor;

        return match ($unidad) {
            'minutos' => (int) round($valorNumerico),
            'horas' => (int) round($valorNumerico * 60),
            'dias' => (int) round($valorNumerico * 1440),
            default => null,
        };
    }

    /**
     * Verificar si un horario está muy cerca de otros horarios ya generados
     */
    private function horarioMuyCerca($nuevoHorario, $horariosExistentes, $separacionMinima)
    {
        foreach ($horariosExistentes as $horarioExistente) {
            if (abs($nuevoHorario - $horarioExistente) < $separacionMinima) {
                return true;
            }
        }
        return false;
    }

    /**
     * Vista para detectar ofertas con precio elevado
     */
    public function detectarOfertasPrecioElevado()
    {
        $tiendas = \App\Models\Tienda::orderBy('nombre', 'asc')->get();
        return view('admin.ofertas.detectarOfertasPrecioElevado', compact('tiendas'));
    }

    /**
     * Procesar detección de ofertas con precio elevado
     */
    public function procesarDetectarOfertasPrecioElevado(Request $request)
    {
        try {
            $validated = $request->validate([
                'tienda_id' => 'required|exists:tiendas,id',
                'dias' => 'required|in:90,180,365'
            ]);

            $tiendaId = $validated['tienda_id'];
            $dias = (int) $validated['dias'];
            $desde = Carbon::today()->subDays($dias - 1);

            // Obtener todas las ofertas de la tienda con mostrar='si'
            $ofertas = OfertaProducto::where('tienda_id', $tiendaId)
                ->where('mostrar', 'si')
                ->with('producto')
                ->get();

            $servicioOfertas = new \App\Services\SacarPrimeraOfertaDeUnProductoAplicadoDescuentosYChollos();
            $ofertasDetectadas = [];

            foreach ($ofertas as $oferta) {
                $producto = $oferta->producto;
                if (!$producto) continue;

                // Obtener rango de precio_unidad de las 5 primeras ofertas del producto
                $todasOfertasProducto = $servicioOfertas->obtenerTodas($producto);
                $primeras5Ofertas = $todasOfertasProducto->take(5);

                if ($primeras5Ofertas->count() === 0) continue;

                $precioPrimera = $primeras5Ofertas->first()->precio_unidad ?? null;
                $precioQuinta = $primeras5Ofertas->last()->precio_unidad ?? null;

                if ($precioPrimera === null || $precioQuinta === null) continue;

                // PRUEBA 1: Verificar si el historial de la oferta ha estado en el rango de las 5 ofertas
                $historicoOferta = HistoricoPrecioOferta::where('oferta_producto_id', $oferta->id)
                    ->where('fecha', '>=', $desde)
                    ->get();

                $prueba1Paso = false; // No ha estado en el rango
                foreach ($historicoOferta as $registro) {
                    $precio = (float) $registro->precio_unidad;
                    if ($precio >= $precioPrimera && $precio <= $precioQuinta) {
                        $prueba1Paso = true; // Ha estado en el rango
                        break;
                    }
                }

                // Si pasó la primera prueba, no se añade a la lista
                if ($prueba1Paso) {
                    continue;
                }

                // PRUEBA 2: Verificar si el precio de la oferta y el precio del historial del producto se han cruzado
                $historicoProducto = \App\Models\HistoricoPrecioProducto::where('producto_id', $producto->id)
                    ->where('fecha', '>=', $desde)
                    ->orderBy('fecha')
                    ->get();

                $prueba2Paso = false; // No se han cruzado
                $fechasOferta = $historicoOferta->keyBy('fecha');
                $fechasProducto = $historicoProducto->keyBy('fecha');
                
                // Obtener todas las fechas únicas
                $todasFechas = collect(array_merge($fechasOferta->keys()->toArray(), $fechasProducto->keys()->toArray()))->unique()->sort()->values();
                
                $precioAnteriorOferta = null;
                $precioAnteriorProducto = null;
                
                foreach ($todasFechas as $fecha) {
                    $precioOferta = isset($fechasOferta[$fecha]) ? (float) $fechasOferta[$fecha]->precio_unidad : $precioAnteriorOferta;
                    $precioProducto = isset($fechasProducto[$fecha]) ? (float) $fechasProducto[$fecha]->precio_minimo : $precioAnteriorProducto;
                    
                    if ($precioOferta === null || $precioProducto === null) {
                        if ($precioOferta !== null) $precioAnteriorOferta = $precioOferta;
                        if ($precioProducto !== null) $precioAnteriorProducto = $precioProducto;
                        continue;
                    }
                    
                    // Verificar si se han tocado o cruzado (diferencia menor a 0.01€ o están muy cerca)
                    if (abs($precioOferta - $precioProducto) < 0.01) {
                        $prueba2Paso = true; // Se han tocado/cruzado
                        break;
                    }
                    
                    // Verificar si se han cruzado (cambio de posición relativa)
                    if ($precioAnteriorOferta !== null && $precioAnteriorProducto !== null) {
                        $anteriorOfertaMayor = $precioAnteriorOferta > $precioAnteriorProducto;
                        $actualOfertaMayor = $precioOferta > $precioProducto;
                        
                        if ($anteriorOfertaMayor !== $actualOfertaMayor) {
                            $prueba2Paso = true; // Se han cruzado
                            break;
                        }
                    }
                    
                    $precioAnteriorOferta = $precioOferta;
                    $precioAnteriorProducto = $precioProducto;
                }

                // PRUEBA 3: Calcular medias y verificar si la media de la oferta es >20% que la del producto
                $preciosOferta = $historicoOferta->pluck('precio_unidad')->filter()->toArray();
                $preciosProducto = $historicoProducto->pluck('precio_minimo')->filter()->toArray();

                $prueba3Paso = false;
                $prueba3Porcentaje = 0;

                if (!empty($preciosOferta) && !empty($preciosProducto)) {
                    $mediaOferta = array_sum($preciosOferta) / count($preciosOferta);
                    $mediaProducto = array_sum($preciosProducto) / count($preciosProducto);

                    if ($mediaProducto > 0) {
                        $porcentaje = (($mediaOferta - $mediaProducto) / $mediaProducto) * 100;
                        $prueba3Porcentaje = round($porcentaje, 2);
                        $prueba3Paso = $porcentaje > 20; // Mayor a 20%
                    }
                }

                // Añadir a la lista de ofertas detectadas
                $ofertasDetectadas[] = [
                    'id' => $oferta->id,
                    'producto_nombre' => $producto->nombre,
                    'prueba1_paso' => $prueba1Paso,
                    'prueba2_paso' => $prueba2Paso,
                    'prueba3_paso' => $prueba3Paso,
                    'prueba3_porcentaje' => $prueba3Porcentaje,
                ];
            }

            return response()->json([
                'success' => true,
                'ofertas' => $ofertasDetectadas
            ]);

        } catch (\Exception $e) {
            \Log::error('Error en procesarDetectarOfertasPrecioElevado: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al procesar: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Comprobar ofertas con gastos de envío diferentes a los de la tienda
     * Cron que se ejecuta periódicamente para detectar ofertas que tienen
     * gastos de envío diferentes a los configurados en la tienda
     */
    public function comprobarGastosEnvioOfertas()
    {
        try {
            $fecha30DiasAtras = now()->subDays(30);
            
            // Buscar ofertas que cumplan los criterios
            $ofertas = OfertaProducto::where('mostrar', 'si')
                ->whereNull('chollo_id')
                ->whereNotNull('envio')
                ->where('envio', '>', 0)
                ->where(function($query) use ($fecha30DiasAtras) {
                    $query->whereNull('fecha_actualizacion_envio')
                          ->orWhere('fecha_actualizacion_envio', '<', $fecha30DiasAtras);
                })
                ->whereHas('tienda', function($query) {
                    $query->where('mostrar', 'si');
                })
                ->with('tienda')
                ->get();

            $avisosCreados = 0;

            foreach ($ofertas as $oferta) {
                $tienda = $oferta->tienda;
                
                // Obtener el valor de envío esperado de la tienda (misma lógica que en el formulario)
                $envioEsperado = null;
                $envioTexto = null;
                
                // Buscar primero en envio_gratis, si es null o vacío, buscar en envio_normal
                if ($tienda->envio_gratis && trim($tienda->envio_gratis) !== '') {
                    $envioTexto = $tienda->envio_gratis;
                } elseif ($tienda->envio_normal && trim($tienda->envio_normal) !== '') {
                    $envioTexto = $tienda->envio_normal;
                }
                
                // Si no hay envio_texto, la tienda no tiene configurado envío, saltar
                if (!$envioTexto) {
                    continue;
                }
                
                $envioTextoLower = strtolower($envioTexto);
                
                // Verificar si contiene "gratis"
                if (strpos($envioTextoLower, 'gratis') !== false) {
                    // Si la tienda tiene envío gratis, la oferta no debería tener envío
                    $envioEsperado = 0;
                } else {
                    // Si no es gratis, intentar extraer el precio
                    // Puede ser "2,99 € < 40€", "2€ < 40€", "2,99 €" o "2€"
                    if (preg_match('/(\d+[,.]?\d*)\s*€?/', $envioTexto, $matches)) {
                        // Extraer el número y convertir coma a punto
                        $envioEsperado = (float) str_replace(',', '.', $matches[1]);
                    }
                }
                
                // Comparar el envío de la oferta con el esperado
                $envioOferta = (float) $oferta->envio;
                $esDiferente = false;
                
                if ($envioEsperado === null) {
                    // No se pudo determinar el envío esperado, saltar
                    continue;
                }
                
                // Comparar con tolerancia de 0.01 para evitar problemas de precisión
                if (abs($envioOferta - $envioEsperado) > 0.01) {
                    $esDiferente = true;
                }
                
                // Si el envío es diferente, crear aviso
                if ($esDiferente) {
                    // Verificar si ya existe un aviso para esta oferta con el mismo texto
                    $avisoExistente = DB::table('avisos')
                        ->where('avisoable_type', OfertaProducto::class)
                        ->where('avisoable_id', $oferta->id)
                        ->where('texto_aviso', 'Comprobar gastos de envio')
                        ->where('fecha_aviso', '>=', now())
                        ->first();
                    
                    if (!$avisoExistente) {
                        // Crear aviso con fecha actual
                        DB::table('avisos')->insert([
                            'texto_aviso'     => 'Comprobar gastos de envio',
                            'fecha_aviso'     => now(),
                            'user_id'         => 1, // usuario sistema
                            'avisoable_type'  => OfertaProducto::class,
                            'avisoable_id'    => $oferta->id,
                            'oculto'          => 0, // visible
                            'created_at'      => now(),
                            'updated_at'      => now(),
                        ]);
                        
                        $avisosCreados++;
                    }
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Se procesaron {$ofertas->count()} ofertas y se crearon {$avisosCreados} avisos.",
                'ofertas_procesadas' => $ofertas->count(),
                'avisos_creados' => $avisosCreados
            ]);

        } catch (\Exception $e) {
            \Log::error('Error en comprobarGastosEnvioOfertas: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al comprobar gastos de envío: ' . $e->getMessage()
            ], 500);
        }
    }
}
