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
                CSV-Awin (csv_ofertas)
            </h2>
        </div>
    </x-slot>

    <div class="py-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mb-4 flex flex-wrap justify-end gap-2">
            <a href="{{ route('admin.ejecuciones.descarga-csv-tiendas') }}"
                class="bg-cyan-600 text-white px-4 py-2 rounded hover:bg-cyan-700 text-sm">
                Ejecuciones descarga CSV
            </a>
            <a href="{{ route('admin.ofertas.todas') }}"
                class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 text-sm">
                Todas las ofertas
            </a>
        </div>

        <form method="GET" class="mb-6 flex flex-wrap items-end gap-3">
            <div class="flex-1 min-w-[240px]">
                <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">Buscar por URL (completa o parte)</label>
                <input type="text" name="busqueda" value="{{ $busqueda }}"
                    placeholder="https://... o fragmento de la URL"
                    class="w-full px-4 py-2 border rounded bg-white dark:bg-gray-800 text-sm text-gray-800 dark:text-white" />
            </div>
            <div class="flex-1 min-w-[200px]">
                <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">Buscar por código (EAN, ISBN, UPC, MPN, GTIN)</label>
                <input type="text" name="busqueda_codigo" value="{{ $busquedaCodigo }}"
                    placeholder="Código completo o fragmento"
                    class="w-full px-4 py-2 border rounded bg-white dark:bg-gray-800 text-sm text-gray-800 dark:text-white" />
            </div>
            <div class="min-w-[180px]">
                <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">Tienda</label>
                <select name="tienda_id"
                    class="w-full px-3 py-2 border rounded bg-white dark:bg-gray-800 text-sm text-gray-800 dark:text-white">
                    <option value="">Todas</option>
                    @foreach($tiendasConCsv as $tienda)
                        <option value="{{ $tienda->id }}" {{ (int) $tiendaId === (int) $tienda->id ? 'selected' : '' }}>
                            {{ $tienda->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="min-w-[120px]">
                <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">Stock</label>
                <select name="stock"
                    class="w-full px-3 py-2 border rounded bg-white dark:bg-gray-800 text-sm text-gray-800 dark:text-white">
                    <option value="" {{ $stock === null || $stock === '' ? 'selected' : '' }}>Todos</option>
                    <option value="1" {{ $stock === '1' ? 'selected' : '' }}>Con stock</option>
                    <option value="0" {{ $stock === '0' ? 'selected' : '' }}>Sin stock</option>
                </select>
            </div>
            <button type="submit"
                class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 text-sm">
                Buscar
            </button>
            @if($busqueda !== '' || $busquedaCodigo !== '' || $tiendaId || ($stock !== null && $stock !== ''))
                <a href="{{ route('admin.ofertas.todas_csv') }}"
                    class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600 text-sm">
                    Limpiar
                </a>
            @endif
        </form>

        <div class="mb-4 flex justify-between items-center gap-4">
            <p class="text-sm text-gray-600 dark:text-gray-400">
                @if($filas->total() > 0)
                    {{ number_format($filas->total(), 0, ',', '.') }} fila(s) encontrada(s)
                @else
                    No hay filas que coincidan con los filtros.
                @endif
            </p>
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
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tienda</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nombre</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">URL</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Precio</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Envío</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Stock</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Añadida</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actualizado</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse ($filas as $fila)
                        @php
                            $tieneCodigos = collect([$fila->ean, $fila->isbn, $fila->upc, $fila->mpn, $fila->gtin])
                                ->contains(fn ($valor) => $valor !== null && trim((string) $valor) !== '');
                        @endphp
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 {{ (int) $fila->stock === 0 ? 'opacity-70 bg-gray-50 dark:bg-gray-900/40' : '' }}">
                            <td class="px-4 py-3 text-sm text-gray-800 dark:text-gray-200 whitespace-nowrap">
                                {{ $fila->tienda->nombre ?? '—' }}
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-800 dark:text-gray-200 max-w-xs">
                                @if($fila->nombre)
                                    <span class="line-clamp-2" title="{{ $fila->nombre }}">{{ $fila->nombre }}</span>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm max-w-md">
                                <a href="{{ $fila->url }}" target="_blank" rel="noopener"
                                    class="text-blue-600 hover:text-blue-800 dark:text-blue-400 break-all">
                                    {{ $fila->url }}
                                </a>
                            </td>
                            <td class="px-4 py-3 text-sm whitespace-nowrap">
                                @if($fila->precio !== null)
                                    {{ number_format((float) $fila->precio, 2, ',', '.') }} €
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm whitespace-nowrap">
                                @if($fila->envio !== null)
                                    {{ number_format((float) $fila->envio, 2, ',', '.') }} €
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm whitespace-nowrap">
                                @if($fila->stock === null)
                                    <span class="text-gray-400">—</span>
                                @elseif((int) $fila->stock === 1)
                                    <span class="text-green-600 dark:text-green-400">Sí</span>
                                @else
                                    <span class="text-red-600 dark:text-red-400">No</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm whitespace-nowrap">
                                @if($fila->aniadida_neo === 'si')
                                    <span class="text-green-600 dark:text-green-400">Sí</span>
                                @else
                                    <span class="text-red-600 dark:text-red-400">No</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm whitespace-nowrap text-gray-600 dark:text-gray-400">
                                @if($fila->updated_at)
                                    <div>{{ $fila->updated_at->format('d/m/Y H:i') }}</div>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <div class="inline-flex items-center gap-2">
                                    <a href="{{ $fila->url }}" target="_blank" rel="noopener"
                                        class="text-green-600 hover:text-green-800 dark:text-green-400 dark:hover:text-green-300"
                                        title="Abrir URL">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 3h7m0 0v7m0-7L10 14" />
                                        </svg>
                                    </a>
                                    <button type="button"
                                        class="{{ $tieneCodigos
                                            ? 'text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300 border-indigo-300 dark:border-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 cursor-pointer'
                                            : 'text-gray-400 dark:text-gray-500 border-gray-200 dark:border-gray-600 cursor-not-allowed opacity-60' }} text-xs font-medium px-2 py-1 rounded border"
                                        title="{{ $tieneCodigos ? 'Ver códigos (EAN, ISBN, UPC, MPN, GTIN)' : 'Sin códigos que mostrar' }}"
                                        @if($tieneCodigos)
                                            data-ean="{{ $fila->ean }}"
                                            data-isbn="{{ $fila->isbn }}"
                                            data-upc="{{ $fila->upc }}"
                                            data-mpn="{{ $fila->mpn }}"
                                            data-gtin="{{ $fila->gtin }}"
                                            onclick="abrirModalCodigosCsv(this)"
                                        @else
                                            disabled
                                        @endif>
                                        Códigos
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-6 py-8 text-center text-gray-500">
                                @if($busqueda !== '' || $busquedaCodigo !== '' || $tiendaId || ($stock !== null && $stock !== ''))
                                    No hay filas en csv_ofertas que coincidan con la búsqueda.
                                @else
                                    No hay filas en csv_ofertas. Ejecuta el cron de descarga CSV de tiendas.
                                @endif
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

    {{-- Modal códigos EAN / ISBN / etc. --}}
    <div id="modal-codigos-csv" class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50 p-4">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Códigos del producto</h3>
                <button type="button" onclick="cerrarModalCodigosCsv()"
                    class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 text-xl leading-none" aria-label="Cerrar">&times;</button>
            </div>
            <div id="modal-codigos-csv-contenido" class="px-5 py-4 space-y-3 text-sm">
            </div>
            <div class="px-5 py-3 border-t border-gray-200 dark:border-gray-700 text-right">
                <button type="button" onclick="cerrarModalCodigosCsv()"
                    class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600 text-sm">
                    Cerrar
                </button>
            </div>
        </div>
    </div>

    <script>
        function cambiarCantidad() {
            const cantidad = document.getElementById('perPage').value;
            const params = new URLSearchParams(window.location.search);
            params.set('perPage', cantidad);
            window.location.search = params.toString();
        }

        function abrirModalCodigosCsv(btn) {
            const codigos = {
                EAN: btn.dataset.ean || '',
                ISBN: btn.dataset.isbn || '',
                UPC: btn.dataset.upc || '',
                MPN: btn.dataset.mpn || '',
                GTIN: btn.dataset.gtin || '',
            };
            const contenedor = document.getElementById('modal-codigos-csv-contenido');
            const filas = Object.entries(codigos).map(([tipo, valor]) => {
                const tieneValor = valor !== null && valor !== undefined && String(valor).trim() !== '';
                const valorHtml = tieneValor
                    ? `<span class="font-mono text-gray-900 dark:text-gray-100 break-all">${escapeHtml(String(valor))}</span>`
                    : `<span class="text-gray-400">—</span>`;
                return `<div class="flex flex-col sm:flex-row sm:items-start gap-1 sm:gap-3">
                    <span class="shrink-0 w-14 font-medium text-gray-500 dark:text-gray-400 uppercase text-xs">${escapeHtml(tipo)}</span>
                    ${valorHtml}
                </div>`;
            });
            contenedor.innerHTML = filas.join('');
            document.getElementById('modal-codigos-csv').classList.remove('hidden');
        }

        function cerrarModalCodigosCsv() {
            document.getElementById('modal-codigos-csv').classList.add('hidden');
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        document.getElementById('modal-codigos-csv').addEventListener('click', function(e) {
            if (e.target === this) {
                cerrarModalCodigosCsv();
            }
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && !document.getElementById('modal-codigos-csv').classList.contains('hidden')) {
                cerrarModalCodigosCsv();
            }
        });
    </script>
</x-app-layout>
