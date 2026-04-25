<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class CaptchaController extends Controller
{
    /**
     * Verifica el CAPTCHA y desbloquea la IP si es válido
     * 
     * POST /api/captcha/verificar
     * Body: { "captcha_token": "..." }
     * Headers: X-Fingerprint (opcional)
     */
    public function verificar(Request $request)
    {
        $token = $request->input('captcha_token');
        $ip = $request->ip();
        $fingerprint = $request->header('X-Fingerprint');
        
        if (!$token) {
            return response()->json([
                'success' => false,
                'error' => 'Token de CAPTCHA requerido'
            ], 400);
        }
        
        // Verificar CAPTCHA
        $secretKey = env('RECAPTCHA_SECRET_KEY');
        if (!$secretKey) {
            Log::error('RECAPTCHA_SECRET_KEY no configurada');
            return response()->json([
                'success' => false,
                'error' => 'CAPTCHA no configurado'
            ], 500);
        }
        
        try {
            $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
                'secret' => $secretKey,
                'response' => $token,
                'remoteip' => $ip
            ]);
            
            $result = $response->json();
            
            if ($result['success'] ?? false) {
                // CAPTCHA válido, desbloquear
                $cacheKey = "ip_bloqueada_rate_limit_{$ip}";
                Cache::forget($cacheKey);
                
                if ($fingerprint) {
                    $cacheKeyFP = "fp_bloqueado_rate_limit_{$fingerprint}";
                    Cache::forget($cacheKeyFP);
                }
                
                // Limpiar solo contadores por minuto del token (no hora/día) para que pueda obtener token
                // pero siga acumulando hacia los límites de hora y día
                RateLimiter::clear("anti_scraping:token:ip:minute:{$ip}");
                if ($fingerprint) {
                    RateLimiter::clear("anti_scraping:token:fingerprint:minute:{$fingerprint}");
                }
                
                Log::info('IP desbloqueada después de resolver CAPTCHA', [
                    'ip' => $ip,
                    'fingerprint' => $fingerprint
                ]);
                
                return response()->json([
                    'success' => true,
                    'message' => 'CAPTCHA verificado correctamente. Puedes continuar.'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => 'CAPTCHA inválido'
                ], 400);
            }
        } catch (\Exception $e) {
            Log::error('Error al verificar CAPTCHA', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'error' => 'Error al verificar CAPTCHA'
            ], 500);
        }
    }
}



