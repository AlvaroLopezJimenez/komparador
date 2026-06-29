<?php

namespace App\Http\Controllers\Scraping;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OfertaProducto;
use App\Models\EjecucionGlobal;
use App\Models\Producto;
use App\Models\Tienda;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class ScraperSegundoPlanoController extends ScraperBaseController
{
    /**
     * Si el proceso termina sin llegar al update final (timeout FPM/nginx, SIGTERM, OOM, etc.),
     * deja la fila en en_progreso con fin=null. Se cierra aquí para no bloquear crons ni el histórico.
     */
    private static function cerrarEjecucionScraperHuérfana(int $ejecucionId): void
    {
        try {
            $e = EjecucionGlobal::query()->find($ejecucionId);
            if (!$e || $e->fin !== null) {
                return;
            }
            $log = is_array($e->log) ? $e->log : [];
            if (($log['estado'] ?? '') === 'completada') {
                return;
            }
            if (!in_array($log['estado'] ?? '', ['iniciada', 'en_progreso'], true)) {
                return;
            }
            $last = error_get_last();
            if ($last !== null) {
                $fatalMask = E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR;
                if (($last['type'] ?? 0) & $fatalMask) {
                    $log['error_shutdown'] = ($last['message'] ?? '') . ' @ ' . ($last['file'] ?? '') . ':' . ($last['line'] ?? '');
                } else {
                    $log['error_shutdown'] = $last['message'] ?? '';
                }
            }
            $log['estado'] = 'fallida';
            $procesadas = (int) ($log['procesadas'] ?? 0);
            $actualizadas = (int) ($log['actualizadas'] ?? 0);
            $errores = (int) ($log['errores'] ?? 0);
            $e->update([
                'fin'            => now(),
                'total'          => $procesadas,
                'total_guardado' => $actualizadas,
                'total_errores'  => $errores,
                'log'            => $log,
            ]);
        } catch (\Throwable $ignore) {
            //
        }
    }

    /**
     * Ejecutar scraping de ofertas en segundo plano (para cron jobs)
     */
    public function ejecutarScraperOfertasSegundoPlano(Request $request)
    {
        // Verificar token de seguridad
        $token = $request->query('token');
        if (!$token || $token !== env('TOKEN_ACTUALIZAR_PRECIOS')) {
            return response()->json([
                'status' => 'error',
                'message' => 'Token inválido'
            ], 403);
        }

        return $this->procesarScraperOfertas($token);
    }

    /**
     * Ejecutar scraping de ofertas de una tienda específica
     */
    public function ejecutarScraperOfertasTienda(Request $request)
    {
        // Verificar token de seguridad
        $token = $request->query('token');
        if (!$token || $token !== env('TOKEN_ACTUALIZAR_PRECIOS')) {
            return response()->json([
                'status' => 'error',
                'message' => 'Token inválido'
            ], 403);
        }

        $tiendaId = $request->input('tienda_id');
        $cantidad = $request->input('cantidad', null);

        if (!$tiendaId) {
            return response()->json([
                'status' => 'error',
                'message' => 'ID de tienda requerido'
            ], 400);
        }

        // Verificar que la tienda existe
        $tienda = Tienda::find($tiendaId);
        if (!$tienda) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tienda no encontrada'
            ], 404);
        }

        return $this->procesarScraperOfertas($token, $tiendaId, $cantidad);
    }

    /**
     * Ejecutar scraping de ofertas (método principal)
     */
    private function procesarScraperOfertas($token = null, $tiendaId = null, $cantidad = null)
    {
        $ejecucion = null;
        
        try {
            // Verificar si hay una ejecución en curso
            $ultimaEjecucion = EjecucionGlobal::where('nombre', 'ejecuciones_scrapear_ofertas')
                ->orderBy('inicio', 'desc')
                ->first();

            // Ejecuciones zombie (sin fin tras timeout del servidor, etc.): cerrar si llevan ≥1h abiertas
            if ($ultimaEjecucion
                && $ultimaEjecucion->fin === null
                && isset($ultimaEjecucion->log['estado'])
                && in_array($ultimaEjecucion->log['estado'], ['iniciada', 'en_progreso'], true)
                && $ultimaEjecucion->inicio->diffInHours(now()) >= 1) {
                $logZombie = is_array($ultimaEjecucion->log) ? $ultimaEjecucion->log : [];
                $logZombie['estado'] = 'fallida';
                $logZombie['error_cierre'] = 'Sin fin tras 1h (timeout o proceso terminado); cerrada al lanzar un nuevo cron.';
                $procesadasZ = (int) ($logZombie['procesadas'] ?? 0);
                $actualizadasZ = (int) ($logZombie['actualizadas'] ?? 0);
                $erroresZ = (int) ($logZombie['errores'] ?? 0);
                $ultimaEjecucion->update([
                    'fin'            => now(),
                    'total'          => $procesadasZ,
                    'total_guardado' => $actualizadasZ,
                    'total_errores'  => $erroresZ,
                    'log'            => $logZombie,
                ]);
                $ultimaEjecucion = EjecucionGlobal::where('nombre', 'ejecuciones_scrapear_ofertas')
                    ->orderBy('inicio', 'desc')
                    ->first();
            }

            // Solo bloquear si la última ejecución sigue abierta (sin fin) y hace menos de 1 hora
            if ($ultimaEjecucion &&
                $ultimaEjecucion->fin === null &&
                isset($ultimaEjecucion->log['estado']) &&
                in_array($ultimaEjecucion->log['estado'], ['iniciada', 'en_progreso'], true) &&
                $ultimaEjecucion->inicio->diffInHours(now()) < 1) {
                
                return response()->json([
                    'status' => 'error',
                    'message' => 'Ya hay una ejecución en curso (estado: ' . ($ultimaEjecucion->log['estado'] ?? 'desconocido') . ') iniciada hace menos de 1 hora',
                    'ultima_ejecucion' => [
                        'id' => $ultimaEjecucion->id,
                        'inicio' => $ultimaEjecucion->inicio,
                        'estado' => $ultimaEjecucion->log['estado'] ?? 'desconocido',
                        'tiempo_transcurrido_minutos' => $ultimaEjecucion->inicio->diffInMinutes(now())
                    ]
                ], 409);
            }

            // Configurar timeouts muy largos para evitar interrupciones
            set_time_limit(0); // Sin límite de tiempo
            ini_set('max_execution_time', 0);
            ini_set('memory_limit', '512M');

            // 1) Selección de ofertas a procesar ANTES de crear la ejecución
            if ($tiendaId) {
                $ofertas = $this->obtenerOfertasTienda($tiendaId, $cantidad);
            } else {
                $ofertasNoCsv = $this->obtenerOfertasElegibles(50);
                $ofertasCsv = $this->scraping()->obtenerOfertasElegiblesCsvAwin();
                $ofertas = $ofertasNoCsv
                    ->concat($ofertasCsv)
                    ->unique('id')
                    ->values();
            }

            $totalOfertas = $ofertas->count();

            // Si no hay ofertas para procesar, no crear ejecución y retornar directamente
            if ($totalOfertas === 0) {
                return response()->json([
                    'status'         => 'ok',
                    'total_ofertas'  => 0,
                    'actualizadas'   => 0,
                    'errores'        => 0,
                    'message'        => 'No hay ofertas para actualizar'
                ]);
            }

            // Solo crear la ejecución si hay ofertas para procesar
            $ejecucion = EjecucionGlobal::create([
                'inicio' => now(),
                'nombre' => 'ejecuciones_scrapear_ofertas',
                'log'    => [
                    'token' => $token,
                    'estado' => 'iniciada',
                    'tienda_id' => $tiendaId,
                    'cantidad' => $cantidad,
                    'total_ofertas' => $totalOfertas,
                    'ofertas' => $ofertas->pluck('id')->toArray(),
                    'actualizadas' => 0,
                    'errores' => 0,
                    'procesadas' => 0,
                    'resultados' => []
                ],
            ]);

            $idEjecucion = $ejecucion->id;
            register_shutdown_function(function () use ($idEjecucion) {
                self::cerrarEjecucionScraperHuérfana($idEjecucion);
            });

            $actualizadas = 0;
            $errores      = 0;
            $log          = [];

            // 2) Procesar ofertas en lotes para evitar timeouts
            $loteSize = 10; // Procesar 10 ofertas por lote
            $lotes = $ofertas->chunk($loteSize);
            $procesadas = 0;

            foreach ($lotes as $loteIndex => $lote) {
                foreach ($lote as $oferta) {
                    try {
                        // Verificar si la oferta sigue siendo válida
                        if (!$oferta->exists) {
                            $errores++;
                            $log[] = [
                                'oferta_id'                 => $oferta->id ?? 'desconocido',
                                'tienda_nombre'             => 'Oferta eliminada',
                                'url'                       => 'N/A',
                                'variante'                  => null,
                                'precio_anterior'           => null,
                                'precio_nuevo'              => null,
                                'success'                   => false,
                                'error'                     => 'La oferta ya no existe en la base de datos',
                                'cambios_detectados'        => false,
                                'url_notificacion_llamada'  => false,
                            ];
                            $procesadas++;
                            continue;
                        }

                        $resultado = $this->procesarOfertaScraper($oferta);
                        $log[] = $resultado;

                        if (!empty($resultado['success'])) {
                            $actualizadas++;
                        } else {
                            $errores++;
                        }

                        $procesadas++;

                    } catch (\Throwable $e) {
                        $errores++;
                        $log[] = [
                            'oferta_id'                 => $oferta->id ?? 'desconocido',
                            'tienda_nombre'             => $oferta->tienda->nombre ?? 'Desconocida',
                            'url'                       => $oferta->url ?? 'N/A',
                            'variante'                  => $oferta->variante ?? null,
                            'precio_anterior'           => $oferta->precio_total ?? null,
                            'precio_nuevo'              => null,
                            'success'                   => false,
                            'error'                     => 'Error procesando oferta: ' . $e->getMessage(),
                            'cambios_detectados'        => false,
                            'url_notificacion_llamada'  => false,
                        ];
                        $procesadas++;

                        // Log del error para debugging
                        \Log::error('Error procesando oferta ID: ' . ($oferta->id ?? 'desconocido'), [
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString(),
                            'tienda_id' => $tiendaId,
                            'cantidad' => $cantidad
                        ]);
                    }
                }

                // Actualizar progreso después de cada lote
                $logEstructurado = [
                    'token' => $token,
                    'estado' => 'en_progreso',
                    'tienda_id' => $tiendaId,
                    'cantidad' => $cantidad,
                    'total_ofertas' => $totalOfertas,
                    'ofertas' => $ofertas->pluck('id')->toArray(),
                    'actualizadas' => $actualizadas,
                    'errores' => $errores,
                    'procesadas' => $procesadas,
                    'resultados' => $log
                ];

                $ejecucion->update([
                    'log' => $logEstructurado,
                ]);

                // Pequeña pausa entre lotes para evitar sobrecarga
                if ($loteIndex < count($lotes) - 1) {
                    usleep(100000); // 0.1 segundos
                }
            }

            // Crear estructura JSON organizada final
            $logEstructurado = [
                'token' => $token,
                'estado' => 'completada',
                'tienda_id' => $tiendaId,
                'cantidad' => $cantidad,
                'total_ofertas' => $totalOfertas,
                'ofertas' => $ofertas->pluck('id')->toArray(),
                'actualizadas' => $actualizadas,
                'errores' => $errores,
                'procesadas' => $totalOfertas,
                'resultados' => $log
            ];

            $ejecucion->update([
                'fin'            => now(),
                'total'          => $totalOfertas,
                'total_guardado' => $actualizadas,
                'total_errores'  => $errores,
                'log'            => $logEstructurado,
            ]);

            // Redistribuir updated_at cuando proviene del formulario de tienda
            // (tanto para todas las ofertas como para una cantidad específica)
            if ($tiendaId && $actualizadas > 0) {
                try {
                    $idsActualizadas = collect($log)
                        ->filter(function ($item) {
                            return !empty($item['success']) && !empty($item['oferta_id']);
                        })
                        ->pluck('oferta_id')
                        ->unique()
                        ->values();

                    if ($idsActualizadas->count() > 0) {
                        $ofertasActualizadas = OfertaProducto::whereIn('id', $idsActualizadas)->get();

                        $total = max(1, $ofertasActualizadas->count());
                        $windowSeconds = 12 * 60 * 60; // 08:00 a 20:00
                        $step = intdiv($windowSeconds, $total);

                        // Orden determinista por id
                        $ofertasOrdenadas = $ofertasActualizadas->sortBy('id')->values();

                        foreach ($ofertasOrdenadas as $index => $oferta) {
                            $fechaBase = Carbon::parse($oferta->updated_at)->startOfDay();
                            $hora = (8 * 60 * 60) + ($index * $step);
                            if ($hora > (20 * 60 * 60)) {
                                $hora = 20 * 60 * 60; // clamp a 20:00
                            }
                            $nuevoUpdatedAt = $fechaBase->copy()->addSeconds($hora);

                            // Mantener created_at intacto; solo actualizar updated_at
                            // Usar DB::table para evitar que Laravel sobrescriba updated_at
                            \DB::table('ofertas_producto')
                                ->where('id', $oferta->id)
                                ->update(['updated_at' => $nuevoUpdatedAt]);
                        }
                    }
                } catch (\Throwable $e) {
                    Log::warning('No se pudo redistribuir updated_at de ofertas', [
                        'tienda_id' => $tiendaId,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Ejecutar rutas adicionales después de procesar todas las ofertas
            $this->ejecutarRutasAdicionales($token);

            return response()->json([
                'status'         => 'ok',
                'total_ofertas'  => $totalOfertas,
                'actualizadas'   => $actualizadas,
                'errores'        => $errores,
            ]);

        } catch (\Throwable $e) {
            // Error fatal: conservar progreso ya guardado en BD (y en memoria si hay más filas en $log)
            if ($ejecucion) {
                $ejecucion->refresh();
                $logPrev = is_array($ejecucion->log) ? $ejecucion->log : [];
                if ($ejecucion->fin !== null && ($logPrev['estado'] ?? '') === 'completada') {
                    \Log::error('Error en ScraperSegundoPlanoController tras completar ofertas (p. ej. rutas adicionales)', [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                        'ejecucion_id' => $ejecucion->id,
                    ]);
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Scraping completado pero falló un paso posterior: ' . $e->getMessage(),
                        'total_ofertas' => $logPrev['total_ofertas'] ?? 0,
                        'actualizadas' => $logPrev['actualizadas'] ?? 0,
                        'errores' => $logPrev['errores'] ?? 0,
                    ], 500);
                }
                $logBase = $logPrev;
                $resDb = $logBase['resultados'] ?? [];
                $resLocal = isset($log) && is_array($log) ? $log : [];
                $resultados = count($resLocal) >= count($resDb) ? $resLocal : $resDb;
                $resultados[] = [
                    'oferta_id' => 'error_fatal',
                    'tienda_nombre' => 'Error del sistema',
                    'url' => 'N/A',
                    'variante' => null,
                    'precio_anterior' => null,
                    'precio_nuevo' => null,
                    'success' => false,
                    'error' => 'Error fatal: ' . $e->getMessage(),
                    'cambios_detectados' => false,
                    'url_notificacion_llamada' => false,
                ];
                $procesadasCatch = isset($procesadas) ? (int) $procesadas : (int) ($logBase['procesadas'] ?? 0);
                $actualizadasCatch = isset($actualizadas) ? (int) $actualizadas : (int) ($logBase['actualizadas'] ?? 0);
                $erroresCatch = isset($errores) ? (int) $errores : (int) ($logBase['errores'] ?? 0);
                $logEstructurado = array_merge($logBase, [
                    'token' => $token ?? ($logBase['token'] ?? null),
                    'estado' => 'fallida',
                    'tienda_id' => $tiendaId ?? ($logBase['tienda_id'] ?? null),
                    'cantidad' => $cantidad ?? ($logBase['cantidad'] ?? null),
                    'total_ofertas' => isset($totalOfertas) ? $totalOfertas : (int) ($logBase['total_ofertas'] ?? 0),
                    'ofertas' => isset($ofertas) ? $ofertas->pluck('id')->toArray() : ($logBase['ofertas'] ?? []),
                    'actualizadas' => $actualizadasCatch,
                    'errores' => $erroresCatch + 1,
                    'procesadas' => $procesadasCatch,
                    'resultados' => $resultados,
                ]);

                $ejecucion->update([
                    'fin'            => now(),
                    'total'          => $procesadasCatch,
                    'total_guardado' => $actualizadasCatch,
                    'total_errores'  => $erroresCatch + 1,
                    'log'            => $logEstructurado,
                ]);
            }

            // Log del error fatal
            \Log::error('Error fatal en ScraperSegundoPlanoController', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'tienda_id' => $tiendaId,
                'cantidad' => $cantidad
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Error fatal durante la ejecución: ' . $e->getMessage(),
                'total_ofertas' => 0,
                'actualizadas' => 0,
                'errores' => 1,
            ], 500);
        }
    }

    /**
     * Obtener ofertas de una tienda específica
     */
    protected function obtenerOfertasTienda($tiendaId, $cantidad = null)
    {
        $query = OfertaProducto::with(['producto', 'tienda'])
            ->where('tienda_id', $tiendaId)
            ->where('mostrar', 'si')
            ->where('como_scrapear', 'automatico')
            ->whereNull('chollo_id');

        if ($cantidad) {
            $query->limit($cantidad);
        }

        return $query->get();
    }

    /**
     * Ejecutar rutas adicionales después de procesar todas las ofertas
     */
    private function ejecutarRutasAdicionales($token)
    {
        try {
            $baseUrl = config('app.url');
            
            // Primera ruta: precio-bajo/ejecutar-segundo-plano
            $url1 = $baseUrl . '/panel-privado/productos/precio-bajo/ejecutar-segundo-plano?token=' . $token;
            
            Log::info('Ejecutando ruta adicional 1: precio-bajo/ejecutar-segundo-plano');
            
            $response1 = Http::timeout(300) // 5 minutos de timeout
                ->get($url1);
            
            if ($response1->successful()) {
                Log::info('Ruta precio-bajo ejecutada exitosamente', [
                    'status' => $response1->status(),
                    'response' => $response1->json()
                ]);
            } else {
                Log::warning('Error ejecutando ruta precio-bajo', [
                    'status' => $response1->status(),
                    'response' => $response1->body()
                ]);
            }
            
            // Esperar 5 segundos antes de ejecutar la segunda ruta
            sleep(5);
            
            // Segunda ruta: precios-hot/ejecutar-segundo-plano
            $url2 = route('precios-hot.ejecutar.segundo-plano', ['token' => $token]);
            
            Log::info('Ejecutando ruta adicional 2: precios-hot/ejecutar-segundo-plano');
            
            $response2 = Http::timeout(300) // 5 minutos de timeout
                ->get($url2);
            
            if ($response2->successful()) {
                Log::info('Ruta precios-hot ejecutada exitosamente', [
                    'status' => $response2->status(),
                    'response' => $response2->json()
                ]);
            } else {
                Log::warning('Error ejecutando ruta precios-hot', [
                    'status' => $response2->status(),
                    'response' => $response2->body()
                ]);
            }
            
        } catch (\Exception $e) {
            Log::error('Error ejecutando rutas adicionales', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
