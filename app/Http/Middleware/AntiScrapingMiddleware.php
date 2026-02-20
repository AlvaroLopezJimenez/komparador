<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use App\Services\AntiScrapingScoringService;
use App\Services\AntiScrapingAvisoService;
use App\Models\ActividadSospechosa;

class AntiScrapingMiddleware
{
    protected $scoringService;
    protected $avisoService;

    public function __construct(AntiScrapingScoringService $scoringService, AntiScrapingAvisoService $avisoService)
    {
        $this->scoringService = $scoringService;
        $this->avisoService = $avisoService;
    }

    /**
     * Handle an incoming request.
     *
     * ORDEN OPTIMIZADO:
     * 0. Usuarios autenticados (bypass completo, más rápido)
     * 1. Bots legítimos (bypass antes de validar token - importante para SEO)
     * 2. Token válido (rápido, rechazo inmediato)
     * 3. TTL correcto (rápido)
     * 4. Rate limit (rápido, cache)
     * 5. Heurísticas (solo si pasa todo lo anterior)
     */
    public function handle(Request $request, Closure $next, string $type = 'ofertas')
    {
        // ✅ ORDEN 0: Usuarios autenticados (bypass con monitoreo)
        // Usar guard 'web' explícitamente para asegurar que detecta la sesión web
        $isAuthenticated = Auth::guard('web')->check();
        
        if ($isAuthenticated) {
            $user = Auth::guard('web')->user();
            
            // Rate limits altos pero existentes para detectar abuso
            $authLimits = config('anti-scraping.authenticated.rate_limits');
            if (!$this->pasaRateLimitAutenticado($request, $authLimits, $type)) {
                // Log de actividad sospechosa para usuarios autenticados
                $this->logActividad($request, $type, 0, 'rate_limit_auth', [
                    'user_id' => $user->id,
                    'user_email' => $user->email ?? 'N/A',
                ]);
                
                $retryAfter = $this->getRetryAfter($request, $type);
                return response()->json([
                    'error' => 'Rate limit excedido',
                    'code' => 'RATE_LIMIT_EXCEEDED',
                    'retry_after' => $retryAfter
                ], 429)->header('Retry-After', $retryAfter);
            }
            
            // Logging pasivo si está habilitado
            if (config('anti-scraping.authenticated.enable_logging')) {
                $score = config('anti-scraping.authenticated.enable_heuristics') 
                    ? $this->scoringService->calcularScore($request, $type) 
                    : 0;
                
                // Solo log si hay actividad sospechosa (score > 0)
                if ($score > 0) {
                    $this->logActividad($request, $type, $score, 'monitoring_auth', [
                        'user_id' => $user->id,
                        'user_email' => $user->email ?? 'N/A',
                    ]);
                }
            }
            
            // Permitir acceso (sin bloqueos ni CAPTCHA)
            return $next($request);
        }

        // ✅ ORDEN 1: Bypass para bots legítimos (ANTES de validar token)
        // Los bots legítimos (Googlebot, Bingbot, etc.) no pueden obtener tokens dinámicos
        // como los usuarios reales, así que los permitimos antes de validar el token
        // Esto es importante para SEO ya que Googlebot ejecuta JavaScript y necesita acceder a la API
        if ($this->esBotLegitimo($request)) {
            return $next($request);
        }

        // ✅ ORDEN 2: Validación rápida de token (rechazo inmediato)
        // Solo validar token si NO hay usuario autenticado (doble verificación)
        $token = $request->header('X-Auth-Token');
        if (!$token || !$this->validarToken($token, $request)) {
            // Si no hay token y el usuario tampoco está autenticado, rechazar
            if (!$isAuthenticated) {
                $this->avisoService->crearAvisoBloqueo(
                    'rate_limit',
                    $request->ip(),
                    $request->header('X-Fingerprint'),
                    0,
                    ['endpoint' => $request->path(), 'tipo' => 'INVALID_TOKEN']
                );
                return response()->json([
                    'error' => 'Token inválido o expirado',
                    'code' => 'INVALID_TOKEN'
                ], 401);
            }
            // Si el usuario está autenticado pero no hay token, continuar (bypass)
        }

        // ✅ ORDEN 3: Validar TTL del token (rápido)
        // Solo validar expiración si hay token y el usuario NO está autenticado
        if ($token && !$isAuthenticated && $this->tokenExpirado($token)) {
            $this->avisoService->crearAvisoBloqueo(
                'rate_limit',
                $request->ip(),
                $request->header('X-Fingerprint'),
                0,
                ['endpoint' => $request->path(), 'tipo' => 'TOKEN_EXPIRED']
            );
            return response()->json([
                'error' => 'Token expirado',
                'code' => 'TOKEN_EXPIRED'
            ], 401);
        }

        // ✅ ORDEN 4: Rate limiting (cache, rápido)
        // Solo se aplica si no es usuario autenticado ni bot legítimo
        $limits = config("anti-scraping.limits.{$type}");
        $rateLimitResult = $this->pasaRateLimit($request, $limits, $type);
        
        if (!$rateLimitResult['pasa']) {
            // IMPORTANTE: Verificar de nuevo que no sea usuario autenticado ni bot
            // (por si acaso, aunque ya debería estar filtrado)
            if ($isAuthenticated || $this->esBotLegitimo($request)) {
                // No debería llegar aquí, pero por seguridad permitir acceso
                return $next($request);
            }
            
            $ip = $request->ip();
            $fingerprint = $request->header('X-Fingerprint');
            $limiteSuperado = $rateLimitResult['limite_superado'];
            $permiteCaptcha = $rateLimitResult['permite_captcha'];
            
            // Si es límite diario, bloquear sin CAPTCHA
            if ($limiteSuperado === 'day') {
                // Verificar si ya está bloqueado
                if (self::estaIPBloqueada($ip)) {
                    // Ya está bloqueado por límite diario, mostrar mensaje sin CAPTCHA
                    return response()->json([
                        'error' => 'Demasiadas peticiones realizadas. Si es un error por favor ponte en contacto con info@komparador.com',
                        'code' => 'RATE_LIMIT_EXCEEDED_DAY',
                        'captcha_required' => false,
                        'contact_email' => 'info@komparador.com'
                    ], 429);
                }
                
                // Primera vez que supera límite diario, bloquear por 7 días sin CAPTCHA
                $this->bloquearPorRateLimit($ip, $fingerprint, false); // false = sin CAPTCHA
                $this->avisoService->crearAvisoBloqueo(
                    'rate_limit',
                    $ip,
                    $fingerprint,
                    0,
                    ['endpoint' => $request->path(), 'tipo' => $type, 'limite' => 'day']
                );
                
                return response()->json([
                    'error' => 'Demasiadas peticiones realizadas. Si es un error por favor ponte en contacto con info@komparador.com',
                    'code' => 'RATE_LIMIT_EXCEEDED_DAY',
                    'captcha_required' => false,
                    'contact_email' => 'info@komparador.com'
                ], 429);
            }
            
            // Si es límite de minuto u hora, permitir CAPTCHA
            // Verificar si ya está bloqueado por 7 días
            if (self::estaIPBloqueada($ip)) {
                // Verificar si permite CAPTCHA
                $cacheKeyCaptcha = "ip_bloqueada_rate_limit_captcha_{$ip}";
                $permiteCaptchaCache = Cache::get($cacheKeyCaptcha, true); // Por defecto true para compatibilidad
                
                if ($permiteCaptchaCache) {
                    // Permitir CAPTCHA (límite minuto/hora)
                    $captchaToken = $request->header('X-Captcha-Token');
                    if ($captchaToken && $this->verificarCaptcha($captchaToken)) {
                        // CAPTCHA válido, desbloquear
                        $this->desbloquearIP($ip);
                        // Permitir esta petición (pasar rate limit esta vez)
                        return $next($request);
                    }
                    
                    // Está bloqueado y no tiene CAPTCHA válido
                    return response()->json([
                        'error' => 'Has realizado demasiadas peticiones',
                        'code' => 'RATE_LIMIT_EXCEEDED',
                        'captcha_required' => true,
                        'retry_after' => $this->getRetryAfter($request, $type)
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
            
            // Primera vez que supera el límite (minuto/hora), bloquear por 7 días con CAPTCHA
            $this->bloquearPorRateLimit($ip, $fingerprint, true); // true = con CAPTCHA
            $this->avisoService->crearAvisoBloqueo(
                'rate_limit',
                $ip,
                $fingerprint,
                0,
                ['endpoint' => $request->path(), 'tipo' => $type, 'limite' => $limiteSuperado]
            );
            
            return response()->json([
                'error' => 'Has realizado demasiadas peticiones',
                'code' => 'RATE_LIMIT_EXCEEDED',
                'captcha_required' => true,
                'retry_after' => $this->getRetryAfter($request, $type)
            ], 429);
        }

        // ✅ ORDEN 5: Heurísticas (solo si pasa todo lo anterior)
        $score = $this->scoringService->calcularScore($request, $type);
        $accion = $this->determinarAccion($score);

        // Aplicar acciones según score
        if ($score >= 100) {
            $this->bloquearProlongado($request);
            $this->logActividad($request, $type, $score, 'prolonged_ban');
            // Crear aviso para bloqueo prolongado
            $this->avisoService->crearAvisoBloqueo(
                'prolonged_ban',
                $request->ip(),
                $request->header('X-Fingerprint'),
                $score,
                ['endpoint' => $request->path(), 'tipo' => $type]
            );
            return response()->json([
                'error' => 'Acceso bloqueado',
                'code' => 'BLOCKED',
                'duration' => '7 días'
            ], 403);
        }

        if ($score >= 81) {
            $this->bloquearTemporal($request);
            $this->logActividad($request, $type, $score, 'temp_ban');
            // Crear aviso para bloqueo temporal
            $this->avisoService->crearAvisoBloqueo(
                'temp_ban',
                $request->ip(),
                $request->header('X-Fingerprint'),
                $score,
                ['endpoint' => $request->path(), 'tipo' => $type]
            );
            return response()->json([
                'error' => 'Acceso bloqueado temporalmente',
                'code' => 'TEMP_BLOCKED',
                'duration' => '1 hora'
            ], 403);
        }

        if ($score >= 41) {
            $this->logActividad($request, $type, $score, 'captcha');
            // Crear aviso solo si el score es muy alto (>= 60) para evitar spam
            if ($score >= 60) {
                $this->avisoService->crearAvisoBloqueo(
                    'captcha',
                    $request->ip(),
                    $request->header('X-Fingerprint'),
                    $score,
                    ['endpoint' => $request->path(), 'tipo' => $type]
                );
            }
            return response()->json([
                'error' => 'CAPTCHA requerido',
                'code' => 'CAPTCHA_REQUIRED',
                'captcha_required' => true
            ], 429);
        }

        if ($score >= 21) {
            // Ralentizar respuesta
            $delay = rand(
                config('anti-scraping.actions.slowdown.min_delay'),
                config('anti-scraping.actions.slowdown.max_delay')
            );
            usleep($delay * 1000); // Convertir ms a microsegundos
            $this->logActividad($request, $type, $score, 'slowdown');
        } else {
            $this->logActividad($request, $type, $score, 'normal');
        }

        return $next($request);
    }

    /**
     * Decodifica Base64 URL-safe
     */
    private function base64UrlDecode(string $data): string
    {
        $base64 = strtr($data, '-_', '+/');
        $base64 = str_pad($base64, strlen($base64) % 4, '=', STR_PAD_RIGHT);
        return base64_decode($base64);
    }

    /**
     * Valida el formato, firma, IP y fingerprint del token
     */
    private function validarToken(string $token, Request $request): bool
    {
        try {
            $parts = explode('.', $token);
            if (count($parts) !== 3) {
                Log::warning('Token con formato inválido', ['parts' => count($parts)]);
                return false;
            }

            $payload = json_decode($this->base64UrlDecode($parts[1]), true);
            if (!$payload) {
                Log::warning('Token payload inválido');
                return false;
            }

            // Verificar firma
            $secret = config('anti-scraping.token.secret');
            $signature = hash_hmac('sha256', $parts[0] . '.' . $parts[1], $secret, true);
            $expectedSignature = $this->base64UrlEncode($signature);
            
            if (!hash_equals($expectedSignature, $parts[2])) {
                return false;
            }

            // Validar que la IP del token coincida con la IP de la solicitud
            $tokenIP = $payload['ip'] ?? null;
            if ($tokenIP) {
                $requestIP = $this->obtenerIPReal($request);
                
                // Validar que la IP del token coincida con la IP real
                if ($tokenIP !== $requestIP) {
                    Log::warning('Token usado desde IP diferente', [
                        'token_ip' => $tokenIP,
                        'request_ip' => $requestIP,
                        'endpoint' => $request->path()
                    ]);
                    return false;
                }
            }

            // Validar que el fingerprint del token coincida con el fingerprint de la solicitud
            $tokenFingerprint = $payload['fingerprint'] ?? null;
            $requestFingerprint = $request->header('X-Fingerprint');
            
            if ($tokenFingerprint && $requestFingerprint) {
                if ($tokenFingerprint !== $requestFingerprint) {
                    Log::warning('Token usado con fingerprint diferente', [
                        'token_fp' => $tokenFingerprint,
                        'request_fp' => $requestFingerprint,
                        'endpoint' => $request->path()
                    ]);
                    return false;
                }
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Error validando token', ['error' => $e->getMessage(), 'token_preview' => substr($token, 0, 20) . '...']);
            return false;
        }
    }

    /**
     * Codifica a Base64 URL-safe
     */
    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Verifica si el token ha expirado
     */
    private function tokenExpirado(string $token): bool
    {
        try {
            $parts = explode('.', $token);
            $payload = json_decode($this->base64UrlDecode($parts[1]), true);
            
            if (!isset($payload['exp'])) {
                return true;
            }

            return time() > $payload['exp'];
        } catch (\Exception $e) {
            return true;
        }
    }

    /**
     * Verifica si es un bot legítimo (Google, Bing, etc.)
     * PRIMERO verifica la IP (más confiable), luego el User-Agent
     */
    private function esBotLegitimo(Request $request): bool
    {
        $ip = $this->obtenerIPReal($request);
        $ua = Str::lower($request->userAgent() ?? '');

        // PRIMERO: Verificar si la IP pertenece a rangos conocidos de bots legítimos
        // Esto es importante porque algunos bots pueden no tener User-Agent correcto
        // o pueden estar detrás de proxies que modifican el User-Agent
        if ($this->esIPDeBotLegitimo($ip)) {
            Log::info('Bot legítimo detectado por IP', [
                'ip' => $ip,
                'user_agent' => $request->userAgent(),
                'endpoint' => $request->path()
            ]);
            return true;
        }

        // SEGUNDO: Verificar User-Agent
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
                    $esGooglebot = $this->verificarGooglebotIP($ip);
                    if ($esGooglebot) {
                        Log::info('Googlebot detectado por User-Agent e IP', [
                            'ip' => $ip,
                            'user_agent' => $request->userAgent()
                        ]);
                    }
                    return $esGooglebot;
                }
                Log::info('Bot legítimo detectado por User-Agent', [
                    'bot' => $bot,
                    'ip' => $ip,
                    'user_agent' => $request->userAgent()
                ]);
                return true;
            }
        }

        return false;
    }

    /**
     * Verifica que la IP pertenece a rangos conocidos de Googlebot
     * Actualizado con más rangos de Google
     */
    private function verificarGooglebotIP(string $ip): bool
    {
        $rangosGooglebot = [
            '66.249.',      // Principal (incluye 66.249.92.32)
            '64.233.',      // Google
            '72.14.',       // Google
            '74.125.',      // Google
            '108.177.',     // Google
            '172.217.',     // Google
            '209.85.',      // Google
            '216.239.',     // Google (adicional)
            '142.250.',     // Google (adicional)
        ];

        foreach ($rangosGooglebot as $rango) {
            if (str_starts_with($ip, $rango)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Obtiene la IP real del cliente, considerando Cloudflare y otros proxies
     * Prioriza CF-Connecting-IP (Cloudflare) sobre X-Forwarded-For
     */
    private function obtenerIPReal(Request $request): string
    {
        // 1. Cloudflare (prioridad - más confiable)
        $cfIP = $request->header('CF-Connecting-IP');
        if ($cfIP) {
            return trim(explode(',', $cfIP)[0]);
        }

        // 2. X-Forwarded-For (otros proxies/load balancers)
        $xff = $request->header('X-Forwarded-For');
        if ($xff) {
            // X-Forwarded-For puede contener múltiples IPs separadas por comas
            // La primera es la IP real del cliente
            return trim(explode(',', $xff)[0]);
        }

        // 3. IP directa del request (fallback)
        return $request->ip();
    }

    /**
     * Verifica si la IP pertenece a rangos conocidos de bots legítimos
     * Esto permite detectar bots incluso si el User-Agent no es reconocido
     * o está siendo modificado por proxies
     */
    private function esIPDeBotLegitimo(string $ip): bool
    {
        // Rangos de Googlebot (más importante)
        if ($this->verificarGooglebotIP($ip)) {
            return true;
        }

        // Rangos de otros bots legítimos
        $rangosBots = [
            // Bingbot
            '40.77.167.',
            '207.46.13.',
            // Facebook
            '31.13.24.',
            '31.13.25.',
            '31.13.26.',
            '31.13.27.',
            // Twitter
            '199.16.156.',
            '199.59.148.',
            // LinkedIn
            '108.174.10.',
        ];

        foreach ($rangosBots as $rango) {
            if (str_starts_with($ip, $rango)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Verifica si pasa los rate limits para usuarios autenticados
     */
    private function pasaRateLimitAutenticado(Request $request, array $limits, string $type): bool
    {
        $ip = $request->ip();
        $user = Auth::user();
        $userId = $user->id;

        // Rate limit por IP (minuto)
        if (isset($limits['per_minute_ip'])) {
            $key = "anti_scraping:auth:ip:minute:{$ip}:{$type}";
            if (RateLimiter::tooManyAttempts($key, $limits['per_minute_ip'])) {
                return false;
            }
            RateLimiter::hit($key, 60);
        }

        // Rate limit por hora (IP)
        if (isset($limits['per_hour_ip'])) {
            $key = "anti_scraping:auth:ip:hour:{$ip}:{$type}";
            if (RateLimiter::tooManyAttempts($key, $limits['per_hour_ip'])) {
                return false;
            }
            RateLimiter::hit($key, 3600);
        }

        // Rate limit por día (IP)
        if (isset($limits['per_day_ip'])) {
            $key = "anti_scraping:auth:ip:day:{$ip}:{$type}";
            if (RateLimiter::tooManyAttempts($key, $limits['per_day_ip'])) {
                return false;
            }
            RateLimiter::hit($key, 86400);
        }

        return true;
    }

    /**
     * Verifica si pasa los rate limits
     * Devuelve array con información sobre qué límite se superó
     */
    private function pasaRateLimit(Request $request, array $limits, string $type): array
    {
        $ip = $request->ip();
        $fingerprint = $request->header('X-Fingerprint');
        $token = $request->header('X-Auth-Token');
        
        $resultado = [
            'pasa' => true,
            'limite_superado' => null, // 'minute', 'hour', 'day'
            'permite_captcha' => true
        ];

        // Rate limit por IP (minuto)
        if (isset($limits['per_minute_ip'])) {
            $key = "anti_scraping:ip:minute:{$ip}:{$type}";
            if (RateLimiter::tooManyAttempts($key, $limits['per_minute_ip'])) {
                $resultado['pasa'] = false;
                $resultado['limite_superado'] = 'minute';
                $resultado['permite_captcha'] = true;
                return $resultado;
            }
            RateLimiter::hit($key, 60);
        }

        // Rate limit por fingerprint (minuto)
        if ($fingerprint && isset($limits['per_minute_fingerprint'])) {
            $key = "anti_scraping:fingerprint:minute:{$fingerprint}:{$type}";
            if (RateLimiter::tooManyAttempts($key, $limits['per_minute_fingerprint'])) {
                $resultado['pasa'] = false;
                $resultado['limite_superado'] = 'minute';
                $resultado['permite_captcha'] = true;
                return $resultado;
            }
            RateLimiter::hit($key, 60);
        }

        // Rate limit por token (minuto)
        if ($token && isset($limits['per_minute_token'])) {
            $key = "anti_scraping:token:minute:{$token}:{$type}";
            if (RateLimiter::tooManyAttempts($key, $limits['per_minute_token'])) {
                $resultado['pasa'] = false;
                $resultado['limite_superado'] = 'minute';
                $resultado['permite_captcha'] = true;
                return $resultado;
            }
            RateLimiter::hit($key, 60);
        }

        // Rate limit por hora (IP)
        if (isset($limits['per_hour_ip'])) {
            $key = "anti_scraping:ip:hour:{$ip}:{$type}";
            if (RateLimiter::tooManyAttempts($key, $limits['per_hour_ip'])) {
                $resultado['pasa'] = false;
                $resultado['limite_superado'] = 'hour';
                $resultado['permite_captcha'] = true;
                return $resultado;
            }
            RateLimiter::hit($key, 3600);
        }

        // Rate limit por día (IP) - SIN CAPTCHA
        if (isset($limits['per_day_ip'])) {
            $key = "anti_scraping:ip:day:{$ip}:{$type}";
            if (RateLimiter::tooManyAttempts($key, $limits['per_day_ip'])) {
                $resultado['pasa'] = false;
                $resultado['limite_superado'] = 'day';
                $resultado['permite_captcha'] = false; // NO permite CAPTCHA
                return $resultado;
            }
            RateLimiter::hit($key, 86400);
        }

        return $resultado;
    }

    /**
     * Obtiene el tiempo de espera antes de poder reintentar
     */
    private function getRetryAfter(Request $request, string $type): int
    {
        $ip = $request->ip();
        $key = "anti_scraping:ip:minute:{$ip}:{$type}";
        
        try {
            return RateLimiter::availableIn($key);
        } catch (\Exception $e) {
            return 60; // Por defecto 60 segundos
        }
    }

    /**
     * Determina la acción según el score
     */
    private function determinarAccion(int $score): string
    {
        $thresholds = config('anti-scraping.scoring.thresholds');

        if ($score >= 100) {
            return 'prolonged_ban';
        } elseif ($score >= 81) {
            return 'temp_ban';
        } elseif ($score >= 41) {
            return 'captcha';
        } elseif ($score >= 21) {
            return 'slowdown';
        }

        return 'normal';
    }

    /**
     * Bloquea una IP temporalmente (1 hora)
     */
    private function bloquearTemporal(Request $request): void
    {
        $ip = $request->ip();
        $cacheKey = "ip_bloqueada_temp_{$ip}";
        $duration = config('anti-scraping.actions.temp_ban.duration');
        
        Cache::put($cacheKey, true, $duration);
        
        Log::warning('IP bloqueada temporalmente', [
            'ip' => $ip,
            'duration' => $duration,
            'endpoint' => $request->path()
        ]);
    }

    /**
     * Bloquea una IP prolongadamente (7 días)
     */
    private function bloquearProlongado(Request $request): void
    {
        $ip = $request->ip();
        $cacheKey = "ip_bloqueada_prolongado_{$ip}";
        $duration = config('anti-scraping.actions.prolonged_ban.duration');
        
        Cache::put($cacheKey, true, $duration);
        
        Log::error('IP bloqueada prolongadamente', [
            'ip' => $ip,
            'duration' => $duration,
            'endpoint' => $request->path()
        ]);
    }

    /**
     * Verifica si una IP está bloqueada por rate limit
     * IMPORTANTE: Solo verifica bloqueos, no valida si es usuario autenticado o bot
     * (esa validación se hace antes de llamar a esta función)
     */
    public static function estaIPBloqueada(string $ip): bool
    {
        $cacheKey = "ip_bloqueada_rate_limit_{$ip}";
        return Cache::has($cacheKey) || 
               Cache::has("ip_bloqueada_temp_{$ip}") || 
               Cache::has("ip_bloqueada_prolongado_{$ip}");
    }

    /**
     * Bloquea una IP/fingerprint por 7 días por superar rate limit
     * IMPORTANTE: Solo se debe llamar si NO es usuario autenticado y NO es bot
     * 
     * @param string $ip IP a bloquear
     * @param string|null $fingerprint Fingerprint a bloquear
     * @param bool $permiteCaptcha Si true, permite desbloquear con CAPTCHA. Si false, bloqueo permanente hasta que expire.
     */
    private function bloquearPorRateLimit(string $ip, ?string $fingerprint, bool $permiteCaptcha = true): void
    {
        // Verificación de seguridad adicional (no debería llegar aquí si es autenticado o bot)
        $request = request();
        if (Auth::guard('web')->check() || $this->esBotLegitimo($request)) {
            Log::warning('Intento de bloquear IP de usuario autenticado o bot', [
                'ip' => $ip,
                'fingerprint' => $fingerprint,
                'authenticated' => Auth::guard('web')->check(),
                'user_agent' => $request->userAgent()
            ]);
            return; // No bloquear
        }
        
        $cacheKey = "ip_bloqueada_rate_limit_{$ip}";
        $duration = 604800; // 7 días en segundos
        
        Cache::put($cacheKey, true, $duration);
        
        // Guardar si permite CAPTCHA o no
        $cacheKeyCaptcha = "ip_bloqueada_rate_limit_captcha_{$ip}";
        Cache::put($cacheKeyCaptcha, $permiteCaptcha, $duration);
        
        // También bloquear por fingerprint si existe
        if ($fingerprint) {
            $cacheKeyFP = "fp_bloqueado_rate_limit_{$fingerprint}";
            Cache::put($cacheKeyFP, true, $duration);
            
            $cacheKeyFPCaptcha = "fp_bloqueado_rate_limit_captcha_{$fingerprint}";
            Cache::put($cacheKeyFPCaptcha, $permiteCaptcha, $duration);
        }
        
        Log::warning('IP bloqueada por rate limit (7 días)', [
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
        
        // También desbloquear fingerprint si existe
        $fingerprint = request()->header('X-Fingerprint');
        if ($fingerprint) {
            $cacheKeyFP = "fp_bloqueado_rate_limit_{$fingerprint}";
            Cache::forget($cacheKeyFP);
        }
        
        Log::info('IP desbloqueada después de resolver CAPTCHA', ['ip' => $ip]);
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
            Log::error('Error al verificar CAPTCHA', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Registra actividad sospechosa en la base de datos
     */
    private function logActividad(Request $request, string $endpoint, int $score, string $accion, array $detallesAdicionales = []): void
    {
        try {
            $detalles = array_merge([
                'score' => $score,
                'accion' => $accion,
                'timestamp' => now()->toIso8601String(),
            ], $detallesAdicionales);

            ActividadSospechosa::create([
                'ip' => $request->ip(),
                'fingerprint' => $request->header('X-Fingerprint'),
                'user_agent' => $request->userAgent(),
                'endpoint' => $request->path(),
                'method' => $request->method(),
                'score' => $score,
                'accion_tomada' => $accion,
                'detalles' => $detalles,
                'created_at' => now(),
            ]);
        } catch (\Exception $e) {
            // No fallar si hay error en el logging
            Log::error('Error registrando actividad sospechosa', [
                'error' => $e->getMessage()
            ]);
        }
    }
}

