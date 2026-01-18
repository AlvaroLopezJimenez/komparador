<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alerta Cancelada - Komparador.com</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="icon" type="image/png" href="{{ asset('images/icono.webp') }}">
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-md mx-auto bg-white rounded-lg shadow-md p-6">
            <div class="text-center mb-6">
                <h1 class="text-2xl font-bold text-gray-800 mb-2">
                    @if($success ?? false)
                        Alerta Cancelada
                    @else
                        Error
                    @endif
                </h1>
            </div>

            @if($success ?? false)
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    {{ $mensaje }}
                </div>

                @if(isset($producto))
                    <div class="bg-gray-50 rounded-lg p-4 mb-6">
                        <h2 class="font-semibold text-lg text-gray-800 mb-2">{{ $producto->nombre }}</h2>
                        <p class="text-sm text-gray-600">Precio actual: {{ number_format($producto->precio, 2, ',', '.') }}€</p>
                    </div>
                @endif

                <div class="flex justify-center">
                    @if(isset($producto))
                        <a href="{{ $producto->categoria->construirUrlCategorias($producto->slug) }}" 
                           class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded-md transition-colors duration-200">
                            Volver al producto
                        </a>
                    @else
                        <a href="/" 
                           class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded-md transition-colors duration-200">
                            Ir a la página principal
                        </a>
                    @endif
                </div>
            @else
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    {{ $mensaje }}
                </div>

                <div class="flex justify-center">
                    <a href="/" 
                       class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded-md transition-colors duration-200">
                        Ir a la página principal
                    </a>
                </div>
            @endif

            <div class="mt-6 text-center">
                <p class="text-xs text-gray-500">
                    @if($success ?? false)
                        Gracias por usar Komparador.com. Si en el futuro quieres recibir alertas de precio, puedes crear una nueva alerta desde la página del producto.
                    @else
                        Si tienes algún problema, por favor contacta con nosotros.
                    @endif
                </p>
            </div>
        </div>
    </div>
</body>
</html>












