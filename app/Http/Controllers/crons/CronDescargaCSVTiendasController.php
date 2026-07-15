<?php

namespace App\Http\Controllers\Crons;

use App\Http\Controllers\Controller;
use App\Models\EjecucionGlobal;
use App\Models\Tienda;
use App\Services\DescargaCsvAwinTiendaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CronDescargaCSVTiendasController extends Controller
{
    public const NOMBRE_EJECUCION_GLOBAL = 'cron_descarga_csv_tiendas';

    /** Si una ejecución sigue sin fin tras este tiempo, se considera zombie y se cierra. */
    private const HORAS_EJECUCION_ZOMBIE = 1;

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

        $resumenLimpieza = $this->descargaCsvAwinTiendaService->limpiarDirectoriosCsvAwinAntiguos(
            DescargaCsvAwinTiendaService::HORAS_ANTIGUEDAD_LIMPIEZA_DEFECTO
        );

        $bloqueo = $this->comprobarEjecucionEnCurso();
        if ($bloqueo !== null) {
            return $bloqueo;
        }

        $ejecucion = EjecucionGlobal::create([
            'inicio' => now(),
            'fin' => null,
            'nombre' => self::NOMBRE_EJECUCION_GLOBAL,
            'total' => 0,
            'total_guardado' => 0,
            'total_errores' => 0,
            'log' => [
                'estado' => 'running',
                'paso_actual' => 'limpieza_csv_antiguos',
                'pasos' => [
                    [
                        'momento' => now()->toDateTimeString(),
                        'paso' => 'inicio',
                        'detalle' => 'Ejecución creada',
                        'contexto' => [],
                    ],
                    [
                        'momento' => now()->toDateTimeString(),
                        'paso' => 'limpieza_csv_antiguos',
                        'detalle' => 'Limpieza de directorios temporales con más de 2 horas',
                        'contexto' => [
                            'eliminados' => $resumenLimpieza['eliminados'],
                            'errores' => $resumenLimpieza['errores'],
                            'bytes_liberados' => $resumenLimpieza['bytes_liberados'],
                            'bytes_liberados_legible' => $this->formatearBytes($resumenLimpieza['bytes_liberados']),
                        ],
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

                $registrarPaso = function (string $paso, array $contexto = []) use ($ejecucion): void {
                    $this->actualizarEjecucionPaso($ejecucion, $paso, $contexto);
                };

                $resultado = $this->descargaCsvAwinTiendaService->procesarTienda($tienda, $registrarPaso);

                $this->actualizarEjecucionPaso($ejecucion, 'tienda_resumen', [
                    'detalle' => !empty($resultado['errores'])
                        ? 'Tienda finalizada con errores'
                        : (!empty($resultado['omitida']) ? 'Tienda omitida' : 'Tienda finalizada correctamente'),
                    'tienda_id' => $tienda->id,
                    'tienda' => $tienda->nombre,
                    'omitida' => !empty($resultado['omitida']),
                    'motivo' => $resultado['motivo'] ?? null,
                    'urls_procesadas' => (int) ($resultado['urls_procesadas'] ?? 0),
                    'filas_guardadas' => (int) ($resultado['filas_insertadas_o_actualizadas'] ?? 0),
                    'filas_omitidas' => (int) ($resultado['filas_omitidas'] ?? 0),
                    'errores' => $resultado['errores'] ?? [],
                ]);

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
            $ejecucion->refresh();
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
            Log::warning('CronDescargaCSVTiendas: no se pudo registrar paso de ejecución', [
                'ejecucion_id' => $ejecucion->id,
                'paso' => $paso,
                'error' => $e->getMessage(),
            ]);
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

    private function comprobarEjecucionEnCurso(): ?JsonResponse
    {
        $ultimaEjecucion = EjecucionGlobal::query()
            ->where('nombre', self::NOMBRE_EJECUCION_GLOBAL)
            ->orderByDesc('inicio')
            ->first();

        if (!$ultimaEjecucion || $ultimaEjecucion->fin !== null) {
            return null;
        }

        $log = is_array($ultimaEjecucion->log) ? $ultimaEjecucion->log : [];
        $estado = $log['estado'] ?? '';

        if ($estado !== 'running') {
            return null;
        }

        if ($ultimaEjecucion->inicio->diffInHours(now()) >= self::HORAS_EJECUCION_ZOMBIE) {
            $this->cerrarEjecucionZombie($ultimaEjecucion, $log);

            return null;
        }

        return response()->json([
            'status' => 'error',
            'message' => 'Ya hay una ejecución del cron CSV en curso (iniciada hace '
                . $ultimaEjecucion->inicio->diffForHumans(now(), true) . ')',
            'ultima_ejecucion' => [
                'id' => $ultimaEjecucion->id,
                'inicio' => $ultimaEjecucion->inicio->toDateTimeString(),
                'estado' => $estado,
                'minutos_transcurridos' => $ultimaEjecucion->inicio->diffInMinutes(now()),
            ],
        ], 409);
    }

    /**
     * @param  array<string, mixed>  $log
     */
    private function cerrarEjecucionZombie(EjecucionGlobal $ejecucion, array $log): void
    {
        $pasos = isset($log['pasos']) && is_array($log['pasos']) ? $log['pasos'] : [];
        $pasos[] = [
            'momento' => now()->toDateTimeString(),
            'paso' => 'error',
            'detalle' => 'Ejecución cerrada como zombie',
            'contexto' => [
                'motivo' => 'Sin fin tras ' . self::HORAS_EJECUCION_ZOMBIE . ' horas',
            ],
        ];

        $log['estado'] = 'error';
        $log['paso_actual'] = 'error';
        $log['error'] = [
            'mensaje' => 'Ejecución interrumpida o colgada; cerrada automáticamente al lanzar un nuevo cron.',
            'tipo' => 'zombie',
        ];
        $log['pasos'] = $pasos;

        $ejecucion->update([
            'fin' => now(),
            'total_errores' => (int) $ejecucion->total_errores + 1,
            'log' => $log,
        ]);
    }

    private function formatearBytes(int $bytes): string
    {
        if ($bytes >= 1024 * 1024 * 1024) {
            return round($bytes / (1024 * 1024 * 1024), 2) . ' GB';
        }
        if ($bytes >= 1024 * 1024) {
            return round($bytes / (1024 * 1024), 2) . ' MB';
        }
        if ($bytes >= 1024) {
            return round($bytes / 1024, 2) . ' KB';
        }

        return $bytes . ' B';
    }

    /**
     * Cierra manualmente una ejecución en curso para desbloquear nuevas llamadas al cron.
     * No detiene el proceso PHP en el servidor si sigue vivo; solo actualiza el registro en BD.
     */
    public function cancelarEjecucion(Request $request, EjecucionGlobal $ejecucion): RedirectResponse
    {
        if ($ejecucion->nombre !== self::NOMBRE_EJECUCION_GLOBAL) {
            abort(404, 'Ejecución no encontrada');
        }

        $fecha = $ejecucion->inicio?->toDateString() ?? now()->toDateString();
        $mes = $ejecucion->inicio?->format('Y-m') ?? now()->format('Y-m');
        $volver = route('admin.ejecuciones.descarga-csv-tiendas', [
            'fecha' => $fecha,
            'mes' => $mes,
        ]);

        if ($ejecucion->fin !== null) {
            return redirect($volver)->with('error', 'La ejecución #' . $ejecucion->id . ' ya estaba finalizada.');
        }

        $log = is_array($ejecucion->log) ? $ejecucion->log : [];
        $pasos = isset($log['pasos']) && is_array($log['pasos']) ? $log['pasos'] : [];
        $pasos[] = [
            'momento' => now()->toDateTimeString(),
            'paso' => 'cancelada',
            'detalle' => 'Ejecución cerrada manualmente desde el panel',
            'contexto' => [
                'usuario_id' => $request->user()?->id,
            ],
        ];

        $log['estado'] = 'cancelada';
        $log['paso_actual'] = 'cancelada';
        $log['error'] = [
            'mensaje' => 'Ejecución cerrada manualmente. El proceso en el servidor puede seguir activo un tiempo.',
            'tipo' => 'cancelada_manual',
        ];
        $log['pasos'] = $pasos;

        $ejecucion->update([
            'fin' => now(),
            'total_errores' => (int) $ejecucion->total_errores + 1,
            'log' => $log,
        ]);

        return redirect($volver)->with(
            'success',
            'Ejecución #' . $ejecucion->id . ' cerrada. Ya puedes lanzar de nuevo el cron.'
        );
    }
}
