<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ActividadSospechosa;
use Illuminate\Support\Facades\DB;

class AntiScrapingController extends Controller
{
    /**
     * Muestra el ranking de fingerprints problemáticos
     */
    public function fingerprintsProblematicos(Request $request)
    {
        $perPage = $request->input('per_page', 50);
        $filtro = $request->input('filtro', 'score'); // score, requests, ips
        
        // Obtener fingerprints con más actividad sospechosa
        $query = ActividadSospechosa::select([
            'fingerprint',
            DB::raw('COUNT(*) as total_actividades'),
            DB::raw('MAX(score) as score_maximo'),
            DB::raw('AVG(score) as score_promedio'),
            DB::raw('COUNT(DISTINCT ip) as ips_unicas'),
            DB::raw('COUNT(DISTINCT endpoint) as endpoints_unicos'),
            DB::raw('MAX(created_at) as ultima_actividad'),
        ])
        ->whereNotNull('fingerprint')
        ->where('fingerprint', '!=', '')
        ->groupBy('fingerprint')
        ->having('total_actividades', '>=', 5); // Mínimo 5 actividades para aparecer

        // Aplicar ordenamiento
        switch ($filtro) {
            case 'score':
                $query->orderBy('score_maximo', 'desc')
                      ->orderBy('score_promedio', 'desc');
                break;
            case 'requests':
                $query->orderBy('total_actividades', 'desc');
                break;
            case 'ips':
                $query->orderBy('ips_unicas', 'desc')
                      ->orderBy('total_actividades', 'desc');
                break;
            default:
                $query->orderBy('score_maximo', 'desc');
        }

        $fingerprints = $query->paginate($perPage);

        // Convertir ultima_actividad a Carbon para cada fingerprint
        $fingerprints->getCollection()->transform(function ($fp) {
            if ($fp->ultima_actividad) {
                $fp->ultima_actividad = \Carbon\Carbon::parse($fp->ultima_actividad);
            }
            return $fp;
        });

        // Obtener estadísticas generales
        $estadisticas = [
            'total_fingerprints' => ActividadSospechosa::whereNotNull('fingerprint')
                ->distinct('fingerprint')
                ->count('fingerprint'),
            'total_actividades' => ActividadSospechosa::count(),
            'bloqueos_prolongados' => ActividadSospechosa::where('accion_tomada', 'prolonged_ban')->count(),
            'bloqueos_temporales' => ActividadSospechosa::where('accion_tomada', 'temp_ban')->count(),
            'captchas_requeridos' => ActividadSospechosa::where('accion_tomada', 'captcha')->count(),
        ];

        return view('admin.anti-scraping.fingerprints', compact('fingerprints', 'estadisticas', 'filtro', 'perPage'));
    }

    /**
     * Muestra detalles de un fingerprint específico
     */
    public function fingerprintDetalle(Request $request, string $fingerprint)
    {
        $perPage = $request->input('per_page', 50);

        // Obtener todas las actividades de este fingerprint
        $actividades = ActividadSospechosa::where('fingerprint', $fingerprint)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        // Estadísticas del fingerprint
        $estadisticas = [
            'total_actividades' => ActividadSospechosa::where('fingerprint', $fingerprint)->count(),
            'ips_unicas' => ActividadSospechosa::where('fingerprint', $fingerprint)
                ->distinct('ip')
                ->count('ip'),
            'score_maximo' => ActividadSospechosa::where('fingerprint', $fingerprint)->max('score'),
            'score_promedio' => ActividadSospechosa::where('fingerprint', $fingerprint)->avg('score'),
            'bloqueos_prolongados' => ActividadSospechosa::where('fingerprint', $fingerprint)
                ->where('accion_tomada', 'prolonged_ban')
                ->count(),
            'bloqueos_temporales' => ActividadSospechosa::where('fingerprint', $fingerprint)
                ->where('accion_tomada', 'temp_ban')
                ->count(),
            'primera_actividad' => ActividadSospechosa::where('fingerprint', $fingerprint)
                ->min('created_at'),
            'ultima_actividad' => ActividadSospechosa::where('fingerprint', $fingerprint)
                ->max('created_at'),
        ];

        // IPs asociadas a este fingerprint
        $ips = ActividadSospechosa::select('ip', DB::raw('COUNT(*) as total'))
            ->where('fingerprint', $fingerprint)
            ->groupBy('ip')
            ->orderBy('total', 'desc')
            ->get();

        return view('admin.anti-scraping.fingerprint-detalle', compact(
            'fingerprint',
            'actividades',
            'estadisticas',
            'ips',
            'perPage'
        ));
    }
}

