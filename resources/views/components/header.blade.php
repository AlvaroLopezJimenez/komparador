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

// Detectar si estamos en una vista que tiene el componente listado-categorias-horizontal-head
// (index.blade.php o comparador/unidades.blade.php)
// Si estamos en home o en una ruta con múltiples categorías (patrón categoria/categoria/slug), ocultamos el hamburguesa
$rutaActual = request()->route();
$esHome = $rutaActual && $rutaActual->getName() === 'home';

// Detectar si es la vista comparador/unidades
// Forma 1: Verificar si existe la variable $producto (solo está en comparador/unidades)
$esComparadorUnidades = isset($producto) && is_object($producto);

// Forma 2: Si la forma 1 no funciona, verificar el patrón de la URL
if (!$esComparadorUnidades) {
    $path = trim(request()->path(), '/');
    $segmentos = array_filter(explode('/', $path));
    
    // La ruta de comparador/unidades tiene el patrón: categoria1/categoria2/.../slug (mínimo 2 segmentos)
    $rutasExcluidas = ['buscar', 'categoria', 'categorias', 'contacto', 'politicas', 'api'];
    $primerSegmento = $segmentos[0] ?? '';
    
    if (count($segmentos) >= 2 && !empty($primerSegmento) && !in_array($primerSegmento, $rutasExcluidas)) {
        // Verificar que no sea ninguna de las rutas conocidas
        $esComparadorUnidades = !request()->is('buscar*') && 
                                !request()->is('categoria*') && 
                                !request()->is('categorias*') &&
                                !request()->is('politicas*') &&
                                !request()->is('api*');
    }
}

$ocultarHamburguesa = $esHome || $esComparadorUnidades;
@endphp
{{-- HEADER --}}
    <header class="bg-white shadow px-6 py-4 max-w-7xl mx-auto w-full">
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 w-full">

{{-- LOGO + BUSCADOR (movil/tablet) + HAMBURGUESA --}}
<div class="flex items-center w-full gap-2 lg:w-auto flex-nowrap header-mobile-container">
    {{-- LOGO --}}
    <div class="flex items-center flex-shrink-0 header-logo">
        {{-- añadirCam() -> _f1() --}}
        <a href="{{ _f1(route('home')) }}" class="flex items-center">
        <img src="{{ asset('images/icono.webp') }}" alt="Logo móvil y tablet" class="h-8 w-auto block lg:hidden">
        <img src="{{ asset('images/logo.webp') }}" alt="Logo escritorio" class="h-12 w-auto hidden lg:block">
        </a>
    </div>

    {{-- BUSCADOR MÓVIL + TABLET --}}
    <div class="flex-1 lg:hidden header-search">
        <div class="relative w-full">
            {{-- añadirCam() -> _f1() --}}
            <form action="{{ _f1(route('buscar')) }}" method="GET" class="flex w-full" id="form-search-mobile">
                <input type="text"
                       name="q"
                       placeholder="Buscar productos..."
                       class="flex-1 px-2 py-2 border border-gray-300 rounded-l-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
                       id="sim1"
                       required>
                <button type="submit"
                        style="background-color: #5f8c21;"
                        class="px-3 py-2 text-white rounded-r-lg hover:bg-[#4d7a1a] transition-colors text-sm"
                        onmouseover="this.style.backgroundColor='#4d7a1a'"
                        onmouseout="this.style.backgroundColor='#5f8c21'">
                    Buscar
                </button>
            </form>
            {{-- SUGERENCIAS QUE MUESTRA EL BUSCADOR--}}
            {{-- sugerencias-mobile -> sgm1 --}}
            <div id="sgm1" class="max-h-72 overflow-y-auto scrollbar-thin scrollbar-thumb-gray-300 absolute top-full left-0 right-0 bg-white border border-gray-300 rounded-b-lg shadow-lg z-50 hidden">
                {{-- Las sugerencias se cargarán aquí dinámicamente --}}
            </div>
        </div>
    </div>

    {{-- BOTÓN HAMBURGUESA --}}
    {{-- btnMenu -> bm1 --}}
    @if(!$ocultarHamburguesa)
    <button id="bm1" class="ml-3 block lg:hidden flex-shrink-0 p-1 header-menu-btn">
        <svg class="w-6 h-6 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M4 6h16M4 12h16M4 18h16" />
        </svg>
    </button>
    @endif
</div>


        {{-- BUSCADOR ESCRITORIO --}}
        <div class="hidden lg:block flex-1 max-w-xl mx-auto">
            <div class="relative">
                {{-- añadirCam() -> _f1() --}}
            <form action="{{ _f1(route('buscar')) }}" method="GET" class="flex w-full" id="form-search-desktop">
                    <input type="text"
                           name="q"
                           placeholder="Buscar productos..."
                           class="flex-1 px-3 py-2 border border-gray-300 rounded-l-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-base"
                           id="si1"
                           required>
                    <button type="submit"
                            style="background-color: #5f8c21;"
                            class="px-4 py-2 text-white rounded-r-lg hover:bg-[#4d7a1a] transition-colors text-base"
                            onmouseover="this.style.backgroundColor='#4d7a1a'"
                            onmouseout="this.style.backgroundColor='#5f8c21'">
                        Buscar
                    </button>
                </form>

                {{-- SUGERENCIAS --}}
                {{-- sugerencias-desktop -> sgd1 --}}
                <div id="sgd1" class="max-h-72 overflow-y-auto scrollbar-thin scrollbar-thumb-gray-300 absolute top-full left-0 right-0 bg-white border border-gray-300 rounded-b-lg shadow-lg z-50 hidden">
                    {{-- Las sugerencias se cargarán aquí dinámicamente --}}
                </div>
            </div>
        </div>

        {{-- NAV ESCRITORIO --}}
        <nav class="hidden lg:flex space-x-2">
            {{-- añadirCam() -> _f1() --}}
            <a href="{{ _f1(route('home')) }}" class="px-4 py-2 rounded text-gray-700 font-medium hover:bg-[#ef76b6] hover:text-white hover:shadow-md">Inicio</a>
            <div class="relative group">
</div>
            <a href="{{ route('politicas.contacto') }}" class="px-4 py-2 rounded text-gray-700 font-medium hover:bg-[#ef76b6] hover:text-white hover:shadow-md">Contacto</a>
        </nav>
    </div>
</header>

{{-- INCLUIR EL PANEL DE CATEGORÍAS LATERAL (siempre disponible) --}}
<x-panel-categorias-lateral />

    {{-- NAV MÓVIL --}}
    {{-- navMobile -> nm1 --}}
    <nav id="nm1" class="hidden bg-white shadow-lg lg:hidden">
        <div class="px-6 py-4 space-y-2">
            {{-- añadirCam() -> _f1() --}}
            <a href="{{ _f1(route('home')) }}" class="block px-4 py-2 rounded text-gray-700 font-medium transition-all duration-200 hover:bg-[#ef76b6] hover:text-white">
                Inicio
            </a>
            <div>
</div>

            <a href="{{ route('politicas.contacto') }}" class="block px-4 py-2 rounded text-gray-700 font-medium transition-all duration-200 hover:bg-[#ef76b6] hover:text-white">
                Contacto
            </a>
        </div>
    </nav>

    <style>
    {{-- Estilos específicos para el header móvil --}}
    @media (max-width: 1024px) {
        .header-mobile-container {
            display: flex;
            align-items: center;
            width: 100%;
            gap: 0.5rem;
            flex-wrap: nowrap;
        }
        
        .header-logo {
            flex-shrink: 0;
            min-width: auto;
        }
        
        .header-search {
            flex: 1;
            min-width: 0;
        }
        
        .header-menu-btn {
            flex-shrink: 0;
            margin-left: 0.75rem;
            padding: 0.25rem;
        }
        
        {{-- Asegurar que el logo no ocupe demasiado espacio --}}
        .header-logo img {
            max-width: 40px;
            height: auto;
        }
    }
    </style>

    @push('scripts')
    <script>
    {{-- escapeHtml() -> _e1() - Función para escapar HTML (protección adicional contra XSS) --}}
    function _e1(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return String(text).replace(/[&<>"']/g, m => map[m]);
    }
    
    {{-- añadirCamJS() -> _f2() - Función para añadir parámetro cam a URLs en JavaScript de forma segura --}}
    function _f2(url) {
        const urlParams = new URLSearchParams(window.location.search);
        const cam = urlParams.get('cam');
        if (cam) {
            {{-- Validar que solo contenga caracteres alfanuméricos, guiones y guiones bajos --}}
            if (!/^[a-zA-Z0-9\-_]+$/.test(cam)) {
                return url; {{-- Si no es válido, no añadir --}}
            }
            const separator = url.includes('?') ? '&' : '?';
            return url + separator + 'cam=' + encodeURIComponent(cam);
        }
        return url;
    }

    {{-- Validación de formularios de búsqueda (prevenir envío si está vacío) --}}
    {{-- formSearchMobile -> fsm1, formSearchDesktop -> fsd1 --}}
    const _fsm1 = document.getElementById('form-search-mobile');
    const _fsd1 = document.getElementById('form-search-desktop');

    if (_fsm1) {
        _fsm1.addEventListener('submit', function(e) {
            const _input = this.querySelector('input[name="q"]');
            if (!_input || !_input.value.trim()) {
                e.preventDefault();
                return false;
            }
        });
    }

    if (_fsd1) {
        _fsd1.addEventListener('submit', function(e) {
            const _input = this.querySelector('input[name="q"]');
            if (!_input || !_input.value.trim()) {
                e.preventDefault();
                return false;
            }
        });
    }

    {{-- Menú móvil / Panel de categorías --}}
    {{-- btnMenu -> bm1, navMobile -> nm1 --}}
    const _bm1 = document.getElementById('bm1');
    if (_bm1) {
        _bm1.addEventListener('click', function() {
            {{-- Si el panel de categorías está disponible, abrirlo --}}
            {{-- Si no, mostrar el menú móvil tradicional --}}
            if (typeof window._ap1 === 'function') {
                window._ap1();
            } else {
                const _n1 = document.getElementById('nm1');
                if (_n1) {
                    _n1.classList.toggle('hidden');
                }
            }
        });
    }

    {{-- Submenú Tallas (escritorio) --}}
    {{-- btnTallasDesktop -> btd1, submenuTallasDesktop -> std1 --}}
    document.getElementById('btd1')?.addEventListener('click', function (e) {
        e.preventDefault();
        const _sm1 = document.getElementById('std1');
        _sm1.classList.toggle('hidden');
    });

    {{-- Submenú Tallas (móvil) --}}
    {{-- btnTallasMobile -> btm1, submenuTallasMobile -> stm1 --}}
    document.getElementById('btm1')?.addEventListener('click', function (e) {
        e.preventDefault();
        const _sm2 = document.getElementById('stm1');
        _sm2.classList.toggle('hidden');
    });

    {{-- Buscador con sugerencias (funciona con ambos inputs) --}}
    {{-- inputsBusqueda -> _i1, searchInput -> si1, searchInputMobile -> sim1 --}}
    {{-- sugerencias-mobile -> sgm1, sugerencias-desktop -> sgd1 --}}
    const _i1 = [
        document.getElementById('si1'),
        document.getElementById('sim1')
    ].filter(Boolean);

    _i1.forEach(input => {
        const _c1 = input.closest('div.relative') || input.closest('div.lg\\:hidden');
        const _s1 = input.id === 'sim1'
    ? _c1?.querySelector('#sgm1')
    : _c1?.querySelector('#sgd1');

        if (!_s1) return;

        {{-- timeoutId -> _t1, query -> _q1, data -> _d1, html -> _h1, item -> _it --}}
        let _t1;

        input.addEventListener('input', function() {
            clearTimeout(_t1);
            const _q1 = this.value.trim();

            if (_q1.length < 2) {
                _s1.classList.add('hidden');
                return;
            }

            _t1 = setTimeout(() => {
                fetch(`/api/buscar-productos?q=${encodeURIComponent(_q1)}`)
                    .then(response => response.json())
                    .then(_d1 => {
    if (_d1.length > 0) {
        {{-- nombreEscapado -> _n1, imagenEscapada -> _img1, urlEscapada -> _u1 --}}
        let _h1 = _d1.slice(0, 6).map((_it, index) => {
            if (_it.tipo === 'categoria') {
                const _n1 = _e1(_it.nombre || '');
                const _img1 = _e1(_it.imagen || 'placeholder.jpg');
                const _u1 = _e1(_f2(_it.url || '#'));
                return `
                    <a href="${_u1}" 
                       class="block px-4 py-3 hover:bg-gray-100 border-b border-gray-200 last:border-b-0">
                        <div class="flex items-center space-x-3">
                            <img src="/images/${_img1}" 
                                 alt="${_n1}" 
                                 class="w-12 h-12 object-cover rounded">
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 truncate">${_n1}</p>
                                <p class="text-xs font-medium" style="color: #e97b11;">Categoría</p>
                            </div>
                        </div>
                    </a>
                `;
            } else {
                {{-- precioEscapado -> _p1, unidadMedida -> _um, unidadHtml -> _uh --}}
                const _n2 = _e1(_it.nombre || '');
                const _img2 = _e1(_it.imagen_pequena || 'placeholder.jpg');
                const _u2 = _e1(_f2(_it.url || '#'));
                const _p1 = _e1(_it.precio || '0');
                const _um = _it.unidadDeMedida || '';
                let _uh = '';
                if (_um === 'unidad') {
                    _uh = '<span class="text-xs text-gray-500">/Und.</span>';
                } else if (_um === 'kilos') {
                    _uh = '<span class="text-xs text-gray-500">/Kg.</span>';
                } else if (_um === 'litros') {
                    _uh = '<span class="text-xs text-gray-500">/L.</span>';
                } else if (_um === 'unidadMilesima') {
                    _uh = '<span class="text-xs text-gray-500">/Und.</span>';
                } else if (_um === '800gramos') {
                    _uh = '<span class="text-xs text-gray-500">/800gr.</span>';
                } else if (_um === '100ml') {
                    _uh = '<span class="text-xs text-gray-500">/100ml.</span>';
                }
                return `
                    <a href="${_u2}" 
                       class="block px-4 py-3 hover:bg-gray-100 border-b border-gray-200 last:border-b-0">
                        <div class="flex items-center space-x-3">
                            <img src="/images/${_img2}" 
                                 alt="${_n2}" 
                                 class="w-12 h-12 object-cover rounded">
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 truncate">${_n2}</p>
                                <p class="text-lg font-bold" style="color: #73b112;">
                                    ${_p1}€${_uh}
                                </p>
                            </div>
                        </div>
                    </a>
                `;
            }
        }).join('');

        if (_d1.length === 7) {
            {{-- buscarUrlEscapada -> _bu1 --}}
            const _bu1 = _e1(_f2('/buscar?q=' + encodeURIComponent(input.value)));
            _h1 += `
                <div class="px-4">
                    <button style="background-color: #5f8c21;" class="w-full text-white font-semibold py-2 rounded-md transition"
                            onmouseover="this.style.backgroundColor='#4d7a1a'"
                            onmouseout="this.style.backgroundColor='#5f8c21'"
                            onclick="window.location.href='${_bu1}'">
                        Mostrar más productos
                    </button>
                </div>
            `;
        }

        _s1.innerHTML = _h1;
        _s1.classList.remove('hidden');
    } else {
        _s1.classList.add('hidden');
    }
})

                    .catch(_err => {
                        console.error('Error:', _err);
                        _s1.classList.add('hidden');
                    });
            }, 300);
        });

        {{-- Ocultar sugerencias al hacer clic fuera --}}
        document.addEventListener('click', function(e) {
            if (!input.contains(e.target) && !_s1.contains(e.target)) {
                _s1.classList.add('hidden');
            }
        });
    });
</script>
@endpush