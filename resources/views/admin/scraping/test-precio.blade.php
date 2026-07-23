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
                        <div class="flex items-center gap-3">
                            <select id="tienda" name="tienda" required
                                class="flex-1 min-w-0 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                                <option value="">Seleccionar tienda...</option>
                                @foreach($tiendas as $tienda)
                                    <option value="{{ $tienda }}">{{ $tienda }}</option>
                                @endforeach
                            </select>
                            <div id="tiendaControladorIndicador" class="hidden shrink-0 flex flex-col items-center justify-center text-center min-w-[4.5rem]">
                                <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Controlador</span>
                                <span id="tiendaControladorIcono" class="text-xl font-semibold leading-none mt-1" aria-hidden="true"></span>
                            </div>
                        </div>
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
        const urlInput = document.getElementById('url');
        const tiendaSelect = document.getElementById('tienda');
        const tiendaControladorIndicador = document.getElementById('tiendaControladorIndicador');
        const tiendaControladorIcono = document.getElementById('tiendaControladorIcono');
        const controladoresTiendas = @json($controladoresTiendas);
        let urlTiendaDetectionTimeout = null;

        document.getElementById('formTestPrecio').addEventListener('submit', function(e) {
            e.preventDefault();
            procesarUrl();
        });

        function normalizarNombreTienda(nombreTienda) {
            return String(nombreTienda || '')
                .toLowerCase()
                .replace(/[^a-z0-9]/g, '');
        }

        function verificarControladorTienda(nombreTienda) {
            const nombreNormalizado = normalizarNombreTienda(nombreTienda);
            if (!nombreNormalizado) return false;

            return controladoresTiendas.some(function(controlador) {
                return nombreNormalizado === normalizarNombreTienda(controlador);
            });
        }

        function actualizarIndicadorControlador(nombreTienda) {
            if (!nombreTienda || !nombreTienda.trim()) {
                ocultarIndicadorControlador();
                return;
            }

            const tieneControlador = verificarControladorTienda(nombreTienda);
            tiendaControladorIndicador.classList.remove('hidden');
            tiendaControladorIcono.textContent = tieneControlador ? '✅' : '❌';
            tiendaControladorIcono.classList.remove('text-green-600', 'dark:text-green-400', 'text-red-600', 'dark:text-red-400');
            tiendaControladorIcono.classList.add(
                tieneControlador ? 'text-green-600' : 'text-red-600',
                tieneControlador ? 'dark:text-green-400' : 'dark:text-red-400'
            );
            tiendaControladorIndicador.title = tieneControlador
                ? 'Existe controlador de scraping para esta tienda'
                : 'No existe controlador de scraping para esta tienda';
        }

        function ocultarIndicadorControlador() {
            tiendaControladorIndicador.classList.add('hidden');
            tiendaControladorIcono.textContent = '';
            tiendaControladorIndicador.removeAttribute('title');
        }

        async function limpiarUrlViaApi(url) {
            if (!url || !url.trim()) {
                return { url_limpia: url || '' };
            }
            try {
                const res = await fetch('{{ route("admin.ofertas.limpiar.url") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ url: url.trim() })
                });
                if (!res.ok) return { url_limpia: url.trim() };
                const data = await res.json();
                return { url_limpia: data.url_limpia ?? url.trim() };
            } catch (e) {
                return { url_limpia: url.trim() };
            }
        }

        function extraerDominioNormalizado(url) {
            try {
                if (!url || !url.trim()) {
                    return null;
                }

                let urlCompleta = url.trim();
                if (!/^https?:\/\//i.test(urlCompleta)) {
                    urlCompleta = 'https://' + urlCompleta;
                }

                let hostname;
                try {
                    hostname = new URL(urlCompleta).hostname;
                } catch (e) {
                    const match = urlCompleta.match(/^(?:https?:\/\/)?(?:www\.)?([^\/\s]+)/i);
                    if (match && match[1]) {
                        hostname = match[1];
                    } else {
                        return null;
                    }
                }

                if (!hostname) {
                    return null;
                }

                hostname = hostname.toLowerCase();
                hostname = hostname.replace(/^www\./, '');

                return hostname;
            } catch (error) {
                return null;
            }
        }

        function normalizarUrlTienda(urlTienda) {
            if (!urlTienda || !urlTienda.trim()) {
                return null;
            }

            let normalizada = urlTienda.trim().toLowerCase();
            normalizada = normalizada.replace(/^https?:\/\//, '');
            normalizada = normalizada.replace(/^www\./, '');
            normalizada = normalizada.replace(/\/.*$/, '');
            normalizada = normalizada.replace(/\/$/, '');

            if (!normalizada || !normalizada.includes('.')) {
                return null;
            }

            return normalizada;
        }

        function normalizarClaveTiendaDetectar(texto) {
            return String(texto || '')
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .toLowerCase()
                .replace(/[^a-z0-9]/g, '');
        }

        function clavesHostTiendaDetectar(tienda) {
            const claves = [];
            const nombre = normalizarClaveTiendaDetectar(tienda && tienda.nombre);
            if (nombre) claves.push(nombre);
            const urlCampo = tienda && tienda.url ? String(tienda.url).trim() : '';
            if (urlCampo && !urlCampo.includes('.')) {
                const slug = normalizarClaveTiendaDetectar(urlCampo);
                if (slug) claves.push(slug);
            }
            return [...new Set(claves)];
        }

        function buscarTiendaEnListaPorUrl(host, todasLasTiendas) {
            if (!host || !Array.isArray(todasLasTiendas)) return null;

            for (let i = 0; i < todasLasTiendas.length; i++) {
                const tienda = todasLasTiendas[i];
                const dominioTienda = normalizarUrlTienda(tienda && tienda.url);
                if (!dominioTienda) continue;
                if (host === dominioTienda || host.endsWith('.' + dominioTienda) || dominioTienda.endsWith('.' + host)) {
                    return tienda;
                }
            }

            let mejor = null;
            let mejorLongitud = 0;
            for (let i = 0; i < todasLasTiendas.length; i++) {
                const claves = clavesHostTiendaDetectar(todasLasTiendas[i]);
                for (let j = 0; j < claves.length; j++) {
                    const clave = claves[j];
                    if (clave.length < 4) continue;
                    if (host.includes(clave) && clave.length > mejorLongitud) {
                        mejor = todasLasTiendas[i];
                        mejorLongitud = clave.length;
                    }
                }
            }
            return mejor;
        }

        function seleccionarTiendaEnSelect(tienda) {
            if (!tienda || !tienda.nombre) return;
            const existe = Array.from(tiendaSelect.options).some(opt => opt.value === tienda.nombre);
            if (existe) {
                tiendaSelect.value = tienda.nombre;
                tiendaSelect.classList.add('border-green-500');
                actualizarIndicadorControlador(tienda.nombre);
            }
        }

        async function detectarTiendaPorUrl(url) {
            if (!url || !url.trim()) {
                return;
            }

            const tiendaActual = tiendaSelect.value;
            if (tiendaActual && tiendaActual.trim() !== '') {
                return;
            }

            const dominioUrl = extraerDominioNormalizado(url);
            if (!dominioUrl) {
                return;
            }

            try {
                const response = await fetch('{{ route("admin.ofertas.tiendas.disponibles") }}');
                if (!response.ok) {
                    return;
                }

                const todasLasTiendas = await response.json();
                if (!Array.isArray(todasLasTiendas) || todasLasTiendas.length === 0) {
                    return;
                }

                const tiendaEncontrada = buscarTiendaEnListaPorUrl(dominioUrl, todasLasTiendas);
                if (tiendaEncontrada) {
                    seleccionarTiendaEnSelect(tiendaEncontrada);
                }
            } catch (error) {
                // Silenciar errores
            }
        }

        urlInput.addEventListener('paste', function() {
            setTimeout(async () => {
                const urlPegada = urlInput.value.trim();
                if (!urlPegada) return;
                const { url_limpia } = await limpiarUrlViaApi(urlPegada);
                if (url_limpia !== urlPegada) {
                    urlInput.value = url_limpia;
                }
                setTimeout(() => detectarTiendaPorUrl(url_limpia || urlPegada), 50);
            }, 50);
        });

        urlInput.addEventListener('input', function() {
            const url = urlInput.value.trim();

            if (urlTiendaDetectionTimeout) clearTimeout(urlTiendaDetectionTimeout);

            if (!url) {
                return;
            }

            const tiendaActual = tiendaSelect.value;
            if (!tiendaActual || tiendaActual.trim() === '') {
                urlTiendaDetectionTimeout = setTimeout(async () => {
                    const urlActual = urlInput.value.trim();
                    if (!urlActual) return;
                    const { url_limpia } = await limpiarUrlViaApi(urlActual);
                    detectarTiendaPorUrl(url_limpia || urlActual);
                }, 500);
            }
        });

        tiendaSelect.addEventListener('change', function() {
            if (tiendaSelect.value) {
                tiendaSelect.classList.add('border-green-500');
                actualizarIndicadorControlador(tiendaSelect.value);
            } else {
                tiendaSelect.classList.remove('border-green-500');
                ocultarIndicadorControlador();
            }
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
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    url: url,
                    tienda: tienda,
                    variante: variante
                })
            })
            .then(async response => {
                const texto = await response.text();
                let data = null;
                let parseError = null;
                try {
                    data = JSON.parse(texto);
                } catch (e) {
                    parseError = e.message;
                }
                return {
                    data,
                    status: response.status,
                    texto,
                    parseError,
                };
            })
            .then(resultado => {
                document.getElementById('loadingResultados').classList.add('hidden');
                document.getElementById('btnProcesar').disabled = false;
                mostrarRespuestaServidor(resultado, url, tienda, variante);
            })
            .catch(error => {
                document.getElementById('loadingResultados').classList.add('hidden');
                document.getElementById('btnProcesar').disabled = false;
                mostrarRespuestaServidor({
                    data: null,
                    status: null,
                    texto: null,
                    parseError: error.message,
                    networkError: true,
                }, url, tienda, variante);
            });
        }

        function extraerMensajeError(payload) {
            if (!payload || typeof payload !== 'object') return null;
            if (payload.error) return String(payload.error);
            if (payload.message) return String(payload.message);
            if (payload.data && payload.data.error) return String(payload.data.error);
            if (payload.exception) return String(payload.exception);
            return null;
        }

        function esExitoScraping(payload) {
            if (!payload || typeof payload !== 'object') return false;
            // TestPrecio envuelve: { success: true, data: { success, precio, ... } }
            if (payload.success === true && payload.data && typeof payload.data === 'object') {
                return payload.data.success === true && payload.data.precio != null;
            }
            return payload.success === true && payload.precio != null;
        }

        function datosScraping(payload) {
            if (payload && payload.data && typeof payload.data === 'object') {
                return payload.data;
            }
            return payload || {};
        }

        function htmlBloqueRespuestaServidor(resultado) {
            const meta = [
                resultado.status != null ? `HTTP ${resultado.status}` : null,
                resultado.parseError ? `Parse JSON: ${resultado.parseError}` : null,
                resultado.networkError ? 'Error de red/fetch' : null,
            ].filter(Boolean).join(' · ') || 'sin meta';

            const cuerpo = resultado.data != null
                ? JSON.stringify(resultado.data, null, 2)
                : (resultado.texto || '(sin cuerpo)');

            return `
                <div class="mt-4 bg-gray-50 dark:bg-gray-900/50 border border-gray-300 dark:border-gray-600 rounded-lg p-4">
                    <h5 class="font-semibold text-gray-800 dark:text-gray-200 mb-1">Respuesta del servidor</h5>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">${escapeHtml(meta)}</p>
                    <pre class="p-3 bg-gray-100 dark:bg-gray-950 rounded text-xs overflow-x-auto max-h-[32rem] overflow-y-auto whitespace-pre-wrap border border-gray-200 dark:border-gray-700 text-gray-800 dark:text-gray-200">${escapeHtml(cuerpo)}</pre>
                </div>
            `;
        }

        function htmlBloqueDebug(debug) {
            if (!debug || (typeof debug === 'object' && Object.keys(debug).length === 0)) {
                return '';
            }
            return `
                <div class="mt-4 bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-200 dark:border-indigo-700 rounded-lg p-4">
                    <h5 class="font-semibold text-indigo-800 dark:text-indigo-200 mb-2">Debug / dónde falló</h5>
                    <pre class="p-3 bg-gray-100 dark:bg-gray-900 rounded text-xs overflow-x-auto max-h-80 overflow-y-auto whitespace-pre-wrap border border-gray-200 dark:border-gray-700 text-gray-800 dark:text-gray-200">${escapeHtml(JSON.stringify(debug, null, 2))}</pre>
                </div>
            `;
        }

        function mostrarRespuestaServidor(resultado, url, tienda, variante) {
            const resultadosDiv = document.getElementById('resultados');
            const payload = resultado.data;
            const ok = !resultado.parseError && !resultado.networkError && esExitoScraping(payload);
            const data = datosScraping(payload);
            const apiLog = (payload && payload.api_log) || data.api_log || null;
            const debug = (payload && payload.debug) || data.debug || null;
            const tiempo = (payload && payload.tiempo_respuesta) || data.tiempo_respuesta || null;
            const mensajeError = extraerMensajeError(payload)
                || resultado.parseError
                || (resultado.networkError ? resultado.parseError : null)
                || 'Sin mensaje de error en la respuesta (mira el JSON completo abajo)';

            let html = '';
            if (ok) {
                html += `
                <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-700 rounded-lg p-4">
                    <h4 class="font-semibold text-green-800 dark:text-green-200 mb-2">✅ Procesamiento Exitoso</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <strong class="text-gray-700 dark:text-gray-300">URL:</strong>
                            <p class="text-sm text-gray-600 dark:text-gray-400 break-all">${escapeHtml(url)}</p>
                        </div>
                        <div>
                            <strong class="text-gray-700 dark:text-gray-300">Tienda:</strong>
                            <p class="text-sm text-gray-600 dark:text-gray-400">${escapeHtml(tienda)}</p>
                        </div>
                    </div>
                    ${variante ? `
                    <div class="mb-4">
                        <strong class="text-gray-700 dark:text-gray-300">Variante:</strong>
                        <p class="text-sm text-gray-600 dark:text-gray-400">${escapeHtml(variante)}</p>
                    </div>` : ''}
                    <div class="bg-white dark:bg-gray-700 rounded-lg p-4 border">
                        <h5 class="font-semibold text-gray-800 dark:text-gray-200 mb-2">Datos Extraídos:</h5>
                        <div class="space-y-2">
                            <div>
                                <strong class="text-gray-700 dark:text-gray-300">Precio:</strong>
                                <span class="text-lg font-bold text-green-600 dark:text-green-400 ml-2">${escapeHtml(String(data.precio))}€</span>
                            </div>
                            ${tiempo != null ? `
                            <div>
                                <strong class="text-gray-700 dark:text-gray-300">Tiempo total:</strong>
                                <span class="ml-2 text-gray-600 dark:text-gray-400">${escapeHtml(String(tiempo))}ms</span>
                            </div>` : ''}
                            ${data.api_log && data.api_log.tiempo_api_ms != null ? `
                            <div>
                                <strong class="text-gray-700 dark:text-gray-300">Tiempo API (obtenerHTML):</strong>
                                <span class="ml-2 text-gray-600 dark:text-gray-400">${escapeHtml(String(data.api_log.tiempo_api_ms))}ms</span>
                            </div>` : ''}
                            ${(data.descuentos_detectados && data.descuentos_detectados.length) || data.descuentos ? `
                            <div>
                                <strong class="text-gray-700 dark:text-gray-300">Descuentos detectados:</strong>
                                <ul class="mt-1 ml-4 list-disc text-sm text-gray-600 dark:text-gray-400 space-y-1">
                                    ${(data.descuentos_detectados && data.descuentos_detectados.length
                                        ? data.descuentos_detectados
                                        : String(data.descuentos || '').split('||').filter(Boolean)
                                    ).map(descuento => `<li><code class="text-xs bg-gray-100 dark:bg-gray-800 px-1 rounded">${escapeHtml(descuento)}</code> — ${escapeHtml(formatearDescuentoDetectado(descuento))}</li>`).join('')}
                                </ul>
                            </div>` : `
                            <div>
                                <strong class="text-gray-700 dark:text-gray-300">Descuentos detectados:</strong>
                                <span class="ml-2 text-gray-500 dark:text-gray-400">Ninguno</span>
                            </div>`}
                        </div>
                    </div>
                </div>`;
            } else {
                html += `
                <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700 rounded-lg p-4">
                    <h4 class="font-semibold text-red-800 dark:text-red-200 mb-2">❌ Error en el Procesamiento</h4>
                    <p class="text-red-700 dark:text-red-300 break-words">${escapeHtml(mensajeError)}</p>
                    ${tiempo != null ? `
                    <div class="mt-3 pt-3 border-t border-red-200 dark:border-red-700">
                        <strong class="text-gray-700 dark:text-gray-300">Tiempo total:</strong>
                        <span class="ml-2 text-gray-600 dark:text-gray-400">${escapeHtml(String(tiempo))}ms</span>
                    </div>` : ''}
                    <div class="mt-3 text-sm text-gray-600 dark:text-gray-400">
                        <div><strong>URL:</strong> <span class="break-all">${escapeHtml(url)}</span></div>
                        <div><strong>Tienda:</strong> ${escapeHtml(tienda)}</div>
                    </div>
                </div>`;
            }

            html += htmlBloqueDebug(debug);
            html += htmlBloqueApiLog(apiLog);
            html += htmlBloqueRespuestaServidor(resultado);
            resultadosDiv.innerHTML = html;
        }

        function formatearDescuentoDetectado(descuento) {
            const item = String(descuento || '').trim();
            const match2aCupon = item.match(/^2a al (\d+) - cupon;(.+)$/i);
            if (match2aCupon) {
                return `-${match2aCupon[1]}% en 2ª ud (cupón ${match2aCupon[2]})`;
            }
            const matchCuponPct = item.match(/^cupon;([^;]+);%(\d+)$/i);
            if (matchCuponPct) {
                return `Cupón ${matchCuponPct[1]} (-${matchCuponPct[2]}%)`;
            }
            const matchCuponEur = item.match(/^cupon;([^;]+);([\d.,]+)$/i);
            if (matchCuponEur) {
                return `Cupón ${matchCuponEur[1]} (-${matchCuponEur[2]}€)`;
            }
            return item;
        }

        function htmlBloqueApiLog(apiLog) {
            if (!apiLog) {
                return `
                    <div class="mt-4 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700 rounded-lg p-4">
                        <h5 class="font-semibold text-amber-800 dark:text-amber-200 mb-1">Log API</h5>
                        <p class="text-sm text-amber-700 dark:text-amber-300">
                            No hay log de API. La petición no pasó por <code>obtenerHTML</code>
                            (p. ej. error previo, CSV Awin, o controlador sin llamada a la API).
                        </p>
                    </div>
                `;
            }

            const json = JSON.stringify(apiLog, null, 2);
            const tiempoApi = apiLog.tiempo_api_ms != null ? `${apiLog.tiempo_api_ms}ms` : '—';
            const proveedor = apiLog.proveedor || '—';

            return `
                <div class="mt-4 bg-slate-50 dark:bg-slate-900/40 border border-slate-200 dark:border-slate-600 rounded-lg p-4">
                    <h5 class="font-semibold text-gray-800 dark:text-gray-200 mb-2">Log API</h5>
                    <div class="flex flex-wrap gap-4 text-sm mb-3 text-gray-600 dark:text-gray-400">
                        <div><strong class="text-gray-700 dark:text-gray-300">Proveedor:</strong> ${escapeHtml(String(proveedor))}</div>
                        <div><strong class="text-gray-700 dark:text-gray-300">Tiempo API:</strong> ${escapeHtml(tiempoApi)}</div>
                        <div><strong class="text-gray-700 dark:text-gray-300">HTML:</strong> ${apiLog.html_length != null ? escapeHtml(String(apiLog.html_length)) + ' bytes' : '—'}</div>
                    </div>
                    <pre class="p-3 bg-gray-100 dark:bg-gray-900 rounded text-xs overflow-x-auto max-h-96 overflow-y-auto whitespace-pre-wrap border border-gray-200 dark:border-gray-700 text-gray-800 dark:text-gray-200">${escapeHtml(json)}</pre>
                </div>
            `;
        }

        function escapeHtml(text) {
            return String(text)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;');
        }

        function limpiarResultados() {
            document.getElementById('formTestPrecio').reset();
            tiendaSelect.classList.remove('border-green-500');
            ocultarIndicadorControlador();
            document.getElementById('resultadosContainer').classList.add('hidden');
            document.getElementById('resultados').innerHTML = '';
        }
    </script>
</x-app-layout>
