@php
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

    $tieneImagen = !empty($chollo->imagen_grande);
    $imagen = $tieneImagen ? asset('images/' . $chollo->imagen_grande) : null;
    $titulo = $chollo->titulo ?? 'Chollo sin título';

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

    $tiendaNombre = optional($chollo->tienda)->nombre ?? 'Ver tienda';
    $fechaPublicacion = optional($chollo->created_at)->diffForHumans();

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
        $estadoBadge = 'Publicado hace ' . ($fechaPublicacion ?: 'recientemente');
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

    $tieneCuponCompleto = $cuponCodigo && $cuponValor;
    $urlTienda = añadirCam($chollo->url ?? '#');
    if ($ofertaRedireccion) {
        $urlTienda = $ofertaRedireccion;
    }

    $gastosEnvio = $chollo->gastos_envio ?? '';
@endphp

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
    <title>{{ $titulo }} | CholloPañales</title>
    <meta name="description" content="{{ Str::limit(strip_tags($chollo->descripcion ?? ''), 160) }}">
    <link rel="canonical" href="{{ $canonicalUrl }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="icon" type="image/png" href="{{ asset('images/icono.webp') }}">
</head>
<body class="bg-gray-50">
    <x-header />

    <main class="max-w-7xl mx-auto px-4 lg:px-6 xl:px-8 py-6 lg:py-10">
        <div class="flex flex-col lg:flex-row gap-6 lg:gap-8">
            <section class="w-full lg:w-3/4">
                <article class="bg-white rounded-[32px] border border-gray-200 shadow-lg overflow-hidden">
                    {{-- VERSIÓN MÓVIL --}}
                    <div class="lg:hidden">
                        <div class="p-4">
                            {{-- Imagen centrada arriba --}}
                            @if($tieneImagen)
                                <div class="relative mb-4 bg-gray-50 rounded-2xl border border-gray-200 mx-auto" style="width:100%;max-width:320px;height:200px;padding:12px;box-sizing:border-box;display:flex;align-items:center;justify-content:center;">
                                    <div class="flex h-full w-full items-center justify-center overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-inner">
                                        <img
                                            src="{{ $imagen }}"
                                            alt="{{ $titulo }}"
                                            class="max-h-full max-w-full object-contain"
                                            style="display:block;max-height:100%;max-width:100%;{{ $estaExpirado ? 'filter:grayscale(100%);opacity:0.6;' : '' }}"
                                            loading="lazy"
                                            onerror="this.remove();"
                                        >
                                    </div>
                                </div>
                            @endif

                            {{-- Badge de estado arriba a la derecha --}}
                            <div class="flex justify-end mb-3">
                                @if($estadoBadge)
                                    <span class="inline-flex items-center rounded-xl px-3 py-1 text-xs font-semibold {{ $estadoBadgeClase }} shadow-sm" @if($estadoCountdownTarget) data-countdown-target="{{ $estadoCountdownTarget }}" data-countdown-prefix="{{ $estadoCountdownPrefix }}" data-countdown-type="{{ $estadoCountdownType }}" @endif>
                                        {{ $estadoBadge }}
                                    </span>
                                @endif
                            </div>

                            {{-- Título --}}
                            <h1 class="text-xl font-bold leading-tight text-gray-900 mb-4">
                                {{ $titulo }}
                            </h1>

                            {{-- Precios --}}
                            <div class="flex items-baseline gap-2 mb-3 flex-wrap">
                                @if($precioNuevo)
                                    <span class="font-extrabold leading-none {{ $estaExpirado ? 'text-gray-400' : 'text-pink-600' }}" style="font-size:1.75rem;">
                                        {{ number_format($precioNuevo, 2, ',', '.') }}€
                                    </span>
                                @elseif(!empty($chollo->precio_nuevo))
                                    <span class="font-extrabold leading-none {{ $estaExpirado ? 'text-gray-400' : 'text-pink-600' }}" style="font-size:1.75rem;">
                                        {{ $chollo->precio_nuevo }}
                                    </span>
                                @endif

                                @if($precioAntiguo || !empty($chollo->precio_antiguo))
                                    <span class="text-lg text-gray-400 line-through">
                                        {{ $precioAntiguo ? number_format($precioAntiguo, 2, ',', '.') . '€' : $chollo->precio_antiguo }}
                                    </span>
                                @endif

                                @if($descuento)
                                    <span class="inline-flex items-center gap-1 rounded-md px-2 py-1 text-xs font-semibold shadow-sm whitespace-nowrap" style="{{ $estaExpirado ? 'background-color:#e5e7eb;color:#6b7280;' : 'background-color:#16a34a;color:#ffffff;' }}">
                                        <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v8m0-8h3m-3 0H9m3-3a9 9 0 110 18 9 9 0 010-18z" />
                                        </svg>
                                        -{{ $descuento }}%
                                    </span>
                                @endif
                            </div>

                            {{-- Envío y disponible en --}}
                            <div class="flex items-center gap-2 text-sm text-gray-600 flex-wrap mb-4">
                                @if($gastosEnvio)
                                    <div class="flex items-center gap-1">
                                        <img src="{{ asset('images/van.png') }}" loading="lazy" alt="Envío" style="width:12px;height:12px;">
                                        {{ $gastosEnvio }}
                                    </div>
                                @endif
                                @if($gastosEnvio)
                                    <span class="text-gray-300">|</span>
                                @endif
                                <div>
                                    Disponible en: <span class="font-semibold">{{ $tiendaNombre }}</span>
                                </div>
                            </div>

                            {{-- Botones de acción móvil --}}
                            <div class="flex flex-col gap-3 mt-4">
                                @if($tieneCuponCompleto)
                                    @if($comparadorUrl)
                                        <a
                                            href="{{ $comparadorUrl }}"
                                            class="relative z-30 inline-flex items-center justify-center gap-2 rounded-xl px-4 py-3 text-lg font-semibold shadow-lg transition js-stop-propagation {{ $estaExpirado ? 'text-gray-500 hover:brightness-100' : 'text-black hover:brightness-110' }}"
                                            style="{{ $estaExpirado ? 'background-color:#e5e7eb;transform:scale(0.92,0.79);transform-origin:center;' : 'background-color:#7dd3fc;transform:scale(0.92,0.79);transform-origin:center;' }}"
                                            target="_blank"
                                            rel="noopener"
                                        >
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                            </svg>
                                            Ir al Komparador
                                        </a>
                                    @endif

                                    <div class="w-full {{ $estaExpirado ? 'flex overflow-hidden rounded-xl border-2 border-dashed border-gray-300 bg-gray-100' : 'flex overflow-hidden rounded-xl border-2 border-dashed border-pink-500 bg-pink-500/10' }}">
                                        <button
                                            type="button"
                                            class="relative z-30 px-4 py-3 text-sm font-semibold uppercase tracking-wide js-stop-propagation flex-shrink-0 {{ $estaExpirado ? 'text-gray-500 bg-white/40' : 'text-pink-600 bg-white/60 hover:bg-white transition' }}"
                                            data-copy-code="{{ $cuponCodigo }}"
                                        >
                                            {{ $cuponCodigo }}
                                        </button>
                                        <a
                                            href="{{ $urlTienda }}"
                                            class="relative z-30 flex flex-1 items-center justify-center gap-2 px-6 py-3 text-base font-semibold js-stop-propagation js-copy-go {{ $estaExpirado ? 'bg-gray-300 text-gray-600 hover:bg-gray-300' : 'bg-pink-500 text-white transition hover:bg-pink-400' }}"
                                            target="_blank"
                                            rel="noopener sponsored"
                                            data-copy-go="{{ $cuponCodigo }}"
                                        >
                                            <span class="js-copy-text">Copiar + Ir a la tienda</span>
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 3h7v7m-1.5-5.5L10 16l-4-4-6 6" />
                                            </svg>
                                        </a>
                                    </div>
                                @else
                                    @if($cuponCodigo || $cuponValor)
                                        <div class="flex items-center justify-between gap-4 rounded-xl border border-dashed border-pink-400 bg-pink-50 px-3 py-2.5">
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
                                        @if($comparadorUrl)
                                            <a
                                                href="{{ $comparadorUrl }}"
                                                class="relative z-30 inline-flex items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold shadow-lg transition js-stop-propagation {{ $estaExpirado ? 'text-gray-500 hover:brightness-100' : 'text-black hover:brightness-110' }}"
                                                style="{{ $estaExpirado ? 'background-color:#e5e7eb;transform:scale(0.92,0.79);transform-origin:center;' : 'background-color:#7dd3fc;transform:scale(0.92,0.79);transform-origin:center;' }}"
                                                target="_blank"
                                                rel="noopener"
                                            >
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                                </svg>
                                                Ir al Komparador
                                            </a>
                                        @endif

                                        <a
                                            href="{{ $urlTienda }}"
                                            class="relative z-30 inline-flex items-center justify-center gap-2 rounded-xl px-6 py-3 text-base font-semibold shadow-lg js-stop-propagation {{ $estaExpirado ? 'bg-gray-300 text-gray-600 hover:bg-gray-300' : 'bg-pink-500 text-white transition hover:bg-pink-400' }}"
                                            target="_blank"
                                            rel="noopener sponsored"
                                        >
                                            Ir al chollo
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                            </svg>
                                        </a>
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- Descripción de la oferta móvil --}}
                        @if($chollo->descripcion)
                            <div class="border-t border-gray-200 p-4">
                                <h2 class="text-lg font-bold text-gray-900 mb-3">Descripción de la oferta</h2>
                                <div class="prose prose-sm max-w-none text-gray-700 text-sm">
                                    {!! $chollo->descripcion !!}
                                </div>
                            </div>
                        @endif

                        {{-- También te podría interesar - MÓVIL --}}
                        @if(isset($chollosRelacionados) && $chollosRelacionados->count() > 0)
                            <div class="border-t border-gray-200 p-4 lg:hidden">
                                <h2 class="text-xl font-bold text-gray-900 mb-4">También te podría interesar</h2>
                                <div class="overflow-x-auto pb-2" style="scrollbar-width: thin;">
                                    <div class="flex gap-4" style="width: max-content;">
                                        @foreach($chollosRelacionados as $cholloRel)
                                            @php
                                                $precioNuevo = null;
                                                if (!empty($cholloRel->precio_nuevo)) {
                                                    $precioNuevo = is_numeric($cholloRel->precio_nuevo)
                                                        ? (float) $cholloRel->precio_nuevo
                                                        : (float) str_replace(',', '.', preg_replace('/[^\d,\.]/', '', $cholloRel->precio_nuevo));
                                                }

                                                $precioAntiguo = null;
                                                if (!empty($cholloRel->precio_antiguo)) {
                                                    $precioAntiguo = is_numeric($cholloRel->precio_antiguo)
                                                        ? (float) $cholloRel->precio_antiguo
                                                        : (float) str_replace(',', '.', preg_replace('/[^\d,\.]/', '', $cholloRel->precio_antiguo));
                                                }

                                                $descuento = null;
                                                if ($precioNuevo && $precioAntiguo && $precioAntiguo > 0) {
                                                    $descuento = round((1 - ($precioNuevo / $precioAntiguo)) * 100);
                                                    if ($descuento <= 0) {
                                                        $descuento = null;
                                                    }
                                                } elseif (!empty($cholloRel->descuentos)) {
                                                    $descuento = (int) filter_var($cholloRel->descuentos, FILTER_SANITIZE_NUMBER_INT);
                                                    if ($descuento === 0) {
                                                        $descuento = null;
                                                    }
                                                }

                                                $imagenRel = $cholloRel->imagen_pequena ? asset('images/' . $cholloRel->imagen_pequena) : asset('images/chollos/default.jpg');
                                                $tituloRel = $cholloRel->titulo;
                                                $urlRel = route('chollos.show', $cholloRel->slug);
                                            @endphp
                                            <a href="{{ $urlRel }}" class="block flex-shrink-0 w-48 bg-white rounded-lg border border-gray-200 shadow-sm hover:shadow-md transition-shadow overflow-hidden">
                                                <div class="relative" style="width: 100%; height: 120px;">
                                                    <img src="{{ $imagenRel }}" alt="{{ $tituloRel }}" class="w-full h-full object-contain bg-gray-50" loading="lazy">
                                                    @if($descuento)
                                                        <div class="absolute top-2 right-2 bg-red-500 text-white text-xs font-bold px-2 py-1 rounded">
                                                            -{{ $descuento }}%
                                                        </div>
                                                    @endif
                                                </div>
                                                <div class="p-3">
                                                    <h3 class="text-sm font-semibold text-gray-900 mb-2 line-clamp-2" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                                        {{ $tituloRel }}
                                                    </h3>
                                                    <div class="flex items-baseline gap-2">
                                                        @if($precioNuevo)
                                                            <span class="text-lg font-bold text-pink-500">{{ number_format($precioNuevo, 2, ',', '.') }}€</span>
                                                        @endif
                                                        @if($precioAntiguo && $precioAntiguo > $precioNuevo)
                                                            <span class="text-xs text-gray-500 line-through">{{ number_format($precioAntiguo, 2, ',', '.') }}€</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </a>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>

                    {{-- VERSIÓN DESKTOP (sin cambios) --}}
                    <div class="hidden lg:block">
                        <div class="p-6 md:p-8">
                            {{-- Contenedor principal con imagen y contenido --}}
                            <div class="flex flex-col lg:flex-row gap-6 lg:gap-8">
                                {{-- Imagen del producto --}}
                                @if($tieneImagen)
                                    <div class="flex-shrink-0 relative bg-gray-50 rounded-2xl border border-gray-200" style="width:260px;height:260px;padding:12px;box-sizing:border-box;display:flex;align-items:center;justify-content:center;">
                                        <div class="flex h-full w-full items-center justify-center overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-inner">
                                            <img
                                                src="{{ $imagen }}"
                                                alt="{{ $titulo }}"
                                                class="max-h-full max-w-full object-contain"
                                                style="display:block;{{ $estaExpirado ? 'filter:grayscale(100%);opacity:0.6;' : '' }}"
                                                loading="lazy"
                                                onerror="this.remove();"
                                            >
                                        </div>
                                    </div>
                                @else
                                    <div class="flex-shrink-0 relative bg-gray-50 rounded-2xl border border-gray-200" style="width:260px;height:260px;padding:12px;box-sizing:border-box;display:flex;align-items:center;justify-content:center;">
                                        <div class="flex h-full w-full items-center justify-center overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-inner bg-gradient-to-br from-gray-200 via-gray-100 to-gray-200" style="{{ $estaExpirado ? 'filter:grayscale(100%);opacity:0.6;' : '' }}">
                                            <svg class="h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7a4 4 0 014-4h10a4 4 0 014 4v10a4 4 0 01-4 4H7a4 4 0 01-4-4V7z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11a4 4 0 11-8 0 4 4 0 018 0z" />
                                            </svg>
                                        </div>
                                    </div>
                                @endif

                                {{-- Contenido principal --}}
                                <div class="flex-1 flex flex-col gap-4">
                                    {{-- Título con badge de estado a la derecha --}}
                                    <div class="flex items-start justify-between gap-4">
                                        <h1 class="text-2xl md:text-3xl lg:text-4xl font-bold leading-tight text-gray-900 flex-1">
                                            {{ $titulo }}
                                        </h1>
                                        @if($estadoBadge)
                                            <span class="inline-flex items-center rounded-xl px-3 py-1 text-xs font-semibold {{ $estadoBadgeClase }} shadow-sm flex-shrink-0" @if($estadoCountdownTarget) data-countdown-target="{{ $estadoCountdownTarget }}" data-countdown-prefix="{{ $estadoCountdownPrefix }}" data-countdown-type="{{ $estadoCountdownType }}" @endif>
                                                {{ $estadoBadge }}
                                            </span>
                                        @endif
                                    </div>

                                    {{-- Precios --}}
                                    <div class="flex flex-wrap items-baseline gap-3">
                                        @if($precioNuevo)
                                            <span class="font-extrabold leading-none {{ $estaExpirado ? 'text-gray-400' : 'text-pink-600' }}" style="font-size:2rem;">
                                                {{ number_format($precioNuevo, 2, ',', '.') }}€
                                            </span>
                                        @elseif(!empty($chollo->precio_nuevo))
                                            <span class="font-extrabold leading-none {{ $estaExpirado ? 'text-gray-400' : 'text-pink-600' }}" style="font-size:2rem;">
                                                {{ $chollo->precio_nuevo }}
                                            </span>
                                        @endif

                                        @if($precioAntiguo || !empty($chollo->precio_antiguo))
                                            <span class="text-xl text-gray-400 line-through">
                                                {{ $precioAntiguo ? number_format($precioAntiguo, 2, ',', '.') . '€' : $chollo->precio_antiguo }}
                                            </span>
                                        @endif

                                        @if($descuento)
                                            <span class="inline-flex items-center gap-1 rounded-md px-3 py-1 text-base font-semibold shadow-sm" style="{{ $estaExpirado ? 'background-color:#e5e7eb;color:#6b7280;' : 'background-color:#16a34a;color:#ffffff;' }}">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v8m0-8h3m-3 0H9m3-3a9 9 0 110 18 9 9 0 010-18z" />
                                                </svg>
                                                -{{ $descuento }}%
                                            </span>
                                        @endif
                                    </div>

                                    {{-- Envío y disponible en --}}
                                    <div class="flex items-center gap-3 text-sm text-gray-600 flex-wrap">
                                        @if($gastosEnvio)
                                            <div class="flex items-center gap-2">
                                                <img src="{{ asset('images/van.png') }}" loading="lazy" alt="Envío" class="w-4 h-4">
                                                {{ $gastosEnvio }}
                                            </div>
                                        @endif
                                        @if($gastosEnvio)
                                            <span class="text-gray-300">|</span>
                                        @endif
                                        <div>
                                            Disponible en: <span class="font-semibold">{{ $tiendaNombre }}</span>
                                        </div>
                                    </div>

                                    {{-- Botones de acción --}}
                                    <div class="flex flex-col gap-4 mt-auto">
                                    @if($tieneCuponCompleto)
                                        <div class="flex flex-wrap items-center gap-3">
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
                                                    <span class="js-copy-text">Copiar + Ir a la tienda</span>
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

                                        <div class="flex flex-wrap items-center gap-2">
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
                                                Ir al chollo
                                            </a>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- Descripción de la oferta desktop --}}
                        @if($chollo->descripcion)
                            <div class="border-t border-gray-200 p-6 md:p-8">
                                <h2 class="text-xl font-bold text-gray-900 mb-4">Descripción de la oferta</h2>
                                <div class="prose prose-sm max-w-none text-gray-700">
                                    {!! $chollo->descripcion !!}
                                </div>
                            </div>
                        @endif

                        {{-- También te podría interesar - DESKTOP --}}
                        @if(isset($chollosRelacionados) && $chollosRelacionados->count() > 0)
                            <div class="border-t border-gray-200 p-6 md:p-8">
                                <h2 class="text-2xl font-bold text-gray-900 mb-6">También te podría interesar</h2>
                                <div class="overflow-x-auto pb-4" style="scrollbar-width: thin;">
                                    <div class="flex gap-6" style="width: max-content;">
                                        @foreach($chollosRelacionados as $cholloRel)
                                            @php
                                                $precioNuevo = null;
                                                if (!empty($cholloRel->precio_nuevo)) {
                                                    $precioNuevo = is_numeric($cholloRel->precio_nuevo)
                                                        ? (float) $cholloRel->precio_nuevo
                                                        : (float) str_replace(',', '.', preg_replace('/[^\d,\.]/', '', $cholloRel->precio_nuevo));
                                                }

                                                $precioAntiguo = null;
                                                if (!empty($cholloRel->precio_antiguo)) {
                                                    $precioAntiguo = is_numeric($cholloRel->precio_antiguo)
                                                        ? (float) $cholloRel->precio_antiguo
                                                        : (float) str_replace(',', '.', preg_replace('/[^\d,\.]/', '', $cholloRel->precio_antiguo));
                                                }

                                                $descuento = null;
                                                if ($precioNuevo && $precioAntiguo && $precioAntiguo > 0) {
                                                    $descuento = round((1 - ($precioNuevo / $precioAntiguo)) * 100);
                                                    if ($descuento <= 0) {
                                                        $descuento = null;
                                                    }
                                                } elseif (!empty($cholloRel->descuentos)) {
                                                    $descuento = (int) filter_var($cholloRel->descuentos, FILTER_SANITIZE_NUMBER_INT);
                                                    if ($descuento === 0) {
                                                        $descuento = null;
                                                    }
                                                }

                                                $imagenRel = $cholloRel->imagen_pequena ? asset('images/' . $cholloRel->imagen_pequena) : asset('images/chollos/default.jpg');
                                                $tituloRel = $cholloRel->titulo;
                                                $urlRel = route('chollos.show', $cholloRel->slug);
                                            @endphp
                                            <a href="{{ $urlRel }}" class="block flex-shrink-0 w-36 bg-white rounded-lg border border-gray-200 shadow-sm hover:shadow-md transition-shadow overflow-hidden">
                                                <div class="relative" style="width: 140px; height: 79px;">
                                                    <img src="{{ $imagenRel }}" alt="{{ $tituloRel }}" class="w-full h-full object-contain bg-gray-50" loading="lazy">
                                                    @if($descuento)
                                                        <div class="absolute top-2 right-2 bg-red-500 text-white text-xs font-bold px-2 py-1 rounded">
                                                            -{{ $descuento }}%
                                                        </div>
                                                    @endif
                                                </div>
                                                <div class="p-4">
                                                    <h3 class="text-sm font-semibold text-gray-900 mb-2 line-clamp-2" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                                        {{ $tituloRel }}
                                                    </h3>
                                                    <div class="flex items-baseline gap-2">
                                                        @if($precioNuevo)
                                                            <span class="text-xl font-bold text-pink-500">{{ number_format($precioNuevo, 2, ',', '.') }}€</span>
                                                        @endif
                                                        @if($precioAntiguo && $precioAntiguo > $precioNuevo)
                                                            <span class="text-sm text-gray-500 line-through">{{ number_format($precioAntiguo, 2, ',', '.') }}€</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </a>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </article>
            </section>

            {{-- Sidebar con productos hot --}}
            <aside class="w-full lg:w-1/4 space-y-6 lg:space-y-8">
                <div id="sidebar-content" class="space-y-6 lg:space-y-8">
                    {{-- Productos Rebajados (Precios Hot de la categoría del chollo) --}}
                    @if(isset($productosRebajados) && $productosRebajados->count() > 0)
                        <div class="bg-white shadow rounded-lg p-4">
                            <h3 class="text-lg font-semibold mb-3 text-gray-800 border-b border-gray-200 pb-2">Productos Rebajados</h3>
                            <div class="space-y-2">
                                @foreach($productosRebajados as $item)
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

            initCountdowns(document);
            tickCountdowns();
            countdownInterval = setInterval(tickCountdowns, 1000);
            initCouponButtons(document);
        });
    </script>
</body>
</html>

