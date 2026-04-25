<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.dashboard') }}">
                <h2 class="font-semibold text-xl text-white leading-tight">Inicio -></h2>
            </a>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Cron Avisos sin stock (scrapear) — Resultado
            </h2>
        </div>
    </x-slot>

    <div class="py-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="max-w-5xl mx-auto space-y-6">
            <p class="text-sm text-gray-600 dark:text-gray-400">
                Cada vez que se llama a <code class="text-xs">/admin/avisos/scrapear-sin-stock?token=…</code> o al comando Artisan <code class="text-xs">avisos:scrapear-sin-stock</code>, se guarda una fila aquí.
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
                        <a href="{{ route('admin.ejecuciones.avisos-sin-stock-scrapear', ['mes' => $mesAnterior]) }}"
                            class="px-2 py-1 text-sm rounded border border-gray-200 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700">
                            ← Mes anterior
                        </a>
                        <h2 class="font-semibold text-lg capitalize">{{ $mesTexto }}</h2>
                        <a href="{{ route('admin.ejecuciones.avisos-sin-stock-scrapear', ['mes' => $mesSiguiente]) }}"
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
                                <a href="{{ route('admin.ejecuciones.avisos-sin-stock-scrapear', ['fecha' => $fechaKey, 'mes' => $mesSeleccionado->format('Y-m')]) }}"
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
                                <th class="text-left py-2 pr-4">Procesados</th>
                                <th class="text-left py-2 pr-4">Precio OK</th>
                                <th class="text-left py-2 pr-4">Aplazados</th>
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
                                        <a href="{{ route('admin.ejecuciones.avisos-sin-stock-scrapear', ['ejecucion_id' => $e->id, 'fecha' => $fechaSeleccionada->toDateString(), 'mes' => $mesSeleccionado->format('Y-m')]) }}" class="text-blue-600 dark:text-blue-400 hover:underline">Ver log</a>
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
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 border-l-4 border-blue-500">
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        <strong>Ejecución #{{ $ejecucion->id }}</strong>
                        — Inicio: {{ $ejecucion->inicio?->format('d/m/Y H:i') }},
                        Fin: {{ $ejecucion->fin?->format('d/m/Y H:i') }}
                    </p>
                    <p class="mt-2 text-sm">
                        Procesados: <strong>{{ $ejecucion->total }}</strong> —
                        Con precio aplicado: <strong>{{ $ejecucion->total_guardado }}</strong> —
                        Aplazados (sin precio): <strong>{{ $ejecucion->total_errores }}</strong>
                    </p>
                    <p class="mt-2">
                        <a href="{{ route('admin.ejecuciones.avisos-sin-stock-scrapear', ['fecha' => $fechaSeleccionada->toDateString(), 'mes' => $mesSeleccionado->format('Y-m')]) }}" class="text-blue-600 dark:text-blue-400 hover:underline">Volver al listado</a>
                    </p>
                </div>

                @php
                    $logEjecucion = is_array($ejecucion->log ?? null) ? $ejecucion->log : [];
                    $contadores = $logEjecucion['contadores'] ?? [];
                    $resultados = $logEjecucion['resultados'] ?? [];
                    $pasosEjecucion = is_array($logEjecucion['pasos'] ?? null) ? $logEjecucion['pasos'] : [];
                    $errorEjecucion = is_array($logEjecucion['error'] ?? null) ? $logEjecucion['error'] : null;
                @endphp

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
                        <h3 class="font-semibold text-base mb-2">Contadores</h3>
                        <pre class="text-xs bg-gray-100 dark:bg-gray-900 p-3 rounded overflow-x-auto">{{ json_encode($contadores, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
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
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 space-y-4">
                        <h3 class="font-semibold text-base">Ofertas procesadas ({{ count($resultados) }})</h3>
                        @foreach ($resultados as $idx => $r)
                            <div class="rounded-lg border border-gray-200 dark:border-gray-600 p-3 text-sm space-y-1">
                                <p><span class="text-gray-500">#{{ $idx + 1 }}</span> — Aviso {{ $r['aviso_id'] ?? '-' }} — Oferta {{ $r['oferta_id'] ?? '-' }} — <strong>{{ $r['accion'] ?? '-' }}</strong></p>
                                @if (!empty($r['tienda']))
                                    <p class="text-gray-600 dark:text-gray-400">Tienda: {{ $r['tienda'] }}</p>
                                @endif
                                @if (!empty($r['oferta_url']))
                                    <p class="break-all"><a href="{{ $r['oferta_url'] }}" target="_blank" rel="noopener" class="text-blue-600 dark:text-blue-400 hover:underline">{{ \Illuminate\Support\Str::limit($r['oferta_url'], 100) }}</a></p>
                                @endif
                                @if (($r['accion'] ?? '') === 'precio_aplicado' && isset($r['precio']))
                                    <p>Precio: <strong>{{ $r['precio'] }}</strong></p>
                                @endif
                                @if (!empty($r['scraping']))
                                    <pre class="text-xs bg-gray-100 dark:bg-gray-900 p-2 rounded mt-2 overflow-x-auto">{{ json_encode($r['scraping'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @elseif (isset($ejecucion) && empty($resultados))
                    <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg p-4 text-sm">
                        No hay filas en <code class="text-xs">resultados</code> para esta ejecución (cero avisos procesados: todos filtrados o ningún candidato).
                    </div>
                @endif
            @endif

        </div>
    </div>
</x-app-layout>
