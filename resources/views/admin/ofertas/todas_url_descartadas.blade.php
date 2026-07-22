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
                URLs descartadas
            </h2>
        </div>
    </x-slot>

    <div class="py-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        @if(session('success'))
            <div class="mb-4 p-3 rounded-lg bg-green-100 dark:bg-green-900/30 border border-green-400 text-green-800 dark:text-green-200 text-sm">
                {{ session('success') }}
            </div>
        @endif

        <form method="GET" class="mb-6 flex flex-wrap items-end gap-3">
            <div class="flex-1 min-w-[240px]">
                <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">Buscar por URL (completa o parte)</label>
                <input type="text" name="busqueda" value="{{ $busqueda }}"
                    placeholder="https://... o fragmento de la URL"
                    class="w-full px-4 py-2 border rounded bg-white dark:bg-gray-800 text-sm text-gray-800 dark:text-white" />
            </div>
            <div class="min-w-[180px]">
                <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">Tienda</label>
                <select name="tienda_id"
                    class="w-full px-3 py-2 border rounded bg-white dark:bg-gray-800 text-sm text-gray-800 dark:text-white">
                    <option value="">Todas</option>
                    @foreach($tiendas as $tienda)
                        <option value="{{ $tienda->id }}" {{ (int) $tiendaId === (int) $tienda->id ? 'selected' : '' }}>
                            {{ $tienda->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="min-w-[180px]">
                <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">Categoría</label>
                <select name="categoria_id"
                    class="w-full px-3 py-2 border rounded bg-white dark:bg-gray-800 text-sm text-gray-800 dark:text-white">
                    <option value="">Todas</option>
                    @foreach($categorias as $categoria)
                        <option value="{{ $categoria->id }}" {{ (int) $categoriaId === (int) $categoria->id ? 'selected' : '' }}>
                            {{ $categoria->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="relative flex-1 min-w-[220px]">
                <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">Producto</label>
                <input type="hidden" name="producto_id" id="producto_id" value="{{ $productoId ?? '' }}">
                <input type="text" id="producto_search" autocomplete="off"
                    value="{{ $productoSeleccionado ? ('#' . $productoSeleccionado->id . ' - ' . trim($productoSeleccionado->nombre . ' - ' . ($productoSeleccionado->marca ?? '') . ' - ' . ($productoSeleccionado->modelo ?? '') . ' - ' . ($productoSeleccionado->talla ?? ''), ' -')) : '' }}"
                    placeholder="Buscar producto (mín. 2 caracteres)..."
                    class="w-full px-4 py-2 border rounded bg-white dark:bg-gray-800 text-sm text-gray-800 dark:text-white" />
                <div id="producto_sugerencias"
                    class="absolute z-20 left-0 right-0 mt-1 bg-white dark:bg-gray-800 border rounded shadow-lg hidden max-h-60 overflow-y-auto"></div>
                @if($productoSeleccionado)
                    <button type="button" id="producto_quitar"
                        class="mt-1 text-xs text-red-600 hover:underline">Quitar producto</button>
                @endif
            </div>
            <button type="submit"
                class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 text-sm">
                Buscar
            </button>
            @if($busqueda !== '' || $tiendaId || $categoriaId || $productoId)
                <a href="{{ route('admin.ofertas.url_descartadas') }}"
                    class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600 text-sm">
                    Limpiar
                </a>
            @endif
        </form>

        <div class="mb-4 flex flex-wrap justify-between items-center gap-4">
            <div class="flex flex-wrap items-center gap-3">
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    @if($filas->total() > 0)
                        {{ number_format($filas->total(), 0, ',', '.') }} URL(s) descartada(s)
                    @else
                        No hay URLs que coincidan con los filtros.
                    @endif
                </p>
                <button type="button" id="btn-eliminar-bulk"
                    class="hidden px-3 py-1.5 bg-red-600 text-white text-sm rounded hover:bg-red-700 disabled:opacity-50">
                    Eliminar seleccionadas (<span id="bulk-count">0</span>)
                </button>
            </div>
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
                <span class="text-sm text-gray-700 dark:text-gray-300">por página</span>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900">
                    <tr>
                        <th class="px-3 py-3 text-left">
                            <input type="checkbox" id="check-todos" class="rounded border-gray-300 text-red-600 focus:ring-red-500"
                                title="Seleccionar todas">
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">URL</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tienda</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Categoría</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Producto</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Descartada</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse ($filas as $fila)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50" data-id="{{ $fila->id }}">
                            <td class="px-3 py-3">
                                <input type="checkbox" class="fila-check rounded border-gray-300 text-red-600 focus:ring-red-500"
                                    value="{{ $fila->id }}">
                            </td>
                            <td class="px-4 py-3 text-sm max-w-md">
                                <a href="{{ $fila->url }}" target="_blank" rel="noopener"
                                    class="text-blue-600 hover:text-blue-800 dark:text-blue-400 break-all">
                                    {{ $fila->url }}
                                </a>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-800 dark:text-gray-200 whitespace-nowrap">
                                {{ $fila->tienda->nombre ?? '—' }}
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-800 dark:text-gray-200 whitespace-nowrap">
                                {{ $fila->categoria->nombre ?? '—' }}
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-800 dark:text-gray-200 max-w-xs">
                                @if($fila->producto)
                                    <span class="line-clamp-2" title="#{{ $fila->producto->id }} {{ $fila->producto->nombre }}">
                                        #{{ $fila->producto->id }} — {{ trim($fila->producto->nombre . ' - ' . ($fila->producto->marca ?? '') . ' - ' . ($fila->producto->modelo ?? ''), ' -') }}
                                    </span>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm whitespace-nowrap text-gray-600 dark:text-gray-400">
                                @if($fila->created_at)
                                    {{ $fila->created_at->format('d/m/Y H:i') }}
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <button type="button"
                                    class="btn-eliminar-una text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 text-sm font-medium"
                                    data-id="{{ $fila->id }}"
                                    data-url="{{ $fila->url }}">
                                    Eliminar
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                No hay URLs descartadas.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $filas->links() }}
        </div>
    </div>

    <script>
    function cambiarCantidad() {
        const params = new URLSearchParams(window.location.search);
        params.set('perPage', document.getElementById('perPage').value);
        params.delete('page');
        window.location.search = params.toString();
    }

    document.addEventListener('DOMContentLoaded', function () {
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
        const productoSearch = document.getElementById('producto_search');
        const productoId = document.getElementById('producto_id');
        const productoSugerencias = document.getElementById('producto_sugerencias');
        const productoQuitar = document.getElementById('producto_quitar');
        const checkTodos = document.getElementById('check-todos');
        const btnBulk = document.getElementById('btn-eliminar-bulk');
        const bulkCount = document.getElementById('bulk-count');
        let busquedaTimeout = null;

        function actualizarBulkUi() {
            const checks = document.querySelectorAll('.fila-check:checked');
            const n = checks.length;
            bulkCount.textContent = String(n);
            if (n > 0) {
                btnBulk.classList.remove('hidden');
            } else {
                btnBulk.classList.add('hidden');
            }
            const todos = document.querySelectorAll('.fila-check');
            if (checkTodos && todos.length) {
                checkTodos.checked = n === todos.length;
                checkTodos.indeterminate = n > 0 && n < todos.length;
            }
        }

        document.querySelectorAll('.fila-check').forEach(function (cb) {
            cb.addEventListener('change', actualizarBulkUi);
        });

        if (checkTodos) {
            checkTodos.addEventListener('change', function () {
                document.querySelectorAll('.fila-check').forEach(function (cb) {
                    cb.checked = checkTodos.checked;
                });
                checkTodos.indeterminate = false;
                actualizarBulkUi();
            });
        }

        async function eliminarIds(ids) {
            if (!ids.length) return;
            const res = await fetch('{{ route("admin.ofertas.url_descartadas.destroy-bulk") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ ids }),
            });
            const data = await res.json().catch(() => ({}));
            if (!res.ok || !data.success) {
                throw new Error(data.message || 'No se pudieron eliminar');
            }
            return data;
        }

        document.querySelectorAll('.btn-eliminar-una').forEach(function (btn) {
            btn.addEventListener('click', async function () {
                const id = parseInt(btn.dataset.id, 10);
                const url = btn.dataset.url || '';
                if (!confirm('¿Eliminar esta URL de descartadas?\n\n' + url)) return;
                btn.disabled = true;
                btn.textContent = '...';
                try {
                    await eliminarIds([id]);
                    const tr = btn.closest('tr');
                    if (tr) tr.remove();
                    actualizarBulkUi();
                } catch (err) {
                    alert(err.message || 'Error al eliminar');
                    btn.disabled = false;
                    btn.textContent = 'Eliminar';
                }
            });
        });

        if (btnBulk) {
            btnBulk.addEventListener('click', async function () {
                const ids = Array.from(document.querySelectorAll('.fila-check:checked'))
                    .map(function (cb) { return parseInt(cb.value, 10); })
                    .filter(Boolean);
                if (!ids.length) return;
                if (!confirm('¿Eliminar ' + ids.length + ' URL(s) descartadas? Se procesarán una a una.')) return;
                btnBulk.disabled = true;
                try {
                    const data = await eliminarIds(ids);
                    ids.forEach(function (id) {
                        const tr = document.querySelector('tr[data-id="' + id + '"]');
                        if (tr) tr.remove();
                    });
                    actualizarBulkUi();
                    alert(data.message || 'Eliminadas.');
                } catch (err) {
                    alert(err.message || 'Error al eliminar');
                } finally {
                    btnBulk.disabled = false;
                }
            });
        }

        if (productoSearch) {
            productoSearch.addEventListener('input', function () {
                clearTimeout(busquedaTimeout);
                const q = productoSearch.value.trim();
                if (q.length < 2) {
                    productoSugerencias.classList.add('hidden');
                    return;
                }
                busquedaTimeout = setTimeout(async function () {
                    const res = await fetch('{{ route("admin.ofertas.buscar.productos") }}?q=' + encodeURIComponent(q));
                    const items = await res.json();
                    productoSugerencias.innerHTML = '';
                    if (!items.length) {
                        productoSugerencias.classList.add('hidden');
                        return;
                    }
                    items.forEach(function (item) {
                        const div = document.createElement('div');
                        div.className = 'px-3 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 cursor-pointer text-sm text-gray-800 dark:text-gray-200';
                        div.textContent = '#' + item.id + ' - ' + (item.texto_completo || item.nombre);
                        div.addEventListener('click', function () {
                            productoId.value = item.id;
                            productoSearch.value = '#' + item.id + ' - ' + (item.texto_completo || item.nombre);
                            productoSugerencias.classList.add('hidden');
                        });
                        productoSugerencias.appendChild(div);
                    });
                    productoSugerencias.classList.remove('hidden');
                }, 250);
            });
        }

        if (productoQuitar) {
            productoQuitar.addEventListener('click', function () {
                productoId.value = '';
                productoSearch.value = '';
            });
        }

        document.addEventListener('click', function (e) {
            if (!productoSugerencias.contains(e.target) && e.target !== productoSearch) {
                productoSugerencias.classList.add('hidden');
            }
        });
    });
    </script>
</x-app-layout>
