<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.dashboard') }}">
                <h2 class="font-semibold text-xl text-white leading-tight">
                    Inicio ->
                </h2>
            </a>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Chollos
            </h2>
        </div>
    </x-slot>

    <div class="py-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mb-6 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <form method="GET" class="flex flex-col items-start gap-3 md:flex-row md:items-center">
                <input
                    type="text"
                    name="buscar"
                    value="{{ $busqueda }}"
                    placeholder="Buscar por título, producto, tienda o URL..."
                    class="border px-3 py-2 rounded w-72 text-sm"
                >

                <select name="mostrar" class="border px-3 py-2 rounded text-sm">
                    <option value="activos" {{ $mostrar === 'activos' ? 'selected' : '' }}>Activos</option>
                    <option value="finalizados" {{ $mostrar === 'finalizados' ? 'selected' : '' }}>Finalizados</option>
                    <option value="ocultos" {{ $mostrar === 'ocultos' ? 'selected' : '' }}>Ocultos</option>
                    <option value="todos" {{ $mostrar === 'todos' ? 'selected' : '' }}>Todos</option>
                </select>

                <select name="perPage" class="border px-3 py-2 rounded text-sm">
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

            <a
                href="{{ route('admin.chollos.create') }}"
                class="bg-pink-600 text-white px-4 py-2 rounded shadow hover:bg-pink-700 transition text-sm"
            >
                + Añadir chollo
            </a>
        </div>

        <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-100 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                            Título
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                            Producto
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                            Tienda
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                            Inicio
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                            Fin
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                            Mostrar
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                            Finalizada
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                            Clicks
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                            Acciones
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse ($chollos as $chollo)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                            <td class="px-6 py-4 text-sm text-gray-800 dark:text-gray-200 max-w-xs">
                                <div class="font-semibold">{{ $chollo->titulo }}</div>
                                <div class="text-xs text-gray-500 truncate">
                                    {{ $chollo->url }}
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">
                                {{ $chollo->producto?->nombre ?? '—' }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">
                                {{ $chollo->tienda?->nombre ?? '—' }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">
                                {{ optional($chollo->fecha_inicio)->format('d/m/Y H:i') ?? '—' }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">
                                {{ optional($chollo->fecha_final)->format('d/m/Y H:i') ?? '—' }}
                            </td>
                            <td class="px-6 py-4 text-sm">
                                @if ($chollo->mostrar === 'si')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        Sí
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                        No
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm">
                                @if ($chollo->finalizada === 'si')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-200 text-gray-700">
                                        Sí
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        No
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">
                                {{ number_format($chollo->clicks ?? 0) }}
                            </td>
                            <td class="px-6 py-4 text-right text-sm">
                                <a
                                    href="{{ route('admin.chollos.edit', $chollo) }}"
                                    class="text-pink-600 hover:text-pink-500 font-semibold"
                                >
                                    Editar
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-6 py-12 text-center text-gray-500 dark:text-gray-300 text-sm">
                                No se encontraron chollos con los filtros aplicados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-6">
            {{ $chollos->links() }}
        </div>
    </div>
</x-app-layout>

































