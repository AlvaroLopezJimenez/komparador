<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.dashboard') }}">
                <h2 class="font-semibold text-xl text-white leading-tight">Inicio -></h2>
            </a>
            <h2 class="font-semibold text-xl text-white leading-tight">Actividad de Usuarios</h2>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto py-10 px-4 space-y-8 bg-gray-50 dark:bg-gray-900 rounded-lg shadow-md">
        
        <!-- Filtros -->
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between p-6 cursor-pointer" onclick="toggleFiltros()">
                <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-200">🔍 Filtros de Búsqueda</h3>
                <div class="flex items-center gap-2">
                    <svg id="iconoFiltros" class="w-5 h-5 text-gray-500 dark:text-gray-400 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </div>
            </div>
            
            <div id="contenidoFiltros" class="hidden px-6 pb-6">
                <form method="GET" action="{{ route('admin.users.index') }}" id="formFiltros" class="space-y-4">
                    <input type="hidden" name="filtro_rapido" id="filtro_rapido" value="{{ request('filtro_rapido', $filtroRapido ?? '30dias') }}">
                    <input type="hidden" name="periodo" value="{{ request('periodo', $periodo ?? 'dia') }}">
                    
                    <!-- Selección de Usuario -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Usuario:</label>
                        <select name="usuario_id" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="todos" {{ $usuarioId === null ? 'selected' : '' }}>Todos los usuarios</option>
                            @foreach($usuarios as $usuario)
                                <option value="{{ $usuario->id }}" {{ $usuarioId == $usuario->id ? 'selected' : '' }}>
                                    {{ $usuario->name }} ({{ $usuario->email }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    
                    <!-- Filtros rápidos de fecha -->
                    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">📅 Filtros Rápidos:</label>
                        <div class="flex flex-wrap gap-2">
                            <button type="button" onclick="aplicarFiltroRapido('hoy')" 
                                    class="px-3 py-1.5 text-xs font-medium rounded-md transition-colors {{ request('filtro_rapido', $filtroRapido ?? '30dias') === 'hoy' ? 'bg-blue-600 text-white' : 'bg-white dark:bg-gray-600 text-gray-700 dark:text-gray-300 hover:bg-blue-50 dark:hover:bg-blue-900/20 border border-gray-300 dark:border-gray-500' }}">
                                🟢 Hoy
                            </button>
                            <button type="button" onclick="aplicarFiltroRapido('ayer')" 
                                    class="px-3 py-1.5 text-xs font-medium rounded-md transition-colors {{ request('filtro_rapido', $filtroRapido ?? '30dias') === 'ayer' ? 'bg-blue-600 text-white' : 'bg-white dark:bg-gray-600 text-gray-700 dark:text-gray-300 hover:bg-blue-50 dark:hover:bg-blue-900/20 border border-gray-300 dark:border-gray-500' }}">
                                📅 Ayer
                            </button>
                            <button type="button" onclick="aplicarFiltroRapido('7dias')" 
                                    class="px-3 py-1.5 text-xs font-medium rounded-md transition-colors {{ request('filtro_rapido', $filtroRapido ?? '30dias') === '7dias' ? 'bg-blue-600 text-white' : 'bg-white dark:bg-gray-600 text-gray-700 dark:text-gray-300 hover:bg-blue-50 dark:hover:bg-blue-900/20 border border-gray-300 dark:border-gray-500' }}">
                                📊 Últimos 7 días
                            </button>
                            <button type="button" onclick="aplicarFiltroRapido('30dias')" 
                                    class="px-3 py-1.5 text-xs font-medium rounded-md transition-colors {{ request('filtro_rapido', $filtroRapido ?? '30dias') === '30dias' ? 'bg-blue-600 text-white' : 'bg-white dark:bg-gray-600 text-gray-700 dark:text-gray-300 hover:bg-blue-50 dark:hover:bg-blue-900/20 border border-gray-300 dark:border-gray-500' }}">
                                📈 Últimos 30 días
                            </button>
                            <button type="button" onclick="aplicarFiltroRapido('90dias')" 
                                    class="px-3 py-1.5 text-xs font-medium rounded-md transition-colors {{ request('filtro_rapido', $filtroRapido ?? '30dias') === '90dias' ? 'bg-blue-600 text-white' : 'bg-white dark:bg-gray-600 text-gray-700 dark:text-gray-300 hover:bg-blue-50 dark:hover:bg-blue-900/20 border border-gray-300 dark:border-gray-500' }}">
                                📊 Últimos 90 días
                            </button>
                            <button type="button" onclick="aplicarFiltroRapido('180dias')" 
                                    class="px-3 py-1.5 text-xs font-medium rounded-md transition-colors {{ request('filtro_rapido', $filtroRapido ?? '30dias') === '180dias' ? 'bg-blue-600 text-white' : 'bg-white dark:bg-gray-600 text-gray-700 dark:text-gray-300 hover:bg-blue-50 dark:hover:bg-blue-900/20 border border-gray-300 dark:border-gray-500' }}">
                                📈 Últimos 180 días
                            </button>
                            <button type="button" onclick="aplicarFiltroRapido('1año')" 
                                    class="px-3 py-1.5 text-xs font-medium rounded-md transition-colors {{ request('filtro_rapido', $filtroRapido ?? '30dias') === '1año' ? 'bg-blue-600 text-white' : 'bg-white dark:bg-gray-600 text-gray-700 dark:text-gray-300 hover:bg-blue-50 dark:hover:bg-blue-900/20 border border-gray-300 dark:border-gray-500' }}">
                                📅 Último año
                            </button>
                            <button type="button" onclick="aplicarFiltroRapido('siempre')" 
                                    class="px-3 py-1.5 text-xs font-medium rounded-md transition-colors {{ request('filtro_rapido', $filtroRapido ?? '30dias') === 'siempre' ? 'bg-blue-600 text-white' : 'bg-white dark:bg-gray-600 text-gray-700 dark:text-gray-300 hover:bg-blue-50 dark:hover:bg-blue-900/20 border border-gray-300 dark:border-gray-500' }}">
                                🌍 Desde siempre
                            </button>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
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
                    </div>
                    
                    <div class="flex justify-between items-center">
                        <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors">
                            🔍 Buscar
                        </button>
                        
                        <a href="{{ route('admin.users.index') }}" class="px-6 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600 focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-colors">
                            🔄 Limpiar
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Estadísticas -->
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6 border border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-200 mb-4">📊 Estadísticas</h3>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg">
                    <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ number_format($estadisticas['productos_creados']) }}</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Productos Creados</div>
                </div>
                <div class="bg-green-50 dark:bg-green-900/20 p-4 rounded-lg">
                    <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ number_format($estadisticas['productos_modificados']) }}</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Productos Modificados</div>
                </div>
                <div class="bg-purple-50 dark:bg-purple-900/20 p-4 rounded-lg">
                    <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ number_format($estadisticas['ofertas_creadas']) }}</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Ofertas Creadas</div>
                </div>
                <div class="bg-orange-50 dark:bg-orange-900/20 p-4 rounded-lg">
                    <div class="text-2xl font-bold text-orange-600 dark:text-orange-400">{{ number_format($estadisticas['ofertas_modificadas']) }}</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Ofertas Modificadas</div>
                </div>
            </div>
        </div>

        <!-- Gráfica -->
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-200">📈 Gráfica de Actividad</h3>
                <div class="flex items-center gap-2">
                    <span class="text-sm text-gray-600 dark:text-gray-400 mr-2">Agrupar por:</span>
                    <form method="GET" action="{{ route('admin.users.index') }}" id="periodoForm" class="flex gap-2">
                        <input type="hidden" name="usuario_id" value="{{ $usuarioId ?? 'todos' }}">
                        <input type="hidden" name="filtro_rapido" value="{{ $filtroRapido }}">
                        <input type="hidden" name="fecha_desde" value="{{ $fechaDesde }}">
                        <input type="hidden" name="fecha_hasta" value="{{ $fechaHasta }}">
                        <button type="submit" name="periodo" value="dia" 
                                class="px-3 py-1.5 text-xs font-medium rounded-md transition-colors {{ ($periodo ?? 'dia') === 'dia' ? 'bg-blue-600 text-white' : 'bg-white dark:bg-gray-600 text-gray-700 dark:text-gray-300 hover:bg-blue-50 dark:hover:bg-blue-900/20 border border-gray-300 dark:border-gray-500' }}">
                            Día
                        </button>
                        <button type="submit" name="periodo" value="mes" 
                                class="px-3 py-1.5 text-xs font-medium rounded-md transition-colors {{ ($periodo ?? 'dia') === 'mes' ? 'bg-blue-600 text-white' : 'bg-white dark:bg-gray-600 text-gray-700 dark:text-gray-300 hover:bg-blue-50 dark:hover:bg-blue-900/20 border border-gray-300 dark:border-gray-500' }}">
                            Mes
                        </button>
                        <button type="submit" name="periodo" value="año" 
                                class="px-3 py-1.5 text-xs font-medium rounded-md transition-colors {{ ($periodo ?? 'dia') === 'año' ? 'bg-blue-600 text-white' : 'bg-white dark:bg-gray-600 text-gray-700 dark:text-gray-300 hover:bg-blue-50 dark:hover:bg-blue-900/20 border border-gray-300 dark:border-gray-500' }}">
                            Año
                        </button>
                    </form>
                </div>
            </div>
            
            @if(count($datosGrafica['labels']) > 0)
                <div style="height: 400px;">
                    <canvas id="graficaActividad"></canvas>
                </div>
            @else
                <div class="text-center py-12">
                    <div class="text-gray-500 dark:text-gray-400 text-lg">📊 No hay datos para mostrar</div>
                    <p class="text-sm text-gray-400 dark:text-gray-500 mt-2">
                        No se encontraron actividades para los filtros seleccionados.
                    </p>
                </div>
            @endif
        </div>

        <!-- Listado de Últimos Movimientos -->
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6 border border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-200 mb-4">📋 Últimos Movimientos</h3>
            
            @if($movimientos->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Fecha</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Usuario</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Acción</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Detalle</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($movimientos as $movimiento)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                        <div class="flex flex-col">
                                            <span class="font-medium">{{ $movimiento->created_at->format('d/m/Y') }}</span>
                                            <span class="text-xs text-gray-500 dark:text-gray-400">{{ $movimiento->created_at->format('H:i:s') }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                        @if($movimiento->user)
                                            <div class="flex items-center">
                                                <svg class="w-4 h-4 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                                </svg>
                                                <span class="font-medium">{{ $movimiento->user->name }}</span>
                                            </div>
                                            <span class="text-xs text-gray-500 dark:text-gray-400 ml-6">{{ $movimiento->user->email }}</span>
                                        @else
                                            <span class="text-gray-400 dark:text-gray-500">-</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @php
                                            $acciones = [
                                                'producto_creado' => ['texto' => 'Producto Creado', 'color' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200'],
                                                'producto_modificado' => ['texto' => 'Producto Modificado', 'color' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'],
                                                'oferta_creada' => ['texto' => 'Oferta Creada', 'color' => 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200'],
                                                'oferta_modificada' => ['texto' => 'Oferta Modificada', 'color' => 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200'],
                                                'login' => ['texto' => 'Login', 'color' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200'],
                                            ];
                                            $accion = $acciones[$movimiento->action_type] ?? ['texto' => $movimiento->action_type, 'color' => 'bg-gray-100 text-gray-800'];
                                        @endphp
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $accion['color'] }}">
                                            {{ $accion['texto'] }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">
                                        @if($movimiento->action_type === 'login')
                                            <div class="flex items-center">
                                                <svg class="w-4 h-4 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                                </svg>
                                                <span class="text-gray-600 dark:text-gray-400">Inicio de sesión</span>
                                            </div>
                                        @elseif($movimiento->producto)
                                            <div class="flex items-center">
                                                <svg class="w-4 h-4 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                                </svg>
                                                <a href="{{ route('admin.productos.edit', $movimiento->producto) }}" class="text-blue-600 dark:text-blue-400 hover:underline">
                                                    {{ $movimiento->producto->nombre }}
                                                </a>
                                            </div>
                                        @elseif($movimiento->oferta)
                                            <div class="flex flex-col">
                                                <div class="flex items-center">
                                                    <svg class="w-4 h-4 mr-2 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                                    </svg>
                                                    <a href="{{ route('admin.ofertas.edit', $movimiento->oferta) }}" class="text-purple-600 dark:text-purple-400 hover:underline">
                                                        {{ $movimiento->oferta->producto->nombre ?? 'Sin producto' }}
                                                    </a>
                                                </div>
                                                @if($movimiento->oferta->tienda)
                                                    <span class="text-xs text-gray-500 dark:text-gray-400 ml-6">
                                                        {{ $movimiento->oferta->tienda->nombre }}
                                                    </span>
                                                @endif
                                            </div>
                                        @else
                                            <span class="text-gray-400 dark:text-gray-500">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                <!-- Paginación -->
                <div class="mt-6">
                    {{ $movimientos->links() }}
                </div>
            @else
                <div class="text-center py-12">
                    <div class="text-gray-500 dark:text-gray-400 text-lg">📝 No hay movimientos para mostrar</div>
                    <p class="text-sm text-gray-400 dark:text-gray-500 mt-2">
                        No se encontraron movimientos para los filtros seleccionados.
                    </p>
                </div>
            @endif
        </div>
    </div>

    <!-- Scripts para los gráficos -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <script>
        function toggleFiltros() {
            const contenido = document.getElementById('contenidoFiltros');
            const icono = document.getElementById('iconoFiltros');
            
            if (contenido.classList.contains('hidden')) {
                contenido.classList.remove('hidden');
                icono.style.transform = 'rotate(180deg)';
            } else {
                contenido.classList.add('hidden');
                icono.style.transform = 'rotate(0deg)';
            }
        }
        
        function aplicarFiltroRapido(filtro) {
            document.getElementById('filtro_rapido').value = filtro;
            document.getElementById('formFiltros').submit();
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            // Verificar si hay filtros activos y mantener el desplegable abierto
            const urlParams = new URLSearchParams(window.location.search);
            const tieneFiltros = urlParams.has('fecha_desde') || urlParams.has('fecha_hasta') || 
                                urlParams.has('usuario_id') || urlParams.has('filtro_rapido');
            
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
                    document.getElementById('filtro_rapido').value = '';
                });
            }
            
            if (fechaHastaInput) {
                fechaHastaInput.addEventListener('change', function() {
                    document.getElementById('filtro_rapido').value = '';
                });
            }
            
            // Gráfica de actividad
            @if(count($datosGrafica['labels']) > 0)
                const ctx = document.getElementById('graficaActividad').getContext('2d');
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: @json($datosGrafica['labels']),
                        datasets: [
                            {
                                label: 'Productos Creados',
                                data: @json($datosGrafica['productos_creados']),
                                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                                borderColor: 'rgba(59, 130, 246, 1)',
                                borderWidth: 2,
                                fill: true,
                                tension: 0.4
                            },
                            {
                                label: 'Ofertas Creadas',
                                data: @json($datosGrafica['ofertas_creadas']),
                                backgroundColor: 'rgba(34, 197, 94, 0.1)',
                                borderColor: 'rgba(34, 197, 94, 1)',
                                borderWidth: 2,
                                fill: true,
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
                                display: true,
                                position: 'top'
                            }
                        }
                    }
                });
            @endif
        });
    </script>
</x-app-layout>

