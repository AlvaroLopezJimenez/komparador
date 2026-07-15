<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <a href="{{ route('admin.dashboard') }}" class="flex items-center space-x-2 text-white hover:text-gray-200 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                    <h2 class="font-semibold text-xl">Panel</h2>
                </a>
                <div class="hidden sm:block w-px h-6 bg-gray-600"></div>
                <h1 class="text-xl sm:text-2xl font-light text-white">Historial Scraper</h1>
            </div>
            <div class="text-sm text-gray-300 hidden sm:block">
                {{ now()->format('d/m/Y H:i') }}
            </div>
        </div>
    </x-slot>

    <div class="min-h-screen bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            
            <!-- Header Card -->
            <div class="bg-gray-800/80 backdrop-blur-sm rounded-2xl shadow-xl border border-gray-700/50 overflow-hidden mb-6">
                <div class="p-4 sm:p-5">
                    <div class="flex flex-wrap items-center gap-3">
                        <a href="{{ route('admin.scraping.ejecucion-tiempo-real') }}"
                           class="inline-flex items-center justify-center px-4 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 text-white text-sm font-medium rounded-lg hover:from-blue-700 hover:to-indigo-700 transition-all duration-200 shadow-lg">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                            <span class="hidden sm:inline">Ejecución en Tiempo Real</span>
                            <span class="sm:hidden">Tiempo Real</span>
                        </a>

                        <button type="button" onclick="toggleFiltros()"
                                class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 text-white text-sm font-medium rounded-lg hover:from-blue-700 hover:to-indigo-700 transition-all duration-200 shadow-lg">
                            <svg id="iconoFiltros" class="w-4 h-4 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                            </svg>
                            <span>🔍 Filtros Avanzados</span>
                        </button>

                        @if($filtroRapido || $fechaDesde || $fechaHasta || $horaDesde || $horaHasta || $busqueda)
                            <span class="text-sm text-green-400">✓ Filtros activos</span>
                            <a href="{{ route('admin.ofertas.scraper.ejecuciones') }}"
                               class="px-3 py-1.5 bg-red-600 text-white text-xs font-medium rounded-md hover:bg-red-700 transition-colors">
                                Limpiar
                            </a>
                        @endif
                    </div>

                    <!-- Contenido de filtros (oculto por defecto) -->
                    <div id="contenidoFiltros" class="hidden mt-4">
                            <form method="GET" class="space-y-4 bg-gray-700/30 rounded-xl p-6 border border-gray-600/50">
                                <input type="hidden" name="filtro_rapido" id="filtro_rapido" value="{{ $filtroRapido ?? 'hoy' }}">
                                @if(($tipoEjecucion ?? 'todas') !== 'todas')
                                    <input type="hidden" name="tipo_ejecucion" value="{{ $tipoEjecucion }}">
                                @endif
                                
                                <!-- Filtros rápidos de fecha -->
                                <div class="bg-gray-700/50 rounded-lg p-4">
                                    <label class="block text-sm font-medium text-gray-300 mb-3">📅 Filtros Rápidos:</label>
                                    <div class="flex flex-wrap gap-2">
                                        <button type="button" onclick="aplicarFiltroRapido('hoy')" 
                                                class="px-3 py-1.5 text-xs font-medium rounded-md transition-colors {{ $filtroRapido === 'hoy' ? 'bg-blue-600 text-white' : 'bg-gray-600 text-gray-300 hover:bg-gray-500 border border-gray-500' }}">
                                            🟢 Hoy
                                        </button>
                                        <button type="button" onclick="aplicarFiltroRapido('ayer')" 
                                                class="px-3 py-1.5 text-xs font-medium rounded-md transition-colors {{ $filtroRapido === 'ayer' ? 'bg-blue-600 text-white' : 'bg-gray-600 text-gray-300 hover:bg-gray-500 border border-gray-500' }}">
                                            📅 Ayer
                                        </button>
                                        <button type="button" onclick="aplicarFiltroRapido('7dias')" 
                                                class="px-3 py-1.5 text-xs font-medium rounded-md transition-colors {{ $filtroRapido === '7dias' ? 'bg-blue-600 text-white' : 'bg-gray-600 text-gray-300 hover:bg-gray-500 border border-gray-500' }}">
                                            📊 Últimos 7 días
                                        </button>
                                        <button type="button" onclick="aplicarFiltroRapido('30dias')" 
                                                class="px-3 py-1.5 text-xs font-medium rounded-md transition-colors {{ $filtroRapido === '30dias' ? 'bg-blue-600 text-white' : 'bg-gray-600 text-gray-300 hover:bg-gray-500 border border-gray-500' }}">
                                            📈 Últimos 30 días
                                        </button>
                                        <button type="button" onclick="aplicarFiltroRapido('90dias')" 
                                                class="px-3 py-1.5 text-xs font-medium rounded-md transition-colors {{ $filtroRapido === '90dias' ? 'bg-blue-600 text-white' : 'bg-gray-600 text-gray-300 hover:bg-gray-500 border border-gray-500' }}">
                                            📊 Últimos 90 días
                                        </button>
                                        <button type="button" onclick="aplicarFiltroRapido('180dias')" 
                                                class="px-3 py-1.5 text-xs font-medium rounded-md transition-colors {{ $filtroRapido === '180dias' ? 'bg-blue-600 text-white' : 'bg-gray-600 text-gray-300 hover:bg-gray-500 border border-gray-500' }}">
                                            📈 Últimos 180 días
                                        </button>
                                        <button type="button" onclick="aplicarFiltroRapido('1año')" 
                                                class="px-3 py-1.5 text-xs font-medium rounded-md transition-colors {{ $filtroRapido === '1año' ? 'bg-blue-600 text-white' : 'bg-gray-600 text-gray-300 hover:bg-gray-500 border border-gray-500' }}">
                                            📅 Último año
                                        </button>
                                        <button type="button" onclick="aplicarFiltroRapido('siempre')" 
                                                class="px-3 py-1.5 text-xs font-medium rounded-md transition-colors {{ $filtroRapido === 'siempre' ? 'bg-blue-600 text-white' : 'bg-gray-600 text-gray-300 hover:bg-gray-500 border border-gray-500' }}">
                                            🌍 Desde siempre
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                                    <!-- Fecha desde -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-300 mb-1">Fecha desde:</label>
                                        <input type="date" name="fecha_desde" id="fecha_desde" value="{{ $fechaDesde }}" 
                                               class="w-full px-3 py-2 border border-gray-600 rounded-md text-sm bg-gray-700 text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    </div>
                                    
                                    <!-- Fecha hasta -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-300 mb-1">Fecha hasta:</label>
                                        <input type="date" name="fecha_hasta" id="fecha_hasta" value="{{ $fechaHasta }}" 
                                               class="w-full px-3 py-2 border border-gray-600 rounded-md text-sm bg-gray-700 text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    </div>
                                    
                                    <!-- Hora desde -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-300 mb-1">Hora desde:</label>
                                        <input type="time" name="hora_desde" value="{{ $horaDesde }}" 
                                               class="w-full px-3 py-2 border border-gray-600 rounded-md text-sm bg-gray-700 text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    </div>
                                    
                                    <!-- Hora hasta -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-300 mb-1">Hora hasta:</label>
                                        <input type="time" name="hora_hasta" value="{{ $horaHasta }}" 
                                               class="w-full px-3 py-2 border border-gray-600 rounded-md text-sm bg-gray-700 text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    </div>
                                    
                                    <!-- Búsqueda -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-300 mb-1">Búsqueda:</label>
                                        <div class="relative">
                                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                                </svg>
                                            </div>
                                            <input type="text" name="buscar" placeholder="Buscar por fecha..." 
                                                value="{{ $busqueda }}"
                                                class="w-full pl-10 pr-4 py-2 border border-gray-600 rounded-md text-sm bg-gray-700 text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="flex justify-between items-center pt-4 border-t border-gray-600/50">
                                    <button type="submit" class="px-6 py-2 bg-gradient-to-r from-green-500 to-emerald-600 text-white font-medium rounded-md hover:from-green-600 hover:to-emerald-700 transform hover:scale-105 transition-all duration-200 shadow-lg">
                                        🔍 Aplicar Filtros
                                    </button>
                                    
                                    <a href="{{ route('admin.ofertas.scraper.ejecuciones') }}" 
                                       class="px-6 py-2 bg-gradient-to-r from-gray-500 to-gray-600 text-white font-medium rounded-md hover:from-gray-600 hover:to-gray-700 transform hover:scale-105 transition-all duration-200 shadow-lg">
                                        🔄 Limpiar Todo
                                    </a>
                                </div>
                            </form>
                    </div>
                </div>
            </div>

            <!-- Estadísticas Generales -->
            <div class="bg-gray-800/80 backdrop-blur-sm rounded-2xl shadow-xl border border-gray-700/50 overflow-hidden mb-6">
                <div class="p-6 sm:p-8">
                    <h3 class="text-lg sm:text-xl font-bold text-white mb-6">📊 Estadísticas Generales</h3>
                    
                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
                        <div class="bg-gradient-to-br from-blue-900/20 to-blue-800/20 p-4 rounded-xl border border-blue-700/50 text-center">
                            <div class="text-3xl font-bold text-blue-400 mb-2">{{ number_format($totalEjecuciones) }}</div>
                            <div class="text-sm text-blue-300 font-medium">Total Ejecuciones</div>
                        </div>
                        <div class="bg-gradient-to-br from-purple-900/20 to-purple-800/20 p-4 rounded-xl border border-purple-700/50 text-center">
                            <div class="text-3xl font-bold text-purple-400 mb-2">{{ number_format($totalOfertas) }}</div>
                            <div class="text-sm text-purple-300 font-medium">Total Ofertas</div>
                        </div>
                        <div class="bg-gradient-to-br from-green-900/20 to-emerald-800/20 p-4 rounded-xl border border-green-700/50 text-center">
                            <div class="text-3xl font-bold text-green-400 mb-2">{{ number_format($totalActualizadas) }}</div>
                            <div class="text-sm text-green-300 font-medium">Total Actualizadas</div>
                        </div>
                        <div class="bg-gradient-to-br from-amber-900/20 to-orange-800/20 p-4 rounded-xl border border-amber-700/50 text-center">
                            <div class="text-3xl font-bold text-amber-400 mb-2">{{ number_format($totalOcultadas) }}</div>
                            <div class="text-sm text-amber-300 font-medium">Total Ocultadas</div>
                        </div>
                        <div class="bg-gradient-to-br from-red-900/20 to-red-800/20 p-4 rounded-xl border border-red-700/50 text-center">
                            <a href="#errores-por-tienda" class="block hover:scale-105 transition-transform duration-200 cursor-pointer">
                                <div class="text-3xl font-bold text-red-400 mb-2">{{ number_format($totalErrores) }}</div>
                                <div class="text-sm text-red-300 font-medium">Total Errores</div>
                                @if($totalOfertas > 0)
                                    <div class="text-xs text-red-200 mt-1">
                                        {{ number_format(($totalErrores / $totalOfertas) * 100, 1) }}% del total
                                    </div>
                                @endif
                            </a>
                        </div>
                        <div class="bg-gradient-to-br from-cyan-900/20 to-teal-800/20 p-4 rounded-xl border border-cyan-700/50 text-center">
                            <div class="text-3xl font-bold text-cyan-400 mb-2">
                                @if($mediaPeticionesPorMinuto !== null)
                                    {{ number_format($mediaPeticionesPorMinuto, 1) }}
                                @else
                                    0
                                @endif
                            </div>
                            <div class="text-sm text-cyan-300 font-medium">Media pet/min API</div>
                            @if($mediaPeticionesPorMinuto !== null)
                                <div class="text-xs text-cyan-200 mt-1">
                                    {{ number_format($ejecucionesConMetricasPpm) }} ejecuciones
                                </div>
                            @else
                                <div class="text-xs text-cyan-200 mt-1">
                                    Sin datos en el período
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- APIs usadas -->
            <div id="apis-usadas" class="bg-gray-800/80 backdrop-blur-sm rounded-2xl shadow-xl border border-gray-700/50 overflow-hidden mb-6">
                <div class="p-6 sm:p-8">
                    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
                        <h3 class="text-lg sm:text-xl font-bold text-white">🔌 APIs usadas</h3>
                        <div id="filtro-api-global-badge" class="hidden text-sm text-indigo-200 bg-indigo-900/40 border border-indigo-700/50 px-3 py-1 rounded-full">
                            Filtro: <span id="filtro-api-global-nombre" class="font-semibold"></span>
                            <button type="button" onclick="aplicarFiltroApiGlobal('todas')" class="ml-2 text-indigo-300 hover:text-white underline">Quitar</button>
                        </div>
                    </div>
                    @if(!empty($conteoGlobalPorApi))
                        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-4">
                            <button type="button"
                                onclick="aplicarFiltroApiGlobal('todas')"
                                class="filtro-api-global-card bg-gradient-to-br from-gray-700/40 to-gray-800/40 p-4 rounded-xl border border-gray-600/50 text-center hover:scale-[1.02] transition-all duration-200 ring-2 ring-offset-2 ring-offset-gray-900 ring-gray-400 scale-[1.02]"
                                data-api-filtro="todas" aria-pressed="true">
                                <div class="text-2xl font-bold text-gray-200 mb-1">∞</div>
                                <div class="text-sm text-gray-300 font-medium">Todas</div>
                            </button>
                            @foreach($conteoGlobalPorApi as $apiBase => $total)
                                <button type="button"
                                    onclick='aplicarFiltroApiGlobal(@json($apiBase))'
                                    class="filtro-api-global-card bg-gradient-to-br from-indigo-900/20 to-violet-800/20 p-4 rounded-xl border border-indigo-700/50 text-center hover:scale-[1.02] transition-all duration-200"
                                    data-api-filtro="{{ $apiBase }}" aria-pressed="false">
                                    <div class="text-2xl font-bold text-indigo-300 mb-1">{{ number_format($total) }}</div>
                                    <div class="text-sm text-indigo-200 font-medium break-words">{{ $apiBase }}</div>
                                </button>
                            @endforeach
                        </div>
                    @else
                        <p class="text-gray-400 text-sm">No hay peticiones con API registradas en el período filtrado.</p>
                    @endif
                </div>
            </div>

            <!-- Gráficos -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <!-- Gráfico por Hora -->
                <div class="bg-gray-800/80 backdrop-blur-sm rounded-2xl shadow-xl border border-gray-700/50 overflow-hidden">
                    <button type="button" onclick="toggleGraficoHoras()"
                        class="w-full p-4 sm:p-5 flex items-center justify-between gap-3 text-left hover:bg-gray-700/30 transition-colors">
                        <h3 class="text-lg sm:text-xl font-bold text-white">📈 Estadísticas por Hora del Día</h3>
                        <svg id="iconoGraficoHoras" class="w-5 h-5 text-gray-400 shrink-0 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                    <div id="contenidoGraficoHoras" class="hidden border-t border-gray-700/50 px-4 sm:px-6 pb-4 sm:pb-6">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-end gap-2 pt-4 mb-4">
                            <label class="text-sm text-gray-300 font-medium whitespace-nowrap">🎯 Filtrar por tienda:</label>
                            <select id="filtroTienda" onchange="filtrarPorTienda(this.value)"
                                    class="px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 min-w-[200px]">
                                <option value="todas">👁️ Todas las tiendas</option>
                                @if(isset($tiendasEnDatos))
                                    @foreach($tiendasEnDatos as $tienda)
                                        <option value="{{ $tienda }}">{{ $tienda }}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                        <div class="relative" style="height: 300px;">
                            <canvas id="graficoHoras"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Gráfico por Día -->
                <div class="bg-gray-800/80 backdrop-blur-sm rounded-2xl shadow-xl border border-gray-700/50 overflow-hidden">
                    <button type="button" onclick="toggleGraficoDias()"
                        class="w-full p-4 sm:p-5 flex items-center justify-between gap-3 text-left hover:bg-gray-700/30 transition-colors">
                        <h3 class="text-lg sm:text-xl font-bold text-white">📅 Estadísticas por Día</h3>
                        <svg id="iconoGraficoDias" class="w-5 h-5 text-gray-400 shrink-0 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                    <div id="contenidoGraficoDias" class="hidden border-t border-gray-700/50 px-4 sm:px-6 pb-4 sm:pb-6">
                        <div class="relative pt-4" style="height: 300px;">
                            <canvas id="graficoDias"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Errores por Tienda -->
            <div id="errores-por-tienda" class="bg-gray-800/80 backdrop-blur-sm rounded-2xl shadow-xl border border-gray-700/50 overflow-hidden mb-6">
                <div class="p-6 sm:p-8">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-lg sm:text-xl font-bold text-white">🚨 Errores por Tienda</h3>
                        <span id="errores-por-tienda-filtro-api" class="hidden text-xs text-indigo-300 bg-indigo-900/30 border border-indigo-700/40 px-2 py-1 rounded-full"></span>
                    </div>
                    
                    @if(count($erroresPorTienda) > 0)
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b border-gray-700/50">
                                        <th class="text-left py-3 px-4 font-semibold text-gray-300">Tienda</th>
                                        <th class="text-center py-3 px-4 font-semibold text-gray-300">Total Errores</th>
                                        <th class="text-center py-3 px-4 font-semibold text-gray-300">% del Total</th>
                                        <th class="text-center py-3 px-4 font-semibold text-gray-300">Barra de Progreso</th>
                                    </tr>
                                </thead>
                                <tbody id="errores-por-tienda-tbody" class="divide-y divide-gray-700/50">
                                    @foreach($erroresPorTienda as $tienda => $errores)
                                        @php
                                            $porcentaje = $totalErrores > 0 ? ($errores / $totalErrores) * 100 : 0;
                                            $apiTienda = $tiendasConApi[$tienda] ?? null;
                                        @endphp
                                        <tr class="fila-error-tienda hover:bg-gray-700/50 transition-colors"
                                            data-tienda="{{ $tienda }}"
                                            data-errores-total="{{ $errores }}"
                                            data-errores-api='@json($erroresPorTiendaDetalle[$tienda] ?? [])'>
                                            <td class="py-3 px-4">
                                                <button onclick="verErroresTienda('{{ $tienda }}')" class="w-full text-left">
                                                    <div class="flex items-center space-x-3 hover:bg-gray-700/50 p-2 rounded-lg transition-colors cursor-pointer">
                                                        <div class="w-8 h-8 bg-gradient-to-br from-red-500 to-pink-600 rounded-lg flex items-center justify-center">
                                                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                                            </svg>
                                                        </div>
                                                        <div class="flex-1">
                                                            <div class="flex items-center space-x-2">
                                                                <span class="font-medium text-white">{{ $tienda }}</span>
                                                                @if($apiTienda)
                                                                    <span class="px-2 py-1 text-xs font-medium rounded-full bg-blue-900 text-blue-200">
                                                                        API: {{ $apiTienda }}
                                                                    </span>
                                                                @endif
                                                            </div>
                                                        </div>
                                                        <div class="ml-auto">
                                                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                                            </svg>
                                                        </div>
                                                    </div>
                                                </button>
                                            </td>
                                            <td class="py-3 px-4 text-center">
                                                <span class="text-xl font-bold text-red-400 celda-errores-count">{{ number_format($errores) }}</span>
                                            </td>
                                            <td class="py-3 px-4 text-center">
                                                <span class="text-sm font-medium text-gray-300 celda-errores-pct">{{ number_format($porcentaje, 1) }}%</span>
                                            </td>
                                            <td class="py-3 px-4">
                                                <div class="w-full bg-gray-700 rounded-full h-3">
                                                    <div class="celda-errores-barra bg-gradient-to-r from-red-500 to-pink-600 h-3 rounded-full transition-all duration-300" 
                                                         style="width: {{ min($porcentaje, 100) }}%"></div>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @if(count($erroresPorTienda) > 4)
                            <div class="mt-4 text-center">
                                <button type="button"
                                    id="btn-ver-mas-errores-tienda"
                                    onclick="toggleErroresTiendaExtra()"
                                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-gray-700 hover:bg-gray-600 text-white text-sm font-medium rounded-lg border border-gray-600 transition-colors">
                                    <span id="btn-ver-mas-errores-tienda-texto">Ver más ({{ count($erroresPorTienda) - 4 }} tiendas)</span>
                                    <svg id="icono-ver-mas-errores-tienda" class="w-4 h-4 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </button>
                            </div>
                        @endif
                    @else
                        <div class="text-center py-12">
                            <div class="w-16 h-16 bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-4">
                                <svg class="w-8 h-8 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>
                            <h4 class="text-lg font-medium text-white mb-2">¡Excelente!</h4>
                            <p class="text-gray-400">No se han registrado errores en este período de tiempo.</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Limitaciones de API -->
            <div class="bg-gray-800/80 backdrop-blur-sm rounded-2xl shadow-xl border border-gray-700/50 overflow-hidden mb-6">
                <button type="button" onclick="toggleLimitacionesApi()"
                    class="w-full p-4 sm:p-5 flex items-center justify-between gap-3 text-left hover:bg-gray-700/30 transition-colors">
                    <h3 class="text-lg sm:text-xl font-bold text-white">🔒 Limitaciones de API</h3>
                    <svg id="iconoLimitacionesApi" class="w-5 h-5 text-gray-400 shrink-0 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>
                <div id="contenidoLimitacionesApi" class="hidden border-t border-gray-700/50 px-4 sm:px-6 pb-4 sm:pb-6">
                    <div class="flex flex-wrap items-center justify-between gap-3 pt-4 mb-4">
                        <p class="text-sm text-gray-400">
                            Control de peticiones a APIs externas según ejecuciones reales en el período filtrado.
                        </p>
                        <button onclick="cargarEstadisticasAvanzadas()" 
                                id="btn-actualizar-api"
                                class="px-4 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 text-white text-sm font-medium rounded-lg hover:from-blue-700 hover:to-indigo-700 transition-all duration-200 shadow-lg shrink-0">
                            <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                            Actualizar
                        </button>
                    </div>
                    <div id="contenido-limitaciones-api">
                        <div class="text-center py-12">
                            <div class="w-16 h-16 bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-4">
                                <svg class="w-8 h-8 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                            </div>
                            <p class="text-gray-400">Pulsa el botón "Actualizar" para cargar las estadísticas de APIs</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Estadísticas por Tienda -->
            <div class="bg-gray-800/80 backdrop-blur-sm rounded-2xl shadow-xl border border-gray-700/50 overflow-hidden mb-6">
                <button type="button" onclick="toggleEstadisticasTienda()"
                    class="w-full p-4 sm:p-5 flex items-center justify-between gap-3 text-left hover:bg-gray-700/30 transition-colors">
                    <h3 class="text-lg sm:text-xl font-bold text-white">📊 Estadísticas por Tienda</h3>
                    <svg id="iconoEstadisticasTienda" class="w-5 h-5 text-gray-400 shrink-0 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>
                <div id="contenidoEstadisticasTienda" class="hidden border-t border-gray-700/50 px-4 sm:px-6 pb-4 sm:pb-6">
                    <div class="flex justify-end pt-4 mb-4">
                        <button onclick="cargarEstadisticasAvanzadas()" 
                                id="btn-actualizar-tiendas"
                                class="px-4 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 text-white text-sm font-medium rounded-lg hover:from-blue-700 hover:to-indigo-700 transition-all duration-200 shadow-lg">
                            <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                            Actualizar
                        </button>
                    </div>
                    <div id="contenido-estadisticas-tienda">
                        <div class="text-center py-12">
                            <div class="w-16 h-16 bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-4">
                                <svg class="w-8 h-8 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                            </div>
                            <p class="text-gray-400">Pulsa el botón "Actualizar" para cargar las estadísticas de tiendas</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Lista de Ejecuciones -->
            <div id="ejecuciones-recientes" class="bg-gray-800/80 backdrop-blur-sm rounded-2xl shadow-xl border border-gray-700/50 overflow-hidden">
                <div class="p-6 sm:p-8 border-b border-gray-700/50">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <h3 class="text-lg sm:text-xl font-bold text-white">Ejecuciones Recientes</h3>
                        <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                            <span id="ejecuciones-filtro-api-badge" class="hidden text-xs text-indigo-300 bg-indigo-900/30 border border-indigo-700/40 px-2 py-1 rounded-full"></span>
                            <div class="flex items-center gap-2">
                                <label for="filtro-tipo-ejecucion" class="text-sm text-gray-400 whitespace-nowrap">Tipo:</label>
                                <select id="filtro-tipo-ejecucion"
                                    class="px-3 py-2 border border-gray-600 rounded-lg text-sm bg-gray-700 text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 min-w-[200px]">
                                    <option value="todas" @selected(($tipoEjecucion ?? 'todas') === 'todas')>Todas</option>
                                    <option value="normal" @selected(($tipoEjecucion ?? 'todas') === 'normal')>Ejecuciones normales</option>
                                    <option value="primera_oferta" @selected(($tipoEjecucion ?? 'todas') === 'primera_oferta')>Primera oferta</option>
                                </select>
                            </div>
                            <div id="ejecuciones-recientes-contador" class="text-sm text-gray-400" data-total-original="{{ $ejecuciones->total() }} ejecuciones">
                                {{ $ejecuciones->total() }} ejecuciones
                            </div>
                        </div>
                    </div>
                </div>
                
                <div id="ejecuciones-recientes-cuerpo" class="transition-opacity duration-200">
                    @if($ejecuciones->count() > 0)
                        {{-- Cabecera alineada con los cuadros de resumen --}}
                        <div class="hidden sm:flex items-center gap-4 px-6 sm:px-8 py-2 border-b border-gray-700/50 text-xs font-semibold text-gray-500 uppercase tracking-wide">
                            <div class="flex-1 min-w-0">Ejecución</div>
                            <div class="flex gap-2 shrink-0">
                                <div class="ejecucion-stat-col ejecucion-stat-actualiz text-center">Actualiz.</div>
                                <div class="ejecucion-stat-col ejecucion-stat-ppm text-center">pet/min API</div>
                                <div class="ejecucion-stat-col ejecucion-stat-ocult text-center">Ocult.</div>
                                <div class="ejecucion-stat-col ejecucion-stat-error text-center">Errores</div>
                                <div class="ejecucion-stat-col ejecucion-stat-action text-center">Ver</div>
                                <div class="ejecucion-stat-col ejecucion-stat-action text-center">Elim.</div>
                            </div>
                        </div>
                        <div class="divide-y divide-gray-700/50">
                            @foreach($ejecuciones as $ejecucion)
                                @php
                                    $logEjecucionApis = is_array($ejecucion->log ?? null) ? $ejecucion->log : [];
                                    $apisEjecucion = array_keys($logEjecucionApis['conteo_por_api'] ?? []);
                                    $logEjecucionResumen = is_array($ejecucion->log ?? null) ? $ejecucion->log : [];
                                    $metricasPpm = $logEjecucionResumen['metricas_peticiones_api'] ?? null;
                                    $ocultadasEjecucion = (int) ($logEjecucionResumen['ocultadas'] ?? 0);
                                    $erroresEjecucion = (int) ($ejecucion->total_errores ?? 0);
                                    $ppmEjecucion = (int) ($metricasPpm['peticiones_por_minuto'] ?? 0);
                                    $tiendaPpmEjecucion = $metricasPpm['tienda_mas_repetida'] ?? '—';
                                @endphp
                                <div class="ejecucion-item px-6 sm:px-8 py-5 sm:py-6 hover:bg-gray-700/50 transition-all duration-200"
                                     data-apis="{{ implode(',', $apisEjecucion) }}">
                                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                                        {{-- Info ejecución --}}
                                        <div class="flex items-start gap-4 flex-1 min-w-0">
                                            <div class="flex-shrink-0">
                                                @if($ejecucion->fin)
                                                    <div class="w-12 h-12 bg-gradient-to-br from-green-400 to-emerald-500 rounded-xl flex items-center justify-center shadow-lg">
                                                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                        </svg>
                                                    </div>
                                                @else
                                                    <div class="w-12 h-12 bg-gradient-to-br from-yellow-400 to-orange-500 rounded-xl flex items-center justify-center shadow-lg animate-pulse">
                                                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                        </svg>
                                                    </div>
                                                @endif
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <div class="flex flex-wrap items-center gap-2 mb-2">
                                                    <h4 class="text-lg font-bold text-white">Ejecución #{{ $ejecucion->id }}</h4>
                                                    <span class="px-3 py-1 text-xs font-medium rounded-full {{ $ejecucion->fin ? 'bg-green-900 text-green-200' : 'bg-yellow-900 text-yellow-200' }}">
                                                        {{ $ejecucion->fin ? 'Completada' : 'En Progreso' }}
                                                    </span>
                                                    @if($ejecucion->nombre === 'actualizar_primera_oferta')
                                                        <span class="px-3 py-1 text-xs font-medium rounded-full bg-purple-900 text-purple-200">Primera oferta por producto</span>
                                                    @else
                                                        <span class="px-3 py-1 text-xs font-medium rounded-full bg-indigo-900 text-indigo-200">Ofertas tiempo actualización cumplido</span>
                                                    @endif
                                                </div>
                                                <div class="flex flex-col sm:flex-row sm:flex-wrap sm:items-center gap-x-6 gap-y-1 text-sm text-gray-400">
                                                    <span>Inicio: {{ $ejecucion->inicio->format('d/m/Y H:i:s') }}</span>
                                                    @if($ejecucion->fin)
                                                        <span>Fin: {{ $ejecucion->fin->format('d/m/Y H:i:s') }}</span>
                                                        <span>Duración: {{ $ejecucion->inicio->diffForHumans($ejecucion->fin, true) }}</span>
                                                    @else
                                                        <span class="text-yellow-400">En progreso...</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Cuadros de resumen (anchos fijos → alineación vertical entre filas) --}}
                                        <div class="flex flex-row gap-2 shrink-0 self-stretch sm:self-auto">
                                            <div class="ejecucion-stat-col ejecucion-stat-actualiz text-center px-2 py-2 rounded-lg bg-gray-700/30 border border-gray-600/40 flex flex-col justify-center">
                                                <div class="text-xl font-bold text-white leading-tight tabular-nums">{{ $ejecucion->total_guardado ?? 0 }}/{{ $ejecucion->total ?? 0 }}</div>
                                                <div class="text-xs text-gray-400 sm:hidden mt-0.5">Actualizadas</div>
                                            </div>
                                            <div class="ejecucion-stat-col ejecucion-stat-ppm text-center px-2 py-2 rounded-lg bg-cyan-900/20 border border-cyan-700/40 flex flex-col justify-center overflow-hidden">
                                                <div class="text-xl font-bold text-cyan-400 leading-tight tabular-nums">{{ $ppmEjecucion }}</div>
                                                <div class="text-[10px] text-gray-400 leading-tight mt-0.5">pet/min</div>
                                                <div class="text-xs text-cyan-300 truncate max-w-full mt-0.5" title="{{ $tiendaPpmEjecucion }}">{{ $tiendaPpmEjecucion }}</div>
                                            </div>
                                            <div class="ejecucion-stat-col ejecucion-stat-ocult text-center px-2 py-2 rounded-lg bg-amber-900/20 border border-amber-700/40 flex flex-col justify-center">
                                                <div class="text-xl font-bold text-amber-400 leading-tight tabular-nums">{{ $ocultadasEjecucion }}</div>
                                                <div class="text-xs text-gray-400 sm:hidden mt-0.5">Ocultadas</div>
                                            </div>
                                            <div class="ejecucion-stat-col ejecucion-stat-error text-center px-2 py-2 rounded-lg bg-red-900/20 border border-red-700/40 flex flex-col justify-center">
                                                <div class="text-xl font-bold text-red-400 leading-tight tabular-nums">{{ $erroresEjecucion }}</div>
                                                <div class="text-xs text-gray-400 sm:hidden mt-0.5">Errores</div>
                                            </div>
                                            <button type="button"
                                                onclick="verDetalles({{ $ejecucion->id }})"
                                                title="Ver detalles"
                                                aria-label="Ver detalles de ejecución #{{ $ejecucion->id }}"
                                                class="ejecucion-stat-col ejecucion-stat-action rounded-lg bg-gradient-to-r from-blue-500 to-indigo-600 flex items-center justify-center text-white hover:from-blue-600 hover:to-indigo-700 hover:scale-[1.03] transition-all duration-200 shadow-lg cursor-pointer">
                                                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                                </svg>
                                            </button>
                                            <form method="POST" action="{{ route('admin.ofertas.scraper.ejecuciones.eliminar', $ejecucion) }}"
                                                onsubmit="return confirm('¿Estás seguro de que quieres eliminar esta ejecución?')"
                                                class="ejecucion-stat-col ejecucion-stat-action shrink-0">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                    title="Eliminar ejecución"
                                                    aria-label="Eliminar ejecución #{{ $ejecucion->id }}"
                                                    class="w-full h-full min-h-[4.5rem] rounded-lg bg-gradient-to-r from-red-500 to-pink-600 flex items-center justify-center text-white hover:from-red-600 hover:to-pink-700 hover:scale-[1.03] transition-all duration-200 shadow-lg">
                                                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                    </svg>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <!-- Paginación Mejorada -->
                        <div class="px-6 sm:px-8 py-6 border-t border-gray-700/50">
                            {{ $ejecuciones->links() }}
                        </div>
                    @else
                        <div class="p-12 sm:p-16 text-center">
                            <div class="w-24 h-24 mx-auto mb-6 bg-gradient-to-br from-gray-700 to-gray-800 rounded-full flex items-center justify-center">
                                <svg class="w-12 h-12 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                            </div>
                            <h3 class="text-xl font-bold text-white mb-3">No hay ejecuciones</h3>
                            <p class="text-gray-400 max-w-md mx-auto">
                                @if(($tipoEjecucion ?? 'todas') === 'normal')
                                    No hay ejecuciones normales del scraper con los filtros actuales.
                                @elseif(($tipoEjecucion ?? 'todas') === 'primera_oferta')
                                    No hay ejecuciones de primera oferta con los filtros actuales.
                                @else
                                    Aún no se han realizado ejecuciones del scraper. Cuando ejecutes el scraper, aquí aparecerá el historial completo.
                                @endif
                            </p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Detalles Mejorado -->
    <div id="modal-detalles" class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50 p-4">
        <div class="bg-gray-800 rounded-2xl shadow-2xl border border-gray-700/50 w-full max-w-4xl max-h-[90vh] flex flex-col overflow-hidden transform transition-all duration-300">
            <div class="p-4 sm:p-5 border-b border-gray-700/50 shrink-0">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl sm:text-2xl font-bold text-white">Detalles de la Ejecución</h2>
                    <button onclick="cerrarModal()" class="p-2 text-gray-400 hover:text-gray-300 hover:bg-gray-700 rounded-lg transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>
            <div class="flex-1 min-h-0 p-4 sm:p-5 overflow-hidden flex flex-col">
                <div id="detalles-contenido" class="flex-1 min-h-0 flex flex-col">
                    <!-- El contenido se cargará dinámicamente -->
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Errores por Tienda -->
    <div id="modal-errores-tienda" class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50 p-4">
        <div class="bg-gray-800 rounded-2xl shadow-2xl border border-gray-700/50 w-full max-w-6xl max-h-[90vh] overflow-hidden transform transition-all duration-300">
            <div class="p-6 sm:p-8 border-b border-gray-700/50">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl sm:text-2xl font-bold text-white">🚨 Errores Detallados por Tienda</h2>
                    <button onclick="cerrarModalErroresTienda()" class="p-2 text-gray-400 hover:text-gray-300 hover:bg-gray-700 rounded-lg transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>
            <div class="p-6 sm:p-8 overflow-y-auto max-h-[calc(90vh-120px)]">
                <div id="errores-tienda-contenido">
                    <!-- El contenido se cargará dinámicamente -->
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts para los gráficos -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        .ejecucion-stat-actualiz { width: 6.5rem; min-width: 6.5rem; min-height: 4.5rem; flex-shrink: 0; }
        .ejecucion-stat-ppm { width: 9.5rem; min-width: 9.5rem; min-height: 4.5rem; flex-shrink: 0; }
        .ejecucion-stat-ocult { width: 4.5rem; min-width: 4.5rem; min-height: 4.5rem; flex-shrink: 0; }
        .ejecucion-stat-error { width: 5rem; min-width: 5rem; min-height: 4.5rem; flex-shrink: 0; }
        .ejecucion-stat-action {
            width: 4.5rem;
            min-width: 4.5rem;
            min-height: 4.5rem;
            flex-shrink: 0;
        }
    </style>
    
    <script>
        const editarOfertaBaseUrl = @json(url('/panel-privado/ofertas'));
        const tiendasApiCache = @json($tiendasApiCache ?? []);
        let filtroApiGlobalActual = 'todas';

        function urlEditarOferta(ofertaId) {
            const id = parseInt(ofertaId, 10);
            if (!Number.isFinite(id) || id <= 0) {
                return null;
            }
            return `${editarOfertaBaseUrl}/${id}/edit`;
        }

        function construirUrlListadoEjecuciones(tipo) {
            const url = new URL(window.location.href);
            if (tipo === 'todas') {
                url.searchParams.delete('tipo_ejecucion');
            } else {
                url.searchParams.set('tipo_ejecucion', tipo);
            }
            url.searchParams.delete('page');
            return url;
        }

        function cargarListadoEjecuciones(tipo, pageUrl) {
            const cuerpo = document.getElementById('ejecuciones-recientes-cuerpo');
            const contador = document.getElementById('ejecuciones-recientes-contador');
            if (!cuerpo) {
                return;
            }

            const url = pageUrl
                ? new URL(pageUrl, window.location.origin)
                : construirUrlListadoEjecuciones(tipo);

            cuerpo.classList.add('opacity-50', 'pointer-events-none');

            fetch(url.toString(), {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'text/html',
                },
            })
                .then((response) => {
                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}`);
                    }
                    return response.text();
                })
                .then((html) => {
                    const doc = new DOMParser().parseFromString(html, 'text/html');
                    const nuevoCuerpo = doc.getElementById('ejecuciones-recientes-cuerpo');
                    const nuevoContador = doc.getElementById('ejecuciones-recientes-contador');

                    if (nuevoCuerpo) {
                        cuerpo.innerHTML = nuevoCuerpo.innerHTML;
                    }
                    if (nuevoContador && contador) {
                        contador.textContent = nuevoContador.textContent;
                        contador.dataset.totalOriginal = nuevoContador.dataset.totalOriginal || nuevoContador.textContent.trim();
                    }

                    history.replaceState(null, '', url.pathname + url.search);
                    aplicarFiltroApiGlobal(filtroApiGlobalActual, false);
                })
                .catch((error) => {
                    console.error('Error al cargar ejecuciones:', error);
                })
                .finally(() => {
                    cuerpo.classList.remove('opacity-50', 'pointer-events-none');
                });
        }

        document.getElementById('filtro-tipo-ejecucion')?.addEventListener('change', function () {
            cargarListadoEjecuciones(this.value);
        });

        document.getElementById('ejecuciones-recientes-cuerpo')?.addEventListener('click', function (event) {
            const enlace = event.target.closest('a[href*="page="]');
            if (!enlace || !this.contains(enlace)) {
                return;
            }
            event.preventDefault();
            cargarListadoEjecuciones(null, enlace.href);
        });

        window.addEventListener('popstate', function () {
            const url = new URL(window.location.href);
            const tipo = url.searchParams.get('tipo_ejecucion') || 'todas';
            const select = document.getElementById('filtro-tipo-ejecucion');
            if (select) {
                select.value = tipo;
            }
            cargarListadoEjecuciones(null, window.location.href);
        });

        let modalFiltroTipoActual = 'todas';
        let modalFiltroApiActual = 'todas';
        let modalFiltroTiendaActual = 'todas';

        function aplicarFiltroApiGlobal(api, scroll = true) {
            filtroApiGlobalActual = api || 'todas';

            document.querySelectorAll('.filtro-api-global-card').forEach((card) => {
                const activo = card.dataset.apiFiltro === filtroApiGlobalActual;
                card.classList.remove('ring-2', 'ring-offset-2', 'ring-offset-gray-900', 'scale-[1.02]', 'ring-gray-400', 'ring-indigo-400');
                card.setAttribute('aria-pressed', activo ? 'true' : 'false');
                if (activo) {
                    card.classList.add('ring-2', 'ring-offset-2', 'ring-offset-gray-900', 'scale-[1.02]', filtroApiGlobalActual === 'todas' ? 'ring-gray-400' : 'ring-indigo-400');
                }
            });

            const badge = document.getElementById('filtro-api-global-badge');
            const badgeNombre = document.getElementById('filtro-api-global-nombre');
            if (badge && badgeNombre) {
                if (filtroApiGlobalActual === 'todas') {
                    badge.classList.add('hidden');
                } else {
                    badge.classList.remove('hidden');
                    badgeNombre.textContent = filtroApiGlobalActual;
                }
            }

            const badgeErrores = document.getElementById('errores-por-tienda-filtro-api');
            if (badgeErrores) {
                if (filtroApiGlobalActual === 'todas') {
                    badgeErrores.classList.add('hidden');
                } else {
                    badgeErrores.classList.remove('hidden');
                    badgeErrores.textContent = `API: ${filtroApiGlobalActual}`;
                }
            }

            const badgeEjecuciones = document.getElementById('ejecuciones-filtro-api-badge');
            if (badgeEjecuciones) {
                if (filtroApiGlobalActual === 'todas') {
                    badgeEjecuciones.classList.add('hidden');
                } else {
                    badgeEjecuciones.classList.remove('hidden');
                    badgeEjecuciones.textContent = `API: ${filtroApiGlobalActual}`;
                }
            }

            filtrarErroresPorTiendaPorApi(filtroApiGlobalActual);
            filtrarEjecucionesPorApi(filtroApiGlobalActual);

            if (scroll && filtroApiGlobalActual !== 'todas') {
                document.getElementById('ejecuciones-recientes')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }

        let erroresTiendaExpandido = false;
        let filtroApiErroresTiendaActual = 'todas';

        function toggleErroresTiendaExtra() {
            erroresTiendaExpandido = !erroresTiendaExpandido;
            actualizarVisibilidadErroresTienda();
        }

        function actualizarVisibilidadErroresTienda() {
            const filas = document.querySelectorAll('.fila-error-tienda');
            const api = filtroApiErroresTiendaActual;
            let totalErroresVisibles = 0;
            const filasApiVisibles = [];

            filas.forEach((fila) => {
                const erroresTotal = parseInt(fila.dataset.erroresTotal || '0', 10);
                let erroresApi = JSON.parse(fila.dataset.erroresApi || '{}');
                if (typeof erroresApi !== 'object' || erroresApi === null) {
                    erroresApi = {};
                }

                let count = erroresTotal;
                let pasaApi = true;

                if (api !== 'todas') {
                    count = parseInt(erroresApi[api] || '0', 10);
                    pasaApi = count > 0;
                }

                fila.dataset.countVisible = String(count);
                fila.classList.toggle('hidden-api', !pasaApi);

                if (pasaApi) {
                    filasApiVisibles.push(fila);
                    totalErroresVisibles += count;
                    const celdaCount = fila.querySelector('.celda-errores-count');
                    if (celdaCount) {
                        celdaCount.textContent = count.toLocaleString();
                    }
                }
            });

            filasApiVisibles.forEach((fila, index) => {
                const ocultaLimite = !erroresTiendaExpandido && index >= 4;
                fila.classList.toggle('hidden-limit', ocultaLimite);
                fila.classList.toggle('hidden', ocultaLimite);

                const count = parseInt(fila.dataset.countVisible || '0', 10);
                const pct = totalErroresVisibles > 0 ? (count / totalErroresVisibles) * 100 : 0;
                const celdaPct = fila.querySelector('.celda-errores-pct');
                const celdaBarra = fila.querySelector('.celda-errores-barra');
                if (celdaPct) {
                    celdaPct.textContent = `${pct.toFixed(1)}%`;
                }
                if (celdaBarra) {
                    celdaBarra.style.width = `${Math.min(pct, 100)}%`;
                }
            });

            filas.forEach((fila) => {
                if (fila.classList.contains('hidden-api')) {
                    fila.classList.add('hidden');
                    fila.classList.remove('hidden-limit');
                }
            });

            const btn = document.getElementById('btn-ver-mas-errores-tienda');
            const btnTexto = document.getElementById('btn-ver-mas-errores-tienda-texto');
            const icono = document.getElementById('icono-ver-mas-errores-tienda');
            const restantes = Math.max(0, filasApiVisibles.length - 4);

            if (btn) {
                btn.classList.toggle('hidden', restantes === 0);
            }
            if (btnTexto) {
                btnTexto.textContent = erroresTiendaExpandido
                    ? 'Ver menos'
                    : `Ver más (${restantes} tienda${restantes === 1 ? '' : 's'})`;
            }
            if (icono) {
                icono.classList.toggle('rotate-180', erroresTiendaExpandido);
            }
        }

        function filtrarErroresPorTiendaPorApi(api) {
            filtroApiErroresTiendaActual = api || 'todas';
            erroresTiendaExpandido = false;
            actualizarVisibilidadErroresTienda();
        }

        function filtrarEjecucionesPorApi(api) {
            const items = document.querySelectorAll('.ejecucion-item');
            const contador = document.getElementById('ejecuciones-recientes-contador');
            let visibles = 0;

            items.forEach((item) => {
                const apis = (item.dataset.apis || '').split(',').filter(Boolean);
                const mostrar = api === 'todas' || apis.includes(api);
                item.classList.toggle('hidden', !mostrar);
                if (mostrar) {
                    visibles++;
                }
            });

            if (contador) {
                const totalOriginal = contador.dataset.totalOriginal || contador.textContent.trim();
                if (api === 'todas') {
                    contador.textContent = totalOriginal;
                } else {
                    contador.textContent = `${visibles} visibles · ${totalOriginal} · API: ${api}`;
                }
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            actualizarVisibilidadErroresTienda();
        });

        function apiBaseDeResultado(item) {
            let api = item.api_usada || '';
            if (!api && item.tienda_nombre && tiendasApiCache[item.tienda_nombre]) {
                api = tiendasApiCache[item.tienda_nombre];
            }
            if (!api) {
                return '';
            }
            return String(api).split(';')[0];
        }

        function verDetalles(ejecucionId) {
            // Mostrar modal con loading mejorado
            document.getElementById('modal-detalles').classList.remove('hidden');
            document.getElementById('detalles-contenido').innerHTML = `
                <div class="flex flex-col items-center justify-center py-12">
                    <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-full flex items-center justify-center mb-4">
                        <svg class="animate-spin h-8 w-8 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>
                    <p class="text-gray-300 font-medium">Cargando detalles...</p>
                </div>
            `;
            
            // Hacer llamada AJAX para obtener los detalles
            fetch(`/panel-privado/ofertas/scraper/ejecuciones/${ejecucionId}/json`)
                .then(response => response.json())
                .then(data => {
                    const resultados = Array.isArray(data) ? data : (data.resultados || []);
                    const metricas = Array.isArray(data) ? null : (data.metricas_peticiones_api || null);
                    const conteoPorApi = Array.isArray(data) ? {} : (data.conteo_por_api || {});
                    modalFiltroTipoActual = 'todas';
                    modalFiltroApiActual = filtroApiGlobalActual !== 'todas' ? filtroApiGlobalActual : 'todas';
                    modalFiltroTiendaActual = 'todas';
                    mostrarDetallesEjecucion(resultados, ejecucionId, metricas, conteoPorApi);
                })
                .catch(error => {
                    console.error('Error:', error);
                    mostrarError('Error de conexión al cargar los detalles');
                });
        }

        function filtrarResultadosModal(filtro) {
            modalFiltroTipoActual = filtro;
            aplicarFiltrosModalResultados();
        }

        function filtrarResultadosModalPorApi(api) {
            modalFiltroApiActual = api;
            aplicarFiltrosModalResultados();
        }

        function filtrarResultadosModalPorTienda(tienda) {
            modalFiltroTiendaActual = tienda;
            aplicarFiltrosModalResultados();
        }

        function aplicarFiltrosModalResultados() {
            const lista = document.getElementById('modal-resultados-lista');
            const contador = document.getElementById('modal-resultados-contador');
            const vacio = document.getElementById('modal-resultados-vacio');
            if (!lista) {
                return;
            }

            const filas = lista.querySelectorAll('[data-tipo-resultado]');
            let visibles = 0;

            filas.forEach((fila) => {
                const tipo = fila.dataset.tipoResultado;
                const apiFila = fila.dataset.api || '';
                const tiendaFila = fila.dataset.tienda || '';
                const mostrarTipo = modalFiltroTipoActual === 'todas' || tipo === modalFiltroTipoActual;
                const mostrarApi = modalFiltroApiActual === 'todas' || apiFila === modalFiltroApiActual;
                const mostrarTienda = modalFiltroTiendaActual === 'todas' || tiendaFila === modalFiltroTiendaActual;
                const mostrar = mostrarTipo && mostrarApi && mostrarTienda;
                fila.classList.toggle('hidden', !mostrar);
                if (mostrar) {
                    visibles++;
                }
            });

            if (contador) {
                contador.textContent = String(visibles);
            }
            if (vacio) {
                vacio.classList.toggle('hidden', visibles > 0);
            }

            const anillos = {
                todas: 'ring-gray-400',
                actualizada: 'ring-green-400',
                ocultada: 'ring-amber-400',
                error: 'ring-red-400',
            };

            document.querySelectorAll('.modal-filtro-resultado').forEach((btn) => {
                const activo = btn.dataset.filtro === modalFiltroTipoActual;
                btn.classList.remove('ring-2', 'ring-offset-2', 'ring-offset-gray-900', 'scale-[1.02]', 'ring-gray-400', 'ring-green-400', 'ring-amber-400', 'ring-red-400');
                btn.setAttribute('aria-pressed', activo ? 'true' : 'false');
                if (activo) {
                    btn.classList.add('ring-2', 'ring-offset-2', 'ring-offset-gray-900', 'scale-[1.02]', anillos[modalFiltroTipoActual] || 'ring-gray-400');
                }
            });

            document.querySelectorAll('.modal-filtro-api').forEach((btn) => {
                const activo = btn.dataset.apiFiltro === modalFiltroApiActual;
                btn.classList.remove('ring-2', 'ring-offset-2', 'ring-offset-gray-900', 'scale-[1.02]', 'ring-indigo-400', 'ring-gray-400');
                btn.setAttribute('aria-pressed', activo ? 'true' : 'false');
                if (activo) {
                    btn.classList.add('ring-2', 'ring-offset-2', 'ring-offset-gray-900', 'scale-[1.02]', modalFiltroApiActual === 'todas' ? 'ring-gray-400' : 'ring-indigo-400');
                }
            });

            document.querySelectorAll('.modal-filtro-tienda').forEach((btn) => {
                const activo = btn.dataset.tiendaFiltro === modalFiltroTiendaActual;
                btn.classList.remove('ring-2', 'ring-offset-2', 'ring-offset-gray-900', 'scale-[1.02]', 'ring-purple-400', 'ring-gray-400');
                btn.setAttribute('aria-pressed', activo ? 'true' : 'false');
                if (activo) {
                    btn.classList.add('ring-2', 'ring-offset-2', 'ring-offset-gray-900', 'scale-[1.02]', modalFiltroTiendaActual === 'todas' ? 'ring-gray-400' : 'ring-purple-400');
                }
            });
        }

        function mostrarDetallesEjecucion(data, ejecucionId, metricasPpm = null, conteoPorApi = {}) {
            if (!data || data.length === 0) {
                document.getElementById('detalles-contenido').innerHTML = `
                    <div class="bg-gradient-to-br from-gray-700 to-gray-800 p-8 rounded-2xl text-center border border-gray-600/50">
                        <div class="w-16 h-16 bg-gray-600 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        </div>
                        <p class="text-gray-400 font-medium">No hay resultados detallados disponibles para esta ejecución.</p>
                    </div>
                `;
                return;
            }

            const actualizadas = data.filter(item => item.success && !item.oferta_oculta).length;
            const ocultadas = data.filter(item => item.success && item.oferta_oculta).length;
            const errores = data.filter(item => !item.success).length;

            if (!conteoPorApi || Object.keys(conteoPorApi).length === 0) {
                conteoPorApi = {};
                data.forEach((item) => {
                    const api = apiBaseDeResultado(item);
                    if (api) {
                        conteoPorApi[api] = (conteoPorApi[api] || 0) + 1;
                    }
                });
            }

            const apisEnEjecucion = Object.keys(conteoPorApi || {});

            const conteoPorTienda = {};
            data.forEach((item) => {
                const tienda = item.tienda_nombre || 'N/A';
                conteoPorTienda[tienda] = (conteoPorTienda[tienda] || 0) + 1;
            });
            const tiendasEnEjecucion = Object.keys(conteoPorTienda).sort((a, b) => conteoPorTienda[b] - conteoPorTienda[a]);

            const ppmValor = metricasPpm ? Number(metricasPpm.peticiones_por_minuto || 0) : 0;
            let contenido = `
                <div class="flex flex-col flex-1 min-h-0 gap-3">
                    <!-- Información General -->
                    <div class="bg-gradient-to-br from-blue-900/20 to-indigo-800/20 px-4 py-2.5 rounded-xl border border-blue-700/50 shrink-0">
                        <div class="flex flex-wrap items-center gap-x-5 gap-y-1 text-sm">
                            <span class="font-bold text-blue-100 flex items-center">
                                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                Información General
                            </span>
                            <span class="text-blue-200">
                                <span class="font-semibold">ID de Ejecución:</span>
                                <span class="text-blue-300 ml-1">#${ejecucionId}</span>
                            </span>
                            <span class="text-blue-200">
                                <span class="font-semibold">Total de Ofertas:</span>
                                <span class="text-blue-300 ml-1">${data.length}</span>
                            </span>
                            ${metricasPpm ? `
                            <span class="text-blue-200">
                                <span class="font-semibold">Peticiones/min API:</span>
                                <span class="text-cyan-300 ml-1">${ppmValor}</span>
                                <span class="text-blue-300 ml-1">(${metricasPpm.tienda_mas_repetida || 'N/A'}, ${metricasPpm.peticiones_tienda || 0} pet.)</span>
                            </span>
                            ` : ''}
                        </div>
                    </div>

                    <!-- Estadísticas -->
                    <div class="grid grid-cols-2 lg:grid-cols-${metricasPpm ? '5' : '4'} gap-2 shrink-0">
                        <button type="button" onclick="filtrarResultadosModal('todas')"
                            class="modal-filtro-resultado bg-gradient-to-br from-gray-700 to-gray-800 px-3 py-2 rounded-lg border border-gray-600/50 text-center cursor-pointer hover:scale-[1.02] transition-all duration-200 ring-2 ring-offset-2 ring-offset-gray-900 ring-gray-400 scale-[1.02]"
                            data-filtro="todas" aria-pressed="true" title="Ver todas las ofertas">
                            <div class="text-xl font-bold text-white leading-tight">${data.length}</div>
                            <div class="text-xs text-gray-400 font-medium">Total Ofertas</div>
                        </button>
                        <button type="button" onclick="filtrarResultadosModal('actualizada')"
                            class="modal-filtro-resultado bg-gradient-to-br from-green-900/20 to-emerald-800/20 px-3 py-2 rounded-lg border border-green-700/50 text-center cursor-pointer hover:scale-[1.02] transition-all duration-200"
                            data-filtro="actualizada" aria-pressed="false" title="Ver solo precio actualizado">
                            <div class="text-xl font-bold text-green-400 leading-tight">${actualizadas}</div>
                            <div class="text-xs text-green-300 font-medium">Precio actualizado</div>
                        </button>
                        <button type="button" onclick="filtrarResultadosModal('ocultada')"
                            class="modal-filtro-resultado bg-gradient-to-br from-amber-900/20 to-orange-800/20 px-3 py-2 rounded-lg border border-amber-700/50 text-center cursor-pointer hover:scale-[1.02] transition-all duration-200"
                            data-filtro="ocultada" aria-pressed="false" title="Ver solo ocultadas">
                            <div class="text-xl font-bold text-amber-400 leading-tight">${ocultadas}</div>
                            <div class="text-xs text-amber-300 font-medium">Ocultadas</div>
                        </button>
                        <button type="button" onclick="filtrarResultadosModal('error')"
                            class="modal-filtro-resultado bg-gradient-to-br from-red-900/20 to-pink-800/20 px-3 py-2 rounded-lg border border-red-700/50 text-center cursor-pointer hover:scale-[1.02] transition-all duration-200"
                            data-filtro="error" aria-pressed="false" title="Ver solo errores">
                            <div class="text-xl font-bold text-red-400 leading-tight">${errores}</div>
                            <div class="text-xs text-red-300 font-medium">Errores</div>
                        </button>
                        ${metricasPpm ? `
                        <div class="bg-gradient-to-br from-cyan-900/20 to-teal-800/20 px-3 py-2 rounded-lg border border-cyan-700/50 text-center"
                            title="Máximo real de peticiones en un mismo minuto (tienda API no CSV con más peticiones)">
                            <div class="text-xl font-bold text-cyan-400 leading-tight">${ppmValor}</div>
                            <div class="text-xs text-cyan-300 font-medium">Pet/min API</div>
                        </div>
                        ` : ''}
                    </div>

                    <!-- Resultados Detallados -->
                    <div class="flex flex-col flex-1 min-h-0 bg-gray-800 border border-gray-700/50 rounded-xl overflow-hidden shadow-lg">
                        <div class="px-4 py-2 border-b border-gray-700/50 bg-gradient-to-r from-gray-700 to-gray-800 shrink-0 space-y-2">
                            <div class="flex flex-wrap items-center gap-x-3 gap-y-2">
                                <h3 class="font-bold text-white text-sm flex items-center shrink-0">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                    </svg>
                                    Resultados Detallados (<span id="modal-resultados-contador">${data.length}</span> ofertas)
                                </h3>
                                <div class="flex flex-wrap items-center gap-2">
                                    <button type="button" onclick="filtrarResultadosModalPorApi('todas')"
                                        class="modal-filtro-api px-2.5 py-1 text-xs font-medium rounded-md bg-gray-600 hover:bg-gray-500 text-white transition-colors ring-2 ring-offset-2 ring-offset-gray-900 ring-gray-400 scale-[1.02]"
                                        data-api-filtro="todas" aria-pressed="true">
                                        Todas
                                    </button>
                                    ${apisEnEjecucion.map((api) => `
                                    <button type="button" onclick='filtrarResultadosModalPorApi(${JSON.stringify(api)})'
                                        class="modal-filtro-api px-2.5 py-1 text-xs font-medium rounded-md bg-indigo-900/60 hover:bg-indigo-800 text-indigo-100 border border-indigo-700/50 transition-colors"
                                        data-api-filtro="${String(api).replace(/"/g, '&quot;')}" aria-pressed="false">
                                        ${api} (${conteoPorApi[api]})
                                    </button>
                                    `).join('')}
                                </div>
                            </div>
                            <div class="flex flex-wrap items-center gap-2">
                                <button type="button" onclick="filtrarResultadosModalPorTienda('todas')"
                                    class="modal-filtro-tienda px-2.5 py-1 text-xs font-medium rounded-md bg-gray-600 hover:bg-gray-500 text-white transition-colors ring-2 ring-offset-2 ring-offset-gray-900 ring-gray-400 scale-[1.02]"
                                    data-tienda-filtro="todas" aria-pressed="true">
                                    Todas
                                </button>
                                ${tiendasEnEjecucion.map((tienda) => `
                                <button type="button" onclick='filtrarResultadosModalPorTienda(${JSON.stringify(tienda)})'
                                    class="modal-filtro-tienda px-2.5 py-1 text-xs font-medium rounded-md bg-purple-900/60 hover:bg-purple-800 text-purple-100 border border-purple-700/50 transition-colors"
                                    data-tienda-filtro="${String(tienda).replace(/"/g, '&quot;')}" aria-pressed="false">
                                    ${tienda} (${conteoPorTienda[tienda]})
                                </button>
                                `).join('')}
                            </div>
                        </div>
                        <div id="modal-resultados-lista" class="flex-1 min-h-0 overflow-y-auto overscroll-y-contain">
                        <div id="modal-resultados-vacio" class="hidden p-6 text-center text-gray-400 text-sm">
                            No hay resultados con este filtro.
                        </div>
            `;

            data.forEach((item, index) => {
                const esOcultada = !!(item.success && item.oferta_oculta);
                const esExito = !!(item.success && !item.oferta_oculta);
                const bgColor = esExito
                    ? 'bg-gradient-to-r from-green-900/10 to-emerald-900/10'
                    : (esOcultada
                        ? 'bg-gradient-to-r from-amber-900/10 to-orange-900/10'
                        : 'bg-gradient-to-r from-red-900/10 to-pink-900/10');
                const badgeClass = esExito
                    ? 'bg-green-900 text-green-200'
                    : (esOcultada ? 'bg-amber-900 text-amber-200' : 'bg-red-900 text-red-200');
                const badgeText = esExito ? '✓ Precio' : (esOcultada ? '◐ Ocultada' : '✗ Error');
                const tipoResultado = esExito ? 'actualizada' : (esOcultada ? 'ocultada' : 'error');
                const apiBase = apiBaseDeResultado(item);
                const apiEtiqueta = apiBase || 'Sin API';
                const tiendaNombre = item.tienda_nombre || 'N/A';
                const editUrl = urlEditarOferta(item.oferta_id);
                const ofertaUrl = item.url && item.url !== 'N/A' ? item.url : null;
                
                contenido += `
                    <div class="px-4 py-3 border-b border-gray-700/50 ${bgColor} hover:bg-opacity-75 transition-all duration-200" data-tipo-resultado="${tipoResultado}" data-api="${apiBase}" data-tienda="${String(tiendaNombre).replace(/"/g, '&quot;')}">
                        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-2">
                            <div class="flex-1 min-w-0 space-y-1">
                                <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-sm">
                                    <span class="font-bold text-white">Oferta #${item.oferta_id || 'N/A'}</span>
                                    <span class="px-2 py-0.5 text-xs font-bold rounded-full ${badgeClass}">${badgeText}</span>
                                    <span class="text-gray-400">
                                        <span class="font-medium text-gray-300">Tienda:</span>
                                        ${tiendaNombre}
                                    </span>
                                    <span class="px-2 py-0.5 text-xs font-medium rounded-full ${apiBase ? 'bg-indigo-900 text-indigo-200' : 'bg-gray-700 text-gray-400'}">
                                        API: ${apiEtiqueta}
                                    </span>
                                    ${item.hora_peticion ? `
                                    <span class="text-gray-500 text-xs">
                                        <span class="font-medium text-gray-400">Hora:</span>
                                        ${item.hora_peticion}
                                    </span>
                                    ` : ''}
                                </div>
                `;

                if (esExito) {
                    const precioAnt = parseFloat(String(item.precio_anterior ?? '').replace(',', '.'));
                    const precioNuevo = parseFloat(String(item.precio_nuevo ?? '').replace(',', '.'));
                    const precioCambio = Number.isFinite(precioAnt) && Number.isFinite(precioNuevo) && precioAnt !== precioNuevo;
                    const precioMostrar = item.precio_nuevo ?? item.precio_anterior ?? 'N/A';

                    if (precioCambio) {
                        contenido += `
                                <div class="flex items-center flex-wrap gap-x-2 text-sm">
                                    <span class="font-medium text-gray-300">Precio:</span>
                                    <span class="line-through text-gray-500">€${item.precio_anterior || 'N/A'}</span>
                                    <span class="text-green-400 font-bold">€${item.precio_nuevo || 'N/A'}</span>
                                </div>
                        `;
                    } else {
                        contenido += `
                                <div class="flex items-center flex-wrap gap-x-2 text-sm">
                                    <span class="font-medium text-gray-300">Precio:</span>
                                    <span class="text-gray-300">€${precioMostrar}</span>
                                </div>
                        `;
                    }
                } else if (esOcultada) {
                    contenido += `
                                <div class="text-sm">
                                    <span class="font-medium text-amber-300">Oferta ocultada:</span>
                                    <span class="text-amber-200 ml-1">${item.motivo_ocultacion || 'Sin stock / 404 / CSV / segunda mano'}</span>
                                </div>
                    `;
                } else {
                    contenido += `
                                <div class="text-sm">
                                    <span class="font-medium text-red-400">Error:</span>
                                    <span class="text-red-400 ml-1">${item.error || 'Error desconocido'}</span>
                                </div>
                    `;
                }

                if (item.cambios_detectados) {
                    contenido += `
                                <div class="text-xs text-orange-400 font-medium">⚠️ Precio del producto actualizado</div>
                    `;
                }

                if (item.proxy_ip) {
                    contenido += `
                                <div class="text-xs text-gray-500">Proxy: <span class="font-mono">${item.proxy_ip}</span></div>
                    `;
                }

                contenido += `
                            </div>
                            <div class="shrink-0 flex items-center gap-2">
                                ${ofertaUrl ? `
                                <a href="${ofertaUrl}" target="_blank" rel="noopener noreferrer"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm font-medium bg-gray-600 hover:bg-gray-500 text-white transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                    </svg>
                                    Ir
                                </a>
                                ` : ''}
                                ${editUrl ? `
                                <a href="${editUrl}" target="_blank" rel="noopener noreferrer"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm font-medium bg-blue-600 hover:bg-blue-500 text-white transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                    Editar
                                </a>
                                ` : ''}
                            </div>
                        </div>
                    </div>
                `;
            });

            contenido += `
                        </div>
                    </div>
                </div>
            `;
            
            document.getElementById('detalles-contenido').innerHTML = contenido;
            aplicarFiltrosModalResultados();
        }

        function mostrarError(mensaje) {
            document.getElementById('detalles-contenido').innerHTML = `
                <div class="bg-gradient-to-br from-red-900/20 to-pink-800/20 p-8 rounded-2xl border border-red-700/50">
                    <div class="flex items-center justify-center mb-4">
                        <div class="w-16 h-16 bg-red-900 rounded-full flex items-center justify-center">
                            <svg class="w-8 h-8 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                            </svg>
                        </div>
                    </div>
                    <p class="text-red-300 text-center font-medium">${mensaje}</p>
                </div>
            `;
        }

        function cerrarModal() {
            document.getElementById('modal-detalles').classList.add('hidden');
        }

        function cerrarModalErroresTienda() {
            document.getElementById('modal-errores-tienda').classList.add('hidden');
        }

        // Cerrar modal al hacer clic fuera
        document.getElementById('modal-detalles').addEventListener('click', function(e) {
            if (e.target === this) {
                cerrarModal();
            }
        });

        document.getElementById('modal-errores-tienda').addEventListener('click', function(e) {
            if (e.target === this) {
                cerrarModalErroresTienda();
            }
        });

        // Cerrar modal con Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                cerrarModal();
                cerrarModalErroresTienda();
            }
        });

        // Función para ver errores por tienda
        function verErroresTienda(tienda) {
            // Mostrar modal con loading
            document.getElementById('modal-errores-tienda').classList.remove('hidden');
            document.getElementById('errores-tienda-contenido').innerHTML = `
                <div class="flex flex-col items-center justify-center py-12">
                    <div class="w-16 h-16 bg-gradient-to-br from-red-500 to-pink-600 rounded-full flex items-center justify-center mb-4">
                        <svg class="animate-spin h-8 w-8 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>
                    <p class="text-gray-300 font-medium">Cargando errores de ${tienda}...</p>
                </div>
            `;
            
            // Obtener parámetros de filtro actuales (primero de la URL, luego del formulario)
            const urlParams = new URLSearchParams(window.location.search);
            let filtroRapido = urlParams.get('filtro_rapido') || '';
            let fechaDesde = urlParams.get('fecha_desde') || '';
            let fechaHasta = urlParams.get('fecha_hasta') || '';
            let horaDesde = urlParams.get('hora_desde') || '';
            let horaHasta = urlParams.get('hora_hasta') || '';
            
            // Si no hay parámetros en la URL, obtener valores del formulario
            if (!filtroRapido && !fechaDesde && !fechaHasta) {
                const filtroRapidoInput = document.getElementById('filtro_rapido');
                const fechaDesdeInput = document.getElementById('fecha_desde');
                const fechaHastaInput = document.getElementById('fecha_hasta');
                const horaDesdeInput = document.querySelector('input[name="hora_desde"]');
                const horaHastaInput = document.querySelector('input[name="hora_hasta"]');
                
                if (filtroRapidoInput) filtroRapido = filtroRapidoInput.value || '';
                if (fechaDesdeInput) fechaDesde = fechaDesdeInput.value || '';
                if (fechaHastaInput) fechaHasta = fechaHastaInput.value || '';
                if (horaDesdeInput) horaDesde = horaDesdeInput.value || '';
                if (horaHastaInput) horaHasta = horaHastaInput.value || '';
            }
            
            // Si aún no hay filtro rápido ni fechas, usar 'hoy' por defecto
            if (!filtroRapido && !fechaDesde && !fechaHasta) {
                filtroRapido = 'hoy';
            }
            
            // Construir URL para la petición
            const url = new URL('/panel-privado/ofertas/scraper/errores-tienda', window.location.origin);
            url.searchParams.set('tienda', tienda);
            // Solo enviar filtro_rapido si no hay fechas manuales (las fechas manuales tienen prioridad)
            if (filtroRapido && !fechaDesde && !fechaHasta) {
                url.searchParams.set('filtro_rapido', filtroRapido);
            }
            if (fechaDesde) url.searchParams.set('fecha_desde', fechaDesde);
            if (fechaHasta) url.searchParams.set('fecha_hasta', fechaHasta);
            if (horaDesde) url.searchParams.set('hora_desde', horaDesde);
            if (horaHasta) url.searchParams.set('hora_hasta', horaHasta);
            
            // Hacer llamada AJAX
            fetch(url.toString())
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        mostrarErroresTienda(data);
                    } else {
                        mostrarErrorErroresTienda('Error al cargar los errores: ' + (data.error || 'Error desconocido'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    mostrarErrorErroresTienda('Error de conexión al cargar los errores');
                });
        }

        function mostrarErroresTienda(data) {
            const { tienda, errores, urls_resueltas, total_errores, es_filtro_un_dia, fecha_filtro } = data;
            
            let contenido = `
                <div class="space-y-6">
                    <!-- Header de la tienda -->
                    <div class="bg-gradient-to-br from-red-900/20 to-pink-800/20 p-6 rounded-2xl border border-red-700/50">
                        <div class="flex items-center space-x-4">
                            <div class="w-12 h-12 bg-gradient-to-br from-red-500 to-pink-600 rounded-xl flex items-center justify-center">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-white">${tienda}</h3>
                                <p class="text-red-300">${total_errores} errores en este período</p>
                                ${fecha_filtro ? `<p class="text-sm text-gray-400">Filtro: ${fecha_filtro}</p>` : ''}
                            </div>
                        </div>
                    </div>

                    <!-- Aviso sobre funcionalidad de URLs resueltas -->
                    ${!es_filtro_un_dia ? `
                        <div class="bg-gradient-to-br from-yellow-900/20 to-orange-800/20 p-4 rounded-xl border border-yellow-700/50">
                            <div class="flex items-center space-x-2">
                                <svg class="w-5 h-5 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                </svg>
                                <span class="text-yellow-300 font-medium">ℹ️ Información</span>
                            </div>
                            <p class="text-yellow-200 text-sm mt-2">
                                La verificación de URLs resueltas solo está disponible cuando se filtra por un día específico. 
                                Para ver si las URLs se resolvieron posteriormente, selecciona un solo día en los filtros.
                            </p>
                        </div>
                    ` : ''}

                    <!-- Lista de errores -->
                    <div class="bg-gray-800 border border-gray-700/50 rounded-2xl overflow-hidden shadow-lg">
                        <div class="px-6 py-4 border-b border-gray-700/50 bg-gradient-to-r from-gray-700 to-gray-800">
                            <h4 class="font-bold text-white flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                </svg>
                                Errores Detallados (${errores.length})
                            </h4>
                        </div>
                        <div class="max-h-96 overflow-y-auto">
            `;

            if (errores.length > 0) {
                errores.forEach((error, index) => {
                    const urlResuelta = urls_resueltas[error.url];
                    const tieneResolucion = urlResuelta && es_filtro_un_dia;
                    
                    contenido += `
                        <div class="p-6 border-b border-gray-700/50 hover:bg-gray-700/50 transition-all duration-200">
                            <div class="space-y-4">
                                <!-- Header del error -->
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-3">
                                        <span class="text-sm font-bold text-white">Error #${index + 1}</span>
                                        <span class="px-3 py-1 text-xs font-bold rounded-full bg-red-900 text-red-200">✗ Error</span>
                                        ${tieneResolucion ? `
                                            <span class="px-3 py-1 text-xs font-bold rounded-full bg-green-900 text-green-200">✅ Resuelto</span>
                                        ` : ''}
                                    </div>
                                    <div class="text-sm text-gray-400">
                                        ${error.fecha_ultima_error}
                                    </div>
                                </div>
                                
                                <!-- Detalles del error -->
                                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 text-sm">
                                    <div class="space-y-2">
                                        <div class="flex items-center space-x-2">
                                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
                                            </svg>
                                            <span class="font-medium text-gray-300">URL:</span>
                                            <a href="${error.url}" target="_blank" class="text-blue-400 hover:text-blue-300 underline truncate">${error.url}</a>
                                        </div>
                                        <div class="flex items-center space-x-2">
                                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                            </svg>
                                            <span class="font-medium text-gray-300">Error:</span>
                                            <span class="text-red-400">${error.error}</span>
                                        </div>
                                        ${error.precio_anterior ? `
                                            <div class="flex items-center space-x-2">
                                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                                </svg>
                                                <span class="font-medium text-gray-300">Precio anterior:</span>
                                                <span class="text-gray-400">€${error.precio_anterior}</span>
                                            </div>
                                        ` : ''}
                                        ${error.variante ? `
                                            <div class="flex items-center space-x-2">
                                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                                                </svg>
                                                <span class="font-medium text-gray-300">Variante:</span>
                                                <span class="text-gray-400">${error.variante}</span>
                                            </div>
                                        ` : ''}
                                        <div class="flex items-center space-x-2">
                                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                            </svg>
                                            <span class="font-medium text-gray-300">Peticiones solicitadas:</span>
                                            <span class="text-blue-400 font-bold">${error.peticiones}</span>
                                        </div>
                                        ${error.peticiones > 1 ? `
                                            <div class="flex items-center space-x-2">
                                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                                <span class="font-medium text-gray-300">Primera vez:</span>
                                                <span class="text-gray-400">${error.fecha_primera_error}</span>
                                            </div>
                                        ` : ''}
                                    </div>
                                    
                                    <!-- Información de resolución -->
                                    ${tieneResolucion ? `
                                        <div class="bg-gradient-to-br from-green-900/20 to-emerald-800/20 p-4 rounded-xl border border-green-700/50">
                                            <h5 class="font-bold text-green-200 mb-2 flex items-center">
                                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                </svg>
                                                ✅ URL Resuelta
                                            </h5>
                                            <div class="space-y-2 text-sm">
                                                <div class="flex items-center space-x-2">
                                                    <span class="font-medium text-green-300">Fecha resolución:</span>
                                                    <span class="text-green-200">${urlResuelta.fecha_resolucion}</span>
                                                </div>
                                                ${urlResuelta.precio_nuevo ? `
                                                    <div class="flex items-center space-x-2">
                                                        <span class="font-medium text-green-300">Precio nuevo:</span>
                                                        <span class="text-green-200 font-bold">€${urlResuelta.precio_nuevo}</span>
                                                    </div>
                                                ` : ''}
                                                <div class="flex items-center space-x-2">
                                                    <span class="font-medium text-green-300">Ejecución ID:</span>
                                                    <span class="text-green-200">#${urlResuelta.ejecucion_id}</span>
                                                </div>
                                            </div>
                                        </div>
                                    ` : ''}
                                </div>
                                
                                <!-- Botones de acción -->
                                <div class="flex flex-wrap gap-2 mt-4 pt-4 border-t border-gray-700/50">
                                    ${error.oferta_id ? `
                                        <button onclick="window.open('/panel-privado/ofertas/${error.oferta_id}/edit', '_blank')" 
                                                class="inline-flex items-center px-3 py-2 text-xs bg-blue-600 hover:bg-blue-700 text-white rounded-md transition-colors">
                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                            </svg>
                                            Editar Oferta
                                        </button>
                                    ` : ''}
                                    ${error.producto_slug && error.producto_categoria_slugs ? `
                                        <button onclick="window.open('/${error.producto_categoria_slugs}/${error.producto_slug}', '_blank')" 
                                                class="inline-flex items-center px-3 py-2 text-xs bg-green-600 hover:bg-green-700 text-white rounded-md transition-colors">
                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                            </svg>
                                            Ver Producto
                                        </button>
                                    ` : ''}
                                    ${error.url ? `
                                        <button onclick="window.open('${error.url}', '_blank')" 
                                                class="inline-flex items-center px-3 py-2 text-xs bg-orange-600 hover:bg-orange-700 text-white rounded-md transition-colors">
                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                            </svg>
                                            URL Original
                                        </button>
                                    ` : ''}
                                </div>
                            </div>
                        </div>
                    `;
                });
            } else {
                contenido += `
                    <div class="p-12 text-center">
                        <div class="w-16 h-16 bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                        <h4 class="text-lg font-medium text-white mb-2">¡Excelente!</h4>
                        <p class="text-gray-400">No se han registrado errores para ${tienda} en este período de tiempo.</p>
                    </div>
                `;
            }

            contenido += `
                        </div>
                    </div>
                </div>
            `;
            
            document.getElementById('errores-tienda-contenido').innerHTML = contenido;
        }

        function mostrarErrorErroresTienda(mensaje) {
            document.getElementById('errores-tienda-contenido').innerHTML = `
                <div class="bg-gradient-to-br from-red-900/20 to-pink-800/20 p-8 rounded-2xl border border-red-700/50">
                    <div class="flex items-center justify-center mb-4">
                        <div class="w-16 h-16 bg-red-900 rounded-full flex items-center justify-center">
                            <svg class="w-8 h-8 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                            </svg>
                        </div>
                    </div>
                    <p class="text-red-300 text-center font-medium">${mensaje}</p>
                </div>
            `;
        }

        // Variables globales para los gráficos
        let graficoHorasChart = null;
        let graficoDiasChart = null;
        let graficoHorasInicializado = false;
        let graficoDiasInicializado = false;
        let datosPorHoraPorTienda = @json($datosPorHoraPorTienda ?? []);
        let tiendasEnDatos = @json($tiendasEnDatos ?? []);

        function initGraficoHoras() {
            if (graficoHorasInicializado) {
                return;
            }

            const datosPorHora = @json($datosPorHora);
            const labelsHoras = [];
            const valuesOfertas = [];
            const valuesCorrectas = [];
            const valuesErrores = [];

            for (let i = 0; i < 24; i++) {
                labelsHoras.push(i.toString().padStart(2, '0') + ':00');
                const horaData = datosPorHora[i] || { total_ofertas: 0, total_correctas: 0, total_errores: 0 };
                valuesOfertas.push(horaData.total_ofertas);
                valuesCorrectas.push(horaData.total_correctas);
                valuesErrores.push(horaData.total_errores);
            }

            const ctxHoras = document.getElementById('graficoHoras').getContext('2d');
            graficoHorasChart = new Chart(ctxHoras, {
                type: 'bar',
                data: {
                    labels: labelsHoras,
                    datasets: [
                        {
                            label: 'Total Ofertas',
                            data: valuesOfertas,
                            backgroundColor: 'rgba(147, 51, 234, 0.5)',
                            borderColor: 'rgba(147, 51, 234, 1)',
                            borderWidth: 1
                        },
                        {
                            label: 'Correctas',
                            data: valuesCorrectas,
                            backgroundColor: 'rgba(34, 197, 94, 0.5)',
                            borderColor: 'rgba(34, 197, 94, 1)',
                            borderWidth: 1
                        },
                        {
                            label: 'Errores',
                            data: valuesErrores,
                            backgroundColor: 'rgba(239, 68, 68, 0.5)',
                            borderColor: 'rgba(239, 68, 68, 1)',
                            borderWidth: 1
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    layout: {
                        padding: {
                            top: 10,
                            bottom: 10
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            labels: {
                                color: '#ffffff'
                            }
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false,
                            callbacks: {
                                title: function(context) {
                                    return 'Hora: ' + context[0].label;
                                },
                                label: function(context) {
                                    const label = context.dataset.label || '';
                                    const value = context.parsed.y;
                                    return label + ': ' + value;
                                }
                            }
                        }
                    },
                    interaction: {
                        mode: 'index',
                        intersect: false
                    }
                }
            });

            graficoHorasInicializado = true;
        }

        function initGraficoDias() {
            if (graficoDiasInicializado) {
                return;
            }

            const datosPorDia = @json($datosPorDia);
            const labelsDias = [];
            const valuesOfertasDias = [];
            const valuesCorrectasDias = [];
            const valuesErroresDias = [];

            Object.keys(datosPorDia).forEach(dia => {
                const fecha = new Date(dia);
                labelsDias.push(fecha.toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit' }));
                valuesOfertasDias.push(datosPorDia[dia].total_ofertas);
                valuesCorrectasDias.push(datosPorDia[dia].total_correctas);
                valuesErroresDias.push(datosPorDia[dia].total_errores);
            });

            const ctxDias = document.getElementById('graficoDias').getContext('2d');
            graficoDiasChart = new Chart(ctxDias, {
                type: 'line',
                data: {
                    labels: labelsDias,
                    datasets: [
                        {
                            label: 'Total Ofertas',
                            data: valuesOfertasDias,
                            backgroundColor: 'rgba(147, 51, 234, 0.1)',
                            borderColor: 'rgba(147, 51, 234, 1)',
                            borderWidth: 2,
                            fill: false,
                            tension: 0.4
                        },
                        {
                            label: 'Correctas',
                            data: valuesCorrectasDias,
                            backgroundColor: 'rgba(34, 197, 94, 0.1)',
                            borderColor: 'rgba(34, 197, 94, 1)',
                            borderWidth: 2,
                            fill: false,
                            tension: 0.4
                        },
                        {
                            label: 'Errores',
                            data: valuesErroresDias,
                            backgroundColor: 'rgba(239, 68, 68, 0.1)',
                            borderColor: 'rgba(239, 68, 68, 1)',
                            borderWidth: 2,
                            fill: false,
                            tension: 0.4
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    layout: {
                        padding: {
                            top: 10,
                            bottom: 10
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            labels: {
                                color: '#ffffff'
                            }
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false,
                            callbacks: {
                                title: function(context) {
                                    return 'Día: ' + context[0].label;
                                },
                                label: function(context) {
                                    const label = context.dataset.label || '';
                                    const value = context.parsed.y;
                                    return label + ': ' + value;
                                }
                            }
                        }
                    },
                    interaction: {
                        mode: 'index',
                        intersect: false
                    }
                }
            });

            graficoDiasInicializado = true;
        }

        function toggleGraficoHoras() {
            const contenido = document.getElementById('contenidoGraficoHoras');
            const icono = document.getElementById('iconoGraficoHoras');
            const abrir = contenido.classList.contains('hidden');

            if (abrir) {
                contenido.classList.remove('hidden');
                icono.style.transform = 'rotate(180deg)';
                initGraficoHoras();
                if (graficoHorasChart) {
                    setTimeout(() => graficoHorasChart.resize(), 0);
                }
            } else {
                contenido.classList.add('hidden');
                icono.style.transform = 'rotate(0deg)';
            }
        }

        function toggleGraficoDias() {
            const contenido = document.getElementById('contenidoGraficoDias');
            const icono = document.getElementById('iconoGraficoDias');
            const abrir = contenido.classList.contains('hidden');

            if (abrir) {
                contenido.classList.remove('hidden');
                icono.style.transform = 'rotate(180deg)';
                initGraficoDias();
                if (graficoDiasChart) {
                    setTimeout(() => graficoDiasChart.resize(), 0);
                }
            } else {
                contenido.classList.add('hidden');
                icono.style.transform = 'rotate(0deg)';
            }
        }

        function toggleLimitacionesApi() {
            const contenido = document.getElementById('contenidoLimitacionesApi');
            const icono = document.getElementById('iconoLimitacionesApi');
            const abrir = contenido.classList.contains('hidden');
            contenido.classList.toggle('hidden', !abrir);
            icono.style.transform = abrir ? 'rotate(180deg)' : 'rotate(0deg)';
        }

        function toggleEstadisticasTienda() {
            const contenido = document.getElementById('contenidoEstadisticasTienda');
            const icono = document.getElementById('iconoEstadisticasTienda');
            const abrir = contenido.classList.contains('hidden');
            contenido.classList.toggle('hidden', !abrir);
            icono.style.transform = abrir ? 'rotate(180deg)' : 'rotate(0deg)';
        }

        // Función para aplicar filtros rápidos de fecha
        function aplicarFiltroRapido(tipo) {
            const hoy = new Date();
            let fechaDesde, fechaHasta;
            
            switch(tipo) {
                case 'hoy':
                    fechaDesde = fechaHasta = hoy.toISOString().split('T')[0];
                    break;
                case 'ayer':
                    const ayer = new Date(hoy);
                    ayer.setDate(hoy.getDate() - 1);
                    fechaDesde = fechaHasta = ayer.toISOString().split('T')[0];
                    break;
                case '7dias':
                    const hace7Dias = new Date(hoy);
                    hace7Dias.setDate(hoy.getDate() - 7);
                    fechaDesde = hace7Dias.toISOString().split('T')[0];
                    fechaHasta = hoy.toISOString().split('T')[0];
                    break;
                case '30dias':
                    const hace30Dias = new Date(hoy);
                    hace30Dias.setDate(hoy.getDate() - 30);
                    fechaDesde = hace30Dias.toISOString().split('T')[0];
                    fechaHasta = hoy.toISOString().split('T')[0];
                    break;
                case '90dias':
                    const hace90Dias = new Date(hoy);
                    hace90Dias.setDate(hoy.getDate() - 90);
                    fechaDesde = hace90Dias.toISOString().split('T')[0];
                    fechaHasta = hoy.toISOString().split('T')[0];
                    break;
                case '180dias':
                    const hace180Dias = new Date(hoy);
                    hace180Dias.setDate(hoy.getDate() - 180);
                    fechaDesde = hace180Dias.toISOString().split('T')[0];
                    fechaHasta = hoy.toISOString().split('T')[0];
                    break;
                case '1año':
                    const hace1Año = new Date(hoy);
                    hace1Año.setFullYear(hoy.getFullYear() - 1);
                    fechaDesde = hace1Año.toISOString().split('T')[0];
                    fechaHasta = hoy.toISOString().split('T')[0];
                    break;
                case 'siempre':
                    fechaDesde = '';
                    fechaHasta = '';
                    break;
                default:
                    return;
            }
            
            // Construir URL con parámetros
            const url = new URL(window.location);
            url.searchParams.set('filtro_rapido', tipo);
            url.searchParams.set('fecha_desde', fechaDesde);
            url.searchParams.set('fecha_hasta', fechaHasta);
            
            // Actualizar el valor del campo oculto
            document.getElementById('filtro_rapido').value = tipo;
            
            // Mantener otros parámetros existentes
            const horaDesde = document.querySelector('input[name="hora_desde"]').value;
            const horaHasta = document.querySelector('input[name="hora_hasta"]').value;
            const busqueda = document.querySelector('input[name="buscar"]').value;
            
            if (horaDesde) url.searchParams.set('hora_desde', horaDesde);
            if (horaHasta) url.searchParams.set('hora_hasta', horaHasta);
            if (busqueda) url.searchParams.set('buscar', busqueda);

            const tipoEjecucion = document.getElementById('filtro-tipo-ejecucion')?.value;
            if (tipoEjecucion && tipoEjecucion !== 'todas') {
                url.searchParams.set('tipo_ejecucion', tipoEjecucion);
            } else {
                url.searchParams.delete('tipo_ejecucion');
            }
            
            // Redirigir a la nueva URL
            window.location.href = url.toString();
        }

        // Función para mostrar/ocultar filtros
        function toggleFiltros() {
            const contenidoFiltros = document.getElementById('contenidoFiltros');
            const iconoFiltros = document.getElementById('iconoFiltros');
            
            if (contenidoFiltros.classList.contains('hidden')) {
                contenidoFiltros.classList.remove('hidden');
                iconoFiltros.style.transform = 'rotate(180deg)';
            } else {
                contenidoFiltros.classList.add('hidden');
                iconoFiltros.style.transform = 'rotate(0deg)';
            }
        }

        // Event listeners para desactivar filtro rápido cuando se cambian fechas manualmente
        document.addEventListener('DOMContentLoaded', function() {
            // Scroll suave para enlaces internos
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function (e) {
                    e.preventDefault();
                    const target = document.querySelector(this.getAttribute('href'));
                    if (target) {
                        target.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                });
            });
            // Verificar si hay filtros activos y mantener el desplegable abierto
            const urlParams = new URLSearchParams(window.location.search);
            const tieneFiltros = urlParams.has('filtro_rapido') || urlParams.has('fecha_desde') || 
                                urlParams.has('fecha_hasta') || urlParams.has('hora_desde') || 
                                urlParams.has('hora_hasta') || urlParams.has('buscar');
            
            if (tieneFiltros) {
                const contenidoFiltros = document.getElementById('contenidoFiltros');
                const iconoFiltros = document.getElementById('iconoFiltros');
                
                if (contenidoFiltros && iconoFiltros) {
                    contenidoFiltros.classList.remove('hidden');
                    iconoFiltros.style.transform = 'rotate(180deg)';
                }
            }
            
            const fechaDesdeInput = document.getElementById('fecha_desde');
            const fechaHastaInput = document.getElementById('fecha_hasta');
            
            if (fechaDesdeInput) {
                fechaDesdeInput.addEventListener('change', function() {
                    // Si se cambia la fecha manualmente, desactivar filtro rápido
                    document.getElementById('filtro_rapido').value = '';
                });
            }
            
            if (fechaHastaInput) {
                fechaHastaInput.addEventListener('change', function() {
                    // Si se cambia la fecha manualmente, desactivar filtro rápido
                    document.getElementById('filtro_rapido').value = '';
                });
            }
        });

        // Función para filtrar por tienda
        function filtrarPorTienda(tiendaSeleccionada) {
            if (!graficoHorasChart) return;
            
            const valuesOfertas = [];
            const valuesCorrectas = [];
            const valuesErrores = [];
            
            if (tiendaSeleccionada === 'todas') {
                // Usar los datos originales totales
                const datosPorHora = @json($datosPorHora);
                for (let i = 0; i < 24; i++) {
                    const horaData = datosPorHora[i] || { total_ofertas: 0, total_correctas: 0, total_errores: 0 };
                    valuesOfertas.push(horaData.total_ofertas);
                    valuesCorrectas.push(horaData.total_correctas);
                    valuesErrores.push(horaData.total_errores);
                }
            } else {
                // Mostrar datos solo de la tienda seleccionada
                for (let i = 0; i < 24; i++) {
                    const tiendaData = datosPorHoraPorTienda[tiendaSeleccionada] && datosPorHoraPorTienda[tiendaSeleccionada][i] ? datosPorHoraPorTienda[tiendaSeleccionada][i] : { total_ofertas: 0, total_correctas: 0, total_errores: 0 };
                    valuesOfertas.push(tiendaData.total_ofertas || 0);
                    valuesCorrectas.push(tiendaData.total_correctas || 0);
                    valuesErrores.push(tiendaData.total_errores || 0);
                }
            }
            
            // Actualizar los datos del gráfico
            graficoHorasChart.data.datasets[0].data = valuesOfertas;
            graficoHorasChart.data.datasets[1].data = valuesCorrectas;
            graficoHorasChart.data.datasets[2].data = valuesErrores;
            
            graficoHorasChart.update();
        }

        // Función para cargar estadísticas avanzadas
        function cargarEstadisticasAvanzadas() {
            // Obtener parámetros de filtro actuales
            const urlParams = new URLSearchParams(window.location.search);
            const fechaDesde = urlParams.get('fecha_desde') || '';
            const fechaHasta = urlParams.get('fecha_hasta') || '';
            // Si hay fechas manuales, no enviar filtro_rapido para que tenga prioridad
            const params = {
                fecha_desde: fechaDesde,
                fecha_hasta: fechaHasta,
                hora_desde: urlParams.get('hora_desde') || '',
                hora_hasta: urlParams.get('hora_hasta') || ''
            };
            // Solo enviar filtro_rapido si no hay fechas manuales
            const filtroRapido = urlParams.get('filtro_rapido');
            if (filtroRapido && !fechaDesde && !fechaHasta) {
                params.filtro_rapido = filtroRapido;
            } else if (!fechaDesde && !fechaHasta) {
                params.filtro_rapido = 'hoy'; // Por defecto solo si no hay fechas
            }

            // Mostrar loading en ambos botones
            const btnApi = document.getElementById('btn-actualizar-api');
            const btnTiendas = document.getElementById('btn-actualizar-tiendas');
            const contenidoApi = document.getElementById('contenido-limitaciones-api');
            const contenidoTiendas = document.getElementById('contenido-estadisticas-tienda');
            
            btnApi.disabled = true;
            btnTiendas.disabled = true;
            btnApi.innerHTML = '<svg class="w-4 h-4 inline mr-2 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>Cargando...';
            btnTiendas.innerHTML = '<svg class="w-4 h-4 inline mr-2 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>Cargando...';
            
            contenidoApi.innerHTML = '<div class="text-center py-8"><div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div><p class="mt-2 text-gray-400">Cargando datos...</p></div>';
            contenidoTiendas.innerHTML = '<div class="text-center py-8"><div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div><p class="mt-2 text-gray-400">Cargando datos...</p></div>';

            // Construir URL con parámetros
            const url = new URL('{{ route("admin.ofertas.scraper.ejecuciones.estadisticas-avanzadas") }}', window.location.origin);
            Object.keys(params).forEach(key => {
                if (params[key] !== undefined && params[key] !== null && params[key] !== '') {
                    url.searchParams.set(key, params[key]);
                }
            });

            fetch(url.toString())
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        renderizarLimitacionesAPI(data.estadisticasPorAPI);
                        renderizarEstadisticasTienda(data.estadisticasPorTienda);
                    } else {
                        mostrarError('Error al cargar las estadísticas');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    mostrarError('Error de conexión al cargar las estadísticas');
                })
                .finally(() => {
                    btnApi.disabled = false;
                    btnTiendas.disabled = false;
                    btnApi.innerHTML = '<svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>Actualizar';
                    btnTiendas.innerHTML = '<svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>Actualizar';
                });
        }

        function renderizarLimitacionesAPI(estadisticasPorAPI) {
            const contenido = document.getElementById('contenido-limitaciones-api');
            
            if (!estadisticasPorAPI || Object.keys(estadisticasPorAPI).length === 0) {
                contenido.innerHTML = `
                    <div class="text-center py-8">
                        <div class="text-gray-500 text-lg">📝 No hay APIs configuradas</div>
                        <p class="text-sm text-gray-400 mt-2">
                            No se encontraron ejecuciones con APIs configuradas en el período seleccionado.
                        </p>
                    </div>
                `;
                return;
            }

            const paletaApi = [
                {bg: 'from-blue-900/20 to-blue-800/20', border: 'border-blue-700/50', text: 'text-blue-200', text_light: 'text-blue-300', icon_bg: 'bg-blue-600'},
                {bg: 'from-green-900/20 to-green-800/20', border: 'border-green-700/50', text: 'text-green-200', text_light: 'text-green-300', icon_bg: 'bg-green-600'},
                {bg: 'from-purple-900/20 to-purple-800/20', border: 'border-purple-700/50', text: 'text-purple-200', text_light: 'text-purple-300', icon_bg: 'bg-purple-600'},
                {bg: 'from-amber-900/20 to-amber-800/20', border: 'border-amber-700/50', text: 'text-amber-200', text_light: 'text-amber-300', icon_bg: 'bg-amber-600'},
                {bg: 'from-rose-900/20 to-rose-800/20', border: 'border-rose-700/50', text: 'text-rose-200', text_light: 'text-rose-300', icon_bg: 'bg-rose-600'}
            ];
            const colorDefecto = {bg: 'from-gray-900/20 to-gray-800/20', border: 'border-gray-700/50', text: 'text-gray-200', text_light: 'text-gray-300', icon_bg: 'bg-gray-600'};

            let html = '<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">';
            let i = 0;
            for (const [apiBase, datos] of Object.entries(estadisticasPorAPI)) {
                const color = paletaApi[i % paletaApi.length];
                const iniciales = apiBase.substring(0, 2).toUpperCase();
                
                html += `
                    <div class="bg-gradient-to-br ${color.bg} p-6 rounded-lg border ${color.border}">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-8 h-8 ${color.icon_bg} rounded-lg flex items-center justify-center">
                                <span class="text-white font-bold text-sm">${iniciales}</span>
                            </div>
                            <h4 class="text-lg font-semibold ${color.text}">${apiBase}</h4>
                        </div>
                        <div class="space-y-3">
                            <div class="flex justify-between items-center">
                                <span class="text-sm ${color.text_light}">Total peticiones:</span>
                                <span class="font-semibold ${color.text}">${Number(datos.total_peticiones).toLocaleString()} peticiones</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-red-300">Errores:</span>
                                <span class="font-semibold text-red-400">${Number(datos.errores || 0).toLocaleString()} (${Number(datos.porcentaje_errores || 0).toFixed(2)}%)</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm ${color.text_light}">% del total:</span>
                                <span class="font-semibold ${color.text}">${Number(datos.porcentaje_total).toFixed(2)}%</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm ${color.text_light}">Tiendas:</span>
                                <span class="font-semibold ${color.text}">${datos.cantidad_tiendas} tiendas</span>
                            </div>
                        </div>
                        <div class="mt-4 p-3 bg-green-900/20 border border-green-700/50 rounded-lg">
                            <p class="text-sm text-green-300 font-medium">✅ API configurada y funcionando.</p>
                        </div>
                    </div>
                `;
                i++;
            }
            html += '</div>';
            contenido.innerHTML = html;
        }

        function renderizarEstadisticasTienda(estadisticasPorTienda) {
            const contenido = document.getElementById('contenido-estadisticas-tienda');
            
            if (!estadisticasPorTienda || estadisticasPorTienda.length === 0) {
                contenido.innerHTML = `
                    <div class="text-center py-12">
                        <div class="w-16 h-16 bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        </div>
                        <h4 class="text-lg font-medium text-white mb-2">No hay datos</h4>
                        <p class="text-gray-400">No se encontraron estadísticas de tiendas para este período de tiempo.</p>
                    </div>
                `;
                return;
            }

            let html = `
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-700/50">
                                <th class="text-left py-3 px-4 font-semibold text-gray-300">Tienda</th>
                                <th class="text-left py-3 px-4 font-semibold text-gray-300">API</th>
                                <th class="text-center py-3 px-4 font-semibold text-gray-300">Intentos Scraper</th>
                                <th class="text-center py-3 px-4 font-semibold text-gray-300">1a Oferta</th>
                                <th class="text-center py-3 px-4 font-semibold text-gray-300">Total Solicitudes</th>
                                <th class="text-center py-3 px-4 font-semibold text-gray-300">Errores</th>
                                <th class="text-center py-3 px-4 font-semibold text-gray-300">% del Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-700/50">
            `;

            estadisticasPorTienda.forEach(stats => {
                const apiTienda = stats.api || null;
                const errores = stats.errores || 0;
                const porcentajeErrores = stats.porcentaje_errores || 0;
                const porcentajeTotal = Number(stats.porcentaje_total || 0).toFixed(2);
                
                html += `
                    <tr class="hover:bg-gray-700/50 transition-colors">
                        <td class="py-3 px-4">
                            <div class="flex items-center space-x-3">
                                <div class="w-8 h-8 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-lg flex items-center justify-center">
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                    </svg>
                                </div>
                                <span class="font-medium text-white">${stats.nombre}</span>
                            </div>
                        </td>
                        <td class="py-3 px-4">
                            ${apiTienda ? 
                                `<span class="px-2 py-1 text-xs font-medium rounded-full bg-blue-900 text-blue-200">${apiTienda}</span>` :
                                `<span class="px-2 py-1 text-xs font-medium rounded-full bg-gray-700 text-gray-400">Sin API</span>`
                            }
                        </td>
                        <td class="py-3 px-4 text-center">
                            <span class="text-lg font-bold text-blue-400">${Number(stats.intentos_scraper_normal || 0).toLocaleString()}</span>
                        </td>
                        <td class="py-3 px-4 text-center">
                            <span class="text-lg font-bold text-purple-400">${Number(stats.intentos_primera_oferta || 0).toLocaleString()}</span>
                        </td>
                        <td class="py-3 px-4 text-center">
                            <span class="text-xl font-bold text-white">${Number(stats.total_intentos || 0).toLocaleString()}</span>
                        </td>
                        <td class="py-3 px-4 text-center">
                            <span class="text-lg font-bold text-red-400">${Number(errores).toLocaleString()}</span>
                            <span class="text-sm text-gray-400 ml-1">(${Number(porcentajeErrores).toFixed(2)}%)</span>
                        </td>
                        <td class="py-3 px-4 text-center">
                            <span class="text-sm font-medium text-gray-300">${porcentajeTotal}%</span>
                        </td>
                    </tr>
                `;
            });

            html += `
                        </tbody>
                    </table>
                </div>
            `;
            contenido.innerHTML = html;
        }

        function mostrarError(mensaje) {
            const contenidoApi = document.getElementById('contenido-limitaciones-api');
            const contenidoTiendas = document.getElementById('contenido-estadisticas-tienda');
            const errorHtml = `
                <div class="text-center py-8">
                    <div class="text-red-500 text-lg">❌ ${mensaje}</div>
                </div>
            `;
            contenidoApi.innerHTML = errorHtml;
            contenidoTiendas.innerHTML = errorHtml;
        }
    </script>
</x-app-layout> 