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
    <link rel="canonical" href="{{ $u1 }}">
    <meta property="og:type" content="website">
    {{-- $categoriaActual -> $ca1, $productos -> $pr1 --}}
    <title>{{ $ca1->nombre }} - Komparador.com</title>
    <meta name="description" content="Descubre {{ $pr1->total() }} productos en la categoría {{ $ca1->nombre }} al mejor precio. Compara ofertas entre todas las tiendas en Komparador.com.">
    {{-- Icono --}}
    <link rel="icon" type="image/png" href="{{ asset('images/icono.webp') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])

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
@endphp
    
    {{-- HEADER DESDE COMPONENTS/HEADER --}}
    <x-header />

    {{-- CONTENIDO PRINCIPAL --}}
    <main class="max-w-7xl mx-auto px-6 py-2 bg-gray-100">
        
        {{-- BREADCRUMB --}}
        <nav class="mb-1">
            @php
                // En móvil, mostrar solo las últimas 2 categorías si hay más de 3
                $totalCategorias = count($b1);
                $breadcrumbMostrar = $b1;
                
                if ($totalCategorias > 3) {
                    // Mantener las últimas 2 categorías
                    $breadcrumbMostrar = array_slice($b1, -2);
                }
            @endphp
            <ol class="flex items-center space-x-1 text-sm text-gray-600 overflow-hidden">
                <li class="flex-shrink-0">
                    {{-- añadirCam() -> _f1() --}}
                    <a href="{{ _f1(route('home')) }}" class="flex items-center transition-colors" style="color: inherit;" onmouseover="this.style.color='#e97b11'" onmouseout="this.style.color='inherit'" title="Inicio">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                        </svg>
                    </a>
                </li>
                
                @if($totalCategorias > 3)
                    <li class="flex items-center flex-shrink-0">
                        <svg class="w-4 h-4 mx-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                        <span class="text-gray-400">...</span>
                    </li>
                @endif
                
                {{-- $breadcrumb -> $b1, $item -> $it --}}
                @foreach($breadcrumbMostrar as $it)
                <li class="flex items-center flex-shrink-0 min-w-0">
                    <svg class="w-4 h-4 mx-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                    {{-- añadirCam() -> _f1() --}}
                    <a href="{{ _f1($it['url']) }}" class="truncate max-w-[150px] sm:max-w-[200px] md:max-w-none transition-colors" style="color: inherit;" onmouseover="this.style.color='#e97b11'" onmouseout="this.style.color='inherit'" title="{{ $it['nombre'] }}">{{ $it['nombre'] }}</a>
                </li>
                @endforeach
            </ol>
        </nav>

        {{-- TÍTULO Y CONTADOR --}}
        {{-- $categoriaActual -> $ca1, $productos -> $pr1 --}}
        <div class="mb-4 bg-gray-200 px-4 py-3 rounded">
    <h1 class="text-3xl font-bold text-gray-800 mb-2">{{ $ca1->nombre }}</h1>
    <p class="text-gray-600">{{ $pr1->total() }} productos encontrados</p>
</div>

        

        {{-- BLOQUE DE CATEGORÍAS PRINCIPALES O SUBCATEGORÍAS --}}
        {{-- $bloqueCategorias -> $bc1, $categorias -> $c1, $subcategorias -> $sc1 --}}
        @php
        // Si estamos en "todas las categorías", usamos $categorias, si no, $subcategorias
        $bc1 = isset($ca1) && $ca1->nombre === 'Todas las categorías' ? $c1 : $sc1;
        @endphp
        @if($bc1->count() > 0)
            <section class="mb-12">
                <h2 class="text-2xl font-bold text-gray-800 mb-6">
                  {{ isset($ca1) && $ca1->nombre === 'Todas las categorías' ? 'Todas las categorías' : 'Subcategorías' }}
                </h2>
                {{-- subcategorias-container -> sc2 --}}
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3 md:gap-6" id="sc2">
                    {{-- $cat -> $ct1, $index -> $idx --}}
                    @foreach($bc1->take(8) as $idx => $ct1)
                    <div class="{{ $idx >= 4 ? 'hidden sm:block' : '' }} bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow duration-300 p-2 md:p-4 flex flex-col items-center text-center">
                        {{-- añadirCam() -> _f1() --}}
                        <a href="{{ _f1(route('categoria.show', $ct1->slug)) }}" class="block">
                            <div class="h-20 w-20 md:h-24 md:w-24 mb-3 md:mb-4 mx-auto">
                                @if($ct1->imagen)
                                    <img loading="lazy" src="{{ asset('images/'.$ct1->imagen) }}" alt="{{ $ct1->nombre }}" class="w-full h-full object-contain">
                                @else
                                    <div class="w-full h-full bg-gray-200 flex items-center justify-center rounded">
                                        <svg class="w-6 h-6 md:w-8 md:h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h2.586a1 1 0 01.707.293l1.414 1.414A1 1 0 009.414 5H20a1 1 0 011 1v11a1 1 0 01-1 1H4a1 1 0 01-1-1V4z"></path>
                                        </svg>
                                    </div>
                                @endif
                            </div>
                        </a>
                        {{-- añadirCam() -> _f1() --}}
                        <a href="{{ _f1(route('categoria.show', $ct1->slug)) }}" class="text-black text-base md:text-xl font-bold hover:text-gray-700">
                        <div class="flex items-center justify-center gap-1 md:gap-2 mb-1 md:mb-2">
                            {{ $ct1->nombre }}
                            <svg class="w-3 h-3 md:w-4 md:h-4 text-black hover:text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </div>
                        </a>
                        <hr class="border-t border-gray-300 w-32 md:w-40 mx-auto mb-2 md:mb-3">
                        {{-- Categorías hijas --}}
                        {{-- $hijas -> $hj1, $hija -> $hj2 --}}
                        @php
                        $hj1 = $ct1->subcategorias()->orderBy('nombre')->limit(8)->get();
                        @endphp
                        @if($hj1->count() > 0)
                            <div class="w-full">
                                <div class="grid grid-cols-1 gap-1">
                                    @foreach($hj1 as $hj2)
                                    <div class="bg-gray-100 hover:bg-pink-50 rounded-md px-2 md:px-3 py-1.5 md:py-2 transition-all duration-200 border-l-2 border-transparent hover:border-pink-300 hover:shadow-md hover:scale-105">
                                        {{-- añadirCam() -> _f1() --}}
                                        <a href="{{ _f1(route('categoria.show', $hj2->slug)) }}" 
                                           class="text-xs md:text-base text-gray-700 hover:text-pink-600 transition-colors duration-200 block">
                                            <div class="flex items-center justify-between">
                                                <span class="flex-1 min-w-0 text-left">{{ $hj2->nombre }}</span>
                                                <svg class="w-3 h-3 text-gray-400 ml-2 flex-shrink-0 hidden sm:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                                </svg>
                                            </div>
                                        </a>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                    @endforeach
                    {{-- Categorías adicionales generadas desde PHP (ocultas inicialmente, dentro del contenedor) --}}
                    @php
                    if ($bc1->count() > 8) {
                        foreach ($bc1->skip(8) as $ct2) {
                            $hj3 = $ct2->subcategorias()->orderBy('nombre')->limit(8)->get();
                            $urlCategoria = _f1(route('categoria.show', $ct2->slug));
                    @endphp
                        <div class="categoria-adicional bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow duration-300 p-2 md:p-4 flex flex-col items-center text-center" style="display: none;">
                            <a href="{{ $urlCategoria }}" class="block">
                                <div class="h-20 w-20 md:h-24 md:w-24 mb-3 md:mb-4 mx-auto">
                                    @if($ct2->imagen)
                                        <img loading="lazy" src="{{ asset('images/'.$ct2->imagen) }}" alt="{{ $ct2->nombre }}" class="w-full h-full object-contain">
                                    @else
                                        <div class="w-full h-full bg-gray-200 flex items-center justify-center rounded">
                                            <svg class="w-6 h-6 md:w-8 md:h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h2.586a1 1 0 01.707.293l1.414 1.414A1 1 0 009.414 5H20a1 1 0 011 1v11a1 1 0 01-1 1H4a1 1 0 01-1-1V4z"></path>
                                            </svg>
                                        </div>
                                    @endif
                                </div>
                            </a>
                            <a href="{{ $urlCategoria }}" class="text-black text-base md:text-xl font-bold hover:text-gray-700">
                                <div class="flex items-center justify-center gap-1 md:gap-2 mb-1 md:mb-2">
                                    {{ $ct2->nombre }}
                                    <svg class="w-3 h-3 md:w-4 md:h-4 text-black hover:text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                    </svg>
                                </div>
                            </a>
                            <hr class="border-t border-gray-300 w-32 md:w-40 mx-auto mb-2 md:mb-3">
                            @if($hj3->count() > 0)
                                <div class="w-full">
                                    <div class="grid grid-cols-1 gap-1">
                                        @foreach($hj3->take(8) as $hj4)
                                        <div class="bg-gray-100 hover:bg-pink-50 rounded-md px-2 md:px-3 py-1.5 md:py-2 transition-all duration-200 border-l-2 border-transparent hover:border-pink-300 hover:shadow-md hover:scale-105">
                                            {{-- añadirCam() -> _f1() --}}
                                            <a href="{{ _f1(route('categoria.show', $hj4->slug)) }}" class="text-xs md:text-base text-gray-700 hover:text-pink-600 transition-colors duration-200 block">
                                                <div class="flex items-center justify-between">
                                                    <span class="flex-1 min-w-0 text-left">{{ $hj4->nombre }}</span>
                                                    <svg class="w-3 h-3 text-gray-400 ml-2 flex-shrink-0 hidden sm:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                                    </svg>
                                                </div>
                                            </a>
                                        </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    @php
                        }
                    }
                    @endphp
                </div>
                @if($bc1->count() > 4)
                <div class="text-center mt-6">
                    {{-- btn-mostrar-subcategorias -> bms1 --}}
                    <button id="bms1" 
                            style="background-color: #e97b11;"
                            class="text-white px-6 py-2 rounded-lg transition-colors"
                            onmouseover="this.style.backgroundColor='#d16a0f'"
                            onmouseout="this.style.backgroundColor='#e97b11'">
                        Mostrar más categorías
                    </button>
                </div>
                @endif
            </section>
        @endif

        



        {{-- PRODUCTOS --}}
        {{-- $producto -> $pr2, $totalProductosDisponibles -> $tpd1 --}}
        @if($pr1->count() > 0)
    <section class="mb-12">
        <h2 class="text-2xl font-bold text-gray-800 mb-6 inline-block" style="border-bottom: 4px solid #73b112;">
    Productos más visitados de {{ $ca1->nombre }}
    @if(isset($tpd1))
        <span class="text-sm font-normal text-gray-600">({{ number_format($tpd1, 0, ',', '.') }} productos disponibles)</span>
    @endif
</h2>

        {{-- productos-container -> pcp1 (productos-container-principal) --}}
        <div id="pcp1" class="grid grid-cols-2 md:grid-cols-2 lg:grid-cols-5 gap-6">
            @foreach($pr1->take(10) as $pr2)
            {{-- añadirCam() -> _f1() --}}
            <a href="{{ _f1($pr2->categoria->construirUrlCategorias($pr2->slug)) }}"
               class="group flex flex-col items-center bg-white rounded-xl shadow-md transition-all duration-300 transform hover:scale-105 hover:shadow-2xl cursor-pointer p-4">
                <div class="w-full flex justify-center mb-3">
                    @if(!empty($pr2->imagen_pequena[0] ?? ''))
                        <img loading="lazy" src="{{ asset('images/' . ($pr2->imagen_pequena[0] ?? '')) }}"
                             alt="{{ $pr2->nombre }}"
                             class="w-32 h-32 object-contain rounded-lg shadow-sm transition-all duration-300 group-hover:shadow-lg bg-gray-100">
                    @else
                        <div class="w-32 h-32 flex items-center justify-center rounded-lg bg-gradient-to-br from-blue-100 to-blue-200">
                            <svg class="w-12 h-12 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                    @endif
                </div>
                <p class="font-semibold text-gray-700 text-center text-sm mb-1 line-clamp-2">{{ $pr2->nombre }}</p>
                <p class="text-center mb-1 flex items-center justify-center gap-1">
                    @if($pr2->precio > 0)
                        <span class="text-xl font-bold" style="color: #e97b11;">{{ $pr2->unidadDeMedida === 'unidadMilesima' ? number_format($pr2->precio, 3) : number_format($pr2->precio, 2) }}€</span>
                        @if($pr2->unidadDeMedida === 'unidad')
                            <span class="text-sm md:text-base text-gray-500">/Und.</span>
                        @elseif($pr2->unidadDeMedida === 'kilos')
                            <span class="text-sm md:text-base text-gray-500">/Kg.</span>
                        @elseif($pr2->unidadDeMedida === 'litros')
                            <span class="text-sm md:text-base text-gray-500">/L.</span>
                        @elseif($pr2->unidadDeMedida === 'unidadMilesima')
                            <span class="text-sm md:text-base text-gray-500">/Und.</span>
                        @elseif($pr2->unidadDeMedida === 'unidadUnica')
                            {{-- No mostrar sufijo para UnidadUnica --}}
                        @elseif($pr2->unidadDeMedida === '800gramos')
                            <span class="text-sm md:text-base text-gray-500">/800gr.</span>
                        @elseif($pr2->unidadDeMedida === '100ml')
                            <span class="text-sm md:text-base text-gray-500">/100ml.</span>
                        @endif
                    @else
                        <span class="text-sm font-semibold" style="color: #e97b11;">Sin Ofertas Disponibles</span>
                    @endif
                </p>
            </a>
            @endforeach
            {{-- Productos adicionales generados desde PHP (ocultos inicialmente, dentro del contenedor) --}}
            @if($pr1->count() > 10)
                @foreach($pr1->skip(10) as $pr3)
                {{-- añadirCam() -> _f1() --}}
                <a href="{{ _f1($pr3->categoria->construirUrlCategorias($pr3->slug)) }}"
                   class="producto-adicional group flex flex-col items-center bg-white rounded-xl shadow-md transition-all duration-300 transform hover:scale-105 hover:shadow-2xl cursor-pointer p-4" style="display: none;">
                    <div class="w-full flex justify-center mb-3">
                        @if(!empty($pr3->imagen_pequena[0] ?? ''))
                            <img loading="lazy" src="{{ asset('images/' . ($pr3->imagen_pequena[0] ?? '')) }}"
                                 alt="{{ $pr3->nombre }}"
                                 class="w-32 h-32 object-contain rounded-lg shadow-sm transition-all duration-300 group-hover:shadow-lg bg-gray-100">
                        @else
                            <div class="w-32 h-32 flex items-center justify-center rounded-lg bg-gradient-to-br from-blue-100 to-blue-200">
                                <svg class="w-12 h-12 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                            </div>
                        @endif
                    </div>
                    <p class="font-semibold text-gray-700 text-center text-sm mb-1 line-clamp-2">{{ $pr3->nombre }}</p>
                    <p class="text-center mb-1 flex items-center justify-center gap-1">
                        @if($pr3->precio > 0)
                            <span class="text-xl font-bold" style="color: #e97b11;">{{ $pr3->unidadDeMedida === 'unidadMilesima' ? number_format($pr3->precio, 3) : number_format($pr3->precio, 2) }}€</span>
                            @if($pr3->unidadDeMedida === 'unidad')
                                <span class="text-sm md:text-base text-gray-500">/Und.</span>
                            @elseif($pr3->unidadDeMedida === 'kilos')
                                <span class="text-sm md:text-base text-gray-500">/Kg.</span>
                            @elseif($pr3->unidadDeMedida === 'litros')
                                <span class="text-sm md:text-base text-gray-500">/L.</span>
                            @elseif($pr3->unidadDeMedida === 'unidadMilesima')
                                <span class="text-sm md:text-base text-gray-500">/Und.</span>
                            @elseif($pr3->unidadDeMedida === 'unidadUnica')
                                {{-- No mostrar sufijo para UnidadUnica --}}
                            @elseif($pr3->unidadDeMedida === '800gramos')
                                <span class="text-sm md:text-base text-gray-500">/800gr.</span>
                            @elseif($pr3->unidadDeMedida === '100ml')
                                <span class="text-sm md:text-base text-gray-500">/100ml.</span>
                            @endif
                        @else
                            <span class="text-sm font-semibold" style="color: #e97b11;">Sin Ofertas Disponibles</span>
                        @endif
                    </p>
                </a>
                @endforeach
            @endif
        </div>

            @if($pr1->count() > 12)
            <div class="text-center mt-6">
                {{-- btn-mostrar-productos -> bmp1 --}}
                <button id="bmp1" 
                        style="background-color: #70b216;"
                        class="text-white px-6 py-2 rounded-lg transition-colors"
                        onmouseover="this.style.backgroundColor='#60a013'"
                        onmouseout="this.style.backgroundColor='#70b216'">
                    Mostrar más productos
                </button>
            </div>
            @endif
        </section>
        @endif

                {{-- SECCIÓN PRECIOS HOT --}}
        {{-- $preciosHot -> $ph1, $productoHot -> $ph2 --}}
        @if(isset($ph1) && $ph1 && count($ph1->datos) > 0)
<section class="mb-8">
    <h2 class="text-2xl font-bold text-gray-800 mb-6">🔥 Precios Hot</h2>
    <div class="relative">
        <div class="flex overflow-x-auto scrollbar-visible gap-4 px-1 pb-2">
            @foreach($ph1->datos as $ph2)
            <div class="bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow duration-300 relative min-w-[320px] md:min-w-[360px] flex-shrink-0">
                <div class="p-4">
                    {{-- Badge descuento --}}
                    <span class="absolute top-2 right-2 bg-red-100 text-red-700 text-xs font-semibold px-2 py-1 rounded z-10">
        -{{ (int) floatval(str_replace(',', '.', $ph2['porcentaje_diferencia'])) }}%
    </span>

                    <div class="flex mb-3 items-start">
                        {{-- Imagen producto --}}
                        <img loading="lazy" src="{{ asset('images/' . $ph2['img_producto']) }}"
                             alt="{{ $ph2['producto_nombre'] }}"
                             class="w-20 h-20 object-contain rounded bg-gray-100 shadow-sm flex-shrink-0">

                        {{-- Logo tienda + nombre producto --}}
                        <div class="flex flex-col justify-between flex-grow h-full">
                            <div class="flex justify-center w-full">
                                <img loading="lazy" src="{{ asset('images/' . $ph2['img_tienda']) }}"
                                     alt="{{ $ph2['tienda_nombre'] }}"
                                     class="object-contain max-h-7 w-auto mb-2">
                            </div>
                            <h3 class="font-semibold text-gray-800 text-sm md:text-base leading-tight pl-3">
                                {{ $ph2['producto_nombre'] }}
                            </h3>
                        </div>
                    </div>

                    <div class="flex items-center justify-between">
                        <div>
                            <div class="flex items-baseline gap-1">
                                <p class="text-lg font-bold" style="color: #e97b11;">
                                    {{ $ph2['precio_formateado'] }}
                                </p>
                            </div>
                            @if(isset($ph2['unidades_formateadas']) && !empty($ph2['unidades_formateadas']))
                                <p class="text-sm text-gray-600">{{ $ph2['unidades_formateadas'] }}</p>
                            @endif
                        </div>
                        {{-- añadirCam() -> _f1() --}}
                        <a href="{{ _f1($ph2['url_producto']) }}" 
                               class="text-xs bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded transition">
                                Ver producto
                        </a>
                        {{-- añadirCam() -> _f1() --}}
                        <a href="{{ _f1($ph2['url_oferta']) }}"
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

        {{-- ÚLTIMOS PRODUCTOS AÑADIDOS --}}
        {{-- $ultimosProductos -> $up1, $producto -> $pr4 --}}
        @if($up1->count() > 0)
        <section class="mb-12">
            <h2 class="text-2xl font-bold text-gray-800 mb-6">Últimos Productos Añadidos</h2>
            <div class="relative">
                <div class="flex space-x-6 overflow-x-auto pb-4 scrollbar-visible">
                    @foreach($up1 as $pr4)
                    {{-- añadirCam() -> _f1() --}}
                    <a href="{{ _f1($pr4->categoria->construirUrlCategorias($pr4->slug)) }}"
                    class="group flex flex-col items-center bg-white rounded-xl shadow-md transition-all duration-300 transform hover:scale-105 hover:shadow-2xl cursor-pointer p-4 w-2/5 min-w-[160px] sm:w-1/4 sm:min-w-[200px] lg:w-1/5 lg:min-w-[220px]">
                        <div class="w-full flex justify-center mb-3">
                            @if(!empty($pr4->imagen_pequena[0] ?? ''))
                                <img loading="lazy" src="{{ asset('images/' . ($pr4->imagen_pequena[0] ?? '')) }}"
                                    alt="{{ $pr4->nombre }}"
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
                            {{ $pr4->nombre }}
                        </h3>
                        <p class="text-center mb-1 flex items-center justify-center gap-1">
                            @if($pr4->precio > 0)
                                <span class="text-xl font-bold" style="color: #e97b11;">{{ $pr4->unidadDeMedida === 'unidadMilesima' ? number_format($pr4->precio, 3) : number_format($pr4->precio, 2) }}€</span>
                                @if($pr4->unidadDeMedida === 'unidad')
                                    <span class="text-sm md:text-base text-gray-500">/Und.</span>
                                @elseif($pr4->unidadDeMedida === 'kilos')
                                    <span class="text-sm md:text-base text-gray-500">/Kg.</span>
                                @elseif($pr4->unidadDeMedida === 'litros')
                                    <span class="text-sm md:text-base text-gray-500">/L.</span>
                                @elseif($pr4->unidadDeMedida === 'unidadMilesima')
                                    <span class="text-sm md:text-base text-gray-500">/Und.</span>
                                @elseif($pr4->unidadDeMedida === 'unidadUnica')
                                    {{-- No mostrar sufijo para UnidadUnica --}}
                                @elseif($pr4->unidadDeMedida === '800gramos')
                                    <span class="text-sm md:text-base text-gray-500">/800gr.</span>
                                @elseif($pr4->unidadDeMedida === '100ml')
                                    <span class="text-sm md:text-base text-gray-500">/100ml.</span>
                                @endif
                            @else
                                <span class="text-sm font-semibold" style="color: #e97b11;">Sin Ofertas Disponibles</span>
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

    {{-- JS PARA EL MENÚ MÓVIL Y CARGA DINÁMICA --}}
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

    <script>
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
        
        {{-- escapeHtml() -> _e1() - Función para escapar HTML (protección adicional) --}}
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

        {{-- Variables para JavaScript --}}
        {{-- $esTodasCategorias -> $etc1 --}}
        @php
        $etc1 = isset($ca1) && $ca1->nombre === 'Todas las categorías';
        @endphp
        {{-- esTodasCategorias -> _etc1, btnMostrarSubcategorias -> _bms1, subcategorias-container -> sc2 --}}
        const _etc1 = {{ $etc1 ? 'true' : 'false' }};
        
        const _bms1 = document.getElementById('bms1');
        if (_bms1) {
            _bms1.addEventListener('click', function () {
                const _c2 = document.getElementById('sc2');
                
                if (!_c2) return;
                
                {{-- PASO 1: Mostrar las categorías que ya están renderizadas pero ocultas en móvil (índices 4-7) --}}
                {{-- Buscar todos los elementos hijos que tienen la clase 'hidden' --}}
                {{-- todasLasCategorias -> _tlc1, categoriasAdicionales -> _ca1, categoriasOcultas -> _co1 --}}
                const _tlc1 = Array.from(_c2.children);
                _tlc1.forEach(_ct3 => {
                    if (_ct3.classList.contains('hidden')) {
                        {{-- Remover la clase 'hidden' para mostrarlas en móvil también --}}
                        _ct3.classList.remove('hidden');
                    }
                });
                
                {{-- PASO 2: Mostrar categorías adicionales que ya están en el HTML (generadas desde PHP) --}}
                const _ca1 = _c2.querySelectorAll('.categoria-adicional');
                _ca1.forEach(_ct4 => {
                    {{-- Mostrar todas las categorías adicionales --}}
                    _ct4.style.display = 'block';
                });

                {{-- Ocultar el botón después de mostrar todas las categorías disponibles --}}
                {{-- Verificar si quedan más categorías ocultas --}}
                setTimeout(() => {
                    const _tlc2 = Array.from(_c2.children);
                    const _co1 = _tlc2.filter(_ct5 => {
                        const display = window.getComputedStyle(_ct5).display;
                        return display === 'none' || _ct5.classList.contains('hidden');
                    });
                    
                    {{-- Si no hay más categorías ocultas, ocultar el botón --}}
                    if (_co1.length === 0) {
                        _bms1.style.display = 'none';
                    }
                }, 100);
            });
        }


        {{-- Mostrar más productos usando HTML generado desde PHP (más seguro) --}}
        {{-- nombreCategoria -> _nc1, tienePadre -> _tp1, btnMostrarProductos -> _bmp1, productos-container -> pcp1 --}}
        {{-- productosAdicionales -> _pa1, botonBusquedaExistente -> _bbe1, botonBusqueda -> _bb1 --}}
        {{-- linkBusqueda -> _lb1, urlBusqueda -> _ub1, svgIcon -> _svg1, path -> _p1 --}}
        const _nc1 = @json($ca1->nombre);
        const _tp1 = {{ isset($ca1->parent_id) && $ca1->parent_id ? 'true' : 'false' }};
        const _bmp1 = document.getElementById('bmp1');
        if (_bmp1) {
            _bmp1.addEventListener('click', function () {
                const _c3 = document.getElementById('pcp1');
                
                if (!_c3) return;

                {{-- Mostrar productos adicionales que ya están en el HTML (generados desde PHP) --}}
                const _pa1 = _c3.querySelectorAll('.producto-adicional');
                _pa1.forEach(_pr5 => {
                    {{-- Verificar si está oculta por estilo inline --}}
                    const displayStyle = _pr5.style.display;
                    if (displayStyle === 'none' || !displayStyle) {
                        _pr5.style.display = 'block';
                    }
                });
                
                {{-- Ocultar el botón solo si se mostraron productos --}}
                if (_pa1.length > 0) {
                    _bmp1.style.display = 'none';
                }
                
                {{-- Solo mostrar el botón de búsqueda si la categoría tiene un padre --}}
                if (_tp1) {
                    {{-- Verificar si el botón de búsqueda ya existe para no duplicarlo --}}
                    const _bbe1 = _c3.parentNode.querySelector('.boton-busqueda-productos');
                    if (!_bbe1) {
                        {{-- Crear y añadir el nuevo botón de búsqueda usando textContent para evitar XSS --}}
                        const _bb1 = document.createElement('div');
                        _bb1.className = 'text-center mt-6 boton-busqueda-productos';
                        
                        const _lb1 = document.createElement('a');
                        const _ub1 = _f2('/buscar?q=' + encodeURIComponent(_nc1));
                        _lb1.href = _ub1;
                        _lb1.style.backgroundColor = '#70b216';
                        _lb1.className = 'text-white px-6 py-2 rounded-lg transition-colors inline-flex items-center gap-2';
                        _lb1.onmouseover = function() { this.style.backgroundColor = '#60a013'; };
                        _lb1.onmouseout = function() { this.style.backgroundColor = '#70b216'; };
                        
                        const _svg1 = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
                        _svg1.setAttribute('class', 'w-5 h-5');
                        _svg1.setAttribute('fill', 'none');
                        _svg1.setAttribute('stroke', 'currentColor');
                        _svg1.setAttribute('viewBox', '0 0 24 24');
                        const _p1 = document.createElementNS('http://www.w3.org/2000/svg', 'path');
                        _p1.setAttribute('stroke-linecap', 'round');
                        _p1.setAttribute('stroke-linejoin', 'round');
                        _p1.setAttribute('stroke-width', '2');
                        _p1.setAttribute('d', 'M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z');
                        _svg1.appendChild(_p1);
                        
                        _lb1.appendChild(_svg1);
                        _lb1.appendChild(document.createTextNode(' Buscar más productos de ' + _e1(_nc1)));
                        
                        _bb1.appendChild(_lb1);
                        
                        {{-- Insertar el botón después del contenedor de productos --}}
                        _c3.parentNode.insertBefore(_bb1, _c3.nextSibling);
                    }
                }
            });
        }
    </script>
    <x-cookie-consent />
    {{-- JS PARA EL BUSCADOR DEL HEADER --}}
@stack('scripts')
</body>
</html> 