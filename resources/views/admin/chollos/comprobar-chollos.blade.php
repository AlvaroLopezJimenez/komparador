@php
    // Función helper para formatear unidades según la unidad de medida
    if (!function_exists('formatearUnidades')) {
        function formatearUnidades($unidades, $unidadDeMedida) {
            // Normalizar la unidad de medida (lowercase y sin espacios)
            $unidadNormalizada = strtolower(trim($unidadDeMedida ?? ''));
            
            if ($unidadNormalizada === 'unidades') {
                // Para unidades, mostrar solo el número entero sin decimales
                return (string) intval($unidades);
            } elseif (in_array($unidadNormalizada, ['kilos', 'litros'])) {
                // Para kilos y litros, mostrar con decimales necesarios (eliminar ceros finales)
                $formateado = number_format(floatval($unidades), 3, ',', '.');
                // Eliminar ceros finales y la coma si no hay decimales
                $formateado = rtrim($formateado, '0');
                $formateado = rtrim($formateado, ',');
                return $formateado;
            }
            // Por defecto, mostrar con 2 decimales
            return number_format(floatval($unidades), 2, ',', '.');
        }
    }

    if (!function_exists('formatearDescuentos')) {
        function formatearDescuentos($descuentos) {
            if (empty($descuentos)) {
                return null;
            }

            // Verificar si es un cupón
            if (strpos($descuentos, 'cupon;') === 0) {
                $partes = explode(';', $descuentos);
                
                if (count($partes) === 3) {
                    // Formato: cupon;codigo;cantidad
                    $codigo = $partes[1];
                    $cantidad = $partes[2];
                    
                    // Verificar si la cantidad es un porcentaje
                    if (strpos($cantidad, '%') === 0) {
                        return ['tipo' => 'cupon', 'codigo' => $codigo, 'cantidad' => $cantidad];
                    }
                    
                    return ['tipo' => 'cupon', 'codigo' => $codigo, 'cantidad' => $cantidad . '€'];
                } elseif (count($partes) === 2) {
                    // Formato: cupon;cantidad o cupon;%porcentaje
                    $cantidad = $partes[1];
                    
                    // Verificar si es porcentaje
                    if (strpos($cantidad, '%') === 0) {
                        return ['tipo' => 'cupon', 'codigo' => null, 'cantidad' => $cantidad];
                    }
                    
                    return ['tipo' => 'cupon', 'codigo' => null, 'cantidad' => $cantidad . '€'];
                }
            }

            // Si no es cupón, devolver tal cual
            return ['tipo' => 'otro', 'texto' => $descuentos];
        }
    }
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.dashboard') }}">
                <h2 class="font-semibold text-xl text-white leading-tight">
                    Inicio ->
                </h2>
            </a>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Comprobar Chollos
            </h2>
        </div>
    </x-slot>

    <div class="py-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Botones de acción -->
        <div class="mb-6 flex justify-between items-center">
            <form method="GET" class="flex flex-col items-start gap-3 md:flex-row md:items-center">
                <select name="perPage" class="border px-3 py-2 rounded text-sm bg-gray-700 text-gray-200 border-gray-600">
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
        </div>

        <div class="bg-gray-800 dark:bg-gray-800 shadow rounded-lg overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-700 dark:divide-gray-600" style="table-layout: fixed;">
                <colgroup>
                    <col style="width: 20px;">
                    <col style="width: auto;">
                    <col style="width: auto;">
                    <col style="width: auto;">
                    <col style="width: auto;">
                </colgroup>
                <thead class="bg-gray-700 dark:bg-gray-700">
                    <tr>
                        <th class="py-2 text-left text-xs font-medium text-gray-300 dark:text-gray-300 uppercase tracking-wider" style="width: 20px;"></th>
                        <th class="px-6 py-4 text-left text-xs font-medium text-gray-300 dark:text-gray-300 uppercase tracking-wider">Elemento</th>
                        <th class="px-6 py-4 text-left text-xs font-medium text-gray-300 dark:text-gray-300 uppercase tracking-wider">Información de la Oferta</th>
                        <th class="px-6 py-4 text-left text-xs font-medium text-gray-300 dark:text-gray-300 uppercase tracking-wider">Última Comprobada</th>
                        <th class="px-6 py-4 text-left text-xs font-medium text-gray-300 dark:text-gray-300 uppercase tracking-wider">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-700 dark:divide-gray-600">
                    @forelse ($ofertas as $oferta)
                        <tr class="hover:bg-gray-700 dark:hover:bg-gray-700 transition-colors" data-descuento-original="{{ $oferta->descuentos ?? '' }}">
                            <td class="p-0" onclick="event.stopPropagation()" style="width: 20px;">
                                @if($oferta->chollo_id)
                                    <div class="flex items-center justify-center bg-pink-600" style="writing-mode: vertical-lr; transform: rotate(180deg); min-height: 40px;">
                                        <span class="text-white text-sm font-bold py-8">Chollo</span>
                                    </div>
                                @else
                                    <div class="flex items-center justify-center bg-purple-600" style="writing-mode: vertical-lr; transform: rotate(180deg); min-height: 40px;">
                                        <span class="text-white text-sm font-bold py-8">Oferta</span>
                                    </div>
                                @endif
                            </td>
                            <td class="px-6 py-1">
                                <div class="flex flex-col space-y-1">
                                    <span class="text-gray-200 dark:text-gray-200 break-words text-sm">{{ $oferta->producto->nombre ?? '—' }}</span>
                                    @if($oferta->chollo)
                                        <span class="text-pink-400 dark:text-pink-400 break-words text-xs font-medium">{{ $oferta->chollo->titulo ?? '—' }}</span>
                                    @endif
                                    <div class="flex items-center space-x-1">
                                        @if($oferta->chollo)
                                            <button onclick="event.stopPropagation(); window.open('{{ route('admin.chollos.edit', $oferta->chollo->id) }}', '_blank')" 
                                                class="px-3 py-2 text-sm bg-purple-600 hover:bg-purple-700 text-white rounded transition-colors">
                                                Editar Chollo
                                            </button>
                                            <button onclick="event.stopPropagation(); window.open('{{ route('chollos.show', $oferta->chollo->slug) }}', '_blank')" 
                                                class="px-3 py-2 text-sm bg-pink-600 hover:bg-pink-700 text-white rounded transition-colors">
                                                Ver Chollo
                                            </button>
                                        @endif
                                        <button onclick="event.stopPropagation(); window.open('{{ route('admin.ofertas.edit', $oferta->id) }}', '_blank')" 
                                            class="px-3 py-2 text-sm bg-blue-600 hover:bg-blue-700 text-white rounded transition-colors">
                                            Editar
                                        </button>
                                        @if($oferta->url)
                                            <button onclick="event.stopPropagation(); window.open('{{ $oferta->url }}', '_blank')" 
                                                class="px-3 py-2 text-sm bg-green-600 hover:bg-green-700 text-white rounded transition-colors">
                                                Ver
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-1">
                                <div class="text-base text-gray-200 dark:text-gray-200 whitespace-pre-line break-words">
                                    <div class="mt-0 p-1 bg-gray-700 rounded text-sm text-gray-300">
                                        <div class="flex flex-wrap items-center gap-1 text-sm">
                                            <span class="font-medium">{{ $oferta->tienda->nombre ?? 'Tienda ID: ' . $oferta->tienda_id }}</span>
                                            <span>•</span>
                                            <span>{{ formatearUnidades($oferta->unidades, $oferta->producto->unidadDeMedida ?? 'unidades') }} uds</span>
                                            <span>•</span>
                                            <input type="number" 
                                                   step="0.01" 
                                                   min="0" 
                                                   value="{{ number_format($oferta->precio_total, 2, '.', '') }}" 
                                                   data-oferta-id="{{ $oferta->id }}"
                                                   data-unidades="{{ $oferta->unidades }}"
                                                   data-precio-original="{{ number_format($oferta->precio_total, 2, '.', '') }}"
                                                   class="precio-total-input w-16 px-1 py-0.5 bg-gray-600 text-gray-200 border border-gray-500 rounded text-sm focus:outline-none focus:ring-1 focus:ring-blue-500"
                                                   onclick="event.stopPropagation(); this.select();"
                                                   onfocus="this.select(); this.value = '';"
                                                   onblur="if(this.value === '' || this.value === null) { this.value = this.dataset.precioOriginal; calcularPrecioUnidad(this); } else { calcularPrecioUnidad(this); }"
                                                   onchange="calcularPrecioUnidad(this)">
                                            <span>€</span>
                                            <span>•</span>
                                            <span class="text-green-400 font-medium precio-unidad-display" data-oferta-id="{{ $oferta->id }}" data-unidad-medida="{{ $oferta->producto->unidadDeMedida ?? 'unidades' }}">{{ ($oferta->producto->unidadDeMedida ?? 'unidades') === 'unidadMilesima' ? number_format($oferta->precio_unidad, 3) : number_format($oferta->precio_unidad, 2) }}€/ud</span>
                                        </div>
                                        @if($oferta->chollo_id)
                                        <div class="flex flex-wrap items-center gap-1 text-sm mt-0.5">
                                            @php
                                                $codigoCupon = '';
                                                $cantidadCupon = '';
                                                $esCupon = false;
                                                $textoDescuento = '';
                                                
                                                // Verificar si es un cupón
                                                if (!empty($oferta->descuentos) && strpos($oferta->descuentos, 'cupon;') === 0) {
                                                    $esCupon = true;
                                                    $partes = explode(';', $oferta->descuentos);
                                                    
                                                    if (count($partes) === 3) {
                                                        // Formato: cupon;codigo;cantidad
                                                        $codigoCupon = $partes[1];
                                                        $cantidadCupon = $partes[2];
                                                    } elseif (count($partes) === 2) {
                                                        // Formato: cupon;cantidad o cupon;%porcentaje
                                                        $cantidadCupon = $partes[1];
                                                    }
                                                    
                                                    // Limpiar cantidad si tiene símbolos (excepto si es porcentaje)
                                                    if (strpos($cantidadCupon, '%') === 0) {
                                                        // Es porcentaje, mantenerlo
                                                    } else {
                                                        // Es cantidad numérica, limpiar
                                                        $cantidadCupon = str_replace(['€', '(', ')', '-'], '', $cantidadCupon);
                                                    }
                                                } elseif (!empty($oferta->descuentos)) {
                                                    // Es otro tipo de descuento (3x2, 2x1, etc.)
                                                    $textoDescuento = $oferta->descuentos;
                                                }
                                            @endphp
                                            @if($esCupon || empty($oferta->descuentos))
                                                <span class="text-orange-500 font-medium">Cupón:</span>
                                                <input type="text" 
                                                       value="{{ $codigoCupon }}" 
                                                       data-oferta-id="{{ $oferta->id }}"
                                                       placeholder="Código"
                                                       class="cupon-codigo-input w-10 px-1 py-0.5 bg-gray-600 text-gray-200 border border-gray-500 rounded text-sm focus:outline-none focus:ring-1 focus:ring-orange-500"
                                                       onclick="event.stopPropagation(); this.select();">
                                                <span class="text-orange-500 font-medium">(-</span>
                                                <input type="text" 
                                                       value="{{ $cantidadCupon }}" 
                                                       data-oferta-id="{{ $oferta->id }}"
                                                       placeholder="Cantidad"
                                                       class="cupon-cantidad-input w-12 px-1 py-0.5 bg-gray-600 text-gray-200 border border-gray-500 rounded text-sm focus:outline-none focus:ring-1 focus:ring-orange-500"
                                                       onclick="event.stopPropagation(); this.select();">
                                                <span class="text-orange-500 font-medium">)</span>
                                            @elseif($textoDescuento)
                                                <span class="text-orange-500 font-medium">Descuento:</span>
                                                <span class="text-orange-500 font-medium">{{ $textoDescuento }}</span>
                                            @endif
                                            <span>•</span>
                                            <span class="text-gray-300">Mostrar:</span>
                                            <div class="flex items-center space-x-2" onclick="event.stopPropagation();">
                                                <label class="flex items-center cursor-pointer">
                                                    <input type="radio" 
                                                           name="mostrar_{{ $oferta->id }}" 
                                                           value="si" 
                                                           data-oferta-id="{{ $oferta->id }}"
                                                           class="mostrar-select w-3 h-3 text-yellow-600 bg-gray-600 border-gray-500 focus:ring-yellow-500 focus:ring-2"
                                                           {{ $oferta->mostrar === 'si' ? 'checked' : '' }}>
                                                    <span class="ml-1 text-xs text-gray-300">Sí</span>
                                                </label>
                                                <label class="flex items-center cursor-pointer">
                                                    <input type="radio" 
                                                           name="mostrar_{{ $oferta->id }}" 
                                                           value="no" 
                                                           data-oferta-id="{{ $oferta->id }}"
                                                           class="mostrar-select w-3 h-3 text-yellow-600 bg-gray-600 border-gray-500 focus:ring-yellow-500 focus:ring-2"
                                                           {{ $oferta->mostrar === 'no' ? 'checked' : '' }}>
                                                    <span class="ml-1 text-xs text-gray-300">No</span>
                                                </label>
                                            </div>
                                        </div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-1 text-sm text-gray-200 dark:text-gray-200">
                                @if($oferta->chollo_id)
                                    {{ $oferta->comprobada ? $oferta->comprobada->format('d/m/Y H:i') : 'Nunca' }}
                                @else
                                    {{ $oferta->updated_at ? $oferta->updated_at->format('d/m/Y H:i') : 'Nunca' }}
                                @endif
                            </td>
                            <td class="px-6 py-1" onclick="event.stopPropagation()">
                                <div class="flex items-center space-x-2">
                                    <button onclick="marcarComprobada({{ $oferta->id }})" 
                                        class="px-4 py-2 text-sm bg-yellow-600 hover:bg-yellow-700 text-white rounded-md transition-colors">
                                        Comprobada
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-gray-400 dark:text-gray-400 text-sm">
                                No hay ofertas pendientes de comprobar.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-6">
            {{ $ofertas->links() }}
        </div>
    </div>

    <script>
        // Función para calcular precio por unidad cuando cambia el precio total
        function calcularPrecioUnidad(input) {
            const precioTotal = parseFloat(input.value) || 0;
            const unidades = parseFloat(input.dataset.unidades) || 1;
            const ofertaId = input.dataset.ofertaId;
            
            if (unidades > 0) {
                const precioUnidad = precioTotal / unidades;
                
                // Actualizar el display del precio por unidad
                const precioUnidadDisplay = document.querySelector(`.precio-unidad-display[data-oferta-id="${ofertaId}"]`);
                if (precioUnidadDisplay) {
                    // Formatear con 2 o 3 decimales según la unidad de medida
                    const unidadMedida = precioUnidadDisplay.dataset.unidadMedida || 'unidades';
                    const decimales = unidadMedida === 'unidadMilesima' ? 3 : 2;
                    precioUnidadDisplay.textContent = precioUnidad.toFixed(decimales) + '€/ud';
                }
            }
        }

        // Función para marcar como comprobada
        function marcarComprobada(ofertaId) {
            const precioTotalInput = document.querySelector(`.precio-total-input[data-oferta-id="${ofertaId}"]`);
            const precioTotal = parseFloat(precioTotalInput.value) || 0;

            if (precioTotal <= 0) {
                alert('El precio total debe ser mayor que 0');
                return;
            }

            // Preparar body de la petición
            const bodyData = {
                precio_total: precioTotal
            };

            // Solo incluir cupón y mostrar si la oferta es de chollo (tiene chollo_id)
            // Verificamos si existe la fila de cupón/mostrar para saber si es chollo
            const codigoCuponInput = document.querySelector(`.cupon-codigo-input[data-oferta-id="${ofertaId}"]`);
            const cantidadCuponInput = document.querySelector(`.cupon-cantidad-input[data-oferta-id="${ofertaId}"]`);
            const mostrarRadio = document.querySelector(`.mostrar-select[data-oferta-id="${ofertaId}"]:checked`);
            
            if (codigoCuponInput && cantidadCuponInput && mostrarRadio) {
                // Es una oferta de chollo, incluir cupón y mostrar
                const codigoCupon = codigoCuponInput.value.trim();
                const cantidadCupon = cantidadCuponInput.value.trim();
                const mostrar = mostrarRadio.value;

                // Obtener el descuento original de la oferta
                const fila = precioTotalInput.closest('tr');
                const descuentoOriginal = fila ? (fila.dataset.descuentoOriginal || '') : '';
                
                // Construir el campo descuentos
                let descuentos = '';
                
                // Si hay campos de cupón visibles, construir el cupón
                if (codigoCupon || cantidadCupon) {
                    // Hay valores en los campos de cupón, construir el cupón
                    if (codigoCupon && cantidadCupon) {
                        descuentos = `cupon;${codigoCupon};${cantidadCupon}`;
                    } else if (cantidadCupon) {
                        descuentos = `cupon;${cantidadCupon}`;
                    } else if (codigoCupon) {
                        descuentos = `cupon;${codigoCupon};`;
                    }
                } else if (descuentoOriginal && !descuentoOriginal.startsWith('cupon;')) {
                    // Si no hay cupón pero hay otro descuento, mantenerlo
                    descuentos = descuentoOriginal;
                }

                bodyData.descuentos = descuentos;
                bodyData.mostrar = mostrar;
            }

            fetch(`/panel-privado/chollos/ofertas/${ofertaId}/marcar-comprobada`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify(bodyData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Actualizar el precio unidad en el display
                    const precioUnidadDisplay = document.querySelector(`.precio-unidad-display[data-oferta-id="${ofertaId}"]`);
                    if (precioUnidadDisplay) {
                        const unidadMedida = precioUnidadDisplay.dataset.unidadMedida || 'unidades';
                        const decimales = unidadMedida === 'unidadMilesima' ? 3 : 2;
                        precioUnidadDisplay.textContent = data.precio_unidad.toFixed(decimales) + '€/ud';
                    }
                    
                    // Recargar la página después de un breve delay
                    setTimeout(() => {
                        location.reload();
                    }, 500);
                } else {
                    alert('Error: ' + (data.message || 'Error desconocido'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error de conexión al marcar como comprobada');
            });
        }

    </script>
</x-app-layout>

