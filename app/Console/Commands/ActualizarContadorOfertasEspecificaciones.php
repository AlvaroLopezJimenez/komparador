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
            
            // Inicializar contadores para cada sublínea
            $contadores = [];
            
            // Recorrer cada línea principal del producto
            foreach ($especificaciones as $lineaId => $sublineas) {
                if (!is_array($sublineas)) {
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
                    
                    // Contar ofertas que coinciden con esta sublínea
                    $count = 0;
                    
                    foreach ($ofertasActivas as $oferta) {
                        $ofertaEspecificaciones = $oferta->especificaciones_internas;
                        
                        if (!$ofertaEspecificaciones || !is_array($ofertaEspecificaciones)) {
                            continue;
                        }
                        
                        // Verificar si la oferta tiene esta línea principal
                        if (!isset($ofertaEspecificaciones[$lineaId])) {
                            continue;
                        }
                        
                        $ofertaSublineas = $ofertaEspecificaciones[$lineaId];
                        
                        // Manejar array de IDs o array de objetos
                        if (!is_array($ofertaSublineas)) {
                            $ofertaSublineas = [$ofertaSublineas];
                        }
                        
                        // Verificar si alguna sublínea de la oferta coincide
                        foreach ($ofertaSublineas as $ofertaSublinea) {
                            $ofertaSublineaId = null;
                            
                            if (is_array($ofertaSublinea) && isset($ofertaSublinea['id'])) {
                                $ofertaSublineaId = $ofertaSublinea['id'];
                            } elseif (is_string($ofertaSublinea) || is_numeric($ofertaSublinea)) {
                                $ofertaSublineaId = $ofertaSublinea;
                            }
                            
                            if ($ofertaSublineaId && (string)$ofertaSublineaId === (string)$sublineaId) {
                                $count++;
                                break; // Solo contar una vez por oferta
                            }
                        }
                    }
                    
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








