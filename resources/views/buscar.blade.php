<!DOCTYPE html>
<html lang="es">

<head>
@php
    // $cU3 -> $canonicalUrl
    $cU3 = url()->current();
    // $u1X -> $u
    $u1X = parse_url($cU3);
    $u1X['host'] = 'komparador.com';
    $cU3 =
        ($u1X['scheme'] ?? 'https') . '://' .
        $u1X['host'] .
        ($u1X['path'] ?? '') .
        (isset($u1X['query']) ? '?' . $u1X['query'] : '');
@endphp
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  {{-- $cU3 -> $canonicalUrl --}}
  <link rel="canonical" href="{{ $cU3 }}">
  {{-- $q1X2 -> $query --}}
  <title>Búsqueda: {{ $q1X2 }} - Komparador.com</title>
  {{-- $q1X2 -> $query --}}
  <meta name="description" content="Resultados de búsqueda para {{ $q1X2 }} en Komparador.com">
  {{-- $q1X2 -> $query --}}
  <meta property="og:title" content="Resultados para '{{ $q1X2 }}' - Komparador.com">
  {{-- $q1X2 -> $query --}}
<meta property="og:description" content="Explora productos relacionados con '{{ $q1X2 }}' en Komparador.com.">
<meta property="og:image" content="{{ asset('images/logo.png') }}">
<meta property="og:url" content="{{ url()->current() }}">
<meta property="og:type" content="website">
<meta name="twitter:card" content="summary_large_image">
  {{-- $q1X2 -> $query --}}
<meta name="twitter:title" content="Búsqueda: {{ $q1X2 }}">
  {{-- $q1X2 -> $query --}}
<meta name="twitter:description" content="Compara precios en Komparador.com para '{{ $q1X2 }}'.">
<meta name="twitter:image" content="{{ asset('images/logo.png') }}">
    <link rel="icon" type="image/png" href="{{ asset('images/icono.webp') }}">  
@vite(['resources/css/app.css', 'resources/js/app.js'])

    @if (env('GA_MEASUREMENT_ID'))
        <script async src="https://www.googletagmanager.com/gtag/js?id={{ env('GA_MEASUREMENT_ID') }}"></script>
        <script>
          window.dataLayer = window.dataLayer || [];
          function gtag(){ dataLayer.push(arguments); }
    
          gtag('consent', 'default', {
            'ad_storage': 'denied',
            'analytics_storage': 'denied',
            'ad_user_data': 'denied',
            'ad_personalization': 'denied',
            'url_passthrough': true
          });
    
          (function applyStoredConsent(){
            try {
              var m = document.cookie.match(/(?:^|; )cookie_consent=([^;]+)/);
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
    
          window.addEventListener('cookie-consent-changed', function(){
            try {
              var m = document.cookie.match(/(?:^|; )cookie_consent=([^;]+)/);
              var c = m ? JSON.parse(decodeURIComponent(m[1])) : {};
              gtag('consent', 'update', {
                'analytics_storage': c.analytics ? 'granted' : 'denied',
                'ad_storage':       c.marketing ? 'granted' : 'denied',
                'ad_user_data':     c.marketing ? 'granted' : 'denied',
                'ad_personalization': c.marketing ? 'granted' : 'denied'
              });
            } catch(e){}
          });
    
          gtag('js', new Date());
          gtag('config', '{{ env('GA_MEASUREMENT_ID') }}', {
            'transport_type': 'beacon'
          });
        </script>
    @endif
</head>

<body class="bg-gray-50">
@php
// aC4X -> añadirCam
if (!function_exists('aC4X')) {
    function aC4X($uL2X) {
        // $uL2X -> $url
        if (!request()->has('cam')) {
            return $uL2X;
        }
        
        // $cA3X -> $cam
        $cA3X = request('cam');
        
        if (!preg_match('/^[a-zA-Z0-9\-_]+$/', $cA3X)) {
            return $uL2X;
        }
        
        // $sE5X -> $separator
        $sE5X = strpos($uL2X, '?') !== false ? '&' : '?';
        return $uL2X . $sE5X . 'cam=' . urlencode($cA3X);
    }
}

// oCV6X -> obtenerCamValidado
if (!function_exists('oCV6X')) {
    function oCV6X() {
        if (!request()->has('cam')) {
            return null;
        }
        
        // $cA3X -> $cam
        $cA3X = request('cam');
        
        if (!preg_match('/^[a-zA-Z0-9\-_]+$/', $cA3X)) {
            return null;
        }
        
        return $cA3X;
    }
}
@endphp
  <x-header />
<main class="max-w-7xl mx-auto flex flex-col gap-6 py-4 px-4 bg-gray-100">

      <div class="mb-4">
        <h1 class="text-xl font-bold text-gray-800">
  {{-- $q1X2 -> $query --}}
  Resultados para "{{ $q1X2 }}"
</h1>
        <p class="text-gray-600">
          {{-- $p4X7 -> $productos --}}
          Se encontraron {{ $p4X7->total() }} productos para "{{ $q1X2 }}"
        </p>
      </div>

      {{-- $p4X7 -> $productos --}}
      @if($p4X7->count() > 0)
        <div class="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
          {{-- $p4X7 -> $productos, $p5X8 -> $producto --}}
          @foreach($p4X7 as $p5X8)
            {{-- aC4X -> añadirCam, $p5X8 -> $producto --}}
            <a href="{{ aC4X($p5X8->categoria->construirUrlCategorias($p5X8->slug)) }}" class="bg-white rounded-lg shadow p-4 card-hover hover:shadow-md">
              <div class="flex justify-center mb-3">
                {{-- $p5X8 -> $producto --}}
                @if(!empty($p5X8->imagen_pequena[0] ?? ''))
                    {{-- $p5X8 -> $producto --}}
                    <img loading="lazy" src="{{ asset('images/' . ($p5X8->imagen_pequena[0] ?? '')) }}" 
                         {{-- $p5X8 -> $producto --}}
                         alt="{{ $p5X8->nombre }}" 
                         class="w-24 h-24 object-contain">
                @else
                    <div class="w-24 h-24 flex items-center justify-center rounded-lg bg-gradient-to-br from-blue-100 to-blue-200">
                        <svg class="w-12 h-12 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                @endif
              </div>
              <div class="text-center">
                <h3 class="font-semibold text-gray-800 mb-1">
                  {{-- $p5X8 -> $producto --}}
                  {{ Str::limit($p5X8->nombre, 50) }}
                </h3>
                <p class="text-center mb-1">
    <span class="text-xs text-gray-500">Desde:</span>
    {{-- $p5X8 -> $producto --}}
<span class="text-xl font-bold text-pink-600">{{ number_format($p5X8->precio, 2) }}€
    {{-- $p5X8 -> $producto --}}
    @if($p5X8->unidadDeMedida === 'unidad')
        <span class="text-xs text-gray-500">/Und.</span>
    {{-- $p5X8 -> $producto --}}
    @elseif($p5X8->unidadDeMedida === 'kilos')
        <span class="text-xs text-gray-500">/kg.</span>
    {{-- $p5X8 -> $producto --}}
    @elseif($p5X8->unidadDeMedida === 'litros')
        <span class="text-xs text-gray-500">/L.</span>
    @endif
</span>
</p>
              </div>
            </a>
          @endforeach
        </div>

        <div class="mt-8">
          {{-- $p4X7 -> $productos, $q1X2 -> $query, oCV6X -> obtenerCamValidado --}}
          {{ $p4X7->appends(array_filter(['q' => $q1X2, 'cam' => oCV6X()]))->links() }}
        </div>
      @else
        <div class="text-center py-8">
          <div class="text-gray-500 mb-4">
            <svg class="w-16 h-16 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
            </svg>
            <h3 class="text-lg font-semibold mb-2">No se encontraron resultados</h3>
            <p class="text-gray-600">Intenta con otros términos de búsqueda</p>
          </div>
        </div>
      @endif
    </div>
  </div>
</main>
  <x-footer />
<x-cookie-consent />
@stack('scripts')
</body>

</html>