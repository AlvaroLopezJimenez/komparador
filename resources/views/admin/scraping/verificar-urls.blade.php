<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.dashboard') }}">
                <h2 class="font-semibold text-xl text-white leading-tight">Inicio -></h2>
            </a>
            <a href="{{ route('admin.scraping.diagnostico') }}">
                <h2 class="font-semibold text-xl text-white leading-tight">Scraping -></h2>
            </a>
            <h2 class="font-semibold text-xl text-white leading-tight">Verificar URLs</h2>
        </div>
    </x-slot>

    <div class="max-w-6xl mx-auto py-10 px-4 space-y-8">
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6 border border-gray-200 dark:border-gray-700">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Verificar URLs de Ofertas</h1>
            
            <form id="formVerificarUrls" class="space-y-6">
                @csrf
                
                <!-- Selección de Producto -->
                <div>
                    <label class="block mb-2 font-medium text-gray-700 dark:text-gray-200">Producto (opcional)</label>
                    <div class="relative">
                        <input type="hidden" name="producto_id" id="producto_id" value="">
                        <input type="text" id="producto_nombre"
                            class="w-full px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border focus:outline-none focus:ring-2 focus:ring-blue-500"
                            placeholder="Escribe para buscar productos (opcional)..."
                            autocomplete="off">
                        <div id="producto_sugerencias" class="absolute z-50 w-full mt-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md shadow-lg hidden max-h-60 overflow-y-auto"></div>
                    </div>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        Si no seleccionas un producto, se buscarán en todas las ofertas de la base de datos
                    </p>
                </div>

                <!-- Campo de URLs -->
                <div>
                    <label class="block mb-2 font-medium text-gray-700 dark:text-gray-200">URLs a verificar</label>
                    <!-- Textarea oculto para el formulario -->
                    <textarea name="urls" id="urls" required class="hidden"></textarea>
                    <!-- Div editable para mostrar con colores -->
                    <div id="urls-editable" 
                        contenteditable="true"
                        class="w-full px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border focus:outline-none focus:ring-2 focus:ring-blue-500 whitespace-pre-wrap overflow-auto"
                        style="font-family: monospace; line-height: 1.5; min-height: 250px; max-height: 600px;"
                        placeholder="Pega aquí las URLs, una por línea..."></div>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        Cada URL debe estar en una línea separada
                    </p>
                </div>

                                 <!-- Botones de acción -->
                 <div class="flex justify-between items-center">
                     <div id="botonesCrearOfertas" class="hidden">
                         <div class="flex items-center gap-4">
                             <!-- Configuración de bloque -->
                             <div class="flex items-center gap-2">
                                 <label class="text-sm font-medium text-gray-700 dark:text-gray-200">Bloques de:</label>
                                 <input type="number" id="tamanoBloque" value="5" min="1" max="20" 
                                     class="w-16 px-2 py-1 text-sm rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                                 <span class="text-sm text-gray-500 dark:text-gray-400">ofertas</span>
                             </div>
                             
                             <!-- Botón principal -->
                             <button type="button" id="btnCrearTodasOfertas"
                                 class="inline-flex items-center bg-green-600 hover:bg-green-700 text-white font-semibold px-6 py-3 rounded-md shadow-lg transition">
                                 <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                     <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                 </svg>
                                 <span id="textoBoton">Crear todas las ofertas (<span id="contadorUrlsNuevas">0</span>)</span>
                             </button>
                             
                             <!-- Indicador de progreso -->
                             <div id="indicadorProgreso" class="hidden">
                                 <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                                     <span id="progresoTexto">Bloque 1 de X</span>
                                     <div class="w-24 bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                         <div id="barraProgreso" class="bg-green-500 h-2 rounded-full transition-all duration-300" style="width: 0%"></div>
                                     </div>
                                 </div>
                             </div>
                         </div>
                     </div>
                     <button type="submit" id="btnVerificar"
                         class="inline-flex items-center bg-blue-600 hover:bg-blue-700 text-white font-semibold px-6 py-3 rounded-md shadow-lg transition disabled:opacity-50 disabled:cursor-not-allowed">
                         <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                             <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                         </svg>
                         Verificar URLs
                     </button>
                 </div>
            </form>
        </div>

        <!-- Resultados -->
        <div id="resultados" class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6 border border-gray-200 dark:border-gray-700 hidden">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Resultados de la verificación</h2>
                <div id="contadorClickados" class="hidden">
                    <span class="inline-flex items-center px-3 py-1 bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-200 text-sm rounded-full">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span id="numeroClickados">0</span> procesados
                    </span>
                </div>
            </div>
            
            <!-- Resumen estadístico -->
            <div id="resumenEstadistico" class="mb-6 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-600">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Resumen de URLs verificadas</h3>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="text-center">
                        <div id="totalUrls" class="text-2xl font-bold text-gray-900 dark:text-white">0</div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">Total URLs</div>
                    </div>
                    <div class="text-center">
                        <div id="urlsNuevas" class="text-2xl font-bold text-green-600 dark:text-green-400">0</div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">URLs nuevas</div>
                    </div>
                    <div class="text-center">
                        <div id="urlsExistentesOtros" class="text-2xl font-bold text-gray-600 dark:text-gray-400">0</div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">Existen en otros</div>
                    </div>
                    <div class="text-center">
                        <div id="urlsExistentesMismo" class="text-2xl font-bold text-red-600 dark:text-red-400">0</div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">Existen en este</div>
                    </div>
                </div>
            </div>
            
            <div id="resultadosLista" class="space-y-3"></div>
        </div>
    </div>

    <script>
        let timeoutBusqueda = null;
        let productosActuales = [];
        let indiceSeleccionado = -1;

        // Función para buscar productos en tiempo real
        async function buscarProductos(query) {
            if (query.length < 2) {
                ocultarSugerencias();
                return;
            }

            try {
                const response = await fetch(`{{ route('admin.ofertas.buscar.productos') }}?q=${encodeURIComponent(query)}`);
                const productos = await response.json();
                productosActuales = productos;
                mostrarSugerencias(productos);
            } catch (error) {
                console.error('Error al buscar productos:', error);
            }
        }

        // Función para mostrar sugerencias
        function mostrarSugerencias(productos) {
            const contenedor = document.getElementById('producto_sugerencias');
            contenedor.innerHTML = '';
            indiceSeleccionado = -1;

            if (productos.length === 0) {
                contenedor.innerHTML = '<div class="px-4 py-2 text-gray-500 dark:text-gray-400">No se encontraron productos</div>';
                contenedor.classList.remove('hidden');
                return;
            }

            productos.forEach((producto, index) => {
                const div = document.createElement('div');
                div.className = 'px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 cursor-pointer text-gray-900 dark:text-white border-b border-gray-200 dark:border-gray-600 last:border-b-0';
                div.textContent = producto.texto_completo;
                div.dataset.index = index;
                
                div.onclick = () => {
                    seleccionarProducto(producto);
                };

                div.onmouseenter = () => {
                    const elementos = contenedor.querySelectorAll('div[data-index]');
                    elementos.forEach(el => el.classList.remove('bg-blue-100', 'dark:bg-blue-700'));
                    div.classList.add('bg-blue-100', 'dark:bg-blue-700');
                    indiceSeleccionado = index;
                };

                contenedor.appendChild(div);
            });

            if (productos.length > 0) {
                const primerElemento = contenedor.querySelector('div[data-index="0"]');
                if (primerElemento) {
                    primerElemento.classList.add('bg-blue-100', 'dark:bg-blue-700');
                    indiceSeleccionado = 0;
                }
            }

            contenedor.classList.remove('hidden');
        }

        // Función para ocultar sugerencias
        function ocultarSugerencias() {
            document.getElementById('producto_sugerencias').classList.add('hidden');
            indiceSeleccionado = -1;
        }

        // Función para seleccionar un producto
        function seleccionarProducto(producto) {
            const productoIdInput = document.getElementById('producto_id');
            const productoNombreInput = document.getElementById('producto_nombre');
            
            productoIdInput.value = producto.id;
            productoNombreInput.value = producto.texto_completo;
            productoNombreInput.classList.add('border-green-500');
            
            ocultarSugerencias();
        }

        // Función para navegar con teclado
        function navegarSugerencias(direccion) {
            const contenedor = document.getElementById('producto_sugerencias');
            const elementos = contenedor.querySelectorAll('div[data-index]');
            
            if (elementos.length === 0) return;
            
            elementos.forEach(el => el.classList.remove('bg-blue-100', 'dark:bg-blue-700'));
            
            if (direccion === 'arriba') {
                indiceSeleccionado = indiceSeleccionado <= 0 ? elementos.length - 1 : indiceSeleccionado - 1;
            } else if (direccion === 'abajo') {
                indiceSeleccionado = indiceSeleccionado >= elementos.length - 1 ? 0 : indiceSeleccionado + 1;
            }
            
            if (indiceSeleccionado >= 0 && indiceSeleccionado < elementos.length) {
                elementos[indiceSeleccionado].classList.add('bg-blue-100', 'dark:bg-blue-700');
                elementos[indiceSeleccionado].scrollIntoView({ block: 'nearest' });
            }
        }

        // Función para seleccionar el producto actualmente resaltado
        function seleccionarProductoResaltado() {
            if (indiceSeleccionado >= 0 && indiceSeleccionado < productosActuales.length) {
                seleccionarProducto(productosActuales[indiceSeleccionado]);
            }
        }

        // Función para verificar URLs
        async function verificarUrls(event) {
            event.preventDefault();
            
            const btnVerificar = document.getElementById('btnVerificar');
            const urls = document.getElementById('urls').value.trim();
            
            if (!urls) {
                alert('Por favor, introduce al menos una URL para verificar');
                return;
            }
            
            // Verificar que existe el token CSRF
            const csrfTokenMeta = document.querySelector('meta[name="csrf-token"]');
            if (!csrfTokenMeta) {
                alert('Error: No se encontró el token CSRF. Por favor, recarga la página.');
                console.error('Meta tag CSRF no encontrado');
                return;
            }
            
            const csrfToken = csrfTokenMeta.getAttribute('content');
            if (!csrfToken) {
                alert('Error: El token CSRF está vacío. Por favor, recarga la página.');
                console.error('Token CSRF vacío');
                return;
            }
            
            // Deshabilitar botón y mostrar estado de carga
            btnVerificar.disabled = true;
            btnVerificar.innerHTML = '<svg class="animate-spin w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Verificando...';
            
            try {
                const formData = new FormData();
                formData.append('urls', urls);
                formData.append('producto_id', document.getElementById('producto_id').value || '');
                formData.append('_token', csrfToken);
                
                const url = '{{ route("admin.scraping.verificar-urls.procesar") }}';
                console.log('Enviando petición a:', url);
                
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: formData
                });
                
                console.log('Respuesta recibida. Status:', response.status);
                
                // Verificar si la respuesta es exitosa
                if (!response.ok) {
                    let errorMessage = `Error ${response.status}: ${response.statusText}`;
                    try {
                        const errorText = await response.text();
                        console.error('Error response text:', errorText);
                        if (errorText) {
                            // Intentar parsear como JSON
                            try {
                                const errorJson = JSON.parse(errorText);
                                if (errorJson.message) {
                                    errorMessage += ` - ${errorJson.message}`;
                                }
                            } catch {
                                // Si no es JSON, mostrar el texto del error
                                if (errorText.length < 200) {
                                    errorMessage += ` - ${errorText}`;
                                }
                            }
                        }
                    } catch (e) {
                        console.error('Error al leer respuesta de error:', e);
                    }
                    alert(`Error al verificar las URLs:\n${errorMessage}\n\nPor favor, revisa la consola del navegador para más detalles.`);
                    return;
                }
                
                // Intentar parsear la respuesta como JSON
                let data;
                try {
                    const responseText = await response.text();
                    console.log('Response text:', responseText);
                    data = JSON.parse(responseText);
                } catch (parseError) {
                    console.error('Error al parsear JSON:', parseError);
                    alert('Error: La respuesta del servidor no es válida. Por favor, revisa la consola para más detalles.');
                    return;
                }
                
                if (data.success) {
                    // Manejar URLs duplicadas
                    if (data.total_duplicadas > 0) {
                        alert(`Se encontraron ${data.total_duplicadas} URL${data.total_duplicadas > 1 ? 's' : ''} duplicada${data.total_duplicadas > 1 ? 's' : ''}. Las URLs duplicadas han sido marcadas en el campo de texto con "-Duplicada".`);
                        
                        // Marcar URLs duplicadas en el textarea
                        const urlsTextarea = document.getElementById('urls');
                        let textoUrls = urlsTextarea.value;
                        const lineas = textoUrls.split('\n');
                        
                        // Marcar cada URL duplicada
                        data.urls_duplicadas.forEach(urlDuplicada => {
                            for (let i = 0; i < lineas.length; i++) {
                                const lineaOriginal = lineas[i];
                                const lineaTrim = lineaOriginal.trim();
                                
                                // Si la línea coincide exactamente con la URL duplicada (sin espacios) y no tiene ya "-Duplicada"
                                if (lineaTrim === urlDuplicada && !lineaTrim.endsWith(' -Duplicada')) {
                                    // Preservar espacios al inicio y agregar "-Duplicada" al final
                                    const espaciosInicio = lineaOriginal.match(/^\s*/)[0];
                                    lineas[i] = espaciosInicio + lineaTrim + ' -Duplicada';
                                    break; // Solo marcar la primera ocurrencia de esta URL
                                }
                            }
                        });
                        
                        // Actualizar el textarea
                        urlsTextarea.value = lineas.join('\n');
                        
                        // Renderizar con colores (las líneas con "-Duplicada" aparecerán en amarillo)
                        if (typeof renderizarUrlsConColores === 'function') {
                            renderizarUrlsConColores();
                        }
                    }
                    
                    // Mostrar resultados (sin duplicadas)
                    mostrarResultados(data.resultados);
                } else {
                    alert('Error al verificar las URLs: ' + (data.message || 'Error desconocido'));
                }
            } catch (error) {
                console.error('Error completo:', error);
                console.error('Error message:', error.message);
                console.error('Error stack:', error.stack);
                alert(`Error de conexión al verificar las URLs:\n${error.message}\n\nPor favor, revisa la consola del navegador para más detalles.`);
            } finally {
                // Restaurar botón
                btnVerificar.disabled = false;
                btnVerificar.innerHTML = '<svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg> Verificar URLs';
            }
        }

                                  // Función para mostrar resultados
         function mostrarResultados(resultados) {
             const resultadosDiv = document.getElementById('resultados');
             const resultadosLista = document.getElementById('resultadosLista');
             const botonesCrearOfertas = document.getElementById('botonesCrearOfertas');
             const contadorUrlsNuevas = document.getElementById('contadorUrlsNuevas');
             const indicadorProgreso = document.getElementById('indicadorProgreso');
             
             // Resetear estado del sistema de bloques
             resetearEstadoBloques();
             
             resultadosLista.innerHTML = '';
             
             // Calcular estadísticas
             const totalUrls = resultados.length;
             const urlsNuevas = resultados.filter(r => !r.existe_mismo_producto && !r.existe_otros_productos);
             const urlsExistentesOtros = resultados.filter(r => !r.existe_mismo_producto && r.existe_otros_productos);
             const urlsExistentesMismo = resultados.filter(r => r.existe_mismo_producto);
             
             // Actualizar resumen estadístico
             document.getElementById('totalUrls').textContent = totalUrls;
             document.getElementById('urlsNuevas').textContent = urlsNuevas.length;
             document.getElementById('urlsExistentesOtros').textContent = urlsExistentesOtros.length;
             document.getElementById('urlsExistentesMismo').textContent = urlsExistentesMismo.length;
             
             // Contar URLs que no existen en ningún producto (solo las completamente nuevas)
             const contadorNuevas = urlsNuevas.length;
             
             // Mostrar/ocultar botón general y actualizar contador
             if (contadorNuevas > 0) {
                 botonesCrearOfertas.classList.remove('hidden');
                 contadorUrlsNuevas.textContent = contadorNuevas;
             } else {
                 botonesCrearOfertas.classList.add('hidden');
                 contadorUrlsNuevas.textContent = '0';
             }
             
             resultados.forEach((resultado, index) => {
                // Determinar el estado y color basado en la lógica nueva
                let existeMismoProducto = resultado.existe_mismo_producto;
                let existeOtrosProductos = resultado.existe_otros_productos;
                
                // Si existe en el mismo producto, mostrar en rojo
                // Si existe en otros productos, mostrar en verde pero con información
                // Si no existe en ningún lado, mostrar en verde
                let bgClass, borderClass, statusIcon, statusText, statusColor;
                
                if (existeMismoProducto) {
                    // Rojo: URL existe para el producto seleccionado
                    bgClass = 'bg-red-50 dark:bg-red-900/20';
                    borderClass = 'border-red-200 dark:border-red-700';
                    statusIcon = '<svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>';
                    statusText = 'URL ya existe para este producto';
                    statusColor = 'text-red-700 dark:text-red-300';
                } else if (existeOtrosProductos) {
                    // Gris: URL existe para otros productos (no se puede crear nueva oferta)
                    bgClass = 'bg-gray-50 dark:bg-gray-800';
                    borderClass = 'border-gray-200 dark:border-gray-600';
                    statusIcon = '<svg class="w-5 h-5 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>';
                    statusText = 'URL ya existe en otros productos';
                    statusColor = 'text-gray-700 dark:text-gray-200';
                } else {
                    // Verde: URL completamente nueva
                    bgClass = 'bg-green-50 dark:bg-green-900/20';
                    borderClass = 'border-green-200 dark:border-green-700';
                    statusIcon = '<svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>';
                    statusText = 'URL disponible';
                    statusColor = 'text-green-700 dark:text-green-300';
                }
                
                const div = document.createElement('div');
                div.className = `p-4 rounded-lg border ${bgClass} ${borderClass}`;
                // Guardar la URL normalizada en un atributo data para el procesamiento masivo
                div.setAttribute('data-url-normalizada', resultado.url_normalizada);
                
                // Botón para crear oferta solo si no existe en ningún producto
                let botonCrearOferta = '';
                if (!existeMismoProducto && !existeOtrosProductos) {
                    const urlCrearOferta = `{{ route('admin.ofertas.create.formularioGeneral') }}?url=${encodeURIComponent(resultado.url_normalizada)}`;
                    botonCrearOferta = `
                        <div class="mt-2">
                            <button onclick="abrirUrlYFormulario('${resultado.url_normalizada}', '${urlCrearOferta}', this)" class="inline-flex items-center px-3 py-1 bg-green-500 hover:bg-green-600 text-white text-sm rounded transition">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                                Crear oferta
                            </button>
                        </div>
                    `;
                }
                
                // Información de ofertas existentes
                let ofertaInfo = '';
                
                // Si existe en el mismo producto, mostrar esa oferta
                if (existeMismoProducto && resultado.oferta_mismo_producto) {
                    const oferta = resultado.oferta_mismo_producto;
                    const producto = oferta.producto;
                    const tienda = oferta.tienda;
                    
                    // Construir la ruta del producto de forma más robusta
                    let rutaProducto = '';
                    if (producto.ruta_completa) {
                        rutaProducto = producto.ruta_completa;
                    } else if (producto.categoria && producto.categoria.slug && producto.slug) {
                        rutaProducto = `${producto.categoria.slug}/${producto.slug}`;
                    } else {
                        rutaProducto = `producto/${producto.slug || producto.id}`;
                    }
                    
                    const urlProducto = `{{ url('/') }}/${rutaProducto}`;
                    const urlEditar = `{{ url('/panel-privado/ofertas') }}/${oferta.id}/edit`;
                    
                    ofertaInfo = `
                        <div class="mt-3 p-3 bg-red-100 dark:bg-red-900/30 rounded-lg">
                            <div class="text-sm text-red-800 dark:text-red-200 mb-2">
                                <strong>⚠️ Esta URL ya existe para el producto seleccionado:</strong>
                            </div>
                            <div class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                                <strong>Producto:</strong> ${producto.nombre} - ${producto.marca} - ${producto.modelo} - ${producto.talla}
                            </div>
                            <div class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                                <strong>Tienda:</strong> ${tienda.nombre}
                            </div>
                            <div class="text-sm text-gray-600 dark:text-gray-400 mb-3">
                                <strong>Precio:</strong> ${oferta.precio_total}€ (${oferta.precio_unidad}€/unidad)
                            </div>
                            <div class="flex gap-2">
                                <a href="${urlProducto}" target="_blank" class="inline-flex items-center px-3 py-1 bg-blue-500 hover:bg-blue-600 text-white text-sm rounded transition">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                    </svg>
                                    Ver en web
                                </a>
                                <a href="${urlEditar}" class="inline-flex items-center px-3 py-1 bg-yellow-500 hover:bg-yellow-600 text-white text-sm rounded transition">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                    Editar
                                </a>
                            </div>
                        </div>
                    `;
                }
                // Si existe en otros productos, mostrar esa información
                else if (existeOtrosProductos && resultado.ofertas_otros_productos.length > 0) {
                    const ofertas = resultado.ofertas_otros_productos;
                    let productosInfo = '';
                    
                    ofertas.forEach(oferta => {
                        const producto = oferta.producto;
                        const tienda = oferta.tienda;
                        
                        // Construir la ruta del producto
                        let rutaProducto = '';
                        if (producto.ruta_completa) {
                            rutaProducto = producto.ruta_completa;
                        } else if (producto.categoria && producto.categoria.slug && producto.slug) {
                            rutaProducto = `${producto.categoria.slug}/${producto.slug}`;
                        } else {
                            rutaProducto = `producto/${producto.slug || producto.id}`;
                        }
                        
                        const urlProducto = `{{ url('/') }}/${rutaProducto}`;
                        const urlEditar = `{{ url('/panel-privado/ofertas') }}/${oferta.id}/edit`;
                        
                        productosInfo += `
                            <div class="mb-3 p-3 bg-blue-50 dark:bg-gray-700 rounded-lg border border-blue-200 dark:border-gray-600">
                                <div class="text-sm text-gray-600 dark:text-gray-200 mb-2">
                                    <strong>Producto:</strong> ${producto.nombre} - ${producto.marca} - ${producto.modelo} - ${producto.talla}
                                </div>
                                <div class="text-sm text-gray-600 dark:text-gray-200 mb-2">
                                    <strong>Tienda:</strong> ${tienda.nombre}
                                </div>
                                <div class="text-sm text-gray-600 dark:text-gray-200 mb-3">
                                    <strong>Precio:</strong> ${oferta.precio_total}€ (${oferta.precio_unidad}€/unidad)
                                </div>
                                <div class="flex gap-2">
                                    <a href="${urlProducto}" target="_blank" class="inline-flex items-center px-3 py-1 bg-blue-500 hover:bg-blue-600 text-white text-sm rounded transition">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                        </svg>
                                        Ver en web
                                    </a>
                                    <a href="${urlEditar}" class="inline-flex items-center px-3 py-1 bg-yellow-500 hover:bg-yellow-600 text-white text-sm rounded transition">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                        </svg>
                                        Editar
                                    </a>
                                </div>
                            </div>
                        `;
                    });
                    
                    ofertaInfo = `
                        <div class="mt-3 p-3 bg-blue-100 dark:bg-gray-800 rounded-lg border border-gray-300 dark:border-gray-600">
                            <div class="text-sm text-blue-800 dark:text-blue-200 mb-3">
                                <strong>ℹ️ Esta URL existe en otros productos:</strong>
                            </div>
                            ${productosInfo}
                        </div>
                    `;
                }
                
                div.innerHTML = `
                    <div class="flex items-start gap-3">
                        <div class="flex-shrink-0 mt-0.5">
                            ${statusIcon}
                        </div>
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-2">
                                <span class="font-medium ${statusColor}">${statusText}</span>
                            </div>
                            <div class="text-sm text-gray-600 dark:text-gray-300 mb-1">
                                <strong>URL original:</strong> 
                                <a href="${resultado.url_original}" target="_blank" class="text-blue-500 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 underline">${resultado.url_original}</a>
                            </div>
                            <div class="text-sm text-gray-500 dark:text-gray-300 mb-2">
                                <strong>URL normalizada:</strong> 
                                <a href="${resultado.url_normalizada}" target="_blank" class="text-blue-500 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 underline">${resultado.url_normalizada}</a>
                            </div>
                             ${botonCrearOferta}
                             ${ofertaInfo}
                        </div>
                    </div>
                `;
                
                resultadosLista.appendChild(div);
            });
            
            resultadosDiv.classList.remove('hidden');
            
            // Convertir todas las URLs en enlaces después de mostrar los resultados
            convertirUrlsEnEnlaces();
        }

                 // Función para convertir URLs en enlaces
         function convertirUrlsEnEnlaces() {
             const resultadosLista = document.getElementById('resultadosLista');
             const urls = resultadosLista.querySelectorAll('.text-blue-500');
             
             urls.forEach(url => {
                 if (url.textContent && url.textContent.match(/^https?:\/\//)) {
                     url.href = url.textContent;
                     url.target = '_blank';
                 }
             });
         }
         
         // Función para abrir URL original y formulario (para botones individuales)
         function abrirUrlYFormulario(urlOriginal, urlFormulario, botonElement) {
             // Buscar o crear el indicador al lado del botón
             const contenedorBoton = botonElement.parentElement;
             let indicador = contenedorBoton.querySelector('.indicador-clickado');
             
             if (!indicador) {
                 // Crear el indicador si no existe
                 indicador = document.createElement('span');
                 indicador.className = 'indicador-clickado inline-flex items-center ml-2 px-2 py-1 bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-200 text-xs rounded-full';
                 indicador.innerHTML = `
                     <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                         <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                     </svg>
                     ¡Clickado!
                 `;
                 contenedorBoton.appendChild(indicador);
             }
             
             // Actualizar contador
             actualizarContadorClickados();
             
             // Abrir formulario primero
             window.open(urlFormulario, '_blank');
             
             // Esperar un poco y abrir URL original
             setTimeout(() => {
                 window.open(urlOriginal, '_blank');
             }, 1000); // 1 segundo después de abrir el formulario
         }
         
         // Función para actualizar el contador de botones clickados
         function actualizarContadorClickados() {
             const contadorDiv = document.getElementById('contadorClickados');
             const numeroSpan = document.getElementById('numeroClickados');
             
             // Contar indicadores de clickados
             const indicadoresClickados = document.querySelectorAll('.indicador-clickado');
             const numeroClickados = indicadoresClickados.length;
             
             if (numeroClickados > 0) {
                 contadorDiv.classList.remove('hidden');
                 numeroSpan.textContent = numeroClickados;
             } else {
                 contadorDiv.classList.add('hidden');
             }
         }
         
         // Variables globales para el sistema de bloques
         let urlsNuevasGlobales = [];
         let bloqueActual = 0;
         let totalBloques = 0;
         let urlsProcesadas = 0;
         let procesoEnCurso = false;

         // Función para crear todas las ofertas
         function crearTodasOfertas() {
             const resultadosLista = document.getElementById('resultadosLista');
             const tamanoBloque = parseInt(document.getElementById('tamanoBloque').value) || 5;
             const btnCrearTodasOfertas = document.getElementById('btnCrearTodasOfertas');
             const indicadorProgreso = document.getElementById('indicadorProgreso');
             const textoBoton = document.getElementById('textoBoton');
             
             // Si es la primera vez que se ejecuta, preparar las URLs
             if (!procesoEnCurso) {
                 urlsNuevasGlobales = [];
                 
                 // Obtener solo las URLs que son completamente nuevas (verde, no gris ni rojo)
                 // Estas son las que tienen fondo verde y el texto exacto "URL disponible"
                 const elementosVerdes = resultadosLista.querySelectorAll('[class*="bg-green-50"], [class*="dark:bg-green-900"]');
                 
                elementosVerdes.forEach((elemento) => {
                    // Verificar que el elemento tiene fondo verde Y el texto exacto "URL disponible"
                    const statusText = elemento.querySelector('.text-green-700, .dark\\:text-green-300');
                    if (statusText && statusText.textContent.trim() === 'URL disponible') {
                        // Obtener la URL normalizada del atributo data
                        const urlNormalizada = elemento.getAttribute('data-url-normalizada');
                        if (urlNormalizada && urlNormalizada.match(/^https?:\/\//)) {
                            urlsNuevasGlobales.push(urlNormalizada);
                        }
                    }
                });
                 
                 if (urlsNuevasGlobales.length === 0) {
                     alert('No hay URLs nuevas para crear ofertas.');
                     return;
                 }
                 
                 // Calcular bloques
                 totalBloques = Math.ceil(urlsNuevasGlobales.length / tamanoBloque);
                 bloqueActual = 0;
                 urlsProcesadas = 0;
                 procesoEnCurso = true;
                 
                 // Mostrar indicador de progreso
                 indicadorProgreso.classList.remove('hidden');
                 
                 // Confirmar antes de empezar
                 const confirmacion = confirm(`Se procesarán ${urlsNuevasGlobales.length} URLs en ${totalBloques} bloques de ${tamanoBloque} ofertas cada uno. Se abrirán ${tamanoBloque * 2} pestañas por bloque. ¿Continuar?`);
                 if (!confirmacion) {
                     procesoEnCurso = false;
                     indicadorProgreso.classList.add('hidden');
                     return;
                 }
             }
             
             // Verificar si ya se procesaron todas las URLs
             if (urlsProcesadas >= urlsNuevasGlobales.length) {
                 alert('¡Todas las ofertas han sido procesadas!');
                 procesoEnCurso = false;
                 indicadorProgreso.classList.add('hidden');
                 textoBoton.innerHTML = `Crear todas las ofertas (<span id="contadorUrlsNuevas">${urlsNuevasGlobales.length}</span>)`;
                 return;
             }
             
             // Calcular URLs para este bloque
             const inicioBloque = bloqueActual * tamanoBloque;
             const finBloque = Math.min(inicioBloque + tamanoBloque, urlsNuevasGlobales.length);
             const urlsBloque = urlsNuevasGlobales.slice(inicioBloque, finBloque);
             
             // Actualizar indicadores
             actualizarIndicadoresProgreso();
             
             // Deshabilitar botón temporalmente
             btnCrearTodasOfertas.disabled = true;
             textoBoton.innerHTML = `Procesando bloque ${bloqueActual + 1}...`;
             
             // Procesar URLs del bloque actual
             urlsBloque.forEach((url, index) => {
                 const urlCrearOferta = `{{ route('admin.ofertas.create.formularioGeneral') }}?url=${encodeURIComponent(url)}`;
                 
                 // Usar setTimeout para abrir cada pestaña con un delay
                 setTimeout(() => {
                     // Primero abrir el formulario de oferta
                     window.open(urlCrearOferta, `_formulario_${Date.now()}_${bloqueActual}_${index}`);
                     
                     // Luego abrir la URL original de la tienda (con un pequeño delay adicional)
                     setTimeout(() => {
                         window.open(url, `_tienda_${Date.now()}_${bloqueActual}_${index}`);
                     }, 1000); // 1 segundo después de abrir el formulario
                 }, index * 2000); // 2 segundos entre cada par de pestañas
             });
             
             // Actualizar contadores
             urlsProcesadas += urlsBloque.length;
             bloqueActual++;
             
             // Rehabilitar botón después de un delay
             setTimeout(() => {
                 btnCrearTodasOfertas.disabled = false;
                 
                 if (urlsProcesadas < urlsNuevasGlobales.length) {
                     textoBoton.innerHTML = `Siguiente bloque (${urlsNuevasGlobales.length - urlsProcesadas} restantes)`;
                 } else {
                     textoBoton.innerHTML = `Crear todas las ofertas (<span id="contadorUrlsNuevas">${urlsNuevasGlobales.length}</span>)`;
                     procesoEnCurso = false;
                     indicadorProgreso.classList.add('hidden');
                 }
             }, urlsBloque.length * 2000 + 1000); // Tiempo suficiente para que se abran todas las pestañas
         }
         
         // Función para actualizar indicadores de progreso
         function actualizarIndicadoresProgreso() {
             const progresoTexto = document.getElementById('progresoTexto');
             const barraProgreso = document.getElementById('barraProgreso');
             
             const porcentaje = (urlsProcesadas / urlsNuevasGlobales.length) * 100;
             
             progresoTexto.textContent = `Bloque ${bloqueActual + 1} de ${totalBloques} (${urlsProcesadas}/${urlsNuevasGlobales.length})`;
             barraProgreso.style.width = `${porcentaje}%`;
         }
         
        // Función para resetear el estado del sistema de bloques
        function resetearEstadoBloques() {
            urlsNuevasGlobales = [];
            bloqueActual = 0;
            totalBloques = 0;
            urlsProcesadas = 0;
            procesoEnCurso = false;
            
            // Ocultar indicador de progreso
            const indicadorProgreso = document.getElementById('indicadorProgreso');
            const textoBoton = document.getElementById('textoBoton');
            const btnCrearTodasOfertas = document.getElementById('btnCrearTodasOfertas');
            
            indicadorProgreso.classList.add('hidden');
            btnCrearTodasOfertas.disabled = false;
            textoBoton.innerHTML = `Crear todas las ofertas (<span id="contadorUrlsNuevas">0</span>)`;
        }
        
        // Función global para renderizar el contenido con colores
        function renderizarUrlsConColores() {
            const urlsTextarea = document.getElementById('urls');
            const urlsEditable = document.getElementById('urls-editable');
            
            if (!urlsTextarea || !urlsEditable) return;
            
            const texto = urlsTextarea.value;
            const lineas = texto.split('\n');
            
            // Limpiar el contenido del div editable
            urlsEditable.innerHTML = '';
            
            lineas.forEach((linea, index) => {
                const divLinea = document.createElement('div');
                divLinea.style.minHeight = '1.5em';
                
                // Si la línea termina con " -Duplicada", aplicar fondo amarillo
                if (linea.trim().endsWith(' -Duplicada')) {
                    divLinea.style.backgroundColor = '#fef3c7'; // Amarillo claro
                    divLinea.style.padding = '2px 4px';
                    divLinea.style.margin = '1px 0';
                    divLinea.className = 'dark:bg-yellow-900/30';
                }
                
                // Agregar el texto de la línea
                divLinea.textContent = linea;
                urlsEditable.appendChild(divLinea);
            });
            
            // Si está vacío, mostrar placeholder
            if (texto.trim() === '') {
                urlsEditable.textContent = '';
            }
        }

                // Event listeners
         document.addEventListener('DOMContentLoaded', function() {
             const productoInput = document.getElementById('producto_nombre');
             const form = document.getElementById('formVerificarUrls');
             const btnCrearTodasOfertas = document.getElementById('btnCrearTodasOfertas');
             
             // Event listener para el botón de crear todas las ofertas
             btnCrearTodasOfertas.addEventListener('click', crearTodasOfertas);
            
            // Event listener para escribir en el campo de producto
            productoInput.addEventListener('input', function(e) {
                const query = e.target.value;
                
                if (timeoutBusqueda) {
                    clearTimeout(timeoutBusqueda);
                }
                
                if (query.length === 0) {
                    ocultarSugerencias();
                    document.getElementById('producto_id').value = '';
                    productoInput.classList.remove('border-green-500');
                    return;
                }
                
                timeoutBusqueda = setTimeout(() => {
                    buscarProductos(query);
                }, 300);
            });
            
            // Event listener para teclas especiales
            productoInput.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    ocultarSugerencias();
                    productoInput.blur();
                } else if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    navegarSugerencias('abajo');
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    navegarSugerencias('arriba');
                } else if (e.key === 'Enter') {
                    e.preventDefault();
                    if (indiceSeleccionado >= 0) {
                        seleccionarProductoResaltado();
                    }
                }
            });
            
            // Ocultar sugerencias al hacer clic fuera
            document.addEventListener('click', function(e) {
                if (!e.target.closest('#producto_nombre') && !e.target.closest('#producto_sugerencias')) {
                    ocultarSugerencias();
                }
            });
            
            // Event listener para el formulario
            form.addEventListener('submit', verificarUrls);
            
            // Sincronizar div editable con textarea oculto
            const urlsTextarea = document.getElementById('urls');
            const urlsEditable = document.getElementById('urls-editable');
            
            // Función para sincronizar el contenido del div editable con el textarea oculto
            function sincronizarTextarea() {
                // Obtener el texto del div editable (sin HTML)
                const texto = urlsEditable.innerText || urlsEditable.textContent || '';
                urlsTextarea.value = texto;
            }
            
            // Sincronizar cuando el div editable cambia
            urlsEditable.addEventListener('input', function() {
                sincronizarTextarea();
                renderizarUrlsConColores();
            });
            
            // Sincronizar cuando se pega contenido
            urlsEditable.addEventListener('paste', function(e) {
                e.preventDefault();
                const texto = (e.clipboardData || window.clipboardData).getData('text');
                const seleccion = window.getSelection();
                const rango = seleccion.getRangeAt(0);
                rango.deleteContents();
                rango.insertNode(document.createTextNode(texto));
                sincronizarTextarea();
                renderizarUrlsConColores();
            });
            
            // Inicializar renderizado
            renderizarUrlsConColores();
        });
    </script>
</x-app-layout>
