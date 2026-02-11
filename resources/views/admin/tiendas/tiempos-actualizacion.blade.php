<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Laravel') }} - Tiempos de Actualización</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2/dist/chartjs-plugin-datalabels.min.js"></script>
</head>
<body class="font-sans antialiased">
    <div class="min-h-screen bg-gray-100 dark:bg-gray-900">
        <!-- Header -->
        <header class="bg-white dark:bg-gray-800 shadow">
            <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <a href="{{ route('admin.dashboard') }}">
                            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Panel -></h2>
                        </a>
                        <h1 class="text-2xl font-light text-gray-800 dark:text-gray-200">Tiempos de Actualización</h1>
                    </div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">
                        {{ now()->format('d/m/Y H:i') }}
                    </div>
                </div>
            </div>
        </header>

        <!-- Page Content -->
        <main class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-900 dark:to-gray-800">
            <div class="max-w-7xl mx-auto px-4 py-8">
                
                <!-- Selector de Tienda -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden mb-6">
                    <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                        <div class="flex items-center">
                            <div class="p-2 bg-blue-100 dark:bg-blue-900 rounded-lg">
                                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <h3 class="ml-3 text-lg font-medium text-gray-900 dark:text-white">Seleccionar Tienda</h3>
                        </div>
                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                            Selecciona una tienda para ver el desglose de tiempos de actualización y modificarlos
                        </p>
                    </div>
                    
                    <div class="p-6">
                        <div class="max-w-md">
                            <label for="tienda-select" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Tienda
                            </label>
                            <select id="tienda-select" 
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                                <option value="">Selecciona una tienda...</option>
                                @foreach($tiendas as $tienda)
                                    <option value="{{ $tienda['id'] }}" data-nombre="{{ $tienda['nombre'] }}">
                                        {{ $tienda['nombre'] }} ({{ $tienda['tiempo_comun_formateado'] }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        
                        @if($tiendas->isEmpty())
                            <div class="text-center py-12">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                </svg>
                                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No hay tiendas con ofertas</h3>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                    No se encontraron tiendas que tengan ofertas asociadas.
                                </p>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Desglose de Tiempos (se muestra al seleccionar una tienda) -->
                <div id="desglose-container" class="hidden">
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 mb-6">
                        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <div class="p-2 bg-green-100 dark:bg-green-900 rounded-lg">
                                        <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                        </svg>
                                    </div>
                                    <h3 class="ml-3 text-lg font-medium text-gray-900 dark:text-white" id="tienda-seleccionada-nombre">
                                        Tienda Seleccionada
                                    </h3>
                                </div>
                                <button onclick="ocultarDesglose()"
                                    class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                        
                    </div>
                    
                    <!-- Gráfico de barras horizontal - Ocupa todo el ancho del main -->
                    <div class="bg-white dark:bg-gray-800 p-4 shadow mb-6" style="margin-left: -1rem; margin-right: -1rem; width: calc(100% + 2rem); box-sizing: border-box;">
                        <div style="position: relative; height: 256px; width: 100%;">
                            <canvas id="grafico-tiempos" style="width: 100% !important; height: 100% !important;"></canvas>
                        </div>
                        <div id="mensaje-grafico" class="text-center text-gray-500 dark:text-gray-400 mt-4 hidden">No hay ofertas en esta tienda.</div>
                    </div>
                    
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 mb-6">
                        <div class="p-6">
                            <!-- Estadísticas -->
                            <div id="estadisticas-desglose" class="mb-4 text-sm text-gray-600 dark:text-gray-400">
                                <!-- Se llena dinámicamente -->
                            </div>

                            <!-- Formulario para cambiar tiempo -->
                            <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                                <h4 class="text-md font-medium text-gray-900 dark:text-white mb-4">
                                    Cambiar Tiempo de Actualización
                                </h4>
                                <form id="formulario-cambiar-tiempo" class="space-y-4">
                                    @csrf
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                        <div>
                                            <label for="tiempo_cantidad" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                                Cantidad
                                            </label>
                                            <input type="number" 
                                                id="tiempo_cantidad" 
                                                name="tiempo_cantidad" 
                                                min="1" 
                                                required
                                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                                        </div>
                                        
                                        <div>
                                            <label for="tiempo_unidad" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                                Unidad
                                            </label>
                                            <select id="tiempo_unidad" 
                                                name="tiempo_unidad" 
                                                required
                                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                                                <option value="minutos">Minutos</option>
                                                <option value="horas" selected>Horas</option>
                                                <option value="dias">Días</option>
                                            </select>
                                        </div>
                                        
                                        <div class="flex items-end">
                                            <button type="submit" 
                                                id="btn-actualizar-tiempo"
                                                class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-md transition-colors focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                                Actualizar Todas
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Notificaciones -->
                <div id="notificaciones" class="fixed top-4 right-4 z-50 space-y-2"></div>
            </div>
        </main>
    </div>

    <script>
        let tiendaSeleccionadaId = null;
        let tiendaSeleccionadaNombre = null;

        // Event listener para el desplegable
        document.getElementById('tienda-select').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            
            if (this.value === '') {
                ocultarDesglose();
                return;
            }
            
            const tiendaId = parseInt(this.value);
            const tiendaNombre = selectedOption.getAttribute('data-nombre');
            
            seleccionarTienda(tiendaId, tiendaNombre);
        });

        // Seleccionar tienda
        function seleccionarTienda(tiendaId, tiendaNombre) {
            tiendaSeleccionadaId = tiendaId;
            tiendaSeleccionadaNombre = tiendaNombre;
            
            // Actualizar UI
            document.getElementById('tienda-seleccionada-nombre').textContent = tiendaNombre;
            document.getElementById('desglose-container').classList.remove('hidden');
            
            // Cargar desglose
            cargarDesgloseTiempos(tiendaId);
        }

        // Ocultar desglose
        function ocultarDesglose() {
            document.getElementById('desglose-container').classList.add('hidden');
            document.getElementById('tienda-select').value = '';
            tiendaSeleccionadaId = null;
            tiendaSeleccionadaNombre = null;
        }

        // Variables globales para el gráfico
        let graficoTiempos = null;
        
        // Cargar desglose de tiempos
        async function cargarDesgloseTiempos(tiendaId) {
            try {
                const response = await fetch(`/panel-privado/tiendas/${tiendaId}/desglose-tiempos`);
                const data = await response.json();
                
                const mensajeGrafico = document.getElementById('mensaje-grafico');
                const canvas = document.getElementById('grafico-tiempos');
                
                if (!canvas) {
                    console.error('No se encontró el elemento canvas del gráfico');
                    mostrarNotificacion('Error: No se encontró el elemento del gráfico', 'error');
                    return;
                }
                
                if (data.desglose.length === 0) {
                    if (mensajeGrafico) {
                        mensajeGrafico.classList.remove('hidden');
                    }
                    if (graficoTiempos) {
                        graficoTiempos.destroy();
                        graficoTiempos = null;
                    }
                    return;
                }
                
                if (mensajeGrafico) {
                    mensajeGrafico.classList.add('hidden');
                }
                
                // Obtener valores de rango configurado
                const minRango = data.frecuencia_minima_minutos || 0;
                const maxRango = data.frecuencia_maxima_minutos || 1440;
                
                // Ordenar desglose por tiempo
                const desgloseOrdenado = [...data.desglose].sort((a, b) => a.minutos - b.minutos);
                
                // Preparar datos para Chart.js
                const labels = desgloseOrdenado.map(item => formatearTiempo(item.minutos));
                const valores = desgloseOrdenado.map(item => item.cantidad);
                
                const isDark = document.documentElement.classList.contains('dark');
                
                // Color del área rellena (azul)
                const colorArea = isDark ? 'rgba(96, 165, 250, 0.3)' : 'rgba(59, 130, 246, 0.3)';
                
                // Color de la línea (azul)
                const colorLinea = isDark ? 'rgba(96, 165, 250, 1)' : 'rgba(59, 130, 246, 1)';
                
                // Destruir gráfico anterior si existe
                if (graficoTiempos) {
                    graficoTiempos.destroy();
                    graficoTiempos = null;
                }
                
                // Configurar plugins
                const pluginsConfig = {
                    legend: {
                        display: true,
                        position: 'top',
                        labels: {
                            color: isDark ? '#e5e7eb' : '#374151',
                            font: {
                                size: 12,
                                weight: 'bold'
                            }
                        }
                    },
                    tooltip: {
                        enabled: true,
                        intersect: false,
                        mode: 'index',
                        callbacks: {
                            label: function(context) {
                                const item = desgloseOrdenado[context.dataIndex];
                                return `${context.parsed.y} ofertas - ${item.formato} (${item.minutos} min)`;
                            }
                        }
                    }
                };
                
                // Crear nuevo gráfico
                const ctx = canvas.getContext('2d');
                graficoTiempos = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Cantidad de ofertas',
                            data: valores,
                            borderColor: colorLinea,
                            backgroundColor: colorArea,
                            borderWidth: 2,
                            fill: true,
                            tension: 0.4,
                            pointRadius: 0,
                            pointHoverRadius: 5,
                            pointHoverBorderWidth: 2,
                            pointHoverBackgroundColor: colorLinea
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: {
                            mode: 'index',
                            intersect: false
                        },
                        plugins: pluginsConfig,
                        scales: {
                            x: {
                                title: {
                                    display: true,
                                    text: 'Tiempo de actualización',
                                    color: isDark ? '#9ca3af' : '#6b7280',
                                    font: {
                                        size: 12,
                                        weight: 'bold'
                                    }
                                },
                                ticks: {
                                    color: isDark ? '#9ca3af' : '#6b7280',
                                    maxRotation: 45,
                                    minRotation: 45
                                },
                                grid: {
                                    color: isDark ? 'rgba(75, 85, 99, 0.2)' : 'rgba(229, 231, 235, 0.5)'
                                }
                            },
                            y: {
                                title: {
                                    display: true,
                                    text: 'Cantidad de ofertas',
                                    color: isDark ? '#9ca3af' : '#6b7280',
                                    font: {
                                        size: 12,
                                        weight: 'bold'
                                    }
                                },
                                beginAtZero: true,
                                ticks: {
                                    stepSize: 1,
                                    color: isDark ? '#9ca3af' : '#6b7280'
                                },
                                grid: {
                                    color: isDark ? 'rgba(75, 85, 99, 0.2)' : 'rgba(229, 231, 235, 0.5)'
                                }
                            }
                        }
                    }
                });
                
                // Mostrar estadísticas
                const totalOfertas = data.desglose.reduce((sum, item) => sum + item.cantidad, 0);
                const estadisticas = document.getElementById('estadisticas-desglose');
                estadisticas.innerHTML = `
                    <div class="grid grid-cols-4 gap-4 mt-4">
                        <div class="text-center p-3 bg-gray-100 dark:bg-gray-800 rounded-lg">
                            <div class="text-xl font-bold text-gray-900 dark:text-white">${totalOfertas}</div>
                            <div class="text-xs text-gray-600 dark:text-gray-400">Total Ofertas</div>
                        </div>
                        <div class="text-center p-3 bg-gray-100 dark:bg-gray-800 rounded-lg">
                            <div class="text-xl font-bold text-gray-900 dark:text-white">${data.desglose.length}</div>
                            <div class="text-xs text-gray-600 dark:text-gray-400">Tiempos Diferentes</div>
                        </div>
                        <div class="text-center p-3 bg-gray-100 dark:bg-gray-800 rounded-lg">
                            <div class="text-xl font-bold text-gray-900 dark:text-white">${formatearTiempo(minRango)}</div>
                            <div class="text-xs text-gray-600 dark:text-gray-400">Frecuencia Mínima</div>
                        </div>
                        <div class="text-center p-3 bg-gray-100 dark:bg-gray-800 rounded-lg">
                            <div class="text-xl font-bold text-gray-900 dark:text-white">${formatearTiempo(maxRango)}</div>
                            <div class="text-xs text-gray-600 dark:text-gray-400">Frecuencia Máxima</div>
                        </div>
                    </div>
                `;
                
            } catch (error) {
                console.error('Error al cargar desglose:', error);
                mostrarNotificacion('Error al cargar el desglose de tiempos: ' + error.message, 'error');
                
                // Mostrar mensaje de error en el gráfico
                const mensajeGrafico = document.getElementById('mensaje-grafico');
                if (mensajeGrafico) {
                    mensajeGrafico.textContent = 'Error al cargar el gráfico. Por favor, recarga la página.';
                    mensajeGrafico.classList.remove('hidden');
                }
            }
        }
        
        // Función auxiliar para formatear tiempo
        function formatearTiempo(minutos) {
            if (minutos < 60) {
                return `${Math.round(minutos)} min`;
            } else if (minutos < 24 * 60) {
                const horas = Math.round(minutos / 60 * 10) / 10;
                return `${horas} h`;
            } else {
                const dias = Math.round(minutos / (24 * 60) * 10) / 10;
                return `${dias} d`;
            }
        }

        // Formulario para cambiar tiempo
        document.getElementById('formulario-cambiar-tiempo').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            if (!tiendaSeleccionadaId) {
                mostrarNotificacion('Por favor selecciona una tienda primero', 'error');
                return;
            }
            
            const formData = new FormData(this);
            const button = document.getElementById('btn-actualizar-tiempo');
            const originalText = button.textContent;
            
            // Mostrar estado de carga
            button.disabled = true;
            button.textContent = 'Actualizando...';
            
            try {
                const response = await fetch(`/panel-privado/tiendas/${tiendaSeleccionadaId}/actualizar-tiempos`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        tiempo_cantidad: parseInt(formData.get('tiempo_cantidad')),
                        tiempo_unidad: formData.get('tiempo_unidad')
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    mostrarNotificacion(result.message, 'success');
                    
                    // Recargar el desglose
                    await cargarDesgloseTiempos(tiendaSeleccionadaId);
                    
                    // Recargar la página para actualizar los promedios
                    setTimeout(() => {
                        location.reload();
                    }, 2000);
                } else {
                    mostrarNotificacion('Error al actualizar los tiempos', 'error');
                }
                
            } catch (error) {
                console.error('Error:', error);
                mostrarNotificacion('Error de conexión', 'error');
            } finally {
                button.disabled = false;
                button.textContent = originalText;
            }
        });

        // Función para mostrar notificaciones
        function mostrarNotificacion(mensaje, tipo = 'info') {
            const notificacion = document.createElement('div');
            notificacion.className = `p-4 rounded-lg shadow-lg max-w-sm ${
                tipo === 'success' ? 'bg-green-500 text-white' :
                tipo === 'error' ? 'bg-red-500 text-white' :
                'bg-blue-500 text-white'
            }`;
            notificacion.textContent = mensaje;

            const container = document.getElementById('notificaciones');
            container.appendChild(notificacion);

            setTimeout(() => {
                if (notificacion.parentNode) {
                    notificacion.parentNode.removeChild(notificacion);
                }
            }, 5000);
        }
    </script>
</body>
</html>
