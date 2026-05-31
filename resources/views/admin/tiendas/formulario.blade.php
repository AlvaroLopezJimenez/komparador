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
                            value="{{ old('envio_normal', $tienda->envio_normal) }}"
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
                <legend class="text-lg font-semibold text-gray-700 dark:text-gray-200">URL de listado por categoría</legend>

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

                <p class="text-sm text-gray-500 dark:text-gray-400">Puedes añadir varias URLs de listado por categoría con los botones + y −.</p>

                <style>
                    .url-categoria-linea-grid {
                        display: grid;
                        width: 100%;
                        align-items: center;
                        column-gap: 0.5rem;
                        row-gap: 0.25rem;
                        grid-template-columns: 1.75rem 14rem minmax(0, 1fr) 13rem 1.75rem 1.75rem;
                    }
                </style>

                <div id="arbol-urls-categoria" class="space-y-3">
                    @php
                        $neoobjetivosPorCategoria = $neoobjetivosPorCategoria ?? collect();
                        $categoriasSinNeoobjetivo = $categoriasSinNeoobjetivo ?? collect();
                        $categoriasAncestrosSinNeo = $categoriasAncestrosSinNeo ?? collect();
                        $conteoTotalOfertas = $conteoTotalOfertas ?? [];
                        $renderCategorias = function($categorias, $neoobjetivosPorCategoria, $categoriasSinNeoobjetivo, $categoriasAncestrosSinNeo, $conteoTotalOfertas, $nivel = 0) use (&$renderCategorias) {
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
                                $numOfertas = $conteoTotalOfertas[$categoria->id] ?? 0;
                    @endphp

                    <div
                        class="js-categoria-row ml-{{ $margin }} {{ $sinNeo ? 'bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded p-2' : '' }}"
                        data-categoria-id="{{ $categoria->id }}"
                        data-sin-neo-inicial="{{ $sinNeo ? '1' : '0' }}"
                        x-data="{ open{{ $categoria->id }}: false }"
                    >
                        <div class="js-urls-categoria-lineas space-y-2 w-full" data-categoria-id="{{ $categoria->id }}" data-siguiente-indice="{{ $lineasUrl->count() }}">
                            @php foreach ($lineasUrl as $idx => $linea): @endphp
                            <div class="js-url-categoria-linea url-categoria-linea-grid">
                                @if($idx === 0)
                                    @if($hasChildren)
                                        <button type="button"
                                            @click="open{{ $categoria->id }} = !open{{ $categoria->id }}"
                                            class="w-7 h-7 justify-self-center flex items-center justify-center text-white bg-pink-600 hover:bg-pink-700 rounded-full transition shrink-0"
                                            :aria-label="open{{ $categoria->id }} ? 'Contraer' : 'Expandir'">
                                            <span x-text="open{{ $categoria->id }} ? '-' : '+'"></span>
                                        </button>
                                    @else
                                        <span class="w-7 h-7 block shrink-0" aria-hidden="true"></span>
                                    @endif
                                    <span class="js-categoria-nombre text-sm font-medium leading-tight break-words {{ $sinNeo ? 'text-red-700 dark:text-red-300' : ($esAncestro ? 'text-red-600 dark:text-red-400' : 'text-gray-800 dark:text-gray-200') }}">{{ $categoria->nombre }} <span class="text-gray-500 dark:text-gray-400 font-normal">({{ $numOfertas }})</span></span>
                                @else
                                    <span class="w-7 h-7 block shrink-0" aria-hidden="true"></span>
                                    <span class="block" aria-hidden="true"></span>
                                @endif
                                <div class="min-w-0">
                                    <input type="hidden" name="urls_categoria[{{ $categoria->id }}][{{ $idx }}][id]" value="{{ $linea['id'] }}">
                                    <input
                                        type="url"
                                        name="urls_categoria[{{ $categoria->id }}][{{ $idx }}][url]"
                                        data-id="{{ $categoria->id }}"
                                        placeholder="https://..."
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
                            @php endforeach; @endphp
                        </div>

                        @if ($hasChildren)
                            <div class="space-y-2 mt-2 ml-4" x-show="open{{ $categoria->id }}" x-cloak>
                                @php $renderCategorias($categoria->children, $neoobjetivosPorCategoria, $categoriasSinNeoobjetivo, $categoriasAncestrosSinNeo, $conteoTotalOfertas, $nivel + 1); @endphp
                            </div>
                        @endif
                    </div>

                    @php
                            }
                        };
                        $renderCategorias($categorias, $neoobjetivosPorCategoria, $categoriasSinNeoobjetivo, $categoriasAncestrosSinNeo, $conteoTotalOfertas);
                    @endphp
                </div>

                <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const arbol = document.getElementById('arbol-urls-categoria');
                    if (!arbol) return;

                    function siguienteIndice(contenedor) {
                        const actual = parseInt(contenedor.dataset.siguienteIndice || '0', 10);
                        contenedor.dataset.siguienteIndice = String(actual + 1);
                        return actual;
                    }

                    function crearLineaUrlCategoria(contenedor, categoriaId, idx, url = '', visitada = '', neoId = '') {
                        const div = document.createElement('div');
                        div.className = 'js-url-categoria-linea url-categoria-linea-grid';
                        const filaCategoria = contenedor.closest('.js-categoria-row');
                        const sinNeo = filaCategoria && filaCategoria.dataset.sinNeoInicial === '1';
                        const borderUrl = sinNeo ? 'border-red-500' : '';
                        const urlEsc = url.replace(/"/g, '&quot;');

                        div.innerHTML = `
                            <span class="w-7 h-7 block shrink-0" aria-hidden="true"></span>
                            <span class="block" aria-hidden="true"></span>
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

                        const urlInput = div.querySelector('.js-url-categoria');
                        if (urlInput) {
                            urlInput.addEventListener('input', function() {
                                document.dispatchEvent(new CustomEvent('url-categoria-cambiada'));
                            });
                            urlInput.addEventListener('change', function() {
                                document.dispatchEvent(new CustomEvent('url-categoria-cambiada'));
                            });
                        }

                        return div;
                    }

                    arbol.addEventListener('click', function(e) {
                        const btnAñadir = e.target.closest('.btn-añadir-url-categoria');
                        const btnEliminar = e.target.closest('.btn-eliminar-url-categoria');
                        if (!btnAñadir && !btnEliminar) return;

                        const linea = e.target.closest('.js-url-categoria-linea');
                        const contenedor = e.target.closest('.js-urls-categoria-lineas');
                        if (!linea || !contenedor) return;

                        const categoriaId = contenedor.dataset.categoriaId;

                        if (btnAñadir) {
                            const idx = siguienteIndice(contenedor);
                            const nuevaLinea = crearLineaUrlCategoria(contenedor, categoriaId, idx);
                            linea.insertAdjacentElement('afterend', nuevaLinea);
                            nuevaLinea.querySelector('.js-url-categoria')?.focus();
                            return;
                        }

                        const lineas = contenedor.querySelectorAll('.js-url-categoria-linea');
                        if (lineas.length <= 1) {
                            linea.querySelector('.js-url-categoria').value = '';
                            linea.querySelector('input[type="hidden"]')?.setAttribute('value', '');
                            const visitada = linea.querySelector('.js-visitada-categoria');
                            if (visitada) visitada.value = '';
                            document.dispatchEvent(new CustomEvent('url-categoria-cambiada'));
                            return;
                        }

                        linea.remove();
                        document.dispatchEvent(new CustomEvent('url-categoria-cambiada'));
                    });
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
                        if (!hayRojas || checkbox.checked) {
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

        document.addEventListener('DOMContentLoaded', function() {
            const helpBtn = document.getElementById('api-scraping-help-btn');
            const helpTooltip = document.getElementById('api-scraping-help-tooltip');

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
        });
    </script>






</x-app-layout>