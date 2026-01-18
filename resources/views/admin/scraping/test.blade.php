<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Testing de Scraping') }}
        </h2>
    </x-slot>

    {{-- Asegúrate de tener el token CSRF para el fetch --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">

                    <!-- Formulario de testing -->
                    <div class="mb-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Probar URL de Scraping</h3>

                        <form id="testForm" class="space-y-4">
                            @csrf
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div class="md:col-span-2">
                                    <label for="url" class="block text-sm font-medium text-gray-700 mb-2">
                                        URL a probar
                                    </label>
                                    <input type="url"
                                           id="url"
                                           name="url"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                                           placeholder="https://ejemplo.com/producto"
                                           required>
                                </div>

                                <div>
                                    <label for="api" class="block text-sm font-medium text-gray-700 mb-2">
                                        API de Scraping
                                        <button type="button"
                                                onclick="mostrarModalAPI()"
                                                class="ml-1 inline-flex items-center justify-center w-4 h-4 text-gray-400 hover:text-gray-600 focus:outline-none">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                        </button>
                                    </label>
                                    <select id="api"
                                            name="api"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                                        <option value="brightData;true">Bright Data (con JavaScript)</option>
                                        <option value="scrapingAnt">ScrapingAnt</option>
                                        <option value="brightData;false" selected>Bright Data (sin JavaScript)</option>
                                        <option value="miVpsHtml;1">Mi VPS;SELENIUM;0.8</option>
                                        <option value="miVpsHtml;2">Mi VPS;SELENIUM;1.2</option>
                                        <option value="miVpsHtml;3">Mi VPS;SELENIUM;1.8</option>
                                        <option value="miVpsHtml;4">Mi VPS;REQUESTS;0.0</option>
                                        <option value="miVpsHtml;5">Mi VPS;PROXY;1.0</option>
                                        <option value="aliexpressOpen">Api Aliexpress (Open Platform)</option>
                                        <option value="amazonApi">Amazon Product Advertising API</option>
                                        <option value="amazonProductInfo">Amazon Product Info API - RapidAPI</option>
                                        <option value="amazonPricing">Amazon Pricing And Product Info API - RapidAPI</option>
                                    </select>
                                </div>
                            </div>

                            <div class="flex items-center space-x-4">
                                <button type="submit"
                                        id="btnProcesar"
                                        class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                    </svg>
                                    Procesar URL
                                </button>

                                <div id="loading" class="hidden">
                                    <div class="flex items-center">
                                        <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        <span class="text-sm text-gray-600">Procesando...</span>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- Resultados -->
                    <div id="resultados" class="hidden">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Resultado del Scraping</h3>

                        <!-- Información de la URL y proveedor -->
                        <div class="mb-4 space-y-2">
                            <div class="p-3 bg-gray-50 rounded-md">
                                <span class="text-sm font-medium text-gray-700">URL procesada: </span>
                                <span id="urlProcesada" class="text-sm text-gray-600"></span>
                            </div>
                            <div id="proveedorInfo" class="p-3 bg-blue-50 rounded-md hidden">
                                <span class="text-sm font-medium text-blue-700">Proveedor utilizado: </span>
                                <span id="proveedorUtilizado" class="text-sm text-blue-600 font-semibold"></span>
                                <span id="httpStatus" class="ml-2 text-xs text-blue-700"></span>
                            </div>
                        </div>

                        <!-- Área de resultado (HTML/JSON/TEXTO) -->
                        <div class="mb-4">
                            <label for="htmlResult" class="block text-sm font-medium text-gray-700 mb-2">
                                Salida recibida
                                <button type="button"
                                        id="btnCopiar"
                                        class="ml-2 inline-flex items-center px-2 py-1 bg-gray-600 border border-transparent rounded text-xs text-white hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2">
                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                    </svg>
                                    Copiar
                                </button>
                            </label>
                            <textarea id="htmlResult"
                                      class="w-full h-96 px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 font-mono text-xs"
                                      readonly
                                      placeholder="La salida (HTML / JSON / texto) aparecerá aquí..."></textarea>
                        </div>

                        <!-- Estadísticas -->
                        <div id="estadisticas" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="p-3 bg-blue-50 rounded-md">
                                <div class="text-sm font-medium text-blue-800">Tamaño</div>
                                <div id="tamanoHTML" class="text-lg font-bold text-blue-900">0 bytes</div>
                            </div>
                            <div class="p-3 bg-green-50 rounded-md">
                                <div class="text-sm font-medium text-green-800">Líneas</div>
                                <div id="lineasHTML" class="text-lg font-bold text-green-900">0</div>
                            </div>
                            <div class="p-3 bg-purple-50 rounded-md">
                                <div class="text-sm font-medium text-purple-800">Tiempo</div>
                                <div id="tiempoRespuesta" class="text-lg font-bold text-purple-900">0ms</div>
                            </div>
                        </div>
                    </div>

                    <!-- Mensaje de error -->
                    <div id="error" class="hidden mt-4 p-4 bg-red-50 border border-red-200 rounded-md">
                        <div class="flex">
                            <svg class="w-5 h-5 text-red-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                            </svg>
                            <div>
                                <h3 class="text-sm font-medium text-red-800">Error en el procesamiento</h3>
                                <div id="errorMensaje" class="mt-1 text-sm text-red-700"></div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <!-- Modal de información de API -->
    <div id="modalAPI" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 max-w-md w-full">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-200">Información de APIs de Scraping</h2>
                <button type="button" onclick="cerrarModalAPI()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <div class="space-y-4 text-sm text-gray-700 dark:text-gray-300">
                <div>
                    <h3 class="font-semibold text-gray-900 dark:text-gray-200 mb-2">Bright Data (con JavaScript) - Por defecto</h3>
                    <p>Más lento y costoso. Necesario para sitios que usan JavaScript para cargar precios dinámicamente. Es la opción por defecto para mayor compatibilidad.</p>
                </div>

                <div>
                    <h3 class="font-semibold text-gray-900 dark:text-gray-200 mb-2">ScrapingAnt</h3>
                    <p>API de RapidAPI. Ideal para sitios que requieren proxy español. No requiere configuración adicional y es muy confiable.</p>
                </div>

                <div>
                    <h3 class="font-semibold text-gray-900 dark:text-gray-200 mb-2">Bright Data (sin JavaScript)</h3>
                    <p>Más rápido y económico. Ideal para sitios que no requieren JavaScript para mostrar precios.</p>
                </div>

                <div>
                    <h3 class="font-semibold text-gray-900 dark:text-gray-200 mb-2">Mi VPS — Proxies Residenciales</h3>
                    <p>Usa proxies residenciales rotativos para mayor anonimato. Ideal para sitios con detección avanzada de bots. Cada petición usa un proxy diferente de forma automática.</p>
                </div>

                <div>
                    <h3 class="font-semibold text-gray-900 dark:text-gray-200 mb-2">Amazon Product Advertising API</h3>
                    <p>API oficial de Amazon para obtener información de productos directamente. Devuelve datos estructurados en lugar de HTML. Requiere URL de Amazon con ASIN válido.</p>
                </div>

                <div>
                    <h3 class="font-semibold text-gray-900 dark:text-gray-200 mb-2">Amazon Product Info API - RapidAPI</h3>
                    <p>API de RapidAPI que extrae información detallada de productos de Amazon. Devuelve datos estructurados incluyendo precio, disponibilidad, imágenes y más. Ideal para obtener precios actualizados de Amazon.</p>
                </div>

                <div>
                    <h3 class="font-semibold text-gray-900 dark:text-gray-200 mb-2">Amazon Pricing And Product Info API - RapidAPI</h3>
                    <p>API de RapidAPI que obtiene información de precios y productos de Amazon. Devuelve datos estructurados incluyendo buyBoxPrice, disponibilidad y más. Ideal para obtener precios actualizados con información detallada del buy box de Amazon.</p>
                </div>
            </div>

            <div class="mt-6 flex justify-end">
                <button type="button" onclick="cerrarModalAPI()"
                    class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    Entendido
                </button>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('testForm');
            const btnProcesar = document.getElementById('btnProcesar');
            const loading = document.getElementById('loading');
            const resultados = document.getElementById('resultados');
            const errorBox = document.getElementById('error');
            const htmlResult = document.getElementById('htmlResult');
            const urlProcesada = document.getElementById('urlProcesada');
            const errorMensaje = document.getElementById('errorMensaje');
            const btnCopiar = document.getElementById('btnCopiar');
            const tamanoHTML = document.getElementById('tamanoHTML');
            const lineasHTML = document.getElementById('lineasHTML');
            const tiempoRespuesta = document.getElementById('tiempoRespuesta');
            const proveedorInfo = document.getElementById('proveedorInfo');
            const proveedorUtilizado = document.getElementById('proveedorUtilizado');
            const httpStatus = document.getElementById('httpStatus');

            form.addEventListener('submit', async function (e) {
                e.preventDefault();

                const url = document.getElementById('url').value;
                const api = document.getElementById('api').value;
                if (!url) return;

                btnProcesar.disabled = true;
                loading.classList.remove('hidden');
                resultados.classList.add('hidden');
                errorBox.classList.add('hidden');
                proveedorInfo.classList.add('hidden');
                htmlResult.value = '';
                httpStatus.textContent = '';

                const inicio = Date.now();

                const requestData = { url: url };
                if (api) requestData.api = api;

                try {
                    const resp = await fetch('{{ route("admin.scraping.test.procesar") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(requestData)
                    });

                    const status = resp.status;
                    const text = await resp.text();  // SIEMPRE leemos como texto primero
                    urlProcesada.textContent = url;

                    // si no devuelve nada:
                    if (!text || text.trim() === '') {
                        errorMensaje.textContent = `La API no devolvió contenido (HTTP ${status}).`;
                        errorBox.classList.remove('hidden');
                        return;
                    }

                    let data = null;
                    try {
                        data = JSON.parse(text);
                    } catch (e) {
                        // No es JSON -> lo mostramos crudo
                        proveedorUtilizado.textContent = api || 'desconocido';
                        httpStatus.textContent = `(HTTP ${status}, no-JSON)`;
                        proveedorInfo.classList.remove('hidden');

                        htmlResult.value = text;
                        pintarMetricas(text, inicio);
                        resultados.classList.remove('hidden');
                        return;
                    }

                    // Es JSON (nuestro backend)
                    const proveedor = data.proveedor || (api || 'desconocido');
                    proveedorUtilizado.textContent = proveedor;
                    httpStatus.textContent = `(HTTP ${status})`;
                    proveedorInfo.classList.remove('hidden');

                    if (data.success) {
                        let mostrado = '';

                        if (proveedor === 'ALIEXPRESS_OPEN' || proveedor === 'AMAZON_API' || proveedor === 'AMAZON_PRODUCT_INFO' || proveedor === 'AMAZON_PRICING') {
                            mostrado = JSON.stringify(data.raw ?? data, null, 2);
                        } else {
                            mostrado = (data.html || '');
                        }

                        htmlResult.value = mostrado;
                        pintarMetricas(mostrado, inicio);
                        resultados.classList.remove('hidden');
                    } else {
                        // Error JSON del backend (mostramos mensaje + raw si viene)
                        errorMensaje.textContent = data.error || 'Error desconocido del servidor';
                        errorBox.classList.remove('hidden');

                        if (data.raw) {
                            const mostrado = JSON.stringify(data.raw, null, 2);
                            htmlResult.value = mostrado;
                            pintarMetricas(mostrado, inicio);
                            resultados.classList.remove('hidden');
                        }
                    }
                } catch (err) {
                    errorMensaje.textContent = 'Error de conexión: ' + (err && err.message ? err.message : err);
                    errorBox.classList.remove('hidden');
                } finally {
                    btnProcesar.disabled = false;
                    loading.classList.add('hidden');
                }
            });

            function pintarMetricas(texto, inicio) {
                const tamano = new Blob([texto]).size;
                const lineas = (texto || '').split('\n').length;
                tamanoHTML.textContent = formatBytes(tamano);
                lineasHTML.textContent = lineas.toLocaleString();
                tiempoRespuesta.textContent = (Date.now() - inicio) + 'ms';
            }

            // Copiar
            btnCopiar.addEventListener('click', function () {
                htmlResult.select();
                htmlResult.setSelectionRange(0, 99999);
                document.execCommand('copy');
            });

            function formatBytes(bytes, decimals = 2) {
                if (bytes === 0) return '0 Bytes';
                const k = 1024;
                const dm = decimals < 0 ? 0 : decimals;
                const sizes = ['Bytes', 'KB', 'MB', 'GB'];
                const i = Math.floor(Math.log(bytes) / Math.log(k));
                return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
            }

            // Modal
            window.mostrarModalAPI = function () {
                document.getElementById('modalAPI').classList.remove('hidden');
            }
            window.cerrarModalAPI = function () {
                document.getElementById('modalAPI').classList.add('hidden');
            }
            document.getElementById('modalAPI').addEventListener('click', function (e) {
                if (e.target === this) cerrarModalAPI();
            });
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') cerrarModalAPI();
            });
        });
    </script>
</x-app-layout>
