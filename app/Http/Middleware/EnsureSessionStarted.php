<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class EnsureSessionStarted
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Asegurar que la sesión esté iniciada
        if (!Session::isStarted()) {
            Session::start();
        }

        // Asegurar que el CSRF token esté disponible
        if (!$request->session()->has('_token')) {
            $request->session()->regenerateToken();
        }

        return $next($request);
    }
}
