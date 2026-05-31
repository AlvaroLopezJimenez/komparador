<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.dashboard') }}">
                <h2 class="font-semibold text-xl text-white leading-tight">Inicio -></h2>
            </a>
            <h2 class="font-semibold text-xl text-white leading-tight">Tiendas</h2>
        </div>
    </x-slot>

    <div class="py-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        {{-- Añadir tienda --}}
        <div class="mb-4 text-right">
            <a href="{{ route('admin.tiendas.create') }}"
                class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">
                + Añadir tienda
            </a>
        </div>

        @if(!empty($tiendasSinNeoobjetivoPorCategoria))
        <details class="mb-6 p-4 rounded-lg bg-amber-50 dark:bg-amber-900/20 border border-amber-400 text-amber-900 dark:text-amber-100 group">
            <summary class="font-semibold cursor-pointer list-none flex items-center gap-2 select-none">
                <svg class="w-4 h-4 shrink-0 transition-transform group-open:rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
                <span>
                    Hay tiendas visibles con categorías que tienen ofertas activas pero sin URL de categoría en Neoobjetivo
                    ({{ count($tiendasSinNeoobjetivoPorCategoria) }} {{ count($tiendasSinNeoobjetivoPorCategoria) === 1 ? 'tienda' : 'tiendas' }}).
                </span>
            </summary>
            <ul class="mt-3 ml-6 space-y-2 text-sm border-t border-amber-300/60 dark:border-amber-600/60 pt-3">
                @foreach($tiendasSinNeoobjetivoPorCategoria as $aviso)
                @php
                    $nombresCategorias = $aviso['categorias']->pluck('nombre');
                    $totalCategorias = $nombresCategorias->count();
                    $textoCategorias = $nombresCategorias->take(6)->join(', ');
                    if ($totalCategorias > 6) {
                        $textoCategorias .= ' y (' . ($totalCategorias - 6) . ' más)';
                    }
                @endphp
                <li>
                    <a href="{{ route('admin.tiendas.edit', $aviso['tienda']) }}"
                        class="font-medium text-amber-900 dark:text-amber-100 underline hover:text-amber-700 dark:hover:text-amber-200">
                        {{ $aviso['tienda']->nombre }}
                    </a>
                    <span class="text-amber-800 dark:text-amber-200"> — </span>
                    <span class="text-amber-800 dark:text-amber-200">{{ $textoCategorias }}</span>
                </li>
                @endforeach
            </ul>
        </details>
        @endif

        {{-- Filtros y selector cantidad --}}
        <div class="mb-4 flex flex-col md:flex-row md:justify-between md:items-center gap-4">

            {{-- Buscador y filtro mostrar --}}
            <form method="GET" class="flex flex-wrap items-center gap-3">
                @if(request()->has('perPage'))
                    <input type="hidden" name="perPage" value="{{ request('perPage') }}">
                @endif
                <input type="text" name="buscar" value="{{ request('buscar') }}"
                    placeholder="Buscar por nombre"
                    class="px-3 py-2 rounded border bg-white dark:bg-gray-800 text-gray-800 dark:text-white">
                <button type="submit"
                    class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                    Buscar
                </button>
                <span class="text-sm text-gray-700 dark:text-gray-300">Mostrar tienda:</span>
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

            {{-- Selector por página --}}
            <div class="flex items-center gap-2">
                <label for="perPage" class="text-sm text-gray-700 dark:text-gray-300">Mostrar:</label>
                <select id="perPage" name="perPage"
                    class="px-2 py-1 pr-8 rounded border bg-white dark:bg-gray-800 text-sm text-gray-800 dark:text-white"
                    onchange="cambiarCantidad()">
                    @foreach ([20, 50, 100, 200] as $cantidad)
                    <option value="{{ $cantidad }}" {{ request('perPage', 25) == $cantidad ? 'selected' : '' }}>{{ $cantidad }}</option>
                    @endforeach
                </select>
                <span class="text-sm text-gray-700 dark:text-gray-300">resultados por página</span>
            </div>
        </div>

        {{-- Tabla --}}
        <div class="bg-white shadow rounded-lg overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nombre</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Envío gratis</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ofertas</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mostrar</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse ($tiendas as $tienda)
                    <tr class="hover:bg-gray-100 dark:hover:bg-gray-300 transition-colors {{ $tienda->mostrar_tienda === 'no' ? 'opacity-60 bg-gray-50' : '' }}">
                        <td class="px-6 py-4">{{ $tienda->nombre }}</td>
                        <td class="px-6 py-4">{{ $tienda->envio_gratis }}</td>
                        <td class="px-6 py-4">{{ $tienda->ofertas_mostrar_si_count }}/{{ $tienda->ofertas_count }}</td>
                        <td class="px-6 py-4 text-center">
                            @if($tienda->mostrar_tienda === 'si')
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
                        <td class="px-6 py-4 text-right">
                            <a href="{{ $tienda->url }}" target="_blank" rel="noopener noreferrer"
                                class="text-green-500 hover:underline mr-4">Ir</a>
                            <a href="{{ route('admin.tiendas.ofertas', $tienda) }}"
                                class="text-purple-500 hover:underline mr-4">Ofertas</a>
                            <a href="{{ route('admin.tiendas.edit', $tienda) }}"
                                class="text-blue-500 hover:underline mr-4">Editar</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                            No hay tiendas registradas.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $tiendas->links() }}
        </div>
    </div>

    <script>
        function cambiarCantidad() {
            const cantidad = document.getElementById('perPage').value;
            const params = new URLSearchParams(window.location.search);
            params.set('perPage', cantidad);
            window.location.search = params.toString();
        }
    </script>
</x-app-layout>
