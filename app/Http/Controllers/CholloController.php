<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Categoria;
use App\Models\Chollo;
use App\Models\OfertaProducto;
use App\Models\PrecioHot;
use App\Models\Producto;
use App\Models\Tienda;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use App\Services\TiemposActualizacionOfertasDinamicos;

class CholloController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) $request->input('perPage', 20);
        $busqueda = $request->input('buscar');
        $mostrar = $request->input('mostrar', 'activos');

        $chollos = Chollo::with(['producto', 'tienda', 'categoria'])
            ->when($busqueda, function ($query) use ($busqueda) {
                $texto = mb_strtolower($busqueda);

                $query->where(function ($q) use ($texto) {
                    $q->whereRaw('LOWER(titulo) LIKE ?', ["%{$texto}%"])
                        ->orWhereRaw('LOWER(url) LIKE ?', ["%{$texto}%"])
                        ->orWhereRaw('LOWER(descripcion) LIKE ?', ["%{$texto}%"])
                        ->orWhereHas('producto', function ($rel) use ($texto) {
                            $rel->whereRaw('LOWER(nombre) LIKE ?', ["%{$texto}%"])
                                ->orWhereRaw('LOWER(marca) LIKE ?', ["%{$texto}%"])
                                ->orWhereRaw('LOWER(modelo) LIKE ?', ["%{$texto}%"])
                                ->orWhereRaw('LOWER(talla) LIKE ?', ["%{$texto}%"]);
                        })
                        ->orWhereHas('tienda', function ($rel) use ($texto) {
                            $rel->whereRaw('LOWER(nombre) LIKE ?', ["%{$texto}%"]);
                        })
                        ->orWhereHas('categoria', function ($rel) use ($texto) {
                            $rel->whereRaw('LOWER(nombre) LIKE ?', ["%{$texto}%"]);
                        });
                });
            })
            ->when($mostrar === 'activos', function ($query) {
                $query->where('mostrar', 'si')
                    ->where(function ($q) {
                        $q->whereNull('fecha_final')
                            ->orWhere('fecha_final', '>', Carbon::now());
                    });
            })
            ->when($mostrar === 'finalizados', function ($query) {
                $query->where(function ($q) {
                    $q->where('finalizada', 'si')
                        ->orWhere(function ($inner) {
                            $inner->whereNotNull('fecha_final')
                                ->where('fecha_final', '<=', Carbon::now());
                        });
                });
            })
            ->when($mostrar === 'ocultos', function ($query) {
                $query->where('mostrar', 'no');
            })
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString();

        return view('admin.chollos.index', [
            'chollos' => $chollos,
            'perPage' => $perPage,
            'busqueda' => $busqueda,
            'mostrar' => $mostrar,
        ]);
    }

    public function create(Request $request)
    {
        $chollo = null;
        $producto = $request->filled('producto_id')
            ? Producto::find($request->input('producto_id'))
            : null;
        $tienda = $request->filled('tienda_id')
            ? Tienda::find($request->input('tienda_id'))
            : null;
        $categoria = $request->filled('categoria_id')
            ? Categoria::find($request->input('categoria_id'))
            : null;

        return view('admin.chollos.formulario', [
            'chollo' => $chollo,
            'productoSeleccionado' => $producto,
            'tiendaSeleccionada' => $tienda,
            'categoriaSeleccionada' => $categoria,
            'categoriasRaiz' => $this->obtenerCategoriasRaiz(),
            'url' => $request->query('url'),
            'descripcionInternaDefault' => $this->obtenerDescripcionInternaDefault(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validarFormulario($request);

        $this->validarLimiteOfertasVinculadas($request, null, $validated['tipo']);

        $this->verificarDuplicadoActivo($validated['url'], null, $request);

        $datos = $this->prepararDatos($validated, $request, null, true);
        $datos['fecha_inicio'] = $datos['fecha_inicio'] ?? Carbon::now();
        $datos['clicks'] = 0;
        $datos['me_gusta'] = 0;
        $datos['no_me_gusta'] = 0;

        $slugBase = $datos['slug'];
        $chollo = null;

        DB::transaction(function () use (&$chollo, $datos, $slugBase) {
            $chollo = Chollo::create($datos);

            $slugFinal = $this->construirSlugFinal($slugBase, $chollo->id);
            if ($slugFinal !== $slugBase) {
                $chollo->update(['slug' => $slugFinal]);
                $chollo->slug = $slugFinal;
            }
        });

        $this->procesarOfertasTemporales($request, $chollo);

        return redirect()
            ->route('admin.chollos.index')
            ->with('success', 'Chollo creado correctamente');
    }

    public function edit(Chollo $chollo)
    {
        return view('admin.chollos.formulario', [
            'chollo' => $chollo->load([
                'producto',
                'tienda',
                'categoria',
                'ofertas.tienda',
                'ofertas.producto',
            ]),
            'productoSeleccionado' => $chollo->producto,
            'tiendaSeleccionada' => $chollo->tienda,
            'categoriaSeleccionada' => $chollo->categoria,
            'categoriasRaiz' => $this->obtenerCategoriasRaiz(),
            'url' => $chollo->url,
            'descripcionInternaDefault' => $this->obtenerDescripcionInternaDefault(),
        ]);
    }

    public function update(Request $request, Chollo $chollo)
    {
        $validated = $this->validarFormulario($request, $chollo->id);

        $this->validarLimiteOfertasVinculadas($request, $chollo, $validated['tipo']);

        $this->verificarDuplicadoActivo($validated['url'], $chollo->id, $request);

        $datos = $this->prepararDatos($validated, $request, $chollo);

        $chollo->update($datos);

        $this->procesarOfertasTemporales($request, $chollo);

        return redirect()
            ->route('admin.chollos.index')
            ->with('success', 'Chollo actualizado correctamente');
    }

    // MÉTODO DESTROY COMENTADO POR SEGURIDAD - No se permite eliminar chollos desde el panel
    /*
    public function destroy(Chollo $chollo)
    {
        $chollo->delete();

        return redirect()
            ->route('admin.chollos.index')
            ->with('success', 'Chollo eliminado correctamente');
    }
    */

    public function verificarUrl(Request $request)
    {
        $request->validate([
            'url' => 'nullable|url',
            'chollo_id' => 'nullable|exists:chollos,id',
        ]);

        $url = $request->input('url');

        if (blank($url)) {
            return response()->json([
                'tipo' => 'vacia',
                'mensaje' => 'URL vacía',
            ]);
        }

        $query = Chollo::with(['producto:id,nombre,marca,modelo,talla', 'tienda:id,nombre'])
            ->where('url', $url);

        if ($request->filled('chollo_id')) {
            $query->where('id', '!=', $request->input('chollo_id'));
        }

        $chollos = $query->get();

        if ($chollos->isEmpty()) {
            return response()->json([
                'tipo' => 'disponible',
                'mensaje' => 'Esta URL no existe en otros chollos activos',
            ]);
        }

        $activos = $chollos->filter(fn($chollo) => $this->cholloEstaActivo($chollo));
        $inactivos = $chollos->diff($activos);

        if ($activos->isNotEmpty()) {
            return response()->json([
                'tipo' => 'activo',
                'mensaje' => 'Esta URL ya existe en los siguientes chollos activos',
                'chollos' => $this->formatearListadoChollos($activos),
                'requiere_confirmacion' => true,
            ]);
        }

        return response()->json([
            'tipo' => 'inactivo',
            'mensaje' => 'Esta URL existe en chollos finalizados u ocultos',
            'chollos' => $this->formatearListadoChollos($inactivos),
            'requiere_confirmacion' => false,
        ]);
    }

    private function validarFormulario(Request $request, ?int $cholloId = null): array
    {
        $request->merge([
            'unidades' => $this->normalizarNumero($request->input('unidades')),
            'precio_antiguo' => $this->normalizarNumero($request->input('precio_antiguo')),
            'precio_unidad' => $this->normalizarNumero($request->input('precio_unidad'), 4),
            'categoria_id' => $request->filled('categoria_id') ? $request->input('categoria_id') : null,
            'sin_categoria' => $request->boolean('sin_categoria'),
        ]);

        $tipoSeleccionado = $request->input('tipo', 'producto');

        $esTienda = $tipoSeleccionado === 'tienda';

        $precioNuevoRules = ['string', 'max:255'];
        array_unshift($precioNuevoRules, $esTienda ? 'nullable' : 'required');

        return $request->validate([
            'tipo' => ['required', 'in:producto,oferta,tienda'],
            'producto_id' => [
                'nullable',
                Rule::requiredIf($tipoSeleccionado !== 'tienda'),
                'exists:productos,id',
            ],
            'tienda_id' => ['required', 'exists:tiendas,id'],
            'sin_categoria' => ['nullable', 'boolean'],
            'categoria_id' => [
                Rule::requiredIf(fn () => !$request->boolean('sin_categoria')),
                'nullable',
                'exists:categorias,id',
            ],
            'titulo' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                Rule::unique('chollos', 'slug')->ignore($cholloId),
            ],
            'imagen_grande' => ['nullable', 'string', 'max:255'],
            'imagen_pequena' => ['nullable', 'string', 'max:255'],
            'unidades' => ['nullable', 'numeric', 'min:0'],
            'precio_antiguo' => ['nullable', 'numeric', 'min:0'],
            'precio_nuevo' => $precioNuevoRules,
            'precio_unidad' => ['nullable', 'numeric', 'min:0'],
            'descuentos' => ['nullable', 'string', 'max:255'],
            'gastos_envio' => ['nullable', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string'],
            'descripcion_interna' => ['nullable', 'string'],
            'descripcion_interna_sin_config' => ['nullable', 'string', 'in:0,1'],
            'url' => ['required', 'url'],
            'finalizada' => ['required', 'in:si,no'],
            'mostrar' => ['required', 'in:si,no'],
            'fecha_inicio' => ['nullable', 'date'],
            'fecha_final' => ['nullable', 'date'],
            'comprobada' => ['nullable', 'date'],
            'frecuencia_valor' => ['required', 'numeric', 'min:0.1'],
            'frecuencia_unidad' => ['required', 'in:minutos,horas,dias'],
            'meta_titulo' => ['nullable', 'string', 'max:255'],
            'meta_descripcion' => ['nullable', 'string'],
            'anotaciones_internas' => ['nullable', 'string'],
            'url_duplicate_confirm' => ['nullable', 'boolean'],
        ]);
    }

    private function prepararDatos(array $validated, Request $request, ?Chollo $chollo = null, bool $esCreacion = false): array
    {
        $datos = $validated;

        $datos['tipo'] = $datos['tipo'] ?? 'producto';

        $sinCategoria = $request->boolean('sin_categoria');

        if ($datos['tipo'] === 'tienda') {
            $datos['producto_id'] = $request->filled('producto_id')
                ? (int) $request->input('producto_id')
                : null;
        } elseif (isset($datos['producto_id'])) {
            $datos['producto_id'] = (int) $datos['producto_id'];
        }

        if ($sinCategoria) {
            $datos['categoria_id'] = null;
        } elseif (array_key_exists('categoria_id', $datos)) {
            $datos['categoria_id'] = $datos['categoria_id'] !== null ? (int) $datos['categoria_id'] : null;
        }

        $datos['slug'] = $this->normalizarSlug(
            $validated['slug'] ?? '',
            $validated['titulo'] ?? '',
            $chollo,
            $esCreacion
        );

        $datos['descuentos'] = $this->procesarDescuentos($validated['descuentos'] ?? '', $request);

        // Manejar descripcion_interna según si hay configuración o no
        $esTienda = $datos['tipo'] === 'tienda';
        $sinConfig = $request->input('descripcion_interna_sin_config') === '1';
        $descripcionInternaValue = $datos['descripcion_interna'] ?? null;
        
        if ($sinConfig || (empty($descripcionInternaValue) || trim($descripcionInternaValue) === '')) {
            // Si está marcado como "sin configuración" o está vacío, establecer como null
            $datos['descripcion_interna'] = null;
        } else {
            // Si hay valor, normalizarlo
            $datos['descripcion_interna'] = $this->normalizarDescripcionInterna($descripcionInternaValue);
        }

        $datos['precio_unidad'] = $this->calcularPrecioUnidad(
            $datos['precio_unidad'] ?? null,
            $datos['unidades'] ?? null,
            $validated['precio_nuevo'] ?? ''
        );

        $datos['fecha_inicio'] = $request->filled('fecha_inicio')
            ? Carbon::parse($request->input('fecha_inicio'))
            : null;

        $datos['fecha_final'] = $request->filled('fecha_final')
            ? Carbon::parse($request->input('fecha_final'))
            : null;

        if ($request->filled('comprobada')) {
            $datos['comprobada'] = Carbon::parse($request->input('comprobada'));
        } elseif (!$chollo) {
            $datos['comprobada'] = Carbon::now();
        } else {
            $datos['comprobada'] = $chollo->comprobada;
        }

        $datos['frecuencia_comprobacion_min'] = $this->calcularFrecuenciaEnMinutos($request);

        if ($datos['finalizada'] === 'si' && !$datos['fecha_final']) {
            $datos['fecha_final'] = Carbon::now();
        }

        $datos['frecuencia_comprobacion_min'] = max(1, (int) $datos['frecuencia_comprobacion_min']);

        unset($datos['frecuencia_valor'], $datos['frecuencia_unidad'], $datos['sin_categoria']);
        if (isset($datos['url_duplicate_confirm'])) {
            unset($datos['url_duplicate_confirm']);
        }

        return $datos;
    }

    private function obtenerDescripcionInternaDefault(): string
    {
        $estructura = [
            'cupones' => [
                [
                    'descuento' => 2,
                    'sobrePrecioTotal' => 30,
                    'cupon' => 'ESF1000',
                ],
                [
                    'descuento' => 5,
                    'sobrePrecioTotal' => 50,
                    'cupon' => 'ESF2000',
                ],
                [
                    'descuento' => 10,
                    'sobrePrecioTotal' => 100,
                    'cupon' => 'ESF3000',
                ],
            ],
        ];

        return json_encode($estructura, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    private function normalizarDescripcionInterna(?string $valor): string
    {
        if ($valor === null || trim($valor) === '') {
            return $this->obtenerDescripcionInternaDefault();
        }

        $texto = trim($valor);

        $intentarDecodificar = function (string $contenido) {
            try {
                return json_decode($contenido, true, 512, JSON_THROW_ON_ERROR);
            } catch (\Throwable $e) {
                return null;
            }
        };

        $decodificado = $intentarDecodificar($texto);

        if ($decodificado === null) {
            return $this->obtenerDescripcionInternaDefault();
        }

        if (is_string($decodificado)) {
            $decodificado = $intentarDecodificar($decodificado);
            if ($decodificado === null) {
                return $this->obtenerDescripcionInternaDefault();
            }
        }

        if (isset($decodificado['cupones'])) {
            $cupones = $decodificado['cupones'];
        } elseif (is_array($decodificado)) {
            $cupones = $decodificado;
        } else {
            return $this->obtenerDescripcionInternaDefault();
        }

        if (is_string($cupones)) {
            $cupones = $intentarDecodificar($cupones);
        }

        if (!is_array($cupones)) {
            $cupones = [];
        }

        $normalizados = [];

        foreach ($cupones as $item) {
            if (!is_array($item)) {
                continue;
            }

            $cuponNormalizado = [
                'descuento' => isset($item['descuento']) ? (float) $item['descuento'] : 0.0,
                'sobrePrecioTotal' => isset($item['sobrePrecioTotal']) ? (float) $item['sobrePrecioTotal'] : 0.0,
                'cupon' => isset($item['cupon']) ? (string) $item['cupon'] : '',
            ];
            
            // Preservar tipo_descuento si existe (para "1 Solo cupón")
            if (isset($item['tipo_descuento'])) {
                $cuponNormalizado['tipo_descuento'] = (string) $item['tipo_descuento'];
            }
            
            $normalizados[] = $cuponNormalizado;
        }

        if (empty($normalizados)) {
            $normalizados = [
                [
                    'descuento' => 0.0,
                    'sobrePrecioTotal' => 0.0,
                    'cupon' => '',
                ],
            ];
        }

        // Construir el resultado preservando el tipo si existe
        $resultado = ['cupones' => $normalizados];
        
        // Preservar el campo 'tipo' si existe (para "1 Solo cupón")
        if (isset($decodificado['tipo']) && $decodificado['tipo'] === '1_solo_cupon') {
            $resultado['tipo'] = '1_solo_cupon';
        }

        return json_encode($resultado, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    private function validarLimiteOfertasVinculadas(Request $request, ?Chollo $chollo, string $tipo): void
    {
        if ($tipo !== 'oferta') {
            return;
        }

        $temporalesJson = $request->input('ofertas_temporales');
        $temporales = [];
        if ($temporalesJson) {
            $decoded = json_decode($temporalesJson, true);
            if (is_array($decoded)) {
                $temporales = $decoded;
            }
        }

        $existentesActivos = 0;
        if ($chollo) {
            $existentesActivos = $chollo->ofertas()->count();
        }

        $total = $existentesActivos + count($temporales);

        if ($total > 1) {
            throw ValidationException::withMessages([
                'ofertas_temporales' => 'Los chollos de tipo oferta solo pueden tener una oferta vinculada.',
            ]);
        }
    }

    private function normalizarSlug(?string $slug, ?string $titulo = null, ?Chollo $chollo = null, bool $esCreacion = false): string
    {
        $slug = $slug ?? '';
        $slugLimpio = Str::slug($slug);

        if ($slugLimpio === '' && $titulo) {
            $slugLimpio = Str::slug($titulo);
        }

        if ($slugLimpio === '' && $chollo) {
            return $chollo->slug;
        }

        if ($slugLimpio === '') {
            $slugLimpio = 'chollo';
        }

        if ($esCreacion) {
            $sinId = preg_replace('/-\d+$/', '', $slugLimpio);
            $slugLimpio = $sinId !== null && $sinId !== '' ? $sinId : $slugLimpio;
        }

        return $slugLimpio;
    }

    private function construirSlugFinal(string $slugBase, int $id): string
    {
        $sinId = preg_replace('/-\d+$/', '', $slugBase);
        $base = $sinId !== null && $sinId !== '' ? $sinId : $slugBase;
        $base = trim($base, '-');

        if ($base === '') {
            $base = 'chollo';
        }

        return $base . '-' . $id;
    }

    private function procesarDescuentos(?string $descuentos, Request $request): ?string
    {
        if ($descuentos === null || $descuentos === '') {
            return null;
        }

        if ($descuentos === 'cupon') {
            $codigo = trim($request->input('cupon_codigo', ''));
            $cantidad = $this->normalizarNumero($request->input('cupon_cantidad'));

            if ($codigo && $cantidad !== null) {
                return 'cupon;' . $codigo . ';' . $cantidad;
            }

            if ($cantidad !== null) {
                return 'cupon;' . $cantidad;
            }
        }

        return $descuentos;
    }

    private function calcularPrecioUnidad($precioUnidad, $unidades, string $precioNuevoTexto): ?float
    {
        if ($precioUnidad !== null) {
            return $precioUnidad;
        }

        if (!$unidades || $unidades <= 0) {
            return null;
        }

        if (preg_match('/\d+[.,]?\d*/', $precioNuevoTexto, $matches)) {
            $precio = $this->normalizarNumero($matches[0], 4);
            if ($precio !== null && $precio > 0) {
                return round($precio / $unidades, 4);
            }
        }

        return null;
    }

    private function verificarDuplicadoActivo(string $url, ?int $ignorarId, Request $request): void
    {
        $existeActivo = Chollo::where('url', $url)
            ->when($ignorarId, fn($q) => $q->where('id', '!=', $ignorarId))
            ->where('mostrar', 'si')
            ->where(function ($q) {
                $q->whereNull('fecha_final')
                    ->orWhere('fecha_final', '>', Carbon::now());
            })
            ->exists();

        if ($existeActivo && !$request->boolean('url_duplicate_confirm')) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'url' => 'Existe un chollo activo con esta URL. Confirma que deseas continuar.',
            ]);
        }
    }

    private function cholloEstaActivo(Chollo $chollo): bool
    {
        if ($chollo->mostrar !== 'si') {
            return false;
        }

        if ($chollo->fecha_final === null) {
            return true;
        }

        return $chollo->fecha_final->isFuture();
    }

    private function formatearListadoChollos($chollos): array
    {
        return $chollos->map(function ($chollo) {
            return [
                'id' => $chollo->id,
                'titulo' => $chollo->titulo,
                'producto' => $chollo->producto?->nombre,
                'tienda' => $chollo->tienda?->nombre,
                'fecha_final' => $chollo->fecha_final?->format('d/m/Y H:i'),
                'mostrar' => $chollo->mostrar,
            ];
        })->values()->toArray();
    }

    private function procesarOfertasTemporales(Request $request, Chollo $chollo): void
    {
        $ofertasJson = $request->input('ofertas_temporales');

        if (!$ofertasJson) {
            return;
        }

        $ofertas = json_decode($ofertasJson, true);

        if (!is_array($ofertas) || empty($ofertas)) {
            return;
        }

        $ofertasExistentesActivas = $chollo->ofertas()->count();

        if ($chollo->tipo === 'oferta') {
            $capacidadDisponible = max(0, 1 - $ofertasExistentesActivas);
            if ($capacidadDisponible <= 0) {
                return;
            }

            if (count($ofertas) > $capacidadDisponible) {
                $ofertas = array_slice($ofertas, 0, $capacidadDisponible);
            }

            if (empty($ofertas)) {
                return;
            }
        }

        foreach ($ofertas as $indice => $ofertaDatos) {
            try {
                $payload = $this->validarOfertaTemporal($ofertaDatos, $chollo);
                OfertaProducto::create($payload);
            } catch (\Throwable $e) {
                \Log::warning('Error al crear oferta temporal para chollo', [
                    'chollo_id' => $chollo->id,
                    'indice' => $indice,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }


    private function validarOfertaTemporal(array $datos, Chollo $chollo): array
    {
        $datosNormalizados = $datos;

        if (isset($datosNormalizados['precio_total'])) {
            $datosNormalizados['precio_total'] = str_replace(',', '.', $datosNormalizados['precio_total']);
        }

        if (isset($datosNormalizados['precio_unidad'])) {
            $datosNormalizados['precio_unidad'] = str_replace(',', '.', $datosNormalizados['precio_unidad']);
        }

        if (isset($datosNormalizados['unidades'])) {
            $datosNormalizados['unidades'] = str_replace(',', '.', $datosNormalizados['unidades']);
        }

        $validator = Validator::make($datosNormalizados, [
            'producto_id' => ['nullable', 'exists:productos,id'],
            'tienda_id' => ['required', 'exists:tiendas,id'],
            'unidades' => ['required', 'numeric', 'min:0.01'],
            'precio_total' => ['required', 'numeric', 'min:0'],
            'precio_unidad' => ['nullable', 'numeric', 'min:0'],
            'url' => ['required', 'url'],
            'variante' => ['nullable', 'string', 'max:255'],
            'descuentos' => ['nullable', 'string', 'max:255'],
            'mostrar' => ['nullable', 'in:si,no'],
            'frecuencia_valor' => ['required', 'numeric', 'min:0.1'],
            'frecuencia_unidad' => ['required', 'in:minutos,horas,dias'],
            'fecha_inicio' => ['required', 'date'],
            'fecha_final' => ['nullable', 'date'],
            'comprobada' => ['nullable', 'date'],
            'frecuencia_chollo_valor' => ['required', 'numeric', 'min:0.1'],
            'frecuencia_chollo_unidad' => ['required', 'in:minutos,horas,dias'],
            'anotaciones_internas' => ['nullable', 'string'],
        ]);

        $validated = $validator->validate();

        $productoId = $validated['producto_id'] ?? $chollo->producto_id;
        $productoId = (int) $productoId;
        if ($productoId <= 0) {
            $productoId = (int) $chollo->producto_id;
        }

        $precioTotal = $this->normalizarNumero($validated['precio_total']);
        $unidades = $this->normalizarNumero($validated['unidades']);
        $precioUnidad = $this->normalizarNumero($validated['precio_unidad'] ?? null, 4);

        if ($precioUnidad === null && $unidades !== null && $unidades > 0 && $precioTotal !== null) {
            $precioUnidad = round($precioTotal / $unidades, 4);
        }

        $frecuenciaPrecioMin = $this->convertirFrecuenciaEnMinutos(
            $validated['frecuencia_valor'],
            $validated['frecuencia_unidad']
        );

        $frecuenciaCholloMin = $this->convertirFrecuenciaEnMinutos(
            $validated['frecuencia_chollo_valor'],
            $validated['frecuencia_chollo_unidad']
        );

        return [
            'producto_id' => $productoId,
            'tienda_id' => (int) $validated['tienda_id'],
            'chollo_id' => $chollo->id,
            'unidades' => $unidades,
            'precio_total' => $precioTotal,
            'precio_unidad' => $precioUnidad,
            'frecuencia_actualizar_precio_minutos' => $frecuenciaPrecioMin,
            'url' => $validated['url'],
            'variante' => $validated['variante'] ?? null,
            'mostrar' => $validated['mostrar'] ?? 'si',
            'descuentos' => $validated['descuentos'] ?? null,
            'anotaciones_internas' => $validated['anotaciones_internas'] ?? null,
            'fecha_inicio' => Carbon::parse($validated['fecha_inicio']),
            'fecha_final' => $validated['fecha_final'] ? Carbon::parse($validated['fecha_final']) : null,
            'comprobada' => $validated['comprobada'] ? Carbon::parse($validated['comprobada']) : null,
            'frecuencia_comprobacion_chollos_min' => $frecuenciaCholloMin,
            'clicks' => 0,
        ];
    }

    private function convertirFrecuenciaEnMinutos($valor, string $unidad): int
    {
        $valorNumerico = (float) $valor;

        return (int) match ($unidad) {
            'minutos' => round($valorNumerico),
            'horas' => round($valorNumerico * 60),
            'dias' => round($valorNumerico * 1440),
            default => 1440,
        };
    }

    private function obtenerCategoriasRaiz()
    {
        return Categoria::query()
            ->whereNull('parent_id')
            ->orderBy('nombre')
            ->get(['id', 'nombre'])
            ->map(fn($categoria) => [
                'id' => $categoria->id,
                'nombre' => $categoria->nombre,
            ])
            ->toArray();
    }

    private function normalizarNumero($valor, int $decimales = 2): ?float
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        if (is_string($valor)) {
            $valor = str_replace(',', '.', $valor);
        }

        if (!is_numeric($valor)) {
            return null;
        }

        return round((float) $valor, $decimales);
    }

    private function calcularFrecuenciaEnMinutos(Request $request): int
    {
        $valor = (float) $request->input('frecuencia_valor', 1);
        $unidad = $request->input('frecuencia_unidad', 'dias');

        $minutos = match ($unidad) {
            'minutos' => $valor,
            'horas' => $valor * 60,
            'dias' => $valor * 1440,
            default => 1440,
        };

        return (int) round(max($minutos, 1));
    }

    public function show($slug)
    {
        $chollo = Chollo::with(['tienda', 'ofertas.producto.categoria', 'producto', 'categoria'])
            ->where('slug', $slug)
            ->where('mostrar', 'si')
            ->firstOrFail();

        $preciosHot = $this->obtenerPreciosHotPublico();

        // Obtener productos rebajados de la categoría del chollo (si tiene categoría)
        $productosRebajados = collect();
        if ($chollo->categoria && $chollo->categoria->nombre) {
            $precioHotCategoria = PrecioHot::where('nombre', $chollo->categoria->nombre)->first();
            
            if ($precioHotCategoria && !empty($precioHotCategoria->datos)) {
                $productosRebajados = collect($precioHotCategoria->datos)
                    ->take(10)
                    ->map(function ($item) {
                        $producto = Producto::find($item['producto_id'] ?? null);
                        if (!$producto) {
                            return null;
                        }

                        return [
                            'producto' => $producto,
                            'oferta_id' => $item['oferta_id'] ?? null,
                            'tienda_id' => $item['tienda_id'] ?? null,
                            'img_tienda' => $item['img_tienda'] ?? 'tiendas/carrefour.png',
                            'img_producto' => $item['img_producto'] ?? 'panales/chelino-nature-talla-1.jpg',
                            'precio_oferta' => $item['precio_oferta'] ?? 0,
                            'precio_formateado' => $item['precio_formateado'] ?? number_format($item['precio_oferta'] ?? 0, 2, ',', '.') . ' €/Und.',
                            'porcentaje_diferencia' => $item['porcentaje_diferencia'] ?? 0,
                            'url_oferta' => $item['url_oferta'] ?? '#',
                            'url_producto' => $item['url_producto'] ?? '#',
                            'producto_nombre' => $item['producto_nombre'] ?? $producto->nombre,
                            'tienda_nombre' => $item['tienda_nombre'] ?? 'Tienda desconocida',
                            'unidades' => $item['unidades'] ?? 1,
                            'unidades_formateadas' => $item['unidades_formateadas'] ?? number_format($item['unidades'] ?? 1, 0, ',', '.') . ' Unidades',
                            'unidad_medida' => $item['unidad_medida'] ?? $producto->unidadDeMedida
                        ];
                    })
                    ->filter()
                    ->values();
            }
        }

        // Obtener chollos relacionados (También te podría interesar)
        $chollosRelacionados = $this->obtenerChollosRelacionados($chollo, 20);

        return view('chollos.vistaChollo', compact('chollo', 'preciosHot', 'productosRebajados', 'chollosRelacionados'));
    }

    /**
     * Obtiene chollos relacionados basándose en la categoría del chollo
     * Busca primero en su categoría, luego en categorías padre, y finalmente en general
     */
    private function obtenerChollosRelacionados($chollo, $limite = 20)
    {
        $chollosRelacionados = collect();
        $ahora = Carbon::now();
        $excluirIds = [$chollo->id]; // Excluir el chollo actual

        // Query base para chollos activos
        $queryBase = function() use ($ahora) {
            return Chollo::with(['tienda', 'categoria'])
                ->where('mostrar', 'si')
                ->where('finalizada', 'no')
                ->where('fecha_inicio', '<=', $ahora)
                ->where(function($query) use ($ahora) {
                    $query->whereNull('fecha_final')
                          ->orWhere('fecha_final', '>=', $ahora);
                });
        };

        // Si el chollo tiene categoría, buscar primero en su jerarquía
        if ($chollo->categoria_id && $chollo->categoria) {
            $jerarquiaCategorias = $chollo->categoria->obtenerJerarquiaCompleta();
            // Invertir el orden para buscar primero en la categoría más específica (actual), luego en padres
            $jerarquiaCategorias = array_reverse($jerarquiaCategorias);

            // Buscar en orden: categoría actual, luego padres
            foreach ($jerarquiaCategorias as $categoria) {
                if ($chollosRelacionados->count() >= $limite) {
                    break;
                }

                $chollosCategoria = $queryBase()
                    ->where('categoria_id', $categoria->id)
                    ->whereNotIn('id', $excluirIds)
                    ->orderBy('created_at', 'desc')
                    ->take($limite - $chollosRelacionados->count())
                    ->get();

                foreach ($chollosCategoria as $cholloRel) {
                    if ($chollosRelacionados->count() >= $limite) {
                        break;
                    }
                    if (!$chollosRelacionados->contains('id', $cholloRel->id)) {
                        $chollosRelacionados->push($cholloRel);
                        $excluirIds[] = $cholloRel->id;
                    }
                }
            }
        }

        // Si aún no hay suficientes, buscar los últimos publicados generales
        if ($chollosRelacionados->count() < $limite) {
            $chollosGenerales = $queryBase()
                ->whereNotIn('id', $excluirIds)
                ->orderBy('created_at', 'desc')
                ->take($limite - $chollosRelacionados->count())
                ->get();

            $chollosRelacionados = $chollosRelacionados->merge($chollosGenerales);
        }

        return $chollosRelacionados->take($limite);
    }

    public function listadoPublico(Request $request)
    {
        $perPage = (int) $request->input('per_page', 12);
        $perPage = $perPage > 0 ? min($perPage, 40) : 12;

        $fechaLimite = Carbon::now()->subDays(7);

        $chollos = Chollo::with(['tienda', 'ofertas.producto.categoria'])
            ->where('mostrar', 'si')
            ->where('created_at', '>=', $fechaLimite)
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->appends($request->except('page'));

        $preciosHot = $this->obtenerPreciosHotPublico();
        $categorias = Categoria::orderBy('nombre')->get(['id', 'nombre']);

        $esAjax = $request->boolean('ajax');
        $view = view('chollos.listado_chollos', compact('chollos', 'preciosHot', 'categorias', 'esAjax'));

        if ($esAjax) {
            $html = $view->render();
            $nextPage = $chollos->nextPageUrl();
            if ($nextPage) {
                $nextPage .= Str::contains($nextPage, '?') ? '&' : '?';
                $nextPage .= 'ajax=1';
            }

            return response()->json([
                'html' => $html,
                'next_page' => $nextPage,
            ]);
        }

        return $view;
    }

    private function obtenerPreciosHotPublico(): ?PrecioHot
    {
        $preciosHot = PrecioHot::where('nombre', 'Precios Hot')->first();

        if (!$preciosHot || empty($preciosHot->datos)) {
            return $preciosHot;
        }

        $preciosHot->datos = collect($preciosHot->datos)
            ->map(function ($item) {
                if (!is_array($item)) {
                    return null;
                }

                return [
                    'producto_id' => $item['producto_id'] ?? null,
                    'oferta_id' => $item['oferta_id'] ?? null,
                    'tienda_id' => $item['tienda_id'] ?? null,
                    'img_tienda' => $item['img_tienda'] ?? 'tiendas/carrefour.png',
                    'img_producto' => $item['img_producto'] ?? 'panales/chelino-nature-talla-1.jpg',
                    'precio_oferta' => $item['precio_oferta'] ?? 0,
                    'precio_formateado' => $item['precio_formateado'] ?? number_format($item['precio_oferta'] ?? 0, 2, ',', '.') . ' €',
                    'porcentaje_diferencia' => $item['porcentaje_diferencia'] ?? 0,
                    'url_oferta' => $item['url_oferta'] ?? '#',
                    'url_producto' => $item['url_producto'] ?? '#',
                    'producto_nombre' => $item['producto_nombre'] ?? 'Producto desconocido',
                    'tienda_nombre' => $item['tienda_nombre'] ?? 'Tienda desconocida',
                    'unidades' => $item['unidades'] ?? 1,
                    'unidades_formateadas' => $item['unidades_formateadas'] ?? number_format($item['unidades'] ?? 1, 0, ',', '.') . ' unidades',
                    'unidad_medida' => $item['unidad_medida'] ?? 'unidad',
                ];
            })
            ->filter()
            ->toArray();

        return $preciosHot;
    }

    /**
     * Comprobar y finalizar chollos y ofertas vencidas
     * Ejecuta en segundo plano para cron jobs
     */
    public function comprobarChollosYOfertasFinalizadas(Request $request)
    {
        // Verificar token de seguridad
        $token = $request->query('token');
        if (!$token || $token !== env('TOKEN_ACTUALIZAR_PRECIOS')) {
            return response()->json([
                'status' => 'error',
                'message' => 'Token inválido'
            ], 403);
        }

        $ahora = Carbon::now();
        $chollosFinalizados = 0;
        $ofertasOcultadas = 0;
        $ofertasOcultadasPorFecha = 0;
        $chollosFinalizadosPorAntiguedad = 0;
        $ofertasOcultadasPorAntiguedad = 0;
        $log = [];

        try {
            // 1. Buscar chollos con fecha_final < ahora y finalizada = 'no'
            $chollosVencidos = Chollo::where('finalizada', 'no')
                ->whereNotNull('fecha_final')
                ->where('fecha_final', '<', $ahora)
                ->get();

            foreach ($chollosVencidos as $chollo) {
                // Marcar chollo como finalizada
                $chollo->update(['finalizada' => 'si']);
                $chollosFinalizados++;

                // 2. Buscar ofertas vinculadas a este chollo con mostrar = 'si'
                $ofertasVinculadas = OfertaProducto::where('chollo_id', $chollo->id)
                    ->where('mostrar', 'si')
                    ->get();

                foreach ($ofertasVinculadas as $oferta) {
                    $oferta->update(['mostrar' => 'no']);
                    $ofertasOcultadas++;
                }

                $log[] = [
                    'chollo_id' => $chollo->id,
                    'titulo' => $chollo->titulo,
                    'ofertas_ocultadas' => $ofertasVinculadas->count()
                ];
            }

            // 3. Buscar ofertas con chollo_id != null, fecha_final < ahora y mostrar = 'si'
            $ofertasVencidas = OfertaProducto::whereNotNull('chollo_id')
                ->whereNotNull('fecha_final')
                ->where('fecha_final', '<', $ahora)
                ->where('mostrar', 'si')
                ->get();

            foreach ($ofertasVencidas as $oferta) {
                $oferta->update(['mostrar' => 'no']);
                $ofertasOcultadasPorFecha++;
            }

            // 4. Buscar chollos con más de un mes desde fecha_inicio, sin fecha_final y finalizada = 'no'
            $fechaLimiteUnMes = Carbon::now()->subMonth();
            $chollosAntiguos = Chollo::where('finalizada', 'no')
                ->whereNull('fecha_final')
                ->whereNotNull('fecha_inicio')
                ->where('fecha_inicio', '<', $fechaLimiteUnMes)
                ->get();

            foreach ($chollosAntiguos as $chollo) {
                // Marcar chollo como finalizada
                $chollo->update(['finalizada' => 'si']);
                $chollosFinalizadosPorAntiguedad++;

                // Buscar ofertas vinculadas a este chollo y ocultarlas
                $ofertasVinculadas = OfertaProducto::where('chollo_id', $chollo->id)
                    ->where('mostrar', 'si')
                    ->get();

                foreach ($ofertasVinculadas as $oferta) {
                    $oferta->update(['mostrar' => 'no']);
                    $ofertasOcultadasPorAntiguedad++;
                }

                $log[] = [
                    'chollo_id' => $chollo->id,
                    'titulo' => $chollo->titulo,
                    'tipo' => 'antiguedad',
                    'ofertas_ocultadas' => $ofertasVinculadas->count()
                ];
            }

            return response()->json([
                'status' => 'ok',
                'message' => 'Proceso completado correctamente',
                'chollos_finalizados' => $chollosFinalizados,
                'chollos_finalizados_por_antiguedad' => $chollosFinalizadosPorAntiguedad,
                'ofertas_ocultadas_por_chollo' => $ofertasOcultadas,
                'ofertas_ocultadas_por_fecha' => $ofertasOcultadasPorFecha,
                'ofertas_ocultadas_por_antiguedad' => $ofertasOcultadasPorAntiguedad,
                'total_chollos_finalizados' => $chollosFinalizados + $chollosFinalizadosPorAntiguedad,
                'total_ofertas_ocultadas' => $ofertasOcultadas + $ofertasOcultadasPorFecha + $ofertasOcultadasPorAntiguedad,
                'log' => $log
            ]);

        } catch (\Exception $e) {
            \Log::error('Error al comprobar chollos y ofertas finalizadas', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Error en el proceso: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Listar ofertas de chollos pendientes de comprobar
     */
    public function comprobarChollos(Request $request)
    {
        $perPage = (int) $request->input('perPage', 20);
        $ahora = Carbon::now();

        // Obtener ofertas de chollos que cumplen los criterios básicos
        $ofertasChollos = OfertaProducto::with(['producto', 'tienda', 'chollo'])
            ->whereNotNull('chollo_id')
            ->whereNotNull('frecuencia_comprobacion_chollos_min')
            ->whereHas('chollo', function ($query) use ($ahora) {
                $query->where('finalizada', 'no')
                    ->where('mostrar', 'si')
                    ->where('fecha_inicio', '<=', $ahora)
                    ->where(function ($q) use ($ahora) {
                        $q->whereNull('fecha_final')
                            ->orWhere('fecha_final', '>', $ahora);
                    });
            })
            ->get()
            ->filter(function ($oferta) use ($ahora) {
                // Si nunca se ha comprobado, está pendiente
                if (!$oferta->comprobada) {
                    return true;
                }

                // Calcular minutos desde la última comprobación
                $minutosDesdeComprobada = $ahora->diffInMinutes($oferta->comprobada);
                
                // Verificar si ha pasado el tiempo de frecuencia
                return $minutosDesdeComprobada >= $oferta->frecuencia_comprobacion_chollos_min;
            });

        // Obtener ofertas manuales que han superado su tiempo de actualización
        $ofertasManuales = OfertaProducto::with(['producto', 'tienda', 'chollo'])
            ->where('mostrar', 'si')
            ->where('como_scrapear', 'manual')
            ->whereNull('chollo_id')
            ->whereHas('tienda', function($query) {
                $query->where('scrapear', 'si');
            })
            ->whereRaw('TIMESTAMPDIFF(MINUTE, updated_at, NOW()) >= frecuencia_actualizar_precio_minutos')
            ->get();

        // Combinar ambas colecciones
        $ofertas = $ofertasChollos->concat($ofertasManuales)
            ->sortBy(function ($oferta) {
                // Ordenar por fecha de comprobada (null primero) para chollos
                // Para ofertas manuales, usar updated_at
                if ($oferta->chollo_id) {
                    return $oferta->comprobada ? $oferta->comprobada->timestamp : 0;
                } else {
                    return $oferta->updated_at ? $oferta->updated_at->timestamp : 0;
                }
            })
            ->values();

        // Paginar manualmente
        $currentPage = $request->input('page', 1);
        $perPage = max(1, $perPage);
        $total = $ofertas->count();
        $items = $ofertas->slice(($currentPage - 1) * $perPage, $perPage)->values();
        
        $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('admin.chollos.comprobar-chollos', ['ofertas' => $paginator, 'perPage' => $perPage]);
    }

    /**
     * Marcar oferta como comprobada y actualizar precio
     */
    public function marcarComprobada(Request $request, OfertaProducto $oferta)
    {
        $validated = $request->validate([
            'precio_total' => 'required|numeric|min:0',
            'descuentos' => 'nullable|string|max:255',
            'mostrar' => 'nullable|in:si,no',
        ]);

        $precioTotal = (float) $validated['precio_total'];
        $unidades = $oferta->unidades;

        if ($unidades <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Las unidades deben ser mayores que 0'
            ], 422);
        }

        $precioUnidad = round($precioTotal / $unidades, 3);

        $datosActualizar = [
            'comprobada' => Carbon::now(),
            'precio_total' => $precioTotal,
            'precio_unidad' => $precioUnidad,
        ];

        // Actualizar descuentos si se proporciona
        if ($request->has('descuentos')) {
            $datosActualizar['descuentos'] = $request->input('descuentos') ?: null;
        }

        // Actualizar mostrar si se proporciona
        if ($request->has('mostrar')) {
            $datosActualizar['mostrar'] = $request->input('mostrar');
        }

        // Si es una oferta manual (sin chollo_id), actualizar también updated_at
        if (!$oferta->chollo_id) {
            $datosActualizar['updated_at'] = Carbon::now();
        }

        $oferta->update($datosActualizar);
        
        // Si es una oferta manual (sin chollo_id), registrar en historial de tiempos dinámicos
        if (!$oferta->chollo_id) {
            $serviceTiempos = new TiemposActualizacionOfertasDinamicos();
            $serviceTiempos->registrarActualizacion($oferta->id, $precioTotal, 'manual');
        }

        return response()->json([
            'success' => true,
            'message' => 'Oferta marcada como comprobada',
            'precio_unidad' => $precioUnidad
        ]);
    }

    /**
     * Contar ofertas pendientes de comprobar
     */
    public function contarPendientes()
    {
        $ahora = Carbon::now();

        $ofertas = OfertaProducto::with('chollo')
            ->whereNotNull('chollo_id')
            ->whereNotNull('frecuencia_comprobacion_chollos_min')
            ->whereHas('chollo', function ($query) use ($ahora) {
                $query->where('finalizada', 'no')
                    ->where('mostrar', 'si')
                    ->where('fecha_inicio', '<=', $ahora)
                    ->where(function ($q) use ($ahora) {
                        $q->whereNull('fecha_final')
                            ->orWhere('fecha_final', '>', $ahora);
                    });
            })
            ->get()
            ->filter(function ($oferta) use ($ahora) {
                // Si nunca se ha comprobado, está pendiente
                if (!$oferta->comprobada) {
                    return true;
                }

                // Calcular minutos desde la última comprobación
                $minutosDesdeComprobada = $ahora->diffInMinutes($oferta->comprobada);
                
                // Verificar si ha pasado el tiempo de frecuencia
                return $minutosDesdeComprobada >= $oferta->frecuencia_comprobacion_chollos_min;
            });

        return $ofertas->count();
    }

    /**
     * Ocultar múltiples ofertas (marcar mostrar = 'no')
     */
    public function ocultarMultiples(Request $request)
    {
        $validated = $request->validate([
            'ofertas_ids' => 'required|array',
            'ofertas_ids.*' => 'required|exists:ofertas_producto,id',
        ]);

        $actualizadas = 0;
        $errores = 0;
        $log = [];

        foreach ($validated['ofertas_ids'] as $ofertaId) {
            try {
                $oferta = OfertaProducto::findOrFail($ofertaId);
                $oferta->update(['mostrar' => 'no']);
                $actualizadas++;

            } catch (\Exception $e) {
                $errores++;
                $log[] = [
                    'oferta_id' => $ofertaId,
                    'error' => $e->getMessage()
                ];
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Se ocultaron {$actualizadas} ofertas",
            'actualizadas' => $actualizadas,
            'errores' => $errores,
            'log' => $log
        ]);
    }

    /**
     * Aplicar fechas y cupones del chollo a todas sus ofertas vinculadas
     */
    public function aplicarFechasYCupones(Request $request, Chollo $chollo)
    {
        // Verificar que el chollo sea de tipo tienda
        if ($chollo->tipo !== 'tienda') {
            return response()->json([
                'success' => false,
                'message' => 'Esta acción solo está disponible para chollos de tipo tienda.'
            ], 400);
        }

        // Verificar que tenga configuración de cupones
        $descripcionInterna = $chollo->descripcion_interna;
        if (empty($descripcionInterna)) {
            return response()->json([
                'success' => false,
                'message' => 'El chollo no tiene configuración de cupones.'
            ], 400);
        }

        // Parsear descripción interna para obtener cupones
        $decoded = json_decode($descripcionInterna, true);
        if (!is_array($decoded) || !isset($decoded['cupones']) || !is_array($decoded['cupones']) || count($decoded['cupones']) === 0) {
            return response()->json([
                'success' => false,
                'message' => 'El chollo no tiene cupones configurados.'
            ], 400);
        }

        // Si es tipo "1_solo_cupon", tomar el primer cupón
        if (isset($decoded['tipo']) && $decoded['tipo'] === '1_solo_cupon') {
            $cupones = isset($decoded['cupones'][0]) ? [$decoded['cupones'][0]] : [];
        } else {
            $cupones = $decoded['cupones'];
        }

        // Obtener el valor de mostrar del request (por defecto 'si')
        $mostrarAplicar = $request->input('mostrar', 'si');
        if (!in_array($mostrarAplicar, ['si', 'no'])) {
            $mostrarAplicar = 'si';
        }

        // Obtener todas las ofertas vinculadas al chollo
        $ofertas = $chollo->ofertas()->with('tienda')->get();

        $actualizadas = 0;
        $sinCoincidencia = 0;
        $errores = 0;
        $ofertasSinCoincidencia = [];
        $ofertasActualizadas = [];

        foreach ($ofertas as $oferta) {
            try {
                $datosActualizar = [];

                // Aplicar fechas del chollo siempre
                $datosActualizar['fecha_inicio'] = $chollo->fecha_inicio;
                $datosActualizar['fecha_final'] = $chollo->fecha_final;

                // Aplicar el valor de mostrar seleccionado
                $datosActualizar['mostrar'] = $mostrarAplicar;

                // Buscar coincidencia de cupón
                $descuentos = $oferta->descuentos ?? '';
                $cuponEncontrado = false;
                $nuevoCodigoCupon = null;

                // Si la oferta tiene descuentos con formato "cupon;codigo;cantidad"
                if (!empty($descuentos) && str_starts_with($descuentos, 'cupon;')) {
                    $partes = explode(';', $descuentos);
                    if (count($partes) >= 3) {
                        $codigoCuponActual = $partes[1];
                        $cantidadDescuentoActual = floatval($partes[2]);

                        // Buscar en los cupones del chollo uno que coincida con el descuento
                        foreach ($cupones as $cupon) {
                            $descuentoCupon = floatval($cupon['descuento'] ?? 0);
                            
                            // Comparar descuentos (considerando pequeñas diferencias por redondeo)
                            if (abs($descuentoCupon - $cantidadDescuentoActual) < 0.01) {
                                $nuevoCodigoCupon = $cupon['cupon'] ?? '';
                                $cuponEncontrado = true;
                                break;
                            }
                        }

                        if ($cuponEncontrado && !empty($nuevoCodigoCupon)) {
                            // Actualizar el código del cupón manteniendo el formato
                            $datosActualizar['descuentos'] = "cupon;{$nuevoCodigoCupon};{$cantidadDescuentoActual}";
                            $actualizadas++;
                            $ofertasActualizadas[] = [
                                'id' => $oferta->id,
                                'tienda' => $oferta->tienda->nombre ?? 'Sin tienda',
                                'codigo_anterior' => $codigoCuponActual,
                                'codigo_nuevo' => $nuevoCodigoCupon
                            ];
                        } else {
                            // No se encontró coincidencia
                            $sinCoincidencia++;
                            $ofertasSinCoincidencia[] = [
                                'id' => $oferta->id,
                                'tienda' => $oferta->tienda->nombre ?? 'Sin tienda',
                                'descuento_actual' => $cantidadDescuentoActual,
                                'codigo_actual' => $codigoCuponActual
                            ];
                        }
                    } else {
                        // Formato incorrecto
                        $sinCoincidencia++;
                        $ofertasSinCoincidencia[] = [
                            'id' => $oferta->id,
                            'tienda' => $oferta->tienda->nombre ?? 'Sin tienda',
                            'error' => 'Formato de descuentos incorrecto'
                        ];
                    }
                } else {
                    // La oferta no tiene cupón configurado
                    $sinCoincidencia++;
                    $ofertasSinCoincidencia[] = [
                        'id' => $oferta->id,
                        'tienda' => $oferta->tienda->nombre ?? 'Sin tienda',
                        'error' => 'La oferta no tiene cupón configurado'
                    ];
                }

                // Actualizar la oferta
                $oferta->update($datosActualizar);

            } catch (\Exception $e) {
                $errores++;
                $ofertasSinCoincidencia[] = [
                    'id' => $oferta->id,
                    'tienda' => $oferta->tienda->nombre ?? 'Sin tienda',
                    'error' => $e->getMessage()
                ];
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Proceso completado. Actualizadas: {$actualizadas}, Sin coincidencia: {$sinCoincidencia}, Errores: {$errores}",
            'actualizadas' => $actualizadas,
            'sin_coincidencia' => $sinCoincidencia,
            'errores' => $errores,
            'ofertas_actualizadas' => $ofertasActualizadas,
            'ofertas_sin_coincidencia' => $ofertasSinCoincidencia
        ]);
    }
}

