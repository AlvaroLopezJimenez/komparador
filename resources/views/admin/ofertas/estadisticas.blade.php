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
            <a href="{{ route('admin.ofertas.index', $oferta->producto_id) }}">
                <h2 class="font-semibold text-xl text-white leading-tight">Ofertas de {{ $oferta->producto->nombre }} -></h2>
            </a>
        </div>

    </x-slot>

    <div class="py-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">


        <div class="mb-4 flex flex-wrap items-center gap-4">
            <div class="flex items-center gap-2">
                <label for="dias" class="mr-2 font-medium text-white">Rango:</label>
                <select id="dias" class="border px-3 py-2 rounded w-40 sm:w-48">
                    <option value="30">30 días</option>
                    <option value="90" selected>90 días</option>
                    <option value="180">180 días</option>
                    <option value="365">365 días</option>
                </select>
            </div>
            
            <button onclick="abrirHistorial()" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded">
                ✏️ Editar historial
            </button>
            
            <div id="info-rangos" class="flex items-center gap-4 text-white text-sm">
                <span id="rango-precio-unidad">
                    <strong>Rango precio_unidad (5 ofertas):</strong> 
                    <span id="rango-precio-unidad-valor" class="font-bold text-lg ml-1 border-b-2 border-green-500 inline-block">Cargando...</span>
                </span>
                <span id="rango-historico">
                    <strong>Histórico producto:</strong> 
                    <span id="rango-historico-valor" class="font-bold text-lg ml-1 border-b-2 border-green-500 inline-block">Cargando...</span>
                </span>
            </div>
            
            <button id="btn-ocultar-precio-elevado" onclick="ocultarPrecioElevado()" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded">
                Mostrar-no
            </button>

            <div id="toast" class="fixed bottom-6 right-6 bg-green-600 text-white px-4 py-2 rounded shadow-lg hidden z-50">
                ✅ Precios actualizados correctamente.
            </div>

            <div id="modal-historial" class="hidden fixed inset-0 z-50 bg-black bg-opacity-50 flex items-center justify-center">
                <div class="bg-white dark:bg-gray-800 w-full max-w-4xl h-[600px] overflow-y-auto p-6 rounded-lg shadow-lg space-y-4">
                    <div class="flex justify-between items-center">
                        <h2 class="text-xl font-semibold text-gray-800 dark:text-white">
                            Editar precios de {{ $oferta->producto->nombre }} - {{ $oferta->tienda->nombre }}
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
        </div>

        <div class="bg-white p-4 rounded-lg shadow">
            <canvas id="graficoPrecio" class="w-full h-48 sm:h-64 lg:h-72"></canvas>
            <div id="mensaje" class="text-center text-gray-500 mt-4 hidden">No hay histórico disponible.</div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const ctx = document.getElementById('graficoPrecio').getContext('2d');
        const mensaje = document.getElementById('mensaje');
        let grafico;
        const esUnidadMilesima = {{ $oferta->producto->unidadDeMedida === 'unidadMilesima' ? 'true' : 'false' }};

        function fetchDatos(dias) {
            fetch(`{{ route('admin.ofertas.estadisticas.datos', $oferta) }}?dias=${dias}`)
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
                    
                    // Añadir banda verde si hay datos (primero el mínimo, luego el máximo con fill: '-1')
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
                                // Línea de precio de la oferta (azul/cyan)
                                {
                                    label: 'Precio oferta (€) - Color azul',
                                    data: data.valores,
                                    borderColor: 'rgb(75, 192, 192)',
                                    borderWidth: 2,
                                    backgroundColor: 'rgba(75, 192, 192, 0.1)',
                                    tension: 0.3,
                                    fill: false,
                                    spanGaps: true,
                                    order: 2
                                },
                                // Línea de precio histórico del producto (rojo/rosa)
                                {
                                    label: 'Precio histórico producto (€) - Color rojo',
                                    data: data.valores_producto || [],
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
                                        color: '#fff',
                                        usePointStyle: true,
                                        padding: 15,
                                        font: {
                                            size: 14,
                                            weight: 'bold'
                                        },
                                        filter: function(legendItem) {
                                            // Ocultar el segundo dataset de la banda verde (el que tiene label vacío)
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
                                                label += context.parsed.y.toFixed(2) + '€';
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

        document.getElementById('dias').addEventListener('change', e => {
            fetchDatos(e.target.value);
            fetchInfoRangos(e.target.value);
        });

        // Función para formatear precio según el tipo de unidad
        function formatearPrecio(precio, esUnidadMilesima) {
            if (precio === null || precio === undefined) return '-';
            const decimales = esUnidadMilesima ? 3 : 2;
            return precio.toFixed(decimales) + '€';
        }

        // Función para cargar información de rangos
        function fetchInfoRangos(dias) {
            const url = `{{ route('admin.ofertas.estadisticas.info', $oferta) }}?dias=${dias}`;
            console.log('Cargando información de rangos desde:', url);
            
            fetch(url, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(res => {
                    console.log('Respuesta recibida:', res.status, res.statusText);
                    if (!res.ok) {
                        throw new Error(`HTTP error! status: ${res.status}`);
                    }
                    return res.json();
                })
                .then(data => {
                    console.log('Datos recibidos:', data);
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
                    console.error('Detalles del error:', err.message);
                    document.getElementById('rango-precio-unidad-valor').textContent = 'Error: ' + err.message;
                    document.getElementById('rango-historico-valor').textContent = 'Error: ' + err.message;
                });
        }

        // Función para ocultar oferta por precio elevado
        function ocultarPrecioElevado() {
            alert('Se ocultará esta oferta (mostrar=no) y se añadirá la anotación "PRECIO MUY ELEVADO CONSTANTE EN EL TIEMPO"');
            
            fetch(`{{ route('admin.ofertas.ocultar.precio.elevado', $oferta) }}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    // Opcional: recargar la página o actualizar el estado
                    window.location.reload();
                } else {
                    alert('Error al ocultar la oferta');
                }
            })
            .catch(err => {
                console.error('Error al ocultar oferta:', err);
                alert('Error al ocultar la oferta');
            });
        }

        // Inicial
        fetchDatos(90);
        fetchInfoRangos(90);
    </script>

    <!-- SCRIPT PARA EL CALENDARIO DE MODIFICAR PRECIOS DEL HISTORIAL -->
    <script>
        let mesActual = new Date().getMonth();
        let anioActual = new Date().getFullYear();
        let preciosCargados = {};
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

            fetch(`{{ route('admin.ofertas.historial.mes', $oferta) }}?mes=${mesActual + 1}&anio=${anioActual}`)
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
            const primerDia = new Date(anioActual, mesActual, 1).getDay();

            for (let i = 0; i < primerDia; i++) {
                tabla.innerHTML += '<div></div>';
            }

            for (let dia = 1; dia <= diasEnMes; dia++) {
                const fecha = `${anioActual}-${String(mesActual + 1).padStart(2, '0')}-${String(dia).padStart(2, '0')}`;
                const precio = cambios[fecha] !== undefined ?
                    String(cambios[fecha]) :
                    (preciosCargados.hasOwnProperty(fecha) ? String(preciosCargados[fecha]) : '');

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
            fetch(`{{ route('admin.ofertas.historial.guardar', $oferta) }}`, {
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
                fetchDatos(document.getElementById('dias').value);
                mostrarToast();
            });
        }

        function mostrarToast() {
            const toast = document.getElementById('toast');
            toast.classList.remove('hidden');
            setTimeout(() => toast.classList.add('hidden'), 3000);
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