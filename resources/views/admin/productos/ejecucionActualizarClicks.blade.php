<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.dashboard') }}">
                <h2 class="font-semibold text-xl text-white leading-tight">
                    Inicio ->
                </h2>
            </a>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Productos
            </h2>
        </div>
    </x-slot>

<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900">
                <h2 class="text-2xl font-bold mb-6">Actualizar Clicks de Productos</h2>
                
                <div class="mb-6">
                    <p class="text-gray-600 mb-4">
                        Esta función actualiza el campo 'clicks' de todos los productos con el total de clicks 
                        recibidos en el último mes para cada producto.
                    </p>
                </div>

                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                    <h3 class="font-semibold text-blue-800 mb-2">Información:</h3>
                    <ul class="text-sm text-blue-700 space-y-1">
                        <li>• Se contarán los clicks de las ofertas de cada producto en el último mes</li>
                        <li>• El resultado se guardará en el campo 'clicks' de cada producto</li>
                        <li>• Los productos se ordenarán por clicks en las búsquedas</li>
                        <li>• El proceso puede tardar varios minutos dependiendo del número de productos</li>
                    </ul>
                </div>

                <div class="flex space-x-4">
                    <button id="btnEjecutar" 
                            class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                        Ejecutar Actualización
                    </button>
                    <a href="{{ route('admin.productos.actualizar.clicks.ejecuciones') }}" 
                       class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                        Ver Historial
                    </a>
                </div>

                <!-- MODAL DE EJECUCIÓN -->
                <div id="modalEjecucion" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
                    <div class="flex items-center justify-center min-h-screen">
                        <div class="bg-white rounded-lg p-6 max-w-2xl w-full mx-4">
                            <h3 class="text-lg font-semibold mb-4">Actualizando Clicks de Productos</h3>
                            
                            <div class="mb-4">
                                <div class="flex justify-between items-center mb-2">
                                    <span class="text-sm font-medium text-gray-700">Progreso:</span>
                                    <span id="progreso" class="text-sm text-gray-600">0%</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    <div id="barraProgreso" class="bg-blue-600 h-2 rounded-full transition-all duration-300" style="width: 0%"></div>
                                </div>
                            </div>

                            <div class="mb-4">
                                <div class="flex justify-between text-sm text-gray-600 mb-1">
                                    <span>Productos procesados:</span>
                                    <span id="contadorProcesados">0</span>
                                </div>
                                <div class="flex justify-between text-sm text-gray-600 mb-1">
                                    <span>Actualizados correctamente:</span>
                                    <span id="contadorActualizados" class="text-green-600">0</span>
                                </div>
                                <div class="flex justify-between text-sm text-gray-600">
                                    <span>Errores:</span>
                                    <span id="contadorErrores" class="text-red-600">0</span>
                                </div>
                            </div>

                            <div id="logContainer" class="max-h-60 overflow-y-auto bg-gray-100 p-3 rounded text-sm font-mono">
                                <div id="logContent"></div>
                            </div>

                            <div class="mt-4 flex justify-end">
                                <button id="btnCerrar" 
                                        class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                                    Cerrar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const btnEjecutar = document.getElementById('btnEjecutar');
    const modalEjecucion = document.getElementById('modalEjecucion');
    const btnCerrar = document.getElementById('btnCerrar');
    const progreso = document.getElementById('progreso');
    const barraProgreso = document.getElementById('barraProgreso');
    const contadorProcesados = document.getElementById('contadorProcesados');
    const contadorActualizados = document.getElementById('contadorActualizados');
    const contadorErrores = document.getElementById('contadorErrores');
    const logContent = document.getElementById('logContent');

    btnEjecutar.addEventListener('click', function() {
        // Mostrar modal
        modalEjecucion.classList.remove('hidden');
        
        // Resetear contadores
        contadorProcesados.textContent = '0';
        contadorActualizados.textContent = '0';
        contadorErrores.textContent = '0';
        progreso.textContent = '0%';
        barraProgreso.style.width = '0%';
        logContent.innerHTML = '';

        // Deshabilitar botón
        btnEjecutar.disabled = true;
        btnEjecutar.textContent = 'Ejecutando...';
        btnEjecutar.classList.add('opacity-50');

        // Ejecutar actualización
        fetch('{{ route("admin.productos.actualizar.clicks.procesar") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'ok') {
                agregarLog(`✅ Actualización completada: ${data.actualizados} actualizados, ${data.errores} errores`);
                progreso.textContent = '100%';
                barraProgreso.style.width = '100%';
                contadorProcesados.textContent = data.actualizados + data.errores;
                contadorActualizados.textContent = data.actualizados;
                contadorErrores.textContent = data.errores;
            } else {
                agregarLog(`❌ Error en la actualización: ${data.message || 'Error desconocido'}`);
            }
        })
        .catch(error => {
            agregarLog(`❌ Error de conexión: ${error.message}`);
        })
        .finally(() => {
            btnEjecutar.disabled = false;
            btnEjecutar.textContent = 'Ejecutar Actualización';
            btnEjecutar.classList.remove('opacity-50');
        });
    });

    btnCerrar.addEventListener('click', function() {
        modalEjecucion.classList.add('hidden');
    });

    function agregarLog(mensaje) {
        const timestamp = new Date().toLocaleTimeString();
        const logEntry = document.createElement('div');
        logEntry.className = 'mb-1';
        logEntry.innerHTML = `<span class="text-gray-500">[${timestamp}]</span> ${mensaje}`;
        logContent.appendChild(logEntry);
        logContent.scrollTop = logContent.scrollHeight;
    }
});
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