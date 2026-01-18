<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.dashboard') }}">
                <h2 class="font-semibold text-xl text-white leading-tight">
                    Inicio ->
                </h2>
            </a>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Historial de ejecuciones de productos
            </h2>
        </div>
    </x-slot>

    <div class="py-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mb-4 flex justify-between items-center">
            <form method="GET" class="flex gap-2">
                <input type="text" name="buscar" value="{{ $busqueda }}" placeholder="Buscar por fecha..."
                    class="border px-3 py-2 rounded w-64">
                <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">Buscar</button>
            </form>
            <form method="POST" action="{{ route('admin.ejecuciones.eliminar.antiguas') }}" class="flex items-center gap-2 mb-4">
                @csrf
                <label class="text-sm text-gray-700 dark:text-white">Eliminar las</label>
                <input type="number" name="cantidad" min="1" required
                    class="border px-3 py-2 rounded w-24 text-sm">
                <label class="text-sm text-gray-700 dark:text-white">más antiguas</label>
                <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700 text-sm">Eliminar</button>
            </form>
        </div>
        <div class="mb-4 text-sm text-gray-700 dark:text-white">
            Total de ejecuciones: {{ $totalEjecuciones }}
        </div>
        <div class="bg-white shadow rounded-lg overflow-x-auto">


            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Inicio</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fin</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Guardados</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Errores</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse ($ejecuciones as $ejecucion)
                    <tr class="hover:bg-gray-100 dark:hover:bg-gray-300 transition-colors">
                        <td class="px-6 py-4">{{ $ejecucion->inicio->format('Y-m-d H:i') }}</td>
                        <td class="px-6 py-4">
                            {{ $ejecucion->fin ? $ejecucion->fin->format('Y-m-d H:i') : 'En progreso' }}
                        </td>
                        <td class="px-6 py-4">{{ $ejecucion->total }}</td>
                        <td class="px-6 py-4">{{ $ejecucion->total_guardado }}</td>
                        <td class="px-6 py-4 text-red-600">{{ $ejecucion->total_errores }}</td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex justify-end items-center space-x-6">
                                <button onclick="verLog('{{ $ejecucion->id }}')"
                                    class="text-indigo-500 hover:underline">Ver log</button>

                                <form method="POST" action="{{ route('admin.ejecuciones.eliminar', $ejecucion) }}" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                        onclick="return confirm('¿Eliminar esta ejecución?')"
                                        class="text-red-600 hover:underline">Eliminar</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                            No se han encontrado ejecuciones.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-6">
            {{ $ejecuciones->withQueryString()->links() }}
        </div>
    </div>

    {{-- Modal para ver el log --}}
    <div id="modal-log" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-3xl max-h-[80vh] overflow-auto">
            <h2 class="text-lg font-semibold mb-4">Log de la ejecución</h2>
            <pre id="log-contenido" class="whitespace-pre-wrap text-sm bg-gray-100 p-4 rounded overflow-auto"></pre>
            <div class="text-right mt-4">
                <button onclick="document.getElementById('modal-log').classList.add('hidden')"
                    class="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700">Cerrar</button>
            </div>
        </div>
    </div>

    <script>
        async function verLog(id) {
            id = parseInt(id);
            const response = await fetch(`/panel-privado/productos/historico-precios/ejecuciones/${id}/log`);
            const data = await response.json();
            document.getElementById('log-contenido').textContent = JSON.stringify(data.log, null, 2);
            document.getElementById('modal-log').classList.remove('hidden');
        }
    </script>
    {{-- EVITAR TENER QUE PINCHAR DOS VECES EN LOS ENLACES PARA QUE FUNCIONEN--}}
    <script>
document.addEventListener('DOMContentLoaded', function() {
    // Prevenir doble clic en enlaces
    const links = document.querySelectorAll('a[href]');
    links.forEach(link => {
        link.addEventListener('click', function(e) {
            // Si el enlace ya está siendo procesado, prevenir el clic
            if (this.dataset.processing === 'true') {
                e.preventDefault();
                return false;
            }
            
            // Marcar como en procesamiento
            this.dataset.processing = 'true';
            
            // Remover la marca después de un tiempo
            setTimeout(() => {
                this.dataset.processing = 'false';
            }, 2000);
        });
    });
    
    // Prevenir doble clic en botones
    const buttons = document.querySelectorAll('button');
    buttons.forEach(button => {
        button.addEventListener('click', function(e) {
            if (this.dataset.processing === 'true') {
                e.preventDefault();
                return false;
            }
            
            this.dataset.processing = 'true';
            
            setTimeout(() => {
                this.dataset.processing = 'false';
            }, 2000);
        });
    });
});
</script>
</x-app-layout>