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
        
        @php
            $hayRecomendaciones = $ofertasElegibles->count() === 0
                || count($controladoresTiendas) === 0
                || count($limitacionesAPI['tiendas_sin_api']) > 0
                || !empty($tiendasScrapingSuperaMostrar)
                || $tiendasMostrandoSinScraping->isNotEmpty()
                || $ofertasMostrar === 0;
        @endphp

        @if($hayRecomendaciones)
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
                            Las siguientes tiendas no tienen API por defecto configurada: {{ implode(', ', $limitacionesAPI['tiendas_sin_api']) }}.
                        </p>
                    </div>
                @endif

                @if(!empty($tiendasScrapingSuperaMostrar))
                    <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700 rounded-lg p-4">
                        <h4 class="font-semibold text-red-800 dark:text-red-200">❌ Categorías scrapeando por encima de las que se muestran</h4>
                        <p class="text-red-700 dark:text-red-300 text-sm mt-1">
                            Estas tiendas tienen más categorías con ofertas en scraping que en mostrar. Revisa la configuración por categoría en el formulario de cada tienda.
                        </p>
                        <ul class="mt-2 text-sm text-red-800 dark:text-red-200 space-y-1 max-h-40 overflow-y-auto">
                            @foreach($tiendasScrapingSuperaMostrar as $aviso)
                                <li>
                                    <a href="{{ route('admin.tiendas.edit', $aviso['tienda']) }}"
                                        class="font-medium underline hover:text-red-600 dark:hover:text-red-300">
                                        {{ $aviso['tienda']->nombre }}
                                    </a>
                                    <span> — cat.mos {{ $aviso['cat_mos']['si'] }}/{{ $aviso['cat_mos']['total'] }}, cat.scraping {{ $aviso['cat_scraping']['si'] }}/{{ $aviso['cat_scraping']['total'] }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if($tiendasMostrandoSinScraping->isNotEmpty())
                    <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700 rounded-lg p-4">
                        <h4 class="font-semibold text-red-800 dark:text-red-200">❌ Tiendas visibles sin scraping activo</h4>
                        <p class="text-red-700 dark:text-red-300 text-sm mt-1">
                            Estas tiendas tienen mostrar activado pero scraping desactivado a nivel de tienda. Sus ofertas no se actualizarán automáticamente.
                        </p>
                        <ul class="mt-2 text-sm text-red-800 dark:text-red-200 space-y-1 max-h-40 overflow-y-auto">
                            @foreach($tiendasMostrandoSinScraping as $tienda)
                                <li>
                                    <a href="{{ route('admin.tiendas.edit', $tienda) }}"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="font-medium underline hover:text-red-600 dark:hover:text-red-300">
                                        {{ $tienda->nombre }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
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
            </div>
        </div>
        @endif

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
                Control de peticiones a APIs externas. Se usa la API de cada categoría si está configurada; si no, la API por defecto de la tienda.
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
            <div class="mt-6" id="desglosePorTienda">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-3">
                    <h5 class="text-md font-semibold text-gray-700 dark:text-gray-200">
                        📊 Desglose por Tienda ({{ $ejecucionesPorDia['total_ofertas_activas'] }} ofertas activas)
                        <span id="desgloseTiendasVisibles" class="text-sm font-normal text-gray-500 dark:text-gray-400"></span>
                    </h5>
                    <div class="flex flex-row gap-2 items-center">
                        <button type="button" id="toggleFiltrosDesglose"
                            class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 hover:bg-gray-50 dark:hover:bg-gray-600 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 inline-flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                            </svg>
                            Filtros
                        </button>
                        <input type="search" id="buscadorTienda" placeholder="Buscar tienda..." class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 min-w-[180px]">
                    </div>
                </div>
                <div id="panelFiltrosDesglose" class="hidden mb-4 p-4 bg-gray-50 dark:bg-gray-700/40 rounded-lg border border-gray-200 dark:border-gray-600">
                    <div class="flex flex-wrap gap-x-6 gap-y-3 items-center">
                        <div class="flex flex-col gap-1">
                            <label for="filtroApiTienda" class="text-xs font-medium text-gray-600 dark:text-gray-300 uppercase">API</label>
                            <select id="filtroApiTienda" class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 min-w-[180px]">
                                <option value="">Todas las APIs</option>
                                @foreach($porApiAgrupada as $apiBase => $datos)
                                    <option value="{{ $apiBase }}">{{ $apiBase }}</option>
                                @endforeach
                                <option value="__sin_configurar__">Sin configurar</option>
                            </select>
                        </div>
                        <div class="relative desglose-filtro-dropdown">
                            <span class="text-xs font-medium text-gray-600 dark:text-gray-300 uppercase block mb-1" title="Controlador">Controlador</span>
                            <button type="button" id="filtroControladorBtn" class="desglose-filtro-dropdown-btn px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 hover:bg-gray-50 dark:hover:bg-gray-600 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 min-w-[120px] inline-flex items-center justify-between gap-2">
                                <span id="filtroControladorTexto">Sí</span>
                                <svg class="w-4 h-4 shrink-0 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                            </button>
                            <div id="filtroControladorPanel" class="desglose-filtro-dropdown-panel hidden absolute z-20 mt-1 w-full min-w-[140px] rounded-md border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 shadow-lg p-2 space-y-1">
                                <label class="flex items-center gap-2 px-2 py-1.5 rounded hover:bg-gray-50 dark:hover:bg-gray-600 cursor-pointer text-sm text-gray-700 dark:text-gray-200">
                                    <input type="checkbox" id="filtroControladorSi" checked class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
                                    <span>Sí</span>
                                </label>
                                <label class="flex items-center gap-2 px-2 py-1.5 rounded hover:bg-gray-50 dark:hover:bg-gray-600 cursor-pointer text-sm text-gray-700 dark:text-gray-200">
                                    <input type="checkbox" id="filtroControladorNo" class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
                                    <span>No</span>
                                </label>
                            </div>
                        </div>
                        <div class="relative desglose-filtro-dropdown">
                            <span class="text-xs font-medium text-gray-600 dark:text-gray-300 uppercase block mb-1">scraping</span>
                            <button type="button" id="filtroScrapingBtn" class="desglose-filtro-dropdown-btn px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 hover:bg-gray-50 dark:hover:bg-gray-600 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 min-w-[120px] inline-flex items-center justify-between gap-2">
                                <span id="filtroScrapingTexto">Sí</span>
                                <svg class="w-4 h-4 shrink-0 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                            </button>
                            <div id="filtroScrapingPanel" class="desglose-filtro-dropdown-panel hidden absolute z-20 mt-1 w-full min-w-[140px] rounded-md border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 shadow-lg p-2 space-y-1">
                                <label class="flex items-center gap-2 px-2 py-1.5 rounded hover:bg-gray-50 dark:hover:bg-gray-600 cursor-pointer text-sm text-gray-700 dark:text-gray-200">
                                    <input type="checkbox" id="filtroScrapingSi" checked class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
                                    <span>Sí</span>
                                </label>
                                <label class="flex items-center gap-2 px-2 py-1.5 rounded hover:bg-gray-50 dark:hover:bg-gray-600 cursor-pointer text-sm text-gray-700 dark:text-gray-200">
                                    <input type="checkbox" id="filtroScrapingNo" class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
                                    <span>No</span>
                                </label>
                            </div>
                        </div>
                        <div class="relative desglose-filtro-dropdown">
                            <span class="text-xs font-medium text-gray-600 dark:text-gray-300 uppercase block mb-1">mostrar</span>
                            <button type="button" id="filtroMostrarBtn" class="desglose-filtro-dropdown-btn px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 hover:bg-gray-50 dark:hover:bg-gray-600 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 min-w-[120px] inline-flex items-center justify-between gap-2">
                                <span id="filtroMostrarTexto">Sí</span>
                                <svg class="w-4 h-4 shrink-0 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                            </button>
                            <div id="filtroMostrarPanel" class="desglose-filtro-dropdown-panel hidden absolute z-20 mt-1 w-full min-w-[140px] rounded-md border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 shadow-lg p-2 space-y-1">
                                <label class="flex items-center gap-2 px-2 py-1.5 rounded hover:bg-gray-50 dark:hover:bg-gray-600 cursor-pointer text-sm text-gray-700 dark:text-gray-200">
                                    <input type="checkbox" id="filtroMostrarSi" checked class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
                                    <span>Sí</span>
                                </label>
                                <label class="flex items-center gap-2 px-2 py-1.5 rounded hover:bg-gray-50 dark:hover:bg-gray-600 cursor-pointer text-sm text-gray-700 dark:text-gray-200">
                                    <input type="checkbox" id="filtroMostrarNo" class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
                                    <span>No</span>
                                </label>
                            </div>
                        </div>
                        <div class="flex flex-col gap-1">
                            <label for="desglosePerPage" class="text-xs font-medium text-gray-600 dark:text-gray-300 uppercase">Por página</label>
                            <select id="desglosePerPage" class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 min-w-[100px]">
                                <option value="20" selected>20</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                                <option value="200">200</option>
                            </select>
                        </div>
                        <button type="button" id="limpiarFiltrosDesglose"
                            class="text-sm text-blue-600 dark:text-blue-400 hover:underline">
                            Limpiar filtros
                        </button>
                    </div>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                    Cálculo basado en la frecuencia de actualización de cada oferta activa. Total: {{ number_format($ejecucionesPorDia['total_ejecuciones_por_dia'], 1) }} ejecuciones por día.
                </p>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="desglose-sort-header px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider cursor-pointer select-none hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors" data-sort="tienda">Tienda<span class="desglose-sort-indicator ml-1"></span></th>
                                <th class="desglose-sort-header px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider cursor-pointer select-none hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors" data-sort="ofertas">Ofertas<span class="desglose-sort-indicator ml-1"></span></th>
                                <th class="desglose-sort-header px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider cursor-pointer select-none hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors" data-sort="pet-dia">pet./dia<span class="desglose-sort-indicator ml-1"></span></th>
                                <th class="desglose-sort-header px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider cursor-pointer select-none hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors" data-sort="pet-mes">pet/mes<span class="desglose-sort-indicator ml-1"></span></th>
                                <th class="desglose-sort-header px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider cursor-pointer select-none hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors" data-sort="porcentaje">% del Total<span class="desglose-sort-indicator ml-1"></span></th>
                                <th class="desglose-sort-header px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider cursor-pointer select-none hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors" data-sort="api">API<span class="desglose-sort-indicator ml-1"></span></th>
                                <th class="desglose-sort-header px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider cursor-pointer select-none hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors" data-sort="controlador" title="Controlador">Con.<span class="desglose-sort-indicator ml-1"></span></th>
                                <th class="desglose-sort-header px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider cursor-pointer select-none hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors" data-sort="scraping">scraping<span class="desglose-sort-indicator ml-1"></span></th>
                                <th class="desglose-sort-header px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider cursor-pointer select-none hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors" data-sort="mostrar">mostrar<span class="desglose-sort-indicator ml-1"></span></th>
                                <th class="desglose-sort-header px-4 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider cursor-pointer select-none hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors" data-sort="cat-mos" title="Categorías con ofertas mostrando">cat.mos<span class="desglose-sort-indicator ml-1"></span></th>
                                <th class="desglose-sort-header px-4 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider cursor-pointer select-none hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors" data-sort="cat-scraping" title="Categorías con ofertas scrapeando">cat.scraping<span class="desglose-sort-indicator ml-1"></span></th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider" title="APIs por categoría con ofertas">cat API</th>
                            </tr>
                        </thead>
                        <tbody id="desgloseTiendasBody" class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($limitacionesAPI['por_tienda'] as $tienda => $datos)
                                @php
                                    $apiTienda = $datos['api'] ?? null;
                                    $apiBase = $apiTienda ? explode(';', $apiTienda, 2)[0] : null;
                                    $resumen = $resumenScrapingPorTienda[$tienda] ?? [
                                        'cat_mos' => ['si' => 0, 'total' => 0],
                                        'cat_scraping' => ['si' => 0, 'total' => 0],
                                        'cat_api' => [],
                                        'cat_sin_api' => 0,
                                    ];

                                    // Color según el grupo base
                                    if ($apiBase && isset($apiColorMap[$apiBase])) {
                                        $colorApi = $apiColorMap[$apiBase];
                                    } else {
                                        $colorApi = $colorDefecto;
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
                                <tr class="fila-desglose-tienda"
                                    data-orden-original="{{ $loop->index }}"
                                    data-api-base="{{ $apiBase ?? '' }}"
                                    data-tienda-nombre="{{ strtolower($tienda) }}"
                                    data-sort-tienda="{{ strtolower($tienda) }}"
                                    data-sort-ofertas="{{ $datos['ofertas_activas'] ?? 0 }}"
                                    data-sort-pet-dia="{{ $datos['peticiones_por_dia'] ?? 0 }}"
                                    data-sort-pet-mes="{{ $datos['peticiones_por_mes'] ?? 0 }}"
                                    data-sort-porcentaje="{{ $porcentaje }}"
                                    data-sort-api="{{ strtolower($apiBase ?? '') }}"
                                    data-sort-controlador="{{ $controladorExiste ? 1 : 0 }}"
                                    data-sort-scraping="{{ (isset($datos['scrapear']) && $datos['scrapear'] === 'si') ? 1 : 0 }}"
                                    data-sort-mostrar="{{ (isset($datos['mostrar_tienda']) && $datos['mostrar_tienda'] === 'si') ? 1 : 0 }}"
                                    data-sort-cat-mos="{{ $resumen['cat_mos']['si'] }}"
                                    data-sort-cat-scraping="{{ $resumen['cat_scraping']['si'] }}">
                                    <td class="px-4 py-2 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                                        @php $tiendaModel = $tiendasPorNombre[$tienda] ?? null; @endphp
                                        @if($tiendaModel)
                                            <a href="{{ route('admin.tiendas.edit', $tiendaModel) }}"
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                class="text-blue-600 dark:text-blue-400 hover:underline">
                                                {{ $tienda }}
                                            </a>
                                        @else
                                            {{ $tienda }}
                                        @endif
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
                                    <td class="px-4 py-2 whitespace-nowrap text-sm">
                                        @if($apiBase)
                                            <div class="w-8 h-8 {{ $colorApi['icon_bg'] }} rounded-lg flex items-center justify-center" title="{{ $apiTienda }}">
                                                <span class="text-white font-bold text-sm">{{ strtoupper(substr($apiBase, 0, 2)) }}</span>
                                            </div>
                                        @else
                                            <span class="text-red-600 dark:text-red-400 font-semibold">❌</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                        @if($controladorExiste)
                                            <span class="text-green-600 dark:text-green-400 font-semibold">✅</span>
                                        @else
                                            <span class="text-red-600 dark:text-red-400 font-semibold">❌</span>
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
                                    <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100 text-center" title="Categorías con ofertas mostrando">
                                        {{ $resumen['cat_mos']['si'] }}/{{ $resumen['cat_mos']['total'] }}
                                    </td>
                                    <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100 text-center" title="Categorías con ofertas scrapeando">
                                        {{ $resumen['cat_scraping']['si'] }}/{{ $resumen['cat_scraping']['total'] }}
                                    </td>
                                    <td class="px-4 py-2 text-sm text-gray-900 dark:text-gray-100">
                                        <div class="flex flex-wrap items-center gap-2">
                                            @foreach($resumen['cat_api'] as $filaApi)
                                                @php
                                                    $icon = $filaApi['icon'];
                                                    $iconBg = isset($apiColorMap[$filaApi['base']]) ? $apiColorMap[$filaApi['base']]['icon_bg'] : $icon['icon_bg'];
                                                @endphp
                                                <span class="inline-flex items-center gap-1" title="{{ $icon['title'] }}">
                                                    <span class="w-6 h-6 text-xs {{ $iconBg }} rounded-lg flex items-center justify-center text-white font-bold shrink-0">
                                                        {{ $icon['label'] }}
                                                    </span>
                                                    <span class="text-sm font-medium">{{ $filaApi['count'] }}</span>
                                                </span>
                                            @endforeach
                                            @if($resumen['cat_sin_api'] > 0)
                                                @php $iconSinApi = \App\Services\TiendaScrapingConfigResolver::metaIconoApi(null, true); @endphp
                                                <span class="inline-flex items-center gap-1" title="{{ $iconSinApi['title'] }}">
                                                    <span class="w-6 h-6 text-xs {{ $iconSinApi['icon_bg'] }} rounded-lg flex items-center justify-center text-white font-bold shrink-0">
                                                        {{ $iconSinApi['label'] }}
                                                    </span>
                                                    <span class="text-sm font-medium">{{ $resumen['cat_sin_api'] }}</span>
                                                </span>
                                            @endif
                                            @if(empty($resumen['cat_api']) && $resumen['cat_sin_api'] === 0)
                                                <span class="text-sm text-gray-400">—</span>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <div id="desgloseTiendasSinResultados" class="hidden text-center py-6 text-sm text-gray-500 dark:text-gray-400">
                        No hay tiendas que coincidan con los filtros seleccionados.
                    </div>
                    <div id="desglosePaginacion" class="mt-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <p id="desglosePaginacionInfo" class="text-sm text-gray-600 dark:text-gray-400"></p>
                        <div id="desglosePaginacionBotones" class="flex flex-wrap items-center gap-1"></div>
                    </div>
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
     
     <!-- JavaScript para filtros del desglose por tienda -->
     <script>
         document.addEventListener('DOMContentLoaded', function() {
             const filtroApiTienda = document.getElementById('filtroApiTienda');
             const buscadorTienda = document.getElementById('buscadorTienda');
             const toggleFiltrosDesglose = document.getElementById('toggleFiltrosDesglose');
             const panelFiltrosDesglose = document.getElementById('panelFiltrosDesglose');
             const filtroControladorSi = document.getElementById('filtroControladorSi');
             const filtroControladorNo = document.getElementById('filtroControladorNo');
             const filtroControladorBtn = document.getElementById('filtroControladorBtn');
             const filtroControladorPanel = document.getElementById('filtroControladorPanel');
             const filtroControladorTexto = document.getElementById('filtroControladorTexto');
             const filtroScrapingSi = document.getElementById('filtroScrapingSi');
             const filtroScrapingNo = document.getElementById('filtroScrapingNo');
             const filtroScrapingBtn = document.getElementById('filtroScrapingBtn');
             const filtroScrapingPanel = document.getElementById('filtroScrapingPanel');
             const filtroScrapingTexto = document.getElementById('filtroScrapingTexto');
             const filtroMostrarSi = document.getElementById('filtroMostrarSi');
             const filtroMostrarNo = document.getElementById('filtroMostrarNo');
             const filtroMostrarBtn = document.getElementById('filtroMostrarBtn');
             const filtroMostrarPanel = document.getElementById('filtroMostrarPanel');
             const filtroMostrarTexto = document.getElementById('filtroMostrarTexto');
             const limpiarFiltrosDesglose = document.getElementById('limpiarFiltrosDesglose');
             const desglosePerPage = document.getElementById('desglosePerPage');
             const desglosePaginacionInfo = document.getElementById('desglosePaginacionInfo');
             const desglosePaginacionBotones = document.getElementById('desglosePaginacionBotones');
             const desglosePaginacion = document.getElementById('desglosePaginacion');
             const desgloseTiendasBody = document.getElementById('desgloseTiendasBody');
             const desgloseTiendasVisibles = document.getElementById('desgloseTiendasVisibles');
             const desgloseTiendasSinResultados = document.getElementById('desgloseTiendasSinResultados');
             const desgloseTiendasTabla = document.querySelector('#desglosePorTienda table');
             const sortHeaders = document.querySelectorAll('.desglose-sort-header');

             const columnasNumericas = new Set(['ofertas', 'pet-dia', 'pet-mes', 'porcentaje', 'controlador', 'scraping', 'mostrar', 'cat-mos', 'cat-scraping']);
             let sortColumna = null;
             let sortDireccion = null;
             let paginaActual = 1;

             function obtenerPerPage() {
                 const valor = desglosePerPage ? parseInt(desglosePerPage.value, 10) : 20;
                 return Number.isFinite(valor) && valor > 0 ? valor : 20;
             }

             function esFiltroSiNoPorDefecto(checkSi, checkNo) {
                 return checkSi && checkSi.checked && checkNo && !checkNo.checked;
             }

             function textoFiltroSiNo(checkSi, checkNo) {
                 const si = checkSi && checkSi.checked;
                 const no = checkNo && checkNo.checked;
                 if (si && no) return 'Sí, No';
                 if (si) return 'Sí';
                 if (no) return 'No';
                 return 'Ninguno';
             }

             function actualizarTextosFiltrosSiNo() {
                 if (filtroControladorTexto) filtroControladorTexto.textContent = textoFiltroSiNo(filtroControladorSi, filtroControladorNo);
                 if (filtroScrapingTexto) filtroScrapingTexto.textContent = textoFiltroSiNo(filtroScrapingSi, filtroScrapingNo);
                 if (filtroMostrarTexto) filtroMostrarTexto.textContent = textoFiltroSiNo(filtroMostrarSi, filtroMostrarNo);
             }

             function coincideFiltroSiNo(valorFila, checkSi, checkNo) {
                 const si = checkSi && checkSi.checked;
                 const no = checkNo && checkNo.checked;
                 if (si && no) return true;
                 if (!si && !no) return false;
                 if (si && valorFila === '1') return true;
                 if (no && valorFila === '0') return true;
                 return false;
             }

             function cerrarPanelesFiltroSiNo(exceptoPanel) {
                 [filtroControladorPanel, filtroScrapingPanel, filtroMostrarPanel].forEach(panel => {
                     if (panel && panel !== exceptoPanel) {
                         panel.classList.add('hidden');
                     }
                 });
             }

             function configurarFiltroSiNo(btn, panel, checkSi, checkNo) {
                 if (!btn || !panel || !checkSi || !checkNo) return;

                 btn.addEventListener('click', function(e) {
                     e.stopPropagation();
                     const abrir = panel.classList.contains('hidden');
                     cerrarPanelesFiltroSiNo(null);
                     panel.classList.toggle('hidden', !abrir);
                 });

                 panel.addEventListener('click', function(e) {
                     e.stopPropagation();
                 });

                 [checkSi, checkNo].forEach(input => {
                     input.addEventListener('change', function() {
                         actualizarTextosFiltrosSiNo();
                         filtrarDesgloseTiendas();
                     });
                 });
             }

             function hayFiltrosActivos() {
                 const apiSeleccionada = filtroApiTienda ? filtroApiTienda.value : '';
                 const textoBusqueda = buscadorTienda ? buscadorTienda.value.trim() : '';
                 return apiSeleccionada !== ''
                     || textoBusqueda !== ''
                     || !esFiltroSiNoPorDefecto(filtroControladorSi, filtroControladorNo)
                     || !esFiltroSiNoPorDefecto(filtroScrapingSi, filtroScrapingNo)
                     || !esFiltroSiNoPorDefecto(filtroMostrarSi, filtroMostrarNo);
             }

             function filaCoincideFiltros(fila) {
                 const apiSeleccionada = filtroApiTienda ? filtroApiTienda.value : '';
                 const textoBusqueda = buscadorTienda ? buscadorTienda.value.trim().toLowerCase() : '';

                 const apiBase = fila.dataset.apiBase || '';
                 const nombreTienda = fila.dataset.tiendaNombre || '';

                 let coincideApi = true;
                 if (apiSeleccionada === '__sin_configurar__') {
                     coincideApi = apiBase === '';
                 } else if (apiSeleccionada !== '') {
                     coincideApi = apiBase === apiSeleccionada;
                 }

                 const coincideNombre = textoBusqueda === '' || nombreTienda.includes(textoBusqueda);
                 const coincideControlador = coincideFiltroSiNo(fila.dataset.sortControlador, filtroControladorSi, filtroControladorNo);
                 const coincideScraping = coincideFiltroSiNo(fila.dataset.sortScraping, filtroScrapingSi, filtroScrapingNo);
                 const coincideMostrar = coincideFiltroSiNo(fila.dataset.sortMostrar, filtroMostrarSi, filtroMostrarNo);

                 return coincideApi && coincideNombre && coincideControlador && coincideScraping && coincideMostrar;
             }

             function obtenerFilasFiltradas() {
                 return obtenerFilasDesglose().filter(fila => filaCoincideFiltros(fila));
             }

             function actualizarEstiloBotonFiltros() {
                 if (!toggleFiltrosDesglose) return;
                 if (hayFiltrosActivos()) {
                     toggleFiltrosDesglose.classList.add('ring-2', 'ring-blue-500', 'border-blue-500');
                 } else {
                     toggleFiltrosDesglose.classList.remove('ring-2', 'ring-blue-500', 'border-blue-500');
                 }
             }

             function obtenerFilasDesglose() {
                 return desgloseTiendasBody ? Array.from(desgloseTiendasBody.querySelectorAll('.fila-desglose-tienda')) : [];
             }

             function obtenerValorOrden(fila, columna) {
                 const clave = 'sort' + columna.split('-').map(p => p.charAt(0).toUpperCase() + p.slice(1)).join('');
                 return fila.dataset[clave] ?? '';
             }

             function compararValores(a, b, columna) {
                 if (columnasNumericas.has(columna)) {
                     return parseFloat(a) - parseFloat(b);
                 }
                 return String(a).localeCompare(String(b), 'es', { sensitivity: 'base' });
             }

             function actualizarIndicadoresOrden() {
                 sortHeaders.forEach(header => {
                     const indicador = header.querySelector('.desglose-sort-indicator');
                     if (!indicador) return;

                     if (header.dataset.sort === sortColumna && sortDireccion === 'desc') {
                         indicador.textContent = '↓';
                     } else if (header.dataset.sort === sortColumna && sortDireccion === 'asc') {
                         indicador.textContent = '↑';
                     } else {
                         indicador.textContent = '';
                     }
                 });
             }

             function aplicarOrdenDesglose() {
                 if (!desgloseTiendasBody) return;

                 const filas = obtenerFilasDesglose();

                 filas.sort((a, b) => {
                     if (!sortColumna || sortDireccion === null) {
                         return parseInt(a.dataset.ordenOriginal, 10) - parseInt(b.dataset.ordenOriginal, 10);
                     }

                     const cmp = compararValores(
                         obtenerValorOrden(a, sortColumna),
                         obtenerValorOrden(b, sortColumna),
                         sortColumna
                     );

                     return sortDireccion === 'desc' ? -cmp : cmp;
                 });

                 filas.forEach(fila => desgloseTiendasBody.appendChild(fila));
                 actualizarIndicadoresOrden();
                 actualizarDesgloseTiendas();
             }

             function crearBotonPagina(texto, pagina, activa = false, deshabilitada = false) {
                 const boton = document.createElement('button');
                 boton.type = 'button';
                 boton.textContent = texto;
                 boton.className = activa
                     ? 'px-3 py-1 rounded text-sm bg-blue-600 text-white'
                     : 'px-3 py-1 rounded text-sm border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600';
                 boton.disabled = deshabilitada;
                 if (deshabilitada) {
                     boton.classList.add('opacity-50', 'cursor-not-allowed');
                 }
                 if (!deshabilitada && !activa) {
                     boton.addEventListener('click', function() {
                         paginaActual = pagina;
                         actualizarDesgloseTiendas();
                     });
                 }
                 return boton;
             }

             function renderizarPaginacion(totalFiltradas, totalPaginas, inicio, fin) {
                 if (!desglosePaginacionBotones || !desglosePaginacionInfo) return;

                 desglosePaginacionBotones.innerHTML = '';

                 if (totalFiltradas === 0) {
                     desglosePaginacionInfo.textContent = '';
                     if (desglosePaginacion) desglosePaginacion.classList.add('hidden');
                     return;
                 }

                 if (desglosePaginacion) desglosePaginacion.classList.remove('hidden');

                 const totalTodas = obtenerFilasDesglose().length;
                 let textoInfo = `Mostrando ${inicio + 1}-${fin} de ${totalFiltradas} tienda${totalFiltradas === 1 ? '' : 's'}`;
                 if (hayFiltrosActivos() && totalFiltradas !== totalTodas) {
                     textoInfo += ` (filtradas de ${totalTodas})`;
                 }
                 desglosePaginacionInfo.textContent = textoInfo;

                 desglosePaginacionBotones.appendChild(crearBotonPagina('«', paginaActual - 1, false, paginaActual <= 1));

                 const paginasVisibles = [];
                 for (let p = 1; p <= totalPaginas; p++) {
                     if (p === 1 || p === totalPaginas || Math.abs(p - paginaActual) <= 2) {
                         paginasVisibles.push(p);
                     }
                 }

                 let ultimaPaginaMostrada = 0;
                 paginasVisibles.forEach(p => {
                     if (ultimaPaginaMostrada && p - ultimaPaginaMostrada > 1) {
                         const puntos = document.createElement('span');
                         puntos.textContent = '…';
                         puntos.className = 'px-2 text-gray-500';
                         desglosePaginacionBotones.appendChild(puntos);
                     }
                     desglosePaginacionBotones.appendChild(crearBotonPagina(String(p), p, p === paginaActual));
                     ultimaPaginaMostrada = p;
                 });

                 desglosePaginacionBotones.appendChild(crearBotonPagina('»', paginaActual + 1, false, paginaActual >= totalPaginas));
             }

             function actualizarDesgloseTiendas(resetPagina = false) {
                 if (resetPagina) {
                     paginaActual = 1;
                 }

                 const todasLasFilas = obtenerFilasDesglose();
                 const filasFiltradas = obtenerFilasFiltradas();
                 const perPage = obtenerPerPage();
                 const totalFiltradas = filasFiltradas.length;
                 const totalPaginas = Math.max(1, Math.ceil(totalFiltradas / perPage));

                 if (paginaActual > totalPaginas) {
                     paginaActual = totalPaginas;
                 }
                 if (paginaActual < 1) {
                     paginaActual = 1;
                 }

                 const inicio = (paginaActual - 1) * perPage;
                 const fin = Math.min(inicio + perPage, totalFiltradas);
                 const filasPagina = new Set(filasFiltradas.slice(inicio, fin));

                 todasLasFilas.forEach(fila => {
                     fila.classList.toggle('hidden', !filasPagina.has(fila));
                 });

                 if (desgloseTiendasVisibles) {
                     const totalTodas = todasLasFilas.length;
                     if (hayFiltrosActivos()) {
                         desgloseTiendasVisibles.textContent = ` — ${totalFiltradas} de ${totalTodas} tiendas`;
                     } else {
                         desgloseTiendasVisibles.textContent = '';
                     }
                 }

                 if (desgloseTiendasSinResultados && desgloseTiendasTabla) {
                     const sinResultados = totalFiltradas === 0 && todasLasFilas.length > 0;
                     desgloseTiendasSinResultados.classList.toggle('hidden', !sinResultados);
                     desgloseTiendasTabla.classList.toggle('hidden', sinResultados);
                 }

                 renderizarPaginacion(totalFiltradas, totalPaginas, inicio, fin);
                 actualizarEstiloBotonFiltros();
             }

             sortHeaders.forEach(header => {
                 header.addEventListener('click', function() {
                     const columna = this.dataset.sort;

                     if (sortColumna === columna) {
                         if (sortDireccion === null) {
                             sortDireccion = 'desc';
                         } else if (sortDireccion === 'desc') {
                             sortDireccion = 'asc';
                         } else {
                             sortDireccion = null;
                             sortColumna = null;
                         }
                     } else {
                         sortColumna = columna;
                         sortDireccion = 'desc';
                     }

                     aplicarOrdenDesglose();
                 });
             });

             function filtrarDesgloseTiendas() {
                 actualizarDesgloseTiendas(true);
             }

             if (toggleFiltrosDesglose && panelFiltrosDesglose) {
                 toggleFiltrosDesglose.addEventListener('click', function() {
                     panelFiltrosDesglose.classList.toggle('hidden');
                 });
             }

             if (limpiarFiltrosDesglose) {
                 limpiarFiltrosDesglose.addEventListener('click', function() {
                     if (filtroApiTienda) filtroApiTienda.value = '';
                     if (filtroControladorSi) filtroControladorSi.checked = true;
                     if (filtroControladorNo) filtroControladorNo.checked = false;
                     if (filtroScrapingSi) filtroScrapingSi.checked = true;
                     if (filtroScrapingNo) filtroScrapingNo.checked = false;
                     if (filtroMostrarSi) filtroMostrarSi.checked = true;
                     if (filtroMostrarNo) filtroMostrarNo.checked = false;
                     actualizarTextosFiltrosSiNo();
                     cerrarPanelesFiltroSiNo(null);
                     filtrarDesgloseTiendas();
                 });
             }

             configurarFiltroSiNo(filtroControladorBtn, filtroControladorPanel, filtroControladorSi, filtroControladorNo);
             configurarFiltroSiNo(filtroScrapingBtn, filtroScrapingPanel, filtroScrapingSi, filtroScrapingNo);
             configurarFiltroSiNo(filtroMostrarBtn, filtroMostrarPanel, filtroMostrarSi, filtroMostrarNo);

             document.addEventListener('click', function() {
                 cerrarPanelesFiltroSiNo(null);
             });

             if (filtroApiTienda) filtroApiTienda.addEventListener('change', filtrarDesgloseTiendas);
             if (buscadorTienda) buscadorTienda.addEventListener('input', filtrarDesgloseTiendas);
             if (desglosePerPage) desglosePerPage.addEventListener('change', function() {
                 actualizarDesgloseTiendas(true);
             });

             actualizarTextosFiltrosSiNo();
             actualizarDesgloseTiendas();
         });
     </script>
 </x-app-layout>
