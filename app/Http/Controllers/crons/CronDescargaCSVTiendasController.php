<?php

namespace App\Http\Controllers\Crons;

use App\Http\Controllers\Controller;
use App\Models\EjecucionGlobal;
use App\Models\Tienda;
use App\Services\DescargaCsvAwinTiendaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CronDescargaCSVTiendasController extends Controller
{
    public const NOMBRE_EJECUCION_GLOBAL = 'cron_descarga_csv_tiendas';

    public function __construct(
        private readonly DescargaCsvAwinTiendaService $descargaCsvAwinTiendaService,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $token = $request->query('token');
        if (!$token || $token !== env('TOKEN_ACTUALIZAR_PRECIOS')) {
            return response()->json([
                'status' => 'error',
                'message' => 'Token inválido',
            ], 403);
        }

        @set_time_limit(0);

        $ejecucion = EjecucionGlobal::create([
            'inicio' => now(),
            'fin' => null,
            'nombre' => self::NOMBRE_EJECUCION_GLOBAL,
            'total' => 0,
            'total_guardado' => 0,
            'total_errores' => 0,
            'log' => [
                'estado' => 'running',
                'paso_actual' => 'consulta_tiendas',
                'pasos' => [
                    [
                        'momento' => now()->toDateTimeString(),
                        'paso' => 'inicio',
                        'detalle' => 'Ejecución creada',
                        'contexto' => [],
                    ],
                ],
            ],
        ]);

        try {
            $tiendas = Tienda::query()
                ->whereNotNull('url_csv')
                ->orderBy('id')
                ->get()
                ->filter(fn (Tienda $tienda) => is_array($tienda->url_csv) && $tienda->url_csv !== [])
                ->values();

            $this->actualizarEjecucionPaso($ejecucion, 'consulta_tiendas', [
                'detalle' => 'Tiendas con url_csv configurada',
                'tiendas_encontradas' => $tiendas->count(),
            ]);

            $resultados = [];
            $contadores = [
                'tiendas_encontradas' => $tiendas->count(),
                'tiendas_procesadas' => 0,
                'tiendas_omitidas' => 0,
                'urls_procesadas' => 0,
                'filas_insertadas_o_actualizadas' => 0,
                'filas_omitidas' => 0,
                'errores_tienda' => 0,
            ];

            foreach ($tiendas as $tienda) {
                $this->actualizarEjecucionPaso($ejecucion, 'procesando_tienda', [
                    'detalle' => 'Procesando tienda',
                    'tienda_id' => $tienda->id,
                    'tienda' => $tienda->nombre,
                ]);

                $resultado = $this->descargaCsvAwinTiendaService->procesarTienda($tienda);
                $resultados[] = $resultado;

                if (!empty($resultado['omitida'])) {
                    $contadores['tiendas_omitidas']++;
                    continue;
                }

                $contadores['tiendas_procesadas']++;
                $contadores['urls_procesadas'] += (int) ($resultado['urls_procesadas'] ?? 0);
                $contadores['filas_insertadas_o_actualizadas'] += (int) ($resultado['filas_insertadas_o_actualizadas'] ?? 0);
                $contadores['filas_omitidas'] += (int) ($resultado['filas_omitidas'] ?? 0);
                $contadores['errores_tienda'] += count($resultado['errores'] ?? []);
            }

            $ejecucion->refresh();
            $log = is_array($ejecucion->log) ? $ejecucion->log : [];
            $log['estado'] = 'ok';
            $log['paso_actual'] = 'finalizado';
            $log['contadores'] = $contadores;
            $log['resultados'] = $resultados;
            $log['resumen'] = sprintf(
                '%d tienda(s), %d fila(s) guardadas, %d error(es) de tienda',
                $contadores['tiendas_procesadas'],
                $contadores['filas_insertadas_o_actualizadas'],
                $contadores['errores_tienda']
            );

            $ejecucion->update([
                'fin' => now(),
                'total' => $contadores['tiendas_encontradas'],
                'total_guardado' => $contadores['filas_insertadas_o_actualizadas'],
                'total_errores' => $contadores['errores_tienda'],
                'log' => $log,
            ]);

            return response()->json([
                'status' => 'ok',
                'message' => 'Cron descarga CSV tiendas ejecutado correctamente',
                'ejecucion_id' => $ejecucion->id,
                'contadores' => $contadores,
            ]);
        } catch (\Throwable $e) {
            $this->actualizarEjecucionError($ejecucion, $e);

            return response()->json([
                'status' => 'error',
                'message' => 'Error en cron descarga CSV tiendas: ' . $e->getMessage(),
                'ejecucion_id' => $ejecucion->id,
            ], 500);
        }
    }

    private function actualizarEjecucionPaso(EjecucionGlobal $ejecucion, string $paso, array $contexto = []): void
    {
        try {
            $log = is_array($ejecucion->log) ? $ejecucion->log : [];
            $pasos = isset($log['pasos']) && is_array($log['pasos']) ? $log['pasos'] : [];
            $detalle = $contexto['detalle'] ?? null;
            unset($contexto['detalle']);

            $pasos[] = [
                'momento' => now()->toDateTimeString(),
                'paso' => $paso,
                'detalle' => $detalle,
                'contexto' => $contexto,
            ];

            $log['estado'] = 'running';
            $log['paso_actual'] = $paso;
            $log['pasos'] = $pasos;

            $ejecucion->update(['log' => $log]);
        } catch (\Throwable $e) {
            //
        }
    }

    private function actualizarEjecucionError(EjecucionGlobal $ejecucion, \Throwable $e): void
    {
        try {
            $log = is_array($ejecucion->log) ? $ejecucion->log : [];
            $pasos = isset($log['pasos']) && is_array($log['pasos']) ? $log['pasos'] : [];
            $pasoActual = $log['paso_actual'] ?? 'desconocido';

            $pasos[] = [
                'momento' => now()->toDateTimeString(),
                'paso' => 'error',
                'detalle' => 'Excepción no controlada',
                'contexto' => [
                    'paso_en_el_que_fallo' => $pasoActual,
                    'error' => $e->getMessage(),
                ],
            ];

            $log['estado'] = 'error';
            $log['paso_actual'] = 'error';
            $log['error'] = [
                'mensaje' => $e->getMessage(),
                'tipo' => get_class($e),
            ];
            $log['pasos'] = $pasos;

            $ejecucion->update([
                'fin' => now(),
                'total_errores' => (int) $ejecucion->total_errores + 1,
                'log' => $log,
            ]);
        } catch (\Throwable $inner) {
            //
        }
    }
}
