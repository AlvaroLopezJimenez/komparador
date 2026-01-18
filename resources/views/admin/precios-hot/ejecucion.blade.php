<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.dashboard') }}">
                <h2 class="font-semibold text-xl text-white leading-tight">Inicio -></h2>
            </a>
            <a href="{{ route('admin.categorias.index') }}">
                <h2 class="font-semibold text-xl text-white leading-tight">Categorías -></h2>
            </a>
            <h2 class="font-semibold text-xl text-white leading-tight">Ejecución Precios Hot</h2>
        </div>
    </x-slot>

    <div class="max-w-6xl mx-auto py-10 px-4 space-y-8 bg-gray-50 dark:bg-gray-900 rounded-lg shadow-md">
        
        <!-- Panel de Control -->
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6 border border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-200 mb-4">Panel de Control</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg">
                    <div class="text-2xl font-bold text-blue-600 dark:text-blue-400" id="totalCategorias">0</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Total Categorías</div>
                </div>
                <div class="bg-green-50 dark:bg-green-900/20 p-4 rounded-lg">
                    <div class="text-2xl font-bold text-green-600 dark:text-green-400" id="inserciones">0</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Inserciones</div>
                </div>
                <div class="bg-red-50 dark:bg-red-900/20 p-4 rounded-lg">
                    <div class="text-2xl font-bold text-red-600 dark:text-red-400" id="errores">0</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Errores</div>
                </div>
            </div>

            <div class="flex gap-4">
                <button id="btnIniciar" onclick="iniciarEjecucion()" 
                    class="bg-green-600 hover:bg-green-700 text-white font-semibold px-6 py-3 rounded-md shadow-md transition">
                    Iniciar Ejecución
                </button>
                <button id="btnDetener" onclick="detenerEjecucion()" disabled
                    class="bg-red-600 hover:bg-red-700 text-white font-semibold px-6 py-3 rounded-md shadow-md transition disabled:opacity-50 disabled:cursor-not-allowed">
                    Detener
                </button>
                <button onclick="limpiarLog()" 
                    class="bg-gray-600 hover:bg-gray-700 text-white font-semibold px-6 py-3 rounded-md shadow-md transition">
                    Limpiar Log
                </button>
            </div>
        </div>

        <!-- Barra de Progreso -->
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6 border border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-200 mb-4">Progreso</h3>
            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-4">
                <div id="barraProgreso" class="bg-blue-600 h-4 rounded-full transition-all duration-300" style="width: 0%"></div>
            </div>
            <div class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                <span id="progresoTexto">0%</span> completado
            </div>
        </div>

        <!-- Log en Tiempo Real -->
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6 border border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-200 mb-4">Log en Tiempo Real</h3>
            <div id="logContainer" class="bg-gray-100 dark:bg-gray-700 rounded-lg p-4 h-96 overflow-y-auto font-mono text-sm">
                <div class="text-gray-500 dark:text-gray-400">Esperando inicio de ejecución...</div>
            </div>
        </div>

    </div>

    <script>
        let ejecucionActiva = false;
        let totalCategorias = 0;
        let procesadas = 0;
        let inserciones = 0;
        let errores = 0;
        let pollingInterval = null;

        function iniciarEjecucion() {
            if (ejecucionActiva) return;

            ejecucionActiva = true;
            document.getElementById('btnIniciar').disabled = true;
            document.getElementById('btnDetener').disabled = false;
            
            // Resetear contadores
            totalCategorias = 0;
            procesadas = 0;
            inserciones = 0;
            errores = 0;
            actualizarContadores();
            
            agregarLog('🚀 Iniciando ejecución de precios hot...', 'info');
            
            // Iniciar ejecución real en el servidor
            iniciarEjecucionServidor();
        }

        function detenerEjecucion() {
            ejecucionActiva = false;
            document.getElementById('btnIniciar').disabled = false;
            document.getElementById('btnDetener').disabled = true;
            
            if (pollingInterval) {
                clearInterval(pollingInterval);
                pollingInterval = null;
            }
            
            agregarLog('⏹️ Ejecución detenida por el usuario', 'warning');
        }

        function iniciarEjecucionServidor() {
            // Usar el token recibido desde el backend
            const token = "{{ $tokenScraper }}";
            
            fetch('/admin/precios-hot/ejecutar-segundo-plano?token=' + token, {
                method: 'GET',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'ok') {
                    agregarLog('✅ Ejecución iniciada en el servidor', 'success');
                    agregarLog(`📊 Total de categorías procesadas: ${data.total_categorias}`, 'info');
                    
                    totalCategorias = data.total_categorias;
                    inserciones = data.total_inserciones;
                    errores = data.total_errores;
                    procesadas = totalCategorias;
                    
                    actualizarContadores();
                    actualizarProgreso();
                    
                    agregarLog('🎉 Ejecución completada', 'success');
                    agregarLog(`📈 Resumen final: ${inserciones} inserciones, ${errores} errores`, 'info');
                    
                    ejecucionActiva = false;
                    document.getElementById('btnIniciar').disabled = false;
                    document.getElementById('btnDetener').disabled = true;
                } else {
                    agregarLog('❌ Error al iniciar ejecución: ' + (data.message || 'Error desconocido'), 'error');
                    detenerEjecucion();
                }
            })
            .catch(error => {
                agregarLog('❌ Error de conexión: ' + error.message, 'error');
                detenerEjecucion();
            });
        }

        function actualizarContadores() {
            document.getElementById('totalCategorias').textContent = totalCategorias;
            document.getElementById('inserciones').textContent = inserciones;
            document.getElementById('errores').textContent = errores;
        }

        function actualizarProgreso() {
            const porcentaje = totalCategorias > 0 ? (procesadas / totalCategorias) * 100 : 0;
            document.getElementById('barraProgreso').style.width = porcentaje + '%';
            document.getElementById('progresoTexto').textContent = Math.round(porcentaje) + '%';
        }

        function agregarLog(mensaje, tipo = 'info') {
            const container = document.getElementById('logContainer');
            const timestamp = new Date().toLocaleTimeString();
            
            let icono = 'ℹ️';
            let clase = 'text-gray-700 dark:text-gray-300';
            
            switch(tipo) {
                case 'success':
                    icono = '✅';
                    clase = 'text-green-600 dark:text-green-400';
                    break;
                case 'error':
                    icono = '❌';
                    clase = 'text-red-600 dark:text-red-400';
                    break;
                case 'warning':
                    icono = '⚠️';
                    clase = 'text-yellow-600 dark:text-yellow-400';
                    break;
            }
            
            const logEntry = document.createElement('div');
            logEntry.className = `mb-1 ${clase}`;
            logEntry.innerHTML = `<span class="text-gray-500 dark:text-gray-400">[${timestamp}]</span> ${icono} ${mensaje}`;
            
            container.appendChild(logEntry);
            container.scrollTop = container.scrollHeight;
        }

        function limpiarLog() {
            document.getElementById('logContainer').innerHTML = '<div class="text-gray-500 dark:text-gray-400">Log limpiado...</div>';
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