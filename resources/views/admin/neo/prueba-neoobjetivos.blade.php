<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.dashboard') }}">
                <h2 class="font-semibold text-xl text-white leading-tight">Inicio -></h2>
            </a>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Prueba Neoobjetivos
            </h2>
        </div>
    </x-slot>

    <div class="py-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="max-w-5xl mx-auto space-y-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
            <h3 class="font-semibold text-lg mb-3">Ejecutar prueba (misma lógica que el cron; escrituras a BD revertidas)</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                Verás el mismo detalle que en «Cron Neo Objetivos - Resultado»: peticiones, respuestas, decisiones por URL y <code class="text-xs">log_pasos</code>.
            </p>
            <form action="{{ route('admin.neo.prueba-neoobjetivos.ejecutar') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label for="url" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">URL a probar</label>
                    <input
                        id="url"
                        name="url"
                        type="text"
                        value="{{ old('url', $form['url'] ?? '') }}"
                        placeholder="https://..."
                        class="w-full px-3 py-2 border rounded bg-white dark:bg-gray-700 dark:text-white"
                        required
                    >
                    @error('url')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="tienda_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tienda</label>
                    <select
                        id="tienda_id"
                        name="tienda_id"
                        class="w-full px-3 py-2 border rounded bg-white dark:bg-gray-700 dark:text-white"
                    >
                        <option value="">Selecciona tienda</option>
                        @foreach($tiendas as $tienda)
                            <option value="{{ $tienda->id }}" {{ (string) old('tienda_id', $form['tienda_id'] ?? '') === (string) $tienda->id ? 'selected' : '' }}>
                                {{ $tienda->nombre }}
                            </option>
                        @endforeach
                    </select>
                    @error('tienda_id')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded">
                    Ejecutar prueba
                </button>
            </form>
            <p class="mt-4 text-sm text-gray-600 dark:text-gray-400">
                Tras pulsar «Ejecutar prueba», recuerda: los textos de inserción o actualización en <code class="text-xs">neo</code> solo reflejan qué haría la lógica en un cron real; aquí son prueba y <strong>no persisten</strong> en la tabla.
            </p>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 border-l-4 border-indigo-500">
            <h3 class="font-semibold text-lg mb-2">Comprobar filas pendientes por visitar</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                Lista filas de <code class="text-xs">neoobjetivo</code> con <code class="text-xs">visitada &lt; ahora - 7 días</code>, mostrando la URL cifrada (valor real en BD) y la URL descifrada.
            </p>
            <form action="{{ route('admin.neo.prueba-neoobjetivos') }}" method="GET" class="flex flex-wrap items-end gap-3">
                <input type="hidden" name="ver_pendientes" value="1">
                <div>
                    <label for="limite_pendientes" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Límite</label>
                    <input
                        id="limite_pendientes"
                        name="limite_pendientes"
                        type="number"
                        min="1"
                        max="200"
                        value="{{ (int) ($limite_pendientes ?? 50) }}"
                        class="w-32 px-3 py-2 border rounded bg-white dark:bg-gray-700 dark:text-white"
                    >
                </div>
                <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded">
                    Ver pendientes
                </button>
            </form>

            @if (!empty($mostrar_pendientes))
                @php
                    $filasPendientes = $filas_pendientes ?? collect();
                @endphp
                <div class="mt-4">
                    <p class="text-sm text-gray-700 dark:text-gray-300 mb-2">
                        Filas encontradas: <strong>{{ $filasPendientes->count() }}</strong>
                    </p>
                    @if ($filasPendientes->isEmpty())
                        <p class="text-sm text-gray-500 dark:text-gray-400">No hay filas pendientes con ese criterio.</p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm border border-gray-200 dark:border-gray-600">
                                <thead>
                                    <tr class="bg-gray-100 dark:bg-gray-700">
                                        <th class="text-left py-2 px-3 border-b border-gray-200 dark:border-gray-600">ID</th>
                                        <th class="text-left py-2 px-3 border-b border-gray-200 dark:border-gray-600">Visitada</th>
                                        <th class="text-left py-2 px-3 border-b border-gray-200 dark:border-gray-600">Rama Neo</th>
                                        <th class="text-left py-2 px-3 border-b border-gray-200 dark:border-gray-600">URL cifrada (BD)</th>
                                        <th class="text-left py-2 px-3 border-b border-gray-200 dark:border-gray-600">URL descifrada</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($filasPendientes as $fila)
                                        <tr class="border-b border-gray-100 dark:border-gray-700 align-top">
                                            <td class="py-2 px-3">{{ $fila['id'] ?? '-' }}</td>
                                            <td class="py-2 px-3">{{ $fila['visitada'] ?? '-' }}</td>
                                            <td class="py-2 px-3">
                                                @if (!empty($fila['es_url_neo']))
                                                    <span class="text-green-700 dark:text-green-400 font-medium">Sí</span>
                                                @else
                                                    <span class="text-gray-600 dark:text-gray-300">No</span>
                                                @endif
                                            </td>
                                            <td class="py-2 px-3">
                                                <code class="text-xs break-all">{{ $fila['url_cifrada'] ?? '' }}</code>
                                            </td>
                                            <td class="py-2 px-3 break-all">
                                                @if (!empty($fila['url_descifrada']))
                                                    <a href="{{ $fila['url_descifrada'] }}" target="_blank" rel="noopener" class="text-blue-600 dark:text-blue-400 hover:underline">{{ \Illuminate\Support\Str::limit($fila['url_descifrada'], 120) }}</a>
                                                @else
                                                    <span class="text-gray-500 dark:text-gray-400">—</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            @endif
        </div>

        @if (!empty($resultado))
            @php
                $logEjecucion = is_array($resultado['log'] ?? null) ? $resultado['log'] : [];
                $estadoEjecucion = $logEjecucion['estado'] ?? null;
                $pasoActual = $logEjecucion['paso_actual'] ?? null;
                $pasosEjecucion = is_array($logEjecucion['pasos'] ?? null) ? $logEjecucion['pasos'] : [];
                $total_filas_rama_neo = $resultado['total_filas_rama_neo'] ?? $resultado['total_filas_neo_comparador'] ?? 0;
                $total_filas_categoria_tienda_prueba = $resultado['total_filas_categoria_tienda_prueba'] ?? 0;
                $resultados = $resultado['resultados'] ?? [];
                $resultados_rama_categoria_tienda = $resultado['resultados_categoria_tienda'] ?? [];
                $resultados_rama_categoria_tienda_detalle = $resultado['resultados_categoria_tienda_detalle'] ?? [];
                $resultados_categoria = $resultado['resultados_categoria'] ?? [];
                $filas_sin_tienda_aviso = $resultado['filas_sin_tienda_aviso'] ?? 0;
                $soloRedireccion = !empty($resultado['solo_redireccion']) && $resultado['solo_redireccion'];
                $respSoloRed = is_array($resultado['respuesta_solo_redireccion'] ?? null) ? $resultado['respuesta_solo_redireccion'] : [];
            @endphp

            @if ($soloRedireccion)
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 border-l-4 border-purple-500">
                    <h3 class="font-semibold text-lg mb-2">Solo redirección (URL relocator)</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">
                        Se ha enviado solo una petición POST al VPS <code class="text-xs">{{ $respSoloRed['endpoint'] ?? '—' }}</code>
                        con timeout <strong>{{ $respSoloRed['timeout_segundos'] ?? '—' }}s</strong>. Sin sacar-ofertas ni simulación de guardado en neo.
                    </p>
                    <p class="text-sm mb-2"><span class="text-gray-500">URL enviada:</span> <a href="{{ $respSoloRed['request_payload']['url'] ?? '#' }}" target="_blank" rel="noopener" class="text-blue-600 dark:text-blue-400 break-all">{{ $respSoloRed['request_payload']['url'] ?? '—' }}</a></p>
                    <p class="text-sm mb-2"><span class="text-gray-500">HTTP status:</span> <strong>{{ $respSoloRed['http_status'] ?? '-' }}</strong></p>
                    @if (!empty($respSoloRed['error_excepcion']))
                        <p class="text-sm text-red-600 dark:text-red-400 mb-2"><span class="text-gray-500">Excepción cliente HTTP:</span> {{ $respSoloRed['error_excepcion'] }}</p>
                    @endif
                    @if (!empty($respSoloRed['body_json']))
                        <h4 class="font-semibold text-sm mt-4 mb-1">Respuesta JSON (parseada)</h4>
                        <pre class="p-3 bg-gray-100 dark:bg-gray-900 rounded text-xs overflow-x-auto max-h-96 overflow-y-auto whitespace-pre-wrap border border-gray-200 dark:border-gray-700">{{ json_encode($respSoloRed['body_json'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</pre>
                    @endif
                    <h4 class="font-semibold text-sm mt-4 mb-1">Cuerpo bruto completo</h4>
                    <pre class="p-3 bg-gray-100 dark:bg-gray-900 rounded text-xs overflow-x-auto max-h-96 overflow-y-auto whitespace-pre-wrap break-words border border-gray-200 dark:border-gray-700">{{ $respSoloRed['body_raw'] ?? '(vacío)' }}</pre>
                </div>
            @endif

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 border-l-4 {{ $estadoEjecucion === 'error' ? 'border-red-500' : ($estadoEjecucion === 'ok' ? 'border-green-500' : 'border-amber-500') }}">
                <h3 class="font-semibold text-base mb-2">Seguimiento de la ejecución</h3>
                <p class="text-sm text-gray-700 dark:text-gray-300">
                    <span class="text-gray-500">Estado:</span>
                    <strong>{{ $estadoEjecucion ?? 'desconocido' }}</strong>
                    @if ($pasoActual)
                        — <span class="text-gray-500">Paso actual:</span> <strong>{{ $pasoActual }}</strong>
                    @endif
                    — <span class="text-gray-500">Guardados simulados:</span> <strong>{{ $resultado['totales']['total_guardado'] ?? 0 }}</strong>
                    — <span class="text-gray-500">Errores simulados:</span> <strong>{{ $resultado['totales']['total_errores'] ?? 0 }}</strong>
                </p>

                @if (!empty($pasosEjecucion))
                    <details class="mt-3" open>
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

            @unless ($soloRedireccion)
            <p class="text-gray-600 dark:text-gray-400">
                Rama Neo (VPS): <strong>{{ $total_filas_rama_neo }}</strong>
                — rama categoría/tienda: <strong>{{ $total_filas_categoria_tienda_prueba }}</strong>
            </p>
            @endunless

            {{-- Rama categoría/tienda (mismo bloque que cron_neo_objetivos_resultado) --}}
            @if (!$soloRedireccion && isset($total_filas_categoria_tienda_prueba) && (int) $total_filas_categoria_tienda_prueba > 0)
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 border-l-4 border-amber-500">
                    <h2 class="font-semibold text-lg mb-2">Rama categoría/tienda</h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Prueba con URL de categoría/tienda: <strong>{{ $total_filas_categoria_tienda_prueba }}</strong>
                        @if (isset($filas_sin_tienda_aviso) && (int) $filas_sin_tienda_aviso > 0)
                            — Sin tienda detectada (aviso creado): <strong>{{ $filas_sin_tienda_aviso }}</strong>
                        @endif
                    </p>

                    @if (!empty($resultados_rama_categoria_tienda_detalle))
                        <h3 class="font-semibold text-base mt-4 mb-2">Detalle por fila (categoría/tienda)</h3>
                        <div class="space-y-3">
                            @foreach ($resultados_rama_categoria_tienda_detalle as $idx => $rd)
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

                    @if (!empty($resultados_rama_categoria_tienda))
                        <h3 class="font-semibold text-base mt-4 mb-2">Peticiones categoría/tienda</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">Se solicitó el contenido de la URL de categoría/sitemap y se extrajeron las URLs de productos con el método de la tienda.</p>
                        <div class="space-y-6">
                            @foreach ($resultados_rama_categoria_tienda as $idxNoI => $rno)
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
                                            @if (!empty($pet['vps_log']))
                                                <details class="mt-2">
                                                    <summary class="inline-block cursor-pointer select-none px-3 py-1.5 text-sm font-medium rounded-md bg-purple-600 text-white hover:bg-purple-700 dark:bg-purple-700 dark:hover:bg-purple-600">
                                                        Ver log VPS
                                                    </summary>
                                                    <pre class="mt-2 p-3 bg-gray-100 dark:bg-gray-900 rounded text-xs overflow-x-auto max-h-96 overflow-y-auto whitespace-pre-wrap border border-gray-200 dark:border-gray-700">{{ json_encode($pet['vps_log'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</pre>
                                                </details>
                                            @elseif (($pet['api_utilizada'] ?? '') === 'vps_html')
                                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Log del VPS no incluido en esta respuesta.</p>
                                            @endif
                                            @if (!empty($pet['error']))
                                                <p class="text-sm text-red-600 dark:text-red-400"><span class="text-gray-500">Error:</span> {{ $pet['error'] }}</p>
                                            @endif
                                            @if (isset($pet['siguiente_url']) && $pet['siguiente_url'] !== null && $pet['siguiente_url'] !== '')
                                                <p class="text-sm text-gray-600 dark:text-gray-400"><span class="text-gray-500">Siguiente página:</span> <a href="{{ $pet['siguiente_url'] }}" target="_blank" rel="noopener" class="text-blue-600 dark:text-blue-400 break-all hover:underline">{{ \Illuminate\Support\Str::limit($pet['siguiente_url'], 80) }}</a></p>
                                            @endif
                                            @if (!empty($pet['urls_extraidas']))
                                                <div class="mt-2">
                                                    <span class="text-gray-500 text-sm block mb-1">URLs de productos extraídas ({{ count($pet['urls_extraidas']) }}):</span>
                                                    <ul class="list-disc list-inside space-y-1 text-sm max-h-60 overflow-y-auto">
                                                        @foreach ($pet['urls_extraidas'] as $u)
                                                            <li class="break-all">
                                                                <a href="{{ $u }}" target="_blank" rel="noopener" class="text-blue-600 dark:text-blue-400 hover:underline">{{ \Illuminate\Support\Str::limit($u, 80) }}</a>
                                                            </li>
                                                        @endforeach
                                                    </ul>
                                                </div>
                                            @elseif (isset($pet['urls_extraidas']) && empty($pet['error']))
                                                <p class="text-sm text-gray-500">No se encontraron URLs de productos en esta petición.</p>
                                            @endif
                                        </div>
                                    @endforeach
                                    @if (!empty($rno['redirecciones']))
                                        @php
                                            $reds = $rno['redirecciones'];
                                            $nGuardados = count(array_filter($reds, fn($red) => empty($red['skipped']) && empty($red['error'])));
                                            $nOmitidos = count(array_filter($reds, fn($red) => !empty($red['skipped'])));
                                            $nErrores = count(array_filter($reds, fn($red) => !empty($red['error'])));
                                        @endphp
                                        <div class="mt-4 pt-4 border-t border-amber-200 dark:border-amber-800">
                                            <h3 class="font-semibold text-sm mb-2">Guardado en neo (categoría) — simulado</h3>
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
                                                                    <p class="mb-1 break-all"><a href="{{ $red['url_final'] }}" target="_blank" rel="noopener" class="text-blue-600 dark:text-blue-400 hover:underline">{{ \Illuminate\Support\Str::limit($red['url_final'], 80) }}</a></p>
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
                                                                                    {{ $paso['texto'] ?? '' }}: <span class="break-all">{{ \Illuminate\Support\Str::limit($paso['valor'], 60) }}</span>
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
                                                <a href="{{ $rc['url'] ?? '#' }}" target="_blank" rel="noopener" class="text-blue-600 dark:text-blue-400 hover:underline">{{ \Illuminate\Support\Str::limit($rc['url'] ?? '', 60) }}</a>
                                            </td>
                                            <td class="py-2 px-3">{{ $rc['tienda_nombre'] ?? $rc['tienda_id'] ?? '-' }}</td>
                                            <td class="py-2 px-3"><span class="font-medium">{{ $rc['tipo_listado'] ?? '-' }}</span></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-sm text-gray-500 dark:text-gray-500 mt-2">Ninguna fila con tienda y tipo de listado (sitemap/paginación/mostrar_más) en esta prueba.</p>
                    @endif
                </div>
            @endif

            {{-- Rama Neo (VPS) --}}
            @if (!$soloRedireccion && !empty($resultados))
                @foreach ($resultados as $idx => $r)
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 space-y-2">
                        <h2 class="font-semibold text-lg">Petición {{ $idx + 1 }}</h2>
                        <p class="text-sm"><span class="text-gray-500">URL:</span> <a href="{{ $r['url'] }}" target="_blank" rel="noopener" class="text-blue-600 dark:text-blue-400 break-all">{{ $r['url'] }}</a></p>
                        <p class="text-sm"><span class="text-gray-500">HTTP status:</span> {{ $r['http_status'] ?? '-' }}</p>
                        @if (!empty($r['vps_log']))
                            <details class="mt-2">
                                <summary class="inline-block cursor-pointer select-none px-3 py-1.5 text-sm font-medium rounded-md bg-purple-600 text-white hover:bg-purple-700 dark:bg-purple-700 dark:hover:bg-purple-600">
                                    Ver log VPS (sacar-ofertas-idea)
                                </summary>
                                <pre class="mt-2 p-3 bg-gray-100 dark:bg-gray-900 rounded text-xs overflow-x-auto max-h-96 overflow-y-auto whitespace-pre-wrap border border-gray-200 dark:border-gray-700">{{ json_encode($r['vps_log'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</pre>
                            </details>
                        @endif
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
                                            <p class="text-xs text-amber-700 dark:text-amber-300 mt-2">Esta prueba usa transacción con rollback: no se persiste en base de datos; el resultado refleja lo que haría el cron.</p>
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
                                <p class="text-gray-500 text-sm italic">No disponible.</p>
                            @endif
                        </div>
                    </div>
                @endforeach
            @elseif (!$soloRedireccion && (int) $total_filas_rama_neo > 0)
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                    <p class="text-gray-600 dark:text-gray-400">No se pudo montar el resultado de la rama Neo (revisa el log de errores arriba).</p>
                </div>
            @endif
        @endif

        </div>
    </div>

    <script>
        function actualizarRequisitoTienda() {
            const inputUrl = document.getElementById('url');
            const selectTienda = document.getElementById('tienda_id');
            if (!inputUrl || !selectTienda) {
                return;
            }

            const valorUrl = (inputUrl.value || '').toLowerCase();
            const marcaRamaNeoSubcadena = typeof atob !== 'undefined' ? atob('aWRlYWxv') : '';
            const esRamaNeo = marcaRamaNeoSubcadena !== '' && valorUrl.includes(marcaRamaNeoSubcadena);
            selectTienda.required = !esRamaNeo;
        }

        document.addEventListener('DOMContentLoaded', function () {
            const inputUrl = document.getElementById('url');
            if (inputUrl) {
                inputUrl.addEventListener('input', actualizarRequisitoTienda);
                inputUrl.addEventListener('change', actualizarRequisitoTienda);
            }
            actualizarRequisitoTienda();
        });
    </script>
</x-app-layout>
