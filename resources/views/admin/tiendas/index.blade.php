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

        {{-- Filtros y selector cantidad --}}
        <div class="mb-4 flex flex-col md:flex-row md:justify-between md:items-center gap-4">

            {{-- Buscador --}}
            <form method="GET" class="flex gap-2">
                <input type="text" name="buscar" value="{{ request('buscar') }}"
                    placeholder="Buscar por nombre"
                    class="px-3 py-2 rounded border bg-white dark:bg-gray-800 text-gray-800 dark:text-white">
                <button type="submit"
                    class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                    Buscar
                </button>
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
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Envío normal</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Opiniones</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Puntuación</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Productos</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse ($tiendas as $tienda)
                    <tr class="hover:bg-gray-100 dark:hover:bg-gray-300 transition-colors">
                        <td class="px-6 py-4">{{ $tienda->nombre }}</td>
                        <td class="px-6 py-4">{{ $tienda->envio_gratis }}</td>
                        <td class="px-6 py-4">{{ $tienda->envio_normal }}</td>
                        <td class="px-6 py-4">{{ $tienda->opiniones }}</td>
                        <td class="px-6 py-4">{{ $tienda->puntuacion }}</td>
                        <td class="px-6 py-4">{{ $tienda->ofertas_count }}</td>
                        <td class="px-6 py-4 text-right">
                            <a href="{{ route('admin.tiendas.ofertas', $tienda) }}"
                                class="text-purple-500 hover:underline mr-4">Ofertas</a>
                            <a href="{{ route('admin.tiendas.edit', $tienda) }}"
                                class="text-blue-500 hover:underline mr-4">Editar</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-4 text-center text-gray-500">
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