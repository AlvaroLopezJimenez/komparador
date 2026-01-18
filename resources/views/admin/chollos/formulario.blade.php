@php
    $esEdicion = (bool) $chollo;
    
    // Manejar valores cuando hay errores de validación
    $productoIdOld = old('producto_id');
    $productoNombreOld = old('producto_nombre');
    $tiendaIdOld = old('tienda_id');
    $tiendaNombreOld = old('tienda_nombre');
    
    // Si hay valores old(), usarlos; si no, usar los valores del modelo
    if ($productoIdOld && $productoNombreOld) {
        // Crear objeto temporal para mantener compatibilidad
        $productoSeleccionado = (object) [
            'id' => $productoIdOld,
            'nombre' => explode(' - ', $productoNombreOld)[0] ?? '',
            'marca' => explode(' - ', $productoNombreOld)[1] ?? '',
            'modelo' => explode(' - ', $productoNombreOld)[2] ?? '',
            'talla' => explode(' - ', $productoNombreOld)[3] ?? '',
        ];
    } else {
        $productoSeleccionado = $productoSeleccionado ?? $chollo?->producto;
    }
    
    if ($tiendaIdOld && $tiendaNombreOld) {
        // Crear objeto temporal para mantener compatibilidad
        $tiendaSeleccionada = (object) [
            'id' => $tiendaIdOld,
            'nombre' => $tiendaNombreOld,
        ];
    } else {
        $tiendaSeleccionada = $tiendaSeleccionada ?? $chollo?->tienda;
    }
    
    $categoriaSeleccionada = $categoriaSeleccionada ?? $chollo?->categoria;
    $comprobadaValue = old('comprobada');
    if (!$comprobadaValue) {
        $comprobadaValue = optional($chollo?->comprobada)->format('Y-m-d\TH:i') ?? now()->format('Y-m-d\TH:i');
    }
    $frecuenciaValorOld = old('frecuencia_valor');
    $frecuenciaUnidadOld = old('frecuencia_unidad');
    $frecuenciaMinutosBase = optional($chollo)->frecuencia_comprobacion_min ?? 1440;
    if ($frecuenciaMinutosBase <= 0) {
        $frecuenciaMinutosBase = 1440;
    }
    if ($frecuenciaMinutosBase % 1440 === 0) {
        $frecuenciaValorDefault = $frecuenciaMinutosBase / 1440;
        $frecuenciaUnidadDefault = 'dias';
    } elseif ($frecuenciaMinutosBase % 60 === 0) {
        $frecuenciaValorDefault = $frecuenciaMinutosBase / 60;
        $frecuenciaUnidadDefault = 'horas';
    } else {
        $frecuenciaValorDefault = $frecuenciaMinutosBase;
        $frecuenciaUnidadDefault = 'minutos';
    }
    $frecuenciaValor = $frecuenciaValorOld !== null ? $frecuenciaValorOld : $frecuenciaValorDefault;
    $frecuenciaUnidad = $frecuenciaUnidadOld !== null ? $frecuenciaUnidadOld : $frecuenciaUnidadDefault;

    $fechaInicioValue = old('fecha_inicio', optional(optional($chollo)->fecha_inicio)->format('Y-m-d\TH:i') ?? now()->format('Y-m-d\TH:i'));
    $fechaFinalValue = old('fecha_final', optional(optional($chollo)->fecha_final)->format('Y-m-d\TH:i'));

    $ofertasTemporalesOld = old('ofertas_temporales');
    $ofertasTemporalesIniciales = [];
    if ($ofertasTemporalesOld) {
        $decodedTemporales = json_decode($ofertasTemporalesOld, true);
        if (is_array($decodedTemporales)) {
            $ofertasTemporalesIniciales = $decodedTemporales;
        }
    }

    $tipoSeleccionado = old('tipo', optional($chollo)->tipo ?? 'producto');
    $descripcionInternaDefaultCupones = [
        [
            'descuento' => 2,
            'sobrePrecioTotal' => 30,
            'cupon' => 'ESF1000',
        ],
        [
            'descuento' => 5,
            'sobrePrecioTotal' => 50,
            'cupon' => 'ESF2000',
        ],
        [
            'descuento' => 10,
            'sobrePrecioTotal' => 100,
            'cupon' => 'ESF3000',
        ],
    ];
    $descripcionInternaDefaultData = ['cupones' => $descripcionInternaDefaultCupones];
    $descripcionInternaDefaultValor = json_encode($descripcionInternaDefaultData, JSON_UNESCAPED_UNICODE);
    $descripcionInternaValor = old(
        'descripcion_interna',
        optional($chollo)->descripcion_interna ?? ''
    );
    
    // Determinar si hay cupones guardados y el tipo
    $tieneCupones = false;
    $esSoloCupon = false;
    if (!blank($descripcionInternaValor)) {
        try {
            $decoded = json_decode($descripcionInternaValor, true);
            if (is_array($decoded) && isset($decoded['cupones']) && is_array($decoded['cupones']) && count($decoded['cupones']) > 0) {
                $tieneCupones = true;
                // Verificar si es el tipo "1_solo_cupon" (tiene campo tipo_descuento)
                if (isset($decoded['tipo']) && $decoded['tipo'] === '1_solo_cupon') {
                    $esSoloCupon = true;
                }
            }
        } catch (\Exception $e) {
            $tieneCupones = false;
        }
    }
    
    // Por defecto, si no hay cupones y es un nuevo chollo sin old(), no establecer valor por defecto
    // Esto permitirá que el selector esté en "Sin configuración" por defecto
    // Si el usuario selecciona "Cupones", entonces se usarán los cupones por defecto en JavaScript
    $sinCategoriaOld = old('sin_categoria');
    if ($sinCategoriaOld !== null) {
        $sinCategoriaSeleccionado = (bool) $sinCategoriaOld;
    } elseif ($esEdicion) {
        $sinCategoriaSeleccionado = optional($chollo)->categoria_id === null;
    } else {
        $sinCategoriaSeleccionado = false;
    }
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.dashboard') }}">
                <h2 class="font-semibold text-xl text-white leading-tight">Inicio -></h2>
            </a>
            <a href="{{ route('admin.chollos.index') }}">
                <h2 class="font-semibold text-xl text-white leading-tight">Chollos -></h2>
            </a>
            <h2 class="font-semibold text-xl text-white leading-tight">
                {{ $esEdicion ? 'Editar chollo' : 'Añadir chollo' }}
            </h2>
        </div>
    </x-slot>

    <div class="max-w-5xl mx-auto py-10 px-4 space-y-8 bg-gray-50 dark:bg-gray-900 rounded-lg shadow-md">
        <form
            method="POST"
            action="{{ $esEdicion ? route('admin.chollos.update', $chollo) : route('admin.chollos.store') }}"
            id="form-chollo"
            data-chollo-id="{{ optional($chollo)->id ?? 'null' }}"
            data-es-edicion="{{ $esEdicion ? '1' : '0' }}"
        >
            @csrf
            @if ($esEdicion)
                @method('PUT')
            @endif

            @if ($errors->any())
                <div class="bg-red-100 border border-red-300 text-red-700 dark:bg-red-900/30 dark:text-red-200 dark:border-red-700 px-4 py-3 rounded-lg mb-6">
                    <div class="flex items-start gap-3">
                        <svg class="w-5 h-5 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                        </svg>
                        <div>
                            <h3 class="font-semibold mb-1">Por favor revisa los siguientes campos antes de guardar:</h3>
                            <ul class="list-disc list-inside space-y-1 text-sm">
                                @php
                                    $nombresCampos = [
                                        'producto_id' => 'Producto',
                                        'tienda_id' => 'Tienda',
                                        'categoria_id' => 'Categoría',
                                        'tipo' => 'Tipo',
                                        'titulo' => 'Título',
                                        'slug' => 'Slug',
                                        'precio_nuevo' => 'Precio nuevo',
                                        'precio_unidad' => 'Precio por unidad',
                                        'url' => 'URL',
                                        'descripcion_interna' => 'Descripción interna (cupones)',
                                        'imagen_grande' => 'Imagen grande',
                                        'frecuencia_valor' => 'Frecuencia de comprobación (valor)',
                                        'frecuencia_unidad' => 'Frecuencia de comprobación (unidad)',
                                        'finalizada' => 'Finalizada',
                                        'mostrar' => 'Mostrar en web',
                                    ];
                                @endphp
                                @foreach ($errors->keys() as $key)
                                    @php
                                        $nombreCampo = $nombresCampos[$key] ?? ucfirst(str_replace('_', ' ', $key));
                                        $mensajes = $errors->get($key);
                                    @endphp
                                    @foreach ($mensajes as $mensaje)
                                        <li><strong>{{ $nombreCampo }}:</strong> {{ $mensaje }}</li>
                                    @endforeach
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            @endif

            {{--CATEGORIA, SUBCATEGORIA Y SUBSUBCATEGORIA --}}
            <fieldset class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6 space-y-6 border border-gray-200 dark:border-gray-700">
                <legend class="text-lg font-semibold text-gray-700 dark:text-gray-200">Categoría</legend>

                <div id="categorias-container" class="space-y-4">
                    <!-- Los selectores de categorías se generarán dinámicamente aquí -->
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <div class="flex gap-2">
                        <button type="button" id="agregar-categoria" class="bg-green-600 hover:bg-green-700 text-white font-semibold px-4 py-2 rounded-md transition duration-150 ease-in-out focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                            + Agregar nivel de categoría
                        </button>
                        <button type="button" id="limpiar-categorias" class="bg-gray-600 hover:bg-gray-700 text-white font-semibold px-4 py-2 rounded-md transition duration-150 ease-in-out focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                            Limpiar categorías
                        </button>
                    </div>
                    <div class="inline-flex items-center gap-2 ml-auto">
                        <input type="hidden" name="sin_categoria" value="0">
                        <input
                            type="checkbox"
                            name="sin_categoria"
                            id="sin_categoria"
                            value="1"
                            class="rounded border-gray-300 text-pink-600 focus:ring-pink-500"
                            {{ $sinCategoriaSeleccionado ? 'checked' : '' }}
                        >
                        <label for="sin_categoria" class="text-sm font-medium text-gray-700 dark:text-gray-200">
                            Sin categoría
                        </label>
                    </div>
                </div>

                <!-- Campo oculto para la categoría final -->
                <input type="hidden" name="categoria_id" id="categoria-final" value="{{ old('categoria_id', $categoriaSeleccionada->id ?? '') }}">
                @error('categoria_id')
                    <p class="text-sm text-red-500 mt-2">{{ $message }}</p>
                @enderror
            </fieldset>

            {{-- INFORMACIÓN GENERAL --}}
            <fieldset class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6 space-y-6 border border-gray-200 dark:border-gray-700">
                <legend class="text-lg font-semibold text-gray-700 dark:text-gray-200">Información general</legend>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block mb-1 font-medium text-gray-700 dark:text-gray-200">Tipo *</label>
                        <select
                            name="tipo"
                            id="tipo"
                            class="w-full px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border @error('tipo') border-red-500 @enderror"
                        >
                            <option value="producto" {{ $tipoSeleccionado === 'producto' ? 'selected' : '' }}>Producto-ofertas</option>
                            <option value="oferta" {{ $tipoSeleccionado === 'oferta' ? 'selected' : '' }}>Oferta</option>
                            <option value="tienda" {{ $tipoSeleccionado === 'tienda' ? 'selected' : '' }}>Tienda</option>
                        </select>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            Elige si el chollo está asociado a un producto concreto o es un chollo general de tienda.
                        </p>
                        @error('tipo')
                            <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Producto --}}
                    <div id="campo-producto" class="{{ $tipoSeleccionado === 'tienda' ? 'hidden' : '' }}">
                        <label class="block mb-1 font-medium text-gray-700 dark:text-gray-200">Producto *</label>
                        <div class="relative">
                            <input type="hidden" name="producto_id" id="producto_id" value="{{ old('producto_id', $productoSeleccionado->id ?? '') }}">
                            <input type="hidden" name="producto_nombre" id="producto_nombre_hidden" value="{{ old('producto_nombre', $productoSeleccionado ? $productoSeleccionado->nombre . ' - ' . $productoSeleccionado->marca . ' - ' . $productoSeleccionado->modelo . ' - ' . $productoSeleccionado->talla : '') }}">
                            @php
                                $productoNombreValue = old('producto_nombre');
                                if (!$productoNombreValue && $productoSeleccionado) {
                                    $productoNombreValue = $productoSeleccionado->nombre . ' - ' . $productoSeleccionado->marca . ' - ' . $productoSeleccionado->modelo . ' - ' . $productoSeleccionado->talla;
                                }
                            @endphp
                            <input
                                type="text"
                                id="producto_nombre"
                                value="{{ $productoNombreValue ?? '' }}"
                                class="w-full px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border {{ $errors->has('producto_id') ? 'border-red-500 focus:ring-red-500 focus:border-red-500' : ($productoSeleccionado ? 'border-green-500' : 'border-gray-300') }} focus:outline-none focus:ring-2 focus:ring-blue-500 {{ $tipoSeleccionado === 'tienda' ? 'opacity-60 cursor-not-allowed' : '' }}"
                                placeholder="Escribe para buscar productos..."
                                autocomplete="off"
                                {{ $tipoSeleccionado === 'tienda' ? 'disabled' : '' }}
                            >
                            <div id="producto_sugerencias" class="absolute z-50 w-full mt-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md shadow-lg hidden max-h-60 overflow-y-auto"></div>
                        </div>
                        @error('producto_id')
                            <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div id="mensaje-tipo-tienda" class="md:col-span-2 {{ $tipoSeleccionado === 'tienda' ? '' : 'hidden' }}">
                        <div class="p-4 rounded-lg border border-blue-200 dark:border-blue-700 bg-blue-50/60 dark:bg-blue-900/30 text-sm text-blue-700 dark:text-blue-200">
                            Este chollo está marcado como <strong class="font-semibold">de tienda</strong>, por lo que no requiere asociar un producto concreto.
                        </div>
                    </div>

                    {{-- Tienda --}}
                    <div>
                        <label class="block mb-1 font-medium text-gray-700 dark:text-gray-200">Tienda *</label>
                        <div class="relative">
                            <input type="hidden" name="tienda_id" id="tienda_id" value="{{ old('tienda_id', $tiendaSeleccionada->id ?? '') }}" required>
                            <input type="hidden" name="tienda_nombre" id="tienda_nombre_hidden" value="{{ old('tienda_nombre', $tiendaSeleccionada->nombre ?? '') }}">
                            @php
                                $tiendaNombreValue = old('tienda_nombre');
                                if (!$tiendaNombreValue && $tiendaSeleccionada) {
                                    $tiendaNombreValue = $tiendaSeleccionada->nombre ?? '';
                                }
                            @endphp
                            <input
                                type="text"
                                id="tienda_nombre"
                                value="{{ $tiendaNombreValue ?? '' }}"
                                class="w-full px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border {{ $errors->has('tienda_id') ? 'border-red-500 focus:ring-red-500 focus:border-red-500' : ($tiendaSeleccionada ? 'border-green-500' : 'border-gray-300') }} focus:outline-none focus:ring-2 focus:ring-blue-500"
                                placeholder="Escribe para buscar tiendas..."
                                autocomplete="off"
                            >
                            <div id="tienda_sugerencias" class="absolute z-50 w-full mt-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md shadow-lg hidden max-h-60 overflow-y-auto"></div>
                        </div>
                        @error('tienda_id')
                            <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Título --}}
                    <div class="md:col-span-2">
                        <label class="block mb-1 font-medium text-gray-700 dark:text-gray-200">Título *</label>
                        <input
                            type="text"
                            name="titulo"
                            value="{{ old('titulo', optional($chollo)->titulo ?? '') }}"
                            class="w-full px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border @error('titulo') border-red-500 @enderror"
                            required
                        >
                        @error('titulo')
                            <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="md:col-span-2">
                        <label class="block mb-1 font-medium text-gray-700 dark:text-gray-200">Slug *</label>
                        <input
                            type="text"
                            name="slug"
                            id="slug"
                            value="{{ old('slug', optional($chollo)->slug ?? '') }}"
                            class="w-full px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border @error('slug') border-red-500 @enderror"
                            required
                        >
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            Se generará automáticamente a partir del título la primera vez, pero puedes modificarlo si lo necesitas.
                        </p>
                        @error('slug')
                            <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </fieldset>

            {{-- DESCRIPCIÓN INTERNA --}}
            <fieldset
                id="seccion-descripcion-interna"
                class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6 space-y-4 border border-gray-200 dark:border-gray-700 {{ $tipoSeleccionado === 'tienda' ? '' : 'hidden' }}"
            >
                <legend class="text-lg font-semibold text-gray-700 dark:text-gray-200">Descripción interna *</legend>
                <div class="space-y-4">
                    <div>
                        <label class="block mb-1 font-medium text-gray-700 dark:text-gray-200">Tipo de configuración</label>
                        <select
                            id="descripcion_interna_selector"
                            class="w-full px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border"
                        >
                            <option value="sin_configuracion" {{ !$tieneCupones ? 'selected' : '' }}>Sin configuración</option>
                            <option value="cupon" {{ $tieneCupones && !$esSoloCupon ? 'selected' : '' }}>Cupones</option>
                            <option value="1_solo_cupon" {{ $esSoloCupon ? 'selected' : '' }}>1 Solo cupón</option>
                        </select>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            Selecciona "Sin configuración" para no guardar cupones, "Cupones" para gestionar múltiples cupones, o "1 Solo cupón" para aplicar un único descuento al precio total.
                        </p>
                        <div id="aviso-solo-cupon" class="hidden mt-2 p-3 rounded-lg border border-blue-200 dark:border-blue-700 bg-blue-50/60 dark:bg-blue-900/30 text-sm text-blue-700 dark:text-blue-200">
                            <strong>Nota:</strong> Esta opción aplicará el descuento del cupón al precio total al mostrarlo en el comparador.
                        </div>
                    </div>

                    <div class="space-y-3 {{ !$tieneCupones ? 'hidden' : '' }}" id="descripcion-interna-cupones"></div>

                    <div id="btn-agregar-cupon-container" class="{{ !$tieneCupones ? 'hidden' : '' }}">
                        <button
                            type="button"
                            id="btn-agregar-cupon"
                            class="inline-flex items-center px-4 py-2 rounded-md bg-green-600 hover:bg-green-700 text-white font-semibold"
                        >
                            Añadir cupón
                        </button>
                    </div>
                </div>

                <input type="hidden" name="descripcion_interna" id="descripcion_interna" value="">
                <input type="hidden" name="descripcion_interna_sin_config" id="descripcion_interna_sin_config" value="0">

                <p class="text-xs text-gray-500 dark:text-gray-400">
                    Los cupones configurados se guardarán como parte de la descripción interna del chollo y se usarán en los procesos automáticos.
                </p>
                @error('descripcion_interna')
                    <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                @enderror
            </fieldset>

            {{-- IMÁGENES --}}
            <fieldset class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6 space-y-4 border border-gray-200 dark:border-gray-700">
                <legend class="text-lg font-semibold text-gray-700 dark:text-gray-200">Imágenes</legend>

                <!-- Pestañas de gestión de imágenes -->
                <div class="border-b border-gray-200 dark:border-gray-600">
                    <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                        <button type="button" id="tab-upload" class="tab-button border-b-2 border-blue-500 py-2 px-1 text-sm font-medium text-blue-600 dark:text-blue-400" aria-current="page">
                            Subir imagen
                        </button>
                        <button type="button" id="tab-manual" class="tab-button border-b-2 border-transparent py-2 px-1 text-sm font-medium text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300">
                            Nombre manual
                        </button>
                        <button type="button" id="tab-url" class="tab-button border-b-2 border-transparent py-2 px-1 text-sm font-medium text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300">
                            Descargar desde URL
                        </button>
                    </nav>
                </div>

                <!-- Contenido de la pestaña de subida -->
                <div id="content-upload" class="tab-content space-y-4">
                    <!-- Imagen Grande -->
                    <div>
                        <label class="block mb-2 font-medium text-gray-700 dark:text-gray-200">Imagen Grande *</label>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <!-- Selector de carpeta -->
                            <div>
                                <label class="block mb-1 text-sm text-gray-600 dark:text-gray-400">Carpeta</label>
                                <div class="flex gap-2">
                                    <select id="carpeta-imagen-grande" class="flex-1 px-3 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border text-sm">
                                        <option value="">Selecciona una carpeta</option>
                                        <option value="panales">panales</option>
                                        <option value="categorias">categorias</option>
                                        <option value="tiendas">tiendas</option>
                                    </select>
                                    <button type="button" id="btn-ver-imagenes-grande" class="bg-gray-500 hover:bg-gray-600 text-white px-3 py-2 rounded text-sm">
                                        Ver
                                    </button>
                                </div>
                                
                                <!-- Información de imagen actual -->
                                @php
                                    $imgGrandeActual = old('imagen_grande', $chollo->imagen_grande ?? '');
                                @endphp
                                @if($imgGrandeActual)
                                <div class="mt-2 p-2 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-700 rounded text-xs">
                                    <div class="text-green-700 dark:text-green-300 font-medium">✓ Imagen actual:</div>
                                    <div class="text-green-600 dark:text-green-400 break-words">{{ $imgGrandeActual }}</div>
                                    
                                    <!-- Vista previa de imagen actual -->
                                    <div class="mt-2 flex items-center gap-2">
                                        <img id="preview-upload-grande" src="{{ asset('images/' . $imgGrandeActual) }}" alt="Vista previa" class="h-12 w-12 object-contain border rounded">
                                        <button type="button" id="btn-limpiar-grande" class="text-xs text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300">
                                            Limpiar
                                        </button>
                                    </div>
                                </div>
                                @else
                                <!-- Vista previa vacía (oculta por defecto) -->
                                <div class="mt-2 flex items-center gap-2 hidden" id="preview-container-grande">
                                    <img id="preview-upload-grande" src="" alt="Vista previa" class="h-12 w-12 object-contain border rounded">
                                    <button type="button" id="btn-limpiar-grande" class="text-xs text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300">
                                        Limpiar
                                    </button>
                                </div>
                                @endif
                            </div>
                            
                            <!-- Área de upload -->
                            <div class="md:col-span-2">
                                <label class="block mb-1 text-sm text-gray-600 dark:text-gray-400">Seleccionar imagen</label>
                                <div class="flex gap-2">
                                    <input type="file" id="file-imagen-grande" accept="image/*" class="hidden">
                                    <button type="button" id="btn-seleccionar-grande" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm">
                                        Seleccionar archivo
                                    </button>
                                    <span id="nombre-archivo-grande" class="text-sm text-gray-500 dark:text-gray-400 self-center"></span>
                                </div>
                                
                                <!-- Área de drag & drop -->
                                <div id="drop-zone-grande" class="mt-2 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-4 text-center hover:border-blue-400 dark:hover:border-blue-500 transition-colors">
                                    <p class="text-sm text-gray-600 dark:text-gray-400">
                                        Arrastra y suelta una imagen aquí o haz clic para seleccionarla.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <input type="hidden" id="ruta-imagen-grande" name="imagen_grande" value="{{ old('imagen_grande', $chollo->imagen_grande ?? '') }}">
                    <span id="ruta-completa-grande" class="text-sm text-gray-600 dark:text-gray-400 hidden"></span>
                    @error('imagen_grande')
                        <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                    @enderror

                    <!-- Imagen Pequeña -->
                    <div>
                        <label class="block mb-2 font-medium text-gray-700 dark:text-gray-200">Imagen Pequeña</label>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block mb-1 text-sm text-gray-600 dark:text-gray-400">Carpeta</label>
                                <div class="flex gap-2">
                                    <select id="carpeta-imagen-pequena" class="flex-1 px-3 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border text-sm">
                                        <option value="">Selecciona una carpeta</option>
                                        <option value="panales">panales</option>
                                        <option value="categorias">categorias</option>
                                        <option value="tiendas">tiendas</option>
                                    </select>
                                    <button type="button" id="btn-ver-imagenes-pequena" class="bg-gray-500 hover:bg-gray-600 text-white px-3 py-2 rounded text-sm">
                                        Ver
                                    </button>
                                </div>

                                @php
                                    $imgPequenaActual = old('imagen_pequena', $chollo->imagen_pequena ?? '');
                                @endphp
                                @if($imgPequenaActual)
                                <div class="mt-2 p-2 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-700 rounded text-xs">
                                    <div class="text-green-700 dark:text-green-300 font-medium">✓ Imagen actual:</div>
                                    <div class="text-green-600 dark:text-green-400 break-words">{{ $imgPequenaActual }}</div>
                                    <div class="mt-2 flex items-center gap-2">
                                        <img id="preview-upload-pequena" src="{{ asset('images/' . $imgPequenaActual) }}" alt="Vista previa" class="h-12 w-12 object-contain border rounded">
                                        <button type="button" id="btn-limpiar-pequena" class="text-xs text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300">
                                            Limpiar
                                        </button>
                                    </div>
                                </div>
                                @else
                                <div class="mt-2 flex items-center gap-2 hidden" id="preview-container-pequena">
                                    <img id="preview-upload-pequena" src="" alt="Vista previa" class="h-12 w-12 object-contain border rounded">
                                    <button type="button" id="btn-limpiar-pequena" class="text-xs text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300">
                                        Limpiar
                                    </button>
                                </div>
                                @endif
                            </div>

                            <div class="md:col-span-2">
                                <label class="block mb-1 text-sm text-gray-600 dark:text-gray-400">Seleccionar imagen</label>
                                <div class="flex gap-2">
                                    <input type="file" id="file-imagen-pequena" accept="image/*" class="hidden">
                                    <button type="button" id="btn-seleccionar-pequena" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm">
                                        Seleccionar archivo
                                    </button>
                                    <span id="nombre-archivo-pequena" class="text-sm text-gray-500 dark:text-gray-400 self-center"></span>
                                </div>

                                <div id="drop-zone-pequena" class="mt-2 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-4 text-center hover:border-blue-400 dark:hover:border-blue-500 transition-colors">
                                    <p class="text-sm text-gray-600 dark:text-gray-400">
                                        Arrastra y suelta una imagen aquí o haz clic para seleccionarla.
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <input type="hidden" id="ruta-imagen-pequena" name="imagen_pequena" value="{{ old('imagen_pequena', $chollo->imagen_pequena ?? '') }}">
                        <span id="ruta-completa-pequena" class="text-sm text-gray-600 dark:text-gray-400 hidden"></span>
                        @error('imagen_pequena')
                            <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Contenido de la pestaña manual -->
                <div id="content-manual" class="tab-content space-y-4 hidden">
                    <div>
                        <label class="block mb-1 font-medium text-gray-700 dark:text-gray-200">Nombre de imagen grande *</label>
                        <div class="flex gap-2 items-center">
                            <input type="text"
                                id="imagen-grande-input"
                                name="imagen_grande_manual"
                                value="{{ old('imagen_grande', $chollo->imagen_grande ?? '') }}"
                                class="w-full px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border @error('imagen_grande') border-red-500 @enderror">
                            <button type="button" id="buscar-imagen-grande" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-3 h-full rounded">Buscar imagen</button>
                            @php
                                $imgGrande = old('imagen_grande', $chollo->imagen_grande ?? '');
                                $imgGrandeSrc = $imgGrande ? asset('images/' . $imgGrande) : '';
                                $imgGrandeDisplay = $imgGrande ? 'block' : 'none';
                            @endphp
                            <img id="preview-imagen-grande" src="{{ $imgGrandeSrc }}" alt="Vista previa" class="h-12 w-12 object-contain ml-2 border rounded" style="display: {{ $imgGrandeDisplay }};">
                        </div>
                        @error('imagen_grande')
                            <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="block mb-1 font-medium text-gray-700 dark:text-gray-200">Nombre de imagen pequeña *</label>
                        <div class="flex gap-2 items-center">
                            <input type="text"
                                id="imagen-pequena-input"
                                name="imagen_pequena_manual"
                                value="{{ old('imagen_pequena', $chollo->imagen_pequena ?? '') }}"
                                class="w-full px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border @error('imagen_pequena') border-red-500 @enderror">
                            <button type="button" id="buscar-imagen-pequena" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-3 h-full rounded">Buscar imagen</button>
                            @php
                                $imgPequena = old('imagen_pequena', $chollo->imagen_pequena ?? '');
                                $imgPequenaSrc = $imgPequena ? asset('images/' . $imgPequena) : '';
                                $imgPequenaDisplay = $imgPequena ? 'block' : 'none';
                            @endphp
                            <img id="preview-imagen-pequena" src="{{ $imgPequenaSrc }}" alt="Vista previa" class="h-12 w-12 object-contain ml-2 border rounded" style="display: {{ $imgPequenaDisplay }};">
                        </div>
                        @error('imagen_pequena')
                            <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Contenido de la pestaña de descarga desde URL (oculto por defecto) -->
                <div id="content-url" class="tab-content space-y-4 hidden">
                    <div>
                        <label class="block mb-2 font-medium text-gray-700 dark:text-gray-200">Descargar imagen desde URL *</label>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Se generarán automáticamente ambas versiones (grande 300x250 y pequeña 144x120) con el mismo recorte. El nombre incluirá un número aleatorio de 6 dígitos.</p>
                        
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <!-- Selector de carpeta -->
                            <div>
                                <label class="block mb-1 text-sm text-gray-600 dark:text-gray-400">Carpeta</label>
                                <select id="carpeta-imagen-url" class="w-full px-3 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border text-sm">
                                    <option value="">Selecciona una carpeta</option>
                                    <option value="panales">panales</option>
                                    <option value="categorias">categorias</option>
                                    <option value="tiendas">tiendas</option>
                                </select>
                            </div>
                            
                            <!-- URL y botón -->
                            <div class="md:col-span-2">
                                <label class="block mb-1 text-sm text-gray-600 dark:text-gray-400">URL de la imagen</label>
                                <div class="flex gap-2">
                                    <input type="url" id="url-imagen" 
                                        class="flex-1 px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border text-sm"
                                        placeholder="https://ejemplo.com/imagen.jpg">
                                    <button type="button" id="btn-descargar-url" 
                                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm disabled:bg-gray-400 disabled:cursor-not-allowed">
                                        Descargar
                                    </button>
                                </div>
                                <div id="error-url" class="mt-1 text-sm text-red-500 hidden"></div>
                                
                                <!-- Resultados de las rutas -->
                                <div id="ruta-resultado-grande" class="mt-2 text-sm text-gray-600 dark:text-gray-400 hidden">
                                    <div class="flex items-center gap-2">
                                        <span class="font-medium">Imagen grande:</span>
                                        <span id="ruta-texto-grande" class="font-medium"></span>
                                        <span id="estado-proceso-grande" class="ml-2"></span>
                                    </div>
                                </div>
                                <div id="ruta-resultado-pequena" class="mt-2 text-sm text-gray-600 dark:text-gray-400 hidden">
                                    <div class="flex items-center gap-2">
                                        <span class="font-medium">Imagen pequeña:</span>
                                        <span id="ruta-texto-pequena" class="font-medium"></span>
                                        <span id="estado-proceso-pequena" class="ml-2"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Nota: Los campos ocultos se actualizarán automáticamente en los campos principales -->
                    </div>
                </div>
            </fieldset>

            {{-- PRECIOS --}}
            <fieldset class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6 space-y-6 border border-gray-200 dark:border-gray-700">
                <legend class="text-lg font-semibold text-gray-700 dark:text-gray-200">Precios</legend>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                    <div>
                        <label class="block mb-1 font-medium text-gray-700 dark:text-gray-200">Precio antiguo (€)</label>
                        <input
                            type="number"
                            step="0.01"
                            min="0"
                            name="precio_antiguo"
                            value="{{ old('precio_antiguo', optional($chollo)->precio_antiguo ?? '') }}"
                            class="w-full px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border @error('precio_antiguo') border-red-500 @enderror"
                        >
                        @error('precio_antiguo')
                            <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div>
                        <label class="block mb-1 font-medium text-gray-700 dark:text-gray-200">Unidades</label>
                        <input
                            type="number"
                            step="0.01"
                            min="0"
                            name="unidades"
                            id="unidades"
                            value="{{ old('unidades', optional($chollo)->unidades ?? '') }}"
                            class="w-full px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border @error('unidades') border-red-500 @enderror"
                        >
                        @error('unidades')
                            <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block mb-1 font-medium text-gray-700 dark:text-gray-200">Precio nuevo *</label>
                        <input
                            type="text"
                            name="precio_nuevo"
                            id="precio_nuevo"
                            value="{{ old('precio_nuevo', optional($chollo)->precio_nuevo ?? '') }}"
                            class="w-full px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border @error('precio_nuevo') border-red-500 @enderror"
                            placeholder="Ej: 9.99 o 9.99 - 12.99"
                            @if($tipoSeleccionado !== 'tienda') required @endif
                        >
                        @error('precio_nuevo')
                            <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block mb-1 font-medium text-gray-700 dark:text-gray-200">Precio por unidad (€)</label>
                        <input
                            type="number"
                            step="0.0001"
                            min="0"
                            name="precio_unidad"
                            id="precio_unidad"
                            value="{{ old('precio_unidad', optional($chollo)->precio_unidad ?? '') }}"
                            class="w-full px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border @error('precio_unidad') border-red-500 @enderror"
                        >
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            Se recalculará automáticamente cuando cambien las unidades o el precio nuevo.
                        </p>
                        <p id="mensaje-calculo-precio-unidad" class="text-xs text-blue-500 dark:text-blue-400 mt-1 hidden">
                            El precio por unidad se está calculando como precio total / unidades (no hay producto asociado).
                        </p>
                        @error('precio_unidad')
                            <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block mb-1 font-medium text-gray-700 dark:text-gray-200">Gastos de envío</label>
                        <input
                            type="text"
                            name="gastos_envio"
                            value="{{ old('gastos_envio', optional($chollo)->gastos_envio ?? '') }}"
                            class="w-full px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border @error('gastos_envio') border-red-500 @enderror"
                            placeholder="Ej: Gratis, 3.95€, etc."
                        >
                        @error('gastos_envio')
                            <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block mb-1 font-medium text-gray-700 dark:text-gray-200">Descuentos</label>
                        @php
                            $descuentoActual = old('descuentos', optional($chollo)->descuentos ?? '');
                            $descuentoParaSelect = $descuentoActual;
                            $codigoCupon = '';
                            $cantidadCupon = '';

                            if ($descuentoActual && str_starts_with($descuentoActual, 'cupon;')) {
                                $partes = explode(';', $descuentoActual);
                                $descuentoParaSelect = 'cupon';
                                if (count($partes) === 3) {
                                    $codigoCupon = $partes[1];
                                    $cantidadCupon = $partes[2];
                                } elseif (count($partes) === 2) {
                                    $cantidadCupon = $partes[1];
                                }
                            }
                        @endphp

                        <div class="flex gap-2 items-center">
                            <select
                                name="descuentos"
                                id="select_descuentos"
                                class="flex-1 px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border @error('descuentos') border-red-500 @enderror"
                            >
                                <option value="">Sin descuento</option>
                                <option value="cupon" {{ $descuentoParaSelect === 'cupon' ? 'selected' : '' }}>Cupón</option>
                                <option value="3x2" {{ $descuentoParaSelect === '3x2' ? 'selected' : '' }}>3x2</option>
                                <option value="2x1 - SoloCarrefour" {{ $descuentoParaSelect === '2x1 - SoloCarrefour' ? 'selected' : '' }}>2x1 - SoloCarrefour</option>
                                <option value="2a al 70" {{ $descuentoParaSelect === '2a al 70' ? 'selected' : '' }}>2ª al 70%</option>
                                <option value="2a al 50" {{ $descuentoParaSelect === '2a al 50' ? 'selected' : '' }}>2ª al 50%</option>
                                <option value="2a al 50 - cheque - SoloCarrefour" {{ $descuentoParaSelect === '2a al 50 - cheque - SoloCarrefour' ? 'selected' : '' }}>2ª al 50% - Cheque - SoloCarrefour</option>
                            </select>

                            <div id="cupon_campos_container" class="hidden flex gap-2">
                                <input
                                    type="text"
                                    id="cupon_codigo"
                                    name="cupon_codigo"
                                    value="{{ old('cupon_codigo', $codigoCupon) }}"
                                    placeholder="Código"
                                    class="px-3 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border"
                                >
                                <input
                                    type="number"
                                    id="cupon_cantidad"
                                    name="cupon_cantidad"
                                    value="{{ old('cupon_cantidad', $cantidadCupon) }}"
                                    step="0.01"
                                    min="0"
                                    placeholder="€"
                                    class="px-3 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border text-center"
                                >
                            </div>
                        </div>
                        @error('descuentos')
                            <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </fieldset>

            {{-- URL Y VISIBILIDAD --}}
            <fieldset class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6 space-y-6 border border-gray-200 dark:border-gray-700">
                <legend class="text-lg font-semibold text-gray-700 dark:text-gray-200">Publicación</legend>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="md:col-span-2">
                        <label class="block mb-1 font-medium text-gray-700 dark:text-gray-200">URL *</label>
                        <input
                            type="url"
                            name="url"
                            id="url_input"
                            value="{{ old('url', optional($chollo)->url ?? $url ?? '') }}"
                            class="w-full px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border @error('url') border-red-500 @enderror"
                            required
                        >
                        <div id="url_validation_message" class="mt-1 text-sm hidden"></div>
                        <div id="url_duplicate_checkbox" class="mt-2 hidden">
                            <label class="inline-flex items-center text-sm text-gray-700 dark:text-gray-200">
                                <input type="checkbox" id="url_duplicate_confirm" name="url_duplicate_confirm" value="1" class="mr-2 rounded border-gray-300 text-pink-600 focus:ring-pink-500" {{ old('url_duplicate_confirm') ? 'checked' : '' }}>
                                Confirmo que quiero usar esta URL a pesar de existir en otro chollo activo
                            </label>
                        </div>
                        <div id="url_other_elements" class="mt-2 hidden">
                            <div class="text-sm text-blue-600 dark:text-blue-400 mb-2">Chollos relacionados:</div>
                            <div id="url_elements_list" class="text-sm text-gray-600 dark:text-gray-400 space-y-1"></div>
                        </div>
                        @error('url')
                            <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block mb-1 font-medium text-gray-700 dark:text-gray-200">Finalizada *</label>
                        <div class="flex gap-6">
                            <label class="inline-flex items-center">
                                <input type="radio" name="finalizada" value="no" {{ old('finalizada', optional($chollo)->finalizada ?? 'no') === 'no' ? 'checked' : '' }} class="form-radio text-pink-600">
                                <span class="ml-2 text-gray-700 dark:text-gray-200">No</span>
                            </label>
                            <label class="inline-flex items-center">
                                <input type="radio" name="finalizada" value="si" {{ old('finalizada', optional($chollo)->finalizada ?? 'no') === 'si' ? 'checked' : '' }} class="form-radio text-pink-600">
                                <span class="ml-2 text-gray-700 dark:text-gray-200">Sí</span>
                            </label>
                        </div>
                        @error('finalizada')
                            <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block mb-1 font-medium text-gray-700 dark:text-gray-200">Mostrar en web *</label>
                        <div class="flex gap-6">
                            <label class="inline-flex items-center">
                                <input type="radio" name="mostrar" value="si" {{ old('mostrar', optional($chollo)->mostrar ?? 'si') === 'si' ? 'checked' : '' }} class="form-radio text-pink-600">
                                <span class="ml-2 text-gray-700 dark:text-gray-200">Sí</span>
                            </label>
                            <label class="inline-flex items-center">
                                <input type="radio" name="mostrar" value="no" {{ old('mostrar', optional($chollo)->mostrar ?? 'si') === 'no' ? 'checked' : '' }} class="form-radio text-pink-600">
                                <span class="ml-2 text-gray-700 dark:text-gray-200">No</span>
                            </label>
                        </div>
                        @error('mostrar')
                            <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block mb-1 font-medium text-gray-700 dark:text-gray-200">Fecha de inicio</label>
                        <input
                            type="datetime-local"
                            name="fecha_inicio"
                            id="fecha_inicio"
                            value="{{ $fechaInicioValue }}"
                            class="w-full px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border @error('fecha_inicio') border-red-500 @enderror"
                        >
                        @error('fecha_inicio')
                            <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block mb-1 font-medium text-gray-700 dark:text-gray-200 flex items-center gap-3">
                            Fecha de finalización
                            <button type="button" id="btn_fecha_final_ahora" class="px-2 py-1 text-xs bg-gray-600 hover:bg-gray-700 text-white rounded">
                                Ahora
                            </button>
                        </label>
                        <input
                            type="datetime-local"
                            name="fecha_final"
                            id="fecha_final"
                            value="{{ $fechaFinalValue }}"
                            class="w-full px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border @error('fecha_final') border-red-500 @enderror"
                        >
                        @error('fecha_final')
                            <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block mb-1 font-medium text-gray-700 dark:text-gray-200 flex items-center gap-3">
                            Comprobada
                            <button type="button" id="btn_comprobada_ahora" class="px-2 py-1 text-xs bg-gray-600 hover:bg-gray-700 text-white rounded">
                                Ahora
                            </button>
                        </label>
                        <input
                            type="datetime-local"
                            name="comprobada"
                            id="comprobada"
                            value="{{ $comprobadaValue }}"
                            class="w-full px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border @error('comprobada') border-red-500 @enderror"
                        >
                        @error('comprobada')
                            <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="md:col-span-2">
                        <label class="block mb-1 font-medium text-gray-700 dark:text-gray-200">Frecuencia de comprobación *</label>
                        <div class="flex flex-col sm:flex-row sm:items-center gap-2">
                            <input
                                type="number"
                                step="0.1"
                                min="0.1"
                                name="frecuencia_valor"
                                id="frecuencia_valor"
                                value="{{ old('frecuencia_valor', $frecuenciaValor) }}"
                                class="sm:w-32 px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border @error('frecuencia_valor') border-red-500 @enderror"
                            >
                            <select
                                name="frecuencia_unidad"
                                id="frecuencia_unidad"
                                class="sm:w-48 px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border @error('frecuencia_unidad') border-red-500 @enderror"
                            >
                                @php $unidadSeleccionada = old('frecuencia_unidad', $frecuenciaUnidad); @endphp
                                <option value="minutos" {{ $unidadSeleccionada === 'minutos' ? 'selected' : '' }}>Minutos</option>
                                <option value="horas" {{ $unidadSeleccionada === 'horas' ? 'selected' : '' }}>Horas</option>
                                <option value="dias" {{ $unidadSeleccionada === 'dias' ? 'selected' : '' }}>Días</option>
                            </select>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            El sistema almacenará esta frecuencia en minutos.
                        </p>
                        @error('frecuencia_valor')
                            <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                        @enderror
                        @error('frecuencia_unidad')
                            <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </fieldset>

            {{-- DESCRIPCIÓN --}}
            <fieldset class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6 space-y-4 border border-gray-200 dark:border-gray-700">
                <legend class="text-lg font-semibold text-gray-700 dark:text-gray-200">Descripción</legend>
                <textarea
                    name="descripcion"
                    rows="5"
                    class="w-full px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border @error('descripcion') border-red-500 @enderror"
                >{{ old('descripcion', optional($chollo)->descripcion ?? '') }}</textarea>
                @error('descripcion')
                    <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                @enderror
            </fieldset>

            {{-- SEO --}}
            <fieldset class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6 space-y-6 border border-gray-200 dark:border-gray-700">
                <legend class="text-lg font-semibold text-gray-700 dark:text-gray-200">SEO</legend>

                <div>
                    <label class="block mb-1 font-medium text-gray-700 dark:text-gray-200">Meta título</label>
                    <input
                        type="text"
                        name="meta_titulo"
                        value="{{ old('meta_titulo', optional($chollo)->meta_titulo ?? '') }}"
                        class="w-full px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border @error('meta_titulo') border-red-500 @enderror"
                    >
                    @error('meta_titulo')
                        <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block mb-1 font-medium text-gray-700 dark:text-gray-200">Meta descripción</label>
                    <textarea
                        name="meta_descripcion"
                        rows="3"
                        class="w-full px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border @error('meta_descripcion') border-red-500 @enderror"
                    >{{ old('meta_descripcion', optional($chollo)->meta_descripcion ?? '') }}</textarea>
                    @error('meta_descripcion')
                        <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </fieldset>

            {{-- OFERTAS VINCULADAS --}}
            <fieldset class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6 space-y-4 border border-gray-200 dark:border-gray-700">
                <legend class="text-lg font-semibold text-gray-700 dark:text-gray-200">Ofertas vinculadas al chollo</legend>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Añade ofertas especiales que se activarán junto a este chollo. Estas ofertas se guardarán automáticamente al guardar el chollo.
                </p>

                <div class="flex flex-wrap items-center gap-3 pb-4 border-b border-gray-200 dark:border-gray-700">
                    <button 
                        type="button" 
                        id="btn_aplicar_fechas_cupones" 
                        class="inline-flex items-center px-4 py-2 rounded-md bg-blue-600 hover:bg-blue-700 text-white font-semibold disabled:opacity-50 disabled:cursor-not-allowed"
                        disabled
                    >
                        Aplicar fechas y cupones del chollo
                    </button>
                    <div class="flex items-center gap-3">
                        <label class="inline-flex items-center">
                            <input type="radio" name="aplicar_mostrar" value="si" checked class="form-radio text-blue-600">
                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-200">Sí</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input type="radio" name="aplicar_mostrar" value="no" class="form-radio text-blue-600">
                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-200">No</span>
                        </label>
                    </div>
                    <div class="relative inline-block">
                        <button 
                            type="button" 
                            id="btn_info_aplicar_fechas_cupones"
                            class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-gray-400 hover:bg-gray-500 text-white text-sm font-semibold disabled:opacity-50 disabled:cursor-not-allowed"
                            disabled
                            title="Este botón es solo para chollos de tipo tienda y que tengan en tipo de configuración 'Cupones'"
                        >
                            ?
                        </button>
                        <div id="tooltip_info_aplicar" class="hidden absolute left-0 bottom-full mb-2 w-64 p-3 bg-gray-900 text-white text-xs rounded-lg shadow-lg z-50">
                            Este botón es solo para chollos de tipo tienda y que tengan en tipo de configuración "Cupones"
                            <div class="absolute left-4 top-full w-0 h-0 border-l-4 border-r-4 border-t-4 border-transparent border-t-gray-900"></div>
                        </div>
                    </div>
                </div>

                <input type="hidden" name="ofertas_temporales" id="ofertas_temporales_input" value="{{ old('ofertas_temporales', json_encode([])) }}">

                <div class="space-y-3" id="ofertas_existentes_lista">
                    @if($esEdicion && $chollo->ofertas->count())
                        @foreach($chollo->ofertas->sortBy(function($oferta) { return $oferta->producto->nombre ?? '—'; }) as $oferta)
                            @php
                                $opacidad = ($oferta->mostrar === 'no') ? 'opacity-50' : '';
                            @endphp
                            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 bg-gray-50 dark:bg-gray-800 flex flex-col md:flex-row md:items-center md:justify-between gap-4 {{ $opacidad }}" data-oferta-id="{{ $oferta->id }}" data-mostrar="{{ $oferta->mostrar }}">
                                <div class="flex-1 flex flex-col md:flex-row md:items-center md:justify-between gap-2">
                                    <div class="text-sm text-gray-700 dark:text-gray-200">
                                        <div class="font-semibold text-base text-gray-900 dark:text-white mb-1">
                                            {{ $oferta->producto->nombre ?? '—' }}
                                        </div>
                                        <div>
                                            {{ $oferta->tienda->nombre ?? 'Sin tienda' }} · 
                                            Precio total: <span class="font-semibold">{{ number_format($oferta->precio_total, 2) }}€</span> ·
                                            Unidades: {{ $oferta->unidades }} ·
                                            Precio/unidad: {{ number_format($oferta->precio_unidad, 4) }}€
                                        </div>
                                        @if(!empty($oferta->descuentos))
                                            <div class="mt-1">
                                                Descuentos: <span class="font-semibold">{{ $oferta->descuentos }}</span>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="flex items-center gap-2 shrink-0">
                                        <a href="{{ route('admin.ofertas.edit', $oferta) }}" target="_blank" class="px-3 py-2 text-sm font-medium rounded-md bg-blue-600 text-white hover:bg-blue-700">
                                            Editar
                                        </a>
                                        <a href="{{ $oferta->url }}" target="_blank" class="px-3 py-2 text-sm font-medium rounded-md bg-green-600 text-white hover:bg-green-700">
                                            Ir
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="text-sm text-gray-500 dark:text-gray-400" id="ofertas_existentes_vacias">
                            No hay ofertas guardadas todavía.
                        </div>
                    @endif
                </div>

                <div id="ofertas_temporales_lista" class="space-y-3"></div>

                <div class="flex flex-wrap items-center gap-3 pt-2">
                    <button type="button" id="btn_abrir_modal_oferta" class="inline-flex items-center px-4 py-2 rounded-md bg-green-600 hover:bg-green-700 text-white font-semibold">
                        Añadir oferta
                    </button>
                    <span class="text-xs text-gray-500 dark:text-gray-400">
                        Las ofertas temporales se guardarán cuando guardes el chollo.
                    </span>
                </div>
            </fieldset>

            {{-- Avisos --}}
            <div id="seccion-avisos" class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6 space-y-4 border border-gray-200 dark:border-gray-700 mt-8">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Avisos</h3>
                    <button
                        type="button"
                        onclick="mostrarModalNuevoAviso()"
                        class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-pink-600 hover:bg-pink-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-pink-500"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        Añadir Aviso
                    </button>
                </div>

                <div id="lista-avisos" class="space-y-3"></div>
                <div id="sin-avisos" class="text-center py-4 text-gray-500 dark:text-gray-400 hidden">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                    <p class="mt-2 text-sm">No hay avisos configurados</p>
                </div>
            </div>

            {{-- ANOTACIONES INTERNAS --}}
            <fieldset class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6 space-y-4 border border-gray-200 dark:border-gray-700">
                <legend class="text-lg font-semibold text-gray-700 dark:text-gray-200">Anotaciones internas</legend>
                <textarea
                    name="anotaciones_internas"
                    rows="4"
                    class="w-full px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border @error('anotaciones_internas') border-red-500 @enderror"
                >{{ old('anotaciones_internas', optional($chollo)->anotaciones_internas ?? '') }}</textarea>
                @error('anotaciones_internas')
                    <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                @enderror
            </fieldset>

            {{-- GUARDAR --}}
            <div class="sticky bottom-4 flex justify-end z-10 mt-8">
                <button
                    type="submit"
                    id="btn_guardar"
                    class="inline-flex items-center bg-pink-600 hover:bg-pink-700 text-white font-semibold text-base px-6 py-3 rounded-md shadow-lg transition disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    Guardar chollo
                </button>
            </div>
        </form>

        {{-- Modal Oferta Chollo --}}
        <div id="modal-oferta-chollo" class="hidden fixed inset-0 bg-black/30 backdrop-blur-sm flex items-center justify-center z-50 p-4">
            <div class="bg-white dark:bg-gray-800 rounded-lg w-full max-w-5xl p-6 shadow-xl overflow-y-auto max-h-[90vh]">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Añadir oferta vinculada</h2>
                    <button type="button" id="btn_cerrar_modal_oferta" class="text-gray-500 hover:text-gray-700 dark:text-gray-300 dark:hover:text-gray-100">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <form id="form-modal-oferta" class="space-y-4">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="md:col-span-2">
                            <label class="block mb-1 font-medium text-gray-700 dark:text-gray-200">Producto *</label>
                            <div class="relative">
                                <input type="hidden" id="modal_producto_id">
                                <input type="text"
                                    id="modal_producto_nombre"
                                    class="w-full px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    placeholder="Escribe para buscar productos..."
                                    autocomplete="off">
                                <div id="modal_producto_sugerencias" class="absolute z-50 w-full mt-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md shadow-lg hidden max-h-60 overflow-y-auto"></div>
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Por defecto se usa el producto del chollo, pero puedes cambiarlo si esta oferta corresponde a otro.</p>
                        </div>

                        <div class="md:col-span-2">
                            <label class="block mb-1 font-medium text-gray-700 dark:text-gray-200">Tienda *</label>
                            <div class="relative">
                                <input type="hidden" id="modal_tienda_id">
                                <input type="text" id="modal_tienda_nombre" class="w-full px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Escribe para buscar tiendas..." autocomplete="off">
                                <div id="modal_tienda_sugerencias" class="absolute z-50 w-full mt-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md shadow-lg hidden max-h-60 overflow-y-auto"></div>
                            </div>
                        </div>

                        <div>
                            <label class="block mb-1 font-medium text-gray-700 dark:text-gray-200">Unidades *</label>
                            <input type="number" id="modal_unidades" min="0.01" step="0.01" class="w-full px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>

                        <div>
                            <label class="block mb-1 font-medium text-gray-700 dark:text-gray-200">Precio total (€) *</label>
                            <input type="number" id="modal_precio_total" min="0" step="0.01" class="w-full px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>

                        <div>
                            <label class="block mb-1 font-medium text-gray-700 dark:text-gray-200">Precio por unidad (€)</label>
                            <input type="number" id="modal_precio_unidad" min="0" step="0.0001" class="w-full px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border focus:outline-none focus:ring-2 focus:ring-blue-500" readonly>
                        </div>

                        <div>
                            <label class="block mb-1 font-medium text-gray-700 dark:text-gray-200">Mostrar *</label>
                            <select id="modal_mostrar" class="w-full px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="si">Sí</option>
                                <option value="no">No</option>
                            </select>
                        </div>

                        <div class="md:col-span-2">
                            <label class="block mb-1 font-medium text-gray-700 dark:text-gray-200">URL *</label>
                            <input type="url" id="modal_url" class="w-full px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="https://...">
                        </div>

                        <div>
                            <label class="block mb-1 font-medium text-gray-700 dark:text-gray-200">Variante</label>
                            <input type="text" id="modal_variante" class="w-full px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Número, texto, etc.">
                        </div>

                        <div>
                            <label class="block mb-1 font-medium text-gray-700 dark:text-gray-200">Descuentos</label>
                            <div class="flex gap-2 items-center">
                                <select id="modal_select_descuentos" class="flex-1 px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="">Sin descuento</option>
                                    <option value="cupon">Cupón</option>
                                    <option value="3x2">3x2</option>
                                    <option value="2x1 - SoloCarrefour">2x1 - SoloCarrefour</option>
                                    <option value="2a al 70">2ª al 70%</option>
                                    <option value="2a al 50">2ª al 50%</option>
                                    <option value="2a al 50 - cheque - SoloCarrefour">2ª al 50% - Cheque - SoloCarrefour</option>
                                </select>

                                <div id="modal_cupon_campos_container" class="hidden flex gap-2">
                                    <input
                                        type="text"
                                        id="modal_cupon_codigo"
                                        placeholder="Código"
                                        class="w-32 px-3 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    >
                                    <input
                                        type="number"
                                        id="modal_cupon_cantidad"
                                        step="0.01"
                                        min="0"
                                        placeholder="€"
                                        class="w-20 px-3 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border text-center focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    >
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="block mb-1 font-medium text-gray-700 dark:text-gray-200">Actualizar precio cada *</label>
                            <div class="flex gap-2">
                                <input type="number" id="modal_frecuencia_valor" min="0.1" step="0.1" class="w-full px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <select id="modal_frecuencia_unidad" class="px-3 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="minutos">Minutos</option>
                                    <option value="horas">Horas</option>
                                    <option value="dias" selected>Días</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block mb-1 font-medium text-gray-700 dark:text-gray-200">Inicio *</label>
                            <input type="datetime-local" id="modal_fecha_inicio" class="w-full px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>

                        <div>
                            <label class="block mb-1 font-medium text-gray-700 dark:text-gray-200">Fecha final</label>
                            <input type="datetime-local" id="modal_fecha_final" class="w-full px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>

                        <div>
                            <label class="block mb-1 font-medium text-gray-700 dark:text-gray-200">Comprobada</label>
                            <input type="datetime-local" id="modal_comprobada" class="w-full px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>

                        <div>
                            <label class="block mb-1 font-medium text-gray-700 dark:text-gray-200">Comprobación chollo *</label>
                            <div class="flex gap-2">
                                <input type="number" id="modal_frecuencia_chollo_valor" min="0.1" step="0.1" class="w-full px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <select id="modal_frecuencia_chollo_unidad" class="px-3 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="minutos">Minutos</option>
                                    <option value="horas">Horas</option>
                                    <option value="dias" selected>Días</option>
                                </select>
                            </div>
                        </div>

                        <div class="md:col-span-2">
                            <label class="block mb-1 font-medium text-gray-700 dark:text-gray-200">Anotaciones internas</label>
                            <textarea id="modal_anotaciones" rows="3" class="w-full px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 pt-4">
                        <button type="button" id="btn_cancelar_modal_oferta" class="px-4 py-2 text-sm font-medium rounded-md bg-gray-600 text-white hover:bg-gray-500">
                            Cancelar
                        </button>
                        <button type="submit" class="px-4 py-2 text-sm font-semibold rounded-md bg-pink-600 text-white hover:bg-pink-700">
                            Añadir oferta
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Modal Aviso --}}
        <div id="modal-aviso" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
            <div class="bg-gray-800 dark:bg-gray-800 rounded-lg p-6 max-w-md w-full">
                <h2 id="modal-titulo" class="text-lg font-semibold mb-4 text-gray-200 dark:text-gray-200">Nuevo aviso</h2>
                <div id="form-aviso-container">
                    <input type="hidden" id="aviso-id" name="aviso_id">
                    <input type="hidden" id="avisoable-type" name="avisoable_type" value="App\Models\Chollo">
                    <input type="hidden" id="avisoable-id" name="avisoable_id" value="{{ optional($chollo)->id ?? 'null' }}">

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
                        <textarea id="texto-aviso" name="texto_aviso" rows="3" class="w-full px-3 py-2 border border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200" placeholder="Escribe el texto del aviso..." required></textarea>
                    </div>

                    <div class="mb-4">
                        <label for="fecha-aviso" class="block text-sm font-medium text-gray-300 dark:text-gray-300 mb-2">Fecha y hora</label>
                        <div class="flex flex-wrap gap-2 mb-3" data-botones-fecha-rapida>
                            <button type="button" class="px-3 py-1 bg-gray-700 hover:bg-gray-600 text-white text-xs md:text-sm rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 transition" data-dias="1">1 día</button>
                            <button type="button" class="px-3 py-1 bg-gray-700 hover:bg-gray-600 text-white text-xs md:text-sm rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 transition" data-dias="4">4 días</button>
                            <button type="button" class="px-3 py-1 bg-gray-700 hover:bg-gray-600 text-white text-xs md:text-sm rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 transition" data-dias="7">7 días</button>
                            <button type="button" class="px-3 py-1 bg-gray-700 hover:bg-gray-600 text-white text-xs md:text-sm rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 transition" data-dias="14">14 días</button>
                        </div>
                        <input type="datetime-local" id="fecha-aviso" name="fecha_aviso" class="w-full px-3 py-2 border border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200" required>
                    </div>

                    <div class="mb-4">
                        <label class="flex items-center">
                            <input type="checkbox" id="oculto" name="oculto" class="rounded border-gray-300 text-pink-600 focus:ring-pink-500">
                            <span class="ml-2 text-sm text-gray-300 dark:text-gray-300">Ocultar aviso</span>
                        </label>
                        <p class="text-xs text-gray-400 dark:text-gray-400 mt-1">Los avisos ocultos no aparecen en las pestañas principales pero se mantienen para futuras comprobaciones.</p>
                    </div>

                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="cerrarModalAviso()" class="px-4 py-2 text-sm font-medium text-gray-300 bg-gray-600 rounded-md hover:bg-gray-500 dark:bg-gray-600 dark:text-gray-300 dark:hover:bg-gray-500">
                            Cancelar
                        </button>
                        <button type="button" onclick="guardarAviso()" class="px-4 py-2 text-sm font-medium text-white bg-pink-600 rounded-md hover:bg-pink-700">
                            Guardar
                        </button>
                    </div>
                </div>
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
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600">
                        Cancelar
                    </button>
                    <button type="button" id="btn-confirmar-recorte" 
                        class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700">
                        Aceptar y procesar
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Datos iniciales para categorías --}}
    <div id="datos-categorias"
        data-categorias-raiz='@json($categoriasRaiz)'
        data-categoria-id="{{ old('categoria_id', $categoriaSeleccionada->id ?? '') }}">
    </div>

    {{-- Scripts --}}
    <script>
        const descripcionInternaInicial = @json($descripcionInternaValor);
        const descripcionInternaDefaultData = @json($descripcionInternaDefaultData);

        // ========= CATEGORÍAS =========
        let categoriasRaiz = [];
        let categoriaChollo = null;

        document.addEventListener('DOMContentLoaded', () => {
            const datos = document.getElementById('datos-categorias');
            categoriasRaiz = JSON.parse(datos.dataset.categoriasRaiz || '[]');
            categoriaChollo = datos.dataset.categoriaId || null;

            if (categoriaChollo) {
                cargarJerarquiaCompleta(categoriaChollo);
            } else {
                crearSelectorCategoria(0, categoriasRaiz, null);
            }

            configurarBotonesCategorias();
            inicializarSinCategoria();
            cargarAvisos();
            inicializarBotonesFechaRapidaAviso();
            inicializarCamposCupon();
            inicializarValidacionUrl();
            inicializarPrecioUnidadAuto();
            inicializarSlugAutomatico();
            inicializarTipoChollo();
            inicializarDescripcionInternaGestion();
            inicializarBotonAplicarFechasCupones();
        });

        function configurarBotonesCategorias() {
            const btnLimpiar = document.getElementById('limpiar-categorias');
            if (btnLimpiar) {
                btnLimpiar.addEventListener('click', () => limpiarTodasLasCategorias());
            }

            const btnAgregar = document.getElementById('agregar-categoria');
            if (btnAgregar) {
                btnAgregar.addEventListener('click', () => agregarNivelCategoria());
            }
        }

        function inicializarSinCategoria() {
            const checkbox = document.getElementById('sin_categoria');
            const container = document.getElementById('categorias-container');
            const hiddenInput = document.getElementById('categoria-final');
            const btnAgregar = document.getElementById('agregar-categoria');
            const btnLimpiar = document.getElementById('limpiar-categorias');

            if (!checkbox) {
                return;
            }

            const actualizarEstadoVisual = (deshabilitar) => {
                if (container) {
                    container.querySelectorAll('select.categoria-select').forEach((select) => {
                        select.disabled = deshabilitar;
                        select.classList.toggle('opacity-60', deshabilitar);
                        select.classList.toggle('cursor-not-allowed', deshabilitar);
                    });
                }

                [btnAgregar, btnLimpiar].forEach((btn) => {
                    if (!btn) {
                        return;
                    }
                    btn.disabled = deshabilitar;
                    btn.classList.toggle('opacity-60', deshabilitar);
                    btn.classList.toggle('cursor-not-allowed', deshabilitar);
                });
            };

            const aplicarEstado = () => {
                const deshabilitar = checkbox.checked;

                actualizarEstadoVisual(deshabilitar);

                if (deshabilitar) {
                    if (hiddenInput) {
                        hiddenInput.value = '';
                    }
                    actualizarIndicadorBotonAgregar();
                } else {
                    actualizarCategoriaFinal();
                }
            };

            checkbox.addEventListener('change', aplicarEstado);
            aplicarEstado();
        }

        function agregarNivelCategoria() {
            const sinCategoria = document.getElementById('sin_categoria');
            if (sinCategoria && sinCategoria.checked) {
                return;
            }

            const selectores = document.querySelectorAll('.categoria-select');
            let ultimoNivel = -1;
            let ultimaCategoriaId = null;

            for (let i = selectores.length - 1; i >= 0; i--) {
                if (selectores[i].value) {
                    ultimoNivel = parseInt(selectores[i].dataset.nivel);
                    ultimaCategoriaId = selectores[i].value;
                    break;
                }
            }

            if (!ultimaCategoriaId) {
                alert('Primero debes seleccionar una categoría para poder agregar un nivel adicional.');
                return;
            }

            verificarYAgregarSubcategoria(ultimoNivel + 1, ultimaCategoriaId);
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
                    crearSelectorCategoria(nivel, subcategorias, null);
                    actualizarCategoriaFinal();
                } else {
                    alert('La categoría seleccionada no tiene subcategorías disponibles.');
                }
            })
            .catch(error => {
                console.error('Error verificando subcategorías:', error);
                alert('Error al verificar si hay subcategorías disponibles.');
            });
        }

        function cargarJerarquiaCompleta(categoriaId) {
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
                crearSelectorCategoria(0, categoriasRaiz, null);
            });
        }

        function construirJerarquiaCompleta(jerarquia) {
            const container = document.getElementById('categorias-container');
            container.innerHTML = '';

            jerarquia.forEach((nivel, index) => {
                if (index === 0) {
                    crearSelectorCategoria(0, categoriasRaiz, nivel.id);
                } else {
                    const nivelAnterior = jerarquia[index - 1];
                    cargarSubcategoriasParaJerarquia(index, nivelAnterior.id, nivel.id);
                }
            });

            document.getElementById('categoria-final').value = categoriaChollo;
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

            const selectorExistente = document.querySelector(`.selector-categoria[data-nivel="${nivel}"]`);
            if (selectorExistente) {
                selectorExistente.remove();
            }

            const div = document.createElement('div');
            div.className = 'selector-categoria flex items-center gap-4 mb-4';
            div.dataset.nivel = nivel;

            const label = document.createElement('label');
            label.className = 'block mb-1 font-medium text-gray-700 dark:text-gray-200 min-w-[120px]';
            label.textContent = nivel === 0 ? 'Categoría principal *' : `Subcategoría ${nivel}`;

            const select = document.createElement('select');
            select.className = 'categoria-select w-full px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border';
            select.dataset.nivel = nivel;

            const optionDefault = document.createElement('option');
            optionDefault.value = '';
            optionDefault.textContent = nivel === 0 ? 'Selecciona una categoría' : 'Selecciona una subcategoría';
            select.appendChild(optionDefault);

            const opcionesOrdenadas = [...opciones].sort((a, b) => a.nombre.localeCompare(b.nombre, 'es', {sensitivity: 'base'}));
            opcionesOrdenadas.forEach(cat => {
                const option = document.createElement('option');
                option.value = cat.id;
                option.textContent = cat.nombre;
                if (valorSeleccionado == cat.id) {
                    option.selected = true;
                }
                select.appendChild(option);
            });

            let removeBtn = null;
            if (nivel > 0) {
                removeBtn = document.createElement('button');
                removeBtn.type = 'button';
                removeBtn.className = 'bg-red-600 hover:bg-red-700 text-white px-3 py-2 rounded text-sm';
                removeBtn.textContent = '×';
                removeBtn.title = 'Eliminar este nivel y los superiores';
                removeBtn.onclick = () => eliminarSelectorYSuperiores(nivel);
            }

            div.appendChild(label);
            div.appendChild(select);
            if (removeBtn) div.appendChild(removeBtn);

            container.appendChild(div);

            select.addEventListener('change', () => {
                const categoriaId = select.value;
                limpiarSelectoresSuperiores(nivel + 1);
                if (categoriaId) {
                    cargarSubcategorias(nivel + 1, categoriaId);
                }
                actualizarCategoriaFinal();
            });

        const sinCategoriaCheckbox = document.getElementById('sin_categoria');
        if (sinCategoriaCheckbox && sinCategoriaCheckbox.checked) {
            select.disabled = true;
            select.classList.add('opacity-60', 'cursor-not-allowed');
        }
        }

        function cargarSubcategorias(nivel, categoriaId) {
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
            actualizarCategoriaFinal();
        }

        function limpiarTodasLasCategorias() {
            const sinCategoria = document.getElementById('sin_categoria');
            if (sinCategoria && sinCategoria.checked) {
                return;
            }

            const container = document.getElementById('categorias-container');
            container.innerHTML = '';
            document.getElementById('categoria-final').value = '';
            crearSelectorCategoria(0, categoriasRaiz, null);
        }

        function actualizarCategoriaFinal() {
            const sinCategoria = document.getElementById('sin_categoria');
            const hidden = document.getElementById('categoria-final');

            if (sinCategoria && sinCategoria.checked) {
                if (hidden) {
                    hidden.value = '';
                }
                return;
            }

            const selectores = document.querySelectorAll('.categoria-select');
            let categoriaFinal = null;

            for (let i = selectores.length - 1; i >= 0; i--) {
                if (selectores[i].value) {
                    categoriaFinal = selectores[i].value;
                    break;
                }
            }

            if (hidden) {
                hidden.value = categoriaFinal || '';
            }
            actualizarIndicadorBotonAgregar();
        }

        function actualizarIndicadorBotonAgregar() {
            const btnAgregar = document.getElementById('agregar-categoria');
            if (!btnAgregar) return;

            const sinCategoria = document.getElementById('sin_categoria');
            if (sinCategoria && sinCategoria.checked) {
                btnAgregar.disabled = true;
                btnAgregar.className = 'bg-gray-400 cursor-not-allowed text-white font-semibold px-4 py-2 rounded-md transition duration-150 ease-in-out focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 opacity-60';
                btnAgregar.title = 'El chollo se guardará sin categoría';
                return;
            }

            const selectores = document.querySelectorAll('.categoria-select');
            let ultimaCategoriaId = null;

            for (let i = selectores.length - 1; i >= 0; i--) {
                if (selectores[i].value) {
                    ultimaCategoriaId = selectores[i].value;
                    break;
                }
            }

            if (!ultimaCategoriaId) {
                btnAgregar.disabled = true;
                btnAgregar.className = 'bg-gray-400 cursor-not-allowed text-white font-semibold px-4 py-2 rounded-md transition duration-150 ease-in-out focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500';
                btnAgregar.title = 'Primero debes seleccionar una categoría';
                return;
            }

            verificarSubcategoriasDisponibles(ultimaCategoriaId, btnAgregar);
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
                    btnAgregar.disabled = false;
                    btnAgregar.className = 'bg-green-600 hover:bg-green-700 text-white font-semibold px-4 py-2 rounded-md transition duration-150 ease-in-out focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500';
                    btnAgregar.title = `Agregar nivel de subcategoría (${subcategorias.length} opciones disponibles)`;
                } else {
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

        // ========= BÚSQUEDA PRODUCTOS =========
        let timeoutBusquedaProducto = null;
        let productosActuales = [];
        let indiceSeleccionado = -1;

        document.getElementById('producto_nombre').addEventListener('input', (e) => {
            const query = e.target.value;
            document.getElementById('producto_id').value = '';
            e.target.classList.remove('border-green-500');

            if (timeoutBusquedaProducto) clearTimeout(timeoutBusquedaProducto);
            if (query.length < 2) {
                ocultarSugerenciasProducto();
                return;
            }

            timeoutBusquedaProducto = setTimeout(() => buscarProductos(query), 300);
        });

        document.getElementById('producto_nombre').addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                ocultarSugerenciasProducto();
                e.target.blur();
            } else if (e.key === 'ArrowDown') {
                e.preventDefault();
                navegarSugerenciasProducto('abajo');
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                navegarSugerenciasProducto('arriba');
            } else if (e.key === 'Enter') {
                e.preventDefault();
                seleccionarProductoResaltado();
            }
        });

        async function buscarProductos(query) {
            try {
                const response = await fetch(`{{ route('admin.ofertas.buscar.productos') }}?q=${encodeURIComponent(query)}`);
                productosActuales = await response.json();
                mostrarSugerenciasProducto(productosActuales);
            } catch (error) {
                console.error('Error al buscar productos:', error);
            }
        }

        function mostrarSugerenciasProducto(productos) {
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
                div.onclick = () => seleccionarProducto(producto);
                div.onmouseenter = () => resaltarSugerenciaProducto(index);
                contenedor.appendChild(div);
            });

            resaltarSugerenciaProducto(0);
            contenedor.classList.remove('hidden');
        }

        function resaltarSugerenciaProducto(index) {
            const contenedor = document.getElementById('producto_sugerencias');
            const elementos = contenedor.querySelectorAll('div[data-index]');
            elementos.forEach(el => el.classList.remove('bg-blue-100', 'dark:bg-blue-700'));
            if (elementos[index]) elementos[index].classList.add('bg-blue-100', 'dark:bg-blue-700');
            indiceSeleccionado = index;
        }

        function navegarSugerenciasProducto(direccion) {
            if (productosActuales.length === 0) return;
            if (direccion === 'abajo') {
                indiceSeleccionado = (indiceSeleccionado + 1) % productosActuales.length;
            } else {
                indiceSeleccionado = (indiceSeleccionado - 1 + productosActuales.length) % productosActuales.length;
            }
            resaltarSugerenciaProducto(indiceSeleccionado);
        }

        function seleccionarProductoResaltado() {
            if (indiceSeleccionado >= 0 && indiceSeleccionado < productosActuales.length) {
                seleccionarProducto(productosActuales[indiceSeleccionado]);
            }
        }

        function seleccionarProducto(producto) {
            document.getElementById('producto_id').value = producto.id;
            document.getElementById('producto_nombre').value = producto.texto_completo;
            const productoNombreHidden = document.getElementById('producto_nombre_hidden');
            if (productoNombreHidden) {
                productoNombreHidden.value = producto.texto_completo;
            }
            document.getElementById('producto_nombre').classList.add('border-green-500');
            ocultarSugerenciasProducto();
            // Recalcular precio por unidad si ya hay precio nuevo y unidades
            if (typeof recalcularPrecioUnidadChollo === 'function') {
                setTimeout(() => {
                    recalcularPrecioUnidadChollo();
                }, 100);
            }
        }

        function ocultarSugerenciasProducto() {
            document.getElementById('producto_sugerencias').classList.add('hidden');
        }

        function ocultarSugerencias() {
            ocultarSugerenciasProducto();
        }

        document.addEventListener('click', function(e) {
            if (!e.target.closest('#producto_nombre') && !e.target.closest('#producto_sugerencias')) {
                ocultarSugerencias();
            }
            if (!e.target.closest('#tienda_nombre') && !e.target.closest('#tienda_sugerencias')) {
                ocultarSugerenciasTienda();
            }
            if (!e.target.closest('#modal_producto_nombre') && !e.target.closest('#modal_producto_sugerencias')) {
                if (typeof window.ocultarSugerenciasProductoModal === 'function') {
                    window.ocultarSugerenciasProductoModal();
                }
            }
            if (!e.target.closest('#modal_tienda_nombre') && !e.target.closest('#modal_tienda_sugerencias')) {
                if (typeof window.ocultarSugerenciasTiendaModal === 'function') {
                    window.ocultarSugerenciasTiendaModal();
                }
            }
        });

        // ========= BÚSQUEDA TIENDAS =========
        let timeoutBusquedaTienda = null;
        let tiendasActuales = [];
        let indiceSeleccionadoTienda = -1;

        document.getElementById('tienda_nombre').addEventListener('input', (e) => {
            const query = e.target.value;
            document.getElementById('tienda_id').value = '';
            e.target.classList.remove('border-green-500');

            if (timeoutBusquedaTienda) clearTimeout(timeoutBusquedaTienda);
            if (query.length < 2) {
                ocultarSugerenciasTienda();
                return;
            }

            timeoutBusquedaTienda = setTimeout(() => buscarTiendas(query), 300);
        });

        document.getElementById('tienda_nombre').addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                ocultarSugerenciasTienda();
                e.target.blur();
            } else if (e.key === 'ArrowDown') {
                e.preventDefault();
                navegarSugerenciasTienda('abajo');
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                navegarSugerenciasTienda('arriba');
            } else if (e.key === 'Enter') {
                e.preventDefault();
                seleccionarTiendaResaltada();
            }
        });

        async function buscarTiendas(query) {
            try {
                const response = await fetch('{{ route('admin.ofertas.tiendas.disponibles') }}');
                const todas = await response.json();
                tiendasActuales = todas.filter(tienda => tienda.nombre.toLowerCase().includes(query.toLowerCase()));
                mostrarSugerenciasTienda(tiendasActuales);
            } catch (error) {
                console.error('Error al buscar tiendas:', error);
            }
        }

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
                div.onclick = () => seleccionarTienda(tienda);
                div.onmouseenter = () => resaltarSugerenciaTienda(index);
                contenedor.appendChild(div);
            });

            resaltarSugerenciaTienda(0);
            contenedor.classList.remove('hidden');
        }

        function resaltarSugerenciaTienda(index) {
            const contenedor = document.getElementById('tienda_sugerencias');
            const elementos = contenedor.querySelectorAll('div[data-index]');
            elementos.forEach(el => el.classList.remove('bg-blue-100', 'dark:bg-blue-700'));
            if (elementos[index]) elementos[index].classList.add('bg-blue-100', 'dark:bg-blue-700');
            indiceSeleccionadoTienda = index;
        }

        function navegarSugerenciasTienda(direccion) {
            if (tiendasActuales.length === 0) return;
            if (direccion === 'abajo') {
                indiceSeleccionadoTienda = (indiceSeleccionadoTienda + 1) % tiendasActuales.length;
            } else {
                indiceSeleccionadoTienda = (indiceSeleccionadoTienda - 1 + tiendasActuales.length) % tiendasActuales.length;
            }
            resaltarSugerenciaTienda(indiceSeleccionadoTienda);
        }

        function seleccionarTiendaResaltada() {
            if (indiceSeleccionadoTienda >= 0 && indiceSeleccionadoTienda < tiendasActuales.length) {
                seleccionarTienda(tiendasActuales[indiceSeleccionadoTienda]);
            }
        }

        function seleccionarTiendaModalResaltada() {
            if (indiceSeleccionadoTiendaModal < 0 || !tiendasFiltradasModal[indiceSeleccionadoTiendaModal]) {
                return;
            }
            seleccionarTiendaModal(tiendasFiltradasModal[indiceSeleccionadoTiendaModal]);
        }

        let productosModalActuales = [];
        let indiceSeleccionadoProductoModal = -1;
        let modalProductoNombre = null;
        let modalProductoId = null;

        async function buscarProductosModal(query) {
            if (query.length < 2) {
                ocultarSugerenciasProductoModal();
                return;
            }

            try {
                const response = await fetch(`{{ route('admin.ofertas.buscar.productos') }}?q=${encodeURIComponent(query)}`);
                const productos = await response.json();
                productosModalActuales = productos;
                mostrarSugerenciasProductoModal(productos);
            } catch (error) {
                console.error('Error al buscar productos para el modal:', error);
            }
        }

        function mostrarSugerenciasProductoModal(productos) {
            const contenedor = document.getElementById('modal_producto_sugerencias');
            if (!contenedor) {
                return;
            }

            contenedor.innerHTML = '';
            productosModalActuales = Array.isArray(productos) ? productos : [];
            indiceSeleccionadoProductoModal = -1;

            if (!productosModalActuales.length) {
                contenedor.innerHTML = '<div class="px-4 py-2 text-gray-500 dark:text-gray-400">No se encontraron productos</div>';
                contenedor.classList.remove('hidden');
                return;
            }

            productosModalActuales.forEach((producto, index) => {
                const div = document.createElement('div');
                div.className = 'px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 cursor-pointer text-gray-900 dark:text-white border-b border-gray-200 dark:border-gray-600 last:border-b-0';
                div.textContent = producto.texto_completo ?? producto.nombre ?? '';
                div.dataset.index = index;
                div.onmouseenter = () => resaltarSugerenciaProductoModal(index);
                div.onclick = () => seleccionarProductoModal(producto, index);

                contenedor.appendChild(div);
            });

            resaltarSugerenciaProductoModal(0);
            contenedor.classList.remove('hidden');
        }

        function resaltarSugerenciaProductoModal(index) {
            const contenedor = document.getElementById('modal_producto_sugerencias');
            if (!contenedor) {
                return;
            }
            const elementos = contenedor.querySelectorAll('div[data-index]');
            if (!elementos.length) {
                indiceSeleccionadoProductoModal = -1;
                return;
            }

            let nuevoIndice = Number(index);
            if (Number.isNaN(nuevoIndice)) {
                nuevoIndice = 0;
            }

            if (nuevoIndice < 0) {
                nuevoIndice = elementos.length - 1;
            } else if (nuevoIndice >= elementos.length) {
                nuevoIndice = 0;
            }

            elementos.forEach(el => el.classList.remove('bg-blue-100', 'dark:bg-blue-700'));
            const elemento = elementos[nuevoIndice];
            if (elemento) {
                elemento.classList.add('bg-blue-100', 'dark:bg-blue-700');
            }
            indiceSeleccionadoProductoModal = nuevoIndice;
        }

        function ocultarSugerenciasProductoModal() {
            const contenedor = document.getElementById('modal_producto_sugerencias');
            if (!contenedor) return;
            contenedor.classList.add('hidden');
            contenedor.innerHTML = '';
            indiceSeleccionadoProductoModal = -1;
        }

        function actualizarResumenProductoModal(nombre) {
            const resumen = document.getElementById('modal_producto_resumen');
            if (resumen) {
                resumen.textContent = nombre && nombre.trim() !== '' ? nombre : '—';
            }
        }

        function seleccionarProductoModal(producto, index = null) {
            if (!producto) return;
            if (index !== null && !Number.isNaN(Number(index))) {
                indiceSeleccionadoProductoModal = Number(index);
            }
            if (modalProductoId) {
                modalProductoId.value = producto.id;
            }
            if (modalProductoNombre) {
                modalProductoNombre.value = producto.texto_completo ?? producto.nombre ?? '';
                modalProductoNombre.classList.add('border-green-500');
            }
            actualizarResumenProductoModal(producto.texto_completo ?? producto.nombre ?? '');
            ocultarSugerenciasProductoModal();
            // Recalcular precio por unidad si ya hay precio total y unidades
            if (typeof actualizarPrecioUnidadModal === 'function') {
                actualizarPrecioUnidadModal();
            }
        }

        function navegarSugerenciasProductoModal(direccion) {
            if (!productosModalActuales.length) {
                return;
            }

            if (direccion === 'abajo') {
                indiceSeleccionadoProductoModal = (indiceSeleccionadoProductoModal + 1) % productosModalActuales.length;
            } else if (direccion === 'arriba') {
                indiceSeleccionadoProductoModal = (indiceSeleccionadoProductoModal - 1 + productosModalActuales.length) % productosModalActuales.length;
            } else {
                return;
            }

            resaltarSugerenciaProductoModal(indiceSeleccionadoProductoModal);
        }

        function seleccionarProductoModalResaltada() {
            if (!productosModalActuales.length) {
                return;
            }

            if (indiceSeleccionadoProductoModal < 0 || indiceSeleccionadoProductoModal >= productosModalActuales.length) {
                indiceSeleccionadoProductoModal = 0;
            }

            seleccionarProductoModal(productosModalActuales[indiceSeleccionadoProductoModal], indiceSeleccionadoProductoModal);
        }

        function seleccionarTienda(tienda) {
            document.getElementById('tienda_id').value = tienda.id;
            document.getElementById('tienda_nombre').value = tienda.nombre;
            const tiendaNombreHidden = document.getElementById('tienda_nombre_hidden');
            if (tiendaNombreHidden) {
                tiendaNombreHidden.value = tienda.nombre;
            }
            document.getElementById('tienda_nombre').classList.add('border-green-500');
            ocultarSugerenciasTienda();
        }

        function ocultarSugerenciasTienda() {
            document.getElementById('tienda_sugerencias').classList.add('hidden');
        }

        // ========= CUPONES =========
        function inicializarCamposCupon() {
            const select = document.getElementById('select_descuentos');
            const container = document.getElementById('cupon_campos_container');
            const codigo = document.getElementById('cupon_codigo');
            const cantidad = document.getElementById('cupon_cantidad');

            function toggleCampos() {
                if (select.value === 'cupon') {
                    container.classList.remove('hidden');
                    codigo.required = true;
                    cantidad.required = true;
                } else {
                    container.classList.add('hidden');
                    codigo.required = false;
                    cantidad.required = false;
                }
            }

            select.addEventListener('change', toggleCampos);
            toggleCampos();
        }

        document.getElementById('form-chollo').addEventListener('submit', () => {
            // Actualizar campos ocultos de producto y tienda antes de enviar
            const productoNombre = document.getElementById('producto_nombre');
            const productoNombreHidden = document.getElementById('producto_nombre_hidden');
            if (productoNombre && productoNombreHidden) {
                productoNombreHidden.value = productoNombre.value;
            }

            const tiendaNombre = document.getElementById('tienda_nombre');
            const tiendaNombreHidden = document.getElementById('tienda_nombre_hidden');
            if (tiendaNombre && tiendaNombreHidden) {
                tiendaNombreHidden.value = tiendaNombre.value;
            }

            // Sincronizar descripcion_interna antes de enviar
            if (typeof sincronizarHidden === 'function') {
                sincronizarHidden();
            }

            const select = document.getElementById('select_descuentos');
            if (!select) {
                return;
            }

            if (select.value === 'cupon') {
                const codigo = document.getElementById('cupon_codigo');
                const cantidad = document.getElementById('cupon_cantidad');

                if (codigo) {
                    codigo.value = codigo.value.trim();
                }

                if (cantidad && cantidad.value) {
                    cantidad.value = String(cantidad.value).replace(',', '.');
                }
            }
        });

        // ========= VALIDACIÓN URL =========
        function inicializarValidacionUrl() {
            const urlInput = document.getElementById('url_input');
            const mensaje = document.getElementById('url_validation_message');
            const checkboxContainer = document.getElementById('url_duplicate_checkbox');
            const checkbox = document.getElementById('url_duplicate_confirm');
            const lista = document.getElementById('url_elements_list');
            const otherContainer = document.getElementById('url_other_elements');
            const btnGuardar = document.getElementById('btn_guardar');

            let timeout = null;

            urlInput.addEventListener('input', () => {
                const url = urlInput.value.trim();
                checkbox.checked = false;
                checkboxContainer.classList.add('hidden');
                otherContainer.classList.add('hidden');
                mensaje.classList.add('hidden');
                btnGuardar.disabled = false;
                btnGuardar.textContent = 'Guardar chollo';

                if (timeout) clearTimeout(timeout);
                if (!url) return;

                btnGuardar.disabled = true;
                btnGuardar.textContent = 'Validando URL...';

                timeout = setTimeout(async () => {
                    try {
                        const response = await fetch('{{ route('admin.chollos.verificar.url') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({
                                url,
                                chollo_id: {{ optional($chollo)->id ?? 'null' }},
                            })
                        });
                        const data = await response.json();
                        checkboxContainer.classList.add('hidden');
                        otherContainer.classList.add('hidden');
                        mensaje.classList.remove('hidden');

                        if (data.tipo === 'disponible') {
                            mensaje.className = 'mt-1 text-sm text-green-500';
                            mensaje.textContent = data.mensaje;
                            btnGuardar.disabled = false;
                            btnGuardar.textContent = 'Guardar chollo';
                        } else if (data.tipo === 'activo') {
                            mensaje.className = 'mt-1 text-sm text-red-500';
                            mensaje.textContent = data.mensaje;
                            checkboxContainer.classList.remove('hidden');
                            otherContainer.classList.remove('hidden');
                            lista.innerHTML = '';
                            (data.chollos || []).forEach(item => {
                                const div = document.createElement('div');
                                div.innerHTML = `<strong>ID:</strong> ${item.id} &bull; <strong>${item.titulo}</strong> &bull; ${item.tienda ?? ''}`;
                                lista.appendChild(div);
                            });
                            btnGuardar.disabled = !checkbox.checked;
                            btnGuardar.textContent = btnGuardar.disabled ? 'Confirma la URL duplicada' : 'Guardar chollo';
                        } else if (data.tipo === 'inactivo') {
                            mensaje.className = 'mt-1 text-sm text-blue-500';
                            mensaje.textContent = data.mensaje;
                            otherContainer.classList.remove('hidden');
                            lista.innerHTML = '';
                            (data.chollos || []).forEach(item => {
                                const div = document.createElement('div');
                                div.innerHTML = `<strong>ID:</strong> ${item.id} &bull; <strong>${item.titulo}</strong> &bull; ${item.tienda ?? ''}`;
                                lista.appendChild(div);
                            });
                            btnGuardar.disabled = false;
                            btnGuardar.textContent = 'Guardar chollo';
                        } else {
                            mensaje.classList.add('hidden');
                            btnGuardar.disabled = false;
                            btnGuardar.textContent = 'Guardar chollo';
                        }
                    } catch (error) {
                        console.error('Error validando URL:', error);
                        mensaje.className = 'mt-1 text-sm text-red-500';
                        mensaje.textContent = 'No se pudo validar la URL.';
                        mensaje.classList.remove('hidden');
                        btnGuardar.disabled = false;
                        btnGuardar.textContent = 'Guardar chollo';
                    }
                }, 800);
            });

            checkbox.addEventListener('change', () => {
                btnGuardar.disabled = checkboxContainer.classList.contains('hidden') ? false : !checkbox.checked;
                btnGuardar.textContent = btnGuardar.disabled ? 'Confirma la URL duplicada' : 'Guardar chollo';
            });

            if (urlInput.value.trim() !== '') {
                setTimeout(() => {
                    urlInput.dispatchEvent(new Event('input'));
                }, 100);
            }
        }

        // ========= PRECIO POR UNIDAD =========
        let recalcularPrecioUnidadChollo = null;

        function inicializarPrecioUnidadAuto() {
            const unidadesInput = document.getElementById('unidades');
            const precioNuevoInput = document.getElementById('precio_nuevo');
            const precioUnidadInput = document.getElementById('precio_unidad');
            const productoIdInput = document.getElementById('producto_id');
            const mensajeCalculo = document.getElementById('mensaje-calculo-precio-unidad');

            if (!unidadesInput || !precioNuevoInput || !precioUnidadInput) {
                return;
            }

            recalcularPrecioUnidadChollo = async function() {
                const unidades = parseFloat(String(unidadesInput.value).replace(',', '.'));
                const precioTexto = precioNuevoInput.value;

                if (!unidades || unidades <= 0) {
                    if (mensajeCalculo) {
                        mensajeCalculo.classList.add('hidden');
                    }
                    return;
                }

                const match = precioTexto.match(/\d+[.,]?\d*/);
                if (!match) {
                    if (mensajeCalculo) {
                        mensajeCalculo.classList.add('hidden');
                    }
                    return;
                }

                const precio = parseFloat(match[0].replace(',', '.'));
                if (!precio || precio <= 0) {
                    if (mensajeCalculo) {
                        mensajeCalculo.classList.add('hidden');
                    }
                    return;
                }

                // Verificar si hay producto asociado
                const productoId = productoIdInput ? productoIdInput.value : '';

                if (!productoId || productoId === '') {
                    // No hay producto: cálculo simple
                    precioUnidadInput.value = (precio / unidades).toFixed(4);
                    if (mensajeCalculo) {
                        mensajeCalculo.classList.remove('hidden');
                    }
                } else {
                    // Hay producto: usar el servicio
                    if (mensajeCalculo) {
                        mensajeCalculo.classList.add('hidden');
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
                                precio_total: precio,
                                unidades: unidades
                            })
                        });

                        const data = await response.json();
                        if (data.success) {
                            // El servicio ya devuelve el precio con los decimales correctos (2 o 3 según unidadDeMedida)
                            const precioUnidad = parseFloat(data.precio_unidad);
                            // Si el número tiene más de 2 decimales (tercer decimal no es 0), mostrar 3, sino 2
                            const tiene3Decimales = (precioUnidad * 1000) % 10 !== 0;
                            const decimales = tiene3Decimales ? 3 : 2;
                            precioUnidadInput.value = precioUnidad.toFixed(decimales);
                        } else {
                            // Fallback si el servicio falla
                            precioUnidadInput.value = (precio / unidades).toFixed(4);
                        }
                    } catch (error) {
                        console.error('Error al calcular precio por unidad:', error);
                        // Fallback en caso de error de red
                        precioUnidadInput.value = (precio / unidades).toFixed(4);
                    }
                }
            };

            unidadesInput.addEventListener('input', recalcularPrecioUnidadChollo);
            precioNuevoInput.addEventListener('input', recalcularPrecioUnidadChollo);
            
            // También recalcular cuando cambie el producto
            if (productoIdInput) {
                productoIdInput.addEventListener('change', recalcularPrecioUnidadChollo);
            }
            
            recalcularPrecioUnidadChollo();
        }

        function inicializarSlugAutomatico() {
            const form = document.getElementById('form-chollo');
            const tituloInput = document.querySelector('input[name="titulo"]');
            const slugInput = document.getElementById('slug');

            if (!form || !tituloInput || !slugInput) {
                return;
            }

            const esEdicion = form.dataset.esEdicion === '1';
            let slugEditadoManual = esEdicion && slugInput.value.trim() !== '';

            const generarSlug = (texto) => {
                return texto
                    .normalize('NFD')
                    .replace(/[\u0300-\u036f]/g, '')
                    .toLowerCase()
                    .replace(/[^a-z0-9]+/g, '-')
                    .replace(/^-+|-+$/g, '')
                    .replace(/-{2,}/g, '-');
            };

            const aplicarSlug = (texto) => {
                const slugGenerado = generarSlug(texto);
                slugInput.value = slugGenerado || 'chollo';
            };

            tituloInput.addEventListener('input', () => {
                if (slugEditadoManual) {
                    return;
                }
                aplicarSlug(tituloInput.value);
            });

            slugInput.addEventListener('input', (event) => {
                slugEditadoManual = true;
                const cursor = event.target.selectionStart;
                const valorOriginal = event.target.value;
                const slugNormalizado = generarSlug(valorOriginal);
                event.target.value = slugNormalizado;
                const posicion = cursor !== null ? Math.min(slugNormalizado.length, cursor) : null;
                if (posicion !== null) {
                    event.target.setSelectionRange(posicion, posicion);
                }
            });

            slugInput.addEventListener('blur', () => {
                if (slugInput.value.trim() === '') {
                    slugInput.value = 'chollo';
                }
            });

            if (!esEdicion && slugInput.value.trim() === '' && tituloInput.value.trim() !== '') {
                aplicarSlug(tituloInput.value);
            }
        }

        function inicializarTipoChollo() {
            const tipoSelect = document.getElementById('tipo');
            if (!tipoSelect) {
                return;
            }

            const campoProducto = document.getElementById('campo-producto');
            const mensajeTienda = document.getElementById('mensaje-tipo-tienda');
            const productoNombre = document.getElementById('producto_nombre');
            const productoId = document.getElementById('producto_id');
            const productoSugerencias = document.getElementById('producto_sugerencias');
            const descripcionInternaSeccion = document.getElementById('seccion-descripcion-interna');
            const precioNuevoInput = document.getElementById('precio_nuevo');

            const toggleProducto = () => {
                const tipoActual = tipoSelect.value;
                const requiereProducto = tipoActual !== 'tienda';
                const esTienda = tipoActual === 'tienda';

                if (campoProducto) {
                    campoProducto.classList.toggle('hidden', !requiereProducto);
                }

                if (mensajeTienda) {
                    mensajeTienda.classList.toggle('hidden', !esTienda);
                }

                if (productoNombre) {
                    if (requiereProducto) {
                        productoNombre.removeAttribute('disabled');
                        productoNombre.classList.remove('opacity-60', 'cursor-not-allowed');
                    } else {
                        productoNombre.value = '';
                        productoNombre.setAttribute('disabled', 'disabled');
                        productoNombre.classList.add('opacity-60', 'cursor-not-allowed');
                        productoNombre.classList.remove('border-green-500');
                    }
                }

                if (productoId) {
                    if (requiereProducto) {
                        productoId.removeAttribute('data-ignorar-validacion');
                    } else {
                        productoId.setAttribute('data-ignorar-validacion', '1');
                        productoId.value = '';
                        // Recalcular precio por unidad cuando se elimina el producto
                        if (typeof recalcularPrecioUnidadChollo === 'function') {
                            setTimeout(() => {
                                recalcularPrecioUnidadChollo();
                            }, 100);
                        }
                    }
                }

                if (!requiereProducto && productoSugerencias) {
                    productoSugerencias.classList.add('hidden');
                }

                if (descripcionInternaSeccion) {
                    descripcionInternaSeccion.classList.toggle('hidden', !esTienda);
                }

                if (precioNuevoInput) {
                    if (esTienda) {
                        precioNuevoInput.removeAttribute('required');
                        precioNuevoInput.required = false;
                    } else {
                        precioNuevoInput.setAttribute('required', 'required');
                        precioNuevoInput.required = true;
                    }
                }

                if (typeof actualizarEstadoBotonOfertas === 'function') {
                    actualizarEstadoBotonOfertas();
                }

                if (typeof renderOfertasTemporales === 'function') {
                    renderOfertasTemporales();
                }
            };

            tipoSelect.addEventListener('change', toggleProducto);
            toggleProducto();
        }

        function inicializarDescripcionInternaGestion() {
            const form = document.getElementById('form-chollo');
            const hiddenInput = document.getElementById('descripcion_interna');
            const container = document.getElementById('descripcion-interna-cupones');
            const addButton = document.getElementById('btn-agregar-cupon');
            const addButtonContainer = document.getElementById('btn-agregar-cupon-container');
            const selector = document.getElementById('descripcion_interna_selector');

            if (!form || !hiddenInput || !container || !addButton || !selector) {
                return;
            }

            const defaultCupones = Array.isArray(descripcionInternaDefaultData?.cupones)
                ? descripcionInternaDefaultData.cupones
                : [
                    { descuento: 0, sobrePrecioTotal: 0, cupon: '' },
                ];

            const parseDescripcionInterna = (valor) => {
                if (!valor || typeof valor !== 'string') {
                    return [];
                }

                const intentarParse = (texto) => {
                    try {
                        return JSON.parse(texto);
                    } catch (error) {
                        return null;
                    }
                };

                let parsed = intentarParse(valor);

                if (parsed === null && typeof valor === 'string') {
                    parsed = intentarParse(valor.trim());
                }

                if (parsed === null) {
                    return [];
                }

                if (typeof parsed === 'string') {
                    parsed = intentarParse(parsed);
                    if (parsed === null) {
                        return [];
                    }
                }

                if (Array.isArray(parsed)) {
                    return parsed;
                }

                if (parsed && typeof parsed === 'object') {
                    if (Array.isArray(parsed.cupones)) {
                        return parsed.cupones;
                    }
                    if (typeof parsed.cupones === 'string') {
                        const cuponesDesdeString = intentarParse(parsed.cupones);
                        if (Array.isArray(cuponesDesdeString)) {
                            return cuponesDesdeString;
                        }
                    }
                }

                return [];
            };
            
            // Función para obtener el tipo de descripción interna
            const obtenerTipoDescripcionInterna = (valor) => {
                if (!valor || typeof valor !== 'string') {
                    return null;
                }
                
                try {
                    const parsed = JSON.parse(valor);
                    if (parsed && typeof parsed === 'object' && parsed.tipo) {
                        return parsed.tipo;
                    }
                } catch (error) {
                    // Ignorar errores de parseo
                }
                
                return null;
            };

            const normalizarDatoParaEstado = (dato) => {
                if (!dato || typeof dato !== 'object') {
                    return { descuento: '', sobrePrecioTotal: '', cupon: '', tipo_descuento: 'euros' };
                }

                return {
                    descuento: dato.descuento !== undefined && dato.descuento !== null ? String(dato.descuento) : '',
                    sobrePrecioTotal: dato.sobrePrecioTotal !== undefined && dato.sobrePrecioTotal !== null ? String(dato.sobrePrecioTotal) : '',
                    cupon: dato.cupon !== undefined && dato.cupon !== null ? String(dato.cupon) : '',
                    tipo_descuento: dato.tipo_descuento !== undefined && dato.tipo_descuento !== null ? String(dato.tipo_descuento) : 'euros',
                };
            };

            const obtenerNumero = (valor) => {
                if (valor === null || valor === undefined) {
                    return 0;
                }

                const numero = Number.parseFloat(String(valor).replace(',', '.'));
                return Number.isFinite(numero) ? numero : 0;
            };

            let cuponesEstado = [];

            const cuponesIniciales = parseDescripcionInterna(descripcionInternaInicial);
            const tieneCupones = cuponesIniciales.length > 0;
            
            // Determinar el valor inicial del selector
            let tipoInicial = 'sin_configuracion';
            if (tieneCupones && descripcionInternaInicial && descripcionInternaInicial.trim() !== '') {
                const tipo = obtenerTipoDescripcionInterna(descripcionInternaInicial);
                if (tipo === '1_solo_cupon') {
                    tipoInicial = '1_solo_cupon';
                } else {
                    tipoInicial = 'cupon';
                }
            }
            selector.value = tipoInicial;

            if (tieneCupones) {
                cuponesEstado = cuponesIniciales.map(normalizarDatoParaEstado);
            } else {
                cuponesEstado = defaultCupones.map(normalizarDatoParaEstado);
            }

            // Función para mostrar/ocultar elementos de cupones
            const toggleCuponesVisibility = (mostrar, esSoloCupon = false) => {
                const avisoSoloCupon = document.getElementById('aviso-solo-cupon');
                
                if (mostrar) {
                    container.classList.remove('hidden');
                    if (esSoloCupon) {
                        // Ocultar botón de añadir cupón para "1 Solo cupón"
                        if (addButtonContainer) {
                            addButtonContainer.classList.add('hidden');
                        }
                        if (avisoSoloCupon) {
                            avisoSoloCupon.classList.remove('hidden');
                        }
                    } else {
                        // Mostrar botón de añadir cupón para "Cupones"
                        if (addButtonContainer) {
                            addButtonContainer.classList.remove('hidden');
                        }
                        if (avisoSoloCupon) {
                            avisoSoloCupon.classList.add('hidden');
                        }
                    }
                } else {
                    container.classList.add('hidden');
                    if (addButtonContainer) {
                        addButtonContainer.classList.add('hidden');
                    }
                    if (avisoSoloCupon) {
                        avisoSoloCupon.classList.add('hidden');
                    }
                }
            };

            // Event listener para el selector
            selector.addEventListener('change', () => {
                const valorSeleccionado = selector.value;
                const sinConfigInput = document.getElementById('descripcion_interna_sin_config');
                
                if (valorSeleccionado === 'sin_configuracion') {
                    toggleCuponesVisibility(false);
                    hiddenInput.value = '';
                    if (sinConfigInput) {
                        sinConfigInput.value = '1';
                    }
                } else if (valorSeleccionado === 'cupon') {
                    toggleCuponesVisibility(true, false);
                    if (sinConfigInput) {
                        sinConfigInput.value = '0';
                    }
                    if (cuponesEstado.length === 0) {
                        cuponesEstado = defaultCupones.map(normalizarDatoParaEstado);
                    }
                    render();
                } else if (valorSeleccionado === '1_solo_cupon') {
                    toggleCuponesVisibility(true, true);
                    if (sinConfigInput) {
                        sinConfigInput.value = '0';
                    }
                    // Solo permitir un cupón
                    if (cuponesEstado.length === 0) {
                        cuponesEstado = [{ descuento: '', sobrePrecioTotal: '', cupon: '', tipo_descuento: 'euros' }];
                    } else if (cuponesEstado.length > 1) {
                        cuponesEstado = [cuponesEstado[0]];
                    }
                    render();
                }
            });

            // Aplicar estado inicial
            const sinConfigInput = document.getElementById('descripcion_interna_sin_config');
            const valorInicial = selector.value;
            if (valorInicial === 'sin_configuracion') {
                toggleCuponesVisibility(false);
                hiddenInput.value = '';
                if (sinConfigInput) {
                    sinConfigInput.value = '1';
                }
            } else if (valorInicial === '1_solo_cupon') {
                toggleCuponesVisibility(true, true);
                if (sinConfigInput) {
                    sinConfigInput.value = '0';
                }
            } else {
                toggleCuponesVisibility(true, false);
                if (sinConfigInput) {
                    sinConfigInput.value = '0';
                }
            }

            const crearCuponVacio = () => ({ descuento: '', sobrePrecioTotal: '', cupon: '', tipo_descuento: 'euros' });

            const sincronizarHidden = () => {
                const sinConfigInput = document.getElementById('descripcion_interna_sin_config');
                
                // Si el selector está en "sin_configuracion", marcar como sin configuración
                if (selector.value === 'sin_configuracion') {
                    hiddenInput.value = '';
                    if (sinConfigInput) {
                        sinConfigInput.value = '1';
                    }
                    return;
                }

                // Si hay configuración, marcar como con configuración
                if (sinConfigInput) {
                    sinConfigInput.value = '0';
                }

                // Guardar cupones según el tipo seleccionado
                if (selector.value === '1_solo_cupon') {
                    // Para "1 Solo cupón", guardar con tipo
                    const cupon = cuponesEstado.length > 0 ? cuponesEstado[0] : crearCuponVacio();
                    const payload = {
                        tipo: '1_solo_cupon',
                        cupones: [{
                            descuento: obtenerNumero(cupon.descuento),
                            sobrePrecioTotal: obtenerNumero(cupon.sobrePrecioTotal),
                            cupon: (cupon.cupon ?? '').trim(),
                            tipo_descuento: cupon.tipo_descuento || 'euros',
                        }],
                    };
                    hiddenInput.value = JSON.stringify(payload, null, 4);
                } else {
                    // Para "Cupones", guardar normalmente
                    const payload = {
                        cupones: cuponesEstado.map((item) => ({
                            descuento: obtenerNumero(item.descuento),
                            sobrePrecioTotal: obtenerNumero(item.sobrePrecioTotal),
                            cupon: (item.cupon ?? '').trim(),
                        })),
                    };
                    hiddenInput.value = JSON.stringify(payload, null, 4);
                }
            };

            const eliminarCupon = (indice) => {
                if (cuponesEstado.length <= 1) {
                    return;
                }

                cuponesEstado.splice(indice, 1);
                render();
            };

            const crearFila = (datos, indice) => {
                const esPrimero = indice === 0;
                const esSoloCupon = selector.value === '1_solo_cupon';

                const fila = document.createElement('div');
                fila.className = 'flex flex-col md:flex-row md:items-center gap-3';
                fila.setAttribute('data-index', indice);
                fila.draggable = !esSoloCupon && cuponesEstado.length > 1;

                const crearInput = (labelTexto, value, opciones = {}) => {
                    const wrapper = document.createElement('div');
                    wrapper.className = 'md:flex-1';

                    const label = document.createElement('label');
                    label.className = esPrimero
                        ? 'block mb-1 font-medium text-gray-700 dark:text-gray-200'
                        : 'sr-only';
                    label.textContent = labelTexto;
                    wrapper.appendChild(label);

                    const input = document.createElement('input');
                    const tipoInput = opciones.type ?? 'number';
                    input.type = tipoInput;
                    if (tipoInput === 'number') {
                        input.step = opciones.step ?? '0.01';
                    }
                    if (opciones.min !== undefined) {
                        input.min = opciones.min;
                    }
                    input.value = value ?? '';
                    input.className = 'w-full px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border focus:outline-none focus:ring-2 focus:ring-blue-500';

                    if (opciones.placeholder) {
                        input.placeholder = opciones.placeholder;
                    }

                    input.addEventListener('input', (event) => {
                        cuponesEstado[indice][opciones.campo] = event.target.value;
                        sincronizarHidden();
                    });

                    wrapper.appendChild(input);

                    return wrapper;
                };

                const crearSelect = (labelTexto, value, campo, opcionesArray) => {
                    const wrapper = document.createElement('div');
                    wrapper.className = 'md:flex-1';

                    const label = document.createElement('label');
                    label.className = esPrimero
                        ? 'block mb-1 font-medium text-gray-700 dark:text-gray-200'
                        : 'sr-only';
                    label.textContent = labelTexto;
                    wrapper.appendChild(label);

                    const select = document.createElement('select');
                    select.className = 'w-full px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-white border focus:outline-none focus:ring-2 focus:ring-blue-500';
                    
                    opcionesArray.forEach(opt => {
                        const option = document.createElement('option');
                        option.value = opt.value;
                        option.textContent = opt.text;
                        if (opt.value === value) {
                            option.selected = true;
                        }
                        select.appendChild(option);
                    });

                    select.addEventListener('change', (event) => {
                        cuponesEstado[indice][campo] = event.target.value;
                        // Si cambia el tipo, actualizar el label del descuento
                        if (campo === 'tipo_descuento') {
                            render();
                        }
                        sincronizarHidden();
                    });

                    wrapper.appendChild(select);
                    return wrapper;
                };

                // Descuento: euros o porcentaje según el tipo
                if (esSoloCupon) {
                    const tipoDescuento = datos.tipo_descuento || 'euros';
                    const labelDescuento = tipoDescuento === 'euros' ? 'Descuento (€)' : 'Descuento (%)';
                    fila.appendChild(crearInput(labelDescuento, datos.descuento, { campo: 'descuento', min: '0', step: '0.01' }));
                    fila.appendChild(crearSelect('Tipo de descuento', tipoDescuento, 'tipo_descuento', [
                        { value: 'euros', text: 'Euros (€)' },
                        { value: 'porcentaje', text: 'Porcentaje (%)' }
                    ]));
                } else {
                    fila.appendChild(crearInput('Descuento (€)', datos.descuento, { campo: 'descuento', min: '0', step: '0.01' }));
                    fila.appendChild(crearInput('Sobre precio total (€)', datos.sobrePrecioTotal, { campo: 'sobrePrecioTotal', min: '0', step: '0.01' }));
                }
                
                fila.appendChild(crearInput('Cupón', datos.cupon, { campo: 'cupon', type: 'text', step: undefined, placeholder: 'Código' }));

                const acciones = document.createElement('div');
                acciones.className = 'flex items-center gap-2 md:justify-center md:self-auto h-full';

                // Solo mostrar botón eliminar y arrastrar si no es "1 Solo cupón" y hay más de un cupón
                if (!esSoloCupon && cuponesEstado.length > 1) {
                    // Icono de arrastrar
                    const iconoArrastrar = document.createElement('div');
                    iconoArrastrar.className = 'cursor-move text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300 flex items-center';
                    iconoArrastrar.innerHTML = `
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <circle cx="9" cy="5" r="1.5"></circle>
                            <circle cx="9" cy="12" r="1.5"></circle>
                            <circle cx="9" cy="19" r="1.5"></circle>
                            <circle cx="15" cy="5" r="1.5"></circle>
                            <circle cx="15" cy="12" r="1.5"></circle>
                            <circle cx="15" cy="19" r="1.5"></circle>
                        </svg>
                    `;
                    iconoArrastrar.setAttribute('draggable', 'false');
                    iconoArrastrar.title = 'Arrastra para reordenar';
                    acciones.appendChild(iconoArrastrar);

                    const botonEliminar = document.createElement('button');
                    botonEliminar.type = 'button';
                    botonEliminar.className = 'w-7 h-7 flex items-center justify-center rounded-full bg-red-600 hover:bg-red-700 text-white text-base font-semibold';
                    botonEliminar.innerHTML = '&minus;';
                    botonEliminar.addEventListener('click', () => eliminarCupon(indice));
                    acciones.appendChild(botonEliminar);
                }

                fila.appendChild(acciones);

                return fila;
            };

            const reordenarCupones = (indiceOrigen, indiceDestino) => {
                if (indiceOrigen === indiceDestino) {
                    return;
                }

                // Guardar el elemento a mover
                const cuponMovido = cuponesEstado[indiceOrigen];
                
                // Verificar si el destino es el último elemento
                const esUltimoElemento = indiceDestino === cuponesEstado.length - 1;
                
                // Eliminar el elemento del origen
                cuponesEstado.splice(indiceOrigen, 1);
                
                // Calcular el nuevo índice de destino después de eliminar
                let nuevoIndiceDestino = indiceDestino;
                if (indiceOrigen < indiceDestino) {
                    // Si arrastro hacia abajo, después de eliminar el destino se desplazó -1
                    if (esUltimoElemento) {
                        // Si era el último elemento, insertar al final del array
                        nuevoIndiceDestino = cuponesEstado.length;
                    } else {
                        // Si no era el último, insertar después del destino (usar indiceDestino)
                        nuevoIndiceDestino = indiceDestino;
                    }
                } else {
                    // Si arrastro hacia arriba, insertar antes del destino
                    nuevoIndiceDestino = indiceDestino;
                }
                
                // Insertar el elemento en la nueva posición
                cuponesEstado.splice(nuevoIndiceDestino, 0, cuponMovido);
                
                render();
            };

            const render = () => {
                container.innerHTML = '';

                cuponesEstado.forEach((cupon, indice) => {
                    const fila = crearFila(cupon, indice);
                    container.appendChild(fila);

                    // Añadir event listeners para drag and drop
                    if (fila.draggable) {
                        fila.addEventListener('dragstart', (e) => {
                            e.dataTransfer.effectAllowed = 'move';
                            e.dataTransfer.setData('text/html', fila.outerHTML);
                            fila.classList.add('opacity-50', 'bg-gray-200', 'dark:bg-gray-600');
                            fila.setAttribute('data-drag-index', indice);
                        });

                        fila.addEventListener('dragend', (e) => {
                            fila.classList.remove('opacity-50', 'bg-gray-200', 'dark:bg-gray-600');
                            // Limpiar clases de todas las filas
                            container.querySelectorAll('[data-index]').forEach(f => {
                                f.classList.remove('border-blue-500', 'border-2');
                            });
                        });

                        fila.addEventListener('dragover', (e) => {
                            e.preventDefault();
                            e.dataTransfer.dropEffect = 'move';
                            const filaArrastrada = container.querySelector('[data-drag-index]');
                            if (filaArrastrada && fila !== filaArrastrada) {
                                fila.classList.add('border-blue-500', 'border-2');
                            }
                        });

                        fila.addEventListener('dragleave', (e) => {
                            fila.classList.remove('border-blue-500', 'border-2');
                        });

                        fila.addEventListener('drop', (e) => {
                            e.preventDefault();
                            e.stopPropagation();
                            fila.classList.remove('border-blue-500', 'border-2');
                            
                            const filaArrastrada = container.querySelector('[data-drag-index]');
                            if (filaArrastrada) {
                                const indiceOrigen = parseInt(filaArrastrada.getAttribute('data-drag-index'));
                                const indiceDestino = parseInt(fila.getAttribute('data-index'));
                                
                                reordenarCupones(indiceOrigen, indiceDestino);
                            }
                        });
                    }
                });

                sincronizarHidden();
            };

            addButton.addEventListener('click', () => {
                cuponesEstado.push(crearCuponVacio());
                render();
            });

            form.addEventListener('submit', sincronizarHidden);

            // Hacer sincronizarHidden disponible globalmente para el listener del formulario
            window.sincronizarHidden = sincronizarHidden;

            render();
        }

        // ========= APLICAR FECHAS Y CUPONES =========
        function inicializarBotonAplicarFechasCupones() {
            const btnAplicarFechasCupones = document.getElementById('btn_aplicar_fechas_cupones');
            const btnInfoAplicarFechasCupones = document.getElementById('btn_info_aplicar_fechas_cupones');
            const tooltipInfoAplicar = document.getElementById('tooltip_info_aplicar');
            const tipoSelectPrincipal = document.getElementById('tipo');
            const selectorDescripcion = document.getElementById('descripcion_interna_selector');

            if (!btnAplicarFechasCupones || !btnInfoAplicarFechasCupones) {
                return;
            }

            function actualizarEstadoBotonAplicarFechasCupones() {
                const tipoActual = tipoSelectPrincipal ? tipoSelectPrincipal.value : 'producto';
                const configuracionActual = selectorDescripcion ? selectorDescripcion.value : 'sin_configuracion';

                const esTipoTienda = tipoActual === 'tienda';
                const tieneCupones = configuracionActual === 'cupon';

                const debeHabilitar = esTipoTienda && tieneCupones;

                btnAplicarFechasCupones.disabled = !debeHabilitar;
                btnInfoAplicarFechasCupones.disabled = debeHabilitar;

                if (debeHabilitar) {
                    btnAplicarFechasCupones.classList.remove('opacity-50', 'cursor-not-allowed');
                    btnAplicarFechasCupones.classList.add('bg-blue-600', 'hover:bg-blue-700');
                    btnInfoAplicarFechasCupones.classList.add('opacity-50', 'cursor-not-allowed');
                    btnInfoAplicarFechasCupones.classList.remove('bg-gray-400', 'hover:bg-gray-500');
                    if (tooltipInfoAplicar) {
                        tooltipInfoAplicar.classList.add('hidden');
                    }
                } else {
                    btnAplicarFechasCupones.classList.add('opacity-50', 'cursor-not-allowed');
                    btnAplicarFechasCupones.classList.remove('bg-blue-600', 'hover:bg-blue-700');
                    btnInfoAplicarFechasCupones.classList.remove('opacity-50', 'cursor-not-allowed');
                    btnInfoAplicarFechasCupones.classList.add('bg-gray-400', 'hover:bg-gray-500');
                }
            }

            // Event listeners para actualizar el estado del botón
            if (tipoSelectPrincipal) {
                tipoSelectPrincipal.addEventListener('change', actualizarEstadoBotonAplicarFechasCupones);
            }

            if (selectorDescripcion) {
                selectorDescripcion.addEventListener('change', actualizarEstadoBotonAplicarFechasCupones);
            }

            // Tooltip de información
            if (tooltipInfoAplicar) {
                btnInfoAplicarFechasCupones.addEventListener('mouseenter', () => {
                    if (btnInfoAplicarFechasCupones.disabled) {
                        tooltipInfoAplicar.classList.remove('hidden');
                    }
                });

                btnInfoAplicarFechasCupones.addEventListener('mouseleave', () => {
                    tooltipInfoAplicar.classList.add('hidden');
                });
            }

            // Event listener para aplicar fechas y cupones
            btnAplicarFechasCupones.addEventListener('click', async function() {
                const cholloId = document.getElementById('form-chollo')?.dataset.cholloId;
                if (!cholloId || cholloId === 'null') {
                    alert('Primero debes guardar el chollo antes de aplicar fechas y cupones.');
                    return;
                }

                // Obtener el valor del radio button seleccionado
                const mostrarSeleccionado = document.querySelector('input[name="aplicar_mostrar"]:checked');
                const valorMostrar = mostrarSeleccionado ? mostrarSeleccionado.value : 'si';

                if (!confirm(`¿Estás seguro de que quieres aplicar las fechas y cupones del chollo a todas las ofertas vinculadas? Esta acción actualizará las fechas de inicio y final, los códigos de cupón cuando coincida el descuento, y establecerá mostrar="${valorMostrar}" para todas las ofertas.`)) {
                    return;
                }

                btnAplicarFechasCupones.disabled = true;
                const textoOriginal = btnAplicarFechasCupones.textContent;
                btnAplicarFechasCupones.textContent = 'Procesando...';

                try {
                    const response = await fetch(`{{ url('panel-privado/chollos') }}/${cholloId}/aplicar-fechas-cupones`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            mostrar: valorMostrar
                        })
                    });

                    const data = await response.json();

                    if (data.success) {
                        let mensaje = `Proceso completado:\n`;
                        mensaje += `- Ofertas actualizadas: ${data.actualizadas}\n`;
                        mensaje += `- Ofertas sin coincidencia de cupón: ${data.sin_coincidencia}\n`;
                        mensaje += `- Errores: ${data.errores}`;

                        if (data.ofertas_sin_coincidencia && data.ofertas_sin_coincidencia.length > 0) {
                            mensaje += `\n\nOfertas sin coincidencia:\n`;
                            data.ofertas_sin_coincidencia.forEach(oferta => {
                                mensaje += `- Oferta ID ${oferta.id} (${oferta.tienda}): ${oferta.error || `Descuento ${oferta.descuento_actual}€ no coincide con ningún cupón`}\n`;
                            });
                        }

                        alert(mensaje);
                        if (typeof mostrarNotificacion === 'function') {
                            mostrarNotificacion(data.message, 'success');
                        }
                        
                        // Recargar la página para ver los cambios
                        window.location.reload();
                    } else {
                        alert(data.message || 'Error al aplicar fechas y cupones');
                        if (typeof mostrarNotificacion === 'function') {
                            mostrarNotificacion(data.message || 'Error al aplicar fechas y cupones', 'error');
                        }
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('Error al aplicar fechas y cupones. Por favor, intenta de nuevo.');
                    if (typeof mostrarNotificacion === 'function') {
                        mostrarNotificacion('Error al aplicar fechas y cupones', 'error');
                    }
                } finally {
                    btnAplicarFechasCupones.disabled = false;
                    btnAplicarFechasCupones.textContent = textoOriginal;
                    actualizarEstadoBotonAplicarFechasCupones();
                }
            });

            // Inicializar estado del botón
            actualizarEstadoBotonAplicarFechasCupones();
        }

        document.getElementById('btn_fecha_final_ahora').addEventListener('click', () => {
            const ahora = new Date();
            const offset = ahora.getTimezoneOffset();
            const local = new Date(ahora.getTime() - offset * 60000);
            document.getElementById('fecha_final').value = local.toISOString().slice(0, 16);
        });

        document.getElementById('btn_comprobada_ahora').addEventListener('click', () => {
            const ahora = new Date();
            const offset = ahora.getTimezoneOffset();
            const local = new Date(ahora.getTime() - offset * 60000);
            document.getElementById('comprobada').value = local.toISOString().slice(0, 16);
        });

        // ========= AVISOS =========
        let avisosActuales = [];
        let modoEdicionAviso = false;
        let avisoEditando = null;

        function cargarAvisos() {
            const avisoableId = document.getElementById('avisoable-id').value;
            if (!avisoableId || avisoableId === 'null') {
                avisosActuales = [];
                mostrarAvisos();
                return;
            }

            fetch(`{{ route('admin.avisos.get.elemento') }}?avisoable_type=App\\Models\\Chollo&avisoable_id=${avisoableId}`)
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        avisosActuales = data.avisos;
                        mostrarAvisos();
                    }
                })
                .catch(error => console.error('Error cargando avisos:', error));
        }

        function mostrarAvisos() {
            const lista = document.getElementById('lista-avisos');
            const vacio = document.getElementById('sin-avisos');

            if (avisosActuales.length === 0) {
                lista.innerHTML = '';
                vacio.classList.remove('hidden');
                return;
            }

            vacio.classList.add('hidden');
            lista.innerHTML = avisosActuales.map(aviso => `
                <div class="flex flex-col md:flex-row md:items-center justify-between p-3 bg-gray-700 dark:bg-gray-700 rounded-lg">
                    <div class="flex-1">
                        <p class="text-sm text-gray-200 dark:text-gray-200">${aviso.texto_aviso}</p>
                        <p class="text-xs text-gray-400 dark:text-gray-400 mt-1">
                            ${new Date(aviso.fecha_aviso).toLocaleString('es-ES')} - ${aviso.user.name}
                            ${aviso.oculto ? '<span class="ml-2 px-2 py-1 bg-gray-600 text-gray-200 text-xs rounded">Oculto</span>' : ''}
                        </p>
                    </div>
                    <div class="flex space-x-2 mt-3 md:mt-0 md:ml-4">
                        <button type="button" onclick="editarAviso(${aviso.id})" class="text-blue-400 hover:text-blue-300">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                            </svg>
                        </button>
                        <button type="button" onclick="eliminarAviso(${aviso.id})" class="text-red-400 hover:text-red-300">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            `).join('');
        }

        function mostrarModalNuevoAviso() {
            const avisoableId = document.getElementById('avisoable-id').value;
            if (!avisoableId || avisoableId === 'null') {
                alert('Guarda el chollo antes de añadir avisos.');
                return;
            }

            modoEdicionAviso = false;
            avisoEditando = null;
            document.getElementById('modal-titulo').textContent = 'Nuevo aviso';
            document.getElementById('aviso-id').value = '';
            document.getElementById('texto-aviso').value = '';
            document.getElementById('oculto').checked = false;
            const tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);
            tomorrow.setHours(0, 1, 0, 0);
            document.getElementById('fecha-aviso').value = tomorrow.toISOString().slice(0, 16);

            document.getElementById('modal-aviso').classList.remove('hidden');
        }

        function editarAviso(avisoId) {
            const aviso = avisosActuales.find(a => a.id === avisoId);
            if (!aviso) return;

            modoEdicionAviso = true;
            avisoEditando = aviso;
            document.getElementById('modal-titulo').textContent = 'Editar aviso';
            document.getElementById('aviso-id').value = aviso.id;
            document.getElementById('texto-aviso').value = aviso.texto_aviso;
            document.getElementById('fecha-aviso').value = aviso.fecha_aviso.slice(0, 16);
            document.getElementById('oculto').checked = aviso.oculto;

            document.getElementById('modal-aviso').classList.remove('hidden');
        }

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

        function guardarAviso() {
            const avisoableId = document.getElementById('avisoable-id').value;
            if (!avisoableId || avisoableId === 'null') {
                alert('Guarda el chollo antes de añadir avisos.');
                return;
            }

            const texto = document.getElementById('texto-aviso').value.trim();
            const fecha = document.getElementById('fecha-aviso').value;
            const oculto = document.getElementById('oculto').checked;

            if (!texto || !fecha) {
                alert('Completa todos los campos del aviso.');
                return;
            }

            const url = modoEdicionAviso
                ? `{{ url('panel-privado/avisos') }}/${avisoEditando.id}`
                : `{{ route('admin.avisos.store') }}`;

            const metodo = modoEdicionAviso ? 'PUT' : 'POST';

            fetch(url, {
                method: metodo,
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    texto_aviso: texto,
                    fecha_aviso: fecha,
                    avisoable_type: 'App\\Models\\Chollo',
                    avisoable_id: avisoableId,
                    oculto: oculto
                })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    cerrarModalAviso();
                    cargarAvisos();
                    mostrarNotificacion('Aviso guardado correctamente', 'success');
                } else {
                    alert('Error al guardar el aviso.');
                }
            })
            .catch(() => alert('Error al guardar el aviso.'));
        }

        function eliminarAviso(avisoId) {
            if (!confirm('¿Eliminar este aviso?')) return;

            fetch(`{{ url('panel-privado/avisos') }}/${avisoId}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    cargarAvisos();
                    mostrarNotificacion('Aviso eliminado correctamente', 'success');
                } else {
                    alert('Error al eliminar el aviso.');
                }
            })
            .catch(() => alert('Error al eliminar el aviso.'));
        }

        document.getElementById('modal-aviso').addEventListener('click', (e) => {
            if (e.target === e.currentTarget) cerrarModalAviso();
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') cerrarModalAviso();
        });

        function mostrarNotificacion(mensaje, tipo = 'info') {
            const notificacion = document.createElement('div');
            notificacion.className = `fixed top-4 right-4 p-4 rounded-lg shadow-lg z-50 max-w-sm ${
                tipo === 'success' ? 'bg-green-500 text-white' :
                tipo === 'error' ? 'bg-red-500 text-white' :
                'bg-blue-500 text-white'
            }`;
            notificacion.textContent = mensaje;
            document.body.appendChild(notificacion);
            setTimeout(() => notificacion.remove(), 4000);
        }

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

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const btnAbrirModal = document.getElementById('btn_abrir_modal_oferta');
        if (!btnAbrirModal) {
            return;
        }

        const modal = document.getElementById('modal-oferta-chollo');
        const btnCerrarModal = document.getElementById('btn_cerrar_modal_oferta');
        const btnCancelarModal = document.getElementById('btn_cancelar_modal_oferta');
        const formModal = document.getElementById('form-modal-oferta');

        modalProductoNombre = document.getElementById('modal_producto_nombre');
        modalProductoId = document.getElementById('modal_producto_id');
        const modalProductoSugerencias = document.getElementById('modal_producto_sugerencias');
        const modalProductoResumen = document.getElementById('modal_producto_resumen');

        const modalTiendaNombre = document.getElementById('modal_tienda_nombre');
        const modalTiendaId = document.getElementById('modal_tienda_id');
        const modalTiendaSugerencias = document.getElementById('modal_tienda_sugerencias');

        const modalUnidades = document.getElementById('modal_unidades');
        const modalPrecioTotal = document.getElementById('modal_precio_total');
        const modalPrecioUnidad = document.getElementById('modal_precio_unidad');
        const modalMostrar = document.getElementById('modal_mostrar');
        const modalUrl = document.getElementById('modal_url');
        const modalVariante = document.getElementById('modal_variante');
        const modalSelectDescuentos = document.getElementById('modal_select_descuentos');
        const modalCuponCamposContainer = document.getElementById('modal_cupon_campos_container');
        const modalCuponCodigo = document.getElementById('modal_cupon_codigo');
        const modalCuponCantidad = document.getElementById('modal_cupon_cantidad');
        const modalFrecuenciaValor = document.getElementById('modal_frecuencia_valor');
        const modalFrecuenciaUnidad = document.getElementById('modal_frecuencia_unidad');
        const modalFechaInicio = document.getElementById('modal_fecha_inicio');
        const modalFechaFinal = document.getElementById('modal_fecha_final');
        const modalComprobada = document.getElementById('modal_comprobada');
        const modalFrecuenciaCholloValor = document.getElementById('modal_frecuencia_chollo_valor');
        const modalFrecuenciaCholloUnidad = document.getElementById('modal_frecuencia_chollo_unidad');
        const modalAnotaciones = document.getElementById('modal_anotaciones');

        const cholloFechaInicioInput = document.getElementById('fecha_inicio');
        const cholloFechaFinalInput = document.getElementById('fecha_final');
        const tipoSelectPrincipal = document.getElementById('tipo');

        const ofertasTemporalesInput = document.getElementById('ofertas_temporales_input');
        const ofertasTemporalesLista = document.getElementById('ofertas_temporales_lista');

        let ofertasTemporales = @json($ofertasTemporalesIniciales);

        let tiendasDisponibles = null;
        let tiendasFiltradasModal = [];
        let timeoutBusquedaTiendaModal = null;
        let timeoutBusquedaProductoModal = null;
        let indiceSeleccionadoTiendaModal = -1;
        productosModalActuales = [];
        indiceSeleccionadoProductoModal = -1;

        function toggleModalCuponCampos() {
            if (!modalSelectDescuentos || !modalCuponCamposContainer || !modalCuponCodigo || !modalCuponCantidad) {
                return;
            }

            if (modalSelectDescuentos.value === 'cupon') {
                modalCuponCamposContainer.classList.remove('hidden');
                modalCuponCodigo.required = true;
                modalCuponCantidad.required = true;
            } else {
                modalCuponCamposContainer.classList.add('hidden');
                modalCuponCodigo.required = false;
                modalCuponCantidad.required = false;
                modalCuponCodigo.value = '';
                modalCuponCantidad.value = '';
            }
        }

        if (modalSelectDescuentos) {
            modalSelectDescuentos.addEventListener('change', toggleModalCuponCampos);
            toggleModalCuponCampos();
        }

        const tiendasEndpoint = '{{ route("admin.ofertas.tiendas.disponibles") }}';
        if (!modal || !formModal) {
            console.warn('Modal de oferta o formulario no encontrados en el DOM.');
            return;
        }

        function obtenerValorElemento(id) {
            const elemento = document.getElementById(id);
            return elemento ? elemento.value : '';
        }

        const formatoFechaAhora = () => {
            const ahora = new Date();
            const offset = ahora.getTimezoneOffset();
            const local = new Date(ahora.getTime() - offset * 60 * 1000);
            return local.toISOString().slice(0, 16);
        };

        function actualizarCamposHidden() {
            ofertasTemporalesInput.value = JSON.stringify(ofertasTemporales);
        }

        function obtenerCantidadOfertasExistentesActivas() {
            const existentes = document.querySelectorAll('[data-oferta-id]');
            return existentes.length;
        }

        function obtenerCantidadOfertasTemporales() {
            return ofertasTemporales.length;
        }

        function obtenerCantidadTotalOfertas() {
            return obtenerCantidadOfertasExistentesActivas() + obtenerCantidadOfertasTemporales();
        }

        function actualizarEstadoBotonOfertas() {
            if (!btnAbrirModal) {
                return;
            }

            const tipoActual = tipoSelectPrincipal ? tipoSelectPrincipal.value : 'producto';
            const limite = tipoActual === 'oferta' ? 1 : Infinity;
            const cantidadActual = obtenerCantidadTotalOfertas();
            const debeDeshabilitar = cantidadActual >= limite;

            btnAbrirModal.disabled = debeDeshabilitar;
            btnAbrirModal.classList.toggle('opacity-60', debeDeshabilitar);
            btnAbrirModal.classList.toggle('cursor-not-allowed', debeDeshabilitar);

            if (debeDeshabilitar && tipoActual === 'oferta') {
                btnAbrirModal.setAttribute('title', 'Los chollos de tipo oferta solo pueden tener una oferta vinculada.');
            } else {
                btnAbrirModal.removeAttribute('title');
            }
        }

        function renderOfertasTemporales() {
            ofertasTemporalesLista.innerHTML = '';

            if (!ofertasTemporales.length) {
                const vacio = document.createElement('div');
                vacio.className = 'text-sm text-gray-500 dark:text-gray-400';
                vacio.textContent = 'No hay ofertas temporales añadidas.';
                ofertasTemporalesLista.appendChild(vacio);

                if (tipoSelectPrincipal && tipoSelectPrincipal.value === 'oferta' && obtenerCantidadTotalOfertas() >= 1) {
                    const nota = document.createElement('div');
                    nota.className = 'text-xs text-blue-600 dark:text-blue-300 mt-1';
                    nota.textContent = 'Los chollos de tipo oferta solo permiten una oferta vinculada.';
                    ofertasTemporalesLista.appendChild(nota);
                }

                actualizarEstadoBotonOfertas();
                return;
            }

            ofertasTemporales.forEach((oferta, index) => {
                const card = document.createElement('div');
                card.className = 'border border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-4 bg-white/60 dark:bg-gray-700/40 flex flex-col md:flex-row md:items-start md:justify-between gap-4';

                const info = document.createElement('div');
                info.className = 'space-y-1 text-sm text-gray-700 dark:text-gray-200';
                const tiendaNombre = oferta.tienda_nombre ?? 'Sin tienda';
                const inputProductoPrincipal = document.getElementById('producto_nombre');
                let productoNombre = oferta.producto_nombre;
                if (!productoNombre) {
                    productoNombre = inputProductoPrincipal ? inputProductoPrincipal.value : '';
                }
                if (!productoNombre) {
                    productoNombre = 'Producto del chollo';
                }
                info.innerHTML = `
                    <div class="font-semibold text-base text-gray-900 dark:text-white">
                        ${tiendaNombre} <span class="ml-2 text-xs px-2 py-0.5 rounded bg-yellow-500/20 text-yellow-600 dark:text-yellow-300">Temporal</span>
                    </div>
                    <div>
                        Producto: <span class="font-semibold">${productoNombre}</span>
                    </div>
                    <div>
                        Precio total: <span class="font-semibold">${Number(oferta.precio_total ?? 0).toFixed(2)}€</span> ·
                        Unidades: ${Number(oferta.unidades ?? 0).toFixed(2)} ·
                        Precio/unidad: ${Number(oferta.precio_unidad ?? 0).toFixed(4)}€
                    </div>
                    <div>
                        Actualización precio: cada ${Number(oferta.frecuencia_valor ?? 0).toFixed(1)} ${oferta.frecuencia_unidad} ·
                        Comprobación chollo: cada ${Number(oferta.frecuencia_chollo_valor ?? 0).toFixed(1)} ${oferta.frecuencia_chollo_unidad}
                    </div>
                    <div>
                        Inicio: ${oferta.fecha_inicio ? oferta.fecha_inicio.replace('T', ' ') : '—'} ·
                        Fin: ${oferta.fecha_final ? oferta.fecha_final.replace('T', ' ') : '—'}
                    </div>
                    <div>URL: <span class="break-all">${oferta.url}</span></div>
                `;
                card.appendChild(info);

                const acciones = document.createElement('div');
                acciones.className = 'flex items-center gap-2 shrink-0';
                const btnEliminar = document.createElement('button');
                btnEliminar.type = 'button';
                btnEliminar.className = 'px-3 py-2 text-sm font-medium rounded-md bg-red-600 text-white hover:bg-red-700';
                btnEliminar.dataset.index = index;
                btnEliminar.textContent = 'Quitar';
                acciones.appendChild(btnEliminar);
                card.appendChild(acciones);

                ofertasTemporalesLista.appendChild(card);
            });

            ofertasTemporalesLista.querySelectorAll('button[data-index]').forEach(btn => {
                btn.addEventListener('click', (event) => {
                    const index = parseInt(event.currentTarget.dataset.index, 10);
                    if (!isNaN(index)) {
                        ofertasTemporales.splice(index, 1);
                        actualizarCamposHidden();
                        renderOfertasTemporales();
                    }
                });
            });

            if (tipoSelectPrincipal && tipoSelectPrincipal.value === 'oferta' && obtenerCantidadTotalOfertas() >= 1) {
                const nota = document.createElement('div');
                nota.className = 'text-xs text-blue-600 dark:text-blue-300';
                nota.textContent = 'Los chollos de tipo oferta solo permiten una oferta vinculada.';
                ofertasTemporalesLista.appendChild(nota);
            }

            actualizarEstadoBotonOfertas();
        }


        async function cargarTiendas() {
            if (Array.isArray(tiendasDisponibles)) {
                return tiendasDisponibles;
            }

            try {
                const response = await fetch(tiendasEndpoint);
                if (!response.ok) {
                    throw new Error('Respuesta inválida');
                }
                tiendasDisponibles = await response.json();
            } catch (error) {
                console.error('Error al cargar tiendas disponibles', error);
                tiendasDisponibles = [];
            }

            return tiendasDisponibles;
        }

        function ocultarSugerenciasTiendaModal() {
            if (!modalTiendaSugerencias) {
                return;
            }
            modalTiendaSugerencias.classList.add('hidden');
            modalTiendaSugerencias.innerHTML = '';
            indiceSeleccionadoTiendaModal = -1;
            tiendasFiltradasModal = [];
        }

        function mostrarSugerenciasTiendaModal(tiendas) {
            if (!modalTiendaSugerencias) {
                return;
            }
            modalTiendaSugerencias.innerHTML = '';
            indiceSeleccionadoTiendaModal = -1;
            tiendasFiltradasModal = tiendas;

            if (!tiendas.length) {
                modalTiendaSugerencias.innerHTML = '<div class="px-4 py-2 text-gray-500 dark:text-gray-400">No se encontraron tiendas</div>';
                modalTiendaSugerencias.classList.remove('hidden');
                return;
            }

            tiendas.forEach((tienda, index) => {
                const div = document.createElement('div');
                div.className = 'px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 cursor-pointer text-gray-900 dark:text-white border-b border-gray-200 dark:border-gray-600 last:border-b-0';
                div.dataset.index = index;
                div.textContent = tienda.nombre;

                div.addEventListener('mouseenter', () => {
                    modalTiendaSugerencias.querySelectorAll('[data-index]').forEach(item => item.classList.remove('bg-blue-100', 'dark:bg-blue-700'));
                    div.classList.add('bg-blue-100', 'dark:bg-blue-700');
                    indiceSeleccionadoTiendaModal = index;
                });

                div.addEventListener('click', () => {
                    seleccionarTiendaModal(tienda);
                });

                modalTiendaSugerencias.appendChild(div);
            });

            const first = modalTiendaSugerencias.querySelector('[data-index="0"]');
            if (first) {
                first.classList.add('bg-blue-100', 'dark:bg-blue-700');
                indiceSeleccionadoTiendaModal = 0;
            }

            modalTiendaSugerencias.classList.remove('hidden');
        }

        function seleccionarTiendaModal(tienda) {
            if (!modalTiendaId || !modalTiendaNombre) {
                return;
            }
            modalTiendaId.value = tienda.id;
            modalTiendaNombre.value = tienda.nombre;
            modalTiendaNombre.classList.add('border-green-500');
            ocultarSugerenciasTiendaModal();
        }

        function navegarSugerenciasTiendaModal(direccion) {
            if (!modalTiendaSugerencias) {
                return;
            }
            const elementos = modalTiendaSugerencias.querySelectorAll('[data-index]');
            if (!elementos.length) {
                return;
            }

            elementos.forEach(elemento => elemento.classList.remove('bg-blue-100', 'dark:bg-blue-700'));

            if (direccion === 'arriba') {
                indiceSeleccionadoTiendaModal = indiceSeleccionadoTiendaModal <= 0 ? elementos.length - 1 : indiceSeleccionadoTiendaModal - 1;
            } else if (direccion === 'abajo') {
                indiceSeleccionadoTiendaModal = indiceSeleccionadoTiendaModal >= elementos.length - 1 ? 0 : indiceSeleccionadoTiendaModal + 1;
            }

            const elemento = elementos[indiceSeleccionadoTiendaModal];
            if (elemento) {
                elemento.classList.add('bg-blue-100', 'dark:bg-blue-700');
                elemento.scrollIntoView({ block: 'nearest' });
            }
        }

        function seleccionarTiendaModalResaltada() {
            if (!modalTiendaId || !modalTiendaNombre) {
                return;
            }
            if (indiceSeleccionadoTiendaModal < 0 || !tiendasFiltradasModal[indiceSeleccionadoTiendaModal]) {
                return;
            }
            seleccionarTiendaModal(tiendasFiltradasModal[indiceSeleccionadoTiendaModal]);
        }

        if (modalProductoNombre && modalProductoId) {
            modalProductoNombre.addEventListener('input', function(e) {
                const query = e.target.value.trim();
                modalProductoId.value = '';
                modalProductoNombre.classList.remove('border-green-500');

                if (timeoutBusquedaProductoModal) {
                    clearTimeout(timeoutBusquedaProductoModal);
                }

                if (query.length < 2) {
                    ocultarSugerenciasProductoModal();
                    return;
                }

                timeoutBusquedaProductoModal = setTimeout(() => {
                    buscarProductosModal(query);
                }, 200);
            });

            modalProductoNombre.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    ocultarSugerenciasProductoModal();
                    modalProductoNombre.blur();
                } else if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    navegarSugerenciasProductoModal('abajo');
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    navegarSugerenciasProductoModal('arriba');
                } else if (e.key === 'Enter') {
                    e.preventDefault();
                    seleccionarProductoModalResaltada();
                }
            });
        }

        if (modalTiendaNombre && modalTiendaId && modalTiendaSugerencias) {
        modalTiendaNombre.addEventListener('input', async function(e) {
            const query = e.target.value.trim();
            modalTiendaId.value = '';
            modalTiendaNombre.classList.remove('border-green-500');

            if (timeoutBusquedaTiendaModal) {
                clearTimeout(timeoutBusquedaTiendaModal);
            }

            if (!query) {
                ocultarSugerenciasTiendaModal();
                return;
            }

            timeoutBusquedaTiendaModal = setTimeout(async () => {
                await cargarTiendas();
                const resultados = (tiendasDisponibles || [])
                    .filter(tienda => tienda.nombre.toLowerCase().includes(query.toLowerCase()))
                    .slice(0, 10);
                mostrarSugerenciasTiendaModal(resultados);
            }, 200);
        });

        modalTiendaNombre.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                ocultarSugerenciasTiendaModal();
                modalTiendaNombre.blur();
            } else if (e.key === 'ArrowDown') {
                e.preventDefault();
                navegarSugerenciasTiendaModal('abajo');
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                navegarSugerenciasTiendaModal('arriba');
            } else if (e.key === 'Enter') {
                e.preventDefault();
                seleccionarTiendaModalResaltada();
            }
        });
        }

        async function actualizarPrecioUnidadModal() {
            const unidades = parseFloat(modalUnidades.value);
            const precioTotal = parseFloat(modalPrecioTotal.value);
            const productoId = modalProductoId ? modalProductoId.value : '';

            // Validar que tenemos los datos necesarios
            if (!productoId || isNaN(precioTotal) || precioTotal < 0 || isNaN(unidades) || unidades <= 0) {
                // Fallback: cálculo simple si no hay producto o datos inválidos
                if (!isNaN(unidades) && unidades > 0 && !isNaN(precioTotal)) {
                    const resultado = (precioTotal / unidades).toFixed(4);
                    modalPrecioUnidad.value = resultado;
                } else {
                    modalPrecioUnidad.value = '';
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
                    modalPrecioUnidad.value = precioUnidad.toFixed(decimales);
                } else {
                    // Fallback si el servicio falla
                    if (!isNaN(unidades) && unidades > 0 && !isNaN(precioTotal)) {
                        const resultado = (precioTotal / unidades).toFixed(4);
                        modalPrecioUnidad.value = resultado;
                    }
                }
            } catch (error) {
                console.error('Error al calcular precio por unidad:', error);
                // Fallback en caso de error de red
                if (!isNaN(unidades) && unidades > 0 && !isNaN(precioTotal)) {
                    const resultado = (precioTotal / unidades).toFixed(4);
                    modalPrecioUnidad.value = resultado;
                }
            }
        }

        if (modalUnidades) {
            modalUnidades.addEventListener('input', actualizarPrecioUnidadModal);
        }
        if (modalPrecioTotal) {
            modalPrecioTotal.addEventListener('input', actualizarPrecioUnidadModal);
        }

        function establecerValoresPorDefecto() {
            if (modalProductoId) {
                modalProductoId.value = '';
            }
            if (modalProductoNombre) {
                modalProductoNombre.value = '';
                modalProductoNombre.classList.remove('border-green-500');
            }
            actualizarResumenProductoModal('—');
            ocultarSugerenciasProductoModal();

            if (modalTiendaId) {
                modalTiendaId.value = '';
            }
            if (modalTiendaNombre) {
                modalTiendaNombre.value = '';
                modalTiendaNombre.classList.remove('border-green-500');
            }
            ocultarSugerenciasTiendaModal();

            if (modalUnidades) modalUnidades.value = '';
            if (modalPrecioTotal) modalPrecioTotal.value = '';
            if (modalPrecioUnidad) modalPrecioUnidad.value = '';
            if (modalMostrar) modalMostrar.value = 'si';
            if (modalUrl) modalUrl.value = '';
            if (modalVariante) modalVariante.value = '';
            if (modalSelectDescuentos) {
                modalSelectDescuentos.value = '';
            }
            if (modalCuponCodigo) modalCuponCodigo.value = '';
            if (modalCuponCantidad) modalCuponCantidad.value = '';
            toggleModalCuponCampos();
            if (modalFrecuenciaValor) modalFrecuenciaValor.value = '99999';
            if (modalFrecuenciaUnidad) modalFrecuenciaUnidad.value = 'dias';
            if (modalFrecuenciaCholloValor) modalFrecuenciaCholloValor.value = '1';
            if (modalFrecuenciaCholloUnidad) modalFrecuenciaCholloUnidad.value = 'dias';
            if (modalAnotaciones) modalAnotaciones.value = '';

            if (modalFechaInicio) {
                const fechaBase = cholloFechaInicioInput ? cholloFechaInicioInput.value : '';
                modalFechaInicio.value = fechaBase ? fechaBase : formatoFechaAhora();
            }
            if (modalFechaFinal) {
                const fechaFinalBase = cholloFechaFinalInput ? cholloFechaFinalInput.value : '';
                modalFechaFinal.value = fechaFinalBase || '';
            }
            if (modalComprobada) modalComprobada.value = formatoFechaAhora();
        }

        function abrirModal() {
            if (!modal) {
                console.error('❌ No se encontró el modal de oferta asociado');
                return;
            }

            establecerValoresPorDefecto();
            actualizarResumenProductoModal('Selecciona un producto para esta oferta');

            modal.classList.remove('hidden');
            if (modalProductoNombre) {
                modalProductoNombre.focus();
            } else if (modalTiendaNombre) {
                modalTiendaNombre.focus();
            }
        }

        function cerrarModal() {
            modal.classList.add('hidden');
        }

        btnAbrirModal.addEventListener('click', abrirModal);
        if (btnCerrarModal) {
            btnCerrarModal.addEventListener('click', cerrarModal);
        }
        if (btnCancelarModal) {
            btnCancelarModal.addEventListener('click', (e) => {
                e.preventDefault();
                cerrarModal();
            });
        }
        formModal.addEventListener('submit', function(e) {
            e.preventDefault();

            const tipoActual = tipoSelectPrincipal ? tipoSelectPrincipal.value : 'producto';
            const cantidadActual = obtenerCantidadTotalOfertas();
            if (tipoActual === 'oferta' && cantidadActual >= 1) {
                if (typeof mostrarNotificacion === 'function') {
                    mostrarNotificacion('Solo puedes vincular una oferta cuando el chollo es de tipo oferta.', 'error');
                } else {
                    alert('Los chollos de tipo oferta solo pueden tener una oferta vinculada.');
                }
                return;
            }

            const productoSeleccionadoModal = modalProductoId ? modalProductoId.value : '';
            const productoIdOferta = parseInt(productoSeleccionadoModal || '0', 10);

            if (!productoIdOferta) {
                alert('Selecciona un producto válido para la oferta.');
                if (modalProductoNombre) {
                    modalProductoNombre.focus();
                }
                return;
            }

            if (!modalTiendaId || !modalTiendaId.value) {
                alert('Selecciona una tienda válida.');
                if (modalTiendaNombre) {
                    modalTiendaNombre.focus();
                }
                return;
            }

            const unidades = modalUnidades ? parseFloat(modalUnidades.value) : NaN;
            const precioTotal = modalPrecioTotal ? parseFloat(modalPrecioTotal.value) : NaN;
            const frecuenciaValor = modalFrecuenciaValor ? parseFloat(modalFrecuenciaValor.value) : NaN;
            const frecuenciaCholloValor = modalFrecuenciaCholloValor ? parseFloat(modalFrecuenciaCholloValor.value) : NaN;
            const url = modalUrl ? modalUrl.value.trim() : '';
            const fechaInicio = modalFechaInicio ? modalFechaInicio.value : '';
            let descuentosValor = '';
            if (modalSelectDescuentos) {
                const seleccion = modalSelectDescuentos.value;
                if (seleccion === 'cupon') {
                    const codigo = modalCuponCodigo ? modalCuponCodigo.value.trim() : '';
                    const cantidad = modalCuponCantidad ? modalCuponCantidad.value : '';

                    if (!codigo) {
                        alert('Introduce el código del cupón.');
                        if (modalCuponCodigo) modalCuponCodigo.focus();
                        return;
                    }

                    if (!cantidad || Number(cantidad) <= 0) {
                        alert('Introduce una cantidad válida para el cupón.');
                        if (modalCuponCantidad) modalCuponCantidad.focus();
                        return;
                    }

                    descuentosValor = `cupon;${codigo};${cantidad}`;
                } else {
                    descuentosValor = seleccion;
                }
            }

            if (isNaN(unidades) || unidades <= 0) {
                alert('Introduce un número de unidades válido.');
                if (modalUnidades) modalUnidades.focus();
                return;
            }

            if (isNaN(precioTotal) || precioTotal < 0) {
                alert('Introduce un precio total válido.');
                if (modalPrecioTotal) modalPrecioTotal.focus();
                return;
            }

            if (!url) {
                alert('La URL es obligatoria.');
                if (modalUrl) modalUrl.focus();
                return;
            }

            if (!fechaInicio) {
                alert('La fecha de inicio es obligatoria.');
                if (modalFechaInicio) modalFechaInicio.focus();
                return;
            }

            if (isNaN(frecuenciaValor) || frecuenciaValor <= 0) {
                alert('Introduce una frecuencia de actualización válida.');
                if (modalFrecuenciaValor) modalFrecuenciaValor.focus();
                return;
            }

            if (isNaN(frecuenciaCholloValor) || frecuenciaCholloValor <= 0) {
                alert('Introduce una frecuencia de comprobación válida.');
                if (modalFrecuenciaCholloValor) modalFrecuenciaCholloValor.focus();
                return;
            }

            let productoNombreOferta = '';
            if (modalProductoNombre && typeof modalProductoNombre.value === 'string') {
                productoNombreOferta = modalProductoNombre.value.trim();
            }
            if (!productoNombreOferta && productosModalActuales.length) {
                const productoCoincidente = productosModalActuales.find(p => String(p.id) === String(productoIdOferta));
                if (productoCoincidente) {
                    productoNombreOferta = productoCoincidente.texto_completo ?? productoCoincidente.nombre ?? '';
                }
            }
            if (!productoNombreOferta) {
                productoNombreOferta = 'Producto seleccionado';
            }
            if (modalProductoId) {
                modalProductoId.value = productoIdOferta;
            }

            const ofertaTemporal = {
                producto_id: productoIdOferta,
                producto_nombre: productoNombreOferta,
                tienda_id: parseInt(modalTiendaId ? modalTiendaId.value : '0', 10),
                tienda_nombre: modalTiendaNombre ? modalTiendaNombre.value.trim() : '',
                unidades: parseFloat(unidades.toFixed(2)),
                precio_total: parseFloat(precioTotal.toFixed(2)),
                precio_unidad: modalPrecioUnidad && modalPrecioUnidad.value ? parseFloat(parseFloat(modalPrecioUnidad.value).toFixed(4)) : parseFloat((precioTotal / unidades).toFixed(4)),
                mostrar: modalMostrar ? modalMostrar.value : 'si',
                url: url,
                variante: modalVariante ? modalVariante.value.trim() : '',
                descuentos: descuentosValor,
                frecuencia_valor: parseFloat(frecuenciaValor.toFixed(1)),
                frecuencia_unidad: modalFrecuenciaUnidad ? modalFrecuenciaUnidad.value : 'dias',
                fecha_inicio: fechaInicio,
                fecha_final: modalFechaFinal ? (modalFechaFinal.value || null) : null,
                comprobada: modalComprobada ? (modalComprobada.value || null) : null,
                frecuencia_chollo_valor: parseFloat(frecuenciaCholloValor.toFixed(1)),
                frecuencia_chollo_unidad: modalFrecuenciaCholloUnidad ? modalFrecuenciaCholloUnidad.value : 'dias',
                anotaciones_internas: modalAnotaciones ? modalAnotaciones.value.trim() : '',
            };

            ofertasTemporales.push(ofertaTemporal);
            actualizarCamposHidden();
            renderOfertasTemporales();
            cerrarModal();

            if (typeof mostrarNotificacion === 'function') {
                mostrarNotificacion('Oferta añadida al chollo', 'success');
            }
        });

        renderOfertasTemporales();
        actualizarCamposHidden();
        actualizarEstadoBotonOfertas();

        if (tipoSelectPrincipal) {
            tipoSelectPrincipal.addEventListener('change', () => {
                actualizarEstadoBotonOfertas();
                renderOfertasTemporales();
            });
        }

        window.ocultarSugerenciasProductoModal = ocultarSugerenciasProductoModal;
        window.ocultarSugerenciasTiendaModal = ocultarSugerenciasTiendaModal;
    });
    </script>

    <!-- Script para gestión de imágenes con pestañas -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const formularioChollo = document.getElementById('form-chollo');
        if (!formularioChollo) {
            return;
        }

        const tabUpload = document.getElementById('tab-upload');
        const tabManual = document.getElementById('tab-manual');
        const contentUpload = document.getElementById('content-upload');
        const contentManual = document.getElementById('content-manual');

        const tabUrl = document.getElementById('tab-url');
        const contentUrl = document.getElementById('content-url');

        function cambiarTab(tabActiva, contenidoActivo) {
            contentUpload.classList.add('hidden');
            contentManual.classList.add('hidden');
            if (contentUrl) contentUrl.classList.add('hidden');

            tabUpload.classList.remove('border-blue-500', 'text-blue-600', 'dark:text-blue-400');
            tabUpload.classList.add('border-transparent', 'text-gray-500', 'dark:text-gray-400');
            tabManual.classList.remove('border-blue-500', 'text-blue-600', 'dark:text-blue-400');
            tabManual.classList.add('border-transparent', 'text-gray-500', 'dark:text-gray-400');
            if (tabUrl) {
                tabUrl.classList.remove('border-blue-500', 'text-blue-600', 'dark:text-blue-400');
                tabUrl.classList.add('border-transparent', 'text-gray-500', 'dark:text-gray-400');
            }

            contenidoActivo.classList.remove('hidden');

            tabActiva.classList.remove('border-transparent', 'text-gray-500', 'dark:text-gray-400');
            tabActiva.classList.add('border-blue-500', 'text-blue-600', 'dark:text-blue-400');
        }

        if (tabUpload) {
            tabUpload.addEventListener('click', () => cambiarTab(tabUpload, contentUpload));
        }
        if (tabManual) {
            tabManual.addEventListener('click', () => cambiarTab(tabManual, contentManual));
        }
        if (tabUrl && contentUrl) {
            tabUrl.addEventListener('click', () => cambiarTab(tabUrl, contentUrl));
        }

        function configurarUpload(tipo) {
            const carpetaSelect = document.getElementById(`carpeta-imagen-${tipo}`);
            const fileInput = document.getElementById(`file-imagen-${tipo}`);
            const btnSeleccionar = document.getElementById(`btn-seleccionar-${tipo}`);
            const dropZone = document.getElementById(`drop-zone-${tipo}`);
            const nombreArchivo = document.getElementById(`nombre-archivo-${tipo}`);
            const preview = document.getElementById(`preview-upload-${tipo}`);
            const rutaImagen = document.getElementById(`ruta-imagen-${tipo}`);
            const rutaCompleta = document.getElementById(`ruta-completa-${tipo}`);

            if (!carpetaSelect || !fileInput || !btnSeleccionar || !dropZone || !nombreArchivo || !preview || !rutaImagen || !rutaCompleta) {
                return;
            }

            btnSeleccionar.addEventListener('click', () => fileInput.click());

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

            function procesarArchivo(file) {
                if (!file.type.startsWith('image/')) {
                    alert('Por favor selecciona un archivo de imagen válido.');
                    return;
                }

                if (file.size > 5 * 1024 * 1024) {
                    alert('La imagen es demasiado grande. Máximo 5MB.');
                    return;
                }

                const carpeta = carpetaSelect.value;
                if (!carpeta) {
                    alert('Por favor selecciona una carpeta primero.');
                    return;
                }

                nombreArchivo.textContent = file.name;

                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.classList.remove('hidden');
                    const previewContainer = document.getElementById(`preview-container-${tipo}`);
                    if (previewContainer) {
                        previewContainer.classList.remove('hidden');
                    }
                };
                reader.readAsDataURL(file);

                subirImagen(file, carpeta);
            }

            function limpiarCamposImagen() {
                rutaImagen.value = '';
                rutaCompleta.textContent = '';
                nombreArchivo.textContent = '';
                fileInput.value = '';
                const previewContainer = document.getElementById(`preview-container-${tipo}`);
                if (previewContainer) {
                    previewContainer.classList.add('hidden');
                }
                preview.src = '';
                preview.classList.add('hidden');
            }

            carpetaSelect.addEventListener('change', () => {
                limpiarCamposImagen();
            });

            const btnLimpiar = document.getElementById(`btn-limpiar-${tipo}`);
            if (btnLimpiar) {
                btnLimpiar.addEventListener('click', () => {
                    limpiarCamposImagen();
                });
            }

            function subirImagen(file, carpeta) {
                const formData = new FormData();
                formData.append('imagen', file);
                formData.append('carpeta', carpeta);
                formData.append('_token', '{{ csrf_token() }}');

                nombreArchivo.textContent = 'Subiendo...';
                btnSeleccionar.disabled = true;

                fetch('{{ route("admin.imagenes.subir") }}', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.data) {
                        rutaImagen.value = data.data.ruta_relativa;
                        rutaCompleta.textContent = `Ruta: ${data.data.ruta_relativa}`;
                        rutaCompleta.classList.remove('hidden');
                        nombreArchivo.textContent = '✓ Subida correctamente';
                        mostrarNotificacion('Imagen subida correctamente', 'success');
                    } else {
                        throw new Error(data.message || 'Error al subir la imagen.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    nombreArchivo.textContent = '✗ Error al subir';
                    mostrarNotificacion('Error al subir la imagen', 'error');
                    rutaImagen.value = '';
                    rutaCompleta.textContent = '';
                    preview.src = '';
                    preview.classList.add('hidden');
                })
                .finally(() => {
                    btnSeleccionar.disabled = false;
                });
            }
        }

        configurarUpload('grande');
        configurarUpload('pequena');

        function configurarBuscadorManual(tipo) {
            const btnBuscar = document.getElementById(`buscar-imagen-${tipo}`);
            const inputManual = document.getElementById(`imagen-${tipo}-input`);
            const previewManual = document.getElementById(`preview-imagen-${tipo}`);
            const rutaImagen = document.getElementById(`ruta-imagen-${tipo}`);

            if (!btnBuscar || !inputManual || !previewManual || !rutaImagen) {
                return;
            }

            btnBuscar.addEventListener('click', () => {
                const carpetaSelect = document.getElementById(`carpeta-imagen-${tipo}`);
                const carpeta = carpetaSelect ? carpetaSelect.value : '';
                cargarImagenesModal(tipo, inputManual, previewManual, rutaImagen, carpeta);
            });

            inputManual.addEventListener('input', () => {
                rutaImagen.value = inputManual.value;
            });
        }

        configurarBuscadorManual('grande');
        configurarBuscadorManual('pequena');

        function cargarImagenesModal(tipo, inputManual, previewManual, rutaImagen, carpetaInicial = '') {
            const modal = document.getElementById('modalImagenes');
            if (!modal) return;

            modal.dataset.tipo = tipo;
            modal.dataset.inputManual = inputManual ? inputManual.id : '';
            modal.dataset.previewManual = previewManual ? previewManual.id : '';
            modal.dataset.rutaImagen = rutaImagen ? rutaImagen.id : '';

            const nombreCarpetaModal = document.getElementById('nombre-carpeta-modal');
            const lista = document.getElementById('contenido-modal-imagenes');
            const carpetaFormulario = document.getElementById(`carpeta-imagen-${tipo}`);
            const carpeta = carpetaInicial || (carpetaFormulario ? carpetaFormulario.value : '');

            nombreCarpetaModal.textContent = carpeta || '—';
            lista.innerHTML = '<div class="text-center text-gray-500 dark:text-gray-400 col-span-full">Cargando imágenes...</div>';

            modal.classList.remove('hidden');
            modal.dataset.carpeta = carpeta;

            cargarImagenesDentroDelModal();
        }

        function cargarImagenesDentroDelModal() {
            const modal = document.getElementById('modalImagenes');
            const lista = document.getElementById('contenido-modal-imagenes');
            const carpeta = modal.dataset.carpeta || '';
            const tipo = modal.dataset.tipo || '';

            if (!carpeta) {
                lista.innerHTML = '<div class="text-center text-gray-500 dark:text-gray-400 col-span-full">Selecciona una carpeta en el formulario para ver las imágenes disponibles.</div>';
                return;
            }

            fetch(`{{ route('admin.imagenes.listar') }}?carpeta=${carpeta}`)
                .then(response => response.json())
                .then(data => {
                    if (!data.success || !data.data || data.data.length === 0) {
                        lista.innerHTML = '<div class="text-center text-gray-500 dark:text-gray-400 col-span-full">No hay imágenes en esta carpeta.</div>';
                        return;
                    }

                    lista.innerHTML = '';
                    data.data.forEach(imagen => {
                        const elemento = document.createElement('div');
                        elemento.className = 'border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden cursor-pointer hover:border-blue-500 dark:hover:border-blue-400 transition-colors';
                        elemento.innerHTML = `
                            <img src="${imagen.url}" alt="${imagen.nombre}" class="h-24 w-full object-cover">
                            <div class="p-2 text-xs text-gray-600 dark:text-gray-300 break-all text-center">${imagen.nombre}</div>
                        `;

                        elemento.addEventListener('click', () => {
                            const inputManual = document.getElementById(modal.dataset.inputManual);
                            const previewManual = document.getElementById(modal.dataset.previewManual);
                            const rutaImagen = document.getElementById(modal.dataset.rutaImagen);
                            const rutaRelativa = `${carpeta}/${imagen.nombre}`;

                            if (inputManual) inputManual.value = rutaRelativa;
                            if (rutaImagen) rutaImagen.value = rutaRelativa;
                            if (previewManual) {
                                previewManual.src = imagen.url;
                                previewManual.style.display = 'block';
                            }

                            cerrarModalImagenes();
                        });

                        lista.appendChild(elemento);
                    });
                })
                .catch(error => {
                    console.error('Error al cargar imágenes:', error);
                    lista.innerHTML = '<div class="text-center text-sm text-red-500 col-span-full">Error al cargar las imágenes.</div>';
                });
        }

        window.cerrarModalImagenes = function() {
            const modal = document.getElementById('modalImagenes');
            if (modal) {
                modal.classList.add('hidden');
                delete modal.dataset.tipo;
                delete modal.dataset.inputManual;
                delete modal.dataset.previewManual;
                delete modal.dataset.rutaImagen;
                delete modal.dataset.carpeta;
            }
        };

        const modalImagenes = document.getElementById('modalImagenes');
        if (modalImagenes) {
            modalImagenes.addEventListener('click', (e) => {
                if (e.target === modalImagenes) {
                    cerrarModalImagenes();
                }
            });
        }

        const btnVerImagenesGrande = document.getElementById('btn-ver-imagenes-grande');
        if (btnVerImagenesGrande) {
            btnVerImagenesGrande.addEventListener('click', () => {
                cargarImagenesModal(
                    'grande',
                    document.getElementById('imagen-grande-input'),
                    document.getElementById('preview-imagen-grande'),
                    document.getElementById('ruta-imagen-grande'),
                    document.getElementById('carpeta-imagen-grande').value
                );
            });
        }

        const btnVerImagenesPequena = document.getElementById('btn-ver-imagenes-pequena');
        if (btnVerImagenesPequena) {
            btnVerImagenesPequena.addEventListener('click', () => {
                cargarImagenesModal(
                    'pequena',
                    document.getElementById('imagen-pequena-input'),
                    document.getElementById('preview-imagen-pequena'),
                    document.getElementById('ruta-imagen-pequena'),
                    document.getElementById('carpeta-imagen-pequena').value
                );
            });
        }
    });
    </script>

    <script>
        const btnBuscarImagenGrande = document.getElementById('buscar-imagen-grande');
        if (btnBuscarImagenGrande) {
            btnBuscarImagenGrande.onclick = function() {
                const input = document.getElementById('imagen-grande-input');
                const preview = document.getElementById('preview-imagen-grande');
                if (input.value.trim() !== '') {
                    preview.src = '/images/' + input.value.trim();
                    preview.style.display = 'block';
                } else {
                    preview.src = '';
                    preview.style.display = 'none';
                }
            };
        }

        const inputImagenGrande = document.getElementById('imagen-grande-input');
        if (inputImagenGrande) {
            inputImagenGrande.addEventListener('input', function() {
                const preview = document.getElementById('preview-imagen-grande');
                preview.style.display = 'none';
            });
        }

        const btnBuscarImagenPequena = document.getElementById('buscar-imagen-pequena');
        if (btnBuscarImagenPequena) {
            btnBuscarImagenPequena.onclick = function() {
                const input = document.getElementById('imagen-pequena-input');
                const preview = document.getElementById('preview-imagen-pequena');
                if (input.value.trim() !== '') {
                    preview.src = '/images/' + input.value.trim();
                    preview.style.display = 'block';
                } else {
                    preview.src = '';
                    preview.style.display = 'none';
                }
            };
        }

        const inputImagenPequena = document.getElementById('imagen-pequena-input');
        if (inputImagenPequena) {
            inputImagenPequena.addEventListener('input', function() {
                const preview = document.getElementById('preview-imagen-pequena');
                preview.style.display = 'none';
            });
        }
    </script>

    <!-- Cropper.js CSS y JS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>

    <!-- Script para descargar imagen desde URL -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        let cropper = null;
        let imagenTemporalUrl = null;
        let carpetaActual = null;

        // Función para validar que el slug esté relleno
        function validarSlug() {
            const slugInput = document.querySelector('input[name="slug"]');
            return slugInput ? slugInput.value.trim() !== '' : false;
        }

        // Función para mostrar error
        function mostrarError(mensaje) {
            const errorDiv = document.getElementById('error-url');
            if (errorDiv) {
                errorDiv.textContent = mensaje;
                errorDiv.classList.remove('hidden');
            }
        }

        // Función para ocultar error
        function ocultarError() {
            const errorDiv = document.getElementById('error-url');
            if (errorDiv) {
                errorDiv.classList.add('hidden');
            }
        }

        // Función para mostrar estado de carga
        function mostrarEstado(tipo, estado) {
            const estadoDiv = document.getElementById(`estado-proceso-${tipo}`);
            const rutaResultado = document.getElementById(`ruta-resultado-${tipo}`);
            
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

        // Función para generar número aleatorio de 6 dígitos
        function generarNumeroAleatorio() {
            return Math.floor(100000 + Math.random() * 900000);
        }

        // Función para descargar imagen desde URL
        async function descargarImagen(url) {
            if (!validarSlug()) {
                mostrarError('Debes escribir el slug del chollo antes de descargar la imagen.');
                return;
            }

            const carpetaSelect = document.getElementById('carpeta-imagen-url');
            const carpeta = carpetaSelect ? carpetaSelect.value : '';

            if (!carpeta) {
                mostrarError('Debes seleccionar una carpeta.');
                return;
            }

            if (!url || url.trim() === '') {
                mostrarError('Debes introducir una URL válida.');
                return;
            }

            ocultarError();
            carpetaActual = carpeta;
            imagenTemporalUrl = url;

            // Mostrar modal de recorte directamente con la URL original
            mostrarModalRecortar(url);
        }

        // Función para mostrar modal de recorte
        function mostrarModalRecortar(urlImagen) {
            const modal = document.getElementById('modalRecortarImagen');
            const img = document.getElementById('imagen-recortar');
            
            if (!modal || !img) return;
            
            // Limpiar cropper anterior si existe
            if (cropper) {
                cropper.destroy();
                cropper = null;
            }
            
            // Usar proxy para evitar problemas CORS
            const urlProxy = urlImagen.startsWith('http://') || urlImagen.startsWith('https://') 
                ? `{{ route('admin.imagenes.proxy') }}?url=${encodeURIComponent(urlImagen)}`
                : urlImagen;
            
            // Configurar la imagen con crossOrigin para evitar problemas CORS
            img.crossOrigin = 'anonymous';
            img.src = urlProxy;
            modal.classList.remove('hidden');

            // Inicializar cropper cuando la imagen se cargue
            img.onload = function() {
                if (cropper) {
                    cropper.destroy();
                }
                
                cropper = new Cropper(img, {
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
            
            // Manejar errores de carga de imagen
            img.onerror = function() {
                mostrarError('Error al cargar la imagen. Verifica que la URL sea accesible y sea una imagen válida.');
                cerrarModalRecortar();
            };
        }

        // Función para cerrar modal de recorte
        window.cerrarModalRecortar = function() {
            const modal = document.getElementById('modalRecortarImagen');
            if (modal) {
                modal.classList.add('hidden');
            }
            
            if (cropper) {
                cropper.destroy();
                cropper = null;
            }
            
            imagenTemporalUrl = null;
            carpetaActual = null;
        };

        // Función para redimensionar canvas estirando la imagen al tamaño exacto (distorsionando si es necesario)
        function redimensionarCanvas(canvasOriginal, anchoDestino, altoDestino) {
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');
            
            // Forzar el tamaño exacto
            canvas.width = anchoDestino;
            canvas.height = altoDestino;
            
            // Estirar la imagen completa al tamaño exacto sin mantener aspecto
            ctx.imageSmoothingEnabled = true;
            ctx.imageSmoothingQuality = 'high';
            ctx.drawImage(canvasOriginal, 0, 0, canvasOriginal.width, canvasOriginal.height, 0, 0, anchoDestino, altoDestino);
            
            return canvas;
        }

        // Función para procesar imagen recortada
        async function procesarImagenRecortada() {
            if (!cropper || !carpetaActual) {
                console.error('Cropper o carpeta no disponible');
                mostrarError('Error: No se puede procesar la imagen. Intenta de nuevo.');
                return;
            }

            // Obtener el canvas recortado sin redimensionar
            const canvasOriginal = cropper.getCroppedCanvas({
                imageSmoothingEnabled: true,
                imageSmoothingQuality: 'high'
            });

            if (!canvasOriginal) {
                mostrarError('Error al recortar la imagen');
                return;
            }

            mostrarEstado('grande', 'loading');
            mostrarEstado('pequena', 'loading');

            try {
                // Redimensionar a 300x250 para imagen grande
                const canvasGrande = redimensionarCanvas(canvasOriginal, 300, 250);
                
                // Redimensionar a 144x120 para imagen pequeña
                const canvasPequena = redimensionarCanvas(canvasOriginal, 144, 120);

                // Convertir ambos canvas a blob (webp)
                const blobGrande = await new Promise((resolve, reject) => {
                    canvasGrande.toBlob((blob) => {
                        if (blob) {
                            resolve(blob);
                        } else {
                            reject(new Error('Error al convertir imagen grande'));
                        }
                    }, 'image/webp', 0.9);
                });

                const blobPequena = await new Promise((resolve, reject) => {
                    canvasPequena.toBlob((blob) => {
                        if (blob) {
                            resolve(blob);
                        } else {
                            reject(new Error('Error al convertir imagen pequeña'));
                        }
                    }, 'image/webp', 0.9);
                });

                // Obtener slug y generar número aleatorio
                const slugInput = document.querySelector('input[name="slug"]');
                const slug = slugInput ? slugInput.value.trim() : '';
                const numeroAleatorio = generarNumeroAleatorio();
                
                // Crear nombre base del archivo: slug-numero.webp
                const nombreBase = `${slug}-${numeroAleatorio}`;

                // Subir ambas imágenes
                const formDataGrande = new FormData();
                formDataGrande.append('imagen', blobGrande, `${nombreBase}.webp`);
                formDataGrande.append('carpeta', carpetaActual);
                formDataGrande.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));

                const formDataPequena = new FormData();
                formDataPequena.append('imagen', blobPequena, `${nombreBase}-thumbnail.webp`);
                formDataPequena.append('carpeta', carpetaActual);
                formDataPequena.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));

                // Subir ambas imágenes en paralelo
                const [responseGrande, responsePequena] = await Promise.all([
                    fetch('{{ route("admin.imagenes.subir-simple") }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: formDataGrande
                    }),
                    fetch('{{ route("admin.imagenes.subir-simple") }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: formDataPequena
                    })
                ]);

                const dataGrande = await responseGrande.json();
                const dataPequena = await responsePequena.json();

                if (!dataGrande.success || !dataPequena.success) {
                    throw new Error(dataGrande.message || dataPequena.message || 'Error al subir las imágenes');
                }

                // Actualizar campos principales (los que tienen el atributo name)
                const rutaGrande = document.getElementById('ruta-imagen-grande');
                const rutaPequena = document.getElementById('ruta-imagen-pequena');
                const rutaTextoGrande = document.getElementById('ruta-texto-grande');
                const rutaTextoPequena = document.getElementById('ruta-texto-pequena');

                // Actualizar ruta grande
                if (rutaGrande) {
                    rutaGrande.value = dataGrande.data.ruta_relativa;
                }
                if (rutaTextoGrande) {
                    rutaTextoGrande.textContent = dataGrande.data.ruta_relativa;
                }
                
                // Actualizar ruta pequeña
                if (rutaPequena) {
                    rutaPequena.value = dataPequena.data.ruta_relativa;
                }
                if (rutaTextoPequena) {
                    rutaTextoPequena.textContent = dataPequena.data.ruta_relativa;
                }

                // Mostrar estado de éxito para ambas
                mostrarEstado('grande', 'success');
                mostrarEstado('pequena', 'success');

                cerrarModalRecortar();

            } catch (error) {
                console.error('Error:', error);
                mostrarError(error.message || 'Error al procesar la imagen');
                mostrarEstado('grande', 'error');
                mostrarEstado('pequena', 'error');
            }
        }

        // Event listener para botón de descarga
        const btnDescargar = document.getElementById('btn-descargar-url');
        if (btnDescargar) {
            btnDescargar.addEventListener('click', () => {
                const urlInput = document.getElementById('url-imagen');
                const url = urlInput ? urlInput.value.trim() : '';
                descargarImagen(url);
            });
        }

        // Event listener para confirmar recorte
        const btnConfirmar = document.getElementById('btn-confirmar-recorte');
        if (btnConfirmar) {
            btnConfirmar.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                console.log('Botón confirmar recorte clickeado');
                procesarImagenRecortada();
            });
        }

        // Actualizar select de carpetas cuando cambie la pestaña
        const tabUrl = document.getElementById('tab-url');
        if (tabUrl) {
            tabUrl.addEventListener('click', () => {
                // Cargar carpetas disponibles si es necesario
                const carpetaSelect = document.getElementById('carpeta-imagen-url');
                if (carpetaSelect && carpetaSelect.options.length <= 1) {
                    fetch('{{ route("admin.imagenes.carpetas") }}')
                        .then(response => response.json())
                        .then(data => {
                            if (data.success && data.data && data.data.length > 0) {
                                carpetaSelect.innerHTML = '<option value="">Selecciona una carpeta</option>';
                                data.data.forEach(carpeta => {
                                    const option = document.createElement('option');
                                    option.value = carpeta;
                                    option.textContent = carpeta;
                                    carpetaSelect.appendChild(option);
                                });
                            }
                        })
                        .catch(error => console.error('Error cargando carpetas:', error));
                }
            });
        }
    });
    </script>

    @if ($errors->any())
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const fieldMap = {
                    'producto_id': 'producto_nombre',
                    'tipo': 'tipo',
                    'tienda_id': 'tienda_nombre',
                    'categoria_id': 'categorias-container',
                    'titulo': 'titulo',
                    'precio_nuevo': 'precio_nuevo',
                    'precio_unidad': 'precio_unidad',
                    'frecuencia_valor': 'frecuencia_valor',
                    'frecuencia_unidad': 'frecuencia_unidad',
                    'url': 'url_input',
                    'descripcion_interna': 'descripcion_interna',
                };

                const firstError = @json($errors->keys()[0] ?? null);
                const targetId = fieldMap[firstError] || firstError;
                const elemento = document.getElementById(targetId);

                if (elemento) {
                    elemento.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    elemento.classList.add('ring', 'ring-red-500', 'ring-offset-2');
                    if (typeof elemento.focus === 'function') {
                        setTimeout(() => elemento.focus({ preventScroll: true }), 300);
                    }
                }
            });
        </script>
    @endif
</x-app-layout>

