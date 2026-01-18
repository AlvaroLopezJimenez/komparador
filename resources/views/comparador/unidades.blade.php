<!DOCTYPE html>
<html lang="es">

<head>
@php

    $canonicalUrl = $producto->categoria->construirUrlCategorias($producto->slug);

    // Reemplazar host por el que queremos mostrar
    $u = parse_url($canonicalUrl);
    $u['host'] = 'komparador.com';

    // Reconstruir URL
    $canonicalUrl =
        ($u['scheme'] ?? 'https') . '://' .
        $u['host'] .
        (isset($u['path']) ? $u['path'] : '') .
        (isset($u['query']) ? '?' . $u['query'] : '');
@endphp
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  {{-- titulo personalizado de este tipo --}}
  {{-- Dodot Bebé-Seco desde 9,99 € | Agosto 2025--}}
  <title>
    {{ $producto->nombre }} desde {{ number_format($producto->precio, 2, ',', '.') }} €
    @if($producto->unidadDeMedida === 'unidad')
        /Und.
    @elseif($producto->unidadDeMedida === 'kilos')
        /kg.
    @elseif($producto->unidadDeMedida === 'litros')
        /L.
    @elseif($producto->unidadDeMedida === 'unidadMilesima')
        /Und.
    @elseif($producto->unidadDeMedida === 'unidadUnica')
        {{-- No mostrar sufijo --}}
    @elseif($producto->unidadDeMedida === '800gramos')
        /800gr.
    @elseif($producto->unidadDeMedida === '100ml')
        /100ml.
    @endif
    | {{ ucfirst(\Carbon\Carbon::now()->translatedFormat('F Y')) }}
</title>
  <meta name="description" content="{{ $producto->meta_description }}">
  <link rel="canonical" href="{{ $canonicalUrl }}">
  <meta property="og:title" content="{{ $producto->meta_titulo }}">
<meta property="og:description" content="{{ $producto->meta_description }}">
<meta property="og:image" content="{{ asset('images/' . ($producto->imagen_pequena[0] ?? '')) }}">
<meta property="og:url" content="{{ url()->current() }}">
<meta property="og:type" content="product">

  <meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $producto->meta_titulo }}">
<meta name="twitter:description" content="{{ $producto->meta_description }}">
<meta name="twitter:image" content="{{ asset('images/' . ($producto->imagen_pequena[0] ?? '')) }}">
{{-- CSRF Token --}}
<meta name="csrf-token" content="{{ csrf_token() }}">
{{-- Icono --}}
    <link rel="icon" type="image/png" href="{{ asset('images/icono.webp') }}">
  @vite(['resources/css/app.css', 'resources/js/app.js'])

  {{-- SCRIPT PARA GOOGLE ANALYTICS--}}
    @if (env('GA_MEASUREMENT_ID'))
        {{-- Google tag (gtag.js) --}}
        <script async src="https://www.googletagmanager.com/gtag/js?id={{ env('GA_MEASUREMENT_ID') }}"></script>
        <script>
          {{-- ============ Consent Mode + GA4 integrado con tu cookie_consent ============ --}}
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
              var m = document.cookie.match(/(?:^|; )cookie_consent=([^;]+)/);
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
    
          {{-- 4) Inicializa GA4 --}}
          gtag('js', new Date());
          gtag('config', '{{ env('GA_MEASUREMENT_ID') }}', {
            'transport_type': 'beacon'
          });
        </script>
    @endif
    
    @php
        $ipAutorizada = env('IP_AUTORIZADA', '');
        $ipActual = request()->ip();
        $habilitar = empty($ipAutorizada) || $ipActual === $ipAutorizada;
    @endphp

    @if($habilitar)
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
  <style>
    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      font-size: 1.05rem;
    }

    .card-hover {
      transition: transform 0.2s ease-in-out, background-color 0.2s ease-in-out;
    }

    .card-hover:hover {
      transform: scale(1.02);
      background-color: #f9f9f9;
    }

    .divider {
      position: relative;
    }

    .divider::after {
      content: "";
      position: absolute;
      top: 20%;
      bottom: 20%;
      right: 0;
      width: 1px;
      background: linear-gradient(to bottom, transparent, rgba(0, 0, 0, 0.1), transparent);
    }

    .icon-small {
      width: 16px;
      height: 16px;
      display: inline-block;
      vertical-align: middle;
    }

    {{-- Wrapper para manejar el badge sin desbordamiento --}}
    .mejor-oferta-wrapper {
      position: relative;
      margin-bottom: 1rem;
      display: flex;
      flex-direction: column;
      height: 100%;
    }

    {{-- Estilos para el contenedor de mejores ofertas con efecto gaming --}}
    .mejor-oferta-gaming-container {
      position: relative;
      overflow: hidden;
      border: 2px solid transparent;
      background: linear-gradient(45deg, #ff6b6b, #4ecdc4, #45b7d1, #96ceb4, #feca57, #ff9ff3, #54a0ff, #5f27cd);
      background-size: 400% 400%;
      animation: gamingBorder 3s ease-in-out infinite;
      box-shadow: 0 0 20px rgba(255, 107, 107, 0.3);
      border-radius: 0.75rem;
      padding: 2px;
    }

    .mejor-oferta-gaming-container::before {
      content: '';
      position: absolute;
      top: 2px;
      left: 2px;
      right: 2px;
      bottom: 2px;
      background: #f8f9fa;
      border-radius: 0.5rem;
      z-index: 1;
    }

    .mejor-oferta-gaming-container > * {
      position: relative;
      z-index: 2;
    }

    {{-- Contenedor interno para mantener el espaciado --}}
    .mejor-oferta-gaming-container .ofertas-grupo {
      display: flex;
      flex-direction: column;
      gap: 0.5rem;
    }

    .mejor-oferta-badge-grupo {
      position: absolute;
      top: -8px;
      right: 10px;
      background: linear-gradient(45deg, #ff6b6b, #4ecdc4, #45b7d1);
      background-size: 200% 200%;
      animation: gamingBadge 2s ease-in-out infinite;
      color: white;
      font-size: 0.75rem;
      font-weight: bold;
      padding: 4px 8px;
      border-radius: 12px;
      z-index: 10;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
      transform: scale(0.9);
      pointer-events: none;
    }

    {{-- Mantener los estilos originales por compatibilidad --}}
    .mejor-oferta-gaming {
      position: relative;
      overflow: hidden;
      border: 2px solid transparent;
      background: linear-gradient(45deg, #ff6b6b, #4ecdc4, #45b7d1, #96ceb4, #feca57, #ff9ff3, #54a0ff, #5f27cd);
      background-size: 400% 400%;
      animation: gamingBorder 3s ease-in-out infinite;
      box-shadow: 0 0 20px rgba(255, 107, 107, 0.3);
    }

    .mejor-oferta-gaming::before {
      content: '';
      position: absolute;
      top: 2px;
      left: 2px;
      right: 2px;
      bottom: 2px;
      background: white;
      border-radius: 0.5rem;
      z-index: 1;
    }

    .mejor-oferta-gaming > * {
      position: relative;
      z-index: 2;
    }

    .mejor-oferta-badge {
      position: absolute;
      top: -8px;
      right: 10px;
      background: linear-gradient(45deg, #ff6b6b, #4ecdc4, #45b7d1);
      background-size: 200% 200%;
      animation: gamingBadge 2s ease-in-out infinite;
      color: white;
      font-size: 0.75rem;
      font-weight: bold;
      padding: 4px 8px;
      border-radius: 12px;
      z-index: 10;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
      transform: scale(0.9);
    }

    @keyframes gamingBorder {
      0% {
        background-position: 0% 50%;
        box-shadow: 0 0 20px rgba(255, 107, 107, 0.3);
      }
      25% {
        background-position: 100% 50%;
        box-shadow: 0 0 20px rgba(78, 205, 196, 0.3);
      }
      50% {
        background-position: 100% 100%;
        box-shadow: 0 0 20px rgba(69, 183, 209, 0.3);
      }
      75% {
        background-position: 0% 100%;
        box-shadow: 0 0 20px rgba(150, 206, 180, 0.3);
      }
      100% {
        background-position: 0% 50%;
        box-shadow: 0 0 20px rgba(255, 107, 107, 0.3);
      }
    }

    @keyframes gamingBadge {
      0% {
        background-position: 0% 50%;
        transform: scale(0.9) rotate(-2deg);
      }
      50% {
        background-position: 100% 50%;
        transform: scale(1.05) rotate(2deg);
      }
      100% {
        background-position: 0% 50%;
        transform: scale(0.9) rotate(-2deg);
      }
    }

    {{-- Efecto de brillo solo en el botón --}}
    .mejor-oferta-gaming .boton,
    .mejor-oferta-gaming-container .boton {
      position: relative;
      overflow: hidden;
    }

    .mejor-oferta-gaming .boton::after,
    .mejor-oferta-gaming-container .boton::after {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
      animation: shine 2s infinite;
      z-index: 3;
    }

    @keyframes shine {
      0% {
        left: -100%;
      }
      100% {
        left: 100%;
      }
    }

    @media (max-width: 1024px) {
      .bloque-movil-scroll {
        display: flex;
        overflow-x: auto;
        gap: 0.75rem;
        padding-bottom: 0.5rem;
        scroll-snap-type: x mandatory;
      }

      .bloque-movil-scroll>* {
        flex: 0 0 auto;
        scroll-snap-align: start;
      }

      .product-card {
        display: grid !important;
        grid-template-columns: repeat(3, 1fr);
        grid-template-areas:
          "logo precio-und precio-und"
          "envio und precio-total"
          "boton boton boton";
        gap: 0.5rem;
        padding: 1rem;
      }

      .logo {
        grid-area: logo;
        display: flex;
        justify-content: center;
        align-items: center;
      }

      .logo img {
        display: block;
        margin: 0 auto;
      }

      .precio-und {
        grid-area: precio-und;
        text-align: center !important;
        align-self: center;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
      }

      .envio {
        grid-area: envio;
        text-align: center;
      }

      .und {
        grid-area: und;
        text-align: center;
      }

      .precio-total {
        grid-area: precio-total;
        text-align: center;
      }

      .boton {
        grid-area: boton;
        text-align: center;
      }

      .divider::after {
        display: none;
      }

      {{-- Ajustes para móvil --}}
      .mejor-oferta-badge {
        font-size: 0.7rem;
        padding: 3px 6px;
        top: -6px;
        right: 8px;
      }

      .mejor-oferta-badge-grupo {
        font-size: 0.7rem;
        padding: 3px 6px;
        top: -6px;
        right: 8px;
      }

      .mejor-oferta-gaming,
      .mejor-oferta-gaming-container {
        border-width: 1px;
      }

      .mejor-oferta-gaming::before,
      .mejor-oferta-gaming-container::before {
        top: 1px;
        left: 1px;
        right: 1px;
        bottom: 1px;
      }

      .mejor-oferta-gaming-container {
        padding: 1px;
        margin-bottom: 0.75rem;
      }

      .mejor-oferta-gaming-container .ofertas-grupo {
        gap: 0.25rem;
      }
    }

    {{-- Estilos para el contenido HTML de la descripción --}}
    .descripcion-contenido .descripcion-titulo {
      font-size: 1.1rem !important;
      font-weight: 600 !important;
      color: #4B5563 !important;
      margin-bottom: 0.75rem !important;
      margin-top: 0 !important;
      line-height: 1.4 !important;
      display: block !important;
    }

    .descripcion-contenido p {
      margin-bottom: 1rem !important;
      line-height: 1.6 !important;
      display: block !important;
      font-size: 1.05rem !important;
      color: #374151 !important;
    }

    .descripcion-contenido p:last-child {
      margin-bottom: 0 !important;
    }

    {{-- Estilos para la ventana emergente de cupón --}}
    .cupon-modal-overlay {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.5);
      display: flex;
      justify-content: center;
      align-items: center;
      z-index: 1000;
      opacity: 0;
      visibility: hidden;
      transition: all 0.3s ease;
    }

    .cupon-modal-overlay.show {
      opacity: 1;
      visibility: visible;
    }

    .cupon-modal {
      background: white;
      border-radius: 12px;
      padding: 2rem;
      max-width: 500px;
      width: 90%;
      box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
      transform: scale(0.9);
      transition: transform 0.3s ease;
      position: relative;
    }

    .cupon-modal-close {
      position: absolute;
      top: 1rem;
      right: 1rem;
      background: none;
      border: none;
      color: #6b7280;
      cursor: pointer;
      padding: 0.5rem;
      border-radius: 50%;
      transition: all 0.2s ease;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .cupon-modal-close:hover {
      background-color: #f3f4f6;
      color: #374151;
    }

    .cupon-modal-overlay.show .cupon-modal {
      transform: scale(1);
    }

    .cupon-modal-header {
      text-align: center;
      margin-bottom: 1.5rem;
    }

    .cupon-modal-icon {
      width: 48px;
      height: 48px;
      background: linear-gradient(135deg, #f9a8d4 0%, #f472b6 100%);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 1rem;
    }

    .cupon-modal-title {
      font-size: 1.25rem;
      font-weight: 700;
      color: #1f2937;
      margin-bottom: 0.5rem;
    }

    .cupon-modal-text {
      color: #6b7280;
      line-height: 1.5;
      margin-bottom: 1.5rem;
    }

    .cupon-modal-buttons {
      display: flex;
      gap: 0.75rem;
      justify-content: center;
    }

    .cupon-btn {
      padding: 0.75rem 1.5rem;
      border-radius: 8px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.2s ease;
      border: none;
      text-decoration: none;
      display: inline-block;
      text-align: center;
    }

    .cupon-btn-primary {
      background: linear-gradient(135deg, #f9a8d4 0%, #f472b6 100%);
      color: white;
    }

    .cupon-btn-primary:hover {
      background: linear-gradient(135deg, #f472b6 0%, #ec4899 100%);
      transform: translateY(-1px);
      box-shadow: 0 10px 15px -3px rgba(244, 114, 182, 0.3);
    }

    .cupon-btn-secondary {
      background: #f3f4f6;
      color: #374151;
      border: 1px solid #d1d5db;
    }

    .cupon-btn-secondary:hover {
      background: #e5e7eb;
    }

    .cupon-indicator {
      background: linear-gradient(135deg, #f59e0b, #d97706);
      color: white;
      font-size: 0.75rem;
      font-weight: 600;
      padding: 0.25rem 0.5rem;
      border-radius: 4px;
      margin-bottom: 0.5rem;
      display: inline-block;
      animation: pulse 2s infinite;
    }

    {{-- Badge de cupón superpuesto --}}
    .cupon-badge {
      position: absolute;
      top: -12px;
      right: 8px;
      background: linear-gradient(135deg, #f59e0b, #d97706);
      color: white;
      font-size: 0.6rem;
      font-weight: 700;
      padding: 3px 6px;
      border-radius: 4px;
      z-index: 10;
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
      pointer-events: none;
      transform: scale(0.9);
    }


    @keyframes pulse {
      0%, 100% { opacity: 1; }
      50% { opacity: 0.8; }
    }

    @media (max-width: 640px) {
      .cupon-modal {
        padding: 1.5rem;
        margin: 1rem;
      }
      
      .cupon-modal-buttons {
        flex-direction: column;
      }
      
      .cupon-btn {
        width: 100%;
      }

      .cupon-split-button {
        height: 48px;
        min-height: 48px;
        font-size: 0.875rem;
      }

      .cupon-left, .cupon-right {
        font-size: 0.875rem;
        padding: 12px 8px;
        min-height: 48px;
      }
    }

    {{-- Reducir tamaño de texto de categorías solo en móvil --}}
    @media (max-width: 640px) {
      .categoria-texto-movil {
        font-size: 0.7rem !important;
        line-height: 1.2;
      }
    }
    {{-- Forzar reset de order y col-span en desktop para productos unidad única --}}
    @media (min-width: 1024px) {
      {{-- Reset general para todos los elementos con order --}}
      .product-card .logo[class*="order-"],
      .product-card .envio[class*="order-"],
      .product-card .precio-total[class*="order-"],
      .product-card .precio-und[class*="order-"],
      .product-card .boton[class*="order-"],
      .product-card .columna-dinamica[class*="order-"] {
        order: 0 !important;
        grid-row: auto !important;
        grid-column: auto !important;
      }
      .product-card .boton[class*="col-span-"] {
        grid-column: span 1 / span 1 !important;
      }
      
      {{-- Reset específico para unidad única sin columnas en PC --}}
      .product-card.grid-cols-3 .envio[class*="order-2"] {
        order: 0 !important;
        grid-row: auto !important;
        grid-column: auto !important;
      }
      .product-card.grid-cols-3 .precio-total[class*="order-3"] {
        order: 0 !important;
        grid-row: auto !important;
        grid-column: auto !important;
      }
      .product-card.grid-cols-3 .boton[class*="order-4"] {
        order: 0 !important;
        grid-row: auto !important;
        grid-column: auto !important;
      }
      
      {{-- Resetear grid-template-columns para unidad única sin columnas en PC --}}
      .product-card.grid-cols-3[class*="sm:grid-cols-[100px_1fr_1fr_auto]"] {
        grid-template-columns: 100px 1fr 1fr auto !important;
      }
      
      {{-- Estilo para precio total en PC cuando es unidad única sin columnas --}}
      .product-card .precio-total.precio-unidad-unica-sin-columnas > div:last-child {
        font-size: 1.875rem !important;
        font-weight: 800 !important;
        color: #f472b6 !important;
      }
      .product-card .precio-total.precio-unidad-unica-sin-columnas > div:last-child > span {
        font-size: 0.875rem !important;
      }
      
      {{-- Añadir espaciado entre el label y el número del precio en PC para unidad única sin columnas --}}
      .product-card .precio-total.precio-unidad-unica-sin-columnas > div:first-child {
        margin-bottom: 0.5rem !important;
      }
    }
    
    {{-- Forzar orden correcto en móvil y tablet para productos unidad única usando grid-row --}}
    @media (max-width: 1023px) {
      {{-- Forzar que grid-cols-2 tenga exactamente 2 columnas --}}
      .product-card.grid-cols-2 {
        grid-template-columns: repeat(2, 1fr) !important;
      }
      
      {{-- Para grid-cols-2 (1 columna dinámica): 2 columnas en ambas filas --}}
      {{-- Primera fila: Logo 50% + Precio 50% --}}
      .product-card.grid-cols-2 .logo[class*="order-1"] {
        order: 1 !important;
        grid-row: 1 !important;
        grid-column: 1 !important;
      }
      .product-card.grid-cols-2 .precio-total[class*="order-2"],
      .product-card.grid-cols-2 .precio-total:not([class*="order-"]) {
        order: 2 !important;
        grid-row: 1 !important;
        grid-column: 2 !important;
      }
      
      {{-- Segunda fila: Envío 50% + Columna 50% --}}
      .product-card.grid-cols-2 .envio[class*="order-3"] {
        order: 3 !important;
        grid-row: 2 !important;
        grid-column: 1 !important;
      }
      .product-card.grid-cols-2 .columna-dinamica[class*="order-4"] {
        order: 4 !important;
        grid-row: 2 !important;
        grid-column: 2 !important;
      }
      
      {{-- Forzar que grid-cols-3 tenga exactamente 3 columnas --}}
      .product-card.grid-cols-3 {
        grid-template-columns: repeat(3, 1fr) !important;
      }
      
      {{-- Logo siempre en primera fila, primera columna para grid-cols-3 --}}
      .product-card.grid-cols-3 .logo[class*="order-1"] {
        order: 1 !important;
        grid-row: 1 !important;
        grid-column: 1 !important;
      }
      
      {{-- Para grid-cols-3 CON columnas dinámicas (2, 3 o 4 columnas): 3 columnas en segunda fila --}}
      {{-- Primera fila: Logo columna 1, Precio desde columna 2 hasta el final (cuando precio tiene order-2 y envío tiene order-3) --}}
      .product-card.grid-cols-3 .precio-total[class*="order-2"]:not([class*="order-3"]) {
        order: 2 !important;
        grid-row: 1 !important;
        grid-column: 2 / -1 !important;
      }
      
      {{-- Segunda fila: Envío 33% + Columnas 33% cada una (solo cuando hay columnas dinámicas - envío tiene order-3) --}}
      .product-card.grid-cols-3 .envio[class*="order-3"] {
        order: 3 !important;
        grid-row: 2 !important;
        grid-column: 1 !important;
      }
      .product-card.grid-cols-3 .columna-dinamica[class*="order-4"] {
        order: 4 !important;
        grid-row: 2 !important;
        grid-column: 2 !important;
      }
      .product-card.grid-cols-3 .columna-dinamica[class*="order-5"] {
        order: 5 !important;
        grid-row: 2 !important;
        grid-column: 3 !important;
      }
      
      {{-- Para grid-cols-3 SIN columnas dinámicas (unidad única sin columnas): Primera fila (Logo order-1, Envío order-2, Precio order-3), Segunda fila (Botón order-4) --}}
      {{-- Cuando envío tiene order-2 (sin columnas) --}}
      .product-card.grid-cols-3 .envio[class*="order-2"] {
        order: 2 !important;
        grid-row: 1 !important;
        grid-column: 2 !important;
      }
      {{-- Cuando precio tiene order-3 (sin columnas) --}}
      .product-card.grid-cols-3 .precio-total[class*="order-3"] {
        order: 3 !important;
        grid-row: 1 !important;
        grid-column: 3 !important;
      }
      
      {{-- Botón: ocupa toda la fila (fuerza col-span completo) --}}
      .product-card[class*="grid-cols"] .boton[class*="order-4"],
      .product-card[class*="grid-cols"] .boton[class*="order-5"],
      .product-card[class*="grid-cols"] .boton[class*="order-6"] {
        grid-column: 1 / -1 !important;
        width: 100% !important;
        max-width: 100% !important;
        padding-left: 0 !important;
        padding-right: 0 !important;
      }
      
      {{-- Ajustar fila del botón según su order --}}
      .product-card.grid-cols-3 .boton[class*="order-4"] {
        order: 4 !important;
        grid-row: 2 !important;
      }
      .product-card[class*="grid-cols"] .boton[class*="order-5"],
      .product-card[class*="grid-cols"] .boton[class*="order-6"] {
        order: 6 !important;
        grid-row: 3 !important;
      }
      
      {{-- Asegurar que el contenido del botón también ocupe todo el ancho --}}
      .product-card[class*="grid-cols"] .boton[class*="order-4"] > span,
      .product-card[class*="grid-cols"] .boton[class*="order-5"] > span,
      .product-card[class*="grid-cols"] .boton[class*="order-6"] > span {
        width: 100% !important;
        display: block !important;
      }
      
      {{-- Limitar el ancho de la etiqueta cupon-badge en móvil para que no se alargue --}}
      .product-card[class*="grid-cols"] .boton[class*="order-4"] .cupon-badge,
      .product-card[class*="grid-cols"] .boton[class*="order-5"] .cupon-badge,
      .product-card[class*="grid-cols"] .boton[class*="order-6"] .cupon-badge {
        width: auto !important;
        max-width: fit-content !important;
        right: 0 !important;
        left: auto !important;
      }
      
      {{-- Estilo para precio total en móvil y tablet cuando es unidad única sin columnas --}}
      .product-card .precio-total.precio-unidad-unica-sin-columnas > div:last-child {
        font-size: 1.5rem !important;
        font-weight: 800 !important;
        color: #f472b6 !important;
      }
      .product-card .precio-total.precio-unidad-unica-sin-columnas > div:last-child > span {
        font-size: 0.875rem !important;
      }
    }
    
    {{-- Estilos para el modal de imágenes --}}
    {{-- x18: Modal de imágenes --}}
    #x18 {
      backdrop-filter: blur(4px);
    }
    
    #x18 .miniatura-imagen {
      cursor: pointer;
      transition: all 0.2s;
      border: 4px solid transparent;
      border-radius: 0.5rem;
      padding: 2px;
    }
    
    #x18 .miniatura-imagen:hover {
      border-color: #60a5fa;
      transform: scale(1.05);
    }
    
    #x18 .miniatura-imagen.activa {
      border-color: #3b82f6;
      box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.4);
      transform: scale(1.05);
    }
    
    #x18 .miniatura-imagen img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      border-radius: 0.375rem;
    }
    
    {{-- Estilos para miniaturas en la página (no en el modal) --}}
    .miniatura-pagina-desktop,
    .miniatura-pagina-movil {
      cursor: pointer;
      transition: all 0.2s;
      border: 3px solid transparent;
      border-radius: 0.5rem;
      padding: 2px;
      flex-shrink: 0;
    }
    
    .miniatura-pagina-desktop:hover,
    .miniatura-pagina-movil:hover {
      border-color: #60a5fa;
      transform: scale(1.05);
    }
    
    .miniatura-pagina-desktop.activa,
    .miniatura-pagina-movil.activa {
      border-color: #3b82f6;
      box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.4);
      transform: scale(1.05);
    }
    
    .miniatura-pagina-desktop img,
    .miniatura-pagina-movil img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      border-radius: 0.375rem;
    }
    
    #miniaturas-container-movil {
      scrollbar-width: thin;
      scrollbar-color: #cbd5e0 transparent;
    }
    
    #miniaturas-container-movil::-webkit-scrollbar {
      height: 6px;
    }
    
    #miniaturas-container-movil::-webkit-scrollbar-track {
      background: transparent;
    }
    
    #miniaturas-container-movil::-webkit-scrollbar-thumb {
      background-color: #cbd5e0;
      border-radius: 3px;
    }
    {{-- Estilos para el carrusel de sublíneas --}}
    .scrollbar-hide {
      -ms-overflow-style: none;
      scrollbar-width: none;
    }
    
    .scrollbar-hide::-webkit-scrollbar {
      display: none;
    }
    
    .sublineas-carrusel-container {
      position: relative;
    }
    
    .sublineas-carrusel {
      {{-- Mejoras para desplazamiento táctil en móvil --}}
      touch-action: pan-x;
      overscroll-behavior-x: contain;
      -webkit-overflow-scrolling: touch;
      scroll-snap-type: x proximity;
    }
    
    .sublineas-carrusel button {
      scroll-snap-align: start;
    }
    
    {{-- Ocultar botones en móvil, mostrar solo en desktop --}}
    .carrusel-btn-left,
    .carrusel-btn-right {
      display: none;
    }
    
    @media (min-width: 640px) {
      .carrusel-btn-left,
      .carrusel-btn-right {
        display: block;
      }
      
      .sublineas-carrusel-container:hover .carrusel-btn-left,
      .sublineas-carrusel-container:hover .carrusel-btn-right {
        opacity: 1;
      }
    }
  </style>
</head>

<body class="bg-gray-50">
  @php
  // Los precios se calcularán en JavaScript después de aplicar descuentos
  $precioMin = null;
  $precioMax = null;
  
  // Función para añadir parámetro cam a URLs de forma segura
  if (!function_exists('añadirCam')) {
    function añadirCam($url) {
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



  {{-- HEADER --}}
  <x-header />

  {{-- BARRA DE CATEGORÍAS Y PANEL LATERAL --}}
  <x-listado-categorias-horizontal-head />
    
  <div class="max-w-7xl mx-auto flex flex-col gap-2 py-2 px-4 bg-gray-100">
    {{-- BREADCRUMB --}}
<nav class="mb-1">
    <ol class="flex items-center space-x-1 text-sm text-gray-600">
        <li>
            <a href="{{ añadirCam(route('home')) }}" class="hover:text-pink-600">Inicio</a>
        </li>
        @foreach($breadcrumb as $item)
        <li class="flex items-center">
            <svg class="w-4 h-4 mx-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
            </svg>
            <a href="{{ añadirCam(url('categoria/' . $item['slug'])) }}" class="hover:text-pink-600">{{ $item['nombre'] }}</a>
        </li>
        @endforeach
    </ol>
</nav>

    {{-- BLOQUE MÓVIL ÚNICAMENTE --}}
    <h1 class="text-xl font-bold mb-1 block lg:hidden">{{ $producto->titulo}}</h1>

    <div class="block lg:hidden bg-white rounded-lg shadow pt-2 pb-4 px-4">
      {{-- Primera fila: Imagen del producto con miniaturas a los lados --}}
      <div class="flex items-center justify-center gap-2 mb-4">
        {{-- Miniaturas izquierda --}}
        <div id="carrusel-miniaturas-movil-izq" class="flex flex-col gap-2">
          {{-- Las miniaturas se insertarán aquí dinámicamente --}}
        </div>
        {{-- Imagen principal --}}
        <div class="flex-1 flex justify-center">
          <img src="{{ asset('images/' . ($producto->imagen_pequena[0] ?? '')) }}" loading="lazy" alt="Imagen de {{$producto->nombre}}" class="w-28 h-28 object-contain cursor-pointer" id="imagen-producto-movil" onclick="_am1()">
        </div>
        {{-- Miniaturas derecha --}}
        <div id="carrusel-miniaturas-movil-der" class="flex flex-col gap-2">
          {{-- Las miniaturas se insertarán aquí dinámicamente --}}
        </div>
      </div>
      
      {{-- Segunda fila: Las mejores ofertas (máximo 4, distribuidas en filas de 2) --}}
      <div id="mejor-oferta-grid-movil">
        {{-- Primera fila de ofertas --}}
        <div class="flex gap-2 items-start mb-2">
          {{-- Primera oferta --}}
          <div class="flex-1" id="mejor-oferta-contenedor-movil-1">
            <div class="flex items-center justify-center mb-1">
              <div id="mejor-oferta-logo-movil-1" class="flex items-center justify-center">
                <div class="w-12 h-5 bg-gray-200 rounded animate-pulse"></div>
              </div>
            </div>
            <div class="text-center">
              <div id="mejor-oferta-precio-movil-1" class="text-lg font-extrabold text-pink-400 mb-1">
                <div class="w-12 h-6 bg-gray-200 rounded animate-pulse mx-auto"></div>
              </div>
              <div id="mejor-oferta-boton-movil-1" class="w-full">
                <div class="w-full h-6 bg-gray-200 rounded animate-pulse"></div>
              </div>
            </div>
          </div>
          
          {{-- Segunda oferta --}}
          <div class="flex-1" id="mejor-oferta-contenedor-movil-2">
            <div class="flex items-center justify-center mb-1">
              <div id="mejor-oferta-logo-movil-2" class="flex items-center justify-center">
                <div class="w-12 h-5 bg-gray-200 rounded animate-pulse"></div>
              </div>
            </div>
            <div class="text-center">
              <div id="mejor-oferta-precio-movil-2" class="text-lg font-extrabold text-pink-400 mb-1">
                <div class="w-12 h-6 bg-gray-200 rounded animate-pulse mx-auto"></div>
              </div>
              <div id="mejor-oferta-boton-movil-2" class="w-full">
                <div class="w-full h-6 bg-gray-200 rounded animate-pulse"></div>
              </div>
            </div>
          </div>
        </div>
        
        {{-- Segunda fila de ofertas --}}
        <div class="flex gap-2 items-start mb-2 mt-1">
          {{-- Tercera oferta --}}
          <div class="flex-1" id="mejor-oferta-contenedor-movil-3" style="display: none;">
            <div class="flex items-center justify-center mb-1">
              <div id="mejor-oferta-logo-movil-3" class="flex items-center justify-center">
                <div class="w-12 h-5 bg-gray-200 rounded animate-pulse"></div>
              </div>
            </div>
            <div class="text-center">
              <div id="mejor-oferta-precio-movil-3" class="text-lg font-extrabold text-pink-400 mb-1">
                <div class="w-12 h-6 bg-gray-200 rounded animate-pulse mx-auto"></div>
              </div>
              <div id="mejor-oferta-boton-movil-3" class="w-full">
                <div class="w-full h-6 bg-gray-200 rounded animate-pulse"></div>
              </div>
            </div>
          </div>
          
          {{-- Cuarta oferta --}}
          <div class="flex-1" id="mejor-oferta-contenedor-movil-4" style="display: none;">
            <div class="flex items-center justify-center mb-1">
              <div id="mejor-oferta-logo-movil-4" class="flex items-center justify-center">
                <div class="w-12 h-5 bg-gray-200 rounded animate-pulse"></div>
              </div>
            </div>
            <div class="text-center">
              <div id="mejor-oferta-precio-movil-4" class="text-lg font-extrabold text-pink-400 mb-1">
                <div class="w-12 h-6 bg-gray-200 rounded animate-pulse mx-auto"></div>
              </div>
              <div id="mejor-oferta-boton-movil-4" class="w-full">
                <div class="w-full h-6 bg-gray-200 rounded animate-pulse"></div>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      {{-- Botón para ver todas las ofertas --}}
      <a href="#listado-precios" class="block w-full text-center py-3 bg-pink-500 text-white rounded font-semibold hover:bg-pink-600 mb-4">
        Ver todas las ofertas ({{ count($ofertas) }})
      </a>
      {{-- x10: Descripción corta (móvil) --}}
      <div class="text-sm text-gray-600 leading-relaxed relative overflow-hidden max-h-[3em] my-4" id="x10">
        {{ $producto->descripcion_corta}}
        {{-- x11: Botón leer más (móvil) --}}
        <span class="absolute bottom-0 right-0 bg-white pl-2 text-blue-500 font-semibold cursor-pointer" id="x11">Leer más</span>
      </div>
      {{-- x12: Descripción completa (móvil) --}}
      <div class="text-sm text-gray-600 leading-relaxed hidden my-4" id="x12">
        {{ $producto->descripcion_corta}}
        {{-- x13: Botón leer menos (móvil) --}}
        <span class="text-blue-500 font-semibold cursor-pointer" id="x13">Leer menos</span>
      </div>
      <div class="bloque-movil-scroll overflow-x-auto whitespace-nowrap pb-2">
        @foreach ($relacionados as $relacionado)
        <a href="{{ añadirCam($relacionado->categoria->construirUrlCategorias($relacionado->slug)) }}" class="inline-block align-top bg-gray-50 rounded-lg border p-3 w-36 text-center card-hover hover:bg-gray-100">
          <img src="{{ asset('images/' . ($relacionado->imagen_pequena[0] ?? '')) }}" loading="lazy" alt="{{$relacionado->nombre}}" class="w-full h-auto object-contain mb-2">
          <div class="text-sm font-semibold">{{ $relacionado->talla }}</div>
          <div class="text-sm text-pink-500">{{ number_format($relacionado->precio, 2, ',', '.') }}
            @if($relacionado->unidadDeMedida === 'kilos')
              /Kg.
            @elseif($relacionado->unidadDeMedida === 'litros')
              /L.
            @elseif($relacionado->unidadDeMedida === 'unidadMilesima')
              /Und.
            @elseif($relacionado->unidadDeMedida === 'unidadUnica')
              {{-- No mostrar sufijo --}}
            @elseif($relacionado->unidadDeMedida === 'unidad')
              /Und.
            @elseif($relacionado->unidadDeMedida === '800gramos')
              /800gr.
            @elseif($relacionado->unidadDeMedida === '100ml')
              /100ml.
            @endif
          </div>
        </a>
        @endforeach
      </div>
    </div>

    {{-- BLOQUE DESKTOP ÚNICAMENTE --}}
    <div class="hidden lg:grid grid-cols-4 gap-4 mb-4">
      <div class="bg-white rounded-lg shadow p-4 flex flex-col items-center justify-center">
        <img src="{{ asset('images/' . ($producto->imagen_pequena[0] ?? '')) }}" alt="Imagen de {{$producto->nombre}}" class="max-h-60 object-contain cursor-pointer mb-3" id="imagen-producto-desktop" onclick="_am1()">
        {{-- Carrusel de miniaturas desktop --}}
        <div id="carrusel-miniaturas-desktop" class="flex gap-2 justify-center items-center w-full flex-wrap" style="max-width: 100%;">
          {{-- Las miniaturas se insertarán aquí dinámicamente --}}
        </div>
      </div>

      <div class="col-span-2 bg-white rounded-lg shadow overflow-hidden flex flex-col">
        <div class="pt-4 pb-2 px-4">
          <h1 class="text-xl font-bold mb-2">{{ $producto->titulo}}</h1>
          {{-- x14: Descripción corta (desktop) --}}
          <div class="text-gray-600 text-sm leading-relaxed relative overflow-hidden max-h-[3em]" id="x14">
            {{ $producto->descripcion_corta}}
            {{-- x15: Botón leer más (desktop) --}}
            <span class="absolute bottom-0 right-0 bg-white pl-2 text-blue-500 font-semibold cursor-pointer" id="x15">Leer más</span>
          </div>
          {{-- x16: Descripción completa (desktop) --}}
          <div class="text-gray-600 text-sm leading-relaxed hidden" id="x16">
            {{ $producto->descripcion_corta}}
            {{-- x17: Botón leer menos (desktop) --}}
            <span class="text-blue-500 font-semibold cursor-pointer" id="x17">Leer menos</span>
          </div>
        </div>

        {{-- LÍNEA CON TEXTO EN MEDIO --}}
        <div class="flex items-center px-4 mt-2 mb-4 gap-2">
          <div class="flex-grow border-t border-gray-200"></div>
          <h2 class="text-base font-semibold text-gray-700 whitespace-nowrap">Ofertas similares a {{ $producto->marca }} {{ $producto->modelo }} {{ $producto->talla }}</h2>
          <div class="flex-grow border-t border-gray-200"></div>
        </div>

        {{-- CARRUSEL DE PRODUCTOS --}}
        <div class="overflow-x-auto whitespace-nowrap pb-2 px-4">
          @foreach ($relacionados as $relacionado)
          <a href="{{ añadirCam($relacionado->categoria->construirUrlCategorias($relacionado->slug)) }}" class="inline-block align-top card-hover p-4 border border-gray-200 rounded-lg text-center hover:bg-gray-50 transition-transform duration-200 w-[160px] mr-2">
            <div class="flex justify-center mb-2">
              <img src="{{ asset('images/' . ($relacionado->imagen_pequena[0] ?? '')) }}" loading="lazy" alt="{{$relacionado->nombre}}" class="w-[94px] max-w-full h-auto object-contain">
            </div>
            <div class="text-sm font-semibold text-black-700">{{ $relacionado->marca }} - {{ $relacionado->talla }}</div>
            <div class="text-xl font-bold text-pink-500">{{ number_format($relacionado->precio, 2) }}€
    @if($relacionado->unidadDeMedida === 'unidad')
        <span class="text-xs text-gray-500">/Und.</span>
    @elseif($relacionado->unidadDeMedida === 'kilos')
        <span class="text-xs text-gray-500">/Kg.</span>
    @elseif($relacionado->unidadDeMedida === 'litros')
        <span class="text-xs text-gray-500">/L.</span>
    @elseif($relacionado->unidadDeMedida === 'unidadMilesima')
        <span class="text-xs text-gray-500">/Und.</span>
    @elseif($relacionado->unidadDeMedida === 'unidadUnica')
        {{-- No mostrar sufijo --}}
    @elseif($relacionado->unidadDeMedida === '800gramos')
        <span class="text-xs text-gray-500">/800gr.</span>
    @elseif($relacionado->unidadDeMedida === '100ml')
        <span class="text-xs text-gray-500">/100ml.</span>
    @endif
          </div>
          </a>
          @endforeach
        </div>
      </div>
      
      {{-- GRAFICO HISTORICO PRECIO DESKTOP--}}
      <div class="bg-white rounded-lg shadow p-4" style="height: 360px;">
        <div class="flex justify-center items-center mb-4">
          <div class="flex space-x-2">
            <button id="btn-3m" class="px-3 py-1 text-sm border rounded hover:bg-gray-100 transition-colors" data-periodo="3m">3M</button>
            <button id="btn-6m" class="px-3 py-1 text-sm border rounded hover:bg-gray-100 transition-colors" data-periodo="6m">6M</button>
            <button id="btn-9m" class="px-3 py-1 text-sm border rounded hover:bg-gray-100 transition-colors" data-periodo="9m">9M</button>
            <button id="btn-1y" class="px-3 py-1 text-sm border rounded hover:bg-gray-100 transition-colors" data-periodo="1y">1A</button>
          </div>
        </div>
        <div class="relative" style="height: 280px;">
          <canvas id="graficoPrecios" class="w-full h-full"></canvas>
        </div>
      </div>
    </div>

    <div class="flex flex-col-reverse lg:flex-row gap-6">
      <aside class="w-full lg:w-1/4">


        {{-- FORMULARIO DE ALERTA DE PRECIO - DISEÑO AZUL-PÚRPURA --}}
        <div class="bg-gradient-to-br from-blue-500 to-purple-600 shadow-lg rounded-lg p-4 mb-6 text-white">
          <div class="flex items-center mb-3">
            <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5v-5z"></path>
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
            </svg>
            <h3 class="text-lg font-bold">¡Avísame si baja de precio!</h3>
          </div>
          
          <form id="formAlertaPrecio2" class="space-y-4">
            <div>
              <label for="correo_alerta2" class="block text-sm font-medium text-blue-100 mb-1">Tu email</label>
              <input type="email" id="correo_alerta2" name="correo" required 
                     class="w-full px-3 py-2 bg-white/20 border border-white/30 rounded-md focus:outline-none focus:ring-2 focus:ring-white focus:border-transparent text-white placeholder-blue-200"
                     placeholder="tu@email.com">
            </div>
            
            <div>
              <label for="precio_limite2" class="block text-sm font-medium text-blue-100 mb-1">
                @if($producto->unidadDeMedida === 'unidadUnica')
                    Precio máximo que pagarías
                @else
                    Precio máximo que pagarías por 
                    @if($producto->unidadDeMedida === 'unidad')
                        unidad
                    @elseif($producto->unidadDeMedida === 'kilos')
                        kilo
                    @elseif($producto->unidadDeMedida === 'litros')
                        litro
                    @elseif($producto->unidadDeMedida === 'unidadMilesima')
                        unidad
                    @elseif($producto->unidadDeMedida === '800gramos')
                        800 gramos
                    @elseif($producto->unidadDeMedida === '100ml')
                        100 ml
                    @else
                        unidad
                    @endif
                @endif
              </label>
              <input type="number" id="precio_limite2" name="precio_limite" step="0.01" min="0" required 
                     class="w-full px-3 py-2 bg-white/20 border border-white/30 rounded-md focus:outline-none focus:ring-2 focus:ring-white focus:border-transparent text-white placeholder-blue-200"
                     value="{{ number_format($producto->precio, 2, '.', '') }}">
            </div>
            
            <div class="flex items-start space-x-2">
              <input type="checkbox" id="acepto_politicas2" name="acepto_politicas" required 
                     class="mt-1 h-4 w-4 text-blue-600 focus:ring-blue-500 border-white/30 rounded bg-white/20">
              <label for="acepto_politicas2" class="text-sm text-blue-100">
                Acepto las <a href="{{ route('politicas.privacidad') }}" target="_blank" class="text-yellow-300 hover:text-yellow-200 underline font-semibold">políticas de privacidad</a>. 
                <span class="text-xs text-blue-200 block mt-1">Solo te avisaremos cuando baje el precio, nada de spam, ni publicidad👉👈.</span>
              </label>
            </div>
            
            <input type="hidden" name="producto_id" value="{{ $producto->id }}">
            
            <button type="submit" 
                    class="w-full bg-yellow-400 hover:bg-yellow-300 text-gray-800 font-bold py-3 px-4 rounded-md transition-all duration-200 transform hover:scale-105 shadow-lg">
              ⚡ ¡Activar alerta ahora!
            </button>
          </form>
          
          <div id="mensajeAlerta2" class="mt-3 text-sm hidden"></div>
        </div>

        <div class="bg-white shadow rounded-lg p-4">
          {{-- Productos por debajo del precio medio --}}
          @if($productosPrecioMedio->count() > 0)
          <div class="mb-6">
            <h3 class="text-lg font-semibold mb-3 text-gray-800 border-b border-gray-200 pb-2">Productos Rebajados</h3>
            <div class="space-y-2">
              @foreach($productosPrecioMedio as $index => $item)
              <a href="{{ añadirCam($item['url_producto']) }}" class="block">
                <div class="flex items-center space-x-3 p-2 bg-gray-50 rounded hover:bg-gray-100 transition-colors">
                  <div class="flex-shrink-0">
                    <img src="{{ asset('images/' . $item['img_producto']) }}" alt="{{ $item['producto_nombre'] }}" class="w-10 h-10 object-cover rounded">
                  </div>
                  <div class="flex-1 min-w-0">
                    <div class="text-sm font-medium text-gray-900 truncate">{{ $item['producto_nombre'] }}</div>
                    <div class="flex items-center justify-between mt-1">
                      <div class="text-sm font-bold text-pink-500">
                        <span class="text-xs text-gray-500">Desde </span>
                        {{ $item['precio_formateado'] }}
                      </div>
                      <div class="bg-red-500 text-white text-xs font-bold px-2 py-1 rounded-full">
                        -{{ round($item['porcentaje_diferencia']) }}%
                      </div>
                    </div>
                  </div>
                </div>
              </a>
              @endforeach
            </div>
          </div>
          @endif

          {{-- Productos Precios Hot --}}
          @if($productosPreciosHot->count() > 0)
          <div>
            <h3 class="text-lg font-semibold mb-3 text-gray-800 border-b border-gray-200 pb-2">Precios Hot 🔥 </h3>
            <div class="space-y-2">
              @foreach($productosPreciosHot as $index => $item)
              <a href="{{ añadirCam($item['url_producto']) }}" class="block">
                                 <div class="flex items-center space-x-3 p-2 bg-blue-50 rounded hover:bg-blue-100 transition-colors">
                  <div class="flex-shrink-0">
                    <img src="{{ asset('images/' . $item['img_producto']) }}" alt="{{ $item['producto_nombre'] }}" class="w-10 h-10 object-cover rounded">
                  </div>
                  <div class="flex-1 min-w-0">
                    <div class="text-sm font-medium text-gray-900 truncate">{{ $item['producto_nombre'] }}</div>
                    <div class="flex items-center justify-between mt-1">
                      <div class="text-sm font-bold text-pink-500">
                      <span class="text-xs text-gray-500">Desde </span>
                        {{ $item['precio_formateado'] }}
                      </div>
                      <div class="bg-red-500 text-white text-xs font-bold px-2 py-1 rounded-full">
                        -{{ round($item['porcentaje_diferencia']) }}%
                      </div>
                    </div>
                  </div>
                </div>
              </a>
              @endforeach
            </div>
          </div>
          @endif
        </div>
      </aside>

      {{-- GRÁFICO PARA MÓVIL Y TABLET --}}
      <div class="block lg:hidden bg-white rounded-lg shadow p-4 mb-4">
        <div class="flex justify-center items-center mb-4">
          <h3 class="text-lg font-semibold text-gray-800 mr-4">Evolución del precio</h3>
          <div class="flex space-x-2">
            <button id="btn-3m-movil" class="px-3 py-1 text-sm border rounded hover:bg-gray-100 transition-colors" data-periodo="3m">3M</button>
            <button id="btn-6m-movil" class="px-3 py-1 text-sm border rounded hover:bg-gray-100 transition-colors" data-periodo="6m">6M</button>
            <button id="btn-9m-movil" class="px-3 py-1 text-sm border rounded hover:bg-gray-100 transition-colors" data-periodo="9m">9M</button>
            <button id="btn-1y-movil" class="px-3 py-1 text-sm border rounded hover:bg-gray-100 transition-colors" data-periodo="1y">1A</button>
          </div>
        </div>
        <div class="relative" style="height: 250px;">
          <canvas id="graficoPreciosMovil" class="w-full h-full"></canvas>
        </div>
      </div>

      <main id="listado-precios" class="w-full lg:w-3/4">
        <h2 class="text-2xl font-semibold mb-4">{{ $producto->subtitulo}}</h2>
        
        {{-- Filtros de especificaciones internas --}}
        @if($producto->categoria_id_especificaciones_internas && $producto->categoria_especificaciones_internas_elegidas)
          @php
            // Obtener la categoría con sus especificaciones internas
            $categoriaEspecificaciones = \App\Models\Categoria::find($producto->categoria_id_especificaciones_internas);
            $especificacionesElegidas = $producto->categoria_especificaciones_internas_elegidas;
            
            if ($categoriaEspecificaciones && $categoriaEspecificaciones->especificaciones_internas && 
                isset($categoriaEspecificaciones->especificaciones_internas['filtros'])) {
              
              $filtrosImportantes = collect($categoriaEspecificaciones->especificaciones_internas['filtros'])
                ->map(function($filtro) use ($especificacionesElegidas) {
                  // Filtrar solo las sublíneas que están marcadas como "Mostrar" (m === 1)
                  $sublineasElegidas = $especificacionesElegidas[$filtro['id']] ?? [];
                  
                  $filtro['subprincipales'] = collect($filtro['subprincipales'] ?? [])
                    ->map(function($sub) use ($sublineasElegidas) {
                      // Buscar los datos de la sublínea en las elegidas (incluyendo imágenes)
                      $sublineaData = collect($sublineasElegidas)->first(function($item) use ($sub) {
                        $sublineaId = is_array($item) && isset($item['id']) ? $item['id'] : $item;
                        return (string)$sublineaId === (string)$sub['id'];
                      });
                      
                      // Verificar si está marcada como "Mostrar"
                      $estaMarcadaComoMostrar = $sublineaData && 
                        (is_array($sublineaData) && ((isset($sublineaData['m']) && $sublineaData['m'] === 1) || 
                         (isset($sublineaData['mostrar']) && $sublineaData['mostrar'] === true)));
                      
                      if (!$estaMarcadaComoMostrar) {
                        return null; // No incluir esta sublínea
                      }
                      
                      // Añadir imágenes a la sublínea si existen
                      if (is_array($sublineaData) && isset($sublineaData['img']) && is_array($sublineaData['img']) && count($sublineaData['img']) > 0) {
                        $sub['imagenes'] = $sublineaData['img'];
                      } else {
                        $sub['imagenes'] = [];
                      }
                      
                      return $sub;
                    })
                    ->filter(function($sub) {
                      return $sub !== null; // Filtrar los null
                    })
                    ->values()
                    ->toArray();
                  return $filtro;
                })
                ->filter(function($filtro) {
                  // Mostrar solo las líneas principales que tienen al menos una sublínea marcada como "Mostrar"
                  return count($filtro['subprincipales'] ?? []) > 0;
                })
                ->values();
            } else {
              $filtrosImportantes = collect([]);
            }
          @endphp
          @if(isset($filtrosImportantes) && $filtrosImportantes->count() > 0)
            <div id="filtros-especificaciones-internas" class="mb-6">
              <div class="space-y-3 sm:space-y-4">
                @foreach($filtrosImportantes as $filtro)
                  @php
                    // Detectar si hay sublíneas con imágenes
                    $tieneImagenes = false;
                    foreach ($filtro['subprincipales'] as $sub) {
                      if (isset($sub['imagenes']) && is_array($sub['imagenes']) && count($sub['imagenes']) > 0) {
                        $tieneImagenes = true;
                        break;
                      }
                    }
                  @endphp
                  <div class="filtro-linea-principal" data-linea-id="{{ $filtro['id'] }}">
                    <div class="font-bold text-sm sm:text-base text-black mb-2" style="color: #000000 !important; font-weight: bold !important;">{{ $filtro['texto'] }}</div>
                    @if($tieneImagenes)
                      {{-- Carrusel para sublíneas con imágenes --}}
                      <div class="relative sublineas-carrusel-container" data-linea-id="{{ $filtro['id'] }}">
                        <div class="overflow-hidden">
                          <div class="sublineas-carrusel flex gap-2 sm:gap-3 overflow-x-auto scrollbar-hide" style="scroll-behavior: smooth; -webkit-overflow-scrolling: touch; touch-action: pan-x; overscroll-behavior-x: contain;">
                            @foreach($filtro['subprincipales'] as $sub)
                              <button 
                                type="button"
                                class="filtro-sublinea-btn px-2 sm:px-3 py-1 sm:py-1.5 text-xs sm:text-sm border rounded transition-colors bg-white text-gray-900 border-gray-400 hover:bg-gray-100 flex items-center gap-1 flex-shrink-0"
                                style="background-color: #ffffff !important; color: #111827 !important; border-color: #9ca3af !important;"
                                data-linea-id="{{ $filtro['id'] }}"
                                data-sublinea-id="{{ $sub['id'] }}"
                                data-sublinea-texto="{{ htmlspecialchars($sub['texto'], ENT_QUOTES, 'UTF-8') }}"
                                @if(isset($sub['imagenes']) && is_array($sub['imagenes']) && count($sub['imagenes']) > 0)
                                  data-imagenes='{!! json_encode($sub['imagenes'], JSON_HEX_QUOT | JSON_HEX_APOS | JSON_UNESCAPED_UNICODE) !!}'
                                @endif>
                                @if(isset($sub['imagenes']) && is_array($sub['imagenes']) && count($sub['imagenes']) > 0)
                                  {{-- Mostrar imagen pequeña si existe --}}
                                  @php
                                    $imagenOriginal = $sub['imagenes'][0];
                                    // Intentar usar la versión thumbnail si existe
                                    $imagenPequena = preg_replace('/\.(jpg|jpeg|png|gif|webp)$/i', '-thumbnail.webp', $imagenOriginal);
                                    // Verificar si existe el thumbnail, si no, usar la original
                                    $rutaThumbnail = public_path('images/' . $imagenPequena);
                                    $imagenFinal = file_exists($rutaThumbnail) ? $imagenPequena : $imagenOriginal;
                                  @endphp
                                  <img src="{{ asset('images/' . $imagenFinal) }}" alt="{{ $producto->nombre }} {{ $sub['texto'] }}" class="object-cover rounded" style="width: 78px; height: 78px; flex-shrink: 0;">
                                @else
                                  {{-- Mostrar texto si no hay imagen --}}
                                  {{ $sub['texto'] }}
                                @endif
                                @if(!isset($sub['imagenes']) || !is_array($sub['imagenes']) || count($sub['imagenes']) == 0)
                                  {{-- Mostrar contador solo si no hay imagen --}}
                                  <span class="contador-sublinea" data-linea-id="{{ $filtro['id'] }}" data-sublinea-id="{{ $sub['id'] }}">(0)</span>
                                @endif
                              </button>
                            @endforeach
                          </div>
                        </div>
                        {{-- Botones de navegación del carrusel --}}
                        <button type="button" class="carrusel-btn-left absolute left-0 top-1/2 bg-white border border-gray-300 rounded-full p-2 shadow-md hover:bg-gray-50 z-10 opacity-0 transition-opacity pointer-events-none" data-linea-id="{{ $filtro['id'] }}" style="transform: translateY(-50%);">
                          <svg class="w-5 h-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                          </svg>
                        </button>
                        <button type="button" class="carrusel-btn-right absolute right-0 top-1/2 bg-white border border-gray-300 rounded-full p-2 shadow-md hover:bg-gray-50 z-10 opacity-0 transition-opacity pointer-events-none" data-linea-id="{{ $filtro['id'] }}" style="transform: translateY(-50%);">
                          <svg class="w-5 h-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                          </svg>
                        </button>
                      </div>
                    @else
                      {{-- Layout normal (flex-wrap) para sublíneas sin imágenes --}}
                      <div class="flex flex-wrap gap-2 sm:gap-3">
                        @foreach($filtro['subprincipales'] as $sub)
                          <button 
                            type="button"
                            class="filtro-sublinea-btn px-2 sm:px-3 py-1 sm:py-1.5 text-xs sm:text-sm border rounded transition-colors bg-white text-gray-900 border-gray-400 hover:bg-gray-100 flex items-center gap-1"
                            style="background-color: #ffffff !important; color: #111827 !important; border-color: #9ca3af !important;"
                            data-linea-id="{{ $filtro['id'] }}"
                            data-sublinea-id="{{ $sub['id'] }}"
                            data-sublinea-texto="{{ htmlspecialchars($sub['texto'], ENT_QUOTES, 'UTF-8') }}">
                            {{ $sub['texto'] }}
                            <span class="contador-sublinea" data-linea-id="{{ $filtro['id'] }}" data-sublinea-id="{{ $sub['id'] }}">(0)</span>
                          </button>
                        @endforeach
                      </div>
                    @endif
                  </div>
                @endforeach
              </div>
            </div>
          @endif
        @endif
        
        {{-- Filtros arriba del listado --}}
        @php
        $tiendasUnicas = collect($ofertas)->map(fn($o) => $o->tienda->nombre)->unique()->sort()->values();
        // Formatear cantidades para mostrar números enteros cuando sea posible
        $cantidadesUnicas = collect($ofertas)->map(function($o) {
          return ($o->unidades == intval($o->unidades)) ? intval($o->unidades) : floatval($o->unidades);
        })->unique()->sort()->values();
        @endphp
        @php
        // Detectar si viene el parámetro ?v=xxx y validarlo
        $unidadSeleccionada = null;
        if (request()->has('v')) {
            $v = request('v');
            // Validar que sea numérico (puede ser entero o decimal)
            if (is_numeric($v) && $v > 0) {
                $unidadSeleccionada = floatval($v);
            }
        }
        $ofertasFiltradas = $unidadSeleccionada
            ? $ofertas->where('unidades', $unidadSeleccionada)
            : $ofertas;
        @endphp
        <div class="flex flex-wrap gap-4 mb-6 items-center bg-gray-200 border rounded">
          <div>
            {{-- x1: Filtro de tienda --}}
            <label for="x1" class="font-semibold mr-2 text-sm">Tienda:</label>
            <select id="x1" class="border rounded px-2 py-1 min-w-[180px] w-[180px] text-sm">
              <option value="">Todas</option>
              @foreach($tiendasUnicas as $tienda)
                <option value="{{ $tienda }}">{{ $tienda }}</option>
              @endforeach
            </select>
          </div>
          <div>
            {{-- x2: Filtro de envío gratis --}}
            <label class="font-semibold mr-2 text-sm"><input type="checkbox" id="x2" class="mr-1"> Envío gratis</label>
          </div>
          {{-- x3c: Contenedor del filtro de cantidad --}}
          <div id="x3c">
            {{-- x3: Filtro de cantidad --}}
            <label for="x3" class="font-semibold mr-2 text-sm">Cantidad:</label>
            <select id="x3" class="border rounded px-2 py-1 min-w-[140px] w-[140px] text-sm">
              <option value="">Todas</option>
              @foreach($cantidadesUnicas as $cantidad)
                @php
                  // Formatear la cantidad según la unidad de medida
                  if ($producto->unidadDeMedida === 'kilos') {
                    if ($cantidad >= 1) {
                      // Mostrar como número entero si no tiene decimales
                      $cantidadFormateada = ($cantidad == intval($cantidad)) ? number_format($cantidad, 0, ',', '.') : number_format($cantidad, 2, ',', '.');
                      $textoCantidad = $cantidadFormateada . ' Kilos';
                    } else {
                      $gramos = round($cantidad * 1000);
                      $textoCantidad = number_format($gramos, 0, ',', '.') . ' gramos';
                    }
                  } elseif ($producto->unidadDeMedida === 'litros') {
                    if ($cantidad >= 1) {
                      // Mostrar como número entero si no tiene decimales
                      $cantidadFormateada = ($cantidad == intval($cantidad)) ? number_format($cantidad, 0, ',', '.') : number_format($cantidad, 2, ',', '.');
                      $textoCantidad = $cantidadFormateada . ' Litros';
                    } else {
                      $mililitros = round($cantidad * 1000);
                      $textoCantidad = number_format($mililitros, 0, ',', '.') . ' ml';
                    }
                  } elseif ($producto->unidadDeMedida === 'unidadMilesima') {
                    // Para UnidadMilésima, mostrar como número entero si no tiene decimales
                    $cantidadFormateada = ($cantidad == intval($cantidad)) ? number_format($cantidad, 0, ',', '.') : number_format($cantidad, 2, ',', '.');
                    $textoCantidad = $cantidadFormateada . ' Unidades';
                  } elseif ($producto->unidadDeMedida === 'unidadUnica') {
                    // Para UnidadUnica, solo mostrar la cantidad sin "Unidades"
                    $cantidadFormateada = ($cantidad == intval($cantidad)) ? number_format($cantidad, 0, ',', '.') : number_format($cantidad, 2, ',', '.');
                    $textoCantidad = $cantidadFormateada;
                  } else {
                    // Por defecto: unidades (incluye 800gramos y 100ml) - mostrar como número entero si no tiene decimales
                    $cantidadFormateada = ($cantidad == intval($cantidad)) ? number_format($cantidad, 0, ',', '.') : number_format($cantidad, 2, ',', '.');
                    $textoCantidad = $cantidadFormateada . ' Unidades';
                  }
                @endphp
                @php
                  // Contar ofertas para esta cantidad
                  $ofertasParaEstaCantidad = $ofertas->where('unidades', $cantidad)->count();
                @endphp
                <option value="{{ $cantidad }}" {{ $unidadSeleccionada == $cantidad ? 'selected' : '' }}>{{ $textoCantidad }} ({{ $ofertasParaEstaCantidad }})</option>
              @endforeach
            </select>
          </div>
          @if($producto->unidadDeMedida !== 'unidadUnica')
          <div class="flex items-center gap-1">
            <span class="font-semibold mr-2 text-sm">Ordenar:</span>
            {{-- x4: Botón ordenar por unidades --}}
            <button id="x4" type="button" class="border rounded px-3 py-1 bg-blue-500 text-white font-semibold text-sm focus:outline-none focus:ring-2 focus:ring-blue-300 transition">
              @if($producto->unidadDeMedida === 'kilos')
                Kilos
              @elseif($producto->unidadDeMedida === 'litros')
                Litros
              @elseif($producto->unidadDeMedida === 'unidadMilesima')
                Unidades
              @elseif($producto->unidadDeMedida === '800gramos')
                800 gramos
              @elseif($producto->unidadDeMedida === '100ml')
                100 ml
              @else
                Unidades
              @endif
            </button>
            {{-- x5: Botón ordenar por precio total --}}
            <button id="x5" type="button" class="border rounded px-3 py-1 bg-white text-blue-500 font-semibold text-sm focus:outline-none focus:ring-2 focus:ring-blue-300 transition">Precio total</button>
          </div>
          @endif
        </div>
        {{-- x6: Contenedor del listado de ofertas --}}
        <div id="x6" class="space-y-1"></div>
        {{-- x7: Contenedor del botón mostrar más --}}
        <div id="x7" class="text-center mt-6 hidden">
          {{-- x8: Botón mostrar más ofertas --}}
          <button id="x8" class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-3 px-6 rounded-lg transition-colors duration-200">
            {{-- x9: Contador de ofertas restantes --}}
            Mostrar más ofertas (<span id="x9">0</span>)
          </button>
        </div>
        @php
        // Preparar datos de columnas para unidadUnica
        $columnasData = null;
        $esUnidadUnica = ($producto->unidadDeMedida === 'unidadUnica');
        if ($esUnidadUnica && $producto->categoria_id_especificaciones_internas && $producto->categoria_especificaciones_internas_elegidas) {
          $categoriaEspecificaciones = \App\Models\Categoria::find($producto->categoria_id_especificaciones_internas);
          $especificacionesElegidas = $producto->categoria_especificaciones_internas_elegidas;
          
          if ($categoriaEspecificaciones && $categoriaEspecificaciones->especificaciones_internas && 
              isset($categoriaEspecificaciones->especificaciones_internas['filtros']) &&
              isset($especificacionesElegidas['_columnas'])) {
            
            $columnasIds = $especificacionesElegidas['_columnas'] ?? [];
            $filtros = $categoriaEspecificaciones->especificaciones_internas['filtros'];
            
            // Crear mapa de líneas principales con sus datos
            $columnasData = [];
            foreach ($filtros as $filtro) {
              if (in_array($filtro['id'], $columnasIds)) {
                $columnasData[] = [
                  'id' => $filtro['id'],
                  'texto' => $filtro['texto'],
                  'subprincipales' => $filtro['subprincipales'] ?? []
                ];
              }
            }
            
            // Ordenar según _orden si existe
            if (isset($especificacionesElegidas['_orden']) && is_array($especificacionesElegidas['_orden'])) {
              $orden = $especificacionesElegidas['_orden'];
              usort($columnasData, function($a, $b) use ($orden) {
                $posA = array_search($a['id'], $orden);
                $posB = array_search($b['id'], $orden);
                if ($posA === false) $posA = 999;
                if ($posB === false) $posB = 999;
                return $posA - $posB;
              });
            }
          }
        }
        
        // Las ofertas ya vienen con descuentos aplicados desde la ruta
        $ofertasArray = $ofertas->map(function($item) use ($producto) {
          try {
            // Formatear unidades para mostrar números enteros cuando sea posible
            $unidadesFormateadas = ($item->unidades == intval($item->unidades)) ? 
              intval($item->unidades) : 
              floatval($item->unidades);
            
            // Formatear precio_unidad según la unidad de medida
            $decimalesPrecioUnidad = ($producto->unidadDeMedida === 'unidadMilesima') ? 3 : 2;
            
            // Verificar que la relación tienda esté disponible
            if (!isset($item->tienda) || !$item->tienda) {
              \Log::error('Oferta sin tienda:', ['oferta_id' => $item->id ?? 'N/A']);
              return null;
            }
            
            // Verificar que precio_unidad sea válido
            if (!isset($item->precio_unidad) || $item->precio_unidad === null || $item->precio_unidad < 0) {
              \Log::error('Oferta con precio_unidad inválido:', [
                'oferta_id' => $item->id ?? 'N/A',
                'precio_unidad' => $item->precio_unidad ?? 'null'
              ]);
              return null;
            }
            
            return [
              "id" => $item->id,
              "nombre" => $item->tienda->nombre ?? 'N/A',
              "tienda" => $item->tienda->nombre ?? 'N/A',
              "logo" => asset('images/' . ($item->tienda->url_imagen ?? '')),
              "envio_gratis" => $item->tienda->envio_gratis ?? '',
              "envio_normal" => $item->tienda->envio_normal ?? '',
              "unidades" => $unidadesFormateadas,
              "unidades_originales" => $unidadesFormateadas,
              "precio_total" => number_format($item->precio_total ?? 0, 2, ',', ''),
              "precio_unidad" => number_format($item->precio_unidad ?? 0, $decimalesPrecioUnidad, ',', ''),
              "descuentos" => $item->descuentos ?? '',
              "url" => añadirCam(route('click.redirigir', ['ofertaId' => $item->id])),
              "especificaciones_internas" => $item->especificaciones_internas ?? null,
            ];
          } catch (\Exception $e) {
            \Log::error('Error al procesar oferta:', [
              'oferta_id' => $item->id ?? 'N/A',
              'error' => $e->getMessage(),
              'trace' => $e->getTraceAsString()
            ]);
            return null;
          }
        })->filter()->values(); // Filtrar nulls
        @endphp
        <script>
          {{-- Datos de ofertas para JS --}}
          {{-- x3: Filtro de cantidad --}}
          document.getElementById('x3').addEventListener('change', _mi1);
          
          {{-- Solo añadir event listeners de ordenar si los botones existen (no es unidadUnica) --}}
          {{-- x4: Botón ordenar por unidades --}}
          const btnOrdenUnidades = document.getElementById('x4');
          {{-- x5: Botón ordenar por precio total --}}
          const btnOrdenPrecio = document.getElementById('x5');
          
          if (btnOrdenUnidades) {
            btnOrdenUnidades.addEventListener('click', function() {
              ordenActual = 'unidades';
              _sob1();
              filtroInteractuado = true;
              _ro1();
            });
          }
          
          if (btnOrdenPrecio) {
            btnOrdenPrecio.addEventListener('click', function() {
              ordenActual = 'precio_total';
              _sob1();
              filtroInteractuado = true;
              _ro1();
            });
          }
          
          document.addEventListener('DOMContentLoaded', function() {
            if (btnOrdenUnidades && btnOrdenPrecio) {
              _sob1();
            }
            _cbe1();
            _aeb1();
            {{-- Actualizar contadores de sublíneas al cargar --}}
            _acs1();
            {{-- Actualizar filtros dinámicamente al cargar (basándose en todas las ofertas) --}}
            _afd1(ofertas);
            _ro1();
          });
          {{-- Cargar ofertas con manejo de errores --}}
          let ofertas = [];
          try {
            const ofertasJson = @json($ofertasArray);
            ofertas = Array.isArray(ofertasJson) ? ofertasJson : [];
            
          } catch (e) {
            ofertas = [];
          }
          const unidadMedida = '{{ $producto->unidadDeMedida }}';
          const esUnidadUnica = unidadMedida === 'unidadUnica';
          const columnasData = @json($columnasData ?? null);
          const gruposDeOfertas = @json($producto->grupos_de_ofertas ?? null);

          let ordenActual = null; {{-- En la primera carga, respeta el orden del backend --}}
          let filtroInteractuado = false;
          let mostrarTodasLasOfertas = false;
          const OFERTAS_INICIALES = 15;
          
          {{-- Filtros de especificaciones internas --}}
          {{-- Inicializar con filtros recibidos desde la URL (si existen) --}}
          {{-- v13: especificacionesSeleccionadas - Objeto con las especificaciones seleccionadas por el usuario --}}
          window.v13 = @json($filtrosAplicadosDesdeUrl ?? []);
          {{-- Eliminar precio_min y precio_max de especificacionesSeleccionadas si existen --}}
          if (v13.precio_min !== undefined) {
            delete v13.precio_min;
          }
          if (v13.precio_max !== undefined) {
            delete v13.precio_max;
          }
          {{-- Convertir arrays de IDs a strings para consistencia --}}
          Object.keys(v13).forEach(lineaId => {
            if (Array.isArray(v13[lineaId])) {
              v13[lineaId] = v13[lineaId].map(id => String(id));
            }
          });
          let primeraLineaSeleccionada = null; {{-- ID de la primera línea principal que tiene una sublínea seleccionada --}}
          {{-- Determinar primera línea seleccionada si hay filtros iniciales --}}
          if (Object.keys(v13).length > 0) {
            primeraLineaSeleccionada = Object.keys(v13)[0];
          }
          
          {{-- Función para parsear cupón y extraer código y cantidad (global) --}}
          {{-- _pc1: parsearCupon - Parsea un string de descuentos para extraer código y valor del cupón --}}
          window._pc1 = function _pc1(descuentos) {
            try {
              if (!descuentos || typeof descuentos !== 'string' || !descuentos.startsWith('cupon;')) {
                return null;
              }
              
              const partes = descuentos.split(';');
              if (partes.length >= 3) {
                {{-- Formato nuevo: cupon;codigo;cantidad (puede tener más partes si el código contiene ;) --}}
                {{-- Tomar todo entre el primer y último ; como código, y el último como valor --}}
                const codigo = partes.slice(1, -1).join(';'); {{-- Unir todas las partes del medio por si el código contiene ; --}}
                const valor = partes[partes.length - 1];
                return {
                  codigo: codigo || null,
                  valor: valor || ''
                };
              } else if (partes.length === 2) {
                {{-- Formato antiguo: cupon;cantidad (solo cantidad) --}}
                return {
                  codigo: null,
                  valor: partes[1] || ''
                };
              }
              return null;
            } catch (e) {
              return null;
            }
          }
          
          {{-- Función para formatear la cantidad según la unidad de medida --}}
          {{-- _fc1: formatearCantidad - Formatea una cantidad según la unidad de medida (kilos, litros, etc.) --}}
          function _fc1(cantidad, unidad) {
            if (unidad === 'kilos') {
              if (cantidad >= 1) {
                {{-- Mostrar como número entero si no tiene decimales, sino con decimales --}}
                const cantidadFormateada = cantidad % 1 === 0 ? cantidad.toString() : cantidad.toFixed(2);
                return cantidadFormateada + ' Kilos';
              } else {
                {{-- Convertir a gramos --}}
                const gramos = Math.round(cantidad * 1000);
                return gramos + ' gramos';
              }
            } else if (unidad === 'litros') {
              if (cantidad >= 1) {
                {{-- Mostrar como número entero si no tiene decimales, sino con decimales --}}
                const cantidadFormateada = cantidad % 1 === 0 ? cantidad.toString() : cantidad.toFixed(2);
                return cantidadFormateada + ' Litros';
              } else {
                {{-- Convertir a mililitros --}}
                const mililitros = Math.round(cantidad * 1000);
                return mililitros + ' ml';
              }
            } else if (unidad === 'unidadMilesima') {
              {{-- Para UnidadMilésima, mostrar como número entero si no tiene decimales --}}
              const cantidadFormateada = cantidad % 1 === 0 ? cantidad.toString() : cantidad.toFixed(2);
              return cantidadFormateada + ' Unidades';
            } else if (unidad === 'unidadUnica') {
              {{-- Para UnidadUnica, solo mostrar la cantidad sin "Unidades" --}}
              const cantidadFormateada = cantidad % 1 === 0 ? cantidad.toString() : cantidad.toFixed(2);
              return cantidadFormateada;
            } else {
              {{-- Por defecto: unidades (incluye 800gramos y 100ml) - mostrar como número entero si no tiene decimales --}}
              const cantidadFormateada = cantidad % 1 === 0 ? cantidad.toString() : cantidad.toFixed(2);
              return cantidadFormateada + ' Unidades';
            }
          }
          
          {{-- Función para obtener el label de precio según unidad --}}
          {{-- _glp1: getLabelPrecio - Obtiene el texto del label de precio según la unidad de medida --}}
          function _glp1(unidad) {
            if (unidad === 'kilos') {
              return 'Precio por kilo';
            } else if (unidad === 'litros') {
              return 'Precio por litro';
            } else if (unidad === 'unidadMilesima') {
              return 'Precio por unidad';
            } else if (unidad === 'unidadUnica') {
              return 'Precio'; {{-- Sin "por unidad" para UnidadUnica --}}
            } else if (unidad === '800gramos') {
              return 'Precio por 800 gramos';
            } else if (unidad === '100ml') {
              return 'Precio por 100 ml';
            } else {
              return 'Precio por unidad';
            }
          }
          
          {{-- Función para obtener el sufijo de precio según unidad --}}
          {{-- _gsp1: getSufijoPrecio - Obtiene el sufijo de precio según la unidad de medida (€/Kg, €/L, etc.) --}}
          function _gsp1(unidad) {
            if (unidad === 'kilos') {
              return '€/Kg.';
            } else if (unidad === 'litros') {
              return '€/L.';
            } else if (unidad === 'unidadMilesima') {
              return '€/Und.';
            } else if (unidad === 'unidadUnica') {
              return '€'; {{-- Sin sufijo para UnidadUnica --}}
            } else if (unidad === '800gramos') {
              return '€/800gr.';
            } else if (unidad === '100ml') {
              return '€/100ml.';
            } else {
              return '€/Und.';
            }
          }

          {{-- Función para actualizar las mejores ofertas en móvil --}}
          {{-- _amom1: actualizarMejorOfertaMovil - Actualiza las mejores ofertas mostradas en la vista móvil --}}
          function _amom1(filtradas) {
            if (filtradas.length === 0) return;
            
            {{-- Calcular el precio mínimo entre todas las ofertas --}}
            const preciosUnidad = filtradas.map(o => parseFloat(o.precio_unidad.replace(',', '.')));
            const precioMinimo = Math.min(...preciosUnidad);
            
            {{-- Filtrar todas las ofertas que tienen el mejor precio y tomar máximo 4 --}}
            const ofertasMejorPrecio = filtradas
              .map(o => ({...o, precioUnidadNum: parseFloat(o.precio_unidad.replace(',', '.'))}))
              .filter(o => o.precioUnidadNum === precioMinimo)
              .slice(0, 4);
            
            {{-- Función para actualizar una oferta específica --}}
            function actualizarOferta(oferta, index) {
              {{-- Solo la primera oferta (index 1) muestra el badge si tiene el mejor precio --}}
              const esMejorPrecio = oferta.precioUnidadNum === precioMinimo && index === 1;
              {{-- Todas las ofertas con mejor precio tienen el borde animado --}}
              const tieneMejorPrecio = oferta.precioUnidadNum === precioMinimo;
              
              {{-- Determinar si es una sola oferta con mejor precio y no es unidadUnica --}}
              const esSolaOfertaMejorPrecio = ofertasMejorPrecio.length === 1 && unidadMedida !== 'unidadUnica';
              
              {{-- Aplicar estilo de mejor oferta si es el mejor precio --}}
              const contenedorElement = document.getElementById(`mejor-oferta-contenedor-movil-${index}`);
              if (contenedorElement) {
                if (esMejorPrecio) {
                  contenedorElement.className = 'flex-1 mejor-oferta-wrapper';
                  
                  {{-- Si es una sola oferta con mejor precio y no es unidadUnica, mostrar información completa --}}
                  let precioHtml = '';
                  if (esSolaOfertaMejorPrecio) {
                    precioHtml = `
                      <div class="text-center mb-2">
                        <div class="text-base text-gray-600 mb-1">${_fc1(oferta.unidades, unidadMedida)}</div>
                        <div class="flex items-center justify-center gap-2">
                          <div class="text-base text-gray-600"><b>Total:</b> ${oferta.precio_total} €</div>
                          <div class="text-2xl font-extrabold text-pink-400">
                            ${oferta.precio_unidad} <span class="text-sm text-gray-500 font-normal">${_gsp1(unidadMedida)}</span>
                          </div>
                        </div>
                      </div>
                    `;
                  } else {
                    precioHtml = `
                      <div class="text-center">
                        <div id="mejor-oferta-precio-movil-${index}" class="text-lg font-extrabold text-pink-400 mb-1">
                          <span class="text-xl font-extrabold text-pink-400">${oferta.precio_unidad} <span class="text-xs text-gray-500 font-normal">${_gsp1(unidadMedida)}</span></span>
                        </div>
                      </div>
                    `;
                  }
                  
                  contenedorElement.innerHTML = `
                    <div class="mejor-oferta-badge-grupo" style="right: -30px; top: -20px;">🏆 Mejor precio</div>
                    <div class="mejor-oferta-gaming-container">
                      <div class="ofertas-grupo">
                        <a href="${oferta.url}" target="_blank" rel="sponsored noopener noreferrer" class="block cursor-pointer">
                          <div class="flex items-center justify-center mb-1">
                            <div id="mejor-oferta-logo-movil-${index}" class="flex items-center justify-center">
                              <img src="${oferta.logo}" loading="lazy" alt="${oferta.nombre}" class="h-8 object-contain">
                            </div>
                          </div>
                          ${precioHtml}
                          <div id="mejor-oferta-boton-movil-${index}" class="w-full">
                            ${_gbo1(oferta)}
                          </div>
                        </a>
                      </div>
                    </div>
                  `;
                } else if (tieneMejorPrecio) {
                  {{-- Segunda oferta con mejor precio: borde animado pero sin badge --}}
                  contenedorElement.className = 'flex-1 mejor-oferta-wrapper';
                  contenedorElement.style.paddingTop = '0px'; {{-- Sin padding cuando ambas tienen mejor precio --}}
                  contenedorElement.innerHTML = `
                    <div class="mejor-oferta-gaming-container">
                      <div class="ofertas-grupo">
                        <a href="${oferta.url}" target="_blank" rel="sponsored noopener noreferrer" class="block cursor-pointer">
                          <div class="flex items-center justify-center mb-1">
                            <div id="mejor-oferta-logo-movil-${index}" class="flex items-center justify-center">
                              <img src="${oferta.logo}" loading="lazy" alt="${oferta.nombre}" class="h-8 object-contain">
                            </div>
                          </div>
                          <div class="text-center">
                            <div id="mejor-oferta-precio-movil-${index}" class="text-lg font-extrabold text-pink-400 mb-1">
                              <span class="text-xl font-extrabold text-pink-400">${oferta.precio_unidad} <span class="text-xs text-gray-500 font-normal">${_gsp1(unidadMedida)}</span></span>
                            </div>
                            <div id="mejor-oferta-boton-movil-${index}" class="w-full">
                              ${_gbo1(oferta)}
                            </div>
                          </div>
                        </a>
                      </div>
                    </div>
                  `;
                } else {
                  {{-- Oferta normal sin mejor precio --}}
                  contenedorElement.className = 'flex-1';
                  contenedorElement.style.display = 'flex';
                  contenedorElement.style.flexDirection = 'column';
                  contenedorElement.style.height = '100%';
                  {{-- Solo aplicar padding si la primera oferta tiene el badge (mejor precio) --}}
                  const primeraOfertaTieneMejorPrecio = ofertasOrdenadas[0].precioUnidadNum === precioMinimo;
                  contenedorElement.style.paddingTop = primeraOfertaTieneMejorPrecio ? '8px' : '0px';
                  contenedorElement.innerHTML = `
                    <a href="${oferta.url}" target="_blank" rel="sponsored noopener noreferrer" class="block cursor-pointer">
                      <div class="flex items-center justify-center mb-1">
                        <div id="mejor-oferta-logo-movil-${index}" class="flex items-center justify-center">
                          <img src="${oferta.logo}" loading="lazy" alt="${oferta.nombre}" class="h-5 object-contain">
                        </div>
                      </div>
                      <div class="text-center">
                        <div id="mejor-oferta-precio-movil-${index}" class="text-lg font-extrabold text-pink-400 mb-1">
                          <span class="text-xl font-extrabold text-pink-400">${oferta.precio_unidad} <span class="text-xs text-gray-500 font-normal">${_gsp1(unidadMedida)}</span></span>
                        </div>
                        <div id="mejor-oferta-boton-movil-${index}" class="w-full">
                          ${_gbo1(oferta)}
                        </div>
                      </div>
                    </a>
                  `;
                }
              }
            }

            {{-- Función para generar el botón de oferta --}}
            {{-- _gbo1: generarBotonOferta - Genera el HTML del botón de oferta con badges de cupones --}}
            function _gbo1(oferta) {
              {{-- Siempre usar el mismo botón azul con "Ir a la tienda" (igual que sin descuento) --}}
              const botonBase = 'inline-block w-full py-1 px-1 bg-blue-500 text-white text-base font-semibold rounded hover:bg-blue-600 transition-colors text-center relative cursor-pointer';
              let badgeHtml = '';
              let dataAttrs = '';
              let tieneDescuento = false;
              
              {{-- Asegurar que descuentos sea un string --}}
              const descuentosStr = oferta.descuentos ? String(oferta.descuentos) : '';
              
              if (descuentosStr && typeof descuentosStr === 'string' && descuentosStr.startsWith('CholloTienda1SoloCuponQueAplicaDescuento;')) {
                tieneDescuento = true;
                badgeHtml = '<span class="cupon-badge" style="background: linear-gradient(135deg, #ff6900, #ff8c00); top: 0px; right: 0px; bottom: auto; font-size: 0.7rem; padding: 2px 6px; font-weight: bold; white-space: nowrap; pointer-events: none; z-index: 10;">CUPÓN</span>';
                dataAttrs = `data-cupon-chollo-tienda-solo="true" data-descuentos="${oferta.descuentos}" data-oferta-id="${oferta.id}" data-url="${oferta.url}"`;
              } else if (descuentosStr && typeof descuentosStr === 'string' && descuentosStr.startsWith('CholloTienda;')) {
                tieneDescuento = true;
                badgeHtml = '<span class="cupon-badge" style="background: linear-gradient(135deg, #ff6900, #ff8c00); top: 0px; right: 0px; bottom: auto; font-size: 0.7rem; padding: 2px 6px; font-weight: bold; white-space: nowrap; pointer-events: none; z-index: 10;">CUPÓN</span>';
                dataAttrs = `data-cupon-chollo-tienda="true" data-descuentos="${oferta.descuentos}" data-oferta-id="${oferta.id}" data-url="${oferta.url}"`;
              } else if (descuentosStr && typeof descuentosStr === 'string' && descuentosStr.startsWith('cupon;')) {
                try {
                  tieneDescuento = true;
                  const cuponInfo = _pc1(oferta.descuentos);
                  const valorCupon = cuponInfo ? cuponInfo.valor : (oferta.descuentos.split(';')[1] || '');
                  const codigoCupon = cuponInfo ? cuponInfo.codigo : null;
                  {{-- Escapar valores para evitar problemas con comillas y caracteres especiales --}}
                  const codigoCuponEscapado = codigoCupon ? codigoCupon.replace(/"/g, '&quot;').replace(/'/g, '&#39;') : '';
                  const valorCuponEscapado = String(valorCupon).replace(/"/g, '&quot;').replace(/'/g, '&#39;');
                  const urlEscapada = oferta.url.replace(/"/g, '&quot;').replace(/'/g, '&#39;');
                  badgeHtml = '<span class="cupon-badge" style="background: linear-gradient(135deg, #ff6900, #ff8c00); top: 0px; right: 0px; bottom: auto; font-size: 0.7rem; padding: 2px 6px; font-weight: bold; white-space: nowrap; pointer-events: none; z-index: 10;">CUPÓN</span>';
                  dataAttrs = codigoCupon 
                    ? `data-cupon="true" data-codigo-cupon="${codigoCuponEscapado}" data-valor-cupon="${valorCuponEscapado}" data-url="${urlEscapada}"` 
                    : `data-cupon="true" data-valor-cupon="${valorCuponEscapado}" data-url="${urlEscapada}"`;
                } catch (e) {
                  tieneDescuento = true;
                  badgeHtml = '<span class="cupon-badge" style="background: linear-gradient(135deg, #ff6900, #ff8c00); top: 0px; right: 0px; bottom: auto; font-size: 0.7rem; padding: 2px 6px; font-weight: bold; white-space: nowrap; pointer-events: none; z-index: 10;">CUPÓN</span>';
                  dataAttrs = `data-cupon="true" data-url="${oferta.url.replace(/"/g, '&quot;').replace(/'/g, '&#39;')}"`;
                }
              } else if (oferta.descuentos === 'cupon') {
                tieneDescuento = true;
                badgeHtml = '<span class="cupon-badge" style="background: linear-gradient(135deg, #ff6900, #ff8c00); top: 0px; right: 0px; bottom: auto; font-size: 0.7rem; padding: 2px 6px; font-weight: bold; white-space: nowrap; pointer-events: none; z-index: 10;">CUPÓN</span>';
                dataAttrs = `data-cupon="true" data-url="${oferta.url.replace(/"/g, '&quot;').replace(/'/g, '&#39;')}"`;
              } else if (oferta.descuentos === '3x2') {
                tieneDescuento = true;
                badgeHtml = '<span class="cupon-badge" style="background: linear-gradient(135deg, #8b5cf6, #a855f7); top: 0px; right: 0px; bottom: auto; font-size: 0.7rem; padding: 2px 6px; font-weight: normal; white-space: nowrap; pointer-events: none; z-index: 10;">3x2</span>';
                dataAttrs = `data-3x2="true" data-url="${oferta.url.replace(/"/g, '&quot;').replace(/'/g, '&#39;')}"`;
              } else if (oferta.descuentos === '2x1 - SoloCarrefour') {
                tieneDescuento = true;
                badgeHtml = '<span class="cupon-badge" style="background: linear-gradient(135deg, #10b981, #059669); top: 0px; right: 0px; bottom: auto; font-size: 0.7rem; padding: 2px 6px; font-weight: normal; white-space: nowrap; pointer-events: none; z-index: 10;">2x1</span>';
                dataAttrs = `data-2x1="true" data-url="${oferta.url.replace(/"/g, '&quot;').replace(/'/g, '&#39;')}"`;
              } else if (oferta.descuentos === '2a al 50 - cheque - SoloCarrefour') {
                tieneDescuento = true;
                badgeHtml = '<span class="cupon-badge" style="background: linear-gradient(135deg, #f59e0b, #d97706); top: 0px; right: 0px; bottom: auto; font-size: 0.7rem; padding: 2px 6px; font-weight: bold; white-space: nowrap; pointer-events: none; z-index: 10;">2a al 50%</span>';
                dataAttrs = `data-2a-al-50-cheque="true" data-url="${oferta.url.replace(/"/g, '&quot;').replace(/'/g, '&#39;')}"`;
              } else if (oferta.descuentos === '2a al 70') {
                tieneDescuento = true;
                badgeHtml = '<span class="cupon-badge" style="background: linear-gradient(135deg, #f59e0b, #d97706); top: 0px; right: 0px; bottom: auto; font-size: 0.7rem; padding: 2px 6px; font-weight: normal; white-space: nowrap; pointer-events: none; z-index: 10;">2a AL 70%</span>';
                dataAttrs = `data-2a-al-70="true" data-url="${oferta.url.replace(/"/g, '&quot;').replace(/'/g, '&#39;')}"`;
              } else if (oferta.descuentos === '2a al 50') {
                tieneDescuento = true;
                badgeHtml = '<span class="cupon-badge" style="background: linear-gradient(135deg, #f59e0b, #d97706); top: 0px; right: 0px; bottom: auto; font-size: 0.7rem; padding: 2px 6px; font-weight: normal; white-space: nowrap; pointer-events: none; z-index: 10;">2a al 50%</span>';
                dataAttrs = `data-2a-al-50="true" data-url="${oferta.url.replace(/"/g, '&quot;').replace(/'/g, '&#39;')}"`;
              }
              
              {{-- Si tiene descuento, usar <span> en lugar de <a> para evitar enlaces anidados --}}
              if (tieneDescuento) {
                {{-- Siempre mostrar "Ir a la tienda" en el botón, el badge ya indica que hay cupón --}}
                return `<div class="relative w-full">${badgeHtml}<span class="${botonBase}" ${dataAttrs}>Ir a la tienda</span></div>`;
              } else {
                return `<div class="relative w-full">${badgeHtml}<a href="${oferta.url}" target="_blank" rel="sponsored noopener noreferrer" class="${botonBase}" ${dataAttrs}>Ir a la tienda</a></div>`;
              }
            }
            
            {{-- Actualizar todas las ofertas con mejor precio --}}
            ofertasMejorPrecio.forEach((oferta, index) => {
              actualizarOferta(oferta, index + 1);
            });
            
            {{-- Configurar event listeners después de actualizar las ofertas (con un pequeño delay para asegurar que el DOM esté listo) --}}
            setTimeout(() => {
              _scel1();
            }, 100);
            
            {{-- Mostrar/ocultar contenedores según el número de ofertas y layout --}}
            for (let i = 1; i <= 4; i++) {
              const contenedor = document.getElementById(`mejor-oferta-contenedor-movil-${i}`);
              if (contenedor) {
                if (i <= ofertasMejorPrecio.length) {
                  contenedor.style.display = 'flex';
                  
                  {{-- Añadir indicador de posición mejorado --}}
                  const indicadorPosicion = contenedor.querySelector('.indicador-posicion');
                  if (!indicadorPosicion) {
                    const indicador = document.createElement('div');
                    indicador.className = 'indicador-posicion absolute top-1 left-1 z-10';
                    
                    {{-- Crear el contenido del indicador con ranking visual --}}
                    let rankingText = '';
                    let rankingClass = '';
                    
                    if (i === 1) {
                      rankingText = '🥇 1º';
                      rankingClass = 'bg-yellow-500 text-yellow-900 text-xs font-bold rounded-full px-1 py-1 flex items-center gap-1 shadow-md';
                    } else if (i === 2) {
                      rankingText = '🥈 2º';
                      rankingClass = 'bg-gray-400 text-gray-900 text-xs font-bold rounded-full px-1 py-1 flex items-center gap-1 shadow-md';
                    } else if (i === 3) {
                      rankingText = '🥉 3º';
                      rankingClass = 'bg-orange-600 text-white text-xs font-bold rounded-full px-1 py-1 flex items-center gap-1 shadow-md';
                    } else if (i === 4) {
                      rankingText = '4º';
                      rankingClass = 'bg-blue-500 text-white text-xs font-bold rounded-full px-1 py-1 flex items-center gap-1 shadow-md';
                    }
                    
                    indicador.className += ' ' + rankingClass;
                    indicador.innerHTML = rankingText;
                    contenedor.style.position = 'relative';
                    contenedor.appendChild(indicador);
                  }
                  
                  {{-- Ajustar el ancho según el número de ofertas --}}
                  if (ofertasMejorPrecio.length === 1) {
                    {{-- Si solo hay 1 oferta, que ocupe todo el ancho --}}
                    contenedor.style.flex = '1 1 100%';
                    contenedor.style.maxWidth = '100%';
                  } else if (ofertasMejorPrecio.length === 3 && i === 3) {
                    {{-- Si hay 3 ofertas, la tercera ocupa todo el ancho de su fila --}}
                    contenedor.style.flex = '1 1 100%';
                    contenedor.style.maxWidth = '100%';
                  } else {
                    {{-- Ofertas normales (2 por fila) --}}
                    contenedor.style.flex = '1 1 50%';
                    contenedor.style.maxWidth = '50%';
                  }
                } else {
                  contenedor.style.display = 'none';
                }
              }
            }
          }

          {{-- Obtener el parámetro v de la URL para filtrado inicial --}}
          {{-- _gpv1: getParamV - Obtiene el parámetro 'v' de la URL para filtrado inicial --}}
          function _gpv1() {
            const urlParams = new URLSearchParams(window.location.search);
            return urlParams.get('v');
          }

          {{-- Función para filtrar ofertas por especificaciones internas --}}
          {{-- _fpe1: filtrarPorEspecificaciones - Filtra un array de ofertas según las especificaciones seleccionadas --}}
          function _fpe1(ofertasArray) {
            if (!v13 || Object.keys(v13).length === 0) {
              return ofertasArray;
            }
            
            return ofertasArray.filter(oferta => {
              if (!oferta.especificaciones_internas || typeof oferta.especificaciones_internas !== 'object') {
                return false;
              }
              
              {{-- Verificar que la oferta tenga todas las especificaciones seleccionadas --}}
              for (const [lineaId, sublineasIds] of Object.entries(v13)) {
                if (sublineasIds.length === 0) continue;
                
                const ofertaLinea = oferta.especificaciones_internas[lineaId];
                if (!ofertaLinea) {
                  return false;
                }
                
                {{-- La oferta puede tener un array de sublíneas o un solo valor (compatibilidad) --}}
                const ofertaSublineas = Array.isArray(ofertaLinea) ? ofertaLinea : [ofertaLinea];
                
                {{-- Verificar que al menos una de las sublíneas seleccionadas coincida con las de la oferta --}}
                const coincide = sublineasIds.some(sublineaId => {
                  return ofertaSublineas.some(ofertaSublinea => {
                    return String(ofertaSublinea) === String(sublineaId);
                  });
                });
                
                if (!coincide) {
                  return false;
                }
              }
              
              return true;
            });
          }
          
          {{-- Función para obtener ofertas filtradas por las especificaciones seleccionadas (excepto una línea específica) --}}
          {{-- _oof1: obtenerOfertasFiltradas - Obtiene ofertas filtradas excluyendo opcionalmente una línea específica --}}
          function _oof1(excluirLineaId = null) {
            let ofertasFiltradas = ofertas.slice();
            
            {{-- Aplicar filtros de especificaciones existentes (excepto la línea excluida) --}}
            const especificacionesTemp = { ...v13 };
            if (excluirLineaId && especificacionesTemp[excluirLineaId]) {
              delete especificacionesTemp[excluirLineaId];
            }
            
            {{-- Aplicar filtros temporales --}}
            for (const [tempLineaId, tempSublineasIds] of Object.entries(especificacionesTemp)) {
              if (tempSublineasIds.length === 0) continue;
              ofertasFiltradas = ofertasFiltradas.filter(oferta => {
                if (!oferta.especificaciones_internas || typeof oferta.especificaciones_internas !== 'object') {
                  return false;
                }
                const ofertaLinea = oferta.especificaciones_internas[tempLineaId];
                if (!ofertaLinea) return false;
                
                {{-- La oferta puede tener un array de sublíneas o un solo valor (compatibilidad) --}}
                const ofertaSublineas = Array.isArray(ofertaLinea) ? ofertaLinea : [ofertaLinea];
                
                return tempSublineasIds.some(tempSublineaId => {
                  return ofertaSublineas.some(ofertaSublinea => {
                    return String(ofertaSublinea) === String(tempSublineaId);
                  });
                });
              });
            }
            
            return ofertasFiltradas;
          }
          
          {{-- Función para verificar si una sublínea tiene ofertas disponibles --}}
          {{-- _tod1: tieneOfertasDisponibles - Verifica si una sublínea específica tiene ofertas disponibles --}}
          function _tod1(lineaId, sublineaId) {
            {{-- Obtener ofertas filtradas por todas las selecciones actuales (excepto esta línea) --}}
            const ofertasFiltradas = _oof1(lineaId);
            
            {{-- Si no hay ofertas filtradas, esta sublínea no tiene ofertas disponibles --}}
            if (ofertasFiltradas.length === 0) {
              return false;
            }
            
            {{-- Verificar si hay ofertas con esta sublínea específica --}}
            const sublineaIdStr = String(sublineaId);
            const tieneOfertas = ofertasFiltradas.some(oferta => {
              if (!oferta.especificaciones_internas || typeof oferta.especificaciones_internas !== 'object') {
                return false;
              }
              const ofertaLinea = oferta.especificaciones_internas[lineaId];
              if (!ofertaLinea) return false;
              
              {{-- La oferta puede tener un array de sublíneas o un solo valor (compatibilidad) --}}
              const ofertaSublineas = Array.isArray(ofertaLinea) ? ofertaLinea : [ofertaLinea];
              
              return ofertaSublineas.some(ofertaSublinea => {
                return String(ofertaSublinea) === sublineaIdStr;
              });
            });
            
            return tieneOfertas;
          }
          
          {{-- Función para contar las ofertas que aplican para una sublínea específica --}}
          {{-- _cos1: contarOfertasPorSublinea - Cuenta cuántas ofertas hay disponibles para una sublínea específica --}}
          function _cos1(lineaId, sublineaId) {
            {{-- Obtener ofertas filtradas excluyendo la línea actual --}}
            let ofertasFiltradas = _oof1(lineaId);
            
            {{-- Contar ofertas que tienen esta sublínea específica --}}
            const sublineaIdStr = String(sublineaId);
            const cantidad = ofertasFiltradas.filter(oferta => {
              if (!oferta.especificaciones_internas || typeof oferta.especificaciones_internas !== 'object') {
                return false;
              }
              const ofertaLinea = oferta.especificaciones_internas[lineaId];
              if (!ofertaLinea) return false;
              
              {{-- La oferta puede tener un array de sublíneas o un solo valor (compatibilidad) --}}
              const ofertaSublineas = Array.isArray(ofertaLinea) ? ofertaLinea : [ofertaLinea];
              
              return ofertaSublineas.some(ofertaSublinea => {
                return String(ofertaSublinea) === sublineaIdStr;
              });
            }).length;
            
            return cantidad;
          }
          
          {{-- Función para actualizar todos los contadores de sublíneas --}}
          {{-- _acs1: actualizarContadoresSublineas - Actualiza los contadores de ofertas en todas las sublíneas --}}
          function _acs1() {
            const contadores = document.querySelectorAll('.contador-sublinea');
            contadores.forEach(contador => {
              const lineaId = contador.dataset.lineaId;
              const sublineaId = contador.dataset.sublineaId;
              
              if (lineaId && sublineaId) {
                const cantidad = _cos1(lineaId, sublineaId);
                contador.textContent = `(${cantidad})`;
              }
            });
          }
          
          {{-- Función para verificar si una línea principal tiene al menos una sublínea disponible --}}
          {{-- _tlasd1: tieneLineaAlgunaSublineaDisponible - Verifica si una línea principal tiene al menos una sublínea con ofertas --}}
          function _tlasd1(lineaId) {
            {{-- Si esta es la primera línea seleccionada, todas sus sublíneas están disponibles --}}
            if (primeraLineaSeleccionada === lineaId) {
              return true;
            }
            
            {{-- Si no hay primera línea seleccionada, todas están disponibles --}}
            if (primeraLineaSeleccionada === null) {
              return true;
            }
            
            {{-- Para otras líneas, verificar si al menos una sublínea tiene ofertas disponibles --}}
            const botonesLinea = document.querySelectorAll(`.filtro-sublinea-btn[data-linea-id="${lineaId}"]`);
            
            {{-- Verificar si al menos una sublínea de esta línea tiene ofertas disponibles --}}
            for (const boton of botonesLinea) {
              const sublineaId = boton.dataset.sublineaId;
              if (_tod1(lineaId, sublineaId)) {
                return true;
              }
            }
            
            return false;
          }
          
          {{-- Función para actualizar los filtros dinámicamente según las ofertas mostradas --}}
          {{-- _afd1: actualizarFiltrosDinamicos - Actualiza los filtros dinámicos (cantidad, tienda) según las ofertas disponibles --}}
          function _afd1(ofertasFiltradas) {
            {{-- Obtener cantidades únicas de las ofertas filtradas --}}
            const cantidadesUnicas = [...new Set(ofertasFiltradas.map(o => o.unidades))].sort((a, b) => a - b);
            
            {{-- Actualizar el desplegable de cantidad --}}
            {{-- x3: Filtro de cantidad --}}
            const selectCantidad = document.getElementById('x3');
            {{-- x3c: Contenedor del filtro de cantidad --}}
            const containerCantidad = document.getElementById('x3c');
            const cantidadSeleccionada = selectCantidad ? selectCantidad.value : '';
            
            if (cantidadesUnicas.length <= 1) {
              {{-- Si solo hay una opción o ninguna, ocultar el filtro --}}
              if (containerCantidad) {
                containerCantidad.style.display = 'none';
              }
            } else {
              {{-- Si hay más de una opción, mostrar el filtro y actualizar opciones --}}
              if (containerCantidad) {
                containerCantidad.style.display = 'block';
              }
              
              if (selectCantidad) {
                {{-- Guardar la selección actual --}}
                const seleccionActual = cantidadSeleccionada;
                
                {{-- Limpiar opciones --}}
                selectCantidad.innerHTML = '<option value="">Todas</option>';
                
                {{-- Añadir nuevas opciones --}}
                cantidadesUnicas.forEach(cantidad => {
                  const option = document.createElement('option');
                  option.value = cantidad;
                  
                  {{-- Formatear la cantidad según la unidad de medida --}}
                  let textoCantidad = '';
                  const formatearNumero = (num) => {
                    if (num % 1 === 0) {
                      {{-- Número entero: añadir puntos para miles --}}
                      return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                    } else {
                      {{-- Número decimal: formatear con 2 decimales, reemplazar punto por coma --}}
                      const partes = num.toFixed(2).split('.');
                      const parteEntera = partes[0].replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                      return parteEntera + ',' + partes[1];
                    }
                  };
                  
                  if (unidadMedida === 'kilos') {
                    if (cantidad >= 1) {
                      textoCantidad = formatearNumero(cantidad) + ' Kilos';
                    } else {
                      const gramos = Math.round(cantidad * 1000);
                      textoCantidad = gramos.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.') + ' gramos';
                    }
                  } else if (unidadMedida === 'litros') {
                    if (cantidad >= 1) {
                      textoCantidad = formatearNumero(cantidad) + ' Litros';
                    } else {
                      const mililitros = Math.round(cantidad * 1000);
                      textoCantidad = mililitros.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.') + ' ml';
                    }
                  } else if (unidadMedida === 'unidadMilesima') {
                    textoCantidad = formatearNumero(cantidad) + ' Unidades';
                  } else if (unidadMedida === 'unidadUnica') {
                    textoCantidad = formatearNumero(cantidad);
                  } else {
                    textoCantidad = formatearNumero(cantidad) + ' Unidades';
                  }
                  
                  {{-- Contar ofertas para esta cantidad --}}
                  const ofertasParaEstaCantidad = ofertasFiltradas.filter(o => o.unidades === cantidad).length;
                  option.textContent = `${textoCantidad} (${ofertasParaEstaCantidad})`;
                  
                  {{-- Restaurar selección si existe --}}
                  if (seleccionActual && String(cantidad) === seleccionActual) {
                    option.selected = true;
                  }
                  
                  selectCantidad.appendChild(option);
                });
              }
            }
            
            {{-- Actualizar el desplegable de tienda --}}
            {{-- x1: Filtro de tienda --}}
            const selectTienda = document.getElementById('x1');
            const tiendasUnicas = [...new Set(ofertasFiltradas.map(o => o.tienda))].sort();
            const tiendaSeleccionada = selectTienda ? selectTienda.value : '';
            
            if (selectTienda) {
              {{-- Guardar la selección actual --}}
              const seleccionActual = tiendaSeleccionada;
              
              {{-- Limpiar opciones (mantener "Todas") --}}
              selectTienda.innerHTML = '<option value="">Todas</option>';
              
              {{-- Añadir nuevas opciones --}}
              tiendasUnicas.forEach(tienda => {
                const option = document.createElement('option');
                option.value = tienda;
                option.textContent = tienda;
                
                {{-- Restaurar selección si existe --}}
                if (seleccionActual && tienda === seleccionActual) {
                  option.selected = true;
                }
                
                selectTienda.appendChild(option);
              });
            }
          }
          
          {{-- Función para actualizar el estado de los botones --}}
          {{-- _aeb1: actualizarEstadoBotones - Actualiza el estado visual (habilitado/deshabilitado) de los botones de filtros --}}
          function _aeb1() {
            const botones = document.querySelectorAll('.filtro-sublinea-btn');
            const lineasPrincipales = new Set();
            
            {{-- Primero, recopilar todas las líneas principales --}}
            botones.forEach(boton => {
              lineasPrincipales.add(boton.dataset.lineaId);
            });
            
            {{-- Verificar si hay una primera línea seleccionada --}}
            const hayPrimeraLinea = primeraLineaSeleccionada !== null;
            
            botones.forEach(boton => {
              const lineaId = boton.dataset.lineaId;
              const sublineaId = String(boton.dataset.sublineaId);
              {{-- Convertir a string para comparación consistente --}}
              const estaSeleccionado = v13[lineaId] && 
                                       v13[lineaId].some(id => String(id) === sublineaId);
              
              {{-- Si es la primera línea seleccionada, verificar si tiene ofertas disponibles --}}
              if (primeraLineaSeleccionada === lineaId) {
                {{-- Verificar si esta sublínea tiene ofertas disponibles --}}
                const disponible = _tod1(lineaId, sublineaId);
                
                if (!disponible) {
                  {{-- Esta sublínea no tiene ofertas disponibles, desactivarla --}}
                  boton.disabled = true;
                  boton.style.setProperty('background-color', '#d1d5db', 'important');
                  boton.style.setProperty('color', '#6b7280', 'important');
                  boton.style.setProperty('border-color', '#9ca3af', 'important');
                  boton.classList.add('opacity-50', 'cursor-not-allowed');
                } else {
                  {{-- Esta sublínea tiene ofertas disponibles, habilitarla --}}
                  boton.disabled = false;
                  if (estaSeleccionado) {
                    boton.style.setProperty('background-color', '#3b82f6', 'important');
                    boton.style.setProperty('color', '#ffffff', 'important');
                    boton.style.setProperty('border-color', '#2563eb', 'important');
                    boton.classList.remove('opacity-50', 'cursor-not-allowed');
                  } else {
                    boton.style.setProperty('background-color', '#ffffff', 'important');
                    boton.style.setProperty('color', '#111827', 'important');
                    boton.style.setProperty('border-color', '#9ca3af', 'important');
                    boton.classList.remove('opacity-50', 'cursor-not-allowed');
                  }
                }
              } else if (hayPrimeraLinea) {
                {{-- Si hay una primera línea seleccionada y esta no es ella --}}
                {{-- Primero verificar si la línea completa tiene alguna sublínea disponible --}}
                const lineaTieneAlgunaDisponible = _tlasd1(lineaId);
                
                if (!lineaTieneAlgunaDisponible) {
                  {{-- Si la línea completa no tiene ninguna sublínea disponible, desactivar todos los botones --}}
                  boton.disabled = true;
                  boton.style.setProperty('background-color', '#d1d5db', 'important');
                  boton.style.setProperty('color', '#6b7280', 'important');
                  boton.style.setProperty('border-color', '#9ca3af', 'important');
                  boton.classList.add('opacity-50', 'cursor-not-allowed');
                  {{-- Si estaba seleccionado, deseleccionarlo visualmente --}}
                  if (estaSeleccionado) {
                    {{-- No cambiar el color si está deshabilitado, solo mantenerlo gris --}}
                  }
                } else {
                  {{-- Si la línea tiene alguna disponible, verificar esta sublínea específica --}}
                  const disponible = _tod1(lineaId, sublineaId);
                  
                  if (!disponible) {
                    {{-- Esta sublínea específica no tiene ofertas disponibles --}}
                    boton.disabled = true;
                    boton.style.setProperty('background-color', '#d1d5db', 'important');
                    boton.style.setProperty('color', '#6b7280', 'important');
                    boton.style.setProperty('border-color', '#9ca3af', 'important');
                    boton.classList.add('opacity-50', 'cursor-not-allowed');
                  } else {
                    {{-- Esta sublínea tiene ofertas disponibles --}}
                    boton.disabled = false;
                    boton.style.setProperty('background-color', estaSeleccionado ? '#3b82f6' : '#ffffff', 'important');
                    boton.style.setProperty('color', estaSeleccionado ? '#ffffff' : '#111827', 'important');
                    boton.style.setProperty('border-color', estaSeleccionado ? '#2563eb' : '#9ca3af', 'important');
                    boton.classList.remove('opacity-50', 'cursor-not-allowed');
                  }
                }
              } else {
                {{-- Si no hay primera línea seleccionada, verificar si esta sublínea tiene ofertas disponibles --}}
                const disponible = _tod1(lineaId, sublineaId);
                
                if (!disponible) {
                  {{-- Esta sublínea no tiene ofertas disponibles, desactivarla desde el principio --}}
                  boton.disabled = true;
                  boton.style.setProperty('background-color', '#d1d5db', 'important');
                  boton.style.setProperty('color', '#6b7280', 'important');
                  boton.style.setProperty('border-color', '#9ca3af', 'important');
                  boton.classList.add('opacity-50', 'cursor-not-allowed');
                } else {
                  {{-- Esta sublínea tiene ofertas disponibles --}}
                  boton.disabled = false;
                  if (estaSeleccionado) {
                    boton.style.setProperty('background-color', '#3b82f6', 'important');
                    boton.style.setProperty('color', '#ffffff', 'important');
                    boton.style.setProperty('border-color', '#2563eb', 'important');
                    boton.classList.remove('opacity-50', 'cursor-not-allowed');
                  } else {
                    boton.style.setProperty('background-color', '#ffffff', 'important');
                    boton.style.setProperty('color', '#111827', 'important');
                    boton.style.setProperty('border-color', '#9ca3af', 'important');
                    boton.classList.remove('opacity-50', 'cursor-not-allowed');
                  }
                }
              }
            });
          }
          
          {{-- Configurar event listeners para los botones de especificaciones --}}
          {{-- _cbe1: configurarBotonesEspecificaciones - Configura los event listeners para los botones de especificaciones (click, long press) --}}
          function _cbe1() {
            const botones = document.querySelectorAll('.filtro-sublinea-btn');
            botones.forEach(boton => {
              {{-- Detectar long press en móvil para botones con imágenes --}}
              let longPressTimer = null;
              let hasMoved = false;
              let touchStartX = 0;
              let touchStartY = 0;
              let longPressExecuted = false; {{-- Flag para saber si se ejecutó long press --}}
              
              const imagenesSublinea = boton.dataset.imagenes ? JSON.parse(boton.dataset.imagenes) : null;
              const tieneImagenes = imagenesSublinea && Array.isArray(imagenesSublinea) && imagenesSublinea.length > 0;
              
              if (tieneImagenes) {
                {{-- Solo en móvil (ancho < 640px) --}}
                boton.addEventListener('touchstart', function(e) {
                  if (this.disabled) return;
                  
                  hasMoved = false;
                  longPressExecuted = false;
                  const touch = e.touches[0];
                  touchStartX = touch.clientX;
                  touchStartY = touch.clientY;
                  
                  longPressTimer = setTimeout(() => {
                    if (!hasMoved) {
                      {{-- Long press detectado - abrir modal con imágenes de la sublínea --}}
                      e.preventDefault();
                      e.stopPropagation();
                      
                      longPressExecuted = true;
                      
                      {{-- Marcar que el modal se abrió desde long press --}}
                      v5 = true;
                      
                      {{-- Cambiar las imágenes del producto temporalmente a las de la sublínea --}}
                      _cip1(imagenesSublinea);
                      
                      {{-- Abrir el modal --}}
                      if (typeof _am1 === 'function') {
                        _am1();
                      }
                    }
                  }, 500); // 500ms para long press
                }, { passive: true });
                
                boton.addEventListener('touchmove', function(e) {
                  if (!longPressTimer) return;
                  
                  const touch = e.touches[0];
                  const deltaX = Math.abs(touch.clientX - touchStartX);
                  const deltaY = Math.abs(touch.clientY - touchStartY);
                  
                  {{-- Si se mueve más de 10px, cancelar long press --}}
                  if (deltaX > 10 || deltaY > 10) {
                    hasMoved = true;
                    if (longPressTimer) {
                      clearTimeout(longPressTimer);
                      longPressTimer = null;
                    }
                  }
                }, { passive: true });
                
                boton.addEventListener('touchend', function(e) {
                  if (longPressTimer) {
                    clearTimeout(longPressTimer);
                    longPressTimer = null;
                  }
                  
                  {{-- Si se ejecutó long press, prevenir el click normal --}}
                  if (longPressExecuted) {
                    e.preventDefault();
                    e.stopPropagation();
                    {{-- Resetear flag después de un pequeño delay --}}
                    setTimeout(() => {
                      longPressExecuted = false;
                    }, 100);
                  }
                }, { passive: false });
                
                boton.addEventListener('touchcancel', function(e) {
                  if (longPressTimer) {
                    clearTimeout(longPressTimer);
                    longPressTimer = null;
                  }
                  longPressExecuted = false;
                }, { passive: true });
              }
              
              boton.addEventListener('click', function(e) {
                {{-- Si se ejecutó long press, no ejecutar el click normal --}}
                if (longPressExecuted) {
                  e.preventDefault();
                  e.stopPropagation();
                  return;
                }
                if (this.disabled) return;
                
                const lineaId = this.dataset.lineaId;
                const sublineaId = this.dataset.sublineaId;
                
                {{-- Inicializar array si no existe --}}
                if (!v13[lineaId]) {
                  v13[lineaId] = [];
                }
                
                {{-- Si es la primera selección, marcar esta línea como primera --}}
                if (primeraLineaSeleccionada === null) {
                  {{-- Verificar si hay alguna selección previa --}}
                  const haySeleccionesPrevias = Object.keys(v13).length > 0 || 
                                                Object.values(v13).some(arr => arr.length > 0);
                  if (!haySeleccionesPrevias) {
                    primeraLineaSeleccionada = lineaId;
                  }
                }
                
                {{-- Obtener imágenes de la sublínea si tiene --}}
                const imagenesSublinea = this.dataset.imagenes ? JSON.parse(this.dataset.imagenes) : null;
                
                {{-- Toggle: si ya está seleccionada, deseleccionar --}}
                {{-- Convertir a string para comparación consistente --}}
                const sublineaIdStr = String(sublineaId);
                const index = v13[lineaId].findIndex(id => String(id) === sublineaIdStr);
                if (index > -1) {
                  v13[lineaId].splice(index, 1);
                  {{-- Si se vacía la línea, eliminar la entrada --}}
                  if (v13[lineaId].length === 0) {
                    delete v13[lineaId];
                    {{-- Si era la primera línea, resetear --}}
                    if (primeraLineaSeleccionada === lineaId) {
                      primeraLineaSeleccionada = null;
                      {{-- Si hay otras líneas seleccionadas, la primera de ellas será la nueva primera --}}
                      const otrasLineas = Object.keys(v13);
                      if (otrasLineas.length > 0) {
                        primeraLineaSeleccionada = otrasLineas[0];
                      }
                    }
                  }
                  
                  {{-- Restaurar imágenes: buscar si hay otra sublínea seleccionada con imágenes --}}
                  _rio1();
                } else {
                  v13[lineaId].push(sublineaIdStr);
                  {{-- Si es la primera selección de cualquier línea, marcar como primera línea --}}
                  if (primeraLineaSeleccionada === null) {
                    primeraLineaSeleccionada = lineaId;
                  }
                  
                  {{-- Cambiar imágenes si la sublínea tiene imágenes --}}
                  {{-- Si hay múltiples sublíneas con imágenes, usar la última seleccionada --}}
                  if (imagenesSublinea && imagenesSublinea.length > 0) {
                    _cip1(imagenesSublinea);
                  } else {
                    {{-- Si esta sublínea no tiene imágenes, buscar si hay otra seleccionada con imágenes --}}
                    _rio1();
                  }
                }
                
                {{-- Actualizar estado de botones PRIMERO para deshabilitar los que no tienen ofertas --}}
                _aeb1();
                {{-- Actualizar contadores de sublíneas --}}
                _acs1();
                {{-- Luego renderizar las ofertas --}}
                _ro1();
                {{-- Actualizar oferta en el modal si está abierto --}}
                if (typeof _rod1 === 'function') {
                  {{-- x18: Modal de imágenes --}}
                  const modal = document.getElementById('x18');
                  if (modal && !modal.classList.contains('hidden')) {
                    _rod1();
                  }
                }
              });
            });
          }
          
          {{-- Función para obtener el texto de una sublínea por su ID --}}
          {{-- _ots1: obtenerTextoSublinea - Obtiene el texto descriptivo de una sublínea por su ID --}}
          function _ots1(principalId, sublineaId) {
            if (!columnasData || !Array.isArray(columnasData)) return '-';
            const lineaPrincipal = columnasData.find(l => l.id === principalId);
            if (!lineaPrincipal || !lineaPrincipal.subprincipales) return '-';
            const sublinea = lineaPrincipal.subprincipales.find(s => s.id === sublineaId);
            return sublinea ? sublinea.texto : '-';
          }
          
          {{-- Función para renderizar columnas dinámicas para unidadUnica --}}
          {{-- _ots1: obtenerTextoSublinea (sobrecarga) - Obtiene el texto descriptivo de una sublínea por su ID (versión alternativa) --}}
          function _ots1(lineaId, sublineaId) {
            if (!columnasData || !Array.isArray(columnasData)) {
              return '-';
            }
            
            const linea = columnasData.find(l => l.id === lineaId);
            if (!linea || !linea.subprincipales || !Array.isArray(linea.subprincipales)) {
              return '-';
            }
            
            const sublinea = linea.subprincipales.find(s => s.id === sublineaId);
            return sublinea ? sublinea.texto : '-';
          }
          
          {{-- _rcuu1: renderizarColumnasUnidadUnica - Renderiza las columnas dinámicas para productos de unidad única --}}
          function _rcuu1(oferta) {
            if (!esUnidadUnica || !columnasData || !Array.isArray(columnasData) || columnasData.length === 0) {
              return '';
            }
            
            const numColumnas = columnasData.length;
            const especificacionesOferta = oferta.especificaciones_internas || {};
            const columnasOferta = especificacionesOferta._columnas || {};
            
            let html = '';
            
            {{-- En móvil: las columnas dinámicas van después del precio (order-4, order-5, etc.) --}}
            {{-- En desktop: sin order (orden natural) --}}
            if (numColumnas === 1) {
              {{-- 1 línea: 1 columna normal (estilo como cantidad) --}}
              const linea = columnasData[0];
              const sublineaId = columnasOferta[linea.id];
              const textoSublinea = sublineaId ? _ots1(linea.id, sublineaId) : '-';
              
              html += `<div class="columna-dinamica text-gray-700 divider text-center min-w-0 overflow-hidden order-4 sm:!order-[0]"><div class="font-semibold">${linea.texto}</div><div class="text-sm text-gray-500 leading-tight">${textoSublinea}</div></div>`;
            } else if (numColumnas === 2) {
              {{-- 2 líneas: 2 columnas normales (estilo como cantidad) --}}
              columnasData.forEach((linea, index) => {
                const sublineaId = columnasOferta[linea.id];
                const textoSublinea = sublineaId ? _ots1(linea.id, sublineaId) : '-';
                const ordenMovil = index === 0 ? 'order-4' : 'order-5';
                
                html += `<div class="columna-dinamica text-gray-700 divider text-center min-w-0 overflow-hidden ${ordenMovil} sm:!order-[0]"><div class="font-semibold">${linea.texto}</div><div class="text-sm text-gray-500 leading-tight">${textoSublinea}</div></div>`;
              });
            } else if (numColumnas === 3) {
              {{-- 3 líneas: 1 columna normal (estilo como cantidad) + 1 columna dividida en 2 filas (nombre y opción en misma línea) --}}
              const linea1 = columnasData[0];
              const sublineaId1 = columnasOferta[linea1.id];
              const textoSublinea1 = sublineaId1 ? _ots1(linea1.id, sublineaId1) : '-';
              
              html += `<div class="columna-dinamica text-gray-700 divider text-center min-w-0 overflow-hidden order-4 sm:!order-[0]"><div class="font-semibold">${linea1.texto}</div><div class="text-sm text-gray-500 leading-tight">${textoSublinea1}</div></div>`;
              
              {{-- Segunda columna dividida en 2 filas (estilo como envío - nombre y opción en misma línea) --}}
              html += `<div class="columna-dinamica text-gray-700 divider text-center min-w-0 overflow-hidden order-5 sm:!order-[0]">`;
              for (let i = 1; i < 3; i++) {
                const linea = columnasData[i];
                const sublineaId = columnasOferta[linea.id];
                const textoSublinea = sublineaId ? _ots1(linea.id, sublineaId) : '-';
                
                html += `<div class="text-xs leading-tight" style="font-weight: 600;"><span class="font-semibold">${linea.texto}:</span> <span class="text-gray-500">${textoSublinea}</span></div>`;
                if (i < 2) html += `<div class="border-t border-gray-300 mt-2 mb-1"></div>`;
              }
              html += `</div>`;
            } else if (numColumnas === 4) {
              {{-- 4 líneas: 2 columnas divididas en 2 filas cada una --}}
              for (let col = 0; col < 2; col++) {
                const ordenMovil = col === 0 ? 'order-4' : 'order-5';
                html += `<div class="columna-dinamica text-gray-700 divider text-center min-w-0 overflow-hidden ${ordenMovil} sm:!order-[0]">`;
                for (let fila = 0; fila < 2; fila++) {
                  const index = col * 2 + fila;
                  const linea = columnasData[index];
                  const sublineaId = columnasOferta[linea.id];
                  const textoSublinea = sublineaId ? _ots1(linea.id, sublineaId) : '-';
                  
                  html += `<div class="text-xs leading-tight" style="font-weight: 600;"><span class="font-semibold">${linea.texto}:</span> <span class="text-gray-500">${textoSublinea}</span></div>`;
                  if (fila < 1) html += `<div class="border-t border-gray-300 mt-2 mb-1"></div>`;
                }
                html += `</div>`;
              }
            }
            
            return html;
          }
          
          {{-- Función para procesar grupos de ofertas y unificar las que están en grupos --}}
          {{-- _pgo1: procesarGruposOfertas - Procesa y agrupa las ofertas según sus especificaciones --}}
          function _pgo1(filtradas) {
            if (!esUnidadUnica || !gruposDeOfertas || Object.keys(gruposDeOfertas).length === 0) {
              {{-- Si no hay grupos o no es unidadUnica, devolver las ofertas sin agrupar --}}
              return {
                gruposUnificados: [],
                ofertasNoAgrupadas: filtradas,
                ofertasEnGrupos: []
              };
            }
            
            {{-- Crear mapa de ofertas por ID para búsqueda rápida (normalizar IDs a números) --}}
            const ofertasMap = {};
            filtradas.forEach(o => {
              const id = Number(o.id);
              ofertasMap[id] = o;
              ofertasMap[o.id] = o; {{-- También mantener el ID original por si acaso --}}
            });
            
            {{-- Identificar ofertas que están en grupos --}}
            const ofertasIdsEnGrupos = new Set();
            const gruposPorPrecio = {}; {{-- { "grupoId_precioUnidad": { grupoId, precioUnidad, ofertasIds: [], tiendaId } } --}}
            
            for (const [grupoId, grupoData] of Object.entries(gruposDeOfertas)) {
              if (!Array.isArray(grupoData) || grupoData.length < 2) continue;
              
              const tiendaIdGrupo = grupoData[0];
              const ofertasIdsGrupo = Array.isArray(grupoData[1]) ? grupoData[1] : [];
              
              {{-- Agrupar ofertas del mismo grupo por precio_unidad --}}
              const ofertasPorPrecio = {};
              
              for (const ofertaId of ofertasIdsGrupo) {
                {{-- Normalizar el ID a número para comparación --}}
                const idNum = Number(ofertaId);
                const oferta = ofertasMap[idNum] || ofertasMap[ofertaId];
                
                if (!oferta) {
                  continue; {{-- La oferta no está en las filtradas --}}
                }
                
                {{-- Añadir tanto el ID numérico como el original al Set --}}
                ofertasIdsEnGrupos.add(idNum);
                ofertasIdsEnGrupos.add(oferta.id);
                
                const precioUnidad = oferta.precio_unidad;
                
                if (!ofertasPorPrecio[precioUnidad]) {
                  ofertasPorPrecio[precioUnidad] = [];
                }
                ofertasPorPrecio[precioUnidad].push(oferta);
              }
              
              {{-- Crear grupos unificados por precio_unidad --}}
              for (const [precioUnidad, ofertasGrupo] of Object.entries(ofertasPorPrecio)) {
                if (ofertasGrupo.length >= 1) { // Incluir grupos con una o más ofertas
                  const key = `${grupoId}_${precioUnidad}`;
                  gruposPorPrecio[key] = {
                    grupoId,
                    precioUnidad,
                    ofertas: ofertasGrupo,
                    ofertasIds: ofertasGrupo.map(o => Number(o.id)),
                    tiendaId: tiendaIdGrupo
                  };
                }
              }
            }
            
            {{-- Separar ofertas agrupadas y no agrupadas (comparar con IDs normalizados) --}}
            const ofertasNoAgrupadas = filtradas.filter(o => {
              const idNum = Number(o.id);
              return !ofertasIdsEnGrupos.has(idNum) && !ofertasIdsEnGrupos.has(o.id);
            });
            
            const gruposUnificados = Object.values(gruposPorPrecio).map(grupo => grupo);
            
            return {
              gruposUnificados,
              ofertasNoAgrupadas,
              ofertasEnGrupos: Array.from(ofertasIdsEnGrupos)
            };
          }
          
          {{-- Función para renderizar columnas de un grupo unificado (muestra todas las variantes) --}}
          {{-- _rcgu1: renderizarColumnasGrupoUnificado - Renderiza las columnas dinámicas para un grupo unificado de ofertas --}}
          function _rcgu1(ofertasGrupo) {
            if (!esUnidadUnica || !columnasData || !Array.isArray(columnasData) || columnasData.length === 0) {
              return '';
            }
            
            const numColumnas = columnasData.length;
            let html = '';
            
            {{-- Obtener todas las variantes únicas por columna --}}
            const variantesPorColumna = {};
            
            columnasData.forEach(linea => {
              const variantes = new Set();
              
              ofertasGrupo.forEach(oferta => {
                const especificaciones = oferta.especificaciones_internas || {};
                const columnasOferta = especificaciones._columnas || {};
                const sublineaId = columnasOferta[linea.id];
                
                if (sublineaId) {
                  const textoSublinea = _ots1(linea.id, sublineaId);
                  if (textoSublinea && textoSublinea !== '-') {
                    variantes.add(textoSublinea);
                  }
                }
              });
              
              variantesPorColumna[linea.id] = Array.from(variantes).sort();
            });
            
            {{-- Renderizar según número de columnas --}}
            if (numColumnas === 1) {
              const linea = columnasData[0];
              const variantes = variantesPorColumna[linea.id] || [];
              const textoVariantes = variantes.length > 0 ? variantes.join(', ') : '-';
              
              html += `<div class="columna-dinamica text-gray-700 divider text-center min-w-0 overflow-hidden order-4 sm:!order-[0]"><div class="font-semibold">${linea.texto}</div><div class="text-sm text-gray-500 leading-tight">${textoVariantes}</div></div>`;
            } else if (numColumnas === 2) {
              columnasData.forEach((linea, index) => {
                const variantes = variantesPorColumna[linea.id] || [];
                const textoVariantes = variantes.length > 0 ? variantes.join(', ') : '-';
                const ordenMovil = index === 0 ? 'order-4' : 'order-5';
                
                html += `<div class="columna-dinamica text-gray-700 divider text-center min-w-0 overflow-hidden ${ordenMovil} sm:!order-[0]"><div class="font-semibold">${linea.texto}</div><div class="text-sm text-gray-500 leading-tight">${textoVariantes}</div></div>`;
              });
            } else if (numColumnas === 3) {
              const linea1 = columnasData[0];
              const variantes1 = variantesPorColumna[linea1.id] || [];
              const textoVariantes1 = variantes1.length > 0 ? variantes1.join(', ') : '-';
              
              html += `<div class="columna-dinamica text-gray-700 divider text-center min-w-0 overflow-hidden order-4 sm:!order-[0]"><div class="font-semibold">${linea1.texto}</div><div class="text-sm text-gray-500 leading-tight">${textoVariantes1}</div></div>`;
              
              html += `<div class="columna-dinamica text-gray-700 divider text-center min-w-0 overflow-hidden order-5 sm:!order-[0]">`;
              for (let i = 1; i < 3; i++) {
                const linea = columnasData[i];
                const variantes = variantesPorColumna[linea.id] || [];
                const textoVariantes = variantes.length > 0 ? variantes.join(', ') : '-';
                
                html += `<div class="text-xs leading-tight" style="font-weight: 600;"><span class="font-semibold">${linea.texto}:</span> <span class="text-gray-500">${textoVariantes}</span></div>`;
                if (i < 2) html += `<div class="border-t border-gray-300 mt-2 mb-1"></div>`;
              }
              html += `</div>`;
            } else if (numColumnas === 4) {
              for (let col = 0; col < 2; col++) {
                const ordenMovil = col === 0 ? 'order-4' : 'order-5';
                html += `<div class="columna-dinamica text-gray-700 divider text-center min-w-0 overflow-hidden ${ordenMovil} sm:!order-[0]">`;
                for (let fila = 0; fila < 2; fila++) {
                  const index = col * 2 + fila;
                  const linea = columnasData[index];
                  const variantes = variantesPorColumna[linea.id] || [];
                  const textoVariantes = variantes.length > 0 ? variantes.join(', ') : '-';
                  
                  html += `<div class="text-xs leading-tight" style="font-weight: 600;"><span class="font-semibold">${linea.texto}:</span> <span class="text-gray-500">${textoVariantes}</span></div>`;
                  if (fila < 1) html += `<div class="border-t border-gray-300 mt-2 mb-1"></div>`;
                }
                html += `</div>`;
              }
            }
            
            return html;
          }
          
          {{-- _ro1: renderOfertas - Renderiza todas las ofertas en el contenedor principal --}}
          function _ro1() {
            try {
            {{-- x1: Filtro de tienda --}}
            const tienda = document.getElementById('x1').value;
            {{-- x2: Filtro de envío gratis --}}
            const envioGratis = document.getElementById('x2').checked;
            {{-- x3: Filtro de cantidad --}}
            const cantidad = document.getElementById('x3').value;
            let filtradas = ofertas.slice();
            
            {{-- Aplicar filtro de especificaciones internas primero --}}
            filtradas = _fpe1(filtradas);
            
            {{-- Actualizar filtros dinámicamente según las ofertas filtradas por especificaciones --}}
            _afd1(filtradas);
            
            {{-- Aplicar otros filtros sobre las ofertas ya filtradas por especificaciones --}}
            {{-- x1: Filtro de tienda --}}
            {{-- x2: Filtro de envío gratis --}}
            {{-- x3: Filtro de cantidad --}}
            if (tienda) filtradas = filtradas.filter(o => o.tienda === tienda);
            if (envioGratis) filtradas = filtradas.filter(o => (o.envio_gratis && o.envio_gratis.toLowerCase().includes('gratis')));
            if (cantidad) filtradas = filtradas.filter(o => String(o.unidades) === cantidad);
            
            {{-- Procesar grupos de ofertas (solo si es unidadUnica) --}}
            let { gruposUnificados, ofertasNoAgrupadas, ofertasEnGrupos } = _pgo1(filtradas);
            
            {{-- Combinar grupos unificados con ofertas no agrupadas para ordenar --}}
            let todasLasOfertas = [...ofertasNoAgrupadas];
            
            {{-- Crear array unificado con grupos y ofertas para ordenarlos juntos --}}
            let itemsUnificados = [];
            
            {{-- Añadir grupos como items --}}
            gruposUnificados.forEach(grupo => {
              itemsUnificados.push({
                tipo: 'grupo',
                datos: grupo,
                precio_unidad: grupo.precioUnidad ? parseFloat(String(grupo.precioUnidad).replace(',', '.')) : Infinity,
                precio_total: grupo.ofertas[0]?.precio_total ? parseFloat(String(grupo.ofertas[0].precio_total).replace(',', '.')) : Infinity
              });
            });
            
            {{-- Añadir ofertas individuales como items --}}
            todasLasOfertas.forEach(oferta => {
              itemsUnificados.push({
                tipo: 'oferta',
                datos: oferta,
                precio_unidad: oferta.precio_unidad ? parseFloat(String(oferta.precio_unidad).replace(',', '.')) : Infinity,
                precio_total: oferta.precio_total ? parseFloat(String(oferta.precio_total).replace(',', '.')) : Infinity
              });
            });
            
            {{-- Ordenar todos los items juntos --}}
            if (ordenActual === 'precio_total') {
              itemsUnificados.sort((a, b) => a.precio_total - b.precio_total);
            } else if (ordenActual === 'unidades') {
              itemsUnificados.sort((a, b) => a.precio_unidad - b.precio_unidad);
            } else {
              {{-- Por defecto, ordenar por precio_unidad --}}
              itemsUnificados.sort((a, b) => a.precio_unidad - b.precio_unidad);
            }
            
            {{-- Mantener itemsUnificados para renderizar en orden, pero también separar para otras operaciones --}}
            gruposUnificados = itemsUnificados.filter(item => item.tipo === 'grupo').map(item => item.datos);
            todasLasOfertas = itemsUnificados.filter(item => item.tipo === 'oferta').map(item => item.datos);
            
            {{-- Identificar las mejores ofertas (precio por unidad más barato) DESPUÉS de la ordenación --}}
            let mejoresOfertas = [];
            let mejorPrecio = Infinity;
            
            // Considerar ofertas individuales y grupos unificados
            todasLasOfertas.forEach(o => {
              if (o.precio_unidad) {
                const precio = parseFloat(String(o.precio_unidad).replace(',', '.'));
                if (!isNaN(precio) && precio < mejorPrecio) mejorPrecio = precio;
              }
            });
            
            gruposUnificados.forEach(grupo => {
              // El grupo tiene precioUnidad (camelCase), no precio_unidad
              if (grupo.precioUnidad) {
                const precio = parseFloat(String(grupo.precioUnidad).replace(',', '.'));
                if (!isNaN(precio) && precio < mejorPrecio) mejorPrecio = precio;
              }
            });
            
            if (mejorPrecio !== Infinity) {
              mejoresOfertas = todasLasOfertas.filter(o => {
                if (!o.precio_unidad) return false;
                const precio = parseFloat(String(o.precio_unidad).replace(',', '.'));
                return !isNaN(precio) && precio === mejorPrecio;
              });
              gruposUnificados.forEach(grupo => {
                // El grupo tiene precioUnidad (camelCase), no precio_unidad
                if (grupo.precioUnidad) {
                  const precio = parseFloat(String(grupo.precioUnidad).replace(',', '.'));
                  if (!isNaN(precio) && precio === mejorPrecio) {
                    mejoresOfertas.push(...grupo.ofertas);
                  }
                }
              });
            }
            
            // Si ordenActual es null, respeta el orden original del backend
            {{-- x6: Contenedor del listado de ofertas --}}
            const cont = document.getElementById('x6');
            {{-- x7: Contenedor del botón mostrar más --}}
            const botonMostrarMas = document.getElementById('x7');
            cont.innerHTML = '';
            
            const totalItems = todasLasOfertas.length + gruposUnificados.length;
            
            if (totalItems === 0) {
              if (filtroInteractuado) {
                cont.innerHTML = `<div class='bg-red-100 border-l-4 border-red-500 text-red-800 p-6 rounded text-lg text-center'>No hay ofertas que coincidan con los filtros seleccionados.</div>`;
              } else {
                cont.innerHTML = `<div class='bg-yellow-100 border-l-4 border-yellow-500 text-yellow-800 p-6 rounded text-lg text-center'>No hay ofertas disponibles actualmente para este producto.</div>`;
              }
              botonMostrarMas.classList.add('hidden');
              return;
            }
            
            // Determinar cuántas ofertas mostrar (considerando grupos como un solo item)
            const itemsAMostrar = mostrarTodasLasOfertas ? totalItems : Math.min(totalItems, OFERTAS_INICIALES);
            const itemsRestantes = totalItems - itemsAMostrar;
            
            // Mostrar/ocultar botón de "mostrar más"
            if (itemsRestantes > 0 && !mostrarTodasLasOfertas) {
              {{-- x9: Contador de ofertas restantes --}}
              document.getElementById('x9').textContent = itemsRestantes;
              botonMostrarMas.classList.remove('hidden');
            } else {
              botonMostrarMas.classList.add('hidden');
            }
            
            // Crear grupos de ofertas
            let html = '';
            let itemIndex = 0;
            let ofertasRenderizadas = [];
            
            // Renderizar items unificados en orden (grupos y ofertas intercalados)
            for (let idxUnificado = 0; idxUnificado < itemsUnificados.length; idxUnificado++) {
              const itemOrdenado = itemsUnificados[idxUnificado];
              
              if (!mostrarTodasLasOfertas && itemIndex >= OFERTAS_INICIALES) break;
              
              // Si es un grupo unificado
              if (itemOrdenado.tipo === 'grupo') {
                const grupo = itemOrdenado.datos;
                const primeraOferta = grupo.ofertas[0];
                const esMejorOferta = mejoresOfertas.some(mejor => mejor.id === primeraOferta.id);
                
                // Crear contenedor para el grupo unificado
                const tieneDescuentosEnGrupo = grupo.ofertas.some(item => 
                  item.descuentos === 'cupon' || 
                  item.descuentos === '3x2' ||
                  (item.descuentos && typeof item.descuentos === 'string' && (
                    item.descuentos.startsWith('cupon;') ||
                    item.descuentos.startsWith('SoloAliexpress;') ||
                    item.descuentos.startsWith('CholloTienda;') ||
                    item.descuentos.startsWith('CholloTienda1SoloCuponQueAplicaDescuento;')
                  ))
                );
                
                if (esMejorOferta) {
                  html += `<div class="mejor-oferta-wrapper" ${tieneDescuentosEnGrupo ? 'style="padding-top: 8px;"' : 'style="padding-top: 8px;"'}>`;
                  html += '<div class="mejor-oferta-badge-grupo">🏆 Mejor precio</div>';
                  html += '<div class="mejor-oferta-gaming-container">';
                  html += '<div class="ofertas-grupo">';
                }
                
                // Renderizar fila unificada del grupo
                const item = primeraOferta;
                
                // Intercambiar estilos según filtro activo
                let precioTotalClass = 'text-sm text-gray-500';
                let precioTotalLabelClass = 'font-semibold';
                let precioUnidadClass = 'text-3xl font-extrabold text-pink-400';
                let precioUnidadLabelClass = 'text-sm text-gray-500';
                if (ordenActual === 'precio_total') {
                  precioTotalClass = 'text-3xl font-extrabold text-pink-400';
                  precioTotalLabelClass = 'text-sm text-gray-500';
                  precioUnidadClass = 'text-sm text-gray-500';
                  precioUnidadLabelClass = 'font-semibold';
                }
                
                // Determinar estructura de grid según unidadUnica
                let gridCols = 'sm:grid-cols-[100px_1fr_1fr_1fr_1fr_auto]';
                let gridColsMovil = 'grid-cols-1';
                let columnasDinamicas = '';
                let mostrarCantidad = true;
                let mostrarPrecioTotal = true;
                let mostrarPrecioUnidad = true;
                let esUnidadUnicaConColumnas = false;
                let esUnidadUnicaSinColumnas = false;
                
                // Caso 1: Unidad única CON columnas marcadas
                if (esUnidadUnica && columnasData && columnasData.length > 0) {
                  mostrarCantidad = false;
                  mostrarPrecioTotal = false;
                  mostrarPrecioUnidad = false;
                  columnasDinamicas = _rcgu1(grupo.ofertas);
                  esUnidadUnicaConColumnas = true;
                  
                  // Para móvil/tablet: grid dinámico según número de columnas
                  const numColumnas = columnasData.length;
                  if (numColumnas === 1) {
                    gridColsMovil = 'grid-cols-2';
                  } else {
                    gridColsMovil = 'grid-cols-3';
                  }
                  
                  // Calcular grid según número de columnas dinámicas para desktop
                  if (numColumnas === 1) {
                    gridCols = 'sm:grid-cols-[100px_1fr_1fr_1fr_1fr_auto]';
                  } else if (numColumnas === 2) {
                    gridCols = 'sm:grid-cols-[100px_1fr_1fr_1fr_1fr_auto]';
                  } else if (numColumnas === 3 || numColumnas === 4) {
                    gridCols = 'sm:grid-cols-[100px_1fr_1fr_1fr_1fr_auto]';
                  }
                }
                // Caso 2: Unidad única SIN columnas marcadas
                else if (esUnidadUnica && (!columnasData || columnasData.length === 0)) {
                  mostrarCantidad = false;
                  mostrarPrecioTotal = true;
                  mostrarPrecioUnidad = false;
                  esUnidadUnicaSinColumnas = true;
                  precioTotalLabelClass = 'text-sm text-gray-500';
                  gridCols = 'sm:grid-cols-[100px_1fr_1fr_auto]';
                  gridColsMovil = 'grid-cols-3';
                }
                
                // En móvil (unidad única CON columnas): Primera fila (Logo order-1, Precio order-2), Segunda fila (Envío order-3, Columnas order-4+)
                // En móvil (unidad única SIN columnas): Primera fila (Logo order-1, Envío order-2, Precio order-3), Segunda fila (Botón order-4)
                const ordenLogo = esUnidadUnicaConColumnas ? 'order-1 sm:!order-[0]' : (esUnidadUnicaSinColumnas ? 'order-1 sm:!order-[0]' : '');
                const ordenEnvio = esUnidadUnicaConColumnas ? 'order-3 sm:!order-[0]' : (esUnidadUnicaSinColumnas ? 'order-2 sm:!order-[0]' : '');
                const ordenPrecio = esUnidadUnicaConColumnas ? 'order-2 sm:!order-[0]' : (esUnidadUnicaSinColumnas ? 'order-3 sm:!order-[0]' : '');
                
                // Botón: después de las columnas dinámicas en móvil (con columnas) o en segunda fila (sin columnas)
                const numColumnas = esUnidadUnicaConColumnas ? columnasData.length : 0;
                let colSpanBoton = '';
                let ordenBoton = '';
                
                if (esUnidadUnicaConColumnas) {
                  colSpanBoton = numColumnas === 1 ? 'col-span-2' : 'col-span-3';
                  ordenBoton = numColumnas === 1 ? `order-5 sm:!order-[0] ${colSpanBoton} sm:!col-span-1` : `order-6 sm:!order-[0] ${colSpanBoton} sm:!col-span-1`;
                } else if (esUnidadUnicaSinColumnas) {
                  colSpanBoton = 'col-span-3';
                  ordenBoton = `order-4 sm:!order-[0] ${colSpanBoton} sm:!col-span-1`;
                }
                
                // Usar la primera oferta del grupo para datos comunes (logo, envío, precio, descuentos)
                const tieneDescuento = primeraOferta.descuentos && (
                  primeraOferta.descuentos === 'cupon' || 
                  primeraOferta.descuentos === '3x2' ||
                  (typeof primeraOferta.descuentos === 'string' && (
                    primeraOferta.descuentos.startsWith('cupon;') ||
                    primeraOferta.descuentos.startsWith('SoloAliexpress;') ||
                    primeraOferta.descuentos.startsWith('CholloTienda;') ||
                    primeraOferta.descuentos.startsWith('CholloTienda1SoloCuponQueAplicaDescuento;')
                  ))
                );
                
                // Generar botón con descuentos (usando la primera oferta)
                let botonHtml = '';
                if (primeraOferta.descuentos && typeof primeraOferta.descuentos === 'string' && primeraOferta.descuentos.startsWith('SoloAliexpress;')) {
                  botonHtml = '<span class="cupon-badge" style="background: linear-gradient(135deg, #ff6900, #ff8c00); top: 0px; right: 0px; bottom: auto; font-size: 0.7rem; padding: 2px 6px; font-weight: bold; white-space: nowrap;">CUPÓN</span>' +
                    '<span class="inline-block w-full py-3 px-2 bg-blue-500 text-white text-base font-semibold rounded hover:bg-blue-600" data-cupon-aliexpress="true" data-descuentos="' + primeraOferta.descuentos.replace(/"/g, '&quot;') + '" data-oferta-id="' + primeraOferta.id + '" data-url="' + primeraOferta.url + '">Ir a la tienda</span>';
                } else if (primeraOferta.descuentos && typeof primeraOferta.descuentos === 'string' && primeraOferta.descuentos.startsWith('CholloTienda1SoloCuponQueAplicaDescuento;')) {
                  botonHtml = '<span class="cupon-badge" style="background: linear-gradient(135deg, #ff6900, #ff8c00); top: 0px; right: 0px; bottom: auto; font-size: 0.7rem; padding: 2px 6px; font-weight: bold; white-space: nowrap;">CUPÓN</span>' +
                    '<span class="inline-block w-full py-3 px-2 bg-blue-500 text-white text-base font-semibold rounded hover:bg-blue-600" data-cupon-chollo-tienda-solo="true" data-descuentos="' + primeraOferta.descuentos.replace(/"/g, '&quot;') + '" data-oferta-id="' + primeraOferta.id + '" data-url="' + primeraOferta.url + '">Ir a la tienda</span>';
                } else if (primeraOferta.descuentos && typeof primeraOferta.descuentos === 'string' && primeraOferta.descuentos.startsWith('CholloTienda;')) {
                  botonHtml = '<span class="cupon-badge" style="background: linear-gradient(135deg, #ff6900, #ff8c00); top: 0px; right: 0px; bottom: auto; font-size: 0.7rem; padding: 2px 6px; font-weight: bold; white-space: nowrap;">CUPÓN</span>' +
                    '<span class="inline-block w-full py-3 px-2 bg-blue-500 text-white text-base font-semibold rounded hover:bg-blue-600" data-cupon-chollo-tienda="true" data-descuentos="' + primeraOferta.descuentos.replace(/"/g, '&quot;') + '" data-oferta-id="' + primeraOferta.id + '" data-url="' + primeraOferta.url + '">Ir a la tienda</span>';
                } else if (primeraOferta.descuentos && typeof primeraOferta.descuentos === 'string' && primeraOferta.descuentos.startsWith('cupon;')) {
                  try {
                    const cuponInfo = _pc1(primeraOferta.descuentos);
                    const valorCupon = cuponInfo ? cuponInfo.valor : (primeraOferta.descuentos.split(';')[1] || '');
                    const codigoCupon = cuponInfo ? cuponInfo.codigo : null;
                    const codigoCuponEscapado = codigoCupon ? codigoCupon.replace(/"/g, '&quot;').replace(/'/g, '&#39;') : '';
                    const valorCuponEscapado = String(valorCupon).replace(/"/g, '&quot;').replace(/'/g, '&#39;');
                    const dataAttrs = codigoCupon 
                      ? `data-cupon="true" data-codigo-cupon="${codigoCuponEscapado}" data-valor-cupon="${valorCuponEscapado}"` 
                      : `data-cupon="true" data-valor-cupon="${valorCuponEscapado}"`;
                    botonHtml = '<span class="cupon-badge" style="background: linear-gradient(135deg, #ff6900, #ff8c00); top: 0px; right: 0px; bottom: auto; font-size: 0.7rem; padding: 2px 6px; font-weight: bold;">CUPÓN</span>' +
                               '<span class="inline-block w-full py-3 px-2 bg-blue-500 text-white text-base font-semibold rounded hover:bg-blue-600" ' + dataAttrs + ' target="_blank">Ir a la tienda</span>';
                  } catch (e) {
                    botonHtml = '<span class="inline-block w-full py-3 px-2 bg-blue-500 text-white text-base font-semibold rounded hover:bg-blue-600">Ir a la tienda</span>';
                  }
                } else if (primeraOferta.descuentos === 'cupon') {
                  botonHtml = '<span class="cupon-badge" style="background: linear-gradient(135deg, #ff6900, #ff8c00); top: 0px; right: 0px; bottom: auto; font-size: 0.7rem; padding: 2px 6px; font-weight: bold; white-space: nowrap;">CUPÓN</span>' +
                    '<span class="inline-block w-full py-3 px-2 bg-blue-500 text-white text-base font-semibold rounded hover:bg-blue-600" data-cupon="true" target="_blank">Ir a la tienda</span>';
                } else if (primeraOferta.descuentos === '3x2') {
                  botonHtml = '<span class="cupon-badge" style="background: linear-gradient(135deg, #8b5cf6, #a855f7); top: 0px; right: 0px; bottom: auto; font-size: 0.7rem; padding: 2px 6px; font-weight: normal; white-space: nowrap;">3x2</span>' +
                    '<span class="inline-block w-full py-3 px-2 bg-blue-500 text-white text-base font-semibold rounded hover:bg-blue-600" data-3x2="true" target="_blank">Ir a la tienda</span>';
                } else if (primeraOferta.descuentos === '2x1 - SoloCarrefour') {
                  botonHtml = '<span class="cupon-badge" style="background: linear-gradient(135deg, #10b981, #059669); top: 0px; right: 0px; bottom: auto; font-size: 0.7rem; padding: 2px 6px; font-weight: normal; white-space: nowrap;">2x1</span>' +
                    '<span class="inline-block w-full py-3 px-2 bg-blue-500 text-white text-base font-semibold rounded hover:bg-blue-600" data-2x1="true" target="_blank">Ir a la tienda</span>';
                } else if (primeraOferta.descuentos === '2a al 50 - cheque - SoloCarrefour') {
                  botonHtml = '<span class="cupon-badge" style="background: linear-gradient(135deg, #f59e0b, #d97706); top: 0px; right: 0px; bottom: auto; font-size: 0.7rem; padding: 2px 6px; font-weight: bold; white-space: nowrap;">2a al 50%</span>' +
                    '<span class="inline-block w-full py-3 px-2 bg-blue-500 text-white text-base font-semibold rounded hover:bg-blue-600" data-2a-al-50-cheque="true" target="_blank">Ir a la tienda</span>';
                } else if (primeraOferta.descuentos === '2a al 70') {
                  botonHtml = '<span class="cupon-badge" style="background: linear-gradient(135deg, #f59e0b, #d97706); top: 0px; right: 0px; bottom: auto; font-size: 0.7rem; padding: 2px 6px; font-weight: normal; white-space: nowrap;">2a AL 70%</span>' +
                    '<span class="inline-block w-full py-3 px-2 bg-blue-500 text-white text-base font-semibold rounded hover:bg-blue-600" data-2a-al-70="true" target="_blank">Ir a la tienda</span>';
                } else if (primeraOferta.descuentos === '2a al 50') {
                  botonHtml = '<span class="cupon-badge" style="background: linear-gradient(135deg, #f59e0b, #d97706); top: 0px; right: 0px; bottom: auto; font-size: 0.7rem; padding: 2px 6px; font-weight: normal; white-space: nowrap;">2a al 50%</span>' +
                    '<span class="inline-block w-full py-3 px-2 bg-blue-500 text-white text-base font-semibold rounded hover:bg-blue-600" data-2a-al-50="true" target="_blank">Ir a la tienda</span>';
                } else {
                  botonHtml = '<span class="inline-block w-full py-3 px-2 bg-blue-500 text-white text-base font-semibold rounded hover:bg-blue-600">Ir a la tienda</span>';
                }
                
                html += `
                  <a href="${primeraOferta.url}" target="_blank" rel="sponsored noopener noreferrer" class="product-card block bg-white rounded-lg shadow py-2 px-4 sm:px-6 grid ${gridColsMovil} ${gridCols} gap-2 sm:gap-4 sm:items-center card-hover hover:shadow-md">
                    <div class="logo flex items-center justify-center divider min-w-0 overflow-hidden ${ordenLogo}">
                      <img src="${primeraOferta.logo}" loading="lazy" alt="${primeraOferta.nombre}" class="w-full max-w-[130px] sm:h-[45px] h-[36px] object-contain">
                    </div>
                    <div class="envio text-gray-700 divider text-center min-w-0 overflow-hidden ${ordenEnvio}">
                      <p class="text-sm text-gray-500 font-bold"><img src='{{ asset('images/van.png') }}' loading="lazy" alt='Van' class='icon-small'> ${primeraOferta.envio_gratis}</p>
                      <p class="text-sm text-gray-500"><img src='{{ asset('images/van.png') }}' loading="lazy" alt='Van' class='icon-small'> ${primeraOferta.envio_normal}</p>
                    </div>
                    ${mostrarCantidad ? `
                    <div class="und text-gray-700 divider text-center min-w-0 overflow-hidden">
                      <div class="font-semibold">Cantidad</div>
                      <div class="text-sm text-gray-500 leading-tight">${_fc1(primeraOferta.unidades, unidadMedida)}</div>
                    </div>
                    ` : ''}
                    ${columnasDinamicas}
                    ${!mostrarPrecioTotal && !mostrarPrecioUnidad ? `
                    <div class="precio-total text-gray-700 divider text-center min-w-0 overflow-hidden ${ordenPrecio}">
                      <div class="text-sm text-gray-500">Precio total</div>
                      <div class="text-3xl font-extrabold text-pink-400">${primeraOferta.precio_total} <span class="text-sm text-gray-500 font-normal">€</span></div>
                    </div>
                    ` : ''}
                    ${mostrarPrecioTotal ? `
                    <div class="precio-total text-gray-700 divider text-center min-w-0 overflow-hidden ${ordenPrecio} ${esUnidadUnicaSinColumnas ? 'precio-unidad-unica-sin-columnas' : ''}">
                      <div class="${precioTotalLabelClass}">Precio total</div>
                      <div class="${precioTotalClass}">${primeraOferta.precio_total} <span class="text-sm text-gray-500 font-normal">€</span></div>
                    </div>
                    ` : ''}
                    ${mostrarPrecioUnidad ? `
                    <div class="precio-und text-center text-gray-700 divider min-w-0 overflow-hidden ${ordenPrecio}">
                      <div class="${precioUnidadLabelClass}">${_glp1(unidadMedida)}</div>
                      <div class="${precioUnidadClass}">
                        ${primeraOferta.precio_unidad}
                        <span class="text-sm text-gray-500 font-normal">${_gsp1(unidadMedida)}</span>
                      </div>
                    </div>
                    ` : ''}
                    <div class="boton text-center min-w-0 relative overflow-visible ${ordenBoton}">
                      ${botonHtml}
                    </div>
                  </a>
                `;
                
                if (esMejorOferta) {
                  html += '</div></div></div>';
                }
                
                // Registrar que este grupo fue renderizado
                ofertasRenderizadas.push({
                  tipo: 'grupo_unificado',
                  grupoId: grupo.grupoId,
                  ofertas_ids: grupo.ofertas.map(o => o.id),
                  precioUnidad: grupo.precioUnidad,
                  cantidad_ofertas: grupo.ofertas.length
                });
                
                itemIndex++;
              }
              // Si es una oferta individual
              else if (itemOrdenado.tipo === 'oferta') {
                const item = itemOrdenado.datos;
                
                const estaEnGrupo = ofertasEnGrupos.includes(item.id);
                const esMejorOferta = mejoresOfertas.some(mejor => mejor.id === item.id);
                
                // Si está en un grupo unificado, saltarla (ya fue procesada como grupo)
                if (estaEnGrupo) {
                  continue;
                }
                
                // Si es una mejor oferta, buscar si hay más consecutivas dentro de itemsUnificados
                if (esMejorOferta) {
                  const grupoMejoresOfertas = [];
                  let j = idxUnificado;
                
                  // Agrupar todas las mejores ofertas consecutivas (solo ofertas individuales, no grupos)
                  while (j < itemsUnificados.length && 
                         itemsUnificados[j].tipo === 'oferta' &&
                         !ofertasEnGrupos.includes(itemsUnificados[j].datos.id) &&
                         mejoresOfertas.some(mejor => mejor.id === itemsUnificados[j].datos.id)) {
                    grupoMejoresOfertas.push(itemsUnificados[j].datos);
                    j++;
                  }
                  
                  // Solo renderizar si hay ofertas en el grupo
                  if (grupoMejoresOfertas.length > 0) {
                    // Crear contenedor para el grupo de mejores ofertas
                    const tieneDescuentosEnGrupo = grupoMejoresOfertas.some(item => 
                      item.descuentos === 'cupon' || 
                      item.descuentos === '3x2' ||
                      (item.descuentos && typeof item.descuentos === 'string' && (
                        item.descuentos.startsWith('cupon;') ||
                        item.descuentos.startsWith('SoloAliexpress;') ||
                        item.descuentos.startsWith('CholloTienda;') ||
                        item.descuentos.startsWith('CholloTienda1SoloCuponQueAplicaDescuento;')
                      ))
                    );
                    html += `<div class="mejor-oferta-wrapper" ${tieneDescuentosEnGrupo ? 'style="padding-top: 8px;"' : 'style="padding-top: 8px;"'}>`;
                  
                    html += '<div class="mejor-oferta-badge-grupo">🏆 Mejor precio</div>';
                  
                    html += '<div class="mejor-oferta-gaming-container">';
                    html += '<div class="ofertas-grupo">';
                  
                    grupoMejoresOfertas.forEach(itemGrupo => {
                  // Intercambiar estilos según filtro activo
                  let precioTotalClass = 'text-sm text-gray-500';
                  let precioTotalLabelClass = 'font-semibold';
                  let precioUnidadClass = 'text-3xl font-extrabold text-pink-400';
                  let precioUnidadLabelClass = 'text-sm text-gray-500';
                  if (ordenActual === 'precio_total') {
                    precioTotalClass = 'text-3xl font-extrabold text-pink-400';
                    precioTotalLabelClass = 'text-sm text-gray-500';
                    precioUnidadClass = 'text-sm text-gray-500';
                    precioUnidadLabelClass = 'font-semibold';
                  }
                  
                  // Determinar estructura de grid según unidadUnica
                  let gridCols = 'sm:grid-cols-[100px_1fr_1fr_1fr_1fr_auto]';
                  let gridColsMovil = 'grid-cols-1';
                  let columnasDinamicas = '';
                  let mostrarCantidad = true;
                  let mostrarPrecioTotal = true;
                  let mostrarPrecioUnidad = true;
                  let esUnidadUnicaConColumnas = false;
                  let esUnidadUnicaSinColumnas = false;
                  
                  // Caso 1: Unidad única CON columnas marcadas
                  if (esUnidadUnica && columnasData && columnasData.length > 0) {
                    mostrarCantidad = false;
                    mostrarPrecioTotal = false;
                    mostrarPrecioUnidad = false;
                    columnasDinamicas = _rcuu1(itemGrupo);
                    esUnidadUnicaConColumnas = true;
                    
                    // Para móvil/tablet: grid dinámico según número de columnas
                    // Primera fila: logo 50% + precio 50% (2 columnas)
                    // Segunda fila: envío + columnas dinámicas
                    const numColumnas = columnasData.length;
                    if (numColumnas === 1) {
                      // 1 columna: envío 50% + columna 50% (2 columnas en segunda fila)
                      gridColsMovil = 'grid-cols-2';
                    } else {
                      // 2, 3 o 4 columnas: envío 33% + cada columna 33% (3 columnas en segunda fila)
                      gridColsMovil = 'grid-cols-3';
                    }
                    
                    // Calcular grid según número de columnas dinámicas para desktop
                    if (numColumnas === 1) {
                      gridCols = 'sm:grid-cols-[100px_1fr_1fr_1fr_1fr_auto]';
                    } else if (numColumnas === 2) {
                      gridCols = 'sm:grid-cols-[100px_1fr_1fr_1fr_1fr_auto]';
                    } else if (numColumnas === 3 || numColumnas === 4) {
                      gridCols = 'sm:grid-cols-[100px_1fr_1fr_1fr_1fr_auto]';
                    }
                  }
                  // Caso 2: Unidad única SIN columnas marcadas
                  else if (esUnidadUnica && (!columnasData || columnasData.length === 0)) {
                    mostrarCantidad = false;
                    mostrarPrecioTotal = true; // Mostrar precio total
                    mostrarPrecioUnidad = false;
                    esUnidadUnicaSinColumnas = true;
                    
                    // El label "Precio total" no debe estar en negrita para unidad única sin columnas
                    precioTotalLabelClass = 'text-sm text-gray-500';
                    
                    // PC: Logo, Envío, Precio total, Botón (4 columnas)
                    gridCols = 'sm:grid-cols-[100px_1fr_1fr_auto]';
                    
                    // Móvil: Primera fila (Logo, Envío, Precio total), Segunda fila (Botón)
                    gridColsMovil = 'grid-cols-3';
                  }
                  
                  // En móvil (unidad única CON columnas): Primera fila (Logo order-1, Precio order-2), Segunda fila (Envío order-3, Columnas order-4+)
                  // En móvil (unidad única SIN columnas): Primera fila (Logo order-1, Envío order-2, Precio order-3), Segunda fila (Botón order-4)
                  // En desktop: sin order (orden natural)
                  const ordenLogo = esUnidadUnicaConColumnas ? 'order-1 sm:!order-[0]' : (esUnidadUnicaSinColumnas ? 'order-1 sm:!order-[0]' : '');
                  const ordenEnvio = esUnidadUnicaConColumnas ? 'order-3 sm:!order-[0]' : (esUnidadUnicaSinColumnas ? 'order-2 sm:!order-[0]' : '');
                  const ordenPrecio = esUnidadUnicaConColumnas ? 'order-2 sm:!order-[0]' : (esUnidadUnicaSinColumnas ? 'order-3 sm:!order-[0]' : '');
                  
                  // Botón: después de las columnas dinámicas en móvil (con columnas) o en segunda fila (sin columnas)
                  const numColumnas = esUnidadUnicaConColumnas ? columnasData.length : 0;
                  let colSpanBoton = '';
                  let ordenBoton = '';
                  
                  if (esUnidadUnicaConColumnas) {
                    // Botón: col-span según número de columnas del grid móvil
                    colSpanBoton = numColumnas === 1 ? 'col-span-2' : 'col-span-3';
                    ordenBoton = numColumnas === 1 ? `order-5 sm:!order-[0] ${colSpanBoton} sm:!col-span-1` : `order-6 sm:!order-[0] ${colSpanBoton} sm:!col-span-1`;
                  } else if (esUnidadUnicaSinColumnas) {
                    // Botón: ocupa toda la segunda fila en móvil
                    colSpanBoton = 'col-span-3';
                    ordenBoton = `order-4 sm:!order-[0] ${colSpanBoton} sm:!col-span-1`;
                  }
                  
                  html += `
                    <a href="${itemGrupo.url}" target="_blank" rel="sponsored noopener noreferrer" class="product-card block bg-white rounded-lg shadow py-2 px-4 sm:px-6 grid ${gridColsMovil} ${gridCols} gap-2 sm:gap-4 sm:items-center card-hover hover:shadow-md">
                      <div class="logo flex items-center justify-center divider min-w-0 overflow-hidden ${ordenLogo}">
                        <img src="${itemGrupo.logo}" loading="lazy" alt="${itemGrupo.nombre}" class="w-full max-w-[130px] sm:h-[45px] h-[36px] object-contain">
                      </div>
                      <div class="envio text-gray-700 divider text-center min-w-0 overflow-hidden ${ordenEnvio}">
                        <p class="text-sm text-gray-500 font-bold"><img src='{{ asset('images/van.png') }}' loading="lazy" alt='Van' class='icon-small'> ${itemGrupo.envio_gratis}</p>
                        <p class="text-sm text-gray-500"><img src='{{ asset('images/van.png') }}' loading="lazy" alt='Van' class='icon-small'> ${itemGrupo.envio_normal}</p>
                      </div>
                      ${mostrarCantidad ? `
                      <div class="und text-gray-700 divider text-center min-w-0 overflow-hidden">
                        <div class="font-semibold">Cantidad</div>
                        <div class="text-sm text-gray-500 leading-tight">${_fc1(itemGrupo.unidades, unidadMedida)}</div>
                      </div>
                      ` : ''}
                      ${columnasDinamicas}
                      ${!mostrarPrecioTotal && !mostrarPrecioUnidad ? `
                      <div class="precio-total text-gray-700 divider text-center min-w-0 overflow-hidden ${ordenPrecio}">
                        <div class="text-sm text-gray-500">Precio total</div>
                        <div class="text-3xl font-extrabold text-pink-400">${itemGrupo.precio_total} <span class="text-sm text-gray-500 font-normal">€</span></div>
                      </div>
                      ` : ''}
                      ${mostrarPrecioTotal ? `
                      <div class="precio-total text-gray-700 divider text-center min-w-0 overflow-hidden ${ordenPrecio} ${esUnidadUnicaSinColumnas ? 'precio-unidad-unica-sin-columnas' : ''}">
                        <div class="${precioTotalLabelClass}">Precio total</div>
                        <div class="${precioTotalClass}">${itemGrupo.precio_total} <span class="text-sm text-gray-500 font-normal">€</span></div>
                      </div>
                      ` : ''}
                      ${mostrarPrecioUnidad ? `
                      <div class="precio-und text-center text-gray-700 divider min-w-0 overflow-hidden ${ordenPrecio}">
                        <div class="${precioUnidadLabelClass}">${_glp1(unidadMedida)}</div>
                        <div class="${precioUnidadClass}">
                          ${itemGrupo.precio_unidad}
                          <span class="text-sm text-gray-500 font-normal">${_gsp1(unidadMedida)}</span>
                        </div>
                      </div>
                      ` : ''}
                      <div class="boton text-center min-w-0 relative overflow-visible ${ordenBoton}">
                  ${itemGrupo.descuentos && typeof itemGrupo.descuentos === 'string' && itemGrupo.descuentos.startsWith('SoloAliexpress;') ? 
                    '<span class="cupon-badge" style="background: linear-gradient(135deg, #ff6900, #ff8c00); top: 0px; right: 0px; bottom: auto; font-size: 0.7rem; padding: 2px 6px; font-weight: bold; white-space: nowrap;">CUPÓN</span>' +
                    '<span class="inline-block w-full py-3 px-2 bg-blue-500 text-white text-base font-semibold rounded hover:bg-blue-600" data-cupon-aliexpress="true" data-descuentos="' + itemGrupo.descuentos.replace(/"/g, '&quot;') + '" data-oferta-id="' + itemGrupo.id + '" data-url="' + itemGrupo.url + '">Ir a la tienda</span>' :
                    (itemGrupo.descuentos && typeof itemGrupo.descuentos === 'string' && itemGrupo.descuentos.startsWith('CholloTienda1SoloCuponQueAplicaDescuento;')) ?
                    '<span class="cupon-badge" style="background: linear-gradient(135deg, #ff6900, #ff8c00); top: 0px; right: 0px; bottom: auto; font-size: 0.7rem; padding: 2px 6px; font-weight: bold; white-space: nowrap;">CUPÓN</span>' +
                    '<span class="inline-block w-full py-3 px-2 bg-blue-500 text-white text-base font-semibold rounded hover:bg-blue-600" data-cupon-chollo-tienda-solo="true" data-descuentos="' + itemGrupo.descuentos.replace(/"/g, '&quot;') + '" data-oferta-id="' + itemGrupo.id + '" data-url="' + itemGrupo.url + '">Ir a la tienda</span>' :
                    itemGrupo.descuentos && typeof itemGrupo.descuentos === 'string' && itemGrupo.descuentos.startsWith('CholloTienda;') ?
                    '<span class="cupon-badge" style="background: linear-gradient(135deg, #ff6900, #ff8c00); top: 0px; right: 0px; bottom: auto; font-size: 0.7rem; padding: 2px 6px; font-weight: bold; white-space: nowrap;">CUPÓN</span>' +
                    '<span class="inline-block w-full py-3 px-2 bg-blue-500 text-white text-base font-semibold rounded hover:bg-blue-600" data-cupon-chollo-tienda="true" data-descuentos="' + itemGrupo.descuentos.replace(/"/g, '&quot;') + '" data-oferta-id="' + itemGrupo.id + '" data-url="' + itemGrupo.url + '">Ir a la tienda</span>' :
                    itemGrupo.descuentos && typeof itemGrupo.descuentos === 'string' && itemGrupo.descuentos.startsWith('cupon;') ? 
                    (() => {
                      try {
                        const cuponInfo = _pc1(itemGrupo.descuentos);
                        const valorCupon = cuponInfo ? cuponInfo.valor : (itemGrupo.descuentos.split(';')[1] || '');
                        const codigoCupon = cuponInfo ? cuponInfo.codigo : null;
                        // Escapar valores para evitar problemas con comillas y caracteres especiales
                        const codigoCuponEscapado = codigoCupon ? codigoCupon.replace(/"/g, '&quot;').replace(/'/g, '&#39;') : '';
                        const valorCuponEscapado = String(valorCupon).replace(/"/g, '&quot;').replace(/'/g, '&#39;');
                        const dataAttrs = codigoCupon 
                          ? `data-cupon="true" data-codigo-cupon="${codigoCuponEscapado}" data-valor-cupon="${valorCuponEscapado}"` 
                          : `data-cupon="true" data-valor-cupon="${valorCuponEscapado}"`;
                        // Si es mejor oferta (primera del grupo), badge dentro (top: 0px), sino medio fuera (sin especificar top)
                        const badgeTop = 'top: 0px;'; // Para mejor oferta, dentro del botón
                        return '<span class="cupon-badge" style="background: linear-gradient(135deg, #ff6900, #ff8c00); ' + badgeTop + ' right: 0px; bottom: auto; font-size: 0.7rem; padding: 2px 6px; font-weight: bold;">CUPÓN</span>' +
                               '<span class="inline-block w-full py-3 px-2 bg-blue-500 text-white text-base font-semibold rounded hover:bg-blue-600" ' + dataAttrs + ' target="_blank">Ir a la tienda</span>';
                      } catch (e) {
                        return '<span class="inline-block w-full py-3 px-2 bg-blue-500 text-white text-base font-semibold rounded hover:bg-blue-600">Ir a la tienda</span>';
                      }
                    })() : 
                    itemGrupo.descuentos === 'cupon' ? 
                    '<span class="cupon-badge" style="background: linear-gradient(135deg, #ff6900, #ff8c00); top: 0px; right: 0px; bottom: auto; font-size: 0.7rem; padding: 2px 6px; font-weight: bold; white-space: nowrap;">CUPÓN</span>' +
                    '<span class="inline-block w-full py-3 px-2 bg-blue-500 text-white text-base font-semibold rounded hover:bg-blue-600" data-cupon="true" target="_blank">Ir a la tienda</span>' : 
                    itemGrupo.descuentos === '3x2' ?
                    '<span class="cupon-badge" style="background: linear-gradient(135deg, #8b5cf6, #a855f7); top: 0px; right: 0px; bottom: auto; font-size: 0.7rem; padding: 2px 6px; font-weight: normal; white-space: nowrap;">3x2</span>' +
                    '<span class="inline-block w-full py-3 px-2 bg-blue-500 text-white text-base font-semibold rounded hover:bg-blue-600" data-3x2="true" target="_blank">Ir a la tienda</span>' :
                    itemGrupo.descuentos === '2x1 - SoloCarrefour' ?
                    '<span class="cupon-badge" style="background: linear-gradient(135deg, #10b981, #059669); top: 0px; right: 0px; bottom: auto; font-size: 0.7rem; padding: 2px 6px; font-weight: normal; white-space: nowrap;">2x1</span>' +
                    '<span class="inline-block w-full py-3 px-2 bg-blue-500 text-white text-base font-semibold rounded hover:bg-blue-600" data-2x1="true" target="_blank">Ir a la tienda</span>' :
                    itemGrupo.descuentos === '2a al 50 - cheque - SoloCarrefour' ?
                    '<span class="cupon-badge" style="background: linear-gradient(135deg, #f59e0b, #d97706); top: 0px; right: 0px; bottom: auto; font-size: 0.7rem; padding: 2px 6px; font-weight: bold; white-space: nowrap;">2a al 50%</span>' +
                    '<span class="inline-block w-full py-3 px-2 bg-blue-500 text-white text-base font-semibold rounded hover:bg-blue-600" data-2a-al-50-cheque="true" target="_blank">Ir a la tienda</span>' :
                    itemGrupo.descuentos === '2a al 70' ?
                    '<span class="cupon-badge" style="background: linear-gradient(135deg, #f59e0b, #d97706); top: 0px; right: 0px; bottom: auto; font-size: 0.7rem; padding: 2px 6px; font-weight: normal; white-space: nowrap;">2a AL 70%</span>' +
                    '<span class="inline-block w-full py-3 px-2 bg-blue-500 text-white text-base font-semibold rounded hover:bg-blue-600" data-2a-al-70="true" target="_blank">Ir a la tienda</span>' :
                    itemGrupo.descuentos === '2a al 50' ?
                    '<span class="cupon-badge" style="background: linear-gradient(135deg, #f59e0b, #d97706); top: 0px; right: 0px; bottom: auto; font-size: 0.7rem; padding: 2px 6px; font-weight: normal; white-space: nowrap;">2a al 50%</span>' +
                    '<span class="inline-block w-full py-3 px-2 bg-blue-500 text-white text-base font-semibold rounded hover:bg-blue-600" data-2a-al-50="true" target="_blank">Ir a la tienda</span>' :
                    '<span class="inline-block w-full py-3 px-2 bg-blue-500 text-white text-base font-semibold rounded hover:bg-blue-600">Ir a la tienda</span>'
                  }
                      </div>
                    </a>
                  `;
                    });
                  
                    html += '</div></div></div>';
                  
                    // Avanzar el índice de ofertas procesadas
                    itemIndex += grupoMejoresOfertas.length;
                  
                    // IMPORTANTE: Saltar todas las ofertas que ya fueron procesadas en el grupo
                    // j apunta a la primera oferta DESPUÉS del grupo
                    idxUnificado = j - 1; // -1 porque el for incrementará al final
                    continue;
                  }
                }
                
                // Si no es mejor oferta, renderizar como oferta individual normal
                if (!esMejorOferta) {
                  // Renderizar oferta individual normal (sin borde de mejor oferta)
                  // Usar el mismo código que se usaba en el forEach eliminado
                  let precioTotalClass = 'text-sm text-gray-500';
                let precioTotalLabelClass = 'font-semibold';
                let precioUnidadClass = 'text-3xl font-extrabold text-pink-400';
                let precioUnidadLabelClass = 'text-sm text-gray-500';
                if (ordenActual === 'precio_total') {
                  precioTotalClass = 'text-3xl font-extrabold text-pink-400';
                  precioTotalLabelClass = 'text-sm text-gray-500';
                  precioUnidadClass = 'text-sm text-gray-500';
                  precioUnidadLabelClass = 'font-semibold';
                }
                
                let gridCols = 'sm:grid-cols-[100px_1fr_1fr_1fr_1fr_auto]';
                let gridColsMovil = 'grid-cols-1';
                let columnasDinamicas = '';
                let mostrarCantidad = true;
                let mostrarPrecioTotal = true;
                let mostrarPrecioUnidad = true;
                let esUnidadUnicaConColumnas = false;
                let esUnidadUnicaSinColumnas = false;
                
                if (esUnidadUnica && columnasData && columnasData.length > 0) {
                  mostrarCantidad = false;
                  mostrarPrecioTotal = false;
                  mostrarPrecioUnidad = false;
                  columnasDinamicas = _rcuu1(item);
                  esUnidadUnicaConColumnas = true;
                  
                  const numColumnas = columnasData.length;
                  if (numColumnas === 1) {
                    gridColsMovil = 'grid-cols-2';
                  } else {
                    gridColsMovil = 'grid-cols-3';
                  }
                  
                  if (numColumnas === 1) {
                    gridCols = 'sm:grid-cols-[100px_1fr_1fr_1fr_1fr_auto]';
                  } else if (numColumnas === 2) {
                    gridCols = 'sm:grid-cols-[100px_1fr_1fr_1fr_1fr_auto]';
                  } else if (numColumnas === 3 || numColumnas === 4) {
                    gridCols = 'sm:grid-cols-[100px_1fr_1fr_1fr_1fr_auto]';
                  }
                } else if (esUnidadUnica && (!columnasData || columnasData.length === 0)) {
                  mostrarCantidad = false;
                  mostrarPrecioTotal = true;
                  mostrarPrecioUnidad = false;
                  esUnidadUnicaSinColumnas = true;
                  precioTotalLabelClass = 'text-sm text-gray-500';
                  gridCols = 'sm:grid-cols-[100px_1fr_1fr_auto]';
                  gridColsMovil = 'grid-cols-3';
                }
                
                const ordenLogo = esUnidadUnicaConColumnas ? 'order-1 sm:!order-[0]' : (esUnidadUnicaSinColumnas ? 'order-1 sm:!order-[0]' : '');
                const ordenEnvio = esUnidadUnicaConColumnas ? 'order-3 sm:!order-[0]' : (esUnidadUnicaSinColumnas ? 'order-2 sm:!order-[0]' : '');
                const ordenPrecio = esUnidadUnicaConColumnas ? 'order-2 sm:!order-[0]' : (esUnidadUnicaSinColumnas ? 'order-3 sm:!order-[0]' : '');
                
                const numColumnas = esUnidadUnicaConColumnas ? columnasData.length : 0;
                let colSpanBoton = '';
                let ordenBoton = '';
                
                if (esUnidadUnicaConColumnas) {
                  colSpanBoton = numColumnas === 1 ? 'col-span-2' : 'col-span-3';
                  ordenBoton = numColumnas === 1 ? `order-5 sm:!order-[0] ${colSpanBoton} sm:!col-span-1` : `order-6 sm:!order-[0] ${colSpanBoton} sm:!col-span-1`;
                } else if (esUnidadUnicaSinColumnas) {
                  colSpanBoton = 'col-span-3';
                  ordenBoton = `order-4 sm:!order-[0] ${colSpanBoton} sm:!col-span-1`;
                }
                
                // Generar botón
                let botonHtml = '';
                if (item.descuentos && typeof item.descuentos === 'string' && item.descuentos.startsWith('SoloAliexpress;')) {
                  botonHtml = '<span class="cupon-badge" style="background: linear-gradient(135deg, #ff6900, #ff8c00); top: 0px; right: 0px; bottom: auto; font-size: 0.7rem; padding: 2px 6px; font-weight: bold; white-space: nowrap;">CUPÓN</span>' +
                    '<span class="inline-block w-full py-3 px-2 bg-blue-500 text-white text-base font-semibold rounded hover:bg-blue-600" data-cupon-aliexpress="true" data-descuentos="' + item.descuentos.replace(/"/g, '&quot;') + '" data-oferta-id="' + item.id + '" data-url="' + item.url + '">Ir a la tienda</span>';
                } else if (item.descuentos && typeof item.descuentos === 'string' && item.descuentos.startsWith('CholloTienda1SoloCuponQueAplicaDescuento;')) {
                  botonHtml = '<span class="cupon-badge" style="background: linear-gradient(135deg, #ff6900, #ff8c00); top: 0px; right: 0px; bottom: auto; font-size: 0.7rem; padding: 2px 6px; font-weight: bold; white-space: nowrap;">CUPÓN</span>' +
                    '<span class="inline-block w-full py-3 px-2 bg-blue-500 text-white text-base font-semibold rounded hover:bg-blue-600" data-cupon-chollo-tienda-solo="true" data-descuentos="' + item.descuentos.replace(/"/g, '&quot;') + '" data-oferta-id="' + item.id + '" data-url="' + item.url + '">Ir a la tienda</span>';
                } else if (item.descuentos && typeof item.descuentos === 'string' && item.descuentos.startsWith('CholloTienda;')) {
                  botonHtml = '<span class="cupon-badge" style="background: linear-gradient(135deg, #ff6900, #ff8c00); top: 0px; right: 0px; bottom: auto; font-size: 0.7rem; padding: 2px 6px; font-weight: bold; white-space: nowrap;">CUPÓN</span>' +
                    '<span class="inline-block w-full py-3 px-2 bg-blue-500 text-white text-base font-semibold rounded hover:bg-blue-600" data-cupon-chollo-tienda="true" data-descuentos="' + item.descuentos.replace(/"/g, '&quot;') + '" data-oferta-id="' + item.id + '" data-url="' + item.url + '">Ir a la tienda</span>';
                } else if (item.descuentos && typeof item.descuentos === 'string' && item.descuentos.startsWith('cupon;')) {
                  try {
                    const cuponInfo = _pc1(item.descuentos);
                    const valorCupon = cuponInfo ? cuponInfo.valor : (item.descuentos.split(';')[1] || '');
                    const codigoCupon = cuponInfo ? cuponInfo.codigo : null;
                    const codigoCuponEscapado = codigoCupon ? codigoCupon.replace(/"/g, '&quot;').replace(/'/g, '&#39;') : '';
                    const valorCuponEscapado = String(valorCupon).replace(/"/g, '&quot;').replace(/'/g, '&#39;');
                    const urlEscapada = item.url.replace(/"/g, '&quot;').replace(/'/g, '&#39;');
                    const dataAttrs = codigoCupon 
                      ? `data-cupon="true" data-codigo-cupon="${codigoCuponEscapado}" data-valor-cupon="${valorCuponEscapado}" data-url="${urlEscapada}"` 
                      : `data-cupon="true" data-valor-cupon="${valorCuponEscapado}" data-url="${urlEscapada}"`;
                    botonHtml = '<span class="cupon-badge" style="background: linear-gradient(135deg, #ff6900, #ff8c00); top: 0px; right: 0px; bottom: auto; font-size: 0.7rem; padding: 2px 6px; font-weight: bold; white-space: nowrap;">CUPÓN</span>' +
                               '<span class="inline-block w-full py-3 px-2 bg-blue-500 text-white text-base font-semibold rounded hover:bg-blue-600" ' + dataAttrs + '>Ir a la tienda</span>';
                  } catch (e) {
                    botonHtml = '<span class="cupon-badge" style="background: linear-gradient(135deg, #ff6900, #ff8c00); top: 0px; right: 0px; bottom: auto; font-size: 0.7rem; padding: 2px 6px; font-weight: bold; white-space: nowrap;">CUPÓN</span>' +
                               '<span class="inline-block w-full py-3 px-2 bg-blue-500 text-white text-base font-semibold rounded hover:bg-blue-600" data-cupon="true" data-url="' + item.url.replace(/"/g, '&quot;').replace(/'/g, '&#39;') + '">Ir a la tienda</span>';
                  }
                } else if (item.descuentos === 'cupon') {
                  botonHtml = '<span class="cupon-badge" style="background: linear-gradient(135deg, #ff6900, #ff8c00); top: 0px; right: 0px; bottom: auto; font-size: 0.7rem; padding: 2px 6px; font-weight: bold; white-space: nowrap;">CUPÓN</span>' +
                    '<span class="inline-block w-full py-3 px-2 bg-blue-500 text-white text-base font-semibold rounded hover:bg-blue-600" data-cupon="true" data-url="' + item.url.replace(/"/g, '&quot;').replace(/'/g, '&#39;') + '">Ir a la tienda</span>';
                } else if (item.descuentos === '3x2') {
                  botonHtml = '<span class="cupon-badge" style="background: linear-gradient(135deg, #8b5cf6, #a855f7); top: 0px; right: 0px; bottom: auto; font-size: 0.7rem; padding: 2px 6px; font-weight: normal; white-space: nowrap;">3x2</span>' +
                    '<span class="inline-block w-full py-3 px-2 bg-blue-500 text-white text-base font-semibold rounded hover:bg-blue-600" data-3x2="true" data-url="' + item.url.replace(/"/g, '&quot;').replace(/'/g, '&#39;') + '">Ir a la tienda</span>';
                } else if (item.descuentos === '2x1 - SoloCarrefour') {
                  botonHtml = '<span class="cupon-badge" style="background: linear-gradient(135deg, #10b981, #059669); top: 0px; right: 0px; bottom: auto; font-size: 0.7rem; padding: 2px 6px; font-weight: normal; white-space: nowrap;">2x1</span>' +
                    '<span class="inline-block w-full py-3 px-2 bg-blue-500 text-white text-base font-semibold rounded hover:bg-blue-600" data-2x1="true" data-url="' + item.url.replace(/"/g, '&quot;').replace(/'/g, '&#39;') + '">Ir a la tienda</span>';
                } else if (item.descuentos === '2a al 50 - cheque - SoloCarrefour') {
                  botonHtml = '<span class="cupon-badge" style="background: linear-gradient(135deg, #f59e0b, #d97706); top: 0px; right: 0px; bottom: auto; font-size: 0.7rem; padding: 2px 6px; font-weight: bold; white-space: nowrap;">2a al 50%</span>' +
                    '<span class="inline-block w-full py-3 px-2 bg-blue-500 text-white text-base font-semibold rounded hover:bg-blue-600" data-2a-al-50-cheque="true" data-url="' + item.url.replace(/"/g, '&quot;').replace(/'/g, '&#39;') + '">Ir a la tienda</span>';
                } else if (item.descuentos === '2a al 70') {
                  botonHtml = '<span class="cupon-badge" style="background: linear-gradient(135deg, #f59e0b, #d97706); top: 0px; right: 0px; bottom: auto; font-size: 0.7rem; padding: 2px 6px; font-weight: normal; white-space: nowrap;">2a AL 70%</span>' +
                    '<span class="inline-block w-full py-3 px-2 bg-blue-500 text-white text-base font-semibold rounded hover:bg-blue-600" data-2a-al-70="true" data-url="' + item.url.replace(/"/g, '&quot;').replace(/'/g, '&#39;') + '">Ir a la tienda</span>';
                } else if (item.descuentos === '2a al 50') {
                  botonHtml = '<span class="cupon-badge" style="background: linear-gradient(135deg, #f59e0b, #d97706); top: 0px; right: 0px; bottom: auto; font-size: 0.7rem; padding: 2px 6px; font-weight: normal; white-space: nowrap;">2a al 50%</span>' +
                    '<span class="inline-block w-full py-3 px-2 bg-blue-500 text-white text-base font-semibold rounded hover:bg-blue-600" data-2a-al-50="true" data-url="' + item.url.replace(/"/g, '&quot;').replace(/'/g, '&#39;') + '">Ir a la tienda</span>';
                } else {
                  botonHtml = '<span class="inline-block w-full py-3 px-2 bg-blue-500 text-white text-base font-semibold rounded hover:bg-blue-600">Ir a la tienda</span>';
                }
                
                html += `
                  <a href="${item.url}" target="_blank" rel="sponsored noopener noreferrer" class="product-card block bg-white rounded-lg shadow py-2 px-4 sm:px-6 grid ${gridColsMovil} ${gridCols} gap-2 sm:gap-4 sm:items-center card-hover hover:shadow-md">
                    <div class="logo flex items-center justify-center divider min-w-0 overflow-hidden ${ordenLogo}">
                      <img src="${item.logo}" loading="lazy" alt="${item.nombre}" class="w-full max-w-[130px] sm:h-[45px] h-[36px] object-contain">
                    </div>
                    <div class="envio text-gray-700 divider text-center min-w-0 overflow-hidden ${ordenEnvio}">
                      <p class="text-sm text-gray-500 font-bold"><img src='{{ asset('images/van.png') }}' loading="lazy" alt='Van' class='icon-small'> ${item.envio_gratis}</p>
                      <p class="text-sm text-gray-500"><img src='{{ asset('images/van.png') }}' loading="lazy" alt='Van' class='icon-small'> ${item.envio_normal}</p>
                    </div>
                    ${mostrarCantidad ? `
                    <div class="und text-gray-700 divider text-center min-w-0 overflow-hidden">
                      <div class="font-semibold">Cantidad</div>
                      <div class="text-sm text-gray-500 leading-tight">${_fc1(item.unidades, unidadMedida)}</div>
                    </div>
                    ` : ''}
                    ${columnasDinamicas}
                    ${!mostrarPrecioTotal && !mostrarPrecioUnidad ? `
                    <div class="precio-total text-gray-700 divider text-center min-w-0 overflow-hidden ${ordenPrecio}">
                      <div class="text-sm text-gray-500">Precio total</div>
                      <div class="text-3xl font-extrabold text-pink-400">${item.precio_total} <span class="text-sm text-gray-500 font-normal">€</span></div>
                    </div>
                    ` : ''}
                    ${mostrarPrecioTotal ? `
                    <div class="precio-total text-gray-700 divider text-center min-w-0 overflow-hidden ${ordenPrecio} ${esUnidadUnicaSinColumnas ? 'precio-unidad-unica-sin-columnas' : ''}">
                      <div class="${precioTotalLabelClass}">Precio total</div>
                      <div class="${precioTotalClass}">${item.precio_total} <span class="text-sm text-gray-500 font-normal">€</span></div>
                    </div>
                    ` : ''}
                    ${mostrarPrecioUnidad ? `
                    <div class="precio-und text-center text-gray-700 divider min-w-0 overflow-hidden ${ordenPrecio}">
                      <div class="${precioUnidadLabelClass}">${_glp1(unidadMedida)}</div>
                      <div class="${precioUnidadClass}">
                        ${item.precio_unidad}
                        <span class="text-sm text-gray-500 font-normal">${_gsp1(unidadMedida)}</span>
                      </div>
                    </div>
                    ` : ''}
                    <div class="boton text-center min-w-0 relative overflow-visible ${ordenBoton}">
                      ${botonHtml}
                    </div>
                  </a>
                `;
                
                ofertasRenderizadas.push({
                  tipo: 'oferta_individual',
                  id: item.id,
                  tienda: item.tienda,
                  precio_unidad: item.precio_unidad
                });
                
                itemIndex++;
                }
              }
            }
            
            cont.innerHTML = html;
            
            // Añadir event listeners para cupones después de renderizar
            // Usar setTimeout para asegurar que el DOM se haya actualizado
            setTimeout(() => {
              _scel1();
            }, 100);
            
            // Actualizar mejor oferta en móvil
            _amom1(filtradas);
            } catch (error) {
              {{-- x6: Contenedor del listado de ofertas --}}
              const cont = document.getElementById('x6');
              if (cont) {
                cont.innerHTML = `<div class='bg-red-100 border-l-4 border-red-500 text-red-800 p-6 rounded text-lg text-center'>Error al cargar las ofertas. Por favor, recarga la página. Error: ${error.message}</div>`;
              }
            }
          }

          {{-- _mi1: marcarInteraccion - Marca que el usuario ha interactuado con los filtros --}}
          function _mi1() {
            filtroInteractuado = true;
            mostrarTodasLasOfertas = false; // Resetear para mostrar solo las primeras 15
            _ro1();
            // Actualizar oferta en el modal si está abierto
            if (typeof _rod1 === 'function') {
              {{-- x18: Modal de imágenes --}}
              const modal = document.getElementById('x18');
              if (modal && !modal.classList.contains('hidden')) {
                _rod1();
              }
            }
          }

          {{-- _sob1: setOrdenBotones - Establece el estado visual de los botones de ordenación --}}
          function _sob1() {
            {{-- x4: Botón ordenar por unidades --}}
            const btnUnidades = document.getElementById('x4');
            {{-- x5: Botón ordenar por precio total --}}
            const btnPrecio = document.getElementById('x5');
            
            // Solo ejecutar si los botones existen
            if (!btnUnidades || !btnPrecio) return;
            
            [btnUnidades, btnPrecio].forEach(btn => {
              if (btn) {
                btn.classList.remove('bg-gray-700', 'text-white', 'bg-white', 'text-blue-500');
                btn.classList.add('bg-white', 'text-blue-500'); // Estado base inactivo
              }
            });
            if (ordenActual === 'unidades' || ordenActual === null) {
              btnUnidades.classList.add('bg-gray-700', 'text-white'); // Activo
              btnUnidades.classList.remove('bg-white', 'text-blue-500');
            } else if (ordenActual === 'precio_total') {
              btnPrecio.classList.add('bg-gray-700', 'text-white'); // Activo
              btnPrecio.classList.remove('bg-white', 'text-blue-500');
            }
          }

          {{-- x1: Filtro de tienda --}}
          document.getElementById('x1').addEventListener('change', _mi1);
          {{-- x2: Filtro de envío gratis --}}
          document.getElementById('x2').addEventListener('change', _mi1);
          {{-- x3: Filtro de cantidad --}}
          document.getElementById('x3').addEventListener('change', _mi1);
          
          {{-- Función para configurar event listeners de cupones (debe estar antes de usarse) --}}
          {{-- _scel1: setupCuponEventListeners - Configura todos los event listeners para los botones de cupones y descuentos --}}
          function _scel1() {
            // Buscar todos los botones con cupón (tanto split-button como normales)
            const cuponButtons = document.querySelectorAll('[data-cupon="true"]');
            const cuponAliExpressButtons = document.querySelectorAll('[data-cupon-aliexpress="true"]');
            const cuponCholloTiendaButtons = document.querySelectorAll('[data-cupon-chollo-tienda="true"]');
            const cuponCholloTiendaSoloButtons = document.querySelectorAll('[data-cupon-chollo-tienda-solo="true"]');
            const botones3x2 = document.querySelectorAll('[data-3x2="true"]');
            const botones2x1 = document.querySelectorAll('[data-2x1="true"]');
            const botones2aAl50Cheque = document.querySelectorAll('[data-2a-al-50-cheque="true"]');
            const botones2aAl50 = document.querySelectorAll('[data-2a-al-50="true"]');
            const botones2aAl70 = document.querySelectorAll('[data-2a-al-70="true"]');
            
            // Buscar todas las tarjetas de ofertas para añadir event listeners
            const productCards = document.querySelectorAll('.product-card');
            
            // Buscar botones de las mejores ofertas en móvil
            const mejorOfertaBoton1 = document.getElementById('mejor-oferta-boton-movil-1');
            const mejorOfertaBoton2 = document.getElementById('mejor-oferta-boton-movil-2');
            
            
            cuponButtons.forEach((button, index) => {
              // Remover event listeners anteriores para evitar duplicados
              button.removeEventListener('click', _hcc1);
              button.addEventListener('click', _hcc1);
            });
            
            cuponAliExpressButtons.forEach((button, index) => {
              // Remover event listeners anteriores para evitar duplicados
              button.removeEventListener('click', _hcac1);
              button.addEventListener('click', _hcac1);
            });
            
            cuponCholloTiendaButtons.forEach((button, index) => {
              // Remover event listeners anteriores para evitar duplicados
              button.removeEventListener('click', _hctc1);
              button.addEventListener('click', _hctc1);
            });
            
            cuponCholloTiendaSoloButtons.forEach((button, index) => {
              // Remover event listeners anteriores para evitar duplicados
              button.removeEventListener('click', _hctc1);
              button.addEventListener('click', _hctc1);
            });
            
            botones3x2.forEach(button => {
              // Remover event listeners anteriores para evitar duplicados
              button.removeEventListener('click', _h3x21);
              button.addEventListener('click', _h3x21);
            });
            
            botones2x1.forEach(button => {
              // Remover event listeners anteriores para evitar duplicados
              button.removeEventListener('click', _h2x11);
              button.addEventListener('click', _h2x11);
            });
            
            botones2aAl50Cheque.forEach(button => {
              button.removeEventListener('click', _h2a50c1);
              button.addEventListener('click', _h2a50c1);
            });
            
            botones2aAl50.forEach(button => {
              // Remover event listeners anteriores para evitar duplicados
              button.removeEventListener('click', _h2a501);
              button.addEventListener('click', _h2a501);
            });
            
            botones2aAl70.forEach(button => {
              // Remover event listeners anteriores para evitar duplicados
              button.removeEventListener('click', _h2a701);
              button.addEventListener('click', _h2a701);
            });
            
            // Configurar event listeners para tarjetas de ofertas
            productCards.forEach(card => {
              // Remover event listeners anteriores para evitar duplicados
              card.removeEventListener('click', _hpcc1);
              card.addEventListener('click', _hpcc1);
            });
            
            // Configurar event listeners para los contenedores de las mejores ofertas en móvil
            const mejorOfertaContenedor1 = document.getElementById('mejor-oferta-contenedor-movil-1');
            const mejorOfertaContenedor2 = document.getElementById('mejor-oferta-contenedor-movil-2');
            const mejorOfertaContenedor3 = document.getElementById('mejor-oferta-contenedor-movil-3');
            const mejorOfertaContenedor4 = document.getElementById('mejor-oferta-contenedor-movil-4');
            
            [mejorOfertaContenedor1, mejorOfertaContenedor2, mejorOfertaContenedor3, mejorOfertaContenedor4].forEach((contenedor, index) => {
              if (contenedor) {
                // Buscar el botón dentro del contenedor usando el ID específico (puede ser <a> o <span>)
                const botonContainer = document.getElementById(`mejor-oferta-boton-movil-${index + 1}`);
                
                if (botonContainer) {
                  // Buscar tanto <a> como <span> (los botones con descuento son <span>)
                  const botonElement = botonContainer.querySelector('a, span[data-cupon], span[data-cupon-aliexpress], span[data-cupon-chollo-tienda], span[data-3x2], span[data-2x1], span[data-2a-al-50-cheque], span[data-2a-al-50], span[data-2a-al-70]');
                  
                  if (botonElement) {
                    // Remover event listeners anteriores para evitar duplicados
                    botonElement.removeEventListener('click', _hmomc1);
                    // Usar capture: true para que se ejecute antes que el handler del contenedor
                    botonElement.addEventListener('click', _hmomc1, true);
                  }
                }
                
                // También agregar event listener al enlace padre del contenedor para que abra el modal si tiene descuento
                const contenedorLink = contenedor.querySelector('a');
                if (contenedorLink) {
                  // Remover event listeners anteriores
                  const handlerAnterior = contenedorLink._handleMejorOfertaContenedor;
                  if (handlerAnterior) {
                    contenedorLink.removeEventListener('click', handlerAnterior);
                  }
                  
                  // Crear handler específico para el contenedor que busca el botón dentro
                  const handlerContenedor = function(e) {
                    // Si el clic fue directamente en el botón, no hacer nada (ya se maneja)
                    if (e.target.closest('#mejor-oferta-boton-movil-' + (index + 1))) {
                      return;
                    }
                    
                    // Buscar el botón dentro del contenedor (puede ser <a> o <span>)
                    const botonContainer = document.getElementById(`mejor-oferta-boton-movil-${index + 1}`);
                    
                    if (botonContainer) {
                      // Buscar tanto <a> como <span> con atributos de descuento
                      const botonElement = botonContainer.querySelector('a, span[data-cupon], span[data-cupon-aliexpress], span[data-cupon-chollo-tienda], span[data-3x2], span[data-2x1], span[data-2a-al-50-cheque], span[data-2a-al-50], span[data-2a-al-70]');
                      
                      if (botonElement) {
                        const tieneDescuento = botonElement.hasAttribute('data-cupon') || 
                                                botonElement.hasAttribute('data-cupon-aliexpress') || 
                                                botonElement.hasAttribute('data-cupon-chollo-tienda') || 
                                                botonElement.hasAttribute('data-3x2') || 
                                                botonElement.hasAttribute('data-2x1') || 
                                               botonElement.hasAttribute('data-2a-al-50-cheque') || 
                                               botonElement.hasAttribute('data-2a-al-50') || 
                                                botonElement.hasAttribute('data-2a-al-70');
                        
                        if (tieneDescuento) {
                          // Si tiene descuento, prevenir navegación y abrir modal
                          e.preventDefault();
                          e.stopPropagation();
                          // Llamar directamente a handleMejorOfertaMovilClick con el botonElement como target
                          const fakeEvent = {
                            target: botonElement,
                            preventDefault: () => {},
                            stopPropagation: () => {}
                          };
                          _hmomc1(fakeEvent);
                        }
                      }
                      // Si no tiene descuento, dejar que el enlace padre navegue normalmente
                    }
                  };
                  
                  // Guardar referencia del handler para poder removerlo después
                  contenedorLink._handleMejorOfertaContenedor = handlerContenedor;
                  // Usar capture: true para que se ejecute antes que otros handlers y poder prevenir la navegación
                  contenedorLink.addEventListener('click', handlerContenedor, true);
                }
              }
            });
          }
          
          // Event listener para el botón "mostrar más"
          {{-- x8: Botón mostrar más ofertas --}}
          document.getElementById('x8').addEventListener('click', function() {
            mostrarTodasLasOfertas = true;
            _ro1();
          });
          // Solo añadir event listeners de ordenar si los botones existen (no es unidadUnica)
          {{-- x4: Botón ordenar por unidades --}}
          const btnOrdenUnidades2 = document.getElementById('x4');
          {{-- x5: Botón ordenar por precio total --}}
          const btnOrdenPrecio2 = document.getElementById('x5');
          
          if (btnOrdenUnidades2) {
            btnOrdenUnidades2.addEventListener('click', function() {
              ordenActual = 'unidades'; // Ahora 'unidades' significa precio_unidad
              _sob1();
              filtroInteractuado = true;
              mostrarTodasLasOfertas = false; // Resetear para mostrar solo las primeras 15
              _ro1();
              // Actualizar oferta en el modal si está abierto
              if (typeof _rod1 === 'function') {
                {{-- x18: Modal de imágenes --}}
              const modal = document.getElementById('x18');
                if (modal && !modal.classList.contains('hidden')) {
                  _rod1();
                }
              }
            });
          }
          
          if (btnOrdenPrecio2) {
            btnOrdenPrecio2.addEventListener('click', function() {
              ordenActual = 'precio_total';
              _sob1();
              filtroInteractuado = true;
              mostrarTodasLasOfertas = false; // Resetear para mostrar solo las primeras 15
              _ro1();
              // Actualizar oferta en el modal si está abierto
              if (typeof _rod1 === 'function') {
                {{-- x18: Modal de imágenes --}}
              const modal = document.getElementById('x18');
                if (modal && !modal.classList.contains('hidden')) {
                  _rod1();
                }
              }
            });
          }

          document.addEventListener('DOMContentLoaded', function() {
            // Solo llamar a setOrdenBotones si los botones existen (no es unidadUnica)
            {{-- x4: Botón ordenar por unidades --}}
            const btnOrdenUnidades3 = document.getElementById('x4');
            {{-- x5: Botón ordenar por precio total --}}
            const btnOrdenPrecio3 = document.getElementById('x5');
            if (btnOrdenUnidades3 && btnOrdenPrecio3) {
              _sob1();
            }
            // Filtrado inicial por parámetro v si existe
            const vParam = _gpv1();
            if (vParam) {
              {{-- x3: Filtro de cantidad --}}
              document.getElementById('x3').value = vParam;
            }
            _ro1();
            
            // Funcionalidad para "Leer más" y "Leer menos" - MÓVIL
            {{-- x10: Descripción corta (móvil) --}}
            const descripcionCorta = document.getElementById('x10');
            {{-- x12: Descripción completa (móvil) --}}
            const descripcionCompleta = document.getElementById('x12');
            {{-- x11: Botón leer más (móvil) --}}
            const leerMas = document.getElementById('x11');
            {{-- x13: Botón leer menos (móvil) --}}
            const leerMenos = document.getElementById('x13');
            
            if (leerMas && leerMenos && descripcionCorta && descripcionCompleta) {
              leerMas.addEventListener('click', function() {
                descripcionCorta.classList.add('hidden');
                descripcionCompleta.classList.remove('hidden');
              });
              
              leerMenos.addEventListener('click', function() {
                descripcionCompleta.classList.add('hidden');
                descripcionCorta.classList.remove('hidden');
              });
            }
            
            // Funcionalidad para "Leer más" y "Leer menos" - DESKTOP
            {{-- x14: Descripción corta (desktop) --}}
            const descripcionCortaDesktop = document.getElementById('x14');
            {{-- x16: Descripción completa (desktop) --}}
            const descripcionCompletaDesktop = document.getElementById('x16');
            {{-- x15: Botón leer más (desktop) --}}
            const leerMasDesktop = document.getElementById('x15');
            {{-- x17: Botón leer menos (desktop) --}}
            const leerMenosDesktop = document.getElementById('x17');
            
            if (leerMasDesktop && leerMenosDesktop && descripcionCortaDesktop && descripcionCompletaDesktop) {
              leerMasDesktop.addEventListener('click', function() {
                descripcionCortaDesktop.classList.add('hidden');
                descripcionCompletaDesktop.classList.remove('hidden');
              });
              
              leerMenosDesktop.addEventListener('click', function() {
                descripcionCompletaDesktop.classList.add('hidden');
                descripcionCortaDesktop.classList.remove('hidden');
              });
            }
          });
        </script>

        {{-- INFO DETALLADA DEL PRODUCTO --}}
        <section class="mt-8 bg-white rounded-2xl shadow-lg p-6 text-gray-800 leading-relaxed space-y-8">

          {{-- TÍTULO --}}
          <header>
            <h2 class="text-2xl font-bold mb-2">{{ $producto->marca}} {{ $producto->modelo}} {{ $producto->talla}} – Análisis completo</h2>
            <div class="text-gray-600 text-sm leading-relaxed descripcion-contenido">{!! str_replace('<h3>', '<p class="descripcion-titulo">', str_replace('</h3>', '</p>', $producto->descripcion_larga)) !!}</div>
          </header>

          {{-- CARACTERÍSTICAS --}}
          <section>
            <h3 class="text-xl font-semibold mb-4">Características principales</h3>
            <ul class="list-disc pl-5 space-y-1 text-gray-700">
              @foreach ($producto->caracteristicas as $caracteristica)
              <li>{{ $caracteristica }}</li>
              @endforeach
            </ul>
          </section>

          {{-- PROS Y CONTRAS --}}
          <section>
            <h3 class="text-xl font-semibold mb-4">Pros y contras</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div class="bg-green-50 border-l-4 border-green-400 p-4 rounded-lg">
                <h4 class="font-semibold text-green-700 mb-2">Pros</h4>
                <ul class="list-disc pl-5 text-sm text-green-800 space-y-1">
                  @foreach ($producto->pros as $pro)
                  <li>{{ $pro }}</li>
                  @endforeach
                </ul>
              </div>
              <div class="bg-red-50 border-l-4 border-red-400 p-4 rounded-lg">
                <h4 class="font-semibold text-red-700 mb-2">Contras</h4>
                <ul class="list-disc pl-5 text-sm text-red-800 space-y-1">
                  @foreach ($producto->contras as $contra)
                  <li>{{ $contra }}</li>
                  @endforeach
                </ul>
              </div>
            </div>
          </section>

          {{-- PREGUNTAS FRECUENTES (ACORDEÓN CON FLECHAS) --}}
          <section x-data="{ open: null }">
            <h3 class="text-xl font-semibold mb-4">Preguntas frecuentes</h3>
            <div class="space-y-3">
              @foreach ($producto->faq as $i => $item)
              <div class="border rounded-lg overflow-hidden">
                <button @click="open === {{ $i }} ? open = null : open = {{ $i }}"
                  class="w-full flex justify-between items-center px-4 py-3 text-left font-medium bg-gray-100 hover:bg-gray-200 transition">
                  <span>{{ $item['pregunta'] }}</span>
                  <svg :class="open === {{ $i }} ? 'rotate-180' : ''"
                    class="w-5 h-5 text-gray-500 transition-transform duration-300" fill="none" stroke="currentColor" stroke-width="2"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                  </svg>
                </button>
                <div x-show="open === {{ $i }}" x-transition class="px-4 py-3 text-sm text-gray-700 bg-white">
                  {{ $item['respuesta'] }}
                </div>
              </div>
              @endforeach
            </div>
          </section>

          {{-- Snippets para mejorar el SEO --}}
          {{-- Para indexar en GOOGLE la ficha de producto --}}
          @php
          // Generar array de ofertas para cada versión (por unidades)
          $offersJsonLd = $cantidadesUnicas->map(function($cantidad) use ($producto, $ofertas) {
              $oferta = $ofertas->where('unidades', $cantidad)->sortBy('precio_total')->first();
              if (!$oferta) return null;
              return [
                  "@type" => "Offer",
                  "url" => $producto->categoria->construirUrlCategorias($producto->slug) . '?v=' . $cantidad,
                  "price" => number_format($oferta->precio_total, 2, '.', ''),
                  "priceCurrency" => "EUR",
                  "itemCondition" => "https://schema.org/NewCondition",
                  "availability" => "https://schema.org/InStock",
                  "seller" => [
                      "@type" => "Organization",
                      "name" => $oferta->tienda->nombre
                  ],
                  "sku" => $oferta->id,
                  "description" => $producto->descripcion_corta . ' - ' . $cantidad . ' unidades',
              ];
          })->filter()->values();

          $productJsonLd = [
              "@context" => "https://schema.org",
              "@type" => "Product",
              "name" => $producto->titulo,
              "image" => [ asset('images/' . ($producto->imagen_pequena[0] ?? '')) ],
              "description" => $producto->descripcion_corta,
              "brand" => [
                  "@type" => "Brand",
                  "name" => $producto->marca,
                  "url" => url('buscar?q=' . Str::slug($producto->marca))
              ],
              "offers" => $offersJsonLd
          ];
          @endphp
          {{-- Para indexar las preguntas frecuentes --}}
          @php
          $faqJsonLd = [
          "@context" => "https://schema.org",
          "@type" => "FAQPage",
          "mainEntity" => collect($producto->faq, true)->map(fn($item) => [
          "@type" => "Question",
          "name" => $item['pregunta'],
          "acceptedAnswer" => [
          "@type" => "Answer",
          "text" => $item['respuesta']
          ]
          ])->values()
          ];
          @endphp

          <script type="application/ld+json">
            {!! json_encode($faqJsonLd, JSON_HEX_QUOT | JSON_HEX_APOS | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
            </script>

            <script type="application/ld+json">
            {!! json_encode($productJsonLd, JSON_HEX_QUOT | JSON_HEX_APOS | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
          </script>
          {{-- Para indexar el buscador --}}
          <script type="application/ld+json">
            {
              "@context": "https://schema.org",
              "@type": "WebSite",
              "name": "Komparador.com",
              "url": "https://komparador.com",
              "potentialAction": {
                "@type": "SearchAction",
                "target": "https://komparador.com/buscar?q={search_term_string}",
                "query-input": "required name=search_term_string"
              }
            }
          </script>
      </main>
    </div>
    </section>
  </div>

  {{-- FOOTER DESDE LA RUTA COMPONENTS/FOOTER --}}
    <x-footer />

  {{-- BOTÓN FLOTANTE PARA MÓVIL - ALERTA DE PRECIO --}}
  {{-- x19: Botón flotante de alerta (móvil) --}}
  <div id="x19" class="fixed bottom-8 right-4 z-50 lg:hidden">
    <button onclick="_sa1()" 
            class="bg-gradient-to-r from-blue-500 to-purple-600 hover:from-blue-600 hover:to-purple-700 text-white rounded-full p-3 shadow-xl transform hover:scale-105 transition-all duration-300 flex items-center justify-center group">
      <svg class="w-5 h-5 group-hover:rotate-12 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5v-5z"></path>
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
      </svg>
    </button>
    <div class="absolute -top-1 -right-1 bg-yellow-400 text-gray-800 text-xs rounded-full w-5 h-5 flex items-center justify-center animate-bounce">
      <span class="text-xs font-bold">⚡</span>
    </div>
  </div>

  {{-- VENTANA EMERGENTE PARA CUPONES Y 3x2 --}}
  {{-- x20: Modal overlay de cupones --}}
  <div id="x20" class="cupon-modal-overlay">
    <div class="cupon-modal">
      {{-- Botón de cerrar (X) en la esquina superior derecha --}}
      {{-- x21: Botón cerrar modal de cupones --}}
      <button id="x21" class="cupon-modal-close">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
        </svg>
      </button>
      
      <div class="cupon-modal-header">
        <div class="cupon-modal-icon">
          <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
          </svg>
        </div>
        {{-- x22: Título del modal de cupones --}}
        <h3 id="x22" class="cupon-modal-title">¡Aplicar cupón!</h3>
        {{-- x23: Texto del modal de cupones --}}
        <p id="x23" class="cupon-modal-text">
          Esta oferta tiene un cupón de descuento disponible. 
          Recuerda aplicarlo para disfrutar del precio mostrado.
        </p>
      </div>
      {{-- x24: Lista de cupones en el modal --}}
      <div id="x24" class="mb-4 max-h-96 overflow-y-auto">
        {{-- Los cupones se generarán dinámicamente aquí --}}
      </div>
      <div class="cupon-modal-buttons">
        {{-- x25: Botón cancelar modal de cupones --}}
        <button id="x25" class="cupon-btn cupon-btn-secondary">Cancelar</button>
        {{-- x26: Botón continuar modal de cupones --}}
        <a id="x26" href="#" class="cupon-btn cupon-btn-primary">Ir a la tienda</a>
      </div>
    </div>
  </div>


  <script>
    // Variables globales para los gráficos
    {{-- v7: chart - Instancia del gráfico de precios desktop --}}
    let v7 = null;
    {{-- v8: chartMovil - Instancia del gráfico de precios móvil --}}
    let v8 = null;
    {{-- v9: periodoActual - Período actual del gráfico (3m, 6m, 1a, etc.) --}}
    let v9 = '3m';
    {{-- v10: productoId - ID del producto actual --}}
    const v10 = {{ $producto->id }};
    {{-- v11: tokenSeguridad - Token de seguridad para las peticiones API --}}
    const v11 = '{{ hash('md5', $producto->id . env('APP_KEY', 'default_key')) }}';
    
    // Función para detectar el mejor período inicial
    {{-- _dmp1: detectarMejorPeriodo - Detecta el mejor período inicial para mostrar en el gráfico de precios --}}
    function _dmp1(precios) {
      const datosConPrecio = precios.filter(p => p.precio > 0).length;
      const totalDatos = precios.length;
      const densidad = (datosConPrecio / totalDatos) * 100;
      
      // Si no hay datos, mostrar mensaje
      if (datosConPrecio === 0) {
        _mmsd1();
        return '3m'; // Cambiar por defecto a 3 meses
      }
      
      // Siempre empezar con 3 meses por defecto
      return '3m';
    }
    
    // Función para mostrar mensaje cuando no hay datos
    {{-- _mmsd1: mostrarMensajeSinDatos - Muestra un mensaje cuando no hay datos históricos disponibles --}}
    function _mmsd1() {
      const contenedores = [
        document.getElementById('graficoPrecios').parentElement,
        document.getElementById('graficoPreciosMovil')?.parentElement
      ].filter(Boolean);
      
      contenedores.forEach(contenedor => {
        const canvas = contenedor.querySelector('canvas');
        if (canvas) {
          canvas.style.display = 'none';
          
          // Crear mensaje informativo
          const mensaje = document.createElement('div');
          mensaje.className = 'flex items-center justify-center h-full text-gray-500 text-center p-4';
          mensaje.innerHTML = `
            <div>
              <svg class="w-12 h-12 mx-auto mb-2 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
              </svg>
              <p class="text-sm">No hay datos históricos disponibles</p>
              <p class="text-xs text-gray-400 mt-1">Los precios se actualizan cada 4 horas</p>
            </div>
          `;
          contenedor.appendChild(mensaje);
        }
      });
    }
    
    document.addEventListener('DOMContentLoaded', function() {
      const ctx = document.getElementById('graficoPrecios').getContext('2d');
      const precios = @json($precios);
      const labels = precios.map(p => p.fecha);
      const datos = precios.map(p => p.precio);

      // Función para crear/actualizar gráfico
      {{-- _cg1: crearGrafico - Crea o actualiza un gráfico de precios históricos --}}
      function _cg1(ctx, labels, datos, isMovil = false) {
        const config = {
          type: 'line',
          data: {
            labels: labels,
            datasets: [{
              label: 'Precio (€)',
              data: datos,
              fill: true,
              borderColor: 'rgb(75, 192, 192)',
              backgroundColor: 'rgba(75, 192, 192, 0.1)',
              tension: 0.3,
              pointRadius: 0, // Quitar los puntos
              pointHoverRadius: 4, // Solo mostrar punto al hacer hover
              pointHoverBackgroundColor: 'rgb(75, 192, 192)',
              pointHoverBorderColor: '#fff',
              pointHoverBorderWidth: 2
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
              mode: 'index',
              intersect: false
            },
            plugins: {
              tooltip: {
                mode: 'index',
                intersect: false,
                callbacks: {
                  label: context => `Precio: ${context.parsed.y.toFixed(2)} €`
                }
              },
              legend: {
                display: false
              }
            },
            scales: {
              x: {
                ticks: {
                  maxTicksLimit: 6, // Reducir a 6 fechas máximo
                  maxRotation: 0,
                  autoSkip: true,
                  autoSkipPadding: 20 // Espacio entre etiquetas
                },
                grid: {
                  display: false
                }
              },
              y: {
                beginAtZero: false,
                ticks: {
                  callback: value => value.toFixed(2) + ' €',
                  maxTicksLimit: 8
                },
                grid: {
                  color: 'rgba(0, 0, 0, 0.1)'
                }
              }
            },
            layout: {
              padding: {
                left: 10,
                right: 10,
                top: 10,
                bottom: 10
              }
            }
          }
        };

        if (isMovil) {
          if (v8) v8.destroy();
          v8 = new Chart(ctx, config);
        } else {
          if (v7) v7.destroy();
          v7 = new Chart(ctx, config);
        }
      }

      // Función para actualizar gráficos con nuevos datos
      {{-- _ag1: actualizarGraficos - Actualiza los gráficos de precios con nuevos datos --}}
      function _ag1(labels, datos) {
        // Añadir efecto de fade out
        const contenedores = [
          document.getElementById('graficoPrecios').parentElement,
          document.getElementById('graficoPreciosMovil')?.parentElement
        ].filter(Boolean);
        
        contenedores.forEach(contenedor => {
          contenedor.style.opacity = '0.7';
          contenedor.style.transition = 'opacity 0.2s ease-in-out';
        });
        
        // Crear nuevos gráficos
        const ctx = document.getElementById('graficoPrecios').getContext('2d');
        _cg1(ctx, labels, datos, false);

        const canvasMovil = document.getElementById('graficoPreciosMovil');
        if (canvasMovil) {
          const ctxMovil = canvasMovil.getContext('2d');
          _cg1(ctxMovil, labels, datos, true);
        }
        
        // Forzar redibujado después de un pequeño delay
        setTimeout(() => {
          if (v7) {
            v7.resize();
            v7.render();
          }
          if (v8) {
            v8.resize();
            v8.render();
          }
        }, 100);
        
        // Restaurar opacidad
        setTimeout(() => {
          contenedores.forEach(contenedor => {
            contenedor.style.opacity = '1';
          });
        }, 200);
      }

      // Función para cambiar período
      {{-- _cp1: cambiarPeriodo - Cambia el período del gráfico de precios (1m, 3m, 6m, 1a) --}}
      async function _cp1(periodo) {
        if (periodo === v9) return;
        
        try {
          // Deshabilitar botones durante la carga
          const botones = document.querySelectorAll('[data-periodo]');
          botones.forEach(btn => {
            btn.disabled = true;
            btn.style.cursor = 'not-allowed';
            btn.style.opacity = '0.6';
          });
          
          const response = await fetch(`/api/precios-historicos/${v10}?periodo=${periodo}&token=${v11}`);
          
          if (!response.ok) {
            throw new Error('Error al cargar datos');
          }
          
          const data = await response.json();
          
          // Actualizar gráficos
          const labels = data.precios.map(p => p.fecha);
          const datos = data.precios.map(p => p.precio);
          _ag1(labels, datos);
          
          // Actualizar estado de botones
          v9 = periodo;
          _ab1(periodo);
          
        } catch (error) {
          // Mostrar mensaje de error más específico
          let mensaje = 'Error al cargar los datos del gráfico';
          if (error.message.includes('429')) {
            mensaje = 'Demasiadas solicitudes. Inténtalo de nuevo en un momento.';
          } else if (error.message.includes('403')) {
            mensaje = 'Error de autenticación. Recarga la página.';
          } else if (error.message.includes('404')) {
            mensaje = 'No se encontraron datos para este período.';
          }
          
          // Mostrar mensaje de error en consola
        } finally {
          // Habilitar botones
          const botones = document.querySelectorAll('[data-periodo]');
          botones.forEach(btn => {
            btn.disabled = false;
            btn.style.cursor = 'pointer';
            btn.style.opacity = '1';
          });
        }
      }

      // Función para actualizar estado de botones
      {{-- _ab1: actualizarBotones - Actualiza el estado visual de los botones de período del gráfico --}}
      function _ab1(periodoActivo) {
        const botones = document.querySelectorAll('[data-periodo]');
        botones.forEach(btn => {
          const periodo = btn.getAttribute('data-periodo');
          if (periodo === periodoActivo) {
            btn.classList.add('bg-blue-500', 'text-white');
            btn.classList.remove('hover:bg-gray-100');
          } else {
            btn.classList.remove('bg-blue-500', 'text-white');
            btn.classList.add('hover:bg-gray-100');
          }
        });
      }

      // Crear gráficos iniciales con los datos de 3 meses que vienen del servidor
      _cg1(ctx, labels, datos, false);

      const canvasMovil = document.getElementById('graficoPreciosMovil');
      if (canvasMovil) {
        const ctxMovil = canvasMovil.getContext('2d');
        _cg1(ctxMovil, labels, datos, true);
      }
      
      // Actualizar estado de botones para mostrar 3M como activo
      _ab1('3m');

      // Event listeners para botones de período
      document.querySelectorAll('[data-periodo]').forEach(btn => {
        btn.addEventListener('click', function() {
          const periodo = this.getAttribute('data-periodo');
          _cp1(periodo);
        });
      });

      // Resize observers
      const container = document.getElementById('graficoPrecios').parentElement;
      const resizeObserver = new ResizeObserver(() => {
        if (v7) {
          v7.resize();
          v7.render();
        }
      });
      resizeObserver.observe(container);

      if (canvasMovil) {
        const containerMovil = canvasMovil.parentElement;
        const resizeObserverMovil = new ResizeObserver(() => {
          if (v8) {
            v8.resize();
            v8.render();
          }
        });
        resizeObserverMovil.observe(containerMovil);
      }
    });
  </script>


  {{-- JS PARA EL ACORDEON DE PREGUNTAS FRECUENTES --}}
  <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<x-cookie-consent />

  {{-- JS PARA LOS FORMULARIOS DE ALERTA DE PRECIO --}}
<script>
// Función para scroll suave al formulario de alerta
{{-- _sa1: scrollToAlerta - Realiza scroll suave hasta el formulario de alerta de precio --}}
function _sa1() {
  const formularioAlerta = document.querySelector('aside');
  if (formularioAlerta) {
    // Añadir efecto visual al botón
    const boton = document.querySelector('#boton-flotante-alerta button');
    if (boton) {
      boton.classList.add('scale-95');
      setTimeout(() => boton.classList.remove('scale-95'), 200);
    }
    
    // Calcular la posición del formulario
    const rect = formularioAlerta.getBoundingClientRect();
    const windowHeight = window.innerHeight;
    
    // Scroll para que el formulario esté completamente visible con offset
    formularioAlerta.scrollIntoView({ 
      behavior: 'smooth', 
      block: 'start' 
    });
    
    // Añadir un offset adicional para que el formulario aparezca más abajo
    setTimeout(() => {
      window.scrollBy({
        top: -50,
        behavior: 'smooth'
      });
    }, 300);
    
    // Añadir un pequeño delay y hacer focus en el primer input
    setTimeout(() => {
      const primerInput = document.getElementById('correo_alerta2');
      if (primerInput) {
        primerInput.focus();
        // Añadir un efecto de highlight sutil
        primerInput.classList.add('ring-4', 'ring-blue-300');
        setTimeout(() => {
          primerInput.classList.remove('ring-4', 'ring-blue-300');
        }, 2000);
      }
    }, 800);
  }
}

// Ocultar botón flotante cuando el usuario está cerca del formulario
{{-- _tbf1: toggleBotonFlotante - Muestra/oculta el botón flotante de alerta según la posición del scroll --}}
function _tbf1() {
  {{-- x19: Botón flotante de alerta (móvil) --}}
  const botonFlotante = document.getElementById('x19');
  const formularioAlerta = document.querySelector('aside');
  
  if (botonFlotante && formularioAlerta) {
    const rect = formularioAlerta.getBoundingClientRect();
    const windowHeight = window.innerHeight;
    
    // El formulario está visible si está en la pantalla y no está muy arriba
    const isVisible = rect.top < windowHeight * 0.8 && rect.bottom > windowHeight * 0.2;
    
    if (isVisible) {
      botonFlotante.style.opacity = '0';
      botonFlotante.style.pointerEvents = 'none';
    } else {
      botonFlotante.style.opacity = '1';
      botonFlotante.style.pointerEvents = 'auto';
    }
  }
}

document.addEventListener('DOMContentLoaded', function() {
    // Función para manejar formularios de alerta
    {{-- _sfa1: setupFormularioAlerta - Configura los event listeners del formulario de alerta de precio --}}
    function _sfa1(formId, mensajeId) {
        const formAlerta = document.getElementById(formId);
        const mensajeAlerta = document.getElementById(mensajeId);
        
        if (formAlerta) {
            formAlerta.addEventListener('submit', function(e) {
                e.preventDefault();
                
                // Obtener los datos del formulario
                const formData = new FormData(formAlerta);
                const data = {
                    correo: formData.get('correo'),
                    precio_limite: parseFloat(formData.get('precio_limite')),
                    producto_id: parseInt(formData.get('producto_id')),
                    acepto_politicas: formData.get('acepto_politicas') === 'on'
                };
                
                // Validación básica del lado del cliente
                if (!data.correo || !data.precio_limite || !data.acepto_politicas) {
                    _mm1(mensajeAlerta, 'Por favor, completa todos los campos y acepta las políticas de privacidad.', 'error');
                    return;
                }
                
                // Deshabilitar el botón durante el envío
                const submitBtn = formAlerta.querySelector('button[type="submit"]');
                const originalText = submitBtn.textContent;
                submitBtn.disabled = true;
                submitBtn.textContent = 'Guardando...';
                
                // Enviar la petición
                fetch('{{ route("alertas.guardar") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify(data)
                })
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        _mm1(mensajeAlerta, result.message, 'success');
                        formAlerta.reset();
                        // Restaurar el precio por defecto
                        const precioInput = formAlerta.querySelector('input[name="precio_limite"]');
                        if (precioInput) {
                            precioInput.value = '{{ number_format($producto->precio, 2, ".", "") }}';
                        }
                    } else {
                        let errorMessage = 'Error al guardar la alerta.';
                        if (result.errors) {
                            const errors = Object.values(result.errors).flat();
                            errorMessage = errors.join(', ');
                        }
                        _mm1(mensajeAlerta, errorMessage, 'error');
                    }
                })
                .catch(error => {
                    _mm1(mensajeAlerta, 'Error de conexión. Inténtalo de nuevo.', 'error');
                })
                .finally(() => {
                    // Restaurar el botón
                    submitBtn.disabled = false;
                    submitBtn.textContent = originalText;
                });
            });
        }
    }
    
    {{-- _mm1: mostrarMensaje - Muestra un mensaje de éxito o error en el formulario de alerta --}}
    function _mm1(mensajeElement, mensaje, tipo) {
        if (!mensajeElement) return;
        
        mensajeElement.textContent = mensaje;
        
        // Estilos según el tipo de mensaje
        if (tipo === 'success') {
            mensajeElement.className = 'mt-3 text-sm p-3 rounded-md bg-green-100 text-green-800 border border-green-200';
        } else {
            mensajeElement.className = 'mt-3 text-sm p-3 rounded-md bg-red-100 text-red-800 border border-red-200';
        }
        
        mensajeElement.classList.remove('hidden');
        
        // Ocultar el mensaje después de 5 segundos
        setTimeout(() => {
            mensajeElement.classList.add('hidden');
        }, 5000);
    }
    
    // Configurar el formulario de alerta
    _sfa1('formAlertaPrecio2', 'mensajeAlerta2');
    
    // Configurar el comportamiento del botón flotante
    window.addEventListener('scroll', _tbf1);
    
    // Ocultar botón flotante inicialmente si el formulario ya es visible
    setTimeout(_tbf1, 100);
    
    // Configurar funcionalidad de cupones
    _scm2();
});

{{-- _scel1 ya está definida en el script anterior --}}

// Función para manejar clic en tarjeta de oferta
{{-- _hpcc1: handleProductCardClick - Maneja el evento de clic en las tarjetas de producto --}}
function _hpcc1(e) {
  // Si el clic fue en un botón específico, no hacer nada (ya se maneja por separado)
  if (e.target.closest('[data-cupon], [data-cupon-aliexpress], [data-cupon-chollo-tienda], [data-cupon-chollo-tienda-solo], [data-3x2], [data-2x1], [data-2a-al-50-cheque], [data-2a-al-50], [data-2a-al-70]')) {
    return;
  }
  
  const card = e.currentTarget;
  const url = card.href;
  
  // Buscar qué tipo de descuento tiene esta oferta
  const cuponButton = card.querySelector('[data-cupon="true"]');
  const cuponAliExpressButton = card.querySelector('[data-cupon-aliexpress="true"]');
  const cuponCholloTiendaButton = card.querySelector('[data-cupon-chollo-tienda="true"]');
  const cuponCholloTiendaSoloButton = card.querySelector('[data-cupon-chollo-tienda-solo="true"]');
  const boton3x2 = card.querySelector('[data-3x2="true"]');
  const boton2x1 = card.querySelector('[data-2x1="true"]');
  const boton2aAl50Cheque = card.querySelector('[data-2a-al-50-cheque="true"]');
  const boton2aAl50 = card.querySelector('[data-2a-al-50="true"]');
  const boton2aAl70 = card.querySelector('[data-2a-al-70="true"]');
  
  if (cuponAliExpressButton) {
    e.preventDefault();
    e.stopPropagation();
    const descuentos = cuponAliExpressButton.getAttribute('data-descuentos');
    const ofertaId = cuponAliExpressButton.getAttribute('data-oferta-id');
    showCuponAliExpressModal(url, descuentos, ofertaId);
  } else if (cuponCholloTiendaButton || cuponCholloTiendaSoloButton) {
    e.preventDefault();
    e.stopPropagation();
    const button = cuponCholloTiendaSoloButton || cuponCholloTiendaButton;
    const descuentos = button.getAttribute('data-descuentos');
    const ofertaId = button.getAttribute('data-oferta-id');
    _scctm1(url, descuentos, ofertaId);
  } else if (cuponButton) {
    e.preventDefault();
    e.stopPropagation();
    const valorCupon = cuponButton.getAttribute('data-valor-cupon');
    const codigoCupon = cuponButton.getAttribute('data-codigo-cupon');
    _scm1(url, valorCupon, codigoCupon);
  } else if (boton3x2) {
    e.preventDefault();
    e.stopPropagation();
    _s3x2m1(url);
  } else if (boton2x1) {
    e.preventDefault();
    e.stopPropagation();
    _s2x1m1(url);
  } else if (boton2aAl50Cheque) {
    e.preventDefault();
    e.stopPropagation();
    _s2a50cm1(url);
  } else if (boton2aAl50) {
    e.preventDefault();
    e.stopPropagation();
    _s2a50m1(url);
  } else if (boton2aAl70) {
    e.preventDefault();
    e.stopPropagation();
    _s2a70m1(url);
  }
}

// Función para manejar clic en botón con cupón
{{-- _hcc1: handleCuponClick - Maneja el clic en botones con cupones normales --}}
function _hcc1(e) {
  e.preventDefault();
  e.stopPropagation();
  
  const button = e.target.closest('[data-cupon="true"]') || e.target;
  // Buscar el enlace padre que contiene la URL (la tarjeta de producto)
  const card = button.closest('.product-card');
  let url = card ? card.href : null;
  // Si no hay card, buscar data-url en el botón
  if (!url) {
    url = button.getAttribute('data-url');
  }
  const valorCupon = button.getAttribute('data-valor-cupon');
  const codigoCupon = button.getAttribute('data-codigo-cupon');
  
  if (!url) {
    return;
  }
  
  // Mostrar modal de cupón
  _scm1(url, valorCupon, codigoCupon);
}

// Función para manejar clic en botón con cupón de AliExpress
{{-- _hcac1: handleCuponAliExpressClick - Maneja el clic en botones con cupones de AliExpress --}}
function _hcac1(e) {
  e.preventDefault();
  e.stopPropagation();
  
  const button = e.target.closest('[data-cupon-aliexpress="true"]') || e.target;
  // Para AliExpress, necesitamos obtener la URL del contexto de la oferta
  const ofertaElement = button.closest('.oferta-item, .oferta-grupo');
  let url = '#';
  
  if (ofertaElement) {
    // Buscar el enlace de la oferta
    const linkElement = ofertaElement.querySelector('a[href]');
    if (linkElement) {
      url = linkElement.href;
    }
  }
  
  // También intentar obtener la URL desde el atributo data-url
  const urlFromData = button.getAttribute('data-url');
  if (urlFromData) {
    url = urlFromData;
  }
  
  const descuentos = button.getAttribute('data-descuentos');
  const ofertaId = button.getAttribute('data-oferta-id');
  
  // Mostrar modal de cupón AliExpress
  showCuponAliExpressModal(url, descuentos, ofertaId);
}

// Función para manejar clic en botón con cupón de CholloTienda
{{-- _hctc1: handleCuponCholloTiendaClick - Maneja el clic en botones con cupones de CholloTienda --}}
function _hctc1(e) {
  e.preventDefault();
  e.stopPropagation();
  
  const button = e.target.closest('[data-cupon-chollo-tienda="true"], [data-cupon-chollo-tienda-solo="true"]') || e.target;
  // Para CholloTienda, necesitamos obtener la URL del contexto de la oferta
  const ofertaElement = button.closest('.oferta-item, .oferta-grupo');
  let url = '#';
  
  if (ofertaElement) {
    // Buscar el enlace de la oferta
    const linkElement = ofertaElement.querySelector('a[href]');
    if (linkElement) {
      url = linkElement.href;
    }
  }
  
  // También intentar obtener la URL desde el atributo data-url
  const urlFromData = button.getAttribute('data-url');
  if (urlFromData) {
    url = urlFromData;
  }
  
  const descuentos = button.getAttribute('data-descuentos');
  const ofertaId = button.getAttribute('data-oferta-id');
  
  // Mostrar modal de cupón CholloTienda
  _scctm1(url, descuentos, ofertaId);
}

// Función para manejar clic en botón 3x2
{{-- _h3x21: handle3x2Click - Maneja el clic en botones con oferta 3x2 --}}
function _h3x21(e) {
  e.preventDefault();
  e.stopPropagation();
  
  const button = e.target.closest('[data-3x2="true"]') || e.target;
  const link = button.closest('a');
  let url = link ? link.href : null;
  // Si no hay link, buscar data-url en el botón
  if (!url) {
    url = button.getAttribute('data-url');
  }
  
  if (!url) {
    return;
  }
  
  // Mostrar modal de 3x2
  _s3x2m1(url);
}

// Función para manejar clic en botón 2x1
{{-- _h2x11: handle2x1Click - Maneja el clic en botones con oferta 2x1 --}}
function _h2x11(e) {
  e.preventDefault();
  e.stopPropagation();
  
  const button = e.target.closest('[data-2x1="true"]') || e.target;
  const link = button.closest('a');
  let url = link ? link.href : null;
  // Si no hay link, buscar data-url en el botón
  if (!url) {
    url = button.getAttribute('data-url');
  }
  
  if (!url) {
    return;
  }
  
  // Mostrar modal de 2x1
  _s2x1m1(url);
}

// Función para manejar clic en botón 2a al 50 con cheque
{{-- _h2a50c1: handle2aAl50ChequeClick - Maneja el clic en botones con oferta 2ª al 50% con cheque --}}
function _h2a50c1(e) {
  e.preventDefault();
  e.stopPropagation();
  
  const link = e.target.closest('a');
  const url = link ? link.href : e.target.getAttribute('data-url');
  
  if (!url) {
    return;
  }
  
  _s2a50cm1(url);
}

// Función para manejar clic en botón 2a al 50
{{-- _h2a501: handle2aAl50Click - Maneja el clic en botones con oferta 2ª al 50% --}}
function _h2a501(e) {
  e.preventDefault();
  e.stopPropagation();
  
  const button = e.target.closest('[data-2a-al-50="true"]') || e.target;
  const link = button.closest('a');
  let url = link ? link.href : null;
  // Si no hay link, buscar data-url en el botón
  if (!url) {
    url = button.getAttribute('data-url');
  }
  
  if (!url) {
    return;
  }
  
  // Mostrar modal de 2a al 50
  _s2a50m1(url);
}

// Función para manejar clic en botón 2a al 70
{{-- _h2a701: handle2aAl70Click - Maneja el clic en botones con oferta 2ª al 70% --}}
function _h2a701(e) {
  e.preventDefault();
  e.stopPropagation();
  
  const button = e.target.closest('[data-2a-al-70="true"]') || e.target;
  const link = button.closest('a');
  let url = link ? link.href : null;
  // Si no hay link, buscar data-url en el botón
  if (!url) {
    url = button.getAttribute('data-url');
  }
  
  if (!url) {
    return;
  }
  
  // Mostrar modal de 2a al 70
  _s2a70m1(url);
}

// Función para manejar clic en botón de la mejor oferta en móvil
{{-- _hmomc1: handleMejorOfertaMovilClick - Maneja el clic en las mejores ofertas móviles --}}
function _hmomc1(e) {
  // Si e.preventDefault existe, prevenir comportamiento por defecto
  if (e.preventDefault) {
    e.preventDefault();
    e.stopPropagation();
  }
  
  // Buscar el elemento del botón (puede ser <a> o <span>)
  let link = e.target;
  
  // Si el target es el badge o algo dentro, buscar el elemento del botón
  if (link.tagName === 'SPAN' && link.classList.contains('cupon-badge')) {
    link = link.parentElement.querySelector('a, span[data-cupon], span[data-cupon-aliexpress], span[data-cupon-chollo-tienda], span[data-cupon-chollo-tienda-solo], span[data-3x2], span[data-2x1], span[data-2a-al-50], span[data-2a-al-70]');
  }
  
  // Si no es el botón, buscar el botón más cercano
  if (!link || (!link.hasAttribute('data-cupon') && !link.hasAttribute('data-cupon-aliexpress') && !link.hasAttribute('data-cupon-chollo-tienda') && !link.hasAttribute('data-cupon-chollo-tienda-solo') && !link.hasAttribute('data-3x2') && !link.hasAttribute('data-2x1') && !link.hasAttribute('data-2a-al-50-cheque') && !link.hasAttribute('data-2a-al-50') && !link.hasAttribute('data-2a-al-70'))) {
    const botonContainer = e.target.closest('[id^="mejor-oferta-boton-movil-"]');
    if (botonContainer) {
      link = botonContainer.querySelector('a, span[data-cupon], span[data-cupon-aliexpress], span[data-cupon-chollo-tienda], span[data-cupon-chollo-tienda-solo], span[data-3x2], span[data-2x1], span[data-2a-al-50-cheque], span[data-2a-al-50], span[data-2a-al-70]');
    }
  }
  
  if (!link) {
    return;
  }
  
  // Obtener la URL del href o del atributo data-url
  const url = link.href || link.getAttribute('data-url');
  
  // Verificar qué tipo de descuento tiene
  if (link.hasAttribute('data-cupon-aliexpress')) {
    const descuentos = link.getAttribute('data-descuentos');
    const ofertaId = link.getAttribute('data-oferta-id');
    showCuponAliExpressModal(url, descuentos, ofertaId);
  } else if (link.hasAttribute('data-cupon-chollo-tienda') || link.hasAttribute('data-cupon-chollo-tienda-solo')) {
    const descuentos = link.getAttribute('data-descuentos');
    const ofertaId = link.getAttribute('data-oferta-id');
    _scctm1(url, descuentos, ofertaId);
  } else if (link.hasAttribute('data-cupon')) {
    const valorCupon = link.getAttribute('data-valor-cupon');
    const codigoCupon = link.getAttribute('data-codigo-cupon');
    // Si hay código de cupón, mostrar modal; si no, ir directamente a la tienda
    if (codigoCupon) {
      _scm1(url, valorCupon, codigoCupon);
    } else {
      // Si no hay código, ir directamente a la tienda
      window.open(url, '_blank', 'noopener,noreferrer');
    }
  } else if (link.hasAttribute('data-3x2')) {
    _s3x2m1(url);
  } else if (link.hasAttribute('data-2x1')) {
    _s2x1m1(url);
  } else if (link.hasAttribute('data-2a-al-50-cheque')) {
    _s2a50cm1(url);
  } else if (link.hasAttribute('data-2a-al-50')) {
    _s2a50m1(url);
  } else if (link.hasAttribute('data-2a-al-70')) {
    _s2a70m1(url);
  } else {
    // Oferta normal, ir directamente
    window.open(url, '_blank', 'noopener,noreferrer');
  }
}

// Función para mostrar modal de cupón
{{-- _scm1: showCuponModal - Muestra el modal con información del cupón --}}
function _scm1(url, valorCupon = null, codigoCupon = null) {
  _cm1();
  {{-- x20: Modal overlay de cupones --}}
  const modal = document.getElementById('x20');
  {{-- x26: Botón continuar modal de cupones --}}
  const continueBtn = document.getElementById('x26');
  {{-- x22: Título del modal de cupones --}}
  const title = document.getElementById('x22');
  {{-- x23: Texto del modal de cupones --}}
  const text = document.getElementById('x23');
  {{-- x24: Lista de cupones en el modal --}}
  const cuponesList = document.getElementById('x24');
  
  if (!modal || !continueBtn || !title || !text || !cuponesList) return;
  
  continueBtn.href = url;
  continueBtn.textContent = 'Ir a la tienda';
  continueBtn.onclick = function(e) {
    e.preventDefault();
    window.open(url, '_blank', 'noopener,noreferrer');
    _hcm1();
  };
  
  title.textContent = '¡Aplicar cupón!';
  text.textContent = 'Para conseguir este precio, hay que aplicar este cupón.';
  
  // Generar HTML para el cupón
  let cuponesHtml = '';
  if (codigoCupon && valorCupon) {
    cuponesHtml = _ghc1({
      descuentoTexto: `-${valorCupon}€`,
      codigoCupon,
      mostrarSobrePrecio: false,
      urlRedireccion: url
    });
  } else if (valorCupon) {
    cuponesHtml = `
      <div class="cupon-item flex flex-wrap items-center gap-4 p-3 border border-gray-200 rounded-lg mb-2 bg-gray-50 dark:bg-gray-700">
        <div class="flex items-center gap-2 flex-shrink-0">
          <span class="font-semibold text-gray-800 dark:text-gray-200">-${valorCupon}€</span>
        </div>
        <div class="flex items-center gap-2 flex-shrink-0">
          <span class="text-sm text-gray-700 dark:text-gray-300">Cupón disponible</span>
        </div>
        <button class="copiar-cupon-btn px-3 py-1 bg-blue-500 text-white text-sm rounded hover:bg-blue-600 transition-colors flex items-center gap-1 ml-auto flex-shrink-0">
          Ir a la tienda
        </button>
      </div>
    `;
  }
  
  cuponesList.innerHTML = cuponesHtml;
  
  // Configurar botones de copiar
  if (codigoCupon) {
    _cbc1(cuponesList, url, true);
  } else {
    // Si no hay código, solo configurar el botón de ir a la tienda
    cuponesList.querySelectorAll('.copiar-cupon-btn').forEach(btn => {
      btn.onclick = function(e) {
        e.preventDefault();
        window.open(url, '_blank', 'noopener,noreferrer');
        _hcm1();
      };
    });
  }
  
  modal.classList.add('show');
  document.body.style.overflow = 'hidden';
}



// Función para parsear cupones de CholloTienda
{{-- _pcct1: parsearCuponesCholloTienda - Parsea los cupones de CholloTienda --}}
function _pcct1(descuentos) {
  if (!descuentos) {
    return [];
  }
  
  // Verificar si es el formato nuevo "1 Solo cupón"
  if (descuentos.startsWith('CholloTienda1SoloCuponQueAplicaDescuento;')) {
    const partes = descuentos.split(';');
    const cupon = {};
    
    // Buscar los valores en el string
    for (let i = 0; i < partes.length; i++) {
      if (partes[i] === 'descuento' && i + 1 < partes.length) {
        cupon.descuento = parseFloat(partes[i + 1]);
      }
      if (partes[i] === 'tipo_descuento' && i + 1 < partes.length) {
        cupon.tipo_descuento = partes[i + 1];
      }
      if (partes[i] === 'cupon' && i + 1 < partes.length) {
        cupon.cupon = partes[i + 1];
      }
    }
    
    // Si se encontró el cupón, devolverlo en un array
    if (cupon.descuento !== undefined && cupon.cupon) {
      return [cupon];
    }
    
    return [];
  }
  
  // Formato original CholloTienda;
  if (!descuentos.startsWith('CholloTienda;')) {
    return [];
  }
  
  const partes = descuentos.split(';');
  const cupones = [];
  
  // Buscar patrones de cupones: descuento;X;sobrePrecioTotal;Y;cupon;Z
  // El formato es: CholloTienda;tienda_id;{id};descuento;{valor};sobrePrecioTotal;{minimo};cupon;{codigo};...
  for (let i = 0; i < partes.length; i++) {
    if (partes[i] === 'descuento' && i + 4 < partes.length) {
      if (partes[i + 2] === 'sobrePrecioTotal' && partes[i + 4] === 'cupon') {
        cupones.push({
          descuento: parseFloat(partes[i + 1]),
          sobrePrecioTotal: parseFloat(partes[i + 3]),
          cupon: partes[i + 5]
        });
      }
    }
  }
  
  return cupones;
}

// Función para mostrar modal de cupón CholloTienda
{{-- _scctm1: showCuponCholloTiendaModal - Muestra el modal de cupones de CholloTienda --}}
function _scctm1(url, descuentos, ofertaId) {
  _cm1();
  {{-- x20: Modal overlay de cupones --}}
  const modal = document.getElementById('x20');
  {{-- x26: Botón continuar modal de cupones --}}
  const continueBtn = document.getElementById('x26');
  {{-- x22: Título del modal de cupones --}}
  const title = document.getElementById('x22');
  {{-- x23: Texto del modal de cupones --}}
  const text = document.getElementById('x23');
  {{-- x24: Lista de cupones en el modal --}}
  const cuponesList = document.getElementById('x24');
  
  if (!modal || !continueBtn || !title || !text || !cuponesList) return;
  
  const urlRedireccion = `/redirigir/${ofertaId}${window.location.search}`;
  continueBtn.href = urlRedireccion;
  continueBtn.textContent = 'Ir a la tienda';
  continueBtn.onclick = function(e) {
    e.preventDefault();
    window.open(urlRedireccion, '_blank', 'noopener,noreferrer');
    _hcm1();
  };
  
  const cuponesInfo = _pcct1(descuentos);
  const esSoloCupon = descuentos && descuentos.startsWith('CholloTienda1SoloCuponQueAplicaDescuento;');
  
  title.textContent = '¡Cupones disponibles!';
  text.innerHTML = esSoloCupon 
    ? 'Para conseguir este precio, hay que aplicar este cupón.<br>'
    : 'Los cupones son validos para la gran mayoría de productos, pero no para todos. Puedes comprobarlo en la tienda<br>';
  
  // Generar HTML para la lista de cupones
  let cuponesHtml = '';
  cuponesInfo.forEach(cupon => {
    const descuentoTexto = (esSoloCupon && cupon.tipo_descuento === 'porcentaje') 
      ? `-${cupon.descuento}%` 
      : `-${cupon.descuento}€`;
    
    cuponesHtml += generarHTMLCupon({
      descuentoTexto,
      codigoCupon: cupon.cupon,
      mostrarSobrePrecio: !esSoloCupon && cupon.sobrePrecioTotal !== undefined,
      sobrePrecioTotal: cupon.sobrePrecioTotal,
      urlRedireccion
    });
  });
  
  cuponesList.innerHTML = cuponesHtml;
  _cbc1(cuponesList, urlRedireccion);
  
  modal.classList.add('show');
  document.body.style.overflow = 'hidden';
}

// Función para copiar al portapapeles
{{-- _cap1: copiarAlPortapapeles - Copia texto al portapapeles --}}
function _cap1(texto) {
  if (navigator.clipboard && window.isSecureContext) {
    navigator.clipboard.writeText(texto).then(() => {
    }).catch(err => {
      _fctc1(texto);
    });
  } else {
    fallbackCopyTextToClipboard(texto);
  }
}

{{-- _fctc1: fallbackCopyTextToClipboard - Método alternativo para copiar texto al portapapeles --}}
function _fctc1(texto) {
  const textArea = document.createElement("textarea");
  textArea.value = texto;
  textArea.style.position = "fixed";
  textArea.style.left = "-999999px";
  textArea.style.top = "-999999px";
  document.body.appendChild(textArea);
  textArea.focus();
  textArea.select();
  
  try {
    document.execCommand('copy');
  } catch (err) {
  }
  
  document.body.removeChild(textArea);
}

// ==================== FUNCIONES GENÉRICAS PARA MODALES ====================

// Configuración de modales simples
{{-- v12: modalConfigs - Configuración de los modales de ofertas especiales (3x2, 2x1, etc.) --}}
const v12 = {
  '3x2': {
    title: '¡Oferta 3x2!',
    text: 'Esta oferta tiene una promoción 3x2. Para obtener este precio, debes comprar 3 unidades y en el carrito se te aplicará automáticamente el descuento.'
  },
  '2x1': {
    title: '¡Oferta 2x1 Acumula!',
    text: 'Esta oferta tiene descuento 2x1 Acumula! Al comprar dos productos, el precio del producto de menor valor, se añadirá automáticamente a tu cheque ahorro de Carrefour.'
  },
  '2a-al-50-cheque': {
    title: '¡2ª al 50% en cheque!',
    text: 'Esta oferta tiene promoción 2ª unidad al 50% . Compra 2 unidades y recibirás el 50%  de la segunda unidad en un cheque para próximas compras en Carrefour.'
  },
  '2a-al-50': {
    title: '¡Oferta 2ª al 50%!',
    text: 'Esta oferta tiene descuento 2ª al 50%. Para obtener este precio, debes comprar 2 unidades y en el carrito se te aplicará automáticamente el descuento o puede que tengas que marcar alguna casilla en la vista del producto.'
  },
  '2a-al-70': {
    title: '¡Oferta 2ª al 70%!',
    text: 'Esta oferta tiene descuento 2ª al 70%. Para obtener este precio, debes comprar 2 unidades y en el carrito se te aplicará automáticamente el descuento o puede que tengas que marcar alguna casilla en la vista del producto.'
  }
};

// Función genérica para modales simples
{{-- _ssm1: showSimpleModal - Muestra un modal simple con configuración personalizada --}}
function _ssm1(url, config) {
  _cm1();
  {{-- x20: Modal overlay de cupones --}}
  const modal = document.getElementById('x20');
  {{-- x26: Botón continuar modal de cupones --}}
  const continueBtn = document.getElementById('x26');
  {{-- x22: Título del modal de cupones --}}
  const title = document.getElementById('x22');
  {{-- x23: Texto del modal de cupones --}}
  const text = document.getElementById('x23');
  {{-- x24: Lista de cupones en el modal --}}
  const cuponesList = document.getElementById('x24');
  
  if (!modal || !continueBtn || !title || !text) return;
  
  // Ocultar lista de cupones si existe
  if (cuponesList) cuponesList.innerHTML = '';
  
  continueBtn.href = url;
  continueBtn.textContent = config.buttonText || 'Ir a la tienda';
  continueBtn.onclick = function(e) {
    e.preventDefault();
    window.open(url, '_blank', 'noopener,noreferrer');
    _hcm1();
  };
  
  title.textContent = config.title;
  text.textContent = config.text;
  
  modal.classList.add('show');
  document.body.style.overflow = 'hidden';
}

// Función para generar HTML de un cupón
{{-- _ghc1: generarHTMLCupon - Genera el HTML para mostrar un cupón en el modal --}}
function _ghc1(cuponData) {
  const { descuentoTexto, codigoCupon, mostrarSobrePrecio, sobrePrecioTotal, urlRedireccion } = cuponData;
  
  return `
    <div class="cupon-item flex items-center gap-2 p-3 border border-gray-200 rounded-lg mb-2 bg-gray-50 dark:bg-gray-700 overflow-hidden">
      <div class="flex items-center gap-2 flex-shrink-0">
        <span class="font-semibold text-gray-800 dark:text-gray-200 whitespace-nowrap">${descuentoTexto}</span>
      </div>
      ${mostrarSobrePrecio && sobrePrecioTotal ? `
      <div class="flex items-center gap-2 flex-shrink-0">
        <span class="text-sm font-medium text-gray-800 dark:text-gray-200 whitespace-nowrap">+${sobrePrecioTotal}€</span>
      </div>
      ` : ''}
      <div class="flex items-center gap-2 min-w-0 flex-1">
        <svg class="w-4 h-4 text-orange-600 dark:text-orange-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
        </svg>
        <span class="text-sm text-gray-700 dark:text-gray-300 font-mono bg-gray-200 dark:bg-gray-600 px-2 py-1 rounded truncate">${codigoCupon}</span>
      </div>
      <button class="copiar-cupon-btn px-2 py-1 bg-blue-500 text-white text-sm rounded hover:bg-blue-600 transition-colors flex items-center gap-1 flex-shrink-0 whitespace-nowrap" 
              data-cupon="${codigoCupon}" data-url="${urlRedireccion || ''}">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
        </svg>
        <span>Copiar + Ir</span>
      </button>
    </div>
  `;
}

// Función para configurar botones de copiar cupones
{{-- _cbc1: configurarBotonesCopiar - Configura los event listeners de los botones copiar cupón --}}
function _cbc1(cuponesList, urlDefault, cerrarModal = false) {
  cuponesList.querySelectorAll('.copiar-cupon-btn').forEach(btn => {
    btn.onclick = function(e) {
      e.preventDefault();
      const codigoCupon = this.getAttribute('data-cupon');
      const url = this.getAttribute('data-url') || urlDefault;
      
      if (codigoCupon) {
        _cap1(codigoCupon);
        
        const textoOriginal = this.innerHTML;
        this.innerHTML = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg><span>¡Copiado!</span>';
        this.style.background = '#10b981';
        
        setTimeout(() => {
          window.open(url, '_blank', 'noopener,noreferrer');
        }, 500);
        
        setTimeout(() => {
          this.innerHTML = textoOriginal;
          this.style.background = '';
          if (cerrarModal) {
            _hcm1();
          }
        }, cerrarModal ? 2000 : 1000);
      } else {
        window.open(url, '_blank', 'noopener,noreferrer');
        _hcm1();
      }
    };
  });
}

// Funciones de modal simplificadas
{{-- _s3x2m1: show3x2Modal - Muestra el modal de oferta 3x2 --}}
function _s3x2m1(url) {
  _ssm1(url, v12['3x2']);
}

{{-- _s2x1m1: show2x1Modal - Muestra el modal de oferta 2x1 --}}
function _s2x1m1(url) {
  _ssm1(url, v12['2x1']);
}

{{-- _s2a50cm1: show2aAl50ChequeModal - Muestra el modal de oferta 2ª al 50% con cheque --}}
function _s2a50cm1(url) {
  _ssm1(url, v12['2a-al-50-cheque']);
}

{{-- _s2a50m1: show2aAl50Modal - Muestra el modal de oferta 2ª al 50% --}}
function _s2a50m1(url) {
  _ssm1(url, v12['2a-al-50']);
}

{{-- _s2a70m1: show2aAl70Modal - Muestra el modal de oferta 2ª al 70% --}}
function _s2a70m1(url) {
  _ssm1(url, v12['2a-al-70']);
}

// Función para ocultar modal de cupón
{{-- _hcm1: hideCuponModal - Oculta el modal de cupones --}}
function _hcm1() {
  {{-- x20: Modal overlay de cupones --}}
  const modal = document.getElementById('x20');
  
  if (modal) {
    modal.classList.remove('show');
    
    // Restaurar scroll del body
    document.body.style.overflow = '';
  }
}


// Función para configurar el modal de cupón
{{-- _scm2: setupCuponModal - Configura el modal de cupones (event listeners) --}}
function _scm2() {
  {{-- x20: Modal overlay de cupones --}}
  const modal = document.getElementById('x20');
  {{-- x25: Botón cancelar modal de cupones --}}
  const cancelBtn = document.getElementById('x25');
  {{-- x26: Botón continuar modal de cupones --}}
  const continueBtn = document.getElementById('x26');
  {{-- x21: Botón cerrar modal de cupones --}}
  const closeBtn = document.getElementById('x21');
  
  if (modal && cancelBtn) {
    // Cerrar modal al hacer clic en cancelar
    cancelBtn.addEventListener('click', _hcm1);
  }
  
  if (closeBtn) {
    // Cerrar modal al hacer clic en el botón X
    closeBtn.addEventListener('click', _hcm1);
  }
  
  if (modal) {
    // Cerrar modal al hacer clic en el overlay (fuera del modal)
    modal.addEventListener('click', function(e) {
      if (e.target === modal) {
        _hcm1();
      }
    });
    
    // Cerrar modal con tecla Escape
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape' && modal.classList.contains('show')) {
        _hcm1();
      }
    });
  }
  
  if (continueBtn) {
    // Cerrar modal al hacer clic en continuar
    continueBtn.addEventListener('click', function() {
      _hcm1();
    });
  }
}

// ==================== MODAL DE IMÁGENES ====================
{{-- v6: imagesBaseUrl - URL base para las imágenes --}}
const v6 = '{{ asset('images/') }}';
const vanIconUrlModal = '{{ asset('images/van.png') }}';
{{-- v1: imagenesProducto - Array con todas las imágenes del producto --}}
let v1 = [];
{{-- v17: imagenActualIndex - Índice de la imagen actual en el modal --}}
let v17 = 0;
{{-- v2: imagenesGrandes - Array con las imágenes grandes del producto --}}
let v2 = [];
{{-- v3: imagenesPequenas - Array con las imágenes pequeñas del producto --}}
let v3 = [];
{{-- v18: carruselMovilStartIndex - Índice inicial del carrusel móvil --}}
let v18 = 0;
{{-- v4: imagenPrincipalIndex - Índice de la imagen principal mostrada en la página --}}
let v4 = 0;
{{-- v5: modalAbiertoDesdeLongPress - Flag para saber si el modal se abrió desde long press --}}
let v5 = false;

{{-- _am1: abrirModalImagenes - Abre el modal de imágenes del producto --}}
function _am1() {
  if (v1.length === 0) return;
  
  // Usar el índice de la imagen principal actual
  v17 = v4 || 0;
  // Inicializar el carrusel móvil
  const maxVisible = 7;
  v18 = 0;
  if (v18 + maxVisible > v1.length) {
    v18 = Math.max(0, v1.length - maxVisible);
  }
  
  {{-- x18: Modal de imágenes --}}
  const modal = document.getElementById('x18');
  if (modal) {
    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
    _am2();
  }
}

// Variables para almacenar imágenes originales y actuales
{{-- v14: imagenesOriginalesGrandes - Backup de las imágenes grandes originales --}}
let v14 = [];
{{-- v15: imagenesOriginalesPequenas - Backup de las imágenes pequeñas originales --}}
let v15 = [];
{{-- v16: imagenesOriginalesProducto - Backup de todas las imágenes originales --}}
let v16 = [];

// Inicializar imágenes del producto
document.addEventListener('DOMContentLoaded', function() {
  const imagenGrandeArray = @json($producto->imagen_grande ?? []);
  const imagenPequenaArray = @json($producto->imagen_pequena ?? []);
  
  v2 = Array.isArray(imagenGrandeArray) ? imagenGrandeArray : [];
  v3 = Array.isArray(imagenPequenaArray) ? imagenPequenaArray : [];
  
  // Guardar imágenes originales
  v14 = [...v2];
  v15 = [...v3];
  
  // Usar imágenes pequeñas si no hay grandes, y viceversa
  v1 = v2.length > 0 ? v2 : v3;
  v16 = [...v1];
  
  // Inicializar índice de imagen principal
  v4 = 0;
  
  // Renderizar carruseles de miniaturas
  _rmp1();
  
  // Configurar eventos para cambiar imagen
  _ceip1();
  
  // Si hay filtros iniciales desde la URL, buscar si alguno tiene imágenes y cambiar la imagen del producto
  // Esto debe ejecutarse después de que se inicialicen las imágenes del producto
  if (typeof window.v13 !== 'undefined' && window.v13 && Object.keys(window.v13).length > 0) {
    // Buscar la última sublínea seleccionada con imágenes
    let ultimaSublineaConImagenes = null;
    const botones = document.querySelectorAll('.filtro-sublinea-btn');
    
    // Recorrer en orden inverso para encontrar la última seleccionada
    const botonesArray = Array.from(botones).reverse();
    
    botonesArray.forEach(boton => {
      const lineaId = boton.dataset.lineaId;
      const sublineaId = boton.dataset.sublineaId;
      const imagenesSublinea = boton.dataset.imagenes ? JSON.parse(boton.dataset.imagenes) : null;
      
      // Verificar si esta sublínea está seleccionada y tiene imágenes
      if (imagenesSublinea && imagenesSublinea.length > 0 && 
          window.v13[lineaId] && 
          window.v13[lineaId].includes(String(sublineaId))) {
        // Si encontramos una, usarla (como estamos en orden inverso, será la última)
        if (!ultimaSublineaConImagenes) {
          ultimaSublineaConImagenes = imagenesSublinea;
        }
      }
    });
    
    // Si hay una sublínea con imágenes seleccionada, cambiar a sus imágenes
    if (ultimaSublineaConImagenes && typeof cambiarImagenesProducto === 'function') {
      _cip1(ultimaSublineaConImagenes);
    }
  }
  
  // Cerrar modal al hacer clic fuera
  {{-- x18: Modal de imágenes --}}
  const modal = document.getElementById('x18');
  if (modal) {
    modal.addEventListener('click', function(e) {
      // Cerrar si el click fue directamente en el modal o en el div intermedio
      // (pero no en el contenedor blanco del modal)
      const modalContent = modal.querySelector('.bg-white.rounded-lg.shadow-2xl');
      if (!modalContent || !modalContent.contains(e.target)) {
        _cm1();
      }
    });
    
    // Cerrar con tecla Escape
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
        _cm1();
      }
    });
  }
});

// ==================== CARRUSEL DE MINIATURAS EN LA PÁGINA ====================

// Función para renderizar miniaturas en la página (no en el modal)
{{-- _rmp1: renderizarMiniaturasPagina - Renderiza todas las miniaturas del producto en la página --}}
function _rmp1() {
  if (v1.length === 0) return;
  
  // Renderizar miniaturas desktop
  _rmd1();
  
  // Renderizar miniaturas móvil
  _rmm1();
  
  // Actualizar imagen principal
  _aip1();
}

// Renderizar miniaturas desktop (hasta 3 + botón + = 4 cuadrados)
{{-- _rmd1: renderizarMiniaturasDesktop - Renderiza las miniaturas en la vista desktop --}}
function _rmd1() {
  const container = document.getElementById('carrusel-miniaturas-desktop');
  if (!container) return;
  
  container.innerHTML = '';
  
  const maxMiniaturas = 3;
  const numMiniaturas = Math.min(v1.length, maxMiniaturas);
  const hayMasImagenes = v1.length > maxMiniaturas;
  
  // Renderizar miniaturas
  for (let i = 0; i < numMiniaturas; i++) {
    const imgPath = v3[i] || v2[i] || '';
    const isActive = i === v4;
    const miniatura = document.createElement('div');
    miniatura.className = `miniatura-pagina-desktop ${isActive ? 'activa' : ''}`;
    miniatura.style.width = '60px';
    miniatura.style.height = '60px';
    miniatura.onmouseenter = () => _cip2(i);
    miniatura.innerHTML = `<img src="${v6}/${imgPath}" alt="Miniatura ${i + 1}">`;
    container.appendChild(miniatura);
  }
  
  // Añadir botón + como 4º cuadrado (siempre visible)
  const botonMas = document.createElement('div');
  botonMas.className = 'miniatura-pagina-desktop boton-mas';
  botonMas.style.width = '60px';
  botonMas.style.height = '60px';
    botonMas.onclick = _am1;
  botonMas.innerHTML = `
    <div class="w-full h-full flex items-center justify-center bg-gray-100 rounded border-2 border-dashed border-gray-300 cursor-pointer hover:bg-gray-200 transition-colors">
      <svg class="w-6 h-6 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
      </svg>
    </div>
  `;
  container.appendChild(botonMas);
}

// Renderizar miniaturas móvil (3 a la izquierda, 2-3 a la derecha)
{{-- _rmm1: renderizarMiniaturasMovil - Renderiza las miniaturas en la vista móvil --}}
function _rmm1() {
  const containerIzq = document.getElementById('carrusel-miniaturas-movil-izq');
  const containerDer = document.getElementById('carrusel-miniaturas-movil-der');
  if (!containerIzq || !containerDer) return;
  
  containerIzq.innerHTML = '';
  containerDer.innerHTML = '';
  
  const hayMasImagenes = v1.length > 5;
  
  // Miniaturas izquierda: imágenes 0, 1, 2 (las 3 primeras)
  for (let i = 0; i < Math.min(3, v1.length); i++) {
    const imgPath = v3[i] || v2[i] || '';
    const isActive = i === v4;
    const miniatura = document.createElement('div');
    miniatura.className = `miniatura-pagina-movil ${isActive ? 'activa' : ''}`;
    miniatura.style.width = '48px';
    miniatura.style.height = '48px';
    miniatura.onclick = () => _cip2(i);
    miniatura.innerHTML = `<img src="${v6}/${imgPath}" alt="Miniatura ${i + 1}">`;
    containerIzq.appendChild(miniatura);
  }
  
  // Rellenar con espacios vacíos si hay menos de 3
  while (containerIzq.children.length < 3) {
    const espacio = document.createElement('div');
    espacio.style.width = '48px';
    espacio.style.height = '48px';
    espacio.style.flexShrink = '0';
    containerIzq.appendChild(espacio);
  }
  
  // Miniaturas derecha: imágenes 3, 4 (si existen) + botón + si hay más de 5
  // Imagen 3 (índice 3)
  if (v1.length > 3) {
    const i = 3;
    const imgPath = v3[i] || v2[i] || '';
    const isActive = i === v4;
    const miniatura = document.createElement('div');
    miniatura.className = `miniatura-pagina-movil ${isActive ? 'activa' : ''}`;
    miniatura.style.width = '48px';
    miniatura.style.height = '48px';
    miniatura.onclick = () => _cip2(i);
    miniatura.innerHTML = `<img src="${v6}/${imgPath}" alt="Miniatura ${i + 1}">`;
    containerDer.appendChild(miniatura);
  }
  
  // Imagen 4 (índice 4)
  if (v1.length > 4) {
    const i = 4;
    const imgPath = v3[i] || v2[i] || '';
    const isActive = i === v4;
    const miniatura = document.createElement('div');
    miniatura.className = `miniatura-pagina-movil ${isActive ? 'activa' : ''}`;
    miniatura.style.width = '48px';
    miniatura.style.height = '48px';
    miniatura.onclick = () => _cip2(i);
    miniatura.innerHTML = `<img src="${v6}/${imgPath}" alt="Miniatura ${i + 1}">`;
    containerDer.appendChild(miniatura);
  }
  
  // Botón + como 6º cuadrado (3er de la derecha) si hay más de 5 imágenes
  if (hayMasImagenes) {
    const botonMas = document.createElement('div');
    botonMas.className = 'miniatura-pagina-movil boton-mas';
    botonMas.style.width = '48px';
    botonMas.style.height = '48px';
    botonMas.onclick = _am1;
    botonMas.innerHTML = `
      <div class="w-full h-full flex items-center justify-center bg-gray-100 rounded border-2 border-dashed border-gray-300 cursor-pointer hover:bg-gray-200 transition-colors">
        <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
        </svg>
      </div>
    `;
    containerDer.appendChild(botonMas);
  }
  
  // Rellenar con espacios vacíos si hay menos de 3
  while (containerDer.children.length < 3) {
    const espacio = document.createElement('div');
    espacio.style.width = '48px';
    espacio.style.height = '48px';
    espacio.style.flexShrink = '0';
    containerDer.appendChild(espacio);
  }
}

// Función para cambiar las imágenes del producto por las de una sublínea
{{-- _cip1: cambiarImagenesProducto - Cambia las imágenes del producto por las de una sublínea --}}
function _cip1(imagenesSublinea) {
  if (!imagenesSublinea || imagenesSublinea.length === 0) return;
  
  // Las imágenes de la sublínea son las imágenes grandes
  // Intentar usar thumbnails para las pequeñas si existen
  const nuevasImagenesGrandes = [];
  const nuevasImagenesPequenas = [];
  
  imagenesSublinea.forEach(imagen => {
    // Las imágenes de la sublínea se usan como grandes
    nuevasImagenesGrandes.push(imagen);
    
    // Intentar encontrar thumbnail para la versión pequeña
    const thumbnail = imagen.replace(/\.(jpg|jpeg|png|gif|webp)$/i, '-thumbnail.webp');
    // Usar thumbnail si existe, sino usar la imagen original
    nuevasImagenesPequenas.push(thumbnail);
  });
  
  // Actualizar arrays de imágenes
  v2 = nuevasImagenesGrandes;
  v3 = nuevasImagenesPequenas;
  v1 = v2.length > 0 ? v2 : v3;
  
  // Resetear índice a 0
  v4 = 0;
  
  // Actualizar imagen principal y miniaturas
  _aip1();
  _rmp1();
  
  // Si el modal está abierto, actualizarlo también
  {{-- x18: Modal de imágenes --}}
  const modalAbierto = document.getElementById('x18');
  if (modalAbierto && !modalAbierto.classList.contains('hidden')) {
    _am2();
  }
}

// Función para restaurar las imágenes originales del producto o cambiar a otra sublínea seleccionada
{{-- _rio1: restaurarImagenesOriginales - Restaura las imágenes originales del producto --}}
function _rio1() {
  // Buscar la última sublínea seleccionada con imágenes (para priorizar la más reciente)
  let ultimaSublineaConImagenes = null;
  const botones = document.querySelectorAll('.filtro-sublinea-btn');
  
  // Recorrer en orden inverso para encontrar la última seleccionada
  const botonesArray = Array.from(botones).reverse();
  
  botonesArray.forEach(boton => {
    const lineaId = boton.dataset.lineaId;
    const sublineaId = boton.dataset.sublineaId;
    const imagenesSublinea = boton.dataset.imagenes ? JSON.parse(boton.dataset.imagenes) : null;
    
    // Verificar si esta sublínea está seleccionada y tiene imágenes
    if (imagenesSublinea && imagenesSublinea.length > 0 && 
        window.v13 && window.v13[lineaId] && 
        window.v13[lineaId].includes(String(sublineaId))) {
      // Si encontramos una, usarla (como estamos en orden inverso, será la última)
      if (!ultimaSublineaConImagenes) {
        ultimaSublineaConImagenes = imagenesSublinea;
      }
    }
  });
  
  // Si hay una sublínea con imágenes seleccionada, cambiar a sus imágenes
  if (ultimaSublineaConImagenes) {
    _cip1(ultimaSublineaConImagenes);
  } else {
    // Si no hay ninguna sublínea con imágenes seleccionada, restaurar originales
    v2 = [...v14];
    v3 = [...v15];
    v1 = [...v16];
    
    // Resetear índice a 0
    v4 = 0;
    
    // Actualizar imagen principal y miniaturas
    _aip1();
    _rmp1();
  }
}

// Cambiar imagen principal
{{-- _cip2: cambiarImagenPrincipal - Cambia la imagen principal del producto --}}
function _cip2(index) {
  if (index < 0 || index >= v1.length) return;
  
  v4 = index;
  _aip1();
  _rmp1(); // Re-renderizar para actualizar bordes
}

// Actualizar imagen principal
{{-- _aip1: actualizarImagenPrincipal - Actualiza la imagen principal en desktop y móvil --}}
function _aip1() {
  const imgDesktop = document.getElementById('imagen-producto-desktop');
  const imgMovil = document.getElementById('imagen-producto-movil');
  
  if (v1.length === 0) return;
  
  const imgPath = v3[v4] || v2[v4] || v1[v4] || '';
  const imgUrl = imgPath ? `${v6}/${imgPath}` : '';
  
  if (imgDesktop && imgUrl) {
    imgDesktop.src = imgUrl;
  }
  if (imgMovil && imgUrl) {
    imgMovil.src = imgUrl;
  }
}

// Configurar eventos para imagen principal
{{-- _ceip1: configurarEventosImagenPrincipal - Configura los eventos de la imagen principal --}}
function _ceip1() {
  const imgMovil = document.getElementById('imagen-producto-movil');
  const imgDesktop = document.getElementById('imagen-producto-desktop');
  
  // Configurar click para abrir modal
  if (imgMovil) {
    imgMovil.onclick = function() {
      if (typeof _am1 === 'function') {
        _am1();
      }
    };
    // Configurar swipe para móvil
    _csip1(imgMovil);
  }
  
  if (imgDesktop) {
    imgDesktop.onclick = function() {
      if (typeof _am1 === 'function') {
        _am1();
      }
    };
  }
}

// Configurar swipe para imagen principal móvil
{{-- _csip1: configurarSwipeImagenPrincipal - Configura el gesto swipe para la imagen principal móvil --}}
function _csip1(elemento) {
  let touchStartX = 0;
  let touchEndX = 0;
  const minSwipeDistance = 50;
  
  elemento.addEventListener('touchstart', function(e) {
    touchStartX = e.touches[0].clientX;
  }, { passive: true });
  
  elemento.addEventListener('touchend', function(e) {
    touchEndX = e.changedTouches[0].clientX;
    const swipeDistance = touchEndX - touchStartX;
    
    // Swipe hacia la izquierda (siguiente imagen)
    if (swipeDistance < -minSwipeDistance && v4 < v1.length - 1) {
      _cip2(v4 + 1);
    }
    // Swipe hacia la derecha (imagen anterior)
    else if (swipeDistance > minSwipeDistance && v4 > 0) {
      _cip2(v4 - 1);
    }
  }, { passive: true });
}

{{-- _cm1: cerrarModalImagenes - Cierra el modal de imágenes del producto --}}
function _cm1() {
  {{-- x18: Modal de imágenes --}}
  const modal = document.getElementById('x18');
  if (modal) {
    modal.classList.add('hidden');
    document.body.style.overflow = '';
    
    // Si el modal se abrió desde long press, restaurar imágenes originales
    if (v5) {
      _rio1();
      v5 = false;
    }
  }
}

{{-- _ci1: cambiarImagen - Cambia la imagen principal en el modal --}}
function _ci1(direccion) {
  v17 += direccion;
  
  if (v17 < 0) v17 = 0;
  if (v17 >= v1.length) v17 = v1.length - 1;
  
  _am2();
  _cms1();
}

{{-- _si1: seleccionarImagen - Selecciona una imagen en el modal --}}
function _si1(index) {
  v17 = index;
  
  // Si estamos en móvil, ajustar el carrusel para centrar la imagen seleccionada
  const maxVisible = 7;
  let newStartIndex = Math.max(0, v17 - 3);
  if (newStartIndex + maxVisible > v1.length) {
    newStartIndex = Math.max(0, v1.length - maxVisible);
  }
  v18 = newStartIndex;
  
  _am2();
  _cms1();
}

{{-- _am2: actualizarModal - Actualiza el contenido del modal de imágenes --}}
function _am2() {
  if (v1.length === 0) return;
  
  const imagenGrande = document.getElementById('imagen-grande-modal');
  const btnFlechaIzq = document.getElementById('btn-flecha-izq');
  const btnFlechaDer = document.getElementById('btn-flecha-der');
  
  if (imagenGrande) {
    // Usar imagen_grande para la imagen grande del modal
    const imagenActual = v2[v17] || v3[v17] || '';
    imagenGrande.src = imagenActual ? `${v6}/${imagenActual}` : '';
    imagenGrande.alt = 'Imagen ' + (v17 + 1) + ' del producto';
    
    // Configurar swipe para móvil (solo una vez)
    if (!imagenGrande.hasAttribute('data-swipe-configured')) {
      _csm1(imagenGrande);
      imagenGrande.setAttribute('data-swipe-configured', 'true');
    }
  }
  
  // Actualizar estado de flechas
  if (btnFlechaIzq) {
    btnFlechaIzq.disabled = v17 === 0;
    btnFlechaIzq.style.opacity = v17 === 0 ? '0.3' : '0.5';
  }
  if (btnFlechaDer) {
    btnFlechaDer.disabled = v17 === v1.length - 1;
    btnFlechaDer.style.opacity = v17 === v1.length - 1 ? '0.3' : '0.5';
  }
  
  // Renderizar miniaturas
  _rm1();
  
  // Renderizar oferta en desktop
  _rod1();
}

// Función para configurar swipe en móvil
{{-- _csm1: configurarSwipeMovil - Configura el gesto swipe para el modal móvil --}}
function _csm1(elemento) {
  let touchStartX = 0;
  let touchEndX = 0;
  const minSwipeDistance = 50; // Distancia mínima en píxeles para considerar un swipe
  
  elemento.addEventListener('touchstart', function(e) {
    touchStartX = e.changedTouches[0].screenX;
  }, { passive: true });
  
  elemento.addEventListener('touchend', function(e) {
    touchEndX = e.changedTouches[0].screenX;
    handleSwipe();
  }, { passive: true });
  
  function handleSwipe() {
    const swipeDistance = touchEndX - touchStartX;
    
    // Swipe hacia la izquierda (siguiente imagen)
    if (swipeDistance < -minSwipeDistance && v17 < v1.length - 1) {
      _ci1(1);
    }
    // Swipe hacia la derecha (imagen anterior)
    else if (swipeDistance > minSwipeDistance && v17 > 0) {
      _ci1(-1);
    }
  }
}

// Función para configurar swipe en el carrusel móvil
{{-- _cscm1: configurarSwipeCarruselMovil - Configura el gesto swipe para el carrusel móvil --}}
function _cscm1(elemento) {
  let touchStartX = 0;
  let touchStartY = 0;
  let isScrolling = false;
  const minSwipeDistance = 30; // Distancia mínima en píxeles para considerar un swipe
  
  elemento.addEventListener('touchstart', function(e) {
    touchStartX = e.touches[0].clientX;
    touchStartY = e.touches[0].clientY;
    isScrolling = false;
  }, { passive: true });
  
  elemento.addEventListener('touchmove', function(e) {
    if (!isScrolling) {
      const deltaX = Math.abs(e.touches[0].clientX - touchStartX);
      const deltaY = Math.abs(e.touches[0].clientY - touchStartY);
      // Si el movimiento horizontal es mayor que el vertical, es un swipe horizontal
      if (deltaX > deltaY) {
        isScrolling = true;
      }
    }
    
    if (isScrolling) {
      // Permitir el scroll nativo del contenedor
      const currentScrollLeft = elemento.scrollLeft;
      const touchX = e.touches[0].clientX;
      const deltaX = touchX - touchStartX;
      
      // Actualizar el scroll del contenedor
      elemento.scrollLeft = currentScrollLeft - deltaX;
      touchStartX = touchX;
    }
  }, { passive: true });
  
  elemento.addEventListener('touchend', function(e) {
    isScrolling = false;
  }, { passive: true });
}

{{-- _rm1: renderizarMiniaturas - Renderiza las miniaturas en el modal --}}
function _rm1() {
  if (v1.length === 0) return;
  
  const miniaturasDesktop = document.getElementById('miniaturas-container-desktop');
  const miniaturasMovil = document.getElementById('miniaturas-container-movil');
  
  // Limpiar contenedores
  if (miniaturasDesktop) miniaturasDesktop.innerHTML = '';
  if (miniaturasMovil) miniaturasMovil.innerHTML = '';
  
  // Configurar swipe para el carrusel móvil (solo una vez)
  if (miniaturasMovil && !miniaturasMovil.hasAttribute('data-swipe-configured')) {
    _cscm1(miniaturasMovil);
    miniaturasMovil.setAttribute('data-swipe-configured', 'true');
  }
  
  // Renderizar miniaturas desktop (vertical - todas las imágenes)
  if (miniaturasDesktop) {
    for (let i = 0; i < v1.length; i++) {
      const imgPath = v3[i] || v2[i] || '';
      const isActive = i === v17;
      const miniatura = document.createElement('div');
      miniatura.className = `miniatura-imagen ${isActive ? 'activa' : ''}`;
      miniatura.style.width = '96px';
      miniatura.style.height = '96px';
      miniatura.onclick = () => _si1(i);
      // Cambiar imagen grande al pasar el ratón (solo en desktop)
      miniatura.onmouseenter = () => {
        if (i !== v17) {
          v17 = i;
          _am2();
        }
      };
      miniatura.innerHTML = `<img src="${v6}/${imgPath}" alt="Miniatura ${i + 1}">`;
      miniaturasDesktop.appendChild(miniatura);
    }
  }
  
  // Renderizar miniaturas móvil (horizontal, solo las visibles usando carruselMovilStartIndex)
  if (miniaturasMovil) {
    const maxVisible = 7;
    let movilStartIndex = v18;
    if (movilStartIndex + maxVisible > v1.length) {
      movilStartIndex = Math.max(0, v1.length - maxVisible);
    }
    const movilEndIndex = Math.min(movilStartIndex + maxVisible, v1.length);
    
    for (let i = movilStartIndex; i < movilEndIndex; i++) {
      const imgPath = v3[i] || v2[i] || '';
      const isActive = i === v17;
      const miniatura = document.createElement('div');
      miniatura.className = `miniatura-imagen flex-shrink-0 ${isActive ? 'activa' : ''}`;
      miniatura.style.width = '64px';
      miniatura.style.height = '64px';
      miniatura.onclick = () => _si1(i);
      miniatura.innerHTML = `<img src="${v6}/${imgPath}" alt="Miniatura ${i + 1}">`;
      miniaturasMovil.appendChild(miniatura);
    }
    
    // Actualizar botones del carrusel móvil
    _abcm1(movilStartIndex, movilEndIndex);
  }
}

{{-- _cms1: centrarMiniaturaSeleccionada - Centra la miniatura seleccionada en el carrusel --}}
function _cms1() {
  // Solo para móvil, centrar la miniatura seleccionada
  const containerMovil = document.getElementById('miniaturas-container-movil');
  if (!containerMovil) return;
  
  // Scroll suave hacia la miniatura activa después de un pequeño delay para que se renderice
  setTimeout(() => {
    const miniaturaActiva = containerMovil.querySelector('.activa');
    if (miniaturaActiva) {
      const containerRect = containerMovil.getBoundingClientRect();
      const miniaturaRect = miniaturaActiva.getBoundingClientRect();
      const scrollLeft = miniaturaActiva.offsetLeft - (containerRect.width / 2) + (miniaturaRect.width / 2);
      containerMovil.scrollTo({ left: scrollLeft, behavior: 'smooth' });
    }
  }, 50);
}

{{-- _mcm1: moverCarruselMovil - Mueve el carrusel de miniaturas en móvil --}}
function _mcm1(direccion) {
  const maxVisible = 7;
  v18 += direccion;
  
  if (v18 < 0) v18 = 0;
  if (v18 + maxVisible > v1.length) {
    v18 = Math.max(0, v1.length - maxVisible);
  }
  
  // Re-renderizar solo las miniaturas
  _rm1();
}

{{-- _abcm1: actualizarBotonesCarruselMovil - Actualiza el estado de los botones del carrusel móvil --}}
function _abcm1(startIndex, endIndex) {
  const btnIzq = document.getElementById('btn-carrusel-izq-movil');
  const btnDer = document.getElementById('btn-carrusel-der-movil');
  
  if (btnIzq) {
    btnIzq.disabled = startIndex === 0;
  }
  if (btnDer) {
    btnDer.disabled = endIndex >= v1.length;
  }
}

{{-- _rod1: renderizarOfertaDesktop - Renderiza la información de la oferta en el modal desktop --}}
function _rod1() {
  // Solo renderizar en desktop
  const container = document.getElementById('modal-oferta-container');
  if (!container) return;
  
  if (!ofertas || ofertas.length === 0) {
    container.innerHTML = '<p class="text-gray-500 text-center">No hay ofertas disponibles</p>';
    return;
  }
  
  // Obtener las ofertas filtradas actuales (igual que en renderOfertas)
  let filtradas = ofertas.slice();
  
  // Aplicar filtro de especificaciones internas primero
  if (typeof _fpe1 === 'function') {
    filtradas = _fpe1(filtradas);
  }
  
  // Aplicar otros filtros (igual que en renderOfertas)
  {{-- x1: Filtro de tienda --}}
  const tienda = document.getElementById('x1')?.value;
  {{-- x2: Filtro de envío gratis --}}
  const envioGratis = document.getElementById('x2')?.checked;
  {{-- x3: Filtro de cantidad --}}
  const cantidad = document.getElementById('x3')?.value;
  
  if (tienda) filtradas = filtradas.filter(o => o.tienda === tienda);
  if (envioGratis) filtradas = filtradas.filter(o => (o.envio_gratis && o.envio_gratis.toLowerCase().includes('gratis')));
  if (cantidad) filtradas = filtradas.filter(o => String(o.unidades) === cantidad);
  
  // Aplicar el mismo orden que en renderOfertas
  if (ordenActual === 'precio_total') {
    filtradas.sort((a, b) => parseFloat(a.precio_total.replace(',', '.')) - parseFloat(b.precio_total.replace(',', '.')));
  } else if (ordenActual === 'unidades') {
    filtradas.sort((a, b) => parseFloat(a.precio_unidad.replace(',', '.')) - parseFloat(b.precio_unidad.replace(',', '.')));
  }
  
  // Si no hay ofertas filtradas, mostrar mensaje
  if (filtradas.length === 0) {
    container.innerHTML = '<p class="text-gray-500 text-center">No hay ofertas disponibles con los filtros seleccionados</p>';
    return;
  }
  
  // Obtener la primera oferta de las filtradas (la más barata según el orden actual)
  const primeraOferta = filtradas[0];
  const unidadMedida = '{{ $producto->unidadDeMedida }}';
  const esUnidadUnica = unidadMedida === 'unidadUnica';
  const columnasDataModal = @json($columnasData ?? null);
  
  // Función auxiliar para obtener texto de sublínea (usa columnasDataModal local)
  {{-- _otsm1: obtenerTextoSublineaModal - Obtiene el texto de una sublínea para el modal --}}
  function _otsm1(lineaId, sublineaId) {
    if (!columnasDataModal || !Array.isArray(columnasDataModal)) return '-';
    const linea = columnasDataModal.find(l => l.id === lineaId);
    if (!linea || !linea.subprincipales) return '-';
    const sublinea = linea.subprincipales.find(s => s.id === sublineaId);
    return sublinea ? sublinea.texto : '-';
  }
  
  // Función para renderizar columnas dinámicas (adaptada para modal)
  {{-- _rcuum1: renderizarColumnasUnidadUnicaModal - Renderiza columnas dinámicas para oferta única en el modal --}}
  function _rcuum1(oferta) {
    if (!esUnidadUnica || !columnasDataModal || !Array.isArray(columnasDataModal) || columnasDataModal.length === 0) {
      return '';
    }
    
    const numColumnas = columnasDataModal.length;
    const especificacionesOferta = oferta.especificaciones_internas || {};
    const columnasOferta = especificacionesOferta._columnas || {};
    
    let html = '';
    
    // Para el modal, cada columna se muestra como independiente, centrada y con separador
    columnasDataModal.forEach((linea, index) => {
      const sublineaId = columnasOferta[linea.id];
      const textoSublinea = sublineaId ? _otsm1(linea.id, sublineaId) : '-';
      // Añadir separador (border-b) excepto en la última columna
      const separatorClass = index < numColumnas - 1 ? 'pb-4 mb-4 border-b border-gray-200' : '';
      html += `<div class="text-center ${separatorClass}">
        <div class="text-xs text-gray-500 mb-1">${linea.texto}</div>
        <div class="text-sm font-semibold text-gray-700">${textoSublinea}</div>
      </div>`;
    });
    
    return html;
  }
  
  // Renderizar columnas dinámicas usando la misma función que el listado
  const columnasDinamicasHtml = esUnidadUnica && columnasDataModal && Array.isArray(columnasDataModal) && columnasDataModal.length > 0 
    ? _rcuum1(primeraOferta) 
    : '';
  
  // Determinar qué mostrar (igual que en renderOfertas)
  let mostrarCantidad = !esUnidadUnica;
  let mostrarPrecioTotal = false;
  let mostrarPrecioUnidad = false;
  let esUnidadUnicaConColumnas = false;
  let esUnidadUnicaSinColumnas = false;
  
  if (esUnidadUnica && columnasDataModal && Array.isArray(columnasDataModal) && columnasDataModal.length > 0) {
    mostrarCantidad = false;
    mostrarPrecioTotal = false;
    mostrarPrecioUnidad = false;
    esUnidadUnicaConColumnas = true;
  } else if (esUnidadUnica && (!columnasDataModal || columnasDataModal.length === 0)) {
    mostrarCantidad = false;
    mostrarPrecioTotal = true;
    mostrarPrecioUnidad = false;
    esUnidadUnicaSinColumnas = true;
  } else {
    mostrarCantidad = true;
    mostrarPrecioTotal = true;
    mostrarPrecioUnidad = true;
  }
  
  let html = `
    <div class="mejor-oferta-wrapper">
      <div class="mejor-oferta-badge-grupo">🏆 Mejor precio</div>
      <div class="mejor-oferta-gaming-container">
        <div class="ofertas-grupo">
          <div class="bg-white rounded-lg p-4">
            <div class="flex items-center justify-center mb-4">
              <img src="${primeraOferta.logo}" alt="${primeraOferta.nombre}" class="h-10 object-contain">
            </div>
  `;
  
  // Envío (igual que en el listado)
  html += `
      <div class="text-center mb-4 pb-4 border-b border-gray-200">
        <div class="envio text-gray-700">
          ${primeraOferta.envio_gratis ? '<p class="text-sm text-gray-500 font-bold"><img src="' + vanIconUrlModal + '" loading="lazy" alt="Van" class="icon-small inline-block align-middle mr-1"> ' + primeraOferta.envio_gratis + '</p>' : ''}
          ${primeraOferta.envio_normal ? '<p class="text-sm text-gray-500"><img src="' + vanIconUrlModal + '" loading="lazy" alt="Van" class="icon-small inline-block align-middle mr-1"> ' + primeraOferta.envio_normal + '</p>' : ''}
        </div>
      </div>
  `;
  
  // Cantidad (solo si no es unidad única, usando la función correcta)
  if (mostrarCantidad) {
    html += `
      <div class="text-center mb-4 pb-4 border-b border-gray-200">
        <div class="und text-gray-700">
          <div class="font-semibold">Cantidad</div>
          <div class="text-sm text-gray-500 leading-tight">${_fc1(primeraOferta.unidades, unidadMedida)}</div>
        </div>
      </div>
    `;
  }
  
  // Columnas dinámicas (si es unidad única con columnas)
  if (columnasDinamicasHtml) {
    html += `<div class="mb-4">${columnasDinamicasHtml}</div>`;
  }
  
  // Precio (igual que en el listado)
  if (!mostrarPrecioTotal && !mostrarPrecioUnidad && primeraOferta.precio_total) {
    // Unidad única con columnas: mostrar precio total grande
    html += `
      <div class="text-center mb-4 pb-4 border-b border-gray-200">
        <div class="precio-total text-gray-700">
          <div class="text-sm text-gray-500">Precio total</div>
          <div class="text-3xl font-extrabold text-pink-400">${primeraOferta.precio_total} <span class="text-sm text-gray-500 font-normal">€</span></div>
        </div>
      </div>
    `;
  } else if (mostrarPrecioTotal && primeraOferta.precio_total) {
    // Unidad única sin columnas u otros casos: mostrar precio total normal
    const precioTotalClass = esUnidadUnicaSinColumnas ? 'text-xl font-bold text-gray-700' : 'text-xl font-bold text-gray-700';
    const precioTotalLabelClass = esUnidadUnicaSinColumnas ? 'text-sm text-gray-500' : 'text-sm text-gray-500';
    html += `
      <div class="text-center mb-4 pb-4 border-b border-gray-200">
        <div class="precio-total text-gray-700">
          <div class="${precioTotalLabelClass}">Precio total</div>
          <div class="${precioTotalClass}">${primeraOferta.precio_total} <span class="text-sm text-gray-500 font-normal">€</span></div>
        </div>
      </div>
    `;
  }
  
  // Precio por unidad (solo si se debe mostrar)
  if (mostrarPrecioUnidad) {
    html += `
      <div class="text-center mb-4 pb-4 border-b border-gray-200">
        <div class="precio-und text-gray-700">
          <div class="font-semibold">${_glp1(unidadMedida)}</div>
          <div class="text-2xl font-extrabold text-pink-400">
            ${primeraOferta.precio_unidad} <span class="text-sm text-gray-500 font-normal">${_gsp1(unidadMedida)}</span>
          </div>
        </div>
      </div>
    `;
  }
  
  // Generar botón con descuentos (misma lógica que en el listado)
  let botonHtml = '';
  if (primeraOferta.descuentos && typeof primeraOferta.descuentos === 'string' && primeraOferta.descuentos.startsWith('SoloAliexpress;')) {
    botonHtml = '<div class="relative">' +
      '<span class="cupon-badge" style="background: linear-gradient(135deg, #ff6900, #ff8c00); font-size: 0.7rem; padding: 2px 6px; font-weight: bold; white-space: nowrap; position: absolute; top: -12px; right: 8px; z-index: 10;">CUPÓN</span>' +
      '<span class="inline-block w-full py-3 px-4 bg-blue-500 text-white text-center font-semibold rounded hover:bg-blue-600 transition-colors cursor-pointer" data-cupon-aliexpress="true" data-descuentos="' + primeraOferta.descuentos.replace(/"/g, '&quot;') + '" data-oferta-id="' + primeraOferta.id + '" data-url="' + primeraOferta.url + '">Ir a la tienda</span>' +
      '</div>';
  } else if (primeraOferta.descuentos && typeof primeraOferta.descuentos === 'string' && primeraOferta.descuentos.startsWith('CholloTienda1SoloCuponQueAplicaDescuento;')) {
    botonHtml = '<div class="relative">' +
      '<span class="cupon-badge" style="background: linear-gradient(135deg, #ff6900, #ff8c00); font-size: 0.7rem; padding: 2px 6px; font-weight: bold; white-space: nowrap; position: absolute; top: -12px; right: 8px; z-index: 10;">CUPÓN</span>' +
      '<span class="inline-block w-full py-3 px-4 bg-blue-500 text-white text-center font-semibold rounded hover:bg-blue-600 transition-colors cursor-pointer" data-cupon-chollo-tienda-solo="true" data-descuentos="' + primeraOferta.descuentos.replace(/"/g, '&quot;') + '" data-oferta-id="' + primeraOferta.id + '" data-url="' + primeraOferta.url + '">Ir a la tienda</span>' +
      '</div>';
  } else if (primeraOferta.descuentos && typeof primeraOferta.descuentos === 'string' && primeraOferta.descuentos.startsWith('CholloTienda;')) {
    botonHtml = '<div class="relative">' +
      '<span class="cupon-badge" style="background: linear-gradient(135deg, #ff6900, #ff8c00); font-size: 0.7rem; padding: 2px 6px; font-weight: bold; white-space: nowrap; position: absolute; top: -12px; right: 8px; z-index: 10;">CUPÓN</span>' +
      '<span class="inline-block w-full py-3 px-4 bg-blue-500 text-white text-center font-semibold rounded hover:bg-blue-600 transition-colors cursor-pointer" data-cupon-chollo-tienda="true" data-descuentos="' + primeraOferta.descuentos.replace(/"/g, '&quot;') + '" data-oferta-id="' + primeraOferta.id + '" data-url="' + primeraOferta.url + '">Ir a la tienda</span>' +
      '</div>';
  } else if (primeraOferta.descuentos && typeof primeraOferta.descuentos === 'string' && primeraOferta.descuentos.startsWith('cupon;')) {
    try {
      const cuponInfo = _pc1(primeraOferta.descuentos);
      const valorCupon = cuponInfo ? cuponInfo.valor : (primeraOferta.descuentos.split(';')[1] || '');
      const codigoCupon = cuponInfo ? cuponInfo.codigo : null;
      const codigoCuponEscapado = codigoCupon ? codigoCupon.replace(/"/g, '&quot;').replace(/'/g, '&#39;') : '';
      const valorCuponEscapado = String(valorCupon).replace(/"/g, '&quot;').replace(/'/g, '&#39;');
      const urlEscapada = primeraOferta.url.replace(/"/g, '&quot;').replace(/'/g, '&#39;');
      const dataAttrs = codigoCupon 
        ? `data-cupon="true" data-codigo-cupon="${codigoCuponEscapado}" data-valor-cupon="${valorCuponEscapado}" data-url="${urlEscapada}"` 
        : `data-cupon="true" data-valor-cupon="${valorCuponEscapado}" data-url="${urlEscapada}"`;
      botonHtml = '<div class="relative">' +
        '<span class="cupon-badge" style="background: linear-gradient(135deg, #ff6900, #ff8c00); font-size: 0.7rem; padding: 2px 6px; font-weight: bold; position: absolute; top: -12px; right: 8px; z-index: 10;">CUPÓN</span>' +
        '<span class="inline-block w-full py-3 px-4 bg-blue-500 text-white text-center font-semibold rounded hover:bg-blue-600 transition-colors cursor-pointer" ' + dataAttrs + '>Ir a la tienda</span>' +
        '</div>';
    } catch (e) {
      botonHtml = '<a href="' + primeraOferta.url + '" target="_blank" rel="sponsored noopener noreferrer" class="block w-full py-3 px-4 bg-blue-500 text-white text-center font-semibold rounded hover:bg-blue-600 transition-colors">Ir a la tienda</a>';
    }
  } else if (primeraOferta.descuentos === 'cupon') {
    botonHtml = '<div class="relative">' +
      '<span class="cupon-badge" style="background: linear-gradient(135deg, #ff6900, #ff8c00); font-size: 0.7rem; padding: 2px 6px; font-weight: bold; white-space: nowrap; position: absolute; top: -12px; right: 8px; z-index: 10;">CUPÓN</span>' +
      '<span class="inline-block w-full py-3 px-4 bg-blue-500 text-white text-center font-semibold rounded hover:bg-blue-600 transition-colors cursor-pointer" data-cupon="true" data-url="' + primeraOferta.url.replace(/"/g, '&quot;') + '">Ir a la tienda</span>' +
      '</div>';
  } else if (primeraOferta.descuentos === '3x2') {
    botonHtml = '<div class="relative">' +
      '<span class="cupon-badge" style="background: linear-gradient(135deg, #8b5cf6, #a855f7); font-size: 0.7rem; padding: 2px 6px; font-weight: normal; white-space: nowrap; position: absolute; top: -12px; right: 8px; z-index: 10;">3x2</span>' +
      '<span class="inline-block w-full py-3 px-4 bg-blue-500 text-white text-center font-semibold rounded hover:bg-blue-600 transition-colors cursor-pointer" data-3x2="true" data-url="' + primeraOferta.url.replace(/"/g, '&quot;') + '">Ir a la tienda</span>' +
      '</div>';
  } else if (primeraOferta.descuentos === '2x1 - SoloCarrefour') {
    botonHtml = '<div class="relative">' +
      '<span class="cupon-badge" style="background: linear-gradient(135deg, #10b981, #059669); font-size: 0.7rem; padding: 2px 6px; font-weight: normal; white-space: nowrap; position: absolute; top: -12px; right: 8px; z-index: 10;">2x1</span>' +
      '<span class="inline-block w-full py-3 px-4 bg-blue-500 text-white text-center font-semibold rounded hover:bg-blue-600 transition-colors cursor-pointer" data-2x1="true" data-url="' + primeraOferta.url.replace(/"/g, '&quot;') + '">Ir a la tienda</span>' +
      '</div>';
  } else if (primeraOferta.descuentos === '2a al 50 - cheque - SoloCarrefour') {
    botonHtml = '<div class="relative">' +
      '<span class="cupon-badge" style="background: linear-gradient(135deg, #f59e0b, #d97706); font-size: 0.7rem; padding: 2px 6px; font-weight: bold; white-space: nowrap; position: absolute; top: -12px; right: 8px; z-index: 10;">2a al 50%</span>' +
      '<span class="inline-block w-full py-3 px-4 bg-blue-500 text-white text-center font-semibold rounded hover:bg-blue-600 transition-colors cursor-pointer" data-2a-al-50-cheque="true" data-url="' + primeraOferta.url.replace(/"/g, '&quot;') + '">Ir a la tienda</span>' +
      '</div>';
  } else if (primeraOferta.descuentos === '2a al 70') {
    botonHtml = '<div class="relative">' +
      '<span class="cupon-badge" style="background: linear-gradient(135deg, #f59e0b, #d97706); font-size: 0.7rem; padding: 2px 6px; font-weight: normal; white-space: nowrap; position: absolute; top: -12px; right: 8px; z-index: 10;">2a al 70%</span>' +
      '<span class="inline-block w-full py-3 px-4 bg-blue-500 text-white text-center font-semibold rounded hover:bg-blue-600 transition-colors cursor-pointer" data-2a-al-70="true" data-url="' + primeraOferta.url.replace(/"/g, '&quot;') + '">Ir a la tienda</span>' +
      '</div>';
  } else if (primeraOferta.descuentos === '2a al 50') {
    botonHtml = '<div class="relative">' +
      '<span class="cupon-badge" style="background: linear-gradient(135deg, #f59e0b, #d97706); font-size: 0.7rem; padding: 2px 6px; font-weight: normal; white-space: nowrap; position: absolute; top: -12px; right: 8px; z-index: 10;">2a al 50%</span>' +
      '<span class="inline-block w-full py-3 px-4 bg-blue-500 text-white text-center font-semibold rounded hover:bg-blue-600 transition-colors cursor-pointer" data-2a-al-50="true" data-url="' + primeraOferta.url.replace(/"/g, '&quot;') + '">Ir a la tienda</span>' +
      '</div>';
  } else {
    botonHtml = '<a href="' + primeraOferta.url + '" target="_blank" rel="sponsored noopener noreferrer" class="block w-full py-3 px-4 bg-blue-500 text-white text-center font-semibold rounded hover:bg-blue-600 transition-colors">Ir a la tienda</a>';
  }
  
  html += botonHtml;
  html += `
          </div>
        </div>
      </div>
    </div>
  `;
  
  container.innerHTML = html;
  
  // Configurar event listeners para descuentos después de renderizar
  setTimeout(() => {
    _scel1();
  }, 100);
}

// Inicializar carruseles de sublíneas
{{-- _ics1: inicializarCarruselesSublineas - Inicializa los carruseles de sublíneas --}}
function _ics1() {
  const containers = document.querySelectorAll('.sublineas-carrusel-container');
  
  containers.forEach(container => {
    const carrusel = container.querySelector('.sublineas-carrusel');
    const lineaId = container.dataset.lineaId;
    const btnLeft = container.querySelector(`.carrusel-btn-left[data-linea-id="${lineaId}"]`);
    const btnRight = container.querySelector(`.carrusel-btn-right[data-linea-id="${lineaId}"]`);
    
    if (!carrusel) return;
    
    // Verificar si estamos en desktop (ancho >= 640px)
    const esDesktop = () => window.innerWidth >= 640;
    
    // Función para actualizar visibilidad de botones (solo en desktop)
    const actualizarBotones = () => {
      if (!esDesktop() || !btnLeft || !btnRight) {
        // En móvil, asegurar que los botones estén ocultos
        if (btnLeft) {
          btnLeft.style.opacity = '0';
          btnLeft.style.pointerEvents = 'none';
        }
        if (btnRight) {
          btnRight.style.opacity = '0';
          btnRight.style.pointerEvents = 'none';
        }
        return;
      }
      
      const scrollLeft = carrusel.scrollLeft;
      const scrollWidth = carrusel.scrollWidth;
      const clientWidth = carrusel.clientWidth;
      
      // Mostrar/ocultar botón izquierdo
      if (scrollLeft > 5) {
        btnLeft.style.opacity = '1';
        btnLeft.style.pointerEvents = 'auto';
      } else {
        btnLeft.style.opacity = '0';
        btnLeft.style.pointerEvents = 'none';
      }
      
      // Mostrar/ocultar botón derecho
      if (scrollLeft < scrollWidth - clientWidth - 5) {
        btnRight.style.opacity = '1';
        btnRight.style.pointerEvents = 'auto';
      } else {
        btnRight.style.opacity = '0';
        btnRight.style.pointerEvents = 'none';
      }
    };
    
    // Event listener para el scroll
    carrusel.addEventListener('scroll', actualizarBotones);
    
    // Event listener para resize
    window.addEventListener('resize', actualizarBotones);
    
    // Botones solo funcionan en desktop
    if (btnLeft) {
      btnLeft.addEventListener('click', (e) => {
        if (esDesktop()) {
          e.preventDefault();
          carrusel.scrollBy({ left: -200, behavior: 'smooth' });
        }
      });
    }
    
    if (btnRight) {
      btnRight.addEventListener('click', (e) => {
        if (esDesktop()) {
          e.preventDefault();
          carrusel.scrollBy({ left: 200, behavior: 'smooth' });
        }
      });
    }
    
    // Mostrar botones al hacer hover sobre el contenedor (solo desktop)
    if (esDesktop()) {
      container.addEventListener('mouseenter', () => {
        actualizarBotones();
      });
    }
    
    // Inicializar visibilidad de botones
    actualizarBotones();
  });
}

// Inicializar carruseles cuando el DOM esté listo
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', _ics1);
} else {
  _ics1();
}
</script>

{{-- MODAL DE IMÁGENES DEL PRODUCTO --}}
{{-- x18: Modal de imágenes --}}
<div id="x18" class="fixed inset-0 z-50 hidden overflow-y-auto" style="background-color: rgba(0, 0, 0, 0.75);">
  <div class="flex items-center justify-center min-h-screen px-4">
    {{-- Contenedor del modal --}}
    <div class="bg-white rounded-lg shadow-2xl max-w-7xl w-full max-h-[90vh] overflow-hidden relative flex flex-col lg:flex-row" onclick="event.stopPropagation()">
      
      {{-- Botón cerrar Desktop (arriba derecha) --}}
      <button onclick="_cm1()" class="hidden lg:block absolute top-4 right-4 z-50 bg-white rounded-full p-2 shadow-lg hover:bg-gray-100 transition-colors">
        <svg class="w-6 h-6 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
        </svg>
      </button>
      
      {{-- Botón cerrar Móvil (arriba derecha) --}}
      <button onclick="_cm1()" class="lg:hidden absolute top-4 right-4 z-50 bg-white rounded-full p-2 shadow-lg hover:bg-gray-100 transition-colors">
        <svg class="w-6 h-6 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
        </svg>
      </button>
      
      {{-- Contenedor principal --}}
      <div class="flex-1 flex flex-col lg:flex-row overflow-hidden">
        
        {{-- Columna izquierda: Carrusel de imágenes pequeñas (solo desktop) --}}
        <div id="carrusel-imagenes-desktop" class="hidden lg:flex flex-col w-36 p-4 border-r border-gray-200" style="max-height: calc(90vh - 2rem); overflow-y: auto;">
          <div id="miniaturas-container-desktop" class="flex flex-col gap-2" style="max-height: 600px;">
            {{-- Miniaturas se insertarán aquí --}}
          </div>
        </div>
        
        {{-- Contenedor central: Imagen grande --}}
        <div class="flex-1 flex items-center justify-center bg-gray-50 p-4 lg:p-6">
          <div class="relative flex items-center justify-center w-full" style="padding-left: 3rem; padding-right: 3rem;">
            {{-- Flecha izquierda --}}
            <button id="btn-flecha-izq" onclick="_ci1(-1)" class="absolute left-0 top-1/2 transform -translate-y-1/2 bg-white rounded-full p-2 shadow-lg hover:bg-gray-100 transition-all z-10 opacity-50 hover:opacity-100 disabled:opacity-30 disabled:cursor-not-allowed">
              <svg class="w-6 h-6 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
              </svg>
            </button>
            
            {{-- Imagen grande --}}
            <img id="imagen-grande-modal" src="" alt="Imagen del producto" class="max-w-full max-h-[60vh] object-contain">
            
            {{-- Flecha derecha --}}
            <button id="btn-flecha-der" onclick="_ci1(1)" class="absolute right-0 top-1/2 transform -translate-y-1/2 bg-white rounded-full p-2 shadow-lg hover:bg-gray-100 transition-all z-10 opacity-50 hover:opacity-100 disabled:opacity-30 disabled:cursor-not-allowed">
              <svg class="w-6 h-6 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
              </svg>
            </button>
          </div>
        </div>
        
        {{-- Columna derecha: Información del producto y oferta (solo desktop) --}}
        <div id="info-producto-desktop" class="hidden lg:flex flex-col w-80 border-l border-gray-200 px-6 pb-6 overflow-y-auto" style="padding-top: 5rem;">
          <div id="modal-oferta-container">
            {{-- La primera oferta se insertará aquí --}}
          </div>
        </div>
        
      </div>
      
      {{-- Carrusel de imágenes pequeñas móvil (debajo de la imagen grande) --}}
      <div id="carrusel-imagenes-movil" class="lg:hidden border-t border-gray-200 p-4 bg-white">
        <div class="flex items-center gap-2 relative">
          {{-- Flecha izquierda carrusel móvil --}}
          <button id="btn-carrusel-izq-movil" onclick="_mcm1("-1)" class="flex-shrink-0 bg-gray-100 rounded-full p-1 hover:bg-gray-200 disabled:opacity-30 disabled:cursor-not-allowed">
            <svg class="w-5 h-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
          </button>
          
          {{-- Contenedor de miniaturas móvil --}}
          <div id="miniaturas-container-movil" class="flex gap-2 overflow-x-auto flex-1 scrollbar-hide" style="scroll-behavior: smooth; -webkit-overflow-scrolling: touch;">
            {{-- Miniaturas se insertarán aquí --}}
          </div>
          
          {{-- Flecha derecha carrusel móvil --}}
          <button id="btn-carrusel-der-movil" onclick="_mcm1("1)" class="flex-shrink-0 bg-gray-100 rounded-full p-1 hover:bg-gray-200 disabled:opacity-30 disabled:cursor-not-allowed">
            <svg class="w-5 h-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
            </svg>
          </button>
        </div>
      </div>
      
    </div>
  </div>
</div>

{{-- JS PARA EL BUSCADOR DEL HEADER --}}
@stack('scripts')
</body>

</html>