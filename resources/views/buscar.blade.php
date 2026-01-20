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
        @php
          $esPreciosHot = strtolower(trim($q1X2 ?? '')) === 'precios hot';
          $esMasVendidos = in_array(strtolower(trim($q1X2 ?? '')), ['más vendidos', 'mas vendidos', 'más vendido', 'mas vendido']);
          $diasSeleccionado = $diasSeleccionado ?? 7;
        @endphp
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
          <div>
            <h1 class="text-xl font-bold text-gray-800">
              @if($esPreciosHot)
                🔥 Precios Hot
              @elseif($esMasVendidos)
                + Vendidos
              @else
                {{-- $q1X2 -> $query --}}
                Resultados para "{{ $q1X2 }}"
              @endif
            </h1>
            <p class="text-gray-600">
              {{-- $p4X7 -> $productos --}}
              @if($esPreciosHot)
                Se encontraron {{ $p4X7->total() }} productos con los mejores descuentos
              @elseif($esMasVendidos)
                Se encontraron {{ $p4X7->total() }} productos más vendidos
              @else
                Se encontraron {{ $p4X7->total() }} productos para "{{ $q1X2 }}"
              @endif
            </p>
          </div>
          
          {{-- FILTROS DE DÍAS (solo para más vendidos) --}}
          @if($esMasVendidos)
            <div class="flex gap-2">
              @php
                $diasOpciones = [
                  1 => 'Hoy',
                  7 => '7 días',
                  30 => '30 días'
                ];
              @endphp
              @foreach($diasOpciones as $dias => $label)
                @php
                  $url = route('buscar', array_filter(['q' => 'más vendidos', 'dias' => $dias, 'cam' => oCV6X()]));
                @endphp
                <a href="{{ aC4X($url) }}" 
                   class="px-4 py-2 text-sm font-semibold rounded border transition-colors {{ $diasSeleccionado == $dias ? 'text-white' : 'bg-white hover:bg-green-50' }}"
                   style="{{ $diasSeleccionado == $dias ? 'background-color: #73b112; border-color: #5f8c21;' : 'color: #73b112; border-color: #73b112;' }}">
                  {{ $label }}
                </a>
              @endforeach
            </div>
          @endif
        </div>
      </div>

      {{-- $p4X7 -> $productos --}}
      @php
        // Detectar si es búsqueda de precios hot o más vendidos
        $esPreciosHot = strtolower(trim($q1X2 ?? '')) === 'precios hot';
        $esMasVendidos = in_array(strtolower(trim($q1X2 ?? '')), ['más vendidos', 'mas vendidos', 'más vendido', 'mas vendido']);
      @endphp
      @if($p4X7->count() > 0)
        <div class="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
          {{-- $p4X7 -> $productos, $p5X8 -> $producto o $item --}}
          @foreach($p4X7 as $p5X8)
            @php
              // Si es precios hot, el item tiene estructura diferente
              if ($esPreciosHot && isset($p5X8['producto'])) {
                $producto = $p5X8['producto'];
                $porcentajeDescuento = $p5X8['porcentaje_diferencia'] ?? 0;
                $precioOferta = $p5X8['precio_oferta'] ?? 0;
                $unidadMedida = $p5X8['unidad_medida'] ?? $producto->unidadDeMedida;
                $urlProducto = $p5X8['url_producto'] ?? ($producto->categoria ? $producto->categoria->construirUrlCategorias($producto->slug) : '#');
              } else {
                $producto = $p5X8;
                $porcentajeDescuento = null;
                $precioOferta = null;
                $unidadMedida = $producto->unidadDeMedida;
                $urlProducto = $producto->categoria ? $producto->categoria->construirUrlCategorias($producto->slug) : '#';
              }
              
              // Obtener imagen
              $imagen = is_array($producto->imagen_pequena) 
                ? ($producto->imagen_pequena[0] ?? '') 
                : ($producto->imagen_pequena ?? '');
              
              // Formatear precio
              $precio = $precioOferta ?: $producto->precio;
              $precioFormateado = $unidadMedida === 'unidadMilesima' 
                ? number_format($precio, 3, ',', '.')
                : number_format($precio, 2, ',', '.');
              
              // Sufijo unidad de medida
              $sufijo = match($unidadMedida) {
                'unidad' => '/Und.',
                'kilos' => '/Kg.',
                'litros' => '/L.',
                'unidadMilesima' => '/Und.',
                'unidadUnica' => '',
                '800gramos' => '/800gr.',
                '100ml' => '/100ml.',
                default => ''
              };
            @endphp
            {{-- aC4X -> añadirCam --}}
            <a href="{{ aC4X($urlProducto) }}" class="relative bg-white rounded-lg shadow p-4 card-hover hover:shadow-md">
              {{-- Badge de descuento (solo para precios hot) --}}
              @if($esPreciosHot && $porcentajeDescuento > 0)
                <div class="absolute top-2 right-2 bg-red-500 text-white text-xs font-bold px-2 py-1 rounded-md shadow-md z-10">
                  -{{ $porcentajeDescuento }}%
                </div>
              @endif
              <div class="flex justify-center mb-3">
                @if(!empty($imagen))
                    <img loading="lazy" src="{{ asset('images/' . $imagen) }}" 
                         alt="{{ $producto->nombre }}" 
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
                  {{ Str::limit($producto->nombre, 50) }}
                </h3>
                <p class="text-center mb-1">
                  <span class="text-xs text-gray-500">Desde:</span>
                  <span class="text-xl font-bold" style="color: #e97b11;">
                    {{ $precioFormateado }}€
                    @if($sufijo)
                      <span class="text-xs text-gray-500">{{ $sufijo }}</span>
                    @endif
                  </span>
                </p>
              </div>
            </a>
          @endforeach
        </div>

        <div class="mt-8">
          {{-- $p4X7 -> $productos, $q1X2 -> $query, oCV6X -> obtenerCamValidado --}}
          @php
            $paramsPaginacion = array_filter([
              'q' => $q1X2, 
              'cam' => oCV6X(),
              'dias' => ($esMasVendidos && isset($diasSeleccionado)) ? $diasSeleccionado : null
            ]);
          @endphp
          {{ $p4X7->appends($paramsPaginacion)->links() }}
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