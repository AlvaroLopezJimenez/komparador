<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.dashboard') }}">
                <h2 class="font-semibold text-xl text-white leading-tight">Inicio -></h2>
            </a>
            <a href="{{ route('admin.ofertas.todas') }}">
                <h2 class="font-semibold text-xl text-white leading-tight">Ofertas -></h2>
            </a>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Importar desde CholloPañales (temporal)
            </h2>
        </div>
    </x-slot>

    <div class="py-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700 rounded-lg p-4 text-sm text-yellow-800 dark:text-yellow-200">
            Herramienta temporal: pega el JSON exportado desde CholloPañales, elige el producto destino en Komparador y selecciona qué ofertas importar.
        </div>

        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6 space-y-4">
            <div>
                <label for="json_export" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    JSON exportado
                </label>
                <textarea id="json_export" rows="10"
                    class="w-full px-4 py-3 border rounded bg-white dark:bg-gray-900 text-sm font-mono text-gray-800 dark:text-white"
                    placeholder='Pega aquí el JSON de "Exportar JSON (Komparador)"...'></textarea>
            </div>

            <div class="relative max-w-xl">
                <label for="producto_search" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Producto destino en Komparador
                </label>
                <input type="text" id="producto_search" autocomplete="off"
                    class="w-full px-4 py-2 border rounded bg-white dark:bg-gray-900 text-gray-800 dark:text-white"
                    placeholder="Buscar producto (mín. 2 caracteres)...">
                <input type="hidden" id="producto_id">
                <div id="producto_sugerencias"
                    class="absolute z-20 left-0 right-0 mt-1 bg-white dark:bg-gray-800 border rounded shadow-lg hidden max-h-60 overflow-y-auto"></div>
                <div id="producto_elegido" class="hidden mt-2 text-sm text-gray-600 dark:text-gray-300">
                    <span class="font-medium">Producto:</span>
                    <span id="producto_elegido_nombre"></span>
                    <button type="button" id="producto_quitar" class="ml-2 text-red-600 hover:underline">Quitar</button>
                </div>
            </div>

            <div class="flex flex-wrap gap-3">
                <button type="button" id="btnAnalizar"
                    class="bg-blue-600 text-white px-5 py-2 rounded hover:bg-blue-700 disabled:opacity-50">
                    Analizar ofertas
                </button>
                <button type="button" id="btnImportar" disabled
                    class="bg-green-600 text-white px-5 py-2 rounded hover:bg-green-700 disabled:opacity-50">
                    Importar seleccionadas
                </button>
            </div>
        </div>

        <div id="panelResultados" class="hidden bg-white dark:bg-gray-800 shadow rounded-lg p-6 space-y-4">
            <div id="resumenOrigen" class="text-sm text-gray-600 dark:text-gray-300"></div>
            <div class="flex items-center gap-4 text-sm">
                <label class="inline-flex items-center gap-2">
                    <input type="checkbox" id="checkTodos" checked class="rounded">
                    <span>Seleccionar importables</span>
                </label>
                <span id="contadorSeleccionadas" class="text-gray-500"></span>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b dark:border-gray-700 text-left text-gray-600 dark:text-gray-300">
                            <th class="py-2 pr-3">✓</th>
                            <th class="py-2 pr-3">Estado</th>
                            <th class="py-2 pr-3">Tienda</th>
                            <th class="py-2 pr-3">Unidades</th>
                            <th class="py-2 pr-3">Precio total</th>
                            <th class="py-2 pr-3">€/ud</th>
                            <th class="py-2 pr-3">Envío</th>
                            <th class="py-2 pr-3">Actualiza</th>
                            <th class="py-2 pr-3">Mostrar</th>
                            <th class="py-2 pr-3">URL</th>
                        </tr>
                    </thead>
                    <tbody id="tablaFilas"></tbody>
                </table>
            </div>
            <div id="resultadoImportacion" class="hidden text-sm"></div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
        const jsonExport = document.getElementById('json_export');
        const productoSearch = document.getElementById('producto_search');
        const productoId = document.getElementById('producto_id');
        const productoSugerencias = document.getElementById('producto_sugerencias');
        const productoElegido = document.getElementById('producto_elegido');
        const productoElegidoNombre = document.getElementById('producto_elegido_nombre');
        const btnAnalizar = document.getElementById('btnAnalizar');
        const btnImportar = document.getElementById('btnImportar');
        const panelResultados = document.getElementById('panelResultados');
        const resumenOrigen = document.getElementById('resumenOrigen');
        const tablaFilas = document.getElementById('tablaFilas');
        const checkTodos = document.getElementById('checkTodos');
        const contadorSeleccionadas = document.getElementById('contadorSeleccionadas');
        const resultadoImportacion = document.getElementById('resultadoImportacion');

        let filasActuales = [];
        let busquedaTimeout = null;

        function actualizarContador() {
            const seleccionadas = document.querySelectorAll('.fila-check:checked:not(:disabled)').length;
            contadorSeleccionadas.textContent = seleccionadas + ' seleccionada(s)';
            btnImportar.disabled = seleccionadas === 0;
        }

        function escaparHtml(texto) {
            const div = document.createElement('div');
            div.textContent = texto ?? '';
            return div.innerHTML;
        }

        function urlComoEnlace(url) {
            const urlLimpia = (url || '').trim();
            if (!urlLimpia) {
                return '-';
            }
            const href = /^https?:\/\//i.test(urlLimpia) ? urlLimpia : 'https://' + urlLimpia;
            const texto = urlLimpia.length > 60 ? urlLimpia.slice(0, 57) + '...' : urlLimpia;
            return `<a href="${escaparHtml(href)}" target="_blank" rel="noopener noreferrer" class="text-blue-600 dark:text-blue-400 hover:underline break-all" title="${escaparHtml(urlLimpia)}">${escaparHtml(texto)}</a>`;
        }

        function renderFilas(filas) {
            filasActuales = filas;
            tablaFilas.innerHTML = '';

            filas.forEach(function(fila) {
                const tr = document.createElement('tr');
                tr.className = 'border-b dark:border-gray-700 align-top';

                const checked = fila.importable && fila.seleccionada ? 'checked' : '';
                const disabled = fila.importable ? '' : 'disabled';
                const estadoClass = fila.importable
                    ? 'text-green-600 dark:text-green-400'
                    : 'text-red-600 dark:text-red-400';

                tr.innerHTML = `
                    <td class="py-2 pr-3">
                        <input type="checkbox" class="fila-check rounded" data-indice="${fila.indice}" ${checked} ${disabled}>
                    </td>
                    <td class="py-2 pr-3 ${estadoClass}">${fila.estado_texto}${fila.detalle ? '<br><span class="text-xs text-gray-500">' + fila.detalle + '</span>' : ''}</td>
                    <td class="py-2 pr-3">${fila.tienda_nombre || fila.tienda_origen_nombre || '-'}</td>
                    <td class="py-2 pr-3">${fila.unidades ?? '-'}</td>
                    <td class="py-2 pr-3">${fila.precio_total ?? '-'}</td>
                    <td class="py-2 pr-3">${fila.precio_unidad ?? '-'}</td>
                    <td class="py-2 pr-3">${fila.envio_texto ?? '-'}</td>
                    <td class="py-2 pr-3">${fila.frecuencia_texto ?? '-'}</td>
                    <td class="py-2 pr-3">${fila.mostrar ?? '-'}</td>
                    <td class="py-2 pr-3 max-w-xs">${urlComoEnlace(fila.url)}</td>
                `;
                tablaFilas.appendChild(tr);
            });

            document.querySelectorAll('.fila-check').forEach(function(cb) {
                cb.addEventListener('change', actualizarContador);
            });

            const importables = document.querySelectorAll('.fila-check:not(:disabled)');
            const marcadas = document.querySelectorAll('.fila-check:checked:not(:disabled)');
            checkTodos.checked = importables.length > 0 && marcadas.length === importables.length;
            checkTodos.indeterminate = marcadas.length > 0 && marcadas.length < importables.length;

            actualizarContador();
        }

        productoSearch.addEventListener('input', function() {
            clearTimeout(busquedaTimeout);
            const q = productoSearch.value.trim();
            if (q.length < 2) {
                productoSugerencias.classList.add('hidden');
                return;
            }
            busquedaTimeout = setTimeout(async function() {
                const res = await fetch(`{{ route('admin.ofertas.buscar.productos') }}?q=${encodeURIComponent(q)}`);
                const items = await res.json();
                productoSugerencias.innerHTML = '';
                if (!items.length) {
                    productoSugerencias.classList.add('hidden');
                    return;
                }
                items.forEach(function(item) {
                    const div = document.createElement('div');
                    div.className = 'px-3 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 cursor-pointer';
                    div.textContent = `#${item.id} - ${item.texto_completo || item.nombre}`;
                    div.addEventListener('click', function() {
                        productoId.value = item.id;
                        productoElegidoNombre.textContent = `#${item.id} - ${item.texto_completo || item.nombre}`;
                        productoElegido.classList.remove('hidden');
                        productoSearch.value = '';
                        productoSugerencias.classList.add('hidden');
                    });
                    productoSugerencias.appendChild(div);
                });
                productoSugerencias.classList.remove('hidden');
            }, 250);
        });

        document.getElementById('producto_quitar').addEventListener('click', function() {
            productoId.value = '';
            productoElegido.classList.add('hidden');
        });

        checkTodos.addEventListener('change', function() {
            document.querySelectorAll('.fila-check:not(:disabled)').forEach(function(cb) {
                cb.checked = checkTodos.checked;
            });
            checkTodos.indeterminate = false;
            actualizarContador();
        });

        btnAnalizar.addEventListener('click', async function() {
            if (!jsonExport.value.trim()) {
                alert('Pega el JSON exportado.');
                return;
            }
            if (!productoId.value) {
                alert('Selecciona un producto destino.');
                return;
            }

            btnAnalizar.disabled = true;
            btnAnalizar.textContent = 'Analizando...';
            resultadoImportacion.classList.add('hidden');

            try {
                const res = await fetch('{{ route('admin.ofertas.importar-chollo.analizar') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        json_export: jsonExport.value,
                        producto_id: productoId.value,
                    }),
                });

                const data = await res.json();
                if (!res.ok) {
                    throw new Error(data.message || 'Error al analizar.');
                }

                const origen = data.origen || {};
                const prodOrigen = origen.producto_origen || {};
                resumenOrigen.innerHTML = `
                    <strong>Producto origen:</strong> ${prodOrigen.nombre || '-'}
                    (${origen.origen || 'chollopañales'})
                    · <strong>Producto destino:</strong> #${data.producto.id} ${data.producto.nombre}
                    · <strong>Ofertas:</strong> ${data.filas.length}
                `;

                renderFilas(data.filas);
                panelResultados.classList.remove('hidden');
            } catch (e) {
                alert(e.message || 'Error al analizar.');
            } finally {
                btnAnalizar.disabled = false;
                btnAnalizar.textContent = 'Analizar ofertas';
            }
        });

        btnImportar.addEventListener('click', async function() {
            const indices = Array.from(document.querySelectorAll('.fila-check:checked:not(:disabled)'))
                .map(function(cb) { return parseInt(cb.dataset.indice, 10); });

            if (!indices.length) {
                alert('Selecciona al menos una oferta.');
                return;
            }

            if (!confirm(`¿Importar ${indices.length} oferta(s)?`)) {
                return;
            }

            btnImportar.disabled = true;
            btnImportar.textContent = 'Importando...';

            try {
                const res = await fetch('{{ route('admin.ofertas.importar-chollo.importar') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        json_export: jsonExport.value,
                        producto_id: productoId.value,
                        indices: indices,
                    }),
                });

                const data = await res.json();
                resultadoImportacion.classList.remove('hidden');

                if (data.creadas > 0) {
                    resultadoImportacion.innerHTML = `<p class="text-green-600 dark:text-green-400">Importadas: ${data.creadas} oferta(s).</p>`;
                }
                if (data.errores && data.errores.length) {
                    resultadoImportacion.innerHTML += `<ul class="mt-2 text-red-600 dark:text-red-400 list-disc pl-5">${data.errores.map(e => `<li>${e}</li>`).join('')}</ul>`;
                }

                await btnAnalizar.click();
            } catch (e) {
                alert(e.message || 'Error al importar.');
            } finally {
                btnImportar.textContent = 'Importar seleccionadas';
                actualizarContador();
            }
        });
    });
    </script>
</x-app-layout>
