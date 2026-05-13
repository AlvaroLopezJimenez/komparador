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

            {{-- Botones auxiliares --}}
            <div class="flex flex-wrap gap-2">
                <button type="button" id="btnAbrirModalEnvio"
                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Modificar envío
                </button>
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

    {{-- Modal: modificar gasto de envío en bloque --}}
    <div id="modalModificarEnvio" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-modal="true" role="dialog">
        <div class="flex min-h-full items-center justify-center p-4">
            <div id="modalModificarEnvioBackdrop" class="fixed inset-0 bg-black/50"></div>
            <div class="relative w-full max-w-lg rounded-lg bg-white dark:bg-gray-800 shadow-xl border border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between border-b border-gray-200 dark:border-gray-700 px-4 py-3">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Modificar envío</h3>
                    <button type="button" id="btnCerrarModalEnvio" class="text-gray-500 hover:text-gray-800 dark:hover:text-gray-200 text-2xl leading-none">&times;</button>
                </div>
                <div class="px-4 py-4 space-y-4">
                    <p class="text-sm text-gray-600 dark:text-gray-300">
                        Ofertas de <strong>{{ $tienda->nombre }}</strong>: elige qué gastos de envío actuales quieres sustituir e indica el nuevo importe en euros.
                    </p>
                    <div id="modalEnvioCargando" class="text-sm text-gray-500 hidden">Cargando resumen…</div>
                    <div id="modalEnvioVacio" class="text-sm text-amber-700 dark:text-amber-400 hidden">No hay ofertas en esta tienda; no se puede agrupar por envío.</div>
                    <div id="modalEnvioLista" class="space-y-2 max-h-60 overflow-y-auto hidden"></div>
                    <div id="modalEnvioForm" class="space-y-3 hidden">
                        <div>
                            <label for="nuevoEnvioEuros" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nuevo gasto de envío (€)</label>
                            <input type="number" id="nuevoEnvioEuros" step="0.01" min="0" max="99.99"
                                class="w-full px-3 py-2 rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white"
                                placeholder="Ej. 4,99">
                        </div>
                        <div class="flex gap-2 justify-end">
                            <button type="button" id="btnCancelarModalEnvio"
                                class="px-4 py-2 rounded border border-gray-300 dark:border-gray-600 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700">Cancelar</button>
                            <button type="button" id="btnGuardarModalEnvio"
                                class="px-4 py-2 rounded bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 disabled:opacity-50">Guardar</button>
                        </div>
                    </div>
                    <div id="modalEnvioResultado" class="hidden text-sm rounded-md px-3 py-2"></div>
                </div>
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

        // Mantener búsqueda al cambiar cantidad
        document.getElementById('perPage').addEventListener('change', function() {
            document.getElementById('perPageHidden').value = this.value;
        });

        (function() {
            const modal = document.getElementById('modalModificarEnvio');
            const backdrop = document.getElementById('modalModificarEnvioBackdrop');
            const btnAbrir = document.getElementById('btnAbrirModalEnvio');
            const btnCerrar = document.getElementById('btnCerrarModalEnvio');
            const btnCancelar = document.getElementById('btnCancelarModalEnvio');
            const btnGuardar = document.getElementById('btnGuardarModalEnvio');
            const elCargando = document.getElementById('modalEnvioCargando');
            const elVacio = document.getElementById('modalEnvioVacio');
            const elLista = document.getElementById('modalEnvioLista');
            const elForm = document.getElementById('modalEnvioForm');
            const elResultado = document.getElementById('modalEnvioResultado');
            const inputNuevo = document.getElementById('nuevoEnvioEuros');

            const urlResumen = @json(route('admin.tiendas.ofertas.resumen-envio', $tienda));
            const urlGuardar = @json(route('admin.tiendas.ofertas.modificar-envio-masivo', $tienda));
            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

            function mostrarResultado(ok, texto, errores) {
                elResultado.classList.remove('hidden', 'bg-green-100', 'text-green-800', 'bg-red-100', 'text-red-800', 'dark:bg-green-900/40', 'dark:text-green-200', 'dark:bg-red-900/40', 'dark:text-red-200');
                if (ok) {
                    elResultado.classList.add('bg-green-100', 'text-green-800', 'dark:bg-green-900/40', 'dark:text-green-200');
                } else {
                    elResultado.classList.add('bg-red-100', 'text-red-800', 'dark:bg-red-900/40', 'dark:text-red-200');
                }
                let html = texto;
                if (errores && errores.length) {
                    html += '<ul class="mt-2 list-disc pl-5">' + errores.map(e => '<li>' + String(e).replace(/</g, '&lt;') + '</li>').join('') + '</ul>';
                }
                elResultado.innerHTML = html;
                elResultado.classList.remove('hidden');
            }

            function resetModalUi() {
                elCargando.classList.add('hidden');
                elVacio.classList.add('hidden');
                elLista.classList.add('hidden');
                elLista.innerHTML = '';
                elForm.classList.add('hidden');
                elResultado.classList.add('hidden');
                elResultado.innerHTML = '';
                inputNuevo.value = '';
                btnGuardar.disabled = false;
            }

            function abrirModal() {
                modal.classList.remove('hidden');
                resetModalUi();
                elCargando.classList.remove('hidden');
                fetch(urlResumen, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                }).then(r => r.json()).then(data => {
                    elCargando.classList.add('hidden');
                    const grupos = data.grupos || [];
                    if (!grupos.length) {
                        elVacio.classList.remove('hidden');
                        return;
                    }
                    elLista.classList.remove('hidden');
                    elForm.classList.remove('hidden');
                    grupos.forEach(function(g, idx) {
                        const id = 'chkEnvio_' + idx;
                        const row = document.createElement('label');
                        row.className = 'flex items-start gap-3 p-2 rounded border border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer';
                        row.innerHTML = '<input type="checkbox" class="mt-1 chk-envio-grupo" data-clave="' + String(g.clave).replace(/"/g, '&quot;') + '" id="' + id + '" checked>' +
                            '<span class="text-sm text-gray-800 dark:text-gray-200">' + String(g.texto).replace(/</g, '&lt;') + '</span>';
                        elLista.appendChild(row);
                    });
                }).catch(function() {
                    elCargando.classList.add('hidden');
                    mostrarResultado(false, 'No se pudo cargar el resumen de envíos.', []);
                });
            }

            function cerrarModal() {
                modal.classList.add('hidden');
            }

            btnAbrir.addEventListener('click', abrirModal);
            btnCerrar.addEventListener('click', cerrarModal);
            btnCancelar.addEventListener('click', cerrarModal);
            backdrop.addEventListener('click', cerrarModal);

            btnGuardar.addEventListener('click', function() {
                const checks = elLista.querySelectorAll('.chk-envio-grupo:checked');
                const claves = Array.from(checks).map(function(c) { return c.getAttribute('data-clave'); });
                const raw = String(inputNuevo.value || '').trim().replace(',', '.');
                const nuevo = parseFloat(raw);

                elResultado.classList.add('hidden');
                if (!claves.length) {
                    mostrarResultado(false, 'Marca al menos un tipo de gasto de envío.', []);
                    return;
                }
                if (raw === '' || Number.isNaN(nuevo) || nuevo < 0 || nuevo > 99.99) {
                    mostrarResultado(false, 'Indica un importe válido entre 0 y 99,99 €.', []);
                    return;
                }

                btnGuardar.disabled = true;
                fetch(urlGuardar, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({ nuevo_envio: nuevo, claves_envio: claves })
                }).then(async function(r) {
                    const data = await r.json().catch(function() { return {}; });
                    btnGuardar.disabled = false;
                    if (r.ok && data.ok) {
                        mostrarResultado(true, data.mensaje || 'Cambios guardados.', data.errores || []);
                        fetch(urlResumen, {
                            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                        }).then(r2 => r2.json()).then(data2 => {
                            const grupos2 = data2.grupos || [];
                            elLista.innerHTML = '';
                            if (!grupos2.length) {
                                elLista.classList.add('hidden');
                                elForm.classList.add('hidden');
                                elVacio.classList.remove('hidden');
                                return;
                            }
                            elVacio.classList.add('hidden');
                            grupos2.forEach(function(g, idx) {
                                const id = 'chkEnvio_' + idx;
                                const row = document.createElement('label');
                                row.className = 'flex items-start gap-3 p-2 rounded border border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer';
                                row.innerHTML = '<input type="checkbox" class="mt-1 chk-envio-grupo" data-clave="' + String(g.clave).replace(/"/g, '&quot;') + '" id="' + id + '" checked>' +
                                    '<span class="text-sm text-gray-800 dark:text-gray-200">' + String(g.texto).replace(/</g, '&lt;') + '</span>';
                                elLista.appendChild(row);
                            });
                        }).catch(function() { /* ignorar */ });
                    } else {
                        let msg = data.mensaje || data.message || 'No se pudo completar la operación.';
                        if (data.errors) {
                            const flat = Object.values(data.errors).flat();
                            if (flat.length) msg += ' ' + flat.join(' ');
                        }
                        mostrarResultado(false, msg, data.errores || []);
                    }
                }).catch(function() {
                    btnGuardar.disabled = false;
                    mostrarResultado(false, 'Error de red al guardar.', []);
                });
            });
        })();
    </script>
</x-app-layout>