<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SignedUrlService
{
    /**
     * Genera una URL firmada para una oferta
     * 
     * @param int $ofertaId ID de la oferta
     * @return string URL firmada con formato /r?o={ofertaId}&t={token}
     */
    public function generarUrlFirmada(int $ofertaId): string
    {
        $expiration = config('anti-scraping.signed_urls.expiration', 300); // 5 minutos por defecto
        $secret = config('anti-scraping.signed_urls.secret', config('app.key'));
        
        // Crear payload con expiración
        $payload = [
            'o' => $ofertaId,
            'exp' => time() + $expiration,
            'iat' => time(),
        ];
        
        // Codificar payload
        $payloadEncoded = $this->base64UrlEncode(json_encode($payload));
        
        // Crear firma
        $signature = hash_hmac('sha256', $payloadEncoded, $secret, true);
        $signatureEncoded = $this->base64UrlEncode($signature);
        
        // Construir token completo
        $token = $payloadEncoded . '.' . $signatureEncoded;
        
        // Construir URL
        return route('click.redirigir', ['ofertaId' => $ofertaId]) . '?o=' . $ofertaId . '&t=' . $token;
    }
    
    /**
     * Valida una URL firmada
     * 
     * @param string $token Token de la URL
     * @param int $ofertaId ID de la oferta esperado
     * @return bool True si es válido, false si no
     */
    public function validarUrlFirmada(string $token, int $ofertaId): bool
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
                Log::warning('Firma inválida en URL firmada', [
                    'oferta_id' => $ofertaId,
                    'ip' => request()->ip(),
                ]);
                return false;
            }
            
            // Decodificar payload
            $payload = json_decode($this->base64UrlDecode($payloadEncoded), true);
            
            if (!$payload) {
                return false;
            }
            
            // Verificar que el ID de oferta coincida
            if (!isset($payload['o']) || (int)$payload['o'] !== $ofertaId) {
                Log::warning('ID de oferta no coincide en URL firmada', [
                    'esperado' => $ofertaId,
                    'recibido' => $payload['o'] ?? null,
                    'ip' => request()->ip(),
                ]);
                return false;
            }
            
            // Verificar expiración
            if (!isset($payload['exp']) || time() > $payload['exp']) {
                Log::info('URL firmada expirada', [
                    'oferta_id' => $ofertaId,
                    'exp' => $payload['exp'] ?? null,
                    'ahora' => time(),
                    'ip' => request()->ip(),
                ]);
                return false;
            }
            
            return true;
        } catch (\Exception $e) {
            Log::error('Error validando URL firmada', [
                'error' => $e->getMessage(),
                'oferta_id' => $ofertaId,
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





