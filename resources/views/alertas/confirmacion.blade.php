<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmacion de alerta | Komparador</title>
    @vite('resources/css/app.css')
</head>
<body class="bg-gray-100 min-h-screen">
    <x-header />

    <main class="max-w-3xl mx-auto px-4 py-8">
        <div class="bg-white rounded-lg shadow p-6">
            <h1 class="text-2xl font-bold text-gray-900 mb-3">
                {{ $success ? 'Alerta confirmada' : 'No se pudo confirmar la alerta' }}
            </h1>

            <p class="{{ $success ? 'text-green-700' : 'text-red-700' }} text-sm mb-5">
                {{ $mensaje ?? '' }}
            </p>

            @if($success && isset($alerta))
                <div class="border border-gray-200 rounded-md p-4 bg-gray-50 text-sm text-gray-800 space-y-2">
                    @if(isset($producto) && $producto)
                        <p><strong>Producto:</strong> {{ $producto->nombre }}</p>
                    @elseif(isset($categoria) && $categoria)
                        <p><strong>Categoria:</strong> {{ $categoria->nombre }}</p>
                    @endif

                    @if(isset($especificacionesAgrupadas) && is_array($especificacionesAgrupadas) && count($especificacionesAgrupadas) > 0)
                        @foreach($especificacionesAgrupadas as $grupo => $valores)
                            <p><strong>{{ $grupo }}:</strong> {{ implode(', ', $valores) }}</p>
                        @endforeach
                    @endif

                    <p><strong>Precio limite:</strong> {{ number_format((float) $alerta->precio_limite, 2, ',', '.') }} €</p>
                </div>
            @endif

            <div class="mt-6">
                <a href="{{ route('home') }}"
                   class="inline-block bg-gray-900 text-white px-4 py-2 rounded hover:bg-gray-700 transition-colors">
                    Ir al inicio
                </a>
            </div>
        </div>
    </main>
</body>
</html>
