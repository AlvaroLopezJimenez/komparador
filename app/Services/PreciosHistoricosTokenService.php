<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class PreciosHistoricosTokenService
{
    /**
     * Genera un token seguro para acceder a precios históricos
     * 
     * @param int $productoId ID del producto
     * @param int $expiration Tiempo de expiración en segundos (por defecto 1 hora)
     * @return string Token HMAC firmado
     */
    public function generarToken(int $productoId, int $expiration = 3600): string
    {
        $secret = config('anti-scraping.signed_urls.secret', config('app.key'));
        
        // Crear payload con expiración
        $payload = [
            'p' => $productoId, // producto
            'exp' => time() + $expiration,
            'iat' => time(),
            'nonce' => bin2hex(random_bytes(16)), // Nonce aleatorio para evitar ataques de repetición
        ];
        
        // Codificar payload
        $payloadEncoded = $this->base64UrlEncode(json_encode($payload));
        
        // Crear firma HMAC-SHA256
        $signature = hash_hmac('sha256', $payloadEncoded, $secret, true);
        $signatureEncoded = $this->base64UrlEncode($signature);
        
        // Construir token completo
        $token = $payloadEncoded . '.' . $signatureEncoded;
        
        // Guardar en cache para validación rápida (opcional, para rate limiting)
        $cacheKey = 'precios_historicos_token:' . hash('sha256', $token);
        Cache::put($cacheKey, $productoId, $expiration);
        
        return $token;
    }
    
    /**
     * Valida un token de precios históricos
     * 
     * @param string $token Token a validar
     * @param int $productoId ID del producto esperado
     * @return bool True si es válido, false si no
     */
    public function validarToken(string $token, int $productoId): bool
    {
        try {
            $secret = config('anti-scraping.signed_urls.secret', config('app.key'));
            
            // Separar payload y firma
            $parts = explode('.', $token);
            if (count($parts) !== 2) {
                return false;
            }
            
            [$payloadEncoded, $signatureEncoded] = $parts;
            
            // Verificar firma
            $expectedSignature = hash_hmac('sha256', $payloadEncoded, $secret, true);
            $expectedSignatureEncoded = $this->base64UrlEncode($expectedSignature);
            
            if (!hash_equals($expectedSignatureEncoded, $signatureEncoded)) {
                Log::warning('Firma inválida en token de precios históricos', [
                    'producto_id' => $productoId,
                    'ip' => request()->ip(),
                ]);
                return false;
            }
            
            // Decodificar payload
            $payload = json_decode($this->base64UrlDecode($payloadEncoded), true);
            
            if (!$payload) {
                return false;
            }
            
            // Verificar que el ID de producto coincida
            if (!isset($payload['p']) || (int)$payload['p'] !== $productoId) {
                Log::warning('ID de producto no coincide en token de precios históricos', [
                    'esperado' => $productoId,
                    'recibido' => $payload['p'] ?? null,
                    'ip' => request()->ip(),
                ]);
                return false;
            }
            
            // Verificar expiración
            if (!isset($payload['exp']) || time() > $payload['exp']) {
                Log::info('Token de precios históricos expirado', [
                    'producto_id' => $productoId,
                    'exp' => $payload['exp'] ?? null,
                    'ahora' => time(),
                    'ip' => request()->ip(),
                ]);
                return false;
            }
            
            return true;
        } catch (\Exception $e) {
            Log::error('Error validando token de precios históricos', [
                'error' => $e->getMessage(),
                'producto_id' => $productoId,
                'ip' => request()->ip(),
            ]);
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
     * Decodifica Base64 URL-safe
     */
    private function base64UrlDecode(string $data): string
    {
        $base64 = strtr($data, '-_', '+/');
        $base64 = str_pad($base64, strlen($base64) % 4, '=', STR_PAD_RIGHT);
        return base64_decode($base64);
    }
}





