<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.dashboard') }}">
                <h2 class="font-semibold text-xl text-white leading-tight">
                    Inicio ->
                </h2>
            </a>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Productos
            </h2>
        </div>
    </x-slot>
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold">Historial de Actualización de Clicks</h2>
                    <a href="{{ route('admin.productos.actualizar.clicks.ejecutar') }}" 
                       class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                        Nueva Ejecución
                    </a>
                </div>

                @if($ejecuciones->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full bg-white border border-gray-300">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 border-b border-gray-200 bg-gray-50 text-left text-xs leading-4 font-medium text-gray-500 uppercase tracking-wider">
                                        Fecha
                                    </th>
                                    <th class="px-6 py-3 border-b border-gray-200 bg-gray-50 text-left text-xs leading-4 font-medium text-gray-500 uppercase tracking-wider">
                                        Duración
                                    </th>
                                    <th class="px-6 py-3 border-b border-gray-200 bg-gray-50 text-left text-xs leading-4 font-medium text-gray-500 uppercase tracking-wider">
                                        Total
                                    </th>
                                    <th class="px-6 py-3 border-b border-gray-200 bg-gray-50 text-left text-xs leading-4 font-medium text-gray-500 uppercase tracking-wider">
                                        Guardados
                                    </th>
                                    <th class="px-6 py-3 border-b border-gray-200 bg-gray-50 text-left text-xs leading-4 font-medium text-gray-500 uppercase tracking-wider">
                                        Errores
                                    </th>
                                    <th class="px-6 py-3 border-b border-gray-200 bg-gray-50 text-left text-xs leading-4 font-medium text-gray-500 uppercase tracking-wider">
                                        Acciones
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white">
                                @foreach($ejecuciones as $ejecucion)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-no-wrap text-sm leading-5 text-gray-900">
                                            {{ $ejecucion->created_at->format('d/m/Y H:i:s') }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-no-wrap text-sm leading-5 text-gray-900">
                                            @if($ejecucion->fin)
                                                {{ $ejecucion->inicio->diffInSeconds($ejecucion->fin) }}s
                                            @else
                                                <span class="text-yellow-600">En progreso</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-no-wrap text-sm leading-5 text-gray-900">
                                            {{ $ejecucion->total }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-no-wrap text-sm leading-5 text-green-600">
                                            {{ $ejecucion->total_guardado }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-no-wrap text-sm leading-5 text-red-600">
                                            {{ $ejecucion->total_errores }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-no-wrap text-sm leading-5 text-gray-900">
                                            <button onclick="verLog({{ $ejecucion->id }})" 
                                                    class="text-blue-600 hover:text-blue-900 mr-3">
                                                Ver Log
                                            </button>
                                            <form action="{{ route('admin.productos.actualizar.clicks.ejecucion.eliminar', $ejecucion->id) }}" 
                                                  method="POST" 
                                                  class="inline"
                                                  onsubmit="return confirm('¿Estás seguro de que quieres eliminar esta ejecución?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:text-red-900">
                                                    Eliminar
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-6">
                        {{ $ejecuciones->links() }}
                    </div>
                @else
                    <div class="text-center py-8">
                        <div class="text-gray-500">
                            <svg class="w-16 h-16 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <h3 class="text-lg font-semibold mb-2">No hay ejecuciones</h3>
                            <p class="text-gray-600">Aún no se han realizado ejecuciones de actualización de clicks.</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- MODAL PARA VER LOG -->
<div id="modalLog" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen">
        <div class="bg-white rounded-lg p-6 max-w-4xl w-full mx-4 max-h-[80vh] overflow-hidden">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold">Log de Ejecución</h3>
                <button onclick="cerrarModalLog()" class="text-gray-500 hover:text-gray-700">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <div id="logContent" class="max-h-96 overflow-y-auto bg-gray-100 p-4 rounded text-sm font-mono">
                <!-- El contenido del log se cargará aquí -->
            </div>
        </div>
    </div>
</div>

<script>
function verLog(ejecucionId) {
    const modalLog = document.getElementById('modalLog');
    const logContent = document.getElementById('logContent');
    
    // Mostrar modal
    modalLog.classList.remove('hidden');
    logContent.innerHTML = '<div class="text-center text-gray-500">Cargando log...</div>';
    
    // Cargar log
    fetch(`/panel-privado/productos/actualizar-clicks/ejecuciones/${ejecucionId}/json`)
        .then(response => response.json())
        .then(data => {
            if (data.log && data.log.length > 0) {
                const logHtml = data.log.map(entry => {
                    const status = entry.status === 'actualizado' ? '✅' : '❌';
                    const errorMsg = entry.error ? ` - Error: ${entry.error}` : '';
                    return `<div class="mb-1">
                        <span class="text-gray-500">[${entry.producto_id}]</span> 
                        <span class="font-medium">${entry.nombre}</span>
                        ${status} ${entry.clicks || 0} clicks${errorMsg}
                    </div>`;
                }).join('');
                logContent.innerHTML = logHtml;
            } else {
                logContent.innerHTML = '<div class="text-center text-gray-500">No hay log disponible</div>';
            }
        })
        .catch(error => {
            logContent.innerHTML = '<div class="text-center text-red-500">Error al cargar el log</div>';
            console.error('Error:', error);
        });
}

function cerrarModalLog() {
    document.getElementById('modalLog').classList.add('hidden');
}

// Cerrar modal al hacer clic fuera
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