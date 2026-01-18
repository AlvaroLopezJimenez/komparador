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

    <div class="max-w-5xl mx-auto py-10 px-4 space-y-8 bg-gray-50 dark:bg-gray-900 rounded-lg shadow-md"
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
        <form method="POST" action="{{ $categoria ? route('admin.categorias.update', $categoria) : route('admin.categorias.store') }}">
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

                {{-- IMÁGENES --}}
                <fieldset class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6 space-y-4 border border-gray-200 dark:border-gray-700">
                    <legend class="text-lg font-semibold text-gray-700 dark:text-gray-200">Imagen de la categoría</legend>

                    <!-- Pestañas de gestión de imágenes -->
                    <div class="border-b border-gray-200 dark:border-gray-600">
                        <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                            <button type="button" id="tab-upload-categoria" class="tab-button-categoria border-b-2 border-blue-500 py-2 px-1 text-sm font-medium text-blue-600 dark:text-blue-400" aria-current="page">
                                Subir imagen
                            </button>
                            <button type="button" id="tab-manual-categoria" class="tab-button-categoria border-b-2 border-transparent py-2 px-1 text-sm font-medium text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300">
                                Nombre manual
                            </button>
                            <button type="button" id="tab-url-categoria" class="tab-button-categoria border-b-2 border-transparent py-2 px-1 text-sm font-medium text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300">
                                Descargar desde URL
                            </button>
                        </nav>
                    </div>

                    <!-- Contenido de la pestaña de subida -->
                    <div id="content-upload-categoria" class="tab-content-categoria space-y-4">
                        <div>
                            <label class="block mb-2 font-medium text-gray-700 dark:text-gray-200">Imagen de la categoría *</label>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <!-- Selector de carpeta -->
                                <div>
                                    <label class="block mb-1 text-sm text-gray-600 dark:text-gray-400">Carpeta</label>
                                    <div class="flex gap-2">
                                        <select id="carpeta-imagen-categoria" class="flex-1 px-3 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border text-sm">
                                            <option value="">Selecciona una carpeta</option>
                                            <option value="categorias">categorias</option>
                                            <option value="panales">panales</option>
                                            <option value="tiendas">tiendas</option>
                                        </select>
                                        <button type="button" id="btn-ver-imagenes-categoria" class="bg-gray-500 hover:bg-gray-600 text-white px-3 py-2 rounded text-sm">
                                            Ver
                                        </button>
                                    </div>
                                    
                                    <!-- Información de imagen actual -->
                                    @php
                                        $imgActual = old('imagen', $categoria->imagen ?? '');
                                        $carpetaActual = $imgActual ? explode('/', $imgActual)[0] : '';
                                    @endphp
                                    @if($imgActual)
                                    <div class="mt-2 p-2 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-700 rounded text-xs">
                                        <div class="text-green-700 dark:text-green-300 font-medium">✓ Imagen actual:</div>
                                        <div class="text-green-600 dark:text-green-400 break-words">{{ $imgActual }}</div>
                                        
                                        <!-- Vista previa de imagen actual -->
                                        <div class="mt-2 flex items-center gap-2">
                                            <img id="preview-upload-categoria" src="{{ asset('images/' . $imgActual) }}" alt="Vista previa" class="h-12 w-12 object-contain border rounded">
                                            <button type="button" id="btn-limpiar-categoria" class="text-xs text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300">
                                                Limpiar
                                            </button>
                                        </div>
                                    </div>
                                    @else
                                    <!-- Vista previa vacía (oculta por defecto) -->
                                    <div class="mt-2 flex items-center gap-2 hidden" id="preview-container-categoria">
                                        <img id="preview-upload-categoria" src="" alt="Vista previa" class="h-12 w-12 object-contain border rounded">
                                        <button type="button" id="btn-limpiar-categoria" class="text-xs text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300">
                                            Limpiar
                                        </button>
                                    </div>
                                    @endif
                                </div>
                                
                                <!-- Área de upload -->
                                <div class="md:col-span-2">
                                    <label class="block mb-1 text-sm text-gray-600 dark:text-gray-400">Seleccionar imagen</label>
                                    <div class="flex gap-2">
                                        <input type="file" id="file-imagen-categoria" accept="image/*" class="hidden">
                                        <button type="button" id="btn-seleccionar-categoria" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm">
                                            Seleccionar archivo
                                        </button>
                                        <span id="nombre-archivo-categoria" class="text-sm text-gray-500 dark:text-gray-400 self-center"></span>
                                    </div>
                                    
                                    <!-- Área de drag & drop -->
                                    <div id="drop-zone-categoria" class="mt-2 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-4 text-center hover:border-blue-400 dark:hover:border-blue-500 transition-colors">
                                        <div class="text-gray-500 dark:text-gray-400">
                                            <svg class="mx-auto h-8 w-8 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                            </svg>
                                            <p>Arrastra y suelta la imagen aquí</p>
                                            <p class="text-xs">o haz clic para seleccionar</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Campo oculto -->
                            <input type="hidden" id="ruta-imagen-categoria" name="imagen" value="{{ $categoria ? $categoria->imagen : '' }}">
                            <span id="ruta-completa-categoria" class="text-sm text-gray-600 dark:text-gray-400 hidden"></span>
                            @error('imagen') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <!-- Contenido de la pestaña manual (oculto por defecto) -->
                    <div id="content-manual-categoria" class="tab-content-categoria space-y-4 hidden">
                        <div>
                            <label class="block mb-1 font-medium text-gray-700 dark:text-gray-200">Nombre de imagen *</label>
                            <div class="flex gap-2 items-center">
                                <input type="text" id="imagen-categoria-input" name="imagen_manual" 
                                    value="{{ $categoria && $categoria->imagen ? $categoria->imagen : '' }}"
                                    placeholder="categorias/ejemplo.jpg" 
                                    class="w-full px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border @error('imagen') border-red-500 @enderror">
                                <button type="button" id="buscar-imagen-categoria" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-3 h-full rounded">Buscar imagen</button>
                                @php
                                    $imgCategoria = old('imagen', $categoria->imagen ?? '');
                                    $imgCategoriaSrc = $imgCategoria ? asset('images/' . $imgCategoria) : '';
                                    $imgCategoriaDisplay = $imgCategoria ? 'block' : 'none';
                                @endphp
                                <img id="preview-imagen-categoria" src="{{ $imgCategoriaSrc }}" alt="Vista previa" class="h-12 w-12 object-contain ml-2 border rounded" style="display: {{ $imgCategoriaDisplay }};">
                            </div>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Ejemplo: categorias/panales.jpg</p>
                            @error('imagen') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <!-- Contenido de la pestaña de descarga desde URL (oculto por defecto) -->
                    <div id="content-url-categoria" class="tab-content-categoria space-y-4 hidden">
                        <div>
                            <label class="block mb-2 font-medium text-gray-700 dark:text-gray-200">Descargar imagen desde URL *</label>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Se generará automáticamente la imagen redimensionada a 144x120.</p>
                            
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <!-- Selector de carpeta -->
                                <div>
                                    <label class="block mb-1 text-sm text-gray-600 dark:text-gray-400">Carpeta</label>
                                    <select id="carpeta-imagen-url-categoria" class="w-full px-3 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border text-sm">
                                        <option value="">Selecciona una carpeta</option>
                                        <option value="categorias">categorias</option>
                                        <option value="panales">panales</option>
                                        <option value="tiendas">tiendas</option>
                                    </select>
                                </div>
                                
                                <!-- URL y botón -->
                                <div class="md:col-span-2">
                                    <label class="block mb-1 text-sm text-gray-600 dark:text-gray-400">URL de la imagen</label>
                                    <div class="flex gap-2">
                                        <input type="url" id="url-imagen-categoria" 
                                            class="flex-1 px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border text-sm"
                                            placeholder="https://ejemplo.com/imagen.jpg">
                                        <button type="button" id="btn-descargar-url-categoria" 
                                            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm disabled:bg-gray-400 disabled:cursor-not-allowed">
                                            Descargar
                                        </button>
                                    </div>
                                    <div id="error-url-categoria" class="mt-1 text-sm text-red-500 hidden"></div>
                                    
                                    <!-- Resultado de la ruta -->
                                    <div id="ruta-resultado-categoria" class="mt-2 text-sm text-gray-600 dark:text-gray-400 hidden">
                                        <div class="flex items-center gap-2">
                                            <span class="font-medium">Imagen:</span>
                                            <span id="ruta-texto-categoria" class="font-medium"></span>
                                            <span id="estado-proceso-categoria" class="ml-2"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Campo oculto para la ruta -->
                            <input type="hidden" id="ruta-imagen-categoria-url" name="imagen" value="{{ old('imagen', $categoria->imagen ?? '') }}">
                        </div>
                    </div>
                </fieldset>

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

        {{-- MODAL PARA VER IMÁGENES EXISTENTES --}}
        <div id="modalImagenesCategoria" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center hidden">
            <div class="bg-white dark:bg-gray-900 rounded-lg p-6 max-w-6xl w-full relative shadow-xl overflow-y-auto max-h-[90vh]">
                <button onclick="cerrarModalImagenesCategoria()" class="absolute top-3 right-4 text-xl text-gray-800 dark:text-gray-100 hover:text-gray-600 dark:hover:text-gray-300">×</button>
                <div class="mb-4">
                    <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-200">Imágenes en la carpeta: <span class="text-blue-600 dark:text-blue-400" id="nombre-carpeta-modal">categorias</span></h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Haz clic en una imagen para seleccionarla</p>
                </div>
                <div id="contenido-modal-imagenes-categoria" class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                    <div class="text-center text-gray-500 dark:text-gray-400">Cargando imágenes...</div>
                </div>
            </div>
        </div>

        {{-- MODAL PARA RECORTAR IMAGEN DESDE URL --}}
        <div id="modalRecortarImagenCategoria" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
            <div class="bg-white dark:bg-gray-900 rounded-lg p-6 max-w-4xl w-full relative shadow-xl">
                <button onclick="cerrarModalRecortarCategoria()" class="absolute top-3 right-4 text-xl text-gray-800 dark:text-gray-100 hover:text-gray-600 dark:hover:text-gray-300">×</button>
                <div class="mb-4">
                    <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-200">Recortar imagen</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Selecciona el área que deseas mantener. La imagen se redimensionará automáticamente a 144x120.</p>
                </div>
                <div class="mb-4">
                    <div id="contenedor-cropper-categoria" class="max-h-[60vh] overflow-auto">
                        <img id="imagen-recortar-categoria" src="" alt="Imagen a recortar" style="max-width: 100%; display: block;">
                    </div>
                </div>
                <div class="flex justify-end gap-3">
                    <button type="button" onclick="cerrarModalRecortarCategoria()" 
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">
                        Cancelar
                    </button>
                    <button type="button" id="btn-confirmar-recorte-categoria" 
                        class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700">
                        Aceptar y procesar
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

        // Inicialización cuando se carga la página
        document.addEventListener('DOMContentLoaded', () => {
            console.log('DOM cargado, inicializando sistema de categorías...');
            
            const datos = document.getElementById('datos-categorias');
            if (datos) {
                const categoriasData = datos.dataset.categoriasRaiz;
                const categoriaData = datos.dataset.categoriaActual;
                const parentIdData = datos.dataset.parentId;
                
                try {
                    categoriasRaiz = JSON.parse(categoriasData || '[]');
                    categoriaActual = categoriaData && categoriaData !== 'null' ? JSON.parse(categoriaData) : null;
                    parentIdActual = parentIdData || null;
                    
                    console.log('Categorías raíz parseadas:', categoriasRaiz);
                    console.log('Categoría actual:', categoriaActual);
                    console.log('Parent ID actual:', parentIdActual);
                    
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
            
            // Configurar scripts de imágenes
            inicializarPestañasImagenes();
            configurarUpload();
            configurarBotonVerImagenes();
            configurarImagenManual();
            
            // Verificar slug inicial si estamos editando
            if (categoriaActual) {
                const scope = document.querySelector('[x-data]')?._x_dataStack?.[0];
                if (scope) {
                    setTimeout(() => {
                        scope.verificarSlugExistente();
                    }, 500);
                }
            }
        });

        // Función para inicializar pestañas de imágenes
        function inicializarPestañasImagenes() {
            const tabUpload = document.getElementById('tab-upload-categoria');
            const tabManual = document.getElementById('tab-manual-categoria');
            const tabUrl = document.getElementById('tab-url-categoria');
            const contentUpload = document.getElementById('content-upload-categoria');
            const contentManual = document.getElementById('content-manual-categoria');
            const contentUrl = document.getElementById('content-url-categoria');

            if (!tabUpload || !tabManual || !tabUrl || !contentUpload || !contentManual || !contentUrl) {
                return;
            }

            // Función para cambiar pestañas
            function cambiarTab(tabActiva, contenidoActivo) {
                // Ocultar todos los contenidos
                contentUpload.classList.add('hidden');
                contentManual.classList.add('hidden');
                contentUrl.classList.add('hidden');
                
                // Quitar estilos activos de todas las pestañas
                tabUpload.classList.remove('border-blue-500', 'text-blue-600', 'dark:text-blue-400');
                tabUpload.classList.add('border-transparent', 'text-gray-500', 'dark:text-gray-400');
                tabManual.classList.remove('border-blue-500', 'text-blue-600', 'dark:text-blue-400');
                tabManual.classList.add('border-transparent', 'text-gray-500', 'dark:text-gray-400');
                tabUrl.classList.remove('border-blue-500', 'text-blue-600', 'dark:text-blue-400');
                tabUrl.classList.add('border-transparent', 'text-gray-500', 'dark:text-gray-400');
                
                // Mostrar contenido activo
                contenidoActivo.classList.remove('hidden');
                
                // Estilo activo para la pestaña seleccionada
                tabActiva.classList.remove('border-transparent', 'text-gray-500', 'dark:text-gray-400');
                tabActiva.classList.add('border-blue-500', 'text-blue-600', 'dark:text-blue-400');
            }

            // Eventos de cambio de pestañas
            tabUpload.addEventListener('click', () => cambiarTab(tabUpload, contentUpload));
            tabManual.addEventListener('click', () => cambiarTab(tabManual, contentManual));
            tabUrl.addEventListener('click', () => cambiarTab(tabUrl, contentUrl));
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
                
                console.log('Jerarquía recibida:', jerarquia);
                
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
            console.log(`Creando selector nivel ${nivel} con ${opciones.length} opciones, modoEdicion: ${modoEdicion}`);
            
            const container = document.getElementById('categorias-container');
            if (!container) {
                console.error('No se encontró el contenedor de categorías');
                return;
            }
            
            // Verificar si ya existe un selector en este nivel (para evitar duplicados)
            const selectorExistente = container.querySelector(`.selector-categoria[data-nivel="${nivel}"]`);
            if (selectorExistente) {
                console.log(`Selector nivel ${nivel} ya existe, actualizando...`);
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
                    console.log(`Cambio en selector nivel ${nivel}, categoría seleccionada:`, categoriaId);
                    
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
                    console.log(`Cambio en selector nivel ${nivel}, categoría seleccionada:`, categoriaId);
                    
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
            console.log(`Iniciando carga de subcategorías para nivel ${nivel}, categoría ${categoriaId}`);
            
            limpiarSelectoresSuperiores(nivel);
            
            const url = `/panel-privado/categorias/${categoriaId}/subcategorias`;
            console.log(`Haciendo fetch a: ${url}`);
            
            fetch(url, {
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            })
            .then(res => {
                console.log('Respuesta del servidor:', res.status, res.statusText);
                return res.json();
            })
            .then(subcategorias => {
                console.log('Subcategorías recibidas:', subcategorias);
                if (subcategorias && subcategorias.length > 0) {
                    console.log(`Creando selector para nivel ${nivel} con ${subcategorias.length} subcategorías`);
                    crearSelectorCategoria(nivel, subcategorias, null);
                } else {
                    console.log('No hay subcategorías disponibles para este nivel');
                }
            })
            .catch(error => {
                console.error('Error cargando subcategorías:', error);
            });
        }

        function limpiarSelectoresSuperiores(nivel) {
            const selectores = document.querySelectorAll(`.selector-categoria[data-nivel="${nivel}"]`);
            console.log(`Limpiando ${selectores.length} selectores del nivel ${nivel}`);
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
            console.log('Categoría final actualizada:', categoriaFinal);
        }

        // Función para configurar upload
        function configurarUpload() {
            const carpetaSelect = document.getElementById('carpeta-imagen-categoria');
            const fileInput = document.getElementById('file-imagen-categoria');
            const btnSeleccionar = document.getElementById('btn-seleccionar-categoria');
            const dropZone = document.getElementById('drop-zone-categoria');
            const nombreArchivo = document.getElementById('nombre-archivo-categoria');
            const preview = document.getElementById('preview-upload-categoria');
            const rutaImagen = document.getElementById('ruta-imagen-categoria');
            const rutaCompleta = document.getElementById('ruta-completa-categoria');
            const previewContainer = document.getElementById('preview-container-categoria');

            if (!carpetaSelect || !fileInput || !btnSeleccionar || !dropZone || !nombreArchivo || !preview || !rutaImagen) {
                console.error('No se encontraron todos los elementos necesarios para la subida de imágenes');
                return;
            }

            btnSeleccionar.addEventListener('click', () => {
                fileInput.click();
            });

            fileInput.addEventListener('change', (e) => {
                const file = e.target.files[0];
                if (file) {
                    procesarArchivo(file);
                }
            });

            dropZone.addEventListener('click', () => fileInput.click());
            
            dropZone.addEventListener('dragover', (e) => {
                e.preventDefault();
                dropZone.classList.add('border-blue-500', 'bg-blue-50', 'dark:bg-blue-900/20');
            });

            dropZone.addEventListener('dragleave', (e) => {
                e.preventDefault();
                dropZone.classList.remove('border-blue-500', 'bg-blue-50', 'dark:bg-blue-900/20');
            });

            dropZone.addEventListener('drop', (e) => {
                e.preventDefault();
                dropZone.classList.remove('border-blue-500', 'bg-blue-50', 'dark:bg-blue-900/20');
                
                const files = e.dataTransfer.files;
                if (files.length > 0) {
                    procesarArchivo(files[0]);
                }
            });

            // Función para procesar el archivo
            function procesarArchivo(file) {
                // Validar que sea una imagen
                if (!file.type.startsWith('image/')) {
                    alert('Por favor selecciona un archivo de imagen válido.');
                    return;
                }

                // Validar tamaño (máximo 5MB)
                if (file.size > 5 * 1024 * 1024) {
                    alert('La imagen es demasiado grande. Máximo 5MB.');
                    return;
                }

                // Validar que se haya seleccionado una carpeta
                const carpeta = carpetaSelect.value;
                if (!carpeta) {
                    alert('Por favor selecciona una carpeta primero.');
                    return;
                }

                // Mostrar nombre del archivo
                nombreArchivo.textContent = file.name;

                // Crear vista previa
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.classList.remove('hidden');
                    
                    // Mostrar contenedor de vista previa
                    if (previewContainer) {
                        previewContainer.classList.remove('hidden');
                    }
                };
                reader.readAsDataURL(file);

                // Subir imagen al servidor
                subirImagen(file, carpeta);
            }

            // Función para limpiar campos de imagen
            function limpiarCamposImagen() {
                rutaImagen.value = '';
                if (rutaCompleta) {
                    rutaCompleta.textContent = '';
                }
                nombreArchivo.textContent = '';
                fileInput.value = '';
                
                // Ocultar contenedor de vista previa
                if (previewContainer) {
                    previewContainer.classList.add('hidden');
                }
                
                // Limpiar vista previa
                preview.src = '';
                preview.classList.add('hidden');
            }

            // Limpiar campos cuando se cambia la carpeta
            carpetaSelect.addEventListener('change', () => {
                limpiarCamposImagen();
            });

            // Configurar botón de limpiar
            const btnLimpiar = document.getElementById('btn-limpiar-categoria');
            if (btnLimpiar) {
                btnLimpiar.addEventListener('click', () => {
                    limpiarCamposImagen();
                });
            }

            // Función para redimensionar imagen a 144x120
            function redimensionarImagen(file, callback) {
                const img = new Image();
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    img.onload = function() {
                        const canvas = document.createElement('canvas');
                        const ctx = canvas.getContext('2d');
                        
                        // Tamaño objetivo: 144x120
                        canvas.width = 144;
                        canvas.height = 120;
                        
                        // Redimensionar imagen estirando al tamaño exacto
                        ctx.imageSmoothingEnabled = true;
                        ctx.imageSmoothingQuality = 'high';
                        ctx.drawImage(img, 0, 0, 144, 120);
                        
                        // Convertir a blob (webp)
                        canvas.toBlob(function(blob) {
                            if (blob) {
                                callback(blob);
                            } else {
                                callback(file); // Si falla, usar archivo original
                            }
                        }, 'image/webp', 0.9);
                    };
                    img.src = e.target.result;
                };
                reader.readAsDataURL(file);
            }

            // Función para subir la imagen al servidor
            function subirImagen(file, carpeta) {
                // Mostrar indicador de carga
                nombreArchivo.textContent = 'Redimensionando y subiendo...';
                btnSeleccionar.disabled = true;

                // Redimensionar imagen antes de subir
                redimensionarImagen(file, function(blobRedimensionado) {
                    const formData = new FormData();
                    
                    // Obtener nombre base del slug o nombre de categoría
                    const slugInput = document.getElementById('slug-categoria');
                    const nombreBase = slugInput && slugInput.value.trim() ? slugInput.value.trim() : 'categoria_' + Date.now();
                    
                    formData.append('imagen', blobRedimensionado, `${nombreBase}.webp`);
                    formData.append('carpeta', carpeta);
                    formData.append('_token', '{{ csrf_token() }}');

                    nombreArchivo.textContent = 'Subiendo...';

                    fetch('{{ route("admin.imagenes.subir-simple") }}', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Actualizar campos con la respuesta del servidor
                            rutaImagen.value = data.data.ruta_relativa;
                            if (rutaCompleta) {
                                rutaCompleta.textContent = `Ruta: ${data.data.ruta_relativa}`;
                            }
                            nombreArchivo.textContent = '✓ Subida correctamente';
                            
                            // Mostrar contenedor de vista previa
                            if (previewContainer) {
                                previewContainer.classList.remove('hidden');
                            }
                            
                            // Actualizar vista previa con la imagen subida
                            preview.src = `/images/${data.data.ruta_relativa}`;
                            
                            // Sincronizar con pestaña manual
                            const inputManual = document.getElementById('imagen-categoria-input');
                            const previewManual = document.getElementById('preview-imagen-categoria');
                            if (inputManual) {
                                inputManual.value = data.data.ruta_relativa;
                            }
                            if (previewManual) {
                                previewManual.src = `/images/${data.data.ruta_relativa}`;
                                previewManual.style.display = 'block';
                            }
                            
                            console.log('Imagen subida:', data.data);
                        } else {
                            throw new Error(data.message || 'Error al subir la imagen');
                        }
                    })
                    .catch(error => {
                        console.error('Error al subir imagen:', error);
                        nombreArchivo.textContent = '✗ Error al subir';
                        alert(`Error al subir la imagen: ${error.message}`);
                        
                        // Limpiar campos en caso de error
                        rutaImagen.value = '';
                        if (rutaCompleta) {
                            rutaCompleta.textContent = '';
                        }
                        preview.classList.add('hidden');
                    })
                    .finally(() => {
                        btnSeleccionar.disabled = false;
                    });
                });
            }
        }

        function configurarBotonVerImagenes() {
            const btnVer = document.getElementById('btn-ver-imagenes-categoria');
            const carpetaSelect = document.getElementById('carpeta-imagen-categoria');
            const rutaImagen = document.getElementById('ruta-imagen-categoria');
            const rutaCompleta = document.getElementById('ruta-completa-categoria');
            const preview = document.getElementById('preview-upload-categoria');

            if (!btnVer || !carpetaSelect) {
                return;
            }

            btnVer.addEventListener('click', () => {
                const carpeta = carpetaSelect.value;
                if (!carpeta) {
                    alert('Por favor selecciona una carpeta primero.');
                    return;
                }
                abrirModalImagenes(carpeta, rutaImagen, rutaCompleta, preview);
            });
        }

        // Función para abrir el modal de imágenes
        function abrirModalImagenes(carpeta, rutaImagen, rutaCompleta, preview) {
            const modal = document.getElementById('modalImagenesCategoria');
            const nombreCarpeta = document.getElementById('nombre-carpeta-modal');
            const contenido = document.getElementById('contenido-modal-imagenes-categoria');

            if (nombreCarpeta) {
                nombreCarpeta.textContent = carpeta;
            }
            if (modal) {
                modal.classList.remove('hidden');
            }

            // Cargar imágenes de la carpeta
            cargarImagenesCarpeta(carpeta, contenido, rutaImagen, rutaCompleta, preview);
        }

        // Función para cargar imágenes de una carpeta
        function cargarImagenesCarpeta(carpeta, contenido, rutaImagen, rutaCompleta, preview) {
            contenido.innerHTML = '<div class="text-center text-gray-500 dark:text-gray-400">Cargando imágenes...</div>';

            fetch(`{{ route('admin.imagenes.listar') }}?carpeta=${carpeta}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.data.length > 0) {
                        mostrarImagenesEnModal(data.data, contenido, rutaImagen, rutaCompleta, preview);
                    } else {
                        contenido.innerHTML = '<div class="text-center text-gray-500 dark:text-gray-400">No hay imágenes en esta carpeta</div>';
                    }
                })
                .catch(error => {
                    console.error('Error al cargar imágenes:', error);
                    contenido.innerHTML = '<div class="text-center text-red-500">Error al cargar las imágenes</div>';
                });
        }

        // Función para mostrar imágenes en el modal
        function mostrarImagenesEnModal(imagenes, contenido, rutaImagen, rutaCompleta, preview) {
            contenido.innerHTML = '';

            imagenes.forEach(imagen => {
                const div = document.createElement('div');
                div.className = 'relative group cursor-pointer hover:scale-105 transition-transform duration-200';
                div.innerHTML = `
                    <img src="${imagen.url}" alt="${imagen.nombre}" 
                         class="w-full h-24 object-cover rounded border-2 border-transparent hover:border-blue-500">
                    <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-20 transition-all duration-200 rounded"></div>
                    <div class="absolute bottom-0 left-0 right-0 bg-black bg-opacity-75 text-white text-xs p-1 rounded-b truncate">
                        ${imagen.nombre}
                    </div>
                `;

                div.addEventListener('click', () => {
                    seleccionarImagenExistente(imagen, rutaImagen, rutaCompleta, preview);
                    cerrarModalImagenesCategoria();
                });

                contenido.appendChild(div);
            });
        }

        // Función para seleccionar una imagen existente
        function seleccionarImagenExistente(imagen, rutaImagen, rutaCompleta, preview) {
            // Actualizar campos
            rutaImagen.value = imagen.ruta;
            if (rutaCompleta) {
                rutaCompleta.textContent = `Ruta: ${imagen.ruta}`;
            }

            // Actualizar vista previa
            preview.src = imagen.url;
            preview.classList.remove('hidden');

            // Actualizar nombre del archivo
            const nombreArchivo = document.getElementById('nombre-archivo-categoria');
            if (nombreArchivo) {
                nombreArchivo.textContent = `✓ Seleccionada: ${imagen.nombre}`;
            }

            // Mostrar contenedor de vista previa
            const previewContainer = document.getElementById('preview-container-categoria');
            if (previewContainer) {
                previewContainer.classList.remove('hidden');
            }

            // Sincronizar con pestaña manual
            const inputManual = document.getElementById('imagen-categoria-input');
            const previewManual = document.getElementById('preview-imagen-categoria');
            if (inputManual) {
                inputManual.value = imagen.ruta;
            }
            if (previewManual) {
                previewManual.src = imagen.url;
                previewManual.style.display = 'block';
            }

            console.log('Imagen seleccionada:', imagen);
        }

        // Función para cerrar el modal de imágenes
        window.cerrarModalImagenesCategoria = function() {
            document.getElementById('modalImagenesCategoria').classList.add('hidden');
        };

        function seleccionarImagenCategoria(ruta) {
            const rutaImagen = document.getElementById('ruta-imagen-categoria');
            const rutaCompleta = document.getElementById('ruta-completa-categoria');
            const preview = document.getElementById('preview-upload-categoria');
            const inputManual = document.getElementById('imagen-categoria-input');
            const previewManual = document.getElementById('preview-imagen-categoria');
            const previewContainer = document.getElementById('preview-container-categoria');
            
            if (rutaImagen) {
                rutaImagen.value = ruta;
            }
            if (rutaCompleta) {
                rutaCompleta.textContent = `Ruta: ${ruta}`;
            }
            if (preview) {
                preview.src = `/images/${ruta}`;
                preview.classList.remove('hidden');
            }
            if (previewContainer) {
                previewContainer.classList.remove('hidden');
            }
            if (inputManual) {
                inputManual.value = ruta;
            }
            if (previewManual) {
                previewManual.src = `/images/${ruta}`;
                previewManual.style.display = 'block';
            }
            
            cerrarModalImagenesCategoria();
        }

        function configurarImagenManual() {
            const btnBuscar = document.getElementById('buscar-imagen-categoria');
            const inputImagen = document.getElementById('imagen-categoria-input');
            const preview = document.getElementById('preview-imagen-categoria');
            const rutaImagen = document.getElementById('ruta-imagen-categoria');
            
            if (btnBuscar) {
                btnBuscar.onclick = function() {
                    const input = document.getElementById('imagen-categoria-input');
                    if (input && input.value.trim() !== '') {
                        if (preview) {
                            preview.src = '/images/' + input.value.trim();
                            preview.style.display = 'block';
                        }
                        if (rutaImagen) {
                            rutaImagen.value = input.value.trim();
                        }
                    } else if (preview) {
                        preview.src = '';
                        preview.style.display = 'none';
                    }
                };
            }
            
            if (inputImagen) {
                inputImagen.addEventListener('input', function() {
                    if (rutaImagen) {
                        rutaImagen.value = this.value;
                    }
                    if (this.value.trim() === '' && preview) {
                        preview.style.display = 'none';
                    } else if (this.value.trim() !== '' && preview) {
                        preview.src = '/images/' + this.value.trim();
                        preview.style.display = 'block';
                    }
                });
            }
        }

        // Sincronizar campos entre pestañas
        function sincronizarCampos() {
            const rutaImagen = document.getElementById('ruta-imagen-categoria').value;
            
            // Sincronizar imagen
            const inputManual = document.getElementById('imagen-categoria-input');
            if (inputManual) {
                inputManual.value = rutaImagen || '';
            }
            
            // Sincronizar vista previa en pestaña manual
            if (rutaImagen) {
                const previewManual = document.getElementById('preview-imagen-categoria');
                if (previewManual) {
                    previewManual.src = `{{ asset('images/') }}/${rutaImagen}`;
                    previewManual.style.display = 'block';
                }
            }
        }

        // Sincronizar campos manuales con los campos de upload
        function sincronizarCamposManuales() {
            const inputManual = document.getElementById('imagen-categoria-input');
            const rutaImagen = document.getElementById('ruta-imagen-categoria');
            
            if (inputManual && inputManual.value) {
                if (rutaImagen) {
                    rutaImagen.value = inputManual.value;
                }
            }
        }

        // Sincronizar antes de enviar el formulario
        const form = document.querySelector('form');
        if (form) {
            form.addEventListener('submit', function(e) {
                sincronizarCampos();
            });
        }
        
        // Sincronizar campos manuales cuando se escriba en ellos
        const inputManual = document.getElementById('imagen-categoria-input');
        if (inputManual) {
            inputManual.addEventListener('input', sincronizarCamposManuales);
        }

        // Habilitar botón de guardar cuando hay nombre
        document.addEventListener('DOMContentLoaded', function() {
            const nombreInput = document.getElementById('nombre-categoria');
            const btnGuardar = document.getElementById('btn-guardar-categoria');
            
            if (nombreInput && btnGuardar) {
                nombreInput.addEventListener('input', function() {
                    const tieneNombre = this.value.trim().length > 0;
                    const scope = document.querySelector('[x-data]')?._x_dataStack?.[0];
                    const slugExiste = scope ? scope.slugExiste : false;
                    btnGuardar.disabled = !tieneNombre || slugExiste;
                });
            }
        });
    </script>

    {{-- SCRIPT PARA DESCARGAR Y RECORTAR IMAGEN DESDE URL --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/cropperjs@1.5.13/dist/cropper.min.css">
    <script src="https://cdn.jsdelivr.net/npm/cropperjs@1.5.13/dist/cropper.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        let cropperCategoria = null;
        let imagenTemporalUrlCategoria = null;
        let carpetaActualCategoria = null;
        let nombreCategoria = null;

        // Función para validar que el slug esté relleno
        function validarNombre() {
            // Obtener el slug
            const slugInput = document.getElementById('slug-categoria');
            let slug = slugInput ? slugInput.value.trim() : '';
            
            // Si no hay slug, intentar obtener el nombre
            if (!slug) {
                const nombreInput = document.getElementById('nombre-categoria');
                slug = nombreInput ? nombreInput.value.trim() : '';
            }
            
            nombreCategoria = slug;
            return slug !== '';
        }

        // Función para mostrar error
        function mostrarError(mensaje) {
            const errorDiv = document.getElementById('error-url-categoria');
            if (errorDiv) {
                errorDiv.textContent = mensaje;
                errorDiv.classList.remove('hidden');
            }
        }

        // Función para ocultar error
        function ocultarError() {
            const errorDiv = document.getElementById('error-url-categoria');
            if (errorDiv) {
                errorDiv.classList.add('hidden');
            }
        }

        // Función para mostrar estado de carga
        function mostrarEstado(estado) {
            const estadoDiv = document.getElementById('estado-proceso-categoria');
            const rutaResultado = document.getElementById('ruta-resultado-categoria');
            
            if (estado === 'loading') {
                if (estadoDiv) {
                    estadoDiv.innerHTML = '<svg class="animate-spin h-5 w-5 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>';
                }
                if (rutaResultado) {
                    rutaResultado.classList.remove('hidden');
                }
            } else if (estado === 'success') {
                if (estadoDiv) {
                    estadoDiv.innerHTML = '<svg class="h-5 w-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>';
                }
                if (rutaResultado) {
                    rutaResultado.classList.remove('hidden');
                }
            } else if (estado === 'error') {
                if (estadoDiv) {
                    estadoDiv.innerHTML = '<svg class="h-5 w-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>';
                }
                if (rutaResultado) {
                    rutaResultado.classList.remove('hidden');
                }
            }
        }

        // Función para descargar imagen desde URL
        async function descargarImagen(url) {
            if (!validarNombre()) {
                mostrarError('Debes escribir el nombre o slug de la categoría antes de descargar la imagen.');
                return;
            }

            const carpetaSelect = document.getElementById('carpeta-imagen-url-categoria');
            const carpeta = carpetaSelect.value;

            if (!carpeta) {
                mostrarError('Debes seleccionar una carpeta primero.');
                return;
            }

            if (!url || url.trim() === '') {
                mostrarError('Debes introducir una URL válida.');
                return;
            }

            ocultarError();
            carpetaActualCategoria = carpeta;
            imagenTemporalUrlCategoria = url;

            // Mostrar modal de recorte directamente con la URL original
            mostrarModalRecortar(url);
        }

        // Función para mostrar modal de recorte
        function mostrarModalRecortar(urlImagen) {
            const modal = document.getElementById('modalRecortarImagenCategoria');
            const img = document.getElementById('imagen-recortar-categoria');
            
            // Limpiar cropper anterior si existe
            if (cropperCategoria) {
                cropperCategoria.destroy();
                cropperCategoria = null;
            }
            
            // Usar proxy para evitar problemas CORS
            const urlProxy = urlImagen.startsWith('http://') || urlImagen.startsWith('https://') 
                ? `{{ route('admin.imagenes.proxy') }}?url=${encodeURIComponent(urlImagen)}`
                : urlImagen;
            
            // Configurar la imagen con crossOrigin para evitar problemas CORS
            img.crossOrigin = 'anonymous';
            img.src = urlProxy;
            if (modal) {
                modal.classList.remove('hidden');
            }

            // Inicializar cropper cuando la imagen se cargue
            img.onload = function() {
                if (cropperCategoria) {
                    cropperCategoria.destroy();
                }
                
                cropperCategoria = new Cropper(img, {
                    aspectRatio: NaN, // Sin restricción de aspecto
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
            
            // Manejar errores de carga de imagen
            img.onerror = function() {
                mostrarError('Error al cargar la imagen. Verifica que la URL sea accesible y sea una imagen válida.');
                cerrarModalRecortarCategoria();
            };
        }

        // Función para cerrar modal de recorte
        window.cerrarModalRecortarCategoria = function() {
            const modal = document.getElementById('modalRecortarImagenCategoria');
            if (modal) {
                modal.classList.add('hidden');
            }
            
            if (cropperCategoria) {
                cropperCategoria.destroy();
                cropperCategoria = null;
            }
            
            imagenTemporalUrlCategoria = null;
            carpetaActualCategoria = null;
        };

        // Función para redimensionar canvas estirando la imagen al tamaño exacto
        function redimensionarCanvas(canvasOriginal, anchoDestino, altoDestino) {
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');
            
            // Forzar el tamaño exacto
            canvas.width = anchoDestino;
            canvas.height = altoDestino;
            
            // Estirar la imagen completa al tamaño exacto sin mantener aspecto
            ctx.imageSmoothingEnabled = true;
            ctx.imageSmoothingQuality = 'high';
            // Dibujar la imagen completa estirada al tamaño exacto
            ctx.drawImage(canvasOriginal, 0, 0, canvasOriginal.width, canvasOriginal.height, 0, 0, anchoDestino, altoDestino);
            
            return canvas;
        }

        // Función para procesar imagen recortada
        async function procesarImagenRecortada() {
            if (!cropperCategoria || !carpetaActualCategoria) {
                console.error('Cropper o carpeta no disponible');
                mostrarError('Error: No se puede procesar la imagen. Intenta de nuevo.');
                return;
            }

            // Obtener el canvas recortado sin redimensionar
            const canvasOriginal = cropperCategoria.getCroppedCanvas({
                imageSmoothingEnabled: true,
                imageSmoothingQuality: 'high'
            });

            if (!canvasOriginal) {
                mostrarError('Error al recortar la imagen');
                return;
            }

            mostrarEstado('loading');

            try {
                // Redimensionar a 144x120 para categoría
                const canvasCategoria = redimensionarCanvas(canvasOriginal, 144, 120);

                // Convertir canvas a blob (webp)
                const blobCategoria = await new Promise((resolve, reject) => {
                    canvasCategoria.toBlob((blob) => {
                        if (blob) {
                            resolve(blob);
                        } else {
                            reject(new Error('Error al convertir imagen'));
                        }
                    }, 'image/webp', 0.9);
                });

                // Crear nombre base del archivo desde el slug
                const slugInput = document.getElementById('slug-categoria');
                const nombreBase = slugInput ? slugInput.value.trim() : nombreCategoria.trim();

                // Subir imagen
                const formDataCategoria = new FormData();
                formDataCategoria.append('imagen', blobCategoria, `${nombreBase}.webp`);
                formDataCategoria.append('carpeta', carpetaActualCategoria);
                formDataCategoria.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));

                const responseCategoria = await fetch('{{ route("admin.imagenes.subir-simple") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: formDataCategoria
                });

                const dataCategoria = await responseCategoria.json();

                if (!dataCategoria.success) {
                    throw new Error(dataCategoria.message || 'Error al subir la imagen');
                }

                // Actualizar campos con la ruta
                const rutaCategoria = document.getElementById('ruta-imagen-categoria-url');
                const rutaTextoCategoria = document.getElementById('ruta-texto-categoria');

                // Actualizar ruta
                if (rutaCategoria) {
                    rutaCategoria.value = dataCategoria.data.ruta_relativa;
                }
                if (rutaTextoCategoria) {
                    rutaTextoCategoria.textContent = dataCategoria.data.ruta_relativa;
                }

                // Mostrar estado de éxito
                mostrarEstado('success');
                
                // También actualizar los campos ocultos de la pestaña upload
                const rutaUpload = document.getElementById('ruta-imagen-categoria');
                if (rutaUpload) {
                    rutaUpload.value = dataCategoria.data.ruta_relativa;
                }

                // Actualizar vista previa
                const preview = document.getElementById('preview-upload-categoria');
                const previewContainer = document.getElementById('preview-container-categoria');
                if (preview) {
                    preview.src = `/images/${dataCategoria.data.ruta_relativa}`;
                    preview.classList.remove('hidden');
                }
                if (previewContainer) {
                    previewContainer.classList.remove('hidden');
                }

                // Sincronizar con pestaña manual
                const inputManual = document.getElementById('imagen-categoria-input');
                const previewManual = document.getElementById('preview-imagen-categoria');
                if (inputManual) {
                    inputManual.value = dataCategoria.data.ruta_relativa;
                }
                if (previewManual) {
                    previewManual.src = `/images/${dataCategoria.data.ruta_relativa}`;
                    previewManual.style.display = 'block';
                }

                cerrarModalRecortarCategoria();

            } catch (error) {
                console.error('Error:', error);
                mostrarError(error.message || 'Error al procesar la imagen');
                mostrarEstado('error');
            }
        }

        // Event listener para botón de descarga
        const btnDescargar = document.getElementById('btn-descargar-url-categoria');
        if (btnDescargar) {
            btnDescargar.addEventListener('click', () => {
                const url = document.getElementById('url-imagen-categoria').value.trim();
                descargarImagen(url);
            });
        }

        // Event listener para confirmar recorte
        const btnConfirmar = document.getElementById('btn-confirmar-recorte-categoria');
        if (btnConfirmar) {
            btnConfirmar.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                console.log('Botón confirmar recorte clickeado');
                procesarImagenRecortada();
            });
        }

        // Cargar carpetas disponibles para el select de URL
        fetch('{{ route("admin.imagenes.carpetas") }}')
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data.length > 0) {
                    actualizarSelectCarpetasURL(data.data);
                }
            })
            .catch(error => {
                console.error('Error al cargar carpetas:', error);
            });

        function actualizarSelectCarpetasURL(carpetas) {
            const select = document.getElementById('carpeta-imagen-url-categoria');
            if (!select) return;

            const primeraOpcion = select.querySelector('option[value=""]');
            select.innerHTML = '';
            
            if (primeraOpcion) {
                select.appendChild(primeraOpcion);
            } else {
                const option = document.createElement('option');
                option.value = '';
                option.textContent = 'Selecciona una carpeta';
                select.appendChild(option);
            }

            carpetas.forEach(carpeta => {
                const option = document.createElement('option');
                option.value = carpeta;
                option.textContent = carpeta.charAt(0).toUpperCase() + carpeta.slice(1);
                select.appendChild(option);
            });
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
                    crearLineaPrincipal(filtro.texto || '', filtro.importante || false, filtro.subprincipales || [], filtro.id || null, filtro.slug || null);
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
                // Actualizar slug cuando cambia el texto (solo si no tiene slug guardado)
                if (!divPrincipal.dataset.slug || divPrincipal.dataset.slug === '') {
                    divPrincipal.dataset.slug = generarSlug(this.value);
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
                    crearLineaIntermedia(containerSubprincipales, sub.texto || '', sub.id || null, sub.slug || null);
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
        function crearLineaIntermedia(containerPadre, texto = '', idUnico = null, slugUnico = null) {
            const idIntermedia = `intermedia-${contadorIntermedia++}`;
            const idUnicoLinea = idUnico || generarIdUnico();
            const slugLinea = slugUnico || (texto ? generarSlug(texto) : '');
            const divIntermedia = document.createElement('div');
            divIntermedia.className = 'linea-intermedia flex items-center gap-3 border-l-4 border-blue-400 pl-3 py-2 bg-blue-50 dark:bg-blue-900/20 rounded';
            divIntermedia.dataset.id = idIntermedia;
            divIntermedia.dataset.idUnico = idUnicoLinea;
            divIntermedia.dataset.slug = slugLinea;
            divIntermedia.draggable = false;
            
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
            
            inputTexto.addEventListener('input', actualizarJSON);
            
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
                const slugPrincipal = lineaPrincipal.dataset.slug || generarSlug(texto);
                
                // Actualizar slug en el dataset si cambió el texto
                if (texto && !lineaPrincipal.dataset.slug) {
                    lineaPrincipal.dataset.slug = slugPrincipal;
                }
                
                const containerSubprincipales = lineaPrincipal.querySelector('.subprincipales-container');
                const lineasIntermedias = containerSubprincipales ? containerSubprincipales.querySelectorAll('.linea-intermedia') : [];
                
                const subprincipales = [];
                lineasIntermedias.forEach(lineaIntermedia => {
                    const textoIntermedia = lineaIntermedia.querySelector('.linea-intermedia-texto').value.trim();
                    const idUnicoIntermedia = lineaIntermedia.dataset.idUnico;
                    const slugIntermedia = lineaIntermedia.dataset.slug || generarSlug(textoIntermedia);
                    
                    // Actualizar slug en el dataset si cambió el texto
                    if (textoIntermedia && !lineaIntermedia.dataset.slug) {
                        lineaIntermedia.dataset.slug = slugIntermedia;
                    }
                    
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
</x-app-layout>












