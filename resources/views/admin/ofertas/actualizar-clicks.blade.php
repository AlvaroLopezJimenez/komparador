<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.dashboard') }}">
                <h2 class="font-semibold text-xl text-white leading-tight">Inicio -></h2>
            </a>
            <a href="{{ route('admin.ofertas.todas') }}">
                <h2 class="font-semibold text-xl text-white leading-tight">Ofertas -></h2>
            </a>
            <h2 class="font-semibold text-xl text-white leading-tight">Ejecución Guardar numero de clicks</h2>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold mb-2">Proceso de Actualización</h3>
                        <p class="text-gray-600 dark:text-gray-400 mb-4">
                            Este proceso actualizará los clicks de todas las ofertas de productos en función de los clics reales registrados 
                            en la tabla de clics de los últimos 7 días.
                        </p>
                        <div class="bg-blue-50 dark:bg-blue-900 border border-blue-200 dark:border-blue-700 rounded-lg p-4 mb-4">
                            <p class="text-sm text-blue-800 dark:text-blue-200">
                                <strong>Nota:</strong> El número de días (7) se puede modificar más adelante según las necesidades.
                            </p>
                        </div>
                    </div>

                    <div id="progreso-container" class="hidden mb-6">
                        <div class="mb-4">
                            <div class="flex justify-between text-sm text-gray-600 dark:text-gray-400 mb-2">
                                <span id="oferta-actual">Procesando...</span>
                                <span id="contador-progreso">0 / 0</span>
                            </div>
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2.5">
                                <div id="barra-progreso" class="bg-blue-600 h-2.5 rounded-full transition-all duration-300" style="width: 0%"></div>
                            </div>
                        </div>
                    </div>

                    <div id="resultado-container" class="hidden mb-6">
                        <div id="resultado-mensaje" class="p-4 rounded-lg"></div>
                    </div>

                    <div class="flex space-x-4">
                        <button id="btn-iniciar" 
                                class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                            Iniciar Actualización
                        </button>
                        <a href="{{ route('admin.ofertas.actualizar.clicks.ejecuciones') }}" 
                           class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                            Ver Historial
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('btn-iniciar').addEventListener('click', function() {
            const btn = this;
            const progresoContainer = document.getElementById('progreso-container');
            const resultadoContainer = document.getElementById('resultado-container');
            const resultadoMensaje = document.getElementById('resultado-mensaje');
            const barraProgreso = document.getElementById('barra-progreso');
            const contadorProgreso = document.getElementById('contador-progreso');
            const ofertaActual = document.getElementById('oferta-actual');

            btn.disabled = true;
            btn.textContent = 'Procesando...';
            progresoContainer.classList.remove('hidden');
            resultadoContainer.classList.add('hidden');

            function actualizarProgreso() {
                fetch('{{ route("admin.ofertas.actualizar.clicks.procesar") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.progreso !== undefined) {
                        barraProgreso.style.width = data.progreso + '%';
                        contadorProgreso.textContent = `${data.ofertas_procesadas} / ${data.total_ofertas}`;
                        ofertaActual.textContent = `Procesando: ${data.oferta_actual}`;

                        if (data.progreso < 100) {
                            setTimeout(actualizarProgreso, 1000);
                        } else {
                            mostrarResultado('success', data.message);
                        }
                    } else if (data.success) {
                        mostrarResultado('success', data.message);
                    } else {
                        mostrarResultado('error', data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    mostrarResultado('error', 'Error en la comunicación con el servidor');
                });
            }

            function mostrarResultado(tipo, mensaje) {
                btn.disabled = false;
                btn.textContent = 'Iniciar Actualización';
                progresoContainer.classList.add('hidden');
                resultadoContainer.classList.remove('hidden');
                
                if (tipo === 'success') {
                    resultadoMensaje.className = 'p-4 rounded-lg bg-green-100 border border-green-400 text-green-700';
                } else {
                    resultadoMensaje.className = 'p-4 rounded-lg bg-red-100 border border-red-400 text-red-700';
                }
                
                resultadoMensaje.textContent = mensaje;
            }

            actualizarProgreso();
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
