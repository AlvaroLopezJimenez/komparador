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

    <div class="py-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mb-4 flex justify-between items-center">
            <form method="GET" class="flex gap-2">
                <input type="text" name="buscar" value="{{ $busqueda }}" placeholder="Buscar..."
                    class="border px-3 py-2 rounded w-64">
                <select name="mostrar" class="border px-3 py-2 rounded">
                    <option value="todos" {{ $mostrarFiltro === 'todos' ? 'selected' : '' }}>Todos los productos</option>
                    <option value="si" {{ $mostrarFiltro === 'si' ? 'selected' : '' }}>Mostrar: Sí</option>
                    <option value="no" {{ $mostrarFiltro === 'no' ? 'selected' : '' }}>Mostrar: No</option>
                </select>
                <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">Filtrar</button>
            </form>
            <a href="{{ route('admin.productos.create') }}"
                class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">+ Añadir producto</a>
        </div>

        <div class="bg-white shadow rounded-lg overflow-x-auto dark:bg-gray-300">
            <table class="min-w-full divide-y divide-gray-800">
                <thead class="bg-gray-400">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-800 uppercase tracking-wider">Nombre</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-800 uppercase tracking-wider">Mostrar</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-800 uppercase tracking-wider">Ofertas</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-800 uppercase tracking-wider">Precio</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-800 uppercase tracking-wider">Actualizado</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-800 uppercase tracking-wider">Clicks (30d)</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-800 uppercase tracking-wider">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-800">
                    @foreach ($productos as $producto)
                    <tr class="hover:bg-gray-100 dark:hover:bg-gray-400 transition-colors">
                        <td class="px-6 py-4">{{ $producto->nombre }}</td>
                        <td class="px-6 py-4">
                            @if($producto->mostrar === 'si')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    ✓ Sí
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                    ✗ No
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4">{{ $producto->ofertas_visibles_count }}/{{ $producto->ofertas_count }}</td>
                        <td class="px-6 py-4">{{ $producto->precio }}</td>
                        <td class="px-6 py-4">{{ $producto->updated_at }}</td>
                        <td class="px-6 py-4">{{ $producto->clicks_ultimos_30_dias }}</td>
                        <td class="px-6 py-4 text-right">
                            @if ($producto->categoria)
                                <a href="{{ $producto->categoria->construirUrlCategorias($producto->slug) }}"
                                    target="_blank"
                                    class="text-green-500 hover:underline mr-4">Ir</a>
                            @else
                                <span class="text-gray-400 italic">Sin categoría</span>
                            @endif
                            <a href="{{ route('admin.ofertas.index', $producto) }}"
                                class="text-purple-500 hover:underline mr-4">Ofertas</a>
                            <a href="{{ route('admin.productos.estadisticas', $producto) }}"
                                class="text-indigo-500 hover:underline mr-4">Estadísticas</a>
                            <a href="{{ route('admin.productos.edit', $producto) }}"
                                class="text-blue-500 hover:underline mr-4">Editar</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-6">
            {{ $productos->withQueryString()->links() }}
        </div>
    </div>
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