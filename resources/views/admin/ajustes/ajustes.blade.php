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
                <h1 class="text-xl sm:text-2xl font-light text-white">Ajustes del Sistema</h1>
            </div>
            <div class="text-sm text-gray-300 hidden sm:block">
                {{ now()->format('d/m/Y H:i') }}
            </div>
        </div>
    </x-slot>

    <!-- Notification Toast -->
    <div id="toast-container" class="fixed top-5 right-5 z-50 flex flex-col space-y-3 pointer-events-none"></div>

    <div class="min-h-screen bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900 text-white">
        <div class="max-w-[1660px] mx-auto px-4 sm:px-6 lg:px-8 py-6">
            
            <!-- Flexible Sidebar + Content Layout (Strict inline flex style) -->
            <div style="display: flex; gap: 24px; align-items: flex-start; width: 100%;">
                
                <!-- Left Sidebar: Settings Tabs (250px fixed) -->
                <div class="space-y-4" style="width: 250px; min-width: 250px; flex-shrink: 0;">
                    <div class="bg-gray-800/80 backdrop-blur-sm rounded-2xl shadow-xl border border-gray-700/50 p-4">
                        <h4 class="text-xs font-semibold text-gray-400 uppercase tracking-wider px-3 mb-3">Secciones</h4>
                        <div class="space-y-1">
                            <button type="button" onclick="cambiarTabAjustes('cron')" id="tab-btn-cron"
                                    class="tab-ajuste-btn w-full flex items-center space-x-3 px-3 py-2.5 rounded-lg bg-blue-600/90 text-white font-medium shadow-md shadow-blue-600/10 transition-all select-none cursor-pointer">
                                <svg class="w-5 h-5 text-blue-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <span>Cron</span>
                            </button>
                            <button type="button" onclick="cambiarTabAjustes('productos')" id="tab-btn-productos"
                                    class="tab-ajuste-btn w-full flex items-center space-x-3 px-3 py-2.5 rounded-lg text-gray-300 hover:bg-gray-700/50 hover:text-white font-medium transition-all select-none cursor-pointer">
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                </svg>
                                <span>Productos</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Right panel: Active Tab Content Area -->
                <div class="space-y-6" style="flex: 1; min-width: 0;">

                    <div id="tab-panel-cron" class="tab-panel-ajustes space-y-6">
                    
                    <!-- Content Card Filtros (Togglable advanced style) -->
                    <div class="bg-gray-800/80 backdrop-blur-sm rounded-2xl shadow-xl border border-gray-700/50 p-5">
                        <div class="flex flex-wrap items-center gap-3">
                            <button type="button" onclick="toggleFiltros()"
                                    class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 text-white text-sm font-medium rounded-lg hover:from-blue-700 hover:to-indigo-700 transition-all duration-200 shadow-lg">
                                <svg id="iconoFiltros" class="w-4 h-4 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                                </svg>
                                <span>🔍 Filtros de Rango Temporal</span>
                            </button>

                            @if($filtroRapido || $fechaDesde || $fechaHasta || $horaDesde || $horaHasta || $busqueda)
                                <span class="text-sm text-green-400">✓ Filtros activos</span>
                                <a href="{{ route('admin.ajustes.index') }}"
                                   class="px-3 py-1.5 bg-red-600 text-white text-xs font-medium rounded-md hover:bg-red-700 transition-colors">
                                    Limpiar Todo
                                </a>
                            @endif
                        </div>

                        <!-- Togglable content (hidden by default) -->
                        <div id="contenidoFiltros" class="hidden mt-4">
                            <form method="GET" action="{{ route('admin.ajustes.index') }}" id="filtroForm" class="space-y-4 bg-gray-700/30 rounded-xl p-6 border border-gray-600/50">
                                <input type="hidden" name="filtro_rapido" id="filtro_rapido" value="{{ $filtroRapido }}">
                                
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
                                        <button type="button" onclick="aplicarFiltroRapido('siempre')" 
                                                class="px-3 py-1.5 text-xs font-medium rounded-md transition-colors {{ $filtroRapido === 'siempre' ? 'bg-blue-600 text-white shadow-lg shadow-blue-500/20' : 'bg-gray-600 text-gray-300 hover:bg-gray-500 border border-gray-500' }}">
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
                                        <input type="time" name="hora_desde" id="hora_desde" value="{{ $horaDesde }}" 
                                               class="w-full px-3 py-2 border border-gray-600 rounded-md text-sm bg-gray-700 text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    </div>
                                    
                                    <!-- Hora hasta -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-300 mb-1">Hora hasta:</label>
                                        <input type="time" name="hora_hasta" id="hora_hasta" value="{{ $horaHasta }}" 
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
                                            <input type="text" name="buscar" id="buscar" placeholder="Buscar por ejecición..." 
                                                value="{{ $busqueda }}"
                                                class="w-full pl-10 pr-4 py-2 border border-gray-600 rounded-md text-sm bg-gray-700 text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="flex justify-between items-center pt-4 border-t border-gray-600/50">
                                    <button type="submit" class="px-6 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-medium rounded-md hover:from-blue-700 hover:to-indigo-700 transform hover:scale-105 transition-all duration-200 shadow-lg">
                                        🔍 Aplicar Filtros
                                    </button>
                                    
                                    <a href="{{ route('admin.ajustes.index') }}" 
                                       class="px-6 py-2 bg-gradient-to-r from-gray-500 to-gray-600 text-white font-medium rounded-md hover:from-gray-600 hover:to-gray-700 transform hover:scale-105 transition-all duration-200 shadow-lg">
                                        🔄 Limpiar Todo
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Card Mapa de Ejecuciones -->
                    <div class="bg-gray-800/80 backdrop-blur-sm rounded-2xl shadow-xl border border-gray-700/50 p-5 space-y-6">
                        <h3 class="text-lg font-medium text-gray-200 flex items-center gap-2 border-b border-gray-700/50 pb-3">
                            <svg class="w-5 h-5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                            Mapa de Ejecución de Crons en el rango de tiempo
                        </h3>

                        <!-- Timeline continuo -->
                        <div class="pt-1">
                            <h4 class="text-sm font-semibold text-gray-300 flex items-center gap-2">
                                <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2H6a2 2 0 01-2-2v-4zM14 16a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2h-2a2 2 0 01-2-2v-4z"></path>
                                </svg>
                                Eje de Tiempos
                                <span class="text-xs font-normal text-gray-400">({{ $isSingleDay ? 'Barras = rango real' . (!empty($timelineMeta['grid_interval']) ? ' · cuadrícula ' . $timelineMeta['grid_interval'] . ' min' : '') : 'Línea de tiempo por días' }})</span>
                            </h4>

                            @php
                                $timelineWidth = (int) ($timelineMeta['width_px'] ?? 2880);
                                $gridInterval = $timelineMeta['grid_interval'] ?? null;
                                $barH = 14;
                                $laneGap = 3;
                                $rowPadY = 6;
                            @endphp

                            <div class="flex overflow-hidden rounded-xl border border-gray-700/50 bg-[#111827]">
                                <!-- Nombres fijos -->
                                <div class="w-72 shrink-0 border-r border-gray-700/40 bg-[#111827] z-20 shadow-[2px_0_5px_rgba(0,0,0,0.3)] py-4">
                                    <div class="text-xs font-semibold text-gray-400 border-b border-gray-700/40 pb-2 px-4 flex items-center h-8">
                                        Nombre del Cron
                                    </div>
                                    <div class="mt-2 divide-y divide-gray-700/40" id="mapa-nombres">
                                        @foreach($crons as $key => $cron)
                                            @if($cron['activo'])
                                                @php
                                                    $bars = $timelineBars[$key] ?? [];
                                                    $maxLane = 0;
                                                    foreach ($bars as $b) {
                                                        $maxLane = max($maxLane, (int) ($b['lane'] ?? 0));
                                                    }
                                                    $lanes = $maxLane + 1;
                                                    $rowH = max(32, ($lanes * $barH) + (($lanes - 1) * $laneGap) + ($rowPadY * 2));
                                                @endphp
                                                <a href="#cron-ajuste-{{ $key }}"
                                                   onclick="irAAjusteCron('{{ $key }}'); return false;"
                                                   class="fila-nombre text-xs font-medium text-gray-300 flex items-center gap-1.5 px-4 truncate transition-colors duration-150 hover:text-blue-300 hover:bg-blue-900/20 cursor-pointer"
                                                   data-row-key="{{ $key }}"
                                                   style="height: {{ $rowH }}px"
                                                   title="Ir a ajustes de {{ $cron['name'] }}">
                                                    <span class="w-2 h-2 rounded-full bg-emerald-500 inline-block shrink-0 animate-pulse"></span>
                                                    {{ $cron['name'] }}
                                                </a>
                                            @endif
                                        @endforeach
                                    </div>
                                </div>

                                <!-- Timeline scrollable -->
                                <div id="mapa-scroll-container" class="flex-1 overflow-x-auto py-4 pr-4 bg-gray-900/50 pl-2">
                                    <div id="mapa-grid" class="relative" style="width: {{ $timelineWidth }}px">
                                        {{-- Cuadrícula de fondo (intervalo mínimo de crons) + columnas de hora en punto --}}
                                        <div id="mapa-grid-bg" class="absolute inset-0 pointer-events-none z-0" aria-hidden="true">
                                            @foreach(($timelineMeta['grid_cells'] ?? []) as $idx => $cell)
                                                <div class="mapa-col-bg absolute top-0 bottom-0 box-border border-r transition-colors duration-75 {{ !empty($cell['is_hour']) ? 'bg-sky-900/30 border-sky-700/50' : (!empty($cell['alt']) ? 'bg-gray-800/20 border-gray-700/25' : 'border-gray-700/25') }}"
                                                     data-col-index="{{ $idx }}"
                                                     style="left: {{ $cell['left_pct'] }}%; width: {{ $cell['width_pct'] }}%"
                                                     title="{{ $cell['label'] }}"></div>
                                            @endforeach
                                        </div>

                                        <!-- Cabecera de ticks -->
                                        <div class="relative z-10 h-8 border-b border-gray-700/40 mb-2">
                                            @foreach(($timelineMeta['ticks'] ?? []) as $tick)
                                                @php
                                                    $parts = explode(':', $tick['label'] ?? '');
                                                    $horaNum = $parts[0] ?? '';
                                                    $minNum = $parts[1] ?? '';
                                                    $labelCorto = !empty($tick['is_hour'])
                                                        ? (string) intval($horaNum)
                                                        : (':' . $minNum);
                                                @endphp
                                                @if(!empty($tick['is_hour']))
                                                    <div class="mapa-header-col absolute top-0 bottom-0 flex items-end pb-0.5 pl-0.5 text-sm font-mono select-none text-sky-300 font-bold transition-colors duration-75 leading-none"
                                                         style="left: {{ $tick['left_pct'] }}%"
                                                         data-tick-left="{{ $tick['left_pct'] }}"
                                                         title="{{ $tick['label'] }}">{{ $labelCorto }}</div>
                                                @elseif($isSingleDay && $gridInterval && $gridInterval <= 30)
                                                    <div class="mapa-header-min absolute top-0 bottom-0 flex items-end pb-1 pl-0.5 text-[8px] font-mono select-none text-gray-500 transition-colors duration-75"
                                                         style="left: {{ $tick['left_pct'] }}%"
                                                         data-tick-left="{{ $tick['left_pct'] }}"
                                                         title="{{ $tick['label'] }}">{{ $labelCorto }}</div>
                                                @endif
                                            @endforeach
                                        </div>

                                        <!-- Filas -->
                                        <div class="relative z-10 divide-y divide-gray-700/40" id="mapa-filas">
                                            @foreach($crons as $key => $cron)
                                                @if($cron['activo'])
                                                    @php
                                                        $bars = $timelineBars[$key] ?? [];
                                                        $maxLane = 0;
                                                        foreach ($bars as $b) {
                                                            $maxLane = max($maxLane, (int) ($b['lane'] ?? 0));
                                                        }
                                                        $lanes = $maxLane + 1;
                                                        $rowH = max(32, ($lanes * $barH) + (($lanes - 1) * $laneGap) + ($rowPadY * 2));
                                                    @endphp
                                                    <div class="fila-cuadrícula relative transition-colors duration-75 bg-gray-800/10"
                                                         data-row-key="{{ $key }}"
                                                         style="height: {{ $rowH }}px">
                                                        {{-- Celdas programadas (borde amarillo) --}}
                                                        @foreach(($timelineScheduled[$key] ?? []) as $cellIdx)
                                                            @php
                                                                $schedCell = ($timelineMeta['grid_cells'] ?? [])[$cellIdx] ?? null;
                                                            @endphp
                                                            @if($schedCell)
                                                                <div class="mapa-slot-programado absolute top-0 bottom-0 pointer-events-none z-[5] box-border border-2 border-yellow-400/70"
                                                                     style="left: {{ $schedCell['left_pct'] }}%; width: {{ $schedCell['width_pct'] }}%"
                                                                     title="Programado: {{ $schedCell['label'] }}"></div>
                                                            @endif
                                                        @endforeach
                                                        {{-- Barras de ejecución --}}
                                                        @foreach($bars as $bar)
                                                            @php
                                                                $status = $bar['status'];
                                                                $exec = $bar['exec'];
                                                                $lane = (int) ($bar['lane'] ?? 0);
                                                                $top = $rowPadY + ($lane * ($barH + $laneGap));
                                                                $colorClass = 'bg-emerald-500 border-emerald-600';
                                                                if ($status === 'running') {
                                                                    $colorClass = 'bg-amber-400 border-amber-500 animate-pulse';
                                                                } elseif ($status === 'error') {
                                                                    $colorClass = 'bg-red-500 border-red-600';
                                                                }
                                                            @endphp
                                                            <div class="mapa-barra absolute rounded border cursor-pointer hover:brightness-110 hover:z-20 shadow-sm {{ $colorClass }}"
                                                                 style="left: {{ $bar['left_pct'] }}%; width: {{ $bar['width_pct'] }}%; top: {{ $top }}px; height: {{ $barH }}px;"
                                                                 onclick="mostrarDetalleModal(this)"
                                                                 data-exec-id="{{ $exec->id }}"
                                                                 data-exec-cron-name="{{ $cron['name'] }}"
                                                                 data-exec-status="{{ $status }}"
                                                                 data-exec-inicio="{{ \Carbon\Carbon::parse($exec->inicio)->format('H:i:s') }} ({{ \Carbon\Carbon::parse($exec->inicio)->format('d/m/Y') }})"
                                                                 data-exec-fin="{{ $exec->fin ? \Carbon\Carbon::parse($exec->fin)->format('H:i:s') : 'En curso/Sin registrar' }}"
                                                                 data-exec-total="{{ $exec->total }}"
                                                                 data-exec-guardado="{{ $exec->total_guardado }}"
                                                                 data-exec-errores="{{ $bar['errores_mostrar'] }}"
                                                                 data-exec-log-summary="{{ $bar['log_summary'] }}"
                                                                 data-exec-history-url="{{ $bar['history_url'] }}"
                                                                 title="{{ $cron['name'] }}&#10;{{ \Carbon\Carbon::parse($exec->inicio)->format('H:i:s') }} → {{ $exec->fin ? \Carbon\Carbon::parse($exec->fin)->format('H:i:s') : 'en curso' }}&#10;{{ $status === 'success' ? 'Éxito' : ($status === 'running' ? 'En curso' : 'Error') }}"></div>
                                                        @endforeach
                                                    </div>
                                                @endif
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Leyenda del mapa -->
                            <div class="flex items-center gap-4 text-xs text-gray-400 pt-1 justify-end px-2 flex-wrap">
                                @if($isSingleDay && $gridInterval)
                                    <span class="flex items-center gap-1.5"><span class="w-3.5 h-3.5 rounded border border-gray-600/50 bg-gray-800/40 inline-block"></span> Cuadrícula {{ $gridInterval }} min</span>
                                @endif
                                <span class="flex items-center gap-1.5"><span class="w-3.5 h-3.5 rounded bg-sky-900/50 border border-sky-700/60 inline-block"></span> Hora en punto</span>
                                <span class="flex items-center gap-1.5"><span class="w-3.5 h-3.5 rounded border-2 border-yellow-400/70 bg-transparent inline-block"></span> Programado</span>
                                <span class="flex items-center gap-1.5"><span class="w-3.5 h-3.5 rounded border-2 border-amber-400 bg-transparent inline-block"></span> Ahora</span>
                                <span class="flex items-center gap-1.5"><span class="w-3.5 h-3.5 rounded bg-amber-400 border border-amber-500 inline-block animate-pulse"></span> En Curso</span>
                                <span class="flex items-center gap-1.5"><span class="w-3.5 h-3.5 rounded bg-emerald-500 border border-emerald-600 inline-block"></span> Ejecución Correcta</span>
                                <span class="flex items-center gap-1.5"><span class="w-3.5 h-3.5 rounded bg-red-500 border border-red-600 inline-block"></span> Error en Ejecución</span>
                            </div>
                        </div>
                    </div>

                    <!-- Card Listado y Configuración de Crons -->
                    <div class="bg-gray-800/80 backdrop-blur-sm rounded-2xl shadow-xl border border-gray-700/50 overflow-hidden">
                        <div class="p-5 border-b border-gray-700/50 bg-gray-800/40 flex items-center justify-between flex-wrap gap-3">
                            <h3 class="text-lg font-medium text-gray-200 flex items-center gap-2 shrink-0">
                                <svg class="w-5 h-5 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path>
                                </svg>
                                Ajustes y Frecuencia de Ejecuciones
                            </h3>
                            <div class="relative w-full max-w-xs ml-auto">
                                <svg class="w-4 h-4 text-gray-500 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                                <input type="search" id="buscar-cron"
                                       placeholder="Buscar por nombre de cron..."
                                       oninput="filtrarCronsPorNombre(this.value)"
                                       class="w-full pl-9 pr-3 py-2 text-sm rounded-lg bg-gray-900 border border-gray-600 text-gray-200 placeholder-gray-500 focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                        </div>

                        <div class="overflow-x-auto">
                            <table id="tabla-crons" class="min-w-full divide-y divide-gray-700/40">
                                <thead class="bg-gray-900/30">
                                    <tr>
                                        <th scope="col" class="px-4 py-4 text-center text-xs font-semibold text-gray-400 uppercase tracking-wider w-16">
                                            Activo
                                        </th>
                                        <th scope="col" class="px-4 py-4 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider w-px whitespace-nowrap">
                                            Cron Job
                                        </th>
                                        <th scope="col" class="px-3 py-4 text-center text-xs font-semibold text-gray-400 uppercase tracking-wider w-20 whitespace-nowrap" title="Ejecuciones estimadas por día según la expresión cron">
                                            Ejec./día
                                        </th>
                                        <th scope="col" class="px-4 py-4 text-center text-xs font-semibold text-gray-400 uppercase tracking-wider w-20">
                                            Minuto
                                        </th>
                                        <th scope="col" class="px-4 py-4 text-center text-xs font-semibold text-gray-400 uppercase tracking-wider w-20">
                                            Hora
                                        </th>
                                        <th scope="col" class="px-4 py-4 text-center text-xs font-semibold text-gray-400 uppercase tracking-wider w-20">
                                            Día
                                        </th>
                                        <th scope="col" class="px-4 py-4 text-center text-xs font-semibold text-gray-400 uppercase tracking-wider w-20">
                                            Mes
                                        </th>
                                        <th scope="col" class="px-4 py-4 text-center text-xs font-semibold text-gray-400 uppercase tracking-wider w-20">
                                            Día Semana
                                        </th>
                                        <th scope="col" class="px-4 py-4 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider w-36">
                                            Historial / Última
                                        </th>
                                        <th scope="col" class="px-4 py-4 text-center text-xs font-semibold text-gray-400 uppercase tracking-wider w-28">
                                            Acciones
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-700/30 bg-gray-800/10">
                                    @foreach($crons as $key => $cron)
                                        @php
                                            $ejDia = (float) ($cron['ejecuciones_dia'] ?? 0);
                                            if ($ejDia >= 10) {
                                                $ejDiaLabel = (string) (int) round($ejDia);
                                            } elseif ($ejDia >= 1) {
                                                $ejDiaLabel = rtrim(rtrim(number_format($ejDia, 1, '.', ''), '0'), '.');
                                            } elseif ($ejDia > 0) {
                                                $ejDiaLabel = number_format($ejDia, 2, '.', '');
                                            } else {
                                                $ejDiaLabel = '0';
                                            }
                                        @endphp
                                        <tr id="cron-ajuste-{{ $key }}"
                                            class="cron-row hover:bg-gray-700/20 transition-all duration-200 {{ !$cron['activo'] ? 'opacity-40 bg-gray-900/20 grayscale-[25%] border-l-2 border-red-500/30' : '' }}"
                                            data-cron-key="{{ $key }}"
                                            data-cron-name="{{ strtolower($cron['name']) }}"
                                            data-ejec-dia="{{ $ejDia }}">
                                            <!-- Activo Checkbox -->
                                            <td class="px-4 py-4 whitespace-nowrap text-center">
                                                <div class="flex items-center justify-center">
                                                    <input type="checkbox" id="check-{{ $key }}" 
                                                           {{ $cron['activo'] ? 'checked' : '' }}
                                                           onchange="toggleCronActivoVisual(this); guardarConfiguracionAjax('{{ $key }}', 'activo', this.checked ? '1' : '0')"
                                                           class="w-4 h-4 text-blue-600 border-gray-600 rounded bg-gray-700 focus:ring-blue-500 focus:ring-offset-gray-800">
                                                </div>
                                            </td>

                                            <!-- Nombre e info de ruta -->
                                            <td class="px-4 py-4 whitespace-nowrap">
                                                <div class="text-xs font-semibold text-gray-200">{{ $cron['name'] }}</div>
                                                <div class="text-[10px] text-gray-500 mt-0.5 select-all">
                                                    <code>/{{ $cron['route'] }}</code>
                                                </div>
                                            </td>

                                            <!-- Ejecuciones / día -->
                                            <td class="px-3 py-4 whitespace-nowrap text-center">
                                                <span class="ejec-dia-valor text-sm font-mono font-semibold text-cyan-300" title="Estimación media de ejecuciones por día">{{ $ejDiaLabel }}</span>
                                            </td>

                                            <!-- Minuto Input -->
                                            <td class="px-2 py-4 text-center">
                                                <input type="text" value="{{ $cron['minuto'] }}" 
                                                       data-cron-field="minuto"
                                                       onchange="onCronScheduleChange(this, '{{ $key }}', 'minuto')"
                                                       class="w-16 px-1.5 py-1 text-xs text-center rounded bg-gray-900 border border-gray-600 text-white focus:ring-1 focus:ring-blue-500 focus:border-transparent font-mono">
                                            </td>

                                            <!-- Hora Input -->
                                            <td class="px-2 py-4 text-center">
                                                <input type="text" value="{{ $cron['hora'] }}" 
                                                       data-cron-field="hora"
                                                       onchange="onCronScheduleChange(this, '{{ $key }}', 'hora')"
                                                       class="w-16 px-1.5 py-1 text-xs text-center rounded bg-gray-900 border border-gray-600 text-white focus:ring-1 focus:ring-blue-500 focus:border-transparent font-mono">
                                            </td>

                                            <!-- Dia Input -->
                                            <td class="px-2 py-4 text-center">
                                                <input type="text" value="{{ $cron['dia'] }}" 
                                                       data-cron-field="dia"
                                                       onchange="onCronScheduleChange(this, '{{ $key }}', 'dia')"
                                                       class="w-16 px-1.5 py-1 text-xs text-center rounded bg-gray-900 border border-gray-600 text-white focus:ring-1 focus:ring-blue-500 focus:border-transparent font-mono">
                                            </td>

                                            <!-- Mes Input -->
                                            <td class="px-2 py-4 text-center">
                                                <input type="text" value="{{ $cron['mes'] }}" 
                                                       data-cron-field="mes"
                                                       onchange="onCronScheduleChange(this, '{{ $key }}', 'mes')"
                                                       class="w-16 px-1.5 py-1 text-xs text-center rounded bg-gray-900 border border-gray-600 text-white focus:ring-1 focus:ring-blue-500 focus:border-transparent font-mono">
                                            </td>

                                            <!-- Dia Semana Input -->
                                            <td class="px-2 py-4 text-center">
                                                <input type="text" value="{{ $cron['dia_semana'] }}" 
                                                       data-cron-field="dia_semana"
                                                       onchange="onCronScheduleChange(this, '{{ $key }}', 'dia_semana')"
                                                       class="w-16 px-1.5 py-1 text-xs text-center rounded bg-gray-900 border border-gray-600 text-white focus:ring-1 focus:ring-blue-500 focus:border-transparent font-mono">
                                            </td>

                                            <!-- Última Ejecución -->
                                            <td class="px-4 py-4 whitespace-nowrap text-xs text-gray-300">
                                                @if($cron['ultima_ejecucion'])
                                                    @php
                                                        $ultima = $cron['ultima_ejecucion'];
                                                        $ultimaLog = is_array($ultima->log) ? $ultima->log : [];
                                                        $ultimaLogEstado = $ultimaLog['estado'] ?? '';
                                                        if ($key === 'cron_avisos_sin_stock_scrapear') {
                                                            $ultimaEsFallo = ($ultimaLogEstado === 'error');
                                                        } else {
                                                            $ultimaEsFallo = ($ultima->total_errores > 0 || $ultimaLogEstado === 'error');
                                                        }
                                                        $ultimaResumen = $ultimaLog['resumen'] ?? ($ultimaLog['message'] ?? null);
                                                        if ($ultimaResumen === null && !empty($ultimaLog)) {
                                                            $ultimaResumen = is_string($ultimaLog) ? $ultimaLog : json_encode($ultimaLog, JSON_UNESCAPED_UNICODE);
                                                        }
                                                        $ultimaStatus = $ultimaEsFallo ? 'error' : 'success';
                                                        if (!$ultima->fin) {
                                                            $ultimaStatus = 'running';
                                                        }
                                                        $ultimaErroresMostrar = (int) $ultima->total_errores;
                                                        if ($key === 'cron_avisos_sin_stock_scrapear') {
                                                            $ultimaErroresMostrar = ($ultimaLogEstado === 'error') ? $ultimaErroresMostrar : 0;
                                                        }
                                                    @endphp
                                                    <button type="button"
                                                            onclick="mostrarDetalleModal(this)"
                                                            class="text-left w-full rounded-lg px-1 py-0.5 -mx-1 hover:bg-gray-700/40 transition-colors cursor-pointer"
                                                            title="Ver detalle de la última ejecución"
                                                            data-exec-id="{{ $ultima->id }}"
                                                            data-exec-cron-name="{{ $cron['name'] }}"
                                                            data-exec-status="{{ $ultimaStatus }}"
                                                            data-exec-inicio="{{ $ultima->inicio?->format('H:i:s') }} ({{ $ultima->inicio?->format('d/m/Y') }})"
                                                            data-exec-fin="{{ $ultima->fin ? $ultima->fin->format('H:i:s') : 'En curso/Sin registrar' }}"
                                                            data-exec-total="{{ $ultima->total ?? 0 }}"
                                                            data-exec-guardado="{{ $ultima->total_guardado ?? 0 }}"
                                                            data-exec-errores="{{ $ultimaErroresMostrar }}"
                                                            data-exec-log-summary="{{ e(is_string($ultimaResumen) ? $ultimaResumen : json_encode($ultimaResumen, JSON_UNESCAPED_UNICODE)) }}"
                                                            data-exec-history-url="">
                                                        <div class="flex items-center gap-1 text-gray-200">
                                                            @if($ultimaEsFallo)
                                                                <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                                                                <span class="text-red-400 font-semibold">Fallo</span>
                                                            @else
                                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                                                <span class="text-emerald-400 font-semibold">Éxito</span>
                                                            @endif
                                                            <span>hace {{ $ultima->inicio->diffForHumans(now(), true) }}</span>
                                                        </div>
                                                        <div class="text-[10px] text-gray-400 mt-0.5">
                                                            {{ $ultima->inicio->format('d/m H:i') }}
                                                        </div>
                                                        @if($ultimaResumen)
                                                            <div class="text-[10px] text-gray-500 mt-1 line-clamp-2 max-w-xs" title="{{ is_string($ultimaResumen) ? $ultimaResumen : json_encode($ultimaResumen, JSON_UNESCAPED_UNICODE) }}">
                                                                {{ is_string($ultimaResumen) ? $ultimaResumen : json_encode($ultimaResumen, JSON_UNESCAPED_UNICODE) }}
                                                            </div>
                                                        @endif
                                                    </button>
                                                @else
                                                    <span class="text-gray-500 italic">No hay ejecuciones</span>
                                                @endif
                                            </td>

                                            <!-- Acciones (Play + History Link) -->
                                            <td class="px-4 py-4 whitespace-nowrap text-center text-sm font-medium">
                                                <div class="flex items-center justify-center gap-2">
                                                    <!-- Play Button -->
                                                    <button onclick="ejecutarCronManualAjax('{{ $key }}', this)" 
                                                            class="inline-flex items-center justify-center p-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white transition-all shadow-md shadow-emerald-700/20"
                                                            title="Ejecutar ahora manualmente">
                                                        <svg class="w-4 h-4 play-icon" fill="currentColor" viewBox="0 0 24 24">
                                                            <path d="M8 5v14l11-7z"></path>
                                                        </svg>
                                                        <!-- Spinner (hidden by default) -->
                                                        <svg class="w-4 h-4 animate-spin hidden spinner-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                        </svg>
                                                    </button>
                                                    
                                                    <!-- History Link -->
                                                    @php
                                                        $historyRoute = null;
                                                        switch ($key) {
                                                            case 'clicks_actualizar_ofertas':
                                                                $historyRoute = route('admin.ofertas.actualizar.clicks.ejecuciones');
                                                                break;
                                                            case 'historico_guardar_productos':
                                                                $historyRoute = route('admin.productos.historico.ejecuciones');
                                                                break;
                                                            case 'precios_hot_calcular':
                                                                $historyRoute = route('admin.precios-hot.ejecuciones');
                                                                break;
                                                            case 'categorias_actualizar_clicks':
                                                                $historyRoute = route('admin.categorias.actualizar.clicks.ejecuciones');
                                                                break;
                                                            case 'productos_actualizar_clicks':
                                                                $historyRoute = route('admin.productos.actualizar.clicks.ejecuciones');
                                                                break;
                                                            case 'cron_avisos_sin_stock_scrapear':
                                                                $historyRoute = route('admin.ejecuciones.avisos-sin-stock-scrapear');
                                                                break;
                                                            case 'cron_descarga_csv_tiendas':
                                                                $historyRoute = route('admin.ejecuciones.descarga-csv-tiendas');
                                                                break;
                                                            case 'cron_buscar_amazon_productos':
                                                                $historyRoute = route('admin.ejecuciones.buscar-amazon-productos');
                                                                break;
                                                            case 'ofertas_scraper_segundo_plano':
                                                                $historyRoute = route('admin.ofertas.scraper.ejecuciones');
                                                                break;
                                                            case 'actualizar_primera_oferta_segundo_plano':
                                                                $historyRoute = route('admin.scraping.actualizar-primera-oferta.historial');
                                                                break;
                                                            case 'cron_neo_objetivos':
                                                                $historyRoute = url('/panel-privado/ejecuciones/neo-objetivos');
                                                                break;
                                                        }
                                                    @endphp
 
                                                     @if($historyRoute)
                                                         <a href="{{ $historyRoute }}" 
                                                            class="inline-flex items-center justify-center p-2 rounded-lg bg-gray-700 hover:bg-gray-600 text-gray-300 transition-all border border-gray-600"
                                                            title="Ver historial de ejecuciones de este cron">
                                                             <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                                                             </svg>
                                                         </a>
                                                     @endif
                                                 </div>
                                             </td>
                                         </tr>
                                     @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    </div>{{-- /tab-panel-cron --}}

                    <div id="tab-panel-productos" class="tab-panel-ajustes space-y-6 hidden">
                        <div class="bg-gray-800/80 backdrop-blur-sm rounded-2xl shadow-xl border border-gray-700/50 overflow-hidden">
                            <div class="p-5 border-b border-gray-700/50 bg-gray-800/40">
                                <h3 class="text-lg font-medium text-gray-200 flex items-center gap-2">
                                    <svg class="w-5 h-5 text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                    </svg>
                                    Historial
                                </h3>
                            </div>

                            <div class="p-6 space-y-6">
                                <div class="rounded-xl border border-gray-700/60 bg-gray-900/30 p-5 space-y-4">
                                    <div>
                                        <h4 class="text-base font-semibold text-white">Rellenar huecos</h4>
                                        <p class="text-sm text-gray-400 mt-2 leading-relaxed">
                                            Busca días vacíos en el historial de precios de todos los productos (precio general y especificaciones internas)
                                            y los rellena usando el precio del día anterior. Si no hay precio anterior en esa serie, usa el del día posterior.
                                        </p>
                                    </div>

                                    <div class="flex flex-wrap items-center gap-3">
                                        <button type="button" id="btn-rellenar-huecos" onclick="ejecutarRellenarHuecosHistorial(this)"
                                                class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg bg-violet-600 hover:bg-violet-700 text-white text-sm font-medium transition-all shadow-md shadow-violet-700/20">
                                            <svg class="w-4 h-4 btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                            </svg>
                                            <svg class="w-4 h-4 btn-spinner hidden animate-spin" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                            Rellenar huecos en todos los productos
                                        </button>
                                    </div>

                                    <div id="resultado-rellenar-huecos" class="hidden rounded-lg border p-4 text-sm"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </div>

    <!-- Modal Detalle Ejecución -->
    <div id="modal-ejecucion-detalle" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm hidden opacity-0 transition-opacity duration-300">
        <div class="bg-gray-800 border border-gray-700 rounded-2xl w-full max-w-lg shadow-2xl overflow-hidden transform scale-95 transition-transform duration-300">
            <div class="px-6 py-4 border-b border-gray-700 flex items-center justify-between">
                <h3 class="text-lg font-bold text-gray-100 flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500" id="det-status-dot"></span>
                    Detalles de Ejecución
                </h3>
                <button onclick="cerrarDetalleModal()" class="text-gray-400 hover:text-white transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            <div class="p-6 space-y-4 text-sm text-gray-300">
                <div>
                    <span class="text-gray-400 text-xs font-semibold uppercase tracking-wider block">Cron Job:</span>
                    <span id="det-cron-name" class="text-base font-medium text-white"></span>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <span class="text-gray-400 text-xs font-semibold uppercase tracking-wider block">Hora de Inicio:</span>
                        <span id="det-inicio" class="text-white font-mono"></span>
                    </div>
                    <div>
                        <span class="text-gray-400 text-xs font-semibold uppercase tracking-wider block">Hora de Fin:</span>
                        <span id="det-fin" class="text-white font-mono"></span>
                    </div>
                </div>
                <div class="grid grid-cols-3 gap-2 bg-gray-900/40 p-3 rounded-lg border border-gray-700/50">
                    <div class="text-center border-r border-gray-700/50">
                        <span class="text-xs text-gray-400 block">Total</span>
                        <span id="det-total" class="text-base font-bold text-white"></span>
                    </div>
                    <div class="text-center border-r border-gray-700/50">
                        <span class="text-xs text-emerald-400 block">Guardados</span>
                        <span id="det-guardado" class="text-base font-bold text-emerald-400"></span>
                    </div>
                    <div class="text-center">
                        <span class="text-xs text-red-400 block">Errores</span>
                        <span id="det-errores" class="text-base font-bold text-red-400"></span>
                    </div>
                </div>
                <div>
                    <span class="text-gray-400 text-xs font-semibold uppercase tracking-wider block mb-1">Resumen del Log:</span>
                    <pre id="det-log-summary" class="bg-gray-950 p-3 rounded-lg text-xs font-mono text-gray-300 max-h-36 overflow-y-auto whitespace-pre-wrap border border-gray-900"></pre>
                </div>
            </div>
            <div class="px-6 py-4 bg-gray-900/30 border-t border-gray-700/50 flex justify-between gap-3">
                <a id="det-history-btn" href="#" class="inline-flex items-center gap-1.5 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg text-xs transition-colors shadow-md shadow-blue-700/20">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                    Ver Historial Completo
                </a>
                <button onclick="cerrarDetalleModal()" class="px-4 py-2 bg-gray-700 hover:bg-gray-600 text-gray-300 font-medium rounded-lg text-xs transition-colors border border-gray-600">
                    Cerrar
                </button>
            </div>
        </div>
    </div>

    <!-- Custom JS logic -->
    <script>
        function cambiarTabAjustes(tab) {
            document.querySelectorAll('.tab-panel-ajustes').forEach(panel => panel.classList.add('hidden'));
            document.querySelectorAll('.tab-ajuste-btn').forEach(btn => {
                btn.classList.remove('bg-blue-600/90', 'text-white', 'shadow-md', 'shadow-blue-600/10');
                btn.classList.add('text-gray-300', 'hover:bg-gray-700/50', 'hover:text-white');
                btn.querySelector('svg')?.classList.remove('text-blue-200');
                btn.querySelector('svg')?.classList.add('text-gray-400');
            });

            const panel = document.getElementById('tab-panel-' + tab);
            const btn = document.getElementById('tab-btn-' + tab);
            if (panel) panel.classList.remove('hidden');
            if (btn) {
                btn.classList.add('bg-blue-600/90', 'text-white', 'shadow-md', 'shadow-blue-600/10');
                btn.classList.remove('text-gray-300', 'hover:bg-gray-700/50', 'hover:text-white');
                btn.querySelector('svg')?.classList.add('text-blue-200');
                btn.querySelector('svg')?.classList.remove('text-gray-400');
            }
        }

        async function ejecutarRellenarHuecosHistorial(button) {
            const icon = button.querySelector('.btn-icon');
            const spinner = button.querySelector('.btn-spinner');
            const resultado = document.getElementById('resultado-rellenar-huecos');

            button.disabled = true;
            icon.classList.add('hidden');
            spinner.classList.remove('hidden');
            resultado.classList.add('hidden');

            try {
                const res = await fetch('{{ route('admin.ajustes.productos.historial.rellenar-huecos') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                });
                const data = await res.json();

                resultado.classList.remove('hidden');
                if (!res.ok || data.status === 'error') {
                    resultado.className = 'rounded-lg border border-red-500/40 bg-red-900/20 p-4 text-sm text-red-200';
                    resultado.innerHTML = `<p class="font-semibold">Error</p><p class="mt-1">${data.message || 'No se pudo completar la operación.'}</p>`;
                    showToast(data.message || 'Error al rellenar huecos', 'error');
                    return;
                }

                resultado.className = 'rounded-lg border border-emerald-500/40 bg-emerald-900/20 p-4 text-sm text-emerald-100 space-y-2';
                resultado.innerHTML = `
                    <p class="font-semibold text-white">${data.message}</p>
                    <p><span class="text-gray-400">Historiales de producto actualizados:</span> <strong>${data.historiales_producto ?? 0}</strong></p>
                    <p><span class="text-gray-400">Historiales de especificación actualizados:</span> <strong>${data.historiales_especificacion ?? 0}</strong></p>
                `;
                showToast(data.message, 'success');
            } catch (e) {
                resultado.classList.remove('hidden');
                resultado.className = 'rounded-lg border border-red-500/40 bg-red-900/20 p-4 text-sm text-red-200';
                resultado.textContent = 'Error de conexión al ejecutar la prueba.';
                showToast('Error de conexión', 'error');
            } finally {
                button.disabled = false;
                icon.classList.remove('hidden');
                spinner.classList.add('hidden');
            }
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

        // Aplicar Filtro rápido de fecha
        function aplicarFiltroRapido(filtro) {
            document.getElementById('filtro_rapido').value = filtro;
            // Limpiar inputs manuales si pulsa filtro rápido
            document.getElementById('fecha_desde').value = '';
            document.getElementById('fecha_hasta').value = '';
            document.getElementById('hora_desde').value = '';
            document.getElementById('hora_hasta').value = '';
            document.getElementById('buscar').value = '';
            document.getElementById('filtroForm').submit();
        }

        // Desactivar filtros rápidos si cambian fechas o inputs manualmente
        document.addEventListener('DOMContentLoaded', function() {
            const inputsManuales = ['fecha_desde', 'fecha_hasta', 'hora_desde', 'hora_hasta', 'buscar'];
            inputsManuales.forEach(id => {
                const el = document.getElementById(id);
                if (el) {
                    el.addEventListener('change', () => {
                        document.getElementById('filtro_rapido').value = '';
                    });
                    el.addEventListener('input', () => {
                        document.getElementById('filtro_rapido').value = '';
                    });
                }
            });

            // Expandir filtros si hay parámetros de búsqueda manuales en la URL
            const urlParams = new URLSearchParams(window.location.search);
            const tieneFiltros = urlParams.has('fecha_desde') || 
                                urlParams.has('fecha_hasta') || 
                                urlParams.has('hora_desde') || 
                                urlParams.has('hora_hasta') || 
                                urlParams.has('buscar');
            
            if (tieneFiltros) {
                const contenidoFiltros = document.getElementById('contenidoFiltros');
                const iconoFiltros = document.getElementById('iconoFiltros');
                if (contenidoFiltros && iconoFiltros) {
                    contenidoFiltros.classList.remove('hidden');
                    iconoFiltros.style.transform = 'rotate(180deg)';
                }
            }
        });

        // Modal Detalle
        function mostrarDetalleModal(el) {
            const modal = document.getElementById('modal-ejecucion-detalle');
            const cronName = el.getAttribute('data-exec-cron-name');
            const status = el.getAttribute('data-exec-status');
            const inicio = el.getAttribute('data-exec-inicio');
            const fin = el.getAttribute('data-exec-fin');
            const total = el.getAttribute('data-exec-total');
            const guardado = el.getAttribute('data-exec-guardado');
            const errores = el.getAttribute('data-exec-errores');
            const logSummary = el.getAttribute('data-exec-log-summary');
            const historyUrl = el.getAttribute('data-exec-history-url');

            document.getElementById('det-cron-name').innerText = cronName;
            document.getElementById('det-inicio').innerText = inicio;
            document.getElementById('det-fin').innerText = fin;
            document.getElementById('det-total').innerText = total || '0';
            document.getElementById('det-guardado').innerText = guardado || '0';
            document.getElementById('det-errores').innerText = errores || '0';
            
            // Si es un log JSON formateado o un objeto, tratar de mostrarlo de forma bonita
            let formattedLog = logSummary;
            try {
                if (logSummary && (logSummary.startsWith('{') || logSummary.startsWith('['))) {
                    const parsed = JSON.parse(logSummary);
                    formattedLog = JSON.stringify(parsed, null, 2);
                }
            } catch(e) {}
            
            document.getElementById('det-log-summary').innerText = formattedLog || 'No hay log disponible.';

            const dot = document.getElementById('det-status-dot');
            if (status === 'error') {
                dot.className = 'w-2.5 h-2.5 rounded-full bg-red-500 animate-pulse';
            } else {
                dot.className = 'w-2.5 h-2.5 rounded-full bg-emerald-500';
            }

            const historyBtn = document.getElementById('det-history-btn');
            if (historyUrl) {
                historyBtn.href = historyUrl;
                historyBtn.classList.remove('hidden');
            } else {
                historyBtn.classList.add('hidden');
            }

            // Show modal
            modal.classList.remove('hidden');
            setTimeout(() => {
                modal.classList.remove('opacity-0');
                modal.firstElementChild.classList.remove('scale-95');
            }, 10);
        }

        function cerrarDetalleModal() {
            const modal = document.getElementById('modal-ejecucion-detalle');
            modal.classList.add('opacity-0');
            modal.firstElementChild.classList.add('scale-95');
            setTimeout(() => {
                modal.classList.add('hidden');
            }, 300);
        }

        // Close modal on clicking backdrop
        document.getElementById('modal-ejecucion-detalle').addEventListener('click', function(e) {
            if (e.target === this) {
                cerrarDetalleModal();
            }
        });

        // Toast Messages Helper
        function showToast(message, type = 'success') {
            const container = document.getElementById('toast-container');
            const toast = document.createElement('div');
            
            toast.className = `flex items-center w-full max-w-xs p-4 text-gray-300 rounded-lg shadow-lg bg-gray-800 border border-gray-700/50 pointer-events-auto transform translate-y-2 opacity-0 transition-all duration-300`;
            
            const colorClass = type === 'success' ? 'text-emerald-400 bg-emerald-900/20' : 'text-red-400 bg-red-900/20';
            const icon = type === 'success' 
                ? `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>` 
                : `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>`;

            toast.innerHTML = `
                <div class="inline-flex items-center justify-center shrink-0 w-8 h-8 rounded-lg ${colorClass}">
                    ${icon}
                </div>
                <div class="ml-3 text-sm font-normal">${message}</div>
            `;
            
            container.appendChild(toast);
            
            // Animar entrada
            setTimeout(() => {
                toast.classList.remove('translate-y-2', 'opacity-0');
            }, 10);
            
            // Auto eliminar tras 4 segundos
            setTimeout(() => {
                toast.classList.add('translate-y-2', 'opacity-0');
                setTimeout(() => {
                    toast.remove();
                }, 300);
            }, 4000);
        }

        function irAAjusteCron(key) {
            const buscar = document.getElementById('buscar-cron');
            if (buscar && buscar.value) {
                buscar.value = '';
                filtrarCronsPorNombre('');
            }

            const row = document.getElementById('cron-ajuste-' + key);
            if (!row) return;

            row.scrollIntoView({ behavior: 'smooth', block: 'center' });
            row.classList.add('ring-2', 'ring-blue-400', 'bg-blue-900/30');
            setTimeout(() => {
                row.classList.remove('ring-2', 'ring-blue-400', 'bg-blue-900/30');
            }, 2200);
        }

        // AJAX update settings
        const CRON_INACTIVO_CLASSES = ['opacity-40', 'bg-gray-900/20', 'grayscale-[25%]', 'border-l-2', 'border-red-500/30'];

        function toggleCronActivoVisual(checkbox) {
            const row = checkbox.closest('tr');
            if (!row) return;

            if (checkbox.checked) {
                row.classList.remove(...CRON_INACTIVO_CLASSES);
            } else {
                row.classList.add(...CRON_INACTIVO_CLASSES);
            }
            reordenarCronsPorFrecuencia();
        }

        function filtrarCronsPorNombre(query) {
            const q = (query || '').trim().toLowerCase();
            document.querySelectorAll('#tabla-crons tbody tr.cron-row').forEach(row => {
                const name = row.getAttribute('data-cron-name') || '';
                row.style.display = (!q || name.includes(q)) ? '' : 'none';
            });
        }

        function countCronFieldMatches(field, min, max) {
            field = String(field ?? '').trim();
            if (field === '' || field === '*') return max - min + 1;

            const values = new Set();
            for (const rawPart of field.split(',')) {
                const part = rawPart.trim();
                if (!part) continue;

                if (part.includes('/')) {
                    const [range, stepStr] = part.split('/');
                    const step = Math.max(1, parseInt(stepStr, 10) || 1);
                    let start = min, end = max;
                    if (range && range !== '*') {
                        if (range.includes('-')) {
                            const [a, b] = range.split('-').map(n => parseInt(n, 10));
                            start = a; end = b;
                        } else {
                            start = parseInt(range, 10);
                            end = max;
                        }
                    }
                    for (let i = start; i <= end; i += step) {
                        if (i >= min && i <= max) values.add(i);
                    }
                    continue;
                }

                if (part.includes('-')) {
                    const [a, b] = part.split('-').map(n => parseInt(n, 10));
                    for (let i = a; i <= b; i++) {
                        if (i >= min && i <= max) values.add(i);
                    }
                    continue;
                }

                const v = parseInt(part, 10);
                if (!Number.isNaN(v) && v >= min && v <= max) values.add(v);
            }

            return values.size;
        }

        function countCronFieldMatchesDow(field) {
            field = String(field ?? '').trim();
            if (field === '' || field === '*') return 7;

            const raw = new Set();
            for (const rawPart of field.split(',')) {
                const part = rawPart.trim();
                if (!part) continue;

                if (part.includes('/')) {
                    const [range, stepStr] = part.split('/');
                    const step = Math.max(1, parseInt(stepStr, 10) || 1);
                    let start = 0, end = 6;
                    if (range && range !== '*') {
                        if (range.includes('-')) {
                            const [a, b] = range.split('-').map(n => parseInt(n, 10));
                            start = a; end = b;
                        } else {
                            start = parseInt(range, 10);
                            end = 6;
                        }
                    }
                    for (let i = start; i <= end; i += step) raw.add(i);
                    continue;
                }

                if (part.includes('-')) {
                    const [a, b] = part.split('-').map(n => parseInt(n, 10));
                    for (let i = a; i <= b; i++) raw.add(i);
                    continue;
                }

                raw.add(parseInt(part, 10));
            }

            const normalized = new Set();
            for (let v of raw) {
                if (v === 7) v = 0;
                if (v >= 0 && v <= 6) normalized.add(v);
            }
            return normalized.size;
        }

        function calcularEjecucionesPorDia(minuto, hora, dia, mes, diaSemana) {
            const mins = countCronFieldMatches(minuto, 0, 59);
            const hours = countCronFieldMatches(hora, 0, 23);
            const days = countCronFieldMatches(dia, 1, 31);
            const months = countCronFieldMatches(mes, 1, 12);
            const dows = countCronFieldMatchesDow(diaSemana);

            const perMatchingDay = mins * hours;
            const diaRestricted = String(dia ?? '').trim() !== '*' && String(dia ?? '').trim() !== '';
            const dowRestricted = String(diaSemana ?? '').trim() !== '*' && String(diaSemana ?? '').trim() !== '';
            const mesRestricted = String(mes ?? '').trim() !== '*' && String(mes ?? '').trim() !== '';

            let dayFactor = 1;
            if (diaRestricted && dowRestricted) {
                const pDay = days / 30.437;
                const pDow = dows / 7;
                dayFactor = Math.min(1, pDay + pDow - (pDay * pDow));
            } else if (diaRestricted) {
                dayFactor = days / 30.437;
            } else if (dowRestricted) {
                dayFactor = dows / 7;
            }

            const monthFactor = mesRestricted ? (months / 12) : 1;
            return Math.round(perMatchingDay * dayFactor * monthFactor * 10000) / 10000;
        }

        function formatEjecucionesDia(n) {
            if (n >= 10) return String(Math.round(n));
            if (n >= 1) {
                const s = n.toFixed(1);
                return s.replace(/\.0$/, '');
            }
            if (n > 0) return n.toFixed(2);
            return '0';
        }

        function actualizarEjecucionesDiaFila(row) {
            const get = (field) => {
                const input = row.querySelector(`input[data-cron-field="${field}"]`);
                return input ? input.value : '*';
            };
            const ej = calcularEjecucionesPorDia(get('minuto'), get('hora'), get('dia'), get('mes'), get('dia_semana'));
            row.setAttribute('data-ejec-dia', String(ej));
            const label = row.querySelector('.ejec-dia-valor');
            if (label) label.textContent = formatEjecucionesDia(ej);
        }

        function reordenarCronsPorFrecuencia() {
            const tbody = document.querySelector('#tabla-crons tbody');
            if (!tbody) return;
            const rows = Array.from(tbody.querySelectorAll('tr.cron-row'));
            rows.sort((a, b) => {
                const aActivo = a.querySelector('input[type="checkbox"]')?.checked ? 1 : 0;
                const bActivo = b.querySelector('input[type="checkbox"]')?.checked ? 1 : 0;
                if (aActivo !== bActivo) return bActivo - aActivo;
                const diff = parseFloat(b.dataset.ejecDia || '0') - parseFloat(a.dataset.ejecDia || '0');
                if (diff !== 0) return diff;
                return (a.dataset.cronName || '').localeCompare(b.dataset.cronName || '');
            });
            rows.forEach(r => tbody.appendChild(r));
        }

        function onCronScheduleChange(input, key, campo) {
            const row = input.closest('tr');
            if (row) {
                actualizarEjecucionesDiaFila(row);
                reordenarCronsPorFrecuencia();
            }
            guardarConfiguracionAjax(key, campo, input.value);
        }

        function guardarConfiguracionAjax(key, campo, value) {
            fetch('{{ route("admin.ajustes.actualizar") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ key, campo, value })
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'ok') {
                    showToast('Configuración guardada correctamente.', 'success');
                } else {
                    showToast(data.message || 'Error guardando la configuración.', 'error');
                }
            })
            .catch(err => {
                console.error(err);
                showToast('Error de conexión al guardar configuración.', 'error');
            });
        }

        // AJAX manual execution
        function ejecutarCronManualAjax(key, button) {
            const playIcon = button.querySelector('.play-icon');
            const spinnerIcon = button.querySelector('.spinner-icon');
            
            // Deshabilitar botón y activar spinner
            button.disabled = true;
            playIcon.classList.add('hidden');
            spinnerIcon.classList.remove('hidden');
            button.classList.remove('bg-emerald-600', 'hover:bg-emerald-700');
            button.classList.add('bg-gray-600', 'cursor-not-allowed');

            showToast('Lanzando ejecución del cron manualmente...', 'success');

            const route = '{{ route("admin.ajustes.ejecutar", ":key") }}'.replace(':key', key);

            fetch(route, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'ok') {
                    showToast(data.message + (data.detail ? ' Detalle: ' + data.detail : ''), 'success');
                } else {
                    showToast(data.message || 'Fallo durante la ejecución manual del cron.', 'error');
                }
            })
            .catch(err => {
                console.error(err);
                showToast('Error de conexión o timeout durante la ejecución.', 'error');
            })
            .finally(() => {
                // Reestablecer botón
                button.disabled = false;
                playIcon.classList.remove('hidden');
                spinnerIcon.classList.add('hidden');
                button.classList.add('bg-emerald-600', 'hover:bg-emerald-700');
                button.classList.remove('bg-gray-600', 'cursor-not-allowed');
            });
        }

        // Copiar comando al portapapeles
        function copiarComando(id) {
            const copyText = document.getElementById(id);
            if (copyText) {
                copyText.select();
                copyText.setSelectionRange(0, 99999);
                navigator.clipboard.writeText(copyText.value)
                    .then(() => showToast('Comando copiado al portapapeles.', 'success'))
                    .catch(() => showToast('Error al copiar el comando.', 'error'));
            }
        }

        // Resaltado de fila + columna en el mapa timeline
        document.addEventListener('DOMContentLoaded', function() {
            const mapaGrid = document.getElementById('mapa-grid');
            const gridCells = @json($timelineMeta['grid_cells'] ?? []);
            let activeRowKey = null;
            let activeColIdx = null;

            function colIndexFromX(x) {
                if (!mapaGrid || !gridCells.length) return null;
                const pct = (x / mapaGrid.offsetWidth) * 100;
                let found = 0;
                for (let i = 0; i < gridCells.length; i++) {
                    if (pct >= gridCells[i].left_pct) found = i;
                    else break;
                }
                return found;
            }

            function clearColumnHighlight() {
                document.querySelectorAll('.mapa-col-bg.mapa-col-hover').forEach(el => {
                    el.classList.remove('mapa-col-hover', '!bg-blue-500/25', 'ring-1', 'ring-inset', 'ring-blue-400/40');
                });
                document.querySelectorAll('.mapa-header-col.mapa-col-hover, .mapa-header-min.mapa-col-hover').forEach(el => {
                    el.classList.remove('mapa-col-hover', 'text-blue-300', 'font-bold');
                });
                activeColIdx = null;
            }

            function setColumnHighlight(colIdx) {
                if (colIdx === activeColIdx) return;
                clearColumnHighlight();
                if (colIdx === null || !gridCells[colIdx]) return;

                const col = document.querySelector(`.mapa-col-bg[data-col-index="${colIdx}"]`);
                if (col) {
                    // Solo fondo en hover; si es la columna "ahora", no tocar su borde amarillo
                    col.classList.add('mapa-col-hover', '!bg-blue-500/25');
                    if (!col.classList.contains('mapa-col-ahora')) {
                        col.classList.add('ring-1', 'ring-inset', 'ring-blue-400/40');
                    }
                }

                const left = gridCells[colIdx].left_pct;
                document.querySelectorAll('.mapa-header-col, .mapa-header-min').forEach(el => {
                    const t = parseFloat(el.getAttribute('data-tick-left'));
                    if (!Number.isNaN(t) && Math.abs(t - left) < 0.01) {
                        el.classList.add('mapa-col-hover', 'text-blue-300', 'font-bold');
                    }
                });

                activeColIdx = colIdx;
            }

            function clearMapaHighlight() {
                if (activeRowKey !== null) {
                    document.querySelectorAll(`.fila-cuadrícula[data-row-key="${activeRowKey}"], .fila-nombre[data-row-key="${activeRowKey}"]`).forEach(el => {
                        el.classList.remove('!bg-blue-900/40');
                    });
                }
                activeRowKey = null;
                clearColumnHighlight();
            }

            function setRowHighlight(rowKey) {
                if (activeRowKey === rowKey) return;
                if (activeRowKey !== null) {
                    document.querySelectorAll(`.fila-cuadrícula[data-row-key="${activeRowKey}"], .fila-nombre[data-row-key="${activeRowKey}"]`).forEach(el => {
                        el.classList.remove('!bg-blue-900/40');
                    });
                }
                if (rowKey !== null) {
                    document.querySelectorAll(`.fila-cuadrícula[data-row-key="${rowKey}"], .fila-nombre[data-row-key="${rowKey}"]`).forEach(el => {
                        el.classList.add('!bg-blue-900/40');
                    });
                }
                activeRowKey = rowKey;
            }

            if (mapaGrid) {
                mapaGrid.addEventListener('mousemove', function(e) {
                    const rect = mapaGrid.getBoundingClientRect();
                    const x = e.clientX - rect.left;
                    setColumnHighlight(colIndexFromX(x));

                    const row = e.target.closest('.fila-cuadrícula');
                    setRowHighlight(row ? row.getAttribute('data-row-key') : null);
                });

                mapaGrid.addEventListener('mouseleave', function() {
                    clearMapaHighlight();
                });
            }

            // Escala: ~3 horas visibles; a la derecha del "ahora" como mucho 30 min
            const isSingleDay = @json($isSingleDay);
            const VISIBLE_MINUTES = 3 * 60; // 3 horas en pantalla
            const FUTURE_MINUTES = 30;      // margen a la derecha de la hora actual
            const DAY_MINUTES = 1440;

            function markCurrentTimeColumn() {
                document.querySelectorAll('.mapa-col-bg.mapa-col-ahora').forEach(el => {
                    el.classList.remove('mapa-col-ahora', 'z-[1]');
                    el.style.boxShadow = '';
                });
                document.querySelectorAll('.mapa-header-col.mapa-col-ahora, .mapa-header-min.mapa-col-ahora').forEach(el => {
                    el.classList.remove('mapa-col-ahora', 'text-amber-300');
                });

                if (!isSingleDay || !gridCells.length) return;

                const now = new Date();
                const minutesNow = now.getHours() * 60 + now.getMinutes() + now.getSeconds() / 60;
                const pctNow = (minutesNow / DAY_MINUTES) * 100;

                let colIdx = 0;
                for (let i = 0; i < gridCells.length; i++) {
                    if (pctNow >= gridCells[i].left_pct) colIdx = i;
                    else break;
                }

                const col = document.querySelector(`.mapa-col-bg[data-col-index="${colIdx}"]`);
                if (col) {
                    col.classList.add('mapa-col-ahora', 'z-[1]');
                    // box-shadow propio: no lo pisa el ring azul del hover
                    col.style.boxShadow = 'inset 0 0 0 2px rgb(251 191 36)';
                }

                const left = gridCells[colIdx]?.left_pct;
                if (left == null) return;
                document.querySelectorAll('.mapa-header-col, .mapa-header-min').forEach(el => {
                    const t = parseFloat(el.getAttribute('data-tick-left'));
                    if (!Number.isNaN(t) && Math.abs(t - left) < 0.01) {
                        el.classList.add('mapa-col-ahora', 'text-amber-300');
                    }
                });
            }

            function layoutTimelineViewport(scrollToNow = true) {
                if (!isSingleDay || !mapaGrid) return;
                const scrollContainer = document.getElementById('mapa-scroll-container');
                if (!scrollContainer) return;

                const vw = scrollContainer.clientWidth;
                if (vw <= 0) return;

                const timelineWidth = (DAY_MINUTES / VISIBLE_MINUTES) * vw;
                mapaGrid.style.width = timelineWidth + 'px';

                markCurrentTimeColumn();

                if (!scrollToNow) return;

                const now = new Date();
                const minutesNow = now.getHours() * 60 + now.getMinutes() + now.getSeconds() / 60;
                const nowPx = (minutesNow / DAY_MINUTES) * timelineWidth;
                const futurePx = (FUTURE_MINUTES / DAY_MINUTES) * timelineWidth;
                // "Ahora" queda cerca del borde derecho, con ~30 min de futuro a su derecha
                scrollContainer.scrollLeft = Math.max(0, Math.min(
                    timelineWidth - vw,
                    nowPx - (vw - futurePx)
                ));
            }

            requestAnimationFrame(() => layoutTimelineViewport(true));
            // Actualizar columna "ahora" cada minuto
            setInterval(markCurrentTimeColumn, 60 * 1000);
            let resizeTimer = null;
            window.addEventListener('resize', () => {
                clearTimeout(resizeTimer);
                resizeTimer = setTimeout(() => layoutTimelineViewport(false), 120);
            });

            // Sincronizar hover de filas entre la columna fija y la timeline
            const nameRows = document.querySelectorAll('.fila-nombre');
            nameRows.forEach(nameRow => {
                const key = nameRow.getAttribute('data-row-key');
                const gridRow = document.querySelector(`.fila-cuadrícula[data-row-key="${key}"]`);
                if (gridRow) {
                    nameRow.addEventListener('mouseenter', () => {
                        nameRow.classList.add('!bg-blue-900/40');
                        gridRow.classList.add('!bg-blue-900/40');
                    });
                    nameRow.addEventListener('mouseleave', () => {
                        if (activeRowKey === key) return;
                        nameRow.classList.remove('!bg-blue-900/40');
                        gridRow.classList.remove('!bg-blue-900/40');
                    });
                }
            });
        });
    </script>
</x-app-layout>
