@php
    // Función helper para formatear unidades según la unidad de medida
    // Nota: Esta función también se define en comprobar-chollos.blade.php
    // Se usa function_exists() para evitar redeclaraciones
    if (!function_exists('formatearUnidades')) {
        function formatearUnidades($unidades, $unidadDeMedida) {
            // Normalizar la unidad de medida (lowercase y sin espacios)
            $unidadNormalizada = strtolower(trim($unidadDeMedida ?? ''));
            
            if ($unidadNormalizada === 'unidades') {
                // Para unidades, mostrar solo el número entero sin decimales
                return (string) intval($unidades);
            } elseif (in_array($unidadNormalizada, ['kilos', 'litros'])) {
                // Para kilos y litros, mostrar con decimales necesarios (eliminar ceros finales)
                $formateado = number_format(floatval($unidades), 3, ',', '.');
                // Eliminar ceros finales y la coma si no hay decimales
                $formateado = rtrim($formateado, '0');
                $formateado = rtrim($formateado, ',');
                return $formateado;
            }
            // Por defecto, mostrar con 2 decimales
            return number_format(floatval($unidades), 2, ',', '.');
        }
    }

    if (!function_exists('obtenerTipoAviso')) {
        function obtenerTipoAviso($aviso) {
        $tipo = $aviso->avisoable_type ?? '';
        switch ($tipo) {
            case 'App\Models\Producto':
                return 'productos';
            case 'App\Models\OfertaProducto':
                return 'ofertas';
            case 'App\Models\Chollo':
                return 'chollos';
            default:
                return 'internos';
        }
        }
    }

    if (!function_exists('contarAvisosPorTipo')) {
        function contarAvisosPorTipo($avisos) {
        $conteo = [
            'productos' => 0,
            'ofertas' => 0,
            'chollos' => 0,
            'internos' => 0,
        ];

        foreach ($avisos as $aviso) {
            $tipo = obtenerTipoAviso($aviso);
            if (!isset($conteo[$tipo])) {
                $conteo[$tipo] = 0;
            }
            $conteo[$tipo]++;
        }

        $conteo['todos'] = method_exists($avisos, 'count') ? $avisos->count() : (is_countable($avisos) ? count($avisos) : 0);

        return $conteo;
        }
    }

    /**
     * Obtiene la primera oferta de un producto consultando la tabla producto_oferta_mas_barata_por_producto
     * Consulta el oferta_id desde la tabla y luego obtiene todos los datos de la oferta desde la tabla de ofertas
     */
    if (!function_exists('obtenerPrimeraOfertaProducto')) {
        function obtenerPrimeraOfertaProducto($productoId) {
        // Consultar la tabla producto_oferta_mas_barata_por_producto para obtener el oferta_id
        $ofertaMasBarataTabla = \App\Models\ProductoOfertaMasBarataPorProducto::where('producto_id', $productoId)->first();
        
        if (!$ofertaMasBarataTabla || !$ofertaMasBarataTabla->oferta_id) {
            return null;
        }
        
        // Obtener todos los datos de la oferta desde la tabla de ofertas
        $oferta = \App\Models\OfertaProducto::with('tienda')->find($ofertaMasBarataTabla->oferta_id);
        
        return $oferta;
        }
    }
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.dashboard') }}">
                <h2 class="font-semibold text-xl text-white leading-tight">
                    Inicio ->
                </h2>
            </a>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Avisos
            </h2>
        </div>
    </x-slot>

    <div class="py-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Banner de advertencia para productos con precio NULL -->
        @if(($avisosProductoPrecioNullCount ?? 0) > 0)
            <div class="mb-6 bg-red-900 border-l-4 border-red-500 text-red-100 p-4 rounded">
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                    <div>
                        <p class="font-bold">Advertencia: Hay {{ $avisosProductoPrecioNullCount }} aviso(s) de Producto con precio NULL</p>
                        <p class="text-sm mt-1">Estos productos no tienen ofertas activas y su precio no se ha actualizado. Revisa estos productos para evitar errores en la vista.</p>
                        @if($avisosProductoPrecioNull && $avisosProductoPrecioNull->count() > 0)
                            <details class="mt-2">
                                <summary class="cursor-pointer text-sm underline">Ver ejemplos ({{ $avisosProductoPrecioNull->count() }})</summary>
                                <ul class="mt-2 ml-4 list-disc text-sm">
                                    @foreach($avisosProductoPrecioNull->take(10) as $avisoNull)
                                        <li>
                                            Aviso #{{ $avisoNull->aviso_id }} - 
                                            Producto #{{ $avisoNull->producto_id }} 
                                            @if($avisoNull->producto_nombre)
                                                ({{ $avisoNull->producto_nombre }})
                                            @endif
                                            - 
                                            <a href="{{ route('admin.productos.edit', $avisoNull->producto_id) }}" target="_blank" class="underline hover:text-red-200">
                                                Editar Producto
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </details>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        <!-- Botones de acción -->
        <div class="mb-6 flex justify-between items-center">
            <div class="flex items-center space-x-3">
                <span id="avisos-seleccionados" class="text-sm text-gray-300">0 avisos seleccionados</span>
                <button id="btn-eliminar-seleccionados" onclick="eliminarAvisosSeleccionados()" 
                    class="inline-flex items-center px-3 py-1 text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700 disabled:bg-gray-500 disabled:cursor-not-allowed"
                    disabled>
                    Eliminar Seleccionados
                </button>
                @if(auth()->id() === 1)
                    <div class="flex items-center space-x-2">
                        <label class="flex items-center space-x-2 cursor-pointer">
                            <input type="checkbox" 
                                   id="mostrar-todos-avisos" 
                                   class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                   {{ session('avisos_mostrar_todos', false) ? 'checked' : '' }}
                                   onchange="toggleMostrarTodosAvisos(this.checked)">
                            <span class="text-sm text-gray-300">Mostrar todos</span>
                        </label>
                        <button type="button" 
                                class="tooltip-btn-avisos text-gray-400 hover:text-gray-300 cursor-help focus:outline-none ml-1" 
                                aria-label="Ayuda" 
                                data-tooltip="Si marcamos este check se mostrarán los avisos de todos los usuarios"
                                onclick="event.stopPropagation()">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm0 8a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/>
                            </svg>
                        </button>
                    </div>
                @endif
            </div>
            <div class="flex items-center space-x-3">
                <form method="GET" class="flex flex-col items-start gap-3 md:flex-row md:items-center">
                    <select name="perPage" class="border px-3 py-2 rounded text-sm bg-gray-700 text-gray-200 border-gray-600">
                        @foreach ([10, 20, 50, 100] as $option)
                            <option value="{{ $option }}" {{ (int) $perPage === $option ? 'selected' : '' }}>
                                {{ $option }} / página
                            </option>
                        @endforeach
                    </select>

                    <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 text-sm">
                        Filtrar
                    </button>
                </form>
                <button onclick="mostrarModalNuevoAvisoInterno()" 
                    class="inline-flex items-center px-6 py-4 border border-transparent text-sm font-medium rounded-md text-white bg-pink-600 hover:bg-pink-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-pink-500">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    Crear Aviso Interno
                </button>
            </div>
        </div>

        <!-- Pestañas -->
        <div class="mb-6">
            <div class="border-b border-gray-200 dark:border-gray-700">
                <nav class="-mb-px flex space-x-8">
                    <button id="tab-vencidos" class="tab-button active py-2 px-1 border-b-2 border-pink-500 font-medium text-sm text-pink-600 dark:text-pink-400">
                        Avisos Vencidos
                        @if($totalVencidos > 0)
                            <span class="ml-2 bg-red-100 text-red-800 text-xs font-medium px-2.5 py-0.5 rounded-full dark:bg-red-900 dark:text-red-300">
                                {{ $totalVencidos }}
                            </span>
                        @endif
                    </button>
                    <button id="tab-pendientes" class="tab-button py-2 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300">
                        Avisos Pendientes
                        @if($totalPendientes > 0)
                            <span class="ml-2 bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded-full dark:bg-blue-900 dark:text-blue-300">
                                {{ $totalPendientes }}
                            </span>
                        @endif
                    </button>
                    <button id="tab-ocultos" class="tab-button py-2 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300">
                        Avisos Ocultos
                        @if($totalOcultos > 0)
                            <span class="ml-2 bg-gray-100 text-gray-800 text-xs font-medium px-2.5 py-0.5 rounded-full dark:bg-gray-900 dark:text-gray-300">
                                {{ $totalOcultos }}
                            </span>
                        @endif
                    </button>
                </nav>
            </div>
        </div>

        <!-- Contenido de pestañas -->
        <div id="content-vencidos" class="tab-content">
            @php
                $conteoTiposVencidos = contarAvisosPorTipo($avisosVencidos);
                $subTabs = [
                    'todos' => 'Todos',
                    'productos' => 'Productos',
                    'ofertas' => 'Ofertas',
                    'chollos' => 'Chollos',
                    'internos' => 'Internos',
                ];
            @endphp
            <div class="mb-4">
                <div class="flex flex-wrap gap-2">
                    @foreach($subTabs as $clave => $label)
                        @php
                            $count = $conteoTiposVencidos[$clave] ?? 0;
                        @endphp
                        <button
                            type="button"
                            class="subtab-button inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-md border border-gray-600 text-gray-300 hover:border-pink-500 hover:text-pink-500 transition-colors"
                            data-tabla="vencidos"
                            data-subtab="{{ $clave }}"
                        >
                            <span>{{ $label }}</span>
                            @if($clave !== 'todos')
                                <span class="ml-1 text-[11px] text-gray-400">({{ $count }})</span>
                            @endif
                        </button>
                    @endforeach
                </div>
            </div>
            <div class="bg-gray-800 dark:bg-gray-800 shadow rounded-lg overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-700 dark:divide-gray-600">
                    <thead class="bg-gray-700 dark:bg-gray-700">
                        <tr>
                            <th class="py-2 text-left text-xs font-medium text-gray-300 dark:text-gray-300 uppercase tracking-wider" style="width: 20px;"></th>
                            <th class="px-2 py-2 text-left text-xs font-medium text-gray-300 dark:text-gray-300 uppercase tracking-wider" style="width: 20px;">
                                <input type="checkbox" id="select-all-vencidos" class="checkbox-eliminar rounded border-gray-300 text-red-600 focus:ring-red-500" onchange="toggleAllCheckboxes('vencidos')">
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-300 dark:text-gray-300 uppercase tracking-wider w-1/4">Elemento</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-300 dark:text-gray-300 uppercase tracking-wider w-2/5">Texto del Aviso</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-300 dark:text-gray-300 uppercase tracking-wider w-1/12">Fecha Vencimiento</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-300 dark:text-gray-300 uppercase tracking-wider w-1/12">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-700 dark:divide-gray-600">
                        @forelse ($avisosVencidos as $aviso)
                        @php
                            $tipoAviso = obtenerTipoAviso($aviso);
                        @endphp
                        <tr class="hover:bg-gray-700 dark:hover:bg-gray-700 transition-colors cursor-pointer subtab-row" data-tipo="{{ $tipoAviso }}" onclick="editarAviso({{ $aviso->id }}, {{ json_encode($aviso->texto_aviso) }}, '{{ $aviso->fecha_aviso->format('Y-m-d\TH:i') }}', {{ $aviso->oculto ? 'true' : 'false' }})">
                            <td class="p-0" onclick="event.stopPropagation()" style="width: 20px;">
                                @if($aviso->avisoable_type === 'App\Models\Producto')
                                    <div class="flex items-center justify-center bg-blue-600" style="writing-mode: vertical-lr; transform: rotate(180deg); min-height: 40px;">
                                        <span class="text-white text-sm font-bold py-8">Producto</span>
                                    </div>
                                @elseif($aviso->avisoable_type === 'App\Models\OfertaProducto')
                                    <div class="flex items-center justify-center bg-purple-600" style="writing-mode: vertical-lr; transform: rotate(180deg); min-height: 40px;">
                                        <span class="text-white text-sm font-bold py-8">Oferta</span>
                                    </div>
                                @elseif($aviso->avisoable_type === 'App\Models\Chollo')
                                    <div class="flex items-center justify-center bg-pink-600" style="writing-mode: vertical-lr; transform: rotate(180deg); min-height: 40px;">
                                        <span class="text-white text-sm font-bold py-8">Chollo</span>
                                    </div>
                                @elseif($aviso->avisoable_type === 'App\Models\Chollo')
                                    <div class="flex items-center justify-center bg-pink-600" style="writing-mode: vertical-lr; transform: rotate(180deg); min-height: 40px;">
                                        <span class="text-white text-sm font-bold py-8">Chollo</span>
                                    </div>
                                @else
                                    <div class="flex items-center justify-center bg-gray-600" style="writing-mode: vertical-lr; transform: rotate(180deg); min-height: 40px;">
                                        <span class="text-gray-200 text-sm font-bold py-8">Interno</span>
                                    </div>
                                @endif
                            </td>
                            <td class="px-6 py-1 cursor-pointer hover:bg-gray-600 transition-colors" onclick="event.stopPropagation(); toggleCheckbox({{ $aviso->id }}, 'vencidos')">
                                <div class="flex items-center justify-center h-8">
                                    <input type="checkbox" class="checkbox-aviso rounded border-gray-300 text-red-600 focus:ring-red-500 pointer-events-none" 
                                           data-aviso-id="{{ $aviso->id }}" 
                                           data-tabla="vencidos"
                                           onchange="actualizarContadorSeleccionados()">
                                </div>
                            </td>
                            <td class="px-6 py-1">
                                @if($aviso->avisoable_type === 'App\Models\Producto' && $aviso->avisoable_id && $aviso->avisoable_id !== null)
                                    <div class="flex flex-col space-y-1">
                                        <span class="text-gray-200 dark:text-gray-200 break-words text-sm">{{ $aviso->elemento_nombre }}</span>
                                        <div class="flex items-center space-x-1">
                                            <button onclick="event.stopPropagation(); window.open('{{ route('admin.ofertas.index', $aviso->avisoable_id) }}', '_blank')" 
                                                class="px-2 py-1 text-xs bg-gray-600 hover:bg-gray-700 text-white rounded transition-colors">
                                                Ofertas
                                            </button>
                                            @if($aviso->avisoable && $aviso->avisoable->categoria)
                                                <button onclick="event.stopPropagation(); window.open('/{{ implode('/', $aviso->avisoable->categoria->obtenerSlugsJerarquia()) }}/{{ $aviso->avisoable->slug }}', '_blank')" 
                                                    class="px-2 py-1 text-xs bg-green-600 hover:bg-green-700 text-white rounded transition-colors">
                                                    Ver
                                                </button>
                                            @endif
                                            @php
                                                $ofertaMasBarata = obtenerPrimeraOfertaProducto($aviso->avisoable_id);
                                            @endphp
                                            @if($ofertaMasBarata)
                                                <button onclick="event.stopPropagation(); window.open('{{ $ofertaMasBarata->url }}', '_blank')" 
                                                    class="px-2 py-1 text-xs bg-orange-600 hover:bg-orange-700 text-white rounded transition-colors">
                                                    1ª Oferta
                                                </button>
                                                <button onclick="event.stopPropagation(); window.open('{{ route('admin.ofertas.edit', $ofertaMasBarata->id) }}', '_blank')" 
                                                    class="px-2 py-1 text-xs bg-blue-600 hover:bg-blue-700 text-white rounded transition-colors">
                                                    Editar
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                @elseif($aviso->avisoable_type === 'App\Models\OfertaProducto' && $aviso->avisoable_id && $aviso->avisoable_id !== null)
                                    <div class="flex flex-col space-y-1">
                                        <span class="text-gray-200 dark:text-gray-200 break-words text-sm">{{ $aviso->elemento_nombre }}</span>
                                        <div class="flex items-center space-x-1">
                                            
                                            <button onclick="event.stopPropagation(); window.open('{{ route('admin.ofertas.edit', $aviso->avisoable_id) }}', '_blank')" 
                                                class="px-3 py-1.5 text-sm bg-blue-600 hover:bg-blue-700 text-white rounded transition-colors">
                                                Editar
                                            </button>
                                            <button onclick="event.stopPropagation(); mostrarOferta({{ $aviso->avisoable_id }}, {{ $aviso->id }})" 
                                                class="px-3 py-1.5 text-sm bg-purple-600 hover:bg-purple-700 text-white rounded transition-colors">
                                                Mostrar->si
                                            </button>
                                            @if($aviso->avisoable && $aviso->avisoable->url)
                                                <button onclick="event.stopPropagation(); window.open('{{ $aviso->avisoable->url }}', '_blank')" 
                                                    class="px-3 py-1.5 text-sm bg-green-600 hover:bg-green-700 text-white rounded transition-colors">
                                                    Ver
                                                </button>
                                            @endif
                                            
                                        </div>
                                    </div>
                                @elseif($aviso->avisoable_type === 'App\Models\Chollo' && $aviso->avisoable)
                                    <div class="flex flex-col space-y-1">
                                        <span class="text-gray-200 dark:text-gray-200 break-words text-sm">{{ $aviso->elemento_nombre }}</span>
                                        <div class="flex items-center space-x-1">
                                            <button onclick="event.stopPropagation(); window.open('{{ route('admin.chollos.edit', $aviso->avisoable_id) }}', '_blank')" 
                                                class="px-3 py-1.5 text-sm bg-pink-600 hover:bg-pink-700 text-white rounded transition-colors">
                                                Editar
                                            </button>
                                            @if($aviso->avisoable->url)
                                                <button onclick="event.stopPropagation(); window.open('{{ $aviso->avisoable->url }}', '_blank')" 
                                                    class="px-3 py-1.5 text-sm bg-green-600 hover:bg-green-700 text-white rounded transition-colors">
                                                    Ver
                                                </button>
                                            @endif
                                            @if($aviso->avisoable->producto)
                                                <button onclick="event.stopPropagation(); window.open('{{ route('admin.productos.edit', $aviso->avisoable->producto->id) }}', '_blank')" 
                                                    class="px-3 py-1.5 text-sm bg-blue-600 hover:bg-blue-700 text-white rounded transition-colors">
                                                    Producto
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                @else
                                    <span class="text-gray-200 dark:text-gray-200">
                                        @if($aviso->avisoable_type === 'Interno' && $aviso->avisoable_id === 0)
                                            Aviso Interno
                                        @else
                                            {{ $aviso->elemento_nombre }}
                                        @endif
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-1 max-w-md">
                                <div class="text-base text-gray-200 dark:text-gray-200 whitespace-pre-line break-words max-w-full" style="overflow-wrap: anywhere; word-break: break-word;">
                                    {{ $aviso->texto_aviso }}
                                    @if($aviso->avisoable_type === 'App\Models\Producto' && $aviso->avisoable_id && $aviso->avisoable_id !== null)
                                        @php
                                            $ofertaMasBarata = obtenerPrimeraOfertaProducto($aviso->avisoable_id);
                                        @endphp
                                        @if($ofertaMasBarata)
                                            @php
                                                $productoOferta = \App\Models\Producto::find($aviso->avisoable_id);
                                            @endphp
                                            <div class="mt-0 p-1 bg-gray-700 rounded text-xs text-gray-300">
                                                <div class="flex flex-wrap items-center gap-1 text-xs">
                                                    <span class="font-medium">{{ $ofertaMasBarata->tienda->nombre ?? 'Tienda ID: ' . $ofertaMasBarata->tienda_id }}</span>
                                                    <span>•</span>
                                                    <span>{{ formatearUnidades($ofertaMasBarata->unidades, $productoOferta->unidadDeMedida ?? 'unidades') }} uds</span>
                                                    <span>•</span>
                                                    <input type="number" 
                                                           step="0.01" 
                                                           min="0" 
                                                           value="{{ number_format($ofertaMasBarata->precio_total, 2, '.', '') }}" 
                                                           data-oferta-id="{{ $ofertaMasBarata->id }}"
                                                           data-producto-id="{{ $productoOferta->id ?? '' }}"
                                                           data-unidades="{{ $ofertaMasBarata->unidades }}"
                                                           data-unidad-medida="{{ $productoOferta->unidadDeMedida ?? 'unidad' }}"
                                                           data-precio-original="{{ number_format($ofertaMasBarata->precio_total, 2, '.', '') }}"
                                                           class="precio-total-oferta-mas-barata w-20 px-1 py-0.5 bg-gray-600 text-gray-200 border border-gray-500 rounded text-xs focus:outline-none focus:ring-1 focus:ring-blue-500"
                                                           onclick="event.stopPropagation(); this.select(); this.value = '';"
                                                           onfocus="this.select(); this.value = '';">
                                                    <span>€</span>
                                                    <button onclick="event.stopPropagation(); guardarPrecioOfertaMasBarata({{ $ofertaMasBarata->id }})" 
                                                        class="px-2 py-1 text-xs bg-green-600 hover:bg-green-700 text-white rounded transition-colors">
                                                        Guardar
                                                    </button>
                                                    <span>•</span>
                                                    <span class="text-green-400 font-medium precio-unidad-oferta-mas-barata" data-oferta-id="{{ $ofertaMasBarata->id }}">{{ ($productoOferta->unidadDeMedida ?? 'unidades') === 'unidadMilesima' ? number_format($ofertaMasBarata->precio_unidad, 3) : number_format($ofertaMasBarata->precio_unidad, 2) }}€/ud</span>
                                                    @if($ofertaMasBarata->chollo_id !== null)
                                                        <span>•</span>
                                                        <span class="text-orange-500 font-medium">Chollo</span>
                                                    @endif
                                                </div>
                                            </div>
                                        @endif
                                    @elseif($aviso->avisoable_type === 'App\Models\OfertaProducto' && $aviso->avisoable_id && $aviso->avisoable_id !== null && $aviso->avisoable)
                                        <div class="mt-0 p-1 bg-gray-700 rounded text-xs text-gray-300">
                                            <div class="flex flex-wrap items-center gap-1 text-xs">
                                                <span class="font-medium">{{ $aviso->avisoable->tienda->nombre ?? 'Tienda ID: ' . $aviso->avisoable->tienda_id }}</span>
                                                <span>•</span>
                                                <span>{{ formatearUnidades($aviso->avisoable->unidades, $aviso->avisoable->producto->unidadDeMedida ?? 'unidades') }} uds</span>
                                                <span>•</span>
                                                <input type="number" 
                                                       step="0.01" 
                                                       min="0" 
                                                       value="{{ number_format($aviso->avisoable->precio_total, 2, '.', '') }}" 
                                                       data-oferta-id="{{ $aviso->avisoable_id }}"
                                                       data-producto-id="{{ $aviso->avisoable->producto_id ?? '' }}"
                                                       data-unidades="{{ $aviso->avisoable->unidades }}"
                                                       data-precio-original="{{ number_format($aviso->avisoable->precio_total, 2, '.', '') }}"
                                                       class="precio-total-input w-20 px-1 py-0.5 bg-gray-600 text-gray-200 border border-gray-500 rounded text-xs focus:outline-none focus:ring-1 focus:ring-blue-500"
                                                       onclick="event.stopPropagation(); this.select();"
                                                       onfocus="this.select(); this.value = '';"
                                                       onblur="if(this.value === '' || this.value === null) { this.value = this.dataset.precioOriginal; calcularPrecioUnidad(this); } else { calcularPrecioUnidad(this); }"
                                                       onchange="calcularPrecioUnidad(this)">
                                                <span>€</span>
                                                <span>•</span>
                                                <span class="text-green-400 font-medium precio-unidad-display" data-oferta-id="{{ $aviso->avisoable_id }}">{{ number_format($aviso->avisoable->precio_unidad, 2) }}€/ud</span>
                                            </div>
                                        </div>
                                    @elseif($aviso->avisoable_type === 'App\Models\Chollo' && $aviso->avisoable)
                                        <div class="mt-0 p-1 bg-gray-700 rounded text-xs text-gray-300">
                                            <div class="flex flex-wrap items-center gap-1 text-xs">
                                                @if($aviso->avisoable->tienda)
                                                    <span class="font-medium">{{ $aviso->avisoable->tienda->nombre }}</span>
                                                    <span>•</span>
                                                @endif
                                                @if($aviso->avisoable->precio_nuevo)
                                                    <span class="text-green-400 font-medium">{{ $aviso->avisoable->precio_nuevo }}</span>
                                                    <span>•</span>
                                                @endif
                                                @if($aviso->avisoable->precio_unidad)
                                                    <span>{{ number_format($aviso->avisoable->precio_unidad, 4) }} €/ud</span>
                                                    <span>•</span>
                                                @endif
                                                <span>Mostrar: {{ strtoupper($aviso->avisoable->mostrar) }}</span>
                                                <span>•</span>
                                                <span>Finalizada: {{ strtoupper($aviso->avisoable->finalizada) }}</span>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-1 text-sm text-gray-200 dark:text-gray-200">
                                {{ $aviso->fecha_aviso->format('d/m/Y H:i') }}
                            </td>
                            <td class="px-6 py-1" onclick="event.stopPropagation()">
                                <div class="flex items-center space-x-2">
                                    <button onclick="editarAviso({{ $aviso->id }}, {{ json_encode($aviso->texto_aviso) }}, '{{ $aviso->fecha_aviso->format('Y-m-d\TH:i') }}', {{ $aviso->oculto ? 'true' : 'false' }})" 
                                        class="px-4 py-2 text-sm bg-blue-600 hover:bg-blue-700 text-white rounded-md transition-colors">
                                        Editar
                                    </button>
                                    @if($aviso->avisoable_type === 'App\Models\Producto' && strpos($aviso->texto_aviso, 'Precio actualizado producto') !== false)
                                        @php
                                            $precioActual = $aviso->avisoable?->precio;
                                            $alertasCount = 0;
                                            
                                            // Solo contar alertas si el precio no es NULL
                                            if ($aviso->avisoable_id && $precioActual !== null) {
                                                $alertasCount = \App\Models\CorreoAvisoPrecio::where('producto_id', $aviso->avisoable_id)
                                                    ->where('precio_limite', '>=', $precioActual)
                                                    ->where(function($query) {
                                                        $query->whereNull('ultimo_envio_correo')
                                                              ->orWhere('ultimo_envio_correo', '<', now()->subWeek());
                                                    })
                                                    ->count();
                                            }
                                        @endphp
                                        @if($precioActual === null)
                                            <div class="px-3 py-1 text-xs bg-red-600 text-white rounded">
                                                ERROR: Producto #{{ $aviso->avisoable_id }} con precio NULL
                                            </div>
                                        @elseif($alertasCount > 0)
                                            <button onclick="enviarAlertasProducto({{ $aviso->avisoable_id }}, {{ $precioActual }})" 
                                                class="px-3 py-1 text-sm text-white rounded-md transition-all duration-300 bg-gradient-to-r from-green-500 to-blue-500 hover:from-green-600 hover:to-blue-600 animate-pulse shadow-lg font-bold">
                                                📧 Correos ({{ $alertasCount }})
                                            </button>
                                        @endif
                                    @endif
                                    @if($aviso->avisoable_type === 'App\Models\OfertaProducto' && preg_match('/\d+\s*(?:a\s*)?vez/i', $aviso->texto_aviso))
                                        <button onclick="aplazarAviso({{ $aviso->id }})" 
                                            class="px-3 py-1 text-sm bg-yellow-600 hover:bg-yellow-700 text-white rounded-md transition-colors">
                                            Aplazar
                                        </button>
                                    @endif
                                    @if(!($aviso->avisoable_type === 'App\Models\OfertaProducto' && preg_match('/\d+\s*(?:a\s*)?vez/i', $aviso->texto_aviso)))
                                        <button onclick="eliminarAviso({{ $aviso->id }})" 
                                            class="text-red-400 hover:text-red-300 dark:text-red-400 dark:hover:text-red-300 p-0.5">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-6 py-4 text-center text-gray-400 dark:text-gray-400">
                                No hay avisos vencidos.
                            </td>
                        </tr>
                        @endforelse
                        <tr class="mensaje-sin-avisos-filtrado hidden">
                            <td colspan="6" class="px-6 py-4 text-center text-gray-400 dark:text-gray-400">
                                No hay avisos en esta categoría.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <!-- Paginación para avisos vencidos -->
            <div class="mt-4 px-4">
                {{ $avisosVencidos->links() }}
            </div>
        </div>

        <div id="content-pendientes" class="tab-content hidden">
            @php
                $conteoTiposPendientes = contarAvisosPorTipo($avisosPendientes);
            @endphp
            <div class="mb-4">
                <div class="flex flex-wrap gap-2">
                    @foreach($subTabs as $clave => $label)
                        @php
                            $count = $conteoTiposPendientes[$clave] ?? 0;
                        @endphp
                        <button
                            type="button"
                            class="subtab-button inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-md border border-gray-600 text-gray-300 hover:border-pink-500 hover:text-pink-500 transition-colors"
                            data-tabla="pendientes"
                            data-subtab="{{ $clave }}"
                        >
                            <span>{{ $label }}</span>
                            @if($clave !== 'todos')
                                <span class="ml-1 text-[11px] text-gray-400">({{ $count }})</span>
                            @endif
                        </button>
                    @endforeach
                </div>
            </div>
            <div class="bg-gray-800 dark:bg-gray-800 shadow rounded-lg overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-700 dark:divide-gray-600">
                    <thead class="bg-gray-700 dark:bg-gray-700">
                        <tr>
                            <th class="py-2 text-left text-xs font-medium text-gray-300 dark:text-gray-300 uppercase tracking-wider" style="width: 20px;"></th>
                            <th class="px-2 py-2 text-left text-xs font-medium text-gray-300 dark:text-gray-300 uppercase tracking-wider" style="width: 20px;">
                                <input type="checkbox" id="select-all-pendientes" class="checkbox-eliminar rounded border-gray-300 text-red-600 focus:ring-red-500" onchange="toggleAllCheckboxes('pendientes')">
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-300 dark:text-gray-300 uppercase tracking-wider w-1/4">Elemento</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-300 dark:text-gray-300 uppercase tracking-wider w-2/5">Texto del Aviso</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-300 dark:text-gray-300 uppercase tracking-wider w-1/12">Fecha Programada</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-300 dark:text-gray-300 uppercase tracking-wider w-1/12">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-700 dark:divide-gray-600">
                        @forelse ($avisosPendientes as $aviso)
                        @php
                            $tipoAviso = obtenerTipoAviso($aviso);
                        @endphp
                        <tr class="hover:bg-gray-700 dark:hover:bg-gray-700 transition-colors cursor-pointer subtab-row" data-tipo="{{ $tipoAviso }}" onclick="editarAviso({{ $aviso->id }}, {{ json_encode($aviso->texto_aviso) }}, '{{ $aviso->fecha_aviso->format('Y-m-d\TH:i') }}', {{ $aviso->oculto ? 'true' : 'false' }})">
                            <td class="p-0" onclick="event.stopPropagation()" style="width: 20px;">
                                @if($aviso->avisoable_type === 'App\Models\Producto')
                                    <div class="flex items-center justify-center bg-blue-600" style="writing-mode: vertical-lr; transform: rotate(180deg); min-height: 40px;">
                                        <span class="text-white text-sm font-bold py-8">Producto</span>
                                    </div>
                                @elseif($aviso->avisoable_type === 'App\Models\OfertaProducto')
                                    <div class="flex items-center justify-center bg-purple-600" style="writing-mode: vertical-lr; transform: rotate(180deg); min-height: 40px;">
                                        <span class="text-white text-sm font-bold py-8">Oferta</span>
                                    </div>
                                @else
                                    <div class="flex items-center justify-center bg-gray-600" style="writing-mode: vertical-lr; transform: rotate(180deg); min-height: 40px;">
                                        <span class="text-gray-200 text-sm font-bold py-8">Interno</span>
                                    </div>
                                @endif
                            </td>
                            <td class="px-6 py-1 cursor-pointer hover:bg-gray-600 transition-colors" onclick="event.stopPropagation(); toggleCheckbox({{ $aviso->id }}, 'pendientes')">
                                <div class="flex items-center justify-center h-8">
                                    <input type="checkbox" class="checkbox-aviso rounded border-gray-300 text-red-600 focus:ring-red-500 pointer-events-none" 
                                           data-aviso-id="{{ $aviso->id }}" 
                                           data-tabla="pendientes"
                                           onchange="actualizarContadorSeleccionados()">
                                </div>
                            </td>
                            <td class="px-6 py-1">
                                @if($aviso->avisoable_type === 'App\Models\Producto' && $aviso->avisoable_id && $aviso->avisoable_id !== null)
                                    <div class="flex flex-col space-y-1">
                                        <span class="text-gray-200 dark:text-gray-200 break-words text-sm">{{ $aviso->elemento_nombre }}</span>
                                        <div class="flex items-center space-x-1">
                                            @if($aviso->avisoable && $aviso->avisoable->categoria)
                                                <button onclick="event.stopPropagation(); window.open('/{{ implode('/', $aviso->avisoable->categoria->obtenerSlugsJerarquia()) }}/{{ $aviso->avisoable->slug }}', '_blank')" 
                                                    class="px-2 py-1 text-xs bg-green-600 hover:bg-green-700 text-white rounded transition-colors">
                                                    Ver
                                                </button>
                                            @endif
                                            @php
                                                $ofertaMasBarata = obtenerPrimeraOfertaProducto($aviso->avisoable_id);
                                            @endphp
                                            @if($ofertaMasBarata)
                                                <button onclick="event.stopPropagation(); window.open('{{ $ofertaMasBarata->url }}', '_blank')" 
                                                    class="px-2 py-1 text-xs bg-orange-600 hover:bg-orange-700 text-white rounded transition-colors">
                                                    1ª Oferta
                                                </button>
                                                <button onclick="event.stopPropagation(); window.open('{{ route('admin.ofertas.edit', $ofertaMasBarata->id) }}', '_blank')" 
                                                    class="px-2 py-1 text-xs bg-blue-600 hover:bg-blue-700 text-white rounded transition-colors">
                                                    Editar
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                @elseif($aviso->avisoable_type === 'App\Models\OfertaProducto' && $aviso->avisoable_id && $aviso->avisoable_id !== null)
                                    <div class="flex flex-col space-y-1">
                                        <span class="text-gray-200 dark:text-gray-200 break-words text-sm">{{ $aviso->elemento_nombre }}</span>
                                        <div class="flex items-center space-x-1">
                                            <button onclick="event.stopPropagation(); window.open('{{ route('admin.ofertas.edit', $aviso->avisoable_id) }}', '_blank')" 
                                                class="px-3 py-1.5 text-sm bg-blue-600 hover:bg-blue-700 text-white rounded transition-colors">
                                                Editar
                                            </button>
                                            @if($aviso->avisoable && $aviso->avisoable->url)
                                                <button onclick="event.stopPropagation(); window.open('{{ $aviso->avisoable->url }}', '_blank')" 
                                                    class="px-3 py-1.5 text-sm bg-green-600 hover:bg-green-700 text-white rounded transition-colors">
                                                    Ver
                                                </button>
                                            @endif
                                            <button onclick="event.stopPropagation(); mostrarOferta({{ $aviso->avisoable_id }}, {{ $aviso->id }})" 
                                                class="px-3 py-1.5 text-sm bg-purple-600 hover:bg-purple-700 text-white rounded transition-colors">
                                                Mostrar->si
                                            </button>
                                        </div>
                                    </div>
                                @else
                                    <span class="text-gray-200 dark:text-gray-200">
                                        @if($aviso->avisoable_type === 'Interno' && $aviso->avisoable_id === 0)
                                            Aviso Interno
                                        @else
                                            {{ $aviso->elemento_nombre }}
                                        @endif
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-1 max-w-md">
                                <div class="text-base text-gray-200 dark:text-gray-200 whitespace-pre-line break-words max-w-full" style="overflow-wrap: anywhere; word-break: break-word;">
                                    {{ $aviso->texto_aviso }}
                                    @if($aviso->avisoable_type === 'App\Models\Producto' && $aviso->avisoable_id && $aviso->avisoable_id !== null)
                                        @php
                                            $ofertaMasBarata = obtenerPrimeraOfertaProducto($aviso->avisoable_id);
                                        @endphp
                                        @if($ofertaMasBarata)
                                            @php
                                                $productoOferta = \App\Models\Producto::find($aviso->avisoable_id);
                                            @endphp
                                            <div class="mt-0 p-1 bg-gray-700 rounded text-xs text-gray-300">
                                                <div class="flex flex-wrap items-center gap-1 text-xs">
                                                    <span class="font-medium">{{ $ofertaMasBarata->tienda->nombre ?? 'Tienda ID: ' . $ofertaMasBarata->tienda_id }}</span>
                                                    <span>•</span>
                                                    <span>{{ formatearUnidades($ofertaMasBarata->unidades, $productoOferta->unidadDeMedida ?? 'unidades') }} uds</span>
                                                    <span>•</span>
                                                    <input type="number" 
                                                           step="0.01" 
                                                           min="0" 
                                                           value="{{ number_format($ofertaMasBarata->precio_total, 2, '.', '') }}" 
                                                           data-oferta-id="{{ $ofertaMasBarata->id }}"
                                                           data-producto-id="{{ $productoOferta->id ?? '' }}"
                                                           data-unidades="{{ $ofertaMasBarata->unidades }}"
                                                           data-unidad-medida="{{ $productoOferta->unidadDeMedida ?? 'unidad' }}"
                                                           data-precio-original="{{ number_format($ofertaMasBarata->precio_total, 2, '.', '') }}"
                                                           class="precio-total-oferta-mas-barata w-20 px-1 py-0.5 bg-gray-600 text-gray-200 border border-gray-500 rounded text-xs focus:outline-none focus:ring-1 focus:ring-blue-500"
                                                           onclick="event.stopPropagation(); this.select(); this.value = '';"
                                                           onfocus="this.select(); this.value = '';">
                                                    <span>€</span>
                                                    <button onclick="event.stopPropagation(); guardarPrecioOfertaMasBarata({{ $ofertaMasBarata->id }})" 
                                                        class="px-2 py-1 text-xs bg-green-600 hover:bg-green-700 text-white rounded transition-colors">
                                                        Guardar
                                                    </button>
                                                    <span>•</span>
                                                    <span class="text-green-400 font-medium precio-unidad-oferta-mas-barata" data-oferta-id="{{ $ofertaMasBarata->id }}">{{ ($productoOferta->unidadDeMedida ?? 'unidades') === 'unidadMilesima' ? number_format($ofertaMasBarata->precio_unidad, 3) : number_format($ofertaMasBarata->precio_unidad, 2) }}€/ud</span>
                                                    @if($ofertaMasBarata->chollo_id !== null)
                                                        <span>•</span>
                                                        <span class="text-orange-500 font-medium">Chollo</span>
                                                    @endif
                                                </div>
                                            </div>
                                        @endif
                                    @elseif($aviso->avisoable_type === 'App\Models\OfertaProducto' && $aviso->avisoable_id && $aviso->avisoable_id !== null && $aviso->avisoable)
                                        <div class="mt-1 p-1.5 bg-gray-700 rounded text-xs text-gray-300">
                                            <div class="flex flex-wrap items-center gap-2 text-xs">
                                                <span class="font-medium">{{ $aviso->avisoable->tienda->nombre ?? 'Tienda ID: ' . $aviso->avisoable->tienda_id }}</span>
                                                <span>•</span>
                                                <span>{{ formatearUnidades($aviso->avisoable->unidades, $aviso->avisoable->producto->unidadDeMedida ?? 'unidades') }} uds</span>
                                                <span>•</span>
                                                <input type="number" 
                                                       step="0.01" 
                                                       min="0" 
                                                       value="{{ number_format($aviso->avisoable->precio_total, 2, '.', '') }}" 
                                                       data-oferta-id="{{ $aviso->avisoable_id }}"
                                                       data-producto-id="{{ $aviso->avisoable->producto_id ?? '' }}"
                                                       data-unidades="{{ $aviso->avisoable->unidades }}"
                                                       data-precio-original="{{ number_format($aviso->avisoable->precio_total, 2, '.', '') }}"
                                                       class="precio-total-input w-20 px-1 py-0.5 bg-gray-600 text-gray-200 border border-gray-500 rounded text-xs focus:outline-none focus:ring-1 focus:ring-blue-500"
                                                       onclick="event.stopPropagation(); this.select();"
                                                       onfocus="this.select(); this.value = '';"
                                                       onblur="if(this.value === '' || this.value === null) { this.value = this.dataset.precioOriginal; calcularPrecioUnidad(this); } else { calcularPrecioUnidad(this); }"
                                                       onchange="calcularPrecioUnidad(this)">
                                                <span>€</span>
                                                <span>•</span>
                                                <span class="text-green-400 font-medium precio-unidad-display" data-oferta-id="{{ $aviso->avisoable_id }}">{{ number_format($aviso->avisoable->precio_unidad, 2) }}€/ud</span>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-1 text-sm text-gray-200 dark:text-gray-200">
                                {{ $aviso->fecha_aviso->format('d/m/Y H:i') }}
                            </td>
                            <td class="px-6 py-1" onclick="event.stopPropagation()">
                                <div class="flex items-center space-x-2">
                                    <button onclick="editarAviso({{ $aviso->id }}, {{ json_encode($aviso->texto_aviso) }}, '{{ $aviso->fecha_aviso->format('Y-m-d\TH:i') }}', {{ $aviso->oculto ? 'true' : 'false' }})" 
                                        class="px-4 py-2 text-sm bg-blue-600 hover:bg-blue-700 text-white rounded-md transition-colors">
                                        Editar
                                    </button>
                                    @if($aviso->avisoable_type === 'App\Models\OfertaProducto' && preg_match('/\d+\s*(?:a\s*)?vez/i', $aviso->texto_aviso))
                                        <button onclick="aplazarAviso({{ $aviso->id }})" 
                                            class="px-3 py-1 text-sm bg-yellow-600 hover:bg-yellow-700 text-white rounded-md transition-colors">
                                            Aplazar
                                        </button>
                                    @endif
                                    @if(!($aviso->avisoable_type === 'App\Models\OfertaProducto' && preg_match('/\d+\s*(?:a\s*)?vez/i', $aviso->texto_aviso)))
                                        <button onclick="eliminarAviso({{ $aviso->id }})" 
                                            class="text-red-400 hover:text-red-300 dark:text-red-400 dark:hover:text-red-300 p-1">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-6 py-4 text-center text-gray-400 dark:text-gray-400">
                                No hay avisos pendientes.
                            </td>
                        </tr>
                        @endforelse
                        <tr class="mensaje-sin-avisos-filtrado hidden">
                            <td colspan="6" class="px-6 py-4 text-center text-gray-400 dark:text-gray-400">
                                No hay avisos en esta categoría.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <!-- Paginación para avisos pendientes -->
            <div class="mt-4 px-4">
                {{ $avisosPendientes->links() }}
            </div>
        </div>

        <div id="content-ocultos" class="tab-content hidden">
            @php
                $conteoTiposOcultos = contarAvisosPorTipo($avisosOcultos);
            @endphp
            <div class="mb-4">
                <div class="flex flex-wrap gap-2">
                    @foreach($subTabs as $clave => $label)
                        @php
                            $count = $conteoTiposOcultos[$clave] ?? 0;
                        @endphp
                        <button
                            type="button"
                            class="subtab-button inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-md border border-gray-600 text-gray-300 hover:border-pink-500 hover:text-pink-500 transition-colors"
                            data-tabla="ocultos"
                            data-subtab="{{ $clave }}"
                        >
                            <span>{{ $label }}</span>
                            @if($clave !== 'todos')
                                <span class="ml-1 text-[11px] text-gray-400">({{ $count }})</span>
                            @endif
                        </button>
                    @endforeach
                </div>
            </div>
            <div class="bg-gray-800 dark:bg-gray-800 shadow rounded-lg overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-700 dark:divide-gray-600">
                    <thead class="bg-gray-700 dark:bg-gray-700">
                        <tr>
                            <th class="py-3 text-left text-xs font-medium text-gray-300 dark:text-gray-300 uppercase tracking-wider" style="width: 20px;"></th>
                            <th class="px-2 py-2 text-left text-xs font-medium text-gray-300 dark:text-gray-300 uppercase tracking-wider" style="width: 20px;">
                                <input type="checkbox" id="select-all-ocultos" class="checkbox-eliminar rounded border-gray-300 text-red-600 focus:ring-red-500" onchange="toggleAllCheckboxes('ocultos')">
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-300 dark:text-gray-300 uppercase tracking-wider w-1/4">Elemento</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-300 dark:text-gray-300 uppercase tracking-wider w-2/5">Texto del Aviso</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-300 dark:text-gray-300 uppercase tracking-wider w-1/12">Fecha</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-300 dark:text-gray-300 uppercase tracking-wider w-1/12">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-700 dark:divide-gray-600">
                        @forelse ($avisosOcultos as $aviso)
                        @php
                            $tipoAviso = obtenerTipoAviso($aviso);
                        @endphp
                        <tr class="hover:bg-gray-700 dark:hover:bg-gray-700 transition-colors cursor-pointer subtab-row" data-tipo="{{ $tipoAviso }}" onclick="editarAviso({{ $aviso->id }}, {{ json_encode($aviso->texto_aviso) }}, '{{ $aviso->fecha_aviso->format('Y-m-d\TH:i') }}', {{ $aviso->oculto ? 'true' : 'false' }})">
                            <td class="p-0" onclick="event.stopPropagation()" style="width: 20px;">
                                @if($aviso->avisoable_type === 'App\Models\Producto')
                                    <div class="flex items-center justify-center bg-blue-600" style="writing-mode: vertical-lr; transform: rotate(180deg); min-height: 40px;">
                                        <span class="text-white text-xs font-bold py-2">Producto</span>
                                    </div>
                                @elseif($aviso->avisoable_type === 'App\Models\OfertaProducto')
                                    <div class="flex items-center justify-center bg-purple-600" style="writing-mode: vertical-lr; transform: rotate(180deg); min-height: 40px;">
                                        <span class="text-white text-xs font-bold py-2">Oferta</span>
                                    </div>
                                @else
                                    <div class="flex items-center justify-center bg-gray-600" style="writing-mode: vertical-lr; transform: rotate(180deg); min-height: 40px;">
                                        <span class="text-gray-200 text-xs font-bold py-2">Interno</span>
                                    </div>
                                @endif
                            </td>
                            <td class="px-6 py-1 cursor-pointer hover:bg-gray-600 transition-colors" onclick="event.stopPropagation(); toggleCheckbox({{ $aviso->id }}, 'ocultos')">
                                <div class="flex items-center justify-center h-8">
                                    <input type="checkbox" class="checkbox-aviso rounded border-gray-300 text-red-600 focus:ring-red-500 pointer-events-none" 
                                           data-aviso-id="{{ $aviso->id }}" 
                                           data-tabla="ocultos"
                                           onchange="actualizarContadorSeleccionados()">
                                </div>
                            </td>
                            <td class="px-6 py-1">
                                @if($aviso->avisoable_type === 'App\Models\Producto' && $aviso->avisoable_id && $aviso->avisoable_id !== null)
                                    <div class="flex flex-col space-y-1">
                                        <span class="text-gray-200 dark:text-gray-200 break-words text-sm">{{ $aviso->elemento_nombre }}</span>
                                        <div class="flex items-center space-x-1">
                                            @if($aviso->avisoable && $aviso->avisoable->categoria)
                                                <button onclick="event.stopPropagation(); window.open('/{{ implode('/', $aviso->avisoable->categoria->obtenerSlugsJerarquia()) }}/{{ $aviso->avisoable->slug }}', '_blank')" 
                                                    class="px-2 py-1 text-xs bg-green-600 hover:bg-green-700 text-white rounded transition-colors">
                                                    Ver
                                                </button>
                                            @endif
                                            @php
                                                $ofertaMasBarata = obtenerPrimeraOfertaProducto($aviso->avisoable_id);
                                            @endphp
                                            @if($ofertaMasBarata)
                                                <button onclick="event.stopPropagation(); window.open('{{ $ofertaMasBarata->url }}', '_blank')" 
                                                    class="px-2 py-1 text-xs bg-orange-600 hover:bg-orange-700 text-white rounded transition-colors">
                                                    1ª Oferta
                                                </button>
                                                <button onclick="event.stopPropagation(); window.open('{{ route('admin.ofertas.edit', $ofertaMasBarata->id) }}', '_blank')" 
                                                    class="px-2 py-1 text-xs bg-blue-600 hover:bg-blue-700 text-white rounded transition-colors">
                                                    Editar
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                @elseif($aviso->avisoable_type === 'App\Models\OfertaProducto' && $aviso->avisoable_id && $aviso->avisoable_id !== null)
                                    <div class="flex flex-col space-y-1">
                                        <span class="text-gray-200 dark:text-gray-200 break-words text-sm">{{ $aviso->elemento_nombre }}</span>
                                        <div class="flex items-center space-x-1">
                                            <button onclick="event.stopPropagation(); window.open('{{ route('admin.ofertas.edit', $aviso->avisoable_id) }}', '_blank')" 
                                                class="px-3 py-1.5 text-sm bg-blue-600 hover:bg-blue-700 text-white rounded transition-colors">
                                                Editar
                                            </button>
                                            @if($aviso->avisoable && $aviso->avisoable->url)
                                                <button onclick="event.stopPropagation(); window.open('{{ $aviso->avisoable->url }}', '_blank')" 
                                                    class="px-3 py-1.5 text-sm bg-green-600 hover:bg-green-700 text-white rounded transition-colors">
                                                    Ver
                                                </button>
                                            @endif
                                            <button onclick="event.stopPropagation(); mostrarOferta({{ $aviso->avisoable_id }}, {{ $aviso->id }})" 
                                                class="px-3 py-1.5 text-sm bg-purple-600 hover:bg-purple-700 text-white rounded transition-colors">
                                                Mostrar->si
                                            </button>
                                        </div>
                                    </div>
                                @else
                                    <span class="text-gray-200 dark:text-gray-200">
                                        @if($aviso->avisoable_type === 'Interno' && $aviso->avisoable_id === 0)
                                            Aviso Interno
                                        @else
                                            {{ $aviso->elemento_nombre }}
                                        @endif
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-1 max-w-md">
                                <div class="text-base text-gray-200 dark:text-gray-200 whitespace-pre-line break-words max-w-full" style="overflow-wrap: anywhere; word-break: break-word;">
                                    {{ $aviso->texto_aviso }}
                                    @if($aviso->avisoable_type === 'App\Models\Producto' && $aviso->avisoable_id && $aviso->avisoable_id !== null)
                                        @php
                                            $ofertaMasBarata = obtenerPrimeraOfertaProducto($aviso->avisoable_id);
                                        @endphp
                                        @if($ofertaMasBarata)
                                            @php
                                                $productoOferta = \App\Models\Producto::find($aviso->avisoable_id);
                                            @endphp
                                            <div class="mt-0 p-1 bg-gray-700 rounded text-xs text-gray-300">
                                                <div class="flex flex-wrap items-center gap-1 text-xs">
                                                    <span class="font-medium">{{ $ofertaMasBarata->tienda->nombre ?? 'Tienda ID: ' . $ofertaMasBarata->tienda_id }}</span>
                                                    <span>•</span>
                                                    <span>{{ formatearUnidades($ofertaMasBarata->unidades, $productoOferta->unidadDeMedida ?? 'unidades') }} uds</span>
                                                    <span>•</span>
                                                    <input type="number" 
                                                           step="0.01" 
                                                           min="0" 
                                                           value="{{ number_format($ofertaMasBarata->precio_total, 2, '.', '') }}" 
                                                           data-oferta-id="{{ $ofertaMasBarata->id }}"
                                                           data-producto-id="{{ $productoOferta->id ?? '' }}"
                                                           data-unidades="{{ $ofertaMasBarata->unidades }}"
                                                           data-unidad-medida="{{ $productoOferta->unidadDeMedida ?? 'unidad' }}"
                                                           data-precio-original="{{ number_format($ofertaMasBarata->precio_total, 2, '.', '') }}"
                                                           class="precio-total-oferta-mas-barata w-20 px-1 py-0.5 bg-gray-600 text-gray-200 border border-gray-500 rounded text-xs focus:outline-none focus:ring-1 focus:ring-blue-500"
                                                           onclick="event.stopPropagation(); this.select(); this.value = '';"
                                                           onfocus="this.select(); this.value = '';">
                                                    <span>€</span>
                                                    <button onclick="event.stopPropagation(); guardarPrecioOfertaMasBarata({{ $ofertaMasBarata->id }})" 
                                                        class="px-2 py-1 text-xs bg-green-600 hover:bg-green-700 text-white rounded transition-colors">
                                                        Guardar
                                                    </button>
                                                    <span>•</span>
                                                    <span class="text-green-400 font-medium precio-unidad-oferta-mas-barata" data-oferta-id="{{ $ofertaMasBarata->id }}">{{ ($productoOferta->unidadDeMedida ?? 'unidades') === 'unidadMilesima' ? number_format($ofertaMasBarata->precio_unidad, 3) : number_format($ofertaMasBarata->precio_unidad, 2) }}€/ud</span>
                                                    @if($ofertaMasBarata->chollo_id !== null)
                                                        <span>•</span>
                                                        <span class="text-orange-500 font-medium">Chollo</span>
                                                    @endif
                                                </div>
                                            </div>
                                        @endif
                                    @elseif($aviso->avisoable_type === 'App\Models\OfertaProducto' && $aviso->avisoable_id && $aviso->avisoable_id !== null && $aviso->avisoable)
                                        <div class="mt-1 p-1.5 bg-gray-700 rounded text-xs text-gray-300">
                                            <div class="flex flex-wrap items-center gap-2 text-xs">
                                                <span class="font-medium">{{ $aviso->avisoable->tienda->nombre ?? 'Tienda ID: ' . $aviso->avisoable->tienda_id }}</span>
                                                <span>•</span>
                                                <span>{{ formatearUnidades($aviso->avisoable->unidades, $aviso->avisoable->producto->unidadDeMedida ?? 'unidades') }} uds</span>
                                                <span>•</span>
                                                <input type="number" 
                                                       step="0.01" 
                                                       min="0" 
                                                       value="{{ number_format($aviso->avisoable->precio_total, 2, '.', '') }}" 
                                                       data-oferta-id="{{ $aviso->avisoable_id }}"
                                                       data-producto-id="{{ $aviso->avisoable->producto_id ?? '' }}"
                                                       data-unidades="{{ $aviso->avisoable->unidades }}"
                                                       data-precio-original="{{ number_format($aviso->avisoable->precio_total, 2, '.', '') }}"
                                                       class="precio-total-input w-20 px-1 py-0.5 bg-gray-600 text-gray-200 border border-gray-500 rounded text-xs focus:outline-none focus:ring-1 focus:ring-blue-500"
                                                       onclick="event.stopPropagation(); this.select();"
                                                       onfocus="this.select(); this.value = '';"
                                                       onblur="if(this.value === '' || this.value === null) { this.value = this.dataset.precioOriginal; calcularPrecioUnidad(this); } else { calcularPrecioUnidad(this); }"
                                                       onchange="calcularPrecioUnidad(this)">
                                                <span>€</span>
                                                <span>•</span>
                                                <span class="text-green-400 font-medium precio-unidad-display" data-oferta-id="{{ $aviso->avisoable_id }}">{{ number_format($aviso->avisoable->precio_unidad, 2) }}€/ud</span>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-1 text-sm text-gray-200 dark:text-gray-200">
                                {{ $aviso->fecha_aviso->format('d/m/Y H:i') }}
                            </td>
                            <td class="px-6 py-1" onclick="event.stopPropagation()">
                                <div class="flex items-center space-x-2">
                                    <button onclick="editarAviso({{ $aviso->id }}, {{ json_encode($aviso->texto_aviso) }}, '{{ $aviso->fecha_aviso->format('Y-m-d\TH:i') }}', {{ $aviso->oculto ? 'true' : 'false' }})" 
                                        class="px-4 py-2 text-sm bg-blue-600 hover:bg-blue-700 text-white rounded-md transition-colors">
                                        Editar
                                    </button>
                                    @if($aviso->avisoable_type === 'App\Models\OfertaProducto' && preg_match('/\d+\s*(?:a\s*)?vez/i', $aviso->texto_aviso))
                                        <button onclick="aplazarAviso({{ $aviso->id }})" 
                                            class="px-3 py-1 text-sm bg-yellow-600 hover:bg-yellow-700 text-white rounded-md transition-colors">
                                            Aplazar
                                        </button>
                                    @endif
                                    @if(!($aviso->avisoable_type === 'App\Models\OfertaProducto' && preg_match('/\d+\s*(?:a\s*)?vez/i', $aviso->texto_aviso)))
                                        <button onclick="eliminarAviso({{ $aviso->id }})" 
                                            class="text-red-400 hover:text-red-300 dark:text-red-400 dark:hover:text-red-300 p-1">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-6 py-4 text-center text-gray-400 dark:text-gray-400">
                                No hay avisos ocultos.
                            </td>
                        </tr>
                        @endforelse
                        <tr class="mensaje-sin-avisos-filtrado hidden">
                            <td colspan="6" class="px-6 py-4 text-center text-gray-400 dark:text-gray-400">
                                No hay avisos en esta categoría.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <!-- Paginación para avisos ocultos -->
            <div class="mt-4 px-4">
                {{ $avisosOcultos->links() }}
            </div>
        </div>
    </div>

    <!-- Modal para editar aviso -->
    <div id="modal-editar-aviso" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div class="bg-gray-800 dark:bg-gray-800 rounded-lg p-6 max-w-2xl w-full">
            <h2 class="text-lg font-semibold mb-4 text-gray-200 dark:text-gray-200">Editar Aviso</h2>
            <form id="form-editar-aviso">
                <input type="hidden" id="aviso-id" name="aviso_id">
                <div class="mb-4">
                    <label for="texto-aviso" class="block text-sm font-medium text-gray-300 dark:text-gray-300 mb-2">Texto del aviso</label>
                    <textarea id="texto-aviso" name="texto_aviso" rows="3" class="w-full px-3 py-2 border border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200 resize-none" required></textarea>
                </div>
                <div class="mb-4">
                    <label for="fecha-aviso" class="block text-sm font-medium text-gray-300 dark:text-gray-300 mb-2">Fecha y hora</label>
                    <input type="datetime-local" id="fecha-aviso" name="fecha_aviso" class="w-full px-3 py-2 border border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200" required>
                </div>
                <div class="mb-4">
                    <label class="flex items-center">
                        <input type="checkbox" id="oculto" name="oculto" class="rounded border-gray-300 text-pink-600 focus:ring-pink-500">
                        <span class="ml-2 text-sm text-gray-300 dark:text-gray-300">Ocultar aviso</span>
                    </label>
                    <p class="text-xs text-gray-400 dark:text-gray-400 mt-1">Los avisos ocultos no aparecen en las pestañas principales pero se mantienen para futuras comprobaciones</p>
                </div>
                <div class="flex justify-between">
                    <button type="button" onclick="eliminarAvisoDesdeModal()" class="px-6 py-4 text-sm font-medium text-white bg-red-600 rounded-md hover:bg-red-700">
                        Eliminar
                    </button>
                    <div class="flex space-x-3">
                        <button type="button" onclick="cerrarModalEditar()" class="px-6 py-4 text-sm font-medium text-gray-300 bg-gray-600 rounded-md hover:bg-gray-500 dark:bg-gray-600 dark:text-gray-300 dark:hover:bg-gray-500">
                            Cancelar
                        </button>
                        <button type="submit" class="px-6 py-4 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700">
                            Guardar
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal para confirmar envío de alertas -->
    <div id="modal-enviar-alertas" class="hidden fixed inset-0 flex items-center justify-center z-50 p-4 backdrop-blur-sm" style="pointer-events: none;">
        <div class="bg-gray-800 dark:bg-gray-800 rounded-lg p-6 max-w-2xl w-full shadow-2xl border-2 border-gray-600" style="pointer-events: auto;">
            <h2 class="text-lg font-semibold mb-4 text-gray-200 dark:text-gray-200">Confirmar envío de alertas</h2>
            <div class="mb-4">
                <p class="text-sm text-gray-300 dark:text-gray-300 mb-3">Precio actual del producto:</p>
                <div class="flex items-center gap-3 mb-4">
                    <p class="text-xl font-bold text-gray-200" id="modal-precio-producto">-</p>
                    <p class="text-sm font-medium" id="modal-mensaje-validacion"></p>
                </div>
                
                <p class="text-sm text-gray-300 dark:text-gray-300 mb-3">Precios límites de las alertas:</p>
                <div id="modal-precios-limites" class="space-y-2 mb-4">
                    <!-- Se llenará dinámicamente -->
                </div>
                
                <p class="text-sm text-gray-400 dark:text-gray-400" id="modal-total-alertas">Total: 0 alertas</p>
            </div>
            <div class="flex justify-end space-x-3">
                <button type="button" onclick="cerrarModalEnviarAlertas()" class="px-6 py-4 text-sm font-medium text-gray-300 bg-gray-600 rounded-md hover:bg-gray-500 dark:bg-gray-600 dark:text-gray-300 dark:hover:bg-gray-500">
                    Cancelar
                </button>
                <button type="button" id="btn-confirmar-enviar-alertas" onclick="confirmarEnviarAlertas()" class="px-6 py-4 text-sm font-medium text-white bg-green-600 rounded-md hover:bg-green-700">
                    Enviar
                </button>
            </div>
        </div>
    </div>

    <!-- Modal para crear aviso interno -->
    <div id="modal-nuevo-aviso-interno" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div class="bg-gray-800 dark:bg-gray-800 rounded-lg p-6 max-w-2xl w-full">
            <h2 class="text-lg font-semibold mb-4 text-gray-200 dark:text-gray-200">Crear Aviso Interno</h2>
            <form id="form-nuevo-aviso-interno">
                <input type="hidden" name="tipo_aviso" value="interno">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-300 dark:text-gray-300 mb-2">Usuarios</label>
                    <div class="relative">
                        <button type="button" 
                                id="dropdown-usuarios-btn" 
                                class="w-full px-3 py-2 text-left border border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200 flex items-center justify-between">
                            <span id="dropdown-usuarios-text">Seleccionar usuarios...</span>
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                        <div id="dropdown-usuarios-menu" class="hidden absolute z-10 w-full mt-1 bg-gray-700 border border-gray-600 rounded-md shadow-lg max-h-60 overflow-y-auto">
                            <div class="p-2">
                                <label class="flex items-center px-3 py-2 hover:bg-gray-600 rounded cursor-pointer">
                                    <input type="checkbox" 
                                           id="checkbox-todos" 
                                           class="checkbox-usuario rounded border-gray-300 text-blue-600 focus:ring-blue-500" 
                                           value="todos" 
                                           checked>
                                    <span class="ml-2 text-sm text-gray-200">Todos</span>
                                </label>
                                @foreach($usuarios as $usuario)
                                    <label class="flex items-center px-3 py-2 hover:bg-gray-600 rounded cursor-pointer">
                                        <input type="checkbox" 
                                               class="checkbox-usuario rounded border-gray-300 text-blue-600 focus:ring-blue-500" 
                                               value="{{ $usuario->id }}" 
                                               data-user-name="{{ $usuario->name }}">
                                        <span class="ml-2 text-sm text-gray-200">{{ $usuario->name }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    <p class="text-xs text-gray-400 dark:text-gray-400 mt-1">Se creará un aviso para cada usuario seleccionado</p>
                </div>
                <div class="mb-4">
                    <label for="texto-nuevo-aviso" class="block text-sm font-medium text-gray-300 dark:text-gray-300 mb-2">Texto del aviso</label>
                    <textarea id="texto-nuevo-aviso" name="texto_aviso" rows="3" class="w-full px-3 py-2 border border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200 resize-none" required></textarea>
                </div>
                <div class="mb-4">
                    <label for="fecha-nuevo-aviso" class="block text-sm font-medium text-gray-300 dark:text-gray-300 mb-2">Fecha y hora</label>
                    <input type="datetime-local" id="fecha-nuevo-aviso" name="fecha_aviso" class="w-full px-3 py-2 border border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200" required>
                </div>
                <div class="mb-4">
                    <label class="flex items-center">
                        <input type="checkbox" id="oculto-nuevo" name="oculto" class="rounded border-gray-300 text-pink-600 focus:ring-pink-500">
                        <span class="ml-2 text-sm text-gray-300 dark:text-gray-300">Ocultar aviso</span>
                    </label>
                    <p class="text-xs text-gray-400 dark:text-gray-400 mt-1">Los avisos ocultos no aparecen en las pestañas principales pero se mantienen para futuras comprobaciones</p>
                </div>
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="cerrarModalNuevoAvisoInterno()" class="px-6 py-4 text-sm font-medium text-gray-300 bg-gray-600 rounded-md hover:bg-gray-500 dark:bg-gray-600 dark:text-gray-300 dark:hover:bg-gray-500">
                        Cancelar
                    </button>
                    <button type="submit" class="px-6 py-4 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700">
                        Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Funcionalidad de pestañas
        document.addEventListener('DOMContentLoaded', function() {
            const tabButtons = document.querySelectorAll('.tab-button');
            const tabContents = document.querySelectorAll('.tab-content');
            const subtabButtons = document.querySelectorAll('.subtab-button');

            // Funciones para guardar y restaurar el estado
            const guardarEstadoPestañas = (tabla, subtab) => {
                const estado = {
                    tabla: tabla,
                    subtab: subtab,
                    timestamp: Date.now() // Guardar marca de tiempo actual
                };
                localStorage.setItem('avisos-estado', JSON.stringify(estado));
            };

            const restaurarEstadoPestañas = () => {
                const estadoGuardado = localStorage.getItem('avisos-estado');
                
                // Si no hay estado guardado, usar valores por defecto
                if (!estadoGuardado) {
                    return { tabla: 'vencidos', subtab: 'todos', valido: false };
                }

                try {
                    const estado = JSON.parse(estadoGuardado);
                    const ahora = Date.now();
                    const tiempoTranscurrido = ahora - (estado.timestamp || 0);
                    const unaHoraEnMs = 60 * 60 * 1000; // 1 hora en milisegundos

                    // Si ha pasado más de una hora, usar valores por defecto
                    if (tiempoTranscurrido > unaHoraEnMs) {
                        // Limpiar el estado expirado
                        localStorage.removeItem('avisos-estado');
                        return { tabla: 'vencidos', subtab: 'todos', valido: false };
                    }

                    // El estado es válido (menos de una hora)
                    return {
                        tabla: estado.tabla || 'vencidos',
                        subtab: estado.subtab || 'todos',
                        valido: true
                    };
                } catch (e) {
                    // Si hay error al parsear, usar valores por defecto
                    localStorage.removeItem('avisos-estado');
                    return { tabla: 'vencidos', subtab: 'todos', valido: false };
                }
            };

            const activarSubtab = (tabla, subtab, guardarEstado = true) => {
                const container = document.getElementById(`content-${tabla}`);
                if (!container) {
                    return;
                }

                const botones = container.querySelectorAll('.subtab-button');
                botones.forEach(btn => {
                    btn.classList.remove('active-subtab', 'border-pink-500', 'text-pink-400', 'bg-gray-700');
                    btn.classList.add('border-gray-600', 'text-gray-300');
                });

                const botonActivo = container.querySelector(`.subtab-button[data-subtab="${subtab}"]`);
                if (botonActivo) {
                    botonActivo.classList.add('active-subtab', 'border-pink-500', 'text-pink-400', 'bg-gray-700');
                    botonActivo.classList.remove('border-gray-600', 'text-gray-300');
                }

                const filas = container.querySelectorAll('tbody .subtab-row');
                let visibles = 0;
                filas.forEach(fila => {
                    const tipo = fila.dataset.tipo || 'internos';
                    const mostrar = subtab === 'todos' || tipo === subtab;
                    fila.classList.toggle('hidden', !mostrar);
                    if (mostrar) {
                        visibles++;
                    }
                });

                const mensajeFiltrado = container.querySelector('.mensaje-sin-avisos-filtrado');
                if (mensajeFiltrado) {
                    if (filas.length === 0) {
                        mensajeFiltrado.classList.add('hidden');
                    } else {
                        mensajeFiltrado.classList.toggle('hidden', visibles > 0);
                    }
                }

                // Guardar estado si se solicita
                if (guardarEstado) {
                    guardarEstadoPestañas(tabla, subtab);
                }
            };

            tabButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const target = this.id === 'tab-vencidos' ? 'vencidos' : 
                                 this.id === 'tab-pendientes' ? 'pendientes' : 'ocultos';
                    
                    // Actualizar botones
                    tabButtons.forEach(btn => {
                        btn.classList.remove('active', 'border-pink-500', 'text-pink-600', 'dark:text-pink-400');
                        btn.classList.add('border-transparent', 'text-gray-500', 'dark:text-gray-400');
                    });
                    this.classList.add('active', 'border-pink-500', 'text-pink-600', 'dark:text-pink-400');
                    this.classList.remove('border-transparent', 'text-gray-500', 'dark:text-gray-400');
                    
                    // Mostrar contenido
                    tabContents.forEach(content => {
                        content.classList.add('hidden');
                    });
                    document.getElementById(`content-${target}`).classList.remove('hidden');

                    // Si cambiamos de pestaña principal, usar la subpestaña guardada para esa pestaña solo si es válida
                    const estadoGuardado = restaurarEstadoPestañas();
                    const subtabAUsar = (estadoGuardado.valido && estadoGuardado.tabla === target) 
                        ? estadoGuardado.subtab 
                        : 'todos';
                    activarSubtab(target, subtabAUsar);
                });
            });

            subtabButtons.forEach(button => {
                button.addEventListener('click', function(event) {
                    event.stopPropagation();
                    const tabla = this.dataset.tabla;
                    const subtab = this.dataset.subtab;
                    activarSubtab(tabla, subtab);
                });
            });

            // Restaurar estado guardado al cargar la página
            const estadoGuardado = restaurarEstadoPestañas();
            
            // Solo usar el estado guardado si es válido (menos de una hora)
            if (estadoGuardado.valido) {
                const tablaInicial = estadoGuardado.tabla;
                const subtabInicial = estadoGuardado.subtab;

                // Activar la pestaña principal guardada directamente
                const tabButtonInicial = document.getElementById(`tab-${tablaInicial}`);
                if (tabButtonInicial) {
                    // Actualizar botones de pestañas principales
                    tabButtons.forEach(btn => {
                        btn.classList.remove('active', 'border-pink-500', 'text-pink-600', 'dark:text-pink-400');
                        btn.classList.add('border-transparent', 'text-gray-500', 'dark:text-gray-400');
                    });
                    tabButtonInicial.classList.add('active', 'border-pink-500', 'text-pink-600', 'dark:text-pink-400');
                    tabButtonInicial.classList.remove('border-transparent', 'text-gray-500', 'dark:text-gray-400');
                    
                    // Mostrar contenido de la pestaña
                    tabContents.forEach(content => {
                        content.classList.add('hidden');
                    });
                    const contenidoInicial = document.getElementById(`content-${tablaInicial}`);
                    if (contenidoInicial) {
                        contenidoInicial.classList.remove('hidden');
                    }

                    // Activar la subpestaña guardada (sin guardar para evitar actualizar el timestamp)
                    activarSubtab(tablaInicial, subtabInicial, false);
                } else {
                    // Si no existe la pestaña, usar la primera pestaña (vencidos) con subpestaña 'todos'
                    activarSubtab('vencidos', 'todos', false);
                }
            } else {
                // Estado expirado o no válido, usar valores por defecto
                activarSubtab('vencidos', 'todos', false);
            }
        });

        // Funciones para editar avisos
        function editarAviso(id, texto, fecha, oculto) {
            document.getElementById('aviso-id').value = id;
            const textarea = document.getElementById('texto-aviso');
            textarea.value = texto;
            document.getElementById('fecha-aviso').value = fecha;
            document.getElementById('oculto').checked = oculto;
            document.getElementById('modal-editar-aviso').classList.remove('hidden');
            
            // Ajustar altura del textarea automáticamente
            setTimeout(() => {
                ajustarAlturaTextarea(textarea);
            }, 100);
        }
        
        // Función para ajustar altura del textarea
        function ajustarAlturaTextarea(textarea) {
            textarea.style.height = 'auto';
            const lineHeight = parseInt(window.getComputedStyle(textarea).lineHeight);
            const lines = textarea.value.split('\n').length;
            const minHeight = 3 * lineHeight; // Mínimo 3 líneas
            const calculatedHeight = Math.max(lines * lineHeight, minHeight);
            textarea.style.height = calculatedHeight + 'px';
        }

        function cerrarModalEditar() {
            document.getElementById('modal-editar-aviso').classList.add('hidden');
        }

        // Formulario de edición
        document.getElementById('form-editar-aviso').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const avisoId = formData.get('aviso_id');
            
            fetch(`/panel-privado/avisos/${avisoId}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    texto_aviso: formData.get('texto_aviso'),
                    fecha_aviso: formData.get('fecha_aviso'),
                    oculto: formData.get('oculto') === 'on'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    cerrarModalEditar();
                    location.reload();
                } else {
                    alert('Error al actualizar el aviso');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al actualizar el aviso');
            });
        });

        // Función helper para obtener el texto del aviso desde el DOM
        function obtenerTextoAvisoDesdeDOM(avisoId) {
            // Buscar la fila que contiene el aviso
            const fila = document.querySelector(`tr[onclick*="editarAviso(${avisoId}"]`);
            if (!fila) {
                return null;
            }
            
            // Buscar la celda que contiene el texto del aviso (tercera columna)
            const celdas = fila.querySelectorAll('td');
            if (celdas.length < 3) {
                return null;
            }
            
            // La celda del texto es la tercera (índice 2)
            const celdaTexto = celdas[2];
            const divTexto = celdaTexto.querySelector('div.text-base');
            if (!divTexto) {
                return null;
            }
            
            // Obtener solo el texto directo del div (antes de cualquier elemento hijo)
            // El texto del aviso es el contenido de texto directo del div
            let textoAviso = '';
            
            // Recorrer los nodos hijos del div
            for (let node of divTexto.childNodes) {
                if (node.nodeType === Node.TEXT_NODE) {
                    // Es un nodo de texto, añadirlo
                    textoAviso += node.textContent;
                } else if (node.nodeType === Node.ELEMENT_NODE) {
                    // Si encontramos un elemento, el texto del aviso ya terminó
                    // El texto del aviso está antes del primer elemento hijo
                    break;
                }
            }
            
            return textoAviso.trim();
        }

        // Función para verificar el texto del aviso antes de eliminar
        async function verificarTextoAvisoAntesDeEliminar(avisoId, textoVista) {
            // Si no se pudo obtener el texto de la vista, permitir la eliminación (fallback)
            if (textoVista === null) {
                return true;
            }
            
            try {
                const response = await fetch(`/panel-privado/avisos/${avisoId}/texto`, {
                    method: 'GET',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });
                
                const data = await response.json();
                
                if (data.success) {
                    const textoBD = data.texto_aviso.trim();
                    const textoVistaTrim = textoVista.trim();
                    
                    // Comparar los textos
                    if (textoBD !== textoVistaTrim) {
                        alert('Este aviso ha cambiado el texto. La página se recargará para mostrar los cambios.');
                        location.reload();
                        return false;
                    }
                    return true;
                } else {
                    // Si hay error al obtener el texto, permitir la eliminación (fallback)
                    return true;
                }
            } catch (error) {
                console.error('Error al verificar texto del aviso:', error);
                // Si hay error, permitir la eliminación (fallback)
                return true;
            }
        }

        // Función para eliminar avisos
        async function eliminarAviso(id) {
            if (!confirm('¿Estás seguro de que quieres eliminar este aviso?')) {
                return;
            }
            
            // Obtener el texto del aviso desde el DOM
            const textoVista = obtenerTextoAvisoDesdeDOM(id);
            
            // Verificar el texto antes de eliminar (pasa null si no se pudo obtener)
            const puedeEliminar = await verificarTextoAvisoAntesDeEliminar(id, textoVista);
            
            if (!puedeEliminar) {
                return; // Ya se recargó la página en la función de verificación
            }
            
            fetch(`/panel-privado/avisos/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error al eliminar el aviso');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al eliminar el aviso');
            });
        }

        // Función para eliminar aviso desde el modal
        async function eliminarAvisoDesdeModal() {
            const avisoId = document.getElementById('aviso-id').value;
            if (!confirm('¿Estás seguro de que quieres eliminar este aviso?')) {
                return;
            }
            
            // Obtener el texto del aviso desde el textarea del modal
            const textoVista = document.getElementById('texto-aviso').value.trim();
            
            // Verificar el texto antes de eliminar
            const puedeEliminar = await verificarTextoAvisoAntesDeEliminar(avisoId, textoVista);
            
            if (!puedeEliminar) {
                return; // Ya se recargó la página en la función de verificación
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
                    cerrarModalEditar();
                    location.reload();
                } else {
                    alert('Error al eliminar el aviso');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al eliminar el aviso');
            });
        }

        // Cerrar modal al hacer clic fuera
        document.getElementById('modal-editar-aviso').addEventListener('click', function(e) {
            if (e.target === this) {
                cerrarModalEditar();
            }
        });

        // Cerrar modal con Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                cerrarModalEditar();
            }
        });
        
        // Ajustar textarea automáticamente mientras se escribe
        document.addEventListener('DOMContentLoaded', function() {
            const textarea = document.getElementById('texto-aviso');
            if (textarea) {
                textarea.addEventListener('input', function() {
                    ajustarAlturaTextarea(this);
                });
            }
            
            const textareaNuevo = document.getElementById('texto-nuevo-aviso');
            if (textareaNuevo) {
                textareaNuevo.addEventListener('input', function() {
                    ajustarAlturaTextarea(this);
                });
            }
        });

        // Funciones para crear aviso interno
        function mostrarModalNuevoAvisoInterno() {
            // Establecer fecha por defecto (mañana a las 00:01)
            const tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);
            tomorrow.setHours(0, 1, 0, 0);
            document.getElementById('fecha-nuevo-aviso').value = tomorrow.toISOString().slice(0, 16);
            
            // Resetear checkboxes: marcar solo "Todos"
            const checkboxTodos = document.getElementById('checkbox-todos');
            const checkboxesUsuarios = document.querySelectorAll('.checkbox-usuario:not(#checkbox-todos)');
            checkboxTodos.checked = true;
            checkboxesUsuarios.forEach(cb => cb.checked = false);
            actualizarTextoDropdownUsuarios();
            
            // Cerrar el dropdown si está abierto
            document.getElementById('dropdown-usuarios-menu').classList.add('hidden');
            
            document.getElementById('modal-nuevo-aviso-interno').classList.remove('hidden');
        }
        
        // Función para actualizar el texto del botón del dropdown
        function actualizarTextoDropdownUsuarios() {
            const checkboxTodos = document.getElementById('checkbox-todos');
            const checkboxesUsuarios = document.querySelectorAll('.checkbox-usuario:not(#checkbox-todos)');
            const checkboxesMarcados = Array.from(checkboxesUsuarios).filter(cb => cb.checked);
            const textoDropdown = document.getElementById('dropdown-usuarios-text');
            
            if (checkboxTodos.checked) {
                textoDropdown.textContent = 'Todos';
            } else if (checkboxesMarcados.length === 0) {
                textoDropdown.textContent = 'Seleccionar usuarios...';
            } else if (checkboxesMarcados.length === 1) {
                textoDropdown.textContent = checkboxesMarcados[0].dataset.userName;
            } else {
                textoDropdown.textContent = `${checkboxesMarcados.length} usuarios seleccionados`;
            }
        }
        
        // Configurar el dropdown de usuarios
        document.addEventListener('DOMContentLoaded', function() {
            const dropdownBtn = document.getElementById('dropdown-usuarios-btn');
            const dropdownMenu = document.getElementById('dropdown-usuarios-menu');
            const checkboxTodos = document.getElementById('checkbox-todos');
            const checkboxesUsuarios = document.querySelectorAll('.checkbox-usuario:not(#checkbox-todos)');
            
            // Abrir/cerrar dropdown
            if (dropdownBtn && dropdownMenu) {
                dropdownBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    dropdownMenu.classList.toggle('hidden');
                });
                
                // Cerrar dropdown al hacer clic fuera
                document.addEventListener('click', function(e) {
                    if (!dropdownBtn.contains(e.target) && !dropdownMenu.contains(e.target)) {
                        dropdownMenu.classList.add('hidden');
                    }
                });
            }
            
            // Lógica de checkboxes: "Todos" vs usuarios individuales
            if (checkboxTodos) {
                checkboxTodos.addEventListener('change', function() {
                    if (this.checked) {
                        // Si se marca "Todos", desmarcar todos los usuarios
                        checkboxesUsuarios.forEach(cb => cb.checked = false);
                    }
                    actualizarTextoDropdownUsuarios();
                });
            }
            
            // Si se marca un usuario, desmarcar "Todos"
            checkboxesUsuarios.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    if (this.checked) {
                        checkboxTodos.checked = false;
                    }
                    actualizarTextoDropdownUsuarios();
                });
            });
            
            // Inicializar texto del dropdown
            actualizarTextoDropdownUsuarios();
        });

        function cerrarModalNuevoAvisoInterno() {
            // Cerrar el dropdown si está abierto
            const dropdownMenu = document.getElementById('dropdown-usuarios-menu');
            if (dropdownMenu) {
                dropdownMenu.classList.add('hidden');
            }
            
            document.getElementById('modal-nuevo-aviso-interno').classList.add('hidden');
        }

        // Formulario de creación de aviso interno
        document.getElementById('form-nuevo-aviso-interno').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const submitButton = this.querySelector('button[type="submit"]');
            const originalText = submitButton.textContent;
            
            // Cerrar el dropdown si está abierto
            const dropdownMenu = document.getElementById('dropdown-usuarios-menu');
            if (dropdownMenu) {
                dropdownMenu.classList.add('hidden');
            }
            
            // Deshabilitar botón y mostrar estado de carga
            submitButton.disabled = true;
            submitButton.textContent = 'Guardando...';
            
            // Obtener los usuarios seleccionados
            const checkboxTodos = document.getElementById('checkbox-todos');
            const checkboxesUsuarios = document.querySelectorAll('.checkbox-usuario:not(#checkbox-todos)');
            let userIds = [];
            
            if (checkboxTodos && checkboxTodos.checked) {
                // Si "Todos" está marcado, enviar null para que el backend cree para todos
                userIds = null;
            } else {
                // Obtener los IDs de los usuarios marcados
                checkboxesUsuarios.forEach(checkbox => {
                    if (checkbox.checked) {
                        userIds.push(parseInt(checkbox.value));
                    }
                });
                
                // Validar que al menos un usuario esté seleccionado
                if (userIds.length === 0) {
                    alert('Por favor, selecciona al menos un usuario o marca "Todos"');
                    submitButton.disabled = false;
                    submitButton.textContent = originalText;
                    return;
                }
            }
            
            fetch('/panel-privado/avisos/interno', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    texto_aviso: formData.get('texto_aviso'),
                    fecha_aviso: formData.get('fecha_aviso'),
                    oculto: formData.get('oculto') === 'on',
                    user_ids: userIds
                })
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(errorData => {
                        throw new Error(errorData.message || `HTTP error! status: ${response.status}`);
                    }).catch(() => {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    });
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    cerrarModalNuevoAvisoInterno();
                    location.reload();
                } else {
                    console.error('Error del servidor:', data);
                    alert('Error al crear el aviso interno: ' + (data.message || 'Error desconocido'));
                }
            })
            .catch(error => {
                console.error('Error completo:', error);
                alert('Error al crear el aviso interno: ' + error.message);
            })
            .finally(() => {
                // Restaurar botón
                submitButton.disabled = false;
                submitButton.textContent = originalText;
            });
        });

        // Cerrar modal de nuevo aviso interno al hacer clic fuera
        document.getElementById('modal-nuevo-aviso-interno').addEventListener('click', function(e) {
            if (e.target === this) {
                cerrarModalNuevoAvisoInterno();
            }
        });

        // Cerrar modal de nuevo aviso interno con Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                cerrarModalNuevoAvisoInterno();
            }
        });

        // Función para calcular precio por unidad cuando cambia el precio total usando el servicio
        async function calcularPrecioUnidad(input) {
            const precioTotal = parseFloat(input.value) || 0;
            const unidades = parseFloat(input.dataset.unidades) || 1;
            const ofertaId = input.dataset.ofertaId;
            const productoId = input.dataset.productoId;
            const precioUnidadDisplay = document.querySelector(`.precio-unidad-display[data-oferta-id="${ofertaId}"]`);
            
            if (!precioUnidadDisplay) {
                return;
            }
            
            // Validar que tenemos los datos necesarios
            if (!productoId || isNaN(precioTotal) || precioTotal < 0 || isNaN(unidades) || unidades <= 0) {
                // Fallback: cálculo simple si no hay producto o datos inválidos
                if (unidades > 0) {
                    const precioUnidad = precioTotal / unidades;
                    precioUnidadDisplay.textContent = precioUnidad.toFixed(2) + '€/ud';
                }
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
                    // El servicio ya devuelve el precio con los decimales correctos (2 o 3 según unidadDeMedida)
                    // Formatear el número para mostrar los decimales correctos
                    const precioUnidad = parseFloat(data.precio_unidad);
                    // Si el número tiene más de 2 decimales (tercer decimal no es 0), mostrar 3, sino 2
                    const tiene3Decimales = (precioUnidad * 1000) % 10 !== 0;
                    const decimales = tiene3Decimales ? 3 : 2;
                    precioUnidadDisplay.textContent = precioUnidad.toFixed(decimales) + '€/ud';
                } else {
                    // Fallback si el servicio falla
                    if (unidades > 0) {
                        const precioUnidad = precioTotal / unidades;
                        precioUnidadDisplay.textContent = precioUnidad.toFixed(2) + '€/ud';
                    }
                }
            } catch (error) {
                console.error('Error al calcular precio por unidad:', error);
                // Fallback en caso de error de red
                if (unidades > 0) {
                    const precioUnidad = precioTotal / unidades;
                    precioUnidadDisplay.textContent = precioUnidad.toFixed(2) + '€/ud';
                }
            }
        }

        // Función para mostrar oferta y eliminar aviso
        function mostrarOferta(ofertaId, avisoId) {
            if (!confirm('¿Estás seguro de que quieres mostrar esta oferta y eliminar el aviso?')) {
                return;
            }
            
            // Obtener el precio_total del input si existe
            const precioTotalInput = document.querySelector(`.precio-total-input[data-oferta-id="${ofertaId}"]`);
            const precioUnidadDisplay = document.querySelector(`.precio-unidad-display[data-oferta-id="${ofertaId}"]`);
            
            // Preparar el body de la petición
            const bodyData = {
                mostrar: 'si'
            };
            
            // Si existe un input de precio_total, significa que se puede modificar
            if (precioTotalInput) {
                const precioTotal = parseFloat(precioTotalInput.value);
                
                // Incluir precio_total si es válido
                if (!isNaN(precioTotal) && precioTotal >= 0) {
                    bodyData.precio_total = precioTotal;
                }
                
                // Obtener el precio_unidad del display (extraer el número)
                // El precio_unidad ya fue calculado por calcularPrecioUnidad usando el servicio
                if (precioUnidadDisplay) {
                    const precioUnidadText = precioUnidadDisplay.textContent.replace('€/ud', '').trim();
                    const precioUnidad = parseFloat(precioUnidadText);
                    if (!isNaN(precioUnidad) && precioUnidad >= 0) {
                        bodyData.precio_unidad = precioUnidad;
                    }
                }
            }
            
            // Actualizar oferta a mostrar = si y opcionalmente los precios
            fetch(`/panel-privado/ofertas/${ofertaId}/mostrar`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify(bodyData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Eliminar el aviso
                    return fetch(`/panel-privado/avisos/${avisoId}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        }
                    });
                } else {
                    throw new Error('Error al actualizar la oferta');
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    throw new Error('Error al eliminar el aviso');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al procesar la solicitud');
            });
        }

        // Variables globales para el modal de alertas
        let productoIdActual = null;
        let precioActualModal = null;

        // Función para mostrar modal de confirmación de alertas
        async function enviarAlertasProducto(productoId, precioActual) {
            productoIdActual = productoId;
            precioActualModal = precioActual;
            
            // Obtener información de las alertas
            try {
                const response = await fetch('{{ route("avisos.info-alertas") }}?producto_id=' + productoId + '&precio_actual=' + precioActual, {
                    method: 'GET',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });
                
                const data = await response.json();
                
                if (!data.success) {
                    alert('Error al obtener información de alertas: ' + data.message);
                    return;
                }
                
                // Mostrar precio del producto
                const precioProducto = parseFloat(data.precio_producto);
                const precioProductoFormateado = precioProducto.toFixed(2);
                document.getElementById('modal-precio-producto').textContent = precioProductoFormateado + ' €';
                
                // Mostrar precios límites agrupados
                const preciosContainer = document.getElementById('modal-precios-limites');
                preciosContainer.innerHTML = '';
                
                if (Object.keys(data.precios_limites).length === 0) {
                    preciosContainer.innerHTML = '<p class="text-gray-400 text-sm">No hay alertas disponibles</p>';
                    return;
                }
                
                // Verificar si todos los precios límites son >= precio del producto
                let todosCorrectos = true;
                const mensajeValidacion = document.getElementById('modal-mensaje-validacion');
                
                for (const [precioLimite, cantidad] of Object.entries(data.precios_limites)) {
                    const precioLimiteNum = parseFloat(precioLimite);
                    
                    // Si algún precio límite es menor al precio del producto, marcar como incorrecto
                    if (precioLimiteNum < precioProducto) {
                        todosCorrectos = false;
                    }
                    
                    // Si el precio límite es mayor o igual al precio del producto, verde; si es menor, rojo
                    const colorTexto = precioLimiteNum >= precioProducto ? 'text-green-400' : 'text-red-400';
                    
                    const div = document.createElement('div');
                    div.className = 'flex justify-between items-center bg-gray-700 p-3 rounded';
                    div.innerHTML = `
                        <span class="${colorTexto} font-bold">${precioLimiteNum.toFixed(2)} €</span>
                        <span class="text-gray-400">${cantidad} ${cantidad === 1 ? 'alerta' : 'alertas'}</span>
                    `;
                    preciosContainer.appendChild(div);
                }
                
                // Mostrar mensaje de validación
                if (todosCorrectos) {
                    mensajeValidacion.textContent = 'Todos los correos son correctos';
                    mensajeValidacion.className = 'text-sm font-medium text-green-400';
                } else {
                    mensajeValidacion.textContent = 'Hay un precio de un correo que aplica a enviar correo que es inferior al precio del producto';
                    mensajeValidacion.className = 'text-sm font-medium text-red-400';
                }
                
                // Mostrar total
                document.getElementById('modal-total-alertas').textContent = 'Total: ' + data.total_alertas + ' ' + (data.total_alertas === 1 ? 'alerta' : 'alertas');
                
                // Mostrar modal
                document.getElementById('modal-enviar-alertas').classList.remove('hidden');
                
            } catch (error) {
                console.error('Error:', error);
                alert('Error de conexión al obtener información de alertas: ' + error.message);
            }
        }

        // Función para cerrar modal de alertas
        function cerrarModalEnviarAlertas() {
            document.getElementById('modal-enviar-alertas').classList.add('hidden');
            productoIdActual = null;
            precioActualModal = null;
        }

        // Función para confirmar y enviar alertas
        function confirmarEnviarAlertas() {
            if (!productoIdActual || precioActualModal === null) {
                alert('Error: No se pudo obtener la información del producto');
                return;
            }
            
            const btnConfirmar = document.getElementById('btn-confirmar-enviar-alertas');
            const textoOriginal = btnConfirmar.textContent;
            btnConfirmar.disabled = true;
            btnConfirmar.textContent = 'Enviando...';
            
            fetch('{{ route("avisos.enviar-alertas") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    producto_id: productoIdActual,
                    precio_actual: precioActualModal
                })
            })
            .then(response => response.json())
            .then(data => {
                console.log('Respuesta completa:', data);
                
                let mensaje = '=== RESULTADO DEL ENVÍO ===\n\n';
                mensaje += 'Correos enviados: ' + data.enviados + '\n';
                mensaje += 'Mensaje: ' + data.message + '\n\n';
                
                if (data.total_alertas !== undefined) {
                    mensaje += 'Total de alertas encontradas: ' + data.total_alertas + '\n';
                }
                
                if (data.alertas_filtradas !== undefined) {
                    mensaje += 'Alertas después de filtrar por fecha: ' + data.alertas_filtradas + '\n';
                }
                
                if (data.errores && data.errores.length > 0) {
                    mensaje += '\n--- ERRORES ---\n';
                    data.errores.forEach((error, index) => {
                        mensaje += (index + 1) + '. ' + error + '\n';
                    });
                }
                
                alert(mensaje);
                
                // Cerrar modal
                cerrarModalEnviarAlertas();
                
                if (data.success && data.enviados > 0) {
                    location.reload();
                } else {
                    btnConfirmar.disabled = false;
                    btnConfirmar.textContent = textoOriginal;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error de conexión al enviar alertas: ' + error.message);
                btnConfirmar.disabled = false;
                btnConfirmar.textContent = textoOriginal;
            });
        }


        // Cerrar modal de alertas con Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const modalAlertas = document.getElementById('modal-enviar-alertas');
                if (!modalAlertas.classList.contains('hidden')) {
                    cerrarModalEnviarAlertas();
                }
            }
        });

        // Funcionalidad para eliminar múltiples avisos

        function toggleAllCheckboxes(tabla) {
            const selectAll = document.getElementById(`select-all-${tabla}`);
            const checkboxes = document.querySelectorAll(`input[data-tabla="${tabla}"]`);
            
            checkboxes.forEach(checkbox => {
                checkbox.checked = selectAll.checked;
            });
            
            actualizarContadorSeleccionados();
        }

        function actualizarContadorSeleccionados() {
            const checkboxes = document.querySelectorAll('.checkbox-aviso:checked');
            const contador = document.getElementById('avisos-seleccionados');
            const btnEliminar = document.getElementById('btn-eliminar-seleccionados');
            
            const cantidad = checkboxes.length;
            contador.textContent = `${cantidad} avisos seleccionados`;
            
            // Habilitar/deshabilitar botón
            btnEliminar.disabled = cantidad === 0;
        }

        async function eliminarAvisosSeleccionados() {
            const checkboxes = document.querySelectorAll('.checkbox-aviso:checked');
            const avisosIds = Array.from(checkboxes).map(checkbox => checkbox.dataset.avisoId);
            
            if (avisosIds.length === 0) {
                alert('No hay avisos seleccionados para eliminar.');
                return;
            }
            
            if (!confirm(`¿Estás seguro de que quieres eliminar ${avisosIds.length} avisos?`)) {
                return;
            }
            
            // Deshabilitar botón durante la eliminación
            const btnEliminar = document.getElementById('btn-eliminar-seleccionados');
            const originalText = btnEliminar.textContent;
            btnEliminar.disabled = true;
            btnEliminar.textContent = 'Verificando...';
            
            // Verificar cada aviso antes de eliminar
            let avisosParaEliminar = [];
            
            for (const id of avisosIds) {
                const textoVista = obtenerTextoAvisoDesdeDOM(id);
                
                const puedeEliminar = await verificarTextoAvisoAntesDeEliminar(id, textoVista);
                
                if (!puedeEliminar) {
                    // Si el texto cambió, se recargó la página, así que salimos
                    return;
                }
                
                // Si llegamos aquí, el texto es igual, podemos eliminar
                avisosParaEliminar.push(id);
            }
            
            // Si no quedan avisos para eliminar (todos tenían cambios), recargar
            if (avisosParaEliminar.length === 0) {
                location.reload();
                return;
            }
            
            btnEliminar.textContent = 'Eliminando...';
            
            // Eliminar avisos uno por uno
            let eliminados = 0;
            let errores = 0;
            
            const eliminarAviso = (id) => {
                return fetch(`/panel-privado/avisos/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });
            };
            
            const promesas = avisosParaEliminar.map(id => 
                eliminarAviso(id)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            eliminados++;
                        } else {
                            errores++;
                        }
                    })
                    .catch(() => {
                        errores++;
                    })
            );
            
            Promise.all(promesas).then(() => {
                if (errores === 0) {
                    location.reload();
                } else if (eliminados > 0) {
                    alert(`Se eliminaron ${eliminados} avisos, pero ${errores} fallaron.`);
                    location.reload();
                } else {
                    alert('Error al eliminar los avisos.');
                    btnEliminar.disabled = false;
                    btnEliminar.textContent = originalText;
                }
            });
        }

        // Función para aplazar aviso
        function aplazarAviso(avisoId) {
            fetch(`/panel-privado/avisos/${avisoId}/aplazar`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error al aplazar el aviso: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error de conexión al aplazar el aviso');
            });
        }

        // Función para alternar checkbox al hacer clic en la celda
        function toggleCheckbox(avisoId, tabla) {
            const checkbox = document.querySelector(`input[data-aviso-id="${avisoId}"][data-tabla="${tabla}"]`);
            if (checkbox) {
                checkbox.checked = !checkbox.checked;
                actualizarContadorSeleccionados();
            }
        }

        // Función para guardar el precio de la oferta más barata
        async function guardarPrecioOfertaMasBarata(ofertaId) {
            const input = document.querySelector(`.precio-total-oferta-mas-barata[data-oferta-id="${ofertaId}"]`);
            const precioUnidadDisplay = document.querySelector(`.precio-unidad-oferta-mas-barata[data-oferta-id="${ofertaId}"]`);
            
            if (!input || !precioUnidadDisplay) {
                alert('Error: No se encontró el campo de precio');
                return;
            }

            const precioTotal = parseFloat(input.value);
            const unidades = parseFloat(input.dataset.unidades) || 1;
            const productoId = input.dataset.productoId;

            if (isNaN(precioTotal) || precioTotal < 0) {
                alert('Por favor, introduce un precio válido');
                input.value = input.dataset.precioOriginal;
                return;
            }

            if (!productoId) {
                alert('Error: No se encontró el producto asociado');
                return;
            }

            try {
                const response = await fetch(`/panel-privado/ofertas/${ofertaId}/mostrar`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        mostrar: 'si',
                        precio_total: precioTotal
                    })
                });

                const data = await response.json();

                if (data.success) {
                    // Actualizar el precio por unidad mostrado
                    const precioUnidad = parseFloat(data.precio_unidad);
                    const unidadMedida = input.dataset.unidadMedida;
                    const decimales = (unidadMedida === 'unidadMilesima') ? 3 : 2;
                    precioUnidadDisplay.textContent = precioUnidad.toFixed(decimales) + '€/ud';
                    
                    // Actualizar el precio original guardado
                    input.dataset.precioOriginal = precioTotal.toFixed(2);
                    
                    // Mostrar mensaje de éxito
                    const button = input.nextElementSibling.nextElementSibling; // El botón Guardar
                    const originalText = button.textContent;
                    button.textContent = '✓ Guardado';
                    button.classList.add('bg-green-500');
                    setTimeout(() => {
                        button.textContent = originalText;
                        button.classList.remove('bg-green-500');
                    }, 2000);
                } else {
                    alert('Error al guardar el precio: ' + (data.error || 'Error desconocido'));
                    input.value = input.dataset.precioOriginal;
                }
            } catch (error) {
                console.error('Error al guardar precio:', error);
                alert('Error de conexión al guardar el precio');
                input.value = input.dataset.precioOriginal;
            }
        }

        // Función para configurar tooltips con click (similar a formulario.blade.php)
        function configurarTooltipsAvisos() {
            const tooltipButtons = document.querySelectorAll('.tooltip-btn-avisos');
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

        // Inicializar tooltips al cargar la página
        document.addEventListener('DOMContentLoaded', function() {
            configurarTooltipsAvisos();
        });

        // Función para toggle mostrar todos los avisos (solo usuario ID 1)
        function toggleMostrarTodosAvisos(checked) {
            fetch('{{ route("admin.avisos.toggle.mostrar.todos") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    mostrar_todos: checked
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Recargar la página para mostrar los cambios
                    location.reload();
                } else {
                    alert('Error: ' + (data.message || 'Error desconocido'));
                    // Revertir el checkbox si hay error
                    document.getElementById('mostrar-todos-avisos').checked = !checked;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error de conexión al cambiar el filtro');
                // Revertir el checkbox si hay error
                document.getElementById('mostrar-todos-avisos').checked = !checked;
            });
        }
    </script>
</x-app-layout>

