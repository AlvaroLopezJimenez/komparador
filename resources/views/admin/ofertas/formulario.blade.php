<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.dashboard') }}">
                <h2 class="font-semibold text-xl text-white leading-tight">Inicio -></h2>
            </a>
            @if($producto)
            <a href="{{ route('admin.productos.index') }}">
                <h2 class="font-semibold text-xl text-white leading-tight">Productos -></h2>
            </a>
            @endif
            <a href="{{ route('admin.ofertas.todas') }}">
                <h2 class="font-semibold text-xl text-white leading-tight">Ofertas -></h2>
            </a>
            <h2 class="font-semibold text-xl text-white leading-tight">
                {{ $oferta ? 'Editar oferta' : 'Añadir oferta' }}
            </h2>
        </div>
    </x-slot>

    <div class="max-w-5xl mx-auto py-10 px-4 space-y-8 bg-gray-50 dark:bg-gray-900 rounded-lg shadow-md form-container">

        <form method="POST"
                            action="{{ $oferta ? route('admin.ofertas.update', $oferta) : route('admin.ofertas.store') }}">
            @csrf
            @if($oferta)
            @method('PUT')
            @endif

            @php
                $cholloSeleccionado = $oferta?->chollo;
                $esOfertaChollo = (bool) old('es_chollo', $cholloSeleccionado ? 1 : 0);
                $cholloNombre = old('chollo_nombre', $cholloSeleccionado?->titulo ?? '');
                $cholloId = old('chollo_id', $cholloSeleccionado?->id ?? '');
                $fechaInicioChollo = old('fecha_inicio', optional($oferta?->fecha_inicio)->format('Y-m-d\TH:i'));
                $fechaFinalChollo = old('fecha_final', optional($oferta?->fecha_final)->format('Y-m-d\TH:i'));
                $fechaComprobadaChollo = old('comprobada', optional($oferta?->comprobada)->format('Y-m-d\TH:i'));

                $frecuenciaCholloValorOld = old('frecuencia_chollo_valor');
                $frecuenciaCholloUnidadOld = old('frecuencia_chollo_unidad');
                $frecuenciaCholloMin = $oferta?->frecuencia_comprobacion_chollos_min ?? 1440;

                if (!$frecuenciaCholloMin || $frecuenciaCholloMin <= 0) {
                    $frecuenciaCholloMin = 1440;
                }

                if ($frecuenciaCholloMin % 1440 === 0) {
                    $frecuenciaCholloValorDefault = $frecuenciaCholloMin / 1440;
                    $frecuenciaCholloUnidadDefault = 'dias';
                } elseif ($frecuenciaCholloMin % 60 === 0) {
                    $frecuenciaCholloValorDefault = $frecuenciaCholloMin / 60;
                    $frecuenciaCholloUnidadDefault = 'horas';
                } else {
                    $frecuenciaCholloValorDefault = $frecuenciaCholloMin;
                    $frecuenciaCholloUnidadDefault = 'minutos';
                }

                $frecuenciaCholloValor = $frecuenciaCholloValorOld ?? $frecuenciaCholloValorDefault;
                $frecuenciaCholloUnidad = $frecuenciaCholloUnidadOld ?? $frecuenciaCholloUnidadDefault;
            @endphp

            {{-- INFORMACIÓN GENERAL --}}
            <fieldset class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6 space-y-6 border border-gray-200 dark:border-gray-700">
                <legend class="text-lg font-semibold text-gray-700 dark:text-gray-200">Información general</legend>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {{-- PRODUCTO --}}
                    <div class="col-span-1 md:col-span-2">
                        <label class="block mb-1 font-medium text-gray-700 dark:text-gray-200">Producto *</label>
                        <div class="relative">
                            <input type="hidden" name="producto_id" id="producto_id" value="{{ old('producto_id', $producto->id ?? '') }}" required>
                            <input type="text" id="producto_nombre"
                                value="{{ old('producto_nombre', isset($producto) ? $producto->nombre . ' - ' . $producto->marca . ' - ' . $producto->modelo . ' - ' . $producto->talla : '') }}"
                                class="w-full px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border {{ isset($producto) ? 'border-green-500' : '' }} focus:outline-none focus:ring-2 focus:ring-blue-500"
                                placeholder="Escribe para buscar productos..."
                                autocomplete="off">
                            <div id="producto_sugerencias" class="absolute z-50 w-full mt-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md shadow-lg hidden max-h-60 overflow-y-auto"></div>
                        </div>
                        @error('producto_id')
                        <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- TIENDA, ENVÍO Y UNIDADES --}}
                    <div>
                        <div class="grid gap-4" style="grid-template-columns: 0.5fr 0.3fr 0.2fr;">
                            {{-- TIENDA --}}
                            <div>
                                <label class="block mb-1 font-medium text-gray-700 dark:text-gray-200">Tienda *</label>
                                <div class="relative">
                                    <input type="hidden" name="tienda_id" id="tienda_id" value="{{ old('tienda_id', $oferta->tienda_id ?? '') }}">
                                    <input type="text" id="tienda_nombre"
                                        value="{{ old('tienda_nombre', isset($oferta) && $oferta->tienda ? $oferta->tienda->nombre : '') }}"
                                        class="w-full px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border {{ isset($oferta) && $oferta->tienda ? 'border-green-500' : '' }} focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        placeholder="Escribe para buscar tiendas..."
                                        autocomplete="off">
                                    <div id="tienda_sugerencias" class="absolute z-50 w-full mt-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md shadow-lg hidden max-h-60 overflow-y-auto"></div>
                                </div>
                                @error('tienda_id')
                                <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- ENVÍO --}}
                            <div>
                                <label class="block mb-1 font-medium text-gray-700 dark:text-gray-200 flex items-center gap-2">
                                    Envío (€)
                                    <div class="relative inline-block group">
                                        <button type="button" class="w-5 h-5 rounded-full bg-blue-500 hover:bg-blue-600 text-white text-xs font-bold flex items-center justify-center cursor-help focus:outline-none focus:ring-2 focus:ring-blue-500" 
                                                onclick="document.getElementById('envio-tooltip').classList.toggle('hidden')"
                                                onblur="setTimeout(() => document.getElementById('envio-tooltip').classList.add('hidden'), 200)">
                                            ?
                                        </button>
                                        <div id="envio-tooltip" class="hidden absolute z-50 w-64 p-3 mt-2 text-sm text-white bg-gray-800 dark:bg-gray-700 rounded-lg shadow-lg left-0 bottom-full mb-2">
                                            <p>Esto es solo para las excepciones. Por ejemplo, Amazon tiene Prime y gastos de envío gratis, entonces este campo se deja vacío ya que es lo que está por defecto, pero si hay un producto que es la excepción que sí tiene gastos de envío, entonces este campo sí se rellenaría.</p>
                                            <div class="absolute left-4 top-full w-0 h-0 border-l-4 border-r-4 border-t-4 border-transparent border-t-gray-800 dark:border-t-gray-700"></div>
                                        </div>
                                    </div>
                                </label>
                                <input type="number" name="envio" id="envio_input" step="0.01" min="0" max="99.99"
                                    value="{{ old('envio', $oferta->envio ?? '') }}"
                                    placeholder="(opcional)"
                                    class="w-full px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <p id="envio_error" class="text-sm text-red-500 mt-1 hidden"></p>
                                @error('envio')
                                <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- UNIDADES --}}
                            <div>
                                <label class="block mb-1 font-medium text-gray-700 dark:text-gray-200">Unidades *</label>
                                <input type="number" name="unidades" min="0.01" step="0.01" required
                                    value="{{ old('unidades', $oferta->unidades ?? '') }}"
                                    class="w-full px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border">
                            </div>
                        </div>
                    </div>

                    {{-- PRECIO TOTAL Y PRECIO POR UNIDAD --}}
                    <div>
                        <div class="grid gap-4" style="grid-template-columns: 0.85fr 1.15fr;">
                            {{-- PRECIO TOTAL --}}
                            <div>
                                <label class="block mb-1 font-medium text-gray-700 dark:text-gray-200">Precio total (€) *</label>
                                <div class="flex gap-2">
                                    <input type="number" name="precio_total" step="0.001" max="9999.999" required
                                        value="{{ old('precio_total', $oferta->precio_total ?? '') }}"
                                        class="flex-1 px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <button type="button" id="btnObtenerPrecio" onclick="obtenerPrecioAutomatico()"
                                        class="px-3 py-2 bg-green-500 hover:bg-green-600 text-white rounded transition disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center"
                                        title="Obtener Precio">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            {{-- PRECIO POR UNIDAD --}}
                            <div>
                                <label class="block mb-1 font-medium text-gray-700 dark:text-gray-200">Precio por unidad (€) *</label>
                                <input type="number" name="precio_unidad" step="0.001" max="9999.999" required
                                    value="{{ old('precio_unidad', $oferta->precio_unidad ?? '') }}"
                                    class="w-full px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border focus:outline-none focus:ring-2 focus:ring-blue-500">
                                @error('precio_unidad')
                                <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    {{-- URL --}}
                    <div class="col-span-1 md:col-span-2">
                        <label class="block mb-1 font-medium text-gray-700 dark:text-gray-200">URL de la oferta *</label>
                        <div class="flex gap-2">
                            <input type="url" name="url" id="url_input" required
                                value="{{ old('url', $oferta->url ?? $url ?? '') }}"
                                class="flex-1 px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border">
                            <button type="button" id="btn_revalidar_url" 
                                class="px-3 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded transition disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center"
                                title="Revalidar URL">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                </svg>
                            </button>
                            <a href="{{ old('url', $oferta->url ?? $url ?? '') }}"
                                target="_blank"
                                id="btn_ir_url"
                                class="px-3 py-2 bg-green-500 hover:bg-green-600 text-white rounded transition flex items-center justify-center"
                                title="Ir a la URL">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </a>
                        </div>
                        <div id="url_validation_message" class="mt-1 text-sm hidden"></div>
                        <div id="url_duplicate_checkbox" class="mt-2 hidden">
                            <label class="inline-flex items-center text-sm text-gray-700 dark:text-gray-200">
                                <input type="checkbox" id="url_duplicate_confirm" class="mr-2 rounded border-gray-300 text-pink-600 focus:ring-pink-500">
                                ¿Es correcto?
                            </label>
                        </div>
                        <div id="url_other_products" class="mt-2 hidden">
                            <div class="text-sm text-blue-600 dark:text-blue-400 mb-2">Esta URL existe en los siguientes productos:</div>
                            <div id="url_products_list" class="text-sm text-gray-600 dark:text-gray-400"></div>
                        </div>
                    </div>

                    {{-- CHOLLO --}}
                    <div class="md:col-span-2">
                        <input type="hidden" name="es_chollo" value="0">
                        <div class="flex items-center gap-3 flex-wrap">
                            <label class="font-medium text-gray-700 dark:text-gray-200 whitespace-nowrap">¿Vincular a un chollo?</label>
                            <label class="flex items-center gap-2">
                                <input type="checkbox" name="es_chollo" id="es_chollo_checkbox" value="1" {{ $esOfertaChollo ? 'checked' : '' }}>
                                <span class="text-gray-700 dark:text-gray-200">Sí, pertenece a un chollo programado</span>
                            </label>
                        </div>

                        {{-- Contenedor para mostrar advertencia de oferta existente --}}
                        <div id="chollo_oferta_existente_aviso" class="mt-4 hidden">
                            <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-300 dark:border-yellow-700 rounded-xl p-4">
                                <div class="flex items-start gap-3">
                                    <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98 1.742 2.98H4.42c1.955 0 2.492-1.646 1.742-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                    </svg>
                                    <div class="flex-1">
                                        <h4 class="text-sm font-semibold text-yellow-800 dark:text-yellow-200 mb-2">
                                            Ya existe otra oferta con chollo vinculado
                                        </h4>
                                        <p class="text-sm text-yellow-700 dark:text-yellow-300 mb-3">
                                            Ya existe otra oferta de este producto y tienda que tiene un chollo vinculado.
                                        </p>
                                        <div id="chollo_oferta_existente_info" class="bg-white dark:bg-gray-800 rounded-lg p-3 border border-yellow-200 dark:border-yellow-600 mb-3">
                                            {{-- La información de la oferta se cargará aquí dinámicamente --}}
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <label class="flex items-center gap-2 cursor-pointer">
                                                <input type="checkbox" id="chollo_confirmar_continuar" class="rounded border-gray-300 text-yellow-600 focus:ring-yellow-500">
                                                <span class="text-sm text-yellow-800 dark:text-yellow-200 font-medium">
                                                    Aún así, quiero vincular esta oferta a un chollo
                                                </span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div id="chollo_fields" class="mt-4 space-y-4 border border-blue-300 dark:border-blue-700 bg-blue-50/70 dark:bg-blue-900/20 rounded-xl p-4 {{ $esOfertaChollo ? '' : 'hidden' }}">
                            <div>
                                <label class="block mb-1 font-medium text-gray-700 dark:text-gray-200">Chollo *</label>
                                <div class="relative">
                                    <input type="hidden" name="chollo_id" id="chollo_id" value="{{ $cholloId }}">
                                    <input type="text"
                                        id="chollo_nombre"
                                        name="chollo_nombre"
                                        value="{{ $cholloNombre }}"
                                        class="w-full px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border {{ $cholloId ? 'border-green-500' : '' }} focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        placeholder="Escribe para buscar chollos..."
                                        autocomplete="off">
                                    <div id="chollo_sugerencias" class="absolute z-50 w-full mt-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md shadow-lg hidden max-h-60 overflow-y-auto"></div>
                                </div>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Se listan chollos activos cuyo inicio ya ha comenzado y, si tienen fecha de fin, aún no han terminado.</p>
                                @error('chollo_id')
                                <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label class="block mb-1 font-medium text-gray-700 dark:text-gray-200">Fecha inicio *</label>
                                    <input type="datetime-local"
                                        id="chollo_fecha_inicio"
                                        name="fecha_inicio"
                                        value="{{ $fechaInicioChollo }}"
                                        class="w-full px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    @error('fecha_inicio')
                                    <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label class="block mb-1 font-medium text-gray-700 dark:text-gray-200">Fecha final</label>
                                    <div class="flex gap-2">
                                        <input type="datetime-local"
                                            id="chollo_fecha_final"
                                            name="fecha_final"
                                            value="{{ $fechaFinalChollo }}"
                                            class="w-full px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        <button type="button"
                                            id="chollo_fecha_final_ahora"
                                            class="px-3 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded transition">
                                            Ahora
                                        </button>
                                    </div>
                                    @error('fecha_final')
                                    <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label class="block mb-1 font-medium text-gray-700 dark:text-gray-200">Comprobada</label>
                                    <div class="flex gap-2">
                                        <input type="datetime-local"
                                            id="chollo_comprobada"
                                            name="comprobada"
                                            value="{{ $fechaComprobadaChollo }}"
                                            class="w-full px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        <button type="button"
                                            id="chollo_comprobada_ahora"
                                            class="px-3 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded transition">
                                            Ahora
                                        </button>
                                    </div>
                                    @error('comprobada')
                                    <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <div>
                                <label class="block mb-1 font-medium text-gray-700 dark:text-gray-200">Frecuencia comprobación (minutos)</label>
                                <div class="flex gap-2">
                                    <input type="number"
                                        step="0.1"
                                        min="0.1"
                                        name="frecuencia_chollo_valor"
                                        value="{{ $frecuenciaCholloValor }}"
                                        class="w-full px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <select name="frecuencia_chollo_unidad"
                                        class="px-3 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        <option value="minutos" {{ $frecuenciaCholloUnidad === 'minutos' ? 'selected' : '' }}>Minutos</option>
                                        <option value="horas" {{ $frecuenciaCholloUnidad === 'horas' ? 'selected' : '' }}>Horas</option>
                                        <option value="dias" {{ $frecuenciaCholloUnidad === 'dias' ? 'selected' : '' }}>Días</option>
                                    </select>
                                </div>
                                @error('frecuencia_chollo_valor')
                                <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    {{-- VARIANTE Y ACTUALIZAR CADA --}}
                    <div>
                        <div class="grid gap-4" style="grid-template-columns: 1.8fr 1fr;">
                            {{-- VARIANTE --}}
                            <div>
                                <div class="flex items-center gap-2 mb-1">
                                    <label class="font-medium text-gray-700 dark:text-gray-200">Variante</label>
                                    <button type="button" onclick="abrirModalVariante()" class="text-blue-500 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 transition-colors">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm0 8a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"></path>
                                        </svg>
                                    </button>
                                </div>
                                <input type="text" name="variante" 
                                    value="{{ old('variante', $oferta->variante ?? '') }}"
                                    placeholder="Número, texto, etc. (opcional)"
                                    class="w-full px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border">
                                @error('variante')
                                <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- ACTUALIZAR CADA --}}
                            @php
                            $frecuencia = $oferta->frecuencia_actualizar_precio_minutos ?? 1440;
                            if ($frecuencia % 1440 === 0) {
                            $frecuencia_valor = $frecuencia / 1440;
                            $frecuencia_unidad = 'dias';
                            } elseif ($frecuencia % 60 === 0) {
                            $frecuencia_valor = $frecuencia / 60;
                            $frecuencia_unidad = 'horas';
                            } else {
                            $frecuencia_valor = $frecuencia;
                            $frecuencia_unidad = 'minutos';
                            }
                            @endphp
                            <div>
                                <label class="block mb-1 font-medium text-gray-700 dark:text-gray-200">Actualizar cada *</label>
                                <div class="flex gap-2">
                                    <input type="number" step="0.1" min="0.1" name="frecuencia_valor" required
                                        value="{{ old('frecuencia_valor', $frecuencia_valor ?? 1) }}"
                                        class="w-20 px-2 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border text-center">
                                    <select name="frecuencia_unidad"
                                        class="px-3 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border">
                                        <option value="minutos" {{ old('frecuencia_unidad', $frecuencia_unidad ?? '') == 'minutos' ? 'selected' : '' }}>Minutos</option>
                                        <option value="horas" {{ old('frecuencia_unidad', $frecuencia_unidad ?? '') == 'horas' ? 'selected' : '' }}>Horas</option>
                                        <option value="dias" {{ old('frecuencia_unidad', $frecuencia_unidad ?? '') == 'dias' ? 'selected' : '' }}>Días</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>


                    {{-- DESCUENTOS --}}
                    <div>
                        <label class="block mb-1 font-medium text-gray-700 dark:text-gray-200">Descuentos</label>
                        @php
                            // Determinar el valor a mostrar en el select
                            $descuentoActual = old('descuentos', $oferta->descuentos ?? '');
                            $descuentoParaSelect = '';
                            $codigoCuponManual = '';
                            $valorCuponManual = '';
                            
                            if ($descuentoActual && strpos($descuentoActual, 'cupon;') === 0) {
                                // Es un cupón, extraer el valor y mostrar como "cupon"
                                $descuentoParaSelect = 'cupon';
                                
                                // Parsear formato: cupon;codigo;cantidad
                                $partes = explode(';', $descuentoActual);
                                if (count($partes) === 3) {
                                    // Formato nuevo: cupon;codigo;cantidad
                                    $codigoCuponManual = $partes[1];
                                    $valorCuponManual = $partes[2];
                                } else {
                                    // Formato antiguo: cupon;cantidad (solo cantidad)
                                    $valorCuponManual = str_replace('cupon;', '', $descuentoActual);
                                }
                            } else {
                                // Es un descuento normal
                                $descuentoParaSelect = $descuentoActual;
                            }
                        @endphp
                        
                        <div class="flex gap-2">
                            <select name="descuentos" id="select_descuentos" class="px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border">
                                <option value="">Sin descuento</option>
                                <option value="cupon" {{ $descuentoParaSelect === 'cupon' ? 'selected' : '' }}>Cupón</option>
                                <option value="3x2" {{ $descuentoParaSelect === '3x2' ? 'selected' : '' }}>3x2</option>
                                <option value="2x1 - SoloCarrefour" {{ $descuentoParaSelect === '2x1 - SoloCarrefour' ? 'selected' : '' }}>2x1 - SoloCarrefour</option>
                                <option value="2a al 70" {{ $descuentoParaSelect === '2a al 70' ? 'selected' : '' }}>2ª al 70%</option>
                                <option value="2a al 50" {{ $descuentoParaSelect === '2a al 50' ? 'selected' : '' }}>2ª al 50%</option>
                                <option value="2a al 50 - cheque - SoloCarrefour" {{ $descuentoParaSelect === '2a al 50 - cheque - SoloCarrefour' ? 'selected' : '' }}>2ª al 50% - Cheque - SoloCarrefour</option>
                            </select>
                            
                            {{-- Campos para cupón (código y cantidad) --}}
                            <div id="cupon_campos_container" class="hidden flex gap-2">
                                <input type="text" 
                                       id="cupon_codigo" 
                                       name="cupon_codigo" 
                                       value="{{ old('cupon_codigo', $codigoCuponManual) }}"
                                       placeholder="Código" 
                                       class="w-48 px-3 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border">
                                <input type="number" 
                                       id="cupon_cantidad" 
                                       name="cupon_cantidad" 
                                       step="0.01" 
                                       min="0" 
                                       value="{{ old('cupon_cantidad', $valorCuponManual) }}"
                                       placeholder="€" 
                                       class="w-20 px-3 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border text-center">
                            </div>
                        </div>
                    
                        @error('descuentos')
                        <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                        @enderror
                        @error('cupon_cantidad')
                        <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    

                    {{-- COMO SCRAPEAR Y FECHA DE ÚLTIMA ACTUALIZACIÓN --}}
                    <div class="col-span-1 md:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-6">
                        {{-- ¿CÓMO SCRAPEAR Y MOSTRAR EN WEB? --}}
                        <div class="grid grid-cols-2 gap-4">
                            {{-- ¿CÓMO SCRAPEAR? --}}
                            <div>
                                <label class="block mb-1 font-medium text-gray-700 dark:text-gray-200">¿Cómo scrapear? *</label>
                                <select name="como_scrapear" id="como_scrapear" required
                                    class="w-full px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="">Selecciona una opción</option>
                                    <option value="automatico" id="opcion_automatico" {{ old('como_scrapear', $oferta->como_scrapear ?? '') === 'automatico' ? 'selected' : '' }}>Automático</option>
                                    <option value="manual" id="opcion_manual" {{ old('como_scrapear', $oferta->como_scrapear ?? '') === 'manual' ? 'selected' : '' }}>Manual</option>
                                </select>
                                @error('como_scrapear')
                                <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- MOSTRAR EN WEB --}}
                            <div class="flex flex-col items-center">
                                <label class="block mb-3 font-medium text-gray-700 dark:text-gray-200 text-center w-full">¿Mostrar en web? *</label>
                                <div class="flex items-center gap-4 justify-center">
                                    <label class="flex items-center">
                                        <input type="radio" name="mostrar" value="si" required
                                            {{ old('mostrar', $oferta->mostrar ?? 'si') === 'si' ? 'checked' : '' }}
                                            class="mr-2">
                                        <span class="text-gray-700 dark:text-gray-200">Sí</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input type="radio" name="mostrar" value="no"
                                            {{ old('mostrar', $oferta->mostrar ?? '') === 'no' ? 'checked' : '' }}
                                            class="mr-2">
                                        <span class="text-gray-700 dark:text-gray-200">No</span>
                                    </label>
                                </div>
                                @error('mostrar')
                                <p class="text-sm text-red-500 mt-1 text-center w-full">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        {{-- FECHA DE ÚLTIMA ACTUALIZACIÓN --}}
                        <div>
                            <label class="block mb-1 font-medium text-gray-700 dark:text-gray-200">
                                {{ $oferta ? 'Última actualización' : 'Fecha de actualización inicial' }}
                            </label>
                            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded-lg p-3">
                                <div class="flex items-center gap-3">
                                    {{-- Icono de reloj --}}
                                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    
                                    {{-- Campo de fecha editable con icono de calendario --}}
                                    <div class="flex-1 relative">
                                        <input type="datetime-local" 
                                               name="fecha_actualizacion_manual" 
                                               id="fecha_actualizacion_manual"
                                               value="{{ old('fecha_actualizacion_manual', $oferta && $oferta->updated_at ? $oferta->updated_at->format('Y-m-d\TH:i') : now()->format('Y-m-d\TH:i')) }}"
                                               class="w-full px-3 py-2 pr-10 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-white border border-blue-300 dark:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm font-medium">
                                    </div>
                                </div>
                                
                                {{-- Información de tiempo transcurrido --}}
                                @if($oferta && $oferta->updated_at)
                                <div class="flex items-center gap-2 mt-2 pl-8">
                                    <svg class="w-4 h-4 text-orange-500 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <span class="text-xs text-orange-700 dark:text-orange-300">
                                        @php
                                            $horasTranscurridas = $oferta->updated_at->diffInHours(now());
                                            if ($horasTranscurridas < 1) {
                                                echo 'Actualizado hace menos de 1 hora';
                                            } elseif ($horasTranscurridas < 24) {
                                                echo 'Actualizado hace ' . $horasTranscurridas . ' hora' . ($horasTranscurridas > 1 ? 's' : '');
                                            } else {
                                                $dias = floor($horasTranscurridas / 24);
                                                $horasRestantes = $horasTranscurridas % 24;
                                                echo 'Actualizado hace ' . $dias . ' día' . ($dias > 1 ? 's' : '') . ($horasRestantes > 0 ? ' y ' . $horasRestantes . ' hora' . ($horasRestantes > 1 ? 's' : '') : '');
                                            }
                                        @endphp
                                    </span>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </fieldset>

            @if($oferta)
                {{-- SISTEMA DE AVISOS (solo al editar) --}}
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
            @endif

            {{-- ESPECIFICACIONES INTERNAS --}}
            <fieldset class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6 space-y-6 border border-gray-200 dark:border-gray-700">
                <legend class="text-lg font-semibold text-gray-700 dark:text-gray-200">Especificaciones internas</legend>
                
                <div id="especificaciones-internas-container" class="space-y-4">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Selecciona un producto para cargar las especificaciones internas del producto.</p>
                </div>
                
                <input type="hidden" name="especificaciones_internas" id="especificaciones_internas_input" value="{{ old('especificaciones_internas', $oferta && $oferta->especificaciones_internas ? json_encode($oferta->especificaciones_internas, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '') }}">
            </fieldset>

            {{-- GESTIÓN DE GRUPOS --}}
            <fieldset class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6 space-y-6 border border-gray-200 dark:border-gray-700">
                <legend class="text-lg font-semibold text-gray-700 dark:text-gray-200">Grupos de ofertas</legend>
                
                <div id="grupos-ofertas-container">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Selecciona un producto con unidad de medida 'Unidad Única' para gestionar grupos de ofertas.</p>
                </div>

                {{-- Contenedor de oferta actual y grupos en fila --}}
                <div id="grupos-lista" class="flex flex-wrap gap-4">
                    {{-- La oferta actual y los grupos se renderizarán aquí --}}
                </div>

                {{-- Listado de ofertas del grupo seleccionado --}}
                <div id="ofertas-grupo-seleccionado" class="hidden mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-200 mb-4">Ofertas del grupo seleccionado</h3>
                    <div id="ofertas-grupo-lista" class="space-y-2">
                        {{-- Las ofertas se cargarán aquí --}}
                    </div>
                </div>

                {{-- Botón para crear nuevo grupo --}}
                <div id="btn-crear-grupo-container" class="hidden mt-6 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <button type="button" id="btn-crear-nuevo-grupo" 
                            class="w-full px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-md transition">
                        + Crear nuevo grupo
                    </button>
                </div>
            </fieldset>

            @if(!$oferta)
                {{-- SISTEMA DE AVISOS (solo al crear) --}}
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
            @endif

                {{-- ANOTACIONES INTERNAS --}}
            <div class="col-span-1 md:col-span-2">
                <label class="block mb-1 font-medium text-gray-700 dark:text-gray-200">Anotaciones internas</label>
                <textarea name="anotaciones_internas" rows="4"
                    class="w-full px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border">{{ old('anotaciones_internas', $oferta->anotaciones_internas ?? '') }}</textarea>
            </div>


            {{-- HISTORIAL DE TIEMPOS DE ACTUALIZACIÓN --}}
            @if($oferta)
            <details class="bg-white dark:bg-gray-800 shadow-sm rounded-xl border border-gray-200 dark:border-gray-700 mt-8">
                <summary class="cursor-pointer px-6 py-4 text-lg font-semibold text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors rounded-t-xl">
                    <div class="flex items-center justify-between">
                        <span>Historial de Actualizaciones de Precios</span>
                        <svg class="w-5 h-5 transform transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </div>
                </summary>
                
                <div class="px-6 pb-6 space-y-4">
                    {{-- Botones de filtro por días --}}
                    <div class="flex gap-3 pt-4">
                        <button type="button" 
                                onclick="cargarHistorialTiemposActualizacion(30)" 
                                id="btn-historial-30"
                                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md transition font-medium">
                            30 días
                        </button>
                        <button type="button" 
                                onclick="cargarHistorialTiemposActualizacion(90)" 
                                id="btn-historial-90"
                                class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-md transition font-medium">
                            90 días
                        </button>
                        <button type="button" 
                                onclick="cargarHistorialTiemposActualizacion(180)" 
                                id="btn-historial-180"
                                class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-md transition font-medium">
                            180 días
                        </button>
                    </div>
                    
                    {{-- Contenedor para la tabla del historial --}}
                    <div id="historial-tiempos-container" class="mt-4">
                        <div class="text-center text-gray-500 dark:text-gray-400 py-8">
                            <p>Selecciona un período para ver el historial</p>
                        </div>
                    </div>
                </div>
            </details>
            @endif

            {{-- GUARDAR --}}
            <div class="sticky bottom-4 flex justify-end z-10 mt-8">
                <button type="submit" id="btn_guardar"
                    class="inline-flex items-center bg-pink-600 hover:bg-pink-700 text-white font-semibold text-base px-6 py-3 rounded-md shadow-lg transition disabled:opacity-50 disabled:cursor-not-allowed">
                    Guardar oferta
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
                        <input type="hidden" id="avisoable-type" name="avisoable_type" value="App\Models\OfertaProducto">
                        <input type="hidden" id="avisoable-id" name="avisoable_id" value="{{ $oferta ? $oferta->id : 'null' }}">
                        
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

            {{-- Modal para información de variantes --}}
            <div id="modal-variante" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
                <div class="bg-white dark:bg-gray-800 rounded-lg p-6 max-w-lg w-full">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Información de Variantes</h2>
                        <button type="button" onclick="cerrarModalVariante()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    
                    <div id="info-tienda-variante" class="text-gray-600 dark:text-gray-300">
                        Selecciona una tienda para ver información específica sobre cómo manejan las variantes.
                    </div>
                    
                    <div class="flex justify-end mt-6">
                        <button type="button" onclick="cerrarModalVariante()" 
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300 dark:bg-gray-600 dark:text-gray-300 dark:hover:bg-gray-500">
                            Cerrar
                        </button>
                    </div>
                </div>
            </div>

            {{-- Modal SOLO LECTURA para ver imágenes de una sublínea (especificaciones internas en ofertas) --}}
            <div id="modal-imagenes-sublinea-oferta" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
                <div class="bg-white dark:bg-gray-900 rounded-lg p-6 max-w-5xl w-full relative shadow-xl max-h-[90vh] flex flex-col">
                    <button type="button" onclick="cerrarModalImagenesSublineaOferta()" class="absolute top-3 right-4 text-xl text-gray-800 dark:text-gray-100 hover:text-gray-600 dark:hover:text-gray-300 z-10">×</button>
                    <div class="mb-4">
                        <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-200">Imágenes de la sublínea</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Haz clic en una miniatura para verla en grande</p>
                    </div>
                    <div class="flex-1 overflow-y-auto">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div class="md:col-span-2">
                                <div class="bg-gray-100 dark:bg-gray-800 rounded-lg p-4 flex items-center justify-center" style="min-height: 400px;">
                                    <img id="imagen-grande-sublinea-oferta" src="" alt="" class="max-w-full max-h-96 object-contain rounded">
                                </div>
                            </div>
                            <div class="md:col-span-1">
                                <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-3">Miniaturas</h4>
                                <div id="miniaturas-container-sublinea-oferta" class="space-y-2 max-h-96 overflow-y-auto">
                                    <p class="text-sm text-gray-500 dark:text-gray-400">No hay imágenes</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="flex justify-end gap-3 mt-4">
                        <button type="button" onclick="cerrarModalImagenesSublineaOferta()"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">
                            Cerrar
                        </button>
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
                    <div class="flex flex-col md:flex-row md:items-center justify-between p-3 bg-gray-700 dark:bg-gray-700 rounded-lg">
                        <div class="flex-1">
                            <p class="text-sm text-gray-200 dark:text-gray-200">${aviso.texto_aviso}</p>
                            <p class="text-xs text-gray-400 dark:text-gray-400 mt-1">
                                ${new Date(aviso.fecha_aviso).toLocaleString('es-ES')} - ${aviso.user.name}
                                ${aviso.oculto ? '<span class="ml-2 px-2 py-1 bg-gray-600 text-gray-200 text-xs rounded">Oculto</span>' : ''}
                            </p>
                        </div>
                        <div class="flex space-x-2 mt-3 md:mt-0 md:ml-4">
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
                    cerrarModalVariante();
                }
            });

            // Cerrar modal de variantes al hacer clic fuera
            document.getElementById('modal-variante').addEventListener('click', function(e) {
                if (e.target === this) {
                    cerrarModalVariante();
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



    <style>
        /* Estilos para el select de tiendas con búsqueda */
        .tienda-select-container {
            position: relative;
        }
        
        /* Estilos para el botón sticky */
        .sticky {
            position: -webkit-sticky;
            position: sticky;
        }
        
        /* Asegurar que el botón tenga un fondo semi-transparente cuando está sticky */
        .sticky button {
            backdrop-filter: blur(8px);
            background-color: rgba(219, 39, 119, 0.95);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .sticky button:hover {
            background-color: rgba(190, 24, 93, 0.95);
            transform: translateY(-1px);
        }
        
        .sticky button:disabled {
            background-color: rgba(156, 163, 175, 0.5);
            transform: none;
        }
        
        /* Añadir un pequeño margen al contenedor principal para evitar que el botón se superponga */
        .form-container {
            padding-bottom: 80px;
        }
        
        /* Asegurar que el botón se mantenga dentro del contenedor del formulario */
        .form-container .sticky {
            max-width: calc(100% - 2rem);
            margin-left: auto;
            margin-right: 0;
        }
        
        /* Responsive para dispositivos móviles */
        @media (max-width: 768px) {
            .form-container .sticky {
                max-width: calc(100% - 1rem);
                bottom: 1rem;
            }
            
            .sticky button {
                padding: 0.75rem 1.5rem;
                font-size: 0.875rem;
            }
        }
        
        /* Efectos visuales cuando el botón está en modo sticky */
        .sticky-active button {
            box-shadow: 0 4px 20px rgba(219, 39, 119, 0.3);
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% {
                box-shadow: 0 4px 20px rgba(219, 39, 119, 0.3);
            }
            50% {
                box-shadow: 0 4px 25px rgba(219, 39, 119, 0.5);
            }
        }
        
        /* Estilos para el checkbox de confirmación */
        #url_duplicate_checkbox {
            transition: all 0.3s ease;
        }
        
        #url_duplicate_checkbox label {
            cursor: pointer;
            user-select: none;
        }
        
        #url_duplicate_confirm:checked + span {
            color: #059669;
            font-weight: 500;
        }
        
        /* Estilos para la lista de productos existentes */
        #url_other_products {
            background-color: rgba(59, 130, 246, 0.05);
            border: 1px solid rgba(59, 130, 246, 0.2);
            border-radius: 0.375rem;
            padding: 0.75rem;
        }
        
        #url_products_list {
            max-height: 120px;
            overflow-y: auto;
        }
        
        #url_products_list div {
            padding: 0.25rem 0;
            border-bottom: 1px solid rgba(156, 163, 175, 0.2);
        }
        
        #url_products_list div:last-child {
            border-bottom: none;
        }
        
        /* Estilos para el tooltip de variantes */
        #tooltipVariante {
            backdrop-filter: blur(8px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
        }
        
        #tooltipVariante:hover {
            opacity: 1 !important;
            visibility: visible !important;
        }
        
        /* Animación de entrada del tooltip */
        @keyframes tooltipFadeIn {
            from {
                opacity: 0;
                transform: translateX(-50%) translateY(5px);
            }
            to {
                opacity: 1;
                transform: translateX(-50%) translateY(0);
            }
        }
        
        .group:hover #tooltipVariante {
            animation: tooltipFadeIn 0.3s ease-out;
        }
        
        /* Estilos para el desplegable de historial */
        details[open] summary svg {
            transform: rotate(180deg);
        }
        
        details summary {
            list-style: none;
        }
        
        details summary::-webkit-details-marker {
            display: none;
        }
        
        details summary::marker {
            display: none;
        }
    </style>

    <script>
                 let timeoutBusqueda = null;
         let timeoutBusquedaTienda = null;
        let timeoutBusquedaChollo = null;
        let productoSeleccionado = false;
        let tiendaSeleccionada = false;
        let cholloSeleccionado = @json($esOfertaChollo);
        let productosActuales = [];
        let tiendasActuales = [];
        let chollosActuales = [];
        let indiceSeleccionado = -1;
        let indiceSeleccionadoTienda = -1;
        let indiceSeleccionadoChollo = -1;

                 // Función para buscar productos en tiempo real
         async function buscarProductos(query) {
             if (query.length < 2) {
                 ocultarSugerencias();
                 return;
             }

             try {
                 const response = await fetch(`{{ route('admin.ofertas.buscar.productos') }}?q=${encodeURIComponent(query)}`);
                 const productos = await response.json();
                 productosActuales = productos;
                 mostrarSugerencias(productos);
             } catch (error) {
                 console.error('Error al buscar productos:', error);
             }
         }

         // Función para buscar tiendas en tiempo real
         async function buscarTiendas(query) {
             console.log('Buscando tiendas con query:', query);
             
             if (query.length < 2) {
                 ocultarSugerenciasTienda();
                 return;
             }

             try {
                 const response = await fetch('{{ route("admin.ofertas.tiendas.disponibles") }}');
                 console.log('Respuesta de tiendas:', response);
                 
                 const todasLasTiendas = await response.json();
                 console.log('Todas las tiendas:', todasLasTiendas);
                 
                 // Filtrar tiendas que coincidan con la consulta
                 const tiendasFiltradas = todasLasTiendas.filter(tienda => 
                     tienda.nombre.toLowerCase().includes(query.toLowerCase())
                 );
                 
                 console.log('Tiendas filtradas:', tiendasFiltradas);
                 
                 tiendasActuales = tiendasFiltradas;
                 mostrarSugerenciasTienda(tiendasFiltradas);
             } catch (error) {
                 console.error('Error al buscar tiendas:', error);
             }
         }

                 // Función para mostrar sugerencias
         function mostrarSugerencias(productos) {
             const contenedor = document.getElementById('producto_sugerencias');
             contenedor.innerHTML = '';
             indiceSeleccionado = -1;

             if (productos.length === 0) {
                 contenedor.innerHTML = '<div class="px-4 py-2 text-gray-500 dark:text-gray-400">No se encontraron productos</div>';
                 contenedor.classList.remove('hidden');
                 return;
             }

             productos.forEach((producto, index) => {
                 const div = document.createElement('div');
                 div.className = 'px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 cursor-pointer text-gray-900 dark:text-white border-b border-gray-200 dark:border-gray-600 last:border-b-0';
                 div.textContent = producto.texto_completo;
                 div.dataset.index = index;
                 
                 div.onclick = () => {
                     seleccionarProducto(producto);
                 };

                 div.onmouseenter = () => {
                     // Remover selección anterior
                     const elementos = contenedor.querySelectorAll('div[data-index]');
                     elementos.forEach(el => el.classList.remove('bg-blue-100', 'dark:bg-blue-700'));
                     
                     // Seleccionar el elemento actual
                     div.classList.add('bg-blue-100', 'dark:bg-blue-700');
                     indiceSeleccionado = index;
                 };

                 contenedor.appendChild(div);
             });

             // Marcar la primera opción por defecto
             if (productos.length > 0) {
                 const primerElemento = contenedor.querySelector('div[data-index="0"]');
                 if (primerElemento) {
                     primerElemento.classList.add('bg-blue-100', 'dark:bg-blue-700');
                     indiceSeleccionado = 0;
                 }
             }

             contenedor.classList.remove('hidden');
         }

         // Función para mostrar sugerencias de tiendas
         function mostrarSugerenciasTienda(tiendas) {
             const contenedor = document.getElementById('tienda_sugerencias');
             contenedor.innerHTML = '';
             indiceSeleccionadoTienda = -1;

             if (tiendas.length === 0) {
                 contenedor.innerHTML = '<div class="px-4 py-2 text-gray-500 dark:text-gray-400">No se encontraron tiendas</div>';
                 contenedor.classList.remove('hidden');
                 return;
             }

             tiendas.forEach((tienda, index) => {
                 const div = document.createElement('div');
                 div.className = 'px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 cursor-pointer text-gray-900 dark:text-white border-b border-gray-200 dark:border-gray-600 last:border-b-0';
                 div.textContent = tienda.nombre;
                 div.dataset.index = index;
                 
                 div.onclick = () => {
                     seleccionarTienda(tienda);
                 };

                 div.onmouseenter = () => {
                     // Remover selección anterior
                     const elementos = contenedor.querySelectorAll('div[data-index]');
                     elementos.forEach(el => el.classList.remove('bg-blue-100', 'dark:bg-blue-700'));
                     
                     // Seleccionar el elemento actual
                     div.classList.add('bg-blue-100', 'dark:bg-blue-700');
                     indiceSeleccionadoTienda = index;
                 };

                 contenedor.appendChild(div);
             });

             // Marcar la primera opción por defecto
             if (tiendas.length > 0) {
                 const primerElemento = contenedor.querySelector('div[data-index="0"]');
                 if (primerElemento) {
                     primerElemento.classList.add('bg-blue-100', 'dark:bg-blue-700');
                     indiceSeleccionadoTienda = 0;
                 }
             }

             contenedor.classList.remove('hidden');
         }

                 // Función para ocultar sugerencias
         function ocultarSugerencias() {
             document.getElementById('producto_sugerencias').classList.add('hidden');
             indiceSeleccionado = -1;
         }

         // Función para ocultar sugerencias de tiendas
         function ocultarSugerenciasTienda() {
             document.getElementById('tienda_sugerencias').classList.add('hidden');
             indiceSeleccionadoTienda = -1;
         }

                 // Función para seleccionar un producto
         async function seleccionarProducto(producto) {
             const productoIdInput = document.getElementById('producto_id');
             const productoNombreInput = document.getElementById('producto_nombre');
             const unidadesInput = document.querySelector('[name="unidades"]');
             
             productoIdInput.value = producto.id;
             productoNombreInput.value = producto.texto_completo;
             productoNombreInput.classList.add('border-green-500');
             productoSeleccionado = true;
             
             ocultarSugerencias();
             
             console.log('✅ Producto seleccionado:', producto.id, producto.texto_completo);
             
             // Obtener información completa del producto para verificar unidad de medida
             try {
                 const response = await fetch(`/panel-privado/productos/${producto.id}`);
                 const productoCompleto = await response.json();
                 
                 // Verificar si el producto tiene unidad de medida única
                 if (productoCompleto && productoCompleto.unidadDeMedida === 'unidadUnica') {
                     // Establecer unidades a 1.00 y hacer el campo de solo lectura
                     if (unidadesInput) {
                         unidadesInput.value = '1.00';
                         unidadesInput.readOnly = true;
                         unidadesInput.classList.add('opacity-50', 'cursor-not-allowed', 'bg-gray-200', 'dark:bg-gray-600');
                         unidadesInput.classList.remove('opacity-100');
                     }
                 } else {
                     // Habilitar el campo si no es unidad única
                     if (unidadesInput) {
                         unidadesInput.readOnly = false;
                         unidadesInput.classList.remove('opacity-50', 'cursor-not-allowed', 'bg-gray-200', 'dark:bg-gray-600');
                         unidadesInput.classList.add('opacity-100');
                         // Si el valor está en 1.00 y no es unidad única, limpiarlo
                         if (unidadesInput.value === '1.00' && !productoCompleto) {
                             unidadesInput.value = '';
                         }
                     }
                 }
             } catch (error) {
                 console.error('Error al obtener información del producto:', error);
                 // En caso de error, habilitar el campo por defecto
                 if (unidadesInput) {
                     unidadesInput.readOnly = false;
                     unidadesInput.classList.remove('opacity-50', 'cursor-not-allowed', 'bg-gray-200', 'dark:bg-gray-600');
                     unidadesInput.classList.add('opacity-100');
                 }
             }
             
             // Cargar especificaciones internas del producto
             cargarEspecificacionesInternas(producto.id);
             
             // Si el checkbox del chollo está marcado, verificar si existe otra oferta con chollo
             const cholloCheckbox = document.getElementById('es_chollo_checkbox');
             if (cholloCheckbox && cholloCheckbox.checked) {
                 verificarOfertaCholloExistente();
             }
         }

         // Función para seleccionar una tienda
         function seleccionarTienda(tienda) {
             const tiendaIdInput = document.getElementById('tienda_id');
             const tiendaNombreInput = document.getElementById('tienda_nombre');
             
             tiendaIdInput.value = tienda.id;
             tiendaNombreInput.value = tienda.nombre;
             tiendaNombreInput.classList.add('border-green-500');
             tiendaSeleccionada = true;
             
             ocultarSugerenciasTienda();
             
             // Actualizar información de variantes si el modal está abierto
             if (!document.getElementById('modal-variante').classList.contains('hidden')) {
                 actualizarInformacionVariantes(tienda.nombre);
             }
             
             console.log('✅ Tienda seleccionada:', tienda.id, tienda.nombre);
             
             // Actualizar campo de envío según envio_gratis y envio_normal de la tienda
             actualizarEnvioSegunTienda(tienda.envio_gratis, tienda.envio_normal);
             
             // Actualizar desplegable de como_scrapear según la tienda
             actualizarComoScrapearSegunTienda(tienda.id);

             // Auto-sugerir "Actualizar cada" según la frecuencia más común de la tienda
             aplicarFrecuenciaMasComunDeTienda(tienda.id);
             
             // Si el checkbox del chollo está marcado, verificar si existe otra oferta con chollo
             const cholloCheckbox = document.getElementById('es_chollo_checkbox');
             if (cholloCheckbox && cholloCheckbox.checked) {
                 verificarOfertaCholloExistente();
             }
         }

         // Función para actualizar el campo de envío según envio_gratis y envio_normal de la tienda
         function actualizarEnvioSegunTienda(envioGratis, envioNormal) {
             const envioInput = document.getElementById('envio_input');
             const envioError = document.getElementById('envio_error');
             
             // Solo actualizar si el campo está vacío (no sobrescribir si ya tiene un valor)
             if (envioInput.value && envioInput.value.trim() !== '') {
                 // Ocultar error si existe
                 if (envioError) {
                     envioError.classList.add('hidden');
                     envioError.textContent = '';
                 }
                 return;
             }
             
             // Ocultar error inicialmente
             if (envioError) {
                 envioError.classList.add('hidden');
                 envioError.textContent = '';
             }
             
             // Buscar primero en envio_gratis, si es null o vacío, buscar en envio_normal
             let envioTexto = null;
             if (envioGratis && envioGratis.trim() !== '') {
                 envioTexto = envioGratis;
             } else if (envioNormal && envioNormal.trim() !== '') {
                 envioTexto = envioNormal;
             }
             
             // Si ambos son null o vacíos, mostrar error
             if (!envioTexto) {
                 envioInput.value = '';
                 envioInput.placeholder = '(opcional)';
                 envioInput.classList.remove('bg-gray-200', 'dark:bg-gray-600');
                 envioInput.classList.add('bg-gray-100', 'dark:bg-gray-700');
                 if (envioError) {
                     envioError.textContent = 'La tienda seleccionada no tiene asignados gastos de envío (ni gratis ni de pago)';
                     envioError.classList.remove('hidden');
                 }
                 return;
             }
             
             const envioTextoLower = envioTexto.toLowerCase();
             
             // Verificar si contiene "gratis" (puede ser "Gratis > 40€", "gratis > 40€", o simplemente "Gratis")
             if (envioTextoLower.includes('gratis')) {
                 // Mostrar "gratis" como placeholder y dejar el campo vacío
                 envioInput.value = '';
                 envioInput.placeholder = 'gratis';
                 envioInput.classList.remove('bg-gray-100', 'dark:bg-gray-700');
                 envioInput.classList.add('bg-gray-200', 'dark:bg-gray-600');
                 return;
             }
             
             // Si no es gratis, intentar extraer el precio
             // Puede ser "2,99 € < 40€", "2€ < 40€", "2,99 €" o "2€"
             const precioMatch = envioTexto.match(/(\d+[,.]?\d*)\s*€?/);
             if (precioMatch) {
                 // Extraer el número y convertir coma a punto
                 let precio = precioMatch[1].replace(',', '.');
                 envioInput.value = precio;
                 envioInput.placeholder = '(opcional)';
                 envioInput.classList.remove('bg-gray-200', 'dark:bg-gray-600');
                 envioInput.classList.add('bg-gray-100', 'dark:bg-gray-700');
             } else {
                 // Si no se puede extraer precio, dejar vacío
                 envioInput.value = '';
                 envioInput.placeholder = '(opcional)';
                 envioInput.classList.remove('bg-gray-200', 'dark:bg-gray-600');
                 envioInput.classList.add('bg-gray-100', 'dark:bg-gray-700');
             }
         }

         // Función para actualizar el desplegable de como_scrapear según la tienda
         async function actualizarComoScrapearSegunTienda(tiendaId) {
             const comoScrapearSelect = document.getElementById('como_scrapear');
             const opcionAutomatico = document.getElementById('opcion_automatico');
             const opcionManual = document.getElementById('opcion_manual');
             
             if (!comoScrapearSelect || !opcionAutomatico || !opcionManual) {
                 return;
             }
             
             const ofertaId = {{ $oferta ? $oferta->id : 'null' }};
             const esNuevaOferta = ofertaId === 'null' || ofertaId === null;
             const valorActual = comoScrapearSelect.value;
             
             try {
                 // Obtener información de la tienda
                 const response = await fetch(`/panel-privado/tiendas/${tiendaId}`);
                 const tienda = await response.json();
                 
                 if (!tienda || !tienda.como_scrapear) {
                     // Si no hay información, dejar vacío
                     comoScrapearSelect.value = '';
                     opcionAutomatico.disabled = false;
                     opcionManual.disabled = false;
                     opcionAutomatico.style.opacity = '1';
                     opcionManual.style.opacity = '1';
                     return;
                 }
                 
                 const comoScrapearTienda = tienda.como_scrapear.toLowerCase();
                 
                 if (comoScrapearTienda === 'automatico') {
                     // Solo permitir automático
                     opcionAutomatico.disabled = false;
                     opcionManual.disabled = true;
                     opcionAutomatico.style.opacity = '1';
                     opcionManual.style.opacity = '0.5';
                     
                     // Si es nueva oferta, establecer automático. Si es edición, verificar que el valor actual sea válido
                     if (esNuevaOferta) {
                         comoScrapearSelect.value = 'automatico';
                     } else {
                         // Si el valor actual no es automático, cambiarlo
                         if (valorActual !== 'automatico') {
                             comoScrapearSelect.value = 'automatico';
                         }
                     }
                 } else if (comoScrapearTienda === 'manual') {
                     // Solo permitir manual
                     opcionAutomatico.disabled = true;
                     opcionManual.disabled = false;
                     opcionAutomatico.style.opacity = '0.5';
                     opcionManual.style.opacity = '1';
                     
                     // Si es nueva oferta, establecer manual. Si es edición, verificar que el valor actual sea válido
                     if (esNuevaOferta) {
                         comoScrapearSelect.value = 'manual';
                     } else {
                         // Si el valor actual no es manual, cambiarlo
                         if (valorActual !== 'manual') {
                             comoScrapearSelect.value = 'manual';
                         }
                     }
                 } else if (comoScrapearTienda === 'ambos') {
                     // Permitir ambos
                     opcionAutomatico.disabled = false;
                     opcionManual.disabled = false;
                     opcionAutomatico.style.opacity = '1';
                     opcionManual.style.opacity = '1';
                     
                     // Si es nueva oferta, establecer automático por defecto. Si es edición, mantener el valor actual si es válido
                     if (esNuevaOferta) {
                         comoScrapearSelect.value = 'automatico';
                     } else {
                         // Si el valor actual no es válido (no es automático ni manual), establecer automático
                         if (valorActual !== 'automatico' && valorActual !== 'manual') {
                             comoScrapearSelect.value = 'automatico';
                         }
                     }
                 } else {
                     // Valor desconocido, dejar vacío
                     comoScrapearSelect.value = '';
                     opcionAutomatico.disabled = false;
                     opcionManual.disabled = false;
                     opcionAutomatico.style.opacity = '1';
                     opcionManual.style.opacity = '1';
                 }
             } catch (error) {
                 console.error('Error al obtener información de la tienda:', error);
                 // En caso de error, dejar vacío
                 comoScrapearSelect.value = '';
                 opcionAutomatico.disabled = false;
                 opcionManual.disabled = false;
                 opcionAutomatico.style.opacity = '1';
                 opcionManual.style.opacity = '1';
             }
         }

         // ===========================
         // AUTO "ACTUALIZAR CADA" POR TIENDA (usa TiendaController@obtenerDesgloseTiempos)
         // ===========================
         let frecuenciaActualizarTouched = false;

         function minutosAValorUnidad(minutos) {
             const m = Number(minutos);
             if (!Number.isFinite(m) || m <= 0) {
                 return { valor: 1, unidad: 'dias' };
             }
             // Preferir días si es exacto, luego horas, si no minutos
             if (Number.isInteger(m) && m % 1440 === 0) {
                 return { valor: m / 1440, unidad: 'dias' };
             }
             if (Number.isInteger(m) && m % 60 === 0) {
                 return { valor: m / 60, unidad: 'horas' };
             }
             return { valor: m, unidad: 'minutos' };
         }

         async function aplicarFrecuenciaMasComunDeTienda(tiendaId, opciones = {}) {
             const inputValor = document.querySelector('[name="frecuencia_valor"]');
             const selectUnidad = document.querySelector('[name="frecuencia_unidad"]');
             if (!inputValor || !selectUnidad) return;

             const ofertaId = {{ $oferta ? $oferta->id : 'null' }};
             const esNuevaOferta = ofertaId === 'null' || ofertaId === null;
             const force = !!opciones.force;

             // En edición, no pisar si el usuario ya tocó el campo
             if (!force && !esNuevaOferta && frecuenciaActualizarTouched) {
                 return;
             }

             if (!tiendaId) return;

             try {
                 const response = await fetch(`/panel-privado/tiendas/${tiendaId}/desglose-tiempos`);
                 const data = await response.json();
                 const desglose = Array.isArray(data?.desglose) ? data.desglose : [];

                 if (desglose.length === 0) {
                     // Sin datos: dejar como está
                     return;
                 }

                 // Elegir la moda (mayor cantidad). En empate, menor minutos.
                 let mejor = null;
                 for (const item of desglose) {
                     const cantidad = Number(item?.cantidad ?? 0);
                     const minutos = Number(item?.minutos ?? 0);
                     if (!Number.isFinite(minutos) || minutos <= 0) continue;

                     if (!mejor) {
                         mejor = { cantidad, minutos };
                         continue;
                     }
                     if (cantidad > mejor.cantidad) {
                         mejor = { cantidad, minutos };
                         continue;
                     }
                     if (cantidad === mejor.cantidad && minutos < mejor.minutos) {
                         mejor = { cantidad, minutos };
                     }
                 }

                 if (!mejor) return;

                 const convertido = minutosAValorUnidad(mejor.minutos);
                 inputValor.value = convertido.valor;
                 selectUnidad.value = convertido.unidad;
             } catch (e) {
                 console.error('Error al obtener desglose de tiempos de tienda:', e);
             }
         }

        // Función para navegar con teclado
        function navegarSugerencias(direccion) {
            const contenedor = document.getElementById('producto_sugerencias');
            const elementos = contenedor.querySelectorAll('div[data-index]');
            
            if (elementos.length === 0) return;
            
            // Remover selección anterior
            elementos.forEach(el => el.classList.remove('bg-blue-100', 'dark:bg-blue-700'));
            
            if (direccion === 'arriba') {
                indiceSeleccionado = indiceSeleccionado <= 0 ? elementos.length - 1 : indiceSeleccionado - 1;
            } else if (direccion === 'abajo') {
                indiceSeleccionado = indiceSeleccionado >= elementos.length - 1 ? 0 : indiceSeleccionado + 1;
            }
            
            // Aplicar selección
            if (indiceSeleccionado >= 0 && indiceSeleccionado < elementos.length) {
                elementos[indiceSeleccionado].classList.add('bg-blue-100', 'dark:bg-blue-700');
                elementos[indiceSeleccionado].scrollIntoView({ block: 'nearest' });
            }
        }

                 // Función para seleccionar el producto actualmente resaltado
         function seleccionarProductoResaltado() {
             if (indiceSeleccionado >= 0 && indiceSeleccionado < productosActuales.length) {
                 seleccionarProducto(productosActuales[indiceSeleccionado]);
             }
         }

         // Función para navegar con teclado en tiendas
         function navegarSugerenciasTienda(direccion) {
             const contenedor = document.getElementById('tienda_sugerencias');
             const elementos = contenedor.querySelectorAll('div[data-index]');
             
             if (elementos.length === 0) return;
             
             // Remover selección anterior
             elementos.forEach(el => el.classList.remove('bg-blue-100', 'dark:bg-blue-700'));
             
             if (direccion === 'arriba') {
                 indiceSeleccionadoTienda = indiceSeleccionadoTienda <= 0 ? elementos.length - 1 : indiceSeleccionadoTienda - 1;
             } else if (direccion === 'abajo') {
                 indiceSeleccionadoTienda = indiceSeleccionadoTienda >= elementos.length - 1 ? 0 : indiceSeleccionadoTienda + 1;
             }
             
             // Aplicar selección
             if (indiceSeleccionadoTienda >= 0 && indiceSeleccionadoTienda < elementos.length) {
                 elementos[indiceSeleccionadoTienda].classList.add('bg-blue-100', 'dark:bg-blue-700');
                 elementos[indiceSeleccionadoTienda].scrollIntoView({ block: 'nearest' });
             }
         }

         // Función para seleccionar la tienda actualmente resaltada
         function seleccionarTiendaResaltada() {
             if (indiceSeleccionadoTienda >= 0 && indiceSeleccionadoTienda < tiendasActuales.length) {
                 seleccionarTienda(tiendasActuales[indiceSeleccionadoTienda]);
             }
         }

        // Función para buscar chollos en tiempo real
        async function buscarChollos(query) {
            if (query.length < 2) {
                ocultarSugerenciasChollo();
                return;
            }

            try {
                const response = await fetch(`{{ route('admin.ofertas.buscar.chollos') }}?q=${encodeURIComponent(query)}`);
                const chollos = await response.json();
                chollosActuales = chollos;
                mostrarSugerenciasChollo(chollos);
            } catch (error) {
                console.error('Error al buscar chollos:', error);
            }
        }

        function mostrarSugerenciasChollo(chollos) {
            const contenedor = document.getElementById('chollo_sugerencias');
            contenedor.innerHTML = '';
            indiceSeleccionadoChollo = -1;

            if (chollos.length === 0) {
                contenedor.innerHTML = '<div class="px-4 py-2 text-gray-500 dark:text-gray-400">No se encontraron chollos</div>';
                contenedor.classList.remove('hidden');
                return;
            }

            chollos.forEach((chollo, index) => {
                const div = document.createElement('div');
                div.className = 'px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 cursor-pointer text-gray-900 dark:text-white border-b border-gray-200 dark:border-gray-600 last:border-b-0';
                const detalles = [
                    chollo.titulo,
                    chollo.tienda ? `(${chollo.tienda})` : '',
                    chollo.fecha_inicio ? `Inicio: ${chollo.fecha_inicio}` : ''
                ].filter(Boolean).join(' ');
                div.textContent = detalles;
                div.dataset.index = index;

                div.onclick = () => seleccionarChollo(chollo);

                div.onmouseenter = () => {
                    const elementos = contenedor.querySelectorAll('div[data-index]');
                    elementos.forEach(el => el.classList.remove('bg-blue-100', 'dark:bg-blue-700'));
                    div.classList.add('bg-blue-100', 'dark:bg-blue-700');
                    indiceSeleccionadoChollo = index;
                };

                contenedor.appendChild(div);
            });

            const primerElemento = contenedor.querySelector('div[data-index="0"]');
            if (primerElemento) {
                primerElemento.classList.add('bg-blue-100', 'dark:bg-blue-700');
                indiceSeleccionadoChollo = 0;
            }

            contenedor.classList.remove('hidden');
        }

        function ocultarSugerenciasChollo() {
            document.getElementById('chollo_sugerencias').classList.add('hidden');
            indiceSeleccionadoChollo = -1;
        }

        function seleccionarChollo(chollo) {
            const cholloIdInput = document.getElementById('chollo_id');
            const cholloNombreInput = document.getElementById('chollo_nombre');
            const fechaInicioInput = document.getElementById('chollo_fecha_inicio');
            const fechaFinalInput = document.getElementById('chollo_fecha_final');

            cholloIdInput.value = chollo.id;
            cholloNombreInput.value = chollo.titulo;
            cholloNombreInput.classList.add('border-green-500');
            cholloSeleccionado = true;

            if (chollo.fecha_inicio) {
                fechaInicioInput.value = chollo.fecha_inicio.replace(' ', 'T');
            }
            if (chollo.fecha_final) {
                fechaFinalInput.value = chollo.fecha_final.replace(' ', 'T');
            }

            ocultarSugerenciasChollo();
        }

        function navegarSugerenciasChollo(direccion) {
            const contenedor = document.getElementById('chollo_sugerencias');
            const elementos = contenedor.querySelectorAll('div[data-index]');

            if (elementos.length === 0) return;

            elementos.forEach(el => el.classList.remove('bg-blue-100', 'dark:bg-blue-700'));

            if (direccion === 'arriba') {
                indiceSeleccionadoChollo = indiceSeleccionadoChollo <= 0 ? elementos.length - 1 : indiceSeleccionadoChollo - 1;
            } else if (direccion === 'abajo') {
                indiceSeleccionadoChollo = indiceSeleccionadoChollo >= elementos.length - 1 ? 0 : indiceSeleccionadoChollo + 1;
            }

            if (indiceSeleccionadoChollo >= 0 && indiceSeleccionadoChollo < elementos.length) {
                elementos[indiceSeleccionadoChollo].classList.add('bg-blue-100', 'dark:bg-blue-700');
                elementos[indiceSeleccionadoChollo].scrollIntoView({ block: 'nearest' });
            }
        }

        function seleccionarCholloResaltado() {
            if (indiceSeleccionadoChollo >= 0 && indiceSeleccionadoChollo < chollosActuales.length) {
                seleccionarChollo(chollosActuales[indiceSeleccionadoChollo]);
            }
        }

        // Calcular precio por unidad automáticamente usando el servicio
        async function actualizarPrecioUnidad() {
            const productoId = document.getElementById('producto_id').value;
            const unidades = parseFloat(document.querySelector('[name="unidades"]').value);
            const precioTotal = parseFloat(document.querySelector('[name="precio_total"]').value);
            const precioUnidadInput = document.querySelector('[name="precio_unidad"]');

            // Validar que tenemos todos los datos necesarios
            if (!productoId) {
                // Si no hay producto seleccionado, usar cálculo simple como fallback
                if (!isNaN(unidades) && unidades > 0 && !isNaN(precioTotal)) {
                    precioUnidadInput.value = (precioTotal / unidades).toFixed(2);
                }
                return;
            }

            if (isNaN(unidades) || unidades <= 0 || isNaN(precioTotal) || precioTotal < 0) {
                return;
            }

            try {
                const response = await fetch('{{ route("admin.ofertas.calcular.precio-unidad") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        producto_id: productoId,
                        precio_total: precioTotal,
                        unidades: unidades
                    })
                });

                const data = await response.json();

                if (data.success) {
                    // El servicio ya devuelve el precio redondeado según la unidad de medida
                    precioUnidadInput.value = data.precio_unidad;
                } else {
                    // Si hay error, usar cálculo simple como fallback
                    precioUnidadInput.value = (precioTotal / unidades).toFixed(2);
                }
            } catch (error) {
                console.error('Error al calcular precio por unidad:', error);
                // En caso de error, usar cálculo simple como fallback
                precioUnidadInput.value = (precioTotal / unidades).toFixed(2);
            }
        }

        // Eventos
        document.querySelector('[name="unidades"]').addEventListener('input', actualizarPrecioUnidad);
        document.querySelector('[name="precio_total"]').addEventListener('input', actualizarPrecioUnidad);
        
        // También actualizar cuando se seleccione un producto
        document.getElementById('producto_id').addEventListener('change', function() {
            // Si ya hay unidades y precio total, recalcular
            const unidades = parseFloat(document.querySelector('[name="unidades"]').value);
            const precioTotal = parseFloat(document.querySelector('[name="precio_total"]').value);
            if (unidades > 0 && precioTotal > 0) {
                actualizarPrecioUnidad();
            }
        });

        // Restaurar placeholder y fondo del campo de envío cuando el usuario interactúe
        const envioInput = document.getElementById('envio_input');
        if (envioInput) {
            envioInput.addEventListener('focus', function() {
                if (this.placeholder === 'gratis') {
                    this.placeholder = '(opcional)';
                    this.classList.remove('bg-gray-200', 'dark:bg-gray-600');
                    this.classList.add('bg-gray-100', 'dark:bg-gray-700');
                }
            });
            
            envioInput.addEventListener('input', function() {
                if (this.placeholder === 'gratis' && this.value && this.value.trim() !== '') {
                    this.placeholder = '(opcional)';
                    this.classList.remove('bg-gray-200', 'dark:bg-gray-600');
                    this.classList.add('bg-gray-100', 'dark:bg-gray-700');
                }
            });
        }

        // Manejar cambios en el select de descuentos para mostrar/ocultar campos de cupón
        const selectDescuentos = document.querySelector('[name="descuentos"]');
        const cuponCamposContainer = document.getElementById('cupon_campos_container');
        const cuponCodigoInput = document.getElementById('cupon_codigo');
        const cuponCantidadInput = document.getElementById('cupon_cantidad');
        
        // Función para mostrar/ocultar los campos del cupón
        function toggleCuponCampos() {
            const valorSeleccionado = selectDescuentos.value;
            
            if (valorSeleccionado === 'cupon') {
                cuponCamposContainer.classList.remove('hidden');
                cuponCodigoInput.required = true;
                cuponCantidadInput.required = true;
                // Reducir el select a un ancho máximo pequeño
                selectDescuentos.style.flex = '0 1 auto';
                selectDescuentos.style.maxWidth = '150px';
                selectDescuentos.style.minWidth = '120px';
            } else {
                cuponCamposContainer.classList.add('hidden');
                cuponCodigoInput.required = false;
                cuponCantidadInput.required = false;
                cuponCodigoInput.value = '';
                cuponCantidadInput.value = '';
                // Restaurar el select a tamaño completo
                selectDescuentos.style.flex = '1 1 0%';
                selectDescuentos.style.maxWidth = 'none';
                selectDescuentos.style.minWidth = 'auto';
            }
        }
        
        // Event listener para cambios en el select
        selectDescuentos.addEventListener('change', function() {
            toggleCuponCampos();
        });
        
        // Mostrar/ocultar campos al cargar la página
        document.addEventListener('DOMContentLoaded', function() {
            // Verificar el valor del select y mostrar campos si es necesario
            const valorSelect = selectDescuentos.value;
            if (valorSelect === 'cupon') {
                cuponCamposContainer.classList.remove('hidden');
                cuponCodigoInput.required = true;
                cuponCantidadInput.required = true;
            }
            toggleCuponCampos();
        });

        // Interceptar el envío del formulario para construir el valor del cupón
        document.querySelector('form').addEventListener('submit', function(e) {
            // Limpiar el campo de envío si está vacío con placeholder "gratis" (no enviar valor)
            const envioInput = document.getElementById('envio_input');
            if (envioInput && (!envioInput.value || envioInput.value.trim() === '') && envioInput.placeholder === 'gratis') {
                envioInput.value = '';
            }
            
            const valorSeleccionado = selectDescuentos.value;
            const codigoCupon = cuponCodigoInput.value.trim();
            const cantidadCupon = cuponCantidadInput.value;
            
            if (valorSeleccionado === 'cupon') {
                if (!codigoCupon) {
                    e.preventDefault();
                    alert('Por favor, introduce el código del cupón');
                    cuponCodigoInput.focus();
                    return false;
                }
                
                if (!cantidadCupon || cantidadCupon <= 0) {
                    e.preventDefault();
                    alert('Por favor, introduce una cantidad válida para el cupón');
                    cuponCantidadInput.focus();
                    return false;
                }
                
                // Construir el valor del cupón con formato cupon;codigo;cantidad
                selectDescuentos.value = 'cupon;' + codigoCupon + ';' + cantidadCupon;
            }
        });

        // Función para obtener precio automáticamente
        async function obtenerPrecioAutomatico() {
            const btnObtenerPrecio = document.getElementById('btnObtenerPrecio');
            const urlInput = document.querySelector('[name="url"]');
            const tiendaSelect = document.getElementById('tienda_nombre');
            const varianteInput = document.querySelector('[name="variante"]');
            const unidadesInput = document.querySelector('[name="unidades"]');
            const precioTotalInput = document.querySelector('[name="precio_total"]');
            const precioUnidadInput = document.querySelector('[name="precio_unidad"]');

            // Validar que todos los campos necesarios estén completos
            if (!urlInput.value.trim()) {
                alert('Por favor, introduce la URL de la oferta');
                urlInput.focus();
                return;
            }

            if (!tiendaSelect.value) {
                alert('Por favor, selecciona una tienda');
                tiendaSelect.focus();
                return;
            }

            if (!unidadesInput.value || unidadesInput.value <= 0) {
                alert('Por favor, introduce el número de unidades');
                unidadesInput.focus();
                return;
            }

            // Deshabilitar botón y mostrar estado de carga
            btnObtenerPrecio.disabled = true;
            btnObtenerPrecio.innerHTML = '<svg class="w-5 h-5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>';

            try {
                             // Obtener el nombre de la tienda seleccionada
             const tiendaNombre = tiendaSelect.value;
                
                // Preparar datos para la petición
                const datos = {
                    url: urlInput.value.trim(),
                    tienda: tiendaNombre,
                    variante: varianteInput.value.trim() || null
                };

                // Hacer petición al scraper
                const response = await fetch('{{ route("admin.ofertas.scraper.obtener-precio") }}', {
                    method: 'POST',
                                    headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                    body: JSON.stringify(datos)
                });

                const resultado = await response.json();

                if (resultado.success) {
                    // Actualizar precio total
                    precioTotalInput.value = resultado.precio;
                    
                    // Calcular precio por unidad usando el servicio
                    const productoId = document.getElementById('producto_id').value;
                    const unidades = parseFloat(unidadesInput.value);
                    const precioTotal = parseFloat(resultado.precio);
                    
                    if (productoId && unidades > 0) {
                        try {
                            const response = await fetch('{{ route("admin.ofertas.calcular.precio-unidad") }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                                },
                                body: JSON.stringify({
                                    producto_id: productoId,
                                    precio_total: precioTotal,
                                    unidades: unidades
                                })
                            });

                            const data = await response.json();
                            if (data.success) {
                                precioUnidadInput.value = data.precio_unidad;
                            } else {
                                // Fallback a cálculo simple
                                precioUnidadInput.value = (precioTotal / unidades).toFixed(4);
                            }
                        } catch (error) {
                            console.error('Error al calcular precio por unidad:', error);
                            // Fallback a cálculo simple
                            precioUnidadInput.value = (precioTotal / unidades).toFixed(4);
                        }
                    } else {
                        // Si no hay producto seleccionado, usar cálculo simple
                        precioUnidadInput.value = (precioTotal / unidades).toFixed(4);
                    }

                    // Mostrar mensaje de éxito
                    mostrarNotificacion('✅ Precio obtenido correctamente: ' + resultado.precio + '€', 'success');
                } else {
                    // Mostrar error
                    mostrarNotificacion('❌ Error al obtener precio: ' + resultado.error, 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                mostrarNotificacion('❌ Error de conexión al obtener precio', 'error');
            } finally {
                // Restaurar botón
                btnObtenerPrecio.disabled = false;
                btnObtenerPrecio.innerHTML = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>';
            }
        }

        // Función para abrir modal de variantes
        function abrirModalVariante() {
            document.getElementById('modal-variante').classList.remove('hidden');
            
            // Si hay una tienda seleccionada, actualizar información específica
            const tiendaSeleccionada = document.getElementById('tienda_nombre').value;
            if (tiendaSeleccionada) {
                actualizarInformacionVariantes(tiendaSeleccionada);
            }
        }

        // Función para cerrar modal de variantes
        function cerrarModalVariante() {
            document.getElementById('modal-variante').classList.add('hidden');
        }

        // Función para actualizar información de variantes
        async function actualizarInformacionVariantes(nombreTienda) {
            try {
                const response = await fetch(`{{ route('admin.ofertas.variantes.tienda', '') }}/${encodeURIComponent(nombreTienda)}`);
                const data = await response.json();
                
                if (data.success) {
                    const infoTiendaVariante = document.getElementById('info-tienda-variante');
                    const info = data.data;
                    
                    infoTiendaVariante.innerHTML = `
                        <div class="font-semibold text-blue-600 dark:text-blue-400 mb-2">${info.nombre}</div>
                        <p class="mb-2">${info.descripcion}</p>
                        <div class="text-sm">
                            <strong>Ejemplo:</strong> <span class="text-gray-600 dark:text-gray-400">${info.ejemplo}</span>
                        </div>
                        ${info.requerida ? '<div class="text-yellow-600 dark:text-yellow-400 text-sm mt-2"><strong>⚠️ Requerida</strong></div>' : ''}
                    `;
                } else {
                    document.getElementById('info-tienda-variante').innerHTML = `
                        <p class="text-gray-500 dark:text-gray-400">No hay información específica disponible para esta tienda.</p>
                    `;
                }
            } catch (error) {
                console.error('Error al obtener información de variantes:', error);
                document.getElementById('info-tienda-variante').innerHTML = `
                    <p class="text-red-500 dark:text-red-400">Error al cargar información de la tienda.</p>
                `;
            }
        }

        // Función para mostrar notificaciones
        function mostrarNotificacion(mensaje, tipo = 'info') {
            // Crear elemento de notificación
            const notificacion = document.createElement('div');
            notificacion.className = `fixed top-4 right-4 p-4 rounded-lg shadow-lg z-50 max-w-sm ${
                tipo === 'success' ? 'bg-green-500 text-white' :
                tipo === 'error' ? 'bg-red-500 text-white' :
                'bg-blue-500 text-white'
            }`;
            notificacion.textContent = mensaje;

            // Añadir al DOM
            document.body.appendChild(notificacion);

            // Eliminar después de 5 segundos
            setTimeout(() => {
                if (notificacion.parentNode) {
                    notificacion.parentNode.removeChild(notificacion);
                }
            }, 5000);
        }

        // Función para solicitar actualización de precio en segundo plano
        function solicitarActualizacionPrecio(productoId) {
            // Construir la URL con el token del .env
            const url = '{{ url("/panel-privado/productos/precio-bajo/ejecutar-segundo-plano?token=" . env("TOKEN_ACTUALIZAR_PRECIOS")) }}';
            
            // Hacer la petición de forma asíncrona sin bloquear la interfaz
            fetch(url, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => {
                // No hacer nada con la respuesta, solo ejecutar en segundo plano
                console.log('Actualización de precio solicitada en segundo plano');
            })
            .catch(error => {
                // Silenciar errores para que no afecten la experiencia del usuario
                console.log('Error en actualización de precio (ignorado):', error);
            });
        }

                 // Event listeners para el campo de producto
        document.addEventListener('DOMContentLoaded', function() {
            const productoInput = document.getElementById('producto_nombre');
            const tiendaInput = document.getElementById('tienda_nombre');
            const cholloInput = document.getElementById('chollo_nombre');
            const cholloIdInput = document.getElementById('chollo_id');
            const cholloCheckbox = document.getElementById('es_chollo_checkbox');
            const cholloFields = document.getElementById('chollo_fields');
            const fechaInicioCholloInput = document.getElementById('chollo_fecha_inicio');
            
            if (!productoInput || !tiendaInput) {
                return;
            }

            if (cholloCheckbox && fechaInicioCholloInput) {
                fechaInicioCholloInput.required = cholloCheckbox.checked;
            }

            if (cholloInput && cholloIdInput && cholloIdInput.value) {
                cholloInput.classList.add('border-green-500');
                cholloSeleccionado = true;
            }
            
            if (tiendaSeleccionada) {
                actualizarInformacionVariantes(tiendaSeleccionada);
            }

            // Si hay una tienda seleccionada al cargar (editando oferta), actualizar como_scrapear
            const tiendaIdAlCargar = document.getElementById('tienda_id')?.value;
            if (tiendaIdAlCargar) {
                actualizarComoScrapearSegunTienda(tiendaIdAlCargar);
            }

            // Marcar como "tocado" si el usuario cambia manualmente el campo de frecuencia
            const inputFrecuenciaValor = document.querySelector('[name="frecuencia_valor"]');
            const selectFrecuenciaUnidad = document.querySelector('[name="frecuencia_unidad"]');
            if (inputFrecuenciaValor) {
                inputFrecuenciaValor.addEventListener('input', () => { frecuenciaActualizarTouched = true; });
                inputFrecuenciaValor.addEventListener('change', () => { frecuenciaActualizarTouched = true; });
            }
            if (selectFrecuenciaUnidad) {
                selectFrecuenciaUnidad.addEventListener('change', () => { frecuenciaActualizarTouched = true; });
            }

            // Si estamos creando oferta nueva y ya hay tienda seleccionada al cargar, sugerir frecuencia
            const ofertaIdCarga = {{ $oferta ? $oferta->id : 'null' }};
            const esNuevaOfertaCarga = ofertaIdCarga === 'null' || ofertaIdCarga === null;
            if (esNuevaOfertaCarga && tiendaIdAlCargar) {
                aplicarFrecuenciaMasComunDeTienda(tiendaIdAlCargar, { force: true });
            }

            const actualizarEstadoChollo = (activo) => {
                if (!cholloFields) {
                    return;
                }
                if (activo) {
                    cholloFields.classList.remove('hidden');
                } else {
                    cholloFields.classList.add('hidden');
                    ocultarSugerenciasChollo();
                }
                if (fechaInicioCholloInput) {
                    fechaInicioCholloInput.required = !!activo;
                }
            };

            // Función para mostrar el aviso de oferta existente
            function mostrarAvisoOfertaExistente(oferta) {
                const avisoContainer = document.getElementById('chollo_oferta_existente_aviso');
                const infoContainer = document.getElementById('chollo_oferta_existente_info');

                if (!avisoContainer || !infoContainer) {
                    return;
                }

                // Construir HTML con la información de la oferta
                let html = `
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">Oferta #${oferta.id}</p>
                                ${oferta.chollo ? `<p class="text-xs text-gray-600 dark:text-gray-400">Chollo: ${oferta.chollo.titulo}</p>` : ''}
                                ${oferta.precio_unidad ? `<p class="text-xs text-gray-600 dark:text-gray-400">Precio: ${oferta.precio_unidad}€</p>` : ''}
                            </div>
                            <div class="flex gap-2">
                                <a href="/panel-privado/ofertas/${oferta.id}/edit" target="_blank" 
                                   class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs rounded transition">
                                    Editar
                                </a>
                                ${oferta.url ? `
                                    <a href="${oferta.url}" target="_blank" 
                                       class="px-3 py-1.5 bg-green-600 hover:bg-green-700 text-white text-xs rounded transition">
                                        Ver URL
                                    </a>
                                ` : ''}
                            </div>
                        </div>
                    </div>
                `;

                infoContainer.innerHTML = html;
                avisoContainer.classList.remove('hidden');
            }

            // Función para ocultar el aviso de oferta existente
            function ocultarAvisoOfertaExistente() {
                const avisoContainer = document.getElementById('chollo_oferta_existente_aviso');
                const confirmarCheckbox = document.getElementById('chollo_confirmar_continuar');
                if (avisoContainer) {
                    avisoContainer.classList.add('hidden');
                }
                // Resetear el checkbox de confirmación
                if (confirmarCheckbox) {
                    confirmarCheckbox.checked = false;
                }
            }

            // Función para verificar si existe una oferta con chollo para el mismo producto y tienda
            async function verificarOfertaCholloExistente() {
                const productoId = document.getElementById('producto_id')?.value;
                const tiendaId = document.getElementById('tienda_id')?.value;
                const ofertaId = {{ $oferta ? $oferta->id : 'null' }};

                if (!productoId || !tiendaId) {
                    // Si no hay producto o tienda seleccionados, mostrar campos directamente
                    ocultarAvisoOfertaExistente();
                    actualizarEstadoChollo(true);
                    return;
                }

                try {
                    const url = '{{ route("admin.ofertas.verificar.chollo-existente") }}';
                    
                    const response = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({
                            producto_id: productoId,
                            tienda_id: tiendaId,
                            oferta_id: ofertaId !== 'null' ? ofertaId : null
                        })
                    });

                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }

                    const data = await response.json();

                    // Verificar si la oferta actual ya tiene chollo vinculado
                    const cholloIdActual = document.getElementById('chollo_id')?.value;

                    if (data.existe && data.oferta) {
                        // Mostrar advertencia con información de la oferta existente
                        mostrarAvisoOfertaExistente(data.oferta);
                        
                        // Si la oferta actual ya tiene chollo, mostrar campos directamente
                        if (cholloIdActual) {
                            actualizarEstadoChollo(true);
                            // Marcar el checkbox de confirmación como checked ya que ya está vinculado
                            const confirmarCheckbox = document.getElementById('chollo_confirmar_continuar');
                            if (confirmarCheckbox) {
                                confirmarCheckbox.checked = true;
                            }
                        } else {
                            // NO mostrar campos del chollo todavía, esperar confirmación del usuario
                            actualizarEstadoChollo(false);
                        }
                    } else {
                        // No existe oferta con chollo, mostrar campos directamente
                        ocultarAvisoOfertaExistente();
                        actualizarEstadoChollo(true);
                    }
                } catch (error) {
                    console.error('Error al verificar oferta con chollo:', error);
                    // En caso de error, mostrar campos directamente
                    ocultarAvisoOfertaExistente();
                    actualizarEstadoChollo(true);
                }
            }

            if (cholloCheckbox) {
                // Si el checkbox ya está marcado al cargar (editando oferta con chollo)
                if (cholloCheckbox.checked) {
                    const cholloId = document.getElementById('chollo_id')?.value;
                    // Si la oferta actual ya tiene chollo, mostrar campos directamente
                    if (cholloId) {
                        actualizarEstadoChollo(true);
                        // Aún así verificar si existe otra oferta para mostrar el aviso
                        verificarOfertaCholloExistente();
                    } else {
                        // Si está marcado pero no tiene chollo, verificar
                        verificarOfertaCholloExistente();
                    }
                } else {
                    actualizarEstadoChollo(false);
                }
                
                cholloCheckbox.addEventListener('change', async function() {
                    if (this.checked) {
                        // Verificar si existe otra oferta con chollo antes de mostrar los campos
                        await verificarOfertaCholloExistente();
                    } else {
                        // Si se desmarca, ocultar campos y advertencia
                        actualizarEstadoChollo(false);
                        ocultarAvisoOfertaExistente();
                    }
                });
            }

            // Event listener para el checkbox de confirmación de continuar (usar delegación de eventos)
            document.addEventListener('change', function(e) {
                if (e.target && e.target.id === 'chollo_confirmar_continuar') {
                    if (e.target.checked) {
                        // Si se marca, mostrar los campos del chollo
                        actualizarEstadoChollo(true);
                    } else {
                        // Si se desmarca, ocultar los campos del chollo
                        actualizarEstadoChollo(false);
                    }
                }
            });
            
            // Event listener para escribir en el campo de tienda
            tiendaInput.addEventListener('input', function(e) {
                const query = e.target.value;
                tiendaSeleccionada = false;
                
                if (timeoutBusquedaTienda) {
                    clearTimeout(timeoutBusquedaTienda);
                }
                
                if (query.length === 0) {
                    ocultarSugerenciasTienda();
                    document.getElementById('tienda_id').value = '';
                    tiendaInput.classList.remove('border-green-500');
                    // Ocultar aviso de oferta existente si se limpia la tienda
                    ocultarAvisoOfertaExistente();
                    
                    // Resetear campo como_scrapear si estamos creando una nueva oferta
                    const ofertaId = {{ $oferta ? $oferta->id : 'null' }};
                    if (ofertaId === 'null' || ofertaId === null) {
                        const comoScrapearSelect = document.getElementById('como_scrapear');
                        const opcionAutomatico = document.getElementById('opcion_automatico');
                        const opcionManual = document.getElementById('opcion_manual');
                        if (comoScrapearSelect) {
                            comoScrapearSelect.value = '';
                        }
                        if (opcionAutomatico) {
                            opcionAutomatico.disabled = false;
                            opcionAutomatico.style.opacity = '1';
                        }
                        if (opcionManual) {
                            opcionManual.disabled = false;
                            opcionManual.style.opacity = '1';
                        }
                    }
                    return;
                }
                
                timeoutBusquedaTienda = setTimeout(() => {
                    buscarTiendas(query);
                }, 300);
            });
            
            tiendaInput.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    ocultarSugerenciasTienda();
                    tiendaInput.blur();
                } else if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    navegarSugerenciasTienda('abajo');
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    navegarSugerenciasTienda('arriba');
                } else if (e.key === 'Enter') {
                    e.preventDefault();
                    if (indiceSeleccionadoTienda >= 0) {
                        seleccionarTiendaResaltada();
                    }
                }
            });
           
            productoInput.addEventListener('input', function(e) {
                const query = e.target.value;
                productoSeleccionado = false;
                
                if (timeoutBusqueda) {
                    clearTimeout(timeoutBusqueda);
                }
                
                if (query.length === 0) {
                    ocultarSugerencias();
                    document.getElementById('producto_id').value = '';
                    productoInput.classList.remove('border-green-500');
                    
                    // Restaurar campo de unidades a su estado normal
                    const unidadesInput = document.querySelector('[name="unidades"]');
                    if (unidadesInput) {
                        unidadesInput.readOnly = false;
                        unidadesInput.classList.remove('opacity-50', 'cursor-not-allowed', 'bg-gray-200', 'dark:bg-gray-600');
                        unidadesInput.classList.add('opacity-100');
                    }
                    
                    // Ocultar aviso de oferta existente si se limpia el producto
                    ocultarAvisoOfertaExistente();
                    
                    return;
                }
                
                timeoutBusqueda = setTimeout(() => {
                    buscarProductos(query);
                }, 300);
            });
           
            productoInput.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    ocultarSugerencias();
                    productoInput.blur();
                } else if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    navegarSugerencias('abajo');
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    navegarSugerencias('arriba');
                } else if (e.key === 'Enter') {
                    e.preventDefault();
                    if (indiceSeleccionado >= 0) {
                        seleccionarProductoResaltado();
                    }
                }
            });

            if (cholloInput) {
                cholloInput.addEventListener('input', function(e) {
                    const query = e.target.value;
                    cholloSeleccionado = false;

                    if (timeoutBusquedaChollo) {
                        clearTimeout(timeoutBusquedaChollo);
                    }

                    if (query.length === 0) {
                        ocultarSugerenciasChollo();
                        if (cholloIdInput) {
                            cholloIdInput.value = '';
                        }
                        cholloInput.classList.remove('border-green-500');
                        return;
                    }

                    timeoutBusquedaChollo = setTimeout(() => {
                        buscarChollos(query);
                    }, 300);
                });

                cholloInput.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape') {
                        ocultarSugerenciasChollo();
                        cholloInput.blur();
                    } else if (e.key === 'ArrowDown') {
                        e.preventDefault();
                        navegarSugerenciasChollo('abajo');
                    } else if (e.key === 'ArrowUp') {
                        e.preventDefault();
                        navegarSugerenciasChollo('arriba');
                    } else if (e.key === 'Enter') {
                        e.preventDefault();
                        if (indiceSeleccionadoChollo >= 0) {
                            seleccionarCholloResaltado();
                        }
                    }
                });
            }

            document.addEventListener('click', function(e) {
                if (!e.target.closest('#producto_nombre') && !e.target.closest('#producto_sugerencias')) {
                    ocultarSugerencias();
                }
                if (!e.target.closest('#tienda_nombre') && !e.target.closest('#tienda_sugerencias')) {
                    ocultarSugerenciasTienda();
                }
                if (!e.target.closest('#chollo_nombre') && !e.target.closest('#chollo_sugerencias')) {
                    ocultarSugerenciasChollo();
                }
            });
           
            const form = document.querySelector('form');
            form.addEventListener('submit', function(e) {
        // Confirmación final: si el precio está en 0, forzar "no mostrar" y mostrar info
        if (typeof _precioInputsEsCeroConfirmado === 'function' && _precioInputsEsCeroConfirmado()) {
            _setMostrarNoPorPrecioCero();
            _setAvisoInfoPrecioCero(true);
        }

                const productoId = document.getElementById('producto_id').value;
                const urlInput = document.getElementById('url_input');
                const urlValue = urlInput.value.trim();
                const comoScrapearSelect = document.getElementById('como_scrapear');
                const comoScrapearValue = comoScrapearSelect ? comoScrapearSelect.value : '';

                if (!productoId) {
                    e.preventDefault();
                    alert('Por favor, selecciona un producto antes de guardar la oferta.');
                    document.getElementById('producto_nombre').focus();
                    return false;
                }
                
                if (!urlValue) {
                    e.preventDefault();
                    alert('Por favor, introduce una URL antes de guardar la oferta.');
                    urlInput.focus();
                    return false;
                }

                if (!comoScrapearValue) {
                    e.preventDefault();
                    alert('Por favor, selecciona cómo scrapear la oferta.');
                    if (comoScrapearSelect) {
                        comoScrapearSelect.focus();
                    }
                    return false;
                }

                if (cholloCheckbox && cholloCheckbox.checked) {
                    const cholloId = cholloIdInput ? cholloIdInput.value : '';
                    const fechaInicio = fechaInicioCholloInput ? fechaInicioCholloInput.value : '';

                    if (!cholloId) {
                        e.preventDefault();
                        alert('Selecciona un chollo válido para vincular la oferta.');
                        if (cholloInput) {
                            cholloInput.focus();
                        }
                        return false;
                    }

                    if (!fechaInicio) {
                        e.preventDefault();
                        alert('Indica la fecha de inicio del chollo.');
                        if (fechaInicioCholloInput) {
                            fechaInicioCholloInput.focus();
                        }
                        return false;
                    }
                }
                
                solicitarActualizacionPrecio(productoId);
            });
        });
    </script>

{{-- EVITAR TENER QUE PINCHAR DOS VECES EN LOS ENLACES PARA QUE FUNCIONEN--}}
    <script>
document.addEventListener('DOMContentLoaded', function() {
    // Solo ejecutar este script si estamos en el formulario de ofertas
    const urlInput = document.getElementById('url_input');
    if (!urlInput) {
        return; // No estamos en el formulario de ofertas
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

// Validación en tiempo real de URL
let urlValidationTimeout = null;
let urlValidationInProgress = false;
let urlIsValid = false;
let urlDuplicateConfirmed = false;
let urlTiendaDetectionTimeout = null;

// =======================
// VALIDACIÓN MAX PRECIO
// =======================
// Nota: el usuario escribe "9999,999" pero el input type="number" trabaja internamente con punto.
window.MAX_PRECIO_OFERTA = 9999.999;
window.MAX_DECIMALES_PRECIO_OFERTA = 3;
window.PRECIO_CERO_DIAS_AVISO = 4;

function normalizarNumeroOferta(valor) {
    if (valor === null || valor === undefined) return NaN;
    if (typeof valor === 'number') return valor;
    const s = String(valor).trim();
    if (!s) return NaN;
    return parseFloat(s.replace(',', '.'));
}

function contarDecimalesOferta(valorRaw) {
    if (valorRaw === null || valorRaw === undefined) return 0;
    const s = String(valorRaw).trim();
    if (!s) return 0;
    // Soportar coma o punto como separador decimal. Si hay ambos, tomamos el último que aparezca.
    const lastDot = s.lastIndexOf('.');
    const lastComma = s.lastIndexOf(',');
    const idx = Math.max(lastDot, lastComma);
    if (idx === -1) return 0;
    const dec = s.slice(idx + 1);
    // Si el usuario escribe algo raro (ej: "12."), no contamos decimales
    if (!dec) return 0;
    // Si hay signos de notación científica, consideramos formato inválido para este caso
    if (/e|E/.test(s)) return 999;
    // Contar solo dígitos finales (si mete espacios o letras, lo consideramos inválido)
    if (!/^\d+$/.test(dec)) return 999;
    return dec.length;
}

window.validarPreciosMaximosEnFormulario = function validarPreciosMaximosEnFormulario() {
    const inputTotal = document.querySelector('[name="precio_total"]');
    const inputUnidad = document.querySelector('[name="precio_unidad"]');
    if (!inputTotal || !inputUnidad) return true;

    const max = window.MAX_PRECIO_OFERTA;
    const maxDec = window.MAX_DECIMALES_PRECIO_OFERTA ?? 3;
    const total = normalizarNumeroOferta(inputTotal.value);
    const unidad = normalizarNumeroOferta(inputUnidad.value);

    const totalDec = contarDecimalesOferta(inputTotal.value);
    const unidadDec = contarDecimalesOferta(inputUnidad.value);

    const totalExcede = Number.isFinite(total) && total > max;
    const unidadExcede = Number.isFinite(unidad) && unidad > max;
    const totalDecimalesInvalidos = inputTotal.value && totalDec > maxDec;
    const unidadDecimalesInvalidos = inputUnidad.value && unidadDec > maxDec;

    const marcar = (input, invalido) => {
        // Borde + ring (y quitar el contorno azul de focus cuando hay error)
        input.classList.toggle('border-red-500', invalido);
        input.classList.toggle('ring-2', invalido);
        input.classList.toggle('ring-red-500', invalido);
        input.classList.toggle('focus:ring-red-500', invalido);
        if (invalido) {
            input.classList.remove('focus:ring-blue-500');
        } else {
            // Restaurar el focus azul normal si estaba presente en el input
            input.classList.add('focus:ring-blue-500');
            input.classList.remove('focus:ring-red-500');
            input.classList.remove('ring-2', 'ring-red-500');
            input.classList.remove('border-red-500');
        }
        input.setAttribute('aria-invalid', invalido ? 'true' : 'false');
    };

    const totalInvalido = totalExcede || totalDecimalesInvalidos;
    const unidadInvalido = unidadExcede || unidadDecimalesInvalidos;

    // Mensaje de validación nativo (por si se intenta enviar)
    inputTotal.setCustomValidity(
        totalExcede
            ? `El precio total no puede superar ${String(max).replace('.', ',')}`
            : (totalDecimalesInvalidos ? `Máximo ${maxDec} decimales` : '')
    );
    inputUnidad.setCustomValidity(
        unidadExcede
            ? `El precio por unidad no puede superar ${String(max).replace('.', ',')}`
            : (unidadDecimalesInvalidos ? `Máximo ${maxDec} decimales` : '')
    );

    marcar(inputTotal, totalInvalido);
    marcar(inputUnidad, unidadInvalido);

    return !(totalInvalido || unidadInvalido);
};

// =======================
// PRECIO 0 => NO MOSTRAR + INFO + AVISO SIN STOCK (+4 días)
// (Se dispara solo al "confirmar" el 0: blur/change o submit; evita el caso 0,25 mientras escribes)
// =======================
let _precioCeroAutoAplicado = false;
let _mostrarPrevioAntesDeCero = null;
let _mostrarTouched = false;

function _rawEsCeroConfirmado(raw) {
    const s = String(raw ?? '').trim();
    if (!s) return false;
    // No considerar confirmados valores incompletos tipo "0," o "0."
    if (s.endsWith(',') || s.endsWith('.')) return false;
    return /^0+(?:[.,]0+)?$/.test(s);
}

function _precioInputsEsCeroConfirmado() {
    const total = document.querySelector('[name="precio_total"]')?.value;
    const unidad = document.querySelector('[name="precio_unidad"]')?.value;
    return _rawEsCeroConfirmado(total) || _rawEsCeroConfirmado(unidad);
}

function _setMostrarNoPorPrecioCero() {
    const radioNo = document.querySelector('input[name="mostrar"][value="no"]');
    const radioSi = document.querySelector('input[name="mostrar"][value="si"]');
    if (!radioNo || !radioSi) return;

    if (!_precioCeroAutoAplicado) {
        const actual = (radioNo.checked ? 'no' : (radioSi.checked ? 'si' : null));
        _mostrarPrevioAntesDeCero = actual;
    }

    radioNo.checked = true;
    _precioCeroAutoAplicado = true;
}

function _setAvisoInfoPrecioCero(visible) {
    const listaAvisos = document.getElementById('lista-avisos');
    if (!listaAvisos) return;

    let box = document.getElementById('sin-stock-auto-msg');
    if (!box) {
        box = document.createElement('div');
        box.id = 'sin-stock-auto-msg';
        box.className = 'hidden mb-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-300 dark:border-yellow-700 rounded-xl p-4';
        box.innerHTML = `
            <div class="text-sm text-yellow-800 dark:text-yellow-200 font-semibold mb-1">
                Precio 0 detectado
            </div>
            <div class="text-sm text-yellow-700 dark:text-yellow-300">
                Al poner precio 0, la oferta se pone en <strong>no mostrar</strong> y al guardar se generará un aviso de <strong>“Sin stock - 1a vez”</strong> a <strong>${window.PRECIO_CERO_DIAS_AVISO} días</strong>.
            </div>
        `;
        // Insertar justo encima del listado de avisos
        listaAvisos.parentNode.insertBefore(box, listaAvisos);
    }

    if (visible) box.classList.remove('hidden');
    else box.classList.add('hidden');
}

function _manejarPrecioCeroAuto() {
    const esCero = _precioInputsEsCeroConfirmado();
    if (esCero) {
        _setMostrarNoPorPrecioCero();
        _setAvisoInfoPrecioCero(true);
    } else {
        _setAvisoInfoPrecioCero(false);
        // Opcional: si lo pusimos automáticamente y el usuario no tocó "mostrar", restaurar
        if (_precioCeroAutoAplicado && !_mostrarTouched && _mostrarPrevioAntesDeCero === 'si') {
            const radioSi = document.querySelector('input[name="mostrar"][value="si"]');
            if (radioSi) radioSi.checked = true;
        }
        _precioCeroAutoAplicado = false;
    }
}

// Función para guardar estado de validación en localStorage
function guardarEstadoValidacion() {
    const estado = {
        urlIsValid: urlIsValid,
        urlDuplicateConfirmed: urlDuplicateConfirmed,
        urlValue: document.getElementById('url_input').value
    };
    localStorage.setItem('urlValidationState', JSON.stringify(estado));
}

// Función para cargar estado de validación desde localStorage
function cargarEstadoValidacion() {
    const estadoGuardado = localStorage.getItem('urlValidationState');
    if (estadoGuardado) {
        const estado = JSON.parse(estadoGuardado);
        const urlInput = document.getElementById('url_input');
        
        // Solo restaurar si la URL actual coincide con la guardada
        if (estado.urlValue === urlInput.value) {
            urlIsValid = estado.urlIsValid;
            urlDuplicateConfirmed = estado.urlDuplicateConfirmed;
            // Verificar que actualizarEstadoBoton esté definido antes de llamarlo
            if (typeof actualizarEstadoBoton === 'function') {
                actualizarEstadoBoton();
            }
            
            // Restaurar mensaje de validación si es necesario
            if (urlInput.value.trim() && typeof mostrarMensajeUrl === 'function') {
                if (urlIsValid) {
                    mostrarMensajeUrl('success', 'Esta URL no existe y está disponible');
                } else if (urlDuplicateConfirmed) {
                    mostrarMensajeUrl('error', 'Esta URL ya existe en otra oferta del mismo producto');
                    const confirmCheckbox = document.getElementById('url_duplicate_confirm');
                    if (confirmCheckbox) {
                        confirmCheckbox.checked = true;
                    }
                } else {
                    mostrarMensajeUrl('error', 'Esta URL ya existe en otra oferta del mismo producto');
                }
            }
        }
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // Solo ejecutar este script si estamos en el formulario de ofertas
    const urlInput = document.getElementById('url_input');
    const validationMessage = document.getElementById('url_validation_message');
    const btnGuardar = document.getElementById('btn_guardar');
    
    // Si no estamos en el formulario de ofertas, salir
    if (!urlInput || !validationMessage || !btnGuardar) {
        return;
    }
    
    // Obtener el ID de la oferta si estamos editando
    const ofertaId = {{ $oferta ? $oferta->id : 'null' }};

    // Track si el usuario toca manualmente "mostrar"
    document.querySelectorAll('input[name="mostrar"]').forEach(r => {
        r.addEventListener('change', () => { _mostrarTouched = true; });
    });

    // Precio 0: disparar al confirmar (blur/change) para evitar el caso 0,25 mientras escribes
    const precioTotalInput = document.querySelector('[name="precio_total"]');
    const precioUnidadInput = document.querySelector('[name="precio_unidad"]');
    if (precioTotalInput) {
        precioTotalInput.addEventListener('blur', _manejarPrecioCeroAuto);
        precioTotalInput.addEventListener('change', _manejarPrecioCeroAuto);
    }
    if (precioUnidadInput) {
        precioUnidadInput.addEventListener('blur', _manejarPrecioCeroAuto);
        precioUnidadInput.addEventListener('change', _manejarPrecioCeroAuto);
    }
    
    // Función para validar URL
    async function validarUrl(url) {
        if (!url || url.trim() === '') {
            mostrarMensajeUrl('', '');
            return;
        }
        
        try {
            const productoId = document.getElementById('producto_id').value;
            
            const response = await fetch('{{ route("admin.ofertas.verificar.url") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    url: url,
                    oferta_id: ofertaId,
                    producto_id: productoId
                })
            });
            
            const data = await response.json();
            
            switch (data.tipo) {
                case 'disponible':
                    mostrarMensajeUrl('success', data.mensaje);
                    urlIsValid = true;
                    break;
                    
                case 'mismo_producto':
                    mostrarMensajeUrl('error', data.mensaje);
                    urlIsValid = false;
                    break;
                    
                case 'otros_productos':
                    mostrarMensajeUrl('info', data.mensaje);
                    mostrarProductosExistentes(data.productos);
                    urlIsValid = true; // Permite guardar sin confirmación
                    break;
                    
                case 'vacia':
                    mostrarMensajeUrl('', '');
                    urlIsValid = false;
                    break;
                    
                default:
                    mostrarMensajeUrl('error', 'Error al validar la URL');
                    urlIsValid = false;
            }
        } catch (error) {
            console.error('Error al validar URL:', error);
            mostrarMensajeUrl('error', 'Error al validar la URL');
            urlIsValid = false;
        }
        
        urlValidationInProgress = false;
        actualizarEstadoBoton();
    }
    
    // Función para mostrar mensaje de validación
    function mostrarMensajeUrl(tipo, mensaje) {
        const checkboxContainer = document.getElementById('url_duplicate_checkbox');
        const checkbox = document.getElementById('url_duplicate_confirm');
        const otherProductsContainer = document.getElementById('url_other_products');
        
        validationMessage.className = 'mt-1 text-sm';
        
        // Ocultar todos los contenedores por defecto
        checkboxContainer.classList.add('hidden');
        otherProductsContainer.classList.add('hidden');
        urlDuplicateConfirmed = false;
        checkbox.checked = false;
        
        if (tipo === 'error') {
            validationMessage.classList.add('text-red-500');
            validationMessage.classList.remove('text-green-500', 'text-blue-500', 'hidden');
            // Mostrar checkbox para URL duplicada en mismo producto
            checkboxContainer.classList.remove('hidden');
        } else if (tipo === 'success') {
            validationMessage.classList.add('text-green-500');
            validationMessage.classList.remove('text-red-500', 'text-blue-500', 'hidden');
        } else if (tipo === 'info') {
            validationMessage.classList.add('text-blue-500');
            validationMessage.classList.remove('text-red-500', 'text-green-500', 'hidden');
            // Mostrar lista de productos existentes
            otherProductsContainer.classList.remove('hidden');
        } else {
            validationMessage.classList.add('hidden');
        }
        
        validationMessage.textContent = mensaje;
    }
    
    // Función para mostrar la lista de productos existentes
    function mostrarProductosExistentes(productos) {
        const productsList = document.getElementById('url_products_list');
        
        if (productos && productos.length > 0) {
            const productosHtml = productos.map(producto => {
                const nombreCompleto = `${producto.nombre} - ${producto.marca} - ${producto.modelo} - ${producto.talla}`;
                return `<div class="mb-1">• ${nombreCompleto}</div>`;
            }).join('');
            
            productsList.innerHTML = productosHtml;
        } else {
            productsList.innerHTML = '<div class="text-gray-500">No se encontraron productos</div>';
        }
    }
    
            // Función para actualizar estado del botón
        function actualizarEstadoBoton() {
            const urlValue = urlInput.value.trim();
        const preciosOk = typeof window.validarPreciosMaximosEnFormulario === 'function'
            ? window.validarPreciosMaximosEnFormulario()
            : true;
            
            if (urlValidationInProgress) {
                btnGuardar.disabled = true;
                btnGuardar.textContent = 'Validando URL...';
            } else if (urlValue && !urlIsValid && !urlDuplicateConfirmed) {
                btnGuardar.disabled = true;
                btnGuardar.textContent = 'URL duplicada';
            } else if (!urlValue) {
                btnGuardar.disabled = true;
                btnGuardar.textContent = 'URL requerida';
            } else {
                btnGuardar.disabled = false;
                btnGuardar.textContent = 'Guardar oferta';
            }

        // Si el precio supera el máximo, forzar deshabilitado (sin pisar el texto actual).
        if (!preciosOk) {
            btnGuardar.disabled = true;
            btnGuardar.title = `El precio total y el precio por unidad no pueden superar ${window.MAX_PRECIO_OFERTA.toString().replace('.', ',')} €`;
        } else if (btnGuardar.title && btnGuardar.title.includes('no pueden superar')) {
            btnGuardar.title = '';
        }
            
            // Guardar estado en localStorage
            guardarEstadoValidacion();
        }

    // Exponer para que otros validadores (p.ej. precios) puedan recalcular el estado del botón
    window.actualizarEstadoBotonUrl = actualizarEstadoBoton;
    
    // Función para limpiar URL de Amazon
    function limpiarUrlAmazon(url) {
        try {
            // Detectar URLs de Amazon (diferentes dominios: .es, .com, .co.uk, etc.)
            const amazonRegex = /^https?:\/\/(www\.)?amazon\.(es|com|co\.uk|de|fr|it|ca|com\.au|co\.jp|in|com\.mx|com\.br|nl|se|pl|com\.tr|ae|sa|sg|com\.tw|com\.hk)\//i;
            
            if (!amazonRegex.test(url)) {
                return url; // No es Amazon, devolver URL original
            }
            
            // Extraer el dominio de Amazon (es, com, etc.)
            const dominioMatch = url.match(/amazon\.([a-z.]+)/i);
            if (!dominioMatch) {
                return url;
            }
            const dominio = dominioMatch[1];
            
            // Buscar el código del producto después de /dp/ o /gp/product/
            const dpMatch = url.match(/\/dp\/([A-Z0-9]+)/i) || url.match(/\/gp\/product\/([A-Z0-9]+)/i);
            
            if (dpMatch && dpMatch[1]) {
                const codigoProducto = dpMatch[1];
                // Construir URL limpia: https://www.amazon.{dominio}/dp/CODIGO
                return `https://www.amazon.${dominio}/dp/${codigoProducto}`;
            }
            
            // Si no se encuentra el código, devolver URL original
            return url;
        } catch (error) {
            console.error('Error al limpiar URL de Amazon:', error);
            return url; // En caso de error, devolver URL original
        }
    }

    // Función para limpiar URL de pccomponentes (quitar # al final y ?refurbished)
    function limpiarUrlPccomponentes(url) {
        try {
            // Verificar si la URL contiene "pccomponentes"
            if (!url || !url.toLowerCase().includes('pccomponentes')) {
                return url; // No es pccomponentes, devolver URL original
            }
            
            let urlLimpia = url;
            
            // Si la URL termina con #, quitarlo
            if (urlLimpia.endsWith('#')) {
                urlLimpia = urlLimpia.slice(0, -1);
            }
            
            // Quitar el parámetro ?refurbished o &refurbished (con o sin valor)
            // Caso 1: ?refurbished al inicio de la query string
            urlLimpia = urlLimpia.replace(/\?refurbished(=[^&]*)?(&|$)/gi, function(match, p1, p2) {
                // Si hay más parámetros después (p2 === '&'), reemplazar con ?
                return p2 === '&' ? '?' : '';
            });
            
            // Caso 2: &refurbished como parámetro intermedio o final
            urlLimpia = urlLimpia.replace(/&refurbished(=[^&]*)?(&|$)/gi, function(match, p1, p2) {
                // Si hay más parámetros después (p2 === '&'), mantener &
                return p2 === '&' ? '&' : '';
            });
            
            // Limpiar ? o & al final si quedaron solos
            urlLimpia = urlLimpia.replace(/[?&]$/, '');
            
            return urlLimpia;
        } catch (error) {
            console.error('Error al limpiar URL de pccomponentes:', error);
            return url; // En caso de error, devolver URL original
        }
    }

    // Función para extraer y normalizar el dominio de una URL
    function extraerDominioNormalizado(url) {
        try {
            if (!url || !url.trim()) {
                return null;
            }

            // Añadir protocolo si no tiene
            let urlCompleta = url.trim();
            if (!/^https?:\/\//i.test(urlCompleta)) {
                urlCompleta = 'https://' + urlCompleta;
            }

            // Intentar parsear la URL
            let hostname;
            try {
                const urlObj = new URL(urlCompleta);
                hostname = urlObj.hostname;
            } catch (e) {
                // Si falla el parser, intentar extraer manualmente
                const match = urlCompleta.match(/^(?:https?:\/\/)?(?:www\.)?([^\/\s]+)/i);
                if (match && match[1]) {
                    hostname = match[1];
                } else {
                    return null;
                }
            }

            if (!hostname) {
                return null;
            }

            // Normalizar: quitar www. y convertir a minúsculas
            hostname = hostname.toLowerCase();
            hostname = hostname.replace(/^www\./, '');

            return hostname;
        } catch (error) {
            return null;
        }
    }

    // Función para normalizar la URL de una tienda (igual que extraerDominioNormalizado)
    function normalizarUrlTienda(urlTienda) {
        if (!urlTienda || !urlTienda.trim()) {
            return null;
        }

        // Normalizar: quitar www., https://, http:// y convertir a minúsculas
        let normalizada = urlTienda.trim().toLowerCase();
        normalizada = normalizada.replace(/^https?:\/\//, '');
        normalizada = normalizada.replace(/^www\./, '');
        normalizada = normalizada.replace(/\/.*$/, ''); // Quitar cualquier path después del dominio
        normalizada = normalizada.replace(/\/$/, ''); // Quitar barra final si existe

        return normalizada;
    }

    // Función para detectar y seleccionar automáticamente la tienda basándose en la URL
    async function detectarTiendaPorUrl(url) {
        if (!url || !url.trim()) {
            return;
        }

        // Solo intentar detectar si no hay tienda seleccionada
        const tiendaIdActual = document.getElementById('tienda_id')?.value;
        if (tiendaIdActual && tiendaIdActual.trim() !== '') {
            return; // Ya hay una tienda seleccionada, no hacer nada
        }

        const dominioUrl = extraerDominioNormalizado(url);
        if (!dominioUrl) {
            return;
        }

        try {
            // Obtener todas las tiendas disponibles
            const response = await fetch('{{ route("admin.ofertas.tiendas.disponibles") }}');
            if (!response.ok) {
                return;
            }
            
            const todasLasTiendas = await response.json();

            if (!Array.isArray(todasLasTiendas) || todasLasTiendas.length === 0) {
                return;
            }

            // Buscar tienda que coincida con el dominio
            const tiendaEncontrada = todasLasTiendas.find(tienda => {
                if (!tienda || !tienda.url || !tienda.url.trim()) {
                    return false;
                }

                const dominioTienda = normalizarUrlTienda(tienda.url);
                if (!dominioTienda) {
                    return false;
                }

                // Comparar dominios normalizados (case-insensitive)
                return dominioTienda.toLowerCase() === dominioUrl.toLowerCase();
            });

            // Si encontramos una tienda, seleccionarla automáticamente
            if (tiendaEncontrada) {
                seleccionarTienda(tiendaEncontrada);
            }
        } catch (error) {
            // Silenciar errores
        }
    }
    
    // Event listener para pegar URL (limpiar automáticamente URLs de Amazon y pccomponentes, y detectar tienda)
    urlInput.addEventListener('paste', function(e) {
        // Usar setTimeout para acceder al valor después de que se pegue
        setTimeout(() => {
            const urlPegada = urlInput.value.trim();
            if (urlPegada) {
                // Verificar si es URL de pccomponentes y si tiene # o ?refurbished
                const esPccomponentes = urlPegada.toLowerCase().includes('pccomponentes');
                const tieneHash = urlPegada.includes('#');
                const tieneRefurbished = /[?&]refurbished/i.test(urlPegada);
                
                // Limpiar URL de Amazon primero
                let urlLimpia = limpiarUrlAmazon(urlPegada);
                // Luego limpiar URL de pccomponentes (quitar # al final y ?refurbished)
                urlLimpia = limpiarUrlPccomponentes(urlLimpia);
                
                // Si es pccomponentes y tiene # o ?refurbished, establecer precio total a 0
                if (esPccomponentes && (tieneHash || tieneRefurbished)) {
                    const precioTotalInput = document.querySelector('[name="precio_total"]');
                    if (precioTotalInput) {
                        precioTotalInput.value = '0';
                        // Disparar evento input para que se recalcule el precio por unidad
                        precioTotalInput.dispatchEvent(new Event('input', { bubbles: true }));
                        // Disparar evento change para que se ejecute la lógica de precio 0 (poner "no mostrar" y aviso)
                        precioTotalInput.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                }
                
                if (urlLimpia !== urlPegada) {
                    urlInput.value = urlLimpia;
                    // Disparar evento input para que se ejecute la validación
                    urlInput.dispatchEvent(new Event('input', { bubbles: true }));
                }
                
                // Detectar tienda automáticamente después de limpiar la URL
                // Usar un pequeño delay adicional para asegurar que el valor esté disponible
                setTimeout(() => {
                    detectarTiendaPorUrl(urlLimpia || urlPegada);
                }, 50);
            }
        }, 50);
    });
    
    // Event listener para cambios en el campo URL
    urlInput.addEventListener('input', function(e) {
        let url = e.target.value.trim();
        
        // Verificar si es URL de pccomponentes y si tiene # o ?refurbished antes de limpiar
        const esPccomponentes = url.toLowerCase().includes('pccomponentes');
        const tieneHash = url.includes('#');
        const tieneRefurbished = /[?&]refurbished/i.test(url);
        
        // Limpiar URL de pccomponentes (quitar # al final y ?refurbished) si aplica
        const urlLimpiaPccomponentes = limpiarUrlPccomponentes(url);
        if (urlLimpiaPccomponentes !== url) {
            url = urlLimpiaPccomponentes;
            urlInput.value = url;
            
            // Si es pccomponentes y tenía # o ?refurbished, establecer precio total a 0
            if (esPccomponentes && (tieneHash || tieneRefurbished)) {
                const precioTotalInput = document.querySelector('[name="precio_total"]');
                if (precioTotalInput) {
                    precioTotalInput.value = '0';
                    // Disparar evento input para que se recalcule el precio por unidad
                    precioTotalInput.dispatchEvent(new Event('input', { bubbles: true }));
                    // Disparar evento change para que se ejecute la lógica de precio 0 (poner "no mostrar" y aviso)
                    precioTotalInput.dispatchEvent(new Event('change', { bubbles: true }));
                }
            }
        }
        
        // Actualizar el href del botón "Ir a la URL"
        const btnIrUrl = document.getElementById('btn_ir_url');
        if (btnIrUrl) {
            if (url) {
                btnIrUrl.href = url;
                btnIrUrl.classList.remove('opacity-50', 'pointer-events-none');
            } else {
                btnIrUrl.href = '#';
                btnIrUrl.classList.add('opacity-50', 'pointer-events-none');
            }
        }
        
        // Limpiar timeouts anteriores
        if (urlValidationTimeout) {
            clearTimeout(urlValidationTimeout);
        }
        if (urlTiendaDetectionTimeout) {
            clearTimeout(urlTiendaDetectionTimeout);
        }
        
        // Si la URL está vacía, ocultar mensaje y habilitar botón
        if (!url) {
            mostrarMensajeUrl('', '');
            urlIsValid = false;
            urlDuplicateConfirmed = false;
            actualizarEstadoBoton();
            return;
        }
        
        // Mostrar estado de validación
        urlValidationInProgress = true;
        actualizarEstadoBoton();
        
        // Validar después de 1 segundo de inactividad
        urlValidationTimeout = setTimeout(() => {
            validarUrl(url);
        }, 1000);
        
        // Detectar tienda automáticamente después de limpiar la URL y un pequeño delay
        // Solo si no hay tienda seleccionada
        const tiendaIdActual = document.getElementById('tienda_id')?.value;
        if (!tiendaIdActual || tiendaIdActual.trim() === '') {
            // Primero limpiar la URL (por si es Amazon u otra tienda con limpieza especial)
            const urlLimpia = limpiarUrlAmazon(url);
            urlTiendaDetectionTimeout = setTimeout(() => {
                // Asegurarse de usar la URL actual del input por si cambió
                const urlActual = urlInput.value.trim();
                detectarTiendaPorUrl(urlLimpia || urlActual);
            }, 500); // Delay para evitar demasiadas peticiones mientras se escribe
        }
    });
    
    // Event listener para el checkbox de confirmación
    document.getElementById('url_duplicate_confirm').addEventListener('change', function(e) {
        urlDuplicateConfirmed = e.target.checked;
        actualizarEstadoBoton();
    });
    
    // Event listener para el botón de revalidar URL
    const btnRevalidarUrl = document.getElementById('btn_revalidar_url');
    if (btnRevalidarUrl) {
        btnRevalidarUrl.addEventListener('click', function() {
            const url = urlInput.value.trim();
            if (!url) {
                alert('Por favor, introduce una URL primero');
                urlInput.focus();
                return;
            }
            
            // Limpiar timeout anterior si existe
            if (urlValidationTimeout) {
                clearTimeout(urlValidationTimeout);
            }
            
            // Deshabilitar botón mientras valida
            btnRevalidarUrl.disabled = true;
            btnRevalidarUrl.innerHTML = '<svg class="w-5 h-5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>';
            
            // Mostrar estado de validación
            urlValidationInProgress = true;
            actualizarEstadoBoton();
            
            // Validar inmediatamente
            validarUrl(url).finally(() => {
                // Restaurar botón después de validar
                btnRevalidarUrl.disabled = false;
                btnRevalidarUrl.innerHTML = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>';
            });
        });
    }
    
    // Cargar estado de validación guardado (después de definir actualizarEstadoBoton)
    // cargarEstadoValidacion(); // Comentado temporalmente para evitar error
    
    // Inicializar botón "Ir a la URL"
    const btnIrUrl = document.getElementById('btn_ir_url');
    if (btnIrUrl && urlInput.value.trim()) {
        btnIrUrl.href = urlInput.value.trim();
    } else if (btnIrUrl) {
        btnIrUrl.href = '#';
        btnIrUrl.classList.add('opacity-50', 'pointer-events-none');
    }
    
    // Validar URL inicial si existe
    if (urlInput.value.trim()) {
        urlValidationInProgress = true;
        actualizarEstadoBoton();
        validarUrl(urlInput.value.trim());
    } else {
        // Si no hay URL inicial, actualizar estado del botón
        actualizarEstadoBoton();
    }
    
    // Cargar estado de validación guardado (después de definir actualizarEstadoBoton)
    cargarEstadoValidacion();
    
    // Añadir efecto visual cuando el botón está en modo sticky
    const btnGuardarContainer = btnGuardar.closest('.sticky');
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                btnGuardarContainer.classList.remove('sticky-active');
            } else {
                btnGuardarContainer.classList.add('sticky-active');
            }
        });
    }, { threshold: 1.0 });
    
    // Observar el botón para detectar cuando entra en modo sticky
    observer.observe(btnGuardarContainer);
});

// Validación reactiva de precios (rojo + bloqueo de submit)
document.addEventListener('DOMContentLoaded', function() {
    const inputTotal = document.querySelector('[name="precio_total"]');
    const inputUnidad = document.querySelector('[name="precio_unidad"]');
    const form = document.querySelector('form');
    if (!inputTotal || !inputUnidad || !form) return;

    const recalcularEstados = () => {
        if (typeof window.validarPreciosMaximosEnFormulario === 'function') {
            window.validarPreciosMaximosEnFormulario();
        }
        if (typeof window.actualizarEstadoBotonUrl === 'function') {
            window.actualizarEstadoBotonUrl();
        }
        if (typeof window.actualizarEstadoBotonGuardar === 'function') {
            window.actualizarEstadoBotonGuardar();
        }
    };

    inputTotal.addEventListener('input', recalcularEstados);
    inputUnidad.addEventListener('input', recalcularEstados);

    // Estado inicial
    recalcularEstados();

    // Bloquear submit siempre que exceda el máximo (por si algún otro script re-habilita el botón)
    form.addEventListener('submit', function(e) {
        const ok = typeof window.validarPreciosMaximosEnFormulario === 'function'
            ? window.validarPreciosMaximosEnFormulario()
            : true;
        if (!ok) {
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
            alert(`El precio total y/o el precio por unidad no pueden superar ${window.MAX_PRECIO_OFERTA.toString().replace('.', ',')} €.`);
            return false;
        }
    }, { capture: true, passive: false });
});

document.addEventListener('DOMContentLoaded', function() {
    const btnFechaFinalAhora = document.getElementById('chollo_fecha_final_ahora');
    const btnComprobadaAhora = document.getElementById('chollo_comprobada_ahora');

    function fechaActualISO() {
        const ahora = new Date();
        ahora.setSeconds(0, 0);
        const tzOffset = ahora.getTimezoneOffset();
        const local = new Date(ahora.getTime() - tzOffset * 60 * 1000);
        return local.toISOString().slice(0, 16);
    }

    if (btnFechaFinalAhora) {
        btnFechaFinalAhora.addEventListener('click', () => {
            const input = document.getElementById('chollo_fecha_final');
            if (input) {
                input.value = fechaActualISO();
            }
        });
    }

    if (btnComprobadaAhora) {
        btnComprobadaAhora.addEventListener('click', () => {
            const input = document.getElementById('chollo_comprobada');
            if (input) {
                input.value = fechaActualISO();
            }
        });
    }
});

// Variable global para almacenar información del producto actual
let productoActualOferta = null;
// Imágenes por sublínea (solo lectura) para el formulario de ofertas
window.__ofertaImagenesSublinea = window.__ofertaImagenesSublinea || {};
window.__ofertaImagenesSublineaActual = { key: null, imagenes: [] };

function obtenerImagenesProductoOferta() {
    const producto = productoActualOferta || null;
    const out = [];
    const add = (val) => {
        if (!val) return;
        if (Array.isArray(val)) {
            val.forEach(add);
            return;
        }
        if (typeof val === 'string') {
            const v = val.trim();
            if (v) out.push(v);
        }
    };

    // Campos reales del modelo/API
    add(producto?.imagen_grande);
    add(producto?.imagen_pequena);

    // Compatibilidad (por si en algún sitio se expone con otros nombres)
    add(producto?.imagenes_grandes);
    add(producto?.imagenes_pequenas);

    return Array.from(new Set(out));
}

// ESPECIFICACIONES INTERNAS
async function cargarEspecificacionesInternas(productoId) {
    const container = document.getElementById('especificaciones-internas-container');
    const inputHidden = document.getElementById('especificaciones_internas_input');
    
    if (!container || !productoId) {
        container.innerHTML = '<p class="text-sm text-gray-500 dark:text-gray-400">Selecciona un producto para cargar las especificaciones internas.</p>';
        productoActualOferta = null;
        return;
    }
    
    // Asegurar que el contenedor tenga los estilos correctos desde el principio
    container.style.backgroundColor = '';
    container.style.color = '';
    
    try {
        // Obtener el producto con categoria_especificaciones_internas_elegidas y categoria_id_especificaciones_internas
        const responseProducto = await fetch(`/panel-privado/productos/${productoId}`);
        const producto = await responseProducto.json();
        
        // Guardar información del producto globalmente para validación
        productoActualOferta = producto;
        
        if (!producto || !producto.categoria_id_especificaciones_internas) {
            container.innerHTML = '<p class="text-sm text-gray-500 dark:text-gray-400">Este producto no tiene una categoría asociada para especificaciones internas.</p>';
            inputHidden.value = '';
            return;
        }
        
        if (!producto.categoria_especificaciones_internas_elegidas) {
            container.innerHTML = '<p class="text-sm text-gray-500 dark:text-gray-400">Este producto no tiene especificaciones internas elegidas configuradas.</p>';
            inputHidden.value = '';
            return;
        }
        
        // Obtener las especificaciones internas de la categoría
        const responseCategoria = await fetch(`/panel-privado/productos/categoria/${producto.categoria_id_especificaciones_internas}/especificaciones-internas`);
        const dataCategoria = await responseCategoria.json();
        
        if (dataCategoria.error || !dataCategoria.especificaciones_internas || !dataCategoria.especificaciones_internas.filtros) {
            container.innerHTML = '<p class="text-sm text-gray-500 dark:text-gray-400">No se pudieron cargar las especificaciones internas de la categoría.</p>';
            inputHidden.value = '';
            return;
        }
        
        const especificacionesCategoria = dataCategoria.especificaciones_internas;
        const especificacionesElegidas = producto.categoria_especificaciones_internas_elegidas;
        
        // Combinar filtros de categoría y producto antes de procesarlos
        const filtrosCombinados = [];
        
        // Añadir filtros de categoría
        if (especificacionesCategoria.filtros && Array.isArray(especificacionesCategoria.filtros)) {
            filtrosCombinados.push(...especificacionesCategoria.filtros);
        }
        
        // Añadir filtros del producto (si existen)
        if (especificacionesElegidas._producto && 
            especificacionesElegidas._producto.filtros && 
            Array.isArray(especificacionesElegidas._producto.filtros)) {
            filtrosCombinados.push(...especificacionesElegidas._producto.filtros);
        }
        
        // Filtrar solo las líneas principales que tienen sublíneas marcadas como "Oferta"
        const filtrosFiltrados = filtrosCombinados
            .map(filtro => {
                const sublineasElegidas = especificacionesElegidas[filtro.id] || [];
                // Filtrar solo las sublíneas marcadas como "Oferta" (o === 1)
                const sublineasOferta = filtro.subprincipales.filter(sub => {
                    return sublineasElegidas.some(item => {
                        const sublineaId = typeof item === 'object' && item.id ? item.id : item;
                        return String(sublineaId) === String(sub.id) && 
                               ((typeof item === 'object' && item.o === 1) || false);
                    });
                });
                
                // Si hay sublíneas de oferta, devolver el filtro con solo esas sublíneas
                if (sublineasOferta.length > 0) {
                    return {
                        ...filtro,
                        subprincipales: sublineasOferta
                    };
                }
                return null;
            })
            .filter(filtro => filtro !== null);
        
        if (filtrosFiltrados.length === 0) {
            container.innerHTML = '<p class="text-sm text-gray-500 dark:text-gray-400">Este producto no tiene especificaciones internas marcadas como "Oferta".</p>';
            inputHidden.value = '';
            return;
        }
        
        // Cargar las especificaciones guardadas si estamos editando
        let especificacionesGuardadas = {};
        if (inputHidden.value) {
            try {
                especificacionesGuardadas = JSON.parse(inputHidden.value);
            } catch (e) {
                console.error('Error parseando especificaciones guardadas:', e);
            }
        }
        
        // Verificar si es unidadUnica y obtener columnas marcadas
        const esUnidadUnica = producto.unidadDeMedida === 'unidadUnica';
        const columnasProducto = esUnidadUnica && especificacionesElegidas._columnas ? especificacionesElegidas._columnas : [];
        
        // Mostrar los desplegables solo con las sublíneas marcadas como "Oferta"
        mostrarDesplegablesEspecificaciones(filtrosFiltrados, especificacionesGuardadas, esUnidadUnica, columnasProducto, especificacionesElegidas);
        
    } catch (error) {
        console.error('Error al cargar especificaciones internas:', error);
        container.innerHTML = '<p class="text-sm text-red-500">Error al cargar las especificaciones internas.</p>';
    }
}

function mostrarDesplegablesEspecificaciones(filtros, especificacionesGuardadas = {}, esUnidadUnica = false, columnasProducto = [], especificacionesElegidas = {}) {
    const container = document.getElementById('especificaciones-internas-container');
    const inputHidden = document.getElementById('especificaciones_internas_input');
    
    if (!container) return;

    // Reset del mapa de imágenes en cada render (para evitar datos desactualizados)
    window.__ofertaImagenesSublinea = {};
    
    let html = '<p class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-4">Marca las opciones deseadas en cada línea principal:</p>';
    html += '<div class="space-y-4">';
    
    filtros.forEach((filtro, index) => {
        const idPrincipal = filtro.id;
        const textoPrincipal = filtro.texto || `Línea principal ${index + 1}`;
        const subprincipales = filtro.subprincipales || [];
        
        // Obtener las sublíneas seleccionadas guardadas (puede ser array o valor único para compatibilidad)
        let sublineasSeleccionadas = [];
        if (especificacionesGuardadas[idPrincipal]) {
            if (Array.isArray(especificacionesGuardadas[idPrincipal])) {
                sublineasSeleccionadas = especificacionesGuardadas[idPrincipal];
            } else {
                // Compatibilidad con estructura antigua (un solo valor)
                const valor = especificacionesGuardadas[idPrincipal];
                if (valor !== null && valor !== 'null') {
                    sublineasSeleccionadas = [valor];
                }
            }
        }
        
        // Obtener las sublíneas seleccionadas para mostrar como chips
        const seleccionadas = subprincipales.filter(sub => 
            sublineasSeleccionadas.some(selected => String(selected) === String(sub.id))
        );
        
        html += `<div class="linea-principal-oferta border border-gray-300 dark:border-gray-600 rounded-lg p-4 bg-white dark:bg-gray-800" data-principal-id="${idPrincipal}">`;
        html += `<label class="block mb-2 font-medium text-gray-700 dark:text-gray-200">${textoPrincipal}${filtro.importante ? ' <span class="text-yellow-600 dark:text-yellow-400">(Aplica Filtro Categoria)</span>' : ''}<span class="error-columna-msg text-red-600 dark:text-red-400 text-sm font-normal ml-2" style="display: none;">Falta marcar una opcion de columna</span></label>`;
        
        // Desplegable
        html += `<details class="mb-2" open>`;
        html += `<summary class="cursor-pointer text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 font-medium">Seleccionar opciones</summary>`;
        html += `<div class="mt-2 space-y-2 pl-4 border-l-2 border-gray-300 dark:border-gray-600">`;
        
        // Verificar si esta línea principal está marcada como columna en el producto
        const esLineaColumna = esUnidadUnica && columnasProducto.includes(idPrincipal);
        
        // Obtener la sublínea marcada como columna en esta oferta (si existe)
        const columnaGuardada = especificacionesGuardadas._columnas ? especificacionesGuardadas._columnas[idPrincipal] : null;
        
        // Mostrar cada sublínea con un checkbox
        subprincipales.forEach((sub, subIndex) => {
            const idSublinea = sub.id;
            const textoSublinea = sub.texto || '(Sin texto)';
            const esPrimeraSublinea = subIndex === 0;
            const isChecked = sublineasSeleccionadas.some(selected => 
                String(selected) === String(idSublinea)
            );
            const esColumnaMarcada = columnaGuardada && String(columnaGuardada) === String(idSublinea);

            // --- Imágenes de sublínea (solo ver) ---
            const keyImg = `${String(idPrincipal)}::${String(idSublinea)}`;
            const imagenesSublinea = [];
            let usarImagenesProducto = false;
            const addImgs = (val) => {
                if (!val) return;
                if (Array.isArray(val)) {
                    val.forEach(addImgs);
                    return;
                }
                if (typeof val === 'string') {
                    const v = val.trim();
                    if (v) imagenesSublinea.push(v);
                }
            };
            // Soportar estructura en la propia sublínea
            addImgs(sub.imagenes ?? sub.imagen);
            // Soportar estructura en "especificacionesElegidas" (donde suele guardarse `img`)
            try {
                const elegidas = especificacionesElegidas ? especificacionesElegidas[idPrincipal] : null;
                if (Array.isArray(elegidas)) {
                    const item = elegidas.find(it => {
                        if (typeof it === 'string' || typeof it === 'number') {
                            return String(it) === String(idSublinea);
                        }
                        if (it && typeof it === 'object' && it.id) {
                            return String(it.id) === String(idSublinea);
                        }
                        return false;
                    });
                    if (item && typeof item === 'object') {
                        usarImagenesProducto = item.usarImagenesProducto === true;
                        addImgs(item.img ?? item.imagenes ?? item.imagen);
                    }
                }
            } catch (e) {
                // Silenciar: no bloquea el render
            }
            let imagenesUnicas = Array.from(new Set(imagenesSublinea));

            // Si la sublínea está marcada como "imag. producto", usar imágenes del producto asociado a la oferta
            if (usarImagenesProducto) {
                imagenesUnicas = obtenerImagenesProductoOferta();
            }

            if (imagenesUnicas.length > 0) {
                window.__ofertaImagenesSublinea[keyImg] = imagenesUnicas;
            }
            
            html += `<div class="flex items-center gap-2 hover:bg-gray-100 dark:hover:bg-gray-700 p-2 rounded">`;
            html += `<label class="flex items-center gap-2 cursor-pointer flex-1">`;
            html += `<input type="checkbox" class="especificacion-checkbox rounded border-gray-300 text-green-600 focus:ring-green-500" data-principal-id="${idPrincipal}" data-sublinea-id="${idSublinea}" data-sublinea-texto="${textoSublinea.replace(/"/g, '&quot;')}" ${isChecked ? 'checked' : ''}>`;
            html += `<span class="text-sm text-gray-700 dark:text-gray-300">${textoSublinea}</span>`;

            // Botón para ver imágenes (justo a continuación del nombre de la sublínea)
            // - Si hay imágenes: botón activo
            // - Si está en "imag. producto" pero el producto no tiene imágenes: mostrar botón deshabilitado con "0 imágenes"
            if (imagenesUnicas.length > 0 || usarImagenesProducto) {
                const n = imagenesUnicas.length;
                const deshabilitado = n === 0;
                html += `<button type="button"
                                class="btn-ver-imagenes-sublinea-oferta text-xs px-2 py-1 bg-blue-600 hover:bg-blue-700 text-white rounded transition-colors ml-1 ${deshabilitado ? 'opacity-50 cursor-not-allowed' : ''}"
                                data-key="${keyImg}"
                                ${deshabilitado ? 'disabled' : ''}
                                title="${deshabilitado ? 'El producto no tiene imágenes' : `Ver ${n} imagen${n !== 1 ? 'es' : ''}`}">
                            ${n} imagen${n !== 1 ? 'es' : ''}
                         </button>`;
            }
            
            // Icono de ayuda "?" solo en la primera sublínea
            if (esPrimeraSublinea) {
                html += `<div class="relative">`;
                html += `<button type="button" class="tooltip-btn text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 cursor-help focus:outline-none ml-1" aria-label="Ayuda" data-tooltip='Esta oferta aparecerá para esta opcion de filtrado en la vista del producto'>`;
                html += `<svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">`;
                html += `<path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm0 8a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"></path>`;
                html += `</svg>`;
                html += `</button>`;
                html += `</div>`;
            }
            
            html += `</label>`;
            
            // Checkbox "Columna" (solo si es unidadUnica y esta línea principal está marcada como columna en el producto)
            if (esUnidadUnica && esLineaColumna) {
                html += `<div class="flex items-center gap-1">`;
                html += `<label class="flex items-center gap-1 cursor-pointer" title="Columna en ofertas">`;
                html += `<input type="checkbox" class="columna-oferta-sublinea-checkbox rounded border-gray-300 text-orange-600 focus:ring-orange-500" data-principal-id="${idPrincipal}" data-sublinea-id="${idSublinea}" ${esColumnaMarcada ? 'checked' : ''} ${!isChecked ? 'disabled' : ''}>`;
                html += `<span class="text-xs text-gray-600 dark:text-gray-400">Columna</span>`;
                html += `</label>`;
                
                // Icono de ayuda "?" solo en la primera sublínea
                if (esPrimeraSublinea) {
                    html += `<div class="relative">`;
                    html += `<button type="button" class="tooltip-btn text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 cursor-help focus:outline-none" aria-label="Ayuda" data-tooltip='Al ser un producto con unidad de medida unica, las columnas de las ofertas se modifican. Y este filtro aparecerá como columna en el listado de ofertas, por lo que cada oferta debe marcar una opcion que corresponda al producto de la tienda al que se le va a llevar al usuario'>`;
                    html += `<svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">`;
                    html += `<path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm0 8a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"></path>`;
                    html += `</svg>`;
                    html += `</button>`;
                    html += `</div>`;
                }
                html += `</div>`;
            }
            
            html += `</div>`;
        });
        
        html += `</div>`;
        html += `</details>`;
        
        // Contenedor para chips de opciones seleccionadas
        html += `<div class="mt-2 flex flex-wrap gap-2 chips-container-${idPrincipal}">`;
        
        // Mostrar opciones seleccionadas como chips
        if (seleccionadas.length > 0) {
            seleccionadas.forEach(sub => {
                html += `<span class="inline-flex items-center gap-1 px-2 py-1 text-xs bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 rounded">`;
                html += `<svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>`;
                html += `<span>${sub.texto || '(Sin texto)'}</span>`;
                html += `</span>`;
            });
        }
        
        html += `</div>`;
        html += `</div>`;
    });
    
    html += '</div>';
    
    // Usar requestAnimationFrame para evitar el parpadeo
    requestAnimationFrame(() => {
        container.innerHTML = html;
        
        // Añadir event listeners a los checkboxes
        const checkboxes = container.querySelectorAll('.especificacion-checkbox');
        checkboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const principalId = this.dataset.principalId;
                const sublineaId = this.dataset.sublineaId;
                
                // Habilitar/deshabilitar checkbox de columna si existe (buscar en todo el documento, no solo en container)
                const columnaCheckbox = document.querySelector(`.columna-oferta-sublinea-checkbox[data-principal-id="${principalId}"][data-sublinea-id="${sublineaId}"]`);
                if (columnaCheckbox) {
                    columnaCheckbox.disabled = !this.checked;
                    if (!this.checked) {
                        columnaCheckbox.checked = false;
                    } else {
                        // Si se marca el checkbox y es la primera sublínea marcada en esta línea principal, marcar también el checkbox de columna
                        // Buscar en todo el documento, no solo en container
                        const otrosCheckboxesMismaLinea = document.querySelectorAll(`.especificacion-checkbox[data-principal-id="${principalId}"]`);
                        let hayOtraMarcada = false;
                        otrosCheckboxesMismaLinea.forEach(cb => {
                            if (cb !== this && cb.checked) {
                                hayOtraMarcada = true;
                            }
                        });
                        
                        // Si no hay otra sublínea marcada, marcar automáticamente el checkbox de columna
                        if (!hayOtraMarcada && columnaCheckbox && !columnaCheckbox.disabled) {
                            columnaCheckbox.checked = true;
                            // Desmarcar otros checkboxes de columna en la misma línea principal
                            const otrosColumnaCheckboxes = document.querySelectorAll(`.columna-oferta-sublinea-checkbox[data-principal-id="${principalId}"]`);
                            otrosColumnaCheckboxes.forEach(cb => {
                                if (cb !== columnaCheckbox) {
                                    cb.checked = false;
                                }
                            });
                        }
                    }
                }
                
                actualizarEspecificacionesJSON();
                actualizarChipsSeleccionados();
                // Validar columna después de cambiar
                const container = document.getElementById('especificaciones-internas-container');
                if (container) {
                    validarColumnaOferta(container, principalId);
                }
            });
        });
        
        // Añadir event listeners a los checkboxes de columna (solo si es unidadUnica)
        // Buscar en todo el documento, no solo en container, para asegurar que se encuentren todos
        if (esUnidadUnica) {
            // Esperar un momento para que el DOM se actualice completamente
            setTimeout(() => {
                const columnaCheckboxes = document.querySelectorAll('.columna-oferta-sublinea-checkbox');
                columnaCheckboxes.forEach(checkbox => {
                    // Evitar añadir múltiples listeners
                    if (checkbox.dataset.listenerAdded) return;
                    checkbox.dataset.listenerAdded = 'true';
                    
                    checkbox.addEventListener('change', function() {
                        const principalId = this.dataset.principalId;
                        const sublineaId = this.dataset.sublineaId;
                        
                        // Desmarcar otros checkboxes de columna en la misma línea principal (buscar en todo el documento)
                        const otrosCheckboxes = document.querySelectorAll(`.columna-oferta-sublinea-checkbox[data-principal-id="${principalId}"]`);
                        otrosCheckboxes.forEach(cb => {
                            if (cb !== this && cb.checked) {
                                cb.checked = false;
                            }
                        });
                        
                        actualizarEspecificacionesJSON();
                        // Validar columna después de cambiar
                        const container = document.getElementById('especificaciones-internas-container');
                        if (container) {
                            validarColumnaOferta(container, principalId);
                        }
                    });
                });
            }, 100);
        }
        
        // Configurar tooltips con click
        configurarTooltipsOfertas(container);

        // Configurar botones de imágenes (solo lectura)
        configurarBotonesImagenesSublineasOferta(container);
        
        // Validar todas las líneas principales que tienen checkbox de columna al cargar
        if (esUnidadUnica && columnasProducto.length > 0) {
            columnasProducto.forEach(principalId => {
                validarColumnaOferta(container, principalId);
            });
        }
        
        // Actualizar estado inicial del botón de guardar
        setTimeout(() => {
            actualizarEstadoBotonGuardar();
        }, 200);
        
        // Actualizar JSON inicial
        actualizarEspecificacionesJSON();
        actualizarChipsSeleccionados();
    });
}

// ============ IMÁGENES EN SUBLÍNEAS (OFERTAS, SOLO VER) ============
function configurarBotonesImagenesSublineasOferta(contenedor) {
    if (!contenedor) return;
    contenedor.querySelectorAll('.btn-ver-imagenes-sublinea-oferta').forEach(btn => {
        if (btn.dataset.listenerAdded === 'true') return;
        btn.dataset.listenerAdded = 'true';
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation(); // evitar que el click en el botón dispare el toggle del checkbox (está dentro del label)
            const key = this.dataset.key;
            abrirModalImagenesSublineaOferta(key);
        });
    });
}

function resolverUrlImagenOferta(imgPath) {
    const publicBase = @json(url('/'));
    const imagesBase = @json(asset('images/')); // incluye "/" final

    if (!imgPath) return '';
    let p = String(imgPath).trim();
    if (!p) return '';

    // URL absoluta
    if (/^https?:\/\//i.test(p)) return p;
    if (/^\/\//.test(p)) return p;

    // Normalizar: quitar slashes iniciales
    p = p.replace(/^\/+/, '');

    // Si ya viene con "images/..." (muy común), no lo dupliques.
    if (/^images\//i.test(p)) {
        return `${String(publicBase).replace(/\/+$/, '')}/${p}`;
    }

    // Asegurar exactamente una "/" entre base y ruta
    return `${String(imagesBase).replace(/\/+$/, '')}/${p}`;
}

window.abrirModalImagenesSublineaOferta = function(key) {
    const modal = document.getElementById('modal-imagenes-sublinea-oferta');
    if (!modal) return;
    const imagenes = (window.__ofertaImagenesSublinea && window.__ofertaImagenesSublinea[key]) ? window.__ofertaImagenesSublinea[key] : [];
    window.__ofertaImagenesSublineaActual = { key, imagenes: Array.isArray(imagenes) ? imagenes : [] };
    renderizarMiniaturasSublineaOferta();
    modal.classList.remove('hidden');
};

window.cerrarModalImagenesSublineaOferta = function() {
    const modal = document.getElementById('modal-imagenes-sublinea-oferta');
    if (!modal) return;
    modal.classList.add('hidden');
    window.__ofertaImagenesSublineaActual = { key: null, imagenes: [] };
};

function renderizarMiniaturasSublineaOferta() {
    const container = document.getElementById('miniaturas-container-sublinea-oferta');
    const imgGrande = document.getElementById('imagen-grande-sublinea-oferta');
    if (!container || !imgGrande) return;

    container.innerHTML = '';

    const imagenes = (window.__ofertaImagenesSublineaActual && Array.isArray(window.__ofertaImagenesSublineaActual.imagenes))
        ? window.__ofertaImagenesSublineaActual.imagenes
        : [];

    if (imagenes.length === 0) {
        imgGrande.src = '';
        imgGrande.alt = 'No hay imágenes';
        container.innerHTML = '<p class="text-sm text-gray-500 dark:text-gray-400">No hay imágenes</p>';
        return;
    }

    // Mostrar primera imagen como grande
    if (imagenes[0]) {
        imgGrande.src = resolverUrlImagenOferta(imagenes[0]);
        imgGrande.alt = 'Imagen 1';
    }

    // Miniaturas (solo click, sin reordenar)
    imagenes.forEach((imgPath, index) => {
        const div = document.createElement('div');
        div.className = 'miniatura-sublinea-oferta cursor-pointer border-2 border-gray-300 dark:border-gray-600 rounded p-1 hover:border-blue-500 transition-colors';
        if (index === 0) div.classList.add('border-blue-500');

        const url = resolverUrlImagenOferta(imgPath);
        div.innerHTML = `<img src="${url}" alt="Miniatura ${index + 1}" class="w-full h-20 object-cover rounded">`;

        div.addEventListener('click', () => {
            imgGrande.src = url;
            imgGrande.alt = `Imagen ${index + 1}`;
            container.querySelectorAll('.miniatura-sublinea-oferta').forEach(m => m.classList.remove('border-blue-500'));
            div.classList.add('border-blue-500');
        });

        container.appendChild(div);
    });
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        if (!document.getElementById('modal-imagenes-sublinea-oferta')?.classList.contains('hidden')) {
            window.cerrarModalImagenesSublineaOferta();
        }
    }
});

document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('modal-imagenes-sublinea-oferta');
    if (!modal) return;
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            window.cerrarModalImagenesSublineaOferta();
        }
    });
});

// Función para validar si una línea principal con checkbox de columna tiene al menos una opción marcada
function validarColumnaOferta(contenedor, principalId) {
    if (!principalId) return;
    
    const lineaPrincipal = contenedor.querySelector(`.linea-principal-oferta[data-principal-id="${principalId}"]`);
    if (!lineaPrincipal) return;
    
    // Verificar si esta línea principal tiene checkboxes de columna (solo si es unidadUnica y está marcada como columna en el producto)
    const columnaCheckboxes = lineaPrincipal.querySelectorAll('.columna-oferta-sublinea-checkbox');
    if (columnaCheckboxes.length === 0) {
        // Si no tiene checkboxes de columna, quitar validación
        lineaPrincipal.classList.remove('border-red-500');
        lineaPrincipal.classList.add('border-gray-300', 'dark:border-gray-600');
        const errorMsg = lineaPrincipal.querySelector('.error-columna-msg');
        if (errorMsg) errorMsg.style.display = 'none';
        actualizarEstadoBotonGuardar();
        return;
    }
    
    // Verificar si hay al menos una opción de columna marcada
    const columnasMarcadas = lineaPrincipal.querySelectorAll('.columna-oferta-sublinea-checkbox:checked');
    const tieneColumnaMarcada = columnasMarcadas.length > 0;
    
    if (!tieneColumnaMarcada) {
        // Mostrar error: borde rojo y mensaje
        lineaPrincipal.classList.remove('border-gray-300', 'dark:border-gray-600');
        lineaPrincipal.classList.add('border-red-500');
        const errorMsg = lineaPrincipal.querySelector('.error-columna-msg');
        if (errorMsg) errorMsg.style.display = 'inline';
    } else {
        // Ocultar error: borde normal y sin mensaje
        lineaPrincipal.classList.remove('border-red-500');
        lineaPrincipal.classList.add('border-gray-300', 'dark:border-gray-600');
        const errorMsg = lineaPrincipal.querySelector('.error-columna-msg');
        if (errorMsg) errorMsg.style.display = 'none';
    }
    
    // Actualizar estado del botón de guardar
    actualizarEstadoBotonGuardar();
}

// Función para actualizar el estado del botón de guardar basado en las validaciones
function actualizarEstadoBotonGuardar() {
    const btnGuardar = document.getElementById('btn_guardar');
    if (!btnGuardar) return;
    
    const container = document.getElementById('especificaciones-internas-container');
    if (!container) {
        btnGuardar.disabled = false;
        return;
    }
    
    // Verificar si hay líneas principales con checkboxes de columna sin marcar
    const todasLasLineas = container.querySelectorAll('.linea-principal-oferta');
    let hayError = false;
    
    todasLasLineas.forEach(lineaPrincipal => {
        const columnaCheckboxes = lineaPrincipal.querySelectorAll('.columna-oferta-sublinea-checkbox');
        if (columnaCheckboxes.length > 0) {
            let tieneColumnaMarcada = false;
            columnaCheckboxes.forEach(cb => {
                if (cb.checked && !cb.disabled) {
                    tieneColumnaMarcada = true;
                }
            });
            if (!tieneColumnaMarcada) {
                hayError = true;
            }
        }
    });
    
    // Deshabilitar el botón si hay errores
    btnGuardar.disabled = hayError;
    if (hayError) {
        btnGuardar.title = 'Debes marcar una opción de columna en todas las líneas principales que tienen checkboxes de columna';
    } else {
        btnGuardar.title = '';
    }

    // Respetar también el límite máximo de precio
    const preciosOk = typeof window.validarPreciosMaximosEnFormulario === 'function'
        ? window.validarPreciosMaximosEnFormulario()
        : true;
    if (!preciosOk) {
        btnGuardar.disabled = true;
        btnGuardar.title = `El precio total y el precio por unidad no pueden superar ${window.MAX_PRECIO_OFERTA.toString().replace('.', ',')} €`;
    }
}

// Función para configurar tooltips con click en el formulario de ofertas
function configurarTooltipsOfertas(contenedor) {
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

function actualizarEspecificacionesJSON() {
    const inputHidden = document.getElementById('especificaciones_internas_input');
    const checkboxes = document.querySelectorAll('.especificacion-checkbox');
    const especificaciones = {};
    
    // Agrupar checkboxes por línea principal
    checkboxes.forEach(checkbox => {
        const principalId = checkbox.dataset.principalId;
        const sublineaId = checkbox.dataset.sublineaId;
        
        if (!especificaciones[principalId]) {
            especificaciones[principalId] = [];
        }
        
        if (checkbox.checked) {
            especificaciones[principalId].push(sublineaId);
        }
    });
    
    // Limpiar arrays vacíos (si no se seleccionó ninguna sublínea de una línea principal, no se guarda)
    Object.keys(especificaciones).forEach(key => {
        if (especificaciones[key].length === 0) {
            delete especificaciones[key];
        }
    });
    
    // Guardar columnas si existen checkboxes de columna
    const columnaCheckboxes = document.querySelectorAll('.columna-oferta-sublinea-checkbox:checked');
    if (columnaCheckboxes.length > 0) {
        especificaciones._columnas = {};
        columnaCheckboxes.forEach(checkbox => {
            const principalId = checkbox.dataset.principalId;
            const sublineaId = checkbox.dataset.sublineaId;
            especificaciones._columnas[principalId] = sublineaId;
        });
    }
    
    inputHidden.value = JSON.stringify(especificaciones, null, 0);
}

// Función para actualizar los chips de opciones seleccionadas
function actualizarChipsSeleccionados() {
    const inputHidden = document.getElementById('especificaciones_internas_input');
    let especificacionesGuardadas = {};
    if (inputHidden && inputHidden.value) {
        try {
            especificacionesGuardadas = JSON.parse(inputHidden.value);
        } catch (e) {
            return;
        }
    }
    
    // Obtener todos los contenedores principales
    const contenedoresPrincipales = document.querySelectorAll('#especificaciones-internas-container > div > div[data-principal-id]');
    
    contenedoresPrincipales.forEach(contenedorPrincipal => {
        const principalId = contenedorPrincipal.dataset.principalId;
        if (!principalId) return;
        
        const opcionesSeleccionadas = especificacionesGuardadas[principalId] || [];
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

// Cargar especificaciones internas al cargar la página si hay un producto seleccionado
document.addEventListener('DOMContentLoaded', function() {
    const productoId = document.getElementById('producto_id')?.value;
    if (productoId) {
        // Verificar unidad de medida del producto al cargar
        (async function() {
            try {
                const response = await fetch(`/panel-privado/productos/${productoId}`);
                const productoCompleto = await response.json();
                const unidadesInput = document.querySelector('[name="unidades"]');
                
                // Verificar si el producto tiene unidad de medida única
                if (productoCompleto && productoCompleto.unidadDeMedida === 'unidadUnica') {
                    // Establecer unidades a 1.00 y hacer el campo de solo lectura
                    if (unidadesInput) {
                        unidadesInput.value = '1.00';
                        unidadesInput.readOnly = true;
                        unidadesInput.classList.add('opacity-50', 'cursor-not-allowed', 'bg-gray-200', 'dark:bg-gray-600');
                        unidadesInput.classList.remove('opacity-100');
                    }
                } else {
                    // Habilitar el campo si no es unidad única
                    if (unidadesInput) {
                        unidadesInput.readOnly = false;
                        unidadesInput.classList.remove('opacity-50', 'cursor-not-allowed', 'bg-gray-200', 'dark:bg-gray-600');
                        unidadesInput.classList.add('opacity-100');
                    }
                }
            } catch (error) {
                console.error('Error al obtener información del producto:', error);
            }
        })();
        
        // Esperar un poco para asegurar que el DOM esté completamente cargado
        setTimeout(() => {
            cargarEspecificacionesInternas(productoId);
        }, 500);
    }
    
    // Validar antes de enviar el formulario - usar capture para ejecutarse primero
    const form = document.querySelector('form');
    if (form) {
        // Añadir listener con capture: true para que se ejecute antes que otros
        form.addEventListener('submit', function(e) {
            // Validar que se haya seleccionado una opción en cada desplegable (validación antigua)
            const selects = document.querySelectorAll('.especificacion-select');
            let todosSeleccionados = true;
            
            selects.forEach(select => {
                if (!select.value) {
                    todosSeleccionados = false;
                    select.classList.add('border-red-500');
                } else {
                    select.classList.remove('border-red-500');
                }
            });
            
            if (!todosSeleccionados) {
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();
                alert('Por favor, selecciona una opción en todos los desplegables de especificaciones internas.');
                return false;
            }
            
            // Validar columnas - verificar directamente las líneas principales con checkboxes de columna
            const container = document.getElementById('especificaciones-internas-container');
            let errorValidacion = false;
            let mensajeError = '';
            
            if (container) {
                // Buscar todas las líneas principales
                const todasLasLineas = container.querySelectorAll('.linea-principal-oferta');
                
                todasLasLineas.forEach(lineaPrincipal => {
                    // Verificar si esta línea tiene checkboxes de columna
                    const columnaCheckboxes = lineaPrincipal.querySelectorAll('.columna-oferta-sublinea-checkbox');
                    
                    if (columnaCheckboxes.length > 0) {
                        // Esta línea tiene checkboxes de columna, verificar si hay alguna marcada
                        let tieneColumnaMarcada = false;
                        columnaCheckboxes.forEach(cb => {
                            if (cb.checked && !cb.disabled) {
                                tieneColumnaMarcada = true;
                            }
                        });
                        
                        if (!tieneColumnaMarcada) {
                            // No hay ninguna columna marcada, esto es un error
                            errorValidacion = true;
                            const labelLinea = lineaPrincipal.querySelector('label');
                            let nombreLinea = 'línea principal';
                            if (labelLinea) {
                                nombreLinea = labelLinea.textContent.trim()
                                    .replace(/\s*\(Aplica Filtro Categoria\)\s*/g, '')
                                    .replace(/\s*Falta marcar una opcion de columna\s*/g, '')
                                    .trim();
                            }
                            if (!mensajeError.includes(nombreLinea)) {
                                mensajeError += `Debes marcar una opción de columna en la línea "${nombreLinea}".\n`;
                            }
                        }
                    }
                });
            }
            
            // Validación adicional: verificar si hay mensajes de error visibles
            if (!errorValidacion && container) {
                const mensajesError = container.querySelectorAll('.error-columna-msg');
                mensajesError.forEach(msgError => {
                    const estilo = window.getComputedStyle(msgError);
                    const styleInline = msgError.getAttribute('style') || '';
                    const tieneDisplayNone = styleInline.includes('display: none') || styleInline.includes('display:none');
                    const estaVisible = estilo.display !== 'none' && estilo.visibility !== 'hidden' && !tieneDisplayNone;
                    
                    if (estaVisible) {
                        errorValidacion = true;
                        const lineaPrincipal = msgError.closest('.linea-principal-oferta');
                        if (lineaPrincipal) {
                            const labelLinea = lineaPrincipal.querySelector('label');
                            const nombreLinea = labelLinea ? labelLinea.textContent.trim()
                                .replace(/\s*\(Aplica Filtro Categoria\)\s*/g, '')
                                .replace(/\s*Falta marcar una opcion de columna\s*/g, '')
                                .trim() : 'línea principal';
                            if (!mensajeError.includes(nombreLinea)) {
                                mensajeError += `Debes marcar una opción de columna en la línea "${nombreLinea}".\n`;
                            }
                        }
                    }
                });
            }
            
            if (errorValidacion) {
                console.log('Validación de columnas fallida:', mensajeError);
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();
                alert('Error de validación:\n\n' + mensajeError);
                return false;
            }
        }, {capture: true, passive: false}); // capture: true para ejecutarse antes que otros listeners
    }
    
    // Validación adicional - interceptar el click en el botón de submit
    const btnGuardar = document.getElementById('btn_guardar');
    if (btnGuardar) {
        btnGuardar.addEventListener('click', function(e) {
            const container = document.getElementById('especificaciones-internas-container');
            if (!container) return;
            
            let errorValidacion = false;
            let mensajeError = '';
            
            // Buscar todas las líneas principales
            const todasLasLineas = container.querySelectorAll('.linea-principal-oferta');
            
            todasLasLineas.forEach(lineaPrincipal => {
                // Verificar si esta línea tiene checkboxes de columna
                const columnaCheckboxes = lineaPrincipal.querySelectorAll('.columna-oferta-sublinea-checkbox');
                
                if (columnaCheckboxes.length > 0) {
                    // Esta línea tiene checkboxes de columna, verificar si hay alguna marcada
                    let tieneColumnaMarcada = false;
                    columnaCheckboxes.forEach(cb => {
                        if (cb.checked && !cb.disabled) {
                            tieneColumnaMarcada = true;
                        }
                    });
                    
                    if (!tieneColumnaMarcada) {
                        // No hay ninguna columna marcada, esto es un error
                        errorValidacion = true;
                        const labelLinea = lineaPrincipal.querySelector('label');
                        let nombreLinea = 'línea principal';
                        if (labelLinea) {
                            nombreLinea = labelLinea.textContent.trim()
                                .replace(/\s*\(Aplica Filtro Categoria\)\s*/g, '')
                                .replace(/\s*Falta marcar una opcion de columna\s*/g, '')
                                .trim();
                        }
                        if (!mensajeError.includes(nombreLinea)) {
                            mensajeError += `Debes marcar una opción de columna en la línea "${nombreLinea}".\n`;
                        }
                    }
                }
            });
            
            if (errorValidacion) {
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();
                alert('Error de validación:\n\n' + mensajeError);
                return false;
            }
        }, true); // capture: true para ejecutarse antes
    }
});

// ============ GESTIÓN DE GRUPOS DE OFERTAS ============

let gruposOfertasActual = null;
let ofertaActualId = {{ $oferta ? $oferta->id : 'null' }};
let grupoActualOferta = null; // ID del grupo al que pertenece la oferta actual

// Función para verificar unidad de medida y mostrar/ocultar sección de grupos
function actualizarSeccionGrupos(producto) {
    const container = document.getElementById('grupos-ofertas-container');
    if (!container) return;
    
    if (!producto || producto.unidadDeMedida !== 'unidadUnica') {
        container.innerHTML = `
            <div class="p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700 rounded-lg">
                <p class="text-sm text-yellow-800 dark:text-yellow-200">
                    Las ofertas de este producto no se pueden agrupar porque el producto no tiene unidad de medida 'Unidad Única'. 
                    Solo los productos con unidad de medida única pueden agrupar ofertas.
                </p>
            </div>
        `;
        // Ocultar el contenedor de grupos y el botón de crear grupo
        const gruposLista = document.getElementById('grupos-lista');
        const btnCrearContainer = document.getElementById('btn-crear-grupo-container');
        if (gruposLista) gruposLista.innerHTML = '';
        if (btnCrearContainer) btnCrearContainer.classList.add('hidden');
        return;
    }
    
    // Mostrar mensaje y cargar grupos automáticamente
    container.innerHTML = `
        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
            Agrupa esta oferta con otras del mismo producto y tienda para mostrarlas unificadas en una sola fila.
        </p>
    `;
    
    // Cargar grupos automáticamente si hay producto y tienda seleccionados
    const productoId = document.getElementById('producto_id')?.value;
    const tiendaId = document.getElementById('tienda_id')?.value;
    if (productoId && tiendaId) {
        cargarGruposOfertas();
    }
}

// Función para cargar y mostrar grupos
async function cargarGruposOfertas() {
    const productoId = document.getElementById('producto_id').value;
    const tiendaId = document.getElementById('tienda_id').value;
    
    const container = document.getElementById('grupos-ofertas-container');
    const gruposLista = document.getElementById('grupos-lista');
    const btnCrearContainer = document.getElementById('btn-crear-grupo-container');
    
    if (!productoId) {
        return;
    }
    
    if (!tiendaId) {
        return;
    }
    
    // Obtener producto con grupos
    try {
        const response = await fetch(`/panel-privado/productos/${productoId}`);
        const producto = await response.json();
        
        // Validar que el producto sea unidadUnica
        if (!producto || producto.unidadDeMedida !== 'unidadUnica') {
            const gruposLista = document.getElementById('grupos-lista');
            const btnCrearContainer = document.getElementById('btn-crear-grupo-container');
            if (gruposLista) gruposLista.innerHTML = '';
            if (btnCrearContainer) btnCrearContainer.classList.add('hidden');
            return;
        }
        
        gruposOfertasActual = producto.grupos_de_ofertas || {};
        
        // Buscar en qué grupo está la oferta actual (si estamos editando)
        grupoActualOferta = null;
        if (ofertaActualId && ofertaActualId !== 'null') {
            for (const [grupoId, grupoData] of Object.entries(gruposOfertasActual)) {
                if (Array.isArray(grupoData) && grupoData.length >= 2) {
                    const ofertasGrupo = grupoData[1];
                    if (Array.isArray(ofertasGrupo) && ofertasGrupo.includes(Number(ofertaActualId))) {
                        grupoActualOferta = grupoId;
                        break;
                    }
                }
            }
        }
        
        // Cargar ofertas del producto para mostrar en los grupos
        const responseOfertas = await fetch(`/panel-privado/ofertas/producto/${productoId}`);
        const ofertasData = await responseOfertas.json();
        const ofertas = ofertasData.ofertas || [];
        
        // Obtener tiendas para mostrar imágenes
        const tiendasMap = {};
        for (const oferta of ofertas) {
            if (oferta.tienda_id && !tiendasMap[oferta.tienda_id]) {
                const responseTienda = await fetch(`/panel-privado/tiendas/${oferta.tienda_id}`);
                const tienda = await responseTienda.json();
                tiendasMap[oferta.tienda_id] = tienda;
            }
        }
        
        // Mostrar elementos
        container.innerHTML = '';
        btnCrearContainer.classList.remove('hidden');
        
        renderizarGrupos(gruposOfertasActual, ofertas, tiendasMap, tiendaId);
    } catch (error) {
        console.error('Error al cargar grupos:', error);
    }
}

// Función para renderizar grupos
function renderizarGrupos(grupos, ofertas, tiendasMap, tiendaIdActual) {
    const container = document.getElementById('grupos-lista');
    if (!container) return;
    
    let html = '';
    
    // Primero, renderizar la oferta actual como cuadrado
    const tiendaActual = tiendasMap[tiendaIdActual];
    const tiendaActualNombre = tiendaActual?.nombre || 'Tienda seleccionada';
    const tiendaActualImagen = tiendaActual?.url_imagen || null;
    
    // Verificar si la oferta actual pertenece a un grupo
    const perteneceAGrupo = grupoActualOferta !== null;
    
    html += `
        <div id="oferta-actual-drag" 
             class="w-36 h-36 p-3 bg-blue-50 dark:bg-blue-900/20 border-2 border-blue-300 dark:border-blue-700 rounded-lg cursor-move flex flex-col items-center justify-center relative"
             draggable="true">
            ${perteneceAGrupo ? `
                <button type="button" 
                        onclick="event.stopPropagation(); quitarOfertaDelGrupo('${grupoActualOferta}', ${ofertaActualId})" 
                        class="absolute top-2 right-2 px-2 py-1 bg-orange-600 hover:bg-orange-700 text-white text-xs rounded transition z-10"
                        title="Sacar oferta del grupo">
                    Sacar oferta
                </button>
            ` : ''}
            <svg class="w-6 h-6 text-blue-600 dark:text-blue-400 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
            </svg>
            ${tiendaActualImagen 
                ? `<img src="/images/${tiendaActualImagen}" alt="${tiendaActualNombre}" class="w-12 h-12 object-contain rounded mb-1">`
                : `<div class="w-12 h-12 bg-gray-200 dark:bg-gray-600 rounded flex items-center justify-center text-xs text-center mb-1">${tiendaActualNombre.substring(0, 10)}</div>`
            }
            <p class="font-semibold text-gray-900 dark:text-white text-sm text-center">Oferta actual</p>
            <p id="oferta-actual-info" class="text-xs text-gray-600 dark:text-gray-400 text-center mt-1">${perteneceAGrupo ? 'Pertenece a un grupo' : 'Arrastra a un grupo'}</p>
        </div>
    `;
    
    if (!grupos || Object.keys(grupos).length === 0) {
        html += '<p class="text-sm text-gray-500 dark:text-gray-400">No hay grupos creados. Crea uno nuevo para comenzar.</p>';
        container.innerHTML = html;
        configurarDragAndDrop();
        return;
    }
    
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
    
    // Renderizar grupos por tienda como cuadrados
    for (const [tiendaId, gruposTienda] of Object.entries(gruposPorTienda)) {
        const tienda = tiendasMap[tiendaId];
        const tiendaNombre = tienda?.nombre || 'Tienda desconocida';
        const tiendaImagen = tienda?.url_imagen || null;
        
        for (const { grupoId, grupoData } of gruposTienda) {
            const ofertasIds = Array.isArray(grupoData[1]) ? grupoData[1] : [];
            const ofertasGrupo = ofertas.filter(o => ofertasIds.includes(o.id));
            const esGrupoActual = grupoId === grupoActualOferta;
            
            html += `
                <div class="grupo-item w-36 h-36 border-2 rounded-lg ${esGrupoActual ? 'border-green-500 bg-green-50 dark:bg-green-900/20' : 'border-gray-200 dark:border-gray-700'} overflow-hidden cursor-pointer transition-all hover:shadow-lg" 
                     data-grupo-id="${grupoId}" 
                     data-tienda-id="${tiendaId}"
                     onclick="mostrarOfertasGrupo('${grupoId}', ${JSON.stringify(ofertasGrupo).replace(/"/g, '&quot;')})">
                    <div class="h-full p-4 flex flex-col items-center justify-center">
                        ${tiendaImagen 
                            ? `<img src="/images/${tiendaImagen}" alt="${tiendaNombre}" class="w-12 h-12 object-contain rounded mb-1">`
                            : `<div class="w-12 h-12 bg-gray-200 dark:bg-gray-600 rounded flex items-center justify-center text-xs text-center mb-1">${tiendaNombre.substring(0, 10)}</div>`
                        }
                        <p class="font-semibold text-gray-900 dark:text-white text-sm text-center">${tiendaNombre}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 text-center mt-1">${ofertasGrupo.length} oferta${ofertasGrupo.length !== 1 ? 's' : ''}</p>
                        ${esGrupoActual ? '<span class="text-xs text-green-600 dark:text-green-400 font-semibold mt-1">(Actual)</span>' : ''}
                    </div>
                </div>
            `;
        }
    }
    
    container.innerHTML = html;
    configurarDragAndDrop();
}

// Función para configurar drag and drop
function configurarDragAndDrop() {
    const dragElement = document.getElementById('oferta-actual-drag');
    if (!dragElement) return; // Si no existe el elemento, salir
    
    const gruposItems = document.querySelectorAll('.grupo-item');
    
    let elementoArrastrado = null;
    
    dragElement.addEventListener('dragstart', (e) => {
        elementoArrastrado = dragElement;
        dragElement.style.opacity = '0.5';
        e.dataTransfer.effectAllowed = 'move';
    });
    
    dragElement.addEventListener('dragend', () => {
        if (dragElement) dragElement.style.opacity = '1';
        elementoArrastrado = null;
    });
    
    gruposItems.forEach(grupoItem => {
        grupoItem.addEventListener('dragover', (e) => {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
            
            // Remover borde verde de todos
            gruposItems.forEach(item => {
                item.classList.remove('border-green-500', 'bg-green-50', 'dark:bg-green-900/20');
            });
            
            // Añadir borde verde al grupo sobre el que estamos
            grupoItem.classList.add('border-green-500', 'bg-green-50', 'dark:bg-green-900/20');
        });
        
        grupoItem.addEventListener('dragleave', (e) => {
            // Solo remover si realmente salimos del elemento (no de un hijo)
            if (!grupoItem.contains(e.relatedTarget)) {
                const esGrupoActual = grupoItem.dataset.grupoId === grupoActualOferta;
                if (!esGrupoActual) {
                    grupoItem.classList.remove('border-green-500', 'bg-green-50', 'dark:bg-green-900/20');
                }
            }
        });
        
        grupoItem.addEventListener('drop', async (e) => {
            e.preventDefault();
            e.stopPropagation(); // Evitar que se active el onclick del grupo
            
            const productoId = document.getElementById('producto_id').value;
            if (!productoId) return;
            
            // Validar que el producto sea unidadUnica
            try {
                const response = await fetch(`/panel-privado/productos/${productoId}`);
                const producto = await response.json();
                
                if (!producto || producto.unidadDeMedida !== 'unidadUnica') {
                    alert('Las ofertas de este producto no se pueden agrupar porque el producto no tiene unidad de medida \'Unidad Única\'.');
                    return;
                }
            } catch (error) {
                console.error('Error al validar producto:', error);
                return;
            }
            
            const grupoId = grupoItem.dataset.grupoId;
            const tiendaIdGrupo = grupoItem.dataset.tiendaId;
            const tiendaIdActual = document.getElementById('tienda_id').value;
            
            // Validar que la tienda coincida
            if (tiendaIdActual && tiendaIdActual !== tiendaIdGrupo) {
                alert('No puedes añadir esta oferta a este grupo porque pertenece a una tienda diferente.');
                return;
            }
            
            // Añadir oferta al grupo
            await añadirOfertaAGrupo(grupoId, tiendaIdGrupo);
            
            // Recargar grupos
            cargarGruposOfertas();
        });
    });
}

// Función para añadir oferta a un grupo
async function añadirOfertaAGrupo(grupoId, tiendaId) {
    const productoId = document.getElementById('producto_id').value;
    
    if (!gruposOfertasActual) gruposOfertasActual = {};
    
    // Si el grupo no existe, crearlo
    if (!gruposOfertasActual[grupoId]) {
        gruposOfertasActual[grupoId] = [Number(tiendaId), []];
    }
    
    // Obtener ID de oferta
    let ofertaId = null;
    if (ofertaActualId && ofertaActualId !== 'null') {
        ofertaId = Number(ofertaActualId);
    } else {
        // Si es nueva oferta, necesitamos guardarla primero... pero esto se hará al guardar
        // Por ahora, no podemos añadir una oferta nueva a un grupo hasta que esté guardada
        alert('Debes guardar la oferta primero antes de añadirla a un grupo.');
        return;
    }
    
    // Remover oferta de otros grupos primero
    for (const [gId, gData] of Object.entries(gruposOfertasActual)) {
        if (Array.isArray(gData) && gData.length >= 2 && Array.isArray(gData[1])) {
            const index = gData[1].indexOf(ofertaId);
            if (index > -1) {
                gData[1].splice(index, 1);
                // Si el grupo queda vacío, eliminarlo
                if (gData[1].length === 0) {
                    delete gruposOfertasActual[gId];
                }
            }
        }
    }
    
    // Añadir oferta al grupo seleccionado
    if (!gruposOfertasActual[grupoId][1].includes(ofertaId)) {
        gruposOfertasActual[grupoId][1].push(ofertaId);
    }
    
    // Guardar cambios
    await guardarGrupos(productoId);
}

// Función para mostrar ofertas de un grupo seleccionado
window.mostrarOfertasGrupo = function(grupoId, ofertasGrupo) {
    const contenedorOfertas = document.getElementById('ofertas-grupo-seleccionado');
    const listaOfertas = document.getElementById('ofertas-grupo-lista');
    
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
                                onclick="quitarOfertaDelGrupo('${grupoId}', ${oferta.id})" 
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
window.quitarOfertaDelGrupo = async function(grupoId, ofertaId) {
    const productoId = document.getElementById('producto_id').value;
    
    if (!confirm('¿Estás seguro de que quieres quitar esta oferta del grupo?')) {
        return;
    }
    
    if (!gruposOfertasActual || !gruposOfertasActual[grupoId]) {
        return;
    }
    
    const grupoData = gruposOfertasActual[grupoId];
    if (Array.isArray(grupoData) && grupoData.length >= 2 && Array.isArray(grupoData[1])) {
        const index = grupoData[1].indexOf(Number(ofertaId));
        if (index > -1) {
            grupoData[1].splice(index, 1);
            
            // Si el grupo queda vacío, eliminarlo
            if (grupoData[1].length === 0) {
                delete gruposOfertasActual[grupoId];
            }
            
            // Si la oferta quitada es la actual, actualizar grupoActualOferta
            if (ofertaActualId && Number(ofertaActualId) === Number(ofertaId)) {
                grupoActualOferta = null;
            }
            
            // Guardar cambios
            await guardarGrupos(productoId);
            
            // Recargar grupos
            await cargarGruposOfertas();
            
            // Ocultar el listado de ofertas ya que el grupo puede haber cambiado
            const contenedorOfertas = document.getElementById('ofertas-grupo-seleccionado');
            if (contenedorOfertas) {
                contenedorOfertas.classList.add('hidden');
            }
        }
    }
};

// Función para guardar grupos
async function guardarGrupos(productoId) {
    try {
        const response = await fetch(`/panel-privado/productos/${productoId}/grupos-ofertas/actualizar`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ grupos_de_ofertas: gruposOfertasActual })
        });
        
        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.message || 'Error al guardar grupos');
        }
        
        // Actualizar grupo actual
        if (ofertaActualId && ofertaActualId !== 'null') {
            for (const [grupoId, grupoData] of Object.entries(gruposOfertasActual)) {
                if (Array.isArray(grupoData) && grupoData.length >= 2) {
                    const ofertasIds = grupoData[1];
                    if (ofertasIds.includes(Number(ofertaActualId))) {
                        grupoActualOferta = grupoId;
                        break;
                    }
                }
            }
        }
    } catch (error) {
        console.error('Error al guardar grupos:', error);
        alert('Error al guardar los grupos: ' + error.message);
        throw error; // Relanzar para que la función que llama pueda manejarlo
    }
}

// Función para crear nuevo grupo
document.addEventListener('DOMContentLoaded', function() {
    const btnCrearGrupo = document.getElementById('btn-crear-nuevo-grupo');
    if (btnCrearGrupo) {
        btnCrearGrupo.addEventListener('click', async function() {
            const productoId = document.getElementById('producto_id').value;
            const tiendaId = document.getElementById('tienda_id').value;
            
            if (!productoId) {
                alert('Debes seleccionar un producto primero');
                return;
            }
            
            if (!tiendaId) {
                alert('Debes seleccionar una tienda primero');
                return;
            }
            
            // Validar que el producto sea unidadUnica
            try {
                const response = await fetch(`/panel-privado/productos/${productoId}`);
                const producto = await response.json();
                
                if (!producto || producto.unidadDeMedida !== 'unidadUnica') {
                    alert('Las ofertas de este producto no se pueden agrupar porque el producto no tiene unidad de medida \'Unidad Única\'.');
                    return;
                }
            } catch (error) {
                console.error('Error al validar producto:', error);
                alert('Error al validar el producto');
                return;
            }
            
            // Obtener ID de oferta actual
            let ofertaId = null;
            if (ofertaActualId && ofertaActualId !== 'null') {
                ofertaId = Number(ofertaActualId);
            } else {
                alert('Debes guardar la oferta primero antes de añadirla a un grupo.');
                return;
            }
            
            if (!gruposOfertasActual) gruposOfertasActual = {};
            
            // Remover oferta de otros grupos primero
            for (const [gId, gData] of Object.entries(gruposOfertasActual)) {
                if (Array.isArray(gData) && gData.length >= 2 && Array.isArray(gData[1])) {
                    const index = gData[1].indexOf(ofertaId);
                    if (index > -1) {
                        gData[1].splice(index, 1);
                        // Si el grupo queda vacío, eliminarlo
                        if (gData[1].length === 0) {
                            delete gruposOfertasActual[gId];
                        }
                    }
                }
            }
            
            // Generar ID único para el grupo
            const nuevoGrupoId = 'g_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
            
            // Crear nuevo grupo con la oferta actual
            gruposOfertasActual[nuevoGrupoId] = [Number(tiendaId), [ofertaId]];
            grupoActualOferta = nuevoGrupoId;
            
            // Guardar
            await guardarGrupos(productoId);
            
            // Recargar grupos para mostrar el nuevo cuadrado
            await cargarGruposOfertas();
        });
    }
});


// Observar cambios en producto_id y tienda_id para actualizar sección de grupos
const productoIdInput = document.getElementById('producto_id');
const tiendaIdInput = document.getElementById('tienda_id');

if (productoIdInput) {
    productoIdInput.addEventListener('change', async function() {
        const productoId = this.value;
        const tiendaId = tiendaIdInput?.value;
        if (productoId) {
            try {
                const response = await fetch(`/panel-privado/productos/${productoId}`);
                const producto = await response.json();
                actualizarSeccionGrupos(producto);
                if (tiendaId) {
                    cargarGruposOfertas();
                }
            } catch (error) {
                console.error('Error al cargar producto:', error);
            }
        } else {
            const container = document.getElementById('grupos-ofertas-container');
            if (container) {
                container.innerHTML = '<p class="text-sm text-gray-500 dark:text-gray-400">Selecciona un producto con unidad de medida \'Unidad Única\' para gestionar grupos de ofertas.</p>';
            }
            document.getElementById('oferta-actual-drag')?.classList.add('hidden');
            document.getElementById('btn-crear-grupo-container')?.classList.add('hidden');
        }
    });
}

if (tiendaIdInput) {
    tiendaIdInput.addEventListener('change', async function() {
        const productoId = productoIdInput?.value;
        const tiendaId = this.value;
        if (productoId && tiendaId) {
            cargarGruposOfertas();
        }
    });
}

// Cargar sección de grupos al inicio si hay producto
document.addEventListener('DOMContentLoaded', async function() {
    const productoId = document.getElementById('producto_id')?.value;
    const tiendaId = document.getElementById('tienda_id')?.value;
    if (productoId) {
        try {
            const response = await fetch(`/panel-privado/productos/${productoId}`);
            const producto = await response.json();
            actualizarSeccionGrupos(producto);
            if (tiendaId) {
                cargarGruposOfertas();
            }
        } catch (error) {
            console.error('Error al cargar producto:', error);
        }
    }
});

    // ============ HISTORIAL DE TIEMPOS DE ACTUALIZACIÓN ============
    let diasHistorialActual = 30;

    function cargarHistorialTiemposActualizacion(dias) {
        const ofertaId = {{ $oferta ? $oferta->id : 'null' }};
        if (!ofertaId || ofertaId === 'null') {
            return;
        }

        diasHistorialActual = dias;

        // Actualizar botones activos
        document.querySelectorAll('[id^="btn-historial-"]').forEach(btn => {
            btn.classList.remove('bg-blue-600', 'hover:bg-blue-700');
            btn.classList.add('bg-gray-600', 'hover:bg-gray-700');
        });
        document.getElementById(`btn-historial-${dias}`).classList.remove('bg-gray-600', 'hover:bg-gray-700');
        document.getElementById(`btn-historial-${dias}`).classList.add('bg-blue-600', 'hover:bg-blue-700');

        const container = document.getElementById('historial-tiempos-container');
        container.innerHTML = '<div class="text-center text-gray-500 dark:text-gray-400 py-4"><p>Cargando...</p></div>';

        fetch(`/panel-privado/ofertas/${ofertaId}/historial-tiempos-actualizacion?dias=${dias}`, {
            method: 'GET',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                mostrarHistorialTiemposActualizacion(data.historial, data.total);
            } else {
                container.innerHTML = '<div class="text-center text-red-500 py-4"><p>Error al cargar el historial</p></div>';
            }
        })
        .catch(error => {
            console.error('Error al cargar historial:', error);
            container.innerHTML = '<div class="text-center text-red-500 py-4"><p>Error al cargar el historial</p></div>';
        });
    }

    function mostrarHistorialTiemposActualizacion(historial, total) {
        const container = document.getElementById('historial-tiempos-container');
        
        if (total === 0) {
            container.innerHTML = `
                <div class="text-center text-gray-500 dark:text-gray-400 py-8">
                    <p>No hay registros de actualización en este período</p>
                </div>
            `;
            return;
        }

        let html = `
            <div class="bg-gray-800 dark:bg-gray-800 shadow rounded-lg overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-700 dark:divide-gray-600">
                    <thead class="bg-gray-700 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 dark:text-gray-300 uppercase tracking-wider">Fecha</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 dark:text-gray-300 uppercase tracking-wider">Precio Total</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 dark:text-gray-300 uppercase tracking-wider">Tipo</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 dark:text-gray-300 uppercase tracking-wider">Frecuencia Aplicada</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 dark:text-gray-300 uppercase tracking-wider">Frecuencia Calculada</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-700 dark:divide-gray-600">
        `;

        historial.forEach(registro => {
            const tipoClass = registro.tipo_actualizacion === 'automatico' 
                ? 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200' 
                : 'bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200';
            
            const frecuenciaAplicada = registro.frecuencia_aplicada_minutos 
                ? formatearFrecuencia(registro.frecuencia_aplicada_minutos) 
                : '-';
            
            const frecuenciaCalculada = registro.frecuencia_calculada_minutos 
                ? formatearFrecuencia(registro.frecuencia_calculada_minutos) 
                : '-';

            html += `
                <tr class="hover:bg-gray-700 dark:hover:bg-gray-700">
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-200 dark:text-gray-200">
                        ${registro.created_at}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-200 dark:text-gray-200">
                        ${registro.precio_total} €
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 py-1 text-xs font-semibold rounded-full ${tipoClass}">
                            ${registro.tipo_actualizacion === 'automatico' ? 'Automático' : 'Manual'}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-200 dark:text-gray-200">
                        ${frecuenciaAplicada}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-200 dark:text-gray-200">
                        ${frecuenciaCalculada}
                    </td>
                </tr>
            `;
        });

        html += `
                    </tbody>
                </table>
            </div>
            <div class="mt-4 text-sm text-gray-400 dark:text-gray-500 text-center">
                Total: ${total} registro${total !== 1 ? 's' : ''}
            </div>
        `;

        container.innerHTML = html;
    }

    function formatearFrecuencia(minutos) {
        if (!minutos) return '-';
        
        if (minutos % 1440 === 0) {
            const dias = minutos / 1440;
            return `${dias} día${dias !== 1 ? 's' : ''}`;
        } else if (minutos % 60 === 0) {
            const horas = minutos / 60;
            return `${horas} hora${horas !== 1 ? 's' : ''}`;
        } else {
            return `${minutos} minuto${minutos !== 1 ? 's' : ''}`;
        }
    }

    // Configurar el desplegable para cargar historial cuando se abra
    document.addEventListener('DOMContentLoaded', function() {
        const ofertaId = {{ $oferta ? $oferta->id : 'null' }};
        if (ofertaId && ofertaId !== 'null') {
            const detailsElement = document.querySelector('details');
            if (detailsElement) {
                // Detectar cuando se abre el desplegable
                detailsElement.addEventListener('toggle', function() {
                    if (this.open && diasHistorialActual === 30) {
                        // Solo cargar si está cerrado y se abre por primera vez
                        const container = document.getElementById('historial-tiempos-container');
                        if (container && container.textContent.includes('Selecciona un período')) {
                            cargarHistorialTiemposActualizacion(30);
                        }
                    }
                });
            }
        }
    });
</script>
</x-app-layout>