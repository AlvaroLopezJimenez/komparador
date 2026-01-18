<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.dashboard') }}">
                <h2 class="font-semibold text-xl text-white leading-tight">Inicio -></h2>
            </a>
            <a href="{{ route('admin.categorias.index') }}">
                <h2 class="font-semibold text-xl text-white leading-tight">Categorías -></h2>
            </a>
            <h2 class="font-semibold text-xl text-white leading-tight">Historial de Ejecuciones Precios Hot</h2>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto py-10 px-4 space-y-8 bg-gray-50 dark:bg-gray-900 rounded-lg shadow-md">
        
        <!-- Resumen -->
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6 border border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-200 mb-4">Resumen</h3>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg">
                    <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $ejecuciones->count() }}</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Total Ejecuciones</div>
                </div>
                <div class="bg-green-50 dark:bg-green-900/20 p-4 rounded-lg">
                    <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $ejecuciones->sum('total_guardado') }}</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Total Inserciones</div>
                </div>
                <div class="bg-orange-50 dark:bg-orange-900/20 p-4 rounded-lg">
                    <div class="text-2xl font-bold text-orange-600 dark:text-orange-400">{{ $ejecuciones->sum('total') }}</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Total Categorías</div>
                </div>
                <div class="bg-red-50 dark:bg-red-900/20 p-4 rounded-lg">
                    <div class="text-2xl font-bold text-red-600 dark:text-red-400">{{ $ejecuciones->sum('total_errores') }}</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Total Errores</div>
                </div>
            </div>
        </div>

        <!-- Lista de Ejecuciones -->
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6 border border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-200 mb-4">Ejecuciones</h3>
            
            @if($ejecuciones->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    ID
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Inicio
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Fin
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Duración
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Categorías
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Inserciones
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Errores
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Estado
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Acciones
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($ejecuciones as $ejecucion)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                        {{ $ejecucion->id }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        {{ $ejecucion->inicio->format('d/m/Y H:i:s') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        {{ $ejecucion->fin ? $ejecucion->fin->format('d/m/Y H:i:s') : '-' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        @if($ejecucion->fin)
                                            {{ $ejecucion->inicio->diffForHumans($ejecucion->fin, true) }}
                                        @else
                                            En progreso...
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        {{ $ejecucion->total }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        <span class="text-green-600 dark:text-green-400 font-medium">
                                            {{ $ejecucion->total_guardado }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        <span class="text-red-600 dark:text-red-400 font-medium">
                                            {{ $ejecucion->total_errores }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($ejecucion->fin)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                                Completada
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                                                En progreso
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <button onclick="verLog({{ $ejecucion->id }})" 
                                            class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300">
                                            Ver Log
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-8">
                    <div class="text-gray-500 dark:text-gray-400 text-lg">No hay ejecuciones registradas</div>
                    <div class="text-gray-400 dark:text-gray-500 text-sm mt-2">Las ejecuciones aparecerán aquí una vez que se ejecute el proceso</div>
                </div>
            @endif
        </div>
    </div>

    <!-- Modal para ver log -->
    <div id="modalLog" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white dark:bg-gray-900 rounded-lg shadow-lg p-6 w-full max-w-4xl max-h-[80vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white">Log de Ejecución</h3>
                <button onclick="cerrarModalLog()" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div id="logContent" class="bg-gray-100 dark:bg-gray-700 rounded-lg p-4 font-mono text-sm max-h-96 overflow-y-auto">
                <!-- El contenido del log se cargará aquí -->
            </div>
        </div>
    </div>

    <script>
        function verLog(ejecucionId) {
            // Aquí podrías hacer una llamada AJAX para obtener el log específico
            // Por ahora, mostraremos un mensaje de ejemplo
            document.getElementById('logContent').innerHTML = `
                <div class="text-gray-600 dark:text-gray-300">
                    <div class="mb-2">📋 Log de la ejecución #${ejecucionId}</div>
                    <div class="mb-1">✅ Categoría 'Pañales': 15 productos hot encontrados</div>
                    <div class="mb-1">✅ Categoría 'Toallitas': 12 productos hot encontrados</div>
                    <div class="mb-1">⚠️ Categoría 'Cremas': No se encontraron productos hot</div>
                    <div class="mb-1">✅ Categoría global 'Precios Hot': 45 productos hot encontrados</div>
                </div>
            `;
            document.getElementById('modalLog').classList.remove('hidden');
        }

        function cerrarModalLog() {
            document.getElementById('modalLog').classList.add('hidden');
        }

        // Cerrar modal al hacer clic fuera de él
        document.getElementById('modalLog').addEventListener('click', function(e) {
            if (e.target === this) {
                cerrarModalLog();
            }
        });
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