<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

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
        // ✅ Verificar si es usuario autenticado (bypass completo)
        if (Auth::guard('web')->check()) {
            // Usuario autenticado, generar token sin restricciones
            $ip = $request->ip();
            $fingerprint = $request->header('X-Fingerprint') ?? 'auth_' . Auth::id();
            
            $token = $this->generarToken($ip, $fingerprint);
            
            return response()->json([
                'token' => $token,
                'expires_in' => config('anti-scraping.token.expiration'),
                'max_uses' => config('anti-scraping.token.max_uses')
            ]);
        }
        
        // ✅ Verificar si es bot legítimo (bypass completo)
        if ($this->esBotLegitimo($request)) {
            $ip = $request->ip();
            $fingerprint = $request->header('X-Fingerprint') ?? 'bot_' . md5($request->userAgent());
            
            $token = $this->generarToken($ip, $fingerprint);
            
            return response()->json([
                'token' => $token,
                'expires_in' => config('anti-scraping.token.expiration'),
                'max_uses' => config('anti-scraping.token.max_uses')
            ]);
        }
        
        // Usuario no autenticado y no bot, aplicar rate limiting
        $fingerprint = $request->header('X-Fingerprint');
        $ip = $request->ip();

        // Validar fingerprint
        if (!$fingerprint || strlen($fingerprint) < 10) {
            return response()->json([
                'error' => 'Fingerprint inválido',
                'code' => 'INVALID_FINGERPRINT'
            ], 400);
        }

        // Rate limiting para generación de tokens (solo para no autenticados)
        $limits = config('anti-scraping.limits.token');
        
        // Verificar si ya está bloqueado
        $captchaResuelto = false;
        if (\App\Http\Middleware\AntiScrapingMiddleware::estaIPBloqueada($ip)) {
            // Verificar si viene con CAPTCHA resuelto
            $captchaToken = $request->header('X-Captcha-Token');
            if ($captchaToken && $this->verificarCaptcha($captchaToken)) {
                // CAPTCHA válido, desbloquear
                $this->desbloquearIP($ip);
                $captchaResuelto = true;
                // Continuar con la generación del token (permitir esta petición)
            } else {
                // Verificar si permite CAPTCHA
                $cacheKeyCaptcha = "ip_bloqueada_rate_limit_captcha_{$ip}";
                $permiteCaptcha = Cache::get($cacheKeyCaptcha, true);
                
                if ($permiteCaptcha) {
                    // Permite CAPTCHA (límite minuto/hora)
                    return response()->json([
                        'error' => 'Has realizado demasiadas peticiones',
                        'code' => 'RATE_LIMIT_EXCEEDED',
                        'captcha_required' => true
                    ], 429);
                } else {
                    // NO permite CAPTCHA (límite diario)
                    return response()->json([
                        'error' => 'Demasiadas peticiones realizadas. Si es un error por favor ponte en contacto con info@komparador.com',
                        'code' => 'RATE_LIMIT_EXCEEDED_DAY',
                        'captcha_required' => false,
                        'contact_email' => 'info@komparador.com'
                    ], 429);
                }
            }
        }
        
        // Si el CAPTCHA fue resuelto, permitir esta petición sin verificar rate limits
        // (ya se desbloqueó, pero el bloqueo se mantiene para futuras peticiones)
        if ($captchaResuelto) {
            // Permitir esta petición específica, generar token directamente
            $token = $this->generarToken($ip, $fingerprint);
            
            return response()->json([
                'token' => $token,
                'expires_in' => config('anti-scraping.token.expiration'),
                'max_uses' => config('anti-scraping.token.max_uses')
            ]);
        }
        
        // Verificar límites y bloquear si es necesario
        $limiteSuperado = null;
        $permiteCaptcha = true;
        
        // Por IP (minuto)
        $keyIP = "anti_scraping:token:ip:minute:{$ip}";
        if (RateLimiter::tooManyAttempts($keyIP, $limits['per_minute_ip'])) {
            $limiteSuperado = 'minute';
            $permiteCaptcha = true;
        } else {
            RateLimiter::hit($keyIP, 60);
        }

        // Por fingerprint (minuto)
        if (!$limiteSuperado) {
            $keyFP = "anti_scraping:token:fingerprint:minute:{$fingerprint}";
            if (RateLimiter::tooManyAttempts($keyFP, $limits['per_minute_fingerprint'])) {
                $limiteSuperado = 'minute';
                $permiteCaptcha = true;
            } else {
                RateLimiter::hit($keyFP, 60);
            }
        }
        
        // Por IP (hora)
        if (!$limiteSuperado) {
            $keyHour = "anti_scraping:token:ip:hour:{$ip}";
            if (isset($limits['per_hour_ip']) && RateLimiter::tooManyAttempts($keyHour, $limits['per_hour_ip'])) {
                $limiteSuperado = 'hour';
                $permiteCaptcha = true;
            } else {
                if (isset($limits['per_hour_ip'])) {
                    RateLimiter::hit($keyHour, 3600);
                }
            }
        }
        
        // Por IP (día) - SIN CAPTCHA
        if (!$limiteSuperado) {
            $keyDay = "anti_scraping:token:ip:day:{$ip}";
            if (isset($limits['per_day_ip']) && RateLimiter::tooManyAttempts($keyDay, $limits['per_day_ip'])) {
                $limiteSuperado = 'day';
                $permiteCaptcha = false;
            } else {
                if (isset($limits['per_day_ip'])) {
                    RateLimiter::hit($keyDay, 86400);
                }
            }
        }
        
        // Si se superó algún límite, bloquear
        if ($limiteSuperado) {
            $this->bloquearPorRateLimit($ip, $fingerprint, $permiteCaptcha);
            
            if ($limiteSuperado === 'day') {
                return response()->json([
                    'error' => 'Demasiadas peticiones realizadas. Si es un error por favor ponte en contacto con info@komparador.com',
                    'code' => 'RATE_LIMIT_EXCEEDED_DAY',
                    'captcha_required' => false,
                    'contact_email' => 'info@komparador.com'
                ], 429);
            } else {
                return response()->json([
                    'error' => 'Has realizado demasiadas peticiones',
                    'code' => 'RATE_LIMIT_EXCEEDED',
                    'captcha_required' => true
                ], 429);
            }
        }

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

    /**
     * Verifica si es un bot legítimo (Google, Bing, etc.)
     */
    private function esBotLegitimo(Request $request): bool
    {
        $ua = Str::lower($request->userAgent() ?? '');
        $ip = $request->ip();

        $botsLegitimos = [
            'googlebot',
            'bingbot',
            'slurp', // Yahoo
            'duckduckbot',
            'baiduspider',
            'yandexbot',
            'facebookexternalhit',
            'twitterbot',
            'linkedinbot',
            'applebot',
            'ia_archiver', // Archive.org
        ];

        foreach ($botsLegitimos as $bot) {
            if (Str::contains($ua, $bot)) {
                // Verificación adicional para Googlebot (verificar IP)
                if ($bot === 'googlebot') {
                    return $this->verificarGooglebotIP($ip);
                }
                return true;
            }
        }

        return false;
    }

    /**
     * Verifica que la IP pertenece a rangos conocidos de Googlebot
     */
    private function verificarGooglebotIP(string $ip): bool
    {
        $rangosGooglebot = [
            '66.249.',      // Principal
            '64.233.',      // Google
            '72.14.',       // Google
            '74.125.',      // Google
            '108.177.',     // Google
            '172.217.',     // Google
            '209.85.',      // Google
        ];

        foreach ($rangosGooglebot as $rango) {
            if (str_starts_with($ip, $rango)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Bloquea una IP/fingerprint por 7 días por superar rate limit
     */
    private function bloquearPorRateLimit(string $ip, ?string $fingerprint, bool $permiteCaptcha = true): void
    {
        $cacheKey = "ip_bloqueada_rate_limit_{$ip}";
        $duration = 604800; // 7 días en segundos
        
        Cache::put($cacheKey, true, $duration);
        
        // Guardar si permite CAPTCHA o no
        $cacheKeyCaptcha = "ip_bloqueada_rate_limit_captcha_{$ip}";
        Cache::put($cacheKeyCaptcha, $permiteCaptcha, $duration);
        
        if ($fingerprint) {
            $cacheKeyFP = "fp_bloqueado_rate_limit_{$fingerprint}";
            Cache::put($cacheKeyFP, true, $duration);
            
            $cacheKeyFPCaptcha = "fp_bloqueado_rate_limit_captcha_{$fingerprint}";
            Cache::put($cacheKeyFPCaptcha, $permiteCaptcha, $duration);
        }
        
        Log::warning('IP bloqueada por rate limit en TokenController (7 días)', [
            'ip' => $ip,
            'fingerprint' => $fingerprint,
            'permite_captcha' => $permiteCaptcha
        ]);
    }

    /**
     * Desbloquea una IP después de resolver CAPTCHA
     */
    private function desbloquearIP(string $ip): void
    {
        $cacheKey = "ip_bloqueada_rate_limit_{$ip}";
        Cache::forget($cacheKey);
        
        $cacheKeyCaptcha = "ip_bloqueada_rate_limit_captcha_{$ip}";
        Cache::forget($cacheKeyCaptcha);
        
        Log::info('IP desbloqueada después de resolver CAPTCHA en TokenController', ['ip' => $ip]);
    }

    /**
     * Verifica CAPTCHA de Google reCAPTCHA
     */
    private function verificarCaptcha(string $token): bool
    {
        $secretKey = env('RECAPTCHA_SECRET_KEY');
        
        if (!$secretKey) {
            Log::error('RECAPTCHA_SECRET_KEY no configurada');
            return false;
        }
        
        try {
            $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
                'secret' => $secretKey,
                'response' => $token,
                'remoteip' => request()->ip()
            ]);
            
            $result = $response->json();
            return $result['success'] ?? false;
        } catch (\Exception $e) {
            Log::error('Error al verificar CAPTCHA en TokenController', ['error' => $e->getMessage()]);
            return false;
        }
    }
}








