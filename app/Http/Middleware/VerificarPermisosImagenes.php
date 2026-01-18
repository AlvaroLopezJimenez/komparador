<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class VerificarPermisosImagenes
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Closure): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Verificar que las carpetas de imágenes existan y tengan permisos de escritura
        $carpetasImagenes = [
            'panales' => public_path('images/panales'),
            'categorias' => public_path('images/categorias'),
            'tiendas' => public_path('images/tiendas')
        ];

        foreach ($carpetasImagenes as $nombre => $ruta) {
            // Crear carpeta si no existe
            if (!is_dir($ruta)) {
                try {
                    if (!mkdir($ruta, 0755, true)) {
                        Log::warning("No se pudo crear la carpeta de imágenes: {$ruta}");
                    }
                } catch (\Exception $e) {
                    Log::error("Error al crear carpeta de imágenes {$ruta}: " . $e->getMessage());
                }
            }

            // Verificar permisos de escritura
            if (is_dir($ruta) && !is_writable($ruta)) {
                Log::warning("La carpeta de imágenes no tiene permisos de escritura: {$ruta}");
                
                // Intentar cambiar permisos (solo en desarrollo)
                if (config('app.debug')) {
                    try {
                        chmod($ruta, 0755);
                        Log::info("Permisos de carpeta {$ruta} cambiados a 0755");
                    } catch (\Exception $e) {
                        Log::error("No se pudieron cambiar los permisos de la carpeta {$ruta}: " . $e->getMessage());
                    }
                }
            }
        }

        return $next($request);
    }
}
