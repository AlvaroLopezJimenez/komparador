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
<header class="kk-header-wrap">
    <div class="kk-header-inner">
        <div class="kk-logo-zone">
            <a href="{{ _f1(route('home')) }}" class="kk-logo-link">
                <img src="{{ asset('images/logo.webp') }}" alt="Komparador" class="kk-logo-desktop">
                <img src="{{ asset('images/icono.webp') }}" alt="Komparador" class="kk-logo-mobile">
            </a>
        </div>

        <div class="kk-search-zone">
            <div class="relative hidden lg:block">
                <form action="{{ _f1(route('buscar')) }}" method="GET" class="kk-search" id="form-search-desktop">
                    <input type="text" name="q" placeholder="Busca un producto, marca, modelo" id="si1" required>
                    <button type="submit">Buscar</button>
                </form>
                <div id="sgd1" class="kk-suggest hidden" role="listbox" aria-label="Sugerencias de búsqueda"></div>
            </div>
            <div class="relative lg:hidden">
                <form action="{{ _f1(route('buscar')) }}" method="GET" class="kk-search" id="form-search-mobile">
                    <input type="text" name="q" placeholder="Busca un producto, marca, modelo" id="sim1" required>
                    <button type="submit">Buscar</button>
                </form>
                <div id="sgm1" class="kk-suggest hidden" role="listbox" aria-label="Sugerencias de búsqueda"></div>
            </div>
        </div>

        <nav class="kk-nav-desktop">
            <a href="{{ _f1(route('home')) }}">Inicio</a>
            <a href="{{ _f1(route('buscar', ['q' => 'precios hot'])) }}">🔥 En mínimos</a>
            <a href="{{ _f1(route('buscar', ['q' => 'más vendidos'])) }}" class="kk-nav-vendidos"><span class="kk-nav-plus">+</span> Vendidos</a>
        </nav>

        <button type="button" id="bm1" class="kk-menu-btn" aria-label="Abrir categorías" aria-expanded="false" aria-controls="pc1">
            <svg class="kk-menu-btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        </button>
    </div>
</header>

{{-- INCLUIR EL PANEL DE CATEGORÍAS LATERAL (siempre disponible) --}}
<x-panel-categorias-lateral />

    {{-- NAV MÓVIL --}}
    {{-- navMobile -> nm1 --}}
    <nav id="nm1" class="hidden kk-mobile-nav lg:hidden">
        <div class="kk-mobile-nav-inner">
            <a href="{{ _f1(route('home')) }}">Inicio</a>
            <a href="{{ _f1(route('buscar', ['q' => 'precios hot'])) }}">🔥 En mínimos</a>
            <a href="{{ _f1(route('buscar', ['q' => 'más vendidos'])) }}" class="kk-mobile-nav-vendidos">
                <span class="kk-nav-plus">+</span>
                <span>Vendidos</span>
            </a>
        </div>
    </nav>

    <style>
    .kk-header-wrap {
        position: sticky;
        top: 0;
        z-index: 110;
        border-bottom: 1px solid #e2e8f0;
        background: rgba(255, 255, 255, 0.92);
        backdrop-filter: blur(12px);
    }
    .kk-header-inner {
        max-width: 1200px;
        margin: 0 auto;
        padding: .75rem 1rem;
        display: flex;
        flex-wrap: nowrap;
        align-items: center;
        gap: .5rem;
    }
    .kk-logo-zone { flex-shrink: 0; }
    .kk-search-zone {
        flex: 1 1 0;
        min-width: 0;
    }
    .kk-logo-link { display: inline-flex; align-items: center; }
    .kk-logo-desktop { height: 34px; width: auto; display: none; }
    .kk-logo-mobile { height: 30px; width: auto; display: block; }
    .kk-search {
        display: flex;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        overflow: hidden;
        background: #f1f5f9;
    }
    .kk-search:focus-within {
        border-color: #e97b11;
        box-shadow: 0 0 0 3px rgba(233, 123, 17, 0.15);
        background: #fff;
    }
    .kk-search input {
        flex: 1;
        border: none;
        background: transparent;
        outline: none;
        padding: .6rem .8rem;
        font-size: .88rem;
    }
    .kk-search button {
        border: none;
        background: #e97b11;
        color: #fff;
        font-weight: 700;
        font-size: .82rem;
        padding: 0 .9rem;
    }
    .kk-search button:hover { background: #d16a0f; }
    .kk-suggest {
        max-height: 18rem;
        overflow-y: auto;
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background: #fff;
        border: 1px solid #e2e8f0;
        border-top: none;
        border-radius: 0 0 12px 12px;
        box-shadow: 0 12px 30px rgba(15, 23, 42, 0.12);
        z-index: 150;
    }
    .kk-suggest-item {
        cursor: pointer;
    }
    .kk-suggest-item.kk-suggest-active,
    .kk-suggest-item.kk-suggest-active:hover {
        background: #fef3e7;
    }
    @keyframes kk-badge-modelos-shift {
        0%, 100% { background-position: 0% 50%; }
        50% { background-position: 100% 50%; }
    }
    .kk-badge-modelos {
        display: inline-flex;
        align-items: center;
        font-weight: 700;
        line-height: 1.2;
        color: #1f2937;
        border-radius: 0.4rem;
        background: linear-gradient(90deg, #fde047, #fbbf24, #f97316, #fb923c, #fde047);
        background-size: 220% 220%;
        animation: kk-badge-modelos-shift 2.4s ease infinite;
        box-shadow: 0 2px 6px rgba(249, 115, 22, 0.35);
        white-space: nowrap;
    }
    .kk-badge-modelos--suggest {
        font-size: 13px;
        padding: 0.12rem 0.42rem;
        margin-left: 0.5rem;
        vertical-align: middle;
    }
    .kk-badge-modelos--card {
        position: absolute;
        top: -4px;
        right: -10px;
        z-index: 10;
        font-size: 13px;
        padding: 0.22rem 0.52rem;
    }
    button.kk-suggest-item.kk-suggest-active,
    button.kk-suggest-item.kk-suggest-active:hover {
        background-color: #4d7a1a;
        outline: 2px solid #e97b11;
        outline-offset: -2px;
    }
    .kk-nav-desktop { display: none; align-items: center; gap: .25rem; }
    .kk-nav-desktop a {
        font-size: .83rem;
        font-weight: 600;
        color: #475569;
        border-radius: .62rem;
        padding: .45rem .7rem;
        white-space: nowrap;
    }
    .kk-nav-desktop a:hover { background: #fef3e7; color: #d16a0f; }
    .kk-nav-plus {
        color: #73b112;
        font-weight: 800;
    }
    .kk-nav-desktop a:hover .kk-nav-plus,
    .kk-mobile-nav-inner a:hover .kk-nav-plus {
        color: #73b112;
    }
    .kk-nav-vendidos .kk-nav-plus { margin-right: .1rem; }
    .kk-menu-btn {
        flex-shrink: 0;
        margin-left: .25rem;
        border: 1px solid #e2e8f0;
        border-radius: .65rem;
        padding: .45rem;
        color: #334155;
        background: #fff;
        line-height: 0;
    }
    .kk-menu-btn-icon { width: 1.5rem; height: 1.5rem; display: block; }
    .kk-menu-btn:hover { background: #f8fafc; }
    .kk-mobile-nav {
        border-bottom: 1px solid #e2e8f0;
        background: #fff;
        box-shadow: 0 10px 15px -3px rgba(15, 23, 42, 0.08), 0 4px 6px -4px rgba(15, 23, 42, 0.06);
    }
    .kk-mobile-nav-vendidos {
        display: flex;
        align-items: center;
        gap: .5rem;
    }
    .kk-mobile-nav-vendidos .kk-nav-plus {
        font-size: 1.15rem;
        font-weight: 800;
        line-height: 1;
        flex-shrink: 0;
    }
    .kk-mobile-nav-inner {
        max-width: 1200px;
        margin: 0 auto;
        padding: .7rem 1rem .9rem;
        display: grid;
        gap: .45rem;
    }
    .kk-mobile-nav-inner a {
        border-radius: .55rem;
        padding: .5rem .65rem;
        font-size: .86rem;
        font-weight: 600;
        color: #334155;
    }
    .kk-mobile-nav-inner a:hover { background: #f8fafc; color: #d16a0f; }
    @media (min-width: 1024px) {
        .kk-logo-desktop { display: block; }
        .kk-logo-mobile { display: none; }
        .kk-nav-desktop { display: inline-flex; }
        .kk-header-inner {
            display: grid;
            grid-template-columns: auto 1fr auto auto;
            gap: .8rem;
            padding: .85rem 1rem;
        }
        .kk-logo-zone { flex-shrink: unset; }
        .kk-search-zone {
            flex: unset;
            min-width: unset;
        }
        .kk-menu-btn { margin-left: 0; }
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
            {{-- Si el panel de categorías está disponible, abrirlo (mismo comportamiento que la copia) --}}
            {{-- Si no, desplegar el nav móvil #nm1 --}}
            if (typeof window._pclAp1 === 'function') {
                window._pclAp1();
            } else {
                const _n1 = document.getElementById('nm1');
                if (_n1) {
                    _n1.classList.toggle('hidden');
                    const _open = !_n1.classList.contains('hidden');
                    _bm1.setAttribute('aria-expanded', _open ? 'true' : 'false');
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

        {{-- timeoutId -> _t1, activeIdx -> _a1 --}}
        let _t1;
        let _a1 = -1;

        function _g1() {
            return Array.from(_s1.querySelectorAll('.kk-suggest-item'));
        }

        function _r1() {
            _a1 = -1;
            _u1();
        }

        function _u1() {
            const _items = _g1();
            _items.forEach(function(el, i) {
                el.classList.toggle('kk-suggest-active', i === _a1);
                el.setAttribute('aria-selected', i === _a1 ? 'true' : 'false');
            });
            if (_a1 >= 0 && _items[_a1]) {
                _items[_a1].scrollIntoView({ block: 'nearest' });
            }
        }

        function _n1(delta) {
            const _items = _g1();
            if (!_items.length) return;
            if (_a1 === -1) {
                _a1 = delta > 0 ? 0 : _items.length - 1;
            } else {
                _a1 = (_a1 + delta + _items.length) % _items.length;
            }
            _u1();
        }

        function _go1() {
            const _items = _g1();
            if (_a1 < 0 || !_items[_a1]) return false;
            const _url = _items[_a1].dataset.url;
            if (_url) {
                window.location.href = _url;
                return true;
            }
            return false;
        }

        input.addEventListener('input', function() {
            clearTimeout(_t1);
            _r1();
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
                            let _h1 = _d1.slice(0, 6).map(_it => {
                                if (_it.tipo === 'categoria') {
                                    const _nc = _e1(_it.nombre || '');
                                    const _img1 = _e1(_it.imagen || 'placeholder.jpg');
                                    const _uc = _e1(_f2(_it.url || '#'));
                                    return `
                                        <a href="${_uc}"
                                           data-url="${_uc}"
                                           role="option"
                                           class="kk-suggest-item block px-4 py-3 hover:bg-gray-100 border-b border-gray-200 last:border-b-0">
                                            <div class="flex items-center space-x-3">
                                                <img src="/images/${_img1}"
                                                     alt="${_nc}"
                                                     class="w-12 h-12 object-cover rounded">
                                                <div class="flex-1 min-w-0">
                                                    <p class="text-sm font-medium text-gray-900 truncate">${_nc}</p>
                                                    <p class="text-xs font-medium" style="color: #e97b11;">Categoría</p>
                                                </div>
                                            </div>
                                        </a>
                                    `;
                                }

                                const _n2 = _e1(_it.nombre || '');
                                const _img2 = _e1(_it.imagen_pequena || 'placeholder.jpg');
                                const _u2 = _e1(_f2(_it.url || '#'));
                                const _p1 = _e1(_it.precio || '0');
                                const _um = _it.unidadDeMedida || '';
                                const _numModelos = parseInt(_it.num_modelos, 10) || 0;
                                const _badgeModelos = _numModelos > 1
                                    ? `<span class="kk-badge-modelos kk-badge-modelos--suggest">+${_numModelos} modelos</span>`
                                    : '';
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
                                const _p1Num = parseFloat(_p1.replace(/\./g, '').replace(',', '.'));
                                const precioHtml = _p1Num > 0
                                    ? `<p class="text-lg font-bold flex items-center gap-3 flex-wrap" style="color: #73b112;">${_p1}€${_uh}${_badgeModelos}</p>`
                                    : `<p class="text-sm font-semibold text-gray-500 flex items-center gap-3 flex-wrap">Sin Ofertas Disponibles${_badgeModelos}</p>`;
                                return `
                                    <a href="${_u2}"
                                       data-url="${_u2}"
                                       role="option"
                                       class="kk-suggest-item block px-4 py-3 hover:bg-gray-100 border-b border-gray-200 last:border-b-0">
                                        <div class="flex items-center space-x-3">
                                            <div class="relative shrink-0">
                                                <img src="/images/${_img2}"
                                                     alt="${_n2}"
                                                     class="w-12 h-12 object-cover rounded">
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <p class="text-sm font-medium text-gray-900 truncate">${_n2}</p>
                                                ${precioHtml}
                                            </div>
                                        </div>
                                    </a>
                                `;
                            }).join('');

                            if (_d1.length === 7) {
                                const _bu1 = _e1(_f2('/buscar?q=' + encodeURIComponent(input.value)));
                                _h1 += `
                                    <div class="px-4 py-2 border-t border-gray-200">
                                        <button type="button"
                                                data-url="${_bu1}"
                                                role="option"
                                                class="kk-suggest-item w-full text-white font-semibold py-2 rounded-md transition"
                                                style="background-color: #5f8c21;"
                                                onmouseover="this.style.backgroundColor='#4d7a1a'"
                                                onmouseout="this.style.backgroundColor='#5f8c21'">
                                            Mostrar más productos
                                        </button>
                                    </div>
                                `;
                            }

                            _s1.innerHTML = _h1;
                            _s1.classList.remove('hidden');
                            _r1();
                        } else {
                            _s1.classList.add('hidden');
                            _r1();
                        }
                    })
                    .catch(_err => {
                        console.error('[Komparador] Error API buscar-productos:', _err);
                        _s1.classList.add('hidden');
                        _r1();
                    });
            }, 300);
        });

        input.addEventListener('keydown', function(e) {
            if (_s1.classList.contains('hidden') || !_g1().length) {
                return;
            }

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                _n1(1);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                _n1(-1);
            } else if (e.key === 'Enter') {
                if (_go1()) {
                    e.preventDefault();
                }
            } else if (e.key === 'Escape') {
                _s1.classList.add('hidden');
                _r1();
            }
        });

        _s1.addEventListener('mouseover', function(e) {
            const _item = e.target.closest('.kk-suggest-item');
            if (!_item) return;
            const _items = _g1();
            const _idx = _items.indexOf(_item);
            if (_idx >= 0) {
                _a1 = _idx;
                _u1();
            }
        });

        {{-- Ocultar sugerencias al hacer clic fuera --}}
        document.addEventListener('click', function(e) {
            if (!input.contains(e.target) && !_s1.contains(e.target)) {
                _s1.classList.add('hidden');
                _r1();
            }
        });
    });
</script>
@endpush