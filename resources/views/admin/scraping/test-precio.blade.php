<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.dashboard') }}">
                <h2 class="font-semibold text-xl text-white leading-tight">Inicio -></h2>
            </a>
            <h2 class="font-semibold text-xl text-white leading-tight">Testing de Precios</h2>
        </div>
    </x-slot>

    <div class="max-w-6xl mx-auto py-10 px-4 space-y-8 bg-gray-50 dark:bg-gray-900 rounded-lg shadow-md">
        
        <!-- Panel de Control -->
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6 border border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-200 mb-4">Testing de Precios</h3>
            <p class="text-gray-600 dark:text-gray-400 mb-6">Prueba el scraping de precios con una URL específica y tienda.</p>
            
            <form id="formTestPrecio" class="space-y-4">
                @csrf
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="url" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            URL del Producto
                        </label>
                        <input type="url" id="url" name="url" required
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                            placeholder="https://ejemplo.com/producto">
                    </div>
                    
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
                </div>
                
                <div>
                    <label for="variante" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Variante (opcional)
                    </label>
                    <input type="text" id="variante" name="variante"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                        placeholder="Ej: Talla 1, Color azul, etc.">
                </div>
                
                <div class="flex gap-4">
                    <button type="submit" id="btnProcesar"
                        class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-6 py-3 rounded-md shadow-md transition">
                        🔍 Obtener Precio
                    </button>
                    <button type="button" onclick="limpiarResultados()"
                        class="bg-gray-600 hover:bg-gray-700 text-white font-semibold px-6 py-3 rounded-md shadow-md transition">
                        🗑️ Limpiar
                    </button>
                </div>
            </form>
        </div>

        <!-- Resultados -->
        <div id="resultadosContainer" class="hidden bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6 border border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-200 mb-4">Resultados</h3>
            
            <div id="loadingResultados" class="hidden text-center py-8">
                <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                <p class="mt-2 text-gray-600 dark:text-gray-400">Procesando...</p>
            </div>
            
            <div id="resultados" class="space-y-4">
                <!-- Los resultados se mostrarán aquí -->
            </div>
        </div>

    </div>

    <script>
        document.getElementById('formTestPrecio').addEventListener('submit', function(e) {
            e.preventDefault();
            procesarUrl();
        });

        function procesarUrl() {
            const url = document.getElementById('url').value;
            const tienda = document.getElementById('tienda').value;
            const variante = document.getElementById('variante').value;
            
            if (!url || !tienda) {
                alert('Por favor, completa la URL y selecciona una tienda.');
                return;
            }
            
            // Mostrar contenedor de resultados y loading
            document.getElementById('resultadosContainer').classList.remove('hidden');
            document.getElementById('loadingResultados').classList.remove('hidden');
            document.getElementById('resultados').innerHTML = '';
            document.getElementById('btnProcesar').disabled = true;
            
            // Realizar petición AJAX
            fetch('{{ route("admin.scraping.test.precio.procesar") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    url: url,
                    tienda: tienda,
                    variante: variante
                })
            })
            .then(response => response.json())
            .then(data => {
                document.getElementById('loadingResultados').classList.add('hidden');
                document.getElementById('btnProcesar').disabled = false;
                
                if (data.success) {
                    mostrarResultados(data.data, url, tienda, variante);
                } else {
                    mostrarError(data.error || 'Error desconocido', data.tiempo_respuesta);
                }
            })
            .catch(error => {
                document.getElementById('loadingResultados').classList.add('hidden');
                document.getElementById('btnProcesar').disabled = false;
                mostrarError('Error de conexión: ' + error.message);
            });
        }

        function mostrarResultados(data, url, tienda, variante) {
            const resultadosDiv = document.getElementById('resultados');
            
            let html = `
                <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-700 rounded-lg p-4">
                    <h4 class="font-semibold text-green-800 dark:text-green-200 mb-2">✅ Procesamiento Exitoso</h4>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <strong class="text-gray-700 dark:text-gray-300">URL:</strong>
                            <p class="text-sm text-gray-600 dark:text-gray-400 break-all">${url}</p>
                        </div>
                        <div>
                            <strong class="text-gray-700 dark:text-gray-300">Tienda:</strong>
                            <p class="text-sm text-gray-600 dark:text-gray-400">${tienda}</p>
                        </div>
                    </div>
                    
                    ${variante ? `
                    <div class="mb-4">
                        <strong class="text-gray-700 dark:text-gray-300">Variante:</strong>
                        <p class="text-sm text-gray-600 dark:text-gray-400">${variante}</p>
                    </div>
                    ` : ''}
                    
                    <div class="bg-white dark:bg-gray-700 rounded-lg p-4 border">
                        <h5 class="font-semibold text-gray-800 dark:text-gray-200 mb-2">Datos Extraídos:</h5>
                        <div class="space-y-2">
                            <div>
                                <strong class="text-gray-700 dark:text-gray-300">Precio:</strong>
                                <span class="text-lg font-bold text-green-600 dark:text-green-400 ml-2">${data.precio}€</span>
                            </div>
                            <div>
                                <strong class="text-gray-700 dark:text-gray-300">Éxito:</strong>
                                <span class="ml-2 ${data.success ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'}">
                                    ${data.success ? '✅ Sí' : '❌ No'}
                                </span>
                            </div>
                            ${data.tiempo_respuesta ? `
                            <div>
                                <strong class="text-gray-700 dark:text-gray-300">Tiempo de respuesta:</strong>
                                <span class="ml-2 text-gray-600 dark:text-gray-400">${data.tiempo_respuesta}ms</span>
                            </div>
                            ` : ''}
                        </div>
                    </div>
                </div>
            `;
            
            resultadosDiv.innerHTML = html;
        }

        function mostrarError(error, tiempoRespuesta = null) {
            const resultadosDiv = document.getElementById('resultados');
            
            let html = `
                <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700 rounded-lg p-4">
                    <h4 class="font-semibold text-red-800 dark:text-red-200 mb-2">❌ Error en el Procesamiento</h4>
                    <p class="text-red-700 dark:text-red-300">${error}</p>
            `;
            
            if (tiempoRespuesta) {
                html += `
                    <div class="mt-3 pt-3 border-t border-red-200 dark:border-red-700">
                        <strong class="text-gray-700 dark:text-gray-300">Tiempo de respuesta:</strong>
                        <span class="ml-2 text-gray-600 dark:text-gray-400">${tiempoRespuesta}ms</span>
                    </div>
                `;
            }
            
            html += '</div>';
            resultadosDiv.innerHTML = html;
        }

        function limpiarResultados() {
            document.getElementById('formTestPrecio').reset();
            document.getElementById('resultadosContainer').classList.add('hidden');
            document.getElementById('resultados').innerHTML = '';
        }
    </script>
</x-app-layout>
