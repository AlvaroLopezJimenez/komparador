<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyNeoProgramaExternoToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = (string) config('anti-scraping.neo_api_programa_externo', '');
        if ($expected === '') {
            abort(503, 'Token programa externo Neo no configurado.');
        }

        $token = $request->bearerToken();
        if ($token === null || $token === '') {
            $token = (string) $request->header('X-Neo-Programa-Token', '');
        }

        if ($token === '' || !hash_equals($expected, $token)) {
            abort(401, 'No autorizado.');
        }

        return $next($request);
    }
}
