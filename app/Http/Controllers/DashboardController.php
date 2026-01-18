<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Producto;
use App\Models\OfertaProducto;
use App\Models\Tienda;
use App\Models\Aviso;
use App\Models\Click;
use App\Models\EjecucionGlobal;
use App\Models\Chollo;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{

    public function index()
    {
        try {
            $userId = auth()->id();
            
            // Contar avisos vencidos usando el nuevo sistema (solo visibles)
            $totalAvisos = Aviso::vencidos()
                ->visibles()
                ->visiblesPorUsuario($userId)
                ->count();

            // Estadísticas para la barra compacta del día actual
            $estadisticasCompactas = $this->obtenerEstadisticasCompactas();

            // Contar chollos pendientes de comprobar
            $chollosPendientes = $this->contarChollosPendientes();

            return view('admin.dashboard', compact('totalAvisos', 'estadisticasCompactas', 'chollosPendientes'));
        } catch (\Exception $e) {
            // Si hay algún error, devolver un mensaje simple para diagnosticar
            return 'Error en DashboardController: ' . $e->getMessage();
        }
    }

    private function obtenerEstadisticasCompactas()
    {
        $hoy = today()->format('Y-m-d');
        
        // Clicks de hoy (reutilizando la lógica del ClickController)
        $clicksHoy = Click::whereDate('created_at', $hoy)->count();
        
        // Estadísticas del scraper del día actual (reutilizando la lógica del OfertaProductoController)
        $ejecucionesHoy = EjecucionGlobal::where('nombre', 'ejecuciones_scrapear_ofertas')
            ->whereDate('created_at', $hoy)
            ->get();
        
        $totalEjecuciones = $ejecucionesHoy->count();
        $totalOfertas = $ejecucionesHoy->sum('total'); // Corregido: usar 'total' en lugar de 'total_ofertas'
        $totalActualizadas = $ejecucionesHoy->sum('total_guardado');
        $totalErrores = $ejecucionesHoy->sum('total_errores');
        
        // Porcentaje de errores sobre total de ofertas
        $porcentajeErrores = $totalOfertas > 0 ? round(($totalErrores / $totalOfertas) * 100, 1) : 0;

        // Estadísticas de "actualizar primera oferta" del día actual
        $ejecucionesPrimeraOfertaHoy = EjecucionGlobal::where('nombre', 'actualizar_primera_oferta')
            ->whereDate('created_at', $hoy)
            ->get();
        
        $totalOfertasPrimeraOferta = $ejecucionesPrimeraOfertaHoy->sum('total');
        $totalErroresPrimeraOferta = $ejecucionesPrimeraOfertaHoy->sum('total_errores');
        
        // Porcentaje de errores sobre total de ofertas de primera oferta
        $porcentajeErroresPrimeraOferta = $totalOfertasPrimeraOferta > 0 ? round(($totalErroresPrimeraOferta / $totalOfertasPrimeraOferta) * 100, 1) : 0;

        return [
            'clicksHoy' => $clicksHoy,
            'totalEjecuciones' => $totalEjecuciones,
            'totalOfertas' => $totalOfertas,
            'totalActualizadas' => $totalActualizadas,
            'totalErrores' => $totalErrores,
            'porcentajeErrores' => $porcentajeErrores,
            'totalOfertasPrimeraOferta' => $totalOfertasPrimeraOferta,
            'totalErroresPrimeraOferta' => $totalErroresPrimeraOferta,
            'porcentajeErroresPrimeraOferta' => $porcentajeErroresPrimeraOferta
        ];
    }

    /**
     * Contar ofertas de chollos y manuales pendientes de comprobar
     */
    private function contarChollosPendientes()
    {
        $ahora = Carbon::now();

        // Obtener ofertas de chollos pendientes de comprobar
        $ofertasChollos = OfertaProducto::with('chollo')
            ->whereNotNull('chollo_id')
            ->whereNotNull('frecuencia_comprobacion_chollos_min')
            ->whereHas('chollo', function ($query) use ($ahora) {
                $query->where('finalizada', 'no')
                    ->where('mostrar', 'si')
                    ->where('fecha_inicio', '<=', $ahora)
                    ->where(function ($q) use ($ahora) {
                        $q->whereNull('fecha_final')
                            ->orWhere('fecha_final', '>', $ahora);
                    });
            })
            ->get()
            ->filter(function ($oferta) use ($ahora) {
                // Si nunca se ha comprobado, está pendiente
                if (!$oferta->comprobada) {
                    return true;
                }

                // Calcular minutos desde la última comprobación
                $minutosDesdeComprobada = $ahora->diffInMinutes($oferta->comprobada);
                
                // Verificar si ha pasado el tiempo de frecuencia
                return $minutosDesdeComprobada >= $oferta->frecuencia_comprobacion_chollos_min;
            });

        // Obtener ofertas manuales que han superado su tiempo de actualización
        $ofertasManuales = OfertaProducto::with(['producto', 'tienda', 'chollo'])
            ->where('mostrar', 'si')
            ->where('como_scrapear', 'manual')
            ->whereNull('chollo_id')
            ->whereHas('tienda', function($query) {
                $query->where('scrapear', 'si');
            })
            ->whereRaw('TIMESTAMPDIFF(MINUTE, updated_at, NOW()) >= frecuencia_actualizar_precio_minutos')
            ->get();

        // Combinar ambas colecciones
        $total = $ofertasChollos->count() + $ofertasManuales->count();

        return $total;
    }
}
