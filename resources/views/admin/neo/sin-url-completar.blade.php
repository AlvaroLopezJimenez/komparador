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
                Neo sin URL (completar por CSV)
            </h2>
        </div>
    </x-slot>

    @php
        $tieneSecretoNeo = (string) config('anti-scraping.neoobjetivo_url_secret', '') !== '';
    @endphp

    <div class="max-w-7xl mx-auto py-10 px-4 space-y-8">
        @unless($tieneSecretoNeo)
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6 border border-gray-200 dark:border-gray-700">
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    No hay secreto de cifrado configurado: los valores <code class="text-xs bg-gray-100 dark:bg-gray-700 px-1 rounded">neo</code> en base de datos se tratan como texto en claro. Puedes dejar la contraseña vacía.
                </p>
            </div>
        @endunless

        @if ($errors->any())
            <div class="rounded-xl border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 px-4 py-3 text-sm text-red-800 dark:text-red-200">
                <ul class="list-disc list-inside space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (session('import_resultado'))
            @php $res = session('import_resultado'); @endphp
            <div class="rounded-xl border border-green-200 dark:border-green-800 bg-green-50 dark:bg-green-900/20 px-6 py-4 space-y-3">
                <h3 class="text-lg font-semibold text-green-900 dark:text-green-100">Resultado de la importación</h3>
                <ul class="text-sm text-green-900 dark:text-green-100 space-y-1">
                    <li>Líneas leídas en el CSV: <strong>{{ $res['lineas_procesadas'] ?? 0 }}</strong></li>
                    <li>Filas <strong>neo</strong> actualizadas (guardados): <strong>{{ $res['filas_actualizadas'] ?? 0 }}</strong></li>
                    <li>Filas con <strong>aniadida = si</strong> (URL ya existía en <code class="text-xs">ofertas_producto.url</code>): <strong>{{ $res['filas_marcadas_aniadida_si'] ?? 0 }}</strong></li>
                    <li>Líneas omitidas (vacías): <strong>{{ $res['lineas_omitidas_vacias'] ?? 0 }}</strong></li>
                    <li>Líneas omitidas por <code class="text-xs">relocator/relocate</code>: <strong>{{ $res['lineas_omitidas_relocator'] ?? 0 }}</strong></li>
                </ul>
                @if (!empty($res['no_encontradas']))
                    <div>
                        <p class="text-sm font-medium text-amber-900 dark:text-amber-100 mb-2">Sin coincidencia (neo no encontrado o la fila ya tenía URL):</p>
                        <ul class="text-xs font-mono text-amber-900 dark:text-amber-100 space-y-1 max-h-48 overflow-y-auto break-all">
                            @foreach ($res['no_encontradas'] as $item)
                                <li>Línea {{ $item['linea'] ?? '?' }} — {{ $item['neo_url'] ?? '' }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                @if (!empty($res['errores']))
                    <div>
                        <p class="text-sm font-medium text-red-900 dark:text-red-100 mb-2">Errores por línea:</p>
                        <ul class="text-xs text-red-900 dark:text-red-100 space-y-1 max-h-48 overflow-y-auto">
                            @foreach ($res['errores'] as $err)
                                <li>Línea {{ $err['linea'] ?? '?' }} — {{ $err['mensaje'] ?? '' }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        @endif

        <div class="grid gap-6 md:grid-cols-2">
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6 border border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Descargar CSV</h3>
                <form method="POST" action="{{ route('admin.neo.sin-url-completar.descargar-csv') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label for="dl_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Contraseña de descifrado
                        </label>
                        <input type="password" name="decryption_password" id="dl_password" autocomplete="off"
                            @if($tieneSecretoNeo) required @endif
                            class="w-full px-3 py-2 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm"
                            placeholder="{{ $tieneSecretoNeo ? 'Mismo valor que neoobjetivo_url_secret' : 'Opcional si no hay secreto' }}">
                    </div>
                    <button type="submit"
                        class="inline-flex items-center bg-blue-600 hover:bg-blue-700 text-white font-semibold px-5 py-2.5 rounded-md transition text-sm">
                        Descargar CSV
                    </button>
                </form>
            </div>

            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6 border border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Subir CSV</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                    La <strong>primera columna</strong> es el valor de <strong>neo</strong> en claro (el mismo que en el CSV descargado).
                    La <strong>segunda columna</strong> son las <strong>URLs de tienda</strong> (enlace al producto en cada tienda).
                    Tras normalizar con <code class="text-xs">LimpiarUrlDeTiendas</code>, si esa URL coincide con alguna fila de <code class="text-xs">ofertas_producto.url</code>, se guarda igual en <code class="text-xs">neo.url</code> y en esa fila <strong>neo</strong> se pone <strong>aniadida = si</strong>; si no hay coincidencia, solo se rellena <code class="text-xs">url</code> y <strong>aniadida</strong> no se modifica.
                </p>
                <form method="POST" action="{{ route('admin.neo.sin-url-completar.subir-csv') }}" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    <div>
                        <label for="up_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Contraseña de cifrado / descifrado
                        </label>
                        <input type="password" name="decryption_password" id="up_password" autocomplete="off"
                            @if($tieneSecretoNeo) required @endif
                            class="w-full px-3 py-2 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm"
                            placeholder="{{ $tieneSecretoNeo ? 'Mismo valor que al descargar' : 'Opcional si no hay secreto' }}">
                    </div>
                    <div>
                        <label for="csv" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Archivo CSV</label>
                        <input type="file" name="csv" id="csv" accept=".csv,.txt" required
                            class="block w-full text-sm text-gray-700 dark:text-gray-300 file:mr-3 file:py-2 file:px-4 file:rounded-md file:border-0 file:bg-gray-100 file:text-gray-800 dark:file:bg-gray-600 dark:file:text-white">
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Formato: columnas <code>neo_url</code> y <code>url_destino</code> (coma o punto y coma). Máx. 5&nbsp;MB. Las URLs de tienda se normalizan con <code>LimpiarUrlDeTiendas</code> y se cotejan con <code>ofertas_producto.url</code> para marcar <code>aniadida</code>.</p>
                    </div>
                    <button type="submit"
                        class="inline-flex items-center bg-emerald-600 hover:bg-emerald-700 text-white font-semibold px-5 py-2.5 rounded-md transition text-sm">
                        Subir CSV
                    </button>
                </form>
            </div>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-4">
            <p class="text-sm text-gray-600 dark:text-gray-400">
                Total en esta página: {{ $neos->total() }} filas con URL vacía.
            </p>
            <form method="GET" class="flex items-center gap-2">
                <label for="perPage" class="text-sm text-gray-700 dark:text-gray-300">Mostrar:</label>
                <select id="perPage" name="perPage"
                    class="px-2 py-1 rounded border bg-white dark:bg-gray-800 text-sm text-gray-800 dark:text-white"
                    onchange="this.form.submit()">
                    @foreach ([20, 50, 100, 200] as $cantidad)
                        <option value="{{ $cantidad }}" {{ (int) $perPage === $cantidad ? 'selected' : '' }}>
                            {{ $cantidad }}
                        </option>
                    @endforeach
                </select>
            </form>
        </div>

        <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-x-auto border border-gray-200 dark:border-gray-700">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-600">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">ID</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Producto</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Tienda</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Categoría</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Añadida</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Alta</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-600">
                    @forelse ($neos as $n)
                        <tr class="text-sm text-gray-900 dark:text-gray-100">
                            <td class="px-4 py-3 whitespace-nowrap font-mono">{{ $n->id }}</td>
                            <td class="px-4 py-3">
                                @if ($n->producto)
                                    {{ $n->producto->nombre }}
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                {{ $n->tienda?->nombre ?? '—' }}
                            </td>
                            <td class="px-4 py-3">
                                {{ $n->categoria?->nombre ?? '—' }}
                            </td>
                            <td class="px-4 py-3">{{ $n->aniadida }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-xs text-gray-500 dark:text-gray-400">
                                {{ $n->created_at?->format('Y-m-d H:i') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                No hay filas con URL vacía y neo rellenado.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="pb-6">
            {{ $neos->links() }}
        </div>
    </div>
</x-app-layout>
