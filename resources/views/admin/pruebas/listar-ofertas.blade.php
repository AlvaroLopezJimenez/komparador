<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.dashboard') }}">
                <h2 class="font-semibold text-xl text-white leading-tight">Inicio -></h2>
            </a>
            <h2 class="font-semibold text-xl text-white leading-tight">Pruebas -> Listar Ofertas Producto</h2>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto py-10 px-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Listar Ofertas de Producto</h1>
            <p class="text-gray-600 dark:text-gray-400 mb-6">
                Esta herramienta muestra todas las ofertas que se tienen en cuenta para determinar el precio del producto, 
                después de aplicar descuentos y chollos. Las ofertas se muestran ordenadas por precio por unidad (de menor a mayor).
            </p>

            {{-- Buscador de Productos --}}
            <div class="mb-6">
                <label class="block mb-2 font-medium text-gray-700 dark:text-gray-200">Producto *</label>
                <div class="flex gap-4 items-start">
                    <div class="relative flex-1">
                        <input type="hidden" name="producto_id" id="producto_id" value="">
                        <input type="text" id="producto_nombre"
                            value=""
                            class="w-full px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border focus:outline-none focus:ring-2 focus:ring-blue-500"
                            placeholder="Escribe para buscar productos..."
                            autocomplete="off">
                        <div id="producto_sugerencias" class="absolute z-50 w-full mt-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md shadow-lg hidden max-h-60 overflow-y-auto"></div>
                    </div>
                    <button type="button" id="btn_buscar_ofertas" onclick="buscarOfertas()"
                        class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition disabled:opacity-50 disabled:cursor-not-allowed"
                        disabled>
                        🔍 Buscar Ofertas
                    </button>
                </div>
            </div>

            {{-- Información del Producto Seleccionado --}}
            <div id="info_producto" class="hidden mb-6 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded-lg">
                <h3 class="font-semibold text-blue-900 dark:text-blue-200 mb-2">Producto Seleccionado:</h3>
                <p id="producto_info_texto" class="text-blue-800 dark:text-blue-300"></p>
            </div>

            {{-- Listado de Ofertas --}}
            <div id="contenedor_ofertas" class="hidden">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Ofertas Encontradas</h2>
                    <span id="total_ofertas" class="text-sm text-gray-600 dark:text-gray-400"></span>
                </div>

                <div id="mensaje_cargando" class="hidden text-center py-8">
                    <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                    <p class="mt-2 text-gray-600 dark:text-gray-400">Buscando ofertas...</p>
                </div>

                <div id="mensaje_sin_ofertas" class="hidden text-center py-8">
                    <p class="text-gray-600 dark:text-gray-400">No se encontraron ofertas para este producto.</p>
                </div>

                <div id="tabla_ofertas" class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Tienda</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Unidades</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Precio Total</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Precio por Unidad</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="tbody_ofertas" class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            {{-- Las ofertas se insertarán aquí dinámicamente --}}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        let timeoutBusqueda = null;
        let productoSeleccionado = false;
        let productosActuales = [];
        let indiceSeleccionado = -1;

        // Función para buscar productos en tiempo real
        async function buscarProductos(query) {
            if (query.length < 2) {
                ocultarSugerencias();
                return;
            }

            try {
                const response = await fetch(`{{ route('admin.pruebas.buscar-productos') }}?q=${encodeURIComponent(query)}`);
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
            const btnBuscarOfertas = document.getElementById('btn_buscar_ofertas');
            
            productoIdInput.value = producto.id;
            productoNombreInput.value = producto.texto_completo;
            productoNombreInput.classList.add('border-green-500');
            productoSeleccionado = true;
            btnBuscarOfertas.disabled = false;
            
            ocultarSugerencias();
            
            // Mostrar información del producto
            const infoProducto = document.getElementById('info_producto');
            const productoInfoTexto = document.getElementById('producto_info_texto');
            productoInfoTexto.textContent = producto.texto_completo;
            infoProducto.classList.remove('hidden');
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

        // Función para buscar ofertas
        async function buscarOfertas() {
            const productoId = document.getElementById('producto_id').value;
            
            if (!productoId) {
                alert('Por favor, selecciona un producto primero');
                return;
            }

            const contenedorOfertas = document.getElementById('contenedor_ofertas');
            const mensajeCargando = document.getElementById('mensaje_cargando');
            const mensajeSinOfertas = document.getElementById('mensaje_sin_ofertas');
            const tablaOfertas = document.getElementById('tabla_ofertas');
            const tbodyOfertas = document.getElementById('tbody_ofertas');
            const totalOfertas = document.getElementById('total_ofertas');
            const btnBuscarOfertas = document.getElementById('btn_buscar_ofertas');

            // Mostrar contenedor y mensaje de carga
            contenedorOfertas.classList.remove('hidden');
            mensajeCargando.classList.remove('hidden');
            mensajeSinOfertas.classList.add('hidden');
            tablaOfertas.classList.add('hidden');
            btnBuscarOfertas.disabled = true;
            btnBuscarOfertas.textContent = '⏳ Buscando...';

            try {
                const response = await fetch('{{ route("admin.pruebas.obtener-ofertas") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        producto_id: productoId
                    })
                });

                const data = await response.json();

                mensajeCargando.classList.add('hidden');

                if (!data.success) {
                    alert('Error: ' + (data.message || 'Error desconocido'));
                    btnBuscarOfertas.disabled = false;
                    btnBuscarOfertas.textContent = '🔍 Buscar Ofertas';
                    return;
                }

                if (data.ofertas.length === 0) {
                    mensajeSinOfertas.classList.remove('hidden');
                    totalOfertas.textContent = '0 ofertas';
                } else {
                    tablaOfertas.classList.remove('hidden');
                    totalOfertas.textContent = `${data.ofertas.length} oferta${data.ofertas.length !== 1 ? 's' : ''}`;
                    
                    // Limpiar tabla
                    tbodyOfertas.innerHTML = '';
                    
                    // Añadir ofertas a la tabla
                    data.ofertas.forEach((oferta, index) => {
                        const tr = document.createElement('tr');
                        tr.className = index % 2 === 0 ? 'bg-white dark:bg-gray-800' : 'bg-gray-50 dark:bg-gray-700';
                        
                        tr.innerHTML = `
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                ${oferta.tienda_nombre}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                ${oferta.unidades}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                ${oferta.precio_total}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-blue-600 dark:text-blue-400">
                                ${oferta.precio_unidad}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <a href="${oferta.url}" target="_blank" 
                                   class="inline-flex items-center px-3 py-1 bg-green-600 hover:bg-green-700 text-white rounded-lg transition">
                                    🔗 Ir
                                </a>
                            </td>
                        `;
                        
                        tbodyOfertas.appendChild(tr);
                    });
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error al buscar ofertas: ' + error.message);
                mensajeCargando.classList.add('hidden');
            } finally {
                btnBuscarOfertas.disabled = false;
                btnBuscarOfertas.textContent = '🔍 Buscar Ofertas';
            }
        }

        // Event listeners
        document.addEventListener('DOMContentLoaded', function() {
            const productoInput = document.getElementById('producto_nombre');
            
            if (!productoInput) {
                console.error('No se encontró el campo de búsqueda de productos');
                return;
            }

            productoInput.addEventListener('input', function(e) {
                const query = e.target.value;
                productoSeleccionado = false;
                
                if (timeoutBusqueda) {
                    clearTimeout(timeoutBusqueda);
                }
                
                if (query.length === 0) {
                    ocultarSugerencias();
                    document.getElementById('producto_id').value = '';
                    productoInput.classList.remove('border-green-500');
                    document.getElementById('btn_buscar_ofertas').disabled = true;
                    document.getElementById('info_producto').classList.add('hidden');
                    document.getElementById('contenedor_ofertas').classList.add('hidden');
                    return;
                }
                
                timeoutBusqueda = setTimeout(() => {
                    buscarProductos(query);
                }, 300);
            });

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

            // Cerrar sugerencias al hacer clic fuera
            document.addEventListener('click', function(e) {
                if (!e.target.closest('#producto_nombre') && !e.target.closest('#producto_sugerencias')) {
                    ocultarSugerencias();
                }
            });
        });
    </script>
</x-app-layout>






















