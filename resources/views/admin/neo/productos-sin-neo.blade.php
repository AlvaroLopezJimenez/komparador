<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.dashboard') }}">
                <h2 class="font-semibold text-xl text-white leading-tight">Inicio -></h2>
            </a>
            <a href="{{ route('admin.neo.index') }}">
                <h2 class="font-semibold text-xl text-white leading-tight">Neo -></h2>
            </a>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Productos sin Neo
            </h2>
        </div>
    </x-slot>

    <div class="py-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <p class="mb-4 text-gray-600 dark:text-gray-400 text-sm">
            Productos que no tienen ningún registro en la tabla neoobjetivo (por producto_id). Paginación: 20 por página.
        </p>

        <div class="bg-white shadow rounded-lg overflow-x-auto dark:bg-gray-800 border border-gray-200 dark:border-gray-700">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-600">
                <thead class="bg-gray-100 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider">Nombre</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider">URL Neo</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-600">
                    @forelse ($productos as $producto)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors neo-fila" data-producto-id="{{ $producto->id }}">
                        <td class="px-6 py-4 text-gray-900 dark:text-gray-100">{{ $producto->nombre }}</td>
                        <td class="px-6 py-4">
                            <div class="flex flex-wrap items-center gap-2">
                                <input type="text"
                                    class="neo-url-input flex-1 min-w-[200px] px-3 py-2 rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm"
                                    placeholder="Pega la URL o usa «No encontrada»"
                                    data-producto-id="{{ $producto->id }}">
                                <button type="button"
                                    class="neo-btn-guardar px-3 py-2 rounded text-sm font-medium bg-blue-600 hover:bg-blue-700 text-white transition-colors"
                                    data-producto-id="{{ $producto->id }}">Guardar</button>
                                <button type="button"
                                    class="neo-btn-no-encontrada px-3 py-2 rounded text-sm font-medium bg-amber-100 dark:bg-amber-900/40 text-amber-800 dark:text-amber-200 border border-amber-300 dark:border-amber-700 hover:bg-amber-200 dark:hover:bg-amber-800/50 transition-colors"
                                    data-producto-id="{{ $producto->id }}">No encontrada</button>
                            </div>
                            <p class="neo-mensaje mt-1 text-xs hidden"></p>
                        </td>
                        <td class="px-6 py-4">
                            @if ($producto->categoria)
                                <a href="{{ $producto->categoria->construirUrlCategorias($producto->slug) }}"
                                    target="_blank"
                                    class="text-green-500 hover:underline mr-4">Ir</a>
                            @else
                                <span class="text-gray-400 dark:text-gray-500 italic mr-4">Sin categoría</span>
                            @endif
                            <a href="{{ route('admin.productos.edit', $producto) }}"
                                class="text-blue-500 hover:underline">Editar</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="3" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                            No hay productos sin neoobjetivo. Todos los productos tienen al menos un registro en neoobjetivo.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-6">
            {{ $productos->withQueryString()->links() }}
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const urlGuardar = '{{ route("admin.neo.guardar-neoobjetivo") }}';
            const csrf = '{{ csrf_token() }}';

            function getFila(productoId) {
                return document.querySelector('.neo-fila[data-producto-id="' + productoId + '"]');
            }

            function getInput(productoId) {
                const fila = getFila(productoId);
                return fila ? fila.querySelector('.neo-url-input') : null;
            }

            function getMensaje(productoId) {
                const fila = getFila(productoId);
                return fila ? fila.querySelector('.neo-mensaje') : null;
            }

            function mostrarMensaje(productoId, texto, esError) {
                const msg = getMensaje(productoId);
                if (!msg) return;
                msg.textContent = texto;
                msg.classList.remove('hidden', 'text-green-600', 'text-red-600', 'dark:text-green-400', 'dark:text-red-400');
                if (esError) {
                    msg.classList.add('text-red-600', 'dark:text-red-400');
                } else {
                    msg.classList.add('text-green-600', 'dark:text-green-400');
                }
            }

            function guardarNeo(productoId, url) {
                const fila = getFila(productoId);
                const btnGuardar = fila ? fila.querySelector('.neo-btn-guardar') : null;
                if (!fila || !btnGuardar) return;

                const urlTrim = (url || '').trim();
                if (urlTrim === '') {
                    mostrarMensaje(productoId, 'Escribe una URL o pulsa «No encontrada».', true);
                    return;
                }
                if (urlTrim.toLowerCase() !== 'no encontrado' && urlTrim.toLowerCase().indexOf('idealo') === -1) {
                    mostrarMensaje(productoId, 'La URL debe contener la palabra «idealo».', true);
                    return;
                }

                btnGuardar.disabled = true;
                mostrarMensaje(productoId, 'Guardando…', false);

                fetch(urlGuardar, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({ producto_id: productoId, url: urlTrim })
                })
                .then(function(r) { return r.json().then(function(data) { return { ok: r.ok, data: data }; }); })
                .then(function(result) {
                    if (result.ok && result.data.success) {
                        mostrarMensaje(productoId, result.data.message || 'Guardado.', false);
                        fila.style.opacity = '0.5';
                        setTimeout(function() { fila.remove(); }, 800);
                    } else {
                        mostrarMensaje(productoId, result.data.message || 'Error al guardar.', true);
                        btnGuardar.disabled = false;
                    }
                })
                .catch(function() {
                    mostrarMensaje(productoId, 'Error de conexión.', true);
                    btnGuardar.disabled = false;
                });
            }

            document.querySelectorAll('.neo-btn-guardar').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    const productoId = parseInt(btn.getAttribute('data-producto-id'), 10);
                    const input = getInput(productoId);
                    guardarNeo(productoId, input ? input.value : '');
                });
            });

            document.querySelectorAll('.neo-btn-no-encontrada').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    const productoId = parseInt(btn.getAttribute('data-producto-id'), 10);
                    const input = getInput(productoId);
                    if (input) {
                        input.value = 'No encontrado';
                        guardarNeo(productoId, 'No encontrado');
                    }
                });
            });
        });
    </script>
</x-app-layout>
