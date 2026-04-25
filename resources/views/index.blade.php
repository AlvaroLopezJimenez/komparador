<!DOCTYPE html>
<html lang="es">
<head>
    @php
    // $canonicalUrl -> $u1
    // Tomar la URL actual
    $u1 = url()->current();

    // Reemplazar el host por el que queremos mostrar
    $u = parse_url($u1);
    $u['host'] = 'komparador.com';

    // Reconstruir URL
    $u1 =
        ($u['scheme'] ?? 'https') . '://' .
        $u['host'] .
        ($u['path'] ?? '') .
        (isset($u['query']) ? '?' . $u['query'] : '');
@endphp
    <meta charset="UTF-8">
    
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Compara precios entre todas las tiendas y llévatelo al mejor precio. Encuentra las mejores ofertas actualizadas en un solo lugar.">
<meta property="og:title" content="Komparador.com - Compara precios entre tiendas">
<meta property="og:description" content="Compara precios entre todas las tiendas y encuentra los mejores precios.">
<meta property="og:image" content="{{ asset('images/logo.png') }}">
<meta property="og:url" content="{{ url()->current() }}">
<meta property="og:type" content="website">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="Komparador.com">
<meta name="twitter:description" content="Compara precios entre todas las tiendas y ahorra.">
<meta name="twitter:image" content="{{ asset('images/logo.png') }}">

    <title>Compara precios entre todas las tiendas y llévatelo al mejor precio - Komparador.com</title>
    <link rel="canonical" href="{{ $u1 }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    {{-- Icono --}}
    <link rel="icon" type="image/png" href="{{ asset('images/icono.webp') }}">
    
    {{-- SCRIPT PARA GOOGLE ANALYTICS--}}
    @if (env('GA_MEASUREMENT_ID'))
        {{-- Google tag (gtag.js) --}}
        <script async src="https://www.googletagmanager.com/gtag/js?id={{ env('GA_MEASUREMENT_ID') }}"></script>
        <script>
          {{-- Consent Mode + GA4 integrado con tu cookie_consent ('cookie_consent' -> 'c_c') --}}
          window.dataLayer = window.dataLayer || [];
          function gtag(){ dataLayer.push(arguments); }
    
          {{-- 1) Estado por defecto (antes de cualquier elección del usuario) --}}
          gtag('consent', 'default', {
            'ad_storage': 'denied',
            'analytics_storage': 'denied',
            'ad_user_data': 'denied',
            'ad_personalization': 'denied',
            {{-- Recomendados por Google cuando hay consentimiento: mantén activados --}}
            'url_passthrough': true
          });
    
          {{-- 2) Lee la cookie de tu banner si ya existe (sin esperar a eventos) --}}
          {{-- analytics → analytics_storage --}}
          {{-- marketing → ad_storage / ad_user_data / ad_personalization --}}
          (function applyStoredConsent(){
            try {
              var m = document.cookie.match(/(?:^|; )c_c=([^;]+)/);
              if (!m) return;
              var c = JSON.parse(decodeURIComponent(m[1]));
              gtag('consent', 'update', {
                'analytics_storage': c.analytics ? 'granted' : 'denied',
                'ad_storage':       c.marketing ? 'granted' : 'denied',
                'ad_user_data':     c.marketing ? 'granted' : 'denied',
                'ad_personalization': c.marketing ? 'granted' : 'denied'
              });
            } catch(e){}
          })();
    
          {{-- 3) Escucha el evento de tu banner para actualizar sin recargar --}}
          window.addEventListener('cookie-consent-changed', function(){
            try {
              var m = document.cookie.match(/(?:^|; )c_c=([^;]+)/);
              var c = m ? JSON.parse(decodeURIComponent(m[1])) : {};
              gtag('consent', 'update', {
                'analytics_storage': c.analytics ? 'granted' : 'denied',
                'ad_storage':       c.marketing ? 'granted' : 'denied',
                'ad_user_data':     c.marketing ? 'granted' : 'denied',
                'ad_personalization': c.marketing ? 'granted' : 'denied'
              });
            } catch(e){}
          });
    
          {{-- 4) Inicializa GA4 --}}
          gtag('js', new Date());
          gtag('config', '{{ env('GA_MEASUREMENT_ID') }}', {
            'transport_type': 'beacon'
          });
        </script>
    @endif
    
    @php
        // $ipAutorizada -> $ip1, $ipActual -> $ip2, $habilitar -> $h1
        $ip1 = env('IP_AUTORIZADA', '');
        $ip2 = request()->ip();
        $h1 = empty($ip1) || $ip2 === $ip1;
    @endphp

    @if($h1)
    <script>
    (function(h,o,t,j,a,r){
        h.hj=h.hj||function(){(h.hj.q=h.hj.q||[]).push(arguments)};
        h._hjSettings={hjid:6624894,hjsv:6};
        a=o.getElementsByTagName('head')[0];
        r=o.createElement('script');r.async=1;
        r.src=t+h._hjSettings.hjid+j+h._hjSettings.hjsv;
        a.appendChild(r);
    })(window,document,'https://static.hotjar.com/c/hotjar-','.js?sv=');
</script>
    @endif
</head>
<body class="bg-gray-50">
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

if (!function_exists('_u1')) {
    function _u1($unidadDeMedida) {
        return match ($unidadDeMedida) {
            'unidad', 'unidadMilesima' => '/Und.',
            'kilos' => '/Kg.',
            'litros' => '/L.',
            '800gramos' => '/800gr.',
            '100ml' => '/100ml.',
            default => '',
        };
    }
}
@endphp
    
    {{-- HEADER DESDE LA RUTA COMPONENTS/HEADER --}}
    <x-header />
    
    {{-- BARRA DE CATEGORÍAS Y PANEL LATERAL --}}
    <x-listado-categorias-horizontal-head />

    <main class="kk-main">
        <section class="kk-hero">
            <div>
                <h1 class="kk-hero-title">El mismo producto,<span>al mejor precio.</span></h1>
                <p class="kk-hero-copy">Comparamos entre todas las tiendas online para ayudarte a ahorrar de forma inmediata, con precios actualizados, además de detectar cupones y ofertas puntuales.</p>
                <div class="kk-hero-cta">
                    <a href="{{ _f1(route('buscar', ['q' => 'precios hot'])) }}" class="kk-btn kk-btn-primary">Ver mayores ofertas</a>
                    <a href="{{ _f1(route('categorias.todas')) }}" class="kk-btn kk-btn-ghost">Explorar categorías</a>
                </div>
                <div class="kk-hero-stats">
                    <div><strong>{{ $hs1['ofertas_indexadas'] ?? '+0' }}</strong><span>ofertas indexadas</span></div>
                    <div><strong>{{ $hs1['productos'] ?? '0' }}</strong><span>Productos</span></div>
                    <div><strong>{{ $hs1['tiendas'] ?? '0' }}</strong><span>Tiendas</span></div>
                </div>
            </div>
            <div class="kk-hero-visual">
                <div class="kk-float-card">
                    <small>Comparación en tiempo real</small>
                    <p class="kk-float-title">Detectamos el mejor precio entre todas las tiendas</p>
                    <div class="kk-mini-price">
                        <span class="kk-now">Desde 14,90€</span>
                        <span class="kk-was">22,40€</span>
                    </div>
                    <div class="kk-badges">
                        <span class="kk-badge">Comparador activo</span>
                        <span class="kk-badge kk-badge-hot">−34%</span>
                    </div>
                </div>
                <div class="kk-float-card">
                    <small>Alertas de precio</small>
                    <p class="kk-float-title">Te avisamos cuando una oferta mejore tu umbral</p>
                    <div class="kk-badges">
                        <span class="kk-badge">Seguimiento diario</span>
                    </div>
                </div>
            </div>
        </section>

        @if(isset($d3) && $d3 && count($d3->datos) > 0)
        <div id="mayores-ofertas" class="kk-deals-bg">
            <section class="kk-section">
                <div class="kk-head">
                    <div>
                        <h2>
                            <span class="kk-deals-title-desktop">Mayores ofertas y chollos del momento</span>
                            <span class="kk-deals-title-mobile">Mayores ofertas</span>
                        </h2>
                    </div>
                    <a href="{{ _f1(route('buscar', ['q' => 'precios hot'])) }}" class="kk-link">Ver todas las ofertas →</a>
                </div>
                <div class="kk-deals-row-wrap">
                    <button type="button" id="kkDealsPrev" class="kk-deals-arrow" aria-label="Ofertas anteriores">‹</button>
                    <div id="kkDealsScroll" class="kk-deals-row">
                        @foreach($d3->datos as $ph)
                        @php
                            $u1 = trim((string) ($ph['unidades_formateadas'] ?? ''));
                            $mostrarUnidades = !in_array(mb_strtolower($u1), ['1 unidades', '1 unidad'], true);
                        @endphp
                        <a href="{{ _f1($ph['url_producto']) }}" class="kk-deal-card">
                            <span class="kk-discount">−{{ (int) floatval(str_replace(',', '.', $ph['porcentaje_diferencia'])) }}%</span>
                            <div class="kk-deal-thumb">
                                <img loading="lazy" src="{{ asset('images/' . $ph['img_producto']) }}" alt="{{ $ph['producto_nombre'] }}">
                            </div>
                            <h3>{{ $ph['producto_nombre'] }}</h3>
                            <div class="kk-deal-meta">
                                <span class="kk-deal-store-line">
                                    {{ $ph['tienda_nombre'] ?? 'Tienda' }}@if($mostrarUnidades && $u1 !== '')<span class="kk-deal-units"> · {{ $u1 }}</span>@endif
                                </span>
                                <span class="kk-deal-price">{{ $ph['precio_formateado'] }}</span>
                            </div>
                        </a>
                        @endforeach
                    </div>
                    <button type="button" id="kkDealsNext" class="kk-deals-arrow" aria-label="Siguientes ofertas">›</button>
                </div>
            </section>
        </div>
        @endif

        @if(isset($d1) && $d1->count() > 0)
        <section class="kk-section">
            <div class="kk-head">
                <div>
                    <h2>Lo más buscado esta semana</h2>
                </div>
                <a href="{{ _f1(route('buscar', ['q' => 'más vendidos'])) }}" class="kk-link">Ranking completo →</a>
            </div>
            <div class="kk-products-grid kk-buscados-grid">
                @foreach($d1 as $p)
                <a href="{{ _f1($p->categoria->construirUrlCategorias($p->slug)) }}" class="kk-product-card">
                    <div class="kk-product-img">
                        @if(!empty($p->imagen_pequena[0] ?? ''))
                            <img loading="lazy" src="{{ asset('images/' . ($p->imagen_pequena[0] ?? '')) }}" alt="{{ $p->nombre }}">
                        @else
                            <svg class="kk-fallback" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        @endif
                    </div>
                    <div class="kk-product-lower">
                        <h3>{{ $p->nombre }}</h3>
                        <div class="kk-price-row">
                            @if($p->precio > 0)
                                <strong>{{ $p->unidadDeMedida === 'unidadMilesima' ? number_format($p->precio, 3) : number_format($p->precio, 2) }}€</strong>
                                <span>{{ _u1($p->unidadDeMedida) }}</span>
                            @else
                                <span class="kk-no-offer">Sin ofertas disponibles</span>
                            @endif
                        </div>
                    </div>
                </a>
                @endforeach
            </div>
        </section>
        @endif

        @if(isset($d2) && $d2->count() > 0)
        <section id="categorias-principales" class="kk-section kk-cats-wrap">
            <div class="kk-head">
                <div>
                    <h2>Categorías principales</h2>
                </div>
                <a href="{{ _f1(route('categorias.todas')) }}" class="kk-link">Ver todas</a>
            </div>
            <div class="kk-cats-row-wrap">
                <button type="button" id="kkCatsPrev" class="kk-cats-arrow" aria-label="Categorías anteriores">‹</button>
                <div id="kkCatsScroll" class="kk-cats-row">
                    @php
                    /** Emojis como en la maqueta index.html (.cat-tile .emoji), cuando no hay imagen en BD */
                    $kkCatEmoji = [
                        'electronica' => '📱',
                        'informatica' => '🖥️',
                        'fruta-y-verdura' => '🍎',
                        'frutas-y-verduras' => '🍎',
                        'bebe' => '🍼',
                        'deporte' => '🏃',
                        'jardin' => '🪴',
                        'bricolaje' => '🔧',
                    ];
                    @endphp
                    @foreach($d2 as $c)
                    @php
                        $kkEmoji1 = $kkCatEmoji[strtolower((string) $c->slug)] ?? null;
                    @endphp
                    <a href="{{ _f1('categoria/' . $c->slug) }}" class="kk-cat-item">
                        <div class="kk-cat-icon">
                            @if($c->imagen)
                                <img loading="lazy" src="{{ asset('images/' . $c->imagen) }}" alt="{{ $c->nombre }}">
                            @elseif($kkEmoji1)
                                <span class="kk-cat-emoji" aria-hidden="true">{{ $kkEmoji1 }}</span>
                            @else
                                <svg class="kk-fallback" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                </svg>
                            @endif
                        </div>
                        <span>{{ $c->nombre }}</span>
                    </a>
                    @endforeach
                </div>
                <button type="button" id="kkCatsNext" class="kk-cats-arrow" aria-label="Siguientes categorías">›</button>
            </div>
        </section>
        @endif

        <section class="kk-banner">
            <div>
                <h3>Alertas cuando baje de precio</h3>
                <p>Recuerda que tú decides el precio al que quieres comprar y te avisaremos justo cuando llegue a ese importe.</p>
            </div>
            <div class="kk-banner-actions">
                <button type="button" id="kkRememberAlert" class="kk-btn kk-btn-primary">Recuérdalo</button>
            </div>
        </section>
    </main>
{{-- FOOTER DESDE LA RUTA COMPONENTS/FOOTER --}}
    <x-footer />
    <style>
        .kk-main {
            max-width: 1200px;
            margin: 0 auto;
            padding: 1.5rem 1rem 2.5rem;
        }
        .kk-hero {
            display: grid;
            grid-template-columns: 1.15fr .85fr;
            gap: 1.5rem;
            align-items: center;
            margin-bottom: 2rem;
            padding: .75rem 0 1.25rem;
            border-bottom: 1px solid #e2e8f0;
        }
        .kk-hero-title {
            margin: 0 0 .75rem;
            font-size: clamp(1.8rem, 4vw, 2.6rem);
            font-weight: 800;
            line-height: 1.1;
            letter-spacing: -.04em;
        }
        .kk-hero-title span {
            display: block;
            margin-left: 4.4ch;
            font-size: 1.15em;
            color: #e97b11;
        }
        .kk-hero-copy {
            margin: 0 0 1rem;
            color: #475569;
            font-size: 1rem;
            max-width: 48ch;
        }
        .kk-hero-cta {
            display: flex;
            gap: .75rem;
            flex-wrap: wrap;
            margin-bottom: 1.15rem;
        }
        .kk-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: .75rem;
            padding: .7rem 1.15rem;
            font-weight: 700;
            font-size: .9rem;
            transition: .2s;
        }
        .kk-btn-primary { background: #e97b11; color: #fff; }
        .kk-btn-primary:hover { background: #d16a0f; }
        .kk-btn-ghost { border: 1px solid #e2e8f0; color: #0f172a; background: #fff; }
        .kk-btn-ghost:hover { border-color: #e97b11; color: #d16a0f; }
        .kk-hero-stats {
            display: flex;
            gap: 1.25rem;
            flex-wrap: wrap;
            padding-top: 1rem;
            border-top: 1px solid #e2e8f0;
        }
        .kk-hero-stats strong { display: block; font-size: 1.2rem; font-weight: 800; color: #0f172a; }
        .kk-hero-stats span { font-size: .78rem; color: #64748b; }
        .kk-hero-visual {
            display: none;
            gap: .8rem;
            justify-items: end;
        }
        @media (min-width: 1025px) {
            .kk-hero-visual {
                display: grid;
            }
        }
        .kk-float-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: .9rem;
            padding: 1rem;
            box-shadow: 0 10px 24px rgba(15,23,42,.08);
            max-width: 320px;
            width: 100%;
            animation: kkFloat 5s ease-in-out infinite;
        }
        .kk-float-card:nth-child(2) {
            animation-delay: -2s;
            max-width: 280px;
            justify-self: start;
        }
        .kk-float-card small { display: block; font-size: .7rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: .04em; }
        .kk-float-card p { margin: .35rem 0 0; font-size: .9rem; color: #334155; }
        .kk-float-title { font-weight: 700; line-height: 1.35; }
        .kk-mini-price {
            display: flex;
            align-items: baseline;
            gap: .5rem;
            margin-top: .55rem;
        }
        .kk-now { color: #e97b11; font-weight: 800; font-size: 1.1rem; }
        .kk-was { color: #94a3b8; text-decoration: line-through; font-size: .85rem; }
        .kk-badges {
            display: flex;
            flex-wrap: wrap;
            gap: .35rem;
            margin-top: .45rem;
        }
        .kk-badge {
            font-size: .64rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .03em;
            border-radius: .4rem;
            padding: .2rem .42rem;
            background: #ecfdf5;
            color: #059669;
        }
        .kk-badge-hot {
            background: #fef2f2;
            color: #dc2626;
        }
        @keyframes kkFloat {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-8px); }
        }
        .kk-section { margin-bottom: 2rem; padding: 1.4rem 0; }
        .kk-deals-bg {
            background: linear-gradient(180deg, #fef3e7 0%, #fff8f0 100%);
            border-block: 1px solid rgba(233,123,17,.14);
            margin-bottom: .35rem;
            width: 100vw;
            margin-left: calc(50% - 50vw);
            margin-right: calc(50% - 50vw);
        }
        .kk-deals-bg .kk-section {
            max-width: 1200px;
            margin: 0 auto;
            padding-left: 1rem;
            padding-right: 1rem;
        }
        .kk-head { display: flex; justify-content: space-between; align-items: end; gap: 1rem; margin-bottom: 1rem; flex-wrap: wrap; }
        .kk-head h2 { margin: 0; font-size: 1.35rem; font-weight: 800; letter-spacing: -.02em; color: #0f172a; }
        .kk-head p { margin: .2rem 0 0; font-size: .9rem; color: #64748b; }
        .kk-link { color: #e97b11; font-size: .88rem; font-weight: 700; }
        .kk-link:hover { text-decoration: underline; }
        .kk-deals-row-wrap {
            position: relative;
            display: flex;
            align-items: center;
            gap: .45rem;
        }
        .kk-deals-row {
            flex: 1;
            display: flex;
            gap: 1rem;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: thin;
            padding: .25rem 0 .55rem;
        }
        .kk-deals-row::-webkit-scrollbar { height: 7px; }
        .kk-deals-row::-webkit-scrollbar-track { background: #f1f5f9; border-radius: 999px; }
        .kk-deals-row::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 999px; }
        .kk-deals-row::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
        .kk-deals-arrow {
            flex: 0 0 auto;
            width: 34px;
            height: 34px;
            border-radius: 50%;
            border: 1px solid #e2e8f0;
            background: #fff;
            color: #e97b11;
            font-size: 1.1rem;
            line-height: 1;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .kk-deals-arrow:hover { background: #fef3e7; }
        .kk-deal-card {
            flex: 0 0 260px;
            display: flex;
            flex-direction: column;
            position: relative;
            background: #fff;
            border-radius: .85rem;
            border: 1px solid #e2e8f0;
            box-shadow: 0 10px 25px rgba(15,23,42,.07);
            padding: 1rem;
            transition: .2s;
        }
        .kk-deal-card:hover { transform: translateY(-2px); box-shadow: 0 14px 30px rgba(15,23,42,.1); }
        .kk-discount {
            position: absolute;
            top: .7rem;
            right: .7rem;
            background: #dc2626;
            color: #fff;
            border-radius: .5rem;
            font-size: .72rem;
            font-weight: 800;
            padding: .15rem .45rem;
        }
        .kk-deal-thumb {
            height: 132px;
            border-radius: .6rem;
            background: #f1f5f9;
            margin-bottom: .65rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .kk-deal-thumb img { max-height: 118px; width: auto; max-width: 100%; object-fit: contain; }
        .kk-deal-card h3 {
            margin: 0 0 .35rem;
            font-size: .87rem;
            line-height: 1.35;
            color: #0f172a;
            min-height: 2.3em;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .kk-deal-meta {
            display: flex;
            flex-direction: column;
            gap: .35rem;
            margin-top: auto;
        }
        .kk-deal-store-line {
            font-size: .74rem;
            color: #64748b;
            line-height: 1.3;
        }
        .kk-deal-units { font-weight: 500; }
        .kk-deal-price { color: #e97b11; font-size: 1.12rem; font-weight: 800; white-space: nowrap; }
        .kk-deals-title-mobile { display: none; }
        .kk-products-grid { display: grid; gap: 1rem; grid-template-columns: repeat(4, minmax(0, 1fr)); }
        .kk-product-card {
            display: flex;
            flex-direction: column;
            border-radius: .85rem;
            border: 1px solid #e2e8f0;
            background: #fff;
            padding: .9rem;
            transition: .2s;
        }
        .kk-product-card:hover { border-color: rgba(233,123,17,.3); box-shadow: 0 12px 28px rgba(15,23,42,.08); transform: translateY(-2px); }
        .kk-product-img {
            aspect-ratio: 1 / 1;
            border-radius: .65rem;
            background: #f1f5f9;
            margin-bottom: .65rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .kk-product-img img { width: 90%; height: 90%; object-fit: contain; }
        .kk-product-lower {
            display: flex;
            flex-direction: column;
            gap: .5rem;
            margin-top: auto;
            flex: 1 1 auto;
            min-height: 0;
        }
        .kk-product-card h3 {
            margin: 0;
            font-size: .87rem;
            font-weight: 700;
            color: #0f172a;
            min-height: 2.4em;
            line-height: 1.35;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .kk-price-row { display: flex; gap: .25rem; align-items: baseline; flex-wrap: wrap; }
        .kk-buscados-grid .kk-price-row {
            width: 100%;
            justify-content: flex-end;
        }
        .kk-price-row strong { color: #e97b11; font-size: 1.06rem; font-weight: 800; }
        .kk-price-row span { color: #64748b; font-size: .75rem; }
        .kk-no-offer { color: #64748b; font-size: .78rem; font-weight: 600; }
        .kk-fallback { width: 2rem; height: 2rem; color: #94a3b8; }
        .kk-cats-wrap {
            background: linear-gradient(180deg, rgba(241,245,249,.45), transparent);
            border-radius: 1rem;
            padding: 1rem;
        }
        .kk-cats-row-wrap {
            position: relative;
            display: flex;
            align-items: center;
            gap: .45rem;
        }
        .kk-cats-row {
            flex: 1;
            display: flex;
            gap: .7rem;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: thin;
            padding: .25rem 0 .45rem;
        }
        .kk-cats-row::-webkit-scrollbar { height: 7px; }
        .kk-cats-row::-webkit-scrollbar-track { background: #f1f5f9; border-radius: 999px; }
        .kk-cats-row::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 999px; }
        .kk-cats-row::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
        .kk-cats-arrow {
            flex: 0 0 auto;
            width: 34px;
            height: 34px;
            border-radius: 50%;
            border: 1px solid #e2e8f0;
            background: #fff;
            color: #e97b11;
            font-size: 1.1rem;
            line-height: 1;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .kk-cats-arrow:hover { background: #fef3e7; }
        .kk-cat-item {
            flex: 0 0 130px;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: .7rem;
            padding: .8rem .45rem;
            text-align: center;
            transition: .2s;
        }
        .kk-cat-item:hover { border-color: #e97b11; background: #fef3e7; }
        .kk-cat-icon {
            width: 50px;
            height: 50px;
            border-radius: .65rem;
            background: #f1f5f9;
            margin: 0 auto .45rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .kk-cat-icon img { width: 35px; height: 35px; object-fit: contain; }
        .kk-cat-emoji {
            font-size: 1.75rem;
            line-height: 1;
            display: block;
        }
        .kk-cat-item span { font-size: .75rem; font-weight: 700; color: #0f172a; }
        .kk-banner {
            margin-top: .5rem;
            background: linear-gradient(120deg, #0f172a 0%, #334155 100%);
            border-radius: 1rem;
            padding: 1.4rem 1.2rem;
            color: #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
        }
        .kk-banner h3 { margin: 0 0 .25rem; color: #fff; font-size: 1.2rem; font-weight: 800; }
        .kk-banner p { margin: 0; font-size: .9rem; }
        .kk-banner-actions {
            display: inline-flex;
            flex-wrap: wrap;
            gap: .6rem;
            align-items: center;
        }
        .kk-btn-dark {
            border-color: rgba(255,255,255,.28);
            color: #e2e8f0;
            background: rgba(255,255,255,.08);
        }
        .kk-btn-dark:hover {
            background: rgba(255,255,255,.16);
            color: #fff;
            border-color: rgba(255,255,255,.42);
        }
        @media (min-width: 1024px) {
            .kk-deal-meta {
                flex-direction: row;
                justify-content: space-between;
                align-items: baseline;
                gap: .5rem;
            }
            .kk-deal-store-line {
                min-width: 0;
                flex: 1;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }
            .kk-deal-price { flex-shrink: 0; }
        }
        @media (max-width: 1024px) {
            .kk-hero { grid-template-columns: 1fr; }
            .kk-hero-cta {
                flex-wrap: nowrap;
                gap: .45rem;
            }
            .kk-hero-cta .kk-btn {
                flex: 1 1 0;
                min-width: 0;
                padding: .62rem .5rem;
                font-size: clamp(.72rem, 3.1vw, .85rem);
                line-height: 1.25;
                text-align: center;
            }
            .kk-deal-card { flex-basis: 240px; }
            {{-- Lo más buscado: mínimo 2 por fila en tablet/móvil (en PC siguen 4 columnas / 8 ítems) --}}
            .kk-buscados-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
        @media (max-width: 767px) {
            {{-- Móvil: título corto en mayores ofertas --}}
            .kk-deals-title-desktop { display: none; }
            .kk-deals-title-mobile { display: inline; }
            {{-- Móvil: scroll de ofertas solo con gesto, sin flechas --}}
            .kk-deals-row-wrap .kk-deals-arrow { display: none; }
            {{-- Móvil: mayores ofertas ≈2 tarjetas por fila; lo más buscado solo 4 productos (5.º–8.º ocultos) --}}
            .kk-deals-row .kk-deal-card {
                flex: 0 0 calc(50% - 0.5rem);
                max-width: calc(50% - 0.5rem);
            }
            .kk-buscados-grid .kk-product-card:nth-child(n+5) { display: none; }
        }
        @media (max-width: 640px) {
            .kk-main { padding: 1rem .75rem 2rem; }
            .kk-cats-arrow { width: 30px; height: 30px; }
            .kk-cat-item { flex-basis: 115px; }
        }
    </style>

    
{{-- JS PARA EL HEADER --}}
@stack('scripts')

<script>
    const _kcs1 = document.getElementById('kkCatsScroll');
    document.getElementById('kkCatsPrev')?.addEventListener('click', () => _kcs1?.scrollBy({ left: -220, behavior: 'smooth' }));
    document.getElementById('kkCatsNext')?.addEventListener('click', () => _kcs1?.scrollBy({ left: 220, behavior: 'smooth' }));
    const _kds1 = document.getElementById('kkDealsScroll');
    document.getElementById('kkDealsPrev')?.addEventListener('click', () => _kds1?.scrollBy({ left: -280, behavior: 'smooth' }));
    document.getElementById('kkDealsNext')?.addEventListener('click', () => _kds1?.scrollBy({ left: 280, behavior: 'smooth' }));
    document.getElementById('kkRememberAlert')?.addEventListener('click', () => {
        window.alert('Guárdalo en mente: tú marcas el precio objetivo y Komparador te avisa cuando aparezca.');
    });
</script>

<x-cookie-consent />
</body>
</html> 