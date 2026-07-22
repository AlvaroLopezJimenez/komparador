<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\Neo;
use App\Models\OfertaProducto;
use App\Models\Producto;
use App\Models\Tienda;
use App\Models\UrlDescartada;
use App\Services\ConsultarNeoCifrado;
use Illuminate\Http\Request;

class UrlDescartadasController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) $request->input('perPage', 20);
        if (!in_array($perPage, [20, 50, 100, 200], true)) {
            $perPage = 20;
        }

        $busqueda = trim((string) $request->input('busqueda', ''));
        $tiendaId = $request->filled('tienda_id') ? (int) $request->input('tienda_id') : null;
        $categoriaId = $request->filled('categoria_id') ? (int) $request->input('categoria_id') : null;
        $productoId = $request->filled('producto_id') ? (int) $request->input('producto_id') : null;

        $filas = UrlDescartada::query()
            ->with(['tienda:id,nombre', 'categoria:id,nombre', 'producto:id,nombre,marca,modelo,talla'])
            ->when($busqueda !== '', function ($q) use ($busqueda) {
                $q->whereRaw('LOWER(url) LIKE ?', ['%' . strtolower($busqueda) . '%']);
            })
            ->when($tiendaId, fn ($q) => $q->where('tienda_id', $tiendaId))
            ->when($categoriaId, fn ($q) => $q->where('categoria_id', $categoriaId))
            ->when($productoId, fn ($q) => $q->where('producto_id', $productoId))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        $tiendas = Tienda::query()->orderBy('nombre')->get(['id', 'nombre']);
        $categorias = Categoria::query()->orderBy('nombre')->get(['id', 'nombre']);

        $productoSeleccionado = null;
        if ($productoId) {
            $productoSeleccionado = Producto::query()
                ->where('id', $productoId)
                ->first(['id', 'nombre', 'marca', 'modelo', 'talla']);
        }

        return view('admin.ofertas.todas_url_descartadas', compact(
            'filas',
            'perPage',
            'busqueda',
            'tiendaId',
            'categoriaId',
            'productoId',
            'productoSeleccionado',
            'tiendas',
            'categorias'
        ));
    }

    public function destroy(UrlDescartada $urlDescartada)
    {
        $resultado = $this->eliminarUna($urlDescartada);

        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'URL eliminada de descartadas.',
                'resultado' => $resultado,
            ]);
        }

        return redirect()
            ->route('admin.ofertas.url_descartadas')
            ->with('success', 'URL eliminada de descartadas.');
    }

    public function destroyBulk(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:urls_descartadas,id',
        ], [
            'ids.required' => 'Selecciona al menos una URL.',
        ]);

        $eliminadas = 0;
        $neoRestauradas = 0;
        $detalles = [];

        $filas = UrlDescartada::whereIn('id', $validated['ids'])->get();
        foreach ($filas as $fila) {
            $resultado = $this->eliminarUna($fila);
            $eliminadas++;
            $neoRestauradas += (int) ($resultado['neo_actualizadas'] ?? 0);
            $detalles[] = $resultado;
        }

        return response()->json([
            'success' => true,
            'message' => "Eliminadas {$eliminadas} URL(s) descartadas.",
            'eliminadas' => $eliminadas,
            'neo_restauradas' => $neoRestauradas,
            'detalles' => $detalles,
        ]);
    }

    /**
     * Elimina la fila y, si la URL está en neo pero no en ofertas_producto, pone neo.aniadida = no.
     *
     * @return array{url: string, neo_actualizadas: int, existia_en_oferta: bool, existia_en_neo: bool}
     */
    private function eliminarUna(UrlDescartada $urlDescartada): array
    {
        $url = trim((string) $urlDescartada->url);
        $urlDescartada->delete();

        $existiaEnNeo = false;
        $existiaEnOferta = false;
        $neoActualizadas = 0;

        if ($url !== '') {
            $urlLookup = app(ConsultarNeoCifrado::class)->hashLookup($url);

            $existiaEnNeo = Neo::where('url_lookup', $urlLookup)->exists();
            $existiaEnOferta = OfertaProducto::where('url_lookup', $urlLookup)->exists();

            if ($existiaEnNeo && !$existiaEnOferta) {
                $neoActualizadas = Neo::where('url_lookup', $urlLookup)
                    ->where('aniadida', 'si')
                    ->update(['aniadida' => 'no']);
            }
        }

        return [
            'url' => $url,
            'neo_actualizadas' => $neoActualizadas,
            'existia_en_oferta' => $existiaEnOferta,
            'existia_en_neo' => $existiaEnNeo,
        ];
    }
}
