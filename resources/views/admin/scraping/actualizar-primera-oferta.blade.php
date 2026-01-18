<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <a href="{{ route('admin.dashboard') }}">
                    <h2 class="font-semibold text-xl text-white leading-tight">← Panel</h2>
                </a>
                <h1 class="text-2xl font-light text-white">Actualizar Primera Oferta - Tiempo Real</h1>
            </div>
            <div class="text-sm text-gray-300">
                {{ now()->format('d/m/Y H:i') }}
            </div>
        </div>
    </x-slot>

    <div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-900 dark:to-gray-800">
        <div class="max-w-7xl mx-auto px-4 py-8">
            
            <!-- Panel de Control -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden mb-6">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="p-2 bg-blue-100 dark:bg-blue-900 rounded-lg">
                                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                </svg>
                            </div>
                            <h2 class="ml-3 text-lg font-medium text-gray-900 dark:text-white">Control de Ejecución</h2>
                        </div>
                        <div class="flex space-x-3">
                            <button id="btn-iniciar" onclick="iniciarEjecucion()" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                                <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h1m4 0h1m-6 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                Iniciar
                            </button>
                            <button id="btn-detener" onclick="detenerEjecucion()" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors hidden">
                                <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                                Detener
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Estadísticas -->
                <div class="p-6 grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg">
                        <div class="flex items-center">
                            <div class="p-2 bg-blue-100 dark:bg-blue-800 rounded-lg">
                                <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Ofertas</p>
                                <p id="total-ofertas" class="text-lg font-semibold text-gray-900 dark:text-white">0</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-green-50 dark:bg-green-900/20 p-4 rounded-lg">
                        <div class="flex items-center">
                            <div class="p-2 bg-green-100 dark:bg-green-800 rounded-lg">
                                <svg class="w-4 h-4 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Actualizadas</p>
                                <p id="total-actualizadas" class="text-lg font-semibold text-gray-900 dark:text-white">0</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-red-50 dark:bg-red-900/20 p-4 rounded-lg">
                        <div class="flex items-center">
                            <div class="p-2 bg-red-100 dark:bg-red-800 rounded-lg">
                                <svg class="w-4 h-4 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Errores</p>
                                <p id="total-errores" class="text-lg font-semibold text-gray-900 dark:text-white">0</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-yellow-50 dark:bg-yellow-900/20 p-4 rounded-lg">
                        <div class="flex items-center">
                            <div class="p-2 bg-yellow-100 dark:bg-yellow-800 rounded-lg">
                                <svg class="w-4 h-4 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Procesadas</p>
                                <p id="total-procesadas" class="text-lg font-semibold text-gray-900 dark:text-white">0</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Barra de Progreso -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden mb-6">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Progreso</span>
                        <span id="progreso-texto" class="text-sm text-gray-500 dark:text-gray-400">0%</span>
                    </div>
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                        <div id="barra-progreso" class="bg-blue-600 h-2 rounded-full transition-all duration-300" style="width: 0%"></div>
                    </div>
                </div>
            </div>

            <!-- Oferta Actual -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden mb-6">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Oferta Actual</h3>
                </div>
                <div class="p-6">
                    <div id="oferta-actual" class="text-center text-gray-500 dark:text-gray-400">
                        <svg class="w-12 h-12 mx-auto mb-4 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                        </svg>
                        <p>No hay ejecución activa</p>
                    </div>
                </div>
            </div>

            <!-- Log de Actividad -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Log de Actividad</h3>
                        <button onclick="limpiarLog()" class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                            Limpiar
                        </button>
                    </div>
                </div>
                <div class="p-6">
                    <div id="log-actividad" class="space-y-2 max-h-96 overflow-y-auto">
                        <div class="text-center text-gray-500 dark:text-gray-400">
                            <p>El log aparecerá aquí durante la ejecución</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let ejecucionId = null;
        let indiceActual = 0;
        let ejecutando = false;
        let intervalo = null;

        function iniciarEjecucion() {
            if (ejecutando) return;
            
            ejecutando = true;
            document.getElementById('btn-iniciar').classList.add('hidden');
            document.getElementById('btn-detener').classList.remove('hidden');
            
            // Limpiar log
            document.getElementById('log-actividad').innerHTML = '';
            
            // Resetear contadores
            document.getElementById('total-ofertas').textContent = '0';
            document.getElementById('total-actualizadas').textContent = '0';
            document.getElementById('total-errores').textContent = '0';
            document.getElementById('total-procesadas').textContent = '0';
            document.getElementById('barra-progreso').style.width = '0%';
            document.getElementById('progreso-texto').textContent = '0%';
            
            // Mostrar estado inicial
            document.getElementById('oferta-actual').innerHTML = `
                <div class="flex items-center justify-center">
                    <svg class="animate-spin h-8 w-8 text-blue-600" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span class="ml-2 text-gray-700 dark:text-gray-300">Iniciando ejecución...</span>
                </div>
            `;
            
            agregarLog('Iniciando ejecución de actualización de primera oferta...', 'info');
            
            fetch('{{ route("admin.scraping.actualizar-primera-oferta.iniciar") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    ejecucionId = data.ejecucion_id;
                    indiceActual = 0;
                    
                    if (data.completada) {
                        finalizarEjecucion(data);
                    } else {
                        document.getElementById('total-ofertas').textContent = data.total_ofertas;
                        agregarLog(`Ejecución iniciada. Total de ofertas a procesar: ${data.total_ofertas}`, 'success');
                        procesarSiguienteOferta();
                    }
                } else {
                    throw new Error('Error al iniciar la ejecución');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                agregarLog('Error al iniciar la ejecución: ' + error.message, 'error');
                detenerEjecucion();
            });
        }

        function procesarSiguienteOferta() {
            if (!ejecutando || !ejecucionId) return;
            
            fetch('{{ route("admin.scraping.actualizar-primera-oferta.procesar") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    ejecucion_id: ejecucionId,
                    indice_actual: indiceActual
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Actualizar estadísticas
                    document.getElementById('total-actualizadas').textContent = data.progreso.actualizadas;
                    document.getElementById('total-errores').textContent = data.progreso.errores;
                    document.getElementById('total-procesadas').textContent = data.progreso.procesadas;
                    
                    // Actualizar barra de progreso
                    const porcentaje = data.progreso.total > 0 ? Math.round((data.progreso.procesadas / data.progreso.total) * 100) : 0;
                    document.getElementById('barra-progreso').style.width = porcentaje + '%';
                    document.getElementById('progreso-texto').textContent = porcentaje + '%';
                    
                    // Mostrar oferta actual
                    if (data.oferta_actual) {
                        mostrarOfertaActual(data.oferta_actual);
                        agregarLogOferta(data.oferta_actual);
                    }
                    
                    if (data.completada) {
                        finalizarEjecucion(data);
                    } else {
                        indiceActual++;
                        // Continuar con la siguiente oferta después de un pequeño delay
                        setTimeout(procesarSiguienteOferta, 1000);
                    }
                } else {
                    throw new Error(data.error || 'Error al procesar oferta');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                agregarLog('Error al procesar oferta: ' + error.message, 'error');
                detenerEjecucion();
            });
        }

        function mostrarOfertaActual(oferta) {
            const estado = oferta.success ? 'success' : 'error';
            const icono = oferta.success ? 
                '<svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>' :
                '<svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>';
            
            const precioInfo = oferta.success ? 
                `<div class="text-sm text-gray-600 dark:text-gray-400">
                    <span class="line-through">€${oferta.precio_anterior}</span> → 
                    <span class="font-semibold text-green-600">€${oferta.precio_nuevo}</span>
                </div>` :
                `<div class="text-sm text-red-600">Error: ${oferta.error}</div>`;
            
            document.getElementById('oferta-actual').innerHTML = `
                <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <div class="flex items-center">
                        ${icono}
                        <div class="ml-3">
                            <div class="font-medium text-gray-900 dark:text-white">${oferta.tienda}</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">ID: ${oferta.id}</div>
                            ${precioInfo}
                        </div>
                    </div>
                    <div class="text-right">
                        <a href="${oferta.url}" target="_blank" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 text-sm">
                            Ver URL →
                        </a>
                    </div>
                </div>
            `;
        }

        function agregarLogOferta(oferta) {
            const tipo = oferta.success ? 'success' : 'error';
            const mensaje = oferta.success ? 
                `Oferta ${oferta.id} (${oferta.tienda}) actualizada: €${oferta.precio_anterior} → €${oferta.precio_nuevo}` :
                `Error en oferta ${oferta.id} (${oferta.tienda}): ${oferta.error}`;
            
            agregarLog(mensaje, tipo);
        }

        function agregarLog(mensaje, tipo = 'info') {
            const logContainer = document.getElementById('log-actividad');
            const timestamp = new Date().toLocaleTimeString();
            
            const colorClass = tipo === 'success' ? 'text-green-600 dark:text-green-400' :
                              tipo === 'error' ? 'text-red-600 dark:text-red-400' :
                              tipo === 'warning' ? 'text-yellow-600 dark:text-yellow-400' :
                              'text-gray-600 dark:text-gray-400';
            
            const logEntry = document.createElement('div');
            logEntry.className = `flex items-start space-x-2 ${colorClass}`;
            logEntry.innerHTML = `
                <span class="text-xs text-gray-400 dark:text-gray-500 flex-shrink-0">${timestamp}</span>
                <span class="text-sm">${mensaje}</span>
            `;
            
            logContainer.appendChild(logEntry);
            logContainer.scrollTop = logContainer.scrollHeight;
        }

        function finalizarEjecucion(data) {
            ejecutando = false;
            document.getElementById('btn-iniciar').classList.remove('hidden');
            document.getElementById('btn-detener').classList.add('hidden');
            
            const progreso = data.progreso || {};
            agregarLog(`Ejecución completada. Actualizadas: ${progreso.actualizadas || 0}, Errores: ${progreso.errores || 0}`, 'success');
            
            document.getElementById('oferta-actual').innerHTML = `
                <div class="text-center text-green-600 dark:text-green-400">
                    <svg class="w-12 h-12 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    <p>Ejecución completada</p>
                </div>
            `;
        }

        function detenerEjecucion() {
            ejecutando = false;
            document.getElementById('btn-iniciar').classList.remove('hidden');
            document.getElementById('btn-detener').classList.add('hidden');
            
            agregarLog('Ejecución detenida por el usuario', 'warning');
            
            document.getElementById('oferta-actual').innerHTML = `
                <div class="text-center text-gray-500 dark:text-gray-400">
                    <svg class="w-12 h-12 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                    <p>Ejecución detenida</p>
                </div>
            `;
        }

        function limpiarLog() {
            document.getElementById('log-actividad').innerHTML = `
                <div class="text-center text-gray-500 dark:text-gray-400">
                    <p>El log aparecerá aquí durante la ejecución</p>
                </div>
            `;
        }
    </script>
</x-app-layout>
