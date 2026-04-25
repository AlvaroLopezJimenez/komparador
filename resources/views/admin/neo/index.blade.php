<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.dashboard') }}">
                <h2 class="font-semibold text-xl text-white leading-tight">Inicio -></h2>
            </a>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Listado Neo
            </h2>
        </div>
    </x-slot>

    <div class="py-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mb-4">
            <a href="{{ route('admin.neo.sin-url-completar') }}"
                class="inline-flex text-sm text-blue-600 dark:text-blue-400 hover:underline">
                Neo sin URL — completar por CSV
            </a>
        </div>
        {{-- Formulario de búsqueda --}}
        <div class="mb-4 text-left">
            <form method="GET" class="mb-6 flex flex-wrap items-center gap-2">
                <input type="text" name="busqueda" value="{{ request('busqueda') }}"
                    placeholder="Buscar por URL o neo"
                    class="flex-1 min-w-[200px] px-4 py-2 border rounded bg-white dark:bg-gray-800 text-sm text-gray-800 dark:text-white" />
                <button type="submit"
                    class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 text-sm">
                    Buscar
                </button>
            </form>
        </div>

        {{-- Filtros y selector de cantidad por página --}}
        <div class="mb-4 flex justify-between items-center gap-4">
            {{-- Filtro Añadida (publicada) --}}
            <div class="flex items-center gap-2">
                <label class="text-sm text-gray-700 dark:text-gray-300">Añadida:</label>
                <form method="GET" class="flex items-center gap-2" id="filtroAniadidaForm">
                    @if(request('busqueda'))
                        <input type="hidden" name="busqueda" value="{{ request('busqueda') }}">
                    @endif
                    @if(request('perPage'))
                        <input type="hidden" name="perPage" value="{{ request('perPage') }}">
                    @endif
                    <label class="flex items-center gap-1">
                        <input type="checkbox" name="aniadida[]" value="si"
                               {{ in_array('si', $aniadidaParaVista) ? 'checked' : '' }}
                               onchange="this.form.submit()"
                               class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                        <span class="text-sm text-gray-700 dark:text-gray-300">Sí</span>
                    </label>
                    <label class="flex items-center gap-1">
                        <input type="checkbox" name="aniadida[]" value="no"
                               {{ in_array('no', $aniadidaParaVista) ? 'checked' : '' }}
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
                        <option value="{{ $cantidad }}" {{ $perPage == $cantidad ? 'selected' : '' }}>
                            {{ $cantidad }}
                        </option>
                    @endforeach
                </select>
                <span class="text-sm text-gray-700 dark:text-gray-300">resultados por página</span>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-600">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Producto</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Tienda</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Categoría</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Añadida</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-600 bg-white dark:bg-gray-800">
                    @forelse ($neos as $neo)
                        <tr class="hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                            <td class="px-6 py-4 text-sm text-gray-800 dark:text-gray-200">
                                @if($neo->producto_id && $neo->producto)
                                    {{ $neo->producto->nombre }}
                                @else
                                    <span class="text-gray-400 dark:text-gray-500">—</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-800 dark:text-gray-200">
                                @if($neo->tienda_id && $neo->tienda)
                                    {{ $neo->tienda->nombre }}
                                @else
                                    <span class="text-gray-400 dark:text-gray-500">—</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-800 dark:text-gray-200">
                                @if($neo->categoria_id && $neo->categoria)
                                    {{ $neo->categoria->nombre }}
                                @else
                                    <span class="text-gray-400 dark:text-gray-500">—</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-800 dark:text-gray-200">
                                {{ $neo->aniadida ?? '—' }}
                            </td>
                            <td class="px-6 py-4 text-right">
                                <a href="{{ $neo->url }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1 text-green-600 dark:text-green-400 hover:text-green-800 dark:hover:text-green-300" title="Ir">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 3h7m0 0v7m0-7L10 14" />
                                    </svg>
                                    Ir
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                                No hay registros que coincidan con el filtro.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $neos->links() }}
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
