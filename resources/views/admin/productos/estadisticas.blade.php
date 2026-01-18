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
                fetchDatos(document.getElementById('dias').value); // actualizar gráfica
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


    <!-- CREO QUE DE AQUÍ EN ADELANTE ES SOLO LA GRAFICA DEL HISTORIAL DE PRECIOS -->
    <div class="py-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">


        <div class="mb-4">
            <label for="dias" class="mr-2 font-medium text-white">Rango:</label>
            <select id="dias" class="border px-3 py-2 rounded w-40 sm:w-48">
                <option value="30">30 días</option>
                <option value="90" selected>90 días</option>
                <option value="180">180 días</option>
                <option value="365">365 días</option>
            </select>
            <button onclick="abrirHistorial()" class="mb-4 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded">
                ✏️ Editar historial
            </button>
        </div>

        <div class="bg-white p-2 rounded-lg shadow">
            <canvas id="graficoPrecio" class="w-full h-48 sm:h-64 lg:h-72"></canvas>
            <div id="mensaje" class="text-center text-gray-500 mt-4 hidden">No hay histórico disponible.</div>
        </div>
        <a href="{{ route('admin.productos.estadisticas.avanzadas', $producto) }}"
            class="mb-4 px-4 py-2 bg-pink-600 hover:bg-pink-700 text-white rounded inline-block">
            📊 Ver análisis de clics
        </a>
        <div class="bg-white p-2 rounded-lg shadow">
            <canvas id="graficoClicks" class="w-full h-48 sm:h-64 lg:h-72"></canvas>
            <div id="mensajeClicks" class="text-center text-gray-500 mt-4 hidden">No hay clics registrados.</div>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const ctx = document.getElementById('graficoPrecio').getContext('2d');
        const mensaje = document.getElementById('mensaje');
        let grafico;

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

                    grafico = new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: data.labels,
                            datasets: [{
                                label: 'Precio mínimo (€)',
                                data: data.valores,
                                borderColor: 'rgb(75, 192, 192)',
                                backgroundColor: 'rgba(75, 192, 192, 0.1)',
                                tension: 0.3,
                                fill: true
                            }]
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
                                    labels: {
                                        color: '#000' // o '#fff' si fondo oscuro
                                    }
                                }
                            }
                        }

                    });
                });
        }

        document.getElementById('dias').addEventListener('change', e => {
            fetchDatos(e.target.value);
        });

        // Inicial
        fetchDatos(90);
    </script>

    {{-- SCRIPT PARA EL GRAFICO DE CLICKS --}}
    <script>
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

        // Actualizar ambos al cambiar rango
        document.getElementById('dias').addEventListener('change', e => {
            const dias = e.target.value;
            fetchDatos(dias);
            fetchClicks(dias);
        });

        // Inicial
        fetchClicks(90);
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