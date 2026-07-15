<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OfertaProducto;
use App\Services\ConsultarNeoCifrado;
use App\Services\LimpiarUrlDeTiendas;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API temporal para que chollopañales.com sincronice ofertas vía URL.
 * Solo devuelve los datos que komparador ya tiene en BD; no ejecuta scraping.
 */
class CholloPanalesApiController extends Controller
{
    /**
     * GET: comprobación rápida de conectividad y token.
     */
    public function probarConexion(Request $request): JsonResponse
    {
        if ($respuesta = $this->verificarToken($request)) {
            return $respuesta;
        }

        return response()->json([
            'ok'      => true,
            'message' => 'Conexión correcta con la API chollopañales.',
            'hora'    => now()->toIso8601String(),
        ]);
    }

    /**
     * POST: busca oferta por URL y devuelve los datos almacenados en komparador.
     */
    public function actualizarPorUrl(Request $request): JsonResponse
    {
        if ($respuesta = $this->verificarToken($request)) {
            return $respuesta;
        }

        $data = $request->validate([
            'url' => ['required', 'string', 'max:2048'],
        ]);

        $urlRecibida = trim((string) $data['url']);
        if ($urlRecibida === '') {
            return response()->json(['ok' => false, 'error' => 'URL vacía.'], 422);
        }

        $oferta = $this->buscarOfertaPorUrl($urlRecibida);
        if (!$oferta) {
            return response()->json([
                'ok'         => false,
                'encontrada' => false,
                'error'      => 'oferta_no_encontrada',
            ], 404);
        }

        $oferta->loadMissing(['tienda', 'producto']);

        return response()->json([
            'ok'         => true,
            'encontrada' => true,
            'oferta'     => $this->serializarOferta($oferta),
        ]);
    }

    private function verificarToken(Request $request): ?JsonResponse
    {
        $expected = (string) env('CHOLLOPANALES_API_TOKEN', '');
        if ($expected === '') {
            return response()->json(['ok' => false, 'error' => 'Token API chollopañales no configurado.'], 503);
        }

        $bearer = $request->bearerToken();
        $bearer = $bearer !== null ? trim((string) $bearer) : '';
        $xToken = trim((string) $request->header('X-CholloPanales-Token', ''));

        $okBearer = $bearer !== '' && hash_equals($expected, $bearer);
        $okX = $xToken !== '' && hash_equals($expected, $xToken);

        if (!$okBearer && !$okX) {
            return response()->json(['ok' => false, 'error' => 'No autorizado.'], 401);
        }

        return null;
    }

    private function buscarOfertaPorUrl(string $url): ?OfertaProducto
    {
        $limpiarUrl = app(LimpiarUrlDeTiendas::class);
        $neoCifrado = app(ConsultarNeoCifrado::class);

        $candidatas = array_values(array_unique(array_filter([
            $limpiarUrl->limpiar($url),
            trim($url),
        ])));

        foreach ($candidatas as $urlNormalizada) {
            if ($urlNormalizada === '') {
                continue;
            }

            $lookup = $neoCifrado->hashLookup($urlNormalizada);
            if ($lookup === '') {
                continue;
            }

            $oferta = OfertaProducto::where('url_lookup', $lookup)->first();
            if ($oferta) {
                return $oferta;
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function serializarOferta(OfertaProducto $oferta): array
    {
        return [
            'oferta_id'     => $oferta->id,
            'unidades'      => $oferta->unidades,
            'precio_total'  => $oferta->precio_total !== null ? (float) $oferta->precio_total : null,
            'precio_unidad' => $oferta->precio_unidad !== null ? (float) $oferta->precio_unidad : null,
            'mostrar'       => $oferta->mostrar,
            'descuentos'    => $oferta->descuentos,
            'variante'      => $oferta->variante,
            'updated_at'    => $oferta->updated_at?->toIso8601String(),
        ];
    }
}
