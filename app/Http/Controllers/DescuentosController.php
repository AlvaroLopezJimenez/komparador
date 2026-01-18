<?php

namespace App\Http\Controllers;

use App\Models\OfertaProducto;
use Illuminate\Support\Facades\Log;

class DescuentosController extends Controller
{
    /**
     * Aplica el descuento correspondiente a una oferta según su campo 'descuentos'
     * 
     * @param OfertaProducto $oferta La oferta original sin descuentos aplicados
     * @return OfertaProducto La oferta con descuentos aplicados (unidades, precio_total y precio_unidad ajustados)
     */
    public function aplicarDescuento(OfertaProducto $oferta): OfertaProducto
    {

        // Si la oferta está asociada a un chollo, respetar sus valores originales
        if (!is_null($oferta->chollo_id)) {
            return $oferta;
        }

        // Si no tiene descuento, devolver la oferta sin modificar
        if (empty($oferta->descuentos)) {
            return $oferta;
        }

        // Crear una copia de la oferta para no modificar el original en BD
        $ofertaConDescuento = clone $oferta;
        
        // Asegurar que se mantengan los campos importantes y las relaciones
        $ofertaConDescuento->tienda_id = $oferta->tienda_id;
        $ofertaConDescuento->descuentos = $oferta->descuentos;
        
        // Mantener la relación tienda si está cargada
        if ($oferta->relationLoaded('tienda') && $oferta->tienda) {
            $ofertaConDescuento->setRelation('tienda', $oferta->tienda);
        }
        
        Log::info('DescuentosController - Aplicando descuento:', [
            'oferta_id' => $oferta->id,
            'descuento' => $oferta->descuentos,
            'unidades_originales' => $oferta->unidades,
            'precio_total_original' => $oferta->precio_total,
            'precio_unidad_original' => $oferta->precio_unidad
        ]);

        // Verificar si es un cupón (formato: cupon;codigo;cantidad, cupon;valor o cupon;%valor)
        if (strpos($oferta->descuentos, 'cupon;') === 0) {
            $descuentoString = $oferta->descuentos;
            
            // Verificar si es cupón con porcentaje (cupon;%15)
            if (strpos($descuentoString, 'cupon;%') === 0) {
                // Extraer el porcentaje del cupón (ej: cupon;%15 -> 15)
                $porcentajeCupon = (float)str_replace('cupon;%', '', $descuentoString);
                
                // Aplicar descuento porcentual: precio_total * (1 - porcentaje/100)
                $precioTotalConDescuento = $oferta->precio_total * (1 - $porcentajeCupon / 100);
                
                $ofertaConDescuento->precio_total = $precioTotalConDescuento;
                // Calcular precio_unidad con 3 decimales
                $ofertaConDescuento->precio_unidad = $oferta->unidades > 0 
                    ? round($precioTotalConDescuento / $oferta->unidades, 3) 
                    : 0;
                
                Log::info('DescuentosController - Cupón porcentaje aplicado:', [
                    'oferta_id' => $oferta->id,
                    'porcentaje_cupon' => $porcentajeCupon,
                    'precio_original' => $oferta->precio_total,
                    'precio_con_cupon' => $precioTotalConDescuento,
                    'precio_unidad' => $ofertaConDescuento->precio_unidad
                ]);
            } else {
                // Parsear formato: cupon;codigo;cantidad o cupon;cantidad (compatibilidad)
                $partes = explode(';', $descuentoString);
                
                if (count($partes) === 3) {
                    // Formato nuevo: cupon;codigo;cantidad
                    $codigoCupon = $partes[1];
                    $valorCupon = (float)$partes[2];
                } else {
                    // Formato antiguo: cupon;cantidad (solo cantidad, sin código)
                    $valorCupon = (float)str_replace('cupon;', '', $descuentoString);
                    $codigoCupon = null;
                }
                
                // Aplicar descuento del cupón: restar el valor del cupón al precio total
                $precioTotalConDescuento = max(0, $oferta->precio_total - $valorCupon);
                
                $ofertaConDescuento->precio_total = $precioTotalConDescuento;
                // Calcular precio_unidad con 3 decimales
                $ofertaConDescuento->precio_unidad = $oferta->unidades > 0 
                    ? round($precioTotalConDescuento / $oferta->unidades, 3) 
                    : 0;
                
                Log::info('DescuentosController - Cupón cantidad fija aplicado:', [
                    'oferta_id' => $oferta->id,
                    'codigo_cupon' => $codigoCupon,
                    'valor_cupon' => $valorCupon,
                    'precio_original' => $oferta->precio_total,
                    'precio_con_cupon' => $precioTotalConDescuento,
                    'precio_unidad' => $ofertaConDescuento->precio_unidad
                ]);
            }
        } elseif (strpos($oferta->descuentos, 'CholloTienda1SoloCuponQueAplicaDescuento;') === 0) {
            // Procesar cupón de tipo "1 Solo cupón" que aplica descuento al precio total
            $descuentoString = $oferta->descuentos;
            $partes = explode(';', $descuentoString);
            
            // Buscar los valores en el string
            $descuento = null;
            $tipoDescuento = 'euros'; // Por defecto euros
            $cupon = null;
            
            for ($i = 0; $i < count($partes); $i++) {
                if ($partes[$i] === 'descuento' && isset($partes[$i + 1])) {
                    $descuento = (float)$partes[$i + 1];
                }
                if ($partes[$i] === 'tipo_descuento' && isset($partes[$i + 1])) {
                    $tipoDescuento = $partes[$i + 1];
                }
                if ($partes[$i] === 'cupon' && isset($partes[$i + 1])) {
                    $cupon = $partes[$i + 1];
                }
            }
            
            if ($descuento !== null && $descuento > 0) {
                // Crear una copia de la oferta para no modificar el original en BD
                $ofertaConDescuento = clone $oferta;
                
                // Asegurar que se mantengan los campos importantes y las relaciones
                $ofertaConDescuento->tienda_id = $oferta->tienda_id;
                $ofertaConDescuento->descuentos = $oferta->descuentos;
                
                // Mantener la relación tienda si está cargada
                if ($oferta->relationLoaded('tienda') && $oferta->tienda) {
                    $ofertaConDescuento->setRelation('tienda', $oferta->tienda);
                }
                
                $precioOriginal = $oferta->precio_total;
                
                // Aplicar descuento según el tipo
                if ($tipoDescuento === 'porcentaje') {
                    // Descuento porcentual: precio_total * (1 - porcentaje/100)
                    $precioTotalConDescuento = $precioOriginal * (1 - $descuento / 100);
                } else {
                    // Descuento en euros: restar directamente
                    $precioTotalConDescuento = max(0, $precioOriginal - $descuento);
                }
                
                $ofertaConDescuento->precio_total = $precioTotalConDescuento;
                
                // Recalcular precio_unidad con 3 decimales
                $ofertaConDescuento->precio_unidad = $oferta->unidades > 0 
                    ? round($precioTotalConDescuento / $oferta->unidades, 3) 
                    : 0;
                
                Log::info('DescuentosController - CholloTienda1SoloCupon aplicado:', [
                    'oferta_id' => $oferta->id,
                    'tipo_descuento' => $tipoDescuento,
                    'descuento' => $descuento,
                    'precio_original' => $precioOriginal,
                    'precio_con_descuento' => $precioTotalConDescuento,
                    'precio_unidad' => $ofertaConDescuento->precio_unidad
                ]);
                
                return $ofertaConDescuento;
            }
            
            // Si no se pudo procesar, devolver la oferta sin modificar
            return $oferta;
        } elseif (strpos($oferta->descuentos, 'CholloTienda;') === 0) {
            // Los cupones de CholloTienda no se procesan aquí, solo se guardan en el campo descuentos
            // para mostrarlos en la vista. No modificar precios ni unidades.
            return $oferta;
        } else {
            // Manejar otros tipos de descuentos existentes
            switch ($oferta->descuentos) {
                case '3x2':
                    // Para 3x2: Compras 3 packs, pagas 2 packs
                    // Ejemplo: 132 unidades a 21,99€ → con 3x2 recibes 396 uds (3 packs) y pagas 43,98€ (2 packs)
                    $ofertaConDescuento->unidades = $oferta->unidades * 3;
                    $ofertaConDescuento->precio_total = $oferta->precio_total * 2; // Pagas 2 packs
                    $ofertaConDescuento->precio_unidad = ($oferta->precio_total * 2) / ($oferta->unidades * 3);
                    break;

                case '2x1 - SoloCarrefour':
                    // Para 2x1: Compras 2 packs, pagas 1 pack
                    // Ejemplo: 132 unidades a 21,99€ → con 2x1 recibes 264 uds (2 packs) y pagas 21,99€ (1 pack)
                    $ofertaConDescuento->unidades = $oferta->unidades * 2;
                    $ofertaConDescuento->precio_total = $oferta->precio_total; // Pagas 1 pack
                    $ofertaConDescuento->precio_unidad = $oferta->precio_total / ($oferta->unidades * 2);
                    break;
                case '2a al 50 - cheque - SoloCarrefour':
                    // Funciona igual que el 2x1 pero el descuento del segundo producto se acumula en un cheque
                    $ofertaConDescuento->unidades = $oferta->unidades * 2;
                    $ofertaConDescuento->precio_total = $oferta->precio_total * 1.5;
                    $ofertaConDescuento->precio_unidad = $ofertaConDescuento->precio_total / $ofertaConDescuento->unidades;
                    break;

                case '2a al 70':
                    // Para 2ª al 70%: La segunda unidad tiene 70% de descuento (pagas solo el 30%)
                    // Ejemplo: 1 unidad a 10€ → con 2ª al 70% tienes 2 unidades
                    // Precio total = 10€ + (10€ * 0.30) = 10€ + 3€ = 13€
                    // Precio por unidad promedio = 13€ / 2 = 6.5€
                    
                    $precioUnidadOriginal = $oferta->precio_total / $oferta->unidades;
                    $precioSegundaUnidad = $precioUnidadOriginal * 0.30; // La 2ª unidad al 30% (70% descuento)
                    
                    $ofertaConDescuento->unidades = $oferta->unidades * 2;
                    $ofertaConDescuento->precio_total = $oferta->precio_total + ($precioUnidadOriginal * $oferta->unidades * 0.30);
                    $ofertaConDescuento->precio_unidad = $ofertaConDescuento->precio_total / $ofertaConDescuento->unidades;
                    break;

                case '2a al 50':
                    // Para 2ª al 50%: La segunda unidad tiene 50% de descuento (pagas solo el 50%)
                    // Ejemplo: 1 unidad a 10€ → con 2ª al 50% tienes 2 unidades
                    // Precio total = 10€ + (10€ * 0.50) = 10€ + 5€ = 15€
                    // Precio por unidad promedio = 15€ / 2 = 7.5€
                    
                    $precioUnidadOriginal = $oferta->precio_total / $oferta->unidades;
                    $precioSegundaUnidad = $precioUnidadOriginal * 0.50; // La 2ª unidad al 50% (50% descuento)
                    
                    $ofertaConDescuento->unidades = $oferta->unidades * 2;
                    $ofertaConDescuento->precio_total = $oferta->precio_total + ($precioUnidadOriginal * $oferta->unidades * 0.50);
                    $ofertaConDescuento->precio_unidad = $ofertaConDescuento->precio_total / $ofertaConDescuento->unidades;
                    break;

                case 'cupon':
                    // Para cupones: el precio ya está ajustado, no hacer nada
                    // El scraper ya aplicó el descuento al precio_total
                    break;

                case 'rebaja':
                case 'promocion':
                case 'oferta_especial':
                    // Para estos tipos: no requieren cálculos especiales
                    // Son solo informativos
                    break;

                default:
                    Log::warning('DescuentosController - Tipo de descuento desconocido:', [
                        'oferta_id' => $oferta->id,
                        'descuento' => $oferta->descuentos
                    ]);
                    break;
            }
        }

        Log::info('DescuentosController - Descuento aplicado:', [
            'oferta_id' => $oferta->id,
            'unidades_finales' => $ofertaConDescuento->unidades,
            'precio_total_final' => $ofertaConDescuento->precio_total,
            'precio_unidad_final' => $ofertaConDescuento->precio_unidad
        ]);

        return $ofertaConDescuento;
    }

    /**
     * Aplica descuentos a una colección de ofertas y las ordena por precio_unidad
     * 
     * @param \Illuminate\Database\Eloquent\Collection $ofertas Colección de ofertas
     * @return \Illuminate\Database\Eloquent\Collection Ofertas con descuentos aplicados y ordenadas
     */
    public function aplicarDescuentosYOrdenar($ofertas)
    {
        $ofertasConDescuentos = $ofertas->map(function($oferta) {
            return $this->aplicarDescuento($oferta);
        });

        // Ordenar por precio_unidad (menor a mayor)
        return $ofertasConDescuentos->sortBy('precio_unidad')->values();
    }

    /**
     * Obtiene el precio por unidad más bajo de una colección de ofertas después de aplicar descuentos
     * 
     * @param \Illuminate\Database\Eloquent\Collection $ofertas Colección de ofertas
     * @return float|null Precio por unidad más bajo
     */
    public function obtenerPrecioUnidadMasBajo($ofertas): ?float
    {
        if ($ofertas->isEmpty()) {
            return null;
        }

        $ofertasConDescuentos = $this->aplicarDescuentosYOrdenar($ofertas);
        
        return $ofertasConDescuentos->first()->precio_unidad ?? null;
    }

}

