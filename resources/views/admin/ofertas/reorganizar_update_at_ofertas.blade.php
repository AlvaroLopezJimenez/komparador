<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.dashboard') }}">
                <h2 class="font-semibold text-xl text-white leading-tight">Inicio -></h2>
            </a>
            <a href="{{ route('admin.tiendas.index') }}">
                <h2 class="font-semibold text-xl text-white leading-tight">Tiendas -></h2>
            </a>
            <h2 class="font-semibold text-xl text-white leading-tight">
                Reorganizar Update_at de Ofertas
            </h2>
        </div>
    </x-slot>

    <div class="max-w-4xl mx-auto py-10 px-4 space-y-8">
        {{-- Panel principal --}}
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6 space-y-6 border border-gray-200 dark:border-gray-700">
            <div class="text-center">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
                    Reorganizar Update_at de Ofertas
                </h1>
                <p class="text-gray-600 dark:text-gray-400">
                    Esta herramienta reorganiza las horas de actualización de las ofertas de una tienda específica,
                    distribuyéndolas uniformemente entre las 8:00 y las 23:00 horas, manteniendo el mismo día que tenían originalmente.
                </p>
            </div>

            {{-- Selector de tienda --}}
            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                <label for="tienda-select" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Seleccionar Tienda
                </label>
                <select id="tienda-select" class="w-full px-4 py-2 rounded bg-white dark:bg-gray-800 text-gray-800 dark:text-white border border-gray-300 dark:border-gray-600">
                    <option value="">Selecciona una tienda...</option>
                    @foreach($tiendas as $tienda)
                        <option value="{{ $tienda->id }}" data-ofertas="{{ $tienda->ofertas_count ?? 0 }}">
                            {{ $tienda->nombre }} ({{ $tienda->ofertas_count ?? 0 }} ofertas)
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Información de la tienda seleccionada --}}
            <div id="tienda-info" class="hidden bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-blue-900 dark:text-blue-100" id="tienda-nombre"></h3>
                        <p class="text-sm text-blue-700 dark:text-blue-300" id="tienda-details"></p>
                    </div>
                    <div class="text-right">
                        <p class="text-2xl font-bold text-blue-900 dark:text-blue-100" id="total-ofertas">0</p>
                        <p class="text-sm text-blue-700 dark:text-blue-300">ofertas</p>
                    </div>
                </div>
            </div>

            {{-- Gráfica de distribución ANTES de reorganizar --}}
            <div id="grafica-antes-container" class="hidden bg-gray-50 dark:bg-gray-700 rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">📊 Distribución Actual de Actualizaciones por Hora</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                    Esta gráfica muestra cuántas ofertas se actualizarán en cada hora del día según su configuración actual de <code>updated_at</code> y <code>frecuencia_actualizar_precio_minutos</code>.
                </p>
                <div class="relative" style="height: 300px;">
                    <canvas id="grafica-antes"></canvas>
                </div>
                <div class="mt-4 p-3 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg">
                    <p class="text-sm text-yellow-700 dark:text-yellow-300">
                        <strong>ℹ️ Nota:</strong> Las ofertas que deberían actualizarse fuera del horario de 8:00 a 23:00 se contabilizan a las 8:00 AM.
                    </p>
                </div>
            </div>

            {{-- Configuración de horarios --}}
            <div id="configuracion-horarios" class="hidden bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">⏰ Configuración de Horarios</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="hora-inicio" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Hora de inicio
                        </label>
                        <input type="number" id="hora-inicio" min="0" max="23" value="8" 
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Hora en formato 24h (0-23)</p>
                    </div>
                    <div>
                        <label for="hora-fin" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Hora de fin
                        </label>
                        <input type="number" id="hora-fin" min="0" max="23" value="22" 
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Hora en formato 24h (0-23)</p>
                    </div>
                </div>
                <div class="mt-3 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                    <p class="text-sm text-blue-700 dark:text-blue-300">
                        <strong>💡 Consejo:</strong> Las ofertas se distribuirán uniformemente entre las horas seleccionadas, manteniendo el mismo día que tenían originalmente. 
                        Si tienes 2 ofertas entre 8:00 y 22:59, una podría ir a las 11:00 y otra a las 15:00 del mismo día.
                    </p>
                </div>
            </div>

            {{-- Botón de ejecutar --}}
            <div class="text-center">
                <button id="btn-ejecutar" disabled
                    class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-pink-600 hover:bg-pink-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-pink-500 disabled:opacity-50 disabled:cursor-not-allowed">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    Ejecutar Reorganización
                </button>
            </div>

            {{-- Barra de progreso --}}
            <div id="progreso-container" class="hidden">
                <div class="bg-gray-200 dark:bg-gray-700 rounded-full h-4 mb-2">
                    <div id="progreso-bar" class="bg-pink-600 h-4 rounded-full transition-all duration-300" style="width: 0%"></div>
                </div>
                <div class="flex justify-between text-sm text-gray-600 dark:text-gray-400">
                    <span id="progreso-text">Preparando...</span>
                    <span id="progreso-porcentaje">0%</span>
                </div>
            </div>

            {{-- Resultados --}}
            <div id="resultados" class="hidden">
                <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4">
                    <h3 class="text-lg font-semibold text-green-900 dark:text-green-100 mb-2">✅ Reorganización Completada</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="text-center">
                            <p class="text-2xl font-bold text-green-900 dark:text-green-100" id="total-procesadas">0</p>
                            <p class="text-sm text-green-700 dark:text-green-300">Total procesadas</p>
                        </div>
                        <div class="text-center">
                            <p class="text-2xl font-bold text-green-900 dark:text-green-100" id="actualizadas">0</p>
                            <p class="text-sm text-green-700 dark:text-green-300">Actualizadas</p>
                        </div>
                        <div class="text-center">
                            <p class="text-2xl font-bold text-red-900 dark:text-red-100" id="errores">0</p>
                            <p class="text-sm text-red-700 dark:text-red-300">Errores</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Gráfica de distribución DESPUÉS de reorganizar --}}
            <div id="grafica-despues-container" class="hidden bg-gray-50 dark:bg-gray-700 rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">📊 Nueva Distribución de Actualizaciones por Hora</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                    Esta gráfica muestra cómo quedaron distribuidas las ofertas después de la reorganización.
                </p>
                <div class="relative" style="height: 300px;">
                    <canvas id="grafica-despues"></canvas>
                </div>
                <div class="mt-4 p-3 bg-green-50 dark:bg-green-900/20 rounded-lg">
                    <p class="text-sm text-green-700 dark:text-green-300">
                        <strong>✨ Resultado:</strong> Las ofertas ahora están distribuidas de manera más uniforme a lo largo del día.
                    </p>
                </div>
            </div>

            {{-- Log detallado --}}
            <div id="log-container" class="hidden">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Detalle de la Ejecución</h3>
                <div class="bg-gray-900 text-green-400 p-4 rounded-lg font-mono text-sm max-h-96 overflow-y-auto">
                    <div id="log-content"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Incluir Chart.js --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const tiendaSelect = document.getElementById('tienda-select');
            const tiendaInfo = document.getElementById('tienda-info');
            const btnEjecutar = document.getElementById('btn-ejecutar');
            const progresoContainer = document.getElementById('progreso-container');
            const resultados = document.getElementById('resultados');
            const logContainer = document.getElementById('log-container');
            
            // Variables para las gráficas
            let graficaAntes = null;
            let graficaDespues = null;

            // Manejar selección de tienda
            tiendaSelect.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                const tiendaId = this.value;
                const ofertasCount = parseInt(selectedOption.dataset.ofertas) || 0;

                if (tiendaId) {
                    document.getElementById('tienda-nombre').textContent = selectedOption.textContent.split(' (')[0];
                    document.getElementById('tienda-details').textContent = `API: ${selectedOption.dataset.api || 'No especificada'}`;
                    document.getElementById('total-ofertas').textContent = ofertasCount;
                    tiendaInfo.classList.remove('hidden');
                    document.getElementById('configuracion-horarios').classList.remove('hidden');
                    btnEjecutar.disabled = ofertasCount === 0;
                    
                    // Cargar y mostrar la gráfica de distribución actual
                    cargarDistribucionActual(tiendaId);
                } else {
                    tiendaInfo.classList.add('hidden');
                    document.getElementById('configuracion-horarios').classList.add('hidden');
                    document.getElementById('grafica-antes-container').classList.add('hidden');
                    btnEjecutar.disabled = true;
                }
            });

            // Manejar ejecución
            btnEjecutar.addEventListener('click', function() {
                const tiendaId = tiendaSelect.value;
                if (!tiendaId) return;

                // Deshabilitar controles
                btnEjecutar.disabled = true;
                tiendaSelect.disabled = true;
                progresoContainer.classList.remove('hidden');
                resultados.classList.add('hidden');
                logContainer.classList.add('hidden');

                // Iniciar progreso
                actualizarProgreso(0, 'Iniciando reorganización...');

                // Obtener horas personalizadas
                const horaInicio = document.getElementById('hora-inicio').value;
                const horaFin = document.getElementById('hora-fin').value;

                // Ejecutar reorganización
                fetch('{{ route("admin.ofertas.reorganizar.update-at.ejecutar") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        tienda_id: tiendaId,
                        hora_inicio: parseInt(horaInicio),
                        hora_fin: parseInt(horaFin)
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'ok') {
                        // Mostrar resultados
                        document.getElementById('total-procesadas').textContent = data.total_ofertas;
                        document.getElementById('actualizadas').textContent = data.actualizadas;
                        document.getElementById('errores').textContent = data.errores;
                        
                        // Mostrar información de horarios utilizados
                        const horariosInfo = document.createElement('div');
                        horariosInfo.className = 'mt-3 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg';
                        horariosInfo.innerHTML = `
                            <p class="text-sm text-blue-700 dark:text-blue-300">
                                <strong>⏰ Horarios utilizados:</strong> ${data.hora_inicio}:00 - ${data.hora_fin}:59
                            </p>
                        `;
                        document.getElementById('resultados').appendChild(horariosInfo);
                        
                        // Mostrar log
                        mostrarLog(data.log);
                        
                        // Completar progreso
                        actualizarProgreso(100, 'Reorganización completada');
                        
                        // Mostrar resultados
                        resultados.classList.remove('hidden');
                        logContainer.classList.remove('hidden');
                        
                        // Cargar y mostrar la gráfica DESPUÉS de la reorganización
                        cargarDistribucionDespues(tiendaId);
                        
                        // Mostrar notificación
                        mostrarNotificacion(`Reorganización completada: ${data.actualizadas} ofertas actualizadas`, 'success');
                    } else {
                        actualizarProgreso(0, 'Error: ' + data.message);
                        mostrarNotificacion('Error: ' + data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    actualizarProgreso(0, 'Error de conexión');
                    mostrarNotificacion('Error de conexión', 'error');
                })
                .finally(() => {
                    // Rehabilitar controles
                    btnEjecutar.disabled = false;
                    tiendaSelect.disabled = false;
                });
            });

            function actualizarProgreso(porcentaje, texto) {
                document.getElementById('progreso-bar').style.width = porcentaje + '%';
                document.getElementById('progreso-text').textContent = texto;
                document.getElementById('progreso-porcentaje').textContent = Math.round(porcentaje) + '%';
            }

            function mostrarLog(log) {
                const logContent = document.getElementById('log-content');
                logContent.innerHTML = '';
                
                log.forEach((item, index) => {
                    const div = document.createElement('div');
                    div.className = 'mb-1';
                    
                    if (item.status === 'actualizada') {
                        const fechaOriginal = item.fecha_original || 'Fecha no disponible';
                        div.innerHTML = `<span class="text-green-400">✓</span> Oferta ${item.oferta_id} (${item.producto}) - Original: ${fechaOriginal} → Nueva: ${item.nueva_fecha}</span>`;
                    } else if (item.status === 'error') {
                        div.innerHTML = `<span class="text-red-400">✗</span> Error en oferta ${item.oferta_id} (${item.producto}): ${item.error}</span>`;
                    }
                    
                    logContent.appendChild(div);
                });
            }

            function mostrarNotificacion(mensaje, tipo = 'info') {
                const notificacion = document.createElement('div');
                notificacion.className = `fixed top-4 right-4 p-4 rounded-lg shadow-lg z-50 max-w-sm ${
                    tipo === 'success' ? 'bg-green-500 text-white' :
                    tipo === 'error' ? 'bg-red-500 text-white' :
                    'bg-blue-500 text-white'
                }`;
                notificacion.textContent = mensaje;

                document.body.appendChild(notificacion);

                setTimeout(() => {
                    if (notificacion.parentNode) {
                        notificacion.parentNode.removeChild(notificacion);
                    }
                }, 5000);
            }

            // Función para cargar la distribución actual de ofertas
            function cargarDistribucionActual(tiendaId) {
                fetch('{{ route("admin.ofertas.reorganizar.update-at.distribucion") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ tienda_id: tiendaId })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'ok') {
                        mostrarGraficaAntes(data.distribucion, data.total_ofertas);
                        document.getElementById('grafica-antes-container').classList.remove('hidden');
                    } else {
                        console.error('Error al cargar distribución:', data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
            }

            // Función para cargar la distribución después de la reorganización
            function cargarDistribucionDespues(tiendaId) {
                fetch('{{ route("admin.ofertas.reorganizar.update-at.distribucion-despues") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ tienda_id: tiendaId })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'ok') {
                        mostrarGraficaDespues(data.distribucion, data.total_ofertas);
                        document.getElementById('grafica-despues-container').classList.remove('hidden');
                    } else {
                        console.error('Error al cargar distribución:', data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
            }

            // Función para mostrar la gráfica ANTES de la reorganización
            function mostrarGraficaAntes(distribucion, totalOfertas) {
                const ctx = document.getElementById('grafica-antes').getContext('2d');
                
                // Destruir gráfica anterior si existe
                if (graficaAntes) {
                    graficaAntes.destroy();
                }

                // Preparar datos
                const labels = [];
                const valores = [];
                for (let hora = 8; hora <= 23; hora++) {
                    labels.push(hora.toString().padStart(2, '0') + ':00');
                    valores.push(distribucion[hora] || 0);
                }

                // Crear nueva gráfica
                graficaAntes = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Ofertas a actualizar',
                            data: valores,
                            backgroundColor: 'rgba(59, 130, 246, 0.6)',
                            borderColor: 'rgba(59, 130, 246, 1)',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    stepSize: 1
                                },
                                title: {
                                    display: true,
                                    text: 'Número de ofertas'
                                }
                            },
                            x: {
                                title: {
                                    display: true,
                                    text: 'Hora del día'
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                display: false
                            },
                            title: {
                                display: true,
                                text: `Total de ofertas: ${totalOfertas}`
                            }
                        }
                    }
                });
            }

            // Función para mostrar la gráfica DESPUÉS de la reorganización
            function mostrarGraficaDespues(distribucion, totalOfertas) {
                const ctx = document.getElementById('grafica-despues').getContext('2d');
                
                // Destruir gráfica anterior si existe
                if (graficaDespues) {
                    graficaDespues.destroy();
                }

                // Preparar datos
                const labels = [];
                const valores = [];
                for (let hora = 8; hora <= 23; hora++) {
                    labels.push(hora.toString().padStart(2, '0') + ':00');
                    valores.push(distribucion[hora] || 0);
                }

                // Crear nueva gráfica
                graficaDespues = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Ofertas a actualizar',
                            data: valores,
                            backgroundColor: 'rgba(34, 197, 94, 0.6)',
                            borderColor: 'rgba(34, 197, 94, 1)',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    stepSize: 1
                                },
                                title: {
                                    display: true,
                                    text: 'Número de ofertas'
                                }
                            },
                            x: {
                                title: {
                                    display: true,
                                    text: 'Hora del día'
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                display: false
                            },
                            title: {
                                display: true,
                                text: `Total de ofertas reorganizadas: ${totalOfertas}`
                            }
                        }
                    }
                });
            }
        });
    </script>
</x-app-layout>
