<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.dashboard') }}">
                <h2 class="font-semibold text-xl text-white leading-tight">Inicio -></h2>
            </a>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Cron descarga CSV tiendas — Resultado
            </h2>
        </div>
    </x-slot>

    <div class="py-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="max-w-7xl mx-auto space-y-6">
            <p class="text-sm text-gray-600 dark:text-gray-400">
                Cada llamada (schedule, URL o botón del panel) lanza un proceso CLI en segundo plano para no morir al cortar el hosting a ~5&nbsp;min. El progreso de cada importación a <code class="text-xs">csv_ofertas</code> queda en estas filas.
            </p>

            @if (session('success'))
                <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4 text-sm text-green-800 dark:text-green-200">
                    {{ session('success') }}
                </div>
            @endif
            @if (session('error'))
                <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4 text-sm text-red-800 dark:text-red-200">
                    {{ session('error') }}
                </div>
            @endif

            @php
                $diaSeleccionadoTexto = $fechaSeleccionada->locale('es')->translatedFormat('d \\d\\e F \\d\\e Y');
                $mesTexto = $mesSeleccionado->locale('es')->translatedFormat('F Y');
                $mesAnterior = $mesSeleccionado->copy()->subMonthNoOverflow()->format('Y-m');
                $mesSiguiente = $mesSeleccionado->copy()->addMonthNoOverflow()->format('Y-m');
                $inicioCalendario = $inicioMes->copy()->startOfWeek(\Carbon\Carbon::MONDAY);
                $finCalendario = $finMes->copy()->endOfWeek(\Carbon\Carbon::SUNDAY);
                $diasSemana = ['L', 'M', 'X', 'J', 'V', 'S', 'D'];
            @endphp

            <details class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <summary class="cursor-pointer flex items-center justify-between">
                    <span class="font-semibold text-base">
                        Fecha seleccionada: <span class="capitalize">{{ $diaSeleccionadoTexto }}</span>
                    </span>
                    <span class="text-sm text-blue-600 dark:text-blue-400">Abrir calendario</span>
                </summary>

                <div class="mt-4">
                    <div class="flex items-center justify-between mb-3">
                        <a href="{{ route('admin.ejecuciones.descarga-csv-tiendas', ['mes' => $mesAnterior]) }}"
                            class="px-2 py-1 text-sm rounded border border-gray-200 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700">
                            ← Mes anterior
                        </a>
                        <h2 class="font-semibold text-lg capitalize">{{ $mesTexto }}</h2>
                        <a href="{{ route('admin.ejecuciones.descarga-csv-tiendas', ['mes' => $mesSiguiente]) }}"
                            class="px-2 py-1 text-sm rounded border border-gray-200 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700">
                            Mes siguiente →
                        </a>
                    </div>

                    <div class="grid grid-cols-7 gap-2 text-center text-xs mb-2">
                        @foreach ($diasSemana as $d)
                            <div class="font-semibold text-gray-600 dark:text-gray-300">{{ $d }}</div>
                        @endforeach
                    </div>
                    <div class="grid grid-cols-7 gap-2">
                        @for ($dia = $inicioCalendario->copy(); $dia->lte($finCalendario); $dia->addDay())
                            @php
                                $fechaKey = $dia->toDateString();
                                $esMesActual = $dia->month === $mesSeleccionado->month;
                                $tieneEjecuciones = $esMesActual && isset($fechasConEjecuciones[$fechaKey]);
                                $esSeleccionado = $fechaKey === $fechaSeleccionada->toDateString();
                            @endphp

                            @if ($tieneEjecuciones)
                                <a href="{{ route('admin.ejecuciones.descarga-csv-tiendas', ['fecha' => $fechaKey, 'mes' => $mesSeleccionado->format('Y-m')]) }}"
                                    class="relative p-2 rounded border text-sm {{ $esSeleccionado ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' : 'border-gray-200 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700' }}">
                                    <span>{{ $dia->day }}</span>
                                    <span class="absolute top-1 right-1 inline-block w-2 h-2 rounded-full bg-green-500" title="Tiene ejecuciones"></span>
                                </a>
                            @else
                                <div class="p-2 rounded border text-sm border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-gray-700/40 text-gray-400 {{ !$esMesActual ? 'opacity-50' : '' }}">
                                    {{ $dia->day }}
                                </div>
                            @endif
                        @endfor
                    </div>
                    <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">Verde: día con ejecuciones. Gris: sin registros (no clicable).</p>
                </div>
            </details>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <h2 class="font-semibold text-lg mb-1">Ejecuciones del día seleccionado</h2>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-3 capitalize">{{ $diaSeleccionadoTexto }}</p>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 dark:border-gray-600">
                                <th class="text-left py-2 pr-4">ID</th>
                                <th class="text-left py-2 pr-4">Inicio</th>
                                <th class="text-left py-2 pr-4">Fin</th>
                                <th class="text-left py-2 pr-4">Tiendas</th>
                                <th class="text-left py-2 pr-4">Filas CSV</th>
                                <th class="text-left py-2 pr-4">Errores</th>
                                <th class="text-left py-2 pr-4">Estado</th>
                                <th class="text-left py-2 pr-4">Ver</th>
                                <th class="text-left py-2">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($ejecuciones as $e)
                                @php
                                    $logFila = is_array($e->log ?? null) ? $e->log : [];
                                    $estadoFila = $logFila['estado'] ?? null;
                                    $pasoActualFila = $logFila['paso_actual'] ?? null;
                                @endphp
                                <tr class="border-b border-gray-100 dark:border-gray-700 {{ (isset($ejecucion_id) && $e->id == $ejecucion_id) ? 'bg-blue-50 dark:bg-blue-900/20' : '' }}">
                                    <td class="py-2 pr-4">{{ $e->id }}</td>
                                    <td class="py-2 pr-4">{{ $e->inicio?->format('d/m/Y H:i') ?? '-' }}</td>
                                    <td class="py-2 pr-4">{{ $e->fin?->format('d/m/Y H:i') ?? '-' }}</td>
                                    <td class="py-2 pr-4">{{ $e->total }}</td>
                                    <td class="py-2 pr-4">{{ $e->total_guardado }}</td>
                                    <td class="py-2 pr-4">{{ $e->total_errores }}</td>
                                    <td class="py-2 pr-4 text-xs">
                                        @if ($e->fin === null)
                                            <span class="inline-block px-2 py-0.5 rounded bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200">En curso</span>
                                        @elseif ($estadoFila === 'ok')
                                            <span class="inline-block px-2 py-0.5 rounded bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-200">OK</span>
                                        @elseif ($estadoFila === 'cancelada')
                                            <span class="inline-block px-2 py-0.5 rounded bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300">Cancelada</span>
                                        @elseif ($estadoFila === 'error')
                                            <span class="inline-block px-2 py-0.5 rounded bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-200">Error</span>
                                        @else
                                            <span class="text-gray-500">{{ $estadoFila ?? '—' }}</span>
                                        @endif
                                        @if ($pasoActualFila)
                                            <span class="block mt-0.5 text-gray-500 dark:text-gray-400 truncate max-w-[10rem]" title="{{ $pasoActualFila }}">{{ $pasoActualFila }}</span>
                                        @endif
                                    </td>
                                    <td class="py-2 pr-4">
                                        <a href="{{ route('admin.ejecuciones.descarga-csv-tiendas', ['ejecucion_id' => $e->id, 'fecha' => $fechaSeleccionada->toDateString(), 'mes' => $mesSeleccionado->format('Y-m')]) }}" class="text-blue-600 dark:text-blue-400 hover:underline">Ver log</a>
                                    </td>
                                    <td class="py-2">
                                        @if ($e->fin === null)
                                            <form method="POST"
                                                action="{{ route('admin.ejecuciones.descarga-csv-tiendas.cancelar', $e) }}"
                                                class="inline"
                                                onsubmit="return confirm('¿Cerrar la ejecución #{{ $e->id }} en curso? Podrás lanzar de nuevo el cron, pero el proceso PHP en el servidor puede seguir activo un tiempo si quedó colgado.')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                    class="text-red-600 dark:text-red-400 hover:underline font-medium">
                                                    Cerrar ejecución
                                                </button>
                                            </form>
                                        @else
                                            <span class="text-gray-400">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="py-4 text-sm text-gray-500 dark:text-gray-400">
                                        No hay ejecuciones guardadas para este día.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if (isset($ejecucion))
                @php
                    $logEjecucion = is_array($ejecucion->log ?? null) ? $ejecucion->log : [];
                    $contadores = $logEjecucion['contadores'] ?? [];
                    $resultados = $logEjecucion['resultados'] ?? [];
                    $pasosEjecucion = is_array($logEjecucion['pasos'] ?? null) ? $logEjecucion['pasos'] : [];
                    $errorEjecucion = is_array($logEjecucion['error'] ?? null) ? $logEjecucion['error'] : null;
                    $resumen = $logEjecucion['resumen'] ?? null;
                    $estado = $logEjecucion['estado'] ?? null;
                    $pasoActual = $logEjecucion['paso_actual'] ?? null;

                    $etiquetasPaso = [
                        'inicio' => 'Inicio',
                        'limpieza_csv_antiguos' => 'Limpieza temporales',
                        'consulta_tiendas' => 'Consulta tiendas',
                        'procesando_tienda' => 'Inicio tienda',
                        'descarga_inicio' => 'Descarga .gz — inicio',
                        'descarga_fin' => 'Descarga .gz — fin',
                        'descompresion_inicio' => 'Descompresión — inicio',
                        'descompresion_fin' => 'Descompresión — fin',
                        'division_inicio' => 'División CSV — inicio',
                        'division_csv' => 'División CSV — resultado',
                        'parte_inicio' => 'Parte CSV — inicio',
                        'parte_guardado_progreso' => 'Guardado en BD — progreso',
                        'parte_fin' => 'Parte CSV — fin',
                        'url_completada' => 'URL completada',
                        'tienda_completada' => 'Tienda completada',
                        'tienda_resumen' => 'Resumen tienda',
                        'error_tienda' => 'Error en tienda',
                        'error' => 'Error',
                        'cancelada' => 'Cancelada',
                    ];

                    $resultadosPorTiendaId = [];
                    foreach ($resultados as $resultadoTienda) {
                        if (!empty($resultadoTienda['tienda_id'])) {
                            $resultadosPorTiendaId[$resultadoTienda['tienda_id']] = $resultadoTienda;
                        }
                    }

                    $pasosGenerales = [];
                    $pasosPorTienda = [];

                    foreach ($pasosEjecucion as $paso) {
                        $codigoPaso = $paso['paso'] ?? '';
                        $contextoPaso = is_array($paso['contexto'] ?? null) ? $paso['contexto'] : [];

                        if ($codigoPaso === 'parte_guardado_progreso') {
                            $claveTienda = isset($contextoPaso['tienda_id'])
                                ? 'tienda_' . $contextoPaso['tienda_id']
                                : '_sin_tienda';
                            if (!isset($pasosPorTienda[$claveTienda])) {
                                $pasosPorTienda[$claveTienda] = [
                                    'tienda_id' => $contextoPaso['tienda_id'] ?? null,
                                    'tienda' => $contextoPaso['tienda'] ?? 'Sin tienda',
                                    'pasos' => [],
                                    'progreso' => [],
                                ];
                            }
                            $pasosPorTienda[$claveTienda]['progreso'][] = $paso;
                            continue;
                        }

                        if (isset($contextoPaso['tienda_id']) || $codigoPaso === 'procesando_tienda') {
                            $claveTienda = 'tienda_' . ($contextoPaso['tienda_id'] ?? 'desconocida');
                            if (!isset($pasosPorTienda[$claveTienda])) {
                                $pasosPorTienda[$claveTienda] = [
                                    'tienda_id' => $contextoPaso['tienda_id'] ?? null,
                                    'tienda' => $contextoPaso['tienda'] ?? 'Tienda',
                                    'pasos' => [],
                                    'progreso' => [],
                                ];
                            }
                            if (!empty($contextoPaso['tienda'])) {
                                $pasosPorTienda[$claveTienda]['tienda'] = $contextoPaso['tienda'];
                            }
                            $pasosPorTienda[$claveTienda]['pasos'][] = $paso;
                            continue;
                        }

                        $pasosGenerales[] = $paso;
                    }

                    foreach ($resultadosPorTiendaId as $tiendaId => $resultadoTienda) {
                        $claveTienda = 'tienda_' . $tiendaId;
                        if (isset($pasosPorTienda[$claveTienda])) {
                            continue;
                        }
                        $pasosPorTienda[$claveTienda] = [
                            'tienda_id' => $tiendaId,
                            'tienda' => $resultadoTienda['tienda'] ?? ('Tienda ' . $tiendaId),
                            'pasos' => [],
                            'progreso' => [],
                        ];
                    }

                    $claveTiendaAbierta = null;
                    if ($ejecucion->fin === null) {
                        foreach ($pasosPorTienda as $clave => $grupo) {
                            $resultadoGrupo = $resultadosPorTiendaId[$grupo['tienda_id']] ?? null;
                            $completada = collect($grupo['pasos'])->contains(
                                fn ($p) => in_array($p['paso'] ?? '', ['tienda_completada', 'tienda_resumen'], true)
                            ) || ($resultadoGrupo !== null && empty($resultadoGrupo['omitida']) && empty($resultadoGrupo['errores']));
                            if (!$completada) {
                                $claveTiendaAbierta = $clave;
                                break;
                            }
                        }
                    }

                    $gruposLog = [];

                    if (!empty($pasosGenerales)) {
                        $gruposLog[] = [
                            'tipo' => 'general',
                            'titulo' => 'Preparación y cierre',
                            'tienda_id' => null,
                            'pasos' => $pasosGenerales,
                            'progreso' => [],
                            'abrir' => true,
                            'borde' => 'border-gray-200 dark:border-gray-600',
                            'completada' => false,
                            'con_error' => collect($pasosGenerales)->contains(
                                fn ($p) => in_array($p['paso'] ?? '', ['error', 'cancelada'], true)
                            ),
                            'ultimo_paso' => !empty($pasosGenerales)
                                ? ($pasosGenerales[array_key_last($pasosGenerales)]['paso'] ?? null)
                                : null,
                            'filas_guardadas' => null,
                        ];
                    }

                    foreach ($pasosPorTienda as $claveTienda => $grupoTienda) {
                        $pasosTienda = $grupoTienda['pasos'];
                        $progresoTienda = $grupoTienda['progreso'];
                        $resultadoTienda = $resultadosPorTiendaId[$grupoTienda['tienda_id']] ?? null;
                        $completada = collect($pasosTienda)->contains(
                            fn ($p) => in_array($p['paso'] ?? '', ['tienda_completada', 'tienda_resumen'], true)
                        ) || (
                            $resultadoTienda !== null
                            && empty($resultadoTienda['omitida'])
                            && empty($resultadoTienda['errores'])
                            && ($estado === 'ok' || $ejecucion->fin !== null)
                        );
                        $conError = collect($pasosTienda)->contains(
                            fn ($p) => ($p['paso'] ?? '') === 'error_tienda'
                        ) || ($resultadoTienda !== null && !empty($resultadoTienda['errores']));
                        $resumenPaso = collect($pasosTienda)->first(
                            fn ($p) => in_array($p['paso'] ?? '', ['tienda_completada', 'tienda_resumen'], true)
                        );
                        $ctxResumen = is_array($resumenPaso['contexto'] ?? null)
                            ? $resumenPaso['contexto']
                            : [];

                        $gruposLog[] = [
                            'tipo' => 'tienda',
                            'titulo' => $grupoTienda['tienda'],
                            'tienda_id' => $grupoTienda['tienda_id'],
                            'pasos' => $pasosTienda,
                            'progreso' => $progresoTienda,
                            'resultado' => $resultadoTienda,
                            'abrir' => $claveTiendaAbierta === $claveTienda,
                            'borde' => $conError
                                ? 'border-red-300 dark:border-red-700'
                                : ($completada ? 'border-green-300 dark:border-green-700' : 'border-amber-300 dark:border-amber-700'),
                            'completada' => $completada,
                            'con_error' => $conError,
                            'omitida' => !empty($resultadoTienda['omitida']),
                            'ultimo_paso' => !empty($pasosTienda)
                                ? ($pasosTienda[array_key_last($pasosTienda)]['paso'] ?? null)
                                : null,
                            'filas_guardadas' => $ctxResumen['filas_guardadas']
                                ?? ($resultadoTienda['filas_insertadas_o_actualizadas'] ?? null),
                            'filas_omitidas' => $ctxResumen['filas_omitidas']
                                ?? ($resultadoTienda['filas_omitidas'] ?? null),
                            'urls_procesadas' => $ctxResumen['urls_procesadas']
                                ?? ($resultadoTienda['urls_procesadas'] ?? null),
                        ];
                    }

                    $etiquetasContexto = [
                        'tienda_id' => 'ID tienda',
                        'tienda' => 'Tienda',
                        'url_indice' => 'Índice URL',
                        'url' => 'URL feed',
                        'tamano_gz' => 'Tamaño .gz (bytes)',
                        'tamano_gz_legible' => 'Tamaño .gz',
                        'tamano_csv' => 'Tamaño CSV (bytes)',
                        'tamano_csv_legible' => 'Tamaño CSV',
                        'dividido' => 'Dividido',
                        'total_partes' => 'Total partes',
                        'umbral_division_legible' => 'Umbral división',
                        'tamano_parte_objetivo_legible' => 'Tamaño objetivo por parte',
                        'parte' => 'Parte nº',
                        'archivo' => 'Archivo',
                        'tamano_parte' => 'Tamaño parte (bytes)',
                        'tamano_parte_legible' => 'Tamaño parte',
                        'lotes_guardados' => 'Lotes guardados',
                        'filas_guardadas' => 'Filas guardadas',
                        'filas_omitidas' => 'Filas omitidas',
                        'errores_lote' => 'Errores de lote',
                        'urls_procesadas' => 'URLs procesadas',
                        'errores' => 'Errores',
                        'error' => 'Mensaje error',
                        'tiendas_encontradas' => 'Tiendas encontradas',
                        'motivo' => 'Motivo',
                        'usuario_id' => 'Usuario',
                        'omitida' => 'Omitida',
                    ];
                @endphp

                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 border-l-4 border-blue-500">
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        <strong>Ejecución #{{ $ejecucion->id }}</strong>
                        — Inicio: {{ $ejecucion->inicio?->format('d/m/Y H:i') }},
                        Fin: {{ $ejecucion->fin?->format('d/m/Y H:i') }}
                        @if ($estado)
                            — Estado: <strong>{{ $estado }}</strong>
                        @endif
                    </p>
                    <p class="mt-2 text-sm">
                        Tiendas encontradas: <strong>{{ $ejecucion->total }}</strong> —
                        Filas guardadas en CSV: <strong>{{ $ejecucion->total_guardado }}</strong> —
                        Errores de tienda: <strong>{{ $ejecucion->total_errores }}</strong>
                    </p>
                    @if ($resumen)
                        <p class="mt-2 text-sm text-gray-700 dark:text-gray-300">{{ $resumen }}</p>
                    @endif
                    @if ($pasoActual && $ejecucion->fin === null)
                        <p class="mt-2 text-sm">
                            <span class="font-medium text-amber-700 dark:text-amber-300">Paso actual:</span>
                            <code class="text-xs bg-amber-50 dark:bg-amber-900/30 px-1.5 py-0.5 rounded">{{ $etiquetasPaso[$pasoActual] ?? $pasoActual }}</code>
                            <span class="text-gray-500">({{ $pasoActual }})</span>
                        </p>
                    @endif
                    <p class="mt-2 flex flex-wrap items-center gap-4">
                        <a href="{{ route('admin.ejecuciones.descarga-csv-tiendas', ['fecha' => $fechaSeleccionada->toDateString(), 'mes' => $mesSeleccionado->format('Y-m')]) }}" class="text-blue-600 dark:text-blue-400 hover:underline">Volver al listado</a>
                        @if ($ejecucion->fin === null)
                            <form method="POST"
                                action="{{ route('admin.ejecuciones.descarga-csv-tiendas.cancelar', $ejecucion) }}"
                                class="inline"
                                onsubmit="return confirm('¿Cerrar esta ejecución en curso?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 dark:text-red-400 hover:underline font-medium">
                                    Cerrar ejecución en curso
                                </button>
                            </form>
                        @endif
                    </p>
                </div>

                @if (!empty($errorEjecucion))
                    <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4 text-sm text-red-800 dark:text-red-200">
                        <p><strong>Error:</strong> {{ $errorEjecucion['mensaje'] ?? '-' }}</p>
                        @if (!empty($errorEjecucion['tipo']))
                            <p class="text-xs mt-1">{{ $errorEjecucion['tipo'] }}</p>
                        @endif
                    </div>
                @endif

                @if (!empty($pasosEjecucion))
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 space-y-3">
                        <h3 class="font-semibold text-base">Log de ejecución ({{ count($pasosEjecucion) }} pasos)</h3>

                        @foreach ($gruposLog as $grupo)
                            <details class="border rounded-lg {{ $grupo['borde'] }}" @if ($grupo['abrir']) open @endif>
                                <summary class="cursor-pointer px-4 py-3 font-medium text-sm text-gray-800 dark:text-gray-200">
                                    <span class="inline-flex flex-wrap items-center gap-2">
                                        <span>{{ $grupo['titulo'] }}</span>
                                        @if ($grupo['tipo'] === 'tienda' && !empty($grupo['tienda_id']))
                                            <span class="text-xs font-normal text-gray-500">(id {{ $grupo['tienda_id'] }})</span>
                                        @endif
                                        @if ($grupo['tipo'] === 'tienda')
                                            @if (!empty($grupo['omitida']))
                                                <span class="text-xs px-1.5 py-0.5 rounded bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300">Omitida</span>
                                            @elseif ($grupo['completada'])
                                                <span class="text-xs px-1.5 py-0.5 rounded bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-200">Completada</span>
                                            @elseif ($grupo['con_error'])
                                                <span class="text-xs px-1.5 py-0.5 rounded bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-200">Con error</span>
                                            @else
                                                <span class="text-xs px-1.5 py-0.5 rounded bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200">En curso</span>
                                            @endif
                                        @endif
                                    </span>
                                    <span class="block mt-1 text-xs font-normal text-gray-500">
                                        {{ count($grupo['pasos']) }} pasos
                                        @if (count($grupo['progreso']) > 0)
                                            · {{ count($grupo['progreso']) }} de progreso
                                        @endif
                                        @if ($grupo['ultimo_paso'])
                                            · Último: {{ $etiquetasPaso[$grupo['ultimo_paso']] ?? $grupo['ultimo_paso'] }}
                                        @endif
                                        @if (!empty($grupo['urls_procesadas']))
                                            · {{ $grupo['urls_procesadas'] }} URL(s)
                                        @endif
                                        @if ($grupo['filas_guardadas'] !== null && $grupo['filas_guardadas'] !== '')
                                            · {{ number_format((int) $grupo['filas_guardadas'], 0, ',', '.') }} filas guardadas
                                        @endif
                                        @if (!empty($grupo['filas_omitidas']))
                                            · {{ number_format((int) $grupo['filas_omitidas'], 0, ',', '.') }} omitidas
                                        @endif
                                    </span>
                                </summary>
                                <div class="px-4 pb-4">
                                    @if ($grupo['tipo'] === 'tienda' && !empty($grupo['resultado']))
                                        @php $res = $grupo['resultado']; @endphp
                                        <div class="mb-4 p-3 rounded-lg text-sm {{ !empty($res['errores']) ? 'bg-red-50 dark:bg-red-900/20' : 'bg-gray-50 dark:bg-gray-700/40' }}">
                                            @if (!empty($res['omitida']))
                                                <p class="text-amber-700 dark:text-amber-300">Omitida: {{ $res['motivo'] ?? 'Sin motivo' }}</p>
                                            @else
                                                <dl class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                                                    <div>
                                                        <dt class="text-xs text-gray-500">URLs procesadas</dt>
                                                        <dd class="font-semibold">{{ $res['urls_procesadas'] ?? 0 }}</dd>
                                                    </div>
                                                    <div>
                                                        <dt class="text-xs text-gray-500">Filas guardadas</dt>
                                                        <dd class="font-semibold">{{ number_format((int) ($res['filas_insertadas_o_actualizadas'] ?? 0), 0, ',', '.') }}</dd>
                                                    </div>
                                                    <div>
                                                        <dt class="text-xs text-gray-500">Filas omitidas</dt>
                                                        <dd class="font-semibold">{{ number_format((int) ($res['filas_omitidas'] ?? 0), 0, ',', '.') }}</dd>
                                                    </div>
                                                </dl>
                                            @endif
                                            @if (!empty($res['errores']) && is_array($res['errores']))
                                                <ul class="mt-2 list-disc list-inside text-red-700 dark:text-red-300 text-xs space-y-1">
                                                    @foreach ($res['errores'] as $errorTienda)
                                                        <li>{{ $errorTienda }}</li>
                                                    @endforeach
                                                </ul>
                                            @endif
                                        </div>
                                    @endif
                                    <div class="overflow-x-auto">
                                        <table class="min-w-full text-sm border border-gray-200 dark:border-gray-600 rounded-lg overflow-hidden">
                                            <thead class="bg-gray-50 dark:bg-gray-700/50">
                                                <tr>
                                                    <th class="text-left py-2 px-3 font-medium w-36">Momento</th>
                                                    <th class="text-left py-2 px-3 font-medium w-44">Paso</th>
                                                    <th class="text-left py-2 px-3 font-medium">Detalle</th>
                                                    <th class="text-left py-2 px-3 font-medium">Datos</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse ($grupo['pasos'] as $paso)
                                                    @php
                                                        $codigoPaso = $paso['paso'] ?? '-';
                                                        $contexto = is_array($paso['contexto'] ?? null) ? $paso['contexto'] : [];
                                                        $esError = str_contains($codigoPaso, 'error') || $codigoPaso === 'cancelada';
                                                        $filaClass = $esError
                                                            ? 'bg-red-50/50 dark:bg-red-900/10'
                                                            : (str_starts_with($codigoPaso, 'parte_') ? 'bg-green-50/30 dark:bg-green-900/10' : '');
                                                    @endphp
                                                    <tr class="border-t border-gray-100 dark:border-gray-700 {{ $filaClass }}">
                                                        <td class="py-2 px-3 align-top text-xs text-gray-500 whitespace-nowrap">{{ $paso['momento'] ?? '-' }}</td>
                                                        <td class="py-2 px-3 align-top">
                                                            <span class="font-medium text-gray-900 dark:text-gray-100">{{ $etiquetasPaso[$codigoPaso] ?? $codigoPaso }}</span>
                                                            <span class="block text-xs text-gray-400">{{ $codigoPaso }}</span>
                                                        </td>
                                                        <td class="py-2 px-3 align-top text-gray-700 dark:text-gray-300">{{ $paso['detalle'] ?? '—' }}</td>
                                                        <td class="py-2 px-3 align-top">
                                                            @php
                                                                $tieneDatosVisibles = false;
                                                                foreach ($contexto as $clave => $valor) {
                                                                    if ($clave === 'partes' || is_array($valor)) {
                                                                        continue;
                                                                    }
                                                                    if ($grupo['tipo'] === 'tienda' && in_array($clave, ['tienda_id', 'tienda'], true)) {
                                                                        continue;
                                                                    }
                                                                    if ($valor !== null && $valor !== '') {
                                                                        $tieneDatosVisibles = true;
                                                                        break;
                                                                    }
                                                                }
                                                            @endphp
                                                            @if (!$tieneDatosVisibles && !empty($contexto['partes']))
                                                                @php $tieneDatosVisibles = true; @endphp
                                                            @endif
                                                            @if (!$tieneDatosVisibles && $grupo['tipo'] === 'tienda' && $codigoPaso === 'procesando_tienda')
                                                                <p class="text-xs text-gray-500">Inicio del procesamiento. Ver resumen arriba si la ejecución ya terminó.</p>
                                                            @endif
                                                            @if (!empty($contexto['partes']) && is_array($contexto['partes']))
                                                                <p class="text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Partes generadas:</p>
                                                                <ul class="text-xs space-y-1 mb-2">
                                                                    @foreach ($contexto['partes'] as $infoParte)
                                                                        <li>
                                                                            Parte {{ $infoParte['parte'] ?? '?' }}:
                                                                            <strong>{{ $infoParte['legible'] ?? ($infoParte['bytes'] ?? '?') }}</strong>
                                                                            <span class="text-gray-400">({{ $infoParte['archivo'] ?? '' }})</span>
                                                                        </li>
                                                                    @endforeach
                                                                </ul>
                                                            @endif
                                                            <dl class="grid grid-cols-1 gap-x-4 gap-y-1 text-xs">
                                                                @foreach ($contexto as $clave => $valor)
                                                                    @if ($clave === 'partes' || is_array($valor))
                                                                        @continue
                                                                    @endif
                                                                    @if ($grupo['tipo'] === 'tienda' && in_array($clave, ['tienda_id', 'tienda'], true))
                                                                        @continue
                                                                    @endif
                                                                    @if ($valor === null || $valor === '')
                                                                        @continue
                                                                    @endif
                                                                    <div class="flex gap-2">
                                                                        <dt class="text-gray-500 dark:text-gray-400 shrink-0">{{ $etiquetasContexto[$clave] ?? str_replace('_', ' ', $clave) }}:</dt>
                                                                        <dd class="text-gray-800 dark:text-gray-200 break-all">
                                                                            @if ($clave === 'url')
                                                                                <span title="{{ $valor }}">{{ \Illuminate\Support\Str::limit($valor, 80) }}</span>
                                                                            @elseif (is_bool($valor))
                                                                                {{ $valor ? 'sí' : 'no' }}
                                                                            @else
                                                                                {{ $valor }}
                                                                            @endif
                                                                        </dd>
                                                                    </div>
                                                                @endforeach
                                                            </dl>
                                                        </td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="4" class="py-3 px-3 text-sm text-gray-500">Sin pasos registrados.</td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>

                                    @if (!empty($grupo['progreso']))
                                        <details class="mt-3 border border-gray-200 dark:border-gray-600 rounded-lg p-3">
                                            <summary class="cursor-pointer font-medium text-sm text-blue-600 dark:text-blue-400">
                                                Progreso al guardar en BD ({{ count($grupo['progreso']) }} registros)
                                            </summary>
                                            <div class="mt-3 overflow-x-auto max-h-96 overflow-y-auto">
                                                <table class="min-w-full text-xs">
                                                    <thead>
                                                        <tr class="border-b border-gray-200 dark:border-gray-600">
                                                            <th class="text-left py-1 pr-3">Momento</th>
                                                            <th class="text-left py-1 pr-3">Parte</th>
                                                            <th class="text-left py-1 pr-3">Archivo</th>
                                                            <th class="text-right py-1 pr-3">Lotes</th>
                                                            <th class="text-right py-1 pr-3">Filas guardadas</th>
                                                            <th class="text-right py-1">Omitidas</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach ($grupo['progreso'] as $paso)
                                                            @php $ctx = is_array($paso['contexto'] ?? null) ? $paso['contexto'] : []; @endphp
                                                            <tr class="border-b border-gray-100 dark:border-gray-700">
                                                                <td class="py-1 pr-3 text-gray-500 whitespace-nowrap">{{ $paso['momento'] ?? '-' }}</td>
                                                                <td class="py-1 pr-3">{{ ($ctx['parte'] ?? '?') }}/{{ ($ctx['total_partes'] ?? '?') }}</td>
                                                                <td class="py-1 pr-3">{{ $ctx['archivo'] ?? '—' }}</td>
                                                                <td class="py-1 pr-3 text-right">{{ $ctx['lotes_guardados'] ?? '—' }}</td>
                                                                <td class="py-1 pr-3 text-right font-medium">{{ number_format((int) ($ctx['filas_guardadas'] ?? 0), 0, ',', '.') }}</td>
                                                                <td class="py-1 text-right">{{ number_format((int) ($ctx['filas_omitidas'] ?? 0), 0, ',', '.') }}</td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        </details>
                                    @endif
                                </div>
                            </details>
                        @endforeach
                    </div>
                @endif

                @if (!empty($contadores))
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                        <h3 class="font-semibold text-base mb-2">Contadores</h3>
                        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                            @foreach ($contadores as $clave => $valor)
                                <div class="flex justify-between gap-4 border-b border-gray-100 dark:border-gray-700 pb-2">
                                    <dt class="text-gray-600 dark:text-gray-400">{{ str_replace('_', ' ', $clave) }}</dt>
                                    <dd class="font-medium">{{ $valor }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    </div>
                @endif

            @endif

        </div>
    </div>
</x-app-layout>
