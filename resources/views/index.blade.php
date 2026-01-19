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
        h._hjSettings={hjid:6621734,hjsv:6};
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
@endphp
    
    {{-- HEADER DESDE LA RUTA COMPONENTS/HEADER --}}
    <x-header />
    
    {{-- BARRA DE CATEGORÍAS Y PANEL LATERAL --}}
    <x-listado-categorias-horizontal-head />

    {{-- CONTENIDO PRINCIPAL --}}
    <main class="max-w-7xl mx-auto px-6 py-2 rounded-xl bg-gray-100">

        {{-- PRODUCTOS TOP --}}
        {{-- $productosTop -> $d1, $producto -> $p --}}
        @if(isset($d1) && $d1->count() > 0)
        <section class="mb-4">
            <h1 class="text-2xl md:text-3xl font-bold mb-4">Los más vendidos</h1>

            <div class="grid grid-cols-2 md:grid-cols-2 lg:grid-cols-4 gap-6">
                @foreach($d1 as $p)
                {{-- añadirCam() -> _f1() --}}
                <a href="{{ _f1($p->categoria->construirUrlCategorias($p->slug)) }}"
                   class="group flex flex-col items-center bg-white rounded-xl shadow-md transition-all duration-300 transform hover:scale-105 hover:shadow-2xl cursor-pointer p-4">
                    <div class="w-full flex justify-center mb-3">
                        @if(!empty($p->imagen_pequena[0] ?? ''))
                            <img loading="lazy" src="{{ asset('images/' . ($p->imagen_pequena[0] ?? '')) }}"
                                 alt="{{ $p->nombre }}"
                                 class="w-32 h-32 object-contain rounded-lg shadow-sm transition-all duration-300 group-hover:shadow-lg bg-gray-100">
                        @else
                            <div class="w-32 h-32 flex items-center justify-center rounded-lg bg-gradient-to-br from-blue-100 to-blue-200">
                                <svg class="w-12 h-12 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                            </div>
                        @endif
                    </div>
                    <p class="font-semibold text-gray-700 text-center text-sm mb-1 line-clamp-2">{{ $p->nombre }}</p>
<p class="text-center mb-1 flex items-center justify-center gap-1">
    <span class="text-[8px] text-gray-500">Desde:</span>
    <span class="text-xl font-bold" style="color: #e97b11;">{{ $p->unidadDeMedida === 'unidadMilesima' ? number_format($p->precio, 3) : number_format($p->precio, 2) }}€</span>
    @if($p->unidadDeMedida === 'unidad')
        <span class="text-[10px] text-gray-500">/Und.</span>
    @elseif($p->unidadDeMedida === 'kilos')
        <span class="text-[10px] text-gray-500">/Kg.</span>
    @elseif($p->unidadDeMedida === 'litros')
        <span class="text-[10px] text-gray-500">/L.</span>
    @elseif($p->unidadDeMedida === 'unidadMilesima')
        <span class="text-[10px] text-gray-500">/Und.</span>
    @elseif($p->unidadDeMedida === 'unidadUnica')
        {{-- No mostrar sufijo para UnidadUnica --}}
    @elseif($p->unidadDeMedida === '800gramos')
        <span class="text-[10px] text-gray-500">/800gr.</span>
    @elseif($p->unidadDeMedida === '100ml')
        <span class="text-[10px] text-gray-500">/100ml.</span>
    @endif
</p>
</a>
                @endforeach
            </div>
        </section>
        @endif

        {{-- CARRUSEL DE CATEGORÍAS TOP --}}
        {{-- $categoriasTop -> $d2, $categoria -> $c --}}
        {{-- carouselCategorias -> c1, flechaIzquierda -> b1, flechaDerecha -> b2 --}}
        @if(isset($d2) && $d2->count() > 0)
<section class="w-full py-8" style="background-color: #fef3e7;">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative">
        <h2 class="text-xl md:text-2xl font-bold text-gray-800 mb-4">Categorías Más Visitadas</h2>

        {{-- Flecha izquierda --}}
        <button type="button"
                id="b1"
                class="absolute left-0 top-[60%] -translate-y-1/2 z-10 bg-white rounded-full p-2 shadow transition"
                style="color: #e97b11;"
                onmouseover="this.style.backgroundColor='#fef3e7';"
                onmouseout="this.style.backgroundColor='white';">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </button>

        {{-- Flecha derecha --}}
        <button type="button"
                id="b2"
                class="absolute right-0 top-[60%] -translate-y-1/2 z-10 bg-white rounded-full p-2 shadow transition"
                style="color: #e97b11;"
                onmouseover="this.style.backgroundColor='#fef3e7';"
                onmouseout="this.style.backgroundColor='white';">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
        </button>

        {{-- Carrusel --}}
        <div class="relative">
            <div id="c1"
                 class="flex gap-4 overflow-x-auto scrollbar-hide scroll-smooth px-1">
                @foreach($d2 as $c)
                {{-- añadirCam() -> _f1() --}}
                <a href="{{ _f1('categoria/' . $c->slug) }}"
                   class="flex-none w-28 sm:w-32 md:w-36 bg-white rounded-xl shadow-sm hover:shadow-md hover:-translate-y-1 transition-all duration-300 p-4 text-center">
                    <div class="w-16 h-16 mx-auto mb-3">
                        @if($c->imagen)
                            <img loading="lazy" src="{{ asset('images/' . $c->imagen) }}"
                                 alt="{{ $c->nombre }}"
                                 class="w-full h-full object-contain rounded-md bg-gray-100">
                        @else
                            <div class="w-full h-full flex items-center justify-center rounded-md bg-gradient-to-br from-pink-100 to-pink-200">
                                <svg class="w-8 h-8 text-pink-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                </svg>
                            </div>
                        @endif
                    </div>
                    <span class="text-base font-semibold text-gray-800 leading-snug">{{ $c->nombre }}</span>
                </a>
                @endforeach
            </div>
        </div>
    </div>
</section>

@endif

{{-- SECCIÓN PRECIOS HOT --}}
        {{-- $preciosHot -> $d3, $productoHot -> $ph --}}
        @if(isset($d3) && $d3 && count($d3->datos) > 0)
<section class="mb-8">
    <h2 class="text-xl md:text-2xl font-bold text-gray-800 mb-4">🔥 Precios Hot</h2>
    <div class="relative">
        <div class="flex overflow-x-auto scrollbar-visible gap-4 px-1 pb-2">
            @foreach($d3->datos as $ph)
            <div class="bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow duration-300 relative min-w-[320px] md:min-w-[360px] flex-shrink-0">
                <div class="p-4">
                    {{-- Badge descuento --}}
                    <span class="absolute top-2 right-2 bg-red-100 text-red-700 text-xs font-semibold px-2 py-1 rounded z-10">
        -{{ (int) floatval(str_replace(',', '.', $ph['porcentaje_diferencia'])) }}%
    </span>

                    <div class="flex mb-3 items-start">
                        {{-- Imagen producto --}}
                        <img loading="lazy" src="{{ asset('images/' . $ph['img_producto']) }}"
                             alt="{{ $ph['producto_nombre'] }}"
                             class="w-20 h-20 object-contain rounded bg-gray-100 shadow-sm flex-shrink-0">

                        {{-- Logo tienda + nombre producto --}}
                        <div class="flex flex-col justify-between flex-grow h-full">
                            <div class="flex justify-center w-full">
                                <img loading="lazy" src="{{ asset('images/' . $ph['img_tienda']) }}"
                                     alt="{{ $ph['tienda_nombre'] }}"
                                     class="object-contain max-h-7 w-auto mb-2">
                            </div>
                            <h3 class="font-semibold text-gray-800 text-sm md:text-base leading-tight pl-3">
                                {{ $ph['producto_nombre'] }}
                            </h3>
                        </div>
                    </div>

                    <div class="flex items-center justify-between">
                        <div>
                            <div class="flex items-baseline gap-1">
                                <p class="text-lg font-bold" style="color: #e97b11;">
                                    {{ $ph['precio_formateado'] }}
                                </p>
                            </div>
                            @if(isset($ph['unidades_formateadas']) && !empty($ph['unidades_formateadas']))
                                <p class="text-sm text-gray-600">{{ $ph['unidades_formateadas'] }}</p>
                            @endif
                        </div>
                        {{-- añadirCam() -> _f1() --}}
                        <a href="{{ _f1($ph['url_producto']) }}" 
                               class="text-xs bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded transition">
                                Ver producto
                        </a>
                        {{-- añadirCam() -> _f1() --}}
                        <a href="{{ _f1($ph['url_oferta']) }}"
                           target="_blank"
                           class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                            Comprar
                        </a>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>
@endif

        {{-- OFERTAS DESTACADAS --}}
        {{-- Código comentado - no se procesa
        @if(isset($ofertasDestacadas) && $ofertasDestacadas->count() > 0)
        <section class="mb-12">
            <h2 class="text-2xl font-bold text-gray-800 mb-6">Ofertas Destacadas</h2>
            <div class="flex overflow-x-auto scrollbar-hide gap-4 px-1">
                @foreach($ofertasDestacadas as $oferta)
                <div class="bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow duration-300 relative min-w-[280px] md:min-w-[300px] flex-shrink-0">
                    <div class="p-4">
                        <span class="absolute top-2 right-2 bg-red-100 text-red-700 text-xs font-semibold px-2 py-0.5 rounded z-10">
                            Oferta
                        </span>

                        <div class="flex mb-3 items-start">
                            <img loading="lazy" src="{{ asset('images/' . ($oferta->producto->imagen_pequena[0] ?? '')) }}" alt="{{ $oferta->producto->nombre }}" class="w-16 h-16 object-contain rounded bg-gray-100 shadow-sm flex-shrink-0">

                            <div class="flex flex-col justify-between flex-grow h-full">
                                <div class="flex justify-center w-full">
                                    <img loading="lazy" src="{{ asset('images/' . $oferta->tienda->url_imagen) }}" alt="{{ $oferta->tienda->nombre }}" class="object-contain max-h-7 w-auto mb-2">
                                </div>

                                <h3 class="font-semibold text-gray-800 text-sm md:text-base leading-tight pl-3">{{ $oferta->producto->nombre }}</h3>
                            </div>
                        </div>

                        <div class="flex items-center justify-between">
                            <div>
                                <div class="flex items-baseline gap-1">
                                    <p class="text-lg font-bold text-green-600">
                                        {{ number_format($oferta->precio_unidad, 2) }}€
                                    </p>
                                    <span class="text-sm text-gray-500">/Und.</span>
                                </div>
                            <p class="text-sm text-gray-600">{{ $oferta->unidades }} unidades</p>
                        </div>
                        <a href="{{ route('click.redirigir', $oferta->id) }}" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">Ver Oferta</a>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </section>
        @endif
        --}}

        {{-- ÚLTIMOS PRODUCTOS --}}
        {{-- $ultimosProductos -> $d4, $producto -> $p --}}
        @if(isset($d4) && $d4->count() > 0)
        <section class="mb-12">
            <h2 class="text-xl md:text-2xl font-bold text-gray-800 mb-4">Últimos Productos Añadidos</h2>
            <div class="relative">
                <div class="flex space-x-4 lg:space-x-3 overflow-x-auto pb-4 scrollbar-visible">
                    @foreach($d4 as $p)
                    {{-- añadirCam() -> _f1() --}}
                    <a href="{{ _f1($p->categoria->construirUrlCategorias($p->slug)) }}"
                    class="group flex flex-col items-center bg-white rounded-xl shadow-md transition-all duration-300 transform hover:scale-105 hover:shadow-2xl cursor-pointer p-4 w-2/5 min-w-[160px] sm:w-1/4 sm:min-w-[200px] lg:w-1/5 lg:min-w-[220px]">
                        <div class="w-full flex justify-center mb-3">
                            @if(!empty($p->imagen_pequena[0] ?? ''))
                                <img loading="lazy" src="{{ asset('images/' . ($p->imagen_pequena[0] ?? '')) }}"
                                    alt="{{ $p->nombre }}"
                                    class="w-32 h-32 object-contain rounded-lg shadow-sm transition-all duration-300 group-hover:shadow-lg bg-gray-100">
                            @else
                                <div class="w-32 h-32 flex items-center justify-center rounded-lg bg-gradient-to-br from-blue-100 to-blue-200">
                                    <svg class="w-12 h-12 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                </div>
                            @endif
                        </div>
                        <h3 class="font-semibold text-gray-700 text-center text-sm mb-1 line-clamp-2">
                            {{ $p->nombre }}
                        </h3>
                        <p class="text-center mb-1 flex items-center justify-center gap-1">
                            <span class="text-[8px] text-gray-500">Desde:</span>
                            <span class="text-xl font-bold" style="color: #e97b11;">{{ $p->unidadDeMedida === 'unidadMilesima' ? number_format($p->precio, 3) : number_format($p->precio, 2) }}€</span>
                            @if($p->unidadDeMedida === 'unidad')
                                <span class="text-[10px] text-gray-500">/Und.</span>
                            @elseif($p->unidadDeMedida === 'kilos')
                                <span class="text-[10px] text-gray-500">/Kg.</span>
                            @elseif($p->unidadDeMedida === 'litros')
                                <span class="text-[10px] text-gray-500">/L.</span>
                            @elseif($p->unidadDeMedida === 'unidadMilesima')
                                <span class="text-[10px] text-gray-500">/Und.</span>
                            @elseif($p->unidadDeMedida === 'unidadUnica')
                                {{-- No mostrar sufijo para UnidadUnica --}}
                            @elseif($p->unidadDeMedida === '800gramos')
                                <span class="text-[10px] text-gray-500">/800gr.</span>
                            @elseif($p->unidadDeMedida === '100ml')
                                <span class="text-[10px] text-gray-500">/100ml.</span>
                            @endif
                        </p>
                    </a>
                    @endforeach
                </div>
            </div>
        </section>
        @endif

        

    </main>
{{-- FOOTER DESDE LA RUTA COMPONENTS/FOOTER --}}
    <x-footer />
    <style>
        .scrollbar-hide {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
        .scrollbar-hide::-webkit-scrollbar {
            display: none;
        }
        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        {{-- Estilos para barras de desplazamiento visibles --}}
        .scrollbar-visible {
            scrollbar-width: thin;
            scrollbar-color: #cbd5e0 #f7fafc;
        }
        
        .scrollbar-visible::-webkit-scrollbar {
            height: 8px;
            display: block;
        }
        
        .scrollbar-visible::-webkit-scrollbar-track {
            background: #f7fafc;
            border-radius: 4px;
        }
        
        .scrollbar-visible::-webkit-scrollbar-thumb {
            background: #cbd5e0;
            border-radius: 4px;
        }
        
        .scrollbar-visible::-webkit-scrollbar-thumb:hover {
            background: #a0aec0;
        }
        
        {{-- Estilos específicos para móvil --}}
        @media (max-width: 1024px) {
            .scrollbar-visible {
                scrollbar-width: thin;
                scrollbar-color: #e2e8f0 #f7fafc;
            }
            
            .scrollbar-visible::-webkit-scrollbar {
                height: 6px;
            }
            
            .scrollbar-visible::-webkit-scrollbar-thumb {
                background: #e2e8f0;
            }
            
            .scrollbar-visible::-webkit-scrollbar-thumb:hover {
                background: #cbd5e0;
            }
        }
        
    </style>

    
{{-- JS PARA EL HEADER --}}
@stack('scripts')

{{-- MOVER EL SCROLL HORIZONTAL DE LAS CATEGORIAS --}}
{{-- carouselCategorias -> c1, flechaIzquierda -> b1, flechaDerecha -> b2 --}}
<script>
    const c1 = document.getElementById('c1');
    const b1 = document.getElementById('b1');
    const b2 = document.getElementById('b2');

    b1?.addEventListener('click', () => {
        c1?.scrollBy({ left: -200, behavior: 'smooth' });
    });

    b2?.addEventListener('click', () => {
        c1?.scrollBy({ left: 200, behavior: 'smooth' });
    });
</script>
<x-cookie-consent />
</body>
</html> 