<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.dashboard') }}">
                <h2 class="font-semibold text-xl text-white leading-tight">Inicio -></h2>
            </a>
            <a href="{{ route('admin.neo.index') }}">
                <h2 class="font-semibold text-xl text-white leading-tight">Neo -></h2>
            </a>
            <h2 class="font-semibold text-xl text-white leading-tight">Crear ofertas en masa IA</h2>
            <style>[x-cloak]{ display:none !important; }</style>
        </div>
    </x-slot>

    <div class="max-w-6xl mx-auto py-10 px-4 space-y-8">
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6 border border-gray-200 dark:border-gray-700">
            <p class="text-gray-600 dark:text-gray-400 mb-4">
                <strong>Filas en neo con añadida = no:</strong> <span>{{ $totalNeoAniadidaNo ?? 0 }}</span>
                <span class="mx-2">|</span>
                <strong>Sin URL:</strong> <span>{{ $totalNeoAniadidaNoSinUrl ?? 0 }}</span>
            </p>

            <div class="mb-4 flex flex-wrap items-center gap-2">
                <button type="button" id="btnProductoNeoIa" class="inline-flex items-center bg-blue-500 hover:bg-blue-600 text-white font-semibold px-4 py-2.5 rounded shadow transition disabled:opacity-50">
                    Producto ({{ $totalProductosNeoAniadidaNo ?? 0 }})
                </button>
                <button type="button" id="btnCategoriaNeoIa" class="inline-flex items-center bg-pink-500 hover:bg-pink-600 text-white font-semibold px-4 py-2.5 rounded shadow transition disabled:opacity-50">
                    Categoría ({{ $totalCategoriasNeoAniadidaNo ?? 0 }})
                </button>
                <button type="button" id="btnTiendaNeoIa" class="inline-flex items-center bg-emerald-500 hover:bg-emerald-600 text-white font-semibold px-4 py-2.5 rounded shadow transition disabled:opacity-50">
                    Tienda ({{ $totalTiendasNeoAniadidaNo ?? 0 }})
                </button>
                <span class="text-sm text-gray-500 dark:text-gray-400">Elige el origen, revisa las URLs y comienza el proceso una a una.</span>
            </div>

            <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">Crear ofertas en masa con IA</h1>
            <p class="text-gray-600 dark:text-gray-400 mb-6">
                La IA revisa cada URL, confirma el producto y propone una especificación por grupo. Antes de guardar, verás un modal editable.
            </p>

            <div class="space-y-4">
                <div>
                    <label class="block mb-2 font-medium text-gray-700 dark:text-gray-200">URLs cargadas</label>
                    <textarea id="urlsIa" rows="9"
                        class="w-full px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white border border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500 font-mono text-sm"
                        placeholder="Carga URLs desde Producto, Categoría o Tienda, o pega una URL por línea."></textarea>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <button type="button" id="btnComenzarIa"
                        class="inline-flex items-center bg-green-600 hover:bg-green-700 text-white font-semibold px-6 py-3 rounded-md transition disabled:opacity-50 disabled:cursor-not-allowed">
                        Comenzar
                    </button>
                    <label class="inline-flex items-center gap-2 cursor-pointer text-sm text-gray-700 dark:text-gray-300">
                        <input type="checkbox" id="usarIaCrearMasivo" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <span>Usar IA</span>
                    </label>
                    <label for="chatgptModelIa" class="text-sm text-gray-700 dark:text-gray-300">Modelo:</label>
                    <select id="chatgptModelIa" class="rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm px-3 py-2 focus:ring-2 focus:ring-blue-500">
                        <option value="gpt-4o-nano">gpt-4o-nano</option>
                        <option value="gpt-4o-mini">gpt-4o-mini</option>
                        <option value="gpt-4o" selected>gpt-4o</option>
                        <option value="gpt-4-turbo">gpt-4-turbo</option>
                    </select>
                    <span id="estadoColaIa" class="text-sm text-gray-600 dark:text-gray-400">0 URLs cargadas</span>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between gap-3 mb-3">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Terminal</h2>
                <button type="button" id="btnLimpiarTerminalIa" class="px-3 py-1.5 rounded bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-sm text-gray-700 dark:text-gray-200">Limpiar</button>
            </div>
            <div id="terminalIa" class="bg-black text-green-200 rounded-lg p-4 min-h-[320px] max-h-[520px] overflow-y-auto font-mono text-sm whitespace-pre-wrap"></div>
        </div>
    </div>

    <div id="modal-listado-ia" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-2xl w-full max-h-[80vh] flex flex-col border border-gray-200 dark:border-gray-700">
            <div class="p-4 border-b border-gray-200 dark:border-gray-700 space-y-3">
                <div class="flex justify-between items-center gap-3">
                    <h3 id="modal-listado-ia-titulo" class="text-lg font-semibold text-gray-900 dark:text-white">Elegir</h3>
                    <button type="button" id="modal-listado-ia-cerrar" class="text-2xl leading-none text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">&times;</button>
                </div>
                <div id="modal-listado-ia-filtros-producto" class="hidden space-y-2 text-sm text-gray-700 dark:text-gray-300">
                    <div class="flex flex-wrap items-center gap-x-4 gap-y-2">
                        <span>Total URLs disponibles: <strong id="modal-listado-ia-total-urls" class="text-gray-900 dark:text-white">—</strong></span>
                        <span class="hidden sm:inline text-gray-400 dark:text-gray-500">|</span>
                        <span class="inline-flex flex-wrap items-center gap-3">
                            <span class="font-medium text-gray-800 dark:text-gray-200">Tiendas con mostrar</span>
                            <label class="inline-flex items-center gap-1.5 cursor-pointer">
                                <input type="checkbox" id="modal-listado-ia-chk-mostrar-si" checked class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <span>Sí</span>
                            </label>
                            <label class="inline-flex items-center gap-1.5 cursor-pointer">
                                <input type="checkbox" id="modal-listado-ia-chk-mostrar-no" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <span>No</span>
                            </label>
                            <label class="inline-flex items-center gap-1.5 cursor-pointer" title="Solo filas neo sin tienda (desmarca Sí y No). Con Sí o No marcados, también suma enlaces sin tienda.">
                                <input type="checkbox" id="modal-listado-ia-chk-tienda-null" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <span>null (<span id="modal-listado-ia-null-count">0</span>)</span>
                            </label>
                            <button type="button" id="modal-listado-ia-btn-buscar-tienda" class="px-2 py-0.5 text-xs font-medium rounded border border-blue-600 text-blue-700 bg-blue-50 hover:bg-blue-100 dark:border-blue-500 dark:text-blue-300 dark:bg-blue-900/30 dark:hover:bg-blue-900/50 disabled:opacity-50" title="Rellenar tienda por host de la URL y categoría desde el producto si falta">Rellenar</button>
                        </span>
                    </div>
                    <details id="modal-listado-ia-details-tiendas-no" class="hidden w-full border border-gray-200 dark:border-gray-600 rounded-lg open:shadow-sm">
                        <summary class="cursor-pointer select-none px-3 py-2 font-medium text-gray-800 dark:text-gray-200 bg-gray-50 dark:bg-gray-700/50 rounded-lg list-none [&::-webkit-details-marker]:hidden flex items-center gap-2">
                            <span class="text-gray-400 dark:text-gray-500 text-xs" aria-hidden="true">▸</span>
                            <span>Elegir tiendas (mostrar «no»)</span>
                        </summary>
                        <div id="modal-listado-ia-tiendas-no-lista" class="p-3 max-h-48 overflow-y-auto space-y-1.5 border-t border-gray-200 dark:border-gray-600"></div>
                    </details>
                </div>
            </div>
            <div id="modal-listado-ia-lista" class="flex-1 overflow-y-auto p-4 space-y-2">
                <p class="text-gray-500 dark:text-gray-400 text-sm">Cargando...</p>
            </div>
            <div class="p-4 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <button type="button" id="modal-listado-ia-atras" class="hidden px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">Atrás</button>
                <button type="button" id="modal-listado-ia-cerrar-btn" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">Cerrar</button>
            </div>
        </div>
    </div>

    <div id="modal-revision-ia" class="hidden fixed inset-0 bg-black bg-opacity-50 z-[60] flex items-center justify-center p-4">
        <div class="bg-gray-50 dark:bg-gray-800 rounded-xl shadow-xl max-w-5xl w-full max-h-[92vh] flex flex-col border border-gray-200 dark:border-gray-700">
            <div id="modal-revision-ia-guardados-bg" class="hidden m-4 mb-0 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 p-3 space-y-2"></div>
            <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-start gap-4 bg-white dark:bg-gray-800 rounded-t-xl">
                <div class="min-w-0">
                    <h3 id="modal-revision-ia-titulo" class="text-lg font-semibold text-gray-900 dark:text-white">Revisar URL</h3>
                    <a id="modal-revision-ia-url" href="#" target="_blank" class="mt-1 block text-sm text-blue-600 dark:text-blue-400 hover:underline break-all"></a>
                </div>
                <div class="flex flex-wrap justify-end gap-2">
                    <button type="button" id="modal-revision-ia-descartar-top" class="text-sm px-3 py-1.5 rounded bg-red-600 hover:bg-red-700 text-white">Descartar</button>
                    <button type="button" id="modal-revision-ia-saltar-top" class="text-sm px-3 py-1.5 rounded bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200">Saltar</button>
                    <button type="button" id="modal-revision-ia-finalizar" class="text-2xl leading-none px-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200" title="Cerrar y finalizar">&times;</button>
                </div>
            </div>
            <div class="flex-1 overflow-y-auto p-4 space-y-4 bg-gray-50 dark:bg-gray-800">
                <div id="modal-revision-ia-alerta" class="hidden rounded border px-3 py-2 text-sm"></div>
                <div id="modal-revision-ia-producto" class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-3 text-sm text-gray-700 dark:text-gray-300"></div>
                <div id="modal-revision-ia-buscador" class="hidden border border-amber-200 dark:border-amber-700 bg-amber-50 dark:bg-amber-900/20 rounded-lg p-4 space-y-3">
                    <div class="flex flex-wrap items-center justify-end gap-3">
                        <a href="{{ route('admin.productos.create') }}" target="_blank" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded transition">Nuevo producto</a>
                    </div>
                    <div class="producto-search-container relative">
                        <div id="modal-revision-ia-palabras" class="mb-2 flex flex-wrap gap-1.5"></div>
                        <input type="text" id="modal-revision-ia-search" class="w-full px-3 py-2 rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 text-sm" placeholder="Buscar producto manualmente..." autocomplete="off">
                        <div id="modal-revision-ia-sugerencias" class="absolute z-50 left-0 right-0 mt-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md shadow-lg hidden max-h-60 overflow-y-auto"></div>
                    </div>
                </div>
                <div id="modal-revision-ia-specs" class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4" data-columnas-ids="[]" data-es-unidad-unica="0"></div>
                <p id="modal-revision-ia-msg" class="hidden text-sm font-medium"></p>
            </div>
            <div class="p-4 border-t border-gray-200 dark:border-gray-700 flex flex-wrap items-center justify-end gap-3 bg-white dark:bg-gray-800 rounded-b-xl">
                <label class="inline-flex items-center gap-2 text-xs text-gray-600 dark:text-gray-300">
                    <span>Envío (€)</span>
                    <input type="text" id="modal-revision-ia-envio" class="w-24 px-2 py-1.5 rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm" autocomplete="off" inputmode="decimal">
                </label>
                <button type="button" id="modal-revision-ia-guardar" class="inline-flex items-center justify-center min-w-[160px] px-8 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded transition disabled:opacity-50 disabled:cursor-not-allowed">Guardar</button>
            </div>
        </div>
    </div>

    <div id="modal-descartar-url-ia" class="hidden fixed inset-0 bg-black bg-opacity-50 z-[80] flex items-center justify-center p-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-md w-full border border-gray-200 dark:border-gray-700">
            <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Confirmar descarte</h3>
                <button type="button" id="modal-descartar-url-ia-cerrar" class="text-2xl leading-none text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">&times;</button>
            </div>
            <div class="p-4">
                <p class="text-sm text-gray-700 dark:text-gray-300">¿Seguro que quieres descartar esta URL?</p>
                <p id="modal-descartar-url-ia-texto" class="mt-2 text-xs break-all text-gray-500 dark:text-gray-400"></p>
                <p id="modal-descartar-url-ia-error" class="hidden mt-2 text-sm font-medium text-red-600 dark:text-red-400"></p>
            </div>
            <div class="p-4 border-t border-gray-200 dark:border-gray-700 flex justify-end gap-2">
                <button type="button" id="modal-descartar-url-ia-cancelar" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">Cancelar</button>
                <button type="button" id="modal-descartar-url-ia-confirmar" class="inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded transition">Aceptar y descartar</button>
            </div>
        </div>
    </div>

    <div id="modal-imagenes-spec-ia" class="hidden fixed inset-0 bg-black bg-opacity-50 z-[70] flex items-center justify-center p-4">
        <div class="bg-white dark:bg-gray-900 rounded-lg p-6 max-w-5xl w-full relative shadow-xl max-h-[90vh] flex flex-col">
            <button type="button" onclick="cerrarModalImagenesSpecIa()" class="absolute top-3 right-4 text-xl text-gray-800 dark:text-gray-100 hover:text-gray-600 dark:hover:text-gray-300 z-10">&times;</button>
            <div class="mb-4">
                <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-200">Imágenes de la especificación</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">Haz clic en una miniatura para verla en grande</p>
            </div>
            <div class="flex-1 overflow-y-auto">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="md:col-span-2">
                        <div class="bg-gray-100 dark:bg-gray-800 rounded-lg p-4 flex items-center justify-center" style="min-height: 400px;">
                            <img id="imagen-grande-spec-ia" src="" alt="" class="max-w-full max-h-96 object-contain rounded">
                        </div>
                    </div>
                    <div class="md:col-span-1">
                        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-3">Miniaturas</h4>
                        <div id="miniaturas-container-spec-ia" class="space-y-2 max-h-96 overflow-y-auto">
                            <p class="text-sm text-gray-500 dark:text-gray-400">No hay imágenes</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="flex justify-end gap-3 mt-4">
                <button type="button" onclick="cerrarModalImagenesSpecIa()"
                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">
                    Cerrar
                </button>
            </div>
        </div>
    </div>

    <script>
    const rutasIa = {
        neoProductos: @json(route('admin.neo.crear-masivo.productos')),
        neoRellenarTiendaId: @json(route('admin.neo.crear-masivo.rellenar-tienda-id')),
        neoTiendasMostrarNo: @json(route('admin.neo.crear-masivo.tiendas-mostrar-no')),
        neoUrlsProducto: @json(route('admin.neo.crear-masivo.urls-por-producto', ['productoId' => '__ID__'])),
        neoCategorias: @json(route('admin.neo.crear-masivo.categorias')),
        neoUrlsCategoria: @json(route('admin.neo.crear-masivo.urls-por-categoria', ['categoriaId' => '__ID__'])),
        neoTiendasCategoria: @json(route('admin.neo.crear-masivo.tiendas-por-categoria', ['categoriaId' => '__ID__'])),
        neoUrlsCategoriaTienda: @json(route('admin.neo.crear-masivo.urls-por-categoria-tienda', ['categoriaId' => '__CID__', 'tiendaId' => '__TID__'])),
        neoTiendas: @json(route('admin.neo.crear-masivo.tiendas')),
        neoUrlsTienda: @json(route('admin.neo.crear-masivo.urls-por-tienda', ['tiendaId' => '__ID__'])),
        neoCategoriasTienda: @json(route('admin.neo.crear-masivo.categorias-por-tienda', ['tiendaId' => '__ID__'])),
        neoUrlsTiendaCategoria: @json(route('admin.neo.crear-masivo.urls-por-tienda-categoria', ['tiendaId' => '__TID__', 'categoriaId' => '__CID__'])),
        analizar: @json(route('admin.ofertas.crear-masivo.analizar')),
        crear: @json(route('admin.ofertas.crear-masivo.crear')),
        buscarProductos: @json(route('admin.ofertas.buscar.productos')),
        recargarEspecificaciones: @json(route('admin.ofertas.crear-masivo.recargar-especificaciones', ['producto' => '__ID__'])),
        contarOpcionesEspecificaciones: @json(route('admin.ofertas.crear-masivo.contar-opciones-especificaciones')),
        anadirOpcionEspecificacion: @json(route('admin.ofertas.crear-masivo.anadir-opcion-especificacion')),
        subirImagenSimple: @json(route('admin.imagenes.subir-simple')),
        carpetasImagenes: @json(route('admin.imagenes.carpetas')),
        palabrasClave: @json(route('admin.neo.crear-masivo-ia.palabras-clave')),
        elegirProducto: @json(route('admin.neo.crear-masivo-ia.elegir-producto')),
        completarEspecificaciones: @json(route('admin.neo.crear-masivo-ia.completar-especificaciones')),
        descartarUrl: @json(route('admin.neo.crear-masivo.descartar-url')),
    };
    const NEO_PANEL_PRODUCTOS_BASE_IA = @json(rtrim(url('/panel-privado/productos'), '/'));

    window.__crearMasivoIaImagenesSublinea = {};
    window.__crearMasivoIaImagenesActual = { key: null, imagenes: [] };

    const estadoIa = {
        urls: [],
        index: 0,
        siguienteAnalisisIndex: 0,
        ejecutando: false,
        usarIa: true,
        analizando: false,
        modalAbierto: false,
        rowActual: null,
        rowActualIndex: null,
        productosBusqueda: [],
        resultadosPendientes: [],
        guardadosPendientes: 0,
        guardadoSegundoPlanoActual: null,
        guardadoSecuencia: 0,
        urlsAbiertasChatgpt: new Set(),
    };

    function csrfIa() {
        return document.querySelector('meta[name="csrf-token"]').content;
    }

    function escaparIa(texto) {
        return String(texto || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function resolverUrlImagenCrearMasivoIa(imgPath) {
        const publicBase = @json(url('/'));
        const imagesBase = @json(asset('images/'));
        if (!imgPath) return '';
        let p = String(imgPath).trim();
        if (!p) return '';
        if (/^https?:\/\//i.test(p)) return p;
        if (/^\/\//.test(p)) return p;
        p = p.replace(/^\/+/, '');
        if (/^images\//i.test(p)) return publicBase.replace(/\/+$/, '') + '/' + p;
        return imagesBase.replace(/\/+$/, '') + '/' + p;
    }

    function logIa(texto, tipo) {
        const terminal = document.getElementById('terminalIa');
        const now = new Date();
        const hora = now.toLocaleTimeString();
        const prefijo = tipo === 'error' ? '[ERROR]' : (tipo === 'ok' ? '[OK]' : '[INFO]');
        terminal.textContent += '[' + hora + '] ' + prefijo + ' ' + texto + "\n";
        terminal.scrollTop = terminal.scrollHeight;
    }

    function urlsTextareaIa() {
        return document.getElementById('urlsIa').value
            .split('\n')
            .map(u => u.trim())
            .filter(Boolean);
    }

    function usarIaActivaCrearMasivo() {
        const cb = document.getElementById('usarIaCrearMasivo');
        return !cb || cb.checked;
    }

    function actualizarOpcionesModeloIa() {
        const usarIa = usarIaProcesoCrearMasivo();
        const modelo = document.getElementById('chatgptModelIa');
        if (modelo) modelo.disabled = !usarIa;
    }

    function usarIaProcesoCrearMasivo() {
        return estadoIa.ejecutando ? estadoIa.usarIa : usarIaActivaCrearMasivo();
    }

    function ponerUrlsIa(urls) {
        const limpias = Array.from(new Set((Array.isArray(urls) ? urls : []).map(u => String(u || '').trim()).filter(Boolean)));
        document.getElementById('urlsIa').value = limpias.join('\n');
        actualizarEstadoColaIa();
        logIa('URLs cargadas: ' + limpias.length, 'ok');
    }

    function actualizarEstadoColaIa() {
        const total = estadoIa.ejecutando ? estadoIa.urls.length : urlsTextareaIa().length;
        const actual = estadoIa.ejecutando
            ? Math.min((estadoIa.rowActualIndex !== null ? estadoIa.rowActualIndex + 1 : estadoIa.siguienteAnalisisIndex), total)
            : 0;
        const sufijo = estadoIa.ejecutando && estadoIa.guardadosPendientes > 0
            ? ' · guardando ' + estadoIa.guardadosPendientes
            : '';
        document.getElementById('estadoColaIa').textContent = estadoIa.ejecutando
            ? 'Procesando ' + actual + ' / ' + total + sufijo
            : total + ' URLs cargadas';
    }

    function hayModalVisibleIa() {
        return [
            'modal-listado-ia',
            'modal-revision-ia',
            'modal-descartar-url-ia',
            'modal-imagenes-spec-ia',
        ].some(id => {
            const modal = document.getElementById(id);
            return modal && !modal.classList.contains('hidden');
        });
    }

    function abrirUrlPrimeraPeticionChatgptIa(url, forzar) {
        const limpia = String(url || '').trim();
        if (!limpia || estadoIa.urlsAbiertasChatgpt.has(limpia)) {
            return;
        }
        if (!forzar && hayModalVisibleIa()) {
            return;
        }

        estadoIa.urlsAbiertasChatgpt.add(limpia);
        const urlAbrir = /^https?:\/\//i.test(limpia) ? limpia : 'https://' + limpia;
        const ventana = window.open(urlAbrir, '_blank', 'noopener');
        if (!ventana) {
            logIa('El navegador ha bloqueado la ventana nueva para: ' + limpia, 'error');
        }
    }

    function extraerPalabrasSlugDesdeUrlIa(url) {
        const rawUrl = String(url || '').trim();
        if (!rawUrl) return [];
        let path = rawUrl;
        try {
            path = new URL(rawUrl).pathname || '';
        } catch (e) {
            path = rawUrl.replace(/^https?:\/\/[^/]+/i, '');
        }
        const segs = String(path || '').replace(/\/+$/, '').split('/').filter(Boolean);
        if (!segs.length) return [];
        let slug = segs[segs.length - 1];
        if (segs.length >= 2 && /^(p|html?)$/i.test(slug)) {
            slug = segs[segs.length - 2];
        }
        if (!slug) return [];
        const partes = [];
        slug.split('|').forEach(bloque => {
            bloque.split('-').forEach(p => {
                const t = String(p || '').trim();
                if (t) partes.push(t);
            });
        });
        return partes;
    }

    function normalizarTokenUrlHighlightIa(token) {
        const base = String(token || '').toLowerCase().trim().replace(/[\s\-_\/]+/g, '');
        if (!base) return '';
        const m = base.match(/^(\d+)g$/);
        if (m) return m[1] + 'gb';
        return base;
    }

    function expandirTokenMixtoUrlHighlightIa(token) {
        const t = normalizarTokenUrlHighlightIa(token);
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

    function tokensDesdeTextoUrlHighlightIa(texto) {
        const parts = String(texto || '').split(/[\s\-_\/]+/).map(p => p.trim()).filter(Boolean);
        const out = [];
        parts.forEach(p => expandirTokenMixtoUrlHighlightIa(p).forEach(x => out.push(x)));
        return Array.from(new Set(out));
    }

    function obtenerSetTokensProductoModalIa(row) {
        const set = new Set();
        const p = row && row.producto ? row.producto : null;
        if (!p) return set;
        const texto = [p.nombre, p.marca, p.modelo, p.talla, p.texto_completo].filter(Boolean).join(' ');
        tokensDesdeTextoUrlHighlightIa(texto).forEach(t => set.add(t));
        return set;
    }

    function obtenerSetTokensSpecsMarcadasModalIa() {
        const set = new Set();
        document.querySelectorAll('#modal-revision-ia-specs .spec-checkbox-ia:checked').forEach(cb => {
            const label = cb.closest('label');
            const span = label ? label.querySelector('.spec-option-text-ia') : null;
            const txt = span ? span.textContent : '';
            tokensDesdeTextoUrlHighlightIa(txt).forEach(t => set.add(t));
        });
        return set;
    }

    function resaltarTextoPorTokensIa(texto, tokensProducto, tokensSpecs) {
        return String(texto || '').split(/(\s+|[-_/]+)/).map(part => {
            if (!part || /^(\s+|[-_/]+)$/.test(part)) return escaparIa(part);
            const expanded = expandirTokenMixtoUrlHighlightIa(part);
            const hitSpec = expanded.some(t => tokensSpecs.has(t));
            const hitProd = expanded.some(t => tokensProducto.has(t));
            const safe = escaparIa(part);
            if (hitSpec) {
                return '<span class="bg-orange-300 dark:bg-orange-600/80 text-orange-950 dark:text-orange-50 px-0.5 rounded border border-orange-500/70 font-semibold">' + safe + '</span>';
            }
            if (hitProd) {
                return '<span class="bg-yellow-200 dark:bg-yellow-700/60 text-yellow-900 dark:text-yellow-100 px-0.5 rounded">' + safe + '</span>';
            }
            return safe;
        }).join('');
    }

    function renderUrlYProductoResaltadosIa() {
        const row = estadoIa.rowActual || {};
        const url = String(row.url_normalizada || row.url || '').trim();
        const urlEl = document.getElementById('modal-revision-ia-url');
        if (!urlEl || !url) return;

        const tokensProducto = obtenerSetTokensProductoModalIa(row);
        const tokensSpecs = obtenerSetTokensSpecsMarcadasModalIa();
        const palabrasSlug = extraerPalabrasSlugDesdeUrlIa(url);
        const tokensUrl = new Set();
        palabrasSlug.forEach(pal => expandirTokenMixtoUrlHighlightIa(pal).forEach(t => tokensUrl.add(t)));
        const highlightedSlug = palabrasSlug.map(pal => {
            const expanded = expandirTokenMixtoUrlHighlightIa(pal);
            const hitSpec = expanded.some(t => tokensSpecs.has(t));
            const hitProd = expanded.some(t => tokensProducto.has(t));
            const safe = escaparIa(pal);
            if (hitSpec) {
                return '<span class="bg-orange-300 dark:bg-orange-600/80 text-orange-950 dark:text-orange-50 px-0.5 rounded border border-orange-500/70 font-semibold">' + safe + '</span>';
            }
            if (hitProd) {
                return '<span class="bg-yellow-200 dark:bg-yellow-700/60 text-yellow-900 dark:text-yellow-100 px-0.5 rounded">' + safe + '</span>';
            }
            return safe;
        }).join('-');

        if (highlightedSlug) {
            const mVt = url.match(/^(.+)\/([^/]+)\/(p)\/?$/i);
            urlEl.innerHTML = mVt && String(mVt[3]).toLowerCase() === 'p'
                ? escaparIa(mVt[1]) + '/' + highlightedSlug + '/' + mVt[3] + (url.endsWith('/') ? '/' : '')
                : escaparIa(url).replace(/([^\/]+)\/?$/, highlightedSlug + (url.endsWith('/') ? '/' : ''));
        } else {
            urlEl.textContent = url;
        }
        urlEl.href = url;

        const prodTexto = document.getElementById('modal-revision-ia-producto-texto');
        if (prodTexto) {
            prodTexto.innerHTML = resaltarTextoPorTokensIa(prodTexto.dataset.texto || prodTexto.textContent || '', tokensUrl, tokensSpecs);
        }
    }

    function abrirModalListadoIa(titulo, opciones) {
        const opts = opciones || {};
        document.getElementById('modal-listado-ia-titulo').textContent = titulo;
        document.getElementById('modal-listado-ia-lista').innerHTML = '<p class="text-gray-500 dark:text-gray-400 text-sm">Cargando...</p>';
        document.getElementById('modal-listado-ia-atras').classList.add('hidden');
        const filtrosProducto = document.getElementById('modal-listado-ia-filtros-producto');
        if (filtrosProducto) {
            filtrosProducto.classList.toggle('hidden', !opts.filtrosProducto);
        }
        const totalEl = document.getElementById('modal-listado-ia-total-urls');
        if (totalEl) totalEl.textContent = opts.filtrosProducto ? '…' : '—';
        document.getElementById('modal-listado-ia').classList.remove('hidden');
    }

    function cerrarModalListadoIa() {
        document.getElementById('modal-listado-ia').classList.add('hidden');
    }

    function renderListadoIa(items, textoVacio, onClick) {
        const lista = document.getElementById('modal-listado-ia-lista');
        lista.innerHTML = '';
        if (!Array.isArray(items) || !items.length) {
            lista.innerHTML = '<p class="text-gray-500 dark:text-gray-400 text-sm">' + escaparIa(textoVacio) + '</p>';
            return;
        }
        items.forEach(item => {
            const div = document.createElement('div');
            div.className = 'p-3 rounded-lg border border-gray-200 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer transition';
            const textoHtml = item.textoHtml || escaparIa(item.texto || item.nombre || item.texto_completo || ('#' + item.id));
            const metaHtml = item.metaHtml || escaparIa(item.meta || '');
            div.innerHTML = '<span class="font-medium text-gray-900 dark:text-white">' + textoHtml + '</span> <span class="text-gray-500 dark:text-gray-400 text-sm">' + metaHtml + '</span>';
            div.addEventListener('click', () => onClick(item));
            lista.appendChild(div);
        });
    }

    function parseRespuestaProductosNeoIa(data) {
        if (Array.isArray(data)) {
            return { productos: data, filas_neo_tienda_id_null: 0 };
        }
        return {
            productos: Array.isArray(data && data.productos) ? data.productos : [],
            filas_neo_tienda_id_null: parseInt(String((data && data.filas_neo_tienda_id_null) || 0), 10) || 0,
        };
    }

    function queryMostrarTiendaModalProductoIa() {
        const chkSi = document.getElementById('modal-listado-ia-chk-mostrar-si');
        const chkNo = document.getElementById('modal-listado-ia-chk-mostrar-no');
        const chkNull = document.getElementById('modal-listado-ia-chk-tienda-null');
        const si = chkSi && chkSi.checked;
        const no = chkNo && chkNo.checked;
        const incluirNull = chkNull && chkNull.checked;
        let q = 'mostrar_si=' + (si ? '1' : '0') + '&mostrar_no=' + (no ? '1' : '0');
        if (incluirNull) q += '&mostrar_null=1';
        return q;
    }

    function esSoloMostrarNoModalProductoIa() {
        const si = document.getElementById('modal-listado-ia-chk-mostrar-si');
        const no = document.getElementById('modal-listado-ia-chk-mostrar-no');
        return !!(si && no && !si.checked && no.checked);
    }

    function queryTiendasNoSeleccionadasModalProductoIa() {
        if (!esSoloMostrarNoModalProductoIa()) return '';
        const container = document.getElementById('modal-listado-ia-tiendas-no-lista');
        if (!container || container.dataset.loaded !== '1') return '';
        const parts = [];
        container.querySelectorAll('input[type="checkbox"].modal-listado-ia-tienda-no-cb').forEach(c => {
            if (c.checked) parts.push('tienda_ids[]=' + encodeURIComponent(c.value));
        });
        return parts.length ? '&' + parts.join('&') : '';
    }

    function queryModalProductoIaFiltros() {
        return queryMostrarTiendaModalProductoIa() + queryTiendasNoSeleccionadasModalProductoIa();
    }

    function ajustarChecksMostrarTiendaModalProductoIa(ev) {
        const targetId = ev && ev.target ? ev.target.id : '';
        const si = document.getElementById('modal-listado-ia-chk-mostrar-si');
        const no = document.getElementById('modal-listado-ia-chk-mostrar-no');
        if (si && no && targetId === 'modal-listado-ia-chk-mostrar-no' && no.checked && si.checked) {
            si.checked = false;
        }
    }

    async function cargarOpcionesTiendasMostrarNoModalIa() {
        const container = document.getElementById('modal-listado-ia-tiendas-no-lista');
        if (!container || container.dataset.loaded === '1') return;
        container.innerHTML = '<p class="text-xs text-gray-500 dark:text-gray-400">Cargando tiendas...</p>';
        try {
            const res = await fetch(rutasIa.neoTiendasMostrarNo + '?_=' + Date.now(), { cache: 'no-store', headers: { 'Accept': 'application/json' } });
            const lista = await res.json();
            container.innerHTML = '';
            if (!Array.isArray(lista) || !lista.length) {
                container.innerHTML = '<p class="text-xs text-gray-500 dark:text-gray-400">No hay tiendas con mostrar «no».</p>';
                container.dataset.loaded = '1';
                return;
            }
            const masterLabel = document.createElement('label');
            masterLabel.className = 'flex items-center gap-2 cursor-pointer font-medium text-gray-900 dark:text-gray-100 pb-2 mb-1 border-b border-gray-200 dark:border-gray-600';
            const masterCb = document.createElement('input');
            masterCb.type = 'checkbox';
            masterCb.id = 'modal-listado-ia-chk-tiendas-no-todas';
            masterCb.checked = true;
            masterCb.className = 'rounded border-gray-300 text-blue-600 focus:ring-blue-500 shrink-0';
            const masterSpan = document.createElement('span');
            masterSpan.textContent = 'Todas las tiendas';
            masterLabel.appendChild(masterCb);
            masterLabel.appendChild(masterSpan);
            container.appendChild(masterLabel);
            masterCb.addEventListener('change', function() {
                container.querySelectorAll('input.modal-listado-ia-tienda-no-cb').forEach(c => {
                    c.checked = masterCb.checked;
                });
                recargarListaModalProductoIa();
            });
            lista.forEach(t => {
                const label = document.createElement('label');
                label.className = 'flex items-center gap-2 cursor-pointer text-gray-800 dark:text-gray-200';
                const cb = document.createElement('input');
                cb.type = 'checkbox';
                cb.value = String(t.id);
                cb.checked = true;
                cb.className = 'rounded border-gray-300 text-blue-600 focus:ring-blue-500 modal-listado-ia-tienda-no-cb';
                cb.addEventListener('change', function() {
                    actualizarMasterTiendasMostrarNoModalIa();
                    recargarListaModalProductoIa();
                });
                const span = document.createElement('span');
                span.textContent = t.nombre || ('Tienda #' + t.id);
                label.appendChild(cb);
                label.appendChild(span);
                container.appendChild(label);
            });
            container.dataset.loaded = '1';
        } catch (e) {
            console.error(e);
            container.innerHTML = '<p class="text-xs text-red-500">Error al cargar tiendas.</p>';
        }
    }

    function actualizarMasterTiendasMostrarNoModalIa() {
        const master = document.getElementById('modal-listado-ia-chk-tiendas-no-todas');
        const checks = document.querySelectorAll('#modal-listado-ia-tiendas-no-lista input.modal-listado-ia-tienda-no-cb');
        if (!master || !checks.length) return;
        master.checked = Array.from(checks).every(c => c.checked);
    }

    async function actualizarVisibilidadPanelTiendasNoModalIa() {
        const details = document.getElementById('modal-listado-ia-details-tiendas-no');
        if (!details) return;
        const soloNo = esSoloMostrarNoModalProductoIa();
        details.classList.toggle('hidden', !soloNo);
        if (soloNo) {
            details.open = true;
            await cargarOpcionesTiendasMostrarNoModalIa();
        } else {
            details.open = false;
        }
    }

    function tiendaNoMostrarIa(tienda) {
        return String((tienda && tienda.mostrar_tienda) || '').toLowerCase() === 'no';
    }

    function tiendaNoScrapearIa(tienda) {
        return String((tienda && tienda.scrapear) || '').toLowerCase() === 'no';
    }

    function htmlEtiquetasTiendaRestringidaIa(tienda) {
        const parts = [];
        if (tiendaNoMostrarIa(tienda)) {
            parts.push('<span class="text-red-600 dark:text-red-400 text-xs font-semibold ml-1">no mostrar</span>');
        }
        if (tiendaNoScrapearIa(tienda)) {
            parts.push('<span class="text-red-600 dark:text-red-400 text-xs font-semibold ml-1">no scrapear</span>');
        }
        return parts.length ? ' ' + parts.join(' ') : '';
    }

    function ordenarTiendasRestringidasAlFinalIa(tiendas) {
        return (Array.isArray(tiendas) ? tiendas : []).slice().sort((a, b) => {
            const aNo = tiendaNoMostrarIa(a) ? 1 : 0;
            const bNo = tiendaNoMostrarIa(b) ? 1 : 0;
            if (aNo !== bNo) return aNo - bNo;
            return String(a.nombre || '').localeCompare(String(b.nombre || ''), 'es');
        });
    }

    async function cargarProductosNeoIa() {
        const tiendasLista = document.getElementById('modal-listado-ia-tiendas-no-lista');
        if (tiendasLista) {
            tiendasLista.innerHTML = '';
            delete tiendasLista.dataset.loaded;
        }
        const chkSi = document.getElementById('modal-listado-ia-chk-mostrar-si');
        const chkNo = document.getElementById('modal-listado-ia-chk-mostrar-no');
        const chkNull = document.getElementById('modal-listado-ia-chk-tienda-null');
        if (chkSi) chkSi.checked = true;
        if (chkNo) chkNo.checked = false;
        if (chkNull) chkNull.checked = false;
        abrirModalListadoIa('Elegir producto', { filtrosProducto: true });
        await recargarListaModalProductoIa();
    }

    async function recargarListaModalProductoIa() {
        await actualizarVisibilidadPanelTiendasNoModalIa();
        const lista = document.getElementById('modal-listado-ia-lista');
        const totalEl = document.getElementById('modal-listado-ia-total-urls');
        lista.innerHTML = '<p class="text-gray-500 dark:text-gray-400 text-sm">Cargando...</p>';
        if (totalEl) totalEl.textContent = '…';
        const filtrosQuery = queryModalProductoIaFiltros();
        const res = await fetch(rutasIa.neoProductos + '?' + filtrosQuery + '&_=' + Date.now(), { cache: 'no-store', headers: { 'Accept': 'application/json' } });
        const data = await res.json();
        const parsed = parseRespuestaProductosNeoIa(data);
        const productos = parsed.productos;
        const nuloCountEl = document.getElementById('modal-listado-ia-null-count');
        if (nuloCountEl) nuloCountEl.textContent = String(parsed.filas_neo_tienda_id_null);
        if (totalEl) {
            totalEl.textContent = String(productos.reduce((sum, p) => sum + (parseInt(String(p.count || 0), 10) || 0), 0));
        }
        renderListadoIa(productos.map(p => ({
            id: p.producto_id,
            texto: p.texto_completo || ('Producto #' + p.producto_id),
            meta: '(' + (p.count || 0) + ' URLs)'
        })), 'No hay productos pendientes.', async item => {
            const url = rutasIa.neoUrlsProducto.replace('__ID__', encodeURIComponent(item.id)) + '?' + queryModalProductoIaFiltros();
            const resUrls = await fetch(url, { headers: { 'Accept': 'application/json' } });
            const dataUrls = await resUrls.json();
            ponerUrlsIa(dataUrls.urls || []);
            cerrarModalListadoIa();
        });
    }

    async function cargarCategoriasNeoIa() {
        abrirModalListadoIa('Elegir categoría');
        const res = await fetch(rutasIa.neoCategorias, { headers: { 'Accept': 'application/json' } });
        const categorias = await res.json();
        renderListadoIa((Array.isArray(categorias) ? categorias : []).map(c => ({
            id: c.categoria_id,
            texto: c.nombre || ('Categoría #' + c.categoria_id),
            meta: '(' + (c.count || 0) + ' URLs)',
            raw: c
        })), 'No hay categorías pendientes.', async item => {
            await mostrarTiendasDeCategoriaIa(item.raw || item);
        });
    }

    async function mostrarTiendasDeCategoriaIa(categoria) {
        document.getElementById('modal-listado-ia-titulo').textContent = 'Elegir tienda de ' + (categoria.nombre || ('Categoría #' + categoria.categoria_id));
        const atras = document.getElementById('modal-listado-ia-atras');
        atras.classList.remove('hidden');
        atras.onclick = cargarCategoriasNeoIa;

        const res = await fetch(rutasIa.neoTiendasCategoria.replace('__ID__', encodeURIComponent(categoria.categoria_id)), { headers: { 'Accept': 'application/json' } });
        const tiendas = await res.json();
        const items = [{
            id: 'todas',
            texto: 'Todas',
            meta: '(' + (categoria.count || 0) + ' URLs)',
            raw: null,
        }].concat(ordenarTiendasRestringidasAlFinalIa(tiendas).map(t => ({
            id: t.tienda_id,
            texto: t.nombre || ('Tienda #' + t.tienda_id),
            textoHtml: escaparIa(t.nombre || ('Tienda #' + t.tienda_id)) + htmlEtiquetasTiendaRestringidaIa(t),
            meta: '(' + (t.count || 0) + ' URLs)',
            raw: t,
        })));

        renderListadoIa(items, 'No hay tiendas pendientes.', async item => {
            const route = item.id === 'todas'
                ? rutasIa.neoUrlsCategoria.replace('__ID__', encodeURIComponent(categoria.categoria_id))
                : rutasIa.neoUrlsCategoriaTienda.replace('__CID__', encodeURIComponent(categoria.categoria_id)).replace('__TID__', encodeURIComponent(item.id));
            const resUrls = await fetch(route, { headers: { 'Accept': 'application/json' } });
            const dataUrls = await resUrls.json();
            ponerUrlsIa(dataUrls.urls || []);
            cerrarModalListadoIa();
        });
    }

    async function cargarTiendasNeoIa() {
        abrirModalListadoIa('Elegir tienda');
        const res = await fetch(rutasIa.neoTiendas, { headers: { 'Accept': 'application/json' } });
        const tiendas = await res.json();
        renderListadoIa(ordenarTiendasRestringidasAlFinalIa(tiendas).map(t => ({
            id: t.tienda_id,
            texto: t.nombre || ('Tienda #' + t.tienda_id),
            textoHtml: escaparIa(t.nombre || ('Tienda #' + t.tienda_id)) + htmlEtiquetasTiendaRestringidaIa(t),
            meta: '(' + (t.count || 0) + ' URLs)',
            raw: t
        })), 'No hay tiendas pendientes.', async item => {
            await mostrarCategoriasDeTiendaIa(item.raw || item);
        });
    }

    async function mostrarCategoriasDeTiendaIa(tienda) {
        document.getElementById('modal-listado-ia-titulo').textContent = 'Elegir categoría de ' + (tienda.nombre || ('Tienda #' + tienda.tienda_id));
        const atras = document.getElementById('modal-listado-ia-atras');
        atras.classList.remove('hidden');
        atras.onclick = cargarTiendasNeoIa;

        const res = await fetch(rutasIa.neoCategoriasTienda.replace('__ID__', encodeURIComponent(tienda.tienda_id)), { headers: { 'Accept': 'application/json' } });
        const categorias = await res.json();
        const items = [{
            id: 'todas',
            texto: 'Todas',
            meta: '(' + (tienda.count || 0) + ' URLs)',
        }].concat((Array.isArray(categorias) ? categorias : []).map(c => ({
            id: c.categoria_id,
            texto: c.nombre || ('Categoría #' + c.categoria_id),
            meta: '(' + (c.count || 0) + ' URLs)',
        })));

        renderListadoIa(items, 'No hay categorías pendientes.', async item => {
            const route = item.id === 'todas'
                ? rutasIa.neoUrlsTienda.replace('__ID__', encodeURIComponent(tienda.tienda_id))
                : rutasIa.neoUrlsTiendaCategoria.replace('__TID__', encodeURIComponent(tienda.tienda_id)).replace('__CID__', encodeURIComponent(item.id));
            const resUrls = await fetch(route, { headers: { 'Accept': 'application/json' } });
            const dataUrls = await resUrls.json();
            ponerUrlsIa(dataUrls.urls || []);
            cerrarModalListadoIa();
        });
    }

    async function comenzarIa() {
        if (estadoIa.ejecutando) return;
        estadoIa.urls = urlsTextareaIa();
        estadoIa.index = 0;
        estadoIa.siguienteAnalisisIndex = 0;
        estadoIa.analizando = false;
        estadoIa.usarIa = usarIaActivaCrearMasivo();
        estadoIa.modalAbierto = false;
        estadoIa.rowActual = null;
        estadoIa.rowActualIndex = null;
        estadoIa.resultadosPendientes = [];
        estadoIa.guardadosPendientes = 0;
        estadoIa.guardadoSegundoPlanoActual = null;
        estadoIa.guardadoSecuencia = 0;
        if (!estadoIa.urls.length) {
            alert('Carga al menos una URL.');
            return;
        }
        estadoIa.ejecutando = true;
        estadoIa.urlsAbiertasChatgpt = new Set();
        document.getElementById('btnComenzarIa').disabled = true;
        document.getElementById('usarIaCrearMasivo').disabled = true;
        logIa('Comienza ejecución: ' + estadoIa.urls.length + ' URLs' + (estadoIa.usarIa ? ' con IA' : ' sin IA'));
        procesarSiguienteIa();
    }

    function procesarSiguienteIa() {
        mostrarSiguienteResultadoIa();
        asegurarAnalisisSiguienteIa();
        comprobarFinalizacionIa();
    }

    function asegurarAnalisisSiguienteIa() {
        actualizarEstadoColaIa();
        if (!estadoIa.ejecutando || estadoIa.analizando) {
            return;
        }
        if (estadoIa.modalAbierto && estadoIa.resultadosPendientes.length > 0) {
            return;
        }
        if (estadoIa.siguienteAnalisisIndex >= estadoIa.urls.length) {
            comprobarFinalizacionIa();
            return;
        }

        const index = estadoIa.siguienteAnalisisIndex++;
        const url = estadoIa.urls[index];
        estadoIa.analizando = true;
        estadoIa.index = index;
        actualizarEstadoColaIa();
        procesarUrlIa(index, url)
            .then(row => {
                estadoIa.resultadosPendientes.push({ index, row });
            })
            .catch(err => {
                logIa('Error procesando URL ' + (index + 1) + ': ' + err.message, 'error');
            })
            .finally(() => {
                estadoIa.analizando = false;
                procesarSiguienteIa();
            });
    }

    async function procesarUrlIa(index, url) {
        logIa('URL ' + (index + 1) + '/' + estadoIa.urls.length + ': ' + url);
        const row = await analizarUrlIa(url);
        if (usarIaProcesoCrearMasivo()) {
            return await prepararRowConFallbackIa(row);
        }
        if (row.producto) {
            logIa('Producto sugerido por la web: ' + (row.producto.texto_completo || row.producto.nombre || row.producto.id), 'ok');
        } else {
            logIa('Producto no encontrado sin IA.');
        }
        return row;
    }

    function mostrarSiguienteResultadoIa() {
        if (!estadoIa.ejecutando || estadoIa.modalAbierto || !estadoIa.resultadosPendientes.length) {
            return;
        }
        const item = estadoIa.resultadosPendientes.shift();
        abrirModalRevisionIa(item.row, item.index);
        asegurarAnalisisSiguienteIa();
    }

    function comprobarFinalizacionIa() {
        if (!estadoIa.ejecutando) return;
        if (
            estadoIa.siguienteAnalisisIndex >= estadoIa.urls.length
            && !estadoIa.analizando
            && !estadoIa.modalAbierto
            && estadoIa.resultadosPendientes.length === 0
            && estadoIa.guardadosPendientes === 0
        ) {
            estadoIa.ejecutando = false;
            document.getElementById('btnComenzarIa').disabled = false;
            document.getElementById('usarIaCrearMasivo').disabled = false;
            actualizarEstadoColaIa();
            logIa('Ejecución finalizada.', 'ok');
        }
    }

    async function analizarUrlIa(url) {
        const usarIa = usarIaProcesoCrearMasivo();
        logIa((usarIa ? 'Petición ChatGPT URL: ' : 'Análisis sin IA URL: ') + url);
        abrirUrlPrimeraPeticionChatgptIa(url, false);
        const res = await fetch(rutasIa.analizar, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfIa(),
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                urls: url,
                usar_chatgpt: usarIa,
                incluir_contenido_pagina: usarIa,
                chatgpt_model: usarIa ? (document.getElementById('chatgptModelIa').value || null) : null,
            }),
        });
        const data = await res.json();
        if (!data.success || !Array.isArray(data.resultados) || !data.resultados[0]) {
            throw new Error(data.message || 'No se pudo analizar la URL.');
        }
        logIa('Respuesta análisis recibida.');
        return data.resultados[0];
    }

    async function prepararRowConFallbackIa(row) {
        if (row.producto && !row.no_entre_opciones) {
            logIa('Producto propuesto: ' + (row.producto.texto_completo || row.producto.nombre || row.producto.id), 'ok');
            return row;
        }

        logIa('Producto no confirmado. Pido palabras clave a ChatGPT.');
        const palabras = await pedirPalabrasClaveIa(row);
        row.__palabrasClaveIa = palabras;
        if (!palabras.length) {
            return row;
        }

        logIa('Palabras clave: ' + palabras.join(', '));
        const productos = await buscarProductosPorTextoIa(palabras.join(' '));
        if (!productos.length) {
            logIa('No hubo sugerencias con esas palabras clave.');
            return row;
        }

        logIa('Sugerencias encontradas: ' + productos.length + '. Pido a ChatGPT que elija una.');
        const elegidoId = await pedirEleccionProductoIa(row, productos);
        const producto = productos.find(p => String(p.id) === String(elegidoId));
        if (!producto) {
            logIa('ChatGPT no eligió una sugerencia clara.');
            return row;
        }

        logIa('Sugerencia elegida: ' + (producto.texto_completo || producto.id), 'ok');
        return await construirRowConProductoIa(row, producto, true);
    }

    async function pedirPalabrasClaveIa(row) {
        const res = await fetch(rutasIa.palabrasClave, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfIa(), 'Accept': 'application/json' },
            body: JSON.stringify({
                url: row.url_normalizada || row.url || '',
                titulo_producto: row.producto ? (row.producto.texto_completo || row.producto.nombre || '') : '',
                motivo: row.chatgpt_respuesta_raw || '',
                chatgpt_model: document.getElementById('chatgptModelIa').value || null,
            }),
        });
        const data = await res.json().catch(() => ({}));
        return data.success && Array.isArray(data.palabras) ? data.palabras : [];
    }

    async function pedirEleccionProductoIa(row, productos) {
        const res = await fetch(rutasIa.elegirProducto, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfIa(), 'Accept': 'application/json' },
            body: JSON.stringify({
                url: row.url_normalizada || row.url || '',
                productos: productos.slice(0, 10),
                chatgpt_model: document.getElementById('chatgptModelIa').value || null,
            }),
        });
        const data = await res.json().catch(() => ({}));
        return data.success ? data.producto_id : null;
    }

    async function construirRowConProductoIa(row, producto, pedirSpecsIa) {
        const res = await fetch(rutasIa.recargarEspecificaciones.replace('__ID__', encodeURIComponent(producto.id)), { headers: { 'Accept': 'application/json' } });
        const data = await res.json();
        const productoCompleto = Object.assign({}, producto, {
            url_producto: data.url_producto || producto.url_producto || null,
            imagenes_producto: Array.isArray(data.imagenes_producto) ? data.imagenes_producto : [],
        });
        const next = Object.assign({}, row, {
            producto: productoCompleto,
            especificaciones: data.success ? (data.especificaciones || null) : null,
            tiene_especificaciones: data.success ? !!data.tiene_especificaciones : false,
            no_entre_opciones: false,
            error: null,
        });

        if (pedirSpecsIa && next.tiene_especificaciones) {
            logIa('Petición ChatGPT URL: ' + (row.url_normalizada || row.url || '') + ' (solo especificaciones)');
            const resSpecs = await fetch(rutasIa.completarEspecificaciones, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfIa(), 'Accept': 'application/json' },
                body: JSON.stringify({
                    url: row.url_normalizada || row.url || '',
                    producto_id: producto.id,
                    especificaciones: next.especificaciones || {},
                    chatgpt_model: document.getElementById('chatgptModelIa').value || null,
                }),
            });
            const dataSpecs = await resSpecs.json().catch(() => ({}));
            if (dataSpecs.success && dataSpecs.selecciones) {
                next.especificaciones_marcadas_chatgpt = dataSpecs.selecciones;
                logIa('Especificaciones IA recibidas.', 'ok');
            }
        }

        return next;
    }

    async function recargarEspecificacionesModalIa(marcar) {
        const row = estadoIa.rowActual;
        if (!row || !row.producto || !row.producto.id) return;
        const especsMarcadas = buildEspecificacionesModalIa() || {};
        if (marcar && marcar.principalId && marcar.subId) {
            if (!Array.isArray(especsMarcadas[marcar.principalId])) especsMarcadas[marcar.principalId] = [];
            if (!especsMarcadas[marcar.principalId].map(String).includes(String(marcar.subId))) {
                especsMarcadas[marcar.principalId].push(String(marcar.subId));
            }
        }

        const res = await fetch(rutasIa.recargarEspecificaciones.replace('__ID__', encodeURIComponent(row.producto.id)), { headers: { 'Accept': 'application/json' } });
        const data = await res.json();
        if (!data.success) return;
        const next = Object.assign({}, row, {
            producto: Object.assign({}, row.producto, {
                url_producto: data.url_producto || row.producto.url_producto || null,
                imagenes_producto: Array.isArray(data.imagenes_producto) ? data.imagenes_producto : (row.producto.imagenes_producto || []),
            }),
            especificaciones: data.especificaciones || null,
            tiene_especificaciones: !!data.tiene_especificaciones,
            especificaciones_marcadas: especsMarcadas,
            especificaciones_marcadas_chatgpt: null,
        });
        estadoIa.rowActual = next;
        renderModalRevisionIa(next);
    }

    async function buscarProductosPorTextoIa(query) {
        if (!query || query.trim().length < 2) return [];
        const res = await fetch(rutasIa.buscarProductos + '?q=' + encodeURIComponent(query), { headers: { 'Accept': 'application/json' } });
        const data = await res.json().catch(() => []);
        return Array.isArray(data) ? data : [];
    }

    function abrirModalRevisionIa(row, index) {
        estadoIa.modalAbierto = true;
        estadoIa.rowActual = row;
        estadoIa.rowActualIndex = index;
        estadoIa.index = index;
        renderModalRevisionIa(row);
        document.getElementById('modal-revision-ia').classList.remove('hidden');
        abrirUrlPrimeraPeticionChatgptIa(row.url_normalizada || row.url || estadoIa.urls[index] || '', true);
        actualizarEstadoColaIa();
    }

    function cerrarModalRevisionIa(avanzar) {
        document.getElementById('modal-revision-ia').classList.add('hidden');
        estadoIa.rowActual = null;
        estadoIa.rowActualIndex = null;
        estadoIa.modalAbierto = false;
        actualizarEstadoColaIa();
        setTimeout(procesarSiguienteIa, avanzar ? 0 : 50);
    }

    function finalizarProcesoIa() {
        estadoIa.ejecutando = false;
        estadoIa.modalAbierto = false;
        estadoIa.rowActual = null;
        estadoIa.rowActualIndex = null;
        estadoIa.resultadosPendientes = [];
        document.getElementById('modal-revision-ia').classList.add('hidden');
        document.getElementById('btnComenzarIa').disabled = false;
        document.getElementById('usarIaCrearMasivo').disabled = false;
        actualizarEstadoColaIa();
        logIa('Proceso finalizado manualmente.');
    }

    function abrirModalDescartarUrlIa() {
        const row = estadoIa.rowActual;
        const url = row ? (row.url_normalizada || row.url || '') : '';
        if (!url) return;
        const modal = document.getElementById('modal-descartar-url-ia');
        const texto = document.getElementById('modal-descartar-url-ia-texto');
        const error = document.getElementById('modal-descartar-url-ia-error');
        if (!modal || !texto) return;
        texto.textContent = url;
        if (error) {
            error.textContent = '';
            error.classList.add('hidden');
        }
        modal.classList.remove('hidden');
    }

    function cerrarModalDescartarUrlIa() {
        const modal = document.getElementById('modal-descartar-url-ia');
        if (modal) modal.classList.add('hidden');
    }

    async function confirmarDescartarUrlIa() {
        const row = estadoIa.rowActual;
        const url = row ? (row.url_normalizada || row.url || '') : '';
        if (!url) return;

        const btnConfirmar = document.getElementById('modal-descartar-url-ia-confirmar');
        const error = document.getElementById('modal-descartar-url-ia-error');
        if (btnConfirmar) {
            btnConfirmar.disabled = true;
            btnConfirmar.classList.add('opacity-70', 'cursor-not-allowed');
            btnConfirmar.textContent = 'Descartando...';
        }
        if (error) {
            error.textContent = '';
            error.classList.add('hidden');
        }

        try {
            const res = await fetch(rutasIa.descartarUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfIa(),
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ url }),
            });
            const data = await res.json().catch(() => ({}));
            if (!res.ok || !data.success) {
                throw new Error(data.message || data.error || 'No se pudo descartar la URL');
            }

            logIa('URL descartada: ' + url, 'ok');
            cerrarModalDescartarUrlIa();
            cerrarModalRevisionIa(true);
        } catch (err) {
            logIa('Error descartando URL: ' + err.message, 'error');
            if (error) {
                error.textContent = err.message;
                error.classList.remove('hidden');
            }
            const msg = document.getElementById('modal-revision-ia-msg');
            if (msg) {
                msg.className = 'text-sm font-medium text-red-600 dark:text-red-400';
                msg.textContent = 'Error al descartar URL: ' + err.message;
                msg.classList.remove('hidden');
            }
        } finally {
            if (btnConfirmar) {
                btnConfirmar.disabled = false;
                btnConfirmar.classList.remove('opacity-70', 'cursor-not-allowed');
                btnConfirmar.textContent = 'Aceptar y descartar';
            }
        }
    }

    function renderModalRevisionIa(row) {
        const url = row.url_normalizada || row.url || '';
        const indexVisible = estadoIa.rowActualIndex !== null ? estadoIa.rowActualIndex : estadoIa.index;
        document.getElementById('modal-revision-ia-titulo').textContent = 'Revisar URL ' + (indexVisible + 1) + ' / ' + estadoIa.urls.length;
        const urlEl = document.getElementById('modal-revision-ia-url');
        urlEl.textContent = url;
        urlEl.href = url;
        renderGuardadosSegundoPlanoIa();
        document.getElementById('modal-revision-ia-msg').classList.add('hidden');
        document.getElementById('modal-revision-ia-guardar').textContent = 'Guardar';
        document.getElementById('modal-revision-ia-envio').value = textoEnvioIa(row.envio_sugerido);

        const alerta = document.getElementById('modal-revision-ia-alerta');
        alerta.className = 'hidden rounded border px-3 py-2 text-sm';
        alerta.textContent = '';
        if (row.error) {
            alerta.textContent = row.error;
            alerta.className = 'rounded border border-red-200 dark:border-red-700 bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-300 px-3 py-2 text-sm';
        }

        renderProductoModalIa(row);
        renderBuscadorModalIa(row);
        renderSpecsModalIa(row);
        renderUrlYProductoResaltadosIa();
        actualizarGuardarModalIa();
    }

    function renderProductoModalIa(row) {
        const cont = document.getElementById('modal-revision-ia-producto');
        if (!row.producto) {
            cont.innerHTML = '';
            cont.classList.add('hidden');
            return;
        }
        cont.classList.remove('hidden');

        const texto = row.producto.nombre || row.producto.texto_completo || [row.producto.marca, row.producto.modelo, row.producto.talla].filter(Boolean).join(' - ');
        const link = row.producto.url_producto
            ? '<a href="' + escaparIa(row.producto.url_producto) + '" target="_blank" class="text-green-600 dark:text-green-400 hover:underline font-medium"><span id="modal-revision-ia-producto-texto" data-texto="' + escaparIa(texto) + '">' + escaparIa(texto) + '</span></a>'
            : '<strong><span id="modal-revision-ia-producto-texto" data-texto="' + escaparIa(texto) + '">' + escaparIa(texto) + '</span></strong>';
        const imagenesProducto = Array.isArray(row.producto.imagenes_producto) ? row.producto.imagenes_producto : [];
        const btnImagenes = imagenesProducto.length
            ? ' <button type="button" id="btnVerImagenesProductoIa" class="inline-flex items-center p-1 bg-blue-600 hover:bg-blue-700 text-white rounded text-xs ml-1 align-middle" title="Ver imágenes del producto"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg></button>'
            : '';
        const productoId = row.producto.id || '';
        const botonesPanel = productoId
            ? ' <a href="' + NEO_PANEL_PRODUCTOS_BASE_IA + '/' + encodeURIComponent(productoId) + '/edit" target="_blank" rel="noopener noreferrer" class="inline-flex items-center px-2 py-1 ml-0.5 text-xs font-medium rounded bg-indigo-600 hover:bg-indigo-700 text-white transition" title="Editar producto en el panel">Editar</a>'
                + ' <a href="' + NEO_PANEL_PRODUCTOS_BASE_IA + '/' + encodeURIComponent(productoId) + '/ofertas" target="_blank" rel="noopener noreferrer" class="inline-flex items-center px-2 py-1 ml-0.5 text-xs font-medium rounded bg-teal-600 hover:bg-teal-700 text-white transition" title="Listado de ofertas del producto">Ofertas</a>'
            : '';
        cont.innerHTML = '<span class="font-medium">Producto:</span> ' + link
            + btnImagenes
            + botonesPanel
            + ' <button type="button" id="btnCambiarProductoIa" class="ml-2 px-2 py-1 text-xs rounded bg-red-500 hover:bg-red-600 text-white">Cambiar</button>';
        const btnVerImagenes = document.getElementById('btnVerImagenesProductoIa');
        if (btnVerImagenes) {
            btnVerImagenes.addEventListener('click', function() {
                abrirModalImagenesProductoIa(imagenesProducto);
            });
        }
        document.getElementById('btnCambiarProductoIa').addEventListener('click', function() {
            const next = Object.assign({}, estadoIa.rowActual, { producto: null, especificaciones: null, tiene_especificaciones: false });
            estadoIa.rowActual = next;
            renderModalRevisionIa(next);
        });
    }

    function renderBuscadorModalIa(row) {
        const wrap = document.getElementById('modal-revision-ia-buscador');
        const palabras = document.getElementById('modal-revision-ia-palabras');
        const search = document.getElementById('modal-revision-ia-search');
        const sugerencias = document.getElementById('modal-revision-ia-sugerencias');
        if (search) search.value = '';
        if (sugerencias) {
            sugerencias.innerHTML = '';
            sugerencias.classList.add('hidden');
        }
        estadoIa.productosBusqueda = [];
        wrap.classList.toggle('hidden', !!row.producto);
        if (row.producto) {
            palabras.innerHTML = '';
            return;
        }

        const palabrasClave = Array.isArray(row.__palabrasClaveIa) && row.__palabrasClaveIa.length
            ? row.__palabrasClaveIa
            : extraerPalabrasSlugDesdeUrlIa(row.url_normalizada || row.url || '');
        const unicas = Array.from(new Set(palabrasClave.map(p => String(p || '').trim()).filter(Boolean)));
        palabras.innerHTML = unicas.length
            ? unicas.map(function(palabra) {
                const segura = escaparIa(palabra);
                return '<button type="button" class="btn-palabra-producto-ia inline-flex items-center px-2 py-1 rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-xs text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-600 transition" data-palabra="' + segura + '">' + segura + '</button>';
            }).join('')
            : '';
    }

    function renderSpecsModalIa(row) {
        const cont = document.getElementById('modal-revision-ia-specs');
        const especs = row.especificaciones || {};
        const filtros = Array.isArray(especs.filtros) ? especs.filtros : [];
        cont.dataset.columnasIds = JSON.stringify(especs.columnas_ids || []);
        cont.dataset.esUnidadUnica = especs.unidad_de_medida === 'unidadUnica' ? '1' : '0';
        window.__crearMasivoIaImagenesSublinea = {};

        if (!row.producto) {
            cont.innerHTML = '';
            cont.classList.add('hidden');
            return;
        }
        cont.classList.remove('hidden');
        if (!row.tiene_especificaciones || !filtros.length) {
            cont.innerHTML = '<p class="text-sm text-gray-500 dark:text-gray-400">Sin especificaciones internas.</p>';
            return;
        }

        const marcadas = row.especificaciones_marcadas_chatgpt || row.especificaciones_marcadas || {};
        const imagenesProducto = row.producto && Array.isArray(row.producto.imagenes_producto) ? row.producto.imagenes_producto : [];
        let html = '<p class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-3">Especificaciones a marcar</p><div class="space-y-4">';
        filtros.forEach(f => {
            const subs = Array.isArray(f.subprincipales) ? f.subprincipales : [];
            if (!subs.length) return;
            const principalId = String(f.id);
            const grupoLabelEsc = escaparIa(f.texto || principalId);
            html += '<div class="spec-line border-l-2 border-gray-300 dark:border-gray-600 pl-3" data-principal-id="' + escaparIa(principalId) + '">';
            html += '<p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">' + grupoLabelEsc + '</p>';
            html += '<div class="flex flex-wrap gap-2 items-center">';
            html += '<button type="button" class="btn-toggle-nueva-opcion-ia inline-flex items-center p-1 shrink-0 rounded bg-indigo-600 hover:bg-indigo-700 text-white text-xs transition focus:outline-none focus:ring-2 focus:ring-indigo-400" data-principal-id="' + escaparIa(principalId) + '" data-insert-first="1" title="Añadir opción en primera posición"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg></button>';
            subs.forEach(sub => {
                const subId = String(sub.id);
                const checked = Array.isArray(marcadas[principalId]) && marcadas[principalId].map(String).includes(subId);
                let imagenes = Array.isArray(sub.imagenes) ? sub.imagenes : [];
                if (sub.usar_imagenes_producto && imagenesProducto.length) imagenes = imagenesProducto;
                const keyImg = 'ia::' + String(row.producto.id || '') + '::' + principalId + '::' + subId;
                if (imagenes.length) window.__crearMasivoIaImagenesSublinea[keyImg] = imagenes;
                const btnImgs = imagenes.length
                    ? '<button type="button" class="btn-ver-imagenes-spec-ia inline-flex items-center p-1 bg-blue-600 hover:bg-blue-700 text-white rounded text-xs ml-0.5" data-key="' + escaparIa(keyImg) + '" title="Ver ' + imagenes.length + ' imagen' + (imagenes.length !== 1 ? 'es' : '') + '"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg></button>'
                    : '';
                html += '<label class="spec-option-label-ia inline-flex items-center gap-1 cursor-pointer rounded border border-gray-200 dark:border-gray-700 px-2 py-1 bg-white dark:bg-gray-800 transition-opacity">'
                    + '<input type="checkbox" class="spec-checkbox-ia rounded border-gray-300 text-green-600 focus:ring-green-500" name="spec_ia_' + escaparIa(principalId) + '[]" data-principal-id="' + escaparIa(principalId) + '" data-sublinea-id="' + escaparIa(subId) + '"' + (checked ? ' checked' : '') + '>'
                    + '<span class="spec-option-text-ia text-sm text-gray-700 dark:text-gray-300" data-principal-id="' + escaparIa(principalId) + '" data-sublinea-id="' + escaparIa(subId) + '">' + escaparIa(sub.texto || subId) + '</span>'
                    + '<span class="spec-count-badge-ia text-xs text-gray-500 dark:text-gray-400" data-principal-id="' + escaparIa(principalId) + '" data-sublinea-id="' + escaparIa(subId) + '">(x)</span>' + btnImgs
                    + '</label>';
                html += '<button type="button" class="btn-toggle-nueva-opcion-ia inline-flex items-center p-1 shrink-0 rounded bg-indigo-600 hover:bg-indigo-700 text-white text-xs transition focus:outline-none focus:ring-2 focus:ring-indigo-400 ml-0.5" data-principal-id="' + escaparIa(principalId) + '" data-after-sub-id="' + escaparIa(subId) + '" title="Añadir opción después de esta"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg></button>';
            });
            html += '<button type="button" class="btn-limpiar-grupo-ia text-xs text-red-600 dark:text-red-400 hover:underline" data-principal-id="' + escaparIa(principalId) + '">Limpiar grupo</button>';
            html += '</div>';
            html += '<div class="nueva-opcion-ia-panel hidden mt-2 p-3 rounded-lg border border-indigo-200 dark:border-indigo-700 bg-indigo-50/60 dark:bg-indigo-950/30 space-y-2" data-principal-id="' + escaparIa(principalId) + '">';
            html += '<p class="text-xs text-indigo-900 dark:text-indigo-100">Nueva opción en «' + grupoLabelEsc + '»</p>';
            html += '<input type="text" class="nueva-opcion-ia-texto w-full px-3 py-2 rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm text-gray-900 dark:text-white" placeholder="Nombre de la opción">';
            html += '<label class="inline-flex items-center gap-2 cursor-pointer text-xs text-gray-700 dark:text-gray-300"><input type="checkbox" class="nueva-opcion-ia-usar-img-prod rounded border-gray-300 text-orange-600 focus:ring-orange-500"><span>Usar imágenes del producto</span></label>';
            html += '<div class="grid grid-cols-1 md:grid-cols-[1fr_auto] gap-2 items-end">';
            html += '<label class="text-xs text-gray-600 dark:text-gray-300">Carpeta imágenes<select class="nueva-opcion-ia-carpeta mt-1 w-full px-3 py-2 rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm text-gray-900 dark:text-white"><option value="">Selecciona una carpeta</option></select></label>';
            html += '<label class="inline-flex items-center justify-center px-3 py-2 text-xs rounded bg-green-600 hover:bg-green-700 text-white cursor-pointer"><input type="file" class="nueva-opcion-ia-file hidden" accept="image/*" multiple>Subir imágenes</label>';
            html += '</div>';
            html += '<div class="flex flex-wrap gap-2 items-center">';
            html += '<button type="button" class="btn-ia-ver-imagenes-draft inline-flex items-center px-2 py-1 text-xs rounded bg-blue-600 hover:bg-blue-700 text-white disabled:opacity-45 disabled:cursor-not-allowed" disabled>Ver imágenes (0)</button>';
            html += '<span class="nueva-opcion-ia-upload-msg text-xs text-gray-500 dark:text-gray-400"></span>';
            html += '</div>';
            html += '<div class="flex flex-wrap gap-2">';
            html += '<button type="button" class="btn-ia-guardar-nueva-opcion inline-flex items-center px-3 py-1.5 text-xs font-medium rounded bg-indigo-700 hover:bg-indigo-800 text-white">Guardar y marcar</button>';
            html += '<button type="button" class="btn-ia-cancelar-nueva-opcion px-3 py-1.5 text-xs rounded border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800">Cancelar</button>';
            html += '</div>';
            html += '<p class="nueva-opcion-ia-msg text-xs hidden"></p>';
            html += '</div></div>';
        });
        cont.innerHTML = html + '</div>';
        cont.querySelectorAll('.btn-limpiar-grupo-ia').forEach(btn => {
            btn.addEventListener('click', function() {
                cont.querySelectorAll('input[name="spec_ia_' + CSS.escape(this.dataset.principalId) + '[]"]').forEach(r => r.checked = false);
                actualizarEstadoVisualSpecsModalIa();
                renderUrlYProductoResaltadosIa();
                actualizarConteosOpcionesSpecsModalIa();
            });
        });
        cont.querySelectorAll('.spec-checkbox-ia').forEach(cb => {
            cb.addEventListener('change', function() {
                actualizarEstadoVisualSpecsModalIa();
                renderUrlYProductoResaltadosIa();
                actualizarConteosOpcionesSpecsModalIa();
            });
        });
        cont.querySelectorAll('.btn-ver-imagenes-spec-ia').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                abrirModalImagenesSpecIa(this.dataset.key);
            });
        });
        actualizarEstadoVisualSpecsModalIa();
        actualizarConteosOpcionesSpecsModalIa();
    }

    function actualizarEstadoVisualSpecsModalIa() {
        const cont = document.getElementById('modal-revision-ia-specs');
        if (!cont) return;

        cont.querySelectorAll('.spec-line').forEach(line => {
            const checks = Array.from(line.querySelectorAll('.spec-checkbox-ia'));
            const tieneMarcada = checks.some(cb => cb.checked);

            line.classList.toggle('border-l-2', tieneMarcada);
            line.classList.toggle('border-gray-300', tieneMarcada);
            line.classList.toggle('dark:border-gray-600', tieneMarcada);
            line.classList.toggle('pl-3', tieneMarcada);
            line.classList.toggle('border', !tieneMarcada);
            line.classList.toggle('border-yellow-300', !tieneMarcada);
            line.classList.toggle('dark:border-yellow-600', !tieneMarcada);
            line.classList.toggle('bg-yellow-50', !tieneMarcada);
            line.classList.toggle('dark:bg-yellow-900/20', !tieneMarcada);
            line.classList.toggle('rounded-lg', !tieneMarcada);
            line.classList.toggle('p-3', !tieneMarcada);

            line.querySelectorAll('.spec-option-label-ia').forEach(label => {
                const input = label.querySelector('.spec-checkbox-ia');
                const apagada = tieneMarcada && input && !input.checked;
                label.classList.toggle('opacity-40', apagada);
                label.classList.toggle('grayscale', apagada);
                label.classList.toggle('bg-gray-100', apagada);
                label.classList.toggle('dark:bg-gray-700', apagada);
                label.classList.toggle('border-gray-300', apagada);
                label.classList.toggle('dark:border-gray-600', apagada);
            });
        });
    }

    function limpiarConteosOpcionesSpecsModalIa() {
        const cont = document.getElementById('modal-revision-ia-specs');
        if (!cont) return;
        cont.querySelectorAll('.spec-count-badge-ia').forEach(badge => {
            badge.textContent = '(x)';
        });
        cont.querySelectorAll('.spec-option-text-ia').forEach(el => {
            el.classList.remove(
                'bg-orange-300', 'dark:bg-orange-600/80', 'text-orange-950', 'dark:text-orange-50',
                'text-green-700', 'dark:text-green-300', 'font-semibold', 'px-0.5', 'rounded',
                'border', 'border-orange-500/70'
            );
        });
    }

    function aplicarConteosOpcionesSpecsModalIa(conteos) {
        const cont = document.getElementById('modal-revision-ia-specs');
        if (!cont) return;
        const counts = conteos && typeof conteos === 'object' ? conteos : {};
        const maxPorGrupo = {};
        const gruposConSeleccion = new Set();

        cont.querySelectorAll('.spec-checkbox-ia:checked').forEach(cb => {
            if (cb.dataset && cb.dataset.principalId) {
                gruposConSeleccion.add(String(cb.dataset.principalId));
            }
        });

        Object.keys(counts).forEach(principalId => {
            const grupo = counts[principalId];
            if (!grupo || typeof grupo !== 'object') return;
            const vals = Object.values(grupo).map(v => parseInt(v, 10)).filter(v => Number.isFinite(v));
            if (vals.length > 0) maxPorGrupo[principalId] = Math.max(...vals);
        });

        cont.querySelectorAll('.spec-count-badge-ia').forEach(badge => {
            const pid = String(badge.dataset.principalId || '');
            const sid = String(badge.dataset.sublineaId || '');
            const n = counts[pid] && counts[pid][sid] != null ? parseInt(counts[pid][sid], 10) : 0;
            badge.textContent = '(' + (Number.isFinite(n) ? n : 0) + ')';
        });

        cont.querySelectorAll('.spec-option-text-ia').forEach(el => {
            const pid = String(el.dataset.principalId || '');
            const sid = String(el.dataset.sublineaId || '');
            const n = counts[pid] && counts[pid][sid] != null ? parseInt(counts[pid][sid], 10) : 0;
            const max = maxPorGrupo[pid] ?? null;
            const cb = cont.querySelector('.spec-checkbox-ia[data-principal-id="' + CSS.escape(pid) + '"][data-sublinea-id="' + CSS.escape(sid) + '"]');
            const estaMarcada = !!(cb && cb.checked);
            const grupoYaMarcado = gruposConSeleccion.has(pid);
            const esTop = Number.isFinite(n) && max !== null && max > 0 && n === max;
            const sugerirVerde = !grupoYaMarcado && !estaMarcada && esTop;

            el.classList.toggle('bg-orange-300', estaMarcada);
            el.classList.toggle('dark:bg-orange-600/80', estaMarcada);
            el.classList.toggle('text-orange-950', estaMarcada);
            el.classList.toggle('dark:text-orange-50', estaMarcada);
            el.classList.toggle('border', estaMarcada);
            el.classList.toggle('border-orange-500/70', estaMarcada);
            el.classList.toggle('px-0.5', estaMarcada || sugerirVerde);
            el.classList.toggle('rounded', estaMarcada || sugerirVerde);

            el.classList.toggle('text-green-700', sugerirVerde);
            el.classList.toggle('dark:text-green-300', sugerirVerde);
            el.classList.toggle('font-semibold', estaMarcada || sugerirVerde);
        });
    }

    async function actualizarConteosOpcionesSpecsModalIa() {
        const row = estadoIa.rowActual;
        const cont = document.getElementById('modal-revision-ia-specs');
        if (!row || !row.producto || !cont) return;
        const specLines = cont.querySelectorAll('.spec-line');
        if (!specLines.length) {
            limpiarConteosOpcionesSpecsModalIa();
            return;
        }

        const body = { producto_id: row.producto.id };
        const especs = buildEspecificacionesModalIa();
        if (especs) body.especificaciones_internas = JSON.stringify(especs);

        const reqId = (cont.__conteosReqId || 0) + 1;
        cont.__conteosReqId = reqId;
        try {
            const res = await fetch(rutasIa.contarOpcionesEspecificaciones, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfIa(),
                    'Accept': 'application/json',
                },
                body: JSON.stringify(body),
            });
            const data = await res.json().catch(() => ({}));
            if (cont.__conteosReqId !== reqId) return;
            if (!data.success) {
                limpiarConteosOpcionesSpecsModalIa();
                return;
            }
            aplicarConteosOpcionesSpecsModalIa(data.conteos || {});
        } catch (e) {
            if (cont.__conteosReqId === reqId) limpiarConteosOpcionesSpecsModalIa();
        }
    }

    function resetPanelNuevaOpcionIa(panel) {
        if (!panel) return;
        const input = panel.querySelector('.nueva-opcion-ia-texto');
        if (input) input.value = '';
        const usar = panel.querySelector('.nueva-opcion-ia-usar-img-prod');
        if (usar) usar.checked = false;
        const file = panel.querySelector('.nueva-opcion-ia-file');
        if (file) file.value = '';
        const msg = panel.querySelector('.nueva-opcion-ia-msg');
        if (msg) {
            msg.textContent = '';
            msg.classList.add('hidden');
        }
        const uploadMsg = panel.querySelector('.nueva-opcion-ia-upload-msg');
        if (uploadMsg) uploadMsg.textContent = '';
        panel.__iaDraftImagenes = [];
        actualizarBotonesImagenDraftIa(panel);
    }

    function cerrarPanelNuevaOpcionIa(panel) {
        if (!panel) return;
        panel.classList.add('hidden');
        resetPanelNuevaOpcionIa(panel);
    }

    function actualizarBotonesImagenDraftIa(panel) {
        if (!panel) return;
        const draft = Array.isArray(panel.__iaDraftImagenes) ? panel.__iaDraftImagenes : [];
        const usarProd = !!(panel.querySelector('.nueva-opcion-ia-usar-img-prod') && panel.querySelector('.nueva-opcion-ia-usar-img-prod').checked);
        const btnVer = panel.querySelector('.btn-ia-ver-imagenes-draft');
        if (btnVer) {
            btnVer.textContent = 'Ver imágenes (' + draft.length + ')';
            btnVer.disabled = usarProd || draft.length === 0;
        }
        const file = panel.querySelector('.nueva-opcion-ia-file');
        if (file) file.disabled = usarProd;
    }

    async function cargarCarpetasNuevaOpcionIa(panel) {
        if (!panel) return;
        const select = panel.querySelector('.nueva-opcion-ia-carpeta');
        if (!select || select.dataset.cargadas === '1') return;
        try {
            const res = await fetch(rutasIa.carpetasImagenes, { headers: { 'Accept': 'application/json' } });
            const data = await res.json().catch(() => ({}));
            const carpetas = Array.isArray(data.data) ? data.data : [];
            select.innerHTML = '<option value="">Selecciona una carpeta</option>' + carpetas.map(c => '<option value="' + escaparIa(c) + '">' + escaparIa(c) + '</option>').join('');
            select.dataset.cargadas = '1';
        } catch (e) {}
    }

    function abrirModalVerImagenesDraftIa(panel) {
        if (!panel || !Array.isArray(panel.__iaDraftImagenes) || !panel.__iaDraftImagenes.length) return;
        window.__crearMasivoIaImagenesActual = { key: 'draft', imagenes: panel.__iaDraftImagenes.slice() };
        renderizarMiniaturasSpecIa();
        document.getElementById('modal-imagenes-spec-ia').classList.remove('hidden');
    }

    async function subirImagenesNuevaOpcionIa(panel, files) {
        if (!panel || !files || !files.length) return;
        const carpeta = panel.querySelector('.nueva-opcion-ia-carpeta') ? panel.querySelector('.nueva-opcion-ia-carpeta').value : '';
        const uploadMsg = panel.querySelector('.nueva-opcion-ia-upload-msg');
        if (!carpeta) {
            if (uploadMsg) uploadMsg.textContent = 'Selecciona una carpeta.';
            return;
        }
        if (!Array.isArray(panel.__iaDraftImagenes)) panel.__iaDraftImagenes = [];
        if (uploadMsg) uploadMsg.textContent = 'Subiendo...';

        for (const file of Array.from(files)) {
            if (!file.type || !file.type.startsWith('image/')) continue;
            const fd = new FormData();
            fd.append('imagen', file);
            fd.append('carpeta', carpeta);
            fd.append('_token', csrfIa());
            const res = await fetch(rutasIa.subirImagenSimple, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfIa(), 'Accept': 'application/json' },
                body: fd,
            });
            const data = await res.json().catch(() => ({}));
            if (!res.ok || !data.success || !data.data || !data.data.ruta_relativa) {
                if (uploadMsg) uploadMsg.textContent = data.message || 'Error al subir imagen.';
                continue;
            }
            panel.__iaDraftImagenes.push(data.data.ruta_relativa);
        }

        const fileInput = panel.querySelector('.nueva-opcion-ia-file');
        if (fileInput) fileInput.value = '';
        if (uploadMsg) uploadMsg.textContent = panel.__iaDraftImagenes.length ? 'Imágenes listas: ' + panel.__iaDraftImagenes.length : '';
        actualizarBotonesImagenDraftIa(panel);
    }

    async function guardarNuevaOpcionIa(panel, btnGuardar) {
        const row = estadoIa.rowActual;
        if (!panel || !row || !row.producto) return;
        const principalId = String(panel.dataset.principalId || '');
        const texto = (panel.querySelector('.nueva-opcion-ia-texto') ? panel.querySelector('.nueva-opcion-ia-texto').value : '').trim();
        const usarProd = !!(panel.querySelector('.nueva-opcion-ia-usar-img-prod') && panel.querySelector('.nueva-opcion-ia-usar-img-prod').checked);
        const msg = panel.querySelector('.nueva-opcion-ia-msg');
        if (!texto) {
            if (msg) {
                msg.textContent = 'Indica el nombre de la opción.';
                msg.className = 'nueva-opcion-ia-msg text-xs text-red-600 mt-1';
                msg.classList.remove('hidden');
            }
            return;
        }

        const body = {
            producto_id: row.producto.id,
            principal_id: principalId,
            texto,
            usar_imagenes_producto: usarProd,
            imagenes: (!usarProd && Array.isArray(panel.__iaDraftImagenes)) ? panel.__iaDraftImagenes : [],
        };
        if (panel.__iaInsertFirst) body.insert_first = true;
        else if (panel.__iaInsertAfterSubId) body.after_sub_id = panel.__iaInsertAfterSubId;

        if (btnGuardar) btnGuardar.disabled = true;
        try {
            const res = await fetch(rutasIa.anadirOpcionEspecificacion, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfIa(), 'Accept': 'application/json' },
                body: JSON.stringify(body),
            });
            const data = await res.json().catch(() => ({}));
            if (!res.ok || !data.success) {
                throw new Error(data.error || data.message || 'No se pudo guardar la opción.');
            }
            cerrarPanelNuevaOpcionIa(panel);
            await recargarEspecificacionesModalIa({ principalId: data.principal_id || principalId, subId: data.sub_id });
        } catch (e) {
            if (msg) {
                msg.textContent = e.message || 'Error';
                msg.className = 'nueva-opcion-ia-msg text-xs text-red-600 mt-1';
                msg.classList.remove('hidden');
            }
        } finally {
            if (btnGuardar) btnGuardar.disabled = false;
        }
    }

    function abrirModalImagenesSpecIa(key) {
        const modal = document.getElementById('modal-imagenes-spec-ia');
        if (!modal) return;
        const imagenes = window.__crearMasivoIaImagenesSublinea && window.__crearMasivoIaImagenesSublinea[key]
            ? window.__crearMasivoIaImagenesSublinea[key]
            : [];
        window.__crearMasivoIaImagenesActual = { key, imagenes: Array.isArray(imagenes) ? imagenes : [] };
        renderizarMiniaturasSpecIa();
        modal.classList.remove('hidden');
    }

    function abrirModalImagenesProductoIa(imagenes) {
        const modal = document.getElementById('modal-imagenes-spec-ia');
        if (!modal) return;
        window.__crearMasivoIaImagenesActual = { key: 'producto', imagenes: Array.isArray(imagenes) ? imagenes : [] };
        renderizarMiniaturasSpecIa();
        modal.classList.remove('hidden');
    }

    function cerrarModalImagenesSpecIa() {
        const modal = document.getElementById('modal-imagenes-spec-ia');
        if (!modal) return;
        modal.classList.add('hidden');
        window.__crearMasivoIaImagenesActual = { key: null, imagenes: [] };
    }

    function renderizarMiniaturasSpecIa() {
        const container = document.getElementById('miniaturas-container-spec-ia');
        const imgGrande = document.getElementById('imagen-grande-spec-ia');
        if (!container || !imgGrande) return;
        container.innerHTML = '';
        const imagenes = window.__crearMasivoIaImagenesActual && Array.isArray(window.__crearMasivoIaImagenesActual.imagenes)
            ? window.__crearMasivoIaImagenesActual.imagenes
            : [];
        if (!imagenes.length) {
            imgGrande.removeAttribute('src');
            imgGrande.alt = 'No hay imágenes';
            container.innerHTML = '<p class="text-sm text-gray-500 dark:text-gray-400">No hay imágenes</p>';
            return;
        }

        imgGrande.src = resolverUrlImagenCrearMasivoIa(imagenes[0]);
        imgGrande.alt = 'Imagen 1';
        imagenes.forEach((imgPath, index) => {
            const div = document.createElement('div');
            const url = resolverUrlImagenCrearMasivoIa(imgPath);
            div.className = 'miniatura-spec-ia cursor-pointer border-2 border-gray-300 dark:border-gray-600 rounded p-1 hover:border-blue-500 transition-colors';
            if (index === 0) div.classList.add('border-blue-500');

            const img = document.createElement('img');
            img.src = url;
            img.alt = 'Miniatura ' + (index + 1);
            img.className = 'w-full h-20 object-cover rounded';
            div.appendChild(img);

            div.addEventListener('click', function() {
                imgGrande.src = url;
                imgGrande.alt = 'Imagen ' + (index + 1);
                container.querySelectorAll('.miniatura-spec-ia').forEach(m => m.classList.remove('border-blue-500'));
                div.classList.add('border-blue-500');
            });
            container.appendChild(div);
        });
    }

    function actualizarGuardarModalIa() {
        const row = estadoIa.rowActual;
        document.getElementById('modal-revision-ia-guardar').disabled = !(row && row.producto && row.tienda);
    }

    function buildEspecificacionesModalIa() {
        const cont = document.getElementById('modal-revision-ia-specs');
        const checks = cont.querySelectorAll('.spec-checkbox-ia:checked');
        const out = {};
        checks.forEach(check => {
            const pid = check.dataset.principalId;
            const sid = check.dataset.sublineaId;
            if (!pid || !sid) return;
            if (!out[pid]) out[pid] = [];
            out[pid].push(sid);
        });

        const columnasIds = JSON.parse(cont.dataset.columnasIds || '[]');
        if (cont.dataset.esUnidadUnica === '1' && Array.isArray(columnasIds) && columnasIds.length) {
            out._columnas = {};
            columnasIds.forEach(pid => {
                if (out[pid] && out[pid][0]) out._columnas[pid] = out[pid][0];
            });
            if (!Object.keys(out._columnas).length) delete out._columnas;
        }
        return Object.keys(out).length ? out : null;
    }

    function textoEnvioIa(valor) {
        if (valor == null || valor === '') return '';
        const n = parseFloat(valor);
        if (!isFinite(n) || n <= 0) return '';
        return String(Math.round(n * 100) / 100).replace('.', ',');
    }

    function aplicarEnvioIa(body) {
        const raw = document.getElementById('modal-revision-ia-envio').value.trim().replace(',', '.');
        if (raw === '') {
            body.envio = null;
            return;
        }
        const n = parseFloat(raw);
        body.envio = isFinite(n) && n > 0 ? Math.round(n * 100) / 100 : null;
    }

    function esErrorPrecioIa(data) {
        const txt = String((data && (data.error || data.message)) || '').toLowerCase();
        return txt.includes('precio') || txt.includes('sin stock') || txt.includes('agotado') || txt.includes('no se pudo obtener') || txt.includes('out of stock');
    }

    function formatearEurosIa(valor, sufijo, fallback) {
        if (valor == null || valor === '') return fallback || '';
        const n = parseFloat(valor);
        if (!isFinite(n)) return fallback || '';
        return n.toFixed(2).replace('.', ',') + (sufijo || '');
    }

    function renderGuardadosSegundoPlanoIa() {
        const cont = document.getElementById('modal-revision-ia-guardados-bg');
        if (!cont) return;
        const item = estadoIa.guardadoSegundoPlanoActual;
        if (!item) {
            cont.classList.add('hidden');
            cont.innerHTML = '';
            return;
        }

        const url = item.url ? '<div class="text-xs text-gray-500 dark:text-gray-400 break-all">' + escaparIa(item.url) + '</div>' : '';
        if (item.estado === 'guardando') {
            cont.innerHTML = '<div class="rounded border border-blue-200 dark:border-blue-700 bg-blue-50 dark:bg-blue-900/20 px-3 py-2">'
                + '<div class="flex items-center gap-2 text-sm font-medium text-blue-700 dark:text-blue-300">'
                + '<span class="inline-block w-3 h-3 border-2 border-blue-600 border-t-transparent rounded-full animate-spin"></span>'
                + '<span>Guardando oferta de URL ' + escaparIa(String(item.index + 1)) + '...</span>'
                + '</div>' + url + '</div>';
        } else if (item.estado === 'ok') {
            const envioVal = formatearEurosIa(item.data && item.data.envio, ' € env.', 'gratis');
            const precioVal = formatearEurosIa(item.data && item.data.precio_unidad, ' €/ud', item.sinPrecio ? '0 €/ud' : '');
            const editUrl = item.data && item.data.oferta_edit_url;
            const editar = editUrl
                ? '<a href="' + escaparIa(editUrl) + '" target="_blank" rel="noopener noreferrer" class="inline-flex items-center justify-center shrink-0 p-1.5 rounded bg-blue-600 hover:bg-blue-700 text-white transition" title="Editar oferta" aria-label="Editar oferta"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg></a>'
                : '';
            cont.innerHTML = '<div class="rounded border border-green-200 dark:border-green-700 bg-green-50 dark:bg-green-900/20 px-3 py-2">'
                + '<div class="flex items-center gap-2 flex-wrap">'
                + editar
                + '<span class="text-sm font-medium text-green-700 dark:text-green-300">'
                + (item.sinPrecio ? 'Oferta creada con precio 0' : 'Oferta creada correctamente')
                + (item.data && item.data.oferta_id ? ' (ID: ' + escaparIa(String(item.data.oferta_id)) + ')' : '')
                + '</span>'
                + '<span class="text-sm text-gray-600 dark:text-gray-400">' + escaparIa(envioVal + (precioVal ? ' · ' + precioVal : '')) + '</span>'
                + '</div>' + url + '</div>';
        } else {
            cont.innerHTML = '<div class="rounded border border-red-200 dark:border-red-700 bg-red-50 dark:bg-red-900/20 px-3 py-2">'
                + '<div class="text-sm font-medium text-red-700 dark:text-red-300">Error guardando URL ' + escaparIa(String(item.index + 1)) + ': ' + escaparIa(item.error || 'Error') + '</div>'
                + url + '</div>';
        }
        cont.classList.remove('hidden');
    }

    function guardarModalIa() {
        const row = estadoIa.rowActual;
        if (!row || !row.producto || !row.tienda) return;

        const btn = document.getElementById('modal-revision-ia-guardar');
        const msg = document.getElementById('modal-revision-ia-msg');
        btn.disabled = true;
        btn.textContent = 'Guardando...';
        msg.className = 'text-sm font-medium text-gray-600 dark:text-gray-300';
        msg.textContent = 'Creando oferta en segundo plano...';
        msg.classList.remove('hidden');

        const body = {
            url: row.url_normalizada || row.url,
            producto_id: row.producto.id,
            tienda_id: row.tienda.id,
            especificaciones_internas: (function() {
                const especs = buildEspecificacionesModalIa();
                return especs ? JSON.stringify(especs) : null;
            })(),
        };
        aplicarEnvioIa(body);

        const indexGuardado = estadoIa.rowActualIndex;
        const guardado = {
            id: ++estadoIa.guardadoSecuencia,
            index: indexGuardado !== null ? indexGuardado : 0,
            url: row.url_normalizada || row.url || '',
            estado: 'guardando',
            data: null,
            error: null,
            sinPrecio: false,
        };
        estadoIa.guardadoSegundoPlanoActual = guardado;
        renderGuardadosSegundoPlanoIa();
        guardarOfertaSegundoPlanoIa(body, guardado);
        cerrarModalRevisionIa(true);
    }

    async function guardarOfertaSegundoPlanoIa(body, guardado) {
        const indexGuardado = guardado.index;
        estadoIa.guardadosPendientes++;
        actualizarEstadoColaIa();
        renderGuardadosSegundoPlanoIa();
        logIa('Guardando oferta en segundo plano para URL ' + ((indexGuardado !== null ? indexGuardado : 0) + 1) + '.');
        try {
            let data = await postCrearOfertaIa(body);
            if (!data.success && esErrorPrecioIa(data)) {
                logIa('Precio no encontrado en URL ' + ((indexGuardado !== null ? indexGuardado : 0) + 1) + '. Reintento creando con precio 0.');
                guardado.sinPrecio = true;
                renderGuardadosSegundoPlanoIa();
                data = await postCrearOfertaIa(Object.assign({}, body, { generar_sin_precio: true }));
            }

            if (data.success) {
                guardado.estado = 'ok';
                guardado.data = data;
                logIa('Oferta creada en segundo plano para URL ' + ((indexGuardado !== null ? indexGuardado : 0) + 1) + '. ID: ' + (data.oferta_id || ''), 'ok');
                renderGuardadosSegundoPlanoIa();
                return;
            }

            throw new Error(data.error || data.message || 'Error al crear oferta.');
        } catch (err) {
            guardado.estado = 'error';
            guardado.error = err.message;
            logIa('Error guardando en segundo plano URL ' + ((indexGuardado !== null ? indexGuardado : 0) + 1) + ': ' + err.message, 'error');
            renderGuardadosSegundoPlanoIa();
        } finally {
            estadoIa.guardadosPendientes = Math.max(0, estadoIa.guardadosPendientes - 1);
            actualizarEstadoColaIa();
            comprobarFinalizacionIa();
        }
    }

    async function postCrearOfertaIa(body) {
        const res = await fetch(rutasIa.crear, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfIa(), 'Accept': 'application/json' },
            body: JSON.stringify(body),
        });
        return await res.json().catch(() => ({ success: false, error: 'Respuesta no JSON (HTTP ' + res.status + ')' }));
    }

    let timeoutBusquedaModalIa = null;
    async function buscarManualModalIa(query) {
        const cont = document.getElementById('modal-revision-ia-sugerencias');
        if (!query || query.trim().length < 2) {
            cont.classList.add('hidden');
            return;
        }
        const productos = await buscarProductosPorTextoIa(query);
        estadoIa.productosBusqueda = productos;
        cont.innerHTML = '';
        if (!productos.length) {
            cont.innerHTML = '<div class="px-4 py-2 text-gray-500 dark:text-gray-400 text-sm">No se encontraron productos</div>';
        } else {
            productos.forEach(producto => {
                const div = document.createElement('div');
                div.className = 'px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 cursor-pointer text-gray-900 dark:text-white border-b border-gray-200 dark:border-gray-600 last:border-b-0 text-sm';
                div.textContent = producto.texto_completo || producto.nombre || ('Producto #' + producto.id);
                div.addEventListener('click', async function() {
                    const next = await construirRowConProductoIa(estadoIa.rowActual, producto, usarIaProcesoCrearMasivo());
                    estadoIa.rowActual = next;
                    document.getElementById('modal-revision-ia-sugerencias').classList.add('hidden');
                    renderModalRevisionIa(next);
                });
                cont.appendChild(div);
            });
        }
        cont.classList.remove('hidden');
    }

    document.getElementById('btnProductoNeoIa').addEventListener('click', () => cargarProductosNeoIa().catch(err => logIa(err.message, 'error')));
    document.getElementById('btnCategoriaNeoIa').addEventListener('click', () => cargarCategoriasNeoIa().catch(err => logIa(err.message, 'error')));
    document.getElementById('btnTiendaNeoIa').addEventListener('click', () => cargarTiendasNeoIa().catch(err => logIa(err.message, 'error')));
    document.getElementById('btnComenzarIa').addEventListener('click', comenzarIa);
    document.getElementById('usarIaCrearMasivo').addEventListener('change', actualizarOpcionesModeloIa);
    document.getElementById('urlsIa').addEventListener('input', actualizarEstadoColaIa);
    document.getElementById('btnLimpiarTerminalIa').addEventListener('click', () => document.getElementById('terminalIa').textContent = '');
    document.getElementById('modal-listado-ia-cerrar').addEventListener('click', cerrarModalListadoIa);
    document.getElementById('modal-listado-ia-cerrar-btn').addEventListener('click', cerrarModalListadoIa);
    ['modal-listado-ia-chk-mostrar-si', 'modal-listado-ia-chk-mostrar-no', 'modal-listado-ia-chk-tienda-null'].forEach(function(id) {
        const el = document.getElementById(id);
        if (!el) return;
        el.addEventListener('change', function(ev) {
            if (document.getElementById('modal-listado-ia').classList.contains('hidden')) return;
            if (id === 'modal-listado-ia-chk-tienda-null' && el.checked) {
                const si = document.getElementById('modal-listado-ia-chk-mostrar-si');
                const no = document.getElementById('modal-listado-ia-chk-mostrar-no');
                if (si) si.checked = false;
                if (no) no.checked = false;
            } else if ((id === 'modal-listado-ia-chk-mostrar-si' || id === 'modal-listado-ia-chk-mostrar-no') && el.checked) {
                const nulo = document.getElementById('modal-listado-ia-chk-tienda-null');
                if (nulo) nulo.checked = false;
            }
            ajustarChecksMostrarTiendaModalProductoIa(ev);
            recargarListaModalProductoIa().catch(err => {
                console.error(err);
                document.getElementById('modal-listado-ia-lista').innerHTML = '<p class="text-red-500 text-sm">Error al cargar: ' + escaparIa(err.message) + '</p>';
            });
        });
    });
    document.getElementById('modal-listado-ia-btn-buscar-tienda').addEventListener('click', async function() {
        if (!confirm('¿Rellenar tienda por URL en filas neo sin tienda_id y categoría desde el producto cuando falte?')) return;
        const btn = this;
        const textoOriginal = btn.textContent;
        btn.disabled = true;
        btn.textContent = 'Rellenando…';
        try {
            const res = await fetch(rutasIa.neoRellenarTiendaId, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfIa(),
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
            if (!document.getElementById('modal-listado-ia').classList.contains('hidden')) {
                await recargarListaModalProductoIa();
            }
        } catch (err) {
            console.error(err);
            alert('Error: ' + err.message);
        } finally {
            btn.disabled = false;
            btn.textContent = textoOriginal;
        }
    });
    document.getElementById('modal-revision-ia-saltar-top').addEventListener('click', () => cerrarModalRevisionIa(false));
    document.getElementById('modal-revision-ia-descartar-top').addEventListener('click', abrirModalDescartarUrlIa);
    document.getElementById('modal-revision-ia-finalizar').addEventListener('click', finalizarProcesoIa);
    document.getElementById('modal-revision-ia-guardar').addEventListener('click', guardarModalIa);
    document.getElementById('modal-descartar-url-ia-cerrar').addEventListener('click', cerrarModalDescartarUrlIa);
    document.getElementById('modal-descartar-url-ia-cancelar').addEventListener('click', cerrarModalDescartarUrlIa);
    document.getElementById('modal-descartar-url-ia-confirmar').addEventListener('click', confirmarDescartarUrlIa);
    document.getElementById('modal-descartar-url-ia').addEventListener('click', function(e) {
        if (e.target === this) cerrarModalDescartarUrlIa();
    });
    document.getElementById('modal-imagenes-spec-ia').addEventListener('click', function(e) {
        if (e.target === this) cerrarModalImagenesSpecIa();
    });
    document.addEventListener('click', function(e) {
        const toggle = e.target.closest('.btn-toggle-nueva-opcion-ia');
        if (toggle) {
            const line = toggle.closest('.spec-line');
            const panel = line ? line.querySelector('.nueva-opcion-ia-panel') : null;
            if (!panel) return;
            const abrir = panel.classList.contains('hidden');
            document.querySelectorAll('.nueva-opcion-ia-panel').forEach(p => {
                if (p !== panel) cerrarPanelNuevaOpcionIa(p);
            });
            if (abrir) {
                panel.classList.remove('hidden');
                resetPanelNuevaOpcionIa(panel);
                panel.__iaInsertFirst = toggle.dataset.insertFirst === '1';
                panel.__iaInsertAfterSubId = toggle.dataset.afterSubId || null;
                cargarCarpetasNuevaOpcionIa(panel);
            } else {
                cerrarPanelNuevaOpcionIa(panel);
            }
            return;
        }

        const cancel = e.target.closest('.btn-ia-cancelar-nueva-opcion');
        if (cancel) {
            cerrarPanelNuevaOpcionIa(cancel.closest('.nueva-opcion-ia-panel'));
            return;
        }

        const btnVer = e.target.closest('.btn-ia-ver-imagenes-draft');
        if (btnVer && !btnVer.disabled) {
            abrirModalVerImagenesDraftIa(btnVer.closest('.nueva-opcion-ia-panel'));
            return;
        }

        const btnGuardarNueva = e.target.closest('.btn-ia-guardar-nueva-opcion');
        if (btnGuardarNueva) {
            guardarNuevaOpcionIa(btnGuardarNueva.closest('.nueva-opcion-ia-panel'), btnGuardarNueva);
        }
    });
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('nueva-opcion-ia-usar-img-prod')) {
            actualizarBotonesImagenDraftIa(e.target.closest('.nueva-opcion-ia-panel'));
            return;
        }
        if (e.target.classList.contains('nueva-opcion-ia-file')) {
            subirImagenesNuevaOpcionIa(e.target.closest('.nueva-opcion-ia-panel'), e.target.files).catch(err => {
                const panel = e.target.closest('.nueva-opcion-ia-panel');
                const msg = panel ? panel.querySelector('.nueva-opcion-ia-upload-msg') : null;
                if (msg) msg.textContent = err.message || 'Error al subir.';
            });
        }
    });
    document.addEventListener('keydown', function(e) {
        if (e.key !== 'Escape') return;
        const modalDescartar = document.getElementById('modal-descartar-url-ia');
        if (modalDescartar && !modalDescartar.classList.contains('hidden')) {
            cerrarModalDescartarUrlIa();
            return;
        }
        const modalImagenes = document.getElementById('modal-imagenes-spec-ia');
        if (modalImagenes && !modalImagenes.classList.contains('hidden')) cerrarModalImagenesSpecIa();
    });
    document.getElementById('modal-revision-ia-search').addEventListener('input', function() {
        clearTimeout(timeoutBusquedaModalIa);
        const q = this.value.trim();
        timeoutBusquedaModalIa = setTimeout(() => buscarManualModalIa(q).catch(err => logIa(err.message, 'error')), 300);
    });
    document.getElementById('modal-revision-ia-palabras').addEventListener('click', function(e) {
        const btn = e.target.closest('.btn-palabra-producto-ia');
        if (!btn) return;
        e.preventDefault();
        const palabra = String(btn.dataset.palabra || '').trim();
        const searchInput = document.getElementById('modal-revision-ia-search');
        if (!palabra || !searchInput) return;
        const actual = searchInput.value.trim();
        searchInput.value = actual ? (actual + ' ' + palabra) : palabra;
        searchInput.focus();
        searchInput.dispatchEvent(new Event('input', { bubbles: true }));
    });

    actualizarOpcionesModeloIa();
    actualizarEstadoColaIa();
    logIa('Vista lista. Carga URLs y pulsa Comenzar.');
    </script>
</x-app-layout>
