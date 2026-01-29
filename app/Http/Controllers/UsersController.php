<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\UserActivity;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class UsersController extends Controller
{
    public function index(Request $request)
    {
        // Obtener todos los usuarios
        $usuarios = User::orderBy('name')->get();
        
        // Usuario seleccionado (por defecto "todos" o null)
        $usuarioId = $request->input('usuario_id');
        if ($usuarioId === 'todos' || $usuarioId === null || $usuarioId === '') {
            $usuarioId = null;
        }
        
        // Obtener filtro rápido
        $filtroRapido = $request->input('filtro_rapido', '30dias');
        
        // Procesar filtro rápido
        $hoy = now();
        
        switch($filtroRapido) {
            case 'hoy':
                $fechaDesde = $fechaHasta = $hoy->toDateString();
                break;
            case 'ayer':
                $fechaDesde = $fechaHasta = $hoy->copy()->subDay()->toDateString();
                break;
            case '7dias':
                $fechaDesde = $hoy->copy()->subDays(7)->toDateString();
                $fechaHasta = $hoy->toDateString();
                break;
            case '30dias':
                $fechaDesde = $hoy->copy()->subDays(30)->toDateString();
                $fechaHasta = $hoy->toDateString();
                break;
            case '90dias':
                $fechaDesde = $hoy->copy()->subDays(90)->toDateString();
                $fechaHasta = $hoy->toDateString();
                break;
            case '180dias':
                $fechaDesde = $hoy->copy()->subDays(180)->toDateString();
                $fechaHasta = $hoy->toDateString();
                break;
            case '1año':
                $fechaDesde = $hoy->copy()->subYear()->toDateString();
                $fechaHasta = $hoy->toDateString();
                break;
            case 'siempre':
                $fechaDesde = '1900-01-01';
                $fechaHasta = $hoy->toDateString();
                break;
            default:
                $fechaDesde = $request->input('fecha_desde', $hoy->copy()->subDays(30)->toDateString());
                $fechaHasta = $request->input('fecha_hasta', $hoy->toDateString());
                break;
        }
        
        // Obtener estadísticas del usuario seleccionado
        $estadisticas = $this->obtenerEstadisticas($usuarioId, $fechaDesde, $fechaHasta);
        
        // Obtener período de agrupación (día, mes, año)
        $periodo = $request->input('periodo', 'dia');
        
        // Obtener datos para la gráfica
        $datosGrafica = $this->obtenerDatosGrafica($usuarioId, $fechaDesde, $fechaHasta, $periodo);
        
        // Obtener últimos movimientos con paginación
        $movimientos = $this->obtenerMovimientos($usuarioId, $fechaDesde, $fechaHasta);
        
        return view('admin.users.index', compact(
            'usuarios',
            'usuarioId',
            'filtroRapido',
            'fechaDesde',
            'fechaHasta',
            'estadisticas',
            'datosGrafica',
            'movimientos',
            'periodo'
        ));
    }
    
    private function obtenerEstadisticas($usuarioId, $fechaDesde, $fechaHasta)
    {
        $query = UserActivity::whereBetween('created_at', [$fechaDesde . ' 00:00:00', $fechaHasta . ' 23:59:59']);
        
        if ($usuarioId) {
            $query->where('user_id', $usuarioId);
        }
        
        return [
            'productos_creados' => (clone $query)->where('action_type', UserActivity::ACTION_PRODUCTO_CREADO)->count(),
            'productos_modificados' => (clone $query)->where('action_type', UserActivity::ACTION_PRODUCTO_MODIFICADO)->count(),
            'ofertas_creadas' => (clone $query)->where('action_type', UserActivity::ACTION_OFERTA_CREADA)->count(),
            'ofertas_modificadas' => (clone $query)->where('action_type', UserActivity::ACTION_OFERTA_MODIFICADA)->count(),
        ];
    }
    
    private function obtenerDatosGrafica($usuarioId, $fechaDesde, $fechaHasta, $periodo = 'dia')
    {
        // Determinar el formato de agrupación según el período
        switch($periodo) {
            case 'mes':
                $formatoFecha = 'Y-m';
                $selectRaw = 'DATE_FORMAT(created_at, "%Y-%m") as fecha';
                break;
            case 'año':
                $formatoFecha = 'Y';
                $selectRaw = 'YEAR(created_at) as fecha';
                break;
            case 'dia':
            default:
                $formatoFecha = 'Y-m-d';
                $selectRaw = 'DATE(created_at) as fecha';
                break;
        }
        
        // Obtener datos agrupados según el período
        $queryProductos = UserActivity::where('action_type', UserActivity::ACTION_PRODUCTO_CREADO)
            ->whereBetween('created_at', [$fechaDesde . ' 00:00:00', $fechaHasta . ' 23:59:59']);
        
        if ($usuarioId) {
            $queryProductos->where('user_id', $usuarioId);
        }
        
        $productos = $queryProductos->selectRaw($selectRaw . ', COUNT(*) as total')
            ->groupBy('fecha')
            ->orderBy('fecha')
            ->get()
            ->mapWithKeys(function ($item) {
                return [strval($item->fecha) => $item];
            });
        
        $queryOfertas = UserActivity::where('action_type', UserActivity::ACTION_OFERTA_CREADA)
            ->whereBetween('created_at', [$fechaDesde . ' 00:00:00', $fechaHasta . ' 23:59:59']);
        
        if ($usuarioId) {
            $queryOfertas->where('user_id', $usuarioId);
        }
        
        $ofertas = $queryOfertas->selectRaw($selectRaw . ', COUNT(*) as total')
            ->groupBy('fecha')
            ->orderBy('fecha')
            ->get()
            ->mapWithKeys(function ($item) {
                return [strval($item->fecha) => $item];
            });
        
        // Generar todas las fechas del rango según el período
        $fechas = [];
        $current = Carbon::parse($fechaDesde);
        $end = Carbon::parse($fechaHasta);
        
        while ($current <= $end) {
            if ($periodo === 'mes') {
                $fechas[] = $current->format('Y-m');
                $current->addMonth()->startOfMonth();
            } elseif ($periodo === 'año') {
                $fechas[] = $current->format('Y');
                $current->addYear()->startOfYear();
            } else {
                $fechas[] = $current->format('Y-m-d');
                $current->addDay();
            }
        }
        
        $labels = [];
        $productosData = [];
        $ofertasData = [];
        
        foreach ($fechas as $fecha) {
            if ($periodo === 'mes') {
                // Formato: Ene/2025
                try {
                    $carbon = Carbon::createFromFormat('Y-m', $fecha);
                    $meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
                    $labels[] = $meses[$carbon->month - 1] . '/' . $carbon->year;
                } catch (\Exception $e) {
                    $labels[] = $fecha;
                }
            } elseif ($periodo === 'año') {
                $labels[] = $fecha;
            } else {
                $labels[] = Carbon::parse($fecha)->format('d/m');
            }
            
            $productosData[] = $productos->get($fecha)?->total ?? 0;
            $ofertasData[] = $ofertas->get($fecha)?->total ?? 0;
        }
        
        return [
            'labels' => $labels,
            'productos_creados' => $productosData,
            'ofertas_creadas' => $ofertasData,
        ];
    }
    
    private function obtenerMovimientos($usuarioId, $fechaDesde, $fechaHasta)
    {
        $query = UserActivity::with(['user', 'producto', 'oferta.producto', 'oferta.tienda'])
            ->whereBetween('created_at', [$fechaDesde . ' 00:00:00', $fechaHasta . ' 23:59:59'])
            ->orderBy('created_at', 'desc');
        
        if ($usuarioId) {
            $query->where('user_id', $usuarioId);
        }
        
        return $query->paginate(20)->appends(request()->query());
    }
}

