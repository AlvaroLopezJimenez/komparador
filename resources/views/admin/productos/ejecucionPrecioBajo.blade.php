<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.dashboard') }}">
                <h2 class="font-semibold text-xl text-white leading-tight">Panel -></h2>
            </a>
            <a href="{{ route('admin.productos.index') }}">
                <h2 class="font-semibold text-xl text-white leading-tight">Productos -></h2>
            </a>
            <h2 class="font-semibold text-xl text-white leading-tight">Guardar Precio Más Bajo</h2>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold mb-4">Ejecución en Tiempo Real - Guardar Precio Más Bajo</h3>
                        <p class="text-gray-600 dark:text-gray-400 mb-4">
                            Este proceso buscará la oferta con el precio por unidad más bajo de cada producto y actualizará el precio del producto si es diferente.
                        </p>
                    </div>

                    <div class="mb-6">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-medium">Progreso</span>
                            <span id="porcentaje" class="text-sm font-medium">0%</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2.5 dark:bg-gray-700">
                            <div id="barra-progreso" class="bg-blue-600 h-2.5 rounded-full transition-all duration-300" style="width: 0%"></div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                        <div class="bg-blue-50 dark:bg-blue-900 p-4 rounded-lg">
                            <div class="text-2xl font-bold text-blue-600 dark:text-blue-400" id="total-productos">0</div>
                            <div class="text-sm text-blue-600 dark:text-blue-400">Total Productos</div>
                        </div>
                        <div class="bg-green-50 dark:bg-green-900 p-4 rounded-lg">
                            <div class="text-2xl font-bold text-green-600 dark:text-green-400" id="precios-actualizados">0</div>
                            <div class="text-sm text-green-600 dark:text-green-400">Precios Actualizados</div>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
                            <div class="text-2xl font-bold text-gray-600 dark:text-gray-400" id="productos-procesados">0</div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">Productos Procesados</div>
                        </div>
                    </div>

                    <div class="mb-6">
                        <div class="flex gap-2 mb-4">
                            <button id="btn-iniciar" onclick="iniciarProceso()" 
                                class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded">
                                ▶ Iniciar Proceso
                            </button>
                            <button id="btn-detener" onclick="detenerProceso()" 
                                class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded hidden">
                                ⏹ Detener Proceso
                            </button>
                        </div>
                    </div>

                    <div class="mb-6">
                        <h4 class="font-semibold mb-2">Log de Ejecución</h4>
                        <div id="log-container" class="bg-gray-100 dark:bg-gray-700 p-4 rounded-lg h-64 overflow-y-auto font-mono text-sm">
                            <div class="text-gray-500">Esperando inicio del proceso...</div>
                        </div>
                    </div>

                    <div class="text-center">
                        <a href="{{ route('admin.dashboard') }}" 
                            class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded">
                            ← Volver al Panel
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let procesoActivo = false;
        let totalProductos = 0;
        let preciosActualizados = 0;
        let productosProcesados = 0;

        function iniciarProceso() {
            if (procesoActivo) return;
            
            procesoActivo = true;
            document.getElementById('btn-iniciar').classList.add('hidden');
            document.getElementById('btn-detener').classList.remove('hidden');
            
            // Resetear contadores
            totalProductos = 0;
            preciosActualizados = 0;
            productosProcesados = 0;
            actualizarContadores();
            
            agregarLog('🚀 Iniciando proceso de guardar precio más bajo...', 'info');
            
            procesarProductos();
        }

        function detenerProceso() {
            procesoActivo = false;
            document.getElementById('btn-iniciar').classList.remove('hidden');
            document.getElementById('btn-detener').classList.add('hidden');
            agregarLog('⏹ Proceso detenido por el usuario', 'warning');
        }

        async function procesarProductos() {
            try {
                const res = await fetch("{{ route('admin.productos.precio-bajo.procesar') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        accion: 'iniciar'
                    })
                });

                if (!res.ok) {
                    throw new Error('Error en la respuesta del servidor');
                }

                const data = await res.json();
                
                if (data.success) {
                    totalProductos = data.total_productos;
                    actualizarContadores();
                    agregarLog(`📊 Total de productos a procesar: ${totalProductos}`, 'info');
                    
                    // Procesar productos uno por uno
                    await procesarProductoIndividual();
                } else {
                    agregarLog(`❌ Error: ${data.message}`, 'error');
                    detenerProceso();
                }
            } catch (error) {
                agregarLog(`❌ Error de conexión: ${error.message}`, 'error');
                detenerProceso();
            }
        }

        async function procesarProductoIndividual() {
            if (!procesoActivo) return;

            try {
                const res = await fetch("{{ route('admin.productos.precio-bajo.procesar') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        accion: 'procesar_producto'
                    })
                });

                if (!res.ok) {
                    throw new Error('Error en la respuesta del servidor');
                }

                const data = await res.json();
                
                if (data.success) {
                    if (data.producto_procesado) {
                        productosProcesados++;
                        
                        if (data.precio_actualizado) {
                            preciosActualizados++;
                            agregarLog(`✅ Producto "${data.nombre_producto}": Precio actualizado de ${data.precio_anterior}€ a ${data.precio_nuevo}€`, 'success');
                        } else {
                            agregarLog(`ℹ️ Producto "${data.nombre_producto}": Precio ya actualizado (${data.precio_actual}€)`, 'info');
                        }
                        
                        actualizarContadores();
                        actualizarProgreso();
                    }
                    
                    if (data.finalizado) {
                        agregarLog('🎉 ¡Proceso completado!', 'success');
                        agregarLog(`📊 Resumen: ${preciosActualizados} precios actualizados de ${totalProductos} productos procesados`, 'info');
                        detenerProceso();
                    } else {
                        // Continuar con el siguiente producto
                        setTimeout(() => procesarProductoIndividual(), 100);
                    }
                } else {
                    agregarLog(`❌ Error: ${data.message}`, 'error');
                    detenerProceso();
                }
            } catch (error) {
                agregarLog(`❌ Error de conexión: ${error.message}`, 'error');
                detenerProceso();
            }
        }

        function actualizarContadores() {
            document.getElementById('total-productos').textContent = totalProductos;
            document.getElementById('precios-actualizados').textContent = preciosActualizados;
            document.getElementById('productos-procesados').textContent = productosProcesados;
        }

        function actualizarProgreso() {
            if (totalProductos === 0) return;
            
            const porcentaje = Math.round((productosProcesados / totalProductos) * 100);
            document.getElementById('porcentaje').textContent = `${porcentaje}%`;
            document.getElementById('barra-progreso').style.width = `${porcentaje}%`;
        }

        function agregarLog(mensaje, tipo = 'info') {
            const logContainer = document.getElementById('log-container');
            const timestamp = new Date().toLocaleTimeString();
            
            let colorClase = 'text-gray-700 dark:text-gray-300';
            if (tipo === 'success') colorClase = 'text-green-600 dark:text-green-400';
            else if (tipo === 'error') colorClase = 'text-red-600 dark:text-red-400';
            else if (tipo === 'warning') colorClase = 'text-yellow-600 dark:text-yellow-400';
            
            const logEntry = document.createElement('div');
            logEntry.className = `mb-1 ${colorClase}`;
            logEntry.textContent = `[${timestamp}] ${mensaje}`;
            
            logContainer.appendChild(logEntry);
            logContainer.scrollTop = logContainer.scrollHeight;
        }
    </script>
</x-app-layout>
