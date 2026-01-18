<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.dashboard') }}">
                <h2 class="font-semibold text-xl text-white leading-tight">Inicio -></h2>
            </a>
            <a href="{{ route('admin.ofertas.todas') }}">
                <h2 class="font-semibold text-xl text-white leading-tight">Ofertas -></h2>
            </a>
            <h2 class="font-semibold text-xl text-white leading-tight">Detectar Ofertas Precio Elevado</h2>
        </div>
    </x-slot>

    <div class="max-w-6xl mx-auto py-10 px-4 space-y-8">
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6 border border-gray-200 dark:border-gray-700">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Detectar Ofertas con Precio Elevado</h1>
            
            <form id="formDetectar" class="space-y-6">
                @csrf
                
                <!-- Selección de Tienda -->
                <div>
                    <label for="tienda_id" class="block mb-2 font-medium text-gray-700 dark:text-gray-200">Tienda</label>
                    <select name="tienda_id" id="tienda_id" required
                        class="w-full px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white border focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Selecciona una tienda</option>
                        @foreach($tiendas as $tienda)
                            <option value="{{ $tienda->id }}">{{ $tienda->nombre }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Selección de Rango de Tiempo -->
                <div>
                    <label for="dias" class="block mb-2 font-medium text-gray-700 dark:text-gray-200">Rango de tiempo</label>
                    <select name="dias" id="dias" required
                        class="w-full px-4 py-2 rounded bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white border focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="90">90 días</option>
                        <option value="180">180 días</option>
                        <option value="365">365 días</option>
                    </select>
                </div>

                <!-- Botón de acción -->
                <div class="flex justify-end">
                    <button type="submit" id="btnEmpezar"
                        class="inline-flex items-center bg-blue-600 hover:bg-blue-700 text-white font-semibold px-6 py-3 rounded-md shadow-lg transition disabled:opacity-50 disabled:cursor-not-allowed">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                        Empezar
                    </button>
                </div>
            </form>
        </div>

        <!-- Resultados -->
        <div id="resultados" class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6 border border-gray-200 dark:border-gray-700 hidden">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Ofertas detectadas con precio elevado</h2>
                <div id="contadorOfertas" class="hidden">
                    <span class="inline-flex items-center px-3 py-1 bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-200 text-sm rounded-full">
                        <span id="numeroOfertas">0</span> ofertas detectadas
                    </span>
                </div>
            </div>
            
            <div id="resultadosLista" class="space-y-4"></div>
        </div>
    </div>

    <script>
        // Objeto para rastrear ofertas clickadas
        const ofertasClickadas = {
            editar: new Set(),
            estadisticas: new Set()
        };
        
        document.getElementById('formDetectar').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const tiendaId = document.getElementById('tienda_id').value;
            const dias = document.getElementById('dias').value;
            const btnEmpezar = document.getElementById('btnEmpezar');
            const resultados = document.getElementById('resultados');
            const resultadosLista = document.getElementById('resultadosLista');
            
            if (!tiendaId || !dias) {
                alert('Por favor, selecciona una tienda y un rango de tiempo');
                return;
            }
            
            // Limpiar el seguimiento de ofertas clickadas al iniciar nueva búsqueda
            ofertasClickadas.editar.clear();
            ofertasClickadas.estadisticas.clear();
            
            btnEmpezar.disabled = true;
            btnEmpezar.innerHTML = '<svg class="animate-spin h-5 w-5 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>Procesando...';
            resultadosLista.innerHTML = '<div class="text-center py-8"><div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div><p class="mt-4 text-gray-600 dark:text-gray-400">Analizando ofertas...</p></div>';
            resultados.classList.remove('hidden');
            
            try {
                const response = await fetch('{{ route("admin.ofertas.detectar.precio.elevado") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        tienda_id: tiendaId,
                        dias: dias
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    mostrarResultados(data.ofertas);
                    document.getElementById('numeroOfertas').textContent = data.ofertas.length;
                    document.getElementById('contadorOfertas').classList.remove('hidden');
                } else {
                    resultadosLista.innerHTML = `<div class="text-center py-8 text-red-600 dark:text-red-400">${data.message || 'Error al procesar las ofertas'}</div>`;
                }
            } catch (error) {
                console.error('Error:', error);
                resultadosLista.innerHTML = '<div class="text-center py-8 text-red-600 dark:text-red-400">Error al procesar las ofertas. Por favor, intenta de nuevo.</div>';
            } finally {
                btnEmpezar.disabled = false;
                btnEmpezar.innerHTML = '<svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>Empezar';
            }
        });
        
        function marcarComoClickado(ofertaId, tipo) {
            ofertasClickadas[tipo].add(ofertaId);
            actualizarIndicador(ofertaId, tipo);
        }
        
        function actualizarIndicador(ofertaId, tipo) {
            const indicadorId = `indicador-${tipo}-${ofertaId}`;
            const indicador = document.getElementById(indicadorId);
            if (indicador && ofertasClickadas[tipo].has(ofertaId)) {
                indicador.classList.remove('hidden');
            }
        }
        
        function mostrarResultados(ofertas) {
            const resultadosLista = document.getElementById('resultadosLista');
            
            if (ofertas.length === 0) {
                resultadosLista.innerHTML = '<div class="text-center py-8 text-gray-600 dark:text-gray-400">No se encontraron ofertas con precio elevado.</div>';
                return;
            }
            
            let html = '';
            ofertas.forEach(oferta => {
                const editadoClickado = ofertasClickadas.editar.has(oferta.id);
                const estadisticasClickado = ofertasClickadas.estadisticas.has(oferta.id);
                
                html += `
                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition" id="oferta-${oferta.id}">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">${oferta.producto_nombre || 'Sin nombre'}</h3>
                                <div class="space-y-2">
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Prueba 1 (Rango 5 ofertas):</span>
                                        <span class="text-red-600 dark:text-red-400 font-bold text-lg">✗</span>
                                        <span class="text-xs text-gray-500 dark:text-gray-400">(No ha estado en el rango)</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Prueba 2 (Cruce precios):</span>
                                        ${oferta.prueba2_paso ? '<span class="text-green-600 dark:text-green-400 font-bold text-lg">✓</span><span class="text-xs text-gray-500 dark:text-gray-400">(Sí se han cruzado)</span>' : '<span class="text-red-600 dark:text-red-400 font-bold text-lg">✗</span><span class="text-xs text-gray-500 dark:text-gray-400">(No se han cruzado)</span>'}
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Prueba 3 (Media >20%):</span>
                                        ${oferta.prueba3_paso ? `<span class="text-red-600 dark:text-red-400 font-bold text-lg">✗</span><span class="text-xs text-gray-500 dark:text-gray-400">(${oferta.prueba3_porcentaje}% - Mayor al 20%)</span>` : `<span class="text-green-600 dark:text-green-400 font-bold text-lg">✓</span><span class="text-xs text-gray-500 dark:text-gray-400">(${oferta.prueba3_porcentaje}% - Menor al 20%)</span>`}
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 ml-4">
                                <div class="flex items-center gap-2">
                                    <a href="/panel-privado/ofertas/${oferta.id}/edit" 
                                        onclick="marcarComoClickado(${oferta.id}, 'editar'); return true;"
                                        target="_blank"
                                        class="bg-blue-500 text-white px-3 py-1 rounded hover:bg-blue-600 text-sm">
                                        Editar
                                    </a>
                                    <span id="indicador-editar-${oferta.id}" class="${editadoClickado ? '' : 'hidden'} flex items-center gap-1 text-green-600 dark:text-green-400 text-sm font-medium">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                        Clickado
                                    </span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <a href="/panel-privado/ofertas/${oferta.id}/estadisticas" 
                                        onclick="marcarComoClickado(${oferta.id}, 'estadisticas'); return true;"
                                        target="_blank"
                                        class="text-indigo-500 hover:text-indigo-700" title="Estadísticas">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3v18M6 8v13M16 13v8M21 17v4" />
                                        </svg>
                                    </a>
                                    <span id="indicador-estadisticas-${oferta.id}" class="${estadisticasClickado ? '' : 'hidden'} flex items-center gap-1 text-green-600 dark:text-green-400 text-sm font-medium">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                        Clickado
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            resultadosLista.innerHTML = html;
        }
    </script>
</x-app-layout>






