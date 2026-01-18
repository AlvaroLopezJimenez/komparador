@php
    $colores = [
        0 => 'pink-400',
        1 => 'pink-300', 
        2 => 'pink-200',
        3 => 'pink-100',
        4 => 'gray-200',
        5 => 'gray-100'
    ];
    $color = $colores[min($nivel, 5)] ?? 'gray-100';
    $ml = $nivel * 4 + 2;
@endphp

<div class="ml-{{ $ml }} border-l-4 border-{{ $color }} pl-2">
    <div class="flex justify-between items-center cursor-pointer py-1"
        @click="toggle({{ $categoria->id }})">
        <div class="flex items-center gap-2">
            <svg :class="{ 'rotate-90': isOpen({{ $categoria->id }}) }" class="w-4 h-4 transition-transform"
                fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"
                xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"></path>
            </svg>
            <span id="categoria-nombre-{{ $categoria->id }}" class="font-semibold text-gray-800 dark:text-gray-100 transition-all duration-200">{{ $categoria->nombre }}</span>
            <span class="text-sm text-{{ $color }}">(Nivel {{ $nivel + 1 }})</span>
        </div>
        <div class="flex gap-2 items-center">
            <span class="text-sm text-gray-600 dark:text-gray-400">{{ $categoria->productos_count }} productos</span>
            <a href="{{ route('admin.categorias.edit', $categoria) }}"
                class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-xs font-medium"
                title="Editar categoría: {{ $categoria->nombre }}"
                onmouseover="document.getElementById('categoria-nombre-{{ $categoria->id }}').style.color='#1d4ed8';"
                onmouseout="document.getElementById('categoria-nombre-{{ $categoria->id }}').style.color='';">
                ✏️ Editar
            </a>
        </div>
    </div>

    @if($categoria->subcategorias->count() > 0)
        <div x-show="isOpen({{ $categoria->id }})" class="ml-6 mt-1 space-y-1 border-l-4 border-{{ $color }} pl-3">
            @foreach ($categoria->subcategorias as $subcategoria)
                @include('admin.categorias.partial-categoria', ['categoria' => $subcategoria, 'nivel' => $nivel + 1])
            @endforeach
        </div>
    @endif
</div>
