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

    <div class="max-w-5xl mx-auto py-10 px-4 space-y-8 bg-gray-50 dark:bg-gray-900 rounded-lg shadow-md">

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

                <div id="categorias-container" class="space-y-4">
                    <!-- Los selectores de categorías se generarán dinámicamente aquí -->
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

            {{-- DATOS PRINCIPALES --}}
            <fieldset class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6 space-y-6 border border-gray-200 dark:border-gray-700">
                <legend class="text-lg font-semibold text-gray-700 dark:text-gray-200">Información general</legend>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block mb-1 font-medium text-gray-700 dark:text-gray-200">Nombre *</label>
                        <input type="text" name="nombre" value="{{ old('nombre', $producto->nombre ?? '') }}"
                            class="w-full px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border @error('nombre') border-red-500 @enderror">
                        @error('nombre') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block mb-1 font-medium text-gray-700 dark:text-gray-200">Slug *</label>
                        <input type="text" id="slug" name="slug" value="{{ old('slug', $producto->slug ?? '') }}"
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
                        <input type="text" name="marca" value="{{ old('marca', $producto->marca ?? '') }}"
                            class="w-full px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border @error('marca') border-red-500 @enderror">
                        @error('marca') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block mb-1 font-medium text-gray-700 dark:text-gray-200">Modelo *</label>
                        <input type="text" name="modelo" value="{{ old('modelo', $producto->modelo ?? '') }}"
                            class="w-full px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border @error('modelo') border-red-500 @enderror">
                        @error('modelo') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block mb-1 font-medium text-gray-700 dark:text-gray-200">Talla</label>
                        <input type="text" name="talla" value="{{ old('talla', $producto->talla ?? '') }}"
                            class="w-full px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border @error('talla') border-red-500 @enderror">
                        @error('talla') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>


                    <div class="md:col-span-2 flex gap-2">
                        <button type="button" id="rellenar-info-automatica" 
                            class="bg-green-600 hover:bg-green-700 text-white font-semibold px-4 py-2 rounded-md transition duration-150 ease-in-out focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
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

                    <div>
                        <label class="block mb-1 font-medium text-gray-700 dark:text-gray-200">Precio (€)</label>
                        <input type="number" step="0.01" name="precio" value="{{ old('precio', $producto->precio ?? '') }}"
                            class="w-full px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border @error('precio') border-red-500 @enderror">
                        @error('precio') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block mb-1 font-medium text-gray-700 dark:text-gray-200">¿Obsoleto? *</label>
                        <div class="flex gap-6">
                            <label class="inline-flex items-center">
                                <input type="radio" name="obsoleto" value="no"
                                    {{ old('obsoleto', $producto->obsoleto ?? 'no') === 'no' ? 'checked' : '' }}
                                    class="form-radio text-pink-600">
                                <span class="ml-2 text-gray-700 dark:text-gray-200">No</span>
                            </label>
                            <label class="inline-flex items-center">
                                <input type="radio" name="obsoleto" value="si"
                                    {{ old('obsoleto', $producto->obsoleto ?? 'no') === 'si' ? 'checked' : '' }}
                                    class="form-radio text-pink-600">
                                <span class="ml-2 text-gray-700 dark:text-gray-200">Sí</span>
                            </label>
                        </div>
                    </div>

                    <div>
                        <label class="block mb-1 font-medium text-gray-700 dark:text-gray-200">¿Mostrar? *</label>
                        <div class="flex gap-6">
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

                </div>
            </fieldset>

            {{-- IMÁGENES --}}
            <fieldset class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6 space-y-4 border border-gray-200 dark:border-gray-700">
                <legend class="text-lg font-semibold text-gray-700 dark:text-gray-200">Imágenes</legend>

                @php
                    // Convertir imágenes existentes a formato JSON si es necesario
                    $imagenesGrandes = old('imagenes_grandes', $producto && $producto->imagen_grande ? (is_array($producto->imagen_grande) ? $producto->imagen_grande : [$producto->imagen_grande]) : []);
                    $imagenesPequenas = old('imagenes_pequenas', $producto && $producto->imagen_pequena ? (is_array($producto->imagen_pequena) ? $producto->imagen_pequena : [$producto->imagen_pequena]) : []);
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
                    <label class="block mb-2 font-medium text-gray-700 dark:text-gray-200">Palabras clave disponibles</label>
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
                {{-- A├æADIR OFERTA --}}
                @if ($producto && $producto->id)
                <a href="{{ route('admin.ofertas.create', ['producto' => $producto->id]) }}"
                    target="_blank"
                    class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-6 py-3 rounded-md shadow-md inline-block text-center">
                    + Añadir oferta
                </a>
                @endif

                {{-- BOTÓN GUARDAR --}}
                <button type="submit"
                    class="inline-flex items-center bg-pink-600 hover:bg-pink-700 text-white font-semibold text-base px-6 py-3 rounded-md shadow-md transition duration-150 ease-in-out focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-pink-500">
                    Guardar producto
                </button>
            </div>


        </form>

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
                            </div>
                            <input type="datetime-local" id="fecha-aviso" name="fecha_aviso" 
                                class="w-full px-3 py-2 border border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200" 
                                required>
                        </div>
                        
                        <div class="mb-4">
                            <label class="flex items-center">
                                <input type="checkbox" id="oculto" name="oculto" class="rounded border-gray-300 text-pink-600 focus:ring-pink-500">
                                <span class="ml-2 text-sm text-gray-300 dark:text-gray-300">Ocultar aviso</span>
                            </label>
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
    <div id="modal-añadir-imagen" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white dark:bg-gray-900 rounded-lg p-6 max-w-4xl w-full relative shadow-xl overflow-y-auto max-h-[90vh]">
            <button onclick="cerrarModalAñadirImagen()" class="absolute top-3 right-4 text-xl text-gray-800 dark:text-gray-100 hover:text-gray-600 dark:hover:text-gray-300">×</button>
            <div class="mb-4">
                <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-200">Añadir nueva imagen</h3>
            </div>
            
            <!-- Pestañas dentro del modal -->
            <div class="border-b border-gray-200 dark:border-gray-600 mb-4">
                <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                    <button type="button" id="tab-url-nueva" class="tab-modal border-b-2 border-blue-500 py-2 px-1 text-sm font-medium text-blue-600 dark:text-blue-400">
                        Descargar desde URL
                    </button>
                    <button type="button" id="tab-subir-nueva" class="tab-modal border-b-2 border-transparent py-2 px-1 text-sm font-medium text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300">
                        Subir imagen
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
            <div class="border-b border-gray-200 dark:border-gray-600 mb-4">
                <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                    <button type="button" id="tab-url-sublinea" class="tab-modal-sublinea border-b-2 border-blue-500 py-2 px-1 text-sm font-medium text-blue-600 dark:text-blue-400">
                        Descargar desde URL
                    </button>
                    <button type="button" id="tab-subir-sublinea" class="tab-modal-sublinea border-b-2 border-transparent py-2 px-1 text-sm font-medium text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300">
                        Subir imagen
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

        // Copiar nombre al slug después de 1 segundo de inactividad
        let nombreTimeout = null;
        const nombreInput = document.querySelector('input[name="nombre"]');
        const slugInput = document.getElementById('slug');
        
        if (nombreInput && slugInput) {
            nombreInput.addEventListener('input', function() {
                // Limpiar timeout anterior
                if (nombreTimeout) {
                    clearTimeout(nombreTimeout);
                }
                
                // Crear nuevo timeout de 1 segundo
                nombreTimeout = setTimeout(() => {
                    const nombre = nombreInput.value.trim();
                    
                    // Solo copiar si hay contenido y el slug está vacío o coincide con el nombre anterior
                    if (nombre) {
                        // Normalizar el nombre igual que se hace con el slug
                        const slugNormalizado = nombre
                            .normalize("NFD").replace(/[\u0300-\u036f]/g, '')
                            .replace(/[^a-zA-Z0-9\s-]/g, '')
                            .trim().replace(/\s+/g, '-').toLowerCase();
                        
                        // Solo actualizar si el slug está vacío o si el usuario no lo ha modificado manualmente
                        const slugActual = slugInput.value.trim();
                        if (!slugActual || slugActual === '') {
                            slugInput.value = slugNormalizado;
                            // Disparar evento input para activar la validación del slug
                            slugInput.dispatchEvent(new Event('input'));
                        }
                    }
                }, 1000);
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
    <div id="datos-categorias"
        data-categorias-raiz="{{ json_encode($categoriasRaiz) }}"
        data-categoria-id="{{ old('categoria_id', $producto->categoria_id ?? '') }}">
    </div>

    <script>
        // Variables globales
        let categoriasRaiz = [];
        let categoriaProducto = null;

        // Inicialización cuando se carga la página
        document.addEventListener('DOMContentLoaded', () => {
            const datos = document.getElementById('datos-categorias');
            categoriasRaiz = JSON.parse(datos.dataset.categoriasRaiz || '[]');
            categoriaProducto = datos.dataset.categoriaId || null;

            if (categoriaProducto) {
                // Es un producto existente - cargar toda la jerarquía
                cargarJerarquiaCompleta(categoriaProducto);
            } else {
                // Es un producto nuevo - crear solo el primer selector
                crearSelectorCategoria(0, categoriasRaiz, null);
            }

            // Configurar botones
            configurarBotones();
        });

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
            // Obtener el último selector con valor
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

        function cargarJerarquiaCompleta(categoriaId) {
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
                    construirJerarquiaCompleta(data.jerarquia);
                }
            })
            .catch(error => {
                console.error('Error cargando jerarquía:', error);
                // Fallback: crear selector básico
                crearSelectorCategoria(0, categoriasRaiz, null);
            });
        }

        function construirJerarquiaCompleta(jerarquia) {
            // Limpiar contenedor
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
            document.getElementById('categoria-final').value = categoriaProducto;
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
            const container = document.getElementById('categorias-container');
            container.innerHTML = '';
            document.getElementById('categoria-final').value = '';
            // Solo crear el primer selector, no duplicar
            crearSelectorCategoria(0, categoriasRaiz, null);
        }

        function actualizarCategoriaFinal() {
            const selectores = document.querySelectorAll('.categoria-select');
            let categoriaFinal = null;
            
            // Buscar el último selector con valor
            for (let i = selectores.length - 1; i >= 0; i--) {
                if (selectores[i].value) {
                    categoriaFinal = selectores[i].value;
                    break;
                }
            }
            
            document.getElementById('categoria-final').value = categoriaFinal || '';
            
            // Actualizar indicador visual del botón agregar
            actualizarIndicadorBotonAgregar();
        }

        function actualizarIndicadorBotonAgregar() {
            const btnAgregar = document.getElementById('agregar-categoria');
            if (!btnAgregar) return;

            // Obtener el último selector con valor
            const selectores = document.querySelectorAll('.categoria-select');
            let ultimaCategoriaId = null;

            for (let i = selectores.length - 1; i >= 0; i--) {
                if (selectores[i].value) {
                    ultimaCategoriaId = selectores[i].value;
                    break;
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
            const productoId = document.getElementById('form-producto').dataset.productoId || null;
            
            // Mostrar contenedor
            contenedor.classList.remove('hidden');
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
        
        // Función para mostrar las palabras clave como botones
        function mostrarPalabrasClaveRelacionadas(palabrasClave) {
            const botonesContainer = document.getElementById('palabras-clave-relacionadas-botones');
            botonesContainer.innerHTML = '';
            
            palabrasClave.forEach(palabraData => {
                // Contenedor para el botón y el icono
                const contenedorBoton = document.createElement('div');
                contenedorBoton.className = 'inline-flex items-center gap-1';
                
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
        });

        // Observar cambios en la categoría final
        const categoriaFinal = document.getElementById('categoria-final');
        if (categoriaFinal) {
            // Escuchar cambios en el valor del campo
            categoriaFinal.addEventListener('change', function() {
                setTimeout(autoSeleccionarCategoriaPrincipal, 300);
                // Auto-seleccionar categoría para especificaciones internas si no hay una seleccionada
                autoSeleccionarCategoriaEspecificaciones();
            });
            
            // También observar cambios programáticos en el valor
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.type === 'attributes' && mutation.attributeName === 'value') {
                        setTimeout(autoSeleccionarCategoriaPrincipal, 300);
                        // Auto-seleccionar categoría para especificaciones internas si no hay una seleccionada
                        autoSeleccionarCategoriaEspecificaciones();
                    }
                });
            });
            
            observer.observe(categoriaFinal, { attributes: true, attributeFilter: ['value'] });
        }
        
        // Función para auto-seleccionar categoría para especificaciones internas
        function autoSeleccionarCategoriaEspecificaciones() {
            const categoriaEspecificacionesId = document.getElementById('categoria_especificaciones_id');
            const categoriaEspecificacionesNombre = document.getElementById('categoria_especificaciones_nombre');
            const categoriaFinal = document.getElementById('categoria-final');
            
            // Solo si no hay una categoría ya seleccionada para especificaciones internas
            if (categoriaEspecificacionesId && !categoriaEspecificacionesId.value && categoriaFinal && categoriaFinal.value) {
                // Buscar la categoría seleccionada
                fetch(`/panel-privado/productos/buscar/categorias?q=`)
                    .then(response => response.json())
                    .then(categorias => {
                        const categoriaSeleccionada = categorias.find(cat => cat.id == categoriaFinal.value);
                        if (categoriaSeleccionada) {
                            // Establecer la categoría en el campo de especificaciones internas
                            categoriaEspecificacionesId.value = categoriaSeleccionada.id;
                            categoriaEspecificacionesNombre.value = categoriaSeleccionada.nombre;
                            
                            // Cargar las especificaciones internas de esta categoría
                            obtenerEspecificacionesInternas(categoriaSeleccionada.id);
                        }
                    })
                    .catch(error => console.error('Error al auto-seleccionar categoría para especificaciones:', error));
            }
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
            
            // Si el checkbox "No añadir" está marcado, no cargar
            if (checkboxNoAnadir && checkboxNoAnadir.checked) {
                return;
            }
            
            try {
                const response = await fetch(`/panel-privado/productos/categoria/${categoriaId}/especificaciones-internas`);
                const data = await response.json();
                
                if (data.error) {
                    contenidoContainer.innerHTML = `<p class="text-red-500 text-sm">${data.error}</p>`;
                    seleccionContainer.classList.remove('hidden');
                    return;
                }
                
                const especificaciones = data.especificaciones_internas;
                
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
            const normalizarFormato = (v) => {
                if (v && typeof v === 'object' && !Array.isArray(v)) {
                    return typeof v.id === 'string' ? v.id : 'texto';
                }
                return (typeof v === 'string') ? v : 'texto';
            };

            // Verificar si la unidad de medida es "Unidad Única"
            const unidadDeMedidaSelect = document.getElementById('unidadDeMedida');
            const esUnidadUnica = unidadDeMedidaSelect && unidadDeMedidaSelect.value === 'unidadUnica';
            
            // Obtener orden guardado y columnas marcadas
            const ordenGuardado = normalizarArrayIds(opcionesGuardadas._orden || []);
            const columnasGuardadas = normalizarArrayIds(opcionesGuardadas._columnas || []);
            
            // Ordenar filtros según el orden guardado, o mantener el orden original
            let filtrosOrdenados = [...especificaciones.filtros];
            if (ordenGuardado.length > 0) {
                const filtrosMap = new Map(filtrosOrdenados.map(f => [f.id, f]));
                filtrosOrdenados = ordenGuardado
                    .map(id => filtrosMap.get(id))
                    .filter(f => f !== undefined)
                    .concat(filtrosOrdenados.filter(f => !ordenGuardado.includes(f.id)));
            }
            
            let html = '<p class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-4">Selecciona las opciones deseadas en cada línea principal:</p>';
            html += '<div id="especificaciones-principales-container" class="space-y-4">';
            
            filtrosOrdenados.forEach((filtro, index) => {
                const idPrincipal = filtro.id;
                const textoPrincipal = filtro.texto || `Línea principal ${index + 1}`;
                const subprincipales = filtro.subprincipales || [];
                const opcionesSeleccionadas = opcionesGuardadas[idPrincipal] || [];
                const esColumna = columnasGuardadas.includes(idPrincipal);
                
                html += `<div class="linea-principal-especificaciones border border-gray-300 dark:border-gray-600 rounded-lg p-4 bg-white dark:bg-gray-800" data-principal-id="${idPrincipal}" draggable="false">`;
                
                // Header de la línea principal con drag handle, checkbox columna (si es unidadUnica) y label
                html += `<div class="flex items-center gap-3 mb-2">`;
                
                // Icono de drag (solo si es unidadUnica)
                if (esUnidadUnica) {
                    html += `<div class="drag-handle-principal-especificaciones cursor-move text-gray-400 hover:text-gray-600 dark:hover:text-gray-300" title="Arrastrar para reordenar" draggable="true">`;
                    html += `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">`;
                    html += `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"></path>`;
                    html += `</svg>`;
                    html += `</div>`;
                }
                
                // Nombre de la línea principal y texto "Aplica Filtro Categoria" si es importante
                html += `<label class="flex-1 block font-medium text-gray-700 dark:text-gray-200">${textoPrincipal}${filtro.importante ? ' <span class="text-yellow-600 dark:text-yellow-400">(Aplica Filtro Categoria)</span>' : ''}</label>`;
                
                // Checkbox "Columna oferta" (solo si es unidadUnica) - ahora a la derecha
                if (esUnidadUnica) {
                    html += `<div class="flex items-center gap-2">`;
                    html += `<label class="flex items-center gap-1 cursor-pointer">`;
                    html += `<input type="checkbox" class="columna-oferta-checkbox rounded border-gray-300 text-orange-600 focus:ring-orange-500" data-principal-id="${idPrincipal}" ${esColumna ? 'checked' : ''}>`;
                    html += `<span class="text-xs text-gray-600 dark:text-gray-400 font-medium">Columna oferta</span>`;
                    html += `</label>`;
                    
                    // Icono de ayuda "?" con tooltip (click)
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
                
                // Desplegable
                html += `<details class="mb-2">`;
                html += `<summary class="cursor-pointer text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 font-medium">Seleccionar opciones</summary>`;
                html += `<div class="mt-2 space-y-2 pl-4 border-l-2 border-gray-300 dark:border-gray-600">`;
                
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
                    const mostrarChecked = sublineaData && (sublineaData.m === 1 || sublineaData.m === '1' || sublineaData.mostrar === true);
                    const ofertaChecked = sublineaData && (sublineaData.o === 1 || sublineaData.o === '1' || sublineaData.oferta === true);
                    // Leer imágenes de la sublínea
                    const imagenesSublinea = sublineaData && Array.isArray(sublineaData.img) ? sublineaData.img : [];
                    const numImagenes = imagenesSublinea.length;
                    
                    // Leer texto alternativo guardado si existe
                    const textoAlternativo = sublineaData && sublineaData.textoAlternativo ? sublineaData.textoAlternativo : '';
                    
                    html += `<div class="flex items-center gap-3 p-2 rounded hover:bg-gray-100 dark:hover:bg-gray-700">`;
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
                    seleccionadas.forEach(sub => {
                        html += `<span class="inline-flex items-center gap-1 px-2 py-1 text-xs bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 rounded">`;
                        html += `<svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>`;
                        html += `<span>${sub.texto || '(Sin texto)'}</span>`;
                        html += `</span>`;
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
            
            // Configurar drag and drop si es unidadUnica
            if (esUnidadUnica) {
                configurarDragAndDropEspecificaciones(contenedor);
            }
            
            // Añadir event listeners a los checkboxes de "Columna oferta" (solo si es unidadUnica)
            if (esUnidadUnica) {
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
                        if (columnasMarcadas.length > 4) {
                            this.checked = false;
                            alert('Solo se pueden marcar hasta 4 líneas principales como columna oferta.');
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
                    
                    // Si se desmarca, desmarcar también "Mostrar", "Oferta" y "Usar imágenes del producto"
                    if (!this.checked) {
                        if (mostrarCheckbox) mostrarCheckbox.checked = false;
                        if (ofertaCheckbox) ofertaCheckbox.checked = false;
                        if (usarImagenesProductoCheckbox) usarImagenesProductoCheckbox.checked = false;
                    }
                    
                    // Habilitar/deshabilitar campo de texto alternativo si existe
                    const textoAlternativoInput = contenedor.querySelector(`.texto-alternativo-sublinea-input[data-principal-id="${principalId}"][data-sublinea-id="${sublineaId}"]`);
                    if (textoAlternativoInput) {
                        textoAlternativoInput.disabled = !this.checked;
                    }
                    
                    actualizarEspecificacionesElegidas();
                    actualizarVisibilidadFormatoVisualizacion(contenedor);
                });
            });
            
            // Añadir event listeners a los checkboxes de "Mostrar", "Oferta" y "Columna"
            const mostrarCheckboxes = contenedor.querySelectorAll('.especificacion-mostrar-checkbox');
            mostrarCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    actualizarEspecificacionesElegidas();
                    actualizarVisibilidadFormatoVisualizacion(contenedor);
                });
            });
            
            const ofertaCheckboxes = contenedor.querySelectorAll('.especificacion-oferta-checkbox');
            ofertaCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', actualizarEspecificacionesElegidas);
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
            
            // Configurar event listeners para botones de imágenes de sublíneas
            configurarBotonesImagenesSublineas(contenedor);
            
            // Actualizar visibilidad de los desplegables de formato
            actualizarVisibilidadFormatoVisualizacion(contenedor);
            
            // Actualizar JSON inicial
            actualizarEspecificacionesElegidas();
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
                if (idsProducto.includes(principalId)) {
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
            if (esUnidadUnica) {
                const contenedorPrincipal = document.querySelector('#especificaciones-principales-container');
                if (contenedorPrincipal) {
                    const lineasPrincipales = Array.from(contenedorPrincipal.querySelectorAll('.linea-principal-especificaciones'));
                    const orden = lineasPrincipales.map(linea => linea.dataset.principalId);
                    especificaciones._orden = orden;
                    
                    // Obtener columnas de categoría (solo las marcadas actualmente)
                    const columnasCheckboxes = contenedorPrincipal.querySelectorAll('.columna-oferta-checkbox:checked');
                    const columnasCategoria = Array.from(columnasCheckboxes).map(cb => cb.dataset.principalId);
                    
                    // Obtener columnas del producto (del contenedor de producto, solo las marcadas actualmente)
                    const contenedorProducto = document.querySelector('#especificaciones-producto-container');
                    let columnasProducto = [];
                    if (contenedorProducto) {
                        const columnasCheckboxesProducto = contenedorProducto.querySelectorAll('.columna-oferta-producto-checkbox:checked');
                        columnasProducto = Array.from(columnasCheckboxesProducto).map(cb => cb.dataset.principalId);
                    }
                    
                    // Combinar solo las columnas actualmente marcadas: categoría + producto, eliminando duplicados
                    const todasLasColumnas = [...new Set([...columnasCategoria, ...columnasProducto])];
                    especificaciones._columnas = todasLasColumnas;
                }
            }
            
            // Preservar TODAS las especificaciones del producto (_producto) si existen
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
                        especificacionesGuardadas._producto.filtros.some(f => f.id === key)) {
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
        };
        
        // Renderizar miniaturas en el modal de imágenes de sublínea
        function renderizarMiniaturasSublinea() {
            const container = document.getElementById('miniaturas-container-sublinea');
            const imgGrande = document.getElementById('imagen-grande-sublinea');
            container.innerHTML = '';
            
            if (sublineaImagenesActual.imagenes.length === 0) {
                imgGrande.src = '';
                imgGrande.alt = 'No hay imágenes';
                container.innerHTML = '<p class="text-sm text-gray-500 dark:text-gray-400">No hay imágenes</p>';
                return;
            }
            
            // Mostrar primera imagen como grande
            if (sublineaImagenesActual.imagenes[0]) {
                imgGrande.src = `{{ asset('images/') }}/${sublineaImagenesActual.imagenes[0]}`;
                imgGrande.alt = 'Imagen 1';
            }
            
            // Renderizar miniaturas
            sublineaImagenesActual.imagenes.forEach((imgPath, index) => {
                const div = document.createElement('div');
                div.className = 'relative group miniatura-sublinea cursor-pointer border-2 border-gray-300 dark:border-gray-600 rounded p-1';
                div.dataset.index = index;
                div.draggable = true;
                if (index === 0) {
                    div.classList.add('border-blue-500');
                }
                
                div.innerHTML = `
                    <img src="{{ asset('images/') }}/${imgPath}" alt="Miniatura ${index + 1}" class="w-full h-20 object-cover rounded">
                    <button type="button" class="absolute top-0 right-0 bg-red-600 hover:bg-red-700 text-white rounded-full w-5 h-5 flex items-center justify-center text-xs opacity-0 group-hover:opacity-100 transition-opacity btn-eliminar-imagen-sublinea" data-index="${index}">×</button>
                `;
                
                div.addEventListener('click', () => {
                    imgGrande.src = `{{ asset('images/') }}/${imgPath}`;
                    imgGrande.alt = `Imagen ${index + 1}`;
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
            
            sublineaImagenesActual.imagenes.splice(index, 1);
            guardarImagenesSublinea();
            renderizarMiniaturasSublinea();
        }
        
        // Guardar imágenes de sublínea en el JSON
        function guardarImagenesSublinea() {
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
            const index = especificaciones[sublineaImagenesActual.principalId].findIndex(item => {
                if (typeof item === 'string' || typeof item === 'number') {
                    return String(item) === String(sublineaImagenesActual.sublineaId);
                } else if (item && item.id) {
                    return String(item.id) === String(sublineaImagenesActual.sublineaId);
                }
                return false;
            });
            
            if (index !== -1) {
                // Actualizar imágenes en el item existente
                if (typeof especificaciones[sublineaImagenesActual.principalId][index] === 'object' && especificaciones[sublineaImagenesActual.principalId][index].id) {
                    if (sublineaImagenesActual.imagenes.length > 0) {
                        especificaciones[sublineaImagenesActual.principalId][index].img = sublineaImagenesActual.imagenes;
                    } else {
                        delete especificaciones[sublineaImagenesActual.principalId][index].img;
                    }
                }
            }
            
            inputHidden.value = JSON.stringify(especificaciones, null, 0);
            
            // Recargar especificaciones para actualizar el contador
            const categoriaId = document.getElementById('categoria_especificaciones_id').value;
            if (categoriaId) {
                obtenerEspecificacionesInternas(categoriaId);
            }
        }
        
        // Abrir modal para añadir imagen a sublínea
        function abrirModalAñadirImagenSublinea(principalId, sublineaId) {
            sublineaImagenesActual.principalId = principalId;
            sublineaImagenesActual.sublineaId = sublineaId;
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
        function limpiarModalAñadirSublinea() {
            document.getElementById('carpeta-subir-sublinea').value = '';
            document.getElementById('carpeta-url-sublinea').value = '';
            document.getElementById('file-subir-sublinea').value = '';
            document.getElementById('url-imagen-sublinea').value = '';
            document.getElementById('nombre-archivo-sublinea').textContent = '';
            document.getElementById('error-url-sublinea').classList.add('hidden');
            document.getElementById('area-recorte-sublinea').classList.add('hidden');
            if (cropperSublinea) {
                cropperSublinea.destroy();
                cropperSublinea = null;
            }
        }
        
        // Cambiar pestañas del modal de sublínea
        function cambiarTabModalSublinea(tab) {
            const tabSubir = document.getElementById('tab-subir-sublinea');
            const tabUrl = document.getElementById('tab-url-sublinea');
            const contentSubir = document.getElementById('content-subir-sublinea');
            const contentUrl = document.getElementById('content-url-sublinea');
            
            if (tab === 'url') {
                tabUrl.classList.add('border-blue-500', 'text-blue-600', 'dark:text-blue-400');
                tabUrl.classList.remove('border-transparent', 'text-gray-500');
                tabSubir.classList.remove('border-blue-500', 'text-blue-600', 'dark:text-blue-400');
                tabSubir.classList.add('border-transparent', 'text-gray-500');
                contentUrl.classList.remove('hidden');
                contentSubir.classList.add('hidden');
            } else {
                tabSubir.classList.add('border-blue-500', 'text-blue-600', 'dark:text-blue-400');
                tabSubir.classList.remove('border-transparent', 'text-gray-500');
                tabUrl.classList.remove('border-blue-500', 'text-blue-600', 'dark:text-blue-400');
                tabUrl.classList.add('border-transparent', 'text-gray-500');
                contentSubir.classList.remove('hidden');
                contentUrl.classList.add('hidden');
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
                    // Actualizar chips
                    if (chipsSeleccionados.length > 0) {
                        chipsContainer.innerHTML = chipsSeleccionados.map(sub => 
                            `<span class="inline-flex items-center gap-1 px-2 py-1 text-xs bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 rounded">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
                                <span>${sub.texto}</span>
                            </span>`
                        ).join('');
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

        // Event listeners para el buscador de categorías
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
                    // Limpiar todo
                    document.getElementById('categoria_especificaciones_id').value = '';
                    document.getElementById('categoria_especificaciones_nombre').value = '';
                    document.getElementById('categoria_especificaciones_internas_elegidas_input').value = '';
                    const seleccionContainer = document.getElementById('especificaciones-internas-seleccion');
                    if (seleccionContainer) {
                        seleccionContainer.classList.add('hidden');
                    }
                } else {
                    // Si hay una categoría seleccionada, recargar
                    const categoriaId = document.getElementById('categoria_especificaciones_id').value;
                    if (categoriaId) {
                        obtenerEspecificacionesInternas(categoriaId);
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
        
        // Función para procesar archivo subido para sublínea
        async function procesarArchivoSublinea(file) {
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
            
            document.getElementById('nombre-archivo-sublinea').textContent = 'Subiendo...';
            
            try {
                // Cargar imagen y procesar con canvas
                const img = new Image();
                img.crossOrigin = 'anonymous';
                
                img.onload = async function() {
                    try {
                        // Grande: tamaño original
                        const canvasGrande = document.createElement('canvas');
                        canvasGrande.width = img.width;
                        canvasGrande.height = img.height;
                        const ctxGrande = canvasGrande.getContext('2d');
                        ctxGrande.drawImage(img, 0, 0);
                        
                        // Pequeña: 300x250
                        const canvasPequena = document.createElement('canvas');
                        canvasPequena.width = 300;
                        canvasPequena.height = 250;
                        const ctxPequena = canvasPequena.getContext('2d');
                        ctxPequena.drawImage(img, 0, 0, 300, 250);
                        
                        // Convertir a blob webp
                        const blobGrande = await new Promise((resolve, reject) => {
                            canvasGrande.toBlob((blob) => blob ? resolve(blob) : reject(new Error('Error grande')), 'image/webp', 0.9);
                        });
                        
                        const blobPequena = await new Promise((resolve, reject) => {
                            canvasPequena.toBlob((blob) => blob ? resolve(blob) : reject(new Error('Error pequeña')), 'image/webp', 0.9);
                        });
                        
                        const slugInput = document.querySelector('input[name="slug"]');
                        const nombreBase = slugInput ? slugInput.value.trim() : 'imagen';
                        const timestamp = Date.now();
                        
                        // Subir ambas
                        const formDataGrande = new FormData();
                        formDataGrande.append('imagen', blobGrande, `${nombreBase}-${timestamp}.webp`);
                        formDataGrande.append('carpeta', carpeta);
                        formDataGrande.append('_token', '{{ csrf_token() }}');
                        
                        const formDataPequena = new FormData();
                        formDataPequena.append('imagen', blobPequena, `${nombreBase}-${timestamp}-thumbnail.webp`);
                        formDataPequena.append('carpeta', carpeta);
                        formDataPequena.append('_token', '{{ csrf_token() }}');
                        
                        const [resGrande, resPequena] = await Promise.all([
                            fetch('{{ route("admin.imagenes.subir-simple") }}', {
                                method: 'POST',
                                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                                body: formDataGrande
                            }),
                            fetch('{{ route("admin.imagenes.subir-simple") }}', {
                                method: 'POST',
                                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                                body: formDataPequena
                            })
                        ]);
                        
                        const dataGrande = await resGrande.json();
                        const dataPequena = await resPequena.json();
                        
                        if (dataGrande.success && dataPequena.success) {
                            // Guardar solo la ruta de la imagen grande en las sublíneas
                            añadirImagenASublinea(dataGrande.data.ruta_relativa);
                            // Limpiar el modal y volver a abrirlo
                            limpiarModalAñadirSublinea();
                            // Mantener el modal abierto y recargar carpetas
                            cargarCarpetasModalSublinea();
                            cambiarTabModalSublinea('subir');
                        } else {
                            throw new Error(dataGrande.message || dataPequena.message || 'Error al subir');
                        }
                    } catch (error) {
                        console.error('Error:', error);
                        alert(`Error al procesar la imagen: ${error.message}`);
                        document.getElementById('nombre-archivo-sublinea').textContent = '';
                    }
                };
                
                img.onerror = function() {
                    alert('Error al cargar la imagen. Por favor, verifica que sea un formato válido.');
                    document.getElementById('nombre-archivo-sublinea').textContent = '';
                };
                
                img.src = URL.createObjectURL(file);
                
            } catch (error) {
                console.error('Error:', error);
                alert(`Error al subir la imagen: ${error.message}`);
                document.getElementById('nombre-archivo-sublinea').textContent = '';
            }
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
            const tabActiva = document.querySelector('.tab-modal-sublinea.border-blue-500');
            const tabId = tabActiva ? tabActiva.id : 'tab-url-sublinea';
            
            if (tabId === 'tab-subir-sublinea') {
                if (!fileInputSublinea.files.length) {
                    alert('Por favor selecciona una imagen primero.');
                    return;
                }
            } else {
                if (!cropperSublinea || !carpetaActualSublinea) {
                    alert('Por favor descarga y recorta la imagen primero.');
                    return;
                }
                
                await procesarImagenRecortadaSublinea();
            }
        });
        
        // Procesar imagen recortada desde URL para sublínea
        async function procesarImagenRecortadaSublinea() {
            const canvasOriginal = cropperSublinea.getCroppedCanvas({
                imageSmoothingEnabled: true,
                imageSmoothingQuality: 'high'
            });
            
            if (!canvasOriginal) {
                alert('Error al recortar la imagen');
                return;
            }
            
            try {
                // Grande: tamaño original (obtener dimensiones originales del canvas recortado)
                const canvasGrande = document.createElement('canvas');
                canvasGrande.width = canvasOriginal.width;
                canvasGrande.height = canvasOriginal.height;
                const ctxGrande = canvasGrande.getContext('2d');
                ctxGrande.drawImage(canvasOriginal, 0, 0);
                
                // Pequeña: 300x250
                const canvasPequena = document.createElement('canvas');
                canvasPequena.width = 300;
                canvasPequena.height = 250;
                const ctxPequena = canvasPequena.getContext('2d');
                ctxPequena.drawImage(canvasOriginal, 0, 0, 300, 250);
                
                // Convertir a blob webp
                const blobGrande = await new Promise((resolve, reject) => {
                    canvasGrande.toBlob((blob) => blob ? resolve(blob) : reject(new Error('Error grande')), 'image/webp', 0.9);
                });
                
                const blobPequena = await new Promise((resolve, reject) => {
                    canvasPequena.toBlob((blob) => blob ? resolve(blob) : reject(new Error('Error pequeña')), 'image/webp', 0.9);
                });
                
                const slugInput = document.querySelector('input[name="slug"]');
                const nombreBase = slugInput ? slugInput.value.trim() : 'imagen';
                const timestamp = Date.now();
                
                // Subir ambas
                const formDataGrande = new FormData();
                formDataGrande.append('imagen', blobGrande, `${nombreBase}-${timestamp}.webp`);
                formDataGrande.append('carpeta', carpetaActualSublinea);
                formDataGrande.append('_token', '{{ csrf_token() }}');
                
                const formDataPequena = new FormData();
                formDataPequena.append('imagen', blobPequena, `${nombreBase}-${timestamp}-thumbnail.webp`);
                formDataPequena.append('carpeta', carpetaActualSublinea);
                formDataPequena.append('_token', '{{ csrf_token() }}');
                
                const [resGrande, resPequena] = await Promise.all([
                    fetch('{{ route("admin.imagenes.subir-simple") }}', {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                        body: formDataGrande
                    }),
                    fetch('{{ route("admin.imagenes.subir-simple") }}', {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                        body: formDataPequena
                    })
                ]);
                
                const dataGrande = await resGrande.json();
                const dataPequena = await resPequena.json();
                
                if (dataGrande.success && dataPequena.success) {
                    // Guardar solo la ruta de la imagen grande en las sublíneas
                    añadirImagenASublinea(dataGrande.data.ruta_relativa);
                    // Limpiar el modal y volver a abrirlo
                    limpiarModalAñadirSublinea();
                    // Mantener el modal abierto y recargar carpetas
                    cargarCarpetasModalSublinea();
                    cambiarTabModalSublinea('url');
                } else {
                    throw new Error(dataGrande.message || dataPequena.message || 'Error al subir');
                }
            } catch (error) {
                console.error('Error:', error);
                alert(`Error al procesar la imagen: ${error.message}`);
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
        document.addEventListener('DOMContentLoaded', () => {
            const btnRellenar = document.getElementById('rellenar-info-automatica');
            const btnText = document.getElementById('btn-text');
            const btnLoading = document.getElementById('btn-loading');
            const progresoInfo = document.getElementById('progreso-info');
            const progresoTexto = document.getElementById('progreso-texto');

            // Campos que deben estar vacíos para poder proceder
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
                btnRellenar.disabled = true;

                try {
                    // Obtener datos del producto
                    const nombre = document.querySelector('input[name="nombre"]').value.trim();
                    const marca = document.querySelector('input[name="marca"]').value.trim();
                    const modelo = document.querySelector('input[name="modelo"]').value.trim();
                    const talla = document.querySelector('input[name="talla"]').value.trim();

                    progresoTexto.textContent = 'Preparando datos del producto...';

                    // Obtener nombre de la última categoría seleccionada
                    const selectores = document.querySelectorAll('.categoria-select');
                    let nombreCategoria = 'bebés'; // Valor por defecto
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
                    const prompt = `Actúa como un experto en SEO y marketing digital especializado en productos para ${nombreCategoria}. Necesito que generes contenido optimizado para un comparador de precios.

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
    {"pregunta": "AQUI DEBES AÑADIRME LA PREGUNTA", "respuesta": "AQUÍ DEBES AÑADIRLA RESPUESTA"},
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

                    // Obtener categoria_id del campo oculto
                    const categoriaId = document.getElementById('categoria-final')?.value || null;

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
                    }if (data.caracteristicas) {
                        document.querySelector('textarea[name="meta_titulo"]').value = data.meta_titulo;
                    }
                    if (data.caracteristicas) {
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
                        btnRellenar.disabled = false;
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
    const btnGuardar = document.querySelector('button[type="submit"]');
    
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
    let imagenEditandoIndex = null;
    let cropperNueva = null;
    let imagenTemporalUrlNueva = null;
    let carpetaActualNueva = null;
    let wasDragged = false; // Flag para saber si se hizo drag
    
    // Cargar imágenes existentes al inicio
    function cargarImagenesExistentes() {
        const inputGrandes = document.getElementById('imagenes-grandes-json');
        const inputPequenas = document.getElementById('imagenes-pequenas-json');
        
        try {
            imagenesGrandes = inputGrandes && inputGrandes.value ? JSON.parse(inputGrandes.value) : [];
            imagenesPequenas = inputPequenas && inputPequenas.value ? JSON.parse(inputPequenas.value) : [];
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
            
            const div = document.createElement('div');
            div.className = 'relative group imagen-miniatura cursor-pointer';
            div.dataset.index = index;
            div.draggable = true;
            
            // Si es la primera (índice 0), marcar como principal con borde naranja
            if (index === 0) {
                div.classList.add('border-4', 'border-orange-500', 'rounded-lg');
            } else {
                div.classList.add('border', 'border-gray-300', 'dark:border-gray-600', 'rounded-lg');
            }
            
            const imgSrc = imgPequena || imgGrande;
            // Construir la URL correctamente como en el modal de edición: asset('images/') + '/' + ruta
            // imgSrc contiene la ruta relativa (ej: 'panales/imagen.jpg')
            const imgUrl = imgSrc ? `{{ asset('images/') }}/${imgSrc}` : '';
            
            div.innerHTML = `
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
    
    // Limpiar modal añadir
    function limpiarModalAñadir() {
        document.getElementById('carpeta-subir-nueva').value = '';
        document.getElementById('carpeta-url-nueva').value = '';
        document.getElementById('file-subir-nueva').value = '';
        document.getElementById('url-imagen-nueva').value = '';
        document.getElementById('nombre-archivo-nueva').textContent = '';
        document.getElementById('error-url-nueva').classList.add('hidden');
        document.getElementById('area-recorte-nueva').classList.add('hidden');
        if (cropperNueva) {
            cropperNueva.destroy();
            cropperNueva = null;
        }
    }
    
    // Cambiar pestañas del modal
    function cambiarTabModal(tab) {
        const tabSubir = document.getElementById('tab-subir-nueva');
        const tabUrl = document.getElementById('tab-url-nueva');
        const contentSubir = document.getElementById('content-subir-nueva');
        const contentUrl = document.getElementById('content-url-nueva');
        
        if (tab === 'url') {
            tabUrl.classList.add('border-blue-500', 'text-blue-600', 'dark:text-blue-400');
            tabUrl.classList.remove('border-transparent', 'text-gray-500');
            tabSubir.classList.remove('border-blue-500', 'text-blue-600', 'dark:text-blue-400');
            tabSubir.classList.add('border-transparent', 'text-gray-500');
            contentUrl.classList.remove('hidden');
            contentSubir.classList.add('hidden');
        } else {
            tabSubir.classList.add('border-blue-500', 'text-blue-600', 'dark:text-blue-400');
            tabSubir.classList.remove('border-transparent', 'text-gray-500');
            tabUrl.classList.remove('border-blue-500', 'text-blue-600', 'dark:text-blue-400');
            tabUrl.classList.add('border-transparent', 'text-gray-500');
            contentSubir.classList.remove('hidden');
            contentUrl.classList.add('hidden');
        }
    }
    
    document.getElementById('tab-subir-nueva').addEventListener('click', () => cambiarTabModal('subir'));
    document.getElementById('tab-url-nueva').addEventListener('click', () => cambiarTabModal('url'));
    
    // Cargar carpetas en modales
    function cargarCarpetasModal() {
        fetch('{{ route("admin.imagenes.carpetas") }}')
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data.length > 0) {
                    actualizarSelectCarpetas('carpeta-subir-nueva', data.data);
                    actualizarSelectCarpetas('carpeta-url-nueva', data.data);
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
    
    // Procesar archivo nuevo
    async function procesarArchivoNuevo(file) {
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
        
        document.getElementById('nombre-archivo-nueva').textContent = 'Subiendo...';
        
        try {
            // Cargar imagen y procesar con canvas
            const img = new Image();
            img.crossOrigin = 'anonymous';
            
            img.onload = async function() {
                try {
                    // Grande: tamaño original
                    const canvasGrande = document.createElement('canvas');
                    canvasGrande.width = img.width;
                    canvasGrande.height = img.height;
                    const ctxGrande = canvasGrande.getContext('2d');
                    ctxGrande.drawImage(img, 0, 0);
                    
                    // Pequeña: 300x250
                    const canvasPequena = document.createElement('canvas');
                    canvasPequena.width = 300;
                    canvasPequena.height = 250;
                    const ctxPequena = canvasPequena.getContext('2d');
                    ctxPequena.drawImage(img, 0, 0, 300, 250);
                    
                    // Convertir a blob webp
                    const blobGrande = await new Promise((resolve, reject) => {
                        canvasGrande.toBlob((blob) => blob ? resolve(blob) : reject(new Error('Error grande')), 'image/webp', 0.9);
                    });
                    
                    const blobPequena = await new Promise((resolve, reject) => {
                        canvasPequena.toBlob((blob) => blob ? resolve(blob) : reject(new Error('Error pequeña')), 'image/webp', 0.9);
                    });
                    
                    const slugInput = document.querySelector('input[name="slug"]');
                    const nombreBase = slugInput ? slugInput.value.trim() : 'imagen';
                    const timestamp = Date.now();
                    
                    // Subir ambas
                    const formDataGrande = new FormData();
                    formDataGrande.append('imagen', blobGrande, `${nombreBase}-${timestamp}.webp`);
                    formDataGrande.append('carpeta', carpeta);
                    formDataGrande.append('_token', '{{ csrf_token() }}');
                    
                    const formDataPequena = new FormData();
                    formDataPequena.append('imagen', blobPequena, `${nombreBase}-${timestamp}-thumbnail.webp`);
                    formDataPequena.append('carpeta', carpeta);
                    formDataPequena.append('_token', '{{ csrf_token() }}');
                    
                    const [resGrande, resPequena] = await Promise.all([
                        fetch('{{ route("admin.imagenes.subir-simple") }}', {
                            method: 'POST',
                            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                            body: formDataGrande
                        }),
                        fetch('{{ route("admin.imagenes.subir-simple") }}', {
                            method: 'POST',
                            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                            body: formDataPequena
                        })
                    ]);
                    
                    const dataGrande = await resGrande.json();
                    const dataPequena = await resPequena.json();
                    
                    if (dataGrande.success && dataPequena.success) {
                        imagenesGrandes.push(dataGrande.data.ruta_relativa);
                        imagenesPequenas.push(dataPequena.data.ruta_relativa);
                        renderizarImagenes();
                        cerrarModalAñadirImagen();
                    } else {
                        throw new Error(dataGrande.message || dataPequena.message || 'Error al subir');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert(`Error al procesar la imagen: ${error.message}`);
                    document.getElementById('nombre-archivo-nueva').textContent = '';
                }
            };
            
            img.onerror = function() {
                alert('Error al cargar la imagen. Por favor, verifica que sea un formato válido.');
                document.getElementById('nombre-archivo-nueva').textContent = '';
            };
            
            img.src = URL.createObjectURL(file);
            
        } catch (error) {
            console.error('Error:', error);
            alert(`Error al subir la imagen: ${error.message}`);
            document.getElementById('nombre-archivo-nueva').textContent = '';
        }
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
        const tabActiva = document.querySelector('.tab-modal.border-blue-500');
        const tabId = tabActiva ? tabActiva.id : 'tab-url-nueva';
        
        if (tabId === 'tab-subir-nueva') {
            // Ya se procesa en procesarArchivoNuevo
            // Solo verificar que hay archivo seleccionado
            if (!fileInputNueva.files.length) {
                alert('Por favor selecciona una imagen primero.');
                return;
            }
        } else {
            // Procesar desde URL con recorte
            if (!cropperNueva || !carpetaActualNueva) {
                alert('Por favor descarga y recorta la imagen primero.');
                return;
            }
            
            await procesarImagenRecortadaNueva();
        }
    });
    
    // Procesar imagen recortada desde URL
    async function procesarImagenRecortadaNueva() {
        const canvasOriginal = cropperNueva.getCroppedCanvas({
            imageSmoothingEnabled: true,
            imageSmoothingQuality: 'high'
        });
        
        if (!canvasOriginal) {
            alert('Error al recortar la imagen');
            return;
        }
        
        try {
            // Grande: tamaño original (obtener dimensiones originales del canvas recortado)
            const canvasGrande = document.createElement('canvas');
            canvasGrande.width = canvasOriginal.width;
            canvasGrande.height = canvasOriginal.height;
            const ctxGrande = canvasGrande.getContext('2d');
            ctxGrande.drawImage(canvasOriginal, 0, 0);
            
            // Pequeña: 300x250
            const canvasPequena = document.createElement('canvas');
            canvasPequena.width = 300;
            canvasPequena.height = 250;
            const ctxPequena = canvasPequena.getContext('2d');
            ctxPequena.drawImage(canvasOriginal, 0, 0, 300, 250);
            
            // Convertir a blob webp
            const blobGrande = await new Promise((resolve, reject) => {
                canvasGrande.toBlob((blob) => blob ? resolve(blob) : reject(new Error('Error grande')), 'image/webp', 0.9);
            });
            
            const blobPequena = await new Promise((resolve, reject) => {
                canvasPequena.toBlob((blob) => blob ? resolve(blob) : reject(new Error('Error pequeña')), 'image/webp', 0.9);
            });
            
            const slugInput = document.querySelector('input[name="slug"]');
            const nombreBase = slugInput ? slugInput.value.trim() : 'imagen';
            const timestamp = Date.now();
            
            // Subir ambas
            const formDataGrande = new FormData();
            formDataGrande.append('imagen', blobGrande, `${nombreBase}-${timestamp}.webp`);
            formDataGrande.append('carpeta', carpetaActualNueva);
            formDataGrande.append('_token', '{{ csrf_token() }}');
            
            const formDataPequena = new FormData();
            formDataPequena.append('imagen', blobPequena, `${nombreBase}-${timestamp}-thumbnail.webp`);
            formDataPequena.append('carpeta', carpetaActualNueva);
            formDataPequena.append('_token', '{{ csrf_token() }}');
            
            const [resGrande, resPequena] = await Promise.all([
                fetch('{{ route("admin.imagenes.subir-simple") }}', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: formDataGrande
                }),
                fetch('{{ route("admin.imagenes.subir-simple") }}', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: formDataPequena
                })
            ]);
            
            const dataGrande = await resGrande.json();
            const dataPequena = await resPequena.json();
            
            if (dataGrande.success && dataPequena.success) {
                imagenesGrandes.push(dataGrande.data.ruta_relativa);
                imagenesPequenas.push(dataPequena.data.ruta_relativa);
                renderizarImagenes();
                cerrarModalAñadirImagen();
            } else {
                throw new Error(dataGrande.message || dataPequena.message || 'Error al subir');
            }
        } catch (error) {
            console.error('Error:', error);
            alert(`Error al procesar la imagen: ${error.message}`);
        }
    }
    
    // Actualizar campos ocultos JSON
    function actualizarCamposOcultos() {
        document.getElementById('imagenes-grandes-json').value = JSON.stringify(imagenesGrandes);
        document.getElementById('imagenes-pequenas-json').value = JSON.stringify(imagenesPequenas);
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
        let nombreCategoria = 'bebés'; // Valor por defecto
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
    {"pregunta": "AQUI DEBES AÑADIRME LA PREGUNTA", "respuesta": "AQUÍ DEBES AÑADIRLA RESPUESTA"},
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

        // Obtener información adicional de la categoría si existe
        if (categoriaId) {
            // Hacer petición para obtener la información adicional de la categoría
            const url = `/panel-privado/categorias/${categoriaId}/info-chatgpt`;
            fetch(url, {
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.info_adicional && data.info_adicional.trim() !== '') {
                    prompt += "\n\n" + data.info_adicional.trim();
                }
                mostrarPromptEnModal(prompt);
            })
            .catch(error => {
                console.error('Error al obtener info de categoría:', error);
                mostrarPromptEnModal(prompt);
            });
        } else {
            mostrarPromptEnModal(prompt);
        }
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
                if (parsed && parsed._columnas && Array.isArray(parsed._columnas)) {
                    // Compatibilidad: puede venir como ["id"] o [{id:"id", c:0}]
                    columnasGuardadas = parsed._columnas
                        .map(v => (v && typeof v === 'object' && !Array.isArray(v)) ? v.id : v)
                        .filter(v => (typeof v === 'string' || typeof v === 'number'))
                        .map(v => String(v));
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
                const esColumna = columnasGuardadas.includes(filtro.id) || filtro._esColumna === true;
                // Obtener formato guardado para esta línea principal
                const formatoGuardado = (typeof formatosGuardados[filtro.id] === 'string') ? formatosGuardados[filtro.id] : 'texto';
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
    
    // Crear una línea principal del producto
    function crearLineaPrincipalProducto(texto = '', importante = false, subprincipales = [], idUnico = null, slugUnico = null, esColumna = false, formatoGuardado = 'texto') {
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
                ${esUnidadUnica ? `
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" class="columna-oferta-producto-checkbox rounded border-gray-300 text-orange-600 focus:ring-orange-500" data-principal-id="${idUnicoLinea}" ${esColumna ? 'checked' : ''}>
                    <span class="text-xs text-gray-600 dark:text-gray-400 font-medium">Columna oferta</span>
                </label>
                ` : ''}
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
        
        checkboxImportante.addEventListener('change', actualizarJSONProducto);
        
        // Event listener para el selector de formato
        if (formatoSelect) {
            formatoSelect.addEventListener('change', function() {
                actualizarJSONProducto();
            });
        }
        
        if (columnaCheckbox) {
            columnaCheckbox.addEventListener('change', function() {
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
            actualizarJSONProducto();
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
            actualizarJSONProducto();
            // Actualizar visibilidad del selector de formato
            actualizarVisibilidadFormatoProducto(lineaPrincipal);
        });
        ofertaCheckbox.addEventListener('change', actualizarJSONProducto);
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
            // Actualizar visibilidad del selector de formato
            actualizarVisibilidadFormatoProducto(lineaPrincipal);
        });
        
        btnAñadir.addEventListener('click', () => {
            const nuevaLinea = crearLineaIntermediaProducto(containerPadre, lineaPrincipal, '');
            divIntermedia.insertAdjacentElement('afterend', nuevaLinea);
            actualizarJSONProducto();
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

        if (especificacionesCompletas._orden) {
            especificacionesCompletas._orden = normalizarArrayIds(especificacionesCompletas._orden);
        }
        if (especificacionesCompletas._columnas) {
            especificacionesCompletas._columnas = normalizarArrayIds(especificacionesCompletas._columnas);
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
        
        // Si es unidadUnica, guardar orden y columnas (combinando con las de categoría)
        if (esUnidadUnica) {
            const lineasPrincipales = Array.from(container.querySelectorAll('.linea-principal-producto'));
            const orden = lineasPrincipales.map(linea => linea.dataset.idUnico);
            especificacionesCompletas._orden = orden;
            
            // Obtener columnas del producto (solo las marcadas actualmente)
            const columnasCheckboxes = container.querySelectorAll('.columna-oferta-producto-checkbox:checked');
            const columnasProducto = Array.from(columnasCheckboxes).map(cb => cb.dataset.principalId);
            
            // Obtener columnas de categoría (del contenedor de categoría, solo las marcadas actualmente)
            const contenedorCategoria = document.querySelector('#especificaciones-principales-container');
            let columnasCategoria = [];
            if (contenedorCategoria) {
                const columnasCheckboxesCategoria = contenedorCategoria.querySelectorAll('.columna-oferta-checkbox:checked');
                columnasCategoria = Array.from(columnasCheckboxesCategoria).map(cb => cb.dataset.principalId);
            }
            
            // Combinar solo las columnas actualmente marcadas: categoría + producto, eliminando duplicados
            const todasLasColumnas = [...new Set([...columnasCategoria, ...columnasProducto])];
            especificacionesCompletas._columnas = todasLasColumnas;
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
            const btnGuardar = document.querySelector('button[type="submit"]');
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
            const btnGuardar = document.querySelector('button[type="submit"]');
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
        const btnGuardar = document.querySelector('button[type="submit"]');
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
});
</script>

</x-app-layout>
