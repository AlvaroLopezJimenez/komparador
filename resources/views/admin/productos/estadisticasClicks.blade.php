<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.dashboard') }}">
                <h2 class="font-semibold text-xl text-white leading-tight">Inicio -></h2>
            </a>
            <a href="{{ route('admin.productos.index') }}">
                <h2 class="font-semibold text-xl text-white leading-tight">Productos -></h2>
            </a>
            <a href="{{ route('admin.productos.estadisticas', $producto) }}">
                <h2 class="font-semibold text-xl text-white leading-tight">Estadísticas -></h2>
            </a>
            <h2 class="font-semibold text-xl text-white leading-tight">Avanzadas: {{ $producto->nombre }}</h2>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto py-10 px-4 space-y-8">

        {{-- SELECTOR DE RANGO DE FECHAS Y PALABRAS CLAVE --}}
        <form method="GET" class="flex flex-wrap gap-4 items-end">
            <div>
                <label for="desde" class="block text-sm font-medium text-gray-700">Desde</label>
                <input type="date" name="desde" id="desde" value="{{ $desde }}" class="border rounded px-3 py-2">
            </div>
            <div>
                <label for="hasta" class="block text-sm font-medium text-gray-700">Hasta</label>
                <input type="date" name="hasta" id="hasta" value="{{ $hasta }}" class="border rounded px-3 py-2">
            </div>
            <div>
                <label for="campana" class="block text-sm font-medium text-gray-700">Campaña</label>
                <select name="campana" id="campana" class="border rounded px-3 py-2">
                    <option value="">Todas</option>
                    @foreach($palabrasClave as $palabra)
                    <option value="{{ $palabra->codigo }}" {{ $campana === $palabra->codigo ? 'selected' : '' }}
                        style="{{ $palabra->activa === 'no' ? 'color:gray' : '' }}">
                        {{ $palabra->palabra }}
                    </option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded">
                🔍 Ver resultados
            </button>
        </form>

        {{-- BLOQUE IZQUIERDA: RANGO PRECIO  --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-white shadow p-6 rounded-lg">
                <h3 class="text-lg font-semibold mb-2">💶 Rango de precio clicado</h3>
                {{-- se completará con lógica en el siguiente paso --}}
                <div id="rango-precio" class="text-xl font-bold text-pink-600">Cargando...</div>
            </div>

            <div class="bg-gray-100 shadow-inner p-6 rounded-lg text-center text-gray-500">
                <span class="text-sm italic">🔒 Espacio reservado para futuras métricas</span>
            </div>
        </div>

        {{-- HEATMAP - MAPA DE CALOR --}}
        <div class="bg-white shadow p-6 rounded-lg mt-4">
            <h3 class="text-lg font-semibold mb-4">🔥 Horarios con más clics</h3>
            <canvas id="heatmap" class="w-full" style="height: 200px;"></canvas>
        </div>

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
                        @forelse($agrupado as $tiendaId => $info)
                        @php
                        $tienda = $info;
                        $esVisible = $visibilidad[$tiendaId] ?? true;
                        @endphp
                        <tr class="{{ !$esVisible ? 'text-gray-500 dark:text-gray-500' : '' }}">
                            <td class="px-6 py-4 whitespace-nowrap flex items-center gap-2">
                                @if ($tienda->url_imagen)
                                <img src="{{ asset('images/' . $tienda->url_imagen) }}" alt="{{ $tienda->nombre }}" class="w-9 h-9 object-contain">
                                @endif
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


        <div class="bg-white dark:bg-gray-900 rounded-lg shadow p-6 mt-10">
    {{-- LISTADO PAGINADO DE CLICS --}}
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
            @forelse ($clicsPaginados as $click)
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

    <div class="mt-4">
        {{ $clicsPaginados->withQueryString()->links() }}
    </div>
</div>


    <!-- </div> -->

    {{-- Scripts --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>

<div id="clicks-data"
    data-producto-id="{{ $producto->id }}"
    data-desde="{{ $desde }}"
    data-hasta="{{ $hasta }}"
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
{{-- EVITAR TENER QUE PINCHAR DOS VECES EN LOS ENLACES PARA QUE FUNCIONEN--}}
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