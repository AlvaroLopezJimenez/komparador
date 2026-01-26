<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.dashboard') }}">
                <h2 class="font-semibold text-xl text-white leading-tight">Inicio -></h2>
            </a>
            <a href="{{ route('admin.productos.index') }}">
                <h2 class="font-semibold text-xl text-white leading-tight">Productos -></h2>
            </a>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ $producto ? 'Ofertas de ' . $producto->nombre : 'Todas las ofertas' }}
            </h2>

        </div>
    </x-slot>

    <div class="py-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        @php
        $rutaCrear = $producto ? route('admin.ofertas.create', ['producto' => $producto->id]) : null;
        @endphp

        @if ($rutaCrear)
        <div class="mb-4 text-right">
            <a href="{{ $rutaCrear }}" target="_blank"
                class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">
                + Añadir oferta
            </a>
        </div>
        @endif

        {{-- Formulario de búsqueda --}}
<form method="GET" class="mb-6 flex flex-wrap items-center gap-2">
    <input type="text" name="busqueda" value="{{ request('busqueda') }}"
        placeholder="Buscar por tienda, modelo, talla, URL o anotaciones"
        class="flex-1 min-w-[200px] px-4 py-2 border rounded bg-white dark:bg-gray-800 text-sm text-gray-800 dark:text-white" />
    <button type="submit"
        class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 text-sm">
        Buscar
    </button>
</form>


        {{-- Filtros y selector de cantidad por página --}}
        <div class="mb-4 flex justify-between items-center gap-4">
            {{-- Filtro de mostrar --}}
            <div class="flex items-center gap-2">
                <label class="text-sm text-gray-700 dark:text-gray-300">Mostrar ofertas:</label>
                <form method="GET" class="flex items-center gap-2" id="filtroMostrarForm">
                    @if(request('busqueda'))
                        <input type="hidden" name="busqueda" value="{{ request('busqueda') }}">
                    @endif
                    <label class="flex items-center gap-1">
                        <input type="checkbox" name="mostrar[]" value="si" 
                               {{ in_array('si', $mostrarParaVista) ? 'checked' : '' }} 
                               onchange="this.form.submit()"
                               class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                        <span class="text-sm text-gray-700 dark:text-gray-300">Sí</span>
                    </label>
                    <label class="flex items-center gap-1">
                        <input type="checkbox" name="mostrar[]" value="no" 
                               {{ in_array('no', $mostrarParaVista) ? 'checked' : '' }} 
                               onchange="this.form.submit()"
                               class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                        <span class="text-sm text-gray-700 dark:text-gray-300">No</span>
                    </label>
                </form>
            </div>

            {{-- Selector de cantidad por página --}}
            <div class="flex items-center gap-2">
                <label for="perPage" class="text-sm text-gray-700 dark:text-gray-300">Mostrar:</label>
                <select id="perPage" name="perPage"
                    class="px-2 py-1 pr-8 rounded border bg-white dark:bg-gray-800 text-sm text-gray-800 dark:text-white"
                    onchange="cambiarCantidad()">
                    @foreach ([20, 50, 100, 200] as $cantidad)
                    <option value="{{ $cantidad }}" {{ $perPage == $cantidad ? 'selected' : '' }}>{{ $cantidad }}</option>
                    @endforeach
                </select>
                <span class="text-sm text-gray-700 dark:text-gray-300">resultados por página</span>
            </div>
        </div>

        <div class="bg-white shadow rounded-lg overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tienda</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Producto</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Unidades</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Precio Total</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">€/Unidad</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Mostrar</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-800 uppercase tracking-wider">Actualiza</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Última Actualización</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse ($ofertas as $oferta)
                    <tr class="hover:bg-gray-100 dark:hover:bg-gray-300 transition-colors {{ $oferta->mostrar === 'no' ? 'opacity-60 bg-gray-50' : '' }}">
                        <td class="px-6 py-4">{{ $oferta->tienda->nombre }}</td>
                        <td class="px-6 py-4">{{ $oferta->producto->nombre }}</td>
                        <td class="px-6 py-4">{{ $oferta->unidades }}</td>
                        <td class="px-6 py-4">{{ number_format($oferta->precio_total, 2) }} €</td>
                        <td class="px-6 py-4">{{ number_format($oferta->precio_unidad, 2) }} €</td>
                        <td class="px-6 py-4">{{ $oferta->mostrar }}</td>
                        @if($oferta->frecuencia_actualizar_precio_minutos > 60)
                            @php
                                $actualizarPrecio = $oferta->frecuencia_actualizar_precio_minutos / 60;
                                $medidaTiempo = " Horas";
                            @endphp
                        @else
                            @php
                                $actualizarPrecio = $oferta->frecuencia_actualizar_precio_minutos;
                                $medidaTiempo = " Min.";
                            @endphp
                        @endif
                        <td class="px-6 py-4">{{ $actualizarPrecio }}{{ $medidaTiempo }}</td>
                        <td class="px-6 py-4 text-center">
                            <div class="text-xs">
                                <div class="font-medium text-black">
                                    {{ $oferta->updated_at ? $oferta->updated_at->format('d/m/Y') : 'N/A' }}
                                </div>
                                <div class="text-black">
                                    {{ $oferta->updated_at ? $oferta->updated_at->format('H:i') : 'N/A' }}
                                </div>
                                <div class="text-black">
                                    @if($oferta->updated_at)
                                        @php
                                            $diff = $oferta->updated_at->diff(now());
                                            if ($diff->days > 0) {
                                                echo $diff->days . ' día' . ($diff->days > 1 ? 's' : '');
                                            } elseif ($diff->h > 0) {
                                                echo $diff->h . ' hora' . ($diff->h > 1 ? 's' : '');
                                            } elseif ($diff->i > 0) {
                                                echo $diff->i . ' min';
                                            } else {
                                                echo 'Ahora';
                                            }
                                        @endphp
                                    @else
                                        N/A
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-right flex items-center justify-end space-x-4">
                            <!-- Botón Ir -->
                            <a href="{{ $oferta->url }}" target="_blank" class="text-green-600 hover:text-green-800" title="Ir">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M14 3h7m0 0v7m0-7L10 14" />
                                </svg>
                            </a>

                            <!-- Botón Estadísticas -->
                            <a href="{{ route('admin.ofertas.estadisticas', $oferta) }}" class="text-indigo-500 hover:text-indigo-700" title="Estadísticas">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M11 3v18M6 8v13M16 13v8M21 17v4" />
                                </svg>
                            </a>

                            <!-- Botón Editar (con texto) -->
                            <a href="{{ route('admin.ofertas.edit', $oferta) }}"
                                class="bg-blue-500 text-white px-3 py-1 rounded hover:bg-blue-600">
                                Editar
                            </a>

                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="px-6 py-4 text-center text-gray-500">
                            No hay ofertas registradas{{ $producto ? ' para este producto.' : '.' }}
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $ofertas->links() }}
        </div>

        {{-- Sección de información de tiendas --}}
        @if($producto && isset($tiendasInfo))
        <div class="mt-8">
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
                <button id="toggleTiendas" class="w-full px-6 py-4 text-left bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors rounded-t-lg">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-white">
                            Información de tiendas para {{ $producto->nombre }}
                        </h3>
                        <svg id="iconTiendas" class="w-5 h-5 text-gray-600 dark:text-gray-400 transform transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </div>
                </button>
                
                <div id="contenidoTiendas" class="hidden px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                                         {{-- Tiendas con ofertas activas --}}
                     <div class="mb-6">
                         <h4 class="text-base font-semibold text-gray-700 dark:text-gray-300 mb-3">
                             Tiendas con ofertas activas ({{ collect($tiendasInfo)->where('tiene_ofertas_activas', true)->count() }})
                         </h4>
                         <div class="flex flex-wrap gap-3">
                             @foreach($tiendasInfo as $info)
                                 @if($info['tiene_ofertas_activas'])
                                 <div class="flex items-center gap-2">
                                     @if($info['tienda']->url)
                                     <a href="{{ $info['tienda']->url }}" target="_blank" 
                                        class="inline-flex items-center px-4 py-2 text-sm font-medium bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 rounded-full hover:bg-green-200 dark:hover:bg-green-800 transition-colors" title="Ir a {{ $info['tienda']->nombre }}">
                                         {{ $info['tienda']->nombre }}
                                         <span class="ml-2 text-sm opacity-75">({{ $info['ofertas_activas'] }}/{{ $info['total_ofertas'] }})</span>
                                     </a>
                                     @else
                                     <span class="inline-flex items-center px-4 py-2 text-sm font-medium bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 rounded-full">
                                         {{ $info['tienda']->nombre }}
                                         <span class="ml-2 text-sm opacity-75">({{ $info['ofertas_activas'] }}/{{ $info['total_ofertas'] }})</span>
                                     </span>
                                     @endif
                                 </div>
                                 @endif
                             @endforeach
                         </div>
                     </div>

                     {{-- Tiendas sin ofertas activas --}}
                     <div>
                         <h4 class="text-base font-semibold text-gray-700 dark:text-gray-300 mb-3">
                             Tiendas sin ofertas activas ({{ collect($tiendasInfo)->where('tiene_ofertas_activas', false)->count() }})
                         </h4>
                         <div class="flex flex-wrap gap-3">
                             @foreach($tiendasInfo as $info)
                                 @if(!$info['tiene_ofertas_activas'])
                                 <div class="flex items-center gap-2">
                                     @if($info['tienda']->url)
                                     <a href="{{ $info['tienda']->url }}" target="_blank" 
                                        class="inline-flex items-center px-4 py-2 text-sm font-medium bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-full hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors" title="Ir a {{ $info['tienda']->nombre }}">
                                         {{ $info['tienda']->nombre }}
                                         @if($info['tiene_ofertas'])
                                         <span class="ml-2 text-sm opacity-75">(0/{{ $info['total_ofertas'] }})</span>
                                         @endif
                                     </a>
                                     @else
                                     <span class="inline-flex items-center px-4 py-2 text-sm font-medium bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-full">
                                         {{ $info['tienda']->nombre }}
                                         @if($info['tiene_ofertas'])
                                         <span class="ml-2 text-sm opacity-75">(0/{{ $info['total_ofertas'] }})</span>
                                         @endif
                                     </span>
                                     @endif
                                 </div>
                                 @endif
                             @endforeach
                         </div>
                     </div>
                </div>
            </div>
        </div>
        @endif
    </div>

    <script>
        function cambiarCantidad() {
            const cantidad = document.getElementById('perPage').value;
            const params = new URLSearchParams(window.location.search);
            params.set('perPage', cantidad);
            window.location.search = params.toString();
        }
    </script>
    {{-- EVITAR TENER QUE PINCHAR DOS VECES EN LOS ENLACES PARA QUE FUNCIONEN--}}
    <script>
document.addEventListener('DOMContentLoaded', function() {
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
    
    // Prevenir doble clic en botones
    const buttons = document.querySelectorAll('button');
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

    // Funcionalidad del desplegable de tiendas
    const toggleTiendas = document.getElementById('toggleTiendas');
    const contenidoTiendas = document.getElementById('contenidoTiendas');
    const iconTiendas = document.getElementById('iconTiendas');
    
    if (toggleTiendas && contenidoTiendas && iconTiendas) {
        toggleTiendas.addEventListener('click', function() {
            const isHidden = contenidoTiendas.classList.contains('hidden');
            
            if (isHidden) {
                contenidoTiendas.classList.remove('hidden');
                iconTiendas.classList.add('rotate-180');
            } else {
                contenidoTiendas.classList.add('hidden');
                iconTiendas.classList.remove('rotate-180');
            }
        });
    }
});
</script>
</x-app-layout>