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

        @if($tienda->exists && isset($categoriasSinApiScraping) && $categoriasSinApiScraping->isNotEmpty())
            @php
                $idsSinApi = $categoriasSinApiScraping->values()->all();
                $catsSinApi = \App\Models\Categoria::whereIn('id', $idsSinApi)
                    ->orderBy('nombre')
                    ->get(['id', 'nombre', 'parent_id']);
                $idSet = array_flip($idsSinApi);
                $catsPorId = $catsSinApi->keyBy('id');
                $raicesSinApi = $catsSinApi->filter(function ($c) use ($idSet) {
                    return !$c->parent_id || !isset($idSet[$c->parent_id]);
                });
                $totalSinApi = $catsSinApi->count();
                $renderArbolSinApi = function ($categorias, $catsPorId) use (&$renderArbolSinApi) {
                    echo '<ul class="list-disc pl-5 mt-1 space-y-0.5">';
                    foreach ($categorias as $cat) {
                        $hijos = $catsPorId->filter(fn ($c) => $c->parent_id == $cat->id)->sortBy('nombre')->values();
                        echo '<li>' . e($cat->nombre);
                        if ($hijos->isNotEmpty()) {
                            if ($hijos->count() > 3) {
                                echo '<details class="ml-1 mt-1">';
                                echo '<summary class="cursor-pointer text-xs font-medium text-amber-800 dark:text-amber-200 select-none list-none">';
                                echo 'Ver ' . $hijos->count() . ' subcategorías</summary>';
                                $renderArbolSinApi($hijos, $catsPorId);
                                echo '</details>';
                            } else {
                                $renderArbolSinApi($hijos, $catsPorId);
                            }
                        }
                        echo '</li>';
                    }
                    echo '</ul>';
                };
            @endphp
            <div class="mb-4 p-4 bg-amber-100 dark:bg-amber-900/30 border border-amber-400 text-amber-900 dark:text-amber-100 rounded-lg">
                <p class="font-semibold">⚠️ Categorías con ofertas sin API de scraping configurada</p>
                <p class="text-sm mt-1">Esta tienda tiene ofertas en categorías que no tienen API asignada. Se usará la API por defecto de la tienda, pero conviene configurarla explícitamente por categoría:</p>
                @if($totalSinApi > 3)
                    <details class="mt-2 text-sm">
                        <summary class="cursor-pointer font-medium text-amber-900 dark:text-amber-100 select-none list-none">
                            Ver {{ $totalSinApi }} categorías
                        </summary>
                        @php $renderArbolSinApi($raicesSinApi, $catsPorId); @endphp
                    </details>
                @else
                    <div class="text-sm">
                        @php $renderArbolSinApi($raicesSinApi, $catsPorId); @endphp
                    </div>
                @endif
            </div>
        @endif

        @if($tienda->exists && !empty($flujoScrapingTienda) && (($flujoScrapingTienda['stats']['scrapear']['total'] ?? 0) > 0))
        <details id="mapa-neuronal-tienda-detalle" class="mb-6 rounded-xl border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 shadow-sm group">
            <summary class="cursor-pointer select-none list-none px-4 py-3 flex flex-wrap items-center justify-between gap-2">
                <span class="font-semibold text-gray-800 dark:text-gray-100 flex items-center gap-2">
                    <span class="text-lg" aria-hidden="true">🧠</span>
                    Mapa neuronal — flujo scraping / mostrar
                </span>
                <span class="text-xs text-gray-500 dark:text-gray-400 group-open:hidden">Pulsa para abrir el mapa 2D interactivo</span>
                <span id="mapa-neuronal-resumen" class="hidden group-open:inline text-xs text-orange-600 dark:text-orange-400">
                    Scraping {{ $flujoScrapingTienda['stats']['scrapear']['activas'] }}/{{ $flujoScrapingTienda['stats']['scrapear']['total'] }}
                    · Mostrar {{ $flujoScrapingTienda['stats']['mostrar']['activas'] }}/{{ $flujoScrapingTienda['stats']['mostrar']['total'] }}
                </span>
            </summary>
            <div class="px-4 pb-4 pt-1 border-t border-gray-200 dark:border-gray-600 space-y-3">
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Cada categoría es una neurona; la tienda está en el centro. En modo <strong>Scraping</strong>, cada pulso lleva el color de su API.
                    <span class="text-red-500">Rojo</span> si la señal está cortada o bloqueada en la tienda.
                    Arrastra para desplazarte · rueda para zoom.
                </p>
                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Ver señal de</span>
                    <button type="button" id="mapa-neuronal-modo-scraping"
                        class="mapa-neuronal-modo-btn px-3 py-1.5 text-sm rounded-lg border border-orange-500 bg-orange-50 dark:bg-orange-900/30 text-orange-700 dark:text-orange-300 font-semibold">
                        Scraping
                    </button>
                    <button type="button" id="mapa-neuronal-modo-mostrar"
                        class="mapa-neuronal-modo-btn px-3 py-1.5 text-sm rounded-lg border border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:border-orange-300">
                        Mostrar
                    </button>
                </div>
                <div id="mapa-neuronal-contenedor" class="relative w-full rounded-xl overflow-hidden border border-cyan-900/40 bg-[#030712] cursor-grab active:cursor-grabbing shadow-[inset_0_0_60px_rgba(56,189,248,0.06)]" style="height: min(70vh, 560px);">
                    <div id="mapa-neuronal-cargando" class="absolute inset-0 z-20 flex items-center justify-center text-sm text-gray-400 pointer-events-none hidden">Cargando mapa…</div>
                    <div id="mapa-neuronal-error" class="absolute inset-0 z-20 hidden flex items-center justify-center p-4 text-sm text-red-400 text-center"></div>
                    <canvas id="mapa-neuronal-canvas" class="w-full h-full block touch-none"></canvas>
                    <div id="mapa-neuronal-tooltip" class="hidden absolute z-10 pointer-events-none px-2 py-1 text-xs rounded-md bg-black/80 text-white border border-gray-600 max-w-[14rem] truncate"></div>
                    <details id="mapa-neuronal-leyenda-apis" class="absolute top-2 right-2 z-10 pointer-events-auto max-w-[13rem] rounded-lg border border-cyan-800/50 bg-[#030712]/92 backdrop-blur-sm shadow-lg shadow-cyan-950/40 text-xs font-mono hidden">
                        <summary class="cursor-pointer select-none px-3 py-2 text-cyan-300/90 hover:text-cyan-200 list-none flex items-center justify-between gap-2">
                            <span class="flex items-center gap-1.5">
                                <span class="inline-block w-2 h-2 rounded-full bg-cyan-400 shadow-[0_0_6px_#22d3ee]"></span>
                                <span id="mapa-neuronal-leyenda-titulo">APIs</span>
                            </span>
                            <span class="text-[10px] text-cyan-500/70">▼</span>
                        </summary>
                        <div id="mapa-neuronal-leyenda-lista" class="px-3 pb-2 pt-1 space-y-1.5 border-t border-cyan-900/40 max-h-48 overflow-y-auto"></div>
                    </details>
                    <div class="absolute bottom-2 left-2 right-2 flex flex-wrap gap-3 text-[10px] text-cyan-200/50 pointer-events-none font-mono">
                        <span><span class="inline-block w-2 h-2 rounded-full bg-green-400 mr-1 shadow-[0_0_6px_#4ade80]"></span>Activo</span>
                        <span><span class="inline-block w-2 h-2 rounded-full bg-slate-500 mr-1"></span>Inactivo</span>
                        <span><span class="inline-block w-2 h-2 rounded-full bg-red-500 mr-1 shadow-[0_0_6px_#f87171]"></span>Cortado</span>
                        <span><span class="inline-block w-2 h-2 rounded-full bg-cyan-300 mr-1 animate-pulse shadow-[0_0_8px_#7dd3fc]"></span>Señal</span>
                    </div>
                </div>
            </div>
        </details>
        <script type="application/json" id="mapa-neuronal-datos">@json($flujoScrapingTienda)</script>
        @endif

        <form id="form-tienda" method="POST" action="{{ $tienda->exists ? route('admin.tiendas.update', $tienda) : route('admin.tiendas.store') }}">
            @csrf
            @if($tienda->exists)
            @method('PUT')
            @endif

            <fieldset class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6 space-y-6 border border-gray-200 dark:border-gray-700">
                <legend class="text-lg font-semibold text-gray-700 dark:text-gray-200">Información de la tienda</legend>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                    <div>
                        <label class="block mb-1 font-medium text-gray-700 dark:text-gray-200">Nombre *</label>
                        <input type="text" id="nombre-tienda" name="nombre" required
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
                            value="{{ old('envio_normal', $tienda->envio_normal) }}"
                            class="w-full px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border">
                    </div>



                    <div class="md:col-span-2">
                        <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1 mb-3">
                            <label class="font-medium text-gray-700 dark:text-gray-200">Logo de la tienda</label>
                            <span class="text-sm text-gray-500 dark:text-gray-400">100×45 · Carpeta: <strong class="font-medium">Tiendas</strong></span>
                        </div>

                        <div id="imagen-tienda-container" class="flex flex-wrap gap-3 items-start min-h-[3rem]">
                            <!-- Vista previa generada por JS -->
                        </div>

                        <div class="mt-4 flex flex-wrap items-center gap-3">
                            <button type="button" id="btn-añadir-imagen-tienda" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-4 py-2 rounded-md transition duration-150 ease-in-out">
                                + Añadir imagen
                            </button>
                            <button type="button" id="btn-cambiar-imagen-tienda" class="hidden bg-gray-600 hover:bg-gray-700 text-white font-semibold px-4 py-2 rounded-md">
                                Cambiar imagen
                            </button>
                        </div>

                        <input type="hidden" id="ruta-imagen-tienda" name="url_imagen" value="{{ old('url_imagen', $tienda->url_imagen ?? '') }}">
                        <p id="ruta-imagen-tienda-texto" class="text-xs text-gray-500 dark:text-gray-400 break-all hidden"></p>
                        @error('url_imagen') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
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
                            <span class="relative inline-flex">
                                <button
                                    type="button"
                                    id="api-scraping-help-btn"
                                    aria-expanded="false"
                                    aria-controls="api-scraping-help-tooltip"
                                    class="inline-flex items-center justify-center text-gray-400 hover:text-gray-300 focus:outline-none focus:ring-2 focus:ring-pink-500 rounded-sm"
                                >
                                    <svg class="w-4 h-4 cursor-pointer" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </button>
                                <div id="api-scraping-help-tooltip" class="hidden absolute bottom-full left-0 mb-2 w-80 p-3 bg-gray-900 text-white text-sm rounded-lg shadow-lg z-10">
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
                            <option value="navegadorLocal" {{ old('api', $tienda->api) == 'navegadorLocal' ? 'selected' : '' }}>
                                Navegador local (programa externo)
                            </option>
                            <option value="CSV-Awin" class="js-opcion-csv-awin" {{ old('api', $tienda->api) == 'CSV-Awin' ? 'selected' : '' }}>
                                CSV-Awin (feed de productos)
                            </option>
                        </select>
                        @error('api')
                        <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block mb-1 font-medium text-gray-700 dark:text-gray-200">
                            API Buscar productos en sus categorias
                            <span class="relative inline-flex">
                                <button
                                    type="button"
                                    id="api-productos-help-btn"
                                    aria-expanded="false"
                                    aria-controls="api-productos-help-tooltip"
                                    class="inline-flex items-center justify-center text-gray-400 hover:text-gray-300 focus:outline-none focus:ring-2 focus:ring-pink-500 rounded-sm"
                                >
                                    <svg class="w-4 h-4 cursor-pointer" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </button>
                                <div id="api-productos-help-tooltip" class="hidden absolute bottom-full left-0 mb-2 w-80 p-3 bg-gray-900 text-white text-sm rounded-lg shadow-lg z-10">
                                    <p>API usada para obtener HTML de listados/sitemaps de categoría y descubrir productos nuevos en neo. Puede ser distinta de la API de precios.</p>
                                </div>
                            </span>
                        </label>
                        <select name="api_productos" id="api-productos-select" required
                                class="w-full px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border">
                            @php $apiProductosVal = old('api_productos', $tienda->api_productos ?? $tienda->api); @endphp
                            <option value="">Selecciona una API</option>
                            <option value="miVpsHtml;1" {{ $apiProductosVal == 'miVpsHtml;1' ? 'selected' : '' }}>Mi VPS — Selenium (rápido, 0.8s)</option>
                            <option value="miVpsHtml;2" {{ $apiProductosVal == 'miVpsHtml;2' ? 'selected' : '' }}>Mi VPS — Selenium (normal, 1.2s)</option>
                            <option value="miVpsHtml;3" {{ $apiProductosVal == 'miVpsHtml;3' ? 'selected' : '' }}>Mi VPS — Selenium (WAF duro, 1.8s)</option>
                            <option value="miVpsHtml;4" {{ $apiProductosVal == 'miVpsHtml;4' ? 'selected' : '' }}>Mi VPS — Requests (estático)</option>
                            <option value="miVpsHtml;5" {{ $apiProductosVal == 'miVpsHtml;5' ? 'selected' : '' }}>Mi VPS — Proxies Residenciales (rotativos)</option>
                            <option value="scrapingAnt" {{ $apiProductosVal == 'scrapingAnt' ? 'selected' : '' }}>ScrapingAnt</option>
                            <option value="brightData;false" {{ $apiProductosVal == 'brightData;false' ? 'selected' : '' }}>Bright Data (sin JavaScript)</option>
                            <option value="brightData;true" {{ $apiProductosVal == 'brightData;true' ? 'selected' : '' }}>Bright Data (con JavaScript)</option>
                            <option value="aliexpressOpen" {{ $apiProductosVal == 'aliexpressOpen' ? 'selected' : '' }}>Api de Aliexpress</option>
                            <option value="amazonApi" {{ $apiProductosVal == 'amazonApi' ? 'selected' : '' }}>Amazon API OFICIAL</option>
                            <option value="amazonProductInfo" {{ $apiProductosVal == 'amazonProductInfo' ? 'selected' : '' }}>Amazon Product Info API - RapidAPI</option>
                            <option value="amazonPricing" {{ $apiProductosVal == 'amazonPricing' ? 'selected' : '' }}>Amazon Pricing And Product Info API - RapidAPI</option>
                            <option value="navegadorLocal" {{ $apiProductosVal == 'navegadorLocal' ? 'selected' : '' }}>Navegador local (programa externo)</option>
                            <option value="CSV-Awin" {{ $apiProductosVal == 'CSV-Awin' ? 'selected' : '' }}>CSV-Awin (feed de productos)</option>
                        </select>
                        <p id="api-productos-guardado-estado" class="hidden text-xs mt-1" aria-live="polite"></p>
                        @error('api_productos')
                        <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    @php
                        $urlCsvOld = old('url_csv');
                        if ($urlCsvOld === null) {
                            $urlCsvLista = is_array($tienda->url_csv ?? null) ? $tienda->url_csv : [];
                            $urlCsvTexto = implode("\n", $urlCsvLista);
                        } else {
                            $urlCsvTexto = is_string($urlCsvOld) ? $urlCsvOld : implode("\n", (array) $urlCsvOld);
                        }
                    @endphp
                    <div class="md:col-span-2">
                        <label class="block mb-1 font-medium text-gray-700 dark:text-gray-200">Enlace de descarga CSV</label>
                        <textarea
                            name="url_csv"
                            id="url-csv-input"
                            rows="4"
                            placeholder="https://productdata.awin.com/...&#10;https://..."
                            class="w-full px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border font-mono text-sm"
                        >{{ $urlCsvTexto }}</textarea>
                        <p class="text-xs text-gray-500 mt-1">URL(s) del ZIP de Awin. Puedes indicar uno o varios enlaces (uno por línea). Obligatorio si usas la API CSV-Awin.</p>
                        <p id="url-csv-csv-awin-aviso" class="hidden text-xs text-red-500 mt-1">Indica al menos un enlace de descarga para poder usar CSV-Awin.</p>
                        @error('url_csv')
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
                        <label class="block mb-1 font-medium text-gray-700 dark:text-gray-200">Avisos sin stock: scrapear automáticamente</label>
                        <select name="avisos_sin_stock_scrapear_automatico" required
                                class="w-full px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border">
                            <option value="si" {{ old('avisos_sin_stock_scrapear_automatico', $tienda->avisos_sin_stock_scrapear_automatico ?? 'si') == 'si' ? 'selected' : '' }}>Sí</option>
                            <option value="no" {{ old('avisos_sin_stock_scrapear_automatico', $tienda->avisos_sin_stock_scrapear_automatico ?? 'si') == 'no' ? 'selected' : '' }}>No</option>
                        </select>
                        <p class="text-xs text-gray-500 mt-1">Si está en "Sí", el cron intentará recuperar precio de ofertas con aviso sin stock vencido</p>
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

                    @php
                        // Convertir frecuencia mínima a valor y unidad
                        // Si hay valores old (después de error de validación), usar esos
                        if (old('frecuencia_minima_valor') && old('frecuencia_minima_unidad')) {
                            $frecuencia_minima_valor = old('frecuencia_minima_valor');
                            $frecuencia_minima_unidad = old('frecuencia_minima_unidad');
                        } else {
                            // Si no, convertir desde minutos almacenados
                            $frecuenciaMinima = $tienda->frecuencia_minima_minutos ?? 1440;
                            if ($frecuenciaMinima % 1440 === 0) {
                                $frecuencia_minima_valor = $frecuenciaMinima / 1440;
                                $frecuencia_minima_unidad = 'dias';
                            } elseif ($frecuenciaMinima % 60 === 0) {
                                $frecuencia_minima_valor = $frecuenciaMinima / 60;
                                $frecuencia_minima_unidad = 'horas';
                            } else {
                                $frecuencia_minima_valor = $frecuenciaMinima;
                                $frecuencia_minima_unidad = 'minutos';
                            }
                        }
                        
                        // Convertir frecuencia máxima a valor y unidad
                        // Si hay valores old (después de error de validación), usar esos
                        if (old('frecuencia_maxima_valor') && old('frecuencia_maxima_unidad')) {
                            $frecuencia_maxima_valor = old('frecuencia_maxima_valor');
                            $frecuencia_maxima_unidad = old('frecuencia_maxima_unidad');
                        } else {
                            // Si no, convertir desde minutos almacenados
                            $frecuenciaMaxima = $tienda->frecuencia_maxima_minutos ?? 2880;
                            if ($frecuenciaMaxima % 1440 === 0) {
                                $frecuencia_maxima_valor = $frecuenciaMaxima / 1440;
                                $frecuencia_maxima_unidad = 'dias';
                            } elseif ($frecuenciaMaxima % 60 === 0) {
                                $frecuencia_maxima_valor = $frecuenciaMaxima / 60;
                                $frecuencia_maxima_unidad = 'horas';
                            } else {
                                $frecuencia_maxima_valor = $frecuenciaMaxima;
                                $frecuencia_maxima_unidad = 'minutos';
                            }
                        }
                    @endphp
                    <div class="col-span-1 md:col-span-2">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block mb-1 font-medium text-gray-700 dark:text-gray-200">
                                    Frecuencia mínima
                                    <span class="relative group">
                                        <svg class="w-4 h-4 inline text-gray-400 cursor-help" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        <div class="absolute bottom-full left-0 mb-2 w-80 p-3 bg-gray-900 text-white text-sm rounded-lg shadow-lg opacity-0 group-hover:opacity-100 transition-opacity duration-200 z-10">
                                            <p>Límite mínimo de frecuencia para todas las ofertas de esta tienda. Las ofertas nunca tendrán una frecuencia menor a este valor. Valor por defecto: 1 día.</p>
                                        </div>
                                    </span>
                                </label>
                                <div class="flex gap-2">
                                    <input type="number" step="0.1" min="0.1" name="frecuencia_minima_valor" required
                                        value="{{ old('frecuencia_minima_valor', $frecuencia_minima_valor ?? 1) }}"
                                        class="w-20 px-2 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border text-center">
                                    <select name="frecuencia_minima_unidad" required
                                        class="px-3 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border">
                                        <option value="minutos" {{ old('frecuencia_minima_unidad', $frecuencia_minima_unidad ?? '') == 'minutos' ? 'selected' : '' }}>Minutos</option>
                                        <option value="horas" {{ old('frecuencia_minima_unidad', $frecuencia_minima_unidad ?? '') == 'horas' ? 'selected' : '' }}>Horas</option>
                                        <option value="dias" {{ old('frecuencia_minima_unidad', $frecuencia_minima_unidad ?? '') == 'dias' ? 'selected' : '' }}>Días</option>
                                    </select>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">Mínimo permitido: 15 minutos</p>
                            </div>

                            <div>
                                <label class="block mb-1 font-medium text-gray-700 dark:text-gray-200">
                                    Frecuencia máxima
                                    <span class="relative group">
                                        <svg class="w-4 h-4 inline text-gray-400 cursor-help" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        <div class="absolute bottom-full left-0 mb-2 w-80 p-3 bg-gray-900 text-white text-sm rounded-lg shadow-lg opacity-0 group-hover:opacity-100 transition-opacity duration-200 z-10">
                                            <p>Límite máximo de frecuencia para todas las ofertas de esta tienda. Las ofertas nunca tendrán una frecuencia mayor a este valor. Valor por defecto: 2 días.</p>
                                        </div>
                                    </span>
                                </label>
                                <div class="flex gap-2">
                                    <input type="number" step="0.1" min="0.1" name="frecuencia_maxima_valor" required
                                        value="{{ old('frecuencia_maxima_valor', $frecuencia_maxima_valor ?? 2) }}"
                                        class="w-20 px-2 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border text-center">
                                    <select name="frecuencia_maxima_unidad" required
                                        class="px-3 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border">
                                        <option value="minutos" {{ old('frecuencia_maxima_unidad', $frecuencia_maxima_unidad ?? '') == 'minutos' ? 'selected' : '' }}>Minutos</option>
                                        <option value="horas" {{ old('frecuencia_maxima_unidad', $frecuencia_maxima_unidad ?? '') == 'horas' ? 'selected' : '' }}>Horas</option>
                                        <option value="dias" {{ old('frecuencia_maxima_unidad', $frecuencia_maxima_unidad ?? '') == 'dias' ? 'selected' : '' }}>Días</option>
                                    </select>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">Valor por defecto: 2 días</p>
                            </div>
                        </div>
                    </div>
                </div>
            </fieldset>

            {{-- URL de listado por categoría (Neoobjetivo) --}}
            <script src="//unpkg.com/alpinejs" defer></script>
            <fieldset class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6 space-y-6 border border-gray-200 dark:border-gray-700">
                <legend class="text-lg font-semibold text-gray-700 dark:text-gray-200">Scraping y URL de listado por categoría</legend>

                @error('urls_categoria')
                    <div class="p-3 rounded-lg bg-red-100 dark:bg-red-900/30 border border-red-400 text-red-700 dark:text-red-300 text-sm">
                        {{ $message }}
                    </div>
                @enderror

                @if(isset($mensajeControlador) && $mensajeControlador)
                    <div class="p-3 rounded-lg bg-amber-100 dark:bg-amber-900/30 border border-amber-400 text-amber-800 dark:text-amber-200 text-sm">
                        {{ $mensajeControlador }}
                    </div>
                @endif
                @if(isset($mensajeTipoListado) && $mensajeTipoListado)
                    <div class="p-3 rounded-lg bg-amber-100 dark:bg-amber-900/30 border border-amber-400 text-amber-800 dark:text-amber-200 text-sm">
                        {{ $mensajeTipoListado }}
                    </div>
                @endif
                @if(isset($tipoListadoCategoria) && $tipoListadoCategoria)
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Tipo de listado de categoría: <strong>{{ $tipoListadoCategoria }}</strong>
                        @if($tipoListadoCategoria === 'sitemap')
                            <span class="text-gray-500">(URLs desde sitemap XML)</span>
                        @elseif($tipoListadoCategoria === 'paginacion')
                            <span class="text-gray-500">(varias páginas con siguiente)</span>
                        @elseif($tipoListadoCategoria === 'mostrar_mas')
                            <span class="text-gray-500">(una petición con "ver más")</span>
                        @endif
                    </p>
                @endif
                @php
                    $puedeBuscarProductosCategoria = !empty($tipoListadoCategoria)
                        && in_array($tipoListadoCategoria, ['sitemap', 'paginacion', 'mostrar_mas'], true);
                    $msgBuscarProductosBloqueado = $mensajeTipoListado
                        ?? $mensajeControlador
                        ?? 'Esta tienda no tiene tipo de listado de categoría configurado.';
                @endphp

                <div id="scraping-categorias-tabs" x-data="{ tabScraping: 'buscar' }">
                    @if($tienda->exists && !empty($resumenScrapingCategorias) && ($resumenScrapingCategorias['cat_mos']['total'] ?? 0) > 0)
                        @php $resumenScraping = $resumenScrapingCategorias; @endphp
                        <div id="resumen-scraping-categorias" class="mb-4 p-4 rounded-lg border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/40">
                            <div class="flex flex-wrap items-center gap-x-6 gap-y-3">
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Scraping</span>
                                    <span class="text-sm font-semibold text-gray-800 dark:text-gray-100" title="Categorías con ofertas con scraping activo">
                                        {{ $resumenScraping['cat_scraping']['si'] }}/{{ $resumenScraping['cat_scraping']['total'] }}
                                    </span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Mostrar</span>
                                    <span class="text-sm font-semibold text-gray-800 dark:text-gray-100" title="Categorías con ofertas visibles en comparador">
                                        {{ $resumenScraping['cat_mos']['si'] }}/{{ $resumenScraping['cat_mos']['total'] }}
                                    </span>
                                </div>
                                <div class="flex flex-wrap items-center gap-2 min-w-0">
                                    <span class="text-xs font-medium uppercase text-gray-500 dark:text-gray-400 shrink-0">APIs</span>
                                    <div class="flex flex-wrap items-center gap-2">
                                        @foreach($resumenScraping['cat_api'] as $filaApi)
                                            @php $iconApi = $filaApi['icon']; @endphp
                                            <button
                                                type="button"
                                                class="js-resumen-api-filtro inline-flex items-center gap-1.5 px-2 py-1 rounded-md border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 hover:border-orange-400 dark:hover:border-orange-500 transition-colors"
                                                data-filtro-api="{{ $filaApi['base'] }}"
                                                title="{{ $iconApi['title'] }} — Ver categorías con esta API"
                                            >
                                                <span class="w-6 h-6 text-xs {{ $iconApi['icon_bg'] }} rounded flex items-center justify-center text-white font-bold shrink-0">
                                                    {{ $iconApi['label'] }}
                                                </span>
                                                <span class="text-sm font-medium text-gray-700 dark:text-gray-200">{{ $filaApi['count'] }}</span>
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    <div class="border-b border-gray-200 dark:border-gray-600 mb-4">
                        <nav class="-mb-px flex flex-wrap gap-x-12 gap-y-1" aria-label="Pestañas scraping categorías">
                            <button
                                type="button"
                                id="tab-scraping-buscar"
                                @click="tabScraping = 'buscar'; window.komparadorScrapingCategorias?.cambiarTab('buscar')"
                                class="tab-scraping-categoria border-b-2 py-2 px-2 text-sm font-medium transition-colors"
                                :class="tabScraping === 'buscar' ? 'border-orange-500 text-orange-600 dark:text-orange-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'"
                            >
                                Buscar
                            </button>
                            <button
                                type="button"
                                id="tab-scraping-ver-todas"
                                @click="tabScraping = 'ver-todas'; window.komparadorScrapingCategorias?.cambiarTab('ver-todas')"
                                class="tab-scraping-categoria border-b-2 py-2 px-2 text-sm font-medium transition-colors"
                                :class="tabScraping === 'ver-todas' ? 'border-orange-500 text-orange-600 dark:text-orange-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'"
                            >
                                Ver todas
                            </button>
                            @if($tienda->exists)
                            <button
                                type="button"
                                id="tab-scraping-campo-scraping"
                                @click="tabScraping = 'campo-scraping'; window.komparadorScrapingCategorias?.cambiarTab('campo-scraping')"
                                class="tab-scraping-categoria border-b-2 py-2 px-2 text-sm font-medium transition-colors"
                                :class="tabScraping === 'campo-scraping' ? 'border-orange-500 text-orange-600 dark:text-orange-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'"
                            >
                                Scraping
                            </button>
                            <button
                                type="button"
                                id="tab-scraping-campo-mostrar"
                                @click="tabScraping = 'campo-mostrar'; window.komparadorScrapingCategorias?.cambiarTab('campo-mostrar')"
                                class="tab-scraping-categoria border-b-2 py-2 px-2 text-sm font-medium transition-colors"
                                :class="tabScraping === 'campo-mostrar' ? 'border-orange-500 text-orange-600 dark:text-orange-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'"
                            >
                                Mostrar
                            </button>
                            @endif
                            @if($tienda->exists && !empty($resumenScrapingCategorias) && ($resumenScrapingCategorias['cat_mos']['total'] ?? 0) > 0)
                            <button
                                type="button"
                                id="tab-scraping-filtros"
                                @click="tabScraping = 'filtros'; window.komparadorScrapingCategorias?.cambiarTab('filtros')"
                                class="tab-scraping-categoria border-b-2 py-2 px-2 text-sm font-medium transition-colors"
                                :class="tabScraping === 'filtros' ? 'border-orange-500 text-orange-600 dark:text-orange-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'"
                            >
                                Buscar por filtros
                            </button>
                            @endif
                            @if($tienda->exists)
                            <button
                                type="button"
                                id="tab-scraping-buscar-productos"
                                @click="tabScraping = 'buscar-productos'; window.komparadorScrapingCategorias?.cambiarTab('buscar-productos')"
                                class="tab-scraping-categoria border-b-2 py-2 px-2 text-sm font-medium transition-colors"
                                :class="tabScraping === 'buscar-productos' ? 'border-orange-500 text-orange-600 dark:text-orange-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'"
                            >
                                Buscar productos
                            </button>
                            @endif
                        </nav>
                    </div>

                    <div id="panel-scraping-buscar" class="space-y-3 mb-4">
                        <div>
                            <label for="buscar-categoria-scraping" class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Buscar categoría</label>
                            <div class="relative">
                                <input
                                    type="text"
                                    id="buscar-categoria-scraping"
                                    class="w-full px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-pink-500"
                                    placeholder="Escribe para buscar categorías..."
                                    autocomplete="off"
                                >
                                <div
                                    id="buscar-categoria-scraping-sugerencias"
                                    class="absolute z-50 w-full mt-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md shadow-lg hidden max-h-60 overflow-y-auto"
                                ></div>
                            </div>
                            <p id="buscar-categoria-scraping-hint" class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                Escribe al menos 2 caracteres para ver categorías coincidentes y su ramificación.
                            </p>
                            <p id="buscar-categoria-scraping-sin-resultados" class="hidden text-sm text-amber-600 dark:text-amber-400 mt-1">
                                No se encontraron categorías con ese criterio.
                            </p>
                        </div>
                    </div>

                    @if($tienda->exists)
                    <div id="panel-scraping-buscar-productos" class="oculto space-y-4 mb-4">
                        @if(empty($puedeBuscarProductosCategoria))
                        <div class="p-3 rounded-lg bg-amber-100 dark:bg-amber-900/30 border border-amber-400 text-amber-800 dark:text-amber-200 text-sm">
                            {{ $msgBuscarProductosBloqueado }}
                        </div>
                        @else
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Busca una categoría con URL de listado configurada y ejecuta la extracción de productos usando la API de «Buscar producto en sus categorias».
                            @if(!empty($tipoListadoCategoria))
                                Tipo de listado: <strong>{{ $tipoListadoCategoria }}</strong>.
                            @endif
                        </p>
                        @endif

                        <div>
                            <label for="buscar-categoria-productos" class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Buscar categoría</label>
                            <div class="relative">
                                <input
                                    type="text"
                                    id="buscar-categoria-productos"
                                    class="w-full px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-pink-500"
                                    placeholder="Escribe para buscar categorías con URL de listado..."
                                    autocomplete="off"
                                    @if(empty($puedeBuscarProductosCategoria)) disabled @endif
                                >
                                <div
                                    id="buscar-categoria-productos-sugerencias"
                                    class="absolute z-50 w-full mt-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md shadow-lg hidden max-h-60 overflow-y-auto"
                                ></div>
                            </div>
                            <p id="buscar-categoria-productos-hint" class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                Solo aparecen categorías que tienen al menos una URL de listado rellena.
                            </p>
                            <p id="buscar-categoria-productos-sin-resultados" class="hidden text-sm text-amber-600 dark:text-amber-400 mt-1">
                                No se encontraron categorías con URL de listado para ese criterio.
                            </p>
                        </div>

                        <div id="buscar-productos-workspace" class="hidden space-y-4 p-4 rounded-lg border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-800/50">
                            <div>
                                <p class="text-xs uppercase text-gray-500 dark:text-gray-400 mb-1">Categoría seleccionada</p>
                                <p id="buscar-productos-categoria-nombre" class="text-base font-semibold text-gray-800 dark:text-gray-100"></p>
                            </div>

                            <div id="buscar-productos-url-selector-wrap" class="space-y-2">
                                <label for="buscar-productos-url-select" class="block text-sm font-medium text-gray-700 dark:text-gray-200">URL de listado</label>
                                <select id="buscar-productos-url-select" class="w-full px-3 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border border-gray-300 dark:border-gray-600 text-sm"></select>
                                <input type="hidden" id="buscar-productos-neoobjetivo-id" value="">
                            </div>

                            <p id="buscar-productos-sin-neo-guardado" class="hidden text-sm text-amber-600 dark:text-amber-400">
                                Guarda la tienda para persistir la URL antes de ejecutar la búsqueda.
                            </p>

                            <div class="flex flex-wrap items-center gap-2">
                                <button type="button" id="btn-buscar-productos-ejecutar"
                                    class="inline-flex items-center gap-1.5 px-4 py-2 rounded-md bg-sky-600 hover:bg-sky-700 text-white text-sm font-medium disabled:opacity-40 disabled:cursor-not-allowed"
                                    @if(empty($puedeBuscarProductosCategoria)) disabled @endif>
                                    ▶ Ejecutar
                                </button>
                                <button type="button" id="btn-buscar-productos-pausar"
                                    class="inline-flex items-center gap-1.5 px-4 py-2 rounded-md bg-amber-600 hover:bg-amber-700 text-white text-sm font-medium disabled:opacity-40 disabled:cursor-not-allowed"
                                    disabled title="Pausar">⏸ Pausar</button>
                                <button type="button" id="btn-buscar-productos-stop"
                                    class="inline-flex items-center gap-1.5 px-4 py-2 rounded-md bg-orange-700 hover:bg-orange-800 text-white text-sm font-medium disabled:opacity-40 disabled:cursor-not-allowed"
                                    disabled title="Detener">⏹ Detener</button>
                                <button type="button" id="btn-buscar-productos-log"
                                    class="inline-flex items-center gap-1.5 px-4 py-2 rounded-md bg-violet-600 hover:bg-violet-700 text-white text-sm font-medium disabled:opacity-40 disabled:cursor-not-allowed"
                                    disabled title="Ver log">📋 Log</button>
                            </div>

                            <div id="buscar-productos-progreso-panel" class="js-buscar-productos-progreso hidden text-gray-200 dark:text-gray-300"></div>
                        </div>

                        <p id="buscar-productos-elegir-categoria" class="text-sm text-gray-500 dark:text-gray-400">
                            Selecciona una categoría de la búsqueda para configurar la ejecución.
                        </p>
                    </div>
                    @endif

                    <div id="panel-scraping-filtros" class="oculto space-y-4 mb-4">
                        @if($tienda->exists && !empty($resumenScrapingCategorias) && ($resumenScrapingCategorias['cat_mos']['total'] ?? 0) > 0)
                        <p class="text-sm text-gray-500 dark:text-gray-400">Filtra las categorías con ofertas de esta tienda. Pulsa una API del resumen o elige filtros abajo.</p>
                        <div class="flex flex-wrap gap-x-6 gap-y-4 items-start">
                            <div class="min-w-[200px]">
                                <label for="filtro-scraping-api" class="block text-xs font-medium uppercase text-gray-500 dark:text-gray-400 mb-1">API</label>
                                <select id="filtro-scraping-api" class="w-full px-3 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border border-gray-300 dark:border-gray-600 text-sm">
                                    <option value="">Todas las APIs</option>
                                    @if(!empty($resumenScrapingCategorias['cat_api']))
                                        @foreach($resumenScrapingCategorias['cat_api'] as $filaApi)
                                            <option value="{{ $filaApi['base'] }}">{{ $filaApi['base'] }} ({{ $filaApi['count'] }})</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                            <div>
                                <span class="block text-xs font-medium uppercase text-gray-500 dark:text-gray-400 mb-1">Scraping</span>
                                <div class="flex flex-wrap gap-3 text-sm text-gray-700 dark:text-gray-200">
                                    <label class="inline-flex items-center gap-1.5 cursor-pointer">
                                        <input type="checkbox" id="filtro-scraping-si" checked class="rounded border-gray-300 text-orange-600 focus:ring-orange-500 js-filtro-scraping-opcion" value="si">
                                        <span>Sí</span>
                                    </label>
                                    <label class="inline-flex items-center gap-1.5 cursor-pointer">
                                        <input type="checkbox" id="filtro-scraping-no" checked class="rounded border-gray-300 text-orange-600 focus:ring-orange-500 js-filtro-scraping-opcion" value="no">
                                        <span>No</span>
                                    </label>
                                </div>
                            </div>
                            <div>
                                <span class="block text-xs font-medium uppercase text-gray-500 dark:text-gray-400 mb-1">Mostrar</span>
                                <div class="flex flex-wrap gap-3 text-sm text-gray-700 dark:text-gray-200">
                                    <label class="inline-flex items-center gap-1.5 cursor-pointer">
                                        <input type="checkbox" id="filtro-mostrar-si" checked class="rounded border-gray-300 text-orange-600 focus:ring-orange-500 js-filtro-mostrar-opcion" value="si">
                                        <span>Sí</span>
                                    </label>
                                    <label class="inline-flex items-center gap-1.5 cursor-pointer">
                                        <input type="checkbox" id="filtro-mostrar-no" checked class="rounded border-gray-300 text-orange-600 focus:ring-orange-500 js-filtro-mostrar-opcion" value="no">
                                        <span>No</span>
                                    </label>
                                </div>
                            </div>
                            <div class="flex items-end">
                                <button type="button" id="btn-limpiar-filtros-scraping" class="px-3 py-2 text-sm rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600">
                                    Limpiar filtros
                                </button>
                            </div>
                        </div>
                        <p id="filtro-scraping-sin-resultados" class="hidden text-sm text-amber-600 dark:text-amber-400">
                            Ninguna categoría con ofertas coincide con los filtros seleccionados.
                        </p>
                        @endif
                    </div>

                    @if($tienda->exists)
                    <div id="panel-scraping-campo-scraping" class="oculto space-y-4 mb-4">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Todas las categorías. Busca por nombre y filtra por scraping. Usa el interruptor para cambiar y guardar al instante.</p>
                        <div class="space-y-3">
                            <div>
                                <label for="buscar-campo-scraping" class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Buscar categoría</label>
                                <div class="relative">
                                    <input
                                        type="text"
                                        id="buscar-campo-scraping"
                                        class="w-full px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-pink-500"
                                        placeholder="Escribe para buscar categorías..."
                                        autocomplete="off"
                                    >
                                    <div
                                        id="buscar-campo-scraping-sugerencias"
                                        class="absolute z-50 w-full mt-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md shadow-lg hidden max-h-60 overflow-y-auto"
                                    ></div>
                                </div>
                                <p id="buscar-campo-scraping-hint" class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                    Escribe al menos 2 caracteres para filtrar por nombre.
                                </p>
                                <p id="buscar-campo-scraping-sin-resultados" class="hidden text-sm text-amber-600 dark:text-amber-400 mt-1">
                                    No se encontraron categorías con ese criterio.
                                </p>
                            </div>
                            <div class="flex flex-wrap gap-x-6 gap-y-4 items-start">
                                <div>
                                    <span class="block text-xs font-medium uppercase text-gray-500 dark:text-gray-400 mb-1">Scraping</span>
                                    <div class="flex flex-wrap gap-3 text-sm text-gray-700 dark:text-gray-200">
                                        <label class="inline-flex items-center gap-1.5 cursor-pointer">
                                            <input type="checkbox" id="filtro-tab-scraping-si" checked class="rounded border-gray-300 text-orange-600 focus:ring-orange-500" value="si">
                                            <span>Sí</span>
                                        </label>
                                        <label class="inline-flex items-center gap-1.5 cursor-pointer">
                                            <input type="checkbox" id="filtro-tab-scraping-no" checked class="rounded border-gray-300 text-orange-600 focus:ring-orange-500" value="no">
                                            <span>No</span>
                                        </label>
                                    </div>
                                </div>
                                <div class="flex items-end">
                                    <button type="button" id="btn-limpiar-campo-scraping" class="px-3 py-2 text-sm rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600">
                                        Limpiar filtros
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="panel-scraping-campo-mostrar" class="oculto space-y-4 mb-4">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Todas las categorías. Busca por nombre y filtra por mostrar. Usa el interruptor para cambiar y guardar al instante.</p>
                        <div class="space-y-3">
                            <div>
                                <label for="buscar-campo-mostrar" class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Buscar categoría</label>
                                <div class="relative">
                                    <input
                                        type="text"
                                        id="buscar-campo-mostrar"
                                        class="w-full px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-pink-500"
                                        placeholder="Escribe para buscar categorías..."
                                        autocomplete="off"
                                    >
                                    <div
                                        id="buscar-campo-mostrar-sugerencias"
                                        class="absolute z-50 w-full mt-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md shadow-lg hidden max-h-60 overflow-y-auto"
                                    ></div>
                                </div>
                                <p id="buscar-campo-mostrar-hint" class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                    Escribe al menos 2 caracteres para filtrar por nombre.
                                </p>
                                <p id="buscar-campo-mostrar-sin-resultados" class="hidden text-sm text-amber-600 dark:text-amber-400 mt-1">
                                    No se encontraron categorías con ese criterio.
                                </p>
                            </div>
                            <div class="flex flex-wrap gap-x-6 gap-y-4 items-start">
                                <div>
                                    <span class="block text-xs font-medium uppercase text-gray-500 dark:text-gray-400 mb-1">Mostrar</span>
                                    <div class="flex flex-wrap gap-3 text-sm text-gray-700 dark:text-gray-200">
                                        <label class="inline-flex items-center gap-1.5 cursor-pointer">
                                            <input type="checkbox" id="filtro-tab-mostrar-si" checked class="rounded border-gray-300 text-orange-600 focus:ring-orange-500" value="si">
                                            <span>Sí</span>
                                        </label>
                                        <label class="inline-flex items-center gap-1.5 cursor-pointer">
                                            <input type="checkbox" id="filtro-tab-mostrar-no" checked class="rounded border-gray-300 text-orange-600 focus:ring-orange-500" value="no">
                                            <span>No</span>
                                        </label>
                                    </div>
                                </div>
                                <div class="flex items-end">
                                    <button type="button" id="btn-limpiar-campo-mostrar" class="px-3 py-2 text-sm rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600">
                                        Limpiar filtros
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                <style>
                    [x-cloak] { display: none !important; }
                    #arbol-urls-categoria.modo-buscar-sin-query { display: none; }
                    #arbol-urls-categoria.modo-buscar .js-categoria-row:not(.buscar-visible):not(.buscar-ruta) { display: none !important; }
                    #arbol-urls-categoria.modo-buscar .js-categoria-row.buscar-ruta > .js-categoria-header,
                    #arbol-urls-categoria.modo-buscar .js-categoria-row.buscar-ruta > .js-categoria-api-panel,
                    #arbol-urls-categoria.modo-buscar .js-categoria-row.buscar-ruta > .js-categoria-url-panel {
                        display: none !important;
                    }
                    #arbol-urls-categoria.modo-buscar .js-categoria-row.buscar-ruta {
                        margin-left: 0 !important;
                        padding: 0 !important;
                        background: transparent !important;
                        border: none !important;
                    }
                    #arbol-urls-categoria.modo-buscar .js-categoria-row.buscar-ruta > .js-categoria-children {
                        margin-left: 0 !important;
                        margin-top: 0 !important;
                    }
                    #arbol-urls-categoria.modo-buscar .js-categoria-children.buscar-expandir { display: block !important; }
                    #panel-scraping-buscar.oculto,
                    #panel-scraping-filtros.oculto,
                    #panel-scraping-campo-scraping.oculto,
                    #panel-scraping-campo-mostrar.oculto,
                    #panel-scraping-buscar-productos.oculto { display: none; }
                    #arbol-urls-categoria.arbol-oculto-tab-buscar-productos { display: none !important; }
                    #arbol-urls-categoria.modo-filtros .js-categoria-row:not(.filtro-visible):not(.filtro-ruta) { display: none !important; }
                    #arbol-urls-categoria.modo-filtros .js-categoria-row.filtro-ruta > .js-categoria-header,
                    #arbol-urls-categoria.modo-filtros .js-categoria-row.filtro-ruta > .js-categoria-api-panel,
                    #arbol-urls-categoria.modo-filtros .js-categoria-row.filtro-ruta > .js-categoria-url-panel {
                        display: none !important;
                    }
                    #arbol-urls-categoria.modo-filtros .js-categoria-row.filtro-ruta {
                        margin-left: 0 !important;
                        padding: 0 !important;
                        background: transparent !important;
                        border: none !important;
                    }
                    #arbol-urls-categoria.modo-filtros .js-categoria-row.filtro-ruta > .js-categoria-children {
                        margin-left: 0 !important;
                        margin-top: 0 !important;
                    }
                    #arbol-urls-categoria.modo-filtros .js-categoria-children.filtro-expandir { display: block !important; }
                    #arbol-urls-categoria.modo-campo-filtro .js-categoria-row:not(.campo-visible):not(.campo-ruta) { display: none !important; }
                    #arbol-urls-categoria.modo-campo-filtro .js-categoria-row.campo-ruta > .js-categoria-header,
                    #arbol-urls-categoria.modo-campo-filtro .js-categoria-row.campo-ruta > .js-categoria-api-panel,
                    #arbol-urls-categoria.modo-campo-filtro .js-categoria-row.campo-ruta > .js-categoria-url-panel {
                        display: none !important;
                    }
                    #arbol-urls-categoria.modo-campo-filtro .js-categoria-row.campo-ruta {
                        margin-left: 0 !important;
                        padding: 0 !important;
                        background: transparent !important;
                        border: none !important;
                    }
                    #arbol-urls-categoria.modo-campo-filtro .js-categoria-row.campo-ruta > .js-categoria-children {
                        margin-left: 0 !important;
                        margin-top: 0 !important;
                    }
                    #arbol-urls-categoria.modo-campo-filtro .js-categoria-children.campo-expandir { display: block !important; }
                    #arbol-urls-categoria.modo-tab-scraping .js-acciones-api-url { display: none !important; }
                    #arbol-urls-categoria.modo-tab-scraping .js-toggle-scrapear-header { display: flex !important; }
                    #arbol-urls-categoria.modo-tab-mostrar .js-acciones-api-url { display: none !important; }
                    #arbol-urls-categoria.modo-tab-mostrar .js-toggle-mostrar-header { display: flex !important; }
                    .js-toggle-scrapear-header,
                    .js-toggle-mostrar-header { display: none; }
                    .campo-toggle {
                        padding: 0;
                        border: none;
                        background: transparent;
                        cursor: pointer;
                        line-height: 0;
                        flex-shrink: 0;
                        -webkit-tap-highlight-color: transparent;
                    }
                    .campo-toggle:focus { outline: none; }
                    .campo-toggle:focus-visible .campo-toggle__track {
                        box-shadow:
                            inset 0 1px 3px rgba(0, 0, 0, 0.14),
                            0 0 0 2px rgba(249, 115, 22, 0.55);
                    }
                    .campo-toggle__track {
                        display: block;
                        position: relative;
                        width: 3.25rem;
                        height: 1.875rem;
                        border-radius: 9999px;
                        overflow: hidden;
                        border: 1px solid rgba(0, 0, 0, 0.08);
                        box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.12);
                        background: #374151;
                    }
                    .dark .campo-toggle__track {
                        border-color: rgba(255, 255, 255, 0.1);
                        box-shadow: inset 0 1px 4px rgba(0, 0, 0, 0.45);
                        background: #1f2937;
                    }
                    .campo-toggle__half {
                        position: absolute;
                        top: 0;
                        width: 50%;
                        height: 100%;
                        z-index: 0;
                        pointer-events: none;
                        user-select: none;
                        transition: opacity 0.22s ease;
                    }
                    .campo-toggle__half--si {
                        left: 0;
                        background: linear-gradient(180deg, #4ade80 0%, #16a34a 100%);
                    }
                    .campo-toggle__half--no {
                        right: 0;
                        background: linear-gradient(180deg, #f87171 0%, #dc2626 100%);
                    }
                    .campo-toggle__label {
                        position: absolute;
                        inset: 0;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        font-size: 0.5625rem;
                        font-weight: 700;
                        letter-spacing: 0.04em;
                        text-transform: uppercase;
                        color: rgba(255, 255, 255, 0.92);
                        text-shadow: 0 1px 1px rgba(0, 0, 0, 0.35);
                        opacity: 0;
                        transition: opacity 0.18s ease;
                    }
                    .campo-toggle--no .campo-toggle__half--no .campo-toggle__label,
                    .campo-toggle--si .campo-toggle__half--si .campo-toggle__label {
                        opacity: 1;
                    }
                    .campo-toggle__thumb {
                        position: absolute;
                        top: 2px;
                        left: 2px;
                        width: calc(1.875rem - 4px);
                        height: calc(1.875rem - 4px);
                        border-radius: 50%;
                        background: linear-gradient(180deg, #f3f4f6 0%, #9ca3af 100%);
                        box-shadow:
                            0 2px 6px rgba(0, 0, 0, 0.28),
                            0 0 0 0.5px rgba(0, 0, 0, 0.08),
                            inset 0 1px 0 rgba(255, 255, 255, 0.55);
                        z-index: 2;
                        transition: transform 0.28s cubic-bezier(0.34, 1.35, 0.64, 1);
                        will-change: transform;
                    }
                    .dark .campo-toggle__thumb {
                        background: linear-gradient(180deg, #d1d5db 0%, #6b7280 100%);
                        box-shadow:
                            0 2px 8px rgba(0, 0, 0, 0.45),
                            0 0 0 0.5px rgba(0, 0, 0, 0.2),
                            inset 0 1px 0 rgba(255, 255, 255, 0.25);
                    }
                    .campo-toggle--no .campo-toggle__thumb {
                        transform: translateX(0);
                    }
                    .campo-toggle--si .campo-toggle__thumb {
                        transform: translateX(calc(3.25rem - (1.875rem - 4px) - 4px));
                    }
                    .campo-toggle:active:not([disabled]) .campo-toggle__thumb {
                        transform: scale(0.96);
                    }
                    .campo-toggle--si:active:not([disabled]) .campo-toggle__thumb {
                        transform: translateX(calc(3.25rem - (1.875rem - 4px) - 4px)) scale(0.96);
                    }
                    .campo-toggle--no:active:not([disabled]) .campo-toggle__thumb {
                        transform: translateX(0) scale(0.96);
                    }
                    .js-toggle-campo-categoria[disabled] { opacity: 0.55; cursor: wait; }
                    .js-resumen-api-filtro.activo { border-color: rgb(249 115 22); box-shadow: 0 0 0 1px rgb(249 115 22); }
                    .categoria-api-grid {
                        display: grid;
                        width: 100%;
                        align-items: end;
                        column-gap: 0.5rem;
                        row-gap: 0.35rem;
                        grid-template-columns: minmax(0, 1.2fr) 4.5rem 4.5rem 5.5rem 5.5rem;
                    }
                    .url-categoria-linea-grid {
                        display: grid;
                        width: 100%;
                        align-items: center;
                        column-gap: 0.5rem;
                        row-gap: 0.25rem;
                        grid-template-columns: minmax(0, 1fr) 13rem 1.75rem 1.75rem;
                    }
                    .js-buscar-productos-progreso {
                        margin-left: 0;
                        margin-top: 0.35rem;
                        padding: 0.5rem 0.65rem;
                        border-radius: 0.375rem;
                        background: rgba(17, 24, 39, 0.55);
                        border: 1px solid rgba(75, 85, 99, 0.5);
                        font-size: 0.75rem;
                        line-height: 1.35;
                    }
                    #modal-log-buscar-productos .log-buscar-scroll {
                        max-height: 16rem;
                        overflow-y: auto;
                    }
                    @media (max-width: 768px) {
                        .categoria-api-grid {
                            grid-template-columns: 1fr;
                        }
                        .url-categoria-linea-grid {
                            grid-template-columns: 1fr 1fr;
                        }
                    }
                </style>

                <div id="arbol-urls-categoria" class="space-y-3"
                    @if($tienda->exists) data-tienda-id="{{ $tienda->id }}" @endif
                    @if($tienda->exists)
                    data-url-preparar-buscar="{{ route('admin.tiendas.buscar-productos-categoria.preparar', $tienda) }}"
                    data-url-procesar-pagina="{{ route('admin.tiendas.buscar-productos-categoria.procesar-pagina', $tienda) }}"
                    data-url-api-productos="{{ route('admin.tiendas.api-productos', $tienda) }}"
                    data-url-categoria-url="{{ route('admin.tiendas.categoria-url', $tienda) }}"
                    data-csrf="{{ csrf_token() }}"
                    data-puede-buscar-productos="{{ ($puedeBuscarProductosCategoria ?? false) ? '1' : '0' }}"
                    data-msg-buscar-productos="{{ e($msgBuscarProductosBloqueado ?? 'Esta tienda no tiene tipo de listado de categoría configurado.') }}"
                    @endif
                    data-tienda-api-base="{{ e(\App\Services\TiendaScrapingConfigResolver::apiBase($tienda->api ?? '') ?? '') }}"
                    data-tienda-scrapear="{{ e($tienda->scrapear ?? 'si') }}"
                    data-tienda-mostrar="{{ e($tienda->mostrar_tienda ?? 'si') }}"
                >
                    @php
                        $neoobjetivosPorCategoria = $neoobjetivosPorCategoria ?? collect();
                        $categoriasSinNeoobjetivo = $categoriasSinNeoobjetivo ?? collect();
                        $categoriasAncestrosSinNeo = $categoriasAncestrosSinNeo ?? collect();
                        $conteoTotalOfertas = $conteoTotalOfertas ?? [];
                        $scrapingPorCategoria = $scrapingPorCategoria ?? collect();
                        $categoriasSinApiScraping = $categoriasSinApiScraping ?? collect();
                        $categoriasAncestrosSinApi = $categoriasAncestrosSinApi ?? collect();
                        $categoriasConOfertas = $categoriasConOfertas ?? collect();
                        $conteoDirectoOfertas = $conteoDirectoOfertas ?? [];
                        $resolverFiltroCategorias = app(\App\Services\TiendaScrapingConfigResolver::class);
                        $renderCategorias = function($categorias, $neoobjetivosPorCategoria, $categoriasSinNeoobjetivo, $categoriasAncestrosSinNeo, $conteoTotalOfertas, $scrapingPorCategoria, $categoriasSinApiScraping, $categoriasAncestrosSinApi, $tienda, $conteoDirectoOfertas, $categoriasConOfertas, $resolverFiltroCategorias, $nivel = 0) use (&$renderCategorias) {
                            foreach ($categorias as $categoria) {
                                $oldLineas = old("urls_categoria.{$categoria->id}");
                                if (is_array($oldLineas)) {
                                    $lineasUrl = collect($oldLineas)->values()->map(function ($linea) {
                                        return [
                                            'id' => is_array($linea) ? ($linea['id'] ?? '') : '',
                                            'url' => is_array($linea) ? ($linea['url'] ?? '') : '',
                                            'visitada' => is_array($linea) ? ($linea['visitada'] ?? '') : '',
                                        ];
                                    });
                                } else {
                                    $neos = $neoobjetivosPorCategoria->get($categoria->id, collect());
                                    if ($neos->isEmpty()) {
                                        $lineasUrl = collect([['id' => '', 'url' => '', 'visitada' => '']]);
                                    } else {
                                        $lineasUrl = $neos->map(function ($neo) {
                                            return [
                                                'id' => $neo->id,
                                                'url' => $neo->url ?? '',
                                                'visitada' => $neo->visitada ? $neo->visitada->format('Y-m-d\TH:i') : '',
                                            ];
                                        })->values();
                                    }
                                }
                                if ($lineasUrl->isEmpty()) {
                                    $lineasUrl = collect([['id' => '', 'url' => '', 'visitada' => '']]);
                                }

                                $hasChildren = $categoria->children && $categoria->children->count();
                                $margin = $nivel * 4;
                                $sinNeo = $categoriasSinNeoobjetivo->contains($categoria->id);
                                $esAncestro = !$sinNeo && $categoriasAncestrosSinNeo->contains($categoria->id);
                                $sinApi = $categoriasSinApiScraping->contains($categoria->id);
                                $esAncestroSinApi = !$sinApi && $categoriasAncestrosSinApi->contains($categoria->id);
                                $numOfertas = $conteoTotalOfertas[$categoria->id] ?? 0;

                                $scrapingOld = old("scraping_categoria.{$categoria->id}");
                                $configScraping = $scrapingPorCategoria->get($categoria->id);
                                if (is_array($scrapingOld)) {
                                    $apiCategoria = $scrapingOld['api'] ?? '';
                                    $scrapearCategoria = $scrapingOld['scrapear'] ?? 'si';
                                    $mostrarCategoria = $scrapingOld['mostrar'] ?? 'si';
                                    $fminVal = $scrapingOld['frecuencia_minima_valor'] ?? '';
                                    $fminUni = $scrapingOld['frecuencia_minima_unidad'] ?? 'dias';
                                    $fmaxVal = $scrapingOld['frecuencia_maxima_valor'] ?? '';
                                    $fmaxUni = $scrapingOld['frecuencia_maxima_unidad'] ?? 'dias';
                                } elseif ($configScraping) {
                                    $apiCategoria = $configScraping->api ?? '';
                                    $scrapearCategoria = $configScraping->scrapear ?? 'si';
                                    $mostrarCategoria = $configScraping->mostrar ?? 'si';
                                    [$fminVal, $fminUni] = \App\Http\Controllers\TiendaController::minutosAValorUnidad($configScraping->frecuencia_minima_minutos);
                                    [$fmaxVal, $fmaxUni] = \App\Http\Controllers\TiendaController::minutosAValorUnidad($configScraping->frecuencia_maxima_minutos);
                                } else {
                                    $apiCategoria = '';
                                    $scrapearCategoria = 'si';
                                    $mostrarCategoria = 'si';
                                    $fminVal = $fmaxVal = '';
                                    $fminUni = $fmaxUni = 'dias';
                                }

                                $rowClass = '';
                                if ($sinNeo) {
                                    $rowClass = 'bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded p-2';
                                } elseif ($sinApi) {
                                    $rowClass = 'bg-amber-50 dark:bg-amber-900/20 border border-amber-300 dark:border-amber-700 rounded p-2';
                                }

                                $abrirPanelApi = is_array(old("scraping_categoria.{$categoria->id}"));
                                $abrirPanelUrl = is_array(old("urls_categoria.{$categoria->id}"));

                                $tieneOfertasDirectas = $categoriasConOfertas->contains($categoria->id);
                                $apiExplicitaFiltro = $configScraping && $configScraping->api !== null && $configScraping->api !== '';
                                $scrapearExplicitoFiltro = $configScraping && $configScraping->scrapear !== null && $configScraping->scrapear !== '';
                                $mostrarExplicitoFiltro = $configScraping && $configScraping->mostrar !== null && $configScraping->mostrar !== '';
                                $apiEfectivaResolver = $resolverFiltroCategorias->resolverApi($tienda, $categoria->id);
                                $apiBaseFiltro = \App\Services\TiendaScrapingConfigResolver::apiBase($apiEfectivaResolver) ?? '';
                                $scrapearEfectivoFiltro = $resolverFiltroCategorias->resolverScrapear($tienda, $categoria->id);
                                $mostrarEfectivoFiltro = $resolverFiltroCategorias->resolverMostrar($tienda, $categoria->id);
                                if (is_array($scrapingOld)) {
                                    if (array_key_exists('scrapear', $scrapingOld)) {
                                        $scrapearExplicitoFiltro = true;
                                        $scrapearEfectivoFiltro = $scrapingOld['scrapear'] ?? $scrapearEfectivoFiltro;
                                    }
                                    if (array_key_exists('mostrar', $scrapingOld)) {
                                        $mostrarExplicitoFiltro = true;
                                        $mostrarEfectivoFiltro = $scrapingOld['mostrar'] ?? $mostrarEfectivoFiltro;
                                    }
                                    if (array_key_exists('api', $scrapingOld)) {
                                        $apiExplicitaFiltro = ($scrapingOld['api'] ?? '') !== '';
                                        $apiEfectivaResolver = $apiExplicitaFiltro
                                            ? $scrapingOld['api']
                                            : $resolverFiltroCategorias->resolverApi($tienda, $categoria->id);
                                        $apiBaseFiltro = \App\Services\TiendaScrapingConfigResolver::apiBase($apiEfectivaResolver) ?? '';
                                    }
                                }
                    @endphp

                    <div
                        class="js-categoria-row ml-{{ $margin }} {{ $rowClass }} space-y-2"
                        data-categoria-id="{{ $categoria->id }}"
                        data-categoria-nombre="{{ e(mb_strtolower($categoria->nombre)) }}"
                        data-tiene-ofertas="{{ $tieneOfertasDirectas ? '1' : '0' }}"
                        data-api-base="{{ $apiBaseFiltro }}"
                        data-api-explicita="{{ $apiExplicitaFiltro ? '1' : '0' }}"
                        data-scrapear="{{ $scrapearEfectivoFiltro }}"
                        data-scrapear-explicito="{{ $scrapearExplicitoFiltro ? '1' : '0' }}"
                        data-mostrar="{{ $mostrarEfectivoFiltro }}"
                        data-mostrar-explicito="{{ $mostrarExplicitoFiltro ? '1' : '0' }}"
                        data-sin-neo-inicial="{{ $sinNeo ? '1' : '0' }}"
                        x-data="{ open{{ $categoria->id }}: false, showApi{{ $categoria->id }}: {{ $abrirPanelApi ? 'true' : 'false' }}, showUrl{{ $categoria->id }}: {{ $abrirPanelUrl ? 'true' : 'false' }} }"
                    >
                        <div class="js-categoria-header flex flex-wrap items-center gap-2 w-full">
                            @if($hasChildren)
                                <button type="button"
                                    @click="open{{ $categoria->id }} = !open{{ $categoria->id }}"
                                    class="w-7 h-7 flex items-center justify-center text-white bg-pink-600 hover:bg-pink-700 rounded-full transition shrink-0"
                                    :aria-label="open{{ $categoria->id }} ? 'Contraer' : 'Expandir'">
                                    <span x-text="open{{ $categoria->id }} ? '-' : '+'"></span>
                                </button>
                            @else
                                <span class="w-7 h-7 block shrink-0" aria-hidden="true"></span>
                            @endif
                            <span class="js-categoria-nombre flex-1 min-w-[8rem] text-sm font-medium leading-tight break-words {{ $sinNeo ? 'text-red-700 dark:text-red-300' : ($sinApi ? 'text-amber-800 dark:text-amber-200' : ($esAncestro || $esAncestroSinApi ? 'text-red-600 dark:text-red-400' : 'text-gray-800 dark:text-gray-200')) }}">{{ $categoria->nombre }} <span class="text-gray-500 dark:text-gray-400 font-normal">({{ $numOfertas }})</span></span>
                            <div class="js-toggle-scrapear-header items-center gap-2 shrink-0">
                                <span class="text-xs text-gray-500 dark:text-gray-400">Scraping</span>
                                <button
                                    type="button"
                                    role="switch"
                                    class="js-toggle-campo-categoria js-toggle-scrapear-categoria campo-toggle {{ $scrapearEfectivoFiltro === 'si' ? 'campo-toggle--si' : 'campo-toggle--no' }}"
                                    aria-checked="{{ $scrapearEfectivoFiltro === 'si' ? 'true' : 'false' }}"
                                    data-campo="scrapear"
                                    data-valor="{{ $scrapearEfectivoFiltro }}"
                                    title="Activar o desactivar scraping para esta categoría"
                                >
                                    <span class="campo-toggle__track" aria-hidden="true">
                                        <span class="campo-toggle__half campo-toggle__half--si"><span class="campo-toggle__label">Sí</span></span>
                                        <span class="campo-toggle__half campo-toggle__half--no"><span class="campo-toggle__label">No</span></span>
                                        <span class="campo-toggle__thumb js-toggle-campo-thumb"></span>
                                    </span>
                                </button>
                            </div>
                            <div class="js-toggle-mostrar-header items-center gap-2 shrink-0">
                                <span class="text-xs text-gray-500 dark:text-gray-400">Mostrar</span>
                                <button
                                    type="button"
                                    role="switch"
                                    class="js-toggle-campo-categoria js-toggle-mostrar-categoria campo-toggle {{ $mostrarEfectivoFiltro === 'si' ? 'campo-toggle--si' : 'campo-toggle--no' }}"
                                    aria-checked="{{ $mostrarEfectivoFiltro === 'si' ? 'true' : 'false' }}"
                                    data-campo="mostrar"
                                    data-valor="{{ $mostrarEfectivoFiltro }}"
                                    title="Activar o desactivar mostrar para esta categoría"
                                >
                                    <span class="campo-toggle__track" aria-hidden="true">
                                        <span class="campo-toggle__half campo-toggle__half--si"><span class="campo-toggle__label">Sí</span></span>
                                        <span class="campo-toggle__half campo-toggle__half--no"><span class="campo-toggle__label">No</span></span>
                                        <span class="campo-toggle__thumb js-toggle-campo-thumb"></span>
                                    </span>
                                </button>
                            </div>
                            <div class="js-acciones-api-url flex flex-wrap items-center gap-2 shrink-0">
                            <button type="button"
                                @click="showApi{{ $categoria->id }} = !showApi{{ $categoria->id }}"
                                class="px-2 py-1 text-xs font-medium rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600 shrink-0"
                                :class="showApi{{ $categoria->id }} ? 'ring-2 ring-pink-500 border-pink-500' : ''">
                                <span x-text="showApi{{ $categoria->id }} ? 'Ocultar API' : 'Editar API'"></span>
                            </button>
                            <button type="button"
                                @click="showUrl{{ $categoria->id }} = !showUrl{{ $categoria->id }}; if (showUrl{{ $categoria->id }}) { $nextTick(() => $refs.urlInput{{ $categoria->id }}?.focus()) }"
                                class="px-2 py-1 text-xs font-medium rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600 shrink-0"
                                :class="showUrl{{ $categoria->id }} ? 'ring-2 ring-pink-500 border-pink-500' : ''">
                                <span x-text="showUrl{{ $categoria->id }} ? 'Ocultar URL' : 'Editar URL'"></span>
                            </button>
                            </div>
                        </div>

                        <div class="js-categoria-api-panel ml-9 space-y-1" x-show="showApi{{ $categoria->id }}" x-cloak>
                            <div class="categoria-api-grid">
                                <div class="min-w-0">
                                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-0.5">API</label>
                                    <select
                                        name="scraping_categoria[{{ $categoria->id }}][api]"
                                        class="w-full px-2 py-1 rounded bg-gray-100 dark:bg-gray-700 text-white border text-sm js-api-scraping-select {{ $sinApi ? 'border-amber-500' : '' }}"
                                    >
                                        <option value="">— Sin configurar —</option>
                                        <option value="miVpsHtml;1" {{ $apiCategoria == 'miVpsHtml;1' ? 'selected' : '' }}>Mi VPS — Selenium (rápido)</option>
                                        <option value="miVpsHtml;2" {{ $apiCategoria == 'miVpsHtml;2' ? 'selected' : '' }}>Mi VPS — Selenium (normal)</option>
                                        <option value="miVpsHtml;3" {{ $apiCategoria == 'miVpsHtml;3' ? 'selected' : '' }}>Mi VPS — Selenium (WAF duro)</option>
                                        <option value="miVpsHtml;4" {{ $apiCategoria == 'miVpsHtml;4' ? 'selected' : '' }}>Mi VPS — Requests</option>
                                        <option value="miVpsHtml;5" {{ $apiCategoria == 'miVpsHtml;5' ? 'selected' : '' }}>Mi VPS — Proxies Residenciales</option>
                                        <option value="scrapingAnt" {{ $apiCategoria == 'scrapingAnt' ? 'selected' : '' }}>ScrapingAnt</option>
                                        <option value="brightData;false" {{ $apiCategoria == 'brightData;false' ? 'selected' : '' }}>Bright Data (sin JS)</option>
                                        <option value="brightData;true" {{ $apiCategoria == 'brightData;true' ? 'selected' : '' }}>Bright Data (con JS)</option>
                                        <option value="aliexpressOpen" {{ $apiCategoria == 'aliexpressOpen' ? 'selected' : '' }}>Api de Aliexpress</option>
                                        <option value="amazonApi" {{ $apiCategoria == 'amazonApi' ? 'selected' : '' }}>Amazon API OFICIAL</option>
                                        <option value="amazonProductInfo" {{ $apiCategoria == 'amazonProductInfo' ? 'selected' : '' }}>Amazon Product Info - RapidAPI</option>
                                        <option value="amazonPricing" {{ $apiCategoria == 'amazonPricing' ? 'selected' : '' }}>Amazon Pricing - RapidAPI</option>
                                        <option value="navegadorLocal" {{ $apiCategoria == 'navegadorLocal' ? 'selected' : '' }}>Navegador local</option>
                                        <option value="CSV-Awin" class="js-opcion-csv-awin" {{ $apiCategoria == 'CSV-Awin' ? 'selected' : '' }}>CSV-Awin (feed)</option>
                                    </select>
                                </div>
                                <div class="min-w-0">
                                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-0.5">Scrapear</label>
                                    <select
                                        name="scraping_categoria[{{ $categoria->id }}][scrapear]"
                                        class="js-scraping-scrapear-select w-full px-1 py-1 rounded bg-gray-100 dark:bg-gray-700 text-white border text-xs"
                                    >
                                        <option value="si" {{ $scrapearCategoria === 'si' ? 'selected' : '' }}>Sí</option>
                                        <option value="no" {{ $scrapearCategoria === 'no' ? 'selected' : '' }}>No</option>
                                    </select>
                                </div>
                                <div class="min-w-0">
                                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-0.5">Mostrar</label>
                                    <select
                                        name="scraping_categoria[{{ $categoria->id }}][mostrar]"
                                        class="js-scraping-mostrar-select w-full px-1 py-1 rounded bg-gray-100 dark:bg-gray-700 text-white border text-xs"
                                    >
                                        <option value="si" {{ $mostrarCategoria === 'si' ? 'selected' : '' }}>Sí</option>
                                        <option value="no" {{ $mostrarCategoria === 'no' ? 'selected' : '' }}>No</option>
                                    </select>
                                </div>
                                <div class="min-w-0">
                                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-0.5">Mín</label>
                                    <div class="flex gap-0.5">
                                        <input type="number" step="0.1" min="0.1" placeholder="Mín"
                                            name="scraping_categoria[{{ $categoria->id }}][frecuencia_minima_valor]"
                                            value="{{ $fminVal }}"
                                            class="w-full min-w-0 px-1 py-1 rounded bg-gray-100 dark:bg-gray-700 text-white border text-xs text-center">
                                        <select name="scraping_categoria[{{ $categoria->id }}][frecuencia_minima_unidad]"
                                            class="min-w-0 px-0.5 py-1 rounded bg-gray-100 dark:bg-gray-700 text-white border text-xs">
                                            <option value="minutos" {{ $fminUni === 'minutos' ? 'selected' : '' }}>m</option>
                                            <option value="horas" {{ $fminUni === 'horas' ? 'selected' : '' }}>h</option>
                                            <option value="dias" {{ $fminUni === 'dias' ? 'selected' : '' }}>d</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="min-w-0">
                                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-0.5">Máx</label>
                                    <div class="flex gap-0.5">
                                        <input type="number" step="0.1" min="0.1" placeholder="Máx"
                                            name="scraping_categoria[{{ $categoria->id }}][frecuencia_maxima_valor]"
                                            value="{{ $fmaxVal }}"
                                            class="w-full min-w-0 px-1 py-1 rounded bg-gray-100 dark:bg-gray-700 text-white border text-xs text-center">
                                        <select name="scraping_categoria[{{ $categoria->id }}][frecuencia_maxima_unidad]"
                                            class="min-w-0 px-0.5 py-1 rounded bg-gray-100 dark:bg-gray-700 text-white border text-xs">
                                            <option value="minutos" {{ $fmaxUni === 'minutos' ? 'selected' : '' }}>m</option>
                                            <option value="horas" {{ $fmaxUni === 'horas' ? 'selected' : '' }}>h</option>
                                            <option value="dias" {{ $fmaxUni === 'dias' ? 'selected' : '' }}>d</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="js-categoria-url-panel ml-9" x-show="showUrl{{ $categoria->id }}" x-cloak>
                            <div class="js-urls-categoria-lineas space-y-2 w-full" data-categoria-id="{{ $categoria->id }}" data-siguiente-indice="{{ $lineasUrl->count() }}">
                                @php foreach ($lineasUrl as $idx => $linea): @endphp
                                <div class="js-url-categoria-bloque space-y-1">
                                <div class="js-url-categoria-linea url-categoria-linea-grid">
                                    <div class="min-w-0">
                                        <input type="hidden" name="urls_categoria[{{ $categoria->id }}][{{ $idx }}][id]" value="{{ $linea['id'] }}">
                                        <input
                                            type="url"
                                            name="urls_categoria[{{ $categoria->id }}][{{ $idx }}][url]"
                                            data-id="{{ $categoria->id }}"
                                            placeholder="https://..."
                                            @if($idx === 0) x-ref="urlInput{{ $categoria->id }}" @endif
                                            class="url-categoria-input js-url-categoria w-full px-2 py-1 bg-gray-100 dark:bg-gray-700 text-white border rounded {{ $sinNeo ? 'border-red-500' : '' }}"
                                            value="{{ $linea['url'] }}"
                                        >
                                    </div>
                                    <input
                                        type="datetime-local"
                                        name="urls_categoria[{{ $categoria->id }}][{{ $idx }}][visitada]"
                                        class="js-visitada-categoria w-full min-w-0 px-2 py-1 bg-gray-100 dark:bg-gray-700 text-white border rounded text-sm"
                                        value="{{ $linea['visitada'] }}"
                                    >
                                    <button type="button" class="btn-eliminar-url-categoria w-7 h-7 flex items-center justify-center bg-red-500 hover:bg-red-600 text-white rounded text-sm shrink-0" title="Eliminar línea">−</button>
                                    <button type="button" class="btn-añadir-url-categoria w-7 h-7 flex items-center justify-center bg-green-500 hover:bg-green-600 text-white rounded text-sm shrink-0" title="Añadir línea debajo">+</button>
                                </div>
                                <p class="js-url-categoria-estado hidden text-xs mt-0.5" aria-live="polite"></p>
                                </div>
                                @php endforeach; @endphp
                            </div>
                        </div>

                        @if ($hasChildren)
                            <div class="js-categoria-children space-y-2 mt-2 ml-4" x-show="open{{ $categoria->id }}" x-cloak>
                                @php $renderCategorias($categoria->children, $neoobjetivosPorCategoria, $categoriasSinNeoobjetivo, $categoriasAncestrosSinNeo, $conteoTotalOfertas, $scrapingPorCategoria, $categoriasSinApiScraping, $categoriasAncestrosSinApi, $tienda, $conteoDirectoOfertas, $categoriasConOfertas, $resolverFiltroCategorias, $nivel + 1); @endphp
                            </div>
                        @endif
                    </div>

                    @php
                            }
                        };
                        $renderCategorias($categorias, $neoobjetivosPorCategoria, $categoriasSinNeoobjetivo, $categoriasAncestrosSinNeo, $conteoTotalOfertas, $scrapingPorCategoria, $categoriasSinApiScraping, $categoriasAncestrosSinApi, $tienda, $conteoDirectoOfertas, $categoriasConOfertas, $resolverFiltroCategorias);
                    @endphp
                </div>
                </div>{{-- /scraping-categorias-tabs --}}

                {{-- Modal log búsqueda productos categoría --}}
                <div id="modal-log-buscar-productos" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
                    <div class="bg-gray-800 rounded-xl shadow-xl w-full max-w-3xl max-h-[90vh] flex flex-col border border-gray-600" role="dialog" aria-modal="true" aria-labelledby="modal-log-buscar-productos-titulo">
                        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-600 shrink-0">
                            <h2 id="modal-log-buscar-productos-titulo" class="text-lg font-semibold text-white">Log — búsqueda de productos</h2>
                            <button type="button" id="btn-cerrar-modal-log-buscar-productos" class="text-gray-400 hover:text-white text-2xl leading-none" title="Cerrar">&times;</button>
                        </div>
                        <div id="modal-log-buscar-productos-contenido" class="px-5 py-4 overflow-y-auto text-sm text-gray-200 space-y-4 flex-1"></div>
                        <div class="px-5 py-3 border-t border-gray-600 shrink-0 flex justify-end">
                            <button type="button" id="btn-cerrar-modal-log-buscar-productos-footer" class="px-4 py-2 rounded bg-gray-600 hover:bg-gray-500 text-white text-sm">Cerrar</button>
                        </div>
                    </div>
                </div>

                <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const arbol = document.getElementById('arbol-urls-categoria');
                    if (!arbol) return;

                    const inputBuscar = document.getElementById('buscar-categoria-scraping');
                    const sugerencias = document.getElementById('buscar-categoria-scraping-sugerencias');
                    const hintBuscar = document.getElementById('buscar-categoria-scraping-hint');
                    const sinResultados = document.getElementById('buscar-categoria-scraping-sin-resultados');
                    const panelBuscar = document.getElementById('panel-scraping-buscar');
                    const panelFiltros = document.getElementById('panel-scraping-filtros');
                    const panelCampoScraping = document.getElementById('panel-scraping-campo-scraping');
                    const panelCampoMostrar = document.getElementById('panel-scraping-campo-mostrar');
                    const panelBuscarProductos = document.getElementById('panel-scraping-buscar-productos');
                    const inputBuscarCampoScraping = document.getElementById('buscar-campo-scraping');
                    const inputBuscarCampoMostrar = document.getElementById('buscar-campo-mostrar');
                    const sugerenciasCampoScraping = document.getElementById('buscar-campo-scraping-sugerencias');
                    const sugerenciasCampoMostrar = document.getElementById('buscar-campo-mostrar-sugerencias');
                    const hintCampoScraping = document.getElementById('buscar-campo-scraping-hint');
                    const hintCampoMostrar = document.getElementById('buscar-campo-mostrar-hint');
                    const sinResultadosCampoScraping = document.getElementById('buscar-campo-scraping-sin-resultados');
                    const sinResultadosCampoMostrar = document.getElementById('buscar-campo-mostrar-sin-resultados');
                    const filtroTabScrapingSi = document.getElementById('filtro-tab-scraping-si');
                    const filtroTabScrapingNo = document.getElementById('filtro-tab-scraping-no');
                    const filtroTabMostrarSi = document.getElementById('filtro-tab-mostrar-si');
                    const filtroTabMostrarNo = document.getElementById('filtro-tab-mostrar-no');
                    const btnLimpiarCampoScraping = document.getElementById('btn-limpiar-campo-scraping');
                    const btnLimpiarCampoMostrar = document.getElementById('btn-limpiar-campo-mostrar');
                    const filtroApi = document.getElementById('filtro-scraping-api');
                    const filtroScrapingSi = document.getElementById('filtro-scraping-si');
                    const filtroScrapingNo = document.getElementById('filtro-scraping-no');
                    const filtroMostrarSi = document.getElementById('filtro-mostrar-si');
                    const filtroMostrarNo = document.getElementById('filtro-mostrar-no');
                    const sinResultadosFiltros = document.getElementById('filtro-scraping-sin-resultados');
                    const btnLimpiarFiltros = document.getElementById('btn-limpiar-filtros-scraping');
                    const resumenApiBotones = document.querySelectorAll('.js-resumen-api-filtro');
                    const filtroPorCategoria = @json((object) ($filtroPorCategoria ?? []));
                    const urlGuardarCampoScraping = @json($tienda->exists ? route('admin.tiendas.categoria-scraping.campo', $tienda) : '');
                    const urlGuardarApiProductos = arbol.dataset.urlApiProductos || '';
                    const urlGuardarCategoriaUrl = arbol.dataset.urlCategoriaUrl || '';
                    const debounceGuardadoUrlCategoria = new Map();
                    let debounceGuardadoApiProductos = null;
                    function obtenerCsrfToken() {
                        return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                    }

                    function normalizarTextoBusqueda(texto) {
                        return String(texto || '')
                            .toLowerCase()
                            .normalize('NFD')
                            .replace(/[\u0300-\u036f]/g, '')
                            .replace(/\s+/g, ' ')
                            .trim();
                    }

                    const STOP_WORDS_BUSQUEDA = new Set([
                        'de', 'la', 'el', 'los', 'las', 'y', 'en', 'del', 'al', 'un', 'una',
                        'con', 'por', 'para', 'a', 'e', 'o', 'u',
                    ]);

                    function palabrasBusqueda(query) {
                        return normalizarTextoBusqueda(query)
                            .split(/\s+/)
                            .filter(function(p) {
                                return p.length >= 2 || (p.length === 1 && /\d/.test(p));
                            });
                    }

                    function palabrasSignificativasBusqueda(query) {
                        const palabras = palabrasBusqueda(query);
                        const significativas = palabras.filter(function(p) {
                            return !STOP_WORDS_BUSQUEDA.has(p);
                        });
                        return significativas.length > 0 ? significativas : palabras;
                    }

                    function textoCoincideBusqueda(nombre, query) {
                        const q = normalizarTextoBusqueda(query);
                        if (q.length < 2) return false;

                        const texto = normalizarTextoBusqueda(nombre);
                        if (texto.includes(q)) return true;

                        const palabras = palabrasSignificativasBusqueda(query);
                        if (palabras.length === 0) return false;

                        return palabras.every(function(palabra) {
                            return texto.includes(palabra);
                        });
                    }

                    const sugerenciasTecladoState = new WeakMap();

                    function kpSugerenciasTecladoItems(contenedor) {
                        if (!contenedor) return [];
                        return Array.from(contenedor.querySelectorAll('[data-kp-sugerencia-item]'));
                    }

                    function kpSugerenciasTecladoReset(contenedor) {
                        if (!contenedor) return;
                        sugerenciasTecladoState.set(contenedor, { index: -1 });
                        kpSugerenciasTecladoPintar(contenedor);
                    }

                    function kpSugerenciasTecladoPintar(contenedor) {
                        if (!contenedor) return;
                        const state = sugerenciasTecladoState.get(contenedor) || { index: -1 };
                        const items = kpSugerenciasTecladoItems(contenedor);
                        const activo = ['bg-pink-100', 'dark:bg-pink-900/40', 'ring-2', 'ring-pink-500'];
                        items.forEach(function(item, i) {
                            const on = i === state.index;
                            activo.forEach(function(cls) { item.classList.toggle(cls, on); });
                            item.setAttribute('aria-selected', on ? 'true' : 'false');
                            if (on) item.scrollIntoView({ block: 'nearest' });
                        });
                    }

                    function kpSugerenciasTecladoMover(contenedor, delta) {
                        const items = kpSugerenciasTecladoItems(contenedor);
                        if (!items.length) return;
                        let state = sugerenciasTecladoState.get(contenedor) || { index: -1 };
                        if (delta > 0) {
                            state.index = state.index < items.length - 1 ? state.index + 1 : 0;
                        } else {
                            state.index = state.index > 0 ? state.index - 1 : (state.index === 0 ? -1 : items.length - 1);
                        }
                        sugerenciasTecladoState.set(contenedor, state);
                        kpSugerenciasTecladoPintar(contenedor);
                    }

                    function kpSugerenciasTecladoSeleccionar(contenedor) {
                        const state = sugerenciasTecladoState.get(contenedor) || { index: -1 };
                        const items = kpSugerenciasTecladoItems(contenedor);
                        if (state.index >= 0 && items[state.index]) {
                            items[state.index].click();
                            return true;
                        }
                        return false;
                    }

                    function kpSugerenciasTecladoBind(input, contenedor) {
                        if (!input || !contenedor) return;
                        input.addEventListener('keydown', function(event) {
                            if (contenedor.classList.contains('hidden')) return;
                            if (!kpSugerenciasTecladoItems(contenedor).length) return;

                            if (event.key === 'ArrowDown') {
                                event.preventDefault();
                                kpSugerenciasTecladoMover(contenedor, 1);
                            } else if (event.key === 'ArrowUp') {
                                event.preventDefault();
                                kpSugerenciasTecladoMover(contenedor, -1);
                            } else if (event.key === 'Enter') {
                                if (kpSugerenciasTecladoSeleccionar(contenedor)) {
                                    event.preventDefault();
                                }
                            } else if (event.key === 'Escape') {
                                event.preventDefault();
                                contenedor.classList.add('hidden');
                                kpSugerenciasTecladoReset(contenedor);
                            }
                        });
                    }

                    function mostrarEstadoGuardadoInline(el, texto, tipo) {
                        if (!el) return;
                        el.textContent = texto;
                        el.classList.remove(
                            'hidden',
                            'text-green-600',
                            'dark:text-green-400',
                            'text-red-500',
                            'dark:text-red-400',
                            'text-amber-600',
                            'dark:text-amber-400'
                        );

                        if (tipo === 'ok') {
                            el.classList.add('text-green-600', 'dark:text-green-400');
                            window.setTimeout(function() {
                                el.classList.add('hidden');
                            }, 2500);
                        } else if (tipo === 'error') {
                            el.classList.add('text-red-500', 'dark:text-red-400');
                        } else {
                            el.classList.add('text-amber-600', 'dark:text-amber-400');
                        }
                    }

                    function obtenerDatosUrlBloque(bloque) {
                        const contenedor = bloque.closest('.js-urls-categoria-lineas');
                        const fila = bloque.closest('.js-categoria-row');
                        const hiddenNeo = bloque.querySelector('input[type="hidden"]');
                        const inputUrl = bloque.querySelector('.js-url-categoria');
                        const inputVisitada = bloque.querySelector('.js-visitada-categoria');

                        return {
                            bloque: bloque,
                            categoriaId: contenedor ? contenedor.dataset.categoriaId : (fila ? fila.dataset.categoriaId : ''),
                            neoId: parseInt(hiddenNeo?.value || '0', 10) || null,
                            url: (inputUrl?.value || '').trim(),
                            visitada: (inputVisitada?.value || '').trim(),
                            hiddenNeo: hiddenNeo,
                            inputVisitada: inputVisitada,
                            estadoEl: bloque.querySelector('.js-url-categoria-estado'),
                            fila: fila,
                        };
                    }

                    async function guardarUrlCategoriaBloque(bloque, opciones) {
                        if (!urlGuardarCategoriaUrl || !bloque) {
                            return { ok: false, error: 'No se puede guardar la URL.' };
                        }

                        const relanzarError = opciones && opciones.relanzarError === true;
                        const datos = obtenerDatosUrlBloque(bloque);
                        if (!datos.categoriaId) {
                            return { ok: false, error: 'Categoría no identificada.' };
                        }

                        mostrarEstadoGuardadoInline(datos.estadoEl, 'Guardando…', 'pending');

                        try {
                            const response = await fetch(urlGuardarCategoriaUrl, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': obtenerCsrfToken(),
                                    'X-Requested-With': 'XMLHttpRequest',
                                },
                                body: JSON.stringify({
                                    categoria_id: parseInt(datos.categoriaId, 10),
                                    neo_id: datos.neoId,
                                    url: datos.url,
                                    visitada: datos.visitada || null,
                                }),
                            });
                            const data = await response.json().catch(function() { return {}; });
                            if (!response.ok || !data.ok) {
                                throw new Error(data.message || 'Error al guardar la URL');
                            }

                            if (datos.hiddenNeo) {
                                datos.hiddenNeo.value = data.neo_id ? String(data.neo_id) : '';
                            }
                            if (data.visitada && datos.inputVisitada && !datos.inputVisitada.value) {
                                datos.inputVisitada.value = data.visitada;
                            }

                            if (datos.fila && datos.url !== '') {
                                datos.fila.dataset.sinNeoInicial = '0';
                            }

                            mostrarEstadoGuardadoInline(datos.estadoEl, 'Guardado ✓', 'ok');
                            document.dispatchEvent(new CustomEvent('url-categoria-cambiada'));
                            return {
                                ok: true,
                                neo_id: data.neo_id || datos.neoId || null,
                                visitada: data.visitada || null,
                            };
                        } catch (error) {
                            mostrarEstadoGuardadoInline(
                                datos.estadoEl,
                                error.message || 'Error al guardar',
                                'error'
                            );
                            if (relanzarError) {
                                throw error;
                            }
                            return { ok: false, error: error.message || 'Error al guardar' };
                        }
                    }

                    async function flushGuardadoUrlCategoriaBloque(bloque) {
                        if (!bloque) {
                            return { ok: false, error: 'Bloque de URL no encontrado.' };
                        }
                        clearTimeout(debounceGuardadoUrlCategoria.get(bloque));
                        debounceGuardadoUrlCategoria.delete(bloque);
                        return guardarUrlCategoriaBloque(bloque, { relanzarError: true });
                    }

                    function obtenerBloqueUrlCategoria(categoriaId, indiceUrl) {
                        const fila = arbol.querySelector('.js-categoria-row[data-categoria-id="' + categoriaId + '"]');
                        if (!fila) return null;
                        const bloques = fila.querySelectorAll('.js-url-categoria-bloque');
                        const idx = typeof indiceUrl === 'number' && indiceUrl >= 0 ? indiceUrl : 0;
                        return bloques[idx] || bloques[0] || null;
                    }

                    function programarGuardadoUrlCategoria(bloque, delayMs) {
                        if (!bloque) return;
                        const key = bloque;
                        clearTimeout(debounceGuardadoUrlCategoria.get(key));
                        debounceGuardadoUrlCategoria.set(
                            key,
                            window.setTimeout(function() {
                                guardarUrlCategoriaBloque(bloque);
                            }, delayMs || 600)
                        );
                    }

                    async function guardarApiProductosSelect() {
                        const select = document.getElementById('api-productos-select');
                        const estado = document.getElementById('api-productos-guardado-estado');
                        if (!select || !urlGuardarApiProductos) return;

                        const apiProductos = select.value;
                        if (!apiProductos) {
                            mostrarEstadoGuardadoInline(estado, 'Selecciona una API', 'error');
                            return;
                        }

                        mostrarEstadoGuardadoInline(estado, 'Guardando…', 'pending');

                        try {
                            const response = await fetch(urlGuardarApiProductos, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': obtenerCsrfToken(),
                                    'X-Requested-With': 'XMLHttpRequest',
                                },
                                body: JSON.stringify({ api_productos: apiProductos }),
                            });
                            const data = await response.json();
                            if (!response.ok || !data.ok) {
                                throw new Error(data.message || 'Error al guardar la API');
                            }
                            mostrarEstadoGuardadoInline(estado, 'Guardado ✓', 'ok');
                        } catch (error) {
                            mostrarEstadoGuardadoInline(estado, error.message || 'Error al guardar', 'error');
                        }
                    }

                    const selectApiProductos = document.getElementById('api-productos-select');
                    if (selectApiProductos && urlGuardarApiProductos) {
                        selectApiProductos.addEventListener('change', function() {
                            clearTimeout(debounceGuardadoApiProductos);
                            debounceGuardadoApiProductos = window.setTimeout(guardarApiProductosSelect, 200);
                        });
                    }

                    arbol.addEventListener('input', function(e) {
                        if (e.target.classList.contains('js-url-categoria') || e.target.classList.contains('js-visitada-categoria')) {
                            const bloque = e.target.closest('.js-url-categoria-bloque');
                            if (bloque) {
                                programarGuardadoUrlCategoria(bloque, 600);
                            }
                        }
                    });

                    arbol.addEventListener('focusout', function(e) {
                        if (e.target.classList.contains('js-url-categoria') || e.target.classList.contains('js-visitada-categoria')) {
                            const bloque = e.target.closest('.js-url-categoria-bloque');
                            if (bloque) {
                                programarGuardadoUrlCategoria(bloque, 150);
                            }
                        }
                    });

                    let tabScrapingActiva = 'buscar';
                    let debounceBuscar = null;
                    let debounceBuscarCampoScraping = null;
                    let debounceBuscarCampoMostrar = null;

                    function setAlpineTabScraping(tab) {
                        const tabsEl = document.getElementById('scraping-categorias-tabs');
                        if (tabsEl && tabsEl._x_dataStack && tabsEl._x_dataStack[0]) {
                            tabsEl._x_dataStack[0].tabScraping = tab;
                        }
                    }

                    function apiBaseDesdeValor(api) {
                        if (!api) return '';
                        return api.split(';')[0];
                    }

                    function apiBaseEfectivoFila(fila) {
                        const apiSelect = fila.querySelector('.js-api-scraping-select');
                        const defecto = arbol.dataset.tiendaApiBase || '';
                        const apiVal = apiSelect ? apiSelect.value : '';
                        if (apiVal === '') {
                            return defecto;
                        }
                        return apiBaseDesdeValor(apiVal);
                    }

                    function scrapearEfectivoFila(fila) {
                        const select = fila.querySelector('.js-scraping-scrapear-select');
                        const defecto = arbol.dataset.tiendaScrapear || 'si';
                        if (fila.dataset.scrapearExplicito !== '1') {
                            return defecto;
                        }
                        return select ? select.value : defecto;
                    }

                    function mostrarEfectivoFila(fila) {
                        const select = fila.querySelector('.js-scraping-mostrar-select');
                        const defecto = arbol.dataset.tiendaMostrar || 'si';
                        if (fila.dataset.mostrarExplicito !== '1') {
                            return defecto;
                        }
                        return select ? select.value : defecto;
                    }

                    function obtenerDatosFiltroEfectivos(fila) {
                        const catId = String(fila.dataset.categoriaId || '');
                        if (!catId || !Object.prototype.hasOwnProperty.call(filtroPorCategoria, catId)) {
                            return null;
                        }

                        const datos = Object.assign({}, filtroPorCategoria[catId]);
                        const tiendaApiBase = arbol.dataset.tiendaApiBase || '';

                        if (fila.dataset.apiExplicita === '1') {
                            const apiVal = fila.querySelector('.js-api-scraping-select')?.value || '';
                            datos.api_base = apiVal ? apiBaseDesdeValor(apiVal) : tiendaApiBase;
                        }
                        if (fila.dataset.scrapearExplicito === '1') {
                            datos.scrapear = fila.querySelector('.js-scraping-scrapear-select')?.value || datos.scrapear;
                        }
                        if (fila.dataset.mostrarExplicito === '1') {
                            datos.mostrar = fila.querySelector('.js-scraping-mostrar-select')?.value || datos.mostrar;
                        }

                        return datos;
                    }

                    function actualizarDatosFiltroFila(fila) {
                        const apiSelect = fila.querySelector('.js-api-scraping-select');
                        const apiVal = apiSelect ? apiSelect.value : '';

                        fila.dataset.apiExplicita = apiVal !== '' ? '1' : '0';
                        fila.dataset.apiBase = apiBaseEfectivoFila(fila);
                        fila.dataset.scrapear = scrapearEfectivoFila(fila);
                        fila.dataset.mostrar = mostrarEfectivoFila(fila);
                    }

                    function actualizarResumenApiActivo(apiValor) {
                        resumenApiBotones.forEach(function(btn) {
                            btn.classList.toggle('activo', btn.dataset.filtroApi === apiValor && apiValor !== '');
                        });
                    }

                    function todasFilasCategoria() {
                        return arbol.querySelectorAll('.js-categoria-row');
                    }

                    function filasDescendientes(fila) {
                        return Array.from(fila.querySelectorAll('.js-categoria-row')).filter(function(r) { return r !== fila; });
                    }

                    function filasAncestros(fila) {
                        const ancestros = [];
                        let padre = fila.parentElement ? fila.parentElement.closest('.js-categoria-row') : null;
                        while (padre) {
                            ancestros.push(padre);
                            padre = padre.parentElement ? padre.parentElement.closest('.js-categoria-row') : null;
                        }
                        return ancestros;
                    }

                    function limpiarFiltroBuscar() {
                        arbol.classList.remove('modo-buscar', 'modo-buscar-sin-query');
                        todasFilasCategoria().forEach(function(fila) {
                            fila.classList.remove('buscar-visible', 'buscar-ruta');
                            const hijos = fila.querySelector(':scope > .js-categoria-children');
                            if (hijos) hijos.classList.remove('buscar-expandir');
                        });
                        if (sinResultados) sinResultados.classList.add('hidden');
                    }

                    function limpiarFiltroAvanzado() {
                        arbol.classList.remove('modo-filtros');
                        todasFilasCategoria().forEach(function(fila) {
                            fila.classList.remove('filtro-visible', 'filtro-ruta');
                            const hijos = fila.querySelector(':scope > .js-categoria-children');
                            if (hijos) hijos.classList.remove('filtro-expandir');
                        });
                        if (sinResultadosFiltros) sinResultadosFiltros.classList.add('hidden');
                        actualizarResumenApiActivo('');
                    }

                    function limpiarFiltroCampoTab() {
                        arbol.classList.remove('modo-campo-filtro');
                        todasFilasCategoria().forEach(function(fila) {
                            fila.classList.remove('campo-visible', 'campo-ruta');
                            const hijos = fila.querySelector(':scope > .js-categoria-children');
                            if (hijos) hijos.classList.remove('campo-expandir');
                        });
                        if (sinResultadosCampoScraping) sinResultadosCampoScraping.classList.add('hidden');
                        if (sinResultadosCampoMostrar) sinResultadosCampoMostrar.classList.add('hidden');
                    }

                    function marcarRutaCampoVisible(fila) {
                        fila.classList.add('campo-visible');
                        filasAncestros(fila).forEach(function(anc) {
                            anc.classList.add('campo-ruta');
                            const hijosAnc = anc.querySelector(':scope > .js-categoria-children');
                            if (hijosAnc) hijosAnc.classList.add('campo-expandir');
                        });
                    }

                    function aplicarFiltroCampoTab(campo) {
                        limpiarFiltroCampoTab();

                        const tabEsperada = campo === 'scrapear' ? 'campo-scraping' : 'campo-mostrar';
                        if (tabScrapingActiva !== tabEsperada) {
                            return;
                        }

                        arbol.classList.remove('modo-buscar', 'modo-buscar-sin-query', 'modo-filtros');

                        const input = campo === 'scrapear' ? inputBuscarCampoScraping : inputBuscarCampoMostrar;
                        const checkboxSi = campo === 'scrapear' ? filtroTabScrapingSi : filtroTabMostrarSi;
                        const checkboxNo = campo === 'scrapear' ? filtroTabScrapingNo : filtroTabMostrarNo;
                        const sinResultadosCampo = campo === 'scrapear' ? sinResultadosCampoScraping : sinResultadosCampoMostrar;
                        const hintCampo = campo === 'scrapear' ? hintCampoScraping : hintCampoMostrar;

                        const q = input ? input.value.trim().toLowerCase() : '';
                        const textoActivo = q.length >= 2;
                        let si = checkboxSi && checkboxSi.checked;
                        let no = checkboxNo && checkboxNo.checked;
                        if (!si && !no) {
                            si = true;
                            no = true;
                        }
                        const filtroCampoActivo = !(si && no);

                        if (hintCampo) {
                            hintCampo.classList.toggle('hidden', textoActivo);
                        }

                        if (!textoActivo && !filtroCampoActivo) {
                            return;
                        }

                        arbol.classList.add('modo-campo-filtro');

                        let coincidencias = 0;
                        todasFilasCategoria().forEach(function(fila) {
                            const nombre = fila.dataset.categoriaNombre || '';
                            if (textoActivo && !textoCoincideBusqueda(nombre, q)) {
                                return;
                            }

                            const valor = campo === 'scrapear' ? fila.dataset.scrapear : fila.dataset.mostrar;
                            if (valor === 'si' && !si) return;
                            if (valor === 'no' && !no) return;

                            marcarRutaCampoVisible(fila);
                            coincidencias++;
                        });

                        if (sinResultadosCampo) {
                            sinResultadosCampo.classList.toggle('hidden', coincidencias > 0);
                        }
                    }

                    function limpiarControlesCampoTab(campo) {
                        if (campo === 'scrapear') {
                            if (inputBuscarCampoScraping) inputBuscarCampoScraping.value = '';
                            if (filtroTabScrapingSi) filtroTabScrapingSi.checked = true;
                            if (filtroTabScrapingNo) filtroTabScrapingNo.checked = true;
                            limpiarSugerenciasCampo(sugerenciasCampoScraping);
                            if (hintCampoScraping) hintCampoScraping.classList.remove('hidden');
                        } else {
                            if (inputBuscarCampoMostrar) inputBuscarCampoMostrar.value = '';
                            if (filtroTabMostrarSi) filtroTabMostrarSi.checked = true;
                            if (filtroTabMostrarNo) filtroTabMostrarNo.checked = true;
                            limpiarSugerenciasCampo(sugerenciasCampoMostrar);
                            if (hintCampoMostrar) hintCampoMostrar.classList.remove('hidden');
                        }
                    }

                    function limpiarSugerenciasCampo(contenedor) {
                        if (!contenedor) return;
                        contenedor.innerHTML = '';
                        contenedor.classList.add('hidden');
                    }

                    function renderSugerenciasCampo(contenedor, input, categorias, campo) {
                        if (!contenedor) return;
                        contenedor.innerHTML = '';

                        if (!categorias.length) {
                            contenedor.classList.add('hidden');
                            return;
                        }

                        categorias.forEach(function(categoria) {
                            const item = document.createElement('button');
                            item.type = 'button';
                            item.className = 'w-full text-left px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 text-sm text-gray-700 dark:text-gray-200';
                            item.textContent = categoria.nombre;
                            item.addEventListener('click', function() {
                                if (input) input.value = categoria.nombre;
                                limpiarSugerenciasCampo(contenedor);
                                aplicarFiltroCampoTab(campo);
                            });
                            contenedor.appendChild(item);
                        });

                        contenedor.classList.remove('hidden');
                    }

                    function actualizarToggleVisual(boton, valor) {
                        const activo = valor === 'si';
                        boton.setAttribute('aria-checked', activo ? 'true' : 'false');
                        boton.dataset.valor = valor;
                        boton.classList.remove('campo-toggle--si', 'campo-toggle--no');
                        boton.classList.add(activo ? 'campo-toggle--si' : 'campo-toggle--no');
                    }

                    function sincronizarSelectsCampoFila(fila, data) {
                        const scrapearSelect = fila.querySelector('.js-scraping-scrapear-select');
                        const mostrarSelect = fila.querySelector('.js-scraping-mostrar-select');

                        if (scrapearSelect && data.scrapear) {
                            scrapearSelect.value = data.scrapear;
                        }
                        if (mostrarSelect && data.mostrar) {
                            mostrarSelect.value = data.mostrar;
                        }

                        fila.dataset.scrapear = data.scrapear;
                        fila.dataset.mostrar = data.mostrar;
                        fila.dataset.scrapearExplicito = data.scrapear_explicito ? '1' : '0';
                        fila.dataset.mostrarExplicito = data.mostrar_explicito ? '1' : '0';

                        const toggleScrapear = fila.querySelector('.js-toggle-scrapear-categoria');
                        const toggleMostrar = fila.querySelector('.js-toggle-mostrar-categoria');
                        if (toggleScrapear) actualizarToggleVisual(toggleScrapear, data.scrapear);
                        if (toggleMostrar) actualizarToggleVisual(toggleMostrar, data.mostrar);

                        const catId = String(fila.dataset.categoriaId || '');
                        if (catId && Object.prototype.hasOwnProperty.call(filtroPorCategoria, catId)) {
                            filtroPorCategoria[catId].scrapear = data.scrapear;
                            filtroPorCategoria[catId].mostrar = data.mostrar;
                        }

                        actualizarDatosFiltroFila(fila);

                        if (window.komparadorFlujoScraping && catId) {
                            const apiSelectFlujo = fila.querySelector('.js-api-scraping-select');
                            const apiValFlujo = apiSelectFlujo ? apiSelectFlujo.value : '';
                            const tiendaApiSelect = document.querySelector('select[name="api"]');
                            const apiEfectivaFlujo = apiValFlujo !== '' ? apiValFlujo : (tiendaApiSelect ? tiendaApiSelect.value : '');
                            window.komparadorFlujoScraping.actualizarCategoria(catId, {
                                scrapear: data.scrapear,
                                mostrar: data.mostrar,
                                scrapear_explicito: data.scrapear_explicito,
                                mostrar_explicito: data.mostrar_explicito,
                                api: apiEfectivaFlujo,
                            });
                        }
                    }

                    async function guardarToggleCampo(fila, campo, nuevoValor, boton) {
                        if (!urlGuardarCampoScraping) {
                            return;
                        }

                        const valorAnterior = campo === 'scrapear' ? fila.dataset.scrapear : fila.dataset.mostrar;
                        actualizarToggleVisual(boton, nuevoValor);
                        boton.disabled = true;

                        try {
                            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                            const response = await fetch(urlGuardarCampoScraping, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': csrf,
                                },
                                body: JSON.stringify({
                                    categoria_id: fila.dataset.categoriaId,
                                    campo: campo,
                                    valor: nuevoValor,
                                }),
                            });
                            const data = await response.json();
                            if (!response.ok || !data.ok) {
                                throw new Error(data.message || 'Error al guardar');
                            }

                            sincronizarSelectsCampoFila(fila, data);

                            if (tabScrapingActiva === 'campo-scraping') {
                                aplicarFiltroCampoTab('scrapear');
                            } else if (tabScrapingActiva === 'campo-mostrar') {
                                aplicarFiltroCampoTab('mostrar');
                            } else if (tabScrapingActiva === 'filtros') {
                                aplicarFiltroAvanzado();
                            }
                        } catch (error) {
                            actualizarToggleVisual(boton, valorAnterior);
                            alert('No se pudo guardar el cambio: ' + (error.message || 'error desconocido'));
                        } finally {
                            boton.disabled = false;
                        }
                    }

                    function marcarRutaFiltroVisible(fila) {
                        fila.classList.add('filtro-visible');
                        filasAncestros(fila).forEach(function(anc) {
                            anc.classList.add('filtro-ruta');
                            const hijosAnc = anc.querySelector(':scope > .js-categoria-children');
                            if (hijosAnc) hijosAnc.classList.add('filtro-expandir');
                        });
                    }

                    function aplicarFiltroAvanzado() {
                        limpiarFiltroAvanzado();

                        if (tabScrapingActiva !== 'filtros') {
                            return;
                        }

                        arbol.classList.remove('modo-buscar', 'modo-buscar-sin-query');
                        arbol.classList.add('modo-filtros');

                        const apiFiltro = filtroApi ? filtroApi.value : '';
                        let scrapingSi = filtroScrapingSi && filtroScrapingSi.checked;
                        let scrapingNo = filtroScrapingNo && filtroScrapingNo.checked;
                        let mostrarSi = filtroMostrarSi && filtroMostrarSi.checked;
                        let mostrarNo = filtroMostrarNo && filtroMostrarNo.checked;
                        if (!scrapingSi && !scrapingNo) {
                            scrapingSi = true;
                            scrapingNo = true;
                        }
                        if (!mostrarSi && !mostrarNo) {
                            mostrarSi = true;
                            mostrarNo = true;
                        }
                        let coincidencias = 0;

                        Object.keys(filtroPorCategoria).forEach(function(catId) {
                            const fila = arbol.querySelector('.js-categoria-row[data-categoria-id="' + catId + '"]');
                            if (!fila) {
                                return;
                            }

                            const datos = obtenerDatosFiltroEfectivos(fila);
                            if (!datos) {
                                return;
                            }

                            if (apiFiltro && datos.api_base !== apiFiltro) {
                                return;
                            }

                            if (datos.scrapear === 'si' && !scrapingSi) return;
                            if (datos.scrapear === 'no' && !scrapingNo) return;

                            if (datos.mostrar === 'si' && !mostrarSi) return;
                            if (datos.mostrar === 'no' && !mostrarNo) return;

                            marcarRutaFiltroVisible(fila);
                            coincidencias++;
                        });

                        if (sinResultadosFiltros) {
                            sinResultadosFiltros.classList.toggle('hidden', coincidencias > 0);
                        }
                        actualizarResumenApiActivo(apiFiltro);
                    }

                    function limpiarControlesFiltro() {
                        if (filtroApi) filtroApi.value = '';
                        [filtroScrapingSi, filtroScrapingNo, filtroMostrarSi, filtroMostrarNo].forEach(function(input) {
                            if (input) input.checked = true;
                        });
                    }

                    function aplicarFiltroBuscar(query) {
                        const q = (query || '').trim().toLowerCase();
                        limpiarFiltroBuscar();

                        if (tabScrapingActiva !== 'buscar') {
                            arbol.classList.remove('modo-buscar-sin-query');
                            return;
                        }

                        if (q.length < 2) {
                            arbol.classList.add('modo-buscar-sin-query');
                            if (hintBuscar) hintBuscar.classList.remove('hidden');
                            return;
                        }

                        if (hintBuscar) hintBuscar.classList.add('hidden');
                        arbol.classList.remove('modo-buscar-sin-query');
                        arbol.classList.add('modo-buscar');

                        const filas = todasFilasCategoria();
                        let coincidencias = 0;

                        filas.forEach(function(fila) {
                            const nombre = fila.dataset.categoriaNombre || '';
                            if (!textoCoincideBusqueda(nombre, q)) return;

                            coincidencias++;
                            fila.classList.add('buscar-visible');
                            filasDescendientes(fila).forEach(function(desc) {
                                desc.classList.add('buscar-visible');
                                const hijosDesc = desc.querySelector(':scope > .js-categoria-children');
                                if (hijosDesc) hijosDesc.classList.add('buscar-expandir');
                            });
                            filasAncestros(fila).forEach(function(anc) {
                                anc.classList.add('buscar-ruta');
                                const hijosAnc = anc.querySelector(':scope > .js-categoria-children');
                                if (hijosAnc) hijosAnc.classList.add('buscar-expandir');
                            });
                            const hijos = fila.querySelector(':scope > .js-categoria-children');
                            if (hijos) hijos.classList.add('buscar-expandir');
                        });

                        if (sinResultados) {
                            sinResultados.classList.toggle('hidden', coincidencias > 0);
                        }
                    }

                    function limpiarSugerencias() {
                        if (!sugerencias) return;
                        sugerencias.innerHTML = '';
                        sugerencias.classList.add('hidden');
                        kpSugerenciasTecladoReset(sugerencias);
                    }

                    function renderSugerencias(categorias) {
                        if (!sugerencias) return;
                        sugerencias.innerHTML = '';

                        if (!categorias.length) {
                            sugerencias.classList.add('hidden');
                            kpSugerenciasTecladoReset(sugerencias);
                            return;
                        }

                        categorias.forEach(function(categoria) {
                            const item = document.createElement('button');
                            item.type = 'button';
                            item.dataset.kpSugerenciaItem = '1';
                            item.setAttribute('role', 'option');
                            item.setAttribute('aria-selected', 'false');
                            item.className = 'w-full text-left px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 text-sm text-gray-700 dark:text-gray-200';
                            item.textContent = categoria.nombre;
                            item.addEventListener('click', function() {
                                if (inputBuscar) inputBuscar.value = categoria.nombre;
                                limpiarSugerencias();
                                aplicarFiltroBuscar(categoria.nombre);
                            });
                            sugerencias.appendChild(item);
                        });

                        sugerencias.classList.remove('hidden');
                        kpSugerenciasTecladoReset(sugerencias);
                    }

                    async function buscarCategoriasApi(query) {
                        try {
                            const response = await fetch(`/panel-privado/productos/buscar/categorias?q=${encodeURIComponent(query)}`);
                            const data = await response.json();
                            const categorias = Array.isArray(data) ? data : [];
                            if (tabScrapingActiva === 'buscar') {
                                renderSugerencias(categorias);
                            }
                            return categorias;
                        } catch (error) {
                            if (tabScrapingActiva === 'buscar') {
                                limpiarSugerencias();
                            }
                            return [];
                        }
                    }

                    function cambiarTabScraping(tab) {
                        tabScrapingActiva = tab;
                        setAlpineTabScraping(tab);

                        if (panelBuscar) {
                            panelBuscar.classList.toggle('oculto', tab !== 'buscar');
                        }
                        if (panelFiltros) {
                            panelFiltros.classList.toggle('oculto', tab !== 'filtros');
                        }
                        if (panelCampoScraping) {
                            panelCampoScraping.classList.toggle('oculto', tab !== 'campo-scraping');
                        }
                        if (panelCampoMostrar) {
                            panelCampoMostrar.classList.toggle('oculto', tab !== 'campo-mostrar');
                        }
                        if (panelBuscarProductos) {
                            panelBuscarProductos.classList.toggle('oculto', tab !== 'buscar-productos');
                        }
                        if (arbol) {
                            arbol.classList.toggle('arbol-oculto-tab-buscar-productos', tab === 'buscar-productos');
                        }

                        limpiarFiltroBuscar();
                        limpiarFiltroAvanzado();
                        limpiarFiltroCampoTab();
                        arbol.classList.remove('modo-tab-scraping', 'modo-tab-mostrar');

                        if (tab === 'buscar') {
                            aplicarFiltroBuscar(inputBuscar ? inputBuscar.value : '');
                        } else if (tab === 'filtros') {
                            aplicarFiltroAvanzado();
                        } else if (tab === 'campo-scraping') {
                            arbol.classList.add('modo-tab-scraping');
                            aplicarFiltroCampoTab('scrapear');
                        } else if (tab === 'campo-mostrar') {
                            arbol.classList.add('modo-tab-mostrar');
                            aplicarFiltroCampoTab('mostrar');
                        } else if (tab === 'buscar-productos') {
                            if (hintBuscar) hintBuscar.classList.add('hidden');
                            if (inputBuscarProductos && inputBuscarProductos.value.trim().length >= 2) {
                                aplicarBusquedaProductos(inputBuscarProductos.value);
                            }
                        } else if (hintBuscar) {
                            hintBuscar.classList.add('hidden');
                        }
                    }

                    function filtrarPorApi(api) {
                        if (filtroApi) filtroApi.value = api;
                        cambiarTabScraping('filtros');
                    }

                    window.komparadorScrapingCategorias = {
                        cambiarTab: cambiarTabScraping,
                        filtrarPorApi: filtrarPorApi,
                    };

                    if (inputBuscar) {
                        kpSugerenciasTecladoBind(inputBuscar, sugerencias);

                        inputBuscar.addEventListener('input', function() {
                            const query = inputBuscar.value.trim();
                            clearTimeout(debounceBuscar);

                            debounceBuscar = setTimeout(function() {
                                aplicarFiltroBuscar(query);
                                if (query.length >= 2) {
                                    buscarCategoriasApi(query);
                                } else {
                                    limpiarSugerencias();
                                }
                            }, 250);
                        });

                        document.addEventListener('click', function(event) {
                            if (!event.target.closest('#buscar-categoria-scraping') && !event.target.closest('#buscar-categoria-scraping-sugerencias')) {
                                limpiarSugerencias();
                            }
                        });
                    }

                    resumenApiBotones.forEach(function(btn) {
                        btn.addEventListener('click', function() {
                            filtrarPorApi(btn.dataset.filtroApi || '');
                        });
                    });

                    if (filtroApi) {
                        filtroApi.addEventListener('change', aplicarFiltroAvanzado);
                    }
                    [filtroScrapingSi, filtroScrapingNo, filtroMostrarSi, filtroMostrarNo].forEach(function(input) {
                        if (input) input.addEventListener('change', aplicarFiltroAvanzado);
                    });
                    if (btnLimpiarFiltros) {
                        btnLimpiarFiltros.addEventListener('click', function() {
                            limpiarControlesFiltro();
                            aplicarFiltroAvanzado();
                        });
                    }

                    if (inputBuscarCampoScraping) {
                        inputBuscarCampoScraping.addEventListener('input', function() {
                            clearTimeout(debounceBuscarCampoScraping);
                            debounceBuscarCampoScraping = setTimeout(function() {
                                const query = inputBuscarCampoScraping.value.trim();
                                aplicarFiltroCampoTab('scrapear');
                                if (query.length >= 2) {
                                    buscarCategoriasApi(query).then(function(data) {
                                        renderSugerenciasCampo(sugerenciasCampoScraping, inputBuscarCampoScraping, data, 'scrapear');
                                    });
                                } else {
                                    limpiarSugerenciasCampo(sugerenciasCampoScraping);
                                }
                            }, 250);
                        });
                    }

                    if (inputBuscarCampoMostrar) {
                        inputBuscarCampoMostrar.addEventListener('input', function() {
                            clearTimeout(debounceBuscarCampoMostrar);
                            debounceBuscarCampoMostrar = setTimeout(function() {
                                const query = inputBuscarCampoMostrar.value.trim();
                                aplicarFiltroCampoTab('mostrar');
                                if (query.length >= 2) {
                                    buscarCategoriasApi(query).then(function(data) {
                                        renderSugerenciasCampo(sugerenciasCampoMostrar, inputBuscarCampoMostrar, data, 'mostrar');
                                    });
                                } else {
                                    limpiarSugerenciasCampo(sugerenciasCampoMostrar);
                                }
                            }, 250);
                        });
                    }

                    [filtroTabScrapingSi, filtroTabScrapingNo].forEach(function(input) {
                        if (input) {
                            input.addEventListener('change', function() {
                                aplicarFiltroCampoTab('scrapear');
                            });
                        }
                    });
                    [filtroTabMostrarSi, filtroTabMostrarNo].forEach(function(input) {
                        if (input) {
                            input.addEventListener('change', function() {
                                aplicarFiltroCampoTab('mostrar');
                            });
                        }
                    });
                    if (btnLimpiarCampoScraping) {
                        btnLimpiarCampoScraping.addEventListener('click', function() {
                            limpiarControlesCampoTab('scrapear');
                            aplicarFiltroCampoTab('scrapear');
                        });
                    }
                    if (btnLimpiarCampoMostrar) {
                        btnLimpiarCampoMostrar.addEventListener('click', function() {
                            limpiarControlesCampoTab('mostrar');
                            aplicarFiltroCampoTab('mostrar');
                        });
                    }

                    document.addEventListener('click', function(event) {
                        if (inputBuscarCampoScraping && sugerenciasCampoScraping
                            && !event.target.closest('#buscar-campo-scraping')
                            && !event.target.closest('#buscar-campo-scraping-sugerencias')) {
                            limpiarSugerenciasCampo(sugerenciasCampoScraping);
                        }
                        if (inputBuscarCampoMostrar && sugerenciasCampoMostrar
                            && !event.target.closest('#buscar-campo-mostrar')
                            && !event.target.closest('#buscar-campo-mostrar-sugerencias')) {
                            limpiarSugerenciasCampo(sugerenciasCampoMostrar);
                        }
                    });

                    arbol.addEventListener('click', function(e) {
                        const toggle = e.target.closest('.js-toggle-campo-categoria');
                        if (!toggle || toggle.disabled) return;

                        const fila = toggle.closest('.js-categoria-row');
                        if (!fila) return;

                        const campo = toggle.dataset.campo;
                        const valorActual = toggle.dataset.valor === 'si' ? 'si' : 'no';
                        const nuevoValor = valorActual === 'si' ? 'no' : 'si';
                        guardarToggleCampo(fila, campo, nuevoValor, toggle);
                    });

                    arbol.addEventListener('change', function(e) {
                        const fila = e.target.closest('.js-categoria-row');
                        if (!fila) return;
                        if (e.target.matches('.js-api-scraping-select, .js-scraping-scrapear-select, .js-scraping-mostrar-select')) {
                            if (e.target.matches('.js-scraping-scrapear-select')) {
                                fila.dataset.scrapearExplicito = '1';
                            }
                            if (e.target.matches('.js-scraping-mostrar-select')) {
                                fila.dataset.mostrarExplicito = '1';
                            }
                            if (e.target.matches('.js-api-scraping-select')) {
                                fila.dataset.apiExplicita = e.target.value !== '' ? '1' : '0';
                            }
                            actualizarDatosFiltroFila(fila);
                            const toggleScrapear = fila.querySelector('.js-toggle-scrapear-categoria');
                            const toggleMostrar = fila.querySelector('.js-toggle-mostrar-categoria');
                            if (toggleScrapear) actualizarToggleVisual(toggleScrapear, fila.dataset.scrapear);
                            if (toggleMostrar) actualizarToggleVisual(toggleMostrar, fila.dataset.mostrar);
                            const catIdFlujo = String(fila.dataset.categoriaId || '');
                            if (window.komparadorFlujoScraping && catIdFlujo) {
                                const apiSelectFlujo = fila.querySelector('.js-api-scraping-select');
                                const apiValFlujo = apiSelectFlujo ? apiSelectFlujo.value : '';
                                const tiendaApiSelect = document.querySelector('select[name="api"]');
                                const apiEfectivaFlujo = apiValFlujo !== '' ? apiValFlujo : (tiendaApiSelect ? tiendaApiSelect.value : '');
                                window.komparadorFlujoScraping.actualizarCategoria(catIdFlujo, {
                                    scrapear: fila.dataset.scrapear,
                                    mostrar: fila.dataset.mostrar,
                                    scrapear_explicito: fila.dataset.scrapearExplicito === '1',
                                    mostrar_explicito: fila.dataset.mostrarExplicito === '1',
                                    api: apiEfectivaFlujo,
                                });
                            }
                            if (tabScrapingActiva === 'filtros') {
                                aplicarFiltroAvanzado();
                            } else if (tabScrapingActiva === 'campo-scraping') {
                                aplicarFiltroCampoTab('scrapear');
                            } else if (tabScrapingActiva === 'campo-mostrar') {
                                aplicarFiltroCampoTab('mostrar');
                            }
                        }
                    });

                    cambiarTabScraping('buscar');

                    function siguienteIndice(contenedor) {
                        const actual = parseInt(contenedor.dataset.siguienteIndice || '0', 10);
                        contenedor.dataset.siguienteIndice = String(actual + 1);
                        return actual;
                    }

                    function crearLineaUrlCategoria(contenedor, categoriaId, idx, url = '', visitada = '', neoId = '') {
                        const bloque = document.createElement('div');
                        bloque.className = 'js-url-categoria-bloque space-y-1';
                        const div = document.createElement('div');
                        div.className = 'js-url-categoria-linea url-categoria-linea-grid';
                        const filaCategoria = contenedor.closest('.js-categoria-row');
                        const sinNeo = filaCategoria && filaCategoria.dataset.sinNeoInicial === '1';
                        const borderUrl = sinNeo ? 'border-red-500' : '';
                        const urlEsc = url.replace(/"/g, '&quot;');

                        div.innerHTML = `
                            <div class="min-w-0">
                                <input type="hidden" name="urls_categoria[${categoriaId}][${idx}][id]" value="${neoId}">
                                <input type="url" name="urls_categoria[${categoriaId}][${idx}][url]" data-id="${categoriaId}" placeholder="https://..."
                                    class="url-categoria-input js-url-categoria w-full px-2 py-1 bg-gray-100 dark:bg-gray-700 text-white border rounded ${borderUrl}" value="${urlEsc}">
                            </div>
                            <input type="datetime-local" name="urls_categoria[${categoriaId}][${idx}][visitada]"
                                class="js-visitada-categoria w-full min-w-0 px-2 py-1 bg-gray-100 dark:bg-gray-700 text-white border rounded text-sm" value="${visitada}">
                            <button type="button" class="btn-eliminar-url-categoria w-7 h-7 flex items-center justify-center bg-red-500 hover:bg-red-600 text-white rounded text-sm shrink-0" title="Eliminar línea">−</button>
                            <button type="button" class="btn-añadir-url-categoria w-7 h-7 flex items-center justify-center bg-green-500 hover:bg-green-600 text-white rounded text-sm shrink-0" title="Añadir línea debajo">+</button>
                        `;

                        bloque.appendChild(div);
                        const estadoP = document.createElement('p');
                        estadoP.className = 'js-url-categoria-estado hidden text-xs mt-0.5';
                        estadoP.setAttribute('aria-live', 'polite');
                        bloque.appendChild(estadoP);

                        const urlInput = div.querySelector('.js-url-categoria');
                        if (urlInput) {
                            urlInput.addEventListener('input', function() {
                                document.dispatchEvent(new CustomEvent('url-categoria-cambiada'));
                            });
                            urlInput.addEventListener('change', function() {
                                document.dispatchEvent(new CustomEvent('url-categoria-cambiada'));
                            });
                        }

                        return bloque;
                    }

                    arbol.addEventListener('click', async function(e) {
                        const btnAñadir = e.target.closest('.btn-añadir-url-categoria');
                        const btnEliminar = e.target.closest('.btn-eliminar-url-categoria');
                        if (!btnAñadir && !btnEliminar) return;

                        const bloque = e.target.closest('.js-url-categoria-bloque');
                        const linea = e.target.closest('.js-url-categoria-linea');
                        const contenedor = e.target.closest('.js-urls-categoria-lineas');
                        if (!bloque || !linea || !contenedor) return;

                        const categoriaId = contenedor.dataset.categoriaId;

                        if (btnAñadir) {
                            const idx = siguienteIndice(contenedor);
                            const nuevaLinea = crearLineaUrlCategoria(contenedor, categoriaId, idx);
                            bloque.insertAdjacentElement('afterend', nuevaLinea);
                            nuevaLinea.querySelector('.js-url-categoria')?.focus();
                            return;
                        }

                        const bloques = contenedor.querySelectorAll('.js-url-categoria-bloque');
                        if (bloques.length <= 1) {
                            const inputUrl = bloque.querySelector('.js-url-categoria');
                            const hiddenNeo = bloque.querySelector('input[type="hidden"]');
                            const visitada = bloque.querySelector('.js-visitada-categoria');
                            if (inputUrl) inputUrl.value = '';
                            if (visitada) visitada.value = '';
                            await guardarUrlCategoriaBloque(bloque);
                            if (hiddenNeo) hiddenNeo.value = '';
                            return;
                        }

                        const inputUrlEliminar = bloque.querySelector('.js-url-categoria');
                        if (inputUrlEliminar) inputUrlEliminar.value = '';
                        await guardarUrlCategoriaBloque(bloque);
                        bloque.remove();
                        document.dispatchEvent(new CustomEvent('url-categoria-cambiada'));
                    });

                    const urlPrepararBuscar = arbol.dataset.urlPrepararBuscar || '';
                    const urlProcesarPagina = arbol.dataset.urlProcesarPagina || '';
                    const csrfToken = arbol.dataset.csrf || '';
                    const modalLogBuscar = document.getElementById('modal-log-buscar-productos');
                    const modalLogBuscarContenido = document.getElementById('modal-log-buscar-productos-contenido');

                    function escHtmlBuscarProductos(texto) {
                        return String(texto ?? '')
                            .replace(/&/g, '&amp;')
                            .replace(/</g, '&lt;')
                            .replace(/>/g, '&gt;')
                            .replace(/"/g, '&quot;');
                    }

                    function crearEstadoBuscarProductosInicial() {
                        return {
                            mensaje: 'Preparando…',
                            numeroPagina: 0,
                            urlActual: '',
                            urlListado: '',
                            tipoListado: '',
                            apiProductos: '',
                            totalEncontrados: 0,
                            urlsEncontradasTotal: [],
                            urlsNuevasTotal: [],
                            logPaginas: [],
                            error: '',
                            finMotivo: '',
                        };
                    }

                    function renderProgresoBuscarProductos(panel, estado) {
                        panel.classList.remove('hidden');
                        panel.innerHTML = `
                            <p><strong class="text-sky-300">${escHtmlBuscarProductos(estado.mensaje || 'Ejecutando…')}</strong></p>
                            ${estado.urlActual ? `<p class="text-gray-300 mt-1">Página ${estado.numeroPagina || 1}: <span class="break-all">${escHtmlBuscarProductos(estado.urlActual)}</span></p>` : ''}
                            <p class="text-gray-300 mt-1">Encontradas: <strong>${estado.totalEncontrados || 0}</strong> · Nuevas en neo: <strong class="text-emerald-300">${(estado.urlsNuevasTotal || []).length}</strong></p>
                            ${estado.error ? `<p class="text-red-400 mt-1">${escHtmlBuscarProductos(estado.error)}</p>` : ''}
                        `;
                    }

                    function renderModalLogBuscarProductos(estado) {
                        if (!modalLogBuscarContenido) return;

                        const urlsEncontradas = estado.urlsEncontradasTotal || [];
                        const urlsNuevas = estado.urlsNuevasTotal || [];
                        const paginasHtml = (estado.logPaginas || []).map(function(p) {
                            const encontradasPag = (p.urls || []).map(function(u) {
                                return `<li class="truncate break-all" title="${escHtmlBuscarProductos(u)}">${escHtmlBuscarProductos(u)}</li>`;
                            }).join('') || '<li class="text-gray-500">Ninguna</li>';
                            const nuevasPag = (p.urlsNuevas || []).map(function(u) {
                                return `<li class="truncate break-all text-emerald-300" title="${escHtmlBuscarProductos(u)}">${escHtmlBuscarProductos(u)}</li>`;
                            }).join('') || '<li class="text-gray-500">Ninguna</li>';
                            return `
                                <details class="border border-gray-600 rounded p-2 bg-gray-900/40">
                                    <summary class="cursor-pointer text-gray-200">Página ${p.numero} — ${escHtmlBuscarProductos(p.url || '')} (${(p.urls || []).length} encontradas, ${(p.urlsNuevas || []).length} nuevas)</summary>
                                    <p class="text-xs text-gray-400 mt-2">Encontradas en esta página:</p>
                                    <ul class="list-disc list-inside text-xs log-buscar-scroll">${encontradasPag}</ul>
                                    <p class="text-xs text-emerald-400 mt-2">Nuevas en neo:</p>
                                    <ul class="list-disc list-inside text-xs log-buscar-scroll">${nuevasPag}</ul>
                                </details>
                            `;
                        }).join('');

                        const listaEncontradas = urlsEncontradas.length
                            ? urlsEncontradas.map(function(u) {
                                return `<li class="truncate break-all" title="${escHtmlBuscarProductos(u)}">${escHtmlBuscarProductos(u)}</li>`;
                            }).join('')
                            : '<li class="text-gray-500">Ninguna URL encontrada.</li>';

                        const listaNuevas = urlsNuevas.length
                            ? urlsNuevas.map(function(u) {
                                return `<li class="truncate break-all text-emerald-300" title="${escHtmlBuscarProductos(u)}">${escHtmlBuscarProductos(u)}</li>`;
                            }).join('')
                            : '<li class="text-gray-500">Ninguna URL nueva insertada en neo.</li>';

                        modalLogBuscarContenido.innerHTML = `
                            <div class="space-y-1 text-gray-300">
                                <p><strong>Estado:</strong> ${escHtmlBuscarProductos(estado.mensaje || estado.finMotivo || '—')}</p>
                                ${estado.urlListado ? `<p><strong>Listado:</strong> <span class="break-all">${escHtmlBuscarProductos(estado.urlListado)}</span></p>` : ''}
                                ${estado.tipoListado ? `<p><strong>Tipo:</strong> ${escHtmlBuscarProductos(estado.tipoListado)} · <strong>API:</strong> ${escHtmlBuscarProductos(estado.apiProductos || '—')}</p>` : ''}
                                <p><strong>Páginas procesadas:</strong> ${estado.numeroPagina || 0} · <strong>Encontradas:</strong> ${urlsEncontradas.length} · <strong>Nuevas en neo:</strong> <span class="text-emerald-300">${urlsNuevas.length}</span></p>
                            </div>
                            ${estado.error ? `<p class="text-red-400">${escHtmlBuscarProductos(estado.error)}</p>` : ''}
                            ${paginasHtml ? `<div class="space-y-2"><h3 class="font-medium text-white">Detalle por página</h3>${paginasHtml}</div>` : ''}
                            <div>
                                <h3 class="font-medium text-white mb-1">URLs encontradas (${urlsEncontradas.length})</h3>
                                <ul class="list-disc list-inside log-buscar-scroll text-gray-300">${listaEncontradas}</ul>
                            </div>
                            <div>
                                <h3 class="font-medium text-emerald-400 mb-1">Nuevas insertadas en neo (${urlsNuevas.length})</h3>
                                <ul class="list-disc list-inside log-buscar-scroll">${listaNuevas}</ul>
                            </div>
                        `;
                    }

                    function abrirModalLogBuscarProductos(estado) {
                        if (!modalLogBuscar) return;
                        renderModalLogBuscarProductos(estado);
                        modalLogBuscar.classList.remove('hidden');
                    }

                    function cerrarModalLogBuscarProductos() {
                        modalLogBuscar?.classList.add('hidden');
                    }

                    document.getElementById('btn-cerrar-modal-log-buscar-productos')?.addEventListener('click', cerrarModalLogBuscarProductos);
                    document.getElementById('btn-cerrar-modal-log-buscar-productos-footer')?.addEventListener('click', cerrarModalLogBuscarProductos);
                    modalLogBuscar?.addEventListener('click', function(e) {
                        if (e.target === modalLogBuscar) cerrarModalLogBuscarProductos();
                    });
                    document.addEventListener('keydown', function(e) {
                        if (e.key === 'Escape' && modalLogBuscar && !modalLogBuscar.classList.contains('hidden')) {
                            cerrarModalLogBuscarProductos();
                        }
                    });

                    async function esperarSiPausado(session) {
                        while (session.paused && !session.abort) {
                            await new Promise(function(resolve) {
                                session.pauseResolve = resolve;
                            });
                        }
                    }

                    function reanudarDesdePausa(session) {
                        session.paused = false;
                        if (typeof session.pauseResolve === 'function') {
                            session.pauseResolve();
                            session.pauseResolve = null;
                        }
                    }

                    async function postJsonBuscarProductos(url, body) {
                        const resp = await fetch(url, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            body: JSON.stringify(body),
                        });
                        const data = await resp.json().catch(function() { return {}; });
                        if (!resp.ok && data.ok !== true) {
                            throw new Error(data.error || ('Error HTTP ' + resp.status));
                        }
                        return data;
                    }

                    function controlesBuscarProductosPanel() {
                        return {
                            play: document.getElementById('btn-buscar-productos-ejecutar'),
                            pause: document.getElementById('btn-buscar-productos-pausar'),
                            stop: document.getElementById('btn-buscar-productos-stop'),
                            log: document.getElementById('btn-buscar-productos-log'),
                        };
                    }

                    function setControlesBuscarProductosPanel(modo) {
                        const c = controlesBuscarProductosPanel();
                        const enCurso = modo === 'running' || modo === 'paused';
                        const puede = arbol.dataset.puedeBuscarProductos === '1';
                        if (c.play) {
                            c.play.disabled = !puede || enCurso;
                        }
                        if (c.pause) {
                            c.pause.disabled = !enCurso;
                            c.pause.innerHTML = modo === 'paused' ? '▶ Reanudar' : '⏸ Pausar';
                        }
                        if (c.stop) c.stop.disabled = !enCurso;
                        if (c.log) {
                            c.log.disabled = !(panelBuscarProductos && panelBuscarProductos._buscarProductosSession);
                        }
                    }

                    function obtenerUrlsCategoriaDesdeArbol(categoriaId) {
                        const fila = arbol.querySelector('.js-categoria-row[data-categoria-id="' + categoriaId + '"]');
                        if (!fila) return [];
                        const urls = [];
                        fila.querySelectorAll('.js-url-categoria-bloque').forEach(function(bloque) {
                            const neoId = parseInt(bloque.querySelector('input[type="hidden"]')?.value || '0', 10);
                            const url = (bloque.querySelector('.js-url-categoria')?.value || '').trim();
                            if (url !== '') {
                                urls.push({ neoobjetivo_id: neoId, url: url });
                            }
                        });
                        return urls;
                    }

                    function nombreCategoriaParaMostrar(fila) {
                        if (!fila) return '';
                        const nombreEl = fila.querySelector('.js-categoria-nombre');
                        if (!nombreEl) return '';
                        const first = nombreEl.firstChild;
                        if (first && first.nodeType === Node.TEXT_NODE) {
                            return first.textContent.trim();
                        }
                        return nombreEl.textContent.replace(/\s*\(\d+\)\s*$/, '').trim();
                    }

                    function categoriasConUrlParaBuscarProductos() {
                        const out = [];
                        arbol.querySelectorAll('.js-categoria-row').forEach(function(fila) {
                            const id = fila.dataset.categoriaId;
                            const urls = obtenerUrlsCategoriaDesdeArbol(id);
                            if (urls.length === 0) return;
                            out.push({
                                id: id,
                                nombre: (fila.dataset.categoriaNombre || '').toLowerCase(),
                                nombreMostrar: nombreCategoriaParaMostrar(fila) || ('Categoría ' + id),
                            });
                        });
                        return out;
                    }

                    function buscarCategoriasConUrlLocal(query) {
                        const q = (query || '').trim();
                        if (q.length < 2) return [];

                        return categoriasConUrlParaBuscarProductos().filter(function(c) {
                            return textoCoincideBusqueda(c.nombre, q);
                        });
                    }

                    function actualizarSeleccionUrlBuscarProductos() {
                        const select = document.getElementById('buscar-productos-url-select');
                        const hiddenNeo = document.getElementById('buscar-productos-neoobjetivo-id');
                        const avisoSinNeo = document.getElementById('buscar-productos-sin-neo-guardado');
                        const btnEjecutar = document.getElementById('btn-buscar-productos-ejecutar');
                        if (!select || !hiddenNeo) return;

                        const opt = select.options[select.selectedIndex];
                        if (!opt) return;

                        const neoId = parseInt(opt.dataset.neoobjetivoId || '0', 10);
                        hiddenNeo.value = neoId > 0 ? String(neoId) : '';
                        const sinNeo = neoId <= 0;
                        if (avisoSinNeo) avisoSinNeo.classList.toggle('hidden', !sinNeo);
                        if (btnEjecutar && arbol.dataset.puedeBuscarProductos === '1') {
                            const session = panelBuscarProductos?._buscarProductosSession;
                            const enCurso = session && (session.running || session.paused);
                            btnEjecutar.disabled = enCurso || sinNeo;
                        }
                    }

                    function seleccionarCategoriaBuscarProductos(categoriaId, nombre) {
                        const workspace = document.getElementById('buscar-productos-workspace');
                        const elegir = document.getElementById('buscar-productos-elegir-categoria');
                        const nombreEl = document.getElementById('buscar-productos-categoria-nombre');
                        const select = document.getElementById('buscar-productos-url-select');
                        const selectorWrap = document.getElementById('buscar-productos-url-selector-wrap');
                        if (!workspace || !select) return;

                        const urls = obtenerUrlsCategoriaDesdeArbol(categoriaId);
                        if (urls.length === 0) {
                            alert('Esta categoría no tiene URL de listado configurada.');
                            return;
                        }

                        if (nombreEl) nombreEl.textContent = nombre;
                        select.innerHTML = '';
                        urls.forEach(function(item, idx) {
                            const opt = document.createElement('option');
                            opt.value = item.url;
                            opt.dataset.neoobjetivoId = String(item.neoobjetivo_id || 0);
                            opt.textContent = urls.length > 1
                                ? ('URL ' + (idx + 1) + ': ' + item.url)
                                : item.url;
                            select.appendChild(opt);
                        });
                        if (selectorWrap) {
                            selectorWrap.classList.toggle('hidden', urls.length === 0);
                        }

                        workspace.dataset.categoriaId = String(categoriaId);
                        workspace.classList.remove('hidden');
                        if (elegir) elegir.classList.add('hidden');
                        actualizarSeleccionUrlBuscarProductos();
                    }

                    const inputBuscarProductos = document.getElementById('buscar-categoria-productos');
                    const sugerenciasProductos = document.getElementById('buscar-categoria-productos-sugerencias');
                    const hintBuscarProductos = document.getElementById('buscar-categoria-productos-hint');
                    const sinResultadosProductos = document.getElementById('buscar-categoria-productos-sin-resultados');
                    let debounceBuscarProductos = null;

                    function limpiarSugerenciasProductos() {
                        if (!sugerenciasProductos) return;
                        sugerenciasProductos.innerHTML = '';
                        sugerenciasProductos.classList.add('hidden');
                        kpSugerenciasTecladoReset(sugerenciasProductos);
                    }

                    function renderSugerenciasProductos(categorias) {
                        if (!sugerenciasProductos) return;
                        sugerenciasProductos.innerHTML = '';

                        if (!categorias.length) {
                            sugerenciasProductos.classList.add('hidden');
                            kpSugerenciasTecladoReset(sugerenciasProductos);
                            if (sinResultadosProductos) sinResultadosProductos.classList.remove('hidden');
                            return;
                        }

                        if (sinResultadosProductos) sinResultadosProductos.classList.add('hidden');
                        categorias.forEach(function(categoria) {
                            const item = document.createElement('button');
                            item.type = 'button';
                            item.dataset.kpSugerenciaItem = '1';
                            item.setAttribute('role', 'option');
                            item.setAttribute('aria-selected', 'false');
                            item.className = 'w-full text-left px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 text-sm text-gray-700 dark:text-gray-200';
                            item.textContent = categoria.nombreMostrar || categoria.nombre;
                            item.addEventListener('click', function() {
                                if (inputBuscarProductos) {
                                    inputBuscarProductos.value = categoria.nombreMostrar || categoria.nombre;
                                }
                                limpiarSugerenciasProductos();
                                seleccionarCategoriaBuscarProductos(String(categoria.id), categoria.nombreMostrar || categoria.nombre);
                            });
                            sugerenciasProductos.appendChild(item);
                        });
                        sugerenciasProductos.classList.remove('hidden');
                        kpSugerenciasTecladoReset(sugerenciasProductos);
                    }

                    function aplicarBusquedaProductos(query) {
                        const q = (query || '').trim();
                        if (q.length < 2) {
                            limpiarSugerenciasProductos();
                            if (sinResultadosProductos) sinResultadosProductos.classList.add('hidden');
                            if (hintBuscarProductos) hintBuscarProductos.classList.remove('hidden');
                            return;
                        }

                        if (hintBuscarProductos) hintBuscarProductos.classList.add('hidden');
                        renderSugerenciasProductos(buscarCategoriasConUrlLocal(q));
                    }

                    if (inputBuscarProductos) {
                        kpSugerenciasTecladoBind(inputBuscarProductos, sugerenciasProductos);

                        inputBuscarProductos.addEventListener('input', function() {
                            const query = inputBuscarProductos.value.trim();
                            clearTimeout(debounceBuscarProductos);
                            debounceBuscarProductos = setTimeout(function() {
                                aplicarBusquedaProductos(query);
                            }, 250);
                        });

                        document.addEventListener('click', function(event) {
                            if (!event.target.closest('#buscar-categoria-productos') && !event.target.closest('#buscar-categoria-productos-sugerencias')) {
                                limpiarSugerenciasProductos();
                            }
                        });

                        document.addEventListener('url-categoria-cambiada', function() {
                            if (tabScrapingActiva !== 'buscar-productos') return;
                            const query = inputBuscarProductos.value.trim();
                            if (query.length >= 2) {
                                aplicarBusquedaProductos(query);
                            }
                        });
                    }

                    document.getElementById('buscar-productos-url-select')?.addEventListener('change', actualizarSeleccionUrlBuscarProductos);

                    function progresoPanelBuscarProductos() {
                        return document.getElementById('buscar-productos-progreso-panel');
                    }

                    async function ejecutarBusquedaProductosPanel() {
                        if (!urlPrepararBuscar || !urlProcesarPagina || !panelBuscarProductos) return;

                        if (arbol.dataset.puedeBuscarProductos !== '1') {
                            alert(arbol.dataset.msgBuscarProductos || 'Esta tienda no tiene tipo de listado de categoría configurado.');
                            return;
                        }

                        const selectUrl = document.getElementById('buscar-productos-url-select');
                        const urlManual = (selectUrl?.value || '').trim();
                        const panel = progresoPanelBuscarProductos();
                        const categoriaId = document.getElementById('buscar-productos-workspace')?.dataset.categoriaId;
                        const selectIdx = selectUrl ? selectUrl.selectedIndex : 0;
                        const bloqueUrl = categoriaId ? obtenerBloqueUrlCategoria(categoriaId, selectIdx) : null;
                        const hiddenNeoInput = document.getElementById('buscar-productos-neoobjetivo-id');

                        if (!urlManual) {
                            alert('Indica una URL de listado antes de buscar productos.');
                            return;
                        }
                        if (panelBuscarProductos._buscarProductosSession && (panelBuscarProductos._buscarProductosSession.running || panelBuscarProductos._buscarProductosSession.paused)) {
                            return;
                        }

                        if (bloqueUrl) {
                            try {
                                await flushGuardadoUrlCategoriaBloque(bloqueUrl);
                            } catch (err) {
                                alert('No se pudo guardar la URL de categoría antes de buscar: ' + (err.message || err));
                                return;
                            }
                        }

                        let neoId = parseInt(bloqueUrl?.querySelector('input[type="hidden"]')?.value || hiddenNeoInput?.value || '0', 10);
                        if (hiddenNeoInput && neoId > 0) {
                            hiddenNeoInput.value = String(neoId);
                        }
                        actualizarSeleccionUrlBuscarProductos();

                        if (!neoId) {
                            alert('No se pudo persistir la URL de categoría. Comprueba que la URL es válida y vuelve a intentarlo.');
                            return;
                        }

                        const session = {
                            running: true,
                            paused: false,
                            abort: false,
                            pauseResolve: null,
                        };
                        panelBuscarProductos._buscarProductosSession = session;

                        const estado = crearEstadoBuscarProductosInicial();
                        session.estado = estado;

                        setControlesBuscarProductosPanel('running');
                        if (panel) renderProgresoBuscarProductos(panel, estado);

                        try {
                            const prep = await postJsonBuscarProductos(urlPrepararBuscar, {
                                neoobjetivo_id: neoId,
                                url_listado: urlManual,
                            });
                            if (!prep.ok) {
                                throw new Error(prep.error || 'No se pudo preparar la búsqueda.');
                            }

                            estado.urlListado = urlManual || prep.url_inicial;
                            estado.tipoListado = prep.tipo_listado || '';
                            estado.apiProductos = prep.api_productos || '';

                            let urlActual = urlManual || prep.url_inicial;
                            let acumulado = 0;
                            let numeroPagina = 0;
                            const maxPaginas = prep.max_paginas || 50;
                            let completada = false;

                            estado.mensaje = 'Ejecutando (' + estado.tipoListado + ')…';
                            if (panel) renderProgresoBuscarProductos(panel, estado);

                            while (!completada && !session.abort && numeroPagina < maxPaginas) {
                                await esperarSiPausado(session);
                                if (session.abort) {
                                    estado.finMotivo = 'Detenido por el usuario.';
                                    estado.mensaje = estado.finMotivo;
                                    break;
                                }

                                numeroPagina++;
                                estado.numeroPagina = numeroPagina;
                                estado.urlActual = urlActual;
                                estado.mensaje = 'Obteniendo HTML y extrayendo productos…';
                                if (panel) renderProgresoBuscarProductos(panel, estado);

                                await esperarSiPausado(session);
                                if (session.abort) {
                                    estado.finMotivo = 'Detenido por el usuario.';
                                    estado.mensaje = estado.finMotivo;
                                    break;
                                }

                                const res = await postJsonBuscarProductos(urlProcesarPagina, {
                                    neoobjetivo_id: neoId,
                                    url_pagina: urlActual,
                                    urls_producto_acumulado_antes: acumulado,
                                    numero_pagina: numeroPagina,
                                });

                                if (!res.ok) {
                                    throw new Error(res.error || 'Error al procesar la página.');
                                }

                                const urlsPagina = Array.isArray(res.urls_productos) ? res.urls_productos : [];
                                const nuevasPagina = Array.isArray(res.urls_nuevas_neo) ? res.urls_nuevas_neo : [];
                                acumulado += urlsPagina.length;

                                estado.urlsEncontradasTotal = estado.urlsEncontradasTotal.concat(urlsPagina);
                                estado.urlsNuevasTotal = estado.urlsNuevasTotal.concat(nuevasPagina);
                                estado.totalEncontrados = acumulado;
                                estado.logPaginas.push({
                                    numero: numeroPagina,
                                    url: urlActual,
                                    urls: urlsPagina,
                                    urlsNuevas: nuevasPagina,
                                });
                                setControlesBuscarProductosPanel(session.paused ? 'paused' : 'running');
                                if (controlesBuscarProductosPanel().log) {
                                    controlesBuscarProductosPanel().log.disabled = false;
                                }

                                if (session.abort) {
                                    estado.finMotivo = 'Detenido por el usuario.';
                                    estado.mensaje = estado.finMotivo;
                                    break;
                                }

                                if (res.completada || !res.siguiente_url) {
                                    completada = true;
                                    estado.finMotivo = 'Completado.';
                                    estado.mensaje = estado.finMotivo;
                                    if (res.visitada_actualizada && categoriaId) {
                                        const bloqueVisitada = obtenerBloqueUrlCategoria(categoriaId, selectIdx);
                                        const visitadaInput = bloqueVisitada?.querySelector('.js-visitada-categoria');
                                        if (visitadaInput) {
                                            const now = new Date();
                                            now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
                                            visitadaInput.value = now.toISOString().slice(0, 16);
                                            flushGuardadoUrlCategoriaBloque(bloqueVisitada).catch(function() {});
                                        }
                                    }
                                } else {
                                    estado.mensaje = 'Página ' + numeroPagina + ' procesada, continuando…';
                                    urlActual = res.siguiente_url;
                                }

                                if (panel) renderProgresoBuscarProductos(panel, estado);
                            }

                            if (!completada && !session.abort && numeroPagina >= maxPaginas) {
                                estado.finMotivo = 'Detenido: límite de ' + maxPaginas + ' páginas.';
                                estado.mensaje = estado.finMotivo;
                                if (panel) renderProgresoBuscarProductos(panel, estado);
                            }
                        } catch (err) {
                            estado.error = err.message || String(err);
                            estado.finMotivo = 'Error en la búsqueda.';
                            estado.mensaje = estado.finMotivo;
                            if (panel) renderProgresoBuscarProductos(panel, estado);
                        } finally {
                            session.running = false;
                            session.paused = false;
                            reanudarDesdePausa(session);
                            setControlesBuscarProductosPanel('idle');
                            if (controlesBuscarProductosPanel().log) {
                                controlesBuscarProductosPanel().log.disabled = false;
                            }
                        }
                    }

                    document.getElementById('btn-buscar-productos-ejecutar')?.addEventListener('click', function(e) {
                        e.preventDefault();
                        ejecutarBusquedaProductosPanel();
                    });

                    document.getElementById('btn-buscar-productos-pausar')?.addEventListener('click', function(e) {
                        e.preventDefault();
                        const session = panelBuscarProductos?._buscarProductosSession;
                        if (!session || (!session.running && !session.paused)) return;
                        const panel = progresoPanelBuscarProductos();
                        if (session.paused) {
                            reanudarDesdePausa(session);
                            setControlesBuscarProductosPanel('running');
                            if (session.estado) {
                                session.estado.mensaje = 'Reanudando…';
                                if (panel) renderProgresoBuscarProductos(panel, session.estado);
                            }
                        } else {
                            session.paused = true;
                            setControlesBuscarProductosPanel('paused');
                            if (session.estado) {
                                session.estado.mensaje = 'Pausado.';
                                if (panel) renderProgresoBuscarProductos(panel, session.estado);
                            }
                        }
                    });

                    document.getElementById('btn-buscar-productos-stop')?.addEventListener('click', function(e) {
                        e.preventDefault();
                        const session = panelBuscarProductos?._buscarProductosSession;
                        if (!session) return;
                        session.abort = true;
                        reanudarDesdePausa(session);
                        const panel = progresoPanelBuscarProductos();
                        if (session.estado) {
                            session.estado.finMotivo = 'Detenido por el usuario.';
                            session.estado.mensaje = session.estado.finMotivo;
                            if (panel) renderProgresoBuscarProductos(panel, session.estado);
                        }
                    });

                    document.getElementById('btn-buscar-productos-log')?.addEventListener('click', function(e) {
                        e.preventDefault();
                        const session = panelBuscarProductos?._buscarProductosSession;
                        if (session?.estado) abrirModalLogBuscarProductos(session.estado);
                    });

                    setControlesBuscarProductosPanel('idle');
                });
                </script>
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

            @php
                $bloquearGuardarPorCategorias = $tienda->exists && ($categoriasSinNeoobjetivo ?? collect())->isNotEmpty();
                $checkboxMarcado = old('sin_listado_categoria');
            @endphp
            <div class="flex flex-wrap items-center justify-end gap-4">
                @if($bloquearGuardarPorCategorias)
                    <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                        <input type="checkbox" name="sin_listado_categoria" id="sin_listado_categoria_checkbox" value="1" {{ $checkboxMarcado ? 'checked' : '' }}
                            class="rounded border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-700 text-pink-600 focus:ring-pink-500">
                        Esta tienda no tiene listado por categoría
                    </label>
                @endif
                <button type="submit" id="btn_guardar_tienda"
                    @if($bloquearGuardarPorCategorias && !$checkboxMarcado) disabled @endif
                    class="inline-flex items-center font-semibold text-base px-6 py-3 rounded-md shadow-md transition {{ ($bloquearGuardarPorCategorias && !$checkboxMarcado) ? 'bg-gray-400 dark:bg-gray-500 text-gray-200 cursor-not-allowed' : 'bg-pink-600 hover:bg-pink-700 text-white' }}">
                    {{ $tienda->exists ? 'Actualizar tienda' : 'Crear tienda' }}
                </button>
            </div>
            @if($bloquearGuardarPorCategorias)
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    var checkbox = document.getElementById('sin_listado_categoria_checkbox');
                    var btn = document.getElementById('btn_guardar_tienda');
                    if (!checkbox || !btn) return;

                    var filasCategorias = document.querySelectorAll('.js-categoria-row[data-sin-neo-inicial="1"]');
                    var hayFilasConIncidencia = filasCategorias.length > 0;

                    function filaTieneUrl(fila) {
                        var inputs = fila.querySelectorAll('.js-url-categoria');
                        for (var j = 0; j < inputs.length; j++) {
                            if (inputs[j].value && inputs[j].value.trim() !== '') {
                                return true;
                            }
                        }
                        return false;
                    }

                    function aplicarEstadoVisualFila(fila, resuelta) {
                        var nombre = fila.querySelector('.js-categoria-nombre');
                        var inputs = fila.querySelectorAll('.js-url-categoria');

                        if (resuelta) {
                            fila.classList.remove('bg-red-50', 'dark:bg-red-900/20', 'border', 'border-red-200', 'dark:border-red-800', 'rounded', 'p-2');
                            if (nombre) {
                                nombre.classList.remove('text-red-700', 'dark:text-red-300');
                                nombre.classList.add('text-gray-800', 'dark:text-gray-200');
                            }
                            inputs.forEach(function(input) {
                                input.classList.remove('border-red-500');
                            });
                        } else {
                            fila.classList.add('bg-red-50', 'dark:bg-red-900/20', 'border', 'border-red-200', 'dark:border-red-800', 'rounded', 'p-2');
                            if (nombre) {
                                nombre.classList.add('text-red-700', 'dark:text-red-300');
                                nombre.classList.remove('text-gray-800', 'dark:text-gray-200');
                            }
                            inputs.forEach(function(input) {
                                input.classList.add('border-red-500');
                            });
                        }
                    }

                    var arbolUrls;

                    function quedanRojas() {
                        if (!hayFilasConIncidencia) return false;
                        for (var i = 0; i < filasCategorias.length; i++) {
                            if (!filaTieneUrl(filasCategorias[i])) return true;
                        }
                        return false;
                    }

                    function actualizarBoton() {
                        var hayRojas = quedanRojas();
                        var csvInvalido = typeof window.komparadorCsvAwinValido === 'function' && !window.komparadorCsvAwinValido();
                        if ((!hayRojas || checkbox.checked) && !csvInvalido) {
                            btn.disabled = false;
                            btn.classList.remove('bg-gray-400', 'dark:bg-gray-500', 'text-gray-200', 'cursor-not-allowed');
                            btn.classList.add('bg-pink-600', 'hover:bg-pink-700', 'text-white');
                        } else {
                            btn.disabled = true;
                            btn.classList.remove('bg-pink-600', 'hover:bg-pink-700', 'text-white');
                            btn.classList.add('bg-gray-400', 'dark:bg-gray-500', 'text-gray-200', 'cursor-not-allowed');
                        }
                    }

                    function refrescarFila(fila) {
                        aplicarEstadoVisualFila(fila, filaTieneUrl(fila));
                        actualizarBoton();
                    }

                    filasCategorias.forEach(function(fila) {
                        refrescarFila(fila);
                    });

                    document.addEventListener('url-categoria-cambiada', function() {
                        filasCategorias.forEach(refrescarFila);
                    });

                    arbolUrls = document.getElementById('arbol-urls-categoria');
                    if (arbolUrls) {
                        arbolUrls.addEventListener('input', function(e) {
                            if (e.target.classList.contains('js-url-categoria')) {
                                var fila = e.target.closest('.js-categoria-row');
                                if (fila) refrescarFila(fila);
                            }
                        });
                        arbolUrls.addEventListener('change', function(e) {
                            if (e.target.classList.contains('js-url-categoria')) {
                                var fila = e.target.closest('.js-categoria-row');
                                if (fila) refrescarFila(fila);
                            }
                        });
                    }

                    checkbox.addEventListener('change', actualizarBoton);
                    document.addEventListener('csv-awin-estado-cambiado', actualizarBoton);
                    actualizarBoton();
                });
            </script>
            @endif
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
        (function() {
            const CSV_AWIN = 'CSV-Awin';

            function urlCsvInput() {
                return document.getElementById('url-csv-input');
            }

            function avisoUrlCsv() {
                return document.getElementById('url-csv-csv-awin-aviso');
            }

            function obtenerUrlsCsv() {
                const input = urlCsvInput();
                if (!input) {
                    return [];
                }
                return input.value
                    .split(/\r\n|\r|\n/)
                    .map(function(linea) { return linea.trim(); })
                    .filter(function(linea) { return linea !== ''; });
            }

            function tieneUrlsCsv() {
                return obtenerUrlsCsv().length > 0;
            }

            function obtenerSelectsApi() {
                const selects = [];
                const principal = document.getElementById('api-select');
                if (principal) {
                    selects.push(principal);
                }
                document.querySelectorAll('.js-api-scraping-select').forEach(function(select) {
                    selects.push(select);
                });
                return selects;
            }

            function algunaApiEsCsvAwin() {
                return obtenerSelectsApi().some(function(select) {
                    return select.value === CSV_AWIN;
                });
            }

            function marcarSelectCsvAwin(select) {
                if (select.value === CSV_AWIN && !tieneUrlsCsv()) {
                    select.classList.add('border-red-500');
                } else {
                    select.classList.remove('border-red-500');
                }
            }

            function actualizarEstadoCsvAwin() {
                const permitido = tieneUrlsCsv();
                const input = urlCsvInput();
                const aviso = avisoUrlCsv();
                const csvAwinActivo = algunaApiEsCsvAwin();

                document.querySelectorAll('.js-opcion-csv-awin').forEach(function(opt) {
                    opt.disabled = !permitido;
                });

                obtenerSelectsApi().forEach(function(select) {
                    marcarSelectCsvAwin(select);
                });

                if (input) {
                    if (csvAwinActivo && !permitido) {
                        input.classList.add('border-red-500');
                    } else if (permitido || !csvAwinActivo) {
                        input.classList.remove('border-red-500');
                    }
                }

                if (aviso) {
                    if (!permitido && csvAwinActivo) {
                        aviso.classList.remove('hidden');
                    } else if (permitido) {
                        aviso.classList.add('hidden');
                    }
                }

                actualizarBotonGuardarCsvAwin();
                document.dispatchEvent(new CustomEvent('csv-awin-estado-cambiado'));
            }

            function actualizarBotonGuardarCsvAwin() {
                const btnGuardar = document.getElementById('btn_guardar_tienda');
                const checkboxCategorias = document.getElementById('sin_listado_categoria_checkbox');
                if (!btnGuardar || checkboxCategorias) {
                    return;
                }

                const csvInvalido = algunaApiEsCsvAwin() && !tieneUrlsCsv();
                if (csvInvalido) {
                    btnGuardar.disabled = true;
                    btnGuardar.classList.remove('bg-pink-600', 'hover:bg-pink-700', 'text-white');
                    btnGuardar.classList.add('bg-gray-400', 'dark:bg-gray-500', 'text-gray-200', 'cursor-not-allowed');
                } else {
                    btnGuardar.disabled = false;
                    btnGuardar.classList.remove('bg-gray-400', 'dark:bg-gray-500', 'text-gray-200', 'cursor-not-allowed');
                    btnGuardar.classList.add('bg-pink-600', 'hover:bg-pink-700', 'text-white');
                }
            }

            window.komparadorCsvAwinValido = function() {
                return !algunaApiEsCsvAwin() || tieneUrlsCsv();
            };

            document.addEventListener('DOMContentLoaded', function() {
                const input = urlCsvInput();
                if (input) {
                    input.addEventListener('input', actualizarEstadoCsvAwin);
                    input.addEventListener('change', actualizarEstadoCsvAwin);
                    input.addEventListener('focus', actualizarEstadoCsvAwin);
                }

                obtenerSelectsApi().forEach(function(select) {
                    select.addEventListener('change', function() {
                        if (select.value === CSV_AWIN && !tieneUrlsCsv()) {
                            select.value = '';
                            select.classList.add('border-red-500');
                            if (input) {
                                input.classList.add('border-red-500');
                                input.focus();
                            }
                            if (avisoUrlCsv()) {
                                avisoUrlCsv().classList.remove('hidden');
                            }
                            alert('Debes indicar al menos un enlace de descarga antes de seleccionar CSV-Awin.');
                            return;
                        }
                        actualizarEstadoCsvAwin();
                    });
                });

                actualizarEstadoCsvAwin();
            });
        })();

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
                return;
            }

            if (typeof window.komparadorCsvAwinValido === 'function' && !window.komparadorCsvAwinValido()) {
                e.preventDefault();
                const input = document.getElementById('url-csv-input');
                if (input) {
                    input.classList.add('border-red-500');
                    input.focus();
                }
                document.querySelectorAll('.js-api-scraping-select, #api-select').forEach(function(select) {
                    if (select.value === 'CSV-Awin') {
                        select.classList.add('border-red-500');
                    }
                });
                const aviso = document.getElementById('url-csv-csv-awin-aviso');
                if (aviso) {
                    aviso.classList.remove('hidden');
                }
                alert('Debes indicar al menos un enlace de descarga para usar CSV-Awin.');
            }
        });

        document.addEventListener('DOMContentLoaded', function() {
            function setupHelpTooltip(btnId, tooltipId) {
                const helpBtn = document.getElementById(btnId);
                const helpTooltip = document.getElementById(tooltipId);

                if (!helpBtn || !helpTooltip) return;

                const cerrarTooltip = () => {
                    helpTooltip.classList.add('hidden');
                    helpBtn.setAttribute('aria-expanded', 'false');
                };

                const abrirTooltip = () => {
                    helpTooltip.classList.remove('hidden');
                    helpBtn.setAttribute('aria-expanded', 'true');
                };

                helpBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    if (helpTooltip.classList.contains('hidden')) {
                        abrirTooltip();
                    } else {
                        cerrarTooltip();
                    }
                });

                document.addEventListener('click', function(e) {
                    if (!helpTooltip.contains(e.target) && !helpBtn.contains(e.target)) {
                        cerrarTooltip();
                    }
                });

                document.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape') {
                        cerrarTooltip();
                    }
                });
            }

            setupHelpTooltip('api-scraping-help-btn', 'api-scraping-help-tooltip');
            setupHelpTooltip('api-productos-help-btn', 'api-productos-help-tooltip');
        });
    </script>






    @if($tienda->exists && !empty($flujoScrapingTienda) && (($flujoScrapingTienda['stats']['scrapear']['total'] ?? 0) > 0))
    <script>
        function crearMapaNeuronalTienda(opciones) {
            const canvas = opciones.canvas;
            const contenedor = opciones.contenedor;
            const tooltip = opciones.tooltip;
            let flujoData = JSON.parse(JSON.stringify(opciones.flujoData || {}));
            let modoFlujo = 'scrapear';
            let inicializado = false;

            let ctx = null;
            let animId = null;
            let resizeObserver = null;
            let dpr = 1;

            const view = { panX: 0, panY: 0, scale: 1 };
            const drag = { activo: false, x: 0, y: 0, panX0: 0, panY0: 0 };
            let tiempoInicio = 0;

            let tiendaNodo = null;
            const nodosCat = [];
            let arbolHijos = new Map();
            let hojasIds = new Set();

            const COLORES = {
                fondo: '#030712',
                fondoGlow: 'rgba(56, 189, 248, 0.04)',
                tienda: '#f97316',
                tiendaGlow: '#fb923c',
                tiendaInactiva: '#64748b',
                activoScrapear: '#22c55e',
                activoScrapearGlow: '#4ade80',
                activoMostrar: '#38bdf8',
                activoMostrarGlow: '#7dd3fc',
                inactivo: '#334155',
                estructural: '#1e293b',
                cortada: '#ef4444',
                cortadaGlow: '#f87171',
                detenida: '#fbbf24',
                pulsoScrapear: '#86efac',
                pulsoMostrar: '#7dd3fc',
                pulsoDetenido: '#fcd34d',
                pulsoRojo: '#f87171',
                texto: '#e2e8f0',
                textoTienda: '#ffedd5',
                grid: 'rgba(56, 189, 248, 0.06)',
                gridPunto: 'rgba(56, 189, 248, 0.12)',
            };

            function truncarNombre(nombre, max) {
                const s = String(nombre || '');
                if (s.length <= max) return s;
                return s.slice(0, max - 1) + '…';
            }

            function recalcularFlujo() {
                const tiendaScrapear = flujoData.tienda?.scrapear || 'si';
                const tiendaMostrar = flujoData.tienda?.mostrar || 'si';
                const statsScrapear = { total: 0, activas: 0, bloqueadas: 0 };
                const statsMostrar = { total: 0, activas: 0, bloqueadas: 0 };

                (flujoData.categorias || []).forEach(function (cat) {
                    cat.scrapear_final = tiendaScrapear === 'no' ? 'no' : (cat.scrapear || 'si');
                    cat.mostrar_final = tiendaMostrar === 'no' ? 'no' : (cat.mostrar || 'si');
                    statsScrapear.total++;
                    statsMostrar.total++;
                    if (cat.scrapear_final === 'si') statsScrapear.activas++;
                    else statsScrapear.bloqueadas++;
                    if (cat.mostrar_final === 'si') statsMostrar.activas++;
                    else statsMostrar.bloqueadas++;
                });

                flujoData.stats = { scrapear: statsScrapear, mostrar: statsMostrar };
            }

            function categoriaEmite(cat) {
                return modoFlujo === 'scrapear'
                    ? cat.scrapear === 'si'
                    : cat.mostrar === 'si';
            }

            function estadoCategoria(cat) {
                const tiendaOk = modoFlujo === 'scrapear'
                    ? flujoData.tienda.scrapear === 'si'
                    : flujoData.tienda.mostrar === 'si';
                const finalOk = modoFlujo === 'scrapear'
                    ? cat.scrapear_final === 'si'
                    : cat.mostrar_final === 'si';
                const catEmite = categoriaEmite(cat);

                let tipo = 'cortada';
                if (!catEmite) {
                    tipo = 'cortada';
                } else if (!tiendaOk) {
                    tipo = 'detenida_tienda';
                } else if (finalOk) {
                    tipo = 'fluye';
                } else {
                    tipo = 'cortada';
                }

                return { tiendaOk, finalOk, catEmite, tipo };
            }

            function conectaConTienda(cat, porId) {
                const pid = cat.parent_id ? String(cat.parent_id) : null;
                return !pid || !porId.has(pid);
            }

            function bloqueaSubida(cat) {
                return !categoriaEmite(cat);
            }

            function estadoCompletoFluye() {
                return modoFlujo === 'scrapear'
                    ? flujoData.tienda.scrapear === 'si'
                    : flujoData.tienda.mostrar === 'si';
            }

            function colorActivo() {
                return modoFlujo === 'scrapear' ? COLORES.activoScrapear : COLORES.activoMostrar;
            }

            function colorPulso(est, enSegmentoTienda, cat) {
                if (enSegmentoTienda && !est.tiendaOk) {
                    return { core: COLORES.pulsoRojo, glow: COLORES.pulsoRojo };
                }
                if (!est.catEmite) {
                    return { core: COLORES.pulsoDetenido, glow: COLORES.pulsoDetenido };
                }
                if (modoFlujo === 'scrapear' && cat) {
                    const icon = cat.api_icon || metaIconoApiJs(cat.api);
                    return {
                        core: icon.pulse_color || COLORES.pulsoScrapear,
                        glow: icon.pulse_glow || icon.pulse_color || COLORES.pulsoScrapear,
                    };
                }
                const core = modoFlujo === 'scrapear' ? COLORES.pulsoScrapear : COLORES.pulsoMostrar;
                return { core: core, glow: core };
            }

            const PALETA_API_FALLBACK = {
                miVpsHtml:         { pulse_color: '#3b82f6', pulse_glow: '#60a5fa', nombre: 'Mi VPS HTML' },
                scrapingAnt:       { pulse_color: '#22c55e', pulse_glow: '#4ade80', nombre: 'ScrapingAnt' },
                brightData:        { pulse_color: '#a855f7', pulse_glow: '#c084fc', nombre: 'Bright Data' },
                aliexpressOpen:    { pulse_color: '#f59e0b', pulse_glow: '#fbbf24', nombre: 'AliExpress Open' },
                amazonApi:         { pulse_color: '#f43f5e', pulse_glow: '#fb7185', nombre: 'Amazon API' },
                amazonProductInfo: { pulse_color: '#6366f1', pulse_glow: '#818cf8', nombre: 'Amazon Product Info' },
                amazonPricing:     { pulse_color: '#06b6d4', pulse_glow: '#22d3ee', nombre: 'Amazon Pricing' },
                navegadorLocal:    { pulse_color: '#14b8a6', pulse_glow: '#2dd4bf', nombre: 'Navegador local' },
                'CSV-Awin':        { pulse_color: '#f97316', pulse_glow: '#fb923c', nombre: 'CSV Awin' },
                _default:          { pulse_color: '#64748b', pulse_glow: '#94a3b8', nombre: 'Otra API' },
                _sin:              { pulse_color: '#9ca3af', pulse_glow: '#d1d5db', nombre: 'Sin API' },
            };

            function apiBaseDesdeValor(api) {
                if (!api) return null;
                return String(api).split(';')[0] || null;
            }

            function metaIconoApiJs(api) {
                if (!api) {
                    return Object.assign({ base: null, label: '?', title: 'Sin API' }, PALETA_API_FALLBACK._sin);
                }
                const base = apiBaseDesdeValor(api);
                const palette = PALETA_API_FALLBACK[base] || PALETA_API_FALLBACK._default;
                return Object.assign({
                    base: base,
                    label: base ? base.slice(0, 2).toUpperCase() : '?',
                    title: api,
                }, palette);
            }

            function recopilarEntradasLeyendaApi() {
                const porBase = new Map();
                (flujoData.categorias || []).forEach(function (cat) {
                    if (!categoriaEmite(cat)) return;
                    const icon = cat.api_icon || metaIconoApiJs(cat.api);
                    const key = icon.base || '__sin__';
                    if (!porBase.has(key)) {
                        porBase.set(key, { key: key, icon: icon, count: 0 });
                    }
                    porBase.get(key).count++;
                });
                return Array.from(porBase.values()).sort(function (a, b) {
                    return String(a.icon.nombre || '').localeCompare(String(b.icon.nombre || ''), 'es');
                });
            }

            function actualizarLeyendaApis() {
                const panel = document.getElementById('mapa-neuronal-leyenda-apis');
                const lista = document.getElementById('mapa-neuronal-leyenda-lista');
                const titulo = document.getElementById('mapa-neuronal-leyenda-titulo');
                if (!panel || !lista) return;

                if (modoFlujo !== 'scrapear') {
                    panel.classList.add('hidden');
                    return;
                }

                const entradas = recopilarEntradasLeyendaApi();
                panel.classList.toggle('hidden', entradas.length === 0);
                if (titulo) {
                    titulo.textContent = 'APIs (' + entradas.length + ')';
                }

                lista.replaceChildren();
                entradas.forEach(function (entrada) {
                    const fila = document.createElement('div');
                    fila.className = 'flex items-center gap-2 text-[11px] text-slate-300';
                    fila.title = entrada.icon.title || entrada.icon.nombre || '';

                    const punto = document.createElement('span');
                    punto.className = 'shrink-0 w-3 h-3 rounded-full';
                    punto.style.background = entrada.icon.pulse_color || '#64748b';
                    punto.style.boxShadow = '0 0 8px ' + (entrada.icon.pulse_glow || entrada.icon.pulse_color || '#64748b');

                    const nombre = document.createElement('span');
                    nombre.className = 'truncate';
                    nombre.textContent = entrada.icon.nombre || entrada.icon.base || 'Sin API';

                    const contador = document.createElement('span');
                    contador.className = 'ml-auto text-[10px] text-cyan-600/60 tabular-nums';
                    contador.textContent = String(entrada.count);

                    fila.appendChild(punto);
                    fila.appendChild(nombre);
                    fila.appendChild(contador);
                    lista.appendChild(fila);
                });

                if (!estadoCompletoFluye()) {
                    const filaBloq = document.createElement('div');
                    filaBloq.className = 'flex items-center gap-2 text-[11px] text-slate-400 pt-1 mt-1 border-t border-cyan-900/30';
                    const puntoB = document.createElement('span');
                    puntoB.className = 'shrink-0 w-3 h-3 rounded-full';
                    puntoB.style.background = COLORES.pulsoRojo;
                    puntoB.style.boxShadow = '0 0 8px ' + COLORES.pulsoRojo;
                    const nombreB = document.createElement('span');
                    nombreB.textContent = 'Bloqueado en tienda';
                    filaBloq.appendChild(puntoB);
                    filaBloq.appendChild(nombreB);
                    lista.appendChild(filaBloq);
                }
            }

            function colorLineaSegmento(cat, porId) {
                const est = estadoCategoria(cat);
                if (!est.catEmite) return COLORES.cortada;
                if (conectaConTienda(cat, porId)) {
                    return est.tiendaOk ? colorActivo() : COLORES.cortada;
                }
                return colorActivo();
            }

            function opacidadLineaSegmento(cat) {
                return categoriaEmite(cat) ? 0.75 : 0.45;
            }

            function mapaCategoriasPorId(categorias) {
                const porId = new Map();
                categorias.forEach(function (cat) {
                    porId.set(String(cat.id), cat);
                });
                return porId;
            }

            function construirArbol(categorias) {
                const porId = mapaCategoriasPorId(categorias);
                const hijos = new Map();
                const raices = [];

                categorias.forEach(function (cat) {
                    const pid = cat.parent_id ? String(cat.parent_id) : null;
                    if (pid && porId.has(pid)) {
                        if (!hijos.has(pid)) hijos.set(pid, []);
                        hijos.get(pid).push(cat);
                    } else {
                        raices.push(cat);
                    }
                });

                function ordenar(arr) {
                    arr.sort(function (a, b) {
                        return String(a.nombre).localeCompare(String(b.nombre), 'es');
                    });
                }
                ordenar(raices);
                hijos.forEach(ordenar);

                const hojas = new Set();
                categorias.forEach(function (cat) {
                    const kids = hijos.get(String(cat.id)) || [];
                    if (!kids.length) hojas.add(String(cat.id));
                });

                return { porId, hijos, raices, hojas };
            }

            function layoutArbol2D(categorias) {
                const { hijos, raices } = construirArbol(categorias);
                const posMap = new Map();
                const radioBase = 130;
                const radioPaso = 95;

                function colocarHijos(parentId, depth, angCentro, angSpan) {
                    const kids = hijos.get(String(parentId)) || [];
                    if (!kids.length) return;
                    const radio = radioBase + depth * radioPaso;
                    const spanHijo = Math.min(Math.PI * 0.55, angSpan * 0.85);

                    kids.forEach(function (kid, i) {
                        const t = kids.length === 1 ? 0.5 : i / (kids.length - 1);
                        const ang = angCentro - spanHijo / 2 + t * spanHijo;
                        posMap.set(String(kid.id), { x: Math.cos(ang) * radio, y: Math.sin(ang) * radio, ang, depth });
                        colocarHijos(kid.id, depth + 1, ang, spanHijo);
                    });
                }

                raices.forEach(function (cat, i) {
                    const ang = (i / Math.max(1, raices.length)) * Math.PI * 2 - Math.PI / 2;
                    const spanRaiz = Math.min(Math.PI * 0.9, (Math.PI * 2 / Math.max(1, raices.length)) * 0.82);
                    posMap.set(String(cat.id), { x: Math.cos(ang) * radioBase, y: Math.sin(ang) * radioBase, ang, depth: 0 });
                    colocarHijos(cat.id, 1, ang, spanRaiz);
                });

                return posMap;
            }

            function distancia(a, b) {
                const dx = b.x - a.x;
                const dy = b.y - a.y;
                return Math.sqrt(dx * dx + dy * dy);
            }

            function colorActivoGlow() {
                return modoFlujo === 'scrapear' ? COLORES.activoScrapearGlow : COLORES.activoMostrarGlow;
            }

            function crearSegmentoBezier(x1, y1, x2, y2, esTienda) {
                const ctrl = controlCurva({ x: x1, y: y1 }, { x: x2, y: y2 });
                return { x1: x1, y1: y1, cx: ctrl.x, cy: ctrl.y, x2: x2, y2: y2, esTienda: !!esTienda };
            }

            function puntoBezier(seg, t) {
                const u = 1 - t;
                return {
                    x: u * u * seg.x1 + 2 * u * t * seg.cx + t * t * seg.x2,
                    y: u * u * seg.y1 + 2 * u * t * seg.cy + t * t * seg.y2,
                };
            }

            function longitudBezier(seg, pasos) {
                pasos = pasos || 24;
                let total = 0;
                let prev = puntoBezier(seg, 0);
                for (let i = 1; i <= pasos; i++) {
                    const p = puntoBezier(seg, i / pasos);
                    total += distancia(prev, p);
                    prev = p;
                }
                return total;
            }

            function puntoBezierPorFraccion(seg, fraccion) {
                const pasos = 32;
                const pts = [puntoBezier(seg, 0)];
                const segLens = [];
                let total = 0;
                for (let i = 1; i <= pasos; i++) {
                    const p = puntoBezier(seg, i / pasos);
                    const d = distancia(pts[pts.length - 1], p);
                    segLens.push(d);
                    total += d;
                    pts.push(p);
                }
                if (total < 0.001) return pts[0];
                let dist = fraccion * total;
                for (let i = 0; i < segLens.length; i++) {
                    if (dist <= segLens[i]) {
                        const p = dist / segLens[i];
                        return {
                            x: pts[i].x + (pts[i + 1].x - pts[i].x) * p,
                            y: pts[i].y + (pts[i + 1].y - pts[i].y) * p,
                        };
                    }
                    dist -= segLens[i];
                }
                return pts[pts.length - 1];
            }

            function puntoEnRutaCurvas(segmentos, t) {
                if (!segmentos || !segmentos.length) {
                    return { x: 0, y: 0, segmentoTienda: false };
                }
                const lens = segmentos.map(function (s) { return longitudBezier(s); });
                const total = lens.reduce(function (a, b) { return a + b; }, 0);
                if (total < 0.001) {
                    return { x: segmentos[0].x1, y: segmentos[0].y1, segmentoTienda: false };
                }
                let dist = t * total;
                for (let i = 0; i < segmentos.length; i++) {
                    if (dist <= lens[i]) {
                        const pos = puntoBezierPorFraccion(segmentos[i], dist / lens[i]);
                        return { x: pos.x, y: pos.y, segmentoTienda: segmentos[i].esTienda };
                    }
                    dist -= lens[i];
                }
                const ult = segmentos[segmentos.length - 1];
                const fin = puntoBezier(ult, 1);
                return { x: fin.x, y: fin.y, segmentoTienda: ult.esTienda };
            }

            function construirRutaPulsoCurvas(cat, porId, nodosPorId) {
                const segmentos = [];
                let actual = cat;

                if (!categoriaEmite(actual)) return segmentos;

                while (actual) {
                    const nodo = nodosPorId.get(String(actual.id));
                    if (!nodo) break;

                    const pid = actual.parent_id ? String(actual.parent_id) : null;
                    let destX, destY, esTienda = false;
                    let siguiente = null;

                    if (pid && porId.has(pid)) {
                        const padre = porId.get(pid);
                        const nodoPadre = nodosPorId.get(pid);
                        if (!nodoPadre) break;
                        destX = nodoPadre.x;
                        destY = nodoPadre.y;
                        siguiente = padre;
                    } else {
                        destX = 0;
                        destY = 0;
                        esTienda = true;
                    }

                    segmentos.push(crearSegmentoBezier(nodo.x, nodo.y, destX, destY, esTienda));

                    if (esTienda) break;
                    if (siguiente && bloqueaSubida(siguiente)) break;
                    actual = siguiente;
                }

                return segmentos;
            }

            function destinoRama(cat, porId, posMap) {
                const pid = cat.parent_id ? String(cat.parent_id) : null;
                if (pid && porId.has(pid)) {
                    return posMap.get(pid);
                }
                return { x: 0, y: 0 };
            }

            function controlCurva(desde, hasta) {
                const mx = (desde.x + hasta.x) / 2;
                const my = (desde.y + hasta.y) / 2;
                const dx = hasta.x - desde.x;
                const dy = hasta.y - desde.y;
                const len = Math.sqrt(dx * dx + dy * dy) || 1;
                const curv = Math.min(28, len * 0.12);
                const px = -dy / len;
                const py = dx / len;
                const signo = desde.x * hasta.y - desde.y * hasta.x >= 0 ? 1 : -1;
                return { x: mx + px * curv * signo, y: my + py * curv * signo };
            }

            function pantallaAWorld(sx, sy) {
                const w = canvas.clientWidth;
                const h = canvas.clientHeight;
                return {
                    x: (sx - w / 2 - view.panX) / view.scale,
                    y: (sy - h / 2 - view.panY) / view.scale,
                };
            }

            function ajustarVistaInicial(posMap) {
                let minX = 0, maxX = 0, minY = 0, maxY = 0;
                posMap.forEach(function (p) {
                    minX = Math.min(minX, p.x);
                    maxX = Math.max(maxX, p.x);
                    minY = Math.min(minY, p.y);
                    maxY = Math.max(maxY, p.y);
                });
                const margen = 80;
                const bboxW = maxX - minX + margen * 2;
                const bboxH = maxY - minY + margen * 2;
                const cw = canvas.clientWidth;
                const ch = canvas.clientHeight;
                view.scale = Math.min(cw / bboxW, ch / bboxH, 1.8);
                view.panX = 0;
                view.panY = 0;
            }

            function reconstruirNodos() {
                nodosCat.length = 0;
                tiendaNodo = null;

                const categorias = flujoData.categorias || [];
                const arbol = construirArbol(categorias);
                arbolHijos = arbol.hijos;
                hojasIds = arbol.hojas;
                const porId = arbol.porId;
                const posMap = layoutArbol2D(categorias);
                const maxOfertas = Math.max(1, ...categorias.map(function (c) {
                    return c.ofertas_total ?? c.ofertas ?? 1;
                }));

                tiendaNodo = {
                    tipo: 'tienda',
                    x: 0,
                    y: 0,
                    radio: 22,
                    nombre: flujoData.tienda.nombre,
                };

                categorias.forEach(function (cat) {
                    const pos = posMap.get(String(cat.id));
                    if (!pos) return;

                    const est = estadoCategoria(cat);
                    const esEstructural = cat.con_ofertas === false;
                    const ofertasPeso = cat.ofertas_total ?? cat.ofertas ?? 0;
                    const radio = esEstructural
                        ? 6 + (ofertasPeso / maxOfertas) * 8
                        : 8 + (ofertasPeso / maxOfertas) * 12;

                    const destino = destinoRama(cat, porId, posMap);
                    const esHoja = hojasIds.has(String(cat.id));
                    const conectaTienda = conectaConTienda(cat, porId);

                    nodosCat.push({
                        cat,
                        x: pos.x,
                        y: pos.y,
                        radio,
                        destinoX: destino.x,
                        destinoY: destino.y,
                        esEstructural,
                        esHoja,
                        conectaTienda,
                        est,
                        fase: Math.random(),
                        velocidad: 0.12 + Math.random() * 0.1,
                    });
                });

                if (!inicializado) ajustarVistaInicial(posMap);
            }

            function dibujarFondoPantalla(w, h, t) {
                const grd = ctx.createRadialGradient(w * 0.5, h * 0.45, 0, w * 0.5, h * 0.5, Math.max(w, h) * 0.65);
                grd.addColorStop(0, '#0a0f1e');
                grd.addColorStop(0.45, COLORES.fondo);
                grd.addColorStop(1, '#000000');
                ctx.fillStyle = grd;
                ctx.fillRect(0, 0, w, h);

                ctx.fillStyle = COLORES.fondoGlow;
                ctx.beginPath();
                ctx.arc(w * 0.5, h * 0.5, Math.min(w, h) * 0.22, 0, Math.PI * 2);
                ctx.fill();
            }

            function dibujarGrid() {
                const paso = 40;
                const w = canvas.clientWidth / view.scale;
                const h = canvas.clientHeight / view.scale;
                const ox = -view.panX / view.scale - w / 2;
                const oy = -view.panY / view.scale - h / 2;
                const t = (performance.now() - tiempoInicio) / 1000;

                ctx.strokeStyle = COLORES.grid;
                ctx.lineWidth = 0.5 / view.scale;
                const x0 = Math.floor(ox / paso) * paso;
                const y0 = Math.floor(oy / paso) * paso;
                for (let x = x0; x < ox + w; x += paso) {
                    ctx.beginPath();
                    ctx.moveTo(x, oy);
                    ctx.lineTo(x, oy + h);
                    ctx.stroke();
                }
                for (let y = y0; y < oy + h; y += paso) {
                    ctx.beginPath();
                    ctx.moveTo(ox, y);
                    ctx.lineTo(ox + w, y);
                    ctx.stroke();
                }

                const pasoPuntos = paso * 2;
                const px0 = Math.floor(ox / pasoPuntos) * pasoPuntos;
                const py0 = Math.floor(oy / pasoPuntos) * pasoPuntos;
                for (let x = px0; x < ox + w; x += pasoPuntos) {
                    for (let y = py0; y < oy + h; y += pasoPuntos) {
                        const brillo = 0.35 + Math.sin(t * 0.8 + x * 0.01 + y * 0.01) * 0.15;
                        ctx.globalAlpha = brillo;
                        ctx.fillStyle = COLORES.gridPunto;
                        ctx.beginPath();
                        ctx.arc(x, y, 1.2 / view.scale, 0, Math.PI * 2);
                        ctx.fill();
                    }
                }
                ctx.globalAlpha = 1;
            }

            function trazarBezier(seg) {
                ctx.beginPath();
                ctx.moveTo(seg.x1, seg.y1);
                ctx.quadraticCurveTo(seg.cx, seg.cy, seg.x2, seg.y2);
            }

            function dibujarConexionNeuronal(seg, color, glowColor, opacidad, t, animada) {
                trazarBezier(seg);

                ctx.save();
                ctx.strokeStyle = glowColor || color;
                ctx.globalAlpha = opacidad * 0.35;
                ctx.lineWidth = 8 / view.scale;
                ctx.shadowColor = glowColor || color;
                ctx.shadowBlur = 14 / view.scale;
                ctx.stroke();
                ctx.restore();

                trazarBezier(seg);
                ctx.strokeStyle = color;
                ctx.globalAlpha = opacidad * 0.55;
                ctx.lineWidth = 1.2 / view.scale;
                ctx.stroke();

                if (animada) {
                    trazarBezier(seg);
                    ctx.setLineDash([6 / view.scale, 14 / view.scale]);
                    ctx.lineDashOffset = -(t * 28) / view.scale;
                    ctx.strokeStyle = glowColor || color;
                    ctx.globalAlpha = 0.85;
                    ctx.lineWidth = 2 / view.scale;
                    ctx.stroke();
                    ctx.setLineDash([]);
                }
                ctx.globalAlpha = 1;
            }

            function dibujarNodoTienda(t, tiendaOk) {
                const colorT = tiendaOk ? COLORES.tienda : COLORES.tiendaInactiva;
                const glowT = tiendaOk ? COLORES.tiendaGlow : COLORES.cortadaGlow;
                const pulsoT = 0.92 + Math.sin(t * 1.4) * 0.06;

                for (let i = 3; i >= 1; i--) {
                    ctx.beginPath();
                    ctx.arc(0, 0, (38 + i * 10) * pulsoT, 0, Math.PI * 2);
                    ctx.strokeStyle = tiendaOk ? 'rgba(34,197,94,' + (0.08 + i * 0.04) + ')' : 'rgba(239,68,68,' + (0.08 + i * 0.04) + ')';
                    ctx.lineWidth = 1 / view.scale;
                    ctx.stroke();
                }

                ctx.save();
                ctx.beginPath();
                ctx.arc(0, 0, 28 * pulsoT, 0, Math.PI * 2);
                const grd = ctx.createRadialGradient(0, 0, 0, 0, 0, 28 * pulsoT);
                grd.addColorStop(0, glowT);
                grd.addColorStop(0.55, colorT);
                grd.addColorStop(1, 'rgba(3,7,18,0.9)');
                ctx.fillStyle = grd;
                ctx.shadowColor = glowT;
                ctx.shadowBlur = 22 / view.scale;
                ctx.fill();
                ctx.restore();

                ctx.beginPath();
                ctx.arc(0, 0, 28 * pulsoT, 0, Math.PI * 2);
                ctx.strokeStyle = 'rgba(255,255,255,0.35)';
                ctx.lineWidth = 1.5 / view.scale;
                ctx.stroke();

                ctx.save();
                ctx.translate(0, 0);
                ctx.rotate(t * 0.35);
                ctx.beginPath();
                ctx.arc(0, 0, 36 * pulsoT, 0, Math.PI * 1.2);
                ctx.strokeStyle = tiendaOk ? 'rgba(74,222,128,0.5)' : 'rgba(248,113,113,0.5)';
                ctx.lineWidth = 2 / view.scale;
                ctx.setLineDash([4 / view.scale, 8 / view.scale]);
                ctx.stroke();
                ctx.setLineDash([]);
                ctx.restore();
            }

            function dibujarNodoCategoria(x, y, radio, fill, glowColor, emite, t, fase) {
                const pulsoN = emite ? 1 + Math.sin(t * 2.2 + fase * 10) * 0.05 : 1;
                const r = radio * pulsoN;

                if (emite && glowColor) {
                    ctx.save();
                    ctx.beginPath();
                    ctx.arc(x, y, r + 6 / view.scale, 0, Math.PI * 2);
                    ctx.fillStyle = glowColor;
                    ctx.globalAlpha = 0.15;
                    ctx.fill();
                    ctx.restore();
                }

                ctx.save();
                ctx.beginPath();
                ctx.arc(x, y, r, 0, Math.PI * 2);
                const grd = ctx.createRadialGradient(x - r * 0.3, y - r * 0.3, 0, x, y, r);
                grd.addColorStop(0, emite ? (glowColor || fill) : '#475569');
                grd.addColorStop(0.6, fill);
                grd.addColorStop(1, '#0f172a');
                ctx.fillStyle = grd;
                if (emite) {
                    ctx.shadowColor = glowColor || fill;
                    ctx.shadowBlur = 10 / view.scale;
                }
                ctx.fill();
                ctx.restore();

                ctx.beginPath();
                ctx.arc(x, y, r, 0, Math.PI * 2);
                ctx.strokeStyle = emite ? 'rgba(255,255,255,0.28)' : 'rgba(255,255,255,0.1)';
                ctx.lineWidth = 1 / view.scale;
                ctx.stroke();

                if (emite) {
                    ctx.beginPath();
                    ctx.arc(x, y, r * 0.35, 0, Math.PI * 2);
                    ctx.fillStyle = 'rgba(255,255,255,0.75)';
                    ctx.fill();
                }
            }

            function dibujarEtiqueta(texto, x, y, esTienda, colorAcento) {
                const fontSize = (esTienda ? 10 : 8.5) / view.scale;
                ctx.font = (esTienda ? '600 ' : '400 ') + fontSize + 'px ui-monospace, SFMono-Regular, Menlo, monospace';
                ctx.textAlign = 'center';
                ctx.textBaseline = 'top';
                const metrics = ctx.measureText(texto);
                const padX = 6 / view.scale;
                const padY = 3 / view.scale;
                const tw = metrics.width + padX * 2;
                const th = fontSize * 1.35 + padY * 2;
                const tx = x - tw / 2;
                const ty = y + (esTienda ? 34 : 12) / view.scale;
                const acento = colorAcento || (esTienda ? COLORES.tienda : 'rgba(56,189,248,0.5)');

                ctx.fillStyle = 'rgba(3, 10, 24, 0.88)';
                ctx.strokeStyle = acento;
                ctx.lineWidth = 1 / view.scale;
                ctx.fillRect(tx, ty, tw, th);
                ctx.strokeRect(tx, ty, tw, th);

                ctx.fillStyle = esTienda ? COLORES.textoTienda : COLORES.texto;
                ctx.fillText(texto, x, ty + padY);
            }

            function dibujarPulsoCometa(segmentos, ciclo, colores, t) {
                const color = typeof colores === 'string' ? colores : colores.core;
                const glow = typeof colores === 'string' ? colores : (colores.glow || colores.core);
                const pos = puntoEnRutaCurvas(segmentos, ciclo);
                const cola = [];
                for (let i = 1; i <= 5; i++) {
                    const tCola = Math.max(0, ciclo - i * 0.018);
                    cola.push(puntoEnRutaCurvas(segmentos, tCola));
                }

                cola.forEach(function (p, i) {
                    const alpha = 0.55 - i * 0.1;
                    const radio = (7 - i * 1.1) / view.scale;
                    ctx.beginPath();
                    ctx.arc(p.x, p.y, radio, 0, Math.PI * 2);
                    ctx.fillStyle = color;
                    ctx.globalAlpha = alpha;
                    ctx.shadowColor = glow;
                    ctx.shadowBlur = (16 - i * 2) / view.scale;
                    ctx.fill();
                });
                ctx.globalAlpha = 1;
                ctx.shadowBlur = 0;

                ctx.beginPath();
                ctx.arc(pos.x, pos.y, 4 / view.scale, 0, Math.PI * 2);
                ctx.fillStyle = '#ffffff';
                ctx.shadowColor = glow;
                ctx.shadowBlur = 18 / view.scale;
                ctx.fill();
                ctx.shadowBlur = 0;
            }

            function renderizar() {
                if (!ctx) return;
                const w = canvas.clientWidth;
                const h = canvas.clientHeight;
                const t = (performance.now() - tiempoInicio) / 1000;

                ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
                dibujarFondoPantalla(w, h, t);

                ctx.save();
                ctx.translate(w / 2 + view.panX, h / 2 + view.panY);
                ctx.scale(view.scale, view.scale);

                dibujarGrid();

                const porIdRender = mapaCategoriasPorId(flujoData.categorias || []);
                const nodosPorId = new Map();
                nodosCat.forEach(function (n) {
                    nodosPorId.set(String(n.cat.id), n);
                });

                nodosCat.forEach(function (nodo) {
                    nodo.est = estadoCategoria(nodo.cat);
                    const lineColor = colorLineaSegmento(nodo.cat, porIdRender);
                    const esActiva = categoriaEmite(nodo.cat);
                    const glowLinea = lineColor === COLORES.cortada ? COLORES.cortadaGlow : colorActivoGlow();
                    const seg = crearSegmentoBezier(nodo.x, nodo.y, nodo.destinoX, nodo.destinoY, nodo.conectaTienda);
                    dibujarConexionNeuronal(
                        seg,
                        lineColor,
                        glowLinea,
                        opacidadLineaSegmento(nodo.cat),
                        t,
                        esActiva && lineColor !== COLORES.cortada
                    );
                });

                const tiendaOk = estadoCompletoFluye();

                if (tiendaNodo) {
                    dibujarNodoTienda(t, tiendaOk);
                    dibujarEtiqueta(truncarNombre(tiendaNodo.nombre, 36), 0, 0, true, COLORES.tienda);
                }

                nodosCat.forEach(function (nodo) {
                    const est = nodo.est;
                    const emite = est.catEmite;
                    let fill = COLORES.inactivo;
                    let glow = null;

                    if (emite) {
                        fill = colorActivo();
                        glow = colorActivoGlow();
                    } else if (nodo.esEstructural) {
                        fill = COLORES.estructural;
                    }

                    dibujarNodoCategoria(nodo.x, nodo.y, nodo.radio, fill, glow, emite, t, nodo.fase);
                    dibujarEtiqueta(
                        truncarNombre(nodo.cat.nombre, 28),
                        nodo.x,
                        nodo.y,
                        false,
                        emite ? colorActivo() : 'rgba(100,116,139,0.45)'
                    );
                });

                nodosCat.forEach(function (nodo) {
                    if (!nodo.esHoja || !nodo.est.catEmite) return;

                    const segmentos = construirRutaPulsoCurvas(nodo.cat, porIdRender, nodosPorId);
                    if (!segmentos.length) return;

                    const est = nodo.est;
                    const ciclo = (t * nodo.velocidad + nodo.fase) % 1;
                    const pos = puntoEnRutaCurvas(segmentos, ciclo);
                    const colorP = colorPulso(est, pos.segmentoTienda, nodo.cat);
                    dibujarPulsoCometa(segmentos, ciclo, colorP, t);
                });

                ctx.restore();
            }

            function animar() {
                animId = requestAnimationFrame(animar);
                renderizar();
            }

            function actualizarVisuales() {
                recalcularFlujo();
                reconstruirNodos();
                actualizarResumen();
                actualizarLeyendaApis();
            }

            function actualizarResumen() {
                const el = document.getElementById('mapa-neuronal-resumen');
                if (!el || !flujoData.stats) return;
                el.textContent = 'Scraping ' + flujoData.stats.scrapear.activas + '/' + flujoData.stats.scrapear.total
                    + ' · Mostrar ' + flujoData.stats.mostrar.activas + '/' + flujoData.stats.mostrar.total;
            }

            function onResize() {
                if (!canvas || !contenedor) return;
                const w = contenedor.clientWidth;
                const h = contenedor.clientHeight;
                if (w < 1 || h < 1) return;
                dpr = Math.min(window.devicePixelRatio || 1, 2);
                canvas.width = Math.floor(w * dpr);
                canvas.height = Math.floor(h * dpr);
                canvas.style.width = w + 'px';
                canvas.style.height = h + 'px';
                if (inicializado) renderizar();
            }

            function nodoEnPos(worldX, worldY) {
                if (tiendaNodo) {
                    const dx = worldX - tiendaNodo.x;
                    const dy = worldY - tiendaNodo.y;
                    if (Math.sqrt(dx * dx + dy * dy) <= tiendaNodo.radio + 4) {
                        return { tipo: 'tienda', nombre: tiendaNodo.nombre };
                    }
                }
                for (let i = nodosCat.length - 1; i >= 0; i--) {
                    const n = nodosCat[i];
                    const dx = worldX - n.x;
                    const dy = worldY - n.y;
                    if (Math.sqrt(dx * dx + dy * dy) <= n.radio + 3) {
                        return { tipo: 'categoria', cat: n.cat };
                    }
                }
                return null;
            }

            function onPointerDown(e) {
                drag.activo = true;
                drag.x = e.clientX;
                drag.y = e.clientY;
                drag.panX0 = view.panX;
                drag.panY0 = view.panY;
                contenedor.setPointerCapture(e.pointerId);
            }

            function onPointerMove(e) {
                const rect = canvas.getBoundingClientRect();
                const sx = e.clientX - rect.left;
                const sy = e.clientY - rect.top;

                if (drag.activo) {
                    view.panX = drag.panX0 + (e.clientX - drag.x);
                    view.panY = drag.panY0 + (e.clientY - drag.y);
                    return;
                }

                if (!tooltip) return;
                const world = pantallaAWorld(sx, sy);
                const hit = nodoEnPos(world.x, world.y);
                if (hit) {
                    let texto = '';
                    if (hit.tipo === 'tienda') {
                        texto = hit.nombre + ' (tienda)';
                    } else {
                        const est = estadoCategoria(hit.cat);
                        const iconApi = hit.cat.api_icon || metaIconoApiJs(hit.cat.api);
                        texto = hit.cat.nombre + ' · ' + hit.cat.ofertas + ' ofertas · ' + (est.catEmite ? (est.finalOk ? 'activa' : 'activa (tienda bloquea)') : 'inactiva');
                        if (modoFlujo === 'scrapear') {
                            texto += ' · API: ' + (iconApi.nombre || iconApi.base || 'Sin API');
                        }
                    }
                    tooltip.textContent = texto;
                    tooltip.classList.remove('hidden');
                    tooltip.style.left = (sx + 12) + 'px';
                    tooltip.style.top = (sy + 12) + 'px';
                } else {
                    tooltip.classList.add('hidden');
                }
            }

            function onPointerUp(e) {
                drag.activo = false;
                try { contenedor.releasePointerCapture(e.pointerId); } catch (_) {}
            }

            function onWheel(e) {
                e.preventDefault();
                const rect = canvas.getBoundingClientRect();
                const sx = e.clientX - rect.left;
                const sy = e.clientY - rect.top;
                const worldAntes = pantallaAWorld(sx, sy);

                const factor = e.deltaY > 0 ? 0.92 : 1.08;
                view.scale = Math.max(0.15, Math.min(4, view.scale * factor));

                const w = canvas.clientWidth;
                const h = canvas.clientHeight;
                view.panX = sx - w / 2 - worldAntes.x * view.scale;
                view.panY = sy - h / 2 - worldAntes.y * view.scale;
            }

            function initCanvas() {
                if (inicializado) {
                    onResize();
                    return Promise.resolve();
                }

                ctx = canvas.getContext('2d');
                tiempoInicio = performance.now();

                recalcularFlujo();
                reconstruirNodos();
                onResize();

                contenedor.addEventListener('pointerdown', onPointerDown);
                contenedor.addEventListener('pointermove', onPointerMove);
                contenedor.addEventListener('pointerup', onPointerUp);
                contenedor.addEventListener('pointercancel', onPointerUp);
                contenedor.addEventListener('pointerleave', function () {
                    drag.activo = false;
                    if (tooltip) tooltip.classList.add('hidden');
                });
                contenedor.addEventListener('wheel', onWheel, { passive: false });

                const leyendaApis = document.getElementById('mapa-neuronal-leyenda-apis');
                if (leyendaApis) {
                    leyendaApis.addEventListener('pointerdown', function (e) {
                        e.stopPropagation();
                    });
                }

                if (typeof ResizeObserver !== 'undefined') {
                    resizeObserver = new ResizeObserver(onResize);
                    resizeObserver.observe(contenedor);
                }
                window.addEventListener('resize', onResize);

                animar();
                actualizarLeyendaApis();
                inicializado = true;
                return Promise.resolve();
            }

            function setModoBotones(modo) {
                const btnS = document.getElementById('mapa-neuronal-modo-scraping');
                const btnM = document.getElementById('mapa-neuronal-modo-mostrar');
                const on = 'px-3 py-1.5 text-sm rounded-lg border border-orange-500 bg-orange-50 dark:bg-orange-900/30 text-orange-700 dark:text-orange-300 font-semibold';
                const off = 'px-3 py-1.5 text-sm rounded-lg border border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:border-orange-300';
                if (btnS) btnS.className = 'mapa-neuronal-modo-btn ' + (modo === 'scrapear' ? on : off);
                if (btnM) btnM.className = 'mapa-neuronal-modo-btn ' + (modo === 'mostrar' ? on : off);
            }

            return {
                setModo: function (modo) {
                    modoFlujo = modo === 'mostrar' ? 'mostrar' : 'scrapear';
                    setModoBotones(modoFlujo);
                    if (inicializado) actualizarVisuales();
                },
                actualizarTienda: function (campo, valor) {
                    if (campo === 'scrapear' || campo === 'mostrar') {
                        flujoData.tienda[campo] = valor === 'no' ? 'no' : 'si';
                        if (inicializado) actualizarVisuales();
                    }
                },
                actualizarCategoria: function (catId, datos) {
                    const cat = (flujoData.categorias || []).find(function (c) { return String(c.id) === String(catId); });
                    if (!cat) return;
                    if (datos.scrapear !== undefined) cat.scrapear = datos.scrapear;
                    if (datos.mostrar !== undefined) cat.mostrar = datos.mostrar;
                    if (datos.scrapear_explicito !== undefined) cat.scrapear_explicito = !!datos.scrapear_explicito;
                    if (datos.mostrar_explicito !== undefined) cat.mostrar_explicito = !!datos.mostrar_explicito;
                    if (datos.api !== undefined) {
                        cat.api = datos.api || null;
                        cat.api_base = apiBaseDesdeValor(cat.api);
                        cat.api_icon = metaIconoApiJs(cat.api);
                    }
                    if (inicializado) actualizarVisuales();
                },
                refrescar: function () {
                    if (inicializado) {
                        actualizarVisuales();
                        onResize();
                    }
                },
                redimensionar: function () {
                    onResize();
                },
                estaInicializado: function () {
                    return inicializado;
                },
                iniciar: initCanvas,
                destruir: function () {
                    if (animId) cancelAnimationFrame(animId);
                    window.removeEventListener('resize', onResize);
                    if (resizeObserver) resizeObserver.disconnect();
                    contenedor.removeEventListener('pointerdown', onPointerDown);
                    contenedor.removeEventListener('pointermove', onPointerMove);
                    contenedor.removeEventListener('pointerup', onPointerUp);
                    contenedor.removeEventListener('pointercancel', onPointerUp);
                    contenedor.removeEventListener('wheel', onWheel);
                    nodosCat.length = 0;
                    tiendaNodo = null;
                    inicializado = false;
                },
            };
        }

        document.addEventListener('DOMContentLoaded', function () {
            const detalle = document.getElementById('mapa-neuronal-tienda-detalle');
            const datosEl = document.getElementById('mapa-neuronal-datos');
            const canvas = document.getElementById('mapa-neuronal-canvas');
            const contenedor = document.getElementById('mapa-neuronal-contenedor');
            const tooltip = document.getElementById('mapa-neuronal-tooltip');
            if (!detalle || !datosEl || !canvas) return;

            let mapa = null;
            let arrancando = false;
            let eventosMapaListos = false;

            function esperarContenedorListo(callback) {
                let intentos = 0;
                function comprobar() {
                    const w = contenedor.clientWidth;
                    const h = contenedor.clientHeight;
                    if ((w > 20 && h > 20) || intentos > 80) {
                        callback();
                    } else {
                        intentos++;
                        requestAnimationFrame(comprobar);
                    }
                }
                requestAnimationFrame(comprobar);
            }

            function enlazarEventosMapa() {
                if (eventosMapaListos || !mapa) return;
                eventosMapaListos = true;

                document.getElementById('mapa-neuronal-modo-scraping')?.addEventListener('click', function () {
                    mapa.setModo('scrapear');
                });
                document.getElementById('mapa-neuronal-modo-mostrar')?.addEventListener('click', function () {
                    mapa.setModo('mostrar');
                });

                const selectScrapearTienda = document.querySelector('select[name="scrapear"]');
                const selectMostrarTienda = document.querySelector('select[name="mostrar_tienda"]');
                if (selectScrapearTienda) {
                    selectScrapearTienda.addEventListener('change', function () {
                        mapa.actualizarTienda('scrapear', selectScrapearTienda.value);
                    });
                }
                if (selectMostrarTienda) {
                    selectMostrarTienda.addEventListener('change', function () {
                        mapa.actualizarTienda('mostrar', selectMostrarTienda.value);
                    });
                }
            }

            function arrancarMapa() {
                if (!detalle.open) return;

                const cargando = document.getElementById('mapa-neuronal-cargando');
                const errorEl = document.getElementById('mapa-neuronal-error');

                if (mapa && mapa.estaInicializado()) {
                    mapa.redimensionar();
                    if (cargando) cargando.classList.add('hidden');
                    if (errorEl) errorEl.classList.add('hidden');
                    return;
                }
                if (arrancando) return;
                arrancando = true;
                if (cargando) cargando.classList.remove('hidden');
                if (errorEl) errorEl.classList.add('hidden');

                esperarContenedorListo(function () {
                    if (!mapa) {
                        const flujoData = JSON.parse(datosEl.textContent);
                        mapa = crearMapaNeuronalTienda({ canvas, contenedor, tooltip, flujoData });
                        window.komparadorFlujoScraping = mapa;
                        enlazarEventosMapa();
                    }

                    mapa.iniciar().then(function () {
                        arrancando = false;
                        if (cargando) cargando.classList.add('hidden');
                        mapa.redimensionar();
                    }).catch(function (err) {
                        arrancando = false;
                        if (cargando) cargando.classList.add('hidden');
                        if (errorEl) {
                            errorEl.textContent = 'No se pudo cargar el mapa. Recarga la página.';
                            errorEl.classList.remove('hidden');
                        }
                        console.error('Mapa neuronal:', err);
                    });
                });
            }

            detalle.addEventListener('toggle', function () {
                if (detalle.open) {
                    arrancarMapa();
                }
            });
            if (detalle.open) arrancarMapa();
        });
    </script>
    @endif


    {{-- MODAL Y SCRIPTS PARA GESTIÓN DE IMÁGENES DE TIENDAS --}}
    {{-- MODAL PARA AÑADIR IMAGEN DE TIENDA (mismo patrón que categorías) --}}
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

        <div id="modal-añadir-imagen-tienda" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
            <div class="bg-white dark:bg-gray-900 rounded-lg p-6 max-w-4xl w-full relative shadow-xl overflow-y-auto max-h-[90vh]">
                <button type="button" onclick="cerrarModalAñadirImagenTienda()" class="absolute top-3 right-4 text-xl text-gray-800 dark:text-gray-100 hover:text-gray-600 dark:hover:text-gray-300">×</button>
                <div class="mb-4">
                    <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-200">Añadir logo de tienda</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">La imagen se guardará redimensionada a 100×45 en la carpeta <strong class="font-medium">tiendas</strong>.</p>
                </div>

                <div class="kp-modal-img-tabs mb-4" role="tablist" aria-label="Origen de la imagen">
                    <nav class="kp-modal-img-tabs__nav">
                        <button type="button" id="tab-url-tnd" class="tab-modal-tnd kp-modal-img-tab kp-modal-img-tab--active" role="tab" aria-selected="true">
                            Descargar desde URL
                        </button>
                        <button type="button" id="tab-subir-tnd" class="tab-modal-tnd kp-modal-img-tab" role="tab" aria-selected="false">
                            Subir imagen
                        </button>
                        <button type="button" id="tab-amazon-tnd" class="tab-modal-tnd kp-modal-img-tab" role="tab" aria-selected="false">
                            Amazon
                        </button>
                        <button type="button" id="tab-interna-global-tnd" class="tab-modal-tnd kp-modal-img-tab" role="tab" aria-selected="false">
                            Interna Global
                        </button>
                    </nav>
                </div>

                <div id="content-url-tnd" class="tab-content-modal-tnd space-y-4">
                    <div>
                        <label class="block mb-2 text-sm text-gray-600 dark:text-gray-400">URL de la imagen</label>
                        <div class="flex gap-2">
                            <input type="url" id="url-imagen-tnd" class="flex-1 px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border text-sm" placeholder="https://ejemplo.com/imagen.jpg">
                            <button type="button" id="btn-descargar-url-tnd" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm">
                                Descargar
                            </button>
                        </div>
                        <div id="error-url-tnd" class="mt-1 text-sm text-red-500 hidden"></div>
                    </div>
                    <div id="area-recorte-tnd" class="hidden space-y-4">
                        <div class="mb-4">
                            <h4 class="text-md font-semibold text-gray-700 dark:text-gray-200 mb-2">Recortar imagen</h4>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">Selecciona el área que deseas mantener. Se redimensionará a 100×45.</p>
                            <div id="contenedor-cropper-tnd" style="max-width: 650px; max-height: 450px; margin: 0 auto; overflow: hidden;">
                                <img id="imagen-recortar-tnd" src="" alt="Imagen a recortar" style="display: block; max-width: 100%; height: auto;">
                            </div>
                        </div>
                    </div>
                </div>

                <div id="content-subir-tnd" class="tab-content-modal-tnd space-y-4 hidden">
                    <div>
                        <label class="block mb-2 text-sm text-gray-600 dark:text-gray-400">Seleccionar imagen</label>
                        <input type="file" id="file-subir-tnd" accept="image/*" class="hidden">
                        <button type="button" id="btn-seleccionar-tnd" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm mb-2">
                            Seleccionar archivo
                        </button>
                        <div id="drop-zone-tnd" class="mt-2 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-4 text-center hover:border-blue-400 dark:hover:border-blue-500 transition-colors">
                            <div class="text-gray-500 dark:text-gray-400">
                                <svg class="mx-auto h-8 w-8 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                </svg>
                                <p>Arrastra y suelta la imagen aquí</p>
                                <p class="text-xs">o haz clic para seleccionar</p>
                            </div>
                        </div>
                        <span id="nombre-archivo-tnd" class="text-sm text-gray-500 dark:text-gray-400"></span>
                    </div>
                </div>

                <div id="content-amazon-tnd" class="tab-content-modal-tnd space-y-4 hidden">
                    <div>
                        <label class="block mb-2 text-sm text-gray-600 dark:text-gray-400">URL del producto de Amazon</label>
                        <div class="flex gap-2">
                            <input type="url" id="url-amazon-tnd" class="flex-1 px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border text-sm" placeholder="https://www.amazon.es/dp/XXXXXXXXXX">
                            <button type="button" id="btn-buscar-amazon-tnd" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm">
                                Buscar
                            </button>
                        </div>
                        <div id="error-amazon-tnd" class="mt-1 text-sm text-red-500 hidden"></div>
                        <div id="loading-amazon-tnd" class="mt-2 text-sm text-gray-500 dark:text-gray-400 hidden">Buscando imágenes...</div>
                    </div>
                    <div id="imagenes-amazon-tnd" class="hidden">
                        <label class="block mb-2 text-sm font-semibold text-gray-700 dark:text-gray-200">Selecciona una imagen:</label>
                        <div id="grid-imagenes-amazon-tnd" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 max-h-96 overflow-y-auto p-2"></div>
                    </div>
                </div>

                <div id="content-interna-global-tnd" class="tab-content-modal-tnd space-y-4 hidden" data-kp-ig-prefix="cat">
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Imágenes recientes del almacén. Pulsa una palabra del nombre de la tienda para buscar. Haz clic en una miniatura para seleccionarla (solo una). Si ya es 100×45 se reutiliza tal cual; si es más grande se redimensionará y guardará en <strong class="font-medium">tiendas</strong>. Pulsa <strong class="font-medium">Guardar</strong> al terminar.
                    </p>
                    <div class="kp-ig-panel border border-gray-200 dark:border-gray-600 rounded-lg p-3 bg-gray-50/80 dark:bg-gray-800/40 space-y-3" data-kp-ig-panel="cat">
                        <div class="kp-ig-seleccion border border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-3 bg-white/70 dark:bg-gray-900/50">
                            <p class="text-xs font-medium text-gray-700 dark:text-gray-300 mb-2">Seleccionada para guardar</p>
                            <div class="kp-ig-seleccion-resumen text-xs text-gray-500 dark:text-gray-400">Ninguna imagen seleccionada.</div>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Palabras del nombre de la tienda</p>
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
                    <button type="button" onclick="cerrarModalAñadirImagenTienda()" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">
                        Cancelar
                    </button>
                    <button type="button" id="btn-guardar-tnd" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700">
                        Guardar
                    </button>
                </div>
            </div>
        </div>

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
            est.wrapPalabras.innerHTML = '<span class="text-xs text-gray-500 dark:text-gray-400">Escribe el nombre de la tienda para ver sugerencias.</span>';
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
        const formTiendaImg = document.getElementById('form-tienda');
        if (!formTiendaImg) return;

        const KP_CARPETA_TND = 'tiendas';
        const KP_W_TND = 100;
        const KP_H_TND = 45;
        const KP_PENDING_TND = '__pending_tnd:';
        const KP_IMG_BASE = @json(rtrim(asset('images/'), '/'));
        const KP_CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';
        const KP_SUBIR_URL = @json(route('admin.imagenes.subir-simple'));
        const KP_PROXY_URL = @json(route('admin.imagenes.proxy'));
        const KP_AMAZON_URL = @json(route('admin.productos.obtener-imagenes-amazon'));
        const KP_LIMPIAR_AMAZON_URL = @json(route('admin.ofertas.limpiar.url'));

        let rutaImagenTienda = '';
        let subidaPendienteTnd = false;
        let cropperTnd = null;
        let carpetaActualUrlTnd = KP_CARPETA_TND;
        let imagenAmazonSeleccionadaTnd = null;
        let seleccionInternaGlobalTnd = null;

        const inputRuta = document.getElementById('ruta-imagen-tienda');
        const contenedor = document.getElementById('imagen-tienda-container');
        const textoRuta = document.getElementById('ruta-imagen-tienda-texto');
        const btnAñadir = document.getElementById('btn-añadir-imagen-tienda');
        const btnCambiar = document.getElementById('btn-cambiar-imagen-tienda');
        const modal = document.getElementById('modal-añadir-imagen-tienda');

        function obtenerNombreArchivoTienda() {
            const nombreInput = document.getElementById('nombre-tienda');
            if (!nombreInput) return '';
            const raw = nombreInput.value.trim();
            if (!raw) return '';
            return raw.toLowerCase()
                .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/^-+|-+$/g, '');
        }

        function validarNombreParaImagen() {
            if (!obtenerNombreArchivoTienda()) {
                alert('Escribe el nombre de la tienda antes de añadir la imagen.');
                return false;
            }
            return true;
        }

        function urlVistaTnd(ruta) {
            if (!ruta || ruta.indexOf(KP_PENDING_TND) === 0) return '';
            if (/^https?:\/\//i.test(ruta)) return ruta;
            return KP_IMG_BASE + '/' + ruta.replace(/^\/+/, '');
        }

        function aplicarRutaImagenTienda(ruta) {
            rutaImagenTienda = ruta || '';
            if (inputRuta) inputRuta.value = rutaImagenTienda;
            if (textoRuta) {
                if (rutaImagenTienda && rutaImagenTienda.indexOf(KP_PENDING_TND) !== 0) {
                    textoRuta.textContent = rutaImagenTienda;
                    textoRuta.classList.remove('hidden');
                } else {
                    textoRuta.textContent = '';
                    textoRuta.classList.add('hidden');
                }
            }
            renderizarImagenTienda();
            if (btnAñadir && btnCambiar) {
                const tiene = !!rutaImagenTienda;
                btnAñadir.classList.toggle('hidden', tiene);
                btnCambiar.classList.toggle('hidden', !tiene);
            }
        }

        function renderizarImagenTienda() {
            if (!contenedor) return;
            contenedor.innerHTML = '';
            if (!rutaImagenTienda) return;

            const pendiente = rutaImagenTienda.indexOf(KP_PENDING_TND) === 0;
            const div = document.createElement('div');
            div.className = 'relative group';

            if (pendiente) {
                div.innerHTML = '<div class="w-[100px] h-[45px] flex flex-col items-center justify-center bg-gray-100 dark:bg-gray-700 rounded border border-gray-300 dark:border-gray-600"><span class="text-xs text-gray-600 dark:text-gray-300">Subiendo…</span></div>';
            } else {
                const src = urlVistaTnd(rutaImagenTienda);
                div.innerHTML = '<div class="relative inline-block">' +
                    '<img src="' + src + '" alt="Logo tienda" class="w-[100px] h-[45px] object-cover rounded border border-gray-300 dark:border-gray-600 block" ' +
                    'onerror="this.src=\'data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'144\' height=\'120\'%3E%3Crect width=\'144\' height=\'120\' fill=\'%23ddd\'/%3E%3C/svg%3E\'">' +
                    '<button type="button" class="btn-quitar-imagen-tnd absolute top-0 right-0 bg-red-600 hover:bg-red-700 text-white rounded-full w-7 h-7 flex items-center justify-center font-bold text-base opacity-0 group-hover:opacity-100 transition-opacity shadow-lg" style="transform: translate(25%, -25%); z-index: 30;" title="Quitar">×</button>' +
                    '</div>';
                div.querySelector('.btn-quitar-imagen-tnd')?.addEventListener('click', function(e) {
                    e.stopPropagation();
                    aplicarRutaImagenTienda('');
                });
            }
            contenedor.appendChild(div);
        }

        async function blobDesdeCanvas100x45(sourceCanvasOrImage) {
            const canvas = document.createElement('canvas');
            canvas.width = KP_W_TND;
            canvas.height = KP_H_TND;
            const ctx = canvas.getContext('2d');
            ctx.imageSmoothingEnabled = true;
            ctx.imageSmoothingQuality = 'high';
            const w = sourceCanvasOrImage.width || sourceCanvasOrImage.naturalWidth;
            const h = sourceCanvasOrImage.height || sourceCanvasOrImage.naturalHeight;
            ctx.drawImage(sourceCanvasOrImage, 0, 0, w, h, 0, 0, KP_W_TND, KP_H_TND);
            return new Promise(function(resolve, reject) {
                canvas.toBlob(function(b) { b ? resolve(b) : reject(new Error('Error al convertir imagen')); }, 'image/webp', 0.9);
            });
        }

        async function cargarImagenDesdeRuta(ruta) {
            const src = window.kpUrlVistaDesdeRutaAlmacenIg
                ? window.kpUrlVistaDesdeRutaAlmacenIg(ruta)
                : urlVistaTnd(ruta);
            return new Promise(function(resolve, reject) {
                const im = new Image();
                im.crossOrigin = 'anonymous';
                im.onload = function() { resolve(im); };
                im.onerror = function() { reject(new Error('No se pudo cargar la imagen de origen')); };
                im.src = src;
            });
        }

        async function subirBlobTienda(blob) {
            const nombreBase = obtenerNombreArchivoTienda() || ('tienda_' + Date.now());
            const formData = new FormData();
            formData.append('imagen', blob, nombreBase + '.webp');
            formData.append('carpeta', KP_CARPETA_TND);
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

        function normalizarRutaAlmacenTnd(ruta) {
            return (ruta || '').trim().replace(/^\/+/, '');
        }

        /** Interna global: reutiliza la ruta si ya es 100×45; solo redimensiona y sube a categorias si es más grande. */
        async function resolverRutaImagenInternaGlobalTienda(rutaOrigen) {
            const ruta = normalizarRutaAlmacenTnd(rutaOrigen);
            if (!ruta) throw new Error('Ruta de imagen no válida');
            const img = await cargarImagenDesdeRuta(ruta);
            const w = img.naturalWidth || img.width;
            const h = img.naturalHeight || img.height;
            if (w === KP_W_TND && h === KP_H_TND) {
                return ruta;
            }
            if (w > KP_W_TND || h > KP_H_TND) {
                const blob = await blobDesdeCanvas100x45(img);
                return subirBlobTienda(blob);
            }
            return ruta;
        }

        function kpPairKeyTnd(pair) {
            return (pair.rutaGrande || '') + '|' + (pair.rutaPequena || '');
        }

        window.cerrarModalAñadirImagenTienda = function() {
            if (modal) modal.classList.add('hidden');
            limpiarModalTnd();
        };

        function limpiarModalTnd() {
            const fileInput = document.getElementById('file-subir-tnd');
            if (fileInput) fileInput.value = '';
            const urlIn = document.getElementById('url-imagen-tnd');
            if (urlIn) urlIn.value = '';
            const urlAmz = document.getElementById('url-amazon-tnd');
            if (urlAmz) urlAmz.value = '';
            const nomArch = document.getElementById('nombre-archivo-tnd');
            if (nomArch) nomArch.textContent = '';
            const errUrl = document.getElementById('error-url-tnd');
            if (errUrl) errUrl.classList.add('hidden');
            const errAmz = document.getElementById('error-amazon-tnd');
            if (errAmz) errAmz.classList.add('hidden');
            const loadAmz = document.getElementById('loading-amazon-tnd');
            if (loadAmz) loadAmz.classList.add('hidden');
            const imgsAmz = document.getElementById('imagenes-amazon-tnd');
            if (imgsAmz) imgsAmz.classList.add('hidden');
            const gridAmz = document.getElementById('grid-imagenes-amazon-tnd');
            if (gridAmz) gridAmz.innerHTML = '';
            const areaRec = document.getElementById('area-recorte-tnd');
            if (areaRec) areaRec.classList.add('hidden');
            imagenAmazonSeleccionadaTnd = null;
            if (cropperTnd) { cropperTnd.destroy(); cropperTnd = null; }
        }

        function abrirModalTnd(tab) {
            if (!validarNombreParaImagen()) return;
            if (modal) modal.classList.remove('hidden');
            limpiarModalTnd();
            cambiarTabModalTnd(tab || 'url');
        }

        function cambiarTabModalTnd(tab) {
            const tabUrl = document.getElementById('tab-url-tnd');
            const tabSubir = document.getElementById('tab-subir-tnd');
            const tabAmazon = document.getElementById('tab-amazon-tnd');
            const tabIg = document.getElementById('tab-interna-global-tnd');
            const cUrl = document.getElementById('content-url-tnd');
            const cSubir = document.getElementById('content-subir-tnd');
            const cAmazon = document.getElementById('content-amazon-tnd');
            const cIg = document.getElementById('content-interna-global-tnd');
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
                    window.kpInternaGlobalAlActivar('tnd');
                }
            }
        }

        if (btnAñadir) btnAñadir.addEventListener('click', function() { abrirModalTnd('url'); });
        if (btnCambiar) btnCambiar.addEventListener('click', function() { abrirModalTnd('url'); });

        document.getElementById('tab-url-tnd')?.addEventListener('click', function() { cambiarTabModalTnd('url'); });
        document.getElementById('tab-subir-tnd')?.addEventListener('click', function() { cambiarTabModalTnd('subir'); });
        document.getElementById('tab-amazon-tnd')?.addEventListener('click', function() { cambiarTabModalTnd('amazon'); });
        document.getElementById('tab-interna-global-tnd')?.addEventListener('click', function() { cambiarTabModalTnd('interna-global'); });

        const nombreCatInput = document.getElementById('nombre-tienda');
        if (nombreCatInput) {
            nombreCatInput.addEventListener('input', function() {
                if (typeof window.kpInternaGlobalRefrescarPalabras === 'function') {
                    window.kpInternaGlobalRefrescarPalabras('tnd');
                }
            });
        }

        async function procesarArchivoSubir(file) {
            if (!validarNombreParaImagen()) return;
            if (!file.type.startsWith('image/')) {
                alert('Selecciona un archivo de imagen válido.');
                return;
            }
            if (file.size > 5 * 1024 * 1024) {
                alert('La imagen es demasiado grande. Máximo 5MB.');
                return;
            }
            const pending = KP_PENDING_TND + Date.now();
            aplicarRutaImagenTienda(pending);
            subidaPendienteTnd = true;
            cerrarModalAñadirImagenTienda();
            try {
                const urlRevoke = URL.createObjectURL(file);
                const img = await new Promise(function(resolve, reject) {
                    const im = new Image();
                    im.onload = function() { URL.revokeObjectURL(urlRevoke); resolve(im); };
                    im.onerror = function() { URL.revokeObjectURL(urlRevoke); reject(new Error('Imagen no válida')); };
                    im.src = urlRevoke;
                });
                const blob = await blobDesdeCanvas100x45(img);
                const ruta = await subirBlobTienda(blob);
                aplicarRutaImagenTienda(ruta);
            } catch (err) {
                console.error(err);
                alert('Error al subir la imagen: ' + (err.message || err));
                aplicarRutaImagenTienda('');
            } finally {
                subidaPendienteTnd = false;
            }
        }

        const fileSubir = document.getElementById('file-subir-tnd');
        const btnSel = document.getElementById('btn-seleccionar-tnd');
        const dropZone = document.getElementById('drop-zone-tnd');
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

        document.getElementById('btn-descargar-url-tnd')?.addEventListener('click', function() {
            const url = document.getElementById('url-imagen-tnd')?.value.trim();
            const err = document.getElementById('error-url-tnd');
            if (!url) {
                if (err) { err.textContent = 'Introduce una URL válida.'; err.classList.remove('hidden'); }
                return;
            }
            if (err) err.classList.add('hidden');
            carpetaActualUrlTnd = KP_CARPETA_TND;
            mostrarRecorteTnd(url);
        });

        function mostrarRecorteTnd(urlImagen) {
            const area = document.getElementById('area-recorte-tnd');
            const img = document.getElementById('imagen-recortar-tnd');
            if (!area || !img) return;
            area.classList.remove('hidden');
            if (cropperTnd) cropperTnd.destroy();
            const urlProxy = /^https?:\/\//i.test(urlImagen)
                ? KP_PROXY_URL + '?url=' + encodeURIComponent(urlImagen)
                : urlImagen;
            img.crossOrigin = 'anonymous';
            img.src = urlProxy;
            img.onload = function() {
                if (cropperTnd) cropperTnd.destroy();
                cropperTnd = new Cropper(img, {
                    aspectRatio: KP_W_TND / KP_H_TND,
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
                const err = document.getElementById('error-url-tnd');
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

        const urlAmazonInput = document.getElementById('url-amazon-tnd');
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

        document.getElementById('btn-buscar-amazon-tnd')?.addEventListener('click', async function() {
            const urlInput = document.getElementById('url-amazon-tnd');
            const errorDiv = document.getElementById('error-amazon-tnd');
            const loadingDiv = document.getElementById('loading-amazon-tnd');
            const imagenesDiv = document.getElementById('imagenes-amazon-tnd');
            const gridDiv = document.getElementById('grid-imagenes-amazon-tnd');
            const url = urlInput?.value.trim();
            if (!url) {
                if (errorDiv) { errorDiv.textContent = 'Introduce una URL de Amazon'; errorDiv.classList.remove('hidden'); }
                return;
            }
            if (errorDiv) errorDiv.classList.add('hidden');
            if (loadingDiv) loadingDiv.classList.remove('hidden');
            if (imagenesDiv) imagenesDiv.classList.add('hidden');
            if (gridDiv) gridDiv.innerHTML = '';
            imagenAmazonSeleccionadaTnd = null;
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
                    radio.name = 'amazon-tnd-sel';
                    radio.className = 'absolute top-2 right-2 w-5 h-5';
                    div.appendChild(img);
                    div.appendChild(radio);
                    div.addEventListener('click', function() {
                        gridDiv.querySelectorAll('input[type=radio]').forEach(function(r) { r.checked = false; });
                        radio.checked = true;
                        imagenAmazonSeleccionadaTnd = imagen;
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

        async function procesarImagenAmazonTnd() {
            if (!imagenAmazonSeleccionadaTnd) {
                alert('Selecciona una imagen de Amazon');
                return;
            }
            const pending = KP_PENDING_TND + 'amz';
            aplicarRutaImagenTienda(pending);
            cerrarModalAñadirImagenTienda();
            try {
                const urlProxy = imagenAmazonSeleccionadaTnd.url.startsWith('http')
                    ? KP_PROXY_URL + '?url=' + encodeURIComponent(imagenAmazonSeleccionadaTnd.url)
                    : imagenAmazonSeleccionadaTnd.url;
                const img = await new Promise(function(resolve, reject) {
                    const im = new Image();
                    im.crossOrigin = 'anonymous';
                    im.onload = function() { resolve(im); };
                    im.onerror = function() { reject(new Error('Error al cargar imagen Amazon')); };
                    im.src = urlProxy;
                });
                const blob = await blobDesdeCanvas100x45(img);
                const ruta = await subirBlobTienda(blob);
                aplicarRutaImagenTienda(ruta);
            } catch (err) {
                alert('Error: ' + (err.message || err));
                aplicarRutaImagenTienda('');
            }
        }

        async function procesarRecorteUrlTnd() {
            if (!cropperTnd) {
                alert('Descarga y recorta la imagen primero.');
                return;
            }
            const canvasOriginal = cropperTnd.getCroppedCanvas({ imageSmoothingEnabled: true, imageSmoothingQuality: 'high' });
            if (!canvasOriginal) {
                alert('Error al recortar');
                return;
            }
            const pending = KP_PENDING_TND + 'url';
            aplicarRutaImagenTienda(pending);
            cerrarModalAñadirImagenTienda();
            try {
                const blob = await blobDesdeCanvas100x45(canvasOriginal);
                const ruta = await subirBlobTienda(blob);
                aplicarRutaImagenTienda(ruta);
            } catch (err) {
                alert('Error: ' + (err.message || err));
                aplicarRutaImagenTienda('');
            }
        }

        async function procesarInternaGlobalTnd() {
            if (!seleccionInternaGlobalTnd) {
                alert('Selecciona una imagen del almacén');
                return;
            }
            const rutaOrigen = seleccionInternaGlobalTnd.rutaPequena || seleccionInternaGlobalTnd.rutaGrande || seleccionInternaGlobalTnd.thumbVisual;
            const pending = KP_PENDING_TND + 'ig';
            aplicarRutaImagenTienda(pending);
            cerrarModalAñadirImagenTienda();
            try {
                const ruta = await resolverRutaImagenInternaGlobalTienda(rutaOrigen);
                aplicarRutaImagenTienda(ruta);
                seleccionInternaGlobalTnd = null;
            } catch (err) {
                alert('Error al procesar la imagen: ' + (err.message || err));
                aplicarRutaImagenTienda('');
            }
        }

        document.getElementById('btn-guardar-tnd')?.addEventListener('click', async function() {
            const tabActiva = document.querySelector('.tab-modal-tnd.kp-modal-img-tab--active');
            const tabId = tabActiva ? tabActiva.id : 'tab-url-tnd';
            if (tabId === 'tab-subir-tnd') {
                if (!fileSubir?.files?.length) alert('Selecciona un archivo primero.');
                return;
            }
            if (tabId === 'tab-amazon-tnd') {
                await procesarImagenAmazonTnd();
                return;
            }
            if (tabId === 'tab-interna-global-tnd') {
                await procesarInternaGlobalTnd();
                return;
            }
            if (tabId === 'tab-url-tnd') {
                await procesarRecorteUrlTnd();
            }
        });

        if (typeof window.kpInternaGlobalRegistrar === 'function') {
            window.kpInternaGlobalRegistrar('tnd', {
                onSelect: function(pair) {
                    if (seleccionInternaGlobalTnd && kpPairKeyTnd(seleccionInternaGlobalTnd) === kpPairKeyTnd(pair)) {
                        seleccionInternaGlobalTnd = null;
                    } else {
                        seleccionInternaGlobalTnd = pair;
                    }
                    if (typeof window.kpInternaGlobalRefrescarSeleccion === 'function') {
                        window.kpInternaGlobalRefrescarSeleccion('tnd');
                    }
                },
                isSelected: function(pair) {
                    return seleccionInternaGlobalTnd && kpPairKeyTnd(seleccionInternaGlobalTnd) === kpPairKeyTnd(pair);
                },
                renderResumen: function() {
                    const el = document.querySelector('[data-kp-ig-panel="tnd"] .kp-ig-seleccion-resumen');
                    const pairs = seleccionInternaGlobalTnd ? [seleccionInternaGlobalTnd] : [];
                    window.kpIgPintarResumenSeleccion(el, pairs);
                },
                getPalabras: function() {
                    const nombre = document.getElementById('nombre-tienda')?.value || '';
                    return window.kpPartirNombreEnPalabras(nombre);
                },
            });
        }

        formTiendaImg.addEventListener('submit', function(e) {
            if (subidaPendienteTnd || (rutaImagenTienda && rutaImagenTienda.indexOf(KP_PENDING_TND) === 0)) {
                e.preventDefault();
                alert('Espera a que termine de subirse la imagen antes de guardar la tienda.');
            }
        });

        if (inputRuta && inputRuta.value) {
            aplicarRutaImagenTienda(inputRuta.value);
        } else {
            renderizarImagenTienda();
            if (btnAñadir && btnCambiar) {
                btnCambiar.classList.add('hidden');
            }
        }

    });
    </script>

</x-app-layout>