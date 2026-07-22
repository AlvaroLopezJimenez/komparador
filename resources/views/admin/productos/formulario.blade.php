<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.dashboard') }}">
                <h2 class="font-semibold text-xl text-white leading-tight">
                    Inicio ->
                </h2>
            </a>
            <a href="{{ route('admin.productos.index') }}">
                <h2 class="font-semibold text-xl text-white leading-tight">
                    Producto ->
                </h2>
            </a>
            <h2 class="font-semibold text-xl text-white leading-tight">
                {{ $producto ? 'Editar producto' : 'Añadir producto' }}
            </h2>
        </div>
    </x-slot>

    <div class="max-w-5xl mx-auto py-10 px-4 space-y-8 bg-gray-50 dark:bg-gray-900 rounded-lg shadow-md form-container">

        <form method="POST" enctype="multipart/form-data"
            action="{{ $producto ? route('admin.productos.update', $producto) : route('admin.productos.store') }}">
            @csrf
            @if($producto)
            @method('PUT')
            @endif
            {{--CATEGORIA, SUBCATEGORIA Y SUBSUBCATEGORIA --}}
            <input type="hidden" id="producto_nombre" value="1">
            <fieldset class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6 space-y-6 border border-gray-200 dark:border-gray-700">
                <legend class="text-lg font-semibold text-gray-700 dark:text-gray-200">Categoría</legend>

                <!-- Pestañas -->
                <div class="border-b border-gray-200 dark:border-gray-600 mb-4">
                    <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                        <button type="button" id="tab-buscador-categoria" class="tab-categoria border-b-2 border-blue-500 py-2 px-1 text-sm font-medium text-blue-600 dark:text-blue-400">
                            Buscador
                        </button>
                        <button type="button" id="tab-manual-categoria" class="tab-categoria border-b-2 border-transparent py-2 px-1 text-sm font-medium text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300">
                            Manual
                        </button>
                    </nav>
                </div>

                <!-- Contenido pestaña Buscador -->
                <div id="content-buscador-categoria" class="tab-content-categoria space-y-4">
                    <div>
                        <div class="flex flex-wrap items-center gap-y-2 mb-2">
                            <label for="categoria-buscador-input" class="font-medium text-gray-700 dark:text-gray-200 shrink-0">Buscar categoría</label>
                            @if(!empty($categoriasRecientes ?? []))
                            <div id="categoria-buscador-recientes" class="flex flex-wrap items-center gap-1.5 flex-1 min-w-0 ml-4 sm:ml-6">
                                @foreach($categoriasRecientes as $catReciente)
                                <button type="button"
                                    class="kp-categoria-reciente-btn text-xs px-2 py-0.5 rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 hover:bg-blue-50 dark:hover:bg-blue-900/30 hover:border-blue-400 dark:hover:border-blue-500 truncate max-w-[11rem]"
                                    data-id="{{ $catReciente['id'] }}"
                                    data-nombre="{{ e($catReciente['nombre']) }}"
                                    title="{{ e($catReciente['nombre']) }}">{{ $catReciente['nombre'] }}</button>
                                @endforeach
                            </div>
                            @endif
                        </div>
                        <div class="relative">
                            <input type="text" id="categoria-buscador-input"
                                class="w-full px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border focus:outline-none focus:ring-2 focus:ring-blue-500"
                                placeholder="Escribe para buscar categorías..."
                                autocomplete="off">
                            <div id="categoria-buscador-sugerencias" class="absolute z-50 w-full mt-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md shadow-lg hidden max-h-60 overflow-y-auto"></div>
                        </div>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Selecciona una categoría para ver su jerarquía completa</p>
                    </div>
                    
                    <!-- Contenedor para mostrar la jerarquía seleccionada -->
                    <div id="categorias-container-buscador" class="space-y-4">
                        <!-- La jerarquía se mostrará aquí al seleccionar una categoría -->
                    </div>
                </div>

                <!-- Contenido pestaña Manual -->
                <div id="content-manual-categoria" class="tab-content-categoria space-y-4 hidden">
                    <div id="categorias-container" class="space-y-4">
                        <!-- Los selectores de categorías se generarán dinámicamente aquí -->
                    </div>
                </div>

                <div class="flex gap-2">
                    <button type="button" id="agregar-categoria" class="bg-green-600 hover:bg-green-700 text-white font-semibold px-4 py-2 rounded-md transition duration-150 ease-in-out focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                        + Agregar nivel de categoría
                    </button>
                    <button type="button" id="limpiar-categorias" class="bg-gray-600 hover:bg-gray-700 text-white font-semibold px-4 py-2 rounded-md transition duration-150 ease-in-out focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                        Limpiar categorías
                    </button>
                </div>

                <!-- Campo oculto para la categoría final -->
                <input type="hidden" name="categoria_id" id="categoria-final" value="{{ old('categoria_id', $producto->categoria_id ?? '') }}">
                
                <!-- Mensaje de advertencia si la categoría tiene subcategorías -->
                <div id="categoria-advertencia-subcategorias" class="hidden mt-4 p-3 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg">
                    <div class="flex items-start">
                        <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400 mt-0.5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                        <div class="flex-1">
                            <p class="text-sm font-medium text-yellow-800 dark:text-yellow-200">
                                La categoría seleccionada tiene subcategorías disponibles.
                            </p>
                            <p class="text-sm text-yellow-700 dark:text-yellow-300 mt-1">
                                Debes seleccionar la última categoría de la jerarquía (sin subcategorías) para poder guardar el producto.
                            </p>
                        </div>
                    </div>
                </div>
            </fieldset>

            {{-- UNIDAD DE MEDIDA --}}
            <fieldset class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6 space-y-6 border border-gray-200 dark:border-gray-700">
                <legend class="text-lg font-semibold text-gray-700 dark:text-gray-200">Unidad de medida</legend>
                
                <div>
                    <label class="block mb-1 font-medium text-gray-700 dark:text-gray-200">Unidad de medida</label>
                    <select name="unidadDeMedida" id="unidadDeMedida" class="w-full px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border @error('unidadDeMedida') border-red-500 @enderror">
                        <option value="unidad" {{ old('unidadDeMedida', $producto->unidadDeMedida ?? 'unidadUnica') == 'unidad' ? 'selected' : '' }}>Unidad</option>
                        <option value="kilos" {{ old('unidadDeMedida', $producto->unidadDeMedida ?? 'unidadUnica') == 'kilos' ? 'selected' : '' }}>Kilos</option>
                        <option value="litros" {{ old('unidadDeMedida', $producto->unidadDeMedida ?? 'unidadUnica') == 'litros' ? 'selected' : '' }}>Litros</option>
                        <option value="unidadMilesima" {{ old('unidadDeMedida', $producto->unidadDeMedida ?? 'unidadUnica') == 'unidadMilesima' ? 'selected' : '' }}>Unidad Milésima</option>
                        <option value="unidadUnica" {{ old('unidadDeMedida', $producto->unidadDeMedida ?? 'unidadUnica') == 'unidadUnica' ? 'selected' : '' }}>Unidad Única</option>
                        <option value="800gramos" {{ old('unidadDeMedida', $producto->unidadDeMedida ?? 'unidadUnica') == '800gramos' ? 'selected' : '' }}>800 gramos</option>
                        <option value="100ml" {{ old('unidadDeMedida', $producto->unidadDeMedida ?? 'unidadUnica') == '100ml' ? 'selected' : '' }}>100 ml</option>
                    </select>
                    @error('unidadDeMedida') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                </div>
            </fieldset>

            {{-- DATOS PRINCIPALES --}}
            <fieldset class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6 space-y-6 border border-gray-200 dark:border-gray-700">
                <legend class="text-lg font-semibold text-gray-700 dark:text-gray-200">Información general</legend>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div id="campo-nombre-sticky">
                        <label class="block mb-1 font-medium text-gray-700 dark:text-gray-200">Nombre *</label>
                        <input type="text" name="nombre" id="input_nombre" value="{{ old('nombre', $producto->nombre ?? '') }}"
                            class="w-full px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border @error('nombre') border-red-500 @enderror">
                        @error('nombre') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div id="slug-campo-wrap">
                        <div class="flex items-center gap-1.5 mb-1">
                            <label class="font-medium text-gray-700 dark:text-gray-200">Slug *</label>
                            @if($producto && $producto->id)
                            <button type="button" id="slug-candado-btn"
                                class="inline-flex items-center justify-center w-7 h-7 rounded-md border border-red-300 dark:border-red-700 bg-red-50 dark:bg-red-900/30 text-red-600 dark:text-red-400 hover:bg-red-100 dark:hover:bg-red-900/50 transition"
                                aria-label="Desbloquear campo slug"
                                title="Candado de control: clic para poder cambiar el slug">
                                <svg id="slug-candado-icono-cerrado" class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
                                </svg>
                                <svg id="slug-candado-icono-abierto" class="w-4 h-4 hidden" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                    <path d="M10 2a5 5 0 00-5 5v2a2 2 0 00-2 2v5a2 2 0 002 2h10a2 2 0 002-2v-5a2 2 0 00-2-2H7V7a3 3 0 115.905-.75A5.002 5.002 0 0010 2z"/>
                                </svg>
                            </button>
                            @endif
                        </div>
                        @if($producto && $producto->id)
                        <input type="hidden" name="slug" id="slug-valor-hidden" value="{{ old('slug', $producto->slug ?? '') }}">
                        @endif
                        <input type="text" id="slug" @if(!($producto && $producto->id)) name="slug" @endif value="{{ old('slug', $producto->slug ?? '') }}"
                            class="w-full px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border @error('slug') border-red-500 @enderror @if($producto && $producto->id) opacity-50 cursor-not-allowed bg-gray-200 dark:bg-gray-800 @endif"
                            @if($producto && $producto->id) readonly @endif>
                        <div id="slug_validation_message" class="mt-1 text-sm hidden"></div>
                        <div id="slug_other_products" class="mt-2 hidden">
                            <div class="text-sm text-red-600 dark:text-red-400 mb-2">Este slug ya existe en el siguiente producto:</div>
                            <div id="slug_products_list" class="text-sm text-gray-600 dark:text-gray-400"></div>
                        </div>
                        @error('slug') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block mb-1 font-medium text-gray-700 dark:text-gray-200">Marca *</label>
                        <div class="kp-segmentacion-palabras-wrap hidden mb-1.5 flex flex-wrap gap-1.5" aria-live="polite"></div>
                        <input type="text" name="marca" id="input_marca" value="{{ old('marca', $producto->marca ?? '') }}"
                            class="kp-campo-segmentacion-nombre w-full px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border @error('marca') border-red-500 @enderror">
                        @error('marca') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block mb-1 font-medium text-gray-700 dark:text-gray-200">Modelo *</label>
                        <div class="kp-segmentacion-palabras-wrap hidden mb-1.5 flex flex-wrap gap-1.5" aria-live="polite"></div>
                        <input type="text" name="modelo" id="input_modelo" value="{{ old('modelo', $producto->modelo ?? '') }}"
                            class="kp-campo-segmentacion-nombre w-full px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border @error('modelo') border-red-500 @enderror">
                        @error('modelo') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="flex items-center gap-1.5 mb-1 font-medium text-gray-700 dark:text-gray-200">
                            <span>Palabras extra</span>
                            <button type="button"
                                class="tooltip-btn inline-flex items-center justify-center w-5 h-5 rounded-full bg-gray-300 dark:bg-gray-600 text-gray-800 dark:text-gray-100 text-xs font-bold hover:bg-gray-400 dark:hover:bg-gray-500 focus:outline-none"
                                aria-label="Ayuda sobre palabras extra"
                                data-tooltip="estas palabras servirán para las búsquedas, pero no aparecerán en ninguna parte">?</button>
                        </label>
                        <div class="kp-segmentacion-palabras-wrap hidden mb-1.5 flex flex-wrap gap-1.5" aria-live="polite"></div>
                        <input type="text" name="talla" id="input_talla" value="{{ old('talla', $producto->talla ?? '') }}"
                            class="kp-campo-segmentacion-nombre w-full px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border @error('talla') border-red-500 @enderror">
                        @error('talla') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="flex items-center gap-1.5 mb-1 font-medium text-gray-700 dark:text-gray-200">
                            <span>Palabras exigidas <span class="text-red-500">*</span></span>
                            <button type="button"
                                class="tooltip-btn inline-flex items-center justify-center w-5 h-5 rounded-full bg-gray-300 dark:bg-gray-600 text-gray-800 dark:text-gray-100 text-xs font-bold hover:bg-gray-400 dark:hover:bg-gray-500 focus:outline-none"
                                aria-label="Ayuda sobre palabras exigidas"
                                data-tooltip='Estas palabras mínimas deberán coincidir con el titulo del producto de amazon, para poder ser guardada en el sistema (Utilizar las mínimas indispensables) Ej Gigabyte GeForce RTX 9060 XT, poner tan solo "Gigabyte 9060 XT"'>?</button>
                        </label>
                        <div class="kp-segmentacion-palabras-wrap hidden mb-1.5 flex flex-wrap gap-1.5" aria-live="polite"></div>
                        <input type="text" id="input_palabras_exigidas" name="palabras_exigidas" required
                            value="{{ old('palabras_exigidas', $producto->palabras_exigidas ?? '') }}"
                            class="kp-campo-segmentacion-nombre w-full px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border @error('palabras_exigidas') border-red-500 @enderror"
                            placeholder="Ej: Gigabyte 9060 XT">
                        @error('palabras_exigidas') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="md:col-span-2 flex gap-2">
                        <button type="button" id="rellenar-info-automatica" disabled
                            class="bg-green-600 hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed text-white font-semibold px-4 py-2 rounded-md transition duration-150 ease-in-out focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                            title="Selecciona una categoría antes de rellenar automáticamente">
                            <span id="btn-text">Rellenar información automáticamente</span>
                            <span id="btn-loading" class="hidden">
                                <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Procesando...
                            </span>
                        </button>
                        <button type="button" id="ver-prompt" 
                            class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-4 py-2 rounded-md transition duration-150 ease-in-out focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Ver prompt
                        </button>
                        <div id="progreso-info" class="mt-2 text-sm text-gray-600 dark:text-gray-400 hidden">
                            <div id="progreso-texto">Iniciando proceso...</div>
                        </div>
                    </div>

                    @php
                        $esProductoNuevo = empty($producto?->id);
                        $precioValorFormulario = old('precio', $esProductoNuevo ? 0 : ($producto->precio ?? ''));
                    @endphp
                    <div class="md:col-span-2">
                        <div class="flex flex-wrap items-end gap-6">
                            <div class="w-32 shrink-0">
                                <label class="block mb-1 font-medium text-gray-700 dark:text-gray-200">Precio (€)</label>
                                <input type="number" step="0.01" name="precio" value="{{ $precioValorFormulario }}"
                                    class="w-full px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border @error('precio') border-red-500 @enderror">
                                @error('precio') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                            </div>

                            <div class="shrink-0" id="obsoleto-campo-wrap">
                                <div class="flex items-center gap-1.5 mb-1">
                                    <span class="font-medium text-gray-700 dark:text-gray-200">¿Obsoleto? *</span>
                                    <button type="button" id="obsoleto-candado-btn"
                                        class="inline-flex items-center justify-center w-7 h-7 rounded-md border border-red-300 dark:border-red-700 bg-red-50 dark:bg-red-900/30 text-red-600 dark:text-red-400 hover:bg-red-100 dark:hover:bg-red-900/50 transition"
                                        aria-label="Desbloquear campo obsoleto"
                                        title="Candado de control: clic para poder cambiar obsoleto">
                                        <svg id="obsoleto-candado-icono-cerrado" class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                            <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
                                        </svg>
                                        <svg id="obsoleto-candado-icono-abierto" class="w-4 h-4 hidden" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                            <path d="M10 2a5 5 0 00-5 5v2a2 2 0 00-2 2v5a2 2 0 002 2h10a2 2 0 002-2v-5a2 2 0 00-2-2H7V7a3 3 0 115.905-.75A5.002 5.002 0 0010 2z"/>
                                        </svg>
                                    </button>
                                </div>
                                <input type="hidden" name="obsoleto" id="obsoleto-valor-hidden" value="{{ old('obsoleto', $producto?->obsoleto ?? 'no') }}">
                                <div id="obsoleto-radios-wrap" class="flex gap-6 h-[42px] items-center opacity-50 pointer-events-none select-none">
                                    <label class="inline-flex items-center cursor-not-allowed">
                                        <input type="radio" value="no"
                                            {{ old('obsoleto', $producto?->obsoleto ?? 'no') === 'no' ? 'checked' : '' }}
                                            class="obsoleto-radio form-radio text-pink-600">
                                        <span class="ml-2 text-gray-700 dark:text-gray-200">No</span>
                                    </label>
                                    <label class="inline-flex items-center cursor-not-allowed">
                                        <input type="radio" value="si"
                                            {{ old('obsoleto', $producto?->obsoleto ?? 'no') === 'si' ? 'checked' : '' }}
                                            class="obsoleto-radio form-radio text-pink-600">
                                        <span class="ml-2 text-gray-700 dark:text-gray-200">Sí</span>
                                    </label>
                                </div>
                            </div>

                            <div class="shrink-0">
                                <label class="block mb-1 font-medium text-gray-700 dark:text-gray-200">¿Mostrar? *</label>
                                <div class="flex gap-6 h-[42px] items-center">
                                    <label class="inline-flex items-center">
                                        <input type="radio" name="mostrar" value="si"
                                            {{ old('mostrar', $producto->mostrar ?? 'si') === 'si' ? 'checked' : '' }}
                                            class="form-radio text-pink-600">
                                        <span class="ml-2 text-gray-700 dark:text-gray-200">Sí</span>
                                    </label>
                                    <label class="inline-flex items-center">
                                        <input type="radio" name="mostrar" value="no"
                                            {{ old('mostrar', $producto->mostrar ?? 'si') === 'no' ? 'checked' : '' }}
                                            class="form-radio text-pink-600">
                                        <span class="ml-2 text-gray-700 dark:text-gray-200">No</span>
                                    </label>
                                </div>
                            </div>

                            <button type="button" id="btn-toggle-codigos-producto"
                                class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold px-3 py-2 rounded-md shadow transition shrink-0 mb-0.5">
                                <span>Códigos EAN, ISBN…</span>
                                <span id="codigos-producto-badge" class="hidden min-w-[1.25rem] px-1.5 py-0.5 rounded-full bg-white/20 text-xs font-bold text-center"></span>
                            </button>
                        </div>
                        @php
                            $codigosProductoInicial = old('ean_isbn_etc');
                            if ($codigosProductoInicial === null) {
                                $codigosProductoInicial = $producto?->ean_isbn_etc ?? [];
                            }
                            if (is_string($codigosProductoInicial)) {
                                $codigosProductoInicial = json_decode($codigosProductoInicial, true) ?? [];
                            }
                            if (!is_array($codigosProductoInicial)) {
                                $codigosProductoInicial = [];
                            }
                            foreach (['ean', 'isbn', 'upc', 'mpn', 'gtin'] as $tipoCodigoProducto) {
                                if (!isset($codigosProductoInicial[$tipoCodigoProducto]) || !is_array($codigosProductoInicial[$tipoCodigoProducto])) {
                                    $val = $codigosProductoInicial[$tipoCodigoProducto] ?? null;
                                    $codigosProductoInicial[$tipoCodigoProducto] = ($val !== null && $val !== '') ? [(string) $val] : [];
                                }
                            }
                        @endphp
                        <div id="codigos-producto-panel" class="hidden mt-4 w-full rounded-lg border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 p-4 space-y-4">
                            <p class="text-sm text-gray-600 dark:text-gray-300">Códigos globales del producto (EAN, ISBN, UPC, MPN, GTIN) usados para buscar coincidencias en los feeds CSV.</p>
                            @foreach (['ean' => 'EAN', 'isbn' => 'ISBN', 'upc' => 'UPC', 'mpn' => 'MPN', 'gtin' => 'GTIN'] as $tipoCodigo => $etiquetaCodigo)
                                <div class="codigos-tipo-block rounded-md border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 p-3" data-tipo="{{ $tipoCodigo }}">
                                    <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
                                        <h4 class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ $etiquetaCodigo }}</h4>
                                        <button type="button" class="btn-anadir-codigo-producto text-sm text-green-600 hover:text-green-700 font-medium" data-tipo="{{ $tipoCodigo }}">
                                            + Añadir {{ $etiquetaCodigo }}
                                        </button>
                                    </div>
                                    <div class="codigos-lista space-y-2" id="codigos-lista-{{ $tipoCodigo }}">
                                        @foreach ($codigosProductoInicial[$tipoCodigo] ?? [] as $valorCodigo)
                                            <div class="flex items-center gap-2 codigo-item">
                                                <input type="text" maxlength="255"
                                                    class="codigo-valor-input flex-1 px-3 py-2 rounded bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white border border-gray-300 dark:border-gray-600 text-sm font-mono"
                                                    value="{{ $valorCodigo }}"
                                                    placeholder="Código {{ $etiquetaCodigo }}">
                                                <button type="button" class="btn-eliminar-codigo-producto px-3 py-2 bg-red-600 hover:bg-red-700 text-white rounded text-sm transition-colors shrink-0" title="Eliminar código">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                    </svg>
                                                </button>
                                            </div>
                                        @endforeach
                                    </div>
                                    <p class="codigos-lista-vacia text-xs text-gray-500 dark:text-gray-400 mt-1 {{ count($codigosProductoInicial[$tipoCodigo] ?? []) > 0 ? 'hidden' : '' }}">Sin códigos {{ $etiquetaCodigo }}.</p>
                                </div>
                            @endforeach
                        </div>
                        <input type="hidden" name="ean_isbn_etc" id="ean_isbn_etc_input"
                            value="{{ old('ean_isbn_etc', is_array($codigosProductoInicial) ? json_encode($codigosProductoInicial, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '') }}">
                        @error('ean_isbn_etc')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                </div>
            </fieldset>

            {{-- ASOCIAR CATEGORÍA PARA ESPECIFICACIONES INTERNAS --}}
            <fieldset class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6 space-y-6 border border-gray-200 dark:border-gray-700">
                <legend class="text-lg font-semibold text-gray-700 dark:text-gray-200">CATEGORIA - especificaciones internas</legend>

                {{-- Buscador de categoría para especificaciones internas --}}
                <div class="mb-6">
                    <label class="block mb-2 font-medium text-gray-700 dark:text-gray-200">Categoría para especificaciones internas</label>
                    <div class="relative">
                        <input type="hidden" name="categoria_id_especificaciones_internas" id="categoria_especificaciones_id" value="{{ old('categoria_id_especificaciones_internas', $producto->categoria_id_especificaciones_internas ?? '') }}">
                        <input type="text" id="categoria_especificaciones_nombre"
                            value="{{ old('categoria_especificaciones_nombre', $categoriaEspecificacionesNombre ?? '') }}"
                            class="w-full px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border focus:outline-none focus:ring-2 focus:ring-blue-500"
                            placeholder="Escribe para buscar categorías..."
                            autocomplete="off">
                        <div id="categoria_especificaciones_sugerencias" class="absolute z-50 w-full mt-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md shadow-lg hidden max-h-60 overflow-y-auto"></div>
                    </div>
                    <p class="text-sm text-gray-500 mt-1">Selecciona una categoría para asociar las especificaciones internas. Por defecto se muestra la última categoría de la jerarquía del producto.</p>
                    @error('categoria_id_especificaciones_internas')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Checkbox para no añadir especificaciones internas --}}
                <div class="mb-4">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="no_anadir_especificaciones" id="no_anadir_especificaciones" value="1" class="rounded border-gray-300 text-red-600 focus:ring-red-500">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-200">No añadir especificaciones internas</span>
                    </label>
                </div>

                {{-- Contenedor para mostrar las especificaciones internas de la categoría seleccionada --}}
                <div id="especificaciones-internas-seleccion" class="mt-4 hidden">
                    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 border border-gray-200 dark:border-gray-600 space-y-4">
                        <div id="especificaciones-internas-contenido"></div>
                    </div>
                </div>
                
                <input type="hidden" name="categoria_especificaciones_internas_elegidas" id="categoria_especificaciones_internas_elegidas_input" value="{{ old('categoria_especificaciones_internas_elegidas', $producto && $producto->categoria_especificaciones_internas_elegidas ? json_encode($producto->categoria_especificaciones_internas_elegidas, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '') }}">
            </fieldset>

            {{-- ESPECIFICACIONES INTERNAS DEL PRODUCTO --}}
            <fieldset class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6 space-y-6 border border-gray-200 dark:border-gray-700">
                <legend class="text-lg font-semibold text-gray-700 dark:text-gray-200">ESPECIFICACIONES INTERNAS DEL PRODUCTO</legend>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Puedes añadir líneas principales y sublíneas específicas de este producto. Estas se guardarán junto con las especificaciones de la categoría.</p>
                
                <div id="especificaciones-producto-container" class="space-y-4">
                    <!-- Las líneas principales específicas del producto se generarán dinámicamente aquí -->
                </div>
                
                <button type="button" id="btn-añadir-linea-producto" class="bg-green-600 hover:bg-green-700 text-white font-semibold px-4 py-2 rounded shadow">
                    + Añadir línea principal
                </button>
            </fieldset>

            {{-- IMÁGENES --}}
            <fieldset class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6 space-y-4 border border-gray-200 dark:border-gray-700">
                <legend class="text-lg font-semibold text-gray-700 dark:text-gray-200">Imágenes</legend>

                @php
                    // Convertir imágenes existentes a formato JSON si es necesario
                    $imagenesGrandesRaw = old('imagenes_grandes', $producto && $producto->imagen_grande ? (is_array($producto->imagen_grande) ? $producto->imagen_grande : [$producto->imagen_grande]) : []);
                    $imagenesPequenasRaw = old('imagenes_pequenas', $producto && $producto->imagen_pequena ? (is_array($producto->imagen_pequena) ? $producto->imagen_pequena : [$producto->imagen_pequena]) : []);
                    // Tras error de validación, old() devuelve el JSON como string; normalizar a array
                    $imagenesGrandes = is_string($imagenesGrandesRaw) ? (json_decode($imagenesGrandesRaw, true) ?? []) : $imagenesGrandesRaw;
                    $imagenesPequenas = is_string($imagenesPequenasRaw) ? (json_decode($imagenesPequenasRaw, true) ?? []) : $imagenesPequenasRaw;
                    $imagenesGrandes = is_array($imagenesGrandes) ? $imagenesGrandes : [];
                    $imagenesPequenas = is_array($imagenesPequenas) ? $imagenesPequenas : [];
                @endphp

                <!-- Fila de imágenes existentes -->
                <div id="imagenes-container" class="flex flex-wrap gap-3 items-start">
                    <!-- Las miniaturas se generarán dinámicamente aquí -->
                </div>
                <style>
                    .writing-vertical {
                        writing-mode: vertical-rl;
                        text-orientation: mixed;
                        transform: rotate(180deg);
                    }
                </style>

                <!-- Botón para añadir nuevas imágenes -->
                <div class="mt-4">
                    <button type="button" id="btn-añadir-imagen" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-4 py-2 rounded-md transition duration-150 ease-in-out">
                        + Añadir imagen
                    </button>
                </div>

                <!-- Campos ocultos para guardar las imágenes en formato JSON -->
                <input type="hidden" id="imagenes-grandes-json" name="imagenes_grandes" value="{{ json_encode($imagenesGrandes, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}">
                <input type="hidden" id="imagenes-pequenas-json" name="imagenes_pequenas" value="{{ json_encode($imagenesPequenas, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}">
                
                @error('imagenes_grandes') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                @error('imagenes_pequenas') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
            </fieldset>

            {{-- PRODUCTOS RELACIONADOS --}}
            <fieldset class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6 space-y-6 border border-gray-200 dark:border-gray-700">
                <legend class="text-lg font-semibold text-gray-700 dark:text-gray-200">Productos relacionados</legend>

                {{-- Buscador de categoría para productos relacionados --}}
                <div class="mb-6">
                    <label class="block mb-2 font-medium text-gray-700 dark:text-gray-200">Categoría para productos relacionados</label>
                    <div class="relative">
                        <input type="hidden" name="id_categoria_productos_relacionados" id="categoria_relacionados_id" value="{{ old('id_categoria_productos_relacionados', $producto->id_categoria_productos_relacionados ?? '') }}">
                        <input type="text" id="categoria_relacionados_nombre"
                            value="{{ old('categoria_relacionados_nombre', $categoriaRelacionadosNombre ?? '') }}"
                            class="w-full px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border focus:outline-none focus:ring-2 focus:ring-blue-500"
                            placeholder="Escribe para buscar categorías..."
                            autocomplete="off">
                        <div id="categoria_relacionados_sugerencias" class="absolute z-50 w-full mt-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md shadow-lg hidden max-h-60 overflow-y-auto"></div>
                    </div>
                    <p class="text-sm text-gray-500 mt-1">Selecciona una categoría para buscar productos relacionados en esa categoría y sus subcategorías</p>
                    @error('id_categoria_productos_relacionados')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Contenedor para mostrar palabras clave relacionadas --}}
                <div id="palabras-clave-relacionadas-container" class="mb-6 hidden">
                    <div class="flex flex-wrap items-center gap-3 mb-2">
                        <label for="palabras-clave-relacionadas-filtro" class="font-medium text-gray-700 dark:text-gray-200 shrink-0">Palabras clave disponibles</label>
                        <textarea id="palabras-clave-relacionadas-filtro" rows="1"
                            class="flex-1 min-w-[12rem] px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white border border-gray-300 dark:border-gray-600 text-sm leading-normal focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none overflow-hidden h-[42px]"
                            placeholder="Escribe para filtrar palabras clave..."
                            autocomplete="off"></textarea>
                    </div>
                    <div id="palabras-clave-relacionadas-botones" class="flex flex-wrap gap-2">
                        <!-- Los botones se generarán dinámicamente aquí -->
                    </div>
                    <p class="text-xs text-gray-500 mt-2">Haz clic en una palabra clave para añadirla o quitarla. Verde = ya está añadida, Gris = no está añadida. El número indica cuántos productos la tienen.</p>
                </div>

                <div id="relacionados-list">
                    <div id="form-producto" data-producto-id="{{ $producto->id ?? '' }}"></div>
                    @php $relacionados = old('keys_relacionados', $producto->keys_relacionados ?? ['']); @endphp
                    @foreach ($relacionados as $index => $relacionado)
                    <div class="flex items-center gap-2 mb-2 relacionado-item">
                        <input type="text" name="keys_relacionados[]" value="{{ $relacionado }}"
                            class="flex-1 px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border">
                        <button type="button" class="btn-eliminar-relacionado px-3 py-2 bg-red-600 hover:bg-red-700 text-white rounded text-sm transition-colors" title="Eliminar esta palabra clave">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    @endforeach
                </div>

                <div class="flex gap-4">
                    <button type="button" id="add-relacionado" class="text-sm text-green-600 font-medium">+ Añadir relacionado</button>
                </div>
                <div class="mt-4">
                    <button type="button" id="buscar-relacionados" class="text-sm bg-blue-600 hover:bg-blue-700 text-white font-semibold px-4 py-2 rounded">
                        Buscar productos relacionados
                    </button>
                    <span id="relacionados-resultado" class="ml-4 text-gray-800 dark:text-gray-200 text-sm"></span>
                </div>
            </fieldset>

            {{-- NEO (URLs objetivo) --}}
            <fieldset class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6 space-y-6 border border-gray-200 dark:border-gray-700">
                <legend class="text-lg font-semibold text-gray-700 dark:text-gray-200">Neo</legend>
                <p class="text-sm text-gray-500 dark:text-gray-400">URLs objetivo para este producto (opcional). Si rellenas un campo, debe contener una URL válida o "No encontrado". La fecha indica cuándo se visitó.</p>
                <div id="neoobjetivo-list" class="mt-3">
                    @php
                        $neoobjetivos = old('neoobjetivo', $producto ? $producto->neoobjetivos : []);
                        if (empty($neoobjetivos)) {
                            $neoobjetivos = [ (object)['id' => null, 'url' => '', 'visitada' => ''] ];
                        }
                    @endphp
                    @foreach ($neoobjetivos as $index => $neo)
                    @php $neoIdMostrar = is_object($neo) ? ($neo->id ?? null) : ($neo['id'] ?? null); @endphp
                    <div class="flex items-center gap-2 mb-2 neoobjetivo-item flex-wrap">
                        <input type="hidden" name="neoobjetivo[{{ $index }}][id]" value="{{ $neoIdMostrar ?? '' }}">
                        <span class="neoobjetivo-id-label text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap font-mono {{ $neoIdMostrar ? '' : 'hidden' }}" title="ID neoobjetivo">#{{ $neoIdMostrar ?: '' }}</span>
                        <input type="text" name="neoobjetivo[{{ $index }}][url]" value="{{ is_object($neo) ? ($neo->url ?? '') : ($neo['url'] ?? '') }}"
                            class="neoobjetivo-url flex-1 min-w-[200px] px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border border-gray-300 dark:border-gray-600"
                            placeholder="https://...">
                        <input type="datetime-local" name="neoobjetivo[{{ $index }}][visitada]" value="{{ is_object($neo) && $neo->visitada ? \Carbon\Carbon::parse($neo->visitada)->format('Y-m-d\TH:i') : (is_array($neo) && !empty($neo['visitada']) ? (\Carbon\Carbon::parse($neo['visitada'])->format('Y-m-d\TH:i') ?? $neo['visitada']) : '') }}"
                            class="neoobjetivo-visitada w-44 px-3 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border border-gray-300 dark:border-gray-600">
                        <button type="button" class="btn-eliminar-neoobjetivo px-3 py-2 bg-red-600 hover:bg-red-700 text-white rounded text-sm transition-colors" title="Eliminar esta URL">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    @endforeach
                </div>
                <div class="flex gap-4">
                    <button type="button" id="add-neoobjetivo" class="text-sm text-green-600 font-medium">+ Añadir URL Neo</button>
                </div>
            </fieldset>

            {{-- GESTIÓN DE GRUPOS DE OFERTAS --}}
            @if($producto && $producto->unidadDeMedida === 'unidadUnica')
            <fieldset class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6 space-y-6 border border-gray-200 dark:border-gray-700">
                <legend class="text-lg font-semibold text-gray-700 dark:text-gray-200">Grupos de ofertas</legend>
                
                <div id="grupos-ofertas-producto-container" class="space-y-4">
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Gestiona los grupos de ofertas para este producto. Las ofertas agrupadas se mostrarán unificadas en una sola fila en la vista pública.
                    </p>

                    {{-- Contenedor de grupos en cuadrados --}}
                    <div id="grupos-lista-producto" class="flex flex-wrap gap-4">
                        <p class="text-sm text-gray-500 dark:text-gray-400 text-center py-4">Cargando grupos...</p>
                    </div>

                    {{-- Listado de ofertas del grupo seleccionado --}}
                    <div id="ofertas-grupo-seleccionado-producto" class="hidden mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-200 mb-4">Ofertas del grupo seleccionado</h3>
                        <div id="ofertas-grupo-lista-producto" class="space-y-2">
                            {{-- Las ofertas se cargarán aquí --}}
                        </div>
                    </div>
                </div>
            </fieldset>
            @endif

            {{-- INFORMACION PRODUCTO --}}

            <fieldset class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6 space-y-6 border border-gray-200 dark:border-gray-700">
                <legend class="text-lg font-semibold text-gray-700 dark:text-gray-200">Contenido y características</legend>

                <div class="space-y-4">
                    <div>
                        <label class="block mb-1 font-medium text-gray-700 dark:text-gray-200">Título *</label>
                        <input type="text" name="titulo" value="{{ old('titulo', $producto->titulo ?? '') }}"
                            class="w-full px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border @error('titulo') border-red-500 @enderror">
                        @error('titulo') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block mb-1 font-medium text-gray-700 dark:text-gray-200">Subtítulo</label>
                        <input type="text" name="subtitulo" value="{{ old('subtitulo', $producto->subtitulo ?? '') }}"
                            class="w-full px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border @error('subtitulo') border-red-500 @enderror">
                        @error('subtitulo') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block mb-1 font-medium text-gray-700 dark:text-gray-200">Descripción corta</label>
                        <textarea name="descripcion_corta" rows="3"
                            class="w-full px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border @error('descripcion_corta') border-red-500 @enderror">{{ old('descripcion_corta', $producto->descripcion_corta ?? '') }}</textarea>
                        @error('descripcion_corta') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block mb-1 font-medium text-gray-700 dark:text-gray-200">Descripción larga</label>
                        <textarea name="descripcion_larga" rows="5"
                            class="w-full px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border @error('descripcion_larga') border-red-500 @enderror">{{ old('descripcion_larga', $producto->descripcion_larga ?? '') }}</textarea>
                        @error('descripcion_larga') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block mb-1 font-medium text-gray-700 dark:text-gray-200">Características (una por línea)</label>
                        <textarea name="caracteristicas" rows="4"
                            class="w-full px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border @error('caracteristicas') border-red-500 @enderror">{{ is_array(old('caracteristicas', $producto->caracteristicas ?? [])) ? implode("\n", old('caracteristicas', $producto->caracteristicas ?? [])) : old('caracteristicas', '') }}</textarea>
                        @error('caracteristicas') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
            </fieldset>

            {{-- PROS Y CONTRAS --}}
            <fieldset class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6 space-y-6 border border-gray-200 dark:border-gray-700">
                <legend class="text-lg font-semibold text-gray-700 dark:text-gray-200">Pros y contras</legend>

                <div>
                    <label class="block mb-2 font-medium text-gray-700 dark:text-gray-200">Pros *</label>
                    <div id="pros-list">
                        @php $pros = old('pros', $producto->pros ?? ['']); @endphp
                        @foreach ($pros as $pro)
                        <input type="text" name="pros[]" value="{{ $pro }}"
                            class="w-full mb-2 px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border">
                        @endforeach
                    </div>
                    <div class="flex gap-4">
                        <button type="button" id="add-pro" class="text-sm text-green-600 font-medium">+ Añadir pro</button>
                        <button type="button" id="remove-pro" class="text-sm text-red-600 font-medium">– Quitar pro</button>
                    </div>
                </div>

                <div>
                    <label class="block mb-2 font-medium text-gray-700 dark:text-gray-200">Contras *</label>
                    <div id="contras-list">
                        @php $contras = old('contras', $producto->contras ?? ['']); @endphp
                        @foreach ($contras as $contra)
                        <input type="text" name="contras[]" value="{{ $contra }}"
                            class="w-full mb-2 px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border">
                        @endforeach
                    </div>
                    <div class="flex gap-4">
                        <button type="button" id="add-contra" class="text-sm text-green-600 font-medium">+ Añadir contra</button>
                        <button type="button" id="remove-contra" class="text-sm text-red-600 font-medium">– Quitar contra</button>
                    </div>
                </div>
            </fieldset>

            {{-- FAQ --}}

            <fieldset class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6 space-y-6 border border-gray-200 dark:border-gray-700">
                <legend class="text-lg font-semibold text-gray-700 dark:text-gray-200">Preguntas frecuentes</legend>

                <div id="faq-list" class="space-y-4">
                    @php $faqs = old('faq', $producto->faq ?? [['pregunta' => '', 'respuesta' => '']]); @endphp
                    @foreach ($faqs as $index => $faq)
                    <div class="faq-item grid grid-cols-1 md:grid-cols-2 gap-4">
                        <input type="text" name="faq[{{ $index }}][pregunta]" placeholder="Pregunta"
                            value="{{ $faq['pregunta'] }}"
                            class="w-full px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border">
                        <input type="text" name="faq[{{ $index }}][respuesta]" placeholder="Respuesta"
                            value="{{ $faq['respuesta'] }}"
                            class="w-full px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border">
                    </div>
                    @endforeach
                </div>

                <div class="flex gap-4">
                    <button type="button" id="add-faq" class="text-sm text-green-600 font-medium">+ Añadir FAQ</button>
                    <button type="button" id="remove-faq" class="text-sm text-red-600 font-medium">– Quitar FAQ</button>
                </div>
            </fieldset>

            {{-- INFORMACION META --}}

            <fieldset class=" bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6 space-y-6 border border-gray-200 dark:border-gray-700">
                <legend class="text-lg font-semibold text-gray-700 dark:text-gray-200">SEO</legend>

                <div class="space-y-4">
                <div>
                        <label class="block mb-1 font-medium text-gray-700 dark:text-gray-200">Meta título</label>
                        <textarea name="meta_titulo" rows="1"
                            class="w-full px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border @error('meta_titulo') border-red-500 @enderror">{{ old('meta_description', $producto->meta_description ?? '') }}</textarea>
                        @error('meta_titulo') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block mb-1 font-medium text-gray-700 dark:text-gray-200">Meta descripción</label>
                        <textarea name="meta_description" rows="3"
                            class="w-full px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border @error('meta_description') border-red-500 @enderror">{{ old('meta_description', $producto->meta_description ?? '') }}</textarea>
                        @error('meta_description') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
            </fieldset>

            {{-- SISTEMA DE AVISOS --}}
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6 space-y-4 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Avisos</h3>
                    <button type="button" onclick="mostrarModalNuevoAviso()" 
                        class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-pink-600 hover:bg-pink-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-pink-500">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        Añadir Aviso
                    </button>
                </div>

                {{-- Lista de avisos existentes --}}
                <div id="lista-avisos" class="space-y-3">
                    {{-- Los avisos se cargarán dinámicamente aquí --}}
                </div>

                {{-- Mensaje cuando no hay avisos --}}
                <div id="sin-avisos" class="text-center py-4 text-gray-500 dark:text-gray-400">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                    <p class="mt-2 text-sm">No hay avisos configurados</p>
                </div>
            </div>

                        {{-- ANOTACIONES INTERNAS --}}
            <div>
                <label class="block mb-1 font-medium text-gray-700 dark:text-gray-200">Anotaciones internas</label>
                <textarea name="anotaciones_internas"
                    class="w-full px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border"
                    rows="4">{{ old('anotaciones_internas', $producto->anotaciones_internas ?? '') }}</textarea>
            </div>

            <div class="flex justify-between items-center mt-8">
                {{-- AÑADIR OFERTA --}}
                @if ($producto && $producto->id)
                <a href="{{ route('admin.ofertas.create', ['producto' => $producto->id]) }}"
                    target="_blank"
                    class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-6 py-3 rounded-md shadow-md inline-block text-center">
                    + Añadir oferta
                </a>
                @endif

                {{-- BOTÓN GUARDAR --}}
                <div id="btn_guardar_wrapper" class="ml-auto">
                    <button type="submit" id="btn_guardar"
                        class="inline-flex items-center bg-pink-600 hover:bg-pink-700 text-white font-semibold text-base px-6 py-3 rounded-md shadow-lg transition duration-150 ease-in-out focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-pink-500 disabled:opacity-50 disabled:cursor-not-allowed">
                        Guardar producto
                    </button>
                </div>
            </div>


        </form>

        {{-- Barra nombre fija (viewport completo al hacer scroll) --}}
        <div id="barra-nombre-fija" class="hidden fixed top-0 left-0 right-0 z-40 border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-md" aria-hidden="true">
            <div class="max-w-5xl mx-auto px-4 py-3">
                <label for="input_nombre_fijo" class="block mb-1 font-medium text-gray-700 dark:text-gray-200">Nombre *</label>
                <input type="text" id="input_nombre_fijo" autocomplete="off"
                    class="w-full px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-pink-500">
            </div>
        </div>

        {{-- Botón guardar flotante (cuando el original no está en pantalla) --}}
        <button type="button" id="btn_guardar_fijo" class="hidden fixed bottom-4 right-4 z-40 inline-flex items-center bg-pink-600 hover:bg-pink-700 text-white font-semibold text-base px-6 py-3 rounded-md shadow-lg transition duration-150 ease-in-out focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-pink-500 disabled:opacity-50 disabled:cursor-not-allowed">
            Guardar producto
        </button>

            {{-- Modal para crear/editar aviso --}}
            <div id="modal-aviso" class="hidden fixed inset-0 bg-transparent flex items-center justify-center z-50 p-4">
                <div class="relative bg-gray-800 dark:bg-gray-800 border border-pink-600/70 rounded-lg p-6 max-w-md w-full shadow-xl">
                    <div class="flex items-start justify-between mb-4">
                        <h2 id="modal-titulo" class="text-lg font-semibold text-gray-200 dark:text-gray-200">Nuevo Aviso</h2>
                        <button type="button" onclick="cerrarModalAviso()" aria-label="Cerrar"
                            class="ml-4 text-gray-300 hover:text-white transition-colors text-2xl leading-none">
                            ×
                        </button>
                    </div>
                    <div id="form-aviso-container">
                        <input type="hidden" id="aviso-id" name="aviso_id">
                        <input type="hidden" id="avisoable-type" name="avisoable_type" value="App\Models\Producto">
                        <input type="hidden" id="avisoable-id" name="avisoable_id" value="{{ $producto ? $producto->id : 'null' }}">
                        
                        <div class="mb-4">
                            <label for="texto-aviso" class="block text-sm font-medium text-gray-300 dark:text-gray-300 mb-2">Texto del aviso</label>
                            <div class="flex gap-2 mb-2">
                                <button type="button" onclick="rellenarTextoAviso('Sin stock - 1a vez')" 
                                    class="px-3 py-1 bg-orange-600 hover:bg-orange-700 text-white text-xs md:text-sm rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500 transition">
                                    Sin stock
                                </button>
                                <button type="button" onclick="rellenarTextoAviso('404 - 1a vez')" 
                                    class="px-3 py-1 bg-red-600 hover:bg-red-700 text-white text-xs md:text-sm rounded-md focus:outline-none focus:ring-2 focus:ring-red-500 transition">
                                    404
                                </button>
                            </div>
                            <textarea id="texto-aviso" name="texto_aviso" rows="3" 
                                class="w-full px-3 py-2 border border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200" 
                                placeholder="Escribe el texto del aviso..." required></textarea>
                        </div>
                        
                        <div class="mb-4">
                            <label for="fecha-aviso" class="block text-sm font-medium text-gray-300 dark:text-gray-300 mb-2">Fecha y hora</label>
                            <div class="flex flex-wrap gap-2 mb-3" data-botones-fecha-rapida>
                                <button type="button" class="px-3 py-1 bg-gray-700 hover:bg-gray-600 text-white text-xs md:text-sm rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 transition" data-dias="1">1 día</button>
                                <button type="button" class="px-3 py-1 bg-gray-700 hover:bg-gray-600 text-white text-xs md:text-sm rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 transition" data-dias="4">4 días</button>
                                <button type="button" class="px-3 py-1 bg-gray-700 hover:bg-gray-600 text-white text-xs md:text-sm rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 transition" data-dias="7">7 días</button>
                                <button type="button" class="px-3 py-1 bg-gray-700 hover:bg-gray-600 text-white text-xs md:text-sm rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 transition" data-dias="14">14 días</button>
                                <button type="button" class="px-3 py-1 bg-gray-700 hover:bg-gray-600 text-white text-xs md:text-sm rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 transition" data-dias="90">3 meses</button>
                            </div>
                            <input type="datetime-local" id="fecha-aviso" name="fecha_aviso" 
                                class="w-full px-3 py-2 border border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200" 
                                required>
                        </div>
                        
                        <div class="mb-4">
                            <div class="flex items-center">
                                <input type="checkbox" id="oculto" name="oculto" class="rounded border-gray-300 text-pink-600 focus:ring-pink-500">
                                <span class="ml-2 text-sm text-gray-300 dark:text-gray-300">Ocultar aviso</span>
                            </div>
                            <p class="text-xs text-gray-400 dark:text-gray-400 mt-1">Los avisos ocultos no aparecen en las pestañas principales pero se mantienen para futuras comprobaciones</p>
                        </div>
                        
                        <div class="flex justify-end space-x-3">
                            <button type="button" onclick="cerrarModalAviso()" 
                                class="px-4 py-2 text-sm font-medium text-gray-300 bg-gray-600 rounded-md hover:bg-gray-500 dark:bg-gray-600 dark:text-gray-300 dark:hover:bg-gray-500">
                                Cancelar
                            </button>
                            <button type="button" onclick="guardarAviso()" 
                                class="px-4 py-2 text-sm font-medium text-white bg-pink-600 rounded-md hover:bg-pink-700">
                                Guardar
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <script>
            let avisosActuales = [];
            let modoEdicion = false;
            let avisoEditando = null;

            // Cargar avisos al cargar la página (solo para mostrar los existentes)
            document.addEventListener('DOMContentLoaded', function() {
                cargarAvisos();
                inicializarBotonesFechaRapidaAviso();
            });

            // Función para cargar avisos
            function cargarAvisos() {
                const avisoableType = document.getElementById('avisoable-type').value;
                const avisoableId = document.getElementById('avisoable-id').value;
                
                // Si no hay ID (elemento nuevo), no cargar avisos
                if (!avisoableId || avisoableId === 'null') {
                    avisosActuales = [];
                    mostrarAvisos();
                    return;
                }
                
                fetch(`/panel-privado/avisos/elemento?avisoable_type=${avisoableType}&avisoable_id=${avisoableId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            avisosActuales = data.avisos;
                            mostrarAvisos();
                        }
                    })
                    .catch(error => {
                        console.error('Error al cargar avisos:', error);
                    });
            }

            // Función para mostrar avisos
            function mostrarAvisos() {
                const listaAvisos = document.getElementById('lista-avisos');
                const sinAvisos = document.getElementById('sin-avisos');
                
                if (avisosActuales.length === 0) {
                    listaAvisos.innerHTML = '';
                    sinAvisos.classList.remove('hidden');
                    return;
                }
                
                sinAvisos.classList.add('hidden');
                
                listaAvisos.innerHTML = avisosActuales.map(aviso => `
                    <div class="flex items-center justify-between p-3 bg-gray-700 dark:bg-gray-700 rounded-lg">
                        <div class="flex-1">
                            <p class="text-sm text-gray-200 dark:text-gray-200">${aviso.texto_aviso}</p>
                            <p class="text-xs text-gray-400 dark:text-gray-400 mt-1">
                                ${new Date(aviso.fecha_aviso).toLocaleString('es-ES')} - ${aviso.user.name}
                                ${aviso.oculto ? '<span class="ml-2 px-2 py-1 bg-gray-600 text-gray-200 text-xs rounded">Oculto</span>' : ''}
                            </p>
                        </div>
                        <div class="flex space-x-2 ml-4">
                            <button type="button" onclick="editarAviso(${aviso.id})" 
                                class="text-blue-400 hover:text-blue-300 dark:text-blue-400 dark:hover:text-blue-300">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                            </button>
                            <button type="button" onclick="eliminarAviso(${aviso.id})" 
                                class="text-red-400 hover:text-red-300 dark:text-red-400 dark:hover:text-red-300">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                `).join('');
            }

            // Función para mostrar modal de nuevo aviso
            function mostrarModalNuevoAviso() {
                modoEdicion = false;
                avisoEditando = null;
                
                document.getElementById('modal-titulo').textContent = 'Nuevo Aviso';
                document.getElementById('aviso-id').value = '';
                document.getElementById('texto-aviso').value = '';
                document.getElementById('oculto').checked = false;
                
                // Establecer fecha por defecto (mañana a las 00:01)
                const tomorrow = new Date();
                tomorrow.setDate(tomorrow.getDate() + 1);
                tomorrow.setHours(0, 1, 0, 0);
                document.getElementById('fecha-aviso').value = tomorrow.toISOString().slice(0, 16);
                
                document.getElementById('modal-aviso').classList.remove('hidden');
            }

            // Función para editar aviso
            function editarAviso(avisoId) {
                const aviso = avisosActuales.find(a => a.id === avisoId);
                if (!aviso) return;
                
                modoEdicion = true;
                avisoEditando = aviso;
                
                document.getElementById('modal-titulo').textContent = 'Editar Aviso';
                document.getElementById('aviso-id').value = aviso.id;
                document.getElementById('texto-aviso').value = aviso.texto_aviso;
                document.getElementById('fecha-aviso').value = aviso.fecha_aviso.slice(0, 16);
                document.getElementById('oculto').checked = aviso.oculto;
                
                document.getElementById('modal-aviso').classList.remove('hidden');
            }

            // Función para cerrar modal
            function cerrarModalAviso() {
                document.getElementById('modal-aviso').classList.add('hidden');
            }

            // Función para rellenar texto del aviso automáticamente
            function rellenarTextoAviso(texto) {
                const textarea = document.getElementById('texto-aviso');
                if (textarea) {
                    textarea.value = texto;
                    // Enfocar el textarea después de rellenar
                    textarea.focus();
                }
            }

            // Función para guardar aviso
            function guardarAviso() {
                const avisoableId = document.getElementById('avisoable-id').value;
                
                // Verificar que el elemento existe antes de crear el aviso
                if (!avisoableId || avisoableId === 'null') {
                    alert('Debes guardar el elemento primero antes de crear avisos');
                    return;
                }
                
                const textoAviso = document.getElementById('texto-aviso').value.trim();
                const fechaAviso = document.getElementById('fecha-aviso').value;
                const oculto = document.getElementById('oculto').checked;
                
                if (!textoAviso || !fechaAviso) {
                    alert('Por favor, completa todos los campos');
                    return;
                }
                
                const url = modoEdicion ? `/panel-privado/avisos/${avisoEditando.id}` : '/panel-privado/avisos';
                const method = modoEdicion ? 'PUT' : 'POST';
                
                const data = {
                    texto_aviso: textoAviso,
                    fecha_aviso: fechaAviso,
                    avisoable_type: document.getElementById('avisoable-type').value,
                    avisoable_id: avisoableId,
                    oculto: oculto
                };
                
                fetch(url, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify(data)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        cerrarModalAviso();
                        cargarAvisos();
                        // Mostrar notificación de éxito
                        mostrarNotificacion('Aviso guardado correctamente', 'success');
                    } else {
                        alert('Error al guardar el aviso: ' + (data.message || 'Error desconocido'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al guardar el aviso');
                });
            }

            // Función para eliminar aviso
            function eliminarAviso(avisoId) {
                if (!confirm('¿Estás seguro de que quieres eliminar este aviso?')) {
                    return;
                }
                
                fetch(`/panel-privado/avisos/${avisoId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        cargarAvisos();
                        mostrarNotificacion('Aviso eliminado correctamente', 'success');
                    } else {
                        alert('Error al eliminar el aviso');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al eliminar el aviso');
                });
            }

            // Función para mostrar notificaciones
            function mostrarNotificacion(mensaje, tipo = 'info') {
                const notificacion = document.createElement('div');
                notificacion.className = `fixed top-4 right-4 p-4 rounded-lg shadow-lg z-50 max-w-sm ${
                    tipo === 'success' ? 'bg-green-500 text-white' :
                    tipo === 'error' ? 'bg-red-500 text-white' :
                    'bg-blue-500 text-white'
                }`;
                notificacion.textContent = mensaje;

                document.body.appendChild(notificacion);

                setTimeout(() => {
                    if (notificacion.parentNode) {
                        notificacion.parentNode.removeChild(notificacion);
                    }
                }, 3000);
            }

            // Cerrar modal con Escape
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    cerrarModalAviso();
                }
            });

            function formatearFechaLocal(date) {
                const pad = (num) => String(num).padStart(2, '0');
                return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
            }

            function inicializarBotonesFechaRapidaAviso() {
                const contenedor = document.querySelector('[data-botones-fecha-rapida]');
                const inputFecha = document.getElementById('fecha-aviso');

                if (!contenedor || !inputFecha || contenedor.dataset.inicializado === 'true') {
                    return;
                }

                contenedor.dataset.inicializado = 'true';

                contenedor.querySelectorAll('button[data-dias]').forEach((boton) => {
                    boton.addEventListener('click', () => {
                        const dias = parseInt(boton.dataset.dias, 10);
                        if (Number.isNaN(dias)) {
                            return;
                        }

                        const fechaBase = new Date();
                        fechaBase.setSeconds(0, 0);
                        fechaBase.setDate(fechaBase.getDate() + dias);

                        inputFecha.value = formatearFechaLocal(fechaBase);
                    });
                });
            }


            </script>



    </div>

    {{-- MODAL PARA AÑADIR OFERTA --}}
    <div id="modalOferta" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center">
        <div class="bg-white dark:bg-gray-900 rounded-lg p-6 max-w-5xl w-full relative shadow-xl overflow-y-auto max-h-[90vh]">
            <button onclick="cerrarModalOferta()" class="absolute top-3 right-4 text-xl text-gray-800 dark:text-gray-100">×</button>
            <div id="contenido-modal-oferta" class="text-center text-gray-700 dark:text-gray-100">Cargando...</div>
        </div>
    </div>

    {{-- MODAL PARA VER IMÁGENES EXISTENTES --}}
    <div id="modalImagenes" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center">
        <div class="bg-white dark:bg-gray-900 rounded-lg p-6 max-w-6xl w-full relative shadow-xl overflow-y-auto max-h-[90vh]">
            <button onclick="cerrarModalImagenes()" class="absolute top-3 right-4 text-xl text-gray-800 dark:text-gray-100 hover:text-gray-600 dark:hover:text-gray-300">×</button>
            <div class="mb-4">
                <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-200">Imágenes en la carpeta: <span id="nombre-carpeta-modal" class="text-blue-600 dark:text-blue-400"></span></h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">Haz clic en una imagen para seleccionarla</p>
            </div>
            <div id="contenido-modal-imagenes" class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                <div class="text-center text-gray-500 dark:text-gray-400">Cargando imágenes...</div>
            </div>
        </div>
    </div>

    {{-- MODAL PARA VER PRODUCTOS DE UNA PALABRA CLAVE --}}
    <div id="modal-productos-palabra-clave" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white dark:bg-gray-900 rounded-lg p-6 max-w-3xl w-full relative shadow-xl max-h-[90vh] flex flex-col">
            <button onclick="cerrarModalProductosPalabraClave()" class="absolute top-3 right-4 text-xl text-gray-800 dark:text-gray-100 hover:text-gray-600 dark:hover:text-gray-300">×</button>
            <div class="mb-4">
                <h3 id="modal-productos-palabra-clave-titulo" class="text-lg font-semibold text-gray-700 dark:text-gray-200">Productos</h3>
            </div>
            <div id="modal-productos-palabra-clave-contenido" class="flex-1 overflow-y-auto">
                <!-- El contenido se cargará dinámicamente -->
            </div>
        </div>
    </div>

    {{-- MODAL PARA EDITAR IMAGEN EXISTENTE --}}
    <div id="modal-editar-imagen" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white dark:bg-gray-900 rounded-lg p-6 max-w-4xl w-full relative shadow-xl">
            <button onclick="cerrarModalEditarImagen()" class="absolute top-3 right-4 text-xl text-gray-800 dark:text-gray-100 hover:text-gray-600 dark:hover:text-gray-300">×</button>
            <div class="mb-4">
                <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-200">Editar imagen</h3>
            </div>
            <div class="mb-4">
                <img id="imagen-grande-editar" src="" alt="Imagen grande" class="max-w-full max-h-96 mx-auto object-contain border rounded">
            </div>
            <div class="space-y-4">
                <div>
                    <label class="block mb-1 font-medium text-gray-700 dark:text-gray-200">Ruta imagen grande</label>
                    <div class="flex gap-2 items-center">
                        <input type="text" id="input-grande-editar" class="flex-1 px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border">
                        <button type="button" id="buscar-imagen-grande-editar" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-3 py-2 rounded">Buscar imagen</button>
                    </div>
                </div>
                <div>
                    <label class="block mb-1 font-medium text-gray-700 dark:text-gray-200">Ruta imagen pequeña</label>
                    <div class="flex gap-2 items-center">
                        <input type="text" id="input-pequena-editar" class="flex-1 px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border">
                        <button type="button" id="buscar-imagen-pequena-editar" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-3 py-2 rounded">Buscar imagen</button>
                    </div>
                </div>
            </div>
            <div class="flex justify-end gap-3 mt-6">
                <button type="button" onclick="cerrarModalEditarImagen()" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">
                    Cancelar
                </button>
                <button type="button" id="btn-guardar-edicion" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700">
                    Guardar
                </button>
            </div>
        </div>
    </div>

    {{-- MODAL PARA AÑADIR NUEVA IMAGEN --}}
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

    <div id="modal-añadir-imagen" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white dark:bg-gray-900 rounded-lg p-6 max-w-4xl w-full relative shadow-xl overflow-y-auto max-h-[90vh]">
            <button onclick="cerrarModalAñadirImagen()" class="absolute top-3 right-4 text-xl text-gray-800 dark:text-gray-100 hover:text-gray-600 dark:hover:text-gray-300">×</button>
            <div class="mb-4">
                <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-200">Añadir nueva imagen</h3>
            </div>
            
            <!-- Pestañas dentro del modal -->
            <div class="kp-modal-img-tabs mb-4" role="tablist" aria-label="Origen de la imagen">
                <nav class="kp-modal-img-tabs__nav">
                    <button type="button" id="tab-url-nueva" class="tab-modal kp-modal-img-tab kp-modal-img-tab--active" role="tab" aria-selected="true">
                        Descargar desde URL
                    </button>
                    <button type="button" id="tab-subir-nueva" class="tab-modal kp-modal-img-tab" role="tab" aria-selected="false">
                        Subir imagen
                    </button>
                    <button type="button" id="tab-amazon-nueva" class="tab-modal kp-modal-img-tab" role="tab" aria-selected="false">
                        Amazon
                    </button>
                    <button type="button" id="tab-interna-nueva" class="tab-modal kp-modal-img-tab" role="tab" aria-selected="false">
                        Interna producto
                    </button>
                    <button type="button" id="tab-interna-global-nueva" class="tab-modal kp-modal-img-tab" role="tab" aria-selected="false">
                        Interna Global
                    </button>
                </nav>
            </div>

            <!-- Contenido pestaña URL -->
            <div id="content-url-nueva" class="tab-content-modal space-y-4">
                <div>
                    <label class="block mb-2 text-sm text-gray-600 dark:text-gray-400">Carpeta</label>
                    <select id="carpeta-url-nueva" class="w-full px-3 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border text-sm">
                        <option value="">Selecciona una carpeta</option>
                    </select>
                </div>
                <div>
                    <label class="block mb-2 text-sm text-gray-600 dark:text-gray-400">URL de la imagen</label>
                    <div class="flex gap-2">
                        <input type="url" id="url-imagen-nueva" class="flex-1 px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border text-sm" placeholder="https://ejemplo.com/imagen.jpg">
                        <button type="button" id="btn-descargar-url-nueva" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm">
                            Descargar
                        </button>
                    </div>
                    <div id="error-url-nueva" class="mt-1 text-sm text-red-500 hidden"></div>
                </div>
                
                <!-- Área de recorte (se mostrará cuando se descargue la URL) -->
                <div id="area-recorte-nueva" class="hidden space-y-4">
                    <div class="mb-4">
                        <h4 class="text-md font-semibold text-gray-700 dark:text-gray-200 mb-2">Recortar imagen</h4>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">Selecciona el área que deseas mantener.</p>
                        <div id="contenedor-cropper-nueva" style="max-width: 650px; max-height: 450px; margin: 0 auto; overflow: hidden;">
                            <img id="imagen-recortar-nueva" src="" alt="Imagen a recortar" style="display: block; max-width: 100%; height: auto;">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contenido pestaña subir -->
            <div id="content-subir-nueva" class="tab-content-modal space-y-4 hidden">
                <div>
                    <label class="block mb-2 text-sm text-gray-600 dark:text-gray-400">Carpeta</label>
                    <select id="carpeta-subir-nueva" class="w-full px-3 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border text-sm">
                        <option value="">Selecciona una carpeta</option>
                    </select>
                </div>
                <div>
                    <label class="block mb-2 text-sm text-gray-600 dark:text-gray-400">Seleccionar imagen</label>
                    <input type="file" id="file-subir-nueva" accept="image/*" class="hidden">
                    <button type="button" id="btn-seleccionar-nueva" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm mb-2">
                        Seleccionar archivo
                    </button>
                    <div id="drop-zone-nueva" class="mt-2 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-4 text-center hover:border-blue-400 dark:hover:border-blue-500 transition-colors">
                        <div class="text-gray-500 dark:text-gray-400">
                            <svg class="mx-auto h-8 w-8 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                            </svg>
                            <p>Arrastra y suelta la imagen aquí</p>
                            <p class="text-xs">o haz clic para seleccionar</p>
                        </div>
                    </div>
                    <span id="nombre-archivo-nueva" class="text-sm text-gray-500 dark:text-gray-400"></span>
                </div>
            </div>

            <!-- Contenido pestaña Amazon -->
            <div id="content-amazon-nueva" class="tab-content-modal space-y-4 hidden">
                <div id="amazon-busqueda-nueva">
                    <label class="block mb-2 text-sm text-gray-600 dark:text-gray-400">URL del producto de Amazon</label>
                    <div class="flex gap-2">
                        <input type="url" id="url-amazon-nueva" class="flex-1 px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border text-sm" placeholder="https://www.amazon.es/dp/XXXXXXXXXX">
                        <button type="button" id="btn-buscar-amazon-nueva" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm">
                            Buscar
                        </button>
                    </div>
                    <div id="error-amazon-nueva" class="mt-1 text-sm text-red-500 hidden"></div>
                    <div id="loading-amazon-nueva" class="mt-2 text-sm text-gray-500 dark:text-gray-400 hidden">
                        Buscando imágenes...
                    </div>
                </div>
                
                <!-- Grid de imágenes de Amazon -->
                <div id="imagenes-amazon-nueva" class="hidden">
                    <label class="block mb-2 text-sm font-semibold text-gray-700 dark:text-gray-200">Selecciona las imágenes que deseas guardar:</label>
                    <div id="grid-imagenes-amazon-nueva" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 max-h-96 overflow-y-auto p-2">
                        <!-- Las imágenes se cargarán aquí dinámicamente -->
                    </div>
                    <div class="mt-4">
                        <label class="block mb-2 text-sm text-gray-600 dark:text-gray-400">Carpeta</label>
                        <select id="carpeta-amazon-nueva" class="w-full px-3 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border text-sm">
                            <option value="">Selecciona una carpeta</option>
                        </select>
                    </div>
                </div>

                <!-- Área de recorte Amazon -->
                <div id="area-recorte-amazon-nueva" class="hidden space-y-4">
                    <p id="progreso-recorte-amazon-nueva" class="text-sm font-semibold text-gray-700 dark:text-gray-200"></p>
                    <div>
                        <h4 class="text-md font-semibold text-gray-700 dark:text-gray-200 mb-2">Recortar imagen</h4>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">Selecciona el área que deseas mantener.</p>
                        <div id="contenedor-cropper-amazon-nueva" style="max-width: 650px; max-height: 450px; margin: 0 auto; overflow: hidden;">
                            <img id="imagen-recortar-amazon-nueva" src="" alt="Imagen a recortar" style="display: block; max-width: 100%; height: auto;">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contenido pestaña Interna producto -->
            <div id="content-interna-nueva" class="tab-content-modal space-y-4 hidden">
                <p class="text-sm text-gray-600 dark:text-gray-400">Puedes marcar <strong class="font-medium">varias imágenes</strong>. Las que ya están en el producto se ven en gris con × para quitarlas. <strong class="font-medium">Guardar</strong> cierra el modal.</p>
                <div class="border border-gray-200 dark:border-gray-600 rounded-lg p-3 bg-gray-50/80 dark:bg-gray-800/40">
                    <p class="text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Galería (vista con miniatura si existe)</p>
                    <div id="galeria-imgs-interna-nueva" class="grid gap-px max-h-44 overflow-y-auto p-0 min-h-[2rem] w-full" style="grid-template-columns: repeat(5, minmax(0, 1fr)); grid-auto-rows: minmax(0, auto);"></div>
                    <p id="galeria-imgs-interna-nueva-vacio" class="text-xs text-gray-500 dark:text-gray-400 mt-2 hidden">Aún no hay rutas de imagen en el formulario. Sube imágenes por otras pestañas o escribe rutas manualmente abajo.</p>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-700 dark:text-gray-300 mb-2">Rutas a guardar (una fila por imagen, en orden)</p>
                    <div id="filas-interna-nueva-producto" class="space-y-3"></div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">Tip: también puedes usar el botón «Añadir fila vacía» y pegar rutas a mano.</p>
                    <button type="button" id="btn-interna-nueva-anadir-fila-vacia" class="mt-2 text-sm text-blue-600 dark:text-blue-400 hover:underline">Añadir fila vacía</button>
                </div>
            </div>

            <div id="content-interna-global-nueva" class="tab-content-modal space-y-4 hidden" data-kp-ig-prefix="nueva">
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Imágenes recientes de todo el almacén. Pulsa una palabra del nombre del producto para añadirla al buscador. Haz clic en una miniatura para marcarla o desmarcarla. Usa el icono de ampliar (esquina superior derecha) para ver la imagen en tamaño original. Pulsa <strong class="font-medium">Guardar</strong> al terminar.
                </p>
                <div class="kp-ig-panel border border-gray-200 dark:border-gray-600 rounded-lg p-3 bg-gray-50/80 dark:bg-gray-800/40 space-y-3" data-kp-ig-panel="nueva">
                    <div class="kp-ig-seleccion border border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-3 bg-white/70 dark:bg-gray-900/50">
                        <p class="text-xs font-medium text-gray-700 dark:text-gray-300 mb-2">Seleccionadas para guardar</p>
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
                <button type="button" onclick="cerrarModalAñadirImagen()" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">
                    Cancelar
                </button>
                <button type="button" id="btn-guardar-nueva" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700">
                    Guardar
                </button>
            </div>
        </div>
    </div>

    {{-- MODAL PARA RECORTAR IMAGEN DESDE URL --}}
    <div id="modalRecortarImagen" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white dark:bg-gray-900 rounded-lg p-6 max-w-4xl w-full relative shadow-xl">
            <button onclick="cerrarModalRecortar()" class="absolute top-3 right-4 text-xl text-gray-800 dark:text-gray-100 hover:text-gray-600 dark:hover:text-gray-300">×</button>
            <div class="mb-4">
                <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-200">Recortar imagen</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">Selecciona el área que deseas mantener. La imagen se redimensionará automáticamente.</p>
            </div>
            <div class="mb-4">
                <div id="contenedor-cropper" class="max-h-[60vh] overflow-auto">
                    <img id="imagen-recortar" src="" alt="Imagen a recortar" style="max-width: 100%; display: block;">
                </div>
            </div>
            <div class="flex justify-end gap-3">
                <button type="button" onclick="cerrarModalRecortar()" 
                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">
                    Cancelar
                </button>
                <button type="button" id="btn-confirmar-recorte" 
                    class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700">
                    Aceptar y procesar
                </button>
            </div>
        </div>
    </div>

    {{-- MODAL PARA GESTIONAR IMÁGENES DE SUBLÍNEA --}}
    <div id="modal-imagenes-sublinea" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white dark:bg-gray-900 rounded-lg p-6 max-w-5xl w-full relative shadow-xl max-h-[90vh] flex flex-col">
            <button onclick="cerrarModalImagenesSublinea()" class="absolute top-3 right-4 text-xl text-gray-800 dark:text-gray-100 hover:text-gray-600 dark:hover:text-gray-300 z-10">×</button>
            <div class="mb-4">
                <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-200">Gestionar imágenes de la sublínea</h3>
            </div>
            <div class="flex-1 overflow-y-auto">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Imagen grande -->
                    <div class="md:col-span-2">
                        <div class="bg-gray-100 dark:bg-gray-800 rounded-lg p-4 flex items-center justify-center" style="min-height: 400px;">
                            <img id="imagen-grande-sublinea" src="" alt="Imagen grande" class="max-w-full max-h-96 object-contain rounded">
                        </div>
                        <div id="rutas-imagen-sublinea-panel" class="mt-3 space-y-2 hidden">
                            <div>
                                <label class="block mb-0.5 text-xs font-medium text-gray-600 dark:text-gray-400">Ruta imagen grande</label>
                                <code id="ruta-grande-sublinea-vista" class="block w-full text-xs text-gray-800 dark:text-gray-200 bg-gray-50 dark:bg-gray-800/80 border border-gray-200 dark:border-gray-600 rounded px-2 py-1.5 break-all whitespace-pre-wrap"></code>
                            </div>
                            <div>
                                <label class="block mb-0.5 text-xs font-medium text-gray-600 dark:text-gray-400">Ruta imagen pequeña</label>
                                <code id="ruta-pequena-sublinea-vista" class="block w-full text-xs text-gray-800 dark:text-gray-200 bg-gray-50 dark:bg-gray-800/80 border border-gray-200 dark:border-gray-600 rounded px-2 py-1.5 break-all whitespace-pre-wrap"></code>
                            </div>
                        </div>
                    </div>
                    <!-- Miniaturas -->
                    <div class="md:col-span-1">
                        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-3">Miniaturas (arrastra para reordenar)</h4>
                        <div id="miniaturas-container-sublinea" class="space-y-2 max-h-96 overflow-y-auto">
                            <!-- Las miniaturas se generarán dinámicamente -->
                        </div>
                    </div>
                </div>
            </div>
            <div class="flex justify-end gap-3 mt-4">
                <button type="button" onclick="cerrarModalImagenesSublinea()" 
                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">
                    Cerrar
                </button>
            </div>
        </div>
    </div>

    {{-- MODAL PARA AÑADIR IMÁGENES A SUBLÍNEA (reutiliza el modal existente) --}}
    <div id="modal-añadir-imagen-sublinea" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white dark:bg-gray-900 rounded-lg p-6 max-w-4xl w-full relative shadow-xl overflow-y-auto max-h-[90vh]">
            <button onclick="cerrarModalAñadirImagenSublinea()" class="absolute top-3 right-4 text-xl text-gray-800 dark:text-gray-100 hover:text-gray-600 dark:hover:text-gray-300">×</button>
            <div class="mb-4">
                <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-200">Añadir imagen a la sublínea</h3>
            </div>
            
            <!-- Pestañas dentro del modal -->
            <div class="kp-modal-img-tabs mb-4" role="tablist" aria-label="Origen de la imagen">
                <nav class="kp-modal-img-tabs__nav">
                    <button type="button" id="tab-url-sublinea" class="tab-modal-sublinea kp-modal-img-tab kp-modal-img-tab--active" role="tab" aria-selected="true">
                        Descargar desde URL
                    </button>
                    <button type="button" id="tab-subir-sublinea" class="tab-modal-sublinea kp-modal-img-tab" role="tab" aria-selected="false">
                        Subir imagen
                    </button>
                    <button type="button" id="tab-amazon-sublinea" class="tab-modal-sublinea kp-modal-img-tab" role="tab" aria-selected="false">
                        Amazon
                    </button>
                    <button type="button" id="tab-interna-sublinea" class="tab-modal-sublinea kp-modal-img-tab" role="tab" aria-selected="false">
                        Interna producto
                    </button>
                    <button type="button" id="tab-interna-global-sublinea" class="tab-modal-sublinea kp-modal-img-tab" role="tab" aria-selected="false">
                        Interna Global
                    </button>
                </nav>
            </div>

            <!-- Contenido pestaña URL -->
            <div id="content-url-sublinea" class="tab-content-modal-sublinea space-y-4">
                <div>
                    <label class="block mb-2 text-sm text-gray-600 dark:text-gray-400">Carpeta</label>
                    <select id="carpeta-url-sublinea" class="w-full px-3 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border text-sm">
                        <option value="">Selecciona una carpeta</option>
                    </select>
                </div>
                <div>
                    <label class="block mb-2 text-sm text-gray-600 dark:text-gray-400">URL de la imagen</label>
                    <div class="flex gap-2">
                        <input type="url" id="url-imagen-sublinea" class="flex-1 px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border text-sm" placeholder="https://ejemplo.com/imagen.jpg">
                        <button type="button" id="btn-descargar-url-sublinea" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm">
                            Descargar
                        </button>
                    </div>
                    <div id="error-url-sublinea" class="mt-1 text-sm text-red-500 hidden"></div>
                </div>
                
                <!-- Área de recorte (se mostrará cuando se descargue la URL) -->
                <div id="area-recorte-sublinea" class="hidden space-y-4">
                    <div class="mb-4">
                        <h4 class="text-md font-semibold text-gray-700 dark:text-gray-200 mb-2">Recortar imagen</h4>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">Selecciona el área que deseas mantener.</p>
                        <div id="contenedor-cropper-sublinea" style="max-width: 650px; max-height: 450px; margin: 0 auto; overflow: hidden;">
                            <img id="imagen-recortar-sublinea" src="" alt="Imagen a recortar" style="display: block; max-width: 100%; height: auto;">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contenido pestaña subir -->
            <div id="content-subir-sublinea" class="tab-content-modal-sublinea space-y-4 hidden">
                <div>
                    <label class="block mb-2 text-sm text-gray-600 dark:text-gray-400">Carpeta</label>
                    <select id="carpeta-subir-sublinea" class="w-full px-3 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border text-sm">
                        <option value="">Selecciona una carpeta</option>
                    </select>
                </div>
                <div>
                    <label class="block mb-2 text-sm text-gray-600 dark:text-gray-400">Seleccionar imagen</label>
                    <input type="file" id="file-subir-sublinea" accept="image/*" class="hidden">
                    <button type="button" id="btn-seleccionar-sublinea" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm mb-2">
                        Seleccionar archivo
                    </button>
                    <div id="drop-zone-sublinea" class="mt-2 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-4 text-center hover:border-blue-400 dark:hover:border-blue-500 transition-colors">
                        <div class="text-gray-500 dark:text-gray-400">
                            <svg class="mx-auto h-8 w-8 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                            </svg>
                            <p>Arrastra y suelta la imagen aquí</p>
                            <p class="text-xs">o haz clic para seleccionar</p>
                        </div>
                    </div>
                    <span id="nombre-archivo-sublinea" class="text-sm text-gray-500 dark:text-gray-400"></span>
                </div>
            </div>

            <!-- Contenido pestaña Amazon -->
            <div id="content-amazon-sublinea" class="tab-content-modal-sublinea space-y-4 hidden">
                <div id="amazon-busqueda-sublinea">
                    <label class="block mb-2 text-sm text-gray-600 dark:text-gray-400">URL del producto de Amazon</label>
                    <div class="flex gap-2">
                        <input type="url" id="url-amazon-sublinea" class="flex-1 px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border text-sm" placeholder="https://www.amazon.es/dp/XXXXXXXXXX">
                        <button type="button" id="btn-buscar-amazon-sublinea" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm">
                            Buscar
                        </button>
                    </div>
                    <div id="error-amazon-sublinea" class="mt-1 text-sm text-red-500 hidden"></div>
                    <div id="loading-amazon-sublinea" class="mt-2 text-sm text-gray-500 dark:text-gray-400 hidden">
                        Buscando imágenes...
                    </div>
                </div>
                
                <!-- Grid de imágenes de Amazon -->
                <div id="imagenes-amazon-sublinea" class="hidden">
                    <label class="block mb-2 text-sm font-semibold text-gray-700 dark:text-gray-200">Selecciona las imágenes que deseas guardar:</label>
                    <div id="grid-imagenes-amazon-sublinea" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 max-h-96 overflow-y-auto p-2">
                        <!-- Las imágenes se cargarán aquí dinámicamente -->
                    </div>
                    <div class="mt-4">
                        <label class="block mb-2 text-sm text-gray-600 dark:text-gray-400">Carpeta</label>
                        <select id="carpeta-amazon-sublinea" class="w-full px-3 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border text-sm">
                            <option value="">Selecciona una carpeta</option>
                        </select>
                    </div>
                </div>

                <!-- Área de recorte Amazon -->
                <div id="area-recorte-amazon-sublinea" class="hidden space-y-4">
                    <p id="progreso-recorte-amazon-sublinea" class="text-sm font-semibold text-gray-700 dark:text-gray-200"></p>
                    <div>
                        <h4 class="text-md font-semibold text-gray-700 dark:text-gray-200 mb-2">Recortar imagen</h4>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">Selecciona el área que deseas mantener.</p>
                        <div id="contenedor-cropper-amazon-sublinea" style="max-width: 650px; max-height: 450px; margin: 0 auto; overflow: hidden;">
                            <img id="imagen-recortar-amazon-sublinea" src="" alt="Imagen a recortar" style="display: block; max-width: 100%; height: auto;">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contenido pestaña Interna sublínea -->
            <div id="content-interna-sublinea" class="tab-content-modal-sublinea space-y-4 hidden">
                <p class="text-sm text-gray-600 dark:text-gray-400">Puedes marcar <strong class="font-medium">varias imágenes</strong> (clic en cada una). Las ya asignadas a esta opción se ven en gris con × para quitarlas. <strong class="font-medium">Guardar</strong> cierra el modal y guarda la lista (solo ruta grande).</p>
                <div class="border border-gray-200 dark:border-gray-600 rounded-lg p-3 bg-gray-50/80 dark:bg-gray-800/40">
                    <p class="text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Galería</p>
                    <div id="galeria-imgs-interna-sublinea" class="grid gap-px max-h-44 overflow-y-auto p-0 min-h-[2rem] w-full" style="grid-template-columns: repeat(5, minmax(0, 1fr)); grid-auto-rows: minmax(0, auto);"></div>
                    <p id="galeria-imgs-interna-sublinea-vacio" class="text-xs text-gray-500 dark:text-gray-400 mt-2 hidden">No hay imágenes en el formulario todavía.</p>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-700 dark:text-gray-300 mb-2">Rutas grandes a añadir (orden = orden de guardado)</p>
                    <div id="filas-interna-sublinea" class="space-y-3"></div>
                    <button type="button" id="btn-interna-sublinea-anadir-fila-vacia" class="mt-2 text-sm text-blue-600 dark:text-blue-400 hover:underline">Añadir fila vacía</button>
                </div>
            </div>

            <div id="content-interna-global-sublinea" class="tab-content-modal-sublinea space-y-4 hidden" data-kp-ig-prefix="sublinea">
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Imágenes recientes de todo el almacén. Pulsa una palabra del nombre del producto para añadirla al buscador. Haz clic en una miniatura para marcarla o desmarcarla. Usa el icono de ampliar (esquina superior derecha) para ver la imagen en tamaño original. Pulsa <strong class="font-medium">Guardar</strong> al terminar.
                </p>
                <div class="kp-ig-panel border border-gray-200 dark:border-gray-600 rounded-lg p-3 bg-gray-50/80 dark:bg-gray-800/40 space-y-3" data-kp-ig-panel="sublinea">
                    <div class="kp-ig-seleccion border border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-3 bg-white/70 dark:bg-gray-900/50">
                        <p class="text-xs font-medium text-gray-700 dark:text-gray-300 mb-2">Seleccionadas para guardar</p>
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
                <button type="button" onclick="cerrarModalAñadirImagenSublinea()" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">
                    Cancelar
                </button>
                <button type="button" id="btn-guardar-imagen-sublinea" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700">
                    Guardar
                </button>
            </div>
        </div>
    </div>

    <style>
        .form-container {
            padding-bottom: 80px;
        }

        #barra-nombre-fija:not(.hidden) {
            animation: barra-nombre-entrada 0.2s ease-out;
        }

        @keyframes barra-nombre-entrada {
            from { transform: translateY(-100%); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        #btn_guardar_fijo:not(.hidden) {
            backdrop-filter: blur(8px);
            background-color: rgba(219, 39, 119, 0.95);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 4px 20px rgba(219, 39, 119, 0.35);
        }

        #btn_guardar_fijo:not(.hidden):hover:not(:disabled) {
            background-color: rgba(190, 24, 93, 0.95);
            transform: translateY(-1px);
        }

        #btn_guardar_fijo:disabled {
            background-color: rgba(156, 163, 175, 0.85) !important;
            transform: none;
        }

        @media (max-width: 768px) {
            #btn_guardar_fijo:not(.hidden) {
                bottom: 1rem;
                right: 1rem;
                padding: 0.75rem 1.5rem;
                font-size: 0.875rem;
            }
        }
    </style>

    {{-- SCRIPTS --}}
    <script>
        // Vista previa de imagen grande
        const btnBuscarImagenGrande = document.getElementById('buscar-imagen-grande');
        if (btnBuscarImagenGrande) {
            btnBuscarImagenGrande.onclick = function() {
                const input = document.getElementById('imagen-grande-input');
                const preview = document.getElementById('preview-imagen-grande');
                if (input && preview) {
                    if (input.value.trim() !== '') {
                        preview.src = '/images/' + input.value.trim();
                        preview.style.display = 'block';
                    } else {
                        preview.src = '';
                        preview.style.display = 'none';
                    }
                }
            };
        }
        const imagenGrandeInput = document.getElementById('imagen-grande-input');
        if (imagenGrandeInput) {
            imagenGrandeInput.addEventListener('input', function() {
                const preview = document.getElementById('preview-imagen-grande');
                if (preview) {
                    preview.style.display = 'none';
                }
            });
        }

        // Vista previa de imagen pequeña
        const btnBuscarImagenPequena = document.getElementById('buscar-imagen-pequena');
        if (btnBuscarImagenPequena) {
            btnBuscarImagenPequena.onclick = function() {
                const input = document.getElementById('imagen-pequena-input');
                const preview = document.getElementById('preview-imagen-pequena');
                if (input && preview) {
                    if (input.value.trim() !== '') {
                        preview.src = '/images/' + input.value.trim();
                        preview.style.display = 'block';
                    } else {
                        preview.src = '';
                        preview.style.display = 'none';
                    }
                }
            };
        }
        const imagenPequenaInput = document.getElementById('imagen-pequena-input');
        if (imagenPequenaInput) {
            imagenPequenaInput.addEventListener('input', function() {
                const preview = document.getElementById('preview-imagen-pequena');
                if (preview) {
                    preview.style.display = 'none';
                }
            });
        }
        
        const prosList = document.getElementById('pros-list');
        const btnAddPro = document.getElementById('add-pro');
        if (btnAddPro && prosList) {
            btnAddPro.onclick = () => {
                const input = document.createElement('input');
                input.type = 'text';
                input.name = 'pros[]';
                input.className = 'w-full mb-2 px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border';
                prosList.appendChild(input);
            };
        }
        const btnRemovePro = document.getElementById('remove-pro');
        if (btnRemovePro && prosList) {
            btnRemovePro.onclick = () => {
                if (prosList.children.length > 1) prosList.removeChild(prosList.lastElementChild);
            };
        }

        const contrasList = document.getElementById('contras-list');
        const btnAddContra = document.getElementById('add-contra');
        if (btnAddContra && contrasList) {
            btnAddContra.onclick = () => {
                const input = document.createElement('input');
                input.type = 'text';
                input.name = 'contras[]';
                input.className = 'w-full mb-2 px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border';
                contrasList.appendChild(input);
            };
        }
        const btnRemoveContra = document.getElementById('remove-contra');
        if (btnRemoveContra && contrasList) {
            btnRemoveContra.onclick = () => {
                if (contrasList.children.length > 1) contrasList.removeChild(contrasList.lastElementChild);
            };
        }

        // Slug autogenerado
        document.getElementById('slug').addEventListener('input', e => {
            e.target.value = e.target.value
                .normalize("NFD").replace(/[\u0300-\u036f]/g, '')
                .replace(/[^a-zA-Z0-9\s-]/g, '')
                .trim().replace(/\s+/g, '-').toLowerCase();
        });

        // Copiar nombre al slug en tiempo real (solo para productos nuevos)
        const nombreInput = document.querySelector('input[name="nombre"]');
        const slugInput = document.getElementById('slug');
        let slugModificadoManualmente = false;
        let slugValorOriginal = slugInput ? slugInput.value.trim() : '';

        // Verificar si estamos editando un producto existente (tiene candado de slug)
        const esEdicion = !!document.getElementById('slug-candado-btn');

        if (nombreInput && slugInput && !esEdicion) {
            // Detectar si el usuario modifica manualmente el slug
            slugInput.addEventListener('input', function() {
                const slugActual = slugInput.value.trim();
                // Si el slug actual es diferente al que se generaría automáticamente del nombre actual
                const nombreActual = nombreInput.value.trim();
                if (nombreActual) {
                    const slugGenerado = nombreActual
                        .normalize("NFD").replace(/[\u0300-\u036f]/g, '')
                        .replace(/[^a-zA-Z0-9\s-]/g, '')
                        .trim().replace(/\s+/g, '-').toLowerCase();
                    
                    // Si el slug no coincide con el generado, significa que fue modificado manualmente
                    if (slugActual !== slugGenerado) {
                        slugModificadoManualmente = true;
                    }
                }
            });
            
            // Detectar si el usuario borra texto del slug manualmente
            slugInput.addEventListener('keydown', function(e) {
                // Si presiona backspace o delete y hay texto en el slug
                if ((e.key === 'Backspace' || e.key === 'Delete') && slugInput.value.length > 0) {
                    slugModificadoManualmente = true;
                }
            });
            
            // Copiar nombre al slug cada vez que se escribe (solo si no fue modificado manualmente)
            nombreInput.addEventListener('input', function() {
                if (!slugModificadoManualmente) {
                    const nombre = nombreInput.value.trim();
                    
                    if (nombre) {
                        // Normalizar el nombre igual que se hace con el slug
                        const slugNormalizado = nombre
                            .normalize("NFD").replace(/[\u0300-\u036f]/g, '')
                            .replace(/[^a-zA-Z0-9\s-]/g, '')
                            .trim().replace(/\s+/g, '-').toLowerCase();
                        
                        slugInput.value = slugNormalizado;
                        // Disparar evento input para activar la validación del slug
                        slugInput.dispatchEvent(new Event('input'));
                    } else {
                        // Si el nombre está vacío, limpiar el slug también
                        slugInput.value = '';
                    }
                }
            });
        }

        // Añadir/Eliminar campos en las FAQ
        const faqList = document.getElementById('faq-list');
        @php
            $faqs = old('faq', $producto->faq ?? [['pregunta' => '', 'respuesta' => '']]);
        @endphp
        let faqIndex = @json(count($faqs));

        const btnAddFaq = document.getElementById('add-faq');
        if (btnAddFaq && faqList) {
            btnAddFaq.onclick = () => {
                const div = document.createElement('div');
                div.className = 'faq-item grid grid-cols-1 md:grid-cols-2 gap-4';
                div.innerHTML = `
                <input type="text" name="faq[${faqIndex}][pregunta]" placeholder="Pregunta"
                    class="w-full px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border">
                <input type="text" name="faq[${faqIndex}][respuesta]" placeholder="Respuesta"
                    class="w-full px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border">
            `;
                faqList.appendChild(div);
                faqIndex++;
            };
        }

        const btnRemoveFaq = document.getElementById('remove-faq');
        if (btnRemoveFaq && faqList) {
            btnRemoveFaq.onclick = () => {
                if (faqList.children.length > 1) {
                    faqList.removeChild(faqList.lastElementChild);
                    faqIndex--;
                } else {
                    alert('Debe haber al menos una pregunta frecuente.');
                }
            };
        }
    </script>

    {{-- SISTEMA DE CATEGORÍAS COMPLETAMENTE REWRITE --}}
    @php
        $kpConfiguracionFormularioProducto = 'ninguno';
        $kpCategoriaIdProducto = old('categoria_id', $producto?->categoria_id ?? null);
        if (!empty($kpCategoriaIdProducto)) {
            $kpConfiguracionFormularioProducto = \App\Models\Categoria::where('id', $kpCategoriaIdProducto)
                ->value('configuracion_formulario_producto') ?? 'ninguno';
        }
    @endphp
    <div id="datos-categorias"
        data-categorias-raiz="{{ json_encode($categoriasRaiz) }}"
        data-categoria-id="{{ old('categoria_id', $producto?->categoria_id ?? '') }}"
        data-configuracion-formulario-producto="{{ $kpConfiguracionFormularioProducto }}">
    </div>

    <script>
        // Variables globales
        let categoriasRaiz = [];
        let categoriaProducto = null;
        let timeoutBusquedaCategoria = null;
        let indiceSeleccionadoCategoriaBuscador = -1;
        let categoriasActualesBuscador = [];
        let categoriaSeleccionadaBuscador = null;
        window.kpConfiguracionFormularioProducto = document.getElementById('datos-categorias')?.dataset?.configuracionFormularioProducto || 'ninguno';
        const KP_CONFIG_NO_COLUMNA_IMAGEN_NOMBRE_PRECIO = 'no_columna_grupo_mostrar_imagen_nombre_precio';
        function kpEsConfigNoColumnaGrupoImagenNombrePrecio(config) {
            return config === KP_CONFIG_NO_COLUMNA_IMAGEN_NOMBRE_PRECIO;
        }
        let pestañaActiva = 'buscador'; // 'buscador' o 'manual'

        // Inicialización cuando se carga la página
        document.addEventListener('DOMContentLoaded', () => {
            const datos = document.getElementById('datos-categorias');
            categoriasRaiz = JSON.parse(datos.dataset.categoriasRaiz || '[]');
            categoriaProducto = datos.dataset.categoriaId || null;

            // Configurar pestañas
            configurarPestañas();

            if (categoriaProducto) {
                // Es un producto existente - cargar toda la jerarquía en manual
                cargarJerarquiaCompleta(categoriaProducto, 'manual');
                // Cargar el nombre de la categoría para el buscador
                cargarNombreCategoriaParaBuscador(categoriaProducto);
                // Verificar si la categoría tiene subcategorías
                verificarCategoriaParaGuardar(categoriaProducto);
                // Mostrar pestaña manual por defecto
                cambiarPestaña('manual');
            } else {
                // Es un producto nuevo - mostrar pestaña de buscador por defecto
                cambiarPestaña('buscador');
            }

            // Configurar botones
            configurarBotones();

            // Configurar buscador de categorías (con delay para asegurar que el DOM esté listo)
            setTimeout(() => {
                configurarBuscadorCategorias();
                configurarBotonesCategoriasRecientesBuscador();
            }, 100);

            // Configurar validación del formulario
            configurarValidacionFormulario();
        });

        // Función para configurar las pestañas
        function configurarPestañas() {
            const tabBuscador = document.getElementById('tab-buscador-categoria');
            const tabManual = document.getElementById('tab-manual-categoria');

            if (tabBuscador) {
                tabBuscador.addEventListener('click', () => cambiarPestaña('buscador'));
            }

            if (tabManual) {
                tabManual.addEventListener('click', () => cambiarPestaña('manual'));
            }
        }

        // Función para cambiar de pestaña
        function cambiarPestaña(pestaña) {
            pestañaActiva = pestaña;
            const tabBuscador = document.getElementById('tab-buscador-categoria');
            const tabManual = document.getElementById('tab-manual-categoria');
            const contentBuscador = document.getElementById('content-buscador-categoria');
            const contentManual = document.getElementById('content-manual-categoria');

            if (!tabBuscador || !tabManual || !contentBuscador || !contentManual) {
                console.error('No se encontraron los elementos de las pestañas');
                return;
            }

            if (pestaña === 'buscador') {
                // Activar pestaña buscador
                tabBuscador.classList.add('border-blue-500', 'text-blue-600', 'dark:text-blue-400');
                tabBuscador.classList.remove('border-transparent', 'text-gray-500');
                tabManual.classList.remove('border-blue-500', 'text-blue-600', 'dark:text-blue-400');
                tabManual.classList.add('border-transparent', 'text-gray-500');
                contentBuscador.classList.remove('hidden');
                contentManual.classList.add('hidden');

                // Reconfigurar el buscador cuando se cambia a esta pestaña
                setTimeout(() => {
                    configurarBuscadorCategorias();
                }, 50);

                // Si hay categoría seleccionada, mostrarla en el buscador
                if (categoriaProducto) {
                    mostrarJerarquiaEnBuscador(categoriaProducto);
                    // Actualizar el input del buscador con el nombre de la categoría
                    const inputBuscador = document.getElementById('categoria-buscador-input');
                    if (inputBuscador && categoriaSeleccionadaBuscador) {
                        inputBuscador.value = categoriaSeleccionadaBuscador.nombre;
                        inputBuscador.classList.add('border-green-500');
                    }
                }
            } else {
                // Activar pestaña manual
                tabManual.classList.add('border-blue-500', 'text-blue-600', 'dark:text-blue-400');
                tabManual.classList.remove('border-transparent', 'text-gray-500');
                tabBuscador.classList.remove('border-blue-500', 'text-blue-600', 'dark:text-blue-400');
                tabBuscador.classList.add('border-transparent', 'text-gray-500');
                contentManual.classList.remove('hidden');
                contentBuscador.classList.add('hidden');

                // Si hay categoría seleccionada, mostrarla en manual
                if (categoriaProducto) {
                    cargarJerarquiaCompleta(categoriaProducto, 'manual');
                } else {
                    // Si no hay categoría, crear el primer selector
                    const container = document.getElementById('categorias-container');
                    if (container && container.children.length === 0) {
                        crearSelectorCategoria(0, categoriasRaiz, null);
                    }
                }
            }
            
            // Actualizar el indicador del botón agregar
            actualizarIndicadorBotonAgregar();
        }

        function configurarBotonesCategoriasRecientesBuscador() {
            const cont = document.getElementById('categoria-buscador-recientes');
            if (!cont || cont.dataset.kpRecientesCfg === '1') {
                return;
            }
            cont.dataset.kpRecientesCfg = '1';
            cont.addEventListener('click', function(e) {
                const btn = e.target.closest('.kp-categoria-reciente-btn');
                if (!btn) {
                    return;
                }
                e.preventDefault();
                const id = btn.dataset.id;
                const nombre = btn.dataset.nombre;
                if (!id || !nombre) {
                    return;
                }
                cambiarPestaña('buscador');
                seleccionarCategoriaBuscador({ id: id, nombre: nombre });
            });
        }

        // Función para configurar el buscador de categorías
        function configurarBuscadorCategorias() {
            const inputBuscador = document.getElementById('categoria-buscador-input');
            if (!inputBuscador) {
                return;
            }

            // Remover event listeners anteriores si existen
            const nuevoInput = inputBuscador.cloneNode(true);
            inputBuscador.parentNode.replaceChild(nuevoInput, inputBuscador);

            // Evento de escritura
            nuevoInput.addEventListener('input', function(e) {
                const query = e.target.value.trim();
                
                if (timeoutBusquedaCategoria) {
                    clearTimeout(timeoutBusquedaCategoria);
                }

                timeoutBusquedaCategoria = setTimeout(() => {
                    buscarCategoriasBuscador(query);
                }, 300);
            });

            // Evento de teclado
            nuevoInput.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    ocultarSugerenciasCategoriaBuscador();
                    nuevoInput.blur();
                } else if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    navegarSugerenciasCategoria('abajo');
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    navegarSugerenciasCategoria('arriba');
                } else if (e.key === 'Enter') {
                    e.preventDefault();
                    if (indiceSeleccionadoCategoriaBuscador >= 0) {
                        seleccionarCategoriaResaltada();
                    }
                }
            });

            // Ocultar sugerencias al hacer clic fuera
            if (!window.buscadorCategoriaClickHandler) {
                window.buscadorCategoriaClickHandler = function(e) {
                    if (!e.target.closest('#categoria-buscador-input') && !e.target.closest('#categoria-buscador-sugerencias')) {
                        ocultarSugerenciasCategoriaBuscador();
                    }
                };
                document.addEventListener('click', window.buscadorCategoriaClickHandler);
            }
        }

        // Función para buscar categorías (buscador principal)
        async function buscarCategoriasBuscador(query) {
            if (query.length < 2) {
                ocultarSugerenciasCategoriaBuscador();
                return;
            }

            try {
                const response = await fetch(`/panel-privado/productos/buscar/categorias?q=${encodeURIComponent(query)}`);
                const categorias = await response.json();
                categoriasActualesBuscador = categorias;
                mostrarSugerenciasCategoriaBuscador(categorias);
            } catch (error) {
                console.error('Error al buscar categorías:', error);
            }
        }

        // Función para mostrar sugerencias (buscador principal)
        function mostrarSugerenciasCategoriaBuscador(categorias) {
            const contenedor = document.getElementById('categoria-buscador-sugerencias');
            contenedor.innerHTML = '';
            indiceSeleccionadoCategoriaBuscador = -1;

            if (categorias.length === 0) {
                contenedor.innerHTML = '<div class="px-4 py-2 text-gray-500 dark:text-gray-400">No se encontraron categorías</div>';
                contenedor.classList.remove('hidden');
                return;
            }

            categorias.forEach((categoria, index) => {
                const div = document.createElement('div');
                div.className = 'px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 cursor-pointer text-gray-900 dark:text-white border-b border-gray-200 dark:border-gray-600 last:border-b-0';
                div.textContent = categoria.nombre;
                div.dataset.index = index;
                div.dataset.categoriaId = categoria.id;
                
                div.onclick = () => {
                    seleccionarCategoriaBuscador(categoria);
                };

                div.onmouseenter = () => {
                    indiceSeleccionadoCategoriaBuscador = index;
                    actualizarResaltadoCategoriaBuscador();
                };

                contenedor.appendChild(div);
            });

            contenedor.classList.remove('hidden');
        }

        // Función para ocultar sugerencias (buscador principal)
        function ocultarSugerenciasCategoriaBuscador() {
            const contenedor = document.getElementById('categoria-buscador-sugerencias');
            if (contenedor) {
                contenedor.classList.add('hidden');
            }
            indiceSeleccionadoCategoriaBuscador = -1;
        }

        // Función para navegar sugerencias con teclado (buscador principal)
        function navegarSugerenciasCategoria(direccion) {
            const contenedor = document.getElementById('categoria-buscador-sugerencias');
            const elementos = contenedor.querySelectorAll('div[data-index]');
            
            if (elementos.length === 0) return;
            
            // Remover selección anterior
            elementos.forEach(el => el.classList.remove('bg-blue-100', 'dark:bg-blue-700'));
            
            if (direccion === 'arriba') {
                indiceSeleccionadoCategoriaBuscador = indiceSeleccionadoCategoriaBuscador <= 0 ? elementos.length - 1 : indiceSeleccionadoCategoriaBuscador - 1;
            } else if (direccion === 'abajo') {
                indiceSeleccionadoCategoriaBuscador = indiceSeleccionadoCategoriaBuscador >= elementos.length - 1 ? 0 : indiceSeleccionadoCategoriaBuscador + 1;
            }
            
            // Aplicar selección
            if (indiceSeleccionadoCategoriaBuscador >= 0 && indiceSeleccionadoCategoriaBuscador < elementos.length) {
                elementos[indiceSeleccionadoCategoriaBuscador].classList.add('bg-blue-100', 'dark:bg-blue-700');
                elementos[indiceSeleccionadoCategoriaBuscador].scrollIntoView({ block: 'nearest' });
            }
        }

        // Función para actualizar resaltado visual (buscador principal)
        function actualizarResaltadoCategoriaBuscador() {
            const contenedor = document.getElementById('categoria-buscador-sugerencias');
            const elementos = contenedor.querySelectorAll('div[data-index]');
            elementos.forEach((el, index) => {
                if (index === indiceSeleccionadoCategoriaBuscador) {
                    el.classList.add('bg-blue-100', 'dark:bg-blue-700');
                } else {
                    el.classList.remove('bg-blue-100', 'dark:bg-blue-700');
                }
            });
        }

        // Función para seleccionar categoría resaltada (buscador principal)
        function seleccionarCategoriaResaltada() {
            if (indiceSeleccionadoCategoriaBuscador >= 0 && categoriasActualesBuscador[indiceSeleccionadoCategoriaBuscador]) {
                seleccionarCategoriaBuscador(categoriasActualesBuscador[indiceSeleccionadoCategoriaBuscador]);
            }
        }

        // Función para seleccionar una categoría del buscador
        function seleccionarCategoriaBuscador(categoria) {
            categoriaSeleccionadaBuscador = categoria;
            const inputBuscador = document.getElementById('categoria-buscador-input');
            if (inputBuscador) {
                inputBuscador.value = categoria.nombre;
                inputBuscador.classList.add('border-green-500');
            }
            
            // Ocultar las sugerencias
            ocultarSugerenciasCategoriaBuscador();

            // Cargar la jerarquía completa de la categoría seleccionada
            mostrarJerarquiaEnBuscador(categoria.id);
            
            // Actualizar la categoría final y validar
            document.getElementById('categoria-final').value = categoria.id;
            categoriaProducto = categoria.id;
            verificarCategoriaParaGuardar(categoria.id);
            aplicarUnidadMedidaDesdeCategoria(categoria.id);
            
            // Si estamos en la pestaña manual, también actualizar allí
            if (pestañaActiva === 'manual') {
                cargarJerarquiaCompleta(categoria.id, 'manual');
            }
        }

        // Función para mostrar la jerarquía en el buscador
        function mostrarJerarquiaEnBuscador(categoriaId) {
            fetch(`/panel-privado/categorias/${categoriaId}/jerarquia`, {
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.jerarquia && data.jerarquia.length > 0) {
                    construirJerarquiaEnBuscador(data.jerarquia);
                    // Actualizar el campo final
                    document.getElementById('categoria-final').value = categoriaId;
                    categoriaProducto = categoriaId;
                    actualizarIndicadorBotonAgregar();
                }
            })
            .catch(error => {
                console.error('Error cargando jerarquía:', error);
            });
        }

        // Función para construir la jerarquía en el contenedor del buscador
        function construirJerarquiaEnBuscador(jerarquia) {
            const container = document.getElementById('categorias-container-buscador');
            container.innerHTML = '';

            // Crear un elemento para mostrar la jerarquía
            const divJerarquia = document.createElement('div');
            divJerarquia.className = 'space-y-2 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600';

            const titulo = document.createElement('h4');
            titulo.className = 'font-semibold text-gray-700 dark:text-gray-200 mb-2';
            titulo.textContent = 'Jerarquía de la categoría seleccionada:';
            divJerarquia.appendChild(titulo);

            const lista = document.createElement('div');
            lista.className = 'space-y-1';

            jerarquia.forEach((nivel, index) => {
                const item = document.createElement('div');
                item.className = 'flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300';
                
                // Agregar flecha si no es el primer nivel
                if (index > 0) {
                    const flecha = document.createElement('span');
                    flecha.textContent = '→';
                    flecha.className = 'text-gray-400';
                    item.appendChild(flecha);
                }

                const nombre = document.createElement('span');
                nombre.textContent = nivel.nombre;
                if (index === jerarquia.length - 1) {
                    nombre.className = 'font-semibold text-blue-600 dark:text-blue-400';
                }
                item.appendChild(nombre);

                lista.appendChild(item);
            });

            divJerarquia.appendChild(lista);
            container.appendChild(divJerarquia);

            // Actualizar campos de especificaciones internas y productos relacionados
            const ultimaCategoria = jerarquia[jerarquia.length - 1];
            actualizarCamposConNombre(ultimaCategoria.id, ultimaCategoria.nombre);

            // Verificar si la categoría tiene subcategorías para validar el guardado
            verificarCategoriaParaGuardar(ultimaCategoria.id);

            // Verificar si la última categoría tiene subcategorías y mostrarlas si es necesario
            verificarYMostrarSubcategoriasBuscador(ultimaCategoria.id);
        }

        // Función para verificar y mostrar subcategorías en el buscador
        function verificarYMostrarSubcategoriasBuscador(categoriaId) {
            fetch(`/panel-privado/categorias/${categoriaId}/subcategorias`, {
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            })
            .then(res => res.json())
            .then(subcategorias => {
                if (subcategorias && subcategorias.length > 0) {
                    // No mostrar automáticamente, solo actualizar el botón
                    actualizarIndicadorBotonAgregar();
                } else {
                    actualizarIndicadorBotonAgregar();
                }
            })
            .catch(error => {
                console.error('Error verificando subcategorías:', error);
                actualizarIndicadorBotonAgregar();
            });
        }

        // Función para cargar el nombre de la categoría en el buscador
        function cargarNombreCategoriaParaBuscador(categoriaId) {
            fetch(`/panel-privado/categorias/${categoriaId}/jerarquia`, {
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.jerarquia && data.jerarquia.length > 0) {
                    const ultimaCategoria = data.jerarquia[data.jerarquia.length - 1];
                    categoriaSeleccionadaBuscador = {
                        id: ultimaCategoria.id,
                        nombre: ultimaCategoria.nombre
                    };
                    const inputBuscador = document.getElementById('categoria-buscador-input');
                    if (inputBuscador) {
                        inputBuscador.value = ultimaCategoria.nombre;
                        inputBuscador.classList.add('border-green-500');
                    }
                }
            })
            .catch(error => {
                console.error('Error cargando nombre de categoría:', error);
            });
        }

        function configurarBotones() {
            // Botón para limpiar todo
            const btnLimpiar = document.getElementById('limpiar-categorias');
            if (btnLimpiar) {
                btnLimpiar.addEventListener('click', () => {
                    limpiarTodasLasCategorias();
                });
            }

            // Botón para agregar nivel de categoría
            const btnAgregar = document.getElementById('agregar-categoria');
            if (btnAgregar) {
                btnAgregar.addEventListener('click', () => {
                    agregarNivelCategoria();
                });
            }
        }

        function agregarNivelCategoria() {
            // Si estamos en la pestaña de buscador, usar la categoría seleccionada
            if (pestañaActiva === 'buscador' && categoriaProducto) {
                verificarYAgregarSubcategoriaBuscador(categoriaProducto);
                return;
            }

            // Si estamos en la pestaña manual, usar los selectores
            const selectores = document.querySelectorAll('.categoria-select');
            let ultimoNivel = -1;
            let ultimaCategoriaId = null;

            // Encontrar el último selector con valor seleccionado
            for (let i = selectores.length - 1; i >= 0; i--) {
                if (selectores[i].value) {
                    ultimoNivel = parseInt(selectores[i].dataset.nivel);
                    ultimaCategoriaId = selectores[i].value;
                    break;
                }
            }

            if (ultimaCategoriaId) {
                // Verificar si la última categoría tiene subcategorías
                verificarYAgregarSubcategoria(ultimoNivel + 1, ultimaCategoriaId);
            } else {
                // Si no hay categoría seleccionada, mostrar mensaje
                alert('Primero debes seleccionar una categoría para poder agregar un nivel adicional.');
            }
        }

        // Función para agregar nivel en el buscador
        function verificarYAgregarSubcategoriaBuscador(categoriaId) {
            fetch(`/panel-privado/categorias/${categoriaId}/subcategorias`, {
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            })
            .then(res => res.json())
            .then(subcategorias => {
                if (subcategorias && subcategorias.length > 0) {
                    // Mostrar selector de subcategorías en el buscador
                    mostrarSelectorSubcategoriasBuscador(subcategorias, categoriaId);
                } else {
                    alert('La categoría seleccionada no tiene subcategorías disponibles.');
                }
            })
            .catch(error => {
                console.error('Error verificando subcategorías:', error);
                alert('Error al verificar si hay subcategorías disponibles.');
            });
        }

        // Función para mostrar selector de subcategorías en el buscador
        function mostrarSelectorSubcategoriasBuscador(subcategorias, categoriaPadreId) {
            const container = document.getElementById('categorias-container-buscador');
            
            // Buscar si ya existe un selector de subcategorías
            const selectorExistente = container.querySelector('.selector-subcategoria-buscador');
            if (selectorExistente) {
                selectorExistente.remove();
            }

            // Crear contenedor para el selector
            const divSelector = document.createElement('div');
            divSelector.className = 'selector-subcategoria-buscador mt-4 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600';

            const label = document.createElement('label');
            label.className = 'block mb-2 font-medium text-gray-700 dark:text-gray-200';
            label.textContent = 'Selecciona una subcategoría:';
            divSelector.appendChild(label);

            const select = document.createElement('select');
            select.className = 'w-full px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border';
            
            const optionDefault = document.createElement('option');
            optionDefault.value = '';
            optionDefault.textContent = 'Selecciona una subcategoría';
            select.appendChild(optionDefault);

            // Ordenar subcategorías alfabéticamente
            const subcategoriasOrdenadas = [...subcategorias].sort((a, b) => a.nombre.localeCompare(b.nombre, 'es', {sensitivity: 'base'}));
            
            subcategoriasOrdenadas.forEach(cat => {
                const option = document.createElement('option');
                option.value = cat.id;
                option.textContent = cat.nombre;
                select.appendChild(option);
            });

            select.addEventListener('change', function() {
                if (this.value) {
                    // Actualizar la categoría final y recargar la jerarquía
                    const nuevaCategoriaId = this.value;
                    document.getElementById('categoria-final').value = nuevaCategoriaId;
                    categoriaProducto = nuevaCategoriaId;
                    
                    // Actualizar el objeto categoriaSeleccionadaBuscador
                    const categoriaSeleccionada = subcategorias.find(cat => cat.id == nuevaCategoriaId);
                    if (categoriaSeleccionada) {
                        categoriaSeleccionadaBuscador = categoriaSeleccionada;
                        const inputBuscador = document.getElementById('categoria-buscador-input');
                        if (inputBuscador) {
                            inputBuscador.value = categoriaSeleccionada.nombre;
                        }
                        // Actualizar campos de especificaciones internas y productos relacionados
                        actualizarCamposConNombre(nuevaCategoriaId, categoriaSeleccionada.nombre);
                    }
                    
                    // Verificar si la categoría tiene subcategorías
                    verificarCategoriaParaGuardar(nuevaCategoriaId);
                    aplicarUnidadMedidaDesdeCategoria(nuevaCategoriaId);
                    
                    mostrarJerarquiaEnBuscador(nuevaCategoriaId);
                    actualizarIndicadorBotonAgregar();
                }
            });

            divSelector.appendChild(select);
            container.appendChild(divSelector);
        }

        function verificarYAgregarSubcategoria(nivel, categoriaId) {
            fetch(`/panel-privado/categorias/${categoriaId}/subcategorias`, {
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            })
            .then(res => res.json())
            .then(subcategorias => {
                if (subcategorias && subcategorias.length > 0) {
                    // Crear el nuevo selector con las subcategorías disponibles
                    crearSelectorCategoria(nivel, subcategorias, null);
                    // Actualizar el campo final
                    actualizarCategoriaFinal();
                } else {
                    // No hay subcategorías disponibles
                    alert('La categoría seleccionada no tiene subcategorías disponibles.');
                }
            })
            .catch(error => {
                console.error('Error verificando subcategorías:', error);
                alert('Error al verificar si hay subcategorías disponibles.');
            });
        }

        function cargarJerarquiaCompleta(categoriaId, tipoPestaña = 'manual') {
            // Obtener la jerarquía completa de la categoría
            fetch(`/panel-privado/categorias/${categoriaId}/jerarquia`, {
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.jerarquia && data.jerarquia.length > 0) {
                    if (tipoPestaña === 'buscador') {
                        construirJerarquiaEnBuscador(data.jerarquia);
                    } else {
                        construirJerarquiaCompleta(data.jerarquia);
                    }
                    // Actualizar el campo final (ya se actualiza en las funciones construirJerarquia)
                    // pero también actualizamos aquí por si acaso
                    const ultimaCategoria = data.jerarquia[data.jerarquia.length - 1];
                    document.getElementById('categoria-final').value = ultimaCategoria.id;
                    categoriaProducto = ultimaCategoria.id;
                }
            })
            .catch(error => {
                console.error('Error cargando jerarquía:', error);
                // Fallback: crear selector básico solo en manual
                if (tipoPestaña === 'manual') {
                    crearSelectorCategoria(0, categoriasRaiz, null);
                }
            });
        }

        function construirJerarquiaCompleta(jerarquia) {
            // Limpiar contenedor manual
            const container = document.getElementById('categorias-container');
            container.innerHTML = '';

            // Crear selectores para cada nivel de la jerarquía
            jerarquia.forEach((nivel, index) => {
                if (index === 0) {
                    // Nivel 0: categorías raíz
                    crearSelectorCategoria(0, categoriasRaiz, nivel.id);
                } else {
                    // Niveles superiores: cargar subcategorías del nivel anterior
                    const nivelAnterior = jerarquia[index - 1];
                    cargarSubcategoriasParaJerarquia(index, nivelAnterior.id, nivel.id);
                }
            });

            // Actualizar campo final
            const ultimaCategoria = jerarquia[jerarquia.length - 1];
            document.getElementById('categoria-final').value = ultimaCategoria.id;
            categoriaProducto = ultimaCategoria.id;
            
            // Actualizar campos de especificaciones internas y productos relacionados
            actualizarCamposConNombre(ultimaCategoria.id, ultimaCategoria.nombre);
            
            // Verificar si la categoría tiene subcategorías
            verificarCategoriaParaGuardar(ultimaCategoria.id);
            
            actualizarIndicadorBotonAgregar();
        }

        function cargarSubcategoriasParaJerarquia(nivel, categoriaId, valorSeleccionado) {
            fetch(`/panel-privado/categorias/${categoriaId}/subcategorias`, {
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            })
            .then(res => res.json())
            .then(subcategorias => {
                if (subcategorias.length > 0) {
                    crearSelectorCategoria(nivel, subcategorias, valorSeleccionado);
                }
            })
            .catch(error => {
                console.error('Error cargando subcategorías:', error);
            });
        }

        function crearSelectorCategoria(nivel, opciones, valorSeleccionado) {
            const container = document.getElementById('categorias-container');
            
            // Verificar si ya existe un selector para este nivel
            const selectorExistente = document.querySelector(`.selector-categoria[data-nivel="${nivel}"]`);
            if (selectorExistente) {
                selectorExistente.remove();
            }
            
            // Crear contenedor del selector
            const div = document.createElement('div');
            div.className = 'selector-categoria flex items-center gap-4 mb-4';
            div.dataset.nivel = nivel;
            
            // Crear label
            const label = document.createElement('label');
            label.className = 'block mb-1 font-medium text-gray-700 dark:text-gray-200 min-w-[120px]';
            label.textContent = nivel === 0 ? 'Categoría principal *' : `Subcategoría ${nivel}`;
            
            // Crear select
            const select = document.createElement('select');
            select.className = 'categoria-select w-full px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border';
            select.dataset.nivel = nivel;
            
            // Opción por defecto
            const optionDefault = document.createElement('option');
            optionDefault.value = '';
            optionDefault.textContent = nivel === 0 ? 'Selecciona una categoría' : 'Selecciona una subcategoría';
            select.appendChild(optionDefault);
            
            // Ordenar opciones alfabéticamente por nombre
            const opcionesOrdenadas = [...opciones].sort((a, b) => a.nombre.localeCompare(b.nombre, 'es', {sensitivity: 'base'}));
            
            // Agregar opciones ordenadas
            opcionesOrdenadas.forEach(cat => {
                const option = document.createElement('option');
                option.value = cat.id;
                option.textContent = cat.nombre;
                if (valorSeleccionado == cat.id) {
                    option.selected = true;
                }
                select.appendChild(option);
            });
            
            // Botón de eliminar (solo para niveles > 0)
            let removeBtn = null;
            if (nivel > 0) {
                removeBtn = document.createElement('button');
                removeBtn.type = 'button';
                removeBtn.className = 'bg-red-600 hover:bg-red-700 text-white px-3 py-2 rounded text-sm';
                removeBtn.textContent = '×';
                removeBtn.title = 'Eliminar este nivel y los superiores';
                removeBtn.onclick = () => {
                    eliminarSelectorYSuperiores(nivel);
                };
            }
            
            // Ensamblar
            div.appendChild(label);
            div.appendChild(select);
            if (removeBtn) div.appendChild(removeBtn);
            
            container.appendChild(div);
            
            // Configurar evento change
            select.addEventListener('change', () => {
                const categoriaId = select.value;
                if (categoriaId) {
                    // Limpiar selectores de niveles superiores
                    limpiarSelectoresSuperiores(nivel + 1);
                    // Cargar subcategorías del siguiente nivel
                    cargarSubcategorias(nivel + 1, categoriaId);
                    // Actualizar campo final
                    actualizarCategoriaFinal();
                } else {
                    // Limpiar selectores de niveles superiores
                    limpiarSelectoresSuperiores(nivel + 1);
                    // Actualizar campo final
                    actualizarCategoriaFinal();
                }
            });
        }

        function cargarSubcategorias(nivel, categoriaId) {
            // Limpiar selectores de niveles superiores
            limpiarSelectoresSuperiores(nivel);
            
            fetch(`/panel-privado/categorias/${categoriaId}/subcategorias`, {
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            })
            .then(res => res.json())
            .then(subcategorias => {
                if (subcategorias.length > 0) {
                    crearSelectorCategoria(nivel, subcategorias, null);
                }
            })
            .catch(error => {
                console.error('Error cargando subcategorías:', error);
            });
        }

        function limpiarSelectoresSuperiores(nivel) {
            const selectores = document.querySelectorAll(`.selector-categoria[data-nivel]`);
            selectores.forEach(selector => {
                const nivelSelector = parseInt(selector.dataset.nivel);
                if (nivelSelector >= nivel) {
                    selector.remove();
                }
            });
        }

        function eliminarSelectorYSuperiores(nivel) {
            const selectores = document.querySelectorAll(`.selector-categoria[data-nivel]`);
            selectores.forEach(selector => {
                const nivelSelector = parseInt(selector.dataset.nivel);
                if (nivelSelector >= nivel) {
                    selector.remove();
                }
            });
            // Actualizar campo final después de eliminar
            actualizarCategoriaFinal();
        }

        function limpiarTodasLasCategorias() {
            // Limpiar contenedor manual
            const container = document.getElementById('categorias-container');
            container.innerHTML = '';
            
            // Limpiar contenedor buscador
            const containerBuscador = document.getElementById('categorias-container-buscador');
            containerBuscador.innerHTML = '';
            
            // Limpiar input del buscador
            const inputBuscador = document.getElementById('categoria-buscador-input');
            if (inputBuscador) {
                inputBuscador.value = '';
                inputBuscador.classList.remove('border-green-500');
            }
            
            // Limpiar campo final
            document.getElementById('categoria-final').value = '';
            categoriaProducto = null;
            categoriaSeleccionadaBuscador = null;
            categoriaTieneSubcategorias = false;
            
            // Ocultar advertencia
            mostrarAdvertenciaSubcategorias(false);
            
            // Habilitar botón de guardar (se validará que haya categoría en el submit)
            actualizarBotonGuardar(true, '');
            
            // Solo crear el primer selector en manual si estamos en esa pestaña
            if (pestañaActiva === 'manual') {
                crearSelectorCategoria(0, categoriasRaiz, null);
            }
            
            actualizarIndicadorBotonAgregar();
        }

        function actualizarCategoriaFinal() {
            let categoriaFinal = null;
            let nombreCategoriaFinal = null;

            // Si estamos en la pestaña de buscador y hay categoría seleccionada
            if (pestañaActiva === 'buscador' && categoriaProducto) {
                categoriaFinal = categoriaProducto;
                // Obtener el nombre de la categoría seleccionada en el buscador
                if (categoriaSeleccionadaBuscador) {
                    nombreCategoriaFinal = categoriaSeleccionadaBuscador.nombre;
                }
            } else {
                // Si estamos en manual, buscar en los selectores
                const selectores = document.querySelectorAll('.categoria-select');
                for (let i = selectores.length - 1; i >= 0; i--) {
                    if (selectores[i].value) {
                        categoriaFinal = selectores[i].value;
                        // Obtener el nombre del option seleccionado
                        const optionSeleccionada = selectores[i].options[selectores[i].selectedIndex];
                        if (optionSeleccionada && optionSeleccionada.textContent) {
                            nombreCategoriaFinal = optionSeleccionada.textContent;
                        }
                        break;
                    }
                }
            }
            
            document.getElementById('categoria-final').value = categoriaFinal || '';
            if (categoriaFinal) {
                categoriaProducto = categoriaFinal;
                
                // Actualizar campos de especificaciones internas y productos relacionados
                actualizarCamposCategoriaAutomaticos(categoriaFinal, nombreCategoriaFinal);
                
                // Verificar si la categoría tiene subcategorías y validar el botón de guardar
                verificarCategoriaParaGuardar(categoriaFinal);

                // Aplicar unidad de medida de la categoría hoja seleccionada
                aplicarUnidadMedidaDesdeCategoria(categoriaFinal);
            } else {
                // Si no hay categoría, habilitar el botón de guardar (se validará en el submit)
                actualizarBotonGuardar(true, '');
            }
            
            // Actualizar indicador visual del botón agregar
            actualizarIndicadorBotonAgregar();
        }

        // Aplica la unidad de medida configurada en la categoría hoja (última de la jerarquía)
        function aplicarUnidadMedidaDesdeCategoria(categoriaId) {
            if (!categoriaId) return;

            fetch(`/panel-privado/categorias/${categoriaId}/jerarquia`, {
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            })
            .then(res => res.json())
            .then(data => {
                window.kpConfiguracionFormularioProducto = data.configuracion_formulario_producto || 'ninguno';

                const unidadMedida = data.unidad_de_medida;
                if (!unidadMedida) return;

                const select = document.getElementById('unidadDeMedida');
                if (select && select.querySelector(`option[value="${unidadMedida}"]`)) {
                    select.value = unidadMedida;
                    select.dispatchEvent(new Event('change'));
                }
            })
            .catch(error => {
                console.error('Error cargando unidad de medida de la categoría:', error);
            });
        }

        // Función para verificar si la categoría tiene subcategorías y validar el botón de guardar
        function verificarCategoriaParaGuardar(categoriaId) {
            if (!categoriaId) {
                categoriaTieneSubcategorias = false;
                actualizarBotonGuardar(true, '');
                mostrarAdvertenciaSubcategorias(false);
                return;
            }

            fetch(`/panel-privado/categorias/${categoriaId}/subcategorias`, {
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            })
            .then(res => res.json())
            .then(subcategorias => {
                if (subcategorias && subcategorias.length > 0) {
                    // La categoría tiene subcategorías - no permitir guardar
                    categoriaTieneSubcategorias = true;
                    actualizarBotonGuardar(false, 'La categoría seleccionada tiene subcategorías. Debes seleccionar la última categoría de la jerarquía.');
                    mostrarAdvertenciaSubcategorias(true);
                } else {
                    // La categoría no tiene subcategorías - permitir guardar
                    categoriaTieneSubcategorias = false;
                    actualizarBotonGuardar(true, '');
                    mostrarAdvertenciaSubcategorias(false);
                }
            })
            .catch(error => {
                console.error('Error verificando subcategorías:', error);
                // En caso de error, permitir guardar (mejor permitir que bloquear)
                categoriaTieneSubcategorias = false;
                actualizarBotonGuardar(true, '');
                mostrarAdvertenciaSubcategorias(false);
            });
        }

        // Función para actualizar el estado del botón de guardar
        function validarPalabrasExigidas() {
            const input = document.getElementById('input_palabras_exigidas');
            if (!input) {
                return { valido: true, mensaje: '' };
            }
            if ((input.value || '').trim() === '') {
                return { valido: false, mensaje: 'Debes rellenar las palabras exigidas.' };
            }
            return { valido: true, mensaje: '' };
        }
        window.validarPalabrasExigidas = validarPalabrasExigidas;

        function actualizarBotonGuardar(habilitado, mensaje) {
            const btnGuardar = document.getElementById('btn_guardar');
            if (!btnGuardar) return;

            if (habilitado) {
                const palabras = validarPalabrasExigidas();
                if (!palabras.valido) {
                    habilitado = false;
                    mensaje = palabras.mensaje;
                }
            }

            // Si se pide habilitar, comprobar formato Neo solo en campos rellenados
            if (habilitado && typeof window.validarNeo === 'function') {
                const r = window.validarNeo();
                if (!r.valido) {
                    habilitado = false;
                    mensaje = r.mensaje;
                }
            }

            if (habilitado) {
                btnGuardar.disabled = false;
                btnGuardar.className = 'inline-flex items-center bg-pink-600 hover:bg-pink-700 text-white font-semibold text-base px-6 py-3 rounded-md shadow-md transition duration-150 ease-in-out focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-pink-500';
                btnGuardar.title = '';
            } else {
                btnGuardar.disabled = true;
                btnGuardar.className = 'inline-flex items-center bg-gray-400 cursor-not-allowed text-white font-semibold text-base px-6 py-3 rounded-md shadow-md transition duration-150 ease-in-out focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500';
                btnGuardar.title = mensaje || 'Debes seleccionar la última categoría de la jerarquía';
            }
        }

        function reevaluarEstadoBotonGuardar() {
            const palabras = validarPalabrasExigidas();
            if (!palabras.valido) {
                actualizarBotonGuardar(false, palabras.mensaje);
                return;
            }
            if (typeof window.validarNeo === 'function') {
                const r = window.validarNeo();
                if (!r.valido) {
                    actualizarBotonGuardar(false, r.mensaje);
                    return;
                }
            }
            const categoriaId = document.getElementById('categoria-final')?.value;
            if (categoriaId && typeof verificarCategoriaParaGuardar === 'function') {
                verificarCategoriaParaGuardar(categoriaId);
                return;
            }
            actualizarBotonGuardar(true, '');
        }
        window.reevaluarEstadoBotonGuardar = reevaluarEstadoBotonGuardar;

        // Función para mostrar/ocultar la advertencia de subcategorías
        function mostrarAdvertenciaSubcategorias(mostrar) {
            const advertencia = document.getElementById('categoria-advertencia-subcategorias');
            if (advertencia) {
                if (mostrar) {
                    advertencia.classList.remove('hidden');
                } else {
                    advertencia.classList.add('hidden');
                }
            }
        }

        // Variable global para controlar si la categoría tiene subcategorías
        let categoriaTieneSubcategorias = false;

        // Función para configurar la validación del formulario
        function configurarValidacionFormulario() {
            const form = document.querySelector('form');
            if (!form) return;

            form.addEventListener('submit', function(e) {
                const categoriaId = document.getElementById('categoria-final').value;
                
                if (!categoriaId) {
                    e.preventDefault();
                    alert('Debes seleccionar una categoría para el producto.');
                    return false;
                }

                // Verificar si la categoría tiene subcategorías (verificación síncrona antes de enviar)
                if (categoriaTieneSubcategorias) {
                    e.preventDefault();
                    alert('No puedes guardar el producto. La categoría seleccionada tiene subcategorías disponibles. Debes seleccionar la última categoría de la jerarquía (sin subcategorías).');
                    
                    // Hacer scroll a la sección de categorías
                    const fieldsetCategoria = document.querySelector('fieldset legend');
                    if (fieldsetCategoria) {
                        fieldsetCategoria.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                    
                    return false;
                }

                // Validación Neo: solo formato en campos rellenados (Neo es opcional)
                if (typeof window.validarNeo === 'function') {
                    const r = window.validarNeo();
                    if (!r.valido) {
                        e.preventDefault();
                        alert(r.mensaje);
                        const neoSection = document.querySelector('#neoobjetivo-list');
                        if (neoSection) neoSection.closest('fieldset').scrollIntoView({ behavior: 'smooth', block: 'center' });
                        return false;
                    }
                }

                const palabras = validarPalabrasExigidas();
                if (!palabras.valido) {
                    e.preventDefault();
                    alert(palabras.mensaje);
                    const peInput = document.getElementById('input_palabras_exigidas');
                    if (peInput) peInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    return false;
                }
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            const inputPalabrasExigidas = document.getElementById('input_palabras_exigidas');
            if (inputPalabrasExigidas) {
                inputPalabrasExigidas.addEventListener('input', function() {
                    if (typeof window.reevaluarEstadoBotonGuardar === 'function') {
                        window.reevaluarEstadoBotonGuardar();
                    } else if (typeof actualizarBotonGuardar === 'function') {
                        const palabras = validarPalabrasExigidas();
                        actualizarBotonGuardar(palabras.valido, palabras.mensaje);
                    }
                });
                if (typeof window.reevaluarEstadoBotonGuardar === 'function') {
                    window.reevaluarEstadoBotonGuardar();
                } else {
                    const palabras = validarPalabrasExigidas();
                    actualizarBotonGuardar(palabras.valido, palabras.mensaje);
                }
            }
            const fieldsetInfo = inputPalabrasExigidas?.closest('fieldset');
            if (fieldsetInfo) {
                const tooltipBtns = fieldsetInfo.querySelectorAll('.tooltip-btn[data-tooltip]');
                tooltipBtns.forEach(btn => {
                    let tooltipPalabrasActual = null;
                    btn.addEventListener('click', function(e) {
                        e.stopPropagation();
                        const tooltipTexto = this.getAttribute('data-tooltip');
                        if (tooltipPalabrasActual) {
                            tooltipPalabrasActual.remove();
                            tooltipPalabrasActual = null;
                            return;
                        }
                        const tooltip = document.createElement('div');
                        tooltip.className = 'fixed z-50 bg-gray-900 text-white text-xs rounded shadow-lg p-3 max-w-sm pointer-events-none leading-relaxed';
                        tooltip.textContent = tooltipTexto;
                        document.body.appendChild(tooltip);
                        const rect = this.getBoundingClientRect();
                        tooltip.style.left = Math.max(8, rect.left) + 'px';
                        tooltip.style.top = (rect.top - tooltip.offsetHeight - 8) + 'px';
                        if (tooltip.getBoundingClientRect().top < 8) {
                            tooltip.style.top = (rect.bottom + 8) + 'px';
                        }
                        tooltipPalabrasActual = tooltip;
                        const cerrar = (ev) => {
                            if (tooltipPalabrasActual && ev.target !== btn && !tooltipPalabrasActual.contains(ev.target)) {
                                tooltipPalabrasActual.remove();
                                tooltipPalabrasActual = null;
                                document.removeEventListener('click', cerrar);
                            }
                        };
                        setTimeout(() => document.addEventListener('click', cerrar), 0);
                    });
                });
            }
        });

        // Función para actualizar automáticamente los campos de categoría
        function actualizarCamposCategoriaAutomaticos(categoriaId, nombreCategoria = null) {
            // Si no tenemos el nombre, obtenerlo de la jerarquía
            if (!nombreCategoria) {
                obtenerNombreCategoria(categoriaId).then(nombre => {
                    if (nombre) {
                        actualizarCamposConNombre(categoriaId, nombre);
                    }
                });
            } else {
                actualizarCamposConNombre(categoriaId, nombreCategoria);
            }
        }

        // Función para obtener el nombre de una categoría
        async function obtenerNombreCategoria(categoriaId) {
            try {
                const response = await fetch(`/panel-privado/categorias/${categoriaId}/jerarquia`, {
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    }
                });
                const data = await response.json();
                if (data.jerarquia && data.jerarquia.length > 0) {
                    const ultimaCategoria = data.jerarquia[data.jerarquia.length - 1];
                    return ultimaCategoria.nombre;
                }
            } catch (error) {
                console.error('Error obteniendo nombre de categoría:', error);
            }
            return null;
        }

        // Función para actualizar los campos con el nombre
        function actualizarCamposConNombre(categoriaId, nombreCategoria) {
            // Actualizar campo de especificaciones internas
            const categoriaEspecificacionesId = document.getElementById('categoria_especificaciones_id');
            const categoriaEspecificacionesNombre = document.getElementById('categoria_especificaciones_nombre');
            if (categoriaEspecificacionesId && categoriaEspecificacionesNombre) {
                const categoriaActualId = categoriaEspecificacionesId.value;
                const categoriaActualNombre = categoriaEspecificacionesNombre.value.trim();
                
                // Verificar si el campo tiene el foco (usuario escribiendo)
                const tieneFoco = document.activeElement === categoriaEspecificacionesNombre;
                
                // Solo actualizar automáticamente si:
                // 1. El campo está completamente vacío (sin ID y sin nombre)
                // 2. O si la categoría es diferente Y el usuario NO está escribiendo
                if ((!categoriaActualId && !categoriaActualNombre) || 
                    (categoriaActualId !== categoriaId.toString() && !tieneFoco)) {
                    // Actualizar los campos
                    categoriaEspecificacionesId.value = categoriaId;
                    categoriaEspecificacionesNombre.value = nombreCategoria;
                    categoriaEspecificacionesNombre.classList.add('border-green-500');
                    
                    // Cargar las especificaciones internas de la categoría con un pequeño delay
                    // para asegurar que el DOM esté actualizado
                    setTimeout(() => {
                        if (typeof obtenerEspecificacionesInternas === 'function') {
                            obtenerEspecificacionesInternas(categoriaId);
                        }
                    }, 100);
                } else if (categoriaActualId === categoriaId.toString() && !tieneFoco) {
                    // Si el ID ya es el mismo pero no se cargaron las especificaciones, cargarlas
                    // Verificar si hay especificaciones cargadas
                    const seleccionContainer = document.getElementById('especificaciones-internas-seleccion');
                    if (seleccionContainer && seleccionContainer.classList.contains('hidden')) {
                        // No hay especificaciones cargadas, cargarlas
                        setTimeout(() => {
                            if (typeof obtenerEspecificacionesInternas === 'function') {
                                obtenerEspecificacionesInternas(categoriaId);
                            }
                        }, 100);
                    }
                }
            }

            // Actualizar campo de productos relacionados
            // IMPORTANTE: Solo actualizar si el campo está completamente vacío
            // Si el usuario ya ha seleccionado una categoría o está escribiendo, no interferir
            const categoriaRelacionadosId = document.getElementById('categoria_relacionados_id');
            const categoriaRelacionadosNombre = document.getElementById('categoria_relacionados_nombre');
            if (categoriaRelacionadosId && categoriaRelacionadosNombre) {
                const categoriaActualId = categoriaRelacionadosId.value;
                const categoriaActualNombre = categoriaRelacionadosNombre.value.trim();
                // Verificar si el campo tiene el foco (usuario escribiendo) o si ya tiene un valor diferente
                const tieneFoco = document.activeElement === categoriaRelacionadosNombre;
                const tieneValor = categoriaActualId || categoriaActualNombre;
                
                // Solo actualizar automáticamente si:
                // 1. El campo está completamente vacío (sin ID y sin nombre)
                // 2. El usuario NO está escribiendo en el campo (no tiene el foco)
                if (!tieneValor && !tieneFoco) {
                    categoriaRelacionadosId.value = categoriaId;
                    categoriaRelacionadosNombre.value = nombreCategoria;
                    categoriaRelacionadosNombre.classList.add('border-green-500');
                    
                    // Cargar las palabras clave relacionadas
                    setTimeout(() => {
                        if (typeof cargarPalabrasClaveRelacionadas === 'function') {
                            cargarPalabrasClaveRelacionadas(categoriaId);
                        }
                    }, 100);
                } else if (categoriaActualId !== categoriaId.toString() && !tieneFoco) {
                    // Si la categoría cambió pero el usuario no está escribiendo, solo cargar palabras clave
                    // sin actualizar el campo visualmente
                    setTimeout(() => {
                        if (typeof cargarPalabrasClaveRelacionadas === 'function') {
                            cargarPalabrasClaveRelacionadas(categoriaId);
                        }
                    }, 100);
                }
            }
        }

        function actualizarIndicadorBotonAgregar() {
            const btnAgregar = document.getElementById('agregar-categoria');
            if (!btnAgregar) return;

            let ultimaCategoriaId = null;

            // Si estamos en buscador, usar categoriaProducto
            if (pestañaActiva === 'buscador' && categoriaProducto) {
                ultimaCategoriaId = categoriaProducto;
            } else {
                // Si estamos en manual, buscar en los selectores
                const selectores = document.querySelectorAll('.categoria-select');
                for (let i = selectores.length - 1; i >= 0; i--) {
                    if (selectores[i].value) {
                        ultimaCategoriaId = selectores[i].value;
                        break;
                    }
                }
            }

            if (ultimaCategoriaId) {
                // Verificar si tiene subcategorías
                verificarSubcategoriasDisponibles(ultimaCategoriaId, btnAgregar);
            } else {
                // No hay categoría seleccionada
                btnAgregar.disabled = true;
                btnAgregar.className = 'bg-gray-400 cursor-not-allowed text-white font-semibold px-4 py-2 rounded-md transition duration-150 ease-in-out focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500';
                btnAgregar.title = 'Primero debes seleccionar una categoría';
            }
        }

        function verificarSubcategoriasDisponibles(categoriaId, btnAgregar) {
            fetch(`/panel-privado/categorias/${categoriaId}/subcategorias`, {
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            })
            .then(res => res.json())
            .then(subcategorias => {
                if (subcategorias && subcategorias.length > 0) {
                    // Hay subcategorías disponibles
                    btnAgregar.disabled = false;
                    btnAgregar.className = 'bg-green-600 hover:bg-green-700 text-white font-semibold px-4 py-2 rounded-md transition duration-150 ease-in-out focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500';
                    btnAgregar.title = `Agregar nivel de subcategoría (${subcategorias.length} opciones disponibles)`;
                } else {
                    // No hay subcategorías disponibles
                    btnAgregar.disabled = true;
                    btnAgregar.className = 'bg-gray-400 cursor-not-allowed text-white font-semibold px-4 py-2 rounded-md transition duration-150 ease-in-out focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500';
                    btnAgregar.title = 'Esta categoría no tiene subcategorías disponibles';
                }
            })
            .catch(error => {
                console.error('Error verificando subcategorías:', error);
                btnAgregar.disabled = true;
                btnAgregar.className = 'bg-gray-400 cursor-not-allowed text-white font-semibold px-4 py-2 rounded-md transition duration-150 ease-in-out focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500';
                btnAgregar.title = 'Error al verificar subcategorías';
            });
        }
    </script>
    {{--AÑADIR O QUITAR CAMPOS DE PALABRAS CLAVE DE RELACIONADOS --}}
    <script>
        const relacionadosList = document.getElementById('relacionados-list');
        
        // Función para crear un item de palabra clave relacionada
        function crearItemRelacionado(valor = '') {
            const item = document.createElement('div');
            item.className = 'flex items-center gap-2 mb-2 relacionado-item';
            
            const input = document.createElement('input');
            input.type = 'text';
            input.name = 'keys_relacionados[]';
            input.className = 'flex-1 px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border';
            input.value = valor;
            
            const btnEliminar = document.createElement('button');
            btnEliminar.type = 'button';
            btnEliminar.className = 'btn-eliminar-relacionado px-3 py-2 bg-red-600 hover:bg-red-700 text-white rounded text-sm transition-colors';
            btnEliminar.title = 'Eliminar esta palabra clave';
            btnEliminar.innerHTML = `
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            `;
            btnEliminar.addEventListener('click', function() {
                eliminarItemRelacionado(item);
            });
            
            item.appendChild(input);
            item.appendChild(btnEliminar);
            
            return item;
        }
        
        // Función para eliminar un item específico
        function eliminarItemRelacionado(item, forzarEliminacion = false) {
            if (!item) {
                return; // El item no existe
            }
            
            const input = item.querySelector('input[name="keys_relacionados[]"]');
            const palabraEliminada = input ? input.value.trim() : '';
            const inputVacio = !palabraEliminada; // Verificar si el input está vacío
            
            // Contar items antes de eliminar
            const items = relacionadosList.querySelectorAll('.relacionado-item');
            const totalItems = items.length;
            
            // No permitir eliminar si solo queda un item (a menos que se fuerce)
            if (!forzarEliminacion && totalItems <= 1) {
                // Si solo queda uno, limpiar su valor en lugar de eliminarlo
                if (input) {
                    input.value = '';
                }
                // Actualizar el botón de palabra clave si se eliminó una palabra
                if (palabraEliminada) {
                    actualizarBotonPalabraClave(palabraEliminada, false);
                }
                return;
            }
            
            // Verificar que el item todavía tiene un padre antes de eliminar
            if (!item.parentNode) {
                return; // El item ya fue eliminado
            }
            
            // Eliminar el item
            item.remove();
            
            // Actualizar el botón de palabra clave si se eliminó una palabra
            if (palabraEliminada) {
                actualizarBotonPalabraClave(palabraEliminada, false);
            }
            
            // Asegurar que siempre haya al menos un input vacío
            // Solo verificar si necesitamos crear uno nuevo si eliminamos un item con contenido
            // o si eliminamos un item vacío pero no quedan más items
            const itemsRestantes = relacionadosList.querySelectorAll('.relacionado-item');
            if (itemsRestantes.length === 0) {
                // Si no quedan items, crear uno nuevo
                const nuevoItem = crearItemRelacionado('');
                relacionadosList.appendChild(nuevoItem);
            } else if (!inputVacio) {
                // Si el item eliminado tenía contenido, verificar si hay algún input vacío
                let tieneVacio = false;
                itemsRestantes.forEach(itemRestante => {
                    const inputRestante = itemRestante.querySelector('input[name="keys_relacionados[]"]');
                    if (inputRestante && !inputRestante.value.trim()) {
                        tieneVacio = true;
                    }
                });
                
                if (!tieneVacio) {
                    const nuevoItem = crearItemRelacionado('');
                    relacionadosList.appendChild(nuevoItem);
                }
            }
        }
        
        // Función para actualizar el estado de un botón de palabra clave
        function actualizarBotonPalabraClave(palabra, tiene) {
            const botonesContainer = document.getElementById('palabras-clave-relacionadas-botones');
            if (!botonesContainer) return;
            
            const contenedorBoton = Array.from(botonesContainer.querySelectorAll('div')).find(div => {
                const boton = div.querySelector('button[data-palabra]');
                return boton && boton.dataset.palabra === palabra;
            });
            
            if (contenedorBoton) {
                const boton = contenedorBoton.querySelector('button[data-palabra]');
                if (boton) {
                    if (tiene) {
                        boton.className = 'px-3 py-1 rounded-md text-sm font-medium transition-colors bg-green-600 hover:bg-green-700 text-white';
                        boton.dataset.tiene = 'true';
                    } else {
                        boton.className = 'px-3 py-1 rounded-md text-sm font-medium transition-colors bg-gray-400 hover:bg-gray-500 text-white';
                        boton.dataset.tiene = 'false';
                    }
                }
            }
        }
        
        // Añadir event listeners a los botones de eliminar existentes
        document.addEventListener('DOMContentLoaded', function() {
            // Usar delegación de eventos para manejar botones dinámicos
            const relacionadosList = document.getElementById('relacionados-list');
            if (relacionadosList) {
                relacionadosList.addEventListener('click', function(e) {
                    if (e.target.closest('.btn-eliminar-relacionado')) {
                        const item = e.target.closest('.relacionado-item');
                        if (item) {
                            eliminarItemRelacionado(item);
                        }
                    }
                });
            }
            
            // También añadir listeners a los botones existentes al cargar
            const botonesEliminar = document.querySelectorAll('.btn-eliminar-relacionado');
            botonesEliminar.forEach(btn => {
                btn.addEventListener('click', function() {
                    const item = this.closest('.relacionado-item');
                    if (item) {
                        eliminarItemRelacionado(item);
                    }
                });
            });
        });
        
        // Botón para añadir nuevo relacionado
        const btnAddRelacionado = document.getElementById('add-relacionado');
        if (btnAddRelacionado) {
            btnAddRelacionado.onclick = () => {
                const nuevoItem = crearItemRelacionado('');
                relacionadosList.appendChild(nuevoItem);
            };
        }
    </script>
    {{-- BUSCAR PRODUCTOS RELACIONADOS CON LAS PALABRAS CLAVES QUE TENGO ESCRITAS--}}
    <script>
        document.getElementById('buscar-relacionados').addEventListener('click', () => {
            const inputs = document.querySelectorAll('input[name="keys_relacionados[]"]');
            const keywords = Array.from(inputs)
                .map(input => input.value.trim())
                .filter(val => val !== '');

            const productoId = document.getElementById('form-producto').dataset.productoId || null;

            if (keywords.length === 0) {
                document.getElementById('relacionados-resultado').textContent = 'Introduce al menos una palabra clave.';
                return;
            }

            fetch('/panel-privado/productos/buscar-relacionados', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        keywords,
                        producto_id: productoId
                    })
                })
                .then(res => res.json())
                .then(data => {
                    const resultado = document.getElementById('relacionados-resultado');

                    if (data.total === 0) {
                        resultado.innerHTML = '❌ Coincidencias encontradas: 0';
                        resultado.style.color = 'red';
                    } else if (data.total > 4) {
                        resultado.innerHTML = '✅ Coincidencias encontradas: ' + data.total;
                        resultado.style.color = 'green';
                    } else {
                        resultado.textContent = 'Coincidencias encontradas: ' + data.total;
                        resultado.style.color = '';
                    }

                })
                .catch(() => {
                    document.getElementById('relacionados-resultado').textContent = 'Error al buscar productos.';
                });
        });
    </script>
    {{-- NEO / NEOOBJETIVO: URLs objetivo, validación, add/remove --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const neoList = document.getElementById('neoobjetivo-list');
            if (!neoList) return;

            function isValidUrl(str) {
                try {
                    const u = new URL(str);
                    return u.protocol === 'http:' || u.protocol === 'https:';
                } catch {
                    return false;
                }
            }

            function isNoEncontrado(val) {
                return (val || '').trim().toLowerCase() === 'no encontrado';
            }

            /** Valida la sección Neo: opcional; solo comprueba formato en campos rellenados. */
            function validarNeo() {
                const inputs = neoList.querySelectorAll('.neoobjetivo-url');
                const valores = Array.from(inputs).map(i => (i.value || '').trim());
                const algunInvalido = valores.some(v => v !== '' && !isValidUrl(v) && !isNoEncontrado(v));
                if (algunInvalido) {
                    return { valido: false, mensaje: 'Cada campo Neo rellenado debe contener una URL válida o "No encontrado". Revisa los campos marcados en rojo.' };
                }
                return { valido: true, mensaje: '' };
            }
            window.validarNeo = validarNeo;

            function actualizarBotonGuardarSegunNeo() {
                if (typeof window.reevaluarEstadoBotonGuardar === 'function') {
                    window.reevaluarEstadoBotonGuardar();
                }
            }

            function actualizarBordeUrl(input) {
                const val = (input.value || '').trim();
                if (val === '') {
                    input.classList.remove('border-red-500');
                    input.classList.add('border-gray-300', 'dark:border-gray-600');
                } else if (isValidUrl(val) || isNoEncontrado(val)) {
                    input.classList.remove('border-red-500');
                    input.classList.add('border-gray-300', 'dark:border-gray-600');
                } else {
                    input.classList.add('border-red-500');
                    input.classList.remove('border-gray-300', 'dark:border-gray-600');
                }
                actualizarBotonGuardarSegunNeo();
            }

            function crearFilaNeoobjetivo(index, url = '', visitada = '', id = '') {
                const div = document.createElement('div');
                div.className = 'flex items-center gap-2 mb-2 neoobjetivo-item flex-wrap';
                const urlClass = 'neoobjetivo-url flex-1 min-w-[200px] px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border border-gray-300 dark:border-gray-600';
                const idLabelClass = id
                    ? 'neoobjetivo-id-label text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap font-mono'
                    : 'neoobjetivo-id-label text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap font-mono hidden';
                div.innerHTML = `
                    <input type="hidden" name="neoobjetivo[${index}][id]" value="${id || ''}">
                    <span class="${idLabelClass}" title="ID neoobjetivo">${id ? '#' + id : '#'}</span>
                    <input type="text" name="neoobjetivo[${index}][url]" value="${url}" class="${urlClass}" placeholder="https://...">
                    <input type="datetime-local" name="neoobjetivo[${index}][visitada]" value="${visitada}" class="neoobjetivo-visitada w-44 px-3 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border border-gray-300 dark:border-gray-600">
                    <button type="button" class="btn-eliminar-neoobjetivo px-3 py-2 bg-red-600 hover:bg-red-700 text-white rounded text-sm transition-colors" title="Eliminar esta URL">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                `;
                const urlInput = div.querySelector('.neoobjetivo-url');
                const btnEliminar = div.querySelector('.btn-eliminar-neoobjetivo');
                urlInput.addEventListener('input', () => actualizarBordeUrl(urlInput));
                urlInput.addEventListener('blur', () => actualizarBordeUrl(urlInput));
                actualizarBordeUrl(urlInput);
                btnEliminar.addEventListener('click', function() {
                    const items = neoList.querySelectorAll('.neoobjetivo-item');
                    if (items.length <= 1) {
                        urlInput.value = '';
                        div.querySelector('input[name*="[visitada]"]').value = '';
                        div.querySelector('input[name*="[id]"]').value = '';
                        const idLabel = div.querySelector('.neoobjetivo-id-label');
                        if (idLabel) {
                            idLabel.textContent = '#';
                            idLabel.classList.add('hidden');
                        }
                        actualizarBordeUrl(urlInput);
                        return;
                    }
                    div.remove();
                });
                return div;
            }

            document.getElementById('add-neoobjetivo').addEventListener('click', function() {
                const index = neoList.querySelectorAll('.neoobjetivo-item').length;
                neoList.appendChild(crearFilaNeoobjetivo(index, '', '', ''));
                actualizarBotonGuardarSegunNeo();
            });

            neoList.addEventListener('click', function(e) {
                if (e.target.closest('.btn-eliminar-neoobjetivo')) {
                    const item = e.target.closest('.neoobjetivo-item');
                    if (item && neoList.querySelectorAll('.neoobjetivo-item').length > 1) {
                        item.remove();
                        actualizarBotonGuardarSegunNeo();
                    }
                }
            });

            neoList.querySelectorAll('.neoobjetivo-url').forEach(input => {
                input.addEventListener('input', () => actualizarBordeUrl(input));
                input.addEventListener('blur', () => actualizarBordeUrl(input));
                actualizarBordeUrl(input);
            });
        });
    </script>
    {{-- BUSCADOR DE CATEGORÍAS PARA PRODUCTOS RELACIONADOS --}}
    <script>
        let categoriasActuales = [];
        let indiceSeleccionadoCategoria = -1;

        // Función para buscar categorías en tiempo real
        async function buscarCategorias(query) {
            if (query.length < 2) {
                ocultarSugerenciasCategoria();
                return;
            }

            try {
                const response = await fetch(`/panel-privado/productos/buscar/categorias?q=${encodeURIComponent(query)}`);
                const categorias = await response.json();
                categoriasActuales = categorias;
                mostrarSugerenciasCategoria(categorias);
            } catch (error) {
                console.error('Error al buscar categorías:', error);
            }
        }

        // Función para mostrar sugerencias de categorías
        function mostrarSugerenciasCategoria(categorias) {
            const contenedor = document.getElementById('categoria_relacionados_sugerencias');
            contenedor.innerHTML = '';

            if (categorias.length === 0) {
                contenedor.innerHTML = '<div class="px-4 py-2 text-white font-bold">No se encontraron categorías</div>';
                contenedor.classList.remove('hidden');
                return;
            }

            categorias.forEach((categoria, index) => {
                const div = document.createElement('div');
                div.className = `px-4 py-2 cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700 text-white font-bold ${index === indiceSeleccionadoCategoria ? 'bg-blue-100 dark:bg-blue-800' : ''}`;
                div.textContent = categoria.nombre;
                div.onclick = () => seleccionarCategoria(categoria);
                contenedor.appendChild(div);
            });

            contenedor.classList.remove('hidden');
        }

        // Función para ocultar sugerencias de categorías
        function ocultarSugerenciasCategoria() {
            const contenedor = document.getElementById('categoria_relacionados_sugerencias');
            contenedor.classList.add('hidden');
            indiceSeleccionadoCategoria = -1;
        }

        // Función para seleccionar una categoría
        function seleccionarCategoria(categoria) {
            document.getElementById('categoria_relacionados_id').value = categoria.id;
            document.getElementById('categoria_relacionados_nombre').value = categoria.nombre;
            ocultarSugerenciasCategoria();
            
            // Debug: verificar que se está guardando correctamente
            console.log('Categoría seleccionada:', {
                id: categoria.id,
                nombre: categoria.nombre,
                campo_id: document.getElementById('categoria_relacionados_id').value,
                campo_nombre: document.getElementById('categoria_relacionados_nombre').value
            });
            
            // Limpiar cualquier error previo
            const errorElement = document.querySelector('[data-field="id_categoria_productos_relacionados"]');
            if (errorElement) {
                errorElement.remove();
            }
            
            // Cargar palabras clave relacionadas
            cargarPalabrasClaveRelacionadas(categoria.id);
        }
        
        // Función para cargar palabras clave relacionadas
        async function cargarPalabrasClaveRelacionadas(categoriaId) {
            const contenedor = document.getElementById('palabras-clave-relacionadas-container');
            const botonesContainer = document.getElementById('palabras-clave-relacionadas-botones');
            const filtroInput = document.getElementById('palabras-clave-relacionadas-filtro');
            const productoId = document.getElementById('form-producto').dataset.productoId || null;
            
            // Mostrar contenedor
            contenedor.classList.remove('hidden');
            if (filtroInput) {
                filtroInput.value = '';
            }
            botonesContainer.innerHTML = '<div class="text-sm text-gray-500">Cargando palabras clave...</div>';
            
            try {
                const url = `/panel-privado/productos/categoria/${categoriaId}/palabras-clave-relacionadas${productoId ? `?producto_id=${productoId}` : ''}`;
                const response = await fetch(url);
                const data = await response.json();
                
                if (data.success && data.palabras_clave && data.palabras_clave.length > 0) {
                    mostrarPalabrasClaveRelacionadas(data.palabras_clave);
                } else {
                    botonesContainer.innerHTML = '<div class="text-sm text-gray-500">No se encontraron palabras clave en esta categoría</div>';
                }
            } catch (error) {
                console.error('Error al cargar palabras clave relacionadas:', error);
                botonesContainer.innerHTML = '<div class="text-sm text-red-500">Error al cargar las palabras clave</div>';
            }
        }
        
        // Filtrar botones de palabras clave según el texto del textarea
        function filtrarPalabrasClaveRelacionadas() {
            const filtroInput = document.getElementById('palabras-clave-relacionadas-filtro');
            const botonesContainer = document.getElementById('palabras-clave-relacionadas-botones');
            if (!filtroInput || !botonesContainer) return;

            const termino = filtroInput.value.trim().toLowerCase();
            const items = botonesContainer.querySelectorAll('.palabra-clave-relacionada-item');

            items.forEach(item => {
                const boton = item.querySelector('button[data-palabra]');
                const palabra = (boton?.dataset.palabra || '').toLowerCase();
                const coincide = !termino || palabra.includes(termino);
                item.classList.toggle('hidden', !coincide);
            });
        }

        // Función para mostrar las palabras clave como botones
        function mostrarPalabrasClaveRelacionadas(palabrasClave) {
            const botonesContainer = document.getElementById('palabras-clave-relacionadas-botones');
            botonesContainer.innerHTML = '';
            
            palabrasClave.forEach(palabraData => {
                // Contenedor para el botón y el icono
                const contenedorBoton = document.createElement('div');
                contenedorBoton.className = 'palabra-clave-relacionada-item inline-flex items-center gap-1';
                
                const boton = document.createElement('button');
                boton.type = 'button';
                boton.className = `px-3 py-1 rounded-md text-sm font-medium transition-colors ${
                    palabraData.tiene_producto_actual 
                        ? 'bg-green-600 hover:bg-green-700 text-white' 
                        : 'bg-gray-400 hover:bg-gray-500 text-white'
                }`;
                boton.textContent = `${palabraData.palabra} (${palabraData.count})`;
                boton.dataset.palabra = palabraData.palabra;
                boton.dataset.tiene = palabraData.tiene_producto_actual ? 'true' : 'false';
                
                boton.addEventListener('click', () => {
                    // Leer el estado actual del botón en lugar del valor inicial
                    const tieneActual = boton.dataset.tiene === 'true';
                    togglePalabraClave(palabraData.palabra, tieneActual);
                });
                
                // Botón/icono para ver productos
                const btnVerProductos = document.createElement('button');
                btnVerProductos.type = 'button';
                btnVerProductos.className = 'text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200 p-1 rounded transition-colors';
                btnVerProductos.title = 'Ver productos con esta palabra clave';
                btnVerProductos.innerHTML = `
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                    </svg>
                `;
                btnVerProductos.addEventListener('click', (e) => {
                    e.stopPropagation(); // Evitar que se active el toggle del botón principal
                    abrirModalProductosPalabraClave(palabraData.palabra, palabraData.count);
                });
                
                contenedorBoton.appendChild(boton);
                contenedorBoton.appendChild(btnVerProductos);
                botonesContainer.appendChild(contenedorBoton);
            });
            
            // Resaltar botones de palabras clave que coinciden con sublíneas marcadas
            resaltarBotonesPalabrasClaveMarcadas();
            filtrarPalabrasClaveRelacionadas();
        }
        
        // Función para abrir el modal de productos de una palabra clave
        async function abrirModalProductosPalabraClave(palabraClave, count) {
            const modal = document.getElementById('modal-productos-palabra-clave');
            const titulo = document.getElementById('modal-productos-palabra-clave-titulo');
            const contenido = document.getElementById('modal-productos-palabra-clave-contenido');
            const categoriaId = document.getElementById('categoria_relacionados_id').value;
            const productoId = document.getElementById('form-producto').dataset.productoId || null;
            
            if (!categoriaId) {
                alert('Debes seleccionar una categoría primero');
                return;
            }
            
            titulo.textContent = `Productos con "${palabraClave}" (${count})`;
            contenido.innerHTML = '<div class="text-center py-4"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div><p class="mt-2 text-gray-600">Cargando productos...</p></div>';
            modal.classList.remove('hidden');
            
            try {
                const url = `/panel-privado/productos/categoria/${categoriaId}/palabra-clave/${encodeURIComponent(palabraClave)}/productos${productoId ? `?producto_id=${productoId}` : ''}`;
                const response = await fetch(url);
                const data = await response.json();
                
                if (data.success && data.productos && data.productos.length > 0) {
                    mostrarProductosEnModal(data.productos, contenido);
                } else {
                    contenido.innerHTML = '<div class="text-center py-4 text-gray-500">No se encontraron productos con esta palabra clave</div>';
                }
            } catch (error) {
                console.error('Error al cargar productos:', error);
                contenido.innerHTML = '<div class="text-center py-4 text-red-500">Error al cargar los productos</div>';
            }
        }
        
        // Función para mostrar productos en el modal
        function mostrarProductosEnModal(productos, contenido) {
            contenido.innerHTML = '';
            
            const lista = document.createElement('div');
            lista.className = 'space-y-2 max-h-96 overflow-y-auto';
            
            productos.forEach(producto => {
                const item = document.createElement('div');
                item.className = 'flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors';
                
                const nombre = document.createElement('div');
                nombre.className = 'flex-1 text-gray-800 dark:text-gray-200';
                nombre.textContent = producto.nombre;
                
                const acciones = document.createElement('div');
                acciones.className = 'flex gap-2';
                
                const btnIr = document.createElement('a');
                btnIr.href = producto.url_producto;
                btnIr.target = '_blank';
                btnIr.className = 'px-3 py-1 bg-green-600 hover:bg-green-700 text-white text-sm rounded transition-colors';
                btnIr.textContent = 'Ir';
                
                const btnEditar = document.createElement('a');
                btnEditar.href = producto.url_editar;
                btnEditar.target = '_blank';
                btnEditar.className = 'px-3 py-1 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded transition-colors';
                btnEditar.textContent = 'Editar';
                
                acciones.appendChild(btnIr);
                acciones.appendChild(btnEditar);
                
                item.appendChild(nombre);
                item.appendChild(acciones);
                lista.appendChild(item);
            });
            
            contenido.appendChild(lista);
        }
        
        // Función para cerrar el modal
        window.cerrarModalProductosPalabraClave = function() {
            document.getElementById('modal-productos-palabra-clave').classList.add('hidden');
        };
        
        // Cerrar modal con Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const modal = document.getElementById('modal-productos-palabra-clave');
                if (modal && !modal.classList.contains('hidden')) {
                    cerrarModalProductosPalabraClave();
                }
            }
        });
        
        // Cerrar modal al hacer clic fuera
        document.getElementById('modal-productos-palabra-clave').addEventListener('click', function(e) {
            if (e.target === this) {
                cerrarModalProductosPalabraClave();
            }
        });
        
        // Función para resaltar/quitar resaltado de botones de palabras clave relacionadas
        function resaltarBotonesPalabrasClave(textoSublinea, resaltar) {
            const botonesContainer = document.getElementById('palabras-clave-relacionadas-botones');
            if (!botonesContainer) return;
            
            // Buscar todos los botones de palabras clave
            const botones = botonesContainer.querySelectorAll('button[data-palabra]');
            
            botones.forEach(boton => {
                const palabraClave = boton.dataset.palabra;
                // Comparar el texto de la sublínea con la palabra clave (sin distinguir mayúsculas/minúsculas)
                if (palabraClave && textoSublinea && 
                    palabraClave.trim().toLowerCase() === textoSublinea.trim().toLowerCase()) {
                    if (resaltar) {
                        // Añadir borde amarillo más grueso
                        boton.classList.add('border-4', 'border-yellow-400');
                    } else {
                        // Antes de quitar el resaltado, verificar si hay otros checkboxes marcados con el mismo texto
                        const textoNormalizado = textoSublinea.trim().toLowerCase();
                        const otrosCheckboxesMarcados = Array.from(document.querySelectorAll('.especificacion-checkbox:checked'))
                            .filter(cb => {
                                const textoOtro = cb.dataset.sublineaTexto;
                                return textoOtro && textoOtro.trim().toLowerCase() === textoNormalizado;
                            });
                        
                        // Solo quitar el resaltado si no hay otros checkboxes marcados con el mismo texto
                        if (otrosCheckboxesMarcados.length === 0) {
                            boton.classList.remove('border-4', 'border-yellow-400');
                        }
                    }
                }
            });
        }
        
        // Función para resaltar todos los botones de palabras clave basándose en las sublíneas marcadas
        function resaltarBotonesPalabrasClaveMarcadas() {
            const checkboxesMarcados = document.querySelectorAll('.especificacion-checkbox:checked');
            checkboxesMarcados.forEach(checkbox => {
                const textoSublinea = checkbox.dataset.sublineaTexto;
                if (textoSublinea) {
                    resaltarBotonesPalabrasClave(textoSublinea, true);
                }
            });
        }
        
        // Función para añadir o quitar una palabra clave
        function togglePalabraClave(palabra, tieneActual) {
            const relacionadosList = document.getElementById('relacionados-list');
            const items = relacionadosList.querySelectorAll('.relacionado-item');
            
            // Buscar el botón correspondiente para actualizarlo
            const botonesContainer = document.getElementById('palabras-clave-relacionadas-botones');
            const boton = Array.from(botonesContainer.querySelectorAll('button')).find(btn => 
                btn.dataset.palabra === palabra
            );
            
            if (tieneActual) {
                // Quitar la palabra clave - buscar el item que contiene la palabra
                let itemEncontrado = null;
                items.forEach(item => {
                    const input = item.querySelector('input[name="keys_relacionados[]"]');
                    if (input && input.value.trim() === palabra.trim()) {
                        itemEncontrado = item;
                    }
                });
                
                if (itemEncontrado) {
                    // Verificar si hay más de un item antes de eliminar
                    const totalItems = relacionadosList.querySelectorAll('.relacionado-item').length;
                    if (totalItems > 1) {
                        // Eliminar el item directamente si hay más de uno (forzar eliminación)
                        eliminarItemRelacionado(itemEncontrado, true);
                    } else {
                        // Si solo queda uno, limpiar el input pero mantener la línea
                        const input = itemEncontrado.querySelector('input[name="keys_relacionados[]"]');
                        if (input) {
                            input.value = '';
                        }
                    }
                }
                
                // Actualizar el botón a gris (no tiene)
                if (boton) {
                    boton.className = 'px-3 py-1 rounded-md text-sm font-medium transition-colors bg-gray-400 hover:bg-gray-500 text-white';
                    boton.dataset.tiene = 'false';
                }
            } else {
                // Añadir la palabra clave
                // Buscar si hay un input vacío
                let itemVacio = null;
                items.forEach(item => {
                    const input = item.querySelector('input[name="keys_relacionados[]"]');
                    if (input && !input.value.trim() && !itemVacio) {
                        itemVacio = item;
                    }
                });
                
                if (itemVacio) {
                    const input = itemVacio.querySelector('input[name="keys_relacionados[]"]');
                    if (input) {
                        input.value = palabra;
                    }
                } else {
                    const nuevoItem = crearItemRelacionado(palabra);
                    relacionadosList.appendChild(nuevoItem);
                }
                
                // Actualizar el botón a verde (tiene)
                if (boton) {
                    boton.className = 'px-3 py-1 rounded-md text-sm font-medium transition-colors bg-green-600 hover:bg-green-700 text-white';
                    boton.dataset.tiene = 'true';
                }
            }
        }

        // Event listeners para el buscador de categorías
        document.getElementById('categoria_relacionados_nombre').addEventListener('input', (e) => {
            // Limpiar el ID cuando el usuario empiece a escribir algo nuevo
            document.getElementById('categoria_relacionados_id').value = '';
            // Ocultar contenedor de palabras clave
            document.getElementById('palabras-clave-relacionadas-container').classList.add('hidden');
            buscarCategorias(e.target.value);
        });

        // Auto-seleccionar categoría principal cuando se selecciona la categoría del producto
        function autoSeleccionarCategoriaPrincipal() {
            // Solo si estamos creando un nuevo producto (no editando)
            const esNuevoProducto = !document.getElementById('form-producto').dataset.productoId;
            
            if (esNuevoProducto) {
                const categoriaFinal = document.getElementById('categoria-final');
                if (categoriaFinal && categoriaFinal.value) {
                    // Verificar si ya está seleccionada la misma categoría
                    const categoriaRelacionadosId = document.getElementById('categoria_relacionados_id').value;
                    if (categoriaRelacionadosId === categoriaFinal.value) {
                        return; // Ya está seleccionada, no hacer nada
                    }
                    
                    // Buscar la categoría seleccionada y auto-seleccionarla en productos relacionados
                    fetch(`/panel-privado/productos/buscar/categorias?q=`)
                        .then(response => response.json())
                        .then(categorias => {
                            // Buscar la categoría por ID
                            const categoriaSeleccionada = categorias.find(cat => cat.id == categoriaFinal.value);
                            if (categoriaSeleccionada) {
                                // Auto-seleccionar la categoría
                                document.getElementById('categoria_relacionados_id').value = categoriaSeleccionada.id;
                                document.getElementById('categoria_relacionados_nombre').value = categoriaSeleccionada.nombre;
                                
                                console.log('Categoría principal auto-seleccionada:', categoriaSeleccionada);
                                
                                // Cargar palabras clave relacionadas
                                cargarPalabrasClaveRelacionadas(categoriaSeleccionada.id);
                            }
                        })
                        .catch(error => console.error('Error al auto-seleccionar categoría:', error));
                }
            }
        }
        
        // Cargar palabras clave al iniciar si hay una categoría seleccionada
        document.addEventListener('DOMContentLoaded', function() {
            const categoriaId = document.getElementById('categoria_relacionados_id').value;
            if (categoriaId) {
                setTimeout(() => {
                    cargarPalabrasClaveRelacionadas(categoriaId);
                }, 500);
            }

            const filtroPalabrasClave = document.getElementById('palabras-clave-relacionadas-filtro');
            if (filtroPalabrasClave) {
                filtroPalabrasClave.addEventListener('input', filtrarPalabrasClaveRelacionadas);
            }
        });

        // Observar cambios en la categoría final
        const categoriaFinal = document.getElementById('categoria-final');
        if (categoriaFinal) {
            // Escuchar cambios en el valor del campo
            categoriaFinal.addEventListener('change', function() {
                setTimeout(autoSeleccionarCategoriaPrincipal, 300);
            });
            
            // También observar cambios programáticos en el valor
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.type === 'attributes' && mutation.attributeName === 'value') {
                        setTimeout(autoSeleccionarCategoriaPrincipal, 300);
                    }
                });
            });
            
            observer.observe(categoriaFinal, { attributes: true, attributeFilter: ['value'] });
        }
        

        // Limpiar campo si el usuario sale sin seleccionar
        document.getElementById('categoria_relacionados_nombre').addEventListener('blur', (e) => {
            // Pequeño delay para permitir que se ejecute el click en las sugerencias
            setTimeout(() => {
                const categoriaId = document.getElementById('categoria_relacionados_id').value;
                const categoriaNombre = document.getElementById('categoria_relacionados_nombre').value;
                
                // Si hay texto pero no hay ID válido, limpiar todo
                if (categoriaNombre && !categoriaId) {
                    document.getElementById('categoria_relacionados_nombre').value = '';
                    document.getElementById('categoria_relacionados_id').value = '';
                    // Ocultar contenedor de palabras clave
                    document.getElementById('palabras-clave-relacionadas-container').classList.add('hidden');
                }
            }, 300);
        });

        document.getElementById('categoria_relacionados_nombre').addEventListener('keydown', (e) => {
            const contenedor = document.getElementById('categoria_relacionados_sugerencias');
            
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                indiceSeleccionadoCategoria = Math.min(indiceSeleccionadoCategoria + 1, categoriasActuales.length - 1);
                mostrarSugerenciasCategoria(categoriasActuales);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                indiceSeleccionadoCategoria = Math.max(indiceSeleccionadoCategoria - 1, -1);
                mostrarSugerenciasCategoria(categoriasActuales);
            } else if (e.key === 'Enter') {
                e.preventDefault();
                if (indiceSeleccionadoCategoria >= 0 && categoriasActuales[indiceSeleccionadoCategoria]) {
                    seleccionarCategoria(categoriasActuales[indiceSeleccionadoCategoria]);
                }
            } else if (e.key === 'Escape') {
                ocultarSugerenciasCategoria();
            }
        });

        // Ocultar sugerencias al hacer clic fuera
        document.addEventListener('click', (e) => {
            const contenedor = document.getElementById('categoria_relacionados_sugerencias');
            const input = document.getElementById('categoria_relacionados_nombre');
            
            if (!contenedor.contains(e.target) && !input.contains(e.target)) {
                ocultarSugerenciasCategoria();
                
                // Limpiar campo si hay texto pero no hay ID válido
                setTimeout(() => {
                    const categoriaId = document.getElementById('categoria_relacionados_id').value;
                    const categoriaNombre = document.getElementById('categoria_relacionados_nombre').value;
                    
                    if (categoriaNombre && !categoriaId) {
                        document.getElementById('categoria_relacionados_nombre').value = '';
                        document.getElementById('categoria_relacionados_id').value = '';
                        // Ocultar contenedor de palabras clave
                        document.getElementById('palabras-clave-relacionadas-container').classList.add('hidden');
                    }
                }, 200);
            }
        });

        // Validación antes de enviar el formulario
        document.querySelector('form').addEventListener('submit', function(e) {
            const categoriaId = document.getElementById('categoria_relacionados_id').value;
            const categoriaNombre = document.getElementById('categoria_relacionados_nombre').value;
            const input = document.getElementById('categoria_relacionados_nombre');
            
            // Debug: verificar datos antes de enviar
            console.log('Enviando formulario:', {
                categoriaId: categoriaId,
                categoriaNombre: categoriaNombre,
                campo_id: document.getElementById('categoria_relacionados_id').value,
                campo_nombre: document.getElementById('categoria_relacionados_nombre').value
            });
            
            // Limpiar errores previos
            input.classList.remove('border-red-500');
            const errorMsg = document.querySelector('#categoria-relacionados-error');
            if (errorMsg) {
                errorMsg.remove();
            }
            
            // Validar que el campo esté relleno (obligatorio)
            if (!categoriaId || !categoriaNombre || categoriaNombre.trim() === '') {
                e.preventDefault();
                
                // Marcar el campo en rojo
                input.classList.add('border-red-500');
                
                // Añadir mensaje de error
                const errorDiv = document.createElement('div');
                errorDiv.id = 'categoria-relacionados-error';
                errorDiv.className = 'text-red-500 text-sm mt-1';
                errorDiv.textContent = 'Debe seleccionar una categoría para productos relacionados.';
                
                // Insertar el mensaje después del input
                input.parentNode.insertBefore(errorDiv, input.parentNode.children[2]);
                
                return false;
            }
        });
    </script>
    {{-- BUSCADOR DE CATEGORÍAS PARA ESPECIFICACIONES INTERNAS --}}
    <script>
        let categoriasActualesEspecificaciones = [];
        let indiceSeleccionadoCategoriaEspecificaciones = -1;

        function kpNormalizarTextoCoincidencia(texto) {
            return String(texto || '')
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .replace(/[\u00A0\u202F\u2007\u2009]/g, ' ')
                .toLowerCase()
                .trim();
        }

        /** Unifica comas/puntos decimales (también unicode) entre dígitos: 4,4 → 4.4 */
        function kpNormalizarSeparadoresDecimales(texto) {
            return kpNormalizarTextoCoincidencia(texto).replace(
                /(\d)\s*[,.\uFF0C\u201A]\s*(\d)/g,
                '$1.$2'
            );
        }

        function kpObtenerTextoSublineaEspecificacion(checkbox) {
            if (!checkbox) return '';
            const label = checkbox.closest('label');
            const desdeSpan = label?.querySelector('span')?.textContent?.trim() || '';
            if (desdeSpan) return desdeSpan;
            return checkbox.dataset.sublineaTexto || '';
        }

        function kpEscaparRegexCoincidencia(s) {
            return s.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        }

        function kpGenerarVariantesCoincidencia(textoOpcion) {
            const base = kpNormalizarTextoCoincidencia(textoOpcion);
            const set = new Set();
            if (!base) return [];
            set.add(base);
            const conPunto = kpNormalizarSeparadoresDecimales(textoOpcion);
            set.add(conPunto);
            set.add(kpQuitarCerosDecimalesFinales(conPunto));
            set.add(conPunto.replace(/(\d)\.(\d)/g, '$1,$2'));
            return Array.from(set).filter((v) => v.length > 0);
        }

        /** 4.40 → 4.4, 4,40 → 4.4 (tras normalizar separador decimal) */
        function kpQuitarCerosDecimalesFinales(texto) {
            return String(texto || '')
                .replace(/(\d+\.\d*?)0+$/g, '$1')
                .replace(/(\d+)\.$/g, '$1');
        }

        function kpExtraerValoresDecimales(texto) {
            const norm = kpNormalizarSeparadoresDecimales(texto);
            const valores = [];
            const re = /\d+\.\d+/g;
            let m;
            while ((m = re.exec(norm)) !== null) {
                const n = parseFloat(m[0]);
                if (!Number.isNaN(n)) valores.push(n);
            }
            return valores;
        }

        function kpCoincidenValoresDecimales(token, texto) {
            const valoresToken = kpExtraerValoresDecimales(token);
            if (valoresToken.length === 0) return false;
            const valoresTexto = kpExtraerValoresDecimales(texto);
            if (valoresTexto.length === 0) return false;
            return valoresToken.some((a) =>
                valoresTexto.some((b) => Math.abs(a - b) < 1e-9)
            );
        }

        function kpTokenCoincideConTextoOpcion(token, texto) {
            if (!token || !texto) return false;

            const tokenNorm = kpNormalizarSeparadoresDecimales(token);
            const textoNorm = kpNormalizarSeparadoresDecimales(texto);
            const tokenSinCeros = kpQuitarCerosDecimalesFinales(tokenNorm);
            const textoSinCeros = kpQuitarCerosDecimalesFinales(textoNorm);

            if (kpCoincidenValoresDecimales(token, texto)) {
                return true;
            }

            if (tokenNorm.length >= 1 && textoNorm.includes(tokenNorm)) {
                return true;
            }

            if (
                tokenSinCeros.length >= 1 &&
                (textoNorm.includes(tokenSinCeros) || textoSinCeros.includes(tokenSinCeros))
            ) {
                return true;
            }

            if (tokenNorm.length >= 2 && textoNorm.length >= 1 && tokenNorm.includes(textoNorm)) {
                return true;
            }

            const variantesToken = kpGenerarVariantesCoincidencia(token);
            const variantesTexto = kpGenerarVariantesCoincidencia(texto);

            for (const vt of variantesToken) {
                if (vt.length < 1) continue;
                for (const vx of variantesTexto) {
                    if (vx.includes(vt) || (vt.length >= 2 && vt.includes(vx))) {
                        return true;
                    }
                }
            }

            const textosAmplios = [kpNormalizarTextoCoincidencia(texto), textoNorm];
            for (const vt of variantesToken) {
                if (vt.length < 1) continue;
                for (const tx of textosAmplios) {
                    if (tx.includes(vt)) return true;
                }
                const escaped = kpEscaparRegexCoincidencia(vt);
                const regex = new RegExp(
                    `(?:^|[^a-z0-9])${escaped}(?:[^a-z0-9]|$)`,
                    'i'
                );
                for (const tx of textosAmplios) {
                    if (regex.test(tx)) return true;
                }
            }

            return false;
        }

        function kpCoincideEnNombre(nombre, textoOpcion) {
            return kpTokenCoincideConTextoOpcion(nombre, textoOpcion);
        }

        function kpGrupoEspecificacionesTieneSeleccion(principalId) {
            return Array.from(document.querySelectorAll('.especificacion-checkbox'))
                .some((checkbox) => String(checkbox.dataset.principalId) === String(principalId) && checkbox.checked);
        }

        function kpAplicarBordePendienteGrupo(linea, pendiente) {
            if (!linea) return;

            if (pendiente) {
                linea.dataset.pendienteSinCoincidencia = '1';
                linea.style.borderColor = '#facc15';
                linea.style.boxShadow = '0 0 0 2px rgba(250, 204, 21, 0.45)';
            } else {
                delete linea.dataset.pendienteSinCoincidencia;
                linea.style.borderColor = '';
                linea.style.boxShadow = '';
            }
        }

        function kpAplicarCoincidenciasDesdeNombre() {
            const nombre = (
                document.getElementById('input_nombre')?.value ||
                document.querySelector('input[name="nombre"]')?.value ||
                ''
            ).trim();
            const container = document.getElementById('especificaciones-principales-container');
            if (!container) return;

            container.querySelectorAll('.linea-principal-especificaciones').forEach((linea) => {
                const principalId = linea.dataset.principalId;
                const checkboxes = Array.from(
                    linea.querySelectorAll('.especificacion-grupo-lista-opciones .especificacion-checkbox')
                );

                let mejorCheckbox = null;
                let mejorLongitud = 0;

                checkboxes.forEach((cb) => {
                    const texto = kpObtenerTextoSublineaEspecificacion(cb);
                    if (nombre && kpCoincideEnNombre(nombre, texto)) {
                        const len = kpNormalizarTextoCoincidencia(texto).length;
                        if (len > mejorLongitud) {
                            mejorLongitud = len;
                            mejorCheckbox = cb;
                        }
                    }
                });

                if (mejorCheckbox) {
                    checkboxes.forEach((cb) => {
                        const debeMarcar = cb === mejorCheckbox;
                        if (cb.checked !== debeMarcar) {
                            cb.checked = debeMarcar;
                            cb.dispatchEvent(new Event('change', { bubbles: true }));
                        }
                    });
                    kpAplicarBordePendienteGrupo(linea, false);
                } else if (nombre.length > 0) {
                    const tieneSeleccion = kpGrupoEspecificacionesTieneSeleccion(principalId);
                    kpAplicarBordePendienteGrupo(linea, !tieneSeleccion);
                } else {
                    kpAplicarBordePendienteGrupo(linea, false);
                }
            });
        }

        window.kpAplicarCoincidenciasDesdeNombre = kpAplicarCoincidenciasDesdeNombre;

        function kpObtenerNombreProductoActual() {
            return (
                document.getElementById('input_nombre')?.value ||
                document.querySelector('input[name="nombre"]')?.value ||
                ''
            ).trim();
        }

        function kpObtenerPalabrasNombreProducto() {
            const nombre = kpObtenerNombreProductoActual();
            if (!nombre) return [];

            // Separar por espacios, /, | y ; — no por comas (3,9) ni puntos decimales (4.40)
            const tokens = nombre
                .split(/(?:\s+|\/|\||;)+/)
                .map((p) => p.trim())
                .filter(Boolean);

            const visto = new Set();
            const palabras = [];

            tokens.forEach((token) => {
                const limpia = token.replace(/^[^a-zA-Z0-9À-ÿ.,]+|[^a-zA-Z0-9À-ÿ.,]+$/gi, '');
                if (!limpia) return;
                const clave = limpia.toLowerCase();
                if (visto.has(clave)) return;
                visto.add(clave);
                palabras.push(limpia);
            });

            return palabras;
        }

        window.kpObtenerPalabrasNombreProducto = kpObtenerPalabrasNombreProducto;

        function kpEjecutarBusquedaGrupoEspecificaciones(input) {
            if (!input) return;

            const contenedor =
                input.closest('#especificaciones-internas-contenido') ||
                document.getElementById('especificaciones-internas-contenido');
            const principalId = input.dataset.principalId;
            const query = input.value.trim();
            const resultados = contenedor?.querySelector(
                `.especificacion-grupo-resultados[data-principal-id="${principalId}"]`
            );
            const linea = input.closest('.linea-principal-especificaciones');
            const lista = linea?.querySelector('.especificacion-grupo-lista-opciones');
            if (!resultados || !linea || !lista) return;

            if (!query) {
                kpRestaurarFilasGrupoEspecificaciones(linea);
                return;
            }

            kpRestaurarFilasGrupoEspecificaciones(linea);

            const tokensBusqueda = query.split(/\s+/).filter(Boolean);
            const opciones = linea.querySelectorAll('.especificacion-opcion-fila');
            let hayCoincidencias = false;

            opciones.forEach((fila) => {
                const cb = fila.querySelector('.especificacion-checkbox');
                if (!cb) return;
                const texto = kpObtenerTextoSublineaEspecificacion(cb);
                const coincide = tokensBusqueda.some((token) =>
                    kpTokenCoincideConTextoOpcion(token, texto)
                );

                if (coincide) {
                    resultados.appendChild(fila);
                    hayCoincidencias = true;
                }
            });

            if (!hayCoincidencias) {
                resultados.innerHTML =
                    '<p class="text-xs text-gray-500 dark:text-gray-400 pl-1 py-2">Sin resultados</p>';
            }

            resultados.classList.remove('hidden');
        }

        function kpTokensBuscadorGrupo(valor) {
            return String(valor || '')
                .trim()
                .split(/\s+/)
                .filter(Boolean);
        }

        function kpMarcarBotonPalabraNombre(btn, activo) {
            if (!btn) return;
            const clasesActivo = [
                'ring-2',
                'ring-blue-500',
                'border-blue-500',
                'bg-blue-100',
                'dark:bg-blue-900',
                'dark:border-blue-400',
                'font-semibold',
            ];
            if (activo) {
                btn.classList.add(...clasesActivo);
                btn.setAttribute('aria-pressed', 'true');
            } else {
                btn.classList.remove(...clasesActivo);
                btn.setAttribute('aria-pressed', 'false');
            }
        }

        function kpSincronizarBotonesPalabrasConBuscador(wrap) {
            if (!wrap) return;
            const input = wrap.querySelector('.especificacion-grupo-buscador');
            const tokens = kpTokensBuscadorGrupo(input?.value || '');

            wrap.querySelectorAll('.especificacion-palabra-nombre-btn').forEach((btn) => {
                const palabra = btn.dataset.palabra || btn.textContent.trim();
                const activo = tokens.some(
                    (t) => t.toLowerCase() === palabra.toLowerCase()
                );
                kpMarcarBotonPalabraNombre(btn, activo);
            });
        }

        function kpActualizarBotonesPalabrasNombreBuscadores(contenedor) {
            const root =
                contenedor ||
                document.getElementById('especificaciones-internas-contenido');
            if (!root) return;

            const palabras = kpObtenerPalabrasNombreProducto();
            root.querySelectorAll('.especificacion-grupo-palabras-nombre').forEach((bloque) => {
                if (palabras.length === 0) {
                    bloque.innerHTML = '';
                    bloque.classList.add('hidden');
                    return;
                }

                bloque.classList.remove('hidden');
                bloque.innerHTML = palabras
                    .map((palabra) => {
                        const segura = String(palabra)
                            .replace(/&/g, '&amp;')
                            .replace(/</g, '&lt;')
                            .replace(/"/g, '&quot;');
                        return `<button type="button" class="especificacion-palabra-nombre-btn text-xs px-2 py-0.5 rounded border border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200 hover:bg-blue-100 dark:hover:bg-blue-900 hover:border-blue-400 dark:hover:border-blue-500 transition-colors" data-palabra="${segura}" aria-pressed="false" title="Pulsar para buscar «${segura}»">${segura}</button>`;
                    })
                    .join('');

                const wrap = bloque.closest('.especificacion-grupo-buscador-wrap');
                kpSincronizarBotonesPalabrasConBuscador(wrap);
            });
        }

        function kpConfigurarDelegacionPalabrasNombreBuscadores(contenedor) {
            const root =
                contenedor ||
                document.getElementById('especificaciones-internas-contenido');
            if (!root || root.dataset.palabrasNombreDelegacion === '1') return;

            root.dataset.palabrasNombreDelegacion = '1';
            root.addEventListener('click', (event) => {
                const btn = event.target.closest('.especificacion-palabra-nombre-btn');
                if (!btn) return;

                const wrap = btn.closest('.especificacion-grupo-buscador-wrap');
                const input = wrap?.querySelector('.especificacion-grupo-buscador');
                if (!input) return;

                const palabra = btn.dataset.palabra || btn.textContent.trim();
                const estabaActivo = btn.getAttribute('aria-pressed') === 'true';
                let tokens = kpTokensBuscadorGrupo(input.value);

                if (estabaActivo) {
                    tokens = tokens.filter((t) => t.toLowerCase() !== palabra.toLowerCase());
                } else if (!tokens.some((t) => t.toLowerCase() === palabra.toLowerCase())) {
                    tokens.push(palabra);
                }

                input.value = tokens.join(' ');
                kpSincronizarBotonesPalabrasConBuscador(wrap);
                kpEjecutarBusquedaGrupoEspecificaciones(input);
                input.focus();
            });
        }

        function kpRestaurarFilasGrupoEspecificaciones(linea) {
            const lista = linea.querySelector('.especificacion-grupo-lista-opciones');
            const resultados = linea.querySelector('.especificacion-grupo-resultados');
            if (!lista) return;

            const filas = [
                ...lista.querySelectorAll('.especificacion-opcion-fila'),
                ...(resultados ? resultados.querySelectorAll('.especificacion-opcion-fila') : []),
            ];
            filas.sort(
                (a, b) =>
                    Number(a.dataset.sublineaOrden || 0) - Number(b.dataset.sublineaOrden || 0)
            );
            filas.forEach((fila) => lista.appendChild(fila));

            if (resultados) {
                resultados.classList.add('hidden');
                resultados.innerHTML = '';
            }
        }

        function kpConfigurarBuscadoresGrupoEspecificaciones(contenedor) {
            kpConfigurarDelegacionPalabrasNombreBuscadores(contenedor);

            const buscadores = contenedor.querySelectorAll('.especificacion-grupo-buscador');
            buscadores.forEach((input) => {
                if (input.dataset.buscadorConfigurado === '1') return;
                input.dataset.buscadorConfigurado = '1';

                input.addEventListener('input', function () {
                    kpSincronizarBotonesPalabrasConBuscador(
                        this.closest('.especificacion-grupo-buscador-wrap')
                    );
                    kpEjecutarBusquedaGrupoEspecificaciones(this);
                });
            });

            kpActualizarBotonesPalabrasNombreBuscadores(contenedor);
        }

        function kpInicializarCoincidenciasNombreProducto() {
            const inputNombre = document.getElementById('input_nombre');
            const inputNombreFijo = document.getElementById('input_nombre_fijo');
            const handler = () => {
                kpAplicarCoincidenciasDesdeNombre();
                kpActualizarBotonesPalabrasNombreBuscadores();
                kpActualizarBotonesSegmentacionNombre();
                if (typeof window.kpInternaGlobalRefrescarPalabras === 'function') {
                    window.kpInternaGlobalRefrescarPalabras('nueva');
                    window.kpInternaGlobalRefrescarPalabras('sublinea');
                }
            };

            if (inputNombre && inputNombre.dataset.coincidenciasNombre !== '1') {
                inputNombre.dataset.coincidenciasNombre = '1';
                inputNombre.addEventListener('input', handler);
            }
            if (inputNombreFijo && inputNombreFijo.dataset.coincidenciasNombre !== '1') {
                inputNombreFijo.dataset.coincidenciasNombre = '1';
                inputNombreFijo.addEventListener('input', handler);
            }

            const contenedorEspecificaciones = document.getElementById('especificaciones-internas-contenido');
            if (contenedorEspecificaciones && contenedorEspecificaciones.dataset.coincidenciasNombre !== '1') {
                contenedorEspecificaciones.dataset.coincidenciasNombre = '1';
                contenedorEspecificaciones.addEventListener('change', (event) => {
                    if (!event.target.matches('.especificacion-checkbox')) return;

                    const principalId = event.target.dataset.principalId;
                    const linea = event.target.closest('.linea-principal-especificaciones');
                    if (
                        linea &&
                        linea.dataset.pendienteSinCoincidencia === '1' &&
                        kpGrupoEspecificacionesTieneSeleccion(principalId)
                    ) {
                        kpAplicarBordePendienteGrupo(linea, false);
                    }
                });
            }

            kpConfigurarDelegacionPalabrasNombreBuscadores();
            kpActualizarBotonesPalabrasNombreBuscadores();
            kpInicializarDelegacionEditoresGrupo();
            kpInicializarSegmentacionNombreCampos();
        }

        let kpCampoSegmentacionActivo = null;

        function kpObtenerWrapPalabrasCampo(campo) {
            if (!campo) return null;
            const prev = campo.previousElementSibling;
            if (prev && prev.classList.contains('kp-segmentacion-palabras-wrap')) {
                return prev;
            }
            return campo.parentElement?.querySelector('.kp-segmentacion-palabras-wrap') || null;
        }

        function kpOcultarTodasSegmentacionPalabras() {
            document.querySelectorAll('.kp-segmentacion-palabras-wrap').forEach((wrap) => {
                wrap.classList.add('hidden');
                wrap.innerHTML = '';
            });
        }

        function kpMarcarBotonSegmentacionNombre(btn, activo) {
            if (!btn) return;
            const clasesActivo = [
                'ring-2',
                'ring-green-500',
                'border-green-500',
                'bg-green-100',
                'dark:bg-green-900',
                'dark:border-green-400',
                'font-semibold',
                'text-green-800',
                'dark:text-green-200',
            ];
            if (activo) {
                btn.classList.add(...clasesActivo);
                btn.setAttribute('aria-pressed', 'true');
            } else {
                btn.classList.remove(...clasesActivo);
                btn.setAttribute('aria-pressed', 'false');
            }
        }

        function kpSincronizarBotonesSegmentacionWrap(wrap, campo) {
            if (!wrap || !campo) return;

            const tokens = kpTokensBuscadorGrupo(campo.value);
            wrap.querySelectorAll('.segmentacion-palabra-nombre-btn').forEach((btn) => {
                const palabra = btn.dataset.palabra || btn.textContent.trim();
                const activo = tokens.some(
                    (t) => t.toLowerCase() === palabra.toLowerCase()
                );
                kpMarcarBotonSegmentacionNombre(btn, activo);
            });
        }

        function kpRenderizarPalabrasEnCampo(campo) {
            const wrap = kpObtenerWrapPalabrasCampo(campo);
            if (!wrap) return;

            const palabras = kpObtenerPalabrasNombreProducto();
            if (!palabras.length) {
                wrap.innerHTML = '<span class="text-xs text-gray-500 dark:text-gray-400">Escribe el nombre del producto.</span>';
                wrap.classList.remove('hidden');
                return;
            }

            wrap.innerHTML = palabras
                .map((palabra) => {
                    const segura = String(palabra)
                        .replace(/&/g, '&amp;')
                        .replace(/</g, '&lt;')
                        .replace(/"/g, '&quot;');
                    return `<button type="button" class="segmentacion-palabra-nombre-btn text-xs px-2 py-0.5 rounded border border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200 hover:bg-blue-100 dark:hover:bg-blue-900 hover:border-blue-400 dark:hover:border-blue-500 transition-colors" data-palabra="${segura}" aria-pressed="false" title="Añadir «${segura}»">${segura}</button>`;
                })
                .join('');

            wrap.classList.remove('hidden');
            kpSincronizarBotonesSegmentacionWrap(wrap, campo);
        }

        function kpActualizarBotonesSegmentacionNombre() {
            if (!kpCampoSegmentacionActivo) return;
            kpRenderizarPalabrasEnCampo(kpCampoSegmentacionActivo);
        }

        function kpInsertarPalabraEnCampoSegmentacion(input, palabra) {
            if (!input || !palabra) return;

            let tokens = kpTokensBuscadorGrupo(input.value);
            const estabaPresente = tokens.some(
                (t) => t.toLowerCase() === palabra.toLowerCase()
            );

            if (estabaPresente) {
                tokens = tokens.filter((t) => t.toLowerCase() !== palabra.toLowerCase());
            } else {
                tokens.push(palabra);
            }

            input.value = tokens.join(' ');
            input.dispatchEvent(new Event('input', { bubbles: true }));

            const wrap = kpObtenerWrapPalabrasCampo(input);
            kpSincronizarBotonesSegmentacionWrap(wrap, input);
        }

        function kpInicializarSegmentacionNombreCampos() {
            const campos = document.querySelectorAll('.kp-campo-segmentacion-nombre');
            if (!campos.length || document.body.dataset.segmentacionInit === '1') {
                return;
            }
            document.body.dataset.segmentacionInit = '1';

            campos.forEach((campo) => {
                campo.addEventListener('focusin', () => {
                    kpOcultarTodasSegmentacionPalabras();
                    kpCampoSegmentacionActivo = campo;
                    kpRenderizarPalabrasEnCampo(campo);
                });

                campo.addEventListener('focusout', (event) => {
                    const wrap = kpObtenerWrapPalabrasCampo(campo);
                    const related = event.relatedTarget;
                    if (related && wrap && wrap.contains(related)) {
                        return;
                    }

                    setTimeout(() => {
                        if (document.activeElement === campo) return;
                        if (wrap && wrap.contains(document.activeElement)) return;

                        wrap?.classList.add('hidden');
                        wrap.innerHTML = '';

                        if (kpCampoSegmentacionActivo === campo) {
                            kpCampoSegmentacionActivo = null;
                        }
                    }, 0);
                });

                campo.addEventListener('input', () => {
                    if (kpCampoSegmentacionActivo === campo) {
                        const wrap = kpObtenerWrapPalabrasCampo(campo);
                        kpSincronizarBotonesSegmentacionWrap(wrap, campo);
                    }
                });
            });

            document.addEventListener('mousedown', (event) => {
                const btn = event.target.closest('.segmentacion-palabra-nombre-btn');
                if (btn) event.preventDefault();
            });

            document.addEventListener('click', (event) => {
                const btn = event.target.closest('.segmentacion-palabra-nombre-btn');
                if (!btn || !kpCampoSegmentacionActivo) return;

                const palabra = btn.dataset.palabra || btn.textContent.trim();
                kpInsertarPalabraEnCampoSegmentacion(kpCampoSegmentacionActivo, palabra);
                kpCampoSegmentacionActivo.focus();
            });
        }

        document.addEventListener('DOMContentLoaded', kpInicializarCoincidenciasNombreProducto);

        // Función para buscar categorías en tiempo real
        async function buscarCategoriasEspecificaciones(query) {
            if (query.length < 2) {
                ocultarSugerenciasCategoriaEspecificaciones();
                return;
            }

            try {
                const response = await fetch(`/panel-privado/productos/buscar/categorias?q=${encodeURIComponent(query)}`);
                const categorias = await response.json();
                categoriasActualesEspecificaciones = categorias;
                mostrarSugerenciasCategoriaEspecificaciones(categorias);
            } catch (error) {
                console.error('Error al buscar categorías:', error);
            }
        }

        // Función para mostrar sugerencias de categorías
        function mostrarSugerenciasCategoriaEspecificaciones(categorias) {
            const contenedor = document.getElementById('categoria_especificaciones_sugerencias');
            if (!contenedor) return;
            
            contenedor.innerHTML = '';

            if (categorias.length === 0) {
                contenedor.innerHTML = '<div class="px-4 py-2 text-white font-bold">No se encontraron categorías</div>';
                contenedor.classList.remove('hidden');
                return;
            }

            categorias.forEach((categoria, index) => {
                const div = document.createElement('div');
                div.className = `px-4 py-2 cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700 text-white font-bold ${index === indiceSeleccionadoCategoriaEspecificaciones ? 'bg-blue-100 dark:bg-blue-800' : ''}`;
                div.textContent = categoria.nombre;
                div.onclick = () => seleccionarCategoriaEspecificaciones(categoria);
                contenedor.appendChild(div);
            });

            contenedor.classList.remove('hidden');
        }

        // Función para ocultar sugerencias de categorías
        function ocultarSugerenciasCategoriaEspecificaciones() {
            const contenedor = document.getElementById('categoria_especificaciones_sugerencias');
            if (contenedor) {
                contenedor.classList.add('hidden');
            }
            indiceSeleccionadoCategoriaEspecificaciones = -1;
        }

        // Función para seleccionar una categoría
        function seleccionarCategoriaEspecificaciones(categoria) {
            document.getElementById('categoria_especificaciones_id').value = categoria.id;
            document.getElementById('categoria_especificaciones_nombre').value = categoria.nombre;
            ocultarSugerenciasCategoriaEspecificaciones();
            
            // Obtener y mostrar las especificaciones internas de la categoría
            obtenerEspecificacionesInternas(categoria.id);
        }

        // Función para obtener las especificaciones internas de una categoría
        async function obtenerEspecificacionesInternas(categoriaId) {
            const seleccionContainer = document.getElementById('especificaciones-internas-seleccion');
            const contenidoContainer = document.getElementById('especificaciones-internas-contenido');
            const checkboxNoAnadir = document.getElementById('no_anadir_especificaciones');
            
            if (!seleccionContainer || !contenidoContainer) return;
            
            try {
                const response = await fetch(`/panel-privado/productos/categoria/${categoriaId}/especificaciones-internas`);
                const data = await response.json();
                
                if (data.error) {
                    contenidoContainer.innerHTML = `<p class="text-red-500 text-sm">${data.error}</p>`;
                    seleccionContainer.classList.remove('hidden');
                    return;
                }
                
                const especificaciones = data.especificaciones_internas;
                
                // Actualizar el checkbox "No añadir especificaciones internas" según si tiene especificaciones
                if (checkboxNoAnadir) {
                    if (!especificaciones || especificaciones === null) {
                        // Si no tiene especificaciones, marcar el checkbox
                        checkboxNoAnadir.checked = true;
                    } else {
                        // Si tiene especificaciones, desmarcar el checkbox
                        checkboxNoAnadir.checked = false;
                    }
                }
                
                // Si el checkbox "No añadir" está marcado después de actualizarlo, no mostrar el contenido
                if (checkboxNoAnadir && checkboxNoAnadir.checked) {
                    seleccionContainer.classList.add('hidden');
                    return;
                }
                
                if (!especificaciones || especificaciones === null) {
                    contenidoContainer.innerHTML = '<p class="text-gray-600 dark:text-gray-300 text-sm font-medium">Esta categoría tiene <span class="text-red-500 font-bold">null</span> en el campo especificaciones_internas.</p>';
                    seleccionContainer.classList.remove('hidden');
                } else {
                    // Cargar las opciones seleccionadas guardadas
                    const inputHidden = document.getElementById('categoria_especificaciones_internas_elegidas_input');
                    let opcionesGuardadas = {};
                    if (inputHidden && inputHidden.value) {
                        try {
                            opcionesGuardadas = JSON.parse(inputHidden.value);
                        } catch (e) {
                            console.error('Error parseando opciones guardadas:', e);
                        }
                    }
                    
                    window.kpEspecificacionesCategoriaState = {
                        categoriaId: String(categoriaId),
                        especificaciones,
                        conteos: data.conteos_productos || {},
                        configuracion_formulario_producto: data.configuracion_formulario_producto || 'ninguno',
                    };

                    // Mostrar los desplegables con checkboxes
                    mostrarDesplegablesEspecificacionesProducto(especificaciones, contenidoContainer, opcionesGuardadas);
                    seleccionContainer.classList.remove('hidden');
                }
            } catch (error) {
                console.error('Error al obtener especificaciones internas:', error);
                contenidoContainer.innerHTML = '<p class="text-red-500 text-sm">Error al cargar las especificaciones internas.</p>';
                seleccionContainer.classList.remove('hidden');
            }
        }
        window.obtenerEspecificacionesInternas = obtenerEspecificacionesInternas;

        window.kpEspecificacionesCategoriaState = {
            categoriaId: null,
            especificaciones: null,
            conteos: {},
            configuracion_formulario_producto: 'ninguno',
        };

        let kpEditorGrupoDelegacionLista = false;

        function kpGenerarSlugEspecificaciones(texto) {
            return String(texto || '')
                .toLowerCase()
                .trim()
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/^-+|-+$/g, '');
        }

        function kpGenerarIdUnicoEspecificaciones() {
            return Date.now() + '_' + Math.random().toString(36).substr(2, 9);
        }

        function kpObtenerConteoSublinea(principalId, sublineaId) {
            const conteos = window.kpEspecificacionesCategoriaState?.conteos || {};
            const linea = conteos[String(principalId)];
            if (!linea) return null;
            const n = linea[String(sublineaId)];
            return n !== undefined && n !== null ? n : null;
        }

        function kpEditorGrupoTieneCambiosPendientes(lineaPrincipal) {
            return !!lineaPrincipal.querySelector(
                '.btn-guardar-fila-editor:not(.hidden), .btn-guardar-fila-editor-principal:not(.hidden)'
            );
        }

        function kpOcultarTodosGuardarFilaEditor(lineaPrincipal) {
            lineaPrincipal
                .querySelectorAll('.btn-guardar-fila-editor, .btn-guardar-fila-editor-principal')
                .forEach((btn) => {
                    btn.classList.add('hidden');
                    btn.disabled = false;
                    btn.textContent = 'Guardar';
                });
            delete lineaPrincipal.dataset.editorGrupoDirty;
        }

        function kpMarcarFilaEditorDirty(fila, dirty) {
            const btn = fila.querySelector('.btn-guardar-fila-editor, .btn-guardar-fila-editor-principal');
            if (btn) btn.classList.toggle('hidden', !dirty);
            const linea = lineaPrincipalDesdeEditor(fila);
            if (linea) {
                linea.dataset.editorGrupoDirty = kpEditorGrupoTieneCambiosPendientes(linea) ? '1' : '0';
            }
        }

        let kpEditorElementoArrastrado = null;

        function kpConfigurarDragAndDropEditorFila(elemento) {
            elemento.addEventListener('dragover', (e) => {
                if (!kpEditorElementoArrastrado || kpEditorElementoArrastrado === elemento) return;
                if (
                    !kpEditorElementoArrastrado.classList.contains('editor-linea-intermedia') ||
                    !elemento.classList.contains('editor-linea-intermedia')
                ) {
                    return;
                }
                if (kpEditorElementoArrastrado.parentNode !== elemento.parentNode) {
                    e.dataTransfer.dropEffect = 'none';
                    return;
                }

                e.preventDefault();
                e.stopPropagation();
                e.dataTransfer.dropEffect = 'move';

                const parent = elemento.parentNode;
                const rect = elemento.getBoundingClientRect();
                const insertAfter = e.clientY > rect.top + rect.height / 2;
                const siblings = Array.from(parent.children).filter((child) =>
                    child.classList.contains('editor-linea-intermedia')
                );
                const currentIndex = siblings.indexOf(elemento);
                const draggedIndex = siblings.indexOf(kpEditorElementoArrastrado);
                if (draggedIndex === -1) return;
                if (insertAfter && draggedIndex === currentIndex + 1) return;
                if (!insertAfter && draggedIndex === currentIndex - 1) return;

                if (insertAfter) {
                    const nextSibling = elemento.nextSibling;
                    if (nextSibling && nextSibling !== kpEditorElementoArrastrado) {
                        parent.insertBefore(kpEditorElementoArrastrado, nextSibling);
                    } else if (!nextSibling) {
                        parent.appendChild(kpEditorElementoArrastrado);
                    }
                } else if (elemento !== kpEditorElementoArrastrado) {
                    parent.insertBefore(kpEditorElementoArrastrado, elemento);
                }
            });

            elemento.addEventListener('drop', (e) => {
                e.preventDefault();
                e.stopPropagation();
            });
        }

        function kpConfigurarDragAndDropEditorDesdeIcono(icono, fila) {
            icono.addEventListener('dragstart', (e) => {
                kpEditorElementoArrastrado = fila;
                fila.style.opacity = '0.5';
                e.dataTransfer.effectAllowed = 'move';
                e.stopPropagation();
            });

            icono.addEventListener('dragend', (e) => {
                fila.style.opacity = '1';
                if (kpEditorElementoArrastrado) {
                    kpMarcarFilaEditorDirty(kpEditorElementoArrastrado, true);
                }
                kpEditorElementoArrastrado = null;
                e.stopPropagation();
            });

            icono.addEventListener('dragover', (e) => {
                e.preventDefault();
                e.stopPropagation();
            });

            icono.addEventListener('drop', (e) => {
                e.preventDefault();
                e.stopPropagation();
            });

            kpConfigurarDragAndDropEditorFila(fila);
        }

        function kpConfigurarDragAndDropEditorContenedor(contenedor) {
            if (!contenedor || contenedor.dataset.dragEditorConfigurado === '1') return;
            contenedor.dataset.dragEditorConfigurado = '1';

            contenedor.addEventListener('dragover', (e) => {
                if (!kpEditorElementoArrastrado) return;
                if (!kpEditorElementoArrastrado.classList.contains('editor-linea-intermedia')) return;

                const target = e.target;
                if (target.classList.contains('editor-linea-intermedia')) return;
                const parentElement = target.closest('.editor-linea-intermedia');
                if (parentElement) return;

                e.preventDefault();
                e.dataTransfer.dropEffect = 'move';

                const siblings = Array.from(contenedor.children).filter((child) =>
                    child.classList.contains('editor-linea-intermedia')
                );
                if (!siblings.includes(kpEditorElementoArrastrado)) {
                    const rect = contenedor.getBoundingClientRect();
                    if (e.clientY > rect.top + rect.height * 0.7) {
                        contenedor.appendChild(kpEditorElementoArrastrado);
                    }
                }
            });

            contenedor.addEventListener('drop', (e) => e.preventDefault());
        }

        function kpCrearFilaEditorIntermedia(container, texto = '', idUnico = null, productosCount = null) {
            const idLinea = idUnico || kpGenerarIdUnicoEspecificaciones();
            const contadorTexto =
                productosCount !== null && productosCount !== undefined
                    ? `(${productosCount} productos)`
                    : '';

            const div = document.createElement('div');
            div.className =
                'editor-linea-intermedia flex items-center gap-2 border-l-4 border-blue-400 pl-3 py-2 bg-blue-50 dark:bg-blue-900/20 rounded';
            div.dataset.idUnico = idLinea;
            div.draggable = false;
            div.innerHTML = `
                <div class="drag-handle-editor-intermedia cursor-move text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 shrink-0" title="Arrastrar para reordenar" draggable="true">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"></path>
                    </svg>
                </div>
                <input type="text" class="editor-intermedia-texto flex-1 min-w-0 px-3 py-2 text-sm rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-200" placeholder="Nombre de la opción" value="${String(texto).replace(/"/g, '&quot;')}">
                ${contadorTexto ? `<span class="text-sm text-gray-500 dark:text-gray-400 shrink-0">${contadorTexto}</span>` : ''}
                <button type="button" class="btn-guardar-fila-editor hidden shrink-0 text-xs px-3 py-1 rounded bg-green-600 hover:bg-green-700 text-white font-medium disabled:opacity-50">Guardar</button>
                <button type="button" class="btn-editor-eliminar-intermedia bg-red-500 hover:bg-red-600 text-white px-2 py-1 rounded text-xs shrink-0" title="Eliminar">−</button>
                <button type="button" class="btn-editor-anadir-intermedia bg-green-500 hover:bg-green-600 text-white px-2 py-1 rounded text-xs shrink-0" title="Añadir debajo">+</button>
            `;

            container.appendChild(div);

            const inputTexto = div.querySelector('.editor-intermedia-texto');
            inputTexto.addEventListener('dragstart', (e) => {
                e.preventDefault();
                e.stopPropagation();
            });
            inputTexto.addEventListener('input', () => kpMarcarFilaEditorDirty(div, true));

            div.querySelector('.btn-editor-eliminar-intermedia').addEventListener('click', () => {
                const linea = lineaPrincipalDesdeEditor(div);
                const containerSub = div.parentElement;
                if (containerSub.querySelectorAll('.editor-linea-intermedia').length <= 1) {
                    alert('Debe quedar al menos una opción en el grupo.');
                    return;
                }
                div.remove();
                const filaPrincipalEditor = linea?.querySelector('.especificacion-grupo-editor-principal');
                if (filaPrincipalEditor) kpMarcarFilaEditorDirty(filaPrincipalEditor, true);
            });

            div.querySelector('.btn-editor-anadir-intermedia').addEventListener('click', () => {
                const linea = lineaPrincipalDesdeEditor(div);
                const nueva = kpCrearFilaEditorIntermedia(container, '');
                div.insertAdjacentElement('afterend', nueva);
                kpMarcarFilaEditorDirty(nueva, true);
            });

            const dragHandle = div.querySelector('.drag-handle-editor-intermedia');
            if (dragHandle) {
                kpConfigurarDragAndDropEditorDesdeIcono(dragHandle, div);
            }

            return div;
        }

        function lineaPrincipalDesdeEditor(el) {
            return el.closest('.linea-principal-especificaciones');
        }

        function kpRenderizarEditorGrupo(lineaPrincipal, filtro) {
            const principalId = lineaPrincipal.dataset.principalId;
            const editor = lineaPrincipal.querySelector('.especificacion-grupo-editor');
            const containerSub = editor?.querySelector('.especificacion-grupo-editor-sublineas');
            if (!editor || !containerSub) return;

            const inputPrincipal = editor.querySelector('.editor-principal-texto');
            const checkImportante = editor.querySelector('.editor-principal-importante');
            if (inputPrincipal) inputPrincipal.value = filtro.texto || '';
            if (checkImportante) checkImportante.checked = !!filtro.importante;

            delete containerSub.dataset.dragEditorConfigurado;
            containerSub.innerHTML = '';
            const subprincipales = Array.isArray(filtro.subprincipales) ? filtro.subprincipales : [];
            if (subprincipales.length === 0) {
                kpCrearFilaEditorIntermedia(containerSub, '');
            } else {
                subprincipales.forEach((sub) => {
                    kpCrearFilaEditorIntermedia(
                        containerSub,
                        sub.texto || '',
                        sub.id || null,
                        kpObtenerConteoSublinea(principalId, sub.id)
                    );
                });
            }

            kpConfigurarDragAndDropEditorContenedor(containerSub);
            kpOcultarTodosGuardarFilaEditor(lineaPrincipal);

            const filaPrincipalEditor = editor.querySelector('.especificacion-grupo-editor-principal');
            if (inputPrincipal && !inputPrincipal.dataset.editorConfigurado) {
                inputPrincipal.dataset.editorConfigurado = '1';
                inputPrincipal.addEventListener('input', () => {
                    if (filaPrincipalEditor) kpMarcarFilaEditorDirty(filaPrincipalEditor, true);
                });
            }
            if (checkImportante && !checkImportante.dataset.editorConfigurado) {
                checkImportante.dataset.editorConfigurado = '1';
                checkImportante.addEventListener('change', () => {
                    if (filaPrincipalEditor) kpMarcarFilaEditorDirty(filaPrincipalEditor, true);
                });
            }
        }

        function kpConstruirFiltroDesdeEditor(lineaPrincipal) {
            const principalId = lineaPrincipal.dataset.principalId;
            const editor = lineaPrincipal.querySelector('.especificacion-grupo-editor');
            const texto = editor.querySelector('.editor-principal-texto')?.value.trim() || '';
            const importante = editor.querySelector('.editor-principal-importante')?.checked || false;
            const subprincipales = [];

            editor.querySelectorAll('.editor-linea-intermedia').forEach((row) => {
                const textoSub = row.querySelector('.editor-intermedia-texto')?.value.trim() || '';
                const idUnico = row.dataset.idUnico;
                if (textoSub && idUnico) {
                    subprincipales.push({
                        texto: textoSub,
                        id: idUnico,
                        slug: kpGenerarSlugEspecificaciones(textoSub),
                    });
                }
            });

            return {
                id: principalId,
                texto,
                slug: kpGenerarSlugEspecificaciones(texto),
                importante,
                subprincipales,
            };
        }

        function kpAbrirEditorGrupo(lineaPrincipal) {
            if (!document.getElementById('categoria_especificaciones_id')?.value) {
                alert('Selecciona primero la categoría para especificaciones internas.');
                return;
            }

            const principalId = lineaPrincipal.dataset.principalId;
            const state = window.kpEspecificacionesCategoriaState;
            const filtro = state?.especificaciones?.filtros?.find(
                (f) => String(f.id) === String(principalId)
            );
            if (!filtro) return;

            const editor = lineaPrincipal.querySelector('.especificacion-grupo-editor');
            const btnEditar = lineaPrincipal.querySelector('.btn-editar-grupo-especificaciones');

            kpRenderizarEditorGrupo(lineaPrincipal, filtro);
            const details = lineaPrincipal.querySelector('.especificacion-grupo-details');
            if (details) details.classList.add('hidden');
            if (editor) editor.classList.remove('hidden');
            if (btnEditar) btnEditar.textContent = 'Ver opciones';
            lineaPrincipal.dataset.modoEditorGrupo = '1';
            kpOcultarTodosGuardarFilaEditor(lineaPrincipal);
        }

        function kpCerrarEditorGrupo(lineaPrincipal) {
            const editor = lineaPrincipal.querySelector('.especificacion-grupo-editor');
            const btnEditar = lineaPrincipal.querySelector('.btn-editar-grupo-especificaciones');
            const details = lineaPrincipal.querySelector('.especificacion-grupo-details');

            if (details) details.classList.remove('hidden');
            if (editor) editor.classList.add('hidden');
            if (btnEditar) btnEditar.textContent = 'Editar';
            delete lineaPrincipal.dataset.modoEditorGrupo;
            kpOcultarTodosGuardarFilaEditor(lineaPrincipal);
        }

        function kpObtenerSublineaIdDesdeBotonGuardarEditor(btnTrigger) {
            if (!btnTrigger || btnTrigger.classList.contains('btn-guardar-fila-editor-principal')) {
                return null;
            }
            const fila = btnTrigger.closest('.editor-linea-intermedia');
            return fila?.dataset.idUnico || null;
        }

        function kpAñadirSublineaAEspecificacionesElegidas(opcionesGuardadas, principalId, sublineaId) {
            if (!principalId || !sublineaId) return opcionesGuardadas;

            const pid = String(principalId);
            const sid = String(sublineaId);
            const out = { ...opcionesGuardadas };
            let arr = Array.isArray(out[pid]) ? [...out[pid]] : [];

            const yaExiste = arr.some((item) => {
                if (typeof item === 'string' || typeof item === 'number') {
                    return String(item) === sid;
                }
                return item && item.id != null && String(item.id) === sid;
            });

            if (!yaExiste) {
                arr.push({ id: sid });
            }

            out[pid] = arr;
            return out;
        }

        function kpMarcarSublineaEspecificacionEnUI(principalId, sublineaId) {
            const contenedor = document.getElementById('especificaciones-internas-contenido');
            if (!contenedor || !principalId || !sublineaId) return false;

            const checkbox = contenedor.querySelector(
                `.especificacion-checkbox[data-principal-id="${principalId}"][data-sublinea-id="${sublineaId}"]`
            );
            if (!checkbox) return false;

            if (!checkbox.checked) {
                checkbox.checked = true;
                checkbox.dispatchEvent(new Event('change', { bubbles: true }));
            } else {
                actualizarEspecificacionesElegidas();
                actualizarChipsSeleccionados();
            }

            return true;
        }

        function kpScrollAGrupoEspecificaciones(principalId) {
            const contenedor = document.getElementById('especificaciones-internas-contenido');
            if (!contenedor || !principalId) return;

            const linea = contenedor.querySelector(
                `.linea-principal-especificaciones[data-principal-id="${principalId}"]`
            );
            if (!linea) return;

            const details = linea.querySelector('.especificacion-grupo-details');
            if (details) {
                details.removeAttribute('open');
                details.classList.remove('hidden');
            }

            const editor = linea.querySelector('.especificacion-grupo-editor');
            if (editor) editor.classList.add('hidden');

            const btnEditar = linea.querySelector('.btn-editar-grupo-especificaciones');
            if (btnEditar) btnEditar.textContent = 'Editar';

            delete linea.dataset.modoEditorGrupo;

            linea.scrollIntoView({ behavior: 'smooth', block: 'center' });

            linea.classList.add('ring-2', 'ring-amber-400', 'ring-offset-2');
            window.setTimeout(() => {
                linea.classList.remove('ring-2', 'ring-amber-400', 'ring-offset-2');
            }, 2200);
        }

        async function kpGuardarEditorGrupo(lineaPrincipal, btnTrigger = null) {
            const categoriaId = document.getElementById('categoria_especificaciones_id')?.value;
            if (!categoriaId) {
                alert('Selecciona una categoría para especificaciones internas antes de guardar.');
                return;
            }

            const principalId = lineaPrincipal.dataset.principalId;
            const filtroEditado = kpConstruirFiltroDesdeEditor(lineaPrincipal);

            if (!filtroEditado.texto) {
                alert('El nombre del grupo no puede estar vacío.');
                return;
            }
            if (filtroEditado.subprincipales.length === 0) {
                alert('Añade al menos una opción al grupo.');
                return;
            }

            const state = window.kpEspecificacionesCategoriaState;
            let especificaciones = state?.especificaciones;
            if (!especificaciones || !Array.isArray(especificaciones.filtros)) {
                especificaciones = { filtros: [] };
            }

            const filtros = [...especificaciones.filtros];
            const idx = filtros.findIndex((f) => String(f.id) === String(principalId));
            if (idx >= 0) {
                filtros[idx] = filtroEditado;
            } else {
                filtros.push(filtroEditado);
            }

            const btnGuardar = btnTrigger;
            if (btnGuardar) {
                btnGuardar.disabled = true;
                btnGuardar.textContent = 'Guardando…';
            }

            try {
                const response = await fetch(
                    `/panel-privado/productos/categoria/${categoriaId}/especificaciones-internas`,
                    {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            Accept: 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        },
                        body: JSON.stringify({ especificaciones_internas: { filtros } }),
                    }
                );
                const data = await response.json();

                if (!response.ok) {
                    const msg =
                        data.message ||
                        data.error ||
                        (data.errors ? Object.values(data.errors).flat().join(' ') : null) ||
                        'No se pudo guardar.';
                    throw new Error(msg);
                }

                window.kpEspecificacionesCategoriaState = {
                    categoriaId: String(categoriaId),
                    especificaciones: data.especificaciones_internas,
                    conteos: data.conteos_productos || {},
                    configuracion_formulario_producto: data.configuracion_formulario_producto
                        || window.kpEspecificacionesCategoriaState?.configuracion_formulario_producto
                        || 'ninguno',
                };

                const sublineaIdGuardada = kpObtenerSublineaIdDesdeBotonGuardarEditor(btnTrigger);
                const principalIdScroll = String(principalId);

                const inputHidden = document.getElementById('categoria_especificaciones_internas_elegidas_input');
                let opcionesGuardadas = {};
                if (inputHidden?.value) {
                    try {
                        opcionesGuardadas = JSON.parse(inputHidden.value);
                    } catch (e) {
                        opcionesGuardadas = {};
                    }
                }

                if (sublineaIdGuardada) {
                    opcionesGuardadas = kpAñadirSublineaAEspecificacionesElegidas(
                        opcionesGuardadas,
                        principalIdScroll,
                        sublineaIdGuardada
                    );
                    if (inputHidden) {
                        inputHidden.value = JSON.stringify(opcionesGuardadas);
                    }
                }

                const contenidoContainer = document.getElementById('especificaciones-internas-contenido');
                if (contenidoContainer && data.especificaciones_internas) {
                    mostrarDesplegablesEspecificacionesProducto(
                        data.especificaciones_internas,
                        contenidoContainer,
                        opcionesGuardadas
                    );
                }

                if (typeof kpAplicarCoincidenciasDesdeNombre === 'function') {
                    kpAplicarCoincidenciasDesdeNombre();
                }

                if (sublineaIdGuardada) {
                    kpMarcarSublineaEspecificacionEnUI(principalIdScroll, sublineaIdGuardada);
                }

                requestAnimationFrame(() => {
                    kpScrollAGrupoEspecificaciones(principalIdScroll);
                });
            } catch (error) {
                console.error(error);
                alert('Error al guardar: ' + error.message);
            } finally {
                if (btnGuardar) {
                    btnGuardar.disabled = false;
                    btnGuardar.textContent = 'Guardar';
                }
            }
        }

        function kpInicializarDelegacionEditoresGrupo() {
            if (kpEditorGrupoDelegacionLista) return;
            kpEditorGrupoDelegacionLista = true;

            const root = document.getElementById('especificaciones-internas-contenido');
            if (!root) return;

            root.addEventListener('keydown', (event) => {
                if (event.key === 'Enter') {
                    const target = event.target;
                    if (target.matches('.editor-principal-texto') || target.matches('.editor-intermedia-texto')) {
                        event.preventDefault();
                        const fila = target.closest('.especificacion-grupo-editor-principal, .editor-linea-intermedia');
                        if (fila) {
                            const btnGuardar = fila.querySelector('.btn-guardar-fila-editor, .btn-guardar-fila-editor-principal');
                            if (btnGuardar) {
                                btnGuardar.click();
                            }
                        }
                    }
                }
            });

            root.addEventListener('click', (event) => {
                const btnEditar = event.target.closest('.btn-editar-grupo-especificaciones');
                const btnGuardar = event.target.closest(
                    '.btn-guardar-fila-editor, .btn-guardar-fila-editor-principal'
                );
                const linea = (btnEditar || btnGuardar)?.closest('.linea-principal-especificaciones');
                if (!linea) return;

                if (btnGuardar) {
                    event.preventDefault();
                    kpGuardarEditorGrupo(linea, btnGuardar);
                    return;
                }

                if (btnEditar) {
                    event.preventDefault();
                    if (linea.dataset.modoEditorGrupo === '1') {
                        if (kpEditorGrupoTieneCambiosPendientes(linea)) {
                            if (
                                !confirm(
                                    'Hay cambios sin guardar. ¿Cerrar el editor sin guardar?'
                                )
                            ) {
                                return;
                            }
                        }
                        kpCerrarEditorGrupo(linea);
                    } else {
                        kpAbrirEditorGrupo(linea);
                    }
                }
            });
        }

        function kpInicializarEditoresGrupoEspecificaciones() {
            kpInicializarDelegacionEditoresGrupo();
        }

        // Event listeners para el buscador de categorías (igual que productos relacionados)
        const inputEspecificaciones = document.getElementById('categoria_especificaciones_nombre');
        if (inputEspecificaciones) {
            inputEspecificaciones.addEventListener('input', (e) => {
                // Limpiar el ID cuando el usuario empiece a escribir algo nuevo
                document.getElementById('categoria_especificaciones_id').value = '';
                // Ocultar el contenedor de especificaciones internas cuando se empiece a escribir
                const infoContainer = document.getElementById('especificaciones-internas-info');
                if (infoContainer) {
                    infoContainer.classList.add('hidden');
                }
                const seleccionContainer = document.getElementById('especificaciones-internas-seleccion');
                if (seleccionContainer) {
                    seleccionContainer.classList.add('hidden');
                }
                buscarCategoriasEspecificaciones(e.target.value);
            });

            inputEspecificaciones.addEventListener('keydown', (e) => {
                const contenedor = document.getElementById('categoria_especificaciones_sugerencias');
                
                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    indiceSeleccionadoCategoriaEspecificaciones = Math.min(indiceSeleccionadoCategoriaEspecificaciones + 1, categoriasActualesEspecificaciones.length - 1);
                    mostrarSugerenciasCategoriaEspecificaciones(categoriasActualesEspecificaciones);
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    indiceSeleccionadoCategoriaEspecificaciones = Math.max(indiceSeleccionadoCategoriaEspecificaciones - 1, -1);
                    mostrarSugerenciasCategoriaEspecificaciones(categoriasActualesEspecificaciones);
                } else if (e.key === 'Enter') {
                    e.preventDefault();
                    if (indiceSeleccionadoCategoriaEspecificaciones >= 0 && categoriasActualesEspecificaciones[indiceSeleccionadoCategoriaEspecificaciones]) {
                        seleccionarCategoriaEspecificaciones(categoriasActualesEspecificaciones[indiceSeleccionadoCategoriaEspecificaciones]);
                    }
                } else if (e.key === 'Escape') {
                    ocultarSugerenciasCategoriaEspecificaciones();
                }
            });

            inputEspecificaciones.addEventListener('blur', (e) => {
                // Pequeño delay para permitir que se ejecute el click en las sugerencias
                setTimeout(() => {
                    const categoriaId = document.getElementById('categoria_especificaciones_id').value;
                    const categoriaNombre = document.getElementById('categoria_especificaciones_nombre').value;
                    
                    // Si hay texto pero no hay ID válido, limpiar todo
                    if (categoriaNombre && !categoriaId) {
                        document.getElementById('categoria_especificaciones_nombre').value = '';
                        document.getElementById('categoria_especificaciones_id').value = '';
                    }
                }, 300);
            });
        }

        // Ocultar sugerencias al hacer clic fuera
        document.addEventListener('click', (e) => {
            const contenedor = document.getElementById('categoria_especificaciones_sugerencias');
            const input = document.getElementById('categoria_especificaciones_nombre');
            
            if (contenedor && input && !contenedor.contains(e.target) && !input.contains(e.target)) {
                ocultarSugerenciasCategoriaEspecificaciones();
                
                // Limpiar campo si hay texto pero no hay ID válido
                setTimeout(() => {
                    const categoriaId = document.getElementById('categoria_especificaciones_id').value;
                    const categoriaNombre = document.getElementById('categoria_especificaciones_nombre').value;
                    
                    if (categoriaNombre && !categoriaId) {
                        document.getElementById('categoria_especificaciones_nombre').value = '';
                        document.getElementById('categoria_especificaciones_id').value = '';
                    }
                }, 200);
            }
        });

        // Función para mostrar los desplegables con checkboxes
        function mostrarDesplegablesEspecificacionesProducto(especificaciones, contenedor, opcionesGuardadas = {}) {
            if (!especificaciones || !especificaciones.filtros || !Array.isArray(especificaciones.filtros)) {
                contenedor.innerHTML = '<p class="text-gray-600 dark:text-gray-300 text-sm">La estructura de especificaciones internas no es válida.</p>';
                return;
            }
            
            // Normalizadores para compatibilidad con estructuras antiguas/rotas:
            // - _orden/_columnas pueden venir como ["id"] o [{id:"id", c:0}]
            // - _formatos puede venir como {"idLinea": "texto"} o {"idLinea": {id:"texto", c:0}}
            const normalizarId = (v) => {
                if (v && typeof v === 'object' && !Array.isArray(v)) {
                    return (typeof v.id === 'string' || typeof v.id === 'number') ? v.id : null;
                }
                return v;
            };
            const normalizarArrayIds = (arr) => {
                if (!Array.isArray(arr)) return [];
                return arr
                    .map(normalizarId)
                    .filter(v => (typeof v === 'string' || typeof v === 'number'))
                    .map(v => String(v));
            };
            // _columnas puede ser lista de IDs, lista de objetos {id}, o mapa { idPrincipal: sublineaId } (otros formularios)
            const normalizarColumnasLista = (raw) => {
                if (raw == null) return [];
                if (Array.isArray(raw)) {
                    return normalizarArrayIds(raw);
                }
                if (typeof raw === 'object') {
                    return Object.keys(raw)
                        .filter(k => k && !String(k).startsWith('_'))
                        .map(k => String(k));
                }
                return [];
            };
            const normalizarFormato = (v) => {
                if (v && typeof v === 'object' && !Array.isArray(v)) {
                    return typeof v.id === 'string' ? v.id : 'texto';
                }
                return (typeof v === 'string') ? v : 'texto';
            };

            // Verificar si la unidad de medida es "Unidad Única"
            const unidadDeMedidaSelect = document.getElementById('unidadDeMedida');
            const esUnidadUnica = unidadDeMedidaSelect && unidadDeMedidaSelect.value === 'unidadUnica';
            
            // Obtener orden guardado y columnas marcadas (siempre como string para includes/Map coherentes con dataset y JSON mixto numérico)
            const ordenGuardado = normalizarArrayIds(opcionesGuardadas._orden || []);
            const columnasGuardadas = normalizarColumnasLista(opcionesGuardadas._columnas);
            const mostrarUIColumnaOferta = true;
            const maxColumnasOferta = esUnidadUnica ? 4 : 1;
            
            // Ordenar filtros según el orden guardado, o mantener el orden original
            let filtrosOrdenados = [...especificaciones.filtros];
            if (ordenGuardado.length > 0) {
                const filtrosMap = new Map(filtrosOrdenados.map(f => [String(f.id), f]));
                filtrosOrdenados = ordenGuardado
                    .map(id => filtrosMap.get(String(id)))
                    .filter(f => f !== undefined)
                    .concat(filtrosOrdenados.filter(f => !ordenGuardado.includes(String(f.id))));
            }
            
            let html = '<p class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-4">Selecciona las opciones deseadas en cada línea principal:</p>';
            html += '<div id="especificaciones-principales-container" class="space-y-4">';
            
            filtrosOrdenados.forEach((filtro, index) => {
                const idPrincipal = filtro.id;
                const textoPrincipal = filtro.texto || `Línea principal ${index + 1}`;
                const subprincipales = filtro.subprincipales || [];
                const opcionesSeleccionadas =
                    opcionesGuardadas[idPrincipal] ||
                    opcionesGuardadas[String(idPrincipal)] ||
                    [];
                const esColumna = columnasGuardadas.includes(String(idPrincipal));
                
                html += `<div class="linea-principal-especificaciones border border-gray-300 dark:border-gray-600 rounded-lg p-4 bg-white dark:bg-gray-800" data-principal-id="${idPrincipal}" draggable="false">`;
                
                // Cabecera del grupo, palabras del nombre y buscador
                html += `<div class="especificacion-grupo-buscador-wrap mb-2" data-principal-id="${idPrincipal}">`;
                html += `<div class="flex flex-wrap items-center gap-3 mb-2">`;
                
                if (esUnidadUnica) {
                    html += `<div class="drag-handle-principal-especificaciones cursor-move text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 shrink-0" title="Arrastrar para reordenar" draggable="true">`;
                    html += `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">`;
                    html += `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"></path>`;
                    html += `</svg>`;
                    html += `</div>`;
                }
                
                html += `<span class="font-medium text-gray-700 dark:text-gray-200 shrink-0">${textoPrincipal}</span>`;
                html += `<div class="especificacion-grupo-palabras-nombre flex flex-wrap gap-1.5 flex-1 min-w-0 hidden" data-principal-id="${idPrincipal}"></div>`;
                
                if (mostrarUIColumnaOferta) {
                    html += `<div class="flex items-center gap-2 shrink-0 ml-auto">`;
                    html += `<label class="flex items-center gap-1 cursor-pointer">`;
                    html += `<input type="checkbox" class="columna-oferta-checkbox rounded border-gray-300 text-orange-600 focus:ring-orange-500" data-principal-id="${idPrincipal}" ${esColumna ? 'checked' : ''}>`;
                    html += `<span class="text-xs text-gray-600 dark:text-gray-400 font-medium">Columna oferta</span>`;
                    html += `</label>`;
                    html += `<div class="relative">`;
                    html += `<button type="button" class="tooltip-btn text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 cursor-help focus:outline-none" aria-label="Ayuda" data-tooltip='Esto modificará las columnas de las ofertas listadas y se mostrarán las marcadas'>`;
                    html += `<svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">`;
                    html += `<path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm0 8a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"></path>`;
                    html += `</svg>`;
                    html += `</button>`;
                    html += `</div>`;
                    html += `</div>`;
                }
                
                html += `</div>`;
                html += `<input type="text" class="especificacion-grupo-buscador w-full px-3 py-1.5 text-sm rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-200 focus:outline-none focus:ring-2 focus:ring-blue-500" data-principal-id="${idPrincipal}" placeholder="Buscar..." autocomplete="off">`;
                html += `<div class="especificacion-grupo-resultados mt-2 space-y-2 pl-4 border-l-2 border-blue-400 dark:border-blue-500 hidden" data-principal-id="${idPrincipal}"></div>`;
                html += `</div>`;
                
                html += `<div class="especificacion-grupo-opciones-toolbar relative mb-2">`;
                html += `<details class="especificacion-grupo-details min-w-0 pr-40 sm:pr-44">`;
                html += `<summary class="cursor-pointer text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 font-medium list-none [&::-webkit-details-marker]:hidden">Ver todas las opciones</summary>`;
                html += `<div class="mt-2 space-y-2 pl-4 border-l-2 border-gray-300 dark:border-gray-600 especificacion-grupo-lista-opciones" data-principal-id="${idPrincipal}">`;
                
                subprincipales.forEach((sub, subIndex) => {
                    const idSublinea = sub.id;
                    const textoSublinea = sub.texto || '(Sin texto)';
                    const esPrimeraSublinea = subIndex === 0;
                    
                    // Obtener datos guardados para esta sublínea (nueva estructura optimizada)
                    let sublineaData = null;
                    if (Array.isArray(opcionesSeleccionadas)) {
                        // Buscar en el array (puede ser estructura antigua o nueva)
                        sublineaData = opcionesSeleccionadas.find(item => {
                            if (typeof item === 'string' || typeof item === 'number') {
                                // Estructura antigua: array de IDs
                                return String(item) === String(idSublinea);
                            } else if (item && item.id) {
                                // Estructura nueva: objeto con id
                                return String(item.id) === String(idSublinea);
                            }
                            return false;
                        });
                        
                        // Si es estructura antigua (solo ID), convertir a nueva
                        if (sublineaData && (typeof sublineaData === 'string' || typeof sublineaData === 'number')) {
                            sublineaData = { id: sublineaData, m: 0, o: 0 };
                        }
                    }
                    
                    const isChecked = sublineaData !== null && sublineaData !== undefined;
                    // Leer flags optimizados: 'm' = mostrar, 'o' = oferta
                    // Comparación flexible para manejar números y strings
                    const flagTruthy = (v) => v === true || v === 1 || v === '1';
                    const mostrarChecked = sublineaData && (flagTruthy(sublineaData.m) || sublineaData.mostrar === true);
                    const ofertaChecked = sublineaData && (flagTruthy(sublineaData.o) || sublineaData.oferta === true);
                    // Leer imágenes de la sublínea
                    const imagenesSublinea = sublineaData && Array.isArray(sublineaData.img) ? sublineaData.img : [];
                    const numImagenes = imagenesSublinea.length;
                    
                    // Leer texto alternativo guardado si existe
                    const textoAlternativo = sublineaData && sublineaData.textoAlternativo ? sublineaData.textoAlternativo : '';
                    
                    html += `<div class="especificacion-opcion-fila flex flex-wrap items-center gap-3 p-2 rounded hover:bg-gray-100 dark:hover:bg-gray-700" data-sublinea-id="${idSublinea}" data-sublinea-orden="${subIndex}">`;
                    html += `<label class="flex items-center gap-2 cursor-pointer flex-1">`;
                    html += `<input type="checkbox" class="especificacion-checkbox rounded border-gray-300 text-green-600 focus:ring-green-500" data-principal-id="${idPrincipal}" data-sublinea-id="${idSublinea}" data-sublinea-texto="${textoSublinea.replace(/"/g, '&quot;')}" ${isChecked ? 'checked' : ''}>`;
                    html += `<span class="text-sm text-gray-700 dark:text-gray-300">${textoSublinea}</span>`;
                    html += `</label>`;
                    
                    // Campo de texto alternativo (siempre se crea, pero solo se muestra si la línea principal está marcada como columna oferta)
                    html += `<input type="text" class="texto-alternativo-sublinea-input flex-1 max-w-xs px-2 py-1 text-xs border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-200 focus:outline-none focus:ring-2 focus:ring-blue-500" data-principal-id="${idPrincipal}" data-sublinea-id="${idSublinea}" placeholder="Texto alternativo (opcional)" value="${textoAlternativo.replace(/"/g, '&quot;')}" ${!isChecked ? 'disabled' : ''} style="display: ${esColumna ? 'block' : 'none'};">`;
                    
                    // Icono de ayuda "?" solo en la primera sublínea
                    if (esPrimeraSublinea) {
                        html += `<div class="relative">`;
                        html += `<button type="button" class="tooltip-btn text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 cursor-help focus:outline-none ml-1" aria-label="Ayuda" data-tooltip='Las opciones marcadas serán las que aplique este producto para el filtro de categoría'>`;
                        html += `<svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">`;
                        html += `<path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm0 8a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"></path>`;
                        html += `</svg>`;
                        html += `</button>`;
                        html += `</div>`;
                    }
                    
                    // Checkbox "Mostrar" con icono de ayuda (solo en la primera sublínea)
                    html += `<div class="flex items-center gap-1">`;
                    html += `<label class="flex items-center gap-1 ${isChecked ? 'cursor-pointer' : 'cursor-not-allowed opacity-50'}" title="Mostrar en comparador">`;
                    html += `<input type="checkbox" class="especificacion-mostrar-checkbox rounded border-gray-300 text-blue-600 focus:ring-blue-500" data-principal-id="${idPrincipal}" data-sublinea-id="${idSublinea}" ${mostrarChecked ? 'checked' : ''} ${!isChecked ? 'disabled' : ''}>`;
                    html += `<span class="text-xs text-gray-600 dark:text-gray-400">Mostrar</span>`;
                    html += `</label>`;
                    if (esPrimeraSublinea) {
                        html += `<div class="relative">`;
                        html += `<button type="button" class="tooltip-btn text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 cursor-help focus:outline-none" aria-label="Ayuda" data-tooltip='Esta opcion aparecerá en la vista del producto, encima de los filtros de ofertas'>`;
                        html += `<svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">`;
                        html += `<path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm0 8a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"></path>`;
                        html += `</svg>`;
                        html += `</button>`;
                        html += `</div>`;
                    }
                    html += `</div>`;
                    
                    // Checkbox "Oferta" con icono de ayuda (solo en la primera sublínea)
                    html += `<div class="flex items-center gap-1">`;
                    html += `<label class="flex items-center gap-1 ${isChecked ? 'cursor-pointer' : 'cursor-not-allowed opacity-50'}" title="Disponible para ofertas">`;
                    html += `<input type="checkbox" class="especificacion-oferta-checkbox rounded border-gray-300 text-purple-600 focus:ring-purple-500" data-principal-id="${idPrincipal}" data-sublinea-id="${idSublinea}" ${ofertaChecked ? 'checked' : ''} ${!isChecked ? 'disabled' : ''}>`;
                    html += `<span class="text-xs text-gray-600 dark:text-gray-400">Oferta</span>`;
                    html += `</label>`;
                    if (esPrimeraSublinea) {
                        html += `<div class="relative">`;
                        html += `<button type="button" class="tooltip-btn text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 cursor-help focus:outline-none" aria-label="Ayuda" data-tooltip='Todas las ofertas vinculadas a este producto, podrán marcar esta opcion para que aplique en el filtrado de opciones de la vista de producto'>`;
                        html += `<svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">`;
                        html += `<path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm0 8a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"></path>`;
                        html += `</svg>`;
                        html += `</button>`;
                        html += `</div>`;
                    }
                    html += `</div>`;
                    
                    // Botón de imágenes para la sublínea
                    // Leer si está marcado como usar imágenes del producto
                    const usarImagenesProducto = sublineaData && sublineaData.usarImagenesProducto === true;
                    html += `<div class="flex items-center gap-2">`;
                    if (numImagenes > 0) {
                        const btnVerDeshabilitado = !isChecked || usarImagenesProducto;
                        html += `<button type="button" class="btn-ver-imagenes-sublinea text-xs px-2 py-1 bg-blue-600 hover:bg-blue-700 text-white rounded transition-colors ${btnVerDeshabilitado ? 'opacity-50 cursor-not-allowed' : ''}" data-principal-id="${idPrincipal}" data-sublinea-id="${idSublinea}" title="Ver ${numImagenes} imagen${numImagenes > 1 ? 'es' : ''}" ${btnVerDeshabilitado ? 'disabled' : ''}>${numImagenes} img</button>`;
                    }
                    html += `<button type="button" class="btn-añadir-imagen-sublinea text-xs px-2 py-1 bg-green-600 hover:bg-green-700 text-white rounded transition-colors ${!isChecked || usarImagenesProducto ? 'opacity-50 cursor-not-allowed' : ''}" data-principal-id="${idPrincipal}" data-sublinea-id="${idSublinea}" title="Añadir imágenes" ${!isChecked || usarImagenesProducto ? 'disabled' : ''}>+imágenes</button>`;
                    // Checkbox "Imag. Producto" con icono de ayuda
                    html += `<div class="flex items-center gap-1">`;
                    html += `<label class="flex items-center gap-1 ${isChecked ? 'cursor-pointer' : 'cursor-not-allowed opacity-50'}" title="Usar imágenes del producto">`;
                    html += `<input type="checkbox" class="especificacion-usar-imagenes-producto-checkbox rounded border-gray-300 text-orange-600 focus:ring-orange-500" data-principal-id="${idPrincipal}" data-sublinea-id="${idSublinea}" ${usarImagenesProducto ? 'checked' : ''} ${!isChecked ? 'disabled' : ''}>`;
                    html += `<span class="text-xs text-gray-600 dark:text-gray-400">Imag. Producto</span>`;
                    html += `</label>`;
                    html += `<div class="relative">`;
                    html += `<button type="button" class="tooltip-btn text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 cursor-help focus:outline-none" aria-label="Ayuda" data-tooltip='Si marcamos este check, esta sublínea utilizará las imágenes del producto'>`;
                    html += `<svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">`;
                    html += `<path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm0 8a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"></path>`;
                    html += `</svg>`;
                    html += `</button>`;
                    html += `</div>`;
                    html += `</div>`;
                    html += `</div>`;
                    html += `</div>`;
                });
                
                html += `</div>`;
                html += `</details>`;
                html += `<div class="especificacion-grupo-opciones-acciones absolute right-0 top-0 z-10 flex items-center gap-2">`;
                html += `<button type="button" class="btn-editar-grupo-especificaciones shrink-0 text-xs px-3 py-1 rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-200 hover:bg-amber-50 dark:hover:bg-amber-900/30 shadow-sm" data-principal-id="${idPrincipal}">Editar</button>`;
                html += `</div>`;
                html += `</div>`;
                html += `<div class="especificacion-grupo-editor hidden mb-3 p-4 border border-amber-300 dark:border-amber-600 rounded-lg bg-amber-50/50 dark:bg-amber-900/10" data-principal-id="${idPrincipal}">`;
                html += `<p class="text-xs text-gray-600 dark:text-gray-400 mb-3">Edita las opciones de este grupo. Al guardar se actualizan las especificaciones internas de la categoría seleccionada arriba.</p>`;
                html += `<div class="especificacion-grupo-editor-principal flex flex-wrap items-center gap-2 mb-3">`;
                html += `<input type="text" class="editor-principal-texto flex-1 min-w-0 px-3 py-2 text-sm rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-200" value="${String(textoPrincipal).replace(/"/g, '&quot;')}" placeholder="Nombre del grupo">`;
                html += `<label class="flex items-center gap-1 text-sm text-gray-700 dark:text-gray-200 cursor-pointer shrink-0"><input type="checkbox" class="editor-principal-importante rounded border-gray-300 text-amber-600 focus:ring-amber-500" ${filtro.importante ? 'checked' : ''}><span>Importante</span></label>`;
                html += `<button type="button" class="btn-guardar-fila-editor-principal hidden shrink-0 text-xs px-3 py-1 rounded bg-green-600 hover:bg-green-700 text-white font-medium disabled:opacity-50">Guardar</button>`;
                html += `</div>`;
                html += `<div class="especificacion-grupo-editor-sublineas space-y-2"></div>`;
                html += `</div>`;
                
                // Contenedor para chips de opciones seleccionadas
                html += `<div class="mt-2 flex flex-wrap gap-2 chips-container-${idPrincipal}">`;
                
                // Mostrar opciones seleccionadas como chips (compatible con estructura antigua y nueva)
                const seleccionadas = subprincipales.filter(sub => {
                    if (!Array.isArray(opcionesSeleccionadas)) return false;
                    return opcionesSeleccionadas.some(item => {
                        if (typeof item === 'string' || typeof item === 'number') {
                            return String(item) === String(sub.id);
                        } else if (item && item.id) {
                            return String(item.id) === String(sub.id);
                        }
                        return false;
                    });
                });
                
                if (seleccionadas.length > 0) {
                    seleccionadas.forEach((sub) => {
                        html += kpHtmlChipEspecificacionSeleccionada(
                            idPrincipal,
                            sub.id,
                            sub.texto || '(Sin texto)'
                        );
                    });
                }
                
                html += `</div>`;
                
                // Desplegable de formato de visualización (solo si hay sublíneas marcadas como "mostrar")
                const formatoGuardado = normalizarFormato(opcionesGuardadas._formatos && opcionesGuardadas._formatos[idPrincipal]);
                html += `<div class="mt-3 formato-visualizacion-container" data-principal-id="${idPrincipal}" style="display: none;">`;
                html += `<label class="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-200">Formato de visualización:</label>`;
                html += `<select class="formato-visualizacion-select w-full px-3 py-2 rounded bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-gray-200 border border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500" data-principal-id="${idPrincipal}">`;
                html += `<option value="texto" ${formatoGuardado === 'texto' ? 'selected' : ''}>Texto</option>`;
                html += `<option value="texto_precio" ${formatoGuardado === 'texto_precio' ? 'selected' : ''}>Texto y precio</option>`;
                html += `<option value="imagen" ${formatoGuardado === 'imagen' ? 'selected' : ''}>Imagen</option>`;
                html += `<option value="imagen_texto" ${formatoGuardado === 'imagen_texto' ? 'selected' : ''}>Imagen y texto</option>`;
                html += `<option value="imagen_precio" ${formatoGuardado === 'imagen_precio' ? 'selected' : ''}>Imagen y precio</option>`;
                html += `<option value="imagen_texto_precio" ${formatoGuardado === 'imagen_texto_precio' ? 'selected' : ''}>Imagen, texto y precio</option>`;
                html += `</select>`;
                html += `<p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Selecciona cómo se mostrarán las sublíneas marcadas como "Mostrar" en la vista del comparador</p>`;
                html += `</div>`;
                
                html += `</div>`;
            });
            
            html += '</div>';
            contenedor.innerHTML = html;

            kpInicializarDelegacionQuitarChipEspecificacion(contenedor);
            
            // Configurar drag and drop si es unidadUnica
            if (esUnidadUnica) {
                configurarDragAndDropEspecificaciones(contenedor);
            }
            
            // Añadir event listeners a los checkboxes de "Columna oferta"
            if (mostrarUIColumnaOferta) {
                const columnaCheckboxes = contenedor.querySelectorAll('.columna-oferta-checkbox');
                columnaCheckboxes.forEach(checkbox => {
                    // Mostrar campos de texto alternativo si ya está marcado al cargar
                    if (checkbox.checked) {
                        const principalId = checkbox.dataset.principalId;
                        const camposTextoAlternativo = contenedor.querySelectorAll(`.texto-alternativo-sublinea-input[data-principal-id="${principalId}"]`);
                        camposTextoAlternativo.forEach(campo => {
                            campo.style.display = 'block';
                            const sublineaCheckbox = contenedor.querySelector(`.especificacion-checkbox[data-principal-id="${principalId}"][data-sublinea-id="${campo.dataset.sublineaId}"]`);
                            campo.disabled = !sublineaCheckbox || !sublineaCheckbox.checked;
                        });
                    }
                    
                    checkbox.addEventListener('change', function() {
                        const columnasMarcadas = Array.from(contenedor.querySelectorAll('.columna-oferta-checkbox:checked'));
                        if (columnasMarcadas.length > maxColumnasOferta) {
                            this.checked = false;
                            alert(esUnidadUnica
                                ? 'Solo se pueden marcar hasta 4 líneas principales como columna oferta.'
                                : 'Solo se puede marcar una línea principal como columna oferta.');
                            return;
                        }
                        
                        // Mostrar/ocultar campos de texto alternativo para las sublíneas de esta línea principal
                        const principalId = this.dataset.principalId;
                        const camposTextoAlternativo = contenedor.querySelectorAll(`.texto-alternativo-sublinea-input[data-principal-id="${principalId}"]`);
                        camposTextoAlternativo.forEach(campo => {
                            if (this.checked) {
                                campo.style.display = 'block';
                                // Habilitar el campo solo si la sublínea está marcada
                                const sublineaCheckbox = contenedor.querySelector(`.especificacion-checkbox[data-principal-id="${principalId}"][data-sublinea-id="${campo.dataset.sublineaId}"]`);
                                campo.disabled = !sublineaCheckbox || !sublineaCheckbox.checked;
                            } else {
                                campo.style.display = 'none';
                            }
                        });
                        actualizarEspecificacionesElegidas();
                    });
                });
            }
            
            // Añadir event listeners a los campos de texto alternativo para actualizar cuando cambien
            const camposTextoAlternativo = contenedor.querySelectorAll('.texto-alternativo-sublinea-input');
            camposTextoAlternativo.forEach(campo => {
                campo.addEventListener('input', function() {
                    actualizarEspecificacionesElegidas();
                });
            });
            
            // Añadir event listeners a los checkboxes principales
            const checkboxes = contenedor.querySelectorAll('.especificacion-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    // Habilitar/deshabilitar checkboxes de "Mostrar", "Oferta" y "Imag. Producto"
                    const principalId = this.dataset.principalId;
                    const sublineaId = this.dataset.sublineaId;
                    const mostrarCheckbox = contenedor.querySelector(`.especificacion-mostrar-checkbox[data-principal-id="${principalId}"][data-sublinea-id="${sublineaId}"]`);
                    const ofertaCheckbox = contenedor.querySelector(`.especificacion-oferta-checkbox[data-principal-id="${principalId}"][data-sublinea-id="${sublineaId}"]`);
                    const usarImagenesProductoCheckbox = contenedor.querySelector(`.especificacion-usar-imagenes-producto-checkbox[data-principal-id="${principalId}"][data-sublinea-id="${sublineaId}"]`);
                    
                    if (mostrarCheckbox) {
                        mostrarCheckbox.disabled = !this.checked;
                        // Actualizar estilos visuales
                        const mostrarLabel = mostrarCheckbox.closest('label');
                        if (mostrarLabel) {
                            if (this.checked) {
                                mostrarLabel.classList.remove('opacity-50', 'cursor-not-allowed');
                                mostrarLabel.classList.add('cursor-pointer');
                            } else {
                                mostrarLabel.classList.remove('cursor-pointer');
                                mostrarLabel.classList.add('opacity-50', 'cursor-not-allowed');
                            }
                        }
                    }
                    if (ofertaCheckbox) {
                        ofertaCheckbox.disabled = !this.checked;
                        // Actualizar estilos visuales
                        const ofertaLabel = ofertaCheckbox.closest('label');
                        if (ofertaLabel) {
                            if (this.checked) {
                                ofertaLabel.classList.remove('opacity-50', 'cursor-not-allowed');
                                ofertaLabel.classList.add('cursor-pointer');
                            } else {
                                ofertaLabel.classList.remove('cursor-pointer');
                                ofertaLabel.classList.add('opacity-50', 'cursor-not-allowed');
                            }
                        }
                    }
                    if (usarImagenesProductoCheckbox) {
                        usarImagenesProductoCheckbox.disabled = !this.checked;
                        // Actualizar estilos visuales
                        const imagProductoLabel = usarImagenesProductoCheckbox.closest('label');
                        if (imagProductoLabel) {
                            if (this.checked) {
                                imagProductoLabel.classList.remove('opacity-50', 'cursor-not-allowed');
                                imagProductoLabel.classList.add('cursor-pointer');
                            } else {
                                imagProductoLabel.classList.remove('cursor-pointer');
                                imagProductoLabel.classList.add('opacity-50', 'cursor-not-allowed');
                            }
                        }
                    }
                    
                    // Actualizar botones de imágenes
                    const btnVerImagen = contenedor.querySelector(`.btn-ver-imagenes-sublinea[data-principal-id="${principalId}"][data-sublinea-id="${sublineaId}"]`);
                    const btnAñadirImagen = contenedor.querySelector(`.btn-añadir-imagen-sublinea[data-principal-id="${principalId}"][data-sublinea-id="${sublineaId}"]`);
                    
                    // Verificar si "Imag. Producto" está marcado (si está marcado, los botones deben estar deshabilitados)
                    const usarImagenesProducto = usarImagenesProductoCheckbox && usarImagenesProductoCheckbox.checked;
                    const debenEstarDeshabilitados = !this.checked || usarImagenesProducto;
                    
                    if (btnVerImagen) {
                        btnVerImagen.disabled = debenEstarDeshabilitados;
                        if (debenEstarDeshabilitados) {
                            btnVerImagen.classList.add('opacity-50', 'cursor-not-allowed');
                        } else {
                            btnVerImagen.classList.remove('opacity-50', 'cursor-not-allowed');
                        }
                    }
                    if (btnAñadirImagen) {
                        btnAñadirImagen.disabled = debenEstarDeshabilitados;
                        if (debenEstarDeshabilitados) {
                            btnAñadirImagen.classList.add('opacity-50', 'cursor-not-allowed');
                        } else {
                            btnAñadirImagen.classList.remove('opacity-50', 'cursor-not-allowed');
                        }
                    }
                    
                    const textoAlternativoInput = contenedor.querySelector(`.texto-alternativo-sublinea-input[data-principal-id="${principalId}"][data-sublinea-id="${sublineaId}"]`);

                    // Si se desmarca, desmarcar también "Mostrar", "Oferta", "Usar imágenes del producto" y limpiar texto alternativo
                    if (!this.checked) {
                        if (mostrarCheckbox) mostrarCheckbox.checked = false;
                        if (ofertaCheckbox) ofertaCheckbox.checked = false;
                        if (usarImagenesProductoCheckbox) usarImagenesProductoCheckbox.checked = false;
                        if (textoAlternativoInput) textoAlternativoInput.value = '';
                    }

                    if (textoAlternativoInput) {
                        textoAlternativoInput.disabled = !this.checked;
                    }
                    
                    // Resaltar/quitar resaltado de botones de palabras clave relacionadas
                    const textoSublinea = this.dataset.sublineaTexto;
                    if (textoSublinea) {
                        resaltarBotonesPalabrasClave(textoSublinea, this.checked);
                    }

                    actualizarEspecificacionesElegidas();
                    actualizarVisibilidadFormatoVisualizacion(contenedor);
                });
            });
            
            // Añadir event listeners a los checkboxes de "Mostrar", "Oferta" y "Columna"
            const mostrarCheckboxes = contenedor.querySelectorAll('.especificacion-mostrar-checkbox');
            mostrarCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    const principalId = this.dataset.principalId;
                    const sublineaId = this.dataset.sublineaId;
                    const configCat = window.kpEspecificacionesCategoriaState?.configuracion_formulario_producto || 'ninguno';
                    const usarNoColumnaImagenNombrePrecio = kpEsConfigNoColumnaGrupoImagenNombrePrecio(configCat);
                    
                    // Si se marca "Mostrar", marcar también "Oferta" de la misma sublínea
                    if (this.checked) {
                        const ofertaCheckbox = contenedor.querySelector(`.especificacion-oferta-checkbox[data-principal-id="${principalId}"][data-sublinea-id="${sublineaId}"]`);
                        if (ofertaCheckbox && !ofertaCheckbox.checked) {
                            ofertaCheckbox.checked = true;
                        }
                        
                        if (usarNoColumnaImagenNombrePrecio) {
                            const formatoSelect = contenedor.querySelector(`.formato-visualizacion-select[data-principal-id="${principalId}"]`);
                            if (formatoSelect) {
                                formatoSelect.value = 'imagen_texto_precio';
                            }
                        } else if (mostrarUIColumnaOferta) {
                            // Marcar también "Columna oferta" del grupo si aplica esa UI y no está marcado
                            const columnaCheckbox = contenedor.querySelector(`.columna-oferta-checkbox[data-principal-id="${principalId}"]`);
                            if (columnaCheckbox && !columnaCheckbox.checked) {
                                const columnasMarcadas = Array.from(contenedor.querySelectorAll('.columna-oferta-checkbox:checked'));
                                if (columnasMarcadas.length < maxColumnasOferta) {
                                    columnaCheckbox.checked = true;
                                    columnaCheckbox.dispatchEvent(new Event('change'));
                                }
                            }
                        }
                    }
                    
                    actualizarEspecificacionesElegidas();
                    actualizarVisibilidadFormatoVisualizacion(contenedor);
                });
            });
            
            const ofertaCheckboxes = contenedor.querySelectorAll('.especificacion-oferta-checkbox');
            ofertaCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    if (this.checked) {
                        const principalId = this.dataset.principalId;
                        const configCat = window.kpEspecificacionesCategoriaState?.configuracion_formulario_producto || 'ninguno';
                        if (kpEsConfigNoColumnaGrupoImagenNombrePrecio(configCat)) {
                            const formatoSelect = contenedor.querySelector(`.formato-visualizacion-select[data-principal-id="${principalId}"]`);
                            if (formatoSelect) {
                                formatoSelect.value = 'imagen_texto_precio';
                            }
                        }
                    }
                    actualizarEspecificacionesElegidas();
                    actualizarVisibilidadFormatoVisualizacion(contenedor);
                });
            });
            
            // Añadir event listeners a los checkboxes de "Usar imágenes del producto"
            const usarImagenesProductoCheckboxes = contenedor.querySelectorAll('.especificacion-usar-imagenes-producto-checkbox');
            usarImagenesProductoCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    const principalId = this.dataset.principalId;
                    const sublineaId = this.dataset.sublineaId;
                    
                    // Si se marca "usar imágenes del producto", deshabilitar botones de imágenes
                    if (this.checked) {
                        const btnVer = contenedor.querySelector(`.btn-ver-imagenes-sublinea[data-principal-id="${principalId}"][data-sublinea-id="${sublineaId}"]`);
                        const btnAñadir = contenedor.querySelector(`.btn-añadir-imagen-sublinea[data-principal-id="${principalId}"][data-sublinea-id="${sublineaId}"]`);
                        if (btnVer) {
                            btnVer.disabled = true;
                            btnVer.classList.add('opacity-50', 'cursor-not-allowed');
                        }
                        if (btnAñadir) {
                            btnAñadir.disabled = true;
                            btnAñadir.classList.add('opacity-50', 'cursor-not-allowed');
                        }
                    } else {
                        // Verificar si el checkbox principal está marcado antes de habilitar
                        const sublineaCheckbox = contenedor.querySelector(`.especificacion-checkbox[data-principal-id="${principalId}"][data-sublinea-id="${sublineaId}"]`);
                        const estaMarcada = sublineaCheckbox && sublineaCheckbox.checked;
                        
                        const btnVer = contenedor.querySelector(`.btn-ver-imagenes-sublinea[data-principal-id="${principalId}"][data-sublinea-id="${sublineaId}"]`);
                        const btnAñadir = contenedor.querySelector(`.btn-añadir-imagen-sublinea[data-principal-id="${principalId}"][data-sublinea-id="${sublineaId}"]`);
                        if (btnVer) {
                            btnVer.disabled = !estaMarcada;
                            if (estaMarcada) {
                                btnVer.classList.remove('opacity-50', 'cursor-not-allowed');
                            } else {
                                btnVer.classList.add('opacity-50', 'cursor-not-allowed');
                            }
                        }
                        if (btnAñadir) {
                            btnAñadir.disabled = !estaMarcada;
                            if (estaMarcada) {
                                btnAñadir.classList.remove('opacity-50', 'cursor-not-allowed');
                            } else {
                                btnAñadir.classList.add('opacity-50', 'cursor-not-allowed');
                            }
                        }
                    }
                    
                    actualizarEspecificacionesElegidas();
                });
            });
            
            // Añadir event listeners a los desplegables de formato de visualización
            const formatoSelects = contenedor.querySelectorAll('.formato-visualizacion-select');
            formatoSelects.forEach(select => {
                select.addEventListener('change', actualizarEspecificacionesElegidas);
            });
            
            // Configurar tooltips con click
            configurarTooltips(contenedor);

            kpConfigurarBuscadoresGrupoEspecificaciones(contenedor);
            kpInicializarEditoresGrupoEspecificaciones();
            
            // Configurar event listeners para botones de imágenes de sublíneas
            configurarBotonesImagenesSublineas(contenedor);
            
            // Actualizar visibilidad de los desplegables de formato
            actualizarVisibilidadFormatoVisualizacion(contenedor);
            
            // Actualizar JSON inicial
            actualizarEspecificacionesElegidas();

            if (typeof kpAplicarCoincidenciasDesdeNombre === 'function') {
                kpAplicarCoincidenciasDesdeNombre();
            }
        }
        
        // Función para configurar tooltips con click
        function configurarTooltips(contenedor) {
            const tooltipButtons = contenedor.querySelectorAll('.tooltip-btn');
            let tooltipActual = null;
            
            tooltipButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    e.stopPropagation();
                    const tooltipTexto = this.getAttribute('data-tooltip');
                    
                    // Cerrar tooltip anterior si existe
                    if (tooltipActual) {
                        tooltipActual.remove();
                        tooltipActual = null;
                        return;
                    }
                    
                    // Crear nuevo tooltip
                    const tooltip = document.createElement('div');
                    tooltip.className = 'fixed z-50 bg-gray-900 text-white text-xs rounded shadow-lg p-2 max-w-xs pointer-events-none';
                    tooltip.textContent = tooltipTexto;
                    tooltip.style.opacity = '0';
                    tooltip.style.transition = 'opacity 0.2s';
                    document.body.appendChild(tooltip);
                    
                    // Posicionar tooltip
                    const rect = this.getBoundingClientRect();
                    tooltip.style.left = rect.left + 'px';
                    tooltip.style.top = (rect.top - tooltip.offsetHeight - 8) + 'px';
                    
                    // Ajustar si se sale de la pantalla
                    setTimeout(() => {
                        const tooltipRect = tooltip.getBoundingClientRect();
                        if (tooltipRect.left < 0) {
                            tooltip.style.left = '8px';
                        }
                        if (tooltipRect.right > window.innerWidth) {
                            tooltip.style.left = (window.innerWidth - tooltipRect.width - 8) + 'px';
                        }
                        if (tooltipRect.top < 0) {
                            tooltip.style.top = (rect.bottom + 8) + 'px';
                        }
                        tooltip.style.opacity = '1';
                    }, 10);
                    
                    tooltipActual = tooltip;
                    
                    // Cerrar tooltip al hacer click fuera o en otro botón
                    const cerrarTooltip = (e) => {
                        if (tooltipActual && !tooltipActual.contains(e.target) && e.target !== button) {
                            tooltipActual.remove();
                            tooltipActual = null;
                            document.removeEventListener('click', cerrarTooltip);
                        }
                    };
                    
                    setTimeout(() => {
                        document.addEventListener('click', cerrarTooltip);
                    }, 100);
                });
            });
        }
        
        // Variables globales para drag and drop de especificaciones
        let elementoArrastradoEspecificaciones = null;
        
        // Configurar drag and drop para reordenar líneas principales de especificaciones
        function configurarDragAndDropEspecificaciones(contenedor) {
            const contenedorPrincipal = contenedor.querySelector('#especificaciones-principales-container');
            if (!contenedorPrincipal) return;
            
            const dragHandles = contenedorPrincipal.querySelectorAll('.drag-handle-principal-especificaciones');
            
            dragHandles.forEach(handle => {
                const lineaPrincipal = handle.closest('.linea-principal-especificaciones');
                if (!lineaPrincipal) return;
                
                // Prevenir drag en el icono mismo
                handle.addEventListener('dragstart', (e) => {
                    elementoArrastradoEspecificaciones = lineaPrincipal;
                    lineaPrincipal.style.opacity = '0.5';
                    e.dataTransfer.effectAllowed = 'move';
                });
                
                handle.addEventListener('dragend', (e) => {
                    if (lineaPrincipal) {
                        lineaPrincipal.style.opacity = '1';
                    }
                    elementoArrastradoEspecificaciones = null;
                    actualizarEspecificacionesElegidas();
                });
            });
            
            // Configurar drop en las líneas principales
            const lineasPrincipales = contenedorPrincipal.querySelectorAll('.linea-principal-especificaciones');
            lineasPrincipales.forEach(linea => {
                linea.addEventListener('dragover', (e) => {
                    if (!elementoArrastradoEspecificaciones || elementoArrastradoEspecificaciones === linea) return;
                    
                    e.preventDefault();
                    e.stopPropagation();
                    e.dataTransfer.dropEffect = 'move';
                    
                    const rect = linea.getBoundingClientRect();
                    const mouseY = e.clientY;
                    const elementMiddle = rect.top + rect.height / 2;
                    const insertAfter = mouseY > elementMiddle;
                    
                    const parent = contenedorPrincipal;
                    const siblings = Array.from(parent.children).filter(child => 
                        child.classList.contains('linea-principal-especificaciones')
                    );
                    
                    const currentIndex = siblings.indexOf(linea);
                    const draggedIndex = siblings.indexOf(elementoArrastradoEspecificaciones);
                    
                    if (draggedIndex === -1) return;
                    
                    if (insertAfter && draggedIndex === currentIndex + 1) return;
                    if (!insertAfter && draggedIndex === currentIndex - 1) return;
                    
                    if (insertAfter) {
                        const nextSibling = linea.nextSibling;
                        if (nextSibling && nextSibling !== elementoArrastradoEspecificaciones) {
                            parent.insertBefore(elementoArrastradoEspecificaciones, nextSibling);
                        } else if (!nextSibling) {
                            parent.appendChild(elementoArrastradoEspecificaciones);
                        }
                    } else {
                        if (linea !== elementoArrastradoEspecificaciones) {
                            parent.insertBefore(elementoArrastradoEspecificaciones, linea);
                        }
                    }
                });
                
                linea.addEventListener('drop', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                });
            });
            
            // Configurar drop en el contenedor principal
            contenedorPrincipal.addEventListener('dragover', (e) => {
                if (!elementoArrastradoEspecificaciones) return;
                
                const target = e.target;
                if (target.classList.contains('linea-principal-especificaciones')) {
                    return;
                }
                
                const parentElement = target.closest('.linea-principal-especificaciones');
                if (parentElement) {
                    return;
                }
                
                e.preventDefault();
                e.dataTransfer.dropEffect = 'move';
                
                const siblings = Array.from(contenedorPrincipal.children).filter(child => 
                    child.classList.contains('linea-principal-especificaciones')
                );
                
                if (siblings.length === 0) {
                    contenedorPrincipal.appendChild(elementoArrastradoEspecificaciones);
                    return;
                }
                
                if (!siblings.includes(elementoArrastradoEspecificaciones)) {
                    const rect = contenedorPrincipal.getBoundingClientRect();
                    const mouseY = e.clientY;
                    if (mouseY > rect.top + rect.height * 0.7) {
                        contenedorPrincipal.appendChild(elementoArrastradoEspecificaciones);
                    }
                    return;
                }
            });
            
            contenedorPrincipal.addEventListener('drop', (e) => {
                e.preventDefault();
            });
        }

        // Función para actualizar el JSON de especificaciones elegidas
        // Función para actualizar la visibilidad del desplegable de formato de visualización
        function actualizarVisibilidadFormatoVisualizacion(contenedor) {
            const contenedorPrincipal = contenedor || document.querySelector('#especificaciones-principales-container');
            if (!contenedorPrincipal) return;
            
            const lineasPrincipales = contenedorPrincipal.querySelectorAll('.linea-principal-especificaciones');
            
            lineasPrincipales.forEach(linea => {
                const principalId = linea.dataset.principalId;
                const formatoContainer = linea.querySelector(`.formato-visualizacion-container[data-principal-id="${principalId}"]`);
                
                if (!formatoContainer) return;
                
                // Verificar si hay sublíneas marcadas como "mostrar"
                const mostrarCheckboxes = linea.querySelectorAll(`.especificacion-mostrar-checkbox[data-principal-id="${principalId}"]:checked`);
                const tieneSublineasMarcadasComoMostrar = mostrarCheckboxes.length > 0;
                
                // Mostrar/ocultar el desplegable
                formatoContainer.style.display = tieneSublineasMarcadasComoMostrar ? 'block' : 'none';
            });
        }
        
        function actualizarEspecificacionesElegidas() {
            const inputHidden = document.getElementById('categoria_especificaciones_internas_elegidas_input');
            const checkboxes = document.querySelectorAll('.especificacion-checkbox');
            const especificaciones = {};
            
            // Leer especificaciones guardadas para preservar imágenes
            let especificacionesGuardadas = {};
            if (inputHidden && inputHidden.value) {
                try {
                    especificacionesGuardadas = JSON.parse(inputHidden.value);
                } catch (e) {
                    console.error('Error al parsear especificaciones guardadas:', e);
                }
            }
            
            // PRESERVAR PRIMERO las especificaciones del producto ANTES de procesar checkboxes de categoría
            // Esto asegura que las especificaciones del producto siempre se guarden
            if (especificacionesGuardadas._producto) {
                especificaciones._producto = especificacionesGuardadas._producto;
                
                // Preservar TODAS las sublíneas del producto guardadas directamente por ID de línea principal
                if (especificacionesGuardadas._producto.filtros) {
                    especificacionesGuardadas._producto.filtros.forEach(filtro => {
                        const principalId = filtro.id;
                        // Si esta línea principal del producto tiene sublíneas guardadas
                        if (especificacionesGuardadas[principalId] && Array.isArray(especificacionesGuardadas[principalId])) {
                            // PRESERVAR las sublíneas del producto
                            especificaciones[principalId] = especificacionesGuardadas[principalId];
                        }
                    });
                }
            }
            
            // También preservar cualquier otra clave que pertenezca al producto
            if (especificacionesGuardadas._producto && especificacionesGuardadas._producto.filtros) {
                const idsProducto = especificacionesGuardadas._producto.filtros.map(f => f.id);
                Object.keys(especificacionesGuardadas).forEach(key => {
                    // Si es una clave especial o ya la preservamos, saltar
                    if (['_formatos', '_orden', '_columnas', '_producto'].includes(key)) {
                        return;
                    }
                    // Si esta clave corresponde a un ID de línea principal del producto
                    if (idsProducto.includes(key) && especificacionesGuardadas[key]) {
                        // Ya la preservamos arriba, pero asegurarnos por si acaso
                        if (!especificaciones[key]) {
                            especificaciones[key] = especificacionesGuardadas[key];
                        }
                    }
                });
            }
            
            // Verificar si es unidadUnica para guardar orden y columnas
            const unidadDeMedidaSelect = document.getElementById('unidadDeMedida');
            const esUnidadUnica = unidadDeMedidaSelect && unidadDeMedidaSelect.value === 'unidadUnica';
            
            // Inicializar _formatos preservando los formatos guardados (compatibilidad con {id,c})
            const formatosRaw = especificacionesGuardadas._formatos || {};
            especificaciones._formatos = {};
            if (formatosRaw && typeof formatosRaw === 'object') {
                Object.entries(formatosRaw).forEach(([k, v]) => {
                    if (v && typeof v === 'object' && !Array.isArray(v)) {
                        if (typeof v.id === 'string') {
                            especificaciones._formatos[k] = v.id;
                        }
                    } else if (typeof v === 'string') {
                        especificaciones._formatos[k] = v;
                    }
                });
            }
            
            // Obtener los IDs de las líneas principales del producto para evitar sobrescribirlas
            const idsProducto = (especificacionesGuardadas._producto && especificacionesGuardadas._producto.filtros) 
                ? especificacionesGuardadas._producto.filtros.map(f => f.id) 
                : [];
            
            checkboxes.forEach(checkbox => {
                if (!checkbox.checked) return;
                
                const principalId = checkbox.dataset.principalId;
                const sublineaId = checkbox.dataset.sublineaId;
                
                // Si este ID pertenece al producto, NO procesarlo aquí (ya está preservado)
                if (idsProducto.some(fid => String(fid) === String(principalId))) {
                    return; // Saltar este checkbox, es del producto, no de categoría
                }
                
                if (!especificaciones[principalId]) {
                    especificaciones[principalId] = [];
                }
                
                // Obtener estado de "Mostrar", "Oferta", "Columna" y "Usar imágenes del producto"
                const mostrarCheckbox = document.querySelector(`.especificacion-mostrar-checkbox[data-principal-id="${principalId}"][data-sublinea-id="${sublineaId}"]`);
                const ofertaCheckbox = document.querySelector(`.especificacion-oferta-checkbox[data-principal-id="${principalId}"][data-sublinea-id="${sublineaId}"]`);
                const columnaCheckbox = document.querySelector(`.columna-sublinea-checkbox[data-principal-id="${principalId}"][data-sublinea-id="${sublineaId}"]`);
                const usarImagenesProductoCheckbox = document.querySelector(`.especificacion-usar-imagenes-producto-checkbox[data-principal-id="${principalId}"][data-sublinea-id="${sublineaId}"]`);
                const textoAlternativoInput = document.querySelector(`.texto-alternativo-sublinea-input[data-principal-id="${principalId}"][data-sublinea-id="${sublineaId}"]`);
                
                const mostrar = mostrarCheckbox ? mostrarCheckbox.checked : false;
                const oferta = ofertaCheckbox ? ofertaCheckbox.checked : false;
                const usarImagenesProducto = usarImagenesProductoCheckbox ? usarImagenesProductoCheckbox.checked : false;
                const textoAlternativo = textoAlternativoInput ? textoAlternativoInput.value.trim() : '';
                
                // Leer imágenes guardadas de esta sublínea
                let imagenesSublinea = [];
                if (especificacionesGuardadas[principalId]) {
                    const sublineaGuardada = especificacionesGuardadas[principalId].find(item => {
                        if (typeof item === 'string' || typeof item === 'number') {
                            return String(item) === String(sublineaId);
                        } else if (item && item.id) {
                            return String(item.id) === String(sublineaId);
                        }
                        return false;
                    });
                    if (sublineaGuardada && sublineaGuardada.img && Array.isArray(sublineaGuardada.img)) {
                        imagenesSublinea = sublineaGuardada.img;
                    }
                }
                
                // Guardar como objeto optimizado: solo guardar flags si son true
                const item = { id: sublineaId };
                if (mostrar) item.m = 1; // 'm' = mostrar (optimizado)
                if (oferta) item.o = 1; // 'o' = oferta (optimizado)
                if (usarImagenesProducto) item.usarImagenesProducto = true; // Usar imágenes del producto
                // Solo guardar imágenes si no se está usando las del producto
                if (!usarImagenesProducto && imagenesSublinea.length > 0) item.img = imagenesSublinea; // 'img' = imágenes (array)
                // Guardar texto alternativo si existe (solo para líneas marcadas como columna oferta)
                if (textoAlternativo) item.textoAlternativo = textoAlternativo;
                
                especificaciones[principalId].push(item);
            });
            
            // Guardar formato de visualización para cada línea principal
            if (!especificaciones._formatos) {
                especificaciones._formatos = {};
            }
            
            const contenedorPrincipal = document.querySelector('#especificaciones-principales-container');
            if (contenedorPrincipal) {
                const lineasPrincipales = contenedorPrincipal.querySelectorAll('.linea-principal-especificaciones');
                lineasPrincipales.forEach(linea => {
                    const principalId = linea.dataset.principalId;
                    const formatoSelect = linea.querySelector(`.formato-visualizacion-select[data-principal-id="${principalId}"]`);
                    
                    if (formatoSelect) {
                        // Guardar el formato en el objeto _formatos
                        especificaciones._formatos[principalId] = formatoSelect.value;
                    }
                });
            }
            
            // Limpiar arrays vacíos
            Object.keys(especificaciones).forEach(key => {
                if (especificaciones[key].length === 0) {
                    delete especificaciones[key];
                }
            });
            
            // Si es unidadUnica, guardar orden y columnas (combinando con las del producto)
            const contenedorPrincipalCol = document.querySelector('#especificaciones-principales-container');
            const hayUiColumnaCategoria = !!(contenedorPrincipalCol && contenedorPrincipalCol.querySelector('.columna-oferta-checkbox'));

            if (contenedorPrincipalCol && hayUiColumnaCategoria) {
                if (esUnidadUnica) {
                    const lineasPrincipales = Array.from(contenedorPrincipalCol.querySelectorAll('.linea-principal-especificaciones'));
                    especificaciones._orden = lineasPrincipales.map(linea => linea.dataset.principalId);
                }

                const columnasCategoria = Array.from(contenedorPrincipalCol.querySelectorAll('.columna-oferta-checkbox:checked')).map(cb => String(cb.dataset.principalId));
                const contenedorProductoCol = document.querySelector('#especificaciones-producto-container');
                let columnasProducto = [];
                if (contenedorProductoCol) {
                    columnasProducto = Array.from(contenedorProductoCol.querySelectorAll('.columna-oferta-producto-checkbox:checked')).map(cb => String(cb.dataset.principalId));
                }
                const todosMarcados = [...new Set([...columnasCategoria, ...columnasProducto])];
                especificaciones._columnas = esUnidadUnica ? todosMarcados : todosMarcados.slice(0, 1);
            } else if (especificacionesGuardadas._columnas !== undefined && especificacionesGuardadas._columnas !== null && !hayUiColumnaCategoria) {
                especificaciones._columnas = especificacionesGuardadas._columnas;
            }

            // Preservar _orden y _columnas si la UI anterior no los rellenó
            if (especificaciones._orden === undefined && especificacionesGuardadas._orden !== undefined && Array.isArray(especificacionesGuardadas._orden)) {
                especificaciones._orden = especificacionesGuardadas._orden.map(String);
            }
            if (especificaciones._columnas === undefined && especificacionesGuardadas._columnas !== undefined && especificacionesGuardadas._columnas !== null) {
                especificaciones._columnas = especificacionesGuardadas._columnas;
            }

            if (especificacionesGuardadas._producto) {
                especificaciones._producto = especificacionesGuardadas._producto;
            }
            
            // Preservar TODAS las sublíneas del producto guardadas directamente por ID de línea principal
            // Las sublíneas del producto se guardan directamente con el ID principal como clave
            if (especificacionesGuardadas._producto && especificacionesGuardadas._producto.filtros) {
                // Obtener todos los IDs de líneas principales del producto
                const idsProducto = especificacionesGuardadas._producto.filtros.map(f => f.id);
                
                // Preservar TODAS las claves que corresponden a IDs de líneas principales del producto
                idsProducto.forEach(principalId => {
                    // Si esta línea principal del producto tiene sublíneas guardadas
                    if (especificacionesGuardadas[principalId] && Array.isArray(especificacionesGuardadas[principalId])) {
                        // SIEMPRE preservar las sublíneas del producto (tienen prioridad)
                        // Solo sobrescribir si no hay especificaciones de categoría con el mismo ID
                        if (!especificaciones[principalId] || especificaciones[principalId].length === 0) {
                            especificaciones[principalId] = especificacionesGuardadas[principalId];
                        } else {
                            // Si hay conflicto, las del producto tienen prioridad porque se guardaron más recientemente
                            // Pero verificar: si las especificaciones actuales son de categoría (tienen clase .especificacion-checkbox),
                            // mantener ambas o priorizar producto
                            // Por ahora, priorizar producto si viene de _producto.filtros
                            especificaciones[principalId] = especificacionesGuardadas[principalId];
                        }
                    }
                });
            }
            
            // También preservar cualquier otra clave que podría ser del producto (por seguridad)
            // Las claves que no son especiales (_formatos, _orden, _columnas, _producto) y que no fueron procesadas
            // por los checkboxes de categoría, podrían ser del producto
            Object.keys(especificacionesGuardadas).forEach(key => {
                // Si es una clave especial, ya la manejamos
                if (['_formatos', '_orden', '_columnas', '_producto'].includes(key)) {
                    return;
                }
                
                // Si esta clave no está en las especificaciones actuales (no fue procesada por checkboxes de categoría)
                // y está en las guardadas, podría ser del producto
                if (!especificaciones[key] && especificacionesGuardadas[key]) {
                    // Verificar si pertenece al producto buscando en _producto.filtros
                    if (especificacionesGuardadas._producto && 
                        especificacionesGuardadas._producto.filtros &&
                        especificacionesGuardadas._producto.filtros.some(f => String(f.id) === String(key))) {
                        // Es del producto, preservarla
                        especificaciones[key] = especificacionesGuardadas[key];
                    }
                }
            });
            
            inputHidden.value = JSON.stringify(especificaciones, null, 0);
            
            // Actualizar los chips visuales
            actualizarChipsSeleccionados();
        }
        
        // Función para actualizar la visibilidad del desplegable de formato de visualización
        function actualizarVisibilidadFormatoVisualizacion(contenedor) {
            const contenedorPrincipal = contenedor || document.querySelector('#especificaciones-principales-container');
            if (!contenedorPrincipal) return;
            
            const lineasPrincipales = contenedorPrincipal.querySelectorAll('.linea-principal-especificaciones');
            
            lineasPrincipales.forEach(linea => {
                const principalId = linea.dataset.principalId;
                const formatoContainer = linea.querySelector(`.formato-visualizacion-container[data-principal-id="${principalId}"]`);
                
                if (!formatoContainer) return;
                
                // Verificar si hay sublíneas marcadas como "mostrar"
                const mostrarCheckboxes = linea.querySelectorAll(`.especificacion-mostrar-checkbox[data-principal-id="${principalId}"]:checked`);
                const tieneSublineasMarcadasComoMostrar = mostrarCheckboxes.length > 0;
                
                // Mostrar/ocultar el desplegable
                formatoContainer.style.display = tieneSublineasMarcadasComoMostrar ? 'block' : 'none';
            });
        }

        // Variables globales para gestión de imágenes de sublíneas
        let sublineaImagenesActual = { principalId: null, sublineaId: null, imagenes: [] };
        let cropperSublinea = null;
        let carpetaActualSublinea = null;
        let imagenTemporalUrlSublinea = null;

        const KP_PENDING_IMG = '__pending:';
        const uploadsPendientesSublinea = new Map();

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

        function kpNuevoIdSubida() {
            return Date.now().toString(36) + Math.random().toString(36).slice(2, 9);
        }

        function kpEsRutaPendiente(ruta) {
            return typeof ruta === 'string' && ruta.indexOf(KP_PENDING_IMG) === 0;
        }

        function kpUrlVistaDesdeRutaAlmacen(raw) {
            const t = (raw || '').trim();
            if (!t) return '';
            if (/^https?:\/\//i.test(t)) return t;
            if (/^\/\//.test(t)) return 'https:' + t;
            let p = t.replace(/^\/+/, '');
            if (/^images\//i.test(p)) {
                return @json(rtrim(url('/'), '/')) + '/' + p;
            }
            return @json(rtrim(asset('images/'), '/')) + '/' + p;
        }
        window.kpUrlVistaDesdeRutaAlmacen = kpUrlVistaDesdeRutaAlmacen;

        function kpActualizarPreviewInputInterno(inputId, imgId) {
            const inp = document.getElementById(inputId);
            const img = document.getElementById(imgId);
            if (!inp || !img) return;
            const u = kpUrlVistaDesdeRutaAlmacen(inp.value);
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
        window.kpActualizarPreviewInputInterno = kpActualizarPreviewInputInterno;

        let __kpInternaSublineaSelOrden = [];
        let __kpInternaSublineaRowInc = 0;
        function kpPairKeyInternaSublinea(p) {
            return (p.rutaGrande || '') + '\x01' + (p.rutaPequena || '');
        }
        function kpStableB64InternaSub(k) {
            try { return btoa(unescape(encodeURIComponent(k))); } catch (e) { return String(k.length); }
        }
        function kpLimpiarInternaSublineaUI() {
            __kpInternaSublineaSelOrden = [];
            const g = document.getElementById('galeria-imgs-interna-sublinea');
            if (g) g.innerHTML = '';
            const f = document.getElementById('filas-interna-sublinea');
            if (f) f.innerHTML = '';
            const vac = document.getElementById('galeria-imgs-interna-sublinea-vacio');
            if (vac) vac.classList.add('hidden');
        }
        function kpBindFilasInternaSublineaDelegation() {
            const cont = document.getElementById('filas-interna-sublinea');
            if (!cont || cont.dataset.kpDelegSubInt) return;
            cont.dataset.kpDelegSubInt = '1';
            const upd = function(inp) {
                const row = inp.closest('.kp-fila-interna-sublinea');
                if (!row) return;
                const g = row.querySelector('.kp-interna-sub-g');
                const p = row.querySelector('.kp-interna-sub-p');
                const pg = row.querySelector('.kp-interna-sub-pg');
                const pp = row.querySelector('.kp-interna-sub-pp');
                if (g && pg && g.id && pg.id) kpActualizarPreviewInputInterno(g.id, pg.id);
                if (p && pp && p.id && pp.id) kpActualizarPreviewInputInterno(p.id, pp.id);
            };
            cont.addEventListener('input', function(e) {
                const t = e.target;
                if (!t.classList.contains('kp-interna-sub-g') && !t.classList.contains('kp-interna-sub-p')) return;
                upd(t);
            });
            cont.addEventListener('paste', function(e) {
                const t = e.target;
                if (!t.classList.contains('kp-interna-sub-g') && !t.classList.contains('kp-interna-sub-p')) return;
                setTimeout(function() { upd(t); }, 0);
            });
        }
        function kpParesParaGaleriaInternaSublinea() {
            if (typeof window.kpRecolectarParesImagenesDisponiblesProducto === 'function') {
                return window.kpRecolectarParesImagenesDisponiblesProducto();
            }
            return [];
        }
        function kpLeerImagenesSublineaJson(principalId, sublineaId) {
            const inputHidden = document.getElementById('categoria_especificaciones_internas_elegidas_input');
            if (!inputHidden || !inputHidden.value) return [];
            try {
                const especificaciones = JSON.parse(inputHidden.value);
                const arr = especificaciones[principalId];
                if (!Array.isArray(arr)) return [];
                const sublineaData = arr.find(function(item) {
                    if (typeof item === 'string' || typeof item === 'number') {
                        return String(item) === String(sublineaId);
                    }
                    return item && item.id && String(item.id) === String(sublineaId);
                });
                if (sublineaData && sublineaData.img && Array.isArray(sublineaData.img)) {
                    return sublineaData.img.slice();
                }
            } catch (e) {}
            return [];
        }
        function kpNormalizarParDesdeRutaGrandeSublinea(rutaGrande) {
            const g = (rutaGrande || '').trim();
            const p = kpInferirRutaThumbnailSublinea(g);
            return { rutaGrande: g, rutaPequena: p, thumbVisual: p || g };
        }
        function kpIndiceSeleccionInternaSublinea(pair) {
            const key = kpPairKeyInternaSublinea(pair);
            let ix = __kpInternaSublineaSelOrden.findIndex(function(x) {
                return kpPairKeyInternaSublinea(x) === key;
            });
            if (ix !== -1) return ix;
            if (pair.rutaGrande) {
                ix = __kpInternaSublineaSelOrden.findIndex(function(x) {
                    return x.rutaGrande && String(x.rutaGrande) === String(pair.rutaGrande);
                });
            }
            return ix;
        }
        function kpRutasDesdeSeleccionInternaSublinea() {
            const rutas = [];
            __kpInternaSublineaSelOrden.forEach(function(pair) {
                const r = (pair.rutaGrande || '').trim();
                if (r && !kpEsRutaPendiente(r) && !rutas.includes(r)) rutas.push(r);
            });
            return rutas;
        }
        function kpRefrescarInternaSublineaDesdeGuardado() {
            const pid = sublineaImagenesActual.principalId;
            const sid = sublineaImagenesActual.sublineaId;
            if (!pid || !sid) {
                kpRenderGaleriaInternaSublinea();
                return;
            }
            sublineaImagenesActual.imagenes = kpLeerImagenesSublineaJson(pid, sid);
            kpCargarInternaSublineaDesdeContexto();
        }
        function kpCargarInternaSublineaDesdeContexto() {
            kpLimpiarInternaSublineaUI();
            const paths = sublineaImagenesActual.imagenes || [];
            paths.forEach(function(ruta) {
                if (!ruta || kpEsRutaPendiente(ruta)) return;
                const pair = kpNormalizarParDesdeRutaGrandeSublinea(ruta);
                __kpInternaSublineaSelOrden.push(pair);
                kpAddFilaInternaSublinea(pair);
            });
            kpRenderGaleriaInternaSublinea();
        }
        function kpAsegurarSublineaMarcadaEnEspecificaciones(principalId, sublineaId) {
            const cb = document.querySelector(
                `.especificacion-checkbox[data-principal-id="${principalId}"][data-sublinea-id="${sublineaId}"]`
            );
            if (cb && !cb.checked) {
                cb.checked = true;
                cb.dispatchEvent(new Event('change', { bubbles: true }));
            }
        }
        function kpActualizarContadorImagenesSublineaUI(principalId, sublineaId) {
            const num = (sublineaImagenesActual.imagenes || []).length;
            const btnVer = document.querySelector(
                `.btn-ver-imagenes-sublinea[data-principal-id="${principalId}"][data-sublinea-id="${sublineaId}"]`
            );
            const btnAdd = document.querySelector(
                `.btn-añadir-imagen-sublinea[data-principal-id="${principalId}"][data-sublinea-id="${sublineaId}"]`
            );
            if (btnVer) {
                if (num > 0) {
                    btnVer.textContent = num + ' img';
                    btnVer.title = 'Ver ' + num + ' imagen' + (num > 1 ? 'es' : '');
                    btnVer.classList.remove('hidden');
                } else {
                    btnVer.textContent = '0 img';
                    btnVer.title = 'Ver imágenes';
                }
            }
            if (btnAdd && num > 0) {
                let btnVerExistente = btnVer;
                if (!btnVerExistente || btnVerExistente.classList.contains('hidden')) {
                    const fila = btnAdd.closest('.especificacion-opcion-fila');
                    if (fila && !btnVerExistente) {
                        const nuevoBtn = document.createElement('button');
                        nuevoBtn.type = 'button';
                        nuevoBtn.className =
                            'btn-ver-imagenes-sublinea text-xs px-2 py-1 bg-blue-600 hover:bg-blue-700 text-white rounded transition-colors';
                        nuevoBtn.dataset.principalId = principalId;
                        nuevoBtn.dataset.sublineaId = sublineaId;
                        nuevoBtn.textContent = num + ' img';
                        nuevoBtn.title = 'Ver ' + num + ' imagen' + (num > 1 ? 'es' : '');
                        nuevoBtn.addEventListener('click', function() {
                            abrirModalImagenesSublinea(principalId, sublineaId);
                        });
                        const wrapImg = btnAdd.parentElement;
                        if (wrapImg) wrapImg.insertBefore(nuevoBtn, btnAdd);
                    }
                }
            }
            actualizarChipsSeleccionados();
        }
        function kpQuitarFilaInternaSublineaPorPair(pair) {
            const cont = document.getElementById('filas-interna-sublinea');
            if (!cont) return;
            const b64 = kpStableB64InternaSub(kpPairKeyInternaSublinea(pair));
            let hit = cont.querySelector('.kp-fila-interna-sublinea[data-pair-key="' + b64 + '"]');
            if (!hit && pair.rutaGrande) {
                cont.querySelectorAll('.kp-fila-interna-sublinea').forEach(function(row) {
                    if (hit) return;
                    const g = row.querySelector('.kp-interna-sub-g');
                    if (g && String(g.value).trim() === String(pair.rutaGrande).trim()) hit = row;
                });
            }
            if (hit) hit.remove();
        }
        function kpDescartarInternaSublineaSeleccion(pair, ev) {
            if (ev) {
                ev.preventDefault();
                ev.stopPropagation();
            }
            const ix = kpIndiceSeleccionInternaSublinea(pair);
            if (ix === -1) return;
            __kpInternaSublineaSelOrden.splice(ix, 1);
            kpQuitarFilaInternaSublineaPorPair(pair);
            kpRenderGaleriaInternaSublinea();
        }
        function kpCrearCeldaGaleriaInternaMarcada(src, tituloDescartar, onDescartar) {
            const wrap = document.createElement('div');
            wrap.className =
                'relative w-full aspect-square max-w-full border border-gray-300 dark:border-gray-500 rounded-sm overflow-hidden bg-gray-200/80 dark:bg-gray-700/80';
            const img = document.createElement('img');
            img.src = src;
            img.className = 'w-full h-full object-cover block grayscale opacity-70';
            img.alt = '';
            wrap.appendChild(img);
            const btnDescartar = document.createElement('button');
            btnDescartar.type = 'button';
            btnDescartar.className =
                'absolute top-0.5 right-0.5 z-10 w-5 h-5 flex items-center justify-center rounded bg-red-600 hover:bg-red-700 text-white text-sm font-bold leading-none shadow-md';
            btnDescartar.title = tituloDescartar;
            btnDescartar.setAttribute('aria-label', tituloDescartar);
            btnDescartar.textContent = '×';
            btnDescartar.addEventListener('click', onDescartar);
            wrap.appendChild(btnDescartar);
            return wrap;
        }
        function kpCrearCeldaGaleriaInternaLibre(src, onSeleccionar) {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className =
                'relative w-full aspect-square max-w-full border border-gray-200 dark:border-gray-600 p-0 overflow-hidden rounded-sm transition hover:border-blue-400 dark:hover:border-blue-500';
            const img = document.createElement('img');
            img.src = src;
            img.className = 'w-full h-full object-cover block pointer-events-none';
            img.alt = '';
            btn.appendChild(img);
            btn.addEventListener('click', onSeleccionar);
            return btn;
        }
        window.kpCrearCeldaGaleriaInternaMarcada = kpCrearCeldaGaleriaInternaMarcada;
        window.kpCrearCeldaGaleriaInternaLibre = kpCrearCeldaGaleriaInternaLibre;
        function kpRenderGaleriaInternaSublinea() {
            const wrap = document.getElementById('galeria-imgs-interna-sublinea');
            const vac = document.getElementById('galeria-imgs-interna-sublinea-vacio');
            if (!wrap) return;
            const pairs = kpParesParaGaleriaInternaSublinea();
            wrap.innerHTML = '';
            if (!pairs.length) {
                if (vac) vac.classList.remove('hidden');
                return;
            }
            if (vac) vac.classList.add('hidden');
            pairs.forEach(function(pair) {
                const seleccionada = kpIndiceSeleccionInternaSublinea(pair) !== -1;
                const src = kpUrlVistaDesdeRutaAlmacen(pair.thumbVisual || pair.rutaPequena || pair.rutaGrande);
                const cell = document.createElement('div');
                cell.className = 'min-w-0 w-full';
                if (seleccionada) {
                    cell.appendChild(
                        kpCrearCeldaGaleriaInternaMarcada(src, 'Quitar de esta opción', function(e) {
                            kpDescartarInternaSublineaSeleccion(pair, e);
                        })
                    );
                } else {
                    cell.appendChild(
                        kpCrearCeldaGaleriaInternaLibre(src, function() {
                            kpToggleInternaSublineaSeleccion(pair);
                        })
                    );
                }
                wrap.appendChild(cell);
            });
        }
        function kpAddFilaInternaSublinea(pairOrNull) {
            kpBindFilasInternaSublineaDelegation();
            const cont = document.getElementById('filas-interna-sublinea');
            if (!cont) return;
            const idn = 'kpsubint' + (++__kpInternaSublineaRowInc);
            let gVal = '';
            let pVal = '';
            let b64 = '';
            if (pairOrNull) {
                gVal = (pairOrNull.rutaGrande || '').trim();
                pVal = (pairOrNull.rutaPequena || '').trim();
                if (gVal && !pVal) pVal = kpInferirRutaThumbnailSublinea(gVal);
                if (!gVal && pVal) gVal = '';
                b64 = kpStableB64InternaSub(kpPairKeyInternaSublinea(pairOrNull));
            }
            const row = document.createElement('div');
            row.className = 'kp-fila-interna-sublinea border border-gray-200 dark:border-gray-600 rounded-lg p-3 space-y-2';
            if (b64) row.dataset.pairKey = b64;
            row.innerHTML =
                '<div class="flex flex-wrap items-start gap-3">' +
                '<div class="flex-1 min-w-[200px]"><label class="block mb-1 text-xs text-gray-600 dark:text-gray-400">Ruta grande (se guarda)</label>' +
                '<input type="text" id="' + idn + '-g" autocomplete="off" class="kp-interna-sub-g w-full px-3 py-2 rounded bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white border text-sm"></div>' +
                '<div class="shrink-0"><span class="block mb-1 text-[10px] text-gray-500 dark:text-gray-400">Vista previa</span>' +
                '<img id="' + idn + '-pg" alt="" class="kp-interna-sub-pg hidden w-9 h-9 object-cover rounded border border-gray-300 dark:border-gray-600"></div></div>' +
                '<div class="flex flex-wrap items-start gap-3">' +
                '<div class="flex-1 min-w-[200px]"><label class="block mb-1 text-xs text-gray-600 dark:text-gray-400">Ruta pequeña (referencia)</label>' +
                '<input type="text" id="' + idn + '-p" autocomplete="off" class="kp-interna-sub-p w-full px-3 py-2 rounded bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white border text-sm"></div>' +
                '<div class="shrink-0"><span class="block mb-1 text-[10px] text-gray-500 dark:text-gray-400">Vista previa</span>' +
                '<img id="' + idn + '-pp" alt="" class="kp-interna-sub-pp hidden w-9 h-9 object-cover rounded border border-gray-300 dark:border-gray-600"></div></div>';
            cont.appendChild(row);
            row.querySelector('.kp-interna-sub-g').value = gVal;
            row.querySelector('.kp-interna-sub-p').value = pVal;
            kpActualizarPreviewInputInterno(idn + '-g', idn + '-pg');
            kpActualizarPreviewInputInterno(idn + '-p', idn + '-pp');
        }
        function kpToggleInternaSublineaSeleccion(pair) {
            const ix = kpIndiceSeleccionInternaSublinea(pair);
            if (ix === -1) {
                __kpInternaSublineaSelOrden.push({
                    rutaGrande: pair.rutaGrande,
                    rutaPequena: pair.rutaPequena,
                    thumbVisual: pair.thumbVisual,
                });
                kpAddFilaInternaSublinea(pair);
            } else {
                __kpInternaSublineaSelOrden.splice(ix, 1);
                kpQuitarFilaInternaSublineaPorPair(pair);
            }
            kpRenderGaleriaInternaSublinea();
            if (typeof window.kpInternaGlobalRefrescarSeleccion === 'function') {
                window.kpInternaGlobalRefrescarSeleccion('sublinea');
            }
        }
        window.kpToggleInternaSublineaSeleccion = kpToggleInternaSublineaSeleccion;
        window.kpIndiceSeleccionInternaSublinea = kpIndiceSeleccionInternaSublinea;
        window.kpRefrescarInternaSublineaDesdeGuardado = kpRefrescarInternaSublineaDesdeGuardado;
        window.kpObtenerParesSeleccionInternaSublinea = function() {
            return __kpInternaSublineaSelOrden.slice();
        };

        function kpCancelarSubidaSublinea(uploadId) {
            const x = uploadsPendientesSublinea.get(uploadId);
            if (!x) return;
            if (x.xhrG) try { x.xhrG.abort(); } catch (e) {}
            if (x.xhrP) try { x.xhrP.abort(); } catch (e) {}
            uploadsPendientesSublinea.delete(uploadId);
        }

        function kpEliminarRutaPendienteDeEspecificaciones(pendingPath) {
            const inputHidden = document.getElementById('categoria_especificaciones_internas_elegidas_input');
            if (!inputHidden || !inputHidden.value) return;
            let especificaciones = {};
            try { especificaciones = JSON.parse(inputHidden.value); } catch (e) { return; }
            let changed = false;
            Object.keys(especificaciones).forEach(function(pid) {
                const arr = especificaciones[pid];
                if (!Array.isArray(arr)) return;
                arr.forEach(function(item) {
                    if (item && typeof item === 'object' && item.img && Array.isArray(item.img)) {
                        const ix = item.img.indexOf(pendingPath);
                        if (ix !== -1) {
                            item.img.splice(ix, 1);
                            changed = true;
                        }
                    }
                });
            });
            if (changed) inputHidden.value = JSON.stringify(especificaciones, null, 0);
        }

        function kpReemplazarRutaPendienteEnEspecificaciones(pendingPath, rutaFinal) {
            const inputHidden = document.getElementById('categoria_especificaciones_internas_elegidas_input');
            let especificaciones = {};
            if (inputHidden && inputHidden.value) {
                try { especificaciones = JSON.parse(inputHidden.value); } catch (e) {}
            }
            let changed = false;
            Object.keys(especificaciones).forEach(function(pid) {
                const arr = especificaciones[pid];
                if (!Array.isArray(arr)) return;
                arr.forEach(function(item) {
                    if (item && typeof item === 'object' && item.img && Array.isArray(item.img)) {
                        const ix = item.img.indexOf(pendingPath);
                        if (ix !== -1) {
                            item.img[ix] = rutaFinal;
                            changed = true;
                        }
                    }
                });
            });
            if (changed && inputHidden) inputHidden.value = JSON.stringify(especificaciones, null, 0);
            if (sublineaImagenesActual.imagenes && sublineaImagenesActual.imagenes.length) {
                const j = sublineaImagenesActual.imagenes.indexOf(pendingPath);
                if (j !== -1) sublineaImagenesActual.imagenes[j] = rutaFinal;
            }
            const modalImg = document.getElementById('modal-imagenes-sublinea');
            if (modalImg && !modalImg.classList.contains('hidden')) renderizarMiniaturasSublinea();
            if (typeof actualizarJSONProducto === 'function') actualizarJSONProducto();
        }

        function prepararModalAnadirSublineaParaOtra(tab) {
            document.getElementById('modal-añadir-imagen-sublinea').classList.remove('hidden');
            limpiarModalAñadirSublinea({ mantenerCarpetas: true });
            cambiarTabModalSublinea(tab || 'url');
        }
        
        // Configurar botones de imágenes de sublíneas
        function configurarBotonesImagenesSublineas(contenedor) {
            // Event listeners para botón "Ver imágenes"
            contenedor.querySelectorAll('.btn-ver-imagenes-sublinea').forEach(btn => {
                btn.addEventListener('click', function() {
                    const principalId = this.dataset.principalId;
                    const sublineaId = this.dataset.sublineaId;
                    abrirModalImagenesSublinea(principalId, sublineaId);
                });
            });
            
            // Event listeners para botón "Añadir imágenes"
            contenedor.querySelectorAll('.btn-añadir-imagen-sublinea').forEach(btn => {
                btn.addEventListener('click', function() {
                    const principalId = this.dataset.principalId;
                    const sublineaId = this.dataset.sublineaId;
                    abrirModalAñadirImagenSublinea(principalId, sublineaId);
                });
            });
            
            // Resaltar botones de palabras clave que coinciden con sublíneas marcadas
            resaltarBotonesPalabrasClaveMarcadas();
        }
        
        // Abrir modal para ver/gestionar imágenes de sublínea
        function abrirModalImagenesSublinea(principalId, sublineaId) {
            const inputHidden = document.getElementById('categoria_especificaciones_internas_elegidas_input');
            let imagenes = [];
            
            if (inputHidden && inputHidden.value) {
                try {
                    const especificaciones = JSON.parse(inputHidden.value);
                    if (especificaciones[principalId]) {
                        const sublineaData = especificaciones[principalId].find(item => {
                            if (typeof item === 'string' || typeof item === 'number') {
                                return String(item) === String(sublineaId);
                            } else if (item && item.id) {
                                return String(item.id) === String(sublineaId);
                            }
                            return false;
                        });
                        if (sublineaData && sublineaData.img && Array.isArray(sublineaData.img)) {
                            imagenes = sublineaData.img;
                        }
                    }
                } catch (e) {
                    console.error('Error al leer imágenes:', e);
                }
            }
            
            sublineaImagenesActual = { principalId, sublineaId, imagenes };
            renderizarMiniaturasSublinea();
            document.getElementById('modal-imagenes-sublinea').classList.remove('hidden');
        }
        
        // Cerrar modal de imágenes de sublínea
        window.cerrarModalImagenesSublinea = function() {
            document.getElementById('modal-imagenes-sublinea').classList.add('hidden');
            sublineaImagenesActual = { principalId: null, sublineaId: null, imagenes: [] };
            actualizarPanelRutasVistaSublinea(null);
        };

        /** Deduce la ruta de la miniatura (misma convención que al subir: -thumbnail antes de la extensión). */
        function kpInferirRutaThumbnailSublinea(rutaGrande) {
            if (!rutaGrande || typeof rutaGrande !== 'string') return '';
            if (kpEsRutaPendiente(rutaGrande)) return '';
            const lastDot = rutaGrande.lastIndexOf('.');
            if (lastDot === -1) return rutaGrande + '-thumbnail';
            return rutaGrande.slice(0, lastDot) + '-thumbnail' + rutaGrande.slice(lastDot);
        }

        function actualizarPanelRutasVistaSublinea(imgPathActiva) {
            const panel = document.getElementById('rutas-imagen-sublinea-panel');
            const elG = document.getElementById('ruta-grande-sublinea-vista');
            const elP = document.getElementById('ruta-pequena-sublinea-vista');
            if (!panel || !elG || !elP) return;
            if (!imgPathActiva || !sublineaImagenesActual.imagenes || sublineaImagenesActual.imagenes.length === 0) {
                panel.classList.add('hidden');
                elG.textContent = '';
                elP.textContent = '';
                return;
            }
            if (kpEsRutaPendiente(imgPathActiva)) {
                panel.classList.remove('hidden');
                elG.textContent = '(subiendo…)';
                elP.textContent = '—';
                return;
            }
            panel.classList.remove('hidden');
            elG.textContent = imgPathActiva;
            elP.textContent = kpInferirRutaThumbnailSublinea(imgPathActiva);
        }
        
        // Renderizar miniaturas en el modal de imágenes de sublínea
        function renderizarMiniaturasSublinea() {
            const container = document.getElementById('miniaturas-container-sublinea');
            const imgGrande = document.getElementById('imagen-grande-sublinea');
            container.innerHTML = '';
            
            if (sublineaImagenesActual.imagenes.length === 0) {
                imgGrande.src = '';
                imgGrande.alt = 'No hay imágenes';
                container.innerHTML = '<p class="text-sm text-gray-500 dark:text-gray-400">No hay imágenes</p>';
                actualizarPanelRutasVistaSublinea(null);
                return;
            }
            
            // Mostrar primera imagen como grande
            const primera = sublineaImagenesActual.imagenes[0];
            if (primera && !kpEsRutaPendiente(primera)) {
                imgGrande.src = `{{ asset('images/') }}/${primera}`;
                imgGrande.alt = 'Imagen 1';
            } else if (primera && kpEsRutaPendiente(primera)) {
                imgGrande.src = '';
                imgGrande.alt = 'Subiendo…';
            }
            actualizarPanelRutasVistaSublinea(primera);
            
            // Renderizar miniaturas
            sublineaImagenesActual.imagenes.forEach((imgPath, index) => {
                const div = document.createElement('div');
                div.className = 'relative group miniatura-sublinea cursor-pointer border-2 border-gray-300 dark:border-gray-600 rounded p-1';
                div.dataset.index = index;
                const pendiente = kpEsRutaPendiente(imgPath);
                div.draggable = !pendiente;
                if (index === 0) {
                    div.classList.add('border-blue-500');
                }
                
                if (pendiente) {
                    const uid = imgPath.slice(KP_PENDING_IMG.length);
                    div.innerHTML = `
                        <div class="w-full h-20 flex flex-col items-center justify-center bg-gray-100 dark:bg-gray-700 rounded p-1">
                            <span class="text-[10px] text-gray-600 dark:text-gray-300 text-center leading-tight">Cargando imagen…</span>
                            <div class="w-full mt-1 h-1.5 bg-gray-200 dark:bg-gray-600 rounded overflow-hidden">
                                <div id="kp-prog-sub-${uid}" class="h-full bg-blue-500 transition-[width] duration-150" style="width:0%"></div>
                            </div>
                        </div>
                        <button type="button" class="absolute top-0 right-0 bg-red-600 hover:bg-red-700 text-white rounded-full w-5 h-5 flex items-center justify-center text-xs opacity-0 group-hover:opacity-100 transition-opacity btn-eliminar-imagen-sublinea" data-index="${index}">×</button>
                    `;
                } else {
                    div.innerHTML = `
                        <img src="{{ asset('images/') }}/${imgPath}" alt="Miniatura ${index + 1}" class="w-full h-20 object-cover rounded">
                        <button type="button" class="absolute top-0 right-0 bg-red-600 hover:bg-red-700 text-white rounded-full w-5 h-5 flex items-center justify-center text-xs opacity-0 group-hover:opacity-100 transition-opacity btn-eliminar-imagen-sublinea" data-index="${index}">×</button>
                    `;
                }
                
                div.addEventListener('click', () => {
                    if (pendiente) return;
                    imgGrande.src = `{{ asset('images/') }}/${imgPath}`;
                    imgGrande.alt = `Imagen ${index + 1}`;
                    actualizarPanelRutasVistaSublinea(imgPath);
                    container.querySelectorAll('.miniatura-sublinea').forEach(m => m.classList.remove('border-blue-500'));
                    div.classList.add('border-blue-500');
                });
                
                container.appendChild(div);
            });
            
            // Configurar drag and drop para reordenar
            configurarDragAndDropImagenesSublinea(container);
            
            // Configurar botones de eliminar
            container.querySelectorAll('.btn-eliminar-imagen-sublinea').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const index = parseInt(btn.dataset.index);
                    eliminarImagenSublinea(index);
                });
            });
        }
        
        // Configurar drag and drop para reordenar imágenes de sublínea
        function configurarDragAndDropImagenesSublinea(container) {
            const miniaturas = container.querySelectorAll('.miniatura-sublinea');
            let elementoArrastrado = null;
            
            miniaturas.forEach(miniatura => {
                miniatura.addEventListener('dragstart', (e) => {
                    elementoArrastrado = miniatura;
                    miniatura.style.opacity = '0.5';
                    e.dataTransfer.effectAllowed = 'move';
                });
                
                miniatura.addEventListener('dragend', () => {
                    if (miniatura) miniatura.style.opacity = '1';
                    elementoArrastrado = null;
                });
                
                miniatura.addEventListener('dragover', (e) => {
                    e.preventDefault();
                    if (!elementoArrastrado || elementoArrastrado === miniatura) return;
                    
                    const allMiniaturas = Array.from(container.querySelectorAll('.miniatura-sublinea'));
                    const draggedIndex = allMiniaturas.indexOf(elementoArrastrado);
                    const targetIndex = allMiniaturas.indexOf(miniatura);
                    
                    if (draggedIndex < targetIndex) {
                        container.insertBefore(elementoArrastrado, miniatura.nextSibling);
                    } else {
                        container.insertBefore(elementoArrastrado, miniatura);
                    }
                });
                
                miniatura.addEventListener('drop', (e) => {
                    e.preventDefault();
                    if (elementoArrastrado) {
                        const allMiniaturas = Array.from(container.querySelectorAll('.miniatura-sublinea'));
                        const nuevasImagenes = allMiniaturas.map(m => {
                            const index = parseInt(m.dataset.index);
                            return sublineaImagenesActual.imagenes[index];
                        });
                        sublineaImagenesActual.imagenes = nuevasImagenes;
                        guardarImagenesSublinea();
                        renderizarMiniaturasSublinea();
                    }
                });
            });
        }
        
        // Eliminar imagen de sublínea
        function eliminarImagenSublinea(index) {
            if (!confirm('¿Estás seguro de que quieres eliminar esta imagen?')) {
                return;
            }
            const path = sublineaImagenesActual.imagenes[index];
            if (path && kpEsRutaPendiente(path)) {
                kpCancelarSubidaSublinea(path.slice(KP_PENDING_IMG.length));
            }
            sublineaImagenesActual.imagenes.splice(index, 1);
            guardarImagenesSublinea();
            renderizarMiniaturasSublinea();
        }
        
        // Guardar imágenes de sublínea en el JSON
        function guardarImagenesSublinea(opciones) {
            opciones = opciones || {};
            const inputHidden = document.getElementById('categoria_especificaciones_internas_elegidas_input');
            if (!inputHidden) return;

            const principalId = sublineaImagenesActual.principalId;
            const sublineaId = sublineaImagenesActual.sublineaId;
            let especificaciones = {};

            if (inputHidden.value) {
                try {
                    especificaciones = JSON.parse(inputHidden.value);
                } catch (e) {
                    console.error('Error al parsear especificaciones:', e);
                }
            }

            if (!especificaciones[principalId]) {
                especificaciones[principalId] = [];
            }

            const imagenes = Array.isArray(sublineaImagenesActual.imagenes)
                ? sublineaImagenesActual.imagenes.slice()
                : [];

            let index = especificaciones[principalId].findIndex(function(item) {
                if (typeof item === 'string' || typeof item === 'number') {
                    return String(item) === String(sublineaId);
                }
                return item && item.id && String(item.id) === String(sublineaId);
            });

            if (index === -1) {
                const nuevo = { id: sublineaId };
                if (imagenes.length > 0) nuevo.img = imagenes;
                especificaciones[principalId].push(nuevo);
            } else {
                const item = especificaciones[principalId][index];
                if (typeof item === 'string' || typeof item === 'number') {
                    const nuevo = { id: sublineaId };
                    if (imagenes.length > 0) nuevo.img = imagenes;
                    especificaciones[principalId][index] = nuevo;
                } else if (item && typeof item === 'object') {
                    if (!item.id) item.id = sublineaId;
                    if (imagenes.length > 0) {
                        item.img = imagenes;
                    } else {
                        delete item.img;
                    }
                }
            }

            inputHidden.value = JSON.stringify(especificaciones, null, 0);
            kpAsegurarSublineaMarcadaEnEspecificaciones(principalId, sublineaId);
            actualizarEspecificacionesElegidas();
            kpActualizarContadorImagenesSublineaUI(principalId, sublineaId);

            if (!opciones.omitirRecargaEspecificaciones) {
                const categoriaId = document.getElementById('categoria_especificaciones_id')?.value;
                if (categoriaId) {
                    obtenerEspecificacionesInternas(categoriaId);
                }
            }
        }

        // Abrir modal para añadir imagen a sublínea
        function abrirModalAñadirImagenSublinea(principalId, sublineaId) {
            sublineaImagenesActual.principalId = principalId;
            sublineaImagenesActual.sublineaId = sublineaId;
            sublineaImagenesActual.imagenes = kpLeerImagenesSublineaJson(principalId, sublineaId);
            document.getElementById('modal-añadir-imagen-sublinea').classList.remove('hidden');
            cargarCarpetasModalSublinea();
            cambiarTabModalSublinea('url');
        }
        
        // Cerrar modal de añadir imagen a sublínea
        window.cerrarModalAñadirImagenSublinea = function() {
            document.getElementById('modal-añadir-imagen-sublinea').classList.add('hidden');
            limpiarModalAñadirSublinea();
        };
        
        // Limpiar modal de añadir imagen a sublínea
        function limpiarModalAñadirSublinea(opciones) {
            opciones = opciones || {};
            const mantener = !!opciones.mantenerCarpetas;
            if (!mantener) {
                document.getElementById('carpeta-subir-sublinea').value = '';
                document.getElementById('carpeta-url-sublinea').value = '';
                document.getElementById('carpeta-amazon-sublinea').value = '';
            }
            document.getElementById('file-subir-sublinea').value = '';
            document.getElementById('url-imagen-sublinea').value = '';
            document.getElementById('url-amazon-sublinea').value = '';
            document.getElementById('nombre-archivo-sublinea').textContent = '';
            document.getElementById('error-url-sublinea').classList.add('hidden');
            document.getElementById('error-amazon-sublinea').classList.add('hidden');
            document.getElementById('loading-amazon-sublinea').classList.add('hidden');
            document.getElementById('imagenes-amazon-sublinea').classList.add('hidden');
            document.getElementById('grid-imagenes-amazon-sublinea').innerHTML = '';
            document.getElementById('area-recorte-sublinea').classList.add('hidden');
            document.getElementById('area-recorte-amazon-sublinea').classList.add('hidden');
            imagenesAmazonSeleccionadasSublinea = [];
            resetEstadoRecorteAmazonSublinea();
            if (cropperSublinea) {
                cropperSublinea.destroy();
                cropperSublinea = null;
            }
            kpLimpiarInternaSublineaUI();
        }
        
        // Cambiar pestañas del modal de sublínea
        function cambiarTabModalSublinea(tab) {
            const tabSubir = document.getElementById('tab-subir-sublinea');
            const tabUrl = document.getElementById('tab-url-sublinea');
            const tabAmazon = document.getElementById('tab-amazon-sublinea');
            const tabInterna = document.getElementById('tab-interna-sublinea');
            const tabInternaGlobal = document.getElementById('tab-interna-global-sublinea');
            const contentSubir = document.getElementById('content-subir-sublinea');
            const contentUrl = document.getElementById('content-url-sublinea');
            const contentAmazon = document.getElementById('content-amazon-sublinea');
            const contentInterna = document.getElementById('content-interna-sublinea');
            const contentInternaGlobal = document.getElementById('content-interna-global-sublinea');
            const allTabs = [tabUrl, tabSubir, tabAmazon, tabInterna, tabInternaGlobal];

            [contentUrl, contentSubir, contentAmazon, contentInterna, contentInternaGlobal].forEach(c => {
                if (c) c.classList.add('hidden');
            });
            
            if (tab === 'url') {
                window.kpModalImgTabSetActive(allTabs, tabUrl);
                contentUrl.classList.remove('hidden');
            } else if (tab === 'subir') {
                window.kpModalImgTabSetActive(allTabs, tabSubir);
                contentSubir.classList.remove('hidden');
            } else if (tab === 'amazon') {
                window.kpModalImgTabSetActive(allTabs, tabAmazon);
                contentAmazon.classList.remove('hidden');
                cargarCarpetasModalSublinea(); // Cargar carpetas cuando se abre la pestaña Amazon
            } else if (tab === 'interna' && tabInterna && contentInterna) {
                window.kpModalImgTabSetActive(allTabs, tabInterna);
                contentInterna.classList.remove('hidden');
                kpBindFilasInternaSublineaDelegation();
                kpRefrescarInternaSublineaDesdeGuardado();
            } else if (tab === 'interna-global' && tabInternaGlobal && contentInternaGlobal) {
                window.kpModalImgTabSetActive(allTabs, tabInternaGlobal);
                contentInternaGlobal.classList.remove('hidden');
                if (typeof window.kpRefrescarInternaSublineaDesdeGuardado === 'function') {
                    window.kpRefrescarInternaSublineaDesdeGuardado();
                }
                if (typeof window.kpInternaGlobalAlActivar === 'function') {
                    window.kpInternaGlobalAlActivar('sublinea');
                }
            }
        }
        
        // Cargar carpetas en modal de sublínea
        function cargarCarpetasModalSublinea() {
            fetch('{{ route("admin.imagenes.carpetas") }}')
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.data.length > 0) {
                        actualizarSelectCarpetasSublinea('carpeta-subir-sublinea', data.data);
                        actualizarSelectCarpetasSublinea('carpeta-url-sublinea', data.data);
                        actualizarSelectCarpetasSublinea('carpeta-amazon-sublinea', data.data);
                    }
                })
                .catch(error => console.error('Error al cargar carpetas:', error));
        }
        
        function actualizarSelectCarpetasSublinea(selectId, carpetas) {
            const select = document.getElementById(selectId);
            if (!select) return;
            
            const primeraOpcion = select.querySelector('option[value=""]') || document.createElement('option');
            primeraOpcion.value = '';
            primeraOpcion.textContent = 'Selecciona una carpeta';
            select.innerHTML = '';
            select.appendChild(primeraOpcion);
            
            let carpetaProductoExiste = false;
            carpetas.forEach(carpeta => {
                const option = document.createElement('option');
                option.value = carpeta;
                option.textContent = carpeta.charAt(0).toUpperCase() + carpeta.slice(1);
                select.appendChild(option);
                // Verificar si existe la carpeta "producto" (case insensitive)
                if (carpeta.toLowerCase() === 'producto') {
                    carpetaProductoExiste = true;
                }
            });
            
            // Si existe la carpeta "producto", seleccionarla por defecto
            if (carpetaProductoExiste) {
                select.value = 'producto';
            }
        }
        
        function kpEscaparHtmlChipEspecificacion(texto) {
            return String(texto || '(Sin texto)')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;');
        }

        function kpHtmlChipEspecificacionSeleccionada(principalId, sublineaId, texto) {
            const textoEsc = kpEscaparHtmlChipEspecificacion(texto);
            const principalEsc = String(principalId).replace(/"/g, '&quot;');
            const sublineaEsc = String(sublineaId).replace(/"/g, '&quot;');
            return `<span class="inline-flex items-center gap-1 px-2 py-1 text-xs bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 rounded" data-sublinea-id="${sublineaEsc}">
                <svg class="w-3 h-3 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
                <span>${textoEsc}</span>
                <button type="button" class="btn-quitar-chip-especificacion shrink-0 rounded p-0.5 text-green-800 dark:text-green-200 hover:bg-green-300/60 dark:hover:bg-green-800 focus:outline-none leading-none font-bold" title="Quitar selección" aria-label="Quitar ${textoEsc}" data-principal-id="${principalEsc}" data-sublinea-id="${sublineaEsc}">×</button>
            </span>`;
        }

        function kpDesmarcarSublineaEspecificacion(principalId, sublineaId) {
            const contenedor = document.getElementById('especificaciones-internas-contenido');
            if (!contenedor || !principalId || !sublineaId) return;

            const checkbox = contenedor.querySelector(
                `.especificacion-checkbox[data-principal-id="${principalId}"][data-sublinea-id="${sublineaId}"]`
            );
            if (!checkbox || !checkbox.checked) return;

            checkbox.checked = false;
            checkbox.dispatchEvent(new Event('change', { bubbles: true }));
        }

        function kpInicializarDelegacionQuitarChipEspecificacion(contenedor) {
            if (!contenedor || contenedor.dataset.chipsQuitarDelegacion === '1') return;
            contenedor.dataset.chipsQuitarDelegacion = '1';
            contenedor.addEventListener('click', (event) => {
                const btn = event.target.closest('.btn-quitar-chip-especificacion');
                if (!btn) return;
                event.preventDefault();
                event.stopPropagation();
                kpDesmarcarSublineaEspecificacion(btn.dataset.principalId, btn.dataset.sublineaId);
            });
        }

        // Función para actualizar los chips de opciones seleccionadas
        function actualizarChipsSeleccionados() {
            const inputHidden = document.getElementById('categoria_especificaciones_internas_elegidas_input');
            let opcionesGuardadas = {};
            if (inputHidden && inputHidden.value) {
                try {
                    opcionesGuardadas = JSON.parse(inputHidden.value);
                } catch (e) {
                    return;
                }
            }
            
            // Obtener todos los contenedores principales
            const contenedoresPrincipales = document.querySelectorAll('#especificaciones-internas-contenido > div > div[data-principal-id]');
            
            contenedoresPrincipales.forEach(contenedorPrincipal => {
                const principalId = contenedorPrincipal.dataset.principalId;
                if (!principalId) return;
                
                const opcionesSeleccionadas = opcionesGuardadas[principalId] || [];
                const checkboxes = contenedorPrincipal.querySelectorAll('.especificacion-checkbox');
                const chipsSeleccionados = [];
                
                checkboxes.forEach(checkbox => {
                    if (checkbox.checked) {
                        const sublineaId = checkbox.dataset.sublineaId;
                        const textoSublinea = checkbox.dataset.sublineaTexto || checkbox.nextElementSibling?.textContent || '';
                        chipsSeleccionados.push({ id: sublineaId, texto: textoSublinea });
                    }
                });
                
                // Buscar el contenedor de chips
                const chipsContainer = contenedorPrincipal.querySelector(`.chips-container-${principalId}`);
                if (chipsContainer) {
                    if (chipsSeleccionados.length > 0) {
                        chipsContainer.innerHTML = chipsSeleccionados
                            .map((sub) =>
                                kpHtmlChipEspecificacionSeleccionada(principalId, sub.id, sub.texto)
                            )
                            .join('');
                    } else {
                        chipsContainer.innerHTML = '';
                    }
                }
            });
        }

        // Listener para recargar especificaciones cuando cambie la unidad de medida
        const unidadDeMedidaSelect = document.getElementById('unidadDeMedida');
        if (unidadDeMedidaSelect) {
            unidadDeMedidaSelect.addEventListener('change', function() {
                const categoriaId = document.getElementById('categoria_especificaciones_id').value;
                if (categoriaId) {
                    // Recargar las especificaciones para mostrar/ocultar checkboxes de columna
                    obtenerEspecificacionesInternas(categoriaId);
                }
            });
        }

        // Función para auto-seleccionar la categoría final cuando se selecciona la categoría del producto
        function autoSeleccionarCategoriaEspecificaciones() {
            const categoriaFinal = document.getElementById('categoria-final');
            const categoriaEspecificacionesId = document.getElementById('categoria_especificaciones_id');
            const categoriaEspecificacionesNombre = document.getElementById('categoria_especificaciones_nombre');
            
            if (!categoriaFinal || !categoriaEspecificacionesId || !categoriaEspecificacionesNombre) {
                return;
            }
            
            if (categoriaFinal.value) {
                // Verificar si ya está seleccionada la misma categoría
                if (categoriaEspecificacionesId.value === categoriaFinal.value) {
                    return; // Ya está seleccionada, no hacer nada
                }
                
                // Si no hay categoría seleccionada, buscar y mostrar la categoría final
                if (!categoriaEspecificacionesId.value) {
                    fetch(`/panel-privado/productos/buscar/categorias?q=`)
                        .then(response => response.json())
                        .then(categorias => {
                            // Buscar la categoría por ID
                            const categoriaSeleccionada = categorias.find(cat => cat.id == categoriaFinal.value);
                                if (categoriaSeleccionada) {
                                    // Auto-seleccionar la categoría
                                    categoriaEspecificacionesId.value = categoriaSeleccionada.id;
                                    categoriaEspecificacionesNombre.value = categoriaSeleccionada.nombre;
                                    
                                    // Cargar las especificaciones internas de la categoría auto-seleccionada
                                    obtenerEspecificacionesInternas(categoriaSeleccionada.id);
                                }
                            })
                            .catch(error => console.error('Error al auto-seleccionar categoría:', error));
                }
            }
        }

        // Observar cambios en la categoría final
        const categoriaFinalEspecificaciones = document.getElementById('categoria-final');
        if (categoriaFinalEspecificaciones) {
            // Escuchar cambios en el valor del campo
            categoriaFinalEspecificaciones.addEventListener('change', function() {
                setTimeout(autoSeleccionarCategoriaEspecificaciones, 300);
            });
            
            // También observar cambios programáticos en el valor
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.type === 'attributes' && mutation.attributeName === 'value') {
                        setTimeout(autoSeleccionarCategoriaEspecificaciones, 300);
                    }
                });
            });
            
            observer.observe(categoriaFinalEspecificaciones, { attributes: true, attributeFilter: ['value'] });
        }
        
        // Event listener para el checkbox "No añadir"
        const checkboxNoAnadir = document.getElementById('no_anadir_especificaciones');
        if (checkboxNoAnadir) {
            checkboxNoAnadir.addEventListener('change', function() {
                if (this.checked) {
                    // Obtener la categoría actual de los campos
                    const categoriaId = document.getElementById('categoria_especificaciones_id').value;
                    const categoriaNombre = document.getElementById('categoria_especificaciones_nombre').value;
                    
                    // Si hay una categoría en los campos, guardarla (actualizar la guardada)
                    if (categoriaId && categoriaNombre) {
                        this.dataset.categoriaIdGuardada = categoriaId;
                        this.dataset.categoriaNombreGuardada = categoriaNombre;
                    } else {
                        // Si no hay categoría en los campos, verificar si hay una guardada
                        // Si hay una guardada, mantenerla (no hacer nada, ya está en el dataset)
                        // Si no hay ninguna, no hacer nada
                        const categoriaIdGuardada = this.dataset.categoriaIdGuardada;
                        if (!categoriaIdGuardada) {
                            // No hay categoría ni en campos ni guardada
                            // No hacer nada, no hay nada que guardar
                        }
                        // Si hay categoría guardada, se mantiene automáticamente en el dataset
                    }
                    
                    // Limpiar todo (pero mantener la categoría guardada en el dataset)
                    document.getElementById('categoria_especificaciones_id').value = '';
                    document.getElementById('categoria_especificaciones_nombre').value = '';
                    document.getElementById('categoria_especificaciones_internas_elegidas_input').value = '';
                    const seleccionContainer = document.getElementById('especificaciones-internas-seleccion');
                    if (seleccionContainer) {
                        seleccionContainer.classList.add('hidden');
                    }
                } else {
                    // Restaurar la categoría guardada si existe (NO eliminar los datos guardados)
                    const categoriaIdGuardada = this.dataset.categoriaIdGuardada;
                    const categoriaNombreGuardada = this.dataset.categoriaNombreGuardada;
                    
                    if (categoriaIdGuardada && categoriaNombreGuardada) {
                        // Restaurar la categoría usando setTimeout para evitar interferencias
                        setTimeout(() => {
                            document.getElementById('categoria_especificaciones_id').value = categoriaIdGuardada;
                            document.getElementById('categoria_especificaciones_nombre').value = categoriaNombreGuardada;
                            
                            // NO eliminar los datos guardados para que se puedan restaurar si se vuelve a marcar
                            // Recargar las especificaciones
                            obtenerEspecificacionesInternas(categoriaIdGuardada);
                        }, 50);
                    } else {
                        // Si no hay categoría guardada, intentar recargar con la que esté en el campo
                        const categoriaId = document.getElementById('categoria_especificaciones_id').value;
                        if (categoriaId) {
                            obtenerEspecificacionesInternas(categoriaId);
                        }
                    }
                }
            });
            
            // Interceptar el envío del formulario para asegurar que se envíe null si el checkbox está marcado
            const form = document.querySelector('form');
            if (form) {
                form.addEventListener('submit', function(e) {
                    // Actualizar especificaciones antes de limpiar (si no está marcado "No añadir")
                    if (typeof actualizarEspecificacionesElegidas === 'function' && !checkboxNoAnadir.checked) {
                        actualizarEspecificacionesElegidas();
                    }
                    
                    if (checkboxNoAnadir.checked) {
                        // Asegurar que se envíe null
                        document.getElementById('categoria_especificaciones_id').value = '';
                        document.getElementById('categoria_especificaciones_internas_elegidas_input').value = '';
                    }
                });
            }
        }

        // Event listeners para pestañas del modal de sublínea
        document.getElementById('tab-subir-sublinea').addEventListener('click', () => cambiarTabModalSublinea('subir'));
        document.getElementById('tab-url-sublinea').addEventListener('click', () => cambiarTabModalSublinea('url'));
        const tabInternaSubEl = document.getElementById('tab-interna-sublinea');
        if (tabInternaSubEl) tabInternaSubEl.addEventListener('click', () => cambiarTabModalSublinea('interna'));
        const tabInternaGlobalSubEl = document.getElementById('tab-interna-global-sublinea');
        if (tabInternaGlobalSubEl) tabInternaGlobalSubEl.addEventListener('click', () => cambiarTabModalSublinea('interna-global'));
        const btnInternaSubFilaVacia = document.getElementById('btn-interna-sublinea-anadir-fila-vacia');
        if (btnInternaSubFilaVacia) {
            btnInternaSubFilaVacia.addEventListener('click', function() {
                kpAddFilaInternaSublinea(null);
            });
        }
        
        // ========== FUNCIONALIDAD AMAZON PARA MODAL SUBLÍNEA ==========
        let imagenesAmazonSeleccionadasSublinea = [];
        let cropperAmazonSublinea = null;
        let colaRecorteAmazonSublinea = [];
        let totalRecorteAmazonSublinea = 0;
        let carpetaActualAmazonSublinea = '';
        let modoRecorteAmazonSublinea = false;

        const kpOpcionesCropperAmazon = {
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
            toggleDragModeOnDblclick: false,
        };

        function resetEstadoRecorteAmazonSublinea() {
            colaRecorteAmazonSublinea = [];
            totalRecorteAmazonSublinea = 0;
            carpetaActualAmazonSublinea = '';
            modoRecorteAmazonSublinea = false;
            const area = document.getElementById('area-recorte-amazon-sublinea');
            const busqueda = document.getElementById('amazon-busqueda-sublinea');
            if (area) area.classList.add('hidden');
            if (busqueda) busqueda.classList.remove('hidden');
            if (cropperAmazonSublinea) {
                cropperAmazonSublinea.destroy();
                cropperAmazonSublinea = null;
            }
        }

        function mostrarRecorteAmazonSublinea(urlImagen) {
            const areaRecorte = document.getElementById('area-recorte-amazon-sublinea');
            const img = document.getElementById('imagen-recortar-amazon-sublinea');
            areaRecorte.classList.remove('hidden');
            if (cropperAmazonSublinea) cropperAmazonSublinea.destroy();
            const urlProxy = urlImagen.startsWith('http')
                ? `{{ route('admin.imagenes.proxy') }}?url=${encodeURIComponent(urlImagen)}`
                : urlImagen;
            img.crossOrigin = 'anonymous';
            img.src = urlProxy;
            img.onload = function() {
                if (cropperAmazonSublinea) cropperAmazonSublinea.destroy();
                cropperAmazonSublinea = new Cropper(img, kpOpcionesCropperAmazon);
            };
            img.onerror = function() {
                document.getElementById('error-amazon-sublinea').textContent = 'Error al cargar la imagen desde Amazon.';
                document.getElementById('error-amazon-sublinea').classList.remove('hidden');
                colaRecorteAmazonSublinea.shift();
                mostrarSiguienteRecorteAmazonSublinea();
            };
        }

        function mostrarSiguienteRecorteAmazonSublinea() {
            if (!colaRecorteAmazonSublinea.length) {
                resetEstadoRecorteAmazonSublinea();
                prepararModalAnadirSublineaParaOtra('amazon');
                cargarCarpetasModalSublinea();
                return;
            }
            const actual = totalRecorteAmazonSublinea - colaRecorteAmazonSublinea.length + 1;
            const progreso = document.getElementById('progreso-recorte-amazon-sublinea');
            if (progreso) progreso.textContent = `Recortando imagen ${actual} de ${totalRecorteAmazonSublinea}`;
            mostrarRecorteAmazonSublinea(colaRecorteAmazonSublinea[0].url);
        }

        function iniciarColaRecorteAmazonSublinea(carpeta) {
            colaRecorteAmazonSublinea = imagenesAmazonSeleccionadasSublinea.slice();
            totalRecorteAmazonSublinea = colaRecorteAmazonSublinea.length;
            carpetaActualAmazonSublinea = carpeta;
            modoRecorteAmazonSublinea = true;
            document.getElementById('imagenes-amazon-sublinea').classList.add('hidden');
            const busqueda = document.getElementById('amazon-busqueda-sublinea');
            if (busqueda) busqueda.classList.add('hidden');
            document.getElementById('error-amazon-sublinea').classList.add('hidden');
            mostrarSiguienteRecorteAmazonSublinea();
        }

        async function procesarRecorteAmazonActualSublinea() {
            if (!cropperAmazonSublinea || !carpetaActualAmazonSublinea) {
                alert('Error al recortar la imagen.');
                return;
            }
            const canvasOriginal = cropperAmazonSublinea.getCroppedCanvas({
                imageSmoothingEnabled: true,
                imageSmoothingQuality: 'high'
            });
            if (!canvasOriginal) {
                alert('Error al recortar la imagen');
                return;
            }
            colaRecorteAmazonSublinea.shift();
            const carpetaUp = carpetaActualAmazonSublinea;
            const slugInput = document.querySelector('input[name="slug"]');
            const nombreBase = slugInput ? slugInput.value.trim() : 'imagen';
            const uploadId = kpNuevoIdSubida();
            const pendingPath = KP_PENDING_IMG + uploadId;
            añadirImagenASublinea(pendingPath);
            const verModalAbiertoSub = document.getElementById('modal-imagenes-sublinea') && !document.getElementById('modal-imagenes-sublinea').classList.contains('hidden');
            if (verModalAbiertoSub) renderizarMiniaturasSublinea();

            try {
                const canvasGrande = document.createElement('canvas');
                canvasGrande.width = canvasOriginal.width;
                canvasGrande.height = canvasOriginal.height;
                const ctxGrande = canvasGrande.getContext('2d');
                ctxGrande.fillStyle = '#ffffff';
                ctxGrande.fillRect(0, 0, canvasGrande.width, canvasGrande.height);
                ctxGrande.drawImage(canvasOriginal, 0, 0);
                const canvasPequena = document.createElement('canvas');
                canvasPequena.width = 300;
                canvasPequena.height = 250;
                const ctxPequena = canvasPequena.getContext('2d');
                ctxPequena.fillStyle = '#ffffff';
                ctxPequena.fillRect(0, 0, canvasPequena.width, canvasPequena.height);
                ctxPequena.drawImage(canvasOriginal, 0, 0, 300, 250);
                const blobGrande = await new Promise(function(resolve, reject) {
                    canvasGrande.toBlob(function(b) { b ? resolve(b) : reject(new Error('Error grande')); }, 'image/webp', 0.9);
                });
                const blobPequena = await new Promise(function(resolve, reject) {
                    canvasPequena.toBlob(function(b) { b ? resolve(b) : reject(new Error('Error pequeña')); }, 'image/webp', 0.9);
                });
                const timestamp = Date.now();
                const formDataGrande = new FormData();
                formDataGrande.append('imagen', blobGrande, `${nombreBase}-${timestamp}.webp`);
                formDataGrande.append('carpeta', carpetaUp);
                formDataGrande.append('_token', '{{ csrf_token() }}');
                const formDataPequena = new FormData();
                formDataPequena.append('imagen', blobPequena, `${nombreBase}-${timestamp}-thumbnail.webp`);
                formDataPequena.append('carpeta', carpetaUp);
                formDataPequena.append('_token', '{{ csrf_token() }}');

                (async function() {
                    try {
                        const onProg = function(pct) {
                            const el = document.getElementById('kp-prog-sub-' + uploadId);
                            if (el) el.style.width = pct + '%';
                        };
                        const { dataG, dataP } = await window.__kpSubirParejaConProgreso(
                            '{{ route("admin.imagenes.subir-simple") }}',
                            formDataGrande,
                            formDataPequena,
                            '{{ csrf_token() }}',
                            onProg,
                            uploadsPendientesSublinea,
                            uploadId
                        );
                        if (dataG.success && dataP.success) {
                            kpReemplazarRutaPendienteEnEspecificaciones(pendingPath, dataG.data.ruta_relativa);
                        } else {
                            throw new Error(dataG.message || dataP.message || 'Error al subir');
                        }
                    } catch (error) {
                        console.error('Error al procesar imagen Amazon sublínea:', error);
                        kpEliminarRutaPendienteDeEspecificaciones(pendingPath);
                        const j = sublineaImagenesActual.imagenes.indexOf(pendingPath);
                        if (j !== -1) {
                            sublineaImagenesActual.imagenes.splice(j, 1);
                            guardarImagenesSublinea();
                        }
                        renderizarMiniaturasSublinea();
                        alert('Error al subir una imagen de Amazon: ' + error.message);
                    }
                })();
            } catch (error) {
                console.error('Error al procesar imagen Amazon sublínea:', error);
                kpEliminarRutaPendienteDeEspecificaciones(pendingPath);
                const j = sublineaImagenesActual.imagenes.indexOf(pendingPath);
                if (j !== -1) {
                    sublineaImagenesActual.imagenes.splice(j, 1);
                    guardarImagenesSublinea();
                }
                renderizarMiniaturasSublinea();
                alert('Error al procesar la imagen: ' + error.message);
            }

            mostrarSiguienteRecorteAmazonSublinea();
        }
        
        // Limpiar URL vía servicio LimpiarUrlDeTiendas (compartido con ofertas)
        async function limpiarUrlAmazonViaApi(url) {
            if (!url || !url.trim()) return url || '';
            try {
                const res = await fetch('{{ route("admin.ofertas.limpiar.url") }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                    body: JSON.stringify({ url: url.trim() })
                });
                if (!res.ok) return url.trim();
                const data = await res.json();
                return data.url_limpia ?? url.trim();
            } catch (e) {
                return url.trim();
            }
        }
        
        // Limpiar URL de Amazon automáticamente al pegar o escribir (modal sublínea)
        const urlAmazonInputSublinea = document.getElementById('url-amazon-sublinea');
        if (urlAmazonInputSublinea) {
            urlAmazonInputSublinea.addEventListener('paste', function(e) {
                setTimeout(async () => {
                    const urlPegada = urlAmazonInputSublinea.value.trim();
                    if (urlPegada) {
                        const urlLimpia = await limpiarUrlAmazonViaApi(urlPegada);
                        if (urlLimpia !== urlPegada) urlAmazonInputSublinea.value = urlLimpia;
                    }
                }, 10);
            });
            urlAmazonInputSublinea.addEventListener('input', function(e) {
                const url = e.target.value.trim();
                if (!url || !url.includes('amazon')) return;
                limpiarUrlAmazonViaApi(url).then(urlLimpia => {
                    if (urlLimpia !== url && urlLimpia.length < url.length) e.target.value = urlLimpia;
                });
            });
        }
        
        // Buscar imágenes de Amazon en modal de sublínea
        document.getElementById('btn-buscar-amazon-sublinea').addEventListener('click', async () => {
            const urlInput = document.getElementById('url-amazon-sublinea');
            const errorDiv = document.getElementById('error-amazon-sublinea');
            const loadingDiv = document.getElementById('loading-amazon-sublinea');
            const imagenesDiv = document.getElementById('imagenes-amazon-sublinea');
            const gridDiv = document.getElementById('grid-imagenes-amazon-sublinea');
            
            const url = urlInput.value.trim();
            if (!url) {
                errorDiv.textContent = 'Por favor, introduce una URL de Amazon';
                errorDiv.classList.remove('hidden');
                return;
            }
            
            errorDiv.classList.add('hidden');
            loadingDiv.classList.remove('hidden');
            imagenesDiv.classList.add('hidden');
            gridDiv.innerHTML = '';
            imagenesAmazonSeleccionadasSublinea = [];
            
            try {
                const response = await fetch('{{ route("admin.productos.obtener-imagenes-amazon") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ url: url })
                });
                
                const data = await response.json();
                
                if (!data.success) {
                    throw new Error(data.error || 'Error al obtener imágenes');
                }
                
                if (!data.imagenes || data.imagenes.length === 0) {
                    errorDiv.textContent = 'No se encontraron imágenes para este producto';
                    errorDiv.classList.remove('hidden');
                    loadingDiv.classList.add('hidden');
                    return;
                }
                
                // Mostrar imágenes
                data.imagenes.forEach((imagen, index) => {
                    const div = document.createElement('div');
                    div.className = 'relative border-2 border-gray-300 dark:border-gray-600 rounded-lg overflow-hidden cursor-pointer hover:border-blue-500 transition-colors';
                    div.dataset.index = index;
                    
                    const img = document.createElement('img');
                    img.src = imagen.url;
                    img.className = 'w-full h-32 object-contain transition-transform duration-200';
                    img.alt = `Imagen ${index + 1}`;
                    img.dataset.originalClass = 'w-full h-32 object-contain transition-transform duration-200';
                    
                    // Contenedor para checkbox y etiqueta de tamaño
                    const checkboxContainer = document.createElement('div');
                    checkboxContainer.className = 'absolute top-2 right-2 flex items-center gap-1';
                    
                    const checkbox = document.createElement('input');
                    checkbox.type = 'checkbox';
                    checkbox.className = 'w-5 h-5';
                    checkbox.dataset.index = index;
                    
                    // Etiqueta de tamaño (L, M, S)
                    const sizeLabel = document.createElement('span');
                    const sizeText = imagen.size === 'large' ? 'L' : (imagen.size === 'medium' ? 'M' : 'S');
                    sizeLabel.textContent = sizeText;
                    sizeLabel.className = 'text-xs font-semibold bg-blue-500 text-white px-1.5 py-0.5 rounded';
                    sizeLabel.style.display = 'inline-block';
                    
                    checkboxContainer.appendChild(checkbox);
                    checkboxContainer.appendChild(sizeLabel);
                    
                    div.appendChild(img);
                    div.appendChild(checkboxContainer);
                    
                    // Funcionalidad de agrandar con espacio
                    let espacioPresionado = false;
                    let ratonEncima = false;
                    
                    div.addEventListener('mouseenter', () => {
                        ratonEncima = true;
                    });
                    
                    div.addEventListener('mouseleave', () => {
                        ratonEncima = false;
                        if (espacioPresionado) {
                            img.className = img.dataset.originalClass;
                            espacioPresionado = false;
                        }
                    });
                    
                    // Listener global para la tecla espacio
                    const espacioKeydown = (e) => {
                        if (e.code === 'Space' && ratonEncima && !espacioPresionado) {
                            e.preventDefault();
                            espacioPresionado = true;
                            img.className = 'w-full max-h-[80vh] object-contain transition-transform duration-200 z-50';
                            div.style.zIndex = '50';
                            div.style.position = 'relative';
                        }
                    };
                    
                    const espacioKeyup = (e) => {
                        if (e.code === 'Space' && espacioPresionado) {
                            e.preventDefault();
                            espacioPresionado = false;
                            img.className = img.dataset.originalClass;
                            div.style.zIndex = '';
                        }
                    };
                    
                    document.addEventListener('keydown', espacioKeydown);
                    document.addEventListener('keyup', espacioKeyup);
                    
                    div.addEventListener('click', (e) => {
                        if (e.target !== checkbox && e.target !== sizeLabel) {
                            checkbox.checked = !checkbox.checked;
                        }
                        actualizarSeleccionAmazonSublinea(checkbox.checked, index, imagen);
                    });
                    
                    checkbox.addEventListener('change', (e) => {
                        actualizarSeleccionAmazonSublinea(e.target.checked, index, imagen);
                    });
                    
                    gridDiv.appendChild(div);
                });
                
                imagenesDiv.classList.remove('hidden');
                loadingDiv.classList.add('hidden');
                
            } catch (error) {
                console.error('Error:', error);
                errorDiv.textContent = error.message || 'Error al obtener imágenes de Amazon';
                errorDiv.classList.remove('hidden');
                loadingDiv.classList.add('hidden');
            }
        });
        
        function actualizarSeleccionAmazonSublinea(seleccionada, index, imagen) {
            if (seleccionada) {
                if (!imagenesAmazonSeleccionadasSublinea.find(img => img.url === imagen.url)) {
                    imagenesAmazonSeleccionadasSublinea.push(imagen);
                }
            } else {
                imagenesAmazonSeleccionadasSublinea = imagenesAmazonSeleccionadasSublinea.filter(img => img.url !== imagen.url);
            }
        }
        
        // Guardar imágenes seleccionadas de Amazon en sublínea
        document.getElementById('btn-guardar-imagen-sublinea').addEventListener('click', async () => {
            const tabActiva = document.querySelector('.tab-modal-sublinea.kp-modal-img-tab--active');
            if (!tabActiva || tabActiva.id !== 'tab-amazon-sublinea') {
                return; // No es la pestaña de Amazon, dejar que el código existente maneje el guardado
            }

            if (modoRecorteAmazonSublinea) {
                await procesarRecorteAmazonActualSublinea();
                return;
            }
            
            if (imagenesAmazonSeleccionadasSublinea.length === 0) {
                alert('Por favor, selecciona al menos una imagen');
                return;
            }
            
            const carpetaSelect = document.getElementById('carpeta-amazon-sublinea');
            const carpeta = carpetaSelect.value;
            if (!carpeta) {
                alert('Por favor, selecciona una carpeta');
                return;
            }

            iniciarColaRecorteAmazonSublinea(carpeta);
        });
        document.getElementById('tab-amazon-sublinea').addEventListener('click', () => cambiarTabModalSublinea('amazon'));
        
        // Event listeners para subida de imágenes de sublínea (similar al modal principal)
        const fileInputSublinea = document.getElementById('file-subir-sublinea');
        const btnSeleccionarSublinea = document.getElementById('btn-seleccionar-sublinea');
        const dropZoneSublinea = document.getElementById('drop-zone-sublinea');
        
        if (btnSeleccionarSublinea) {
            btnSeleccionarSublinea.addEventListener('click', () => fileInputSublinea.click());
        }
        
        if (dropZoneSublinea) {
            dropZoneSublinea.addEventListener('click', () => fileInputSublinea.click());
            dropZoneSublinea.addEventListener('dragover', (e) => {
                e.preventDefault();
                dropZoneSublinea.classList.add('border-blue-500', 'bg-blue-50', 'dark:bg-blue-900/20');
            });
            dropZoneSublinea.addEventListener('dragleave', () => {
                dropZoneSublinea.classList.remove('border-blue-500', 'bg-blue-50', 'dark:bg-blue-900/20');
            });
            dropZoneSublinea.addEventListener('drop', (e) => {
                e.preventDefault();
                dropZoneSublinea.classList.remove('border-blue-500', 'bg-blue-50', 'dark:bg-blue-900/20');
                if (e.dataTransfer.files.length > 0) {
                    procesarArchivoSublinea(e.dataTransfer.files[0]);
                }
            });
        }
        
        if (fileInputSublinea) {
            fileInputSublinea.addEventListener('change', (e) => {
                if (e.target.files.length > 0) {
                    procesarArchivoSublinea(e.target.files[0]);
                }
            });
        }
        
        // Función para procesar archivo subido para sublínea (subida en segundo plano con barra de progreso)
        function procesarArchivoSublinea(file) {
            const carpeta = document.getElementById('carpeta-subir-sublinea').value;
            if (!carpeta) {
                alert('Por favor selecciona una carpeta primero.');
                return;
            }
            if (!file.type.startsWith('image/')) {
                alert('Por favor selecciona un archivo de imagen válido.');
                return;
            }
            if (file.size > 5 * 1024 * 1024) {
                alert('La imagen es demasiado grande. Máximo 5MB.');
                return;
            }
            const uploadId = kpNuevoIdSubida();
            const pendingPath = KP_PENDING_IMG + uploadId;
            añadirImagenASublinea(pendingPath);
            const verModalAbierto = document.getElementById('modal-imagenes-sublinea') && !document.getElementById('modal-imagenes-sublinea').classList.contains('hidden');
            if (verModalAbierto) renderizarMiniaturasSublinea();
            document.getElementById('nombre-archivo-sublinea').textContent = file.name || '';
            prepararModalAnadirSublineaParaOtra('subir');
            cargarCarpetasModalSublinea();

            const img = new Image();
            img.crossOrigin = 'anonymous';
            const urlRevoke = URL.createObjectURL(file);
            img.onload = function() {
                URL.revokeObjectURL(urlRevoke);
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
                        const blobGrande = await new Promise((resolve, reject) => {
                            canvasGrande.toBlob((blob) => blob ? resolve(blob) : reject(new Error('Error grande')), 'image/webp', 0.9);
                        });
                        const blobPequena = await new Promise((resolve, reject) => {
                            canvasPequena.toBlob((blob) => blob ? resolve(blob) : reject(new Error('Error pequeña')), 'image/webp', 0.9);
                        });
                        const slugInput = document.querySelector('input[name="slug"]');
                        const nombreBase = slugInput ? slugInput.value.trim() : 'imagen';
                        const timestamp = Date.now();
                        const formDataGrande = new FormData();
                        formDataGrande.append('imagen', blobGrande, `${nombreBase}-${timestamp}.webp`);
                        formDataGrande.append('carpeta', carpeta);
                        formDataGrande.append('_token', '{{ csrf_token() }}');
                        const formDataPequena = new FormData();
                        formDataPequena.append('imagen', blobPequena, `${nombreBase}-${timestamp}-thumbnail.webp`);
                        formDataPequena.append('carpeta', carpeta);
                        formDataPequena.append('_token', '{{ csrf_token() }}');
                        const onProg = function(pct) {
                            const el = document.getElementById('kp-prog-sub-' + uploadId);
                            if (el) el.style.width = pct + '%';
                        };
                        const { dataG, dataP } = await window.__kpSubirParejaConProgreso(
                            '{{ route("admin.imagenes.subir-simple") }}',
                            formDataGrande,
                            formDataPequena,
                            '{{ csrf_token() }}',
                            onProg,
                            uploadsPendientesSublinea,
                            uploadId
                        );
                        if (dataG.success && dataP.success) {
                            kpReemplazarRutaPendienteEnEspecificaciones(pendingPath, dataG.data.ruta_relativa);
                        } else {
                            throw new Error(dataG.message || dataP.message || 'Error al subir');
                        }
                    } catch (error) {
                        console.error('Error:', error);
                        kpEliminarRutaPendienteDeEspecificaciones(pendingPath);
                        const j = sublineaImagenesActual.imagenes.indexOf(pendingPath);
                        if (j !== -1) {
                            sublineaImagenesActual.imagenes.splice(j, 1);
                            guardarImagenesSublinea();
                        }
                        renderizarMiniaturasSublinea();
                        alert('Error al procesar la imagen: ' + error.message);
                    }
                })();
            };
            img.onerror = function() {
                URL.revokeObjectURL(urlRevoke);
                kpEliminarRutaPendienteDeEspecificaciones(pendingPath);
                const j = sublineaImagenesActual.imagenes.indexOf(pendingPath);
                if (j !== -1) {
                    sublineaImagenesActual.imagenes.splice(j, 1);
                    guardarImagenesSublinea();
                }
                renderizarMiniaturasSublinea();
                alert('Error al cargar la imagen. Por favor, verifica que sea un formato válido.');
            };
            img.src = urlRevoke;
        }
        
        // Event listener para descargar desde URL
        document.getElementById('btn-descargar-url-sublinea').addEventListener('click', async () => {
            const url = document.getElementById('url-imagen-sublinea').value.trim();
            const carpeta = document.getElementById('carpeta-url-sublinea').value;
            
            if (!carpeta) {
                document.getElementById('error-url-sublinea').textContent = 'Debes seleccionar una carpeta primero.';
                document.getElementById('error-url-sublinea').classList.remove('hidden');
                return;
            }
            
            if (!url) {
                document.getElementById('error-url-sublinea').textContent = 'Debes introducir una URL válida.';
                document.getElementById('error-url-sublinea').classList.remove('hidden');
                return;
            }
            
            document.getElementById('error-url-sublinea').classList.add('hidden');
            carpetaActualSublinea = carpeta;
            imagenTemporalUrlSublinea = url;
            
            mostrarRecorteEnModalSublinea(url);
        });
        
        // Mostrar recorte en el modal de sublínea
        function mostrarRecorteEnModalSublinea(urlImagen) {
            const areaRecorte = document.getElementById('area-recorte-sublinea');
            const img = document.getElementById('imagen-recortar-sublinea');
            const contenedorCropper = document.getElementById('contenedor-cropper-sublinea');
            
            areaRecorte.classList.remove('hidden');
            
            if (cropperSublinea) {
                cropperSublinea.destroy();
            }
            
            const urlProxy = urlImagen.startsWith('http') 
                ? `{{ route('admin.imagenes.proxy') }}?url=${encodeURIComponent(urlImagen)}`
                : urlImagen;
            
            img.crossOrigin = 'anonymous';
            img.src = urlProxy;
            
            img.onload = function() {
                if (cropperSublinea) cropperSublinea.destroy();
                cropperSublinea = new Cropper(img, {
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
                    toggleDragModeOnDblclick: false,
                });
            };
            
            img.onerror = function() {
                document.getElementById('error-url-sublinea').textContent = 'Error al cargar la imagen. Verifica la URL.';
                document.getElementById('error-url-sublinea').classList.remove('hidden');
                areaRecorte.classList.add('hidden');
            };
        }
        
        // Event listener para guardar imagen desde URL
        document.getElementById('btn-guardar-imagen-sublinea').addEventListener('click', async () => {
            const tabActiva = document.querySelector('.tab-modal-sublinea.kp-modal-img-tab--active');
            const tabId = tabActiva ? tabActiva.id : 'tab-url-sublinea';
            
            // Si es la pestaña de Amazon, el código de Amazon ya lo maneja, no hacer nada aquí
            if (tabId === 'tab-amazon-sublinea') {
                return;
            }
            
            if (tabId === 'tab-interna-sublinea' || tabId === 'tab-interna-global-sublinea') {
                let rutas = kpRutasDesdeSeleccionInternaSublinea();
                if (!rutas.length) {
                    const contIs = document.getElementById('filas-interna-sublinea');
                    const filasIs = contIs ? contIs.querySelectorAll('.kp-fila-interna-sublinea') : [];
                    rutas = [];
                    for (let i = 0; i < filasIs.length; i++) {
                        const rG = (
                            filasIs[i].querySelector('.kp-interna-sub-g') &&
                            filasIs[i].querySelector('.kp-interna-sub-g').value
                        ) || '';
                        const rGt = rG.trim();
                        if (!rGt) {
                            alert('Fila ' + (i + 1) + ': indica la ruta de la imagen grande o quítala.');
                            return;
                        }
                        if (!rutas.includes(rGt)) rutas.push(rGt);
                    }
                }
                sublineaImagenesActual.imagenes = rutas;
                guardarImagenesSublinea({ omitirRecargaEspecificaciones: true });
                const verModalSub = document.getElementById('modal-imagenes-sublinea');
                if (verModalSub && !verModalSub.classList.contains('hidden')) {
                    renderizarMiniaturasSublinea();
                }
                cerrarModalAñadirImagenSublinea();
                return;
            }
            
            if (tabId === 'tab-subir-sublinea') {
                if (!fileInputSublinea.files.length) {
                    alert('Por favor selecciona una imagen primero.');
                    return;
                }
                return;
            }
            if (!cropperSublinea || !carpetaActualSublinea) {
                alert('Por favor descarga y recorta la imagen primero.');
                return;
            }
            await procesarImagenRecortadaSublinea();
        });
        
        // Procesar imagen recortada desde URL para sublínea (subida en segundo plano)
        async function procesarImagenRecortadaSublinea() {
            const canvasOriginal = cropperSublinea.getCroppedCanvas({
                imageSmoothingEnabled: true,
                imageSmoothingQuality: 'high'
            });
            if (!canvasOriginal) {
                alert('Error al recortar la imagen');
                return;
            }
            const carpetaUp = carpetaActualSublinea;
            if (!carpetaUp) {
                alert('Selecciona una carpeta primero.');
                return;
            }
            try {
                const canvasGrande = document.createElement('canvas');
                canvasGrande.width = canvasOriginal.width;
                canvasGrande.height = canvasOriginal.height;
                const ctxGrande = canvasGrande.getContext('2d');
                ctxGrande.fillStyle = '#ffffff';
                ctxGrande.fillRect(0, 0, canvasGrande.width, canvasGrande.height);
                ctxGrande.drawImage(canvasOriginal, 0, 0);
                const canvasPequena = document.createElement('canvas');
                canvasPequena.width = 300;
                canvasPequena.height = 250;
                const ctxPequena = canvasPequena.getContext('2d');
                ctxPequena.fillStyle = '#ffffff';
                ctxPequena.fillRect(0, 0, canvasPequena.width, canvasPequena.height);
                ctxPequena.drawImage(canvasOriginal, 0, 0, 300, 250);
                const blobGrande = await new Promise((resolve, reject) => {
                    canvasGrande.toBlob((blob) => blob ? resolve(blob) : reject(new Error('Error grande')), 'image/webp', 0.9);
                });
                const blobPequena = await new Promise((resolve, reject) => {
                    canvasPequena.toBlob((blob) => blob ? resolve(blob) : reject(new Error('Error pequeña')), 'image/webp', 0.9);
                });
                const uploadId = kpNuevoIdSubida();
                const pendingPath = KP_PENDING_IMG + uploadId;
                añadirImagenASublinea(pendingPath);
                const verModalAbierto = document.getElementById('modal-imagenes-sublinea') && !document.getElementById('modal-imagenes-sublinea').classList.contains('hidden');
                if (verModalAbierto) renderizarMiniaturasSublinea();
                prepararModalAnadirSublineaParaOtra('url');
                cargarCarpetasModalSublinea();

                const slugInput = document.querySelector('input[name="slug"]');
                const nombreBase = slugInput ? slugInput.value.trim() : 'imagen';
                const timestamp = Date.now();
                const formDataGrande = new FormData();
                formDataGrande.append('imagen', blobGrande, `${nombreBase}-${timestamp}.webp`);
                formDataGrande.append('carpeta', carpetaUp);
                formDataGrande.append('_token', '{{ csrf_token() }}');
                const formDataPequena = new FormData();
                formDataPequena.append('imagen', blobPequena, `${nombreBase}-${timestamp}-thumbnail.webp`);
                formDataPequena.append('carpeta', carpetaUp);
                formDataPequena.append('_token', '{{ csrf_token() }}');

                (async function() {
                    try {
                        const onProg = function(pct) {
                            const el = document.getElementById('kp-prog-sub-' + uploadId);
                            if (el) el.style.width = pct + '%';
                        };
                        const { dataG, dataP } = await window.__kpSubirParejaConProgreso(
                            '{{ route("admin.imagenes.subir-simple") }}',
                            formDataGrande,
                            formDataPequena,
                            '{{ csrf_token() }}',
                            onProg,
                            uploadsPendientesSublinea,
                            uploadId
                        );
                        if (dataG.success && dataP.success) {
                            kpReemplazarRutaPendienteEnEspecificaciones(pendingPath, dataG.data.ruta_relativa);
                        } else {
                            throw new Error(dataG.message || dataP.message || 'Error al subir');
                        }
                    } catch (error) {
                        console.error('Error:', error);
                        kpEliminarRutaPendienteDeEspecificaciones(pendingPath);
                        const j = sublineaImagenesActual.imagenes.indexOf(pendingPath);
                        if (j !== -1) {
                            sublineaImagenesActual.imagenes.splice(j, 1);
                            guardarImagenesSublinea();
                        }
                        renderizarMiniaturasSublinea();
                        alert('Error al procesar la imagen: ' + error.message);
                    }
                })();
            } catch (error) {
                console.error('Error:', error);
                alert('Error al procesar la imagen: ' + error.message);
            }
        }
        
        // Añadir imagen a la sublínea actual
        function añadirImagenASublinea(rutaImagen) {
            const inputHidden = document.getElementById('categoria_especificaciones_internas_elegidas_input');
            let especificaciones = {};
            
            if (inputHidden && inputHidden.value) {
                try {
                    especificaciones = JSON.parse(inputHidden.value);
                } catch (e) {
                    console.error('Error al parsear especificaciones:', e);
                }
            }
            
            if (!especificaciones[sublineaImagenesActual.principalId]) {
                especificaciones[sublineaImagenesActual.principalId] = [];
            }
            
            // Buscar la sublínea en el array
            let index = especificaciones[sublineaImagenesActual.principalId].findIndex(item => {
                if (typeof item === 'string' || typeof item === 'number') {
                    return String(item) === String(sublineaImagenesActual.sublineaId);
                } else if (item && item.id) {
                    return String(item.id) === String(sublineaImagenesActual.sublineaId);
                }
                return false;
            });
            
            if (index === -1) {
                // Crear nuevo item si no existe
                especificaciones[sublineaImagenesActual.principalId].push({
                    id: sublineaImagenesActual.sublineaId,
                    img: [rutaImagen]
                });
            } else {
                // Actualizar item existente
                if (typeof especificaciones[sublineaImagenesActual.principalId][index] === 'object' && especificaciones[sublineaImagenesActual.principalId][index].id) {
                    if (!especificaciones[sublineaImagenesActual.principalId][index].img) {
                        especificaciones[sublineaImagenesActual.principalId][index].img = [];
                    }
                    especificaciones[sublineaImagenesActual.principalId][index].img.push(rutaImagen);
                } else {
                    // Convertir estructura antigua a nueva
                    especificaciones[sublineaImagenesActual.principalId][index] = {
                        id: especificaciones[sublineaImagenesActual.principalId][index],
                        img: [rutaImagen]
                    };
                }
            }
            
            inputHidden.value = JSON.stringify(especificaciones, null, 0);
            
            // Obtener el número actual de imágenes después de añadir
            const especificacionesParseadas = JSON.parse(inputHidden.value);
            let numImagenes = 0;
            if (especificacionesParseadas[sublineaImagenesActual.principalId]) {
                const sublineaData = especificacionesParseadas[sublineaImagenesActual.principalId].find(item => {
                    if (typeof item === 'string' || typeof item === 'number') {
                        return String(item) === String(sublineaImagenesActual.sublineaId);
                    } else if (item && item.id) {
                        return String(item.id) === String(sublineaImagenesActual.sublineaId);
                    }
                    return false;
                });
                if (sublineaData && sublineaData.img && Array.isArray(sublineaData.img)) {
                    numImagenes = sublineaData.img.length;
                }
            }
            
            // Actualizar contador del botón de ver imágenes en la sección de CATEGORÍA
            const btnVerImagenCategoria = document.querySelector(`.btn-ver-imagenes-sublinea[data-principal-id="${sublineaImagenesActual.principalId}"][data-sublinea-id="${sublineaImagenesActual.sublineaId}"]`);
            if (btnVerImagenCategoria) {
                // El botón ya existe, actualizar su contador
                btnVerImagenCategoria.textContent = `${numImagenes} img`;
                btnVerImagenCategoria.title = `Ver ${numImagenes} imagen${numImagenes > 1 ? 'es' : ''}`;
            } else if (numImagenes > 0) {
                // El botón no existe pero hay imágenes, crearlo
                const btnAñadirCategoria = document.querySelector(`.btn-añadir-imagen-sublinea[data-principal-id="${sublineaImagenesActual.principalId}"][data-sublinea-id="${sublineaImagenesActual.sublineaId}"]`);
                if (btnAñadirCategoria) {
                    // Obtener el contenedor padre y el checkbox para verificar estado
                    const contenedorPadre = btnAñadirCategoria.closest('.flex.items-center.gap-2');
                    const checkboxPrincipal = document.querySelector(`.especificacion-checkbox[data-principal-id="${sublineaImagenesActual.principalId}"][data-sublinea-id="${sublineaImagenesActual.sublineaId}"]`);
                    const usarImagenesCheckbox = document.querySelector(`.especificacion-usar-imagenes-producto-checkbox[data-principal-id="${sublineaImagenesActual.principalId}"][data-sublinea-id="${sublineaImagenesActual.sublineaId}"]`);
                    const isChecked = checkboxPrincipal && checkboxPrincipal.checked;
                    const usarImagenesProducto = usarImagenesCheckbox && usarImagenesCheckbox.checked;
                    const btnVerDeshabilitado = !isChecked || usarImagenesProducto;
                    
                    // Crear el botón de ver imágenes
                    const nuevoBtn = document.createElement('button');
                    nuevoBtn.type = 'button';
                    nuevoBtn.className = `btn-ver-imagenes-sublinea text-xs px-2 py-1 bg-blue-600 hover:bg-blue-700 text-white rounded transition-colors ${btnVerDeshabilitado ? 'opacity-50 cursor-not-allowed' : ''}`;
                    nuevoBtn.setAttribute('data-principal-id', sublineaImagenesActual.principalId);
                    nuevoBtn.setAttribute('data-sublinea-id', sublineaImagenesActual.sublineaId);
                    nuevoBtn.title = `Ver ${numImagenes} imagen${numImagenes > 1 ? 'es' : ''}`;
                    nuevoBtn.textContent = `${numImagenes} img`;
                    if (btnVerDeshabilitado) {
                        nuevoBtn.disabled = true;
                    }
                    
                    // Añadir event listener
                    nuevoBtn.addEventListener('click', function() {
                        const principalId = this.dataset.principalId;
                        const sublineaId = this.dataset.sublineaId;
                        abrirModalImagenesSublinea(principalId, sublineaId);
                    });
                    
                    // Insertar antes del botón de añadir
                    contenedorPadre.insertBefore(nuevoBtn, btnAñadirCategoria);
                }
            }
            
            // Actualizar contador del botón de ver imágenes en la sección del PRODUCTO
            const btnVerImagenProducto = document.querySelector(`.btn-ver-imagenes-sublinea-producto[data-principal-id="${sublineaImagenesActual.principalId}"][data-sublinea-id="${sublineaImagenesActual.sublineaId}"]`);
            if (btnVerImagenProducto) {
                // El botón ya existe, actualizar su contador
                btnVerImagenProducto.textContent = `${numImagenes} img`;
                btnVerImagenProducto.title = `Ver ${numImagenes} imagen${numImagenes > 1 ? 'es' : ''}`;
                if (btnVerImagenProducto.classList.contains('hidden')) {
                    btnVerImagenProducto.classList.remove('hidden');
                }
            } else if (numImagenes > 0) {
                // El botón no existe pero hay imágenes, crearlo
                const btnAñadirProducto = document.querySelector(`.btn-añadir-imagen-sublinea-producto[data-principal-id="${sublineaImagenesActual.principalId}"][data-sublinea-id="${sublineaImagenesActual.sublineaId}"]`);
                if (btnAñadirProducto) {
                    // Obtener el contenedor padre y el checkbox para verificar estado
                    const contenedorPadre = btnAñadirProducto.closest('.flex.items-center.gap-2');
                    const checkboxPrincipal = document.querySelector(`.especificacion-producto-checkbox[data-principal-id="${sublineaImagenesActual.principalId}"][data-sublinea-id="${sublineaImagenesActual.sublineaId}"]`);
                    const usarImagenesCheckbox = document.querySelector(`.especificacion-producto-usar-imagenes-producto-checkbox[data-principal-id="${sublineaImagenesActual.principalId}"][data-sublinea-id="${sublineaImagenesActual.sublineaId}"]`);
                    const isChecked = checkboxPrincipal && checkboxPrincipal.checked;
                    const usarImagenesProducto = usarImagenesCheckbox && usarImagenesCheckbox.checked;
                    const btnVerDeshabilitado = !isChecked || usarImagenesProducto;
                    
                    // Crear el botón de ver imágenes
                    const nuevoBtn = document.createElement('button');
                    nuevoBtn.type = 'button';
                    nuevoBtn.className = `btn-ver-imagenes-sublinea-producto text-xs px-2 py-1 bg-blue-600 hover:bg-blue-700 text-white rounded transition-colors ${btnVerDeshabilitado ? 'opacity-50 cursor-not-allowed' : ''}`;
                    nuevoBtn.setAttribute('data-principal-id', sublineaImagenesActual.principalId);
                    nuevoBtn.setAttribute('data-sublinea-id', sublineaImagenesActual.sublineaId);
                    nuevoBtn.title = `Ver ${numImagenes} imagen${numImagenes > 1 ? 'es' : ''}`;
                    nuevoBtn.textContent = `${numImagenes} img`;
                    if (btnVerDeshabilitado) {
                        nuevoBtn.disabled = true;
                    }
                    
                    // Añadir event listener
                    nuevoBtn.addEventListener('click', function() {
                        const principalId = this.dataset.principalId;
                        const sublineaId = this.dataset.sublineaId;
                        abrirModalImagenesSublinea(principalId, sublineaId);
                    });
                    
                    // Insertar antes del botón de añadir
                    contenedorPadre.insertBefore(nuevoBtn, btnAñadirProducto);
                }
            }
            
            // Actualizar JSON del producto si existe la función
            if (typeof actualizarJSONProducto === 'function') {
                actualizarJSONProducto();
            }
            
            // Nota: No recargamos las especificaciones aquí porque ya actualizamos los botones manualmente
            // Esto evita recargas innecesarias y mantiene la UI más fluida
        }

        // Ejecutar al cargar la página si hay una categoría final o una categoría de especificaciones guardada
        document.addEventListener('DOMContentLoaded', () => {
            const categoriaEspecificacionesId = document.getElementById('categoria_especificaciones_id');
            const categoriaEspecificacionesNombre = document.getElementById('categoria_especificaciones_nombre');
            const checkboxNoAnadir = document.getElementById('no_anadir_especificaciones');
            
            // Si no hay categoría seleccionada, marcar el checkbox "No añadir"
            if (!categoriaEspecificacionesId || !categoriaEspecificacionesId.value) {
                if (checkboxNoAnadir) {
                    checkboxNoAnadir.checked = true;
                }
                return;
            }
            
            // Si el checkbox está marcado, no cargar nada
            if (checkboxNoAnadir && checkboxNoAnadir.checked) {
                return;
            }
            
            // Si ya hay una categoría guardada, cargar sus especificaciones internas
            if (categoriaEspecificacionesId && categoriaEspecificacionesId.value) {
                setTimeout(() => {
                    obtenerEspecificacionesInternas(categoriaEspecificacionesId.value);
                }, 500);
                return;
            }
            
            // Si no hay categoría guardada pero hay una categoría final, cargarla
            setTimeout(() => {
                autoSeleccionarCategoriaEspecificaciones();
            }, 1000);
        });
    </script>
    {{-- SCRIPT PARA AÑADIR OFERTA --}}
    <script>
        function abrirModalOferta(productoId) {
            const modal = document.getElementById('modalOferta');
            const contenedor = document.getElementById('contenido-modal-oferta');

            if (!productoId || productoId === 'null') {
                contenedor.innerHTML = '<p class="text-red-600 dark:text-red-400">Primero debes guardar el producto para poder añadir ofertas.</p>';
                modal.classList.remove('hidden');
                return;
            }

            contenedor.innerHTML = '<p class="text-gray-600 dark:text-gray-300">Cargando formulario...</p>';

            fetch(`/admin/productos/${productoId}/ofertas/create`)
                .then(res => res.text())
                .then(html => {
                    contenedor.innerHTML = html;
                    modal.classList.remove('hidden');
                })
                .catch(() => {
                    contenedor.innerHTML = '<p class="text-red-600 dark:text-red-400">Error al cargar el formulario de oferta.</p>';
                    modal.classList.remove('hidden');
                });
        }

        function cerrarModalOferta() {
            document.getElementById('modalOferta').classList.add('hidden');
            document.getElementById('contenido-modal-oferta').innerHTML = '';
        }
    </script>

{{-- SCRIPT PARA RELLENAR INFORMACIÓN AUTOMÁTICAMENTE --}}
    <script>
        function kpObtenerCategoriaProductoAutocompletadoId() {
            return document.getElementById('categoria-final')?.value || '';
        }

        document.addEventListener('DOMContentLoaded', () => {
            const btnRellenar = document.getElementById('rellenar-info-automatica');
            const btnText = document.getElementById('btn-text');
            const btnLoading = document.getElementById('btn-loading');
            const progresoInfo = document.getElementById('progreso-info');
            const progresoTexto = document.getElementById('progreso-texto');
            let rellenandoInfoAutomatica = false;

            const actualizarEstadoBotonRellenar = () => {
                const tieneCategoria = !!kpObtenerCategoriaProductoAutocompletadoId();
                btnRellenar.disabled = rellenandoInfoAutomatica || !tieneCategoria;
                btnRellenar.title = tieneCategoria
                    ? 'Rellenar información automáticamente'
                    : 'Selecciona una categoría antes de rellenar automáticamente';
            };

            actualizarEstadoBotonRellenar();
            setInterval(actualizarEstadoBotonRellenar, 500);
            document.addEventListener('change', (event) => {
                if (event.target.matches('.categoria-select, #categoria-final')) {
                    setTimeout(actualizarEstadoBotonRellenar, 50);
                }
            });
            btnRellenar.addEventListener('mouseenter', actualizarEstadoBotonRellenar);
            btnRellenar.addEventListener('focus', actualizarEstadoBotonRellenar);

            // Campos que deben estar vacíos para poder procedir
            const camposAVerificar = [
                'titulo',
                'subtitulo', 
                'descripcion_corta',
                'descripcion_larga',
                'caracteristicas'
            ];

            // Campos que deben estar llenos para proceder
            const camposRequeridos = [
                'nombre',
                'marca', 
                'modelo',
                'talla'
            ];

            btnRellenar.addEventListener('click', async () => {
                actualizarEstadoBotonRellenar();
                const categoriaIdProducto = kpObtenerCategoriaProductoAutocompletadoId();
                if (!categoriaIdProducto) {
                    alert('Debes seleccionar una categoría antes de rellenar la información automáticamente.');
                    return;
                }

                // Verificar que los campos requeridos estén llenos
                const camposFaltantes = [];
                camposRequeridos.forEach(campo => {
                    const valor = document.querySelector(`input[name="${campo}"]`).value.trim();
                    if (!valor) {
                        camposFaltantes.push(campo);
                    }
                });

                if (camposFaltantes.length > 0) {
                    alert(`Por favor, rellena los siguientes campos antes de continuar: ${camposFaltantes.join(', ')}`);
                    return;
                }

                // Verificar que los campos a rellenar estén vacíos
                const camposLlenos = [];
                camposAVerificar.forEach(campo => {
                    const elemento = document.querySelector(`[name="${campo}"]`);
                    const valor = elemento.value.trim();
                    if (valor) {
                        camposLlenos.push(campo);
                    }
                });

                if (camposLlenos.length > 0) {
                    const confirmar = confirm(`Los siguientes campos ya tienen contenido: ${camposLlenos.join(', ')}. ¿Deseas continuar y sobrescribir el contenido?`);
                    if (!confirmar) {
                        return;
                    }
                }

                // Iniciar proceso
                btnText.classList.add('hidden');
                btnLoading.classList.remove('hidden');
                progresoInfo.classList.remove('hidden');
                rellenandoInfoAutomatica = true;
                btnRellenar.disabled = true;

                try {
                    // Obtener datos del producto
                    const nombre = document.querySelector('input[name="nombre"]').value.trim();
                    const marca = document.querySelector('input[name="marca"]').value.trim();
                    const modelo = document.querySelector('input[name="modelo"]').value.trim();
                    const talla = document.querySelector('input[name="talla"]').value.trim();

                    progresoTexto.textContent = 'Preparando datos del producto...';

                    const categoriaId = categoriaIdProducto;

                    // Obtener nombre de la última categoría seleccionada
                    const selectores = document.querySelectorAll('.categoria-select');
                    let nombreCategoria = 'la categoría seleccionada';
                    for (let i = selectores.length - 1; i >= 0; i--) {
                        if (selectores[i].value) {
                            const opcionSeleccionada = selectores[i].options[selectores[i].selectedIndex];
                            if (opcionSeleccionada && opcionSeleccionada.textContent) {
                                nombreCategoria = opcionSeleccionada.textContent;
                                break;
                            }
                        }
                    }

                    // Preparar prompt para ChatGPT
                    let prompt = `Actúa como un experto en SEO y marketing digital especializado en productos para ${nombreCategoria}. Necesito que generes contenido optimizado para un comparador de precios.

INFORMACIÓN DEL PRODUCTO:
- Nombre: ${nombre}
- Marca: ${marca}
- Modelo: ${modelo}
- Talla: ${talla}

INSTRUCCIONES ESPECÍFICAS:
Debes generar EXACTAMENTE un objeto JSON con la siguiente estructura, sin texto adicional antes o después:

{
  "titulo": "",
  "subtitulo": "",
  "descripcion_corta": "",
  "descripcion_larga": "",
  "caracteristicas": "",
  "meta_titulo": "",
  "meta_descripcion": "",
  "pros": ["AQUI UN PRO", "AQUI UN PRO", "AQUI UN PRO", "AQUI UN PRO", "AQUI UN PRO", "AQUI UN PRO"],
  "contras": ["AQUI UN CONTRA", "AQUI UN CONTRA", "AQUI UN CONTRA"],
  "preguntas_frecuentes": [
    {"pregunta": "AQUI DEBES AÑADIRME LA PREGUNTA", "respuesta": "AQUÍ DEBES AÑADIRLA RESPUESTA"},
    {"pregunta": "AQUI DEBES AÑADIRME LA PREGUNTA", "respuesta": "AQUÍ DEBES AÑADIRLA RESPUESTA"}
  ]
}

REGLAS IMPORTANTES:
-titulo -> Debe ser corto y conciso con la funcion que tiene el comparador de precio y el nombre del producto
-subtitulo -> Debe ser corto ya  que esto es un titulo que estará encima del listado de precios del producto, es un h2, pero no me pongas la etiqueta HTML que ya se la estoy poniendo yo, te lo comento para que sepas que debe ser corto y claro.
-descripcion_corta -> Debe ser una breve descripcion del producto de entre 300 y 400 caracteres.
-descripcion_larga -> Aquí me debes poner h3, p, en este campo si me puedes poner etiquetas HTML ya que esto será la descripcion y explicacion de detalles de la ficha del producto, aquí debemos ponerme entre 700 y 800 caracteres.
-meta_titulo: Debe ser corto y conciso ya que esto es para SEO y tiene que quedar claro de que va el articulo del comparador de precios con el nombre del producto
-meta_descripcion: Debe ser una breve descripcion de lo que se va a encontrar el usuario si entra en mi web, de un comparador de precios, para comprar al mejor precio y cosas así. Con el nombre del producto.
-pros-> TIENES LA ESTRUCTURA DE COMO DEBES AÑADIR LOS PROS, DONDE ME DEBES AÑADIR DONDE PONE AQUI UN PRO, CAMBIARMELO POR UN PRO QUE TENGA ESTE PRODUTO
-contra-> TIENES LA ESTRUCTURA DE COMO DEBES DEVOLVERMELO, TAN SOLO TIENES QUE CAMBIARME EL AQUI UN CONTRA, POR UN CONTRA QUE TENGA ESTE PRODUCTO
-preguntas_frecuentes-> TIENES LA ESTRUCTURA DE COMO DEBES AÑADIRME LA PREGUNTA Y LA RESPUESTA, DONDE PONE AQUI DEBES AÑADIRME LA PREGUNTA, PUES AHÍ ME PONES LA PREGUNTA Y DONDE PONE AQUÍ DEBES AÑADIRLA RESPUESTA, PUES AHÍ ME PONER LA RESPUESTA A LA PREGUNTA, DEBE HABER MINIMO 4 PREGUNTAS CON SUS RESPUESTAS.
- Devuelve ÚNICAMENTE el JSON, sin explicaciones adicionales
- Asegúrate de que el JSON sea válido
- Incluye exactamente los campos especificados
- Usa la información del producto proporcionada
- Optimiza para SEO con palabras clave relevantes`;

                    progresoTexto.textContent = 'Enviando petición a ChatGPT...';

                    // Hacer petición a ChatGPT
                    const response = await fetch('/productos/generar-contenido', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            prompt: prompt,
                            nombre: nombre,
                            marca: marca,
                            modelo: modelo,
                            talla: talla,
                            categoria_id: categoriaId
                        })
                    });

                    if (!response.ok) {
                        throw new Error(`Error HTTP: ${response.status}`);
                    }

                    progresoTexto.textContent = 'Procesando respuesta de ChatGPT...';

                    const data = await response.json();

                    if (data.error) {
                        throw new Error(data.error);
                    }

                    progresoTexto.textContent = 'Rellenando campos del formulario...';

                    // Rellenar los campos con la respuesta
                    if (data.titulo) {
                        document.querySelector('input[name="titulo"]').value = data.titulo;
                    }
                    if (data.subtitulo) {
                        document.querySelector('input[name="subtitulo"]').value = data.subtitulo;
                    }
                    if (data.descripcion_corta) {
                        document.querySelector('textarea[name="descripcion_corta"]').value = data.descripcion_corta;
                    }
                    if (data.descripcion_larga) {
                        document.querySelector('textarea[name="descripcion_larga"]').value = data.descripcion_larga;
                    }
                    if (data.caracteristicas) {
                        document.querySelector('textarea[name="caracteristicas"]').value = data.caracteristicas;
                    }
                    

                    // Rellenar pros
                    if (data.pros && data.pros.length > 0) {
                        const prosList = document.getElementById('pros-list');
                        prosList.innerHTML = '';
                        data.pros.forEach(pro => {
                            const input = document.createElement('input');
                            input.type = 'text';
                            input.name = 'pros[]';
                            input.value = pro.trim();
                            input.className = 'w-full mb-2 px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border';
                            prosList.appendChild(input);
                        });
                    }

                    // Rellenar contras
                    if (data.contras && data.contras.length > 0) {
                        const contrasList = document.getElementById('contras-list');
                        contrasList.innerHTML = '';
                        data.contras.forEach(contra => {
                            const input = document.createElement('input');
                            input.type = 'text';
                            input.name = 'contras[]';
                            input.value = contra.trim();
                            input.className = 'w-full mb-2 px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border';
                            contrasList.appendChild(input);
                        });
                    }

                    // Rellenar FAQ
                    if (data.preguntas_frecuentes && data.preguntas_frecuentes.length > 0) {
                        const faqList = document.getElementById('faq-list');
                        faqList.innerHTML = '';
                        data.preguntas_frecuentes.forEach((faq, index) => {
                            const div = document.createElement('div');
                            div.className = 'faq-item grid grid-cols-1 md:grid-cols-2 gap-4';
                            div.innerHTML = `
                                <input type="text" name="faq[${index}][pregunta]" placeholder="Pregunta"
                                    value="${faq.pregunta}" class="w-full px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border">
                                <input type="text" name="faq[${index}][respuesta]" placeholder="Respuesta"
                                    value="${faq.respuesta}" class="w-full px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border">
                            `;
                            faqList.appendChild(div);
                        });
                    }
                    if (data.meta_titulo) {
                        document.querySelector('textarea[name="meta_titulo"]').value = data.meta_titulo;
                    }
                    if (data.meta_descripcion) {
                        document.querySelector('textarea[name="meta_description"]').value = data.meta_descripcion;
                    }

                    progresoTexto.textContent = '¡Información rellenada correctamente!';
                    progresoTexto.className = 'text-green-600 dark:text-green-400';

                } catch (error) {
                    console.error('Error completo:', error);
                    console.error('Mensaje:', error.message);
                    console.error('Stack:', error.stack);
                    progresoTexto.textContent = `Error: ${error.message}`;
                    progresoTexto.className = 'text-red-600 dark:text-red-400';
                } finally {
                    // Restaurar estado del botón
                    setTimeout(() => {
                        btnText.classList.remove('hidden');
                        btnLoading.classList.add('hidden');
                        progresoInfo.classList.add('hidden');
                        rellenandoInfoAutomatica = false;
                        actualizarEstadoBotonRellenar();
                        progresoTexto.className = 'text-gray-600 dark:text-gray-400';
                    }, 3000);
                }
            });
        });
    </script>
{{-- VALIDACIÓN EN TIEMPO REAL DE SLUG --}}
<script>
// Validación en tiempo real de slug
let slugValidationTimeout = null;
let slugValidationInProgress = false;
let slugIsValid = false;

// Función para guardar estado de validación en localStorage
function guardarEstadoValidacionSlug() {
    const estado = {
        slugIsValid: slugIsValid,
        slugValue: document.getElementById('slug').value
    };
    localStorage.setItem('slugValidationState', JSON.stringify(estado));
}

document.addEventListener('DOMContentLoaded', function() {
    // Solo ejecutar este script si estamos en el formulario de productos
    const productoNombre = document.getElementById('producto_nombre');
    if (!productoNombre) {
        return; // No estamos en el formulario de productos
    }
    
    const slugInput = document.getElementById('slug');
    const validationMessage = document.getElementById('slug_validation_message');
    const btnGuardar = document.getElementById('btn_guardar');
    
    // Si no estamos en el formulario de productos, salir
    if (!slugInput || !validationMessage || !btnGuardar) {
        return;
    }
    
    // Obtener el ID del producto si estamos editando
    const productoId = {{ $producto ? $producto->id : 'null' }};
    
    // Función para validar slug
    async function validarSlug(slug) {
        if (!slug || slug.trim() === '') {
            mostrarMensajeSlug('', '');
            return;
        }
        
        try {
            const response = await fetch('{{ route("admin.productos.verificar.slug") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    slug: slug,
                    producto_id: productoId
                })
            });
            
            const data = await response.json();
            
            switch (data.tipo) {
                case 'disponible':
                    mostrarMensajeSlug('success', data.mensaje);
                    slugIsValid = true;
                    break;
                    
                case 'duplicado':
                    mostrarMensajeSlug('error', data.mensaje);
                    mostrarProductoExistente(data.producto_existente);
                    slugIsValid = false;
                    break;
                    
                case 'vacio':
                    mostrarMensajeSlug('', '');
                    slugIsValid = false;
                    break;
                    
                default:
                    mostrarMensajeSlug('error', 'Error al validar el slug');
                    slugIsValid = false;
            }
        } catch (error) {
            console.error('Error al validar slug:', error);
            mostrarMensajeSlug('error', 'Error al validar el slug');
            slugIsValid = false;
        }
        
        slugValidationInProgress = false;
        actualizarEstadoBotonSlug();
    }
    
    // Función para mostrar mensaje de validación
    function mostrarMensajeSlug(tipo, mensaje) {
        const otherProductsContainer = document.getElementById('slug_other_products');
        
        validationMessage.className = 'mt-1 text-sm';
        
        // Ocultar contenedor por defecto
        otherProductsContainer.classList.add('hidden');
        
        if (tipo === 'error') {
            validationMessage.classList.add('text-red-500');
            validationMessage.classList.remove('text-green-500', 'text-blue-500', 'hidden');
        } else if (tipo === 'success') {
            validationMessage.classList.add('text-green-500');
            validationMessage.classList.remove('text-red-500', 'text-blue-500', 'hidden');
        } else {
            validationMessage.classList.add('hidden');
        }
        
        validationMessage.textContent = mensaje;
    }
    
    // Función para mostrar el producto existente
    function mostrarProductoExistente(producto) {
        const productsList = document.getElementById('slug_products_list');
        const otherProductsContainer = document.getElementById('slug_other_products');
        
        if (producto) {
            const nombreCompleto = `${producto.nombre} - ${producto.marca} - ${producto.modelo} - ${producto.talla}`;
            productsList.innerHTML = `<div class="mb-1">• ${nombreCompleto}</div>`;
            otherProductsContainer.classList.remove('hidden');
        } else {
            productsList.innerHTML = '<div class="text-gray-500">No se encontró información del producto</div>';
        }
    }
    
    // Función para actualizar estado del botón
    function actualizarEstadoBotonSlug() {
        const slugValue = slugInput.value.trim();
        
        if (slugValidationInProgress) {
            btnGuardar.disabled = true;
            btnGuardar.textContent = 'Validando slug...';
        } else if (slugValue && !slugIsValid) {
            btnGuardar.disabled = true;
            btnGuardar.textContent = 'Slug duplicado';
        } else if (!slugValue) {
            btnGuardar.disabled = true;
            btnGuardar.textContent = 'Slug requerido';
        } else {
            btnGuardar.disabled = false;
            btnGuardar.textContent = 'Guardar producto';
        }
        
        // Guardar estado en localStorage
        guardarEstadoValidacionSlug();
    }

    // Función para cargar estado de validación desde localStorage
    function cargarEstadoValidacionSlug() {
        const estadoGuardado = localStorage.getItem('slugValidationState');
        if (estadoGuardado) {
            const estado = JSON.parse(estadoGuardado);
            
            // Solo restaurar si el slug actual coincide con el guardado
            if (estado.slugValue === slugInput.value) {
                slugIsValid = estado.slugIsValid;
                actualizarEstadoBotonSlug();
                
                // Restaurar mensaje de validación si es necesario
                if (slugInput.value.trim()) {
                    if (slugIsValid) {
                        mostrarMensajeSlug('success', 'Este slug está disponible');
                    } else {
                        mostrarMensajeSlug('error', 'Este slug ya existe en otro producto');
                    }
                }
            }
        }
    }
    
    // Event listener para cambios en el campo slug
    slugInput.addEventListener('input', function(e) {
        const slug = e.target.value.trim();
        
        // Limpiar timeout anterior
        if (slugValidationTimeout) {
            clearTimeout(slugValidationTimeout);
        }
        
        // Si el slug está vacío, ocultar mensaje y habilitar botón
        if (!slug) {
            mostrarMensajeSlug('', '');
            slugIsValid = false;
            actualizarEstadoBotonSlug();
            return;
        }
        
        // Mostrar estado de validación
        slugValidationInProgress = true;
        actualizarEstadoBotonSlug();
        
        // Validar después de 1 segundo de inactividad
        slugValidationTimeout = setTimeout(() => {
            validarSlug(slug);
        }, 1000);
    });
    
    
    // Cargar estado de validación guardado
    cargarEstadoValidacionSlug();
    
    // Validar slug inicial si existe
    if (slugInput.value.trim()) {
        slugValidationInProgress = true;
        actualizarEstadoBotonSlug();
        validarSlug(slugInput.value.trim());
    } else {
        // Si no hay slug inicial, actualizar estado del botón
        actualizarEstadoBotonSlug();
    }
    
    // Prevenir doble clic en enlaces
    const links = document.querySelectorAll('a[href]');
    links.forEach(link => {
        link.addEventListener('click', function(e) {
            // Si el enlace ya está siendo procesado, prevenir el clic
            if (this.dataset.processing === 'true') {
                e.preventDefault();
                return false;
            }
            
            // Marcar como en procesamiento
            this.dataset.processing = 'true';
            
            // Remover la marca después de un tiempo
            setTimeout(() => {
                this.dataset.processing = 'false';
            }, 2000);
        });
    });
    
    // Prevenir doble clic en botones (excluyendo el botón de guardar)
    const buttons = document.querySelectorAll('button:not([type="submit"])');
    buttons.forEach(button => {
        button.addEventListener('click', function(e) {
            if (this.dataset.processing === 'true') {
                e.preventDefault();
                return false;
            }
            
            this.dataset.processing = 'true';
            
            setTimeout(() => {
                this.dataset.processing = 'false';
            }, 2000);
        });
    });
});
</script>

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
        if (typeof window.kpUrlVistaDesdeRutaAlmacen === 'function') {
            return window.kpUrlVistaDesdeRutaAlmacen(raw);
        }
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

    window.kpModalImgTabSetActive = function(tabs, activeEl) {
        (tabs || []).forEach(function(t) {
            if (!t) return;
            var on = t === activeEl;
            t.classList.toggle('kp-modal-img-tab--active', on);
            t.setAttribute('aria-selected', on ? 'true' : 'false');
        });
    };

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

{{-- SCRIPT PARA GESTIÓN DE MÚLTIPLES IMÁGENES --}}
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Solo ejecutar este script si estamos en el formulario de productos
    const productoNombre = document.getElementById('producto_nombre');
    if (!productoNombre) {
        return;
    }

    // Variables globales
    let imagenesGrandes = [];
    let imagenesPequenas = [];
    const KP_PENDING_PRD = '__pending:';
    const uploadsPendientesProducto = new Map();
    let imagenEditandoIndex = null;
    let cropperNueva = null;
    let imagenTemporalUrlNueva = null;
    let carpetaActualNueva = null;
    let wasDragged = false; // Flag para saber si se hizo drag

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
    
    function kpAsegurarArrayImagenes(val) {
        if (Array.isArray(val)) return val;
        if (typeof val === 'string') {
            const t = val.trim();
            if (!t) return [];
            try {
                const parsed = JSON.parse(t);
                if (Array.isArray(parsed)) return parsed;
                if (typeof parsed === 'string' && parsed.trim()) {
                    try {
                        const twice = JSON.parse(parsed);
                        return Array.isArray(twice) ? twice : [parsed.trim()];
                    } catch (e2) {
                        return [parsed.trim()];
                    }
                }
            } catch (e) {
                return [t];
            }
        }
        return [];
    }

    // Cargar imágenes existentes al inicio
    function cargarImagenesExistentes() {
        const inputGrandes = document.getElementById('imagenes-grandes-json');
        const inputPequenas = document.getElementById('imagenes-pequenas-json');
        
        try {
            const rawGrandes = inputGrandes && inputGrandes.value ? JSON.parse(inputGrandes.value) : [];
            const rawPequenas = inputPequenas && inputPequenas.value ? JSON.parse(inputPequenas.value) : [];
            imagenesGrandes = kpAsegurarArrayImagenes(rawGrandes);
            imagenesPequenas = kpAsegurarArrayImagenes(rawPequenas);
        } catch (e) {
            console.error('Error al parsear imágenes:', e);
            imagenesGrandes = [];
            imagenesPequenas = [];
        }
        
        // Asegurar que ambos arrays tengan la misma longitud
        const maxLength = Math.max(imagenesGrandes.length, imagenesPequenas.length);
        while (imagenesGrandes.length < maxLength) imagenesGrandes.push('');
        while (imagenesPequenas.length < maxLength) imagenesPequenas.push('');
        
        renderizarImagenes();
    }

    let __kpInternaNuevaSelOrden = [];
    let __kpInternaNuevaRowInc = 0;

    function kpPendientePrdLocal(r) {
        return typeof r === 'string' && r.indexOf(KP_PENDING_PRD) === 0;
    }
    function kpInferirThumbnailDesdeGrandeProd(g) {
        if (!g || typeof g !== 'string' || kpPendientePrdLocal(g)) return '';
        const lastDot = g.lastIndexOf('.');
        if (lastDot === -1) return g + '-thumbnail';
        return g.slice(0, lastDot) + '-thumbnail' + g.slice(lastDot);
    }
    function kpInferirGrandeDesdeThumbnailProd(p) {
        if (!p || typeof p !== 'string' || kpPendientePrdLocal(p)) return '';
        const t = p.trim();
        if (!t) return '';
        const lastDot = t.lastIndexOf('.');
        if (lastDot === -1) return t.endsWith('-thumbnail') ? t.slice(0, -'-thumbnail'.length) : '';
        const base = t.slice(0, lastDot);
        const ext = t.slice(lastDot);
        if (base.endsWith('-thumbnail')) return base.slice(0, -'-thumbnail'.length) + ext;
        return '';
    }
    function kpExtraerImagenesDeItemEspecProd(item, acc) {
        if (!item || typeof item !== 'object' || !item.img || !Array.isArray(item.img)) return;
        item.img.forEach(function(path) {
            if (typeof path !== 'string' || !path.trim()) return;
            const tr = path.trim();
            if (kpPendientePrdLocal(tr)) return;
            acc.push(tr);
        });
    }
    function kpRecolectarRutasSueltasEspecificacionesProd(esp) {
        const acc = [];
        if (!esp || typeof esp !== 'object') return acc;
        const meta = ['_formatos', '_orden', '_columnas'];
        if (esp._producto && typeof esp._producto === 'object' && Array.isArray(esp._producto.filtros)) {
            esp._producto.filtros.forEach(function(filtro) {
                kpExtraerImagenesDeItemEspecProd(filtro, acc);
                if (Array.isArray(filtro.subprincipales)) {
                    filtro.subprincipales.forEach(function(sub) { kpExtraerImagenesDeItemEspecProd(sub, acc); });
                }
            });
        }
        Object.keys(esp).forEach(function(key) {
            if (meta.indexOf(key) !== -1 || key === '_producto') return;
            const arr = esp[key];
            if (!Array.isArray(arr)) return;
            arr.forEach(function(item) { kpExtraerImagenesDeItemEspecProd(item, acc); });
        });
        return acc;
    }
    function kpNormalizarParImagenProd(gRaw, pRaw) {
        let g = (gRaw || '').trim();
        let p = (pRaw || '').trim();
        if (!g && p) g = kpInferirGrandeDesdeThumbnailProd(p) || '';
        if (g && !p) p = kpInferirThumbnailDesdeGrandeProd(g) || '';
        const thumbVisual = (p || kpInferirThumbnailDesdeGrandeProd(g) || g);
        return { rutaGrande: g, rutaPequena: p, thumbVisual: thumbVisual };
    }
    function kpRecolectarParesImagenesDisponiblesProducto() {
        const pairs = [];
        const seen = new Set();
        function addNorm(norm) {
            if (!norm.rutaGrande && !norm.rutaPequena) return;
            const key = norm.rutaGrande + '\n' + norm.rutaPequena;
            if (seen.has(key)) return;
            seen.add(key);
            pairs.push(norm);
        }
        for (let i = 0; i < imagenesGrandes.length; i++) {
            addNorm(kpNormalizarParImagenProd(imagenesGrandes[i], imagenesPequenas[i]));
        }
        const inp = document.getElementById('categoria_especificaciones_internas_elegidas_input');
        if (inp && inp.value) {
            try {
                const esp = JSON.parse(inp.value);
                kpRecolectarRutasSueltasEspecificacionesProd(esp).forEach(function(soloPath) {
                    addNorm(kpNormalizarParImagenProd(soloPath, ''));
                });
            } catch (e) {}
        }
        return pairs;
    }
    window.kpRecolectarParesImagenesDisponiblesProducto = function() {
        return kpRecolectarParesImagenesDisponiblesProducto();
    };

    function kpPairKeyInternaProd(p) {
        return (p.rutaGrande || '') + '\x01' + (p.rutaPequena || '');
    }
    function kpStableB64KeyInterna(keyStr) {
        try {
            return btoa(unescape(encodeURIComponent(keyStr)));
        } catch (e) {
            return String(keyStr.length) + '_' + keyStr.slice(0, 80);
        }
    }
    function kpLimpiarInternaNuevaProductoUI() {
        __kpInternaNuevaSelOrden = [];
        const g = document.getElementById('galeria-imgs-interna-nueva');
        if (g) g.innerHTML = '';
        const f = document.getElementById('filas-interna-nueva-producto');
        if (f) f.innerHTML = '';
        const vac = document.getElementById('galeria-imgs-interna-nueva-vacio');
        if (vac) vac.classList.add('hidden');
    }
    function kpBindFilasInternaNuevaDelegation() {
        const cont = document.getElementById('filas-interna-nueva-producto');
        if (!cont || cont.dataset.kpDelegNueva) return;
        cont.dataset.kpDelegNueva = '1';
        const upd = function(inp) {
            const row = inp.closest('.kp-fila-interna-nueva');
            if (!row) return;
            const g = row.querySelector('.kp-interna-nueva-g');
            const p = row.querySelector('.kp-interna-nueva-p');
            const pg = row.querySelector('.kp-interna-nueva-pg');
            const pp = row.querySelector('.kp-interna-nueva-pp');
            if (typeof window.kpActualizarPreviewInputInterno === 'function') {
                if (g && pg && g.id && pg.id) window.kpActualizarPreviewInputInterno(g.id, pg.id);
                if (p && pp && p.id && pp.id) window.kpActualizarPreviewInputInterno(p.id, pp.id);
            }
        };
        cont.addEventListener('input', function(e) {
            const t = e.target;
            if (!t.classList.contains('kp-interna-nueva-g') && !t.classList.contains('kp-interna-nueva-p')) return;
            upd(t);
        });
        cont.addEventListener('paste', function(e) {
            const t = e.target;
            if (!t.classList.contains('kp-interna-nueva-g') && !t.classList.contains('kp-interna-nueva-p')) return;
            setTimeout(function() { upd(t); }, 0);
        });
    }
    function kpIndiceSeleccionInternaNueva(pair) {
        const norm = kpNormalizarParImagenProd(pair.rutaGrande, pair.rutaPequena);
        const key = kpPairKeyInternaProd(norm);
        let ix = __kpInternaNuevaSelOrden.findIndex(function(x) {
            return kpPairKeyInternaProd(kpNormalizarParImagenProd(x.rutaGrande, x.rutaPequena)) === key;
        });
        if (ix !== -1) return ix;
        if (norm.rutaGrande) {
            ix = __kpInternaNuevaSelOrden.findIndex(function(x) {
                return x.rutaGrande && String(x.rutaGrande) === String(norm.rutaGrande);
            });
        }
        return ix;
    }
    function kpCargarInternaNuevaDesdeProducto() {
        kpLimpiarInternaNuevaProductoUI();
        for (let i = 0; i < imagenesGrandes.length; i++) {
            const pair = kpNormalizarParImagenProd(imagenesGrandes[i], imagenesPequenas[i]);
            if (!pair.rutaGrande && !pair.rutaPequena) continue;
            __kpInternaNuevaSelOrden.push(pair);
            kpAddFilaInternaNuevaProducto(pair);
        }
        kpRenderGaleriaInternaNueva();
    }
    function kpQuitarFilaInternaNuevaPorPair(pair) {
        const cont = document.getElementById('filas-interna-nueva-producto');
        if (!cont) return;
        const norm = kpNormalizarParImagenProd(pair.rutaGrande, pair.rutaPequena);
        const b64 = kpStableB64KeyInterna(kpPairKeyInternaProd(norm));
        let hit = cont.querySelector('.kp-fila-interna-nueva[data-pair-key="' + b64 + '"]');
        if (!hit && norm.rutaGrande) {
            cont.querySelectorAll('.kp-fila-interna-nueva').forEach(function(row) {
                if (hit) return;
                const g = row.querySelector('.kp-interna-nueva-g');
                if (g && String(g.value).trim() === String(norm.rutaGrande).trim()) hit = row;
            });
        }
        if (hit) hit.remove();
    }
    function kpDescartarInternaNuevaSeleccion(pair, ev) {
        if (ev) {
            ev.preventDefault();
            ev.stopPropagation();
        }
        const ix = kpIndiceSeleccionInternaNueva(pair);
        if (ix === -1) return;
        __kpInternaNuevaSelOrden.splice(ix, 1);
        kpQuitarFilaInternaNuevaPorPair(pair);
        kpRenderGaleriaInternaNueva();
    }
    function kpRenderGaleriaInternaNueva() {
        const wrap = document.getElementById('galeria-imgs-interna-nueva');
        const vac = document.getElementById('galeria-imgs-interna-nueva-vacio');
        if (!wrap) return;
        const pairs = kpRecolectarParesImagenesDisponiblesProducto();
        wrap.innerHTML = '';
        if (!pairs.length) {
            if (vac) vac.classList.remove('hidden');
            return;
        }
        if (vac) vac.classList.add('hidden');
        const urlFn =
            typeof window.kpUrlVistaDesdeRutaAlmacen === 'function'
                ? window.kpUrlVistaDesdeRutaAlmacen
                : function() {
                      return '';
                  };
        pairs.forEach(function(pair) {
            const seleccionada = kpIndiceSeleccionInternaNueva(pair) !== -1;
            const src = urlFn(pair.thumbVisual || pair.rutaPequena || pair.rutaGrande);
            const cell = document.createElement('div');
            cell.className = 'min-w-0 w-full';
            if (seleccionada) {
                cell.appendChild(
                    window.kpCrearCeldaGaleriaInternaMarcada(src, 'Quitar de las imágenes del producto', function(e) {
                        kpDescartarInternaNuevaSeleccion(pair, e);
                    })
                );
            } else {
                cell.appendChild(
                    window.kpCrearCeldaGaleriaInternaLibre(src, function() {
                        kpToggleInternaNuevaSeleccion(pair);
                    })
                );
            }
            wrap.appendChild(cell);
        });
    }
    function kpAddFilaInternaNuevaProducto(pairOrNull) {
        kpBindFilasInternaNuevaDelegation();
        const cont = document.getElementById('filas-interna-nueva-producto');
        if (!cont) return;
        const idn = 'kpin' + (++__kpInternaNuevaRowInc);
        let gVal = '';
        let pVal = '';
        let b64 = '';
        if (pairOrNull) {
            const norm = kpNormalizarParImagenProd(pairOrNull.rutaGrande, pairOrNull.rutaPequena);
            gVal = norm.rutaGrande;
            pVal = norm.rutaPequena;
            b64 = kpStableB64KeyInterna(kpPairKeyInternaProd(norm));
        }
        const row = document.createElement('div');
        row.className = 'kp-fila-interna-nueva border border-gray-200 dark:border-gray-600 rounded-lg p-3 space-y-2';
        if (b64) row.dataset.pairKey = b64;
        row.innerHTML =
            '<div class="flex flex-wrap items-start gap-3">' +
            '<div class="flex-1 min-w-[200px]"><label class="block mb-1 text-xs text-gray-600 dark:text-gray-400">Ruta grande</label>' +
            '<input type="text" id="' + idn + '-g" autocomplete="off" class="kp-interna-nueva-g w-full px-3 py-2 rounded bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white border text-sm"></div>' +
            '<div class="shrink-0"><span class="block mb-1 text-[10px] text-gray-500 dark:text-gray-400">Vista previa</span>' +
            '<img id="' + idn + '-pg" alt="" class="kp-interna-nueva-pg hidden w-9 h-9 object-cover rounded border border-gray-300 dark:border-gray-600"></div></div>' +
            '<div class="flex flex-wrap items-start gap-3">' +
            '<div class="flex-1 min-w-[200px]"><label class="block mb-1 text-xs text-gray-600 dark:text-gray-400">Ruta pequeña</label>' +
            '<input type="text" id="' + idn + '-p" autocomplete="off" class="kp-interna-nueva-p w-full px-3 py-2 rounded bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white border text-sm"></div>' +
            '<div class="shrink-0"><span class="block mb-1 text-[10px] text-gray-500 dark:text-gray-400">Vista previa</span>' +
            '<img id="' + idn + '-pp" alt="" class="kp-interna-nueva-pp hidden w-9 h-9 object-cover rounded border border-gray-300 dark:border-gray-600"></div></div>';
        cont.appendChild(row);
        row.querySelector('.kp-interna-nueva-g').value = gVal;
        row.querySelector('.kp-interna-nueva-p').value = pVal;
        if (typeof window.kpActualizarPreviewInputInterno === 'function') {
            window.kpActualizarPreviewInputInterno(idn + '-g', idn + '-pg');
            window.kpActualizarPreviewInputInterno(idn + '-p', idn + '-pp');
        }
    }
    function kpToggleInternaNuevaSeleccion(pair) {
        const norm = kpNormalizarParImagenProd(pair.rutaGrande, pair.rutaPequena);
        const ix = kpIndiceSeleccionInternaNueva(pair);
        if (ix === -1) {
            __kpInternaNuevaSelOrden.push(norm);
            kpAddFilaInternaNuevaProducto(norm);
        } else {
            __kpInternaNuevaSelOrden.splice(ix, 1);
            kpQuitarFilaInternaNuevaPorPair(pair);
        }
        kpRenderGaleriaInternaNueva();
        if (typeof window.kpInternaGlobalRefrescarSeleccion === 'function') {
            window.kpInternaGlobalRefrescarSeleccion('nueva');
        }
    }
    window.kpToggleInternaNuevaSeleccion = kpToggleInternaNuevaSeleccion;
    window.kpIndiceSeleccionInternaNueva = kpIndiceSeleccionInternaNueva;
    window.kpCargarInternaNuevaDesdeProducto = kpCargarInternaNuevaDesdeProducto;
    window.kpAsegurarInternaNuevaInicializada = function() {
        const cont = document.getElementById('filas-interna-nueva-producto');
        const tieneFilas = cont && cont.children.length > 0;
        if (!tieneFilas && __kpInternaNuevaSelOrden.length === 0) {
            kpCargarInternaNuevaDesdeProducto();
        }
    };
    window.kpObtenerParesSeleccionInternaNueva = function() {
        return __kpInternaNuevaSelOrden.slice();
    };
    
    // Renderizar las imágenes en el contenedor
    function renderizarImagenes() {
        const container = document.getElementById('imagenes-container');
        container.innerHTML = '';
        
        if (imagenesGrandes.length === 0) {
            return;
        }
        
        imagenesGrandes.forEach((imgGrande, index) => {
            const imgPequena = imagenesPequenas[index] || '';
            if (!imgGrande && !imgPequena) return; // Saltar si ambas están vacías
            const pendiente = typeof imgGrande === 'string' && imgGrande.indexOf(KP_PENDING_PRD) === 0;

            const div = document.createElement('div');
            div.className = 'relative group imagen-miniatura cursor-pointer';
            div.dataset.index = index;
            div.draggable = !pendiente;
            
            // Si es la primera (índice 0), marcar como principal con borde naranja
            if (index === 0) {
                div.classList.add('border-4', 'border-orange-500', 'rounded-lg');
            } else {
                div.classList.add('border', 'border-gray-300', 'dark:border-gray-600', 'rounded-lg');
            }
            
            const imgSrc = imgPequena || imgGrande;
            const imgUrl = (!pendiente && imgSrc) ? `{{ asset('images/') }}/${imgSrc}` : '';
            const uidPrd = pendiente ? imgGrande.slice(KP_PENDING_PRD.length) : '';

            div.innerHTML = pendiente
                ? `
                ${index === 0 ? '<div class="absolute -left-7 top-0 bottom-0 flex items-center justify-center z-10"><span class="writing-vertical text-xs font-semibold text-orange-500 bg-white dark:bg-gray-800 px-1 py-1 rounded shadow-sm border border-orange-200 dark:border-orange-700">Principal</span></div>' : ''}
                <div class="w-20 h-20 flex flex-col items-center justify-center bg-gray-100 dark:bg-gray-700 rounded p-1 pointer-events-none">
                    <span class="text-[10px] text-gray-600 dark:text-gray-300 text-center leading-tight">Cargando imagen…</span>
                    <div class="w-full mt-1 h-1.5 bg-gray-200 dark:bg-gray-600 rounded overflow-hidden">
                        <div id="kp-prog-prd-${uidPrd}" class="h-full bg-blue-500 transition-[width] duration-150" style="width:0%"></div>
                    </div>
                </div>
                <button type="button" class="absolute top-0 right-0 bg-red-600 hover:bg-red-700 text-white rounded-full w-7 h-7 flex items-center justify-center font-bold text-base opacity-0 group-hover:opacity-100 transition-opacity btn-eliminar-imagen shadow-lg" style="transform: translate(25%, -25%); z-index: 30;" data-index="${index}" title="Eliminar">
                    ×
                </button>
            `
                : `
                ${index === 0 ? '<div class="absolute -left-7 top-0 bottom-0 flex items-center justify-center z-10"><span class="writing-vertical text-xs font-semibold text-orange-500 bg-white dark:bg-gray-800 px-1 py-1 rounded shadow-sm border border-orange-200 dark:border-orange-700">Principal</span></div>' : ''}
                <img src="${imgUrl}" 
                     alt="Imagen ${index + 1}" 
                     class="w-20 h-20 object-cover rounded block pointer-events-none"
                     onerror="this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'80\' height=\'80\'%3E%3Crect width=\'80\' height=\'80\' fill=\'%23ddd\'/%3E%3Ctext x=\'50%25\' y=\'50%25\' text-anchor=\'middle\' dy=\'.3em\' fill=\'%23999\' font-size=\'12\'%3EImagen%3C/text%3E%3C/svg%3E';">
                <button type="button" class="absolute top-0 right-0 bg-red-600 hover:bg-red-700 text-white rounded-full w-7 h-7 flex items-center justify-center font-bold text-base opacity-0 group-hover:opacity-100 transition-opacity btn-eliminar-imagen shadow-lg" style="transform: translate(25%, -25%); z-index: 30;" data-index="${index}" title="Eliminar">
                    ×
                </button>
            `;
            
            // Añadir evento click al div para abrir modal de edición
            div.addEventListener('click', (e) => {
                // No abrir modal si se hizo clic en el botón de eliminar
                if (e.target.closest('.btn-eliminar-imagen')) {
                    return;
                }
                if (pendiente) return;
                // Si se hizo drag, no abrir modal
                if (wasDragged) {
                    wasDragged = false;
                    return;
                }
                abrirModalEditar(index);
            });
            
            container.appendChild(div);
        });
        
        configurarDragAndDrop();
        configurarEventos();
        actualizarCamposOcultos();
    }
    
    // Configurar drag and drop
    function configurarDragAndDrop() {
        const container = document.getElementById('imagenes-container');
        const miniaturas = container.querySelectorAll('.imagen-miniatura');
        let elementoArrastrado = null;
        
        miniaturas.forEach(miniatura => {
            miniatura.addEventListener('dragstart', (e) => {
                elementoArrastrado = miniatura;
                wasDragged = true; // Marcar que se hizo drag
                miniatura.style.opacity = '0.5';
                e.dataTransfer.effectAllowed = 'move';
            });
            
            miniatura.addEventListener('dragend', () => {
                if (miniatura) miniatura.style.opacity = '1';
                elementoArrastrado = null;
                // Resetear flag después de un breve delay para permitir que el click se ignore
                setTimeout(() => {
                    wasDragged = false;
                }, 100);
            });
            
            miniatura.addEventListener('dragover', (e) => {
                e.preventDefault();
                if (!elementoArrastrado || elementoArrastrado === miniatura) return;
                
                const allMiniaturas = Array.from(container.querySelectorAll('.imagen-miniatura'));
                const draggedIndex = allMiniaturas.indexOf(elementoArrastrado);
                const targetIndex = allMiniaturas.indexOf(miniatura);
                
                if (draggedIndex < targetIndex) {
                    container.insertBefore(elementoArrastrado, miniatura.nextSibling);
                } else {
                    container.insertBefore(elementoArrastrado, miniatura);
                }
            });
            
            miniatura.addEventListener('drop', (e) => {
                e.preventDefault();
                if (elementoArrastrado) {
                    // Reordenar arrays basándose en el nuevo orden del DOM
                    const allMiniaturas = Array.from(container.querySelectorAll('.imagen-miniatura'));
                    const nuevasGrandes = [];
                    const nuevasPequenas = [];
                    
                    allMiniaturas.forEach(m => {
                        const index = parseInt(m.dataset.index);
                        nuevasGrandes.push(imagenesGrandes[index]);
                        nuevasPequenas.push(imagenesPequenas[index]);
                    });
                    
                    imagenesGrandes = nuevasGrandes;
                    imagenesPequenas = nuevasPequenas;
                    
                    renderizarImagenes();
                }
            });
        });
    }
    
    // Configurar eventos de botones
    function configurarEventos() {
        // Botones de eliminar
        document.querySelectorAll('.btn-eliminar-imagen').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                e.preventDefault();
                const index = parseInt(btn.dataset.index);
                eliminarImagen(index);
            });
        });
    }
    
    // Eliminar imagen
    function eliminarImagen(index) {
        if (!confirm('¿Estás seguro de que quieres eliminar esta imagen?')) {
            return;
        }
        const g = imagenesGrandes[index];
        if (typeof g === 'string' && g.indexOf(KP_PENDING_PRD) === 0) {
            const id = g.slice(KP_PENDING_PRD.length);
            const x = uploadsPendientesProducto.get(id);
            if (x) {
                if (x.xhrG) try { x.xhrG.abort(); } catch (e) {}
                if (x.xhrP) try { x.xhrP.abort(); } catch (e) {}
                uploadsPendientesProducto.delete(id);
            }
        }
        imagenesGrandes.splice(index, 1);
        imagenesPequenas.splice(index, 1);
        renderizarImagenes();
    }
    
    // Abrir modal de edición
    function abrirModalEditar(index) {
        imagenEditandoIndex = index;
        const modal = document.getElementById('modal-editar-imagen');
        const imgGrande = document.getElementById('imagen-grande-editar');
        const inputGrande = document.getElementById('input-grande-editar');
        const inputPequena = document.getElementById('input-pequena-editar');
        
        const rutaGrande = imagenesGrandes[index] || '';
        const rutaPequena = imagenesPequenas[index] || '';
        
        imgGrande.src = rutaGrande ? `{{ asset('images/') }}/${rutaGrande}` : '';
        inputGrande.value = rutaGrande;
        inputPequena.value = rutaPequena;
        
        modal.classList.remove('hidden');
    }
    
    // Cerrar modal de edición
    window.cerrarModalEditarImagen = function() {
        document.getElementById('modal-editar-imagen').classList.add('hidden');
        imagenEditandoIndex = null;
    };
    
    // Guardar edición
    document.getElementById('btn-guardar-edicion').addEventListener('click', () => {
        const inputGrande = document.getElementById('input-grande-editar');
        const inputPequena = document.getElementById('input-pequena-editar');
        
        if (imagenEditandoIndex !== null) {
            imagenesGrandes[imagenEditandoIndex] = inputGrande.value.trim();
            imagenesPequenas[imagenEditandoIndex] = inputPequena.value.trim();
            renderizarImagenes();
            cerrarModalEditarImagen();
        }
    });
    
    // Configurar buscadores de imágenes en modal de edición
    document.getElementById('buscar-imagen-grande-editar').addEventListener('click', () => {
        const input = document.getElementById('input-grande-editar');
        const preview = document.getElementById('imagen-grande-editar');
        if (input.value.trim()) {
            preview.src = `{{ asset('images/') }}/${input.value.trim()}`;
        }
    });
    
    document.getElementById('buscar-imagen-pequena-editar').addEventListener('click', () => {
        // Solo actualizar si hay cambios, la vista previa ya muestra la grande
    });
    
    // Botón añadir imagen
    document.getElementById('btn-añadir-imagen').addEventListener('click', () => {
        abrirModalAñadir();
    });
    
    // Abrir modal añadir
    function abrirModalAñadir() {
        const modal = document.getElementById('modal-añadir-imagen');
        modal.classList.remove('hidden');
        cargarCarpetasModal();
        cambiarTabModal('url');
    }
    
    // Cerrar modal añadir
    window.cerrarModalAñadirImagen = function() {
        document.getElementById('modal-añadir-imagen').classList.add('hidden');
        limpiarModalAñadir();
    };

    function prepararModalAñadirImagenParaOtraProducto(tab) {
        document.getElementById('modal-añadir-imagen').classList.remove('hidden');
        limpiarModalAñadir({ mantenerCarpetas: true });
        cambiarTabModal(tab || 'url');
    }
    
    // Limpiar modal añadir
    function limpiarModalAñadir(opciones) {
        opciones = opciones || {};
        const mantener = !!opciones.mantenerCarpetas;
        if (!mantener) {
            document.getElementById('carpeta-subir-nueva').value = '';
            document.getElementById('carpeta-url-nueva').value = '';
            document.getElementById('carpeta-amazon-nueva').value = '';
        }
        document.getElementById('file-subir-nueva').value = '';
        document.getElementById('url-imagen-nueva').value = '';
        document.getElementById('url-amazon-nueva').value = '';
        document.getElementById('nombre-archivo-nueva').textContent = '';
        document.getElementById('error-url-nueva').classList.add('hidden');
        document.getElementById('error-amazon-nueva').classList.add('hidden');
        document.getElementById('loading-amazon-nueva').classList.add('hidden');
        document.getElementById('imagenes-amazon-nueva').classList.add('hidden');
        document.getElementById('grid-imagenes-amazon-nueva').innerHTML = '';
        document.getElementById('area-recorte-nueva').classList.add('hidden');
        document.getElementById('area-recorte-amazon-nueva').classList.add('hidden');
        imagenesAmazonSeleccionadas = [];
        resetEstadoRecorteAmazonNueva();
        if (cropperNueva) {
            cropperNueva.destroy();
            cropperNueva = null;
        }
        kpLimpiarInternaNuevaProductoUI();
    }
    
    // Cambiar pestañas del modal
    function cambiarTabModal(tab) {
        const tabSubir = document.getElementById('tab-subir-nueva');
        const tabUrl = document.getElementById('tab-url-nueva');
        const tabAmazon = document.getElementById('tab-amazon-nueva');
        const tabInterna = document.getElementById('tab-interna-nueva');
        const tabInternaGlobal = document.getElementById('tab-interna-global-nueva');
        const contentSubir = document.getElementById('content-subir-nueva');
        const contentUrl = document.getElementById('content-url-nueva');
        const contentAmazon = document.getElementById('content-amazon-nueva');
        const contentInterna = document.getElementById('content-interna-nueva');
        const contentInternaGlobal = document.getElementById('content-interna-global-nueva');
        const allTabs = [tabUrl, tabSubir, tabAmazon, tabInterna, tabInternaGlobal];

        [contentUrl, contentSubir, contentAmazon, contentInterna, contentInternaGlobal].forEach(c => {
            if (c) c.classList.add('hidden');
        });
        
        if (tab === 'url') {
            window.kpModalImgTabSetActive(allTabs, tabUrl);
            contentUrl.classList.remove('hidden');
        } else if (tab === 'subir') {
            window.kpModalImgTabSetActive(allTabs, tabSubir);
            contentSubir.classList.remove('hidden');
        } else if (tab === 'amazon') {
            window.kpModalImgTabSetActive(allTabs, tabAmazon);
            contentAmazon.classList.remove('hidden');
            cargarCarpetasModal(); // Cargar carpetas cuando se abre la pestaña Amazon
        } else if (tab === 'interna' && tabInterna && contentInterna) {
            window.kpModalImgTabSetActive(allTabs, tabInterna);
            contentInterna.classList.remove('hidden');
            kpBindFilasInternaNuevaDelegation();
            kpCargarInternaNuevaDesdeProducto();
        } else if (tab === 'interna-global' && tabInternaGlobal && contentInternaGlobal) {
            window.kpModalImgTabSetActive(allTabs, tabInternaGlobal);
            contentInternaGlobal.classList.remove('hidden');
            if (typeof window.kpAsegurarInternaNuevaInicializada === 'function') {
                window.kpAsegurarInternaNuevaInicializada();
            }
            if (typeof window.kpInternaGlobalAlActivar === 'function') {
                window.kpInternaGlobalAlActivar('nueva');
            }
        }
    }
    
    document.getElementById('tab-subir-nueva').addEventListener('click', () => cambiarTabModal('subir'));
    document.getElementById('tab-url-nueva').addEventListener('click', () => cambiarTabModal('url'));
    document.getElementById('tab-amazon-nueva').addEventListener('click', () => cambiarTabModal('amazon'));
    const tabInternaNuevaEl = document.getElementById('tab-interna-nueva');
    if (tabInternaNuevaEl) tabInternaNuevaEl.addEventListener('click', () => cambiarTabModal('interna'));
    const tabInternaGlobalNuevaEl = document.getElementById('tab-interna-global-nueva');
    if (tabInternaGlobalNuevaEl) tabInternaGlobalNuevaEl.addEventListener('click', () => cambiarTabModal('interna-global'));
    const btnInternaNuevaFilaVacia = document.getElementById('btn-interna-nueva-anadir-fila-vacia');
    if (btnInternaNuevaFilaVacia) {
        btnInternaNuevaFilaVacia.addEventListener('click', function() {
            kpAddFilaInternaNuevaProducto(null);
        });
    }
    
    // Cargar carpetas en modales
    function cargarCarpetasModal() {
        fetch('{{ route("admin.imagenes.carpetas") }}')
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data.length > 0) {
                    actualizarSelectCarpetas('carpeta-subir-nueva', data.data);
                    actualizarSelectCarpetas('carpeta-url-nueva', data.data);
                    actualizarSelectCarpetas('carpeta-amazon-nueva', data.data);
                }
            })
            .catch(error => console.error('Error al cargar carpetas:', error));
    }
    
    function actualizarSelectCarpetas(selectId, carpetas) {
        const select = document.getElementById(selectId);
        if (!select) return;
        
        const primeraOpcion = select.querySelector('option[value=""]') || document.createElement('option');
        primeraOpcion.value = '';
        primeraOpcion.textContent = 'Selecciona una carpeta';
        select.innerHTML = '';
        select.appendChild(primeraOpcion);
        
        let carpetaProductoExiste = false;
        carpetas.forEach(carpeta => {
            const option = document.createElement('option');
            option.value = carpeta;
            option.textContent = carpeta.charAt(0).toUpperCase() + carpeta.slice(1);
            select.appendChild(option);
            // Verificar si existe la carpeta "producto" (case insensitive)
            if (carpeta.toLowerCase() === 'producto') {
                carpetaProductoExiste = true;
            }
        });
        
        // Si existe la carpeta "producto", seleccionarla por defecto
        if (carpetaProductoExiste) {
            select.value = 'producto';
        }
    }

    // ========== FUNCIONALIDAD AMAZON PARA MODAL PRINCIPAL ==========
    let imagenesAmazonSeleccionadas = [];
    let cropperAmazonNueva = null;
    let colaRecorteAmazonNueva = [];
    let totalRecorteAmazonNueva = 0;
    let carpetaActualAmazonNueva = '';
    let modoRecorteAmazonNueva = false;

    function resetEstadoRecorteAmazonNueva() {
        colaRecorteAmazonNueva = [];
        totalRecorteAmazonNueva = 0;
        carpetaActualAmazonNueva = '';
        modoRecorteAmazonNueva = false;
        const area = document.getElementById('area-recorte-amazon-nueva');
        const busqueda = document.getElementById('amazon-busqueda-nueva');
        if (area) area.classList.add('hidden');
        if (busqueda) busqueda.classList.remove('hidden');
        if (cropperAmazonNueva) {
            cropperAmazonNueva.destroy();
            cropperAmazonNueva = null;
        }
    }

    function mostrarRecorteAmazonNueva(urlImagen) {
        const areaRecorte = document.getElementById('area-recorte-amazon-nueva');
        const img = document.getElementById('imagen-recortar-amazon-nueva');
        areaRecorte.classList.remove('hidden');
        if (cropperAmazonNueva) cropperAmazonNueva.destroy();
        const urlProxy = urlImagen.startsWith('http')
            ? `{{ route('admin.imagenes.proxy') }}?url=${encodeURIComponent(urlImagen)}`
            : urlImagen;
        img.crossOrigin = 'anonymous';
        img.src = urlProxy;
        img.onload = function() {
            if (cropperAmazonNueva) cropperAmazonNueva.destroy();
            cropperAmazonNueva = new Cropper(img, {
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
                toggleDragModeOnDblclick: false,
            });
        };
        img.onerror = function() {
            document.getElementById('error-amazon-nueva').textContent = 'Error al cargar la imagen desde Amazon.';
            document.getElementById('error-amazon-nueva').classList.remove('hidden');
            colaRecorteAmazonNueva.shift();
            mostrarSiguienteRecorteAmazonNueva();
        };
    }

    function mostrarSiguienteRecorteAmazonNueva() {
        if (!colaRecorteAmazonNueva.length) {
            resetEstadoRecorteAmazonNueva();
            prepararModalAñadirImagenParaOtraProducto('amazon');
            cargarCarpetasModal();
            return;
        }
        const actual = totalRecorteAmazonNueva - colaRecorteAmazonNueva.length + 1;
        const progreso = document.getElementById('progreso-recorte-amazon-nueva');
        if (progreso) progreso.textContent = `Recortando imagen ${actual} de ${totalRecorteAmazonNueva}`;
        mostrarRecorteAmazonNueva(colaRecorteAmazonNueva[0].url);
    }

    function iniciarColaRecorteAmazonNueva(carpeta) {
        colaRecorteAmazonNueva = imagenesAmazonSeleccionadas.slice();
        totalRecorteAmazonNueva = colaRecorteAmazonNueva.length;
        carpetaActualAmazonNueva = carpeta;
        modoRecorteAmazonNueva = true;
        document.getElementById('imagenes-amazon-nueva').classList.add('hidden');
        const busqueda = document.getElementById('amazon-busqueda-nueva');
        if (busqueda) busqueda.classList.add('hidden');
        document.getElementById('error-amazon-nueva').classList.add('hidden');
        mostrarSiguienteRecorteAmazonNueva();
    }

    async function procesarRecorteAmazonActualNueva() {
        if (!cropperAmazonNueva || !carpetaActualAmazonNueva) {
            alert('Error al recortar la imagen.');
            return;
        }
        const canvasOriginal = cropperAmazonNueva.getCroppedCanvas({
            imageSmoothingEnabled: true,
            imageSmoothingQuality: 'high'
        });
        if (!canvasOriginal) {
            alert('Error al recortar la imagen');
            return;
        }
        colaRecorteAmazonNueva.shift();
        const carpetaUp = carpetaActualAmazonNueva;
        const slugInput = document.querySelector('input[name="slug"]');
        const nombreBase = slugInput ? slugInput.value.trim() : 'imagen';
        const uploadId = Date.now().toString(36) + Math.random().toString(36).slice(2, 9);
        const pendingPath = KP_PENDING_PRD + uploadId;
        imagenesGrandes.push(pendingPath);
        imagenesPequenas.push(pendingPath);
        renderizarImagenes();

        try {
            const canvasGrande = document.createElement('canvas');
            canvasGrande.width = canvasOriginal.width;
            canvasGrande.height = canvasOriginal.height;
            const ctxGrande = canvasGrande.getContext('2d');
            ctxGrande.fillStyle = '#ffffff';
            ctxGrande.fillRect(0, 0, canvasGrande.width, canvasGrande.height);
            ctxGrande.drawImage(canvasOriginal, 0, 0);
            const canvasPequena = document.createElement('canvas');
            canvasPequena.width = 300;
            canvasPequena.height = 250;
            const ctxPequena = canvasPequena.getContext('2d');
            ctxPequena.fillStyle = '#ffffff';
            ctxPequena.fillRect(0, 0, canvasPequena.width, canvasPequena.height);
            ctxPequena.drawImage(canvasOriginal, 0, 0, 300, 250);
            const blobGrande = await new Promise(function(resolve, reject) {
                canvasGrande.toBlob(function(b) { b ? resolve(b) : reject(new Error('Error grande')); }, 'image/webp', 0.9);
            });
            const blobPequena = await new Promise(function(resolve, reject) {
                canvasPequena.toBlob(function(b) { b ? resolve(b) : reject(new Error('Error pequeña')); }, 'image/webp', 0.9);
            });
            const timestamp = Date.now();
            const formDataGrande = new FormData();
            formDataGrande.append('imagen', blobGrande, `${nombreBase}-${timestamp}.webp`);
            formDataGrande.append('carpeta', carpetaUp);
            formDataGrande.append('_token', '{{ csrf_token() }}');
            const formDataPequena = new FormData();
            formDataPequena.append('imagen', blobPequena, `${nombreBase}-${timestamp}-thumbnail.webp`);
            formDataPequena.append('carpeta', carpetaUp);
            formDataPequena.append('_token', '{{ csrf_token() }}');

            (async function() {
                try {
                    const onProg = function(pct) {
                        const el = document.getElementById('kp-prog-prd-' + uploadId);
                        if (el) el.style.width = pct + '%';
                    };
                    const { dataG, dataP } = await window.__kpSubirParejaConProgreso(
                        '{{ route("admin.imagenes.subir-simple") }}',
                        formDataGrande,
                        formDataPequena,
                        '{{ csrf_token() }}',
                        onProg,
                        uploadsPendientesProducto,
                        uploadId
                    );
                    if (dataG.success && dataP.success) {
                        const ix = imagenesGrandes.indexOf(pendingPath);
                        if (ix !== -1) {
                            imagenesGrandes[ix] = dataG.data.ruta_relativa;
                            imagenesPequenas[ix] = dataP.data.ruta_relativa;
                        }
                        renderizarImagenes();
                    } else {
                        throw new Error(dataG.message || dataP.message || 'Error al subir');
                    }
                } catch (error) {
                    console.error('Error al procesar imagen Amazon:', error);
                    quitarPlaceholderProducto(pendingPath);
                    alert('Error al subir una imagen de Amazon: ' + error.message);
                }
            })();
        } catch (error) {
            console.error('Error al procesar imagen Amazon:', error);
            quitarPlaceholderProducto(pendingPath);
            alert('Error al procesar la imagen: ' + error.message);
        }

        mostrarSiguienteRecorteAmazonNueva();
    }
    
    // Limpiar URL de Amazon automáticamente al pegar o escribir (usa limpiarUrlAmazonViaApi definido en modal sublínea)
    const urlAmazonInputNueva = document.getElementById('url-amazon-nueva');
    if (urlAmazonInputNueva && typeof limpiarUrlAmazonViaApi === 'function') {
        urlAmazonInputNueva.addEventListener('paste', function(e) {
            setTimeout(async () => {
                const urlPegada = urlAmazonInputNueva.value.trim();
                if (urlPegada) {
                    const urlLimpia = await limpiarUrlAmazonViaApi(urlPegada);
                    if (urlLimpia !== urlPegada) urlAmazonInputNueva.value = urlLimpia;
                }
            }, 10);
        });
        urlAmazonInputNueva.addEventListener('input', function(e) {
            const url = e.target.value.trim();
            if (!url || !url.includes('amazon')) return;
            limpiarUrlAmazonViaApi(url).then(urlLimpia => {
                if (urlLimpia !== url && urlLimpia.length < url.length) e.target.value = urlLimpia;
            });
        });
    }
    
    // Buscar imágenes de Amazon
    document.getElementById('btn-buscar-amazon-nueva').addEventListener('click', async () => {
        const urlInput = document.getElementById('url-amazon-nueva');
        const errorDiv = document.getElementById('error-amazon-nueva');
        const loadingDiv = document.getElementById('loading-amazon-nueva');
        const imagenesDiv = document.getElementById('imagenes-amazon-nueva');
        const gridDiv = document.getElementById('grid-imagenes-amazon-nueva');
        
        const url = urlInput.value.trim();
        if (!url) {
            errorDiv.textContent = 'Por favor, introduce una URL de Amazon';
            errorDiv.classList.remove('hidden');
            return;
        }
        
        errorDiv.classList.add('hidden');
        loadingDiv.classList.remove('hidden');
        imagenesDiv.classList.add('hidden');
        gridDiv.innerHTML = '';
        imagenesAmazonSeleccionadas = [];
        
        try {
            const response = await fetch('{{ route("admin.productos.obtener-imagenes-amazon") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ url: url })
            });
            
            const data = await response.json();
            
            if (!data.success) {
                throw new Error(data.error || 'Error al obtener imágenes');
            }
            
            if (!data.imagenes || data.imagenes.length === 0) {
                errorDiv.textContent = 'No se encontraron imágenes para este producto';
                errorDiv.classList.remove('hidden');
                loadingDiv.classList.add('hidden');
                return;
            }
            
            // Mostrar imágenes
            data.imagenes.forEach((imagen, index) => {
                const div = document.createElement('div');
                div.className = 'relative border-2 border-gray-300 dark:border-gray-600 rounded-lg overflow-hidden cursor-pointer hover:border-blue-500 transition-colors';
                div.dataset.index = index;
                
                const img = document.createElement('img');
                img.src = imagen.url;
                img.className = 'w-full h-32 object-contain transition-transform duration-200';
                img.alt = `Imagen ${index + 1}`;
                img.dataset.originalClass = 'w-full h-32 object-contain transition-transform duration-200';
                
                // Contenedor para checkbox y etiqueta de tamaño
                const checkboxContainer = document.createElement('div');
                checkboxContainer.className = 'absolute top-2 right-2 flex items-center gap-1';
                
                const checkbox = document.createElement('input');
                checkbox.type = 'checkbox';
                checkbox.className = 'w-5 h-5';
                checkbox.dataset.index = index;
                
                // Etiqueta de tamaño (L, M, S)
                const sizeLabel = document.createElement('span');
                const sizeText = imagen.size === 'large' ? 'L' : (imagen.size === 'medium' ? 'M' : 'S');
                sizeLabel.textContent = sizeText;
                sizeLabel.className = 'text-xs font-semibold bg-blue-500 text-white px-1.5 py-0.5 rounded';
                sizeLabel.style.display = 'inline-block';
                
                checkboxContainer.appendChild(checkbox);
                checkboxContainer.appendChild(sizeLabel);
                
                div.appendChild(img);
                div.appendChild(checkboxContainer);
                
                // Funcionalidad de agrandar con espacio
                let espacioPresionado = false;
                let ratonEncima = false;
                
                div.addEventListener('mouseenter', () => {
                    ratonEncima = true;
                });
                
                div.addEventListener('mouseleave', () => {
                    ratonEncima = false;
                    if (espacioPresionado) {
                        img.className = img.dataset.originalClass;
                        espacioPresionado = false;
                    }
                });
                
                // Listener global para la tecla espacio
                const espacioKeydown = (e) => {
                    if (e.code === 'Space' && ratonEncima && !espacioPresionado) {
                        e.preventDefault();
                        espacioPresionado = true;
                        img.className = 'w-full max-h-[80vh] object-contain transition-transform duration-200 z-50';
                        div.style.zIndex = '50';
                        div.style.position = 'relative';
                    }
                };
                
                const espacioKeyup = (e) => {
                    if (e.code === 'Space' && espacioPresionado) {
                        e.preventDefault();
                        espacioPresionado = false;
                        img.className = img.dataset.originalClass;
                        div.style.zIndex = '';
                    }
                };
                
                document.addEventListener('keydown', espacioKeydown);
                document.addEventListener('keyup', espacioKeyup);
                
                div.addEventListener('click', (e) => {
                    if (e.target !== checkbox && e.target !== sizeLabel) {
                        checkbox.checked = !checkbox.checked;
                    }
                    actualizarSeleccionAmazon(checkbox.checked, index, imagen);
                });
                
                checkbox.addEventListener('change', (e) => {
                    actualizarSeleccionAmazon(e.target.checked, index, imagen);
                });
                
                gridDiv.appendChild(div);
            });
            
            imagenesDiv.classList.remove('hidden');
            loadingDiv.classList.add('hidden');
            
        } catch (error) {
            console.error('Error:', error);
            errorDiv.textContent = error.message || 'Error al obtener imágenes de Amazon';
            errorDiv.classList.remove('hidden');
            loadingDiv.classList.add('hidden');
        }
    });
    
    function actualizarSeleccionAmazon(seleccionada, index, imagen) {
        if (seleccionada) {
            if (!imagenesAmazonSeleccionadas.find(img => img.url === imagen.url)) {
                imagenesAmazonSeleccionadas.push(imagen);
            }
        } else {
            imagenesAmazonSeleccionadas = imagenesAmazonSeleccionadas.filter(img => img.url !== imagen.url);
        }
    }
    
    // Guardar imágenes seleccionadas de Amazon
    document.getElementById('btn-guardar-nueva').addEventListener('click', async () => {
        const tabActiva = document.querySelector('.tab-modal.kp-modal-img-tab--active');
        if (!tabActiva || tabActiva.id !== 'tab-amazon-nueva') {
            return; // No es la pestaña de Amazon, dejar que el código existente maneje el guardado
        }

        if (modoRecorteAmazonNueva) {
            await procesarRecorteAmazonActualNueva();
            return;
        }
        
        if (imagenesAmazonSeleccionadas.length === 0) {
            alert('Por favor, selecciona al menos una imagen');
            return;
        }
        
        const carpetaSelect = document.getElementById('carpeta-amazon-nueva');
        const carpeta = carpetaSelect.value;
        if (!carpeta) {
            alert('Por favor, selecciona una carpeta');
            return;
        }

        iniciarColaRecorteAmazonNueva(carpeta);
    });
    
    // Configurar subida de archivo
    const fileInputNueva = document.getElementById('file-subir-nueva');
    const btnSeleccionarNueva = document.getElementById('btn-seleccionar-nueva');
    const dropZoneNueva = document.getElementById('drop-zone-nueva');
    
    btnSeleccionarNueva.addEventListener('click', () => fileInputNueva.click());
    
    dropZoneNueva.addEventListener('click', () => fileInputNueva.click());
    dropZoneNueva.addEventListener('dragover', (e) => {
        e.preventDefault();
        dropZoneNueva.classList.add('border-blue-500', 'bg-blue-50', 'dark:bg-blue-900/20');
    });
    dropZoneNueva.addEventListener('dragleave', () => {
        dropZoneNueva.classList.remove('border-blue-500', 'bg-blue-50', 'dark:bg-blue-900/20');
    });
    dropZoneNueva.addEventListener('drop', (e) => {
        e.preventDefault();
        dropZoneNueva.classList.remove('border-blue-500', 'bg-blue-50', 'dark:bg-blue-900/20');
        if (e.dataTransfer.files.length > 0) {
            procesarArchivoNuevo(e.dataTransfer.files[0]);
        }
    });
    
    fileInputNueva.addEventListener('change', (e) => {
        if (e.target.files.length > 0) {
            procesarArchivoNuevo(e.target.files[0]);
        }
    });
    
    function quitarPlaceholderProducto(pendingPath) {
        const ix = imagenesGrandes.indexOf(pendingPath);
        if (ix !== -1) {
            imagenesGrandes.splice(ix, 1);
            imagenesPequenas.splice(ix, 1);
            renderizarImagenes();
        }
    }

    // Procesar archivo nuevo (subida en segundo plano)
    function procesarArchivoNuevo(file) {
        const carpeta = document.getElementById('carpeta-subir-nueva').value;
        if (!carpeta) {
            alert('Por favor selecciona una carpeta primero.');
            return;
        }
        if (!file.type.startsWith('image/')) {
            alert('Por favor selecciona un archivo de imagen válido.');
            return;
        }
        if (file.size > 5 * 1024 * 1024) {
            alert('La imagen es demasiado grande. Máximo 5MB.');
            return;
        }
        const uploadId = Date.now().toString(36) + Math.random().toString(36).slice(2, 9);
        const pendingPath = KP_PENDING_PRD + uploadId;
        imagenesGrandes.push(pendingPath);
        imagenesPequenas.push(pendingPath);
        renderizarImagenes();
        document.getElementById('nombre-archivo-nueva').textContent = file.name || '';
        prepararModalAñadirImagenParaOtraProducto('subir');
        cargarCarpetasModal();

        const img = new Image();
        img.crossOrigin = 'anonymous';
        const urlRevoke = URL.createObjectURL(file);
        img.onload = function() {
            URL.revokeObjectURL(urlRevoke);
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
                    const blobGrande = await new Promise((resolve, reject) => {
                        canvasGrande.toBlob((blob) => blob ? resolve(blob) : reject(new Error('Error grande')), 'image/webp', 0.9);
                    });
                    const blobPequena = await new Promise((resolve, reject) => {
                        canvasPequena.toBlob((blob) => blob ? resolve(blob) : reject(new Error('Error pequeña')), 'image/webp', 0.9);
                    });
                    const slugInput = document.querySelector('input[name="slug"]');
                    const nombreBase = slugInput ? slugInput.value.trim() : 'imagen';
                    const timestamp = Date.now();
                    const formDataGrande = new FormData();
                    formDataGrande.append('imagen', blobGrande, `${nombreBase}-${timestamp}.webp`);
                    formDataGrande.append('carpeta', carpeta);
                    formDataGrande.append('_token', '{{ csrf_token() }}');
                    const formDataPequena = new FormData();
                    formDataPequena.append('imagen', blobPequena, `${nombreBase}-${timestamp}-thumbnail.webp`);
                    formDataPequena.append('carpeta', carpeta);
                    formDataPequena.append('_token', '{{ csrf_token() }}');
                    const onProg = (pct) => {
                        const el = document.getElementById('kp-prog-prd-' + uploadId);
                        if (el) el.style.width = pct + '%';
                    };
                    const { dataG, dataP } = await window.__kpSubirParejaConProgreso(
                        '{{ route("admin.imagenes.subir-simple") }}',
                        formDataGrande,
                        formDataPequena,
                        '{{ csrf_token() }}',
                        onProg,
                        uploadsPendientesProducto,
                        uploadId
                    );
                    if (dataG.success && dataP.success) {
                        const ix = imagenesGrandes.indexOf(pendingPath);
                        if (ix !== -1) {
                            imagenesGrandes[ix] = dataG.data.ruta_relativa;
                            imagenesPequenas[ix] = dataP.data.ruta_relativa;
                        }
                        renderizarImagenes();
                    } else {
                        throw new Error(dataG.message || dataP.message || 'Error al subir');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    quitarPlaceholderProducto(pendingPath);
                    alert('Error al procesar la imagen: ' + error.message);
                }
            })();
        };
        img.onerror = function() {
            URL.revokeObjectURL(urlRevoke);
            quitarPlaceholderProducto(pendingPath);
            alert('Error al cargar la imagen. Por favor, verifica que sea un formato válido.');
        };
        img.src = urlRevoke;
    }
    
    // Configurar descarga desde URL (continuará en siguiente parte debido a límite de tamaño)
    document.getElementById('btn-descargar-url-nueva').addEventListener('click', async () => {
        const url = document.getElementById('url-imagen-nueva').value.trim();
        const carpeta = document.getElementById('carpeta-url-nueva').value;
        
        if (!carpeta) {
            document.getElementById('error-url-nueva').textContent = 'Debes seleccionar una carpeta primero.';
            document.getElementById('error-url-nueva').classList.remove('hidden');
            return;
        }
        
        if (!url) {
            document.getElementById('error-url-nueva').textContent = 'Debes introducir una URL válida.';
            document.getElementById('error-url-nueva').classList.remove('hidden');
            return;
        }
        
        document.getElementById('error-url-nueva').classList.add('hidden');
        carpetaActualNueva = carpeta;
        imagenTemporalUrlNueva = url;
        
        // Mostrar área de recorte
        mostrarRecorteEnModal(url);
    });
    
    // Mostrar recorte en el modal
    function mostrarRecorteEnModal(urlImagen) {
        const areaRecorte = document.getElementById('area-recorte-nueva');
        const img = document.getElementById('imagen-recortar-nueva');
        const contenedorCropper = document.getElementById('contenedor-cropper-nueva');
        
        areaRecorte.classList.remove('hidden');
        
        if (cropperNueva) {
            cropperNueva.destroy();
        }
        
        const urlProxy = urlImagen.startsWith('http') 
            ? `{{ route('admin.imagenes.proxy') }}?url=${encodeURIComponent(urlImagen)}`
            : urlImagen;
        
        img.crossOrigin = 'anonymous';
        img.src = urlProxy;
        
        img.onload = function() {
            if (cropperNueva) cropperNueva.destroy();
            // El cropper escalará automáticamente la imagen para que quepa en el contenedor (650x450)
            // pero usará la imagen completa en su resolución original al recortar
            cropperNueva = new Cropper(img, {
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
                toggleDragModeOnDblclick: false,
            });
        };
        
        img.onerror = function() {
            document.getElementById('error-url-nueva').textContent = 'Error al cargar la imagen. Verifica la URL.';
            document.getElementById('error-url-nueva').classList.remove('hidden');
            areaRecorte.classList.add('hidden');
        };
    }
    
    // Guardar nueva imagen desde modal
    document.getElementById('btn-guardar-nueva').addEventListener('click', async () => {
        const tabActiva = document.querySelector('.tab-modal.kp-modal-img-tab--active');
        const tabId = tabActiva ? tabActiva.id : 'tab-url-nueva';
        
        // Si es la pestaña de Amazon, el código de Amazon ya lo maneja, no hacer nada aquí
        if (tabId === 'tab-amazon-nueva') {
            return;
        }
        
        if (tabId === 'tab-interna-nueva' || tabId === 'tab-interna-global-nueva') {
            const contInt = document.getElementById('filas-interna-nueva-producto');
            const filas = contInt ? contInt.querySelectorAll('.kp-fila-interna-nueva') : [];
            const nuevasGrandes = [];
            const nuevasPequenas = [];
            for (let i = 0; i < filas.length; i++) {
                const rG = (
                    filas[i].querySelector('.kp-interna-nueva-g') &&
                    filas[i].querySelector('.kp-interna-nueva-g').value
                ) || '';
                const rP = (
                    filas[i].querySelector('.kp-interna-nueva-p') &&
                    filas[i].querySelector('.kp-interna-nueva-p').value
                ) || '';
                const rGt = rG.trim();
                const rPt = rP.trim();
                if (!rGt) {
                    alert('Fila ' + (i + 1) + ': indica la ruta de la imagen grande.');
                    return;
                }
                if (!rPt) {
                    alert('Fila ' + (i + 1) + ': indica la ruta de la imagen pequeña (miniatura).');
                    return;
                }
                nuevasGrandes.push(rGt);
                nuevasPequenas.push(rPt);
            }
            imagenesGrandes.length = 0;
            imagenesPequenas.length = 0;
            nuevasGrandes.forEach(function(g, idx) {
                imagenesGrandes.push(g);
                imagenesPequenas.push(nuevasPequenas[idx]);
            });
            renderizarImagenes();
            cerrarModalAñadirImagen();
            return;
        }
        
        if (tabId === 'tab-subir-nueva') {
            // Ya se procesa en procesarArchivoNuevo
            // Solo verificar que hay archivo seleccionado
            if (!fileInputNueva.files.length) {
                alert('Por favor selecciona una imagen primero.');
                return;
            }
            return;
        }
        // Procesar desde URL con recorte
        if (!cropperNueva || !carpetaActualNueva) {
            alert('Por favor descarga y recorta la imagen primero.');
            return;
        }
        await procesarImagenRecortadaNueva();
    });
    
    // Procesar imagen recortada desde URL (subida en segundo plano)
    async function procesarImagenRecortadaNueva() {
        const canvasOriginal = cropperNueva.getCroppedCanvas({
            imageSmoothingEnabled: true,
            imageSmoothingQuality: 'high'
        });
        if (!canvasOriginal) {
            alert('Error al recortar la imagen');
            return;
        }
        const carpetaUp = carpetaActualNueva;
        if (!carpetaUp) {
            alert('Selecciona una carpeta primero.');
            return;
        }
        try {
            const canvasGrande = document.createElement('canvas');
            canvasGrande.width = canvasOriginal.width;
            canvasGrande.height = canvasOriginal.height;
            const ctxGrande = canvasGrande.getContext('2d');
            ctxGrande.fillStyle = '#ffffff';
            ctxGrande.fillRect(0, 0, canvasGrande.width, canvasGrande.height);
            ctxGrande.drawImage(canvasOriginal, 0, 0);
            const canvasPequena = document.createElement('canvas');
            canvasPequena.width = 300;
            canvasPequena.height = 250;
            const ctxPequena = canvasPequena.getContext('2d');
            ctxPequena.fillStyle = '#ffffff';
            ctxPequena.fillRect(0, 0, canvasPequena.width, canvasPequena.height);
            ctxPequena.drawImage(canvasOriginal, 0, 0, 300, 250);
            const blobGrande = await new Promise((resolve, reject) => {
                canvasGrande.toBlob((blob) => blob ? resolve(blob) : reject(new Error('Error grande')), 'image/webp', 0.9);
            });
            const blobPequena = await new Promise((resolve, reject) => {
                canvasPequena.toBlob((blob) => blob ? resolve(blob) : reject(new Error('Error pequeña')), 'image/webp', 0.9);
            });
            const uploadId = Date.now().toString(36) + Math.random().toString(36).slice(2, 9);
            const pendingPath = KP_PENDING_PRD + uploadId;
            imagenesGrandes.push(pendingPath);
            imagenesPequenas.push(pendingPath);
            renderizarImagenes();
            prepararModalAñadirImagenParaOtraProducto('url');
            cargarCarpetasModal();

            const slugInput = document.querySelector('input[name="slug"]');
            const nombreBase = slugInput ? slugInput.value.trim() : 'imagen';
            const timestamp = Date.now();
            const formDataGrande = new FormData();
            formDataGrande.append('imagen', blobGrande, `${nombreBase}-${timestamp}.webp`);
            formDataGrande.append('carpeta', carpetaUp);
            formDataGrande.append('_token', '{{ csrf_token() }}');
            const formDataPequena = new FormData();
            formDataPequena.append('imagen', blobPequena, `${nombreBase}-${timestamp}-thumbnail.webp`);
            formDataPequena.append('carpeta', carpetaUp);
            formDataPequena.append('_token', '{{ csrf_token() }}');

            (async function() {
                try {
                    const onProg = (pct) => {
                        const el = document.getElementById('kp-prog-prd-' + uploadId);
                        if (el) el.style.width = pct + '%';
                    };
                    const { dataG, dataP } = await window.__kpSubirParejaConProgreso(
                        '{{ route("admin.imagenes.subir-simple") }}',
                        formDataGrande,
                        formDataPequena,
                        '{{ csrf_token() }}',
                        onProg,
                        uploadsPendientesProducto,
                        uploadId
                    );
                    if (dataG.success && dataP.success) {
                        const ix = imagenesGrandes.indexOf(pendingPath);
                        if (ix !== -1) {
                            imagenesGrandes[ix] = dataG.data.ruta_relativa;
                            imagenesPequenas[ix] = dataP.data.ruta_relativa;
                        }
                        renderizarImagenes();
                    } else {
                        throw new Error(dataG.message || dataP.message || 'Error al subir');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    quitarPlaceholderProducto(pendingPath);
                    alert('Error al procesar la imagen: ' + error.message);
                }
            })();
        } catch (error) {
            console.error('Error:', error);
            alert('Error al procesar la imagen: ' + error.message);
        }
    }
    
    // Actualizar campos ocultos JSON
    function actualizarCamposOcultos() {
        document.getElementById('imagenes-grandes-json').value = JSON.stringify(imagenesGrandes);
        document.getElementById('imagenes-pequenas-json').value = JSON.stringify(imagenesPequenas);
    }

    function kpRutaEsPendienteProducto(r) {
        return typeof r === 'string' && r.indexOf(KP_PENDING_PRD) === 0;
    }

    function kpHayPendienteEnJsonEspecificacionesInternas() {
        const input = document.getElementById('categoria_especificaciones_internas_elegidas_input');
        if (!input || !input.value) return false;
        try {
            const esp = JSON.parse(input.value);
            const keys = Object.keys(esp);
            for (let k = 0; k < keys.length; k++) {
                const arr = esp[keys[k]];
                if (!Array.isArray(arr)) continue;
                for (let i = 0; i < arr.length; i++) {
                    const item = arr[i];
                    if (item && typeof item === 'object' && item.img && Array.isArray(item.img)) {
                        for (let j = 0; j < item.img.length; j++) {
                            if (kpRutaEsPendienteProducto(item.img[j])) return true;
                        }
                    }
                }
            }
        } catch (err) {}
        return false;
    }

    function kpHayImagenesSubiendoEnFormularioProducto() {
        if (imagenesGrandes.some(kpRutaEsPendienteProducto) || imagenesPequenas.some(kpRutaEsPendienteProducto)) {
            return true;
        }
        return kpHayPendienteEnJsonEspecificacionesInternas();
    }

    window.kpHayImagenesSubiendoEnFormularioProducto = kpHayImagenesSubiendoEnFormularioProducto;

    const formBloqueoSubidasImagen = document.querySelector('form');
    if (formBloqueoSubidasImagen) {
        formBloqueoSubidasImagen.addEventListener('submit', function(e) {
            if (kpHayImagenesSubiendoEnFormularioProducto()) {
                e.preventDefault();
                alert('Espera a que terminen de subirse las imágenes (miniaturas con «Cargando imagen…» o barra de progreso) antes de guardar el producto.');
                const cont = document.getElementById('imagenes-container');
                if (cont) cont.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }, true);
    }
    
    if (typeof window.kpInternaGlobalRegistrar === 'function') {
        window.kpInternaGlobalRegistrar('nueva', {
            onSelect: function(pair) {
                if (typeof window.kpToggleInternaNuevaSeleccion === 'function') {
                    window.kpToggleInternaNuevaSeleccion(pair);
                }
            },
            isSelected: function(pair) {
                return typeof window.kpIndiceSeleccionInternaNueva === 'function'
                    && window.kpIndiceSeleccionInternaNueva(pair) !== -1;
            },
            renderResumen: function() {
                const el = document.querySelector('[data-kp-ig-panel="nueva"] .kp-ig-seleccion-resumen');
                const pairs = typeof window.kpObtenerParesSeleccionInternaNueva === 'function'
                    ? window.kpObtenerParesSeleccionInternaNueva()
                    : [];
                window.kpIgPintarResumenSeleccion(el, pairs);
            },
            getPalabras: function() {
                if (typeof window.kpObtenerPalabrasNombreProducto === 'function') {
                    return window.kpObtenerPalabrasNombreProducto();
                }
                const nombre = document.getElementById('input_nombre')?.value
                    || document.querySelector('input[name="nombre"]')?.value
                    || '';
                return window.kpPartirNombreEnPalabras(nombre);
            },
        });
        window.kpInternaGlobalRegistrar('sublinea', {
            onSelect: function(pair) {
                if (typeof window.kpToggleInternaSublineaSeleccion === 'function') {
                    window.kpToggleInternaSublineaSeleccion(pair);
                }
            },
            isSelected: function(pair) {
                return typeof window.kpIndiceSeleccionInternaSublinea === 'function'
                    && window.kpIndiceSeleccionInternaSublinea(pair) !== -1;
            },
            renderResumen: function() {
                const el = document.querySelector('[data-kp-ig-panel="sublinea"] .kp-ig-seleccion-resumen');
                const pairs = typeof window.kpObtenerParesSeleccionInternaSublinea === 'function'
                    ? window.kpObtenerParesSeleccionInternaSublinea()
                    : [];
                window.kpIgPintarResumenSeleccion(el, pairs);
            },
            getPalabras: function() {
                if (typeof window.kpObtenerPalabrasNombreProducto === 'function') {
                    return window.kpObtenerPalabrasNombreProducto();
                }
                return [];
            },
        });
    }

    // Inicializar
    cargarImagenesExistentes();
});
</script>

{{-- CROPPER.JS para recorte de imágenes (usado en el nuevo sistema de múltiples imágenes) --}}
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/cropperjs@1.5.13/dist/cropper.min.css">
<script src="https://cdn.jsdelivr.net/npm/cropperjs@1.5.13/dist/cropper.min.js"></script>

{{-- JavaScript para gestión de grupos de ofertas --}}
@if($producto && $producto->unidadDeMedida === 'unidadUnica')
<script>
    let gruposOfertasProducto = null;
    const productoIdGrupos = {{ $producto->id }};

    // Cargar grupos al iniciar
    document.addEventListener('DOMContentLoaded', async function() {
        await cargarGruposProducto();
    });

    async function cargarGruposProducto() {
        try {
            const response = await fetch(`/panel-privado/productos/${productoIdGrupos}`);
            const producto = await response.json();
            
            gruposOfertasProducto = producto.grupos_de_ofertas || {};
            
            // Cargar ofertas del producto
            const responseOfertas = await fetch(`/panel-privado/ofertas/producto/${productoIdGrupos}`);
            const ofertasData = await responseOfertas.json();
            const ofertas = ofertasData.ofertas || [];
            
            // Obtener tiendas
            const tiendasMap = {};
            for (const oferta of ofertas) {
                if (oferta.tienda_id && !tiendasMap[oferta.tienda_id]) {
                    const responseTienda = await fetch(`/panel-privado/tiendas/${oferta.tienda_id}`);
                    const tienda = await responseTienda.json();
                    tiendasMap[oferta.tienda_id] = tienda;
                }
            }
            
            renderizarGruposProducto(gruposOfertasProducto, ofertas, tiendasMap);
        } catch (error) {
            console.error('Error al cargar grupos:', error);
            document.getElementById('grupos-lista-producto').innerHTML = '<p class="text-sm text-red-500">Error al cargar los grupos</p>';
        }
    }

    function renderizarGruposProducto(grupos, ofertas, tiendasMap) {
        const container = document.getElementById('grupos-lista-producto');
        if (!container) return;
        
        if (!grupos || Object.keys(grupos).length === 0) {
            container.innerHTML = '<p class="text-sm text-gray-500 dark:text-gray-400 text-center py-4">No hay grupos creados. Crea uno nuevo para comenzar.</p>';
            return;
        }
        
        let html = '';
        
        // Agrupar grupos por tienda
        const gruposPorTienda = {};
        for (const [grupoId, grupoData] of Object.entries(grupos)) {
            if (Array.isArray(grupoData) && grupoData.length >= 2) {
                const tiendaId = grupoData[0];
                if (!gruposPorTienda[tiendaId]) {
                    gruposPorTienda[tiendaId] = [];
                }
                gruposPorTienda[tiendaId].push({ grupoId, grupoData });
            }
        }
        
        // Renderizar grupos por tienda
        for (const [tiendaId, gruposTienda] of Object.entries(gruposPorTienda)) {
            const tienda = tiendasMap[tiendaId];
            const tiendaNombre = tienda?.nombre || 'Tienda desconocida';
            const tiendaImagen = tienda?.url_imagen || null;
            
            for (const { grupoId, grupoData } of gruposTienda) {
                const ofertasIds = Array.isArray(grupoData[1]) ? grupoData[1] : [];
                const ofertasGrupo = ofertas.filter(o => ofertasIds.includes(o.id));
                
                html += `
                    <div class="grupo-item w-36 h-36 border-2 border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden cursor-pointer transition-all hover:shadow-lg relative" 
                         data-grupo-id="${grupoId}"
                         onclick="mostrarOfertasGrupoProducto('${grupoId}', ${JSON.stringify(ofertasGrupo).replace(/"/g, '&quot;')})">
                        <div class="h-full p-4 flex flex-col items-center justify-center">
                            ${tiendaImagen 
                                ? `<img src="/images/${tiendaImagen}" alt="${tiendaNombre}" class="w-12 h-12 object-contain rounded mb-1">`
                                : `<div class="w-12 h-12 bg-gray-200 dark:bg-gray-600 rounded flex items-center justify-center text-xs text-center mb-1">${tiendaNombre.substring(0, 10)}</div>`
                            }
                            <p class="font-semibold text-gray-900 dark:text-white text-sm text-center">${tiendaNombre}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 text-center mt-1">${ofertasGrupo.length} oferta${ofertasGrupo.length !== 1 ? 's' : ''}</p>
                        </div>
                    </div>
                `;
            }
        }
        
        container.innerHTML = html;
    }

    // Función para mostrar ofertas de un grupo seleccionado
    window.mostrarOfertasGrupoProducto = function(grupoId, ofertasGrupo) {
        const contenedorOfertas = document.getElementById('ofertas-grupo-seleccionado-producto');
        const listaOfertas = document.getElementById('ofertas-grupo-lista-producto');
        
        if (!contenedorOfertas || !listaOfertas) return;
        
        // Remover selección de otros grupos (bordes)
        document.querySelectorAll('.grupo-item').forEach(item => {
            if (item.dataset.grupoId !== grupoId) {
                item.classList.remove('border-green-500', 'bg-green-50', 'dark:bg-green-900/20');
                item.classList.add('border-gray-200', 'dark:border-gray-700');
            } else {
                item.classList.add('border-green-500', 'bg-green-50', 'dark:bg-green-900/20');
            }
        });
        
        if (!ofertasGrupo || ofertasGrupo.length === 0) {
            listaOfertas.innerHTML = '<p class="text-sm text-gray-500 dark:text-gray-400">Este grupo no tiene ofertas.</p>';
            contenedorOfertas.classList.remove('hidden');
            return;
        }
        
        // Renderizar lista de ofertas
        let html = '';
        ofertasGrupo.forEach(oferta => {
            html += `
                <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <div class="flex-1">
                        <p class="font-semibold text-gray-900 dark:text-white">Oferta #${oferta.id}</p>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Precio: ${oferta.precio_unidad || 'N/A'}€</p>
                    </div>
                    <div class="flex gap-2">
                        <a href="/panel-privado/ofertas/${oferta.id}/edit" target="_blank" 
                           class="px-3 py-1 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded transition">
                            Editar
                        </a>
                        ${oferta.url ? `
                            <a href="${oferta.url}" target="_blank" 
                               class="px-3 py-1 bg-green-600 hover:bg-green-700 text-white text-sm rounded transition">
                                Ver URL
                            </a>
                        ` : ''}
                        <button type="button" 
                                onclick="quitarOfertaDelGrupoProducto('${grupoId}', ${oferta.id})" 
                                class="px-3 py-1 bg-orange-600 hover:bg-orange-700 text-white text-sm rounded transition">
                            Quitar del grupo
                        </button>
                    </div>
                </div>
            `;
        });
        
        listaOfertas.innerHTML = html;
        contenedorOfertas.classList.remove('hidden');
    };

    // Función para quitar una oferta de un grupo
    window.quitarOfertaDelGrupoProducto = async function(grupoId, ofertaId) {
        if (!confirm('¿Estás seguro de que quieres quitar esta oferta del grupo?')) {
            return;
        }
        
        if (!gruposOfertasProducto || !gruposOfertasProducto[grupoId]) {
            return;
        }
        
        const grupoData = gruposOfertasProducto[grupoId];
        if (Array.isArray(grupoData) && grupoData.length >= 2 && Array.isArray(grupoData[1])) {
            const index = grupoData[1].indexOf(Number(ofertaId));
            if (index > -1) {
                grupoData[1].splice(index, 1);
                
                // Si el grupo queda vacío, eliminarlo
                if (grupoData[1].length === 0) {
                    delete gruposOfertasProducto[grupoId];
                }
                
                // Guardar cambios
                await guardarGruposProducto();
                
                // Recargar grupos
                await cargarGruposProducto();
                
                // Ocultar el listado de ofertas ya que el grupo puede haber cambiado
                const contenedorOfertas = document.getElementById('ofertas-grupo-seleccionado-producto');
                if (contenedorOfertas) {
                    contenedorOfertas.classList.add('hidden');
                }
            }
        }
    };

    // Guardar grupos
    async function guardarGruposProducto() {
        try {
            const response = await fetch(`/panel-privado/productos/${productoIdGrupos}/grupos-ofertas/actualizar`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ grupos_de_ofertas: gruposOfertasProducto })
            });
            
            const data = await response.json();
            
            if (!data.success) {
                throw new Error(data.message || 'Error al guardar grupos');
            }
        } catch (error) {
            console.error('Error al guardar grupos:', error);
            alert('Error al guardar los grupos: ' + error.message);
        }
    }
</script>
@endif

{{-- MODAL PARA VER PROMPT --}}
<div id="modal-ver-prompt" class="fixed inset-0 bg-black bg-opacity-10 backdrop-blur-sm z-50 flex items-center justify-center hidden">
    <div class="bg-white dark:bg-gray-900 rounded-lg p-6 max-w-4xl w-full mx-4 relative shadow-xl max-h-[90vh] overflow-y-auto">
        <button onclick="cerrarModalPrompt()" class="absolute top-3 right-4 text-xl text-gray-800 dark:text-gray-100 hover:text-gray-600 dark:hover:text-gray-300">×</button>
        <div class="mb-4">
            <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-200 mb-2">Prompt que se enviará a ChatGPT</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Este es el texto completo que se enviará a ChatGPT cuando uses el botón "Rellenar información automáticamente".</p>
        </div>
        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
            <pre id="contenido-prompt" class="whitespace-pre-wrap text-sm text-gray-800 dark:text-gray-200 font-mono overflow-x-auto"></pre>
        </div>
        <div class="mt-4 flex justify-end">
            <button onclick="cerrarModalPrompt()" class="px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white rounded-md">
                Cerrar
            </button>
        </div>
    </div>
</div>

<script>
    // Función para generar el prompt completo
    function generarPromptCompleto() {
        // Obtener datos del producto
        const nombre = document.querySelector('input[name="nombre"]')?.value.trim() || '';
        const marca = document.querySelector('input[name="marca"]')?.value.trim() || '';
        const modelo = document.querySelector('input[name="modelo"]')?.value.trim() || '';
        const talla = document.querySelector('input[name="talla"]')?.value.trim() || '';
        const categoriaId = document.getElementById('categoria-final')?.value || null;

        // Obtener nombre de la última categoría seleccionada
        const selectores = document.querySelectorAll('.categoria-select');
        let nombreCategoria = 'la categoría seleccionada';
        for (let i = selectores.length - 1; i >= 0; i--) {
            if (selectores[i].value) {
                const opcionSeleccionada = selectores[i].options[selectores[i].selectedIndex];
                if (opcionSeleccionada && opcionSeleccionada.textContent) {
                    nombreCategoria = opcionSeleccionada.textContent;
                    break;
                }
            }
        }

        // Generar el prompt base
        let prompt = `Actúa como un experto en SEO y marketing digital especializado en productos para ${nombreCategoria}. Necesito que generes contenido optimizado para un comparador de precios.

INFORMACIÓN DEL PRODUCTO:
- Nombre: ${nombre}
- Marca: ${marca}
- Modelo: ${modelo}
- Talla: ${talla}

INSTRUCCIONES ESPECÍFICAS:
Debes generar EXACTAMENTE un objeto JSON con la siguiente estructura, sin texto adicional antes o después:

{
  "titulo": "",
  "subtitulo": "",
  "descripcion_corta": "",
  "descripcion_larga": "",
  "caracteristicas": "",
  "meta_titulo": "",
  "meta_descripcion": "",
  "pros": ["AQUI UN PRO", "AQUI UN PRO", "AQUI UN PRO", "AQUI UN PRO", "AQUI UN PRO", "AQUI UN PRO"],
  "contras": ["AQUI UN CONTRA", "AQUI UN CONTRA", "AQUI UN CONTRA"],
  "preguntas_frecuentes": [
    {"pregunta": "AQUI DEBES AÑADIRME LA PREGUNTA", "respuesta": "AQUÍ DEBES AÑADIRLA RESPUESTA"},
    {"pregunta": "AQUI DEBES AÑADIRME LA PREGUNTA", "respuesta": "AQUÍ DEBES AÑADIRLA RESPUESTA"}
  ]
}

REGLAS IMPORTANTES:
-titulo -> Debe ser corto y conciso con la funcion que tiene el comparador de precio y el nombre del producto
-subtitulo -> Debe ser corto ya  que esto es un titulo que estará encima del listado de precios del producto, es un h2, pero no me pongas la etiqueta HTML que ya se la estoy poniendo yo, te lo comento para que sepas que debe ser corto y claro.
-descripcion_corta -> Debe ser una breve descripcion del producto de entre 300 y 400 caracteres.
-descripcion_larga -> Aquí me debes poner h3, p, en este campo si me puedes poner etiquetas HTML ya que esto será la descripcion y explicacion de detalles de la ficha del producto, aquí debemos ponerme entre 700 y 800 caracteres.
-meta_titulo: Debe ser corto y conciso ya que esto es para SEO y tiene que quedar claro de que va el articulo del comparador de precios con el nombre del producto
-meta_descripcion: Debe ser una breve descripcion de lo que se va a encontrar el usuario si entra en mi web, de un comparador de precios, para comprar al mejor precio y cosas así. Con el nombre del producto.
-pros-> TIENES LA ESTRUCTURA DE COMO DEBES AÑADIR LOS PROS, DONDE ME DEBES AÑADIR DONDE PONE AQUI UN PRO, CAMBIARMELO POR UN PRO QUE TENGA ESTE PRODUTO
-contra-> TIENES LA ESTRUCTURA DE COMO DEBES DEVOLVERMELO, TAN SOLO TIENES QUE CAMBIARME EL AQUI UN CONTRA, POR UN CONTRA QUE TENGA ESTE PRODUCTO
-preguntas_frecuentes-> TIENES LA ESTRUCTURA DE COMO DEBES AÑADIRME LA PREGUNTA Y LA RESPUESTA, DONDE PONE AQUI DEBES AÑADIRME LA PREGUNTA, PUES AHÍ ME PONES LA PREGUNTA Y DONDE PONE AQUÍ DEBES AÑADIRLA RESPUESTA, PUES AHÍ ME PONER LA RESPUESTA A LA PREGUNTA, DEBE HABER MINIMO 4 PREGUNTAS CON SUS RESPUESTAS.
- Devuelve ÚNICAMENTE el JSON, sin explicaciones adicionales
- Asegúrate de que el JSON sea válido
- Incluye exactamente los campos especificados
- Usa la información del producto proporcionada
- Optimiza para SEO con palabras clave relevantes`;

        const promesaInfoCategoria = categoriaId
            ? fetch(`/panel-privado/categorias/${categoriaId}/info-chatgpt`, {
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            })
                .then(response => response.json())
                .then(data => data.success && data.info_adicional ? data.info_adicional.trim() : '')
            : Promise.resolve('');

        promesaInfoCategoria
            .then((infoAdicional) => {
                if (infoAdicional) {
                    prompt += "\n\n" + infoAdicional;
                }
                mostrarPromptEnModal(prompt);
            })
            .catch(error => {
                console.error('Error al preparar prompt:', error);
                mostrarPromptEnModal(prompt);
            });
    }

    // Función para mostrar el prompt en el modal
    function mostrarPromptEnModal(prompt) {
        const modal = document.getElementById('modal-ver-prompt');
        const contenido = document.getElementById('contenido-prompt');
        if (modal && contenido) {
            contenido.textContent = prompt;
            modal.classList.remove('hidden');
        }
    }

    // Función para cerrar el modal
    window.cerrarModalPrompt = function() {
        const modal = document.getElementById('modal-ver-prompt');
        if (modal) {
            modal.classList.add('hidden');
        }
    };

    // Event listener para el botón "Ver prompt"
    document.addEventListener('DOMContentLoaded', function() {
        const btnVerPrompt = document.getElementById('ver-prompt');
        if (btnVerPrompt) {
            btnVerPrompt.addEventListener('click', function() {
                generarPromptCompleto();
            });
        }
    });
</script>

{{-- SCRIPT PARA ESPECIFICACIONES INTERNAS DEL PRODUCTO --}}
<script>
document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('especificaciones-producto-container');
    const inputHidden = document.getElementById('categoria_especificaciones_internas_elegidas_input');
    const btnAñadir = document.getElementById('btn-añadir-linea-producto');
    
    if (!container || !inputHidden || !btnAñadir) {
        return;
    }

    let contadorPrincipalProducto = 0;
    let contadorIntermediaProducto = 0;
    let datosCargadosProducto = false;
    let elementoArrastradoProducto = null;
    let tipoArrastradoProducto = null;
    
    // Verificar si es unidadUnica
    const unidadDeMedidaSelect = document.getElementById('unidadDeMedida');
    const esUnidadUnica = unidadDeMedidaSelect && unidadDeMedidaSelect.value === 'unidadUnica';
    
    // Generar ID único para líneas
    function generarIdUnicoProducto() {
        return Date.now() + '_' + Math.random().toString(36).substr(2, 9);
    }
    
    // Función para generar slug desde texto
    function generarSlugProducto(texto) {
        return texto
            .toString()
            .toLowerCase()
            .trim()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '');
    }
    
    // Inicializar con datos existentes
    function inicializarProducto() {
        const valorActual = inputHidden.value;
        let datos = null;
        let columnasGuardadas = [];
        let formatosGuardados = {};
        
        if (valorActual && valorActual.trim() !== '') {
            try {
                const parsed = JSON.parse(valorActual);
                // Buscar líneas del producto (marcadas con prefijo _producto o en _producto)
                if (parsed && parsed._producto && parsed._producto.filtros && Array.isArray(parsed._producto.filtros) && parsed._producto.filtros.length > 0) {
                    datos = parsed._producto.filtros;
                }
                // Obtener columnas guardadas (pueden estar en _columnas o en _esColumna dentro de cada filtro)
                // _columnas puede ser lista o mapa { idPrincipal: sublineaId }
                if (parsed && parsed._columnas) {
                    if (Array.isArray(parsed._columnas)) {
                        columnasGuardadas = parsed._columnas
                            .map(v => (v && typeof v === 'object' && !Array.isArray(v)) ? v.id : v)
                            .filter(v => (typeof v === 'string' || typeof v === 'number'))
                            .map(v => String(v));
                    } else if (typeof parsed._columnas === 'object') {
                        columnasGuardadas = Object.keys(parsed._columnas)
                            .filter(k => k && !String(k).startsWith('_'))
                            .map(k => String(k));
                    }
                }
                // Obtener formatos guardados
                if (parsed && parsed._formatos && typeof parsed._formatos === 'object') {
                    // Compatibilidad: puede venir como {"idLinea":"texto"} o {"idLinea":{id:"texto",c:0}}
                    formatosGuardados = {};
                    Object.entries(parsed._formatos).forEach(([k, v]) => {
                        if (v && typeof v === 'object' && !Array.isArray(v)) {
                            if (typeof v.id === 'string') {
                                formatosGuardados[k] = v.id;
                            }
                        } else if (typeof v === 'string') {
                            formatosGuardados[k] = v;
                        }
                    });
                }
            } catch (e) {
                console.error('Error parseando especificaciones del producto:', e);
            }
        }
        
        if (datos && datos.length > 0) {
            datos.forEach((filtro) => {
                // Verificar si es columna: primero en _columnas, luego en _esColumna
                const esColumna = columnasGuardadas.includes(String(filtro.id)) || filtro._esColumna === true;
                // Obtener formato guardado para esta línea principal
                const formatoGuardado = (typeof formatosGuardados[filtro.id] === 'string') ? formatosGuardados[filtro.id] : 'imagen_texto_precio';
                crearLineaPrincipalProducto(filtro.texto || '', filtro.importante || false, filtro.subprincipales || [], filtro.id || null, filtro.slug || null, esColumna, formatoGuardado);
            });
        }
        
        datosCargadosProducto = true;
        if (!datos || datos.length === 0) {
            actualizarJSONProducto();
        }
        
        // Validar después de cargar datos
        setTimeout(() => {
            if (typeof validarEspecificacionesInternas === 'function') {
                validarEspecificacionesInternas();
            }
        }, 300);
    }
    
    // Ocultar botón eliminar en la primera sublínea (un grupo no puede quedar sin sublíneas)
    function actualizarBotonesEliminarSublineasProducto(lineaPrincipal) {
        const containerSub = lineaPrincipal.querySelector('.subprincipales-producto-container');
        if (!containerSub) return;
        containerSub.querySelectorAll('.linea-intermedia-producto').forEach((sublinea, index) => {
            const btnEliminar = sublinea.querySelector('.btn-eliminar-intermedia-producto');
            if (btnEliminar) {
                btnEliminar.classList.toggle('hidden', index === 0);
            }
        });
    }

    // Marcar sublínea como activa, Mostrar y Oferta (y Columna oferta si es la primera del grupo)
    function marcarSublineaProductoMostrarOferta(divIntermedia, lineaPrincipal) {
        const checkboxPrincipal = divIntermedia.querySelector('.especificacion-producto-checkbox');
        const mostrarCheckbox = divIntermedia.querySelector('.especificacion-producto-mostrar-checkbox');
        const ofertaCheckbox = divIntermedia.querySelector('.especificacion-producto-oferta-checkbox');
        const containerSub = lineaPrincipal.querySelector('.subprincipales-producto-container');
        const sublineas = containerSub ? Array.from(containerSub.querySelectorAll('.linea-intermedia-producto')) : [];
        const esPrimeraSublinea = sublineas[0] === divIntermedia;
        const principalId = lineaPrincipal.dataset.idUnico;
        const usarNoColumnaImagenNombrePrecio = kpEsConfigNoColumnaGrupoImagenNombrePrecio(window.kpConfiguracionFormularioProducto);

        if (checkboxPrincipal && !checkboxPrincipal.checked) {
            checkboxPrincipal.checked = true;
            checkboxPrincipal.dispatchEvent(new Event('change', { bubbles: true }));
        }

        if (mostrarCheckbox && !mostrarCheckbox.disabled) {
            if (!mostrarCheckbox.checked) {
                mostrarCheckbox.checked = true;
                mostrarCheckbox.dispatchEvent(new Event('change', { bubbles: true }));
            } else if (ofertaCheckbox && !ofertaCheckbox.disabled && !ofertaCheckbox.checked) {
                ofertaCheckbox.checked = true;
                ofertaCheckbox.dispatchEvent(new Event('change', { bubbles: true }));
            }
        }

        if (usarNoColumnaImagenNombrePrecio) {
            const formatoSelect = lineaPrincipal.querySelector(`.formato-visualizacion-producto-select[data-principal-id="${principalId}"]`);
            if (formatoSelect) {
                formatoSelect.value = 'imagen_texto_precio';
            }
        } else if (esPrimeraSublinea) {
            // Primera sublínea del grupo: marcar también Columna oferta
            const unidadDeMedidaSelect = document.getElementById('unidadDeMedida');
            const esUnidadUnicaLocal = unidadDeMedidaSelect && unidadDeMedidaSelect.value === 'unidadUnica';
            const maxColumnasProducto = esUnidadUnicaLocal ? 4 : 1;
            const columnaCheckbox = lineaPrincipal.querySelector(`.columna-oferta-producto-checkbox[data-principal-id="${principalId}"]`);
            if (columnaCheckbox && !columnaCheckbox.checked) {
                const columnasMarcadas = Array.from(container.querySelectorAll('.columna-oferta-producto-checkbox:checked'));
                if (columnasMarcadas.length < maxColumnasProducto) {
                    columnaCheckbox.checked = true;
                    columnaCheckbox.dispatchEvent(new Event('change', { bubbles: true }));
                }
            }
        }

        actualizarJSONProducto();
        actualizarVisibilidadFormatoProducto(lineaPrincipal);
    }

    // Crear una línea principal del producto
    function crearLineaPrincipalProducto(texto = '', importante = false, subprincipales = [], idUnico = null, slugUnico = null, esColumna = false, formatoGuardado = 'imagen_texto_precio') {
        const idPrincipal = `principal-producto-${contadorPrincipalProducto++}`;
        const idUnicoLinea = idUnico || generarIdUnicoProducto();
        const slugLinea = slugUnico || (texto ? generarSlugProducto(texto) : '');
        const divPrincipal = document.createElement('div');
        divPrincipal.className = 'linea-principal-producto border border-gray-300 dark:border-gray-600 rounded-lg p-4 bg-gray-50 dark:bg-gray-800 mb-4';
        divPrincipal.dataset.id = idPrincipal;
        divPrincipal.dataset.idUnico = idUnicoLinea;
        divPrincipal.dataset.slug = slugLinea;
        divPrincipal.draggable = false;
        
        divPrincipal.innerHTML = `
            <div class="flex items-center gap-3 mb-3">
                <div class="drag-handle-principal-producto cursor-move text-gray-400 hover:text-gray-600 dark:hover:text-gray-300" title="Arrastrar para reordenar" draggable="true">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"></path>
                    </svg>
                </div>
                <input type="text" 
                       class="linea-principal-producto-texto flex-1 px-3 py-2 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-white border border-gray-300 dark:border-gray-600" 
                       placeholder="Texto principal"
                       value="${texto}">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" class="linea-principal-producto-importante" ${importante ? 'checked' : ''}>
                    <span class="text-sm text-gray-700 dark:text-gray-200">Importante</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" class="columna-oferta-producto-checkbox rounded border-gray-300 text-orange-600 focus:ring-orange-500" data-principal-id="${idUnicoLinea}" ${esColumna ? 'checked' : ''}>
                    <span class="text-xs text-gray-600 dark:text-gray-400 font-medium">Columna oferta</span>
                </label>
                <button type="button" class="btn-eliminar-principal-producto bg-red-500 hover:bg-red-600 text-white px-3 py-2 rounded text-sm" title="Eliminar línea principal">
                    -
                </button>
                <button type="button" class="btn-añadir-principal-producto bg-green-500 hover:bg-green-600 text-white px-3 py-2 rounded text-sm" title="Añadir línea principal debajo">
                    +
                </button>
            </div>
            <div class="subprincipales-producto-container ml-8 space-y-2"></div>
            <div class="formato-visualizacion-producto-container mt-3" data-principal-id="${idUnicoLinea}" style="display: none;">
                <label class="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-200">Formato de visualización:</label>
                <select class="formato-visualizacion-producto-select w-full px-3 py-2 rounded bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-gray-200 border border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500" data-principal-id="${idUnicoLinea}">
                    <option value="texto" ${formatoGuardado === 'texto' ? 'selected' : ''}>Texto</option>
                    <option value="texto_precio" ${formatoGuardado === 'texto_precio' ? 'selected' : ''}>Texto y precio</option>
                    <option value="imagen" ${formatoGuardado === 'imagen' ? 'selected' : ''}>Imagen</option>
                    <option value="imagen_texto" ${formatoGuardado === 'imagen_texto' ? 'selected' : ''}>Imagen y texto</option>
                    <option value="imagen_precio" ${formatoGuardado === 'imagen_precio' ? 'selected' : ''}>Imagen y precio</option>
                    <option value="imagen_texto_precio" ${formatoGuardado === 'imagen_texto_precio' ? 'selected' : ''}>Imagen, texto y precio</option>
                </select>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Selecciona cómo se mostrarán las sublíneas marcadas como "Mostrar" en la vista del comparador</p>
            </div>
        `;
        
        container.appendChild(divPrincipal);
        
        // Configurar eventos
        const inputTexto = divPrincipal.querySelector('.linea-principal-producto-texto');
        const checkboxImportante = divPrincipal.querySelector('.linea-principal-producto-importante');
        const btnEliminar = divPrincipal.querySelector('.btn-eliminar-principal-producto');
        const btnAñadir = divPrincipal.querySelector('.btn-añadir-principal-producto');
        const containerSubprincipales = divPrincipal.querySelector('.subprincipales-producto-container');
        const columnaCheckbox = divPrincipal.querySelector('.columna-oferta-producto-checkbox');
        const formatoSelect = divPrincipal.querySelector('.formato-visualizacion-producto-select');
        const formatoContainer = divPrincipal.querySelector('.formato-visualizacion-producto-container');
        
        inputTexto.addEventListener('dragstart', (e) => {
            e.preventDefault();
            e.stopPropagation();
        });
        
        inputTexto.addEventListener('input', function() {
            // Actualizar slug cuando cambia el texto (siempre regenerar para asegurar que esté correcto)
            const textoActual = this.value.trim();
            if (textoActual) {
                divPrincipal.dataset.slug = generarSlugProducto(textoActual);
            }
            actualizarJSONProducto();
            // Validar en tiempo real
            if (typeof validarEspecificacionesInternas === 'function') {
                validarEspecificacionesInternas();
            }
        });
        
        inputTexto.addEventListener('blur', function() {
            // Validar al salir del campo
            if (typeof validarEspecificacionesInternas === 'function') {
                validarEspecificacionesInternas();
            }
        });

        inputTexto.addEventListener('keydown', function(e) {
            if (e.key === 'Tab' && !e.shiftKey) {
                const containerSub = divPrincipal.querySelector('.subprincipales-producto-container');
                const primeraSublinea = containerSub?.querySelector('.linea-intermedia-producto');
                if (primeraSublinea) {
                    e.preventDefault();
                    const checkbox = primeraSublinea.querySelector('.especificacion-producto-checkbox');
                    const inputSub = primeraSublinea.querySelector('.linea-intermedia-producto-texto');
                    if (checkbox && !checkbox.checked) {
                        checkbox.checked = true;
                        checkbox.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                    if (inputSub) {
                        inputSub.focus();
                    }
                }
            }
        });
        
        checkboxImportante.addEventListener('change', actualizarJSONProducto);
        
        // Event listener para el selector de formato
        if (formatoSelect) {
            formatoSelect.addEventListener('change', function() {
                actualizarJSONProducto();
            });
        }
        
        if (columnaCheckbox) {
            columnaCheckbox.addEventListener('change', function() {
                const unidadDeMedidaSelect = document.getElementById('unidadDeMedida');
                const esUnidadUnicaLocal = unidadDeMedidaSelect && unidadDeMedidaSelect.value === 'unidadUnica';
                const maxColumnasProducto = esUnidadUnicaLocal ? 4 : 1;
                const columnasMarcadas = Array.from(container.querySelectorAll('.columna-oferta-producto-checkbox:checked'));
                if (columnasMarcadas.length > maxColumnasProducto) {
                    this.checked = false;
                    alert(esUnidadUnicaLocal
                        ? 'Solo se pueden marcar hasta 4 líneas principales como columna oferta.'
                        : 'Solo se puede marcar una línea principal como columna oferta.');
                    return;
                }
                // Mostrar/ocultar campos de texto alternativo
                const principalId = this.dataset.principalId;
                const camposTextoAlternativo = container.querySelectorAll(`.texto-alternativo-sublinea-producto-input[data-principal-id="${principalId}"]`);
                camposTextoAlternativo.forEach(campo => {
                    if (this.checked) {
                        campo.style.display = 'block';
                        const sublineaCheckbox = container.querySelector(`.especificacion-producto-checkbox[data-principal-id="${principalId}"][data-sublinea-id="${campo.dataset.sublineaId}"]`);
                        campo.disabled = !sublineaCheckbox || !sublineaCheckbox.checked;
                    } else {
                        campo.style.display = 'none';
                    }
                });
                actualizarJSONProducto();
            });
        }
        
        btnEliminar.addEventListener('click', () => {
            divPrincipal.remove();
            actualizarJSONProducto();
            // Validar después de eliminar
            if (typeof validarEspecificacionesInternas === 'function') {
                validarEspecificacionesInternas();
            }
        });
        
        btnAñadir.addEventListener('click', () => {
            const nuevaLinea = crearLineaPrincipalProducto('', false, []);
            divPrincipal.insertAdjacentElement('afterend', nuevaLinea);
            actualizarJSONProducto();
            // Validar después de añadir
            if (typeof validarEspecificacionesInternas === 'function') {
                setTimeout(() => {
                    validarEspecificacionesInternas();
                }, 100);
            }
        });
        
        // Configurar drag and drop
        const dragHandle = divPrincipal.querySelector('.drag-handle-principal-producto');
        configurarDragAndDropDesdeIconoProducto(dragHandle, divPrincipal, 'principal');
        
        // Cargar subprincipales si existen
        if (subprincipales && subprincipales.length > 0) {
            subprincipales.forEach(sub => {
                crearLineaIntermediaProducto(containerSubprincipales, divPrincipal, sub.texto || '', sub.id || null, sub.slug || null);
            });
        } else {
            crearLineaIntermediaProducto(containerSubprincipales, divPrincipal);
        }
        
        // Actualizar visibilidad del selector de formato después de cargar sublíneas
        setTimeout(() => {
            actualizarVisibilidadFormatoProducto(divPrincipal);
            actualizarBotonesEliminarSublineasProducto(divPrincipal);
        }, 100);
        
        return divPrincipal;
    }
    
    // Crear una línea intermedia (sublínea) del producto
    function crearLineaIntermediaProducto(containerPadre, lineaPrincipal, texto = '', idUnico = null, slugUnico = null) {
        const idIntermedia = `intermedia-producto-${contadorIntermediaProducto++}`;
        const idUnicoLinea = idUnico || generarIdUnicoProducto();
        const slugLinea = slugUnico || (texto ? generarSlugProducto(texto) : '');
        const principalId = lineaPrincipal.dataset.idUnico;
        const esColumna = esUnidadUnica && lineaPrincipal.querySelector('.columna-oferta-producto-checkbox')?.checked;
        
        // Obtener datos guardados para esta sublínea
        let sublineaData = null;
        // Buscar en el campo correcto (inputHidden que contiene las especificaciones de categoría y producto)
        if (inputHidden && inputHidden.value) {
            try {
                const especificaciones = JSON.parse(inputHidden.value);
                // Buscar la sublínea en el array directo con el ID de la línea principal
                if (especificaciones && especificaciones[principalId] && Array.isArray(especificaciones[principalId])) {
                    sublineaData = especificaciones[principalId].find(item => {
                        if (typeof item === 'string' || typeof item === 'number') {
                            return String(item) === String(idUnicoLinea);
                        } else if (item && item.id) {
                            return String(item.id) === String(idUnicoLinea);
                        }
                        return false;
                    });
                }
            } catch (e) {
                console.error('Error parseando especificaciones:', e);
            }
        }
        
        // El checkbox principal está marcado si hay datos guardados para esta sublínea
        const isChecked = sublineaData !== null;
        // Comparación flexible para manejar números y strings
        const mostrarChecked = sublineaData && (sublineaData.m === 1 || sublineaData.m === '1' || sublineaData.mostrar === true);
        const ofertaChecked = sublineaData && (sublineaData.o === 1 || sublineaData.o === '1' || sublineaData.oferta === true);
        const usarImagenesProducto = sublineaData && sublineaData.usarImagenesProducto === true;
        const imagenesSublinea = sublineaData && Array.isArray(sublineaData.img) ? sublineaData.img : [];
        const numImagenes = imagenesSublinea.length;
        const textoAlternativo = sublineaData && sublineaData.textoAlternativo ? sublineaData.textoAlternativo : '';
        
        const divIntermedia = document.createElement('div');
        divIntermedia.className = 'linea-intermedia-producto flex items-center gap-3 border-l-4 border-blue-400 pl-3 py-2 bg-blue-50 dark:bg-blue-900/20 rounded mb-2';
        divIntermedia.dataset.id = idIntermedia;
        divIntermedia.dataset.idUnico = idUnicoLinea;
        divIntermedia.dataset.slug = slugLinea;
        divIntermedia.draggable = false;
        
        divIntermedia.innerHTML = `
            <div class="drag-handle-intermedia-producto cursor-move text-gray-400 hover:text-gray-600 dark:hover:text-gray-300" title="Arrastrar para reordenar" draggable="true">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"></path>
                </svg>
            </div>
            <label class="flex items-center gap-2 cursor-pointer flex-1">
                <input type="checkbox" class="especificacion-producto-checkbox rounded border-gray-300 text-green-600 focus:ring-green-500" data-principal-id="${principalId}" data-sublinea-id="${idUnicoLinea}" data-sublinea-texto="${texto.replace(/"/g, '&quot;')}" ${isChecked ? 'checked' : ''}>
                <input type="text" 
                       class="linea-intermedia-producto-texto flex-1 px-3 py-2 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-white border border-gray-300 dark:border-gray-600" 
                       placeholder="Texto sublínea"
                       value="${texto}">
            </label>
            ${esColumna ? `
            <input type="text" class="texto-alternativo-sublinea-producto-input flex-1 max-w-xs px-2 py-1 text-xs border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-200 focus:outline-none focus:ring-2 focus:ring-blue-500" data-principal-id="${principalId}" data-sublinea-id="${idUnicoLinea}" placeholder="Texto alternativo (opcional)" value="${textoAlternativo.replace(/"/g, '&quot;')}" ${!isChecked ? 'disabled' : ''} style="display: ${esColumna ? 'block' : 'none'};">
            ` : ''}
            <div class="flex items-center gap-1">
                <label class="flex items-center gap-1 ${isChecked ? 'cursor-pointer' : 'cursor-not-allowed opacity-50'}" title="Mostrar en comparador">
                    <input type="checkbox" class="especificacion-producto-mostrar-checkbox rounded border-gray-300 text-blue-600 focus:ring-blue-500" data-principal-id="${principalId}" data-sublinea-id="${idUnicoLinea}" ${mostrarChecked ? 'checked' : ''} ${!isChecked ? 'disabled' : ''}>
                    <span class="text-xs text-gray-600 dark:text-gray-400">Mostrar</span>
                </label>
            </div>
            <div class="flex items-center gap-1">
                <label class="flex items-center gap-1 ${isChecked ? 'cursor-pointer' : 'cursor-not-allowed opacity-50'}" title="Disponible para ofertas">
                    <input type="checkbox" class="especificacion-producto-oferta-checkbox rounded border-gray-300 text-purple-600 focus:ring-purple-500" data-principal-id="${principalId}" data-sublinea-id="${idUnicoLinea}" ${ofertaChecked ? 'checked' : ''} ${!isChecked ? 'disabled' : ''}>
                    <span class="text-xs text-gray-600 dark:text-gray-400">Oferta</span>
                </label>
            </div>
            <div class="flex items-center gap-2">
                ${numImagenes > 0 ? `
                <button type="button" class="btn-ver-imagenes-sublinea-producto text-xs px-2 py-1 bg-blue-600 hover:bg-blue-700 text-white rounded transition-colors ${!isChecked || usarImagenesProducto ? 'opacity-50 cursor-not-allowed' : ''}" data-principal-id="${principalId}" data-sublinea-id="${idUnicoLinea}" title="Ver ${numImagenes} imagen${numImagenes > 1 ? 'es' : ''}" ${!isChecked || usarImagenesProducto ? 'disabled' : ''}>${numImagenes} img</button>
                ` : ''}
                <button type="button" class="btn-añadir-imagen-sublinea-producto text-xs px-2 py-1 bg-green-600 hover:bg-green-700 text-white rounded transition-colors ${!isChecked || usarImagenesProducto ? 'opacity-50 cursor-not-allowed' : ''}" data-principal-id="${principalId}" data-sublinea-id="${idUnicoLinea}" title="Añadir imágenes" ${!isChecked || usarImagenesProducto ? 'disabled' : ''}>+imágenes</button>
                <div class="flex items-center gap-1">
                    <label class="flex items-center gap-1 ${isChecked ? 'cursor-pointer' : 'cursor-not-allowed opacity-50'}" title="Usar imágenes del producto">
                        <input type="checkbox" class="especificacion-producto-usar-imagenes-producto-checkbox rounded border-gray-300 text-orange-600 focus:ring-orange-500" data-principal-id="${principalId}" data-sublinea-id="${idUnicoLinea}" ${usarImagenesProducto ? 'checked' : ''} ${!isChecked ? 'disabled' : ''}>
                        <span class="text-xs text-gray-600 dark:text-gray-400">Imag. Producto</span>
                    </label>
                </div>
            </div>
            <button type="button" class="btn-eliminar-intermedia-producto bg-red-500 hover:bg-red-600 text-white px-2 py-1 rounded text-xs" title="Eliminar línea intermedia">
                -
            </button>
            <button type="button" class="btn-añadir-intermedia-producto bg-green-500 hover:bg-green-600 text-white px-2 py-1 rounded text-xs" title="Añadir línea intermedia debajo">
                +
            </button>
        `;
        
        containerPadre.appendChild(divIntermedia);
        
        // Configurar eventos
        const inputTexto = divIntermedia.querySelector('.linea-intermedia-producto-texto');
        const checkboxPrincipal = divIntermedia.querySelector('.especificacion-producto-checkbox');
        const mostrarCheckbox = divIntermedia.querySelector('.especificacion-producto-mostrar-checkbox');
        const ofertaCheckbox = divIntermedia.querySelector('.especificacion-producto-oferta-checkbox');
        const usarImagenesCheckbox = divIntermedia.querySelector('.especificacion-producto-usar-imagenes-producto-checkbox');
        const btnEliminar = divIntermedia.querySelector('.btn-eliminar-intermedia-producto');
        const btnAñadir = divIntermedia.querySelector('.btn-añadir-intermedia-producto');
        const textoAlternativoInput = divIntermedia.querySelector('.texto-alternativo-sublinea-producto-input');
        
        inputTexto.addEventListener('dragstart', (e) => {
            e.preventDefault();
            e.stopPropagation();
        });
        
        inputTexto.addEventListener('input', function() {
            // Actualizar slug cuando cambia el texto (siempre regenerar para asegurar que esté correcto)
            const textoActual = this.value.trim();
            if (textoActual) {
                divIntermedia.dataset.slug = generarSlugProducto(textoActual);
            }
            if (checkboxPrincipal) {
                checkboxPrincipal.dataset.sublineaTexto = textoActual;
            }
            actualizarJSONProducto();
        });

        inputTexto.addEventListener('keydown', function(e) {
            if (e.key !== 'Tab' || e.shiftKey) return;

            e.preventDefault();

            const containerSub = lineaPrincipal.querySelector('.subprincipales-producto-container');
            const sublineas = containerSub ? Array.from(containerSub.querySelectorAll('.linea-intermedia-producto')) : [];
            const esPrimera = sublineas[0] === divIntermedia;
            const esUltima = sublineas.length > 0 && sublineas[sublineas.length - 1] === divIntermedia;
            const tieneTexto = this.value.trim() !== '';

            if (!tieneTexto && !esPrimera) {
                const indice = sublineas.indexOf(divIntermedia);
                const sublineaAnterior = indice > 0 ? sublineas[indice - 1] : null;
                divIntermedia.remove();
                actualizarJSONProducto();
                actualizarBotonesEliminarSublineasProducto(lineaPrincipal);
                actualizarVisibilidadFormatoProducto(lineaPrincipal);
                if (sublineaAnterior) {
                    const inputAnterior = sublineaAnterior.querySelector('.linea-intermedia-producto-texto');
                    if (inputAnterior) {
                        inputAnterior.focus();
                    }
                }
                return;
            }

            if (!tieneTexto && esPrimera) {
                marcarSublineaProductoMostrarOferta(divIntermedia, lineaPrincipal);
                return;
            }

            const mostrarYaMarcado = mostrarCheckbox && mostrarCheckbox.checked;
            const ofertaYaMarcado = ofertaCheckbox && ofertaCheckbox.checked;

            if (!mostrarYaMarcado || !ofertaYaMarcado) {
                marcarSublineaProductoMostrarOferta(divIntermedia, lineaPrincipal);
                return;
            }

            if (tieneTexto && esUltima) {
                const nuevaLinea = crearLineaIntermediaProducto(containerPadre, lineaPrincipal, '');
                divIntermedia.insertAdjacentElement('afterend', nuevaLinea);
                actualizarBotonesEliminarSublineasProducto(lineaPrincipal);

                const checkboxNueva = nuevaLinea.querySelector('.especificacion-producto-checkbox');
                const inputNueva = nuevaLinea.querySelector('.linea-intermedia-producto-texto');
                if (checkboxNueva && !checkboxNueva.checked) {
                    checkboxNueva.checked = true;
                    checkboxNueva.dispatchEvent(new Event('change', { bubbles: true }));
                }
                if (inputNueva) {
                    inputNueva.focus();
                }
            }
        });
        
        checkboxPrincipal.addEventListener('change', function() {
            const checked = this.checked;
            if (mostrarCheckbox) {
                mostrarCheckbox.disabled = !checked;
                const labelMostrar = mostrarCheckbox.closest('label');
                if (labelMostrar) {
                    if (checked) {
                        labelMostrar.classList.remove('opacity-50', 'cursor-not-allowed');
                        labelMostrar.classList.add('cursor-pointer');
                    } else {
                        labelMostrar.classList.remove('cursor-pointer');
                        labelMostrar.classList.add('opacity-50', 'cursor-not-allowed');
                        mostrarCheckbox.checked = false;
                    }
                }
            }
            if (ofertaCheckbox) {
                ofertaCheckbox.disabled = !checked;
                const labelOferta = ofertaCheckbox.closest('label');
                if (labelOferta) {
                    if (checked) {
                        labelOferta.classList.remove('opacity-50', 'cursor-not-allowed');
                        labelOferta.classList.add('cursor-pointer');
                    } else {
                        labelOferta.classList.remove('cursor-pointer');
                        labelOferta.classList.add('opacity-50', 'cursor-not-allowed');
                        ofertaCheckbox.checked = false;
                    }
                }
            }
            if (usarImagenesCheckbox) {
                usarImagenesCheckbox.disabled = !checked;
                const labelImag = usarImagenesCheckbox.closest('label');
                if (labelImag) {
                    if (checked) {
                        labelImag.classList.remove('opacity-50', 'cursor-not-allowed');
                        labelImag.classList.add('cursor-pointer');
                    } else {
                        labelImag.classList.remove('cursor-pointer');
                        labelImag.classList.add('opacity-50', 'cursor-not-allowed');
                        usarImagenesCheckbox.checked = false;
                    }
                }
            }
            
            // Actualizar botones de imágenes
            const btnVerImagen = divIntermedia.querySelector('.btn-ver-imagenes-sublinea-producto');
            const btnAñadirImagen = divIntermedia.querySelector('.btn-añadir-imagen-sublinea-producto');
            const usarImagenesProducto = usarImagenesCheckbox && usarImagenesCheckbox.checked;
            const debenEstarDeshabilitados = !checked || usarImagenesProducto;
            
            if (btnVerImagen) {
                btnVerImagen.disabled = debenEstarDeshabilitados;
                if (debenEstarDeshabilitados) {
                    btnVerImagen.classList.add('opacity-50', 'cursor-not-allowed');
                } else {
                    btnVerImagen.classList.remove('opacity-50', 'cursor-not-allowed');
                }
            }
            if (btnAñadirImagen) {
                btnAñadirImagen.disabled = debenEstarDeshabilitados;
                if (debenEstarDeshabilitados) {
                    btnAñadirImagen.classList.add('opacity-50', 'cursor-not-allowed');
                } else {
                    btnAñadirImagen.classList.remove('opacity-50', 'cursor-not-allowed');
                }
            }
            
            if (textoAlternativoInput) {
                textoAlternativoInput.disabled = !checked;
            }
            actualizarJSONProducto();
            // Actualizar visibilidad del selector de formato
            actualizarVisibilidadFormatoProducto(lineaPrincipal);
        });
        
        mostrarCheckbox.addEventListener('change', function() {
            const principalId = this.dataset.principalId;
            const sublineaId = this.dataset.sublineaId;
            const usarNoColumnaImagenNombrePrecio = kpEsConfigNoColumnaGrupoImagenNombrePrecio(window.kpConfiguracionFormularioProducto);
            
            // Si se marca "Mostrar", marcar también "Oferta" de la misma sublínea
            if (this.checked) {
                if (ofertaCheckbox && !ofertaCheckbox.checked) {
                    ofertaCheckbox.checked = true;
                }
                
                if (usarNoColumnaImagenNombrePrecio) {
                    const formatoSelect = lineaPrincipal.querySelector(`.formato-visualizacion-producto-select[data-principal-id="${principalId}"]`);
                    if (formatoSelect) {
                        formatoSelect.value = 'imagen_texto_precio';
                    }
                } else {
                    // Marcar también "Columna oferta" del grupo (línea principal) si no está marcado
                    const unidadDeMedidaSelect = document.getElementById('unidadDeMedida');
                    const esUnidadUnicaLocal = unidadDeMedidaSelect && unidadDeMedidaSelect.value === 'unidadUnica';
                    const maxColumnasProducto = esUnidadUnicaLocal ? 4 : 1;
                    const columnaCheckbox = lineaPrincipal.querySelector(`.columna-oferta-producto-checkbox[data-principal-id="${principalId}"]`);
                    if (columnaCheckbox && !columnaCheckbox.checked) {
                        const containerProducto = document.getElementById('especificaciones-producto-container');
                        const columnasMarcadas = Array.from(containerProducto.querySelectorAll('.columna-oferta-producto-checkbox:checked'));
                        if (columnasMarcadas.length < maxColumnasProducto) {
                            columnaCheckbox.checked = true;
                            columnaCheckbox.dispatchEvent(new Event('change'));
                        }
                    }
                }
            }
            
            actualizarJSONProducto();
            // Actualizar visibilidad del selector de formato
            actualizarVisibilidadFormatoProducto(lineaPrincipal);
        });
        ofertaCheckbox.addEventListener('change', function() {
            if (this.checked && kpEsConfigNoColumnaGrupoImagenNombrePrecio(window.kpConfiguracionFormularioProducto)) {
                const principalId = this.dataset.principalId;
                const formatoSelect = lineaPrincipal.querySelector(`.formato-visualizacion-producto-select[data-principal-id="${principalId}"]`);
                if (formatoSelect) {
                    formatoSelect.value = 'imagen_texto_precio';
                }
                actualizarVisibilidadFormatoProducto(lineaPrincipal);
            }
            actualizarJSONProducto();
        });
        usarImagenesCheckbox.addEventListener('change', function() {
            // Actualizar estado de botones de imágenes cuando cambia el checkbox de "usar imágenes del producto"
            const btnVerImagen = divIntermedia.querySelector('.btn-ver-imagenes-sublinea-producto');
            const btnAñadirImagen = divIntermedia.querySelector('.btn-añadir-imagen-sublinea-producto');
            const checkboxPrincipal = divIntermedia.querySelector('.especificacion-producto-checkbox');
            const isChecked = checkboxPrincipal && checkboxPrincipal.checked;
            const usarImagenesProducto = this.checked;
            const debenEstarDeshabilitados = !isChecked || usarImagenesProducto;
            
            if (btnVerImagen) {
                btnVerImagen.disabled = debenEstarDeshabilitados;
                if (debenEstarDeshabilitados) {
                    btnVerImagen.classList.add('opacity-50', 'cursor-not-allowed');
                } else {
                    btnVerImagen.classList.remove('opacity-50', 'cursor-not-allowed');
                }
            }
            if (btnAñadirImagen) {
                btnAñadirImagen.disabled = debenEstarDeshabilitados;
                if (debenEstarDeshabilitados) {
                    btnAñadirImagen.classList.add('opacity-50', 'cursor-not-allowed');
                } else {
                    btnAñadirImagen.classList.remove('opacity-50', 'cursor-not-allowed');
                }
            }
            
            actualizarJSONProducto();
        });
        if (textoAlternativoInput) {
            textoAlternativoInput.addEventListener('input', actualizarJSONProducto);
        }
        
        btnEliminar.addEventListener('click', () => {
            divIntermedia.remove();
            actualizarJSONProducto();
            actualizarBotonesEliminarSublineasProducto(lineaPrincipal);
            // Actualizar visibilidad del selector de formato
            actualizarVisibilidadFormatoProducto(lineaPrincipal);
        });
        
        btnAñadir.addEventListener('click', () => {
            const nuevaLinea = crearLineaIntermediaProducto(containerPadre, lineaPrincipal, '');
            divIntermedia.insertAdjacentElement('afterend', nuevaLinea);
            actualizarJSONProducto();
            actualizarBotonesEliminarSublineasProducto(lineaPrincipal);
        });
        
        // Configurar event listeners para botones de imágenes
        const btnVerImagenes = divIntermedia.querySelector('.btn-ver-imagenes-sublinea-producto');
        const btnAñadirImagen = divIntermedia.querySelector('.btn-añadir-imagen-sublinea-producto');
        
        if (btnVerImagenes) {
            btnVerImagenes.addEventListener('click', function() {
                const principalId = this.dataset.principalId;
                const sublineaId = this.dataset.sublineaId;
                abrirModalImagenesSublinea(principalId, sublineaId);
            });
        }
        
        if (btnAñadirImagen) {
            btnAñadirImagen.addEventListener('click', function() {
                const principalId = this.dataset.principalId;
                const sublineaId = this.dataset.sublineaId;
                abrirModalAñadirImagenSublinea(principalId, sublineaId);
            });
            
            // Inicializar estado del botón según el estado actual
            const checkboxPrincipal = divIntermedia.querySelector('.especificacion-producto-checkbox');
            const usarImagenesCheckbox = divIntermedia.querySelector('.especificacion-producto-usar-imagenes-producto-checkbox');
            const isChecked = checkboxPrincipal && checkboxPrincipal.checked;
            const usarImagenesProducto = usarImagenesCheckbox && usarImagenesCheckbox.checked;
            const debeEstarDeshabilitado = !isChecked || usarImagenesProducto;
            
            if (debeEstarDeshabilitado) {
                btnAñadirImagen.disabled = true;
                btnAñadirImagen.classList.add('opacity-50', 'cursor-not-allowed');
            }
        }
        
        if (btnVerImagenes) {
            // Inicializar estado del botón según el estado actual
            const checkboxPrincipal = divIntermedia.querySelector('.especificacion-producto-checkbox');
            const usarImagenesCheckbox = divIntermedia.querySelector('.especificacion-producto-usar-imagenes-producto-checkbox');
            const isChecked = checkboxPrincipal && checkboxPrincipal.checked;
            const usarImagenesProducto = usarImagenesCheckbox && usarImagenesCheckbox.checked;
            const debeEstarDeshabilitado = !isChecked || usarImagenesProducto;
            
            if (debeEstarDeshabilitado) {
                btnVerImagenes.disabled = true;
                btnVerImagenes.classList.add('opacity-50', 'cursor-not-allowed');
            }
        }
        
        // Configurar drag and drop
        const dragHandle = divIntermedia.querySelector('.drag-handle-intermedia-producto');
        configurarDragAndDropDesdeIconoProducto(dragHandle, divIntermedia, 'intermedia');

        actualizarBotonesEliminarSublineasProducto(lineaPrincipal);
        
        return divIntermedia;
    }
    
    // Función para actualizar la visibilidad del selector de formato
    function actualizarVisibilidadFormatoProducto(lineaPrincipal) {
        const principalId = lineaPrincipal.dataset.idUnico;
        const formatoContainer = lineaPrincipal.querySelector('.formato-visualizacion-producto-container');
        
        if (!formatoContainer) return;
        
        // Verificar si hay sublíneas marcadas como "mostrar"
        const mostrarCheckboxes = lineaPrincipal.querySelectorAll(`.especificacion-producto-mostrar-checkbox[data-principal-id="${principalId}"]:checked`);
        const tieneSublineasMarcadasComoMostrar = mostrarCheckboxes.length > 0;
        
        // Mostrar/ocultar el selector de formato
        formatoContainer.style.display = tieneSublineasMarcadasComoMostrar ? 'block' : 'none';
    }
    
    // Configurar drag and drop desde el icono
    function configurarDragAndDropDesdeIconoProducto(icono, elementoPadre, tipo) {
        icono.addEventListener('dragstart', (e) => {
            elementoArrastradoProducto = elementoPadre;
            tipoArrastradoProducto = tipo;
            elementoPadre.style.opacity = '0.5';
            e.dataTransfer.effectAllowed = 'move';
            if (tipo === 'intermedia') {
                e.stopPropagation();
            }
        });
        
        icono.addEventListener('dragend', (e) => {
            elementoPadre.style.opacity = '1';
            if (elementoArrastradoProducto && tipoArrastradoProducto === tipo) {
                actualizarJSONProducto();
                if (tipo === 'intermedia') {
                    const lineaPrincipal = elementoPadre.closest('.linea-principal-producto');
                    if (lineaPrincipal) {
                        actualizarBotonesEliminarSublineasProducto(lineaPrincipal);
                    }
                }
            }
            elementoArrastradoProducto = null;
            tipoArrastradoProducto = null;
        });
        
        configurarDragAndDropProducto(elementoPadre, tipo);
    }
    
    // Configurar drag and drop para reordenar
    function configurarDragAndDropProducto(elemento, tipo) {
        elemento.addEventListener('dragover', (e) => {
            if (!elementoArrastradoProducto || elementoArrastradoProducto === elemento) return;
            if (tipoArrastradoProducto !== tipo) return;
            
            const parent = elemento.parentNode;
            if (!parent) return;
            
            if (tipo === 'intermedia') {
                const draggedParent = elementoArrastradoProducto.parentNode;
                const targetParent = elemento.parentNode;
                if (draggedParent !== targetParent) {
                    e.dataTransfer.dropEffect = 'none';
                    return;
                }
            }
            
            e.preventDefault();
            e.stopPropagation();
            e.dataTransfer.dropEffect = 'move';
            
            const rect = elemento.getBoundingClientRect();
            const mouseY = e.clientY;
            const elementMiddle = rect.top + rect.height / 2;
            const insertAfter = mouseY > elementMiddle;
            
            const siblings = Array.from(parent.children).filter(child => {
                if (tipo === 'principal') {
                    return child.classList.contains('linea-principal-producto');
                } else {
                    return child.classList.contains('linea-intermedia-producto');
                }
            });
            
            const currentIndex = siblings.indexOf(elemento);
            const draggedIndex = siblings.indexOf(elementoArrastradoProducto);
            
            if (draggedIndex === -1) return;
            if (insertAfter && draggedIndex === currentIndex + 1) return;
            if (!insertAfter && draggedIndex === currentIndex - 1) return;
            
            if (insertAfter) {
                const nextSibling = elemento.nextSibling;
                if (nextSibling && nextSibling !== elementoArrastradoProducto) {
                    parent.insertBefore(elementoArrastradoProducto, nextSibling);
                } else if (!nextSibling) {
                    parent.appendChild(elementoArrastradoProducto);
                }
            } else {
                if (elemento !== elementoArrastradoProducto) {
                    parent.insertBefore(elementoArrastradoProducto, elemento);
                }
            }
        });
        
        elemento.addEventListener('drop', (e) => {
            e.preventDefault();
            e.stopPropagation();
        });
    }
    
    // Actualizar el JSON en el campo oculto (combinando con las especificaciones de categoría)
    function actualizarJSONProducto() {
        if (!datosCargadosProducto) return;
        
        // Leer el JSON actual (que contiene las especificaciones de categoría)
        let especificacionesCompletas = {};
        if (inputHidden.value) {
            try {
                especificacionesCompletas = JSON.parse(inputHidden.value);
            } catch (e) {
                console.error('Error parseando especificaciones completas:', e);
                especificacionesCompletas = {};
            }
        }

        // Normalizar metacampos por compatibilidad con estructuras rotas ({id,c})
        const normalizarId = (v) => {
            if (v && typeof v === 'object' && !Array.isArray(v)) {
                return (typeof v.id === 'string' || typeof v.id === 'number') ? v.id : null;
            }
            return v;
        };
        const normalizarArrayIds = (arr) => {
            if (!Array.isArray(arr)) return [];
            return arr
                .map(normalizarId)
                .filter(v => (typeof v === 'string' || typeof v === 'number'))
                .map(v => String(v));
        };
        const normalizarFormatosMap = (mapa) => {
            const out = {};
            if (!mapa || typeof mapa !== 'object') return out;
            Object.entries(mapa).forEach(([k, v]) => {
                if (v && typeof v === 'object' && !Array.isArray(v)) {
                    if (typeof v.id === 'string') {
                        out[k] = v.id;
                    }
                } else if (typeof v === 'string') {
                    out[k] = v;
                }
            });
            return out;
        };

        const normalizarColumnasListaCompleto = (raw) => {
            if (raw == null) return [];
            if (Array.isArray(raw)) {
                return normalizarArrayIds(raw);
            }
            if (typeof raw === 'object') {
                return Object.keys(raw)
                    .filter(k => k && !String(k).startsWith('_'))
                    .map(k => String(k));
            }
            return [];
        };

        if (especificacionesCompletas._orden) {
            especificacionesCompletas._orden = normalizarArrayIds(especificacionesCompletas._orden);
        }
        if (especificacionesCompletas._columnas) {
            especificacionesCompletas._columnas = normalizarColumnasListaCompleto(especificacionesCompletas._columnas);
        }
        if (especificacionesCompletas._formatos) {
            especificacionesCompletas._formatos = normalizarFormatosMap(especificacionesCompletas._formatos);
        }
        
        // Asegurar que existe la sección _producto
        if (!especificacionesCompletas._producto) {
            especificacionesCompletas._producto = {};
        }
        
        const filtros = [];
        const lineasPrincipales = container.querySelectorAll('.linea-principal-producto');
        
        lineasPrincipales.forEach(lineaPrincipal => {
            const texto = lineaPrincipal.querySelector('.linea-principal-producto-texto').value.trim();
            const importante = lineaPrincipal.querySelector('.linea-principal-producto-importante').checked;
            const idUnicoPrincipal = lineaPrincipal.dataset.idUnico;
            // SIEMPRE regenerar el slug desde el texto para asegurar que esté correcto
            const slugPrincipal = texto ? generarSlugProducto(texto) : '';
            lineaPrincipal.dataset.slug = slugPrincipal;
            const columnaCheckbox = lineaPrincipal.querySelector('.columna-oferta-producto-checkbox');
            const esColumna = columnaCheckbox && columnaCheckbox.checked;
            
            const containerSubprincipales = lineaPrincipal.querySelector('.subprincipales-producto-container');
            const lineasIntermedias = containerSubprincipales ? containerSubprincipales.querySelectorAll('.linea-intermedia-producto') : [];
            
            const subprincipales = [];
            const sublineasElegidas = [];
            
            lineasIntermedias.forEach(lineaIntermedia => {
                const textoIntermedia = lineaIntermedia.querySelector('.linea-intermedia-producto-texto').value.trim();
                const idUnicoIntermedia = lineaIntermedia.dataset.idUnico;
                // SIEMPRE regenerar el slug desde el texto para asegurar que esté correcto
                const slugIntermedia = textoIntermedia ? generarSlugProducto(textoIntermedia) : '';
                lineaIntermedia.dataset.slug = slugIntermedia;
                const checkboxPrincipal = lineaIntermedia.querySelector('.especificacion-producto-checkbox');
                // Usar selectores más específicos con data attributes, igual que en categorías
                // IMPORTANTE: usar idUnicoPrincipal (no principalId) que es el ID único de la línea principal
                const mostrarCheckbox = document.querySelector(`.especificacion-producto-mostrar-checkbox[data-principal-id="${idUnicoPrincipal}"][data-sublinea-id="${idUnicoIntermedia}"]`);
                const ofertaCheckbox = document.querySelector(`.especificacion-producto-oferta-checkbox[data-principal-id="${idUnicoPrincipal}"][data-sublinea-id="${idUnicoIntermedia}"]`);
                const usarImagenesCheckbox = document.querySelector(`.especificacion-producto-usar-imagenes-producto-checkbox[data-principal-id="${idUnicoPrincipal}"][data-sublinea-id="${idUnicoIntermedia}"]`);
                const textoAlternativoInput = document.querySelector(`.texto-alternativo-sublinea-producto-input[data-principal-id="${idUnicoPrincipal}"][data-sublinea-id="${idUnicoIntermedia}"]`);
                
                // Añadir a subprincipales siempre (para la estructura)
                subprincipales.push({
                    texto: textoIntermedia,
                    id: idUnicoIntermedia,
                    slug: slugIntermedia
                });
                
                // Si tiene texto e ID, guardar la sublínea si el checkbox principal está marcado
                if (textoIntermedia && idUnicoIntermedia) {
                    // Leer los checkboxes directamente del DOM para asegurar que obtenemos el estado actual
                    const principalMarcado = checkboxPrincipal ? checkboxPrincipal.checked : false;
                    
                    // Si el checkbox principal está marcado, guardar la sublínea (igual que en categorías)
                    if (principalMarcado) {
                        // Leer los checkboxes igual que en categorías
                        const mostrarMarcado = mostrarCheckbox ? mostrarCheckbox.checked : false;
                        const ofertaMarcada = ofertaCheckbox ? ofertaCheckbox.checked : false;
                        const usarImagenesMarcado = usarImagenesCheckbox ? usarImagenesCheckbox.checked : false;
                        const textoAlternativoValue = textoAlternativoInput ? textoAlternativoInput.value.trim() : '';
                        const tieneTextoAlternativo = textoAlternativoValue.length > 0;
                        
                        // Leer imágenes guardadas de esta sublínea
                        let imagenesSublinea = [];
                        if (especificacionesCompletas[idUnicoPrincipal] && Array.isArray(especificacionesCompletas[idUnicoPrincipal])) {
                            const sublineaGuardada = especificacionesCompletas[idUnicoPrincipal].find(item => {
                                if (typeof item === 'string' || typeof item === 'number') {
                                    return String(item) === String(idUnicoIntermedia);
                                } else if (item && item.id) {
                                    return String(item.id) === String(idUnicoIntermedia);
                                }
                                return false;
                            });
                            if (sublineaGuardada && sublineaGuardada.img && Array.isArray(sublineaGuardada.img)) {
                                imagenesSublinea = sublineaGuardada.img;
                            }
                        }
                        
                        const sub = { id: idUnicoIntermedia };
                        
                        // Guardar todos los checkboxes marcados (igual que en categorías)
                        if (mostrarMarcado) {
                            sub.m = 1;
                        }
                        if (ofertaMarcada) {
                            sub.o = 1;
                        }
                        if (usarImagenesMarcado) {
                            sub.usarImagenesProducto = true;
                        }
                        // Solo guardar imágenes si no se está usando las del producto
                        if (!usarImagenesMarcado && imagenesSublinea.length > 0) {
                            sub.img = imagenesSublinea;
                        }
                        if (tieneTextoAlternativo) {
                            sub.textoAlternativo = textoAlternativoValue;
                        }
                        
                        sublineasElegidas.push(sub);
                    }
                }
            });
            
            if (texto) {
                const filtro = {
                    id: idUnicoPrincipal,
                    texto: texto,
                    slug: slugPrincipal,
                    importante: importante,
                    subprincipales: subprincipales
                };
                
                if (esColumna) {
                    filtro._esColumna = true;
                }
                
                filtros.push(filtro);
                
                // Guardar las sublíneas elegidas en el formato del JSON (usando idUnicoPrincipal como clave)
                if (sublineasElegidas.length > 0) {
                    especificacionesCompletas[idUnicoPrincipal] = sublineasElegidas;
                } else {
                    // Si no hay sublíneas elegidas, eliminar la entrada
                    delete especificacionesCompletas[idUnicoPrincipal];
                }
                
                // Guardar formato si existe
                const formatoSelect = lineaPrincipal.querySelector('.formato-visualizacion-producto-select');
                if (formatoSelect && formatoSelect.value) {
                    if (!especificacionesCompletas._formatos) {
                        especificacionesCompletas._formatos = {};
                    }
                    especificacionesCompletas._formatos[idUnicoPrincipal] = formatoSelect.value;
                }
            }
        });
        
        // Guardar las líneas principales del producto en _producto.filtros
        especificacionesCompletas._producto.filtros = filtros;
        
        // Guardar orden y columnas (combinando con las de categoría)
        const unidadDeMedidaSelectSave = document.getElementById('unidadDeMedida');
        const esUnidadUnicaSave = unidadDeMedidaSelectSave && unidadDeMedidaSelectSave.value === 'unidadUnica';
        {
            const lineasPrincipalesProducto = Array.from(container.querySelectorAll('.linea-principal-producto'));
            const ordenProducto = lineasPrincipalesProducto.map(linea => String(linea.dataset.idUnico));

            const contenedorCategoria = document.querySelector('#especificaciones-principales-container');
            const categoriaUiLista = contenedorCategoria && contenedorCategoria.querySelector('.linea-principal-especificaciones');

            const columnasCheckboxes = container.querySelectorAll('.columna-oferta-producto-checkbox:checked');
            const columnasProducto = Array.from(columnasCheckboxes).map(cb => String(cb.dataset.principalId));

            const idsProducto = new Set(filtros.map(f => String(f.id)));
            const prevColumnasArr = Array.isArray(especificacionesCompletas._columnas)
                ? especificacionesCompletas._columnas.map(String)
                : [];

            let columnasCategoria = [];
            if (categoriaUiLista) {
                const columnasCheckboxesCategoria = contenedorCategoria.querySelectorAll('.columna-oferta-checkbox:checked');
                columnasCategoria = Array.from(columnasCheckboxesCategoria).map(cb => String(cb.dataset.principalId));
            } else {
                columnasCategoria = prevColumnasArr.filter(id => !idsProducto.has(String(id)));
            }

            const todasLasColumnas = [...new Set([...columnasCategoria, ...columnasProducto])];
            especificacionesCompletas._columnas = esUnidadUnicaSave ? todasLasColumnas : todasLasColumnas.slice(0, 1);

            if (esUnidadUnicaSave) {
                if (categoriaUiLista) {
                    const lineasCat = Array.from(contenedorCategoria.querySelectorAll('.linea-principal-especificaciones'));
                    especificacionesCompletas._orden = lineasCat.map(linea => String(linea.dataset.principalId));
                } else if (ordenProducto.length > 0) {
                    especificacionesCompletas._orden = ordenProducto;
                }
            }
        }
        
        // Guardar el JSON completo
        inputHidden.value = JSON.stringify(especificacionesCompletas, null, 0);
    }
    
    // Botón añadir línea principal
    btnAñadir.addEventListener('click', () => {
        crearLineaPrincipalProducto('', false, []);
        actualizarJSONProducto();
        // Validar después de añadir
        setTimeout(() => {
            if (typeof validarEspecificacionesInternas === 'function') {
                validarEspecificacionesInternas();
            }
        }, 100);
    });
    
    // Función para validar que todas las líneas principales estén rellenadas
    function validarEspecificacionesInternas() {
        // Verificar si el checkbox "No añadir especificaciones internas" está marcado
        const checkboxNoAñadir = document.getElementById('no_anadir_especificaciones');
        if (checkboxNoAñadir && checkboxNoAñadir.checked) {
            // Si está marcado, no validar y habilitar el botón
            const btnGuardar = document.getElementById('btn_guardar');
            if (btnGuardar) {
                btnGuardar.disabled = false;
                btnGuardar.classList.remove('opacity-50', 'cursor-not-allowed', 'bg-gray-400');
                btnGuardar.classList.add('bg-pink-600', 'hover:bg-pink-700');
            }
            // Quitar resaltado rojo de todos los campos
            const lineasPrincipales = container.querySelectorAll('.linea-principal-producto');
            lineasPrincipales.forEach(lineaPrincipal => {
                const inputTexto = lineaPrincipal.querySelector('.linea-principal-producto-texto');
                if (inputTexto) {
                    inputTexto.classList.remove('border-red-500', 'border-2');
                    inputTexto.classList.add('border-gray-300', 'dark:border-gray-600');
                }
            });
            return true;
        }
        
        const lineasPrincipales = container.querySelectorAll('.linea-principal-producto');
        let todasRellenadas = true;
        const camposVacios = [];
        
        // Si no hay líneas principales, no hay nada que validar
        if (lineasPrincipales.length === 0) {
            const btnGuardar = document.getElementById('btn_guardar');
            if (btnGuardar) {
                btnGuardar.disabled = false;
                btnGuardar.classList.remove('opacity-50', 'cursor-not-allowed', 'bg-gray-400');
                btnGuardar.classList.add('bg-pink-600', 'hover:bg-pink-700');
            }
            return true;
        }
        
        lineasPrincipales.forEach(lineaPrincipal => {
            const inputTexto = lineaPrincipal.querySelector('.linea-principal-producto-texto');
            if (inputTexto) {
                const valor = inputTexto.value.trim();
                if (!valor || valor === '') {
                    todasRellenadas = false;
                    camposVacios.push(inputTexto);
                    // Resaltar en rojo
                    inputTexto.classList.add('border-red-500', 'border-2');
                    inputTexto.classList.remove('border-gray-300', 'dark:border-gray-600');
                } else {
                    // Quitar resaltado rojo si tiene valor
                    inputTexto.classList.remove('border-red-500', 'border-2');
                    inputTexto.classList.add('border-gray-300', 'dark:border-gray-600');
                }
            }
        });
        
        // Actualizar estado del botón de guardar
        const btnGuardar = document.getElementById('btn_guardar');
        if (btnGuardar) {
            if (!todasRellenadas) {
                btnGuardar.disabled = true;
                btnGuardar.classList.add('opacity-50', 'cursor-not-allowed', 'bg-gray-400');
                btnGuardar.classList.remove('bg-pink-600', 'hover:bg-pink-700');
            } else {
                btnGuardar.disabled = false;
                btnGuardar.classList.remove('opacity-50', 'cursor-not-allowed', 'bg-gray-400');
                btnGuardar.classList.add('bg-pink-600', 'hover:bg-pink-700');
            }
        }
        
        return todasRellenadas;
    }
    
    // Inicializar al cargar
    inicializarProducto();
    
    // Validar al cargar la página
    setTimeout(() => {
        validarEspecificacionesInternas();
    }, 500);
    
    // Observar cambios en el contenedor para validar cuando se añadan/eliminen líneas
    const observer = new MutationObserver(function(mutations) {
        // Validar después de cambios en el DOM
        setTimeout(() => {
            validarEspecificacionesInternas();
        }, 100);
    });
    
    if (container) {
        observer.observe(container, {
            childList: true,
            subtree: true
        });
    }
    
    // Validar cuando cambie el checkbox "No añadir especificaciones internas"
    const checkboxNoAñadir = document.getElementById('no_anadir_especificaciones');
    if (checkboxNoAñadir) {
        checkboxNoAñadir.addEventListener('change', function() {
            validarEspecificacionesInternas();
        });
    }
    
    // Actualizar JSON antes de enviar el formulario
    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', function(e) {
            // Verificar si el checkbox "No añadir especificaciones internas" está marcado
            const checkboxNoAñadir = document.getElementById('no_anadir_especificaciones');
            if (!checkboxNoAñadir || !checkboxNoAñadir.checked) {
                // Validar especificaciones internas antes de enviar solo si no está marcado el checkbox
                if (!validarEspecificacionesInternas()) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    // Mostrar mensaje de error si no hay ninguno
                    const campoVacio = container.querySelector('.linea-principal-producto-texto.border-red-500');
                    if (campoVacio) {
                        campoVacio.focus();
                        campoVacio.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                    
                    // Mostrar alerta
                    alert('Por favor, rellena todas las líneas principales de ESPECIFICACIONES INTERNAS DEL PRODUCTO antes de guardar.');
                    return false;
                }
            }
            
            // Actualizar especificaciones internas del producto PRIMERO
            actualizarJSONProducto();
            // Actualizar especificaciones internas de la categoría (sublíneas)
            // Esta función ahora preservará correctamente las especificaciones del producto
            if (typeof actualizarEspecificacionesElegidas === 'function') {
                actualizarEspecificacionesElegidas();
            }
        });
    }

    // Barra nombre y botón guardar fijos en el viewport
    (function initFijosFormularioProducto() {
        const inputNombre = document.getElementById('input_nombre');
        const inputNombreFijo = document.getElementById('input_nombre_fijo');
        const barraNombreFija = document.getElementById('barra-nombre-fija');
        const campoNombre = document.getElementById('campo-nombre-sticky');
        const btnGuardar = document.getElementById('btn_guardar');
        const btnGuardarFijo = document.getElementById('btn_guardar_fijo');

        if (inputNombre && inputNombreFijo && barraNombreFija && campoNombre) {
            let sincronizandoNombre = false;

            const syncNombreAFijo = () => {
                if (sincronizandoNombre) return;
                sincronizandoNombre = true;
                inputNombreFijo.value = inputNombre.value;
                inputNombreFijo.className = inputNombre.className;
                sincronizandoNombre = false;
            };

            const syncNombreAOriginal = () => {
                if (sincronizandoNombre) return;
                sincronizandoNombre = true;
                inputNombre.value = inputNombreFijo.value;
                inputNombre.dispatchEvent(new Event('input', { bubbles: true }));
                sincronizandoNombre = false;
            };

            inputNombre.addEventListener('input', syncNombreAFijo);
            inputNombreFijo.addEventListener('input', syncNombreAOriginal);

            const observerNombre = new IntersectionObserver((entries) => {
                const visible = entries[0].isIntersecting;
                if (!visible) {
                    syncNombreAFijo();
                    barraNombreFija.classList.remove('hidden');
                    barraNombreFija.setAttribute('aria-hidden', 'false');
                } else {
                    barraNombreFija.classList.add('hidden');
                    barraNombreFija.setAttribute('aria-hidden', 'true');
                }
            }, { threshold: 0, rootMargin: '-1px 0px 0px 0px' });

            observerNombre.observe(campoNombre);
        }

        if (btnGuardar && btnGuardarFijo) {
            const clasesFijas = ['fixed', 'bottom-4', 'right-4', 'z-40', 'shadow-lg'];

            const syncBtnGuardarFijo = () => {
                btnGuardarFijo.disabled = btnGuardar.disabled;
                btnGuardarFijo.textContent = btnGuardar.textContent;
                btnGuardarFijo.className = btnGuardar.className;
                clasesFijas.forEach(c => btnGuardarFijo.classList.add(c));
            };

            btnGuardarFijo.addEventListener('click', () => {
                if (!btnGuardar.disabled) {
                    btnGuardar.click();
                }
            });

            const observerGuardar = new IntersectionObserver((entries) => {
                const visible = entries[0].isIntersecting;
                if (visible) {
                    btnGuardarFijo.classList.add('hidden');
                } else {
                    syncBtnGuardarFijo();
                    btnGuardarFijo.classList.remove('hidden');
                }
            }, { threshold: 0 });

            observerGuardar.observe(btnGuardar);

            const mutObserver = new MutationObserver(() => {
                if (!btnGuardarFijo.classList.contains('hidden')) {
                    syncBtnGuardarFijo();
                }
            });
            mutObserver.observe(btnGuardar, {
                attributes: true,
                attributeFilter: ['disabled', 'class'],
                childList: true,
                characterData: true,
                subtree: true
            });
        }
    })();
});
</script>

{{-- Candado campo obsoleto --}}
<script>
document.addEventListener('DOMContentLoaded', function() {
    const btnCandado = document.getElementById('obsoleto-candado-btn');
    const wrapRadios = document.getElementById('obsoleto-radios-wrap');
    const iconoCerrado = document.getElementById('obsoleto-candado-icono-cerrado');
    const iconoAbierto = document.getElementById('obsoleto-candado-icono-abierto');
    const radios = document.querySelectorAll('.obsoleto-radio');
    const hiddenObsoleto = document.getElementById('obsoleto-valor-hidden');

    if (!btnCandado || !wrapRadios) return;

    let desbloqueado = false;

    function sincronizarHiddenObsoleto() {
        if (!hiddenObsoleto) return;
        const checked = document.querySelector('.obsoleto-radio:checked');
        hiddenObsoleto.value = checked ? checked.value : 'no';
    }

    function aplicarEstadoCandado() {
        if (desbloqueado) {
            btnCandado.className = 'inline-flex items-center justify-center w-7 h-7 rounded-md border border-green-400 dark:border-green-600 bg-green-50 dark:bg-green-900/30 text-green-600 dark:text-green-400 hover:bg-green-100 dark:hover:bg-green-900/50 transition';
            btnCandado.setAttribute('aria-label', 'Campo obsoleto desbloqueado');
            btnCandado.title = 'Obsoleto desbloqueado: puedes cambiar Sí/No';
            iconoCerrado.classList.add('hidden');
            iconoAbierto.classList.remove('hidden');
            wrapRadios.classList.remove('opacity-50', 'pointer-events-none', 'select-none');
            wrapRadios.querySelectorAll('label').forEach(function(label) {
                label.classList.remove('cursor-not-allowed');
                label.classList.add('cursor-pointer');
            });
            radios.forEach(function(radio) { radio.tabIndex = 0; });
        } else {
            btnCandado.className = 'inline-flex items-center justify-center w-7 h-7 rounded-md border border-red-300 dark:border-red-700 bg-red-50 dark:bg-red-900/30 text-red-600 dark:text-red-400 hover:bg-red-100 dark:hover:bg-red-900/50 transition';
            btnCandado.setAttribute('aria-label', 'Candado de control obsoleto');
            btnCandado.title = 'Candado de control: clic para poder cambiar obsoleto';
            iconoCerrado.classList.remove('hidden');
            iconoAbierto.classList.add('hidden');
            wrapRadios.classList.add('opacity-50', 'pointer-events-none', 'select-none');
            wrapRadios.querySelectorAll('label').forEach(function(label) {
                label.classList.add('cursor-not-allowed');
                label.classList.remove('cursor-pointer');
            });
            radios.forEach(function(radio) { radio.tabIndex = -1; });
            sincronizarHiddenObsoleto();
        }
    }

    radios.forEach(function(radio) {
        radio.addEventListener('change', function() {
            if (desbloqueado) {
                sincronizarHiddenObsoleto();
            }
        });
    });

    btnCandado.addEventListener('click', function() {
        if (desbloqueado) return;
        desbloqueado = true;
        aplicarEstadoCandado();
    });

    sincronizarHiddenObsoleto();
    aplicarEstadoCandado();
});
</script>

{{-- Candado campo slug --}}
<script>
document.addEventListener('DOMContentLoaded', function() {
    const btnCandado = document.getElementById('slug-candado-btn');
    const slugInput = document.getElementById('slug');
    const hiddenSlug = document.getElementById('slug-valor-hidden');
    const iconoCerrado = document.getElementById('slug-candado-icono-cerrado');
    const iconoAbierto = document.getElementById('slug-candado-icono-abierto');

    if (!btnCandado || !slugInput) return;

    let desbloqueado = false;
    const clasesBloqueado = ['opacity-50', 'cursor-not-allowed', 'bg-gray-200', 'dark:bg-gray-800'];

    function sincronizarHiddenSlug() {
        if (!hiddenSlug) return;
        hiddenSlug.value = slugInput.value;
    }

    function aplicarEstadoCandadoSlug() {
        if (desbloqueado) {
            btnCandado.className = 'inline-flex items-center justify-center w-7 h-7 rounded-md border border-green-400 dark:border-green-600 bg-green-50 dark:bg-green-900/30 text-green-600 dark:text-green-400 hover:bg-green-100 dark:hover:bg-green-900/50 transition';
            btnCandado.setAttribute('aria-label', 'Campo slug desbloqueado');
            btnCandado.title = 'Slug desbloqueado: puedes modificar el slug';
            iconoCerrado.classList.add('hidden');
            iconoAbierto.classList.remove('hidden');
            slugInput.removeAttribute('readonly');
            clasesBloqueado.forEach(function(clase) { slugInput.classList.remove(clase); });
        } else {
            btnCandado.className = 'inline-flex items-center justify-center w-7 h-7 rounded-md border border-red-300 dark:border-red-700 bg-red-50 dark:bg-red-900/30 text-red-600 dark:text-red-400 hover:bg-red-100 dark:hover:bg-red-900/50 transition';
            btnCandado.setAttribute('aria-label', 'Desbloquear campo slug');
            btnCandado.title = 'Candado de control: clic para poder cambiar el slug';
            iconoCerrado.classList.remove('hidden');
            iconoAbierto.classList.add('hidden');
            slugInput.setAttribute('readonly', 'readonly');
            clasesBloqueado.forEach(function(clase) { slugInput.classList.add(clase); });
            sincronizarHiddenSlug();
        }
    }

    slugInput.addEventListener('input', function() {
        if (desbloqueado) {
            sincronizarHiddenSlug();
        }
    });

    const form = slugInput.closest('form');
    if (form) {
        form.addEventListener('submit', function() {
            sincronizarHiddenSlug();
        });
    }

    btnCandado.addEventListener('click', function() {
        if (desbloqueado) return;
        desbloqueado = true;
        aplicarEstadoCandadoSlug();
        slugInput.focus();
    });

    sincronizarHiddenSlug();
    aplicarEstadoCandadoSlug();
});
</script>

{{-- Códigos EAN / ISBN / UPC / MPN / GTIN del producto --}}
<script>
document.addEventListener('DOMContentLoaded', function() {
    const panel = document.getElementById('codigos-producto-panel');
    const btnToggle = document.getElementById('btn-toggle-codigos-producto');
    const badge = document.getElementById('codigos-producto-badge');
    const inputHidden = document.getElementById('ean_isbn_etc_input');
    const formulario = document.querySelector('form');

    if (!panel || !btnToggle || !inputHidden) {
        return;
    }

    const TIPOS_CODIGO = ['ean', 'isbn', 'upc', 'mpn', 'gtin'];
    const ETIQUETAS = { ean: 'EAN', isbn: 'ISBN', upc: 'UPC', mpn: 'MPN', gtin: 'GTIN' };

    function escapeHtml(texto) {
        return String(texto)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function actualizarVisibilidadListaVacia(bloque) {
        const lista = bloque.querySelector('.codigos-lista');
        const vacio = bloque.querySelector('.codigos-lista-vacia');
        if (!lista || !vacio) return;
        vacio.classList.toggle('hidden', lista.querySelectorAll('.codigo-item').length > 0);
    }

    function crearFilaCodigo(tipo, valor = '') {
        const div = document.createElement('div');
        div.className = 'flex items-center gap-2 codigo-item';
        div.innerHTML = `
            <input type="text" maxlength="255"
                class="codigo-valor-input flex-1 px-3 py-2 rounded bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white border border-gray-300 dark:border-gray-600 text-sm font-mono"
                value="${escapeHtml(valor)}"
                placeholder="Código ${ETIQUETAS[tipo] || tipo.toUpperCase()}">
            <button type="button" class="btn-eliminar-codigo-producto px-3 py-2 bg-red-600 hover:bg-red-700 text-white rounded text-sm transition-colors shrink-0" title="Eliminar código">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        `;
        return div;
    }

    function actualizarJsonCodigos() {
        const estructura = {};
        TIPOS_CODIGO.forEach(function(tipo) {
            estructura[tipo] = [];
        });

        document.querySelectorAll('.codigos-tipo-block').forEach(function(bloque) {
            const tipo = bloque.dataset.tipo;
            if (!tipo || !estructura[tipo]) return;
            bloque.querySelectorAll('.codigo-valor-input').forEach(function(input) {
                const valor = input.value.trim();
                if (valor !== '' && estructura[tipo].indexOf(valor) === -1) {
                    estructura[tipo].push(valor);
                }
            });
        });

        const tieneAlguno = TIPOS_CODIGO.some(function(tipo) {
            return estructura[tipo].length > 0;
        });

        inputHidden.value = tieneAlguno ? JSON.stringify(estructura) : '';
        actualizarBadge();
    }

    function actualizarBadge() {
        if (!badge) return;
        let total = 0;
        document.querySelectorAll('.codigo-valor-input').forEach(function(input) {
            if (input.value.trim() !== '') total++;
        });
        if (total > 0) {
            badge.textContent = String(total);
            badge.classList.remove('hidden');
        } else {
            badge.textContent = '';
            badge.classList.add('hidden');
        }
    }

    btnToggle.addEventListener('click', function() {
        panel.classList.toggle('hidden');
    });

    document.querySelectorAll('.btn-anadir-codigo-producto').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const tipo = btn.dataset.tipo;
            const bloque = btn.closest('.codigos-tipo-block');
            const lista = bloque ? bloque.querySelector('.codigos-lista') : null;
            if (!lista || !tipo) return;
            const fila = crearFilaCodigo(tipo);
            lista.appendChild(fila);
            fila.querySelector('.codigo-valor-input').focus();
            actualizarVisibilidadListaVacia(bloque);
            actualizarJsonCodigos();
        });
    });

    panel.addEventListener('click', function(e) {
        const btnEliminar = e.target.closest('.btn-eliminar-codigo-producto');
        if (!btnEliminar) return;
        const fila = btnEliminar.closest('.codigo-item');
        const bloque = btnEliminar.closest('.codigos-tipo-block');
        if (fila) fila.remove();
        if (bloque) actualizarVisibilidadListaVacia(bloque);
        actualizarJsonCodigos();
    });

    panel.addEventListener('input', function(e) {
        if (e.target.classList.contains('codigo-valor-input')) {
            actualizarJsonCodigos();
        }
    });

    if (formulario) {
        formulario.addEventListener('submit', function() {
            actualizarJsonCodigos();
        });
    }

    document.querySelectorAll('.codigos-tipo-block').forEach(actualizarVisibilidadListaVacia);
    actualizarBadge();
});
</script>

</x-app-layout>
