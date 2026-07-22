<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.dashboard') }}">
                <h2 class="font-semibold text-xl text-white leading-tight">Inicio -></h2>
            </a>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Todas las ofertas
            </h2>
        </div>
    </x-slot>

    <div class="py-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mb-4 flex justify-end gap-2">
            <button
                type="button"
                id="toggleFiltros"
                aria-expanded="false"
                class="bg-gray-700 text-white px-4 py-2 rounded hover:bg-gray-800 text-sm">
                Filtros
            </button>
            <a href="{{ route('admin.ofertas.create.formularioGeneral') }}"
                class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">
                + Añadir oferta
            </a>
        </div>
        {{-- Formulario de búsqueda --}}
        <div class="mb-4 text-left">
<form method="GET" class="mb-6 flex flex-wrap items-center gap-2" id="busquedaForm">
    <input type="text" name="busqueda" value="{{ request('busqueda') }}"
        placeholder="Buscar por tienda, producto, modelo, talla o URL"
        class="flex-1 min-w-[200px] px-4 py-2 border rounded bg-white dark:bg-gray-800 text-sm text-gray-800 dark:text-white" />
    <button type="submit"
        class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 text-sm">
        Buscar
    </button>
    <div id="busquedaFormHiddenFilters" class="hidden"></div>
</form>
</div>

        {{-- Filtros avanzados multi-selección --}}
        <div class="mb-4 hidden" id="panelFiltrosAvanzados">
            <div class="flex flex-row flex-nowrap items-end gap-3 overflow-x-auto pb-1">
                {{-- Tienda --}}
                <div class="w-64 relative">
                    <label class="text-sm text-gray-700 dark:text-gray-300">Tienda</label>
                    <input
                        type="text"
                        autocomplete="off"
                        data-filter-input="tienda"
                        class="mt-1 w-full px-3 py-2 border rounded bg-white dark:bg-gray-800 text-sm text-gray-800 dark:text-white"
                        placeholder="Buscar tienda...">
                    <div class="absolute left-0 right-0 mt-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded shadow z-20 hidden"
                        data-suggestions="tienda"></div>

                    <div class="mt-2 flex flex-wrap gap-2" data-selected="tienda">
                        @foreach(($tiendaIdsSeleccionadas ?? []) as $tiendaId)
                            <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-blue-100 text-blue-900 dark:bg-blue-900/40 dark:text-blue-100"
                                data-token="tienda" data-value="{{ $tiendaId }}">
                                {{ $tiendaIdToNombre[$tiendaId] ?? $tiendaId }}
                                <button type="button"
                                    class="ml-2 hover:underline text-blue-700 dark:text-blue-200"
                                    aria-label="Quitar tienda"
                                    data-token-remove="1"
                                    data-filter-type="tienda"
                                    data-value="{{ $tiendaId }}">
                                    ×
                                </button>
                            </span>
                        @endforeach
                    </div>
                </div>

                {{-- Categoría --}}
                <div class="w-64 relative">
                    <label class="text-sm text-gray-700 dark:text-gray-300">Categoría</label>
                    <input
                        type="text"
                        autocomplete="off"
                        data-filter-input="categoria"
                        class="mt-1 w-full px-3 py-2 border rounded bg-white dark:bg-gray-800 text-sm text-gray-800 dark:text-white"
                        placeholder="Buscar categoría...">
                    <div class="absolute left-0 right-0 mt-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded shadow z-20 hidden"
                        data-suggestions="categoria"></div>

                    <div class="mt-2 flex flex-wrap gap-2" data-selected="categoria">
                        @foreach(($categoriaIdsSeleccionadas ?? []) as $categoriaId)
                            <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-blue-100 text-blue-900 dark:bg-blue-900/40 dark:text-blue-100"
                                data-token="categoria" data-value="{{ $categoriaId }}">
                                {{ $categoriaIdToNombre[$categoriaId] ?? $categoriaId }}
                                <button type="button"
                                    class="ml-2 hover:underline text-blue-700 dark:text-blue-200"
                                    aria-label="Quitar categoría"
                                    data-token-remove="1"
                                    data-filter-type="categoria"
                                    data-value="{{ $categoriaId }}">
                                    ×
                                </button>
                            </span>
                        @endforeach
                    </div>
                </div>

                {{-- Producto --}}
                <div class="w-64 relative">
                    <label class="text-sm text-gray-700 dark:text-gray-300">Producto</label>
                    <input
                        type="text"
                        autocomplete="off"
                        data-filter-input="producto"
                        class="mt-1 w-full px-3 py-2 border rounded bg-white dark:bg-gray-800 text-sm text-gray-800 dark:text-white"
                        placeholder="Buscar producto...">
                    <div class="absolute left-0 right-0 mt-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded shadow z-20 hidden"
                        data-suggestions="producto"></div>

                    <div class="mt-2 flex flex-wrap gap-2" data-selected="producto">
                        @foreach(($productoIdsSeleccionadas ?? []) as $productoId)
                            <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-blue-100 text-blue-900 dark:bg-blue-900/40 dark:text-blue-100"
                                data-token="producto" data-value="{{ $productoId }}">
                                {{ $productoIdToLabel[$productoId] ?? $productoId }}
                                <button type="button"
                                    class="ml-2 hover:underline text-blue-700 dark:text-blue-200"
                                    aria-label="Quitar producto"
                                    data-token-remove="1"
                                    data-filter-type="producto"
                                    data-value="{{ $productoId }}">
                                    ×
                                </button>
                            </span>
                        @endforeach
                    </div>
                </div>

                {{-- Unidades --}}
                <div class="w-64 relative">
                    <label class="text-sm text-gray-700 dark:text-gray-300">Unidades</label>
                    <input
                        type="text"
                        autocomplete="off"
                        data-filter-input="unidades"
                        class="mt-1 w-full px-3 py-2 border rounded bg-white dark:bg-gray-800 text-sm text-gray-800 dark:text-white"
                        placeholder="Buscar unidades...">
                    <div class="absolute left-0 right-0 mt-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded shadow z-20 hidden"
                        data-suggestions="unidades"></div>

                    <div class="mt-2 flex flex-wrap gap-2" data-selected="unidades">
                        @foreach(($unidadesSeleccionadas ?? []) as $unidad)
                            <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-blue-100 text-blue-900 dark:bg-blue-900/40 dark:text-blue-100"
                                data-token="unidades" data-value="{{ $unidad }}">
                                {{ $unidad }}
                                <button type="button"
                                    class="ml-2 hover:underline text-blue-700 dark:text-blue-200"
                                    aria-label="Quitar unidades"
                                    data-token-remove="1"
                                    data-filter-type="unidades"
                                    data-value="{{ $unidad }}">
                                    ×
                                </button>
                            </span>
                        @endforeach
                    </div>
                </div>

                {{-- Texto alternativo num --}}
                <div class="w-64 relative">
                    <label class="text-sm text-gray-700 dark:text-gray-300">Texto alternativo (num)</label>
                    <input
                        type="text"
                        autocomplete="off"
                        data-filter-input="texto_alternativo_num"
                        class="mt-1 w-full px-3 py-2 border rounded bg-white dark:bg-gray-800 text-sm text-gray-800 dark:text-white"
                        placeholder="Buscar texto alternativo...">
                    <div class="absolute left-0 right-0 mt-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded shadow z-20 hidden"
                        data-suggestions="texto_alternativo_num"></div>

                    <div class="mt-2 flex flex-wrap gap-2" data-selected="texto_alternativo_num">
                        @foreach(($textoAlternativoNumSeleccionadas ?? []) as $textoAlt)
                            <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-blue-100 text-blue-900 dark:bg-blue-900/40 dark:text-blue-100"
                                data-token="texto_alternativo_num" data-value="{{ $textoAlt }}">
                                {{ $textoAlt }}
                                <button type="button"
                                    class="ml-2 hover:underline text-blue-700 dark:text-blue-200"
                                    aria-label="Quitar texto alternativo"
                                    data-token-remove="1"
                                    data-filter-type="texto_alternativo_num"
                                    data-value="{{ $textoAlt }}">
                                    ×
                                </button>
                            </span>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- Filtros y selector de cantidad por página --}}
        <div class="mb-4 flex justify-between items-center gap-4">
            {{-- Filtro de mostrar --}}
            <div class="flex items-center gap-2">
                <label class="text-sm text-gray-700 dark:text-gray-300">Mostrar ofertas:</label>
                <form method="GET" class="flex items-center gap-2" id="filtroMostrarForm">
                    <input type="hidden" name="busqueda" id="mostrarFormBusqueda" value="{{ request('busqueda','') }}">
                    <input type="hidden" name="perPage" id="mostrarFormPerPage" value="{{ $perPage }}">
                    <div id="mostrarFormHiddenFilters" class="hidden"></div>

                    <label class="flex items-center gap-1">
                        <input type="checkbox" name="mostrar[]" value="si" 
                               {{ in_array('si', $mostrarParaVista) ? 'checked' : '' }} 
                               onchange="this.form.submit()"
                               class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                        <span class="text-sm text-gray-700 dark:text-gray-300">Sí</span>
                    </label>
                    <label class="flex items-center gap-1">
                        <input type="checkbox" name="mostrar[]" value="no" 
                               {{ in_array('no', $mostrarParaVista) ? 'checked' : '' }} 
                               onchange="this.form.submit()"
                               class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                        <span class="text-sm text-gray-700 dark:text-gray-300">No</span>
                    </label>
                </form>
            </div>

            {{-- Selector de cantidad por página --}}
            <div class="flex items-center gap-2">
                <label for="perPage" class="text-sm text-gray-700 dark:text-gray-300">Mostrar:</label>
                <select id="perPage" name="perPage"
                    class="px-2 py-1 pr-8 rounded border bg-white dark:bg-gray-800 text-sm text-gray-800 dark:text-white"
                    onchange="cambiarCantidad()">
                    @foreach ([20, 50, 100, 200] as $cantidad)
                    <option value="{{ $cantidad }}" {{ $perPage == $cantidad ? 'selected' : '' }}>
                        {{ $cantidad }}
                    </option>
                    @endforeach
                </select>
                <span class="text-sm text-gray-700 dark:text-gray-300">resultados por página</span>
            </div>
        </div>

        <div id="ofertasListado">
            @include('admin.ofertas.partials._todas_listado')
        </div>
    </div>

    <script>
        function cambiarCantidad() {
            const cantidad = document.getElementById('perPage').value;
            const params = new URLSearchParams(window.location.search);
            params.set('perPage', cantidad);
            window.location.search = params.toString();
        }
    </script>

    <script>
        // Estado inicial desde backend (refleja query string actual)
        const estadoFiltros = {
            tienda: @json($tiendaIdsSeleccionadas ?? []),
            categoria: @json($categoriaIdsSeleccionadas ?? []),
            producto: @json($productoIdsSeleccionadas ?? []),
            unidades: @json($unidadesSeleccionadas ?? []),
            texto_alternativo_num: @json($textoAlternativoNumSeleccionadas ?? []),
        };

        const sugerenciasFiltrosUrl = @json(route('admin.ofertas.todas.sugerencias_filtros'));

        // Helpers de UI
        function hiddenInput(name, value) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = name;
            input.value = value;
            return input;
        }

        function getMostrarSeleccionado() {
            return Array.from(document.querySelectorAll('input[name="mostrar[]"]:checked')).map(el => el.value);
        }

        function getBusqActual() {
            const el = document.querySelector('input[name="busqueda"]');
            return el ? el.value : '';
        }

        function getPerPageActual() {
            const el = document.getElementById('perPage');
            return el ? el.value : '20';
        }

        function syncHiddenFilters() {
            const busquedaHidden = document.getElementById('busquedaFormHiddenFilters');
            const mostrarHidden = document.getElementById('mostrarFormHiddenFilters');
            const mostrarFormBusqueda = document.getElementById('mostrarFormBusqueda');
            const mostrarFormPerPage = document.getElementById('mostrarFormPerPage');

            if (!busquedaHidden || !mostrarHidden || !mostrarFormBusqueda || !mostrarFormPerPage) {
                return;
            }

            const mostrar = getMostrarSeleccionado();
            const perPage = getPerPageActual();
            const busq = getBusqActual();

            // Mostrar el mismo estado en ambos formularios (para evitar que se pierdan filtros al enviar)
            mostrarFormBusqueda.value = busq;
            mostrarFormPerPage.value = perPage;

            // BusquedaForm: necesita también mostrar[] (porque no tiene checkboxes)
            busquedaHidden.innerHTML = '';
            busquedaHidden.appendChild(hiddenInput('perPage', perPage));
            (mostrar.length ? mostrar : ['si']).forEach(m => busquedaHidden.appendChild(hiddenInput('mostrar[]', m)));

            estadoFiltros.tienda.forEach(v => busquedaHidden.appendChild(hiddenInput('tienda_ids[]', v)));
            estadoFiltros.categoria.forEach(v => busquedaHidden.appendChild(hiddenInput('categoria_ids[]', v)));
            estadoFiltros.producto.forEach(v => busquedaHidden.appendChild(hiddenInput('producto_ids[]', v)));
            estadoFiltros.unidades.forEach(v => busquedaHidden.appendChild(hiddenInput('unidades[]', v)));
            estadoFiltros.texto_alternativo_num.forEach(v => busquedaHidden.appendChild(hiddenInput('texto_alternativo_num[]', v)));

            // FiltroMostrarForm: checkboxes por fuera (mostrar[] ya viene), solo añadimos filtros y perPage
            mostrarHidden.innerHTML = '';
            estadoFiltros.tienda.forEach(v => mostrarHidden.appendChild(hiddenInput('tienda_ids[]', v)));
            estadoFiltros.categoria.forEach(v => mostrarHidden.appendChild(hiddenInput('categoria_ids[]', v)));
            estadoFiltros.producto.forEach(v => mostrarHidden.appendChild(hiddenInput('producto_ids[]', v)));
            estadoFiltros.unidades.forEach(v => mostrarHidden.appendChild(hiddenInput('unidades[]', v)));
            estadoFiltros.texto_alternativo_num.forEach(v => mostrarHidden.appendChild(hiddenInput('texto_alternativo_num[]', v)));
        }

        function setLoading() {
            const listado = document.getElementById('ofertasListado');
            if (!listado) return;
            listado.dataset.loading = '1';
            listado.innerHTML = '<div class="p-4 text-center text-gray-500">Cargando ofertas...</div>';
        }

        async function applyFilters() {
            const listado = document.getElementById('ofertasListado');
            if (!listado) return;

            const requestId = (window.__aplicarFiltrosOfertaReqId = (window.__aplicarFiltrosOfertaReqId || 0) + 1);

            const mostrar = getMostrarSeleccionado();
            const perPage = getPerPageActual();
            const busq = getBusqActual();

            const params = new URLSearchParams();
            if (busq && busq.trim() !== '') {
                params.set('busqueda', busq.trim());
            }
            params.set('perPage', perPage);
            (mostrar.length ? mostrar : ['si']).forEach(m => params.append('mostrar[]', m));

            estadoFiltros.tienda.forEach(v => params.append('tienda_ids[]', v));
            estadoFiltros.categoria.forEach(v => params.append('categoria_ids[]', v));
            estadoFiltros.producto.forEach(v => params.append('producto_ids[]', v));
            estadoFiltros.unidades.forEach(v => params.append('unidades[]', v));
            estadoFiltros.texto_alternativo_num.forEach(v => params.append('texto_alternativo_num[]', v));

            params.delete('page');

            const url = `${window.location.pathname}?${params.toString()}`;
            window.history.replaceState({}, '', url);

            setLoading();

            try {
                const res = await fetch(url, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin'
                });
                const html = await res.text();
                if (requestId === window.__aplicarFiltrosOfertaReqId) {
                    document.getElementById('ofertasListado').innerHTML = html;
                }
            } catch (e) {
                if (requestId === window.__aplicarFiltrosOfertaReqId) {
                    document.getElementById('ofertasListado').innerHTML =
                        '<div class="p-4 text-center text-red-600">Error cargando ofertas.</div>';
                }
            } finally {
                syncHiddenFilters();
            }
        }

        function addToken(filterType, value, label) {
            if (!value && value !== '0') return;

            const container = document.querySelector(`[data-selected="${filterType}"]`);
            if (!container) return;

            const stateList = estadoFiltros[filterType] || [];
            if (stateList.map(String).includes(String(value))) return;

            estadoFiltros[filterType].push(value);

            const span = document.createElement('span');
            span.className = 'inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-blue-100 text-blue-900 dark:bg-blue-900/40 dark:text-blue-100';
            span.dataset.token = filterType;
            span.dataset.value = value;
            span.innerHTML = `
                <span>${label}</span>
                <button type="button"
                    class="ml-2 hover:underline text-blue-700 dark:text-blue-200"
                    aria-label="Quitar ${filterType}"
                    data-token-remove="1"
                    data-filter-type="${filterType}"
                    data-value="${String(value).replace(/"/g, '&quot;')}">×</button>
            `;
            container.appendChild(span);

            syncHiddenFilters();
            applyFilters();
        }

        function removeToken(filterType, value) {
            const list = estadoFiltros[filterType] || [];
            estadoFiltros[filterType] = list.filter(v => String(v) !== String(value));

            const el = document.querySelector(`[data-token="${filterType}"][data-value="${CSS.escape(String(value))}"]`);
            if (el) el.remove();

            syncHiddenFilters();
            applyFilters();
        }

        function clearFilterInput(filterType) {
            const input = document.querySelector(`[data-filter-input="${filterType}"]`);
            if (input) {
                input.value = '';
            }
        }

        function hideSuggestions(filterType) {
            const container = document.querySelector(`[data-suggestions="${filterType}"]`);
            if (!container) return;
            container.classList.add('hidden');
            container.dataset.activeIndex = '-1';
        }

        function setActiveSuggestion(filterType, idx) {
            const container = document.querySelector(`[data-suggestions="${filterType}"]`);
            if (!container) return;

            const buttons = Array.from(container.querySelectorAll('button[data-suggestion-index]'));
            buttons.forEach(btn => {
                const isActive = Number(btn.dataset.suggestionIndex) === Number(idx);
                btn.classList.toggle('bg-blue-500', isActive);
                btn.classList.toggle('text-white', isActive);
                btn.classList.toggle('hover:bg-blue-500', isActive);
                btn.classList.toggle('dark:bg-blue-500', isActive);
                btn.classList.toggle('dark:hover:bg-blue-500', isActive);
            });

            const activeBtn = buttons[idx];
            activeBtn?.scrollIntoView({ block: 'nearest' });
        }

        function showSuggestions(filterType, items, typedQuery = '') {
            const container = document.querySelector(`[data-suggestions="${filterType}"]`);
            if (!container) return;

            container.innerHTML = '';

            const puedeAplicarTextoLibre = filterType === 'texto_alternativo_num' && typedQuery.length >= 1;
            const sugerencias = Array.isArray(items) ? [...items] : [];

            if (sugerencias.length === 0 && !puedeAplicarTextoLibre) {
                hideSuggestions(filterType);
                return;
            }

            if (sugerencias.length === 0 && puedeAplicarTextoLibre) {
                sugerencias.push({
                    id: typedQuery,
                    nombre: typedQuery,
                    etiqueta: `Aplicar: "${typedQuery}"`,
                    esTextoLibre: true,
                });
            }

            sugerencias.forEach((item, i) => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'w-full px-3 py-2 text-left text-sm text-gray-800 dark:text-gray-100 hover:bg-gray-100 dark:hover:bg-gray-700';
                if (item.esTextoLibre) {
                    btn.className += ' font-medium text-blue-700 dark:text-blue-300';
                }
                btn.textContent = item.etiqueta || item.nombre;
                btn.dataset.id = item.id;
                btn.dataset.label = item.nombre;
                btn.dataset.suggestionIndex = String(i);
                btn.addEventListener('click', () => {
                    addToken(filterType, item.id, item.nombre);
                    clearFilterInput(filterType);
                    hideSuggestions(filterType);
                });
                container.appendChild(btn);
            });

            container.classList.remove('hidden');
            container.dataset.activeIndex = '0';
            setActiveSuggestion(filterType, 0);
        }

        async function fetchSugerencias(filterType, q) {
            const panelBase = window.location.pathname.replace(/\/ofertas.*$/, '');

            if (filterType === 'tienda') {
                const url = `${panelBase}/buscador-tienda?q=${encodeURIComponent(q)}`;
                const res = await fetch(url, { credentials: 'same-origin' });
                const data = await res.json();
                return (data || []).map(x => ({ id: x.id, nombre: x.nombre }));
            }

            if (filterType === 'categoria' || filterType === 'producto') {
                const url = `${panelBase}/buscador-producto?q=${encodeURIComponent(q)}`;
                const res = await fetch(url, { credentials: 'same-origin' });
                const data = await res.json();
                const tipoDeseado = filterType === 'categoria' ? 'categoria' : 'producto';
                return (data || [])
                    .filter(x => x.tipo === tipoDeseado)
                    .map(x => ({ id: x.id, nombre: x.nombre }));
            }

            // unidades / texto alternativo: endpoint propio
            const tipoApi = filterType === 'texto_alternativo_num' ? 'texto_alternativo_num' : 'unidades';

            const params = new URLSearchParams();
            params.set('tipo', tipoApi);
            params.set('q', q);

            // Narrow por tienda/categoria/producto/mos trar
            getMostrarSeleccionado().forEach(m => params.append('mostrar[]', m));
            estadoFiltros.tienda.forEach(v => params.append('tienda_ids[]', v));
            estadoFiltros.categoria.forEach(v => params.append('categoria_ids[]', v));
            estadoFiltros.producto.forEach(v => params.append('producto_ids[]', v));

            const url = `${sugerenciasFiltrosUrl}?${params.toString()}`;
            const res = await fetch(url, { credentials: 'same-origin' });
            return await res.json();
        }

        document.addEventListener('click', function (e) {
            const removeBtn = e.target.closest('[data-token-remove="1"]');
            if (removeBtn) {
                const filterType = removeBtn.getAttribute('data-filter-type');
                const value = removeBtn.getAttribute('data-value');
                removeToken(filterType, value);
                return;
            }
        });

        // Evitar pinchar doble en enlaces/botones (también sirve tras AJAX)
        document.addEventListener('click', function (e) {
            const clickable = e.target.closest('a[href], button');
            if (!clickable) return;
            if (clickable.dataset.processing === 'true') {
                e.preventDefault();
                return false;
            }
            clickable.dataset.processing = 'true';
            setTimeout(() => {
                clickable.dataset.processing = 'false';
            }, 2000);
        });

        document.addEventListener('DOMContentLoaded', function () {
            // Panel colapsable para mostrar/ocultar los filtros avanzados
            const toggleBtn = document.getElementById('toggleFiltros');
            const panel = document.getElementById('panelFiltrosAvanzados');

            if (toggleBtn && panel) {
                toggleBtn.addEventListener('click', () => {
                    panel.classList.toggle('hidden');
                    toggleBtn.setAttribute('aria-expanded', String(!panel.classList.contains('hidden')));

                    if (!panel.classList.contains('hidden')) {
                        const firstInput = panel.querySelector('input[data-filter-input]');
                        firstInput?.focus();
                    }
                });
            }

            const busquedaInput = document.querySelector('input[name="busqueda"]');
            if (busquedaInput) {
                busquedaInput.addEventListener('input', () => {
                    const mostrarFormBusqueda = document.getElementById('mostrarFormBusqueda');
                    if (mostrarFormBusqueda) {
                        mostrarFormBusqueda.value = getBusqActual();
                    }
                });
            }

            // Inicializar hidden inputs para los formularios
            syncHiddenFilters();

            // Ocultar suggestions al hacer click fuera
            document.addEventListener('click', function (e) {
                const insideAny = e.target.closest('[data-suggestions]');
                if (!insideAny) {
                    document.querySelectorAll('[data-suggestions]').forEach(el => el.classList.add('hidden'));
                }
            });

            // Autocomplete inputs
            const inputs = document.querySelectorAll('[data-filter-input]');
            inputs.forEach(input => {
                let t = null;
                input.addEventListener('input', function () {
                    const filterType = input.getAttribute('data-filter-input');
                    const q = input.value.trim();

                    if (t) {
                        clearTimeout(t);
                    }

                    if (q.length < 2) {
                        if (filterType === 'texto_alternativo_num' && q.length >= 1) {
                            showSuggestions(filterType, [], q);
                        } else {
                            hideSuggestions(filterType);
                        }
                        return;
                    }

                    t = setTimeout(async () => {
                        try {
                            const items = await fetchSugerencias(filterType, q);
                            showSuggestions(filterType, items, q);
                        } catch (err) {
                            if (filterType === 'texto_alternativo_num') {
                                showSuggestions(filterType, [], q);
                            } else {
                                hideSuggestions(filterType);
                            }
                        }
                    }, 250);
                });

                // Navegación por teclado en sugerencias
                input.addEventListener('keydown', function (e) {
                    const filterType = input.getAttribute('data-filter-input');
                    const container = document.querySelector(`[data-suggestions="${filterType}"]`);
                    const q = input.value.trim();

                    if (e.key === 'Enter' && filterType === 'texto_alternativo_num' && q.length >= 1) {
                        if (!container || container.classList.contains('hidden')) {
                            e.preventDefault();
                            addToken(filterType, q, q);
                            clearFilterInput(filterType);
                            hideSuggestions(filterType);
                            return;
                        }
                    }

                    if (!container || container.classList.contains('hidden')) return;

                    const buttons = Array.from(container.querySelectorAll('button[data-suggestion-index]'));
                    if (!buttons.length) return;

                    let activeIdx = Number(container.dataset.activeIndex ?? '-1');

                    if (e.key === 'ArrowDown') {
                        e.preventDefault();
                        activeIdx = activeIdx < 0 ? 0 : Math.min(activeIdx + 1, buttons.length - 1);
                        container.dataset.activeIndex = String(activeIdx);
                        setActiveSuggestion(filterType, activeIdx);
                    } else if (e.key === 'ArrowUp') {
                        e.preventDefault();
                        activeIdx = activeIdx < 0 ? buttons.length - 1 : Math.max(activeIdx - 1, 0);
                        container.dataset.activeIndex = String(activeIdx);
                        setActiveSuggestion(filterType, activeIdx);
                    } else if (e.key === 'Enter') {
                        e.preventDefault();
                        activeIdx = activeIdx < 0 ? 0 : activeIdx;
                        const btn = buttons[activeIdx];
                        btn?.click();
                    } else if (e.key === 'Escape') {
                        e.preventDefault();
                        container.classList.add('hidden');
                        container.dataset.activeIndex = '-1';
                    }
                });
            });
        });
    </script>
</x-app-layout>