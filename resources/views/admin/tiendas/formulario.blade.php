<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.dashboard') }}">
                <h2 class="font-semibold text-xl text-white leading-tight">Inicio -></h2>
            </a>
            <a href="{{ route('admin.tiendas.index') }}">
                <h2 class="font-semibold text-xl text-white leading-tight">Tiendas -></h2>
            </a>
            <h2 class="font-semibold text-xl text-white leading-tight">
                {{ $tienda->exists ? 'Editar tienda' : 'Añadir tienda' }}
            </h2>
        </div>
    </x-slot>

    <div class="max-w-5xl mx-auto py-10 px-4 space-y-8 bg-gray-50 dark:bg-gray-900 rounded-lg shadow-md">
        @if ($errors->any())
        <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif
        <form method="POST" action="{{ $tienda->exists ? route('admin.tiendas.update', $tienda) : route('admin.tiendas.store') }}">
            @csrf
            @if($tienda->exists)
            @method('PUT')
            @endif

            <fieldset class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6 space-y-6 border border-gray-200 dark:border-gray-700">
                <legend class="text-lg font-semibold text-gray-700 dark:text-gray-200">Información de la tienda</legend>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                    <div>
                        <label class="block mb-1 font-medium text-gray-700 dark:text-gray-200">Nombre *</label>
                        <input type="text" name="nombre" required
                            value="{{ old('nombre', $tienda->nombre) }}" required
                            class="w-full px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border">
                        @error('nombre')
                        <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block mb-1 font-medium text-gray-700 dark:text-gray-200">URL</label>
                        <input type="text" name="url"
                            value="{{ old('url', $tienda->url) }}" required
                            class="w-full px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border">
                    </div>

                    <div>
                        <label class="block mb-1 font-medium text-gray-700 dark:text-gray-200">Envío gratis</label>
                        <input type="text" name="envio_gratis"
                            value="{{ old('envio_gratis', $tienda->envio_gratis) }}" required
                            class="w-full px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border">
                    </div>

                    <div>
                        <label class="block mb-1 font-medium text-gray-700 dark:text-gray-200">Envío normal</label>
                        <input type="text" name="envio_normal"
                            value="{{ old('envio_normal', $tienda->envio_normal) }}" required
                            class="w-full px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border">
                    </div>



                    <div>
                        <label class="block mb-1 font-medium text-gray-700 dark:text-gray-200">Ruta del logo</label>
                        <input type="text" name="url_imagen"
                            value="{{ old('url_imagen', $tienda->url_imagen) }}" required
                            class="w-full px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border">
                        @if($tienda->url_imagen)
                        <div class="mt-3">
                            <img src="{{ asset('images/' .$tienda->url_imagen) }}" alt="Logo tienda"
                                class="max-h-10 rounded shadow border">
                        </div>
                        @endif
                    </div>

                    <div>
                        <label class="block mb-1 font-medium text-gray-700 dark:text-gray-200">Opiniones</label>
                        <input type="number" name="opiniones" min="0"
                            value="{{ old('opiniones', $tienda->opiniones ?? 0) }}" required

                            class="w-full px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border">
                    </div>

                    <div>
                        <label class="block mb-1 font-medium text-gray-700 dark:text-gray-200">Puntuación (0 - 5)</label>
                        <input type="number" step="0.1" min="0" max="5" name="puntuacion"
                            value="{{ old('puntuacion', $tienda->puntuacion ?? 0) }}" required
                            class="w-full px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border">
                    </div>

                    <div>
                        <label class="block mb-1 font-medium text-gray-700 dark:text-gray-200">URL de opiniones</label>
                        <input type="text" name="url_opiniones"
                            value="{{ old('url_opiniones', $tienda->url_opiniones) }}"
                            class="w-full px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border">
                    </div>

                    <div>
                        <label class="block mb-1 font-medium text-gray-700 dark:text-gray-200">
                            API de Scraping *
                            <span class="relative group">
                                <svg class="w-4 h-4 inline text-gray-400 cursor-help" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <div class="absolute bottom-full left-0 mb-2 w-80 p-3 bg-gray-900 text-white text-sm rounded-lg shadow-lg opacity-0 group-hover:opacity-100 transition-opacity duration-200 z-10">
                                    <p><strong>ScrapingAnt:</strong> API de RapidAPI. Ideal para sitios que requieren proxy español. No requiere configuración adicional.</p>
                                    <p class="mt-2"><strong>Bright Data (sin JavaScript):</strong> Más rápido y económico. Ideal para sitios que no requieren JavaScript para mostrar precios.</p>
                                    <p class="mt-2"><strong>Bright Data (con JavaScript):</strong> Más lento y costoso. Necesario para sitios que usan JavaScript para cargar precios dinámicamente.</p>
                                    <p class="mt-2"><strong>Mi VPS — Proxies Residenciales:</strong> Usa proxies residenciales rotativos para mayor anonimato. Ideal para sitios con detección avanzada de bots.</p>
                                </div>
                            </span>
                        </label>
                        <select name="api" id="api-select" required
                                class="w-full px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border">
                            <option value="">Selecciona una API</option>

                            <!-- Mi VPS -->
                            <option value="miVpsHtml;1" {{ old('api', $tienda->api) == 'miVpsHtml;1' ? 'selected' : '' }}>
                                Mi VPS — Selenium (rápido, 0.8s)
                            </option>
                            <option value="miVpsHtml;2" {{ old('api', $tienda->api) == 'miVpsHtml;2' ? 'selected' : '' }}>
                                Mi VPS — Selenium (normal, 1.2s)
                            </option>
                            <option value="miVpsHtml;3" {{ old('api', $tienda->api) == 'miVpsHtml;3' ? 'selected' : '' }}>
                                Mi VPS — Selenium (WAF duro, 1.8s)
                            </option>
                            <option value="miVpsHtml;4" {{ old('api', $tienda->api) == 'miVpsHtml;4' ? 'selected' : '' }}>
                                Mi VPS — Requests (estático)
                            </option>
                            <option value="miVpsHtml;5" {{ old('api', $tienda->api) == 'miVpsHtml;5' ? 'selected' : '' }}>
                                Mi VPS — Proxies Residenciales (rotativos)
                            </option>

                            <!-- Proveedores externos -->
                            <option value="scrapingAnt" {{ old('api', $tienda->api) == 'scrapingAnt' ? 'selected' : '' }}>
                                ScrapingAnt
                            </option>
                            <option value="brightData;false" {{ old('api', $tienda->api) == 'brightData;false' ? 'selected' : '' }}>
                                Bright Data (sin JavaScript)
                            </option>
                            <option value="brightData;true" {{ old('api', $tienda->api) == 'brightData;true' ? 'selected' : '' }}>
                                Bright Data (con JavaScript)
                            </option>
                            <option value="aliexpressOpen" {{ old('api', $tienda->api) == 'aliexpressOpen' ? 'selected' : '' }}>
                                Api de Aliexpress
                            </option>
                            <option value="amazonApi" {{ old('api', $tienda->api) == 'amazonApi' ? 'selected' : '' }}>
                                Amazon API OFICIAL
                            </option>
                            <option value="amazonProductInfo" {{ old('api', $tienda->api) == 'amazonProductInfo' ? 'selected' : '' }}>
                                Amazon Product Info API - RapidAPI
                            </option>
                            <option value="amazonPricing" {{ old('api', $tienda->api) == 'amazonPricing' ? 'selected' : '' }}>
                                Amazon Pricing And Product Info API - RapidAPI
                            </option>
                        </select>
                        @error('api')
                        <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block mb-1 font-medium text-gray-700 dark:text-gray-200">Mostrar tienda</label>
                        <select name="mostrar_tienda" required
                                class="w-full px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border">
                            <option value="si" {{ old('mostrar_tienda', $tienda->mostrar_tienda ?? 'si') == 'si' ? 'selected' : '' }}>Sí</option>
                            <option value="no" {{ old('mostrar_tienda', $tienda->mostrar_tienda ?? 'si') == 'no' ? 'selected' : '' }}>No</option>
                        </select>
                        <p class="text-xs text-gray-500 mt-1">Si está en "No", las ofertas de esta tienda no aparecerán en el frontend</p>
                    </div>

                    <div>
                        <label class="block mb-1 font-medium text-gray-700 dark:text-gray-200">Scrapear tienda</label>
                        <select name="scrapear" required
                                class="w-full px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border">
                            <option value="si" {{ old('scrapear', $tienda->scrapear ?? 'si') == 'si' ? 'selected' : '' }}>Sí</option>
                            <option value="no" {{ old('scrapear', $tienda->scrapear ?? 'si') == 'no' ? 'selected' : '' }}>No</option>
                        </select>
                        <p class="text-xs text-gray-500 mt-1">Si está en "No", las ofertas de esta tienda no serán actualizadas por el scraper</p>
                    </div>

                    <div>
                        <label class="block mb-1 font-medium text-gray-700 dark:text-gray-200">¿Cómo scrapear?</label>
                        <select name="como_scrapear" required
                                class="w-full px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border">
                            <option value="automatico" {{ old('como_scrapear', $tienda->como_scrapear ?? 'automatico') == 'automatico' ? 'selected' : '' }}>Automático</option>
                            <option value="manual" {{ old('como_scrapear', $tienda->como_scrapear ?? 'automatico') == 'manual' ? 'selected' : '' }}>Manual</option>
                            <option value="ambos" {{ old('como_scrapear', $tienda->como_scrapear ?? 'automatico') == 'ambos' ? 'selected' : '' }}>Ambos</option>
                        </select>
                    </div>
                </div>
            </fieldset>

            {{-- ARBOL DE LAS CATEGORIAS CON SUS RESPECTIVAS COMISIONES--}}
            <script src="//unpkg.com/alpinejs" defer></script>
            <fieldset class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6 space-y-6 border border-gray-200 dark:border-gray-700">
    <legend class="text-lg font-semibold text-gray-700 dark:text-gray-200">Comisiones por categoría</legend>

    <div id="arbol-comisiones" class="space-y-3">
        @php
            $renderCategorias = function($categorias, $comisiones, $nivel = 0, $padreId = null) use (&$renderCategorias) {
                foreach ($categorias as $categoria) {
                    $valor = old("comisiones.{$categoria->id}", $comisiones[$categoria->id] ?? '');
                    $hasChildren = $categoria->children && $categoria->children->count();
                    $margin = $nivel * 4;
        @endphp

        <div class="ml-{{ $margin }}" x-data="{ open{{ $categoria->id }}: false }">
            <div class="flex items-center gap-2">
    @if($hasChildren)
        <button type="button"
            @click="open{{ $categoria->id }} = !open{{ $categoria->id }}"
            class="w-7 h-7 flex items-center justify-center text-white bg-pink-600 hover:bg-pink-700 rounded-full transition"
            :aria-label="open{{ $categoria->id }} ? 'Contraer' : 'Expandir'">
            <span x-text="open{{ $categoria->id }} ? '-' : '+'"></span>
        </button>
    @else
        <span class="w-7 h-7 inline-block"></span>
    @endif

    <label class="flex items-center gap-2 text-sm font-medium text-gray-800 dark:text-gray-200">
        <span>{{ $categoria->nombre }}</span>
        <input
            type="number"
            step="0.01"
            min="0"
            max="100"
            name="comisiones[{{ $categoria->id }}]"
            data-id="{{ $categoria->id }}"
            @if ($padreId)
                data-parent-id="{{ $padreId }}"
            @endif
            class="comision-input px-2 py-1 w-24 bg-gray-100 dark:bg-gray-700 text-white border rounded"
            value="{{ $valor }}"
        >
        <span class="text-sm text-gray-500 dark:text-gray-400">%</span>
    </label>
</div>


            @if ($hasChildren)
                <div class="space-y-2 mt-2 ml-4" x-show="open{{ $categoria->id }}" x-cloak>
                    @php $renderCategorias($categoria->children, $comisiones, $nivel + 1, $categoria->id); @endphp
                </div>
            @endif
        </div>

        @php
                }
            };

            $renderCategorias($categorias, $comisiones);
        @endphp
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
            <div class="md:col-span-2">
                <label class="block mb-1 font-medium text-gray-700 dark:text-gray-200">Anotaciones internas</label>
                <textarea name="anotaciones_internas" rows="4"
                    class="w-full px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border">{{ old('anotaciones_internas', $tienda->anotaciones_internas ?? '') }}</textarea>
            </div>

            {{-- FORZAR ACTUALIZACIÓN DE PRECIOS --}}
            @if($tienda->exists)
            <div class="md:col-span-2">
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6 space-y-4 border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Forzar actualización de precios</h3>
                        <div class="flex items-center gap-2">
                            <input type="number" id="cantidad-ofertas" min="1" placeholder="Todas"
                                class="w-20 px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-pink-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200"
                                title="Deja vacío para actualizar todas las ofertas de esta tienda">
                            <div class="relative group">
                                <svg class="w-5 h-5 text-gray-400 cursor-help" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <div class="absolute bottom-full right-0 mb-2 w-64 p-3 bg-gray-900 text-white text-sm rounded-lg shadow-lg opacity-0 group-hover:opacity-100 transition-opacity duration-200 z-10">
                                    <p>Especifica la cantidad de ofertas que quieres actualizar. Si dejas el campo vacío, se actualizarán todas las ofertas de esta tienda ({{ $tienda->ofertas_count ?? 0 }} ofertas).</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center gap-4">
                        <button type="button" id="btn-forzar-actualizacion" 
                            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-pink-600 hover:bg-pink-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-pink-500 disabled:opacity-50 disabled:cursor-not-allowed">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                            Forzar actualización
                        </button>
                        <span id="estado-actualizacion" class="text-sm text-gray-500 dark:text-gray-400"></span>
                    </div>
                </div>
            </div>
            @endif

            <div class="flex justify-end">
                <button type="submit" id="btn_guardar_tienda"
                    class="inline-flex items-center bg-pink-600 hover:bg-pink-700 text-white font-semibold text-base px-6 py-3 rounded-md shadow-md transition">
                    {{ $tienda->exists ? 'Actualizar tienda' : 'Crear tienda' }}
                </button>
            </div>
        </form>

            {{-- Modal para crear/editar aviso --}}
            <div id="modal-aviso" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
                <div class="bg-gray-800 dark:bg-gray-800 rounded-lg p-6 max-w-md w-full">
                    <h2 id="modal-titulo" class="text-lg font-semibold mb-4 text-gray-200 dark:text-gray-200">Nuevo Aviso</h2>
                    <div id="form-aviso-container">
                        <input type="hidden" id="aviso-id" name="aviso_id">
                        <input type="hidden" id="avisoable-type" name="avisoable_type" value="App\Models\Tienda">
                        <input type="hidden" id="avisoable-id" name="avisoable_id" value="{{ $tienda ? $tienda->id : 'null' }}">
                        
                        <div class="mb-4">
                            <label for="texto-aviso" class="block text-sm font-medium text-gray-300 dark:text-gray-300 mb-2">Texto del aviso</label>
                            <textarea id="texto-aviso" name="texto_aviso" rows="3" 
                                class="w-full px-3 py-2 border border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200" 
                                placeholder="Escribe el texto del aviso..." required></textarea>
                        </div>
                        
                        <div class="mb-4">
                            <label for="fecha-aviso" class="block text-sm font-medium text-gray-300 dark:text-gray-300 mb-2">Fecha y hora</label>
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

            // Cargar avisos al cargar la página
            document.addEventListener('DOMContentLoaded', function() {
                cargarAvisos();
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

            // Cerrar modal al hacer clic fuera
            document.getElementById('modal-aviso').addEventListener('click', function(e) {
                if (e.target === this) {
                    cerrarModalAviso();
                }
            });

                // Cerrar modal con Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            cerrarModalAviso();
        }
    });



            // Prevenir que el formulario del aviso interfiera con el formulario principal
            document.addEventListener('DOMContentLoaded', function() {
                // Asegurar que los botones del modal no envíen el formulario principal
                const modalButtons = document.querySelectorAll('#modal-aviso button[type="button"]');
                modalButtons.forEach(button => {
                    button.addEventListener('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                    });
                });
                
                // Prevenir que el modal envíe el formulario principal
                const modalContainer = document.getElementById('modal-aviso');
                if (modalContainer) {
                    modalContainer.addEventListener('submit', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                    });
                }
            });
            </script>

            {{-- SCRIPT PARA FORZAR ACTUALIZACIÓN DE PRECIOS --}}
            @if($tienda->exists)
            <script>
            document.addEventListener('DOMContentLoaded', function() {
                const btnForzarActualizacion = document.getElementById('btn-forzar-actualizacion');
                const cantidadOfertas = document.getElementById('cantidad-ofertas');
                const estadoActualizacion = document.getElementById('estado-actualizacion');
                const tiendaId = {{ $tienda->id }};

                if (btnForzarActualizacion) {
                    btnForzarActualizacion.addEventListener('click', function() {
                        // Deshabilitar el botón durante la ejecución
                        btnForzarActualizacion.disabled = true;
                        btnForzarActualizacion.innerHTML = `
                            <svg class="animate-spin w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Actualizando...
                        `;
                        estadoActualizacion.textContent = 'Iniciando actualización...';

                        // Preparar datos para la petición
                        const data = {
                            tienda_id: tiendaId,
                            cantidad: cantidadOfertas.value || null
                        };

                        // Realizar la petición
                        fetch('{{ route("admin.ofertas.scraper.ejecutar.tienda") }}?token={{ env("TOKEN_ACTUALIZAR_PRECIOS") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            },
                            body: JSON.stringify(data)
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.status === 'ok') {
                                estadoActualizacion.textContent = `Actualización completada: ${data.actualizadas} actualizadas, ${data.errores} errores de ${data.total_ofertas} ofertas procesadas`;
                                estadoActualizacion.className = 'text-sm text-green-600 dark:text-green-400';
                                
                                // Mostrar notificación de éxito
                                mostrarNotificacion(`Actualización completada: ${data.actualizadas} ofertas actualizadas`, 'success');
                            } else {
                                estadoActualizacion.textContent = `Error: ${data.message}`;
                                estadoActualizacion.className = 'text-sm text-red-600 dark:text-red-400';
                                
                                // Mostrar notificación de error
                                mostrarNotificacion(`Error en la actualización: ${data.message}`, 'error');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            estadoActualizacion.textContent = 'Error de conexión';
                            estadoActualizacion.className = 'text-sm text-red-600 dark:text-red-400';
                            
                            // Mostrar notificación de error
                            mostrarNotificacion('Error de conexión al actualizar precios', 'error');
                        })
                        .finally(() => {
                            // Restaurar el botón
                            btnForzarActualizacion.disabled = false;
                            btnForzarActualizacion.innerHTML = `
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                </svg>
                                Forzar actualización
                            `;
                        });
                    });
                }
            });
            </script>
            @endif

            
    </div>
    <script>
        document.querySelector('form').addEventListener('submit', function(e) {
            const inputs = this.querySelectorAll('[required]');
            let valido = true;

            inputs.forEach(input => {
                if (!input.value.trim()) {
                    valido = false;
                    input.classList.add('border-red-500');
                } else {
                    input.classList.remove('border-red-500');
                }
            });

            if (!valido) {
                e.preventDefault();
                alert('Por favor, completa todos los campos obligatorios.');
            }
        });
    </script>

        {{-- SCRIPT PARA SINCRONIZAR LAS COMISIONES ESCRITAS DE PADRE A HIJO--}}
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const inputs = document.querySelectorAll('.comision-input');

        // Si cambias un padre, cambia también sus hijos
        inputs.forEach(input => {
            input.addEventListener('input', () => {
                const valor = input.value;
                const id = input.dataset.id;

                const hijos = Array.from(inputs).filter(child =>
                    child.dataset.parentId === id
                );

                hijos.forEach(hijo => {
                    hijo.value = valor;
                    hijo.dispatchEvent(new Event('input')); // propaga en cascada
                });
            });
        });
    });
    </script>





</x-app-layout>