<?php

namespace App\Http\Controllers\Scraping;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Crons\CronNeoObjetivosController;
use App\Models\Neoobjetivo;
use App\Models\Tienda;
use App\Services\TiendaScrapingConfigResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Búsqueda manual de productos en URLs de categoría desde el formulario de tienda.
 * Delega en CronNeoObjetivosController (misma lógica que cron y programa externo).
 */
class BuscarProductosCategoriasTiendasScrapingController extends Controller
{
    private const MAX_PAGINAS = 50;

    /**
     * Valida el neoobjetivo y devuelve metadatos antes de iniciar la búsqueda.
     */
    public function preparar(Request $request, Tienda $tienda): JsonResponse
    {
        $data = $request->validate([
            'neoobjetivo_id' => ['required', 'integer', 'min:1'],
            'url_listado'    => ['sometimes', 'nullable', 'string', 'max:2048'],
        ]);

        $neo = Neoobjetivo::where('id', (int) $data['neoobjetivo_id'])
            ->where('tienda_id', $tienda->id)
            ->whereNull('oferta_id')
            ->whereNull('producto_id')
            ->first();

        if (!$neo) {
            return response()->json([
                'ok'    => false,
                'error' => 'No se encontró la URL de categoría para esta tienda.',
            ], 404);
        }

        $urlListadoManual = trim((string) ($data['url_listado'] ?? ''));
        if ($urlListadoManual !== '') {
            $urlActual = trim((string) $neo->url);
            if ($urlActual === '' || strtolower($urlActual) === 'no encontrado') {
                $neo->url = $urlListadoManual;
                $neo->save();
                $neo->refresh();
            }
        }

        $ctx = app(CronNeoObjetivosController::class)->resolverContextoCategoriaTiendaNeoobjetivo($neo->id);
        if (empty($ctx['ok'])) {
            return response()->json([
                'ok'    => false,
                'error' => $ctx['error'] ?? 'No se pudo preparar la búsqueda.',
            ], (int) ($ctx['http_code'] ?? 422));
        }

        $apiProductos = app(TiendaScrapingConfigResolver::class)->resolverApiProductos(
            $tienda,
            $neo->categoria_id ? (int) $neo->categoria_id : null
        );

        return response()->json([
            'ok'              => true,
            'neoobjetivo_id'  => $neo->id,
            'url_inicial'     => $ctx['url_listado'],
            'tipo_listado'    => $ctx['tipo_listado'],
            'api_productos'   => $apiProductos,
            'max_paginas'     => self::MAX_PAGINAS,
        ]);
    }

    /**
     * Procesa una página del listado (paginación, sitemap o mostrar más).
     */
    public function procesarPagina(Request $request, Tienda $tienda): JsonResponse
    {
        $data = $request->validate([
            'neoobjetivo_id'               => ['required', 'integer', 'min:1'],
            'url_pagina'                   => ['required', 'string', 'max:2048'],
            'urls_producto_acumulado_antes' => ['sometimes', 'integer', 'min:0', 'max:500000'],
            'numero_pagina'                => ['sometimes', 'integer', 'min:1', 'max:' . self::MAX_PAGINAS],
        ]);

        $neo = Neoobjetivo::where('id', (int) $data['neoobjetivo_id'])
            ->where('tienda_id', $tienda->id)
            ->whereNull('oferta_id')
            ->whereNull('producto_id')
            ->first();

        if (!$neo) {
            return response()->json([
                'ok'    => false,
                'error' => 'No se encontró la URL de categoría para esta tienda.',
            ], 404);
        }

        $numeroPagina = (int) ($data['numero_pagina'] ?? 1);
        if ($numeroPagina > self::MAX_PAGINAS) {
            return response()->json([
                'ok'        => false,
                'error'     => 'Límite de ' . self::MAX_PAGINAS . ' páginas alcanzado.',
                'completada' => true,
            ], 422);
        }

        $result = app(CronNeoObjetivosController::class)->procesarPaginaCategoriaTiendaDesdeApiInterna(
            $neo->id,
            trim((string) $data['url_pagina']),
            (int) ($data['urls_producto_acumulado_antes'] ?? 0),
            $numeroPagina,
        );

        $http = (int) ($result['http_code'] ?? 500);
        unset($result['http_code']);

        return response()->json($result, !empty($result['ok']) ? 200 : $http);
    }
}
