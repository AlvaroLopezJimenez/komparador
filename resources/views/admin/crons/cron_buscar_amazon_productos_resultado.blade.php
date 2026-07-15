<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.dashboard') }}">
                <h2 class="font-semibold text-xl text-white leading-tight">Inicio -></h2>
            </a>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Cron Buscar productos (Amazon + AliExpress) — Resultado
            </h2>
        </div>
    </x-slot>

    <div class="py-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="max-w-5xl mx-auto space-y-6">
            <p class="text-sm text-gray-600 dark:text-gray-400">
                Por cada producto elegible busca primero en <strong>Amazon</strong> y después en <strong>AliExpress</strong>. El lote (hasta 25) sigue desde el último <code class="text-xs">producto_id</code> procesado y, si hace falta, continúa desde el id 1. La URL del cron espera al terminar y muestra el resumen.
            </p>

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
                        <a href="{{ route('admin.ejecuciones.buscar-amazon-productos', ['mes' => $mesAnterior]) }}"
                            class="px-2 py-1 text-sm rounded border border-gray-200 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700">
                            ← Mes anterior
                        </a>
                        <h2 class="font-semibold text-lg capitalize">{{ $mesTexto }}</h2>
                        <a href="{{ route('admin.ejecuciones.buscar-amazon-productos', ['mes' => $mesSiguiente]) }}"
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
                                <a href="{{ route('admin.ejecuciones.buscar-amazon-productos', ['fecha' => $fechaKey, 'mes' => $mesSeleccionado->format('Y-m')]) }}"
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
                                <th class="text-left py-2 pr-4">Productos</th>
                                <th class="text-left py-2 pr-4">URLs neo</th>
                                <th class="text-left py-2 pr-4">Err. Amazon</th>
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
                                        <a href="{{ route('admin.ejecuciones.buscar-amazon-productos', ['ejecucion_id' => $e->id, 'fecha' => $fechaSeleccionada->toDateString(), 'mes' => $mesSeleccionado->format('Y-m')]) }}" class="text-blue-600 dark:text-blue-400 hover:underline">Ver log</a>
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

            @if (isset($ejecucion))
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 border-l-4 border-amber-500">
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        <strong>Ejecución #{{ $ejecucion->id }}</strong>
                        — Inicio: {{ $ejecucion->inicio?->format('d/m/Y H:i') }},
                        Fin: {{ $ejecucion->fin?->format('d/m/Y H:i') }}
                    </p>
                    <p class="mt-2 text-sm">
                        Productos procesados: <strong>{{ $ejecucion->total }}</strong> —
                        URLs insertadas en neo: <strong>{{ $ejecucion->total_guardado }}</strong> —
                        Errores Amazon: <strong>{{ $ejecucion->total_errores }}</strong>
                    </p>
                    <p class="mt-2">
                        <a href="{{ route('admin.ejecuciones.buscar-amazon-productos', ['fecha' => $fechaSeleccionada->toDateString(), 'mes' => $mesSeleccionado->format('Y-m')]) }}" class="text-blue-600 dark:text-blue-400 hover:underline">Volver al listado</a>
                    </p>
                </div>

                @php
                    $logEjecucion = is_array($ejecucion->log ?? null) ? $ejecucion->log : [];
                    $contadores = $logEjecucion['contadores'] ?? [];
                    $resultados = $logEjecucion['resultados'] ?? [];
                    $pasosEjecucion = is_array($logEjecucion['pasos'] ?? null) ? $logEjecucion['pasos'] : [];
                    $errorEjecucion = is_array($logEjecucion['error'] ?? null) ? $logEjecucion['error'] : null;
                    $estadoEjecucion = $logEjecucion['estado'] ?? 'desconocido';
                    $pareceInterrumpida = $estadoEjecucion === 'running'
                        && empty($ejecucion->fin)
                        && (($ejecucion->total ?? 0) > 0 || count($resultados) > 0);
                @endphp

                @if ($estadoEjecucion === 'ok_parcial')
                    <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg p-4 text-sm text-amber-900 dark:text-amber-100">
                        Lote completado. Quedan productos pendientes — vuelve a llamar al cron para continuar.
                    </div>
                @elseif ($estadoEjecucion === 'interrumpido' || $pareceInterrumpida)
                    <div class="bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-800 rounded-lg p-4 text-sm text-orange-900 dark:text-orange-100">
                        Ejecución interrumpida antes de finalizar (timeout del servidor, habitualmente ~5 min). Los productos ya procesados aparecen abajo. Vuelve a ejecutar el cron para continuar el lote.
                    </div>
                @elseif ($estadoEjecucion === 'running' && empty($ejecucion->fin) && !$pareceInterrumpida)
                    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4 text-sm text-blue-900 dark:text-blue-100">
                        En curso… Recarga esta página para ver el progreso.
                    </div>
                @endif

                @if (!empty($errorEjecucion))
                    <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4 text-sm text-red-800 dark:text-red-200">
                        <p><strong>Error:</strong> {{ $errorEjecucion['mensaje'] ?? '-' }}</p>
                        @if (!empty($errorEjecucion['tipo']))
                            <p class="text-xs mt-1">{{ $errorEjecucion['tipo'] }}</p>
                        @endif
                    </div>
                @endif

                @if (!empty($contadores))
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                        <h3 class="font-semibold text-base mb-3">Contadores</h3>
                        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-2 text-sm">
                            <div><dt class="text-gray-500 inline">Productos en lote:</dt> <dd class="inline font-medium">{{ $contadores['productos_en_query'] ?? 0 }}</dd></div>
                            <div><dt class="text-gray-500 inline">Elegibles total:</dt> <dd class="inline font-medium">{{ $contadores['productos_elegibles_total'] ?? $contadores['productos_pendientes_total'] ?? '—' }}</dd></div>
                            <div><dt class="text-gray-500 inline">Límite por ejecución:</dt> <dd class="inline font-medium">{{ $contadores['limite_por_ejecucion'] ?? 25 }}</dd></div>
                            <div><dt class="text-gray-500 inline">Último producto_id:</dt> <dd class="inline font-medium">{{ $logEjecucion['ultimo_producto_id'] ?? '—' }}</dd></div>
                            <div><dt class="text-gray-500 inline">Procesados:</dt> <dd class="inline font-medium">{{ $contadores['productos_procesados'] ?? 0 }}</dd></div>
                            <div><dt class="text-gray-500 inline">Omitidos sin nombre:</dt> <dd class="inline font-medium">{{ $contadores['omitido_sin_nombre'] ?? 0 }}</dd></div>
                            <div><dt class="text-gray-500 inline">Omitidos sin palabras:</dt> <dd class="inline font-medium">{{ $contadores['omitido_sin_palabras'] ?? 0 }}</dd></div>
                            <div><dt class="text-gray-500 inline">Errores Amazon:</dt> <dd class="inline font-medium text-red-600">{{ $contadores['productos_error_amazon'] ?? 0 }}</dd></div>
                            <div><dt class="text-gray-500 inline">Errores AliExpress:</dt> <dd class="inline font-medium text-red-600">{{ $contadores['productos_error_aliexpress'] ?? 0 }}</dd></div>
                            <div><dt class="text-gray-500 inline">URLs Amazon filtradas:</dt> <dd class="inline font-medium">{{ $contadores['urls_amazon_filtradas'] ?? 0 }}</dd></div>
                            <div><dt class="text-gray-500 inline">URLs AliExpress filtradas:</dt> <dd class="inline font-medium">{{ $contadores['urls_aliexpress_filtradas'] ?? 0 }}</dd></div>
                            <div><dt class="text-gray-500 inline">URLs insertadas neo:</dt> <dd class="inline font-medium text-green-600">{{ $contadores['urls_insertadas_neo'] ?? 0 }}</dd></div>
                            <div><dt class="text-gray-500 inline">Omitidas (oferta):</dt> <dd class="inline font-medium">{{ $contadores['urls_omitida_oferta'] ?? 0 }}</dd></div>
                            <div><dt class="text-gray-500 inline">Omitidas (descartada):</dt> <dd class="inline font-medium">{{ $contadores['urls_omitida_descartada'] ?? 0 }}</dd></div>
                            <div><dt class="text-gray-500 inline">Omitidas (neo):</dt> <dd class="inline font-medium">{{ $contadores['urls_omitida_neo'] ?? 0 }}</dd></div>
                            <div><dt class="text-gray-500 inline">CSV coincidentes:</dt> <dd class="inline font-medium">{{ $contadores['csv_filas_coincidentes'] ?? 0 }}</dd></div>
                            <div><dt class="text-gray-500 inline">CSV insertadas neo:</dt> <dd class="inline font-medium text-green-600">{{ $contadores['csv_insertadas_neo'] ?? 0 }}</dd></div>
                            <div><dt class="text-gray-500 inline">CSV ya en neo:</dt> <dd class="inline font-medium">{{ $contadores['csv_ya_en_neo'] ?? 0 }}</dd></div>
                            <div><dt class="text-gray-500 inline">CSV omitidas (oferta):</dt> <dd class="inline font-medium">{{ $contadores['csv_omitida_oferta'] ?? 0 }}</dd></div>
                            <div><dt class="text-gray-500 inline">CSV aniadida_neo=si:</dt> <dd class="inline font-medium">{{ $contadores['csv_aniadida_neo_si'] ?? 0 }}</dd></div>
                        </dl>
                    </div>
                @endif

                @if (!empty($pasosEjecucion))
                    <details class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                        <summary class="cursor-pointer font-medium text-sm text-blue-600 dark:text-blue-400">Pasos internos ({{ count($pasosEjecucion) }})</summary>
                        <ol class="mt-3 list-decimal list-inside space-y-2 text-sm">
                            @foreach ($pasosEjecucion as $paso)
                                <li>
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
                    </details>
                @endif

                @if (!empty($resultados))
                    @php
                        $urlsInsertadasEjecucion = [];
                        foreach ($resultados as $r) {
                            foreach ($r['detalle_urls'] ?? [] as $det) {
                                if (!is_array($det) || ($det['accion'] ?? '') !== 'insertada') {
                                    continue;
                                }
                                $urlDetalle = trim((string) ($det['url'] ?? ''));
                                if ($urlDetalle !== '') {
                                    $urlsInsertadasEjecucion[] = $urlDetalle;
                                }
                            }
                        }
                        $urlsInsertadasEjecucion = array_values(array_unique($urlsInsertadasEjecucion));
                    @endphp
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 space-y-4">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <h3 class="font-semibold text-base">Productos ({{ count($resultados) }})</h3>
                            @if ($urlsInsertadasEjecucion !== [])
                                <button type="button"
                                    class="px-3 py-1.5 text-sm rounded bg-green-600 text-white hover:bg-green-700 shrink-0"
                                    onclick="copiarTextoCronAmazon('urls-insertadas-ejecucion')">
                                    Copiar todas las URLs insertadas en Neo ({{ count($urlsInsertadasEjecucion) }})
                                </button>
                                <textarea id="urls-insertadas-ejecucion" readonly class="sr-only">{{ implode("\n", $urlsInsertadasEjecucion) }}</textarea>
                            @endif
                        </div>
                        @foreach ($resultados as $idx => $r)
                            <div class="rounded-lg border border-gray-200 dark:border-gray-600 p-3 text-sm space-y-2">
                                <p>
                                    <span class="text-gray-500">#{{ $idx + 1 }}</span> —
                                    Producto <a href="{{ route('admin.productos.edit', $r['producto_id'] ?? 0) }}" class="text-blue-600 dark:text-blue-400 hover:underline">#{{ $r['producto_id'] ?? '-' }}</a>
                                </p>
                                @if (!empty($r['nombre']))
                                    <p class="font-medium">{{ $r['nombre'] }}</p>
                                @endif
                                @if (!empty($r['palabras_exigidas']))
                                    <p class="text-gray-600 dark:text-gray-400">Palabras exigidas: <code class="text-xs bg-gray-100 dark:bg-gray-700 px-1 rounded">{{ $r['palabras_exigidas'] }}</code></p>
                                @endif

                                @if (!empty($r['error_amazon']) || !empty($r['error_aliexpress']))
                                    @if (!empty($r['error_amazon']))
                                        <p class="text-red-600 dark:text-red-400"><strong>Error Amazon:</strong> {{ $r['error_amazon'] }}</p>
                                    @endif
                                    @if (!empty($r['error_aliexpress']))
                                        <p class="text-red-600 dark:text-red-400"><strong>Error AliExpress:</strong> {{ $r['error_aliexpress'] }}</p>
                                    @endif
                                @endif
                                @if (!empty($r['error']) && empty($r['error_amazon']) && empty($r['error_aliexpress']))
                                    <p class="text-red-600 dark:text-red-400"><strong>Error:</strong> {{ $r['error'] }}</p>
                                @endif
                                @if (!empty($r['codigos_ofertas_consultadas']))
                                    <p class="text-gray-600 dark:text-gray-400 text-xs">
                                        Códigos CSV: ofertas consultadas <strong>{{ $r['codigos_ofertas_consultadas'] }}</strong>
                                        @if (!empty($r['codigos_sincronizados']))
                                            — <span class="text-green-600 dark:text-green-400">producto actualizado</span>
                                        @endif
                                    </p>
                                @endif
                                <p>
                                    Páginas Amazon: <strong>{{ $r['paginas_amazon'] ?? 0 }}</strong> —
                                    URLs Amazon: <strong>{{ $r['urls_amazon'] ?? 0 }}</strong> —
                                    URLs AliExpress: <strong>{{ $r['urls_aliexpress'] ?? 0 }}</strong> —
                                    Insertadas: <strong class="text-green-600">{{ $r['urls_insertadas'] ?? 0 }}</strong> —
                                    Omitidas: <strong>{{ $r['urls_omitidas'] ?? 0 }}</strong>
                                    @if (($r['csv_coincidentes_codigo'] ?? 0) > 0)
                                        — CSV por código: <strong>{{ $r['csv_coincidentes_codigo'] }}</strong> coincidencias,
                                        <strong class="text-green-600">{{ $r['csv_insertadas_codigo'] ?? 0 }}</strong> en Neo
                                    @endif
                                </p>

                                @if (!empty($r['detalle_urls']) && is_array($r['detalle_urls']))
                                    @php
                                        $urlsProducto = [];
                                        foreach ($r['detalle_urls'] as $det) {
                                            if (!is_array($det)) {
                                                continue;
                                            }
                                            if (($det['accion'] ?? '') !== 'insertada') {
                                                continue;
                                            }
                                            $urlDetalle = trim((string) ($det['url'] ?? ''));
                                            if ($urlDetalle !== '') {
                                                $urlsProducto[] = $urlDetalle;
                                            }
                                        }
                                        $urlsProducto = array_values(array_unique($urlsProducto));
                                    @endphp
                                    <div class="flex flex-wrap items-center gap-2 mt-1">
                                        <details class="min-w-0">
                                            <summary class="cursor-pointer text-xs text-blue-600 dark:text-blue-400">Detalle URLs ({{ count($r['detalle_urls']) }})</summary>
                                            <ul class="mt-2 space-y-1 text-xs">
                                                @foreach ($r['detalle_urls'] as $det)
                                                    <li class="flex flex-wrap items-start gap-2">
                                                        @if (!empty($det['tienda']))
                                                            <span class="px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 text-[10px] font-medium uppercase shrink-0">{{ $det['tienda'] }}</span>
                                                        @elseif (!empty($det['origen']))
                                                            <span class="px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 text-[10px] font-medium uppercase shrink-0">{{ $det['origen'] }}</span>
                                                        @endif
                                                        @if (!empty($det['origen_busqueda']))
                                                            <span class="px-1.5 py-0.5 rounded bg-purple-100 dark:bg-purple-900/40 text-purple-800 dark:text-purple-200 text-[10px] font-medium shrink-0">{{ $det['origen_busqueda'] }}</span>
                                                        @endif
                                                        @if (($det['accion'] ?? '') === 'insertada')
                                                            <span class="px-1.5 py-0.5 rounded bg-green-100 dark:bg-green-900/40 text-green-800 dark:text-green-200 font-medium shrink-0">Insertada</span>
                                                        @else
                                                            <span class="px-1.5 py-0.5 rounded bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 font-medium shrink-0">Omitida ({{ $det['motivo'] ?? '?' }})</span>
                                                        @endif
                                                        <a href="{{ $det['url'] ?? '#' }}" target="_blank" rel="noopener" class="text-blue-600 dark:text-blue-400 hover:underline break-all">{{ $det['url'] ?? '' }}</a>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </details>
                                        @if ($urlsProducto !== [])
                                            <button type="button"
                                                class="px-2 py-1 text-xs rounded bg-green-600 text-white hover:bg-green-700 shrink-0"
                                                onclick="copiarTextoCronAmazon('urls-producto-{{ $idx }}')">
                                                Copiar URLs
                                            </button>
                                            <textarea id="urls-producto-{{ $idx }}" readonly class="sr-only">{{ implode("\n", $urlsProducto) }}</textarea>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @elseif (isset($ejecucion) && empty($resultados) && !empty($pasosEjecucion))
                    <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg p-4 text-sm">
                        No hay filas en <code class="text-xs">resultados</code> guardadas al cierre. Revisa los pasos internos o recarga si la ejecución sigue en curso.
                    </div>
                @elseif (isset($ejecucion) && empty($resultados))
                    <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg p-4 text-sm">
                        No hay filas en <code class="text-xs">resultados</code> para esta ejecución (ningún producto procesado).
                    </div>
                @endif
            @endif

        </div>
    </div>

    @if (isset($ejecucion))
        <script>
            function copiarTextoCronAmazon(idTextarea) {
                const el = document.getElementById(idTextarea);
                if (!el) {
                    return;
                }
                el.focus();
                el.select();
                if (navigator.clipboard && window.isSecureContext) {
                    navigator.clipboard.writeText(el.value).then(function () {
                        alert('URLs copiadas al portapapeles.');
                    }).catch(function () {
                        document.execCommand('copy');
                        alert('URLs copiadas al portapapeles.');
                    });
                    return;
                }
                document.execCommand('copy');
                alert('URLs copiadas al portapapeles.');
            }
        </script>
    @endif
</x-app-layout>
