<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.dashboard') }}">
                <h2 class="font-semibold text-xl text-white leading-tight">
                    Inicio ->
                </h2>
            </a>
            <a href="{{ route('admin.productos.index') }}">
                <h2 class="font-semibold text-xl text-white leading-tight">Productos -></h2>
            </a>
            <h2 class="font-semibold text-xl text-white leading-tight">Estadísticas de {{ $producto->nombre }}</h2>
        </div>
    </x-slot>

    <!-- MOSTRAR CALENDARIO PARA MODIFICAR HISTORIAL -->
    <div id="toast" class="fixed bottom-6 right-6 bg-green-600 text-white px-4 py-2 rounded shadow-lg hidden z-50">
        ✅ Precios actualizados correctamente.
    </div>
    <div id="modal-historial" class="hidden fixed inset-0 z-50 bg-black bg-opacity-50 flex items-center justify-center">
        <div class="bg-white dark:bg-gray-800 w-full max-w-4xl h-[600px] overflow-y-auto p-6 rounded-lg shadow-lg space-y-4">
            <div class="flex justify-between items-center">
                <h2 class="text-xl font-semibold text-gray-800 dark:text-white">
                    Editar precios de {{ $producto->nombre }}
                </h2>
                <button onclick="cerrarHistorial()" class="text-gray-500 hover:text-gray-700">✖</button>
            </div>

            <div class="flex items-center justify-between">
                <button onclick="cambiarMes(-1)" class="px-2 py-1 bg-gray-300 rounded">←</button>
                <h3 id="mesActual" class="text-lg font-bold text-gray-700 dark:text-white text-center"></h3>
                <button onclick="cambiarMes(1)" class="px-2 py-1 bg-gray-300 rounded">→</button>
            </div>

            <div id="tablaDias" class="grid grid-cols-7 gap-2 text-center text-sm text-gray-800 dark:text-white">
                <!-- aquí se cargan los días del mes -->
            </div>

            <div class="flex justify-end gap-2">
                <button onclick="cerrarHistorial()" class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600">
                    ❌ Cerrar
                </button>
                <button onclick="guardarCambios()" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
                    💾 Guardar cambios
                </button>
            </div>
        </div>
    </div>
    <script>
        let mesActual = new Date().getMonth();
        let anioActual = new Date().getFullYear();
        let preciosCargados = {}; // clave: 'YYYY-MM-DD', valor: precio
        let cambios = {};

        function abrirHistorial() {
            document.getElementById('modal-historial').classList.remove('hidden');
            cargarMes();
        }

        function cerrarHistorial() {
            document.getElementById('modal-historial').classList.add('hidden');
            cambios = {};
        }

        function cambiarMes(delta) {
            mesActual += delta;
            if (mesActual < 0) {
                mesActual = 11;
                anioActual--;
            } else if (mesActual > 11) {
                mesActual = 0;
                anioActual++;
            }
            cargarMes();
        }

        function cargarMes() {
            const mesNombre = new Date(anioActual, mesActual).toLocaleString('default', {
                month: 'long'
            });
            document.getElementById('mesActual').textContent = `${mesNombre.toUpperCase()} ${anioActual}`;

            fetch(`{{ route('admin.productos.historial.mes', $producto) }}?mes=${mesActual + 1}&anio=${anioActual}`)
                .then(res => res.json())
                .then(data => {
                    preciosCargados = data;
                    renderCalendario();
                });
        }

        function renderCalendario() {
            const tabla = document.getElementById('tablaDias');
            tabla.innerHTML = '';
            const diasEnMes = new Date(anioActual, mesActual + 1, 0).getDate();
            const primerDia = new Date(anioActual, mesActual, 1).getDay(); // 0=domingo

            for (let i = 0; i < primerDia; i++) {
                tabla.innerHTML += '<div></div>';
            }

            for (let dia = 1; dia <= diasEnMes; dia++) {
                const fecha = `${anioActual}-${String(mesActual + 1).padStart(2, '0')}-${String(dia).padStart(2, '0')}`;
                const precio = cambios[fecha] !== undefined ?
                    String(cambios[fecha]) :
                    (preciosCargados.hasOwnProperty(fecha) ? String(preciosCargados[fecha]) : '');

                console.log(`Día ${dia}: ${fecha} => Precio:`, precio);

                tabla.innerHTML += `
        <div class="border p-1">
            <div class="font-semibold mb-1">${dia}</div>
            <input type="number" step="0.01" value="${precio}"
                onchange="cambios['${fecha}'] = this.value"
                class="w-full text-black bg-white text-center px-1 py-0.5 rounded border border-gray-300 text-sm" />
        </div>
    `;
            }
        }

        function guardarCambios() {
            fetch(`{{ route('admin.productos.historial.guardar', $producto) }}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    cambios
                })
            }).then(() => {
                cerrarHistorial();
                const diasActual = {{ $dias ?? 90 }};
                fetchDatos(diasActual); // actualizar gráfica
                fetchInfoRangos(diasActual); // actualizar rangos
                mostrarToast();
            });
        }

        function mostrarToast() {
            const toast = document.getElementById('toast');
            toast.classList.remove('hidden');
            setTimeout(() => {
                toast.classList.add('hidden');
            }, 3000);
        }
    </script>


    <!-- SECCIÓN 1: GRÁFICOS DE PRECIOS E HISTORIAL -->
    <div class="py-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Filtros de rango de tiempo (desplegable) -->
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl border border-gray-200 dark:border-gray-700 mb-6">
            <div class="flex items-center justify-between p-6 cursor-pointer" onclick="toggleFiltrosTiempo()">
                <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-200">📅 Seleccionar Rango de Tiempo</h3>
                <div class="flex items-center gap-2">
                    <span class="text-sm text-gray-500 dark:text-gray-400" id="rango-actual">
                        @if(isset($filtroRapido))
                            @if($filtroRapido === 'hoy') 🟢 Hoy
                            @elseif($filtroRapido === 'ayer') 📅 Ayer
                            @elseif($filtroRapido === '7dias') 📊 Últimos 7 días
                            @elseif($filtroRapido === '30dias') 📈 Últimos 30 días
                            @elseif($filtroRapido === '90dias') 📊 Últimos 90 días
                            @elseif($filtroRapido === '180dias') 📈 Últimos 180 días
                            @elseif($filtroRapido === '1año') 📅 Último año
                            @elseif($filtroRapido === 'siempre') 🌍 Desde siempre
                            @else
                                @if($desde && $hasta)
                                    {{ \Carbon\Carbon::parse($desde)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($hasta)->format('d/m/Y') }}
                                @else
                                    📊 Últimos 90 días
                                @endif
                            @endif
                        @else
                            📊 Últimos 90 días
                        @endif
                    </span>
                    <svg id="iconoFiltrosTiempo" class="w-5 h-5 text-gray-500 dark:text-gray-400 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </div>
            </div>
            
            <div id="contenidoFiltrosTiempo" class="hidden px-6 pb-6">
                <form method="GET" action="{{ route('admin.productos.estadisticas', $producto) }}" id="formFiltros">
                    <input type="hidden" name="filtro_rapido" id="filtro_rapido" value="{{ request('filtro_rapido', $filtroRapido ?? '90dias') }}">
                    <input type="hidden" name="campana" value="{{ $campana ?? '' }}">
                    
                    <!-- Filtros rápidos -->
                    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Filtros Rápidos:</label>
                        <div class="flex flex-wrap gap-2">
                            <button type="button" onclick="aplicarFiltroRapido('hoy')" 
                                    class="px-3 py-1.5 text-xs font-medium rounded-md transition-colors {{ (request('filtro_rapido', $filtroRapido ?? '90dias') === 'hoy') ? 'bg-blue-600 text-white' : 'bg-white dark:bg-gray-600 text-gray-700 dark:text-gray-300 hover:bg-blue-50 dark:hover:bg-blue-900/20 border border-gray-300 dark:border-gray-500' }}">
                                🟢 Hoy
                            </button>
                            <button type="button" onclick="aplicarFiltroRapido('ayer')" 
                                    class="px-3 py-1.5 text-xs font-medium rounded-md transition-colors {{ (request('filtro_rapido', $filtroRapido ?? '90dias') === 'ayer') ? 'bg-blue-600 text-white' : 'bg-white dark:bg-gray-600 text-gray-700 dark:text-gray-300 hover:bg-blue-50 dark:hover:bg-blue-900/20 border border-gray-300 dark:border-gray-500' }}">
                                📅 Ayer
                            </button>
                            <button type="button" onclick="aplicarFiltroRapido('7dias')" 
                                    class="px-3 py-1.5 text-xs font-medium rounded-md transition-colors {{ (request('filtro_rapido', $filtroRapido ?? '90dias') === '7dias') ? 'bg-blue-600 text-white' : 'bg-white dark:bg-gray-600 text-gray-700 dark:text-gray-300 hover:bg-blue-50 dark:hover:bg-blue-900/20 border border-gray-300 dark:border-gray-500' }}">
                                📊 Últimos 7 días
                            </button>
                            <button type="button" onclick="aplicarFiltroRapido('30dias')" 
                                    class="px-3 py-1.5 text-xs font-medium rounded-md transition-colors {{ (request('filtro_rapido', $filtroRapido ?? '90dias') === '30dias') ? 'bg-blue-600 text-white' : 'bg-white dark:bg-gray-600 text-gray-700 dark:text-gray-300 hover:bg-blue-50 dark:hover:bg-blue-900/20 border border-gray-300 dark:border-gray-500' }}">
                                📈 Últimos 30 días
                            </button>
                            <button type="button" onclick="aplicarFiltroRapido('90dias')" 
                                    class="px-3 py-1.5 text-xs font-medium rounded-md transition-colors {{ (request('filtro_rapido', $filtroRapido ?? '90dias') === '90dias') ? 'bg-blue-600 text-white' : 'bg-white dark:bg-gray-600 text-gray-700 dark:text-gray-300 hover:bg-blue-50 dark:hover:bg-blue-900/20 border border-gray-300 dark:border-gray-500' }}">
                                📊 Últimos 90 días
                            </button>
                            <button type="button" onclick="aplicarFiltroRapido('180dias')" 
                                    class="px-3 py-1.5 text-xs font-medium rounded-md transition-colors {{ (request('filtro_rapido', $filtroRapido ?? '90dias') === '180dias') ? 'bg-blue-600 text-white' : 'bg-white dark:bg-gray-600 text-gray-700 dark:text-gray-300 hover:bg-blue-50 dark:hover:bg-blue-900/20 border border-gray-300 dark:border-gray-500' }}">
                                📈 Últimos 180 días
                            </button>
                            <button type="button" onclick="aplicarFiltroRapido('1año')" 
                                    class="px-3 py-1.5 text-xs font-medium rounded-md transition-colors {{ (request('filtro_rapido', $filtroRapido ?? '90dias') === '1año') ? 'bg-blue-600 text-white' : 'bg-white dark:bg-gray-600 text-gray-700 dark:text-gray-300 hover:bg-blue-50 dark:hover:bg-blue-900/20 border border-gray-300 dark:border-gray-500' }}">
                                📅 Último año
                            </button>
                            <button type="button" onclick="aplicarFiltroRapido('siempre')" 
                                    class="px-3 py-1.5 text-xs font-medium rounded-md transition-colors {{ (request('filtro_rapido', $filtroRapido ?? '90dias') === 'siempre') ? 'bg-blue-600 text-white' : 'bg-white dark:bg-gray-600 text-gray-700 dark:text-gray-300 hover:bg-blue-50 dark:hover:bg-blue-900/20 border border-gray-300 dark:border-gray-500' }}">
                                🌍 Desde siempre
                            </button>
                        </div>
                    </div>
                    
                    <!-- Filtros manuales de fecha -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Fecha desde:</label>
                            <input type="date" name="desde" id="fecha_desde" value="{{ $desde ?? '' }}" 
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                   onchange="document.getElementById('filtro_rapido').value = ''; actualizarRangoActual()">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Fecha hasta:</label>
                            <input type="date" name="hasta" id="fecha_hasta" value="{{ $hasta ?? '' }}" 
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                   onchange="document.getElementById('filtro_rapido').value = ''; actualizarRangoActual()">
                        </div>
                    </div>
                    
                    <div class="flex justify-end">
                        <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors">
                            🔍 Aplicar Filtros
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- INFORMACIÓN DE RANGOS Y BOTONES --}}
        <div class="mb-4 flex flex-wrap items-center gap-4 bg-white dark:bg-gray-800 shadow-sm rounded-xl border border-gray-200 dark:border-gray-700 p-4">
            <div id="info-rangos" class="flex items-center gap-4 text-gray-900 dark:text-white text-sm flex-1">
                <span id="rango-precio-unidad">
                    <strong>Rango precio_unidad (5 ofertas):</strong> 
                    <span id="rango-precio-unidad-valor" class="font-bold text-lg ml-1 border-b-2 border-green-500 inline-block">Cargando...</span>
                </span>
                <span id="rango-historico">
                    <strong>Histórico producto:</strong> 
                    <span id="rango-historico-valor" class="font-bold text-lg ml-1 border-b-2 border-green-500 inline-block">Cargando...</span>
                </span>
            </div>
            
            <div class="flex items-center gap-2">
                <button type="button" onclick="abrirHistorial()" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded">
                    ✏️ Editar historial
                </button>
                <button id="btn-ocultar-precio-elevado" onclick="ocultarPrecioElevado()" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded">
                    Mostrar-no
                </button>
            </div>
        </div>

        <div class="bg-white p-4 rounded-lg shadow">
            <canvas id="graficoPrecio" class="w-full h-48 sm:h-64 lg:h-72"></canvas>
            <div id="mensaje" class="text-center text-gray-500 mt-4 hidden">No hay histórico disponible.</div>
        </div>
        
        {{-- FILTRO DE CAMPAÑA --}}
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl border border-gray-200 dark:border-gray-700 p-6 mt-4">
            <form method="GET" action="{{ route('admin.productos.estadisticas', $producto) }}" class="flex flex-wrap gap-4 items-end">
                <input type="hidden" name="filtro_rapido" value="{{ request('filtro_rapido', $filtroRapido ?? '90dias') }}">
                <input type="hidden" name="desde" value="{{ $desde ?? '' }}">
                <input type="hidden" name="hasta" value="{{ $hasta ?? '' }}">
                <div>
                    <label for="campana" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Campaña</label>
                    <select name="campana" id="campana" class="border rounded px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                        <option value="">Todas</option>
                        @if(isset($palabrasClave))
                            @foreach($palabrasClave as $palabra)
                            <option value="{{ $palabra->codigo }}" {{ (isset($campana) && $campana === $palabra->codigo) ? 'selected' : '' }}
                                style="{{ $palabra->activa === 'no' ? 'color:gray' : '' }}">
                                {{ $palabra->palabra }}
                            </option>
                            @endforeach
                        @endif
                    </select>
                </div>
                <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded">
                    🔍 Filtrar por campaña
                </button>
            </form>
        </div>
        
        {{-- RANGO DE PRECIO CLICADO Y ESPACIO RESERVADO --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-4">
            <div class="bg-white shadow p-6 rounded-lg">
                <h3 class="text-lg font-semibold mb-2">💶 Rango de precio clicado</h3>
                <div id="rango-precio" class="text-xl font-bold text-pink-600">Cargando...</div>
            </div>
            <div class="bg-gray-100 shadow-inner p-6 rounded-lg text-center text-gray-500">
                <span class="text-sm italic">🔒 Espacio reservado para futuras métricas</span>
            </div>
        </div>
        
        {{-- GRÁFICOS DE CLICKS EN LA MISMA FILA --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mt-4">
            <div class="bg-white p-2 rounded-lg shadow">
                <canvas id="graficoClicks" class="w-full h-48 sm:h-64 lg:h-72"></canvas>
                <div id="mensajeClicks" class="text-center text-gray-500 mt-4 hidden">No hay clics registrados.</div>
            </div>
            <div class="bg-white shadow p-6 rounded-lg">
                <h3 class="text-lg font-semibold mb-4">🔥 Horarios con más clics</h3>
                <canvas id="heatmap" class="w-full" style="height: 200px;"></canvas>
            </div>
        </div>
    </div>

    <!-- SECCIÓN 2: ANÁLISIS AVANZADO DE CLICKS -->
    <div class="max-w-7xl mx-auto py-10 px-4 space-y-8">

        {{-- LISTADO TIENDAS CON CLICS AGRUPADOS --}}
        <div class="bg-white dark:bg-gray-900 rounded-lg shadow p-6 mt-10">
            <div class="flex items-center gap-2 mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-purple-500" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.5-8a5.5 5.5 0 01-11 0 5.5 5.5 0 0111 0z" clip-rule="evenodd" />
                </svg>
                <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-200">Tiendas con clics en este producto</h2>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Tienda</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Clics</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Rango de precio</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-800">
                        @forelse(($agrupado ?? collect()) as $tiendaId => $info)
                        @php
                        $tienda = $info;
                        $esVisible = isset($visibilidad) ? ($visibilidad[$tiendaId] ?? true) : true;
                        @endphp
                        <tr class="{{ !$esVisible ? 'text-gray-500 dark:text-gray-500' : '' }}">
                            <td class="px-6 py-4 whitespace-nowrap flex items-center gap-2">
                                @if ($tienda->url_imagen)
                                <img src="{{ asset('images/' . $tienda->url_imagen) }}" alt="{{ $tienda->nombre }}" class="w-9 h-9 object-contain">
                                @endif
                                <span>{{ $tienda->nombre }}</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">{{ $info->total_clicks }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                {{ number_format($info->min, 2, ',', '.') }} € – {{ number_format($info->max, 2, ',', '.') }} €
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">No hay clics registrados.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        {{-- LISTADO PAGINADO DE CLICS --}}
        <div class="bg-white dark:bg-gray-900 rounded-lg shadow p-6 mt-10">
            <div class="flex items-center gap-2 mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-blue-500" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v14a1 1 0 001.447.894l12-7a1 1 0 000-1.788l-12-7A1 1 0 006 2z" clip-rule="evenodd" />
                </svg>
                <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-200">Clics registrados (últimos)</h2>
            </div>

            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Tienda</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Campaña</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Precio</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Hora</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Posición</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-800">
                    @forelse (($clicsPaginados ?? collect()) as $click)
                    <tr class="text-white font-bold">
                        <td class="px-6 py-4 whitespace-nowrap">{{ $click->oferta->tienda->nombre ?? '-' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">{{ $click->campaña ?? 'orgánico' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">{{ number_format($click->precio_unidad, 2, ',', '.') }} €</td>
                        <td class="px-6 py-4 whitespace-nowrap">{{ $click->created_at->format('d/m/Y H:i') }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">{{ $click->posicion }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">No hay clics registrados.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>

            @if(isset($clicsPaginados))
            <div class="mt-4">
                {{ $clicsPaginados->withQueryString()->links() }}
            </div>
            @endif
        </div>
    </div>

    {{-- Scripts --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Script para gráfico de precios
        const ctx = document.getElementById('graficoPrecio').getContext('2d');
        const mensaje = document.getElementById('mensaje');
        let grafico;
        const esUnidadMilesima = {{ $producto->unidadDeMedida === 'unidadMilesima' ? 'true' : 'false' }};

        function fetchDatos(dias) {
            fetch(`{{ route('admin.productos.estadisticas.datos', $producto) }}?dias=${dias}`)
                .then(res => res.json())
                .then(data => {
                    if (grafico) grafico.destroy();

                    if (data.labels.length === 0) {
                        mensaje.classList.remove('hidden');
                        return;
                    }

                    mensaje.classList.add('hidden');

                    // Preparar datos para la banda verde (rango de las 5 ofertas)
                    const rango5Ofertas = data.rango_5_ofertas || { min: null, max: null };
                    const bandaVerdeMin = [];
                    const bandaVerdeMax = [];
                    
                    if (rango5Ofertas.min !== null && rango5Ofertas.max !== null) {
                        for (let i = 0; i < data.labels.length; i++) {
                            bandaVerdeMin.push(rango5Ofertas.min);
                            bandaVerdeMax.push(rango5Ofertas.max);
                        }
                    }

                    const datasets = [];
                    
                    // Añadir banda verde si hay datos
                    if (rango5Ofertas.min !== null && rango5Ofertas.max !== null) {
                        datasets.push({
                            label: '',
                            data: bandaVerdeMin,
                            borderColor: 'transparent',
                            backgroundColor: 'transparent',
                            fill: false,
                            tension: 0,
                            pointRadius: 0,
                            order: 0
                        });
                        datasets.push({
                            label: 'Rango 5 ofertas más baratas',
                            data: bandaVerdeMax,
                            borderColor: 'transparent',
                            backgroundColor: 'rgba(34, 197, 94, 0.2)',
                            fill: '-1',
                            tension: 0,
                            pointRadius: 0,
                            order: 0
                        });
                    }

                    grafico = new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: data.labels,
                            datasets: [
                                ...datasets,
                                // Línea de precio histórico del producto (rojo/rosa)
                                {
                                    label: 'Precio histórico producto (€) - Color rojo',
                                    data: data.valores,
                                    borderColor: 'rgb(255, 99, 132)',
                                    borderWidth: 2,
                                    backgroundColor: 'rgba(255, 99, 132, 0.1)',
                                    tension: 0.3,
                                    fill: false,
                                    spanGaps: true,
                                    order: 1
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            layout: {
                                padding: {
                                    top: 20,
                                    bottom: 10
                                }
                            },
                            interaction: {
                                mode: 'index',
                                intersect: false
                            },
                            plugins: {
                                legend: {
                                    display: true,
                                    position: 'top',
                                    labels: {
                                        color: '#000',
                                        usePointStyle: true,
                                        padding: 15,
                                        font: {
                                            size: 14,
                                            weight: 'bold'
                                        },
                                        filter: function(legendItem) {
                                            return legendItem.text !== '';
                                        }
                                    }
                                },
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            let label = context.dataset.label || '';
                                            if (label) {
                                                label += ': ';
                                            }
                                            if (context.parsed.y !== null) {
                                                const decimales = esUnidadMilesima ? 3 : 2;
                                                label += context.parsed.y.toFixed(decimales) + '€';
                                            }
                                            return label;
                                        }
                                    }
                                }
                            }
                        }
                    });
                });
        }
        
        // Función para formatear precio según el tipo de unidad
        function formatearPrecio(precio, esUnidadMilesima) {
            if (precio === null || precio === undefined) return '-';
            const decimales = esUnidadMilesima ? 3 : 2;
            return precio.toFixed(decimales) + '€';
        }

        // Función para cargar información de rangos
        function fetchInfoRangos(dias) {
            fetch(`{{ route('admin.productos.estadisticas.info', $producto) }}?dias=${dias}`)
                .then(res => res.json())
                .then(data => {
                    // Mostrar rango de precio_unidad (5 ofertas más baratas)
                    const rangoPrecioUnidad = data.rango_precio_unidad;
                    if (rangoPrecioUnidad && rangoPrecioUnidad.min !== null && rangoPrecioUnidad.max !== null) {
                        const minFormateado = formatearPrecio(rangoPrecioUnidad.min, esUnidadMilesima);
                        const maxFormateado = formatearPrecio(rangoPrecioUnidad.max, esUnidadMilesima);
                        document.getElementById('rango-precio-unidad-valor').textContent = 
                            `${minFormateado} - ${maxFormateado}`;
                    } else {
                        document.getElementById('rango-precio-unidad-valor').textContent = 'No disponible';
                    }
                    
                    // Mostrar rango histórico del producto
                    const rangoHistorico = data.rango_historico;
                    if (rangoHistorico && rangoHistorico.min !== null && rangoHistorico.max !== null) {
                        const minFormateado = formatearPrecio(rangoHistorico.min, esUnidadMilesima);
                        const maxFormateado = formatearPrecio(rangoHistorico.max, esUnidadMilesima);
                        document.getElementById('rango-historico-valor').textContent = 
                            `${minFormateado} - ${maxFormateado}`;
                    } else {
                        document.getElementById('rango-historico-valor').textContent = 'No disponible';
                    }
                })
                .catch(err => {
                    console.error('Error al cargar información de rangos:', err);
                    document.getElementById('rango-precio-unidad-valor').textContent = 'Error';
                    document.getElementById('rango-historico-valor').textContent = 'Error';
                });
        }

        // Función para ocultar ofertas con precio elevado (para productos, ocultar todas las ofertas del producto)
        function ocultarPrecioElevado() {
            if (!confirm('¿Estás seguro de que quieres ocultar todas las ofertas de este producto (mostrar=no) y añadir la anotación "PRECIO MUY ELEVADO CONSTANTE EN EL TIEMPO"?\n\nEsta acción afectará a todas las ofertas del producto.')) {
                return;
            }
            
            const btn = document.getElementById('btn-ocultar-precio-elevado');
            const textoOriginal = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '⏳ Procesando...';
            
            fetch(`{{ route('admin.productos.ocultar.precio.elevado', $producto) }}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert('✅ ' + data.message);
                    window.location.reload();
                } else {
                    alert('❌ Error: ' + (data.message || 'Error al ocultar las ofertas'));
                    btn.disabled = false;
                    btn.innerHTML = textoOriginal;
                }
            })
            .catch(err => {
                console.error('Error al ocultar ofertas:', err);
                alert('❌ Error de conexión al ocultar las ofertas');
                btn.disabled = false;
                btn.innerHTML = textoOriginal;
            });
        }

        {{-- Script para gráfico de clicks básico --}}
        const ctxClicks = document.getElementById('graficoClicks').getContext('2d');
        const mensajeClicks = document.getElementById('mensajeClicks');
        let graficoClicks;

        function fetchClicks(dias) {
            fetch(`{{ route('admin.productos.estadisticas.clicks', $producto) }}?dias=${dias}`)
                .then(res => res.json())
                .then(data => {
                    if (graficoClicks) graficoClicks.destroy();

                    if (data.labels.length === 0 || data.valores.every(v => v === 0)) {
                        mensajeClicks.classList.remove('hidden');
                        return;
                    }

                    mensajeClicks.classList.add('hidden');

                    graficoClicks = new Chart(ctxClicks, {
                        type: 'bar',
                        data: {
                            labels: data.labels,
                            datasets: [{
                                label: 'Clicks',
                                data: data.valores,
                                backgroundColor: 'rgba(99, 102, 241, 0.4)',
                                borderColor: 'rgba(99, 102, 241, 1)',
                                borderWidth: 1
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    display: true
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        stepSize: 1
                                    }
                                }
                            }
                        }
                    });
                });
        }

        // Función para mostrar/ocultar filtros de tiempo
        function toggleFiltrosTiempo() {
            const contenidoFiltros = document.getElementById('contenidoFiltrosTiempo');
            const iconoFiltros = document.getElementById('iconoFiltrosTiempo');
            
            if (contenidoFiltros.classList.contains('hidden')) {
                contenidoFiltros.classList.remove('hidden');
                iconoFiltros.style.transform = 'rotate(180deg)';
            } else {
                contenidoFiltros.classList.add('hidden');
                iconoFiltros.style.transform = 'rotate(0deg)';
            }
        }
        
        // Función para actualizar el texto del rango actual
        function actualizarRangoActual() {
            const fechaDesde = document.getElementById('fecha_desde').value;
            const fechaHasta = document.getElementById('fecha_hasta').value;
            const rangoActual = document.getElementById('rango-actual');
            
            if (fechaDesde && fechaHasta) {
                const desde = new Date(fechaDesde);
                const hasta = new Date(fechaHasta);
                const formatoDesde = desde.toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric' });
                const formatoHasta = hasta.toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric' });
                rangoActual.textContent = `${formatoDesde} - ${formatoHasta}`;
            }
        }
        
        // Función para aplicar filtros rápidos
        function aplicarFiltroRapido(tipo) {
            const hoy = new Date();
            let fechaDesde, fechaHasta;
            let textoRango = '';
            
            switch(tipo) {
                case 'hoy':
                    fechaDesde = fechaHasta = hoy.toISOString().split('T')[0];
                    textoRango = '🟢 Hoy';
                    break;
                case 'ayer':
                    const ayer = new Date(hoy);
                    ayer.setDate(hoy.getDate() - 1);
                    fechaDesde = fechaHasta = ayer.toISOString().split('T')[0];
                    textoRango = '📅 Ayer';
                    break;
                case '7dias':
                    const hace7Dias = new Date(hoy);
                    hace7Dias.setDate(hoy.getDate() - 7);
                    fechaDesde = hace7Dias.toISOString().split('T')[0];
                    fechaHasta = hoy.toISOString().split('T')[0];
                    textoRango = '📊 Últimos 7 días';
                    break;
                case '30dias':
                    const hace30Dias = new Date(hoy);
                    hace30Dias.setDate(hoy.getDate() - 30);
                    fechaDesde = hace30Dias.toISOString().split('T')[0];
                    fechaHasta = hoy.toISOString().split('T')[0];
                    textoRango = '📈 Últimos 30 días';
                    break;
                case '90dias':
                    const hace90Dias = new Date(hoy);
                    hace90Dias.setDate(hoy.getDate() - 90);
                    fechaDesde = hace90Dias.toISOString().split('T')[0];
                    fechaHasta = hoy.toISOString().split('T')[0];
                    textoRango = '📊 Últimos 90 días';
                    break;
                case '180dias':
                    const hace180Dias = new Date(hoy);
                    hace180Dias.setDate(hoy.getDate() - 180);
                    fechaDesde = hace180Dias.toISOString().split('T')[0];
                    fechaHasta = hoy.toISOString().split('T')[0];
                    textoRango = '📈 Últimos 180 días';
                    break;
                case '1año':
                    const hace1Año = new Date(hoy);
                    hace1Año.setFullYear(hoy.getFullYear() - 1);
                    fechaDesde = hace1Año.toISOString().split('T')[0];
                    fechaHasta = hoy.toISOString().split('T')[0];
                    textoRango = '📅 Último año';
                    break;
                case 'siempre':
                    fechaDesde = '';
                    fechaHasta = '';
                    textoRango = '🌍 Desde siempre';
                    break;
                default:
                    return;
            }
            
            // Actualizar campos del formulario
            document.getElementById('filtro_rapido').value = tipo;
            document.getElementById('fecha_desde').value = fechaDesde;
            document.getElementById('fecha_hasta').value = fechaHasta;
            
            // Actualizar texto del rango actual
            document.getElementById('rango-actual').textContent = textoRango;
            
            // Enviar formulario
            document.getElementById('formFiltros').submit();
        }

        // Inicializar gráficos con el rango actual
        const diasActual = {{ $dias ?? 90 }};
        fetchDatos(diasActual);
        fetchClicks(diasActual);
        fetchInfoRangos(diasActual);
    </script>

    {{-- Scripts para análisis avanzado de clicks --}}
    <div id="clicks-data"
        data-producto-id="{{ $producto->id }}"
        data-desde="{{ $desde ?? '' }}"
        data-hasta="{{ $hasta ?? '' }}"
        data-campana="{{ $campana ?? '' }}">
    </div>

    <script>
        const vars = document.getElementById('clicks-data').dataset;
        const productoId = vars.productoId;
        const desde = vars.desde;
        const hasta = vars.hasta;
        const campana = vars.campana;

        document.addEventListener('DOMContentLoaded', () => {
            cargarRangoPrecio();
            cargarGraficaClicsPorHora();
        });

        function cargarRangoPrecio() {
            if (!desde || !hasta) {
                document.getElementById('rango-precio').textContent = 'Selecciona un rango de fechas';
                return;
            }
            fetch(`/panel-privado/productos/${productoId}/clicks/rango-precio?desde=${desde}&hasta=${hasta}&campana=${campana}`)
                .then(res => res.json())
                .then(data => {
                    document.getElementById('rango-precio').textContent =
                        data.min !== null && data.max !== null ?
                        `${data.min} € - ${data.max} €` :
                        'Sin clics en este periodo';
                });
        }

        function cargarGraficaClicsPorHora() {
            if (!desde || !hasta) {
                return;
            }
            fetch(`/panel-privado/productos/${productoId}/clicks/por-hora?desde=${desde}&hasta=${hasta}&campana=${campana}`)
                .then(res => res.json())
                .then(data => {
                    const ctx = document.getElementById('heatmap').getContext('2d');
                    new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: data.labels,
                            datasets: [{
                                label: 'Clics por franja horaria',
                                data: data.values,
                                fill: true,
                                borderColor: 'rgba(0, 123, 255, 1)',
                                backgroundColor: 'rgba(0, 123, 255, 0.2)',
                                tension: 0.2,
                                pointRadius: 2
                            }]
                        },
                        options: {
                            scales: {
                                x: { title: { display: true, text: 'Hora del día' } },
                                y: { beginAtZero: true, title: { display: true, text: 'Número de clics' } }
                            }
                        }
                    });
                })
                .catch(err => console.error('Error al cargar gráfica por hora:', err));
        }
    </script>

    {{-- EVITAR TENER QUE PINCHAR DOS VECES EN LOS ENLACES PARA QUE FUNCIONEN --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Prevenir doble clic en enlaces
            const links = document.querySelectorAll('a[href]');
            links.forEach(link => {
                link.addEventListener('click', function(e) {
                    // Si el enlace ya está siendo procesado, prevenir el clic
                    if (this.dataset.processing === 'true') {
                        e.preventDefault();
                        return false;
                    }
                    
                    // Marcar como en procesamiento
                    this.dataset.processing = 'true';
                    
                    // Remover la marca después de un tiempo
                    setTimeout(() => {
                        this.dataset.processing = 'false';
                    }, 2000);
                });
            });
            
            // Prevenir doble clic en botones
            const buttons = document.querySelectorAll('button');
            buttons.forEach(button => {
                button.addEventListener('click', function(e) {
                    if (this.dataset.processing === 'true') {
                        e.preventDefault();
                        return false;
                    }
                    
                    this.dataset.processing = 'true';
                    
                    setTimeout(() => {
                        this.dataset.processing = 'false';
                    }, 2000);
                });
            });
        });
    </script>
</x-app-layout>
