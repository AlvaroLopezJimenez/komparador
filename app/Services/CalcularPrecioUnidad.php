<?php

namespace App\Services;

class CalcularPrecioUnidad
{
    /**
     * Calcula el precio por unidad según la unidad de medida del producto
     * 
     * @param string $unidadDeMedida La unidad de medida del producto (total, unidad, kilos, litros, unidadMilesima, unidadUnica, 800gramos, 100ml)
     * @param float $precioTotal El precio total de la oferta
     * @param float $unidades El número de unidades de la oferta
     * @return float|null El precio por unidad calculado, o null si hay error
     */
    public function calcular(string $unidadDeMedida, float $precioTotal, float $unidades): ?float
    {
        // Validar que las unidades sean mayores que 0 para evitar división por cero
        if ($unidades <= 0) {
            return null;
        }

        $precioUnidad = null;

        // Para 800 gramos: el precio por unidad es (precio_total / unidades) * 0.8
        // Ejemplo: precio_total=20, unidades=1.6 -> precio_unidad = (20/1.6) * 0.8 = 10
        if ($unidadDeMedida === '800gramos') {
            $precioUnidad = ($precioTotal / $unidades) * 0.8;
        }
        // Para 100 ml: el precio por unidad es (precio_total / unidades) * 0.1
        // Ejemplo: precio_total=20, unidades=2 -> precio_unidad = (20/2) * 0.1 = 1
        elseif ($unidadDeMedida === '100ml') {
            $precioUnidad = ($precioTotal / $unidades) * 0.1;
        }
        // Para todas las demás unidades de medida (total, unidad, kilos, litros, unidadMilesima, unidadUnica)
        // El cálculo es simplemente precio_total / unidades
        else {
            $precioUnidad = $precioTotal / $unidades;
        }

        // Aplicar redondeo según la unidad de medida
        if ($unidadDeMedida === 'unidadMilesima') {
            return round($precioUnidad, 3);
        }

        // Para el resto, redondear a 2 decimales
        return round($precioUnidad, 2);
    }
}

