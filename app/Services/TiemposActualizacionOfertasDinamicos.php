<?php

namespace App\Services;

use App\Models\OfertaProducto;
use App\Models\HistoricoTiempoActualizacionPrecioOferta;
use Carbon\Carbon;

class TiemposActualizacionOfertasDinamicos
{
    // ============================================================================
    // CONFIGURACIÓN DEL NUEVO SISTEMA BASADO EN λ (LAMBDA) PONDERADO
    // ============================================================================
    
    const VENTANA_ANALISIS_DIAS = 14;
    // Ventana de análisis: solo últimos 7 días
    // Trabajamos todo en minutos para mayor precisión
    
    const MINIMO_REGISTROS_REQUERIDOS = 2;
    // Mínimo de registros necesarios para calcular λ
    // Si hay menos, se mantiene el intervalo actual
    
    const UMBRAL_CAMBIO_PORCENTAJE = 1.0;
    // Porcentaje mínimo de diferencia para considerar un cambio válido
    // Si la variación es > 1%, se marca como cambio (1), sino (0)
    
    const FACTOR_DECAY_EXPONENCIAL = 0.0003;
    // Factor k para el peso exponencial: peso = exp(-k * minutos_desde_ahora)
    // Valores más recientes tienen mucho más peso que los antiguos
    
    const FACTOR_C = 1.1;
    // Factor c en la fórmula: intervalo = 1 / (c * λ + epsilon)
    // Controla qué tan agresivo es el ajuste basado en la tasa de cambios
    
    const EPSILON = 0.0000001;
    // Valor epsilon para evitar división por cero
    // Muy pequeño, solo para estabilidad numérica
    
    const FACTOR_SUAVIZADO_ACTUAL = 0.8;
    // Porcentaje del intervalo actual que se mantiene (80%)
    
    const FACTOR_SUAVIZADO_NUEVO = 0.2;
    // Porcentaje del nuevo intervalo calculado que se aplica (20%)
    
    const FRECUENCIA_MINIMA_ABSOLUTA_MINUTOS = 15;
    // Límite absoluto mínimo de frecuencia (nunca menos de 15 minutos)
    // Se aplica ANTES de los límites de tienda
    
    /**
     * Calcula la frecuencia adaptativa basada en λ (lambda) ponderado exponencialmente
     * 
     * Sistema nuevo:
     * - Ventana de 7 días (no 30)
     * - Cálculo de tasa de cambios (λ) con pesos exponenciales
     * - Fórmula: intervalo = 1 / (c * λ + epsilon)
     * - Suavizado: 80% actual + 20% nuevo
     * - Respeta límites de tienda
     * 
     * @param int $ofertaId
     * @return int Frecuencia en minutos
     */
    public function calcularFrecuencia($ofertaId)
    {
        $oferta = OfertaProducto::with('tienda')->findOrFail($ofertaId);
        $frecuenciaActual = $oferta->frecuencia_actualizar_precio_minutos ?? 1440;
        
        // Obtener historial de los últimos 7 días (en minutos)
        $fechaLimite = Carbon::now()->subDays(self::VENTANA_ANALISIS_DIAS);
        $historial = HistoricoTiempoActualizacionPrecioOferta::where('oferta_id', $ofertaId)
            ->where('created_at', '>=', $fechaLimite)
            ->orderBy('created_at', 'asc') // Ordenar de más antiguo a más reciente
            ->get();
        
        // Si no hay suficiente historial, mantener frecuencia actual
        if ($historial->count() < self::MINIMO_REGISTROS_REQUERIDOS) {
            return $frecuenciaActual;
        }
        
        // Calcular λ ponderado
        $lambda = $this->calcularLambdaPonderado($historial);
        
        // Si no se pudo calcular λ (suma_tiempo_ponderado == 0), mantener actual
        if ($lambda === null) {
            return $frecuenciaActual;
        }
        
        // Calcular nuevo intervalo en minutos
        $intervaloCalculado = $this->calcularIntervaloDesdeLambda($lambda);
        
        // Aplicar suavizado: 80% actual + 20% nuevo
        $intervaloSuavizado = (self::FACTOR_SUAVIZADO_ACTUAL * $frecuenciaActual) 
                             + (self::FACTOR_SUAVIZADO_NUEVO * $intervaloCalculado);
        
        // Aplicar límite mínimo absoluto
        if ($intervaloSuavizado < self::FRECUENCIA_MINIMA_ABSOLUTA_MINUTOS) {
            $intervaloSuavizado = self::FRECUENCIA_MINIMA_ABSOLUTA_MINUTOS;
        }
        
        // Aplicar límites de tienda
        $tienda = $oferta->tienda;
        $frecuenciaMinimaTienda = $tienda->frecuencia_minima_minutos ?? self::FRECUENCIA_MINIMA_ABSOLUTA_MINUTOS;
        $frecuenciaMaximaTienda = $tienda->frecuencia_maxima_minutos ?? 10080; // 7 días por defecto
        
        // Aplicar límite mínimo de tienda
        if ($intervaloSuavizado < $frecuenciaMinimaTienda) {
            $intervaloSuavizado = $frecuenciaMinimaTienda;
        }
        
        // Aplicar límite máximo de tienda
        if ($intervaloSuavizado > $frecuenciaMaximaTienda) {
            $intervaloSuavizado = $frecuenciaMaximaTienda;
        }
        
        // Redondear y convertir a entero
        $intervaloFinal = (int) round($intervaloSuavizado);
        
        // Actualizar frecuencia en la oferta
        $oferta->update([
            'frecuencia_actualizar_precio_minutos' => $intervaloFinal
        ]);
        
        return $intervaloFinal;
    }
    
    /**
     * Calcula λ (lambda) ponderado exponencialmente
     * 
     * λ = suma_cambios_ponderados / suma_tiempo_ponderado
     * 
     * Unidad: cambios por minuto
     * 
     * @param \Illuminate\Support\Collection $historial
     * @return float|null Lambda en cambios por minuto, o null si no se puede calcular
     */
    private function calcularLambdaPonderado($historial)
    {
        $ahora = Carbon::now();
        $sumaCambiosPonderados = 0.0;
        $sumaTiempoPonderado = 0.0;
        
        // Iterar sobre pares consecutivos de registros
        for ($i = 1; $i < $historial->count(); $i++) {
            $actualizacionAnterior = $historial[$i - 1];
            $actualizacionActual = $historial[$i];
            
            // Calcular tiempo del intervalo en minutos
            $tiempoIntervaloMinutos = $actualizacionAnterior->created_at->diffInMinutes($actualizacionActual->created_at);
            
            // ⚠️ PROTECCIÓN: Si el intervalo es 0 o negativo, saltar este par
            // Evita explosiones de λ cuando hay timestamps duplicados o muy cercanos
            if ($tiempoIntervaloMinutos <= 0) {
                continue;
            }
            
            // ✅ CORRECCIÓN: Calcular minutos desde ahora con protección contra timestamps futuros
            // El segundo parámetro false permite valores negativos si la fecha es futura
            // Luego forzamos a 0 mínimo para evitar pesos incorrectos por desfases de servidor/colas/timezone
            $minutosDesdeAhora = $actualizacionActual->created_at->diffInMinutes($ahora, false);
            $minutosDesdeAhora = max(0, $minutosDesdeAhora);
            
            // Calcular peso exponencial: peso = exp(-k * minutos_desde_ahora)
            $peso = exp(-self::FACTOR_DECAY_EXPONENCIAL * $minutosDesdeAhora);
            
            // Determinar si hubo cambio (variación > 1%)
            $precioAnterior = (float) $actualizacionAnterior->precio_total;
            $precioActual = (float) $actualizacionActual->precio_total;
            
            $cambio = 0; // Por defecto sin cambio
            
            if ($precioAnterior > 0) {
                $variacion = abs($precioActual - $precioAnterior) / $precioAnterior;
                
                if ($variacion > (self::UMBRAL_CAMBIO_PORCENTAJE / 100)) {
                    $cambio = 1; // Hubo cambio significativo
                }
            } else {
                // Si precio anterior es 0, considerar cambio total
                if ($precioActual > 0) {
                    $cambio = 1;
                }
            }
            
            // Acumular cambios ponderados
            $sumaCambiosPonderados += $cambio * $peso;
            
            // Acumular tiempo ponderado
            $sumaTiempoPonderado += $tiempoIntervaloMinutos * $peso;
        }
        
        // Si no hay tiempo ponderado acumulado, no se puede calcular λ
        if ($sumaTiempoPonderado == 0) {
            return null;
        }
        
        // Calcular λ: cambios por minuto
        $lambda = $sumaCambiosPonderados / $sumaTiempoPonderado;
        
        return $lambda;
    }
    
    /**
     * Calcula el nuevo intervalo en minutos desde λ
     * 
     * Fórmula: intervalo = 1 / (c * λ + epsilon)
     * 
     * @param float $lambda Tasa de cambios por minuto
     * @return float Intervalo en minutos
     */
    private function calcularIntervaloDesdeLambda($lambda)
    {
        // Fórmula: intervalo = 1 / (c * λ + epsilon)
        $denominador = (self::FACTOR_C * $lambda) + self::EPSILON;
        $intervalo = 1.0 / $denominador;
        
        return $intervalo;
    }
    
    /**
     * Registra una actualización exitosa en el historial
     * 
     * @param int $ofertaId
     * @param float $precioTotal
     * @param string $tipo 'automatico' o 'manual'
     * @return HistoricoTiempoActualizacionPrecioOferta
     */
    public function registrarActualizacion($ofertaId, $precioTotal, $tipo = 'automatico')
    {
        $oferta = OfertaProducto::findOrFail($ofertaId);
        $frecuenciaAplicada = $oferta->frecuencia_actualizar_precio_minutos;
        
        // Crear registro en historial
        $registro = HistoricoTiempoActualizacionPrecioOferta::create([
            'oferta_id' => $ofertaId,
            'precio_total' => $precioTotal,
            'tipo_actualizacion' => $tipo,
            'frecuencia_aplicada_minutos' => $frecuenciaAplicada,
            'frecuencia_calculada_minutos' => null, // Se calculará después
        ]);
        
        // Calcular nueva frecuencia basada en el historial actualizado
        $nuevaFrecuencia = $this->calcularFrecuencia($ofertaId);
        
        // Actualizar el registro con la frecuencia calculada
        $registro->update([
            'frecuencia_calculada_minutos' => $nuevaFrecuencia
        ]);
        
        return $registro;
    }
    
    /**
     * Registra o actualiza una actualización manual según el tiempo transcurrido
     * Si el último registro supera el tiempo de actualización, crea uno nuevo.
     * Si no, actualiza el precio del último registro.
     * 
     * ✅ CORREGIDO: Ahora crea el registro PRIMERO, luego calcula λ con el historial actualizado
     * 
     * @param int $ofertaId
     * @param float $precioTotal
     * @param string $tipo 'automatico' o 'manual'
     * @return HistoricoTiempoActualizacionPrecioOferta|null
     */
    public function registrarOActualizarActualizacion($ofertaId, $precioTotal, $tipo = 'manual')
    {
        $oferta = OfertaProducto::findOrFail($ofertaId);
        
        // Si la oferta tiene chollo_id, no registrar en historial
        if ($oferta->chollo_id) {
            return null;
        }
        
        // Buscar el último registro del historial
        $ultimoRegistro = HistoricoTiempoActualizacionPrecioOferta::where('oferta_id', $ofertaId)
            ->orderBy('created_at', 'desc')
            ->first();
        
        // Si no existe registro, crear uno nuevo
        if (!$ultimoRegistro) {
            return $this->registrarActualizacion($ofertaId, $precioTotal, $tipo);
        }
        
        // Calcular tiempo transcurrido desde el último registro
        $tiempoTranscurridoMinutos = Carbon::now()->diffInMinutes($ultimoRegistro->created_at);
        $frecuenciaActual = $oferta->frecuencia_actualizar_precio_minutos ?? 1440;
        
        // Si el tiempo transcurrido supera la frecuencia de actualización, crear nuevo registro
        if ($tiempoTranscurridoMinutos >= $frecuenciaActual) {
            // ✅ CORRECCIÓN: Crear el registro PRIMERO (el precio ya cambió)
            $registro = HistoricoTiempoActualizacionPrecioOferta::create([
                'oferta_id' => $ofertaId,
                'precio_total' => $precioTotal,
                'tipo_actualizacion' => $tipo,
                'frecuencia_aplicada_minutos' => $frecuenciaActual,
                'frecuencia_calculada_minutos' => null, // Se calculará después
            ]);
            
            // ✅ CORRECCIÓN: Luego calcular frecuencia usando el historial ACTUALIZADO (incluye el nuevo registro)
            $frecuenciaCalculada = $this->calcularFrecuencia($ofertaId);
            
            // Actualizar el registro con la frecuencia calculada
            $registro->update([
                'frecuencia_calculada_minutos' => $frecuenciaCalculada
            ]);
            
            return $registro;
        } else {
            // Si no ha pasado suficiente tiempo, actualizar el último registro
            // Si es una actualización manual, también actualizar el tipo a 'manual'
            $datosActualizar = [
                'precio_total' => $precioTotal
            ];
            
            // Si el tipo es 'manual', actualizar también el tipo_actualizacion
            // Esto asegura que las actualizaciones manuales siempre se marquen como 'manual'
            if ($tipo === 'manual') {
                $datosActualizar['tipo_actualizacion'] = 'manual';
            }
            
            $ultimoRegistro->update($datosActualizar);
            
            return $ultimoRegistro;
        }
    }
}
