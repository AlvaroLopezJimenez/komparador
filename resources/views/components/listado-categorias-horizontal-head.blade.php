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

// categoriasBarra -> cb1
$cb1 = \App\Models\Categoria::whereNull('parent_id')
    ->orderBy('nombre')
    ->take(14)
    ->get(['id', 'slug', 'nombre']);
@endphp

<div class="kk-catbar">
    <div class="kk-catbar-inner">
        <button type="button" id="kkCatPrev" class="kk-cat-arrow" aria-label="Categorías anteriores">‹</button>
        <div id="kkCatScroll" class="kk-cat-scroll">
            @foreach($cb1 as $c1)
                <a href="{{ _f1(route('categoria.show', $c1->slug)) }}" class="kk-chip">{{ $c1->nombre }}</a>
            @endforeach
        </div>
        <button type="button" id="kkCatNext" class="kk-cat-arrow" aria-label="Siguientes categorías">›</button>
        <a href="{{ _f1(route('categorias.todas')) }}" class="kk-chip-all">Ver todas</a>
    </div>
</div>

{{-- NOTA: El panel de categorías lateral se incluye en el componente header.blade.php --}}
{{-- para que esté disponible en todas las vistas --}}

<style>
    .kk-catbar {
        border-bottom: 1px solid #e2e8f0;
        background: #fff;
    }
    .kk-catbar-inner {
        max-width: 1200px;
        margin: 0 auto;
        padding: .5rem 1rem;
        display: flex;
        align-items: center;
        gap: .5rem;
    }
    .kk-cat-scroll {
        flex: 1;
        display: flex;
        gap: .45rem;
        overflow-x: auto;
        scrollbar-width: none;
        -ms-overflow-style: none;
        padding: .12rem 0;
    }
    .kk-cat-scroll::-webkit-scrollbar { display: none; }
    .kk-chip {
        flex: 0 0 auto;
        border-radius: 999px;
        border: 1px solid transparent;
        background: #f1f5f9;
        color: #475569;
        font-size: .78rem;
        font-weight: 600;
        padding: .42rem .85rem;
        white-space: nowrap;
        transition: .2s;
    }
    .kk-chip:hover,
    .kk-chip-active {
        color: #d16a0f;
        background: #fef3e7;
        border-color: rgba(233, 123, 17, .24);
    }
    .kk-cat-arrow {
        flex: 0 0 auto;
        width: 34px;
        height: 34px;
        border-radius: 50%;
        border: 1px solid #e2e8f0;
        background: #fff;
        color: #e97b11;
        font-size: 1.1rem;
        line-height: 1;
    }
    .kk-cat-arrow:hover { background: #fef3e7; }
    .kk-chip-all {
        flex: 0 0 auto;
        border-radius: .62rem;
        background: #e97b11;
        color: #fff;
        font-size: .78rem;
        font-weight: 700;
        padding: .46rem .85rem;
        white-space: nowrap;
    }
    .kk-chip-all:hover { background: #d16a0f; }
    @media (max-width: 768px) {
        .kk-chip-all { display: none; }
    }
</style>

@push('scripts')
<script>
    const _kc1 = document.getElementById('kkCatScroll');
    document.getElementById('kkCatPrev')?.addEventListener('click', () => _kc1?.scrollBy({ left: -200, behavior: 'smooth' }));
    document.getElementById('kkCatNext')?.addEventListener('click', () => _kc1?.scrollBy({ left: 200, behavior: 'smooth' }));
</script>
@endpush



