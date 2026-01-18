<?php

namespace App\Http\Controllers\Scraping;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OfertaProducto;
use App\Models\EjecucionGlobal;
use App\Models\Producto;
use App\Models\ProductoOfertaMasBarataPorProducto;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ActualizarPrimeraOfertaController extends ScraperBaseController
{
    /**
     * Vista principal de ejecución en tiempo real
     */
    public function index()
    {
        return view('admin.scraping.actualizar-primera-oferta');
    }

    /**
     * Iniciar una nueva ejecución en tiempo real
     */
    public function iniciar(Request $request)
    {
        try {
            // Obtener ofertas elegibles (primera oferta de cada producto)
            $ofertasElegibles = $this->obtenerPrimerasOfertasElegibles();
        
        if ($ofertasElegibles->isEmpty()) {
            // No crear ejecución si no hay ofertas para procesar
            return response()->json([
                'success' => true,
                'completada' => true,
                'ejecucion_id' => null,
                'total_ofertas' => 0,
                'ofertas' => [],
                'total_guardado' => 0,
                'total_errores' => 0,
                'message' => 'No hay ofertas para actualizar',
                'progreso' => [
                    'actualizadas' => 0,
                    'errores' => 0,
                    'procesadas' => 0
                ]
            ]);
        }

        // Crear registro de ejecución solo si hay ofertas para procesar
        $ejecucion = EjecucionGlobal::create([
            'inicio' => now(),
            'nombre' => 'actualizar_primera_oferta',
            'total' => $ofertasElegibles->count(),
            'total_guardado' => 0,
            'total_errores' => 0,
            'log' => [
                'token' => 'srtocoque',
                'estado' => 'iniciada',
                'total_ofertas' => $ofertasElegibles->count(),
                'ofertas' => $ofertasElegibles->pluck('id')->toArray(),
                'actualizadas' => 0,
                'errores' => 0,
                'procesadas' => 0,
                'resultados' => []
            ]
        ]);

        return response()->json([
            'success' => true,
            'ejecucion_id' => $ejecucion->id,
            'total_ofertas' => $ofertasElegibles->count(),
            'ofertas' => $ofertasElegibles->pluck('id')->toArray()
        ]);
        } catch (\Exception $e) {
            Log::error('Error en ActualizarPrimeraOfertaController::iniciar: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'error' => 'Error interno del servidor: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Procesar la siguiente oferta
     */
    public function procesarSiguiente(Request $request)
    {
        $ejecucionId = $request->input('ejecucion_id');
        $indiceActual = $request->input('indice_actual', 0);
        
        $ejecucion = EjecucionGlobal::findOrFail($ejecucionId);
        $log = $ejecucion->log;
        
        // Obtener ofertas elegibles
        $ofertasElegibles = $this->obtenerPrimerasOfertasElegibles();
        
        if ($indiceActual >= $ofertasElegibles->count()) {
            // Ejecución completada
            $ejecucion->update([
                'fin' => now(),
                'log' => array_merge($log, ['estado' => 'completada'])
            ]);
            
            return response()->json([
                'success' => true,
                'completada' => true,
                'progreso' => [
                    'actualizadas' => $log['actualizadas'] ?? 0,
                    'errores' => $log['errores'] ?? 0,
                    'procesadas' => $log['procesadas'] ?? 0
                ]
            ]);
        }
        
        $oferta = $ofertasElegibles[$indiceActual];
        
        try {
            $resultado = $this->procesarOfertaScraper($oferta);
            $log['resultados'][] = $resultado;
            
            if (!empty($resultado['success'])) {
                $log['actualizadas'] = ($log['actualizadas'] ?? 0) + 1;
            } else {
                $log['errores'] = ($log['errores'] ?? 0) + 1;
            }
            
            $log['procesadas'] = ($log['procesadas'] ?? 0) + 1;
            
            $ejecucion->update([
                'total_guardado' => $log['actualizadas'],
                'total_errores' => $log['errores'],
                'log' => $log
            ]);
            
            return response()->json([
                'success' => true,
                'oferta_actual' => [
                    'id' => $oferta->id,
                    'tienda' => $oferta->tienda->nombre,
                    'url' => $oferta->url,
                    'precio_anterior' => $resultado['precio_anterior'],
                    'precio_nuevo' => $resultado['precio_nuevo'],
                    'success' => $resultado['success'],
                    'error' => $resultado['error'] ?? null
                ],
                'progreso' => [
                    'actualizadas' => $log['actualizadas'],
                    'errores' => $log['errores'],
                    'procesadas' => $log['procesadas'],
                    'total' => $ofertasElegibles->count()
                ]
            ]);
            
        } catch (\Exception $e) {
            $log['errores'] = ($log['errores'] ?? 0) + 1;
            $log['procesadas'] = ($log['procesadas'] ?? 0) + 1;
            $log['resultados'][] = [
                'oferta_id' => $oferta->id,
                'tienda_nombre' => $oferta->tienda->nombre ?? null,
                'url' => $oferta->url,
                'variante' => $oferta->variante,
                'precio_anterior' => $oferta->precio_total,
                'precio_nuevo' => null,
                'success' => false,
                'error' => $e->getMessage(),
                'cambios_detectados' => false,
                'url_notificacion_llamada' => false,
            ];
            
            $ejecucion->update([
                'total_errores' => $log['errores'],
                'log' => $log
            ]);
            
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'oferta_actual' => [
                    'id' => $oferta->id,
                    'tienda' => $oferta->tienda->nombre,
                    'url' => $oferta->url,
                    'precio_anterior' => $oferta->precio_total,
                    'precio_nuevo' => null,
                    'success' => false,
                    'error' => $e->getMessage()
                ],
                'progreso' => [
                    'actualizadas' => $log['actualizadas'] ?? 0,
                    'errores' => $log['errores'],
                    'procesadas' => $log['procesadas'],
                    'total' => $ofertasElegibles->count()
                ]
            ]);
        }
    }

    /**
     * Ejecutar en segundo plano (para cron jobs)
     */
    public function ejecutarSegundoPlano(Request $request)
    {
        // Verificar token de seguridad
        $token = $request->query('token');
        if (!$token || $token !== env('TOKEN_ACTUALIZAR_PRECIOS')) {
            return response()->json([
                'status' => 'error',
                'message' => 'Token inválido'
            ], 403);
        }

        return $this->procesarActualizacionPrimeraOferta($token);
    }

    /**
     * Procesar actualización de primera oferta (método principal)
     */
    private function procesarActualizacionPrimeraOferta($token = null)
    {
        // Evitar timeout en ejecuciones largas
        set_time_limit(0);

        // 1) Selección de ofertas a procesar (primera oferta de cada producto) ANTES de crear la ejecución
        $ofertas = $this->obtenerPrimerasOfertasElegibles();

        $totalOfertas = $ofertas->count();

        // Si no hay ofertas para procesar, no crear ejecución y retornar directamente
        if ($totalOfertas === 0) {
            return response()->json([
                'status'         => 'ok',
                'total_ofertas'  => 0,
                'actualizadas'   => 0,
                'errores'        => 0,
                'message'        => 'No hay ofertas para actualizar'
            ]);
        }

        // Solo crear la ejecución si hay ofertas para procesar
        $ejecucion = EjecucionGlobal::create([
            'inicio' => now(),
            'nombre' => 'actualizar_primera_oferta',
            'log'    => $token ? ['token' => $token] : [],
        ]);

        $actualizadas = 0;
        $errores      = 0;
        $log          = [];

        // 2) Procesar ofertas de forma secuencial sin pausas
        foreach ($ofertas as $oferta) {
            try {
                $resultado = $this->procesarOfertaScraper($oferta);
                $log[] = $resultado;

                if (!empty($resultado['success'])) {
                    $actualizadas++;
                } else {
                    $errores++;
                }

            } catch (\Exception $e) {
                $errores++;
                $log[] = [
                    'oferta_id'                 => $oferta->id,
                    'tienda_nombre'             => $oferta->tienda->nombre ?? null,
                    'url'                       => $oferta->url,
                    'variante'                  => $oferta->variante,
                    'precio_anterior'           => $oferta->precio_total,
                    'precio_nuevo'              => null,
                    'success'                   => false,
                    'error'                     => $e->getMessage(),
                    'cambios_detectados'        => false,
                    'url_notificacion_llamada'  => false,
                ];
            }
        }

        // Crear estructura JSON organizada
        $logEstructurado = [
            'token' => $token,
            'estado' => 'completada',
            'total_ofertas' => $totalOfertas,
            'ofertas' => $ofertas->pluck('id')->toArray(),
            'actualizadas' => $actualizadas,
            'errores' => $errores,
            'procesadas' => $totalOfertas,
            'resultados' => $log
        ];

        $ejecucion->update([
            'fin'            => now(),
            'total'          => $totalOfertas,
            'total_guardado' => $actualizadas,
            'total_errores'  => $errores,
            'log'            => $logEstructurado,
        ]);

        return response()->json([
            'status'         => 'ok',
            'total_ofertas'  => $totalOfertas,
            'actualizadas'   => $actualizadas,
            'errores'        => $errores,
        ]);
    }

    /**
     * Obtener la primera oferta (más barata) de cada producto
     * Consulta la tabla producto_oferta_mas_barata_por_producto para obtener el oferta_id
     * Luego obtiene todos los datos de la oferta desde la tabla de ofertas
     * Solo incluye productos cuya oferta más barata tenga tienda con scrapear='si'
     */
    protected function obtenerPrimerasOfertasElegibles()
    {
        // Obtener todos los IDs de ofertas más baratas desde la tabla producto_oferta_mas_barata_por_producto
        $idsOfertasMasBaratas = ProductoOfertaMasBarataPorProducto::pluck('oferta_id')
            ->filter()
            ->unique()
            ->toArray();

        // Si no hay IDs, devolver colección vacía
        if (empty($idsOfertasMasBaratas)) {
            return collect();
        }

        // Obtener todas las ofertas ORIGINALES desde la BD usando los IDs
        // Esto garantiza que procesarOfertaScraper reciba ofertas con valores reales (sin descuentos aplicados)
        $ofertasOriginales = OfertaProducto::with(['producto', 'tienda'])
            ->whereIn('id', $idsOfertasMasBaratas)
            ->where('mostrar', 'si')
            ->whereNull('chollo_id')
            ->get();

        // Filtrar solo las ofertas cuya tienda tenga scrapear='si'
        $ofertasFiltradas = $ofertasOriginales->filter(function($oferta) {
            return $oferta->tienda && $oferta->tienda->scrapear === 'si';
        });

        // Ordenar por fecha de actualización más antigua primero
        return $ofertasFiltradas->sortBy('updated_at')->values();
    }

    /**
     * Vista del historial de ejecuciones
     */
    public function historialEjecuciones(Request $request)
    {
        // Obtener parámetros de filtro
        $filtroRapido = $request->input('filtro_rapido', 'hoy');
        $fechaDesde = $request->input('fecha_desde');
        $fechaHasta = $request->input('fecha_hasta');
        $horaDesde = $request->input('hora_desde');
        $horaHasta = $request->input('hora_hasta');
        $busqueda = $request->input('buscar');

        // Aplicar filtros de fecha según el filtro rápido
        if ($filtroRapido && $filtroRapido !== 'siempre') {
            $hoy = now();
            
            switch ($filtroRapido) {
                case 'hoy':
                    $fechaDesde = $fechaHasta = $hoy->format('Y-m-d');
                    break;
                case 'ayer':
                    $ayer = $hoy->copy()->subDay();
                    $fechaDesde = $fechaHasta = $ayer->format('Y-m-d');
                    break;
                case '7dias':
                    $fechaDesde = $hoy->copy()->subDays(7)->format('Y-m-d');
                    $fechaHasta = $hoy->format('Y-m-d');
                    break;
                case '30dias':
                    $fechaDesde = $hoy->copy()->subDays(30)->format('Y-m-d');
                    $fechaHasta = $hoy->format('Y-m-d');
                    break;
                case '90dias':
                    $fechaDesde = $hoy->copy()->subDays(90)->format('Y-m-d');
                    $fechaHasta = $hoy->format('Y-m-d');
                    break;
                case '180dias':
                    $fechaDesde = $hoy->copy()->subDays(180)->format('Y-m-d');
                    $fechaHasta = $hoy->format('Y-m-d');
                    break;
                case '1año':
                    $fechaDesde = $hoy->copy()->subYear()->format('Y-m-d');
                    $fechaHasta = $hoy->format('Y-m-d');
                    break;
            }
        }

        // Construir query base
        $query = EjecucionGlobal::where('nombre', 'actualizar_primera_oferta');

        // Aplicar filtros de fecha
        if ($fechaDesde) {
            $query->whereDate('inicio', '>=', $fechaDesde);
        }
        if ($fechaHasta) {
            $query->whereDate('inicio', '<=', $fechaHasta);
        }

        // Aplicar filtros de hora
        if ($horaDesde) {
            $query->whereTime('inicio', '>=', $horaDesde);
        }
        if ($horaHasta) {
            $query->whereTime('inicio', '<=', $horaHasta);
        }

        // Aplicar búsqueda
        if ($busqueda) {
            $query->where(function($q) use ($busqueda) {
                $q->whereDate('inicio', 'like', "%$busqueda%")
                  ->orWhereDate('fin', 'like', "%$busqueda%");
            });
        }

        // Calcular estadísticas generales y datos para gráficos (sin paginación)
        $estadisticasQuery = clone $query;
        $totalEjecuciones = $estadisticasQuery->count();
        $totalOfertas = $estadisticasQuery->sum('total');
        $totalActualizadas = $estadisticasQuery->sum('total_guardado');
        $totalErrores = $estadisticasQuery->sum('total_errores');

        // Calcular errores por tienda y URLs resueltas
        $erroresPorTienda = [];
        $urlsResueltasPorTienda = [];
        $estadisticas = $estadisticasQuery->get();
        
        foreach ($estadisticas as $ejecucion) {
            $log = $ejecucion->log ?? [];
            $resultados = $log['resultados'] ?? [];
            
            foreach ($resultados as $resultado) {
                if (isset($resultado['success']) && !$resultado['success']) {
                    $tiendaNombre = $resultado['tienda_nombre'] ?? 'Tienda Desconocida';
                    
                    if (!isset($erroresPorTienda[$tiendaNombre])) {
                        $erroresPorTienda[$tiendaNombre] = 0;
                        $urlsResueltasPorTienda[$tiendaNombre] = 0;
                    }
                    $erroresPorTienda[$tiendaNombre]++;
                    
                    // Verificar si esta URL se resolvió posteriormente
                    $url = $resultado['url'] ?? '';
                    if ($url) {
                        $urlResuelta = $this->verificarUrlResuelta($url, $ejecucion->inicio);
                        if ($urlResuelta) {
                            $urlsResueltasPorTienda[$tiendaNombre]++;
                        }
                    }
                }
            }
        }
        
        // Ordenar por número de errores (descendente)
        arsort($erroresPorTienda);

        // Calcular datos para gráficos por hora
        $datosPorHora = [];
        for ($i = 0; $i < 24; $i++) {
            $datosPorHora[$i] = [
                'total_ofertas' => 0,
                'total_correctas' => 0,
                'total_errores' => 0
            ];
        }

        $ejecucionesPorHora = $estadisticasQuery->get()->groupBy(function($ejecucion) {
            return $ejecucion->inicio->hour;
        });

        foreach ($ejecucionesPorHora as $hora => $ejecucionesHora) {
            $datosPorHora[$hora] = [
                'total_ofertas' => $ejecucionesHora->sum('total'),
                'total_correctas' => $ejecucionesHora->sum('total_guardado'),
                'total_errores' => $ejecucionesHora->sum('total_errores')
            ];
        }

        // Calcular datos para gráficos por día
        $datosPorDia = [];
        
        // Si hay fechas de filtro, generar datos para todos los días del rango
        if ($fechaDesde && $fechaHasta) {
            $fechaInicio = \Carbon\Carbon::parse($fechaDesde);
            $fechaFin = \Carbon\Carbon::parse($fechaHasta);
            
            // Generar array con todos los días del rango
            for ($fecha = $fechaInicio->copy(); $fecha->lte($fechaFin); $fecha->addDay()) {
                $diaKey = $fecha->format('Y-m-d');
                $datosPorDia[$diaKey] = [
                    'total_ofertas' => 0,
                    'total_correctas' => 0,
                    'total_errores' => 0
                ];
            }
        }
        
        // Agrupar ejecuciones por día y sumar los datos
        $ejecucionesPorDia = $estadisticasQuery->get()->groupBy(function($ejecucion) {
            return $ejecucion->inicio->format('Y-m-d');
        });

        foreach ($ejecucionesPorDia as $dia => $ejecucionesDia) {
            $datosPorDia[$dia] = [
                'total_ofertas' => $ejecucionesDia->sum('total'),
                'total_correctas' => $ejecucionesDia->sum('total_guardado'),
                'total_errores' => $ejecucionesDia->sum('total_errores')
            ];
        }

        // Obtener ejecuciones paginadas para la lista
        $ejecuciones = $query->orderByDesc('inicio')->paginate(15)->withQueryString();

        // Ordenar datos por hora (0-23) y convertir a arrays para JSON
        ksort($datosPorHora);
        ksort($datosPorDia);
        
        // Convertir a arrays para que se puedan serializar correctamente en JSON
        $datosPorHoraArray = [];
        foreach ($datosPorHora as $hora => $datos) {
            $datosPorHoraArray[$hora] = $datos;
        }
        
        $datosPorDiaArray = [];
        foreach ($datosPorDia as $dia => $datos) {
            $datosPorDiaArray[$dia] = $datos;
        }

        return view('admin.scraping.historial-actualizar-primera-oferta', compact(
            'ejecuciones', 
            'busqueda', 
            'totalEjecuciones',
            'filtroRapido',
            'fechaDesde',
            'fechaHasta',
            'horaDesde',
            'horaHasta',
            'totalOfertas',
            'totalActualizadas',
            'totalErrores',
            'erroresPorTienda',
            'urlsResueltasPorTienda'
        ))->with([
            'datosPorHora' => $datosPorHoraArray,
            'datosPorDia' => $datosPorDiaArray
        ]);
    }

    /**
     * Obtener detalles de errores por tienda
     */
    public function obtenerErroresPorTienda(Request $request)
    {
        $tienda = $request->input('tienda');
        $filtroRapido = $request->input('filtro_rapido', 'hoy');
        $fechaDesde = $request->input('fecha_desde');
        $fechaHasta = $request->input('fecha_hasta');
        $horaDesde = $request->input('hora_desde');
        $horaHasta = $request->input('hora_hasta');

        // Aplicar filtros de fecha según el filtro rápido
        if ($filtroRapido && $filtroRapido !== 'siempre') {
            $hoy = now();
            
            switch ($filtroRapido) {
                case 'hoy':
                    $fechaDesde = $fechaHasta = $hoy->format('Y-m-d');
                    break;
                case 'ayer':
                    $ayer = $hoy->copy()->subDay();
                    $fechaDesde = $fechaHasta = $ayer->format('Y-m-d');
                    break;
                case '7dias':
                    $fechaDesde = $hoy->copy()->subDays(7)->format('Y-m-d');
                    $fechaHasta = $hoy->format('Y-m-d');
                    break;
                case '30dias':
                    $fechaDesde = $hoy->copy()->subDays(30)->format('Y-m-d');
                    $fechaHasta = $hoy->format('Y-m-d');
                    break;
                case '90dias':
                    $fechaDesde = $hoy->copy()->subDays(90)->format('Y-m-d');
                    $fechaHasta = $hoy->format('Y-m-d');
                    break;
                case '180dias':
                    $fechaDesde = $hoy->copy()->subDays(180)->format('Y-m-d');
                    $fechaHasta = $hoy->format('Y-m-d');
                    break;
                case '1año':
                    $fechaDesde = $hoy->copy()->subYear()->format('Y-m-d');
                    $fechaHasta = $hoy->format('Y-m-d');
                    break;
            }
        }

        // Construir query base
        $query = EjecucionGlobal::where('nombre', 'actualizar_primera_oferta');
        
        // Aplicar filtros de fecha
        if ($fechaDesde) {
            $query->whereDate('inicio', '>=', $fechaDesde);
        }
        if ($fechaHasta) {
            $query->whereDate('inicio', '<=', $fechaHasta);
        }

        // Aplicar filtros de hora
        if ($horaDesde) {
            $query->whereTime('inicio', '>=', $horaDesde);
        }
        if ($horaHasta) {
            $query->whereTime('inicio', '<=', $horaHasta);
        }

        $ejecuciones = $query->orderBy('inicio', 'desc')->get();
        
        $erroresDetallados = [];
        $urlsResueltas = [];
        
        // Solo verificar URLs resueltas si se está filtrando por un día específico
        $esFiltroUnDia = ($fechaDesde && $fechaHasta && $fechaDesde === $fechaHasta);

        foreach ($ejecuciones as $ejecucion) {
            $log = $ejecucion->log ?? [];
            $resultados = $log['resultados'] ?? [];
            
            foreach ($resultados as $resultado) {
                if (isset($resultado['success']) && !$resultado['success'] && 
                    isset($resultado['tienda_nombre']) && $resultado['tienda_nombre'] === $tienda) {
                    
                    $url = $resultado['url'] ?? '';
                    $ofertaId = $resultado['oferta_id'] ?? null;
                    
                    if ($url && $ofertaId) {
                        $erroresDetallados[] = [
                            'ejecucion_id' => $ejecucion->id,
                            'oferta_id' => $ofertaId,
                            'url' => $url,
                            'error' => $resultado['error'] ?? 'Error desconocido',
                            'fecha_error' => $ejecucion->inicio->format('Y-m-d H:i:s'),
                            'precio_anterior' => $resultado['precio_anterior'] ?? null,
                            'variante' => $resultado['variante'] ?? null
                        ];
                        
                        // Solo verificar si esta URL se resolvió posteriormente si es filtro de un día
                        if ($esFiltroUnDia) {
                            $urlResuelta = $this->verificarUrlResuelta($url, $ejecucion->inicio);
                            if ($urlResuelta) {
                                $urlsResueltas[$url] = $urlResuelta;
                            }
                        }
                    }
                }
            }
        }

        return response()->json([
            'success' => true,
            'tienda' => $tienda,
            'errores' => $erroresDetallados,
            'urls_resueltas' => $urlsResueltas,
            'total_errores' => count($erroresDetallados),
            'es_filtro_un_dia' => $esFiltroUnDia,
            'fecha_filtro' => $esFiltroUnDia ? $fechaDesde : null
        ]);
    }

    /**
     * Verificar si una URL se resolvió posteriormente
     */
    private function verificarUrlResuelta($url, $fechaError)
    {
        // Buscar ejecuciones posteriores donde esta URL se procesó exitosamente
        $ejecucionPosterior = EjecucionGlobal::where('nombre', 'actualizar_primera_oferta')
            ->where('inicio', '>', $fechaError)
            ->orderBy('inicio', 'asc')
            ->first();

        if ($ejecucionPosterior) {
            $log = $ejecucionPosterior->log ?? [];
            $resultados = $log['resultados'] ?? [];
            
            foreach ($resultados as $resultado) {
                if (isset($resultado['url']) && $resultado['url'] === $url && 
                    isset($resultado['success']) && $resultado['success']) {
                    return [
                        'fecha_resolucion' => $ejecucionPosterior->inicio->format('Y-m-d H:i:s'),
                        'precio_nuevo' => $resultado['precio_nuevo'] ?? null,
                        'ejecucion_id' => $ejecucionPosterior->id
                    ];
                }
            }
        }

        return null;
    }

    /**
     * Obtener detalles de una ejecución específica
     */
    public function obtenerDetallesEjecucion($id)
    {
        try {
            Log::info('Obteniendo detalles de ejecución ID: ' . $id);
            
            $ejecucion = EjecucionGlobal::where('nombre', 'actualizar_primera_oferta')
                ->findOrFail($id);

            Log::info('Ejecución encontrada: ' . $ejecucion->id);

            return response()->json([
                'success' => true,
                'ejecucion' => $ejecucion
            ]);
        } catch (\Exception $e) {
            Log::error('Error al obtener detalles de ejecución: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Ejecución no encontrada: ' . $e->getMessage()
            ], 404);
        }
    }
}

