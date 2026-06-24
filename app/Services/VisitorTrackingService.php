<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Cookie;

class VisitorTrackingService
{
    public const COOKIE_VISITOR = 'kmp_vid';
    public const COOKIE_SESSION = 'kmp_sid';

    /**
     * Obtiene o genera visitor_id (1 año) y session_id (hasta fin del día).
     *
     * @return array{visitor_id: string, session_id: string, cookies: Cookie[]}
     */
    public function resolver(Request $request): array
    {
        $cookies = [];

        $visitorId = $request->cookie(self::COOKIE_VISITOR);
        if (!$this->esIdValido($visitorId)) {
            $visitorId = $this->generarId();
            $cookies[] = $this->crearCookieVisitor($visitorId);
        }

        $sessionId = $request->cookie(self::COOKIE_SESSION);
        if (!$this->esIdValido($sessionId)) {
            $sessionId = $this->generarId();
            $cookies[] = $this->crearCookieSession($sessionId);
        }

        return [
            'visitor_id' => $visitorId,
            'session_id' => $sessionId,
            'cookies' => $cookies,
        ];
    }

    public function adjuntarCookies($response, array $cookies)
    {
        foreach ($cookies as $cookie) {
            $response->cookie($cookie);
        }

        return $response;
    }

    private function generarId(): string
    {
        return Str::uuid()->toString() . Str::random(24);
    }

    private function esIdValido(?string $id): bool
    {
        return is_string($id)
            && strlen($id) >= 40
            && strlen($id) <= 100
            && preg_match('/^[a-zA-Z0-9\-]+$/', $id) === 1;
    }

    private function crearCookieVisitor(string $value): Cookie
    {
        return cookie(
            self::COOKIE_VISITOR,
            $value,
            60 * 24 * 365,
            '/',
            null,
            (bool) config('session.secure', false),
            true,
            false,
            'Lax'
        );
    }

    private function crearCookieSession(string $value): Cookie
    {
        $minutosHastaMedianoche = (int) now()->diffInMinutes(now()->endOfDay()) + 1;

        return cookie(
            self::COOKIE_SESSION,
            $value,
            max($minutosHastaMedianoche, 1),
            '/',
            null,
            (bool) config('session.secure', false),
            true,
            false,
            'Lax'
        );
    }
}
