<!DOCTYPE html>
<html lang="es">
<head>
@php
    // _f3() -> construirUrlFiltros()
    // Función helper para construir URL con filtros
    if (!function_exists('_f3')) {
        function _f3($categoriaSlug, $filtrosAplicados, $precioMin = null, $precioMax = null, $orden = 'relevancia', $filtrosImportantes = [], $rebajado = false) {
        $segmentos = [];
        
        // Crear mapa de IDs a slugs
        $mapaIdsASlugs = [];
        foreach ($filtrosImportantes as $filtro) {
            foreach ($filtro['subprincipales'] ?? [] as $sub) {
                $slug = $sub['slug'] ?? \Illuminate\Support\Str::slug($sub['texto'] ?? '');
                $mapaIdsASlugs[$sub['id']] = $slug;
            }
        }
        
        // Agrupar filtros por línea principal
        foreach ($filtrosAplicados as $lineaId => $sublineasIds) {
            if ($lineaId === 'precio_min' || $lineaId === 'precio_max') continue;
            if (empty($sublineasIds) || !is_array($sublineasIds)) continue;
            
            // Eliminar IDs duplicados primero
            $sublineasIds = array_values(array_unique($sublineasIds));
            
            $slugs = [];
            foreach ($sublineasIds as $sublineaId) {
                if (isset($mapaIdsASlugs[$sublineaId])) {
                    $slug = $mapaIdsASlugs[$sublineaId];
                    // Evitar duplicados
                    if (!in_array($slug, $slugs)) {
                        $slugs[] = $slug;
                    }
                }
            }
            
            if (!empty($slugs)) {
                // Si hay múltiples valores únicos, unirlos: talla-1-talla-2
                // Si solo hay uno, ponerlo directamente sin duplicar
                $segmentos[] = implode('-', $slugs);
            }
        }
        
        // Agregar precio si existe
        if ($precioMin !== null && $precioMax !== null && $precioMin > 0 && $precioMax < 999999) {
            $segmentos[] = "precio-{$precioMin}-{$precioMax}";
        }
        
        // Construir URL base
        $url = "/categoria/{$categoriaSlug}";
        if (!empty($segmentos)) {
            $url .= '/' . implode('/', $segmentos);
        }
        
        // Agregar parámetros adicionales
        $queryParts = [];
        if ($orden && $orden !== 'relevancia') {
            $queryParts[] = 'orden=' . urlencode($orden);
        }
        if ($rebajado) {
            $queryParts[] = 'rebajado';
        }
        
        if (!empty($queryParts)) {
            $url .= '?' . implode('&', $queryParts);
        }
        
        return $url;
        }
    }

    // _f1() -> añadirCam()
    // Función helper para añadir parámetro cam a URLs en PHP de forma segura
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
    
    // _f2() -> añadirCamJS()
    // Alias para mantener compatibilidad con código existente
    if (!function_exists('_f2')) {
        function _f2($url) {
            return _f1($url);
        }
    }

    // _f4() -> construirQueryParamsFiltros()
    // Función helper para construir query params de filtros aplicados
    if (!function_exists('_f4')) {
        function _f4($filtrosAplicados, $precioMin = null, $precioMax = null, $filtrosImportantes = []) {
            $params = [];
            
            // Crear mapa de IDs a slugs
            $mapaIdsASlugs = [];
            foreach ($filtrosImportantes as $filtro) {
                foreach ($filtro['subprincipales'] ?? [] as $sub) {
                    $slug = $sub['slug'] ?? \Illuminate\Support\Str::slug($sub['texto'] ?? '');
                    $mapaIdsASlugs[$sub['id']] = $slug;
                }
            }
            
            // Agrupar filtros por línea principal y convertir a slugs
            foreach ($filtrosAplicados as $lineaId => $sublineasIds) {
                if ($lineaId === 'precio_min' || $lineaId === 'precio_max') continue;
                if (empty($sublineasIds) || !is_array($sublineasIds)) continue;
                
                // Eliminar IDs duplicados
                $sublineasIds = array_values(array_unique($sublineasIds));
                
                $slugs = [];
                foreach ($sublineasIds as $sublineaId) {
                    if (isset($mapaIdsASlugs[$sublineaId])) {
                        $slug = $mapaIdsASlugs[$sublineaId];
                        if (!in_array($slug, $slugs)) {
                            $slugs[] = $slug;
                        }
                    }
                }
                
                if (!empty($slugs)) {
                    // Si hay múltiples valores, unirlos: talla-1-talla-2
                    // Si solo hay uno, ponerlo directamente
                    // Usar formato corto 'f' + lineaId (donde lineaId puede ser cualquier formato)
                    // Ejemplos: f123, fid_123_abc, f1767459951337_fe5eebb9, etc.
                    // El formato del ID no importa, solo se usa como identificador único
                    $params['f' . $lineaId] = implode('-', $slugs);
                }
            }
            
            // No agregar precio en los enlaces de productos ya que no se filtra por precio dentro de los productos
            
            return $params;
        }
    }

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
    <title>{{ $ca1->nombre }} - Komparador.com</title>
    <meta name="description" content="Descubre productos en la categoría {{ $ca1->nombre }} al mejor precio. Compara ofertas y encuentra más en Komparador.com.">
    {{-- Icono --}}
    <link rel="icon" type="image/png" href="{{ asset('images/icono.webp') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- SCRIPT PARA GOOGLE ANALYTICS--}}
    @if (env('GA_MEASUREMENT_ID'))
        {{-- Google tag (gtag.js) --}}
        <script async src="https://www.googletagmanager.com/gtag/js?id={{ env('GA_MEASUREMENT_ID') }}"></script>
        <script>
          {{-- Consent Mode + GA4 integrado con tu c_c --}}
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
          (function applyStoredConsent(){
            try {
              var m = document.cookie.match(/(?:^|; )c_c=([^;]+)/);
              if (!m) return;
              var c = JSON.parse(decodeURIComponent(m[1]));
              {{-- analytics → analytics_storage --}}
              {{-- marketing → ad_storage / ad_user_data / ad_personalization --}}
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
        // $ipAutorizada -> $ip1
        // $ipActual -> $ip2
        // $habilitar -> $h1
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
    
    {{-- HEADER DESDE COMPONENTS/HEADER --}}
    <x-header />

    {{-- CONTENIDO PRINCIPAL --}}
    <main class="max-w-7xl mx-auto px-4 sm:px-6 py-2 bg-gray-100">
        
        {{-- BREADCRUMB --}}
        <nav class="mb-2">
            <ol class="flex items-center space-x-2 text-sm text-gray-600">
                <li>
                    <a href="{{ _f1(route('home')) }}" class="hover:text-pink-600">Inicio</a>
                </li>
                @foreach($b1 as $it)
                <li class="flex items-center">
                    <svg class="w-4 h-4 mx-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                    <a href="{{ _f1($it['url']) }}" class="hover:text-pink-600">{{ $it['nombre'] }}</a>
                </li>
                @endforeach
            </ol>
        </nav>

        {{-- TÍTULO Y CONTADOR --}}
        <div class="mb-4 bg-gray-200 px-4 py-3 rounded">
            <div class="flex items-center gap-3 flex-wrap">
                <h1 class="text-2xl sm:text-3xl font-bold text-gray-800">{{ $ca1->nombre }}</h1>
                <p class="text-gray-600"><span id="cp1">{{ $pr1->total() }}</span> productos encontrados</p>
            </div>
        </div>

        {{-- BOTÓN VER FILTROS (solo móvil/tablet) --}}
        @if($fi1->count() > 0)
        <div class="lg:hidden mb-4">
            <button type="button" 
                    id="bvf1" 
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg transition-colors">
                Ver filtros
            </button>
        </div>
        @endif

        {{-- LAYOUT CON SIDEBAR Y MAIN --}}
        <div class="flex flex-col lg:flex-row gap-6">
            {{-- SIDEBAR DE FILTROS (izquierda en escritorio, oculto en móvil) --}}
            @if($fi1->count() > 0)
            <aside class="hidden lg:block lg:w-1/4 lg:flex-shrink-0">
                <div class="bg-white rounded-lg shadow-md p-4 lg:sticky lg:top-4">
                    <h2 class="text-lg font-bold text-gray-900 mb-4 pb-2 border-b border-gray-200">Filtros</h2>
                    <form method="GET" action="{{ route('categoria.show', $ca1->slug) }}" id="ffs1" class="space-y-4">
                        {{-- FILTRO DE RANGO DE PRECIOS --}}
                        @php
                            // $precioMinActual -> $pma1
                            // $precioMaxActual -> $pma2
                            // $precioMin -> $pm1
                            // $precioMax -> $pm2
                            // $precioMinGlobal -> $pmg1
                            // $precioMaxGlobal -> $pmg2
                            $pma1 = $pm1 ?? $pmg1;
                            $pma2 = $pm2 ?? $pmg2;
                        @endphp
                        <div class="filtro-precio border border-gray-200 rounded-lg p-3 bg-gray-50">
                            <div class="font-bold text-base text-gray-900 mb-3 pb-2 border-b border-gray-300" style="color: #111827 !important; font-weight: bold !important;">
                                Rango de Precios
                            </div>
                            <div class="precio-slider-container">
                                <div class="flex items-center justify-between mb-3">
                                    <div class="flex items-center gap-1">
                                        <input type="number" 
                                               id="pmi1" 
                                               class="w-20 px-2 py-1 border border-gray-300 rounded text-sm"
                                               step="0.01"
                                               min="{{ $pmg1 }}"
                                               max="{{ $pmg2 }}"
                                               value="{{ $pma1 }}">
                                        <span class="text-gray-500 text-sm">€</span>
                                    </div>
                                    <span class="text-gray-500">-</span>
                                    <div class="flex items-center gap-1">
                                        <input type="number" 
                                               id="pmx1" 
                                               class="w-20 px-2 py-1 border border-gray-300 rounded text-sm"
                                               step="0.01"
                                               min="{{ $pmg1 }}"
                                               max="{{ $pmg2 }}"
                                               value="{{ $pma2 }}">
                                        <span class="text-gray-500 text-sm">€</span>
                                    </div>
                                </div>
                                <div class="relative h-2 bg-gray-200 rounded-full" id="pst1">
                                    <div class="absolute h-2 bg-gray-200 rounded-full w-full"></div>
                                    <div class="absolute h-2 bg-blue-500 rounded-full" id="psa1"></div>
                                    <input type="range" 
                                           id="pms1" 
                                           class="absolute w-full h-0 bg-transparent appearance-none cursor-pointer z-20"
                                           min="0"
                                           max="100"
                                           step="0.01">
                                <input type="range" 
                                       id="pmxs1" 
                                       class="absolute w-full h-0 bg-transparent appearance-none cursor-pointer z-20"
                                       min="{{ $pmg1 }}"
                                       max="{{ $pmg2 }}"
                                       step="0.01"
                                       value="{{ $pma2 }}">
                                </div>
                                <button type="button" 
                                        id="bap1" 
                                        class="mt-3 w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg transition-colors">
                                    Aplicar precio
                                </button>
                            </div>
                        </div>
                        {{-- FILTRO DE REBAJADO --}}
                        <div class="filtro-rebajado-container relative overflow-hidden rounded-lg" style="border: 1px solid transparent; padding: 1px; background: linear-gradient(45deg, #ef4444, #fbbf24, #dc2626, #fcd34d, #b91c1c, #f59e0b, #991b1b, #fde68a, #7f1d1d, #fef3c7, #f87171, #fecaca, #fca5a5, #fee2e2); background-size: 400% 400%; animation: rebajadoBorder 40s ease-in-out infinite; box-shadow: 0 0 8px rgba(239, 68, 68, 0.25);">
                            <div class="bg-white rounded-md px-3 py-2">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input 
                                        type="checkbox"
                                        id="crs1"
                                        class="filtro-rebajado-checkbox w-5 h-5 text-red-600 border-red-400 rounded focus:ring-red-500 focus:ring-2"
                                        {{ $r1 ?? false ? 'checked' : '' }}
                                        onchange="_afr1(this)">
                                    <span class="text-base font-bold text-gray-800">Rebajado</span>
                                </label>
                            </div>
                        </div>
                        @foreach($fi1 as $index => $filtro)
                            @php
                                $esColapsable = $index >= 4; // A partir del 5º filtro (índice 4)
                                $claseColapsado = $esColapsable ? 'filtro-colapsado' : '';
                            @endphp
                            <div class="filtro-linea-principal border border-gray-200 rounded-lg p-3 bg-gray-50 {{ $claseColapsado }}" data-linea-id="{{ $filtro['id'] }}" data-index="{{ $index }}">
                                <div class="filtro-titulo-header font-bold text-base text-gray-900 pb-2 border-b border-gray-300 flex items-center justify-between cursor-pointer hover:bg-gray-100 px-2 py-2 -mx-2 rounded transition-colors {{ $esColapsable ? 'mb-0' : 'mb-3' }}" style="color: #111827 !important; font-weight: bold !important;" data-linea-id="{{ $filtro['id'] }}">
                                    <span>{{ $filtro['texto'] }}</span>
                                    <div class="flex items-center gap-2">
                                        <span class="contador-linea-principal text-sm font-normal text-gray-600" data-linea-id="{{ $filtro['id'] }}">({{ $filtro['contador'] ?? 0 }})</span>
                                        @if($esColapsable)
                                        <svg class="filtro-flecha w-5 h-5 text-gray-600 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                        </svg>
                                        @endif
                                    </div>
                                </div>
                                <div class="filtro-contenido {{ $esColapsable ? 'hidden' : '' }}">
                                @php
                                    $totalSublineas = count($filtro['subprincipales']);
                                    $mostrarPrimeras = min(5, $totalSublineas);
                                    $tieneMasDe5 = $totalSublineas > 5;
                                    $tieneMasDe8 = $totalSublineas > 8;
                                @endphp
                                <div class="sublineas-container space-y-2 mt-2" 
                                     data-linea-id="{{ $filtro['id'] }}"
                                     data-total="{{ $totalSublineas }}"
                                     data-mostrando="{{ $mostrarPrimeras }}"
                                     data-tiene-mas-de-8="{{ $tieneMasDe8 ? 'true' : 'false' }}">
                                    @foreach($filtro['subprincipales'] as $index => $sub)
                                        @php
                                            $checkboxId = 'checkbox_' . $filtro['id'] . '_' . $sub['id'];
                                            $mostrarItem = $index < $mostrarPrimeras || !$tieneMasDe5;
                                            // Verificar si este filtro está aplicado
                                            // $filtrosAplicados -> $fa1
                                            $slugSublinea = $sub['slug'] ?? \Illuminate\Support\Str::slug($sub['texto'] ?? '');
                                            $estaSeleccionado = false;
                                            if (isset($fa1[$filtro['id']]) && is_array($fa1[$filtro['id']])) {
                                                $estaSeleccionado = in_array($sub['id'], $fa1[$filtro['id']]);
                                            }
                                            $contador = $sub['contador'] ?? 0;
                                            $estaDeshabilitado = $contador === 0;
                                        @endphp
                                        <label class="filtro-sublinea-label flex items-center gap-2 {{ $estaDeshabilitado ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer hover:bg-white' }} px-2 py-2 rounded transition-colors border border-transparent hover:border-gray-300 {{ !$mostrarItem ? 'hidden' : '' }}" 
                                               for="{{ $checkboxId }}"
                                               data-linea-id="{{ $filtro['id'] }}"
                                               data-sublinea-id="{{ $sub['id'] }}"
                                               data-sublinea-slug="{{ $slugSublinea }}"
                                               data-index="{{ $index }}">
                                            <input 
                                                type="checkbox"
                                                id="{{ $checkboxId }}"
                                                class="filtro-sublinea-checkbox w-4 h-4 text-blue-600 border-gray-400 rounded focus:ring-blue-500 focus:ring-2"
                                                data-linea-id="{{ $filtro['id'] }}"
                                                data-sublinea-id="{{ $sub['id'] }}"
                                                data-sublinea-slug="{{ $slugSublinea }}"
                                                data-sublinea-texto="{{ htmlspecialchars($sub['texto'], ENT_QUOTES, 'UTF-8') }}"
                                                {{ $estaSeleccionado ? 'checked' : '' }}
                                                {{ $estaDeshabilitado ? 'disabled' : '' }}
                                                @if(!$estaDeshabilitado)
                                                onchange="_af1(this)"
                                                @endif>
                                            <span class="filtro-sublinea-texto text-sm {{ $estaDeshabilitado ? 'text-gray-400' : 'text-gray-800' }} font-medium">{{ $sub['texto'] }}</span>
                                            <span class="contador-sublinea text-xs text-gray-500 ml-auto" data-linea-id="{{ $filtro['id'] }}" data-sublinea-id="{{ $sub['id'] }}">({{ $contador }})</span>
                                        </label>
                                    @endforeach
                                </div>
                            @if($tieneMasDe5)
                            <button type="button" 
                                    class="btn-mostrar-mas text-sm text-blue-600 hover:text-blue-800 font-medium py-1 transition-colors cursor-pointer mt-2"
                                    data-linea-id="{{ $filtro['id'] }}"
                                    style="padding-left: 0.5rem; padding-right: 0.5rem;">
                                Mostrar más
                            </button>
                            @endif
                            @if($tieneMasDe8)
                            <div class="indicador-scroll text-xs text-gray-500 mt-1 hidden lg:hidden" 
                                 data-indicador-linea="{{ $filtro['id'] }}"
                                 style="padding-left: 0.5rem; padding-right: 0.5rem;">
                                <span class="inline-flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                    Desliza para ver más opciones
                                </span>
                            </div>
                            @endif
                                </div>
                            </div>
                    @endforeach
                    </form>
                </div>
            </aside>
            @endif

            {{-- MAIN CON PRODUCTOS --}}
            <main class="flex-1 lg:w-3/4">
                {{-- BOTONES DE ORDENACIÓN --}}
                <div class="flex justify-end gap-2 mb-4">
                    @php
                        // $urlRelevancia -> $ur1
                        // $urlPrecio -> $up1
                        // $urlRebajado -> $ur2
                        // $orden -> $o1
                        // construirUrlFiltros() -> _f3()
                        $ur1 = _f3($ca1->slug, $fa1 ?? [], $pm1 ?? null, $pm2 ?? null, 'relevancia', $fi1->toArray(), $r1 ?? false);
                        $up1 = _f3($ca1->slug, $fa1 ?? [], $pm1 ?? null, $pm2 ?? null, 'precio', $fi1->toArray(), $r1 ?? false);
                        $ur2 = _f3($ca1->slug, $fa1 ?? [], $pm1 ?? null, $pm2 ?? null, 'rebajado', $fi1->toArray(), $r1 ?? false);
                    @endphp
                    <a href="{{ _f2($ur1) }}" 
                       class="px-4 py-2 text-sm font-semibold rounded border transition-colors {{ ($o1 ?? 'relevancia') === 'relevancia' ? 'bg-blue-500 text-white border-blue-600' : 'bg-white text-blue-500 border-blue-500 hover:bg-blue-50' }}">
                        Relevancia
                    </a>
                    <a href="{{ _f2($up1) }}" 
                       class="px-4 py-2 text-sm font-semibold rounded border transition-colors {{ ($o1 ?? 'relevancia') === 'precio' ? 'bg-blue-500 text-white border-blue-600' : 'bg-white text-blue-500 border-blue-500 hover:bg-blue-50' }}">
                        Precio
                    </a>
                    <a href="{{ _f2($ur2) }}" 
                       class="px-4 py-2 text-sm font-semibold rounded border transition-colors {{ ($o1 ?? 'relevancia') === 'rebajado' ? 'bg-red-500 text-white border-red-600' : 'bg-white text-red-500 border-red-500 hover:bg-red-50' }}">
                        Rebajado
                    </a>
                </div>

                {{-- CONTENEDOR DE PRODUCTOS --}}
                @if($pr1->count() > 0)
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4 sm:gap-6 mb-6">
                    @foreach($pr1 as $pr2)
                        @php
                            // $unidad -> $u2
                            // $imagenFiltro -> $if1
                            // $especificaciones -> $esp1
                            // $productoLinea -> $pl1
                            // $productoSublineas -> $ps1
                            // $sublineaIdSeleccionada -> $sis1
                            // $item -> $it1
                            // $itemId -> $itid1
                            // $imagenPeque -> $ip1
                            // $precioFormateado -> $pf1
                            // $sufijo -> $s1
                            // $urlProducto -> $up2
                            // $paramsFiltros -> $pf2
                            // $separator -> $sep1
                            // construirQueryParamsFiltros() -> _f4()
                            $u2 = $pr2->unidadDeMedida;
                            
                            // Obtener imagen del filtro si existe
                            $if1 = null;
                            if (!empty($fa1)) {
                                $esp1 = $pr2->categoria_especificaciones_internas_elegidas;
                                if ($esp1 && is_array($esp1)) {
                                    foreach ($fa1 as $lineaId => $sublineasIds) {
                                        if ($lineaId === 'precio_min' || $lineaId === 'precio_max') continue;
                                        if (empty($sublineasIds) || !is_array($sublineasIds)) continue;
                                        
                                        $pl1 = $esp1[$lineaId] ?? null;
                                        if (!$pl1) continue;
                                        
                                        $ps1 = is_array($pl1) ? $pl1 : [$pl1];
                                        
                                        foreach ($sublineasIds as $sis1) {
                                            foreach ($ps1 as $it1) {
                                                $itid1 = (is_array($it1) && isset($it1['id'])) ? strval($it1['id']) : strval($it1);
                                                if (strval($sis1) === $itid1) {
                                                    if (is_array($it1) && isset($it1['img']) && is_array($it1['img']) && count($it1['img']) > 0) {
                                                        $if1 = $it1['img'][0];
                                                        break 3;
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                            
                            $ip1 = $if1 ?? (is_array($pr2->imagen_pequena) ? ($pr2->imagen_pequena[0] ?? '') : ($pr2->imagen_pequena ?? ''));
                            
                            $pf1 = $u2 === 'unidadMilesima' 
                                ? number_format($pr2->precio, 3, ',', '.')
                                : number_format($pr2->precio, 2, ',', '.');
                            
                            $s1 = match($u2) {
                                'unidad' => '/Und.',
                                'kilos' => '/Kg.',
                                'litros' => '/L.',
                                'unidadMilesima' => '/Und.',
                                'unidadUnica' => '',
                                '800gramos' => '/800gr.',
                                '100ml' => '/100ml.',
                                default => ''
                            };
                            
                            // Construir URL del producto con filtros aplicados
                            $up2 = $pr2->categoria->construirUrlCategorias($pr2->slug);
                            $pf2 = _f4($fa1 ?? [], $pm1 ?? null, $pm2 ?? null, $fi1->toArray());
                            
                            // Añadir parámetros de filtros si existen
                            if (!empty($pf2)) {
                                $sep1 = strpos($up2, '?') !== false ? '&' : '?';
                                $up2 .= $sep1 . http_build_query($pf2);
                            }
                            
                            // Añadir parámetro cam si existe (usando función validada)
                            $up2 = _f1($up2);
                        @endphp
                        <a href="{{ $up2 }}"
                           class="group relative flex flex-col items-center bg-white rounded-xl shadow-md transition-all duration-300 transform hover:scale-105 hover:shadow-2xl cursor-pointer p-4">
                            @if($pr2->rebajado)
                                <div class="absolute top-2 right-2 bg-red-500 text-white text-xs font-bold px-2 py-1 rounded-md shadow-md z-10">
                                    Rebajado: {{ $pr2->rebajado }}%
                                </div>
                            @endif
                            <div class="w-full flex justify-center mb-3">
                                @if($ip1)
                                    <img loading="lazy" src="/images/{{ $ip1 }}" alt="{{ $pr2->nombre }}" class="w-32 h-32 object-contain rounded-lg shadow-sm transition-all duration-300 group-hover:shadow-lg bg-gray-100">
                                @else
                                    <div class="w-32 h-32 flex items-center justify-center rounded-lg bg-gradient-to-br from-blue-100 to-blue-200">
                                        <svg class="w-12 h-12 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                        </svg>
                                    </div>
                                @endif
                            </div>
                            <p class="font-semibold text-gray-700 text-center text-sm mb-1 line-clamp-2">{{ $pr2->nombre }}</p>
                            <p class="text-center mb-1">
                                <span class="text-xs text-gray-500">Desde:</span>
                                <span class="text-xl font-bold text-pink-600">{{ $pf1 }}€
                                    @if($s1)
                                        <span class="text-xs text-gray-500">{{ $s1 }}</span>
                                    @endif
                                </span>
                            </p>
                        </a>
                    @endforeach
                </div>
                
                {{-- PAGINACIÓN --}}
                <div class="mt-6">
                    {{ $pr1->links() }}
                </div>
                @else
                <div class="text-center py-8">
                    <p class="text-gray-600 text-lg">No hay productos que coincidan con los filtros seleccionados.</p>
                </div>
                @endif
            </main>
        </div>

    </main>

    {{-- MODAL DE FILTROS (solo móvil/tablet) --}}
    @if($fi1->count() > 0)
    <div id="mf1" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 lg:hidden">
        <div class="fixed bottom-0 left-0 right-0 bg-white rounded-t-xl shadow-2xl max-h-[90vh] flex flex-col">
            {{-- HEADER DEL MODAL --}}
            <div class="flex items-center justify-between p-4 border-b border-gray-200">
                <h2 class="text-lg font-bold text-gray-900">Filtros</h2>
                <button type="button" id="cmf1" class="text-gray-500 hover:text-gray-700">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            {{-- CONTENIDO DEL MODAL (scrollable) --}}
            <div class="flex-1 overflow-y-auto p-4">
                <div class="space-y-4">
                    {{-- FILTRO DE RANGO DE PRECIOS --}}
                    <div class="filtro-precio border border-gray-200 rounded-lg p-3 bg-gray-50">
                        <div class="font-bold text-base text-gray-900 mb-3 pb-2 border-b border-gray-300" style="color: #111827 !important; font-weight: bold !important;">
                            Rango de Precios
                        </div>
                        <div class="precio-slider-container">
                            <div class="flex items-center justify-between mb-3">
                                <div class="flex items-center gap-1">
                                    <input type="number" 
                                           id="pmim1" 
                                           class="w-20 px-2 py-1 border border-gray-300 rounded text-sm"
                                           step="0.01"
                                           min="0">
                                    <span class="text-gray-500 text-sm">€</span>
                                </div>
                                <span class="text-gray-500">-</span>
                                <div class="flex items-center gap-1">
                                    <input type="number" 
                                           id="pmxm1" 
                                           class="w-20 px-2 py-1 border border-gray-300 rounded text-sm"
                                           step="0.01"
                                           min="0">
                                    <span class="text-gray-500 text-sm">€</span>
                                </div>
                            </div>
                            <div class="relative h-2 bg-gray-200 rounded-full" id="pstm1">
                                <div class="absolute h-2 bg-gray-200 rounded-full w-full"></div>
                                <div class="absolute h-2 bg-blue-500 rounded-full" id="psam1"></div>
                                <input type="range" 
                                       id="pmsm1" 
                                       class="absolute w-full h-0 bg-transparent appearance-none cursor-pointer z-20"
                                       min="0"
                                       max="100"
                                       step="0.01">
                                <input type="range" 
                                       id="pmxsm1" 
                                       class="absolute w-full h-0 bg-transparent appearance-none cursor-pointer z-20"
                                       min="0"
                                       max="100"
                                       step="0.01">
                                </div>
                            </div>
                        </div>
                    {{-- FILTRO DE REBAJADO EN MODAL --}}
                    <div class="filtro-rebajado-container relative overflow-hidden rounded-lg" style="border: 1px solid transparent; padding: 1px; background: linear-gradient(45deg, #ef4444, #fbbf24, #dc2626, #fcd34d, #b91c1c, #f59e0b, #991b1b, #fde68a, #7f1d1d, #fef3c7, #f87171, #fecaca, #fca5a5, #fee2e2); background-size: 400% 400%; animation: rebajadoBorder 30s ease-in-out infinite; box-shadow: 0 0 8px rgba(239, 68, 68, 0.25);">
                        <div class="bg-white rounded-md px-3 py-2">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input 
                                    type="checkbox"
                                    id="crm1"
                                    class="filtro-rebajado-checkbox w-5 h-5 text-red-600 border-red-400 rounded focus:ring-red-500 focus:ring-2"
                                    {{ $r1 ?? false ? 'checked' : '' }}
                                    onchange="_afr1(this)">
                                <span class="text-base font-bold text-gray-800">Rebajado</span>
                            </label>
                        </div>
                    </div>
                    @foreach($fi1 as $index => $filtro)
                        @php
                            $esColapsable = $index >= 4; // A partir del 5º filtro (índice 4)
                            $claseColapsado = $esColapsable ? 'filtro-colapsado' : '';
                        @endphp
                        <div class="filtro-linea-principal border border-gray-200 rounded-lg p-3 bg-gray-50 {{ $claseColapsado }}" data-linea-id="{{ $filtro['id'] }}" data-index="{{ $index }}">
                            <div class="filtro-titulo-header font-bold text-base text-gray-900 pb-2 border-b border-gray-300 flex items-center justify-between cursor-pointer hover:bg-gray-100 px-2 py-2 -mx-2 rounded transition-colors {{ $esColapsable ? 'mb-0' : 'mb-3' }}" style="color: #111827 !important; font-weight: bold !important;" data-linea-id="{{ $filtro['id'] }}">
                                <span>{{ $filtro['texto'] }}</span>
                                <div class="flex items-center gap-2">
                                    <span class="contador-linea-principal text-sm font-normal text-gray-600" data-linea-id="{{ $filtro['id'] }}">({{ $filtro['contador'] ?? 0 }})</span>
                                    @if($esColapsable)
                                    <svg class="filtro-flecha w-5 h-5 text-gray-600 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                    @endif
                                </div>
                            </div>
                            <div class="filtro-contenido {{ $esColapsable ? 'hidden' : '' }}">
                                @php
                                    $totalSublineas = count($filtro['subprincipales']);
                                    $mostrarPrimeras = min(5, $totalSublineas);
                                    $tieneMasDe5 = $totalSublineas > 5;
                                    $tieneMasDe8 = $totalSublineas > 8;
                                @endphp
                                <div class="sublineas-container space-y-2 mt-2" 
                                     data-linea-id="{{ $filtro['id'] }}"
                                     data-total="{{ $totalSublineas }}"
                                     data-mostrando="{{ $mostrarPrimeras }}"
                                     data-tiene-mas-de-8="{{ $tieneMasDe8 ? 'true' : 'false' }}">
                                @foreach($filtro['subprincipales'] as $index => $sub)
                                    @php
                                        $checkboxId = 'checkbox_modal_' . $filtro['id'] . '_' . $sub['id'];
                                        $mostrarItem = $index < $mostrarPrimeras || !$tieneMasDe5;
                                        $contador = $sub['contador'] ?? 0;
                                        $estaDeshabilitado = $contador === 0;
                                        // Verificar si este filtro está aplicado
                                        // $filtrosAplicados -> $fa1
                                        $slugSublinea = $sub['slug'] ?? \Illuminate\Support\Str::slug($sub['texto'] ?? '');
                                        $estaSeleccionado = false;
                                        if (isset($fa1[$filtro['id']]) && is_array($fa1[$filtro['id']])) {
                                            $estaSeleccionado = in_array($sub['id'], $fa1[$filtro['id']]);
                                        }
                                    @endphp
                                    <label class="filtro-sublinea-label flex items-center gap-2 {{ $estaDeshabilitado ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer hover:bg-white' }} p-2 rounded transition-colors border border-transparent hover:border-gray-300 {{ !$mostrarItem ? 'hidden' : '' }}" 
                                           for="{{ $checkboxId }}"
                                           data-linea-id="{{ $filtro['id'] }}"
                                           data-sublinea-id="{{ $sub['id'] }}"
                                           data-sublinea-slug="{{ $slugSublinea }}"
                                           data-index="{{ $index }}">
                                        <input 
                                            type="checkbox"
                                            id="{{ $checkboxId }}"
                                            class="filtro-sublinea-checkbox w-4 h-4 text-blue-600 border-gray-400 rounded focus:ring-blue-500 focus:ring-2"
                                            data-linea-id="{{ $filtro['id'] }}"
                                            data-sublinea-id="{{ $sub['id'] }}"
                                            data-sublinea-slug="{{ $slugSublinea }}"
                                            data-sublinea-texto="{{ htmlspecialchars($sub['texto'], ENT_QUOTES, 'UTF-8') }}"
                                            {{ $estaSeleccionado ? 'checked' : '' }}
                                            {{ $estaDeshabilitado ? 'disabled' : '' }}
                                            @if(!$estaDeshabilitado)
                                            onchange="_af1(this)"
                                            @endif>
                                        <span class="filtro-sublinea-texto text-sm {{ $estaDeshabilitado ? 'text-gray-400' : 'text-gray-800' }} font-medium">{{ $sub['texto'] }}</span>
                                        <span class="contador-sublinea text-xs text-gray-500 ml-auto" data-linea-id="{{ $filtro['id'] }}" data-sublinea-id="{{ $sub['id'] }}">({{ $contador }})</span>
                                    </label>
                                @endforeach
                            </div>
                            @if($tieneMasDe5)
                            <button type="button" 
                                    class="btn-mostrar-mas text-sm text-blue-600 hover:text-blue-800 font-medium py-1 transition-colors cursor-pointer mt-2"
                                    data-linea-id="{{ $filtro['id'] }}"
                                    style="padding-left: 0.5rem; padding-right: 0.5rem;">
                                Mostrar más
                            </button>
                            @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            
            {{-- FOOTER DEL MODAL CON BOTÓN MOSTRAR PRODUCTOS --}}
            <div class="p-4 border-t border-gray-200 bg-white">
                <button type="button" 
                        id="bmpp1" 
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-4 rounded-lg transition-colors">
                    Mostrar <span id="cpm1">{{ $pr1->total() }}</span> productos
                </button>
            </div>
        </div>
    </div>
    @endif

    {{-- FOOTER DESDE LA RUTA COMPONENTS/FOOTER --}}
    <x-footer />

    <style>
        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        {{-- Estilos para el slider de rango de precios --}}
        .precio-slider-container input[type="range"] {
            -webkit-appearance: none;
            appearance: none;
            pointer-events: none;
        }
        
        .precio-slider-container input[type="range"]::-webkit-slider-thumb {
            -webkit-appearance: none;
            appearance: none;
            width: 18px;
            height: 18px;
            background: #3b82f6;
            border: 2px solid #ffffff;
            border-radius: 50%;
            cursor: pointer;
            pointer-events: all;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
            position: relative;
            top: 50%;
            transform: translateY(-50%);
            margin-top: 0;
        }
        
        .precio-slider-container input[type="range"]::-moz-range-thumb {
            width: 18px;
            height: 18px;
            background: #3b82f6;
            border: 2px solid #ffffff;
            border-radius: 50%;
            cursor: pointer;
            pointer-events: all;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
            position: relative;
            top: 50%;
            transform: translateY(-50%);
        }
        
        .precio-slider-container input[type="range"]::-webkit-slider-runnable-track {
            height: 0;
            background: transparent;
        }
        
        .precio-slider-container input[type="range"]::-moz-range-track {
            height: 0;
            background: transparent;
        }
        
        #pst1, #pstm1 {
            position: relative;
            padding: 9px 0;
            display: flex;
            align-items: center;
        }
        
        {{-- Por defecto, el contenedor NO debe tener scroll --}}
        .sublineas-container {
            overflow: visible !important;
        }
        
        {{-- Solo cuando se añade la clase sublineas-scrollable, activar el scroll --}}
        .sublineas-container.sublineas-scrollable {
            {{-- Altura exacta de 5 items: 
               - Cada label tiene py-2 (0.5rem arriba + 0.5rem abajo = 1rem total)
               - Altura del contenido (checkbox + texto) ≈ 1.5rem
               - space-y-2 añade 0.5rem entre items (4 espacios entre 5 items = 2rem)
               Total: 5 * (1rem padding + 1.5rem contenido) + 2rem espacios = 14.5rem
               Redondeamos a 15rem para seguridad --}}
            overflow-y: scroll !important;
            overflow-x: hidden !important;
            position: relative;
            display: block !important;
            {{-- Hacer que la barra de scroll sea siempre visible en móvil --}}
            -webkit-overflow-scrolling: touch;
        }
        
        {{-- Gradiente más visible en la parte inferior para indicar más contenido --}}
        .sublineas-scrollable::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 2rem;
            background: linear-gradient(to bottom, transparent, rgba(255, 255, 255, 0.98));
            pointer-events: none;
            z-index: 10;
        }
        
        {{-- Indicador visual adicional: mostrar parte de la 6ª sublínea --}}
        .sublineas-scrollable::before {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 8px; {{-- Dejar espacio para la barra de scroll --}}
            height: 0.5rem;
            background: linear-gradient(to bottom, rgba(59, 130, 246, 0.1), transparent);
            pointer-events: none;
            z-index: 9;
        }
        
        {{-- Barra de scroll más visible --}}
        .sublineas-scrollable::-webkit-scrollbar {
            width: 8px;
        }
        
        .sublineas-scrollable::-webkit-scrollbar-track {
            background: #e5e7eb;
            border-radius: 4px;
            border: 1px solid #d1d5db;
        }
        
        .sublineas-scrollable::-webkit-scrollbar-thumb {
            background: #3b82f6;
            border-radius: 4px;
            border: 1px solid #2563eb;
        }
        
        .sublineas-scrollable::-webkit-scrollbar-thumb:hover {
            background: #2563eb;
        }
        
        {{-- Para Firefox --}}
        .sublineas-scrollable {
            scrollbar-width: thin;
            scrollbar-color: #3b82f6 #e5e7eb;
        }
        
        {{-- En móvil, asegurar que el scroll sea visible --}}
        @media (max-width: 1023px) {
            .sublineas-scrollable {
                {{-- Forzar que la barra de scroll sea siempre visible en móvil --}}
                scrollbar-width: thin;
                scrollbar-color: #3b82f6 #e5e7eb;
                {{-- Asegurar que el scroll funcione --}}
                overflow-y: scroll !important;
                -webkit-overflow-scrolling: touch;
            }
            
            .sublineas-scrollable::-webkit-scrollbar {
                width: 10px !important; {{-- Más ancha en móvil para mejor visibilidad --}}
                display: block !important; {{-- Forzar que se muestre --}}
            }
            
            .sublineas-scrollable::-webkit-scrollbar-track {
                background: #e5e7eb !important;
                border-radius: 4px;
                border: 1px solid #d1d5db;
            }
            
            .sublineas-scrollable::-webkit-scrollbar-thumb {
                background: #2563eb !important; {{-- Más oscuro en móvil --}}
                min-height: 30px; {{-- Mínimo tamaño para mejor visibilidad --}}
                border-radius: 4px;
                border: 1px solid #1d4ed8;
            }
        }
        
        {{-- Estilos para filtros colapsables --}}
        .filtro-colapsado .filtro-contenido {
            display: none !important;
        }
        
        .filtro-colapsado.expandido .filtro-contenido {
            display: block !important;
        }
        
        .filtro-colapsado.expandido .filtro-flecha {
            transform: rotate(180deg);
        }
        
        .filtro-colapsado .filtro-titulo-header {
            margin-bottom: 0 !important;
        }
        
        .filtro-colapsado.expandido .filtro-titulo-header {
            margin-bottom: 0.75rem !important;
        }
        
        {{-- Animación para el borde de rebajado --}}
        @keyframes rebajadoBorder {
            0% {
                background-position: 0% 50%;
                box-shadow: 0 0 8px rgba(239, 68, 68, 0.25), 0 0 4px rgba(251, 191, 36, 0.2);
            }
            25% {
                background-position: 100% 50%;
                box-shadow: 0 0 6px rgba(220, 38, 38, 0.25), 0 0 6px rgba(252, 211, 77, 0.25);
            }
            50% {
                background-position: 100% 100%;
                box-shadow: 0 0 8px rgba(185, 28, 28, 0.25), 0 0 4px rgba(245, 158, 11, 0.2);
            }
            75% {
                background-position: 0% 100%;
                box-shadow: 0 0 6px rgba(153, 27, 27, 0.25), 0 0 6px rgba(253, 230, 138, 0.25);
            }
            100% {
                background-position: 0% 50%;
                box-shadow: 0 0 8px rgba(239, 68, 68, 0.25), 0 0 4px rgba(251, 191, 36, 0.2);
            }
        }
    </style>

    <script>
        {{-- _f2() -> añadirCamJS() --}}
        {{-- Función para añadir parámetro cam a URLs en JavaScript de forma segura --}}
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
        
        {{-- _e1() -> escapeHtml() --}}
        {{-- Función para escapar HTML (protección adicional) --}}
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

        {{-- _f3() -> construirUrlFiltros() --}}
        {{-- Función para construir URL con filtros aplicados --}}
        function _f3(filtrosSeleccionados, precioMin = null, precioMax = null, orden = 'relevancia', rebajado = false) {
            const categoriaSlug = '{{ $ca1->slug }}';
            const segmentos = [];
            
            {{-- Obtener estructura de filtros para mapear IDs a slugs --}}
            const estructuraFiltros = @json($fi1);
            const mapaIdsASlugs = {};
            
            estructuraFiltros.forEach(filtro => {
                filtro.subprincipales.forEach(sub => {
                    const slug = sub.slug || generarSlugDesdeTexto(sub.texto);
                    mapaIdsASlugs[sub.id] = slug;
                });
            });
            
            {{-- Agrupar filtros por línea principal --}}
            const filtrosPorLinea = {};
            Object.keys(filtrosSeleccionados).forEach(lineaId => {
                if (lineaId === 'precio_min' || lineaId === 'precio_max') return;
                
                const sublineasIds = filtrosSeleccionados[lineaId];
                if (sublineasIds && sublineasIds.length > 0) {
                    {{-- Eliminar IDs duplicados antes de mapear a slugs --}}
                    const sublineasIdsUnicos = [...new Set(sublineasIds)];
                    const slugs = sublineasIdsUnicos
                        .map(id => mapaIdsASlugs[id])
                        .filter(slug => slug);
                    
                    {{-- Eliminar slugs duplicados también --}}
                    const slugsUnicos = [...new Set(slugs)];
                    
                    if (slugsUnicos.length > 0) {
                        {{-- Si hay múltiples valores, unirlos: talla-1-talla-2 --}}
                        {{-- Si solo hay uno, ponerlo directamente --}}
                        segmentos.push(slugsUnicos.join('-'));
                    }
                }
            });
            
            {{-- Agregar precio si existe --}}
            if (precioMin !== null && precioMax !== null && precioMin > 0 && precioMax < 999999) {
                segmentos.push(`precio-${precioMin}-${precioMax}`);
            }
            
            {{-- Construir URL base --}}
            let url = `/categoria/${categoriaSlug}`;
            if (segmentos.length > 0) {
                url += '/' + segmentos.join('/');
            }
            
            {{-- Agregar parámetros adicionales --}}
            const queryParts = [];
            if (orden && orden !== 'relevancia') {
                queryParts.push('orden=' + encodeURIComponent(orden));
            }
            if (rebajado) {
                queryParts.push('rebajado');
            }
            const cam = new URLSearchParams(window.location.search).get('cam');
            if (cam) {
                {{-- Validar que solo contenga caracteres alfanuméricos, guiones y guiones bajos --}}
                if (/^[a-zA-Z0-9\-_]+$/.test(cam)) {
                    queryParts.push('cam=' + encodeURIComponent(cam));
                }
            }
            
            if (queryParts.length > 0) {
                url += '?' + queryParts.join('&');
            }
            
            return url;
        }

        {{-- _gs1() -> generarSlugDesdeTexto() --}}
        {{-- Función para generar slug desde texto --}}
        function _gs1(texto) {
            return texto
                .toString()
                .toLowerCase()
                .trim()
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/^-+|-+$/g, '');
        }

        {{-- _of1() -> obtenerFiltrosYConstruirUrl() --}}
        {{-- Función para obtener filtros seleccionados y construir URL --}}
        function _of1() {
            {{-- Obtener todos los checkboxes marcados (pueden estar en sidebar o modal) --}}
            {{-- Usar un Set para evitar duplicados por línea principal --}}
            const filtrosSeleccionados = {};
            
            {{-- Primero obtener del sidebar --}}
            const checkboxesSidebar = document.querySelectorAll('aside .filtro-sublinea-checkbox:checked');
            checkboxesSidebar.forEach(cb => {
                const lineaId = cb.dataset.lineaId;
                const sublineaId = cb.dataset.sublineaId;
                
                if (!filtrosSeleccionados[lineaId]) {
                    filtrosSeleccionados[lineaId] = new Set();
                }
                filtrosSeleccionados[lineaId].add(sublineaId);
            });
            
            {{-- Convertir Sets a Arrays --}}
            Object.keys(filtrosSeleccionados).forEach(lineaId => {
                filtrosSeleccionados[lineaId] = Array.from(filtrosSeleccionados[lineaId]);
            });
            
            {{-- Obtener valores de precio --}}
            const precioMinInput = document.getElementById('pmi1');
            const precioMaxInput = document.getElementById('pmx1');
            const precioMin = precioMinInput ? parseFloat(precioMinInput.value) : null;
            const precioMax = precioMaxInput ? parseFloat(precioMaxInput.value) : null;
            
            {{-- Obtener estado del filtro de rebajado --}}
            const checkboxRebajadoSidebar = document.getElementById('crs1');
            const checkboxRebajadoModal = document.getElementById('crm1');
            const rebajado = (checkboxRebajadoSidebar && checkboxRebajadoSidebar.checked) || (checkboxRebajadoModal && checkboxRebajadoModal.checked);
            
            {{-- Obtener orden actual --}}
            let ordenActual = 'relevancia';
            const botonPrecio = document.querySelector('a[href*="orden=precio"]');
            const botonRebajado = document.querySelector('a[href*="orden=rebajado"]');
            
            if (botonRebajado && (botonRebajado.classList.contains('bg-red-500') || botonRebajado.classList.contains('bg-red-600'))) {
                ordenActual = 'rebajado';
            } else if (botonPrecio && (botonPrecio.classList.contains('bg-blue-500') || botonPrecio.classList.contains('bg-blue-600'))) {
                ordenActual = 'precio';
            }
            
            {{-- Construir URL y redirigir --}}
            const url = _f3(filtrosSeleccionados, precioMin, precioMax, ordenActual, rebajado);
            return url;
        }

        {{-- _em1() -> esMovil() --}}
        {{-- Función para detectar si estamos en móvil --}}
        function _em1() {
            return window.innerWidth < 1024; {{-- lg breakpoint de Tailwind --}}
        }

        {{-- _ep1() -> eliminarParametroM() --}}
        {{-- Función para eliminar el parámetro m de la URL sin recargar --}}
        function _ep1() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('m')) {
                urlParams.delete('m');
                const nuevaUrl = window.location.pathname + (urlParams.toString() ? '?' + urlParams.toString() : '');
                window.history.replaceState({}, '', nuevaUrl);
            }
        }

        {{-- _af1() -> aplicarFiltro() --}}
        {{-- Función para aplicar filtro cuando se hace clic en checkbox --}}
        function _af1(checkbox) {
            let url = _of1();
            
            {{-- Si estamos en móvil y el checkbox está dentro del modal, añadir parámetro m al final --}}
            if (_em1() && checkbox.closest('#mf1')) {
                const separator = url.includes('?') ? '&' : '?';
                url += separator + 'm'; {{-- 'm' para móvil, sin valor --}}
            }
            
            window.location.href = url;
        }

        {{-- _afr1() -> aplicarFiltroRebajado() --}}
        {{-- Función para aplicar filtro de rebajado --}}
        function _afr1(checkbox) {
            {{-- Sincronizar checkbox entre sidebar y modal --}}
            const checkboxSidebar = document.getElementById('crs1');
            const checkboxModal = document.getElementById('crm1');
            
            if (checkbox.id === 'crs1' && checkboxModal) {
                checkboxModal.checked = checkbox.checked;
            } else if (checkbox.id === 'crm1' && checkboxSidebar) {
                checkboxSidebar.checked = checkbox.checked;
            }
            
            {{-- Aplicar filtro redirigiendo a la nueva URL --}}
            let url = _of1();
            
            {{-- Si estamos en móvil y el checkbox está dentro del modal, añadir parámetro m al final --}}
            if (_em1() && checkbox.closest('#mf1')) {
                const separator = url.includes('?') ? '&' : '?';
                url += separator + 'm'; {{-- 'm' para móvil, sin valor --}}
            }
            
            window.location.href = url;
        }

        {{-- Event listener para el botón de aplicar precio --}}
        document.addEventListener('DOMContentLoaded', function() {
            const btnAplicarPrecio = document.getElementById('bap1');
            if (btnAplicarPrecio) {
                btnAplicarPrecio.addEventListener('click', function() {
                    let url = _of1();
                    
                    {{-- Si estamos en móvil y el modal está abierto, añadir parámetro m al final --}}
                    if (_em1()) {
                        const modalFiltros = document.getElementById('mf1');
                        if (modalFiltros && !modalFiltros.classList.contains('hidden')) {
                            const separator = url.includes('?') ? '&' : '?';
                            url += separator + 'm'; {{-- 'm' para móvil, sin valor --}}
                        }
                    }
                    
                    window.location.href = url;
                });
            }
        });

        {{-- Filtros de precio - usar valores del servidor --}}
        const precioMinGlobal = {{ $pmg1 ?? 0 }};
        const precioMaxGlobal = {{ $pmg2 ?? 100 }};
        let precioMinActual = {{ $pm1 ?? $pmg1 ?? 0 }};
        let precioMaxActual = {{ $pm2 ?? $pmg2 ?? 100 }};
        
        {{-- _isp1() -> inicializarSlidersPrecio() --}}
        {{-- Inicializar sliders de precio con valores del servidor --}}
        function _isp1() {
            const minSlider = document.getElementById('pms1');
            const maxSlider = document.getElementById('pmxs1');
            const minInput = document.getElementById('pmi1');
            const maxInput = document.getElementById('pmx1');
            const activeBar = document.getElementById('psa1');
            
            const minSliderModal = document.getElementById('pmsm1');
            const maxSliderModal = document.getElementById('pmxsm1');
            const minInputModal = document.getElementById('pmim1');
            const maxInputModal = document.getElementById('pmxm1');
            const activeBarModal = document.getElementById('psam1');
            
            const precioMax = precioMaxGlobal === precioMinGlobal ? precioMinGlobal + 1 : precioMaxGlobal;
            
            const setValues = (sliderMin, sliderMax, inputMin, inputMax, bar) => {
                if (sliderMin && sliderMax && inputMin && inputMax && bar) {
                    sliderMin.min = precioMinGlobal;
                    sliderMin.max = precioMax;
                    sliderMin.value = precioMinActual;
                    sliderMax.min = precioMinGlobal;
                    sliderMax.max = precioMax;
                    sliderMax.value = precioMaxActual;
                    inputMin.value = precioMinActual.toFixed(2);
                    inputMax.value = precioMaxActual.toFixed(2);
                    inputMin.min = precioMinGlobal;
                    inputMin.max = precioMax;
                    inputMax.min = precioMinGlobal;
                    inputMax.max = precioMax;
                    _aba1(sliderMin, sliderMax, bar);
                }
            };
            
            setValues(minSlider, maxSlider, minInput, maxInput, activeBar);
            setValues(minSliderModal, maxSliderModal, minInputModal, maxInputModal, activeBarModal);
        }
        
        {{-- _aba1() -> actualizarBarraActiva() --}}
        {{-- Actualizar la barra activa entre los dos sliders --}}
        function _aba1(minSlider, maxSlider, activeBar) {
            if (!minSlider || !maxSlider || !activeBar) return;
            
            const min = parseFloat(minSlider.value);
            const max = parseFloat(maxSlider.value);
            const precioMax = precioMaxGlobal === precioMinGlobal ? precioMinGlobal + 1 : precioMaxGlobal;
            const minPos = ((min - precioMinGlobal) / (precioMax - precioMinGlobal)) * 100;
            const maxPos = ((max - precioMinGlobal) / (precioMax - precioMinGlobal)) * 100;
            
            activeBar.style.left = minPos + '%';
            activeBar.style.width = (maxPos - minPos) + '%';
        }
        
        {{-- _csp1() -> configurarSlidersPrecio() --}}
        {{-- Configurar eventos para un conjunto de sliders e inputs --}}
        function _csp1(sliderMinId, sliderMaxId, inputMinId, inputMaxId, activeBarId, esModal = false) {
            const minSlider = document.getElementById(sliderMinId);
            const maxSlider = document.getElementById(sliderMaxId);
            const minInput = document.getElementById(inputMinId);
            const maxInput = document.getElementById(inputMaxId);
            const activeBar = document.getElementById(activeBarId);
            
            if (!minSlider || !maxSlider || !minInput || !maxInput || !activeBar) return;
            
            // Guardar valores originales y borrar al hacer focus, restaurar si está vacío al perder focus
            let valorOriginalMin = '';
            let valorOriginalMax = '';
            
            minInput.addEventListener('focus', function() {
                valorOriginalMin = this.value;
                this.value = '';
            });
            
            minInput.addEventListener('blur', function() {
                if (this.value === '' || this.value === null) {
                    this.value = valorOriginalMin;
                }
            });
            
            maxInput.addEventListener('focus', function() {
                valorOriginalMax = this.value;
                this.value = '';
            });
            
            maxInput.addEventListener('blur', function() {
                if (this.value === '' || this.value === null) {
                    this.value = valorOriginalMax;
                }
            });
            
            // Evento para slider mínimo
            minSlider.addEventListener('input', function() {
                const min = parseFloat(this.value);
                const max = parseFloat(maxSlider.value);
                
                if (min >= max) {
                    this.value = max - 0.01;
                    return;
                }
                
                precioMinActual = parseFloat(this.value);
                minInput.value = precioMinActual.toFixed(2);
                _aba1(minSlider, maxSlider, activeBar);
                
                {{-- No hacer nada aquí, el precio se aplica con el botón "Aplicar precio" --}}
            });
            
            {{-- Evento para slider máximo --}}
            maxSlider.addEventListener('input', function() {
                const min = parseFloat(minSlider.value);
                const max = parseFloat(this.value);
                
                if (max <= min) {
                    this.value = min + 0.01;
                    return;
                }
                
                precioMaxActual = parseFloat(this.value);
                maxInput.value = precioMaxActual.toFixed(2);
                _aba1(minSlider, maxSlider, activeBar);
                
                {{-- No hacer nada aquí, el precio se aplica con el botón "Aplicar precio" --}}
            });
            
            {{-- Evento para input mínimo --}}
            minInput.addEventListener('change', function() {
                let min = parseFloat(this.value) || precioMinGlobal;
                const max = parseFloat(maxSlider.value);
                
                if (min < precioMinGlobal) min = precioMinGlobal;
                if (min >= max) min = max - 0.01;
                if (min > precioMaxGlobal) min = precioMaxGlobal;
                
                precioMinActual = min;
                minSlider.value = min;
                this.value = min.toFixed(2);
                _aba1(minSlider, maxSlider, activeBar);
                
                {{-- No hacer nada aquí, el precio se aplica con el botón "Aplicar precio" --}}
            });
            
            {{-- Evento para input máximo --}}
            maxInput.addEventListener('change', function() {
                let max = parseFloat(this.value) || precioMaxGlobal;
                const min = parseFloat(minSlider.value);
                
                if (max > precioMaxGlobal) max = precioMaxGlobal;
                if (max <= min) max = min + 0.01;
                if (max < precioMinGlobal) max = precioMinGlobal;
                
                precioMaxActual = max;
                maxSlider.value = max;
                this.value = max.toFixed(2);
                _aba1(minSlider, maxSlider, activeBar);
                
                {{-- No hacer nada aquí, el precio se aplica con el botón "Aplicar precio" --}}
            });
        }

        {{-- Estas funciones ya no son necesarias - todo el filtrado se hace en el servidor --}}

        {{-- _cbe1() -> configurarBotonesEspecificaciones() --}}
        {{-- Configurar event listeners para los checkboxes de especificaciones (solo sidebar, no modal) --}}
        function _cbe1() {
            const checkboxes = document.querySelectorAll('aside .filtro-sublinea-checkbox');
            
            // Event listener para checkboxes del sidebar
            checkboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    if (this.disabled) return;
                    
                    // Sincronizar con el modal
                    const lineaId = this.dataset.lineaId;
                    const sublineaId = this.dataset.sublineaId;
                    const checkboxModal = document.querySelector(`#mf1 .filtro-sublinea-checkbox[data-linea-id="${lineaId}"][data-sublinea-id="${sublineaId}"]`);
                    if (checkboxModal) {
                        checkboxModal.checked = this.checked;
                    }
                    
                    {{-- Aplicar filtro redirigiendo a la nueva URL --}}
                    _af1(this);
                });
            });
        }

        {{-- _sob1() -> setOrdenBotones() --}}
        {{-- Configurar botones de ordenación --}}
        function _sob1() {
            const btnRelevancia = document.getElementById('btn-orden-relevancia');
            const btnPrecio = document.getElementById('btn-orden-precio');
            
            if (ordenActual === 'relevancia') {
                btnRelevancia.classList.add('bg-blue-500', 'text-white', 'border-blue-600');
                btnRelevancia.classList.remove('bg-white', 'text-blue-500', 'border-blue-500', 'hover:bg-blue-50');
                btnPrecio.classList.remove('bg-blue-500', 'text-white', 'border-blue-600');
                btnPrecio.classList.add('bg-white', 'text-blue-500', 'border-blue-500', 'hover:bg-blue-50');
            } else {
                btnPrecio.classList.add('bg-blue-500', 'text-white', 'border-blue-600');
                btnPrecio.classList.remove('bg-white', 'text-blue-500', 'border-blue-500', 'hover:bg-blue-50');
                btnRelevancia.classList.remove('bg-blue-500', 'text-white', 'border-blue-600');
                btnRelevancia.classList.add('bg-white', 'text-blue-500', 'border-blue-500', 'hover:bg-blue-50');
            }
        }

        {{-- _cbm1() -> configurarBotonesMostrarMas() --}}
        {{-- Función para manejar "Mostrar más" --}}
        function _cbm1() {
            const botonesMostrarMas = document.querySelectorAll('.btn-mostrar-mas');
            console.log('Configurando botones "Mostrar más":', botonesMostrarMas.length);
            
            botonesMostrarMas.forEach(boton => {
                boton.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    console.log('Click en "Mostrar más"');
                    
                    const lineaId = this.dataset.lineaId;
                    console.log('Linea ID:', lineaId);
                    
                    // Buscar el contenedor más cercano (puede estar en sidebar o modal)
                    const lineaPrincipal = this.closest('.filtro-linea-principal');
                    let container = lineaPrincipal ? lineaPrincipal.querySelector(`.sublineas-container[data-linea-id="${lineaId}"]`) : null;
                    
                    if (!container) {
                        // Si no se encuentra con closest, buscar en todo el documento
                        const estaEnModal = this.closest('#mf1');
                        if (estaEnModal) {
                            container = estaEnModal.querySelector(`.sublineas-container[data-linea-id="${lineaId}"]`);
                        } else {
                            container = document.querySelector(`aside .sublineas-container[data-linea-id="${lineaId}"]`);
                        }
                    }
                    
                    if (!container) {
                        console.error('No se encontró el contenedor para línea:', lineaId);
                        return;
                    }
                    
                    console.log('Contenedor encontrado:', container);
                    const tieneMasDe8Attr = container.getAttribute('data-tiene-mas-de-8');
                    console.log('Atributo data-tiene-mas-de-8 (getAttribute):', tieneMasDe8Attr);
                    console.log('Atributo data-tiene-mas-de-8 (dataset):', container.dataset.tieneMasDe8);
                    
                    // Usar getAttribute porque dataset puede no funcionar con guiones múltiples
                    const tieneMasDe8 = tieneMasDe8Attr === 'true';
                    const estaMostrandoTodas = this.classList.contains('mostrando-todas');
                    
                    console.log('tieneMasDe8:', tieneMasDe8, 'estaMostrandoTodas:', estaMostrandoTodas);
                    
                    if (estaMostrandoTodas) {
                        // Ocultar: volver a mostrar solo las primeras 5
                        if (tieneMasDe8) {
                            container.classList.remove('sublineas-scrollable');
                            // Limpiar estilos inline relacionados con scroll usando removeProperty
                            container.style.removeProperty('max-height');
                            container.style.removeProperty('height');
                            container.style.removeProperty('overflow-y');
                            container.style.removeProperty('overflow-x');
                            container.style.removeProperty('-webkit-overflow-scrolling');
                            
                            // Ocultar el indicador de scroll
                            const indicador = document.querySelector(`[data-indicador-linea="${lineaId}"]`);
                            if (indicador) {
                                indicador.classList.add('hidden');
                            }
                        }
                        const labels = container.querySelectorAll('.filtro-sublinea-label');
                        labels.forEach((label, index) => {
                            if (index >= 5) {
                                label.classList.add('hidden');
                            }
                        });
                        this.textContent = 'Mostrar más';
                        this.classList.remove('mostrando-todas');
                    } else {
                        // Mostrar: mostrar todas las sublíneas
                        const labels = container.querySelectorAll('.filtro-sublinea-label');
                        
                        if (tieneMasDe8) {
                            console.log('Entrando en bloque tieneMasDe8 = true');
                            // Más de 8: mostrar todas pero con scroll (mantener espacio de 5)
                            labels.forEach(label => {
                                label.classList.remove('hidden');
                            });
                            console.log('Labels mostrados:', labels.length);
                            
                            // Activar scroll manteniendo el espacio de 5 sublíneas
                            container.classList.add('sublineas-scrollable');
                            
                            // Calcular altura exacta de 5 items + parte de la 6ª para indicar que hay más
                            const primerLabel = labels[0];
                            if (primerLabel) {
                                // Esperar a que el DOM se actualice para obtener la altura real
                                requestAnimationFrame(() => {
                                    const labelHeight = primerLabel.offsetHeight;
                                    const espacioEntreItems = 8; // space-y-2 = 0.5rem = 8px
                                    // Mostrar 5 completas + 1/3 de la 6ª para indicar visualmente que hay más
                                    const altura5Items = (labelHeight * 5) + (espacioEntreItems * 4);
                                    const alturaConIndicador = altura5Items + (labelHeight * 0.33); // Mostrar parte de la 6ª
                                    
                                    console.log('Altura calculada:', {
                                        labelHeight: labelHeight,
                                        altura5Items: altura5Items,
                                        alturaConIndicador: alturaConIndicador,
                                        totalLabels: labels.length
                                    });
                                    
                                    // Limpiar estilos previos primero
                                    container.style.removeProperty('max-height');
                                    container.style.removeProperty('height');
                                    container.style.removeProperty('overflow-y');
                                    container.style.removeProperty('overflow-x');
                                    
                                    // Aplicar nuevos estilos con !important
                                    container.style.setProperty('max-height', `${alturaConIndicador}px`, 'important');
                                    container.style.setProperty('height', `${alturaConIndicador}px`, 'important');
                                    container.style.setProperty('overflow-y', 'scroll', 'important');
                                    container.style.setProperty('overflow-x', 'hidden', 'important');
                                    
                                    // Asegurar que la barra de scroll sea visible en móvil
                                    container.style.setProperty('-webkit-overflow-scrolling', 'touch', 'important');
                                    
                                    // Forzar el redibujado para asegurar que el scroll se active
                                    void container.offsetHeight;
                                    
                                    // Esperar a que el DOM se actualice completamente
                                    requestAnimationFrame(() => {
                                        requestAnimationFrame(() => {
                                            container.scrollTop = 0;
                                            
                                            // Verificar que el scroll funcione
                                            const tieneScroll = container.scrollHeight > container.clientHeight;
                                            const estaEnModal = container.closest('#mf1');
                                            
                                            console.log('Scroll activado:', {
                                                alturaCalculada: alturaConIndicador,
                                                scrollHeight: container.scrollHeight,
                                                clientHeight: container.clientHeight,
                                                offsetHeight: container.offsetHeight,
                                                tieneScroll: tieneScroll,
                                                estaEnModal: !!estaEnModal,
                                                computedStyle: window.getComputedStyle(container).overflowY,
                                                computedMaxHeight: window.getComputedStyle(container).maxHeight,
                                                computedHeight: window.getComputedStyle(container).height
                                            });
                                            
                                            // Si no hay scroll, forzar que se muestre ajustando la altura
                                            if (!tieneScroll && container.scrollHeight > 0) {
                                                // Reducir un poco la altura para forzar el scroll
                                                const nuevaAltura = Math.min(alturaConIndicador, container.scrollHeight - 1);
                                                container.style.setProperty('max-height', `${nuevaAltura}px`, 'important');
                                                container.style.setProperty('height', `${nuevaAltura}px`, 'important');
                                                console.log('Ajustando altura para forzar scroll:', nuevaAltura);
                                                
                                                // Verificar de nuevo después del ajuste
                                                setTimeout(() => {
                                                    const tieneScrollAhora = container.scrollHeight > container.clientHeight;
                                                    console.log('Scroll después del ajuste:', tieneScrollAhora);
                                                }, 50);
                                            }
                                            
                                            // En móvil, asegurar que el scroll sea visible
                                            if (estaEnModal) {
                                                // Calcular altura exacta para forzar scroll
                                                const alturaNecesaria = container.scrollHeight;
                                                const alturaMaxima = alturaConIndicador;
                                                
                                                // Si el contenido es mayor que la altura máxima, forzar scroll
                                                if (alturaNecesaria > alturaMaxima) {
                                                    // Reducir la altura para asegurar que haya scroll
                                                    const alturaConScroll = alturaMaxima - 2; // Reducir 2px para asegurar scroll
                                                    
                                                    container.style.setProperty('max-height', `${alturaConScroll}px`, 'important');
                                                    container.style.setProperty('height', `${alturaConScroll}px`, 'important');
                                                    container.style.setProperty('overflow-y', 'scroll', 'important');
                                                    container.style.setProperty('-webkit-overflow-scrolling', 'touch', 'important');
                                                    
                                                    console.log('Scroll forzado en móvil:', {
                                                        alturaNecesaria: alturaNecesaria,
                                                        alturaMaxima: alturaMaxima,
                                                        alturaConScroll: alturaConScroll,
                                                        scrollHeight: container.scrollHeight,
                                                        clientHeight: container.clientHeight,
                                                        tieneScroll: container.scrollHeight > container.clientHeight
                                                    });
                                                    
                                                    // Forzar un reflow y verificar
                                                    setTimeout(() => {
                                                        const tieneScrollFinal = container.scrollHeight > container.clientHeight;
                                                        console.log('Verificación final scroll móvil:', tieneScrollFinal);
                                                        
                                                        if (!tieneScrollFinal) {
                                                            // Si aún no hay scroll, reducir más la altura
                                                            const alturaAjustada = Math.max(alturaConScroll - 10, alturaMaxima * 0.8);
                                                            container.style.setProperty('max-height', `${alturaAjustada}px`, 'important');
                                                            container.style.setProperty('height', `${alturaAjustada}px`, 'important');
                                                            console.log('Ajuste adicional de altura:', alturaAjustada);
                                                        }
                                                    }, 100);
                                                }
                                            }
                                        });
                                    });
                                });
                            } else {
                                console.warn('No se encontró el primer label');
                                // Fallback si no se puede calcular
                                container.style.setProperty('max-height', '15rem', 'important');
                                container.style.setProperty('height', '15rem', 'important');
                                container.style.setProperty('overflow-y', 'scroll', 'important');
                                container.style.setProperty('overflow-x', 'hidden', 'important');
                            }
                        } else {
                            console.log('Entrando en bloque tieneMasDe8 = false (menos de 8)');
                            // Menos de 8: mostrar todas sin scroll
                            labels.forEach(label => {
                                label.classList.remove('hidden');
                            });
                        }
                        
                        this.textContent = 'Mostrar menos';
                        this.classList.add('mostrando-todas');
                    }
                });
            });
        }

        {{-- El contador se calcula en el servidor, no es necesario actualizarlo en JavaScript --}}

        {{-- _sc1() -> sincronizarCheckboxes() --}}
        {{-- Función para sincronizar checkboxes entre sidebar y modal --}}
        function _sc1() {
            const checkboxesSidebar = document.querySelectorAll('aside .filtro-sublinea-checkbox');
            const checkboxesModal = document.querySelectorAll('#mf1 .filtro-sublinea-checkbox');
            
            // Sincronizar de sidebar a modal
            checkboxesSidebar.forEach(checkboxSidebar => {
                const lineaId = checkboxSidebar.dataset.lineaId;
                const sublineaId = checkboxSidebar.dataset.sublineaId;
                const checkboxModal = Array.from(checkboxesModal).find(cb => 
                    cb.dataset.lineaId === lineaId && cb.dataset.sublineaId === sublineaId
                );
                if (checkboxModal) {
                    checkboxModal.checked = checkboxSidebar.checked;
                    checkboxModal.disabled = checkboxSidebar.disabled;
                }
            });
            
            // Sincronizar de modal a sidebar
            checkboxesModal.forEach(checkboxModal => {
                const lineaId = checkboxModal.dataset.lineaId;
                const sublineaId = checkboxModal.dataset.sublineaId;
                const checkboxSidebar = Array.from(checkboxesSidebar).find(cb => 
                    cb.dataset.lineaId === lineaId && cb.dataset.sublineaId === sublineaId
                );
                if (checkboxSidebar) {
                    checkboxSidebar.checked = checkboxModal.checked;
                    checkboxSidebar.disabled = checkboxModal.disabled;
                }
            });
            
            {{-- Sincronizar checkbox de rebajado --}}
            const checkboxRebajadoSidebar = document.getElementById('crs1');
            const checkboxRebajadoModal = document.getElementById('crm1');
            if (checkboxRebajadoSidebar && checkboxRebajadoModal) {
                checkboxRebajadoModal.checked = checkboxRebajadoSidebar.checked;
            }
        }

        {{-- _cfc1() -> configurarFiltrosColapsables() --}}
        {{-- Configurar filtros colapsables --}}
        function _cfc1() {
            const titulosHeaders = document.querySelectorAll('.filtro-titulo-header');
            
            titulosHeaders.forEach(tituloHeader => {
                const lineaPrincipal = tituloHeader.closest('.filtro-linea-principal');
                if (!lineaPrincipal || !lineaPrincipal.classList.contains('filtro-colapsado')) {
                    return; // Solo configurar si es colapsable
                }
                
                tituloHeader.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    const estaExpandido = lineaPrincipal.classList.contains('expandido');
                    
                    if (estaExpandido) {
                        lineaPrincipal.classList.remove('expandido');
                    } else {
                        lineaPrincipal.classList.add('expandido');
                    }
                });
            });
        }

        {{-- _cmf1() -> configurarModalFiltros() --}}
        {{-- Configurar modal de filtros (móvil/tablet) --}}
        function _cmf1() {
            const btnVerFiltros = document.getElementById('bvf1');
            const modalFiltros = document.getElementById('mf1');
            const cerrarModal = document.getElementById('cmf1');
            const btnMostrarProductos = document.getElementById('bmpp1');
            
            if (btnVerFiltros && modalFiltros) {
                btnVerFiltros.addEventListener('click', function() {
                    // Abrir modal primero para que los elementos estén en el DOM
                    modalFiltros.classList.remove('hidden');
                    document.body.style.overflow = 'hidden';
                    
                    {{-- Sincronizar checkboxes después de abrir el modal --}}
                    _sc1();
                    
                    {{-- Sincronizar sliders de precio al abrir el modal --}}
                    _ssp1();
                    
                    {{-- Sincronizar checkboxes y sliders al abrir el modal --}}
                    _sc1();
                    _ssp1();
                });
            }
            
            if (cerrarModal && modalFiltros) {
                cerrarModal.addEventListener('click', function() {
                    _sc1();
                    modalFiltros.classList.add('hidden');
                    document.body.style.overflow = '';
                    {{-- Eliminar el parámetro m de la URL al cerrar el modal --}}
                    _ep1();
                });
            }
            
            {{-- Cerrar al hacer click fuera del modal --}}
            if (modalFiltros) {
                modalFiltros.addEventListener('click', function(e) {
                    if (e.target === modalFiltros) {
                        _sc1();
                        modalFiltros.classList.add('hidden');
                        document.body.style.overflow = '';
                        {{-- Eliminar el parámetro m de la URL al cerrar el modal --}}
                        _ep1();
                    }
                });
            }
            
            {{-- Botón mostrar productos en modal --}}
            if (btnMostrarProductos && modalFiltros) {
                btnMostrarProductos.addEventListener('click', function() {
                    _sc1();
                    {{-- Los productos ya se muestran desde el servidor --}}
                    modalFiltros.classList.add('hidden');
                    document.body.style.overflow = '';
                    {{-- Eliminar el parámetro m de la URL al cerrar el modal (importante: si se comparte la URL, no se abrirá el modal) --}}
                    _ep1();
                });
            }
            
            {{-- Configurar checkboxes del modal (usar la misma lógica que los del sidebar) --}}
            const checkboxesModal = document.querySelectorAll('#mf1 .filtro-sublinea-checkbox');
            checkboxesModal.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    if (this.disabled) return;
                    
                    // Sincronizar con el sidebar
                    const lineaId = this.dataset.lineaId;
                    const sublineaId = this.dataset.sublineaId;
                    const checkboxSidebar = document.querySelector(`aside .filtro-sublinea-checkbox[data-linea-id="${lineaId}"][data-sublinea-id="${sublineaId}"]`);
                    if (checkboxSidebar) {
                        checkboxSidebar.checked = this.checked;
                    }
                    
                    {{-- Aplicar filtro redirigiendo a la nueva URL --}}
                    _af1(this);
                });
            });
        }

        {{-- Inicializar --}}
        document.addEventListener('DOMContentLoaded', function() {
            {{-- Inicializar sliders de precio --}}
            _isp1();
            
            {{-- Configurar sliders de precio del sidebar --}}
            _csp1(
                'pms1',
                'pmxs1',
                'pmi1',
                'pmx1',
                'psa1',
                false
            );
            
            {{-- Configurar sliders de precio del modal --}}
            _csp1(
                'pmsm1',
                'pmxsm1',
                'pmim1',
                'pmxm1',
                'psam1',
                true
            );
            
            {{-- _ssp1() -> sincronizarSlidersPrecio() --}}
            {{-- Sincronizar sliders entre sidebar y modal --}}
            function _ssp1() {
                const minSlider = document.getElementById('pms1');
                const maxSlider = document.getElementById('pmxs1');
                const minInput = document.getElementById('pmi1');
                const maxInput = document.getElementById('pmx1');
                const minSliderModal = document.getElementById('pmsm1');
                const maxSliderModal = document.getElementById('pmxsm1');
                const minInputModal = document.getElementById('pmim1');
                const maxInputModal = document.getElementById('pmxm1');
                const activeBar = document.getElementById('psa1');
                const activeBarModal = document.getElementById('psam1');
                
                if (minSlider && minSliderModal) {
                    minSliderModal.value = minSlider.value;
                    minInputModal.value = minInput.value;
                    _aba1(minSliderModal, maxSliderModal, activeBarModal);
                }
                if (maxSlider && maxSliderModal) {
                    maxSliderModal.value = maxSlider.value;
                    maxInputModal.value = maxInput.value;
                    _aba1(minSliderModal, maxSliderModal, activeBarModal);
                }
            }
            
            {{-- Sincronizar cuando cambian los sliders del sidebar al modal --}}
            document.getElementById('pms1')?.addEventListener('input', _ssp1);
            document.getElementById('pmxs1')?.addEventListener('input', _ssp1);
            document.getElementById('pmi1')?.addEventListener('change', _ssp1);
            document.getElementById('pmx1')?.addEventListener('change', _ssp1);
            
            {{-- Sincronizar cuando cambian los sliders del modal al sidebar y renderizar --}}
            document.getElementById('pmsm1')?.addEventListener('input', function() {
                const minSlider = document.getElementById('pms1');
                const minInput = document.getElementById('pmi1');
                if (minSlider && minInput) {
                    minSlider.value = this.value;
                    minInput.value = document.getElementById('pmim1').value;
                    precioMinActual = parseFloat(this.value);
                    _aba1(minSlider, document.getElementById('pmxs1'), document.getElementById('psa1'));
                    {{-- El filtrado se hace en el servidor, no es necesario renderizar aquí --}}
                }
            });
            document.getElementById('pmxsm1')?.addEventListener('input', function() {
                const maxSlider = document.getElementById('pmxs1');
                const maxInput = document.getElementById('pmx1');
                if (maxSlider && maxInput) {
                    maxSlider.value = this.value;
                    maxInput.value = document.getElementById('pmxm1').value;
                    precioMaxActual = parseFloat(this.value);
                    _aba1(document.getElementById('pms1'), maxSlider, document.getElementById('psa1'));
                    {{-- El filtrado se hace en el servidor, no es necesario renderizar aquí --}}
                }
            });
            document.getElementById('pmim1')?.addEventListener('change', function() {
                const minInput = document.getElementById('pmi1');
                const minSlider = document.getElementById('pms1');
                if (minInput && minSlider) {
                    minInput.value = this.value;
                    minSlider.value = document.getElementById('pmsm1').value;
                    precioMinActual = parseFloat(minSlider.value);
                    _aba1(minSlider, document.getElementById('pmxs1'), document.getElementById('psa1'));
                    {{-- El filtrado se hace en el servidor, no es necesario renderizar aquí --}}
                }
            });
            document.getElementById('pmxm1')?.addEventListener('change', function() {
                const maxInput = document.getElementById('pmx1');
                const maxSlider = document.getElementById('pmxs1');
                if (maxInput && maxSlider) {
                    maxInput.value = this.value;
                    maxSlider.value = document.getElementById('pmxsm1').value;
                    precioMaxActual = parseFloat(maxSlider.value);
                    _aba1(document.getElementById('pms1'), maxSlider, document.getElementById('psa1'));
                    {{-- El filtrado se hace en el servidor, no es necesario renderizar aquí --}}
                }
            });
            
            _cbe1();
            _cbm1();
            _cfc1();
            _cmf1();
            
            {{-- En móvil, si existe el parámetro m, abrir el modal automáticamente --}}
            {{-- Usar setTimeout y requestAnimationFrame para asegurar que todo esté inicializado --}}
            requestAnimationFrame(function() {
                setTimeout(function() {
                    if (_em1()) {
                        const urlParams = new URLSearchParams(window.location.search);
                        if (urlParams.has('m')) {
                            const modalFiltros = document.getElementById('mf1');
                            if (modalFiltros) {
                                {{-- Abrir el modal --}}
                                modalFiltros.classList.remove('hidden');
                                document.body.style.overflow = 'hidden';
                                
                                {{-- Sincronizar checkboxes y sliders --}}
                                _sc1();
                                _ssp1();
                                
                                {{-- Eliminar el parámetro m de la URL sin recargar la página --}}
                                urlParams.delete('m');
                                const nuevaUrl = window.location.pathname + (urlParams.toString() ? '?' + urlParams.toString() : '');
                                window.history.replaceState({}, '', nuevaUrl);
                            }
                        }
                    }
                }, 150);
            });
            
            {{-- Los productos ya se muestran desde el servidor, no es necesario renderizar --}}
        });
    </script>
    <x-cookie-consent />
    {{-- JS PARA EL BUSCADOR DEL HEADER --}}
@stack('scripts')
</body>
</html>








