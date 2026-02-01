<?php

namespace App\Services;

use App\Models\Aviso;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AntiScrapingAvisoService
{
    /**
     * Crea un aviso para bloqueos importantes del sistema anti-scraping
     * Solo crea avisos si no existe uno similar reciente (evita spam)
     * 
     * @param string $tipo Tipo de bloqueo: 'prolonged_ban', 'temp_ban', 'rate_limit'
     * @param string $ip IP bloqueada
     * @param string|null $fingerprint Fingerprint del cliente
     * @param int $score Score del sistema anti-scraping
     * @param array $detalles Detalles adicionales
     */
    public function crearAvisoBloqueo(string $tipo, string $ip, ?string $fingerprint = null, int $score = 0, array $detalles = []): void
    {
        try {
            // Verificar si ya existe un aviso similar reciente (últimas 24 horas)
            $avisoReciente = Aviso::where('avisoable_type', 'AntiScraping')
                ->where('avisoable_id', 0)
                ->where('texto_aviso', 'like', "%{$ip}%")
                ->where('created_at', '>=', now()->subHours(24))
                ->first();

            if ($avisoReciente) {
                // Ya existe un aviso reciente para esta IP, no crear otro
                return;
            }

            // Generar texto del aviso según el tipo
            $textoAviso = $this->generarTextoAviso($tipo, $ip, $fingerprint, $score, $detalles);

            // Crear aviso con fecha a 1 hora vista (para que aparezca en pendientes)
            Aviso::create([
                'texto_aviso' => $textoAviso,
                'fecha_aviso' => now()->addHour(),
                'user_id' => 1, // Usuario sistema
                'avisoable_type' => 'AntiScraping',
                'avisoable_id' => 0,
                'oculto' => false,
            ]);

            Log::info('Aviso anti-scraping creado', [
                'tipo' => $tipo,
                'ip' => $ip,
                'fingerprint' => $fingerprint,
                'score' => $score
            ]);

        } catch (\Exception $e) {
            Log::error('Error creando aviso anti-scraping', [
                'error' => $e->getMessage(),
                'tipo' => $tipo,
                'ip' => $ip
            ]);
        }
    }

    /**
     * Genera el texto del aviso según el tipo de bloqueo
     */
    private function generarTextoAviso(string $tipo, string $ip, ?string $fingerprint, int $score, array $detalles): string
    {
        $fingerprintStr = $fingerprint ? substr($fingerprint, 0, 12) . '...' : 'N/A';
        
        switch ($tipo) {
            case 'prolonged_ban':
                return sprintf(
                    "🚨 BLOQUEO PROLONGADO (7 días) - IP: %s | Fingerprint: %s | Score: %d | Endpoint: %s",
                    $ip,
                    $fingerprintStr,
                    $score,
                    $detalles['endpoint'] ?? 'N/A'
                );
            
            case 'temp_ban':
                return sprintf(
                    "⚠️ BLOQUEO TEMPORAL (1 hora) - IP: %s | Fingerprint: %s | Score: %d | Endpoint: %s",
                    $ip,
                    $fingerprintStr,
                    $score,
                    $detalles['endpoint'] ?? 'N/A'
                );
            
            case 'rate_limit':
                return sprintf(
                    "📊 RATE LIMIT EXCEDIDO - IP: %s | Fingerprint: %s | Endpoint: %s | Tipo: %s",
                    $ip,
                    $fingerprintStr,
                    $detalles['endpoint'] ?? 'N/A',
                    $detalles['tipo'] ?? 'N/A'
                );
            
            case 'captcha':
                return sprintf(
                    "🔒 CAPTCHA REQUERIDO - IP: %s | Fingerprint: %s | Score: %d | Endpoint: %s",
                    $ip,
                    $fingerprintStr,
                    $score,
                    $detalles['endpoint'] ?? 'N/A'
                );
            
            default:
                return sprintf(
                    "⚠️ ACTIVIDAD SOSPECHOSA - IP: %s | Fingerprint: %s | Score: %d | Acción: %s",
                    $ip,
                    $fingerprintStr,
                    $score,
                    $tipo
                );
        }
    }

    /**
     * Crea un aviso para picos de actividad sospechosa
     * Solo crea si hay un pico significativo
     */
    public function crearAvisoPico(string $ip, ?string $fingerprint, int $requestsUltimaHora, string $endpoint): void
    {
        // Solo crear aviso si hay más de 50 requests en la última hora (pico significativo)
        if ($requestsUltimaHora < 50) {
            return;
        }

        // Verificar si ya existe un aviso de pico reciente (últimas 6 horas)
        $avisoReciente = Aviso::where('avisoable_type', 'AntiScraping')
            ->where('avisoable_id', 0)
            ->where('texto_aviso', 'like', "%PICO%{$ip}%")
            ->where('created_at', '>=', now()->subHours(6))
            ->first();

        if ($avisoReciente) {
            return;
        }

        $fingerprintStr = $fingerprint ? substr($fingerprint, 0, 12) . '...' : 'N/A';
        
        Aviso::create([
            'texto_aviso' => sprintf(
                "📈 PICO DE ACTIVIDAD - IP: %s | Fingerprint: %s | Requests última hora: %d | Endpoint: %s",
                $ip,
                $fingerprintStr,
                $requestsUltimaHora,
                $endpoint
            ),
            'fecha_aviso' => now()->addHour(),
            'user_id' => 1,
            'avisoable_type' => 'AntiScraping',
            'avisoable_id' => 0,
            'oculto' => false,
        ]);
    }
}





