<?php

namespace App\Http\Controllers\Crons;

use App\Http\Controllers\Controller;
use App\Models\Ajuste;
use App\Models\EjecucionGlobal;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CronJefeController extends Controller
{
    private function getCronMetadata(): array
    {
        return [
            'clicks_actualizar_ofertas' => [
                'name' => 'Actualizar Clicks de Ofertas',
                'route' => 'admin/clicks/actualizar-ofertas',
                'log_name' => 'ejecuciones_actualizar_clicks_ofertas',
                'def_active' => '1', 'def_daily' => 'desactivado', 'def_hourly' => '1h'
            ],
            'historico_guardar_productos' => [
                'name' => 'Guardar Histórico de Precios de Productos',
                'route' => 'admin/historico/guardar-productos',
                'log_name' => 'ejecuciones_historico_precios_productos',
                'def_active' => '1', 'def_daily' => 'desactivado', 'def_hourly' => '2h'
            ],
            'productos_actualizar_oferta_mas_barata' => [
                'name' => 'Actualizar Oferta Más Barata de cada Producto',
                'route' => 'admin/productos/actualizar-oferta-mas-barata',
                'log_name' => 'ejecuciones_actualizar_oferta_mas_barata_productos',
                'def_active' => '1', 'def_daily' => 'desactivado', 'def_hourly' => '1h'
            ],
            'precios_hot_calcular' => [
                'name' => 'Calcular Precios Hot',
                'route' => 'admin/precios-hot/calcular',
                'log_name' => 'precios_hot',
                'def_active' => '1', 'def_daily' => 'cada_dia', 'def_hourly' => 'desactivado'
            ],
            'categorias_actualizar_clicks' => [
                'name' => 'Actualizar Clicks de Categorías',
                'route' => 'admin/categorias/actualizar-clicks/procesar',
                'log_name' => 'ejecuciones_clicks_categoria',
                'def_active' => '1', 'def_daily' => 'desactivado', 'def_hourly' => '1h'
            ],
            'productos_actualizar_clicks' => [
                'name' => 'Actualizar Clicks de Productos',
                'route' => 'admin/productos/actualizar-clicks/procesar',
                'log_name' => 'ejecuciones_clicks_producto',
                'def_active' => '1', 'def_daily' => 'desactivado', 'def_hourly' => '1h'
            ],
            'ofertas_historico_precios' => [
                'name' => 'Guardar Histórico de Precios de Ofertas',
                'route' => 'admin/ofertas/historico-precios/ejecutar',
                'log_name' => 'ejecuciones_historico_precios_ofertas',
                'def_active' => '1', 'def_daily' => 'desactivado', 'def_hourly' => '6h'
            ],
            'ofertas_comprobar_gastos_envio' => [
                'name' => 'Comprobar Gastos de Envío de Ofertas',
                'route' => 'admin/ofertas/comprobar-gastos-envio',
                'log_name' => 'ejecuciones_comprobar_gastos_envio_ofertas',
                'def_active' => '1', 'def_daily' => 'desactivado', 'def_hourly' => '12h'
            ],
            'clicks_procesar_geolocalizacion' => [
                'name' => 'Procesar Geolocalización de Clicks',
                'route' => 'admin/clicks/procesar-geolocalizacion',
                'log_name' => 'ejecuciones_clicks_geolocalizacion',
                'def_active' => '1', 'def_daily' => 'desactivado', 'def_hourly' => '1h'
            ],
            'ofertas_actualizar_contador_spec' => [
                'name' => 'Actualizar Contador de Ofertas por Especificación',
                'route' => 'admin/ofertas/actualizar-contador-especificaciones',
                'log_name' => 'ejecuciones_contador_especificaciones',
                'def_active' => '1', 'def_daily' => 'desactivado', 'def_hourly' => '1h'
            ],
            'cron_avisos_sin_stock_scrapear' => [
                'name' => 'Scrapear Avisos Sin Stock Vencidos',
                'route' => 'admin/avisos/scrapear-sin-stock',
                'log_name' => 'cron_avisos_sin_stock_scrapear',
                'def_active' => '1', 'def_daily' => 'desactivado', 'def_hourly' => '1h'
            ],
            'cron_avisos_generar_correo_precio' => [
                'name' => 'Generar Avisos de Correo por Precio',
                'route' => 'admin/avisos/generar-correo-precio',
                'log_name' => 'cron_avisos_generar_correo_precio',
                'def_active' => '1', 'def_daily' => 'desactivado', 'def_hourly' => '1h'
            ],
            'cron_neo_objetivos' => [
                'name' => 'Cron Neo Objetivos',
                'route' => 'admin/cron-neo-objetivos',
                'log_name' => 'cron_neo_objetivos',
                'def_active' => '1', 'def_daily' => 'cada_dia', 'def_hourly' => 'desactivado'
            ],
            'cron_descarga_csv_tiendas' => [
                'name' => 'Descarga Feeds CSV-Awin de Tiendas',
                'route' => 'admin/cron-descarga-csv-tiendas',
                'log_name' => 'cron_descarga_csv_tiendas',
                'def_active' => '1', 'def_daily' => 'cada_dia', 'def_hourly' => 'desactivado'
            ],
            'cron_buscar_amazon_productos' => [
                'name' => 'Buscar Productos Amazon y AliExpress',
                'route' => 'admin/productos/buscar-amazon/cron',
                'log_name' => 'cron_buscar_amazon_productos',
                'def_active' => '1', 'def_daily' => 'cada_dia', 'def_hourly' => 'desactivado'
            ],
            'ofertas_scraper_segundo_plano' => [
                'name' => 'Scraper de Ofertas en Segundo Plano',
                'route' => 'ofertas/scraper/ejecutar-segundo-plano',
                'log_name' => 'ejecuciones_scrapear_ofertas',
                'def_active' => '1', 'def_daily' => 'desactivado', 'def_hourly' => '1h'
            ],
            'actualizar_primera_oferta_segundo_plano' => [
                'name' => 'Actualizar Primera Oferta en Segundo Plano',
                'route' => 'actualizar-primera-oferta/ejecutar-segundo-plano',
                'log_name' => 'actualizar_primera_oferta',
                'def_active' => '1', 'def_daily' => 'desactivado', 'def_hourly' => '1h'
            ],
            'chollos_comprobar_finalizados' => [
                'name' => 'Comprobar Chollos y Ofertas Finalizados',
                'route' => 'chollos/comprobar-finalizados/ejecutar-segundo-plano',
                'log_name' => 'ejecuciones_chollos_comprobar_finalizados',
                'def_active' => '1', 'def_daily' => 'desactivado', 'def_hourly' => '2h'
            ]
        ];
    }

    public function index(Request $request)
    {
        // 1. Inicializar filtros de fecha y rangos (por defecto: hoy)
        $filtroRapido = $request->input('filtro_rapido', 'hoy');
        $fechaDesde = $request->input('fecha_desde');
        $fechaHasta = $request->input('fecha_hasta');
        $horaDesde = $request->input('hora_desde');
        $horaHasta = $request->input('hora_hasta');
        $busqueda = $request->input('buscar');

        $hoy = Carbon::today();

        if ($filtroRapido && $filtroRapido !== 'siempre' && !$fechaDesde && !$fechaHasta) {
            switch ($filtroRapido) {
                case 'hoy':
                    $fechaDesde = $fechaHasta = $hoy->format('Y-m-d');
                    break;
                case 'ayer':
                    $ayer = $hoy->copy()->subDay();
                    $fechaDesde = $fechaHasta = $ayer->format('Y-m-d');
                    break;
                case '7dias':
                    $fechaDesde = $hoy->copy()->subDays(7)->format('Y-m-d');
                    $fechaHasta = $hoy->format('Y-m-d');
                    break;
                case '30dias':
                    $fechaDesde = $hoy->copy()->subDays(30)->format('Y-m-d');
                    $fechaHasta = $hoy->format('Y-m-d');
                    break;
                case '90dias':
                    $fechaDesde = $hoy->copy()->subDays(90)->format('Y-m-d');
                    $fechaHasta = $hoy->format('Y-m-d');
                    break;
                default:
                    $fechaDesde = $fechaHasta = $hoy->format('Y-m-d');
                    $filtroRapido = 'hoy';
                    break;
            }
        } elseif ($filtroRapido === 'siempre' && !$fechaDesde && !$fechaHasta) {
            $fechaDesde = '2025-01-01'; // una fecha de inicio razonable
            $fechaHasta = $hoy->format('Y-m-d');
        } else {
            // Rango manual
            if (!$fechaDesde) {
                $fechaDesde = $hoy->format('Y-m-d');
            }
            if (!$fechaHasta) {
                $fechaHasta = $hoy->format('Y-m-d');
            }
        }

        // 2. Cargar crons e inicializar ajustes si no existen
        $cronsMetadata = $this->getCronMetadata();
        $crons = [];

        foreach ($cronsMetadata as $key => $meta) {
            $activoKey = "cron_{$key}_activo";
            $minKey = "cron_{$key}_minuto";
            $hourKey = "cron_{$key}_hora";
            $dayKey = "cron_{$key}_dia";
            $monthKey = "cron_{$key}_mes";
            $dayOfWeekKey = "cron_{$key}_dia_semana";

            // Seeding on-demand / Migración
            if (Ajuste::getVal($activoKey) === null) {
                Ajuste::setVal($activoKey, $meta['def_active'], 'cron');
            }

            if (Ajuste::getVal($minKey) === null) {
                // Intentar migrar desde los antiguos ajustes de frecuencia
                $oldDaily = Ajuste::getVal("cron_{$key}_frecuencia_diaria");
                $oldHourly = Ajuste::getVal("cron_{$key}_frecuencia_horaria");

                $defMin = '0';
                $defHour = '*';
                $defDay = '*';
                $defMonth = '*';
                $defDayOfWeek = '*';

                if ($oldDaily && $oldDaily !== 'desactivado') {
                    if ($oldDaily === 'cada_dia') {
                        $defMin = '0';
                        $defHour = ($key === 'precios_hot_calcular') ? '6' : '0';
                    } elseif ($oldDaily === 'cada_semana') {
                        $defMin = '0';
                        $defHour = '0';
                        $defDayOfWeek = '1'; // lunes
                    } elseif ($oldDaily === 'cada_mes') {
                        $defMin = '0';
                        $defHour = '0';
                        $defDay = '1'; // dia 1
                    }
                } else {
                    // Valores por defecto basados en def_daily del metadata original
                    if ($meta['def_daily'] === 'cada_dia') {
                        $defMin = '0';
                        $defHour = ($key === 'precios_hot_calcular') ? '6' : '0';
                    }
                }

                $hourlyVal = $oldHourly ?: $meta['def_hourly'];
                if ($hourlyVal && $hourlyVal !== 'desactivado') {
                    if (str_starts_with($hourlyVal, 'at_')) {
                        $time = substr($hourlyVal, 3);
                        $parts = explode(':', $time);
                        $defMin = strval(intval($parts[1] ?? 0));
                        $defHour = strval(intval($parts[0] ?? 0));
                    } else {
                        if ($hourlyVal === '12h') {
                            $defHour = '*/12';
                        } elseif ($hourlyVal === '6h') {
                            $defHour = '*/6';
                        } elseif ($hourlyVal === '2h') {
                            $defHour = '*/2';
                        } elseif ($hourlyVal === '1h') {
                            $defHour = '*';
                        } elseif ($hourlyVal === '30m') {
                            $defMin = '*/30';
                            $defHour = '*';
                        } elseif ($hourlyVal === '10m') {
                            $defMin = '*/10';
                            $defHour = '*';
                        }
                    }
                }

                Ajuste::setVal($minKey, strval($defMin), 'cron');
                Ajuste::setVal($hourKey, strval($defHour), 'cron');
                Ajuste::setVal($dayKey, strval($defDay), 'cron');
                Ajuste::setVal($monthKey, strval($defMonth), 'cron');
                Ajuste::setVal($dayOfWeekKey, strval($defDayOfWeek), 'cron');
            }

            // Consultar último estado de ejecución
            $ultima = EjecucionGlobal::where('nombre', $meta['log_name'])
                ->orderByDesc('inicio')
                ->first();

            $minuto = Ajuste::getVal($minKey);
            $hora = Ajuste::getVal($hourKey);
            $dia = Ajuste::getVal($dayKey);
            $mes = Ajuste::getVal($monthKey);
            $diaSemana = Ajuste::getVal($dayOfWeekKey);
            $ejecucionesDia = $this->calcularEjecucionesPorDia($minuto, $hora, $dia, $mes, $diaSemana);

            $fileName = str_starts_with($key, 'cron_') ? $key : 'cron_' . $key;
            $crons[$key] = [
                'key' => $key,
                'name' => $meta['name'],
                'route' => $meta['route'],
                'log_name' => $meta['log_name'],
                'activo' => Ajuste::getVal($activoKey) === '1',
                'minuto' => $minuto,
                'hora' => $hora,
                'dia' => $dia,
                'mes' => $mes,
                'dia_semana' => $diaSemana,
                'ejecuciones_dia' => $ejecucionesDia,
                'comando' => '/usr/local/bin/ea-php82 /home/iaflobom/komparador-public/cron/' . $fileName . '.php >/dev/null 2>&1',
                'ultima_ejecucion' => $ultima
            ];
        }

        // Activos primero (de más a menos frecuente); inactivos al final
        uasort($crons, function ($a, $b) {
            if ($a['activo'] !== $b['activo']) {
                return $a['activo'] ? -1 : 1;
            }
            $diff = $b['ejecuciones_dia'] <=> $a['ejecuciones_dia'];
            if ($diff !== 0) {
                return $diff;
            }
            return strcmp($a['name'], $b['name']);
        });

        // 3. Buscar ejecuciones del periodo para gráficos e informe temporal
        $nombresLogs = array_column($cronsMetadata, 'log_name');
        $query = EjecucionGlobal::whereIn('nombre', $nombresLogs)
            ->whereBetween('inicio', [$fechaDesde . ' 00:00:00', $fechaHasta . ' 23:59:59']);

        if ($horaDesde) {
            $query->whereTime('inicio', '>=', $horaDesde);
        }
        if ($horaHasta) {
            $query->whereTime('inicio', '<=', $horaHasta);
        }
        if ($busqueda) {
            $query->where(function ($q) use ($busqueda) {
                $q->where('nombre', 'like', "%{$busqueda}%")
                  ->orWhere('log', 'like', "%{$busqueda}%");
            });
        }

        $ejecuciones = $query->orderBy('inicio', 'asc')->get();

        // 4. Timeline continuo (barras por rango real inicio→fin)
        $isSingleDay = ($fechaDesde === $fechaHasta);
        $labels = [];
        $timelineBars = [];
        $timelineMeta = [
            'mode' => $isSingleDay ? 'day' : 'range',
            'width_px' => 0,
            'ticks' => [],
            'grid_cells' => [],
            'grid_interval' => null,
        ];

        foreach ($crons as $key => $cron) {
            $timelineBars[$key] = [];
        }

        if ($isSingleDay) {
            // Intervalo de cuadrícula = el más fino de los crons activos
            $gridInterval = 60;
            foreach ($crons as $cron) {
                if (!$cron['activo']) {
                    continue;
                }
                $minutoVal = (string) ($cron['minuto'] ?? '');
                if (str_contains($minutoVal, '/10') || $minutoVal === '*/10') {
                    $gridInterval = 10;
                    break;
                }
                if (str_contains($minutoVal, '/15') || $minutoVal === '*/15') {
                    $gridInterval = min($gridInterval, 15);
                } elseif (str_contains($minutoVal, '/30') || $minutoVal === '*/30') {
                    $gridInterval = min($gridInterval, 30);
                } elseif ($minutoVal === '*' || preg_match('/^\*\/1$/', $minutoVal)) {
                    $gridInterval = 10;
                    break;
                }
            }

            // Escala: 2px por minuto → 2880px el día completo
            $pxPerMinute = 2;
            $totalMinutes = 1440;
            $timelineMeta['width_px'] = $totalMinutes * $pxPerMinute;
            $timelineMeta['grid_interval'] = $gridInterval;
            $cellWidthPct = ($gridInterval / $totalMinutes) * 100;

            for ($min = 0; $min < $totalMinutes; $min += $gridInterval) {
                $h = intdiv($min, 60);
                $m = $min % 60;
                $label = str_pad($h, 2, '0', STR_PAD_LEFT) . ':' . str_pad($m, 2, '0', STR_PAD_LEFT);
                $isHour = ($m === 0);
                $leftPct = ($min / $totalMinutes) * 100;

                $timelineMeta['grid_cells'][] = [
                    'label' => $label,
                    'left_pct' => $leftPct,
                    'width_pct' => $cellWidthPct,
                    'is_hour' => $isHour,
                ];

                if ($isHour) {
                    $labels[] = $label;
                }

                $timelineMeta['ticks'][] = [
                    'label' => $label,
                    'left_pct' => $leftPct,
                    'is_hour' => $isHour,
                ];
            }
        } else {
            $start = Carbon::parse($fechaDesde)->startOfDay();
            $end = Carbon::parse($fechaHasta)->startOfDay();
            $totalDays = max(1, $start->diffInDays($end) + 1);
            $pxPerDay = max(80, (int) floor(2400 / $totalDays));
            $timelineMeta['width_px'] = $totalDays * $pxPerDay;
            $timelineMeta['total_days'] = $totalDays;
            $timelineMeta['range_start'] = $start->toDateString();
            $cellWidthPct = (1 / $totalDays) * 100;

            for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
                $dayIndex = $start->diffInDays($d);
                $label = $d->format('d/m');
                $leftPct = ($dayIndex / $totalDays) * 100;
                $labels[] = $label;
                $timelineMeta['ticks'][] = [
                    'label' => $label,
                    'left_pct' => $leftPct,
                    'is_hour' => true,
                    'date' => $d->format('Y-m-d'),
                ];
                $timelineMeta['grid_cells'][] = [
                    'label' => $label,
                    'left_pct' => $leftPct,
                    'width_pct' => $cellWidthPct,
                    'is_hour' => false,
                    'alt' => ($dayIndex % 2 === 0),
                ];
            }
        }

        $historyRoutesByKey = [
            'clicks_actualizar_ofertas' => route('admin.ofertas.actualizar.clicks.ejecuciones'),
            'historico_guardar_productos' => route('admin.productos.historico.ejecuciones'),
            'precios_hot_calcular' => route('admin.precios-hot.ejecuciones'),
            'categorias_actualizar_clicks' => route('admin.categorias.actualizar.clicks.ejecuciones'),
            'productos_actualizar_clicks' => route('admin.productos.actualizar.clicks.ejecuciones'),
            'cron_avisos_sin_stock_scrapear' => route('admin.ejecuciones.avisos-sin-stock-scrapear'),
            'cron_descarga_csv_tiendas' => route('admin.ejecuciones.descarga-csv-tiendas'),
            'cron_buscar_amazon_productos' => route('admin.ejecuciones.buscar-amazon-productos'),
            'ofertas_scraper_segundo_plano' => route('admin.ofertas.scraper.ejecuciones'),
            'actualizar_primera_oferta_segundo_plano' => route('admin.scraping.actualizar-primera-oferta.historial'),
            'cron_neo_objetivos' => url('/panel-privado/ejecuciones/neo-objetivos'),
        ];

        foreach ($ejecuciones as $ejec) {
            $momentoInicio = Carbon::parse($ejec->inicio);
            $isRunning = (is_null($ejec->fin) || !$ejec->fin);
            // En curso: la barra llega hasta el momento de cargar la página
            $momentoFin = $isRunning
                ? now()
                : Carbon::parse($ejec->fin);

            $cronKey = null;
            foreach ($cronsMetadata as $cKey => $cMeta) {
                if ($cMeta['log_name'] === $ejec->nombre) {
                    $cronKey = $cKey;
                    break;
                }
            }
            if (!$cronKey || !isset($timelineBars[$cronKey])) {
                continue;
            }

            if ($isRunning) {
                $nuevoStatus = 'running';
            } else {
                $logEstado = is_array($ejec->log) ? ($ejec->log['estado'] ?? '') : '';
                if ($ejec->nombre === \App\Http\Controllers\Crons\AvisosSinStockScrapearCronController::NOMBRE_EJECUCION_GLOBAL) {
                    $tieneErrores = ($logEstado === 'error');
                } else {
                    $tieneErrores = ($ejec->total_errores > 0 || $logEstado === 'error');
                }
                $nuevoStatus = $tieneErrores ? 'error' : 'success';
            }

            if ($isSingleDay) {
                $startSec = ($momentoInicio->hour * 3600) + ($momentoInicio->minute * 60) + $momentoInicio->second;
                $endSec = ($momentoFin->hour * 3600) + ($momentoFin->minute * 60) + $momentoFin->second;
                if ($endSec <= $startSec) {
                    $endSec = $startSec + 30;
                }
                // Si cruza medianoche, cortar al final del día visible
                $endSec = min($endSec, 86400);
                $startSec = max(0, min($startSec, 86399));

                $leftPct = ($startSec / 86400) * 100;
                $widthPct = (($endSec - $startSec) / 86400) * 100;
            } else {
                $rangeStart = Carbon::parse($timelineMeta['range_start'])->startOfDay();
                $totalDays = (int) $timelineMeta['total_days'];
                $dayIndex = $rangeStart->diffInDays($momentoInicio->copy()->startOfDay());
                if ($dayIndex < 0 || $dayIndex >= $totalDays) {
                    continue;
                }

                $startFrac = (($momentoInicio->hour * 3600) + ($momentoInicio->minute * 60) + $momentoInicio->second) / 86400;
                $durSec = max(30, $momentoFin->diffInSeconds($momentoInicio));
                $widthFrac = $durSec / (86400 * $totalDays);

                $leftPct = (($dayIndex + $startFrac) / $totalDays) * 100;
                $widthPct = $widthFrac * 100;
            }

            // Ancho mínimo visible (~4px)
            $minWidthPct = ($timelineMeta['width_px'] > 0)
                ? (4 / $timelineMeta['width_px']) * 100
                : 0.05;
            $widthPct = max($widthPct, $minWidthPct);
            if ($leftPct + $widthPct > 100) {
                $widthPct = max(0.01, 100 - $leftPct);
            }

            $logSummaryText = 'Completado.';
            $logVal = $ejec->log;
            if (is_array($logVal) || is_object($logVal)) {
                $logVal = (array) $logVal;
                $logSummaryText = $logVal['resumen'] ?? ($logVal['error'] ?? ($logVal['message'] ?? json_encode($logVal, JSON_UNESCAPED_UNICODE)));
            } else {
                $logSummaryText = strval($logVal);
            }
            $logSummaryText = substr(
                is_array($logSummaryText) || is_object($logSummaryText)
                    ? json_encode($logSummaryText, JSON_UNESCAPED_UNICODE)
                    : $logSummaryText,
                0,
                500
            );

            $erroresMostrar = (int) $ejec->total_errores;
            if ($cronKey === 'cron_avisos_sin_stock_scrapear') {
                $logEstado = is_array($ejec->log) ? ($ejec->log['estado'] ?? '') : '';
                $erroresMostrar = ($logEstado === 'error') ? $erroresMostrar : 0;
            }

            $timelineBars[$cronKey][] = [
                'status' => $nuevoStatus,
                'exec' => $ejec,
                'left_pct' => round($leftPct, 4),
                'width_pct' => round($widthPct, 4),
                'start_ts' => $momentoInicio->timestamp,
                'end_ts' => $momentoFin->timestamp,
                'history_url' => $historyRoutesByKey[$cronKey] ?? null,
                'log_summary' => $logSummaryText,
                'errores_mostrar' => $erroresMostrar,
            ];
        }

        // Asignar carriles si se solapan en la misma fila
        foreach ($timelineBars as $key => &$bars) {
            usort($bars, fn ($a, $b) => $a['start_ts'] <=> $b['start_ts']);
            $laneEnds = [];
            foreach ($bars as &$bar) {
                $lane = 0;
                while (isset($laneEnds[$lane]) && $laneEnds[$lane] > $bar['start_ts']) {
                    $lane++;
                }
                $laneEnds[$lane] = $bar['end_ts'];
                $bar['lane'] = $lane;
            }
            unset($bar);
        }
        unset($bars);

        // Compatibilidad: heatmap vacío por si algo más lo referencia
        $heatmapData = [];
        $slots = [];

        return view('admin.ajustes.ajustes', compact(
            'crons',
            'filtroRapido',
            'fechaDesde',
            'fechaHasta',
            'horaDesde',
            'horaHasta',
            'busqueda',
            'labels',
            'heatmapData',
            'slots',
            'isSingleDay',
            'timelineBars',
            'timelineMeta'
        ));
    }

    public function actualizarAjuste(Request $request): JsonResponse
    {
        $key = $request->input('key');
        $campo = $request->input('campo'); // 'activo', 'frecuencia_diaria', 'frecuencia_horaria'
        $value = $request->input('value');

        if (!array_key_exists($key, $this->getCronMetadata())) {
            return response()->json(['status' => 'error', 'message' => 'Cron inválido.'], 422);
        }

        $claveAjuste = "cron_{$key}_{$campo}";
        
        // Guardar ajuste
        Ajuste::setVal($claveAjuste, $value, 'cron');

        return response()->json([
            'status' => 'ok',
            'message' => 'Ajuste actualizado correctamente.',
            'clave' => $claveAjuste,
            'valor' => $value
        ]);
    }

    public function ejecutarCronManual(Request $request, string $key)
    {
        @set_time_limit(0);
        @ignore_user_abort(true);

        $metadata = $this->getCronMetadata();
        if (!array_key_exists($key, $metadata)) {
            return response()->json(['status' => 'error', 'message' => 'Cron no encontrado.'], 404);
        }

        $cronMeta = $metadata[$key];
        $token = env('TOKEN_ACTUALIZAR_PRECIOS');
        $requestSimulated = new Request(['token' => $token]);

        try {
            $res = null;

            switch ($key) {
                case 'clicks_actualizar_ofertas':
                    $res = app(\App\Http\Controllers\OfertaProductoController::class)->actualizarClicksOfertas();
                    break;
                case 'historico_guardar_productos':
                    $res = app(\App\Http\Controllers\ProductoController::class)->guardarHistoricoPrecios();
                    break;
                case 'productos_actualizar_oferta_mas_barata':
                    $res = app(\App\Http\Controllers\ProductoController::class)->actualizarOfertaMasBarataPorProducto($requestSimulated);
                    break;
                case 'precios_hot_calcular':
                    $res = app(\App\Http\Controllers\PrecioHotController::class)->ejecutarSegundoPlano($requestSimulated);
                    break;
                case 'categorias_actualizar_clicks':
                    $res = app(\App\Http\Controllers\CategoriaClicksController::class)->procesar();
                    break;
                case 'productos_actualizar_clicks':
                    $res = app(\App\Http\Controllers\ProductoController::class)->actualizarClicks();
                    break;
                case 'ofertas_historico_precios':
                    $res = app(\App\Http\Controllers\OfertaProductoController::class)->ejecutarHistoricoPrecios();
                    break;
                case 'ofertas_comprobar_gastos_envio':
                    $res = app(\App\Http\Controllers\OfertaProductoController::class)->comprobarGastosEnvioOfertas();
                    break;
                case 'clicks_procesar_geolocalizacion':
                    $res = app(\App\Http\Controllers\ClickController::class)->procesarGeolocalizacion($requestSimulated);
                    break;
                case 'ofertas_actualizar_contador_spec':
                    Artisan::call('ofertas:actualizar-contador-especificaciones');
                    $res = ['status' => 'ok', 'message' => 'Contador actualizado.'];
                    break;
                case 'cron_avisos_sin_stock_scrapear':
                    $res = app(\App\Http\Controllers\Crons\AvisosSinStockScrapearCronController::class)();
                    break;
                case 'cron_avisos_generar_correo_precio':
                    $res = app(\App\Http\Controllers\Crons\AvisosCorreoPrecioCronController::class)();
                    break;
                case 'cron_neo_objetivos':
                    $res = app(\App\Http\Controllers\Crons\CronNeoObjetivosController::class)($requestSimulated);
                    break;
                case 'cron_descarga_csv_tiendas':
                    $res = app(\App\Http\Controllers\Crons\CronDescargaCSVTiendasController::class)($requestSimulated);
                    break;
                case 'cron_buscar_amazon_productos':
                    $res = app(\App\Http\Controllers\Crons\CronBuscarProductosAmazonController::class)->ejecutarCron();
                    break;
                case 'ofertas_scraper_segundo_plano':
                    $res = app(\App\Http\Controllers\OfertaProductoController::class)->ejecutarScraperOfertasSegundoPlano($requestSimulated);
                    break;
                case 'actualizar_primera_oferta_segundo_plano':
                    $res = app(\App\Http\Controllers\Scraping\ActualizarPrimeraOfertaController::class)->ejecutarSegundoPlano($requestSimulated);
                    break;
                case 'chollos_comprobar_finalizados':
                    $res = app(\App\Http\Controllers\CholloController::class)->comprobarChollosYOfertasFinalizadas($requestSimulated);
                    break;
            }

            // Crear registro de ejecución simple para los crons que no lo hacen internamente si hiciera falta
            // (Ya la mayoría lo hace).
            $detail = '';
            if ($res instanceof JsonResponse) {
                $content = json_decode($res->getContent(), true);
                $detail = $content['message'] ?? ($content['status'] ?? 'Completado');
            } elseif (is_array($res)) {
                $detail = $res['message'] ?? 'Completado';
            } elseif (is_string($res)) {
                $detail = substr($res, 0, 100);
            }

            return response()->json([
                'status' => 'ok',
                'message' => 'Cron "' . $cronMeta['name'] . '" ejecutado con éxito manualmente.',
                'detail' => $detail
            ]);

        } catch (\Throwable $e) {
            Log::error("Error ejecutando cron manual ($key): " . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'status' => 'error',
                'message' => 'Error ejecutando el cron: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Estima ejecuciones medias por día a partir de una expresión cron (5 campos).
     */
    private function calcularEjecucionesPorDia(?string $minuto, ?string $hora, ?string $dia, ?string $mes, ?string $diaSemana): float
    {
        $mins = $this->countCronFieldMatches($minuto, 0, 59);
        $hours = $this->countCronFieldMatches($hora, 0, 23);
        $days = $this->countCronFieldMatches($dia, 1, 31);
        $months = $this->countCronFieldMatches($mes, 1, 12);
        $dows = $this->countCronFieldMatchesDow($diaSemana);

        $perMatchingDay = $mins * $hours;

        $diaRestricted = trim((string) $dia) !== '*' && trim((string) $dia) !== '';
        $dowRestricted = trim((string) $diaSemana) !== '*' && trim((string) $diaSemana) !== '';
        $mesRestricted = trim((string) $mes) !== '*' && trim((string) $mes) !== '';

        if (!$diaRestricted && !$dowRestricted) {
            $dayFactor = 1.0;
        } elseif ($diaRestricted && $dowRestricted) {
            // En cron clásico, si ambos están restringidos se usa OR
            $pDay = $days / 30.437;
            $pDow = $dows / 7;
            $dayFactor = min(1.0, $pDay + $pDow - ($pDay * $pDow));
        } elseif ($diaRestricted) {
            $dayFactor = $days / 30.437;
        } else {
            $dayFactor = $dows / 7;
        }

        $monthFactor = $mesRestricted ? ($months / 12.0) : 1.0;

        return round($perMatchingDay * $dayFactor * $monthFactor, 4);
    }

    /**
     * Cuenta valores distintos que cubre un campo cron en un rango inclusivo.
     */
    private function countCronFieldMatches(?string $field, int $min, int $max): int
    {
        $field = trim((string) $field);
        if ($field === '' || $field === '*') {
            return $max - $min + 1;
        }

        $values = [];
        foreach (explode(',', $field) as $part) {
            $part = trim($part);
            if ($part === '') {
                continue;
            }

            if (str_contains($part, '/')) {
                [$range, $stepStr] = explode('/', $part, 2);
                $step = max(1, (int) $stepStr);
                if ($range === '*' || $range === '') {
                    $start = $min;
                    $end = $max;
                } elseif (str_contains($range, '-')) {
                    [$start, $end] = array_map('intval', explode('-', $range, 2));
                } else {
                    $start = (int) $range;
                    $end = $max;
                }
                for ($i = $start; $i <= $end; $i += $step) {
                    if ($i >= $min && $i <= $max) {
                        $values[$i] = true;
                    }
                }
                continue;
            }

            if (str_contains($part, '-')) {
                [$start, $end] = array_map('intval', explode('-', $part, 2));
                for ($i = $start; $i <= $end; $i++) {
                    if ($i >= $min && $i <= $max) {
                        $values[$i] = true;
                    }
                }
                continue;
            }

            $v = (int) $part;
            if ($v >= $min && $v <= $max) {
                $values[$v] = true;
            }
        }

        return count($values);
    }

    /**
     * Día de la semana: 0-7 (0 y 7 = domingo) → máximo 7 días distintos.
     */
    private function countCronFieldMatchesDow(?string $field): int
    {
        $field = trim((string) $field);
        if ($field === '' || $field === '*') {
            return 7;
        }

        $raw = [];
        foreach (explode(',', $field) as $part) {
            $part = trim($part);
            if ($part === '') {
                continue;
            }

            if (str_contains($part, '/')) {
                [$range, $stepStr] = explode('/', $part, 2);
                $step = max(1, (int) $stepStr);
                if ($range === '*' || $range === '') {
                    $start = 0;
                    $end = 6;
                } elseif (str_contains($range, '-')) {
                    [$start, $end] = array_map('intval', explode('-', $range, 2));
                } else {
                    $start = (int) $range;
                    $end = 6;
                }
                for ($i = $start; $i <= $end; $i += $step) {
                    $raw[$i] = true;
                }
                continue;
            }

            if (str_contains($part, '-')) {
                [$start, $end] = array_map('intval', explode('-', $part, 2));
                for ($i = $start; $i <= $end; $i++) {
                    $raw[$i] = true;
                }
                continue;
            }

            $raw[(int) $part] = true;
        }

        $normalized = [];
        foreach (array_keys($raw) as $v) {
            if ($v === 7) {
                $v = 0;
            }
            if ($v >= 0 && $v <= 6) {
                $normalized[$v] = true;
            }
        }

        return count($normalized) ?: 0;
    }
}
