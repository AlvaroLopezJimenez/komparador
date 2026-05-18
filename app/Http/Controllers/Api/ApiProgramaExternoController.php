<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Scraping\PeticionApiHTMLController;
use App\Models\OfertaProducto;
use App\Services\Scraping as ScrapingService;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API del programa externo que obtiene HTML con navegador local y actualiza precios de ofertas.
 */
class ApiProgramaExternoController extends Controller
{
    public function __construct(
        private readonly ScrapingService $scraping
    ) {}

    /**
     * GET: hasta 50 ofertas pendientes (tiendas con API navegadorLocal).
     *
     * Query: limite (1–50, default 50)
     */
    public function ofertasPendientes(Request $request): JsonResponse
    {
        $limite = min(50, max(1, (int) $request->query('limite', 50)));
        $ofertas = $this->scraping->obtenerOfertasElegiblesNavegadorLocal($limite);

        $out = [];
        foreach ($ofertas as $oferta) {
            $tienda = $oferta->tienda;
            $out[] = [
                'oferta_id'     => $oferta->id,
                'url'           => $oferta->url,
                'variante'      => $oferta->variante,
                'tienda_id'     => $oferta->tienda_id,
                'tienda_nombre' => $tienda ? $tienda->nombre : null,
                'producto_id'   => $oferta->producto_id,
                'updated_at'    => $oferta->updated_at?->format(DateTimeInterface::ATOM),
                'frecuencia_actualizar_precio_minutos' => $oferta->frecuencia_actualizar_precio_minutos,
            ];
        }

        return response()->json([
            'ok'      => true,
            'total'   => count($out),
            'ofertas' => $out,
        ]);
    }

    /**
     * GET: comprobación rápida de conectividad y token.
     */
    public function probarConexion(): JsonResponse
    {
        return response()->json([
            'ok'      => true,
            'message' => 'Conexión correcta con la API scraping-programa-externo.',
            'hora'    => now()->toIso8601String(),
        ]);
    }

    /**
     * POST: procesa el HTML de una oferta (delega en App\Services\Scraping).
     */
    public function procesarHtmlOferta(Request $request): JsonResponse
    {
        $data = $request->validate([
            'oferta_id'     => ['required', 'integer', 'min:1'],
            'url'           => ['required', 'string', 'max:2048'],
            'html'          => ['required', 'string', 'max:12000000'],
            'variante'      => ['nullable', 'string', 'max:500'],
            'tienda_id'     => ['nullable', 'integer', 'min:1'],
            'tienda_nombre' => ['nullable', 'string', 'max:255'],
        ]);

        $oferta = OfertaProducto::with(['tienda', 'producto'])->find($data['oferta_id']);
        if (!$oferta) {
            return response()->json(['ok' => false, 'error' => 'Oferta no encontrada.'], 404);
        }

        if (!$oferta->tienda || $oferta->tienda->api !== ScrapingService::API_NAVEGADOR_LOCAL) {
            return response()->json([
                'ok'    => false,
                'error' => 'La tienda de esta oferta no usa la API navegador local.',
            ], 422);
        }

        if (isset($data['tienda_id']) && (int) $data['tienda_id'] !== (int) $oferta->tienda_id) {
            return response()->json(['ok' => false, 'error' => 'tienda_id no coincide con la oferta.'], 422);
        }

        $urlEnviada = trim((string) $data['url']);
        if ($urlEnviada !== '' && trim((string) $oferta->url) !== $urlEnviada) {
            return response()->json(['ok' => false, 'error' => 'url no coincide con la oferta.'], 422);
        }

        PeticionApiHTMLController::setHtmlInyectado((string) $data['html']);

        try {
            $resultado = $this->scraping->procesarOferta($oferta);
        } finally {
            PeticionApiHTMLController::clearHtmlInyectado();
        }

        $ok = !empty($resultado['success']);

        return response()->json([
            'ok'        => $ok,
            'oferta_id' => $oferta->id,
            'resultado' => $resultado,
        ], $ok ? 200 : 422);
    }

    /**
     * POST: guarda ejecución al terminar un ciclo (solo al final; nombre distinto al cron web).
     */
    public function registrarEjecucionFin(Request $request): JsonResponse
    {
        $rawLineas = $request->input('lineas_log');
        if (!is_array($rawLineas)) {
            return response()->json(['ok' => false, 'error' => 'lineas_log debe ser un array.'], 422);
        }
        $lineasNorm = [];
        foreach ($rawLineas as $ln) {
            if ($ln === null) {
                $lineasNorm[] = '';
            } elseif (is_string($ln)) {
                $lineasNorm[] = $ln;
            } elseif (is_scalar($ln)) {
                $lineasNorm[] = (string) $ln;
            } else {
                $lineasNorm[] = json_encode($ln, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
            }
        }
        $request->merge(['lineas_log' => $lineasNorm]);

        $data = $request->validate([
            'lineas_log'      => ['required', 'array', 'max:1500'],
            'lineas_log.*'    => ['string', 'max:1000'],
            'estadisticas'    => ['sometimes', 'array', 'max:30'],
            'estado'          => ['sometimes', 'string', 'in:ok,error'],
            'inicio_unix'     => ['sometimes', 'numeric', 'min:946684800', 'max:4102444800'],
            'error_mensaje'   => ['sometimes', 'string', 'max:2000'],
            'total_ofertas'   => ['sometimes', 'integer', 'min:0', 'max:50'],
            'actualizadas'    => ['sometimes', 'integer', 'min:0', 'max:50'],
            'errores'         => ['sometimes', 'integer', 'min:0', 'max:50'],
        ]);

        $estadisticasRaw = $request->input('estadisticas', []);
        $estadisticas = [];
        if (is_array($estadisticasRaw)) {
            $allowed = ['urls_abiertas', 'errores', 'actualizadas', 'procesadas', 'total_ofertas'];
            foreach ($allowed as $k) {
                if (array_key_exists($k, $estadisticasRaw) && is_numeric($estadisticasRaw[$k])) {
                    $estadisticas[$k] = (int) $estadisticasRaw[$k];
                }
            }
        }

        $estadoApi = $data['estado'] ?? 'ok';
        $estado = $estadoApi === 'error' ? 'fallida' : 'completada';
        $estadisticas['total_ofertas'] = (int) ($data['total_ofertas'] ?? $estadisticas['total_ofertas'] ?? 0);
        $estadisticas['actualizadas'] = (int) ($data['actualizadas'] ?? $estadisticas['actualizadas'] ?? 0);
        $estadisticas['errores'] = (int) ($data['errores'] ?? $estadisticas['errores'] ?? 0);
        $estadisticas['procesadas'] = (int) ($estadisticas['procesadas'] ?? $estadisticas['total_ofertas']);

        $inicio = isset($data['inicio_unix'])
            ? Carbon::createFromTimestamp((int) $data['inicio_unix'])
            : now();

        $log = [
            'lineas' => array_slice($data['lineas_log'], 0, 1500),
        ];

        $ejecucionId = $this->scraping->registrarEjecucionProgramaExterno(
            $log,
            $estadisticas,
            $inicio,
            $estado,
            $data['error_mensaje'] ?? null
        );

        return response()->json([
            'ok'           => true,
            'ejecucion_id' => $ejecucionId,
        ]);
    }
}
