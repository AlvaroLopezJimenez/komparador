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
                <div class="p-6 sm:p-8">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div class="flex items-center space-x-4">
                            <div class="p-3 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl shadow-lg">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-xl sm:text-2xl font-bold text-white">Historial de Ejecuciones</h2>
                                <p class="text-sm text-gray-400 mt-1">Registro completo de todas las ejecuciones del scraper</p>
                            </div>
                        </div>
                        <div class="flex flex-col sm:flex-row gap-3">
                            <a href="{{ route('admin.scraping.ejecucion-tiempo-real') }}" 
                               class="inline-flex items-center justify-center px-6 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-medium rounded-xl hover:from-blue-700 hover:to-indigo-700 transform hover:scale-105 transition-all duration-200 shadow-lg hover:shadow-xl">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                </svg>
                                <span class="hidden sm:inline">Ejecución en Tiempo Real</span>
                                <span class="sm:hidden">Tiempo Real</span>
                            </a>
                        </div>
                    </div>

                    <!-- Filtros Mejorados -->
                    <div class="mt-6 sm:mt-8">
                        <!-- Botón para mostrar/ocultar filtros -->
                        <div class="flex items-center justify-between mb-4">
                            <button type="button" onclick="toggleFiltros()" 
                                    class="flex items-center space-x-2 px-4 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-medium rounded-lg hover:from-blue-700 hover:to-indigo-700 transform hover:scale-105 transition-all duration-200 shadow-lg">
                                <svg id="iconoFiltros" class="w-5 h-5 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                                </svg>
                                <span>🔍 Filtros Avanzados</span>
                            </button>
                            
                            <!-- Indicador de filtros activos -->
                            @if($filtroRapido || $fechaDesde || $fechaHasta || $horaDesde || $horaHasta || $busqueda)
                                <div class="flex items-center space-x-2">
                                    <span class="text-sm text-green-400">✓ Filtros activos</span>
                                    <a href="{{ route('admin.ofertas.scraper.ejecuciones') }}" 
                                       class="px-3 py-1 bg-red-600 text-white text-xs rounded-md hover:bg-red-700 transition-colors">
                                        Limpiar
                                    </a>
                                </div>
                            @endif
                        </div>
                        
                        <!-- Contenido de filtros (oculto por defecto) -->
                        <div id="contenidoFiltros" class="hidden">
                            <form method="GET" class="space-y-4 bg-gray-700/30 rounded-xl p-6 border border-gray-600/50">
                                <input type="hidden" name="filtro_rapido" id="filtro_rapido" value="{{ $filtroRapido ?? 'hoy' }}">
                                
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
            </div>

            <!-- Estadísticas Generales -->
            <div class="bg-gray-800/80 backdrop-blur-sm rounded-2xl shadow-xl border border-gray-700/50 overflow-hidden mb-6">
                <div class="p-6 sm:p-8">
                    <h3 class="text-lg sm:text-xl font-bold text-white mb-6">📊 Estadísticas Generales</h3>
                    
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
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
                    </div>
                </div>
            </div>

            <!-- Gráficos -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <!-- Gráfico por Hora -->
                <div class="bg-gray-800/80 backdrop-blur-sm rounded-2xl shadow-xl border border-gray-700/50 overflow-hidden">
                    <div class="p-6 sm:p-8">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg sm:text-xl font-bold text-white">📈 Estadísticas por Hora del Día</h3>
                            <div class="flex items-center space-x-2">
                                <label class="text-sm text-gray-300 font-medium">🎯 Filtrar por tienda:</label>
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
                        </div>
                        <div class="relative" style="height: 300px;">
                            <canvas id="graficoHoras"></canvas>
                        </div>
                    </div>
                </div>
                
                <!-- Gráfico por Día -->
                <div class="bg-gray-800/80 backdrop-blur-sm rounded-2xl shadow-xl border border-gray-700/50 overflow-hidden">
                    <div class="p-6 sm:p-8">
                        <h3 class="text-lg sm:text-xl font-bold text-white mb-4">📅 Estadísticas por Día</h3>
                        <div class="relative" style="height: 300px;">
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
                                <tbody class="divide-y divide-gray-700/50">
                                    @foreach($erroresPorTienda as $tienda => $errores)
                                        @php
                                            $porcentaje = $totalErrores > 0 ? ($errores / $totalErrores) * 100 : 0;
                                            $apiTienda = $tiendasConApi[$tienda] ?? null;
                                        @endphp
                                        <tr class="hover:bg-gray-700/50 transition-colors">
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
                                                <span class="text-xl font-bold text-red-400">{{ number_format($errores) }}</span>
                                            </td>
                                            <td class="py-3 px-4 text-center">
                                                <span class="text-sm font-medium text-gray-300">{{ number_format($porcentaje, 1) }}%</span>
                                            </td>
                                            <td class="py-3 px-4">
                                                <div class="w-full bg-gray-700 rounded-full h-3">
                                                    <div class="bg-gradient-to-r from-red-500 to-pink-600 h-3 rounded-full transition-all duration-300" 
                                                         style="width: {{ min($porcentaje, 100) }}%"></div>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
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
                <div class="p-6 sm:p-8">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg sm:text-xl font-bold text-white">🔒 Limitaciones de API</h3>
                        <button onclick="cargarEstadisticasAvanzadas()" 
                                id="btn-actualizar-api"
                                class="px-4 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-medium rounded-lg hover:from-blue-700 hover:to-indigo-700 transform hover:scale-105 transition-all duration-200 shadow-lg">
                            <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                            Actualizar
                        </button>
                    </div>
                    <p class="text-sm text-gray-400 mb-4">
                        Control de peticiones a APIs externas según ejecuciones reales en el período filtrado.
                    </p>

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
                <div class="p-6 sm:p-8">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-lg sm:text-xl font-bold text-white">📊 Estadísticas por Tienda</h3>
                        <button onclick="cargarEstadisticasAvanzadas()" 
                                id="btn-actualizar-tiendas"
                                class="px-4 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-medium rounded-lg hover:from-blue-700 hover:to-indigo-700 transform hover:scale-105 transition-all duration-200 shadow-lg">
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
            <div class="bg-gray-800/80 backdrop-blur-sm rounded-2xl shadow-xl border border-gray-700/50 overflow-hidden">
                <div class="p-6 sm:p-8 border-b border-gray-700/50">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg sm:text-xl font-bold text-white">Ejecuciones Recientes</h3>
                        <div class="text-sm text-gray-400">
                            {{ $ejecuciones->total() }} ejecuciones
                        </div>
                    </div>
                </div>
                
                @if($ejecuciones->count() > 0)
                    <div class="divide-y divide-gray-700/50">
                        @foreach($ejecuciones as $ejecucion)
                            <div class="p-6 sm:p-8 hover:bg-gray-700/50 transition-all duration-200">
                                <!-- Header de Ejecución -->
                                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-4">
                                    <div class="flex items-start space-x-4">
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
                                            <div class="flex items-center space-x-3 mb-2">
                                                <h4 class="text-lg font-bold text-white">
                                                    Ejecución #{{ $ejecucion->id }}
                                                </h4>
                                                <span class="px-3 py-1 text-xs font-medium rounded-full {{ $ejecucion->fin ? 'bg-green-900 text-green-200' : 'bg-yellow-900 text-yellow-200' }}">
                                                    {{ $ejecucion->fin ? 'Completada' : 'En Progreso' }}
                                                </span>
                                                @if($ejecucion->nombre === 'actualizar_primera_oferta')
                                                    <span class="px-3 py-1 text-xs font-medium rounded-full bg-purple-900 text-purple-200">
                                                        Primera oferta por producto
                                                    </span>
                                                @else
                                                    <span class="px-3 py-1 text-xs font-medium rounded-full bg-indigo-900 text-indigo-200">
                                                        Ofertas tiempo actualización cumplido
                                                    </span>
                                                @endif
                                            </div>
                                            <div class="flex flex-col sm:flex-row sm:items-center space-y-2 sm:space-y-0 sm:space-x-6 text-sm text-gray-400">
                                                <div class="flex items-center space-x-2">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                    </svg>
                                                    <span>Inicio: {{ $ejecucion->inicio->format('d/m/Y H:i:s') }}</span>
                                                </div>
                                                @if($ejecucion->fin)
                                                    <div class="flex items-center space-x-2">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                        </svg>
                                                        <span>Fin: {{ $ejecucion->fin->format('d/m/Y H:i:s') }}</span>
                                                    </div>
                                                    <div class="flex items-center space-x-2">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                                        </svg>
                                                        <span>Duración: {{ $ejecucion->inicio->diffForHumans($ejecucion->fin, true) }}</span>
                                                    </div>
                                                @else
                                                    <div class="flex items-center space-x-2 text-yellow-400">
                                                        <svg class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                                        </svg>
                                                        <span>En progreso...</span>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Estadísticas Rápidas -->
                                    <div class="flex flex-col sm:flex-row items-end space-y-3 sm:space-y-0 sm:space-x-6">
                                        <div class="text-center sm:text-right">
                                            <div class="text-2xl font-bold text-white">
                                                {{ $ejecucion->total_guardado ?? 0 }}/{{ $ejecucion->total ?? 0 }}
                                            </div>
                                            <div class="text-xs text-gray-400">
                                                Actualizadas
                                            </div>
                                        </div>
                                        
                                        @if($ejecucion->total_errores > 0)
                                            <div class="text-center sm:text-right">
                                                <div class="text-2xl font-bold text-red-400">
                                                    {{ $ejecucion->total_errores }}
                                                </div>
                                                <div class="text-xs text-gray-400">
                                                    Errores
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                                

                                
                                <!-- Acciones -->
                                <div class="flex flex-col sm:flex-row gap-3">
                                    <button onclick="verDetalles({{ $ejecucion->id }})" 
                                            class="flex-1 sm:flex-none inline-flex items-center justify-center px-6 py-3 bg-gradient-to-r from-blue-500 to-indigo-600 text-white font-medium rounded-xl hover:from-blue-600 hover:to-indigo-700 transform hover:scale-105 transition-all duration-200 shadow-lg">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                        Ver Detalles
                                    </button>
                                    <form method="POST" action="{{ route('admin.ofertas.scraper.ejecuciones.eliminar', $ejecucion) }}" 
                                        onsubmit="return confirm('¿Estás seguro de que quieres eliminar esta ejecución?')" 
                                        class="flex-1 sm:flex-none">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" 
                                                class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-3 bg-gradient-to-r from-red-500 to-pink-600 text-white font-medium rounded-xl hover:from-red-600 hover:to-pink-700 transform hover:scale-105 transition-all duration-200 shadow-lg">
                                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                            Eliminar
                                        </button>
                                    </form>
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
                        <p class="text-gray-400 max-w-md mx-auto">Aún no se han realizado ejecuciones del scraper. Cuando ejecutes el scraper, aquí aparecerá el historial completo.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Modal de Detalles Mejorado -->
    <div id="modal-detalles" class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50 p-4">
        <div class="bg-gray-800 rounded-2xl shadow-2xl border border-gray-700/50 w-full max-w-4xl max-h-[90vh] overflow-hidden transform transition-all duration-300">
            <div class="p-6 sm:p-8 border-b border-gray-700/50">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl sm:text-2xl font-bold text-white">Detalles de la Ejecución</h2>
                    <button onclick="cerrarModal()" class="p-2 text-gray-400 hover:text-gray-300 hover:bg-gray-700 rounded-lg transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>
            <div class="p-6 sm:p-8 overflow-y-auto max-h-[calc(90vh-120px)]">
                <div id="detalles-contenido">
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
    
    <script>
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
                    mostrarDetallesEjecucion(data, ejecucionId);
                })
                .catch(error => {
                    console.error('Error:', error);
                    mostrarError('Error de conexión al cargar los detalles');
                });
        }

        function mostrarDetallesEjecucion(data, ejecucionId) {
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

            const exitosas = data.filter(item => item.success).length;
            const errores = data.filter(item => !item.success).length;

            let contenido = `
                <div class="space-y-8">
                    <!-- Información General -->
                    <div class="bg-gradient-to-br from-blue-900/20 to-indigo-800/20 p-6 rounded-2xl border border-blue-700/50">
                        <h3 class="font-bold text-blue-100 mb-4 flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Información General
                        </h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                            <div class="flex items-center space-x-2">
                                <span class="font-semibold text-blue-200">ID de Ejecución:</span>
                                <span class="text-blue-300">#${ejecucionId}</span>
                            </div>
                            <div class="flex items-center space-x-2">
                                <span class="font-semibold text-blue-200">Total de Ofertas:</span>
                                <span class="text-blue-300">${data.length}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Estadísticas -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div class="bg-gradient-to-br from-gray-700 to-gray-800 p-6 rounded-2xl border border-gray-600/50 text-center">
                            <div class="text-3xl font-bold text-white mb-2">${data.length}</div>
                            <div class="text-sm text-gray-400 font-medium">Total Ofertas</div>
                        </div>
                        <div class="bg-gradient-to-br from-green-900/20 to-emerald-800/20 p-6 rounded-2xl border border-green-700/50 text-center">
                            <div class="text-3xl font-bold text-green-400 mb-2">${exitosas}</div>
                            <div class="text-sm text-green-300 font-medium">Exitosas</div>
                        </div>
                        <div class="bg-gradient-to-br from-red-900/20 to-pink-800/20 p-6 rounded-2xl border border-red-700/50 text-center">
                            <div class="text-3xl font-bold text-red-400 mb-2">${errores}</div>
                            <div class="text-sm text-red-300 font-medium">Errores</div>
                        </div>
                    </div>

                    <!-- Resultados Detallados -->
                    <div class="bg-gray-800 border border-gray-700/50 rounded-2xl overflow-hidden shadow-lg">
                        <div class="px-6 py-4 border-b border-gray-700/50 bg-gradient-to-r from-gray-700 to-gray-800">
                            <h3 class="font-bold text-white flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                </svg>
                                Resultados Detallados (${data.length} ofertas)
                            </h3>
                        </div>
                        <div class="max-h-96 overflow-y-auto">
            `;

            data.forEach((item, index) => {
                const esExito = item.success;
                const bgColor = esExito ? 'bg-gradient-to-r from-green-900/10 to-emerald-900/10' : 'bg-gradient-to-r from-red-900/10 to-pink-900/10';
                const borderColor = esExito ? 'border-green-700/50' : 'border-red-700/50';
                
                contenido += `
                    <div class="p-6 border-b border-gray-700/50 ${bgColor} ${borderColor} hover:bg-opacity-75 transition-all duration-200">
                        <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
                            <div class="flex-1 space-y-3">
                                <div class="flex items-center space-x-3">
                                    <span class="text-sm font-bold text-white">Oferta #${item.oferta_id || 'N/A'}</span>
                                    <span class="px-3 py-1 text-xs font-bold rounded-full ${esExito ? 'bg-green-900 text-green-200' : 'bg-red-900 text-red-200'}">${esExito ? '✓ Éxito' : '✗ Error'}</span>
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                                    <div class="flex items-center space-x-2">
                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                        </svg>
                                        <span class="font-medium text-gray-300">Tienda:</span>
                                        <span class="text-gray-400">${item.tienda_nombre || 'N/A'}</span>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
                                        </svg>
                                        <span class="font-medium text-gray-300">URL:</span>
                                        <a href="${item.url || '#'}" target="_blank" class="text-blue-400 hover:text-blue-300 underline truncate">${item.url || 'N/A'}</a>
                                    </div>
                                    ${item.proxy_ip ? `
                                    <div class="flex items-center space-x-2">
                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h3m8-12h3a2 2 0 012 2v4a2 2 0 01-2 2h-3m-8 4h8m-8-16h8m-8 0a2 2 0 00-2 2v12a2 2 0 002 2m8-16a2 2 0 012 2v12a2 2 0 01-2 2"></path>
                                        </svg>
                                        <span class="font-medium text-gray-300">Proxy IP:</span>
                                        <span class="text-gray-400 font-mono">${item.proxy_ip}</span>
                                    </div>
                                    ` : ''}
                                </div>
                `;

                if (esExito) {
                    contenido += `
                        <div class="flex items-center space-x-2 text-sm">
                            <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                            </svg>
                            <span class="font-medium text-gray-300">Precio:</span> 
                            <span class="line-through text-gray-500">€${item.precio_anterior || 'N/A'}</span> 
                            <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
                            </svg>
                            <span class="text-green-400 font-bold">€${item.precio_nuevo || 'N/A'}</span>
                        </div>
                    `;
                } else {
                    contenido += `
                        <div class="flex items-center space-x-2 text-sm">
                            <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                            </svg>
                            <span class="font-medium text-red-400">Error:</span> 
                            <span class="text-red-400">${item.error || 'Error desconocido'}</span>
                        </div>
                    `;
                }

                if (item.cambios_detectados) {
                    contenido += `
                        <div class="flex items-center space-x-2 text-sm">
                            <svg class="w-4 h-4 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                            </svg>
                            <span class="text-orange-400 font-medium">⚠️ Precio del producto actualizado</span>
                        </div>
                    `;
                }

                contenido += `
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

        // Variables globales para el gráfico de tiendas
        let graficoHorasChart = null;
        let datosPorHoraPorTienda = @json($datosPorHoraPorTienda ?? []);
        let tiendasEnDatos = @json($tiendasEnDatos ?? []);

        // Scripts para los gráficos
        document.addEventListener('DOMContentLoaded', function() {
            // Datos para el gráfico de horas
            const datosPorHora = @json($datosPorHora);
            const labelsHoras = [];
            const valuesOfertas = [];
            const valuesCorrectas = [];
            const valuesErrores = [];
            
            // Crear array completo de 24 horas
            for (let i = 0; i < 24; i++) {
                labelsHoras.push(i.toString().padStart(2, '0') + ':00');
                const horaData = datosPorHora[i] || { total_ofertas: 0, total_correctas: 0, total_errores: 0 };
                valuesOfertas.push(horaData.total_ofertas);
                valuesCorrectas.push(horaData.total_correctas);
                valuesErrores.push(horaData.total_errores);
            }
            
            // Gráfico de estadísticas por hora
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
            
            // Datos para el gráfico de días
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
            
            // Gráfico de estadísticas por día
            const ctxDias = document.getElementById('graficoDias').getContext('2d');
            new Chart(ctxDias, {
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
        });

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