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
            <p class="text-gray-600 dark:text-gray-400 mb-6">
                Pega URLs de la tabla <strong>neo</strong> (campo <code class="text-sm bg-gray-100 dark:bg-gray-700 px-1 rounded">url</code>), una por línea.
                Pulsa <strong>Comprobar URLs</strong> para ver si existen. Si alguna no está en la base de datos, se mostrará un aviso.
                Cuando quieras borrar, elige por cada URL con varias filas si eliminar solo una fila concreta o todas las que coincidan con esa URL.
            </p>

            <div class="space-y-4">
                <div>
                    <label class="block mb-2 font-medium text-gray-700 dark:text-gray-200">URLs (una por línea)</label>
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
            </div>
        </div>

        <div id="bloque-no-encontradas" class="hidden bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-xl p-6">
            <h3 class="text-lg font-semibold text-amber-900 dark:text-amber-100 mb-2">URLs no encontradas en neo</h3>
            <p class="text-sm text-amber-800 dark:text-amber-200 mb-3">Las siguientes URLs no tienen ninguna fila con ese valor exacto en el campo <code>url</code>:</p>
            <ul id="lista-no-encontradas" class="list-disc list-inside font-mono text-sm text-amber-900 dark:text-amber-100 space-y-1 break-all"></ul>
        </div>

        <div id="bloque-encontradas" class="hidden bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6 border border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Coincidencias en neo</h3>
            <p id="info-repetidas" class="text-sm text-gray-600 dark:text-gray-400 mb-4 hidden"></p>
            <div id="tabla-encontradas" class="space-y-6"></div>
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
            const bloqueNo = document.getElementById('bloque-no-encontradas');
            const listaNo = document.getElementById('lista-no-encontradas');
            const bloqueOk = document.getElementById('bloque-encontradas');
            const tablaOk = document.getElementById('tabla-encontradas');
            const infoRepetidas = document.getElementById('info-repetidas');
            const bloqueResElim = document.getElementById('bloque-resultado-eliminacion');
            const textoResElim = document.getElementById('texto-resultado-eliminacion');

            let ultimaData = null;

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

            function limpiarResultados() {
                bloqueResElim.classList.add('hidden');
                mensajeGlobal.classList.add('hidden');
            }

            btnComprobar.addEventListener('click', async function() {
                limpiarResultados();
                btnComprobar.disabled = true;
                btnEliminar.disabled = true;
                ultimaData = null;
                bloqueNo.classList.add('hidden');
                bloqueOk.classList.add('hidden');
                tablaOk.innerHTML = '';

                try {
                    const res = await fetch(urlComprobar, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrf,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify({ urls: textarea.value }),
                    });
                    const data = await res.json();
                    if (!res.ok) {
                        mostrarMensaje(data.message || 'Error al comprobar.', true);
                        return;
                    }
                    ultimaData = data;

                    if (data.repetidas_en_texto) {
                        infoRepetidas.textContent = 'Has repetido alguna URL en el texto (se comprueba una vez por URL única). ' +
                            'Líneas con URL: ' + data.urls_en_texto + ', únicas: ' + data.urls_unicas + '.';
                        infoRepetidas.classList.remove('hidden');
                    } else {
                        infoRepetidas.classList.add('hidden');
                    }

                    if (data.no_encontradas && data.no_encontradas.length) {
                        bloqueNo.classList.remove('hidden');
                        listaNo.innerHTML = data.no_encontradas.map(function(u) {
                            return '<li>' + escaparHtml(u) + '</li>';
                        }).join('');
                    } else {
                        bloqueNo.classList.add('hidden');
                    }

                    if (data.encontradas && data.encontradas.length) {
                        bloqueOk.classList.remove('hidden');
                        tablaOk.innerHTML = data.encontradas.map(function(item, idx) {
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

                        btnEliminar.disabled = false;
                        if (data.no_encontradas && data.no_encontradas.length) {
                            mostrarMensaje('Comprobación lista: hay URLs que no están en neo (revisa el aviso). Puedes eliminar solo las que sí existen.', false);
                        } else {
                            mostrarMensaje('Todas las URLs únicas existen en neo. Puedes confirmar la eliminación.', false);
                        }
                    } else {
                        mostrarMensaje('Ninguna URL del listado coincide con neo.url.', true);
                    }
                } catch (e) {
                    mostrarMensaje('Error de red o respuesta inválida.', true);
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
                    if (!bloque) return;
                    const url = item.url;
                    if (item.total === 1) {
                        acciones.push({ url: url, alcance: 'una', neo_id: item.filas[0].id });
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
                    const res = await fetch(urlEjecutar, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrf,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify({ acciones: acciones }),
                    });
                    const data = await res.json();
                    if (!res.ok) {
                        mostrarMensaje(data.message || 'Error al eliminar.', true);
                        return;
                    }
                    bloqueResElim.classList.remove('hidden');
                    var lineasDet = (data.detalle || []).map(function(d) {
                        var t = (d.url || '').substring(0, 80) + ((d.url || '').length > 80 ? '…' : '');
                        if (d.aviso) return t + ': ' + d.aviso;
                        return t + ' → ' + (d.eliminadas || 0) + ' fila(s) (' + (d.alcance || '') + (d.neo_id ? ', id ' + d.neo_id : '') + ')';
                    });
                    textoResElim.textContent = 'Filas eliminadas en total: ' + data.total_eliminadas + '.' +
                        (lineasDet.length ? ' ' + lineasDet.join(' | ') : '');
                    mostrarMensaje('Eliminación correcta.', false);
                    ultimaData = null;
                    btnEliminar.disabled = true;
                    bloqueOk.classList.add('hidden');
                    bloqueNo.classList.add('hidden');
                } catch (e) {
                    mostrarMensaje('Error de red al eliminar.', true);
                } finally {
                    btnComprobar.disabled = false;
                }
            });
        });
    </script>
</x-app-layout>
