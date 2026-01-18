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
                Ofertas de {{ $tienda->nombre }}
            </h2>
        </div>
    </x-slot>

    <div class="py-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {{-- Buscador por producto y selector de cantidad --}}
        <div class="mb-4 flex flex-col md:flex-row md:justify-between md:items-center gap-4">

            {{-- Buscador --}}
            <form method="GET" class="flex gap-2">
                <input type="text" name="buscar" value="{{ request('buscar') }}"
                    placeholder="Buscar por producto"
                    class="px-3 py-2 rounded border bg-white dark:bg-gray-800 text-gray-800 dark:text-white">
                <button type="submit"
                    class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                    Buscar
                </button>
            </form>

            {{-- Botón para reorganizar update_at --}}
            <div class="flex gap-2">
                <a href="{{ route('admin.ofertas.reorganizar.update-at') }}"
                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-pink-600 hover:bg-pink-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-pink-500">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    Reorganizar Update_at
                </a>
            </div>

            {{-- Selector de cantidad por página --}}
            <div class="flex items-center gap-2">
                <label for="perPage" class="text-sm text-gray-700 dark:text-gray-300">Mostrar:</label>
                <select id="perPage" name="perPage"
                    class="px-2 py-1 pr-8 rounded border bg-white dark:bg-gray-800 text-sm text-gray-800 dark:text-white"
                    onchange="this.form.submit()" form="cantidadForm">
                    @foreach ([20, 50, 100, 200] as $cantidad)
                    <option value="{{ $cantidad }}" {{ request('perPage', 20) == $cantidad ? 'selected' : '' }}>
                        {{ $cantidad }}
                    </option>
                    @endforeach
                </select>
                <span class="text-sm text-gray-700 dark:text-gray-300">resultados por página</span>
            </div>

            {{-- Form invisible para el selector --}}
            <form method="GET" id="cantidadForm" class="hidden">
                <input type="hidden" name="buscar" value="{{ request('buscar') }}">
                <input type="hidden" name="perPage" id="perPageHidden">
            </form>
        </div>

        {{-- Tabla de ofertas --}}
        <div class="bg-white shadow rounded-lg overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Producto</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Unidades</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Precio Total</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">€/Unidad</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Mostrar</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse ($ofertas as $oferta)
                    <tr class="hover:bg-gray-100 dark:hover:bg-gray-300 transition-colors">
                        <td class="px-6 py-4">{{ $oferta->producto->nombre }}</td>
                        <td class="px-6 py-4">{{ $oferta->unidades }}</td>
                        <td class="px-6 py-4">{{ number_format($oferta->precio_total, 2) }} €</td>
                        <td class="px-6 py-4">{{ number_format($oferta->precio_unidad, 4) }} €</td>
                        <td class="px-6 py-4">{{ $oferta->mostrar }}</td>
                        <td class="px-6 py-4 text-right">
                            <a href="{{ $oferta->url }}" target="_blank"
                                class="text-green-500 hover:underline mr-4">Ir</a>
                            <a href="{{ route('admin.ofertas.edit', $oferta) }}"
                                class="text-blue-500 hover:underline">Editar</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                            No hay ofertas registradas para esta tienda.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $ofertas->appends(request()->query())->links() }}
        </div>
    </div>

    <script>
        function cambiarCantidad() {
            const cantidad = document.getElementById('perPage').value;
            const params = new URLSearchParams(window.location.search);
            params.set('perPage', cantidad);
            window.location.search = params.toString();
        }

        // Mantener búsqueda al cambiar cantidad
        document.getElementById('perPage').addEventListener('change', function() {
            document.getElementById('perPageHidden').value = this.value;
        });
    </script>
</x-app-layout>