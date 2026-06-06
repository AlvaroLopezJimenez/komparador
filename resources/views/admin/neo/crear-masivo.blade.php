<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.dashboard') }}">
                <h2 class="font-semibold text-xl text-white leading-tight">Inicio -></h2>
            </a>
            <a href="{{ route('admin.neo.index') }}">
                <h2 class="font-semibold text-xl text-white leading-tight">Neo -></h2>
            </a>
            <h2 class="font-semibold text-xl text-white leading-tight">Crear ofertas en masa</h2>
            <style>[x-cloak]{ display:none !important; }</style>
        </div>
    </x-slot>

    <div class="max-w-6xl mx-auto py-10 px-4 space-y-8">
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6 border border-gray-200 dark:border-gray-700">
            <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                <p class="text-gray-600 dark:text-gray-400">
                    <strong>Filas en neo con añadida = no:</strong> <span id="total-neo-aniadida-no">{{ $totalNeoAniadidaNo ?? 0 }}</span>
                    <span class="mx-2">|</span>
                    <strong>Sin URL:</strong> <span id="total-neo-aniadida-no-sin-url">{{ $totalNeoAniadidaNoSinUrl ?? 0 }}</span>
                </p>
                <div class="relative shrink-0 ml-auto" id="crear-masivo-ayuda-urls-wrap">
                    <button type="button" id="crear-masivo-ayuda-urls-btn"
                        class="inline-flex items-center justify-center w-7 h-7 rounded-full border border-gray-300 dark:border-gray-600 text-sm font-bold text-gray-600 dark:text-gray-300 bg-gray-50 dark:bg-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 hover:text-gray-900 dark:hover:text-white transition focus:outline-none focus:ring-2 focus:ring-blue-500"
                        aria-label="Ayuda: cómo crear ofertas en masa"
                        aria-expanded="false"
                        aria-controls="crear-masivo-ayuda-urls-panel">?</button>
                    <div id="crear-masivo-ayuda-urls-panel" role="tooltip"
                        class="hidden absolute right-0 top-full mt-2 z-30 w-72 sm:w-96 max-w-[calc(100vw-2rem)] p-3 text-sm text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg shadow-lg leading-relaxed">
                        Pega una lista de URLs de ofertas (una por línea). El sistema detectará el producto, la tienda, si la URL ya existe, y las especificaciones internas a marcar. Después podrás generar las ofertas de forma automática.
                    </div>
                </div>
            </div>
            <div class="mb-4 flex flex-wrap items-center gap-2">
                <button type="button" id="btnProductoNeo" onclick="var m=document.getElementById('modal-producto-neo'); if(m){m.classList.remove('hidden');}" class="inline-flex items-center bg-blue-500 hover:bg-blue-600 text-white font-semibold px-4 py-2.5 rounded shadow transition disabled:opacity-50 disabled:cursor-not-allowed">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                    Producto ({{ $totalProductosNeoAniadidaNo ?? 0 }})
                </button>
                <button type="button" id="btnCategoriaNeo" onclick="var m=document.getElementById('modal-categoria-neo'); if(m){m.classList.remove('hidden');}" class="inline-flex items-center bg-pink-500 hover:bg-pink-600 text-white font-semibold px-4 py-2.5 rounded shadow transition disabled:opacity-50 disabled:cursor-not-allowed">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path></svg>
                    Categoría ({{ $totalCategoriasNeoAniadidaNo ?? 0 }})
                </button>
                <button type="button" id="btnTiendaNeo" onclick="var m=document.getElementById('modal-tienda-neo'); if(m){m.classList.remove('hidden');}" class="inline-flex items-center bg-emerald-500 hover:bg-emerald-600 text-white font-semibold px-4 py-2.5 rounded shadow transition disabled:opacity-50 disabled:cursor-not-allowed">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7l1.664 9.152A2 2 0 006.632 18h10.736a2 2 0 001.968-1.848L21 7M8 11h8M12 7v8"></path></svg>
                    Tienda ({{ $totalTiendasNeoAniadidaNo ?? 0 }})
                </button>
                <span class="text-sm text-gray-500 dark:text-gray-400">Cargar URLs desde neo (añadida=no): por producto, por categoría o por tienda</span>
            </div>
            <form id="formAnalizar" class="space-y-6">
                @csrf
                <div>
                    <label class="block mb-2 font-medium text-gray-700 dark:text-gray-200">URLs (una por línea)</label>
                    <textarea name="urls" id="urls" rows="12" required
                        class="w-full px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white border border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500 font-mono text-sm"
                        placeholder="https://www.coolmod.com/producto-1/&#10;https://www.pccomponentes.com/producto-2/"></textarea>
                    <div id="neo-categoria-filtro-wrap" class="hidden mt-2 flex flex-wrap items-center gap-2 text-sm">
                        <span class="inline-flex items-center px-2 py-1 rounded bg-indigo-100 dark:bg-indigo-900/40 text-indigo-800 dark:text-indigo-200 border border-indigo-200 dark:border-indigo-700">
                            <span id="neo-categoria-filtro-texto"></span>
                        </span>
                        <button type="button" id="neo-categoria-filtro-limpiar" class="text-indigo-600 dark:text-indigo-400 hover:underline text-xs font-medium">Quitar acotación por categoría</button>
                    </div>
                </div>
                <div class="flex flex-col gap-3">
                    <label class="inline-flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="mismo_producto" id="mismo_producto" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <span class="text-gray-700 dark:text-gray-300">Mismo producto (todas las URLs serán del mismo producto; busca y elige producto y especificaciones debajo)</span>
                    </label>
                    <p id="mismo-producto-modo-todas-msg" class="hidden ml-6 text-xs text-indigo-700 dark:text-indigo-300">
                        A cada URL se le aplicará su producto correspondiente.
                    </p>
                    <div id="mismo-producto-block" class="ml-6 space-y-3 hidden border border-gray-200 dark:border-gray-600 rounded-lg p-4 bg-gray-50 dark:bg-gray-700/50">
                        <div class="producto-search-container relative">
                            <label class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300">Buscar producto</label>
                            <input type="text" id="mismo-producto-search" class="producto-search-input w-full px-3 py-2 rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 text-sm" placeholder="Escribe para buscar productos (mín. 2 caracteres)..." autocomplete="off">
                            <div id="mismo-producto-sugerencias" class="producto-sugerencias-crear-masivo absolute z-50 left-0 right-0 mt-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md shadow-lg hidden max-h-60 overflow-y-auto"></div>
                        </div>
                        <div id="mismo-producto-elegido" class="hidden text-sm text-gray-600 dark:text-gray-400">
                            <span class="font-medium">Producto:</span> <span id="mismo-producto-nombre"></span>
                            <button type="button" id="mismo-producto-quitar" class="ml-2 inline-flex items-center justify-center w-6 h-6 rounded bg-red-500 hover:bg-red-600 text-white text-xs font-bold transition" title="Quitar producto">&times;</button>
                        </div>
                        <div id="mismo-producto-spec-container" class="mismo-producto-spec-wrapper" data-columnas-ids="[]" data-es-unidad-unica="0"></div>
                    </div>
                    <label class="inline-flex items-center gap-2 cursor-pointer" id="no_productos_sugeridos-wrap">
                        <input type="checkbox" name="no_productos_sugeridos" id="no_productos_sugeridos" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <span class="text-gray-700 dark:text-gray-300">No mostrar productos sugeridos para cada URL</span>
                    </label>
                    <label class="inline-flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="incluir_contenido_pagina" id="incluir_contenido_pagina" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <span class="text-gray-700 dark:text-gray-300">Incluir contenido de la página (título, h1, meta) y mostrarlo en resultados</span>
                    </label>
                    <label class="inline-flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="usar_chatgpt" id="usar_chatgpt" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <span class="text-gray-700 dark:text-gray-300">Usar ChatGPT para detectar producto y especificaciones</span>
                    </label>
                    <div id="chatgpt_opciones" class="ml-6 space-y-2 hidden">
                        <div class="flex items-center gap-2">
                            <label for="chatgpt_model" class="text-gray-600 dark:text-gray-400 text-sm">Modelo:</label>
                            <select name="chatgpt_model" id="chatgpt_model" class="rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm px-3 py-1.5 focus:ring-2 focus:ring-blue-500">
                                <option value="gpt-4o-nano">gpt-4o-nano &mdash; más barato, más rápido, peor precisión</option>
                                <option value="gpt-4o-mini">gpt-4o-mini &mdash; barato, rápido, buena precisión</option>
                                <option value="gpt-4o" selected>gpt-4o &mdash; mejor precisión, más caro (por defecto)</option>
                                <option value="gpt-4-turbo">gpt-4-turbo &mdash; mejor calidad, el más caro</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <button type="submit" id="btnAnalizar"
                        class="inline-flex items-center bg-blue-600 hover:bg-blue-700 text-white font-semibold px-6 py-3 rounded-md transition disabled:opacity-50 disabled:cursor-not-allowed">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                        <span id="btnAnalizarTexto">Analizar URLs</span>
                    </button>
                    <label for="cantidad_urls_analizar" class="text-sm text-gray-700 dark:text-gray-300">Cantidad a ejecutar:</label>
                    <input type="number" id="cantidad_urls_analizar" min="1" step="1" value="0"
                        class="w-24 px-3 py-2 rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm">
                    <span id="info_total_urls_textarea" class="text-sm text-gray-600 dark:text-gray-400">Total URLs: 0</span>
                    <span id="info_progreso_lote" class="text-sm text-gray-500 dark:text-gray-400"></span>
                    <button type="button" id="btnRepetirMismoLote"
                        class="hidden inline-flex items-center bg-gray-700 hover:bg-gray-800 text-white font-semibold px-4 py-2 rounded-md transition disabled:opacity-50 disabled:cursor-not-allowed">
                        Volver a ejecutar las mismas
                    </button>
                </div>
            </form>
        </div>

        <div id="resultados" class="hidden" aria-hidden="true">
            <div id="resultadosLista" class="space-y-4"></div>
        </div>

        <div id="crear-masivo-siguiente-wrap" class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6 border border-gray-200 dark:border-gray-700 hidden">
            <button type="button" id="btnSiguienteOrigenNeo" class="inline-flex items-center bg-emerald-600 hover:bg-emerald-700 text-white font-semibold px-6 py-3 rounded-md transition disabled:opacity-50 disabled:cursor-not-allowed">
                Siguiente
            </button>
        </div>
    </div>

    {{-- Modal flujo URL a URL (crear ofertas en masa) --}}
    <div id="modal-url-crear-masivo" class="hidden fixed inset-0 bg-black bg-opacity-50 z-[55] flex items-center justify-center p-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-6xl w-full max-h-[92vh] flex flex-col border border-gray-200 dark:border-gray-700 relative">
            <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex items-start justify-between gap-3 shrink-0">
                <div class="min-w-0">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Crear ofertas &mdash; URL por URL</h3>
                    <p id="modal-url-crear-masivo-progreso" class="text-sm text-gray-500 dark:text-gray-400 mt-0.5"></p>
                </div>
                <button type="button" id="modal-url-crear-masivo-cerrar-x" class="text-2xl leading-none text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 shrink-0" title="Cerrar y detener el proceso">&times;</button>
            </div>
            <div id="modal-url-crear-masivo-prev-guardado" class="hidden shrink-0 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 p-4"></div>
            <div id="modal-url-crear-masivo-contenido" class="flex-1 overflow-y-auto p-4 min-h-0"></div>
            <div id="modal-url-crear-masivo-pie" class="shrink-0 p-4 border-t border-gray-200 dark:border-gray-700 flex flex-col items-end gap-2">
                <button type="button" id="modal-url-crear-masivo-siguiente" class="hidden px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">Siguiente URL</button>
                <button type="button" id="modal-url-crear-masivo-saltar" class="hidden px-4 py-2 text-sm font-medium text-gray-600 border border-gray-300 rounded-md hover:bg-gray-50 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700" title="Pasar a la siguiente URL sin generar oferta ni descartar (la fila sigue en la lista)">Saltar URL</button>
            </div>
        </div>
    </div>

    {{-- Modal final: generación en segundo plano de la última URL (sin X; solo Aceptar al terminar) --}}
    <div id="modal-url-crear-masivo-final" class="hidden fixed inset-0 bg-black bg-opacity-50 z-[56] flex items-center justify-center p-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-lg w-full border border-gray-200 dark:border-gray-700 flex flex-col">
            <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Generando última oferta</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">No cierres esta ventana hasta pulsar Aceptar</p>
            </div>
            <div id="modal-url-crear-masivo-final-contenido" class="p-4"></div>
            <div class="p-4 border-t border-gray-200 dark:border-gray-700 flex justify-end">
                <button type="button" id="modal-url-crear-masivo-final-aceptar" disabled
                    class="px-5 py-2 text-sm font-medium text-white bg-blue-600 rounded-md opacity-50 cursor-not-allowed">
                    Aceptar
                </button>
            </div>
        </div>
    </div>

    {{-- Modal para ver imágenes de especificaciones internas --}}
    <div id="modal-imagenes-spec-crear-masivo" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white dark:bg-gray-900 rounded-lg p-6 max-w-5xl w-full relative shadow-xl max-h-[90vh] flex flex-col">
            <button type="button" onclick="cerrarModalImagenesSpecCrearMasivo()" class="absolute top-3 right-4 text-xl text-gray-800 dark:text-gray-100 hover:text-gray-600 dark:hover:text-gray-300 z-10">&times;</button>
            <div class="mb-4">
                <h3 id="modal-imagenes-crear-masivo-titulo" class="text-lg font-semibold text-gray-700 dark:text-gray-200">Imágenes de la especificación</h3>
                <p id="modal-imagenes-crear-masivo-subtitulo" class="text-sm text-gray-500 dark:text-gray-400">Haz clic en una miniatura para verla en grande</p>
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

    {{-- Modal Ver prompt / respuesta ChatGPT --}}
    <div id="modal-ver-prompt-crear-masivo" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white dark:bg-gray-900 rounded-lg shadow-xl max-w-4xl w-full max-h-[90vh] flex flex-col">
            <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Petición y respuesta ChatGPT</h3>
                <button type="button" onclick="cerrarModalVerPromptCrearMasivo()" class="text-2xl leading-none text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">&times;</button>
            </div>
            <div class="flex-1 overflow-y-auto p-4 space-y-6">
                <section>
                    <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Petición (prompt)</h4>
                    <div id="modal-ver-prompt-contenido-peticion" class="text-sm text-gray-800 dark:text-gray-200 space-y-3"></div>
                </section>
                <section>
                    <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Respuesta</h4>
                    <div id="modal-ver-prompt-contenido-respuesta" class="text-sm text-gray-800 dark:text-gray-200 space-y-2"></div>
                </section>
            </div>
            <div class="p-4 border-t border-gray-200 dark:border-gray-700">
                <button type="button" onclick="cerrarModalVerPromptCrearMasivo()"
                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">
                    Cerrar
                </button>
            </div>
        </div>
    </div>

    {{-- Modal elegir producto (neo aniadida=no) --}}
    <div id="modal-producto-neo" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-2xl w-full max-h-[80vh] flex flex-col border border-gray-200 dark:border-gray-700">
            <div class="p-4 border-b border-gray-200 dark:border-gray-700 space-y-3">
                <div class="flex items-start gap-3">
                    <div class="flex-1 min-w-0 flex flex-wrap items-center gap-x-4 gap-y-2 text-sm text-gray-700 dark:text-gray-300">
                        <span>Total URLs disponibles: <strong id="modal-producto-neo-total-urls" class="text-gray-900 dark:text-white">&mdash;</strong></span>
                        <span class="hidden sm:inline text-gray-400 dark:text-gray-500">|</span>
                        <span class="inline-flex flex-wrap items-center gap-3">
                            <span class="font-medium text-gray-800 dark:text-gray-200">Tiendas con mostrar</span>
                            <label class="inline-flex items-center gap-1.5 cursor-pointer">
                                <input type="checkbox" id="modal-producto-neo-chk-mostrar-si" checked class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <span>Sí</span>
                            </label>
                            <label class="inline-flex items-center gap-1.5 cursor-pointer">
                                <input type="checkbox" id="modal-producto-neo-chk-mostrar-no" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <span>No</span>
                            </label>
                            <label class="inline-flex items-center gap-1.5 cursor-pointer" title="Solo filas neo sin tienda (desmarca Sí y No). Con Sí o No marcados, también suma enlaces sin tienda.">
                                <input type="checkbox" id="modal-producto-neo-chk-tienda-null" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <span>null (<span id="modal-producto-neo-null-count">0</span>)</span>
                            </label>
                            <button type="button" id="modal-producto-neo-btn-buscar-tienda" class="px-2 py-0.5 text-xs font-medium rounded border border-blue-600 text-blue-700 bg-blue-50 hover:bg-blue-100 dark:border-blue-500 dark:text-blue-300 dark:bg-blue-900/30 dark:hover:bg-blue-900/50 disabled:opacity-50" title="Rellenar tienda por host de la URL y categoría desde el producto si falta">Rellenar</button>
                        </span>
                        <span class="hidden sm:inline text-gray-400 dark:text-gray-500">|</span>
                        <span class="inline-flex flex-wrap items-center gap-3">
                            <span class="font-medium text-gray-800 dark:text-gray-200">Categorías con mostrar</span>
                            <label class="inline-flex items-center gap-1.5 cursor-pointer">
                                <input type="checkbox" id="modal-producto-neo-chk-cat-mostrar-si" checked class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <span>Sí</span>
                            </label>
                            <label class="inline-flex items-center gap-1.5 cursor-pointer">
                                <input type="checkbox" id="modal-producto-neo-chk-cat-mostrar-no" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <span>No</span>
                            </label>
                            <label class="inline-flex items-center gap-1.5 cursor-pointer" title="Solo filas neo sin categoría (desmarca Sí y No). Con Sí o No marcados, también suma enlaces sin categoría.">
                                <input type="checkbox" id="modal-producto-neo-chk-categoria-null" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <span>null (<span id="modal-producto-neo-categoria-null-count">0</span>)</span>
                            </label>
                        </span>
                    </div>
                    <button type="button" id="modal-producto-neo-cerrar" onclick="var m=document.getElementById('modal-producto-neo'); if(m){m.classList.add('hidden');}" class="text-2xl leading-none text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 shrink-0 -mt-0.5">&times;</button>
                </div>
                <div id="modal-producto-neo-panel-tiendas-no" class="hidden w-full text-sm text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-600 rounded-lg overflow-hidden">
                    <label class="flex items-center gap-2 cursor-pointer font-medium text-gray-900 dark:text-gray-100 px-3 py-2.5 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-600">
                        <input type="checkbox" id="modal-producto-neo-chk-tiendas-no-todas" checked class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 shrink-0 modal-producto-neo-tiendas-no-master">
                        <span>Todas las tiendas</span>
                    </label>
                    <details id="modal-producto-neo-details-tiendas-no-lista" class="group">
                        <summary class="cursor-pointer select-none px-3 py-2 font-medium text-gray-800 dark:text-gray-200 bg-gray-50 dark:bg-gray-700/50 list-none [&::-webkit-details-marker]:hidden flex items-center gap-2" title="Desplegar u ocultar el listado de tiendas">
                            <svg class="w-4 h-4 shrink-0 text-gray-500 dark:text-gray-400 transition-transform group-open:rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                            <span>Elegir tiendas (mostrar «no»)</span>
                            <span class="text-xs font-normal text-gray-500 dark:text-gray-400 ml-auto">desplegar</span>
                        </summary>
                        <div id="modal-producto-neo-tiendas-no-lista" class="p-3 max-h-48 overflow-y-auto space-y-1.5 border-t border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800"></div>
                    </details>
                </div>
                <div class="flex items-center gap-2 min-w-0">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex-1 min-w-0">Elegir producto (neo con añadida=no)</h3>
                    <button type="button" id="modal-producto-neo-btn-toggle-buscador" class="modal-neo-btn-toggle-buscador shrink-0 p-1.5 rounded-lg text-gray-500 hover:text-gray-700 hover:bg-gray-100 dark:text-gray-400 dark:hover:text-gray-200 dark:hover:bg-gray-700 transition" title="Buscar en la lista" aria-label="Buscar en la lista" aria-expanded="false" aria-controls="modal-producto-neo-buscador-wrap">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </button>
                </div>
                <div id="modal-producto-neo-buscador-wrap" class="hidden">
                    <input type="search" id="modal-producto-neo-buscador" class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500" placeholder="Buscar en la lista…" autocomplete="off">
                </div>
            </div>
            <div id="modal-producto-neo-lista" class="flex-1 overflow-y-auto p-4 space-y-2">
                <p class="text-gray-500 dark:text-gray-400 text-sm">Cargando...</p>
            </div>
            <div class="p-4 border-t border-gray-200 dark:border-gray-700">
                <button type="button" id="modal-producto-neo-cerrar-btn" onclick="var m=document.getElementById('modal-producto-neo'); if(m){m.classList.add('hidden');}" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">Cerrar</button>
            </div>
        </div>
    </div>

    {{-- Modal elegir categoría (neo aniadida=no) --}}
    <div id="modal-categoria-neo" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-2xl w-full max-h-[80vh] flex flex-col border border-gray-200 dark:border-gray-700">
            <div class="p-4 border-b border-gray-200 dark:border-gray-700 space-y-3">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="flex flex-wrap items-center gap-x-4 gap-y-2 text-sm text-gray-700 dark:text-gray-300">
                        <span>Total URLs disponibles: <strong id="modal-categoria-neo-total-urls" class="text-gray-900 dark:text-white">&mdash;</strong></span>
                        <span class="hidden sm:inline text-gray-400 dark:text-gray-500">|</span>
                        <span id="modal-categoria-neo-filtros-categoria" class="inline-flex flex-wrap items-center gap-3">
                            <span class="font-medium text-gray-800 dark:text-gray-200">Categorías con mostrar</span>
                            <label class="inline-flex items-center gap-1.5 cursor-pointer">
                                <input type="checkbox" id="modal-categoria-neo-chk-cat-mostrar-si" checked class="rounded border-gray-300 text-pink-600 focus:ring-pink-500">
                                <span>Sí</span>
                            </label>
                            <label class="inline-flex items-center gap-1.5 cursor-pointer">
                                <input type="checkbox" id="modal-categoria-neo-chk-cat-mostrar-no" class="rounded border-gray-300 text-pink-600 focus:ring-pink-500">
                                <span>No</span>
                            </label>
                        </span>
                        <span id="modal-categoria-neo-filtros-tienda" class="hidden inline-flex flex-wrap items-center gap-3">
                            <span class="font-medium text-gray-800 dark:text-gray-200">Tiendas con mostrar</span>
                            <label class="inline-flex items-center gap-1.5 cursor-pointer">
                                <input type="checkbox" id="modal-categoria-neo-chk-mostrar-si" checked class="rounded border-gray-300 text-pink-600 focus:ring-pink-500">
                                <span>Sí</span>
                            </label>
                            <label class="inline-flex items-center gap-1.5 cursor-pointer">
                                <input type="checkbox" id="modal-categoria-neo-chk-mostrar-no" class="rounded border-gray-300 text-pink-600 focus:ring-pink-500">
                                <span>No</span>
                            </label>
                        </span>
                    </div>
                    <button type="button" id="modal-categoria-neo-cerrar" class="text-2xl leading-none text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 shrink-0">&times;</button>
                </div>
                <div id="modal-categoria-neo-panel-tiendas-no" class="hidden w-full text-sm text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-600 rounded-lg overflow-hidden">
                    <label class="flex items-center gap-2 cursor-pointer font-medium text-gray-900 dark:text-gray-100 px-3 py-2.5 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-600">
                        <input type="checkbox" id="modal-categoria-neo-chk-tiendas-no-todas" checked class="rounded border-gray-300 text-pink-600 focus:ring-pink-500 shrink-0 modal-categoria-neo-tiendas-no-master">
                        <span>Todas las tiendas</span>
                    </label>
                    <details id="modal-categoria-neo-details-tiendas-no-lista" class="group">
                        <summary class="cursor-pointer select-none px-3 py-2 font-medium text-gray-800 dark:text-gray-200 bg-gray-50 dark:bg-gray-700/50 list-none [&::-webkit-details-marker]:hidden flex items-center gap-2" title="Desplegar u ocultar el listado de tiendas">
                            <svg class="w-4 h-4 shrink-0 text-gray-500 dark:text-gray-400 transition-transform group-open:rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                            <span>Elegir tiendas (mostrar «no»)</span>
                            <span class="text-xs font-normal text-gray-500 dark:text-gray-400 ml-auto">desplegar</span>
                        </summary>
                        <div id="modal-categoria-neo-tiendas-no-lista" class="p-3 max-h-48 overflow-y-auto space-y-1.5 border-t border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800"></div>
                    </details>
                </div>
                <div class="flex items-center gap-2 min-w-0">
                    <h3 id="modal-categoria-neo-titulo" class="text-lg font-semibold text-gray-900 dark:text-white flex-1 min-w-0">Elegir categoría (neo con añadida=no)</h3>
                    <button type="button" id="modal-categoria-neo-btn-toggle-buscador" class="modal-neo-btn-toggle-buscador shrink-0 p-1.5 rounded-lg text-gray-500 hover:text-gray-700 hover:bg-gray-100 dark:text-gray-400 dark:hover:text-gray-200 dark:hover:bg-gray-700 transition" title="Buscar en la lista" aria-label="Buscar en la lista" aria-expanded="false" aria-controls="modal-categoria-neo-buscador-wrap">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </button>
                </div>
                <div id="modal-categoria-neo-buscador-wrap" class="hidden">
                    <input type="search" id="modal-categoria-neo-buscador" class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-pink-500" placeholder="Buscar en la lista…" autocomplete="off">
                </div>
            </div>
            <div id="modal-categoria-neo-lista" class="flex-1 overflow-y-auto p-4 space-y-2">
                <p class="text-gray-500 dark:text-gray-400 text-sm">Cargando...</p>
            </div>
            <div class="p-4 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <button type="button" id="modal-categoria-neo-atras" class="hidden px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">Atrás</button>
                <button type="button" id="modal-categoria-neo-cerrar-btn" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">Cerrar</button>
            </div>
        </div>
    </div>

    {{-- Modal elegir tienda (neo aniadida=no) --}}
    <div id="modal-tienda-neo" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-2xl w-full max-h-[80vh] flex flex-col border border-gray-200 dark:border-gray-700">
            <div class="p-4 border-b border-gray-200 dark:border-gray-700 space-y-3">
                <div class="flex items-start gap-3">
                    <div class="flex-1 min-w-0 flex flex-wrap items-center gap-x-4 gap-y-2 text-sm text-gray-700 dark:text-gray-300">
                        <span>Total URLs disponibles: <strong id="modal-tienda-neo-total-urls" class="text-gray-900 dark:text-white">&mdash;</strong></span>
                        <span class="hidden sm:inline text-gray-400 dark:text-gray-500">|</span>
                        <span class="inline-flex flex-wrap items-center gap-3">
                            <span class="font-medium text-gray-800 dark:text-gray-200">Tiendas con mostrar</span>
                            <label class="inline-flex items-center gap-1.5 cursor-pointer">
                                <input type="checkbox" id="modal-tienda-neo-chk-mostrar-si" checked class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
                                <span>Sí</span>
                            </label>
                            <label class="inline-flex items-center gap-1.5 cursor-pointer">
                                <input type="checkbox" id="modal-tienda-neo-chk-mostrar-no" class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
                                <span>No</span>
                            </label>
                            <label class="inline-flex items-center gap-1.5 cursor-pointer" title="Solo filas neo sin tienda (desmarca Sí y No). Con Sí o No marcados, también suma enlaces sin tienda.">
                                <input type="checkbox" id="modal-tienda-neo-chk-tienda-null" class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
                                <span>null (<span id="modal-tienda-neo-null-count">0</span>)</span>
                            </label>
                        </span>
                        <span class="hidden sm:inline text-gray-400 dark:text-gray-500">|</span>
                        <span class="inline-flex flex-wrap items-center gap-3">
                            <span class="font-medium text-gray-800 dark:text-gray-200">Categorías con mostrar</span>
                            <label class="inline-flex items-center gap-1.5 cursor-pointer">
                                <input type="checkbox" id="modal-tienda-neo-chk-cat-mostrar-si" checked class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
                                <span>Sí</span>
                            </label>
                            <label class="inline-flex items-center gap-1.5 cursor-pointer">
                                <input type="checkbox" id="modal-tienda-neo-chk-cat-mostrar-no" class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
                                <span>No</span>
                            </label>
                            <label class="inline-flex items-center gap-1.5 cursor-pointer" title="Solo filas neo sin categoría (desmarca Sí y No). Con Sí o No marcados, también suma enlaces sin categoría.">
                                <input type="checkbox" id="modal-tienda-neo-chk-categoria-null" class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
                                <span>null (<span id="modal-tienda-neo-categoria-null-count">0</span>)</span>
                            </label>
                        </span>
                    </div>
                    <button type="button" id="modal-tienda-neo-cerrar" class="text-2xl leading-none text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 shrink-0 -mt-0.5">&times;</button>
                </div>
                <div id="modal-tienda-neo-panel-tiendas-no" class="hidden w-full text-sm text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-600 rounded-lg overflow-hidden">
                    <label class="flex items-center gap-2 cursor-pointer font-medium text-gray-900 dark:text-gray-100 px-3 py-2.5 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-600">
                        <input type="checkbox" id="modal-tienda-neo-chk-tiendas-no-todas" checked class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500 shrink-0 modal-tienda-neo-tiendas-no-master">
                        <span>Todas las tiendas</span>
                    </label>
                    <details id="modal-tienda-neo-details-tiendas-no-lista" class="group">
                        <summary class="cursor-pointer select-none px-3 py-2 font-medium text-gray-800 dark:text-gray-200 bg-gray-50 dark:bg-gray-700/50 list-none [&::-webkit-details-marker]:hidden flex items-center gap-2" title="Desplegar u ocultar el listado de tiendas">
                            <svg class="w-4 h-4 shrink-0 text-gray-500 dark:text-gray-400 transition-transform group-open:rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                            <span>Elegir tiendas (mostrar «no»)</span>
                            <span class="text-xs font-normal text-gray-500 dark:text-gray-400 ml-auto">desplegar</span>
                        </summary>
                        <div id="modal-tienda-neo-tiendas-no-lista" class="p-3 max-h-48 overflow-y-auto space-y-1.5 border-t border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800"></div>
                    </details>
                </div>
                <div class="flex justify-between items-center gap-3">
                    <div class="flex items-center gap-2 min-w-0 flex-1">
                        <h3 id="modal-tienda-neo-titulo" class="text-lg font-semibold text-gray-900 dark:text-white flex-1 min-w-0">Elegir tienda (neo con añadida=no)</h3>
                        <button type="button" id="modal-tienda-neo-btn-toggle-buscador" class="modal-neo-btn-toggle-buscador shrink-0 p-1.5 rounded-lg text-gray-500 hover:text-gray-700 hover:bg-gray-100 dark:text-gray-400 dark:hover:text-gray-200 dark:hover:bg-gray-700 transition" title="Buscar en la lista" aria-label="Buscar en la lista" aria-expanded="false" aria-controls="modal-tienda-neo-buscador-wrap">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        </button>
                    </div>
                </div>
                <div id="modal-tienda-neo-buscador-wrap" class="hidden">
                    <input type="search" id="modal-tienda-neo-buscador" class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500" placeholder="Buscar en la lista…" autocomplete="off">
                </div>
            </div>
            <div id="modal-tienda-neo-lista" class="flex-1 overflow-y-auto p-4 space-y-2">
                <p class="text-gray-500 dark:text-gray-400 text-sm">Cargando...</p>
            </div>
            <div class="p-4 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <button type="button" id="modal-tienda-neo-atras" class="hidden px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">Atrás</button>
                <button type="button" id="modal-tienda-neo-cerrar-btn" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">Cerrar</button>
            </div>
        </div>
    </div>

    {{-- Modal elegir categoría para acotar búsqueda (mismo partial que admin/categorías index) --}}
    <div id="modal-categoria-fila-crear-masivo" class="hidden fixed inset-0 bg-black bg-opacity-50 z-[60] flex items-center justify-center p-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-3xl w-full max-h-[85vh] flex flex-col border border-gray-200 dark:border-gray-700"
            x-data="{
                openCategorias: [],
                toggle(id) {
                    if (this.openCategorias.includes(id)) {
                        this.openCategorias = this.openCategorias.filter(i => i !== id);
                    } else {
                        this.openCategorias.push(id);
                    }
                },
                isOpen(id) {
                    return this.openCategorias.includes(id);
                }
            }">
            <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center flex-shrink-0">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Elegir categoría</h3>
                <button type="button" id="modal-categoria-fila-cerrar" class="text-2xl leading-none text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">&times;</button>
            </div>
            <div class="flex-1 overflow-y-auto p-4 min-h-0">
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">Abre ramas pulsando la fila. En categorías finales, pulsa el <strong class="text-gray-700 dark:text-gray-200">nombre</strong> o <strong class="text-gray-700 dark:text-gray-200">Seleccionar</strong>. En el recorrido URL a URL, al elegir una categoría se pasa a la siguiente.</p>
                <div id="modal-categoria-fila-arbol" class="space-y-1">
                    @foreach ($categoriasRaiz ?? [] as $categoria)
                        @include('admin.categorias.partial-categoria', ['categoria' => $categoria, 'nivel' => 0, 'esPickerCrearMasivo' => true])
                    @endforeach
                </div>
            </div>
            <div class="p-4 border-t border-gray-200 dark:border-gray-700 flex justify-end flex-shrink-0">
                <button type="button" id="modal-categoria-fila-cerrar-btn" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">Cerrar</button>
            </div>
        </div>
    </div>

    {{-- Modal confirmar descartar URL --}}
    <div id="modal-descartar-url-crear-masivo" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-md w-full border border-gray-200 dark:border-gray-700">
            <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Confirmar descarte</h3>
                <button type="button" id="modal-descartar-url-cerrar" class="text-2xl leading-none text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">&times;</button>
            </div>
            <div class="p-4">
                <p class="text-sm text-gray-700 dark:text-gray-300">¿Seguro que quieres descartar esta URL?</p>
                <p id="modal-descartar-url-texto" class="mt-2 text-xs break-all text-gray-500 dark:text-gray-400"></p>
            </div>
            <div class="p-4 border-t border-gray-200 dark:border-gray-700 flex justify-end gap-2">
                <button type="button" id="modal-descartar-url-cancelar" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">Cancelar</button>
                <button type="button" id="modal-descartar-url-confirmar" class="inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded transition">Aceptar y descartar</button>
            </div>
        </div>
    </div>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/cropperjs@1.5.13/dist/cropper.min.css">
    <script src="https://cdn.jsdelivr.net/npm/cropperjs@1.5.13/dist/cropper.min.js"></script>

    {{-- Modales imágenes nueva opción (crear-masivo), mismas capacidades que formulario producto --}}
    <div id="modal-imagenes-sublinea-cm" class="fixed inset-0 bg-black bg-opacity-50 z-[60] hidden flex items-center justify-center p-4">
        <div class="bg-white dark:bg-gray-900 rounded-lg p-6 max-w-5xl w-full relative shadow-xl max-h-[90vh] flex flex-col">
            <button type="button" onclick="cerrarModalImagenesSublineaCm()" class="absolute top-3 right-4 text-xl text-gray-800 dark:text-gray-100 hover:text-gray-600 z-10">&times;</button>
            <div class="mb-4">
                <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-200">Imágenes de la nueva opción</h3>
            </div>
            <div class="flex-1 overflow-y-auto">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="md:col-span-2">
                        <div class="bg-gray-100 dark:bg-gray-800 rounded-lg p-4 flex items-center justify-center" style="min-height: 320px;">
                            <img id="imagen-grande-sublinea-cm" src="" alt="" class="max-w-full max-h-80 object-contain rounded">
                        </div>
                    </div>
                    <div class="md:col-span-1">
                        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-3">Miniaturas (arrastra para reordenar)</h4>
                        <div id="miniaturas-container-sublinea-cm" class="space-y-2 max-h-80 overflow-y-auto"></div>
                    </div>
                </div>
            </div>
            <div class="flex justify-end gap-3 mt-4">
                <button type="button" onclick="cerrarModalImagenesSublineaCm()" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-300">Cerrar</button>
            </div>
        </div>
    </div>

    <style>
        .kp-modal-img-tabs__nav {
            display: flex;
            flex-wrap: wrap;
            gap: 0.25rem;
            padding: 0.35rem;
            background: #f3f4f6;
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
        }
        .dark .kp-modal-img-tabs__nav {
            background: #1f2937;
            border-color: #4b5563;
        }
        .kp-modal-img-tab {
            flex: 1 1 auto;
            min-width: max(6.5rem, fit-content);
            padding: 0.5rem 0.75rem;
            font-size: 0.8125rem;
            font-weight: 500;
            line-height: 1.25;
            text-align: center;
            color: #4b5563;
            background: transparent;
            border: 1px solid transparent;
            border-radius: 0.375rem;
            white-space: nowrap;
            cursor: pointer;
            transition: background-color 0.15s, color 0.15s, border-color 0.15s, box-shadow 0.15s;
        }
        .dark .kp-modal-img-tab {
            color: #9ca3af;
        }
        .kp-modal-img-tab:hover:not(.kp-modal-img-tab--active) {
            background: #e5e7eb;
            color: #374151;
        }
        .dark .kp-modal-img-tab:hover:not(.kp-modal-img-tab--active) {
            background: #374151;
            color: #e5e7eb;
        }
        .kp-modal-img-tab--active {
            background: #fff;
            color: #1d4ed8;
            border-color: #93c5fd;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            font-weight: 600;
        }
        .dark .kp-modal-img-tab--active {
            background: #111827;
            color: #93c5fd;
            border-color: #3b82f6;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.35);
        }
        .kp-modal-img-tab:focus-visible {
            outline: 2px solid #3b82f6;
            outline-offset: 2px;
        }
        .kp-ig-cell {
            position: relative;
        }
        .kp-ig-btn-ampliar {
            line-height: 0;
            z-index: 30;
            background-color: #2563eb;
            color: #fff;
            border: none;
            cursor: pointer;
        }
        .kp-ig-btn-ampliar:hover {
            background-color: #1d4ed8;
        }
        .kp-ig-btn-ampliar svg {
            width: 0.8rem;
            height: 0.8rem;
            display: block;
        }
        #kp-ig-preview-overlay {
            z-index: 99999;
        }
        #kp-ig-preview-overlay img {
            max-width: min(96vw, 1200px);
            max-height: 90vh;
            width: auto;
            height: auto;
            object-fit: contain;
        }
    </style>

    <div id="modal-añadir-imagen-sublinea-cm" class="fixed inset-0 bg-black bg-opacity-50 z-[60] hidden flex items-center justify-center p-4">
        <div class="bg-white dark:bg-gray-900 rounded-lg p-6 max-w-4xl w-full relative shadow-xl overflow-y-auto max-h-[90vh]">
            <button type="button" onclick="cerrarModalAñadirImagenSublineaCm()" class="absolute top-3 right-4 text-xl text-gray-800 dark:text-gray-100">&times;</button>
            <div class="mb-4">
                <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-200">Añadir imagen</h3>
            </div>
            <div class="kp-modal-img-tabs mb-4" role="tablist" aria-label="Origen de la imagen">
                <nav class="kp-modal-img-tabs__nav">
                    <button type="button" id="tab-url-sublinea-cm" class="tab-modal-sublinea-cm kp-modal-img-tab kp-modal-img-tab--active" role="tab" aria-selected="true">Descargar desde URL</button>
                    <button type="button" id="tab-subir-sublinea-cm" class="tab-modal-sublinea-cm kp-modal-img-tab" role="tab" aria-selected="false">Subir imagen</button>
                    <button type="button" id="tab-amazon-sublinea-cm" class="tab-modal-sublinea-cm kp-modal-img-tab" role="tab" aria-selected="false">Amazon</button>
                    <button type="button" id="tab-interna-sublinea-cm" class="tab-modal-sublinea-cm kp-modal-img-tab" role="tab" aria-selected="false">Interna producto</button>
                    <button type="button" id="tab-interna-global-sublinea-cm" class="tab-modal-sublinea-cm kp-modal-img-tab" role="tab" aria-selected="false">Interna Global</button>
                </nav>
            </div>
            <div id="content-url-sublinea-cm" class="tab-content-modal-sublinea-cm space-y-4">
                <div>
                    <label class="block mb-2 text-sm text-gray-600 dark:text-gray-400">Carpeta</label>
                    <select id="carpeta-url-sublinea-cm" class="w-full px-3 py-2 rounded bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white border text-sm">
                        <option value="">Selecciona una carpeta</option>
                    </select>
                </div>
                <div>
                    <label class="block mb-2 text-sm text-gray-600 dark:text-gray-400">URL de la imagen</label>
                    <div class="flex gap-2 flex-wrap">
                        <input type="url" id="url-imagen-sublinea-cm" class="flex-1 min-w-[200px] px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white border text-sm" placeholder="https://…">
                        <button type="button" id="btn-descargar-url-sublinea-cm" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm">Descargar</button>
                    </div>
                    <div id="error-url-sublinea-cm" class="mt-1 text-sm text-red-500 hidden"></div>
                </div>
                <div id="area-recorte-sublinea-cm" class="hidden space-y-4">
                    <div id="contenedor-cropper-sublinea-cm" style="max-width: 650px; max-height: 450px; margin: 0 auto; overflow: hidden;">
                        <img id="imagen-recortar-sublinea-cm" src="" alt="" style="display: block; max-width: 100%; height: auto;">
                    </div>
                </div>
            </div>
            <div id="content-subir-sublinea-cm" class="tab-content-modal-sublinea-cm space-y-4 hidden">
                <div>
                    <label class="block mb-2 text-sm text-gray-600 dark:text-gray-400">Carpeta</label>
                    <select id="carpeta-subir-sublinea-cm" class="w-full px-3 py-2 rounded bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white border text-sm">
                        <option value="">Selecciona una carpeta</option>
                    </select>
                </div>
                <div>
                    <input type="file" id="file-subir-sublinea-cm" accept="image/*" class="hidden">
                    <button type="button" id="btn-seleccionar-sublinea-cm" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm mb-2">Seleccionar archivo</button>
                    <div id="drop-zone-sublinea-cm" class="mt-2 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-4 text-center cursor-pointer text-sm text-gray-500 dark:text-gray-400">Arrastra aquí o haz clic</div>
                    <span id="nombre-archivo-sublinea-cm" class="text-sm text-gray-500"></span>
                </div>
            </div>
            <div id="content-amazon-sublinea-cm" class="tab-content-modal-sublinea-cm space-y-4 hidden">
                <div id="amazon-busqueda-sublinea-cm">
                    <label class="block mb-2 text-sm text-gray-600 dark:text-gray-400">URL Amazon</label>
                    <div class="flex gap-2 flex-wrap">
                        <input type="url" id="url-amazon-sublinea-cm" class="flex-1 min-w-[200px] px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white border text-sm" placeholder="https://www.amazon.es/dp/…">
                        <button type="button" id="btn-buscar-amazon-sublinea-cm" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm">Buscar</button>
                    </div>
                    <div id="error-amazon-sublinea-cm" class="mt-1 text-sm text-red-500 hidden"></div>
                    <div id="loading-amazon-sublinea-cm" class="mt-2 text-sm text-gray-500 hidden">Buscando…</div>
                </div>
                <div id="imagenes-amazon-sublinea-cm" class="hidden">
                    <div id="grid-imagenes-amazon-sublinea-cm" class="grid grid-cols-2 md:grid-cols-3 gap-3 max-h-72 overflow-y-auto p-2"></div>
                    <label class="block mt-3 mb-1 text-sm text-gray-600 dark:text-gray-400">Carpeta</label>
                    <select id="carpeta-amazon-sublinea-cm" class="w-full px-3 py-2 rounded bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white border text-sm">
                        <option value="">Selecciona una carpeta</option>
                    </select>
                </div>

                <!-- Área de recorte Amazon -->
                <div id="area-recorte-amazon-sublinea-cm" class="hidden space-y-4">
                    <p id="progreso-recorte-amazon-sublinea-cm" class="text-sm font-semibold text-gray-700 dark:text-gray-200"></p>
                    <div>
                        <h4 class="text-md font-semibold text-gray-700 dark:text-gray-200 mb-2">Recortar imagen</h4>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">Selecciona el área que deseas mantener.</p>
                        <div id="contenedor-cropper-amazon-sublinea-cm" style="max-width: 650px; max-height: 450px; margin: 0 auto; overflow: hidden;">
                            <img id="imagen-recortar-amazon-sublinea-cm" src="" alt="Imagen a recortar" style="display: block; max-width: 100%; height: auto;">
                        </div>
                    </div>
                </div>
            </div>
            <div id="content-interna-sublinea-cm" class="tab-content-modal-sublinea-cm space-y-4 hidden">
                <p class="text-sm text-gray-600 dark:text-gray-400">Rutas de imágenes ya subidas (relativas al almacén de imágenes). Solo se guarda la ruta grande en el borrador; la pequeña sirve para comprobar la miniatura.</p>
                <div class="flex flex-wrap items-start gap-3">
                    <div class="flex-1 min-w-[220px]">
                        <label class="block mb-1 text-sm text-gray-600 dark:text-gray-400" for="ruta-interna-grande-sublinea-cm">Ruta imagen grande</label>
                        <input type="text" id="ruta-interna-grande-sublinea-cm" autocomplete="off" class="w-full px-3 py-2 rounded bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white border text-sm" placeholder="carpeta/archivo.webp">
                    </div>
                    <div class="flex-shrink-0">
                        <span class="block mb-1 text-xs text-gray-500 dark:text-gray-400">Vista previa</span>
                        <img id="preview-interna-grande-sublinea-cm" src="" alt="" class="hidden w-16 h-16 object-cover rounded border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-800">
                    </div>
                </div>
                <div class="flex flex-wrap items-start gap-3">
                    <div class="flex-1 min-w-[220px]">
                        <label class="block mb-1 text-sm text-gray-600 dark:text-gray-400" for="ruta-interna-pequena-sublinea-cm">Ruta imagen pequeña</label>
                        <input type="text" id="ruta-interna-pequena-sublinea-cm" autocomplete="off" class="w-full px-3 py-2 rounded bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white border text-sm" placeholder="carpeta/archivo-thumbnail.webp">
                    </div>
                    <div class="flex-shrink-0">
                        <span class="block mb-1 text-xs text-gray-500 dark:text-gray-400">Vista previa</span>
                        <img id="preview-interna-pequena-sublinea-cm" src="" alt="" class="hidden w-16 h-16 object-cover rounded border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-800">
                    </div>
                </div>
            </div>
            <div id="content-interna-global-cm" class="tab-content-modal-sublinea-cm space-y-4 hidden" data-kp-ig-prefix="cm">
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Imágenes recientes de todo el almacén. Pulsa una palabra del nombre del producto para añadirla al buscador. Haz clic en una miniatura para marcarla o desmarcarla. Usa el icono de ampliar (esquina superior derecha) para ver la imagen en tamaño original. Pulsa <strong class="font-medium">Guardar</strong> al terminar.
                </p>
                <div class="kp-ig-panel border border-gray-200 dark:border-gray-600 rounded-lg p-3 bg-gray-50/80 dark:bg-gray-800/40 space-y-3" data-kp-ig-panel="cm">
                    <div class="kp-ig-seleccion border border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-3 bg-white/70 dark:bg-gray-900/50">
                        <p class="text-xs font-medium text-gray-700 dark:text-gray-300 mb-2">Seleccionada para guardar</p>
                        <div class="kp-ig-seleccion-resumen text-xs text-gray-500 dark:text-gray-400">Ninguna imagen seleccionada.</div>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Palabras del nombre del producto</p>
                        <div class="kp-ig-palabras flex flex-wrap gap-1.5 min-h-[1.25rem]"></div>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Buscar por nombre de archivo</label>
                        <input type="text" class="kp-ig-buscador w-full px-3 py-1.5 text-sm rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-200 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Ej: dodot talla 4" autocomplete="off" />
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-700 dark:text-gray-300 mb-2">Últimas imágenes añadidas</p>
                        <p class="kp-ig-cargando text-xs text-gray-500 dark:text-gray-400 hidden">Cargando imágenes…</p>
                        <div class="kp-ig-grid grid gap-1 w-full min-h-[2rem]" style="grid-template-columns: repeat(5, minmax(0, 1fr));"></div>
                        <p class="kp-ig-vacio text-xs text-gray-500 dark:text-gray-400 mt-2 hidden">No hay imágenes que coincidan con la búsqueda.</p>
                        <button type="button" class="kp-ig-cargar-mas mt-3 w-full text-sm py-2 rounded-md border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 hidden">Cargar más</button>
                    </div>
                </div>
            </div>
            <div class="flex justify-end gap-3 mt-6">
                <button type="button" onclick="cerrarModalAñadirImagenSublineaCm()" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-md dark:bg-gray-700 dark:text-gray-300">Cancelar</button>
                <button type="button" id="btn-guardar-imagen-sublinea-cm" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700">Guardar</button>
            </div>
        </div>
    </div>

    <script>
    if (!window.kpInternaGlobalRegistrar) {
    (function() {
        const KP_IG_PAGE = 15;
        const KP_IG_URL = @json(route('admin.imagenes.ultimas-globales'));
        const KP_IG_IMG_BASE = @json(rtrim(asset('images/'), '/'));

        window.kpPartirNombreEnPalabras = function(nombre) {
            const n = String(nombre || '').trim();
            if (!n) return [];
            const tokens = n.split(/(?:\s+|\/|\||;)+/).map(function(p) { return p.trim(); }).filter(Boolean);
            const visto = new Set();
            const palabras = [];
            tokens.forEach(function(token) {
                const limpia = token.replace(/^[^a-zA-Z0-9À-ÿ.,]+|[^a-zA-Z0-9À-ÿ.,]+$/gi, '');
                if (!limpia) return;
                const clave = limpia.toLowerCase();
                if (visto.has(clave)) return;
                visto.add(clave);
                palabras.push(limpia);
            });
            return palabras;
        };

        window.kpTokensBuscadorInternaGlobal = function(valor) {
            const v = String(valor || '').trim();
            if (!v) return [];
            return v.split(/\s+/).map(function(t) {
                return t.replace(/^-+|-+$/g, '').trim();
            }).filter(Boolean);
        };

        window.kpUrlVistaDesdeRutaAlmacenIg = function(raw) {
            const t = (raw || '').trim();
            if (!t) return '';
            if (/^https?:\/\//i.test(t)) return t;
            let p = t.replace(/^\/+/, '');
            if (/^images\//i.test(p)) {
                return @json(rtrim(url('/'), '/')) + '/' + p;
            }
            return KP_IG_IMG_BASE + '/' + p;
        };

        const estadosPanel = {};

        function kpIgMarcarBoton(btn, activo) {
            const on = ['ring-2', 'ring-blue-500', 'border-blue-500', 'bg-blue-100', 'dark:bg-blue-900', 'font-semibold'];
            if (activo) {
                btn.classList.add.apply(btn.classList, on);
                btn.setAttribute('aria-pressed', 'true');
            } else {
                btn.classList.remove.apply(btn.classList, on);
                btn.setAttribute('aria-pressed', 'false');
            }
        }

        function kpIgSincronizarPalabrasConBuscador(est) {
            const tokens = window.kpTokensBuscadorInternaGlobal(est.input.value);
            est.wrapPalabras.querySelectorAll('.kp-ig-palabra-btn').forEach(function(btn) {
                const p = btn.dataset.palabra || '';
                const activo = tokens.some(function(t) { return t.toLowerCase() === p.toLowerCase(); });
                kpIgMarcarBoton(btn, activo);
            });
        }

        function kpIgValorBuscadorDesdeTokens(tokens) {
            if (!tokens.length) return '';
            return tokens.join(' ');
        }

        function kpIgRenderPalabras(est) {
            const palabras = est.getPalabras ? est.getPalabras() : [];
            est.wrapPalabras.innerHTML = '';
            if (!palabras.length) {
                est.wrapPalabras.innerHTML = '<span class="text-xs text-gray-500 dark:text-gray-400">Escribe el nombre del producto para ver sugerencias.</span>';
                return;
            }
            palabras.forEach(function(palabra) {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'kp-ig-palabra-btn text-xs px-2 py-0.5 rounded border border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200 hover:bg-blue-100 dark:hover:bg-blue-900 transition-colors';
                btn.dataset.palabra = palabra;
                btn.setAttribute('aria-pressed', 'false');
                btn.textContent = palabra;
                btn.title = 'Buscar «' + palabra + '»';
                est.wrapPalabras.appendChild(btn);
            });
            kpIgSincronizarPalabrasConBuscador(est);
        }

        function kpIgPairFromItem(it) {
            return {
                rutaGrande: it.ruta_grande || it.ruta || '',
                rutaPequena: it.ruta_pequena || it.ruta_grande || it.ruta || '',
                thumbVisual: it.ruta_pequena || it.ruta_grande || it.ruta || '',
                url: it.url || '',
            };
        }

        function kpIgUrlPreviewDesdeRutaOrUrl(raw) {
            const t = String(raw || '').trim();
            if (!t) return '';
            if (/^https?:\/\//i.test(t)) return t;
            return window.kpUrlVistaDesdeRutaAlmacenIg(t);
        }

        window.kpIgCerrarPreviewGrande = function() {
            const overlay = document.getElementById('kp-ig-preview-overlay');
            if (!overlay) return;
            overlay.style.display = 'none';
            overlay.classList.add('hidden');
            overlay.classList.remove('flex');
            overlay.setAttribute('aria-hidden', 'true');
            const img = overlay.querySelector('img');
            if (img) img.removeAttribute('src');
        };

        window.kpIgAbrirPreviewGrande = function(rutaOrUrl) {
            const t = String(rutaOrUrl || '').trim();
            if (!t) return;
            const src = kpIgUrlPreviewDesdeRutaOrUrl(t);
            if (!src) return;
            let overlay = document.getElementById('kp-ig-preview-overlay');
            if (!overlay) {
                overlay = document.createElement('div');
                overlay.id = 'kp-ig-preview-overlay';
                overlay.className = 'fixed inset-0 hidden items-center justify-center bg-black/85 p-4 cursor-pointer';
                overlay.setAttribute('aria-hidden', 'true');
                overlay.setAttribute('role', 'dialog');
                overlay.setAttribute('aria-modal', 'true');
                overlay.setAttribute('aria-label', 'Vista ampliada');
                const img = document.createElement('img');
                img.alt = '';
                img.className = 'block rounded shadow-2xl bg-white max-w-[96vw] max-h-[90vh] w-auto h-auto object-contain pointer-events-none';
                const btnCerrar = document.createElement('button');
                btnCerrar.type = 'button';
                btnCerrar.className = 'absolute top-3 right-3 z-10 w-8 h-8 rounded-full bg-blue-600 text-white text-xl leading-none hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-white';
                btnCerrar.setAttribute('aria-label', 'Cerrar');
                btnCerrar.textContent = '×';
                overlay.appendChild(img);
                overlay.appendChild(btnCerrar);
                btnCerrar.addEventListener('click', function(e) {
                    e.stopPropagation();
                    window.kpIgCerrarPreviewGrande();
                });
                overlay.addEventListener('click', function(e) {
                    if (e.target === overlay) window.kpIgCerrarPreviewGrande();
                });
            }
            const modal = document.querySelector('[id^="modal-añadir-imagen"]:not(.hidden)');
            if (modal) {
                modal.appendChild(overlay);
            } else if (overlay.parentNode !== document.body) {
                document.body.appendChild(overlay);
            }
            const img = overlay.querySelector('img');
            if (!img) return;
            img.src = src;
            overlay.style.display = 'flex';
            overlay.classList.remove('hidden');
            overlay.classList.add('flex');
            overlay.setAttribute('aria-hidden', 'false');
        };

        if (!window.__kpIgPreviewEscapeInit) {
            window.__kpIgPreviewEscapeInit = true;
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') window.kpIgCerrarPreviewGrande();
            });
        }

        function kpIgEstaSeleccionada(est, pair) {
            return typeof est.isSelected === 'function' && est.isSelected(pair);
        }

        function kpIgMarcarCeldaImg(btn, seleccionada) {
            const on = ['ring-4', 'ring-blue-500', 'border-blue-500'];
            const badge = btn.querySelector('.kp-ig-sel-badge');
            if (seleccionada) {
                btn.classList.add.apply(btn.classList, on);
                btn.setAttribute('aria-pressed', 'true');
                if (!badge) {
                    const mark = document.createElement('span');
                    mark.className = 'kp-ig-sel-badge absolute top-0.5 left-0.5 z-10 w-5 h-5 flex items-center justify-center rounded-full bg-blue-600 text-white text-[10px] font-bold shadow pointer-events-none';
                    mark.textContent = '✓';
                    btn.appendChild(mark);
                }
            } else {
                btn.classList.remove.apply(btn.classList, on);
                btn.setAttribute('aria-pressed', 'false');
                if (badge) badge.remove();
            }
        }

        function kpIgSincronizarSeleccionGrid(est) {
            if (!est || !est.grid) return;
            est.grid.querySelectorAll('[data-kp-ig-cell]').forEach(function(cell) {
                const btn = cell.querySelector('button');
                if (!btn) return;
                const pair = {
                    rutaGrande: cell.dataset.kpIgRutaGrande || '',
                    rutaPequena: cell.dataset.kpIgRutaPequena || '',
                    thumbVisual: cell.dataset.kpIgThumb || '',
                };
                kpIgMarcarCeldaImg(btn, kpIgEstaSeleccionada(est, pair));
            });
            if (typeof est.renderResumen === 'function') {
                est.renderResumen();
            }
        }

        window.kpIgPintarResumenSeleccion = function(el, pairs) {
            if (!el) return;
            el.innerHTML = '';
            if (!pairs || !pairs.length) {
                el.innerHTML = '<p class="text-xs text-gray-500 dark:text-gray-400">Ninguna imagen seleccionada. Haz clic en una miniatura de abajo y pulsa <strong class="font-medium">Guardar</strong>.</p>';
                return;
            }
            const grid = document.createElement('div');
            grid.className = 'grid gap-1 w-full kp-ig-seleccion-grid';
            grid.style.gridTemplateColumns = 'repeat(10, minmax(0, 1fr))';
            pairs.forEach(function(pair) {
                const src = window.kpUrlVistaDesdeRutaAlmacenIg(pair.thumbVisual || pair.rutaPequena || pair.rutaGrande);
                const cell = document.createElement('div');
                cell.className = 'relative aspect-square rounded overflow-hidden ring-2 ring-blue-500 bg-gray-100 dark:bg-gray-800';
                const img = document.createElement('img');
                img.src = src;
                img.className = 'w-full h-full object-cover block';
                img.alt = '';
                img.loading = 'lazy';
                cell.appendChild(img);
                grid.appendChild(cell);
            });
            el.appendChild(grid);
            const note = document.createElement('p');
            note.className = 'text-xs text-gray-600 dark:text-gray-400 mt-2';
            note.textContent = pairs.length + ' imagen' + (pairs.length === 1 ? '' : 'es') + ' seleccionada' + (pairs.length === 1 ? '' : 's') + '. Pulsa Guardar para aplicar.';
            el.appendChild(note);
        };

        function kpIgCeldaImagen(item, est, onClick) {
            const pair = kpIgPairFromItem(item);
            const src = window.kpUrlVistaDesdeRutaAlmacenIg(pair.thumbVisual);
            const cell = document.createElement('div');
            cell.className = 'kp-ig-cell min-w-0 w-full';
            cell.dataset.kpIgCell = '1';
            cell.dataset.kpIgRutaGrande = pair.rutaGrande;
            cell.dataset.kpIgRutaPequena = pair.rutaPequena;
            cell.dataset.kpIgThumb = pair.thumbVisual;
            cell.dataset.kpIgUrl = pair.url || pair.rutaGrande || pair.thumbVisual;
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'relative w-full aspect-square p-0 border border-gray-200 dark:border-gray-600 rounded overflow-hidden bg-white dark:bg-gray-800 hover:ring-2 hover:ring-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-500';
            btn.title = pair.rutaGrande || '';
            const img = document.createElement('img');
            img.src = src;
            img.alt = '';
            img.className = 'w-full h-full object-cover block pointer-events-none';
            img.loading = 'lazy';
            img.onerror = function() { img.className = 'w-full h-full object-cover block opacity-40 pointer-events-none'; };
            btn.appendChild(img);
            kpIgMarcarCeldaImg(btn, kpIgEstaSeleccionada(est, pair));
            btn.addEventListener('click', function() {
                onClick(item);
                kpIgSincronizarSeleccionGrid(est);
            });
            const btnAmpliar = document.createElement('button');
            btnAmpliar.type = 'button';
            btnAmpliar.className = 'kp-ig-btn-ampliar absolute top-0 right-0 w-6 h-6 flex items-center justify-center rounded-bl-md shadow-md focus:outline-none focus:ring-2 focus:ring-white';
            btnAmpliar.title = 'Ver tamaño original';
            btnAmpliar.setAttribute('aria-label', 'Ampliar imagen');
            btnAmpliar.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><circle cx="10" cy="10" r="6"/><path d="M14.5 14.5L20 20"/><path d="M10 7v6M7 10h6"/></svg>';
            cell.appendChild(btn);
            cell.appendChild(btnAmpliar);
            return cell;
        }

        function kpIgRenderGrid(est) {
            const prev = est.grid.children.length;
            const hasta = Math.min(est.visible, est.items.length);
            for (let i = prev; i < hasta; i++) {
                const item = est.items[i];
                est.grid.appendChild(kpIgCeldaImagen(item, est, function(it) {
                    if (typeof est.onSelect === 'function') {
                        est.onSelect(kpIgPairFromItem(it));
                    }
                }));
            }
            est.vacio.classList.toggle('hidden', est.items.length > 0);
            est.cargarMas.classList.toggle('hidden', !est.hasMore && est.visible >= est.items.length);
            kpIgSincronizarSeleccionGrid(est);
        }

        async function kpIgCargar(est, appendApi) {
            if (!appendApi) {
                est.items = [];
                est.visible = 0;
                est.grid.innerHTML = '';
                est.vacio.textContent = 'No hay imágenes que coincidan con la búsqueda.';
            }
            est.cargando.classList.remove('hidden');
            est.cargarMas.disabled = true;
            try {
                const params = new URLSearchParams({
                    limit: String(KP_IG_PAGE),
                    offset: String(appendApi ? est.items.length : 0),
                    q: est.input.value.trim(),
                });
                const res = await fetch(KP_IG_URL + '?' + params.toString(), {
                    headers: { Accept: 'application/json' },
                    credentials: 'same-origin',
                });
                if (!res.ok) throw new Error('HTTP ' + res.status);
                const data = await res.json();
                if (!data.success) throw new Error(data.message || 'Error');
                const nuevas = Array.isArray(data.data) ? data.data : [];
                if (appendApi) {
                    est.items = est.items.concat(nuevas);
                } else {
                    est.items = nuevas;
                    est.visible = Math.min(KP_IG_PAGE, est.items.length);
                }
                est.hasMore = !!data.has_more;
                kpIgRenderGrid(est);
            } catch (err) {
                console.error('Interna global:', err);
                if (!appendApi) {
                    est.grid.innerHTML = '';
                    est.vacio.textContent = 'No se pudieron cargar las imágenes.';
                    est.vacio.classList.remove('hidden');
                    est.cargarMas.classList.add('hidden');
                }
            } finally {
                est.cargando.classList.add('hidden');
                est.cargarMas.disabled = false;
            }
        }

        window.kpInternaGlobalRefrescarPalabras = function(prefix) {
            const est = estadosPanel[prefix];
            if (est) kpIgRenderPalabras(est);
        };

        window.kpInternaGlobalRefrescarSeleccion = function(prefix) {
            const est = estadosPanel[prefix];
            if (est) kpIgSincronizarSeleccionGrid(est);
        };

        window.kpInternaGlobalAlActivar = function(prefix) {
            const est = estadosPanel[prefix];
            if (!est) return Promise.resolve();
            kpIgRenderPalabras(est);
            return kpIgCargar(est, false).then(function() {
                kpIgSincronizarSeleccionGrid(est);
            });
        };

        window.kpInternaGlobalRegistrar = function(prefix, options) {
            options = options || {};
            const panel = document.querySelector('[data-kp-ig-panel="' + prefix + '"]');
            if (!panel || panel.dataset.kpIgInit === '1') {
                return estadosPanel[prefix];
            }
            panel.dataset.kpIgInit = '1';

            const est = {
                prefix: prefix,
                panel: panel,
                wrapPalabras: panel.querySelector('.kp-ig-palabras'),
                input: panel.querySelector('.kp-ig-buscador'),
                grid: panel.querySelector('.kp-ig-grid'),
                vacio: panel.querySelector('.kp-ig-vacio'),
                cargando: panel.querySelector('.kp-ig-cargando'),
                cargarMas: panel.querySelector('.kp-ig-cargar-mas'),
                resumen: panel.querySelector('.kp-ig-seleccion-resumen'),
                onSelect: options.onSelect || null,
                isSelected: options.isSelected || null,
                renderResumen: options.renderResumen || null,
                getPalabras: options.getPalabras || function() { return []; },
                items: [],
                visible: 0,
                hasMore: false,
                debounce: null,
            };
            if (!est.renderResumen && est.resumen) {
                est.renderResumen = function() {
                    window.kpIgPintarResumenSeleccion(est.resumen, []);
                };
            }
            estadosPanel[prefix] = est;

            est.grid.addEventListener('click', function(e) {
                const btnZoom = e.target.closest('.kp-ig-btn-ampliar');
                if (btnZoom) {
                    e.preventDefault();
                    e.stopPropagation();
                    e.stopImmediatePropagation();
                    const cell = btnZoom.closest('[data-kp-ig-cell]');
                    if (cell) {
                        const url = (cell.dataset.kpIgUrl || cell.dataset.kpIgRutaGrande || cell.dataset.kpIgThumb || '').trim();
                        if (url) window.kpIgAbrirPreviewGrande(url);
                    }
                    return;
                }
            }, true);

            panel.addEventListener('click', function(e) {
                const btn = e.target.closest('.kp-ig-palabra-btn');
                if (!btn) return;
                const palabra = btn.dataset.palabra || '';
                let tokens = window.kpTokensBuscadorInternaGlobal(est.input.value);
                const activo = btn.getAttribute('aria-pressed') === 'true';
                if (activo) {
                    tokens = tokens.filter(function(t) { return t.toLowerCase() !== palabra.toLowerCase(); });
                } else if (!tokens.some(function(t) { return t.toLowerCase() === palabra.toLowerCase(); })) {
                    tokens.push(palabra);
                }
                est.input.value = kpIgValorBuscadorDesdeTokens(tokens);
                kpIgSincronizarPalabrasConBuscador(est);
                kpIgCargar(est, false);
                est.input.focus();
            });

            est.input.addEventListener('input', function() {
                kpIgSincronizarPalabrasConBuscador(est);
                clearTimeout(est.debounce);
                est.debounce = setTimeout(function() {
                    kpIgCargar(est, false);
                }, 350);
            });

            est.cargarMas.addEventListener('click', function() {
                if (est.visible < est.items.length) {
                    est.visible = Math.min(est.items.length, est.visible + KP_IG_PAGE);
                    kpIgRenderGrid(est);
                } else if (est.hasMore) {
                    const visAntes = est.visible;
                    kpIgCargar(est, true).then(function() {
                        est.visible = Math.min(est.items.length, visAntes + KP_IG_PAGE);
                        kpIgRenderGrid(est);
                    });
                }
            });

            return est;
        };
    })();
    }

</script>

<script>
    const NEO_PANEL_PRODUCTOS_BASE = @json(rtrim(url('/panel-privado/productos'), '/'));
    window.__modalProductoNeoMainReady = false;
    window.__crearMasivoImagenesSublinea = {};

    function cacheKeyImagenesSublineaCrearMasivo(productoId, principalId, subId) {
        return String(productoId) + '::' + String(principalId) + '::' + String(subId);
    }

    function limpiarCacheImagenesSublineaProductoCrearMasivo(productoId) {
        if (!productoId || !window.__crearMasivoImagenesSublinea) return;
        const prefix = String(productoId) + '::';
        Object.keys(window.__crearMasivoImagenesSublinea).forEach(function(k) {
            if (k.indexOf(prefix) === 0) delete window.__crearMasivoImagenesSublinea[k];
        });
    }

    window.__mismoProductoSeleccionado = null;
    // Contexto de categoría cargada desde Neo (id, nombre) para acotar la búsqueda de producto.
    window.__neoCategoriaFiltro = null;

    function actualizarIndicadorCategoriaFiltroNeo() {
        const wrap = document.getElementById('neo-categoria-filtro-wrap');
        const texto = document.getElementById('neo-categoria-filtro-texto');
        if (!wrap || !texto) return;
        const ctx = window.__neoCategoriaFiltro;
        if (ctx && ctx.id) {
            texto.textContent = 'Búsqueda de producto acotada a categoría: ' + (ctx.nombre || ('#' + ctx.id)) + ' (tokens del slug filtrados por especificaciones internas de la categoría).';
            wrap.classList.remove('hidden');
        } else {
            texto.textContent = '';
            wrap.classList.add('hidden');
        }
    }
    let descartarUrlPendienteCrearMasivo = null;
    let descartarFilaPendienteCrearMasivo = null;
    const CANTIDAD_LOTE_DEFECTO_CREAR_MASIVO = 20;
    window.__modoProductoTodasNeo = false;
    window.__neoProductoPorUrlModoTodas = {};
    window.__cacheSpecsProductoModoTodas = {};
    window.__crearMasivoPopupPendiente = null;
    const estadoLotesAnalisis = {
        urls: [],
        cursor: 0,
        lastStart: null,
        lastEnd: null,
        signature: '',
    };

    function prepararNuevaPestanaUrlCrearMasivo() {
        try {
            const w = window.open('', '_blank');
            if (w && !w.closed) {
                window.__crearMasivoPopupPendiente = w;
                return true;
            }
        } catch (e) {}
        return false;
    }

    function abrirUrlModalEnNuevaPestanaCrearMasivo(url) {
        const u = String(url || '').trim();
        if (!u) return;
        const w = window.__crearMasivoPopupPendiente;
        if (w && !w.closed) {
            try {
                w.location.href = u;
                window.__crearMasivoPopupPendiente = null;
                return;
            } catch (e) {}
        }
        try { window.open(u, '_blank', 'noopener,noreferrer'); } catch (e) {}
    }

    function claveUrlProductoTodasNeo(url) {
        const raw = String(url || '').trim();
        if (!raw) return '';
        try {
            const u = new URL(raw);
            const host = String(u.host || '').toLowerCase();
            const path = String(u.pathname || '/').replace(/\/+$/, '') || '/';
            return host + path;
        } catch (e) {
            return raw.replace(/^https?:\/\//i, '').replace(/\/+$/, '').toLowerCase();
        }
    }

    function guardarProductoAsociadoUrlTodasNeo(url, producto) {
        const k = claveUrlProductoTodasNeo(url);
        if (!k || !producto || !producto.id) return;
        window.__neoProductoPorUrlModoTodas[k] = producto;
    }

    function obtenerProductoAsociadoUrlTodasNeo(urlA, urlB) {
        const kA = claveUrlProductoTodasNeo(urlA);
        const kB = claveUrlProductoTodasNeo(urlB);
        return (kA && window.__neoProductoPorUrlModoTodas[kA]) || (kB && window.__neoProductoPorUrlModoTodas[kB]) || null;
    }

    async function enriquecerResultadosModoTodasConSpecs(resultados) {
        if (!Array.isArray(resultados) || resultados.length === 0) return resultados;
        const idsNecesarios = [];
        const vistos = {};
        resultados.forEach(function(r) {
            const asociado = obtenerProductoAsociadoUrlTodasNeo(r && r.url_normalizada, r && r.url);
            const pid = asociado && asociado.id ? String(asociado.id) : '';
            if (!pid || vistos[pid]) return;
            vistos[pid] = true;
            if (!window.__cacheSpecsProductoModoTodas[pid]) idsNecesarios.push(pid);
        });

        for (let i = 0; i < idsNecesarios.length; i++) {
            const pid = idsNecesarios[i];
            try {
                const url = '{{ route("admin.ofertas.crear-masivo.recargar-especificaciones", ["producto" => "__ID__"]) }}'.replace('__ID__', pid);
                const res = await fetch(url, { headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content } });
                const data = await res.json();
                if (data && data.success) {
                    window.__cacheSpecsProductoModoTodas[pid] = {
                        especificaciones: data.especificaciones || null,
                        tiene_especificaciones: !!data.tiene_especificaciones,
                        url_producto: data.url_producto || null,
                        imagenes_producto: Array.isArray(data.imagenes_producto) ? data.imagenes_producto : [],
                    };
                } else {
                    window.__cacheSpecsProductoModoTodas[pid] = { especificaciones: null, tiene_especificaciones: false };
                }
            } catch (e) {
                window.__cacheSpecsProductoModoTodas[pid] = { especificaciones: null, tiene_especificaciones: false };
            }
        }

        return resultados.map(function(r) {
            const asociado = obtenerProductoAsociadoUrlTodasNeo(r && r.url_normalizada, r && r.url);
            if (!asociado) return r;
            const nuevo = Object.assign({}, r);
            const pid = String(asociado.id);
            const cache = window.__cacheSpecsProductoModoTodas[pid] || null;
            const productoAsociado = Object.assign({}, asociado);
            if (cache && cache.url_producto) productoAsociado.url_producto = cache.url_producto;
            if (cache && Array.isArray(cache.imagenes_producto)) productoAsociado.imagenes_producto = cache.imagenes_producto;

            // En modo "Todas", forzamos el producto asociado desde neo para cada URL
            // y limpiamos flags de "sin producto" para mantener coherencia visual.
            nuevo.producto = productoAsociado;
            nuevo.no_entre_opciones = false;
            if (nuevo.tienda) nuevo.error = null;
            if (!nuevo.productos_candidatos || !Array.isArray(nuevo.productos_candidatos) || nuevo.productos_candidatos.length === 0) {
                nuevo.productos_candidatos = [productoAsociado];
            }
            if (cache) {
                nuevo.especificaciones = cache.especificaciones || null;
                nuevo.tiene_especificaciones = !!cache.tiene_especificaciones;
            }
            return nuevo;
        });
    }

    // Fallback defensivo: mantiene funcional el modal de producto aunque falle otra parte del script.
    (function initModalProductoNeoFallback() {
        const btn = document.getElementById('btnProductoNeo');
        const modal = document.getElementById('modal-producto-neo');
        const lista = document.getElementById('modal-producto-neo-lista');
        const totalEl = document.getElementById('modal-producto-neo-total-urls');
        if (!btn || !modal || !lista) return;
        if (btn.dataset.modalProductoNeoFallbackInit === '1') return;
        btn.dataset.modalProductoNeoFallbackInit = '1';

        btn.addEventListener('click', async function() {
            if (window.__modalProductoNeoMainReady) return;
            modal.classList.remove('hidden');
            lista.innerHTML = '<p class="text-gray-500 dark:text-gray-400 text-sm">Cargando...</p>';
            if (totalEl) totalEl.textContent = '...';
            try {
                const url = '{{ route("admin.neo.crear-masivo.productos") }}?mostrar_si=1&mostrar_no=0&_=' + Date.now();
                const res = await fetch(url, { cache: 'no-store', headers: { 'Accept': 'application/json' } });
                const raw = await res.json();
                const productos = Array.isArray(raw) ? raw : (Array.isArray(raw.productos) ? raw.productos : []);
                if (!Array.isArray(productos) || productos.length === 0) {
                    lista.innerHTML = '<p class="text-gray-500 dark:text-gray-400 text-sm">No hay productos con filas neo (añadida=no).</p>';
                    if (totalEl) totalEl.textContent = '0';
                    return;
                }
                let suma = 0;
                productos.forEach(function(item) { suma += parseInt(item.count, 10) || 0; });
                if (totalEl) totalEl.textContent = String(suma);
                lista.innerHTML = '';
                productos.forEach(function(item) {
                    const div = document.createElement('div');
                    div.className = 'p-3 rounded-lg border border-gray-200 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer transition';
                    div.innerHTML = '<span class="font-medium text-gray-900 dark:text-white">' + (item.texto_completo || 'Producto #' + item.producto_id) + '</span> <span class="text-gray-500 dark:text-gray-400 text-sm">(' + item.count + ' URL' + (item.count !== 1 ? 's' : '') + ')</span>';
                    div.addEventListener('click', async function() {
                        modal.classList.add('hidden');
                        if (typeof aplicarProductoNeoAlFormulario === 'function') {
                            await aplicarProductoNeoAlFormulario(item.producto_id, 'mostrar_si=1&mostrar_no=0');
                        }
                    });
                    lista.appendChild(div);
                });
            } catch (err) {
                console.error(err);
                lista.innerHTML = '<p class="text-red-500 text-sm">Error al cargar: ' + (err && err.message ? err.message : 'desconocido') + '</p>';
                if (totalEl) totalEl.textContent = '?';
            }
        });
    })();

    /** Diagnóstico en consola para POST crear-masivo/crear: error_ref coincide con laravel.log y con error_log de PHP. */
    function logCrearOfertaBulkDiagnostico(etiqueta, res, responseText, data) {
        const status = res && typeof res.status === 'number' ? res.status : null;
        console.group(etiqueta + ' · HTTP ' + status);
        if (data && data.error_ref) {
            console.warn('error_ref (busca esta cadena en storage/logs/laravel.log o en el log del servidor web/PHP):', data.error_ref);
        }
        console.log('error:', data && data.error);
        if (data && data.error_debug) {
            console.error('error_debug:', data.error_debug);
        }
        if (data && data.message) {
            console.log('message:', data.message);
        }
        if (data && data.errors) {
            console.log('errors (validación Laravel):', data.errors);
        }
        console.log('payload JSON completo:', data);
        if (responseText && (!data || (typeof data === 'object' && Object.keys(data).length === 0))) {
            console.log('cuerpo bruto (primeros 2000 caracteres):', String(responseText).slice(0, 2000));
        }
        console.groupEnd();
    }

    function obtenerUrlsLimpiasTextarea() {
        return document.getElementById('urls').value
            .split('\n')
            .map(u => u.trim())
            .filter(Boolean);
    }

    function actualizarEstadoLotesDesdeTextarea() {
        const urls = obtenerUrlsLimpiasTextarea();
        const signature = urls.join('\n');
        const total = urls.length;
        const inputCantidad = document.getElementById('cantidad_urls_analizar');
        if (signature !== estadoLotesAnalisis.signature) {
            estadoLotesAnalisis.urls = urls;
            estadoLotesAnalisis.cursor = 0;
            estadoLotesAnalisis.lastStart = null;
            estadoLotesAnalisis.lastEnd = null;
            estadoLotesAnalisis.signature = signature;
            if (inputCantidad) inputCantidad.value = total > 0 ? CANTIDAD_LOTE_DEFECTO_CREAR_MASIVO : 0;
        } else {
            estadoLotesAnalisis.urls = urls;
        }
        const totalActual = estadoLotesAnalisis.urls.length;
        const infoTotal = document.getElementById('info_total_urls_textarea');
        const infoProgreso = document.getElementById('info_progreso_lote');
        const btnRepetir = document.getElementById('btnRepetirMismoLote');
        const btnAnalizarTexto = document.getElementById('btnAnalizarTexto');
        if (inputCantidad && (!inputCantidad.value || parseInt(inputCantidad.value, 10) <= 0)) {
            inputCantidad.value = totalActual > 0 ? CANTIDAD_LOTE_DEFECTO_CREAR_MASIVO : 0;
        }
        if (infoTotal) infoTotal.textContent = 'Total URLs: ' + totalActual;
        const pendientes = Math.max(totalActual - estadoLotesAnalisis.cursor, 0);
        if (infoProgreso) infoProgreso.textContent = 'Pendientes: ' + pendientes;
        if (btnAnalizarTexto) btnAnalizarTexto.textContent = estadoLotesAnalisis.cursor > 0 && pendientes > 0 ? 'Analizar siguientes URLs' : 'Analizar URLs';
        if (btnRepetir) btnRepetir.classList.toggle('hidden', !(estadoLotesAnalisis.lastStart !== null && estadoLotesAnalisis.lastEnd !== null));
    }

    function obtenerLoteSegunCantidad(repetirUltimo) {
        const total = estadoLotesAnalisis.urls.length;
        const cantidadInput = document.getElementById('cantidad_urls_analizar');
        let cantidad = parseInt(cantidadInput ? cantidadInput.value : '0', 10);
        if (!Number.isFinite(cantidad) || cantidad <= 0) cantidad = total;
        if (repetirUltimo) {
            if (estadoLotesAnalisis.lastStart === null || estadoLotesAnalisis.lastEnd === null) return null;
            return {
                urls: estadoLotesAnalisis.urls.slice(estadoLotesAnalisis.lastStart, estadoLotesAnalisis.lastEnd),
                start: estadoLotesAnalisis.lastStart,
                end: estadoLotesAnalisis.lastEnd,
                repetir: true,
            };
        }
        const start = estadoLotesAnalisis.cursor;
        const end = Math.min(start + cantidad, total);
        if (start >= end) return null;
        return {
            urls: estadoLotesAnalisis.urls.slice(start, end),
            start,
            end,
            repetir: false,
        };
    }

    const chkMismoProducto = document.getElementById('mismo_producto');
    const chkNoProductosSugeridos = document.getElementById('no_productos_sugeridos');
    function actualizarDisponibilidadMismoProductoCrearMasivo() {
        const cb = document.getElementById('mismo_producto');
        const block = document.getElementById('mismo-producto-block');
        const msg = document.getElementById('mismo-producto-modo-todas-msg');
        const cbNoSug = document.getElementById('no_productos_sugeridos');
        const wrapNoSug = document.getElementById('no_productos_sugeridos-wrap');
        const modoTodas = !!window.__modoProductoTodasNeo;
        if (cb) {
            cb.disabled = modoTodas || (cbNoSug && cbNoSug.checked);
            cb.classList.toggle('opacity-60', cb.disabled);
            cb.classList.toggle('cursor-not-allowed', cb.disabled);
            if (modoTodas) cb.checked = false;
        }
        if (cbNoSug) {
            cbNoSug.disabled = modoTodas || (cb && cb.checked);
            cbNoSug.classList.toggle('opacity-60', cbNoSug.disabled);
            cbNoSug.classList.toggle('cursor-not-allowed', cbNoSug.disabled);
            if (modoTodas) cbNoSug.checked = false;
        }
        if (wrapNoSug) wrapNoSug.classList.toggle('opacity-60', modoTodas);
        if (msg) msg.classList.toggle('hidden', !modoTodas);
        if (block && modoTodas) block.classList.add('hidden');
        if (modoTodas) window.__mismoProductoSeleccionado = null;
    }
    if (chkMismoProducto) {
        chkMismoProducto.addEventListener('change', function() {
            if (window.__modoProductoTodasNeo) {
                this.checked = false;
                return;
            }
            if (this.checked && chkNoProductosSugeridos) {
                chkNoProductosSugeridos.checked = false;
            }
            actualizarDisponibilidadMismoProductoCrearMasivo();
            const block = document.getElementById('mismo-producto-block');
            if (!block) return;
            if (this.checked) {
                block.classList.remove('hidden');
            } else {
                block.classList.add('hidden');
                window.__mismoProductoSeleccionado = null;
            }
        });
    }
    if (chkNoProductosSugeridos) {
        chkNoProductosSugeridos.addEventListener('change', function() {
            if (window.__modoProductoTodasNeo) {
                this.checked = false;
                return;
            }
            if (this.checked && chkMismoProducto) {
                chkMismoProducto.checked = false;
                const block = document.getElementById('mismo-producto-block');
                if (block) block.classList.add('hidden');
                window.__mismoProductoSeleccionado = null;
            }
            actualizarDisponibilidadMismoProductoCrearMasivo();
        });
    }
    actualizarDisponibilidadMismoProductoCrearMasivo();

    const mismoProductoSearch = document.getElementById('mismo-producto-search');
    const mismoProductoSugerencias = document.getElementById('mismo-producto-sugerencias');
    let mismoProductoTimeout = null;
    if (mismoProductoSearch && mismoProductoSugerencias) {
        mismoProductoSearch.addEventListener('input', function() {
            clearTimeout(mismoProductoTimeout);
            const query = this.value.trim();
            if (query.length < 2) {
                mismoProductoSugerencias.classList.add('hidden');
                return;
            }
            mismoProductoTimeout = setTimeout(() => buscarProductosMismoProducto(query), 300);
        });
        mismoProductoSearch.addEventListener('focus', function() {
            if (window.__mismoProductosBusqueda && window.__mismoProductosBusqueda.length) {
                mismoProductoSugerencias.classList.remove('hidden');
            }
        });
        mismoProductoSearch.addEventListener('blur', function() {
            setTimeout(() => mismoProductoSugerencias.classList.add('hidden'), 200);
        });
    }

    async function buscarProductosMismoProducto(query) {
        try {
            const res = await fetch(`{{ route('admin.ofertas.buscar.productos') }}?q=${encodeURIComponent(query)}`);
            const productos = await res.json();
            window.__mismoProductosBusqueda = Array.isArray(productos) ? productos : [];
            const cont = mismoProductoSugerencias;
            cont.innerHTML = '';
            if (window.__mismoProductosBusqueda.length === 0) {
                cont.innerHTML = '<div class="px-4 py-2 text-gray-500 dark:text-gray-400 text-sm">No se encontraron productos</div>';
            } else {
                window.__mismoProductosBusqueda.forEach((p, i) => {
                    const el = document.createElement('div');
                    el.className = 'producto-sugerencia-item-crear-masivo px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 cursor-pointer text-gray-900 dark:text-white border-b border-gray-200 dark:border-gray-600 last:border-b-0 text-sm' + (i === 0 ? ' bg-blue-100 dark:bg-blue-700' : '');
                    el.textContent = p.texto_completo;
                    el.dataset.producto = JSON.stringify(p);
                    el.addEventListener('click', function(e) {
                        e.preventDefault();
                        try {
                            const producto = JSON.parse(this.dataset.producto);
                            seleccionarMismoProducto(producto);
                        } catch (err) { console.error(err); }
                    });
                    cont.appendChild(el);
                });
            }
            cont.classList.remove('hidden');
        } catch (err) {
            console.error(err);
            mismoProductoSugerencias.classList.add('hidden');
        }
    }

    async function seleccionarMismoProducto(producto) {
        mismoProductoSugerencias.classList.add('hidden');
        mismoProductoSearch.value = '';
        try {
            const url = '{{ route("admin.ofertas.crear-masivo.recargar-especificaciones", ["producto" => "__ID__"]) }}'.replace('__ID__', producto.id);
            const res = await fetch(url, { headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content } });
            const data = await res.json();
            const especs = data.success ? (data.especificaciones || null) : null;
            const tieneEspecs = data.success && (data.tiene_especificaciones || false);
            const urlProducto = data.url_producto || null;
            const imagenesProducto = Array.isArray(data.imagenes_producto) ? data.imagenes_producto : [];
            const productoCompleto = {
                id: producto.id,
                nombre: producto.nombre,
                marca: producto.marca,
                modelo: producto.modelo,
                talla: producto.talla,
                texto_completo: producto.texto_completo,
                url_producto: urlProducto,
                imagenes_producto: imagenesProducto
            };
            window.__mismoProductoSeleccionado = productoCompleto;
            document.getElementById('mismo-producto-nombre').textContent = productoCompleto.texto_completo;
            document.getElementById('mismo-producto-elegido').classList.remove('hidden');
            document.querySelector('#mismo-producto-block .producto-search-container').classList.add('hidden');
            const specContainer = document.getElementById('mismo-producto-spec-container');
            const especsHtml = buildEspecsHtml(productoCompleto, especs, tieneEspecs);
            specContainer.innerHTML = especsHtml;
            specContainer.dataset.columnasIds = (especs && especs.columnas_ids) ? JSON.stringify(especs.columnas_ids) : '[]';
            specContainer.dataset.esUnidadUnica = (especs && especs.unidad_de_medida === 'unidadUnica') ? '1' : '0';
        } catch (err) {
            console.error(err);
            alert('Error al cargar: ' + err.message);
        }
    }

    function resetMismoProductoSeleccionCrearMasivo() {
        window.__modoProductoTodasNeo = false;
        window.__neoProductoPorUrlModoTodas = {};
        window.__cacheSpecsProductoModoTodas = {};
        window.__mismoProductoSeleccionado = null;
        const nombre = document.getElementById('mismo-producto-nombre');
        const elegido = document.getElementById('mismo-producto-elegido');
        const searchWrap = document.querySelector('#mismo-producto-block .producto-search-container');
        const specContainer = document.getElementById('mismo-producto-spec-container');
        const searchInput = document.getElementById('mismo-producto-search');
        const cbMismoProducto = document.getElementById('mismo_producto');
        const blockMismoProducto = document.getElementById('mismo-producto-block');
        if (nombre) nombre.textContent = '';
        if (elegido) elegido.classList.add('hidden');
        if (searchWrap) searchWrap.classList.remove('hidden');
        if (specContainer) specContainer.innerHTML = '';
        if (searchInput) searchInput.value = '';
        if (cbMismoProducto) cbMismoProducto.checked = false;
        if (blockMismoProducto) blockMismoProducto.classList.add('hidden');
        actualizarDisponibilidadMismoProductoCrearMasivo();
    }

    const btnMismoProductoQuitar = document.getElementById('mismo-producto-quitar');
    if (btnMismoProductoQuitar) {
        btnMismoProductoQuitar.addEventListener('click', function() {
            resetMismoProductoSeleccionCrearMasivo();
        });
    }

    function parseRespuestaProductosNeoApi(raw) {
        if (Array.isArray(raw)) {
            return { productos: raw, filas_neo_tienda_id_null: 0, filas_neo_categoria_id_null: 0 };
        }
        return {
            productos: Array.isArray(raw.productos) ? raw.productos : [],
            filas_neo_tienda_id_null: parseInt(String(raw.filas_neo_tienda_id_null ?? 0), 10) || 0,
            filas_neo_categoria_id_null: parseInt(String(raw.filas_neo_categoria_id_null ?? 0), 10) || 0,
        };
    }

    function queryMostrarCategoriaModalProductoNeo() {
        const chkSi = document.getElementById('modal-producto-neo-chk-cat-mostrar-si');
        const chkNo = document.getElementById('modal-producto-neo-chk-cat-mostrar-no');
        const chkNull = document.getElementById('modal-producto-neo-chk-categoria-null');
        const si = chkSi && chkSi.checked;
        const no = chkNo && chkNo.checked;
        const incluirNull = chkNull && chkNull.checked;
        let q = 'categoria_mostrar_si=' + (si ? '1' : '0') + '&categoria_mostrar_no=' + (no ? '1' : '0');
        if (incluirNull) {
            q += '&categoria_mostrar_null=1';
        }
        return q;
    }

    function ajustarChecksMostrarCategoriaModalProductoNeo(ev) {
        const t = ev && ev.target ? ev.target.id : '';
        const si = document.getElementById('modal-producto-neo-chk-cat-mostrar-si');
        const no = document.getElementById('modal-producto-neo-chk-cat-mostrar-no');
        if (!si || !no || t !== 'modal-producto-neo-chk-cat-mostrar-no') {
            return;
        }
        if (no.checked && si.checked) {
            si.checked = false;
        }
    }

    function queryMostrarTiendaModalProductoNeo() {
        const chkSi = document.getElementById('modal-producto-neo-chk-mostrar-si');
        const chkNo = document.getElementById('modal-producto-neo-chk-mostrar-no');
        const chkNull = document.getElementById('modal-producto-neo-chk-tienda-null');
        const si = chkSi && chkSi.checked;
        const no = chkNo && chkNo.checked;
        const incluirNull = chkNull && chkNull.checked;
        let q = 'mostrar_si=' + (si ? '1' : '0') + '&mostrar_no=' + (no ? '1' : '0');
        if (incluirNull) {
            q += '&mostrar_null=1';
        }
        return q;
    }

    function esSoloMostrarNoModalProductoNeo() {
        const si = document.getElementById('modal-producto-neo-chk-mostrar-si');
        const no = document.getElementById('modal-producto-neo-chk-mostrar-no');
        return !!(si && no && !si.checked && no.checked);
    }

    function queryTiendasNoSeleccionadasModalProductoNeo() {
        if (!esSoloMostrarNoModalProductoNeo()) {
            return '';
        }
        const container = document.getElementById('modal-producto-neo-tiendas-no-lista');
        if (!container || container.dataset.loaded !== '1') {
            return '';
        }
        const checks = container.querySelectorAll('input[type="checkbox"].modal-producto-neo-tienda-no-cb');
        const parts = [];
        checks.forEach(function(c) {
            if (c.checked) {
                parts.push('tienda_ids[]=' + encodeURIComponent(c.value));
            }
        });
        return parts.length ? '&' + parts.join('&') : '';
    }

    function queryModalProductoNeoFiltros() {
        const parts = [
            queryMostrarTiendaModalProductoNeo(),
            queryMostrarCategoriaModalProductoNeo(),
            queryTiendasNoSeleccionadasModalProductoNeo().replace(/^&/, ''),
        ].filter(Boolean);
        return parts.join('&');
    }

    const urlTplUrlsPorProductoNeo = '{{ route("admin.neo.crear-masivo.urls-por-producto", ["productoId" => "__ID__"]) }}';

    /**
     * Carga URLs neo + mismo producto (igual que al elegir en el modal Producto).
     * Deja window.__crearMasivoOrigenNeo = { tipo: 'producto', ... } para el botón Siguiente.
     */
    async function aplicarProductoNeoAlFormulario(productoId, filtrosQuery) {
        window.__modoProductoTodasNeo = false;
        window.__neoProductoPorUrlModoTodas = {};
        window.__cacheSpecsProductoModoTodas = {};
        actualizarDisponibilidadMismoProductoCrearMasivo();
        const fq = filtrosQuery != null ? filtrosQuery : queryModalProductoNeoFiltros();
        const resUrl = await fetch(urlTplUrlsPorProductoNeo.replace('__ID__', String(productoId)) + '?' + fq + '&_=' + Date.now(), { cache: 'no-store', headers: { 'Accept': 'application/json' } });
        const dataUrl = await resUrl.json();
        const urls = dataUrl.urls || [];
        const producto = dataUrl.producto;
        window.__neoCategoriaFiltro = null;
        actualizarIndicadorCategoriaFiltroNeo();
        document.getElementById('urls').value = urls.join('\n');
        actualizarEstadoLotesDesdeTextarea();
        document.getElementById('mismo_producto').checked = true;
        document.getElementById('mismo-producto-block').classList.remove('hidden');
        if (producto) {
            await seleccionarMismoProducto({ id: producto.id, nombre: producto.nombre, marca: producto.marca, modelo: producto.modelo, talla: producto.talla, texto_completo: producto.texto_completo });
        }
        window.__crearMasivoOrigenNeo = { tipo: 'producto', producto_id: productoId, filtrosQuery: fq };
    }

    async function aplicarTodosProductosNeoAlFormulario(productos, filtrosQuery) {
        const fq = filtrosQuery != null ? filtrosQuery : queryModalProductoNeoFiltros();
        const listaProductos = Array.isArray(productos) ? productos : [];
        const urlsTotal = [];
        const mapTmp = {};
        for (let i = 0; i < listaProductos.length; i++) {
            const item = listaProductos[i];
            const pid = item && item.producto_id;
            if (!pid) continue;
            const resUrl = await fetch(urlTplUrlsPorProductoNeo.replace('__ID__', String(pid)) + '?' + fq + '&_=' + Date.now(), {
                cache: 'no-store',
                headers: { 'Accept': 'application/json' }
            });
            const dataUrl = await resUrl.json();
            const urls = Array.isArray(dataUrl.urls) ? dataUrl.urls : [];
            const p = dataUrl && dataUrl.producto
                ? {
                    id: dataUrl.producto.id,
                    nombre: dataUrl.producto.nombre,
                    marca: dataUrl.producto.marca,
                    modelo: dataUrl.producto.modelo,
                    talla: dataUrl.producto.talla,
                    texto_completo: dataUrl.producto.texto_completo || item.texto_completo || ('Producto #' + pid),
                    url_producto: dataUrl.producto.url_producto || null,
                    imagenes_producto: Array.isArray(dataUrl.producto.imagenes_producto) ? dataUrl.producto.imagenes_producto : [],
                }
                : {
                    id: pid,
                    texto_completo: item.texto_completo || ('Producto #' + pid),
                    nombre: item.nombre || null,
                    marca: item.marca || null,
                    modelo: item.modelo || null,
                    talla: item.talla || null,
                    url_producto: null,
                    imagenes_producto: [],
                };
            urls.forEach(function(u) {
                if (!u) return;
                const su = String(u);
                urlsTotal.push(su);
                const k = claveUrlProductoTodasNeo(su);
                if (k && p && p.id) mapTmp[k] = p;
            });
        }

        window.__neoCategoriaFiltro = null;
        actualizarIndicadorCategoriaFiltroNeo();
        resetMismoProductoSeleccionCrearMasivo();
        window.__modoProductoTodasNeo = true;
        window.__neoProductoPorUrlModoTodas = mapTmp;
        actualizarDisponibilidadMismoProductoCrearMasivo();
        document.getElementById('urls').value = urlsTotal.join('\n');
        actualizarEstadoLotesDesdeTextarea();
        const cantidadInput = document.getElementById('cantidad_urls_analizar');
        if (cantidadInput) cantidadInput.value = urlsTotal.length > 0 ? urlsTotal.length : 0;
        window.__crearMasivoOrigenNeo = null;
    }

    async function siguienteProductoNeoCrearMasivo() {
        const ctx = window.__crearMasivoOrigenNeo;
        if (!ctx || ctx.tipo !== 'producto' || ctx.filtrosQuery == null) {
            return;
        }
        const urlListado = '{{ route("admin.neo.crear-masivo.productos") }}' + '?' + ctx.filtrosQuery + '&_=' + Date.now();
        const res = await fetch(urlListado, { cache: 'no-store', headers: { 'Accept': 'application/json' } });
        const parsedList = parseRespuestaProductosNeoApi(await res.json());
        const productos = parsedList.productos;
        if (!Array.isArray(productos) || productos.length === 0) {
            alert('No quedan productos pendientes con este filtro de tiendas.');
            return;
        }
        const ids = productos.map(function(p) { return parseInt(String(p.producto_id), 10); });
        const curId = parseInt(String(ctx.producto_id), 10);
        let idx = ids.indexOf(curId);
        let nextItem = null;
        if (idx >= 0 && idx < productos.length - 1) {
            nextItem = productos[idx + 1];
        } else if (idx === -1) {
            nextItem = productos[0];
        }
        if (!nextItem) {
            alert('No hay más productos pendientes con este filtro.');
            return;
        }
        await aplicarProductoNeoAlFormulario(nextItem.producto_id, ctx.filtrosQuery);
    }

    function registrarMasterTiendasMostrarNoModalProducto() {
        const master = document.getElementById('modal-producto-neo-chk-tiendas-no-todas');
        const container = document.getElementById('modal-producto-neo-tiendas-no-lista');
        if (!master || master.__listenerMasterTiendasNo) return;
        master.__listenerMasterTiendasNo = true;
        master.addEventListener('change', function() {
            const marcar = master.checked;
            if (container) {
                container.querySelectorAll('input.modal-producto-neo-tienda-no-cb').forEach(function(c) {
                    c.checked = marcar;
                });
            }
            recargarListaModalProductoNeo({ mantenerDesplegableAbierto: true });
        });
    }

    async function cargarOpcionesTiendasMostrarNoModal() {
        const container = document.getElementById('modal-producto-neo-tiendas-no-lista');
        if (!container || container.dataset.loaded === '1') {
            return;
        }
        container.innerHTML = '<p class="text-xs text-gray-500 dark:text-gray-400">Cargando tiendas...</p>';
        let lista;
        try {
            const res = await fetch('{{ route("admin.neo.crear-masivo.tiendas-mostrar-no") }}' + '?_=' + Date.now(), { cache: 'no-store', headers: { 'Accept': 'application/json' } });
            lista = await res.json();
        } catch (e) {
            console.error(e);
            container.innerHTML = '<p class="text-xs text-red-500">Error al cargar tiendas.</p>';
            return;
        }
        container.innerHTML = '';
        if (!Array.isArray(lista) || lista.length === 0) {
            container.innerHTML = '<p class="text-xs text-gray-500 dark:text-gray-400">No hay tiendas con mostrar «no».</p>';
            container.dataset.loaded = '1';
            return;
        }
        lista.forEach(function(t) {
            const label = document.createElement('label');
            label.className = 'flex items-center gap-2 cursor-pointer text-gray-800 dark:text-gray-200';
            const cb = document.createElement('input');
            cb.type = 'checkbox';
            cb.value = String(t.id);
            cb.checked = true;
            cb.className = 'rounded border-gray-300 text-blue-600 focus:ring-blue-500 modal-producto-neo-tienda-no-cb';
            cb.addEventListener('change', function(ev) {
                ev.stopPropagation();
                actualizarMasterTiendasMostrarNoModal();
                recargarListaModalProductoNeo({ mantenerDesplegableAbierto: true });
            });
            label.addEventListener('click', function(ev) {
                ev.stopPropagation();
            });
            const span = document.createElement('span');
            span.textContent = t.nombre || ('Tienda #' + t.id);
            label.appendChild(cb);
            label.appendChild(span);
            container.appendChild(label);
        });
        container.dataset.loaded = '1';
        registrarMasterTiendasMostrarNoModalProducto();
    }

    function actualizarMasterTiendasMostrarNoModal() {
        const master = document.getElementById('modal-producto-neo-chk-tiendas-no-todas');
        const checks = document.querySelectorAll('#modal-producto-neo-tiendas-no-lista input.modal-producto-neo-tienda-no-cb');
        if (!master || !checks.length) {
            return;
        }
        master.checked = Array.from(checks).every(function(c) {
            return c.checked;
        });
    }

    async function actualizarVisibilidadPanelTiendasNoModal(opciones) {
        opciones = opciones || {};
        const reiniciarSeleccion = opciones.reiniciarSeleccion === true;
        const mantenerDesplegableAbierto = opciones.mantenerDesplegableAbierto === true;
        const panel = document.getElementById('modal-producto-neo-panel-tiendas-no');
        const detailsLista = document.getElementById('modal-producto-neo-details-tiendas-no-lista');
        if (!panel) {
            return;
        }
        const soloNo = esSoloMostrarNoModalProductoNeo();
        panel.classList.toggle('hidden', !soloNo);
        if (soloNo) {
            const master = document.getElementById('modal-producto-neo-chk-tiendas-no-todas');
            const container = document.getElementById('modal-producto-neo-tiendas-no-lista');
            if (reiniciarSeleccion && master) {
                master.checked = true;
                if (container) {
                    container.querySelectorAll('input.modal-producto-neo-tienda-no-cb').forEach(function(c) {
                        c.checked = true;
                    });
                }
            }
            if (detailsLista && !mantenerDesplegableAbierto) {
                detailsLista.open = false;
            }
            await cargarOpcionesTiendasMostrarNoModal();
            registrarMasterTiendasMostrarNoModalProducto();
        } else if (detailsLista) {
            detailsLista.open = false;
        }
    }

    /**
     * Si "Sí" sigue marcado y el usuario marca "No", sin esto quedarían los dos marcados
     * y el backend interpreta "ambos" = sin filtro (mismas URLs que todo).
     * Regla: al activar "No" mientras "Sí" está activo → solo "No".
     * Para "ambos": primero deja solo "No", luego marca también "Sí".
     */
    function ajustarChecksMostrarTiendaModalProductoNeo(ev) {
        const t = ev && ev.target ? ev.target.id : '';
        const si = document.getElementById('modal-producto-neo-chk-mostrar-si');
        const no = document.getElementById('modal-producto-neo-chk-mostrar-no');
        if (!si || !no || t !== 'modal-producto-neo-chk-mostrar-no') {
            return;
        }
        if (no.checked && si.checked) {
            si.checked = false;
        }
    }

    async function recargarListaModalProductoNeo(opciones) {
        await actualizarVisibilidadPanelTiendasNoModal(opciones);
        const lista = document.getElementById('modal-producto-neo-lista');
        const totalEl = document.getElementById('modal-producto-neo-total-urls');
        const btn = document.getElementById('btnProductoNeo');
        const modal = document.getElementById('modal-producto-neo');
        lista.innerHTML = '<p class="text-gray-500 dark:text-gray-400 text-sm">Cargando...</p>';
        if (totalEl) totalEl.textContent = '\u2014';
        const urlListado = '{{ route("admin.neo.crear-masivo.productos") }}' + '?' + queryModalProductoNeoFiltros() + '&_=' + Date.now();
        const res = await fetch(urlListado, { cache: 'no-store', headers: { 'Accept': 'application/json' } });
        const parsedList = parseRespuestaProductosNeoApi(await res.json());
        const productos = parsedList.productos;
        const nuloCountEl = document.getElementById('modal-producto-neo-null-count');
        if (nuloCountEl) {
            nuloCountEl.textContent = String(parsedList.filas_neo_tienda_id_null);
        }
        const catNuloCountEl = document.getElementById('modal-producto-neo-categoria-null-count');
        if (catNuloCountEl) {
            catNuloCountEl.textContent = String(parsedList.filas_neo_categoria_id_null);
        }
        if (!Array.isArray(productos) || productos.length === 0) {
            lista.innerHTML = '<p class="text-gray-500 dark:text-gray-400 text-sm">No hay productos con filas neo (añadida=no) para este filtro.</p>';
            setTotalUrlsDisponiblesNeo('modal-producto-neo-total-urls', 0);
            return;
        }
        const suma = sumarConteosListaNeo(productos, 'count');
        setTotalUrlsDisponiblesNeo('modal-producto-neo-total-urls', suma);
        lista.innerHTML = '';
        const btnTodas = document.createElement('button');
        btnTodas.type = 'button';
        btnTodas.setAttribute('data-busqueda-texto', 'todas');
        btnTodas.className = 'w-full text-left p-3 rounded-lg border-2 border-blue-500 bg-blue-50 dark:bg-blue-900/30 dark:border-blue-400 hover:bg-blue-100 dark:hover:bg-blue-900/50 transition mb-2';
        btnTodas.innerHTML = '<span class="font-semibold text-blue-800 dark:text-blue-200">Todas</span> <span class="text-blue-700 dark:text-blue-300 text-sm">(' + suma + ' URL' + (suma !== 1 ? 's' : '') + ')</span>';
        btnTodas.addEventListener('click', async function() {
            modal.classList.add('hidden');
            btn.disabled = true;
            try {
                await aplicarTodosProductosNeoAlFormulario(productos, queryModalProductoNeoFiltros());
            } catch (err) {
                console.error(err);
                alert('Error al cargar URLs de todos los productos: ' + err.message);
            } finally {
                btn.disabled = false;
            }
        });
        lista.appendChild(btnTodas);
        productos.forEach(function(item) {
            const div = document.createElement('div');
            div.className = 'p-3 rounded-lg border border-gray-200 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer transition';
            const etiqueta = item.texto_completo || ('Producto #' + item.producto_id);
            div.setAttribute('data-busqueda-texto', etiqueta);
            div.innerHTML = '<span class="font-medium text-gray-900 dark:text-white">' + etiqueta + '</span> <span class="text-gray-500 dark:text-gray-400 text-sm">(' + item.count + ' URL' + (item.count !== 1 ? 's' : '') + ')</span>';
            div.addEventListener('click', async function() {
                modal.classList.add('hidden');
                const fq = queryModalProductoNeoFiltros();
                try {
                    await aplicarProductoNeoAlFormulario(item.producto_id, fq);
                } catch (err) {
                    console.error(err);
                    alert('Error al cargar URLs: ' + err.message);
                }
                btn.disabled = false;
            });
            lista.appendChild(div);
        });
        refrescarFiltroBuscadorModalNeo('modal-producto-neo');
    }

    window.__modalProductoNeoMainReady = true;
    document.getElementById('btnProductoNeo').addEventListener('click', async function() {
        const btn = this;
        const modal = document.getElementById('modal-producto-neo');
        const chkSiOpen = document.getElementById('modal-producto-neo-chk-mostrar-si');
        const chkNoOpen = document.getElementById('modal-producto-neo-chk-mostrar-no');
        const tiendasLista = document.getElementById('modal-producto-neo-tiendas-no-lista');
        if (tiendasLista) {
            tiendasLista.innerHTML = '';
            delete tiendasLista.dataset.loaded;
        }
        const panelTiendasNo = document.getElementById('modal-producto-neo-panel-tiendas-no');
        const detTiendas = document.getElementById('modal-producto-neo-details-tiendas-no-lista');
        const masterTiendasNo = document.getElementById('modal-producto-neo-chk-tiendas-no-todas');
        if (panelTiendasNo) panelTiendasNo.classList.add('hidden');
        if (detTiendas) detTiendas.open = false;
        if (masterTiendasNo) masterTiendasNo.checked = true;
        if (chkSiOpen) chkSiOpen.checked = true;
        if (chkNoOpen) chkNoOpen.checked = false;
        const chkNullOpen = document.getElementById('modal-producto-neo-chk-tienda-null');
        if (chkNullOpen) chkNullOpen.checked = false;
        const chkCatSiOpen = document.getElementById('modal-producto-neo-chk-cat-mostrar-si');
        const chkCatNoOpen = document.getElementById('modal-producto-neo-chk-cat-mostrar-no');
        const chkCatNullOpen = document.getElementById('modal-producto-neo-chk-categoria-null');
        if (chkCatSiOpen) chkCatSiOpen.checked = true;
        if (chkCatNoOpen) chkCatNoOpen.checked = false;
        if (chkCatNullOpen) chkCatNullOpen.checked = false;
        ocultarBuscadorModalNeo('modal-producto-neo');
        btn.disabled = true;
        modal.classList.remove('hidden');
        try {
            await recargarListaModalProductoNeo();
        } catch (err) {
            console.error(err);
            document.getElementById('modal-producto-neo-lista').innerHTML = '<p class="text-red-500 text-sm">Error al cargar: ' + err.message + '</p>';
            const totalEl = document.getElementById('modal-producto-neo-total-urls');
            if (totalEl) totalEl.textContent = '\u2014';
        }
        btn.disabled = false;
    });

    ['modal-producto-neo-chk-mostrar-si', 'modal-producto-neo-chk-mostrar-no', 'modal-producto-neo-chk-tienda-null'].forEach(function(id) {
        const el = document.getElementById(id);
        if (!el) return;
        el.addEventListener('change', async function(ev) {
            if (document.getElementById('modal-producto-neo').classList.contains('hidden')) return;
            if (id === 'modal-producto-neo-chk-tienda-null' && el.checked) {
                const si = document.getElementById('modal-producto-neo-chk-mostrar-si');
                const no = document.getElementById('modal-producto-neo-chk-mostrar-no');
                if (si) si.checked = false;
                if (no) no.checked = false;
            } else if ((id === 'modal-producto-neo-chk-mostrar-si' || id === 'modal-producto-neo-chk-mostrar-no') && el.checked) {
                const nulo = document.getElementById('modal-producto-neo-chk-tienda-null');
                if (nulo) nulo.checked = false;
            }
            ajustarChecksMostrarTiendaModalProductoNeo(ev);
            try {
                const soloNo = esSoloMostrarNoModalProductoNeo();
                await recargarListaModalProductoNeo({
                    reiniciarSeleccion: soloNo,
                    mantenerDesplegableAbierto: soloNo,
                });
            } catch (err) {
                console.error(err);
                document.getElementById('modal-producto-neo-lista').innerHTML = '<p class="text-red-500 text-sm">Error al cargar: ' + err.message + '</p>';
            }
        });
    });
    ['modal-producto-neo-chk-cat-mostrar-si', 'modal-producto-neo-chk-cat-mostrar-no', 'modal-producto-neo-chk-categoria-null'].forEach(function(id) {
        const el = document.getElementById(id);
        if (!el) return;
        el.addEventListener('change', async function(ev) {
            if (document.getElementById('modal-producto-neo').classList.contains('hidden')) return;
            if (id === 'modal-producto-neo-chk-categoria-null' && el.checked) {
                const si = document.getElementById('modal-producto-neo-chk-cat-mostrar-si');
                const no = document.getElementById('modal-producto-neo-chk-cat-mostrar-no');
                if (si) si.checked = false;
                if (no) no.checked = false;
            } else if ((id === 'modal-producto-neo-chk-cat-mostrar-si' || id === 'modal-producto-neo-chk-cat-mostrar-no') && el.checked) {
                const nulo = document.getElementById('modal-producto-neo-chk-categoria-null');
                if (nulo) nulo.checked = false;
            }
            ajustarChecksMostrarCategoriaModalProductoNeo(ev);
            try {
                const soloNo = esSoloMostrarNoModalProductoNeo();
                await recargarListaModalProductoNeo({
                    reiniciarSeleccion: soloNo,
                    mantenerDesplegableAbierto: soloNo,
                });
            } catch (err) {
                console.error(err);
                document.getElementById('modal-producto-neo-lista').innerHTML = '<p class="text-red-500 text-sm">Error al cargar: ' + err.message + '</p>';
            }
        });
    });

    const btnBuscarTiendaModalProductoNeo = document.getElementById('modal-producto-neo-btn-buscar-tienda');
    if (btnBuscarTiendaModalProductoNeo) {
        btnBuscarTiendaModalProductoNeo.addEventListener('click', async function() {
            const btn = this;
            if (!confirm('¿Rellenar tienda por URL en filas neo sin tienda_id y categoría desde el producto cuando haya producto_id pero no categoría_id?')) {
                return;
            }
            btn.disabled = true;
            const textoOriginal = btn.textContent;
            btn.textContent = 'Rellenando…';
            try {
                const res = await fetch('{{ route("admin.neo.crear-masivo.rellenar-tienda-id") }}', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                });
                const data = await res.json();
                if (!res.ok || !data.success) {
                    throw new Error(data.message || 'Error al rellenar');
                }
                const st = data.stats || {};
                const ti = st.tienda || st;
                const cat = st.categoria || {};
                alert(
                    '— Tienda por URL —\n' +
                    'Revisadas: ' + (ti.revisadas ?? 0) +
                    '\nActualizadas: ' + (ti.actualizadas ?? 0) +
                    '\nSin tienda detectada: ' + (ti.sin_tienda_detectada ?? 0) +
                    '\nURL vacía: ' + (ti.url_descifrada_vacia ?? 0) +
                    '\nErrores: ' + (ti.errores ?? 0) +
                    '\n\n— Categoría desde producto —\n' +
                    'Revisadas: ' + (cat.revisadas ?? 0) +
                    '\nActualizadas: ' + (cat.actualizadas ?? 0) +
                    '\nProducto sin categoría: ' + (cat.producto_sin_categoria ?? 0) +
                    '\nErrores: ' + (cat.errores ?? 0)
                );
                if (!document.getElementById('modal-producto-neo').classList.contains('hidden')) {
                    await recargarListaModalProductoNeo();
                }
            } catch (err) {
                console.error(err);
                alert('Error: ' + err.message);
            } finally {
                btn.disabled = false;
                btn.textContent = textoOriginal;
            }
        });
    }

    document.getElementById('modal-producto-neo-cerrar').addEventListener('click', function() {
        document.getElementById('modal-producto-neo').classList.add('hidden');
    });
    document.getElementById('modal-producto-neo-cerrar-btn').addEventListener('click', function() {
        document.getElementById('modal-producto-neo').classList.add('hidden');
    });

    const MODALES_NEO_BUSCADOR_PREFIXES = ['modal-producto-neo', 'modal-categoria-neo', 'modal-tienda-neo'];

    function normalizarTextoBuscadorModalNeo(texto) {
        return String(texto || '')
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '');
    }

    function obtenerQueryBuscadorModalNeo(modalPrefix) {
        const inp = document.getElementById(modalPrefix + '-buscador');
        return inp ? normalizarTextoBuscadorModalNeo(inp.value.trim()) : '';
    }

    function aplicarFiltroBuscadorListaModalNeo(listaEl, query) {
        if (!listaEl) return;
        const items = listaEl.querySelectorAll('[data-busqueda-texto]');
        let visibles = 0;
        items.forEach(function(el) {
            const texto = normalizarTextoBuscadorModalNeo(el.getAttribute('data-busqueda-texto') || '');
            const match = !query || texto.indexOf(query) !== -1;
            el.classList.toggle('hidden', !match);
            if (match) visibles++;
        });
        let aviso = listaEl.querySelector('.modal-neo-buscador-sin-resultados');
        if (query && items.length > 0 && visibles === 0) {
            if (!aviso) {
                aviso = document.createElement('p');
                aviso.className = 'modal-neo-buscador-sin-resultados text-gray-500 dark:text-gray-400 text-sm';
                aviso.textContent = 'Ningún resultado para esta búsqueda.';
                listaEl.appendChild(aviso);
            }
            aviso.classList.remove('hidden');
        } else if (aviso) {
            aviso.classList.add('hidden');
        }
    }

    function refrescarFiltroBuscadorModalNeo(modalPrefix) {
        aplicarFiltroBuscadorListaModalNeo(
            document.getElementById(modalPrefix + '-lista'),
            obtenerQueryBuscadorModalNeo(modalPrefix)
        );
    }

    function limpiarBuscadorModalNeo(modalPrefix) {
        const inp = document.getElementById(modalPrefix + '-buscador');
        if (inp) inp.value = '';
        refrescarFiltroBuscadorModalNeo(modalPrefix);
    }

    function actualizarEstiloBtnToggleBuscadorModalNeo(modalPrefix, activo) {
        const btn = document.getElementById(modalPrefix + '-btn-toggle-buscador');
        if (!btn) return;
        btn.setAttribute('aria-expanded', activo ? 'true' : 'false');
        btn.classList.toggle('text-blue-600', activo);
        btn.classList.toggle('dark:text-blue-400', activo);
        btn.classList.toggle('bg-blue-50', activo);
        btn.classList.toggle('dark:bg-blue-900/30', activo);
    }

    function ocultarBuscadorModalNeo(modalPrefix) {
        const wrap = document.getElementById(modalPrefix + '-buscador-wrap');
        if (wrap) wrap.classList.add('hidden');
        actualizarEstiloBtnToggleBuscadorModalNeo(modalPrefix, false);
        limpiarBuscadorModalNeo(modalPrefix);
    }

    /** Muestra u oculta la lupa y el campo de búsqueda del modal (p. ej. tienda→categorías sigue oculto en subnivel). */
    function setBuscadorModalNeoDisponible(modalPrefix, disponible) {
        const btnToggle = document.getElementById(modalPrefix + '-btn-toggle-buscador');
        if (btnToggle) btnToggle.classList.toggle('hidden', !disponible);
        if (!disponible) {
            ocultarBuscadorModalNeo(modalPrefix);
        }
    }

    function toggleBuscadorModalNeo(modalPrefix) {
        const wrap = document.getElementById(modalPrefix + '-buscador-wrap');
        const inp = document.getElementById(modalPrefix + '-buscador');
        if (!wrap) return;
        const abrir = wrap.classList.contains('hidden');
        if (abrir) {
            wrap.classList.remove('hidden');
            actualizarEstiloBtnToggleBuscadorModalNeo(modalPrefix, true);
            if (inp) inp.focus();
        } else {
            ocultarBuscadorModalNeo(modalPrefix);
        }
    }

    function registrarBuscadorModalNeo(modalPrefix) {
        const inp = document.getElementById(modalPrefix + '-buscador');
        if (!inp || inp.__buscadorNeoRegistrado) return;
        inp.__buscadorNeoRegistrado = true;
        inp.addEventListener('input', function() {
            refrescarFiltroBuscadorModalNeo(modalPrefix);
        });
        const btnToggle = document.getElementById(modalPrefix + '-btn-toggle-buscador');
        if (btnToggle && !btnToggle.__toggleBuscadorNeoRegistrado) {
            btnToggle.__toggleBuscadorNeoRegistrado = true;
            btnToggle.addEventListener('click', function() {
                toggleBuscadorModalNeo(modalPrefix);
            });
        }
    }

    MODALES_NEO_BUSCADOR_PREFIXES.forEach(registrarBuscadorModalNeo);

    function setTotalUrlsDisponiblesNeo(elementId, total) {
        const el = document.getElementById(elementId);
        if (!el) return;
        if (total === '…' || total === '...') {
            el.textContent = '…';
            return;
        }
        el.textContent = String(parseInt(String(total), 10) || 0);
    }

    function sumarConteosListaNeo(items, campoCount) {
        if (!Array.isArray(items)) return 0;
        let suma = 0;
        items.forEach(function(item) {
            suma += parseInt(String(item[campoCount] ?? item.count ?? 0), 10) || 0;
        });
        return suma;
    }

    const estadoModalCategoriaNeo = {
        categorias: [],
        categoriaSeleccionada: null,
        tiendasDeCategoria: [],
    };

    function queryMostrarCategoriaModalCategoriaNeo() {
        const chkSi = document.getElementById('modal-categoria-neo-chk-cat-mostrar-si');
        const chkNo = document.getElementById('modal-categoria-neo-chk-cat-mostrar-no');
        const si = chkSi && chkSi.checked;
        const no = chkNo && chkNo.checked;
        return 'categoria_mostrar_si=' + (si ? '1' : '0') + '&categoria_mostrar_no=' + (no ? '1' : '0');
    }

    function queryMostrarTiendaModalCategoriaNeo() {
        const chkSi = document.getElementById('modal-categoria-neo-chk-mostrar-si');
        const chkNo = document.getElementById('modal-categoria-neo-chk-mostrar-no');
        const si = chkSi && chkSi.checked;
        const no = chkNo && chkNo.checked;
        return 'mostrar_si=' + (si ? '1' : '0') + '&mostrar_no=' + (no ? '1' : '0');
    }

    function esSoloMostrarNoModalCategoriaNeo() {
        const si = document.getElementById('modal-categoria-neo-chk-mostrar-si');
        const no = document.getElementById('modal-categoria-neo-chk-mostrar-no');
        return !!(si && no && !si.checked && no.checked);
    }

    function actualizarFiltrosVisiblesModalCategoriaNeo() {
        const enSubnivel = !!estadoModalCategoriaNeo.categoriaSeleccionada;
        const filtrosCat = document.getElementById('modal-categoria-neo-filtros-categoria');
        const filtrosTienda = document.getElementById('modal-categoria-neo-filtros-tienda');
        const panelTiendasNo = document.getElementById('modal-categoria-neo-panel-tiendas-no');
        if (filtrosCat) filtrosCat.classList.toggle('hidden', enSubnivel);
        if (filtrosTienda) filtrosTienda.classList.toggle('hidden', !enSubnivel);
        if (panelTiendasNo && !enSubnivel) {
            panelTiendasNo.classList.add('hidden');
        }
    }

    function ajustarChecksMostrarCategoriaModalCategoriaNeo(ev) {
        const t = ev && ev.target ? ev.target.id : '';
        const si = document.getElementById('modal-categoria-neo-chk-cat-mostrar-si');
        const no = document.getElementById('modal-categoria-neo-chk-cat-mostrar-no');
        if (!si || !no || t !== 'modal-categoria-neo-chk-cat-mostrar-no') {
            return;
        }
        if (no.checked && si.checked) {
            si.checked = false;
        }
    }

    function queryTiendasNoSeleccionadasModalCategoriaNeo() {
        if (!esSoloMostrarNoModalCategoriaNeo()) {
            return '';
        }
        const container = document.getElementById('modal-categoria-neo-tiendas-no-lista');
        if (!container || container.dataset.loaded !== '1') {
            return '';
        }
        const checks = container.querySelectorAll('input[type="checkbox"].modal-categoria-neo-tienda-no-cb');
        const parts = [];
        checks.forEach(function(c) {
            if (c.checked) {
                parts.push('tienda_ids[]=' + encodeURIComponent(c.value));
            }
        });
        return parts.length ? '&' + parts.join('&') : '';
    }

    function queryFiltrosListadoCategoriasModalCategoriaNeo() {
        return queryMostrarCategoriaModalCategoriaNeo();
    }

    function queryFiltrosTiendasModalCategoriaNeo() {
        return queryMostrarTiendaModalCategoriaNeo() + queryTiendasNoSeleccionadasModalCategoriaNeo();
    }

    function ajustarChecksMostrarTiendaModalCategoriaNeo(ev) {
        const t = ev && ev.target ? ev.target.id : '';
        const si = document.getElementById('modal-categoria-neo-chk-mostrar-si');
        const no = document.getElementById('modal-categoria-neo-chk-mostrar-no');
        if (!si || !no || t !== 'modal-categoria-neo-chk-mostrar-no') {
            return;
        }
        if (no.checked && si.checked) {
            si.checked = false;
        }
    }

    function registrarMasterTiendasMostrarNoModalCategoria() {
        const master = document.getElementById('modal-categoria-neo-chk-tiendas-no-todas');
        const container = document.getElementById('modal-categoria-neo-tiendas-no-lista');
        if (!master || master.__listenerMasterTiendasNo) return;
        master.__listenerMasterTiendasNo = true;
        master.addEventListener('change', function() {
            const marcar = master.checked;
            if (container) {
                container.querySelectorAll('input.modal-categoria-neo-tienda-no-cb').forEach(function(c) {
                    c.checked = marcar;
                });
            }
            recargarListadoTrasFiltroTiendasNoModalCategoria({ mantenerDesplegableAbierto: true });
        });
    }

    function recargarListadoTrasFiltroTiendasNoModalCategoria(opciones) {
        if (estadoModalCategoriaNeo.categoriaSeleccionada) {
            renderTiendasDeCategoriaModalNeo(estadoModalCategoriaNeo.categoriaSeleccionada);
        }
    }

    async function cargarOpcionesTiendasMostrarNoModalCategoria() {
        const container = document.getElementById('modal-categoria-neo-tiendas-no-lista');
        if (!container || container.dataset.loaded === '1') {
            return;
        }
        container.innerHTML = '<p class="text-xs text-gray-500 dark:text-gray-400">Cargando tiendas...</p>';
        let lista;
        try {
            const res = await fetch('{{ route("admin.neo.crear-masivo.tiendas-mostrar-no") }}' + '?_=' + Date.now(), { cache: 'no-store', headers: { 'Accept': 'application/json' } });
            lista = await res.json();
        } catch (e) {
            console.error(e);
            container.innerHTML = '<p class="text-xs text-red-500">Error al cargar tiendas.</p>';
            return;
        }
        container.innerHTML = '';
        if (!Array.isArray(lista) || lista.length === 0) {
            container.innerHTML = '<p class="text-xs text-gray-500 dark:text-gray-400">No hay tiendas con mostrar «no».</p>';
            container.dataset.loaded = '1';
            return;
        }
        lista.forEach(function(t) {
            const label = document.createElement('label');
            label.className = 'flex items-center gap-2 cursor-pointer text-gray-800 dark:text-gray-200';
            const cb = document.createElement('input');
            cb.type = 'checkbox';
            cb.value = String(t.id);
            cb.checked = true;
            cb.className = 'rounded border-gray-300 text-pink-600 focus:ring-pink-500 modal-categoria-neo-tienda-no-cb';
            cb.addEventListener('change', function(ev) {
                ev.stopPropagation();
                actualizarMasterTiendasMostrarNoModalCategoria();
                recargarListadoTrasFiltroTiendasNoModalCategoria({ mantenerDesplegableAbierto: true });
            });
            label.addEventListener('click', function(ev) {
                ev.stopPropagation();
            });
            const span = document.createElement('span');
            span.textContent = t.nombre || ('Tienda #' + t.id);
            label.appendChild(cb);
            label.appendChild(span);
            container.appendChild(label);
        });
        container.dataset.loaded = '1';
        registrarMasterTiendasMostrarNoModalCategoria();
    }

    function actualizarMasterTiendasMostrarNoModalCategoria() {
        const master = document.getElementById('modal-categoria-neo-chk-tiendas-no-todas');
        const checks = document.querySelectorAll('#modal-categoria-neo-tiendas-no-lista input.modal-categoria-neo-tienda-no-cb');
        if (!master || !checks.length) {
            return;
        }
        master.checked = Array.from(checks).every(function(c) {
            return c.checked;
        });
    }

    async function actualizarVisibilidadPanelTiendasNoModalCategoria(opciones) {
        opciones = opciones || {};
        const reiniciarSeleccion = opciones.reiniciarSeleccion === true;
        const mantenerDesplegableAbierto = opciones.mantenerDesplegableAbierto === true;
        const panel = document.getElementById('modal-categoria-neo-panel-tiendas-no');
        const detailsLista = document.getElementById('modal-categoria-neo-details-tiendas-no-lista');
        if (!panel) {
            return;
        }
        const soloNo = esSoloMostrarNoModalCategoriaNeo();
        panel.classList.toggle('hidden', !soloNo);
        if (soloNo) {
            const master = document.getElementById('modal-categoria-neo-chk-tiendas-no-todas');
            const container = document.getElementById('modal-categoria-neo-tiendas-no-lista');
            if (reiniciarSeleccion && master) {
                master.checked = true;
                if (container) {
                    container.querySelectorAll('input.modal-categoria-neo-tienda-no-cb').forEach(function(c) {
                        c.checked = true;
                    });
                }
            }
            if (detailsLista && !mantenerDesplegableAbierto) {
                detailsLista.open = false;
            }
            await cargarOpcionesTiendasMostrarNoModalCategoria();
            registrarMasterTiendasMostrarNoModalCategoria();
        } else if (detailsLista) {
            detailsLista.open = false;
        }
    }

    async function recargarContenidoModalCategoriaNeo(opciones) {
        actualizarFiltrosVisiblesModalCategoriaNeo();
        if (estadoModalCategoriaNeo.categoriaSeleccionada) {
            await actualizarVisibilidadPanelTiendasNoModalCategoria(opciones);
            await renderTiendasDeCategoriaModalNeo(estadoModalCategoriaNeo.categoriaSeleccionada);
        } else {
            if (esSoloMostrarNoModalCategoriaNeo()) {
                await actualizarVisibilidadPanelTiendasNoModalCategoria(opciones);
            }
            await recargarListaModalCategoriaNeo();
        }
    }

    async function recargarListaModalCategoriaNeo() {
        actualizarFiltrosVisiblesModalCategoriaNeo();
        const lista = document.getElementById('modal-categoria-neo-lista');
        lista.innerHTML = '<p class="text-gray-500 dark:text-gray-400 text-sm">Cargando...</p>';
        setTotalUrlsDisponiblesNeo('modal-categoria-neo-total-urls', '…');
        const urlListado = '{{ route("admin.neo.crear-masivo.categorias") }}' + '?' + queryFiltrosListadoCategoriasModalCategoriaNeo() + '&_=' + Date.now();
        const res = await fetch(urlListado, { cache: 'no-store', headers: { 'Accept': 'application/json' } });
        const categorias = await res.json();
        estadoModalCategoriaNeo.categorias = Array.isArray(categorias) ? categorias : [];
        renderCategoriasModalNeo();
    }

    function actualizarHeaderModalCategoriaNeo() {
        const titulo = document.getElementById('modal-categoria-neo-titulo');
        const btnAtras = document.getElementById('modal-categoria-neo-atras');
        const enSubnivel = !!estadoModalCategoriaNeo.categoriaSeleccionada;
        actualizarFiltrosVisiblesModalCategoriaNeo();
        setBuscadorModalNeoDisponible('modal-categoria-neo', true);
        if (enSubnivel) {
            if (titulo) titulo.textContent = 'Elegir tienda de ' + (estadoModalCategoriaNeo.categoriaSeleccionada.nombre || ('Categoría #' + estadoModalCategoriaNeo.categoriaSeleccionada.categoria_id));
            if (btnAtras) btnAtras.classList.remove('hidden');
        } else {
            if (titulo) titulo.textContent = 'Elegir categoría (neo con añadida=no)';
            if (btnAtras) btnAtras.classList.add('hidden');
        }
    }

    function renderCategoriasModalNeo() {
        const lista = document.getElementById('modal-categoria-neo-lista');
        const totalEl = document.getElementById('modal-categoria-neo-total-urls');
        const categorias = estadoModalCategoriaNeo.categorias || [];
        estadoModalCategoriaNeo.categoriaSeleccionada = null;
        actualizarHeaderModalCategoriaNeo();
        if (!Array.isArray(categorias) || categorias.length === 0) {
            lista.innerHTML = '<p class="text-gray-500 dark:text-gray-400 text-sm">No hay categorías con filas neo (añadida=no) para este filtro.</p>';
            setTotalUrlsDisponiblesNeo('modal-categoria-neo-total-urls', 0);
            return;
        }
        const suma = sumarConteosListaNeo(categorias, 'count');
        setTotalUrlsDisponiblesNeo('modal-categoria-neo-total-urls', suma);
        lista.innerHTML = '';
        categorias.forEach(function(item) {
            const div = document.createElement('div');
            div.className = 'p-3 rounded-lg border border-gray-200 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer transition';
            const etiqueta = item.nombre || ('Categoría #' + item.categoria_id);
            div.setAttribute('data-busqueda-texto', etiqueta);
            div.innerHTML = '<span class="font-medium text-gray-900 dark:text-white">' + etiqueta + '</span> <span class="text-gray-500 dark:text-gray-400 text-sm">(' + item.count + ' URL' + (item.count !== 1 ? 's' : '') + ')</span>';
            div.addEventListener('click', async function() {
                await renderTiendasDeCategoriaModalNeo(item);
            });
            lista.appendChild(div);
        });
        refrescarFiltroBuscadorModalNeo('modal-categoria-neo');
    }

    function htmlEtiquetasTiendaRestringidaNeo(item) {
        var parts = [];
        if (String(item.mostrar_tienda || '').toLowerCase() === 'no') {
            parts.push('<span class="text-red-600 dark:text-red-400 text-xs font-semibold ml-1">no mostrar</span>');
        }
        if (String(item.scrapear || '').toLowerCase() === 'no') {
            parts.push('<span class="text-red-600 dark:text-red-400 text-xs font-semibold ml-1">no scrapear</span>');
        }
        return parts.length ? ' ' + parts.join(' ') : '';
    }

    async function renderTiendasDeCategoriaModalNeo(item) {
        const lista = document.getElementById('modal-categoria-neo-lista');
        const modal = document.getElementById('modal-categoria-neo');
        estadoModalCategoriaNeo.categoriaSeleccionada = item;
        actualizarHeaderModalCategoriaNeo();
        await actualizarVisibilidadPanelTiendasNoModalCategoria({ mantenerDesplegableAbierto: true });
        lista.innerHTML = '<p class="text-gray-500 dark:text-gray-400 text-sm">Cargando tiendas...</p>';
        setTotalUrlsDisponiblesNeo('modal-categoria-neo-total-urls', '…');
        try {
            const fq = queryFiltrosTiendasModalCategoriaNeo();
            const res = await fetch('{{ route("admin.neo.crear-masivo.tiendas-por-categoria", ["categoriaId" => "__ID__"]) }}'.replace('__ID__', item.categoria_id) + '?' + fq + '&_=' + Date.now(), { cache: 'no-store', headers: { 'Accept': 'application/json' } });
            const tiendas = await res.json();
            estadoModalCategoriaNeo.tiendasDeCategoria = Array.isArray(tiendas) ? tiendas : [];
            if (estadoModalCategoriaNeo.tiendasDeCategoria.length === 0) {
                lista.innerHTML = '<p class="text-gray-500 dark:text-gray-400 text-sm">No hay tiendas pendientes para esta categoría.</p>';
                setTotalUrlsDisponiblesNeo('modal-categoria-neo-total-urls', 0);
                return;
            }
            const sumaTiendas = sumarConteosListaNeo(estadoModalCategoriaNeo.tiendasDeCategoria, 'count');
            setTotalUrlsDisponiblesNeo('modal-categoria-neo-total-urls', sumaTiendas);
            lista.innerHTML = '';
            const btnTodas = document.createElement('button');
            btnTodas.type = 'button';
            btnTodas.setAttribute('data-busqueda-texto', 'todas');
            btnTodas.className = 'w-full text-left p-3 rounded-lg border-2 border-pink-500 bg-pink-50 dark:bg-pink-900/30 dark:border-pink-400 hover:bg-pink-100 dark:hover:bg-pink-900/50 transition mb-2';
            btnTodas.innerHTML = '<span class="font-semibold text-pink-800 dark:text-pink-200">Todas</span> <span class="text-pink-700 dark:text-pink-300 text-sm">(' + sumaTiendas + ' URL' + (sumaTiendas !== 1 ? 's' : '') + ')</span>';
            btnTodas.addEventListener('click', async function() {
                try {
                    const resUrl = await fetch('{{ route("admin.neo.crear-masivo.urls-por-categoria", ["categoriaId" => "__CID__"]) }}'.replace('__CID__', item.categoria_id) + '?' + fq + '&_=' + Date.now(), { cache: 'no-store', headers: { 'Accept': 'application/json' } });
                    const dataUrl = await resUrl.json();
                    window.__neoCategoriaFiltro = { id: item.categoria_id, nombre: item.nombre || ('Categoría #' + item.categoria_id) };
                    actualizarIndicadorCategoriaFiltroNeo();
                    pegarUrlsEnTextareaCrearMasivo(dataUrl.urls || []);
                    window.__crearMasivoOrigenNeo = null;
                    actualizarVisibilidadFooterSiguienteNeo();
                    modal.classList.add('hidden');
                } catch (err) {
                    console.error(err);
                    alert('Error al cargar URLs: ' + err.message);
                }
            });
            lista.appendChild(btnTodas);
            estadoModalCategoriaNeo.tiendasDeCategoria.forEach(function(tienda) {
                const div = document.createElement('div');
                div.className = 'p-3 rounded-lg border border-gray-200 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer transition';
                const etiqueta = tienda.nombre || ('Tienda #' + tienda.tienda_id);
                div.setAttribute('data-busqueda-texto', etiqueta);
                div.innerHTML = '<span class="font-medium text-gray-900 dark:text-white">' + etiqueta + '</span>' + htmlEtiquetasTiendaRestringidaNeo(tienda) + ' <span class="text-gray-500 dark:text-gray-400 text-sm">(' + tienda.count + ' URL' + (tienda.count !== 1 ? 's' : '') + ')</span>';
                div.addEventListener('click', async function() {
                    try {
                        const resUrl = await fetch('{{ route("admin.neo.crear-masivo.urls-por-categoria-tienda", ["categoriaId" => "__CID__", "tiendaId" => "__TID__"]) }}'.replace('__CID__', item.categoria_id).replace('__TID__', tienda.tienda_id) + '?' + fq + '&_=' + Date.now(), { cache: 'no-store', headers: { 'Accept': 'application/json' } });
                        const dataUrl = await resUrl.json();
                        window.__neoCategoriaFiltro = { id: item.categoria_id, nombre: item.nombre || ('Categoría #' + item.categoria_id) };
                        actualizarIndicadorCategoriaFiltroNeo();
                        pegarUrlsEnTextareaCrearMasivo(dataUrl.urls || []);
                        window.__crearMasivoOrigenNeo = null;
                        actualizarVisibilidadFooterSiguienteNeo();
                        modal.classList.add('hidden');
                    } catch (err) {
                        console.error(err);
                        alert('Error al cargar URLs: ' + err.message);
                    }
                });
                lista.appendChild(div);
            });
            refrescarFiltroBuscadorModalNeo('modal-categoria-neo');
        } catch (err) {
            console.error(err);
            lista.innerHTML = '<p class="text-red-500 text-sm">Error al cargar tiendas: ' + err.message + '</p>';
        }
    }

    document.getElementById('btnCategoriaNeo').addEventListener('click', async function() {
        const btn = this;
        window.__crearMasivoOrigenNeo = null;
        actualizarVisibilidadFooterSiguienteNeo();
        const modal = document.getElementById('modal-categoria-neo');
        const lista = document.getElementById('modal-categoria-neo-lista');
        const chkCatSiOpen = document.getElementById('modal-categoria-neo-chk-cat-mostrar-si');
        const chkCatNoOpen = document.getElementById('modal-categoria-neo-chk-cat-mostrar-no');
        const chkSiOpen = document.getElementById('modal-categoria-neo-chk-mostrar-si');
        const chkNoOpen = document.getElementById('modal-categoria-neo-chk-mostrar-no');
        const tiendasLista = document.getElementById('modal-categoria-neo-tiendas-no-lista');
        if (tiendasLista) {
            tiendasLista.innerHTML = '';
            delete tiendasLista.dataset.loaded;
        }
        const panelTiendasNo = document.getElementById('modal-categoria-neo-panel-tiendas-no');
        const detTiendas = document.getElementById('modal-categoria-neo-details-tiendas-no-lista');
        const masterTiendasNo = document.getElementById('modal-categoria-neo-chk-tiendas-no-todas');
        if (panelTiendasNo) panelTiendasNo.classList.add('hidden');
        if (detTiendas) detTiendas.open = false;
        if (masterTiendasNo) masterTiendasNo.checked = true;
        if (chkCatSiOpen) chkCatSiOpen.checked = true;
        if (chkCatNoOpen) chkCatNoOpen.checked = false;
        if (chkSiOpen) chkSiOpen.checked = true;
        if (chkNoOpen) chkNoOpen.checked = false;
        actualizarFiltrosVisiblesModalCategoriaNeo();
        ocultarBuscadorModalNeo('modal-categoria-neo');
        btn.disabled = true;
        estadoModalCategoriaNeo.categoriaSeleccionada = null;
        actualizarHeaderModalCategoriaNeo();
        lista.innerHTML = '<p class="text-gray-500 dark:text-gray-400 text-sm">Cargando...</p>';
        modal.classList.remove('hidden');
        try {
            await recargarListaModalCategoriaNeo();
        } catch (err) {
            console.error(err);
            lista.innerHTML = '<p class="text-red-500 text-sm">Error al cargar: ' + err.message + '</p>';
            const totalEl = document.getElementById('modal-categoria-neo-total-urls');
            if (totalEl) totalEl.textContent = '\u2014';
        }
        btn.disabled = false;
    });

    ['modal-categoria-neo-chk-cat-mostrar-si', 'modal-categoria-neo-chk-cat-mostrar-no'].forEach(function(id) {
        const el = document.getElementById(id);
        if (!el) return;
        el.addEventListener('change', async function(ev) {
            if (document.getElementById('modal-categoria-neo').classList.contains('hidden')) return;
            if (estadoModalCategoriaNeo.categoriaSeleccionada) return;
            ajustarChecksMostrarCategoriaModalCategoriaNeo(ev);
            try {
                await recargarListaModalCategoriaNeo();
            } catch (err) {
                console.error(err);
                document.getElementById('modal-categoria-neo-lista').innerHTML = '<p class="text-red-500 text-sm">Error al cargar: ' + err.message + '</p>';
            }
        });
    });
    ['modal-categoria-neo-chk-mostrar-si', 'modal-categoria-neo-chk-mostrar-no'].forEach(function(id) {
        const el = document.getElementById(id);
        if (!el) return;
        el.addEventListener('change', async function(ev) {
            if (document.getElementById('modal-categoria-neo').classList.contains('hidden')) return;
            if (!estadoModalCategoriaNeo.categoriaSeleccionada) return;
            ajustarChecksMostrarTiendaModalCategoriaNeo(ev);
            try {
                await recargarContenidoModalCategoriaNeo({
                    reiniciarSeleccion: esSoloMostrarNoModalCategoriaNeo(),
                    mantenerDesplegableAbierto: esSoloMostrarNoModalCategoriaNeo(),
                });
            } catch (err) {
                console.error(err);
                document.getElementById('modal-categoria-neo-lista').innerHTML = '<p class="text-red-500 text-sm">Error al cargar: ' + err.message + '</p>';
            }
        });
    });
    document.getElementById('modal-categoria-neo-atras').addEventListener('click', function() {
        estadoModalCategoriaNeo.categoriaSeleccionada = null;
        actualizarHeaderModalCategoriaNeo();
        recargarListaModalCategoriaNeo();
    });
    document.getElementById('modal-categoria-neo-cerrar').addEventListener('click', function() {
        document.getElementById('modal-categoria-neo').classList.add('hidden');
    });
    document.getElementById('modal-categoria-neo-cerrar-btn').addEventListener('click', function() {
        document.getElementById('modal-categoria-neo').classList.add('hidden');
    });

    const estadoModalTiendaNeo = {
        tiendas: [],
        tiendaSeleccionada: null,
    };

    function parseRespuestaTiendasNeoApi(raw) {
        if (Array.isArray(raw)) {
            return { tiendas: raw, filas_neo_categoria_id_null: 0 };
        }
        return {
            tiendas: Array.isArray(raw.tiendas) ? raw.tiendas : [],
            filas_neo_categoria_id_null: parseInt(String(raw.filas_neo_categoria_id_null ?? 0), 10) || 0,
        };
    }

    function queryMostrarTiendaModalTiendaNeo() {
        const chkSi = document.getElementById('modal-tienda-neo-chk-mostrar-si');
        const chkNo = document.getElementById('modal-tienda-neo-chk-mostrar-no');
        const chkNull = document.getElementById('modal-tienda-neo-chk-tienda-null');
        const si = chkSi && chkSi.checked;
        const no = chkNo && chkNo.checked;
        const incluirNull = chkNull && chkNull.checked;
        let q = 'mostrar_si=' + (si ? '1' : '0') + '&mostrar_no=' + (no ? '1' : '0');
        if (incluirNull) {
            q += '&mostrar_null=1';
        }
        return q;
    }

    function queryMostrarCategoriaModalTiendaNeo() {
        const chkSi = document.getElementById('modal-tienda-neo-chk-cat-mostrar-si');
        const chkNo = document.getElementById('modal-tienda-neo-chk-cat-mostrar-no');
        const chkNull = document.getElementById('modal-tienda-neo-chk-categoria-null');
        const si = chkSi && chkSi.checked;
        const no = chkNo && chkNo.checked;
        const incluirNull = chkNull && chkNull.checked;
        let q = 'categoria_mostrar_si=' + (si ? '1' : '0') + '&categoria_mostrar_no=' + (no ? '1' : '0');
        if (incluirNull) {
            q += '&categoria_mostrar_null=1';
        }
        return q;
    }

    function ajustarChecksMostrarCategoriaModalTiendaNeo(ev) {
        const t = ev && ev.target ? ev.target.id : '';
        const si = document.getElementById('modal-tienda-neo-chk-cat-mostrar-si');
        const no = document.getElementById('modal-tienda-neo-chk-cat-mostrar-no');
        if (!si || !no || t !== 'modal-tienda-neo-chk-cat-mostrar-no') {
            return;
        }
        if (no.checked && si.checked) {
            si.checked = false;
        }
    }

    function ajustarChecksMostrarTiendaModalTiendaNeo(ev) {
        const t = ev && ev.target ? ev.target.id : '';
        const si = document.getElementById('modal-tienda-neo-chk-mostrar-si');
        const no = document.getElementById('modal-tienda-neo-chk-mostrar-no');
        if (!si || !no || t !== 'modal-tienda-neo-chk-mostrar-no') {
            return;
        }
        if (no.checked && si.checked) {
            si.checked = false;
        }
    }

    function esSoloMostrarNoModalTiendaNeo() {
        const si = document.getElementById('modal-tienda-neo-chk-mostrar-si');
        const no = document.getElementById('modal-tienda-neo-chk-mostrar-no');
        return !!(si && no && !si.checked && no.checked);
    }

    function queryTiendasNoSeleccionadasModalTiendaNeo() {
        if (!esSoloMostrarNoModalTiendaNeo()) {
            return '';
        }
        const container = document.getElementById('modal-tienda-neo-tiendas-no-lista');
        if (!container || container.dataset.loaded !== '1') {
            return '';
        }
        const checks = container.querySelectorAll('input[type="checkbox"].modal-tienda-neo-tienda-no-cb');
        const parts = [];
        checks.forEach(function(c) {
            if (c.checked) {
                parts.push('tienda_ids[]=' + encodeURIComponent(c.value));
            }
        });
        return parts.length ? '&' + parts.join('&') : '';
    }

    function queryModalTiendaNeoFiltros() {
        const parts = [
            queryMostrarTiendaModalTiendaNeo(),
            queryMostrarCategoriaModalTiendaNeo(),
            queryTiendasNoSeleccionadasModalTiendaNeo().replace(/^&/, ''),
        ].filter(Boolean);
        return parts.join('&');
    }

    function registrarMasterTiendasMostrarNoModalTienda() {
        const master = document.getElementById('modal-tienda-neo-chk-tiendas-no-todas');
        const container = document.getElementById('modal-tienda-neo-tiendas-no-lista');
        if (!master || master.__listenerMasterTiendasNo) return;
        master.__listenerMasterTiendasNo = true;
        master.addEventListener('change', function() {
            const marcar = master.checked;
            if (container) {
                container.querySelectorAll('input.modal-tienda-neo-tienda-no-cb').forEach(function(c) {
                    c.checked = marcar;
                });
            }
            recargarContenidoModalTiendaNeo({ mantenerDesplegableAbierto: true });
        });
    }

    async function cargarOpcionesTiendasMostrarNoModalTienda() {
        const container = document.getElementById('modal-tienda-neo-tiendas-no-lista');
        if (!container || container.dataset.loaded === '1') {
            return;
        }
        container.innerHTML = '<p class="text-xs text-gray-500 dark:text-gray-400">Cargando tiendas...</p>';
        let lista;
        try {
            const res = await fetch('{{ route("admin.neo.crear-masivo.tiendas-mostrar-no") }}' + '?_=' + Date.now(), { cache: 'no-store', headers: { 'Accept': 'application/json' } });
            lista = await res.json();
        } catch (e) {
            console.error(e);
            container.innerHTML = '<p class="text-xs text-red-500">Error al cargar tiendas.</p>';
            return;
        }
        container.innerHTML = '';
        if (!Array.isArray(lista) || lista.length === 0) {
            container.innerHTML = '<p class="text-xs text-gray-500 dark:text-gray-400">No hay tiendas con mostrar «no».</p>';
            container.dataset.loaded = '1';
            return;
        }
        lista.forEach(function(t) {
            const label = document.createElement('label');
            label.className = 'flex items-center gap-2 cursor-pointer text-gray-800 dark:text-gray-200';
            const cb = document.createElement('input');
            cb.type = 'checkbox';
            cb.value = String(t.id);
            cb.checked = true;
            cb.className = 'rounded border-gray-300 text-emerald-600 focus:ring-emerald-500 modal-tienda-neo-tienda-no-cb';
            cb.addEventListener('change', function(ev) {
                ev.stopPropagation();
                const master = document.getElementById('modal-tienda-neo-chk-tiendas-no-todas');
                const checks = document.querySelectorAll('#modal-tienda-neo-tiendas-no-lista input.modal-tienda-neo-tienda-no-cb');
                if (master && checks.length) {
                    master.checked = Array.from(checks).every(function(c) { return c.checked; });
                }
                recargarContenidoModalTiendaNeo({ mantenerDesplegableAbierto: true });
            });
            label.addEventListener('click', function(ev) { ev.stopPropagation(); });
            const span = document.createElement('span');
            span.textContent = t.nombre || ('Tienda #' + t.id);
            label.appendChild(cb);
            label.appendChild(span);
            container.appendChild(label);
        });
        container.dataset.loaded = '1';
        registrarMasterTiendasMostrarNoModalTienda();
    }

    async function actualizarVisibilidadPanelTiendasNoModalTienda(opciones) {
        opciones = opciones || {};
        const reiniciarSeleccion = opciones.reiniciarSeleccion === true;
        const mantenerDesplegableAbierto = opciones.mantenerDesplegableAbierto === true;
        const panel = document.getElementById('modal-tienda-neo-panel-tiendas-no');
        const detailsLista = document.getElementById('modal-tienda-neo-details-tiendas-no-lista');
        if (!panel) {
            return;
        }
        const soloNo = esSoloMostrarNoModalTiendaNeo();
        panel.classList.toggle('hidden', !soloNo);
        if (soloNo) {
            const master = document.getElementById('modal-tienda-neo-chk-tiendas-no-todas');
            const container = document.getElementById('modal-tienda-neo-tiendas-no-lista');
            if (reiniciarSeleccion && master) {
                master.checked = true;
                if (container) {
                    container.querySelectorAll('input.modal-tienda-neo-tienda-no-cb').forEach(function(c) {
                        c.checked = true;
                    });
                }
            }
            if (detailsLista && !mantenerDesplegableAbierto) {
                detailsLista.open = false;
            }
            await cargarOpcionesTiendasMostrarNoModalTienda();
            registrarMasterTiendasMostrarNoModalTienda();
        } else if (detailsLista) {
            detailsLista.open = false;
        }
    }

    async function recargarListaTiendasModalTiendaNeo(opciones) {
        await actualizarVisibilidadPanelTiendasNoModalTienda(opciones);
        const lista = document.getElementById('modal-tienda-neo-lista');
        lista.innerHTML = '<p class="text-gray-500 dark:text-gray-400 text-sm">Cargando...</p>';
        setTotalUrlsDisponiblesNeo('modal-tienda-neo-total-urls', '\u2014');
        const urlListado = '{{ route("admin.neo.crear-masivo.tiendas") }}' + '?' + queryModalTiendaNeoFiltros() + '&_=' + Date.now();
        const res = await fetch(urlListado, { cache: 'no-store', headers: { 'Accept': 'application/json' } });
        const parsed = parseRespuestaTiendasNeoApi(await res.json());
        estadoModalTiendaNeo.tiendas = parsed.tiendas;
        const catNuloCountEl = document.getElementById('modal-tienda-neo-categoria-null-count');
        if (catNuloCountEl) {
            catNuloCountEl.textContent = String(parsed.filas_neo_categoria_id_null);
        }
        renderTiendasModalNeo();
    }

    async function recargarContenidoModalTiendaNeo(opciones) {
        if (estadoModalTiendaNeo.tiendaSeleccionada) {
            await renderCategoriasDeTiendaModalNeo(estadoModalTiendaNeo.tiendaSeleccionada);
        } else {
            await recargarListaTiendasModalTiendaNeo(opciones);
        }
    }

    function pegarUrlsEnTextareaCrearMasivo(urls) {
        document.getElementById('urls').value = (Array.isArray(urls) ? urls : []).join('\n');
        actualizarEstadoLotesDesdeTextarea();
    }

    const btnLimpiarNeoCategoriaFiltro = document.getElementById('neo-categoria-filtro-limpiar');
    if (btnLimpiarNeoCategoriaFiltro) {
        btnLimpiarNeoCategoriaFiltro.addEventListener('click', function() {
            window.__neoCategoriaFiltro = null;
            actualizarIndicadorCategoriaFiltroNeo();
        });
    }

    function actualizarHeaderModalTiendaNeo() {
        const titulo = document.getElementById('modal-tienda-neo-titulo');
        const btnAtras = document.getElementById('modal-tienda-neo-atras');
        const enSubnivel = !!estadoModalTiendaNeo.tiendaSeleccionada;
        setBuscadorModalNeoDisponible('modal-tienda-neo', !enSubnivel);
        if (enSubnivel) {
            if (titulo) titulo.textContent = 'Elegir categoría de ' + (estadoModalTiendaNeo.tiendaSeleccionada.nombre || ('Tienda #' + estadoModalTiendaNeo.tiendaSeleccionada.tienda_id));
            if (btnAtras) btnAtras.classList.remove('hidden');
        } else {
            if (titulo) titulo.textContent = 'Elegir tienda (neo con añadida=no)';
            if (btnAtras) btnAtras.classList.add('hidden');
        }
    }

    function renderTiendasModalNeo() {
        const lista = document.getElementById('modal-tienda-neo-lista');
        const tiendas = estadoModalTiendaNeo.tiendas || [];
        estadoModalTiendaNeo.tiendaSeleccionada = null;
        actualizarHeaderModalTiendaNeo();
        if (!Array.isArray(tiendas) || tiendas.length === 0) {
            lista.innerHTML = '<p class="text-gray-500 dark:text-gray-400 text-sm">No hay tiendas con filas neo (añadida=no).</p>';
            setTotalUrlsDisponiblesNeo('modal-tienda-neo-total-urls', 0);
            return;
        }
        setTotalUrlsDisponiblesNeo('modal-tienda-neo-total-urls', sumarConteosListaNeo(tiendas, 'count'));
        lista.innerHTML = '';
        tiendas.forEach(function(item) {
            const div = document.createElement('div');
            div.className = 'p-3 rounded-lg border border-gray-200 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer transition';
            const etiqueta = item.nombre || ('Tienda #' + item.tienda_id);
            div.setAttribute('data-busqueda-texto', etiqueta);
            div.innerHTML = '<span class="font-medium text-gray-900 dark:text-white">' + etiqueta + '</span>' + htmlEtiquetasTiendaRestringidaNeo(item) + ' <span class="text-gray-500 dark:text-gray-400 text-sm">(' + item.count + ' URL' + (item.count !== 1 ? 's' : '') + ')</span>';
            div.addEventListener('click', async function() {
                await renderCategoriasDeTiendaModalNeo(item);
            });
            lista.appendChild(div);
        });
        refrescarFiltroBuscadorModalNeo('modal-tienda-neo');
    }

    /**
     * Pega URLs desde neo por tienda (opcionalmente por categoría) y guarda contexto para Siguiente.
     */
    async function aplicarTiendaNeoUrlsAlFormulario(item, categoriaId, categoriaNombre) {
        const fq = queryModalTiendaNeoFiltros();
        const routeBase = categoriaId == null
            ? '{{ route("admin.neo.crear-masivo.urls-por-tienda", ["tiendaId" => "__TID__"]) }}'.replace('__TID__', item.tienda_id)
            : '{{ route("admin.neo.crear-masivo.urls-por-tienda-categoria", ["tiendaId" => "__TID__", "categoriaId" => "__CID__"]) }}'.replace('__TID__', item.tienda_id).replace('__CID__', categoriaId);
        const resUrl = await fetch(routeBase + '?' + (fq ? fq + '&' : '') + '_=' + Date.now(), { cache: 'no-store', headers: { 'Accept': 'application/json' } });
        const dataUrl = await resUrl.json();
        if (categoriaId == null) {
            window.__neoCategoriaFiltro = null;
        } else {
            window.__neoCategoriaFiltro = {
                id: categoriaId,
                nombre: (categoriaNombre && String(categoriaNombre).trim()) ? categoriaNombre : ('Categoría #' + categoriaId),
            };
        }
        actualizarIndicadorCategoriaFiltroNeo();
        pegarUrlsEnTextareaCrearMasivo(dataUrl.urls || []);
        window.__crearMasivoOrigenNeo = {
            tipo: 'tienda',
            tienda_id: item.tienda_id,
            categoria_id: categoriaId == null ? null : categoriaId,
            categoria_nombre: categoriaId == null ? null : ((categoriaNombre && String(categoriaNombre).trim()) ? categoriaNombre : ('Categoría #' + categoriaId)),
        };
    }

    async function cargarUrlsDeTiendaYCerrarModal(item, categoriaId, categoriaNombre) {
        const modal = document.getElementById('modal-tienda-neo');
        try {
            await aplicarTiendaNeoUrlsAlFormulario(item, categoriaId, categoriaNombre);
            modal.classList.add('hidden');
        } catch (err) {
            console.error(err);
            alert('Error al cargar URLs: ' + err.message);
        }
    }

    async function siguienteTiendaNeoCrearMasivo() {
        const ctx = window.__crearMasivoOrigenNeo;
        if (!ctx || ctx.tipo !== 'tienda') {
            return;
        }
        const fq = typeof queryModalTiendaNeoFiltros === 'function' ? queryModalTiendaNeoFiltros() : '';
        const urlTiendas = '{{ route("admin.neo.crear-masivo.tiendas") }}' + '?' + (fq ? fq + '&' : '') + '_=' + Date.now();
        const resTiendas = await fetch(urlTiendas, { cache: 'no-store', headers: { 'Accept': 'application/json' } });
        const parsedTiendas = typeof parseRespuestaTiendasNeoApi === 'function'
            ? parseRespuestaTiendasNeoApi(await resTiendas.json())
            : { tiendas: await resTiendas.json() };
        const tiendas = parsedTiendas.tiendas;
        if (!Array.isArray(tiendas) || tiendas.length === 0) {
            alert('No quedan tiendas pendientes.');
            return;
        }
        const tidCur = parseInt(String(ctx.tienda_id), 10);
        const tidIndex = tiendas.findIndex(function(t) { return parseInt(String(t.tienda_id), 10) === tidCur; });
        const curTienda = tidIndex >= 0 ? tiendas[tidIndex] : null;
        const preferredCat = ctx.categoria_id != null ? parseInt(String(ctx.categoria_id), 10) : null;

        if (preferredCat != null && curTienda) {
            const resCat = await fetch('{{ route("admin.neo.crear-masivo.categorias-por-tienda", ["tiendaId" => "__ID__"]) }}'.replace('__ID__', curTienda.tienda_id) + '?' + (fq ? fq + '&' : '') + '_=' + Date.now(), { cache: 'no-store', headers: { 'Accept': 'application/json' } });
            const rawCats = await resCat.json();
            const cats = Array.isArray(rawCats) ? rawCats : [];
            const ci = cats.findIndex(function(c) { return parseInt(String(c.categoria_id), 10) === preferredCat; });
            if (ci >= 0 && ci < cats.length - 1) {
                const nextCat = cats[ci + 1];
                await aplicarTiendaNeoUrlsAlFormulario(curTienda, nextCat.categoria_id, nextCat.nombre);
                return;
            }
        }

        let nextIdx = tidIndex + 1;
        if (tidIndex < 0) {
            nextIdx = 0;
        }
        if (nextIdx >= tiendas.length) {
            alert('No hay más tiendas ni categorías pendientes en este recorrido.');
            return;
        }
        const nextTienda = tiendas[nextIdx];

        if (preferredCat == null) {
            await aplicarTiendaNeoUrlsAlFormulario(nextTienda, null, null);
            return;
        }

        const resCat2 = await fetch('{{ route("admin.neo.crear-masivo.categorias-por-tienda", ["tiendaId" => "__ID__"]) }}'.replace('__ID__', nextTienda.tienda_id) + '?' + (fq ? fq + '&' : '') + '_=' + Date.now(), { cache: 'no-store', headers: { 'Accept': 'application/json' } });
        const rawCats2 = await resCat2.json();
        const cats2 = Array.isArray(rawCats2) ? rawCats2 : [];
        if (cats2.length > 0) {
            const match = cats2.find(function(c) { return parseInt(String(c.categoria_id), 10) === preferredCat; });
            const pick = match || cats2[0];
            await aplicarTiendaNeoUrlsAlFormulario(nextTienda, pick.categoria_id, pick.nombre);
        } else {
            await aplicarTiendaNeoUrlsAlFormulario(nextTienda, null, null);
        }
    }

    async function renderCategoriasDeTiendaModalNeo(item) {
        const lista = document.getElementById('modal-tienda-neo-lista');
        estadoModalTiendaNeo.tiendaSeleccionada = item;
        actualizarHeaderModalTiendaNeo();
        lista.innerHTML = '<p class="text-gray-500 dark:text-gray-400 text-sm">Cargando categorías...</p>';
        setTotalUrlsDisponiblesNeo('modal-tienda-neo-total-urls', '…');
        try {
            const fq = queryModalTiendaNeoFiltros();
            const res = await fetch('{{ route("admin.neo.crear-masivo.categorias-por-tienda", ["tiendaId" => "__ID__"]) }}'.replace('__ID__', item.tienda_id) + '?' + fq + '&_=' + Date.now(), { cache: 'no-store', headers: { 'Accept': 'application/json' } });
            const categorias = await res.json();
            const cats = Array.isArray(categorias) ? categorias : [];
            const sumaCats = sumarConteosListaNeo(cats, 'count');
            const totalTienda = parseInt(String(item.count), 10) || sumaCats;
            setTotalUrlsDisponiblesNeo('modal-tienda-neo-total-urls', totalTienda);
            lista.innerHTML = '';

            const todasDiv = document.createElement('div');
            todasDiv.className = 'p-3 rounded-lg border border-blue-300 dark:border-blue-600 bg-blue-50 dark:bg-blue-900/20 hover:bg-blue-100 dark:hover:bg-blue-900/30 cursor-pointer transition';
            todasDiv.setAttribute('data-busqueda-texto', 'todas');
            todasDiv.innerHTML = '<span class="font-semibold text-blue-800 dark:text-blue-300">Todas</span> <span class="text-blue-700 dark:text-blue-400 text-sm">(' + totalTienda + ' URL' + (totalTienda !== 1 ? 's' : '') + ')</span>';
            todasDiv.addEventListener('click', async function() {
                await cargarUrlsDeTiendaYCerrarModal(item, null);
            });
            lista.appendChild(todasDiv);

            if (cats.length === 0) {
                const vacio = document.createElement('p');
                vacio.className = 'text-gray-500 dark:text-gray-400 text-sm';
                vacio.textContent = 'No hay categorías pendientes para esta tienda.';
                lista.appendChild(vacio);
                return;
            }

            cats.forEach(function(cat) {
                const div = document.createElement('div');
                div.className = 'p-3 rounded-lg border border-gray-200 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer transition';
                const etiqueta = cat.nombre || ('Categoría #' + cat.categoria_id);
                div.setAttribute('data-busqueda-texto', etiqueta);
                div.innerHTML = '<span class="font-medium text-gray-900 dark:text-white">' + etiqueta + '</span> <span class="text-gray-500 dark:text-gray-400 text-sm">(' + cat.count + ' URL' + (cat.count !== 1 ? 's' : '') + ')</span>';
                div.addEventListener('click', async function() {
                    await cargarUrlsDeTiendaYCerrarModal(item, cat.categoria_id, cat.nombre);
                });
                lista.appendChild(div);
            });
        } catch (err) {
            console.error(err);
            lista.innerHTML = '<p class="text-red-500 text-sm">Error al cargar categorías: ' + err.message + '</p>';
        }
    }

    document.getElementById('btnTiendaNeo').addEventListener('click', async function() {
        const btn = this;
        const modal = document.getElementById('modal-tienda-neo');
        const chkSiOpen = document.getElementById('modal-tienda-neo-chk-mostrar-si');
        const chkNoOpen = document.getElementById('modal-tienda-neo-chk-mostrar-no');
        const tiendasLista = document.getElementById('modal-tienda-neo-tiendas-no-lista');
        if (tiendasLista) {
            tiendasLista.innerHTML = '';
            delete tiendasLista.dataset.loaded;
        }
        const panelTiendasNo = document.getElementById('modal-tienda-neo-panel-tiendas-no');
        const detTiendas = document.getElementById('modal-tienda-neo-details-tiendas-no-lista');
        const masterTiendasNo = document.getElementById('modal-tienda-neo-chk-tiendas-no-todas');
        if (panelTiendasNo) panelTiendasNo.classList.add('hidden');
        if (detTiendas) detTiendas.open = false;
        if (masterTiendasNo) masterTiendasNo.checked = true;
        if (chkSiOpen) chkSiOpen.checked = true;
        if (chkNoOpen) chkNoOpen.checked = false;
        const chkNullOpen = document.getElementById('modal-tienda-neo-chk-tienda-null');
        if (chkNullOpen) chkNullOpen.checked = false;
        const chkCatSiOpen = document.getElementById('modal-tienda-neo-chk-cat-mostrar-si');
        const chkCatNoOpen = document.getElementById('modal-tienda-neo-chk-cat-mostrar-no');
        const chkCatNullOpen = document.getElementById('modal-tienda-neo-chk-categoria-null');
        if (chkCatSiOpen) chkCatSiOpen.checked = true;
        if (chkCatNoOpen) chkCatNoOpen.checked = false;
        if (chkCatNullOpen) chkCatNullOpen.checked = false;
        ocultarBuscadorModalNeo('modal-tienda-neo');
        btn.disabled = true;
        estadoModalTiendaNeo.tiendaSeleccionada = null;
        actualizarHeaderModalTiendaNeo();
        modal.classList.remove('hidden');
        try {
            await recargarListaTiendasModalTiendaNeo();
        } catch (err) {
            console.error(err);
            document.getElementById('modal-tienda-neo-lista').innerHTML = '<p class="text-red-500 text-sm">Error al cargar: ' + err.message + '</p>';
            setTotalUrlsDisponiblesNeo('modal-tienda-neo-total-urls', '\u2014');
        }
        btn.disabled = false;
    });
    ['modal-tienda-neo-chk-mostrar-si', 'modal-tienda-neo-chk-mostrar-no', 'modal-tienda-neo-chk-tienda-null'].forEach(function(id) {
        const el = document.getElementById(id);
        if (!el) return;
        el.addEventListener('change', async function(ev) {
            if (document.getElementById('modal-tienda-neo').classList.contains('hidden')) return;
            if (id === 'modal-tienda-neo-chk-tienda-null' && el.checked) {
                const si = document.getElementById('modal-tienda-neo-chk-mostrar-si');
                const no = document.getElementById('modal-tienda-neo-chk-mostrar-no');
                if (si) si.checked = false;
                if (no) no.checked = false;
            } else if ((id === 'modal-tienda-neo-chk-mostrar-si' || id === 'modal-tienda-neo-chk-mostrar-no') && el.checked) {
                const nulo = document.getElementById('modal-tienda-neo-chk-tienda-null');
                if (nulo) nulo.checked = false;
            }
            ajustarChecksMostrarTiendaModalTiendaNeo(ev);
            try {
                const soloNo = esSoloMostrarNoModalTiendaNeo();
                await recargarContenidoModalTiendaNeo({
                    reiniciarSeleccion: soloNo,
                    mantenerDesplegableAbierto: soloNo,
                });
            } catch (err) {
                console.error(err);
                document.getElementById('modal-tienda-neo-lista').innerHTML = '<p class="text-red-500 text-sm">Error al cargar: ' + err.message + '</p>';
            }
        });
    });
    ['modal-tienda-neo-chk-cat-mostrar-si', 'modal-tienda-neo-chk-cat-mostrar-no', 'modal-tienda-neo-chk-categoria-null'].forEach(function(id) {
        const el = document.getElementById(id);
        if (!el) return;
        el.addEventListener('change', async function(ev) {
            if (document.getElementById('modal-tienda-neo').classList.contains('hidden')) return;
            if (id === 'modal-tienda-neo-chk-categoria-null' && el.checked) {
                const si = document.getElementById('modal-tienda-neo-chk-cat-mostrar-si');
                const no = document.getElementById('modal-tienda-neo-chk-cat-mostrar-no');
                if (si) si.checked = false;
                if (no) no.checked = false;
            } else if ((id === 'modal-tienda-neo-chk-cat-mostrar-si' || id === 'modal-tienda-neo-chk-cat-mostrar-no') && el.checked) {
                const nulo = document.getElementById('modal-tienda-neo-chk-categoria-null');
                if (nulo) nulo.checked = false;
            }
            ajustarChecksMostrarCategoriaModalTiendaNeo(ev);
            try {
                const soloNo = esSoloMostrarNoModalTiendaNeo();
                await recargarContenidoModalTiendaNeo({
                    reiniciarSeleccion: soloNo,
                    mantenerDesplegableAbierto: soloNo,
                });
            } catch (err) {
                console.error(err);
                document.getElementById('modal-tienda-neo-lista').innerHTML = '<p class="text-red-500 text-sm">Error al cargar: ' + err.message + '</p>';
            }
        });
    });
    document.getElementById('modal-tienda-neo-atras').addEventListener('click', async function() {
        estadoModalTiendaNeo.tiendaSeleccionada = null;
        actualizarHeaderModalTiendaNeo();
        try {
            await recargarListaTiendasModalTiendaNeo();
        } catch (err) {
            console.error(err);
            document.getElementById('modal-tienda-neo-lista').innerHTML = '<p class="text-red-500 text-sm">Error al recargar tiendas: ' + err.message + '</p>';
        }
    });
    document.getElementById('modal-tienda-neo-cerrar').addEventListener('click', function() {
        document.getElementById('modal-tienda-neo').classList.add('hidden');
    });
    document.getElementById('modal-tienda-neo-cerrar-btn').addEventListener('click', function() {
        document.getElementById('modal-tienda-neo').classList.add('hidden');
    });

    const btnSiguienteOrigenNeo = document.getElementById('btnSiguienteOrigenNeo');
    if (btnSiguienteOrigenNeo) {
        btnSiguienteOrigenNeo.addEventListener('click', function() {
            ejecutarSiguienteOrigenNeoCrearMasivo();
        });
    }

    document.getElementById('usar_chatgpt').addEventListener('change', function() {
        const opciones = document.getElementById('chatgpt_opciones');
        if (this.checked) {
            opciones.classList.remove('hidden');
        } else {
            opciones.classList.add('hidden');
        }
    });
    document.getElementById('urls').addEventListener('input', actualizarEstadoLotesDesdeTextarea);
    document.getElementById('btnRepetirMismoLote').addEventListener('click', function() {
        ejecutarAnalisisLote(true);
    });
    document.getElementById('formAnalizar').addEventListener('submit', async function(e) {
        e.preventDefault();
        ejecutarAnalisisLote(false);
    });

    async function ejecutarAnalisisLote(repetirUltimo) {
        actualizarEstadoLotesDesdeTextarea();
        const lote = obtenerLoteSegunCantidad(repetirUltimo);
        if (!lote || !Array.isArray(lote.urls) || lote.urls.length === 0) {
            alert(repetirUltimo ? 'No hay un lote previo para repetir.' : 'No quedan URLs pendientes por analizar.');
            return false;
        }
        const btn = document.getElementById('btnAnalizar');
        btn.disabled = true;
        const btnRepetir = document.getElementById('btnRepetirMismoLote');
        if (btnRepetir) btnRepetir.disabled = true;
        const textoNormal = document.getElementById('btnAnalizarTexto') ? document.getElementById('btnAnalizarTexto').textContent : 'Analizar URLs';
        btn.innerHTML = '<svg class="w-5 h-5 mr-2 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg> Analizando...';
        prepararNuevaPestanaUrlCrearMasivo();

        try {
            const res = await fetch('{{ route("admin.ofertas.crear-masivo.analizar") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
                body: JSON.stringify((function() {
                    const payload = {
                        urls: lote.urls.join('\n'),
                        usar_chatgpt: document.getElementById('usar_chatgpt').checked,
                        incluir_contenido_pagina: document.getElementById('incluir_contenido_pagina').checked,
                        no_productos_sugeridos: document.getElementById('no_productos_sugeridos').checked,
                        chatgpt_model: document.getElementById('usar_chatgpt').checked && document.getElementById('chatgpt_model').value ? document.getElementById('chatgpt_model').value : null,
                    };
                    if (window.__neoCategoriaFiltro && window.__neoCategoriaFiltro.id) {
                        payload.categoria_id = window.__neoCategoriaFiltro.id;
                    }
                    if (document.getElementById('mismo_producto').checked && window.__mismoProductoSeleccionado && window.__mismoProductoSeleccionado.id) {
                        payload.producto_id = window.__mismoProductoSeleccionado.id;
                        const specContainer = document.getElementById('mismo-producto-spec-container');
                        if (specContainer) {
                            const especs = buildEspecificacionesFromRow(specContainer);
                            if (especs) payload.especificaciones_internas = JSON.stringify(especs);
                        }
                    }
                    return payload;
                })()),
            });
            const data = await res.json();

            // Diagnóstico: por qué cada URL se envía o no a ChatGPT (solo si ChatGPT estaba activado)
            if (data.success && document.getElementById('usar_chatgpt').checked && Array.isArray(data.resultados)) {
                console.log('[Crear-masivo] ChatGPT activado. Diagnóstico por URL:');
                let enviadas = 0;
                data.resultados.forEach(function(r, idx) {
                    const url = (r.url_normalizada || r.url || '').substring(0, 80) + (r.url && r.url.length > 80 ? '...' : '');
                    if (r.existe) {
                        console.log('[Crear-masivo] URL ' + idx + ': NO se envia a ChatGPT - ' + (r.descartada ? 'URL descartada' : 'ya existe oferta para esta URL'));
                    } else if (!r.tienda) {
                        console.log('[Crear-masivo] URL ' + idx + ': NO se envia a ChatGPT - tienda no detectada. URL: ' + url);
                    } else if (!r.productos_candidatos || r.productos_candidatos.length === 0) {
                        console.log('[Crear-masivo] URL ' + idx + ': NO se envia a ChatGPT - sin productos candidatos (revisar slug o coincidencias en BD). URL: ' + url);
                    } else {
                        console.log('[Crear-masivo] URL ' + idx + ': SÍ se envía a ChatGPT (' + r.productos_candidatos.length + ' candidatos). URL: ' + url);
                        enviadas++;
                    }
                });
                console.log('[Crear-masivo] Total URLs enviadas a ChatGPT: ' + enviadas + ' de ' + data.resultados.length);
            }

            if (data.chatgpt_raw_response) {
                if (data.chatgpt_raw_response.por_url) {
                    Object.keys(data.chatgpt_raw_response.por_url).forEach(function(idx) {
                        const u = data.chatgpt_raw_response.por_url[idx];
                        console.log('[Crear-masivo] URL índice ' + idx + ' — Prompt:', u.prompt);
                        console.log('[Crear-masivo] URL índice ' + idx + ' — Respuesta:', u.raw_content);
                        console.log('[Crear-masivo] URL índice ' + idx + ' — Parsed:', u.parsed_resultados);
                    });
                } else {
                    console.log('[Crear-masivo] Prompt enviado a ChatGPT:', data.chatgpt_raw_response.prompt);
                    console.log('[Crear-masivo] Respuesta ChatGPT (raw_content):', data.chatgpt_raw_response.raw_content);
                    console.log('[Crear-masivo] Respuesta ChatGPT (parsed_resultados):', data.chatgpt_raw_response.parsed_resultados);
                }
            }

            if (data.success) {
                if (window.__modoProductoTodasNeo && Array.isArray(data.resultados)) {
                    data.resultados = await enriquecerResultadosModoTodasConSpecs(data.resultados);
                }
                estadoLotesAnalisis.lastStart = lote.start;
                estadoLotesAnalisis.lastEnd = lote.end;
                if (!lote.repetir) estadoLotesAnalisis.cursor = lote.end;
                mostrarResultados(data.resultados);
                actualizarEstadoLotesDesdeTextarea();
                return true;
            }
            alert(data.message || 'Error al analizar');
            return false;
        } catch (err) {
            alert('Error de conexión: ' + err.message);
            return false;
        } finally {
            btn.disabled = false;
            if (btnRepetir) btnRepetir.disabled = false;
            btn.innerHTML = '<svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg> <span id="btnAnalizarTexto">' + textoNormal + '</span>';
            actualizarEstadoLotesDesdeTextarea();
        }
    }

    actualizarEstadoLotesDesdeTextarea();

    function htmlBotonesProductoFilaCrearMasivo(productoId) {
        const iconoImgComoEspecs = '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>';
        const quitarYImg = '<button type="button" class="btn-quitar-producto inline-flex items-center justify-center w-6 h-6 rounded bg-red-500 hover:bg-red-600 text-white text-xs font-bold transition" title="Quitar producto y elegir otro">&times;</button><button type="button" class="btn-ver-imagenes-producto-crear-masivo inline-flex items-center p-1 bg-blue-600 hover:bg-blue-700 text-white rounded text-xs ml-0.5" title="Ver imágenes del producto">' + iconoImgComoEspecs + '</button>';
        const pid = (productoId !== undefined && productoId !== null && productoId !== '') ? String(productoId) : '';
        if (!pid) {
            return quitarYImg;
        }
        const uEdit = NEO_PANEL_PRODUCTOS_BASE + '/' + encodeURIComponent(pid) + '/edit';
        const uOfertas = NEO_PANEL_PRODUCTOS_BASE + '/' + encodeURIComponent(pid) + '/ofertas';
        const btnEditar = '<a href="' + uEdit + '" target="_blank" rel="noopener noreferrer" class="inline-flex items-center px-2 py-1 ml-0.5 text-xs font-medium rounded bg-indigo-600 hover:bg-indigo-700 text-white transition" title="Editar producto en el panel">Editar</a>';
        const btnOfertas = '<a href="' + uOfertas + '" target="_blank" rel="noopener noreferrer" class="inline-flex items-center px-2 py-1 ml-0.5 text-xs font-medium rounded bg-teal-600 hover:bg-teal-700 text-white transition" title="Listado de ofertas del producto">Ofertas</a>';
        return quitarYImg + btnEditar + btnOfertas;
    }

    function escapeHtmlCrearMasivo(texto) {
        return String(texto || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    /** Texto para el input de envío (€) según envio_sugerido del análisis; vacío si gratis / sin dato (no 0). */
    function textoEnvioInputDesdeRowCrearMasivo(row) {
        if (!row || row.envio_sugerido == null || row.envio_sugerido === '') return '';
        const n = parseFloat(row.envio_sugerido);
        if (!isFinite(n) || n <= 0) return '';
        const rounded = Math.round(n * 100) / 100;
        let s = String(rounded);
        if (s.indexOf('.') !== -1) {
            s = s.replace(/(\.\d*?)0+$/, '$1').replace(/\.$/, '');
        }
        return s.replace('.', ',');
    }

    function aplicarEnvioInputAlBodyCrearMasivo(div, body) {
        const inp = div.querySelector('.cm-envio-oferta-input');
        if (!inp) return;
        const t = (inp.value || '').trim().replace(',', '.');
        if (t === '') {
            body.envio = null;
            return;
        }
        const v = parseFloat(t);
        if (isFinite(v) && v > 0) {
            body.envio = Math.round(v * 100) / 100;
        } else {
            body.envio = null;
        }
    }

    function actualizarValorEnvioInputEnFilaCrearMasivo(div) {
        const inp = div.querySelector('.cm-envio-oferta-input');
        if (!inp || !div.__rowData) return;
        inp.value = textoEnvioInputDesdeRowCrearMasivo(div.__rowData);
    }

    function ponerMensajeGeneradoPendienteCrearMasivo(div) {
        if (!div || div.__rowData?.ofertaGenerada) return;
        const msg = div.querySelector('.generado-msg');
        if (!msg) return;
        msg.classList.remove('hidden');
        msg.className = 'mt-2 generado-msg text-sm font-medium text-gray-600 dark:text-gray-400';
        msg.textContent = 'URL pendiente de añadir';
    }

    /** Reemplaza el botón «Generar oferta»: icono editar a la izquierda y resumen envío/precio a la derecha (misma fila que el input de envío debajo). */
    function crearWrapEditarOfertaConResumenCrearMasivo(editUrl, envioVal, precioVal) {
        const wrap = document.createElement('div');
        wrap.className = 'flex items-center gap-2 flex-wrap';
        const btnEditar = document.createElement('a');
        btnEditar.href = editUrl;
        btnEditar.target = '_blank';
        btnEditar.rel = 'noopener noreferrer';
        btnEditar.className = 'inline-flex items-center justify-center shrink-0 p-1.5 rounded bg-blue-600 hover:bg-blue-700 text-white transition';
        btnEditar.title = 'Editar oferta';
        btnEditar.setAttribute('aria-label', 'Editar oferta');
        btnEditar.innerHTML = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>';
        const infoSpan = document.createElement('span');
        infoSpan.className = 'text-sm text-gray-600 dark:text-gray-400 min-w-0';
        infoSpan.textContent = envioVal + (precioVal ? ' · ' + precioVal : '');
        wrap.appendChild(btnEditar);
        wrap.appendChild(infoSpan);
        return wrap;
    }

    const HTML_BTN_GENERAR_OFERTA_CREAR_MASIVO = '<svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg> Generar oferta';
    const HTML_BTN_GENERAR_SPINNER_CREAR_MASIVO = '<svg class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg> Creando...';

    function esErrorPrecioCrearOfertaCrearMasivo(errTxt) {
        const t = String(errTxt || '').toLowerCase();
        return t.includes('precio') || t.includes('price') || t.includes('no disponible') || t.includes('not available') || t.includes('unavailable') || t.includes('sin stock') || t.includes('agotado') || t.includes('out of stock') || t.includes('no se pudo obtener') || t.includes('could not get');
    }

    function textoEnvioPrecioResumenOfertaCrearMasivo(data) {
        const envioVal = (data && data.envio != null && data.envio !== '') ? parseFloat(data.envio).toFixed(2).replace('.', ',') + ' € env.' : 'gratis';
        const precioVal = (data && data.precio_unidad != null) ? parseFloat(data.precio_unidad).toFixed(2).replace('.', ',') + ' €/ud' : '';
        return { envioVal: envioVal, precioVal: precioVal };
    }

    function infoResumenDesdeRespuestaOfertaCrearMasivo(url, data, esPrecioCero) {
        const { envioVal, precioVal } = textoEnvioPrecioResumenOfertaCrearMasivo(data);
        let mensaje = 'Oferta creada correctamente';
        if (data && data.oferta_id) mensaje += ' (ID: ' + data.oferta_id + ')';
        if (esPrecioCero) mensaje = 'Oferta creada con precio 0' + (data && data.oferta_id ? ' (ID: ' + data.oferta_id + ')' : '');
        return {
            status: 'completado',
            url: url,
            success: true,
            mensaje: mensaje,
            oferta_id: data && data.oferta_id,
            oferta_edit_url: data && data.oferta_edit_url,
            envio: data && data.envio,
            precio_unidad: data && data.precio_unidad,
            envioVal: envioVal,
            precioVal: precioVal || (esPrecioCero ? '0 €/ud' : ''),
        };
    }

    async function ejecutarPostCrearOfertaCrearMasivo(body) {
        const res = await fetch('{{ route("admin.ofertas.crear-masivo.crear") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            },
            body: JSON.stringify(body),
        });
        const responseText = await res.text();
        let data;
        try { data = JSON.parse(responseText); } catch (e) { data = { success: false, error: 'Respuesta inválida del servidor' }; }
        if (!data.success || res.status >= 400) {
            logCrearOfertaBulkDiagnostico('[CrearOfertaBulk]', res, responseText, data);
        }
        if (data.success) {
            return { success: true, data: data, esPrecioCero: false };
        }
        if (esErrorPrecioCrearOfertaCrearMasivo(data.error)) {
            const bodyRetry = Object.assign({}, body, { generar_sin_precio: true });
            const res3 = await fetch('{{ route("admin.ofertas.crear-masivo.crear") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' },
                body: JSON.stringify(bodyRetry),
            });
            const res3Text = await res3.text();
            let data3;
            try { data3 = JSON.parse(res3Text); } catch (e) { data3 = {}; }
            if (!data3.success || res3.status >= 400) {
                logCrearOfertaBulkDiagnostico('[CrearOfertaBulk] reintento sin precio', res3, res3Text, data3);
            }
            if (data3.success) {
                return { success: true, data: data3, esPrecioCero: true };
            }
            return { success: false, error: data3.error || 'Error al crear con precio 0' };
        }
        return { success: false, error: (data && data.error) || 'Error al crear' };
    }

    async function prepararBodyGeneracionOfertaCrearMasivo(div, btnGen) {
        const msgEl = div.querySelector('.generado-msg');
        const specLines = div.querySelectorAll('.spec-line');
        if (specLines.length) {
            let gruposSinSeleccion = 0;
            specLines.forEach(function(line) {
                if (!line.querySelector('.spec-checkbox:checked')) gruposSinSeleccion++;
            });
            if (gruposSinSeleccion > 0) {
                if (msgEl) {
                    msgEl.classList.remove('hidden');
                    msgEl.className = 'mt-2 generado-msg text-sm font-medium text-red-600 dark:text-red-400';
                    msgEl.textContent = 'Debes marcar al menos una opción en cada grupo de especificaciones internas.';
                }
                return { ok: false };
            }
        }
        const especs = buildEspecificacionesFromRow(div);
        const confirmoNoEsMisma = div.querySelector('.confirmo-no-es-misma:checked');
        if (!confirmoNoEsMisma && especs && div.__rowData && div.__rowData.producto && div.__rowData.tienda) {
            try {
                const dupBody = { producto_id: div.__rowData.producto.id, tienda_id: div.__rowData.tienda.id, especificaciones_internas: JSON.stringify(especs) };
                const dupRes = await fetch('{{ route("admin.ofertas.crear-masivo.buscar-mismas-especificaciones") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(dupBody),
                });
                const dupData = await dupRes.json().catch(function() { return {}; });
                const container = div.querySelector('.spec-and-ofertas-container');
                if (container) {
                    const old = container.querySelector('.ofertas-mismas-especs');
                    if (old) { old.remove(); quitarCheckboxNoEsMisma(div); }
                    actualizarEstadoBotonGenerar(div);
                }
                if (container && dupData.success && Array.isArray(dupData.ofertas) && dupData.ofertas.length) {
                    const html = '<div class="mt-3 ofertas-mismas-especs text-sm text-amber-700 dark:text-amber-300"><span class="font-medium">Ya existen ofertas con estas especificaciones en esta tienda:</span>' +
                        dupData.ofertas.map(function(o) {
                            const envioTxt = o.envio != null ? (parseFloat(o.envio).toFixed(2).replace('.', ',') + ' € env.') : 'envío gratis';
                            const precioTxt = o.precio_unidad != null ? (parseFloat(o.precio_unidad).toFixed(2).replace('.', ',') + ' €/ud') : '';
                            const info = (precioTxt ? precioTxt + ' · ' : '') + envioTxt;
                            const ver = o.url ? ' <a href="' + o.url + '" target="_blank" class="inline-flex items-center px-2 py-0.5 bg-gray-600 hover:bg-gray-700 text-white text-xs font-medium rounded">Ver</a>' : '';
                            const editar = o.oferta_edit_url ? ' <a href="' + o.oferta_edit_url + '" target="_blank" class="inline-flex items-center px-2 py-0.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium rounded">Editar oferta</a>' : '';
                            return '<div class="mt-1">' + info + ver + editar + '</div>';
                        }).join('') + '</div>';
                    container.insertAdjacentHTML('beforeend', html);
                    agregarCheckboxNoEsMisma(div);
                    actualizarEstadoBotonGenerar(div);
                    if (btnGen) btnGen.innerHTML = HTML_BTN_GENERAR_OFERTA_CREAR_MASIVO;
                    ponerMensajeGeneradoPendienteCrearMasivo(div);
                    return { ok: false };
                }
            } catch (e) {
                console.error('[CrearOfertaBulk] Error buscando duplicados:', e);
            }
        }
        const body = {
            url: btnGen.dataset.url,
            producto_id: btnGen.dataset.productoId,
            tienda_id: btnGen.dataset.tiendaId,
            especificaciones_internas: especs ? JSON.stringify(especs) : null,
        };
        aplicarEnvioInputAlBodyCrearMasivo(div, body);
        const cbGenerarSinPrecio = div.querySelector('.generar-sin-precio-cb');
        if (cbGenerarSinPrecio && cbGenerarSinPrecio.checked) {
            body.generar_sin_precio = true;
        }
        return { ok: true, body: body };
    }

    function aplicarExitoGeneracionEnFilaCrearMasivo(div, btnGen, data, esPrecioCero) {
        const msgEl = div.querySelector('.generado-msg');
        const wrapSinPrecio = div.querySelector('.generar-sin-precio-wrap');
        if (wrapSinPrecio) wrapSinPrecio.classList.add('hidden');
        div.classList.remove('bg-green-50', 'dark:bg-green-900/20', 'border-green-200', 'dark:border-green-700');
        div.classList.add('bg-gray-50', 'dark:bg-gray-800', 'border-gray-200', 'dark:border-gray-700');
        if (div.__rowData) div.__rowData.ofertaGenerada = true;
        const btnQuitar = div.querySelector('.btn-quitar-producto');
        if (btnQuitar) btnQuitar.remove();
        if (msgEl) {
            msgEl.classList.remove('hidden');
            msgEl.className = 'mt-2 generado-msg text-sm font-medium text-green-600 dark:text-green-400';
            msgEl.textContent = esPrecioCero
                ? ('Oferta creada con precio 0 (ID: ' + (data.oferta_id || '') + ')')
                : ('Oferta creada correctamente (ID: ' + (data.oferta_id || '') + ')');
        }
        if (btnGen && btnGen.parentNode) {
            const { envioVal, precioVal } = textoEnvioPrecioResumenOfertaCrearMasivo(data);
            if (data.oferta_edit_url) {
                const wrap = crearWrapEditarOfertaConResumenCrearMasivo(data.oferta_edit_url, envioVal, precioVal || (esPrecioCero ? '0 €/ud' : ''));
                btnGen.parentNode.replaceChild(wrap, btnGen);
            } else {
                btnGen.remove();
            }
        }
    }

    function aplicarErrorGeneracionEnFilaCrearMasivo(div, btnGen, errorMsg, mostrarOpcionPrecioCero) {
        const msgEl = div.querySelector('.generado-msg');
        if (msgEl) {
            msgEl.classList.remove('hidden');
            msgEl.className = 'mt-2 generado-msg text-sm font-medium text-red-600 dark:text-red-400';
            msgEl.textContent = errorMsg || 'Error al crear';
        }
        if (mostrarOpcionPrecioCero && msgEl) {
            let wrapSinPrecioErr = div.querySelector('.generar-sin-precio-wrap');
            if (!wrapSinPrecioErr) {
                wrapSinPrecioErr = document.createElement('div');
                wrapSinPrecioErr.className = 'generar-sin-precio-wrap mt-2 flex flex-wrap items-center gap-3';
                wrapSinPrecioErr.innerHTML = '<label class="inline-flex items-center gap-2 cursor-pointer text-sm text-amber-700 dark:text-amber-300"><input type="checkbox" class="generar-sin-precio-cb rounded border-gray-300 text-amber-600 focus:ring-amber-500"> <span>Generar con precio 0, no mostrar y con aviso de sin stock a 4 días</span></label>';
                msgEl.after(wrapSinPrecioErr);
            }
            wrapSinPrecioErr.classList.remove('hidden');
        }
        if (btnGen) {
            btnGen.disabled = false;
            btnGen.innerHTML = HTML_BTN_GENERAR_OFERTA_CREAR_MASIVO;
        }
    }

    function adjuntarListenerGenerarOfertaCrearMasivo(div) {
        const btnGen = div.querySelector('.btn-generar');
        if (!btnGen || btnGen.__listenerGenerarAdjunto) return;
        btnGen.__listenerGenerarAdjunto = true;
        btnGen.addEventListener('click', function() {
            onClickGenerarOfertaCrearMasivo(div, btnGen);
        });
    }

    async function onClickGenerarOfertaCrearMasivo(div, btnGen) {
        if (!btnGen || btnGen.disabled) return;
        btnGen.disabled = true;
        btnGen.innerHTML = HTML_BTN_GENERAR_SPINNER_CREAR_MASIVO;
        try {
            const prep = await prepararBodyGeneracionOfertaCrearMasivo(div, btnGen);
            if (!prep.ok) {
                btnGen.disabled = false;
                btnGen.innerHTML = HTML_BTN_GENERAR_OFERTA_CREAR_MASIVO;
                actualizarEstadoBotonGenerar(div);
                return;
            }
            if (window.__flujoModalUrlsCrearMasivo && window.__flujoModalUrlsCrearMasivo.filas) {
                lanzarGeneracionOfertaEnSegundoPlanoCrearMasivo(div, btnGen, prep.body);
                return;
            }
            marcarInicioGeneracionOfertaCrearMasivo();
            const result = await ejecutarPostCrearOfertaCrearMasivo(prep.body);
            if (result.success) {
                aplicarExitoGeneracionEnFilaCrearMasivo(div, btnGen, result.data, result.esPrecioCero);
            } else {
                aplicarErrorGeneracionEnFilaCrearMasivo(div, btnGen, result.error, true);
            }
        } catch (err) {
            console.error('[CrearOfertaBulk] Exception:', err);
            aplicarErrorGeneracionEnFilaCrearMasivo(div, btnGen, 'Error: ' + err.message, false);
        } finally {
            if (!window.__flujoModalUrlsCrearMasivo || !window.__flujoModalUrlsCrearMasivo.filas) {
                marcarFinGeneracionOfertaCrearMasivo();
            }
        }
    }

    function extraerPalabrasSlugDesdeUrlCrearMasivo(url) {
        const rawUrl = String(url || '').trim();
        if (!rawUrl) return [];
        let path = rawUrl;
        try {
            path = new URL(rawUrl).pathname || '';
        } catch (e) {
            path = rawUrl.replace(/^https?:\/\/[^/]+/i, '');
        }
        const segs = String(path || '')
            .replace(/\/+$/, '')
            .split('/')
            .filter(Boolean);
        if (!segs.length) return [];
        // VTEX / Appinformatica: …/slug-real/p — el último segmento es "p", no el slug.
        let slug = segs[segs.length - 1];
        const ultimo = slug;
        if (segs.length >= 2 && /^(p|html?)$/i.test(ultimo)) {
            slug = segs[segs.length - 2];
        }
        if (!slug) return [];
        const partes = [];
        slug.split('|').forEach(function(bloque) {
            bloque.split('-').forEach(function(p) {
                const t = String(p || '').trim();
                if (t) partes.push(t);
            });
        });
        return partes;
    }

    function normalizarTokenUrlHighlightCrearMasivo(token) {
        const base = String(token || '').toLowerCase().trim().replace(/[\s\-_\/]+/g, '');
        if (!base) return '';
        const m = base.match(/^(\d+)g$/);
        if (m) return m[1] + 'gb';
        return base;
    }

    function expandirTokenMixtoUrlHighlightCrearMasivo(token) {
        const t = normalizarTokenUrlHighlightCrearMasivo(token);
        if (!t) return [];
        const out = [t];
        let m = t.match(/^([a-z]+)(\d+[a-z0-9]*)$/);
        if (m) {
            out.push(m[1], m[2]);
        } else {
            m = t.match(/^(\d+)([a-z][a-z0-9]*)$/);
            if (m) out.push(m[1], m[2]);
        }
        return Array.from(new Set(out.filter(Boolean)));
    }

    function tokensDesdeTextoUrlHighlightCrearMasivo(texto) {
        const parts = String(texto || '').split(/[\s\-_\/]+/).map(p => p.trim()).filter(Boolean);
        const out = [];
        parts.forEach(p => {
            expandirTokenMixtoUrlHighlightCrearMasivo(p).forEach(x => out.push(x));
        });
        return Array.from(new Set(out));
    }

    function obtenerSetTokensProductoFilaCrearMasivo(rowData) {
        const set = new Set();
        const p = rowData && rowData.producto ? rowData.producto : null;
        if (!p) return set;
        const texto = [p.nombre, p.marca, p.modelo, p.talla, p.texto_completo].filter(Boolean).join(' ');
        tokensDesdeTextoUrlHighlightCrearMasivo(texto).forEach(t => set.add(t));
        return set;
    }

    function aplicarEspecificacionesMarcadasEnFilaCrearMasivo(div, especsMarcadas) {
        if (!div || !especsMarcadas || typeof especsMarcadas !== 'object') return;
        Object.keys(especsMarcadas).forEach(function(principalId) {
            const subIds = especsMarcadas[principalId];
            if (!Array.isArray(subIds)) return;
            subIds.forEach(function(subId) {
                const cb = div.querySelector('.spec-checkbox[data-principal-id="' + principalId + '"][data-sublinea-id="' + subId + '"]');
                if (cb) cb.checked = true;
            });
        });
        actualizarEstadoBotonGenerar(div);
        renderUrlResaltadaFilaCrearMasivo(div);
        actualizarConteosOpcionesEspecsFila(div);
        actualizarAvisosSinImagenEspecsFila(div);
    }

    function urlRecargarEspecificacionesCrearMasivo(productoId, urlOferta) {
        let u = '{{ route("admin.ofertas.crear-masivo.recargar-especificaciones", ["producto" => "__ID__"]) }}'.replace('__ID__', productoId);
        const url = String(urlOferta || '').trim();
        if (url) u += (u.indexOf('?') >= 0 ? '&' : '?') + 'url=' + encodeURIComponent(url);
        return u;
    }

    function obtenerSetTokensSpecsMarcadasFilaCrearMasivo(div) {
        const set = new Set();
        div.querySelectorAll('.spec-checkbox:checked').forEach(cb => {
            const label = cb.closest('label');
            const span = label ? label.querySelector('.spec-option-text') : null;
            let txt = span ? span.textContent : '';
            if (!txt && label) {
                // Fallback defensivo por si cambia el HTML interno del label.
                txt = Array.from(label.querySelectorAll('span'))
                    .filter(s => !s.classList.contains('spec-count-badge'))
                    .map(s => s.textContent || '')
                    .join(' ');
            }
            tokensDesdeTextoUrlHighlightCrearMasivo(txt).forEach(t => set.add(t));
        });
        return set;
    }

    function renderUrlResaltadaFilaCrearMasivo(div) {
        const rowData = div.__rowData || {};
        const url = String(rowData.url_normalizada || rowData.url || '').trim();
        const anchor = div.querySelector('.url-fila-link');
        const span = div.querySelector('.url-fila-texto');
        if (!url || !anchor || !span) return;
        if (rowData.producto_asignado_desde_neo) {
            span.textContent = url;
            anchor.title = url;
            anchor.href = url;
            return;
        }
        const tokensProducto = obtenerSetTokensProductoFilaCrearMasivo(rowData);
        const tokensSpecs = obtenerSetTokensSpecsMarcadasFilaCrearMasivo(div);
        const palabrasSlug = extraerPalabrasSlugDesdeUrlCrearMasivo(url);
        const highlightedSlug = palabrasSlug.map((pal) => {
            const expanded = expandirTokenMixtoUrlHighlightCrearMasivo(pal);
            const hitSpec = expanded.some(t => tokensSpecs.has(t));
            const hitProd = expanded.some(t => tokensProducto.has(t));
            const safe = escapeHtmlCrearMasivo(pal);
            if (hitSpec) {
                return '<span class="bg-orange-300 dark:bg-orange-600/80 text-orange-950 dark:text-orange-50 px-0.5 rounded border border-orange-500/70 font-semibold">' + safe + '</span>';
            }
            if (hitProd) {
                return '<span class="bg-yellow-200 dark:bg-yellow-700/60 text-yellow-900 dark:text-yellow-100 px-0.5 rounded">' + safe + '</span>';
            }
            return safe;
        }).join('-');
        let shown;
        if (highlightedSlug) {
            const mVt = url.match(/^(.+)\/([^/]+)\/(p)\/?$/i);
            if (mVt && String(mVt[3]).toLowerCase() === 'p') {
                const pref = escapeHtmlCrearMasivo(mVt[1]);
                const slashFin = url.endsWith('/') ? '/' : '';
                shown = pref + '/' + highlightedSlug + '/' + mVt[3] + slashFin;
            } else {
                shown = url.replace(/([^\/]+)\/?$/, highlightedSlug + (url.endsWith('/') ? '/' : ''));
            }
        } else {
            shown = escapeHtmlCrearMasivo(url);
        }
        span.innerHTML = shown;
        anchor.title = url;
        anchor.href = url;
    }

    function obtenerCategoriaIdFiltroBusquedaProductoCrearMasivo(row) {
        if (!row) return null;
        if (row.categoria_fila && row.categoria_fila.id != null && row.categoria_fila.id !== '') {
            return parseInt(String(row.categoria_fila.id), 10);
        }
        if (row.categoria_id != null && row.categoria_id !== '') {
            return parseInt(String(row.categoria_id), 10);
        }
        return null;
    }

    function normalizarCategoriaFilaEnResultadoCrearMasivo(r) {
        if (!r) return r;
        if ((!r.categoria_fila || !r.categoria_fila.id) && r.categoria_id != null && r.categoria_id !== '') {
            r.categoria_fila = {
                id: r.categoria_id,
                nombre: r.categoria_nombre || ('Categoría #' + r.categoria_id),
            };
        }
        return r;
    }

    function buildBuscadorProductoHtmlCrearMasivo(url, categoriaFila) {
        const palabrasSlug = extraerPalabrasSlugDesdeUrlCrearMasivo(url);
        const botonesSlugHtml = palabrasSlug.length
            ? '<div class="mt-2 flex flex-wrap gap-1.5 slug-palabras-wrap">' +
                palabrasSlug.map(function(palabra) {
                    const segura = escapeHtmlCrearMasivo(palabra);
                    return '<button type="button" class="btn-slug-palabra-crear-masivo inline-flex items-center px-2 py-1 rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-xs text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-600 transition" data-palabra="' + segura + '">' + segura + '</button>';
                }).join('') +
                '</div>'
            : '';
        const cf = categoriaFila && categoriaFila.id ? categoriaFila : null;
        const nombreCat = cf && cf.nombre ? escapeHtmlCrearMasivo(String(cf.nombre)) : '';
        const toolbarHtml = '<div class="mb-2 flex flex-wrap items-center gap-2 categoria-fila-toolbar">' +
            '<span class="categoria-fila-badge text-xs text-gray-600 dark:text-gray-400' + (cf ? '' : ' hidden') + '">Categoría: <strong class="categoria-fila-nombre">' + nombreCat + '</strong></span>' +
            '</div>';
        return '<div class="mt-3 producto-search-container relative">' +
            toolbarHtml +
            botonesSlugHtml +
            '<input type="text" class="producto-search-input w-full px-3 py-2 rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 text-sm" placeholder="Escribe para buscar productos (mín. 2 caracteres)..." autocomplete="off">' +
            '<div class="producto-sugerencias-crear-masivo absolute z-50 left-0 right-0 mt-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md shadow-lg hidden max-h-60 overflow-y-auto"></div></div>';
    }

    function actualizarToolbarCategoriaFila(div) {
        const toolbar = div.querySelector('.categoria-fila-toolbar');
        if (!toolbar) return;
        const r = div.__rowData;
        const cf = r && r.categoria_fila && r.categoria_fila.id ? r.categoria_fila : null;
        const badge = toolbar.querySelector('.categoria-fila-badge');
        const nombreEl = toolbar.querySelector('.categoria-fila-nombre');
        if (cf) {
            if (nombreEl) nombreEl.textContent = cf.nombre || '';
            if (badge) badge.classList.remove('hidden');
        } else {
            if (nombreEl) nombreEl.textContent = '';
            if (badge) badge.classList.add('hidden');
        }
    }

    async function abrirModalCategoriaFilaCrearMasivo(div) {
        window.__filaModalCategoriaCrearMasivo = div;
        const modal = document.getElementById('modal-categoria-fila-crear-masivo');
        const arbol = document.getElementById('modal-categoria-fila-arbol');
        if (!modal) return;
        modal.classList.remove('hidden');
        if (!arbol) return;
        arbol.innerHTML = '<p class="text-sm text-gray-500 dark:text-gray-400 py-2">Cargando categorías…</p>';
        try {
            const res = await fetch('{{ route("admin.categorias.arbol-picker-crear-masivo") }}?_=' + Date.now(), {
                cache: 'no-store',
                headers: { 'Accept': 'text/html' },
            });
            if (!res.ok) throw new Error('HTTP ' + res.status);
            arbol.innerHTML = await res.text();
            if (typeof window.Alpine !== 'undefined' && typeof window.Alpine.initTree === 'function') {
                window.Alpine.initTree(arbol);
            }
        } catch (err) {
            console.error(err);
            arbol.innerHTML = '<p class="text-sm text-red-600 dark:text-red-400 py-2">No se pudieron cargar las categorías. Inténtalo de nuevo.</p>';
        }
    }

    function cerrarModalCategoriaFilaCrearMasivo() {
        window.__filaModalCategoriaCrearMasivo = null;
        const modal = document.getElementById('modal-categoria-fila-crear-masivo');
        if (modal) modal.classList.add('hidden');
    }

    async function guardarCategoriaNeoServidorCrearMasivo(urlFila, categoriaId) {
        const res = await fetch(@json(route('admin.neo.crear-masivo.actualizar-categoria-url')), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            },
            body: JSON.stringify({ url: urlFila, categoria_id: categoriaId }),
        });
        const data = await res.json().catch(function() { return {}; });
        if (!res.ok || !data.success) {
            const msg = (data && data.message) ? data.message : ('HTTP ' + res.status);
            throw new Error(msg);
        }
        return data;
    }

    async function aplicarCategoriaElegidaArbolCrearMasivo(id, nombre) {
        const div = window.__filaModalCategoriaCrearMasivo;
        cerrarModalCategoriaFilaCrearMasivo();
        if (!div || !div.__rowData) return;
        const urlFila = div.__rowData.url_normalizada || div.__rowData.url || '';
        if (!urlFila) return;
        try {
            await guardarCategoriaNeoServidorCrearMasivo(urlFila, id);
        } catch (err) {
            console.error(err);
            alert('No se pudo guardar la categoría en neo: ' + (err && err.message ? err.message : err));
            return;
        }
        div.__rowData = Object.assign({}, div.__rowData, { categoria_fila: { id: id, nombre: nombre } });
        actualizarToolbarCategoriaFila(div);

        const flujo = window.__flujoModalUrlsCrearMasivo;
        if (flujo && !flujo.modoFinal && div === flujo.filaActualEnModal) {
            avanzarASiguienteUrlEnFlujoModalCrearMasivo();
            return;
        }

        const inp = div.querySelector('.producto-search-input');
        if (inp && inp.value.trim().length >= 2) buscarProductosCrearMasivo(div, inp.value.trim());
    }

    // Fondos grises alternos para que se distinga bien el bloque frente al resto de resultados.
    const CREAR_MASIVO_GRUPO_BORDE_CLASSES = [
        'border-gray-400 dark:border-gray-500 bg-gray-200 dark:bg-gray-800 shadow-inner ring-1 ring-gray-300/90 dark:ring-gray-600',
        'border-gray-500 dark:border-gray-400 bg-gray-300/95 dark:bg-zinc-900 shadow-inner ring-1 ring-gray-400/80 dark:ring-zinc-600',
    ];

    /** Clave de agrupación: mismo id de producto → fila suelta. */
    function claveGrupoProductoSugeridoCrearMasivo(r) {
        if (r.producto && r.producto.id != null && r.producto.id !== '') {
            return 'p:' + String(r.producto.id);
        }
        return 'u:' + r.__crearMasivoOrigenIdx;
    }

    /** Ordena filas para que todas las URLs con el mismo producto sugerido queden seguidas (orden original dentro de cada grupo). */
    function ordenarResultadosPorGrupoProductoCrearMasivo(resultados) {
        resultados.forEach((r, i) => { r.__crearMasivoOrigenIdx = i; });
        const entries = resultados.map((r, i) => ({ r, i }));
        const primera = new Map();
        entries.forEach((e) => {
            const k = (e.r.producto && e.r.producto.id != null && e.r.producto.id !== '')
                ? 'p:' + String(e.r.producto.id)
                : 'u:' + e.i;
            if (!primera.has(k)) primera.set(k, e.i);
        });
        entries.sort((a, b) => {
            const ka = (a.r.producto && a.r.producto.id != null && a.r.producto.id !== '') ? 'p:' + String(a.r.producto.id) : 'u:' + a.i;
            const kb = (b.r.producto && b.r.producto.id != null && b.r.producto.id !== '') ? 'p:' + String(b.r.producto.id) : 'u:' + b.i;
            if (ka !== kb) return primera.get(ka) - primera.get(kb);
            return a.i - b.i;
        });
        return entries.map(e => e.r);
    }

    function contarPorClaveGrupoCrearMasivo(ordenados) {
        const m = new Map();
        ordenados.forEach((r) => {
            const k = claveGrupoProductoSugeridoCrearMasivo(r);
            m.set(k, (m.get(k) || 0) + 1);
        });
        return m;
    }

    let generacionesOfertaEnCursoCrearMasivo = 0;

    /** Con recorrido Neo activo: mientras queden URLs del textarea sin analizar, el pie es «Siguientes URLs»; si no, «Siguiente producto/tienda». */
    function textoBotonSiguienteFooterNeoSinGenerarCrearMasivo() {
        const ctx = window.__crearMasivoOrigenNeo;
        if (!ctx || (ctx.tipo !== 'producto' && ctx.tipo !== 'tienda')) return 'Siguiente';
        actualizarEstadoLotesDesdeTextarea();
        const pend = Math.max(estadoLotesAnalisis.urls.length - estadoLotesAnalisis.cursor, 0);
        if (pend > 0) return 'Siguientes URLs';
        return ctx.tipo === 'producto' ? 'Siguiente producto' : 'Siguiente tienda';
    }

    function marcarInicioGeneracionOfertaCrearMasivo() {
        generacionesOfertaEnCursoCrearMasivo++;
        aplicarEstadoBotonSiguienteSegunGeneracionCrearMasivo();
    }

    function marcarFinGeneracionOfertaCrearMasivo() {
        generacionesOfertaEnCursoCrearMasivo = Math.max(0, generacionesOfertaEnCursoCrearMasivo - 1);
        aplicarEstadoBotonSiguienteSegunGeneracionCrearMasivo();
    }

    /** Deshabilita «Siguiente producto/tienda» en gris con texto «Generando» mientras haya creación de oferta en curso. */
    function aplicarEstadoBotonSiguienteSegunGeneracionCrearMasivo() {
        const btn = document.getElementById('btnSiguienteOrigenNeo');
        const wrap = document.getElementById('crear-masivo-siguiente-wrap');
        if (!btn || !wrap || wrap.classList.contains('hidden')) return;
        const ctx = window.__crearMasivoOrigenNeo;
        if (!ctx || (ctx.tipo !== 'producto' && ctx.tipo !== 'tienda')) return;
        if (generacionesOfertaEnCursoCrearMasivo > 0) {
            btn.disabled = true;
            btn.textContent = 'Generando';
            btn.classList.remove('bg-emerald-600', 'hover:bg-emerald-700');
            btn.classList.add('bg-gray-400', 'hover:bg-gray-500');
            return;
        }
        btn.disabled = false;
        btn.textContent = textoBotonSiguienteFooterNeoSinGenerarCrearMasivo();
        btn.classList.remove('bg-gray-400', 'hover:bg-gray-500');
        btn.classList.add('bg-emerald-600', 'hover:bg-emerald-700');
    }

    function actualizarVisibilidadFooterSiguienteNeo() {
        const wrap = document.getElementById('crear-masivo-siguiente-wrap');
        const btn = document.getElementById('btnSiguienteOrigenNeo');
        if (!wrap) return;
        const ctx = window.__crearMasivoOrigenNeo;
        if (!ctx || (ctx.tipo !== 'producto' && ctx.tipo !== 'tienda')) {
            wrap.classList.add('hidden');
            return;
        }
        wrap.classList.remove('hidden');
        aplicarEstadoBotonSiguienteSegunGeneracionCrearMasivo();
    }

    function scrollCrearMasivoAlFormularioUrls() {
        const el = document.getElementById('urls');
        if (el) el.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    /** Abre el modal de la primera URL tras analizar (flujo URL a URL). */
    function scrollCrearMasivoAPrimerResultadoAnalisis() {
        requestAnimationFrame(function() {
            if (window.__flujoModalUrlsCrearMasivo && window.__flujoModalUrlsCrearMasivo.filas && window.__flujoModalUrlsCrearMasivo.filas.length) {
                const modal = document.getElementById('modal-url-crear-masivo');
                if (modal && !modal.classList.contains('hidden') && typeof modal.scrollIntoView === 'function') {
                    modal.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }
        });
    }

    window.__flujoModalUrlsCrearMasivo = null;

    function htmlResumenGeneracionBannerCrearMasivo(info, tituloSeccion) {
        if (!info) return '';
        const titulo = tituloSeccion || 'URL anterior';
        const urlTxt = escapeHtmlCrearMasivo((info.url || '').substring(0, 120) + ((info.url || '').length > 120 ? '…' : ''));
        let estadoHtml = '';
        if (info.status === 'generando') {
            estadoHtml = '<p class="text-sm font-medium text-blue-600 dark:text-blue-400 flex items-center gap-2">' +
                '<svg class="w-4 h-4 animate-spin shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>' +
                '<span>Generando oferta…</span></p>';
        } else if (info.success) {
            const envioVal = info.envioVal || ((info.envio != null && info.envio !== '') ? parseFloat(info.envio).toFixed(2).replace('.', ',') + ' € env.' : 'gratis');
            const precioVal = info.precioVal || (info.precio_unidad != null ? parseFloat(info.precio_unidad).toFixed(2).replace('.', ',') + ' €/ud' : '');
            estadoHtml = '<p class="text-sm font-medium text-green-600 dark:text-green-400">' + escapeHtmlCrearMasivo(info.mensaje || 'Oferta generada') + '</p>';
            if (precioVal || envioVal) {
                estadoHtml += '<p class="text-sm text-gray-600 dark:text-gray-400 mt-0.5">' + escapeHtmlCrearMasivo((precioVal ? precioVal : '') + (precioVal && envioVal ? ' · ' : '') + envioVal) + '</p>';
            }
            if (info.oferta_id) {
                estadoHtml += '<p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">ID oferta: ' + escapeHtmlCrearMasivo(String(info.oferta_id)) + '</p>';
            }
        } else {
            estadoHtml = '<p class="text-sm font-medium text-red-600 dark:text-red-400">' + escapeHtmlCrearMasivo(info.mensaje || 'Error al generar') + '</p>';
        }
        const btnEditar = (info.success && info.oferta_edit_url)
            ? '<a href="' + info.oferta_edit_url + '" target="_blank" rel="noopener noreferrer" class="inline-flex items-center px-3 py-1.5 text-sm font-medium rounded bg-blue-600 hover:bg-blue-700 text-white transition shrink-0">Editar oferta guardada</a>'
            : '';
        return '<div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">' +
            '<div class="min-w-0 flex-1">' +
            '<p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-1">' + escapeHtmlCrearMasivo(titulo) + '</p>' +
            '<p class="text-xs break-all text-gray-600 dark:text-gray-300 mb-2">' + urlTxt + '</p>' +
            estadoHtml +
            '</div>' +
            (btnEditar ? '<div class="flex shrink-0 items-start">' + btnEditar + '</div>' : '') +
            '</div>';
    }

    function renderPrevGuardadoModalCrearMasivo(info) {
        const prev = document.getElementById('modal-url-crear-masivo-prev-guardado');
        if (!prev) return;
        if (!info) {
            prev.classList.add('hidden');
            prev.innerHTML = '';
            return;
        }
        prev.innerHTML = htmlResumenGeneracionBannerCrearMasivo(info, 'URL anterior');
        prev.classList.remove('hidden');
    }

    function renderContenidoModalFinalCrearMasivo(info) {
        const cont = document.getElementById('modal-url-crear-masivo-final-contenido');
        if (!cont) return;
        cont.innerHTML = htmlResumenGeneracionBannerCrearMasivo(info, 'Última URL');
    }

    function actualizarBannerGeneracionPendienteCrearMasivo() {
        const flujo = window.__flujoModalUrlsCrearMasivo;
        if (!flujo) return;
        if (flujo.modoFinal) {
            renderContenidoModalFinalCrearMasivo(flujo.ultimoGuardado);
            const btnAceptar = document.getElementById('modal-url-crear-masivo-final-aceptar');
            if (btnAceptar && flujo.ultimoGuardado && flujo.ultimoGuardado.status !== 'generando') {
                btnAceptar.disabled = false;
                btnAceptar.classList.remove('opacity-50', 'cursor-not-allowed');
                btnAceptar.classList.add('hover:bg-blue-700');
            }
        } else {
            renderPrevGuardadoModalCrearMasivo(flujo.ultimoGuardado);
        }
    }

    function actualizarPieModalUrlsCrearMasivo(opciones) {
        const pieSig = document.getElementById('modal-url-crear-masivo-siguiente');
        const pieSaltar = document.getElementById('modal-url-crear-masivo-saltar');
        const mostrarSiguiente = !!(opciones && opciones.mostrarSiguiente);
        const mostrarSaltar = !!(opciones && opciones.mostrarSaltar);
        if (pieSig) pieSig.classList.toggle('hidden', !mostrarSiguiente);
        if (pieSaltar) pieSaltar.classList.toggle('hidden', !mostrarSaltar);
    }

    /** Avanza al siguiente ítem del flujo modal sin generar ni descartar la URL actual. */
    function avanzarASiguienteUrlEnFlujoModalCrearMasivo() {
        const flujo = window.__flujoModalUrlsCrearMasivo;
        if (!flujo || flujo.modoFinal) return;
        prepararNuevaPestanaUrlCrearMasivo();
        if (flujo.filaActualEnModal) {
            devolverFilaModalAAlmacenCrearMasivo(flujo.filaActualEnModal);
            flujo.filaActualEnModal = null;
        }
        flujo.indice += 1;
        if (flujo.indice >= flujo.filas.length) {
            cerrarFlujoModalUrlsCrearMasivo();
            return;
        }
        mostrarModalUrlActualCrearMasivo();
    }

    function devolverFilaModalAAlmacenCrearMasivo(fila) {
        const almacen = document.getElementById('resultadosLista');
        const contenido = document.getElementById('modal-url-crear-masivo-contenido');
        if (!fila || !almacen) return;
        ocultarSugerenciasCrearMasivo(fila);
        if (fila.parentNode === contenido || fila.parentNode === document.getElementById('modal-url-crear-masivo')) {
            almacen.appendChild(fila);
        }
    }

    function cerrarModalFinalGeneracionCrearMasivo() {
        const modalFinal = document.getElementById('modal-url-crear-masivo-final');
        if (modalFinal) modalFinal.classList.add('hidden');
        const btnAceptar = document.getElementById('modal-url-crear-masivo-final-aceptar');
        if (btnAceptar) {
            btnAceptar.disabled = true;
            btnAceptar.classList.add('opacity-50', 'cursor-not-allowed');
            btnAceptar.classList.remove('hover:bg-blue-700');
        }
    }

    function cerrarFlujoModalUrlsCrearMasivo() {
        const flujo = window.__flujoModalUrlsCrearMasivo;
        if (flujo && flujo.filaActualEnModal) {
            devolverFilaModalAAlmacenCrearMasivo(flujo.filaActualEnModal);
        }
        window.__flujoModalUrlsCrearMasivo = null;
        const modal = document.getElementById('modal-url-crear-masivo');
        if (modal) modal.classList.add('hidden');
        const contenido = document.getElementById('modal-url-crear-masivo-contenido');
        if (contenido) contenido.innerHTML = '';
        const prev = document.getElementById('modal-url-crear-masivo-prev-guardado');
        if (prev) { prev.classList.add('hidden'); prev.innerHTML = ''; }
        const pieSig = document.getElementById('modal-url-crear-masivo-siguiente');
        const pieSaltar = document.getElementById('modal-url-crear-masivo-saltar');
        if (pieSig) pieSig.classList.add('hidden');
        if (pieSaltar) pieSaltar.classList.add('hidden');
        cerrarModalFinalGeneracionCrearMasivo();
        document.body.style.overflow = '';
        if (window.__cmBeforeUnloadGenerandoHandler) {
            window.removeEventListener('beforeunload', window.__cmBeforeUnloadGenerandoHandler);
            window.__cmBeforeUnloadGenerandoHandler = null;
        }
    }

    function mostrarModalFinalGeneracionCrearMasivo() {
        const flujo = window.__flujoModalUrlsCrearMasivo;
        if (!flujo) return;
        flujo.modoFinal = true;
        flujo.filaActualEnModal = null;
        const modal = document.getElementById('modal-url-crear-masivo');
        if (modal) modal.classList.add('hidden');
        const contenido = document.getElementById('modal-url-crear-masivo-contenido');
        if (contenido) contenido.innerHTML = '';
        const modalFinal = document.getElementById('modal-url-crear-masivo-final');
        if (modalFinal) modalFinal.classList.remove('hidden');
        renderContenidoModalFinalCrearMasivo(flujo.ultimoGuardado);
        const btnAceptar = document.getElementById('modal-url-crear-masivo-final-aceptar');
        if (btnAceptar) {
            btnAceptar.disabled = true;
            btnAceptar.classList.add('opacity-50', 'cursor-not-allowed');
            btnAceptar.classList.remove('hover:bg-blue-700');
        }
        document.body.style.overflow = 'hidden';
        if (!window.__cmBeforeUnloadGenerandoHandler) {
            window.__cmBeforeUnloadGenerandoHandler = function(e) {
                const f = window.__flujoModalUrlsCrearMasivo;
                if (f && f.modoFinal && f.ultimoGuardado && f.ultimoGuardado.status === 'generando') {
                    e.preventDefault();
                    e.returnValue = '';
                }
            };
            window.addEventListener('beforeunload', window.__cmBeforeUnloadGenerandoHandler);
        }
    }

    function finalizarGeneracionEnSegundoPlanoCrearMasivo(div, btnGen, result) {
        const flujo = window.__flujoModalUrlsCrearMasivo;
        const url = (div && div.__rowData && (div.__rowData.url_normalizada || div.__rowData.url)) || (btnGen && btnGen.dataset.url) || '';
        if (result.success) {
            aplicarExitoGeneracionEnFilaCrearMasivo(div, btnGen, result.data, result.esPrecioCero);
            if (flujo) {
                flujo.ultimoGuardado = infoResumenDesdeRespuestaOfertaCrearMasivo(url, result.data, result.esPrecioCero);
            }
        } else {
            aplicarErrorGeneracionEnFilaCrearMasivo(div, btnGen, result.error, false);
            if (flujo) {
                flujo.ultimoGuardado = { status: 'completado', url: url, success: false, mensaje: result.error || 'Error al crear' };
            }
        }
        if (flujo) {
            flujo.generacionesPendientes = Math.max((flujo.generacionesPendientes || 1) - 1, 0);
            actualizarBannerGeneracionPendienteCrearMasivo();
        }
        marcarFinGeneracionOfertaCrearMasivo();
    }

    function lanzarGeneracionOfertaEnSegundoPlanoCrearMasivo(div, btnGen, body) {
        const flujo = window.__flujoModalUrlsCrearMasivo;
        if (!flujo) return;
        const url = (div.__rowData && (div.__rowData.url_normalizada || div.__rowData.url)) || btnGen.dataset.url || '';
        const esUltima = flujo.indice >= flujo.filas.length - 1;
        prepararNuevaPestanaUrlCrearMasivo();

        flujo.ultimoGuardado = { status: 'generando', url: url };
        flujo.generacionesPendientes = (flujo.generacionesPendientes || 0) + 1;
        marcarInicioGeneracionOfertaCrearMasivo();

        devolverFilaModalAAlmacenCrearMasivo(div);
        flujo.filaActualEnModal = null;
        flujo.indice += 1;

        if (esUltima) {
            mostrarModalFinalGeneracionCrearMasivo();
        } else {
            mostrarModalUrlActualCrearMasivo();
        }

        void (async function() {
            try {
                const result = await ejecutarPostCrearOfertaCrearMasivo(body);
                finalizarGeneracionEnSegundoPlanoCrearMasivo(div, btnGen, result);
            } catch (err) {
                console.error('[CrearOfertaBulk] Exception en segundo plano:', err);
                finalizarGeneracionEnSegundoPlanoCrearMasivo(div, btnGen, { success: false, error: 'Error: ' + err.message });
            }
        })();
    }

    function mostrarModalUrlActualCrearMasivo() {
        const flujo = window.__flujoModalUrlsCrearMasivo;
        if (!flujo || !flujo.filas || !flujo.filas.length) return;
        if (flujo.modoFinal) return;
        const modal = document.getElementById('modal-url-crear-masivo');
        const contenido = document.getElementById('modal-url-crear-masivo-contenido');
        const progreso = document.getElementById('modal-url-crear-masivo-progreso');
        if (!modal || !contenido) return;

        cerrarModalFinalGeneracionCrearMasivo();

        if (flujo.filaActualEnModal) {
            devolverFilaModalAAlmacenCrearMasivo(flujo.filaActualEnModal);
        }
        contenido.innerHTML = '';

        if (flujo.indice >= flujo.filas.length) {
            return;
        }

        const fila = flujo.filas[flujo.indice];
        flujo.filaActualEnModal = fila;
        contenido.appendChild(fila);
        adjuntarListenerGenerarOfertaCrearMasivo(fila);

        renderPrevGuardadoModalCrearMasivo(flujo.ultimoGuardado);

        const total = flujo.filas.length;
        if (progreso) progreso.textContent = 'URL ' + (flujo.indice + 1) + ' de ' + total;

        const tieneGenerar = !!fila.querySelector('.btn-generar');
        const yaGenerada = !!(fila.__rowData && fila.__rowData.ofertaGenerada);
        actualizarPieModalUrlsCrearMasivo({
            mostrarSiguiente: !tieneGenerar || yaGenerada,
            mostrarSaltar: true,
        });

        const urlAbrir = (fila.__rowData && (fila.__rowData.url_normalizada || fila.__rowData.url)) || '';
        if (urlAbrir) {
            abrirUrlModalEnNuevaPestanaCrearMasivo(urlAbrir);
        }

        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function avanzarFlujoModalUrlsCrearMasivo() {
        if (!window.__flujoModalUrlsCrearMasivo) return;
        mostrarModalUrlActualCrearMasivo();
    }

    function iniciarFlujoModalUrlsCrearMasivo() {
        const almacen = document.getElementById('resultadosLista');
        if (!almacen) return;
        const filas = Array.from(almacen.querySelectorAll('.crear-masivo-fila'));
        if (!filas.length) return;
        window.__flujoModalUrlsCrearMasivo = {
            filas: filas,
            indice: 0,
            ultimoGuardado: null,
            filaActualEnModal: null,
            modoFinal: false,
            generacionesPendientes: 0,
        };
        mostrarModalUrlActualCrearMasivo();
    }

    (function initModalUrlCrearMasivoListeners() {
        const modalContenidoScroll = document.getElementById('modal-url-crear-masivo-contenido');
        if (modalContenidoScroll) {
            modalContenidoScroll.addEventListener('scroll', function() {
                const flujo = window.__flujoModalUrlsCrearMasivo;
                const fila = flujo && flujo.filaActualEnModal;
                if (!fila) return;
                const sug = fila.querySelector('.producto-sugerencias-crear-masivo');
                if (sug && !sug.classList.contains('hidden')) {
                    posicionarSugerenciasProductoCrearMasivo(fila);
                }
            }, { passive: true });
        }
        window.addEventListener('resize', function() {
            const flujo = window.__flujoModalUrlsCrearMasivo;
            const fila = flujo && flujo.filaActualEnModal;
            if (!fila) return;
            const sug = fila.querySelector('.producto-sugerencias-crear-masivo');
            if (sug && !sug.classList.contains('hidden')) {
                posicionarSugerenciasProductoCrearMasivo(fila);
            }
        }, { passive: true });
        const btnXCerrar = document.getElementById('modal-url-crear-masivo-cerrar-x');
        const btnAceptarFinal = document.getElementById('modal-url-crear-masivo-final-aceptar');
        const btnSiguiente = document.getElementById('modal-url-crear-masivo-siguiente');
        const btnSaltar = document.getElementById('modal-url-crear-masivo-saltar');
        if (btnXCerrar) btnXCerrar.addEventListener('click', cerrarFlujoModalUrlsCrearMasivo);
        if (btnAceptarFinal) btnAceptarFinal.addEventListener('click', cerrarFlujoModalUrlsCrearMasivo);
        if (btnSiguiente) {
            btnSiguiente.addEventListener('click', avanzarASiguienteUrlEnFlujoModalCrearMasivo);
        }
        if (btnSaltar) {
            btnSaltar.addEventListener('click', avanzarASiguienteUrlEnFlujoModalCrearMasivo);
        }
        const modalFinal = document.getElementById('modal-url-crear-masivo-final');
        if (modalFinal) {
            modalFinal.addEventListener('click', function(e) {
                if (e.target === modalFinal) e.stopPropagation();
            });
        }
    })();

    async function ejecutarSiguienteOrigenNeoCrearMasivo() {
        const ctx = window.__crearMasivoOrigenNeo;
        const btn = document.getElementById('btnSiguienteOrigenNeo');
        if (!ctx || (ctx.tipo !== 'producto' && ctx.tipo !== 'tienda')) {
            alert('No hay un recorrido activo desde Producto o Tienda. Usa esos botones arriba para cargar URLs y analizar.');
            return;
        }
        actualizarEstadoLotesDesdeTextarea();
        const pendientesUrls = Math.max(estadoLotesAnalisis.urls.length - estadoLotesAnalisis.cursor, 0);
        if (pendientesUrls > 0) {
            if (btn) {
                btn.disabled = true;
                btn.textContent = 'Analizando...';
            }
            try {
                const analisisOk = await ejecutarAnalisisLote(false);
                if (analisisOk) {
                    scrollCrearMasivoAPrimerResultadoAnalisis();
                }
            } catch (err) {
                console.error(err);
                alert((err && err.message) ? err.message : 'Error al analizar URLs.');
            } finally {
                if (btn) btn.disabled = false;
                actualizarVisibilidadFooterSiguienteNeo();
            }
            return;
        }
        if (btn) {
            btn.disabled = true;
            btn.textContent = 'Cargando...';
        }
        try {
            if (ctx.tipo === 'producto') {
                await siguienteProductoNeoCrearMasivo();
            } else {
                await siguienteTiendaNeoCrearMasivo();
            }
            const listaDiv = document.getElementById('resultadosLista');
            if (listaDiv) listaDiv.innerHTML = '';
            cerrarFlujoModalUrlsCrearMasivo();
            actualizarVisibilidadFooterSiguienteNeo();
            scrollCrearMasivoAlFormularioUrls();
        } catch (err) {
            console.error(err);
            alert((err && err.message) ? err.message : 'Error al cargar el siguiente.');
        } finally {
            actualizarVisibilidadFooterSiguienteNeo();
        }
    }

    function mostrarResultados(resultados) {
        const contenedor = document.getElementById('resultadosLista');
        contenedor.innerHTML = '';
        cerrarFlujoModalUrlsCrearMasivo();

        const ordenados = ordenarResultadosPorGrupoProductoCrearMasivo(resultados);
        const conteosGrupo = contarPorClaveGrupoCrearMasivo(ordenados);
        let claveGrupoWrapperAbierto = null;
        let nodoWrapperGrupo = null;
        let indiceEstiloGrupo = 0;

        ordenados.forEach((r, idx) => {
            r = normalizarCategoriaFilaEnResultadoCrearMasivo(r);
            const div = document.createElement('div');
            const modoTodasConProducto = !!(window.__modoProductoTodasNeo && r && !r.existe && r.tienda && r.producto && !r.error);
            const puedeCrear = !r.existe && r.tienda && r.producto && !r.error;
            const necesitaProducto = !r.existe && r.tienda && !r.producto && !modoTodasConProducto;
            const noEntreOpciones = (r.no_entre_opciones === true) && !modoTodasConProducto;
            let estadoClass = 'bg-gray-50 dark:bg-gray-800';
            let estadoText = '';
            if (r.existe) {
                estadoClass = 'bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700';
                estadoText = r.descartada ? 'URL descartada' : (r.existe_otros_productos ? 'URL ya existe en otros productos' : 'URL ya existe');
            } else if (modoTodasConProducto) {
                estadoClass = 'bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-700';
                estadoText = 'Lista para crear';
            } else if (noEntreOpciones) {
                estadoClass = 'bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700';
                estadoText = 'No está entre las posibles opciones. Busca uno manualmente:';
            } else if (necesitaProducto) {
                estadoClass = 'bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700';
                estadoText = (r.sin_producto_sugerido === true)
                    ? 'Sin producto sugerido. Busca uno manualmente:'
                    : 'Producto no encontrado. Busca uno manualmente:';
            } else if (puedeCrear) {
                estadoClass = 'bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-700';
                estadoText = 'Lista para crear';
            } else if (r.error) {
                estadoClass = 'bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700';
                estadoText = r.error;
            } else {
                estadoText = 'No se pudo determinar producto o tienda';
            }

            // Salvaguarda final: en modo "Producto -> Todas", si ya tenemos tienda y producto
            // asociados, la fila nunca debe mostrarse como "Producto no encontrado".
            if (modoTodasConProducto) {
                estadoClass = 'bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-700';
                estadoText = 'Lista para crear';
            }

            let especsHtml = r.producto
                ? buildEspecsHtml(r.producto, r.especificaciones, r.tiene_especificaciones)
                : '';
            let buscadorProductoHtml = '';
            if (necesitaProducto || noEntreOpciones) {
                buscadorProductoHtml = buildBuscadorProductoHtmlCrearMasivo(r.url_normalizada || r.url || '', r.categoria_fila || null);
            }

            let selectorEmpateHtml = '';
            if (puedeCrear && r.hay_empate && r.candidatos_empatados && r.candidatos_empatados.length > 1 && !r.producto_asignado_desde_neo) {
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
                        const verBtn = o.url ? ' <a href="' + o.url + '" target="_blank" class="inline-flex items-center px-2 py-0.5 bg-gray-600 hover:bg-gray-700 text-white text-xs font-medium rounded">Ver</a>' : '';
                        const editarBtn = o.oferta_edit_url ? ' <a href="' + o.oferta_edit_url + '" target="_blank" class="inline-flex items-center px-2 py-0.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium rounded">Editar oferta</a>' : '';
                        return '<div>' + prodLink + ' — ' + (o.tienda || '') + verBtn + editarBtn + '</div>';
                    }).join('') + '</div>';
            }

            div.className = 'crear-masivo-fila p-4 rounded-lg border ' + estadoClass;
            div.dataset.esUnidadUnica = r.especificaciones && r.especificaciones.unidad_de_medida === 'unidadUnica' ? '1' : '0';
            div.dataset.columnasIds = (r.especificaciones && r.especificaciones.columnas_ids) ? JSON.stringify(r.especificaciones.columnas_ids) : '[]';
            const btnVerPromptHtml = (r.chatgpt_prompt ? '<button type="button" class="btn-ver-prompt-crear-masivo inline-flex items-center px-2 py-1 text-xs font-medium rounded border border-gray-400 dark:border-gray-500 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition">Ver prompt</button>' : '');
            const btnAgregarProductoHtml = '<a href="{{ route("admin.productos.create") }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center justify-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded transition"><svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>Añadir producto</a>';
            const btnCategoriaFilaHtml = '<button type="button" class="btn-elegir-categoria-fila-crear-masivo inline-flex items-center justify-center px-4 py-2 text-sm font-medium rounded border border-indigo-300 dark:border-indigo-600 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-800 dark:text-indigo-200 hover:bg-indigo-100 dark:hover:bg-indigo-900/50 transition">Cambiar Categoría</button>';
            const urlFila = r.url_normalizada || r.url || '';
            const puedeDescartar = !r.descartada;
            const descartarToggleHtml = puedeDescartar
                ? '<label class="descartar-toggle-wrap inline-flex items-center gap-2 text-xs text-gray-600 dark:text-gray-400 cursor-pointer"><input type="checkbox" class="cb-descartar-url rounded border-gray-300 text-red-600 focus:ring-red-500"><span>Descartar</span></label>'
                : '';
            const btnDescartarHtml = puedeDescartar
                ? '<button type="button" class="btn-descartar-url hidden inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded transition" data-url="' + String(urlFila).replace(/"/g, '&quot;') + '"><svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 7h12M9 7V5h6v2m-7 4v6m4-6v6"></path></svg>Descartar URL</button>'
                : '';
            const tiendaFlagsHtml = r.tienda
                ? [
                    (String(r.tienda.mostrar_tienda || '').toLowerCase() === 'no'
                        ? '<span class="inline-flex items-center px-1.5 py-0.5 rounded bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300 text-xs font-medium">Mostrar-&gt;no</span>'
                        : ''),
                    (String(r.tienda.scrapear || '').toLowerCase() === 'no'
                        ? '<span class="inline-flex items-center px-1.5 py-0.5 rounded bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300 text-xs font-medium">Scraping-&gt;no</span>'
                        : ''),
                ].filter(Boolean).join(' ')
                : '';
            const tiendaNoMostrar = String((r.tienda && r.tienda.mostrar_tienda) || '').toLowerCase() === 'no';
            const tiendaNoScraping = String((r.tienda && r.tienda.scrapear) || '').toLowerCase() === 'no';
            const permitirCheckPrecioCero = !!r.tienda && !tiendaNoMostrar && !tiendaNoScraping;
            const neoIdTagHtml = (puedeCrear && r.neo_id)
                ? '<span class="inline-flex items-center px-1.5 py-0.5 rounded bg-indigo-100 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 text-xs font-medium">Neo ID: ' + r.neo_id + '</span>'
                : '';
            div.innerHTML = `
                <div class="flex justify-between items-start gap-4">
                    <div class="flex-1 min-w-0">
                        <div class="font-medium text-gray-900 dark:text-white flex items-center gap-2 flex-wrap">${estadoText} ${neoIdTagHtml} ${btnVerPromptHtml}</div>
                        <div class="mt-1 text-sm break-all">
                            <a href="${r.url_normalizada || r.url}" target="_blank" class="url-fila-link text-blue-500 hover:underline block" title="${r.url_normalizada || r.url || ''}"><span class="url-fila-texto">${escapeHtmlCrearMasivo(r.url_normalizada || r.url || '')}</span></a>
                        </div>
                        <div class="mt-1">
                            ${descartarToggleHtml}
                        </div>
                        ${r.contenido_pagina_extraido ? '<div class="mt-1.5 text-xs text-gray-500 dark:text-gray-400 border-l-2 border-gray-300 dark:border-gray-600 pl-2 space-y-0.5">' + String(r.contenido_pagina_extraido).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/\n/g, '<br>') + '</div>' : ''}
                        ${r.tienda ? '<div class="mt-1 text-sm text-gray-600 dark:text-gray-400">Tienda: <strong>' + r.tienda.nombre + '</strong>' + (tiendaFlagsHtml ? '<span class="ml-2 inline-flex items-center gap-1">' + tiendaFlagsHtml + '</span>' : '') + '</div>' : ''}
                        ${r.producto && r.tienda ? '<div class="mt-1 text-sm text-gray-600 dark:text-gray-400 producto-display flex items-center gap-2 flex-wrap"><span>Producto: ' + (r.producto.url_producto ? '<a href="' + r.producto.url_producto + '" target="_blank" class="text-green-500 hover:underline font-medium">' + r.producto.texto_completo + '</a>' : '<strong>' + r.producto.texto_completo + '</strong>') + '</span>' + htmlBotonesProductoFilaCrearMasivo(r.producto.id) + '</div>' : (r.producto ? '<div class="mt-1 text-sm text-gray-600 dark:text-gray-400 producto-display flex items-center gap-2 flex-wrap"><span>Producto: ' + (r.producto.url_producto ? '<a href="' + r.producto.url_producto + '" target="_blank" class="text-green-500 hover:underline font-medium">' + r.producto.texto_completo + '</a>' : '<strong>' + r.producto.texto_completo + '</strong>') + '</span>' + htmlBotonesProductoFilaCrearMasivo(r.producto.id) + '</div>' : '')}
                        ${selectorEmpateHtml}
                        <div class="spec-and-ofertas-container">${buscadorProductoHtml}${especsHtml}
                        ${ofertasExistentesHtml}</div>
                    </div>
                    ${puedeCrear || necesitaProducto || noEntreOpciones || puedeDescartar ? `
                    <div class="acciones-url-wrap flex-shrink-0 flex items-start gap-3">
                        ${btnDescartarHtml}
                        ${necesitaProducto || noEntreOpciones ? '<div class="flex flex-col items-stretch gap-2">' + btnAgregarProductoHtml + btnCategoriaFilaHtml + '</div>' : ''}
                        ${puedeCrear ? `
                        <div class="generar-oferta-wrap flex flex-col items-start gap-2">
                            <button type="button" class="btn-generar inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded transition"
                                data-url="${r.url_normalizada || r.url}"
                                data-producto-id="${r.producto.id}"
                                data-tienda-id="${r.tienda.id}">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                                Generar oferta
                            </button>
                            <label class="inline-flex flex-col gap-0.5 text-xs text-gray-600 dark:text-gray-300 shrink-0">
                                <span>Envío (€)</span>
                                <input type="text" class="cm-envio-oferta-input w-[5.5rem] px-2 py-1.5 rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm" value="${escapeHtmlCrearMasivo(textoEnvioInputDesdeRowCrearMasivo(r))}" autocomplete="off" inputmode="decimal" />
                            </label>
                            ${permitirCheckPrecioCero ? `
                            <label class="generar-sin-precio-wrap inline-flex items-start gap-2 cursor-pointer text-xs text-amber-700 dark:text-amber-300">
                                <input type="checkbox" class="generar-sin-precio-cb mt-0.5 rounded border-gray-300 text-amber-600 focus:ring-amber-500">
                                <span>Precio 0</span>
                            </label>
                            ` : ''}
                        </div>
                        ` : ''}
                    </div>
                    ` : ''}
                </div>
                ${puedeCrear ? '<div class="mt-2 generado-msg text-sm font-medium text-gray-600 dark:text-gray-400">URL pendiente de añadir</div>' : '<div class="mt-2 generado-msg hidden text-sm font-medium"></div>'}
            `;

            const claveGr = claveGrupoProductoSugeridoCrearMasivo(r);
            const filasEnEsteGrupo = conteosGrupo.get(claveGr) || 1;
            const envolverGrupo = claveGr.startsWith('p:') && filasEnEsteGrupo > 1;
            if (envolverGrupo) {
                if (claveGr !== claveGrupoWrapperAbierto) {
                    claveGrupoWrapperAbierto = claveGr;
                    nodoWrapperGrupo = document.createElement('div');
                    nodoWrapperGrupo.className = 'crear-masivo-grupo-producto rounded-xl border-2 p-4 space-y-3 ' + CREAR_MASIVO_GRUPO_BORDE_CLASSES[indiceEstiloGrupo % CREAR_MASIVO_GRUPO_BORDE_CLASSES.length];
                    nodoWrapperGrupo.dataset.grupoProductoId = String(r.producto.id);
                    const etiquetaGrupo = document.createElement('div');
                    etiquetaGrupo.className = 'text-xs font-bold uppercase tracking-wide text-gray-800 dark:text-gray-100 -mb-1 pb-2 border-b-2 border-gray-400/70 dark:border-gray-500';
                    etiquetaGrupo.textContent = 'Mismo producto sugerido (' + filasEnEsteGrupo + ' URL' + (filasEnEsteGrupo !== 1 ? 's' : '') + '): ' + (r.producto.texto_completo || ('#' + r.producto.id));
                    nodoWrapperGrupo.appendChild(etiquetaGrupo);
                    indiceEstiloGrupo++;
                    contenedor.appendChild(nodoWrapperGrupo);
                }
                nodoWrapperGrupo.appendChild(div);
            } else {
                claveGrupoWrapperAbierto = null;
                nodoWrapperGrupo = null;
                contenedor.appendChild(div);
            }

            div.__rowData = r;
            renderUrlResaltadaFilaCrearMasivo(div);
            if (puedeCrear) actualizarEstadoBotonGenerar(div);
            if (r.producto && r.tiene_especificaciones) {
                setTimeout(function() {
                    actualizarConteosOpcionesEspecsFila(div);
                    actualizarAvisosSinImagenEspecsFila(div);
                }, 50);
            }

            const especsMarcadasAuto = !r.producto_asignado_desde_neo
                ? ((r.especificaciones_marcadas_chatgpt && typeof r.especificaciones_marcadas_chatgpt === 'object')
                    ? r.especificaciones_marcadas_chatgpt
                    : ((r.especificaciones_marcadas && typeof r.especificaciones_marcadas === 'object') ? r.especificaciones_marcadas : null))
                : null;
            if (especsMarcadasAuto) {
                aplicarEspecificacionesMarcadasEnFilaCrearMasivo(div, especsMarcadasAuto);
            }

            // Re-render al final del ciclo por si otros procesos del render afectan checkboxes.
            setTimeout(() => renderUrlResaltadaFilaCrearMasivo(div), 0);

            if (puedeCrear && !r.tiene_especificaciones) {
                setTimeout(function() { buscarOfertasMismasEspecsYMostrar(div); }, 300);
            }

            div.addEventListener('change', function(e) {
                if (e.target.classList.contains('cb-descartar-url')) {
                    const btnDescartar = div.querySelector('.btn-descartar-url');
                    if (btnDescartar) {
                        btnDescartar.classList.toggle('hidden', !e.target.checked);
                    }
                    return;
                }
                if (e.target.classList.contains('confirmo-no-es-misma')) {
                    actualizarEstadoBotonGenerar(div);
                    return;
                }
                if (!e.target.classList.contains('spec-checkbox')) return;
                actualizarEstadoBotonGenerar(div);
                renderUrlResaltadaFilaCrearMasivo(div);
                actualizarConteosOpcionesEspecsFila(div);
                actualizarAvisosSinImagenEspecsFila(div);
                clearTimeout(div.__mismasEspecsTimeout);
                div.__mismasEspecsTimeout = setTimeout(() => buscarOfertasMismasEspecsYMostrar(div), 400);
            });

            div.addEventListener('click', function(e) {
                const btnQuitar = e.target.closest('.btn-quitar-producto');
                if (btnQuitar) {
                    e.preventDefault();
                    quitarProductoYMostrarBuscador(div);
                    return;
                }
                const btnImgProd = e.target.closest('.btn-ver-imagenes-producto-crear-masivo');
                if (btnImgProd) {
                    e.preventDefault();
                    (async function() {
                        let imgs = (div.__rowData && div.__rowData.producto && Array.isArray(div.__rowData.producto.imagenes_producto))
                            ? div.__rowData.producto.imagenes_producto.slice() : [];
                        const pid = div.__rowData && div.__rowData.producto && div.__rowData.producto.id;
                        if ((!imgs || imgs.length === 0) && pid) {
                            try {
                                const urlRec = '{{ route("admin.ofertas.crear-masivo.recargar-especificaciones", ["producto" => "__ID__"]) }}'.replace('__ID__', pid);
                                const resRec = await fetch(urlRec, { headers: { 'Accept': 'application/json' } });
                                const dataRec = await resRec.json();
                                if (dataRec.success && Array.isArray(dataRec.imagenes_producto)) {
                                    imgs = dataRec.imagenes_producto;
                                    if (div.__rowData && div.__rowData.producto) div.__rowData.producto.imagenes_producto = imgs;
                                }
                            } catch (err) { console.error(err); }
                        }
                        abrirModalImagenesProductoCrearMasivo(imgs);
                    })();
                    return;
                }
                const btnVerPrompt = e.target.closest('.btn-ver-prompt-crear-masivo');
                if (btnVerPrompt && div.__rowData && div.__rowData.chatgpt_prompt) {
                    abrirModalVerPromptCrearMasivo(div.__rowData);
                    return;
                }
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
                    return;
                }
                const btnDescartar = e.target.closest('.btn-descartar-url');
                if (btnDescartar && btnDescartar.dataset.url) {
                    e.preventDefault();
                    abrirModalDescartarUrlCrearMasivo(btnDescartar.dataset.url, div);
                    return;
                }
                const btnCatFila = e.target.closest('.btn-elegir-categoria-fila-crear-masivo');
                if (btnCatFila) {
                    e.preventDefault();
                    abrirModalCategoriaFilaCrearMasivo(div);
                    return;
                }
                const btnSlugPalabra = e.target.closest('.btn-slug-palabra-crear-masivo');
                if (btnSlugPalabra) {
                    e.preventDefault();
                    const palabra = String(btnSlugPalabra.dataset.palabra || '').trim();
                    const searchInput = div.querySelector('.producto-search-input');
                    if (!palabra || !searchInput) return;
                    const actual = searchInput.value.trim();
                    searchInput.value = actual ? (actual + ' ' + palabra) : palabra;
                    searchInput.focus();
                    searchInput.dispatchEvent(new Event('input', { bubbles: true }));
                    return;
                }
                const sugItem = e.target.closest('.producto-sugerencia-item-crear-masivo');
                if (sugItem && sugItem.dataset.producto) {
                    e.preventDefault();
                    try {
                        const producto = JSON.parse(sugItem.dataset.producto);
                        aplicarProductoDesdeBusqueda(div, producto);
                    } catch (err) { console.error(err); }
                }
            });

            if (necesitaProducto || noEntreOpciones) {
                const searchInput = div.querySelector('.producto-search-input');
                let timeoutBusqueda = null;
                if (searchInput) {
                    actualizarToolbarCategoriaFila(div);
                    searchInput.addEventListener('input', function() {
                        clearTimeout(timeoutBusqueda);
                        const query = this.value.trim();
                        if (query.length < 2) {
                            ocultarSugerenciasCrearMasivo(div);
                            return;
                        }
                        timeoutBusqueda = setTimeout(() => buscarProductosCrearMasivo(div, query), 300);
                    });
                    searchInput.addEventListener('focus', function() {
                        const sug = div.querySelector('.producto-sugerencias-crear-masivo');
                        if (sug && div.__productosBusqueda && div.__productosBusqueda.length) {
                            sug.classList.remove('hidden');
                            posicionarSugerenciasProductoCrearMasivo(div);
                        }
                    });
                    searchInput.addEventListener('blur', function() {
                        setTimeout(() => ocultarSugerenciasCrearMasivo(div), 200);
                    });
                    searchInput.addEventListener('keydown', function(e) {
                        const sug = div.querySelector('.producto-sugerencias-crear-masivo');
                        if (!sug || sug.classList.contains('hidden')) return;
                        const productos = div.__productosBusqueda || [];
                        if (productos.length === 0) return;
                        let idx = div.__indiceSeleccionadoProducto ?? 0;
                        if (e.key === 'ArrowDown') {
                            e.preventDefault();
                            idx = Math.min(idx + 1, productos.length - 1);
                            div.__indiceSeleccionadoProducto = idx;
                            actualizarHighlightSugerenciasProducto(div);
                        } else if (e.key === 'ArrowUp') {
                            e.preventDefault();
                            idx = Math.max(idx - 1, 0);
                            div.__indiceSeleccionadoProducto = idx;
                            actualizarHighlightSugerenciasProducto(div);
                        } else if (e.key === 'Enter') {
                            e.preventDefault();
                            seleccionarProductoPorIndice(div);
                        }
                    });
                }
            }

            if (puedeCrear && r.hay_empate && r.candidatos_empatados && !r.producto_asignado_desde_neo) {
                div.__candidatosEmpatados = r.candidatos_empatados;
            }

            if (puedeCrear) {
                adjuntarListenerGenerarOfertaCrearMasivo(div);
            }
        });
        actualizarVisibilidadFooterSiguienteNeo();
        iniciarFlujoModalUrlsCrearMasivo();
    }

    function buscadorProductoEnModalUrlCrearMasivo(div) {
        const modalContenido = document.getElementById('modal-url-crear-masivo-contenido');
        return !!(modalContenido && div && modalContenido.contains(div));
    }

    /** Evita que overflow-y-auto del modal recorte el desplegable de sugerencias. */
    function posicionarSugerenciasProductoCrearMasivo(div) {
        const cont = div.querySelector('.producto-sugerencias-crear-masivo');
        const input = div.querySelector('.producto-search-input');
        if (!cont || !input || cont.classList.contains('hidden')) return;
        if (!buscadorProductoEnModalUrlCrearMasivo(div)) {
            restablecerSugerenciasProductoCrearMasivo(div);
            return;
        }
        const rect = input.getBoundingClientRect();
        const gap = 4;
        const maxHDefecto = 240;
        cont.dataset.sugerenciasModo = 'fixed-modal';
        cont.classList.remove('absolute', 'left-0', 'right-0', 'mt-1');
        cont.classList.add('fixed', 'shadow-xl');
        cont.style.left = Math.max(8, rect.left) + 'px';
        cont.style.width = Math.min(rect.width, window.innerWidth - 16) + 'px';
        cont.style.right = 'auto';
        cont.style.zIndex = '80';
        const espacioAbajo = window.innerHeight - rect.bottom - gap;
        const espacioArriba = rect.top - gap;
        if (espacioAbajo < 100 && espacioArriba > espacioAbajo) {
            const altura = Math.min(maxHDefecto, Math.max(80, espacioArriba - 8));
            cont.style.top = 'auto';
            cont.style.bottom = (window.innerHeight - rect.top + gap) + 'px';
            cont.style.maxHeight = altura + 'px';
        } else {
            const altura = Math.min(maxHDefecto, Math.max(80, espacioAbajo - 8));
            cont.style.top = (rect.bottom + gap) + 'px';
            cont.style.bottom = 'auto';
            cont.style.maxHeight = altura + 'px';
        }
    }

    function restablecerSugerenciasProductoCrearMasivo(div) {
        const cont = div && div.querySelector ? div.querySelector('.producto-sugerencias-crear-masivo') : null;
        if (!cont || cont.dataset.sugerenciasModo !== 'fixed-modal') return;
        delete cont.dataset.sugerenciasModo;
        cont.classList.remove('fixed', 'shadow-xl');
        cont.classList.add('absolute', 'left-0', 'right-0', 'mt-1');
        cont.style.left = '';
        cont.style.top = '';
        cont.style.right = '';
        cont.style.bottom = '';
        cont.style.width = '';
        cont.style.maxHeight = '';
        cont.style.zIndex = '';
    }

    async function buscarProductosCrearMasivo(div, query) {
        try {
            const row = div.__rowData || {};
            let urlBus = `{{ route('admin.ofertas.buscar.productos') }}?q=${encodeURIComponent(query)}`;
            const catIdFiltro = obtenerCategoriaIdFiltroBusquedaProductoCrearMasivo(row);
            if (catIdFiltro) {
                urlBus += '&categoria_id=' + encodeURIComponent(String(catIdFiltro));
            }
            const res = await fetch(urlBus);
            const productos = await res.json();
            div.__productosBusqueda = Array.isArray(productos) ? productos : [];
            mostrarSugerenciasProductoCrearMasivo(div);
        } catch (err) {
            console.error(err);
            div.__productosBusqueda = [];
            ocultarSugerenciasCrearMasivo(div);
        }
    }

    function mostrarSugerenciasProductoCrearMasivo(div) {
        const cont = div.querySelector('.producto-sugerencias-crear-masivo');
        if (!cont) return;
        const productos = div.__productosBusqueda || [];
        div.__indiceSeleccionadoProducto = 0;
        cont.innerHTML = '';
        if (productos.length === 0) {
            cont.innerHTML = '<div class="px-4 py-2 text-gray-500 dark:text-gray-400 text-sm">No se encontraron productos</div>';
            div.__indiceSeleccionadoProducto = -1;
        } else {
            productos.forEach((p, i) => {
                const el = document.createElement('div');
                el.className = 'producto-sugerencia-item-crear-masivo px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 cursor-pointer text-gray-900 dark:text-white border-b border-gray-200 dark:border-gray-600 last:border-b-0 text-sm' + (i === 0 ? ' bg-blue-100 dark:bg-blue-700' : '');
                el.textContent = p.texto_completo;
                el.dataset.producto = JSON.stringify(p);
                el.dataset.index = String(i);
                cont.appendChild(el);
            });
        }
        cont.classList.remove('hidden');
        requestAnimationFrame(function() { posicionarSugerenciasProductoCrearMasivo(div); });
    }

    function actualizarHighlightSugerenciasProducto(div) {
        const cont = div.querySelector('.producto-sugerencias-crear-masivo');
        if (!cont) return;
        const idx = div.__indiceSeleccionadoProducto ?? -1;
        const items = cont.querySelectorAll('.producto-sugerencia-item-crear-masivo');
        items.forEach((el, i) => {
            el.classList.toggle('bg-blue-100', i === idx);
            el.classList.toggle('dark:bg-blue-700', i === idx);
        });
        if (idx >= 0 && items[idx]) items[idx].scrollIntoView({ block: 'nearest', behavior: 'smooth' });
        posicionarSugerenciasProductoCrearMasivo(div);
    }

    function seleccionarProductoPorIndice(div) {
        const productos = div.__productosBusqueda || [];
        const idx = div.__indiceSeleccionadoProducto ?? -1;
        if (idx >= 0 && idx < productos.length) {
            aplicarProductoDesdeBusqueda(div, productos[idx]);
        }
    }

    function ocultarSugerenciasCrearMasivo(div) {
        const cont = div.querySelector('.producto-sugerencias-crear-masivo');
        if (cont) cont.classList.add('hidden');
        restablecerSugerenciasProductoCrearMasivo(div);
    }

    function quitarProductoYMostrarBuscador(div) {
        if (div.__rowData?.ofertaGenerada) return;
        const r = div.__rowData;
        if (!r || !r.tienda) return;
        if (r.producto && r.producto.id) limpiarCacheImagenesSublineaProductoCrearMasivo(r.producto.id);
        div.__rowData = Object.assign({}, r, { producto: null, especificaciones: null, tiene_especificaciones: false });
        div.dataset.esUnidadUnica = '0';
        div.dataset.columnasIds = '[]';
        div.classList.remove('bg-green-50', 'dark:bg-green-900/20', 'border-green-200', 'dark:border-green-700');
        div.classList.add('bg-amber-50', 'dark:bg-amber-900/20', 'border', 'border-amber-200', 'dark:border-amber-700');
        div.querySelector('.font-medium').textContent = 'Producto no encontrado. Busca uno manualmente:';
        const productDisplay = div.querySelector('.producto-display');
        if (productDisplay) productDisplay.remove();
        const specParent = div.querySelector('.spec-and-ofertas-container');
        if (specParent) {
            const ofertasHtml = r.ofertas_existentes && r.ofertas_existentes.length
                ? '<div class="mt-2 text-sm text-amber-700 dark:text-amber-300 space-y-1"><span class="font-medium">Existe en:</span>' + r.ofertas_existentes.map(o => {
                    const prodLink = o.url_producto ? '<a href="' + o.url_producto + '" target="_blank" class="text-green-500 hover:underline font-medium">' + (o.producto || '') + '</a>' : (o.producto || '');
                    const verBtn = o.url ? ' <a href="' + o.url + '" target="_blank" class="inline-flex items-center px-2 py-0.5 bg-gray-600 hover:bg-gray-700 text-white text-xs font-medium rounded">Ver</a>' : '';
                    const editarBtn = o.oferta_edit_url ? ' <a href="' + o.oferta_edit_url + '" target="_blank" class="inline-flex items-center px-2 py-0.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium rounded">Editar oferta</a>' : '';
                    return '<div>' + prodLink + ' — ' + (o.tienda || '') + verBtn + editarBtn + '</div>';
                }).join('') + '</div>' : '';
            specParent.innerHTML = buildBuscadorProductoHtmlCrearMasivo(r.url_normalizada || r.url || '', r.categoria_fila || null) + ofertasHtml;
            actualizarToolbarCategoriaFila(div);
        }
        const btnGenerarWrap = div.querySelector('.acciones-url-wrap');
        if (btnGenerarWrap) btnGenerarWrap.remove();
        const topRow = div.querySelector('.flex.justify-between');
        if (topRow) {
            const cbDescartar = div.querySelector('.cb-descartar-url');
            const mostrarBotonDescartar = !!(cbDescartar && cbDescartar.checked);
            const urlFila = (r.url_normalizada || r.url || '').replace(/"/g, '&quot;');
            const btnAgregarWrap = document.createElement('div');
            btnAgregarWrap.className = 'acciones-url-wrap flex-shrink-0 flex items-center gap-3';
            btnAgregarWrap.innerHTML = '<button type="button" class="btn-descartar-url ' + (mostrarBotonDescartar ? '' : 'hidden ') + 'inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded transition" data-url="' + urlFila + '"><svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 7h12M9 7V5h6v2m-7 4v6m4-6v6"></path></svg>Descartar URL</button><div class="flex flex-col items-stretch gap-2"><a href="{{ route("admin.productos.create") }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center justify-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded transition"><svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>Añadir producto</a><button type="button" class="btn-elegir-categoria-fila-crear-masivo inline-flex items-center justify-center px-4 py-2 text-sm font-medium rounded border border-indigo-300 dark:border-indigo-600 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-800 dark:text-indigo-200 hover:bg-indigo-100 dark:hover:bg-indigo-900/50 transition">Cambiar Categoría</button></div>';
            topRow.appendChild(btnAgregarWrap);
        }
        const selectorEmpate = div.querySelector('.btn-elegir-producto')?.closest('.p-3');
        if (selectorEmpate) selectorEmpate.remove();
        const generadoMsg = div.querySelector('.generado-msg');
        if (generadoMsg) { generadoMsg.classList.add('hidden'); generadoMsg.textContent = ''; }
        const searchInput = div.querySelector('.producto-search-input');
        let timeoutBusqueda = null;
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                clearTimeout(timeoutBusqueda);
                const query = this.value.trim();
                if (query.length < 2) {
                    ocultarSugerenciasCrearMasivo(div);
                    return;
                }
                timeoutBusqueda = setTimeout(() => buscarProductosCrearMasivo(div, query), 300);
            });
            searchInput.addEventListener('focus', function() {
                const sug = div.querySelector('.producto-sugerencias-crear-masivo');
                if (sug && div.__productosBusqueda && div.__productosBusqueda.length) {
                    sug.classList.remove('hidden');
                    posicionarSugerenciasProductoCrearMasivo(div);
                }
            });
            searchInput.addEventListener('blur', function() {
                setTimeout(() => ocultarSugerenciasCrearMasivo(div), 200);
            });
            searchInput.addEventListener('keydown', function(e) {
                const sug = div.querySelector('.producto-sugerencias-crear-masivo');
                if (!sug || sug.classList.contains('hidden')) return;
                const productos = div.__productosBusqueda || [];
                if (productos.length === 0) return;
                let idx = div.__indiceSeleccionadoProducto ?? 0;
                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    idx = Math.min(idx + 1, productos.length - 1);
                    div.__indiceSeleccionadoProducto = idx;
                    actualizarHighlightSugerenciasProducto(div);
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    idx = Math.max(idx - 1, 0);
                    div.__indiceSeleccionadoProducto = idx;
                    actualizarHighlightSugerenciasProducto(div);
                } else if (e.key === 'Enter') {
                    e.preventDefault();
                    seleccionarProductoPorIndice(div);
                }
            });
        }
        renderUrlResaltadaFilaCrearMasivo(div);
        limpiarConteosOpcionesEspecsFila(div);
    }

    async function aplicarProductoDesdeBusqueda(div, producto) {
        const r = div.__rowData;
        if (!r || !r.tienda) return;
        ocultarSugerenciasCrearMasivo(div);
        const searchContainer = div.querySelector('.producto-search-container');
        if (searchContainer) searchContainer.remove();
        try {
            const urlFetch = urlRecargarEspecificacionesCrearMasivo(producto.id, r.url_normalizada || r.url);
            const res = await fetch(urlFetch, { headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content } });
            const data = await res.json();
            const especs = data.success ? (data.especificaciones || null) : null;
            const tieneEspecs = data.success && (data.tiene_especificaciones || false);
            const urlProducto = data.url_producto || null;
            const imagenesProducto = Array.isArray(data.imagenes_producto) ? data.imagenes_producto : [];
            const productoCompleto = {
                id: producto.id,
                nombre: producto.nombre,
                marca: producto.marca,
                modelo: producto.modelo,
                talla: producto.talla,
                texto_completo: producto.texto_completo,
                url_producto: urlProducto,
                imagenes_producto: imagenesProducto
            };
            const especsHtml = buildEspecsHtml(productoCompleto, especs, tieneEspecs);
            const specParent = div.querySelector('.spec-and-ofertas-container');
            if (specParent) {
                specParent.innerHTML = especsHtml + (r.ofertas_existentes && r.ofertas_existentes.length ? '<div class="mt-2 text-sm text-amber-700 dark:text-amber-300 space-y-1"><span class="font-medium">Existe en:</span>' + r.ofertas_existentes.map(o => {
                    const prodLink = o.url_producto ? '<a href="' + o.url_producto + '" target="_blank" class="text-green-500 hover:underline font-medium">' + (o.producto || '') + '</a>' : (o.producto || '');
                    const verBtn = o.url ? ' <a href="' + o.url + '" target="_blank" class="inline-flex items-center px-2 py-0.5 bg-gray-600 hover:bg-gray-700 text-white text-xs font-medium rounded">Ver</a>' : '';
                    const editarBtn = o.oferta_edit_url ? ' <a href="' + o.oferta_edit_url + '" target="_blank" class="inline-flex items-center px-2 py-0.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium rounded">Editar oferta</a>' : '';
                    return '<div>' + prodLink + ' — ' + (o.tienda || '') + verBtn + editarBtn + '</div>';
                }).join('') + '</div>' : '');
            }
            div.__rowData = Object.assign({}, r, {
                producto: productoCompleto,
                especificaciones: especs,
                tiene_especificaciones: tieneEspecs,
                producto_asignado_desde_neo: false,
            });
            div.dataset.esUnidadUnica = (especs && especs.unidad_de_medida === 'unidadUnica') ? '1' : '0';
            div.dataset.columnasIds = (especs && especs.columnas_ids) ? JSON.stringify(especs.columnas_ids) : '[]';
            div.classList.remove('bg-amber-50', 'dark:bg-amber-900/20', 'border-amber-200', 'dark:border-amber-700');
            div.classList.add('bg-green-50', 'dark:bg-green-900/20', 'border', 'border-green-200', 'dark:border-green-700');
            div.querySelector('.font-medium').textContent = 'Lista para crear';
            const productLinkHtml = urlProducto
                ? '<a href="' + urlProducto + '" target="_blank" class="text-green-500 hover:underline font-medium">' + producto.texto_completo + '</a>'
                : '<strong>' + producto.texto_completo + '</strong>';
            const accionesProductoHtml = htmlBotonesProductoFilaCrearMasivo(producto.id);
            const productDisplay = div.querySelector('.producto-display');
            if (!productDisplay) {
                const prodDiv = document.createElement('div');
                prodDiv.className = 'mt-1 text-sm text-gray-600 dark:text-gray-400 producto-display flex items-center gap-2 flex-wrap';
                prodDiv.innerHTML = '<span>Producto: ' + productLinkHtml + '</span>' + accionesProductoHtml;
                div.querySelector('.spec-and-ofertas-container').insertAdjacentElement('beforebegin', prodDiv);
            } else {
                productDisplay.className = 'mt-1 text-sm text-gray-600 dark:text-gray-400 producto-display flex items-center gap-2 flex-wrap';
                productDisplay.innerHTML = '<span>Producto: ' + productLinkHtml + '</span>' + accionesProductoHtml;
            }
            const oldActionWrap = div.querySelector('.acciones-url-wrap');
            if (oldActionWrap) oldActionWrap.remove();
            const cbDescartar = div.querySelector('.cb-descartar-url');
            const mostrarBotonDescartar = !!(cbDescartar && cbDescartar.checked);
            const urlFila = (r.url_normalizada || r.url || '').replace(/"/g, '&quot;');
            const tiendaNoMostrar = String((r.tienda && r.tienda.mostrar_tienda) || '').toLowerCase() === 'no';
            const tiendaNoScraping = String((r.tienda && r.tienda.scrapear) || '').toLowerCase() === 'no';
            const permitirCheckPrecioCero = !!r.tienda && !tiendaNoMostrar && !tiendaNoScraping;
            const btnWrap = document.createElement('div');
            btnWrap.className = 'acciones-url-wrap flex-shrink-0 flex items-start gap-3';
            const envioLabelHtml = '<label class="inline-flex flex-col gap-0.5 text-xs text-gray-600 dark:text-gray-300 shrink-0"><span>Envío (€)</span><input type="text" class="cm-envio-oferta-input w-[5.5rem] px-2 py-1.5 rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm" value="' + escapeHtmlCrearMasivo(textoEnvioInputDesdeRowCrearMasivo(r)) + '" autocomplete="off" inputmode="decimal" /></label>';
            btnWrap.innerHTML = '<button type="button" class="btn-descartar-url ' + (mostrarBotonDescartar ? '' : 'hidden ') + 'inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded transition" data-url="' + urlFila + '"><svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 7h12M9 7V5h6v2m-7 4v6m4-6v6"></path></svg>Descartar URL</button><div class="generar-oferta-wrap flex flex-col items-start gap-2"><button type="button" class="btn-generar inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded transition" data-url="' + (r.url_normalizada || r.url) + '" data-producto-id="' + producto.id + '" data-tienda-id="' + r.tienda.id + '"><svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg> Generar oferta</button>' + envioLabelHtml + (permitirCheckPrecioCero ? '<label class="generar-sin-precio-wrap inline-flex items-start gap-2 cursor-pointer text-xs text-amber-700 dark:text-amber-300"><input type="checkbox" class="generar-sin-precio-cb mt-0.5 rounded border-gray-300 text-amber-600 focus:ring-amber-500"><span>Precio 0</span></label>' : '') + '</div>';
            div.querySelector('.flex.justify-between').appendChild(btnWrap);
            const btnGen = div.querySelector('.btn-generar');
            actualizarEstadoBotonGenerar(div);
            ponerMensajeGeneradoPendienteCrearMasivo(div);
            if (!tieneEspecs) {
                setTimeout(function() { buscarOfertasMismasEspecsYMostrar(div); }, 300);
            }
            if (data.success && data.especificaciones_marcadas) {
                aplicarEspecificacionesMarcadasEnFilaCrearMasivo(div, data.especificaciones_marcadas);
            } else {
                renderUrlResaltadaFilaCrearMasivo(div);
                actualizarConteosOpcionesEspecsFila(div);
                actualizarAvisosSinImagenEspecsFila(div);
            }
            adjuntarListenerGenerarOfertaCrearMasivo(div);
        } catch (err) {
            alert('Error al cargar: ' + err.message);
        }
    }

    const iconoRecargarEspecsCrearMasivo = '<svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>';

    function htmlBotonRecargarEspecsCrearMasivo(productoId) {
        if (!productoId) return '';
        return '<button type="button" class="btn-recargar-especs ml-2 inline-flex items-center gap-1 px-2 py-1 text-xs bg-gray-200 hover:bg-gray-300 dark:bg-gray-600 dark:hover:bg-gray-500 text-gray-700 dark:text-gray-200 rounded transition" data-producto-id="' + productoId + '" title="Recargar especificaciones">' + iconoRecargarEspecsCrearMasivo + '<span>Recargar</span></button>';
    }

    const FORMATOS_SPEC_REQUIEREN_IMAGEN_CREAR_MASIVO = ['imagen', 'imagen_texto', 'imagen_precio', 'imagen_texto_precio'];

    function grupoSpecRequiereImagenCrearMasivo(filtro, formatosGrupo) {
        if (!filtro) return false;
        const formato = formatosGrupo[String(filtro.id)] || '';
        return FORMATOS_SPEC_REQUIEREN_IMAGEN_CREAR_MASIVO.includes(formato);
    }

    function opcionSpecTieneImagenesCrearMasivo(div, cb) {
        if (!cb) return true;
        if (cb.dataset.usarImagenesProducto === '1') {
            const imgs = div && div.__rowData && div.__rowData.producto && Array.isArray(div.__rowData.producto.imagenes_producto)
                ? div.__rowData.producto.imagenes_producto
                : [];
            return imgs.length > 0;
        }
        const key = cb.dataset.imagenesKey || '';
        if (key && window.__crearMasivoImagenesSublinea && Array.isArray(window.__crearMasivoImagenesSublinea[key])) {
            return window.__crearMasivoImagenesSublinea[key].length > 0;
        }
        return cb.dataset.tieneImagenes === '1';
    }

    function actualizarAvisosSinImagenEspecsFila(div) {
        if (!div) return;
        div.querySelectorAll('.spec-checkbox').forEach(function(cb) {
            const label = cb.closest('label');
            if (!label) return;
            const aviso = label.querySelector('.spec-sin-imagen-aviso');
            if (!aviso) return;
            const mostrar = cb.checked
                && cb.dataset.requiereImagen === '1'
                && !opcionSpecTieneImagenesCrearMasivo(div, cb);
            aviso.classList.toggle('hidden', !mostrar);
        });
    }

    function buildEspecsHtml(producto, especificaciones, tieneEspecificaciones) {
        if (!producto) return '';
        const filtros = (especificaciones && especificaciones.filtros) ? especificaciones.filtros : [];
        const esUnidadUnica = especificaciones && especificaciones.unidad_de_medida === 'unidadUnica';
        const columnasIds = (especificaciones && especificaciones.columnas_ids) ? especificaciones.columnas_ids : [];
        if (tieneEspecificaciones && filtros.length) {
            const productoId = producto && producto.id ? producto.id : null;
            if (productoId) limpiarCacheImagenesSublineaProductoCrearMasivo(productoId);
            const btnRecargar = htmlBotonRecargarEspecsCrearMasivo(productoId);
            let h = '<div class="mt-3 spec-selector-container"><span class="flex items-center flex-wrap gap-1"><strong class="text-sm text-gray-700 dark:text-gray-300">Especificaciones a marcar:</strong>' + btnRecargar + '</span><div class="mt-2 space-y-3">';
            const imagenesProducto = (producto && Array.isArray(producto.imagenes_producto)) ? producto.imagenes_producto : [];
            const formatosGrupo = (especificaciones && especificaciones.formatos && typeof especificaciones.formatos === 'object')
                ? especificaciones.formatos
                : {};
            filtros.forEach((f) => {
                const subprincipales = f.subprincipales || [];
                const esColumna = esUnidadUnica && columnasIds.includes(f.id);
                const esGrupoProducto = f.es_producto === true;
                const permiteNuevaOpcion = !esGrupoProducto;
                const grupoRequiereImagen = grupoSpecRequiereImagenCrearMasivo(f, formatosGrupo);
                if (subprincipales.length === 0) return;
                const grupoLabelEsc = String(f.texto || f.id || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/"/g, '&quot;');
                h += '<div class="spec-line border-l-2 border-gray-300 dark:border-gray-600 pl-3" data-principal-id="' + f.id + '" data-es-columna="' + (esColumna ? '1' : '0') + '" data-es-producto="' + (esGrupoProducto ? '1' : '0') + '">';
                h += '<label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">' + (f.texto || f.id) + (esColumna ? ' <span class="text-orange-600 dark:text-orange-400 text-xs">(Columna)</span>' : '') + '</label><div class="flex flex-wrap gap-2 items-center">';
                if (permiteNuevaOpcion) {
                    h += '<button type="button" class="btn-toggle-nueva-opcion-cm inline-flex items-center p-1 shrink-0 rounded bg-indigo-600 hover:bg-indigo-700 text-white text-xs transition focus:outline-none focus:ring-2 focus:ring-indigo-400" data-principal-id="' + f.id + '" data-insert-first="1" title="Añadir opción en primera posición"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg></button>';
                }
                subprincipales.forEach((sub) => {
                    let imagenes;
                    if (sub.usar_imagenes_producto) {
                        imagenes = imagenesProducto.slice();
                    } else {
                        imagenes = Array.isArray(sub.imagenes) ? sub.imagenes.slice() : [];
                    }
                    const keyImg = productoId ? cacheKeyImagenesSublineaCrearMasivo(productoId, f.id, sub.id) : (String(f.id) + '::' + String(sub.id));
                    if (imagenes.length) {
                        window.__crearMasivoImagenesSublinea[keyImg] = imagenes;
                    } else {
                        delete window.__crearMasivoImagenesSublinea[keyImg];
                    }
                    const btnImgs = imagenes.length ? '<button type="button" class="btn-ver-imagenes-spec inline-flex items-center p-1 bg-blue-600 hover:bg-blue-700 text-white rounded text-xs ml-0.5" data-key="' + keyImg + '" title="Ver ' + imagenes.length + ' imagen' + (imagenes.length !== 1 ? 'es' : '') + '"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg></button>' : '';
                    const subIdEsc = String(sub.id || '').replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;');
                    const usarImgProd = !!sub.usar_imagenes_producto;
                    const tieneImagenes = imagenes.length > 0;
                    h += '<label class="inline-flex items-center gap-1 cursor-pointer flex-wrap">'
                        + '<input type="checkbox" class="spec-checkbox rounded border-gray-300 text-green-600 focus:ring-green-500"'
                        + ' data-principal-id="' + f.id + '" data-sublinea-id="' + sub.id + '" data-es-columna="' + (esColumna ? '1' : '0') + '"'
                        + ' data-requiere-imagen="' + (grupoRequiereImagen ? '1' : '0') + '"'
                        + ' data-tiene-imagenes="' + (tieneImagenes ? '1' : '0') + '"'
                        + ' data-usar-imagenes-producto="' + (usarImgProd ? '1' : '0') + '"'
                        + ' data-imagenes-key="' + keyImg + '">'
                        + '<span class="spec-option-text text-sm text-gray-600 dark:text-gray-400" data-principal-id="' + f.id + '" data-sublinea-id="' + sub.id + '">' + (sub.texto || sub.id) + '</span>'
                        + '<span class="spec-count-badge text-xs text-gray-500 dark:text-gray-400" data-principal-id="' + f.id + '" data-sublinea-id="' + sub.id + '">(x)</span>'
                        + '<span class="spec-sin-imagen-aviso hidden text-xs font-medium text-amber-600 dark:text-amber-400">(No tiene imagen)</span>'
                        + btnImgs + '</label>';
                    if (permiteNuevaOpcion) {
                        h += '<button type="button" class="btn-toggle-nueva-opcion-cm inline-flex items-center p-1 shrink-0 rounded bg-indigo-600 hover:bg-indigo-700 text-white text-xs transition focus:outline-none focus:ring-2 focus:ring-indigo-400 ml-0.5" data-principal-id="' + f.id + '" data-after-sub-id="' + subIdEsc + '" title="Añadir opción después de esta"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg></button>';
                    }
                });
                h += '</div>';
                if (permiteNuevaOpcion) {
                    h += '<div class="nueva-opcion-cm-panel hidden mt-2 p-3 rounded-lg border border-indigo-200 dark:border-indigo-700 bg-indigo-50/60 dark:bg-indigo-950/30 space-y-2" data-principal-id="' + f.id + '">';
                    h += '<p class="text-xs text-indigo-900 dark:text-indigo-100">Nueva opción en «' + grupoLabelEsc + '»</p>';
                    h += '<input type="text" class="nueva-opcion-cm-texto w-full px-3 py-2 rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm text-gray-900 dark:text-white" placeholder="Nombre de la opción">';
                    h += '<label class="inline-flex items-center gap-2 cursor-pointer text-xs text-gray-700 dark:text-gray-300"><input type="checkbox" class="nueva-opcion-cm-usar-img-prod rounded border-gray-300 text-orange-600 focus:ring-orange-500"><span>Usar imágenes del producto</span></label>';
                    h += '<div class="flex flex-wrap gap-2 items-center nueva-opcion-cm-img-btns">';
                    h += '<button type="button" class="btn-cm-ver-imagenes-draft inline-flex items-center px-2 py-1 text-xs rounded bg-blue-600 hover:bg-blue-700 text-white disabled:opacity-45 disabled:cursor-not-allowed" disabled>Ver imágenes (0)</button>';
                    h += '<button type="button" class="btn-cm-anadir-imagenes-draft inline-flex items-center px-2 py-1 text-xs rounded bg-green-600 hover:bg-green-700 text-white disabled:opacity-45 disabled:cursor-not-allowed">+ Imágenes</button>';
                    h += '</div>';
                    h += '<div class="flex flex-wrap gap-2">';
                    h += '<button type="button" class="btn-cm-guardar-nueva-opcion inline-flex items-center px-3 py-1.5 text-xs font-medium rounded bg-indigo-700 hover:bg-indigo-800 text-white">Guardar y marcar</button>';
                    h += '<button type="button" class="btn-cm-cancelar-nueva-opcion px-3 py-1.5 text-xs rounded border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800">Cancelar</button>';
                    h += '</div>';
                    h += '<p class="nueva-opcion-cm-msg text-xs hidden"></p>';
                    h += '</div>';
                }
                h += '</div>';
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
        btn.disabled = true;
        btn.innerHTML = '<svg class="w-4 h-4 animate-spin inline-block shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg><span class="ml-1">Cargando...</span>';
        try {
            const url = '{{ route("admin.ofertas.crear-masivo.recargar-especificaciones", ["producto" => "__ID__"]) }}'.replace('__ID__', productoId);
            const res = await fetch(url, {
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
            });
            const data = await res.json();
            if (data.success && data.especificaciones !== undefined) {
                const especs = data.especificaciones;
                const tieneEspecs = data.tiene_especificaciones;
                if (div.__rowData && div.__rowData.producto) {
                    if (Array.isArray(data.imagenes_producto)) div.__rowData.producto.imagenes_producto = data.imagenes_producto;
                    if (data.url_producto) div.__rowData.producto.url_producto = data.url_producto;
                }

                function actualizarSpecsEnFila(fila, producto) {
                    const r = fila.__rowData;
                    if (!r || !r.producto || String(r.producto.id) !== String(productoId)) return;
                    const cont = fila.querySelector('.spec-and-ofertas-container');
                    if (!cont) return;
                    const specBlock = cont.querySelector('.spec-selector-container') || Array.from(cont.children).find(function(c) { return c.textContent.trim().indexOf('Sin especificaciones') !== -1; });
                    if (!specBlock) return;
                    const prod = producto || Object.assign({}, r.producto);
                    const newHtml = buildEspecsHtml(prod, especs, tieneEspecs);
                    specBlock.outerHTML = newHtml;
                    fila.__rowData = Object.assign({}, r, { especificaciones: especs, tiene_especificaciones: tieneEspecs });
                    fila.dataset.esUnidadUnica = (especs && especs.unidad_de_medida === 'unidadUnica') ? '1' : '0';
                    fila.dataset.columnasIds = (especs && especs.columnas_ids) ? JSON.stringify(especs.columnas_ids) : '[]';
                    actualizarEstadoBotonGenerar(fila);
                    renderUrlResaltadaFilaCrearMasivo(fila);
                    actualizarConteosOpcionesEspecsFila(fila);
                    actualizarAvisosSinImagenEspecsFila(fila);
                }

                const r = div.__rowData;
                const producto = Object.assign({}, r.producto);
                actualizarSpecsEnFila(div, producto);

                var contenedor = document.getElementById('resultadosLista');
                if (contenedor) {
                    Array.from(contenedor.children).forEach(function(otraFila) {
                        if (otraFila !== div) actualizarSpecsEnFila(otraFila, null);
                    });
                }
            } else {
                alert('No se pudieron recargar las especificaciones');
            }
        } catch (err) {
            alert('Error: ' + err.message);
        } finally {
            btn.disabled = false;
            btn.innerHTML = iconoRecargarEspecsCrearMasivo + '<span>Recargar</span>';
        }
    }

    async function aplicarProductoSeleccionado(div, candidato) {
        const r = div.__rowData;
        if (!r || !r.tienda) return;
        try {
            const urlFetch = urlRecargarEspecificacionesCrearMasivo(candidato.id, r.url_normalizada || r.url);
            const res = await fetch(urlFetch, { headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content } });
            const data = await res.json();
            const especs = data.success ? (data.especificaciones || null) : candidato.especificaciones;
            const tieneEspecs = data.success ? (data.tiene_especificaciones || false) : (candidato.tiene_especificaciones || false);
            const urlProducto = data.url_producto ?? candidato.url_producto ?? null;
            const imagenesProducto = Array.isArray(data.imagenes_producto) ? data.imagenes_producto : [];
            const productoCompleto = {
                id: candidato.id,
                nombre: candidato.nombre,
                marca: candidato.marca,
                modelo: candidato.modelo,
                talla: candidato.talla,
                texto_completo: candidato.texto_completo,
                url_producto: urlProducto,
                imagenes_producto: imagenesProducto
            };
            const especsHtml = buildEspecsHtml(productoCompleto, especs, tieneEspecs);
            const specParent = div.querySelector('.spec-and-ofertas-container');
            if (specParent) {
                const ofertasHtml = (r.ofertas_existentes && r.ofertas_existentes.length) ? '<div class="mt-2 text-sm text-amber-700 dark:text-amber-300 space-y-1"><span class="font-medium">Existe en:</span>' + r.ofertas_existentes.map(o => {
                    const prodLink = o.url_producto ? '<a href="' + o.url_producto + '" target="_blank" class="text-green-500 hover:underline font-medium">' + (o.producto || '') + '</a>' : (o.producto || '');
                    const verBtn = o.url ? ' <a href="' + o.url + '" target="_blank" class="inline-flex items-center px-2 py-0.5 bg-gray-600 hover:bg-gray-700 text-white text-xs font-medium rounded">Ver</a>' : '';
                    const editarBtn = o.oferta_edit_url ? ' <a href="' + o.oferta_edit_url + '" target="_blank" class="inline-flex items-center px-2 py-0.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium rounded">Editar oferta</a>' : '';
                    return '<div>' + prodLink + ' — ' + (o.tienda || '') + verBtn + editarBtn + '</div>';
                }).join('') + '</div>' : '';
                specParent.innerHTML = especsHtml + ofertasHtml;
            }
            div.dataset.esUnidadUnica = (especs && especs.unidad_de_medida === 'unidadUnica') ? '1' : '0';
            div.dataset.columnasIds = (especs && especs.columnas_ids) ? JSON.stringify(especs.columnas_ids) : '[]';
            div.__rowData = Object.assign({}, r, {
                producto: productoCompleto,
                especificaciones: especs,
                tiene_especificaciones: tieneEspecs,
                producto_asignado_desde_neo: false,
            });
            const productDisplay = div.querySelector('.producto-display');
            const productLinkHtml = urlProducto ? '<a href="' + urlProducto + '" target="_blank" class="text-green-500 hover:underline font-medium">' + candidato.texto_completo + '</a>' : '<strong>' + candidato.texto_completo + '</strong>';
            const accionesProductoHtmlB = htmlBotonesProductoFilaCrearMasivo(candidato.id);
            if (productDisplay) {
                productDisplay.className = 'mt-1 text-sm text-gray-600 dark:text-gray-400 producto-display flex items-center gap-2 flex-wrap';
                productDisplay.innerHTML = '<span>Producto: ' + productLinkHtml + '</span>' + accionesProductoHtmlB;
            } else {
                const prodDiv = document.createElement('div');
                prodDiv.className = 'mt-1 text-sm text-gray-600 dark:text-gray-400 producto-display flex items-center gap-2 flex-wrap';
                prodDiv.innerHTML = '<span>Producto: ' + productLinkHtml + '</span>' + accionesProductoHtmlB;
                const specContainer = div.querySelector('.spec-and-ofertas-container');
                if (specContainer) specContainer.insertAdjacentElement('beforebegin', prodDiv);
            }
            const btnGen = div.querySelector('.btn-generar');
            if (btnGen) btnGen.dataset.productoId = candidato.id;
            actualizarValorEnvioInputEnFilaCrearMasivo(div);
            const selectorEmpate = div.querySelector('.btn-elegir-producto')?.closest('.p-3');
            if (selectorEmpate) selectorEmpate.remove();
            if (!tieneEspecs) {
                setTimeout(function() { buscarOfertasMismasEspecsYMostrar(div); }, 300);
            }
            if (data.success && data.especificaciones_marcadas) {
                aplicarEspecificacionesMarcadasEnFilaCrearMasivo(div, data.especificaciones_marcadas);
            } else {
                renderUrlResaltadaFilaCrearMasivo(div);
                actualizarConteosOpcionesEspecsFila(div);
                actualizarAvisosSinImagenEspecsFila(div);
            }
            if (div.querySelector('.btn-generar') && !div.__rowData?.ofertaGenerada) {
                ponerMensajeGeneradoPendienteCrearMasivo(div);
            }
        } catch (err) {
            console.error(err);
            alert('Error al cargar: ' + err.message);
        }
    }

    function quitarCheckboxNoEsMisma(div) {
        const wrap = div.querySelector('.confirmo-no-es-misma-wrap');
        if (wrap) wrap.remove();
    }

    function agregarCheckboxNoEsMisma(div) {
        const btnGen = div.querySelector('.btn-generar');
        const wrap = div.querySelector('.confirmo-no-es-misma-wrap');
        if (!btnGen || wrap) return;
        const parent = btnGen.closest('.flex-shrink-0');
        if (!parent) return;
        parent.classList.add('flex', 'items-center', 'gap-3');
        const goWrap = btnGen.closest('.generar-oferta-wrap');
        if (!goWrap) return;
        const label = document.createElement('label');
        label.className = 'confirmo-no-es-misma-wrap flex items-center gap-2 cursor-pointer text-sm text-amber-700 dark:text-amber-300';
        label.innerHTML = '<input type="checkbox" class="confirmo-no-es-misma rounded border-gray-300 text-amber-600 focus:ring-amber-500"> <span>No es la misma oferta</span>';
        const precioWrap = goWrap.querySelector('.generar-sin-precio-wrap');
        if (precioWrap) {
            goWrap.insertBefore(label, precioWrap);
        } else {
            goWrap.appendChild(label);
        }
    }

    function actualizarEstadoBotonGenerar(div) {
        const btn = div.querySelector('.btn-generar');
        if (!btn || div.__rowData?.ofertaGenerada) return;
        const specLines = div.querySelectorAll('.spec-line');
        let specsIncomplete = false;
        if (specLines.length) {
            specsIncomplete = Array.from(specLines).some(line => !line.querySelector('.spec-checkbox:checked'));
        }
        const hasOfertasBlock = !!div.querySelector('.ofertas-mismas-especs');
        const checkboxChecked = !!div.querySelector('.confirmo-no-es-misma:checked');
        const shouldDisable = specsIncomplete || (hasOfertasBlock && !checkboxChecked);
        btn.disabled = shouldDisable;
        btn.classList.toggle('opacity-50', shouldDisable);
        btn.classList.toggle('cursor-not-allowed', shouldDisable);
        btn.classList.toggle('bg-green-600', !shouldDisable);
        btn.classList.toggle('hover:bg-green-700', !shouldDisable);
        btn.classList.toggle('bg-gray-400', shouldDisable);
        btn.classList.toggle('hover:bg-gray-400', shouldDisable);
    }

    function limpiarConteosOpcionesEspecsFila(div) {
        div.querySelectorAll('.spec-count-badge').forEach((badge) => {
            badge.textContent = '(x)';
        });
        div.querySelectorAll('.spec-option-text').forEach((el) => {
            el.classList.remove(
                'bg-yellow-200', 'dark:bg-yellow-700/60', 'text-yellow-900', 'dark:text-yellow-100',
                'bg-orange-300', 'dark:bg-orange-600/80', 'text-orange-950', 'dark:text-orange-50',
                'text-green-700', 'dark:text-green-300', 'font-semibold', 'px-0.5', 'rounded', 'border', 'border-orange-500/70'
            );
        });
    }

    function aplicarConteosOpcionesEspecsFila(div, conteos) {
        const counts = (conteos && typeof conteos === 'object') ? conteos : {};
        const maxPorGrupo = {};
        const gruposConSeleccion = new Set();
        div.querySelectorAll('.spec-checkbox:checked').forEach((cb) => {
            if (cb.dataset && cb.dataset.principalId) {
                gruposConSeleccion.add(String(cb.dataset.principalId));
            }
        });
        Object.keys(counts).forEach((principalId) => {
            const grupo = counts[principalId];
            if (!grupo || typeof grupo !== 'object') return;
            const vals = Object.values(grupo).map(v => parseInt(v, 10)).filter(v => Number.isFinite(v));
            if (vals.length > 0) maxPorGrupo[principalId] = Math.max(...vals);
        });

        div.querySelectorAll('.spec-count-badge').forEach((badge) => {
            const pid = String(badge.dataset.principalId || '');
            const sid = String(badge.dataset.sublineaId || '');
            const n = (counts[pid] && counts[pid][sid] != null) ? parseInt(counts[pid][sid], 10) : 0;
            badge.textContent = '(' + (Number.isFinite(n) ? n : 0) + ')';
        });

        div.querySelectorAll('.spec-option-text').forEach((el) => {
            const pid = String(el.dataset.principalId || '');
            const sid = String(el.dataset.sublineaId || '');
            const n = (counts[pid] && counts[pid][sid] != null) ? parseInt(counts[pid][sid], 10) : 0;
            const max = maxPorGrupo[pid] ?? null;
            const cb = div.querySelector('.spec-checkbox[data-principal-id="' + pid + '"][data-sublinea-id="' + sid + '"]');
            const estaMarcada = !!(cb && cb.checked);
            const grupoYaMarcado = gruposConSeleccion.has(pid);
            const esTop = Number.isFinite(n) && max !== null && max > 0 && n === max;
            const sugerirVerde = !grupoYaMarcado && !estaMarcada && esTop;

            // Si está marcada, siempre naranja (igual que coincidencia de URL por especificación)
            el.classList.toggle('bg-orange-300', estaMarcada);
            el.classList.toggle('dark:bg-orange-600/80', estaMarcada);
            el.classList.toggle('text-orange-950', estaMarcada);
            el.classList.toggle('dark:text-orange-50', estaMarcada);
            el.classList.toggle('border', estaMarcada);
            el.classList.toggle('border-orange-500/70', estaMarcada);
            el.classList.toggle('px-0.5', estaMarcada || sugerirVerde);
            el.classList.toggle('rounded', estaMarcada || sugerirVerde);

            // Sugerencia dominante: solo texto verde, sin fondo, y solo en grupos aún no marcados.
            el.classList.toggle('text-green-700', sugerirVerde);
            el.classList.toggle('dark:text-green-300', sugerirVerde);
            el.classList.toggle('font-semibold', estaMarcada || sugerirVerde);

            // Limpiar estilos amarillos antiguos.
            el.classList.remove('bg-yellow-200', 'dark:bg-yellow-700/60', 'text-yellow-900', 'dark:text-yellow-100');
        });
    }

    async function actualizarConteosOpcionesEspecsFila(div) {
        if (!div || !div.__rowData || !div.__rowData.producto || div.__rowData.ofertaGenerada) return;
        const specLines = div.querySelectorAll('.spec-line');
        if (!specLines.length) {
            limpiarConteosOpcionesEspecsFila(div);
            return;
        }
        const body = { producto_id: div.__rowData.producto.id };
        const especs = buildEspecificacionesFromRow(div);
        if (especs) body.especificaciones_internas = JSON.stringify(especs);

        const reqId = (div.__conteosReqId || 0) + 1;
        div.__conteosReqId = reqId;
        try {
            const res = await fetch('{{ route("admin.ofertas.crear-masivo.contar-opciones-especificaciones") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
                body: JSON.stringify(body),
            });
            const data = await res.json().catch(() => ({}));
            if (div.__conteosReqId !== reqId) return; // Evita pisar con respuestas viejas.
            if (!data.success) {
                limpiarConteosOpcionesEspecsFila(div);
                return;
            }
            aplicarConteosOpcionesEspecsFila(div, data.conteos || {});
        } catch (e) {
            console.error('[ConteosEspecs] Error:', e);
            if (div.__conteosReqId === reqId) limpiarConteosOpcionesEspecsFila(div);
        }
    }

    async function buscarOfertasMismasEspecsYMostrar(div) {
        if (!div.__rowData || !div.__rowData.producto || !div.__rowData.tienda || div.__rowData.ofertaGenerada) return;
        const tieneEspecs = div.__rowData.tiene_especificaciones;
        const specLines = div.querySelectorAll('.spec-line');

        if (tieneEspecs && specLines.length) {
            let todosConSeleccion = true;
            specLines.forEach(line => { if (!line.querySelector('.spec-checkbox:checked')) todosConSeleccion = false; });
            if (!todosConSeleccion) {
                const container = div.querySelector('.spec-and-ofertas-container');
                const old = container?.querySelector('.ofertas-mismas-especs');
                if (old) old.remove();
                quitarCheckboxNoEsMisma(div);
                actualizarEstadoBotonGenerar(div);
                return;
            }
        }

        const especs = tieneEspecs && specLines.length ? buildEspecificacionesFromRow(div) : null;
        if (tieneEspecs && specLines.length && !especs) return;

        const body = { producto_id: div.__rowData.producto.id, tienda_id: div.__rowData.tienda.id };
        if (especs) body.especificaciones_internas = JSON.stringify(especs);

        try {
            const res = await fetch('{{ route("admin.ofertas.crear-masivo.buscar-mismas-especificaciones") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' },
                body: JSON.stringify(body),
            });
            const data = await res.json().catch(() => ({}));
            const container = div.querySelector('.spec-and-ofertas-container');
            if (!container) return;
            const old = container.querySelector('.ofertas-mismas-especs');
            if (old) { old.remove(); quitarCheckboxNoEsMisma(div); }
            if (data.success && Array.isArray(data.ofertas) && data.ofertas.length) {
                const titulo = tieneEspecs ? 'Ya existen ofertas con estas especificaciones en esta tienda:' : 'Ofertas existentes de este producto en esta tienda:';
                const html = '<div class="mt-3 ofertas-mismas-especs text-sm text-amber-700 dark:text-amber-300"><span class="font-medium">' + titulo + '</span>' +
                    data.ofertas.map(o => {
                        const envioTxt = o.envio != null ? (parseFloat(o.envio).toFixed(2).replace('.', ',') + ' € env.') : 'envío gratis';
                        const precioTxt = o.precio_unidad != null ? (parseFloat(o.precio_unidad).toFixed(2).replace('.', ',') + ' €/ud') : '';
                        const info = (precioTxt ? precioTxt + ' · ' : '') + envioTxt;
                        const ver = o.url ? ' <a href="' + o.url + '" target="_blank" class="inline-flex items-center px-2 py-0.5 bg-gray-600 hover:bg-gray-700 text-white text-xs font-medium rounded">Ver</a>' : '';
                        const editar = o.oferta_edit_url ? ' <a href="' + o.oferta_edit_url + '" target="_blank" class="inline-flex items-center px-2 py-0.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium rounded">Editar oferta</a>' : '';
                        return '<div class="mt-1">' + info + ver + editar + '</div>';
                    }).join('') + '</div>';
                container.insertAdjacentHTML('beforeend', html);
                agregarCheckboxNoEsMisma(div);
            }
            actualizarEstadoBotonGenerar(div);
        } catch (e) { console.error('[MismasEspecs] Error:', e); actualizarEstadoBotonGenerar(div); }
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

    function cmActualizarPreviewInternaCm(inputId, imgId) {
        const inp = document.getElementById(inputId);
        const img = document.getElementById(imgId);
        if (!inp || !img) return;
        const u = resolverUrlImagenCrearMasivo(inp.value);
        if (!u) {
            img.removeAttribute('src');
            img.classList.add('hidden');
            img.classList.remove('ring-2', 'ring-red-400');
            return;
        }
        img.classList.remove('ring-2', 'ring-red-400');
        img.src = u;
        img.classList.remove('hidden');
        img.onerror = function() { img.classList.add('ring-2', 'ring-red-400'); };
        img.onload = function() { img.classList.remove('ring-2', 'ring-red-400'); };
    }

    function cmEnlazarPreviewsInternasCm() {
        [['ruta-interna-grande-sublinea-cm', 'preview-interna-grande-sublinea-cm'], ['ruta-interna-pequena-sublinea-cm', 'preview-interna-pequena-sublinea-cm']].forEach(function(pair) {
            const el = document.getElementById(pair[0]);
            if (!el || el.dataset.kpPreviewBound) return;
            el.dataset.kpPreviewBound = '1';
            const fn = function() { cmActualizarPreviewInternaCm(pair[0], pair[1]); };
            el.addEventListener('input', fn);
            el.addEventListener('paste', function() { setTimeout(fn, 0); });
        });
    }

    function abrirModalImagenesSpecCrearMasivo(key) {
        const modal = document.getElementById('modal-imagenes-spec-crear-masivo');
        const titulo = document.getElementById('modal-imagenes-crear-masivo-titulo');
        const sub = document.getElementById('modal-imagenes-crear-masivo-subtitulo');
        if (titulo) titulo.textContent = 'Imágenes de la especificación';
        if (sub) sub.textContent = 'Haz clic en una miniatura para verla en grande';
        if (!modal) return;
        const imagenes = (window.__crearMasivoImagenesSublinea && window.__crearMasivoImagenesSublinea[key]) ? window.__crearMasivoImagenesSublinea[key] : [];
        window.__crearMasivoImagenesActual = { key, imagenes: Array.isArray(imagenes) ? imagenes : [] };
        renderizarMiniaturasSpecCrearMasivo();
        modal.classList.remove('hidden');
    }

    function abrirModalImagenesProductoCrearMasivo(imagenes) {
        const modal = document.getElementById('modal-imagenes-spec-crear-masivo');
        const titulo = document.getElementById('modal-imagenes-crear-masivo-titulo');
        const sub = document.getElementById('modal-imagenes-crear-masivo-subtitulo');
        if (titulo) titulo.textContent = 'Imágenes del producto';
        if (sub) sub.textContent = 'Rutas de imagen grande e imagen pequeña del producto. Haz clic en una miniatura para verla en grande.';
        if (!modal) return;
        window.__crearMasivoImagenesActual = { key: 'producto', imagenes: Array.isArray(imagenes) ? imagenes : [] };
        renderizarMiniaturasSpecCrearMasivo();
        modal.classList.remove('hidden');
    }

    function abrirModalVerPromptCrearMasivo(r) {
        const modal = document.getElementById('modal-ver-prompt-crear-masivo');
        const contPeticion = document.getElementById('modal-ver-prompt-contenido-peticion');
        const contRespuesta = document.getElementById('modal-ver-prompt-contenido-respuesta');
        if (!modal || !contPeticion || !contRespuesta) return;
        const prompt = r.chatgpt_prompt || '';
        let htmlPeticion = '';
        const urlMatch = prompt.match(/URL índice \d+:\s*(https?:\/\/[^\s\n]+)/);
        if (urlMatch) {
            htmlPeticion += '<div class="mb-3"><span class="font-medium text-gray-600 dark:text-gray-400">URL:</span><br><a href="' + urlMatch[1].replace(/"/g, '&quot;') + '" target="_blank" class="text-blue-500 hover:underline break-all">' + urlMatch[1].replace(/</g, '&lt;').replace(/>/g, '&gt;') + '</a></div>';
        }
        let candidatos = null;
        const idxCand = prompt.indexOf('Productos candidatos:');
        if (idxCand !== -1) {
            const idxBracket = prompt.indexOf('[', idxCand);
            if (idxBracket !== -1) {
                let depth = 0, endIdx = idxBracket;
                for (let i = idxBracket; i < prompt.length; i++) {
                    if (prompt[i] === '[') depth++;
                    else if (prompt[i] === ']') { depth--; if (depth === 0) { endIdx = i + 1; break; } }
                }
                try {
                    candidatos = JSON.parse(prompt.substring(idxBracket, endIdx));
                } catch (err) { candidatos = null; }
            }
        }
        if (candidatos && Array.isArray(candidatos)) {
            htmlPeticion += '<div class="font-medium text-gray-600 dark:text-gray-400 mb-2">Productos candidatos:</div><div class="space-y-3">';
            candidatos.forEach(function(c) {
                let especs = '';
                if (c.especificaciones && typeof c.especificaciones === 'object') {
                    especs = '<div class="mt-1.5 pl-2 border-l-2 border-gray-200 dark:border-gray-600 text-gray-600 dark:text-gray-400">';
                    Object.keys(c.especificaciones).forEach(function(grupo) {
                        const opciones = c.especificaciones[grupo];
                        especs += '<div class="text-xs"><strong>' + grupo + ':</strong> ' + (Array.isArray(opciones) ? opciones.join(', ') : opciones) + '</div>';
                    });
                    especs += '</div>';
                }
                htmlPeticion += '<div class="p-3 rounded-lg border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-800"><div><span class="font-medium">ID ' + (c.id || '') + '</span> — ' + (c.nombre || '').replace(/</g, '&lt;').replace(/>/g, '&gt;') + '</div><div class="text-xs text-gray-500 dark:text-gray-400">' + (c.marca || '') + ' · ' + (c.modelo || '') + (c.talla ? ' · ' + c.talla : '') + '</div>' + especs + '</div>';
            });
            htmlPeticion += '</div>';
        } else {
            htmlPeticion += '<pre class="p-3 rounded bg-gray-100 dark:bg-gray-800 text-xs overflow-x-auto whitespace-pre-wrap break-words max-h-60 overflow-y-auto">' + (prompt || '').replace(/</g, '&lt;').replace(/>/g, '&gt;') + '</pre>';
        }
        contPeticion.innerHTML = htmlPeticion;

        const parsed = r.chatgpt_parsed && r.chatgpt_parsed[0] ? r.chatgpt_parsed[0] : null;
        let htmlRespuesta = '';
        if (parsed) {
            htmlRespuesta += '<div class="grid gap-2"><div><span class="font-medium text-gray-600 dark:text-gray-400">Producto elegido (ID):</span> ' + (parsed.producto_id != null ? parsed.producto_id : '—') + '</div>';
            htmlRespuesta += '<div><span class="font-medium text-gray-600 dark:text-gray-400">No entre opciones:</span> ' + (parsed.no_entre_opciones ? 'Sí' : 'No') + '</div>';
            if (parsed.especificaciones && typeof parsed.especificaciones === 'object' && Object.keys(parsed.especificaciones).length) {
                htmlRespuesta += '<div class="mt-2"><span class="font-medium text-gray-600 dark:text-gray-400">Especificaciones:</span><ul class="mt-1 list-disc list-inside space-y-0.5">';
                Object.keys(parsed.especificaciones).forEach(function(k) {
                    htmlRespuesta += '<li><strong>' + k.replace(/</g, '&lt;') + ':</strong> ' + String(parsed.especificaciones[k]).replace(/</g, '&lt;').replace(/>/g, '&gt;') + '</li>';
                });
                htmlRespuesta += '</ul></div>';
            }
            htmlRespuesta += '</div>';
        }
        if (r.chatgpt_respuesta_raw) {
            htmlRespuesta += '<details class="mt-3"><summary class="cursor-pointer text-gray-500 dark:text-gray-400 text-xs">Ver JSON crudo</summary><pre class="mt-1 p-2 rounded bg-gray-100 dark:bg-gray-800 text-xs overflow-x-auto whitespace-pre-wrap break-words max-h-40 overflow-y-auto">' + (r.chatgpt_respuesta_raw || '').replace(/</g, '&lt;').replace(/>/g, '&gt;') + '</pre></details>';
        }
        contRespuesta.innerHTML = htmlRespuesta || '<p class="text-gray-500 dark:text-gray-400">Sin respuesta parseada</p>';
        modal.classList.remove('hidden');
    }

    function abrirModalDescartarUrlCrearMasivo(url, div) {
        const modal = document.getElementById('modal-descartar-url-crear-masivo');
        const texto = document.getElementById('modal-descartar-url-texto');
        if (!modal || !texto) return;
        descartarUrlPendienteCrearMasivo = url;
        descartarFilaPendienteCrearMasivo = div || null;
        texto.textContent = url || '';
        modal.classList.remove('hidden');
    }

    function cerrarModalDescartarUrlCrearMasivo() {
        const modal = document.getElementById('modal-descartar-url-crear-masivo');
        if (modal) modal.classList.add('hidden');
        descartarUrlPendienteCrearMasivo = null;
        descartarFilaPendienteCrearMasivo = null;
    }

    function aplicarEstadoFilaDescartadaCrearMasivo(div) {
        if (!div) return;
        div.classList.remove(
            'bg-green-50',
            'dark:bg-green-900/20',
            'border-green-200',
            'dark:border-green-700',
            'bg-red-50',
            'dark:bg-red-900/20',
            'border-red-200',
            'dark:border-red-700'
        );
        div.classList.add('bg-amber-50', 'dark:bg-amber-900/20', 'border', 'border-amber-200', 'dark:border-amber-700');
        const estadoEl = div.querySelector('.font-medium');
        if (estadoEl) estadoEl.textContent = 'URL descartada';
        const toggle = div.querySelector('.descartar-toggle-wrap');
        if (toggle) toggle.remove();
        const actionWrap = div.querySelector('.acciones-url-wrap');
        if (actionWrap) actionWrap.remove();
        const btnQuitar = div.querySelector('.btn-quitar-producto');
        if (btnQuitar) btnQuitar.remove();
        const btnImgProd = div.querySelector('.btn-ver-imagenes-producto-crear-masivo');
        if (btnImgProd) btnImgProd.remove();
        const msgEl = div.querySelector('.generado-msg');
        if (msgEl) {
            msgEl.classList.remove('hidden');
            msgEl.className = 'mt-2 generado-msg text-sm font-medium text-green-600 dark:text-green-400';
            msgEl.textContent = 'URL descartada correctamente.';
        }
        if (div.__rowData) {
            div.__rowData.descartada = true;
            div.__rowData.ofertaGenerada = true;
        }
    }

    async function confirmarDescartarUrlCrearMasivo() {
        const url = descartarUrlPendienteCrearMasivo;
        const div = descartarFilaPendienteCrearMasivo;
        if (!url) return;
        const btnConfirmar = document.getElementById('modal-descartar-url-confirmar');
        if (btnConfirmar) {
            btnConfirmar.disabled = true;
            btnConfirmar.classList.add('opacity-70', 'cursor-not-allowed');
            btnConfirmar.textContent = 'Descartando...';
        }
        try {
            const res = await fetch('{{ route("admin.neo.crear-masivo.descartar-url") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ url }),
            });
            const data = await res.json().catch(() => ({}));
            if (!res.ok || !data.success) {
                throw new Error(data.message || data.error || 'No se pudo descartar la URL');
            }
            cerrarModalDescartarUrlCrearMasivo();
            aplicarEstadoFilaDescartadaCrearMasivo(div);
            const flujo = window.__flujoModalUrlsCrearMasivo;
            if (flujo && !flujo.modoFinal && div === flujo.filaActualEnModal) {
                avanzarASiguienteUrlEnFlujoModalCrearMasivo();
            }
        } catch (err) {
            if (div) {
                const msgEl = div.querySelector('.generado-msg');
                if (msgEl) {
                    msgEl.classList.remove('hidden');
                    msgEl.className = 'mt-2 generado-msg text-sm font-medium text-red-600 dark:text-red-400';
                    msgEl.textContent = 'Error al descartar URL: ' + err.message;
                }
            }
        } finally {
            if (btnConfirmar) {
                btnConfirmar.disabled = false;
                btnConfirmar.classList.remove('opacity-70', 'cursor-not-allowed');
                btnConfirmar.textContent = 'Aceptar y descartar';
            }
        }
    }

    function cerrarModalVerPromptCrearMasivo() {
        const modal = document.getElementById('modal-ver-prompt-crear-masivo');
        if (modal) modal.classList.add('hidden');
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

    window.__cmNuevaOpcionPanelDraft = null;
    let cropperSublineaCm = null;
    let cropperAmazonCm = null;
    let colaRecorteAmazonCm = [];
    let totalRecorteAmazonCm = 0;
    let carpetaActualAmazonCm = '';
    let modoRecorteAmazonCm = false;
    let carpetaActualSublineaCm = null;
    let imagenesAmazonSeleccionadasSublineaCm = [];
    const KP_PENDING_CM = '__pending:';
    const uploadsPendientesCm = new Map();

    if (!window.__kpSubirParejaConProgreso) {
        window.__kpSubirParejaConProgreso = function(url, fdG, fdP, csrfToken, onProgress, xhrRegistryMap, uploadId) {
            let pg = 0, pp = 0;
            const emit = function() {
                if (onProgress) onProgress(Math.min(100, Math.round((pg + pp) / 2)));
            };
            const entry = { xhrG: null, xhrP: null };
            if (xhrRegistryMap && uploadId) xhrRegistryMap.set(uploadId, entry);
            const one = function(fd, tag) {
                return new Promise(function(resolve, reject) {
                    const xhr = new XMLHttpRequest();
                    if (tag === 'G') entry.xhrG = xhr;
                    else entry.xhrP = xhr;
                    xhr.open('POST', url);
                    xhr.setRequestHeader('X-CSRF-TOKEN', csrfToken);
                    xhr.setRequestHeader('Accept', 'application/json');
                    xhr.upload.onprogress = function(e) {
                        if (e.lengthComputable) {
                            const pct = Math.round(100 * e.loaded / e.total);
                            if (tag === 'G') pg = pct;
                            else pp = pct;
                            emit();
                        }
                    };
                    xhr.onload = function() {
                        let data;
                        try { data = JSON.parse(xhr.responseText); } catch (e) {
                            reject(new Error('Respuesta inválida'));
                            return;
                        }
                        if (xhr.status >= 200 && xhr.status < 300 && data.success) resolve(data);
                        else reject(new Error((data && data.message) || 'Error al subir'));
                    };
                    xhr.onerror = function() { reject(new Error('Error de red')); };
                    xhr.send(fd);
                });
            };
            return Promise.all([one(fdG, 'G'), one(fdP, 'P')]).then(function(results) {
                if (xhrRegistryMap && uploadId) xhrRegistryMap.delete(uploadId);
                return { dataG: results[0], dataP: results[1] };
            }).catch(function(err) {
                if (xhrRegistryMap && uploadId) xhrRegistryMap.delete(uploadId);
                throw err;
            });
        };
    }

    function cmNuevoIdSubida() {
        return Date.now().toString(36) + Math.random().toString(36).slice(2, 9);
    }

    function cmEsRutaPendiente(ruta) {
        return typeof ruta === 'string' && ruta.indexOf(KP_PENDING_CM) === 0;
    }

    function cmCancelarSubida(uploadId) {
        const x = uploadsPendientesCm.get(uploadId);
        if (!x) return;
        if (x.xhrG) try { x.xhrG.abort(); } catch (e) {}
        if (x.xhrP) try { x.xhrP.abort(); } catch (e) {}
        uploadsPendientesCm.delete(uploadId);
    }

    function cmQuitarPendienteDelDraft(panel, pendingPath) {
        if (!panel || !Array.isArray(panel.__cmDraftImagenes)) return;
        const ix = panel.__cmDraftImagenes.indexOf(pendingPath);
        if (ix !== -1) {
            panel.__cmDraftImagenes.splice(ix, 1);
            renderizarMiniaturasSublineaCm();
            actualizarBotonesImagenDraftCm(panel);
        }
    }

    function resolverContextoProductoCm(el) {
        const node = (el && el.closest) ? el : null;
        if (!node) return { fila: null, productoId: null };
        const fila = node.closest('.crear-masivo-fila');
        if (fila && fila.__rowData && fila.__rowData.producto && fila.__rowData.producto.id) {
            return { fila: fila, productoId: fila.__rowData.producto.id };
        }
        const wrap = node.closest('.mismo-producto-spec-wrapper');
        if (wrap && window.__mismoProductoSeleccionado && window.__mismoProductoSeleccionado.id) {
            return { fila: wrap, productoId: window.__mismoProductoSeleccionado.id };
        }
        return { fila: null, productoId: null };
    }

    function nombreBaseArchivoCm(productoId) {
        return 'p' + (productoId || '0') + '-cm';
    }

    function actualizarBotonesImagenDraftCm(panel) {
        if (!panel) return;
        const draft = panel.__cmDraftImagenes;
        const n = Array.isArray(draft) ? draft.length : 0;
        const usar = panel.querySelector('.nueva-opcion-cm-usar-img-prod');
        const usarOn = usar && usar.checked;
        const btnVer = panel.querySelector('.btn-cm-ver-imagenes-draft');
        const btnAdd = panel.querySelector('.btn-cm-anadir-imagenes-draft');
        const btnGr = panel.querySelector('.btn-cm-guardar-nueva-opcion');
        const haySubidaPendiente = !usarOn && Array.isArray(draft) && draft.some(cmEsRutaPendiente);
        if (btnVer) {
            btnVer.textContent = 'Ver imágenes (' + n + ')';
            btnVer.disabled = usarOn || n === 0;
        }
        if (btnAdd) btnAdd.disabled = !!usarOn;
        if (btnGr && !btnGr.dataset.cmGuardandoOpcion) {
            btnGr.disabled = !!haySubidaPendiente;
            if (haySubidaPendiente) {
                btnGr.setAttribute('title', 'Espera a que terminen de subirse todas las imágenes.');
            } else {
                btnGr.removeAttribute('title');
            }
        }
    }

    function resetPanelNuevaOpcionCm(panel) {
        if (!panel) return;
        panel.__cmInsertAfterSubId = null;
        panel.__cmInsertFirst = false;
        const t = panel.querySelector('.nueva-opcion-cm-texto');
        if (t) t.value = '';
        const u = panel.querySelector('.nueva-opcion-cm-usar-img-prod');
        if (u) u.checked = false;
        panel.__cmDraftImagenes = [];
        const msg = panel.querySelector('.nueva-opcion-cm-msg');
        if (msg) { msg.classList.add('hidden'); msg.textContent = ''; }
        actualizarBotonesImagenDraftCm(panel);
    }

    function cerrarPanelNuevaOpcionCm(panel) {
        if (!panel) return;
        panel.classList.add('hidden');
        resetPanelNuevaOpcionCm(panel);
    }

    window.cerrarModalImagenesSublineaCm = function() {
        const m = document.getElementById('modal-imagenes-sublinea-cm');
        if (m) m.classList.add('hidden');
    };

    window.cerrarModalAñadirImagenSublineaCm = function() {
        const m = document.getElementById('modal-añadir-imagen-sublinea-cm');
        if (m) m.classList.add('hidden');
        limpiarModalAñadirSublineaCm();
    };

    function limpiarModalAñadirSublineaCm(opciones) {
        opciones = opciones || {};
        const mantenerCarpetas = !!opciones.mantenerCarpetas;
        if (!mantenerCarpetas) {
            ['carpeta-subir-sublinea-cm', 'carpeta-url-sublinea-cm', 'carpeta-amazon-sublinea-cm'].forEach(function(id) {
                const s = document.getElementById(id);
                if (s) s.value = '';
            });
        }
        const f = document.getElementById('file-subir-sublinea-cm');
        if (f) f.value = '';
        const u = document.getElementById('url-imagen-sublinea-cm');
        if (u) u.value = '';
        const a = document.getElementById('url-amazon-sublinea-cm');
        if (a) a.value = '';
        const n = document.getElementById('nombre-archivo-sublinea-cm');
        if (n) n.textContent = '';
        const e1 = document.getElementById('error-url-sublinea-cm');
        if (e1) e1.classList.add('hidden');
        const e2 = document.getElementById('error-amazon-sublinea-cm');
        if (e2) e2.classList.add('hidden');
        const ld = document.getElementById('loading-amazon-sublinea-cm');
        if (ld) ld.classList.add('hidden');
        const ia = document.getElementById('imagenes-amazon-sublinea-cm');
        if (ia) ia.classList.add('hidden');
        const g = document.getElementById('grid-imagenes-amazon-sublinea-cm');
        if (g) g.innerHTML = '';
        const ar = document.getElementById('area-recorte-sublinea-cm');
        if (ar) ar.classList.add('hidden');
        const arAmz = document.getElementById('area-recorte-amazon-sublinea-cm');
        if (arAmz) arAmz.classList.add('hidden');
        imagenesAmazonSeleccionadasSublineaCm = [];
        resetEstadoRecorteAmazonCm();
        if (cropperSublineaCm) {
            cropperSublineaCm.destroy();
            cropperSublineaCm = null;
        }
        const riGc = document.getElementById('ruta-interna-grande-sublinea-cm');
        const riPc = document.getElementById('ruta-interna-pequena-sublinea-cm');
        if (riGc) riGc.value = '';
        if (riPc) riPc.value = '';
        cmActualizarPreviewInternaCm('ruta-interna-grande-sublinea-cm', 'preview-interna-grande-sublinea-cm');
        cmActualizarPreviewInternaCm('ruta-interna-pequena-sublinea-cm', 'preview-interna-pequena-sublinea-cm');
    }

    /** Tras subir una imagen con éxito: limpia el formulario pero deja el modal abierto en la misma pestaña (carpetas conservadas). */
    function prepararModalAnadirImagenParaOtraCm(tab) {
        const m = document.getElementById('modal-añadir-imagen-sublinea-cm');
        if (m) m.classList.remove('hidden');
        limpiarModalAñadirSublineaCm({ mantenerCarpetas: true });
        cambiarTabModalSublineaCm(tab || 'url', { skipCargarCarpetas: tab === 'amazon' });
        if (tab === 'url') {
            const cv = document.getElementById('carpeta-url-sublinea-cm');
            if (cv && cv.value) carpetaActualSublineaCm = cv.value;
        }
    }

    function cmObtenerPalabrasNombreProducto() {
        const panel = window.__cmNuevaOpcionPanelDraft;
        if (panel) {
            const ctx = resolverContextoProductoCm(panel);
            if (ctx.fila && ctx.fila.__rowData && ctx.fila.__rowData.producto && ctx.fila.__rowData.producto.nombre) {
                return window.kpPartirNombreEnPalabras(ctx.fila.__rowData.producto.nombre);
            }
        }
        if (window.__mismoProductoSeleccionado && window.__mismoProductoSeleccionado.nombre) {
            return window.kpPartirNombreEnPalabras(window.__mismoProductoSeleccionado.nombre);
        }
        return [];
    }

    function cmInferirThumbnailDesdeGrande(ruta) {
        if (!ruta || typeof ruta !== 'string') return '';
        const t = ruta.trim();
        const lastDot = t.lastIndexOf('.');
        if (lastDot === -1) return t + '-thumbnail';
        const base = t.slice(0, lastDot);
        const ext = t.slice(lastDot);
        if (base.endsWith('-thumbnail')) return t;
        return base + '-thumbnail' + ext;
    }

    function kpModalImgTabSetActive(tabs, activeEl) {
        (tabs || []).forEach(function(t) {
            if (!t) return;
            var on = t === activeEl;
            t.classList.toggle('kp-modal-img-tab--active', on);
            t.setAttribute('aria-selected', on ? 'true' : 'false');
        });
    }

    function cambiarTabModalSublineaCm(tab, opts) {
        opts = opts || {};
        const tabUrl = document.getElementById('tab-url-sublinea-cm');
        const tabSubir = document.getElementById('tab-subir-sublinea-cm');
        const tabAmazon = document.getElementById('tab-amazon-sublinea-cm');
        const tabInterna = document.getElementById('tab-interna-sublinea-cm');
        const tabInternaGlobal = document.getElementById('tab-interna-global-sublinea-cm');
        const allTabs = [tabUrl, tabSubir, tabAmazon, tabInterna, tabInternaGlobal];
        const contents = ['content-url-sublinea-cm', 'content-subir-sublinea-cm', 'content-amazon-sublinea-cm', 'content-interna-sublinea-cm', 'content-interna-global-cm'];
        contents.forEach(function(id) {
            const c = document.getElementById(id);
            if (c) c.classList.add('hidden');
        });
        if (tab === 'url') {
            kpModalImgTabSetActive(allTabs, tabUrl);
            const c = document.getElementById('content-url-sublinea-cm');
            if (c) c.classList.remove('hidden');
        } else if (tab === 'subir') {
            kpModalImgTabSetActive(allTabs, tabSubir);
            const c = document.getElementById('content-subir-sublinea-cm');
            if (c) c.classList.remove('hidden');
        } else if (tab === 'amazon') {
            kpModalImgTabSetActive(allTabs, tabAmazon);
            const c = document.getElementById('content-amazon-sublinea-cm');
            if (c) c.classList.remove('hidden');
            if (!opts.skipCargarCarpetas) cargarCarpetasModalSublineaCm();
        } else if (tab === 'interna') {
            kpModalImgTabSetActive(allTabs, tabInterna);
            const c = document.getElementById('content-interna-sublinea-cm');
            if (c) c.classList.remove('hidden');
            cmEnlazarPreviewsInternasCm();
        } else if (tab === 'interna-global') {
            kpModalImgTabSetActive(allTabs, tabInternaGlobal);
            const c = document.getElementById('content-interna-global-cm');
            if (c) c.classList.remove('hidden');
            if (typeof window.kpInternaGlobalAlActivar === 'function') {
                window.kpInternaGlobalAlActivar('cm');
            }
        }
    }

    function actualizarSelectCarpetasSublineaCm(selectId, carpetas) {
        const select = document.getElementById(selectId);
        if (!select) return;
        const primera = document.createElement('option');
        primera.value = '';
        primera.textContent = 'Selecciona una carpeta';
        select.innerHTML = '';
        select.appendChild(primera);
        let tieneProducto = false;
        (carpetas || []).forEach(function(carpeta) {
            const o = document.createElement('option');
            o.value = carpeta;
            o.textContent = carpeta.charAt(0).toUpperCase() + carpeta.slice(1);
            select.appendChild(o);
            if (String(carpeta).toLowerCase() === 'producto') tieneProducto = true;
        });
        if (tieneProducto) select.value = 'producto';
    }

    function cargarCarpetasModalSublineaCm() {
        fetch(@json(route('admin.imagenes.carpetas'))).then(function(r) { return r.json(); }).then(function(data) {
            if (data.success && data.data && data.data.length > 0) {
                actualizarSelectCarpetasSublineaCm('carpeta-subir-sublinea-cm', data.data);
                actualizarSelectCarpetasSublineaCm('carpeta-url-sublinea-cm', data.data);
                actualizarSelectCarpetasSublineaCm('carpeta-amazon-sublinea-cm', data.data);
            }
        }).catch(function() {});
    }

    function configurarDragMiniaturasCm(container, panel) {
        let arrastrado = null;
        container.querySelectorAll('.miniatura-sublinea-cm').forEach(function(mini) {
            mini.addEventListener('dragstart', function(e) {
                arrastrado = mini;
                mini.style.opacity = '0.5';
                e.dataTransfer.effectAllowed = 'move';
            });
            mini.addEventListener('dragend', function() { mini.style.opacity = '1'; arrastrado = null; });
            mini.addEventListener('dragover', function(e) {
                e.preventDefault();
                if (!arrastrado || arrastrado === mini) return;
                const all = Array.from(container.querySelectorAll('.miniatura-sublinea-cm'));
                const di = all.indexOf(arrastrado);
                const ti = all.indexOf(mini);
                if (di < ti) container.insertBefore(arrastrado, mini.nextSibling);
                else container.insertBefore(arrastrado, mini);
            });
            mini.addEventListener('drop', function(e) {
                e.preventDefault();
                if (!arrastrado || !panel.__cmDraftImagenes) return;
                const all = Array.from(container.querySelectorAll('.miniatura-sublinea-cm'));
                panel.__cmDraftImagenes = all.map(function(el) {
                    const ix = parseInt(el.dataset.index, 10);
                    return panel.__cmDraftImagenes[ix];
                }).filter(Boolean);
                renderizarMiniaturasSublineaCm();
                actualizarBotonesImagenDraftCm(panel);
            });
        });
    }

    function renderizarMiniaturasSublineaCm() {
        const panel = window.__cmNuevaOpcionPanelDraft;
        if (!panel) return;
        const imagenes = Array.isArray(panel.__cmDraftImagenes) ? panel.__cmDraftImagenes : [];
        const container = document.getElementById('miniaturas-container-sublinea-cm');
        const imgGrande = document.getElementById('imagen-grande-sublinea-cm');
        if (!container || !imgGrande) return;
        container.innerHTML = '';
        if (imagenes.length === 0) {
            imgGrande.src = '';
            container.innerHTML = '<p class="text-sm text-gray-500">No hay imágenes</p>';
            return;
        }
        const primeraCm = imagenes[0];
        if (cmEsRutaPendiente(primeraCm)) {
            imgGrande.src = '';
        } else {
            imgGrande.src = resolverUrlImagenCrearMasivo(primeraCm);
        }
        imagenes.forEach(function(imgPath, index) {
            const div = document.createElement('div');
            div.className = 'miniatura-sublinea-cm relative group cursor-pointer border-2 border-gray-300 dark:border-gray-600 rounded p-1';
            div.dataset.index = String(index);
            const pendiente = cmEsRutaPendiente(imgPath);
            div.draggable = !pendiente;
            if (index === 0) div.classList.add('border-blue-500');
            const url = resolverUrlImagenCrearMasivo(imgPath);
            const uidCm = pendiente ? imgPath.slice(KP_PENDING_CM.length) : '';
            if (pendiente) {
                div.innerHTML = '<div class="w-full h-20 flex flex-col items-center justify-center bg-gray-100 dark:bg-gray-700 rounded p-1"><span class="text-[10px] text-gray-600 dark:text-gray-300 text-center leading-tight">Cargando imagen…</span><div class="w-full mt-1 h-1.5 bg-gray-200 dark:bg-gray-600 rounded overflow-hidden"><div id="kp-prog-cm-' + uidCm + '" class="h-full bg-blue-500 transition-[width] duration-150" style="width:0%"></div></div></div><button type="button" class="absolute top-0 right-0 bg-red-600 text-white rounded-full w-5 h-5 text-xs btn-eliminar-imagen-cm" data-index="' + index + '">&times;</button>';
            } else {
                div.innerHTML = '<img src="' + url + '" alt="" class="w-full h-20 object-cover rounded"><button type="button" class="absolute top-0 right-0 bg-red-600 text-white rounded-full w-5 h-5 text-xs btn-eliminar-imagen-cm" data-index="' + index + '">&times;</button>';
            }
            div.addEventListener('click', function(ev) {
                if (ev.target.closest('.btn-eliminar-imagen-cm')) return;
                if (pendiente) return;
                imgGrande.src = url;
                container.querySelectorAll('.miniatura-sublinea-cm').forEach(function(m) { m.classList.remove('border-blue-500'); });
                div.classList.add('border-blue-500');
            });
            div.querySelector('.btn-eliminar-imagen-cm').addEventListener('click', function(ev) {
                ev.stopPropagation();
                if (!confirm('¿Eliminar esta imagen del borrador?')) return;
                const ix = parseInt(div.dataset.index, 10);
                const pathDel = panel.__cmDraftImagenes[ix];
                if (pathDel && cmEsRutaPendiente(pathDel)) {
                    cmCancelarSubida(pathDel.slice(KP_PENDING_CM.length));
                }
                panel.__cmDraftImagenes.splice(ix, 1);
                renderizarMiniaturasSublineaCm();
                actualizarBotonesImagenDraftCm(panel);
            });
            container.appendChild(div);
        });
        configurarDragMiniaturasCm(container, panel);
    }

    function abrirModalVerImagenesDraftCm(panel) {
        window.__cmNuevaOpcionPanelDraft = panel;
        renderizarMiniaturasSublineaCm();
        const m = document.getElementById('modal-imagenes-sublinea-cm');
        if (m) m.classList.remove('hidden');
    }

    function abrirModalAnadirImagenDraftCm(panel) {
        window.__cmNuevaOpcionPanelDraft = panel;
        limpiarModalAñadirSublineaCm();
        const m = document.getElementById('modal-añadir-imagen-sublinea-cm');
        if (m) m.classList.remove('hidden');
        cmEnlazarPreviewsInternasCm();
        cargarCarpetasModalSublineaCm();
        cambiarTabModalSublineaCm('url');
    }

    function añadirRutaImagenDraftCm(ruta) {
        const panel = window.__cmNuevaOpcionPanelDraft;
        if (!panel || !ruta) return;
        if (!Array.isArray(panel.__cmDraftImagenes)) panel.__cmDraftImagenes = [];
        panel.__cmDraftImagenes.push(ruta);
        actualizarBotonesImagenDraftCm(panel);
    }

    function resetEstadoRecorteAmazonCm() {
        colaRecorteAmazonCm = [];
        totalRecorteAmazonCm = 0;
        carpetaActualAmazonCm = '';
        modoRecorteAmazonCm = false;
        const area = document.getElementById('area-recorte-amazon-sublinea-cm');
        const busqueda = document.getElementById('amazon-busqueda-sublinea-cm');
        if (area) area.classList.add('hidden');
        if (busqueda) busqueda.classList.remove('hidden');
        if (cropperAmazonCm) {
            cropperAmazonCm.destroy();
            cropperAmazonCm = null;
        }
    }

    function mostrarRecorteAmazonCm(urlImagen) {
        const areaRecorte = document.getElementById('area-recorte-amazon-sublinea-cm');
        const img = document.getElementById('imagen-recortar-amazon-sublinea-cm');
        if (!areaRecorte || !img) return;
        areaRecorte.classList.remove('hidden');
        if (cropperAmazonCm) { cropperAmazonCm.destroy(); cropperAmazonCm = null; }
        const urlProxy = urlImagen.startsWith('http')
            ? (@json(route('admin.imagenes.proxy'))) + '?url=' + encodeURIComponent(urlImagen)
            : urlImagen;
        img.crossOrigin = 'anonymous';
        img.src = urlProxy;
        img.onload = function() {
            if (cropperAmazonCm) cropperAmazonCm.destroy();
            cropperAmazonCm = new Cropper(img, {
                aspectRatio: NaN,
                viewMode: 1,
                dragMode: 'move',
                autoCropArea: 0.8,
                restore: false,
                guides: true,
                center: true,
                highlight: false,
                cropBoxMovable: true,
                cropBoxResizable: true,
                toggleDragModeOnDblclick: false
            });
        };
        img.onerror = function() {
            const er = document.getElementById('error-amazon-sublinea-cm');
            if (er) { er.textContent = 'Error al cargar la imagen desde Amazon.'; er.classList.remove('hidden'); }
            colaRecorteAmazonCm.shift();
            mostrarSiguienteRecorteAmazonCm();
        };
    }

    function mostrarSiguienteRecorteAmazonCm() {
        if (!colaRecorteAmazonCm.length) {
            resetEstadoRecorteAmazonCm();
            prepararModalAnadirImagenParaOtraCm('amazon');
            cargarCarpetasModalSublineaCm();
            return;
        }
        const actual = totalRecorteAmazonCm - colaRecorteAmazonCm.length + 1;
        const progreso = document.getElementById('progreso-recorte-amazon-sublinea-cm');
        if (progreso) progreso.textContent = 'Recortando imagen ' + actual + ' de ' + totalRecorteAmazonCm;
        mostrarRecorteAmazonCm(colaRecorteAmazonCm[0].url);
    }

    function iniciarColaRecorteAmazonCm(carpeta) {
        colaRecorteAmazonCm = imagenesAmazonSeleccionadasSublineaCm.slice();
        totalRecorteAmazonCm = colaRecorteAmazonCm.length;
        carpetaActualAmazonCm = carpeta;
        modoRecorteAmazonCm = true;
        const imagenesDiv = document.getElementById('imagenes-amazon-sublinea-cm');
        if (imagenesDiv) imagenesDiv.classList.add('hidden');
        const busqueda = document.getElementById('amazon-busqueda-sublinea-cm');
        if (busqueda) busqueda.classList.add('hidden');
        const errDiv = document.getElementById('error-amazon-sublinea-cm');
        if (errDiv) errDiv.classList.add('hidden');
        mostrarSiguienteRecorteAmazonCm();
    }

    async function procesarRecorteAmazonActualCm() {
        if (!cropperAmazonCm || !carpetaActualAmazonCm) {
            alert('Error al recortar la imagen.');
            return;
        }
        const panel = window.__cmNuevaOpcionPanelDraft;
        if (!panel) {
            alert('No hay panel de borrador activo.');
            return;
        }
        const canvasOriginal = cropperAmazonCm.getCroppedCanvas({ imageSmoothingEnabled: true, imageSmoothingQuality: 'high' });
        if (!canvasOriginal) { alert('Error al recortar'); return; }
        colaRecorteAmazonCm.shift();
        const ctx = resolverContextoProductoCm(panel);
        const nombreBase = nombreBaseArchivoCm(ctx.productoId);
        const token = document.querySelector('meta[name="csrf-token"]').content;
        const carpetaUp = carpetaActualAmazonCm;
        const uploadId = cmNuevoIdSubida();
        const pendingPath = KP_PENDING_CM + uploadId;
        if (!Array.isArray(panel.__cmDraftImagenes)) panel.__cmDraftImagenes = [];
        panel.__cmDraftImagenes.push(pendingPath);
        actualizarBotonesImagenDraftCm(panel);
        const modalVerAmz = document.getElementById('modal-imagenes-sublinea-cm');
        if (modalVerAmz && !modalVerAmz.classList.contains('hidden')) renderizarMiniaturasSublineaCm();

        try {
            const canvasGrande = document.createElement('canvas');
            canvasGrande.width = canvasOriginal.width;
            canvasGrande.height = canvasOriginal.height;
            const ctxG = canvasGrande.getContext('2d');
            ctxG.fillStyle = '#ffffff';
            ctxG.fillRect(0, 0, canvasGrande.width, canvasGrande.height);
            ctxG.drawImage(canvasOriginal, 0, 0);
            const canvasPequena = document.createElement('canvas');
            canvasPequena.width = 300;
            canvasPequena.height = 250;
            const ctxP = canvasPequena.getContext('2d');
            ctxP.fillStyle = '#ffffff';
            ctxP.fillRect(0, 0, 300, 250);
            ctxP.drawImage(canvasOriginal, 0, 0, 300, 250);
            const blobGrande = await new Promise(function(res, rej) {
                canvasGrande.toBlob(function(b) { b ? res(b) : rej(new Error('blob')); }, 'image/webp', 0.9);
            });
            const blobPequena = await new Promise(function(res, rej) {
                canvasPequena.toBlob(function(b) { b ? res(b) : rej(new Error('blob')); }, 'image/webp', 0.9);
            });
            const ts = Date.now();
            const fdG = new FormData();
            fdG.append('imagen', blobGrande, nombreBase + '-' + ts + '.webp');
            fdG.append('carpeta', carpetaUp);
            fdG.append('_token', token);
            const fdP = new FormData();
            fdP.append('imagen', blobPequena, nombreBase + '-' + ts + '-thumbnail.webp');
            fdP.append('carpeta', carpetaUp);
            fdP.append('_token', token);

            (async function() {
                try {
                    const onProg = function(pct) {
                        const el = document.getElementById('kp-prog-cm-' + uploadId);
                        if (el) el.style.width = pct + '%';
                    };
                    const r = @json(route('admin.imagenes.subir-simple'));
                    const { dataG, dataP } = await window.__kpSubirParejaConProgreso(r, fdG, fdP, token, onProg, uploadsPendientesCm, uploadId);
                    if (dataG.success && dataP.success && dataG.data && dataG.data.ruta_relativa) {
                        const ix = panel.__cmDraftImagenes.indexOf(pendingPath);
                        if (ix !== -1) panel.__cmDraftImagenes[ix] = dataG.data.ruta_relativa;
                        renderizarMiniaturasSublineaCm();
                        actualizarBotonesImagenDraftCm(panel);
                    } else {
                        throw new Error(dataG.message || dataP.message || 'Error al subir');
                    }
                } catch (ex) {
                    console.error(ex);
                    cmQuitarPendienteDelDraft(panel, pendingPath);
                    alert('Error al subir una imagen de Amazon: ' + (ex && ex.message ? ex.message : ex));
                }
            })();
        } catch (ex) {
            console.error(ex);
            cmQuitarPendienteDelDraft(panel, pendingPath);
            alert('Error al procesar la imagen: ' + (ex && ex.message ? ex.message : ex));
        }

        mostrarSiguienteRecorteAmazonCm();
    }

    async function limpiarUrlAmazonViaCm(url) {
        if (!url || !String(url).trim()) return url || '';
        try {
            const res = await fetch(@json(route('admin.ofertas.limpiar.url')), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                body: JSON.stringify({ url: String(url).trim() })
            });
            if (!res.ok) return String(url).trim();
            const data = await res.json();
            return data.url_limpia || String(url).trim();
        } catch (e) { return String(url).trim(); }
    }

    async function procesarImagenRecortadaSublineaCm() {
        if (!cropperSublineaCm || !carpetaActualSublineaCm) {
            alert('Descarga y recorta la imagen primero.');
            return;
        }
        const panel = window.__cmNuevaOpcionPanelDraft;
        if (!panel) return;
        const canvasOriginal = cropperSublineaCm.getCroppedCanvas({ imageSmoothingEnabled: true, imageSmoothingQuality: 'high' });
        if (!canvasOriginal) { alert('Error al recortar'); return; }
        const ctx = resolverContextoProductoCm(panel);
        const nombreBase = nombreBaseArchivoCm(ctx.productoId);
        const ts = Date.now();
        const token = document.querySelector('meta[name="csrf-token"]').content;
        const carpetaUp = carpetaActualSublineaCm;

        const canvasGrande = document.createElement('canvas');
        canvasGrande.width = canvasOriginal.width;
        canvasGrande.height = canvasOriginal.height;
        const ctxG = canvasGrande.getContext('2d');
        ctxG.fillStyle = '#ffffff';
        ctxG.fillRect(0, 0, canvasGrande.width, canvasGrande.height);
        ctxG.drawImage(canvasOriginal, 0, 0);

        const canvasPequena = document.createElement('canvas');
        canvasPequena.width = 300;
        canvasPequena.height = 250;
        const ctxP = canvasPequena.getContext('2d');
        ctxP.fillStyle = '#ffffff';
        ctxP.fillRect(0, 0, 300, 250);
        ctxP.drawImage(canvasOriginal, 0, 0, 300, 250);

        try {
            const blobGrande = await new Promise(function(res, rej) {
                canvasGrande.toBlob(function(b) { b ? res(b) : rej(new Error('blob')); }, 'image/webp', 0.9);
            });
            const blobPequena = await new Promise(function(res, rej) {
                canvasPequena.toBlob(function(b) { b ? res(b) : rej(new Error('blob')); }, 'image/webp', 0.9);
            });
            const uploadId = cmNuevoIdSubida();
            const pendingPath = KP_PENDING_CM + uploadId;
            if (!Array.isArray(panel.__cmDraftImagenes)) panel.__cmDraftImagenes = [];
            panel.__cmDraftImagenes.push(pendingPath);
            actualizarBotonesImagenDraftCm(panel);
            const modalVerCm = document.getElementById('modal-imagenes-sublinea-cm');
            if (modalVerCm && !modalVerCm.classList.contains('hidden')) renderizarMiniaturasSublineaCm();
            prepararModalAnadirImagenParaOtraCm('url');

            const fdG = new FormData();
            fdG.append('imagen', blobGrande, nombreBase + '-' + ts + '.webp');
            fdG.append('carpeta', carpetaUp);
            fdG.append('_token', token);
            const fdP = new FormData();
            fdP.append('imagen', blobPequena, nombreBase + '-' + ts + '-thumbnail.webp');
            fdP.append('carpeta', carpetaUp);
            fdP.append('_token', token);

            (async function() {
                try {
                    const onProg = function(pct) {
                        const el = document.getElementById('kp-prog-cm-' + uploadId);
                        if (el) el.style.width = pct + '%';
                    };
                    const r = @json(route('admin.imagenes.subir-simple'));
                    const { dataG, dataP } = await window.__kpSubirParejaConProgreso(r, fdG, fdP, token, onProg, uploadsPendientesCm, uploadId);
                    if (dataG.success && dataP.success && dataG.data && dataG.data.ruta_relativa) {
                        const ix = panel.__cmDraftImagenes.indexOf(pendingPath);
                        if (ix !== -1) panel.__cmDraftImagenes[ix] = dataG.data.ruta_relativa;
                        renderizarMiniaturasSublineaCm();
                        actualizarBotonesImagenDraftCm(panel);
                    } else {
                        throw new Error(dataG.message || dataP.message || 'Error al subir');
                    }
                } catch (err) {
                    console.error(err);
                    cmQuitarPendienteDelDraft(panel, pendingPath);
                    alert(err.message || 'Error al subir');
                }
            })();
        } catch (e) {
            alert(e.message || 'Error');
        }
    }

    function procesarArchivoSublineaCm(file) {
        const carpeta = document.getElementById('carpeta-subir-sublinea-cm').value;
        if (!carpeta) { alert('Selecciona una carpeta.'); return; }
        if (!file.type.startsWith('image/')) { alert('Archivo no válido.'); return; }
        const panel = window.__cmNuevaOpcionPanelDraft;
        if (!panel) return;
        const ctx = resolverContextoProductoCm(panel);
        const nombreBase = nombreBaseArchivoCm(ctx.productoId);
        const ts = Date.now();
        const token = document.querySelector('meta[name="csrf-token"]').content;
        const uploadId = cmNuevoIdSubida();
        const pendingPath = KP_PENDING_CM + uploadId;
        if (!Array.isArray(panel.__cmDraftImagenes)) panel.__cmDraftImagenes = [];
        panel.__cmDraftImagenes.push(pendingPath);
        actualizarBotonesImagenDraftCm(panel);
        const modalVerCm2 = document.getElementById('modal-imagenes-sublinea-cm');
        if (modalVerCm2 && !modalVerCm2.classList.contains('hidden')) renderizarMiniaturasSublineaCm();
        document.getElementById('nombre-archivo-sublinea-cm').textContent = file.name || '';
        prepararModalAnadirImagenParaOtraCm('subir');
        cargarCarpetasModalSublineaCm();

        const img = new Image();
        img.crossOrigin = 'anonymous';
        const blobUrl = URL.createObjectURL(file);
        img.onload = function() {
            URL.revokeObjectURL(blobUrl);
            (async function() {
                try {
                    const canvasGrande = document.createElement('canvas');
                    canvasGrande.width = img.width;
                    canvasGrande.height = img.height;
                    canvasGrande.getContext('2d').drawImage(img, 0, 0);
                    const canvasPequena = document.createElement('canvas');
                    canvasPequena.width = 300;
                    canvasPequena.height = 250;
                    canvasPequena.getContext('2d').drawImage(img, 0, 0, 300, 250);
                    const blobGrande = await new Promise(function(res, rej) {
                        canvasGrande.toBlob(function(b) { b ? res(b) : rej(new Error('blob')); }, 'image/webp', 0.9);
                    });
                    const blobPequena = await new Promise(function(res, rej) {
                        canvasPequena.toBlob(function(b) { b ? res(b) : rej(new Error('blob')); }, 'image/webp', 0.9);
                    });
                    const fdG = new FormData();
                    fdG.append('imagen', blobGrande, nombreBase + '-' + ts + '.webp');
                    fdG.append('carpeta', carpeta);
                    fdG.append('_token', token);
                    const fdP = new FormData();
                    fdP.append('imagen', blobPequena, nombreBase + '-' + ts + '-thumbnail.webp');
                    fdP.append('carpeta', carpeta);
                    fdP.append('_token', token);
                    const onProg = function(pct) {
                        const el = document.getElementById('kp-prog-cm-' + uploadId);
                        if (el) el.style.width = pct + '%';
                    };
                    const r = @json(route('admin.imagenes.subir-simple'));
                    const { dataG, dataP } = await window.__kpSubirParejaConProgreso(r, fdG, fdP, token, onProg, uploadsPendientesCm, uploadId);
                    if (dataG.success && dataP.success && dataG.data && dataG.data.ruta_relativa) {
                        const ix = panel.__cmDraftImagenes.indexOf(pendingPath);
                        if (ix !== -1) panel.__cmDraftImagenes[ix] = dataG.data.ruta_relativa;
                        renderizarMiniaturasSublineaCm();
                        actualizarBotonesImagenDraftCm(panel);
                    } else {
                        throw new Error(dataG.message || dataP.message || 'Error al subir');
                    }
                } catch (err) {
                    console.error(err);
                    cmQuitarPendienteDelDraft(panel, pendingPath);
                    alert(err.message || 'Error al subir');
                }
            })();
        };
        img.onerror = function() {
            URL.revokeObjectURL(blobUrl);
            cmQuitarPendienteDelDraft(panel, pendingPath);
            alert('No se pudo leer el archivo.');
        };
        img.src = blobUrl;
    }

    function mostrarRecorteEnModalSublineaCm(urlImagen) {
        const areaRecorte = document.getElementById('area-recorte-sublinea-cm');
        const img = document.getElementById('imagen-recortar-sublinea-cm');
        if (!areaRecorte || !img) return;
        areaRecorte.classList.remove('hidden');
        if (cropperSublineaCm) { cropperSublineaCm.destroy(); cropperSublineaCm = null; }
        const urlProxy = urlImagen.startsWith('http') ? (@json(route('admin.imagenes.proxy'))) + '?url=' + encodeURIComponent(urlImagen) : urlImagen;
        img.crossOrigin = 'anonymous';
        img.src = urlProxy;
        img.onload = function() {
            if (cropperSublineaCm) cropperSublineaCm.destroy();
            cropperSublineaCm = new Cropper(img, {
                aspectRatio: NaN,
                viewMode: 1,
                dragMode: 'move',
                autoCropArea: 0.8,
                restore: false,
                guides: true,
                center: true,
                highlight: false,
                cropBoxMovable: true,
                cropBoxResizable: true,
                toggleDragModeOnDblclick: false
            });
        };
        img.onerror = function() {
            const er = document.getElementById('error-url-sublinea-cm');
            if (er) { er.textContent = 'Error al cargar la imagen.'; er.classList.remove('hidden'); }
            areaRecorte.classList.add('hidden');
        };
    }

    async function recargarEspecificacionesMismoProductoCm() {
        const p = window.__mismoProductoSeleccionado;
        if (!p || !p.id) return;
        const url = '{{ route("admin.ofertas.crear-masivo.recargar-especificaciones", ["producto" => "__ID__"]) }}'.replace('__ID__', p.id);
        const res = await fetch(url, { headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content } });
        const data = await res.json();
        if (!data.success) return;
        const specContainer = document.getElementById('mismo-producto-spec-container');
        if (!specContainer) return;
        const especsHtml = buildEspecsHtml(p, data.especificaciones, data.tiene_especificaciones);
        specContainer.innerHTML = especsHtml;
        specContainer.dataset.columnasIds = (data.especificaciones && data.especificaciones.columnas_ids) ? JSON.stringify(data.especificaciones.columnas_ids) : '[]';
        specContainer.dataset.esUnidadUnica = (data.especificaciones && data.especificaciones.unidad_de_medida === 'unidadUnica') ? '1' : '0';
        if (Array.isArray(data.imagenes_producto)) p.imagenes_producto = data.imagenes_producto;
    }

    function marcarNuevaOpcionTrasGuardarCm(ctxEl, principalId, subId, esFilaResultado) {
        const cb = ctxEl.querySelector('.spec-checkbox[data-principal-id="' + principalId + '"][data-sublinea-id="' + subId + '"]');
        if (cb) cb.checked = true;
        if (esFilaResultado) {
            actualizarEstadoBotonGenerar(ctxEl);
            renderUrlResaltadaFilaCrearMasivo(ctxEl);
            actualizarConteosOpcionesEspecsFila(ctxEl);
            actualizarAvisosSinImagenEspecsFila(ctxEl);
            clearTimeout(ctxEl.__mismasEspecsTimeout);
            ctxEl.__mismasEspecsTimeout = setTimeout(function() { buscarOfertasMismasEspecsYMostrar(ctxEl); }, 400);
        }
    }

    document.addEventListener('click', function(e) {
        const toggle = e.target.closest('.btn-toggle-nueva-opcion-cm');
        if (toggle) {
            const line = toggle.closest('.spec-line');
            if (!line) return;
            const panel = line.querySelector('.nueva-opcion-cm-panel');
            if (!panel) return;
            const open = panel.classList.contains('hidden');
            document.querySelectorAll('.nueva-opcion-cm-panel').forEach(function(p) {
                if (p !== panel) cerrarPanelNuevaOpcionCm(p);
            });
            if (open) {
                panel.classList.remove('hidden');
                resetPanelNuevaOpcionCm(panel);
                if (toggle.getAttribute('data-insert-first') === '1') {
                    panel.__cmInsertFirst = true;
                    panel.__cmInsertAfterSubId = null;
                } else {
                    panel.__cmInsertFirst = false;
                    const aid = toggle.getAttribute('data-after-sub-id');
                    panel.__cmInsertAfterSubId = (aid !== null && aid !== '') ? aid : null;
                }
            } else {
                cerrarPanelNuevaOpcionCm(panel);
            }
            return;
        }
        const cancel = e.target.closest('.btn-cm-cancelar-nueva-opcion');
        if (cancel) {
            cerrarPanelNuevaOpcionCm(cancel.closest('.nueva-opcion-cm-panel'));
            return;
        }
        const btnVer = e.target.closest('.btn-cm-ver-imagenes-draft');
        if (btnVer && !btnVer.disabled) {
            const panel = btnVer.closest('.nueva-opcion-cm-panel');
            if (panel && Array.isArray(panel.__cmDraftImagenes) && panel.__cmDraftImagenes.length) abrirModalVerImagenesDraftCm(panel);
            return;
        }
        const btnAdd = e.target.closest('.btn-cm-anadir-imagenes-draft');
        if (btnAdd && !btnAdd.disabled) {
            const panel = btnAdd.closest('.nueva-opcion-cm-panel');
            if (panel) abrirModalAnadirImagenDraftCm(panel);
            return;
        }
        const btnGuardar = e.target.closest('.btn-cm-guardar-nueva-opcion');
        if (btnGuardar) {
            const panel = btnGuardar.closest('.nueva-opcion-cm-panel');
            if (!panel) return;
            const ctx = resolverContextoProductoCm(panel);
            if (!ctx.productoId) {
                alert('No hay producto asociado.');
                return;
            }
            const principalId = String(panel.dataset.principalId || '');
            const texto = (panel.querySelector('.nueva-opcion-cm-texto') && panel.querySelector('.nueva-opcion-cm-texto').value || '').trim();
            const usarProd = !!(panel.querySelector('.nueva-opcion-cm-usar-img-prod') && panel.querySelector('.nueva-opcion-cm-usar-img-prod').checked);
            const msg = panel.querySelector('.nueva-opcion-cm-msg');
            if (!texto) {
                if (msg) { msg.textContent = 'Indica el nombre de la opción.'; msg.classList.remove('hidden'); msg.className = 'nueva-opcion-cm-msg text-xs text-red-600 mt-1'; }
                return;
            }
            if (!usarProd && Array.isArray(panel.__cmDraftImagenes) && panel.__cmDraftImagenes.some(cmEsRutaPendiente)) {
                alert('Espera a que terminen de subirse todas las imágenes antes de guardar la opción.');
                return;
            }
            const body = {
                producto_id: ctx.productoId,
                principal_id: principalId,
                texto: texto,
                usar_imagenes_producto: usarProd,
                imagenes: (!usarProd && Array.isArray(panel.__cmDraftImagenes)) ? panel.__cmDraftImagenes : []
            };
            if (panel.__cmInsertFirst) body.insert_first = true;
            else if (panel.__cmInsertAfterSubId) body.after_sub_id = panel.__cmInsertAfterSubId;
            btnGuardar.disabled = true;
            btnGuardar.dataset.cmGuardandoOpcion = '1';
            fetch(@json(route('admin.ofertas.crear-masivo.anadir-opcion-especificacion')), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                body: JSON.stringify(body)
            }).then(function(r) { return r.json(); }).then(async function(data) {
                delete btnGuardar.dataset.cmGuardandoOpcion;
                btnGuardar.disabled = false;
                actualizarBotonesImagenDraftCm(panel);
                if (!data.success) {
                    if (msg) { msg.textContent = data.error || 'Error'; msg.classList.remove('hidden'); msg.className = 'nueva-opcion-cm-msg text-xs text-red-600 mt-1'; }
                    return;
                }
                cerrarPanelNuevaOpcionCm(panel);
                const esFila = !!(ctx.fila && ctx.fila.classList && ctx.fila.classList.contains('crear-masivo-fila'));
                if (esFila) {
                    await recargarEspecificacionesFila(ctx.fila, String(ctx.productoId));
                    marcarNuevaOpcionTrasGuardarCm(ctx.fila, data.principal_id, data.sub_id, true);
                } else {
                    await recargarEspecificacionesMismoProductoCm();
                    const specContainer = document.getElementById('mismo-producto-spec-container');
                    if (specContainer) marcarNuevaOpcionTrasGuardarCm(specContainer, data.principal_id, data.sub_id, false);
                }
            }).catch(function(err) {
                delete btnGuardar.dataset.cmGuardandoOpcion;
                btnGuardar.disabled = false;
                actualizarBotonesImagenDraftCm(panel);
                alert('Error: ' + (err && err.message ? err.message : err));
            });
        }
    });

    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('nueva-opcion-cm-usar-img-prod')) {
            actualizarBotonesImagenDraftCm(e.target.closest('.nueva-opcion-cm-panel'));
        }
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const modalImg = document.getElementById('modal-imagenes-spec-crear-masivo');
            if (modalImg && !modalImg.classList.contains('hidden')) cerrarModalImagenesSpecCrearMasivo();
            const modalPrompt = document.getElementById('modal-ver-prompt-crear-masivo');
            if (modalPrompt && !modalPrompt.classList.contains('hidden')) cerrarModalVerPromptCrearMasivo();
            const modalDescartar = document.getElementById('modal-descartar-url-crear-masivo');
            if (modalDescartar && !modalDescartar.classList.contains('hidden')) cerrarModalDescartarUrlCrearMasivo();
            const modalCatFilaEsc = document.getElementById('modal-categoria-fila-crear-masivo');
            if (modalCatFilaEsc && !modalCatFilaEsc.classList.contains('hidden')) cerrarModalCategoriaFilaCrearMasivo();
            const mCm = document.getElementById('modal-imagenes-sublinea-cm');
            if (mCm && !mCm.classList.contains('hidden')) cerrarModalImagenesSublineaCm();
            const aCm = document.getElementById('modal-añadir-imagen-sublinea-cm');
            if (aCm && !aCm.classList.contains('hidden')) cerrarModalAñadirImagenSublineaCm();
        }
    });

    document.addEventListener('DOMContentLoaded', function() {
        (function initAyudaCrearMasivoUrls() {
            const wrap = document.getElementById('crear-masivo-ayuda-urls-wrap');
            const btn = document.getElementById('crear-masivo-ayuda-urls-btn');
            const panel = document.getElementById('crear-masivo-ayuda-urls-panel');
            if (!wrap || !btn || !panel) return;
            let fijadoPorClick = false;
            function mostrarAyudaCrearMasivo() {
                panel.classList.remove('hidden');
                btn.setAttribute('aria-expanded', 'true');
            }
            function ocultarAyudaCrearMasivo() {
                panel.classList.add('hidden');
                btn.setAttribute('aria-expanded', 'false');
            }
            wrap.addEventListener('mouseenter', function() {
                if (!fijadoPorClick) mostrarAyudaCrearMasivo();
            });
            wrap.addEventListener('mouseleave', function() {
                if (!fijadoPorClick) ocultarAyudaCrearMasivo();
            });
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                fijadoPorClick = !fijadoPorClick;
                if (fijadoPorClick) {
                    mostrarAyudaCrearMasivo();
                } else {
                    ocultarAyudaCrearMasivo();
                }
            });
            document.addEventListener('click', function(e) {
                if (!wrap.contains(e.target)) {
                    fijadoPorClick = false;
                    ocultarAyudaCrearMasivo();
                }
            });
        })();

        const modal = document.getElementById('modal-imagenes-spec-crear-masivo');
        if (modal) {
            modal.addEventListener('click', function(e) {
                if (e.target === modal) cerrarModalImagenesSpecCrearMasivo();
            });
        }
        const modalPrompt = document.getElementById('modal-ver-prompt-crear-masivo');
        if (modalPrompt) {
            modalPrompt.addEventListener('click', function(e) {
                if (e.target === modalPrompt) cerrarModalVerPromptCrearMasivo();
            });
        }
        const modalDescartar = document.getElementById('modal-descartar-url-crear-masivo');
        if (modalDescartar) {
            modalDescartar.addEventListener('click', function(e) {
                if (e.target === modalDescartar) cerrarModalDescartarUrlCrearMasivo();
            });
        }
        const modalCatFila = document.getElementById('modal-categoria-fila-crear-masivo');
        if (modalCatFila) {
            modalCatFila.addEventListener('click', function(e) {
                if (e.target === modalCatFila) cerrarModalCategoriaFilaCrearMasivo();
            });
            // Fase captura: solo «Seleccionar» en categorías sin hijos.
            modalCatFila.addEventListener('click', function(e) {
                const btn = e.target.closest('.btn-elegir-categoria-arbol-crear-masivo');
                if (!btn || !modalCatFila.contains(btn)) return;
                e.preventDefault();
                const id = parseInt(btn.getAttribute('data-categoria-id'), 10);
                const nombre = btn.getAttribute('data-categoria-nombre') || '';
                if (!id) return;
                aplicarCategoriaElegidaArbolCrearMasivo(id, nombre);
            }, true);
        }
        const btnCerrarCatFila = document.getElementById('modal-categoria-fila-cerrar');
        const btnCerrarCatFila2 = document.getElementById('modal-categoria-fila-cerrar-btn');
        if (btnCerrarCatFila) btnCerrarCatFila.addEventListener('click', cerrarModalCategoriaFilaCrearMasivo);
        if (btnCerrarCatFila2) btnCerrarCatFila2.addEventListener('click', cerrarModalCategoriaFilaCrearMasivo);
        const btnCerrarDescartar = document.getElementById('modal-descartar-url-cerrar');
        if (btnCerrarDescartar) {
            btnCerrarDescartar.addEventListener('click', cerrarModalDescartarUrlCrearMasivo);
        }
        const btnCancelarDescartar = document.getElementById('modal-descartar-url-cancelar');
        if (btnCancelarDescartar) {
            btnCancelarDescartar.addEventListener('click', cerrarModalDescartarUrlCrearMasivo);
        }
        const btnConfirmarDescartar = document.getElementById('modal-descartar-url-confirmar');
        if (btnConfirmarDescartar) {
            btnConfirmarDescartar.addEventListener('click', confirmarDescartarUrlCrearMasivo);
        }

        const tabUrlCm = document.getElementById('tab-url-sublinea-cm');
        const tabSubirCm = document.getElementById('tab-subir-sublinea-cm');
        const tabAmazonCm = document.getElementById('tab-amazon-sublinea-cm');
        const tabInternaCm = document.getElementById('tab-interna-sublinea-cm');
        if (tabUrlCm) tabUrlCm.addEventListener('click', function() { cambiarTabModalSublineaCm('url'); });
        if (tabSubirCm) tabSubirCm.addEventListener('click', function() { cambiarTabModalSublineaCm('subir'); });
        if (tabAmazonCm) tabAmazonCm.addEventListener('click', function() { cambiarTabModalSublineaCm('amazon'); });
        if (tabInternaCm) tabInternaCm.addEventListener('click', function() { cambiarTabModalSublineaCm('interna'); });
        const tabInternaGlobalCm = document.getElementById('tab-interna-global-sublinea-cm');
        if (tabInternaGlobalCm) tabInternaGlobalCm.addEventListener('click', function() { cambiarTabModalSublineaCm('interna-global'); });

        if (typeof window.kpInternaGlobalRegistrar === 'function') {
            window.kpInternaGlobalRegistrar('cm', {
                onSelect: function(pair) {
                    const rG = document.getElementById('ruta-interna-grande-sublinea-cm');
                    const rP = document.getElementById('ruta-interna-pequena-sublinea-cm');
                    const grande = (pair.rutaGrande || '').trim();
                    const pequena = (pair.rutaPequena || cmInferirThumbnailDesdeGrande(grande) || grande).trim();
                    const actual = rG ? (rG.value || '').trim() : '';
                    if (actual && actual === grande) {
                        if (rG) rG.value = '';
                        if (rP) rP.value = '';
                    } else {
                        if (rG) rG.value = grande;
                        if (rP) rP.value = pequena;
                    }
                    cmActualizarPreviewInternaCm('ruta-interna-grande-sublinea-cm', 'preview-interna-grande-sublinea-cm');
                    cmActualizarPreviewInternaCm('ruta-interna-pequena-sublinea-cm', 'preview-interna-pequena-sublinea-cm');
                },
                isSelected: function(pair) {
                    const rG = document.getElementById('ruta-interna-grande-sublinea-cm');
                    const actual = rG ? (rG.value || '').trim() : '';
                    return !!actual && actual === (pair.rutaGrande || '').trim();
                },
                renderResumen: function() {
                    const el = document.querySelector('[data-kp-ig-panel="cm"] .kp-ig-seleccion-resumen');
                    const rG = document.getElementById('ruta-interna-grande-sublinea-cm');
                    const rP = document.getElementById('ruta-interna-pequena-sublinea-cm');
                    const grande = rG ? (rG.value || '').trim() : '';
                    if (!grande) {
                        window.kpIgPintarResumenSeleccion(el, []);
                        return;
                    }
                    window.kpIgPintarResumenSeleccion(el, [{
                        rutaGrande: grande,
                        rutaPequena: rP ? (rP.value || '').trim() : '',
                        thumbVisual: rP ? (rP.value || '').trim() : grande,
                    }]);
                },
                getPalabras: cmObtenerPalabrasNombreProducto,
            });
        }

        const btnDescUrlCm = document.getElementById('btn-descargar-url-sublinea-cm');
        if (btnDescUrlCm) {
            btnDescUrlCm.addEventListener('click', function() {
                const carpeta = document.getElementById('carpeta-url-sublinea-cm').value;
                const url = document.getElementById('url-imagen-sublinea-cm').value.trim();
                const err = document.getElementById('error-url-sublinea-cm');
                if (!carpeta) {
                    if (err) { err.textContent = 'Selecciona una carpeta.'; err.classList.remove('hidden'); }
                    return;
                }
                if (!url) {
                    if (err) { err.textContent = 'Introduce una URL.'; err.classList.remove('hidden'); }
                    return;
                }
                if (err) err.classList.add('hidden');
                carpetaActualSublineaCm = carpeta;
                mostrarRecorteEnModalSublineaCm(url);
            });
        }

        const btnGuardarImgCm = document.getElementById('btn-guardar-imagen-sublinea-cm');
        if (btnGuardarImgCm) {
            btnGuardarImgCm.addEventListener('click', async function() {
                const tabAmazon = document.getElementById('tab-amazon-sublinea-cm');
                const esAmazon = tabAmazon && tabAmazon.classList.contains('kp-modal-img-tab--active');
                if (esAmazon) {
                    if (modoRecorteAmazonCm) {
                        await procesarRecorteAmazonActualCm();
                        return;
                    }
                    if (imagenesAmazonSeleccionadasSublineaCm.length === 0) {
                        alert('Selecciona al menos una imagen.');
                        return;
                    }
                    const carpeta = document.getElementById('carpeta-amazon-sublinea-cm').value;
                    if (!carpeta) { alert('Selecciona una carpeta.'); return; }
                    iniciarColaRecorteAmazonCm(carpeta);
                    return;
                }
                const tabInterna = document.getElementById('tab-interna-sublinea-cm');
                const tabInternaGlobal = document.getElementById('tab-interna-global-sublinea-cm');
                const esInterna = tabInterna && tabInterna.classList.contains('kp-modal-img-tab--active');
                const esInternaGlobal = tabInternaGlobal && tabInternaGlobal.classList.contains('kp-modal-img-tab--active');
                if (esInterna || esInternaGlobal) {
                    const rG = (document.getElementById('ruta-interna-grande-sublinea-cm') && document.getElementById('ruta-interna-grande-sublinea-cm').value || '').trim();
                    if (!rG) {
                        alert('Indica la ruta de la imagen grande ya subida.');
                        return;
                    }
                    const panelInt = window.__cmNuevaOpcionPanelDraft;
                    if (!panelInt) {
                        alert('No hay panel de borrador activo.');
                        return;
                    }
                    añadirRutaImagenDraftCm(rG);
                    const modalVerInt = document.getElementById('modal-imagenes-sublinea-cm');
                    if (modalVerInt && !modalVerInt.classList.contains('hidden')) renderizarMiniaturasSublineaCm();
                    prepararModalAnadirImagenParaOtraCm('interna');
                    return;
                }
                const tabSubir = document.getElementById('tab-subir-sublinea-cm');
                const esSubir = tabSubir && tabSubir.classList.contains('kp-modal-img-tab--active');
                if (esSubir) {
                    const fi = document.getElementById('file-subir-sublinea-cm');
                    if (!fi || !fi.files.length) { alert('Selecciona una imagen.'); return; }
                    procesarArchivoSublineaCm(fi.files[0]);
                    return;
                }
                await procesarImagenRecortadaSublineaCm();
            });
        }

        const fileSubirCm = document.getElementById('file-subir-sublinea-cm');
        const btnSelSubirCm = document.getElementById('btn-seleccionar-sublinea-cm');
        const dropCm = document.getElementById('drop-zone-sublinea-cm');
        if (btnSelSubirCm && fileSubirCm) btnSelSubirCm.addEventListener('click', function() { fileSubirCm.click(); });
        if (fileSubirCm) {
            fileSubirCm.addEventListener('change', function(ev) {
                if (ev.target.files.length) procesarArchivoSublineaCm(ev.target.files[0]);
            });
        }
        if (dropCm && fileSubirCm) {
            dropCm.addEventListener('click', function() { fileSubirCm.click(); });
            dropCm.addEventListener('dragover', function(ev) { ev.preventDefault(); });
            dropCm.addEventListener('drop', function(ev) {
                ev.preventDefault();
                if (ev.dataTransfer.files.length) procesarArchivoSublineaCm(ev.dataTransfer.files[0]);
            });
        }

        const urlAmzCm = document.getElementById('url-amazon-sublinea-cm');
        if (urlAmzCm) {
            urlAmzCm.addEventListener('paste', function() {
                setTimeout(async function() {
                    const u = urlAmzCm.value.trim();
                    if (u) urlAmzCm.value = await limpiarUrlAmazonViaCm(u);
                }, 10);
            });
        }

        const btnBuscarAmzCm = document.getElementById('btn-buscar-amazon-sublinea-cm');
        if (btnBuscarAmzCm) {
            btnBuscarAmzCm.addEventListener('click', async function() {
                const urlInput = document.getElementById('url-amazon-sublinea-cm');
                const errDiv = document.getElementById('error-amazon-sublinea-cm');
                const loading = document.getElementById('loading-amazon-sublinea-cm');
                const imagenesDiv = document.getElementById('imagenes-amazon-sublinea-cm');
                const grid = document.getElementById('grid-imagenes-amazon-sublinea-cm');
                const url = urlInput.value.trim();
                if (!url) {
                    if (errDiv) { errDiv.textContent = 'Introduce una URL de Amazon.'; errDiv.classList.remove('hidden'); }
                    return;
                }
                if (errDiv) errDiv.classList.add('hidden');
                if (loading) loading.classList.remove('hidden');
                if (imagenesDiv) imagenesDiv.classList.add('hidden');
                if (grid) grid.innerHTML = '';
                imagenesAmazonSeleccionadasSublineaCm = [];
                try {
                    const response = await fetch(@json(route('admin.productos.obtener-imagenes-amazon')), {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                        body: JSON.stringify({ url: url })
                    });
                    const data = await response.json();
                    if (!data.success) throw new Error(data.error || 'Error');
                    if (!data.imagenes || !data.imagenes.length) {
                        if (errDiv) { errDiv.textContent = 'No se encontraron imágenes.'; errDiv.classList.remove('hidden'); }
                        if (loading) loading.classList.add('hidden');
                        return;
                    }
                    data.imagenes.forEach(function(imagen, index) {
                        const div = document.createElement('div');
                        div.className = 'relative border-2 border-gray-300 dark:border-gray-600 rounded overflow-hidden cursor-pointer';
                        div.dataset.index = String(index);
                        const img = document.createElement('img');
                        img.src = imagen.url;
                        img.className = 'w-full h-28 object-contain';
                        const cb = document.createElement('input');
                        cb.type = 'checkbox';
                        cb.className = 'absolute top-2 right-2 w-5 h-5';
                        cb.dataset.index = String(index);
                        div.appendChild(img);
                        div.appendChild(cb);
                        div.addEventListener('click', function(ev) {
                            if (ev.target === cb) return;
                            cb.checked = !cb.checked;
                            actualizarSeleccionAmazonCm(cb.checked, index, imagen);
                        });
                        cb.addEventListener('change', function() {
                            actualizarSeleccionAmazonCm(cb.checked, index, imagen);
                        });
                        grid.appendChild(div);
                    });
                    if (imagenesDiv) imagenesDiv.classList.remove('hidden');
                } catch (err) {
                    if (errDiv) { errDiv.textContent = err.message || 'Error'; errDiv.classList.remove('hidden'); }
                }
                if (loading) loading.classList.add('hidden');
            });
        }

        function actualizarSeleccionAmazonCm(sel, index, imagen) {
            if (sel) {
                if (!imagenesAmazonSeleccionadasSublineaCm.find(function(x) { return x.url === imagen.url; })) {
                    imagenesAmazonSeleccionadasSublineaCm.push(imagen);
                }
            } else {
                imagenesAmazonSeleccionadasSublineaCm = imagenesAmazonSeleccionadasSublineaCm.filter(function(x) { return x.url !== imagen.url; });
            }
        }

        const modalImgCm = document.getElementById('modal-imagenes-sublinea-cm');
        if (modalImgCm) {
            modalImgCm.addEventListener('click', function(ev) { if (ev.target === modalImgCm) cerrarModalImagenesSublineaCm(); });
        }
        const modalAddCm = document.getElementById('modal-añadir-imagen-sublinea-cm');
        if (modalAddCm) {
            modalAddCm.addEventListener('click', function(ev) { if (ev.target === modalAddCm) cerrarModalAñadirImagenSublineaCm(); });
        }
    });
    </script>
</x-app-layout>
