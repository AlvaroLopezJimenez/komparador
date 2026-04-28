<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\Neo;
use App\Models\Neoobjetivo;
use App\Models\OfertaProducto;
use App\Models\Producto;
use App\Models\Tienda;
use App\Models\UrlDescartada;
use App\Services\ConsultarNeoCifrado;
use App\Services\LimpiarUrlDeTiendas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NeoController extends Controller
{
    /**
     * Vista crear ofertas en masa desde neo: muestra cantidad de filas neo con aniadida=no
     * y permite cargar URLs por producto (neo con aniadida=no agrupados por producto_id).
     */
    public function crearMasivo()
    {
        $totalNeoAniadidaNo = Neo::where('aniadida', 'no')->count();
        $totalNeoAniadidaNoSinUrl = Neo::where('aniadida', 'no')
            ->where(function ($q) {
                $q->whereNull('url_cipher')->orWhere('url_cipher', '');
            })
            ->count();
        $totalProductosNeoAniadidaNo = (int) Neo::where('aniadida', 'no')
            ->whereNotNull('url_cipher')
            ->where('url_cipher', '!=', '')
            ->whereNotNull('producto_id')
            ->selectRaw('count(distinct producto_id) as c')
            ->value('c');
        $totalCategoriasNeoAniadidaNo = (int) Neo::where('aniadida', 'no')
            ->whereNotNull('url_cipher')
            ->where('url_cipher', '!=', '')
            ->whereNotNull('categoria_id')
            ->selectRaw('count(distinct categoria_id) as c')
            ->value('c');
        $totalTiendasNeoAniadidaNo = (int) Neo::where('aniadida', 'no')
            ->whereNotNull('url_cipher')
            ->where('url_cipher', '!=', '')
            ->whereNotNull('tienda_id')
            ->selectRaw('count(distinct tienda_id) as c')
            ->value('c');

        $categoriasRaiz = Categoria::categoriasRaizConConteosAdministracion();

        return view('admin.neo.crear-masivo', compact(
            'totalNeoAniadidaNo',
            'totalNeoAniadidaNoSinUrl',
            'totalProductosNeoAniadidaNo',
            'totalCategoriasNeoAniadidaNo',
            'totalTiendasNeoAniadidaNo',
            'categoriasRaiz'
        ));
    }

    /**
     * Productos que tienen al menos una fila en neo con aniadida=no (agrupados por producto_id).
     * Para el botón "Producto" en crear-masivo neo.
     */
    public function productosConNeoAniadidaNo()
    {
        $grupos = Neo::where('aniadida', 'no')
            ->whereNotNull('url_cipher')
            ->where('url_cipher', '!=', '')
            ->whereNotNull('producto_id')
            ->selectRaw('producto_id, count(*) as total')
            ->groupBy('producto_id')
            ->get();

        $productos = Producto::whereIn('id', $grupos->pluck('producto_id'))
            ->get(['id', 'nombre', 'marca', 'modelo', 'talla'])
            ->keyBy('id');

        $lista = $grupos->map(function ($g) use ($productos) {
            $p = $productos->get($g->producto_id);
            $texto = $p
                ? trim($p->nombre . ' - ' . ($p->marca ?? '') . ' - ' . ($p->modelo ?? '') . ' - ' . ($p->talla ?? ''), ' -')
                : 'Producto #' . $g->producto_id;

            return [
                'producto_id' => $g->producto_id,
                'texto_completo' => $texto,
                'count' => (int) $g->total,
            ];
        })->values();

        return response()->json($lista);
    }

    /**
     * URLs de filas neo con aniadida=no para un producto_id, y datos del producto para mismo_producto.
     */
    public function urlsPorProducto(int $productoId)
    {
        $urls = Neo::where('aniadida', 'no')
            ->where('producto_id', $productoId)
            ->whereNotNull('url_cipher')
            ->where('url_cipher', '!=', '')
            ->get()
            ->map(fn (Neo $neo) => trim((string) $neo->url))
            ->filter(fn (string $url) => $url !== '')
            ->values()
            ->toArray();

        $producto = Producto::find($productoId, ['id', 'nombre', 'marca', 'modelo', 'talla']);
        $productoParaVista = null;
        if ($producto) {
            $productoParaVista = [
                'id' => $producto->id,
                'nombre' => $producto->nombre,
                'marca' => $producto->marca,
                'modelo' => $producto->modelo,
                'talla' => $producto->talla,
                'texto_completo' => trim($producto->nombre . ' - ' . ($producto->marca ?? '') . ' - ' . ($producto->modelo ?? '') . ' - ' . ($producto->talla ?? ''), ' -'),
            ];
        }

        return response()->json([
            'urls' => $urls,
            'producto' => $productoParaVista,
        ]);
    }

    /**
     * Categorías que tienen al menos una fila en neo con aniadida=no (agrupadas por categoria_id).
     * Para el botón "Categoría" en crear-masivo neo.
     */
    public function categoriasConNeoAniadidaNo()
    {
        $grupos = Neo::where('aniadida', 'no')
            ->whereNotNull('url_cipher')
            ->where('url_cipher', '!=', '')
            ->whereNotNull('categoria_id')
            ->selectRaw('categoria_id, count(*) as total')
            ->groupBy('categoria_id')
            ->get();

        $categorias = Categoria::whereIn('id', $grupos->pluck('categoria_id'))
            ->get(['id', 'nombre'])
            ->keyBy('id');

        $lista = $grupos->map(function ($g) use ($categorias) {
            $c = $categorias->get($g->categoria_id);
            return [
                'categoria_id'   => $g->categoria_id,
                'nombre'         => $c ? $c->nombre : 'Categoría #' . $g->categoria_id,
                'count'          => (int) $g->total,
            ];
        })->values();

        return response()->json($lista);
    }

    /**
     * URLs de filas neo con aniadida=no para un categoria_id.
     */
    public function urlsPorCategoria(int $categoriaId)
    {
        $urls = Neo::where('aniadida', 'no')
            ->where('categoria_id', $categoriaId)
            ->whereNotNull('url_cipher')
            ->where('url_cipher', '!=', '')
            ->get()
            ->map(fn (Neo $neo) => trim((string) $neo->url))
            ->filter(fn (string $url) => $url !== '')
            ->values()
            ->toArray();

        return response()->json(['urls' => $urls]);
    }

    /**
     * Tiendas pendientes (aniadida=no) para una categoría concreta.
     */
    public function tiendasPorCategoria(int $categoriaId)
    {
        $grupos = Neo::where('aniadida', 'no')
            ->where('categoria_id', $categoriaId)
            ->whereNotNull('url_cipher')
            ->where('url_cipher', '!=', '')
            ->whereNotNull('tienda_id')
            ->selectRaw('tienda_id, count(*) as total')
            ->groupBy('tienda_id')
            ->get();

        $tiendas = Tienda::whereIn('id', $grupos->pluck('tienda_id'))
            ->get(['id', 'nombre'])
            ->keyBy('id');

        $lista = $grupos->map(function ($g) use ($tiendas) {
            $t = $tiendas->get($g->tienda_id);
            return [
                'tienda_id' => $g->tienda_id,
                'nombre' => $t ? $t->nombre : 'Tienda #' . $g->tienda_id,
                'count' => (int) $g->total,
            ];
        })->values();

        return response()->json($lista);
    }

    /**
     * URLs pendientes (aniadida=no) para una categoría y tienda concretas.
     */
    public function urlsPorCategoriaTienda(int $categoriaId, int $tiendaId)
    {
        $urls = Neo::where('aniadida', 'no')
            ->where('categoria_id', $categoriaId)
            ->where('tienda_id', $tiendaId)
            ->whereNotNull('url_cipher')
            ->where('url_cipher', '!=', '')
            ->get()
            ->map(fn (Neo $neo) => trim((string) $neo->url))
            ->filter(fn (string $url) => $url !== '')
            ->values()
            ->toArray();

        return response()->json(['urls' => $urls]);
    }

    /**
     * Tiendas que tienen al menos una fila en neo con aniadida=no (agrupadas por tienda_id).
     * Para el botón "Tienda" en crear-masivo neo.
     */
    public function tiendasConNeoAniadidaNo()
    {
        $grupos = Neo::where('aniadida', 'no')
            ->whereNotNull('url_cipher')
            ->where('url_cipher', '!=', '')
            ->whereNotNull('tienda_id')
            ->selectRaw('tienda_id, count(*) as total')
            ->groupBy('tienda_id')
            ->get();

        $tiendas = Tienda::whereIn('id', $grupos->pluck('tienda_id'))
            ->get(['id', 'nombre'])
            ->keyBy('id');

        $lista = $grupos->map(function ($g) use ($tiendas) {
            $t = $tiendas->get($g->tienda_id);
            return [
                'tienda_id' => $g->tienda_id,
                'nombre' => $t ? $t->nombre : 'Tienda #' . $g->tienda_id,
                'count' => (int) $g->total,
            ];
        })->values();

        return response()->json($lista);
    }

    /**
     * URLs de filas neo con aniadida=no para un tienda_id.
     */
    public function urlsPorTienda(int $tiendaId)
    {
        $urls = Neo::where('aniadida', 'no')
            ->where('tienda_id', $tiendaId)
            ->whereNotNull('url_cipher')
            ->where('url_cipher', '!=', '')
            ->get()
            ->map(fn (Neo $neo) => trim((string) $neo->url))
            ->filter(fn (string $url) => $url !== '')
            ->values()
            ->toArray();

        return response()->json(['urls' => $urls]);
    }

    /**
     * Categorías pendientes (aniadida=no) para una tienda concreta.
     */
    public function categoriasPorTienda(int $tiendaId)
    {
        $grupos = Neo::where('aniadida', 'no')
            ->where('tienda_id', $tiendaId)
            ->whereNotNull('url_cipher')
            ->where('url_cipher', '!=', '')
            ->whereNotNull('categoria_id')
            ->selectRaw('categoria_id, count(*) as total')
            ->groupBy('categoria_id')
            ->get();

        $categorias = Categoria::whereIn('id', $grupos->pluck('categoria_id'))
            ->get(['id', 'nombre'])
            ->keyBy('id');

        $lista = $grupos->map(function ($g) use ($categorias) {
            $c = $categorias->get($g->categoria_id);
            return [
                'categoria_id' => $g->categoria_id,
                'nombre' => $c ? $c->nombre : 'Categoría #' . $g->categoria_id,
                'count' => (int) $g->total,
            ];
        })->values();

        return response()->json($lista);
    }

    /**
     * URLs pendientes (aniadida=no) para una tienda y categoría concretas.
     */
    public function urlsPorTiendaCategoria(int $tiendaId, int $categoriaId)
    {
        $urls = Neo::where('aniadida', 'no')
            ->where('tienda_id', $tiendaId)
            ->where('categoria_id', $categoriaId)
            ->whereNotNull('url_cipher')
            ->where('url_cipher', '!=', '')
            ->get()
            ->map(fn (Neo $neo) => trim((string) $neo->url))
            ->filter(fn (string $url) => $url !== '')
            ->values()
            ->toArray();

        return response()->json(['urls' => $urls]);
    }

    /**
     * Listado de registros de la tabla neo con búsqueda por url/neo y filtro por aniadida (si/no).
     * Por defecto solo se muestran las que tienen aniadida=no.
     * Si se marca "Sí", se muestran ambas. Si hay búsqueda, se muestran ambas.
     */
    public function index(Request $request)
    {
        $perPage = $request->input('perPage', 20);
        $busqueda = $request->input('busqueda');
        $aniadida = $request->input('aniadida', ['no']);

        // Si hay búsqueda, mostrar tanto si como no
        if ($busqueda !== null && $busqueda !== '') {
            $aniadidaParaVista = ['si', 'no'];
        } else {
            $aniadidaParaVista = is_array($aniadida) ? $aniadida : [$aniadida];
        }

        $neos = Neo::with(['producto', 'tienda', 'categoria'])
            ->when($busqueda, function ($query, $busqueda) {
                $busqueda = trim($busqueda);
                $lookup = app(ConsultarNeoCifrado::class)->hashLookup($busqueda);
                $query->where(function ($q) use ($lookup) {
                    $q->where('neo_lookup', $lookup)
                        ->orWhere('url_lookup', $lookup);
                });
            })
            ->when(!$busqueda && count($aniadidaParaVista) > 0, function ($query) use ($aniadidaParaVista) {
                $query->whereIn('aniadida', $aniadidaParaVista);
            })
            ->orderBy('created_at', 'desc')
            ->paginate($perPage)
            ->withQueryString();

        return view('admin.neo.index', compact('neos', 'perPage', 'aniadidaParaVista'));
    }

    /**
     * Productos que no tienen ningún Neoobjetivo asociado (producto_id).
     * Listado paginado a 20 por página con enlace Ir (página pública) y Editar.
     */
    public function productosSinNeo(Request $request)
    {
        $productos = Producto::with('categoria')
            ->whereDoesntHave('neoobjetivos')
            ->orderBy('nombre')
            ->paginate(20)
            ->withQueryString();

        return view('admin.neo.productos-sin-neo', compact('productos'));
    }

    /**
     * Guardar una URL Neo (neoobjetivo) para un producto desde el listado "productos sin neo".
     * Misma lógica que el formulario de producto: URL válida o "No encontrado", visitada = 2 semanas atrás.
     */
    public function guardarNeoobjetivo(Request $request)
    {
        $validated = $request->validate([
            'producto_id' => 'required|exists:productos,id',
            'url'         => 'required|string|max:2048',
        ], [
            'producto_id.required' => 'Falta el producto.',
            'producto_id.exists'   => 'El producto no existe.',
            'url.required'         => 'Escribe una URL o usa el botón "No encontrada".',
        ]);

        $url = trim($validated['url']);
        $esNoEncontrado = strtolower($url) === 'no encontrado';
        $esUrlValida = filter_var($url, FILTER_VALIDATE_URL);

        if (!$esUrlValida && !$esNoEncontrado) {
            return response()->json([
                'success' => false,
                'message' => 'La URL no es válida. Debe ser una URL válida o el texto "No encontrado".',
            ], 422);
        }

        if ($esNoEncontrado) {
            $url = 'No encontrado';
        }

        $producto = Producto::findOrFail($validated['producto_id']);
        $dosSemanas = now()->subWeeks(2);

        Neoobjetivo::create([
            'producto_id' => $producto->id,
            'url'         => $url,
            'visitada'    => $dosSemanas,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'URL Neo guardada correctamente. El producto ya no aparecerá en este listado.',
        ]);
    }

    /**
     * Descarta una URL en crear masivo: la guarda en urls_descartadas
     * y marca como aniadida=si en neo para esa URL.
     */
    public function descartarUrlCrearMasivo(Request $request)
    {
        $validated = $request->validate([
            'url' => 'required|string|max:2048',
        ], [
            'url.required' => 'Falta la URL a descartar.',
        ]);

        $url = trim($validated['url']);

        if ($url === '') {
            return response()->json([
                'success' => false,
                'message' => 'La URL está vacía.',
            ], 422);
        }

        UrlDescartada::firstOrCreate(['url' => $url]);

        $urlLookup = app(ConsultarNeoCifrado::class)->hashLookup($url);
        $filasActualizadas = Neo::where('url_lookup', $urlLookup)
            ->where('aniadida', 'no')
            ->update([
                'aniadida' => 'si',
            ]);

        return response()->json([
            'success' => true,
            'message' => 'URL descartada correctamente.',
            'url' => $url,
            'neo_actualizadas' => $filasActualizadas,
        ]);
    }

    /**
     * Actualiza categoria_id en filas neo (aniadida=no) que coincidan con la URL.
     * Mismas variantes de URL que al crear oferta masiva.
     */
    public function actualizarCategoriaUrlCrearMasivo(Request $request)
    {
        $validated = $request->validate([
            'url' => 'required|string|max:2048',
            'categoria_id' => 'nullable|integer|exists:categorias,id',
        ], [
            'url.required' => 'Falta la URL.',
        ]);

        $url = trim($validated['url']);
        if ($url === '') {
            return response()->json([
                'success' => false,
                'message' => 'La URL está vacía.',
            ], 422);
        }

        $variantes = $this->variantesUrlParaNeoCrearMasivo($url);
        $categoriaId = $validated['categoria_id'] ?? null;

        $lookups = array_values(array_filter(array_map(
            fn ($u) => app(ConsultarNeoCifrado::class)->hashLookup($u),
            $variantes
        )));
        $actualizadas = Neo::where('aniadida', 'no')
            ->whereIn('url_lookup', $lookups)
            ->update(['categoria_id' => $categoriaId]);

        return response()->json([
            'success' => true,
            'message' => $actualizadas > 0
                ? 'Categoría actualizada en neo (' . $actualizadas . ' fila(s)).'
                : 'No había filas neo (añadida=no) con esa URL; nada que actualizar.',
            'neo_actualizadas' => $actualizadas,
        ]);
    }

    /**
     * Normaliza URL igual que en OfertasController::analizarUrls / crear oferta bulk.
     */
    private function normalizarUrlCrearMasivo(string $url): string
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

        return ($parsed['scheme'] ?? 'https') . '://' . $host . $path;
    }

    /**
     * @return list<string>
     */
    private function variantesUrlParaNeoCrearMasivo(string $url): array
    {
        $url = trim($url);
        $urlNorm = $this->normalizarUrlCrearMasivo($url);
        $urlSinBarra = rtrim($urlNorm, '/');
        $urlConBarra = $urlSinBarra . '/';

        return array_values(array_unique(array_filter([
            $url,
            trim($url),
            $urlNorm,
            $urlSinBarra,
            $urlConBarra,
        ], static fn ($v) => $v !== null && $v !== '')));
    }

    /**
     * Formulario: pegar URLs (una por línea) y comprobar / eliminar filas en neo por coincidencia exacta en url.
     */
    public function eliminarNeoPorUrlsForm()
    {
        return view('admin.neo.eliminar-por-urls');
    }

    /**
     * Comprueba qué URLs existen en neo.url y devuelve filas por URL (ids, aniadida, etc.).
     */
    public function eliminarNeoPorUrlsComprobar(Request $request)
    {
        $validated = $request->validate([
            'urls' => 'required|string',
        ], [
            'urls.required' => 'Pega al menos una URL.',
        ]);

        $lineas = $this->parseUrlsTextoMultilinea($validated['urls']);
        if ($lineas === []) {
            return response()->json([
                'success' => false,
                'message' => 'No hay ninguna URL válida en el texto (líneas vacías).',
            ], 422);
        }

        $unicosOrden = array_values(array_unique($lineas));
        $repetidasEnTexto = count($lineas) > count($unicosOrden);

        $noEncontradas = [];
        $encontradas = [];

        foreach ($unicosOrden as $url) {
            $lookup = app(ConsultarNeoCifrado::class)->hashLookup($url);
            $filas = Neo::query()
                ->where('url_lookup', $lookup)
                ->orderBy('id')
                ->get(['id', 'url', 'aniadida', 'created_at']);

            if ($filas->isEmpty()) {
                $noEncontradas[] = $url;
            } else {
                $encontradas[] = [
                    'url'   => $url,
                    'total' => $filas->count(),
                    'filas' => $filas->map(function ($f) {
                        return [
                            'id'         => $f->id,
                            'aniadida'   => $f->aniadida,
                            'created_at' => $f->created_at?->toIso8601String(),
                        ];
                    })->values()->all(),
                ];
            }
        }

        return response()->json([
            'success'            => true,
            'urls_en_texto'      => count($lineas),
            'urls_unicas'        => count($unicosOrden),
            'repetidas_en_texto' => $repetidasEnTexto,
            'no_encontradas'     => $noEncontradas,
            'encontradas'        => $encontradas,
        ]);
    }

    /**
     * Elimina filas neo según alcance por URL: todas las filas con esa url, o una concreta (neo_id).
     *
     * @param  array<int, array{url: string, alcance: string, neo_id?: int|null}>  $acciones
     */
    public function eliminarNeoPorUrlsEjecutar(Request $request)
    {
        $validated = $request->validate([
            'acciones'            => 'required|array|min:1',
            'acciones.*.url'      => 'required|string|max:2048',
            'acciones.*.alcance'  => 'required|string|in:todas,una',
            'acciones.*.neo_id'   => 'nullable|integer|exists:neo,id',
        ], [
            'acciones.required' => 'No hay acciones de borrado.',
            'acciones.min'      => 'No hay acciones de borrado.',
        ]);

        $totalEliminadas = 0;
        $detalle = [];

        foreach ($validated['acciones'] as $accion) {
            $url = trim($accion['url']);
            $alcance = $accion['alcance'];
            $neoId = isset($accion['neo_id']) ? (int) $accion['neo_id'] : null;

            $lookup = app(ConsultarNeoCifrado::class)->hashLookup($url);
            $idsConUrl = Neo::query()->where('url_lookup', $lookup)->pluck('id');
            if ($idsConUrl->isEmpty()) {
                $detalle[] = ['url' => $url, 'eliminadas' => 0, 'aviso' => 'La URL ya no existe en neo.'];
                continue;
            }

            if ($alcance === 'todas') {
                $n = Neo::query()->where('url_lookup', $lookup)->delete();
                $totalEliminadas += $n;
                $detalle[] = ['url' => $url, 'eliminadas' => $n, 'alcance' => 'todas'];
                continue;
            }

            // una
            if ($idsConUrl->count() === 1) {
                $soloId = (int) $idsConUrl->first();
                if ($neoId !== null && $neoId !== $soloId) {
                    return response()->json([
                        'success' => false,
                        'message' => 'El neo_id no corresponde a la única fila de esta URL: ' . $url,
                    ], 422);
                }
                $idBorrar = $soloId;
            } else {
                if ($neoId === null || !$idsConUrl->contains($neoId)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Para la URL con varias filas debes indicar qué neo_id eliminar: ' . $url,
                    ], 422);
                }
                $idBorrar = $neoId;
            }

            $fila = Neo::query()->where('id', $idBorrar)->where('url_lookup', $lookup)->first();
            if (!$fila) {
                return response()->json([
                    'success' => false,
                    'message' => 'El neo_id no coincide con la URL indicada.',
                ], 422);
            }

            $fila->delete();
            $totalEliminadas += 1;
            $detalle[] = ['url' => $url, 'eliminadas' => 1, 'alcance' => 'una', 'neo_id' => $idBorrar];
        }

        return response()->json([
            'success'          => true,
            'total_eliminadas' => $totalEliminadas,
            'detalle'          => $detalle,
        ]);
    }

    /**
     * @return array<int, string>
     */
    private function parseUrlsTextoMultilinea(string $texto): array
    {
        $lineas = preg_split('/\r\n|\r|\n/', $texto) ?: [];
        $out = [];
        foreach ($lineas as $linea) {
            $u = trim((string) $linea);
            if ($u !== '') {
                $out[] = $u;
            }
        }

        return $out;
    }

    /**
     * Filas neo con url vacía: listado y herramientas CSV (exportar neo descifrado / importar urls).
     */
    public function neoSinUrlCompletar(Request $request)
    {
        $perPage = (int) $request->input('perPage', 20);
        if (!in_array($perPage, [20, 50, 100, 200], true)) {
            $perPage = 20;
        }

        $neos = Neo::query()
            ->with(['producto', 'tienda', 'categoria'])
            ->where(function ($q) {
                $q->whereNull('url_cipher')->orWhere('url_cipher', '');
            })
            ->whereNotNull('neo_cipher')
            ->where('neo_cipher', '!=' , '')
            ->orderBy('id')
            ->paginate($perPage)
            ->withQueryString();

        return view('admin.neo.sin-url-completar', compact('neos', 'perPage'));
    }

    /**
     * Descarga CSV: columna 1 neo en claro (descifrado), columna 2 vacía para rellenar URLs.
     */
    public function neoSinUrlDescargarCsv(Request $request)
    {
        $request->validate([
            'encrypt_password' => ['nullable', 'string', 'max:500'],
            'lookup_password' => ['nullable', 'string', 'max:500'],
        ]);

        $secretCheck = $this->validarSecretosNeoCsv(
            $request->input('encrypt_password'),
            $request->input('lookup_password')
        );
        if (!$secretCheck['ok']) {
            return back()->withErrors($secretCheck['errors'] ?? [])->withInput();
        }

        $filename = 'neo-sin-url-' . now()->format('Y-m-d-His') . '.csv';

        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            if ($out === false) {
                return;
            }
            fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($out, ['neo_url', 'url_destino']);

            Neo::query()
                ->where(function ($q) {
                    $q->whereNull('url_cipher')->orWhere('url_cipher', '');
                })
                ->whereNotNull('neo_cipher')
                ->where('neo_cipher', '!=', '')
                ->chunkById(200, function ($neos) use ($out) {
                    foreach ($neos as $neo) {
                        $plain = trim((string) ($neo->neo ?? ''));
                        fputcsv($out, [$plain, '']);
                    }
                });
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Importa CSV (mismo formato que el export): actualiza url donde neo coincida y url siga vacía.
     */
    public function neoSinUrlSubirCsv(Request $request): \Illuminate\Http\RedirectResponse
    {
        $request->validate([
            'encrypt_password' => ['nullable', 'string', 'max:500'],
            'lookup_password' => ['nullable', 'string', 'max:500'],
            'csv'                 => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ], [
            'csv.required' => 'Selecciona un archivo CSV.',
        ]);

        $secretCheck = $this->validarSecretosNeoCsv(
            $request->input('encrypt_password'),
            $request->input('lookup_password')
        );
        if (!$secretCheck['ok']) {
            return back()->withErrors($secretCheck['errors'] ?? [])->withInput();
        }

        $path = $request->file('csv')->getRealPath();
        if ($path === false || !is_readable($path)) {
            return back()->withErrors(['csv' => 'No se pudo leer el archivo.'])->withInput();
        }

        $handle = fopen($path, 'r');
        if ($handle === false) {
            return back()->withErrors(['csv' => 'No se pudo abrir el archivo.'])->withInput();
        }

        $firstLine = fgets($handle);
        if ($firstLine === false) {
            fclose($handle);
            return back()->withErrors(['csv' => 'El archivo CSV está vacío.'])->withInput();
        }
        $delimiter = substr_count($firstLine, ';') > substr_count($firstLine, ',') ? ';' : ',';
        rewind($handle);

        $lineNumber = 0;
        $actualizadas = 0;
        $noEncontradas = [];
        $errores = [];
        $omitidas = 0;
        $omitidasRelocator = 0;
        $marcadasAniadidaSi = 0;

        try {
            while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
                $lineNumber++;
                if ($row === [null] || $row === false) {
                    continue;
                }
                if ($lineNumber === 1 && isset($row[0])) {
                    $cabecera = preg_replace('/^\xEF\xBB\xBF/', '', trim((string) $row[0]));
                    if (strcasecmp($cabecera, 'neo_url') === 0) {
                        continue;
                    }
                }

                $neoPlain = isset($row[0]) ? trim((string) $row[0]) : '';
                $neoPlain = preg_replace('/^\xEF\xBB\xBF/', '', $neoPlain);
                $urlNueva = isset($row[1]) ? trim((string) $row[1]) : '';

                if ($neoPlain === '' && $urlNueva === '') {
                    $omitidas++;
                    continue;
                }
                if ($neoPlain === '') {
                    $errores[] = ['linea' => $lineNumber, 'mensaje' => 'La primera columna (neo_url) está vacía.'];
                    continue;
                }
                if ($urlNueva === '') {
                    $errores[] = ['linea' => $lineNumber, 'mensaje' => 'La segunda columna (url_destino) está vacía.'];
                    continue;
                }
                if (stripos($urlNueva, 'relocator/relocate') !== false) {
                    $omitidasRelocator++;
                    continue;
                }

                $urlNueva = app(LimpiarUrlDeTiendas::class)->limpiar($urlNueva);
                if ($urlNueva === '') {
                    $errores[] = ['linea' => $lineNumber, 'mensaje' => 'La URL quedó vacía tras aplicar LimpiarUrlDeTiendas.'];
                    continue;
                }

                if (strlen($urlNueva) > 500) {
                    $errores[] = ['linea' => $lineNumber, 'mensaje' => 'La URL supera 500 caracteres (límite del campo en base de datos).'];
                    continue;
                }

                $lookup = app(ConsultarNeoCifrado::class)->hashLookup($neoPlain);

                $filas = Neo::query()
                    ->where(function ($q) use ($lookup, $neoPlain) {
                        $q->where('neo_lookup', $lookup);
                    })
                    ->where(function ($q) {
                        $q->whereNull('url_cipher')->orWhere('url_cipher', '');
                    })
                    ->orderBy('id')
                    ->get();

                if ($filas->isEmpty()) {
                    $noEncontradas[] = [
                        'linea'   => $lineNumber,
                        'neo_url' => mb_substr($neoPlain, 0, 200),
                    ];
                    continue;
                }

                $urlExisteEnOfertaProducto = OfertaProducto::query()
                    ->where('url', $urlNueva)
                    ->exists();
                $urlNuevaLookup = app(ConsultarNeoCifrado::class)->hashLookup($urlNueva);

                $filaDestinoExistente = Neo::query()
                    ->where('url_lookup', $urlNuevaLookup)
                    ->orderBy('id')
                    ->first();

                foreach ($filas as $fila) {
                    try {
                        // Si la URL ya existe en neo, fusionamos datos en esa fila y eliminamos la fila origen sin URL.
                        if ($filaDestinoExistente && (int) $filaDestinoExistente->id !== (int) $fila->id) {
                            $destinoActualizado = false;
                            if (trim((string) ($filaDestinoExistente->neo ?? '')) === '' && $neoPlain !== '') {
                                $filaDestinoExistente->neo = $neoPlain;
                                $destinoActualizado = true;
                            }

                            foreach (['oferta_id', 'producto_id', 'categoria_id', 'tienda_id'] as $campoId) {
                                if ($filaDestinoExistente->{$campoId} === null && $fila->{$campoId} !== null) {
                                    $filaDestinoExistente->{$campoId} = $fila->{$campoId};
                                    $destinoActualizado = true;
                                }
                            }

                            if ($urlExisteEnOfertaProducto && $filaDestinoExistente->aniadida !== 'si') {
                                $filaDestinoExistente->aniadida = 'si';
                                $destinoActualizado = true;
                                $marcadasAniadidaSi++;
                            }

                            if ($destinoActualizado) {
                                $filaDestinoExistente->save();
                            }

                            $fila->delete();
                            $actualizadas++;
                            continue;
                        }

                        $fila->url = $urlNueva;
                        if ($urlExisteEnOfertaProducto) {
                            $fila->aniadida = 'si';
                            $marcadasAniadidaSi++;
                        }
                        $fila->save();
                        $actualizadas++;
                    } catch (\Throwable $e) {
                        $errores[] = [
                            'linea'   => $lineNumber,
                            'mensaje' => 'Error al guardar neo id ' . $fila->id . ': ' . mb_substr($e->getMessage(), 0, 200),
                        ];
                    }
                }
            }
        } finally {
            fclose($handle);
        }

        return redirect()
            ->route('admin.neo.sin-url-completar')
            ->with('import_resultado', [
                'lineas_procesadas' => $lineNumber,
                'filas_actualizadas' => $actualizadas,
                'filas_marcadas_aniadida_si' => $marcadasAniadidaSi,
                'lineas_omitidas_vacias' => $omitidas,
                'lineas_omitidas_relocator' => $omitidasRelocator,
                'no_encontradas' => $noEncontradas,
                'errores' => $errores,
            ]);
    }

    /**
     * @return array{ok: bool, errors?: array<string, string>}
     */
    private function validarSecretosNeoCsv(?string $encryptPassword, ?string $lookupPassword): array
    {
        $encryptSecret = (string) config('anti-scraping.neo_encrypt_key', '');
        $lookupSecret = (string) config('anti-scraping.neo_lookup_key', '');
        $encryptPassword = (string) ($encryptPassword ?? '');
        $lookupPassword = (string) ($lookupPassword ?? '');
        $errors = [];

        if ($encryptSecret !== '' && ($encryptPassword === '' || !hash_equals($encryptSecret, $encryptPassword))) {
            $errors['encrypt_password'] = 'La contraseña de cifrado no coincide con anti-scraping.neo_encrypt_key.';
        }
        if ($lookupSecret !== '' && ($lookupPassword === '' || !hash_equals($lookupSecret, $lookupPassword))) {
            $errors['lookup_password'] = 'La contraseña de lookup no coincide con anti-scraping.neo_lookup_key.';
        }

        return empty($errors) ? ['ok' => true] : ['ok' => false, 'errors' => $errors];
    }

}
