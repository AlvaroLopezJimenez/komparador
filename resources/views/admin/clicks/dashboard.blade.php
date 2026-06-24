<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.dashboard') }}">
                <h2 class="font-semibold text-xl text-white leading-tight">Inicio -></h2>
            </a>
            <h2 class="font-semibold text-xl text-white leading-tight">Analytics · Visitas y Clicks</h2>
        </div>
    </x-slot>

    @php
        $resumen = $resumen ?? [
            'visitantes_unicos' => 0,
            'sesiones' => 0,
            'visitas_productos' => 0,
            'clics_tiendas' => 0,
            'ctr_global' => 0,
        ];
        $ipsSospechosas = $ipsSospechosas ?? [];
        $ipsNuevas = $ipsNuevas ?? ['lista' => [], 'total' => 0];
        $datosMapa = $datosMapa ?? ['puntos' => [], 'total_puntos' => 0, 'total_clicks' => 0];
        $busqueda = $busqueda ?? '';
        $porPagina = $porPagina ?? 20;
        $filtroRapido = $filtroRapido ?? request('filtro_rapido', 'hoy');
        $fechaDesde = $fechaDesde ?? now()->toDateString();
        $fechaHasta = $fechaHasta ?? now()->toDateString();
        $horaDesde = $horaDesde ?? '';
        $horaHasta = $horaHasta ?? '';
        $clicks = $clicks ?? new \Illuminate\Pagination\LengthAwarePaginator([], 0, 20);
        $tops = [
            ['id' => 'productos', 'titulo' => 'Top productos', 'icono' => '📦'],
            ['id' => 'categorias', 'titulo' => 'Top categorías', 'icono' => '📂'],
            ['id' => 'tiendas', 'titulo' => 'Top tiendas', 'icono' => '🏪'],
        ];
    @endphp

    <style>
        .rank-medal-1 { background: linear-gradient(135deg, #fbbf24, #f59e0b); color: #fff; }
        .rank-medal-2 { background: linear-gradient(135deg, #9ca3af, #6b7280); color: #fff; }
        .rank-medal-3 { background: linear-gradient(135deg, #d97706, #b45309); color: #fff; }
        .ctr-bar { height: 6px; border-radius: 999px; background: #e5e7eb; overflow: hidden; }
        .dark .ctr-bar { background: #374151; }
        .ctr-bar-fill { height: 100%; border-radius: 999px; transition: width .4s ease; }
        .top-data-table { width: 100%; table-layout: fixed; border-collapse: collapse; }
        .top-data-table th,
        .top-data-table td { padding: 0.5rem 0.4rem; vertical-align: middle; box-sizing: border-box; }
        .top-data-table thead th { font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em; color: #6b7280; border-bottom: 1px solid #e5e7eb; background: #f9fafb; }
        .dark .top-data-table thead th { color: #9ca3af; border-bottom-color: #374151; background: rgba(55, 65, 81, 0.4); }
        .top-data-table tbody tr { border-bottom: 1px solid #f3f4f6; }
        .dark .top-data-table tbody tr { border-bottom-color: rgba(55, 65, 81, 0.6); }
        .top-data-table tbody tr:hover { background: #f9fafb; }
        .dark .top-data-table tbody tr:hover { background: rgba(55, 65, 81, 0.3); }
        .top-data-table .col-rank {
            width: 3.25rem;
            min-width: 3.25rem;
            max-width: 3.25rem;
            padding-left: 1.25rem;
            padding-right: 0.5rem;
            text-align: center;
            overflow: hidden;
        }
        .top-rank-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 1.25rem;
            height: 1.25rem;
            padding: 0 0.15rem;
            font-size: 9px;
            line-height: 1;
            border-radius: 9999px;
            flex-shrink: 0;
            box-sizing: border-box;
        }
        .top-data-table .col-nombre {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            padding-left: 0.15rem;
            padding-right: 0.5rem;
        }
        .top-data-table .col-num { width: 4.25rem; text-align: right; font-variant-numeric: tabular-nums; }
        .top-data-table .col-ctr { width: 3.75rem; text-align: right; padding-right: 0.75rem; font-variant-numeric: tabular-nums; }
        .top-data-table .col-cuota { width: 4.25rem; text-align: right; padding-right: 1.25rem; font-variant-numeric: tabular-nums; font-size: 11px; font-weight: 600; }
        .top-data-table.top-solo-clics .col-num-clics { width: 5rem; }
        .top-data-table.top-solo-clics .col-nombre { max-width: none; }
        .top-orden-btn { padding: 0.375rem 0.625rem; font-size: 11px; font-weight: 500; border-radius: 0.375rem; border: 1px solid #d1d5db; background: #fff; color: #374151; transition: all 0.15s; white-space: nowrap; }
        .dark .top-orden-btn { border-color: #4b5563; background: #374151; color: #d1d5db; }
        .top-orden-btn:hover { background: #eff6ff; border-color: #93c5fd; }
        .dark .top-orden-btn:hover { background: rgba(59, 130, 246, 0.15); border-color: #3b82f6; }
        .top-orden-btn.active { background: #2563eb; border-color: #2563eb; color: #fff; }
        .dark .top-orden-btn.active { background: #2563eb; border-color: #2563eb; color: #fff; }
    </style>

         <div class="max-w-7xl mx-auto py-10 px-4 space-y-8 bg-gray-50 dark:bg-gray-900 rounded-lg shadow-md" id="dashboard-analytics">
         
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

        {{-- Resumen analítica visitas --}}
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6 border border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-200 mb-4">📊 Resumen del periodo</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg">
                    <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ number_format($resumen['visitantes_unicos']) }}</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Visitantes únicos</div>
                </div>
                <div class="bg-indigo-50 dark:bg-indigo-900/20 p-4 rounded-lg">
                    <div class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">{{ number_format($resumen['sesiones']) }}</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Sesiones</div>
                </div>
                <div class="bg-green-50 dark:bg-green-900/20 p-4 rounded-lg">
                    <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ number_format($resumen['visitas_productos']) }}</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Visitas a productos</div>
                </div>
                <div class="bg-orange-50 dark:bg-orange-900/20 p-4 rounded-lg">
                    <div class="text-2xl font-bold text-orange-600 dark:text-orange-400">{{ number_format($resumen['clics_tiendas']) }}</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Clics a tiendas</div>
                </div>
                <div class="bg-purple-50 dark:bg-purple-900/20 p-4 rounded-lg">
                    <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ number_format($resumen['ctr_global'], 1, ',', '.') }}%</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">CTR global</div>
                </div>
            </div>
        </div>

        {{-- Listado de clicks (desplegable) --}}
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between p-6 cursor-pointer" onclick="toggleSeccion('contenidoClicks', 'iconoClicks')">
                <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-200">📋 Listado de Clicks <span class="text-sm font-normal text-gray-500">({{ number_format($clicks->total()) }})</span></h3>
                <svg id="iconoClicks" class="w-5 h-5 text-gray-500 dark:text-gray-400 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </div>
            <div id="contenidoClicks" class="hidden px-6 pb-6">
            <div class="flex items-center justify-end mb-4">
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
                                                    <button onclick="eliminarClick({{ $click->id }}, @json($click->oferta->producto->nombre ?? 'Producto desconocido'))" 
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
        </div>

        {{-- Mapa (desplegable) --}}
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between p-6 cursor-pointer" onclick="toggleSeccionMapa()">
                <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-200">🗺️ Mapa de Clicks <span class="text-sm font-normal text-gray-500">({{ number_format($datosMapa['total_puntos']) }} ubicaciones)</span></h3>
                <svg id="iconoMapa" class="w-5 h-5 text-gray-500 dark:text-gray-400 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </div>
            <div id="contenidoMapa" class="hidden px-6 pb-6">
                @if($datosMapa['total_puntos'] > 0)
                    <div class="mb-4 text-sm text-gray-600 dark:text-gray-400">
                        <span class="font-medium">{{ $datosMapa['total_puntos'] }}</span> ubicaciones con
                        <span class="font-medium">{{ $datosMapa['total_clicks'] }}</span> clicks en total
                    </div>
                    <div id="mapaEspana" style="height: 500px; width: 100%; border-radius: 8px;"></div>
                @else
                    <div class="text-center py-12">
                        <div class="text-gray-500 dark:text-gray-400 text-lg">📍 No hay datos de ubicación</div>
                        <p class="text-sm text-gray-400 dark:text-gray-500 mt-2">Los clicks nuevos incluirán geolocalización automáticamente.</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- Gráfica temporal visitas / clics / CTR (desplegable) --}}
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between p-6 cursor-pointer" onclick="toggleSeccionGrafica()">
                <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-200">📈 Evolución temporal <span class="text-sm font-normal text-gray-500 dark:text-gray-400">(visitas, clics y CTR)</span></h3>
                <svg id="iconoGrafica" class="w-5 h-5 text-gray-500 dark:text-gray-400 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </div>
            <div id="contenidoGrafica" class="hidden px-6 pb-6">
                <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                    <p id="graficaTemporalSubtitulo" class="text-sm text-gray-500 dark:text-gray-400">Cargando…</p>
                    <div class="grafica-granularidad-group inline-flex flex-wrap items-center gap-1 rounded-md" onclick="event.stopPropagation()">
                        <button type="button" class="top-orden-btn grafica-granularidad-btn" data-granularidad="15min">15 min</button>
                        <button type="button" class="top-orden-btn grafica-granularidad-btn" data-granularidad="hour">Hora</button>
                        <button type="button" class="top-orden-btn grafica-granularidad-btn" data-granularidad="day">Día</button>
                        <button type="button" class="top-orden-btn grafica-granularidad-btn" data-granularidad="month">Mes</button>
                        <button type="button" class="top-orden-btn grafica-granularidad-btn" data-granularidad="year">Año</button>
                    </div>
                </div>
                <div id="graficaTemporalLoading" class="py-16 text-center text-sm text-gray-500 dark:text-gray-400">
                    <div class="inline-block w-6 h-6 border-2 border-blue-500 border-t-transparent rounded-full animate-spin mb-2"></div>
                    <div>Cargando gráfica…</div>
                </div>
                <div id="graficaTemporalEmpty" class="hidden py-16 text-center text-sm text-gray-500 dark:text-gray-400">
                    Sin datos en el periodo seleccionado
                </div>
                <div id="graficaTemporalWrap" class="hidden" style="height: 420px; position: relative;">
                    <canvas id="graficaTemporal"></canvas>
                </div>
            </div>
        </div>

        {{-- Rankings analítica --}}
        <div class="grid grid-cols-1 gap-6">
            @foreach($tops as $top)
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl border border-gray-200 dark:border-gray-700 top-section" data-tipo="{{ $top['id'] }}">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-200">
                            {{ $top['icono'] }} {{ $top['titulo'] }}
                            <span class="text-sm font-normal text-gray-500 dark:text-gray-400 top-total" data-tipo="{{ $top['id'] }}">…</span>
                        </h3>
                        @if($top['id'] !== 'tiendas')
                        <div class="top-orden-group inline-flex items-center gap-1 rounded-md" data-tipo="{{ $top['id'] }}">
                            <button type="button" class="top-orden-btn active" data-orden="ctr">Más CTR</button>
                            <button type="button" class="top-orden-btn" data-orden="visitas">Más visitadas</button>
                            <button type="button" class="top-orden-btn" data-orden="clicks">Más clics</button>
                        </div>
                        @else
                        <span class="text-xs text-gray-500 dark:text-gray-400">Ordenado por clics</span>
                        @endif
                    </div>
                </div>
                <div class="top-list min-h-[180px]" data-tipo="{{ $top['id'] }}">
                    <div class="px-6 py-10 text-center text-sm text-gray-500 dark:text-gray-400">
                        <div class="inline-block w-6 h-6 border-2 border-blue-500 border-t-transparent rounded-full animate-spin mb-2"></div>
                        <div>Cargando…</div>
                    </div>
                </div>
                <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700/50 flex items-center justify-between top-paginacion border-t border-gray-200 dark:border-gray-700" data-tipo="{{ $top['id'] }}">
                    <button type="button" class="top-prev px-4 py-2 text-sm rounded-md bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 disabled:opacity-40 disabled:cursor-not-allowed hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors" disabled>← Anterior</button>
                    <span class="text-xs text-gray-500 dark:text-gray-400 top-page-info font-medium">Pág. 1</span>
                    <button type="button" class="top-next px-4 py-2 text-sm rounded-md bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 disabled:opacity-40 disabled:cursor-not-allowed hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">Siguiente →</button>
                </div>
            </div>
            @endforeach
        </div>

    </div>

    <!-- Scripts para el mapa -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    
    <!-- Plugin de clustering para Leaflet -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.Default.css" />
    <script src="https://unpkg.com/leaflet.markercluster@1.4.1/dist/leaflet.markercluster.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
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
            
        });

        let mapaInstance = null;
        let mapaInicializado = false;

        @if($datosMapa['total_puntos'] > 0)
        const puntosMapa = @json($datosMapa['puntos']);

        function inicializarMapa() {
            if (mapaInicializado || typeof L === 'undefined') return;
            const contenedor = document.getElementById('mapaEspana');
            if (!contenedor) return;

            mapaInicializado = true;
            mapaInstance = L.map('mapaEspana').setView([40.4637, -3.7492], 6);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors',
                maxZoom: 18
            }).addTo(mapaInstance);

            const marcadoresCluster = L.markerClusterGroup({
                chunkedLoading: true,
                maxClusterRadius: 50,
                spiderfyOnMaxZoom: true,
                showCoverageOnHover: false,
                zoomToBoundsOnClick: true,
                iconCreateFunction: function(cluster) {
                    let totalClicks = 0;
                    cluster.getAllChildMarkers().forEach(marker => {
                        totalClicks += marker._totalClicks || 1;
                    });
                    let className = 'marker-cluster ';
                    if (totalClicks < 10) className += 'marker-cluster-small';
                    else if (totalClicks < 100) className += 'marker-cluster-medium';
                    else className += 'marker-cluster-large';
                    return L.divIcon({
                        html: '<div><span>' + totalClicks + '</span></div>',
                        className: className,
                        iconSize: L.point(40, 40)
                    });
                }
            });

            puntosMapa.forEach(punto => {
                const iconSize = Math.min(20 + (punto.total_clicks * 2), 40);
                const iconColor = punto.total_clicks > 10 ? 'red' : punto.total_clicks > 5 ? 'orange' : 'blue';
                const icono = L.divIcon({
                    html: `<div style="background-color:${iconColor};width:${iconSize}px;height:${iconSize}px;border-radius:50%;border:2px solid white;display:flex;align-items:center;justify-content:center;color:white;font-weight:bold;font-size:${Math.min(iconSize * 0.4, 14)}px;box-shadow:0 2px 4px rgba(0,0,0,0.3);">${punto.total_clicks}</div>`,
                    className: 'marcador-personalizado',
                    iconSize: [iconSize, iconSize],
                    iconAnchor: [iconSize/2, iconSize/2]
                });
                const marcador = L.marker([punto.latitud, punto.longitud], { icon: icono });
                marcador._totalClicks = punto.total_clicks;
                marcador.bindPopup(`<div style="min-width:200px;"><h4 style="margin:0 0 8px 0;font-weight:bold;">📍 ${punto.ciudad}</h4><div><strong>Clicks:</strong> ${punto.total_clicks}</div><div><strong>IPs:</strong> ${punto.ips_unicas}</div></div>`);
                marcadoresCluster.addLayer(marcador);
            });

            mapaInstance.addLayer(marcadoresCluster);
            if (puntosMapa.length > 0) {
                mapaInstance.fitBounds(marcadoresCluster.getBounds().pad(0.1));
            }
        }
        @endif

        window.toggleSeccion = function(contenidoId, iconoId) {
            const contenido = document.getElementById(contenidoId);
            const icono = document.getElementById(iconoId);
            if (!contenido) return;
            contenido.classList.toggle('hidden');
            if (icono) icono.style.transform = contenido.classList.contains('hidden') ? 'rotate(0deg)' : 'rotate(180deg)';
        };

        window.toggleSeccionMapa = function() {
            toggleSeccion('contenidoMapa', 'iconoMapa');
            setTimeout(() => {
                if (!document.getElementById('contenidoMapa').classList.contains('hidden')) {
                    inicializarMapa();
                    if (mapaInstance) mapaInstance.invalidateSize();
                }
            }, 200);
        };
         
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

    <script>
    (function () {
        const TOP_URL = @json(route('admin.clicks.dashboard.top'));
        const filtros = {
            filtro_rapido: @json($filtroRapido),
            fecha_desde: @json($fechaDesde),
            fecha_hasta: @json($fechaHasta),
            hora_desde: @json($horaDesde),
            hora_hasta: @json($horaHasta),
        };
        const PER_PAGE = 10;
        const estadoTops = {};
        const tipos = ['productos', 'categorias', 'tiendas'];
        tipos.forEach(t => { estadoTops[t] = { page: 1, orden: t === 'tiendas' ? 'clicks' : 'ctr' }; });

        function fmt(n) { return new Intl.NumberFormat('es-ES').format(n); }
        function fmtMetric(n) {
            const num = Number(n);
            if (!Number.isFinite(num)) return '0';
            if (Math.abs(num - Math.round(num)) < 0.001) return fmt(Math.round(num));
            return new Intl.NumberFormat('es-ES', { minimumFractionDigits: 1, maximumFractionDigits: 2 }).format(num);
        }
        function fmtCtr(n) { return new Intl.NumberFormat('es-ES', { minimumFractionDigits: 1, maximumFractionDigits: 1 }).format(n) + '%'; }
        function truncarNombre(nombre, max = 48) {
            if (!nombre) return '(sin nombre)';
            return nombre.length > max ? nombre.substring(0, max) + '…' : nombre;
        }
        function ctrColor(ctr) {
            if (ctr >= 15) return 'bg-green-500';
            if (ctr >= 5) return 'bg-orange-500';
            return 'bg-gray-400';
        }
        function ctrTextColor(ctr) {
            if (ctr >= 15) return 'text-green-600 dark:text-green-400';
            if (ctr >= 5) return 'text-orange-600 dark:text-orange-400';
            return 'text-gray-500 dark:text-gray-400';
        }
        function rankClass(pos, page) {
            const globalPos = (page - 1) * PER_PAGE + pos;
            if (globalPos === 1) return 'rank-medal-1';
            if (globalPos === 2) return 'rank-medal-2';
            if (globalPos === 3) return 'rank-medal-3';
            return 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300';
        }
        function cuotaColumnTitle(orden) {
            if (orden === 'visitas') return 'Cuota';
            return 'Cuota';
        }
        function cuotaTextColor(orden) {
            if (orden === 'visitas') return 'text-blue-600 dark:text-blue-400';
            return 'text-orange-600 dark:text-orange-400';
        }
        function fmtCuotaPct(n) {
            return new Intl.NumberFormat('es-ES', { minimumFractionDigits: 1, maximumFractionDigits: 1 }).format(n) + '%';
        }
        function renderCuotaCell(row, orden) {
            const pct = Number(row.barra_pct ?? 0);
            const textColor = cuotaTextColor(orden);
            return `<td class="col-cuota"><span class="${textColor}">${fmtCuotaPct(pct)}</span></td>`;
        }
        function renderTableHeader(orden, soloClics = false) {
            if (soloClics) {
                return `<table class="top-data-table top-solo-clics">
                    <colgroup>
                        <col class="col-rank">
                        <col class="col-nombre">
                        <col class="col-num-clics">
                        <col class="col-cuota">
                    </colgroup>
                    <thead><tr>
                        <th class="col-rank">#</th>
                        <th class="col-nombre">Nombre</th>
                        <th class="col-num-clics">Clics</th>
                        <th class="col-cuota">Cuota</th>
                    </tr></thead><tbody>`;
            }
            return `<table class="top-data-table">
                <colgroup>
                    <col class="col-rank">
                    <col class="col-nombre">
                    <col class="col-num">
                    <col class="col-num">
                    <col class="col-num">
                    <col class="col-ctr">
                    <col class="col-cuota">
                </colgroup>
                <thead><tr>
                    <th class="col-rank">#</th>
                    <th class="col-nombre">Nombre</th>
                    <th class="col-num">Visitas</th>
                    <th class="col-num">Vis. ún.</th>
                    <th class="col-num">Clics</th>
                    <th class="col-ctr">CTR</th>
                    <th class="col-cuota">${cuotaColumnTitle(orden)}</th>
                </tr></thead><tbody>`;
        }
        function renderTableFooter() {
            return `</tbody></table>`;
        }
        function renderItem(row, index, page, orden, soloClics = false) {
            const pos = index + 1;
            const nombre = (row.nombre || '').replace(/"/g, '&quot;');
            const rank = (page - 1) * PER_PAGE + pos;
            if (soloClics) {
                return `<tr>
                    <td class="col-rank"><span class="top-rank-badge font-bold ${rankClass(pos, page)}">${rank}</span></td>
                    <td class="col-nombre text-sm font-medium text-gray-900 dark:text-gray-100" title="${nombre}">${truncarNombre(row.nombre)}</td>
                    <td class="col-num-clics text-sm text-right text-gray-700 dark:text-gray-300">${fmt(row.clicks)}</td>
                    ${renderCuotaCell(row, 'clicks')}
                </tr>`;
            }
            return `<tr>
                <td class="col-rank"><span class="top-rank-badge font-bold ${rankClass(pos, page)}">${rank}</span></td>
                <td class="col-nombre text-sm font-medium text-gray-900 dark:text-gray-100" title="${nombre}">${truncarNombre(row.nombre)}</td>
                <td class="col-num text-sm text-gray-700 dark:text-gray-300">${fmtMetric(row.visitas)}</td>
                <td class="col-num text-sm text-gray-700 dark:text-gray-300">${fmt(row.visitantes)}</td>
                <td class="col-num text-sm text-gray-700 dark:text-gray-300">${fmt(row.clicks)}</td>
                <td class="col-ctr text-xs font-bold ${ctrTextColor(row.ctr)}">${fmtCtr(row.ctr)}</td>
                ${renderCuotaCell(row, orden)}
            </tr>`;
        }
        function setOrdenActivo(tipo, orden) {
            const group = document.querySelector(`.top-orden-group[data-tipo="${tipo}"]`);
            if (!group) return;
            group.querySelectorAll('.top-orden-btn').forEach(btn => {
                btn.classList.toggle('active', btn.dataset.orden === orden);
            });
        }
        async function cargarTop(tipo, page) {
            const list = document.querySelector(`.top-list[data-tipo="${tipo}"]`);
            const pagDiv = document.querySelector(`.top-paginacion[data-tipo="${tipo}"]`);
            const totalSpan = document.querySelector(`.top-total[data-tipo="${tipo}"]`);
            const orden = estadoTops[tipo].orden || 'ctr';
            if (!list) return;
            list.innerHTML = `<div class="px-6 py-10 text-center text-sm text-gray-500 dark:text-gray-400"><div class="inline-block w-6 h-6 border-2 border-blue-500 border-t-transparent rounded-full animate-spin mb-2"></div><div>Cargando…</div></div>`;
            const params = new URLSearchParams({ tipo, orden, page, per_page: PER_PAGE, filtro_rapido: filtros.filtro_rapido || '', fecha_desde: filtros.fecha_desde || '', fecha_hasta: filtros.fecha_hasta || '', hora_desde: filtros.hora_desde || '', hora_hasta: filtros.hora_hasta || '' });
            try {
                const res = await fetch(`${TOP_URL}?${params}`, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' });
                const json = await res.json();
                const data = Array.isArray(json.data) ? json.data : [];
                const currentPage = Number(json.current_page) || 1;
                const lastPage = Number(json.last_page) || 1;
                const total = Number(json.total) || 0;
                const ordenActivo = json.orden || orden;
                const soloClics = json.modo === 'clicks' || tipo === 'tiendas';
                estadoTops[tipo].page = currentPage;
                if (data.length === 0) {
                    list.innerHTML = `<div class="px-6 py-12 text-center"><div class="text-gray-500 dark:text-gray-400 text-lg mb-2">📭</div><div class="text-sm text-gray-500 dark:text-gray-400">${json.error || (soloClics ? 'Sin clics a tiendas en este periodo' : 'Sin datos en este periodo')}</div></div>`;
                } else {
                    list.innerHTML = renderTableHeader(ordenActivo, soloClics) + data.map((row, i) => renderItem(row, i, currentPage, ordenActivo, soloClics)).join('') + renderTableFooter();
                }
                if (totalSpan) totalSpan.textContent = ' · ' + fmt(total) + ' total';
                if (pagDiv) {
                    pagDiv.querySelector('.top-prev').disabled = currentPage <= 1;
                    pagDiv.querySelector('.top-next').disabled = currentPage >= lastPage;
                    pagDiv.querySelector('.top-page-info').textContent = `Pág. ${currentPage} / ${lastPage}`;
                }
            } catch (e) {
                list.innerHTML = `<div class="px-6 py-10 text-center text-sm text-red-600 dark:text-red-400">Error al cargar</div>`;
                if (totalSpan) totalSpan.textContent = ' · 0 total';
            }
        }
        tipos.forEach(tipo => {
            cargarTop(tipo, 1);
            const pagDiv = document.querySelector(`.top-paginacion[data-tipo="${tipo}"]`);
            if (tipo !== 'tiendas') {
                const ordenGroup = document.querySelector(`.top-orden-group[data-tipo="${tipo}"]`);
                if (ordenGroup) {
                    ordenGroup.querySelectorAll('.top-orden-btn').forEach(btn => {
                        btn.addEventListener('click', () => {
                            const orden = btn.dataset.orden;
                            if (estadoTops[tipo].orden === orden) return;
                            estadoTops[tipo].orden = orden;
                            setOrdenActivo(tipo, orden);
                            cargarTop(tipo, 1);
                        });
                    });
                }
            }
            if (!pagDiv) return;
            pagDiv.querySelector('.top-prev').addEventListener('click', () => { if (estadoTops[tipo].page > 1) cargarTop(tipo, estadoTops[tipo].page - 1); });
            pagDiv.querySelector('.top-next').addEventListener('click', () => cargarTop(tipo, estadoTops[tipo].page + 1));
        });
    })();
    </script>

    <script>
    (function () {
        const GRAFICA_URL = @json(route('admin.clicks.dashboard.grafica'));
        const filtrosGrafica = {
            filtro_rapido: @json($filtroRapido),
            fecha_desde: @json($fechaDesde),
            fecha_hasta: @json($fechaHasta),
            hora_desde: @json($horaDesde),
            hora_hasta: @json($horaHasta),
        };
        const subtitulosGranularidad = {
            '15min': 'Agrupado cada 15 minutos',
            'hour': 'Agrupado por horas',
            'day': 'Agrupado por días',
            'month': 'Agrupado por meses',
            'year': 'Agrupado por años',
        };
        const maxTicksGranularidad = {
            '15min': 24,
            'hour': 48,
            'day': 31,
            'month': 18,
            'year': 12,
        };
        let graficaTemporalChart = null;
        let graficaTemporalCargada = false;
        let estadoGrafica = { granularidad: null };

        function setGranularidadActiva(granularidad) {
            document.querySelectorAll('.grafica-granularidad-btn').forEach(btn => {
                btn.classList.toggle('active', btn.dataset.granularidad === granularidad);
            });
        }

        function maxTicksForGranularidad(granularidad) {
            return maxTicksGranularidad[granularidad] || 31;
        }

        window.toggleSeccionGrafica = function() {
            if (typeof toggleSeccion === 'function') {
                toggleSeccion('contenidoGrafica', 'iconoGrafica');
            }
            setTimeout(() => {
                const contenido = document.getElementById('contenidoGrafica');
                if (!contenido || contenido.classList.contains('hidden')) return;
                if (!graficaTemporalCargada) {
                    cargarGraficaTemporal();
                    graficaTemporalCargada = true;
                } else if (graficaTemporalChart) {
                    graficaTemporalChart.resize();
                }
            }, 200);
        };

        document.querySelectorAll('.grafica-granularidad-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const granularidad = btn.dataset.granularidad;
                if (estadoGrafica.granularidad === granularidad) return;
                estadoGrafica.granularidad = granularidad;
                setGranularidadActiva(granularidad);
                const contenido = document.getElementById('contenidoGrafica');
                if (contenido && !contenido.classList.contains('hidden')) {
                    cargarGraficaTemporal();
                }
            });
        });

        function fmtGrafica(n) {
            return new Intl.NumberFormat('es-ES').format(n);
        }

        async function cargarGraficaTemporal() {
            const loading = document.getElementById('graficaTemporalLoading');
            const empty = document.getElementById('graficaTemporalEmpty');
            const wrap = document.getElementById('graficaTemporalWrap');
            const subtitle = document.getElementById('graficaTemporalSubtitulo');
            if (!loading || typeof Chart === 'undefined') return;

            loading.classList.remove('hidden');
            empty.classList.add('hidden');
            wrap.classList.add('hidden');
            if (subtitle) subtitle.textContent = 'Cargando…';

            const params = new URLSearchParams({
                filtro_rapido: filtrosGrafica.filtro_rapido || '',
                fecha_desde: filtrosGrafica.fecha_desde || '',
                fecha_hasta: filtrosGrafica.fecha_hasta || '',
                hora_desde: filtrosGrafica.hora_desde || '',
                hora_hasta: filtrosGrafica.hora_hasta || '',
            });
            if (estadoGrafica.granularidad) {
                params.set('granularidad', estadoGrafica.granularidad);
            }

            try {
                const res = await fetch(`${GRAFICA_URL}?${params}`, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                });
                const data = await res.json();
                loading.classList.add('hidden');

                const granularidadActiva = data.granularidad || data.granularidad_defecto || 'day';
                estadoGrafica.granularidad = granularidadActiva;
                setGranularidadActiva(granularidadActiva);

                const esManual = granularidadActiva !== (data.granularidad_defecto || granularidadActiva);
                const subtituloBase = subtitulosGranularidad[granularidadActiva] || 'Evolución del periodo';
                const subtituloDefecto = subtitulosGranularidad[data.granularidad_defecto] || '';
                if (subtitle) {
                    subtitle.textContent = esManual && subtituloDefecto
                        ? `${subtituloBase} (sugerido: ${subtituloDefecto.toLowerCase()})`
                        : subtituloBase;
                }

                const visitas = Array.isArray(data.visitas) ? data.visitas : [];
                const clicks = Array.isArray(data.clicks) ? data.clicks : [];
                const labels = Array.isArray(data.labels) ? data.labels : [];
                const ctr = Array.isArray(data.ctr) ? data.ctr : [];
                const hasData = visitas.some(v => v > 0) || clicks.some(v => v > 0);

                if (!labels.length || !hasData) {
                    empty.classList.remove('hidden');
                    return;
                }

                wrap.classList.remove('hidden');
                if (graficaTemporalChart) graficaTemporalChart.destroy();

                const isDark = document.documentElement.classList.contains('dark');
                const gridColor = isDark ? 'rgba(156, 163, 175, 0.2)' : 'rgba(209, 213, 219, 0.8)';
                const textColor = isDark ? '#9ca3af' : '#6b7280';
                const ctx = document.getElementById('graficaTemporal').getContext('2d');
                const muchosPuntos = labels.length > 60;
                const granularidadChart = granularidadActiva;

                graficaTemporalChart = new Chart(ctx, {
                    data: {
                        labels,
                        datasets: [
                            {
                                type: 'line',
                                label: 'Visitas',
                                data: visitas,
                                borderColor: 'rgba(59, 130, 246, 1)',
                                backgroundColor: 'rgba(59, 130, 246, 0.08)',
                                borderWidth: 4,
                                tension: 0.3,
                                pointRadius: muchosPuntos ? 0 : 4,
                                pointHoverRadius: 7,
                                yAxisID: 'y',
                                order: 1,
                            },
                            {
                                type: 'line',
                                label: 'Clics',
                                data: clicks,
                                borderColor: 'rgba(249, 115, 22, 1)',
                                backgroundColor: 'rgba(249, 115, 22, 0.08)',
                                borderWidth: 4,
                                tension: 0.3,
                                pointRadius: muchosPuntos ? 0 : 4,
                                pointHoverRadius: 7,
                                yAxisID: 'y',
                                order: 2,
                            },
                            {
                                type: 'bar',
                                label: 'CTR',
                                data: ctr,
                                backgroundColor: 'rgba(34, 197, 94, 0.5)',
                                borderColor: 'rgba(34, 197, 94, 1)',
                                borderWidth: 1,
                                borderRadius: 3,
                                yAxisID: 'y1',
                                order: 3,
                            },
                        ],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: { mode: 'index', intersect: false },
                        plugins: {
                            legend: {
                                position: 'top',
                                labels: { color: textColor, usePointStyle: true },
                            },
                            tooltip: {
                                callbacks: {
                                    label(context) {
                                        const label = context.dataset.label || '';
                                        const value = context.parsed.y ?? 0;
                                        if (context.dataset.yAxisID === 'y1') {
                                            return `${label}: ${value.toFixed(1)}%`;
                                        }
                                        return `${label}: ${fmtGrafica(value)}`;
                                    },
                                },
                            },
                        },
                        scales: {
                            x: {
                                ticks: {
                                    color: textColor,
                                    maxRotation: 45,
                                    autoSkip: true,
                                    maxTicksLimit: maxTicksForGranularidad(granularidadChart),
                                },
                                grid: { color: gridColor },
                            },
                            y: {
                                type: 'linear',
                                position: 'left',
                                beginAtZero: true,
                                ticks: { color: textColor, precision: 0 },
                                grid: { color: gridColor },
                                title: { display: true, text: 'Visitas / Clics', color: textColor },
                            },
                            y1: {
                                type: 'linear',
                                position: 'right',
                                beginAtZero: true,
                                suggestedMax: 100,
                                ticks: {
                                    color: textColor,
                                    callback: (value) => value + '%',
                                },
                                grid: { drawOnChartArea: false },
                                title: { display: true, text: 'CTR %', color: textColor },
                            },
                        },
                    },
                });
            } catch (e) {
                loading.classList.add('hidden');
                if (empty) {
                    empty.textContent = 'Error al cargar la gráfica';
                    empty.classList.remove('hidden');
                }
            }
        }

    })();
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
