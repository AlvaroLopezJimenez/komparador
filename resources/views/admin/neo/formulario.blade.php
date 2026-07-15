<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.dashboard') }}">
                <h2 class="font-semibold text-xl text-white leading-tight">Inicio -></h2>
            </a>
            <a href="{{ route('admin.neo.index') }}">
                <h2 class="font-semibold text-xl text-white leading-tight">Neo -></h2>
            </a>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Añadir Neo
            </h2>
        </div>
    </x-slot>

    <div class="max-w-6xl mx-auto py-10 px-4 space-y-8">
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6 border border-gray-200 dark:border-gray-700">
            <div class="mb-6 rounded-xl border border-cyan-200 dark:border-cyan-800 bg-cyan-50/50 dark:bg-cyan-900/10 p-4">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Buscar URLs en csv_ofertas (CSV)</h3>
                <form id="form-buscar-csv-neo-anadir" class="flex flex-wrap items-end gap-3">
                    <div class="flex-1 min-w-[220px]">
                        <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">Buscar por URL (completa o parte)</label>
                        <input type="text" id="csv-neo-anadir-busqueda" name="busqueda"
                            placeholder="https://... o fragmento de la URL"
                            class="w-full px-4 py-2 border rounded bg-white dark:bg-gray-700 text-sm text-gray-800 dark:text-white border-gray-300 dark:border-gray-600" />
                    </div>
                    <div class="flex-1 min-w-[180px]">
                        <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">Buscar por código (EAN, ISBN, UPC, MPN, GTIN)</label>
                        <input type="text" id="csv-neo-anadir-busqueda-codigo" name="busqueda_codigo"
                            placeholder="Código completo o fragmento"
                            class="w-full px-4 py-2 border rounded bg-white dark:bg-gray-700 text-sm text-gray-800 dark:text-white border-gray-300 dark:border-gray-600" />
                    </div>
                    <div class="min-w-[180px]">
                        <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">Tienda</label>
                        <select id="csv-neo-anadir-tienda-id" name="tienda_id"
                            class="w-full px-3 py-2 border rounded bg-white dark:bg-gray-700 text-sm text-gray-800 dark:text-white border-gray-300 dark:border-gray-600">
                            <option value="">Todas</option>
                            @foreach($tiendasConCsv ?? [] as $tienda)
                                <option value="{{ $tienda->id }}">{{ $tienda->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="min-w-[120px]">
                        <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">Stock</label>
                        <select id="csv-neo-anadir-stock" name="stock"
                            class="w-full px-3 py-2 border rounded bg-white dark:bg-gray-700 text-sm text-gray-800 dark:text-white border-gray-300 dark:border-gray-600">
                            <option value="">Todos</option>
                            <option value="1">Con stock</option>
                            <option value="0">Sin stock</option>
                        </select>
                    </div>
                    <button type="submit" id="csv-neo-anadir-btn-buscar"
                        class="px-4 py-2 bg-cyan-600 text-white rounded hover:bg-cyan-700 text-sm disabled:opacity-50">
                        Buscar
                    </button>
                    <button type="button" id="csv-neo-anadir-btn-limpiar"
                        class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600 text-sm hidden">
                        Limpiar
                    </button>
                </form>
                <p id="csv-neo-anadir-estado" class="mt-3 text-sm text-gray-600 dark:text-gray-400"></p>
                <div id="csv-neo-anadir-resultados-wrap" class="hidden mt-4 overflow-x-auto">
                    <p id="csv-neo-anadir-total" class="text-sm text-gray-600 dark:text-gray-400 mb-2"></p>
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-900">
                            <tr>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Tienda</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Nombre</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">URL</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Precio</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Stock</th>
                            </tr>
                        </thead>
                        <tbody id="csv-neo-anadir-tbody" class="divide-y divide-gray-200 dark:divide-gray-700"></tbody>
                    </table>
                    <div id="csv-neo-anadir-paginacion" class="mt-3 flex flex-wrap items-center justify-center gap-2"></div>
                </div>
            </div>

            {{-- Selectores --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                {{-- TIENDA --}}
                <div>
                    <label class="block mb-1 font-medium text-gray-700 dark:text-gray-200">Tienda <span class="text-xs text-gray-500">(tienda o categoría obligatoria)</span></label>
                    <div class="relative">
                        <input type="hidden" id="tienda_id">
                        <input type="text" id="tienda_nombre"
                            class="w-full px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white border border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500"
                            placeholder="Escribe para buscar tiendas..."
                            autocomplete="off">
                        <button type="button" id="tienda_limpiar"
                            class="hidden absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-red-500 text-lg leading-none">&times;</button>
                        <div id="tienda_sugerencias" class="absolute z-50 w-full mt-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md shadow-lg hidden max-h-60 overflow-y-auto"></div>
                    </div>
                </div>

                {{-- CATEGORIA --}}
                <div>
                    <label class="block mb-1 font-medium text-gray-700 dark:text-gray-200">Categoría <span class="text-xs text-gray-500">(tienda o categoría obligatoria)</span></label>
                    <div class="relative">
                        <input type="hidden" id="categoria_id">
                        <input type="text" id="categoria_nombre"
                            class="w-full px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white border border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500"
                            placeholder="Escribe para buscar categorías..."
                            autocomplete="off">
                        <button type="button" id="categoria_limpiar"
                            class="hidden absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-red-500 text-lg leading-none">&times;</button>
                        <div id="categoria_sugerencias" class="absolute z-50 w-full mt-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md shadow-lg hidden max-h-60 overflow-y-auto"></div>
                    </div>
                </div>

                {{-- PRODUCTO --}}
                <div>
                    <label class="block mb-1 font-medium text-gray-700 dark:text-gray-200">Producto <span class="text-xs text-gray-500">(opcional)</span></label>
                    <div class="relative">
                        <input type="hidden" id="producto_id">
                        <input type="text" id="producto_nombre"
                            class="w-full px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white border border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500"
                            placeholder="Escribe para buscar productos..."
                            autocomplete="off">
                        <button type="button" id="producto_limpiar"
                            class="hidden absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-red-500 text-lg leading-none">&times;</button>
                        <div id="producto_sugerencias" class="absolute z-50 w-full mt-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md shadow-lg hidden max-h-60 overflow-y-auto"></div>
                    </div>
                </div>
            </div>

            {{-- Resumen del análisis (encima de las URLs) --}}
            <div id="bloque-resumen" class="hidden mb-6 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/40 p-5">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Resumen del análisis</h3>
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                    <div class="rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 p-4 text-center">
                        <div class="text-2xl font-bold text-gray-900 dark:text-white" id="res-total">0</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">URLs únicas</div>
                    </div>
                    <div class="rounded-lg border border-amber-200 dark:border-amber-700 bg-amber-50 dark:bg-amber-900/20 p-4 text-center">
                        <div class="text-2xl font-bold text-amber-700 dark:text-amber-300" id="res-neo">0</div>
                        <div class="text-xs text-amber-700 dark:text-amber-300 mt-1">Ya en Neo</div>
                    </div>
                    <div class="rounded-lg border border-red-200 dark:border-red-700 bg-red-50 dark:bg-red-900/20 p-4 text-center">
                        <div class="text-2xl font-bold text-red-700 dark:text-red-300" id="res-ofertas">0</div>
                        <div class="text-xs text-red-700 dark:text-red-300 mt-1">Ya en Ofertas</div>
                    </div>
                    <div class="rounded-lg border border-purple-200 dark:border-purple-700 bg-purple-50 dark:bg-purple-900/20 p-4 text-center">
                        <div class="text-2xl font-bold text-purple-700 dark:text-purple-300" id="res-descartadas">0</div>
                        <div class="text-xs text-purple-700 dark:text-purple-300 mt-1">Ya descartadas</div>
                    </div>
                    <div class="rounded-lg border border-slate-200 dark:border-slate-600 bg-slate-50 dark:bg-slate-900/20 p-4 text-center">
                        <div class="text-2xl font-bold text-slate-700 dark:text-slate-300" id="res-invalidas">0</div>
                        <div class="text-xs text-slate-700 dark:text-slate-300 mt-1">No válidas</div>
                    </div>
                    <div class="rounded-lg border border-green-200 dark:border-green-700 bg-green-50 dark:bg-green-900/20 p-4 text-center">
                        <div class="text-2xl font-bold text-green-700 dark:text-green-300" id="res-nuevas">0</div>
                        <div class="text-xs text-green-700 dark:text-green-300 mt-1">Nuevas (se guardarán)</div>
                    </div>
                </div>
                <p id="info-repetidas" class="text-sm text-gray-600 dark:text-gray-400 mt-4 hidden"></p>
            </div>

            {{-- Textarea --}}
            <div class="space-y-4">
                <div>
                    <label class="block mb-2 font-medium text-gray-700 dark:text-gray-200">URLs (una por línea)</label>
                    <textarea id="urls-textarea" rows="12"
                        class="w-full px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white border border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500 font-mono text-sm"
                        placeholder="https://ejemplo.com/oferta-1&#10;https://ejemplo.com/oferta-2"></textarea>
                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                        Tras analizar, las URLs que <strong class="text-red-600 dark:text-red-400">ya existen</strong> (y no se guardarán) se resaltan en rojo.
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <button type="button" id="btn-analizar"
                        class="inline-flex items-center bg-blue-600 hover:bg-blue-700 text-white font-semibold px-6 py-3 rounded-md transition disabled:opacity-50 disabled:cursor-not-allowed">
                        Analizar URLs en Neo
                    </button>
                    <button type="button" id="btn-guardar" disabled
                        class="inline-flex items-center bg-green-600 hover:bg-green-700 text-white font-semibold px-6 py-3 rounded-md transition disabled:opacity-50 disabled:cursor-not-allowed">
                        Guardar en Neo
                    </button>
                </div>
                <p id="mensaje-global" class="text-sm hidden"></p>
                <div id="detalle-urls" class="hidden mt-3 space-y-1 text-sm font-mono"></div>
            </div>
        </div>

        {{-- Resultado del guardado --}}
        <div id="bloque-resultado-guardado" class="hidden bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-xl p-6">
            <h3 class="text-lg font-semibold text-green-900 dark:text-green-100 mb-2">Guardado completado</h3>
            <p id="texto-resultado-guardado" class="text-sm text-green-800 dark:text-green-200"></p>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const urlAnalizar = @json(route('admin.neo.anadir.analizar'));
            const urlGuardar = @json(route('admin.neo.anadir.guardar'));
            const urlBuscarCsvOfertas = @json(route('admin.neo.crear-masivo.buscar-csv-ofertas'));
            const urlBuscarProductos = @json(route('admin.ofertas.buscar.productos'));
            const urlTiendasDisponibles = @json(route('admin.ofertas.tiendas.disponibles'));
            const urlBuscarCategorias = @json(url('/panel-privado/productos/buscar/categorias'));
            const urlLimpiarUrl = @json(route('admin.ofertas.limpiar.url'));
            const csrf = @json(csrf_token());

            const textarea = document.getElementById('urls-textarea');
            const btnAnalizar = document.getElementById('btn-analizar');
            const btnGuardar = document.getElementById('btn-guardar');
            const mensajeGlobal = document.getElementById('mensaje-global');
            const bloqueResumen = document.getElementById('bloque-resumen');
            const bloqueResGuardado = document.getElementById('bloque-resultado-guardado');
            const textoResGuardado = document.getElementById('texto-resultado-guardado');
            const infoRepetidas = document.getElementById('info-repetidas');

            const tiendaIdInput = document.getElementById('tienda_id');
            const categoriaIdInput = document.getElementById('categoria_id');
            const productoIdInput = document.getElementById('producto_id');

            let urlsExistentes = new Set();

            async function limpiarUrlViaApi(url) {
                if (!url || !url.trim()) {
                    return { url_limpia: url || '' };
                }
                try {
                    const res = await fetch(urlLimpiarUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrf,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify({ url: url.trim() }),
                    });
                    if (!res.ok) return { url_limpia: url.trim() };
                    const data = await res.json();
                    return { url_limpia: data.url_limpia ?? url.trim() };
                } catch (e) {
                    return { url_limpia: url.trim() };
                }
            }

            async function limpiarLineasTextarea() {
                const lineas = textarea.value.split('\n');
                let cambio = false;
                const nuevas = [];
                for (const linea of lineas) {
                    const original = linea.trim();
                    if (original === '') {
                        nuevas.push('');
                        continue;
                    }
                    const { url_limpia } = await limpiarUrlViaApi(original);
                    if (url_limpia !== original) cambio = true;
                    nuevas.push(url_limpia || original);
                }
                if (cambio) {
                    textarea.value = nuevas.join('\n');
                }
            }

            function mostrarMensaje(texto, esError) {
                mensajeGlobal.textContent = texto;
                mensajeGlobal.classList.remove('hidden', 'text-red-600', 'text-green-600', 'dark:text-red-400', 'dark:text-green-400');
                mensajeGlobal.classList.add(esError ? 'text-red-600' : 'text-green-600', esError ? 'dark:text-red-400' : 'dark:text-green-400');
            }

            // ------- Selector genérico con búsqueda -------
            function configurarSelector(prefijo, opciones) {
                const hidden = document.getElementById(prefijo + '_id');
                const input = document.getElementById(prefijo + '_nombre');
                const sugerencias = document.getElementById(prefijo + '_sugerencias');
                const limpiar = document.getElementById(prefijo + '_limpiar');
                let debounce = null;
                let itemsActuales = [];
                let indiceActivo = -1;

                function ocultar() {
                    sugerencias.classList.add('hidden');
                    sugerencias.innerHTML = '';
                    itemsActuales = [];
                    indiceActivo = -1;
                }

                function seleccionar(id, texto) {
                    hidden.value = id;
                    input.value = texto;
                    input.classList.add('border-green-500');
                    limpiar.classList.remove('hidden');
                    ocultar();
                    if (opciones.onSelect) opciones.onSelect();
                }

                function limpiarSeleccion() {
                    hidden.value = '';
                    input.value = '';
                    input.classList.remove('border-green-500');
                    limpiar.classList.add('hidden');
                    ocultar();
                    if (opciones.onSelect) opciones.onSelect();
                }

                function marcarActivo() {
                    const nodos = sugerencias.querySelectorAll('[data-idx]');
                    nodos.forEach(function(n) {
                        const idx = parseInt(n.getAttribute('data-idx'), 10);
                        if (idx === indiceActivo) {
                            n.classList.add('bg-blue-100', 'dark:bg-gray-600');
                            n.scrollIntoView({ block: 'nearest' });
                        } else {
                            n.classList.remove('bg-blue-100', 'dark:bg-gray-600');
                        }
                    });
                }

                function render(items) {
                    sugerencias.innerHTML = '';
                    itemsActuales = items || [];
                    indiceActivo = -1;
                    if (!items || items.length === 0) {
                        sugerencias.innerHTML = '<div class="px-4 py-2 text-gray-500 dark:text-gray-400 text-sm">Sin resultados</div>';
                        sugerencias.classList.remove('hidden');
                        return;
                    }
                    items.forEach(function(item, index) {
                        const div = document.createElement('div');
                        div.className = 'px-4 py-2 cursor-pointer hover:bg-blue-50 dark:hover:bg-gray-700 text-sm text-gray-800 dark:text-gray-100';
                        div.setAttribute('data-idx', index);
                        div.textContent = item.texto;
                        div.addEventListener('click', function() {
                            seleccionar(item.id, item.texto);
                        });
                        div.addEventListener('mousemove', function() {
                            if (indiceActivo !== index) {
                                indiceActivo = index;
                                marcarActivo();
                            }
                        });
                        sugerencias.appendChild(div);
                    });
                    sugerencias.classList.remove('hidden');
                }

                input.addEventListener('input', function() {
                    hidden.value = '';
                    input.classList.remove('border-green-500');
                    limpiar.classList.toggle('hidden', input.value.trim() === '');
                    const q = input.value.trim();
                    if (q.length < 2) {
                        ocultar();
                        return;
                    }
                    clearTimeout(debounce);
                    debounce = setTimeout(async function() {
                        try {
                            const items = await opciones.buscar(q);
                            render(items);
                        } catch (e) {
                            ocultar();
                        }
                    }, 250);
                });

                // Navegación con teclado: flechas para moverse, Enter para seleccionar
                input.addEventListener('keydown', function(e) {
                    if (sugerencias.classList.contains('hidden') || itemsActuales.length === 0) {
                        return;
                    }
                    if (e.key === 'ArrowDown') {
                        e.preventDefault();
                        indiceActivo = (indiceActivo + 1) % itemsActuales.length;
                        marcarActivo();
                    } else if (e.key === 'ArrowUp') {
                        e.preventDefault();
                        indiceActivo = (indiceActivo - 1 + itemsActuales.length) % itemsActuales.length;
                        marcarActivo();
                    } else if (e.key === 'Enter') {
                        if (indiceActivo >= 0 && indiceActivo < itemsActuales.length) {
                            e.preventDefault();
                            const item = itemsActuales[indiceActivo];
                            seleccionar(item.id, item.texto);
                        }
                    } else if (e.key === 'Escape') {
                        ocultar();
                    }
                });

                limpiar.addEventListener('click', limpiarSeleccion);

                document.addEventListener('click', function(e) {
                    if (!sugerencias.contains(e.target) && e.target !== input) {
                        ocultar();
                    }
                });

                return {
                    seleccionar: seleccionar,
                    limpiar: limpiarSeleccion,
                };
            }

            // Cache de tiendas (para el buscador y la detección automática por URL)
            let tiendasCache = null;
            async function obtenerTiendas() {
                if (tiendasCache) return tiendasCache;
                try {
                    const res = await fetch(urlTiendasDisponibles);
                    tiendasCache = await res.json();
                } catch (e) {
                    tiendasCache = [];
                }
                return tiendasCache;
            }

            const selectorTienda = configurarSelector('tienda', {
                buscar: async function(q) {
                    const todas = await obtenerTiendas();
                    return (todas || [])
                        .filter(t => (t.nombre || '').toLowerCase().includes(q.toLowerCase()))
                        .slice(0, 15)
                        .map(t => ({ id: t.id, texto: t.nombre }));
                }
            });

            const selectorCategoria = configurarSelector('categoria', {
                buscar: async function(q) {
                    const res = await fetch(urlBuscarCategorias + '?q=' + encodeURIComponent(q));
                    const cats = await res.json();
                    return (cats || []).map(c => ({ id: c.id, texto: c.nombre }));
                }
            });

            const selectorProducto = configurarSelector('producto', {
                buscar: async function(q) {
                    const params = new URLSearchParams({ q: q });
                    if (categoriaIdInput.value) {
                        params.set('categoria_id', categoriaIdInput.value);
                    }
                    const res = await fetch(urlBuscarProductos + '?' + params.toString());
                    const prods = await res.json();
                    return (prods || []).map(p => ({ id: p.id, texto: p.texto_completo || p.nombre }));
                }
            });

            // ------- Detección automática de tienda a partir de las URLs pegadas -------
            function extraerDominioNormalizado(url) {
                try {
                    if (!url || !url.trim()) return null;
                    let urlCompleta = url.trim();
                    if (!/^https?:\/\//i.test(urlCompleta)) {
                        urlCompleta = 'https://' + urlCompleta;
                    }
                    let hostname;
                    try {
                        hostname = new URL(urlCompleta).hostname;
                    } catch (e) {
                        const match = urlCompleta.match(/^(?:https?:\/\/)?(?:www\.)?([^\/\s]+)/i);
                        hostname = match && match[1] ? match[1] : null;
                    }
                    if (!hostname) return null;
                    return hostname.toLowerCase().replace(/^www\./, '');
                } catch (error) {
                    return null;
                }
            }

            function normalizarUrlTienda(urlTienda) {
                if (!urlTienda || !urlTienda.trim()) return null;
                let normalizada = urlTienda.trim().toLowerCase();
                normalizada = normalizada.replace(/^https?:\/\//, '');
                normalizada = normalizada.replace(/^www\./, '');
                normalizada = normalizada.replace(/\/.*$/, '');
                normalizada = normalizada.replace(/\/$/, '');
                if (!normalizada || !normalizada.includes('.')) return null;
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

            let detectTiendaTimeout = null;
            async function detectarTiendaDesdeTextarea() {
                const lineas = textarea.value.split('\n').map(l => l.trim()).filter(l => l.length > 0);
                if (lineas.length === 0) return;

                const tiendas = await obtenerTiendas();
                if (!Array.isArray(tiendas) || tiendas.length === 0) return;

                let tiendaComun; // undefined = aún sin decidir, null = no todas coinciden
                for (const linea of lineas) {
                    const dominio = extraerDominioNormalizado(linea);
                    if (!dominio) { tiendaComun = null; break; }
                    const t = buscarTiendaEnListaPorUrl(dominio, tiendas);
                    if (!t) { tiendaComun = null; break; }
                    if (tiendaComun === undefined) {
                        tiendaComun = t;
                    } else if (tiendaComun.id !== t.id) {
                        tiendaComun = null;
                        break;
                    }
                }

                // Solo se autoselecciona si TODAS las URLs son de la misma tienda
                if (tiendaComun && tiendaComun.id) {
                    if (tiendaIdInput.value !== String(tiendaComun.id)) {
                        selectorTienda.seleccionar(tiendaComun.id, tiendaComun.nombre);
                    }
                }
            }

            textarea.addEventListener('input', function() {
                clearTimeout(detectTiendaTimeout);
                detectTiendaTimeout = setTimeout(detectarTiendaDesdeTextarea, 300);
            });
            let limpiarPasteTimeout = null;
            textarea.addEventListener('paste', function() {
                clearTimeout(limpiarPasteTimeout);
                limpiarPasteTimeout = setTimeout(async function() {
                    await limpiarLineasTextarea();
                    detectarTiendaDesdeTextarea();
                }, 50);
            });

            // ------- Buscador CSV (csv_ofertas) -------
            function escapeHtmlNeoAnadir(texto) {
                const div = document.createElement('div');
                div.textContent = texto == null ? '' : String(texto);
                return div.innerHTML;
            }

            function formatearPrecioCsvNeoAnadir(valor) {
                if (valor === null || valor === undefined || valor === '') return '—';
                const num = Number(valor);
                if (Number.isNaN(num)) return '—';
                return num.toLocaleString('es-ES', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' €';
            }

            function formatearStockCsvNeoAnadir(stock) {
                if (stock === null || stock === undefined || stock === '') return '—';
                if (Number(stock) === 1) return 'Sí';
                return 'No';
            }

            function hayFiltrosCsvNeoAnadir() {
                return !!(
                    (document.getElementById('csv-neo-anadir-busqueda')?.value || '').trim() ||
                    (document.getElementById('csv-neo-anadir-busqueda-codigo')?.value || '').trim() ||
                    (document.getElementById('csv-neo-anadir-tienda-id')?.value || '') ||
                    (document.getElementById('csv-neo-anadir-stock')?.value || '')
                );
            }

            function actualizarBotonLimpiarCsvNeoAnadir() {
                const btn = document.getElementById('csv-neo-anadir-btn-limpiar');
                if (btn) btn.classList.toggle('hidden', !hayFiltrosCsvNeoAnadir());
            }

            function pegarUrlsCsvNeoAnadir(urls) {
                const lista = Array.isArray(urls) ? urls.filter(Boolean).map(String) : [];
                textarea.value = lista.join('\n');
                detectarTiendaDesdeTextarea();
            }

            function renderTablaCsvNeoAnadir(filas) {
                const tbody = document.getElementById('csv-neo-anadir-tbody');
                const wrap = document.getElementById('csv-neo-anadir-resultados-wrap');
                if (!tbody || !wrap) return;
                if (!Array.isArray(filas) || filas.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="5" class="px-3 py-6 text-center text-gray-500">No hay filas que coincidan con los filtros.</td></tr>';
                    wrap.classList.remove('hidden');
                    return;
                }
                tbody.innerHTML = filas.map(function(fila) {
                    const nombre = fila.nombre
                        ? '<span class="line-clamp-2" title="' + escapeHtmlNeoAnadir(fila.nombre) + '">' + escapeHtmlNeoAnadir(fila.nombre) + '</span>'
                        : '<span class="text-gray-400">—</span>';
                    const url = fila.url || '';
                    return '<tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">'
                        + '<td class="px-3 py-2 whitespace-nowrap text-gray-800 dark:text-gray-200">' + escapeHtmlNeoAnadir(fila.tienda || '—') + '</td>'
                        + '<td class="px-3 py-2 max-w-xs text-gray-800 dark:text-gray-200">' + nombre + '</td>'
                        + '<td class="px-3 py-2 max-w-md"><a href="' + escapeHtmlNeoAnadir(url) + '" target="_blank" rel="noopener" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 break-all">' + escapeHtmlNeoAnadir(url) + '</a></td>'
                        + '<td class="px-3 py-2 whitespace-nowrap">' + escapeHtmlNeoAnadir(formatearPrecioCsvNeoAnadir(fila.precio)) + '</td>'
                        + '<td class="px-3 py-2 whitespace-nowrap">' + escapeHtmlNeoAnadir(formatearStockCsvNeoAnadir(fila.stock)) + '</td>'
                        + '</tr>';
                }).join('');
                wrap.classList.remove('hidden');
            }

            function renderPaginacionCsvNeoAnadir(pagination, onPage) {
                const cont = document.getElementById('csv-neo-anadir-paginacion');
                if (!cont) return;
                if (!pagination || pagination.last_page <= 1) {
                    cont.innerHTML = '';
                    return;
                }
                const actual = pagination.current_page;
                const ultima = pagination.last_page;
                const botones = [];
                if (actual > 1) {
                    botones.push('<button type="button" class="csv-neo-anadir-pagina px-3 py-1 text-sm rounded border border-gray-300 dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700" data-page="' + (actual - 1) + '">Anterior</button>');
                }
                botones.push('<span class="text-sm text-gray-600 dark:text-gray-400">Página ' + actual + ' de ' + ultima + '</span>');
                if (actual < ultima) {
                    botones.push('<button type="button" class="csv-neo-anadir-pagina px-3 py-1 text-sm rounded border border-gray-300 dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700" data-page="' + (actual + 1) + '">Siguiente</button>');
                }
                cont.innerHTML = botones.join('');
                cont.querySelectorAll('.csv-neo-anadir-pagina').forEach(function(btn) {
                    btn.addEventListener('click', function() {
                        const page = parseInt(btn.dataset.page, 10);
                        if (!Number.isNaN(page) && onPage) onPage(page);
                    });
                });
            }

            async function buscarCsvNeoAnadir(page, opciones) {
                opciones = opciones || {};
                const pegarUrls = opciones.pegarUrls !== false;
                const btnBuscar = document.getElementById('csv-neo-anadir-btn-buscar');
                const estado = document.getElementById('csv-neo-anadir-estado');
                const totalEl = document.getElementById('csv-neo-anadir-total');
                const params = new URLSearchParams();
                const busqueda = (document.getElementById('csv-neo-anadir-busqueda')?.value || '').trim();
                const busquedaCodigo = (document.getElementById('csv-neo-anadir-busqueda-codigo')?.value || '').trim();
                const tiendaId = document.getElementById('csv-neo-anadir-tienda-id')?.value || '';
                const stock = document.getElementById('csv-neo-anadir-stock')?.value || '';
                if (busqueda) params.set('busqueda', busqueda);
                if (busquedaCodigo) params.set('busqueda_codigo', busquedaCodigo);
                if (tiendaId) params.set('tienda_id', tiendaId);
                if (stock !== '') params.set('stock', stock);
                params.set('perPage', '50');
                params.set('page', String(page || 1));

                if (btnBuscar) btnBuscar.disabled = true;
                if (estado) estado.textContent = 'Buscando…';
                try {
                    const res = await fetch(urlBuscarCsvOfertas + '?' + params.toString(), {
                        cache: 'no-store',
                        headers: { 'Accept': 'application/json' },
                    });
                    if (!res.ok) throw new Error('Error HTTP ' + res.status);
                    const data = await res.json();
                    renderTablaCsvNeoAnadir(data.filas || []);
                    renderPaginacionCsvNeoAnadir(data.pagination || null, function(p) {
                        buscarCsvNeoAnadir(p, { pegarUrls: false });
                    });
                    if (totalEl) {
                        const total = data.total || 0;
                        totalEl.textContent = total > 0
                            ? total.toLocaleString('es-ES') + ' fila(s) encontrada(s) (vista previa paginada)'
                            : 'No hay filas que coincidan con los filtros.';
                    }
                    if (pegarUrls) {
                        const urls = Array.isArray(data.urls) ? data.urls : [];
                        pegarUrlsCsvNeoAnadir(urls);
                        let msg = urls.length > 0
                            ? urls.length.toLocaleString('es-ES') + ' URL' + (urls.length !== 1 ? 's' : '') + ' pegada' + (urls.length !== 1 ? 's' : '') + ' en el textarea.'
                            : 'No se han pegado URLs (sin coincidencias).';
                        if (data.truncado) {
                            msg += ' Solo las primeras ' + (data.limite_urls || 0).toLocaleString('es-ES') + ' de ' + (data.total || 0).toLocaleString('es-ES') + ' coincidencias.';
                        }
                        if (estado) estado.textContent = msg;
                    } else if (estado) {
                        estado.textContent = '';
                    }
                    actualizarBotonLimpiarCsvNeoAnadir();
                } catch (e) {
                    if (estado) estado.textContent = 'Error al buscar: ' + (e.message || 'desconocido');
                    renderTablaCsvNeoAnadir([]);
                    renderPaginacionCsvNeoAnadir(null, null);
                    if (totalEl) totalEl.textContent = '';
                } finally {
                    if (btnBuscar) btnBuscar.disabled = false;
                }
            }

            const formBuscarCsvNeoAnadir = document.getElementById('form-buscar-csv-neo-anadir');
            if (formBuscarCsvNeoAnadir) {
                formBuscarCsvNeoAnadir.addEventListener('submit', function(e) {
                    e.preventDefault();
                    buscarCsvNeoAnadir(1, { pegarUrls: true });
                });
            }

            const btnLimpiarCsvNeoAnadir = document.getElementById('csv-neo-anadir-btn-limpiar');
            if (btnLimpiarCsvNeoAnadir) {
                btnLimpiarCsvNeoAnadir.addEventListener('click', function() {
                    ['csv-neo-anadir-busqueda', 'csv-neo-anadir-busqueda-codigo', 'csv-neo-anadir-tienda-id', 'csv-neo-anadir-stock'].forEach(function(id) {
                        const el = document.getElementById(id);
                        if (el) el.value = '';
                    });
                    const estado = document.getElementById('csv-neo-anadir-estado');
                    const totalEl = document.getElementById('csv-neo-anadir-total');
                    const wrap = document.getElementById('csv-neo-anadir-resultados-wrap');
                    if (estado) estado.textContent = '';
                    if (totalEl) totalEl.textContent = '';
                    if (wrap) wrap.classList.add('hidden');
                    renderTablaCsvNeoAnadir([]);
                    renderPaginacionCsvNeoAnadir(null, null);
                    btnLimpiarCsvNeoAnadir.classList.add('hidden');
                });
            }

            ['csv-neo-anadir-busqueda', 'csv-neo-anadir-busqueda-codigo', 'csv-neo-anadir-tienda-id', 'csv-neo-anadir-stock'].forEach(function(id) {
                const el = document.getElementById(id);
                if (!el) return;
                el.addEventListener('input', actualizarBotonLimpiarCsvNeoAnadir);
                el.addEventListener('change', actualizarBotonLimpiarCsvNeoAnadir);
            });

            // ------- Analizar -------
            btnAnalizar.addEventListener('click', async function() {
                bloqueResGuardado.classList.add('hidden');
                mensajeGlobal.classList.add('hidden');
                btnGuardar.disabled = true;
                urlsExistentes = new Set();

                btnAnalizar.disabled = true;
                try {
                    const res = await fetch(urlAnalizar, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrf,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify({ urls: textarea.value }),
                    });
                    let data;
                    try {
                        data = await res.json();
                    } catch (parseErr) {
                        mostrarMensaje('Error del servidor (código ' + res.status + ').', true);
                        return;
                    }
                    if (!res.ok || !data.success) {
                        mostrarMensaje(data.message || 'Error al analizar.', true);
                        return;
                    }

                    document.getElementById('res-total').textContent = data.resumen.total;
                    document.getElementById('res-neo').textContent = data.resumen.ya_en_neo;
                    document.getElementById('res-ofertas').textContent = data.resumen.ya_en_ofertas;
                    document.getElementById('res-descartadas').textContent = data.resumen.ya_descartadas;
                    document.getElementById('res-invalidas').textContent = data.resumen.invalidas ?? 0;
                    document.getElementById('res-nuevas').textContent = data.resumen.nuevas;
                    bloqueResumen.classList.remove('hidden');

                    if (data.repetidas_en_texto) {
                        infoRepetidas.textContent = 'Has repetido alguna URL en el texto (se analiza una vez por URL única). ' +
                            'Líneas con URL: ' + data.urls_en_texto + ', únicas: ' + data.urls_unicas + '.';
                        infoRepetidas.classList.remove('hidden');
                    } else {
                        infoRepetidas.classList.add('hidden');
                    }

                    // Reescribir textarea con URLs limpias (válidas) devueltas por el servidor.
                    const urlsLimpias = data.resultados
                        .filter(r => !r.invalida && !r.error)
                        .map(r => r.url);
                    if (urlsLimpias.length > 0) {
                        textarea.value = urlsLimpias.join('\n');
                    }

                    urlsExistentes = new Set(data.resultados.filter(r => r.existe).map(r => r.url));
                    pintarTextarea(data.resultados);

                    if (data.resumen.nuevas > 0) {
                        btnGuardar.disabled = false;
                        mostrarMensaje('Análisis listo: se guardarán ' + data.resumen.nuevas + ' URL(s) nueva(s). Las existentes se muestran en rojo.', false);
                    } else {
                        mostrarMensaje('Ninguna URL es nueva: todas ya existen en neo, ofertas o están descartadas.', true);
                    }
                } catch (e) {
                    mostrarMensaje('Error de red o respuesta inválida.', true);
                } finally {
                    btnAnalizar.disabled = false;
                }
            });

            // Muestra las existentes en rojo bajo el textarea (el textarea nativo no colorea por línea).
            function pintarTextarea(resultados) {
                const cont = document.getElementById('detalle-urls');
                cont.classList.remove('hidden');
                cont.innerHTML = '';
                resultados.forEach(function(r) {
                    const div = document.createElement('div');
                    const noGuardable = r.existe || r.invalida || r.error;
                    div.className = 'break-all ' + (noGuardable
                        ? 'text-red-600 dark:text-red-400 line-through'
                        : 'text-green-700 dark:text-green-400');
                    const partes = [];
                    if (r.en_neo) partes.push('neo');
                    if (r.en_ofertas) partes.push('ofertas');
                    if (r.en_descartada) partes.push('descartada');
                    let etiqueta = '';
                    if (r.error) {
                        etiqueta = ' (' + r.error + ')';
                    } else if (partes.length) {
                        etiqueta = ' (ya en ' + partes.join(', ') + ')';
                    }
                    let texto = r.url + etiqueta;
                    if (r.url_original && r.url_original !== r.url) {
                        texto = r.url + ' ← ' + r.url_original + etiqueta;
                    }
                    div.textContent = texto;
                    cont.appendChild(div);
                });
            }

            // ------- Guardar -------
            btnGuardar.addEventListener('click', async function() {
                if (!tiendaIdInput.value && !categoriaIdInput.value) {
                    mostrarMensaje('Debes seleccionar como mínimo una tienda o una categoría.', true);
                    return;
                }

                btnGuardar.disabled = true;
                btnAnalizar.disabled = true;
                bloqueResGuardado.classList.add('hidden');
                try {
                    const res = await fetch(urlGuardar, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrf,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify({
                            urls: textarea.value,
                            tienda_id: tiendaIdInput.value || null,
                            categoria_id: categoriaIdInput.value || null,
                            producto_id: productoIdInput.value || null,
                        }),
                    });
                    let data;
                    try {
                        data = await res.json();
                    } catch (parseErr) {
                        mostrarMensaje('Error del servidor (código ' + res.status + ').', true);
                        return;
                    }
                    if (!res.ok || !data.success) {
                        mostrarMensaje(data.message || 'Error al guardar.', true);
                        return;
                    }

                    bloqueResGuardado.classList.remove('hidden');
                    textoResGuardado.textContent = 'Se han guardado ' + data.total_guardadas + ' URL(s) en neo. ' +
                        'Descartadas por existir ya: ' + data.total_descartadas + '.';
                    mostrarMensaje('Guardado correcto.', false);
                    btnGuardar.disabled = true;
                } catch (e) {
                    mostrarMensaje('Error de red al guardar.', true);
                } finally {
                    btnAnalizar.disabled = false;
                }
            });
        });
    </script>
</x-app-layout>
