<?php

namespace App\Helpers;

class FormatoHelper
{
    /**
     * Formatea unidades según la unidad de medida
     * 
     * @param float|int $unidades
     * @param string|null $unidadDeMedida
     * @return string
     */
    public static function formatearUnidades($unidades, $unidadDeMedida)
    {
        // Normalizar la unidad de medida (lowercase y sin espacios)
        $unidadNormalizada = strtolower(trim($unidadDeMedida ?? ''));
        
        if ($unidadNormalizada === 'unidades') {
            // Para unidades, mostrar solo el número entero sin decimales
            return (string) intval($unidades);
        } elseif (in_array($unidadNormalizada, ['kilos', 'litros'])) {
            // Para kilos y litros, mostrar con decimales necesarios (eliminar ceros finales)
            $formateado = number_format(floatval($unidades), 3, ',', '.');
            // Eliminar ceros finales y la coma si no hay decimales
            $formateado = rtrim($formateado, '0');
            $formateado = rtrim($formateado, ',');
            return $formateado;
        }
        // Por defecto, mostrar con 2 decimales
        return number_format(floatval($unidades), 2, ',', '.');
    }
}



