<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.dashboard') }}">
                <h2 class="font-semibold text-xl text-white leading-tight">Inicio -></h2>
            </a>
            <h2 class="font-semibold text-xl text-white leading-tight">Testing Masivo de Ofertas API</h2>
        </div>
    </x-slot>

    <div class="max-w-6xl mx-auto py-10 px-4 space-y-8 bg-gray-50 dark:bg-gray-900 rounded-lg shadow-md">
        
        <!-- Panel de Control -->
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6 border border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-200 mb-4">Configuración de Testing Masivo</h3>
            <p class="text-gray-600 dark:text-gray-400 mb-6">Configura los criterios para buscar ofertas y ejecutar pruebas masivas de API.</p>
            
            <form id="formConfiguracion" class="space-y-4">
                @csrf
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="tienda" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Tienda
                        </label>
                        <select id="tienda" name="tienda" required
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                            <option value="">Seleccionar tienda...</option>
                            @foreach($tiendas as $tienda)
                                <option value="{{ $tienda }}">{{ $tienda }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div>
                        <label for="mostrar" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Estado de Ofertas
                        </label>
                        <select id="mostrar" name="mostrar" required
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                            <option value="no">Mostrar: No (por defecto)</option>
                            <option value="si">Mostrar: Sí</option>
                        </select>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="limite_ofertas" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Límite de Ofertas (opcional)
                        </label>
                        <input type="number" id="limite_ofertas" name="limite_ofertas" min="1"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                            placeholder="Dejar vacío para todas las ofertas">
                        <p class="text-xs text-gray-500 mt-1">Deja vacío para procesar todas las ofertas que cumplan los criterios</p>
                    </div>
                    
                    <div>
                        <label for="tiempo_entre_peticiones" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Tiempo entre peticiones (segundos)
                        </label>
                        <input type="number" id="tiempo_entre_peticiones" name="tiempo_entre_peticiones" min="1" max="60" value="2" required
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                        <p class="text-xs text-gray-500 mt-1">Tiempo de espera entre cada petición (1-60 segundos)</p>
                    </div>
                </div>
                
                <div class="flex gap-4">
                    <button type="button" id="btnBuscarOfertas"
                        class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-6 py-3 rounded-md shadow-md transition">
                        🔍 Buscar Ofertas
                    </button>
                    <button type="button" id="btnLimpiar" onclick="limpiarTodo()"
                        class="bg-gray-600 hover:bg-gray-700 text-white font-semibold px-6 py-3 rounded-md shadow-md transition">
                        🗑️ Limpiar Todo
                    </button>
                </div>
            </form>
        </div>

        <!-- Resultados de Búsqueda -->
        <div id="resultadosBusqueda" class="hidden bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6 border border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-200 mb-4">Resultados de Búsqueda</h3>
            
            <div id="loadingBusqueda" class="hidden text-center py-8">
                <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                <p class="mt-2 text-gray-600 dark:text-gray-400">Buscando ofertas...</p>
            </div>
            
            <div id="infoOfertas" class="hidden">
                <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded-lg p-4 mb-4">
                    <h4 class="font-semibold text-blue-800 dark:text-blue-200 mb-2">📊 Ofertas Encontradas</h4>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <strong class="text-gray-700 dark:text-gray-300">Total de ofertas:</strong>
                            <span id="totalOfertas" class="ml-2 text-lg font-bold text-blue-600 dark:text-blue-400">0</span>
                        </div>
                        <div>
                            <strong class="text-gray-700 dark:text-gray-300">Tienda:</strong>
                            <span id="tiendaSeleccionada" class="ml-2 text-gray-600 dark:text-gray-400">-</span>
                        </div>
                        <div>
                            <strong class="text-gray-700 dark:text-gray-300">Estado:</strong>
                            <span id="estadoOfertas" class="ml-2 text-gray-600 dark:text-gray-400">-</span>
                        </div>
                    </div>
                </div>
                
                <div class="flex gap-4">
                    <button type="button" id="btnEjecutarTodo"
                        class="bg-green-600 hover:bg-green-700 text-white font-semibold px-6 py-3 rounded-md shadow-md transition">
                        ▶️ Ejecutar Todo
                    </button>
                    <button type="button" id="btnPausar" onclick="pausarEjecucion()"
                        class="bg-yellow-600 hover:bg-yellow-700 text-white font-semibold px-6 py-3 rounded-md shadow-md transition hidden">
                        ⏸️ Pausar
                    </button>
                </div>
            </div>
        </div>

        <!-- Resultados de Ejecución -->
        <div id="resultadosEjecucion" class="hidden bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6 border border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-200 mb-4">Resultados de Ejecución</h3>
            
            <!-- Estadísticas -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg">
                    <div class="text-2xl font-bold text-blue-600 dark:text-blue-400" id="totalProcesadas">0</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Procesadas</div>
                </div>
                <div class="bg-green-50 dark:bg-green-900/20 p-4 rounded-lg">
                    <div class="text-2xl font-bold text-green-600 dark:text-green-400" id="totalConPrecio">0</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Con Precio</div>
                </div>
                <div class="bg-red-50 dark:bg-red-900/20 p-4 rounded-lg">
                    <div class="text-2xl font-bold text-red-600 dark:text-red-400" id="totalErrores">0</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Errores</div>
                </div>
                <div class="bg-gray-50 dark:bg-gray-900/20 p-4 rounded-lg">
                    <div class="text-2xl font-bold text-gray-600 dark:text-gray-400" id="tiempoRestante">-</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Tiempo Restante</div>
                </div>
            </div>
            
            <!-- Filtros -->
            <div class="mb-4 flex gap-2">
                <button type="button" id="btnFiltrarTodos" onclick="filtrarResultados('todos')"
                    class="bg-gray-600 hover:bg-gray-700 text-white font-medium px-4 py-2 rounded-md transition">
                    Ver: Todos
                </button>
                <button type="button" id="btnFiltrarConPrecio" onclick="filtrarResultados('con-precio')"
                    class="bg-green-600 hover:bg-green-700 text-white font-medium px-4 py-2 rounded-md transition">
                    Ver: Con Precio
                </button>
                <button type="button" id="btnFiltrarErrores" onclick="filtrarResultados('errores')"
                    class="bg-red-600 hover:bg-red-700 text-white font-medium px-4 py-2 rounded-md transition">
                    Ver: Errores
                </button>
            </div>
            
            <!-- Lista de resultados -->
            <div id="listaResultados" class="space-y-3 max-h-96 overflow-y-auto">
                <!-- Los resultados se mostrarán aquí -->
            </div>
        </div>

    </div>

    <script>
        let ofertasEncontradas = [];
        let ofertasProcesadas = 0;
        let ofertasConPrecio = 0;
        let ofertasConError = 0;
        let tiempoEntrePeticiones = 2;
        let ejecutando = false;
        let pausado = false;

        document.getElementById('btnBuscarOfertas').addEventListener('click', buscarOfertas);
        document.getElementById('btnEjecutarTodo').addEventListener('click', ejecutarTodo);

        function buscarOfertas() {
            const tienda = document.getElementById('tienda').value;
            const mostrar = document.getElementById('mostrar').value;
            const limiteOfertas = document.getElementById('limite_ofertas').value;
            const tiempoEntrePeticionesInput = document.getElementById('tiempo_entre_peticiones').value;
            
            if (!tienda) {
                alert('Por favor, selecciona una tienda.');
                return;
            }
            
            tiempoEntrePeticiones = parseInt(tiempoEntrePeticionesInput);
            
            // Mostrar loading
            document.getElementById('resultadosBusqueda').classList.remove('hidden');
            document.getElementById('loadingBusqueda').classList.remove('hidden');
            document.getElementById('infoOfertas').classList.add('hidden');
            
            // Realizar petición AJAX
            fetch('{{ route("admin.scraping.comprobar-ofertas-api.buscar") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    tienda: tienda,
                    mostrar: mostrar,
                    limite_ofertas: limiteOfertas,
                    tiempo_entre_peticiones: tiempoEntrePeticiones
                })
            })
            .then(response => response.json())
            .then(data => {
                document.getElementById('loadingBusqueda').classList.add('hidden');
                
                if (data.success) {
                    ofertasEncontradas = data.ofertas;
                    mostrarInfoOfertas(data);
                } else {
                    mostrarError('Error al buscar ofertas: ' + (data.error || 'Error desconocido'));
                }
            })
            .catch(error => {
                document.getElementById('loadingBusqueda').classList.add('hidden');
                mostrarError('Error de conexión: ' + error.message);
            });
        }

        function mostrarInfoOfertas(data) {
            document.getElementById('totalOfertas').textContent = data.total;
            document.getElementById('tiendaSeleccionada').textContent = data.tienda;
            document.getElementById('estadoOfertas').textContent = data.mostrar === 'si' ? 'Mostrar: Sí' : 'Mostrar: No';
            
            document.getElementById('infoOfertas').classList.remove('hidden');
        }

        function ejecutarTodo() {
            if (ofertasEncontradas.length === 0) {
                alert('No hay ofertas para procesar.');
                return;
            }
            
            ejecutando = true;
            pausado = false;
            
            // Mostrar controles
            document.getElementById('btnEjecutarTodo').classList.add('hidden');
            document.getElementById('btnPausar').classList.remove('hidden');
            
            // Mostrar resultados
            document.getElementById('resultadosEjecucion').classList.remove('hidden');
            
            // Resetear contadores
            ofertasProcesadas = 0;
            ofertasConPrecio = 0;
            ofertasConError = 0;
            actualizarEstadisticas();
            
            // Limpiar lista de resultados
            document.getElementById('listaResultados').innerHTML = '';
            
            // Iniciar procesamiento
            procesarSiguienteOferta();
        }

        function procesarSiguienteOferta() {
            if (!ejecutando || pausado || ofertasProcesadas >= ofertasEncontradas.length) {
                if (ofertasProcesadas >= ofertasEncontradas.length) {
                    finalizarEjecucion();
                }
                return;
            }
            
            const oferta = ofertasEncontradas[ofertasProcesadas];
            procesarOferta(oferta);
        }

        function procesarOferta(oferta) {
            // Crear elemento en la lista
            const resultadoElement = crearElementoResultado(oferta);
            document.getElementById('listaResultados').appendChild(resultadoElement);
            
            // Crear AbortController para timeout
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 30000); // 30 segundos timeout
            
            // Realizar petición AJAX con timeout
            fetch('{{ route("admin.scraping.comprobar-ofertas-api.procesar") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    oferta_id: oferta.id
                }),
                signal: controller.signal
            })
            .then(response => {
                clearTimeout(timeoutId);
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                return response.json();
            })
            .then(data => {
                ofertasProcesadas++;
                actualizarEstadisticas();
                
                // Actualizar resultado
                actualizarResultado(resultadoElement, data);
                
                if (data.success) {
                    ofertasConPrecio++;
                } else {
                    ofertasConError++;
                }
                
                // Esperar antes de procesar la siguiente
                if (ejecutando && !pausado) {
                    mostrarContadorEspera(resultadoElement);
                    setTimeout(() => {
                        procesarSiguienteOferta();
                    }, tiempoEntrePeticiones * 1000);
                }
            })
            .catch(error => {
                clearTimeout(timeoutId);
                ofertasProcesadas++;
                ofertasConError++;
                actualizarEstadisticas();
                
                let errorMessage = 'Error de conexión: ' + error.message;
                if (error.name === 'AbortError') {
                    errorMessage = 'Timeout: La petición tardó demasiado (30s)';
                }
                
                actualizarResultado(resultadoElement, {
                    success: false,
                    error: errorMessage,
                    oferta_id: oferta.id
                });
                
                if (ejecutando && !pausado) {
                    mostrarContadorEspera(resultadoElement);
                    setTimeout(() => {
                        procesarSiguienteOferta();
                    }, tiempoEntrePeticiones * 1000);
                }
            });
        }

        function crearElementoResultado(oferta) {
            const div = document.createElement('div');
            div.className = 'border border-gray-200 dark:border-gray-600 rounded-lg p-4';
            div.innerHTML = `
                <div class="flex items-center justify-between mb-2">
                    <h4 class="font-semibold text-gray-800 dark:text-gray-200">${oferta.producto?.nombre || 'Sin nombre'}</h4>
                    <div class="flex items-center gap-2">
                        <span class="text-sm text-gray-500 dark:text-gray-400">ID: ${oferta.id}</span>
                        <div id="botones-${oferta.id}" class="hidden flex gap-2">
                            <button onclick="verAvisosOferta(${oferta.id})" 
                                    id="btn-avisos-${oferta.id}"
                                    class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-md transition-colors ${oferta.tiene_avisos ? 'text-yellow-800 bg-yellow-100 hover:bg-yellow-200 dark:bg-yellow-900/20 dark:text-yellow-400 dark:hover:bg-yellow-900/40' : 'text-gray-600 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600'}">
                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                </svg>
                                Avisos
                            </button>
                            <a href="/panel-privado/ofertas/${oferta.id}/edit" target="_blank"
                               class="inline-flex items-center px-2 py-1 text-xs font-medium text-blue-600 bg-blue-100 rounded-md hover:bg-blue-200 dark:bg-blue-900/20 dark:text-blue-400 dark:hover:bg-blue-900/40 transition-colors">
                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                                Editar
                            </a>
                            <button onclick="guardarPrecioYMostrar(${oferta.id})" 
                                    id="btn-guardar-${oferta.id}"
                                    class="inline-flex items-center px-2 py-1 text-xs font-medium text-white bg-green-600 rounded-md hover:bg-green-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                Guardar Mostrar->si
                            </button>
                        </div>
                    </div>
                </div>
                <div class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                    <strong>URL:</strong> <a href="${oferta.url}" target="_blank" class="text-blue-600 dark:text-blue-400 hover:underline break-all">${oferta.url}</a>
                </div>
                <div class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                    <strong>Mostrar:</strong> <span class="font-semibold ${oferta.mostrar === 'si' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'}">${oferta.mostrar === 'si' ? 'Sí' : 'No'}</span>
                </div>
                ${oferta.anotaciones_internas ? `
                <div class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                    <strong>Anotaciones internas:</strong> <span class="text-yellow-600 dark:text-yellow-400">${oferta.anotaciones_internas}</span>
                </div>
                ` : ''}
                <div class="flex items-center justify-between">
                    <div id="estado-${oferta.id}" class="text-sm">
                        <span class="text-blue-600 dark:text-blue-400">⏳ Procesando...</span>
                    </div>
                    <div id="precio-${oferta.id}" class="text-sm font-semibold">
                        <span class="text-gray-500 dark:text-gray-400">Sin precio</span>
                    </div>
                </div>
                <div id="error-${oferta.id}" class="hidden mt-2 text-sm text-red-600 dark:text-red-400"></div>
                <div id="contador-${oferta.id}" class="hidden mt-2 text-sm text-gray-500 dark:text-gray-400"></div>
            `;
            return div;
        }

        function actualizarResultado(elemento, data) {
            const ofertaId = data.oferta_id;
            const estadoElement = document.getElementById(`estado-${ofertaId}`);
            const precioElement = document.getElementById(`precio-${ofertaId}`);
            const errorElement = document.getElementById(`error-${ofertaId}`);
            const botonesElement = document.getElementById(`botones-${ofertaId}`);
            const contadorElement = document.getElementById(`contador-${ofertaId}`);
            
            // Ocultar contador una vez procesado
            if (contadorElement) {
                contadorElement.classList.add('hidden');
            }
            
            if (data.success) {
                estadoElement.innerHTML = '<span class="text-green-600 dark:text-green-400">✅ Éxito</span>';
                precioElement.innerHTML = `<span class="text-green-600 dark:text-green-400">€${parseFloat(data.precio).toFixed(2)}</span>`;
                elemento.classList.add('bg-green-50', 'dark:bg-green-900/20');
                
                // Mostrar botones solo si se obtuvo un precio válido
                if (data.precio && parseFloat(data.precio) > 0) {
                    botonesElement.classList.remove('hidden');
                }
            } else {
                estadoElement.innerHTML = '<span class="text-red-600 dark:text-red-400">❌ Error</span>';
                errorElement.textContent = data.error;
                errorElement.classList.remove('hidden');
                elemento.classList.add('bg-red-50', 'dark:bg-red-900/20');
            }
        }

        function mostrarContadorEspera(elemento) {
            const ofertaId = elemento.querySelector('[id^="contador-"]').id.split('-')[1];
            const contadorElement = document.getElementById(`contador-${ofertaId}`);
            contadorElement.classList.remove('hidden');
            
            let segundos = tiempoEntrePeticiones;
            contadorElement.textContent = `Esperando ${segundos} segundos...`;
            
            const intervalo = setInterval(() => {
                segundos--;
                if (segundos > 0) {
                    contadorElement.textContent = `Esperando ${segundos} segundos...`;
                } else {
                    contadorElement.textContent = 'Procesando siguiente...';
                    clearInterval(intervalo);
                    
                    // Ocultar el contador después de 1 segundo
                    setTimeout(() => {
                        contadorElement.classList.add('hidden');
                    }, 1000);
                }
            }, 1000);
        }

        function actualizarEstadisticas() {
            document.getElementById('totalProcesadas').textContent = ofertasProcesadas;
            document.getElementById('totalConPrecio').textContent = ofertasConPrecio;
            document.getElementById('totalErrores').textContent = ofertasConError;
            
            // Calcular tiempo restante
            const ofertasRestantes = ofertasEncontradas.length - ofertasProcesadas;
            const tiempoRestante = ofertasRestantes * tiempoEntrePeticiones;
            document.getElementById('tiempoRestante').textContent = tiempoRestante > 0 ? `${tiempoRestante}s` : '-';
        }

        function pausarEjecucion() {
            pausado = true;
            document.getElementById('btnPausar').textContent = '▶️ Reanudar';
            document.getElementById('btnPausar').onclick = reanudarEjecucion;
        }

        function reanudarEjecucion() {
            pausado = false;
            document.getElementById('btnPausar').textContent = '⏸️ Pausar';
            document.getElementById('btnPausar').onclick = pausarEjecucion;
            procesarSiguienteOferta();
        }

        function finalizarEjecucion() {
            ejecutando = false;
            document.getElementById('btnEjecutarTodo').classList.remove('hidden');
            document.getElementById('btnPausar').classList.add('hidden');
            
            // Mostrar resumen final
            alert(`Ejecución completada:\n- Procesadas: ${ofertasProcesadas}\n- Con precio: ${ofertasConPrecio}\n- Errores: ${ofertasConError}`);
        }

        function mostrarError(mensaje) {
            alert('Error: ' + mensaje);
        }

        function limpiarTodo() {
            document.getElementById('formConfiguracion').reset();
            document.getElementById('resultadosBusqueda').classList.add('hidden');
            document.getElementById('resultadosEjecucion').classList.add('hidden');
            document.getElementById('infoOfertas').classList.add('hidden');
            document.getElementById('listaResultados').innerHTML = '';
            
            ofertasEncontradas = [];
            ofertasProcesadas = 0;
            ofertasConPrecio = 0;
            ofertasConError = 0;
            ejecutando = false;
            pausado = false;
        }

        function filtrarResultados(tipo) {
            const elementos = document.querySelectorAll('#listaResultados > div');
            
            // Resetear estilos de botones
            document.getElementById('btnFiltrarTodos').classList.remove('bg-gray-800', 'bg-gray-700');
            document.getElementById('btnFiltrarTodos').classList.add('bg-gray-600');
            document.getElementById('btnFiltrarConPrecio').classList.remove('bg-green-800', 'bg-green-700');
            document.getElementById('btnFiltrarConPrecio').classList.add('bg-green-600');
            document.getElementById('btnFiltrarErrores').classList.remove('bg-red-800', 'bg-red-700');
            document.getElementById('btnFiltrarErrores').classList.add('bg-red-600');
            
            elementos.forEach(elemento => {
                let mostrar = true;
                
                if (tipo === 'con-precio') {
                    // Mostrar solo elementos con precio (verde)
                    mostrar = elemento.classList.contains('bg-green-50') || elemento.classList.contains('dark:bg-green-900/20');
                    document.getElementById('btnFiltrarConPrecio').classList.remove('bg-green-600');
                    document.getElementById('btnFiltrarConPrecio').classList.add('bg-green-800');
                } else if (tipo === 'errores') {
                    // Mostrar solo elementos con errores (rojo)
                    mostrar = elemento.classList.contains('bg-red-50') || elemento.classList.contains('dark:bg-red-900/20');
                    document.getElementById('btnFiltrarErrores').classList.remove('bg-red-600');
                    document.getElementById('btnFiltrarErrores').classList.add('bg-red-800');
                } else {
                    // Mostrar todos
                    document.getElementById('btnFiltrarTodos').classList.remove('bg-gray-600');
                    document.getElementById('btnFiltrarTodos').classList.add('bg-gray-800');
                }
                
                if (mostrar) {
                    elemento.classList.remove('hidden');
                } else {
                    elemento.classList.add('hidden');
                }
            });
        }

        function guardarPrecioYMostrar(ofertaId) {
            const boton = document.getElementById(`btn-guardar-${ofertaId}`);
            const precioElement = document.getElementById(`precio-${ofertaId}`);
            
            // Obtener el precio del elemento
            const precioTexto = precioElement.textContent;
            const precio = parseFloat(precioTexto.replace('€', '').replace(',', '.'));
            
            if (!precio || precio <= 0) {
                alert('No hay un precio válido para guardar');
                return;
            }
            
            // Deshabilitar botón y mostrar loading
            boton.disabled = true;
            boton.innerHTML = `
                <svg class="animate-spin w-3 h-3 mr-1" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Guardando...
            `;
            
            // Realizar petición para guardar
            console.log('Enviando datos:', { oferta_id: ofertaId, precio: precio });
            
            fetch('{{ route("admin.scraping.comprobar-ofertas-api.guardar-precio") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    oferta_id: ofertaId,
                    precio: precio
                })
            })
            .then(response => {
                console.log('Respuesta recibida:', response.status, response.statusText);
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('Datos recibidos:', data);
                if (data.success) {
                    // Mostrar éxito
                    boton.innerHTML = `
                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Guardado ✓
                    `;
                    boton.classList.remove('bg-green-600', 'hover:bg-green-700');
                    boton.classList.add('bg-green-500');
                    
                    // Actualizar el estado de mostrar en la interfaz
                    const ofertaElement = boton.closest('.border');
                    const mostrarElements = ofertaElement.querySelectorAll('span');
                    mostrarElements.forEach(span => {
                        if (span.textContent.includes('Mostrar:')) {
                            span.innerHTML = '<strong>Mostrar:</strong> <span class="font-semibold text-green-600 dark:text-green-400">Sí</span>';
                        }
                    });
                } else {
                    alert('Error al guardar: ' + (data.error || 'Error desconocido'));
                    boton.disabled = false;
                    boton.innerHTML = `
                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Guardar Mostrar->si
                    `;
                }
            })
            .catch(error => {
                console.error('Error completo:', error);
                alert('Error de conexión al guardar: ' + error.message);
                boton.disabled = false;
                boton.innerHTML = `
                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    Guardar precio y Mostrar si
                `;
            });
        }

        // Función para ver avisos de una oferta
        function verAvisosOferta(ofertaId) {
            // Mostrar loading en el botón
            const botonAvisos = document.getElementById(`btn-avisos-${ofertaId}`);
            const originalContent = botonAvisos.innerHTML;
            botonAvisos.innerHTML = `
                <svg class="animate-spin w-3 h-3 mr-1" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Cargando...
            `;
            botonAvisos.disabled = true;

            // Realizar petición para obtener avisos
            fetch('{{ route("admin.scraping.comprobar-ofertas-api.avisos") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    oferta_id: ofertaId
                })
            })
            .then(response => response.json())
            .then(data => {
                // Restaurar botón
                botonAvisos.innerHTML = originalContent;
                botonAvisos.disabled = false;

                if (data.success) {
                    mostrarModalAvisos(data.avisos, data.producto_nombre, ofertaId);
                } else {
                    alert('Error al cargar avisos: ' + (data.error || 'Error desconocido'));
                }
            })
            .catch(error => {
                // Restaurar botón
                botonAvisos.innerHTML = originalContent;
                botonAvisos.disabled = false;
                
                console.error('Error:', error);
                alert('Error de conexión al cargar avisos: ' + error.message);
            });
        }

        // Función para mostrar modal de avisos
        function mostrarModalAvisos(avisos, productoNombre, ofertaId) {
            const modal = document.getElementById('modal-avisos-oferta');
            const titulo = document.getElementById('modal-avisos-titulo');
            const listaAvisos = document.getElementById('lista-avisos-oferta');
            const sinAvisos = document.getElementById('sin-avisos-oferta');

            // Actualizar título
            titulo.textContent = `Avisos - ${productoNombre} (ID: ${ofertaId})`;

            if (avisos.length === 0) {
                listaAvisos.classList.add('hidden');
                sinAvisos.classList.remove('hidden');
            } else {
                sinAvisos.classList.add('hidden');
                listaAvisos.classList.remove('hidden');
                
                // Generar HTML de avisos
                listaAvisos.innerHTML = avisos.map(aviso => `
                    <div class="p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <p class="text-sm text-gray-800 dark:text-gray-200">${aviso.texto_aviso}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            ${new Date(aviso.fecha_aviso).toLocaleString('es-ES')} - ${aviso.user.name}
                        </p>
                    </div>
                `).join('');
            }

            // Mostrar modal
            modal.classList.remove('hidden');
        }

        // Función para cerrar modal de avisos
        function cerrarModalAvisos() {
            document.getElementById('modal-avisos-oferta').classList.add('hidden');
        }
    </script>

    {{-- Modal para mostrar avisos de una oferta --}}
    <div id="modal-avisos-oferta" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl border border-gray-200 dark:border-gray-700 w-full max-w-2xl max-h-[80vh] overflow-hidden">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="p-2 bg-yellow-100 dark:bg-yellow-900 rounded-lg">
                            <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                            </svg>
                        </div>
                        <h2 id="modal-avisos-titulo" class="ml-3 text-lg font-medium text-gray-900 dark:text-white">Avisos</h2>
                    </div>
                    <button onclick="cerrarModalAvisos()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>
            <div class="p-6 overflow-y-auto max-h-[60vh]">
                <div id="lista-avisos-oferta" class="space-y-3">
                    {{-- Los avisos se cargarán aquí dinámicamente --}}
                </div>
                <div id="sin-avisos-oferta" class="text-center py-8 text-gray-500 dark:text-gray-400">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                    <p class="mt-2 text-sm">No hay avisos para esta oferta</p>
                </div>
            </div>
            <div class="p-6 border-t border-gray-200 dark:border-gray-700">
                <button onclick="cerrarModalAvisos()" 
                    class="w-full flex items-center justify-center px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600 transition-colors">
                    Cerrar
                </button>
            </div>
        </div>
    </div>

    <script>
        // Cerrar modal al hacer clic fuera
        document.getElementById('modal-avisos-oferta').addEventListener('click', function(e) {
            if (e.target === this) {
                cerrarModalAvisos();
            }
        });

        // Cerrar modal con Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                cerrarModalAvisos();
            }
        });
    </script>
</x-app-layout>
