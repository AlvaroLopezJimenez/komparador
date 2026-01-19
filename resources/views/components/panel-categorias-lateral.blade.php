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

// categoriasPadre -> cp1
$cp1 = \App\Models\Categoria::whereNull('parent_id')
    ->with(['subcategorias' => function($query) {
        $query->orderBy('nombre');
    }])
    ->orderBy('nombre')
    ->get();
@endphp

{{-- OVERLAY OSCURO --}}
{{-- overlayPanel -> op1 --}}
<div id="op1" class="fixed inset-0 bg-black bg-opacity-50 z-[9998] hidden transition-opacity duration-300"></div>

{{-- PANEL LATERAL DE CATEGORÍAS --}}
{{-- panelCategorias -> pc1 --}}
<div id="pc1" class="fixed top-0 left-0 bg-white shadow-2xl z-[9999] transform -translate-x-full transition-transform duration-300 overflow-hidden flex flex-col hidden" style="position: fixed; top: 0; left: 0; height: 100vh; width: 530px;">
    {{-- HEADER DEL PANEL --}}
    {{-- headerPanel -> hp1 --}}
    <div id="hp1" class="flex items-center justify-between px-4 py-4 border-b border-gray-200 bg-white flex-shrink-0">
        {{-- contenedorLogoNombre -> cln1 --}}
        <div id="cln1" class="flex items-center gap-3 flex-1 min-w-0">
            {{-- logoPanel -> lp1 --}}
            <img id="lp1" src="{{ asset('images/logo.webp') }}" alt="Logo" class="h-8 w-auto flex-shrink-0">
            {{-- nombreCategoriaPanel -> ncp1 --}}
            <div id="ncp1" class="hidden items-center gap-2 flex-1 min-w-0">
                {{-- btnVolverPanel -> bvp1 --}}
                <button type="button" id="bvp1" class="flex-shrink-0 p-1 hover:bg-gray-100 rounded transition-colors">
                    <svg class="w-5 h-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </button>
                {{-- textoNombreCategoria -> tnc1 --}}
                <span id="tnc1" class="text-gray-900 font-medium truncate"></span>
            </div>
        </div>
        {{-- btnCerrarPanel -> bcp1 --}}
        <button type="button" id="bcp1" class="flex-shrink-0 p-1 hover:bg-gray-100 rounded transition-colors ml-2">
            <svg class="w-6 h-6 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>

    {{-- CONTENEDOR DE NAVEGACIÓN --}}
    {{-- contenedorNavegacion -> cn1 --}}
    <div id="cn1" class="flex-1 overflow-hidden relative" style="min-height: 0;">
        {{-- LISTA DE CATEGORÍAS PADRE --}}
        {{-- listaCategoriasPadre -> lcp1 --}}
        <div id="lcp1" class="absolute inset-0 overflow-y-auto" style="height: 100%;">
            <div class="px-2 py-2">
                @foreach($cp1 as $categoria)
                    {{-- categoriaItem -> ci1, categoriaNombre -> cn2, categoriaSlug -> cs1, categoriaSubcategorias -> csc1 --}}
                    @php
                        $ci1 = $categoria;
                        $cn2 = $categoria->nombre;
                        $cs1 = $categoria->slug;
                        $csc1 = $categoria->subcategorias;
                    @endphp
                    <div class="categoria-item" data-categoria-id="{{ $categoria->id }}" data-categoria-slug="{{ $cs1 }}" data-categoria-nombre="{{ $cn2 }}" data-tiene-subcategorias="{{ $csc1->count() > 0 ? 'true' : 'false' }}">
                        <a href="{{ _f1(route('categoria.show', $cs1)) }}" class="flex items-center justify-between px-4 py-3 hover:bg-gray-100 rounded-lg transition-colors group">
                            <span class="text-gray-900 font-medium">{{ $cn2 }}</span>
                            @if($csc1->count() > 0)
                                <svg class="w-5 h-5 text-gray-400 group-hover:text-gray-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            @endif
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

<style>
    {{-- Estilos para el panel de categorías --}}
    {{-- panelCategorias -> pc1 --}}
    #pc1 {
        max-width: 530px;
        position: fixed !important;
        top: 0 !important;
        left: 0 !important;
        height: 100vh !important;
        width: 530px !important;
    }
    
    {{-- Panel más estrecho en móvil --}}
    @media (max-width: 768px) {
        #pc1 {
            max-width: 320px !important;
            width: 320px !important;
        }
    }

    {{-- nivelNavegacion -> nn1 --}}
    .nn1 {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: white;
        transform: translateX(100%);
        transition: transform 0.3s ease-in-out;
        overflow-y: auto;
    }

    .nn1.activo {
        transform: translateX(0);
    }

    .nn1.anterior {
        transform: translateX(-100%);
    }
</style>

@push('scripts')
<script>
{{-- Panel de Categorías --}}
{{-- panelCategorias -> pc1, overlayPanel -> op1, btnCategoriasBarra -> bcb1 --}}
{{-- btnCerrarPanel -> bcp1, contenedorNavegacion -> cn1, listaCategoriasPadre -> lcp1 --}}
{{-- headerPanel -> hp1, logoPanel -> lp1, nombreCategoriaPanel -> ncp1, btnVolverPanel -> bvp1 --}}
{{-- textoNombreCategoria -> tnc1, contenedorLogoNombre -> cln1 --}}
const _pc1 = document.getElementById('pc1');
const _op1 = document.getElementById('op1');
const _bcb1 = document.getElementById('bcb1');
const _bcp1 = document.getElementById('bcp1');
const _cn1 = document.getElementById('cn1');
const _lcp1 = document.getElementById('lcp1');
const _lp1 = document.getElementById('lp1');
const _ncp1 = document.getElementById('ncp1');
const _bvp1 = document.getElementById('bvp1');
const _tnc1 = document.getElementById('tnc1');
const _cln1 = document.getElementById('cln1');

{{-- historialNavegacion -> hn1 --}}
let _hn1 = [];

{{-- escapeHtml() -> _e1() --}}
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

{{-- añadirCamJS() -> _f2() --}}
function _f2(url) {
    const urlParams = new URLSearchParams(window.location.search);
    const cam = urlParams.get('cam');
    if (cam) {
        if (!/^[a-zA-Z0-9\-_]+$/.test(cam)) {
            return url;
        }
        const separator = url.includes('?') ? '&' : '?';
        return url + separator + 'cam=' + encodeURIComponent(cam);
    }
    return url;
}

{{-- abrirPanel -> _ap1() - Función global para abrir el panel desde cualquier lugar --}}
window._ap1 = function() {
    if (!_pc1 || !_op1) return;
    _pc1.classList.remove('hidden');
    _pc1.classList.remove('-translate-x-full');
    _op1.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
    _hn1 = [];
    _rh1();
}

{{-- cerrarPanel -> _cp1() --}}
function _cp1() {
    if (!_pc1 || !_op1) return;
    _pc1.classList.add('-translate-x-full');
    setTimeout(() => {
        _pc1.classList.add('hidden');
    }, 300);
    _op1.classList.add('hidden');
    document.body.style.overflow = '';
    _hn1 = [];
    _rh1();
    {{-- eliminarTodosNiveles -> etn1 --}}
    const _etn1 = _cn1?.querySelectorAll('.nn1');
    if (_etn1) {
        _etn1.forEach(nivel => nivel.remove());
    }
}

{{-- restablecerHeader -> _rh1() --}}
function _rh1() {
    if (!_lp1 || !_ncp1) return;
    _lp1.classList.remove('hidden');
    _ncp1.classList.add('hidden');
}

{{-- mostrarCategoria -> _mc1() --}}
function _mc1(categoriaId, categoriaNombre, categoriaSlug, subcategorias) {
    if (!_cn1) return;
    {{-- nivelActual -> na1 --}}
    const _na1 = document.createElement('div');
    _na1.className = 'nn1 activo';
    _na1.setAttribute('data-categoria-id', categoriaId);
    _na1.setAttribute('data-categoria-nombre', categoriaNombre);

    {{-- htmlNivel -> hn2 --}}
    let _hn2 = '<div class="px-2 py-2">';
    
    if (subcategorias && subcategorias.length > 0) {
        subcategorias.forEach(sub => {
            {{-- subNombre -> sn1, subSlug -> ss1, subId -> si1, subSubcategorias -> ssc1 --}}
            const _sn1 = _e1(sub.nombre || '');
            const _ss1 = _e1(sub.slug || '');
            const _si1 = sub.id || '';
            const _ssc1 = sub.subcategorias || [];
            const _tieneSub = _ssc1.length > 0;
            
            {{-- urlSubcategoria -> us1 --}}
            const _us1 = _e1(_f2('/categoria/' + _ss1));
            
            _hn2 += `
                <div class="categoria-item" data-categoria-id="${_si1}" data-categoria-slug="${_ss1}" data-categoria-nombre="${_sn1}" data-tiene-subcategorias="${_tieneSub}">
                    <a href="${_us1}" class="flex items-center justify-between px-4 py-3 hover:bg-gray-100 rounded-lg transition-colors group">
                        <span class="text-gray-900 font-medium">${_sn1}</span>
                        ${_tieneSub ? '<svg class="w-5 h-5 text-gray-400 group-hover:text-gray-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>' : ''}
                    </a>
                </div>
            `;
        });
    } else {
        _hn2 += '<div class="px-4 py-3 text-gray-500 text-sm">No hay subcategorías</div>';
    }
    
    _hn2 += '</div>';
    _na1.innerHTML = _hn2;

    {{-- ocultarNivelesAnteriores -> ona1 --}}
    const _ona1 = _cn1.querySelectorAll('.nn1.activo');
    _ona1.forEach(nivel => {
        nivel.classList.remove('activo');
        nivel.classList.add('anterior');
    });

    _cn1.appendChild(_na1);

    {{-- actualizarHeader -> ah1 --}}
    if (_lp1 && _ncp1 && _tnc1) {
        _lp1.classList.add('hidden');
        _ncp1.classList.remove('hidden');
        _tnc1.textContent = categoriaNombre;
    }

    {{-- guardarEnHistorial -> geh1 --}}
    _hn1.push({
        id: categoriaId,
        nombre: categoriaNombre,
        slug: categoriaSlug
    });
}

{{-- volverAtras -> va1() --}}
function _va1() {
    if (_hn1.length === 0) {
        _cp1();
        return;
    }

    {{-- eliminarUltimoNivel -> eun1 --}}
    if (!_cn1) return;
    const _eun1 = _cn1.querySelector('.nn1.activo');
    if (_eun1) {
        _eun1.remove();
    }

    _hn1.pop();

    {{-- activarNivelAnterior -> ana1 --}}
    const _ana1 = _cn1.querySelectorAll('.nn1.anterior');
    if (_ana1.length > 0) {
        const _ultimoAnterior = _ana1[_ana1.length - 1];
        _ultimoAnterior.classList.remove('anterior');
        _ultimoAnterior.classList.add('activo');
        
        {{-- categoriaAnterior -> ca1 --}}
        const _ca1 = _hn1[_hn1.length - 1];
        if (_ca1 && _tnc1) {
            _tnc1.textContent = _ca1.nombre;
        } else {
            _rh1();
        }
    } else {
        _rh1();
    }
}

{{-- Event Listeners --}}
_bcb1?.addEventListener('click', function(e) {
    e.preventDefault();
    window._ap1();
});

_bcp1?.addEventListener('click', function(e) {
    e.preventDefault();
    _cp1();
});

_op1?.addEventListener('click', function(e) {
    if (e.target === _op1) {
        _cp1();
    }
});

_bvp1?.addEventListener('click', function(e) {
    e.preventDefault();
    _va1();
});

{{-- Manejar clic en categorías con subcategorías --}}
function _mcc1(e) {
    {{-- itemCategoria -> ic1 --}}
    const _ic1 = e.target.closest('.categoria-item');
    if (!_ic1) return;

    {{-- tieneSubcategorias -> ts1 --}}
    const _ts1 = _ic1.getAttribute('data-tiene-subcategorias') === 'true';
    if (!_ts1) return;

    e.preventDefault();
    e.stopPropagation();

    {{-- categoriaId -> cid1, categoriaNombre -> cnom1, categoriaSlug -> cslug1 --}}
    const _cid1 = _ic1.getAttribute('data-categoria-id');
    const _cnom1 = _ic1.getAttribute('data-categoria-nombre');
    const _cslug1 = _ic1.getAttribute('data-categoria-slug');

    {{-- obtenerSubcategorias -> osc1 --}}
    fetch(`/api/categorias/${_cid1}/subcategorias`)
        .then(response => response.json())
        .then(data => {
            _mc1(_cid1, _cnom1, _cslug1, data);
        })
        .catch(error => {
            console.error('Error al obtener subcategorías:', error);
        });
}

{{-- Aplicar a contenedor de navegación y lista padre --}}
_cn1?.addEventListener('click', _mcc1);
_lcp1?.addEventListener('click', _mcc1);
</script>
@endpush

