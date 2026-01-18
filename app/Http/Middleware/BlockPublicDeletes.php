<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BlockPublicDeletes
{
    /**
     * Handle an incoming request.
     * Bloquea métodos DELETE en rutas públicas (no autenticadas)
     * Excepción: cancelar alerta (usa token único y es seguro)
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Si no está autenticado y es método DELETE, bloquear
        if (!$request->user() && $request->isMethod('delete')) {
            // Excepción: cancelar alerta (usa token único y es seguro)
            if ($request->is('cancelar-alerta')) {
                return $next($request);
            }
            
            abort(403, 'Operación no permitida');
        }

        return $next($request);
    }
}












