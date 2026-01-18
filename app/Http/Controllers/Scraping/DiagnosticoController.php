<?php

namespace App\Http\Controllers\Scraping;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OfertaProducto;
use App\Models\Tienda;
use App\Models\EjecucionGlobal;
use Illuminate\Support\Facades\DB;

class DiagnosticoController extends Controller
{
    /**
     * Intervalo de horas para actualizar la oferta más barata de cada producto
     * Cambiar este valor para modificar la frecuencia de actualización
     * Ejemplo: 2 = cada 2 horas, 1 = cada hora, 6 = cada 6 horas
     * NOTA: Las ejecuciones solo se realizan de 8 AM a 11 PM (15 horas activas)
     */
    private const INTERVALO_HORAS_OFERTAS_BARATAS = 1;
    private const HORAS_ACTIVAS_DIARIAS = 15; // 8 AM a 11 PM
    
    /**
     * Mostrar diagnóstico del sistema de scraping
     */
    public function index()
    {
        // Obtener estadísticas de ofertas
        $totalOfertas = OfertaProducto::count();
        $ofertasMostrar = OfertaProducto::where('mostrar', 'si')->count();
        $ofertasOcultas = OfertaProducto::where('mostrar', 'no')->count();
        
        // Obtener ofertas que cumplen criterios para scraping
        $ofertasElegibles = OfertaProducto::with(['producto', 'tienda'])
            ->where('mostrar', 'si')
            ->whereNull('chollo_id')
            ->whereRaw('TIMESTAMPDIFF(MINUTE, updated_at, NOW()) >= frecuencia_actualizar_precio_minutos')
            ->whereHas('tienda', function($query) {
                $query->where('mostrar_tienda', 'si')
                      ->where('scrapear', 'si');
            })
            ->orderByRaw('TIMESTAMPDIFF(MINUTE, updated_at, NOW()) DESC')
            ->limit(10)
            ->get();
        
        // Obtener tiendas disponibles
        $tiendas = Tienda::withCount(['ofertas' => function($query) {
            $query->where('mostrar', 'si');
        }])->get();
        
        // Calcular ejecuciones de scraping por día
        $ejecucionesPorDia = $this->calcularEjecucionesPorDia();
        
        // Calcular ofertas que se van a scrapear (excluyendo tiendas con scrapear = 'no')
        $ofertasScrapeando = $this->calcularOfertasScrapeando();
        
        // Verificar controladores de tiendas disponibles
        $controladoresTiendas = $this->obtenerControladoresTiendas();
        
        // Calcular limitaciones de API
        $limitacionesAPI = $this->calcularLimitacionesAPI();
        
        return view('admin.scraping.diagnostico', compact(
            'totalOfertas',
            'ofertasMostrar', 
            'ofertasOcultas',
            'ofertasElegibles',
            'tiendas',
            'controladoresTiendas',
            'ejecucionesPorDia',
            'ofertasScrapeando',
            'limitacionesAPI'
        ));
    }
    
    /**
     * Obtener lista de controladores de tiendas disponibles
     */
    private function obtenerControladoresTiendas()
    {
        $tiendasPath = app_path('Http/Controllers/Scraping/Tiendas');
        $controladores = [];
        
        if (file_exists($tiendasPath)) {
            $archivos = scandir($tiendasPath);
            
            foreach ($archivos as $archivo) {
                if (pathinfo($archivo, PATHINFO_EXTENSION) === 'php' && 
                    $archivo !== 'PlantillaTiendaController.php' &&
                    $archivo !== 'INSTRUCCIONES_TIENDAS.txt') {
                    
                    $nombreTienda = str_replace('Controller.php', '', $archivo);
                    $controladores[] = $nombreTienda;
                }
            }
        }
        
        sort($controladores);
        return $controladores;
    }
    
    /**
     * Calcular ejecuciones de scraping por día basado en la frecuencia de actualización
     */
    private function calcularEjecucionesPorDia()
    {
        // Obtener todas las ofertas activas con su frecuencia de actualización
        $ofertasActivas = OfertaProducto::with('tienda')
            ->where('mostrar', 'si')
            ->where('frecuencia_actualizar_precio_minutos', '>', 0)
            ->get();
        
        // Obtener todas las tiendas para incluir las que no tienen ofertas activas
        $todasLasTiendas = Tienda::all();
        
        $ejecucionesPorTienda = [];
        $totalEjecuciones = 0;
        
        // Obtener controladores disponibles para verificación
        $controladoresTiendas = $this->obtenerControladoresTiendas();
        
        // Procesar ofertas activas
        foreach ($ofertasActivas as $oferta) {
            $tiendaNombre = $oferta->tienda->nombre;
            $frecuenciaMinutos = $oferta->frecuencia_actualizar_precio_minutos;
            
            // Calcular cuántas veces se ejecuta por día (1440 minutos = 24 horas)
            $ejecucionesPorDia = 1440 / $frecuenciaMinutos;
            
            if (!isset($ejecucionesPorTienda[$tiendaNombre])) {
                // Verificar si existe controlador para esta tienda
                $controladorExiste = $this->verificarControladorTienda($tiendaNombre, $controladoresTiendas);
                
                $ejecucionesPorTienda[$tiendaNombre] = [
                    'ofertas_activas' => 0,
                    'ofertas_totales' => 0,
                    'ejecuciones_por_dia' => 0,
                    'frecuencias' => [],
                    'controlador_existe' => $controladorExiste
                ];
            }
            
            $ejecucionesPorTienda[$tiendaNombre]['ofertas_activas']++;
            
            // Solo contar ejecuciones si la tienda tiene scrapear = 'si'
            if ($oferta->tienda->scrapear === 'si') {
                $ejecucionesPorTienda[$tiendaNombre]['ejecuciones_por_dia'] += $ejecucionesPorDia;
                $ejecucionesPorTienda[$tiendaNombre]['frecuencias'][] = [
                    'minutos' => $frecuenciaMinutos,
                    'ejecuciones_por_dia' => $ejecucionesPorDia
                ];
                $totalEjecuciones += $ejecucionesPorDia;
            }
        }
        
        // Añadir TODAS las tiendas (incluidas las que tienen scrapear=no y mostrar_tienda=no)
        foreach ($todasLasTiendas as $tienda) {
            $tiendaNombre = $tienda->nombre;
            
            if (!isset($ejecucionesPorTienda[$tiendaNombre])) {
                // Verificar si existe controlador para esta tienda
                $controladorExiste = $this->verificarControladorTienda($tiendaNombre, $controladoresTiendas);
                
                $ejecucionesPorTienda[$tiendaNombre] = [
                    'ofertas_activas' => 0,
                    'ofertas_totales' => 0,
                    'ejecuciones_por_dia' => 0,
                    'frecuencias' => [],
                    'controlador_existe' => $controladorExiste
                ];
            }
            
            // Contar ofertas totales para cada tienda
            $ejecucionesPorTienda[$tiendaNombre]['ofertas_totales'] = OfertaProducto::where('tienda_id', $tienda->id)->count();
        }
        
        // Ordenar alfabéticamente por nombre de tienda
        ksort($ejecucionesPorTienda);
        
        // Calcular peticiones adicionales para la oferta más barata de cada producto
        // Contar productos únicos que tienen ofertas activas de tiendas con scrapear = 'si'
        $productosUnicos = DB::table('ofertas_producto')
            ->join('tiendas', 'ofertas_producto.tienda_id', '=', 'tiendas.id')
            ->where('ofertas_producto.mostrar', 'si')
            ->where('tiendas.scrapear', 'si')
            ->distinct('ofertas_producto.producto_id')
            ->count('ofertas_producto.producto_id');
        
        // Calcular peticiones por día basado en el intervalo configurado
        // Solo se ejecutan de 8 AM a 11 PM (15 horas activas)
        $peticionesPorDiaOfertasBaratas = $productosUnicos * (self::HORAS_ACTIVAS_DIARIAS / self::INTERVALO_HORAS_OFERTAS_BARATAS);
        
        $totalEjecucionesConOfertasBaratas = $totalEjecuciones + $peticionesPorDiaOfertasBaratas;
        
        // Calcular total de peticiones al mes (días del mes actual)
        $diasEnMes = date('t'); // Número de días en el mes actual
        $totalPeticionesPorMes = $totalEjecucionesConOfertasBaratas * $diasEnMes;
        
        return [
            'total_ejecuciones_por_dia' => round($totalEjecuciones, 2),
            'total_ejecuciones_con_ofertas_baratas' => round($totalEjecucionesConOfertasBaratas, 2),
            'peticiones_ofertas_baratas_por_dia' => $peticionesPorDiaOfertasBaratas,
            'productos_unicos' => $productosUnicos,
            'total_ofertas_activas' => $ofertasActivas->count(),
            'total_peticiones_por_mes' => round($totalPeticionesPorMes, 2),
            'dias_en_mes' => $diasEnMes,
            'intervalo_horas_ofertas_baratas' => self::INTERVALO_HORAS_OFERTAS_BARATAS,
            'horas_activas_diarias' => self::HORAS_ACTIVAS_DIARIAS,
            'por_tienda' => $ejecucionesPorTienda
        ];
    }
    
    /**
     * Calcular ofertas que se van a scrapear (excluyendo tiendas con scrapear = 'no')
     */
    private function calcularOfertasScrapeando()
    {
        // Obtener ofertas activas solo de tiendas que tienen scrapear = 'si'
        $ofertasScrapeando = OfertaProducto::with('tienda')
            ->where('mostrar', 'si')
            ->where('frecuencia_actualizar_precio_minutos', '>', 0)
            ->whereHas('tienda', function($query) {
                $query->where('scrapear', 'si');
            })
            ->get();
        
        $totalOfertas = $ofertasScrapeando->count();
        
        return [
            'total_ofertas' => $totalOfertas
        ];
    }
    
    /**
     * Normalizar nombre de tienda para comparar con controladores
     */
    private function normalizarNombreTienda($nombreTienda)
    {
        // Convertir a minúsculas y quitar espacios, guiones, puntos, etc.
        $normalizado = strtolower($nombreTienda);
        $normalizado = preg_replace('/[^a-z0-9]/', '', $normalizado);
        return $normalizado;
    }
    
    /**
     * Verificar si existe controlador para una tienda
     */
    private function verificarControladorTienda($nombreTienda, $controladoresTiendas)
    {
        $nombreNormalizado = $this->normalizarNombreTienda($nombreTienda);
        
        foreach ($controladoresTiendas as $controlador) {
            $controladorNormalizado = $this->normalizarNombreTienda($controlador);
            if ($nombreNormalizado === $controladorNormalizado) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Obtener ofertas con errores y éxitos de scraping
     */
    public function ofertasErroresExitos(Request $request)
    {
        $fecha = $request->input('fecha', now()->toDateString());
        $mostrarExitos = $request->input('mostrar_exitos', 'true') === 'true';
        $mostrarErrores = $request->input('mostrar_errores', 'true') === 'true';
        $perPage = $request->input('perPage', 20);
        
        // Obtener ejecuciones del día seleccionado
        $ejecuciones = EjecucionGlobal::where('nombre', 'ejecuciones_scrapear_ofertas')
            ->whereDate('created_at', $fecha)
            ->orderBy('created_at', 'desc')
            ->get();
        
        $ofertasResultados = [];
        $ofertasUltimoEstado = []; // Para trackear el último estado de cada oferta
        
        foreach ($ejecuciones as $ejecucion) {
            // Compatibilidad con formato antiguo y nuevo
            $resultados = [];
            if (isset($ejecucion->log['resultados']) && is_array($ejecucion->log['resultados'])) {
                // Formato nuevo
                $resultados = $ejecucion->log['resultados'];
            } elseif (is_array($ejecucion->log)) {
                // Formato antiguo (el log era directamente un array de resultados)
                $resultados = $ejecucion->log;
            }
            
            if (empty($resultados)) {
                continue;
            }
            
            foreach ($resultados as $resultado) {
                $ofertaId = $resultado['oferta_id'];
                $horaEjecucion = $ejecucion->created_at;
                
                // Solo guardar el resultado más reciente de cada oferta
                if (!isset($ofertasUltimoEstado[$ofertaId]) || 
                    $horaEjecucion > $ofertasUltimoEstado[$ofertaId]['hora']) {
                    
                    $ofertasUltimoEstado[$ofertaId] = [
                        'oferta_id' => $ofertaId,
                        'tienda_nombre' => $resultado['tienda_nombre'],
                        'url' => $resultado['url'],
                        'variante' => $resultado['variante'],
                        'precio_anterior' => $resultado['precio_anterior'],
                        'precio_nuevo' => $resultado['precio_nuevo'],
                        'success' => $resultado['success'],
                        'error' => $resultado['error'],
                        'hora' => $horaEjecucion,
                        'cambios_detectados' => $resultado['cambios_detectados'] ?? false,
                        'url_oferta' => $resultado['url'] // URL de la oferta para el botón "Ir"
                    ];
                }
            }
        }
        
        // Convertir a array y aplicar filtros
        $ofertasResultados = array_values($ofertasUltimoEstado);
        
        // Aplicar filtros
        $ofertasResultados = array_filter($ofertasResultados, function($oferta) use ($mostrarExitos, $mostrarErrores) {
            if ($oferta['success'] === true) {
                return $mostrarExitos;
            } else {
                return $mostrarErrores;
            }
        });
        
        // Ordenar por hora (más reciente primero)
        usort($ofertasResultados, function($a, $b) {
            return $b['hora'] <=> $a['hora'];
        });
        
        // Paginación manual
        $total = count($ofertasResultados);
        $page = $request->input('page', 1);
        $offset = ($page - 1) * $perPage;
        $ofertasPaginadas = array_slice($ofertasResultados, $offset, $perPage);
        
        // Obtener fechas disponibles para el calendario
        $fechasDisponibles = $this->obtenerFechasDisponibles();
        
        // Calcular estadísticas totales del día (sin filtros)
        $todasLasOfertas = array_values($ofertasUltimoEstado);
        $totalExitos = count(array_filter($todasLasOfertas, function($oferta) {
            return $oferta['success'] === true;
        }));
        $totalErrores = count(array_filter($todasLasOfertas, function($oferta) {
            return $oferta['success'] === false;
        }));
        
        return response()->json([
            'ofertas' => $ofertasPaginadas,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => ceil($total / $perPage),
            'fechas_disponibles' => $fechasDisponibles,
            'fecha_seleccionada' => $fecha,
            'mostrar_exitos' => $mostrarExitos,
            'mostrar_errores' => $mostrarErrores,
            'estadisticas_dia' => [
                'exitos' => $totalExitos,
                'errores' => $totalErrores,
                'total' => count($todasLasOfertas)
            ]
        ]);
    }
    
    /**
     * Obtener fechas disponibles para el calendario
     */
    private function obtenerFechasDisponibles()
    {
        $fechas = EjecucionGlobal::where('nombre', 'ejecuciones_scrapear_ofertas')
            ->selectRaw('DATE(created_at) as fecha')
            ->distinct()
            ->orderBy('fecha', 'desc')
            ->pluck('fecha')
            ->toArray();
        
        return $fechas;
    }
    
    /**
     * Calcular limitaciones de API basadas en las ofertas activas
     */
    private function calcularLimitacionesAPI()
    {
        // Obtener todas las ofertas activas con su frecuencia de actualización
        $ofertasActivas = OfertaProducto::with('tienda')
            ->where('mostrar', 'si')
            ->where('frecuencia_actualizar_precio_minutos', '>', 0)
            ->get();
        
        // Obtener todas las tiendas
        $todasLasTiendas = Tienda::all();
        
        $peticionesPorTienda = [];
        $peticionesPorAPI = [];
        $tiendasSinAPI = [];
        
        // Procesar ofertas activas
        foreach ($ofertasActivas as $oferta) {
            $tiendaNombre = $oferta->tienda->nombre;
            $apiTienda = $oferta->tienda->api;
            $frecuenciaMinutos = $oferta->frecuencia_actualizar_precio_minutos;
            
            // Calcular peticiones por día (1440 minutos = 24 horas)
            $peticionesPorDia = 1440 / $frecuenciaMinutos;
            $peticionesPorMes = $peticionesPorDia * 30; // Aproximadamente 30 días
            
            if (!isset($peticionesPorTienda[$tiendaNombre])) {
                $peticionesPorTienda[$tiendaNombre] = [
                    'ofertas_activas' => 0,
                    'ofertas_totales' => 0,
                    'peticiones_por_dia' => 0,
                    'peticiones_por_mes' => 0,
                    'api' => $apiTienda,
                    'scrapear' => $oferta->tienda->scrapear,
                    'mostrar_tienda' => $oferta->tienda->mostrar_tienda
                ];
            }
            
            $peticionesPorTienda[$tiendaNombre]['ofertas_activas']++;
            
            // Solo contar peticiones si la tienda tiene scrapear = 'si'
            if ($oferta->tienda->scrapear === 'si') {
                $peticionesPorTienda[$tiendaNombre]['peticiones_por_dia'] += $peticionesPorDia;
                $peticionesPorTienda[$tiendaNombre]['peticiones_por_mes'] += $peticionesPorMes;
                
                // Clasificar por API solo si scrapear = 'si'
                if ($apiTienda) {
                    if (!isset($peticionesPorAPI[$apiTienda])) {
                        $peticionesPorAPI[$apiTienda] = [
                            'peticiones_por_dia' => 0,
                            'peticiones_por_mes' => 0,
                            'tiendas' => []
                        ];
                    }
                    $peticionesPorAPI[$apiTienda]['peticiones_por_dia'] += $peticionesPorDia;
                    $peticionesPorAPI[$apiTienda]['peticiones_por_mes'] += $peticionesPorMes;
                    
                    if (!in_array($tiendaNombre, $peticionesPorAPI[$apiTienda]['tiendas'])) {
                        $peticionesPorAPI[$apiTienda]['tiendas'][] = $tiendaNombre;
                    }
                } else {
                    if (!in_array($tiendaNombre, $tiendasSinAPI)) {
                        $tiendasSinAPI[] = $tiendaNombre;
                    }
                }
            }
        }
        
        // Añadir TODAS las tiendas (incluidas las que tienen scrapear=no y mostrar_tienda=no)
        foreach ($todasLasTiendas as $tienda) {
            $tiendaNombre = $tienda->nombre;
            
            if (!isset($peticionesPorTienda[$tiendaNombre])) {
                $peticionesPorTienda[$tiendaNombre] = [
                    'ofertas_activas' => 0,
                    'ofertas_totales' => 0,
                    'peticiones_por_dia' => 0,
                    'peticiones_por_mes' => 0,
                    'api' => $tienda->api,
                    'scrapear' => $tienda->scrapear,
                    'mostrar_tienda' => $tienda->mostrar_tienda
                ];
            }
            
            // Contar ofertas totales para cada tienda
            $peticionesPorTienda[$tiendaNombre]['ofertas_totales'] = OfertaProducto::where('tienda_id', $tienda->id)->count();
            
            // Solo añadir a tiendas sin API si scrapear = 'si' y no tiene API
            if ($tienda->scrapear === 'si' && !$tienda->api && !in_array($tiendaNombre, $tiendasSinAPI)) {
                $tiendasSinAPI[] = $tiendaNombre;
            }
        }
        
        // Calcular totales para porcentajes
        $totalPeticionesPorDia = array_sum(array_column($peticionesPorAPI, 'peticiones_por_dia'));
        
        // Añadir porcentajes a cada API
        foreach ($peticionesPorAPI as $api => &$datos) {
            $datos['porcentaje_total'] = $totalPeticionesPorDia > 0 
                ? round(($datos['peticiones_por_dia'] / $totalPeticionesPorDia) * 100, 1) 
                : 0;
        }
        
        // Ordenar alfabéticamente por nombre de tienda
        ksort($peticionesPorTienda);
        
        return [
            'por_api' => $peticionesPorAPI,
            'por_tienda' => $peticionesPorTienda,
            'tiendas_sin_api' => $tiendasSinAPI,
            'total_peticiones_por_dia' => $totalPeticionesPorDia
        ];
    }
}
