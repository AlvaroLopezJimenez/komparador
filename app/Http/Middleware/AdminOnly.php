<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminOnly
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Solo permitir al primer usuario (ID = 1) crear nuevos usuarios
        if (auth()->id() !== 1) {
            abort(403, 'Solo el administrador puede crear nuevos usuarios.');
        }

        return $next($request);
    }
}
