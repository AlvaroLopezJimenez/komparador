<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.dashboard') }}">
                <h2 class="font-semibold text-xl text-white leading-tight">Inicio -></h2>
            </a>
            <h2 class="font-semibold text-xl text-white leading-tight">Ejecucion en tiempo real Scraper</h2>
        </div>
    </x-slot>
    <div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6 border border-gray-200 dark:border-gray-700 mb-6">
            <h1 class="text-2xl font-bold text-gray-800 dark:text-white mb-2">
                ⚡ Ejecución Scraper en Tiempo Real
            </h1>
            <p class="text-gray-600 dark:text-gray-300">
                Ejecuta el scraper de ofertas con visualización en tiempo real del progreso
            </p>
        </div>

        <!-- Controles principales -->
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6 border border-gray-200 dark:border-gray-700 mb-6">
            <div class="flex flex-wrap gap-4 items-center">
                <button id="btnIniciar" onclick="iniciarEjecucion()" 
                        class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg font-medium">
                    🚀 Iniciar Ejecución
                </button>
                
                <button id="btnDetener" onclick="detenerEjecucion()" 
                        class="bg-red-600 hover:bg-red-700 text-white px-6 py-2 rounded-lg font-medium hidden">
                    ⏹️ Detener Ejecución
                </button>
                
                <button onclick="ocultarPanel()" 
                        class="bg-gray-600 hover:bg-gray-700 text-white px-6 py-2 rounded-lg font-medium">
                    👁️ Ocultar Panel
                </button>
                
                <div class="ml-auto">
                    <a href="{{ route('admin.ofertas.scraper.ejecuciones') }}" 
                       class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-medium">
                        📋 Ver Historial
                    </a>
                </div>
            </div>
        </div>

        <!-- Panel de oferta en procesamiento -->
        <div id="panelOfertaActual" class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6 border border-gray-200 dark:border-gray-700 mb-6 hidden">
            <div class="flex justify-between items-start mb-4">
                <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-200">
                    📋 Oferta en Procesamiento
                </h3>
                <button onclick="ocultarPanel()" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                    ✕
                </button>
            </div>
            
            <div id="ofertaActualContent">
                <div class="text-center py-8">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto mb-4"></div>
                    <p class="text-gray-600 dark:text-gray-300">Iniciando procesamiento...</p>
                </div>
            </div>
        </div>

        <!-- Estadísticas en tiempo real -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-4 border border-gray-200 dark:border-gray-700">
                <div class="text-center">
                    <div class="text-2xl font-bold text-blue-600" id="contadorTotal">0</div>
                    <div class="text-sm text-gray-600 dark:text-gray-300">Total Ofertas</div>
                </div>
            </div>
            
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-4 border border-gray-200 dark:border-gray-700">
                <div class="text-center">
                    <div class="text-2xl font-bold text-green-600" id="contadorActualizadas">0</div>
                    <div class="text-sm text-gray-600 dark:text-gray-300">Actualizadas</div>
                </div>
            </div>
            
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-4 border border-gray-200 dark:border-gray-700">
                <div class="text-center">
                    <div class="text-2xl font-bold text-red-600" id="contadorErrores">0</div>
                    <div class="text-sm text-gray-600 dark:text-gray-300">Errores</div>
                </div>
            </div>
            
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-4 border border-gray-200 dark:border-gray-700">
                <div class="text-center">
                    <div class="text-2xl font-bold text-purple-600" id="contadorProcesadas">0</div>
                    <div class="text-sm text-gray-600 dark:text-gray-300">Procesadas</div>
                </div>
            </div>
        </div>

        <!-- Log en tiempo real -->
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6 border border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-200 mb-4">
                📋 Log en Tiempo Real
            </h3>
            
            <div id="logContainer" class="bg-gray-100 dark:bg-gray-900 rounded-lg p-4 h-96 overflow-y-auto font-mono text-sm">
                <div class="text-gray-500 dark:text-gray-400">
                    Esperando inicio de ejecución...
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let ejecucionId = null;
let indiceActual = 0;
let totalOfertas = 0;
let ejecutando = false;
let abortController = null;

function iniciarEjecucion() {
    if (ejecutando) return;
    
    ejecutando = true;
    document.getElementById('btnIniciar').classList.add('hidden');
    document.getElementById('btnDetener').classList.remove('hidden');
    
    mostrarPanelOfertaActual();
    agregarLog('🚀 Iniciando ejecución de scraping en tiempo real...');
    
    // Crear AbortController para timeout
    abortController = new AbortController();
    const timeoutId = setTimeout(() => abortController.abort(), 120000); // 2 minutos
    
    fetch('{{ route("admin.scraping.ejecucion-tiempo-real.iniciar") }}', {
        method: 'GET',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json',
        },
        signal: abortController.signal
    })
    .then(response => response.json())
    .then(data => {
        clearTimeout(timeoutId);
        
        if (data.success) {
            ejecucionId = data.ejecucion_id;
            totalOfertas = data.total_ofertas;
            indiceActual = 0;
            
            actualizarContadores(0, 0, 0, totalOfertas);
            agregarLog('✅ Ejecución iniciada en el servidor');
            agregarLog(`📊 Total de ofertas a procesar: ${totalOfertas}`);
            
                         if (data.completada) {
                 // No hay ofertas elegibles, ejecución completada inmediatamente
                 agregarLog('✅ 🎉 Ejecución completada (no hay ofertas elegibles)');
                 agregarLog(`📈 Resumen final: ${data.total_guardado} actualizadas, ${data.total_errores} errores`);
                 detenerEjecucion(false, false);
             } else {
                procesarSiguienteOferta();
            }
        } else {
            agregarLog(`❌ Error al iniciar: ${data.message}`);
            detenerEjecucion();
        }
    })
    .catch(error => {
        clearTimeout(timeoutId);
        if (error.name === 'AbortError') {
            agregarLog('❌ Timeout: La ejecución tardó demasiado en iniciar');
        } else {
            agregarLog(`❌ Error de conexión: ${error.message}`);
        }
        detenerEjecucion();
    });
}

function procesarSiguienteOferta() {
    if (!ejecutando) {
        return;
    }
    
    // Si hemos procesado todas las ofertas, enviar la petición final al servidor
    if (indiceActual >= totalOfertas) {
        enviarPeticionFinal();
        return;
    }
    
    mostrarEstadoProcesamiento();
    
    // Crear AbortController para timeout
    abortController = new AbortController();
    const timeoutId = setTimeout(() => abortController.abort(), 120000); // 2 minutos
    
    fetch('{{ route("admin.scraping.ejecucion-tiempo-real.procesar-siguiente") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json',
            'Accept': 'application/json',
        },
        body: JSON.stringify({
            ejecucion_id: ejecucionId,
            indice_actual: indiceActual
        }),
        signal: abortController.signal
    })
    .then(response => response.json())
    .then(data => {
        clearTimeout(timeoutId);
        
                 if (data.success) {
             if (data.completada) {
                 agregarLog('✅ 🎉 Ejecución completada');
                 agregarLog(`📈 Resumen final: ${data.total_guardado} actualizadas, ${data.total_errores} errores`);
                 detenerEjecucion(false, false);
                 return;
             }
            
            // Mostrar resultado de la oferta actual
            mostrarOfertaActual(data.oferta_actual);
            
            // Actualizar contadores
            if (data.progreso) {
                actualizarContadores(
                    data.progreso.actualizadas,
                    data.progreso.errores,
                    data.progreso.procesadas,
                    totalOfertas
                );
            }
            
            // Agregar log del resultado
            if (data.oferta_actual.resultado) {
                const resultado = data.oferta_actual.resultado;
                if (resultado.success) {
                    agregarLog(`✅ Oferta ${resultado.oferta_id} (${data.oferta_actual.nombre}): Precio actualizado de ${resultado.precio_anterior}€ a ${resultado.precio_nuevo}€`);
                    if (resultado.cambios_detectados) {
                        agregarLog(`🔄 Cambios detectados en el producto`);
                    }
                } else {
                    agregarLog(`❌ Oferta ${resultado.oferta_id} (${data.oferta_actual.nombre}): ${resultado.error}`);
                }
            }
            
                         indiceActual++;
             
             // Procesar siguiente oferta después de un delay
             setTimeout(() => {
                 procesarSiguienteOferta();
             }, 2000);
            
        } else {
            agregarLog(`❌ Error en el procesamiento: ${data.error}`);
            detenerEjecucion();
        }
    })
    .catch(error => {
        clearTimeout(timeoutId);
        if (error.name === 'AbortError') {
            agregarLog('❌ Timeout: La ejecución tardó demasiado');
        } else {
            agregarLog(`❌ Error de conexión: ${error.message}`);
        }
        detenerEjecucion();
    });
}

function detenerEjecucion(ocultarPanel = true, detencionManual = true) {
    ejecutando = false;
    document.getElementById('btnIniciar').classList.remove('hidden');
    document.getElementById('btnDetener').classList.add('hidden');
    
    if (abortController) {
        abortController.abort();
        abortController = null;
    }
    
    if (ocultarPanel) {
        document.getElementById('panelOfertaActual').classList.add('hidden');
    }
    
    // Solo mostrar el mensaje si fue detención manual del usuario
    if (detencionManual) {
        agregarLog('⚠️ ⏹️ Ejecución detenida por el usuario');
    }
}

function mostrarPanelOfertaActual() {
    document.getElementById('panelOfertaActual').classList.remove('hidden');
    document.getElementById('ofertaActualContent').innerHTML = `
        <div class="text-center py-8">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto mb-4"></div>
            <p class="text-gray-600 dark:text-gray-300">Iniciando procesamiento...</p>
        </div>
    `;
}

function mostrarEstadoProcesamiento() {
    const porcentaje = totalOfertas > 0 ? Math.round((indiceActual / totalOfertas) * 100) : 0;
    const actualizadas = document.getElementById('contadorActualizadas').textContent;
    const errores = document.getElementById('contadorErrores').textContent;
    
    document.getElementById('ofertaActualContent').innerHTML = `
        <div class="text-center py-6">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto mb-4"></div>
            <p class="text-gray-800 dark:text-gray-200 font-medium mb-2">
                Procesando oferta ${indiceActual + 1} de ${totalOfertas} (${porcentaje}%)
            </p>
            <p class="text-sm text-gray-600 dark:text-gray-300">
                Actualizadas: ${actualizadas} | Errores: ${errores}
            </p>
        </div>
    `;
}

function mostrarOfertaActual(oferta) {
    if (!oferta || !oferta.resultado) return;
    
    const resultado = oferta.resultado;
    const statusClass = resultado.success ? 'text-green-600' : 'text-red-600';
    const statusIcon = resultado.success ? '✅' : '❌';
    
    document.getElementById('ofertaActualContent').innerHTML = `
        <div class="space-y-3">
            <div class="flex justify-between items-center">
                <span class="font-medium text-gray-800 dark:text-gray-200">ID: ${resultado.oferta_id}</span>
                <span class="${statusClass} font-medium">${statusIcon} ${resultado.success ? 'Éxito' : 'Error'}</span>
            </div>
            
            <div class="text-sm text-gray-600 dark:text-gray-300">
                <p><strong>Producto:</strong> ${oferta.nombre}</p>
                <p><strong>Tienda:</strong> ${resultado.tienda_nombre}</p>
                <p><strong>URL:</strong> <a href="${resultado.url}" target="_blank" class="text-blue-600 hover:underline">Ver oferta</a></p>
            </div>
            
            <div class="grid grid-cols-2 gap-4">
                <div class="bg-gray-50 dark:bg-gray-700 p-3 rounded">
                    <p class="text-xs text-gray-500 dark:text-gray-400">Precio Anterior</p>
                    <p class="font-medium">${resultado.precio_anterior !== null ? resultado.precio_anterior + '€' : 'N/A'}</p>
                </div>
                <div class="bg-gray-50 dark:bg-gray-700 p-3 rounded">
                    <p class="text-xs text-gray-500 dark:text-gray-400">Precio Nuevo</p>
                    <p class="font-medium">${resultado.precio_nuevo !== null ? resultado.precio_nuevo + '€' : 'N/A'}</p>
                </div>
            </div>
            
            ${resultado.error ? `
                <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 p-3 rounded">
                    <p class="text-sm text-red-700 dark:text-red-300"><strong>Error:</strong> ${resultado.error}</p>
                </div>
            ` : ''}
            
            ${resultado.cambios_detectados ? `
                <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 p-3 rounded">
                    <p class="text-sm text-yellow-700 dark:text-yellow-300">🔄 Cambios detectados en el producto</p>
                </div>
            ` : ''}
        </div>
    `;
}

function ocultarPanel() {
    document.getElementById('panelOfertaActual').classList.add('hidden');
}

function actualizarContadores(actualizadas, errores, procesadas, total) {
    document.getElementById('contadorTotal').textContent = total;
    document.getElementById('contadorActualizadas').textContent = actualizadas;
    document.getElementById('contadorErrores').textContent = errores;
    document.getElementById('contadorProcesadas').textContent = procesadas;
}

function agregarLog(mensaje) {
    const logContainer = document.getElementById('logContainer');
    const timestamp = new Date().toLocaleTimeString('es-ES', { 
        hour: '2-digit', 
        minute: '2-digit', 
        second: '2-digit' 
    });
    
    const logEntry = document.createElement('div');
    logEntry.className = 'mb-1';
    logEntry.innerHTML = `<span class="text-gray-500">[${timestamp}]</span> ${mensaje}`;
    
    logContainer.appendChild(logEntry);
    logContainer.scrollTop = logContainer.scrollHeight;
}



function enviarPeticionFinal() {
    // Crear AbortController para timeout
    abortController = new AbortController();
    const timeoutId = setTimeout(() => abortController.abort(), 120000); // 2 minutos
    
    fetch('{{ route("admin.scraping.ejecucion-tiempo-real.procesar-siguiente") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json',
            'Accept': 'application/json',
        },
        body: JSON.stringify({
            ejecucion_id: ejecucionId,
            indice_actual: indiceActual
        }),
        signal: abortController.signal
    })
    .then(response => response.json())
    .then(data => {
        clearTimeout(timeoutId);
        
                 if (data.success && data.completada) {
             agregarLog('✅ 🎉 Ejecución completada en el servidor');
             agregarLog(`📈 Resumen final: ${data.total_guardado} actualizadas, ${data.total_errores} errores`);
             detenerEjecucion(false, false);
         } else {
            agregarLog('❌ Error: No se pudo marcar la ejecución como completada');
            detenerEjecucion();
        }
    })
    .catch(error => {
        clearTimeout(timeoutId);
        if (error.name === 'AbortError') {
            agregarLog('❌ Timeout: La petición final tardó demasiado');
        } else {
            agregarLog(`❌ Error de conexión: ${error.message}`);
        }
        detenerEjecucion();
    });
}


</script>
</x-app-layout>
