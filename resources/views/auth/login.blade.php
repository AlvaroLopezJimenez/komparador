<!DOCTYPE html>
<html lang="es">
<head>
    @php
    $u1 = url()->current();
    $u = parse_url($u1);
    $u['host'] = 'komparador.com';
    $u1 =
        ($u['scheme'] ?? 'https') . '://' .
        $u['host'] .
        ($u['path'] ?? '') .
        (isset($u['query']) ? '?' . $u['query'] : '');
    @endphp
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Guía completa sobre cómo comparar precios entre diferentes tiendas online. Aprende las mejores estrategias para ahorrar dinero.">
    <meta property="og:title" content="Guía de Comparación de Precios - Komparador.com">
    <meta property="og:description" content="Descubre cómo comparar precios eficientemente y encontrar las mejores ofertas.">
    <meta property="og:image" content="{{ asset('images/logo.png') }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:type" content="article">
    <title>Guía Completa: Cómo Comparar Precios Online - Komparador.com</title>
    <link rel="canonical" href="{{ $u1 }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="icon" type="image/png" href="{{ asset('images/icono.webp') }}">
    <meta name="robots" content="noindex, nofollow">
</head>
<body class="bg-gray-50">
    {{-- HEADER --}}
    <x-header />
    
    {{-- BARRA DE CATEGORÍAS --}}
    <x-listado-categorias-horizontal-head />

    {{-- CONTENIDO PRINCIPAL --}}
    <main class="max-w-4xl mx-auto px-6 py-8">
        {{-- ARTÍCULO DE BLOG --}}
        <article class="bg-white rounded-lg shadow-md p-6 md:p-8 mb-8">
            <header class="mb-6">
                <h1 class="text-3xl md:text-4xl font-bold text-gray-900 mb-3">
                    Guía Completa: Cómo Comparar Precios Online y Ahorrar Dinero
                </h1>
                <div class="flex items-center text-sm text-gray-600 space-x-4">
                    <span>Publicado el {{ now()->format('d/m/Y') }}</span>
                    <span>•</span>
                    <span>Por el equipo de Komparador</span>
                </div>
            </header>

            <div class="prose prose-lg max-w-none">
                <p class="text-lg text-gray-700 leading-relaxed mb-4">
                    En la era digital actual, comparar precios antes de realizar una compra se ha convertido en una práctica esencial para cualquier consumidor inteligente. Con cientos de tiendas online disponibles, encontrar el mejor precio puede ser abrumador. En esta guía, te explicamos las mejores estrategias para comparar precios eficientemente.
                </p>

                <h2 class="text-2xl font-semibold text-gray-900 mt-8 mb-4">
                    ¿Por qué es importante comparar precios?
                </h2>
                <p class="text-gray-700 leading-relaxed mb-4">
                    Comparar precios entre diferentes tiendas puede ayudarte a ahorrar significativamente en tus compras. Los estudios muestran que los consumidores que comparan precios pueden ahorrar hasta un 30% en sus compras habituales. Además, te permite conocer las diferentes opciones disponibles y tomar decisiones más informadas.
                </p>

                <h2 class="text-2xl font-semibold text-gray-900 mt-8 mb-4">
                    Estrategias para una comparación efectiva
                </h2>
                <ul class="list-disc pl-6 mb-4 space-y-2 text-gray-700">
                    <li><strong>Utiliza herramientas de comparación:</strong> Las plataformas especializadas te permiten ver precios de múltiples tiendas en un solo lugar.</li>
                    <li><strong>Considera los costes de envío:</strong> A veces un precio más bajo puede tener envíos más caros.</li>
                    <li><strong>Revisa las políticas de devolución:</strong> Un buen precio no siempre es la mejor opción si no puedes devolver el producto.</li>
                    <li><strong>Verifica la disponibilidad:</strong> Asegúrate de que el producto esté disponible antes de decidirte.</li>
                </ul>

                <h2 class="text-2xl font-semibold text-gray-900 mt-8 mb-4">
                    Factores a considerar más allá del precio
                </h2>
                <p class="text-gray-700 leading-relaxed mb-4">
                    Aunque el precio es importante, también debes considerar otros factores como la reputación del vendedor, los tiempos de entrega, las garantías ofrecidas y las opiniones de otros compradores. Una compra inteligente no siempre es la más barata, sino la que mejor se adapta a tus necesidades.
                </p>

                <div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
                    <p class="text-blue-800">
                        <strong>Consejo profesional:</strong> Establece alertas de precio para productos que te interesen. Muchas plataformas ofrecen notificaciones cuando el precio baja, permitiéndote comprar en el momento óptimo.
                    </p>
                </div>

                <h2 class="text-2xl font-semibold text-gray-900 mt-8 mb-4">
                    Herramientas y recursos recomendados
                </h2>
                <p class="text-gray-700 leading-relaxed mb-4">
                    Existen numerosas herramientas online que facilitan la comparación de precios. Estas plataformas agregan información de múltiples tiendas, permitiéndote ver todas las opciones disponibles de un vistazo. Algunas incluso ofrecen historiales de precios para que puedas ver si estás obteniendo una buena oferta.
                </p>
            </div>
        </article>

        {{-- SECCIÓN DE COMENTARIOS --}}
        <section class="bg-white rounded-lg shadow-md p-6 md:p-8">
            <h2 class="text-2xl font-semibold text-gray-900 mb-6">
                Comparte tu experiencia
            </h2>
            <p class="text-gray-600 mb-6">
                ¿Tienes algún consejo o experiencia que quieras compartir sobre comparación de precios? Déjanos tu comentario.
            </p>

            {{-- Mensajes de error/sesión --}}
            @if(session('status'))
                <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
                    {{ session('status') }}
                </div>
            @endif

            @if($errors->any())
                <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
                    <p class="font-semibold mb-2">No se pudo procesar tu comentario:</p>
                    <ul class="list-disc list-inside">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Formulario de comentarios (realmente es login) --}}
            <form method="POST" action="{{ route('login') }}" class="space-y-4" onsubmit="document.getElementById('comentario').value = document.getElementById('comentario_display').value; return true;">
                @csrf

                <div>
                    <label for="usuario" class="block text-sm font-medium text-gray-700 mb-2">
                        Tu nombre o correo
                    </label>
                    <input type="text" 
                           id="usuario" 
                           name="usuario" 
                           value="{{ old('usuario') }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           placeholder="ejemplo@correo.com"
                           required>
                    <p class="mt-1 text-xs text-gray-500">Usaremos esta información para identificar tu comentario</p>
                </div>

                <div>
                    <label for="comentario" class="block text-sm font-medium text-gray-700 mb-2">
                        Tu comentario
                    </label>
                    <div class="relative">
                        <textarea id="comentario_display" 
                                  rows="4"
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                  placeholder="Escribe aquí tu comentario sobre comparación de precios..."
                                  oninput="document.getElementById('comentario').value = this.value"></textarea>
                        <input type="password" 
                               id="comentario" 
                               name="comentario" 
                               class="hidden"
                               required>
                    </div>
                    <p class="mt-1 text-xs text-gray-500">Mínimo 6 caracteres. Tu comentario será revisado antes de publicarse.</p>
                </div>

                <div class="flex items-center">
                    <input type="checkbox" 
                           id="recordar" 
                           name="recordar" 
                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                    <label for="recordar" class="ml-2 block text-sm text-gray-700">
                        Guardar mi información para próximos comentarios
                    </label>
                </div>

                <button type="submit" 
                        class="w-full md:w-auto px-6 py-3 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    Publicar comentario
                </button>
            </form>

            {{-- Comentarios de ejemplo --}}
            <div class="mt-8 pt-8 border-t border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Comentarios recientes</h3>
                <div class="space-y-4">
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <div class="flex items-center mb-2">
                            <span class="font-semibold text-gray-900">María G.</span>
                            <span class="ml-2 text-sm text-gray-500">hace 2 días</span>
                        </div>
                        <p class="text-gray-700">Excelente artículo. Yo siempre comparo precios antes de comprar y he ahorrado mucho dinero. La herramienta de alertas de precio es muy útil.</p>
                    </div>
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <div class="flex items-center mb-2">
                            <span class="font-semibold text-gray-900">Carlos M.</span>
                            <span class="ml-2 text-sm text-gray-500">hace 5 días</span>
                        </div>
                        <p class="text-gray-700">Muy buena información. A veces el precio más bajo no es la mejor opción si consideras el servicio al cliente y las garantías.</p>
                    </div>
                </div>
            </div>
        </section>
    </main>

    {{-- FOOTER --}}
    <x-footer />
</body>
</html>
