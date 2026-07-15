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
                Eliminar Neo por URLs
            </h2>
        </div>
    </x-slot>

    <div class="max-w-6xl mx-auto py-10 px-4 space-y-8">
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6 border border-gray-200 dark:border-gray-700">
            <div class="space-y-4">
                <div>
                    <div class="flex items-center justify-between gap-3 mb-2">
                        <label for="urls-textarea" class="font-medium text-gray-700 dark:text-gray-200">URLs (una por línea)</label>
                        <button type="button" id="btn-ayuda-urls"
                            class="inline-flex items-center justify-center w-7 h-7 rounded-full border border-gray-300 dark:border-gray-500 bg-gray-100 dark:bg-gray-700 text-sm font-bold text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500 shrink-0"
                            aria-expanded="false" aria-controls="panel-ayuda-urls" title="Ayuda">
                            ?
                        </button>
                    </div>
                    <div id="panel-ayuda-urls" class="hidden mb-3 rounded-lg border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-900/50 p-4 text-sm text-gray-600 dark:text-gray-400 space-y-3">
                        <p>
                            Pega URLs de la tabla <strong>neo</strong> (campo <code class="text-xs bg-gray-100 dark:bg-gray-700 px-1 rounded">url</code>), una por línea.
                            Pulsa <strong>Comprobar URLs</strong> para ver si existen. Si alguna no está en la base de datos, se mostrará un aviso.
                            Las peticiones se envían en bloques de 100 URLs para poder procesar listas grandes (600, 1000, 2000…).
                            Cuando quieras borrar, elige por cada URL con varias filas si eliminar solo una fila concreta o todas las que coincidan con esa URL.
                        </p>
                        <p>
                            Al eliminar, si la URL existe en <strong>ofertas_producto</strong>, <strong>no se borra</strong> la fila en neo.
                            Si deja de existir en neo y está en <strong>csv_ofertas</strong> con <code class="text-xs bg-gray-100 dark:bg-gray-700 px-1 rounded">aniadida_neo=si</code>,
                            se comprueba si sigue en ofertas_producto: si existe allí se mantiene <code class="text-xs">aniadida_neo=si</code>; si no, pasa a <code class="text-xs">aniadida_neo=no</code>.
                            Si quedan otras filas neo con la misma URL (borrado de una sola fila), no se modifica csv_ofertas.
                        </p>
                    </div>
                    <textarea id="urls-textarea" rows="12"
                        class="w-full px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white border border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-red-500 font-mono text-sm"
                        placeholder="https://ejemplo.com/oferta-1&#10;https://ejemplo.com/oferta-2"></textarea>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <button type="button" id="btn-comprobar"
                        class="inline-flex items-center bg-blue-600 hover:bg-blue-700 text-white font-semibold px-6 py-3 rounded-md transition disabled:opacity-50 disabled:cursor-not-allowed">
                        Comprobar URLs
                    </button>
                    <button type="button" id="btn-eliminar" disabled
                        class="inline-flex items-center bg-red-600 hover:bg-red-700 text-white font-semibold px-6 py-3 rounded-md transition disabled:opacity-50 disabled:cursor-not-allowed">
                        Eliminar todas (confirmar)
                    </button>
                </div>
                <p id="mensaje-global" class="text-sm hidden"></p>
                <div id="panel-progreso" class="hidden rounded-xl border border-blue-200 dark:border-blue-700 bg-blue-50 dark:bg-blue-900/20 p-4 space-y-3">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div class="flex items-center gap-2">
                            <span id="progreso-spinner" class="inline-block w-4 h-4 border-2 border-blue-600 dark:border-blue-400 border-t-transparent rounded-full animate-spin shrink-0"></span>
                            <span id="progreso-ok" class="hidden text-green-600 dark:text-green-400 text-lg leading-none shrink-0">✓</span>
                            <span id="progreso-titulo" class="font-semibold text-blue-900 dark:text-blue-100">Procesando…</span>
                        </div>
                        <span id="progreso-contador" class="text-xl font-bold tabular-nums text-blue-700 dark:text-blue-300">0 / 0</span>
                    </div>
                    <div class="w-full bg-blue-200 dark:bg-blue-800 rounded-full h-3 overflow-hidden">
                        <div id="progreso-barra" class="bg-blue-600 dark:bg-blue-400 h-3 rounded-full transition-all duration-300 ease-out" style="width: 0%"></div>
                    </div>
                    <p id="progreso-detalle" class="text-sm text-blue-800 dark:text-blue-200"></p>
                    <p id="progreso-extra" class="text-xs text-blue-700 dark:text-blue-300 hidden"></p>
                </div>

                <div id="bloque-resumen" class="hidden rounded-xl border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-900/40 p-4 sm:p-5 space-y-4">
                    <h3 class="text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Resumen</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <button type="button" id="tarjeta-encontradas"
                            class="rounded-xl border-2 border-green-200 dark:border-green-800 bg-green-50 dark:bg-green-900/25 p-4 text-left transition hover:bg-green-100 dark:hover:bg-green-900/40 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
                            <span class="block text-sm font-medium text-green-800 dark:text-green-200">Encontradas en neo</span>
                            <span id="num-encontradas" class="block mt-1 text-3xl font-bold tabular-nums text-green-700 dark:text-green-300">0</span>
                        </button>
                        <button type="button" id="tarjeta-no-encontradas"
                            class="rounded-xl border-2 border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/25 p-4 text-left transition hover:bg-amber-100 dark:hover:bg-amber-900/40 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
                            <span class="block text-sm font-medium text-amber-800 dark:text-amber-200">No encontradas en neo</span>
                            <span id="num-no-encontradas" class="block mt-1 text-3xl font-bold tabular-nums text-amber-700 dark:text-amber-300">0</span>
                        </button>
                    </div>
                    <div id="panel-detalle-resumen" class="hidden rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 p-4">
                        <div id="panel-detalle-encontradas" class="hidden">
                            <p id="info-repetidas" class="text-sm text-gray-600 dark:text-gray-400 mb-4 hidden"></p>
                            <div id="tabla-encontradas" class="space-y-4 max-h-[32rem] overflow-y-auto"></div>
                        </div>
                        <div id="panel-detalle-no-encontradas" class="hidden">
                            <p class="text-sm text-amber-800 dark:text-amber-200 mb-3">Estas URLs no tienen ninguna fila con ese valor exacto en el campo <code>url</code>:</p>
                            <ul id="lista-no-encontradas" class="list-disc list-inside font-mono text-sm text-amber-900 dark:text-amber-100 space-y-1 break-all max-h-[32rem] overflow-y-auto"></ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div id="bloque-resultado-eliminacion" class="hidden bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-xl p-6">
            <h3 class="text-lg font-semibold text-green-900 dark:text-green-100 mb-2">Eliminación completada</h3>
            <p id="texto-resultado-eliminacion" class="text-sm text-green-800 dark:text-green-200"></p>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const urlComprobar = @json(route('admin.neo.eliminar-por-urls.comprobar'));
            const urlEjecutar = @json(route('admin.neo.eliminar-por-urls.ejecutar'));
            const csrf = @json(csrf_token());

            const textarea = document.getElementById('urls-textarea');
            const btnComprobar = document.getElementById('btn-comprobar');
            const btnEliminar = document.getElementById('btn-eliminar');
            const mensajeGlobal = document.getElementById('mensaje-global');
            const bloqueResumen = document.getElementById('bloque-resumen');
            const tarjetaEncontradas = document.getElementById('tarjeta-encontradas');
            const tarjetaNoEncontradas = document.getElementById('tarjeta-no-encontradas');
            const numEncontradas = document.getElementById('num-encontradas');
            const numNoEncontradas = document.getElementById('num-no-encontradas');
            const panelDetalleResumen = document.getElementById('panel-detalle-resumen');
            const panelDetalleEncontradas = document.getElementById('panel-detalle-encontradas');
            const panelDetalleNoEncontradas = document.getElementById('panel-detalle-no-encontradas');
            const listaNo = document.getElementById('lista-no-encontradas');
            const tablaOk = document.getElementById('tabla-encontradas');
            const infoRepetidas = document.getElementById('info-repetidas');
            const bloqueResElim = document.getElementById('bloque-resultado-eliminacion');
            const textoResElim = document.getElementById('texto-resultado-eliminacion');
            const panelProgreso = document.getElementById('panel-progreso');
            const progresoTitulo = document.getElementById('progreso-titulo');
            const progresoContador = document.getElementById('progreso-contador');
            const progresoBarra = document.getElementById('progreso-barra');
            const progresoDetalle = document.getElementById('progreso-detalle');
            const progresoExtra = document.getElementById('progreso-extra');
            const progresoSpinner = document.getElementById('progreso-spinner');
            const progresoOk = document.getElementById('progreso-ok');
            const btnAyudaUrls = document.getElementById('btn-ayuda-urls');
            const panelAyudaUrls = document.getElementById('panel-ayuda-urls');

            btnAyudaUrls.addEventListener('click', function() {
                const abierto = !panelAyudaUrls.classList.contains('hidden');
                panelAyudaUrls.classList.toggle('hidden', abierto);
                btnAyudaUrls.setAttribute('aria-expanded', abierto ? 'false' : 'true');
            });

            const TAMANO_BLOQUE = 100;

            let ultimaData = null;
            let progresoTotal = 0;
            let vistaResumenActiva = null;
            let cacheEncontradas = [];
            let cacheNoEncontradas = [];

            function marcarTarjetaActiva(tipo) {
                const activaEncontradas = tipo === 'encontradas';
                tarjetaEncontradas.classList.toggle('ring-2', activaEncontradas);
                tarjetaEncontradas.classList.toggle('ring-green-600', activaEncontradas);
                tarjetaEncontradas.classList.toggle('ring-offset-2', activaEncontradas);
                tarjetaEncontradas.classList.toggle('dark:ring-offset-gray-900', activaEncontradas);
                tarjetaEncontradas.classList.toggle('border-green-500', activaEncontradas);
                tarjetaEncontradas.classList.toggle('dark:border-green-500', activaEncontradas);

                const activaNo = tipo === 'no-encontradas';
                tarjetaNoEncontradas.classList.toggle('ring-2', activaNo);
                tarjetaNoEncontradas.classList.toggle('ring-amber-500', activaNo);
                tarjetaNoEncontradas.classList.toggle('ring-offset-2', activaNo);
                tarjetaNoEncontradas.classList.toggle('dark:ring-offset-gray-900', activaNo);
                tarjetaNoEncontradas.classList.toggle('border-amber-500', activaNo);
                tarjetaNoEncontradas.classList.toggle('dark:border-amber-500', activaNo);
            }

            function seleccionarVistaResumen(tipo) {
                vistaResumenActiva = tipo;
                marcarTarjetaActiva(tipo);
                panelDetalleResumen.classList.remove('hidden');
                panelDetalleEncontradas.classList.toggle('hidden', tipo !== 'encontradas');
                panelDetalleNoEncontradas.classList.toggle('hidden', tipo !== 'no-encontradas');
            }

            function actualizarContadoresResumen(cantEncontradas, cantNoEncontradas) {
                numEncontradas.textContent = String(cantEncontradas);
                numNoEncontradas.textContent = String(cantNoEncontradas);
            }

            function renderizarListaNoEncontradas(urls) {
                if (!urls.length) {
                    listaNo.innerHTML = '<li class="list-none text-sm text-gray-500 dark:text-gray-400">No hay URLs en esta categoría.</li>';
                    return;
                }
                listaNo.innerHTML = urls.map(function(u) {
                    return '<li>' + escaparHtml(u) + '</li>';
                }).join('');
            }

            function resetearResumen() {
                cacheEncontradas = [];
                cacheNoEncontradas = [];
                vistaResumenActiva = null;
                actualizarContadoresResumen(0, 0);
                tablaOk.innerHTML = '';
                listaNo.innerHTML = '';
                infoRepetidas.classList.add('hidden');
                panelDetalleResumen.classList.add('hidden');
                panelDetalleEncontradas.classList.add('hidden');
                panelDetalleNoEncontradas.classList.add('hidden');
                marcarTarjetaActiva('');
                bloqueResumen.classList.add('hidden');
            }

            function actualizarResumen(encontradas, noEncontradas, opciones) {
                opciones = opciones || {};
                cacheEncontradas = encontradas || [];
                cacheNoEncontradas = noEncontradas || [];
                actualizarContadoresResumen(cacheEncontradas.length, cacheNoEncontradas.length);
                bloqueResumen.classList.remove('hidden');
                renderizarListaNoEncontradas(cacheNoEncontradas);
                renderizarContenidoEncontradas(cacheEncontradas);

                if (opciones.repetidas_en_texto) {
                    infoRepetidas.textContent = 'Has repetido alguna URL en el texto (se comprueba una vez por URL única). ' +
                        'Líneas con URL: ' + opciones.urls_en_texto + ', únicas: ' + opciones.urls_unicas + '.';
                    infoRepetidas.classList.remove('hidden');
                } else {
                    infoRepetidas.classList.add('hidden');
                }

                if (opciones.seleccion) {
                    seleccionarVistaResumen(opciones.seleccion);
                } else if (vistaResumenActiva === 'encontradas' || vistaResumenActiva === 'no-encontradas') {
                    seleccionarVistaResumen(vistaResumenActiva);
                } else if (cacheEncontradas.length > 0) {
                    seleccionarVistaResumen('encontradas');
                } else if (cacheNoEncontradas.length > 0) {
                    seleccionarVistaResumen('no-encontradas');
                } else {
                    panelDetalleResumen.classList.add('hidden');
                }
            }

            tarjetaEncontradas.addEventListener('click', function() {
                seleccionarVistaResumen('encontradas');
            });

            tarjetaNoEncontradas.addEventListener('click', function() {
                seleccionarVistaResumen('no-encontradas');
            });

            function escaparHtml(s) {
                const d = document.createElement('div');
                d.textContent = s;
                return d.innerHTML;
            }

            function mostrarMensaje(texto, esError) {
                mensajeGlobal.textContent = texto;
                mensajeGlobal.classList.remove('hidden', 'text-red-600', 'text-green-600', 'dark:text-red-400', 'dark:text-green-400');
                mensajeGlobal.classList.add(esError ? 'text-red-600' : 'text-green-600', esError ? 'dark:text-red-400' : 'dark:text-green-400');
            }

            function iniciarProgreso(titulo, total, detalle) {
                progresoTotal = total;
                panelProgreso.classList.remove('hidden');
                panelProgreso.classList.remove('border-green-300', 'dark:border-green-700', 'bg-green-50', 'dark:bg-green-900/20');
                panelProgreso.classList.add('border-blue-200', 'dark:border-blue-700', 'bg-blue-50', 'dark:bg-blue-900/20');
                progresoSpinner.classList.remove('hidden');
                progresoOk.classList.add('hidden');
                progresoTitulo.textContent = titulo;
                progresoContador.textContent = '0 / ' + total;
                progresoBarra.style.width = '0%';
                progresoDetalle.textContent = detalle || '';
                progresoExtra.classList.add('hidden');
                progresoExtra.textContent = '';
            }

            function marcarProgresoCompletado(titulo, detalle, extra) {
                progresoSpinner.classList.add('hidden');
                progresoOk.classList.remove('hidden');
                panelProgreso.classList.remove('border-blue-200', 'dark:border-blue-700', 'bg-blue-50', 'dark:bg-blue-900/20');
                panelProgreso.classList.add('border-green-300', 'dark:border-green-700', 'bg-green-50', 'dark:bg-green-900/20');
                progresoTitulo.textContent = titulo;
                progresoBarra.style.width = '100%';
                progresoDetalle.textContent = detalle;
                if (extra) {
                    progresoExtra.textContent = extra;
                    progresoExtra.classList.remove('hidden');
                }
            }

            function actualizarProgreso(actual, detalle, extra) {
                const pct = progresoTotal > 0 ? Math.min(100, Math.round((actual / progresoTotal) * 100)) : 0;
                progresoContador.textContent = actual + ' / ' + progresoTotal;
                progresoBarra.style.width = pct + '%';
                if (detalle) {
                    progresoDetalle.textContent = detalle;
                }
                if (extra) {
                    progresoExtra.textContent = extra;
                    progresoExtra.classList.remove('hidden');
                }
            }

            function ocultarProgreso() {
                panelProgreso.classList.add('hidden');
                progresoSpinner.classList.remove('hidden');
                progresoOk.classList.add('hidden');
                progresoBarra.style.width = '0%';
                progresoContador.textContent = '0 / 0';
                progresoDetalle.textContent = '';
                progresoExtra.classList.add('hidden');
                progresoExtra.textContent = '';
            }

            function dividirEnBloques(items, tamano) {
                const bloques = [];
                for (let i = 0; i < items.length; i += tamano) {
                    bloques.push(items.slice(i, i + tamano));
                }
                return bloques;
            }

            function parsearUrlsTexto(texto) {
                return (texto || '').split(/\r\n|\r|\n/).map(function(l) {
                    return l.trim();
                }).filter(function(l) {
                    return l !== '';
                });
            }

            async function fetchJson(url, body) {
                const res = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify(body),
                });
                let data;
                try {
                    data = await res.json();
                } catch (parseErr) {
                    throw new Error('Error del servidor (código ' + res.status + '). Revisa el log de Laravel.');
                }
                if (!res.ok) {
                    throw new Error(data.message || data.error || 'Error en la petición.');
                }
                return data;
            }

            function limpiarResultados() {
                bloqueResElim.classList.add('hidden');
                mensajeGlobal.classList.add('hidden');
                ocultarProgreso();
            }

            function renderizarContenidoEncontradas(encontradas) {
                if (!encontradas.length) {
                    tablaOk.innerHTML = '<p class="text-sm text-gray-500 dark:text-gray-400">No hay URLs en esta categoría.</p>';
                    return;
                }

                if (encontradas.length > 100) {
                    const conVariasFilas = encontradas.filter(function(item) {
                        return item.total > 1;
                    }).length;
                    tablaOk.innerHTML =
                        '<div class="rounded-lg border border-blue-200 dark:border-blue-800 bg-blue-50 dark:bg-blue-900/20 p-4 text-sm text-blue-900 dark:text-blue-100">' +
                        '<p class="font-medium mb-2">Vista resumida</p>' +
                        '<p>Se procesan en bloques de ' + TAMANO_BLOQUE + '. Con tantas URLs no se muestra el detalle fila a fila.</p>' +
                        '<p class="mt-2">Al confirmar la eliminación: si una URL tiene una sola fila neo se borra esa fila; si tiene varias filas se borrarán <strong>todas</strong> (' + conVariasFilas + ' URL(s) con varias filas).</p>' +
                        '</div>';
                    return;
                }

                tablaOk.innerHTML = encontradas.map(function(item, idx) {
                    const safeUrl = escaparHtml(item.url);
                    const nameBase = 'alcance-' + idx;
                    const selectId = 'select-neo-' + idx;
                    let radios = '';
                    if (item.total > 1) {
                        radios =
                            '<div class="mt-3 space-y-2 border-t border-gray-200 dark:border-gray-600 pt-3">' +
                            '<p class="text-sm font-medium text-gray-700 dark:text-gray-300">Esta URL tiene ' + item.total + ' filas en neo. ¿Qué quieres eliminar?</p>' +
                            '<label class="flex items-center gap-2 cursor-pointer">' +
                            '<input type="radio" name="' + nameBase + '" value="todas" class="neo-alcance rounded border-gray-300 text-red-600" checked>' +
                            '<span>Eliminar <strong>todas</strong> las filas con esta URL</span></label>' +
                            '<label class="flex items-center gap-2 cursor-pointer">' +
                            '<input type="radio" name="' + nameBase + '" value="una" class="neo-alcance rounded border-gray-300 text-red-600">' +
                            '<span>Eliminar <strong>solo una</strong> fila (elige el id neo)</span></label>' +
                            '<div class="neo-select-una ml-6 hidden">' +
                            '<label class="text-sm text-gray-600 dark:text-gray-400">Fila neo (id):</label> ' +
                            '<select id="' + selectId + '" class="mt-1 rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm px-3 py-1.5">' +
                            item.filas.map(function(f) {
                                return '<option value="' + f.id + '">' + f.id + ' (añadida: ' + (f.aniadida || '—') + ')</option>';
                            }).join('') +
                            '</select></div></div>';
                    } else {
                        radios = '<p class="text-sm text-gray-500 dark:text-gray-400 mt-2">Una sola fila: se eliminará el id ' + item.filas[0].id + '.</p>';
                    }
                    return '<div class="border border-gray-200 dark:border-gray-600 rounded-lg p-4 neo-bloque-url">' +
                        '<p class="font-mono text-sm break-all text-gray-900 dark:text-gray-100">' + safeUrl + '</p>' +
                        '<p class="text-xs text-gray-500 mt-1">Filas: ' + item.total + '</p>' +
                        '<ul class="mt-2 text-xs font-mono text-gray-600 dark:text-gray-400">' +
                        item.filas.map(function(f) {
                            return '<li>id ' + f.id + ' · añadida ' + escaparHtml(String(f.aniadida || '')) + '</li>';
                        }).join('') +
                        '</ul>' + radios + '</div>';
                }).join('');

                tablaOk.querySelectorAll('.neo-alcance').forEach(function(r) {
                    r.addEventListener('change', function() {
                        const bloque = r.closest('.neo-bloque-url');
                        const sel = bloque.querySelector('.neo-select-una');
                        if (!sel) return;
                        if (r.value === 'una') {
                            sel.classList.remove('hidden');
                        } else {
                            sel.classList.add('hidden');
                        }
                    });
                });
            }

            btnComprobar.addEventListener('click', async function() {
                limpiarResultados();
                btnComprobar.disabled = true;
                btnEliminar.disabled = true;
                ultimaData = null;
                resetearResumen();

                try {
                    const lineas = parsearUrlsTexto(textarea.value);
                    if (!lineas.length) {
                        mostrarMensaje('No hay ninguna URL válida en el texto (líneas vacías).', true);
                        return;
                    }

                    bloqueResumen.classList.remove('hidden');
                    actualizarContadoresResumen(0, 0);

                    const unicosOrden = [];
                    const vistos = new Set();
                    lineas.forEach(function(url) {
                        if (!vistos.has(url)) {
                            vistos.add(url);
                            unicosOrden.push(url);
                        }
                    });

                    const bloques = dividirEnBloques(unicosOrden, TAMANO_BLOQUE);
                    const dataAcumulada = {
                        urls_en_texto: lineas.length,
                        urls_unicas: unicosOrden.length,
                        repetidas_en_texto: lineas.length > unicosOrden.length,
                        no_encontradas: [],
                        encontradas: [],
                    };

                    iniciarProgreso(
                        'Comprobando URLs',
                        unicosOrden.length,
                        'Enviando bloques de ' + TAMANO_BLOQUE + ' URLs al servidor…'
                    );

                    let urlsComprobadas = 0;
                    for (let i = 0; i < bloques.length; i++) {
                        actualizarProgreso(
                            urlsComprobadas,
                            'Bloque ' + (i + 1) + ' de ' + bloques.length + ' · comprobando ' + bloques[i].length + ' URL(s)…',
                            'Encontradas hasta ahora: ' + dataAcumulada.encontradas.length + ' · No encontradas: ' + dataAcumulada.no_encontradas.length
                        );
                        const data = await fetchJson(urlComprobar, { urls: bloques[i].join('\n') });
                        if (data.no_encontradas && data.no_encontradas.length) {
                            dataAcumulada.no_encontradas = dataAcumulada.no_encontradas.concat(data.no_encontradas);
                        }
                        if (data.encontradas && data.encontradas.length) {
                            dataAcumulada.encontradas = dataAcumulada.encontradas.concat(data.encontradas);
                        }
                        urlsComprobadas += bloques[i].length;
                        actualizarContadoresResumen(dataAcumulada.encontradas.length, dataAcumulada.no_encontradas.length);
                        actualizarProgreso(
                            urlsComprobadas,
                            'Bloque ' + (i + 1) + ' de ' + bloques.length + ' completado.',
                            'Encontradas: ' + dataAcumulada.encontradas.length + ' · No encontradas: ' + dataAcumulada.no_encontradas.length
                        );
                    }

                    marcarProgresoCompletado(
                        'Comprobación completada',
                        urlsComprobadas + ' / ' + unicosOrden.length + ' URLs comprobadas.',
                        'Encontradas: ' + dataAcumulada.encontradas.length + ' · No encontradas: ' + dataAcumulada.no_encontradas.length
                    );
                    progresoContador.textContent = urlsComprobadas + ' / ' + unicosOrden.length;

                    await new Promise(function(resolve) { setTimeout(resolve, 800); });
                    ocultarProgreso();
                    ultimaData = dataAcumulada;
                    const data = dataAcumulada;

                    actualizarResumen(data.encontradas, data.no_encontradas, {
                        repetidas_en_texto: data.repetidas_en_texto,
                        urls_en_texto: data.urls_en_texto,
                        urls_unicas: data.urls_unicas,
                        seleccion: data.encontradas.length > 0 ? 'encontradas' : (data.no_encontradas.length > 0 ? 'no-encontradas' : null),
                    });

                    if (data.encontradas && data.encontradas.length) {
                        btnEliminar.disabled = false;
                        if (data.no_encontradas && data.no_encontradas.length) {
                            mostrarMensaje('Comprobación lista (' + bloques.length + ' bloques): hay URLs que no están en neo (revisa el aviso). Puedes eliminar solo las que sí existen.', false);
                        } else {
                            mostrarMensaje('Todas las URLs únicas existen en neo (' + bloques.length + ' bloques). Puedes confirmar la eliminación.', false);
                        }
                    } else if (data.no_encontradas && data.no_encontradas.length) {
                        mostrarMensaje('Ninguna URL del listado está en neo (' + data.no_encontradas.length + ' no encontradas).', true);
                    } else {
                        resetearResumen();
                        mostrarMensaje('Ninguna URL del listado coincide con neo.url.', true);
                    }
                } catch (e) {
                    ocultarProgreso();
                    mostrarMensaje(e.message || 'Error de red o respuesta inválida.', true);
                } finally {
                    btnComprobar.disabled = false;
                }
            });

            btnEliminar.addEventListener('click', async function() {
                if (!ultimaData || !ultimaData.encontradas || !ultimaData.encontradas.length) {
                    return;
                }
                bloqueResElim.classList.add('hidden');
                const acciones = [];
                ultimaData.encontradas.forEach(function(item, idx) {
                    const bloques = tablaOk.querySelectorAll('.neo-bloque-url');
                    const bloque = bloques[idx];
                    const url = item.url;
                    if (item.total === 1) {
                        acciones.push({ url: url, alcance: 'una', neo_id: item.filas[0].id });
                        return;
                    }
                    if (!bloque) {
                        acciones.push({ url: url, alcance: 'todas' });
                        return;
                    }
                    const rad = bloque.querySelector('input.neo-alcance:checked');
                    const alcance = rad ? rad.value : 'todas';
                    if (alcance === 'todas') {
                        acciones.push({ url: url, alcance: 'todas' });
                    } else {
                        const sel = bloque.querySelector('select');
                        const neoId = sel ? parseInt(sel.value, 10) : null;
                        acciones.push({ url: url, alcance: 'una', neo_id: neoId });
                    }
                });

                if (!acciones.length) {
                    mostrarMensaje('No hay acciones que ejecutar.', true);
                    return;
                }

                if (!confirm('¿Seguro que quieres eliminar las filas neo indicadas? Esta acción no se puede deshacer.')) {
                    return;
                }

                btnEliminar.disabled = true;
                btnComprobar.disabled = true;
                try {
                    const bloques = dividirEnBloques(acciones, TAMANO_BLOQUE);
                    const resultadoAcumulado = {
                        total_eliminadas: 0,
                        total_csv_aniadida_neo_no: 0,
                        total_omitidas_oferta_producto: 0,
                        detalle: [],
                    };

                    iniciarProgreso(
                        'Eliminando URLs',
                        acciones.length,
                        'Enviando bloques de ' + TAMANO_BLOQUE + ' URLs al servidor…'
                    );

                    let urlsProcesadas = 0;
                    for (let i = 0; i < bloques.length; i++) {
                        actualizarProgreso(
                            urlsProcesadas,
                            'Bloque ' + (i + 1) + ' de ' + bloques.length + ' · eliminando ' + bloques[i].length + ' URL(s)…',
                            'Filas neo eliminadas: ' + resultadoAcumulado.total_eliminadas
                        );
                        const data = await fetchJson(urlEjecutar, { acciones: bloques[i] });
                        resultadoAcumulado.total_eliminadas += data.total_eliminadas || 0;
                        resultadoAcumulado.total_csv_aniadida_neo_no += data.total_csv_aniadida_neo_no || 0;
                        resultadoAcumulado.total_omitidas_oferta_producto += data.total_omitidas_oferta_producto || 0;
                        if (data.detalle && data.detalle.length) {
                            resultadoAcumulado.detalle = resultadoAcumulado.detalle.concat(data.detalle);
                        }
                        urlsProcesadas += bloques[i].length;
                        actualizarProgreso(
                            urlsProcesadas,
                            'Bloque ' + (i + 1) + ' de ' + bloques.length + ' completado.',
                            'Filas neo eliminadas: ' + resultadoAcumulado.total_eliminadas +
                                (resultadoAcumulado.total_omitidas_oferta_producto > 0
                                    ? ' · omitidas (ofertas_producto): ' + resultadoAcumulado.total_omitidas_oferta_producto
                                    : '') +
                                (resultadoAcumulado.total_csv_aniadida_neo_no > 0
                                    ? ' · csv_ofertas aniadida_neo=no: ' + resultadoAcumulado.total_csv_aniadida_neo_no
                                    : '')
                        );
                    }

                    marcarProgresoCompletado(
                        'Eliminación completada',
                        urlsProcesadas + ' / ' + acciones.length + ' URLs procesadas.',
                        'Filas neo eliminadas: ' + resultadoAcumulado.total_eliminadas +
                            (resultadoAcumulado.total_omitidas_oferta_producto > 0
                                ? ' · omitidas (ofertas_producto): ' + resultadoAcumulado.total_omitidas_oferta_producto
                                : '') +
                            (resultadoAcumulado.total_csv_aniadida_neo_no > 0
                                ? ' · csv_ofertas aniadida_neo=no: ' + resultadoAcumulado.total_csv_aniadida_neo_no
                                : '')
                    );
                    progresoContador.textContent = urlsProcesadas + ' / ' + acciones.length;

                    const data = resultadoAcumulado;
                    bloqueResElim.classList.remove('hidden');
                    var lineasDet = (data.detalle || []).map(function(d) {
                        var t = (d.url || '').substring(0, 80) + ((d.url || '').length > 80 ? '…' : '');
                        if (d.aviso) return t + ': ' + d.aviso;
                        var parte = t + ' → ' + (d.eliminadas || 0) + ' fila(s) (' + (d.alcance || '') + (d.neo_id ? ', id ' + d.neo_id : '') + ')';
                        if (d.csv_aniadida_neo_no > 0) {
                            parte += ', csv_ofertas aniadida_neo=no: ' + d.csv_aniadida_neo_no;
                        }
                        return parte;
                    });
                    var resumenCsv = (data.total_csv_aniadida_neo_no > 0)
                        ? ' Filas csv_ofertas marcadas aniadida_neo=no: ' + data.total_csv_aniadida_neo_no + '.'
                        : '';
                    var resumenOmitidas = (data.total_omitidas_oferta_producto > 0)
                        ? ' Omitidas por existir en ofertas_producto: ' + data.total_omitidas_oferta_producto + '.'
                        : '';
                    var resumenBloques = bloques.length > 1 ? ' Procesado en ' + bloques.length + ' bloques.' : '';
                    var textoDetalle = lineasDet.length <= 20
                        ? (lineasDet.length ? ' ' + lineasDet.join(' | ') : '')
                        : ' Detalle: ' + lineasDet.length + ' URLs procesadas (lista omitida por tamaño).';
                    textoResElim.textContent = 'Filas eliminadas en total: ' + data.total_eliminadas + '.' + resumenOmitidas + resumenCsv + resumenBloques + textoDetalle;
                    mostrarMensaje('Eliminación correcta (' + bloques.length + ' bloques).', false);
                    ultimaData = null;
                    btnEliminar.disabled = true;
                    resetearResumen();

                    setTimeout(function() {
                        ocultarProgreso();
                    }, 2500);
                } catch (e) {
                    ocultarProgreso();
                    mostrarMensaje(e.message || 'Error de red al eliminar.', true);
                } finally {
                    btnComprobar.disabled = false;
                }
            });
        });
    </script>
</x-app-layout>
