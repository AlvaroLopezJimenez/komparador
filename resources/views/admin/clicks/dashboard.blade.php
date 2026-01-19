<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.dashboard') }}">
                <h2 class="font-semibold text-xl text-white leading-tight">Inicio -></h2>
            </a>
            <h2 class="font-semibold text-xl text-white leading-tight">Dashboard de Clicks</h2>
        </div>
    </x-slot>

         <div class="max-w-7xl mx-auto py-10 px-4 space-y-8 bg-gray-50 dark:bg-gray-900 rounded-lg shadow-md">
         
         <!-- Alerta de IPs sospechosas -->
         @if(count($ipsSospechosas) > 0)
             <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700 rounded-xl p-6">
                 <div class="flex items-center">
                     <div class="flex-shrink-0">
                         <svg class="h-6 w-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                             <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                         </svg>
                     </div>
                     <div class="ml-3">
                         <h3 class="text-lg font-medium text-red-800 dark:text-red-200">
                             ⚠️ IPs con comportamiento sospechoso detectadas
                         </h3>
                         <div class="mt-2 text-sm text-red-700 dark:text-red-300">
                             <p>Se han detectado <strong>{{ count($ipsSospechosas) }}</strong> IP(s) con actividad inusual:</p>
                             <ul class="mt-2 list-disc list-inside space-y-1">
                                 @foreach($ipsSospechosas as $ip)
                                     <li>
                                         <span class="font-mono">{{ $ip['ip'] }}</span> 
                                         ({{ $ip['total'] }} clicks) - 
                                         <span class="font-semibold {{ $ip['nivel'] === 'muy_sospechosa' ? 'text-red-600 dark:text-red-400' : 'text-yellow-600 dark:text-yellow-400' }}">
                                             {{ $ip['nivel'] === 'muy_sospechosa' ? 'Muy sospechosa' : 'Sospechosa' }}
                                         </span>
                                     </li>
                                 @endforeach
                             </ul>
                         </div>
                     </div>
                 </div>
             </div>
         @endif
         
         <!-- Filtros -->
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between p-6 cursor-pointer" onclick="toggleFiltros()">
                <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-200">🔍 Filtros de Búsqueda</h3>
                <div class="flex items-center gap-2">
                    <button type="button" onclick="event.stopPropagation(); mostrarModalAyuda()" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </button>
                    <svg id="iconoFiltros" class="w-5 h-5 text-gray-500 dark:text-gray-400 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </div>
            </div>
            
            <div id="contenidoFiltros" class="hidden px-6 pb-6">
            
            <form method="GET" action="{{ route('admin.clicks.dashboard') }}" class="space-y-4">
                <input type="hidden" name="filtro_rapido" id="filtro_rapido" value="{{ request('filtro_rapido', $filtroRapido ?? 'hoy') }}">
                <!-- Filtros rápidos de fecha -->
                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">📅 Filtros Rápidos:</label>
                    <div class="flex flex-wrap gap-2">
                        <button type="button" onclick="aplicarFiltroRapido('hoy')" 
                                class="px-3 py-1.5 text-xs font-medium rounded-md transition-colors {{ request('filtro_rapido', $filtroRapido ?? 'hoy') === 'hoy' ? 'bg-blue-600 text-white' : 'bg-white dark:bg-gray-600 text-gray-700 dark:text-gray-300 hover:bg-blue-50 dark:hover:bg-blue-900/20 border border-gray-300 dark:border-gray-500' }}">
                            🟢 Hoy
                        </button>
                        <button type="button" onclick="aplicarFiltroRapido('ayer')" 
                                class="px-3 py-1.5 text-xs font-medium rounded-md transition-colors {{ request('filtro_rapido', $filtroRapido ?? 'hoy') === 'ayer' ? 'bg-blue-600 text-white' : 'bg-white dark:bg-gray-600 text-gray-700 dark:text-gray-300 hover:bg-blue-50 dark:hover:bg-blue-900/20 border border-gray-300 dark:border-gray-500' }}">
                            📅 Ayer
                        </button>
                        <button type="button" onclick="aplicarFiltroRapido('7dias')" 
                                class="px-3 py-1.5 text-xs font-medium rounded-md transition-colors {{ request('filtro_rapido', $filtroRapido ?? 'hoy') === '7dias' ? 'bg-blue-600 text-white' : 'bg-white dark:bg-gray-600 text-gray-700 dark:text-gray-300 hover:bg-blue-50 dark:hover:bg-blue-900/20 border border-gray-300 dark:border-gray-500' }}">
                            📊 Últimos 7 días
                        </button>
                        <button type="button" onclick="aplicarFiltroRapido('30dias')" 
                                class="px-3 py-1.5 text-xs font-medium rounded-md transition-colors {{ request('filtro_rapido', $filtroRapido ?? 'hoy') === '30dias' ? 'bg-blue-600 text-white' : 'bg-white dark:bg-gray-600 text-gray-700 dark:text-gray-300 hover:bg-blue-50 dark:hover:bg-blue-900/20 border border-gray-300 dark:border-gray-500' }}">
                            📈 Últimos 30 días
                        </button>
                        <button type="button" onclick="aplicarFiltroRapido('90dias')" 
                                class="px-3 py-1.5 text-xs font-medium rounded-md transition-colors {{ request('filtro_rapido', $filtroRapido ?? 'hoy') === '90dias' ? 'bg-blue-600 text-white' : 'bg-white dark:bg-gray-600 text-gray-700 dark:text-gray-300 hover:bg-blue-50 dark:hover:bg-blue-900/20 border border-gray-300 dark:border-gray-500' }}">
                            📊 Últimos 90 días
                        </button>
                        <button type="button" onclick="aplicarFiltroRapido('180dias')" 
                                class="px-3 py-1.5 text-xs font-medium rounded-md transition-colors {{ request('filtro_rapido', $filtroRapido ?? 'hoy') === '180dias' ? 'bg-blue-600 text-white' : 'bg-white dark:bg-gray-600 text-gray-700 dark:text-gray-300 hover:bg-blue-50 dark:hover:bg-blue-900/20 border border-gray-300 dark:border-gray-500' }}">
                            📈 Últimos 180 días
                        </button>
                        <button type="button" onclick="aplicarFiltroRapido('1año')" 
                                class="px-3 py-1.5 text-xs font-medium rounded-md transition-colors {{ request('filtro_rapido', $filtroRapido ?? 'hoy') === '1año' ? 'bg-blue-600 text-white' : 'bg-white dark:bg-gray-600 text-gray-700 dark:text-gray-300 hover:bg-blue-50 dark:hover:bg-blue-900/20 border border-gray-300 dark:border-gray-500' }}">
                            📅 Último año
                        </button>
                        <button type="button" onclick="aplicarFiltroRapido('siempre')" 
                                class="px-3 py-1.5 text-xs font-medium rounded-md transition-colors {{ request('filtro_rapido', $filtroRapido ?? 'hoy') === 'siempre' ? 'bg-blue-600 text-white' : 'bg-white dark:bg-gray-600 text-gray-700 dark:text-gray-300 hover:bg-blue-50 dark:hover:bg-blue-900/20 border border-gray-300 dark:border-gray-500' }}">
                            🌍 Desde siempre
                        </button>
                    </div>
                </div>
                
                 <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                     <!-- Fecha desde -->
                     <div>
                         <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Fecha desde:</label>
                         <input type="date" name="fecha_desde" id="fecha_desde" value="{{ request('fecha_desde', $fechaDesde) }}" 
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                     </div>
                     
                     <!-- Fecha hasta -->
                     <div>
                         <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Fecha hasta:</label>
                         <input type="date" name="fecha_hasta" id="fecha_hasta" value="{{ request('fecha_hasta', $fechaHasta) }}" 
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                     </div>
                     
                     <!-- Hora desde -->
                     <div>
                         <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Hora desde:</label>
                         <input type="time" name="hora_desde" value="{{ request('hora_desde', $horaDesde) }}" 
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                     </div>
                     
                     <!-- Hora hasta -->
                     <div>
                         <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Hora hasta:</label>
                         <input type="time" name="hora_hasta" value="{{ request('hora_hasta', $horaHasta) }}" 
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                     </div>
                     
                     <!-- Elementos por página -->
                     <div>
                         <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Por página:</label>
                         <select name="por_pagina" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                             <option value="10" {{ request('por_pagina', $porPagina) == 10 ? 'selected' : '' }}>10</option>
                             <option value="20" {{ request('por_pagina', $porPagina) == 20 ? 'selected' : '' }}>20</option>
                             <option value="50" {{ request('por_pagina', $porPagina) == 50 ? 'selected' : '' }}>50</option>
                             <option value="100" {{ request('por_pagina', $porPagina) == 100 ? 'selected' : '' }}>100</option>
                         </select>
                     </div>
                 </div>
                 
                 <!-- Búsqueda -->
                 <div>
                     <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Búsqueda:</label>
                     <input type="text" name="busqueda" value="{{ request('busqueda', $busqueda) }}" placeholder="Producto, tienda, campaña..."
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                 </div>
                

                
                <div class="flex justify-between items-center">
                    <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors">
                        🔍 Buscar
                    </button>
                    
                    <a href="{{ route('admin.clicks.dashboard') }}" class="px-6 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600 focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-colors">
                        🔄 Limpiar
                    </a>
                </div>
            </form>
            </div>
        </div>

                 <!-- Estadísticas rápidas -->
         <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6 border border-gray-200 dark:border-gray-700">
             <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-200 mb-4">📊 Estadísticas</h3>
             
             <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                 <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg">
                     <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ number_format($estadisticas['totalClicks']) }}</div>
                     <div class="text-sm text-gray-600 dark:text-gray-400">Total Clicks</div>
                 </div>
                 <div class="bg-green-50 dark:bg-green-900/20 p-4 rounded-lg">
                     <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ number_format($estadisticas['totalProductos']) }}</div>
                     <div class="text-sm text-gray-600 dark:text-gray-400">Productos</div>
                 </div>
                 <div class="bg-purple-50 dark:bg-purple-900/20 p-4 rounded-lg">
                     <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ number_format($estadisticas['totalTiendas']) }}</div>
                     <div class="text-sm text-gray-600 dark:text-gray-400">Tiendas</div>
                 </div>
                 <div class="bg-orange-50 dark:bg-orange-900/20 p-4 rounded-lg">
                     <div class="text-2xl font-bold text-orange-600 dark:text-orange-400">{{ number_format($estadisticas['totalOfertas']) }}</div>
                     <div class="text-sm text-gray-600 dark:text-gray-400">Ofertas</div>
                 </div>
                 <div class="bg-emerald-50 dark:bg-emerald-900/20 p-4 rounded-lg">
                     <div class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ number_format($ipsNuevas['total']) }}</div>
                     <div class="text-sm text-gray-600 dark:text-gray-400">IPs Nuevas</div>
                 </div>
             </div>
         </div>

         <!-- Mapa de España con clicks -->
         <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6 border border-gray-200 dark:border-gray-700">
             <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-200 mb-4">🗺️ Mapa de Clicks en España</h3>
             
             @if($datosMapa['total_puntos'] > 0)
                 <div class="mb-4 text-sm text-gray-600 dark:text-gray-400">
                     <span class="font-medium">{{ $datosMapa['total_puntos'] }}</span> ubicaciones diferentes con 
                     <span class="font-medium">{{ $datosMapa['total_clicks'] }}</span> clicks en total
                 </div>
                 
                 <div id="mapaEspana" style="height: 500px; width: 100%; border-radius: 8px;"></div>
             @else
                 <div class="text-center py-12">
                     <div class="text-gray-500 dark:text-gray-400 text-lg">📍 No hay datos de ubicación para mostrar</div>
                     <p class="text-sm text-gray-400 dark:text-gray-500 mt-2">
                         Los clicks nuevos incluirán automáticamente información de geolocalización.
                     </p>
                 </div>
             @endif
         </div>

         <!-- Tabla de clicks -->
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-200">📋 Listado de Clicks</h3>
                <button id="btnEliminarSeleccionados" onclick="eliminarClicksSeleccionados()" disabled
                        class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition-colors disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:bg-red-600">
                    🗑️ Eliminar Seleccionados
                </button>
            </div>
            
            @if($clicks->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-2 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider w-10">
                                    <input type="checkbox" id="seleccionarTodos" onclick="toggleSeleccionarTodos()" 
                                           class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">IP</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Producto</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Tienda</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Posición</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Campaña</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Precio</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Fecha</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($clicks as $click)
                                <tr>
                                    <td class="px-2 py-4 whitespace-nowrap text-center cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700/70 transition-colors" onclick="toggleCheckbox({{ $click->id }}, event)">
                                        <input type="checkbox" name="click_seleccionado" value="{{ $click->id }}" 
                                               class="checkbox-click w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600"
                                               onchange="actualizarBotonEliminar()"
                                               onclick="event.stopPropagation()">
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                                        <div class="flex flex-col">
                                            <div class="flex items-center">
                                                <span class="font-mono text-xs">{{ $click->ip ?? '-' }}</span>
                                                @if($click->ip && in_array($click->ip, $ipsNuevas['lista']))
                                                    <span class="ml-2 inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                                        (N)
                                                    </span>
                                                @endif
                                            </div>
                                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                                @if($click->ciudad)
                                                    @if($click->ciudad === 'error')
                                                        <span class="text-red-600 dark:text-red-400">Error</span>
                                                        <button onclick="regeolocalizarIP('{{ $click->ip }}', '{{ $click->created_at->format('Y-m-d') }}', {{ $click->id }})" 
                                                                class="ml-2 inline-flex items-center px-2 py-1 text-xs font-medium rounded-md bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-300 hover:bg-yellow-200 dark:hover:bg-yellow-900/40 transition-colors" 
                                                                id="btn-regeo-{{ $click->id }}">
                                                            🔄 Reintentar
                                                        </button>
                                                    @else
                                                        {{ $click->ciudad }}
                                                    @endif
                                                @else
                                                    <span class="text-gray-400">-</span>
                                                @endif
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                        <div class="flex flex-col">
                                            <span class="font-medium">{{ $click->oferta->producto->nombre ?? 'Sin producto' }}</span>
                                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ $click->oferta->producto->marca ?? '' }} 
                                                @if($click->oferta->producto->talla)
                                                    - {{ $click->oferta->producto->talla }}
                                                @endif
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                        {{ $click->oferta->tienda->nombre ?? 'Sin tienda' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                        @if($click->posicion)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                                {{ $click->posicion }}º
                                            </span>
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                        @if($click->campaña)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                                {{ $click->campaña }}
                                            </span>
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                        @if($click->precio_unidad)
                                            <span class="font-medium">{{ number_format($click->precio_unidad, 2) }}€</span>
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                        <div class="flex flex-col">
                                            <span>{{ $click->created_at->format('d/m/Y') }}</span>
                                            <span class="text-xs text-gray-500 dark:text-gray-400">{{ $click->created_at->format('H:i:s') }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                        <div class="relative inline-block text-left">
                                            <div>
                                                <button type="button" class="inline-flex justify-center items-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm p-2 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors" onclick="toggleDropdown('dropdown-{{ $click->id }}')">
                                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                                        <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z"></path>
                                                    </svg>
                                                </button>
                                            </div>
                                            <div id="dropdown-{{ $click->id }}" class="hidden origin-top-right absolute right-0 mt-2 w-48 rounded-md shadow-lg bg-white dark:bg-gray-800 ring-1 ring-black ring-opacity-5 dropdown-menu z-50">
                                                <div class="py-1" role="menu">
                                                    @if($click->oferta->producto)
                                                        <a href="/productos/{{ $click->oferta->producto->slug ?? 'producto-' . $click->oferta->producto->id }}" target="_blank" 
                                                           class="flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700" role="menuitem">
                                                            🌐 Ir a Producto
                                                        </a>
                                                        <a href="{{ route('admin.productos.edit', $click->oferta->producto) }}" target="_blank" 
                                                           class="flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700" role="menuitem">
                                                            ✏️ Editar Producto
                                                        </a>
                                                    @endif
                                                    <a href="{{ route('admin.ofertas.edit', $click->oferta) }}" target="_blank" 
                                                       class="flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700" role="menuitem">
                                                        🔧 Editar Oferta
                                                    </a>
                                                    <a href="{{ $click->oferta->url }}" target="_blank" 
                                                       class="flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700" role="menuitem">
                                                        🛒 Ir a Tienda
                                                    </a>
                                                    <div class="border-t border-gray-200 dark:border-gray-700"></div>
                                                    <button onclick="eliminarClick({{ $click->id }}, '{{ $click->oferta->producto->nombre ?? 'Producto desconocido' }}')" 
                                                       class="flex items-center w-full px-4 py-2 text-sm text-red-700 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20" role="menuitem">
                                                        🗑️ Eliminar Click
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                                 <!-- Paginación -->
                 <div class="mt-6">
                     {{ $clicks->appends(request()->query())->links() }}
                 </div>
             @else
                 <div class="text-center py-8">
                     <div class="text-gray-500 dark:text-gray-400 text-lg">📝 No hay clicks para mostrar</div>
                     <p class="text-sm text-gray-400 dark:text-gray-500 mt-2">
                         No se encontraron clicks para los filtros seleccionados.
                     </p>
                 </div>
             @endif
         </div>

         <!-- Gráficos -->
         <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                          <!-- Gráfico de clicks por hora -->
              <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6 border border-gray-200 dark:border-gray-700">
                  <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-200 mb-4">📈 Clicks por Hora del Día</h3>
                  <div class="relative" style="height: 300px;">
                      <canvas id="graficoHoras"></canvas>
                  </div>
              </div>
             
                          <!-- Gráfico de clicks por día -->
              <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6 border border-gray-200 dark:border-gray-700">
                  <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-200 mb-4">📅 Clicks por Día</h3>
                  <div class="relative" style="height: 300px;">
                      <canvas id="graficoDias"></canvas>
                  </div>
              </div>
         </div>

         <!-- Listado de clicks por tienda -->
         <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6 border border-gray-200 dark:border-gray-700">
             <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-200 mb-4">🏪 Clicks por Tienda</h3>
             
             @if($estadisticas['clicksPorTienda']->count() > 0)
                 <div class="overflow-x-auto">
                     <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                         <thead class="bg-gray-50 dark:bg-gray-700">
                             <tr>
                                 <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Tienda</th>
                                 <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Total Clicks</th>
                                 <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Rango de Posiciones</th>
                                 <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">% del Total</th>
                             </tr>
                         </thead>
                         <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                             @foreach($estadisticas['clicksPorTienda'] as $tienda)
                                 @php
                                     $porcentaje = $estadisticas['totalClicks'] > 0 
                                         ? round(($tienda->total / $estadisticas['totalClicks']) * 100, 1) 
                                         : 0;
                                 @endphp
                                 <tr>
                                     <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                                         {{ $tienda->nombre }}
                                     </td>
                                     <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                         <span class="text-lg font-bold text-blue-600 dark:text-blue-400">{{ $tienda->total }}</span>
                                     </td>
                                                                           <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                          @if($tienda->posicion_min && $tienda->posicion_max)
                                              @if($tienda->posicion_min == $tienda->posicion_max)
                                                  <button onclick="mostrarModalPosiciones('{{ $tienda->nombre }}', {{ $tienda->id }})" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200 hover:bg-blue-200 dark:hover:bg-blue-800 transition-colors cursor-pointer">
                                                      {{ $tienda->posicion_min }}º
                                                  </button>
                                              @else
                                                  <button onclick="mostrarModalPosiciones('{{ $tienda->nombre }}', {{ $tienda->id }})" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200 hover:bg-green-200 dark:hover:bg-green-800 transition-colors cursor-pointer">
                                                      {{ $tienda->posicion_min }}º - {{ $tienda->posicion_max }}º
                                                  </button>
                                              @endif
                                          @else
                                              <span class="text-gray-400">-</span>
                                          @endif
                                      </td>
                                     <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                         <span class="text-purple-600 dark:text-purple-400 font-semibold">{{ $porcentaje }}%</span>
                                     </td>
                                 </tr>
                             @endforeach
                         </tbody>
                     </table>
                 </div>
             @else
                 <div class="text-center py-8">
                     <div class="text-gray-500 dark:text-gray-400 text-lg">📝 No hay clicks por tienda para mostrar</div>
                     <p class="text-sm text-gray-400 dark:text-gray-500 mt-2">
                         No se encontraron clicks para los filtros seleccionados.
                     </p>
                 </div>
             @endif
         </div>

                   <!-- Listado de clicks por producto -->
          <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6 border border-gray-200 dark:border-gray-700">
              <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-200 mb-4">📦 Clicks por Producto</h3>
              
              @if($estadisticas['clicksPorProducto']->count() > 0)
                  <div class="overflow-x-auto">
                      <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                          <thead class="bg-gray-50 dark:bg-gray-700">
                              <tr>
                                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Producto</th>
                                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Total Clicks</th>
                                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Rango de Posiciones</th>
                                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">% del Total</th>
                                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Acciones</th>
                              </tr>
                          </thead>
                          <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                              @foreach($estadisticas['clicksPorProducto'] as $producto)
                                  @php
                                      $porcentaje = $estadisticas['totalClicks'] > 0 
                                          ? round(($producto->total / $estadisticas['totalClicks']) * 100, 1) 
                                          : 0;
                                  @endphp
                                  <tr>
                                      <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                          <div class="flex flex-col">
                                              <span class="font-medium">{{ $producto->nombre }}</span>
                                              <span class="text-xs text-gray-500 dark:text-gray-400">
                                                  {{ $producto->marca ?? '' }} 
                                                  @if($producto->talla)
                                                      - {{ $producto->talla }}
                                                  @endif
                                              </span>
                                          </div>
                                      </td>
                                      <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                          <span class="text-lg font-bold text-blue-600 dark:text-blue-400">{{ $producto->total }}</span>
                                      </td>
                                      <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                          @if($producto->posicion_min && $producto->posicion_max)
                                              @if($producto->posicion_min == $producto->posicion_max)
                                                  <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                                      {{ $producto->posicion_min }}º
                                                  </span>
                                              @else
                                                  <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                                      {{ $producto->posicion_min }}º - {{ $producto->posicion_max }}º
                                                  </span>
                                              @endif
                                          @else
                                              <span class="text-gray-400">-</span>
                                          @endif
                                      </td>
                                      <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                          <span class="text-purple-600 dark:text-purple-400 font-semibold">{{ $porcentaje }}%</span>
                                      </td>
                                                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                           <div class="flex space-x-2">
                                               <a href="/productos/{{ $producto->slug ?? 'producto-' . $producto->id }}" target="_blank" 
                                                  class="inline-flex items-center px-2 py-1 border border-transparent text-xs font-medium rounded text-green-700 bg-green-100 hover:bg-green-200 dark:text-green-300 dark:bg-green-900/20 dark:hover:bg-green-900/40 transition-colors">
                                                   🌐 Ir a Producto
                                               </a>
                                               <a href="{{ route('admin.productos.edit', $producto->id) }}" target="_blank" 
                                                  class="inline-flex items-center px-2 py-1 border border-transparent text-xs font-medium rounded text-blue-700 bg-blue-100 hover:bg-blue-200 dark:text-blue-300 dark:bg-blue-900/20 dark:hover:bg-blue-900/40 transition-colors">
                                                   ✏️ Editar
                                               </a>
                                           </div>
                                       </td>
                                  </tr>
                              @endforeach
                          </tbody>
                      </table>
                  </div>
              @else
                  <div class="text-center py-8">
                      <div class="text-gray-500 dark:text-gray-400 text-lg">📝 No hay clicks por producto para mostrar</div>
                      <p class="text-sm text-gray-400 dark:text-gray-500 mt-2">
                          No se encontraron clicks para los filtros seleccionados.
                      </p>
                  </div>
              @endif
          </div>

          


         <!-- Listado de clicks por IP -->
         <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6 border border-gray-200 dark:border-gray-700">
             <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-200 mb-4">🌐 Clicks por IP</h3>
             
             @if($clicksPorIP->count() > 0)
                 <div class="overflow-x-auto">
                     <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                         <thead class="bg-gray-50 dark:bg-gray-700">
                             <tr>
                                 <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">IP</th>
                                 <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Total Clicks</th>
                                 <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Rango de Posiciones</th>
                                 <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">% del Total</th>
                                 <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actividad</th>
                                 <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Estado</th>
                             </tr>
                         </thead>
                         <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                             @foreach($clicksPorIP as $ip)
                                 @php
                                     $porcentaje = $estadisticas['totalClicks'] > 0 
                                         ? round(($ip->total / $estadisticas['totalClicks']) * 100, 1) 
                                         : 0;
                                     
                                     // Determinar si la IP es sospechosa
                                     $esSospechosa = $ip->total > 50; // Más de 50 clicks
                                     $esMuySospechosa = $ip->total > 100; // Más de 100 clicks
                                     
                                     // Calcular tiempo entre primer y último click
                                     $primerClick = \Carbon\Carbon::parse($ip->primer_click);
                                     $ultimoClick = \Carbon\Carbon::parse($ip->ultimo_click);
                                     $duracion = $primerClick->diffForHumans($ultimoClick, true);
                                 @endphp
                                 <tr class="{{ $esMuySospechosa ? 'bg-red-50 dark:bg-red-900/20' : ($esSospechosa ? 'bg-yellow-50 dark:bg-yellow-900/20' : '') }}">
                                     <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                                         <div class="flex items-center">
                                             <span class="font-mono">{{ $ip->ip }}</span>
                                             @if($esMuySospechosa)
                                                 <span class="ml-2 inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                                     ⚠️ Muy alta
                                                 </span>
                                             @elseif($esSospechosa)
                                                 <span class="ml-2 inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                                                     ⚠️ Alta
                                                 </span>
                                             @endif
                                         </div>
                                     </td>
                                     <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                         <span class="text-lg font-bold {{ $esMuySospechosa ? 'text-red-600 dark:text-red-400' : ($esSospechosa ? 'text-yellow-600 dark:text-yellow-400' : 'text-blue-600 dark:text-blue-400') }}">{{ $ip->total }}</span>
                                     </td>
                                     <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                         @if($ip->posicion_min && $ip->posicion_max)
                                             @if($ip->posicion_min == $ip->posicion_max)
                                                 <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                                     {{ $ip->posicion_min }}º
                                                 </span>
                                             @else
                                                 <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                                     {{ $ip->posicion_min }}º - {{ $ip->posicion_max }}º
                                                 </span>
                                             @endif
                                         @else
                                             <span class="text-gray-400">-</span>
                                         @endif
                                     </td>
                                     <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                         <span class="text-purple-600 dark:text-purple-400 font-semibold">{{ $porcentaje }}%</span>
                                     </td>
                                     <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                         <div class="flex flex-col">
                                             <span class="text-xs text-gray-500 dark:text-gray-400">
                                                 <strong>Primer:</strong> {{ $primerClick->format('d/m/Y H:i') }}
                                             </span>
                                             <span class="text-xs text-gray-500 dark:text-gray-400">
                                                 <strong>Último:</strong> {{ $ultimoClick->format('d/m/Y H:i') }}
                                             </span>
                                             <span class="text-xs text-gray-500 dark:text-gray-400">
                                                 <strong>Duración:</strong> {{ $duracion }}
                                             </span>
                                         </div>
                                     </td>
                                     <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                         @if($esMuySospechosa)
                                             <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                                 🔴 Muy sospechosa
                                             </span>
                                         @elseif($esSospechosa)
                                             <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                                                 🟡 Sospechosa
                                             </span>
                                         @else
                                             <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                                 🟢 Normal
                                             </span>
                                         @endif
                                     </td>
                                 </tr>
                             @endforeach
                         </tbody>
                     </table>
                 </div>
                 
                 <!-- Paginación -->
                 <div class="mt-6">
                     {{ $clicksPorIP->appends(request()->query())->links() }}
                 </div>
             @else
                 <div class="text-center py-8">
                     <div class="text-gray-500 dark:text-gray-400 text-lg">📝 No hay clicks por IP para mostrar</div>
                     <p class="text-sm text-gray-400 dark:text-gray-500 mt-2">
                         No se encontraron clicks para los filtros seleccionados.
                     </p>
                 </div>
             @endif
         </div>
    </div>

    <!-- Scripts para los gráficos -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Scripts para el mapa -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    
    <!-- Plugin de clustering para Leaflet -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.Default.css" />
    <script src="https://unpkg.com/leaflet.markercluster@1.4.1/dist/leaflet.markercluster.js"></script>
    
    <!-- Estilos adicionales para el desplegable y el mapa -->
    <style>
        .dropdown-overlay {
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            width: 100% !important;
            height: 100% !important;
            z-index: 9998 !important;
            pointer-events: none !important;
        }
        
        .dropdown-menu {
            position: absolute !important;
            z-index: 9999 !important;
            pointer-events: auto !important;
        }
        
        /* Estilos personalizados para los clusters */
        .marker-cluster-small {
            background-color: rgba(59, 130, 246, 0.6) !important; /* Azul */
            border: 2px solid rgba(59, 130, 246, 0.8) !important;
        }
        
        .marker-cluster-medium {
            background-color: rgba(245, 158, 11, 0.6) !important; /* Naranja */
            border: 2px solid rgba(245, 158, 11, 0.8) !important;
        }
        
        .marker-cluster-large {
            background-color: rgba(239, 68, 68, 0.6) !important; /* Rojo */
            border: 2px solid rgba(239, 68, 68, 0.8) !important;
        }
        
        .marker-cluster div {
            background-color: transparent !important;
            border-radius: 50% !important;
            text-align: center !important;
            font-size: 12px !important;
            font-weight: bold !important;
            color: white !important;
            line-height: 30px !important;
            width: 30px !important;
            height: 30px !important;
            margin: 5px !important;
        }
        
        .marker-cluster span {
            font-size: 12px !important;
            font-weight: bold !important;
            color: white !important;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.5) !important;
        }
    </style>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Verificar si hay filtros activos y mantener el desplegable abierto
            const urlParams = new URLSearchParams(window.location.search);
            const tieneFiltros = urlParams.has('fecha_desde') || urlParams.has('fecha_hasta') || 
                                urlParams.has('hora_desde') || urlParams.has('hora_hasta') || 
                                urlParams.has('busqueda') || urlParams.has('filtro_rapido');
            
            if (tieneFiltros) {
                const contenidoFiltros = document.getElementById('contenidoFiltros');
                const iconoFiltros = document.getElementById('iconoFiltros');
                
                if (contenidoFiltros && iconoFiltros) {
                    contenidoFiltros.classList.remove('hidden');
                    iconoFiltros.style.transform = 'rotate(180deg)';
                }
            }
            
            // Event listeners para desactivar filtro rápido cuando se cambian fechas manualmente
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
            
            // Datos para el gráfico de horas
            const datosHoras = @json($estadisticas['clicksPorHora']);
            const labelsHoras = [];
            const valuesHoras = [];
            
            // Crear array completo de 24 horas
            for (let i = 0; i < 24; i++) {
                labelsHoras.push(i.toString().padStart(2, '0') + ':00');
                valuesHoras.push(0);
            }
            
            // Llenar con datos reales
            datosHoras.forEach(item => {
                valuesHoras[item.hora] = item.total;
            });
            
            // Gráfico de clicks por hora
            const ctxHoras = document.getElementById('graficoHoras').getContext('2d');
            new Chart(ctxHoras, {
                type: 'bar',
                data: {
                    labels: labelsHoras,
                    datasets: [{
                        label: 'Clicks',
                        data: valuesHoras,
                        backgroundColor: 'rgba(59, 130, 246, 0.5)',
                        borderColor: 'rgba(59, 130, 246, 1)',
                        borderWidth: 1
                    }]
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
                            display: false
                        }
                    }
                }
            });
            
                         // Gráfico de clicks por día
             const datosDias = @json($estadisticas['clicksPorDia']);
             if (datosDias.length > 0) {
                 const labelsDias = datosDias.map(item => {
                     const fecha = new Date(item.fecha);
                     return fecha.toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit' });
                 });
                 const valuesDias = datosDias.map(item => item.total);
                 
                 const ctxDias = document.getElementById('graficoDias').getContext('2d');
                 new Chart(ctxDias, {
                     type: 'line',
                     data: {
                         labels: labelsDias,
                         datasets: [{
                             label: 'Clicks',
                             data: valuesDias,
                             backgroundColor: 'rgba(34, 197, 94, 0.1)',
                             borderColor: 'rgba(34, 197, 94, 1)',
                             borderWidth: 2,
                             fill: true,
                             tension: 0.4
                         }]
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
                                 display: false
                             }
                         }
                     }
                 });
             } else {
                 // Si no hay datos, mostrar mensaje en el gráfico
                 const ctxDias = document.getElementById('graficoDias').getContext('2d');
                 ctxDias.font = '16px Arial';
                 ctxDias.fillStyle = '#6B7280';
                 ctxDias.textAlign = 'center';
                 ctxDias.fillText('No hay datos para mostrar', ctxDias.canvas.width / 2, ctxDias.canvas.height / 2);
             }
            
            // ===== INICIALIZAR MAPA CON CLUSTERING =====
            @if($datosMapa['total_puntos'] > 0)
                // Datos del mapa desde el controlador
                const puntosMapa = @json($datosMapa['puntos']);
                
                // Inicializar mapa centrado en España
                const mapa = L.map('mapaEspana').setView([40.4637, -3.7492], 6);
                
                // Añadir capa de OpenStreetMap (gratuita)
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '© OpenStreetMap contributors',
                    maxZoom: 18
                }).addTo(mapa);
                
                // Crear grupo de clustering con configuración personalizada
                const marcadoresCluster = L.markerClusterGroup({
                    // Configuración del clustering
                    chunkedLoading: true,           // Carga en chunks para mejor rendimiento
                    maxClusterRadius: 50,          // Radio máximo para agrupar marcadores (en píxeles)
                    spiderfyOnMaxZoom: true,       // Mostrar spider cuando se alcanza el zoom máximo
                    showCoverageOnHover: false,    // No mostrar cobertura al hacer hover
                    zoomToBoundsOnClick: true,     // Zoom a los bounds del cluster al hacer click
                    
                    // Configuración de iconos del cluster
                    iconCreateFunction: function(cluster) {
                        const childCount = cluster.getChildCount();
                        const childMarkers = cluster.getAllChildMarkers();
                        
                        // Sumar todos los clicks de todos los marcadores en el cluster
                        let totalClicks = 0;
                        childMarkers.forEach(marker => {
                            // Obtener el número de clicks del marcador
                            const clicks = marker._totalClicks || 1;
                            totalClicks += clicks;
                        });
                        
                        let className = 'marker-cluster ';
                        
                        // Clases CSS según el número total de clicks en el cluster
                        if (totalClicks < 10) {
                            className += 'marker-cluster-small';
                        } else if (totalClicks < 100) {
                            className += 'marker-cluster-medium';
                        } else {
                            className += 'marker-cluster-large';
                        }
                        
                        return L.divIcon({
                            html: '<div><span>' + totalClicks + '</span></div>',
                            className: className,
                            iconSize: L.point(40, 40)
                        });
                    }
                });
                
                // Añadir cada punto como marcador individual al cluster
                puntosMapa.forEach(punto => {
                    // Crear icono personalizado para cada marcador individual
                    const iconSize = Math.min(20 + (punto.total_clicks * 2), 40);
                    const iconColor = punto.total_clicks > 10 ? 'red' : 
                                    punto.total_clicks > 5 ? 'orange' : 'blue';
                    
                    const icono = L.divIcon({
                        html: `<div style="
                            background-color: ${iconColor};
                            width: ${iconSize}px;
                            height: ${iconSize}px;
                            border-radius: 50%;
                            border: 2px solid white;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            color: white;
                            font-weight: bold;
                            font-size: ${Math.min(iconSize * 0.4, 14)}px;
                            box-shadow: 0 2px 4px rgba(0,0,0,0.3);
                        ">${punto.total_clicks}</div>`,
                        className: 'marcador-personalizado',
                        iconSize: [iconSize, iconSize],
                        iconAnchor: [iconSize/2, iconSize/2]
                    });
                    
                    // Crear marcador individual
                    const marcador = L.marker([punto.latitud, punto.longitud], { 
                        icon: icono
                    });
                    
                    // Almacenar el número de clicks como propiedad del marcador
                    marcador._totalClicks = punto.total_clicks;
                    
                    // Crear popup con información detallada
                    const popupContent = `
                        <div style="min-width: 200px;">
                            <h4 style="margin: 0 0 8px 0; color: #1f2937; font-weight: bold;">📍 ${punto.ciudad}</h4>
                            <div style="font-size: 14px; color: #6b7280;">
                                <div style="margin-bottom: 4px;">
                                    <strong>Total clicks:</strong> ${punto.total_clicks}
                                </div>
                                <div style="margin-bottom: 4px;">
                                    <strong>IPs únicas:</strong> ${punto.ips_unicas}
                                </div>
                                <div style="margin-bottom: 4px;">
                                    <strong>Coordenadas:</strong> ${punto.latitud.toFixed(4)}, ${punto.longitud.toFixed(4)}
                                </div>
                            </div>
                        </div>
                    `;
                    
                    marcador.bindPopup(popupContent);
                    
                    // Añadir marcador al grupo de clustering
                    marcadoresCluster.addLayer(marcador);
                });
                
                // Añadir el grupo de clustering al mapa
                mapa.addLayer(marcadoresCluster);
                
                // Ajustar vista del mapa para mostrar todos los marcadores
                if (puntosMapa.length > 0) {
                    mapa.fitBounds(marcadoresCluster.getBounds().pad(0.1));
                }
            @endif
            
                 });
         
         // Cerrar modales con tecla Escape
         document.addEventListener('keydown', function(e) {
             if (e.key === 'Escape') {
                 document.getElementById('modalPosiciones').classList.add('hidden');
                 document.getElementById('modalAyuda').classList.add('hidden');
             }
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
              const porPagina = document.querySelector('select[name="por_pagina"]').value;
              const busqueda = document.querySelector('input[name="busqueda"]').value;
              
              if (horaDesde) url.searchParams.set('hora_desde', horaDesde);
              if (horaHasta) url.searchParams.set('hora_hasta', horaHasta);
              if (porPagina) url.searchParams.set('por_pagina', porPagina);
              if (busqueda) url.searchParams.set('busqueda', busqueda);
              
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
         
         // Función para mostrar el modal de ayuda
         function mostrarModalAyuda() {
             document.getElementById('modalAyuda').classList.remove('hidden');
         }
         
         // Función para manejar el desplegable de acciones
         function toggleDropdown(dropdownId) {
             // Cerrar todos los otros desplegables
             const allDropdowns = document.querySelectorAll('[id^="dropdown-"]');
             allDropdowns.forEach(dropdown => {
                 if (dropdown.id !== dropdownId) {
                     dropdown.classList.add('hidden');
                 }
             });
             
             // Toggle el desplegable actual
             const dropdown = document.getElementById(dropdownId);
             dropdown.classList.toggle('hidden');
         }
         
         // Cerrar desplegables al hacer click fuera
         document.addEventListener('click', function(event) {
             if (!event.target.closest('[onclick*="toggleDropdown"]') && !event.target.closest('[id^="dropdown-"]')) {
                 const allDropdowns = document.querySelectorAll('[id^="dropdown-"]');
                 allDropdowns.forEach(dropdown => {
                     dropdown.classList.add('hidden');
                 });
             }
         });
         
         // Función para mostrar el modal de posiciones
         function mostrarModalPosiciones(nombreTienda, tiendaId) {
             // Mostrar loading en el modal
             document.getElementById('modalPosiciones').classList.remove('hidden');
             document.getElementById('modalPosicionesContent').innerHTML = `
                 <div class="text-center py-8">
                     <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                     <p class="mt-2 text-gray-600 dark:text-gray-400">Cargando datos de posiciones...</p>
                 </div>
             `;
             
                           // Hacer petición AJAX para obtener los datos de posiciones
              const params = new URLSearchParams({
                  tienda_id: tiendaId,
                  fecha_desde: '{{ $fechaDesde }}',
                  fecha_hasta: '{{ $fechaHasta }}',
                  busqueda: '{{ $busqueda }}',
                  hora_desde: '{{ $horaDesde }}',
                  hora_hasta: '{{ $horaHasta }}'
              });
             
             fetch(`{{ route('admin.clicks.posiciones-tienda') }}?${params}`)
                 .then(response => response.json())
                 .then(data => {
                     if (data.success) {
                         renderizarModalPosiciones(nombreTienda, data.posiciones, data.totalClicks);
                     } else {
                         document.getElementById('modalPosicionesContent').innerHTML = `
                             <div class="text-center py-8">
                                 <div class="text-red-500 text-lg">❌ Error al cargar los datos</div>
                                 <p class="text-sm text-gray-400 dark:text-gray-500 mt-2">${data.message}</p>
                             </div>
                         `;
                     }
                 })
                 .catch(error => {
                     console.error('Error:', error);
                     document.getElementById('modalPosicionesContent').innerHTML = `
                         <div class="text-center py-8">
                             <div class="text-red-500 text-lg">❌ Error de conexión</div>
                             <p class="text-sm text-gray-400 dark:text-gray-500 mt-2">No se pudieron cargar los datos.</p>
                         </div>
                     `;
                 });
         }
         
        function renderizarModalPosiciones(nombreTienda, posiciones, totalClicks) {
            let contenidoHTML = `
                <div class="space-y-4">
                                          <div class="text-center">
                         <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">📊 Clicks por Posición</h3>
                         <p class="text-sm text-gray-600 dark:text-gray-400">Tienda: <span class="font-medium">${nombreTienda}</span></p>
                         <p class="text-sm text-gray-600 dark:text-gray-400">Total clicks de esta tienda: <span class="font-medium text-blue-600">${totalClicks}</span></p>
                         <p class="text-xs text-gray-500 dark:text-gray-400">Los porcentajes se calculan sobre el total de clicks de esta tienda</p>
                     </div>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Posición</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Clicks</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">% del Total</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Barra</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
            `;
            
            posiciones.forEach(posicion => {
                const porcentaje = totalClicks > 0 ? ((posicion.total / totalClicks) * 100).toFixed(1) : 0;
                const barraWidth = Math.max(porcentaje * 2, 20); // Mínimo 20px de ancho
                
                contenidoHTML += `
                    <tr>
                        <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                ${posicion.posicion}º
                            </span>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                            <span class="font-semibold text-blue-600 dark:text-blue-400">${posicion.total}</span>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                            <span class="font-semibold text-purple-600 dark:text-purple-400">${porcentaje}%</span>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                            <div class="w-32 bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                <div class="bg-gradient-to-r from-blue-500 to-purple-500 h-2 rounded-full" style="width: ${porcentaje}%"></div>
                            </div>
                        </td>
                    </tr>
                `;
            });
            
            contenidoHTML += `
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="text-center pt-4">
                        <button onclick="document.getElementById('modalPosiciones').classList.add('hidden')" class="px-4 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600 focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-colors">
                            Cerrar
                        </button>
                    </div>
                </div>
            `;
            
            document.getElementById('modalPosicionesContent').innerHTML = contenidoHTML;
        }
        
        // Función para eliminar un click
        function eliminarClick(clickId, nombreProducto) {
            // Cerrar dropdown
            const allDropdowns = document.querySelectorAll('[id^="dropdown-"]');
            allDropdowns.forEach(dropdown => {
                dropdown.classList.add('hidden');
            });
            
            // Confirmar eliminación
            if (!confirm(`¿Estás seguro de que quieres eliminar este click del producto "${nombreProducto}"?\n\nEsta acción no se puede deshacer.`)) {
                return;
            }
            
            // Mostrar mensaje de carga
            const boton = event.target;
            const textoOriginal = boton.innerHTML;
            boton.innerHTML = '<span class="inline-block animate-spin">⏳</span> Eliminando...';
            boton.disabled = true;
            
            // Realizar petición DELETE
            fetch(`{{ route('admin.clicks.destroy', ':id') }}`.replace(':id', clickId), {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Recargar la página para actualizar las estadísticas
                    window.location.reload();
                } else {
                    alert('❌ Error al eliminar el click: ' + (data.message || 'Error desconocido'));
                    boton.innerHTML = textoOriginal;
                    boton.disabled = false;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('❌ Error de conexión al intentar eliminar el click');
                boton.innerHTML = textoOriginal;
                boton.disabled = false;
            });
        }
        
        // Función para regeolocalizar una IP
        function regeolocalizarIP(ip, fecha, clickId) {
            const boton = document.getElementById(`btn-regeo-${clickId}`);
            if (!boton) return;
            
            // Guardar texto original
            const textoOriginal = boton.innerHTML;
            
            // Deshabilitar botón y mostrar loading
            boton.disabled = true;
            boton.innerHTML = '<span class="inline-block animate-spin">⏳</span> Procesando...';
            
            // Realizar petición POST
            fetch('{{ route("admin.clicks.regeolocalizar-ip") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    ip: ip,
                    fecha: fecha
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Mostrar mensaje de éxito
                    alert('✅ ' + data.message);
                    
                    // Recargar la página para actualizar todas las filas y estadísticas
                    window.location.reload();
                } else {
                    // Mostrar mensaje de error
                    alert('❌ ' + data.message);
                    
                    // Restaurar botón
                    boton.disabled = false;
                    boton.innerHTML = textoOriginal;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('❌ Error de conexión al intentar regeolocalizar la IP');
                
                // Restaurar botón
                boton.disabled = false;
                boton.innerHTML = textoOriginal;
            });
        }
        
        // Función para seleccionar/deseleccionar todos los checkboxes
        function toggleSeleccionarTodos() {
            const checkboxTodos = document.getElementById('seleccionarTodos');
            const checkboxes = document.querySelectorAll('.checkbox-click');
            
            checkboxes.forEach(checkbox => {
                checkbox.checked = checkboxTodos.checked;
            });
            
            actualizarBotonEliminar();
        }
        
        // Función para alternar el checkbox cuando se hace clic en el contenedor
        function toggleCheckbox(clickId, event) {
            // Evitar que se active dos veces si se hace clic directamente en el checkbox
            if (event.target.type === 'checkbox') {
                return;
            }
            
            // Buscar el checkbox por su valor
            const checkbox = document.querySelector(`input[name="click_seleccionado"][value="${clickId}"]`);
            if (checkbox) {
                checkbox.checked = !checkbox.checked;
                actualizarBotonEliminar();
            }
        }
        
        // Función para actualizar el estado del botón de eliminar seleccionados
        function actualizarBotonEliminar() {
            const checkboxes = document.querySelectorAll('.checkbox-click:checked');
            const botonEliminar = document.getElementById('btnEliminarSeleccionados');
            const checkboxTodos = document.getElementById('seleccionarTodos');
            
            // Actualizar estado del botón
            if (checkboxes.length > 0) {
                botonEliminar.disabled = false;
                botonEliminar.textContent = `🗑️ Eliminar Seleccionados (${checkboxes.length})`;
            } else {
                botonEliminar.disabled = true;
                botonEliminar.textContent = '🗑️ Eliminar Seleccionados';
            }
            
            // Actualizar checkbox "seleccionar todos"
            const totalCheckboxes = document.querySelectorAll('.checkbox-click').length;
            checkboxTodos.checked = checkboxes.length === totalCheckboxes && totalCheckboxes > 0;
        }
        
        // Función para eliminar múltiples clicks seleccionados
        function eliminarClicksSeleccionados() {
            const checkboxes = document.querySelectorAll('.checkbox-click:checked');
            
            if (checkboxes.length === 0) {
                alert('⚠️ Por favor, selecciona al menos un click para eliminar.');
                return;
            }
            
            const ids = Array.from(checkboxes).map(cb => cb.value);
            const cantidad = ids.length;
            
            // Confirmar eliminación
            if (!confirm(`¿Estás seguro de que quieres eliminar ${cantidad} click(s) seleccionado(s)?\n\nEsta acción no se puede deshacer.`)) {
                return;
            }
            
            // Deshabilitar botón y mostrar loading
            const botonEliminar = document.getElementById('btnEliminarSeleccionados');
            const textoOriginal = botonEliminar.innerHTML;
            botonEliminar.disabled = true;
            botonEliminar.innerHTML = '<span class="inline-block animate-spin">⏳</span> Eliminando...';
            
            // Deshabilitar todos los checkboxes durante la eliminación
            checkboxes.forEach(cb => cb.disabled = true);
            
            // Realizar eliminaciones en paralelo
            const promesas = ids.map(id => {
                return fetch(`{{ route('admin.clicks.destroy', ':id') }}`.replace(':id', id), {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        throw new Error(data.message || 'Error desconocido');
                    }
                    return { id, success: true };
                })
                .catch(error => {
                    console.error(`Error al eliminar click ${id}:`, error);
                    return { id, success: false, error: error.message };
                });
            });
            
            // Esperar a que todas las eliminaciones terminen
            Promise.all(promesas)
                .then(resultados => {
                    const exitosas = resultados.filter(r => r.success).length;
                    const fallidas = resultados.filter(r => !r.success).length;
                    
                    // Función para redirigir a una página válida
                    const redirigirAPaginaValida = () => {
                        // Obtener la URL actual
                        const url = new URL(window.location);
                        
                        // Si hay un parámetro de página, quitarlo (o ponerlo en 1)
                        // Esto asegura que si estamos en una página que ya no existe,
                        // nos redirija a la primera página
                        if (url.searchParams.has('page')) {
                            url.searchParams.delete('page');
                        }
                        
                        // Redirigir a la nueva URL
                        window.location.href = url.toString();
                    };
                    
                    if (fallidas === 0) {
                        // Todas las eliminaciones fueron exitosas
                        alert(`✅ Se eliminaron correctamente ${exitosas} click(s).`);
                        // Redirigir a una página válida (sin el parámetro de página)
                        redirigirAPaginaValida();
                    } else {
                        // Algunas eliminaciones fallaron
                        const mensaje = `⚠️ Se eliminaron ${exitosas} click(s) correctamente, pero ${fallidas} fallaron.\n\nLa página se recargará para mostrar el estado actual.`;
                        alert(mensaje);
                        // Redirigir a una página válida
                        redirigirAPaginaValida();
                    }
                })
                .catch(error => {
                    console.error('Error general:', error);
                    alert('❌ Error de conexión al intentar eliminar los clicks seleccionados.');
                    botonEliminar.innerHTML = textoOriginal;
                    botonEliminar.disabled = false;
                    checkboxes.forEach(cb => cb.disabled = false);
                });
        }
     </script>
     
     <!-- Modal para mostrar clicks por posición -->
     <div id="modalPosiciones" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4" onclick="if(event.target === this) this.classList.add('hidden')">
         <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl border border-gray-200 dark:border-gray-700 w-full max-w-2xl max-h-[80vh] overflow-y-auto">
             <div class="p-6">
                 <div id="modalPosicionesContent">
                     <!-- El contenido se cargará dinámicamente -->
                 </div>
             </div>
         </div>
     </div>
     
     <!-- Modal de ayuda para búsqueda -->
     <div id="modalAyuda" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4" onclick="if(event.target === this) this.classList.add('hidden')">
         <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl border border-gray-200 dark:border-gray-700 w-full max-w-2xl">
             <div class="p-6">
                 <div class="flex items-center justify-between mb-4">
                     <h3 class="text-lg font-semibold text-gray-900 dark:text-white">💡 Cómo usar la búsqueda</h3>
                     <button onclick="document.getElementById('modalAyuda').classList.add('hidden')" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                         <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                             <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                         </svg>
                     </button>
                 </div>
                 
                 <div class="space-y-4">
                     <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded-lg p-4">
                         <h4 class="font-semibold text-blue-800 dark:text-blue-200 mb-3">🔍 Ejemplos de búsqueda:</h4>
                         <ul class="text-sm text-blue-700 dark:text-blue-300 space-y-2">
                             <li class="flex items-start">
                                 <span class="font-medium mr-2">•</span>
                                 <div>
                                     <strong>Producto, Tienda:</strong> Busca clicks de un producto específico en una tienda concreta
                                     <br><span class="text-xs text-blue-600 dark:text-blue-400">Ejemplo: "pañales, amazon"</span>
                                 </div>
                             </li>
                             <li class="flex items-start">
                                 <span class="font-medium mr-2">•</span>
                                 <div>
                                     <strong>Producto, Campaña:</strong> Busca clicks de un producto en una campaña específica
                                     <br><span class="text-xs text-blue-600 dark:text-blue-400">Ejemplo: "pañales, navidad"</span>
                                 </div>
                             </li>
                             <li class="flex items-start">
                                 <span class="font-medium mr-2">•</span>
                                 <div>
                                     <strong>Producto, Tienda, Campaña:</strong> Combina los tres criterios
                                     <br><span class="text-xs text-blue-600 dark:text-blue-400">Ejemplo: "pañales, amazon, navidad"</span>
                                 </div>
                             </li>
                         </ul>
                     </div>
                     
                     <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-700 rounded-lg p-4">
                         <h4 class="font-semibold text-green-800 dark:text-green-200 mb-3">✨ Características especiales:</h4>
                         <ul class="text-sm text-green-700 dark:text-green-300 space-y-2">
                             <li class="flex items-start">
                                 <span class="font-medium mr-2">•</span>
                                 <div>
                                     <strong>Búsqueda parcial:</strong> "amaz" encontrará "Amazon", "corte ingles" encontrará "Corte Inglés"
                                 </div>
                             </li>
                             <li class="flex items-start">
                                 <span class="font-medium mr-2">•</span>
                                 <div>
                                     <strong>Marca o talla:</strong> Puedes buscar por marca del producto o talla
                                     <br><span class="text-xs text-green-600 dark:text-green-400">Ejemplo: "dodot, talla 4"</span>
                                 </div>
                             </li>
                             <li class="flex items-start">
                                 <span class="font-medium mr-2">•</span>
                                 <div>
                                     <strong>No distingue mayúsculas:</strong> "AMAZON" y "amazon" dan el mismo resultado
                                 </div>
                             </li>
                         </ul>
                     </div>
                     
                     <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700 rounded-lg p-4">
                         <h4 class="font-semibold text-yellow-800 dark:text-yellow-200 mb-2">⚠️ Nota importante:</h4>
                         <p class="text-sm text-yellow-700 dark:text-yellow-300">
                             Todos los filtros (fechas, horas, búsqueda) se aplican a todas las secciones de la vista: listado de clicks, gráficos y estadísticas por tienda/producto.
                         </p>
                     </div>
                 </div>
                 
                 <div class="flex justify-end mt-6">
                     <button onclick="document.getElementById('modalAyuda').classList.add('hidden')" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors">
                         Entendido
                     </button>
                 </div>
             </div>
         </div>
     </div>
 </x-app-layout>
