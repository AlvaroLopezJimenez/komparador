<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Log;

class TokenController extends Controller
{
    /**
     * Genera un token efímero firmado
     * 
     * POST /api/token
     * Headers: X-Fingerprint (requerido)
     */
    public function generate(Request $request)
    {
        $fingerprint = $request->header('X-Fingerprint');
        $ip = $request->ip();

        // Validar fingerprint
        if (!$fingerprint || strlen($fingerprint) < 10) {
            return response()->json([
                'error' => 'Fingerprint inválido',
                'code' => 'INVALID_FINGERPRINT'
            ], 400);
        }

        // Rate limiting para generación de tokens
        $limits = config('anti-scraping.limits.token');
        
        // Por IP
        $keyIP = "anti_scraping:token:ip:minute:{$ip}";
        if (RateLimiter::tooManyAttempts($keyIP, $limits['per_minute_ip'])) {
            return response()->json([
                'error' => 'Demasiadas solicitudes de token',
                'code' => 'RATE_LIMIT_EXCEEDED',
                'retry_after' => RateLimiter::availableIn($keyIP)
            ], 429);
        }
        RateLimiter::hit($keyIP, 60);

        // Por fingerprint
        $keyFP = "anti_scraping:token:fingerprint:minute:{$fingerprint}";
        if (RateLimiter::tooManyAttempts($keyFP, $limits['per_minute_fingerprint'])) {
            return response()->json([
                'error' => 'Demasiadas solicitudes de token',
                'code' => 'RATE_LIMIT_EXCEEDED',
                'retry_after' => RateLimiter::availableIn($keyFP)
            ], 429);
        }
        RateLimiter::hit($keyFP, 60);

        // Generar token
        $token = $this->generarToken($ip, $fingerprint);

        return response()->json([
            'token' => $token,
            'expires_in' => config('anti-scraping.token.expiration'),
            'max_uses' => config('anti-scraping.token.max_uses')
        ]);
    }

    /**
     * Genera un token JWT firmado
     */
    private function generarToken(string $ip, string $fingerprint): string
    {
        $secret = config('anti-scraping.token.secret');
        $expiration = config('anti-scraping.token.expiration');
        
        // Header
        $header = [
            'typ' => 'JWT',
            'alg' => 'HS256'
        ];
        
        // Payload
        $payload = [
            'ip' => $ip,
            'fingerprint' => $fingerprint,
            'iat' => time(),
            'exp' => time() + $expiration,
            'uses' => 0, // Contador de usos
            'max_uses' => config('anti-scraping.token.max_uses')
        ];
        
        // Codificar
        $base64UrlHeader = $this->base64UrlEncode(json_encode($header));
        $base64UrlPayload = $this->base64UrlEncode(json_encode($payload));
        
        // Crear signature
        $signature = hash_hmac('sha256', $base64UrlHeader . '.' . $base64UrlPayload, $secret, true);
        $base64UrlSignature = $this->base64UrlEncode($signature);
        
        return $base64UrlHeader . '.' . $base64UrlPayload . '.' . $base64UrlSignature;
    }

    /**
     * Codifica a Base64 URL-safe
     */
    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}








