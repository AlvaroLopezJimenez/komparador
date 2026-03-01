<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.dashboard') }}">
                <h2 class="font-semibold text-xl text-white leading-tight">Inicio -></h2>
            </a>
            <a href="{{ route('admin.ofertas.todas') }}">
                <h2 class="font-semibold text-xl text-white leading-tight">Ofertas -></h2>
            </a>
            <h2 class="font-semibold text-xl text-white leading-tight">Crear ofertas en masa</h2>
        </div>
    </x-slot>

    <div class="max-w-6xl mx-auto py-10 px-4 space-y-8">
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6 border border-gray-200 dark:border-gray-700">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">Crear ofertas en masa</h1>
            <p class="text-gray-600 dark:text-gray-400 mb-6">
                Pega una lista de URLs de ofertas (una por línea). El sistema detectará el producto, la tienda, si la URL ya existe, y las especificaciones internas a marcar. Después podrás generar las ofertas de forma automática.
            </p>

            <form id="formAnalizar" class="space-y-6">
                @csrf
                <div>
                    <label class="block mb-2 font-medium text-gray-700 dark:text-gray-200">URLs (una por línea)</label>
                    <textarea name="urls" id="urls" rows="12" required
                        class="w-full px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white border border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500 font-mono text-sm"
                        placeholder="https://www.coolmod.com/producto-1/&#10;https://www.pccomponentes.com/producto-2/"></textarea>
                </div>
                <button type="submit" id="btnAnalizar"
                    class="inline-flex items-center bg-blue-600 hover:bg-blue-700 text-white font-semibold px-6 py-3 rounded-md transition disabled:opacity-50 disabled:cursor-not-allowed">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                    Analizar URLs
                </button>
            </form>
        </div>

        <div id="resultados" class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6 border border-gray-200 dark:border-gray-700 hidden">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Resultados</h2>
            <div id="resultadosLista" class="space-y-4"></div>
        </div>
    </div>

    {{-- Modal para ver imágenes de especificaciones internas --}}
    <div id="modal-imagenes-spec-crear-masivo" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white dark:bg-gray-900 rounded-lg p-6 max-w-5xl w-full relative shadow-xl max-h-[90vh] flex flex-col">
            <button type="button" onclick="cerrarModalImagenesSpecCrearMasivo()" class="absolute top-3 right-4 text-xl text-gray-800 dark:text-gray-100 hover:text-gray-600 dark:hover:text-gray-300 z-10">×</button>
            <div class="mb-4">
                <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-200">Imágenes de la especificación</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">Haz clic en una miniatura para verla en grande</p>
            </div>
            <div class="flex-1 overflow-y-auto">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="md:col-span-2">
                        <div class="bg-gray-100 dark:bg-gray-800 rounded-lg p-4 flex items-center justify-center" style="min-height: 400px;">
                            <img id="imagen-grande-spec-crear-masivo" src="" alt="" class="max-w-full max-h-96 object-contain rounded">
                        </div>
                    </div>
                    <div class="md:col-span-1">
                        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-3">Miniaturas</h4>
                        <div id="miniaturas-container-spec-crear-masivo" class="space-y-2 max-h-96 overflow-y-auto">
                            <p class="text-sm text-gray-500 dark:text-gray-400">No hay imágenes</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="flex justify-end gap-3 mt-4">
                <button type="button" onclick="cerrarModalImagenesSpecCrearMasivo()"
                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">
                    Cerrar
                </button>
            </div>
        </div>
    </div>

    <script>
    window.__crearMasivoImagenesSublinea = {};
    document.getElementById('formAnalizar').addEventListener('submit', async function(e) {
        e.preventDefault();
        const btn = document.getElementById('btnAnalizar');
        btn.disabled = true;
        btn.innerHTML = '<svg class="w-5 h-5 mr-2 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg> Analizando...';

        try {
            const res = await fetch('{{ route("admin.ofertas.crear-masivo.analizar") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ urls: document.getElementById('urls').value }),
            });
            const data = await res.json();

            if (data.success) {
                mostrarResultados(data.resultados);
            } else {
                alert(data.message || 'Error al analizar');
            }
        } catch (err) {
            alert('Error de conexión: ' + err.message);
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg> Analizar URLs';
        }
    });

    function mostrarResultados(resultados) {
        const contenedor = document.getElementById('resultadosLista');
        contenedor.innerHTML = '';
        document.getElementById('resultados').classList.remove('hidden');

        resultados.forEach((r, idx) => {
            const div = document.createElement('div');
            const puedeCrear = !r.existe && r.tienda && r.producto && !r.error;
            let estadoClass = 'bg-gray-50 dark:bg-gray-800';
            let estadoText = '';
            if (r.error) {
                estadoClass = 'bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700';
                estadoText = r.error;
            } else if (r.existe) {
                estadoClass = 'bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700';
                estadoText = r.existe_otros_productos ? 'URL ya existe en otros productos' : 'URL ya existe';
            } else if (puedeCrear) {
                estadoClass = 'bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-700';
                estadoText = 'Lista para crear';
            } else {
                estadoText = 'No se pudo determinar producto o tienda';
            }

            let especsHtml = buildEspecsHtml(r.producto, r.especificaciones, r.tiene_especificaciones);

            let selectorEmpateHtml = '';
            if (puedeCrear && r.hay_empate && r.candidatos_empatados && r.candidatos_empatados.length > 1) {
                selectorEmpateHtml = '<div class="mt-2 p-3 bg-amber-50 dark:bg-amber-900/20 rounded border border-amber-200 dark:border-amber-700"><span class="text-sm font-medium text-amber-800 dark:text-amber-200">Varios productos coinciden. Elige el correcto:</span><div class="mt-2 flex flex-wrap gap-2">' +
                    r.candidatos_empatados.map((c, i) => '<button type="button" class="btn-elegir-producto px-3 py-1.5 text-sm rounded border transition ' + (i === 0 ? 'border-green-600 bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-200' : 'border-gray-300 dark:border-gray-600 hover:border-green-500') + '" data-candidato-idx="' + i + '">' + (c.texto_completo || c.nombre || c.id) + '</button>').join('') +
                    '</div></div>';
            }

            let ofertasExistentesHtml = '';
            if (r.ofertas_existentes && r.ofertas_existentes.length) {
                ofertasExistentesHtml = '<div class="mt-2 text-sm text-amber-700 dark:text-amber-300 space-y-1"><span class="font-medium">Existe en:</span>' +
                    r.ofertas_existentes.map(o => {
                        const prodLink = o.url_producto
                            ? '<a href="' + o.url_producto + '" target="_blank" class="text-green-500 hover:underline font-medium">' + (o.producto || '') + '</a>'
                            : (o.producto || '');
                        const editarBtn = o.oferta_edit_url
                            ? ' <a href="' + o.oferta_edit_url + '" target="_blank" class="inline-flex items-center px-2 py-0.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium rounded">Editar oferta</a>'
                            : '';
                        return '<div>' + prodLink + ' – ' + (o.tienda || '') + ' ' + editarBtn + '</div>';
                    }).join('') + '</div>';
            }

            div.className = 'p-4 rounded-lg border ' + estadoClass;
            div.dataset.esUnidadUnica = r.especificaciones && r.especificaciones.unidad_de_medida === 'unidadUnica' ? '1' : '0';
            div.dataset.columnasIds = (r.especificaciones && r.especificaciones.columnas_ids) ? JSON.stringify(r.especificaciones.columnas_ids) : '[]';
            div.innerHTML = `
                <div class="flex justify-between items-start gap-4">
                    <div class="flex-1 min-w-0">
                        <div class="font-medium text-gray-900 dark:text-white">${estadoText}</div>
                        <div class="mt-1 text-sm break-all">
                            <a href="${r.url_normalizada || r.url}" target="_blank" class="text-blue-500 hover:underline block" title="${r.url_normalizada || r.url || ''}">${r.url_normalizada || r.url || ''}</a>
                        </div>
                        ${r.tienda ? '<div class="mt-1 text-sm text-gray-600 dark:text-gray-400">Tienda: <strong>' + r.tienda.nombre + '</strong></div>' : ''}
                        ${r.producto ? '<div class="mt-1 text-sm text-gray-600 dark:text-gray-400 producto-display">Producto: ' + (r.producto.url_producto ? '<a href="' + r.producto.url_producto + '" target="_blank" class="text-green-500 hover:underline font-medium">' + r.producto.texto_completo + '</a>' : '<strong>' + r.producto.texto_completo + '</strong>') + '</div>' : ''}
                        ${selectorEmpateHtml}
                        <div class="spec-and-ofertas-container">${especsHtml}
                        ${ofertasExistentesHtml}</div>
                    </div>
                    ${puedeCrear ? `
                    <div class="flex-shrink-0">
                        <button type="button" class="btn-generar inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded transition"
                            data-url="${r.url_normalizada}"
                            data-producto-id="${r.producto.id}"
                            data-tienda-id="${r.tienda.id}">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                            Generar oferta
                        </button>
                    </div>
                    ` : ''}
                </div>
                <div class="mt-2 generado-msg hidden text-sm font-medium"></div>
            `;

            contenedor.appendChild(div);
            div.__rowData = r;

            div.addEventListener('click', function(e) {
                const btn = e.target.closest('.btn-ver-imagenes-spec');
                if (btn && btn.dataset.key) abrirModalImagenesSpecCrearMasivo(btn.dataset.key);
                const btnRecargar = e.target.closest('.btn-recargar-especs');
                if (btnRecargar && btnRecargar.dataset.productoId) {
                    e.preventDefault();
                    recargarEspecificacionesFila(div, btnRecargar.dataset.productoId);
                    return;
                }
                const btnElegir = e.target.closest('.btn-elegir-producto');
                if (btnElegir && div.__candidatosEmpatados) {
                    const idx = parseInt(btnElegir.dataset.candidatoIdx, 10);
                    const cand = div.__candidatosEmpatados[idx];
                    if (cand) aplicarProductoSeleccionado(div, cand);
                }
            });

            if (puedeCrear && r.hay_empate && r.candidatos_empatados) {
                div.__candidatosEmpatados = r.candidatos_empatados;
            }

            if (puedeCrear) {
                div.querySelector('.btn-generar').addEventListener('click', async function() {
                    const btnGen = this;
                    btnGen.disabled = true;
                    btnGen.innerHTML = '<svg class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg> Creando...';

                    try {
                        const especs = buildEspecificacionesFromRow(div);
                        console.log('[CrearOfertaBulk] Especificaciones built:', especs);
                        const body = {
                            url: btnGen.dataset.url,
                            producto_id: btnGen.dataset.productoId,
                            tienda_id: btnGen.dataset.tiendaId,
                            especificaciones_internas: especs ? JSON.stringify(especs) : null,
                        };
                        console.log('[CrearOfertaBulk] Request body:', JSON.stringify(body, null, 2));
                        const res2 = await fetch('{{ route("admin.ofertas.crear-masivo.crear") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify(body),
                        });
                        const responseText = await res2.text();
                        console.log('[CrearOfertaBulk] Response status:', res2.status);
                        console.log('[CrearOfertaBulk] Response body (raw):', responseText);
                        let data2;
                        try {
                            data2 = JSON.parse(responseText);
                        } catch (e) {
                            console.error('[CrearOfertaBulk] Error parsing JSON:', e);
                            data2 = { success: false, error: 'Respuesta inválida del servidor' };
                        }

                        console.log('[CrearOfertaBulk] Parsed data:', data2);
                        if (data2.error) console.error('[CrearOfertaBulk] Server error:', data2.error);

                        const msgEl = div.querySelector('.generado-msg');
                        msgEl.classList.remove('hidden');
                        if (data2.success) {
                            msgEl.className = 'mt-2 generado-msg text-sm font-medium text-green-600 dark:text-green-400';
                            msgEl.textContent = 'Oferta creada correctamente (ID: ' + data2.oferta_id + ')';
                            if (data2.oferta_edit_url) {
                                const btnEditar = document.createElement('a');
                                btnEditar.href = data2.oferta_edit_url;
                                btnEditar.target = '_blank';
                                btnEditar.className = 'inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded transition';
                                btnEditar.innerHTML = '<svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>Editar oferta';
                                btnGen.parentNode.replaceChild(btnEditar, btnGen);
                            } else {
                                btnGen.remove();
                            }
                        } else {
                            msgEl.className = 'mt-2 generado-msg text-sm font-medium text-red-600 dark:text-red-400';
                            msgEl.textContent = data2.error || 'Error al crear';
                            btnGen.disabled = false;
                            btnGen.innerHTML = '<svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg> Generar oferta';
                        }
                    } catch (err) {
                        console.error('[CrearOfertaBulk] Exception:', err);
                        div.querySelector('.generado-msg').classList.remove('hidden');
                        div.querySelector('.generado-msg').className = 'mt-2 generado-msg text-sm font-medium text-red-600 dark:text-red-400';
                        div.querySelector('.generado-msg').textContent = 'Error: ' + err.message;
                        btnGen.disabled = false;
                        btnGen.innerHTML = '<svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg> Generar oferta';
                    }
                });
            }
        });
    }

    function buildEspecsHtml(producto, especificaciones, tieneEspecificaciones) {
        if (!producto) return '';
        const filtros = (especificaciones && especificaciones.filtros) ? especificaciones.filtros : [];
        const esUnidadUnica = especificaciones && especificaciones.unidad_de_medida === 'unidadUnica';
        const columnasIds = (especificaciones && especificaciones.columnas_ids) ? especificaciones.columnas_ids : [];
        if (tieneEspecificaciones && filtros.length) {
            const productoId = producto && producto.id ? producto.id : null;
            const btnRecargar = productoId ? '<button type="button" class="btn-recargar-especs ml-2 inline-flex items-center px-2 py-1 text-xs bg-gray-200 hover:bg-gray-300 dark:bg-gray-600 dark:hover:bg-gray-500 text-gray-700 dark:text-gray-200 rounded transition" data-producto-id="' + productoId + '" title="Recargar especificaciones">🔄 Recargar</button>' : '';
            let h = '<div class="mt-3 spec-selector-container"><span class="flex items-center flex-wrap gap-1"><strong class="text-sm text-gray-700 dark:text-gray-300">Especificaciones a marcar:</strong>' + btnRecargar + '</span><div class="mt-2 space-y-3">';
            const imagenesProducto = (producto.imagenes_producto) ? producto.imagenes_producto : [];
            filtros.forEach((f) => {
                const subprincipales = f.subprincipales || [];
                const esColumna = esUnidadUnica && columnasIds.includes(f.id);
                if (subprincipales.length === 0) return;
                h += '<div class="spec-line border-l-2 border-gray-300 dark:border-gray-600 pl-3" data-principal-id="' + f.id + '" data-es-columna="' + (esColumna ? '1' : '0') + '">';
                h += '<label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">' + (f.texto || f.id) + (esColumna ? ' <span class="text-orange-600 dark:text-orange-400 text-xs">(Columna)</span>' : '') + '</label><div class="flex flex-wrap gap-2">';
                subprincipales.forEach((sub) => {
                    let imagenes = Array.isArray(sub.imagenes) ? sub.imagenes : [];
                    if (sub.usar_imagenes_producto && imagenesProducto.length) imagenes = imagenesProducto;
                    const keyImg = String(f.id) + '::' + String(sub.id);
                    if (imagenes.length) window.__crearMasivoImagenesSublinea[keyImg] = imagenes;
                    const btnImgs = imagenes.length ? '<button type="button" class="btn-ver-imagenes-spec inline-flex items-center p-1 bg-blue-600 hover:bg-blue-700 text-white rounded text-xs ml-0.5" data-key="' + keyImg + '" title="Ver ' + imagenes.length + ' imagen' + (imagenes.length !== 1 ? 'es' : '') + '"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg></button>' : '';
                    h += '<label class="inline-flex items-center gap-1 cursor-pointer"><input type="checkbox" class="spec-checkbox rounded border-gray-300 text-green-600 focus:ring-green-500" data-principal-id="' + f.id + '" data-sublinea-id="' + sub.id + '" data-es-columna="' + (esColumna ? '1' : '0') + '"><span class="text-sm text-gray-600 dark:text-gray-400">' + (sub.texto || sub.id) + '</span>' + btnImgs + '</label>';
                });
                h += '</div></div>';
            });
            return h + '</div></div>';
        }
        return '<div class="mt-2 text-sm text-gray-500 dark:text-gray-500">Sin especificaciones internas</div>';
    }

    async function recargarEspecificacionesFila(div, productoId) {
        const btn = div.querySelector('.btn-recargar-especs');
        if (!btn || !div.__rowData || !div.__rowData.producto) return;
        const specContainer = div.querySelector('.spec-selector-container');
        if (!specContainer) return;
        const originalHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="animate-spin">⟳</span> Cargando...';
        try {
            const url = '{{ route("admin.ofertas.crear-masivo.recargar-especificaciones", ["producto" => "__ID__"]) }}'.replace('__ID__', productoId);
            const res = await fetch(url, {
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
            });
            const data = await res.json();
            if (data.success && data.especificaciones !== undefined) {
                const r = div.__rowData;
                const producto = Object.assign({}, r.producto);
                const especs = data.especificaciones;
                const tieneEspecs = data.tiene_especificaciones;
                const newHtml = buildEspecsHtml(producto, especs, tieneEspecs);
                specContainer.outerHTML = newHtml;
                div.__rowData = Object.assign({}, r, { especificaciones: especs, tiene_especificaciones: tieneEspecs });
                div.dataset.esUnidadUnica = (especs && especs.unidad_de_medida === 'unidadUnica') ? '1' : '0';
                div.dataset.columnasIds = (especs && especs.columnas_ids) ? JSON.stringify(especs.columnas_ids) : '[]';
            } else {
                alert('No se pudieron recargar las especificaciones');
            }
        } catch (err) {
            alert('Error: ' + err.message);
        } finally {
            btn.disabled = false;
            btn.innerHTML = originalHtml;
        }
    }

    function aplicarProductoSeleccionado(div, candidato) {
        const r = div.__rowData;
        if (!r || !r.tienda) return;
        const productDisplay = div.querySelector('.producto-display');
        if (productDisplay) {
            const linkHtml = candidato.url_producto
                ? '<a href="' + candidato.url_producto + '" target="_blank" class="text-green-500 hover:underline font-medium">' + candidato.texto_completo + '</a>'
                : '<strong>' + candidato.texto_completo + '</strong>';
            productDisplay.innerHTML = 'Producto: ' + linkHtml;
        }
        const specParent = div.querySelector('.spec-and-ofertas-container');
        if (specParent && specParent.firstElementChild) {
            const newSpecsHtml = buildEspecsHtml(candidato, candidato.especificaciones, candidato.tiene_especificaciones);
            specParent.firstElementChild.outerHTML = newSpecsHtml;
        }
        div.dataset.esUnidadUnica = (candidato.especificaciones && candidato.especificaciones.unidad_de_medida === 'unidadUnica') ? '1' : '0';
        div.dataset.columnasIds = (candidato.especificaciones && candidato.especificaciones.columnas_ids) ? JSON.stringify(candidato.especificaciones.columnas_ids) : '[]';
        const btnGen = div.querySelector('.btn-generar');
        if (btnGen) btnGen.dataset.productoId = candidato.id;
        div.__rowData = Object.assign({}, r, { producto: candidato, especificaciones: candidato.especificaciones, tiene_especificaciones: candidato.tiene_especificaciones });
        const btns = div.querySelectorAll('.btn-elegir-producto');
        btns.forEach((b, i) => {
            const idx = parseInt(b.dataset.candidatoIdx, 10);
            const isSelected = div.__candidatosEmpatados && div.__candidatosEmpatados[idx] && div.__candidatosEmpatados[idx].id === candidato.id;
            b.className = 'btn-elegir-producto px-3 py-1.5 text-sm rounded border transition ' + (isSelected ? 'border-green-600 bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-200' : 'border-gray-300 dark:border-gray-600 hover:border-green-500');
        });
    }

    function buildEspecificacionesFromRow(rowEl) {
        const checkboxes = rowEl.querySelectorAll('.spec-checkbox:checked');
        if (!checkboxes.length) return null;
        const especs = {};
        const columnasIds = JSON.parse(rowEl.dataset.columnasIds || '[]');
        const esUnidadUnica = rowEl.dataset.esUnidadUnica === '1';
        checkboxes.forEach(cb => {
            const pid = cb.dataset.principalId;
            const subId = cb.dataset.sublineaId;
            if (!pid || !subId) return;
            if (!especs[pid]) especs[pid] = [];
            especs[pid].push(subId);
        });
        if (esUnidadUnica && columnasIds.length) {
            especs._columnas = {};
            columnasIds.forEach(pid => {
                const subIds = especs[pid];
                if (subIds && subIds.length) {
                    especs._columnas[pid] = subIds[0];
                }
            });
        }
        return Object.keys(especs).length ? especs : null;
    }

    function resolverUrlImagenCrearMasivo(imgPath) {
        const publicBase = @json(url('/'));
        const imagesBase = @json(asset('images/'));
        if (!imgPath) return '';
        let p = String(imgPath).trim();
        if (!p) return '';
        if (/^https?:\/\//i.test(p)) return p;
        if (/^\/\//.test(p)) return p;
        p = p.replace(/^\/+/, '');
        if (/^images\//i.test(p)) return (publicBase.replace(/\/+$/, '')) + '/' + p;
        return (imagesBase.replace(/\/+$/, '')) + '/' + p;
    }

    function abrirModalImagenesSpecCrearMasivo(key) {
        const modal = document.getElementById('modal-imagenes-spec-crear-masivo');
        if (!modal) return;
        const imagenes = (window.__crearMasivoImagenesSublinea && window.__crearMasivoImagenesSublinea[key]) ? window.__crearMasivoImagenesSublinea[key] : [];
        window.__crearMasivoImagenesActual = { key, imagenes: Array.isArray(imagenes) ? imagenes : [] };
        renderizarMiniaturasSpecCrearMasivo();
        modal.classList.remove('hidden');
    }

    function cerrarModalImagenesSpecCrearMasivo() {
        const modal = document.getElementById('modal-imagenes-spec-crear-masivo');
        if (!modal) return;
        modal.classList.add('hidden');
        window.__crearMasivoImagenesActual = { key: null, imagenes: [] };
    }

    function renderizarMiniaturasSpecCrearMasivo() {
        const container = document.getElementById('miniaturas-container-spec-crear-masivo');
        const imgGrande = document.getElementById('imagen-grande-spec-crear-masivo');
        if (!container || !imgGrande) return;
        container.innerHTML = '';
        const imagenes = (window.__crearMasivoImagenesActual && Array.isArray(window.__crearMasivoImagenesActual.imagenes)) ? window.__crearMasivoImagenesActual.imagenes : [];
        if (imagenes.length === 0) {
            imgGrande.src = '';
            imgGrande.alt = 'No hay imágenes';
            container.innerHTML = '<p class="text-sm text-gray-500 dark:text-gray-400">No hay imágenes</p>';
            return;
        }
        if (imagenes[0]) {
            imgGrande.src = resolverUrlImagenCrearMasivo(imagenes[0]);
            imgGrande.alt = 'Imagen 1';
        }
        imagenes.forEach((imgPath, index) => {
            const div = document.createElement('div');
            div.className = 'miniatura-spec-crear-masivo cursor-pointer border-2 border-gray-300 dark:border-gray-600 rounded p-1 hover:border-blue-500 transition-colors';
            if (index === 0) div.classList.add('border-blue-500');
            const url = resolverUrlImagenCrearMasivo(imgPath);
            div.innerHTML = '<img src="' + url + '" alt="Miniatura ' + (index + 1) + '" class="w-full h-20 object-cover rounded">';
            div.addEventListener('click', function() {
                imgGrande.src = url;
                imgGrande.alt = 'Imagen ' + (index + 1);
                container.querySelectorAll('.miniatura-spec-crear-masivo').forEach(m => m.classList.remove('border-blue-500'));
                div.classList.add('border-blue-500');
            });
            container.appendChild(div);
        });
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const modal = document.getElementById('modal-imagenes-spec-crear-masivo');
            if (modal && !modal.classList.contains('hidden')) cerrarModalImagenesSpecCrearMasivo();
        }
    });

    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('modal-imagenes-spec-crear-masivo');
        if (modal) {
            modal.addEventListener('click', function(e) {
                if (e.target === modal) cerrarModalImagenesSpecCrearMasivo();
            });
        }
    });
    </script>
</x-app-layout>
