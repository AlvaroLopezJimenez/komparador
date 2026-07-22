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
                            $medidaTiempo = ' Horas';
                        @endphp
                    @else
                        @php
                            $actualizarPrecio = $oferta->frecuencia_actualizar_precio_minutos;
                            $medidaTiempo = ' Min.';
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
                        @if(!empty($urlsDescartadasCoincidentes) && $urlsDescartadasCoincidentes->isNotEmpty())
                            No hay ofertas que coincidan con la búsqueda.
                        @else
                            No hay ofertas registradas.
                        @endif
                    </td>
                </tr>
            @endforelse

            @if(!empty($urlsDescartadasCoincidentes) && $urlsDescartadasCoincidentes->isNotEmpty())
                @foreach($urlsDescartadasCoincidentes as $urlDescartada)
                    <tr class="bg-amber-50 dark:bg-amber-900/20 border-l-4 border-amber-500">
                        <td class="px-6 py-3">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-medium bg-amber-200 text-amber-900 dark:bg-amber-800 dark:text-amber-200">
                                URL descartada
                            </span>
                        </td>
                        <td class="px-6 py-3" colspan="7">
                            <a href="{{ $urlDescartada->url }}" target="_blank" rel="noopener" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 break-all">
                                {{ $urlDescartada->url }}
                            </a>
                            @if($urlDescartada->created_at)
                                <span class="text-xs text-gray-500 dark:text-gray-400 ml-2">(añadida {{ $urlDescartada->created_at->format('d/m/Y') }})</span>
                            @endif
                        </td>
                        <td class="px-6 py-3 text-right">
                            <a href="{{ $urlDescartada->url }}" target="_blank" class="text-green-600 hover:text-green-800" title="Abrir URL">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 3h7m0 0v7m0-7L10 14" />
                                </svg>
                            </a>
                        </td>
                    </tr>
                @endforeach
            @endif
        </tbody>
    </table>
</div>

<div class="mt-4">
    {{ $ofertas->links() }}
</div>
