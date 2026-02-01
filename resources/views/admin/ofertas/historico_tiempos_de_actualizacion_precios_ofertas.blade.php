<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.dashboard') }}">
                <h2 class="font-semibold text-xl text-white leading-tight">Inicio -></h2>
            </a>
            <a href="{{ route('admin.ofertas.todas') }}">
                <h2 class="font-semibold text-xl text-white leading-tight">Ofertas -></h2>
            </a>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Histórico de Actualizaciones de Precios
            </h2>
        </div>
    </x-slot>

    <div class="py-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {{-- Selector de cantidad por página --}}
        <div class="mb-4 flex justify-end items-center gap-4">
            <form method="GET" class="flex items-center gap-2">
                <label for="per_page" class="text-sm text-gray-700 dark:text-gray-300">Mostrar:</label>
                <select id="per_page" name="per_page"
                    class="px-2 py-1 pr-8 rounded border bg-white dark:bg-gray-800 text-sm text-gray-800 dark:text-white"
                    onchange="this.form.submit()">
                    @foreach ([25, 50, 100, 200] as $cantidad)
                    <option value="{{ $cantidad }}" {{ request('per_page', 50) == $cantidad ? 'selected' : '' }}>{{ $cantidad }}</option>
                    @endforeach
                </select>
                <span class="text-sm text-gray-700 dark:text-gray-300">resultados por página</span>
            </form>
        </div>

        <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Producto</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Precio Total</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Tipo</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Frecuencia Aplicada</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Frecuencia Calculada</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse ($historial as $registro)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900 dark:text-gray-200">
                                @if($registro->oferta && $registro->oferta->producto)
                                    {{ $registro->oferta->producto->nombre }}
                                @else
                                    <span class="text-gray-400 italic">Producto no disponible</span>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900 dark:text-gray-200">
                                {{ number_format($registro->precio_total, 2, ',', '.') }} €
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($registro->tipo_actualizacion === 'automatico')
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200">
                                    Automático
                                </span>
                            @else
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200">
                                    Manual
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900 dark:text-gray-200">
                                @if($registro->frecuencia_aplicada_minutos)
                                    @php
                                        $minutos = $registro->frecuencia_aplicada_minutos;
                                        if ($minutos % 1440 === 0) {
                                            $dias = $minutos / 1440;
                                            $frecuencia = $dias . ' día' . ($dias !== 1 ? 's' : '');
                                        } elseif ($minutos % 60 === 0) {
                                            $horas = $minutos / 60;
                                            $frecuencia = $horas . ' hora' . ($horas !== 1 ? 's' : '');
                                        } else {
                                            $frecuencia = $minutos . ' minuto' . ($minutos !== 1 ? 's' : '');
                                        }
                                    @endphp
                                    {{ $frecuencia }}
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900 dark:text-gray-200">
                                @if($registro->frecuencia_calculada_minutos)
                                    @php
                                        $minutos = $registro->frecuencia_calculada_minutos;
                                        if ($minutos % 1440 === 0) {
                                            $dias = $minutos / 1440;
                                            $frecuencia = $dias . ' día' . ($dias !== 1 ? 's' : '');
                                        } elseif ($minutos % 60 === 0) {
                                            $horas = $minutos / 60;
                                            $frecuencia = $horas . ' hora' . ($horas !== 1 ? 's' : '');
                                        } else {
                                            $frecuencia = $minutos . ' minuto' . ($minutos !== 1 ? 's' : '');
                                        }
                                    @endphp
                                    {{ $frecuencia }}
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            @if($registro->oferta && $registro->oferta->producto)
                                @php
                                    $producto = $registro->oferta->producto;
                                    $urlProducto = null;
                                    if ($producto->categoria) {
                                        $urlProducto = $producto->categoria->construirUrlCategorias($producto->slug);
                                    }
                                @endphp
                                @if($urlProducto)
                                    <a href="{{ url('/') }}/{{ $urlProducto }}" target="_blank"
                                        class="text-green-500 hover:text-green-700 dark:hover:text-green-400 mr-4">Ir</a>
                                @endif
                                <a href="{{ route('admin.ofertas.edit', $registro->oferta->id) }}"
                                    class="text-blue-500 hover:text-blue-700 dark:hover:text-blue-400">Editar</a>
                            @else
                                <span class="text-gray-400 italic">-</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                            No hay registros de actualización disponibles.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-6">
            {{ $historial->withQueryString()->links() }}
        </div>
    </div>
</x-app-layout>






