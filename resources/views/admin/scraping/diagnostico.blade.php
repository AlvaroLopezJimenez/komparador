<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.dashboard') }}">
                <h2 class="font-semibold text-xl text-white leading-tight">Inicio -></h2>
            </a>
            <h2 class="font-semibold text-xl text-white leading-tight">Diagnóstico Scraping</h2>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto py-10 px-4 space-y-8 bg-gray-50 dark:bg-gray-900 rounded-lg shadow-md">
        
        <!-- Recomendaciones -->
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6 border border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-200 mb-4">💡 Recomendaciones</h3>
            
            <div class="space-y-4">
                @if($ofertasElegibles->count() == 0)
                    <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-700 rounded-lg p-4">
                        <h4 class="font-semibold text-yellow-800 dark:text-yellow-200">✅ Todas las ofertas están actualizadas.</h4>
                    </div>
                @endif

                @if(count($controladoresTiendas) == 0)
                    <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700 rounded-lg p-4">
                        <h4 class="font-semibold text-red-800 dark:text-red-200">❌ Falta controladores de tiendas</h4>
                        <p class="text-red-700 dark:text-red-300 text-sm mt-1">
                            No hay controladores de tiendas disponibles. El scraper no podrá procesar ninguna oferta.
                        </p>
                    </div>
                @endif

                @if(count($limitacionesAPI['tiendas_sin_api']) > 0)
                    <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700 rounded-lg p-4">
                        <h4 class="font-semibold text-yellow-800 dark:text-yellow-200">⚠️ Tiendas sin API configurada</h4>
                        <p class="text-yellow-700 dark:text-yellow-300 text-sm mt-1">
                            Las siguientes tiendas no tienen API configurada: {{ implode(', ', $limitacionesAPI['tiendas_sin_api']) }}. 
                            Configura una API en el formulario de cada tienda.
                        </p>
                    </div>
                @endif

                @if($ofertasMostrar == 0)
                    <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700 rounded-lg p-4">
                        <h4 class="font-semibold text-red-800 dark:text-red-200">❌ No hay ofertas activas</h4>
                        <p class="text-red-700 dark:text-red-300 text-sm mt-1">
                            Todas las ofertas están marcadas como ocultas (mostrar='no'). El scraper no procesará ofertas ocultas.
                        </p>
                    </div>
                @endif

                @if($ofertasElegibles->count() > 0 && count($controladoresTiendas) > 0)
                    <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-700 rounded-lg p-4">
                        <h4 class="font-semibold text-green-800 dark:text-green-200">✅ Sistema listo</h4>
                        <p class="text-green-700 dark:text-green-300 text-sm mt-1">
                            El sistema de scraping está configurado correctamente y hay ofertas disponibles para procesar.
                        </p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Estadísticas Generales -->
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6 border border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-200 mb-4">📊 Estadísticas Generales</h3>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-4">
                <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg">
                    <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $totalOfertas }}</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Total Ofertas</div>
                </div>
                <div class="bg-green-50 dark:bg-green-900/20 p-4 rounded-lg">
                    <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $ofertasMostrar }}</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Ofertas Activas</div>
                </div>
                <div class="bg-orange-50 dark:bg-orange-900/20 p-4 rounded-lg">
                    <div class="text-2xl font-bold text-orange-600 dark:text-orange-400">{{ $ofertasScrapeando['total_ofertas'] }}</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Ofertas Scrapeando</div>
                </div>
                <div class="bg-red-50 dark:bg-red-900/20 p-4 rounded-lg">
                    <div class="text-2xl font-bold text-red-600 dark:text-red-400">{{ $ofertasOcultas }}</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Ofertas Ocultas</div>
                </div>
                <div class="bg-purple-50 dark:bg-purple-900/20 p-4 rounded-lg">
                    <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ number_format($ejecucionesPorDia['total_ejecuciones_por_dia'] + $ejecucionesPorDia['peticiones_ofertas_baratas_por_dia'], 1) }}</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">
                        Ejecuciones/Día
                        <span class="relative group">
                            <svg class="w-3 h-3 inline text-gray-400 cursor-help" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <div class="absolute bottom-full left-0 mb-2 w-80 p-3 bg-gray-900 text-white text-sm rounded-lg shadow-lg opacity-0 group-hover:opacity-100 transition-opacity duration-200 z-10">
                                <p>Total de todas las APIs ({{ number_format($ejecucionesPorDia['total_ejecuciones_por_dia'], 1) }}) + {{ number_format($ejecucionesPorDia['peticiones_ofertas_baratas_por_dia'], 1) }} peticiones por la oferta más barata de cada producto ({{ $ejecucionesPorDia['productos_unicos'] }} productos) cada {{ $ejecucionesPorDia['intervalo_horas_ofertas_baratas'] }} horas. Las ejecuciones solo se realizan de 8 AM a 11 PM ({{ $ejecucionesPorDia['horas_activas_diarias'] }} horas activas).</p>
                            </div>
                        </span>
                    </div>
                </div>
                <div class="bg-indigo-50 dark:bg-indigo-900/20 p-4 rounded-lg">
                    <div class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">{{ number_format(($ejecucionesPorDia['total_ejecuciones_por_dia'] + $ejecucionesPorDia['peticiones_ofertas_baratas_por_dia']) * $ejecucionesPorDia['dias_en_mes'], 0) }}</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">
                        Peticiones/Mes
                        <span class="relative group">
                            <svg class="w-3 h-3 inline text-gray-400 cursor-help" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <div class="absolute bottom-full left-0 mb-2 w-64 p-3 bg-gray-900 text-white text-sm rounded-lg shadow-lg opacity-0 group-hover:opacity-100 transition-opacity duration-200 z-10">
                                <p>Total de peticiones al mes: {{ number_format($ejecucionesPorDia['total_ejecuciones_por_dia'] + $ejecucionesPorDia['peticiones_ofertas_baratas_por_dia'], 1) }} ejecuciones/día × {{ $ejecucionesPorDia['dias_en_mes'] }} días del mes.</p>
                            </div>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Limitaciones de API -->
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6 border border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-200 mb-4">🔒 Limitaciones de API</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                Control de peticiones a APIs externas configuradas por tienda.
            </p>

            @php
                // 1) AGRUPAR por nombre base (antes de ';')
                $porApiOriginal = $limitacionesAPI['por_api'] ?? [];
                $porApiAgrupada = [];
                foreach ($porApiOriginal as $apiNombreCompleto => $datosApi) {
                    $base = explode(';', $apiNombreCompleto, 2)[0]; // nombre antes del ';'
                    if (!isset($porApiAgrupada[$base])) {
                        $porApiAgrupada[$base] = [
                            'peticiones_por_dia' => 0,
                            'peticiones_por_mes' => 0,
                            'tiendas' => [],
                        ];
                    }
                    $porApiAgrupada[$base]['peticiones_por_dia'] += ($datosApi['peticiones_por_dia'] ?? 0);
                    $porApiAgrupada[$base]['peticiones_por_mes'] += ($datosApi['peticiones_por_mes'] ?? 0);
                    if (!empty($datosApi['tiendas']) && is_array($datosApi['tiendas'])) {
                        // Unir tiendas evitando duplicados
                        $porApiAgrupada[$base]['tiendas'] = array_values(array_unique(array_merge($porApiAgrupada[$base]['tiendas'], $datosApi['tiendas'])));
                    }
                }
                // Calcular % del total usando peticiones_por_dia
                $totalDiaAgrupado = 0;
                foreach ($porApiAgrupada as $k => $v) { $totalDiaAgrupado += $v['peticiones_por_dia']; }
                foreach ($porApiAgrupada as $k => $v) {
                    $porApiAgrupada[$k]['porcentaje_total'] = $totalDiaAgrupado > 0 ? round(($v['peticiones_por_dia'] / $totalDiaAgrupado) * 100, 1) : 0;
                }

                // 2) PALETA de 5 colores por orden de aparición
                $paletaApi = [
                    ['bg' => 'from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20',   'border' => 'border-blue-200 dark:border-blue-700',   'text' => 'text-blue-800 dark:text-blue-200',   'text_light' => 'text-blue-700 dark:text-blue-300',   'icon_bg' => 'bg-blue-600',   'text_only' => 'text-blue-600 dark:text-blue-400'],
                    ['bg' => 'from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20','border' => 'border-green-200 dark:border-green-700','text' => 'text-green-800 dark:text-green-200','text_light' => 'text-green-700 dark:text-green-300','icon_bg' => 'bg-green-600', 'text_only' => 'text-green-600 dark:text-green-400'],
                    ['bg' => 'from-purple-50 to-purple-100 dark:from-purple-900/20 dark:to-purple-800/20','border' => 'border-purple-200 dark:border-purple-700','text' => 'text-purple-800 dark:text-purple-200','text_light' => 'text-purple-700 dark:text-purple-300','icon_bg' => 'bg-purple-600','text_only' => 'text-purple-600 dark:text-purple-400'],
                    ['bg' => 'from-amber-50 to-amber-100 dark:from-amber-900/20 dark:to-amber-800/20','border' => 'border-amber-200 dark:border-amber-700','text' => 'text-amber-800 dark:text-amber-200','text_light' => 'text-amber-700 dark:text-amber-300','icon_bg' => 'bg-amber-600','text_only' => 'text-amber-600 dark:text-amber-400'],
                    ['bg' => 'from-rose-50 to-rose-100 dark:from-rose-900/20 dark:to-rose-800/20',   'border' => 'border-rose-200 dark:border-rose-700',   'text' => 'text-rose-800 dark:text-rose-200',   'text_light' => 'text-rose-700 dark:text-rose-300',   'icon_bg' => 'bg-rose-600',   'text_only' => 'text-rose-600 dark:text-rose-400'],
                ];
                $colorDefecto = ['bg' => 'from-gray-50 to-gray-100 dark:from-gray-900/20 dark:to-gray-800/20', 'border' => 'border-gray-200 dark:border-gray-700', 'text' => 'text-gray-800 dark:text-gray-200', 'text_light' => 'text-gray-700 dark:text-gray-300', 'icon_bg' => 'bg-gray-600', 'text_only' => 'text-gray-600 dark:text-gray-400'];

                // 3) Mapa baseApi -> paleta por orden
                $apiColorMap = [];
                $i = 0;
                foreach ($porApiAgrupada as $apiBase => $datosApi) {
                    $apiColorMap[$apiBase] = $paletaApi[$i % count($paletaApi)];
                    $i++;
                }
            @endphp
            
            @if(count($porApiAgrupada) > 0)
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    @foreach($porApiAgrupada as $apiBase => $datos)
                        @php
                            $color = $apiColorMap[$apiBase] ?? $colorDefecto;
                        @endphp
                        
                        <div class="bg-gradient-to-br {{ $color['bg'] }} p-6 rounded-lg border {{ $color['border'] }}">
                            <div class="flex items-center gap-3 mb-4">
                                <div class="w-8 h-8 {{ $color['icon_bg'] }} rounded-lg flex items-center justify-center">
                                    <span class="text-white font-bold text-sm">{{ strtoupper(substr($apiBase, 0, 2)) }}</span>
                                </div>
                                <h4 class="text-lg font-semibold {{ $color['text'] }}">{{ $apiBase }}</h4>
                            </div>
                            
                            <div class="space-y-3">
                                <div class="flex justify-between items-center">
                                    <span class="text-sm {{ $color['text_light'] }}">Peticiones/día:</span>
                                    <span class="font-semibold {{ $color['text'] }}">{{ number_format($datos['peticiones_por_dia'], 1) }} peticiones</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-sm {{ $color['text_light'] }}">Peticiones/mes:</span>
                                    <span class="font-semibold {{ $color['text'] }}">{{ number_format($datos['peticiones_por_mes'], 0) }} peticiones</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-sm {{ $color['text_light'] }}">% del total:</span>
                                    <span class="font-semibold {{ $color['text'] }}">{{ $datos['porcentaje_total'] }}%</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-sm {{ $color['text_light'] }}">Tiendas:</span>
                                    <span class="font-semibold {{ $color['text'] }}">{{ count($datos['tiendas']) }} tiendas</span>
                                </div>
                            </div>
                            
                            <div class="mt-4 p-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-700 rounded-lg">
                                <p class="text-sm text-green-700 dark:text-green-300 font-medium">
                                    ✅ API configurada y funcionando.
                                </p>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-8">
                    <div class="text-gray-500 dark:text-gray-400 text-lg">📝 No hay APIs configuradas</div>
                    <p class="text-sm text-gray-400 dark:text-gray-500 mt-2">
                        Configura las APIs en el formulario de cada tienda para ver las estadísticas aquí.
                    </p>
                </div>
            @endif
            
            <!-- Desglose por tienda -->
            <div class="mt-6">
                <h5 class="text-md font-semibold text-gray-700 dark:text-gray-200 mb-3">📊 Desglose por Tienda ({{ $ejecucionesPorDia['total_ofertas_activas'] }} ofertas activas)</h5>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                    Cálculo basado en la frecuencia de actualización de cada oferta activa. Total: {{ number_format($ejecucionesPorDia['total_ejecuciones_por_dia'], 1) }} ejecuciones por día.
                </p>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Tienda</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Ofertas</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Peticiones/Día</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Peticiones/Mes</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">% del Total</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">API Configurada</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Controlador</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Scrapeando</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Mostrando</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($limitacionesAPI['por_tienda'] as $tienda => $datos)
                                @php
                                    $apiTienda = $datos['api'] ?? null;
                                    $apiBase = $apiTienda ? explode(';', $apiTienda, 2)[0] : null;

                                    // Color según el grupo base
                                    if ($apiBase && isset($apiColorMap[$apiBase])) {
                                        $claseTextoApi = $apiColorMap[$apiBase]['text_only'];
                                    } else {
                                        $claseTextoApi = $apiTienda ? 'text-gray-600 dark:text-gray-400' : 'text-red-600 dark:text-red-400';
                                    }
                                    
                                    // Controlador disponible
                                    $controladorExiste = false;
                                    if (isset($ejecucionesPorDia['por_tienda'][$tienda])) {
                                        $controladorExiste = $ejecucionesPorDia['por_tienda'][$tienda]['controlador_existe'];
                                    }
                                    
                                    // % del total
                                    $porcentaje = $ejecucionesPorDia['total_ejecuciones_por_dia'] > 0 
                                        ? ($datos['peticiones_por_dia'] / $ejecucionesPorDia['total_ejecuciones_por_dia']) * 100 
                                        : 0;
                                @endphp
                                <tr>
                                    <td class="px-4 py-2 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                                        {{ $tienda }}
                                    </td>
                                    <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                        {{ $datos['ofertas_activas'] ?? 0 }}/{{ $datos['ofertas_totales'] ?? 0 }}
                                    </td>
                                    <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                        <span class="font-semibold">{{ number_format($datos['peticiones_por_dia'], 1) }}</span>
                                    </td>
                                    <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                        <span class="font-semibold text-blue-600 dark:text-blue-400">{{ number_format($datos['peticiones_por_mes'], 0) }}</span>
                                    </td>
                                    <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                        <span class="text-purple-600 dark:text-purple-400 font-semibold">{{ number_format($porcentaje, 1) }}%</span>
                                    </td>
                                    <td class="px-4 py-2 whitespace-nowrap text-sm font-semibold {{ $claseTextoApi }}">
                                        @if($apiTienda)
                                            {{ $apiTienda }} {{-- Mostrar nombre completo como pides --}}
                                        @else
                                            ❌ Sin configurar
                                        @endif
                                    </td>
                                    <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                        @if($controladorExiste)
                                            <span class="text-green-600 dark:text-green-400 font-semibold">✅ Disponible</span>
                                        @else
                                            <span class="text-red-600 dark:text-red-400 font-semibold">❌ Faltante</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                        @if(isset($datos['scrapear']) && $datos['scrapear'] === 'si')
                                            <span class="text-green-600 dark:text-green-400 font-semibold">✅</span>
                                        @else
                                            <span class="text-red-600 dark:text-red-400 font-semibold">❌</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                        @if(isset($datos['mostrar_tienda']) && $datos['mostrar_tienda'] === 'si')
                                            <span class="text-green-600 dark:text-green-400 font-semibold">✅</span>
                                        @else
                                            <span class="text-red-600 dark:text-red-400 font-semibold">❌</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>


        <!-- Ofertas con Errores y Éxitos -->
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6 border border-gray-200 dark:border-gray-700">
             <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-200 mb-4">
                 📊 Ofertas con Errores y Éxitos de Scraping
             </h3>
             <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                 Estado de las ofertas procesadas por el scraper. Se muestra el último estado de cada oferta por día.
             </p>
             
             <!-- Filtros -->
             <div class="mb-6 space-y-4">
                 <div class="flex flex-wrap gap-4 items-center">
                     <!-- Selector de fecha -->
                     <div class="flex items-center space-x-2">
                         <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Fecha:</label>
                         <input type="date" id="fechaSelector" class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                     </div>
                     
                     <!-- Checkboxes de filtro -->
                     <div class="flex items-center space-x-4">
                         <label class="flex items-center space-x-2">
                             <input type="checkbox" id="mostrarExitos" checked class="rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500">
                             <span class="text-sm font-medium text-gray-700 dark:text-gray-300">✅ Éxitos</span>
                         </label>
                         <label class="flex items-center space-x-2">
                             <input type="checkbox" id="mostrarErrores" checked class="rounded border-gray-300 dark:border-gray-600 text-red-600 focus:ring-red-500">
                             <span class="text-sm font-medium text-gray-700 dark:text-gray-300">❌ Errores</span>
                         </label>
                     </div>
                    
                     <!-- Elementos por página -->
                     <div class="flex items-center space-x-2">
                         <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Por página:</label>
                         <select id="perPageSelector" class="px-7 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                             <option value="10">10</option>
                             <option value="20" selected>20</option>
                             <option value="50">50</option>
                             <option value="100">100</option>
                         </select>
                     </div>
                     
                     <!-- Botón de actualizar -->
                     <button id="actualizarOfertas" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors">
                         🔄 Actualizar
                     </button>
                 </div>
                 
                 <!-- Estadísticas rápidas -->
                 <div class="flex flex-wrap gap-4 text-sm">
                     <div class="flex items-center space-x-2">
                         <span class="w-3 h-3 bg-green-500 rounded-full"></span>
                         <span class="text-gray-700 dark:text-gray-300">Éxitos: <span id="contadorExitos" class="font-semibold">0</span></span>
                     </div>
                     <div class="flex items-center space-x-2">
                         <span class="w-3 h-3 bg-red-500 rounded-full"></span>
                         <span class="text-gray-700 dark:text-gray-300">Errores: <span id="contadorErrores" class="font-semibold">0</span></span>
                     </div>
                     <div class="flex items-center space-x-2">
                         <span class="w-3 h-3 bg-blue-500 rounded-full"></span>
                         <span class="text-gray-700 dark:text-gray-300">Total: <span id="contadorTotal" class="font-semibold">0</span></span>
                     </div>
                 </div>
             </div>
             
             <!-- Tabla de resultados -->
             <div id="tablaOfertasContainer" class="overflow-x-auto">
                 <div id="loadingOfertas" class="text-center py-8 hidden">
                     <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                     <p class="mt-2 text-gray-600 dark:text-gray-400">Cargando ofertas...</p>
                 </div>
                 
                 <div id="tablaOfertas" class="hidden">
                     <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                         <thead class="bg-gray-50 dark:bg-gray-700">
                             <tr>
                                 <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">ID</th>
                                 <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Tienda</th>
                                 <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Estado</th>
                                 <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Precio Anterior</th>
                                 <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Precio Nuevo</th>
                                 <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Hora</th>
                                 <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Error</th>
                                 <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Acciones</th>
                             </tr>
                         </thead>
                         <tbody id="tablaOfertasBody" class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                             <!-- Los datos se cargarán dinámicamente -->
                         </tbody>
                     </table>
                     
                     <!-- Paginación -->
                     <div id="paginacionOfertas" class="mt-4 flex items-center justify-between">
                         <!-- La paginación se generará dinámicamente -->
                     </div>
                 </div>
                 
                 <div id="noOfertas" class="text-center py-8 hidden">
                     <div class="text-gray-500 dark:text-gray-400 text-lg">📝 No hay ofertas para mostrar</div>
                     <p class="text-sm text-gray-400 dark:text-gray-500 mt-2">
                         No se encontraron ofertas para la fecha y filtro seleccionados.
                     </p>
                 </div>
             </div>
         </div>

        <!-- Ofertas Elegibles para Scraping -->
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6 border border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-200 mb-4">
                🎯 Ofertas Elegibles para Scraping ({{ $ofertasElegibles->count() }})
            </h3>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                Ofertas que cumplen los criterios: mostrar='si', chollo_id IS NULL, y tiempo desde última actualización >= frecuencia_actualizar_precio_minutos
            </p>
            
            @if($ofertasElegibles->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">ID</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Producto</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Tienda</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Precio</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Última Actualización</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Frecuencia (min)</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($ofertasElegibles as $oferta)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">{{ $oferta->id }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                        {{ $oferta->producto->nombre ?? 'Sin producto' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                        {{ $oferta->tienda->nombre ?? 'Sin tienda' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                        {{ $oferta->precio_total }}€
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                        {{ $oferta->updated_at->diffForHumans() }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                        {{ $oferta->frecuencia_actualizar_precio_minutos }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-8">
                    <div class="text-gray-500 dark:text-gray-400 text-lg">❌ No hay ofertas elegibles para scraping</div>
                    <p class="text-sm text-gray-400 dark:text-gray-500 mt-2">
                        Esto puede deberse a que todas las ofertas han sido actualizadas recientemente o no cumplen los criterios de frecuencia.
                    </p>
                </div>
            @endif
        </div>

    </div>
     
     <!-- JavaScript para el panel de ofertas con errores y éxitos -->
     <script>
         document.addEventListener('DOMContentLoaded', function() {
              let currentPage = 1;
              let currentMostrarExitos = true;
              let currentMostrarErrores = true;
              let currentPerPage = 20;
              let currentFecha = new Date().toISOString().split('T')[0];
              let fechasDisponibles = [];
              
              const fechaSelector = document.getElementById('fechaSelector');
              const mostrarExitosCheckbox = document.getElementById('mostrarExitos');
              const mostrarErroresCheckbox = document.getElementById('mostrarErrores');
              const perPageSelector = document.getElementById('perPageSelector');
              const actualizarBtn = document.getElementById('actualizarOfertas');
              const loadingDiv = document.getElementById('loadingOfertas');
              const tablaDiv = document.getElementById('tablaOfertas');
              const noOfertasDiv = document.getElementById('noOfertas');
              const tablaBody = document.getElementById('tablaOfertasBody');
              const paginacionDiv = document.getElementById('paginacionOfertas');
              
              fechaSelector.value = currentFecha;
              cargarOfertas();
              
              actualizarBtn.addEventListener('click', cargarOfertas);
              fechaSelector.addEventListener('change', function() {
                  const fechaSeleccionada = this.value;
                  if (fechasDisponibles.includes(fechaSeleccionada)) {
                      currentFecha = fechaSeleccionada;
                      currentPage = 1;
                      cargarOfertas();
                  } else {
                      alert('No hay ejecuciones disponibles para esta fecha. Selecciona otra fecha.');
                      this.value = currentFecha;
                  }
              });
              mostrarExitosCheckbox.addEventListener('change', function() {
                  currentMostrarExitos = this.checked;
                  currentPage = 1;
                  cargarOfertas();
              });
              mostrarErroresCheckbox.addEventListener('change', function() {
                  currentMostrarErrores = this.checked;
                  currentPage = 1;
                  cargarOfertas();
              });
              perPageSelector.addEventListener('change', function() {
                 currentPerPage = parseInt(this.value);
                 currentPage = 1;
                 cargarOfertas();
              });
             
              function cargarOfertas() {
                 mostrarLoading();
                 
                 const params = new URLSearchParams({
                      fecha: currentFecha,
                      mostrar_exitos: currentMostrarExitos,
                      mostrar_errores: currentMostrarErrores,
                      perPage: currentPerPage,
                      page: currentPage
                  });
                 
                 fetch(`{{ route('admin.scraping.ofertas-errores-exitos') }}?${params}`)
                     .then(response => response.json())
                     .then(data => {
                         ocultarLoading();
                         
                         if (data.ofertas.length === 0) {
                             mostrarNoOfertas();
                             return;
                         }
                         
                         mostrarTabla();
                         renderizarTabla(data.ofertas);
                         renderizarPaginacion(data);
                         actualizarContadores(data.estadisticas_dia);
                         
                         fechasDisponibles = data.fechas_disponibles;
                         
                         mostrarExitosCheckbox.checked = data.mostrar_exitos;
                         mostrarErroresCheckbox.checked = data.mostrar_errores;
                     })
                     .catch(error => {
                         console.error('Error al cargar ofertas:', error);
                         ocultarLoading();
                         mostrarError('Error al cargar las ofertas. Inténtalo de nuevo.');
                     });
              }
             
              function mostrarLoading() {
                 loadingDiv.classList.remove('hidden');
                 tablaDiv.classList.add('hidden');
                 noOfertasDiv.classList.add('hidden');
              }
             
              function ocultarLoading() {
                 loadingDiv.classList.add('hidden');
              }
             
              function mostrarTabla() {
                 tablaDiv.classList.remove('hidden');
                 noOfertasDiv.classList.add('hidden');
              }
             
              function mostrarNoOfertas() {
                 tablaDiv.classList.add('hidden');
                 noOfertasDiv.classList.remove('hidden');
              }
             
              function renderizarTabla(ofertas) {
                 tablaBody.innerHTML = '';
                 
                 ofertas.forEach(oferta => {
                     const row = document.createElement('tr');
                     
                     const estadoClass = oferta.success ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400';
                     const estadoText = oferta.success ? '✅ Éxito' : '❌ Error';
                     const precioNuevo = oferta.precio_nuevo !== null ? `${oferta.precio_nuevo}€` : '-';
                     const precioAnterior = oferta.precio_anterior !== null ? `${oferta.precio_anterior}€` : '-';
                     const hora = new Date(oferta.hora).toLocaleTimeString('es-ES', { 
                         hour: '2-digit', 
                         minute: '2-digit',
                         second: '2-digit'
                     });
                     
                     row.innerHTML = `
                         <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                             ${oferta.oferta_id}
                         </td>
                         <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                             ${oferta.tienda_nombre}
                         </td>
                         <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold ${estadoClass}">
                             ${estadoText}
                         </td>
                         <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                             ${precioAnterior}
                         </td>
                         <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                             ${precioNuevo}
                         </td>
                         <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                             ${hora}
                         </td>
                         <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">
                             ${oferta.error ? `<span class="text-red-600 dark:text-red-400">${oferta.error}</span>` : '-'}
                         </td>
                         <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                             <div class="flex space-x-2">
                                 <a href="${oferta.url_oferta}" target="_blank" class="inline-flex items-center px-2 py-1 border border-transparent text-xs font-medium rounded text-blue-700 bg-blue-100 hover:bg-blue-200 dark:text-blue-300 dark:bg-blue-900/20 dark:hover:bg-blue-900/40 transition-colors">
                                     🔗 Ir
                                 </a>
                                 <a href="/panel-privado/ofertas/${oferta.oferta_id}/edit" class="inline-flex items-center px-2 py-1 border border-transparent text-xs font-medium rounded text-green-700 bg-green-100 hover:bg-green-200 dark:text-green-300 dark:bg-green-900/20 dark:hover:bg-green-900/40 transition-colors">
                                     ✏️ Editar
                                 </a>
                             </div>
                         </td>
                     `;
                     
                     tablaBody.appendChild(row);
                 });
              }
             
              function renderizarPaginacion(data) {
                 if (data.last_page <= 1) {
                     paginacionDiv.innerHTML = '';
                     return;
                 }
                 
                 let paginacionHTML = `
                     <div class="flex items-center space-x-2 text-sm text-gray-700 dark:text-gray-300">
                         <span>Mostrando ${((data.current_page - 1) * data.per_page) + 1} a ${Math.min(data.current_page * data.per_page, data.total)} de ${data.total} resultados</span>
                     </div>
                     <div class="flex items-center space-x-2">
                 `;
                 
                 if (data.current_page > 1) {
                     paginacionHTML += `
                         <button onclick="cambiarPagina(${data.current_page - 1})" class="px-3 py-1 text-sm bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded hover:bg-gray-300 dark:hover:bg-gray-600">
                             Anterior
                         </button>
                     `;
                 }
                 
                 const startPage = Math.max(1, data.current_page - 2);
                 const endPage = Math.min(data.last_page, data.current_page + 2);
                 
                 for (let i = startPage; i <= endPage; i++) {
                     const activeClass = i === data.current_page ? 'bg-blue-600 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-gray-600';
                     paginacionHTML += `
                         <button onclick="cambiarPagina(${i})" class="px-3 py-1 text-sm rounded ${activeClass}">
                             ${i}
                         </button>
                     `;
                 }
                 
                 if (data.current_page < data.last_page) {
                     paginacionHTML += `
                         <button onclick="cambiarPagina(${data.current_page + 1})" class="px-3 py-1 text-sm bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded hover:bg-gray-300 dark:hover:bg-gray-600">
                             Siguiente
                         </button>
                     `;
                 }
                 
                 paginacionHTML += '</div>';
                 paginacionDiv.innerHTML = paginacionHTML;
              }
             
              function cambiarPagina(page) {
                 currentPage = page;
                 cargarOfertas();
              }
             
              function actualizarContadores(estadisticas) {
                  document.getElementById('contadorExitos').textContent = estadisticas.exitos;
                  document.getElementById('contadorErrores').textContent = estadisticas.errores;
                  document.getElementById('contadorTotal').textContent = estadisticas.total;
              }
             
              function mostrarError(mensaje) {
                 const notification = document.createElement('div');
                 notification.className = 'fixed top-4 right-4 bg-red-600 text-white px-6 py-3 rounded-lg shadow-lg z-50';
                 notification.textContent = mensaje;
                 document.body.appendChild(notification);
                 setTimeout(() => { notification.remove(); }, 5000);
              }
             
              window.cambiarPagina = cambiarPagina;
         });
     </script>
 </x-app-layout>
