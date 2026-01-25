@php
    // Generar aviso interno cuando se muestra este error
    try {
        $url = request()->fullUrl();
        
        // Verificar si la URL contiene palabras que debemos ignorar
        $palabrasIgnorar = ['wordpress', 'wp-', 'pdata'];
        $urlLower = strtolower($url);
        $debeIgnorar = false;
        
        foreach ($palabrasIgnorar as $palabra) {
            if (str_contains($urlLower, strtolower($palabra))) {
                $debeIgnorar = true;
                break;
            }
        }
        
        // Solo generar aviso si no debe ignorarse
        if (!$debeIgnorar) {
            $hora = now();
            $ip = request()->ip();
            $textoAviso = "Un visitante ha visto un error 404 - Página no encontrada. URL: {$url} - Hora: {$hora} - IP: {$ip}";
            
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
        }
    } catch (\Exception $e) {
        \Log::error('Error al generar aviso en vista 404: ' . $e->getMessage());
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
    <div class="text-6xl mb-4">🔍</div>
<h1 class="text-3xl font-bold text-yellow-600 mb-2">404 - Página no encontrada</h1>
<p class="text-gray-700 mb-4 max-w-md">
  Parece que esta página se ha tomado unas vacaciones inesperadas. Vuelve al inicio.
</p>

  </main>

  <x-footer />

</body>
</html>
