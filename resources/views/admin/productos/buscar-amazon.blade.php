<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.dashboard') }}">
                <h2 class="font-semibold text-xl text-white leading-tight">Inicio -></h2>
            </a>
            <a href="{{ route('admin.productos.index') }}">
                <h2 class="font-semibold text-xl text-white leading-tight">Productos -></h2>
            </a>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Buscar productos (Amazon / AliExpress)
            </h2>
        </div>
    </x-slot>

    <div class="max-w-6xl mx-auto py-10 px-4 space-y-8">
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6 border border-gray-200 dark:border-gray-700">
            <p id="texto-ayuda-busqueda" class="text-gray-600 dark:text-gray-400 mb-6">
                Busca en <strong>Amazon</strong>, <strong>AliExpress</strong> o en <strong>ambos</strong> (por defecto).
                Opcionalmente indica <strong>palabras coincidentes</strong>: cada palabra debe aparecer en el título del producto
                (se ignoran guiones y espacios, p. ej. <code class="text-xs bg-gray-100 dark:bg-gray-700 px-1 rounded">9060 XT</code> coincide con
                <code class="text-xs bg-gray-100 dark:bg-gray-700 px-1 rounded">9060XT</code> o
                <code class="text-xs bg-gray-100 dark:bg-gray-700 px-1 rounded">E589</code> con <code class="text-xs bg-gray-100 dark:bg-gray-700 px-1 rounded">E-589</code>).
            </p>

            <form id="form-buscar-amazon" class="space-y-4">
                <fieldset>
                    <legend class="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-200">Buscar en</legend>
                    <div class="flex flex-wrap gap-4">
                        <label class="inline-flex items-center gap-2 cursor-pointer select-none">
                            <input type="radio" name="proveedor" value="ambos" checked
                                class="border-gray-300 text-indigo-600 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Ambos</span>
                        </label>
                        <label class="inline-flex items-center gap-2 cursor-pointer select-none">
                            <input type="radio" name="proveedor" value="amazon"
                                class="border-gray-300 text-amber-600 focus:ring-amber-500 dark:border-gray-600 dark:bg-gray-700">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Amazon</span>
                        </label>
                        <label class="inline-flex items-center gap-2 cursor-pointer select-none">
                            <input type="radio" name="proveedor" value="aliexpress"
                                class="border-gray-300 text-red-600 focus:ring-red-500 dark:border-gray-600 dark:bg-gray-700">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-200">AliExpress</span>
                        </label>
                    </div>
                </fieldset>
                <div>
                    <label for="q-amazon" class="block mb-2 font-medium text-gray-700 dark:text-gray-200">
                        Texto de búsqueda
                    </label>
                    <input type="text" id="q-amazon" name="q" required minlength="2" maxlength="500"
                        class="w-full px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white border border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-amber-500"
                        placeholder="Ej: AMD Ryzen Threadripper 9960X">
                </div>
                <div>
                    <label for="palabras-coincidentes" class="block mb-2 font-medium text-gray-700 dark:text-gray-200">
                        Palabras coincidentes <span class="font-normal text-gray-500">(opcional, todas deben coincidir en el título)</span>
                    </label>
                    <input type="text" id="palabras-coincidentes" name="palabras_coincidentes" maxlength="500"
                        class="w-full px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white border border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-amber-500"
                        placeholder="Ej: AMD 9960X Threadripper">
                </div>
                <div class="flex flex-wrap gap-3">
                    <button type="submit" id="btn-buscar-amazon"
                        class="inline-flex items-center justify-center bg-amber-600 hover:bg-amber-700 text-white font-semibold px-6 py-2 rounded-md transition disabled:opacity-50 disabled:cursor-not-allowed">
                        Buscar
                    </button>
                </div>
                <p id="error-buscar-amazon" class="text-sm text-red-500 hidden"></p>
                <p id="loading-buscar-amazon" class="text-sm text-gray-500 dark:text-gray-400 hidden">
                    Consultando…
                </p>
            </form>
        </div>

        <div id="bloque-raw-aliexpress" class="hidden bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6 border border-red-300 dark:border-red-700">
            <details>
                <summary class="cursor-pointer text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">
                    Ver JSON crudo de AliExpress API
                </summary>
                <span id="meta-aliexpress" class="block text-sm text-gray-500 dark:text-gray-400 mb-2"></span>
                <pre id="raw-aliexpress-json" class="text-xs bg-gray-100 dark:bg-gray-900 p-4 rounded overflow-x-auto max-h-[50vh] overflow-y-auto whitespace-pre-wrap break-words"></pre>
            </details>
        </div>

        <div id="bloque-resultado" class="hidden bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6 border border-gray-200 dark:border-gray-700">
            <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Resultados</h3>
                <span id="meta-busqueda" class="text-sm text-gray-500 dark:text-gray-400"></span>
            </div>
            <p id="sin-resultados" class="text-sm text-gray-500 dark:text-gray-400 hidden">
                Ningún producto coincide con todas las palabras indicadas en el título.
            </p>
            <div id="grid-resultados-amazon" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4"></div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const urlBuscar = @json(route('admin.productos.buscar-amazon.api'));
            const csrf = @json(csrf_token());

            const form = document.getElementById('form-buscar-amazon');
            const inputQ = document.getElementById('q-amazon');
            const inputPalabras = document.getElementById('palabras-coincidentes');
            const radiosProveedor = form.querySelectorAll('input[name="proveedor"]');
            const textoAyuda = document.getElementById('texto-ayuda-busqueda');
            const btn = document.getElementById('btn-buscar-amazon');
            const errorEl = document.getElementById('error-buscar-amazon');
            const loadingEl = document.getElementById('loading-buscar-amazon');
            const bloqueResultado = document.getElementById('bloque-resultado');
            const bloqueRawAli = document.getElementById('bloque-raw-aliexpress');
            const rawAliJson = document.getElementById('raw-aliexpress-json');
            const metaAliexpress = document.getElementById('meta-aliexpress');
            const grid = document.getElementById('grid-resultados-amazon');
            const metaBusqueda = document.getElementById('meta-busqueda');
            const sinResultados = document.getElementById('sin-resultados');

            function getProveedorSeleccionado() {
                const checked = form.querySelector('input[name="proveedor"]:checked');
                return checked ? checked.value : 'ambos';
            }

            function actualizarModoBusqueda() {
                const proveedor = getProveedorSeleccionado();
                const clasesBtn = {
                    ambos: 'inline-flex items-center justify-center bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-6 py-2 rounded-md transition disabled:opacity-50 disabled:cursor-not-allowed',
                    amazon: 'inline-flex items-center justify-center bg-amber-600 hover:bg-amber-700 text-white font-semibold px-6 py-2 rounded-md transition disabled:opacity-50 disabled:cursor-not-allowed',
                    aliexpress: 'inline-flex items-center justify-center bg-red-600 hover:bg-red-700 text-white font-semibold px-6 py-2 rounded-md transition disabled:opacity-50 disabled:cursor-not-allowed',
                };
                btn.className = clasesBtn[proveedor] || clasesBtn.ambos;

                const textosAyuda = {
                    ambos: 'Busca en <strong>Amazon</strong> y después en <strong>AliExpress</strong>. Se muestran juntos los productos que coinciden con las palabras indicadas.',
                    amazon: 'Busca en la <strong>Amazon Creators API</strong>. Opcionalmente indica <strong>palabras coincidentes</strong>: cada palabra debe aparecer en el título del producto (se ignoran guiones y espacios).',
                    aliexpress: 'Busca en la <strong>AliExpress Affiliate API</strong>. Opcionalmente indica <strong>palabras coincidentes</strong>: cada palabra debe aparecer en el título del producto (se ignoran guiones y espacios).',
                };
                textoAyuda.innerHTML = textosAyuda[proveedor] || textosAyuda.ambos;
            }

            radiosProveedor.forEach(function(radio) {
                radio.addEventListener('change', actualizarModoBusqueda);
            });
            actualizarModoBusqueda();

            const badgeClasesPorEstado = {
                anadida: 'bg-gray-600 text-white',
                descartada: 'bg-red-600 text-white',
                no_encontrada: 'bg-green-600 text-white',
            };

            const badgeClasesNeo = {
                no: 'bg-slate-500 text-white',
                si_no: 'bg-orange-600 text-white',
                si_si: 'bg-blue-600 text-white',
            };

            function renderItems(items, mostrarBadgeTienda) {
                grid.innerHTML = '';
                if (!items.length) {
                    sinResultados.classList.remove('hidden');
                    return;
                }
                sinResultados.classList.add('hidden');

                items.forEach(function(item) {
                    const proveedor = item.proveedor || 'amazon';
                    const esAli = proveedor === 'aliexpress';
                    const hoverBorder = esAli ? 'hover:border-red-500' : 'hover:border-amber-500';
                    const estado = item.estado || 'no_encontrada';
                    const etiqueta = item.estado_label || 'No encontrada';
                    const neoLabel = item.neo_label || 'Neo: no';
                    const neoClase = item.neo === 'si'
                        ? (item.neo_aniadida === 'si' ? badgeClasesNeo.si_si : badgeClasesNeo.si_no)
                        : badgeClasesNeo.no;
                    const accion = item.accion || 'anadir';
                    const accionUrl = item.accion_url || '#';
                    const idProducto = item.product_id || item.asin || '';
                    const urlProducto = item.url_limpia || item.url || (esAli && idProducto
                        ? 'https://es.aliexpress.com/item/' + idProducto + '.html'
                        : 'https://www.amazon.es/dp/' + item.asin);

                    const tarjeta = document.createElement('div');
                    tarjeta.className = 'flex flex-col rounded-lg border border-gray-200 dark:border-gray-600 overflow-hidden bg-gray-50 dark:bg-gray-900 ' + hoverBorder + ' hover:shadow-md transition';

                    if (mostrarBadgeTienda) {
                        const badgeTienda = document.createElement('span');
                        badgeTienda.className = 'shrink-0 w-full px-2 py-0.5 text-center text-[10px] font-bold uppercase tracking-wide ' +
                            (esAli ? 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-200' : 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200');
                        badgeTienda.textContent = esAli ? 'AliExpress' : 'Amazon';
                        tarjeta.appendChild(badgeTienda);
                    }

                    const enlace = document.createElement('a');
                    enlace.href = urlProducto;
                    enlace.target = '_blank';
                    enlace.rel = 'noopener noreferrer';
                    enlace.title = (item.titulo || idProducto) + ' — ' + etiqueta;
                    enlace.className = 'group block flex-1 min-h-0';

                    const cuadro = document.createElement('div');
                    cuadro.className = 'flex flex-col aspect-square overflow-hidden';

                    const imagenWrap = document.createElement('div');
                    imagenWrap.className = 'flex-1 flex items-center justify-center p-2 min-h-0 overflow-hidden';

                    if (item.imagen) {
                        const img = document.createElement('img');
                        img.src = item.imagen;
                        img.alt = item.titulo || idProducto;
                        img.loading = 'lazy';
                        img.className = 'max-w-full max-h-full object-contain' +
                            (estado === 'anadida' ? ' grayscale opacity-60' : '');
                        imagenWrap.appendChild(img);
                    } else {
                        const placeholder = document.createElement('span');
                        placeholder.className = 'text-xs text-gray-400 text-center px-2';
                        placeholder.textContent = 'Sin imagen';
                        imagenWrap.appendChild(placeholder);
                    }

                    cuadro.appendChild(imagenWrap);

                    const badge = document.createElement('span');
                    badge.className = 'shrink-0 w-full px-2 py-1.5 text-center text-[10px] sm:text-xs font-semibold ' +
                        (badgeClasesPorEstado[estado] || badgeClasesPorEstado.no_encontrada);
                    badge.textContent = etiqueta;
                    cuadro.appendChild(badge);

                    enlace.appendChild(cuadro);
                    tarjeta.appendChild(enlace);

                    const badgeNeo = document.createElement('span');
                    badgeNeo.className = 'shrink-0 w-full px-2 py-1 text-center text-[10px] sm:text-xs font-semibold ' + neoClase;
                    badgeNeo.textContent = neoLabel;
                    tarjeta.appendChild(badgeNeo);

                    const btnAccion = document.createElement('a');
                    btnAccion.href = accionUrl;
                    btnAccion.className = 'shrink-0 w-full px-2 py-2 text-center text-xs font-semibold transition ' +
                        (accion === 'editar'
                            ? 'bg-indigo-600 hover:bg-indigo-700 text-white'
                            : 'bg-amber-600 hover:bg-amber-700 text-white');
                    btnAccion.textContent = accion === 'editar' ? 'Editar' : 'Añadir';
                    tarjeta.appendChild(btnAccion);

                    grid.appendChild(tarjeta);
                });
            }

            async function buscarEnProveedor(q, palabras, proveedor) {
                const response = await fetch(urlBuscar, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                    },
                    body: JSON.stringify({
                        q: q,
                        palabras_coincidentes: palabras,
                        aliexpress: proveedor === 'aliexpress',
                    }),
                });

                const data = await response.json();
                return { response, data, proveedor };
            }

            function etiquetarItems(items, proveedor) {
                return (items || []).map(function(item) {
                    return Object.assign({}, item, { proveedor: proveedor });
                });
            }

            function construirMetaBusqueda(resultados, palabras) {
                const total = resultados.reduce(function(sum, r) {
                    return sum + (r.data.items || []).length;
                }, 0);

                let meta = total + ' producto(s)';

                resultados.forEach(function(r) {
                    const d = r.data;
                    const count = (d.items || []).length;
                    if (r.proveedor === 'amazon') {
                        let parte = ' · Amazon: ' + count;
                        if (d.total_amazon != null && count !== d.total_amazon) {
                            parte += ' de ' + d.total_amazon;
                        }
                        if (d.paginas_consultadas != null && d.paginas_consultadas > 1) {
                            parte += ' (' + d.paginas_consultadas + ' pág.)';
                        }
                        meta += parte;
                    } else if (r.proveedor === 'aliexpress') {
                        let parte = ' · AliExpress: ' + count;
                        if (d.total_aliexpress != null && count !== d.total_aliexpress) {
                            parte += ' de ' + d.total_aliexpress;
                        }
                        meta += parte;
                    }
                });

                if (palabras) {
                    meta += ' · palabras: «' + palabras + '»';
                }

                return meta;
            }

            form.addEventListener('submit', async function(e) {
                e.preventDefault();

                const q = (inputQ.value || '').trim();
                const palabras = (inputPalabras.value || '').trim();
                const seleccion = getProveedorSeleccionado();
                if (q.length < 2) {
                    errorEl.textContent = 'Introduce al menos 2 caracteres en la búsqueda.';
                    errorEl.classList.remove('hidden');
                    return;
                }

                const proveedores = seleccion === 'ambos'
                    ? ['amazon', 'aliexpress']
                    : [seleccion];

                errorEl.classList.add('hidden');
                bloqueResultado.classList.add('hidden');
                bloqueRawAli.classList.add('hidden');
                sinResultados.classList.add('hidden');
                btn.disabled = true;

                const resultadosOk = [];
                const errores = [];
                let rawAli = null;

                try {
                    for (let i = 0; i < proveedores.length; i++) {
                        const proveedor = proveedores[i];
                        const etiquetaCarga = proveedor === 'amazon' ? 'Amazon' : 'AliExpress';
                        loadingEl.textContent = 'Consultando ' + etiquetaCarga + '…' +
                            (proveedores.length > 1 ? ' (' + (i + 1) + '/' + proveedores.length + ')' : '');
                        loadingEl.classList.remove('hidden');

                        const { response, data } = await buscarEnProveedor(q, palabras, proveedor);

                        if (!response.ok || !data.success) {
                            errores.push((proveedor === 'amazon' ? 'Amazon' : 'AliExpress') + ': ' + (data.error || 'Error en la búsqueda'));
                            if (proveedor === 'aliexpress' && data.raw) {
                                rawAli = data.raw;
                            }
                            continue;
                        }

                        resultadosOk.push({ proveedor: proveedor, data: data });
                        if (proveedor === 'aliexpress' && data.raw) {
                            rawAli = data.raw;
                        }
                    }

                    if (errores.length && !resultadosOk.length) {
                        errorEl.textContent = errores.join(' · ');
                        errorEl.classList.remove('hidden');
                        if (rawAli) {
                            bloqueRawAli.classList.remove('hidden');
                            rawAliJson.textContent = JSON.stringify(rawAli, null, 2);
                            metaAliexpress.textContent = 'Error — respuesta parcial de la API';
                        }
                        return;
                    }

                    if (errores.length) {
                        errorEl.textContent = errores.join(' · ');
                        errorEl.classList.remove('hidden');
                    }

                    const itemsCombinados = resultadosOk.reduce(function(acum, r) {
                        return acum.concat(etiquetarItems(r.data.items, r.proveedor));
                    }, []);

                    bloqueResultado.classList.remove('hidden');
                    metaBusqueda.textContent = construirMetaBusqueda(resultadosOk, palabras);
                    renderItems(itemsCombinados, proveedores.length > 1);

                    if (rawAli && (seleccion === 'aliexpress' || seleccion === 'ambos')) {
                        bloqueRawAli.classList.remove('hidden');
                        rawAliJson.textContent = JSON.stringify(rawAli, null, 2);
                        metaAliexpress.textContent = 'keywords: «' + q + '»';
                    }
                } catch (err) {
                    errorEl.textContent = 'Error de red: ' + (err.message || 'desconocido');
                    errorEl.classList.remove('hidden');
                } finally {
                    loadingEl.classList.add('hidden');
                    btn.disabled = false;
                }
            });
        });
    </script>
</x-app-layout>
