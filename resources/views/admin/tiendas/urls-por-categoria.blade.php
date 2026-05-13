<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.dashboard') }}">
                <h2 class="font-semibold text-xl text-white leading-tight">Inicio -></h2>
            </a>
            <a href="{{ route('admin.tiendas.index') }}">
                <h2 class="font-semibold text-xl text-white leading-tight">Tiendas -></h2>
            </a>
            <h2 class="font-semibold text-xl text-white leading-tight">URL por categorías de tiendas</h2>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto py-10 px-4 space-y-6 bg-gray-50 dark:bg-gray-900 rounded-lg shadow-md">
        @if(session('success'))
            <div class="p-3 rounded-lg bg-green-100 dark:bg-green-900/30 border border-green-400 text-green-800 dark:text-green-200 text-sm">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="p-3 rounded-lg bg-red-100 dark:bg-red-900/30 border border-red-400 text-red-700 dark:text-red-300 text-sm">
                <ul class="list-disc pl-5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6 border border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-200 mb-4">Buscar categoría</h3>

            <form method="GET" action="{{ route('admin.tiendas.urls-por-categoria') }}" class="space-y-3">
                <div class="relative">
                    <input
                        type="hidden"
                        name="categoria_id"
                        id="categoria_id"
                        value="{{ old('categoria_id', $categoriaId ?? request('categoria_id')) }}"
                    >
                    <input
                        type="text"
                        id="categoria_buscador_input"
                        value="{{ old('categoria_nombre', $categoriaNombre ?? '') }}"
                        class="w-full px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border border-gray-300 dark:border-gray-600"
                        placeholder="Escribe para buscar categorías..."
                        autocomplete="off"
                    >
                    <div
                        id="categoria_buscador_sugerencias"
                        class="absolute z-50 w-full mt-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md shadow-lg hidden max-h-60 overflow-y-auto"
                    ></div>
                </div>

                <div class="flex items-center gap-3">
                    <button
                        type="submit"
                        class="inline-flex items-center font-semibold text-sm px-4 py-2 rounded-md shadow-md transition bg-pink-600 hover:bg-pink-700 text-white"
                    >
                        Ver URLs de esta categoría
                    </button>
                    <a
                        href="{{ route('admin.tiendas.urls-por-categoria') }}"
                        class="inline-flex items-center font-semibold text-sm px-4 py-2 rounded-md shadow-md transition bg-gray-600 hover:bg-gray-700 text-white"
                    >
                        Limpiar
                    </a>
                </div>
            </form>
        </div>

        @if($categoriaSeleccionada)
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6 border border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-200">
                    Categoría seleccionada: <span class="text-pink-600 dark:text-pink-400">{{ $categoriaSeleccionada->nombre }}</span>
                </h3>
                <p class="text-sm text-gray-500 mt-1">Edita la URL y fecha por tienda, y guarda cada fila de forma individual.</p>
            </div>

            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-2 md:p-4 border border-gray-200 dark:border-gray-700 overflow-x-auto">
                <table class="min-w-full w-full table-fixed text-sm text-left text-gray-700 dark:text-gray-200">
                    <colgroup>
                        <col style="width: 30%">
                        <col style="width: 48%">
                        <col style="width: 12%">
                        <col style="width: 10%">
                    </colgroup>
                    <thead class="text-xs uppercase bg-gray-100 dark:bg-gray-700">
                        <tr>
                            <th class="px-4 py-3">Tienda</th>
                            <th class="px-4 py-3">URL categoría</th>
                            <th class="px-4 py-3">Visitada</th>
                            <th class="px-4 py-3 text-right">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($tiendas as $tienda)
                            <tr class="border-b border-gray-200 dark:border-gray-700 align-top">
                                <td class="px-4 py-3">
                                    @if(!empty($tienda->url))
                                        <a href="{{ $tienda->url }}" target="_blank" class="font-semibold text-blue-600 dark:text-blue-400 hover:underline">
                                            {{ $tienda->nombre }}
                                        </a>
                                    @else
                                        <span class="font-semibold">{{ $tienda->nombre }}</span>
                                    @endif

                                    <div class="mt-2 flex flex-wrap gap-2">
                                        @if($tienda->mostrar_tienda === 'no')
                                            <span class="px-2 py-1 text-xs rounded bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300 border border-red-300 dark:border-red-700">
                                                No mostrar
                                            </span>
                                        @endif

                                        @if($tienda->scrapear === 'no')
                                            <span class="px-2 py-1 text-xs rounded bg-orange-100 dark:bg-orange-900/30 text-orange-800 dark:text-orange-300 border border-orange-300 dark:border-orange-700">
                                                No scraping
                                            </span>
                                        @endif

                                        @if(!empty($tienda->anotaciones_internas))
                                            <button
                                                type="button"
                                                class="px-2 py-1 text-xs rounded bg-yellow-100 dark:bg-yellow-900/30 text-yellow-900 dark:text-yellow-200 border border-yellow-300 dark:border-yellow-700 hover:bg-yellow-200 dark:hover:bg-yellow-800/40"
                                                onclick="abrirModalInfo('{{ $tienda->id }}')"
                                            >
                                                +Info
                                            </button>
                                            <div id="info-interna-{{ $tienda->id }}" class="hidden">{{ $tienda->anotaciones_internas }}</div>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <form method="POST" action="{{ route('admin.tiendas.urls-por-categoria.guardar') }}" class="space-y-2">
                                        @csrf
                                        <input type="hidden" name="tienda_id" value="{{ $tienda->id }}">
                                        <input type="hidden" name="categoria_id" value="{{ $categoriaSeleccionada->id }}">
                                        <input
                                            type="text"
                                            name="url_categoria"
                                            value="{{ old('url_categoria', $tienda->url_categoria ?? '') }}"
                                            class="w-full px-3 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border border-gray-300 dark:border-gray-600"
                                            placeholder="https://..."
                                        >
                                </td>
                                <td class="px-4 py-3">
                                        <input
                                            type="datetime-local"
                                            name="visitada_categoria"
                                            value="{{ old('visitada_categoria', $tienda->visitada_categoria ? \Carbon\Carbon::parse($tienda->visitada_categoria)->format('Y-m-d\TH:i') : '') }}"
                                            class="w-full px-3 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border border-gray-300 dark:border-gray-600"
                                        >
                                </td>
                                <td class="px-4 py-3 text-right">
                                        <button
                                            type="submit"
                                            class="inline-flex items-center font-semibold text-sm px-4 py-2 rounded-md shadow-md transition bg-pink-600 hover:bg-pink-700 text-white"
                                        >
                                            Guardar
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">
                                    No hay tiendas disponibles para mostrar.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @else
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6 border border-gray-200 dark:border-gray-700 text-sm text-gray-500 dark:text-gray-400">
                Selecciona una categoría para ver y editar sus URLs por tienda.
            </div>
        @endif
    </div>

    <div id="modal-info" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div class="bg-gray-800 rounded-lg p-6 max-w-lg w-full">
            <h2 class="text-lg font-semibold mb-4 text-yellow-300">Información interna</h2>
            <div id="modal-info-contenido" class="text-sm text-gray-200 whitespace-pre-wrap"></div>
            <div class="mt-5 flex justify-end">
                <button
                    type="button"
                    onclick="cerrarModalInfo()"
                    class="px-4 py-2 text-sm font-medium text-white bg-gray-600 rounded-md hover:bg-gray-500"
                >
                    Cerrar
                </button>
            </div>
        </div>
    </div>

    <script>
        const categoriaInput = document.getElementById('categoria_buscador_input');
        const categoriaIdInput = document.getElementById('categoria_id');
        const sugerencias = document.getElementById('categoria_buscador_sugerencias');
        let debounceTimer = null;

        function limpiarSugerencias() {
            sugerencias.innerHTML = '';
            sugerencias.classList.add('hidden');
        }

        function seleccionarCategoria(categoria) {
            categoriaInput.value = categoria.nombre;
            categoriaIdInput.value = categoria.id;
            limpiarSugerencias();
        }

        function renderSugerencias(categorias) {
            sugerencias.innerHTML = '';

            if (!categorias.length) {
                sugerencias.classList.add('hidden');
                return;
            }

            categorias.forEach(function(categoria) {
                const item = document.createElement('button');
                item.type = 'button';
                item.className = 'w-full text-left px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 text-sm text-gray-700 dark:text-gray-200';
                item.textContent = categoria.nombre;
                item.addEventListener('click', function() {
                    seleccionarCategoria(categoria);
                });
                sugerencias.appendChild(item);
            });

            sugerencias.classList.remove('hidden');
        }

        async function buscarCategorias(query) {
            try {
                const response = await fetch(`/panel-privado/productos/buscar/categorias?q=${encodeURIComponent(query)}`);
                const data = await response.json();
                renderSugerencias(Array.isArray(data) ? data : []);
            } catch (error) {
                limpiarSugerencias();
            }
        }

        if (categoriaInput) {
            categoriaInput.addEventListener('input', function() {
                const query = categoriaInput.value.trim();
                categoriaIdInput.value = '';

                clearTimeout(debounceTimer);
                if (query.length < 2) {
                    limpiarSugerencias();
                    return;
                }

                debounceTimer = setTimeout(function() {
                    buscarCategorias(query);
                }, 250);
            });

            document.addEventListener('click', function(event) {
                if (!event.target.closest('#categoria_buscador_input') && !event.target.closest('#categoria_buscador_sugerencias')) {
                    limpiarSugerencias();
                }
            });
        }

        function abrirModalInfo(tiendaId) {
            const source = document.getElementById(`info-interna-${tiendaId}`);
            const contenido = document.getElementById('modal-info-contenido');
            const modal = document.getElementById('modal-info');

            if (!source || !contenido || !modal) {
                return;
            }

            contenido.textContent = source.textContent || '';
            modal.classList.remove('hidden');
        }

        function cerrarModalInfo() {
            const modal = document.getElementById('modal-info');
            if (modal) {
                modal.classList.add('hidden');
            }
        }

        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                cerrarModalInfo();
            }
        });
    </script>
</x-app-layout>
