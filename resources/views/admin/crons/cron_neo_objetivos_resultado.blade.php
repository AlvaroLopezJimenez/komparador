<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.dashboard') }}">
                <h2 class="font-semibold text-xl text-white leading-tight">Inicio -></h2>
            </a>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Cron Neo Objetivos - Resultado
            </h2>
        </div>
    </x-slot>

    <div class="py-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="max-w-5xl mx-auto space-y-6">
        @php
            $fechaSeleccionada = isset($fechaSeleccionada) ? $fechaSeleccionada->copy()->startOfDay() : \Carbon\Carbon::today()->startOfDay();
            $mesSeleccionado = isset($mesSeleccionado) ? $mesSeleccionado->copy()->startOfMonth() : $fechaSeleccionada->copy()->startOfMonth();
            $inicioMes = isset($inicioMes) ? $inicioMes->copy()->startOfMonth() : $mesSeleccionado->copy()->startOfMonth();
            $finMes = isset($finMes) ? $finMes->copy()->endOfMonth() : $mesSeleccionado->copy()->endOfMonth();
            $fechasConEjecuciones = is_array($fechasConEjecuciones ?? null) ? $fechasConEjecuciones : [];
            $diaSeleccionadoTexto = $fechaSeleccionada->locale('es')->translatedFormat('d \\d\\e F \\d\\e Y');
            $mesTexto = $mesSeleccionado->locale('es')->translatedFormat('F Y');
            $mesAnterior = $mesSeleccionado->copy()->subMonthNoOverflow()->format('Y-m');
            $mesSiguiente = $mesSeleccionado->copy()->addMonthNoOverflow()->format('Y-m');
            $inicioCalendario = $inicioMes->copy()->startOfWeek(\Carbon\Carbon::MONDAY);
            $finCalendario = $finMes->copy()->endOfWeek(\Carbon\Carbon::SUNDAY);
            $diasSemana = ['L', 'M', 'X', 'J', 'V', 'S', 'D'];

            /*
             * Datos mostrados en esta vista: si hay una ejecución cargada, el log guardado en BD
             * es la fuente de verdad (incluye neo_backfill_tienda_por_url y futuras claves).
             * Si no hay ejecución seleccionada, se usan los valores que ya pasó la ruta (típicamente vacíos).
             */
            $logNeoObjetivos = (!empty($ejecucion) && is_array($ejecucion->log ?? null)) ? $ejecucion->log : [];
            $total_filas_neo = (int) ($logNeoObjetivos['total_filas_neo'] ?? $total_filas_neo ?? 0);
            $resultados = $logNeoObjetivos['resultados'] ?? $resultados ?? [];
            $total_filas_categoria_tienda = (int) ($logNeoObjetivos['total_filas_categoria_tienda'] ?? $total_filas_categoria_tienda ?? 0);
            $filas_sin_tienda_aviso = (int) ($logNeoObjetivos['filas_sin_tienda_aviso'] ?? $filas_sin_tienda_aviso ?? 0);
            $resultados_categoria = $logNeoObjetivos['resultados_categoria'] ?? $resultados_categoria ?? [];
            $resultados_categoria_tienda_detalle = $logNeoObjetivos['resultados_categoria_tienda_detalle'] ?? $resultados_categoria_tienda_detalle ?? [];
            $resultados_categoria_tienda = $logNeoObjetivos['resultados_categoria_tienda'] ?? $resultados_categoria_tienda ?? [];
            $neo_backfill_tienda_por_url = $logNeoObjetivos['neo_backfill_tienda_por_url'] ?? $neo_backfill_tienda_por_url ?? null;
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
                    <a href="{{ route('admin.ejecuciones.neo-objetivos', ['mes' => $mesAnterior]) }}"
                        class="px-2 py-1 text-sm rounded border border-gray-200 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700">
                        ← Mes anterior
                    </a>
                    <h2 class="font-semibold text-lg capitalize">{{ $mesTexto }}</h2>
                    <a href="{{ route('admin.ejecuciones.neo-objetivos', ['mes' => $mesSiguiente]) }}"
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
                            <a href="{{ route('admin.ejecuciones.neo-objetivos', ['fecha' => $fechaKey, 'mes' => $mesSeleccionado->format('Y-m')]) }}"
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
                            <th class="text-left py-2 pr-4">Total</th>
                            <th class="text-left py-2 pr-4">Guardado</th>
                            <th class="text-left py-2 pr-4">Errores</th>
                            <th class="text-left py-2">Ver</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($ejecuciones as $e)
                            <tr class="border-b border-gray-100 dark:border-gray-700 {{ (isset($ejecucion_id) && $e->id == $ejecucion_id) ? 'bg-blue-50 dark:bg-blue-900/20' : '' }}">
                                <td class="py-2 pr-4">{{ $e->id }}</td>
                                <td class="py-2 pr-4">{{ $e->inicio?->format('d/m/Y H:i') ?? '-' }}</td>
                                <td class="py-2 pr-4">{{ $e->fin?->format('d/m/Y H:i') ?? '-' }}</td>
                                <td class="py-2 pr-4">{{ $e->total }}</td>
                                <td class="py-2 pr-4">{{ $e->total_guardado }}</td>
                                <td class="py-2 pr-4">{{ $e->total_errores }}</td>
                                <td class="py-2">
                                    <a href="{{ route('admin.ejecuciones.neo-objetivos', ['ejecucion_id' => $e->id, 'fecha' => $fechaSeleccionada->toDateString(), 'mes' => $mesSeleccionado->format('Y-m')]) }}" class="text-blue-600 dark:text-blue-400 hover:underline">Ver log</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-4 text-sm text-gray-500 dark:text-gray-400">
                                    No hay ejecuciones guardadas para este día.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if (!empty($ejecucion))
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 border-l-4 border-blue-500">
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    <strong>Viendo ejecución #{{ $ejecucion->id }}</strong>
                    — Inicio: {{ $ejecucion->inicio?->format('d/m/Y H:i') }},
                    Fin: {{ $ejecucion->fin?->format('d/m/Y H:i') }},
                    Total: {{ $ejecucion->total }},
                    Guardado: {{ $ejecucion->total_guardado }},
                    Errores: {{ $ejecucion->total_errores }}
                </p>
                <p class="mt-2">
                    <a href="{{ route('admin.ejecuciones.neo-objetivos', ['fecha' => $fechaSeleccionada->toDateString(), 'mes' => $mesSeleccionado->format('Y-m')]) }}" class="text-blue-600 dark:text-blue-400 hover:underline">Volver al listado de ejecuciones</a>
                </p>
                @if (is_array($neo_backfill_tienda_por_url ?? null))
                    @php $nbHdr = $neo_backfill_tienda_por_url; @endphp
                    <p class="mt-3 text-sm text-gray-700 dark:text-gray-300 border-t border-blue-200 dark:border-blue-800 pt-3">
                        <span class="font-medium text-gray-800 dark:text-gray-200">Backfill tienda en neo (al final de cada ejecución del cron):</span>
                        revisadas <strong>{{ (int) ($nbHdr['revisadas'] ?? 0) }}</strong>,
                        tienda asignada <strong>{{ (int) ($nbHdr['actualizadas'] ?? 0) }}</strong>,
                        sin host <strong>{{ (int) ($nbHdr['sin_tienda_detectada'] ?? 0) }}</strong>,
                        URL vacía al descifrar <strong>{{ (int) ($nbHdr['url_descifrada_vacia'] ?? 0) }}</strong>,
                        errores <strong>{{ (int) ($nbHdr['errores'] ?? 0) }}</strong>
                    </p>
                @endif
            </div>

            @php
                $logEjecucion = $logNeoObjetivos;
                $estadoEjecucion = $logEjecucion['estado'] ?? null;
                $pasoActual = $logEjecucion['paso_actual'] ?? null;
                $pasosEjecucion = is_array($logEjecucion['pasos'] ?? null) ? $logEjecucion['pasos'] : [];
                $errorEjecucion = is_array($logEjecucion['error'] ?? null) ? $logEjecucion['error'] : null;
            @endphp

            @if ($estadoEjecucion || $pasoActual || !empty($pasosEjecucion) || $errorEjecucion)
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 border-l-4 {{ $estadoEjecucion === 'error' ? 'border-red-500' : ($estadoEjecucion === 'ok' ? 'border-green-500' : 'border-amber-500') }}">
                    <h3 class="font-semibold text-base mb-2">Seguimiento de la ejecución</h3>
                    <p class="text-sm text-gray-700 dark:text-gray-300">
                        <span class="text-gray-500">Estado:</span>
                        <strong>{{ $estadoEjecucion ?? 'desconocido' }}</strong>
                        @if ($pasoActual)
                            — <span class="text-gray-500">Paso actual:</span> <strong>{{ $pasoActual }}</strong>
                        @endif
                    </p>

                    @if (!empty($errorEjecucion))
                        <div class="mt-2 text-sm text-red-600 dark:text-red-400">
                            <p><span class="text-gray-500">Tipo:</span> {{ $errorEjecucion['tipo'] ?? '-' }}</p>
                            <p><span class="text-gray-500">Mensaje:</span> {{ $errorEjecucion['mensaje'] ?? '-' }}</p>
                        </div>
                    @endif

                    @if (!empty($pasosEjecucion))
                        <details class="mt-3">
                            <summary class="cursor-pointer text-sm text-blue-600 dark:text-blue-400 hover:underline">
                                Ver pasos registrados ({{ count($pasosEjecucion) }})
                            </summary>
                            <div class="mt-2 max-h-80 overflow-y-auto">
                                <ol class="list-decimal list-inside space-y-2 text-sm">
                                    @foreach ($pasosEjecucion as $paso)
                                        <li class="break-words">
                                            <span class="font-medium">{{ $paso['paso'] ?? '-' }}</span>
                                            @if (!empty($paso['momento']))
                                                <span class="text-xs text-gray-500">({{ $paso['momento'] }})</span>
                                            @endif
                                            @if (!empty($paso['detalle']))
                                                <span class="block text-gray-700 dark:text-gray-300">{{ $paso['detalle'] }}</span>
                                            @endif
                                            @if (!empty($paso['contexto']) && is_array($paso['contexto']))
                                                <pre class="mt-1 p-2 bg-gray-100 dark:bg-gray-700 rounded text-xs overflow-x-auto">{{ json_encode($paso['contexto'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</pre>
                                            @endif
                                        </li>
                                    @endforeach
                                </ol>
                            </div>
                        </details>
                    @endif
                </div>
            @endif
        @endif

        <p class="text-gray-600 dark:text-gray-400">
            Filas con visitada &gt; 7 días y URL de rama «neo»: <strong>{{ $total_filas_neo }}</strong>
        </p>

        {{-- Rama categoria_tienda --}}
        @if (isset($total_filas_categoria_tienda) && (int) $total_filas_categoria_tienda > 0)
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 border-l-4 border-amber-500">
                <h2 class="font-semibold text-lg mb-2">Rama categoria_tienda</h2>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Filas con visitada &gt; 7 días y URL de rama <strong>categoria_tienda</strong>: <strong>{{ $total_filas_categoria_tienda }}</strong>
                    @if (isset($filas_sin_tienda_aviso) && (int) $filas_sin_tienda_aviso > 0)
                        — Sin tienda detectada (aviso creado): <strong>{{ $filas_sin_tienda_aviso }}</strong>
                    @endif
                </p>

                {{-- Detalle por fila (hasta dónde llegó cada una) --}}
                @if (!empty($resultados_categoria_tienda_detalle))
                    <h3 class="font-semibold text-base mt-4 mb-2">Detalle por fila (categoria_tienda)</h3>
                    <div class="space-y-3">
                        @foreach ($resultados_categoria_tienda_detalle as $idx => $rd)
                            @php
                                $paso = $rd['paso'] ?? '';
                                $borderClass = match($paso) {
                                    'sin_tienda' => 'border-red-300 dark:border-red-700 bg-red-50 dark:bg-red-900/20',
                                    'controlador_no_encontrado' => 'border-amber-300 dark:border-amber-700 bg-amber-50 dark:bg-amber-900/20',
                                    'sin_tipo_listado' => 'border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50',
                                    'ok' => 'border-green-300 dark:border-green-700 bg-green-50 dark:bg-green-900/20',
                                    default => 'border-gray-200 dark:border-gray-600',
                                };
                            @endphp
                            <div class="rounded-lg border p-3 text-sm {{ $borderClass }}">
                                <p class="font-medium mb-1">Fila {{ $idx + 1 }} — Neoobjetivo ID {{ $rd['neoobjetivo_id'] ?? '-' }}</p>
                                <p class="mb-1"><span class="text-gray-500">URL:</span> <a href="{{ $rd['url'] ?? '#' }}" target="_blank" rel="noopener" class="text-blue-600 dark:text-blue-400 break-all hover:underline">{{ $rd['url'] ?? '-' }}</a></p>
                                <p class="mb-1"><span class="text-gray-500">Paso:</span> <strong>{{ $paso }}</strong></p>
                                <p class="text-gray-700 dark:text-gray-300">{{ $rd['mensaje'] ?? '-' }}</p>
                                @if (!empty($rd['tienda_nombre']))
                                    <p class="mt-1 text-gray-600 dark:text-gray-400"><span class="text-gray-500">Tienda:</span> {{ $rd['tienda_nombre'] }}</p>
                                @endif
                                @if (!empty($rd['tipo_listado']))
                                    <p class="text-gray-600 dark:text-gray-400"><span class="text-gray-500">Tipo listado:</span> {{ $rd['tipo_listado'] }}</p>
                                @endif
                                @if (!empty($rd['clase_buscada']))
                                    <p class="text-gray-600 dark:text-gray-400 text-xs"><span class="text-gray-500">Clase buscada:</span> {{ $rd['clase_buscada'] }}</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif

                {{-- Peticiones categoria_tienda: petición(es) al sitemap/HTML y URLs de productos extraídas --}}
                @if (!empty($resultados_categoria_tienda))
                    <h3 class="font-semibold text-base mt-4 mb-2">Peticiones categoria_tienda (categorías)</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">Se solicitó el contenido de la URL de categoría/sitemap y se extrajeron las URLs de productos con el método de la tienda.</p>
                    <div class="rounded-lg border border-amber-200 dark:border-amber-800 bg-amber-50/40 dark:bg-amber-900/10 p-3 mb-4">
                        <h4 class="font-semibold text-sm mb-2">Resumen por URL</h4>
                        <div class="space-y-2 text-sm">
                            @foreach ($resultados_categoria_tienda as $idxResumen => $rResumen)
                                @php
                                    $redsResumen = is_array($rResumen['redirecciones'] ?? null) ? $rResumen['redirecciones'] : [];
                                    $nGuardadosResumen = count(array_filter($redsResumen, fn($red) => empty($red['skipped']) && empty($red['error'])));
                                    $nOmitidosResumen = count(array_filter($redsResumen, fn($red) => !empty($red['skipped'])));
                                    $nErroresResumen = count(array_filter($redsResumen, fn($red) => !empty($red['error'])));
                                @endphp
                                <div>
                                    <p class="font-medium">URL {{ $idxResumen + 1 }}:</p>
                                    <p class="text-xs break-all text-gray-500 dark:text-gray-400">
                                        {{ $rResumen['url'] ?? '-' }}
                                    </p>
                                    <p class="text-gray-700 dark:text-gray-300">
                                        Guardados: <strong>{{ $nGuardadosResumen }}</strong>
                                        — Omitidos (ya en neo): <strong>{{ $nOmitidosResumen }}</strong>
                                        — Errores: <strong>{{ $nErroresResumen }}</strong>
                                    </p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="space-y-6">
                        @foreach ($resultados_categoria_tienda as $idxNoI => $rno)
                            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 border border-amber-200 dark:border-amber-800 space-y-4">
                                <h2 class="font-semibold text-lg">Neoobjetivo {{ $rno['neoobjetivo_id'] ?? $idxNoI + 1 }} — {{ $rno['tienda_nombre'] ?? '-' }} ({{ $rno['tipo_listado'] ?? '-' }})</h2>
                                <p class="text-sm"><span class="text-gray-500">URL categoría/sitemap:</span> <a href="{{ $rno['url'] ?? '#' }}" target="_blank" rel="noopener" class="text-blue-600 dark:text-blue-400 break-all hover:underline">{{ $rno['url'] ?? '-' }}</a></p>
                                <p class="text-sm"><span class="text-gray-500">Total URLs de productos extraídas:</span> <strong>{{ $rno['urls_extraidas_total'] ?? 0 }}</strong></p>
                                @foreach ($rno['peticiones'] ?? [] as $idxPet => $pet)
                                    <div class="rounded-lg border border-gray-200 dark:border-gray-600 p-3 bg-gray-50 dark:bg-gray-700/50 space-y-2">
                                        <h4 class="font-semibold text-sm">Petición {{ $idxPet + 1 }}@if(!empty($pet['tipo'])) <span class="text-gray-500 font-normal">({{ $pet['tipo'] }})</span>@endif</h4>
                                        <p class="text-sm"><span class="text-gray-500">API utilizada:</span> <strong>{{ $pet['api_utilizada'] ?? '—' }}</strong></p>
                                        <p class="text-sm"><span class="text-gray-500">URL solicitada:</span> <a href="{{ $pet['url_peticion'] ?? '#' }}" target="_blank" rel="noopener" class="text-blue-600 dark:text-blue-400 break-all hover:underline">{{ $pet['url_peticion'] ?? '-' }}</a></p>
                                        <p class="text-sm"><span class="text-gray-500">HTTP status:</span> {{ $pet['http_status'] ?? '-' }}</p>
                                        @if (!empty($pet['error']))
                                            <p class="text-sm text-red-600 dark:text-red-400"><span class="text-gray-500">Error:</span> {{ $pet['error'] }}</p>
                                        @endif
                                        @if (isset($pet['siguiente_url']) && $pet['siguiente_url'] !== null && $pet['siguiente_url'] !== '')
                                            <p class="text-sm text-gray-600 dark:text-gray-400"><span class="text-gray-500">Siguiente página:</span> <a href="{{ $pet['siguiente_url'] }}" target="_blank" rel="noopener" class="text-blue-600 dark:text-blue-400 break-all hover:underline">{{ Str::limit($pet['siguiente_url'], 80) }}</a></p>
                                        @endif
                                        @if (!empty($pet['urls_extraidas']))
                                            <div class="mt-2">
                                                <span class="text-gray-500 text-sm block mb-1">URLs de productos extraídas ({{ count($pet['urls_extraidas']) }}):</span>
                                                <ul class="list-disc list-inside space-y-1 text-sm max-h-60 overflow-y-auto">
                                                    @foreach ($pet['urls_extraidas'] as $u)
                                                        <li class="break-all">
                                                            <a href="{{ $u }}" target="_blank" rel="noopener" class="text-blue-600 dark:text-blue-400 hover:underline">{{ Str::limit($u, 80) }}</a>
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                        @elseif (isset($pet['urls_extraidas']) && empty($pet['error']))
                                            <p class="text-sm text-gray-500">No se encontraron URLs de productos en esta petición.</p>
                                        @endif
                                    </div>
                                @endforeach
                                {{-- Guardado en neo (categoría): mismo flujo de guardado por categoría --}}
                                @if (!empty($rno['redirecciones']))
                                    @php
                                        $reds = $rno['redirecciones'];
                                        $nGuardados = count(array_filter($reds, fn($red) => empty($red['skipped']) && empty($red['error'])));
                                        $nOmitidos = count(array_filter($reds, fn($red) => !empty($red['skipped'])));
                                        $nErrores = count(array_filter($reds, fn($red) => !empty($red['error'])));
                                    @endphp
                                    <div class="mt-4 pt-4 border-t border-amber-200 dark:border-amber-800">
                                        <h3 class="font-semibold text-sm mb-2">Guardado en neo (categoría)</h3>
                                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">Guardados: <strong>{{ $nGuardados }}</strong> — Omitidos (ya en neo): <strong>{{ $nOmitidos }}</strong> — Errores: <strong>{{ $nErrores }}</strong></p>
                                        <details class="mt-2">
                                            <summary class="cursor-pointer text-sm text-amber-700 dark:text-amber-300 hover:underline">Ver detalle por URL ({{ count($reds) }})</summary>
                                            <div class="mt-3 space-y-3 max-h-96 overflow-y-auto">
                                                @foreach ($reds as $redIdx => $red)
                                                    <div class="p-3 rounded-lg border text-sm {{ !empty($red['skipped']) ? 'bg-amber-50 dark:bg-amber-900/20 border-amber-200 dark:border-amber-800' : (!empty($red['success']) ? 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800' : 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800') }}">
                                                        <h4 class="font-semibold mb-1">URL {{ $redIdx + 1 }}</h4>
                                                        @if (!empty($red['skipped']))
                                                            <p class="text-amber-700 dark:text-amber-300">{{ $red['reason'] ?? 'Omitido' }}</p>
                                                            @if (!empty($red['url_final'])) <p class="text-xs text-gray-500 mt-1 break-all">URL final: {{ $red['url_final'] }}</p> @endif
                                                        @else
                                                            @if (!empty($red['url_final']))
                                                                <p class="mb-1 break-all"><a href="{{ $red['url_final'] }}" target="_blank" rel="noopener" class="text-blue-600 dark:text-blue-400 hover:underline">{{ Str::limit($red['url_final'], 80) }}</a></p>
                                                            @endif
                                                            <p class="text-gray-700 dark:text-gray-300">{{ $red['accion_final'] ?? '' }}</p>
                                                            @if (!empty($red['error']))
                                                                <p class="text-red-600 dark:text-red-400 text-xs mt-1">{{ $red['error'] }}</p>
                                                            @endif
                                                        @endif
                                                        @if (!empty($red['log_pasos']))
                                                            <details class="mt-2 text-xs">
                                                                <summary class="cursor-pointer text-gray-500">Pasos</summary>
                                                                <ol class="list-decimal list-inside mt-1 space-y-0.5 text-gray-600 dark:text-gray-400">
                                                                    @foreach ($red['log_pasos'] as $paso)
                                                                        <li>
                                                                            @if (!empty($paso['valor']))
                                                                                {{ $paso['texto'] ?? '' }}: <span class="break-all">{{ Str::limit($paso['valor'], 60) }}</span>
                                                                            @elseif (!empty($paso['decision']))
                                                                                {{ $paso['texto'] ?? '' }} — {{ $paso['decision'] }}
                                                                            @else
                                                                                {{ $paso['texto'] ?? '' }}
                                                                            @endif
                                                                        </li>
                                                                    @endforeach
                                                                </ol>
                                                            </details>
                                                        @endif
                                                    </div>
                                                @endforeach
                                            </div>
                                        </details>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif

                @if (!empty($resultados_categoria))
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">Filas con tienda y tipo de listado listas para procesar: <strong>{{ count($resultados_categoria) }}</strong></p>
                    <div class="overflow-x-auto mt-3">
                        <table class="min-w-full text-sm border border-gray-200 dark:border-gray-600">
                            <thead>
                                <tr class="bg-gray-100 dark:bg-gray-700">
                                    <th class="text-left py-2 px-3 border-b border-gray-200 dark:border-gray-600">Neoobjetivo ID</th>
                                    <th class="text-left py-2 px-3 border-b border-gray-200 dark:border-gray-600">URL</th>
                                    <th class="text-left py-2 px-3 border-b border-gray-200 dark:border-gray-600">Tienda</th>
                                    <th class="text-left py-2 px-3 border-b border-gray-200 dark:border-gray-600">Tipo listado</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($resultados_categoria as $rc)
                                    <tr class="border-b border-gray-100 dark:border-gray-700">
                                        <td class="py-2 px-3">{{ $rc['neoobjetivo_id'] ?? '-' }}</td>
                                        <td class="py-2 px-3 break-all">
                                            <a href="{{ $rc['url'] ?? '#' }}" target="_blank" rel="noopener" class="text-blue-600 dark:text-blue-400 hover:underline">{{ Str::limit($rc['url'] ?? '', 60) }}</a>
                                        </td>
                                        <td class="py-2 px-3">{{ $rc['tienda_nombre'] ?? $rc['tienda_id'] ?? '-' }}</td>
                                        <td class="py-2 px-3"><span class="font-medium">{{ $rc['tipo_listado'] ?? '-' }}</span></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-sm text-gray-500 dark:text-gray-500 mt-2">Ninguna fila con tienda y tipo de listado (sitemap/paginación/mostrar_más) en esta ejecución.</p>
                @endif
            </div>
        @endif

        @if (empty($resultados))
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <p class="text-gray-600 dark:text-gray-400">No se encontraron URLs para procesar o no se realizó ninguna petición al VPS.</p>
            </div>
        @else
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 border border-blue-200 dark:border-blue-800">
                <h3 class="font-semibold text-base mb-2">Resumen por URL</h3>
                <div class="space-y-2 text-sm">
                    @foreach ($resultados as $idxResumen => $rResumen)
                        @php
                            $redsResumen = is_array($rResumen['redirecciones'] ?? null) ? $rResumen['redirecciones'] : [];
                            $nGuardadosResumen = count(array_filter($redsResumen, fn($red) => empty($red['skipped']) && empty($red['error'])));
                            $nOmitidosResumen = count(array_filter($redsResumen, fn($red) => !empty($red['skipped'])));
                            $nErroresResumen = count(array_filter($redsResumen, fn($red) => !empty($red['error'])));
                        @endphp
                        <div>
                            <p class="font-medium">URL {{ $idxResumen + 1 }}:</p>
                            <p class="text-xs break-all text-gray-500 dark:text-gray-400">
                                {{ $rResumen['url'] ?? '-' }}
                            </p>
                            <p class="text-gray-700 dark:text-gray-300">
                                Guardados: <strong>{{ $nGuardadosResumen }}</strong>
                                — Omitidos (ya en neo): <strong>{{ $nOmitidosResumen }}</strong>
                                — Errores: <strong>{{ $nErroresResumen }}</strong>
                            </p>
                        </div>
                    @endforeach
                </div>
            </div>
            @foreach ($resultados as $idx => $r)
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 space-y-2">
                    <h2 class="font-semibold text-lg">Petición {{ $idx + 1 }}</h2>
                    <p class="text-sm"><span class="text-gray-500">URL:</span> <a href="{{ $r['url'] }}" target="_blank" rel="noopener" class="text-blue-600 dark:text-blue-400 break-all">{{ $r['url'] }}</a></p>
                    <p class="text-sm"><span class="text-gray-500">HTTP status:</span> {{ $r['http_status'] ?? '-' }}</p>
                    @if (!empty($r['error']))
                        <p class="text-sm text-red-600 dark:text-red-400"><span class="text-gray-500">Error:</span> {{ $r['error'] }}</p>
                    @endif
                    @if (!empty($r['hrefs']))
                        <div class="mt-3">
                            <span class="text-gray-500 text-sm block mb-1">Enlaces «Ver oferta» extraídos ({{ count($r['hrefs']) }}):</span>
                            <ul class="list-disc list-inside space-y-1 text-sm">
                                @foreach ($r['hrefs'] as $href)
                                    <li class="break-all">
                                        <a href="{{ $href }}" target="_blank" rel="noopener" class="text-blue-600 dark:text-blue-400 hover:underline">{{ $href }}</a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @elseif (isset($r['hrefs']) && empty($r['error']))
                        <p class="text-sm text-gray-500 mt-2">No se encontraron enlaces de oferta en el HTML.</p>
                    @endif
                    @if (!empty($r['redirecciones']))
                        @foreach ($r['redirecciones'] as $redIdx => $red)
                            @php
                                $neoSinRedirVps = !empty($red['sin_llamada_redireccion_vps'])
                                    || (!empty($red['success']) && empty($red['final_url']) && empty($red['error']));
                            @endphp
                            <div class="mt-4 p-4 rounded-lg border {{ !empty($red['skipped']) ? 'bg-amber-50 dark:bg-amber-900/20 border-amber-200 dark:border-amber-800' : (!empty($red['success']) ? 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800' : 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800') }}">
                                <h3 class="font-semibold text-sm mb-2">
                                    @if ($neoSinRedirVps && empty($red['skipped']))
                                        Guardado Neo sin redirección VPS (URL {{ $redIdx + 1 }})
                                    @else
                                        Redirección y guardado (URL {{ $redIdx + 1 }})
                                    @endif
                                </h3>
                                @if (!empty($red['skipped']))
                                    <p class="text-sm text-amber-700 dark:text-amber-300">{{ $red['reason'] ?? 'Omitido' }}</p>
                                    @if (!empty($red['url_limpiada'])) <p class="text-xs text-gray-500 mt-1 break-all">URL limpiada: {{ $red['url_limpiada'] }}</p> @endif
                                @else
                                    @if ($neoSinRedirVps)
                                        <p class="text-sm text-green-800 dark:text-green-200">No se llamó al endpoint <span class="font-mono text-xs">/redireccion</span> del VPS (desactivado en el cron). Tras comprobar que no existía en <code class="text-xs">neo</code>, se insertó la fila con <span class="font-medium">url</span> vacía y el valor normalizado en <span class="font-medium">neo</span>.</p>
                                        @if (!empty($red['url_solicitada'])) <p class="text-xs text-gray-500 dark:text-gray-400 mt-2 break-all">URL relocator / limpiada: {{ $red['url_solicitada'] }}</p> @endif
                                    @elseif (!empty($red['success']) && !empty($red['final_url']))
                                        <p class="text-sm mb-1"><span class="text-gray-500">URL final:</span></p>
                                        <p class="text-sm break-all"><a href="{{ $red['final_url'] }}" target="_blank" rel="noopener" class="text-blue-600 dark:text-blue-400 hover:underline">{{ $red['final_url'] }}</a></p>
                                        @if (isset($red['intentos'])) <p class="text-xs text-gray-500 mt-1">Intentos: {{ $red['intentos'] }}</p> @endif
                                    @else
                                        <p class="text-sm text-red-600 dark:text-red-400"><span class="text-gray-500">Error:</span> {{ $red['error'] ?? 'Desconocido' }}</p>
                                        @if (!empty($red['url_solicitada'])) <p class="text-xs text-gray-500 mt-1 break-all">URL solicitada: {{ $red['url_solicitada'] }}</p> @endif
                                        @if (isset($red['proxies_intentados'])) <p class="text-xs text-gray-500">Proxies intentados: {{ $red['proxies_intentados'] }}</p> @endif
                                    @endif
                                    @if (!empty($red['ips_intentadas']))
                                        <p class="text-xs text-gray-500 mt-1">IPs intentadas: {{ is_array($red['ips_intentadas']) ? implode(', ', $red['ips_intentadas']) : $red['ips_intentadas'] }}</p>
                                    @endif
                                    @if (!empty($red['detalle_por_intento']))
                                        <details class="mt-2 text-xs">
                                            <summary class="cursor-pointer text-gray-500">Detalle por intento</summary>
                                            <pre class="mt-1 p-2 bg-gray-100 dark:bg-gray-700 rounded overflow-x-auto">{{ json_encode($red['detalle_por_intento'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                        </details>
                                    @endif
                                @endif
                                @if (!empty($red['log_pasos']))
                                    <div class="mt-4 border-t border-gray-200 dark:border-gray-600 pt-3">
                                        <h4 class="text-xs font-semibold text-gray-600 dark:text-gray-400 mb-2">Ejecución y decisiones</h4>
                                        <ol class="list-decimal list-inside space-y-1 text-xs">
                                            @foreach ($red['log_pasos'] as $paso)
                                                <li class="break-words">
                                                    @if (!empty($paso['valor']))
                                                        <span class="text-gray-600 dark:text-gray-300">{{ $paso['texto'] }}</span>
                                                        <span class="block ml-4 mt-0.5 break-all text-gray-500">{{ $paso['valor'] }}</span>
                                                    @elseif (!empty($paso['decision']))
                                                        <span class="text-gray-600 dark:text-gray-300">{{ $paso['texto'] }}</span>
                                                        <span class="font-medium {{ str_contains($paso['decision'] ?? '', 'Sí') ? 'text-green-600 dark:text-green-400' : (str_contains($paso['decision'] ?? '', 'No') ? 'text-blue-600 dark:text-blue-400' : 'text-gray-700 dark:text-gray-300') }}"> {{ $paso['decision'] }}</span>
                                                    @else
                                                        <span class="text-gray-600 dark:text-gray-300">{{ $paso['texto'] ?? '' }}</span>
                                                    @endif
                                                </li>
                                            @endforeach
                                        </ol>
                                    </div>
                                @endif
                                @if (!empty($red['accion_final']))
                                    <p class="mt-2 text-sm font-medium text-gray-700 dark:text-gray-300"><span class="text-gray-500">Acción final:</span> {{ $red['accion_final'] }}</p>
                                @endif
                            </div>
                        @endforeach
                    @endif
                    <div class="mt-2">
                        <span class="text-gray-500 text-sm block mb-1">Respuesta del VPS:</span>
                        @if (array_key_exists('body_raw', $r))
                            <pre class="bg-gray-100 dark:bg-gray-700 p-3 rounded text-xs overflow-x-auto overflow-y-auto max-h-96 whitespace-pre-wrap break-words">{{ $r['body_raw'] ?: '(vacío)' }}{{ !empty($r['error']) ? "\nError: " . $r['error'] : '' }}</pre>
                        @else
                            <p class="text-gray-500 text-sm italic">No guardado en el log (se omite para reducir tamaño).</p>
                        @endif
                    </div>
                </div>
            @endforeach
        @endif

        @if (!empty($ejecucion))
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 border border-blue-200 dark:border-blue-800">
                <h3 class="font-semibold text-base mb-2">Descifrar</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">
                    Las URLs nuevas del log se guardan como tokens <code class="text-xs bg-gray-100 dark:bg-gray-700 px-1 rounded">neov2:…</code> (cifrado reversible).
                    Para descifrarlas escribe el valor de <code class="text-xs">NEO_ENCRYPT_KEY</code> (según <code class="text-xs">config('anti-scraping.neo_encrypt_key')</code>).
                    También se soportan logs antiguos en formato <code class="text-xs">encv1:…</code> (con <code class="text-xs">SIGNED_URLS_SECRET</code> / <code class="text-xs">APP_KEY</code>).
                    Los logs antiguos con texto <code class="text-xs">[oculto hash=…]</code> o <code class="text-xs">[url oculta …]</code> no contienen tokens descifrables.
                </p>
                <div class="flex flex-wrap items-center gap-2">
                    <input
                        id="decrypt-secret"
                        type="password"
                        placeholder="Clave de descifrado"
                        class="w-full sm:w-96 px-3 py-2 border rounded bg-white dark:bg-gray-700 dark:text-white"
                    >
                    <button
                        id="btn-descifrar-log"
                        type="button"
                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded"
                    >
                        Descifrar
                    </button>
                </div>
                <p id="decrypt-status" class="text-xs text-gray-500 mt-2"></p>
            </div>
        @endif

        @if (isset($neo_backfill_tienda_por_url) && is_array($neo_backfill_tienda_por_url))
            @php $nbT = $neo_backfill_tienda_por_url; @endphp
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 border-l-4 border-indigo-500 mt-4">
                <h2 class="font-semibold text-lg mb-2">Neo: tienda por URL (al final del cron)</h2>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                    Se ejecuta siempre al terminar la pasada, aunque no hubiera filas <code class="text-xs">neoobjetivo</code> que procesar o no entrara rama Neo ni categoría-tienda.
                </p>
                <p class="text-sm text-gray-700 dark:text-gray-300">
                    Filas <code class="text-xs">neo</code> sin <code class="text-xs">tienda_id</code> con <code class="text-xs">url_cipher</code> y <code class="text-xs">url_lookup</code>:
                    revisadas <strong>{{ (int) ($nbT['revisadas'] ?? 0) }}</strong>,
                    <span class="text-green-700 dark:text-green-400">tienda asignada</span> <strong>{{ (int) ($nbT['actualizadas'] ?? 0) }}</strong>,
                    sin coincidencia de host <strong>{{ (int) ($nbT['sin_tienda_detectada'] ?? 0) }}</strong>,
                    URL vacía al descifrar <strong>{{ (int) ($nbT['url_descifrada_vacia'] ?? 0) }}</strong>,
                    errores <strong>{{ (int) ($nbT['errores'] ?? 0) }}</strong>
                </p>
            </div>
        @endif
        </div>
    </div>

    <script>
        const ENC_PREFIX_V1 = 'encv1:';
        const ENC_PREFIX_V2 = 'neov2:';
        const ENC_REGEX = /(encv1|neov2):[A-Za-z0-9+/=]+/g;

        function base64ToBytes(base64) {
            const binary = atob(base64);
            const bytes = new Uint8Array(binary.length);
            for (let i = 0; i < binary.length; i++) {
                bytes[i] = binary.charCodeAt(i);
            }
            return bytes;
        }

        async function sha256Bytes(text) {
            const data = new TextEncoder().encode(text);
            const digest = await crypto.subtle.digest('SHA-256', data);
            return new Uint8Array(digest);
        }

        async function decryptEncToken(token, secret) {
            if (!token || (!token.startsWith(ENC_PREFIX_V1) && !token.startsWith(ENC_PREFIX_V2))) {
                return token;
            }

            const keyBytes = await sha256Bytes(secret);
            const payloadB64 = token.slice(token.indexOf(':') + 1);
            const payload = base64ToBytes(payloadB64);
            let plainBuffer;

            if (token.startsWith(ENC_PREFIX_V2)) {
                // neov2: iv(12) + tag(16) + ciphertext (AES-256-GCM)
                if (payload.length <= 28) {
                    throw new Error('Payload neov2 inválido');
                }
                const iv = payload.slice(0, 12);
                const tag = payload.slice(12, 28);
                const cipherBytes = payload.slice(28);
                const cipherAndTag = new Uint8Array(cipherBytes.length + tag.length);
                cipherAndTag.set(cipherBytes, 0);
                cipherAndTag.set(tag, cipherBytes.length);
                const cryptoKey = await crypto.subtle.importKey(
                    'raw',
                    keyBytes,
                    { name: 'AES-GCM' },
                    false,
                    ['decrypt']
                );
                plainBuffer = await crypto.subtle.decrypt(
                    { name: 'AES-GCM', iv, tagLength: 128 },
                    cryptoKey,
                    cipherAndTag
                );
            } else {
                // encv1: iv(16) + ciphertext (AES-256-CBC)
                if (payload.length <= 16) {
                    throw new Error('Payload encv1 inválido');
                }
                const iv = payload.slice(0, 16);
                const cipherBytes = payload.slice(16);
                const cryptoKey = await crypto.subtle.importKey(
                    'raw',
                    keyBytes,
                    { name: 'AES-CBC' },
                    false,
                    ['decrypt']
                );
                plainBuffer = await crypto.subtle.decrypt(
                    { name: 'AES-CBC', iv },
                    cryptoKey,
                    cipherBytes
                );
            }

            return new TextDecoder().decode(plainBuffer);
        }

        async function replaceEncryptedTokens(text, secret) {
            if (!text || typeof text !== 'string') {
                return text;
            }

            const matches = text.match(ENC_REGEX);
            if (!matches || matches.length === 0) {
                return text;
            }

            let output = text;
            const unicos = [...new Set(matches)];
            for (const token of unicos) {
                try {
                    const plain = await decryptEncToken(token, secret);
                    output = output.split(token).join(plain);
                } catch (e) {
                    // Mantener token si falla un descifrado concreto.
                }
            }
            return output;
        }

        async function descifrarLogVisible(secret) {
            const status = document.getElementById('decrypt-status');
            const root = document.querySelector('.max-w-5xl');
            if (!root) {
                return { cambios: 0, fallos: 0 };
            }

            let cambios = 0;
            let fallos = 0;

            const walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT, null);
            const textNodes = [];
            while (walker.nextNode()) {
                textNodes.push(walker.currentNode);
            }

            for (const node of textNodes) {
                const original = node.nodeValue || '';
                if (!original.includes(ENC_PREFIX_V1) && !original.includes(ENC_PREFIX_V2)) continue;
                const replaced = await replaceEncryptedTokens(original, secret);
                if (replaced !== original) {
                    node.nodeValue = replaced;
                    cambios++;
                } else {
                    fallos++;
                }
            }

            const attrs = ['href', 'title', 'data-url'];
            for (const attr of attrs) {
                for (const prefix of [ENC_PREFIX_V1, ENC_PREFIX_V2]) {
                    const elements = root.querySelectorAll(`[${attr}*="${prefix}"]`);
                    for (const el of elements) {
                        const original = el.getAttribute(attr) || '';
                        if (!original.includes(ENC_PREFIX_V1) && !original.includes(ENC_PREFIX_V2)) continue;
                        const replaced = await replaceEncryptedTokens(original, secret);
                        if (replaced !== original) {
                            el.setAttribute(attr, replaced);
                            cambios++;
                        } else {
                            fallos++;
                        }
                    }
                }
            }

            if (status) {
                if (cambios === 0) {
                    status.textContent = 'No se ha sustituido ningún token neov2/encv1. Revisa la clave, o este log es antiguo (formato sin cifrado reversible).';
                } else {
                    status.textContent = `Descifrado completado. Fragmentos actualizados: ${cambios}. Sin cambio o error al descifrar: ${fallos}.`;
                }
            }

            return { cambios, fallos };
        }

        document.addEventListener('DOMContentLoaded', function () {
            const btnDescifrar = document.getElementById('btn-descifrar-log');
            const inputSecret = document.getElementById('decrypt-secret');
            const status = document.getElementById('decrypt-status');

            if (!btnDescifrar || !inputSecret) {
                return;
            }

            btnDescifrar.addEventListener('click', async function () {
                const secret = (inputSecret.value || '').trim();
                if (!secret) {
                    if (status) status.textContent = 'Debes indicar una clave para descifrar.';
                    return;
                }

                btnDescifrar.disabled = true;
                const oldText = btnDescifrar.textContent;
                btnDescifrar.textContent = 'Descifrando...';
                if (status) status.textContent = 'Procesando...';
                try {
                    await descifrarLogVisible(secret);
                } catch (e) {
                    if (status) status.textContent = 'Error al descifrar. Revisa la clave.';
                } finally {
                    btnDescifrar.disabled = false;
                    btnDescifrar.textContent = oldText;
                }
            });
        });
    </script>
</x-app-layout>
