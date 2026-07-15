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
                            ${(data.descuentos_detectados && data.descuentos_detectados.length) || data.descuentos ? `
                            <div>
                                <strong class="text-gray-700 dark:text-gray-300">Descuentos detectados:</strong>
                                <ul class="mt-1 ml-4 list-disc text-sm text-gray-600 dark:text-gray-400 space-y-1">
                                    ${(data.descuentos_detectados && data.descuentos_detectados.length
                                        ? data.descuentos_detectados
                                        : String(data.descuentos || '').split('||').filter(Boolean)
                                    ).map(descuento => `<li><code class="text-xs bg-gray-100 dark:bg-gray-800 px-1 rounded">${descuento}</code> — ${formatearDescuentoDetectado(descuento)}</li>`).join('')}
                                </ul>
                                ${data.descuentos ? `
                                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400 break-all">
                                    <strong>Valor serializado:</strong> <code>${data.descuentos}</code>
                                </p>
                                ` : ''}
                            </div>
                            ` : `
                            <div>
                                <strong class="text-gray-700 dark:text-gray-300">Descuentos detectados:</strong>
                                <span class="ml-2 text-gray-500 dark:text-gray-400">Ninguno</span>
                            </div>
                            `}
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
            tiendaSelect.classList.remove('border-green-500');
            ocultarIndicadorControlador();
            document.getElementById('resultadosContainer').classList.add('hidden');
            document.getElementById('resultados').innerHTML = '';
        }
    </script>
</x-app-layout>
