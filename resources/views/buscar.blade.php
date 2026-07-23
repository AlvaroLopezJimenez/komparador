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
  @php
    $esPreciosHotPagina = strtolower(trim($q1X2 ?? '')) === 'precios hot';
    $tituloBusquedaVisible = $esPreciosHotPagina ? 'En mínimos históricos' : $q1X2;
  @endphp
  <title>Búsqueda: {{ $tituloBusquedaVisible }} - Komparador.com</title>
  <meta name="description" content="Resultados de búsqueda para {{ $tituloBusquedaVisible }} en Komparador.com">
  <meta property="og:title" content="Resultados para '{{ $tituloBusquedaVisible }}' - Komparador.com">
<meta property="og:description" content="Explora productos relacionados con '{{ $tituloBusquedaVisible }}' en Komparador.com.">
<meta property="og:image" content="{{ asset('images/logo.png') }}">
<meta property="og:url" content="{{ url()->current() }}">
<meta property="og:type" content="website">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="Búsqueda: {{ $tituloBusquedaVisible }}">
<meta name="twitter:description" content="Compara precios en Komparador.com para '{{ $tituloBusquedaVisible }}'.">
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
          $esPreciosHot = $esPreciosHotPagina ?? (strtolower(trim($q1X2 ?? '')) === 'precios hot');
          $esMasVendidos = in_array(strtolower(trim($q1X2 ?? '')), ['más vendidos', 'mas vendidos', 'más vendido', 'mas vendido']);
          $diasSeleccionado = $diasSeleccionado ?? 7;
          $o1 = $o1 ?? 'relevancia';
          $mostrarOrdenBusqueda = !$esPreciosHot && !$esMasVendidos && $p4X7->count() > 0;
        @endphp
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
          <div>
            <h1 class="text-xl font-bold text-gray-800">
              @if($esPreciosHot)
                🔥 En mínimos históricos
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
                Se encontraron {{ $p4X7->total() }} productos en mínimos históricos
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

          {{-- ORDENACIÓN (búsqueda normal) --}}
          @if($mostrarOrdenBusqueda)
            <div class="flex items-center gap-2 flex-wrap">
              <span class="text-sm font-medium text-gray-700">Ordenar por:</span>
              @php
                $urlOrdenRelevancia = route('buscar', array_filter(['q' => $q1X2, 'orden' => 'relevancia', 'cam' => oCV6X()]));
                $urlOrdenPrecio = route('buscar', array_filter(['q' => $q1X2, 'orden' => 'precio', 'cam' => oCV6X()]));
              @endphp
              <a href="{{ aC4X($urlOrdenRelevancia) }}"
                 class="px-4 py-2 text-sm font-semibold rounded border transition-colors {{ $o1 === 'relevancia' ? 'text-white' : 'bg-white hover:bg-green-50' }}"
                 style="{{ $o1 === 'relevancia' ? 'background-color: #5f8c21; border-color: #4d7a1a;' : 'color: #5f8c21; border-color: #5f8c21;' }}">
                Relevancia
              </a>
              <a href="{{ aC4X($urlOrdenPrecio) }}"
                 class="px-4 py-2 text-sm font-semibold rounded border transition-colors {{ $o1 === 'precio' ? 'text-white' : 'bg-white hover:bg-green-50' }}"
                 style="{{ $o1 === 'precio' ? 'background-color: #5f8c21; border-color: #4d7a1a;' : 'color: #5f8c21; border-color: #5f8c21;' }}">
                Precio
              </a>
            </div>
          @endif
        </div>
      </div>

      {{-- $p4X7 -> $productos --}}
      @php
        $esMasVendidos = $esMasVendidos ?? in_array(strtolower(trim($q1X2 ?? '')), ['más vendidos', 'mas vendidos', 'más vendido', 'mas vendido']);
        $o1 = $o1 ?? 'relevancia';
        $mostrarOrdenBusqueda = $mostrarOrdenBusqueda ?? (!$esPreciosHot && !$esMasVendidos);
      @endphp
      @if($p4X7->count() > 0)
        <div class="grid grid-cols-2 md:grid-cols-2 lg:grid-cols-4 gap-6">
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
                // Usar imagen y nombre de precios hot si están disponibles, sino del producto
                $imagenPreciosHot = $p5X8['img_producto'] ?? null;
                $nombrePreciosHot = $p5X8['producto_nombre'] ?? null;
                $nuevoHoy = !empty($p5X8['nuevo_hoy']);
                $variante = null;
                $esVariante = false;
                $numModelos = null;
              } elseif (isset($p5X8['producto']) && isset($p5X8['es_variante'])) {
                // Producto con posible variante (o general con badge de modelos)
                $producto = $p5X8['producto'];
                $variante = $p5X8['variante'] ?? null;
                $precioVariante = $p5X8['precio_variante'] ?? null;
                $porcentajeDescuento = null;
                $precioOferta = $precioVariante;
                $unidadMedida = $producto->unidadDeMedida;
                $imagenPreciosHot = null;
                $nombrePreciosHot = null;
                $nuevoHoy = false;
                $esVariante = !empty($variante);
                $numModelos = (!$esVariante && !empty($p5X8['num_modelos'])) ? (int) $p5X8['num_modelos'] : null;
                
                // Construir URL con variante
                if ($variante) {
                  $varianteSlug = \Illuminate\Support\Str::slug($variante);
                  $urlBase = $producto->categoria ? $producto->categoria->construirUrlCategorias($producto->slug) : '#';
                  $urlProducto = $urlBase . '/' . $varianteSlug;
                } else {
                  $urlProducto = $producto->categoria ? $producto->categoria->construirUrlCategorias($producto->slug) : '#';
                }
              } else {
                // Producto normal
                $producto = $p5X8;
                $porcentajeDescuento = null;
                $precioOferta = null;
                $unidadMedida = $producto->unidadDeMedida;
                $urlProducto = $producto->categoria ? $producto->categoria->construirUrlCategorias($producto->slug) : '#';
                $imagenPreciosHot = null;
                $nombrePreciosHot = null;
                $nuevoHoy = false;
                $variante = null;
                $esVariante = false;
                $numModelos = null;
              }
              
              // Obtener imagen: prioridad 1) precios hot, 2) variante de especificación (búsqueda normal), 3) imagen general
              $imagenEspecifica = $imagenPreciosHot;
              if (!$imagenEspecifica && $esVariante && !empty($p5X8['imagen_variante'] ?? null)) {
                $imagenEspecifica = $p5X8['imagen_variante'];
              }
              $imagen = $imagenEspecifica ?? (is_array($producto->imagen_pequena) 
                ? ($producto->imagen_pequena[0] ?? '') 
                : ($producto->imagen_pequena ?? ''));
              
              // Formatear precio
              $precio = $precioOferta ?: $producto->precio;
            @endphp
            {{-- aC4X -> añadirCam --}}
            <a href="{{ aC4X($urlProducto) }}" 
               class="group flex flex-col items-center bg-white rounded-xl shadow-md transition-all duration-300 transform hover:scale-105 hover:shadow-2xl cursor-pointer p-4 relative">
              {{-- Badge de descuento (solo para precios hot) --}}
              @if($esPreciosHot && $porcentajeDescuento > 0)
                <div class="absolute top-2 right-2 z-10 inline-flex items-center gap-1">
                  <div class="bg-red-500 text-white text-xs font-bold px-2 py-1 rounded-md shadow-md">
                    -{{ $porcentajeDescuento }}%
                  </div>
                  @if(!empty($nuevoHoy))
                    <span class="kk-fuego-nuevo-hoy" aria-label="Bajada de precio hoy" title="Bajada de precio hoy">🔥</span>
                  @endif
                </div>
              @endif
              <div class="w-full flex justify-center mb-3 relative">
                @if(!empty($numModelos))
                  <span class="kk-badge-modelos kk-badge-modelos--card">+{{ $numModelos }} modelos</span>
                @endif
                @if(!empty($imagen))
                    <img loading="lazy" src="{{ asset('images/' . $imagen) }}" 
                         alt="{{ $producto->nombre }}" 
                         class="w-32 h-32 object-contain rounded-lg shadow-sm transition-all duration-300 group-hover:shadow-lg bg-gray-100">
                @else
                    <div class="w-32 h-32 flex items-center justify-center rounded-lg bg-gradient-to-br from-blue-100 to-blue-200">
                        <svg class="w-12 h-12 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                @endif
              </div>
              @php
                // Construir nombre: priorizar nombre de precios hot si existe
                $nombreMostrar = $nombrePreciosHot ?? $producto->nombre;
                if ($esVariante && $variante) {
                  $partesNombre = [];
                  if (!empty($producto->marca)) {
                    $partesNombre[] = $producto->marca;
                  }
                  if (!empty($producto->modelo)) {
                    $partesNombre[] = $producto->modelo;
                  }
                  if (!empty($variante)) {
                    $partesNombre[] = $variante;
                  }
                  $nombreMostrar = !empty($partesNombre) ? implode(' ', $partesNombre) : ($nombrePreciosHot ?? $producto->nombre);
                }
              @endphp
              <p class="font-semibold text-gray-700 text-center text-sm mb-1 line-clamp-2">{{ $nombreMostrar }}</p>
              <p class="text-center mb-1 flex items-center justify-center gap-1">
                @if($precio > 0)
                  <span class="text-xl font-bold" style="color: #e97b11;">{{ $unidadMedida === 'unidadMilesima' ? number_format($precio, 3) : number_format($precio, 2) }}€</span>
                  @if($unidadMedida === 'unidad')
                    <span class="text-sm md:text-base text-gray-500">/Und.</span>
                  @elseif($unidadMedida === 'kilos')
                    <span class="text-sm md:text-base text-gray-500">/Kg.</span>
                  @elseif($unidadMedida === 'litros')
                    <span class="text-sm md:text-base text-gray-500">/L.</span>
                  @elseif($unidadMedida === 'unidadMilesima')
                    <span class="text-sm md:text-base text-gray-500">/Und.</span>
                  @elseif($unidadMedida === 'unidadUnica')
                    {{-- No mostrar sufijo para UnidadUnica --}}
                  @elseif($unidadMedida === '800gramos')
                    <span class="text-sm md:text-base text-gray-500">/800gr.</span>
                  @elseif($unidadMedida === '100ml')
                    <span class="text-sm md:text-base text-gray-500">/100ml.</span>
                  @endif
                @else
                  <span class="text-sm font-semibold" style="color: #e97b11;">Sin Ofertas Disponibles</span>
                @endif
              </p>
            </a>
          @endforeach
        </div>

        <div class="mt-8">
          {{-- $p4X7 -> $productos, $q1X2 -> $query, oCV6X -> obtenerCamValidado --}}
          @php
            $paramsPaginacion = array_filter([
              'q' => $q1X2, 
              'cam' => oCV6X(),
              'dias' => ($esMasVendidos && isset($diasSeleccionado)) ? $diasSeleccionado : null,
              'orden' => ($mostrarOrdenBusqueda && ($o1 ?? 'relevancia') !== 'relevancia') ? $o1 : null,
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
<style>
    .line-clamp-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    .kk-fuego-nuevo-hoy {
        display: inline-block;
        font-size: 0.95em;
        line-height: 1;
        transform-origin: 50% 85%;
        animation: kkFuegoFlicker 0.55s ease-in-out infinite alternate;
        filter: drop-shadow(0 0 2px rgba(255, 200, 50, 0.85));
    }
    @keyframes kkFuegoFlicker {
        0% {
            transform: scale(1) rotate(-6deg) translateY(0);
            opacity: 0.92;
        }
        40% {
            transform: scale(1.12) rotate(4deg) translateY(-1px);
            opacity: 1;
        }
        100% {
            transform: scale(1.05) rotate(-3deg) translateY(-0.5px);
            opacity: 0.96;
        }
    }
    @media (prefers-reduced-motion: reduce) {
        .kk-fuego-nuevo-hoy {
            animation: none;
        }
    }
</style>
</body>

</html>