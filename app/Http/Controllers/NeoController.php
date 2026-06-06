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
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class NeoController extends Controller
{
    /** Máximo de filas neo revisadas (más recientes primero) para coincidencia parcial sobre la URL descifrada (campo url v2). */
    private const NEO_INDEX_BUSQUEDA_PARCIAL_MAX_FILAS = 35000;

    /** Tope de IDs devueltos (exactos por índice + parciales) para acotar memoria y paginación. */
    private const NEO_INDEX_BUSQUEDA_MAX_RESULTADOS = 2000;

    /**
     * Vista crear ofertas en masa desde neo: muestra cantidad de filas neo con aniadida=no
     * y permite cargar URLs por producto (neo con aniadida=no agrupados por producto_id).
     */
    public function crearMasivo()
    {
        $totalNeoAniadidaNo = Neo::where('aniadida', 'no')
            ->where(function ($q) {
                $q->whereNull('tienda_id')
                    ->orWhereDoesntHave('tienda', function ($tq) {
                        $tq->where('mostrar_tienda', 'no')
                            ->where('scrapear', 'no');
                    });
            })
            ->count();
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
     *
     * Query (checkboxes independientes):
     * - mostrar_si=1 y mostrar_no=0: solo neo con tienda_id no nulo y tienda mostrar_tienda=si (por defecto si no se envía nada).
     * - mostrar_si=0 y mostrar_no=1: solo neo cuyo tienda_id está en tienda_ids[] (cada id debe ser tienda mostrar_tienda=no). Sin ids: ningún resultado.
     * - ambos 1 o ninguno 1: sin filtrar por mostrar_tienda (incluye neo sin tienda_id).
     * - mostrar_si=0, mostrar_no=0 y mostrar_null=1: solo filas neo con tienda_id nulo (exclusivo).
     * - mostrar_null=1 junto con solo «Sí» o solo «No» (y tiendas): además incluye filas con tienda_id nulo (OR).
     * Compat: solo_tiendas_mostrar_si (1 = solo sí; 0 = sin filtro como ambos checks).
     * Respuesta: { productos: [...], filas_neo_tienda_id_null: N } (N = total filas neo sin tienda con URL y producto; el listado sigue el filtro).
     */
    public function productosConNeoAniadidaNo(Request $request)
    {
        $filasNeoTiendaIdNull = Neo::where('aniadida', 'no')
            ->whereNotNull('url_cipher')
            ->where('url_cipher', '!=', '')
            ->whereNotNull('producto_id')
            ->whereNull('tienda_id')
            ->count();

        $q = Neo::where('aniadida', 'no')
            ->whereNotNull('url_cipher')
            ->where('url_cipher', '!=', '')
            ->whereNotNull('producto_id');

        $this->aplicarFiltroMostrarTiendaNeoCrearMasivoProducto($q, $request);
        $this->aplicarFiltroMostrarCategoriaNeoCrearMasivo($q, $request);

        $grupos = $q->selectRaw('producto_id, count(*) as total')
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

        $filasNeoCategoriaIdNull = Neo::where('aniadida', 'no')
            ->whereNotNull('url_cipher')
            ->where('url_cipher', '!=', '')
            ->whereNotNull('producto_id')
            ->whereNull('categoria_id')
            ->count();

        return response()->json([
            'productos' => $lista,
            'filas_neo_tienda_id_null' => $filasNeoTiendaIdNull,
            'filas_neo_categoria_id_null' => $filasNeoCategoriaIdNull,
        ]);
    }

    /**
     * URLs de filas neo con aniadida=no para un producto_id, y datos del producto para mismo_producto.
     *
     * @see productosConNeoAniadidaNo() mismos parámetros mostrar_si / mostrar_no, mostrar_null, tienda_ids[] y categoria_mostrar_*.
     */
    public function urlsPorProducto(Request $request, int $productoId)
    {
        $q = Neo::where('aniadida', 'no')
            ->where('producto_id', $productoId)
            ->whereNotNull('url_cipher')
            ->where('url_cipher', '!=', '');

        $this->aplicarFiltroMostrarTiendaNeoCrearMasivoProducto($q, $request);
        $this->aplicarFiltroMostrarCategoriaNeoCrearMasivo($q, $request);

        $urls = $q->get()
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
     * Listado de tiendas con mostrar_tienda=no para el modal producto (checkboxes).
     */
    public function tiendasMostrarNoCrearMasivoModalProducto()
    {
        $lista = Tienda::query()
            ->where('mostrar_tienda', 'no')
            ->orderBy('nombre')
            ->get(['id', 'nombre'])
            ->map(fn (Tienda $t) => [
                'id' => $t->id,
                'nombre' => $t->nombre,
            ])
            ->values();

        return response()->json($lista);
    }

    /**
     * Filtro tienda en crear-masivo (modal producto / urls por producto):
     * - Solo null: mostrar_si=0, mostrar_no=0, mostrar_null=1 → solo tienda_id nulo.
     * - Solo «Sí»: tienda mostrar_tienda=si; con mostrar_null además OR tienda_id nulo.
     * - Solo «No»: tienda_ids con mostrar no; con mostrar_null además OR tienda_id nulo.
     * - Ambos sí y no o ninguno (sin null): sin filtrar por tienda.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<\App\Models\Neo>  $query
     */
    private function aplicarFiltroMostrarTiendaNeoCrearMasivoProducto($query, Request $request): void
    {
        if ($request->query('mostrar_si') !== null || $request->query('mostrar_no') !== null) {
            $chkSi = $this->queryCheckboxMostrarTiendaVerdadero($request, 'mostrar_si');
            $chkNo = $this->queryCheckboxMostrarTiendaVerdadero($request, 'mostrar_no');
        } elseif ($request->has('solo_tiendas_mostrar_si')) {
            if ($request->boolean('solo_tiendas_mostrar_si')) {
                $chkSi = true;
                $chkNo = false;
            } else {
                $chkSi = true;
                $chkNo = true;
            }
        } else {
            $chkSi = true;
            $chkNo = false;
        }

        $chkNull = $request->query('mostrar_null') !== null
            && $this->queryCheckboxMostrarTiendaVerdadero($request, 'mostrar_null');

        if ($chkNull && !$chkSi && !$chkNo) {
            $query->whereNull('tienda_id');

            return;
        }

        if (($chkSi && $chkNo) || (!$chkSi && !$chkNo)) {
            return;
        }

        if ($chkSi && !$chkNo) {
            $idsSi = Tienda::query()->where('mostrar_tienda', 'si')->select('id');
            $query->where(function ($w) use ($idsSi, $chkNull) {
                $w->where(function ($w2) use ($idsSi) {
                    $w2->whereNotNull('tienda_id')->whereIn('tienda_id', $idsSi);
                });
                if ($chkNull) {
                    $w->orWhereNull('tienda_id');
                }
            });

            return;
        }

        $ids = $this->idsTiendasMostrarNoSeleccionadasCrearMasivo($request);
        if ($ids === []) {
            $query->whereRaw('0 = 1');

            return;
        }

        $query->where(function ($w) use ($ids, $chkNull) {
            $w->whereIn('tienda_id', $ids);
            if ($chkNull) {
                $w->orWhereNull('tienda_id');
            }
        });
    }

    /**
     * Filtro por Categoria.mostrar (si/no) en crear-masivo neo.
     * Parámetros: categoria_mostrar_si, categoria_mostrar_no, categoria_mostrar_null (misma semántica que tienda).
     *
     * @param  \Illuminate\Database\Eloquent\Builder<\App\Models\Neo>  $query
     */
    private function aplicarFiltroMostrarCategoriaNeoCrearMasivo($query, Request $request): void
    {
        if ($request->query('categoria_mostrar_si') !== null || $request->query('categoria_mostrar_no') !== null) {
            $chkSi = $this->queryCheckboxMostrarTiendaVerdadero($request, 'categoria_mostrar_si');
            $chkNo = $this->queryCheckboxMostrarTiendaVerdadero($request, 'categoria_mostrar_no');
        } else {
            $chkSi = true;
            $chkNo = false;
        }

        $chkNull = $request->query('categoria_mostrar_null') !== null
            && $this->queryCheckboxMostrarTiendaVerdadero($request, 'categoria_mostrar_null');

        if ($chkNull && ! $chkSi && ! $chkNo) {
            $query->whereNull('categoria_id');

            return;
        }

        if (($chkSi && $chkNo) || (! $chkSi && ! $chkNo)) {
            return;
        }

        if ($chkSi && ! $chkNo) {
            $idsSi = Categoria::query()->where('mostrar', 'si')->select('id');
            $query->where(function ($w) use ($idsSi, $chkNull) {
                $w->whereIn('categoria_id', $idsSi);
                if ($chkNull) {
                    $w->orWhereNull('categoria_id');
                }
            });

            return;
        }

        $idsNo = Categoria::query()->where('mostrar', 'no')->pluck('id');
        $query->where(function ($w) use ($idsNo, $chkNull) {
            if ($idsNo->isNotEmpty()) {
                $w->whereIn('categoria_id', $idsNo);
            } elseif ($chkNull) {
                $w->whereNull('categoria_id');
            } else {
                $w->whereRaw('0 = 1');
            }
            if ($chkNull && $idsNo->isNotEmpty()) {
                $w->orWhereNull('categoria_id');
            }
        });
    }

    /**
     * Ids recibidos en tienda_ids / tienda_ids[] que existen y tienen mostrar_tienda=no.
     *
     * @return list<int>
     */
    private function idsTiendasMostrarNoSeleccionadasCrearMasivo(Request $request): array
    {
        $raw = $request->input('tienda_ids', []);
        if (! is_array($raw)) {
            $raw = $raw !== null && $raw !== '' ? [(string) $raw] : [];
        }
        $candidatos = array_values(array_unique(array_filter(
            array_map(static fn ($v) => (int) $v, $raw),
            static fn (int $id) => $id > 0
        )));
        if ($candidatos === []) {
            return [];
        }

        return Tienda::query()
            ->where('mostrar_tienda', 'no')
            ->whereIn('id', $candidatos)
            ->pluck('id')
            ->all();
    }

    /**
     * Parámetros GET mostrar_si / mostrar_no: 1/true/yes/on = marcado; 0 o ausente en el otro = no.
     */
    private function queryCheckboxMostrarTiendaVerdadero(Request $request, string $key): bool
    {
        $v = $request->query($key);
        if ($v === null) {
            return false;
        }
        if (is_bool($v)) {
            return $v;
        }

        return in_array(strtolower(trim((string) $v)), ['1', 'true', 'yes', 'on'], true);
    }

    /**
     * Categorías que tienen al menos una fila en neo con aniadida=no (agrupadas por categoria_id).
     * Para el botón "Categoría" en crear-masivo neo.
     *
     * @see productosConNeoAniadidaNo() parámetros categoria_mostrar_si / categoria_mostrar_no en el listado de categorías.
     */
    public function categoriasConNeoAniadidaNo(Request $request)
    {
        $q = Neo::where('aniadida', 'no')
            ->whereNotNull('url_cipher')
            ->where('url_cipher', '!=', '')
            ->whereNotNull('categoria_id');

        $this->aplicarFiltroMostrarCategoriaNeoCrearMasivo($q, $request);

        $grupos = $q->selectRaw('categoria_id, count(*) as total')
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
     *
     * @see productosConNeoAniadidaNo() mismos parámetros mostrar_si / mostrar_no y tienda_ids[].
     */
    public function urlsPorCategoria(Request $request, int $categoriaId)
    {
        $q = Neo::where('aniadida', 'no')
            ->where('categoria_id', $categoriaId)
            ->whereNotNull('url_cipher')
            ->where('url_cipher', '!=', '');

        $this->aplicarFiltroMostrarTiendaNeoCrearMasivoProducto($q, $request);
        $this->aplicarFiltroMostrarCategoriaNeoCrearMasivo($q, $request);

        $urls = $q->get()
            ->map(fn (Neo $neo) => trim((string) $neo->url))
            ->filter(fn (string $url) => $url !== '')
            ->values()
            ->toArray();

        return response()->json(['urls' => $urls]);
    }

    /**
     * Lista JSON de tiendas con conteos neo: incluye flags y deja al final las marcadas no mostrar / no scrapear.
     */
    private function listaTiendasNeoDesdeGrupos($grupos)
    {
        $tiendas = Tienda::whereIn('id', $grupos->pluck('tienda_id'))
            ->get(['id', 'nombre', 'mostrar_tienda', 'scrapear'])
            ->keyBy('id');

        $lista = $grupos->map(function ($g) use ($tiendas) {
            $t = $tiendas->get($g->tienda_id);
            $mostrar = $t ? (string) ($t->mostrar_tienda ?? 'si') : 'si';
            $scrapearVal = $t ? (string) ($t->scrapear ?? 'si') : 'si';
            $depriorizada = ($mostrar === 'no' || $scrapearVal === 'no');

            return [
                'tienda_id' => $g->tienda_id,
                'nombre' => $t ? $t->nombre : 'Tienda #' . $g->tienda_id,
                'count' => (int) $g->total,
                'mostrar_tienda' => $mostrar,
                'scrapear' => $scrapearVal,
                '_sort_depriorizada' => $depriorizada ? 1 : 0,
            ];
        });

        return $lista->sort(function ($a, $b) {
            if ($a['_sort_depriorizada'] !== $b['_sort_depriorizada']) {
                return $a['_sort_depriorizada'] <=> $b['_sort_depriorizada'];
            }

            return strcasecmp($a['nombre'] ?? '', $b['nombre'] ?? '');
        })->values()->map(function ($row) {
            unset($row['_sort_depriorizada']);

            return $row;
        });
    }

    /**
     * Tiendas pendientes (aniadida=no) para una categoría concreta.
     *
     * @see productosConNeoAniadidaNo() mismos parámetros mostrar_si / mostrar_no y tienda_ids[].
     */
    public function tiendasPorCategoria(Request $request, int $categoriaId)
    {
        $q = Neo::where('aniadida', 'no')
            ->where('categoria_id', $categoriaId)
            ->whereNotNull('url_cipher')
            ->where('url_cipher', '!=', '')
            ->whereNotNull('tienda_id');

        $this->aplicarFiltroMostrarTiendaNeoCrearMasivoProducto($q, $request);
        $this->aplicarFiltroMostrarCategoriaNeoCrearMasivo($q, $request);

        $grupos = $q->selectRaw('tienda_id, count(*) as total')
            ->groupBy('tienda_id')
            ->get();

        return response()->json($this->listaTiendasNeoDesdeGrupos($grupos));
    }

    /**
     * URLs pendientes (aniadida=no) para una categoría y tienda concretas.
     *
     * @see productosConNeoAniadidaNo() mismos parámetros mostrar_si / mostrar_no y tienda_ids[].
     */
    public function urlsPorCategoriaTienda(Request $request, int $categoriaId, int $tiendaId)
    {
        $q = Neo::where('aniadida', 'no')
            ->where('categoria_id', $categoriaId)
            ->where('tienda_id', $tiendaId)
            ->whereNotNull('url_cipher')
            ->where('url_cipher', '!=', '');

        $this->aplicarFiltroMostrarTiendaNeoCrearMasivoProducto($q, $request);
        $this->aplicarFiltroMostrarCategoriaNeoCrearMasivo($q, $request);

        $urls = $q->get()
            ->map(fn (Neo $neo) => trim((string) $neo->url))
            ->filter(fn (string $url) => $url !== '')
            ->values()
            ->toArray();

        return response()->json(['urls' => $urls]);
    }

    /**
     * Tiendas que tienen al menos una fila en neo con aniadida=no (agrupadas por tienda_id).
     * Para el botón "Tienda" en crear-masivo neo.
     *
     * @see productosConNeoAniadidaNo() parámetros mostrar_si / mostrar_no / mostrar_null, tienda_ids[] y categoria_mostrar_*.
     */
    public function tiendasConNeoAniadidaNo(Request $request)
    {
        $q = Neo::where('aniadida', 'no')
            ->whereNotNull('url_cipher')
            ->where('url_cipher', '!=', '')
            ->whereNotNull('tienda_id');

        $this->aplicarFiltroMostrarTiendaNeoCrearMasivoProducto($q, $request);
        $this->aplicarFiltroMostrarCategoriaNeoCrearMasivo($q, $request);

        $grupos = $q->selectRaw('tienda_id, count(*) as total')
            ->groupBy('tienda_id')
            ->get();

        $filasNeoCategoriaIdNull = Neo::where('aniadida', 'no')
            ->whereNotNull('url_cipher')
            ->where('url_cipher', '!=', '')
            ->whereNotNull('tienda_id')
            ->whereNull('categoria_id')
            ->count();

        return response()->json([
            'tiendas' => $this->listaTiendasNeoDesdeGrupos($grupos),
            'filas_neo_categoria_id_null' => $filasNeoCategoriaIdNull,
        ]);
    }

    /**
     * URLs de filas neo con aniadida=no para un tienda_id.
     *
     * @see productosConNeoAniadidaNo() mismos parámetros de filtro tienda y categoría.
     */
    public function urlsPorTienda(Request $request, int $tiendaId)
    {
        $q = Neo::where('aniadida', 'no')
            ->where('tienda_id', $tiendaId)
            ->whereNotNull('url_cipher')
            ->where('url_cipher', '!=', '');

        $this->aplicarFiltroMostrarTiendaNeoCrearMasivoProducto($q, $request);
        $this->aplicarFiltroMostrarCategoriaNeoCrearMasivo($q, $request);

        $urls = $q->get()
            ->map(fn (Neo $neo) => trim((string) $neo->url))
            ->filter(fn (string $url) => $url !== '')
            ->values()
            ->toArray();

        return response()->json(['urls' => $urls]);
    }

    /**
     * Categorías pendientes (aniadida=no) para una tienda concreta.
     *
     * @see productosConNeoAniadidaNo() mismos parámetros de filtro tienda y categoría.
     */
    public function categoriasPorTienda(Request $request, int $tiendaId)
    {
        $q = Neo::where('aniadida', 'no')
            ->where('tienda_id', $tiendaId)
            ->whereNotNull('url_cipher')
            ->where('url_cipher', '!=', '')
            ->whereNotNull('categoria_id');

        $this->aplicarFiltroMostrarTiendaNeoCrearMasivoProducto($q, $request);
        $this->aplicarFiltroMostrarCategoriaNeoCrearMasivo($q, $request);

        $grupos = $q->selectRaw('categoria_id, count(*) as total')
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
     *
     * @see productosConNeoAniadidaNo() mismos parámetros de filtro tienda y categoría.
     */
    public function urlsPorTiendaCategoria(Request $request, int $tiendaId, int $categoriaId)
    {
        $q = Neo::where('aniadida', 'no')
            ->where('tienda_id', $tiendaId)
            ->where('categoria_id', $categoriaId)
            ->whereNotNull('url_cipher')
            ->where('url_cipher', '!=', '');

        $this->aplicarFiltroMostrarTiendaNeoCrearMasivoProducto($q, $request);
        $this->aplicarFiltroMostrarCategoriaNeoCrearMasivo($q, $request);

        $urls = $q->get()
            ->map(fn (Neo $neo) => trim((string) $neo->url))
            ->filter(fn (string $url) => $url !== '')
            ->values()
            ->toArray();

        return response()->json(['urls' => $urls]);
    }

    /**
     * Listado de registros de la tabla neo con búsqueda solo por URL (url_lookup / URL descifrada) y filtro por aniadida (si/no).
     * Por defecto solo se muestran las que tienen aniadida=no.
     * Si se marca "Sí", se muestran ambas. Si hay búsqueda, se muestran ambas.
     */
    public function index(Request $request)
    {
        $perPage = (int) $request->input('perPage', 20);
        $perPage = in_array($perPage, [20, 50, 100, 200], true) ? $perPage : 20;
        $busqueda = $request->input('busqueda');
        $busquedaTrim = is_string($busqueda) ? trim($busqueda) : '';
        $aniadida = $request->input('aniadida', ['no']);
        $neoBusquedaAviso = null;

        // Si hay búsqueda, mostrar tanto si como no
        if ($busquedaTrim !== '') {
            $aniadidaParaVista = ['si', 'no'];
            $resultado = $this->neoIdsOrdenadosBusquedaUrl($busquedaTrim);
            $neoBusquedaAviso = $resultado['aviso'];
            $ids = $resultado['ids'];
            $page = LengthAwarePaginator::resolveCurrentPage();
            $total = count($ids);
            $offset = max(0, ($page - 1) * $perPage);
            $slice = array_slice($ids, $offset, $perPage);
            $items = $slice === []
                ? collect()
                : Neo::with(['producto', 'tienda', 'categoria'])
                    ->whereIn('id', $slice)
                    ->get()
                    ->sortBy(fn (Neo $neo) => array_search($neo->id, $slice, true))
                    ->values();

            $neos = new LengthAwarePaginator(
                $items,
                $total,
                $perPage,
                $page,
                [
                    'path' => $request->url(),
                    'pageName' => 'page',
                ]
            );
            $neos->withQueryString();
        } else {
            $aniadidaParaVista = is_array($aniadida) ? $aniadida : [$aniadida];

            $neos = Neo::with(['producto', 'tienda', 'categoria'])
                ->when(count($aniadidaParaVista) > 0, function ($query) use ($aniadidaParaVista) {
                    $query->whereIn('aniadida', $aniadidaParaVista);
                })
                ->orderBy('created_at', 'desc')
                ->paginate($perPage)
                ->withQueryString();
        }

        return view('admin.neo.index', compact('neos', 'perPage', 'aniadidaParaVista', 'neoBusquedaAviso'));
    }

    /**
     * IDs de neo que coinciden con la búsqueda: exactos por url_lookup (todo el histórico)
     * y parciales por subcadena en la URL descifrada (solo entre las filas más recientes, ver constantes).
     *
     * @return array{ids: array<int>, aviso: ?string}
     */
    private function neoIdsOrdenadosBusquedaUrl(string $trimmed): array
    {
        if ($trimmed === '') {
            return ['ids' => [], 'aviso' => null];
        }

        $cifrado = app(ConsultarNeoCifrado::class);
        $lookups = [];
        $h = $cifrado->hashLookup($trimmed);
        if ($h !== '') {
            $lookups[] = $h;
        }
        if (preg_match('#(https?://|www\.)#i', $trimmed)) {
            try {
                $limpia = app(LimpiarUrlDeTiendas::class)->limpiar($trimmed);
                if ($limpia !== '' && $limpia !== $trimmed) {
                    $h2 = $cifrado->hashLookup($limpia);
                    if ($h2 !== '' && !in_array($h2, $lookups, true)) {
                        $lookups[] = $h2;
                    }
                }
            } catch (\Throwable) {
            }
        }

        $exact = collect();
        if ($lookups !== []) {
            $exact = Neo::query()
                ->whereIn('url_lookup', $lookups)
                ->orderByDesc('created_at')
                ->get(['id', 'created_at']);
        }

        $needle = mb_strtolower($trimmed, 'UTF-8');
        $exactIds = $exact->pluck('id')->flip()->all();

        $porParcial = [];
        $filasEscaneadas = 0;
        $topeEscaneoAlcanzado = false;

        foreach (Neo::query()->orderByDesc('created_at')->cursor() as $neo) {
            if (++$filasEscaneadas > self::NEO_INDEX_BUSQUEDA_PARCIAL_MAX_FILAS) {
                $topeEscaneoAlcanzado = true;
                break;
            }

            if (isset($exactIds[$neo->id])) {
                continue;
            }

            $urlPlain = (string) $neo->url;
            if ($urlPlain !== '' && mb_strpos(mb_strtolower($urlPlain, 'UTF-8'), $needle, 0, 'UTF-8') !== false) {
                $porParcial[] = ['id' => (int) $neo->id, 'created_at' => $neo->created_at];
            }
        }

        $merged = $exact
            ->map(fn ($r) => ['id' => (int) $r->id, 'created_at' => $r->created_at])
            ->concat(collect($porParcial))
            ->unique('id')
            ->sortByDesc(function (array $row) {
                $ts = $row['created_at'] ?? null;

                return $ts instanceof \DateTimeInterface ? $ts->getTimestamp() : 0;
            })
            ->values();

        $capados = $merged->count() > self::NEO_INDEX_BUSQUEDA_MAX_RESULTADOS;
        $ids = $merged->take(self::NEO_INDEX_BUSQUEDA_MAX_RESULTADOS)->pluck('id')->all();

        $partes = [];
        if ($topeEscaneoAlcanzado) {
            $partes[] = 'La coincidencia parcial solo ha revisado las '.number_format(self::NEO_INDEX_BUSQUEDA_PARCIAL_MAX_FILAS, 0, ',', '.').' filas más recientes (por fecha de creación). Las coincidencias exactas por URL siguen buscándose en todo el histórico mediante el índice.';
        }
        if ($capados) {
            $partes[] = 'Se muestran como máximo '.self::NEO_INDEX_BUSQUEDA_MAX_RESULTADOS.' resultados; acota la búsqueda si hace falta.';
        }

        return [
            'ids' => $ids,
            'aviso' => $partes === [] ? null : implode(' ', $partes),
        ];
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

        if ($categoriaId !== null && Categoria::where('parent_id', $categoriaId)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Solo puedes asignar categorías finales (sin subcategorías).',
            ], 422);
        }

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

        $cifrado = app(ConsultarNeoCifrado::class);
        $lookupPorUrl = [];
        foreach ($unicosOrden as $url) {
            $lookupPorUrl[$url] = $cifrado->hashLookup($url);
        }

        $lookupsUnicos = array_values(array_unique($lookupPorUrl));
        $filasPorLookup = collect();
        foreach (array_chunk($lookupsUnicos, 500) as $chunkLookups) {
            $filasPorLookup = $filasPorLookup->merge(
                Neo::query()
                    ->whereIn('url_lookup', $chunkLookups)
                    ->orderBy('id')
                    ->get(['id', 'aniadida', 'created_at', 'url_lookup'])
                    ->groupBy('url_lookup')
            );
        }

        $noEncontradas = [];
        $encontradas = [];

        foreach ($unicosOrden as $url) {
            $lookup = $lookupPorUrl[$url];
            $filas = $filasPorLookup->get($lookup, collect());

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

                $urlNuevaLookup = app(ConsultarNeoCifrado::class)->hashLookup($urlNueva);
                $urlExisteEnOfertaProducto = OfertaProducto::query()
                    ->where('url_lookup', $urlNuevaLookup)
                    ->exists();

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
