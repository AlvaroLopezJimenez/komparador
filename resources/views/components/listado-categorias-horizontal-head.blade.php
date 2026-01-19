@php
// añadirCam() -> _f1() - Función para añadir parámetro cam a URLs de forma segura
if (!function_exists('_f1')) {
    function _f1($url) {
        if (!request()->has('cam')) {
            return $url;
        }
        
        $cam = request('cam');
        
        // Validar que solo contenga caracteres alfanuméricos, guiones y guiones bajos
        // Esto evita caracteres especiales que puedan romper la URL
        if (!preg_match('/^[a-zA-Z0-9\-_]+$/', $cam)) {
            // Si el parámetro cam no es válido, simplemente no lo añadimos
            return $url;
        }
        
        // Construir la URL de forma segura
        $separator = strpos($url, '?') !== false ? '&' : '?';
        // Aunque ya validamos, urlencode() añade una capa extra de seguridad
        return $url . $separator . 'cam=' . urlencode($cam);
    }
}
@endphp

{{-- BARRA DE CATEGORÍAS --}}
<div class="sticky top-0 z-40 bg-gray-50/95 backdrop-blur-sm border-b border-gray-200/50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-2 sm:py-3">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-2 sm:space-x-5 overflow-x-auto pb-1 scrollbar-hide" style="-webkit-overflow-scrolling: touch;">
                {{-- btnCategoriasBarra -> bcb1 --}}
                <button type="button" id="bcb1" class="group flex flex-col items-center space-y-1 sm:space-y-1.5 min-w-[60px] sm:min-w-[80px] flex-shrink-0 transition-all duration-200">
                    <div class="w-10 h-10 sm:w-14 sm:h-14 bg-purple-100 rounded-xl flex items-center justify-center transition-all duration-200 group-hover:bg-purple-200 group-hover:scale-105">
                        <svg class="w-6 h-6 sm:w-8 sm:h-9 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                        </svg>
                    </div>
                    <span class="categoria-texto-movil text-[8px] sm:text-sm font-medium text-gray-700 group-hover:text-purple-600 text-center transition-colors">Categorías</span>
                </button>
                {{-- añadirCam() -> _f1() --}}
                <a href="{{ _f1(route('categoria.show', 'electronica')) }}" class="group flex flex-col items-center space-y-1 sm:space-y-1.5 min-w-[60px] sm:min-w-[80px] flex-shrink-0 transition-all duration-200">
                    <div class="w-10 h-10 sm:w-14 sm:h-14 rounded-xl flex items-center justify-center transition-all duration-200 group-hover:scale-105" style="background-color: #d4e9b8;">
                        <img src="{{ asset('images/categorias/electronica.webp') }}" 
                             alt="Icono categoria Electrónica" 
                             class="w-6 h-6 sm:w-8 sm:h-9 transition-all duration-200" />
                    </div>
                    <span class="categoria-texto-movil text-[8px] sm:text-sm font-medium text-gray-700 text-center transition-colors">Electrónica</span>
                </a>


                {{-- añadirCam() -> _f1() --}}
                <a href="{{ _f1(route('categorias.todas')) }}"
                   class="group flex flex-col items-center space-y-1 sm:space-y-1.5 min-w-[60px] sm:min-w-[80px] flex-shrink-0 transition-all duration-200 sm:hidden"
                >
                    <div class="w-10 h-10 sm:w-14 sm:h-14 rounded-xl flex items-center justify-center transition-all duration-200 group-hover:scale-105" style="background-color: #fef3e7;">
                        <svg class="w-6 h-6 sm:w-8 sm:h-9" style="color: #e97b11;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                    </div>
                    <span class="categoria-texto-movil text-[8px] sm:text-sm font-medium text-gray-700 text-center transition-colors group-hover:[color:#e97b11]">Más <span style="color: #e97b11;">></span></span>
                </a>
            </div>
            {{-- añadirCam() -> _f1() --}}
            <a href="{{ _f1(route('categorias.todas')) }}"
               style="background-color: #e97b11;"
               class="hidden sm:flex items-center justify-center text-white rounded-lg transition-colors flex-shrink-0 ml-4
               text-sm font-medium min-w-[100px] px-4 py-2"
               onmouseover="this.style.backgroundColor='#d16a0f'"
               onmouseout="this.style.backgroundColor='#e97b11'"
            >
                Ver todas
                <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
            </a>
        </div>
    </div>
</div>

{{-- NOTA: El panel de categorías lateral se incluye en el componente header.blade.php --}}
{{-- para que esté disponible en todas las vistas --}}

<style>
    .scrollbar-hide {
        -ms-overflow-style: none;
        scrollbar-width: none;
    }
    .scrollbar-hide::-webkit-scrollbar {
        display: none;
    }
    
    {{-- Reducir tamaño de texto de categorías solo en móvil --}}
    @media (max-width: 640px) {
        .categoria-texto-movil {
            font-size: 0.7rem !important;
            line-height: 1.2;
        }
    }
</style>



