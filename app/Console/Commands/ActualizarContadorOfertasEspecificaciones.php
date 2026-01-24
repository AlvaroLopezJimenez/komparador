<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Producto;
use App\Models\OfertaProducto;
use Carbon\Carbon;

class ActualizarContadorOfertasEspecificaciones extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ofertas:actualizar-contador-especificaciones';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Actualiza el contador de ofertas disponibles para cada especificación interna de los productos';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Iniciando actualización de contadores de ofertas por especificaciones...');
        
        $ahora = Carbon::now();
        
        // Obtener productos con especificaciones internas configuradas
        $productos = Producto::whereNotNull('categoria_id_especificaciones_internas')
            ->whereNotNull('categoria_especificaciones_internas_elegidas')
            ->where('categoria_especificaciones_internas_elegidas', '!=', 'null')
            ->where('categoria_especificaciones_internas_elegidas', '!=', '')
            ->get();
        
        $this->info("Encontrados {$productos->count()} productos con especificaciones internas.");
        
        $procesados = 0;
        $actualizados = 0;
        
        foreach ($productos as $producto) {
            $especificaciones = $producto->categoria_especificaciones_internas_elegidas;
            
            // Si es string, intentar decodificar JSON
            if (is_string($especificaciones)) {
                $especificaciones = json_decode($especificaciones, true);
            }
            
            if (!$especificaciones || !is_array($especificaciones) || empty($especificaciones)) {
                continue;
            }

            // Reparar metacampos que pudieron quedar corrompidos (por versiones anteriores del comando):
            // - _formatos: { lineaId: {id: "texto", c: 0} } -> { lineaId: "texto" }
            // - _orden/_columnas: [ {id: "x", c: 0}, ... ] -> [ "x", ... ]
            if (isset($especificaciones['_formatos']) && is_array($especificaciones['_formatos'])) {
                foreach ($especificaciones['_formatos'] as $k => $v) {
                    if (is_array($v) && isset($v['id']) && is_string($v['id'])) {
                        $especificaciones['_formatos'][$k] = $v['id'];
                    } elseif (!is_string($v)) {
                        // Si no es string ni objeto con id, limpiar para evitar errores en vistas
                        unset($especificaciones['_formatos'][$k]);
                    }
                }
            }

            $normalizarListaIds = function ($arr) {
                if (!is_array($arr)) {
                    return [];
                }
                $out = [];
                foreach ($arr as $v) {
                    if (is_array($v) && isset($v['id'])) {
                        $v = $v['id'];
                    }
                    if (is_string($v) || is_numeric($v)) {
                        $out[] = (string) $v;
                    }
                }
                return $out;
            };

            if (isset($especificaciones['_orden'])) {
                $especificaciones['_orden'] = $normalizarListaIds($especificaciones['_orden']);
            }
            if (isset($especificaciones['_columnas'])) {
                $especificaciones['_columnas'] = $normalizarListaIds($especificaciones['_columnas']);
            }
            
            // Obtener ofertas activas del producto
            $ofertasActivas = OfertaProducto::where('producto_id', $producto->id)
                ->where('mostrar', 'si')
                ->whereHas('tienda', function($query) {
                    $query->where('mostrar_tienda', 'si');
                })
                ->where(function($query) use ($ahora) {
                    $query->whereNull('fecha_inicio')
                          ->orWhere('fecha_inicio', '<=', $ahora);
                })
                ->where(function($query) use ($ahora) {
                    $query->whereNull('fecha_final')
                          ->orWhere('fecha_final', '>=', $ahora);
                })
                ->get();

            // Construir un mapa de conteos por línea/sublinea basado en especificaciones_internas de las ofertas.
            // Importante: si una oferta NO tiene especificaciones_internas para una línea, no podemos concluir c=0,
            // así que para esas líneas dejaremos el campo 'c' sin establecer (o lo eliminaremos si ya existía).
            $conteosPorLineaSublinea = []; // [lineaIdStr => [sublineaIdStr => count]]
            foreach ($ofertasActivas as $oferta) {
                $especificacionesOferta = $oferta->especificaciones_internas;
                if (!$especificacionesOferta || !is_array($especificacionesOferta)) {
                    continue;
                }

                foreach ($especificacionesOferta as $lineaOfertaId => $ofertaLinea) {
                    $lineaOfertaIdStr = is_string($lineaOfertaId) ? $lineaOfertaId : (string) $lineaOfertaId;
                    if ($lineaOfertaIdStr !== '' && substr($lineaOfertaIdStr, 0, 1) === '_') {
                        continue;
                    }

                    $ofertaSublineas = is_array($ofertaLinea) ? $ofertaLinea : [$ofertaLinea];
                    $seen = [];
                    foreach ($ofertaSublineas as $ofertaSublinea) {
                        $ofertaSublineaId = null;
                        if (is_array($ofertaSublinea) && isset($ofertaSublinea['id'])) {
                            $ofertaSublineaId = $ofertaSublinea['id'];
                        } elseif (is_string($ofertaSublinea) || is_numeric($ofertaSublinea)) {
                            $ofertaSublineaId = $ofertaSublinea;
                        }
                        if ($ofertaSublineaId === null || $ofertaSublineaId === '') {
                            continue;
                        }
                        $seen[(string) $ofertaSublineaId] = true;
                    }

                    foreach (array_keys($seen) as $sublineaIdStr) {
                        if (!isset($conteosPorLineaSublinea[$lineaOfertaIdStr])) {
                            $conteosPorLineaSublinea[$lineaOfertaIdStr] = [];
                        }
                        $conteosPorLineaSublinea[$lineaOfertaIdStr][$sublineaIdStr] = ($conteosPorLineaSublinea[$lineaOfertaIdStr][$sublineaIdStr] ?? 0) + 1;
                    }
                }
            }
            
            // Inicializar contadores para cada sublínea
            $contadores = [];
            
            // Recorrer cada línea principal del producto
            foreach ($especificaciones as $lineaId => $sublineas) {
                // Ignorar metacampos (_formatos, _orden, _columnas, _producto, etc.)
                $lineaIdStr = is_string($lineaId) ? $lineaId : (string) $lineaId;
                // Evitar str_starts_with() para compatibilidad con entornos antiguos (cron)
                if ($lineaIdStr !== '' && substr($lineaIdStr, 0, 1) === '_') {
                    continue;
                }

                if (!is_array($sublineas)) {
                    continue;
                }

                // Solo procesar arrays numéricos (listas de sublíneas). Si es asociativo, saltar.
                $keys = array_keys($sublineas);
                $isNumericArray = ($keys === range(0, count($sublineas) - 1));
                if (!$isNumericArray) {
                    continue;
                }

                // Si ninguna oferta tiene especificaciones para esta línea, NO establecemos 'c' (y lo borramos si existía).
                // Esto evita que los filtros de categorías se queden a 0 cuando las ofertas no están etiquetadas.
                if (!isset($conteosPorLineaSublinea[$lineaIdStr])) {
                    foreach ($sublineas as $idx => $sublinea) {
                        if (is_array($sublinea) && array_key_exists('c', $sublinea)) {
                            unset($especificaciones[$lineaId][$idx]['c']);
                        }
                    }
                    continue;
                }
                
                // Inicializar contadores para esta línea
                if (!isset($contadores[$lineaId])) {
                    $contadores[$lineaId] = [];
                }
                
                // Recorrer cada sublínea del producto
                foreach ($sublineas as $index => $sublinea) {
                    $sublineaId = null;
                    
                    // Manejar estructura optimizada {id, m, o} o estructura antigua (solo ID)
                    if (is_array($sublinea) && isset($sublinea['id'])) {
                        $sublineaId = $sublinea['id'];
                    } elseif (is_string($sublinea) || is_numeric($sublinea)) {
                        $sublineaId = $sublinea;
                    }
                    
                    if (!$sublineaId) {
                        continue;
                    }
                    
                    // Contar ofertas que coinciden con esta sublínea (usando el mapa precomputado)
                    $sublineaIdStr = (string) $sublineaId;
                    $count = $conteosPorLineaSublinea[$lineaIdStr][$sublineaIdStr] ?? 0;
                    
                    // Actualizar el contador en la estructura
                    if (is_array($sublinea)) {
                        // Estructura optimizada: añadir campo 'c' (count) preservando m y o si existen
                        $especificaciones[$lineaId][$index]['c'] = $count;
                        // Asegurar que id esté presente
                        if (!isset($especificaciones[$lineaId][$index]['id'])) {
                            $especificaciones[$lineaId][$index]['id'] = $sublineaId;
                        }
                    } else {
                        // Estructura antigua: convertir a objeto con id y count
                        $especificaciones[$lineaId][$index] = [
                            'id' => $sublineaId,
                            'c' => $count
                        ];
                    }
                }
            }
            
            // Guardar el producto actualizado
            $producto->categoria_especificaciones_internas_elegidas = $especificaciones;
            $producto->save();
            
            $procesados++;
            if ($procesados % 100 === 0) {
                $this->info("Procesados {$procesados} productos...");
            }
        }
        
        $this->info("Proceso completado. Total procesados: {$procesados} productos.");
        
        return Command::SUCCESS;
    }
}








