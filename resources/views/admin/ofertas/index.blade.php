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
        $ocultarUnidadesYPrecioUnidad = $producto && ($producto->unidadDeMedida === 'unidadUnica');
        $columnasEspecListado = $columnasEspecListado ?? [];
        $celdasEspecPorOfertaId = $celdasEspecPorOfertaId ?? [];
        $filtroEspecActivo = $filtroEspecActivo ?? [];
        $conteosOpcionesEspec = $conteosOpcionesEspec ?? [];
        $numColEspec = count($columnasEspecListado);
        $colspanEmpty = 5 + $numColEspec + ($ocultarUnidadesYPrecioUnidad ? 0 : 2);
        $querySinEspecNiPage = request()->except(['e', 'page']);
        $urlToggleEspecOpcion = function (string $pid, string $sid) use ($producto, $querySinEspecNiPage, $filtroEspecActivo) {
            if (!$producto) {
                return '#';
            }
            $next = $filtroEspecActivo;
            $sid = (string) $sid;
            if (!isset($next[$pid])) {
                $next[$pid] = [];
            }
            if (in_array($sid, $next[$pid], true)) {
                $next[$pid] = array_values(array_filter($next[$pid], fn ($x) => (string) $x !== $sid));
                if ($next[$pid] === []) {
                    unset($next[$pid]);
                }
            } else {
                $next[$pid][] = $sid;
            }
            $q = $querySinEspecNiPage;
            if ($next !== []) {
                $q['e'] = $next;
            }
            return route('admin.ofertas.index', $producto) . (count($q) ? '?' . http_build_query($q) : '');
        };
        $urlLimpiarFiltroEspec = $producto
            ? (route('admin.ofertas.index', $producto) . (count($querySinEspecNiPage) ? '?' . http_build_query($querySinEspecNiPage) : ''))
            : '#';
        $hayOpcionesFiltroEspec = false;
        foreach ($columnasEspecListado as $_col) {
            if (count($_col['opciones_oferta'] ?? []) > 0) {
                $hayOpcionesFiltroEspec = true;
                break;
            }
        }
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
    @foreach($filtroEspecActivo as $pid => $sids)
        @foreach($sids as $sid)
            <input type="hidden" name="e[{{ $pid }}][]" value="{{ $sid }}">
        @endforeach
    @endforeach
    @if(request()->has('perPage'))
        <input type="hidden" name="perPage" value="{{ request('perPage') }}">
    @endif
    @foreach((array) request('mostrar', []) as $m)
        <input type="hidden" name="mostrar[]" value="{{ $m }}">
    @endforeach
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
                    @foreach($filtroEspecActivo as $pid => $sids)
                        @foreach($sids as $sid)
                            <input type="hidden" name="e[{{ $pid }}][]" value="{{ $sid }}">
                        @endforeach
                    @endforeach
                    @if(request()->has('perPage'))
                        <input type="hidden" name="perPage" value="{{ request('perPage') }}">
                    @endif
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

        @if($producto && $numColEspec > 0 && $hayOpcionesFiltroEspec)
        <div class="mb-4 p-4 bg-white dark:bg-gray-800 shadow rounded-lg border border-gray-200 dark:border-gray-700">
            <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
                <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200">Filtrar por especificaciones (columnas)</h3>
                @if(count($filtroEspecActivo) > 0)
                <a href="{{ $urlLimpiarFiltroEspec }}"
                    class="text-xs font-medium text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300">
                    Quitar filtros
                </a>
                @endif
            </div>
            <div class="space-y-4">
                @foreach($columnasEspecListado as $colEspec)
                    @php $opciones = $colEspec['opciones_oferta'] ?? []; @endphp
                    @if(count($opciones) > 0)
                    <div>
                        <div class="text-xs font-medium text-gray-600 dark:text-gray-300 mb-2">{{ $colEspec['texto'] }}</div>
                        <div class="flex flex-wrap gap-2">
                            @foreach($opciones as $opt)
                                @php
                                    $pid = $colEspec['id'];
                                    $sid = (string) $opt['id'];
                                    $activa = isset($filtroEspecActivo[$pid]) && in_array($sid, $filtroEspecActivo[$pid], true);
                                    $cnt = (int) ($conteosOpcionesEspec[$pid][$sid] ?? 0);
                                    $sinResultados = !$activa && $cnt === 0;
                                @endphp
                                <a href="{{ $urlToggleEspecOpcion($pid, $sid) }}"
                                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-md text-xs font-medium transition-colors border
                                        {{ $activa
                                            ? 'bg-emerald-100 text-emerald-900 border-emerald-500 ring-2 ring-emerald-400/80 dark:bg-emerald-900/40 dark:text-emerald-100 dark:border-emerald-500 dark:ring-emerald-500/50'
                                            : ($sinResultados
                                                ? 'bg-gray-100/70 text-gray-500 border-gray-200 opacity-45 grayscale cursor-not-allowed dark:bg-gray-800/60 dark:text-gray-500 dark:border-gray-600'
                                                : 'bg-gray-100 text-gray-800 border-gray-300 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600 dark:hover:bg-gray-600') }}">
                                    <span>{{ $opt['texto'] }}</span>
                                    <span class="tabular-nums opacity-90">({{ $cnt }})</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                    @endif
                @endforeach
            </div>
        </div>
        @endif

        <div class="bg-white shadow rounded-lg overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tienda</th>
                        @foreach ($columnasEspecListado as $colEspec)
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">{{ $colEspec['texto'] }}</th>
                        @endforeach
                        @unless($ocultarUnidadesYPrecioUnidad)
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Unidades</th>
                        @endunless
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Precio Total</th>
                        @unless($ocultarUnidadesYPrecioUnidad)
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">€/Unidad</th>
                        @endunless
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Mostrar</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Última Actualización</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse ($ofertas as $oferta)
                    <tr class="hover:bg-gray-100 dark:hover:bg-gray-300 transition-colors {{ $oferta->mostrar === 'no' ? 'opacity-60 bg-gray-50' : '' }}">
                        <td class="px-6 py-4">{{ $oferta->tienda->nombre }}</td>
                        @foreach ($columnasEspecListado as $idx => $colEspec)
                        <td class="px-6 py-4 text-sm text-gray-800 dark:text-gray-200 max-w-xs">{{ ($celdasEspecPorOfertaId[$oferta->id] ?? [])[$idx] ?? '—' }}</td>
                        @endforeach
                        @unless($ocultarUnidadesYPrecioUnidad)
                        <td class="px-6 py-4">{{ $oferta->unidades }}</td>
                        @endunless
                        <td class="px-6 py-4">{{ number_format($oferta->precio_total, 2) }} €</td>
                        @unless($ocultarUnidadesYPrecioUnidad)
                        <td class="px-6 py-4">{{ number_format($oferta->precio_unidad, 2) }} €</td>
                        @endunless
                        <td class="px-6 py-4 text-center">
                            @if($oferta->mostrar === 'si')
                                <span title="Visible">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-green-600 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                </span>
                            @else
                                <span title="Oculto">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-red-500 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </span>
                            @endif
                        </td>
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
                        <td colspan="{{ $colspanEmpty }}" class="px-6 py-4 text-center text-gray-500">
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