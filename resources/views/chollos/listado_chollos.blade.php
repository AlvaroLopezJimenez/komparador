@php
    $esAjax = $esAjax ?? request()->boolean('ajax');
    use Illuminate\Support\Str;

    if (!function_exists('añadirCam')) {
        function añadirCam($url)
        {
            if (!$url) {
                return $url;
            }

            try {
                if (request()->has('cam')) {
                    $separator = str_contains($url, '?') ? '&' : '?';
                    return $url . $separator . 'cam=' . request('cam');
                }
            } catch (\Throwable $th) {
                return $url;
            }

            return $url;
        }
    }
@endphp

@if(!$esAjax)
<!DOCTYPE html>
<html lang="es">
<head>
    @php
        $canonicalUrl = url()->current();
        $u = parse_url($canonicalUrl);
        $u['host'] = 'chollopanales.com';
        $canonicalUrl =
            ($u['scheme'] ?? 'https') . '://' .
            $u['host'] .
            ($u['path'] ?? '') .
            (isset($u['query']) ? '?' . $u['query'] : '');
    @endphp
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Últimos chollos de pañales y productos bebé | CholloPañales</title>
    <meta name="description" content="Descubre los chollos más recientes de pañales, toallitas y productos para bebé. Actualizamos las ofertas cada pocas horas para que no te pierdas ninguna.">
    <link rel="canonical" href="{{ $canonicalUrl }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="icon" type="image/png" href="{{ asset('images/icono.webp') }}">
</head>
<body class="bg-gray-50">
    <x-header />

    <main class="max-w-7xl mx-auto px-4 lg:px-6 xl:px-8 py-6 lg:py-10">
        <div class="flex flex-col lg:flex-row gap-6 lg:gap-8">
            <section class="w-full lg:w-3/4">
                <header class="mb-6">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                        <div>
                            <h1 class="text-3xl font-bold text-gray-900 tracking-tight">Chollos recientes</h1>
                        </div>
                    </div>
                </header>

                @if($chollos->isEmpty())
                    <div class="bg-white border border-dashed border-pink-200 rounded-2xl p-10 text-center shadow-sm">
                        <img src="{{ asset('images/empty-state.svg') }}" alt="Sin chollos" class="mx-auto w-40 h-40 object-contain mb-4">
                        <h2 class="text-xl font-semibold text-gray-800">Todavía no hay chollos publicados</h2>
                        <p class="text-gray-500 mt-2 max-w-xl mx-auto">
                            Revisa más tarde, estamos cazando nuevas ofertas para ti. Activa las alertas para recibir un aviso cuando aparezcan.
                        </p>
                    </div>
                @else
                    <div id="chollos-list" class="grid gap-4 lg:gap-6">
@endif
                        @foreach($chollos as $chollo)
                            @php
                                $urlDetalle = url('chollo/' . $chollo->slug);
                                $tieneImagen = !empty($chollo->imagen_pequena);
                                $imagen = $tieneImagen ? asset('images/' . $chollo->imagen_pequena) : null;
                                $titulo = $chollo->titulo ?? $chollo->nombre ?? 'Chollo sin título';
                                $descripcion = Str::limit(strip_tags($chollo->descripcion ?? $chollo->descripcion_corta ?? ''), 160);

                                $precioNuevo = null;
                                if (!empty($chollo->precio_nuevo)) {
                                    $precioNuevo = is_numeric($chollo->precio_nuevo)
                                        ? (float) $chollo->precio_nuevo
                                        : (float) str_replace(',', '.', preg_replace('/[^\d,\.]/', '', $chollo->precio_nuevo));
                                }

                                $precioAntiguo = null;
                                if (!empty($chollo->precio_antiguo)) {
                                    $precioAntiguo = is_numeric($chollo->precio_antiguo)
                                        ? (float) $chollo->precio_antiguo
                                        : (float) str_replace(',', '.', preg_replace('/[^\d,\.]/', '', $chollo->precio_antiguo));
                                }

                                $descuento = null;
                                if ($precioNuevo && $precioAntiguo && $precioAntiguo > 0) {
                                    $descuento = round((1 - ($precioNuevo / $precioAntiguo)) * 100);
                                    if ($descuento <= 0) {
                                        $descuento = null;
                                    }
                                } elseif (!empty($chollo->descuentos)) {
                                    $descuento = (int) filter_var($chollo->descuentos, FILTER_SANITIZE_NUMBER_INT);
                                    if ($descuento === 0) {
                                        $descuento = null;
                                    }
                                }

                                $badgeTexto = $descuento ? "-{$descuento}%" : null;
                                $tiendaNombre = optional($chollo->tienda)->nombre ?? 'Ver tienda';
                                $fechaPublicacion = optional($chollo->created_at)->diffForHumans();
                                $esNuevo = optional($chollo->created_at)?->greaterThan(now()->subHours(12));

                                $ahora = \Carbon\Carbon::now();
                                $fechaInicio = optional($chollo->fecha_inicio);
                                $fechaFinal = optional($chollo->fecha_final);
                                $estadoBadge = null;
                                $estadoBadgeClase = 'bg-gray-100 text-gray-600';
                                $estadoCountdownTarget = null;
                                $estadoCountdownPrefix = null;
                                $estadoCountdownType = null;

                                $estaExpirado = $fechaFinal && $fechaFinal->isPast();

                                if ($estaExpirado) {
                                    $fechaFinal->locale(app()->getLocale() ?? 'es');
                                    $tiempoFinalizado = $fechaFinal->diffForHumans(null, true, false, 1);
                                    $estadoBadge = 'Finalizado hace ' . $tiempoFinalizado;
                                    $estadoBadgeClase = 'bg-gray-200 text-gray-600';
                                } elseif ($fechaInicio && $fechaInicio->isFuture()) {
                                    $estadoCountdownTarget = $fechaInicio->toIso8601String();
                                    $estadoCountdownPrefix = 'Empieza en';
                                    $estadoCountdownType = 'start';
                                    $estadoBadge = $estadoCountdownPrefix . ' ' . $fechaInicio->diffForHumans(null, false, false, 2);
                                    $estadoBadgeClase = 'bg-amber-100 text-amber-700';
                                } elseif ($fechaFinal && $fechaFinal->isFuture() && (!$fechaInicio || $fechaInicio->lte($ahora))) {
                                    $estadoCountdownTarget = $fechaFinal->toIso8601String();
                                    $estadoCountdownPrefix = 'Termina en';
                                    $estadoCountdownType = 'end';
                                    $estadoBadge = $estadoCountdownPrefix . ' ' . $fechaFinal->diffForHumans(null, false, false, 2);
                                    $estadoBadgeClase = 'bg-red-100 text-red-700';
                                } else {
                                    $estadoBadge = 'Publicado ' . ($fechaPublicacion ?: 'recientemente');
                                }

                                $cuponCodigo = null;
                                $cuponValor = null;
                                if (!empty($chollo->descuentos) && Str::startsWith($chollo->descuentos, 'cupon')) {
                                    $partes = explode(';', $chollo->descuentos);
                                    if (count($partes) === 3) {
                                        $cuponCodigo = $partes[1];
                                        $cuponValor = $partes[2];
                                    } elseif (count($partes) === 2) {
                                        $cuponValor = $partes[1];
                                    }
                                }

                                $ofertaComparador = null;
                                $comparadorUrl = null;
                                $ofertaParaTienda = null;
                                $ofertaRedireccion = null;
                                if ($chollo->relationLoaded('ofertas')) {
                                    $ofertaComparador = $chollo->ofertas->first(function ($oferta) {
                                        return $oferta->producto && $oferta->producto->categoria && method_exists($oferta->producto->categoria, 'construirUrlCategorias');
                                    });
                                    if ($ofertaComparador) {
                                        $comparadorUrl = añadirCam($ofertaComparador->producto->categoria->construirUrlCategorias($ofertaComparador->producto->slug));
                                    }

                                    $ofertaParaTienda = $chollo->ofertas->first(function ($oferta) {
                                        return !empty($oferta->url);
                                    }) ?: $ofertaComparador;

                                    if ($ofertaParaTienda && $chollo->tipo === 'oferta') {
                                        try {
                                            $ofertaRedireccion = añadirCam(route('click.redirigir', ['ofertaId' => $ofertaParaTienda->id]));
                                        } catch (\Throwable $th) {
                                            $ofertaRedireccion = null;
                                        }
                                    } elseif ($ofertaParaTienda && !empty($ofertaParaTienda->url)) {
                                        $ofertaRedireccion = añadirCam($ofertaParaTienda->url);
                                    }
                                }
                            @endphp

                            @php
                                $tieneCuponCompleto = $cuponCodigo && $cuponValor;
                                $urlTienda = añadirCam($chollo->url ?? '#');
                                if ($ofertaRedireccion) {
                                    $urlTienda = $ofertaRedireccion;
                                }
                            @endphp

                            <article
                                class="group relative overflow-hidden rounded-[32px] border border-gray-200 bg-white text-gray-900 shadow-lg transition-all duration-300 hover:shadow-xl cursor-pointer lg:cursor-pointer"
                                role="link"
                                tabindex="0"
                                onclick="window.location.href='{{ $urlDetalle }}'"
                                onkeydown="if(event.key==='Enter' || event.key===' '){ event.preventDefault(); window.location.href='{{ $urlDetalle }}'; }"
                                data-chollo-url="{{ $urlDetalle }}"
                            >
                                {{-- VISTA MÓVIL --}}
                                <div class="lg:hidden">
                                    {{-- Header con badge --}}
                                    <div class="flex justify-end px-3 pt-1.5 pb-0.5">
                                        @if($estadoBadge)
                                            <span class="rounded-full px-2 py-0.5 text-[8px] font-medium bg-amber-100 text-amber-700" @if($estadoCountdownTarget) data-countdown-target="{{ $estadoCountdownTarget }}" data-countdown-prefix="{{ $estadoCountdownPrefix }}" data-countdown-type="{{ $estadoCountdownType }}" @endif>
                                                {{ $estadoBadge }}
                                            </span>
                                        @endif
                                    </div>

                                    <div class="flex flex-row gap-3 px-3 pt-2 pb-3">
                                        {{-- Imagen del producto --}}
                                        <div class="relative flex-shrink-0" style="width:100px;height:100px;">
                                            <div class="flex h-full w-full items-center justify-center overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                                                @if($tieneImagen)
                                                    <img
                                                        src="{{ $imagen }}"
                                                        alt="{{ $titulo }}"
                                                        class="max-h-full max-w-full object-contain"
                                                        style="display:block;{{ $estaExpirado ? 'filter:grayscale(100%);opacity:0.6;' : '' }}"
                                                        loading="lazy"
                                                        onerror="this.remove();"
                                                    >
                                                @else
                                                    <div class="flex h-full w-full items-center justify-center bg-gradient-to-br from-gray-200 via-gray-100 to-gray-200" style="{{ $estaExpirado ? 'filter:grayscale(100%);opacity:0.6;' : '' }}">
                                                        <svg class="h-8 w-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7a4 4 0 014-4h10a4 4 0 014 4v10a4 4 0 01-4 4H7a4 4 0 01-4-4V7z" />
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11a4 4 0 11-8 0 4 4 0 018 0z" />
                                                        </svg>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>

                                        {{-- Contenido principal --}}
                                        <div class="flex-1 flex flex-col gap-2 min-w-0">
                                            {{-- Título --}}
                                            <h2 class="text-base font-bold leading-tight {{ $estaExpirado ? 'text-gray-500' : 'text-gray-900' }} line-clamp-2">
                                                {{ $titulo }}
                                            </h2>

                                            {{-- Precios y descuento --}}
                                            <div class="flex flex-wrap items-center gap-3">
                                                @if($precioNuevo)
                                                    <span class="font-extrabold leading-none {{ $estaExpirado ? 'text-gray-400' : 'text-pink-600' }}" style="font-size:1.25rem;">
                                                        {{ number_format($precioNuevo, 2, ',', '.') }}€
                                                    </span>
                                                @elseif(!empty($chollo->precio_nuevo))
                                                    <span class="font-extrabold leading-none {{ $estaExpirado ? 'text-gray-400' : 'text-pink-600' }}" style="font-size:1.25rem;">
                                                        {{ $chollo->precio_nuevo }}
                                                    </span>
                                                @endif

                                                @if($precioAntiguo || !empty($chollo->precio_antiguo))
                                                    <span class="text-xs text-gray-400 line-through">
                                                        {{ $precioAntiguo ? number_format($precioAntiguo, 2, ',', '.') . '€' : $chollo->precio_antiguo }}
                                                    </span>
                                                @endif

                                                @if($badgeTexto)
                                                    <span class="inline-flex items-center gap-0.5 rounded px-2 py-0.5 text-xs font-semibold" style="{{ $estaExpirado ? 'background-color:#e5e7eb;color:#6b7280;' : 'background-color:#16a34a;color:#ffffff;' }}">
                                                        {{ $badgeTexto }}
                                                    </span>
                                                @endif
                                            </div>

                                            {{-- Envío gratis --}}
                                            <div class="flex items-center gap-1 text-xs {{ $estaExpirado ? 'text-gray-400' : 'text-gray-600' }}">
                                                <img src="{{ asset('images/van.png') }}" loading="lazy" alt="Envío" style="width:12px;height:12px;">
                                                Envío gratis
                                            </div>

                                            {{-- Tienda --}}
                                            <div class="flex items-center gap-1 text-xs font-semibold {{ $estaExpirado ? 'text-gray-500' : 'text-blue-600' }}">
                                                Disponible en <span class="font-bold">{{ $tiendaNombre }}</span>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Botones de acción --}}
                                    <div class="px-3 pb-3 pt-2 flex flex-col gap-2">
                                        @if($tieneCuponCompleto)
                                            <div class="flex overflow-hidden rounded-lg border-2 border-dashed {{ $estaExpirado ? 'border-gray-300 bg-gray-100' : 'border-pink-500 bg-pink-500/10' }}">
                                                <button
                                                    type="button"
                                                    class="relative z-30 px-3 py-2 text-xs font-semibold uppercase tracking-wide js-stop-propagation {{ $estaExpirado ? 'text-gray-500 bg-white/40' : 'text-pink-600 bg-white/60' }}"
                                                    data-copy-code="{{ $cuponCodigo }}"
                                                    onclick="event.stopPropagation();"
                                                >
                                                    {{ $cuponCodigo }}
                                                </button>
                                                <a
                                                    href="{{ $urlTienda }}"
                                                    class="relative z-30 flex items-center justify-center gap-1.5 flex-1 px-4 py-2 text-sm font-semibold js-stop-propagation js-copy-go {{ $estaExpirado ? 'bg-gray-300 text-gray-600' : 'bg-pink-500 text-white' }}"
                                                    target="_blank"
                                                    rel="noopener sponsored"
                                                    data-copy-go="{{ $cuponCodigo }}"
                                                    onclick="event.stopPropagation();"
                                                >
                                                    <span class="js-copy-text">Copiar + Ir al chollo</span>
                                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 3h7v7m-1.5-5.5L10 16l-4-4-6 6" />
                                                    </svg>
                                                </a>
                                            </div>
                                            @if($comparadorUrl)
                                                <a
                                                    href="{{ $comparadorUrl }}"
                                                    class="relative z-30 inline-flex items-center justify-center gap-1.5 rounded-lg px-4 py-2 text-xs font-semibold shadow-sm js-stop-propagation {{ $estaExpirado ? 'text-gray-500 hover:brightness-100' : 'text-black hover:brightness-110' }}"
                                                    style="{{ $estaExpirado ? 'background-color:#e5e7eb;' : 'background-color:#7dd3fc;' }}"
                                                    target="_blank"
                                                    rel="noopener"
                                                    onclick="event.stopPropagation();"
                                                >
                                                    Ir al Komparador
                                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                                    </svg>
                                                </a>
                                            @endif
                                        @else
                                            @if($cuponCodigo || $cuponValor)
                                                <div class="flex items-center justify-between gap-2 rounded-lg border border-dashed border-pink-400 bg-pink-50 px-3 py-2">
                                                    <div class="text-xs {{ $estaExpirado ? 'text-gray-500' : 'text-pink-700' }}">
                                                        @if($cuponCodigo && $cuponValor)
                                                            Usa el cupón <span class="font-bold uppercase">{{ $cuponCodigo }}</span> y ahorra {{ $cuponValor }}€
                                                        @elseif($cuponCodigo)
                                                            Usa el cupón <span class="font-bold uppercase">{{ $cuponCodigo }}</span>
                                                        @else
                                                            Cupón disponible: <span class="font-bold">-{{ $cuponValor }}€</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endif

                                            <div class="flex flex-col gap-2">
                                                <a
                                                    href="{{ $urlTienda }}"
                                                    class="relative z-30 inline-flex items-center justify-center gap-1.5 rounded-lg px-4 py-3 text-sm font-semibold shadow-md js-stop-propagation {{ $estaExpirado ? 'bg-gray-300 text-gray-600' : 'bg-pink-500 text-white' }}"
                                                    target="_blank"
                                                    rel="noopener sponsored"
                                                    onclick="event.stopPropagation();"
                                                >
                                                    Ir al chollo
                                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                                    </svg>
                                                </a>
                                                @if($comparadorUrl)
                                                    <a
                                                        href="{{ $comparadorUrl }}"
                                                        class="relative z-30 inline-flex items-center justify-center gap-1.5 rounded-lg px-4 py-2 text-xs font-semibold shadow-sm js-stop-propagation {{ $estaExpirado ? 'text-gray-500 hover:brightness-100' : 'text-black hover:brightness-110' }}"
                                                        style="{{ $estaExpirado ? 'background-color:#e5e7eb;' : 'background-color:#7dd3fc;' }}"
                                                        target="_blank"
                                                        rel="noopener"
                                                        onclick="event.stopPropagation();"
                                                    >
                                                        Ir al Komparador
                                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                                        </svg>
                                                    </a>
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                {{-- VISTA ESCRITORIO (igual que antes) --}}
                                <div class="hidden lg:flex flex-row">
                                    <div class="relative border-r border-gray-100 bg-gray-50" style="width:202px;height:202px;padding:12px;box-sizing:border-box;display:flex;align-items:center;justify-content:center;">
                                        <div class="flex h-full w-full items-center justify-center overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-inner group-hover:scale-105 transition-transform">
                                            @if($tieneImagen)
                                                <img
                                                    src="{{ $imagen }}"
                                                    alt="{{ $titulo }}"
                                                    class="max-h-full max-w-full object-contain"
                                                    style="display:block;{{ $estaExpirado ? 'filter:grayscale(100%);opacity:0.6;' : '' }}"
                                                    loading="lazy"
                                                    onerror="this.remove();"
                                                >
                                            @else
                                                <div class="flex h-full w-full items-center justify-center bg-gradient-to-br from-gray-200 via-gray-100 to-gray-200" style="{{ $estaExpirado ? 'filter:grayscale(100%);opacity:0.6;' : '' }}">
                                                    <svg class="h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7a4 4 0 014-4h10a4 4 0 014 4v10a4 4 0 01-4 4H7a4 4 0 01-4-4V7z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11a4 4 0 11-8 0 4 4 0 018 0z" />
                                                    </svg>
                                                </div>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="flex flex-1 flex-col gap-5 p-6 md:p-8">
                                        <div class="flex flex-col gap-3">
                                            <div class="flex flex-wrap items-center gap-2 text-xs font-semibold uppercase tracking-wide">
                                                @if($esNuevo)
                                                    <span class="rounded-xl bg-emerald-500 px-3 py-1 text-white shadow-sm">Nuevo</span>
                                                @endif
                                                @if($estadoBadge)
                                                    <span class="ml-auto rounded-xl px-3 py-1 text-xs font-semibold {{ $estadoBadgeClase }} shadow-sm" @if($estadoCountdownTarget) data-countdown-target="{{ $estadoCountdownTarget }}" data-countdown-prefix="{{ $estadoCountdownPrefix }}" data-countdown-type="{{ $estadoCountdownType }}" @endif>{{ $estadoBadge }}</span>
                                                @endif
                                            </div>

                                            <h2 class="text-xl md:text-2xl font-bold leading-tight text-gray-900 transition group-hover:text-pink-600">
                                                {{ $titulo }}
                                            </h2>

                                            <div class="flex flex-wrap items-center gap-2">
                                                @if($precioNuevo)
                                                    <span class="font-extrabold leading-none {{ $estaExpirado ? 'text-gray-400' : 'text-pink-600' }}" style="font-size:1.7rem;">
                                                        {{ number_format($precioNuevo, 2, ',', '.') }}€
                                                    </span>
                                                @elseif(!empty($chollo->precio_nuevo))
                                                    <span class="font-extrabold leading-none {{ $estaExpirado ? 'text-gray-400' : 'text-pink-600' }}" style="font-size:2.35rem;">
                                                        {{ $chollo->precio_nuevo }}
                                                    </span>
                                                @endif

                                                @if($precioAntiguo || !empty($chollo->precio_antiguo))
                                                    <span class="text-base text-gray-400 line-through">
                                                        {{ $precioAntiguo ? number_format($precioAntiguo, 2, ',', '.') . '€' : $chollo->precio_antiguo }}
                                                    </span>
                                                @endif

                                                @if($badgeTexto)
                                                    <span class="inline-flex items-center gap-1 rounded-md px-3 py-1 text-sm font-semibold shadow-sm" style="{{ $estaExpirado ? 'background-color:#e5e7eb;color:#6b7280;' : 'background-color:#16a34a;color:#ffffff;' }}">
                                                        <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v8m0-8h3m-3 0H9m3-3a9 9 0 110 18 9 9 0 010-18z" />
                                                        </svg>
                                                        {{ $badgeTexto }}
                                                    </span>
                                                @endif

                                                <span class="flex items-center gap-2 px-3 py-1 text-[0.95rem] font-semibold {{ $estaExpirado ? 'text-gray-500' : '' }}" style="{{ $estaExpirado ? 'color:#6b7280;' : 'color:#0369a1;' }}">
                                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 7h14M5 12h9m-9 5h6" />
                                                    </svg>
                                                    {{ $tiendaNombre }}
                                                </span>
                                            </div>

                                            @if($descripcion)
                                                <p class="text-sm md:text-base leading-relaxed text-gray-600">
                                                    {{ $descripcion }}
                                                </p>
                                            @endif
                                        </div>

                                        <div class="flex flex-col gap-4">
                                            @if($tieneCuponCompleto)
                                                <div class="mt-4 flex flex-wrap items-center justify-end gap-3">
                                                    @if($comparadorUrl)
                                                        <a
                                                            href="{{ $comparadorUrl }}"
                                                            class="relative z-30 inline-flex items-center gap-2 rounded-xl px-6 py-3 font-semibold text-[0.95rem] shadow-lg transition js-stop-propagation {{ $estaExpirado ? 'text-gray-500 hover:brightness-100' : 'text-black hover:brightness-110' }}"
                                                            style="{{ $estaExpirado ? 'background-color:#e5e7eb;transform:scale(0.92,0.79);transform-origin:center;' : 'background-color:#7dd3fc;transform:scale(0.92,0.79);transform-origin:center;' }}"
                                                            target="_blank"
                                                            rel="noopener"
                                                        >
                                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                                            </svg>
                                                            Ir al Komparador
                                                        </a>
                                                    @endif

                                                    <div class="{{ $estaExpirado ? 'flex overflow-hidden rounded-xl border-2 border-dashed border-gray-300 bg-gray-100' : 'flex overflow-hidden rounded-xl border-2 border-dashed border-pink-500 bg-pink-500/10' }}">
                                                        <button
                                                            type="button"
                                                            class="relative z-30 px-4 py-3 text-sm font-semibold uppercase tracking-wide js-stop-propagation {{ $estaExpirado ? 'text-gray-500 bg-white/40' : 'text-pink-600 bg-white/60 hover:bg-white transition' }}"
                                                            data-copy-code="{{ $cuponCodigo }}"
                                                        >
                                                            {{ $cuponCodigo }}
                                                        </button>
                                                        <a
                                                            href="{{ $urlTienda }}"
                                                            class="relative z-30 flex items-center gap-2 px-6 py-3 text-base font-semibold js-stop-propagation js-copy-go {{ $estaExpirado ? 'bg-gray-300 text-gray-600 hover:bg-gray-300' : 'bg-pink-500 text-white transition hover:bg-pink-400' }}"
                                                            target="_blank"
                                                            rel="noopener sponsored"
                                                            data-copy-go="{{ $cuponCodigo }}"
                                                        >
                                                            <span class="js-copy-text">Copiar + Ir al chollo</span>
                                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 3h7v7m-1.5-5.5L10 16l-4-4-6 6" />
                                                            </svg>
                                                        </a>
                                                    </div>
                                                </div>
                                            @else
                                                @if($cuponCodigo || $cuponValor)
                                                <div class="flex items-center justify-between gap-4 rounded-2xl border border-dashed border-pink-400 bg-pink-50 px-4 py-3">
                                                        <div class="text-sm {{ $estaExpirado ? 'text-gray-500' : 'text-pink-700' }}">
                                                            @if($cuponCodigo && $cuponValor)
                                                                Usa el cupón <span class="font-bold uppercase">{{ $cuponCodigo }}</span> y ahorra {{ $cuponValor }}€
                                                            @elseif($cuponCodigo)
                                                                Usa el cupón <span class="font-bold uppercase">{{ $cuponCodigo }}</span>
                                                            @else
                                                                Cupón disponible: <span class="font-bold">-{{ $cuponValor }}€</span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                @endif

                                                <div class="mt-4 flex flex-wrap items-center justify-end gap-2">
                                                    @if($comparadorUrl)
                                                        <a
                                                            href="{{ $comparadorUrl }}"
                                                            class="relative z-30 inline-flex items-center gap-2 rounded-xl px-6 py-3 font-semibold text-[0.95rem] shadow-lg transition js-stop-propagation {{ $estaExpirado ? 'text-gray-500 hover:brightness-100' : 'text-black hover:brightness-110' }}"
                                                            style="{{ $estaExpirado ? 'background-color:#e5e7eb;transform:scale(0.92,0.79);transform-origin:center;' : 'background-color:#7dd3fc;transform:scale(0.92,0.79);transform-origin:center;' }}"
                                                            target="_blank"
                                                            rel="noopener"
                                                        >
                                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                                            </svg>
                                                            Ir al Komparador
                                                        </a>
                                                    @endif

                                                    <a
                                                        href="{{ $urlTienda }}"
                                                        class="relative z-30 inline-flex items-center gap-2 rounded-xl px-8 py-3 text-base font-semibold shadow-lg js-stop-propagation {{ $estaExpirado ? 'bg-gray-300 text-gray-600 hover:bg-gray-300' : 'bg-pink-500 text-white transition hover:bg-pink-400' }}"
                                                        target="_blank"
                                                        rel="noopener sponsored"
                                                    >
                                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                                        </svg>
                                                        Ir a la tienda
                                                    </a>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </article>
                        @endforeach
@if(!$esAjax)
                    </div>

                    <div id="scroll-feedback" class="flex items-center justify-center mt-6 text-sm text-gray-500 gap-2 hidden">
                        <svg class="w-4 h-4 animate-spin text-pink-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                        </svg>
                        Cargando más chollos…
                    </div>

                    <div
                        id="scroll-sentinel"
                        class="h-1 w-full"
                        data-next-page="{{ $chollos->nextPageUrl() ? $chollos->nextPageUrl() . (Str::contains($chollos->nextPageUrl(), '?') ? '&' : '?') . 'ajax=1' : '' }}"
                    ></div>
                @endif
            </section>

            <aside class="w-full lg:w-1/4 space-y-6 lg:space-y-8">
                <div id="sidebar-content" class="space-y-6 lg:space-y-8">
                    @if(isset($preciosHot) && $preciosHot && count($preciosHot->datos) > 0)
                        <div class="bg-white shadow rounded-lg p-4">
                            <div class="mb-3">
                                <h3 class="text-lg font-semibold text-gray-900">Precios Hot 🔥</h3>
                                <p class="text-xs text-gray-500 mt-1">Ofertas con mayores descuentos ahora mismo</p>
                            </div>
                            <div class="space-y-2">
                                @foreach($preciosHot->datos as $item)
                                    <a href="{{ añadirCam($item['url_producto']) }}" class="block">
                                        <div class="flex items-center space-x-3 p-2 bg-blue-50 rounded hover:bg-blue-100 transition-colors">
                                            <div class="flex-shrink-0">
                                                <img
                                                    src="{{ asset('images/' . $item['img_producto']) }}"
                                                    alt="{{ $item['producto_nombre'] }}"
                                                    class="w-10 h-10 object-contain rounded"
                                                    loading="lazy"
                                                >
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <div class="text-sm font-medium text-gray-900 truncate">{{ $item['producto_nombre'] }}</div>
                                                <div class="flex items-center justify-between mt-1">
                                                    <div class="text-sm font-bold text-pink-500">
                                                        <span class="text-xs text-gray-500">Desde </span>
                                                        {{ $item['precio_formateado'] }}
                                                    </div>
                                                    <div class="bg-red-500 text-white text-xs font-bold px-2 py-1 rounded-full">
                                                        -{{ (int) floatval(str_replace(',', '.', $item['porcentaje_diferencia'])) }}%
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
        </div>
    </main>

    <x-footer />

    <script>
        let countdownItems = [];
        let countdownInterval = null;

        function formatCountdown(ms) {
            const totalSeconds = Math.max(0, Math.ceil(ms / 1000));
            const days = Math.floor(totalSeconds / 86400);
            const hours = Math.floor((totalSeconds % 86400) / 3600);
            const minutes = Math.floor((totalSeconds % 3600) / 60);
            const seconds = totalSeconds % 60;

            const mm = String(minutes).padStart(2, '0');
            const ss = String(seconds).padStart(2, '0');
            const hh = String(hours).padStart(2, '0');

            if (days > 0) {
                const diasTexto = days === 1 ? '1 Día' : `${days} Días`;
                return `${diasTexto} y ${hh}:${mm}:${ss}`;
            }

            if (hours > 0) {
                return `${hh}:${mm}:${ss}`;
            }

            return `${String(minutes).padStart(2, '0')}:${ss}`;
        }

        function updateCountdownItem(item, now = Date.now()) {
            const diff = item.target - now;
            if (diff <= 0) {
                if (item.type === 'start') {
                    item.el.textContent = 'Disponible';
                    item.el.classList.remove('bg-amber-100', 'text-amber-700');
                    item.el.classList.add('bg-emerald-100', 'text-emerald-700');
                } else {
                    item.el.textContent = 'Finalizado';
                    item.el.classList.remove('bg-red-100', 'text-red-700');
                    item.el.classList.add('bg-red-200', 'text-red-800');
                }
                item.active = false;
                item.el.removeAttribute('data-countdown-target');
                return;
            }

            const formatted = formatCountdown(diff);
            const prefix = item.prefix ? item.prefix.trim() : '';
            item.el.textContent = prefix ? `${prefix} ${formatted}` : formatted;
        }

        function tickCountdowns() {
            const now = Date.now();
            countdownItems = countdownItems.filter(item => {
                if (!item.active) {
                    return false;
                }
                updateCountdownItem(item, now);
                return item.active;
            });
        }

        function initCountdowns(container = document) {
            const elements = container.querySelectorAll('[data-countdown-target]');
            elements.forEach(el => {
                if (el.dataset.countdownBound === 'true') {
                    return;
                }

                const targetTime = Date.parse(el.dataset.countdownTarget);
                if (Number.isNaN(targetTime)) {
                    return;
                }

                el.dataset.countdownBound = 'true';
                countdownItems.push({
                    el,
                    target: targetTime,
                    prefix: el.dataset.countdownPrefix || '',
                    type: el.dataset.countdownType || 'end',
                    active: true,
                });
                updateCountdownItem(countdownItems[countdownItems.length - 1]);
            });
        }

        function fallbackCopyText(value) {
            if (!value) {
                return;
            }

            const textarea = document.createElement('textarea');
            textarea.value = value;
            textarea.setAttribute('readonly', '');
            textarea.style.position = 'fixed';
            textarea.style.left = '-999999px';
            textarea.style.top = '-999999px';
            textarea.style.opacity = '0';
            document.body.appendChild(textarea);
            textarea.focus();
            textarea.select();
            textarea.setSelectionRange(0, textarea.value.length);

            try {
                document.execCommand('copy');
            } catch (error) {
            } finally {
                document.body.removeChild(textarea);
            }
        }

        function copyText(value) {
            if (!value) {
                return;
            }

            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(value).catch(() => fallbackCopyText(value));
            } else {
                fallbackCopyText(value);
            }
        }

        function animateCopyButton(link, onComplete) {
            if (!link) {
                onComplete?.();
                return;
            }

            if (link.dataset.copyAnimating === 'true') {
                onComplete?.();
                return;
            }

            link.dataset.copyAnimating = 'true';
            link.classList.add('pointer-events-none');

            const textWrapper = link.querySelector('.js-copy-text');
            if (!textWrapper) {
                setTimeout(() => {
                    onComplete?.();
                }, 600);
                return;
            }

            const baseText = textWrapper.dataset.originalText || textWrapper.textContent || '';
            const parts = baseText.split('Copiar');
            const originalText = parts.length > 1 ? `Copiado${parts.slice(1).join('Copiar')}` : baseText;
            textWrapper.dataset.originalText = baseText;
            link.dataset.copyTextState = 'split';
            textWrapper.dataset.originalText = originalText;

            const letters = Array.from(originalText);
            textWrapper.innerHTML = '';

            const spans = letters.map(letter => {
                const span = document.createElement('span');
                span.textContent = letter === ' ' ? '\u00A0' : letter;
                span.style.display = 'inline-block';
                span.style.transition = 'color 0.22s ease, transform 0.22s ease';
                span.style.color = window.getComputedStyle(link).color || 'inherit';
                textWrapper.appendChild(span);
                return span;
            });

            const successColor = '#22c55e';
            const immediateCount = Math.min('Copiado'.length, spans.length);

            spans.slice(0, immediateCount).forEach(span => {
                span.style.color = successColor;
            });

            const remaining = spans.slice(immediateCount);
            const totalDuration = 1000;
            const delayPer = remaining.length > 0 ? totalDuration / remaining.length : 0;

            remaining.forEach((span, index) => {
                setTimeout(() => {
                    span.style.color = successColor;
                }, Math.round(index * delayPer));
            });

            setTimeout(() => {
                link.classList.remove('pointer-events-none');
                delete link.dataset.copyAnimating;
                textWrapper.textContent = baseText;
                onComplete?.();
            }, totalDuration + 250);
        }

        function initCouponButtons(root = document) {
            root.querySelectorAll('[data-copy-code]').forEach(btn => {
                if (btn.dataset.copyBound === 'true') return;
                btn.dataset.copyBound = 'true';
                btn.addEventListener('click', (event) => {
                    event.stopPropagation();
                    const code = btn.getAttribute('data-copy-code');
                    copyText(code);
                });
            });

            root.querySelectorAll('[data-copy-go]').forEach(link => {
                if (link.dataset.copyGoBound === 'true') return;
                link.dataset.copyGoBound = 'true';
                link.addEventListener('click', (event) => {
                    event.stopPropagation();
                    const code = link.getAttribute('data-copy-go');
                    if (code) {
                        event.preventDefault();
                        copyText(code);
                        const href = link.href;
                        const target = link.getAttribute('target');
                        animateCopyButton(link, () => {
                            if (target === '_blank') {
                                window.open(href, '_blank', 'noopener');
                            } else {
                                window.location.href = href;
                            }
                        });
                    }
                });
            });
        }

        document.addEventListener('DOMContentLoaded', () => {
            document.addEventListener('click', (event) => {
                if (event.target.closest('.js-stop-propagation')) {
                    event.stopPropagation();
                }
            });

            const sentinel = document.getElementById('scroll-sentinel');
            const listContainer = document.getElementById('chollos-list');
            const feedback = document.getElementById('scroll-feedback');
            let nextPageUrl = sentinel?.dataset.nextPage || null;
            let isLoading = false;
            let loadsCount = 0;
            const MAX_LOADS = 6;

            initCountdowns(document);
            tickCountdowns();
            countdownInterval = setInterval(tickCountdowns, 1000);
            initCouponButtons(document);

            async function loadMoreChollos() {
                if (!nextPageUrl || isLoading) return;
                if (loadsCount >= MAX_LOADS) {
                    observer.disconnect();
                    feedback?.classList.add('hidden');
                    return;
                }

                isLoading = true;
                loadsCount += 1;

                feedback?.classList.remove('hidden');

                try {
                    const response = await fetch(nextPageUrl, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json, text/html' },
                    });

                    if (!response.ok) {
                        throw new Error('No se pudo cargar el siguiente bloque de chollos.');
                    }

                    const contentType = response.headers.get('content-type') || '';

                    let html = '';
                    let updatedNextPage = null;

                    if (contentType.includes('application/json')) {
                        const payload = await response.json();
                        html = payload.html ?? '';
                        updatedNextPage = payload.next_page ?? null;
                    } else {
                        html = await response.text();
                        updatedNextPage = null;
                        const url = new URL(nextPageUrl, window.location.origin);
                        const currentPage = parseInt(url.searchParams.get('page') || '1', 10);
                        if (!Number.isNaN(currentPage)) {
                            url.searchParams.set('page', currentPage + 1);
                            updatedNextPage = url.toString();
                        }
                    }

                    if (html.trim().length > 0 && listContainer) {
                        const template = document.createElement('template');
                        template.innerHTML = html;
                        listContainer.appendChild(template.content);
                        initCountdowns(listContainer);
                        tickCountdowns();
                        initCouponButtons(listContainer);
                    }

                    nextPageUrl = updatedNextPage;

                    if (!nextPageUrl) {
                        observer.disconnect();
                    }
                } catch (error) {
                    console.error(error);
                    const errorToast = document.createElement('div');
                    errorToast.className = 'max-w-sm mx-auto bg-red-100 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm shadow-md';
                    errorToast.textContent = 'No se pudieron cargar más chollos. Desplázate un poco y lo volveremos a intentar.';
                    feedback?.parentNode?.insertBefore(errorToast, feedback);
                    setTimeout(() => errorToast.remove(), 4000);
                } finally {
                    isLoading = false;
                    feedback?.classList.add('hidden');
                }
            }

            const observer = sentinel
                ? new IntersectionObserver((entries) => {
                    entries.forEach((entry) => {
                        if (entry.isIntersecting) {
                            loadMoreChollos();
                        }
                    });
                }, { rootMargin: '200px' })
                : null;

            if (observer && sentinel) {
                observer.observe(sentinel);
                if (!nextPageUrl) {
                    observer.disconnect();
                }
            }

        });
    </script>
</body>
</html>
@endif

