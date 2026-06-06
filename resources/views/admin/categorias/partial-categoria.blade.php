@php
    $esPicker = !empty($esPickerCrearMasivo ?? false);
    $colores = $esPicker
        ? [
            0 => 'slate-500',
            1 => 'slate-400',
            2 => 'slate-300',
            3 => 'slate-200',
            4 => 'gray-200',
            5 => 'gray-100',
        ]
        : [
            0 => 'pink-400',
            1 => 'pink-300',
            2 => 'pink-200',
            3 => 'pink-100',
            4 => 'gray-200',
            5 => 'gray-100',
        ];
    $color = $colores[min($nivel, 5)] ?? 'gray-100';
    $ml = $nivel * 4 + 2;
@endphp

@if ($esPicker)
    @php
        $tieneSubcategorias = $categoria->subcategorias->count() > 0;
    @endphp
    <div class="ml-{{ $ml }} border-l-4 border-{{ $color }} pl-2 picker-categoria-item">
        <div class="picker-categoria-fila group flex justify-between items-center gap-2 -mx-1 px-2 py-1.5 rounded-md cursor-pointer border border-transparent transition-all duration-150 hover:bg-slate-100 dark:hover:bg-slate-700/70 hover:border-slate-300 dark:hover:border-slate-500 hover:shadow-sm"
            @if($tieneSubcategorias) @click="toggle({{ $categoria->id }})" @endif>
            <div class="flex items-center gap-2 min-w-0 flex-1">
                @if($tieneSubcategorias)
                    <svg :class="{ 'rotate-90': isOpen({{ $categoria->id }}) }" class="w-4 h-4 shrink-0 transition-transform text-gray-600 dark:text-gray-400"
                        fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"></path>
                    </svg>
                @else
                    <span class="w-4 h-4 shrink-0 inline-block" aria-hidden="true"></span>
                @endif
                @if($tieneSubcategorias)
                    <span class="font-semibold text-gray-800 dark:text-gray-100 truncate group-hover:text-slate-900 dark:group-hover:text-white">{{ $categoria->nombre }}</span>
                @else
                    <button type="button"
                        class="btn-elegir-categoria-arbol-crear-masivo font-semibold text-gray-800 dark:text-gray-100 truncate text-left min-w-0 p-0 bg-transparent border-0 cursor-pointer group-hover:text-blue-600 dark:group-hover:text-blue-400 group-hover:underline underline-offset-2"
                        title="Seleccionar: {{ $categoria->nombre }}"
                        data-categoria-id="{{ $categoria->id }}"
                        data-categoria-nombre="{{ $categoria->nombre }}"
                        @click.stop>
                        {{ $categoria->nombre }}
                    </button>
                @endif
                @if(($categoria->mostrar ?? 'si') === 'no')
                    <span class="text-sm font-medium text-red-600 dark:text-red-400 shrink-0">Mostrar - no</span>
                @endif
                <span class="text-sm text-gray-500 dark:text-gray-400 shrink-0">(Nivel {{ $nivel + 1 }})</span>
            </div>
            <div class="flex gap-2 items-center shrink-0">
                <span class="text-sm text-gray-600 dark:text-gray-400 whitespace-nowrap">{{ $categoria->productos_count }} productos</span>
                @unless($tieneSubcategorias)
                    <button type="button"
                        class="btn-elegir-categoria-arbol-crear-masivo bg-blue-600 hover:bg-blue-700 group-hover:bg-blue-700 text-white px-3 py-1 rounded text-xs font-medium shadow-sm group-hover:shadow"
                        title="Seleccionar: {{ $categoria->nombre }}"
                        data-categoria-id="{{ $categoria->id }}"
                        data-categoria-nombre="{{ $categoria->nombre }}"
                        @click.stop>
                        Seleccionar
                    </button>
                @endunless
            </div>
        </div>
        @if($tieneSubcategorias)
            <div x-show="isOpen({{ $categoria->id }})" x-cloak class="ml-6 mt-1 space-y-1 border-l-4 border-{{ $color }} pl-3">
                @foreach ($categoria->subcategorias as $subcategoria)
                    @include('admin.categorias.partial-categoria', ['categoria' => $subcategoria, 'nivel' => $nivel + 1, 'esPickerCrearMasivo' => true])
                @endforeach
            </div>
        @endif
    </div>
@else
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
            @if(($categoria->mostrar ?? 'si') === 'no')
                <span class="text-sm font-medium text-red-600 dark:text-red-400">Mostrar - no</span>
            @endif
            <span class="text-sm text-{{ $color }}">(Nivel {{ $nivel + 1 }})</span>
            @if($categoria->subcategorias->count() > 0)
                <span class="text-sm text-gray-600 dark:text-gray-400">Mostradas ({{ $categoria->mostradas_descendientes_count ?? 0 }}/{{ $categoria->total_descendientes_count ?? 0 }})</span>
            @endif
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
                @include('admin.categorias.partial-categoria', ['categoria' => $subcategoria, 'nivel' => $nivel + 1, 'esPickerCrearMasivo' => false])
            @endforeach
        </div>
    @endif
</div>
@endif
