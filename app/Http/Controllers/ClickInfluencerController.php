<?php

namespace App\Http\Controllers;

use App\Models\Click;
use App\Models\Oferta;
use App\Models\Producto;
use App\Models\Tienda;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClickInfluencerController extends Controller
{
    // Definir usuarios y campañas en el controlador
    private $usuarios = [
        'influencer1' => [
            'password' => 'password123',
            'campaña' => 'navidad2024',
            'nombre' => 'Influencer Navidad 2024'
        ],
        'influencer2' => [
            'password' => 'password456',
            'campaña' => 'blackfriday2024',
            'nombre' => 'Influencer Black Friday 2024'
        ],
        'influencer3' => [
            'password' => 'password789',
            'campaña' => 'verano2024',
            'nombre' => 'Influencer Verano 2024'
        ],
        // Añadir más usuarios aquí según sea necesario
    ];

    // Usuario master que puede ver todos los influencers
    private $usuarioMaster = [
        'usuario' => 'srtocoque',
        'password' => 'master2024'
    ];

    public function dashboard(Request $request, $usuario, $password)
    {
        // Verificar si es el usuario master
        $esMaster = $this->esUsuarioMaster($usuario, $password);
        
        if ($esMaster) {
            // Si es master, obtener el influencer seleccionado o el primero por defecto
            $influencerSeleccionado = $request->get('influencer', 'influencer1');
            
            if (!isset($this->usuarios[$influencerSeleccionado])) {
                $influencerSeleccionado = 'influencer1';
            }
            
            $campaña = $this->usuarios[$influencerSeleccionado]['campaña'];
            $nombreUsuario = $this->usuarios[$influencerSeleccionado]['nombre'];
            $esAdmin = true;
            $influencersDisponibles = $this->obtenerInfluencersDisponibles();
        } else {
            // Verificar autenticación normal
            if (!$this->autenticarUsuario($usuario, $password)) {
                abort(403, 'Acceso denegado');
            }

            $campaña = $this->usuarios[$usuario]['campaña'];
            $nombreUsuario = $this->usuarios[$usuario]['nombre'];
            $esAdmin = false;
            $influencersDisponibles = null;
        }

        // Obtener parámetros de filtro
        $fechaDesde = $request->get('fecha_desde', Carbon::now()->subDays(7)->format('Y-m-d'));
        $fechaHasta = $request->get('fecha_hasta', Carbon::now()->format('Y-m-d'));
        $horaDesde = $request->get('hora_desde', '00:00');
        $horaHasta = $request->get('hora_hasta', '23:59');
        $busqueda = $request->get('busqueda', '');
        $porPagina = $request->get('por_pagina', 20);
        $filtroRapido = $request->get('filtro_rapido', '7dias');

        // Aplicar filtros rápidos si se especifica
        if ($filtroRapido && $filtroRapido !== 'siempre') {
            list($fechaDesde, $fechaHasta) = $this->aplicarFiltroRapido($filtroRapido);
        }

        // Construir query base para la campaña específica
        $query = Click::where('campaña', $campaña)
            ->whereBetween('created_at', [
                $fechaDesde . ' ' . $horaDesde . ':00',
                $fechaHasta . ' ' . $horaHasta . ':59'
            ]);

        // Aplicar búsqueda si se especifica
        if ($busqueda) {
            $query->whereHas('oferta.producto', function ($q) use ($busqueda) {
                $q->where(function ($subQ) use ($busqueda) {
                    $subQ->where('nombre', 'like', '%' . $busqueda . '%')
                        ->orWhere('marca', 'like', '%' . $busqueda . '%')
                        ->orWhere('talla', 'like', '%' . $busqueda . '%');
                });
            })->orWhereHas('oferta.tienda', function ($q) use ($busqueda) {
                $q->where('nombre', 'like', '%' . $busqueda . '%');
            })->orWhere('campaña', 'like', '%' . $busqueda . '%');
        }

        // Obtener clicks paginados
        $clicks = $query->with(['oferta.producto', 'oferta.tienda'])
            ->orderBy('created_at', 'desc')
            ->paginate($porPagina);

        // Estadísticas para la campaña específica
        $estadisticas = $this->obtenerEstadisticas($campaña, $fechaDesde, $fechaHasta, $horaDesde, $horaHasta, $busqueda);

        // Clicks por IP (solo para la campaña específica)
        $clicksPorIP = $this->obtenerClicksPorIP($campaña, $fechaDesde, $fechaHasta, $horaDesde, $horaHasta, $busqueda);

        return view('admin.clicks.clicksInfluencer', compact(
            'clicks',
            'estadisticas',
            'clicksPorIP',
            'fechaDesde',
            'fechaHasta',
            'horaDesde',
            'horaHasta',
            'busqueda',
            'porPagina',
            'filtroRapido',
            'campaña',
            'nombreUsuario',
            'esAdmin',
            'influencersDisponibles'
        ));
    }

    public function posicionesTienda(Request $request)
    {
        // Verificar que la petición viene de la vista correcta
        $referer = $request->header('referer');
        if (!$referer || !str_contains($referer, 'influencer')) {
            return response()->json(['success' => false, 'message' => 'Acceso no autorizado']);
        }

        $tiendaId = $request->get('tienda_id');
        $fechaDesde = $request->get('fecha_desde');
        $fechaHasta = $request->get('fecha_hasta');
        $busqueda = $request->get('busqueda', '');
        $horaDesde = $request->get('hora_desde', '00:00');
        $horaHasta = $request->get('hora_hasta', '23:59');

        // Obtener la campaña del usuario autenticado (esto es un ejemplo, deberías obtenerlo de la sesión)
        $campaña = $request->get('campaña');

        if (!$campaña) {
            return response()->json(['success' => false, 'message' => 'Campaña no especificada']);
        }

        $query = Click::where('campaña', $campaña)
            ->whereHas('oferta', function ($q) use ($tiendaId) {
                $q->where('tienda_id', $tiendaId);
            })
            ->whereBetween('created_at', [
                $fechaDesde . ' ' . $horaDesde . ':00',
                $fechaHasta . ' ' . $horaHasta . ':59'
            ]);

        if ($busqueda) {
            $query->whereHas('oferta.producto', function ($q) use ($busqueda) {
                $q->where(function ($subQ) use ($busqueda) {
                    $subQ->where('nombre', 'like', '%' . $busqueda . '%')
                        ->orWhere('marca', 'like', '%' . $busqueda . '%')
                        ->orWhere('talla', 'like', '%' . $busqueda . '%');
                });
            });
        }

        $posiciones = $query->select('posicion', DB::raw('count(*) as total'))
            ->whereNotNull('posicion')
            ->groupBy('posicion')
            ->orderBy('posicion')
            ->get();

        $totalClicks = $query->count();

        return response()->json([
            'success' => true,
            'posiciones' => $posiciones,
            'totalClicks' => $totalClicks
        ]);
    }

    private function autenticarUsuario($usuario, $password)
    {
        return isset($this->usuarios[$usuario]) && 
               $this->usuarios[$usuario]['password'] === $password;
    }

    private function aplicarFiltroRapido($tipo)
    {
        $hoy = Carbon::now();
        
        switch($tipo) {
            case 'hoy':
                return [$hoy->format('Y-m-d'), $hoy->format('Y-m-d')];
            case 'ayer':
                $ayer = $hoy->copy()->subDay();
                return [$ayer->format('Y-m-d'), $ayer->format('Y-m-d')];
            case '7dias':
                $hace7Dias = $hoy->copy()->subDays(7);
                return [$hace7Dias->format('Y-m-d'), $hoy->format('Y-m-d')];
            case '30dias':
                $hace30Dias = $hoy->copy()->subDays(30);
                return [$hace30Dias->format('Y-m-d'), $hoy->format('Y-m-d')];
            case '90dias':
                $hace90Dias = $hoy->copy()->subDays(90);
                return [$hace90Dias->format('Y-m-d'), $hoy->format('Y-m-d')];
            case '180dias':
                $hace180Dias = $hoy->copy()->subDays(180);
                return [$hace180Dias->format('Y-m-d'), $hoy->format('Y-m-d')];
            case '1año':
                $hace1Año = $hoy->copy()->subYear();
                return [$hace1Año->format('Y-m-d'), $hoy->format('Y-m-d')];
            default:
                return [Carbon::now()->subDays(7)->format('Y-m-d'), $hoy->format('Y-m-d')];
        }
    }

    private function obtenerEstadisticas($campaña, $fechaDesde, $fechaHasta, $horaDesde, $horaHasta, $busqueda)
    {
        // Query base para filtros
        $baseQuery = Click::where('campaña', $campaña)
            ->whereBetween('clicks.created_at', [
                $fechaDesde . ' ' . $horaDesde . ':00',
                $fechaHasta . ' ' . $horaHasta . ':59'
            ]);

        if ($busqueda) {
            $baseQuery->whereHas('oferta.producto', function ($q) use ($busqueda) {
                $q->where(function ($subQ) use ($busqueda) {
                    $subQ->where('nombre', 'like', '%' . $busqueda . '%')
                        ->orWhere('marca', 'like', '%' . $busqueda . '%')
                        ->orWhere('talla', 'like', '%' . $busqueda . '%');
                });
            })->orWhereHas('oferta.tienda', function ($q) use ($busqueda) {
                $q->where('nombre', 'like', '%' . $busqueda . '%');
            })->orWhere('campaña', 'like', '%' . $busqueda . '%');
        }

        $totalClicks = $baseQuery->count();

        // Clicks por tienda
        $clicksPorTienda = Click::where('campaña', $campaña)
            ->whereBetween('clicks.created_at', [
                $fechaDesde . ' ' . $horaDesde . ':00',
                $fechaHasta . ' ' . $horaHasta . ':59'
            ])
            ->select(
                'tiendas.nombre',
                'tiendas.id',
                DB::raw('count(*) as total'),
                DB::raw('min(clicks.posicion) as posicion_min'),
                DB::raw('max(clicks.posicion) as posicion_max')
            )
            ->join('ofertas_producto', 'clicks.oferta_id', '=', 'ofertas_producto.id')
            ->join('tiendas', 'ofertas_producto.tienda_id', '=', 'tiendas.id')
            ->groupBy('tiendas.id', 'tiendas.nombre')
            ->orderBy('total', 'desc')
            ->get();

        // Clicks por producto
        $clicksPorProducto = Click::where('campaña', $campaña)
            ->whereBetween('clicks.created_at', [
                $fechaDesde . ' ' . $horaDesde . ':00',
                $fechaHasta . ' ' . $horaHasta . ':59'
            ])
            ->select(
                'productos.nombre',
                'productos.marca',
                'productos.talla',
                'productos.slug',
                'productos.id',
                DB::raw('count(*) as total'),
                DB::raw('min(clicks.posicion) as posicion_min'),
                DB::raw('max(clicks.posicion) as posicion_max')
            )
            ->join('ofertas_producto', 'clicks.oferta_id', '=', 'ofertas_producto.id')
            ->join('productos', 'ofertas_producto.producto_id', '=', 'productos.id')
            ->groupBy('productos.id', 'productos.nombre', 'productos.marca', 'productos.talla', 'productos.slug')
            ->orderBy('total', 'desc')
            ->limit(10)
            ->get();

        // Clicks por hora
        $clicksPorHora = Click::where('campaña', $campaña)
            ->whereBetween('clicks.created_at', [
                $fechaDesde . ' ' . $horaDesde . ':00',
                $fechaHasta . ' ' . $horaHasta . ':59'
            ])
            ->select(
                DB::raw('HOUR(clicks.created_at) as hora'),
                DB::raw('count(*) as total')
            )
            ->groupBy('hora')
            ->orderBy('hora')
            ->get();

        // Clicks por día
        $clicksPorDia = Click::where('campaña', $campaña)
            ->whereBetween('clicks.created_at', [
                $fechaDesde . ' ' . $horaDesde . ':00',
                $fechaHasta . ' ' . $horaHasta . ':59'
            ])
            ->select(
                DB::raw('DATE(clicks.created_at) as fecha'),
                DB::raw('count(*) as total')
            )
            ->groupBy('fecha')
            ->orderBy('fecha')
            ->get();

        // Total de productos únicos
        $totalProductos = Click::where('campaña', $campaña)
            ->whereBetween('clicks.created_at', [
                $fechaDesde . ' ' . $horaDesde . ':00',
                $fechaHasta . ' ' . $horaHasta . ':59'
            ])
            ->distinct('oferta_id')->count('oferta_id');

        // Total de tiendas únicas
        $totalTiendas = Click::where('campaña', $campaña)
            ->whereBetween('clicks.created_at', [
                $fechaDesde . ' ' . $horaDesde . ':00',
                $fechaHasta . ' ' . $horaHasta . ':59'
            ])
            ->join('ofertas_producto', 'clicks.oferta_id', '=', 'ofertas_producto.id')
            ->distinct('ofertas_producto.tienda_id')
            ->count('ofertas_producto.tienda_id');

        // Total de ofertas únicas
        $totalOfertas = Click::where('campaña', $campaña)
            ->whereBetween('clicks.created_at', [
                $fechaDesde . ' ' . $horaDesde . ':00',
                $fechaHasta . ' ' . $horaHasta . ':59'
            ])
            ->distinct('oferta_id')->count('oferta_id');

        return [
            'totalClicks' => $totalClicks,
            'totalProductos' => $totalProductos,
            'totalTiendas' => $totalTiendas,
            'totalOfertas' => $totalOfertas,
            'clicksPorTienda' => $clicksPorTienda,
            'clicksPorProducto' => $clicksPorProducto,
            'clicksPorHora' => $clicksPorHora,
            'clicksPorDia' => $clicksPorDia
        ];
    }

    private function obtenerClicksPorIP($campaña, $fechaDesde, $fechaHasta, $horaDesde, $horaHasta, $busqueda)
    {
        $query = Click::where('campaña', $campaña)
            ->whereBetween('clicks.created_at', [
                $fechaDesde . ' ' . $horaDesde . ':00',
                $fechaHasta . ' ' . $horaHasta . ':59'
            ])
            ->whereNotNull('ip');

        if ($busqueda) {
            $query->whereHas('oferta.producto', function ($q) use ($busqueda) {
                $q->where(function ($subQ) use ($busqueda) {
                    $subQ->where('nombre', 'like', '%' . $busqueda . '%')
                        ->orWhere('marca', 'like', '%' . $busqueda . '%')
                        ->orWhere('talla', 'like', '%' . $busqueda . '%');
                });
            })->orWhereHas('oferta.tienda', function ($q) use ($busqueda) {
                $q->where('nombre', 'like', '%' . $busqueda . '%');
            })->orWhere('campaña', 'like', '%' . $busqueda . '%');
        }

        return $query->select(
                'ip',
                DB::raw('count(*) as total'),
                DB::raw('min(clicks.created_at) as primer_click'),
                DB::raw('max(clicks.created_at) as ultimo_click'),
                DB::raw('min(clicks.posicion) as posicion_min'),
                DB::raw('max(clicks.posicion) as posicion_max')
            )
            ->groupBy('ip')
            ->orderBy('total', 'desc')
            ->paginate(20);
    }

    /**
     * Verificar si es el usuario master
     */
    private function esUsuarioMaster($usuario, $password)
    {
        return $usuario === $this->usuarioMaster['usuario'] && 
               $password === $this->usuarioMaster['password'];
    }

    /**
     * Obtener lista de influencers disponibles para el selector
     */
    private function obtenerInfluencersDisponibles()
    {
        $influencers = [];
        
        foreach ($this->usuarios as $usuario => $datos) {
            $influencers[] = (object) [
                'usuario' => $usuario,
                'nombre' => $datos['nombre'],
                'campaña' => $datos['campaña']
            ];
        }
        
        return collect($influencers);
    }
}
