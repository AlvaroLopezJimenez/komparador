<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Models\ActividadSospechosa;

class AntiScrapingScoringService
{
    /**
     * Calcula el score de sospecha basado en patrones repetidos
     */
    public function calcularScore(Request $request, string $endpoint): int
    {
        $ip = $request->ip();
        $fingerprint = $request->header('X-Fingerprint');
        $score = 0;
        
        $config = config('anti-scraping.scoring.penalties');
        
        // 1. Productos por minuto (solo si > threshold)
        $productosUltimoMinuto = $this->contarProductosUltimoMinuto($ip);
        if ($productosUltimoMinuto > $config['productos_por_minuto']['threshold']) {
            $extra = $productosUltimoMinuto - $config['productos_por_minuto']['threshold'];
            $points = $extra * $config['productos_por_minuto']['points_per_product'];
            $score += min($points, $config['productos_por_minuto']['max_points']);
        }
        
        // 2. Acceso secuencial (solo si patrón repetido)
        $secuenciales = $this->contarAccesosSecuenciales($ip);
        if ($secuenciales >= 20) {
            $score += $config['acceso_secuencial']['points'][20];
        } elseif ($secuenciales >= 10) {
            $score += $config['acceso_secuencial']['points'][10];
        } elseif ($secuenciales >= $config['acceso_secuencial']['min_sequential']) {
            $score += $config['acceso_secuencial']['points'][5];
        }
        
        // 3. Sin pausas humanas (solo si patrón continuo)
        $sinPausas = $this->contarRequestsSinPausa($ip, $config['sin_pausas_humanas']['threshold_seconds']);
        if ($sinPausas >= $config['sin_pausas_humanas']['min_requests']) {
            $score += $config['sin_pausas_humanas']['points'];
        }
        
        // 4. No carga recursos estáticos (solo si patrón continuo)
        $sinRecursos = $this->contarRequestsSinRecursos($ip);
        if ($sinRecursos >= $config['no_recursos_estaticos']['min_requests']) {
            $score += $config['no_recursos_estaticos']['points'];
        }
        
        // 5. Fingerprint desde múltiples IPs
        if ($fingerprint) {
            $ipsDelFingerprint = $this->contarIPsDelFingerprint($fingerprint);
            if ($ipsDelFingerprint >= $config['fingerprint_multiple_ips']['min_ips']) {
                $score += $config['fingerprint_multiple_ips']['points'];
            }
            
            // 7. Fingerprint reutilizado
            $reutilizaciones = $this->contarReutilizacionesFingerprint($fingerprint);
            if ($reutilizaciones >= $config['fingerprint_reutilizado']['min_reuses']) {
                $score += $config['fingerprint_reutilizado']['points'];
            }
        }
        
        // 6. Acceso directo (solo si patrón repetido)
        $accesosDirectos = $this->contarAccesosDirectos($ip);
        if ($accesosDirectos >= 20) {
            $score += $config['acceso_directo_endpoint']['points'][20];
        } elseif ($accesosDirectos >= 10) {
            $score += $config['acceso_directo_endpoint']['points'][10];
        } elseif ($accesosDirectos >= $config['acceso_directo_endpoint']['min_direct_access']) {
            $score += $config['acceso_directo_endpoint']['points'][5];
        }
        
        return (int) $score;
    }
    
    /**
     * Cuenta productos accedidos en el último minuto
     */
    private function contarProductosUltimoMinuto(string $ip): int
    {
        $cacheKey = "scoring_productos_minuto_{$ip}";
        
        return Cache::remember($cacheKey, 60, function () use ($ip) {
            return ActividadSospechosa::where('ip', $ip)
                ->where('endpoint', 'like', '%/ofertas/%')
                ->where('created_at', '>', now()->subMinute())
                ->distinct('endpoint')
                ->count();
        });
    }
    
    /**
     * Cuenta accesos secuenciales (IDs consecutivos)
     */
    private function contarAccesosSecuenciales(string $ip): int
    {
        $cacheKey = "scoring_secuenciales_{$ip}";
        
        return Cache::remember($cacheKey, 60, function () use ($ip) {
            $ultimosAccesos = ActividadSospechosa::where('ip', $ip)
                ->where('endpoint', 'like', '%/ofertas/%')
                ->where('created_at', '>', now()->subMinutes(5))
                ->orderBy('created_at', 'desc')
                ->limit(30)
                ->get()
                ->map(function ($actividad) {
                    // Extraer ID del producto del endpoint
                    if (preg_match('/\/ofertas\/(\d+)/', $actividad->endpoint, $matches)) {
                        return (int) $matches[1];
                    }
                    return null;
                })
                ->filter()
                ->values()
                ->toArray();
            
            if (count($ultimosAccesos) < 5) {
                return 0;
            }
            
            // Verificar si son secuenciales
            $secuenciales = 0;
            $maxSecuenciales = 0;
            
            for ($i = 1; $i < count($ultimosAccesos); $i++) {
                if ($ultimosAccesos[$i] === $ultimosAccesos[$i - 1] + 1) {
                    $secuenciales++;
                    $maxSecuenciales = max($maxSecuenciales, $secuenciales);
                } else {
                    $secuenciales = 0;
                }
            }
            
            return $maxSecuenciales + 1; // +1 porque empezamos desde el segundo elemento
        });
    }
    
    /**
     * Cuenta requests sin pausa (< threshold segundos)
     */
    private function contarRequestsSinPausa(string $ip, int $thresholdSeconds): int
    {
        $cacheKey = "scoring_sin_pausa_{$ip}_{$thresholdSeconds}";
        
        return Cache::remember($cacheKey, 60, function () use ($ip, $thresholdSeconds) {
            $requests = ActividadSospechosa::where('ip', $ip)
                ->where('created_at', '>', now()->subMinutes(5))
                ->orderBy('created_at', 'asc')
                ->get(['created_at']);
            
            if ($requests->count() < 10) {
                return 0;
            }
            
            $sinPausa = 0;
            $maxSinPausa = 0;
            
            for ($i = 1; $i < $requests->count(); $i++) {
                $diff = $requests[$i]->created_at->diffInSeconds($requests[$i - 1]->created_at);
                if ($diff < $thresholdSeconds) {
                    $sinPausa++;
                    $maxSinPausa = max($maxSinPausa, $sinPausa);
                } else {
                    $sinPausa = 0;
                }
            }
            
            return $maxSinPausa + 1;
        });
    }
    
    /**
     * Cuenta requests sin carga de recursos estáticos
     */
    private function contarRequestsSinRecursos(string $ip): int
    {
        $cacheKey = "scoring_sin_recursos_{$ip}";
        
        return Cache::remember($cacheKey, 300, function () use ($ip) {
            // Verificar si hay requests a recursos estáticos en los últimos 5 minutos
            $conRecursos = ActividadSospechosa::where('ip', $ip)
                ->where('created_at', '>', now()->subMinutes(5))
                ->where(function ($query) {
                    $query->where('endpoint', 'like', '%.js')
                          ->orWhere('endpoint', 'like', '%.css')
                          ->orWhere('endpoint', 'like', '%.png')
                          ->orWhere('endpoint', 'like', '%.jpg')
                          ->orWhere('endpoint', 'like', '%.webp');
                })
                ->count();
            
            if ($conRecursos > 0) {
                return 0; // Si carga recursos, no es sospechoso
            }
            
            // Si no carga recursos, contar requests a endpoints de datos
            return ActividadSospechosa::where('ip', $ip)
                ->where('created_at', '>', now()->subMinutes(5))
                ->where(function ($query) {
                    $query->where('endpoint', 'like', '%/api/ofertas/%')
                          ->orWhere('endpoint', 'like', '%/api/especificaciones/%')
                          ->orWhere('endpoint', 'like', '%/api/precios-historicos/%');
                })
                ->count();
        });
    }
    
    /**
     * Cuenta IPs diferentes que usan el mismo fingerprint
     */
    private function contarIPsDelFingerprint(string $fingerprint): int
    {
        $cacheKey = "scoring_fp_ips_{$fingerprint}";
        
        return Cache::remember($cacheKey, 300, function () use ($fingerprint) {
            return ActividadSospechosa::where('fingerprint', $fingerprint)
                ->where('created_at', '>', now()->subHours(24))
                ->distinct('ip')
                ->count('ip');
        });
    }
    
    /**
     * Cuenta accesos directos a endpoints (sin pasar por HTML)
     */
    private function contarAccesosDirectos(string $ip): int
    {
        $cacheKey = "scoring_directos_{$ip}";
        
        return Cache::remember($cacheKey, 300, function () use ($ip) {
            // Verificar si hay acceso previo a la página HTML antes de acceder a endpoints
            $accesosDirectos = 0;
            
            $requests = ActividadSospechosa::where('ip', $ip)
                ->where('created_at', '>', now()->subMinutes(10))
                ->orderBy('created_at', 'asc')
                ->get();
            
            foreach ($requests as $request) {
                // Si es un endpoint de API
                if (str_contains($request->endpoint, '/api/')) {
                    // Buscar si hay acceso a HTML antes de este request (en los últimos 30 segundos)
                    $hayAccesoHTML = ActividadSospechosa::where('ip', $ip)
                        ->where('endpoint', 'not like', '%/api/%')
                        ->where('created_at', '>=', $request->created_at->subSeconds(30))
                        ->where('created_at', '<=', $request->created_at)
                        ->exists();
                    
                    if (!$hayAccesoHTML) {
                        $accesosDirectos++;
                    }
                }
            }
            
            return $accesosDirectos;
        });
    }
    
    /**
     * Cuenta reutilizaciones del mismo fingerprint
     */
    private function contarReutilizacionesFingerprint(string $fingerprint): int
    {
        $cacheKey = "scoring_fp_reuses_{$fingerprint}";
        
        return Cache::remember($cacheKey, 300, function () use ($fingerprint) {
            // Contar cuántas veces se ha usado este fingerprint en las últimas 24 horas
            return ActividadSospechosa::where('fingerprint', $fingerprint)
                ->where('created_at', '>', now()->subHours(24))
                ->count();
        });
    }
}

