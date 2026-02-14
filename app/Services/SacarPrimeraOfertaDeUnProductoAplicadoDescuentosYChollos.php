<?php

namespace App\Services;

use App\Models\Producto;
use App\Http\Controllers\DescuentosController;
use App\Services\CalcularPrecioUnidad;

class SacarPrimeraOfertaDeUnProductoAplicadoDescuentosYChollos
{
    /**
     * Obtiene la primera oferta (más barata) de un producto aplicando:
     * - Filtros de mostrar='si' y tienda mostrar_tienda='si'
     * - Chollos de tipo tienda activos
     * - DescuentosController para aplicar descuentos
     * - Recalculo de precio_unidad si es unidadMilesima
     * 
     * @param Producto $producto El producto del cual obtener la oferta más barata
     * @return \App\Models\OfertaProducto|null La oferta más barata con descuentos aplicados, o null si no hay ofertas
     */
    public function obtener(Producto $producto)
    {
        // Obtener ofertas originales
        // Todas las ofertas deben cumplir: mostrar='si' y mostrar_tienda='si'
        // Adicionalmente, las ofertas con chollo_id deben cumplir:
        // - fecha_inicio <= fecha_actual (ya ha comenzado)
        // - (fecha_final >= fecha_actual OR fecha_final IS NULL) (aún no ha terminado o no tiene fecha final)
        $ofertasOriginales = $producto->ofertas()->with('tienda')
            ->where('mostrar', 'si')
            ->whereHas('tienda', function($query) {
                $query->where('mostrar_tienda', 'si');
            })
            ->where(function($query) {
                // Ofertas sin chollo_id se incluyen normalmente
                $query->whereNull('chollo_id')
                      // Ofertas con chollo_id deben cumplir las condiciones de fechas
                      ->orWhere(function($q) {
                          $q->whereNotNull('chollo_id')
                            ->where('fecha_inicio', '<=', now())
                            ->where(function($q2) {
                                $q2->whereNull('fecha_final')
                                   ->orWhere('fecha_final', '>=', now());
                            });
                      });
            })
            ->get();
        
        if ($ofertasOriginales->isEmpty()) {
            return null;
        }
        
        // Buscar TODOS los chollos activos de tipo tienda (ordenados por más reciente primero)
        $chollosTienda = \App\Models\Chollo::where('tipo', 'tienda')
            ->where('finalizada', 'no')
            ->where('mostrar', 'si')
            ->where('fecha_inicio', '<=', now())
            ->where(function($query) {
                $query->whereNull('fecha_final')
                      ->orWhere('fecha_final', '>=', now());
            })
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->get();
        
        // Si hay chollos de tipo tienda activos, aplicar sus cupones a las ofertas correspondientes
        // Esto se hace ANTES de pasar por DescuentosController para que pueda procesar los descuentos
        if ($chollosTienda->isNotEmpty()) {
            // Crear un mapa de chollos por tienda_id para acceso rápido
            // Si hay múltiples chollos para la misma tienda, se usa el más reciente (ya están ordenados)
            $chollosPorTienda = [];
            foreach ($chollosTienda as $cholloTienda) {
                $tiendaId = $cholloTienda->tienda_id;
                // Solo guardar el primero que encontremos para cada tienda (el más reciente)
                if (!isset($chollosPorTienda[$tiendaId])) {
                    $chollosPorTienda[$tiendaId] = $cholloTienda;
                }
            }
            
            $ofertasOriginales = $ofertasOriginales->map(function($oferta) use ($chollosPorTienda) {
                $ofertaTiendaId = $oferta->tienda_id ?? ($oferta->getAttribute('tienda_id') ?? null);
                
                // Buscar si hay un chollo activo para esta tienda específica
                if ($ofertaTiendaId && isset($chollosPorTienda[$ofertaTiendaId])) {
                    $cholloTienda = $chollosPorTienda[$ofertaTiendaId];
                    
                    // Decodificar los cupones desde descripcion_interna
                    $descripcionInterna = json_decode($cholloTienda->descripcion_interna, true);
                    $cupones = $descripcionInterna['cupones'] ?? [];
                    $tipoChollo = $descripcionInterna['tipo'] ?? 'cupon'; // 'cupon' o '1_solo_cupon'
                    
                    if (!empty($cupones)) {
                        // Solo aplicar si el campo descuentos está vacío
                        $descuentosActual = $oferta->descuentos ?? ($oferta->getAttribute('descuentos') ?? null);
                        
                        if (empty($descuentosActual)) {
                            if ($tipoChollo === '1_solo_cupon') {
                                // Formato para "1 Solo cupón": CholloTienda1SoloCuponQueAplicaDescuento;tienda_id;{$tiendaId};descuento;{$cupon['descuento']};tipo_descuento;{$cupon['tipo_descuento']};cupon;{$cupon['cupon']}
                                $cupon = $cupones[0] ?? null;
                                if ($cupon && isset($cupon['descuento']) && isset($cupon['cupon'])) {
                                    $tipoDescuento = $cupon['tipo_descuento'] ?? 'euros';
                                    $descuentosInfo = "CholloTienda1SoloCuponQueAplicaDescuento;tienda_id;{$cholloTienda->tienda_id};descuento;{$cupon['descuento']};tipo_descuento;{$tipoDescuento};cupon;{$cupon['cupon']}";
                                    
                                    $oferta->descuentos = $descuentosInfo;
                                    $oferta->setAttribute('descuentos', $descuentosInfo);
                                }
                            } else {
                                // Formato original: CholloTienda;tienda_id;{$tiendaId};descuento;{$cupon['descuento']};sobrePrecioTotal;{$cupon['sobrePrecioTotal']};cupon;{$cupon['cupon']}
                                $descuentosInfo = "CholloTienda;tienda_id;{$cholloTienda->tienda_id}";
                                
                                // Añadir información de todos los cupones disponibles
                                foreach ($cupones as $cupon) {
                                    if (isset($cupon['descuento']) && isset($cupon['sobrePrecioTotal']) && isset($cupon['cupon'])) {
                                        $descuentosInfo .= ";descuento;{$cupon['descuento']};sobrePrecioTotal;{$cupon['sobrePrecioTotal']};cupon;{$cupon['cupon']}";
                                    }
                                }
                                
                                $oferta->descuentos = $descuentosInfo;
                                $oferta->setAttribute('descuentos', $descuentosInfo);
                            }
                            
                            // También asegurar que tienda_id esté disponible
                            if (!isset($oferta->tienda_id)) {
                                $oferta->tienda_id = $ofertaTiendaId;
                                $oferta->setAttribute('tienda_id', $ofertaTiendaId);
                            }
                        }
                    }
                }
                return $oferta;
            })->values();
        }
        
        // Ahora pasar todas las ofertas por DescuentosController para aplicar descuentos
        // Esto procesará tanto los descuentos normales como los de CholloTienda1SoloCuponQueAplicaDescuento
        // Las ofertas que tienen CholloTienda;... se devuelven sin modificar
        // Las ofertas que tienen CholloTienda1SoloCuponQueAplicaDescuento;... se les aplica el descuento
        $descuentosController = new DescuentosController();
        $ofertas = $descuentosController->aplicarDescuentosYOrdenar($ofertasOriginales);
        
        // Aplicar gastos de envío y recalcular precio_unidad según unidad de medida
        $ofertas = $this->aplicarEnvioYRecalcularPrecioUnidad($ofertas, $producto);
        
        // Reordenar las ofertas después de recalcular precios
        $ofertas = $ofertas->sortBy('precio_unidad')->values();
        
        // Devolver la primera oferta (la más barata)
        return $ofertas->first();
    }

    /**
     * Obtiene todas las ofertas de un producto ordenadas por precio_unidad (más barata primero)
     * Aplica los mismos descuentos y chollos que el método obtener()
     * 
     * @param Producto $producto El producto del cual obtener las ofertas
     * @return \Illuminate\Database\Eloquent\Collection Colección de ofertas ordenadas por precio_unidad
     */
    public function obtenerTodas(Producto $producto)
    {
        // Obtener ofertas originales
        // Todas las ofertas deben cumplir: mostrar='si' y mostrar_tienda='si'
        // Adicionalmente, las ofertas con chollo_id deben cumplir:
        // - fecha_inicio <= fecha_actual (ya ha comenzado)
        // - (fecha_final >= fecha_actual OR fecha_final IS NULL) (aún no ha terminado o no tiene fecha final)
        $ofertasOriginales = $producto->ofertas()->with('tienda')
            ->where('mostrar', 'si')
            ->whereHas('tienda', function($query) {
                $query->where('mostrar_tienda', 'si');
            })
            ->where(function($query) {
                // Ofertas sin chollo_id se incluyen normalmente (solo verificando fecha_inicio si existe)
                $query->whereNull('chollo_id')
                      ->where(function($q) {
                          $q->whereNull('fecha_inicio')
                            ->orWhere('fecha_inicio', '<=', now());
                      })
                      // Ofertas con chollo_id deben cumplir las condiciones de fechas
                      ->orWhere(function($q) {
                          $q->whereNotNull('chollo_id')
                            ->where(function($q2) {
                                $q2->whereNull('fecha_inicio')
                                   ->orWhere('fecha_inicio', '<=', now());
                            })
                            ->where(function($q2) {
                                $q2->whereNull('fecha_final')
                                   ->orWhere('fecha_final', '>=', now());
                            });
                      });
            })
            ->get();
        
        if ($ofertasOriginales->isEmpty()) {
            return collect();
        }
        
        // Buscar TODOS los chollos activos de tipo tienda (ordenados por más reciente primero)
        $chollosTienda = \App\Models\Chollo::where('tipo', 'tienda')
            ->where('finalizada', 'no')
            ->where('mostrar', 'si')
            ->where('fecha_inicio', '<=', now())
            ->where(function($query) {
                $query->whereNull('fecha_final')
                      ->orWhere('fecha_final', '>=', now());
            })
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->get();
        
        // Si hay chollos de tipo tienda activos, aplicar sus cupones a las ofertas correspondientes
        if ($chollosTienda->isNotEmpty()) {
            // Crear un mapa de chollos por tienda_id para acceso rápido
            // Si hay múltiples chollos para la misma tienda, se usa el más reciente (ya están ordenados)
            $chollosPorTienda = [];
            foreach ($chollosTienda as $cholloTienda) {
                $tiendaId = $cholloTienda->tienda_id;
                // Solo guardar el primero que encontremos para cada tienda (el más reciente)
                if (!isset($chollosPorTienda[$tiendaId])) {
                    $chollosPorTienda[$tiendaId] = $cholloTienda;
                }
            }
            
            $ofertasOriginales = $ofertasOriginales->map(function($oferta) use ($chollosPorTienda) {
                $ofertaTiendaId = $oferta->tienda_id ?? ($oferta->getAttribute('tienda_id') ?? null);
                
                // Buscar si hay un chollo activo para esta tienda específica
                if ($ofertaTiendaId && isset($chollosPorTienda[$ofertaTiendaId])) {
                    $cholloTienda = $chollosPorTienda[$ofertaTiendaId];
                    
                    $descripcionInterna = json_decode($cholloTienda->descripcion_interna, true);
                    $cupones = $descripcionInterna['cupones'] ?? [];
                    $tipoChollo = $descripcionInterna['tipo'] ?? 'cupon';
                    
                    if (!empty($cupones)) {
                        $descuentosActual = $oferta->descuentos ?? ($oferta->getAttribute('descuentos') ?? null);
                        
                        if (empty($descuentosActual)) {
                            if ($tipoChollo === '1_solo_cupon') {
                                $cupon = $cupones[0] ?? null;
                                if ($cupon && isset($cupon['descuento']) && isset($cupon['cupon'])) {
                                    $tipoDescuento = $cupon['tipo_descuento'] ?? 'euros';
                                    $descuentosInfo = "CholloTienda1SoloCuponQueAplicaDescuento;tienda_id;{$cholloTienda->tienda_id};descuento;{$cupon['descuento']};tipo_descuento;{$tipoDescuento};cupon;{$cupon['cupon']}";
                                    
                                    $oferta->descuentos = $descuentosInfo;
                                    $oferta->setAttribute('descuentos', $descuentosInfo);
                                }
                            } else {
                                $descuentosInfo = "CholloTienda;tienda_id;{$cholloTienda->tienda_id}";
                                
                                foreach ($cupones as $cupon) {
                                    if (isset($cupon['descuento']) && isset($cupon['sobrePrecioTotal']) && isset($cupon['cupon'])) {
                                        $descuentosInfo .= ";descuento;{$cupon['descuento']};sobrePrecioTotal;{$cupon['sobrePrecioTotal']};cupon;{$cupon['cupon']}";
                                    }
                                }
                                
                                $oferta->descuentos = $descuentosInfo;
                                $oferta->setAttribute('descuentos', $descuentosInfo);
                            }
                            
                            if (!isset($oferta->tienda_id)) {
                                $oferta->tienda_id = $ofertaTiendaId;
                                $oferta->setAttribute('tienda_id', $ofertaTiendaId);
                            }
                        }
                    }
                }
                return $oferta;
            })->values();
        }
        
        // Pasar todas las ofertas por DescuentosController para aplicar descuentos
        $descuentosController = new DescuentosController();
        $ofertas = $descuentosController->aplicarDescuentosYOrdenar($ofertasOriginales);
        
        // Aplicar gastos de envío y recalcular precio_unidad según unidad de medida
        $ofertas = $this->aplicarEnvioYRecalcularPrecioUnidad($ofertas, $producto);
        
        // Reordenar las ofertas después de recalcular precios
        $ofertas = $ofertas->sortBy('precio_unidad')->values();
        
        return $ofertas;
    }

    /**
     * Obtiene el ID de la oferta más barata de un producto
     * Útil cuando solo necesitas el ID para consultas posteriores
     * 
     * @param Producto $producto El producto del cual obtener el ID de la oferta más barata
     * @return int|null El ID de la oferta más barata, o null si no hay ofertas
     */
    public function obtenerId(Producto $producto)
    {
        $oferta = $this->obtener($producto);
        return $oferta ? $oferta->id : null;
    }

    /**
     * Aplica los gastos de envío al precio_total y recalcula el precio_unidad según la unidad de medida
     * 
     * @param \Illuminate\Support\Collection $ofertas Colección de ofertas
     * @param Producto $producto El producto asociado a las ofertas
     * @return \Illuminate\Support\Collection Colección de ofertas con precios recalculados
     */
    private function aplicarEnvioYRecalcularPrecioUnidad($ofertas, Producto $producto)
    {
        $unidadDeMedida = $producto->unidadDeMedida ?? 'unidad';
        $calcularPrecioUnidad = new CalcularPrecioUnidad();
        
        return $ofertas->map(function($oferta) use ($unidadDeMedida, $calcularPrecioUnidad) {
            // Obtener el valor de envío (puede ser null)
            $envio = $oferta->envio ?? ($oferta->getAttribute('envio') ?? null);
            $envio = $envio ? (float) $envio : 0;
            
            // Sumar el envío al precio_total si existe
            $precioTotalConEnvio = $oferta->precio_total + $envio;
            
            // Recalcular precio_unidad según la unidad de medida
            if ($unidadDeMedida === 'unidadUnica') {
                // Para unidadUnica: precio_unidad = precio_total + envio (sin dividir)
                $oferta->precio_unidad = round($precioTotalConEnvio, 2);
            } else {
                // Para las demás unidades de medida: usar el servicio CalcularPrecioUnidad
                // pero con el precio_total que incluye el envío
                if ($oferta->unidades > 0) {
                    $precioUnidadCalculado = $calcularPrecioUnidad->calcular(
                        $unidadDeMedida,
                        $precioTotalConEnvio,
                        $oferta->unidades
                    );
                    
                    if ($precioUnidadCalculado !== null) {
                        $oferta->precio_unidad = $precioUnidadCalculado;
                    }
                }
            }
            
            // Actualizar también el precio_total para reflejar el envío (solo para mostrar, no se guarda)
            $oferta->precio_total = $precioTotalConEnvio;
            
            return $oferta;
        });
    }
}

