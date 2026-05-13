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
            <p class="text-gray-600 dark:text-gray-400 mb-4">
                <strong>Filas en neo con añadida = no:</strong> <span id="total-neo-aniadida-no">{{ $totalNeoAniadidaNo ?? 0 }}</span>
                <span class="mx-2">|</span>
                <strong>Sin URL:</strong> <span id="total-neo-aniadida-no-sin-url">{{ $totalNeoAniadidaNoSinUrl ?? 0 }}</span>
            </p>
            <div class="mb-4 flex flex-wrap items-center gap-2">
                <button type="button" id="btnProductoNeo" class="inline-flex items-center bg-blue-500 hover:bg-blue-600 text-white font-semibold px-4 py-2.5 rounded shadow transition disabled:opacity-50 disabled:cursor-not-allowed">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                    Producto ({{ $totalProductosNeoAniadidaNo ?? 0 }})
                </button>
                <button type="button" id="btnCategoriaNeo" class="inline-flex items-center bg-pink-500 hover:bg-pink-600 text-white font-semibold px-4 py-2.5 rounded shadow transition disabled:opacity-50 disabled:cursor-not-allowed">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path></svg>
                    Categoría ({{ $totalCategoriasNeoAniadidaNo ?? 0 }})
                </button>
                <button type="button" id="btnTiendaNeo" class="inline-flex items-center bg-emerald-500 hover:bg-emerald-600 text-white font-semibold px-4 py-2.5 rounded shadow transition disabled:opacity-50 disabled:cursor-not-allowed">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7l1.664 9.152A2 2 0 006.632 18h10.736a2 2 0 001.968-1.848L21 7M8 11h8M12 7v8"></path></svg>
                    Tienda ({{ $totalTiendasNeoAniadidaNo ?? 0 }})
                </button>
                <span class="text-sm text-gray-500 dark:text-gray-400">Cargar URLs desde neo (añadida=no): por producto, por categoría o por tienda</span>
            </div>
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
                    <div id="mismo-producto-block" class="ml-6 space-y-3 hidden border border-gray-200 dark:border-gray-600 rounded-lg p-4 bg-gray-50 dark:bg-gray-700/50">
                        <div class="producto-search-container relative">
                            <label class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300">Buscar producto</label>
                            <input type="text" id="mismo-producto-search" class="producto-search-input w-full px-3 py-2 rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 text-sm" placeholder="Escribe para buscar productos (mín. 2 caracteres)..." autocomplete="off">
                            <div id="mismo-producto-sugerencias" class="producto-sugerencias-crear-masivo absolute z-50 left-0 right-0 mt-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md shadow-lg hidden max-h-60 overflow-y-auto"></div>
                        </div>
                        <div id="mismo-producto-elegido" class="hidden text-sm text-gray-600 dark:text-gray-400">
                            <span class="font-medium">Producto:</span> <span id="mismo-producto-nombre"></span>
                            <button type="button" id="mismo-producto-quitar" class="ml-2 inline-flex items-center justify-center w-6 h-6 rounded bg-red-500 hover:bg-red-600 text-white text-xs font-bold transition" title="Quitar producto">✕</button>
                        </div>
                        <div id="mismo-producto-spec-container" class="mismo-producto-spec-wrapper" data-columnas-ids="[]" data-es-unidad-unica="0"></div>
                    </div>
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
                                <option value="gpt-4o-nano">gpt-4o-nano — más barato, más rápido, peor precisión</option>
                                <option value="gpt-4o-mini">gpt-4o-mini — barato, rápido, buena precisión</option>
                                <option value="gpt-4o" selected>gpt-4o — mejor precisión, más caro (por defecto)</option>
                                <option value="gpt-4-turbo">gpt-4-turbo — mejor calidad, el más caro</option>
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

        <div id="resultados" class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6 border border-gray-200 dark:border-gray-700 hidden">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Resultados</h2>
            <div id="resultadosLista" class="space-y-4"></div>
        </div>

        <div id="crear-masivo-siguiente-wrap" class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6 border border-gray-200 dark:border-gray-700 hidden">
            <button type="button" id="btnSiguienteOrigenNeo" class="inline-flex items-center bg-emerald-600 hover:bg-emerald-700 text-white font-semibold px-6 py-3 rounded-md transition disabled:opacity-50 disabled:cursor-not-allowed">
                Siguiente
            </button>
        </div>
    </div>

    {{-- Modal para ver imágenes de especificaciones internas --}}
    <div id="modal-imagenes-spec-crear-masivo" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white dark:bg-gray-900 rounded-lg p-6 max-w-5xl w-full relative shadow-xl max-h-[90vh] flex flex-col">
            <button type="button" onclick="cerrarModalImagenesSpecCrearMasivo()" class="absolute top-3 right-4 text-xl text-gray-800 dark:text-gray-100 hover:text-gray-600 dark:hover:text-gray-300 z-10">×</button>
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
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="flex flex-wrap items-center gap-x-4 gap-y-2 text-sm text-gray-700 dark:text-gray-300">
                        <span>Total URLs disponibles: <strong id="modal-producto-neo-total-urls" class="text-gray-900 dark:text-white">—</strong></span>
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
                        </span>
                    </div>
                    <button type="button" id="modal-producto-neo-cerrar" class="text-2xl leading-none text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 shrink-0">&times;</button>
                </div>
                <details id="modal-producto-neo-details-tiendas-no" class="hidden w-full text-sm text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-600 rounded-lg open:shadow-sm">
                    <summary class="cursor-pointer select-none px-3 py-2 font-medium text-gray-800 dark:text-gray-200 bg-gray-50 dark:bg-gray-700/50 rounded-lg list-none [&::-webkit-details-marker]:hidden flex items-center gap-2">
                        <span class="text-gray-400 dark:text-gray-500 text-xs" aria-hidden="true">▸</span>
                        <span>Elegir tiendas (mostrar «no»)</span>
                    </summary>
                    <div id="modal-producto-neo-tiendas-no-lista" class="p-3 max-h-48 overflow-y-auto space-y-1.5 border-t border-gray-200 dark:border-gray-600"></div>
                </details>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Elegir producto (neo con añadida=no)</h3>
            </div>
            <div id="modal-producto-neo-lista" class="flex-1 overflow-y-auto p-4 space-y-2">
                <p class="text-gray-500 dark:text-gray-400 text-sm">Cargando...</p>
            </div>
            <div class="p-4 border-t border-gray-200 dark:border-gray-700">
                <button type="button" id="modal-producto-neo-cerrar-btn" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">Cerrar</button>
            </div>
        </div>
    </div>

    {{-- Modal elegir categoría (neo aniadida=no) --}}
    <div id="modal-categoria-neo" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-2xl w-full max-h-[80vh] flex flex-col border border-gray-200 dark:border-gray-700">
            <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Elegir categoría (neo con añadida=no)</h3>
                <button type="button" id="modal-categoria-neo-cerrar" class="text-2xl leading-none text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">&times;</button>
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
            <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Elegir tienda (neo con añadida=no)</h3>
                <button type="button" id="modal-tienda-neo-cerrar" class="text-2xl leading-none text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">&times;</button>
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
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">La búsqueda de productos se limitará a esta categoría y sus subcategorías.</p>
                <fieldset class="bg-gray-50 dark:bg-gray-900/40 rounded-xl p-4 space-y-2 border border-gray-200 dark:border-gray-700">
                    <legend class="text-sm font-semibold text-gray-700 dark:text-gray-200 px-1">Categorías</legend>
                    @foreach ($categoriasRaiz ?? [] as $categoria)
                        @include('admin.categorias.partial-categoria', ['categoria' => $categoria, 'nivel' => 0, 'esPickerCrearMasivo' => true])
                    @endforeach
                </fieldset>
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
            <button type="button" onclick="cerrarModalImagenesSublineaCm()" class="absolute top-3 right-4 text-xl text-gray-800 dark:text-gray-100 hover:text-gray-600 z-10">×</button>
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

    <div id="modal-añadir-imagen-sublinea-cm" class="fixed inset-0 bg-black bg-opacity-50 z-[60] hidden flex items-center justify-center p-4">
        <div class="bg-white dark:bg-gray-900 rounded-lg p-6 max-w-4xl w-full relative shadow-xl overflow-y-auto max-h-[90vh]">
            <button type="button" onclick="cerrarModalAñadirImagenSublineaCm()" class="absolute top-3 right-4 text-xl text-gray-800 dark:text-gray-100">×</button>
            <div class="mb-4">
                <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-200">Añadir imagen</h3>
            </div>
            <div class="border-b border-gray-200 dark:border-gray-600 mb-4">
                <nav class="-mb-px flex flex-wrap gap-4" aria-label="Tabs">
                    <button type="button" id="tab-url-sublinea-cm" class="tab-modal-sublinea-cm border-b-2 border-blue-500 py-2 px-1 text-sm font-medium text-blue-600 dark:text-blue-400">URL</button>
                    <button type="button" id="tab-subir-sublinea-cm" class="tab-modal-sublinea-cm border-b-2 border-transparent py-2 px-1 text-sm font-medium text-gray-500 dark:text-gray-400">Subir</button>
                    <button type="button" id="tab-amazon-sublinea-cm" class="tab-modal-sublinea-cm border-b-2 border-transparent py-2 px-1 text-sm font-medium text-gray-500 dark:text-gray-400">Amazon</button>
                    <button type="button" id="tab-interna-sublinea-cm" class="tab-modal-sublinea-cm border-b-2 border-transparent py-2 px-1 text-sm font-medium text-gray-500 dark:text-gray-400">Interna</button>
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
                <div>
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
            <div class="flex justify-end gap-3 mt-6">
                <button type="button" onclick="cerrarModalAñadirImagenSublineaCm()" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-md dark:bg-gray-700 dark:text-gray-300">Cancelar</button>
                <button type="button" id="btn-guardar-imagen-sublinea-cm" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700">Guardar</button>
            </div>
        </div>
    </div>

    <script>
    const NEO_PANEL_PRODUCTOS_BASE = @json(rtrim(url('/panel-privado/productos'), '/'));
    window.__crearMasivoImagenesSublinea = {};
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
    const estadoLotesAnalisis = {
        urls: [],
        cursor: 0,
        lastStart: null,
        lastEnd: null,
        signature: '',
    };

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

    document.getElementById('mismo_producto').addEventListener('change', function() {
        const block = document.getElementById('mismo-producto-block');
        if (this.checked) {
            block.classList.remove('hidden');
        } else {
            block.classList.add('hidden');
            window.__mismoProductoSeleccionado = null;
        }
    });

    const mismoProductoSearch = document.getElementById('mismo-producto-search');
    const mismoProductoSugerencias = document.getElementById('mismo-producto-sugerencias');
    let mismoProductoTimeout = null;
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

    document.getElementById('mismo-producto-quitar').addEventListener('click', function() {
        window.__mismoProductoSeleccionado = null;
        document.getElementById('mismo-producto-nombre').textContent = '';
        document.getElementById('mismo-producto-elegido').classList.add('hidden');
        document.querySelector('#mismo-producto-block .producto-search-container').classList.remove('hidden');
        document.getElementById('mismo-producto-spec-container').innerHTML = '';
        document.getElementById('mismo-producto-search').value = '';
    });

    function parseRespuestaProductosNeoApi(raw) {
        if (Array.isArray(raw)) {
            return { productos: raw, filas_neo_tienda_id_null: 0 };
        }
        return {
            productos: Array.isArray(raw.productos) ? raw.productos : [],
            filas_neo_tienda_id_null: parseInt(String(raw.filas_neo_tienda_id_null ?? 0), 10) || 0,
        };
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
        return queryMostrarTiendaModalProductoNeo() + queryTiendasNoSeleccionadasModalProductoNeo();
    }

    const urlTplUrlsPorProductoNeo = '{{ route("admin.neo.crear-masivo.urls-por-producto", ["productoId" => "__ID__"]) }}';

    /**
     * Carga URLs neo + mismo producto (igual que al elegir en el modal Producto).
     * Deja window.__crearMasivoOrigenNeo = { tipo: 'producto', ... } para el botón Siguiente.
     */
    async function aplicarProductoNeoAlFormulario(productoId, filtrosQuery) {
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
        const masterLabel = document.createElement('label');
        masterLabel.className = 'flex items-center gap-2 cursor-pointer font-medium text-gray-900 dark:text-gray-100 pb-2 mb-1 border-b border-gray-200 dark:border-gray-600';
        const masterCb = document.createElement('input');
        masterCb.type = 'checkbox';
        masterCb.id = 'modal-producto-neo-chk-tiendas-no-todas';
        masterCb.checked = true;
        masterCb.className = 'rounded border-gray-300 text-blue-600 focus:ring-blue-500 shrink-0 modal-producto-neo-tiendas-no-master';
        const masterSpan = document.createElement('span');
        masterSpan.textContent = 'Todas las tiendas';
        masterLabel.appendChild(masterCb);
        masterLabel.appendChild(masterSpan);
        container.appendChild(masterLabel);
        masterCb.addEventListener('change', function() {
            const marcar = masterCb.checked;
            container.querySelectorAll('input.modal-producto-neo-tienda-no-cb').forEach(function(c) {
                c.checked = marcar;
            });
            recargarListaModalProductoNeo();
        });
        lista.forEach(function(t) {
            const label = document.createElement('label');
            label.className = 'flex items-center gap-2 cursor-pointer text-gray-800 dark:text-gray-200';
            const cb = document.createElement('input');
            cb.type = 'checkbox';
            cb.value = String(t.id);
            cb.checked = true;
            cb.className = 'rounded border-gray-300 text-blue-600 focus:ring-blue-500 modal-producto-neo-tienda-no-cb';
            cb.addEventListener('change', function() {
                actualizarMasterTiendasMostrarNoModal();
                recargarListaModalProductoNeo();
            });
            const span = document.createElement('span');
            span.textContent = t.nombre || ('Tienda #' + t.id);
            label.appendChild(cb);
            label.appendChild(span);
            container.appendChild(label);
        });
        container.dataset.loaded = '1';
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

    async function actualizarVisibilidadPanelTiendasNoModal() {
        const details = document.getElementById('modal-producto-neo-details-tiendas-no');
        if (!details) {
            return;
        }
        const soloNo = esSoloMostrarNoModalProductoNeo();
        details.classList.toggle('hidden', !soloNo);
        if (soloNo) {
            details.open = true;
            await cargarOpcionesTiendasMostrarNoModal();
        } else {
            details.open = false;
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

    async function recargarListaModalProductoNeo() {
        await actualizarVisibilidadPanelTiendasNoModal();
        const lista = document.getElementById('modal-producto-neo-lista');
        const totalEl = document.getElementById('modal-producto-neo-total-urls');
        const btn = document.getElementById('btnProductoNeo');
        const modal = document.getElementById('modal-producto-neo');
        lista.innerHTML = '<p class="text-gray-500 dark:text-gray-400 text-sm">Cargando...</p>';
        if (totalEl) totalEl.textContent = '…';
        const urlListado = '{{ route("admin.neo.crear-masivo.productos") }}' + '?' + queryModalProductoNeoFiltros() + '&_=' + Date.now();
        const res = await fetch(urlListado, { cache: 'no-store', headers: { 'Accept': 'application/json' } });
        const parsedList = parseRespuestaProductosNeoApi(await res.json());
        const productos = parsedList.productos;
        const nuloCountEl = document.getElementById('modal-producto-neo-null-count');
        if (nuloCountEl) {
            nuloCountEl.textContent = String(parsedList.filas_neo_tienda_id_null);
        }
        if (!Array.isArray(productos) || productos.length === 0) {
            lista.innerHTML = '<p class="text-gray-500 dark:text-gray-400 text-sm">No hay productos con filas neo (añadida=no) para este filtro.</p>';
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
    }

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
        const detTiendas = document.getElementById('modal-producto-neo-details-tiendas-no');
        if (detTiendas) {
            detTiendas.classList.add('hidden');
            detTiendas.open = false;
        }
        if (chkSiOpen) chkSiOpen.checked = true;
        if (chkNoOpen) chkNoOpen.checked = false;
        const chkNullOpen = document.getElementById('modal-producto-neo-chk-tienda-null');
        if (chkNullOpen) chkNullOpen.checked = false;
        btn.disabled = true;
        modal.classList.remove('hidden');
        try {
            await recargarListaModalProductoNeo();
        } catch (err) {
            console.error(err);
            document.getElementById('modal-producto-neo-lista').innerHTML = '<p class="text-red-500 text-sm">Error al cargar: ' + err.message + '</p>';
            const totalEl = document.getElementById('modal-producto-neo-total-urls');
            if (totalEl) totalEl.textContent = '—';
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
                await recargarListaModalProductoNeo();
            } catch (err) {
                console.error(err);
                document.getElementById('modal-producto-neo-lista').innerHTML = '<p class="text-red-500 text-sm">Error al cargar: ' + err.message + '</p>';
            }
        });
    });

    document.getElementById('modal-producto-neo-cerrar').addEventListener('click', function() {
        document.getElementById('modal-producto-neo').classList.add('hidden');
    });
    document.getElementById('modal-producto-neo-cerrar-btn').addEventListener('click', function() {
        document.getElementById('modal-producto-neo').classList.add('hidden');
    });

    const estadoModalCategoriaNeo = {
        categorias: [],
        categoriaSeleccionada: null,
    };

    function actualizarHeaderModalCategoriaNeo() {
        const titulo = document.querySelector('#modal-categoria-neo h3');
        const btnAtras = document.getElementById('modal-categoria-neo-atras');
        if (estadoModalCategoriaNeo.categoriaSeleccionada) {
            if (titulo) titulo.textContent = 'Elegir tienda de ' + (estadoModalCategoriaNeo.categoriaSeleccionada.nombre || ('Categoría #' + estadoModalCategoriaNeo.categoriaSeleccionada.categoria_id));
            if (btnAtras) btnAtras.classList.remove('hidden');
        } else {
            if (titulo) titulo.textContent = 'Elegir categoría (neo con añadida=no)';
            if (btnAtras) btnAtras.classList.add('hidden');
        }
    }

    function renderCategoriasModalNeo() {
        const lista = document.getElementById('modal-categoria-neo-lista');
        const categorias = estadoModalCategoriaNeo.categorias || [];
        estadoModalCategoriaNeo.categoriaSeleccionada = null;
        actualizarHeaderModalCategoriaNeo();
        if (!Array.isArray(categorias) || categorias.length === 0) {
            lista.innerHTML = '<p class="text-gray-500 dark:text-gray-400 text-sm">No hay categorías con filas neo (añadida=no).</p>';
            return;
        }
        lista.innerHTML = '';
        categorias.forEach(function(item) {
            const div = document.createElement('div');
            div.className = 'p-3 rounded-lg border border-gray-200 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer transition';
            div.innerHTML = '<span class="font-medium text-gray-900 dark:text-white">' + (item.nombre || 'Categoría #' + item.categoria_id) + '</span> <span class="text-gray-500 dark:text-gray-400 text-sm">(' + item.count + ' URL' + (item.count !== 1 ? 's' : '') + ')</span>';
            div.addEventListener('click', async function() {
                await renderTiendasDeCategoriaModalNeo(item);
            });
            lista.appendChild(div);
        });
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
        estadoModalCategoriaNeo.categoriaSeleccionada = item;
        actualizarHeaderModalCategoriaNeo();
        lista.innerHTML = '<p class="text-gray-500 dark:text-gray-400 text-sm">Cargando tiendas...</p>';
        try {
            const res = await fetch('{{ route("admin.neo.crear-masivo.tiendas-por-categoria", ["categoriaId" => "__ID__"]) }}'.replace('__ID__', item.categoria_id), { headers: { 'Accept': 'application/json' } });
            const tiendas = await res.json();
            if (!Array.isArray(tiendas) || tiendas.length === 0) {
                lista.innerHTML = '<p class="text-gray-500 dark:text-gray-400 text-sm">No hay tiendas pendientes para esta categoría.</p>';
                return;
            }
            lista.innerHTML = '';
            tiendas.forEach(function(tienda) {
                const div = document.createElement('div');
                div.className = 'p-3 rounded-lg border border-gray-200 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer transition';
                div.innerHTML = '<span class="font-medium text-gray-900 dark:text-white">' + (tienda.nombre || ('Tienda #' + tienda.tienda_id)) + '</span>' + htmlEtiquetasTiendaRestringidaNeo(tienda) + ' <span class="text-gray-500 dark:text-gray-400 text-sm">(' + tienda.count + ' URL' + (tienda.count !== 1 ? 's' : '') + ')</span>';
                div.addEventListener('click', async function() {
                    try {
                        const resUrl = await fetch('{{ route("admin.neo.crear-masivo.urls-por-categoria-tienda", ["categoriaId" => "__CID__", "tiendaId" => "__TID__"]) }}'.replace('__CID__', item.categoria_id).replace('__TID__', tienda.tienda_id), { headers: { 'Accept': 'application/json' } });
                        const dataUrl = await resUrl.json();
                        window.__neoCategoriaFiltro = { id: item.categoria_id, nombre: item.nombre || ('Categoría #' + item.categoria_id) };
                        actualizarIndicadorCategoriaFiltroNeo();
                        pegarUrlsEnTextareaCrearMasivo(dataUrl.urls || []);
                        window.__crearMasivoOrigenNeo = null;
                        actualizarVisibilidadFooterSiguienteNeo();
                        document.getElementById('modal-categoria-neo').classList.add('hidden');
                    } catch (err) {
                        console.error(err);
                        alert('Error al cargar URLs: ' + err.message);
                    }
                });
                lista.appendChild(div);
            });
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
        btn.disabled = true;
        estadoModalCategoriaNeo.categoriaSeleccionada = null;
        actualizarHeaderModalCategoriaNeo();
        lista.innerHTML = '<p class="text-gray-500 dark:text-gray-400 text-sm">Cargando...</p>';
        modal.classList.remove('hidden');
        try {
            const res = await fetch('{{ route("admin.neo.crear-masivo.categorias") }}', { headers: { 'Accept': 'application/json' } });
            const categorias = await res.json();
            estadoModalCategoriaNeo.categorias = Array.isArray(categorias) ? categorias : [];
            renderCategoriasModalNeo();
        } catch (err) {
            console.error(err);
            lista.innerHTML = '<p class="text-red-500 text-sm">Error al cargar: ' + err.message + '</p>';
        }
        btn.disabled = false;
    });
    document.getElementById('modal-categoria-neo-atras').addEventListener('click', function() {
        renderCategoriasModalNeo();
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
        const titulo = document.querySelector('#modal-tienda-neo h3');
        const btnAtras = document.getElementById('modal-tienda-neo-atras');
        if (estadoModalTiendaNeo.tiendaSeleccionada) {
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
            return;
        }
        lista.innerHTML = '';
        tiendas.forEach(function(item) {
            const div = document.createElement('div');
            div.className = 'p-3 rounded-lg border border-gray-200 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer transition';
            div.innerHTML = '<span class="font-medium text-gray-900 dark:text-white">' + (item.nombre || 'Tienda #' + item.tienda_id) + '</span>' + htmlEtiquetasTiendaRestringidaNeo(item) + ' <span class="text-gray-500 dark:text-gray-400 text-sm">(' + item.count + ' URL' + (item.count !== 1 ? 's' : '') + ')</span>';
            div.addEventListener('click', async function() {
                await renderCategoriasDeTiendaModalNeo(item);
            });
            lista.appendChild(div);
        });
    }

    /**
     * Pega URLs desde neo por tienda (opcionalmente por categoría) y guarda contexto para Siguiente.
     */
    async function aplicarTiendaNeoUrlsAlFormulario(item, categoriaId, categoriaNombre) {
        const routeBase = categoriaId == null
            ? '{{ route("admin.neo.crear-masivo.urls-por-tienda", ["tiendaId" => "__TID__"]) }}'.replace('__TID__', item.tienda_id)
            : '{{ route("admin.neo.crear-masivo.urls-por-tienda-categoria", ["tiendaId" => "__TID__", "categoriaId" => "__CID__"]) }}'.replace('__TID__', item.tienda_id).replace('__CID__', categoriaId);
        const resUrl = await fetch(routeBase, { headers: { 'Accept': 'application/json' } });
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
        const urlTiendas = '{{ route("admin.neo.crear-masivo.tiendas") }}' + '?_=' + Date.now();
        const resTiendas = await fetch(urlTiendas, { headers: { 'Accept': 'application/json' } });
        const tiendas = await resTiendas.json();
        if (!Array.isArray(tiendas) || tiendas.length === 0) {
            alert('No quedan tiendas pendientes.');
            return;
        }
        const tidCur = parseInt(String(ctx.tienda_id), 10);
        const tidIndex = tiendas.findIndex(function(t) { return parseInt(String(t.tienda_id), 10) === tidCur; });
        const curTienda = tidIndex >= 0 ? tiendas[tidIndex] : null;
        const preferredCat = ctx.categoria_id != null ? parseInt(String(ctx.categoria_id), 10) : null;

        if (preferredCat != null && curTienda) {
            const resCat = await fetch('{{ route("admin.neo.crear-masivo.categorias-por-tienda", ["tiendaId" => "__ID__"]) }}'.replace('__ID__', curTienda.tienda_id), { headers: { 'Accept': 'application/json' } } );
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

        const resCat2 = await fetch('{{ route("admin.neo.crear-masivo.categorias-por-tienda", ["tiendaId" => "__ID__"]) }}'.replace('__ID__', nextTienda.tienda_id), { headers: { 'Accept': 'application/json' } } );
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
        try {
            const res = await fetch('{{ route("admin.neo.crear-masivo.categorias-por-tienda", ["tiendaId" => "__ID__"]) }}'.replace('__ID__', item.tienda_id), { headers: { 'Accept': 'application/json' } });
            const categorias = await res.json();
            lista.innerHTML = '';

            const todasDiv = document.createElement('div');
            todasDiv.className = 'p-3 rounded-lg border border-blue-300 dark:border-blue-600 bg-blue-50 dark:bg-blue-900/20 hover:bg-blue-100 dark:hover:bg-blue-900/30 cursor-pointer transition';
            todasDiv.innerHTML = '<span class="font-semibold text-blue-800 dark:text-blue-300">Todas</span> <span class="text-blue-700 dark:text-blue-400 text-sm">(' + (item.count || 0) + ' URL' + ((item.count || 0) !== 1 ? 's' : '') + ')</span>';
            todasDiv.addEventListener('click', async function() {
                await cargarUrlsDeTiendaYCerrarModal(item, null);
            });
            lista.appendChild(todasDiv);

            if (!Array.isArray(categorias) || categorias.length === 0) {
                const vacio = document.createElement('p');
                vacio.className = 'text-gray-500 dark:text-gray-400 text-sm';
                vacio.textContent = 'No hay categorías pendientes para esta tienda.';
                lista.appendChild(vacio);
                return;
            }

            categorias.forEach(function(cat) {
                const div = document.createElement('div');
                div.className = 'p-3 rounded-lg border border-gray-200 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer transition';
                div.innerHTML = '<span class="font-medium text-gray-900 dark:text-white">' + (cat.nombre || ('Categoría #' + cat.categoria_id)) + '</span> <span class="text-gray-500 dark:text-gray-400 text-sm">(' + cat.count + ' URL' + (cat.count !== 1 ? 's' : '') + ')</span>';
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
        const lista = document.getElementById('modal-tienda-neo-lista');
        btn.disabled = true;
        estadoModalTiendaNeo.tiendaSeleccionada = null;
        actualizarHeaderModalTiendaNeo();
        lista.innerHTML = '<p class="text-gray-500 dark:text-gray-400 text-sm">Cargando...</p>';
        modal.classList.remove('hidden');
        try {
            const res = await fetch('{{ route("admin.neo.crear-masivo.tiendas") }}', { headers: { 'Accept': 'application/json' } });
            const tiendas = await res.json();
            estadoModalTiendaNeo.tiendas = Array.isArray(tiendas) ? tiendas : [];
            renderTiendasModalNeo();
        } catch (err) {
            console.error(err);
            lista.innerHTML = '<p class="text-red-500 text-sm">Error al cargar: ' + err.message + '</p>';
        }
        btn.disabled = false;
    });
    document.getElementById('modal-tienda-neo-atras').addEventListener('click', function() {
        renderTiendasModalNeo();
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
                        console.log('[Crear-masivo] URL ' + idx + ': NO se envía a ChatGPT → ' + (r.descartada ? 'URL descartada' : 'ya existe oferta para esta URL'));
                    } else if (!r.tienda) {
                        console.log('[Crear-masivo] URL ' + idx + ': NO se envía a ChatGPT → tienda no detectada. URL: ' + url);
                    } else if (!r.productos_candidatos || r.productos_candidatos.length === 0) {
                        console.log('[Crear-masivo] URL ' + idx + ': NO se envía a ChatGPT → sin productos candidatos (revisar slug o coincidencias en BD). URL: ' + url);
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
                        console.log('[Crear-masivo] URL índice ' + idx + ' – Prompt:', u.prompt);
                        console.log('[Crear-masivo] URL índice ' + idx + ' – Respuesta:', u.raw_content);
                        console.log('[Crear-masivo] URL índice ' + idx + ' – Parsed:', u.parsed_resultados);
                    });
                } else {
                    console.log('[Crear-masivo] Prompt enviado a ChatGPT:', data.chatgpt_raw_response.prompt);
                    console.log('[Crear-masivo] Respuesta ChatGPT (raw_content):', data.chatgpt_raw_response.raw_content);
                    console.log('[Crear-masivo] Respuesta ChatGPT (parsed_resultados):', data.chatgpt_raw_response.parsed_resultados);
                }
            }

            if (data.success) {
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
        const quitarYImg = '<button type="button" class="btn-quitar-producto inline-flex items-center justify-center w-6 h-6 rounded bg-red-500 hover:bg-red-600 text-white text-xs font-bold transition" title="Quitar producto y elegir otro">✕</button><button type="button" class="btn-ver-imagenes-producto-crear-masivo inline-flex items-center p-1 bg-blue-600 hover:bg-blue-700 text-white rounded text-xs ml-0.5" title="Ver imágenes del producto">' + iconoImgComoEspecs + '</button>';
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
            '<span class="categoria-fila-badge text-xs text-gray-600 dark:text-gray-400' + (cf ? '' : ' hidden') + '">URL movida a la categoría: <strong class="categoria-fila-nombre">' + nombreCat + '</strong></span>' +
            '<button type="button" class="btn-quitar-categoria-fila-crear-masivo text-xs text-red-600 dark:text-red-400 hover:underline' + (cf ? '' : ' hidden') + '">Quitar filtro categoría</button>' +
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
        const btnQuitar = toolbar.querySelector('.btn-quitar-categoria-fila-crear-masivo');
        if (cf) {
            if (nombreEl) nombreEl.textContent = cf.nombre || '';
            if (badge) badge.classList.remove('hidden');
            if (btnQuitar) btnQuitar.classList.remove('hidden');
        } else {
            if (nombreEl) nombreEl.textContent = '';
            if (badge) badge.classList.add('hidden');
            if (btnQuitar) btnQuitar.classList.add('hidden');
        }
    }

    function abrirModalCategoriaFilaCrearMasivo(div) {
        window.__filaModalCategoriaCrearMasivo = div;
        const modal = document.getElementById('modal-categoria-fila-crear-masivo');
        if (modal) modal.classList.remove('hidden');
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
        const inp = div.querySelector('.producto-search-input');
        if (inp && inp.value.trim().length >= 2) buscarProductosCrearMasivo(div, inp.value.trim());
    }

    // Fondos grises alternos para que se distinga bien el bloque frente al resto de resultados.
    const CREAR_MASIVO_GRUPO_BORDE_CLASSES = [
        'border-gray-400 dark:border-gray-500 bg-gray-200 dark:bg-gray-800 shadow-inner ring-1 ring-gray-300/90 dark:ring-gray-600',
        'border-gray-500 dark:border-gray-400 bg-gray-300/95 dark:bg-zinc-900 shadow-inner ring-1 ring-gray-400/80 dark:ring-zinc-600',
    ];

    /** Clave de agrupación: mismo id de producto sugerido → mismo grupo; sin producto → fila suelta. */
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

    /** Desplaza a la primera fila del bloque de resultados (p. ej. tras «Siguientes URLs»). */
    function scrollCrearMasivoAPrimerResultadoAnalisis() {
        requestAnimationFrame(function() {
            const lista = document.getElementById('resultadosLista');
            const first = lista && lista.firstElementChild;
            if (first && typeof first.scrollIntoView === 'function') {
                first.scrollIntoView({ behavior: 'smooth', block: 'start' });
                return;
            }
            const res = document.getElementById('resultados');
            if (res && !res.classList.contains('hidden') && typeof res.scrollIntoView === 'function') {
                res.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    }

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
            const resDiv = document.getElementById('resultados');
            const listaDiv = document.getElementById('resultadosLista');
            if (listaDiv) listaDiv.innerHTML = '';
            if (resDiv) resDiv.classList.add('hidden');
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
        document.getElementById('resultados').classList.remove('hidden');

        const ordenados = ordenarResultadosPorGrupoProductoCrearMasivo(resultados);
        const conteosGrupo = contarPorClaveGrupoCrearMasivo(ordenados);
        let claveGrupoWrapperAbierto = null;
        let nodoWrapperGrupo = null;
        let indiceEstiloGrupo = 0;

        ordenados.forEach((r, idx) => {
            const div = document.createElement('div');
            const puedeCrear = !r.existe && r.tienda && r.producto && !r.error;
            const necesitaProducto = !r.existe && r.tienda && !r.producto;
            const noEntreOpciones = r.no_entre_opciones === true;
            let estadoClass = 'bg-gray-50 dark:bg-gray-800';
            let estadoText = '';
            if (r.existe) {
                estadoClass = 'bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700';
                estadoText = r.descartada ? 'URL descartada' : (r.existe_otros_productos ? 'URL ya existe en otros productos' : 'URL ya existe');
            } else if (noEntreOpciones) {
                estadoClass = 'bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700';
                estadoText = 'No está entre las posibles opciones. Busca uno manualmente:';
            } else if (necesitaProducto) {
                estadoClass = 'bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700';
                estadoText = 'Producto no encontrado. Busca uno manualmente:';
            } else if (puedeCrear) {
                estadoClass = 'bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-700';
                estadoText = 'Lista para crear';
            } else if (r.error) {
                estadoClass = 'bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700';
                estadoText = r.error;
            } else {
                estadoText = 'No se pudo determinar producto o tienda';
            }

            let especsHtml = buildEspecsHtml(r.producto, r.especificaciones, r.tiene_especificaciones);
            let buscadorProductoHtml = '';
            if (necesitaProducto || noEntreOpciones) {
                buscadorProductoHtml = buildBuscadorProductoHtmlCrearMasivo(r.url_normalizada || r.url || '', r.categoria_fila || null);
            }

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
                        const verBtn = o.url ? ' <a href="' + o.url + '" target="_blank" class="inline-flex items-center px-2 py-0.5 bg-gray-600 hover:bg-gray-700 text-white text-xs font-medium rounded">Ver</a>' : '';
                        const editarBtn = o.oferta_edit_url ? ' <a href="' + o.oferta_edit_url + '" target="_blank" class="inline-flex items-center px-2 py-0.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium rounded">Editar oferta</a>' : '';
                        return '<div>' + prodLink + ' – ' + (o.tienda || '') + verBtn + editarBtn + '</div>';
                    }).join('') + '</div>';
            }

            div.className = 'crear-masivo-fila p-4 rounded-lg border ' + estadoClass;
            div.dataset.esUnidadUnica = r.especificaciones && r.especificaciones.unidad_de_medida === 'unidadUnica' ? '1' : '0';
            div.dataset.columnasIds = (r.especificaciones && r.especificaciones.columnas_ids) ? JSON.stringify(r.especificaciones.columnas_ids) : '[]';
            const btnVerPromptHtml = (r.chatgpt_prompt ? '<button type="button" class="btn-ver-prompt-crear-masivo inline-flex items-center px-2 py-1 text-xs font-medium rounded border border-gray-400 dark:border-gray-500 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition">Ver prompt</button>' : '');
            const btnAgregarProductoHtml = '<a href="{{ route("admin.productos.create") }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center justify-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded transition"><svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>Añadir producto</a>';
            const btnCategoriaFilaHtml = '<button type="button" class="btn-elegir-categoria-fila-crear-masivo inline-flex items-center justify-center px-4 py-2 text-sm font-medium rounded border border-indigo-300 dark:border-indigo-600 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-800 dark:text-indigo-200 hover:bg-indigo-100 dark:hover:bg-indigo-900/50 transition">Categoría</button>';
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
                setTimeout(() => actualizarConteosOpcionesEspecsFila(div), 50);
            }

            const especsMarcadasAuto = (r.especificaciones_marcadas_chatgpt && typeof r.especificaciones_marcadas_chatgpt === 'object')
                ? r.especificaciones_marcadas_chatgpt
                : ((r.especificaciones_marcadas && typeof r.especificaciones_marcadas === 'object') ? r.especificaciones_marcadas : null);
            if (especsMarcadasAuto) {
                Object.keys(especsMarcadasAuto).forEach(function(principalId) {
                    const subIds = especsMarcadasAuto[principalId];
                    if (!Array.isArray(subIds)) return;
                    subIds.forEach(function(subId) {
                        const cb = div.querySelector('.spec-checkbox[data-principal-id="' + principalId + '"][data-sublinea-id="' + subId + '"]');
                        if (cb) cb.checked = true;
                    });
                });
                actualizarEstadoBotonGenerar(div);
                renderUrlResaltadaFilaCrearMasivo(div);
                actualizarConteosOpcionesEspecsFila(div);
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
                const btnQCatFila = e.target.closest('.btn-quitar-categoria-fila-crear-masivo');
                if (btnQCatFila) {
                    e.preventDefault();
                    if (!div.__rowData) return;
                    const urlQ = div.__rowData.url_normalizada || div.__rowData.url || '';
                    (async function() {
                        if (urlQ) {
                            try {
                                await guardarCategoriaNeoServidorCrearMasivo(urlQ, null);
                            } catch (err) {
                                console.error(err);
                                alert('No se pudo quitar la categoría en neo: ' + (err && err.message ? err.message : err));
                                return;
                            }
                        }
                        div.__rowData = Object.assign({}, div.__rowData, { categoria_fila: null });
                        actualizarToolbarCategoriaFila(div);
                        const inpQ = div.querySelector('.producto-search-input');
                        if (inpQ && inpQ.value.trim().length >= 2) buscarProductosCrearMasivo(div, inpQ.value.trim());
                    })();
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
                        if (sug && div.__productosBusqueda && div.__productosBusqueda.length) sug.classList.remove('hidden');
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

            if (puedeCrear && r.hay_empate && r.candidatos_empatados) {
                div.__candidatosEmpatados = r.candidatos_empatados;
            }

            if (puedeCrear) {
                div.querySelector('.btn-generar').addEventListener('click', async function() {
                    const btnGen = this;
                    btnGen.disabled = true;
                    btnGen.innerHTML = '<svg class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg> Creando...';

                    try {
                        marcarInicioGeneracionOfertaCrearMasivo();
                        const msgEl = div.querySelector('.generado-msg');
                        // Validar que si hay especificaciones internas, cada grupo tenga al menos una opción marcada
                        const specLines = div.querySelectorAll('.spec-line');
                        if (specLines.length) {
                            let gruposSinSeleccion = 0;
                            specLines.forEach(line => {
                                if (!line.querySelector('.spec-checkbox:checked')) gruposSinSeleccion++;
                            });
                            if (gruposSinSeleccion > 0) {
                                msgEl.classList.remove('hidden');
                                msgEl.className = 'mt-2 generado-msg text-sm font-medium text-red-600 dark:text-red-400';
                                msgEl.textContent = 'Debes marcar al menos una opción en cada grupo de especificaciones internas.';
                                btnGen.disabled = false;
                                btnGen.innerHTML = '<svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg> Generar oferta';
                                return;
                            }
                        }

                        const especs = buildEspecificacionesFromRow(div);
                        console.log('[CrearOfertaBulk] Especificaciones built:', especs);

                        // Si el usuario ya confirmó "No es la misma oferta", no volver a comprobar duplicados y crear directamente
                        const confirmoNoEsMisma = div.querySelector('.confirmo-no-es-misma:checked');
                        const yaConfirmoDistinta = !!confirmoNoEsMisma;

                        // Buscar ofertas existentes con mismas especificaciones (no bloquea la creación si ya confirmó)
                        console.log('[MismasEspecs] Condición:', { tieneEspecs: !!especs, yaConfirmoDistinta, tieneRowData: !!div.__rowData, producto: div.__rowData?.producto?.id, tienda: div.__rowData?.tienda?.id });
                        if (!yaConfirmoDistinta && especs && div.__rowData && div.__rowData.producto && div.__rowData.tienda) {
                            try {
                                const dupBody = { producto_id: div.__rowData.producto.id, tienda_id: div.__rowData.tienda.id, especificaciones_internas: JSON.stringify(especs) };
                                console.log('[MismasEspecs] Request body:', dupBody);
                                const dupRes = await fetch('{{ route("admin.ofertas.crear-masivo.buscar-mismas-especificaciones") }}', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                        'Accept': 'application/json',
                                    },
                                    body: JSON.stringify(dupBody),
                                });
                                console.log('[MismasEspecs] Response status:', dupRes.status);
                                const dupData = await dupRes.json().catch((err) => { console.error('[MismasEspecs] JSON parse error:', err); return {}; });
                                console.log('[MismasEspecs] Response data:', dupData);
                                const container = div.querySelector('.spec-and-ofertas-container');
                                console.log('[MismasEspecs] Container:', !!container, 'success:', dupData.success, 'ofertas:', dupData.ofertas, 'length:', dupData.ofertas?.length);
                                if (container) {
                                    const old = container.querySelector('.ofertas-mismas-especs');
                                    if (old) { old.remove(); quitarCheckboxNoEsMisma(div); }
                                    actualizarEstadoBotonGenerar(div);
                                }
                                if (container && dupData.success && Array.isArray(dupData.ofertas) && dupData.ofertas.length) {
                                    const html = '<div class="mt-3 ofertas-mismas-especs text-sm text-amber-700 dark:text-amber-300"><span class="font-medium">Ya existen ofertas con estas especificaciones en esta tienda:</span>' +
                                        dupData.ofertas.map(o => {
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
                                    btnGen.innerHTML = '<svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg> Generar oferta';
                                    console.log('[MismasEspecs] HTML insertado correctamente, ofertas:', dupData.ofertas.length);
                                    ponerMensajeGeneradoPendienteCrearMasivo(div);
                                    return;
                                } else {
                                    console.log('[MismasEspecs] No se insertó HTML. Razón: container=' + !!container + ', success=' + dupData.success + ', ofertas length=' + (dupData.ofertas?.length ?? 'N/A'));
                                }
                            } catch (e) {
                                console.error('[CrearOfertaBulk] Error buscando duplicados por especificaciones:', e);
                            }
                        } else {
                            console.log('[MismasEspecs] No se llamó al endpoint (falta especs, rowData, producto o tienda)');
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
                        if (!data2.success || res2.status >= 400) {
                            logCrearOfertaBulkDiagnostico('[CrearOfertaBulk]', res2, responseText, data2);
                        }

                        msgEl.classList.remove('hidden');
                        if (data2.success) {
                            // Marcar visualmente la fila como "procesada": pasar de verde a gris claro solo en esta URL
                            div.classList.remove('bg-green-50', 'dark:bg-green-900/20', 'border-green-200', 'dark:border-green-700');
                            div.classList.add('bg-gray-50', 'dark:bg-gray-800', 'border-gray-200', 'dark:border-gray-700');
                            if (div.__rowData) div.__rowData.ofertaGenerada = true;
                            const btnQuitar = div.querySelector('.btn-quitar-producto');
                            if (btnQuitar) btnQuitar.remove();
                            msgEl.className = 'mt-2 generado-msg text-sm font-medium text-green-600 dark:text-green-400';
                            msgEl.textContent = 'Oferta creada correctamente (ID: ' + data2.oferta_id + ')';
                            if (data2.oferta_edit_url) {
                                const envioVal = data2.envio != null && data2.envio !== '' ? parseFloat(data2.envio).toFixed(2).replace('.', ',') + ' € env.' : 'gratis';
                                const precioVal = data2.precio_unidad != null ? parseFloat(data2.precio_unidad).toFixed(2).replace('.', ',') + ' €/ud' : '';
                                const wrap = crearWrapEditarOfertaConResumenCrearMasivo(data2.oferta_edit_url, envioVal, precioVal);
                                btnGen.parentNode.replaceChild(wrap, btnGen);
                            } else {
                                btnGen.remove();
                            }
                        } else {
                            const errTxt = (data2.error || '').toLowerCase();
                            // Genérico: cualquier error que indique sin precio o no disponible (cualquier tienda)
                            const esErrorPrecio = errTxt.includes('precio') || errTxt.includes('price') || errTxt.includes('no disponible') || errTxt.includes('not available') || errTxt.includes('unavailable') || errTxt.includes('sin stock') || errTxt.includes('agotado') || errTxt.includes('out of stock') || errTxt.includes('no se pudo obtener') || errTxt.includes('could not get');
                            if (esErrorPrecio) {
                                msgEl.className = 'mt-2 generado-msg text-sm font-medium text-amber-600 dark:text-amber-400';
                                msgEl.textContent = 'Precio no encontrado. Creando oferta con precio 0...';
                                body.generar_sin_precio = true;
                                const res3 = await fetch('{{ route("admin.ofertas.crear-masivo.crear") }}', {
                                    method: 'POST',
                                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' },
                                    body: JSON.stringify(body),
                                });
                                const res3Text = await res3.text();
                                let data3;
                                try { data3 = JSON.parse(res3Text); } catch (e) { data3 = {}; }
                                if (!data3.success || res3.status >= 400) {
                                    logCrearOfertaBulkDiagnostico('[CrearOfertaBulk] reintento sin precio', res3, res3Text, data3);
                                }
                                if (data3.success) {
                                    div.classList.remove('bg-green-50', 'dark:bg-green-900/20', 'border-green-200', 'dark:border-green-700');
                                    div.classList.add('bg-gray-50', 'dark:bg-gray-800', 'border-gray-200', 'dark:border-gray-700');
                                    if (div.__rowData) div.__rowData.ofertaGenerada = true;
                                    const btnQuitar = div.querySelector('.btn-quitar-producto');
                                    if (btnQuitar) btnQuitar.remove();
                                    msgEl.className = 'mt-2 generado-msg text-sm font-medium text-green-600 dark:text-green-400';
                                    msgEl.textContent = 'Oferta creada con precio 0 (ID: ' + data3.oferta_id + ')';
                                    if (data3.oferta_edit_url) {
                                        const envioVal = data3.envio != null && data3.envio !== '' ? parseFloat(data3.envio).toFixed(2).replace('.', ',') + ' € env.' : 'gratis';
                                        const precioVal = data3.precio_unidad != null ? parseFloat(data3.precio_unidad).toFixed(2).replace('.', ',') + ' €/ud' : '0 €/ud';
                                        const wrap = crearWrapEditarOfertaConResumenCrearMasivo(data3.oferta_edit_url, envioVal, precioVal);
                                        btnGen.parentNode.replaceChild(wrap, btnGen);
                                    } else { btnGen.remove(); }
                                } else {
                                    msgEl.className = 'mt-2 generado-msg text-sm font-medium text-red-600 dark:text-red-400';
                                    msgEl.textContent = data3.error || 'Error al crear';
                                    btnGen.disabled = false;
                                    btnGen.innerHTML = '<svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg> Generar oferta';
                                }
                            } else {
                                msgEl.className = 'mt-2 generado-msg text-sm font-medium text-red-600 dark:text-red-400';
                                msgEl.textContent = data2.error || 'Error al crear';
                                btnGen.disabled = false;
                                btnGen.innerHTML = '<svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg> Generar oferta';
                            }
                        }
                    } catch (err) {
                        console.error('[CrearOfertaBulk] Exception:', err);
                        div.querySelector('.generado-msg').classList.remove('hidden');
                        div.querySelector('.generado-msg').className = 'mt-2 generado-msg text-sm font-medium text-red-600 dark:text-red-400';
                        div.querySelector('.generado-msg').textContent = 'Error: ' + err.message;
                        btnGen.disabled = false;
                        btnGen.innerHTML = '<svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg> Generar oferta';
                    } finally {
                        marcarFinGeneracionOfertaCrearMasivo();
                    }
                });
            }
        });
        actualizarVisibilidadFooterSiguienteNeo();
    }

    async function buscarProductosCrearMasivo(div, query) {
        try {
            const row = div.__rowData || {};
            let urlBus = `{{ route('admin.ofertas.buscar.productos') }}?q=${encodeURIComponent(query)}`;
            if (row.categoria_fila && row.categoria_fila.id) {
                urlBus += '&categoria_id=' + encodeURIComponent(String(row.categoria_fila.id));
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
    }

    function quitarProductoYMostrarBuscador(div) {
        if (div.__rowData?.ofertaGenerada) return;
        const r = div.__rowData;
        if (!r || !r.tienda) return;
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
                    return '<div>' + prodLink + ' – ' + (o.tienda || '') + verBtn + editarBtn + '</div>';
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
            btnAgregarWrap.innerHTML = '<button type="button" class="btn-descartar-url ' + (mostrarBotonDescartar ? '' : 'hidden ') + 'inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded transition" data-url="' + urlFila + '"><svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 7h12M9 7V5h6v2m-7 4v6m4-6v6"></path></svg>Descartar URL</button><div class="flex flex-col items-stretch gap-2"><a href="{{ route("admin.productos.create") }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center justify-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded transition"><svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>Añadir producto</a><button type="button" class="btn-elegir-categoria-fila-crear-masivo inline-flex items-center justify-center px-4 py-2 text-sm font-medium rounded border border-indigo-300 dark:border-indigo-600 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-800 dark:text-indigo-200 hover:bg-indigo-100 dark:hover:bg-indigo-900/50 transition">Categoría</button></div>';
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
                if (sug && div.__productosBusqueda && div.__productosBusqueda.length) sug.classList.remove('hidden');
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
            const especsHtml = buildEspecsHtml(productoCompleto, especs, tieneEspecs);
            const specParent = div.querySelector('.spec-and-ofertas-container');
            if (specParent) {
                specParent.innerHTML = especsHtml + (r.ofertas_existentes && r.ofertas_existentes.length ? '<div class="mt-2 text-sm text-amber-700 dark:text-amber-300 space-y-1"><span class="font-medium">Existe en:</span>' + r.ofertas_existentes.map(o => {
                    const prodLink = o.url_producto ? '<a href="' + o.url_producto + '" target="_blank" class="text-green-500 hover:underline font-medium">' + (o.producto || '') + '</a>' : (o.producto || '');
                    const verBtn = o.url ? ' <a href="' + o.url + '" target="_blank" class="inline-flex items-center px-2 py-0.5 bg-gray-600 hover:bg-gray-700 text-white text-xs font-medium rounded">Ver</a>' : '';
                    const editarBtn = o.oferta_edit_url ? ' <a href="' + o.oferta_edit_url + '" target="_blank" class="inline-flex items-center px-2 py-0.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium rounded">Editar oferta</a>' : '';
                    return '<div>' + prodLink + ' – ' + (o.tienda || '') + verBtn + editarBtn + '</div>';
                }).join('') + '</div>' : '');
            }
            div.__rowData = Object.assign({}, r, { producto: productoCompleto, especificaciones: especs, tiene_especificaciones: tieneEspecs });
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
            renderUrlResaltadaFilaCrearMasivo(div);
            actualizarConteosOpcionesEspecsFila(div);
            btnGen.addEventListener('click', async function() {
                const btn = this;
                btn.disabled = true;
                btn.innerHTML = '<svg class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg> Creando...';
                try {
                    marcarInicioGeneracionOfertaCrearMasivo();
                    const msgEl = div.querySelector('.generado-msg');
                    // Validar que si hay especificaciones internas, cada grupo tenga al menos una opción marcada
                    const specLines = div.querySelectorAll('.spec-line');
                    if (specLines.length) {
                        let gruposSinSeleccion = 0;
                        specLines.forEach(line => {
                            if (!line.querySelector('.spec-checkbox:checked')) gruposSinSeleccion++;
                        });
                        if (gruposSinSeleccion > 0) {
                            msgEl.classList.remove('hidden');
                            msgEl.className = 'mt-2 generado-msg text-sm font-medium text-red-600 dark:text-red-400';
                            msgEl.textContent = 'Debes marcar al menos una opción en cada grupo de especificaciones internas.';
                            btn.disabled = false;
                            btn.innerHTML = '<svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg> Generar oferta';
                            return;
                        }
                    }

                    const especsPayload = buildEspecificacionesFromRow(div);
                    console.log('[MismasEspecs-B] especsPayload:', especsPayload);

                    // Si el usuario ya confirmó "No es la misma oferta", no volver a comprobar duplicados y crear directamente
                    const confirmoNoEsMismaB = div.querySelector('.confirmo-no-es-misma:checked');
                    const yaConfirmoDistintaB = !!confirmoNoEsMismaB;

                    // Buscar ofertas existentes con mismas especificaciones (no bloquea la creación si ya confirmó)
                    if (!yaConfirmoDistintaB && especsPayload && div.__rowData && div.__rowData.producto && div.__rowData.tienda) {
                        try {
                            const dupBodyB = { producto_id: div.__rowData.producto.id, tienda_id: div.__rowData.tienda.id, especificaciones_internas: JSON.stringify(especsPayload) };
                            console.log('[MismasEspecs-B] Request body:', dupBodyB);
                            const dupRes = await fetch('{{ route("admin.ofertas.crear-masivo.buscar-mismas-especificaciones") }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                    'Accept': 'application/json',
                                },
                                body: JSON.stringify(dupBodyB),
                            });
                            console.log('[MismasEspecs-B] Response status:', dupRes.status);
                            const dupData = await dupRes.json().catch((err) => { console.error('[MismasEspecs-B] JSON parse error:', err); return {}; });
                            console.log('[MismasEspecs-B] Response data:', dupData);
                            const container = div.querySelector('.spec-and-ofertas-container');
                            console.log('[MismasEspecs-B] Container:', !!container, 'ofertas length:', dupData.ofertas?.length);
                            if (container) {
                                const old = container.querySelector('.ofertas-mismas-especs');
                                if (old) { old.remove(); quitarCheckboxNoEsMisma(div); }
                                actualizarEstadoBotonGenerar(div);
                            }
                            if (container && dupData.success && Array.isArray(dupData.ofertas) && dupData.ofertas.length) {
                                const html = '<div class="mt-3 ofertas-mismas-especs text-sm text-amber-700 dark:text-amber-300"><span class="font-medium">Ya existen ofertas con estas especificaciones en esta tienda:</span>' +
                                    dupData.ofertas.map(o => {
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
                                btn.innerHTML = '<svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg> Generar oferta';
                                console.log('[MismasEspecs-B] HTML insertado, ofertas:', dupData.ofertas.length);
                                ponerMensajeGeneradoPendienteCrearMasivo(div);
                                return;
                            } else {
                                console.log('[MismasEspecs-B] No se insertó. container=' + !!container + ', success=' + dupData.success + ', length=' + (dupData.ofertas?.length ?? 'N/A'));
                            }
                        } catch (e) {
                            console.error('[CrearOfertaBulk] Error buscando duplicados por especificaciones:', e);
                        }
                    } else {
                        console.log('[MismasEspecs-B] No se llamó (falta especsPayload, producto o tienda)');
                    }

                    const body = { url: btn.dataset.url, producto_id: btn.dataset.productoId, tienda_id: btn.dataset.tiendaId, especificaciones_internas: especsPayload ? JSON.stringify(especsPayload) : null };
                    aplicarEnvioInputAlBodyCrearMasivo(div, body);
                    const cbGenerarSinPrecioB = div.querySelector('.generar-sin-precio-cb');
                    if (cbGenerarSinPrecioB && cbGenerarSinPrecioB.checked) body.generar_sin_precio = true;
                    const res2 = await fetch('{{ route("admin.ofertas.crear-masivo.crear") }}', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' }, body: JSON.stringify(body) });
                    const res2TextB = await res2.text();
                    let data2;
                    try { data2 = JSON.parse(res2TextB); } catch (e) { data2 = {}; }
                    if (!data2.success || res2.status >= 400) {
                        logCrearOfertaBulkDiagnostico('[CrearOfertaBulk-B]', res2, res2TextB, data2);
                    }
                    msgEl.classList.remove('hidden');
                    if (data2.success) {
                        const wrapSinPrecioB = div.querySelector('.generar-sin-precio-wrap');
                        if (wrapSinPrecioB) wrapSinPrecioB.classList.add('hidden');
                        // Marcar visualmente la fila como "procesada": pasar de verde a gris claro solo en esta URL
                        div.classList.remove('bg-green-50', 'dark:bg-green-900/20', 'border-green-200', 'dark:border-green-700');
                        div.classList.add('bg-gray-50', 'dark:bg-gray-800', 'border-gray-200', 'dark:border-gray-700');
                        if (div.__rowData) div.__rowData.ofertaGenerada = true;
                        const btnQuitar = div.querySelector('.btn-quitar-producto');
                        if (btnQuitar) btnQuitar.remove();
                        msgEl.className = 'mt-2 generado-msg text-sm font-medium text-green-600 dark:text-green-400';
                        msgEl.textContent = 'Oferta creada correctamente (ID: ' + (data2.oferta_id || '') + ')';
                        if (data2.oferta_edit_url) {
                            const envioVal = data2.envio != null && data2.envio !== '' ? parseFloat(data2.envio).toFixed(2).replace('.', ',') + ' € env.' : 'gratis';
                            const precioVal = data2.precio_unidad != null ? parseFloat(data2.precio_unidad).toFixed(2).replace('.', ',') + ' €/ud' : '';
                            const wrap = crearWrapEditarOfertaConResumenCrearMasivo(data2.oferta_edit_url, envioVal, precioVal);
                            btn.parentNode.replaceChild(wrap, btn);
                        } else { btn.remove(); }
                    } else {
                        const errTxtB = (data2.error || '').toLowerCase();
                        const esErrorPrecioB = errTxtB.includes('precio') || errTxtB.includes('price') || errTxtB.includes('no disponible') || errTxtB.includes('not available') || errTxtB.includes('unavailable') || errTxtB.includes('sin stock') || errTxtB.includes('agotado') || errTxtB.includes('out of stock') || errTxtB.includes('no se pudo obtener') || errTxtB.includes('could not get');
                        if (esErrorPrecioB) {
                            msgEl.className = 'mt-2 generado-msg text-sm font-medium text-amber-600 dark:text-amber-400';
                            msgEl.textContent = 'Precio no encontrado. Creando oferta con precio 0...';
                            body.generar_sin_precio = true;
                            const res3B = await fetch('{{ route("admin.ofertas.crear-masivo.crear") }}', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' }, body: JSON.stringify(body) });
                            const res3BText = await res3B.text();
                            let data3B;
                            try { data3B = JSON.parse(res3BText); } catch (e) { data3B = {}; }
                            if (!data3B.success || res3B.status >= 400) {
                                logCrearOfertaBulkDiagnostico('[CrearOfertaBulk-B] reintento sin precio', res3B, res3BText, data3B);
                            }
                            if (data3B.success) {
                                const wrapSinPrecioB2 = div.querySelector('.generar-sin-precio-wrap');
                                if (wrapSinPrecioB2) wrapSinPrecioB2.classList.add('hidden');
                                div.classList.remove('bg-green-50', 'dark:bg-green-900/20', 'border-green-200', 'dark:border-green-700');
                                div.classList.add('bg-gray-50', 'dark:bg-gray-800', 'border-gray-200', 'dark:border-gray-700');
                                if (div.__rowData) div.__rowData.ofertaGenerada = true;
                                const btnQuitarB = div.querySelector('.btn-quitar-producto');
                                if (btnQuitarB) btnQuitarB.remove();
                                msgEl.className = 'mt-2 generado-msg text-sm font-medium text-green-600 dark:text-green-400';
                                msgEl.textContent = 'Oferta creada con precio 0 (ID: ' + data3B.oferta_id + ')';
                                if (data3B.oferta_edit_url) {
                                    const envioValB = data3B.envio != null && data3B.envio !== '' ? parseFloat(data3B.envio).toFixed(2).replace('.', ',') + ' € env.' : 'gratis';
                                    const precioValB = data3B.precio_unidad != null ? parseFloat(data3B.precio_unidad).toFixed(2).replace('.', ',') + ' €/ud' : '0 €/ud';
                                    const wrapB = crearWrapEditarOfertaConResumenCrearMasivo(data3B.oferta_edit_url, envioValB, precioValB);
                                    btn.parentNode.replaceChild(wrapB, btn);
                                } else { btn.remove(); }
                            } else {
                                msgEl.className = 'mt-2 generado-msg text-sm font-medium text-red-600 dark:text-red-400';
                                msgEl.textContent = data3B.error || 'Error al crear';
                                let wrapSinPrecioErr = div.querySelector('.generar-sin-precio-wrap');
                                if (!wrapSinPrecioErr) {
                                    wrapSinPrecioErr = document.createElement('div');
                                    wrapSinPrecioErr.className = 'generar-sin-precio-wrap mt-2 flex flex-wrap items-center gap-3';
                                    wrapSinPrecioErr.innerHTML = '<label class="inline-flex items-center gap-2 cursor-pointer text-sm text-amber-700 dark:text-amber-300"><input type="checkbox" class="generar-sin-precio-cb rounded border-gray-300 text-amber-600 focus:ring-amber-500"> <span>Generar con precio 0, no mostrar y con aviso de sin stock a 4 días</span></label>';
                                    msgEl.after(wrapSinPrecioErr);
                                }
                                wrapSinPrecioErr.classList.remove('hidden');
                                btn.disabled = false;
                                btn.innerHTML = '<svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg> Generar oferta';
                            }
                        } else {
                            msgEl.className = 'mt-2 generado-msg text-sm font-medium text-red-600 dark:text-red-400';
                            msgEl.textContent = data2.error || 'Error al crear';
                            let wrapSinPrecioErr = div.querySelector('.generar-sin-precio-wrap');
                            if (!wrapSinPrecioErr) {
                                wrapSinPrecioErr = document.createElement('div');
                                wrapSinPrecioErr.className = 'generar-sin-precio-wrap mt-2 flex flex-wrap items-center gap-3';
                                wrapSinPrecioErr.innerHTML = '<label class="inline-flex items-center gap-2 cursor-pointer text-sm text-amber-700 dark:text-amber-300"><input type="checkbox" class="generar-sin-precio-cb rounded border-gray-300 text-amber-600 focus:ring-amber-500"> <span>Generar con precio 0, no mostrar y con aviso de sin stock a 4 días</span></label>';
                                msgEl.after(wrapSinPrecioErr);
                            }
                            wrapSinPrecioErr.classList.remove('hidden');
                            btn.disabled = false;
                            btn.innerHTML = '<svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg> Generar oferta';
                        }
                    }
                } catch (err) {
                    div.querySelector('.generado-msg').classList.remove('hidden');
                    div.querySelector('.generado-msg').className = 'mt-2 generado-msg text-sm font-medium text-red-600 dark:text-red-400';
                    div.querySelector('.generado-msg').textContent = 'Error: ' + err.message;
                    btn.disabled = false;
                    btn.innerHTML = '<svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg> Generar oferta';
                } finally {
                    marcarFinGeneracionOfertaCrearMasivo();
                }
            });
        } catch (err) {
            alert('Error al cargar: ' + err.message);
        }
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
                const grupoLabelEsc = String(f.texto || f.id || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/"/g, '&quot;');
                h += '<div class="spec-line border-l-2 border-gray-300 dark:border-gray-600 pl-3" data-principal-id="' + f.id + '" data-es-columna="' + (esColumna ? '1' : '0') + '">';
                h += '<label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">' + (f.texto || f.id) + (esColumna ? ' <span class="text-orange-600 dark:text-orange-400 text-xs">(Columna)</span>' : '') + '</label><div class="flex flex-wrap gap-2 items-center">';
                h += '<button type="button" class="btn-toggle-nueva-opcion-cm inline-flex items-center p-1 shrink-0 rounded bg-indigo-600 hover:bg-indigo-700 text-white text-xs transition focus:outline-none focus:ring-2 focus:ring-indigo-400" data-principal-id="' + f.id + '" data-insert-first="1" title="Añadir opción en primera posición"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg></button>';
                subprincipales.forEach((sub) => {
                    let imagenes = Array.isArray(sub.imagenes) ? sub.imagenes : [];
                    if (sub.usar_imagenes_producto && imagenesProducto.length) imagenes = imagenesProducto;
                    const keyImg = String(f.id) + '::' + String(sub.id);
                    if (imagenes.length) window.__crearMasivoImagenesSublinea[keyImg] = imagenes;
                    const btnImgs = imagenes.length ? '<button type="button" class="btn-ver-imagenes-spec inline-flex items-center p-1 bg-blue-600 hover:bg-blue-700 text-white rounded text-xs ml-0.5" data-key="' + keyImg + '" title="Ver ' + imagenes.length + ' imagen' + (imagenes.length !== 1 ? 'es' : '') + '"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg></button>' : '';
                    const subIdEsc = String(sub.id || '').replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;');
                    h += '<label class="inline-flex items-center gap-1 cursor-pointer"><input type="checkbox" class="spec-checkbox rounded border-gray-300 text-green-600 focus:ring-green-500" data-principal-id="' + f.id + '" data-sublinea-id="' + sub.id + '" data-es-columna="' + (esColumna ? '1' : '0') + '"><span class="spec-option-text text-sm text-gray-600 dark:text-gray-400" data-principal-id="' + f.id + '" data-sublinea-id="' + sub.id + '">' + (sub.texto || sub.id) + '</span><span class="spec-count-badge text-xs text-gray-500 dark:text-gray-400" data-principal-id="' + f.id + '" data-sublinea-id="' + sub.id + '">(x)</span>' + btnImgs + '</label>';
                    h += '<button type="button" class="btn-toggle-nueva-opcion-cm inline-flex items-center p-1 shrink-0 rounded bg-indigo-600 hover:bg-indigo-700 text-white text-xs transition focus:outline-none focus:ring-2 focus:ring-indigo-400 ml-0.5" data-principal-id="' + f.id + '" data-after-sub-id="' + subIdEsc + '" title="Añadir opción después de esta"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg></button>';
                });
                h += '</div>';
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
            btn.innerHTML = originalHtml;
        }
    }

    async function aplicarProductoSeleccionado(div, candidato) {
        const r = div.__rowData;
        if (!r || !r.tienda) return;
        try {
            const url = '{{ route("admin.ofertas.crear-masivo.recargar-especificaciones", ["producto" => "__ID__"]) }}'.replace('__ID__', candidato.id);
            const res = await fetch(url, { headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content } });
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
                    return '<div>' + prodLink + ' – ' + (o.tienda || '') + verBtn + editarBtn + '</div>';
                }).join('') + '</div>' : '';
                specParent.innerHTML = especsHtml + ofertasHtml;
            }
            div.dataset.esUnidadUnica = (especs && especs.unidad_de_medida === 'unidadUnica') ? '1' : '0';
            div.dataset.columnasIds = (especs && especs.columnas_ids) ? JSON.stringify(especs.columnas_ids) : '[]';
            div.__rowData = Object.assign({}, r, { producto: productoCompleto, especificaciones: especs, tiene_especificaciones: tieneEspecs });
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
            renderUrlResaltadaFilaCrearMasivo(div);
            actualizarConteosOpcionesEspecsFila(div);
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
                htmlPeticion += '<div class="p-3 rounded-lg border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-800"><div><span class="font-medium">ID ' + (c.id || '') + '</span> – ' + (c.nombre || '').replace(/</g, '&lt;').replace(/>/g, '&gt;') + '</div><div class="text-xs text-gray-500 dark:text-gray-400">' + (c.marca || '') + ' · ' + (c.modelo || '') + (c.talla ? ' · ' + c.talla : '') + '</div>' + especs + '</div>';
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
        imagenesAmazonSeleccionadasSublineaCm = [];
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

    function cambiarTabModalSublineaCm(tab, opts) {
        opts = opts || {};
        const tabs = ['tab-url-sublinea-cm', 'tab-subir-sublinea-cm', 'tab-amazon-sublinea-cm', 'tab-interna-sublinea-cm'];
        const contents = ['content-url-sublinea-cm', 'content-subir-sublinea-cm', 'content-amazon-sublinea-cm', 'content-interna-sublinea-cm'];
        tabs.forEach(function(id) {
            const t = document.getElementById(id);
            if (t) {
                t.classList.remove('border-blue-500', 'text-blue-600', 'dark:text-blue-400');
                t.classList.add('border-transparent', 'text-gray-500', 'dark:text-gray-400');
            }
        });
        contents.forEach(function(id) {
            const c = document.getElementById(id);
            if (c) c.classList.add('hidden');
        });
        if (tab === 'url') {
            const t = document.getElementById('tab-url-sublinea-cm');
            const c = document.getElementById('content-url-sublinea-cm');
            if (t) { t.classList.add('border-blue-500', 'text-blue-600', 'dark:text-blue-400'); t.classList.remove('border-transparent', 'text-gray-500', 'dark:text-gray-400'); }
            if (c) c.classList.remove('hidden');
        } else if (tab === 'subir') {
            const t = document.getElementById('tab-subir-sublinea-cm');
            const c = document.getElementById('content-subir-sublinea-cm');
            if (t) { t.classList.add('border-blue-500', 'text-blue-600', 'dark:text-blue-400'); t.classList.remove('border-transparent', 'text-gray-500', 'dark:text-gray-400'); }
            if (c) c.classList.remove('hidden');
        } else if (tab === 'amazon') {
            const t = document.getElementById('tab-amazon-sublinea-cm');
            const c = document.getElementById('content-amazon-sublinea-cm');
            if (t) { t.classList.add('border-blue-500', 'text-blue-600', 'dark:text-blue-400'); t.classList.remove('border-transparent', 'text-gray-500', 'dark:text-gray-400'); }
            if (c) c.classList.remove('hidden');
            if (!opts.skipCargarCarpetas) cargarCarpetasModalSublineaCm();
        } else if (tab === 'interna') {
            const t = document.getElementById('tab-interna-sublinea-cm');
            const c = document.getElementById('content-interna-sublinea-cm');
            if (t) { t.classList.add('border-blue-500', 'text-blue-600', 'dark:text-blue-400'); t.classList.remove('border-transparent', 'text-gray-500', 'dark:text-gray-400'); }
            if (c) c.classList.remove('hidden');
            cmEnlazarPreviewsInternasCm();
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
                div.innerHTML = '<div class="w-full h-20 flex flex-col items-center justify-center bg-gray-100 dark:bg-gray-700 rounded p-1"><span class="text-[10px] text-gray-600 dark:text-gray-300 text-center leading-tight">Cargando imagen…</span><div class="w-full mt-1 h-1.5 bg-gray-200 dark:bg-gray-600 rounded overflow-hidden"><div id="kp-prog-cm-' + uidCm + '" class="h-full bg-blue-500 transition-[width] duration-150" style="width:0%"></div></div></div><button type="button" class="absolute top-0 right-0 bg-red-600 text-white rounded-full w-5 h-5 text-xs btn-eliminar-imagen-cm" data-index="' + index + '">×</button>';
            } else {
                div.innerHTML = '<img src="' + url + '" alt="" class="w-full h-20 object-cover rounded"><button type="button" class="absolute top-0 right-0 bg-red-600 text-white rounded-full w-5 h-5 text-xs btn-eliminar-imagen-cm" data-index="' + index + '">×</button>';
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
            // Fase captura: el botón «Elegir» usa @click.stop (Alpine) y el evento no burbujea hasta el backdrop.
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
                const esAmazon = tabAmazon && tabAmazon.classList.contains('border-blue-500');
                if (esAmazon) {
                    if (imagenesAmazonSeleccionadasSublineaCm.length === 0) {
                        alert('Selecciona al menos una imagen.');
                        return;
                    }
                    const carpeta = document.getElementById('carpeta-amazon-sublinea-cm').value;
                    if (!carpeta) { alert('Selecciona una carpeta.'); return; }
                    const panelAmz = window.__cmNuevaOpcionPanelDraft;
                    if (!panelAmz) {
                        alert('No hay panel de borrador activo.');
                        return;
                    }
                    const ctx = resolverContextoProductoCm(panelAmz);
                    const nombreBase = nombreBaseArchivoCm(ctx.productoId);
                    const token = document.querySelector('meta[name="csrf-token"]').content;
                    const rutaSubir = @json(route('admin.imagenes.subir-simple'));
                    const seleccionAmzCm = imagenesAmazonSeleccionadasSublineaCm.slice();
                    const trabajosAmz = seleccionAmzCm.map(function(imagen, i) {
                        const uidAmz = cmNuevoIdSubida() + 'z' + i;
                        return {
                            imagen: imagen,
                            ts: Date.now() + i,
                            uploadId: uidAmz,
                            pendingPath: KP_PENDING_CM + uidAmz
                        };
                    });
                    trabajosAmz.forEach(function(t) {
                        if (!Array.isArray(panelAmz.__cmDraftImagenes)) panelAmz.__cmDraftImagenes = [];
                        panelAmz.__cmDraftImagenes.push(t.pendingPath);
                    });
                    actualizarBotonesImagenDraftCm(panelAmz);
                    const modalVerAmz = document.getElementById('modal-imagenes-sublinea-cm');
                    if (modalVerAmz && !modalVerAmz.classList.contains('hidden')) renderizarMiniaturasSublineaCm();

                    trabajosAmz.forEach(function(t) {
                        const urlProxy = t.imagen.url.startsWith('http')
                            ? (@json(route('admin.imagenes.proxy'))) + '?url=' + encodeURIComponent(t.imagen.url)
                            : t.imagen.url;
                        (async function() {
                            try {
                                const img = await new Promise(function(resolve, reject) {
                                    const im = new Image();
                                    im.crossOrigin = 'anonymous';
                                    im.onload = function() { resolve(im); };
                                    im.onerror = function() { reject(new Error('Carga imagen')); };
                                    im.src = urlProxy;
                                });
                                const cG = document.createElement('canvas');
                                cG.width = img.width; cG.height = img.height;
                                cG.getContext('2d').drawImage(img, 0, 0);
                                const cP = document.createElement('canvas');
                                cP.width = 300; cP.height = 250;
                                cP.getContext('2d').drawImage(img, 0, 0, 300, 250);
                                const bG = await new Promise(function(res, rej) { cG.toBlob(function(b) { b ? res(b) : rej(new Error('b')); }, 'image/webp', 0.9); });
                                const bP = await new Promise(function(res, rej) { cP.toBlob(function(b) { b ? res(b) : rej(new Error('b')); }, 'image/webp', 0.9); });
                                const fdG = new FormData();
                                fdG.append('imagen', bG, nombreBase + '-' + t.ts + '.webp');
                                fdG.append('carpeta', carpeta);
                                fdG.append('_token', token);
                                const fdP = new FormData();
                                fdP.append('imagen', bP, nombreBase + '-' + t.ts + '-thumbnail.webp');
                                fdP.append('carpeta', carpeta);
                                fdP.append('_token', token);
                                const onProg = function(pct) {
                                    const el = document.getElementById('kp-prog-cm-' + t.uploadId);
                                    if (el) el.style.width = pct + '%';
                                };
                                const { dataG, dataP } = await window.__kpSubirParejaConProgreso(rutaSubir, fdG, fdP, token, onProg, uploadsPendientesCm, t.uploadId);
                                if (dataG.success && dataP.success && dataG.data && dataG.data.ruta_relativa) {
                                    const ix = panelAmz.__cmDraftImagenes.indexOf(t.pendingPath);
                                    if (ix !== -1) panelAmz.__cmDraftImagenes[ix] = dataG.data.ruta_relativa;
                                    renderizarMiniaturasSublineaCm();
                                    actualizarBotonesImagenDraftCm(panelAmz);
                                } else {
                                    throw new Error(dataG.message || dataP.message || 'Error al subir');
                                }
                            } catch (ex) {
                                console.error(ex);
                                cmQuitarPendienteDelDraft(panelAmz, t.pendingPath);
                                alert('Error al subir una imagen de Amazon: ' + (ex && ex.message ? ex.message : ex));
                            }
                        })();
                    });

                    prepararModalAnadirImagenParaOtraCm('amazon');
                    cargarCarpetasModalSublineaCm();
                    return;
                }
                const tabInterna = document.getElementById('tab-interna-sublinea-cm');
                const esInterna = tabInterna && tabInterna.classList.contains('border-blue-500');
                if (esInterna) {
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
                const esSubir = tabSubir && tabSubir.classList.contains('border-blue-500');
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
