<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.dashboard') }}">
                <h2 class="font-semibold text-xl text-white leading-tight">Inicio -></h2>
            </a>
            <a href="{{ route('admin.categorias.index') }}">
                <h2 class="font-semibold text-xl text-white leading-tight">Categorías -></h2>
            </a>
            <h2 class="font-semibold text-xl text-white leading-tight">
                {{ $categoria ? 'Editar Categoría' : 'Nueva Categoría' }}
            </h2>
            <style>[x-cloak]{ display:none !important; }</style>
        </div>
    </x-slot>

    <div id="categoria-form-alpine" class="max-w-5xl mx-auto py-10 px-4 space-y-8 bg-gray-50 dark:bg-gray-900 rounded-lg shadow-md"
        @verificar-slug-categoria="verificarSlugExistente()"
        x-data="{
            slugModificadoManualmente: false,
            slugExiste: false,
            categoriaId: {{ $categoria ? $categoria->id : 'null' }},
            slugOriginal: '{{ $categoria ? $categoria->slug : '' }}',
            async generarSlugDesdeNombre() {
                const nombreInput = document.getElementById('nombre-categoria');
                const slugInput = document.getElementById('slug-categoria');
                
                if (!this.slugModificadoManualmente && nombreInput && slugInput) {
                    const nombre = nombreInput.value;
                    const slug = this.convertirASlug(nombre);
                    slugInput.value = slug;
                    await this.verificarSlugExistente();
                }
            },
            convertirASlug(texto) {
                return texto
                    .toString()
                    .toLowerCase()
                    .trim()
                    .normalize('NFD')
                    .replace(/[\u0300-\u036f]/g, '')
                    .replace(/[^a-z0-9]+/g, '-')
                    .replace(/^-+|-+$/g, '');
            },
            async verificarSlugExistente() {
                const slugInput = document.getElementById('slug-categoria');
                if (!slugInput || !slugInput.value.trim()) {
                    const mensajeDiv = document.getElementById('slug-mensaje');
                    if (mensajeDiv) {
                        mensajeDiv.classList.add('hidden');
                    }
                    const btnGuardar = document.getElementById('btn-guardar-categoria');
                    if (btnGuardar) {
                        btnGuardar.disabled = true;
                    }
                    return;
                }
                
                const slug = slugInput.value.trim();
                const mensajeDiv = document.getElementById('slug-mensaje');
                if (!mensajeDiv) return;
                
                mensajeDiv.classList.remove('hidden');
                
                try {
                    const urlVerificarSlug = '{{ route("admin.categorias.verificar-slug") }}';
                    const csrfToken = '{{ csrf_token() }}';
                    const response = await fetch(urlVerificarSlug, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: JSON.stringify({
                            slug: slug,
                            categoria_id: this.categoriaId
                        })
                    });
                    
                    const data = await response.json();
                    
                    mensajeDiv.innerHTML = '';
                    const span = document.createElement('span');
                    const btnGuardar = document.getElementById('btn-guardar-categoria');
                    
                    if (this.categoriaId) {
                        // Modo edición
                        const slugNormalizado = slug.toLowerCase().trim();
                        const slugOriginalNormalizado = this.slugOriginal.toLowerCase().trim();
                        
                        if (slugNormalizado === slugOriginalNormalizado) {
                            span.className = 'text-green-600 dark:text-green-400';
                            span.textContent = '✓ Slug disponible (mismo slug de esta categoría)';
                            mensajeDiv.className = 'mt-1 text-sm text-green-600 dark:text-green-400';
                            this.slugExiste = false;
                        } else if (data.existe) {
                            span.className = 'text-red-600 dark:text-red-400';
                            span.textContent = '⚠️ Este slug ya existe en otra categoría';
                            mensajeDiv.className = 'mt-1 text-sm text-red-600 dark:text-red-400';
                            this.slugExiste = true;
                            if (btnGuardar) {
                                btnGuardar.disabled = true;
                            }
                        } else {
                            span.className = 'text-green-600 dark:text-green-400';
                            span.textContent = '✓ Slug disponible';
                            mensajeDiv.className = 'mt-1 text-sm text-green-600 dark:text-green-400';
                            this.slugExiste = false;
                        }
                    } else {
                        // Modo creación
                        if (data.existe) {
                            span.className = 'text-red-600 dark:text-red-400';
                            span.textContent = '⚠️ Este slug ya existe';
                            mensajeDiv.className = 'mt-1 text-sm text-red-600 dark:text-red-400';
                            this.slugExiste = true;
                            if (btnGuardar) {
                                btnGuardar.disabled = true;
                            }
                        } else {
                            span.className = 'text-green-600 dark:text-green-400';
                            span.textContent = '✓ Slug disponible';
                            mensajeDiv.className = 'mt-1 text-sm text-green-600 dark:text-green-400';
                            this.slugExiste = false;
                            const nombreInput = document.getElementById('nombre-categoria');
                            if (btnGuardar && nombreInput) {
                                const tieneNombre = nombreInput.value.trim().length > 0;
                                btnGuardar.disabled = !tieneNombre || this.slugExiste;
                            }
                        }
                    }
                    
                    mensajeDiv.appendChild(span);
                } catch (error) {
                    console.error('Error al verificar slug:', error);
                    mensajeDiv.classList.add('hidden');
                }
            }
        }">

        {{-- MENSAJES FLASH --}}
        @if (session('success'))
        <div class="p-3 bg-green-100 text-green-800 rounded shadow-sm border border-green-300">
            {{ session('success') }}
        </div>
        @endif
        @if (session('error'))
        <div class="p-3 bg-red-100 text-red-800 rounded shadow-sm border border-red-300">
            {{ session('error') }}
        </div>
        @endif

        {{-- FORMULARIO CREAR/EDITAR CATEGORÍA --}}
        <form id="form-categoria" method="POST" action="{{ $categoria ? route('admin.categorias.update', $categoria) : route('admin.categorias.store') }}">
            @csrf
            @if($categoria)
                @method('PUT')
            @endif
            <fieldset class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6 space-y-4 border border-gray-200 dark:border-gray-700">
                <legend class="text-lg font-semibold text-gray-700 dark:text-gray-200">
                    {{ $categoria ? 'Editar Categoría' : 'Nueva Categoría' }}
                </legend>

                {{-- SISTEMA DE CATEGORÍAS DINÁMICO --}}
                <div id="categorias-container" class="space-y-4">
                    <!-- Los selectores de categorías se generarán dinámicamente aquí -->
                </div>

                <!-- Campo oculto para la categoría padre final -->
                <input type="hidden" name="parent_id" id="categoria-final" value="{{ $categoria ? $categoria->parent_id : '' }}">

                <div>
                    <label class="block mb-1 font-medium text-gray-700 dark:text-gray-200">Nombre *</label>
                    <input type="text" name="nombre" id="nombre-categoria" value="{{ $categoria ? $categoria->nombre : '' }}" required 
                        class="w-full px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border"
                        @input="if (!slugModificadoManualmente) { generarSlugDesdeNombre(); }">
                </div>

                <div>
                    <label class="block mb-1 font-medium text-gray-700 dark:text-gray-200">Slug *</label>
                    <div class="relative">
                        <input type="text" name="slug" id="slug-categoria" value="{{ $categoria ? $categoria->slug : '' }}" required 
                            class="w-full px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border"
                            @input.debounce.500ms="slugModificadoManualmente = true; verificarSlugExistente()">
                        <div id="slug-mensaje" class="mt-1 text-sm hidden"></div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block mb-1 font-medium text-gray-700 dark:text-gray-200">¿Mostrar? *</label>
                        <div class="flex gap-6">
                            <label class="inline-flex items-center">
                                <input type="radio" name="mostrar" value="si"
                                    {{ old('mostrar', $categoria->mostrar ?? 'si') === 'si' ? 'checked' : '' }}
                                    class="form-radio text-pink-600">
                                <span class="ml-2 text-gray-700 dark:text-gray-200">Sí</span>
                            </label>
                            <label class="inline-flex items-center">
                                <input type="radio" name="mostrar" value="no"
                                    {{ old('mostrar', $categoria->mostrar ?? 'si') === 'no' ? 'checked' : '' }}
                                    class="form-radio text-pink-600">
                                <span class="ml-2 text-gray-700 dark:text-gray-200">No</span>
                            </label>
                        </div>
                    </div>

                    <div>
                        <label class="flex items-center gap-1.5 mb-1 font-medium text-gray-700 dark:text-gray-200">
                            <span>¿Texto cantidad alternativo en ofertas? *</span>
                            <button type="button"
                                class="tooltip-btn inline-flex items-center justify-center w-5 h-5 rounded-full bg-gray-300 dark:bg-gray-600 text-gray-800 dark:text-gray-100 text-xs font-bold hover:bg-gray-400 dark:hover:bg-gray-500 focus:outline-none"
                                aria-label="Ayuda sobre texto cantidad alternativo"
                                data-tooltip="Si marcas Sí, al crear o editar ofertas de productos de esta categoría será obligatorio escribir un texto alternativo de cantidad (por ejemplo «6 botellines» en lugar de «1,5 Litros»). El campo de litros/kilos/unidades sigue usándose para calcular el precio por unidad; el texto alternativo solo cambia lo que ve el usuario en el comparador.">?</button>
                        </label>
                        <div class="flex gap-6">
                            <label class="inline-flex items-center">
                                <input type="radio" name="permitir_texto_cantidad_alternativo" value="si"
                                    {{ old('permitir_texto_cantidad_alternativo', $categoria->permitir_texto_cantidad_alternativo ?? 'no') === 'si' ? 'checked' : '' }}
                                    class="form-radio text-pink-600">
                                <span class="ml-2 text-gray-700 dark:text-gray-200">Sí</span>
                            </label>
                            <label class="inline-flex items-center">
                                <input type="radio" name="permitir_texto_cantidad_alternativo" value="no"
                                    {{ old('permitir_texto_cantidad_alternativo', $categoria->permitir_texto_cantidad_alternativo ?? 'no') === 'no' ? 'checked' : '' }}
                                    class="form-radio text-pink-600">
                                <span class="ml-2 text-gray-700 dark:text-gray-200">No</span>
                            </label>
                        </div>
                        @error('permitir_texto_cantidad_alternativo') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <label class="block mb-1 font-medium text-gray-700 dark:text-gray-200">Unidad de medida</label>
                    <select name="unidad_de_medida" class="w-full px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border @error('unidad_de_medida') border-red-500 @enderror">
                        <option value="" {{ old('unidad_de_medida', $categoria?->unidad_de_medida ?? '') === '' ? 'selected' : '' }}>— Selecciona una opción —</option>
                        <option value="unidad" {{ old('unidad_de_medida', $categoria?->unidad_de_medida ?? '') === 'unidad' ? 'selected' : '' }}>Unidad</option>
                        <option value="kilos" {{ old('unidad_de_medida', $categoria?->unidad_de_medida ?? '') === 'kilos' ? 'selected' : '' }}>Kilos</option>
                        <option value="litros" {{ old('unidad_de_medida', $categoria?->unidad_de_medida ?? '') === 'litros' ? 'selected' : '' }}>Litros</option>
                        <option value="unidadMilesima" {{ old('unidad_de_medida', $categoria?->unidad_de_medida ?? '') === 'unidadMilesima' ? 'selected' : '' }}>Unidad Milésima</option>
                        <option value="unidadUnica" {{ old('unidad_de_medida', $categoria?->unidad_de_medida ?? '') === 'unidadUnica' ? 'selected' : '' }}>Unidad Única</option>
                        <option value="800gramos" {{ old('unidad_de_medida', $categoria?->unidad_de_medida ?? '') === '800gramos' ? 'selected' : '' }}>800 gramos</option>
                        <option value="100ml" {{ old('unidad_de_medida', $categoria?->unidad_de_medida ?? '') === '100ml' ? 'selected' : '' }}>100 ml</option>
                    </select>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Opcional. Se usará como valor por defecto al crear productos en esta categoría.</p>
                    @error('unidad_de_medida') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block mb-1 font-medium text-gray-700 dark:text-gray-200">Configuracion formulario producto</label>
                    <div class="flex items-center gap-2">
                        @php
                            $configFormProducto = old('configuracion_formulario_producto', $categoria?->configuracion_formulario_producto ?? 'ninguno');
                        @endphp
                        <select name="configuracion_formulario_producto" id="configuracion_formulario_producto"
                            class="flex-1 px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border @error('configuracion_formulario_producto') border-red-500 @enderror">
                            <option value="ninguno" {{ $configFormProducto === 'ninguno' ? 'selected' : '' }}>Ninguno</option>
                            <option value="no_columna_grupo_mostrar_imagen_nombre_precio" {{ $configFormProducto === 'no_columna_grupo_mostrar_imagen_nombre_precio' ? 'selected' : '' }}>No columna, Grupo mostrar Imagen-nombre-precio</option>
                        </select>
                        <button type="button"
                            id="ayuda-configuracion-formulario-producto"
                            class="{{ $configFormProducto === 'no_columna_grupo_mostrar_imagen_nombre_precio' ? '' : 'hidden' }} inline-flex items-center justify-center w-6 h-6 rounded-full bg-gray-300 dark:bg-gray-600 text-gray-800 dark:text-gray-100 text-sm font-bold hover:bg-gray-400 dark:hover:bg-gray-500 focus:outline-none shrink-0"
                            aria-label="Ayuda sobre configuración formulario producto"
                            data-tooltip="Al marcar en una subespecificación el check de oferta o mostrar, ya no se marcará la columna automáticamente y se pondrá dicho grupo a mostrar como «Imagen-texto-precio».">?</button>
                    </div>
                    @error('configuracion_formulario_producto') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                </div>

                @if(!$categoria)
                {{-- IMÁGENES (nueva categoría: después de datos básicos) --}}
                <fieldset class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6 space-y-4 border border-gray-200 dark:border-gray-700">
                    <legend class="text-lg font-semibold text-gray-700 dark:text-gray-200">Imagen de la categoría</legend>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Una sola imagen por categoría (144×120 px). Se guarda en la carpeta <strong class="font-medium">categorias</strong>.</p>

                    <div id="imagen-categoria-container" class="flex flex-wrap gap-3 items-start min-h-[5rem]">
                        <!-- Vista previa generada por JS -->
                    </div>

                    <div class="mt-4 flex flex-wrap items-center gap-3">
                        <button type="button" id="btn-añadir-imagen-categoria" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-4 py-2 rounded-md transition duration-150 ease-in-out">
                            + Añadir imagen
                        </button>
                        <button type="button" id="btn-cambiar-imagen-categoria" class="hidden bg-gray-600 hover:bg-gray-700 text-white font-semibold px-4 py-2 rounded-md">
                            Cambiar imagen
                        </button>
                    </div>

                    <input type="hidden" id="ruta-imagen-categoria" name="imagen" value="{{ old('imagen', '') }}">
                    <p id="ruta-imagen-categoria-texto" class="text-xs text-gray-500 dark:text-gray-400 break-all hidden"></p>
                    @error('imagen') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                </fieldset>
                @endif

                {{-- ESPECIFICACIONES INTERNAS --}}
                <fieldset class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6 space-y-4 border border-gray-200 dark:border-gray-700">
                    <legend class="text-lg font-semibold text-gray-700 dark:text-gray-200">Especificaciones internas</legend>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Puedes dejar este campo vacío o añadir especificaciones internas para esta categoría.</p>
                    
                    <div id="especificaciones-container" class="space-y-4">
                        <!-- Las líneas se generarán dinámicamente aquí -->
                    </div>
                    
                    <!-- Campo oculto para guardar el JSON -->
                    <input type="hidden" name="especificaciones_internas" id="especificaciones-internas-input" value="{{ old('especificaciones_internas', $categoria && $categoria->especificaciones_internas ? json_encode($categoria->especificaciones_internas, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '') }}">
                </fieldset>

                @if($categoria)
                {{-- BUSCAR URL NUEVAS (cron Amazon + AliExpress + CSV) --}}
                <fieldset id="buscar-urls-nuevas-categoria" class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6 space-y-4 border border-gray-200 dark:border-gray-700">
                    <legend class="text-lg font-semibold text-gray-700 dark:text-gray-200">Buscar URL nuevas</legend>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Ejecuta la misma búsqueda que el cron de Amazon/AliExpress/CSV para todos los productos elegibles de esta categoría
                        (y subcategorías): <strong class="font-medium text-gray-700 dark:text-gray-200">mostrar=si</strong>,
                        <strong class="font-medium text-gray-700 dark:text-gray-200">obsoleto=no</strong>, con nombre y palabras exigidas.
                        Deja «1» para empezar desde el primero, o indica la posición en la lista (ej. 44 para continuar en el producto 44 de {{ (int) ($totalProductosBuscarUrls ?? 0) }}).
                    </p>

                    <div class="flex flex-wrap items-center gap-3">
                        @php
                            $totalBuscarUrls = (int) ($totalProductosBuscarUrls ?? 0);
                        @endphp
                        <label for="input-desde-producto-buscar-urls-cat"
                            class="inline-flex flex-wrap items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                            <span>Empezar desde</span>
                            <input type="number"
                                id="input-desde-producto-buscar-urls-cat"
                                min="1"
                                max="{{ max(1, $totalBuscarUrls) }}"
                                value="1"
                                step="1"
                                @if($totalBuscarUrls === 0) disabled @endif
                                class="w-20 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 px-2 py-1.5 text-sm text-center disabled:opacity-50">
                            <span class="whitespace-nowrap">/ <strong id="total-productos-buscar-urls-cat">{{ $totalBuscarUrls }}</strong></span>
                            <span id="hint-desde-producto-buscar-urls-cat" class="text-xs text-gray-500 dark:text-gray-400"></span>
                        </label>
                        <button type="button" id="btn-ejecutar-cron-buscar-urls-cat"
                            @if($totalBuscarUrls === 0) disabled @endif
                            class="bg-emerald-600 hover:bg-emerald-700 text-white font-semibold px-4 py-2 rounded-md transition disabled:opacity-50 disabled:cursor-not-allowed">
                            Ejecutar cron
                        </button>
                        <button type="button" id="btn-pausar-cron-buscar-urls-cat"
                            class="bg-amber-600 hover:bg-amber-700 text-white font-semibold px-4 py-2 rounded-md transition disabled:opacity-50 disabled:cursor-not-allowed"
                            disabled title="Pausar">
                            ⏸ Pausar
                        </button>
                        <button type="button" id="btn-detener-cron-buscar-urls-cat"
                            class="bg-orange-700 hover:bg-orange-800 text-white font-semibold px-4 py-2 rounded-md transition disabled:opacity-50 disabled:cursor-not-allowed"
                            disabled title="Detener">
                            ⏹ Detener
                        </button>
                        <button type="button" id="btn-log-buscar-urls-cat"
                            class="bg-slate-600 hover:bg-slate-700 text-white font-semibold px-4 py-2 rounded-md transition disabled:opacity-50 disabled:cursor-not-allowed"
                            disabled>
                            Log
                        </button>
                    </div>

                    <div id="progreso-buscar-urls-cat" class="hidden rounded-lg border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-900/40 p-4 space-y-2 text-sm">
                        <p id="texto-progreso-buscar-urls-cat" class="font-medium text-gray-800 dark:text-gray-100">
                            Preparando…
                        </p>
                        <p class="text-gray-600 dark:text-gray-300">
                            URL encontradas: <strong id="urls-todas-buscar-cat">0</strong> ·
                            Existentes: <strong id="urls-existentes-buscar-cat" class="text-amber-600 dark:text-amber-400">0</strong> ·
                            Añadidas a Neo: <strong id="urls-neo-buscar-cat" class="text-emerald-600 dark:text-emerald-400">0</strong>
                        </p>
                        <p id="producto-actual-buscar-urls-cat" class="text-gray-500 dark:text-gray-400 text-xs"></p>
                    </div>
                </fieldset>

                <div id="modal-log-buscar-urls-cat" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60" aria-modal="true" role="dialog">
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-4xl max-h-[90vh] flex flex-col border border-gray-200 dark:border-gray-700">
                        <div class="flex items-center justify-between gap-3 px-5 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Log — Buscar URL nuevas</h3>
                            <button type="button" id="btn-cerrar-modal-log-buscar-urls-cat" class="text-gray-500 hover:text-gray-800 dark:hover:text-gray-200 text-2xl leading-none" aria-label="Cerrar">&times;</button>
                        </div>
                        <div id="modal-log-buscar-urls-cat-contenido" class="px-5 py-4 overflow-y-auto flex-1 space-y-4 text-sm"></div>
                        <div class="px-5 py-3 border-t border-gray-200 dark:border-gray-700 flex justify-end">
                            <button type="button" id="btn-cerrar-modal-log-buscar-urls-cat-footer" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded">Cerrar</button>
                        </div>
                    </div>
                </div>
                @endif

                {{-- INFORMACIÓN ADICIONAL PARA CHATGPT --}}
                <fieldset class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6 space-y-4 border border-gray-200 dark:border-gray-700">
                    <legend class="text-lg font-semibold text-gray-700 dark:text-gray-200">Información adicional para ChatGPT</legend>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Esta información se añadirá automáticamente al final del prompt cuando se use el botón de "Rellenar información automáticamente" en productos de esta categoría.</p>
                    
                    <div>
                        <label class="block mb-1 font-medium text-gray-700 dark:text-gray-200">Información adicional</label>
                        <textarea name="info_adicional_chatgpt" 
                            class="w-full px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border"
                            rows="4"
                            placeholder="Ejemplo: Esta categoría se enfoca en productos ecológicos y sostenibles...">{{ old('info_adicional_chatgpt', $categoria->info_adicional_chatgpt ?? '') }}</textarea>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Puedes dejar este campo vacío si no necesitas información adicional para esta categoría.</p>
                    </div>
                </fieldset>

                @if($categoria)
                {{-- IMÁGENES (editar: última sección antes de guardar) --}}
                <fieldset class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6 space-y-4 border border-gray-200 dark:border-gray-700">
                    <legend class="text-lg font-semibold text-gray-700 dark:text-gray-200">Imagen de la categoría</legend>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Una sola imagen por categoría (144×120 px). Se guarda en la carpeta <strong class="font-medium">categorias</strong>.</p>

                    <div id="imagen-categoria-container" class="flex flex-wrap gap-3 items-start min-h-[5rem]">
                        <!-- Vista previa generada por JS -->
                    </div>

                    <div class="mt-4 flex flex-wrap items-center gap-3">
                        <button type="button" id="btn-añadir-imagen-categoria" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-4 py-2 rounded-md transition duration-150 ease-in-out">
                            + Añadir imagen
                        </button>
                        <button type="button" id="btn-cambiar-imagen-categoria" class="hidden bg-gray-600 hover:bg-gray-700 text-white font-semibold px-4 py-2 rounded-md">
                            Cambiar imagen
                        </button>
                    </div>

                    <input type="hidden" id="ruta-imagen-categoria" name="imagen" value="{{ old('imagen', $categoria->imagen ?? '') }}">
                    <p id="ruta-imagen-categoria-texto" class="text-xs text-gray-500 dark:text-gray-400 break-all hidden"></p>
                    @error('imagen') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                </fieldset>
                @endif

                <div class="flex justify-end gap-2">
                    <a href="{{ route('admin.categorias.index') }}" 
                        class="bg-gray-500 hover:bg-gray-600 text-white font-semibold px-6 py-2 rounded shadow">
                        Cancelar
                    </a>
                    <button type="submit" id="btn-guardar-categoria" 
                        class="bg-pink-600 hover:bg-pink-700 text-white font-semibold px-6 py-2 rounded shadow disabled:bg-gray-400 disabled:cursor-not-allowed"
                        {{ !$categoria ? 'disabled' : '' }}>
                        {{ $categoria ? 'Actualizar categoría' : 'Guardar categoría' }}
                    </button>
                </div>
                
                @if ($errors->has('slug'))
                    <div class="mt-2 p-3 bg-red-100 text-red-800 rounded border border-red-300">
                        {{ $errors->first('slug') }}
                    </div>
                @endif
            </fieldset>
        </form>

        {{-- SISTEMA DE CATEGORÍAS COMPLETAMENTE REWRITE --}}
        <div id="datos-categorias"
            data-categorias-raiz="{{ json_encode($categoriasRaiz) }}"
            data-categoria-actual="{{ $categoria ? json_encode($categoria) : 'null' }}"
            data-parent-id="{{ $categoria && $categoria->parent_id ? $categoria->parent_id : '' }}">
        </div>

        {{-- MODAL PARA AÑADIR IMAGEN DE CATEGORÍA (mismo patrón que productos) --}}
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
            .kp-ig-cell { position: relative; }
            .kp-ig-btn-ampliar {
                line-height: 0;
                z-index: 30;
                background-color: #2563eb;
                color: #fff;
                border: none;
                cursor: pointer;
            }
            .kp-ig-btn-ampliar:hover { background-color: #1d4ed8; }
            .kp-ig-btn-ampliar svg { width: 0.8rem; height: 0.8rem; display: block; }
            #kp-ig-preview-overlay { z-index: 99999; }
            #kp-ig-preview-overlay img {
                max-width: min(96vw, 1200px);
                max-height: 90vh;
                width: auto;
                height: auto;
                object-fit: contain;
            }
        </style>

        <div id="modal-añadir-imagen-categoria" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
            <div class="bg-white dark:bg-gray-900 rounded-lg p-6 max-w-4xl w-full relative shadow-xl overflow-y-auto max-h-[90vh]">
                <button type="button" onclick="cerrarModalAñadirImagenCategoria()" class="absolute top-3 right-4 text-xl text-gray-800 dark:text-gray-100 hover:text-gray-600 dark:hover:text-gray-300">×</button>
                <div class="mb-4">
                    <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-200">Añadir imagen de categoría</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">La imagen se guardará redimensionada a 144×120 en la carpeta <strong class="font-medium">categorias</strong>.</p>
                </div>

                <div class="kp-modal-img-tabs mb-4" role="tablist" aria-label="Origen de la imagen">
                    <nav class="kp-modal-img-tabs__nav">
                        <button type="button" id="tab-url-cat" class="tab-modal-cat kp-modal-img-tab kp-modal-img-tab--active" role="tab" aria-selected="true">
                            Descargar desde URL
                        </button>
                        <button type="button" id="tab-subir-cat" class="tab-modal-cat kp-modal-img-tab" role="tab" aria-selected="false">
                            Subir imagen
                        </button>
                        <button type="button" id="tab-amazon-cat" class="tab-modal-cat kp-modal-img-tab" role="tab" aria-selected="false">
                            Amazon
                        </button>
                        <button type="button" id="tab-interna-global-cat" class="tab-modal-cat kp-modal-img-tab" role="tab" aria-selected="false">
                            Interna Global
                        </button>
                    </nav>
                </div>

                <div id="content-url-cat" class="tab-content-modal-cat space-y-4">
                    <div>
                        <label class="block mb-2 text-sm text-gray-600 dark:text-gray-400">URL de la imagen</label>
                        <div class="flex gap-2">
                            <input type="url" id="url-imagen-cat" class="flex-1 px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border text-sm" placeholder="https://ejemplo.com/imagen.jpg">
                            <button type="button" id="btn-descargar-url-cat" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm">
                                Descargar
                            </button>
                        </div>
                        <div id="error-url-cat" class="mt-1 text-sm text-red-500 hidden"></div>
                    </div>
                    <div id="area-recorte-cat" class="hidden space-y-4">
                        <div class="mb-4">
                            <h4 class="text-md font-semibold text-gray-700 dark:text-gray-200 mb-2">Recortar imagen</h4>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">Selecciona el área que deseas mantener. Se redimensionará a 144×120.</p>
                            <div id="contenedor-cropper-cat" style="max-width: 650px; max-height: 450px; margin: 0 auto; overflow: hidden;">
                                <img id="imagen-recortar-cat" src="" alt="Imagen a recortar" style="display: block; max-width: 100%; height: auto;">
                            </div>
                        </div>
                    </div>
                </div>

                <div id="content-subir-cat" class="tab-content-modal-cat space-y-4 hidden">
                    <div>
                        <label class="block mb-2 text-sm text-gray-600 dark:text-gray-400">Seleccionar imagen</label>
                        <input type="file" id="file-subir-cat" accept="image/*" class="hidden">
                        <button type="button" id="btn-seleccionar-cat" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm mb-2">
                            Seleccionar archivo
                        </button>
                        <div id="drop-zone-cat" class="mt-2 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-4 text-center hover:border-blue-400 dark:hover:border-blue-500 transition-colors">
                            <div class="text-gray-500 dark:text-gray-400">
                                <svg class="mx-auto h-8 w-8 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                </svg>
                                <p>Arrastra y suelta la imagen aquí</p>
                                <p class="text-xs">o haz clic para seleccionar</p>
                            </div>
                        </div>
                        <span id="nombre-archivo-cat" class="text-sm text-gray-500 dark:text-gray-400"></span>
                    </div>
                </div>

                <div id="content-amazon-cat" class="tab-content-modal-cat space-y-4 hidden">
                    <div>
                        <label class="block mb-2 text-sm text-gray-600 dark:text-gray-400">URL del producto de Amazon</label>
                        <div class="flex gap-2">
                            <input type="url" id="url-amazon-cat" class="flex-1 px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border text-sm" placeholder="https://www.amazon.es/dp/XXXXXXXXXX">
                            <button type="button" id="btn-buscar-amazon-cat" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm">
                                Buscar
                            </button>
                        </div>
                        <div id="error-amazon-cat" class="mt-1 text-sm text-red-500 hidden"></div>
                        <div id="loading-amazon-cat" class="mt-2 text-sm text-gray-500 dark:text-gray-400 hidden">Buscando imágenes...</div>
                    </div>
                    <div id="imagenes-amazon-cat" class="hidden">
                        <label class="block mb-2 text-sm font-semibold text-gray-700 dark:text-gray-200">Selecciona una imagen:</label>
                        <div id="grid-imagenes-amazon-cat" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 max-h-96 overflow-y-auto p-2"></div>
                    </div>
                </div>

                <div id="content-interna-global-cat" class="tab-content-modal-cat space-y-4 hidden" data-kp-ig-prefix="cat">
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Imágenes recientes del almacén. Pulsa una palabra del nombre de la categoría para buscar. Haz clic en una miniatura para seleccionarla (solo una). Si ya es 144×120 se reutiliza tal cual; si es más grande se redimensionará y guardará en <strong class="font-medium">categorias</strong>. Pulsa <strong class="font-medium">Guardar</strong> al terminar.
                    </p>
                    <div class="kp-ig-panel border border-gray-200 dark:border-gray-600 rounded-lg p-3 bg-gray-50/80 dark:bg-gray-800/40 space-y-3" data-kp-ig-panel="cat">
                        <div class="kp-ig-seleccion border border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-3 bg-white/70 dark:bg-gray-900/50">
                            <p class="text-xs font-medium text-gray-700 dark:text-gray-300 mb-2">Seleccionada para guardar</p>
                            <div class="kp-ig-seleccion-resumen text-xs text-gray-500 dark:text-gray-400">Ninguna imagen seleccionada.</div>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Palabras del nombre de la categoría</p>
                            <div class="kp-ig-palabras flex flex-wrap gap-1.5 min-h-[1.25rem]"></div>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Buscar por nombre de archivo</label>
                            <input type="text" class="kp-ig-buscador w-full px-3 py-1.5 text-sm rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-200 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Ej: panales talla 4" autocomplete="off" />
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
                    <button type="button" onclick="cerrarModalAñadirImagenCategoria()" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">
                        Cancelar
                    </button>
                    <button type="button" id="btn-guardar-cat" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700">
                        Guardar
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- SCRIPTS PARA GESTIÓN DE IMÁGENES DE CATEGORÍAS --}}
    <script>
        // Variables globales
        let categoriasRaiz = [];
        let categoriaActual = null;
        let parentIdActual = null;

        function obtenerAlpineCategoriaForm() {
            const el = document.getElementById('categoria-form-alpine');
            if (!el) {
                return null;
            }
            if (typeof Alpine !== 'undefined' && typeof Alpine.$data === 'function') {
                return Alpine.$data(el);
            }
            return el._x_dataStack?.[0] ?? null;
        }

        function verificarSlugInicialSiEdicion() {
            if (!categoriaActual) {
                return;
            }
            const el = document.getElementById('categoria-form-alpine');
            if (el) {
                el.dispatchEvent(new CustomEvent('verificar-slug-categoria'));
            }
        }

        // Inicialización cuando se carga la página
        document.addEventListener('DOMContentLoaded', () => {
            const datos = document.getElementById('datos-categorias');
            if (datos) {
                const categoriasData = datos.dataset.categoriasRaiz;
                const categoriaData = datos.dataset.categoriaActual;
                const parentIdData = datos.dataset.parentId;
                
                try {
                    categoriasRaiz = JSON.parse(categoriasData || '[]');
                    categoriaActual = categoriaData && categoriaData !== 'null' ? JSON.parse(categoriaData) : null;
                    parentIdActual = parentIdData || null;
                    
                    if (categoriaActual && parentIdActual) {
                        // Modo edición: cargar jerarquía completa
                        cargarJerarquiaCategoria(parentIdActual);
                    } else if (categoriasRaiz.length > 0) {
                        // Modo creación: crear solo el primer selector
                        crearSelectorCategoria(0, categoriasRaiz, null);
                    } else {
                        const container = document.getElementById('categorias-container');
                        container.innerHTML = '<p class="text-gray-500">No hay categorías padre disponibles. Puedes crear una categoría raíz.</p>';
                    }
                } catch (error) {
                    console.error('Error parseando datos:', error);
                }
            }
        });

        document.addEventListener('alpine:initialized', () => {
            verificarSlugInicialSiEdicion();
        });

        if (typeof window.Alpine !== 'undefined') {
            queueMicrotask(verificarSlugInicialSiEdicion);
        }

        async function cargarJerarquiaCategoria(parentId) {
            // Obtener la jerarquía completa desde la raíz hasta el padre
            try {
                const response = await fetch(`/panel-privado/categorias/${parentId}/jerarquia`, {
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                });
                
                const data = await response.json();
                const jerarquia = data.jerarquia || [];
                
                // Construir selectores nivel por nivel
                for (let i = 0; i < jerarquia.length; i++) {
                    const categoriaId = jerarquia[i].id;
                    let opciones = [];
                    
                    if (i === 0) {
                        // Primer nivel: categorías raíz
                        opciones = categoriasRaiz;
                    } else {
                        // Niveles siguientes: subcategorías del nivel anterior
                        const parentIdAnterior = jerarquia[i - 1].id;
                        const subcategorias = await obtenerSubcategorias(parentIdAnterior);
                        opciones = subcategorias;
                    }
                    
                    // Crear el selector con el valor seleccionado
                    crearSelectorCategoria(i, opciones, categoriaId, true); // true = modo edición, no disparar eventos
                    
                    // Esperar un poco para que el DOM se actualice
                    await new Promise(resolve => setTimeout(resolve, 50));
                    
                    // Si no es el último nivel, cargar las subcategorías del siguiente nivel directamente
                    if (i < jerarquia.length - 1) {
                        const siguienteCategoriaId = jerarquia[i + 1].id;
                        const subcategorias = await obtenerSubcategorias(categoriaId);
                        
                        // Crear el selector del siguiente nivel
                        if (subcategorias && subcategorias.length > 0) {
                            crearSelectorCategoria(i + 1, subcategorias, siguienteCategoriaId, true);
                            await new Promise(resolve => setTimeout(resolve, 50));
                        }
                    }
                }
                
                // Actualizar campo final
                actualizarCategoriaFinal();
            } catch (error) {
                console.error('Error cargando jerarquía:', error);
            }
        }

        async function obtenerSubcategorias(parentId) {
            const url = `/panel-privado/categorias/${parentId}/subcategorias`;
            const response = await fetch(url, {
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            });
            return await response.json();
        }

        function crearSelectorCategoria(nivel, opciones, valorSeleccionado, modoEdicion = false) {
            const container = document.getElementById('categorias-container');
            if (!container) {
                console.error('No se encontró el contenedor de categorías');
                return;
            }
            
            // Verificar si ya existe un selector en este nivel (para evitar duplicados)
            const selectorExistente = container.querySelector(`.selector-categoria[data-nivel="${nivel}"]`);
            if (selectorExistente) {
                // Actualizar el selector existente
                const selectExistente = selectorExistente.querySelector('select');
                if (selectExistente) {
                    // Limpiar opciones actuales (excepto la opción por defecto)
                    while (selectExistente.options.length > 1) {
                        selectExistente.remove(1);
                    }
                    // Agregar nuevas opciones
                    const opcionesOrdenadas = [...opciones].sort((a, b) => a.nombre.localeCompare(b.nombre, 'es', {sensitivity: 'base'}));
                    opcionesOrdenadas.forEach(cat => {
                        const option = document.createElement('option');
                        option.value = cat.id;
                        option.textContent = cat.nombre;
                        if (valorSeleccionado == cat.id) {
                            option.selected = true;
                        }
                        selectExistente.appendChild(option);
                    });
                }
                return;
            }
            
            // Crear contenedor del selector
            const div = document.createElement('div');
            div.className = 'selector-categoria flex items-center gap-4 mb-4';
            div.dataset.nivel = nivel;
            
            // Crear label
            const label = document.createElement('label');
            label.className = 'block mb-1 font-medium text-gray-700 dark:text-gray-200 min-w-[120px]';
            label.textContent = nivel === 0 ? 'Categoría padre' : `Subcategoría ${nivel}`;
            
            // Crear select
            const select = document.createElement('select');
            select.className = 'categoria-select w-full px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border';
            select.dataset.nivel = nivel;
            
            // Opción por defecto
            const optionDefault = document.createElement('option');
            optionDefault.value = '';
            optionDefault.textContent = nivel === 0 ? '-- Ninguna (raíz) --' : '-- Ninguna --';
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
                removeBtn.onclick = () => {
                    eliminarSelectorYSuperiores(nivel);
                };
            }
            
            // Ensamblar
            div.appendChild(label);
            div.appendChild(select);
            if (removeBtn) div.appendChild(removeBtn);
            
            container.appendChild(div);
            
            // Configurar evento change solo si no estamos en modo edición
            if (!modoEdicion) {
                select.addEventListener('change', () => {
                    const categoriaId = select.value;
                    
                    if (categoriaId) {
                        cargarSubcategorias(nivel + 1, categoriaId);
                        actualizarCategoriaFinal();
                    } else {
                        limpiarSelectoresSuperiores(nivel + 1);
                        actualizarCategoriaFinal();
                    }
                });
            } else {
                // En modo edición, también agregar el evento pero sin crear selectores automáticamente
                select.addEventListener('change', () => {
                    const categoriaId = select.value;
                    
                    if (categoriaId) {
                        cargarSubcategorias(nivel + 1, categoriaId);
                        actualizarCategoriaFinal();
                    } else {
                        limpiarSelectoresSuperiores(nivel + 1);
                        actualizarCategoriaFinal();
                    }
                });
            }
        }

        function cargarSubcategorias(nivel, categoriaId) {
            limpiarSelectoresSuperiores(nivel);
            
            const url = `/panel-privado/categorias/${categoriaId}/subcategorias`;
            
            fetch(url, {
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            })
            .then(res => res.json())
            .then(subcategorias => {
                if (subcategorias && subcategorias.length > 0) {
                    crearSelectorCategoria(nivel, subcategorias, null);
                }
            })
            .catch(error => {
                console.error('Error cargando subcategorías:', error);
            });
        }

        function limpiarSelectoresSuperiores(nivel) {
            const selectores = document.querySelectorAll(`.selector-categoria[data-nivel="${nivel}"]`);
            selectores.forEach(selector => selector.remove());
        }

        function eliminarSelectorYSuperiores(nivel) {
            const selectores = document.querySelectorAll(`.selector-categoria[data-nivel="${nivel}"]`);
            selectores.forEach(selector => selector.remove());
            actualizarCategoriaFinal();
        }

        function actualizarCategoriaFinal() {
            const selectores = document.querySelectorAll('.categoria-select');
            let categoriaFinal = null;
            
            for (let i = selectores.length - 1; i >= 0; i--) {
                if (selectores[i].value) {
                    categoriaFinal = selectores[i].value;
                    break;
                }
            }
            
            document.getElementById('categoria-final').value = categoriaFinal || '';
        }


        // Habilitar botón de guardar cuando hay nombre
        document.addEventListener('DOMContentLoaded', function() {
            const nombreInput = document.getElementById('nombre-categoria');
            const btnGuardar = document.getElementById('btn-guardar-categoria');
            
            if (nombreInput && btnGuardar) {
                nombreInput.addEventListener('input', function() {
                    const tieneNombre = this.value.trim().length > 0;
                    const scope = obtenerAlpineCategoriaForm();
                    const slugExiste = scope ? scope.slugExiste : false;
                    btnGuardar.disabled = !tieneNombre || slugExiste;
                });
            }
        });
    </script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/cropperjs@1.5.13/dist/cropper.min.css">
    <script src="https://cdn.jsdelivr.net/npm/cropperjs@1.5.13/dist/cropper.min.js"></script>

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
            est.wrapPalabras.innerHTML = '<span class="text-xs text-gray-500 dark:text-gray-400">Escribe el nombre de la categoría para ver sugerencias.</span>';
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
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.tooltip-btn[data-tooltip]').forEach(function(btn) {
            btn.addEventListener('mouseenter', function() {
                const texto = btn.getAttribute('data-tooltip');
                if (!texto) return;
                let tip = document.getElementById('kp-tooltip-global-cat');
                if (!tip) {
                    tip = document.createElement('div');
                    tip.id = 'kp-tooltip-global-cat';
                    tip.className = 'fixed z-[9999] max-w-xs px-3 py-2 text-xs text-white bg-gray-900 rounded shadow-lg pointer-events-none';
                    document.body.appendChild(tip);
                }
                tip.textContent = texto;
                tip.classList.remove('hidden');
                const rect = btn.getBoundingClientRect();
                tip.style.left = Math.min(rect.left, window.innerWidth - 280) + 'px';
                tip.style.top = (rect.bottom + 6) + 'px';
            });
            btn.addEventListener('mouseleave', function() {
                const tip = document.getElementById('kp-tooltip-global-cat');
                if (tip) tip.classList.add('hidden');
            });
        });

        (function initAyudaConfigFormularioProducto() {
            const selectConfig = document.getElementById('configuracion_formulario_producto');
            const btnAyuda = document.getElementById('ayuda-configuracion-formulario-producto');
            if (!selectConfig || !btnAyuda) return;

            const valorConAyuda = 'no_columna_grupo_mostrar_imagen_nombre_precio';
            let tooltipActual = null;

            function actualizarVisibilidadAyuda() {
                if (selectConfig.value === valorConAyuda) {
                    btnAyuda.classList.remove('hidden');
                } else {
                    btnAyuda.classList.add('hidden');
                    if (tooltipActual) {
                        tooltipActual.remove();
                        tooltipActual = null;
                    }
                }
            }

            selectConfig.addEventListener('change', actualizarVisibilidadAyuda);
            actualizarVisibilidadAyuda();

            btnAyuda.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                const texto = this.getAttribute('data-tooltip');
                if (!texto) return;

                if (tooltipActual) {
                    tooltipActual.remove();
                    tooltipActual = null;
                    return;
                }

                const tooltip = document.createElement('div');
                tooltip.className = 'fixed z-[9999] max-w-sm px-3 py-2 text-xs text-white bg-gray-900 rounded shadow-lg leading-relaxed';
                tooltip.textContent = texto;
                document.body.appendChild(tooltip);
                const rect = this.getBoundingClientRect();
                tooltip.style.left = Math.min(rect.left, window.innerWidth - 320) + 'px';
                tooltip.style.top = (rect.bottom + 6) + 'px';
                tooltipActual = tooltip;

                const cerrar = function(ev) {
                    if (tooltipActual && ev.target !== btnAyuda) {
                        tooltipActual.remove();
                        tooltipActual = null;
                        document.removeEventListener('click', cerrar);
                    }
                };
                setTimeout(function() {
                    document.addEventListener('click', cerrar);
                }, 0);
            });
        })();

        const formCatImg = document.getElementById('form-categoria');
        if (!formCatImg) return;

        const KP_CARPETA_CAT = 'categorias';
        const KP_W_CAT = 144;
        const KP_H_CAT = 120;
        const KP_PENDING_CAT = '__pending_cat:';
        const KP_IMG_BASE = @json(rtrim(asset('images/'), '/'));
        const KP_CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';
        const KP_SUBIR_URL = @json(route('admin.imagenes.subir-simple'));
        const KP_PROXY_URL = @json(route('admin.imagenes.proxy'));
        const KP_AMAZON_URL = @json(route('admin.productos.obtener-imagenes-amazon'));
        const KP_LIMPIAR_AMAZON_URL = @json(route('admin.ofertas.limpiar.url'));

        let rutaImagenCategoria = '';
        let subidaPendienteCat = false;
        let cropperCat = null;
        let carpetaActualUrlCat = KP_CARPETA_CAT;
        let imagenAmazonSeleccionadaCat = null;
        let seleccionInternaGlobalCat = null;

        const inputRuta = document.getElementById('ruta-imagen-categoria');
        const contenedor = document.getElementById('imagen-categoria-container');
        const textoRuta = document.getElementById('ruta-imagen-categoria-texto');
        const btnAñadir = document.getElementById('btn-añadir-imagen-categoria');
        const btnCambiar = document.getElementById('btn-cambiar-imagen-categoria');
        const modal = document.getElementById('modal-añadir-imagen-categoria');

        function obtenerSlugCategoria() {
            const slugInput = document.getElementById('slug-categoria');
            if (slugInput && slugInput.value.trim()) return slugInput.value.trim();
            const nombreInput = document.getElementById('nombre-categoria');
            return nombreInput ? nombreInput.value.trim() : '';
        }

        function validarSlugParaImagen() {
            if (!obtenerSlugCategoria()) {
                alert('Escribe el nombre o slug de la categoría antes de añadir la imagen.');
                return false;
            }
            return true;
        }

        function urlVistaCat(ruta) {
            if (!ruta || ruta.indexOf(KP_PENDING_CAT) === 0) return '';
            if (/^https?:\/\//i.test(ruta)) return ruta;
            return KP_IMG_BASE + '/' + ruta.replace(/^\/+/, '');
        }

        function aplicarRutaImagenCategoria(ruta) {
            rutaImagenCategoria = ruta || '';
            if (inputRuta) inputRuta.value = rutaImagenCategoria;
            if (textoRuta) {
                if (rutaImagenCategoria && rutaImagenCategoria.indexOf(KP_PENDING_CAT) !== 0) {
                    textoRuta.textContent = rutaImagenCategoria;
                    textoRuta.classList.remove('hidden');
                } else {
                    textoRuta.textContent = '';
                    textoRuta.classList.add('hidden');
                }
            }
            renderizarImagenCategoria();
            if (btnAñadir && btnCambiar) {
                const tiene = !!rutaImagenCategoria;
                btnAñadir.classList.toggle('hidden', tiene);
                btnCambiar.classList.toggle('hidden', !tiene);
            }
        }

        function renderizarImagenCategoria() {
            if (!contenedor) return;
            contenedor.innerHTML = '';
            if (!rutaImagenCategoria) return;

            const pendiente = rutaImagenCategoria.indexOf(KP_PENDING_CAT) === 0;
            const div = document.createElement('div');
            div.className = 'relative group';

            if (pendiente) {
                div.innerHTML = '<div class="w-[144px] h-[120px] flex flex-col items-center justify-center bg-gray-100 dark:bg-gray-700 rounded border border-gray-300 dark:border-gray-600"><span class="text-xs text-gray-600 dark:text-gray-300">Subiendo…</span></div>';
            } else {
                const src = urlVistaCat(rutaImagenCategoria);
                div.innerHTML = '<div class="relative inline-block">' +
                    '<img src="' + src + '" alt="Imagen categoría" class="w-[144px] h-[120px] object-cover rounded border border-gray-300 dark:border-gray-600 block" ' +
                    'onerror="this.src=\'data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'144\' height=\'120\'%3E%3Crect width=\'144\' height=\'120\' fill=\'%23ddd\'/%3E%3C/svg%3E\'">' +
                    '<button type="button" class="btn-quitar-imagen-cat absolute top-0 right-0 bg-red-600 hover:bg-red-700 text-white rounded-full w-7 h-7 flex items-center justify-center font-bold text-base opacity-0 group-hover:opacity-100 transition-opacity shadow-lg" style="transform: translate(25%, -25%); z-index: 30;" title="Quitar">×</button>' +
                    '</div>';
                div.querySelector('.btn-quitar-imagen-cat')?.addEventListener('click', function(e) {
                    e.stopPropagation();
                    aplicarRutaImagenCategoria('');
                });
            }
            contenedor.appendChild(div);
        }

        async function blobDesdeCanvas144x120(sourceCanvasOrImage) {
            const canvas = document.createElement('canvas');
            canvas.width = KP_W_CAT;
            canvas.height = KP_H_CAT;
            const ctx = canvas.getContext('2d');
            ctx.imageSmoothingEnabled = true;
            ctx.imageSmoothingQuality = 'high';
            const w = sourceCanvasOrImage.width || sourceCanvasOrImage.naturalWidth;
            const h = sourceCanvasOrImage.height || sourceCanvasOrImage.naturalHeight;
            ctx.drawImage(sourceCanvasOrImage, 0, 0, w, h, 0, 0, KP_W_CAT, KP_H_CAT);
            return new Promise(function(resolve, reject) {
                canvas.toBlob(function(b) { b ? resolve(b) : reject(new Error('Error al convertir imagen')); }, 'image/webp', 0.9);
            });
        }

        async function cargarImagenDesdeRuta(ruta) {
            const src = window.kpUrlVistaDesdeRutaAlmacenIg
                ? window.kpUrlVistaDesdeRutaAlmacenIg(ruta)
                : urlVistaCat(ruta);
            return new Promise(function(resolve, reject) {
                const im = new Image();
                im.crossOrigin = 'anonymous';
                im.onload = function() { resolve(im); };
                im.onerror = function() { reject(new Error('No se pudo cargar la imagen de origen')); };
                im.src = src;
            });
        }

        async function subirBlobCategoria(blob) {
            const nombreBase = obtenerSlugCategoria() || ('categoria_' + Date.now());
            const formData = new FormData();
            formData.append('imagen', blob, nombreBase + '.webp');
            formData.append('carpeta', KP_CARPETA_CAT);
            formData.append('_token', KP_CSRF);
            const res = await fetch(KP_SUBIR_URL, {
                method: 'POST',
                body: formData,
                headers: { 'X-CSRF-TOKEN': KP_CSRF, 'Accept': 'application/json' },
            });
            const data = await res.json();
            if (!data.success) throw new Error(data.message || 'Error al subir');
            return data.data.ruta_relativa;
        }

        function normalizarRutaAlmacenCat(ruta) {
            return (ruta || '').trim().replace(/^\/+/, '');
        }

        /** Interna global: reutiliza la ruta si ya es 144×120; solo redimensiona y sube a categorias si es más grande. */
        async function resolverRutaImagenInternaGlobalCategoria(rutaOrigen) {
            const ruta = normalizarRutaAlmacenCat(rutaOrigen);
            if (!ruta) throw new Error('Ruta de imagen no válida');
            const img = await cargarImagenDesdeRuta(ruta);
            const w = img.naturalWidth || img.width;
            const h = img.naturalHeight || img.height;
            if (w === KP_W_CAT && h === KP_H_CAT) {
                return ruta;
            }
            if (w > KP_W_CAT || h > KP_H_CAT) {
                const blob = await blobDesdeCanvas144x120(img);
                return subirBlobCategoria(blob);
            }
            return ruta;
        }

        function kpPairKeyCat(pair) {
            return (pair.rutaGrande || '') + '|' + (pair.rutaPequena || '');
        }

        window.cerrarModalAñadirImagenCategoria = function() {
            if (modal) modal.classList.add('hidden');
            limpiarModalCat();
        };

        function limpiarModalCat() {
            const fileInput = document.getElementById('file-subir-cat');
            if (fileInput) fileInput.value = '';
            const urlIn = document.getElementById('url-imagen-cat');
            if (urlIn) urlIn.value = '';
            const urlAmz = document.getElementById('url-amazon-cat');
            if (urlAmz) urlAmz.value = '';
            const nomArch = document.getElementById('nombre-archivo-cat');
            if (nomArch) nomArch.textContent = '';
            const errUrl = document.getElementById('error-url-cat');
            if (errUrl) errUrl.classList.add('hidden');
            const errAmz = document.getElementById('error-amazon-cat');
            if (errAmz) errAmz.classList.add('hidden');
            const loadAmz = document.getElementById('loading-amazon-cat');
            if (loadAmz) loadAmz.classList.add('hidden');
            const imgsAmz = document.getElementById('imagenes-amazon-cat');
            if (imgsAmz) imgsAmz.classList.add('hidden');
            const gridAmz = document.getElementById('grid-imagenes-amazon-cat');
            if (gridAmz) gridAmz.innerHTML = '';
            const areaRec = document.getElementById('area-recorte-cat');
            if (areaRec) areaRec.classList.add('hidden');
            imagenAmazonSeleccionadaCat = null;
            if (cropperCat) { cropperCat.destroy(); cropperCat = null; }
        }

        function abrirModalCat(tab) {
            if (!validarSlugParaImagen()) return;
            if (modal) modal.classList.remove('hidden');
            limpiarModalCat();
            cambiarTabModalCat(tab || 'url');
        }

        function cambiarTabModalCat(tab) {
            const tabUrl = document.getElementById('tab-url-cat');
            const tabSubir = document.getElementById('tab-subir-cat');
            const tabAmazon = document.getElementById('tab-amazon-cat');
            const tabIg = document.getElementById('tab-interna-global-cat');
            const cUrl = document.getElementById('content-url-cat');
            const cSubir = document.getElementById('content-subir-cat');
            const cAmazon = document.getElementById('content-amazon-cat');
            const cIg = document.getElementById('content-interna-global-cat');
            const allTabs = [tabUrl, tabSubir, tabAmazon, tabIg];
            [cUrl, cSubir, cAmazon, cIg].forEach(function(c) { if (c) c.classList.add('hidden'); });
            if (tab === 'url') {
                window.kpModalImgTabSetActive(allTabs, tabUrl);
                cUrl.classList.remove('hidden');
            } else if (tab === 'subir') {
                window.kpModalImgTabSetActive(allTabs, tabSubir);
                cSubir.classList.remove('hidden');
            } else if (tab === 'amazon') {
                window.kpModalImgTabSetActive(allTabs, tabAmazon);
                cAmazon.classList.remove('hidden');
            } else if (tab === 'interna-global') {
                window.kpModalImgTabSetActive(allTabs, tabIg);
                cIg.classList.remove('hidden');
                if (typeof window.kpInternaGlobalAlActivar === 'function') {
                    window.kpInternaGlobalAlActivar('cat');
                }
            }
        }

        if (btnAñadir) btnAñadir.addEventListener('click', function() { abrirModalCat('url'); });
        if (btnCambiar) btnCambiar.addEventListener('click', function() { abrirModalCat('url'); });

        document.getElementById('tab-url-cat')?.addEventListener('click', function() { cambiarTabModalCat('url'); });
        document.getElementById('tab-subir-cat')?.addEventListener('click', function() { cambiarTabModalCat('subir'); });
        document.getElementById('tab-amazon-cat')?.addEventListener('click', function() { cambiarTabModalCat('amazon'); });
        document.getElementById('tab-interna-global-cat')?.addEventListener('click', function() { cambiarTabModalCat('interna-global'); });

        const nombreCatInput = document.getElementById('nombre-categoria');
        if (nombreCatInput) {
            nombreCatInput.addEventListener('input', function() {
                if (typeof window.kpInternaGlobalRefrescarPalabras === 'function') {
                    window.kpInternaGlobalRefrescarPalabras('cat');
                }
            });
        }

        async function procesarArchivoSubir(file) {
            if (!validarSlugParaImagen()) return;
            if (!file.type.startsWith('image/')) {
                alert('Selecciona un archivo de imagen válido.');
                return;
            }
            if (file.size > 5 * 1024 * 1024) {
                alert('La imagen es demasiado grande. Máximo 5MB.');
                return;
            }
            const pending = KP_PENDING_CAT + Date.now();
            aplicarRutaImagenCategoria(pending);
            subidaPendienteCat = true;
            cerrarModalAñadirImagenCategoria();
            try {
                const urlRevoke = URL.createObjectURL(file);
                const img = await new Promise(function(resolve, reject) {
                    const im = new Image();
                    im.onload = function() { URL.revokeObjectURL(urlRevoke); resolve(im); };
                    im.onerror = function() { URL.revokeObjectURL(urlRevoke); reject(new Error('Imagen no válida')); };
                    im.src = urlRevoke;
                });
                const blob = await blobDesdeCanvas144x120(img);
                const ruta = await subirBlobCategoria(blob);
                aplicarRutaImagenCategoria(ruta);
            } catch (err) {
                console.error(err);
                alert('Error al subir la imagen: ' + (err.message || err));
                aplicarRutaImagenCategoria('');
            } finally {
                subidaPendienteCat = false;
            }
        }

        const fileSubir = document.getElementById('file-subir-cat');
        const btnSel = document.getElementById('btn-seleccionar-cat');
        const dropZone = document.getElementById('drop-zone-cat');
        if (btnSel && fileSubir) btnSel.addEventListener('click', function() { fileSubir.click(); });
        if (dropZone) {
            dropZone.addEventListener('click', function() { fileSubir?.click(); });
            dropZone.addEventListener('dragover', function(e) {
                e.preventDefault();
                dropZone.classList.add('border-blue-500', 'bg-blue-50', 'dark:bg-blue-900/20');
            });
            dropZone.addEventListener('dragleave', function() {
                dropZone.classList.remove('border-blue-500', 'bg-blue-50', 'dark:bg-blue-900/20');
            });
            dropZone.addEventListener('drop', function(e) {
                e.preventDefault();
                dropZone.classList.remove('border-blue-500', 'bg-blue-50', 'dark:bg-blue-900/20');
                if (e.dataTransfer.files.length) procesarArchivoSubir(e.dataTransfer.files[0]);
            });
        }
        if (fileSubir) {
            fileSubir.addEventListener('change', function(e) {
                if (e.target.files.length) procesarArchivoSubir(e.target.files[0]);
            });
        }

        document.getElementById('btn-descargar-url-cat')?.addEventListener('click', function() {
            const url = document.getElementById('url-imagen-cat')?.value.trim();
            const err = document.getElementById('error-url-cat');
            if (!url) {
                if (err) { err.textContent = 'Introduce una URL válida.'; err.classList.remove('hidden'); }
                return;
            }
            if (err) err.classList.add('hidden');
            carpetaActualUrlCat = KP_CARPETA_CAT;
            mostrarRecorteCat(url);
        });

        function mostrarRecorteCat(urlImagen) {
            const area = document.getElementById('area-recorte-cat');
            const img = document.getElementById('imagen-recortar-cat');
            if (!area || !img) return;
            area.classList.remove('hidden');
            if (cropperCat) cropperCat.destroy();
            const urlProxy = /^https?:\/\//i.test(urlImagen)
                ? KP_PROXY_URL + '?url=' + encodeURIComponent(urlImagen)
                : urlImagen;
            img.crossOrigin = 'anonymous';
            img.src = urlProxy;
            img.onload = function() {
                if (cropperCat) cropperCat.destroy();
                cropperCat = new Cropper(img, {
                    aspectRatio: KP_W_CAT / KP_H_CAT,
                    viewMode: 1,
                    dragMode: 'move',
                    autoCropArea: 0.9,
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
                const err = document.getElementById('error-url-cat');
                if (err) { err.textContent = 'Error al cargar la imagen.'; err.classList.remove('hidden'); }
                area.classList.add('hidden');
            };
        }

        async function limpiarUrlAmazonViaApi(url) {
            if (!url || !url.trim()) return url || '';
            try {
                const res = await fetch(KP_LIMPIAR_AMAZON_URL, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': KP_CSRF },
                    body: JSON.stringify({ url: url.trim() }),
                });
                if (!res.ok) return url.trim();
                const data = await res.json();
                return data.url_limpia ?? url.trim();
            } catch (e) {
                return url.trim();
            }
        }

        const urlAmazonInput = document.getElementById('url-amazon-cat');
        if (urlAmazonInput) {
            urlAmazonInput.addEventListener('paste', function() {
                setTimeout(async function() {
                    const u = urlAmazonInput.value.trim();
                    if (u) {
                        const limpia = await limpiarUrlAmazonViaApi(u);
                        if (limpia !== u) urlAmazonInput.value = limpia;
                    }
                }, 10);
            });
            urlAmazonInput.addEventListener('input', function(e) {
                const url = e.target.value.trim();
                if (!url || !url.includes('amazon')) return;
                limpiarUrlAmazonViaApi(url).then(function(limpia) {
                    if (limpia !== url && limpia.length < url.length) e.target.value = limpia;
                });
            });
        }

        document.getElementById('btn-buscar-amazon-cat')?.addEventListener('click', async function() {
            const urlInput = document.getElementById('url-amazon-cat');
            const errorDiv = document.getElementById('error-amazon-cat');
            const loadingDiv = document.getElementById('loading-amazon-cat');
            const imagenesDiv = document.getElementById('imagenes-amazon-cat');
            const gridDiv = document.getElementById('grid-imagenes-amazon-cat');
            const url = urlInput?.value.trim();
            if (!url) {
                if (errorDiv) { errorDiv.textContent = 'Introduce una URL de Amazon'; errorDiv.classList.remove('hidden'); }
                return;
            }
            if (errorDiv) errorDiv.classList.add('hidden');
            if (loadingDiv) loadingDiv.classList.remove('hidden');
            if (imagenesDiv) imagenesDiv.classList.add('hidden');
            if (gridDiv) gridDiv.innerHTML = '';
            imagenAmazonSeleccionadaCat = null;
            try {
                const response = await fetch(KP_AMAZON_URL, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': KP_CSRF },
                    body: JSON.stringify({ url: url }),
                });
                const data = await response.json();
                if (!data.success) throw new Error(data.error || 'Error al obtener imágenes');
                if (!data.imagenes || !data.imagenes.length) {
                    if (errorDiv) { errorDiv.textContent = 'No se encontraron imágenes'; errorDiv.classList.remove('hidden'); }
                    return;
                }
                data.imagenes.forEach(function(imagen, index) {
                    const div = document.createElement('div');
                    div.className = 'relative border-2 border-gray-300 dark:border-gray-600 rounded-lg overflow-hidden cursor-pointer hover:border-blue-500';
                    const img = document.createElement('img');
                    img.src = imagen.url;
                    img.className = 'w-full h-32 object-contain';
                    const radio = document.createElement('input');
                    radio.type = 'radio';
                    radio.name = 'amazon-cat-sel';
                    radio.className = 'absolute top-2 right-2 w-5 h-5';
                    div.appendChild(img);
                    div.appendChild(radio);
                    div.addEventListener('click', function() {
                        gridDiv.querySelectorAll('input[type=radio]').forEach(function(r) { r.checked = false; });
                        radio.checked = true;
                        imagenAmazonSeleccionadaCat = imagen;
                        gridDiv.querySelectorAll('div').forEach(function(d) { d.classList.remove('ring-4', 'ring-blue-500'); });
                        div.classList.add('ring-4', 'ring-blue-500');
                    });
                    gridDiv.appendChild(div);
                });
                if (imagenesDiv) imagenesDiv.classList.remove('hidden');
            } catch (error) {
                if (errorDiv) { errorDiv.textContent = error.message || 'Error'; errorDiv.classList.remove('hidden'); }
            } finally {
                if (loadingDiv) loadingDiv.classList.add('hidden');
            }
        });

        async function procesarImagenAmazonCat() {
            if (!imagenAmazonSeleccionadaCat) {
                alert('Selecciona una imagen de Amazon');
                return;
            }
            const pending = KP_PENDING_CAT + 'amz';
            aplicarRutaImagenCategoria(pending);
            cerrarModalAñadirImagenCategoria();
            try {
                const urlProxy = imagenAmazonSeleccionadaCat.url.startsWith('http')
                    ? KP_PROXY_URL + '?url=' + encodeURIComponent(imagenAmazonSeleccionadaCat.url)
                    : imagenAmazonSeleccionadaCat.url;
                const img = await new Promise(function(resolve, reject) {
                    const im = new Image();
                    im.crossOrigin = 'anonymous';
                    im.onload = function() { resolve(im); };
                    im.onerror = function() { reject(new Error('Error al cargar imagen Amazon')); };
                    im.src = urlProxy;
                });
                const blob = await blobDesdeCanvas144x120(img);
                const ruta = await subirBlobCategoria(blob);
                aplicarRutaImagenCategoria(ruta);
            } catch (err) {
                alert('Error: ' + (err.message || err));
                aplicarRutaImagenCategoria('');
            }
        }

        async function procesarRecorteUrlCat() {
            if (!cropperCat) {
                alert('Descarga y recorta la imagen primero.');
                return;
            }
            const canvasOriginal = cropperCat.getCroppedCanvas({ imageSmoothingEnabled: true, imageSmoothingQuality: 'high' });
            if (!canvasOriginal) {
                alert('Error al recortar');
                return;
            }
            const pending = KP_PENDING_CAT + 'url';
            aplicarRutaImagenCategoria(pending);
            cerrarModalAñadirImagenCategoria();
            try {
                const blob = await blobDesdeCanvas144x120(canvasOriginal);
                const ruta = await subirBlobCategoria(blob);
                aplicarRutaImagenCategoria(ruta);
            } catch (err) {
                alert('Error: ' + (err.message || err));
                aplicarRutaImagenCategoria('');
            }
        }

        async function procesarInternaGlobalCat() {
            if (!seleccionInternaGlobalCat) {
                alert('Selecciona una imagen del almacén');
                return;
            }
            const rutaOrigen = seleccionInternaGlobalCat.rutaPequena || seleccionInternaGlobalCat.rutaGrande || seleccionInternaGlobalCat.thumbVisual;
            const pending = KP_PENDING_CAT + 'ig';
            aplicarRutaImagenCategoria(pending);
            cerrarModalAñadirImagenCategoria();
            try {
                const ruta = await resolverRutaImagenInternaGlobalCategoria(rutaOrigen);
                aplicarRutaImagenCategoria(ruta);
                seleccionInternaGlobalCat = null;
            } catch (err) {
                alert('Error al procesar la imagen: ' + (err.message || err));
                aplicarRutaImagenCategoria('');
            }
        }

        document.getElementById('btn-guardar-cat')?.addEventListener('click', async function() {
            const tabActiva = document.querySelector('.tab-modal-cat.kp-modal-img-tab--active');
            const tabId = tabActiva ? tabActiva.id : 'tab-url-cat';
            if (tabId === 'tab-subir-cat') {
                if (!fileSubir?.files?.length) alert('Selecciona un archivo primero.');
                return;
            }
            if (tabId === 'tab-amazon-cat') {
                await procesarImagenAmazonCat();
                return;
            }
            if (tabId === 'tab-interna-global-cat') {
                await procesarInternaGlobalCat();
                return;
            }
            if (tabId === 'tab-url-cat') {
                await procesarRecorteUrlCat();
            }
        });

        if (typeof window.kpInternaGlobalRegistrar === 'function') {
            window.kpInternaGlobalRegistrar('cat', {
                onSelect: function(pair) {
                    if (seleccionInternaGlobalCat && kpPairKeyCat(seleccionInternaGlobalCat) === kpPairKeyCat(pair)) {
                        seleccionInternaGlobalCat = null;
                    } else {
                        seleccionInternaGlobalCat = pair;
                    }
                    if (typeof window.kpInternaGlobalRefrescarSeleccion === 'function') {
                        window.kpInternaGlobalRefrescarSeleccion('cat');
                    }
                },
                isSelected: function(pair) {
                    return seleccionInternaGlobalCat && kpPairKeyCat(seleccionInternaGlobalCat) === kpPairKeyCat(pair);
                },
                renderResumen: function() {
                    const el = document.querySelector('[data-kp-ig-panel="cat"] .kp-ig-seleccion-resumen');
                    const pairs = seleccionInternaGlobalCat ? [seleccionInternaGlobalCat] : [];
                    window.kpIgPintarResumenSeleccion(el, pairs);
                },
                getPalabras: function() {
                    const nombre = document.getElementById('nombre-categoria')?.value || '';
                    return window.kpPartirNombreEnPalabras(nombre);
                },
            });
        }

        formCatImg.addEventListener('submit', function(e) {
            if (subidaPendienteCat || (rutaImagenCategoria && rutaImagenCategoria.indexOf(KP_PENDING_CAT) === 0)) {
                e.preventDefault();
                alert('Espera a que termine de subirse la imagen antes de guardar la categoría.');
            }
        });

        if (inputRuta && inputRuta.value) {
            aplicarRutaImagenCategoria(inputRuta.value);
        } else {
            renderizarImagenCategoria();
            if (btnAñadir && btnCambiar) {
                btnCambiar.classList.add('hidden');
            }
        }
    });
    </script>


    {{-- SCRIPT PARA ESPECIFICACIONES INTERNAS --}}
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const container = document.getElementById('especificaciones-container');
        const inputHidden = document.getElementById('especificaciones-internas-input');
        
        if (!container || !inputHidden) {
            return;
        }

        let contadorPrincipal = 0;
        let contadorIntermedia = 0;
        let datosCargados = false;
        
        // Obtener conteos de productos desde el servidor
        const conteosProductos = @json($conteosProductos ?? []);
        
        // Función para obtener el contador de productos de una sublínea
        function obtenerContadorProductos(lineaId, sublineaId) {
            if (!conteosProductos || !conteosProductos[lineaId]) {
                return null;
            }
            return conteosProductos[lineaId][sublineaId] ?? null;
        }
        
        // Generar ID único para líneas
        function generarIdUnico() {
            return Date.now() + '_' + Math.random().toString(36).substr(2, 9);
        }

        // Inicializar con datos existentes o 3 líneas por defecto
        function inicializar() {
            const valorActual = inputHidden.value;
            let datos = null;
            
            if (valorActual && valorActual.trim() !== '') {
                try {
                    const parsed = JSON.parse(valorActual);
                    if (parsed && parsed.filtros && Array.isArray(parsed.filtros) && parsed.filtros.length > 0) {
                        datos = parsed.filtros;
                    }
                } catch (e) {
                    console.error('Error parseando especificaciones internas:', e);
                }
            }
            
            if (datos && datos.length > 0) {
                // Cargar datos existentes
                datos.forEach((filtro, index) => {
                    // Obtener conteos para las sublíneas de este filtro
                    const subprincipalesConConteos = (filtro.subprincipales || []).map(sub => {
                        const productosCount = obtenerContadorProductos(filtro.id, sub.id);
                        return {
                            ...sub,
                            productosCount: productosCount
                        };
                    });
                    
                    crearLineaPrincipal(filtro.texto || '', filtro.importante || false, subprincipalesConConteos, filtro.id || null, filtro.slug || null);
                });
            } else {
                // Crear 3 líneas por defecto
                for (let i = 0; i < 3; i++) {
                    crearLineaPrincipal('', false, []);
                }
            }
            
            datosCargados = true;
            // No actualizar JSON al inicializar si hay datos existentes
            if (!datos || datos.length === 0) {
                actualizarJSON();
            }
        }

        // Crear una línea principal
        function crearLineaPrincipal(texto = '', importante = false, subprincipales = [], idUnico = null, slugUnico = null) {
            const idPrincipal = `principal-${contadorPrincipal++}`;
            const idUnicoLinea = idUnico || generarIdUnico();
            const slugLinea = slugUnico || (texto ? generarSlug(texto) : '');
            const divPrincipal = document.createElement('div');
            divPrincipal.className = 'linea-principal border border-gray-300 dark:border-gray-600 rounded-lg p-4 bg-gray-50 dark:bg-gray-800';
            divPrincipal.dataset.id = idPrincipal;
            divPrincipal.dataset.idUnico = idUnicoLinea;
            divPrincipal.dataset.slug = slugLinea;
            divPrincipal.draggable = false;
            
            divPrincipal.innerHTML = `
                <div class="flex items-center gap-3 mb-3">
                    <div class="drag-handle-principal cursor-move text-gray-400 hover:text-gray-600 dark:hover:text-gray-300" title="Arrastrar para reordenar" draggable="true">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"></path>
                        </svg>
                    </div>
                    <input type="text" 
                           class="linea-principal-texto flex-1 px-3 py-2 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-white border border-gray-300 dark:border-gray-600" 
                           placeholder="Texto principal"
                           value="${texto}">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" class="linea-principal-importante" ${importante ? 'checked' : ''}>
                        <span class="text-sm text-gray-700 dark:text-gray-200">Importante</span>
                    </label>
                    <button type="button" class="btn-eliminar-principal bg-red-500 hover:bg-red-600 text-white px-3 py-2 rounded text-sm" title="Eliminar línea principal">
                        -
                    </button>
                    <button type="button" class="btn-añadir-principal bg-green-500 hover:bg-green-600 text-white px-3 py-2 rounded text-sm" title="Añadir línea principal debajo">
                        +
                    </button>
                </div>
                <div class="subprincipales-container ml-8 space-y-2"></div>
            `;
            
            container.appendChild(divPrincipal);
            
            // Configurar eventos de la línea principal
            const inputTexto = divPrincipal.querySelector('.linea-principal-texto');
            const checkboxImportante = divPrincipal.querySelector('.linea-principal-importante');
            const btnEliminar = divPrincipal.querySelector('.btn-eliminar-principal');
            const btnAñadir = divPrincipal.querySelector('.btn-añadir-principal');
            const containerSubprincipales = divPrincipal.querySelector('.subprincipales-container');
            
            // Prevenir drag en los campos de texto
            inputTexto.addEventListener('dragstart', (e) => {
                e.preventDefault();
                e.stopPropagation();
            });
            
            inputTexto.addEventListener('input', function() {
                // Actualizar slug cuando cambia el texto (siempre regenerar para asegurar que esté correcto)
                const textoActual = this.value.trim();
                if (textoActual) {
                    divPrincipal.dataset.slug = generarSlug(textoActual);
                }
                actualizarJSON();
            });
            checkboxImportante.addEventListener('change', actualizarJSON);
            
            btnEliminar.addEventListener('click', () => {
                divPrincipal.remove();
                actualizarJSON();
            });
            
            btnAñadir.addEventListener('click', () => {
                const nuevaLinea = crearLineaPrincipal('', false, []);
                divPrincipal.insertAdjacentElement('afterend', nuevaLinea);
                actualizarJSON();
            });
            
            // Configurar drag and drop desde el icono
            const dragHandle = divPrincipal.querySelector('.drag-handle-principal');
            configurarDragAndDropDesdeIcono(dragHandle, divPrincipal, 'principal');
            
            // Cargar subprincipales si existen
            if (subprincipales && subprincipales.length > 0) {
                subprincipales.forEach(sub => {
                    const productosCount = sub.productosCount !== undefined ? sub.productosCount : obtenerContadorProductos(idUnicoLinea, sub.id);
                    crearLineaIntermedia(containerSubprincipales, sub.texto || '', sub.id || null, sub.slug || null, productosCount);
                });
            } else {
                // Siempre crear al menos una línea intermedia por defecto
                crearLineaIntermedia(containerSubprincipales);
            }
            
            // Configurar el contenedor para permitir soltar al final (solo para líneas intermedias del mismo contenedor)
            if (!containerSubprincipales.dataset.dragConfigured) {
                containerSubprincipales.dataset.dragConfigured = 'true';
                
                containerSubprincipales.addEventListener('dragover', (e) => {
                    // Solo permitir si es una línea intermedia del mismo contenedor
                    if (elementoArrastrado && 
                        elementoArrastrado.classList.contains('linea-intermedia') &&
                        elementoArrastrado.parentNode === containerSubprincipales) {
                        
                        // Si el evento viene de un elemento hijo, no manejarlo aquí
                        if (e.target.classList.contains('linea-intermedia')) {
                            return;
                        }
                        
                        e.preventDefault();
                        e.dataTransfer.dropEffect = 'move';
                        
                        // Si estamos cerca del final del contenedor, mover al final
                        const rect = containerSubprincipales.getBoundingClientRect();
                        if (e.clientY > rect.top + rect.height * 0.8) {
                            const siblings = Array.from(containerSubprincipales.children).filter(child => 
                                child.classList.contains('linea-intermedia')
                            );
                            
                            if (siblings.length > 0 && siblings[siblings.length - 1] !== elementoArrastrado) {
                                containerSubprincipales.appendChild(elementoArrastrado);
                            }
                        }
                    }
                });
                
                containerSubprincipales.addEventListener('drop', (e) => {
                    e.preventDefault();
                    if (elementoArrastrado && elementoArrastrado.classList.contains('linea-intermedia')) {
                        actualizarJSON();
                    }
                });
            }
            
            return divPrincipal;
        }

        // Crear una línea intermedia
        function crearLineaIntermedia(containerPadre, texto = '', idUnico = null, slugUnico = null, productosCount = null) {
            const idIntermedia = `intermedia-${contadorIntermedia++}`;
            const idUnicoLinea = idUnico || generarIdUnico();
            
            // Generar slug desde el texto (si viene slugUnico, usarlo, pero al guardar se regenerará)
            const slugLinea = slugUnico || (texto ? generarSlug(texto) : '');
            
            const divIntermedia = document.createElement('div');
            divIntermedia.className = 'linea-intermedia flex items-center gap-3 border-l-4 border-blue-400 pl-3 py-2 bg-blue-50 dark:bg-blue-900/20 rounded';
            divIntermedia.dataset.id = idIntermedia;
            divIntermedia.dataset.idUnico = idUnicoLinea;
            divIntermedia.dataset.slug = slugLinea;
            divIntermedia.draggable = false;
            
            // Mostrar contador de productos si existe
            const contadorTexto = productosCount !== null && productosCount !== undefined ? `(${productosCount} productos)` : '';
            
            divIntermedia.innerHTML = `
                <div class="drag-handle-intermedia cursor-move text-gray-400 hover:text-gray-600 dark:hover:text-gray-300" title="Arrastrar para reordenar" draggable="true">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"></path>
                    </svg>
                </div>
                <input type="text" 
                       class="linea-intermedia-texto flex-1 px-3 py-2 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-white border border-gray-300 dark:border-gray-600" 
                       placeholder="Texto intermedio"
                       value="${texto}">
                ${contadorTexto ? `<span class="text-sm text-gray-500 dark:text-gray-400 productos-count-display">${contadorTexto}</span>` : ''}
                <button type="button" class="btn-eliminar-intermedia bg-red-500 hover:bg-red-600 text-white px-2 py-1 rounded text-xs" title="Eliminar línea intermedia">
                    -
                </button>
                <button type="button" class="btn-añadir-intermedia bg-green-500 hover:bg-green-600 text-white px-2 py-1 rounded text-xs" title="Añadir línea intermedia debajo">
                    +
                </button>
            `;
            
            containerPadre.appendChild(divIntermedia);
            
            // Configurar eventos de la línea intermedia
            const inputTexto = divIntermedia.querySelector('.linea-intermedia-texto');
            const btnEliminar = divIntermedia.querySelector('.btn-eliminar-intermedia');
            const btnAñadir = divIntermedia.querySelector('.btn-añadir-intermedia');
            
            // Prevenir drag en los campos de texto
            inputTexto.addEventListener('dragstart', (e) => {
                e.preventDefault();
                e.stopPropagation();
            });
            
            inputTexto.addEventListener('input', function() {
                const textoActual = this.value.trim();
                
                // Actualizar slug en tiempo real cuando se escribe
                if (textoActual) {
                    const nuevoSlug = generarSlug(textoActual);
                    divIntermedia.dataset.slug = nuevoSlug;
                }
                
                actualizarJSON();
            });
            
            btnEliminar.addEventListener('click', () => {
                divIntermedia.remove();
                actualizarJSON();
            });
            
            btnAñadir.addEventListener('click', () => {
                const nuevaLinea = crearLineaIntermedia(containerPadre, '');
                divIntermedia.insertAdjacentElement('afterend', nuevaLinea);
                actualizarJSON();
            });
            
            // Configurar drag and drop desde el icono
            const dragHandle = divIntermedia.querySelector('.drag-handle-intermedia');
            configurarDragAndDropDesdeIcono(dragHandle, divIntermedia, 'intermedia');
            
            return divIntermedia;
        }

        // Variables globales para drag and drop
        let elementoArrastrado = null;
        let tipoArrastrado = null;

        // Configurar drag and drop desde el icono (el icono arrastra el elemento padre)
        function configurarDragAndDropDesdeIcono(icono, elementoPadre, tipo) {
            // Prevenir que el arrastre del icono se propague
            icono.addEventListener('dragstart', (e) => {
                elementoArrastrado = elementoPadre;
                tipoArrastrado = tipo;
                elementoPadre.style.opacity = '0.5';
                e.dataTransfer.effectAllowed = 'move';
                
                // Para líneas intermedias, detener la propagación para evitar que el contenedor principal lo maneje
                if (tipo === 'intermedia') {
                    e.stopPropagation();
                    elementoPadre.dataset.parentContainer = elementoPadre.parentNode.className;
                }
            });
            
            icono.addEventListener('dragend', (e) => {
                elementoPadre.style.opacity = '1';
                if (elementoArrastrado && tipoArrastrado === tipo) {
                    actualizarJSON();
                }
                
                // Para líneas intermedias, detener la propagación
                if (tipo === 'intermedia') {
                    e.stopPropagation();
                }
                
                elementoArrastrado = null;
                tipoArrastrado = null;
            });
            
            // Prevenir que el icono sea un drop target
            icono.addEventListener('dragover', (e) => {
                e.preventDefault();
                e.stopPropagation();
            });
            
            icono.addEventListener('drop', (e) => {
                e.preventDefault();
                e.stopPropagation();
            });
            
            // Configurar drag and drop en el elemento padre para recibir el drop
            configurarDragAndDrop(elementoPadre, tipo);
        }

        // Configurar drag and drop para reordenar (solo para recibir drops)
        function configurarDragAndDrop(elemento, tipo) {
            elemento.addEventListener('dragover', (e) => {
                if (!elementoArrastrado || elementoArrastrado === elemento) return;
                if (tipoArrastrado !== tipo) return;
                
                const parent = elemento.parentNode;
                if (!parent) return;
                
                // Para líneas intermedias, asegurar que están en el mismo contenedor
                if (tipo === 'intermedia') {
                    const draggedParent = elementoArrastrado.parentNode;
                    const targetParent = elemento.parentNode;
                    
                    // Solo permitir reordenar si están en el mismo contenedor
                    if (draggedParent !== targetParent) {
                        e.dataTransfer.dropEffect = 'none';
                        return;
                    }
                }
                
                e.preventDefault();
                e.stopPropagation(); // IMPORTANTE: Detener propagación para evitar que el contenedor interfiera
                e.dataTransfer.dropEffect = 'move';
                
                const rect = elemento.getBoundingClientRect();
                const mouseY = e.clientY;
                const elementMiddle = rect.top + rect.height / 2;
                const insertAfter = mouseY > elementMiddle;
                
                // Obtener todos los siblings del mismo tipo en el mismo contenedor
                const siblings = Array.from(parent.children).filter(child => {
                    if (tipo === 'principal') {
                        return child.classList.contains('linea-principal');
                    } else {
                        return child.classList.contains('linea-intermedia');
                    }
                });
                
                const currentIndex = siblings.indexOf(elemento);
                const draggedIndex = siblings.indexOf(elementoArrastrado);
                
                if (draggedIndex === -1) return;
                
                // Evitar movimientos innecesarios
                if (insertAfter && draggedIndex === currentIndex + 1) return;
                if (!insertAfter && draggedIndex === currentIndex - 1) return;
                
                // Realizar el movimiento inmediatamente
                if (insertAfter) {
                    const nextSibling = elemento.nextSibling;
                    if (nextSibling && nextSibling !== elementoArrastrado) {
                        parent.insertBefore(elementoArrastrado, nextSibling);
                    } else if (!nextSibling) {
                        parent.appendChild(elementoArrastrado);
                    }
                } else {
                    if (elemento !== elementoArrastrado) {
                        parent.insertBefore(elementoArrastrado, elemento);
                    }
                }
            });
            
            elemento.addEventListener('drop', (e) => {
                e.preventDefault();
                e.stopPropagation();
            });
        }
        
        // Configurar drag and drop en los contenedores para permitir soltar al final
        function configurarDragAndDropContenedor(contenedor, tipo) {
            if (contenedor.dataset.dragConfigured) return;
            contenedor.dataset.dragConfigured = 'true';
            
            contenedor.addEventListener('dragover', (e) => {
                if (!elementoArrastrado) return;
                
                // Verificar que el tipo coincida
                const isPrincipal = tipo === 'principal';
                const isPrincipalDragged = elementoArrastrado.classList.contains('linea-principal');
                const isIntermediaDragged = elementoArrastrado.classList.contains('linea-intermedia');
                
                // IMPORTANTE: Si el contenedor es para líneas principales y se está arrastrando una sublínea, IGNORAR completamente
                if (isPrincipal && isIntermediaDragged) {
                    return; // No permitir que el contenedor principal maneje sublíneas
                }
                
                if (isPrincipal && !isPrincipalDragged) return;
                if (!isPrincipal && !isIntermediaDragged) return;
                
                // Si el evento viene directamente de un elemento del tipo correcto, no manejarlo aquí
                // (ya lo maneja el elemento individual)
                const target = e.target;
                if (target.classList.contains('linea-principal') || target.classList.contains('linea-intermedia')) {
                    return; // Dejar que el elemento individual lo maneje
                }
                
                // Si el evento viene de un hijo de un elemento (input, button, etc.), verificar si es del tipo correcto
                const parentElement = target.closest('.linea-principal, .linea-intermedia');
                if (parentElement) {
                    // Si el contenedor es para líneas principales y el elemento padre es una sublínea, IGNORAR
                    if (isPrincipal && parentElement.classList.contains('linea-intermedia')) {
                        return; // No permitir que el contenedor principal maneje eventos de sublíneas
                    }
                    
                    if ((isPrincipal && parentElement.classList.contains('linea-principal')) || 
                        (!isPrincipal && parentElement.classList.contains('linea-intermedia'))) {
                        return; // Dejar que el elemento individual lo maneje
                    }
                }
                
                e.preventDefault();
                e.dataTransfer.dropEffect = 'move';
                
                // Obtener todos los elementos del mismo tipo
                const siblings = Array.from(contenedor.children).filter(child => {
                    if (isPrincipal) {
                        return child.classList.contains('linea-principal');
                    } else {
                        return child.classList.contains('linea-intermedia');
                    }
                });
                
                // Si no hay elementos del tipo correcto, añadir al final
                if (siblings.length === 0) {
                    contenedor.appendChild(elementoArrastrado);
                    return;
                }
                
                // Si el elemento arrastrado no está en la lista de siblings, añadirlo al final
                if (!siblings.includes(elementoArrastrado)) {
                    const rect = contenedor.getBoundingClientRect();
                    const mouseY = e.clientY;
                    
                    // Si el mouse está en la parte inferior del contenedor (último 30%), añadir al final
                    if (mouseY > rect.top + rect.height * 0.7) {
                        contenedor.appendChild(elementoArrastrado);
                    }
                    return;
                }
                
                // Buscar el elemento más cercano al mouse (excluyendo el arrastrado)
                let closestElement = null;
                let closestDistance = Infinity;
                
                siblings.forEach(sibling => {
                    if (sibling === elementoArrastrado) return;
                    const siblingRect = sibling.getBoundingClientRect();
                    const siblingMiddle = siblingRect.top + siblingRect.height / 2;
                    const distance = Math.abs(e.clientY - siblingMiddle);
                    if (distance < closestDistance) {
                        closestDistance = distance;
                        closestElement = sibling;
                    }
                });
                
                // Si encontramos un elemento cercano, insertar antes o después según la posición del mouse
                if (closestElement) {
                    const closestRect = closestElement.getBoundingClientRect();
                    const insertAfter = e.clientY > closestRect.top + closestRect.height / 2;
                    
                    if (insertAfter) {
                        // Insertar después del elemento más cercano
                        const nextSibling = closestElement.nextSibling;
                        if (nextSibling && nextSibling !== elementoArrastrado) {
                            contenedor.insertBefore(elementoArrastrado, nextSibling);
                        } else {
                            // Es el último elemento, añadir al final
                            contenedor.appendChild(elementoArrastrado);
                        }
                    } else {
                        // Insertar antes del elemento más cercano
                        contenedor.insertBefore(elementoArrastrado, closestElement);
                    }
                } else {
                    // Si no hay elementos cercanos y estamos en la parte inferior, añadir al final
                    const rect = contenedor.getBoundingClientRect();
                    if (e.clientY > rect.top + rect.height * 0.7) {
                        contenedor.appendChild(elementoArrastrado);
                    }
                }
            });
            
            contenedor.addEventListener('drop', (e) => {
                // Si el contenedor es para líneas principales y se está arrastrando una sublínea, IGNORAR
                if (tipo === 'principal' && elementoArrastrado && elementoArrastrado.classList.contains('linea-intermedia')) {
                    return; // No permitir que el contenedor principal maneje sublíneas
                }
                e.preventDefault();
            });
        }

        // Función para generar slug desde texto
        function generarSlug(texto) {
            return texto
                .toString()
                .toLowerCase()
                .trim()
                .replace(/\+/g, ' plus ')
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '') // Eliminar acentos
                .replace(/[^a-z0-9]+/g, '-')     // Reemplazar espacios y caracteres especiales
                .replace(/^-+|-+$/g, '');        // Eliminar guiones al inicio/final
        }

        // Actualizar el JSON en el campo oculto
        function actualizarJSON() {
            if (!datosCargados) return;
            
            const filtros = [];
            const lineasPrincipales = container.querySelectorAll('.linea-principal');
            
            lineasPrincipales.forEach(lineaPrincipal => {
                const texto = lineaPrincipal.querySelector('.linea-principal-texto').value.trim();
                const importante = lineaPrincipal.querySelector('.linea-principal-importante').checked;
                const idUnicoPrincipal = lineaPrincipal.dataset.idUnico;
                
                // SIEMPRE regenerar el slug desde el texto para asegurar que esté correcto
                const slugPrincipal = texto ? generarSlug(texto) : '';
                lineaPrincipal.dataset.slug = slugPrincipal;
                
                const containerSubprincipales = lineaPrincipal.querySelector('.subprincipales-container');
                const lineasIntermedias = containerSubprincipales ? containerSubprincipales.querySelectorAll('.linea-intermedia') : [];
                
                const subprincipales = [];
                lineasIntermedias.forEach(lineaIntermedia => {
                    const textoIntermedia = lineaIntermedia.querySelector('.linea-intermedia-texto').value.trim();
                    const idUnicoIntermedia = lineaIntermedia.dataset.idUnico;
                    
                    // SIEMPRE regenerar el slug desde el texto para asegurar que esté correcto
                    const slugIntermedia = textoIntermedia ? generarSlug(textoIntermedia) : '';
                    lineaIntermedia.dataset.slug = slugIntermedia;
                    
                    // Solo incluir sublíneas que tengan texto
                    if (textoIntermedia && idUnicoIntermedia) {
                        subprincipales.push({ 
                            texto: textoIntermedia,
                            id: idUnicoIntermedia,
                            slug: slugIntermedia
                        });
                    }
                });
                
                // Solo añadir si hay texto principal (las sublíneas vacías no cuentan)
                if (texto) {
                    filtros.push({
                        id: idUnicoPrincipal,
                        texto: texto,
                        slug: slugPrincipal,
                        importante: importante,
                        subprincipales: subprincipales
                    });
                }
            });
            
            const jsonData = {
                filtros: filtros
            };
            
            // Guardar JSON sin espacios y con caracteres Unicode sin escapar
            inputHidden.value = JSON.stringify(jsonData, null, 0);
        }

        // Configurar drag and drop en el contenedor principal
        configurarDragAndDropContenedor(container, 'principal');
        
        // Inicializar al cargar
        inicializar();
        
        // Actualizar JSON antes de enviar el formulario
        const form = document.querySelector('form');
        if (form) {
            form.addEventListener('submit', function(e) {
                actualizarJSON();
            });
        }
    });
    </script>

    @if($categoria)
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const KP_CSRF_BUSCAR_URLS = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';
        const URL_CRON_BUSCAR_URLS = @json(route('admin.productos.buscar-amazon.cron.panel'));
        const CATEGORIA_ID_BUSCAR_URLS = {{ (int) $categoria->id }};

        const btnEjecutar = document.getElementById('btn-ejecutar-cron-buscar-urls-cat');
        const btnPausar = document.getElementById('btn-pausar-cron-buscar-urls-cat');
        const btnDetener = document.getElementById('btn-detener-cron-buscar-urls-cat');
        const btnLog = document.getElementById('btn-log-buscar-urls-cat');
        const inputDesdeProducto = document.getElementById('input-desde-producto-buscar-urls-cat');
        const elTotalProductos = document.getElementById('total-productos-buscar-urls-cat');
        const elHintDesdeProducto = document.getElementById('hint-desde-producto-buscar-urls-cat');
        const panelProgreso = document.getElementById('progreso-buscar-urls-cat');
        const textoProgreso = document.getElementById('texto-progreso-buscar-urls-cat');
        const elUrlsTodas = document.getElementById('urls-todas-buscar-cat');
        const elUrlsExistentes = document.getElementById('urls-existentes-buscar-cat');
        const elUrlsNeo = document.getElementById('urls-neo-buscar-cat');
        const elProductoActual = document.getElementById('producto-actual-buscar-urls-cat');
        const modalLog = document.getElementById('modal-log-buscar-urls-cat');
        const modalLogContenido = document.getElementById('modal-log-buscar-urls-cat-contenido');

        if (!btnEjecutar) return;

        let sesionBuscarUrls = null;

        function obtenerTotalProductosElegiblesBuscarUrls() {
            return parseInt(elTotalProductos?.textContent || '0', 10) || 0;
        }

        function normalizarIndiceInicioBuscarUrls(valor) {
            const total = obtenerTotalProductosElegiblesBuscarUrls();
            if (total <= 0) {
                return 1;
            }

            let indice = parseInt(String(valor ?? '1'), 10);
            if (!Number.isFinite(indice) || indice < 1) {
                indice = 1;
            }
            if (indice > total) {
                indice = total;
            }

            if (inputDesdeProducto) {
                inputDesdeProducto.value = String(indice);
                inputDesdeProducto.max = String(total);
            }

            return indice;
        }

        function actualizarHintDesdeProducto(productoIds, indiceLista) {
            if (!elHintDesdeProducto) {
                return;
            }

            const total = Array.isArray(productoIds) ? productoIds.length : 0;
            if (total <= 0 || indiceLista < 1) {
                elHintDesdeProducto.textContent = '';
                return;
            }

            const productoId = productoIds[indiceLista - 1];
            if (productoId) {
                elHintDesdeProducto.textContent = '(producto ID #' + productoId + ')';
            } else {
                elHintDesdeProducto.textContent = '';
            }
        }

        if (inputDesdeProducto) {
            inputDesdeProducto.addEventListener('change', function() {
                normalizarIndiceInicioBuscarUrls(inputDesdeProducto.value);
            });
            inputDesdeProducto.addEventListener('blur', function() {
                normalizarIndiceInicioBuscarUrls(inputDesdeProducto.value);
            });
        }

        function escHtmlBuscarUrls(texto) {
            return String(texto ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;');
        }

        function clasificarUrlsProducto(detalleUrls) {
            const todas = [];
            const existentes = [];
            const insertadasNeo = [];

            (detalleUrls || []).forEach(function(det) {
                if (!det || !det.url) return;
                const url = String(det.url).trim();
                if (!url) return;
                todas.push(url);
                if (det.accion === 'insertada') {
                    insertadasNeo.push(url);
                } else {
                    existentes.push(url);
                }
            });

            return { todas, existentes, insertadasNeo };
        }

        function actualizarPanelProgreso(data) {
            if (!panelProgreso) return;
            panelProgreso.classList.remove('hidden');

            const indice = data.indice || 0;
            const total = data.total || 0;
            const resumen = data.resumen_urls || {};
            const productoActual = data.producto_actual || {};
            const mensajeEstado = data.mensaje_estado || '';

            if (textoProgreso) {
                if (mensajeEstado) {
                    textoProgreso.textContent = mensajeEstado;
                } else if (data.terminado) {
                    textoProgreso.textContent = 'Finalizado: ' + indice + '/' + total + ' productos procesados';
                } else {
                    textoProgreso.textContent = indice + '/' + total + ' buscando productos…';
                }
            }
            if (elUrlsTodas) elUrlsTodas.textContent = String(resumen.todas ?? 0);
            if (elUrlsExistentes) elUrlsExistentes.textContent = String(resumen.existentes ?? 0);
            if (elUrlsNeo) elUrlsNeo.textContent = String(resumen.insertadas_neo ?? 0);
            if (elProductoActual) {
                if (productoActual.nombre) {
                    elProductoActual.textContent = 'Último producto: #' + (productoActual.producto_id || '—') + ' — ' + productoActual.nombre;
                } else {
                    elProductoActual.textContent = '';
                }
            }
        }

        function renderModalLogBuscarUrls(resultados) {
            if (!modalLogContenido) return;

            const lista = Array.isArray(resultados) ? resultados : [];
            if (!lista.length) {
                modalLogContenido.innerHTML = '<p class="text-gray-500 dark:text-gray-400">Aún no hay resultados.</p>';
                return;
            }

            if (!sesionBuscarUrls) {
                sesionBuscarUrls = { clasificaciones: [] };
            }
            sesionBuscarUrls.clasificaciones = lista.map(function(r) {
                return clasificarUrlsProducto(r.detalle_urls);
            });

            const bloques = lista.map(function(r, idx) {
                const clasif = sesionBuscarUrls.clasificaciones[idx];
                const textareaId = 'textarea-urls-cat-' + idx;
                const urlsTodasTexto = clasif.todas.join('\n');

                const resumenProducto = [
                    'Amazon: ' + (r.urls_amazon || 0),
                    'AliExpress: ' + (r.urls_aliexpress || 0),
                    'Insertadas: ' + (r.urls_insertadas || 0),
                    'Omitidas: ' + (r.urls_omitidas || 0),
                ].join(' · ');

                let erroresHtml = '';
                if (r.error_amazon) {
                    erroresHtml += '<p class="text-red-600 dark:text-red-400 text-xs mt-1">Error Amazon: ' + escHtmlBuscarUrls(r.error_amazon) + '</p>';
                }
                if (r.error_aliexpress) {
                    erroresHtml += '<p class="text-red-600 dark:text-red-400 text-xs mt-1">Error AliExpress: ' + escHtmlBuscarUrls(r.error_aliexpress) + '</p>';
                }

                return `
                    <details class="rounded-lg border border-gray-200 dark:border-gray-600 p-3 bg-gray-50 dark:bg-gray-900/30" open>
                        <summary class="cursor-pointer font-medium text-gray-900 dark:text-white">
                            #${idx + 1} — Producto #${escHtmlBuscarUrls(r.producto_id)} — ${escHtmlBuscarUrls(r.nombre || 'Sin nombre')}
                        </summary>
                        <p class="text-xs text-gray-600 dark:text-gray-400 mt-2">${escHtmlBuscarUrls(resumenProducto)}</p>
                        ${erroresHtml}
                        <div class="mt-3 grid grid-cols-1 sm:grid-cols-3 gap-2" data-filtro-grupo="${idx}">
                            <button type="button" data-filtro="todas" data-idx="${idx}"
                                class="filtro-urls-cat-btn rounded-lg border-2 border-blue-500 bg-blue-50 dark:bg-blue-900/30 p-3 text-left hover:opacity-90 transition">
                                <span class="block text-xs text-gray-500 dark:text-gray-400">Todas las URL</span>
                                <span class="text-xl font-bold text-blue-700 dark:text-blue-300">${clasif.todas.length}</span>
                            </button>
                            <button type="button" data-filtro="existentes" data-idx="${idx}"
                                class="filtro-urls-cat-btn rounded-lg border-2 border-transparent bg-amber-50 dark:bg-amber-900/20 p-3 text-left hover:opacity-90 transition">
                                <span class="block text-xs text-gray-500 dark:text-gray-400">Ya existían</span>
                                <span class="text-xl font-bold text-amber-700 dark:text-amber-300">${clasif.existentes.length}</span>
                            </button>
                            <button type="button" data-filtro="insertadas_neo" data-idx="${idx}"
                                class="filtro-urls-cat-btn rounded-lg border-2 border-transparent bg-emerald-50 dark:bg-emerald-900/20 p-3 text-left hover:opacity-90 transition">
                                <span class="block text-xs text-gray-500 dark:text-gray-400">Añadidas a Neo</span>
                                <span class="text-xl font-bold text-emerald-700 dark:text-emerald-300">${clasif.insertadasNeo.length}</span>
                            </button>
                        </div>
                        <div class="mt-3 flex flex-wrap items-center gap-2">
                            <button type="button" class="btn-copiar-urls-cat px-3 py-1.5 text-xs rounded bg-slate-700 hover:bg-slate-800 text-white" data-textarea="${textareaId}">
                                Copiar todas
                            </button>
                        </div>
                        <textarea id="${textareaId}" readonly rows="6"
                            class="mt-2 w-full text-xs font-mono rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-800 dark:text-gray-200 p-2 resize-y">${escHtmlBuscarUrls(urlsTodasTexto)}</textarea>
                    </details>
                `;
            }).join('');

            modalLogContenido.innerHTML = bloques;

            modalLogContenido.querySelectorAll('.filtro-urls-cat-btn').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    const idx = parseInt(btn.getAttribute('data-idx') || '0', 10);
                    const filtro = btn.getAttribute('data-filtro') || 'todas';
                    const textarea = document.getElementById('textarea-urls-cat-' + idx);
                    const grupo = btn.closest('[data-filtro-grupo]');
                    const clasif = sesionBuscarUrls?.clasificaciones?.[idx];
                    if (!textarea || !grupo || !clasif) return;

                    grupo.querySelectorAll('.filtro-urls-cat-btn').forEach(function(b) {
                        b.classList.remove('border-blue-500', 'border-amber-500', 'border-emerald-500');
                        b.classList.add('border-transparent');
                    });

                    const colorMap = {
                        todas: 'border-blue-500',
                        existentes: 'border-amber-500',
                        insertadas_neo: 'border-emerald-500',
                    };
                    btn.classList.remove('border-transparent');
                    btn.classList.add(colorMap[filtro] || 'border-blue-500');

                    const mapaUrls = {
                        todas: clasif.todas,
                        existentes: clasif.existentes,
                        insertadas_neo: clasif.insertadasNeo,
                    };
                    textarea.value = (mapaUrls[filtro] || []).join('\n');
                });
            });

            modalLogContenido.querySelectorAll('.btn-copiar-urls-cat').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    const textarea = document.getElementById(btn.getAttribute('data-textarea'));
                    if (!textarea) return;
                    const texto = textarea.value;
                    if (navigator.clipboard && window.isSecureContext) {
                        navigator.clipboard.writeText(texto).then(function() {
                            alert('URLs copiadas al portapapeles.');
                        }).catch(function() {
                            textarea.select();
                            document.execCommand('copy');
                            alert('URLs copiadas al portapapeles.');
                        });
                    } else {
                        textarea.select();
                        document.execCommand('copy');
                        alert('URLs copiadas al portapapeles.');
                    }
                });
            });
        }

        function abrirModalLog() {
            if (!modalLog || !sesionBuscarUrls) return;
            renderModalLogBuscarUrls(sesionBuscarUrls.resultados || []);
            modalLog.classList.remove('hidden');
        }

        function cerrarModalLog() {
            modalLog?.classList.add('hidden');
        }

        document.getElementById('btn-cerrar-modal-log-buscar-urls-cat')?.addEventListener('click', cerrarModalLog);
        document.getElementById('btn-cerrar-modal-log-buscar-urls-cat-footer')?.addEventListener('click', cerrarModalLog);
        modalLog?.addEventListener('click', function(e) {
            if (e.target === modalLog) cerrarModalLog();
        });
        btnLog?.addEventListener('click', abrirModalLog);

        async function esperarSiPausadoBuscarUrls(session) {
            while (session.paused && !session.abort) {
                await new Promise(function(resolve) {
                    session.pauseResolve = resolve;
                });
            }
        }

        function reanudarDesdePausaBuscarUrls(session) {
            session.paused = false;
            if (typeof session.pauseResolve === 'function') {
                session.pauseResolve();
                session.pauseResolve = null;
            }
        }

        function setControlesBuscarUrls(modo) {
            const enCurso = modo === 'running' || modo === 'paused';
            if (btnEjecutar) btnEjecutar.disabled = enCurso || obtenerTotalProductosElegiblesBuscarUrls() <= 0;
            if (inputDesdeProducto) inputDesdeProducto.disabled = enCurso || obtenerTotalProductosElegiblesBuscarUrls() <= 0;
            if (btnPausar) {
                btnPausar.disabled = !enCurso;
                btnPausar.textContent = modo === 'paused' ? '▶ Reanudar' : '⏸ Pausar';
            }
            if (btnDetener) btnDetener.disabled = !enCurso;
            if (btnLog) {
                const tieneResultados = sesionBuscarUrls && (sesionBuscarUrls.resultados || []).length > 0;
                btnLog.disabled = !tieneResultados;
            }
        }

        async function postJsonBuscarUrls(url, body) {
            const contexto = {
                accion: body && body.accion ? body.accion : null,
                producto_id: body && body.producto_id ? body.producto_id : null,
                indice: body && body.indice ? body.indice : null,
                ejecucion_id: body && body.ejecucion_id ? body.ejecucion_id : null,
                categoria_id: body && body.categoria_id ? body.categoria_id : null,
            };

            let resp;
            let rawText = '';

            try {
                resp = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': KP_CSRF_BUSCAR_URLS,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify(body),
                });
                rawText = await resp.text();
            } catch (networkError) {
                console.error('[Buscar URLs categoría] Error de red', {
                    contexto: contexto,
                    url: url,
                    error: networkError,
                });
                throw networkError;
            }

            let data = {};
            if (rawText) {
                try {
                    data = JSON.parse(rawText);
                } catch (parseError) {
                    console.error('[Buscar URLs categoría] Respuesta no JSON (posible error PHP)', {
                        contexto: contexto,
                        status: resp.status,
                        statusText: resp.statusText,
                        contentType: resp.headers.get('content-type'),
                        rawText: rawText.length > 4000 ? rawText.slice(0, 4000) + '…' : rawText,
                    });
                    throw new Error(
                        'Error HTTP ' + resp.status + ' — respuesta no JSON. Revisa la consola (F12) para el detalle.'
                    );
                }
            }

            if (!resp.ok || data.ok === false) {
                if (!rawText && resp.status >= 500) {
                    console.error('[Buscar URLs categoría] HTTP 500 sin cuerpo — PHP murió antes de responder (memoria, timeout o error fatal). Revisa storage/logs/laravel.log buscando producto_id o panelCronProcesarProducto.', {
                        contexto: contexto,
                        status: resp.status,
                        statusText: resp.statusText,
                        contentType: resp.headers.get('content-type'),
                    });
                }
                console.error(
                    '[Buscar URLs categoría] Error API',
                    data.error || data.message || ('HTTP ' + resp.status),
                    data.exception ? '(' + data.exception + ')' : '',
                    data.fase ? '[fase: ' + data.fase + ']' : '',
                    contexto,
                    data
                );
                const partes = [
                    data.error || data.message || ('Error HTTP ' + resp.status + (rawText ? '' : ' (sin cuerpo — revisa laravel.log)')),
                ];
                if (data.exception) {
                    partes.push('(' + data.exception + ')');
                }
                if (data.fase) {
                    partes.push('[fase: ' + data.fase + ']');
                }
                if (contexto.producto_id) {
                    partes.push('— producto #' + contexto.producto_id);
                }
                if (contexto.indice) {
                    partes.push('(' + contexto.indice + '/' + (sesionBuscarUrls ? sesionBuscarUrls.total : '?') + ')');
                }
                throw new Error(partes.join(' '));
            }

            return data;
        }

        async function ejecutarBusquedaUrlsCategoria() {
            if (sesionBuscarUrls && (sesionBuscarUrls.running || sesionBuscarUrls.paused)) return;

            const indiceInicio = normalizarIndiceInicioBuscarUrls(inputDesdeProducto?.value || 1);
            const indiceInicioCero = indiceInicio - 1;

            sesionBuscarUrls = {
                running: true,
                paused: false,
                abort: false,
                pauseResolve: null,
                ejecucion_id: null,
                producto_ids: [],
                resultados: [],
                total: 0,
                indiceInicio: indiceInicioCero,
                indiceActual: indiceInicioCero,
            };

            setControlesBuscarUrls('running');
            if (btnLog) btnLog.disabled = true;

            let finalizado = false;
            let detenido = false;

            try {
                const inicio = await postJsonBuscarUrls(URL_CRON_BUSCAR_URLS, {
                    accion: 'iniciar',
                    categoria_id: CATEGORIA_ID_BUSCAR_URLS,
                });
                if (inicio.sin_productos) {
                    alert('No hay productos elegibles en esta categoría para buscar URLs.');
                    return;
                }

                sesionBuscarUrls.ejecucion_id = inicio.ejecucion_id;
                sesionBuscarUrls.producto_ids = inicio.producto_ids || [];
                sesionBuscarUrls.total = inicio.total || 0;

                if (elTotalProductos && sesionBuscarUrls.total > 0) {
                    elTotalProductos.textContent = String(sesionBuscarUrls.total);
                    if (inputDesdeProducto) {
                        inputDesdeProducto.max = String(sesionBuscarUrls.total);
                    }
                }

                const indiceInicioFinal = normalizarIndiceInicioBuscarUrls(indiceInicio);
                const indiceInicioCeroFinal = indiceInicioFinal - 1;

                if (indiceInicioCeroFinal >= sesionBuscarUrls.producto_ids.length) {
                    alert('La posición ' + indiceInicioFinal + ' es mayor que el total (' + sesionBuscarUrls.total + ').');
                    return;
                }

                sesionBuscarUrls.indiceInicio = indiceInicioCeroFinal;
                sesionBuscarUrls.indiceActual = indiceInicioCeroFinal;
                actualizarHintDesdeProducto(sesionBuscarUrls.producto_ids, indiceInicioFinal);

                const productoIdInicio = sesionBuscarUrls.producto_ids[indiceInicioCeroFinal];
                actualizarPanelProgreso({
                    indice: indiceInicioCeroFinal,
                    total: sesionBuscarUrls.total,
                    resumen_urls: { todas: 0, existentes: 0, insertadas_neo: 0 },
                    terminado: false,
                    mensaje_estado: indiceInicioFinal > 1
                        ? ('Empezando en posición ' + indiceInicioFinal + '/' + sesionBuscarUrls.total + ' (producto #' + productoIdInicio + ')…')
                        : ('Cargados ' + sesionBuscarUrls.total + ' productos. Procesando desde el primero…'),
                });

                for (let i = indiceInicioCeroFinal; i < sesionBuscarUrls.producto_ids.length; i++) {
                    await esperarSiPausadoBuscarUrls(sesionBuscarUrls);
                    if (sesionBuscarUrls.abort) {
                        detenido = true;
                        actualizarPanelProgreso({
                            indice: i,
                            total: sesionBuscarUrls.total,
                            resumen_urls: resumirUrlsDesdeResultados(sesionBuscarUrls.resultados),
                            mensaje_estado: 'Detenido por el usuario (' + i + '/' + sesionBuscarUrls.total + ').',
                        });
                        break;
                    }

                    const productoId = sesionBuscarUrls.producto_ids[i];
                    sesionBuscarUrls.indiceActual = i;

                    const data = await postJsonBuscarUrls(URL_CRON_BUSCAR_URLS, {
                        accion: 'procesar',
                        categoria_id: CATEGORIA_ID_BUSCAR_URLS,
                        ejecucion_id: sesionBuscarUrls.ejecucion_id,
                        producto_id: productoId,
                        indice: i + 1,
                    });

                    if (sesionBuscarUrls.abort) {
                        detenido = true;
                        actualizarPanelProgreso({
                            indice: i + 1,
                            total: sesionBuscarUrls.total,
                            resumen_urls: resumirUrlsDesdeResultados(sesionBuscarUrls.resultados),
                            mensaje_estado: 'Detenido por el usuario (' + (i + 1) + '/' + sesionBuscarUrls.total + ').',
                        });
                        break;
                    }

                    sesionBuscarUrls.resultados = acumularResultadoProductoBuscarUrls(
                        sesionBuscarUrls.resultados,
                        data.resultado_producto
                    );
                    actualizarPanelProgreso(data);
                    setControlesBuscarUrls(sesionBuscarUrls.paused ? 'paused' : 'running');

                    if (modalLog && !modalLog.classList.contains('hidden')) {
                        renderModalLogBuscarUrls(sesionBuscarUrls.resultados);
                    }
                }

                if (!detenido && !sesionBuscarUrls.abort) {
                    finalizado = true;
                    alert('Búsqueda finalizada.');
                }
            } catch (error) {
                console.error('[Buscar URLs categoría] Fallo en la ejecución', {
                    ejecucion_id: sesionBuscarUrls ? sesionBuscarUrls.ejecucion_id : null,
                    producto_id: sesionBuscarUrls && sesionBuscarUrls.producto_ids
                        ? sesionBuscarUrls.producto_ids[sesionBuscarUrls.indiceActual]
                        : null,
                    indice: sesionBuscarUrls ? (sesionBuscarUrls.indiceActual + 1) : null,
                    total: sesionBuscarUrls ? sesionBuscarUrls.total : null,
                    resultados_parciales: sesionBuscarUrls ? (sesionBuscarUrls.resultados || []).length : 0,
                    error: error,
                });
                alert('Error: ' + (error.message || 'No se pudo completar la búsqueda') + '\n\nAbre la consola del navegador (F12) para más detalle.');
            } finally {
                if (sesionBuscarUrls) {
                    sesionBuscarUrls.running = false;
                    sesionBuscarUrls.paused = false;
                    reanudarDesdePausaBuscarUrls(sesionBuscarUrls);
                }
                setControlesBuscarUrls('idle');
                if (sesionBuscarUrls && (sesionBuscarUrls.resultados || []).length > 0) {
                    if (btnLog) btnLog.disabled = false;
                }
            }
        }

        function acumularResultadoProductoBuscarUrls(resultadosActuales, resultadoProducto) {
            const lista = Array.isArray(resultadosActuales) ? resultadosActuales.slice() : [];
            if (!resultadoProducto || !resultadoProducto.producto_id) {
                return lista;
            }

            const idx = lista.findIndex(function(r) {
                return r && r.producto_id === resultadoProducto.producto_id;
            });

            if (idx >= 0) {
                lista[idx] = resultadoProducto;
            } else {
                lista.push(resultadoProducto);
            }

            return lista;
        }

        function resumirUrlsDesdeResultados(resultados) {
            let todas = 0;
            let existentes = 0;
            let insertadasNeo = 0;
            (resultados || []).forEach(function(r) {
                (r.detalle_urls || []).forEach(function(det) {
                    if (!det) return;
                    todas++;
                    if (det.accion === 'insertada') {
                        insertadasNeo++;
                    } else {
                        existentes++;
                    }
                });
            });
            return { todas: todas, existentes: existentes, insertadas_neo: insertadasNeo };
        }

        btnEjecutar.addEventListener('click', function() {
            if (sesionBuscarUrls && (sesionBuscarUrls.running || sesionBuscarUrls.paused)) return;
            if (sesionBuscarUrls && (sesionBuscarUrls.resultados || []).length > 0) {
                if (!confirm('¿Volver a ejecutar la búsqueda? Se creará una nueva ejecución.')) {
                    return;
                }
            }
            ejecutarBusquedaUrlsCategoria();
        });

        btnPausar?.addEventListener('click', function() {
            if (!sesionBuscarUrls || (!sesionBuscarUrls.running && !sesionBuscarUrls.paused)) return;

            if (sesionBuscarUrls.paused) {
                reanudarDesdePausaBuscarUrls(sesionBuscarUrls);
                setControlesBuscarUrls('running');
                actualizarPanelProgreso({
                    indice: sesionBuscarUrls.indiceActual,
                    total: sesionBuscarUrls.total,
                    resumen_urls: resumirUrlsDesdeResultados(sesionBuscarUrls.resultados),
                    mensaje_estado: 'Reanudando…',
                });
            } else {
                sesionBuscarUrls.paused = true;
                setControlesBuscarUrls('paused');
                actualizarPanelProgreso({
                    indice: sesionBuscarUrls.indiceActual,
                    total: sesionBuscarUrls.total,
                    resumen_urls: resumirUrlsDesdeResultados(sesionBuscarUrls.resultados),
                    mensaje_estado: 'Pausado (' + sesionBuscarUrls.indiceActual + '/' + sesionBuscarUrls.total + ').',
                });
            }
        });

        btnDetener?.addEventListener('click', async function() {
            if (!sesionBuscarUrls) return;
            sesionBuscarUrls.abort = true;
            reanudarDesdePausaBuscarUrls(sesionBuscarUrls);
            actualizarPanelProgreso({
                indice: sesionBuscarUrls.indiceActual,
                total: sesionBuscarUrls.total,
                resumen_urls: resumirUrlsDesdeResultados(sesionBuscarUrls.resultados),
                mensaje_estado: 'Deteniendo…',
            });

            if (sesionBuscarUrls.ejecucion_id) {
                try {
                    const data = await postJsonBuscarUrls(URL_CRON_BUSCAR_URLS, {
                        accion: 'detener',
                        categoria_id: CATEGORIA_ID_BUSCAR_URLS,
                        ejecucion_id: sesionBuscarUrls.ejecucion_id,
                    });
                    sesionBuscarUrls.resultados = data.resultados || sesionBuscarUrls.resultados;
                    actualizarPanelProgreso(Object.assign({}, data, {
                        mensaje_estado: 'Detenido por el usuario (' + (data.indice || sesionBuscarUrls.indiceActual) + '/' + (data.total || sesionBuscarUrls.total) + ').',
                    }));
                } catch (error) {
                    console.error('No se pudo registrar la detención en EjecucionGlobal:', error);
                }
            }
        });
    });
    </script>
    @endif
</x-app-layout>












