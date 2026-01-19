@php
    // Generar aviso interno cuando se muestra este error
    try {
        $url = request()->fullUrl();
        $hora = now();
        $ip = request()->ip();
        $textoAviso = "Un visitante ha visto un error 500 - Error interno del servidor. URL: {$url} - Hora: {$hora} - IP: {$ip}";
        
        \DB::table('avisos')->insert([
            'texto_aviso'     => $textoAviso,
            'fecha_aviso'     => $hora,
            'user_id'         => 1,
            'avisoable_type'  => 'Interno',
            'avisoable_id'    => 0,
            'oculto'          => 0,
            'created_at'      => $hora,
            'updated_at'      => $hora,
        ]);
    } catch (\Exception $e) {
        \Log::error('Error al generar aviso en vista 500: ' . $e->getMessage());
    }
@endphp

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>¡Vaya! Algo salió mal</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 text-gray-800 min-h-screen flex flex-col">

  <x-header />

  <main class="flex-grow flex flex-col justify-center items-center px-4 text-center">
    <div class="text-6xl mb-4">💥</div>
    <h1 class="text-3xl font-bold text-red-600 mb-2">500 - Error interno del servidor</h1>
    <p class="text-gray-700 mb-4 max-w-md">
      Algo se ha roto en los engranajes del sistema. Nuestro técnico favorito ya está en camino.
    </p>
    <a href="{{ route('home') }}" class="px-6 py-2 bg-pink-500 text-white font-semibold rounded hover:bg-pink-600 transition">
      Volver al inicio
    </a>
  </main>

  <x-footer />

</body>
</html>
