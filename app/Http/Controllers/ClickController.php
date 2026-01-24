<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Click;
use App\Models\OfertaProducto;
use App\Models\Producto;
use Illuminate\Support\Facades\DB;
use App\Models\Tienda;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use App\Jobs\GuardarClickJob;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class ClickController extends Controller
{

    //LISTAR TODOS LOS CLICKS
    public function index(Request $request)
    {
        $query = Click::with(['oferta.producto', 'oferta.tienda']);

        // Filtros opcionales (por campaña o producto)
        if ($request->filled('campana')) {
            $query->where('campaña', $request->campana);
        }

        if ($request->filled('producto')) {
            $query->whereHas('oferta', fn($q) => $q->where('producto_id', $request->producto));
        }

        $clics = $query->latest()->paginate(30);

        return view('admin.clics.index', compact('clics'));
    }

    // NUEVO MÉTODO PARA EL DASHBOARD DE CLICKS
    public function dashboard(Request $request)
    {
        // Obtener filtro rápido
        $filtroRapido = $request->input('filtro_rapido', 'hoy');
        
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
                $fechaDesde = '1900-01-01'; // Fecha muy antigua para incluir todo
                $fechaHasta = $hoy->toDateString();
                break;
            default:
                // Si no hay filtro rápido o es inválido, usar los valores por defecto o los enviados
                $fechaDesde = $request->input('fecha_desde', $hoy->toDateString());
                $fechaHasta = $request->input('fecha_hasta', $hoy->toDateString());
                break;
        }
        
        $busqueda = $request->input('busqueda', '');
        $porPagina = $request->input('por_pagina', 20);
        
        // Filtros de hora
        $horaDesde = $request->input('hora_desde', '');
        $horaHasta = $request->input('hora_hasta', '');

        // Query base con relaciones
        $query = Click::with(['oferta.producto', 'oferta.tienda'])
            ->whereBetween('created_at', [$fechaDesde . ' 00:00:00', $fechaHasta . ' 23:59:59']);
        
        // Aplicar filtro de hora si se especifica
        if (!empty($horaDesde) && !empty($horaHasta)) {
            $query->whereRaw('TIME(created_at) BETWEEN ? AND ?', [$horaDesde, $horaHasta]);
        }

        // Aplicar búsqueda si existe
        if (!empty($busqueda)) {
            $terminos = array_map('trim', explode(',', $busqueda));
            
            $query->where(function($q) use ($terminos) {
                foreach ($terminos as $termino) {
                    $q->where(function($subQ) use ($termino) {
                        $subQ->whereHas('oferta.producto', function($productoQ) use ($termino) {
                            $productoQ->where(function($p) use ($termino) {
                                $p->where('nombre', 'LIKE', '%' . $termino . '%')
                                  ->orWhere('marca', 'LIKE', '%' . $termino . '%')
                                  ->orWhere('talla', 'LIKE', '%' . $termino . '%')
                                  ->orWhereRaw('CONCAT(nombre, " ", marca, " ", talla) LIKE ?', ['%' . $termino . '%'])
                                  ->orWhereRaw('CONCAT(marca, " - ", talla) LIKE ?', ['%' . $termino . '%'])
                                  ->orWhereRaw('CONCAT(nombre, " ", talla) LIKE ?', ['%' . $termino . '%']);
                            });
                        })
                        ->orWhereHas('oferta.tienda', function($tiendaQ) use ($termino) {
                            $tiendaQ->where('nombre', 'LIKE', '%' . $termino . '%');
                        })
                        ->orWhere('campaña', 'LIKE', '%' . $termino . '%')
                        ->orWhere('ip', 'LIKE', '%' . $termino . '%');
                    });
                }
            });
        }

        // Obtener clicks paginados
        $clicks = $query->orderBy('created_at', 'desc')->paginate($porPagina);

        // Estadísticas para gráficos
        $estadisticas = $this->obtenerEstadisticas($fechaDesde, $fechaHasta, $busqueda, $horaDesde, $horaHasta);

        // Obtener clicks por IP paginados
        $clicksPorIP = $this->obtenerClicksPorIPPaginados($fechaDesde, $fechaHasta, $busqueda, $horaDesde, $horaHasta, $porPagina);

        // Detectar IPs sospechosas
        $ipsSospechosas = $this->detectarIPsSospechosas($estadisticas['clicksPorIP']);

        // Detectar IPs nuevas (primer registro en el rango de tiempo seleccionado)
        $ipsNuevas = $this->detectarIPsNuevas($fechaDesde, $fechaHasta, $busqueda, $horaDesde, $horaHasta);

        // Obtener datos para el mapa
        $datosMapa = $this->obtenerDatosMapa($fechaDesde, $fechaHasta, $busqueda, $horaDesde, $horaHasta);

        return view('admin.clicks.dashboard', compact(
            'clicks',
            'clicksPorIP',
            'ipsSospechosas',
            'ipsNuevas',
            'fechaDesde',
            'fechaHasta',
            'busqueda',
            'porPagina',
            'horaDesde',
            'horaHasta',
            'estadisticas',
            'filtroRapido',
            'datosMapa'
        ));
    }

    // MÉTODO PRIVADO PARA OBTENER ESTADÍSTICAS
    private function obtenerEstadisticas($fechaDesde, $fechaHasta, $busqueda, $horaDesde = '', $horaHasta = '')
    {
        // Query base para estadísticas
        $queryBase = Click::with(['oferta.producto', 'oferta.tienda'])
            ->whereBetween('created_at', [$fechaDesde . ' 00:00:00', $fechaHasta . ' 23:59:59']);

        // Aplicar búsqueda si existe
        if (!empty($busqueda)) {
            $terminos = array_map('trim', explode(',', $busqueda));
            
            $queryBase->where(function($q) use ($terminos) {
                foreach ($terminos as $termino) {
                    $q->where(function($subQ) use ($termino) {
                        $subQ->whereHas('oferta.producto', function($productoQ) use ($termino) {
                            $productoQ->where(function($p) use ($termino) {
                                $p->where('nombre', 'LIKE', '%' . $termino . '%')
                                  ->orWhere('marca', 'LIKE', '%' . $termino . '%')
                                  ->orWhere('talla', 'LIKE', '%' . $termino . '%')
                                  ->orWhereRaw("CONCAT(nombre, ' ', marca, ' ', talla) LIKE ?", ['%' . $termino . '%'])
                                  ->orWhereRaw("CONCAT(marca, ' - ', talla) LIKE ?", ['%' . $termino . '%'])
                                  ->orWhereRaw("CONCAT(nombre, ' ', talla) LIKE ?", ['%' . $termino . '%']);
                            });
                        })
                        ->orWhereHas('oferta.tienda', function($tiendaQ) use ($termino) {
                            $tiendaQ->where('nombre', 'LIKE', '%' . $termino . '%');
                        })
                        ->orWhere('campaña', 'LIKE', '%' . $termino . '%')
                        ->orWhere('ip', 'LIKE', '%' . $termino . '%');
                    });
                }
            });
        }
        
        // Aplicar filtro de hora si se especifica
        if (!empty($horaDesde) && !empty($horaHasta)) {
            $queryBase->whereRaw('TIME(created_at) BETWEEN ? AND ?', [$horaDesde, $horaHasta]);
        }

        // 1. Clicks por hora del día
        $clicksPorHora = DB::table('clicks')
            ->join('ofertas_producto', 'clicks.oferta_id', '=', 'ofertas_producto.id')
            ->join('productos', 'ofertas_producto.producto_id', '=', 'productos.id')
            ->join('tiendas', 'ofertas_producto.tienda_id', '=', 'tiendas.id')
            ->whereBetween('clicks.created_at', [$fechaDesde . ' 00:00:00', $fechaHasta . ' 23:59:59'])
            ->when(!empty($busqueda), function($q) use ($busqueda) {
                $terminos = array_map('trim', explode(',', $busqueda));
                foreach ($terminos as $termino) {
                    $q->where(function($subQ) use ($termino) {
                        $subQ->where('productos.nombre', 'LIKE', '%' . $termino . '%')
                             ->orWhere('productos.marca', 'LIKE', '%' . $termino . '%')
                             ->orWhere('productos.talla', 'LIKE', '%' . $termino . '%')
                             ->orWhereRaw("CONCAT(productos.nombre, ' ', productos.marca, ' ', productos.talla) LIKE ?", ['%' . $termino . '%'])
                             ->orWhereRaw("CONCAT(productos.marca, ' - ', productos.talla) LIKE ?", ['%' . $termino . '%'])
                             ->orWhereRaw("CONCAT(productos.nombre, ' ', productos.talla) LIKE ?", ['%' . $termino . '%'])
                             ->orWhere('tiendas.nombre', 'LIKE', '%' . $termino . '%')
                             ->orWhere('clicks.campaña', 'LIKE', '%' . $termino . '%')
                             ->orWhere('clicks.ip', 'LIKE', '%' . $termino . '%');
                    });
                }
            })
            ->when(!empty($horaDesde) && !empty($horaHasta), function($q) use ($horaDesde, $horaHasta) {
                $q->whereRaw('TIME(clicks.created_at) BETWEEN ? AND ?', [$horaDesde, $horaHasta]);
            })
            ->selectRaw('HOUR(clicks.created_at) as hora, COUNT(*) as total')
            ->groupBy('hora')
            ->orderBy('hora')
            ->get();

        // 2. Clicks por día (si hay múltiples días)
        $clicksPorDia = [];
        if ($fechaDesde !== $fechaHasta) {
            $clicksPorDia = DB::table('clicks')
                ->join('ofertas_producto', 'clicks.oferta_id', '=', 'ofertas_producto.id')
                ->join('productos', 'ofertas_producto.producto_id', '=', 'productos.id')
                ->join('tiendas', 'ofertas_producto.tienda_id', '=', 'tiendas.id')
                ->whereBetween('clicks.created_at', [$fechaDesde . ' 00:00:00', $fechaHasta . ' 23:59:59'])
                ->when(!empty($busqueda), function($q) use ($busqueda) {
                    $terminos = array_map('trim', explode(',', $busqueda));
                    foreach ($terminos as $termino) {
                        $q->where(function($subQ) use ($termino) {
                            $subQ->where('productos.nombre', 'LIKE', '%' . $termino . '%')
                                 ->orWhere('productos.marca', 'LIKE', '%' . $termino . '%')
                                 ->orWhere('productos.talla', 'LIKE', '%' . $termino . '%')
                                 ->orWhere('tiendas.nombre', 'LIKE', '%' . $termino . '%')
                                 ->orWhere('clicks.campaña', 'LIKE', '%' . $termino . '%');
                        });
                    }
                })
                ->when(!empty($horaDesde) && !empty($horaHasta), function($q) use ($horaDesde, $horaHasta) {
                    $q->whereRaw('TIME(clicks.created_at) BETWEEN ? AND ?', [$horaDesde, $horaHasta]);
                })
                ->selectRaw('DATE(clicks.created_at) as fecha, COUNT(*) as total')
                ->groupBy('fecha')
                ->orderBy('fecha')
                ->get();
        }

                    // 3. Clicks por tienda
            $clicksPorTienda = DB::table('clicks')
                ->join('ofertas_producto', 'clicks.oferta_id', '=', 'ofertas_producto.id')
                ->join('productos', 'ofertas_producto.producto_id', '=', 'productos.id')
                ->join('tiendas', 'ofertas_producto.tienda_id', '=', 'tiendas.id')
                ->whereBetween('clicks.created_at', [$fechaDesde . ' 00:00:00', $fechaHasta . ' 23:59:59'])
                ->when(!empty($busqueda), function($q) use ($busqueda) {
                    $terminos = array_map('trim', explode(',', $busqueda));
                    foreach ($terminos as $termino) {
                        $q->where(function($subQ) use ($termino) {
                            $subQ->where('productos.nombre', 'LIKE', '%' . $termino . '%')
                                 ->orWhere('productos.marca', 'LIKE', '%' . $termino . '%')
                                 ->orWhere('productos.talla', 'LIKE', '%' . $termino . '%')
                                 ->orWhereRaw("CONCAT(productos.nombre, ' ', productos.marca, ' ', productos.talla) LIKE ?", ['%' . $termino . '%'])
                                 ->orWhereRaw("CONCAT(productos.marca, ' - ', productos.talla) LIKE ?", ['%' . $termino . '%'])
                                 ->orWhereRaw("CONCAT(productos.nombre, ' ', productos.talla) LIKE ?", ['%' . $termino . '%'])
                                 ->orWhere('tiendas.nombre', 'LIKE', '%' . $termino . '%')
                                 ->orWhere('clicks.campaña', 'LIKE', '%' . $termino . '%')
                                 ->orWhere('clicks.ip', 'LIKE', '%' . $termino . '%');
                        });
                    }
                })
                ->when(!empty($horaDesde) && !empty($horaHasta), function($q) use ($horaDesde, $horaHasta) {
                    $q->whereRaw('TIME(clicks.created_at) BETWEEN ? AND ?', [$horaDesde, $horaHasta]);
                })
                ->selectRaw('tiendas.id, tiendas.nombre, COUNT(*) as total, MIN(clicks.posicion) as posicion_min, MAX(clicks.posicion) as posicion_max')
                ->groupBy('tiendas.id', 'tiendas.nombre')
                ->orderByDesc('total')
                ->get();

            // 4. Clicks por producto
            $clicksPorProducto = DB::table('clicks')
                ->join('ofertas_producto', 'clicks.oferta_id', '=', 'ofertas_producto.id')
                ->join('productos', 'ofertas_producto.producto_id', '=', 'productos.id')
                ->join('tiendas', 'ofertas_producto.tienda_id', '=', 'tiendas.id')
                ->whereBetween('clicks.created_at', [$fechaDesde . ' 00:00:00', $fechaHasta . ' 23:59:59'])
                ->when(!empty($busqueda), function($q) use ($busqueda) {
                    $terminos = array_map('trim', explode(',', $busqueda));
                    foreach ($terminos as $termino) {
                        $q->where(function($subQ) use ($termino) {
                            $subQ->where('productos.nombre', 'LIKE', '%' . $termino . '%')
                                 ->orWhere('productos.marca', 'LIKE', '%' . $termino . '%')
                                 ->orWhere('productos.talla', 'LIKE', '%' . $termino . '%')
                                 ->orWhereRaw("CONCAT(productos.nombre, ' ', productos.marca, ' ', productos.talla) LIKE ?", ['%' . $termino . '%'])
                                 ->orWhereRaw("CONCAT(productos.marca, ' - ', productos.talla) LIKE ?", ['%' . $termino . '%'])
                                 ->orWhereRaw("CONCAT(productos.nombre, ' ', productos.talla) LIKE ?", ['%' . $termino . '%'])
                                 ->orWhere('tiendas.nombre', 'LIKE', '%' . $termino . '%')
                                 ->orWhere('clicks.campaña', 'LIKE', '%' . $termino . '%')
                                 ->orWhere('clicks.ip', 'LIKE', '%' . $termino . '%');
                        });
                    }
                })
                ->when(!empty($horaDesde) && !empty($horaHasta), function($q) use ($horaDesde, $horaHasta) {
                    $q->whereRaw('TIME(clicks.created_at) BETWEEN ? AND ?', [$horaDesde, $horaHasta]);
                })
                ->selectRaw('productos.id, productos.nombre, productos.marca, productos.talla, COUNT(*) as total, MIN(clicks.posicion) as posicion_min, MAX(clicks.posicion) as posicion_max')
                ->groupBy('productos.id', 'productos.nombre', 'productos.marca', 'productos.talla')
                ->orderByDesc('total')
                ->get();

            // 5. Clicks por IP
            $clicksPorIP = DB::table('clicks')
                ->join('ofertas_producto', 'clicks.oferta_id', '=', 'ofertas_producto.id')
                ->join('productos', 'ofertas_producto.producto_id', '=', 'productos.id')
                ->join('tiendas', 'ofertas_producto.tienda_id', '=', 'tiendas.id')
                ->whereBetween('clicks.created_at', [$fechaDesde . ' 00:00:00', $fechaHasta . ' 23:59:59'])
                ->when(!empty($busqueda), function($q) use ($busqueda) {
                    $terminos = array_map('trim', explode(',', $busqueda));
                    foreach ($terminos as $termino) {
                        $q->where(function($subQ) use ($termino) {
                            $subQ->where('clicks.ip', 'LIKE', '%' . $termino . '%')
                                 ->orWhere('clicks.campaña', 'LIKE', '%' . $termino . '%')
                                 ->orWhere('productos.nombre', 'LIKE', '%' . $termino . '%')
                                 ->orWhere('productos.marca', 'LIKE', '%' . $termino . '%')
                                 ->orWhere('productos.talla', 'LIKE', '%' . $termino . '%')
                                 ->orWhere('tiendas.nombre', 'LIKE', '%' . $termino . '%');
                        });
                    }
                })
                ->when(!empty($horaDesde) && !empty($horaHasta), function($q) use ($horaDesde, $horaHasta) {
                    $q->whereRaw('TIME(clicks.created_at) BETWEEN ? AND ?', [$horaDesde, $horaHasta]);
                })
                ->selectRaw('clicks.ip, COUNT(*) as total, MIN(clicks.posicion) as posicion_min, MAX(clicks.posicion) as posicion_max, MIN(clicks.created_at) as primer_click, MAX(clicks.created_at) as ultimo_click')
                ->groupBy('clicks.ip')
                ->orderByDesc('total')
                ->get();

        // Estadísticas adicionales
        $totalProductos = DB::table('clicks')
            ->join('ofertas_producto', 'clicks.oferta_id', '=', 'ofertas_producto.id')
            ->join('productos', 'ofertas_producto.producto_id', '=', 'productos.id')
            ->whereBetween('clicks.created_at', [$fechaDesde . ' 00:00:00', $fechaHasta . ' 23:59:59'])
            ->when(!empty($busqueda), function($q) use ($busqueda) {
                $terminos = array_map('trim', explode(',', $busqueda));
                foreach ($terminos as $termino) {
                    $q->where(function($subQ) use ($termino) {
                        $subQ->where('productos.nombre', 'LIKE', '%' . $termino . '%')
                             ->orWhere('productos.marca', 'LIKE', '%' . $termino . '%')
                             ->orWhere('productos.talla', 'LIKE', '%' . $termino . '%')
                             ->orWhereRaw("CONCAT(productos.nombre, ' ', productos.marca, ' ', productos.talla) LIKE ?", ['%' . $termino . '%'])
                             ->orWhereRaw("CONCAT(productos.marca, ' - ', productos.talla) LIKE ?", ['%' . $termino . '%'])
                             ->orWhereRaw("CONCAT(productos.nombre, ' ', productos.talla) LIKE ?", ['%' . $termino . '%']);
                    });
                }
            })
            ->when(!empty($horaDesde) && !empty($horaHasta), function($q) use ($horaDesde, $horaHasta) {
                $q->whereRaw('TIME(clicks.created_at) BETWEEN ? AND ?', [$horaDesde, $horaHasta]);
            })
            ->distinct('productos.id')
            ->count('productos.id');

        $totalTiendas = DB::table('clicks')
            ->join('ofertas_producto', 'clicks.oferta_id', '=', 'ofertas_producto.id')
            ->join('tiendas', 'ofertas_producto.tienda_id', '=', 'tiendas.id')
            ->whereBetween('clicks.created_at', [$fechaDesde . ' 00:00:00', $fechaHasta . ' 23:59:59'])
            ->when(!empty($busqueda), function($q) use ($busqueda) {
                $terminos = array_map('trim', explode(',', $busqueda));
                foreach ($terminos as $termino) {
                    $q->where(function($subQ) use ($termino) {
                        $subQ->where('tiendas.nombre', 'LIKE', '%' . $termino . '%')
                             ->orWhere('clicks.campaña', 'LIKE', '%' . $termino . '%')
                             ->orWhere('clicks.ip', 'LIKE', '%' . $termino . '%');
                    });
                }
            })
            ->when(!empty($horaDesde) && !empty($horaHasta), function($q) use ($horaDesde, $horaHasta) {
                $q->whereRaw('TIME(clicks.created_at) BETWEEN ? AND ?', [$horaDesde, $horaHasta]);
            })
            ->distinct('tiendas.id')
            ->count('tiendas.id');

        $totalOfertas = DB::table('clicks')
            ->join('ofertas_producto', 'clicks.oferta_id', '=', 'ofertas_producto.id')
            ->whereBetween('clicks.created_at', [$fechaDesde . ' 00:00:00', $fechaHasta . ' 23:59:59'])
            ->when(!empty($busqueda), function($q) use ($busqueda) {
                $terminos = array_map('trim', explode(',', $busqueda));
                foreach ($terminos as $termino) {
                    $q->where(function($subQ) use ($termino) {
                        $subQ->where('clicks.campaña', 'LIKE', '%' . $termino . '%')
                             ->orWhere('clicks.ip', 'LIKE', '%' . $termino . '%');
                    });
                }
            })
            ->when(!empty($horaDesde) && !empty($horaHasta), function($q) use ($horaDesde, $horaHasta) {
                $q->whereRaw('TIME(clicks.created_at) BETWEEN ? AND ?', [$horaDesde, $horaHasta]);
            })
            ->distinct('ofertas_producto.id')
            ->count('ofertas_producto.id');

        return [
            'clicksPorHora' => $clicksPorHora,
            'clicksPorDia' => $clicksPorDia,
            'clicksPorTienda' => $clicksPorTienda,
            'clicksPorProducto' => $clicksPorProducto,
            'clicksPorIP' => $clicksPorIP,
            'totalClicks' => $queryBase->count(),
            'totalProductos' => $totalProductos,
            'totalTiendas' => $totalTiendas,
            'totalOfertas' => $totalOfertas
        ];
    }

    // MÉTODO PARA OBTENER CLICKS POR IP PAGINADOS
    private function obtenerClicksPorIPPaginados($fechaDesde, $fechaHasta, $busqueda, $horaDesde, $horaHasta, $porPagina)
    {
        $query = DB::table('clicks')
            ->join('ofertas_producto', 'clicks.oferta_id', '=', 'ofertas_producto.id')
            ->join('productos', 'ofertas_producto.producto_id', '=', 'productos.id')
            ->join('tiendas', 'ofertas_producto.tienda_id', '=', 'tiendas.id')
            ->whereBetween('clicks.created_at', [$fechaDesde . ' 00:00:00', $fechaHasta . ' 23:59:59'])
            ->when(!empty($busqueda), function($q) use ($busqueda) {
                $terminos = array_map('trim', explode(',', $busqueda));
                foreach ($terminos as $termino) {
                    $q->where(function($subQ) use ($termino) {
                        $subQ->where('clicks.ip', 'LIKE', '%' . $termino . '%')
                             ->orWhere('clicks.campaña', 'LIKE', '%' . $termino . '%')
                             ->orWhere('productos.nombre', 'LIKE', '%' . $termino . '%')
                             ->orWhere('productos.marca', 'LIKE', '%' . $termino . '%')
                             ->orWhere('productos.talla', 'LIKE', '%' . $termino . '%')
                             ->orWhereRaw("CONCAT(productos.nombre, ' ', productos.marca, ' ', productos.talla) LIKE ?", ['%' . $termino . '%'])
                             ->orWhereRaw("CONCAT(productos.marca, ' - ', productos.talla) LIKE ?", ['%' . $termino . '%'])
                             ->orWhereRaw("CONCAT(productos.nombre, ' ', productos.talla) LIKE ?", ['%' . $termino . '%'])
                             ->orWhere('tiendas.nombre', 'LIKE', '%' . $termino . '%');
                    });
                }
            })
            ->when(!empty($horaDesde) && !empty($horaHasta), function($q) use ($horaDesde, $horaHasta) {
                $q->whereRaw('TIME(clicks.created_at) BETWEEN ? AND ?', [$horaDesde, $horaHasta]);
            })
            ->selectRaw('clicks.ip, COUNT(*) as total, MIN(clicks.posicion) as posicion_min, MAX(clicks.posicion) as posicion_max, MIN(clicks.created_at) as primer_click, MAX(clicks.created_at) as ultimo_click')
            ->groupBy('clicks.ip')
            ->orderByDesc('total');

        return $query->paginate($porPagina);
    }

    // MÉTODO PARA DETECTAR IPS SOSPECHOSAS
    private function detectarIPsSospechosas($clicksPorIP)
    {
        $ipsSospechosas = [];
        
        foreach ($clicksPorIP as $ip) {
            $esSospechosa = $ip->total > 50; // Más de 50 clicks
            $esMuySospechosa = $ip->total > 100; // Más de 100 clicks
            
            if ($esSospechosa) {
                $ipsSospechosas[] = [
                    'ip' => $ip->ip,
                    'total' => $ip->total,
                    'nivel' => $esMuySospechosa ? 'muy_sospechosa' : 'sospechosa'
                ];
            }
        }
        
        return $ipsSospechosas;
    }

    // MÉTODO PARA DETECTAR IPS NUEVAS
    private function detectarIPsNuevas($fechaDesde, $fechaHasta, $busqueda, $horaDesde = '', $horaHasta = '')
    {
        // Obtener todas las IPs que aparecen en el rango de tiempo seleccionado
        $queryIPsEnRango = Click::whereBetween('created_at', [$fechaDesde . ' 00:00:00', $fechaHasta . ' 23:59:59'])
            ->whereNotNull('ip')
            ->select('ip')
            ->distinct();

        // Aplicar filtro de hora si se especifica
        if (!empty($horaDesde) && !empty($horaHasta)) {
            $queryIPsEnRango->whereRaw('TIME(created_at) BETWEEN ? AND ?', [$horaDesde, $horaHasta]);
        }

        // Aplicar búsqueda si existe (solo si la búsqueda incluye IPs)
        if (!empty($busqueda)) {
            $terminos = array_map('trim', explode(',', $busqueda));
            $queryIPsEnRango->where(function($q) use ($terminos) {
                foreach ($terminos as $termino) {
                    $q->orWhere('ip', 'LIKE', '%' . $termino . '%');
                }
            });
        }

        $ipsEnRango = $queryIPsEnRango->pluck('ip')->toArray();

        // Para cada IP en el rango, verificar si es nueva (primer registro en toda la base de datos)
        $ipsNuevas = [];
        $totalIPsNuevas = 0;

        foreach ($ipsEnRango as $ip) {
            // Buscar el primer registro de esta IP en toda la base de datos
            $primerRegistro = Click::where('ip', $ip)
                ->orderBy('created_at', 'asc')
                ->first();

            if ($primerRegistro) {
                // Verificar si el primer registro está dentro del rango seleccionado
                $primerRegistroFecha = $primerRegistro->created_at->format('Y-m-d H:i:s');
                $fechaInicioRango = $fechaDesde . ' 00:00:00';
                $fechaFinRango = $fechaHasta . ' 23:59:59';

                if ($primerRegistroFecha >= $fechaInicioRango && $primerRegistroFecha <= $fechaFinRango) {
                    // Verificar también el filtro de hora si está aplicado
                    if (!empty($horaDesde) && !empty($horaHasta)) {
                        $horaPrimerRegistro = $primerRegistro->created_at->format('H:i:s');
                        if ($horaPrimerRegistro >= $horaDesde && $horaPrimerRegistro <= $horaHasta) {
                            $ipsNuevas[] = $ip;
                            $totalIPsNuevas++;
                        }
                    } else {
                        $ipsNuevas[] = $ip;
                        $totalIPsNuevas++;
                    }
                }
            }
        }

        return [
            'lista' => $ipsNuevas,
            'total' => $totalIPsNuevas
        ];
    }

    // MÉTODO PARA OBTENER DATOS PARA EL MAPA
    private function obtenerDatosMapa($fechaDesde, $fechaHasta, $busqueda, $horaDesde = '', $horaHasta = '')
    {
        // Query base para obtener clicks con coordenadas
        $query = Click::with(['oferta.producto', 'oferta.tienda'])
            ->whereBetween('created_at', [$fechaDesde . ' 00:00:00', $fechaHasta . ' 23:59:59'])
            ->whereNotNull('latitud')
            ->whereNotNull('longitud')
            ->where('latitud', '!=', 0)
            ->where('longitud', '!=', 0);

        // Aplicar filtro de hora si se especifica
        if (!empty($horaDesde) && !empty($horaHasta)) {
            $query->whereRaw('TIME(created_at) BETWEEN ? AND ?', [$horaDesde, $horaHasta]);
        }

        // Aplicar búsqueda si existe
        if (!empty($busqueda)) {
            $terminos = array_map('trim', explode(',', $busqueda));
            
            $query->where(function($q) use ($terminos) {
                foreach ($terminos as $termino) {
                    $q->where(function($subQ) use ($termino) {
                        $subQ->whereHas('oferta.producto', function($productoQ) use ($termino) {
                            $productoQ->where(function($p) use ($termino) {
                                $p->where('nombre', 'LIKE', '%' . $termino . '%')
                                  ->orWhere('marca', 'LIKE', '%' . $termino . '%')
                                  ->orWhere('talla', 'LIKE', '%' . $termino . '%');
                            });
                        })
                        ->orWhereHas('oferta.tienda', function($tiendaQ) use ($termino) {
                            $tiendaQ->where('nombre', 'LIKE', '%' . $termino . '%');
                        })
                        ->orWhere('campaña', 'LIKE', '%' . $termino . '%')
                        ->orWhere('ip', 'LIKE', '%' . $termino . '%')
                        ->orWhere('ciudad', 'LIKE', '%' . $termino . '%');
                    });
                }
            });
        }

        // Obtener clicks agrupados por coordenadas
        $clicks = $query->get();
        
        // Agrupar clicks por coordenadas para contar repeticiones
        $puntosMapa = [];
        
        foreach ($clicks as $click) {
            $clave = $click->latitud . ',' . $click->longitud;
            
            if (!isset($puntosMapa[$clave])) {
                $puntosMapa[$clave] = [
                    'latitud' => (float) $click->latitud,
                    'longitud' => (float) $click->longitud,
                    'ciudad' => $click->ciudad,
                    'total' => 0,
                    'ips' => []
                ];
            }
            
            $puntosMapa[$clave]['total']++;
            
            // Agregar IP única para contar IPs diferentes
            if (!in_array($click->ip, $puntosMapa[$clave]['ips'])) {
                $puntosMapa[$clave]['ips'][] = $click->ip;
            }
        }
        
        // Convertir a array y calcular IPs únicas
        $puntos = [];
        foreach ($puntosMapa as $punto) {
            $puntos[] = [
                'latitud' => $punto['latitud'],
                'longitud' => $punto['longitud'],
                'ciudad' => $punto['ciudad'],
                'total_clicks' => $punto['total'],
                'ips_unicas' => count($punto['ips']),
                'label' => $punto['ciudad'] . ' (' . $punto['total'] . ' clicks, ' . count($punto['ips']) . ' IPs)'
            ];
        }
        
        return [
            'puntos' => $puntos,
            'total_puntos' => count($puntos),
            'total_clicks' => $clicks->count()
        ];
    }

    /**
     * Procesa la geolocalización de clicks pendientes
     * Método para ser llamado desde cron jobs
     */
    public function procesarGeolocalizacion(Request $request)
    {
        $limit = $request->get('limit', 10); // Máximo clicks a procesar por ejecución
        
        // Buscar clicks sin geolocalización
        $clicksSinGeo = Click::whereNull('ciudad')
            ->whereNull('latitud')
            ->whereNull('longitud')
            ->whereNotNull('ip')
            ->where('ip', '!=', '')
            ->limit($limit)
            ->get();
        
        if ($clicksSinGeo->isEmpty()) {
            return response()->json([
                'status' => 'ok',
                'message' => 'No hay clicks pendientes de geolocalización',
                'procesados' => 0,
                'errores' => 0
            ]);
        }
        
        $procesados = 0;
        $errores = 0;
        
        foreach ($clicksSinGeo as $click) {
            try {
                // Obtener geolocalización
                $geoData = $this->obtenerGeolocalizacion($click->ip);
                
                if ($geoData['ciudad']) {
                    // Actualizar el click con la geolocalización (redondear coordenadas a 7 decimales)
                    $click->update([
                        'ciudad' => $geoData['ciudad'],
                        'pais' => $geoData['pais'],
                        'latitud' => round($geoData['latitud'], 7),
                        'longitud' => round($geoData['longitud'], 7),
                    ]);
                    
                    $procesados++;
                } else {
                    $errores++;
                }
                
                // Esperar 10 segundos entre peticiones para no sobrecargar ip.guide
                if ($click !== $clicksSinGeo->last()) {
                    sleep(10);
                }
                
            } catch (\Exception $e) {
                $errores++;
                
                Log::error("Error procesando geolocalización", [
                    'click_id' => $click->id,
                    'ip' => $click->ip,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        return response()->json([
            'status' => 'ok',
            'message' => 'Geolocalización procesada',
            'procesados' => $procesados,
            'errores' => $errores,
            'total_encontrados' => $clicksSinGeo->count()
        ]);
    }
    
    /**
     * Obtiene la geolocalización de una IP usando el servicio ip.guide
     * 
     * @param string $ip Dirección IP a consultar
     * @return array Array con ciudad, país, latitud y longitud
     */
    private function obtenerGeolocalizacion(string $ip): array
    {
        try {
            // Hacer petición al servicio ip.guide
            $response = Http::timeout(10)->get("https://ip.guide/{$ip}");
            
            if ($response->successful()) {
                $data = $response->json();
                
                // Extraer información de geolocalización
                $location = $data['location'] ?? null;
                
                // Si location existe y tiene ciudad, devolver datos completos
                if ($location && isset($location['city']) && !empty($location['city'])) {
                    return [
                        'ciudad' => $location['city'],
                        'pais' => $location['country'] ?? null,
                        'latitud' => $location['latitude'] ?? null,
                        'longitud' => $location['longitude'] ?? null,
                    ];
                }
                
                // Si location es null, devolver ciudad como "error"
                if ($location === null) {
                    Log::warning("Geolocalización con location null para IP", [
                        'ip' => $ip,
                        'response' => $data
                    ]);
                    
                    return [
                        'ciudad' => 'error',
                        'pais' => null,
                        'latitud' => null,
                        'longitud' => null,
                    ];
                }
            }
            
            Log::warning("No se pudo obtener geolocalización para IP", [
                'ip' => $ip,
                'status' => $response->status()
            ]);
            
        } catch (\Exception $e) {
            Log::error("Error al obtener geolocalización", [
                'ip' => $ip,
                'error' => $e->getMessage()
            ]);
        }
        
        // Retornar valores por defecto si hay error
        return [
            'ciudad' => 'error',
            'pais' => null,
            'latitud' => null,
            'longitud' => null,
        ];
    }

    //FUNCION PARA LAS ESTADISTICAS DE CLICK EN PRODUCTO
    public function estadisticasAvanzadas(Request $request, Producto $producto)
    {
        $desde = $request->input('desde', now()->subDays(30)->toDateString());
        $hasta = $request->input('hasta', now()->toDateString());
        $campana = $request->input('campana');

        $palabrasClave = $producto->palabrasClave()->get();

        $query = DB::table('clicks')
            ->join('ofertas_producto', 'clicks.oferta_id', '=', 'ofertas_producto.id')
            ->join('tiendas', 'ofertas_producto.tienda_id', '=', 'tiendas.id')
            ->where('ofertas_producto.producto_id', $producto->id)
            ->whereBetween('clicks.created_at', [$desde . ' 00:00:00', $hasta . ' 23:59:59']);

        if ($campana) {
            $query->where('clicks.campaña', $campana);
        }

        $agrupado = $query
            ->selectRaw('tiendas.id, tiendas.nombre, tiendas.url_imagen, tiendas.opiniones, tiendas.puntuacion, COUNT(*) as total_clicks, MIN(clicks.precio_unidad) as min, MAX(clicks.precio_unidad) as max')
            ->groupBy('tiendas.id', 'tiendas.nombre', 'tiendas.url_imagen', 'tiendas.opiniones', 'tiendas.puntuacion')
            ->orderByDesc('total_clicks')
            ->get()
            ->keyBy('id');

        $visibilidad = DB::table('ofertas_producto')
            ->selectRaw('tienda_id, COUNT(*) as total, SUM(CASE WHEN mostrar = "no" THEN 1 ELSE 0 END) as ocultas')
            ->where('producto_id', $producto->id)
            ->groupBy('tienda_id')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->tienda_id => $item->ocultas >= $item->total];
            });

        $clicsPaginados = Click::with(['oferta.tienda'])
            ->whereHas('oferta', function ($q) use ($producto) {
                $q->where('producto_id', $producto->id);
            })
            ->when($campana, fn($q) => $q->where('campaña', $campana))
            ->whereBetween('created_at', [$desde . ' 00:00:00', $hasta . ' 23:59:59'])
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('admin.productos.estadisticasClicks', compact(
            'producto',
            'desde',
            'hasta',
            'campana',
            'palabrasClave',
            'agrupado',
            'visibilidad',
            'clicsPaginados',
        ));
    }

    //COMPROBAR SI LA IP ESTÁ EN LA LISTA DE EXCLUSIÓN Y NO CONTABILIZARLA
    private function esIPExcluida(string $ip): bool
    {
        // Lista de IPs o rangos de IPs que NO deben contabilizarse
        $ipsExcluidas = [

            //VillaLopez
            '81.38.158.',

            // Googlebot IPs (todo el rango 66.249.x.x)
            '66.249.',     // Googlebot - todas las IPs que empiecen por 66.249
            
            // Otros bots conocidos
            '40.77.167.',  // Microsoft Bingbot
            '207.46.13.',  // Microsoft Bingbot
            
            // Facebook
            '31.13.24.',   // Facebook crawler
            '31.13.25.',   // Facebook crawler
            '31.13.26.',   // Facebook crawler
            '31.13.27.',   // Facebook crawler
            
            // Twitter
            '199.16.156.', // Twitter bot
            '199.59.148.', // Twitter bot
            
            // LinkedIn
            '108.174.10.', // LinkedIn bot

            // Alibaba
            '47.82.11.', 
            
            // Puedes añadir más IPs o rangos aquí según necesites
            // '192.168.1.',    // Ejemplo de IP local
            // '10.0.0.',       // Ejemplo de IP local
        ];

        // Verificar si la IP empieza por alguno de los prefijos excluidos
        foreach ($ipsExcluidas as $prefijoExcluido) {
            if (str_starts_with($ip, $prefijoExcluido)) {
                return true;
            }
        }

        return false;
    }


    /**
     * MÉTODO PRINCIPAL QUE MANEJA LOS CLICKS EN OFERTAS
     * 
     * Este método se ejecuta cuando un usuario hace click en una oferta.
     * ANTES: Guardaba el click de forma síncrona (el usuario esperaba)
     * AHORA: Envía un Job asíncrono y redirige inmediatamente
     */
    public function redirigir(Request $request, $ofertaId)
    {
        // ===== RECOPILACIÓN DE DATOS BÁSICOS =====
        // Obtenemos los datos necesarios para procesar el click
        $cam = $request->query('cam');                    // Parámetro de campaña (opcional)
        $oferta = OfertaProducto::with('producto')->findOrFail($ofertaId); // Buscamos la oferta
        $ip = $request->ip();                            // IP del usuario para evitar duplicados

        // ===== EXCLUSIÓN DE USUARIOS AUTENTICADOS =====
        // Si el usuario está autenticado, saltarse todos los bloqueos
        $usuarioAutenticado = auth()->check();
        $captchaResuelto = false; // Flag para indicar si el CAPTCHA fue resuelto
        
        // ===== BLOQUEO INMEDIATO DE BOTS DE IA =====
        // Los bots de IA se bloquean siempre, sin límites
        if ($this->esBotIA($request)) {
            $this->bloquearIPMensual($ip, 0); // Bloqueo mensual inmediato
            return response()
                ->view('redireccion', [
                    'bloqueadoMensual' => true,
                    'ofertaId' => $ofertaId,
                    'cam' => $cam,
                ])
                ->header('X-Robots-Tag', 'noindex, nofollow');
        }

        // ===== VALIDACIONES DE SEGURIDAD PARA USUARIOS NO AUTENTICADOS =====
        if (!$usuarioAutenticado) {
            // Si es un bot legítimo (Googlebot, Bingbot, etc.), redirigir sin restricciones
            if ($this->esBot($request) || $this->esIPExcluida($ip)) {
                // Guardar click con lógica de duplicados (estadísticas)
                $this->guardarClick($oferta, $ip, $cam);
                $urlConAfiliado = $this->procesarUrlAfiliacion($oferta);
                return response()
                    ->view('redireccion', [
                        'requiereCaptcha' => false,
                        'bloqueadoMensual' => false,
                        'url' => $urlConAfiliado,
                        'ofertaId' => $ofertaId,
                        'cam' => $cam,
                    ])
                    ->header('X-Robots-Tag', 'noindex, nofollow');
            }

            // ===== VALIDACIÓN DE BLOQUEO MENSUAL =====
            if ($this->estaIPBloqueadaMensual($ip)) {
                return response()
                    ->view('redireccion', [
                        'bloqueadoMensual' => true,
                        'ofertaId' => $ofertaId,
                        'cam' => $cam,
                    ])
                    ->header('X-Robots-Tag', 'noindex, nofollow');
            }

            // ===== VALIDACIÓN DE BLOQUEO POR CAPTCHA =====
            if ($this->estaIPBloqueada($ip)) {
                $captchaToken = $request->query('captcha_token');
                
                if ($captchaToken && $this->verificarCaptcha($captchaToken)) {
                    // CAPTCHA válido, desbloquear IP y marcar como resuelto
                    $this->desbloquearIP($ip);
                    $captchaResuelto = true;
                } else {
                    // No tiene token o token inválido, mostrar CAPTCHA
                    return response()
                        ->view('redireccion', [
                            'requiereCaptcha' => true,
                            'bloqueadoMensual' => false,
                            'ofertaId' => $ofertaId,
                            'cam' => $cam,
                        ])
                        ->header('X-Robots-Tag', 'noindex, nofollow');
                }
            }

            // ===== GUARDADO DEL CLICK PARA CONTEO DE BLOQUEOS =====
            // Guardamos el click ANTES de verificar bloqueos para que se cuente correctamente
            // Esto guarda TODAS las redirecciones (incluso duplicados) para el conteo
            // PERO solo si NO acabamos de resolver un CAPTCHA (para evitar bloqueos inmediatos)
            if (!$captchaResuelto) {
                $this->guardarClickParaBloqueo($oferta, $ip, $cam);

                // ===== DETECCIÓN DE PATRONES SOSPECHOSOS =====
                // Verificar bloqueo mensual (200/semana)
                if ($this->debeBloquearIPMensual($ip)) {
                    $cantidad = Click::where('ip', $ip)
                        ->where('created_at', '>', now()->subWeek())
                        ->count();
                    $this->bloquearIPMensual($ip, $cantidad);
                    
                    return response()
                        ->view('redireccion', [
                            'bloqueadoMensual' => true,
                            'ofertaId' => $ofertaId,
                            'cam' => $cam,
                        ])
                        ->header('X-Robots-Tag', 'noindex, nofollow');
                }

                // Verificar bloqueo por CAPTCHA diario (100/día)
                if ($this->debeBloquearIPDiario($ip)) {
                    $cantidad = Click::where('ip', $ip)
                        ->whereDate('created_at', now()->toDateString())
                        ->count();
                    $this->bloquearIP($ip, 'CAPTCHA diario activado (100+ redirecciones en un día)', $cantidad);
                    
                    return response()
                        ->view('redireccion', [
                            'requiereCaptcha' => true,
                            'bloqueadoMensual' => false,
                            'ofertaId' => $ofertaId,
                            'cam' => $cam,
                        ])
                        ->header('X-Robots-Tag', 'noindex, nofollow');
                }

                // Verificar bloqueo por CAPTCHA (15/5min)
                if ($this->debeBloquearIP($ip)) {
                    $cantidad = Click::where('ip', $ip)
                        ->where('created_at', '>', now()->subMinutes(5))
                        ->count();
                    $this->bloquearIP($ip, 'CAPTCHA activado (15+ redirecciones en 5 minutos)', $cantidad);
                    
                    return response()
                        ->view('redireccion', [
                            'requiereCaptcha' => true,
                            'bloqueadoMensual' => false,
                            'ofertaId' => $ofertaId,
                            'cam' => $cam,
                        ])
                        ->header('X-Robots-Tag', 'noindex, nofollow');
                }
            }
        }

        // ===== PROCESAMIENTO DE URLS DE AFILIACIÓN =====
        // Si el usuario está autenticado, usar URL original sin parámetros de afiliación
        // Si no está autenticado, añadir parámetros de afiliación
        if ($usuarioAutenticado) {
            $urlConAfiliado = $oferta->url; // URL original sin parámetros
        } else {
            $urlConAfiliado = $this->procesarUrlAfiliacion($oferta);
        }

        // ===== GUARDADO DEL CLICK (solo si no se guardó antes) =====
        // Si el usuario está autenticado, NO guardar el click (ni para bloqueo ni para estadísticas)
        // Si el CAPTCHA fue resuelto, guardar el click para estadísticas (con lógica de duplicados)
        // Si es bot legítimo o IP excluida, guardar con lógica de duplicados
        if (!$usuarioAutenticado) {
            if ($this->esBot($request) || $this->esIPExcluida($ip)) {
                $this->guardarClick($oferta, $ip, $cam);
            } elseif (isset($captchaResuelto) && $captchaResuelto) {
                // Si el CAPTCHA fue resuelto, guardar para estadísticas (con lógica de duplicados)
                $this->guardarClick($oferta, $ip, $cam);
            }
        }

        // ===== REDIRECCIÓN NORMAL =====
        // El usuario es redirigido normalmente (sin bloqueos)
        return response()
        ->view('redireccion', [
            'requiereCaptcha' => false,
            'bloqueadoMensual' => false,
            'url' => $urlConAfiliado,
            'ofertaId' => $ofertaId,
            'cam' => $cam,
        ])
        ->header('X-Robots-Tag', 'noindex, nofollow');
    }

    /**
     * Calcula la posición de una oferta basándose en el precio por unidad
     * En el momento del click, consulta todas las ofertas del producto y ordena por precio
     */
    private function calcularPosicionOferta(OfertaProducto $oferta): ?int
    {
        // Si la oferta está oculta o no tiene precio válido, no tiene posición
        if ($oferta->mostrar !== 'si' || $oferta->precio_unidad <= 0 || is_null($oferta->precio_unidad)) {
            return null;
        }

        // Consulta en tiempo real: obtener todas las ofertas visibles del mismo producto
        // ordenadas por precio de menor a mayor
        $ofertasOrdenadas = OfertaProducto::where('producto_id', $oferta->producto_id)
            ->where('mostrar', 'si') // Solo ofertas visibles
            ->whereNotNull('precio_unidad') // Solo ofertas con precio válido
            ->where('precio_unidad', '>', 0) // Solo precios positivos
            ->orderBy('precio_unidad', 'asc') // Ordenar por precio de menor a mayor
            ->pluck('id')
            ->toArray();

        // Buscar en qué posición está la oferta actual
        $posicion = array_search($oferta->id, $ofertasOrdenadas);
        
        // Si no se encuentra, retornar null
        if ($posicion === false) {
            return null;
        }
        
        // Retornar posición + 1 (las posiciones empiezan en 1, no en 0)
        return $posicion + 1;
    }

    //COMPROBAR SI ES BOT EL QUE HA CLICKEADO EN LA OFERTA Y NO CONTABILIZARLO
    private function esBot(Request $request): bool
    {
        $ua = Str::lower($request->userAgent() ?? '');

        // 1) User-Agents conocidos de bots/crawlers
        $patrones = [
            'bot','spider','crawler','preview','linkchecker',
            'googlebot','bingbot','slurp','duckduckbot','baiduspider',
            'yandexbot','sogou','exabot','facebookexternalhit','facebot',
            'ia_archiver','ahrefsbot','semrushbot','mj12bot','uptimerobot',
        ];
        foreach ($patrones as $p) {
            if (Str::contains($ua, $p)) return true;
        }

        // 2) Requests de prefetch/prerender/link preview
        $h = $request->headers;
        if ($request->isMethod('HEAD')) return true;
        if (Str::lower($h->get('Purpose','')) === 'prefetch') return true;
        if (Str::lower($h->get('X-Purpose','')) === 'preview') return true;
        if (Str::lower($h->get('Sec-Purpose','')) === 'prefetch;prerender') return true;
        if (Str::lower($h->get('Sec-Fetch-Mode','')) === 'navigate'
            && Str::lower($h->get('Sec-Fetch-Site','')) === 'none'
            && Str::contains($ua, 'google')) return true;

        // 3) Sin UA (muchos bots lo omiten)
        if ($ua === '') return true;

        return false;
    }

    /**
     * Verifica si el request es de un bot de inteligencia artificial
     */
    private function esBotIA(Request $request): bool
    {
        $ua = Str::lower($request->userAgent() ?? '');
        
        // User-Agents conocidos de bots de IA
        $patronesIA = [
            'gptbot',           // OpenAI GPTBot
            'chatgpt-user',     // ChatGPT
            'ccbot',            // Common Crawl (usado por servicios de IA)
            'anthropic-ai',     // Anthropic
            'claude-web',       // Claude
            'perplexitybot',    // Perplexity
            'google-extended',  // Google Extended (usado por IA)
            'openai',           // OpenAI
            'chatgpt',          // ChatGPT
        ];
        
        foreach ($patronesIA as $patron) {
            if (Str::contains($ua, $patron)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Verifica si una IP está bloqueada por CAPTCHA (15/5min o 100/día)
     */
    private function estaIPBloqueada(string $ip): bool
    {
        $cacheKey = "ip_bloqueada_captcha_{$ip}";
        return Cache::has($cacheKey);
    }

    /**
     * Verifica si una IP está bloqueada mensualmente (200/semana)
     */
    private function estaIPBloqueadaMensual(string $ip): bool
    {
        $cacheKey = "ip_bloqueada_mensual_{$ip}";
        return Cache::has($cacheKey);
    }

    /**
     * Verifica si una IP debe ser bloqueada por CAPTCHA (15 redirecciones en 5 minutos)
     */
    private function debeBloquearIP(string $ip): bool
    {
        $redireccionesRecientes = Click::where('ip', $ip)
            ->where('created_at', '>', now()->subMinutes(5))
            ->count();
        
        return $redireccionesRecientes >= 15;
    }

    /**
     * Verifica si una IP debe ser bloqueada por CAPTCHA diario (100 redirecciones en un día)
     */
    private function debeBloquearIPDiario(string $ip): bool
    {
        $redireccionesHoy = Click::where('ip', $ip)
            ->whereDate('created_at', now()->toDateString())
            ->count();
        
        return $redireccionesHoy >= 100;
    }

    /**
     * Verifica si una IP debe ser bloqueada mensualmente (200 redirecciones en una semana)
     */
    private function debeBloquearIPMensual(string $ip): bool
    {
        $redireccionesSemana = Click::where('ip', $ip)
            ->where('created_at', '>', now()->subWeek())
            ->count();
        
        return $redireccionesSemana >= 200;
    }

    /**
     * Bloquea una IP por CAPTCHA (hasta que resuelva el CAPTCHA)
     */
    private function bloquearIP(string $ip, string $motivo, int $cantidad): array
    {
        $cacheKey = "ip_bloqueada_captcha_{$ip}";
        // Bloquear por 24 horas o hasta que se resuelva el CAPTCHA
        Cache::put($cacheKey, true, now()->addDay());
        
        // Generar aviso
        $resultadoAviso = $this->generarAviso($ip, $cantidad, $motivo);
        
        Log::warning('IP bloqueada por CAPTCHA', [
            'ip' => $ip,
            'redirecciones' => $cantidad,
            'motivo' => $motivo
        ]);
        
        return $resultadoAviso;
    }

    /**
     * Bloquea una IP mensualmente (por 1 mes)
     */
    private function bloquearIPMensual(string $ip, int $cantidad): array
    {
        $cacheKey = "ip_bloqueada_mensual_{$ip}";
        
        // Bloquear por 1 mes
        Cache::put($cacheKey, true, now()->addMonth());
        
        // Generar aviso
        $resultadoAviso = $this->generarAviso($ip, $cantidad, 'Bloqueo mensual activado (200+ redirecciones en una semana)');
        
        Log::warning('IP bloqueada mensualmente', [
            'ip' => $ip,
            'redirecciones' => $cantidad
        ]);
        
        return $resultadoAviso;
    }

    /**
     * Desbloquea una IP después de resolver el CAPTCHA
     */
    private function desbloquearIP(string $ip): void
    {
        $cacheKey = "ip_bloqueada_captcha_{$ip}";
        Cache::forget($cacheKey);
        
        Log::info('IP desbloqueada después de completar CAPTCHA', ['ip' => $ip]);
    }

    /**
     * Verifica el token de CAPTCHA de Google reCAPTCHA
     */
    private function verificarCaptcha(string $token): bool
    {
        $secretKey = env('RECAPTCHA_SECRET_KEY');
        
        if (!$secretKey) {
            Log::error('RECAPTCHA_SECRET_KEY no configurada');
            return false;
        }
        
        try {
            $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
                'secret' => $secretKey,
                'response' => $token,
                'remoteip' => request()->ip()
            ]);
            
            $result = $response->json();
            return $result['success'] ?? false;
        } catch (\Exception $e) {
            Log::error('Error al verificar CAPTCHA', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Genera un aviso en la base de datos cuando se activa un bloqueo
     */
    private function generarAviso(string $ip, int $cantidad, string $motivo): array
    {
        try {
            $avisoId = DB::table('avisos')->insertGetId([
                'texto_aviso' => "IP bloqueada: {$ip}. Redirecciones: {$cantidad}. Motivo: {$motivo}",
                'fecha_aviso' => now(), // Fecha actual (momento del bloqueo)
                'user_id' => 1, // usuario sistema
                'avisoable_type' => 'Interno', // Aviso interno (sin modelo asociado)
                'avisoable_id' => 0, // Aviso interno (sin modelo asociado)
                'oculto' => 0, // visible
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            return [
                'success' => true,
                'aviso_id' => $avisoId,
                'mensaje' => "Aviso creado correctamente (ID: {$avisoId})"
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'mensaje' => "Error al crear aviso: " . $e->getMessage()
            ];
        }
    }

    /**
     * Guarda un click en la base de datos (sin geolocalización) para conteo de bloqueos
     * Este método SIEMPRE guarda el click, sin verificar duplicados
     */
    private function guardarClickParaBloqueo(OfertaProducto $oferta, string $ip, ?string $cam): void
    {
        try {
            // Calcular posición de la oferta
            $posicion = $this->calcularPosicionOferta($oferta);
            
            // Guardar SIEMPRE para que el conteo de bloqueos funcione correctamente
            Click::create([
                'oferta_id' => $oferta->id,
                'campaña' => $cam,
                'ip' => $ip,
                'precio_unidad' => $oferta->precio_unidad,
                'posicion' => $posicion,
                // NO guardamos geolocalización aquí - lo hará el cron
                'ciudad' => null,
                'pais' => null,
                'latitud' => null,
                'longitud' => null,
            ]);
            
        } catch (\Exception $e) {
            // IMPORTANTE: Si hay error al guardar, NO bloqueamos al usuario
            Log::error("Error al guardar click para bloqueo", [
                'oferta_id' => $oferta->id,
                'ip' => $ip,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Guarda un click en la base de datos (sin geolocalización)
     * Este método evita duplicados (IP + oferta + fecha) para estadísticas
     */
    private function guardarClick(OfertaProducto $oferta, string $ip, ?string $cam): void
    {
        try {
            // Calcular posición de la oferta
            $posicion = $this->calcularPosicionOferta($oferta);
            
            // Evitar duplicados (IP + oferta + fecha)
            $existe = Click::where('oferta_id', $oferta->id)
                ->where('ip', $ip)
                ->whereDate('created_at', now()->toDateString())
                ->exists();
            
            // Si no existe duplicado, guardar el click SIN geolocalización
            if (!$existe) {
                Click::create([
                    'oferta_id' => $oferta->id,
                    'campaña' => $cam,
                    'ip' => $ip,
                    'precio_unidad' => $oferta->precio_unidad,
                    'posicion' => $posicion,
                    // NO guardamos geolocalización aquí - lo hará el cron
                    'ciudad' => null,
                    'pais' => null,
                    'latitud' => null,
                    'longitud' => null,
                ]);
                
                Log::info("Click guardado directamente", [
                    'oferta_id' => $oferta->id,
                    'ip' => $ip,
                    'posicion' => $posicion,
                    'tienda' => $oferta->tienda->nombre ?? 'N/A'
                ]);
            }
            
        } catch (\Exception $e) {
            // IMPORTANTE: Si hay error al guardar, NO bloqueamos al usuario
            Log::error("Error al guardar click", [
                'oferta_id' => $oferta->id,
                'ip' => $ip,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Procesa la URL de una oferta añadiendo códigos de afiliación según la tienda
     */
    private function procesarUrlAfiliacion(OfertaProducto $oferta): string
    {
        $base = $oferta->url;
        $sep  = parse_url($base, PHP_URL_QUERY) ? '&' : '?'; // Detecta si ya tiene parámetros
        
        if ($oferta->tienda_id == 2) {
            // Amazon - añade tag de afiliado
            return $base . $sep . 'tag=kmpa2-21';
        
        } elseif ($oferta->tienda_id == 9999) {
            // Ejemplo Aliexpress para cuando tengamos afiliacion
            return $base . $sep . http_build_query([
                'tt'             => 'CPS_NORMAL',
                'aff_platform'   => 'portals-tool',
                'afSmartRedirect'=> 'y',
                'aff_fcid'       => '7aaf9292ea694a388de71775d85a0607-1757284266870-00409-_oEuPaip',
                'aff_fsk'        => '_oEuPaip',
                'aff_trace_key'  => '7aaf9292ea694a388de71775d85a0607-1757284266870-00409-_oEuPaip',
                'sk'             => '_oEuPaip',
                'terminal_id'    => '8131d8ed9f8045ca855f504617e42023',
            ], '', '&', PHP_QUERY_RFC3986);
        
        }  elseif ($oferta->tienda_id == 99999) {
            // Ejemplo Awin para cuando tengamos
            return 'https://www.awin1.com/cread.php?awinmid=25399&awinaffid=1302515&ued=' . $base;
        
        } else {
            // Otras tiendas - sin códigos de afiliación
            return $base;
        }
    }



    //SACAR RANGO DE PRECIO
    public function rangoPrecio(Request $request, Producto $producto)
    {
        $desde = $request->input('desde');
        $hasta = $request->input('hasta');
        $campana = $request->input('campana');

        $query = DB::table('clicks')
            ->join('ofertas_producto', 'clicks.oferta_id', '=', 'ofertas_producto.id')
            ->where('ofertas_producto.producto_id', $producto->id)
            ->whereBetween('clicks.created_at', [$desde . ' 00:00:00', $hasta . ' 23:59:59']);

        if ($campana) {
            $query->where('clicks.campaña', $campana);
        }

        $min = $query->min('clicks.precio_unidad');
        $max = $query->max('clicks.precio_unidad');

        return response()->json([
            'min' => $min !== null ? number_format($min, 2, ',', '') : null,
            'max' => $max !== null ? number_format($max, 2, ',', '') : null,
        ]);
    }

    //PARA LA GRAFICA DE CLICKS POR HORAS
    public function porHora(Request $request, Producto $producto)
{
    $desde = $request->input('desde');
    $hasta = $request->input('hasta');
    $campana = $request->input('campana');

    $query = DB::table('clicks')
        ->join('ofertas_producto', 'clicks.oferta_id', '=', 'ofertas_producto.id')
        ->where('ofertas_producto.producto_id', $producto->id)
        ->whereBetween('clicks.created_at', [$desde . ' 00:00:00', $hasta . ' 23:59:59']);

    if ($campana) {
        $query->where('clicks.campaña', $campana);
    }

    $resultados = $query
        ->selectRaw('HOUR(clicks.created_at) as hora, FLOOR(MINUTE(clicks.created_at)/30)*30 as bloque, COUNT(*) as total')
        ->groupBy('hora', 'bloque')
        ->orderBy('hora')
        ->orderBy('bloque')
        ->get();

    $labels = [];
    $values = [];

    for ($h = 0; $h < 24; $h++) {
        foreach ([0, 30] as $m) {
            $label = str_pad($h, 2, '0', STR_PAD_LEFT) . ':' . str_pad($m, 2, '0', STR_PAD_LEFT);
            $labels[] = $label;
            $values[] = 0;
        }
    }

    foreach ($resultados as $r) {
        $index = $r->hora * 2 + ($r->bloque === 30 ? 1 : 0);
        $values[$index] = $r->total;
    }

    return response()->json([
        'labels' => $labels,
        'values' => $values,
    ]);
}



    //LISTA DE TIENDAS, CON CLICKS Y RANGO DE PRECIOS, EN LAS ESTADISTICAS DE PRODUCTO
    public function tiendas(Request $request, Producto $producto)
    {
        $desde = $request->input('desde');
        $hasta = $request->input('hasta');
        $campana = $request->input('campana');

        $query = DB::table('clicks')
            ->join('ofertas_producto', 'clicks.oferta_id', '=', 'ofertas_producto.id')
            ->join('tiendas', 'ofertas_producto.tienda_id', '=', 'tiendas.id')
            ->where('ofertas_producto.producto_id', $producto->id)
            ->whereBetween('clicks.created_at', [$desde . ' 00:00:00', $hasta . ' 23:59:59']);

        if ($campana) {
            $query->where('clicks.campaña', $campana);
        }

        $agrupado = $query
            ->selectRaw('tiendas.id, tiendas.nombre, tiendas.url_imagen, tiendas.opiniones, tiendas.puntuacion, COUNT(*) as total_clicks, MIN(clicks.precio_unidad) as min, MAX(clicks.precio_unidad) as max')
            ->groupBy('tiendas.id', 'tiendas.nombre', 'tiendas.url_imagen', 'tiendas.opiniones', 'tiendas.puntuacion')
            ->orderByDesc('total_clicks')
            ->get();

        // Verificamos si la tienda tiene TODAS sus ofertas del producto como "no visibles"
        $visibilidad = DB::table('ofertas_producto')
            ->selectRaw('tienda_id, COUNT(*) as total, SUM(CASE WHEN mostrar = "no" THEN 1 ELSE 0 END) as ocultas')
            ->where('producto_id', $producto->id)
            ->groupBy('tienda_id')
            ->get()
            ->pluck('ocultas', 'tienda_id');

        return View::make('admin.productos.partials.tiendasClicks', compact('agrupado', 'visibilidad'))->render();
    }
    
    // MÉTODO PARA OBTENER CLICKS POR POSICIÓN DE UNA TIENDA
    public function posicionesTienda(Request $request)
    {
        $tiendaId = $request->input('tienda_id');
        $fechaDesde = $request->input('fecha_desde', now()->toDateString());
        $fechaHasta = $request->input('fecha_hasta', now()->toDateString());
        $busqueda = $request->input('busqueda', '');
        $horaDesde = $request->input('hora_desde', '');
        $horaHasta = $request->input('hora_hasta', '');
        
        try {
            // Query base para obtener clicks de la tienda específica
            $query = Click::with(['oferta.producto', 'oferta.tienda'])
                ->whereHas('oferta', function($q) use ($tiendaId) {
                    $q->where('tienda_id', $tiendaId);
                })
                ->whereBetween('created_at', [$fechaDesde . ' 00:00:00', $fechaHasta . ' 23:59:59']);
            
            // Aplicar filtro de hora si se especifica
            if (!empty($horaDesde) && !empty($horaHasta)) {
                $query->whereRaw('TIME(created_at) BETWEEN ? AND ?', [$horaDesde, $horaHasta]);
            }
            
            // Aplicar búsqueda si existe
            if (!empty($busqueda)) {
                $terminos = array_map('trim', explode(',', $busqueda));
                
                $query->where(function($q) use ($terminos) {
                    foreach ($terminos as $termino) {
                        $q->where(function($subQ) use ($termino) {
                            $subQ->whereHas('oferta.producto', function($productoQ) use ($termino) {
                                $productoQ->where(function($p) use ($termino) {
                                    $p->where('nombre', 'LIKE', '%' . $termino . '%')
                                      ->orWhere('marca', 'LIKE', '%' . $termino . '%')
                                      ->orWhere('talla', 'LIKE', '%' . $termino . '%');
                                });
                            })
                            ->orWhereHas('oferta.tienda', function($tiendaQ) use ($termino) {
                                $tiendaQ->where('nombre', 'LIKE', '%' . $termino . '%');
                            })
                            ->orWhere('campaña', 'LIKE', '%' . $termino . '%');
                        });
                    }
                });
            }
            
            // Calcular total de clicks de ESTA tienda específica (ANTES de filtrar por posición)
            $totalClicksEstaTienda = $query->count();
            
            // Obtener clicks agrupados por posición
            $posiciones = $query->select(DB::raw('posicion, COUNT(*) as total'))
                ->whereNotNull('posicion')
                ->groupBy('posicion')
                ->orderBy('posicion', 'asc')
                ->get();
            
            return response()->json([
                'success' => true,
                'posiciones' => $posiciones,
                'totalClicks' => $totalClicksEstaTienda
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los datos de posiciones: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verifica las posiciones de las ofertas de un producto específico
     * Útil para debugging y validación
     */
    public function verificarPosiciones(Request $request, $productoId)
    {
        $producto = \App\Models\Producto::findOrFail($productoId);
        
        // Obtener todas las ofertas del producto ordenadas por precio
        $ofertas = OfertaProducto::where('producto_id', $productoId)
            ->orderBy('precio_unidad', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        // Calcular posiciones esperadas (solo ofertas visibles)
        $ofertasVisibles = $ofertas->where('mostrar', 'si')
            ->where('precio_unidad', '>', 0)
            ->whereNotNull('precio_unidad');

        $posicionesEsperadas = [];
        $posicion = 1;
        foreach ($ofertasVisibles as $oferta) {
            $posicionesEsperadas[$oferta->id] = $posicion++;
        }

        // Obtener estadísticas de clicks para cada oferta
        $estadisticasClicks = Click::whereIn('oferta_id', $ofertas->pluck('id'))
            ->selectRaw('oferta_id, COUNT(*) as total_clicks, AVG(posicion) as posicion_promedio, MIN(posicion) as posicion_min, MAX(posicion) as posicion_max')
            ->groupBy('oferta_id')
            ->get()
            ->keyBy('oferta_id');

        $resultado = [];
        foreach ($ofertas as $oferta) {
            $estadisticas = $estadisticasClicks->get($oferta->id);
            $posicionEsperada = $posicionesEsperadas[$oferta->id] ?? null;
            
            $resultado[] = [
                'id' => $oferta->id,
                'tienda' => $oferta->tienda->nombre ?? 'N/A',
                'precio_unidad' => $oferta->precio_unidad,
                'mostrar' => $oferta->mostrar,
                'posicion_esperada' => $posicionEsperada,
                'total_clicks' => $estadisticas ? $estadisticas->total_clicks : 0,
                'posicion_promedio' => $estadisticas ? round($estadisticas->posicion_promedio, 2) : null,
                'posicion_min' => $estadisticas ? $estadisticas->posicion_min : null,
                'posicion_max' => $estadisticas ? $estadisticas->posicion_max : null,
                'coincide' => $estadisticas && $posicionEsperada && 
                             $estadisticas->posicion_min == $posicionEsperada && 
                             $estadisticas->posicion_max == $posicionEsperada
            ];
        }

        return response()->json([
            'producto' => [
                'id' => $producto->id,
                'nombre' => $producto->nombre,
                'marca' => $producto->marca,
                'talla' => $producto->talla
            ],
            'ofertas' => $resultado,
            'resumen' => [
                'total_ofertas' => $ofertas->count(),
                'ofertas_visibles' => $ofertasVisibles->count(),
                'ofertas_con_clicks' => $estadisticasClicks->count(),
                'posiciones_correctas' => collect($resultado)->where('coincide', true)->count(),
                'posiciones_incorrectas' => collect($resultado)->where('coincide', false)->where('total_clicks', '>', 0)->count()
            ]
        ]);
    }

    /**
     * Elimina un click específico
     * 
     * @param int $id ID del click a eliminar
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        try {
            $click = Click::findOrFail($id);
            
            // Guardar información del click antes de eliminarlo (para logs)
            $clickInfo = [
                'id' => $click->id,
                'ip' => $click->ip,
                'oferta_id' => $click->oferta_id,
                'campaña' => $click->campaña,
                'fecha' => $click->created_at->format('Y-m-d H:i:s')
            ];
            
            // Eliminar el click
            $click->delete();
            
            // Log de la eliminación
            Log::info('Click eliminado por administrador', $clickInfo);
            
            return response()->json([
                'success' => true,
                'message' => 'Click eliminado correctamente'
            ]);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'El click no existe o ya fue eliminado'
            ], 404);
            
        } catch (\Exception $e) {
            Log::error('Error al eliminar click', [
                'click_id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el click: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Regeolocaliza una IP y actualiza todos los clicks del mismo día con esa IP
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function regeolocalizarIP(Request $request)
    {
        try {
            $ip = $request->input('ip');
            $fechaClick = $request->input('fecha'); // Fecha del click en formato Y-m-d
            
            if (empty($ip)) {
                return response()->json([
                    'success' => false,
                    'message' => 'La IP es requerida'
                ], 400);
            }
            
            if (empty($fechaClick)) {
                return response()->json([
                    'success' => false,
                    'message' => 'La fecha es requerida'
                ], 400);
            }
            
            // Obtener geolocalización
            $geoData = $this->obtenerGeolocalizacion($ip);
            
            if ($geoData['ciudad'] === 'error') {
                // Si vuelve a dar error, devolver mensaje de error
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudo obtener la geolocalización. El servicio devolvió un error.',
                    'ciudad' => 'error',
                    'pais' => null,
                    'latitud' => null,
                    'longitud' => null
                ]);
            }
            
            // Actualizar todos los clicks de esa IP del mismo día
            $fechaInicio = $fechaClick . ' 00:00:00';
            $fechaFin = $fechaClick . ' 23:59:59';
            
            $clicksActualizados = Click::where('ip', $ip)
                ->whereBetween('created_at', [$fechaInicio, $fechaFin])
                ->update([
                    'ciudad' => $geoData['ciudad'],
                    'pais' => $geoData['pais'],
                    'latitud' => $geoData['latitud'] ? round($geoData['latitud'], 7) : null,
                    'longitud' => $geoData['longitud'] ? round($geoData['longitud'], 7) : null,
                ]);
            
            Log::info('IP regeolocalizada correctamente', [
                'ip' => $ip,
                'fecha' => $fechaClick,
                'clicks_actualizados' => $clicksActualizados,
                'ciudad' => $geoData['ciudad'],
                'pais' => $geoData['pais']
            ]);
            
            return response()->json([
                'success' => true,
                'message' => "Geolocalización encontrada correctamente. Se actualizaron {$clicksActualizados} click(s) del mismo día.",
                'ciudad' => $geoData['ciudad'],
                'pais' => $geoData['pais'],
                'latitud' => $geoData['latitud'] ? round($geoData['latitud'], 7) : null,
                'longitud' => $geoData['longitud'] ? round($geoData['longitud'], 7) : null,
                'clicks_actualizados' => $clicksActualizados
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error al regeolocalizar IP', [
                'ip' => $request->input('ip'),
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar la geolocalización: ' . $e->getMessage()
            ], 500);
        }
    }
}
