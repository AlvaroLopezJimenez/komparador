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

        $bearer = $request->bearerToken();
        $bearer = $bearer !== null ? trim((string) $bearer) : '';
        $xNeo = trim((string) $request->header('X-Neo-Programa-Token', ''));

        // Válido si coincide el Bearer o la cabecera X (Cloudflare/proxies a veces alteran Authorization).
        $okBearer = $bearer !== '' && hash_equals($expected, $bearer);
        $okX = $xNeo !== '' && hash_equals($expected, $xNeo);
        if (!$okBearer && !$okX) {
            abort(401, 'No autorizado.');
        }

        return $next($request);
    }
}
