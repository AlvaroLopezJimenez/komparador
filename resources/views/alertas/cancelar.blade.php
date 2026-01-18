<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cancelar Alerta de Precio - Komparador.com</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="icon" type="image/png" href="{{ asset('images/icono.webp') }}">
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-md mx-auto bg-white rounded-lg shadow-md p-6">
            <div class="text-center mb-6">
                <h1 class="text-2xl font-bold text-gray-800 mb-2">Cancelar Alerta de Precio</h1>
                <p class="text-gray-600">Confirma que quieres cancelar tu alerta para este producto</p>
            </div>

            @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    {{ session('error') }}
                </div>
            @endif

            <div class="bg-gray-50 rounded-lg p-4 mb-6">
                <h2 class="font-semibold text-lg text-gray-800 mb-2">{{ $producto->nombre }}</h2>
                <p class="text-sm text-gray-600">Precio actual: {{ number_format($producto->precio, 2, ',', '.') }}€</p>
            </div>

            <form method="POST" action="{{ route('alertas.cancelar-procesar') }}" class="space-y-4">
                @csrf
                <input type="hidden" name="token" value="{{ $alerta->token_cancelacion }}">
                
                <div>
                    <label for="correo" class="block text-sm font-medium text-gray-700 mb-1">Correo electrónico</label>
                    <input type="email" id="correo" name="correo" value="{{ $alerta->correo }}" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           readonly>
                    <p class="text-xs text-gray-500 mt-1">Este es el correo asociado a tu alerta</p>
                </div>

                <div class="flex items-start space-x-2">
                    <input type="checkbox" id="confirmacion" name="confirmacion" required 
                           class="mt-1 h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                    <label for="confirmacion" class="text-sm text-gray-700">
                        Confirmo que quiero cancelar mi alerta de precio para este producto
                    </label>
                </div>

                @error('confirmacion')
                    <p class="text-red-500 text-sm">{{ $message }}</p>
                @enderror

                <div class="flex space-x-3">
                    <a href="{{ $producto->categoria->construirUrlCategorias($producto->slug) }}" 
                       class="flex-1 bg-gray-500 hover:bg-gray-600 text-white font-semibold py-2 px-4 rounded-md transition-colors duration-200 text-center">
                        Volver al producto
                    </a>
                    <button type="submit" 
                            class="flex-1 bg-red-500 hover:bg-red-600 text-white font-semibold py-2 px-4 rounded-md transition-colors duration-200">
                        Cancelar Alerta
                    </button>
                </div>
            </form>

            <div class="mt-6 text-center">
                <p class="text-xs text-gray-500">
                    Al cancelar tu alerta, ya no recibirás más notificaciones cuando el precio de este producto baje.
                </p>
            </div>
        </div>
    </div>
</body>
</html>





































