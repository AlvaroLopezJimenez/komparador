<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        
        // Prevenir MIME type sniffing (evita que el navegador adivine el tipo de archivo)
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        
        // Prevenir clickjacking (evita que tu página se cargue en un iframe)
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        
        // Protección XSS básica (navegadores antiguos)
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        
        // Controlar qué información se envía en el header Referer
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        
        // Solo en producción: Forzar HTTPS (HSTS)
        if (config('app.env') === 'production' && $request->secure()) {
            $response->headers->set(
                'Strict-Transport-Security', 
                'max-age=31536000; includeSubDomains; preload'
            );
        }
        
        return $response;
    }
}












