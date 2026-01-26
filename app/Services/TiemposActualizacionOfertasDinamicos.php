<?php

namespace App\Services;

use App\Models\OfertaProducto;
use App\Models\HistoricoTiempoActualizacionPrecioOferta;
use Carbon\Carbon;

class TiemposActualizacionOfertasDinamicos
{
    // ============================================================================
    // CONFIGURACIÓN DE RANGOS DE HISTORIAL
    // ============================================================================
    // Define cuántas actualizaciones se necesitan para aplicar diferentes niveles
    // de análisis. Con pocos datos, el sistema es más conservador.
    
    const RANGO_CONSERVADOR_MIN = 2;
    // Mínimo de actualizaciones exitosas necesarias para empezar a aplicar
    // ajustes conservadores (ajustes más pequeños y cautelosos)
    
    const RANGO_CONSERVADOR_MAX = 5;
    // Máximo de actualizaciones para estar en modo conservador.
    // Con 6 o más actualizaciones, se aplica el análisis completo.
    
    const RANGO_COMPLETO_MIN = 6;
    // Mínimo de actualizaciones exitosas necesarias para aplicar el análisis
    // completo con todos los ajustes normales de frecuencia
    
    // ============================================================================
    // CONFIGURACIÓN DE VENTANA DE ANÁLISIS
    // ============================================================================
    
    const VENTANA_ANALISIS_DIAS = 30;
    // Días hacia atrás desde hoy que se analizarán para detectar patrones.
    // Solo se consideran actualizaciones dentro de esta ventana temporal.
    
    // ============================================================================
    // VALIDACIÓN DE INTERVALOS ENTRE ACTUALIZACIONES
    // ============================================================================
    
    const FACTOR_INTERVALO_VALIDO = 1.5;
    // Si el tiempo entre dos actualizaciones consecutivas es mayor a
    // (frecuencia_actual * FACTOR_INTERVALO_VALIDO), se ignora ese intervalo.
    // Ejemplo: Si frecuencia es 1440 min (24h) y hay un gap de 3 días (4320 min),
    // se ignora porque 4320 > (1440 * 1.5 = 2160). Esto evita considerar
    // períodos donde la oferta estuvo desactivada o sin scrapear.
    
    // ============================================================================
    // MARGEN DE TOLERANCIA PARA CAMBIOS DE PRECIO
    // ============================================================================
    
    const MARGEN_TOLERANCIA_PORCENTAJE = 1;
    // Porcentaje mínimo de diferencia entre dos precios para considerar que
    // hubo un "cambio significativo". Si la diferencia es <= 1%, se considera
    // que el precio no cambió (estable). Si es > 1%, se marca como "cambio".
    // Ejemplo: Precio anterior 50€, nuevo 50.30€ → diferencia 0.6% → SIN CAMBIO
    //          Precio anterior 50€, nuevo 51€ → diferencia 2% → CAMBIO
    
    // ============================================================================
    // AJUSTES DE FRECUENCIA SEGÚN MOVIMIENTO (PORCENTUAL)
    // ============================================================================
    
    const REDUCCION_MUCHO_MOVIMIENTO_PORCENTAJE = 15;
    // Porcentaje a REDUCIR de la frecuencia actual cuando hay mucho movimiento
    // (2 cambios consecutivos). Reducir frecuencia = actualizar más seguido.
    // Ejemplo: 24h → 20.4h (reducción de 3.6h), 12h → 10.2h (reducción de 1.8h)
    // Se adapta automáticamente a diferentes frecuencias base.
    
    const AUMENTO_POCO_MOVIMIENTO_PORCENTAJE = 5;
    // Porcentaje a AUMENTAR de la frecuencia actual cuando hay poco movimiento
    // (3 sin cambios consecutivos). Aumentar frecuencia = actualizar menos seguido.
    // Ejemplo: 20h → 21h (aumento de 1h), 12h → 12.6h (aumento de 0.6h)
    // Más conservador que la reducción (5% vs 15%).
    
    // ============================================================================
    // LÍMITE MÍNIMO ABSOLUTO DE FRECUENCIA
    // ============================================================================
    
    const FRECUENCIA_MINIMA_ABSOLUTA_MINUTOS = 15;
    // Límite absoluto mínimo de frecuencia, incluso si el cálculo sugiere menos.
    // Evita actualizar demasiado frecuentemente y sobrecargar el sistema.
    // Este límite se aplica ANTES de validar los límites de la tienda.
    // Ninguna oferta tendrá una frecuencia menor a 15 minutos.
    
    // ============================================================================
    // PESOS TEMPORALES PARA ANÁLISIS (Dar más importancia a lo reciente)
    // ============================================================================
    
    const PESO_RECIENTE_DIAS = 7;
    // Días desde hoy hacia atrás que se consideran "recientes" y tienen más peso
    // en el análisis. Los cambios recientes son más relevantes que los antiguos.
    
    const PESO_RECIENTE = 3;
    // Multiplicador de peso para actualizaciones de los últimos 7 días.
    // Un cambio reciente vale 3x más que uno antiguo en el análisis.
    
    const PESO_MEDIO_DIAS = 15;
    // Días desde hoy hasta el día 15. Actualizaciones en este rango tienen
    // peso medio (menos que recientes, más que antiguas).
    
    const PESO_MEDIO = 2;
    // Multiplicador de peso para actualizaciones de los días 8-15.
    // Un cambio de este período vale 2x más que uno antiguo.
    
    const PESO_ANTIGUO_DIAS = 30;
    // Días desde hoy hasta el día 30. Actualizaciones en este rango tienen
    // peso base (el menor peso en el análisis).
    
    const PESO_ANTIGUO = 1;
    // Multiplicador de peso base para actualizaciones de los días 16-30.
    // Este es el peso de referencia (1x).
    
    // ============================================================================
    // DETECCIÓN DE PATRONES CONSECUTIVOS
    // ============================================================================
    
    const CAMBIOS_CONSECUTIVOS_MUCHO_MOVIMIENTO = 2;
    // Número de cambios de precio consecutivos (con diferencia > 1%) necesarios
    // para detectar "mucho movimiento" y reducir la frecuencia.
    // Se analizan desde el más reciente hacia atrás, aplicando pesos temporales.
    
    const SIN_CAMBIOS_CONSECUTIVOS_POCO_MOVIMIENTO = 3;
    // Número de actualizaciones sin cambios consecutivas (diferencia <= 1%)
    // necesarias para detectar "poco movimiento" y aumentar la frecuencia.
    // Requiere más confirmación que los cambios (3 vs 2) para ser más conservador
    // al aumentar la frecuencia.
    
    // ============================================================================
    // FACTOR DE REDUCCIÓN PARA MODO CONSERVADOR
    // ============================================================================
    
    const FACTOR_CONSERVADOR = 0.5;
    // Factor de reducción para ajustes en modo conservador (2-5 actualizaciones).
    // Los ajustes se reducen al 50% de su valor normal.
    
    /**
     * Calcula la frecuencia adaptativa basada en el historial de actualizaciones
     * 
     * @param int $ofertaId
     * @return int Frecuencia en minutos
     */
    public function calcularFrecuencia($ofertaId)
    {
        $oferta = OfertaProducto::with('tienda')->findOrFail($ofertaId);
        $frecuenciaActual = $oferta->frecuencia_actualizar_precio_minutos ?? 1440;
        
        // Obtener historial de los últimos 30 días
        $fechaInicio = Carbon::now()->subDays(self::VENTANA_ANALISIS_DIAS);
        $historial = HistoricoTiempoActualizacionPrecioOferta::where('oferta_id', $ofertaId)
            ->where('created_at', '>=', $fechaInicio)
            ->orderBy('created_at', 'desc')
            ->get();
        
        // Si no hay suficiente historial, mantener frecuencia actual
        if ($historial->count() < self::RANGO_CONSERVADOR_MIN) {
            return $frecuenciaActual;
        }
        
        // Filtrar actualizaciones válidas (intervalos no excesivos)
        $actualizacionesValidas = $this->filtrarActualizacionesValidas($historial, $frecuenciaActual);
        
        if ($actualizacionesValidas->count() < self::RANGO_CONSERVADOR_MIN) {
            return $frecuenciaActual;
        }
        
        // Determinar modo (conservador o completo)
        $esModoConservador = $actualizacionesValidas->count() >= self::RANGO_CONSERVADOR_MIN 
                          && $actualizacionesValidas->count() <= self::RANGO_CONSERVADOR_MAX;
        
        // Clasificar cambios de precio
        $clasificaciones = $this->clasificarCambiosPrecio($actualizacionesValidas);
        
        // Detectar movimiento consecutivo
        $nuevaFrecuencia = $this->detectarMovimientoYCalcularFrecuencia(
            $clasificaciones,
            $frecuenciaActual,
            $esModoConservador
        );
        
        // Aplicar límite mínimo absoluto
        if ($nuevaFrecuencia < self::FRECUENCIA_MINIMA_ABSOLUTA_MINUTOS) {
            $nuevaFrecuencia = self::FRECUENCIA_MINIMA_ABSOLUTA_MINUTOS;
        }
        
        // Validar límites de tienda
        $tienda = $oferta->tienda;
        $frecuenciaMinimaTienda = $tienda->frecuencia_minima_minutos ?? self::FRECUENCIA_MINIMA_ABSOLUTA_MINUTOS;
        $frecuenciaMaximaTienda = $tienda->frecuencia_maxima_minutos ?? 10080;
        
        // Si la frecuencia actual ya está en el mínimo de la tienda, no bajar más
        if ($frecuenciaActual <= $frecuenciaMinimaTienda && $nuevaFrecuencia < $frecuenciaActual) {
            $nuevaFrecuencia = $frecuenciaActual;
        }
        
        // Aplicar límites de tienda
        if ($nuevaFrecuencia < $frecuenciaMinimaTienda) {
            $nuevaFrecuencia = $frecuenciaMinimaTienda;
        }
        if ($nuevaFrecuencia > $frecuenciaMaximaTienda) {
            $nuevaFrecuencia = $frecuenciaMaximaTienda;
        }
        
        // Actualizar frecuencia en la oferta
        $oferta->update([
            'frecuencia_actualizar_precio_minutos' => $nuevaFrecuencia
        ]);
        
        return $nuevaFrecuencia;
    }
    
    /**
     * Filtra actualizaciones válidas (ignora intervalos excesivos)
     */
    private function filtrarActualizacionesValidas($historial, $frecuenciaActual)
    {
        $validas = collect();
        $limiteIntervalo = $frecuenciaActual * self::FACTOR_INTERVALO_VALIDO;
        
        foreach ($historial as $index => $actualizacion) {
            if ($index === 0) {
                // La más reciente siempre es válida
                $validas->push($actualizacion);
                continue;
            }
            
            // Calcular intervalo con la anterior
            $anterior = $historial[$index - 1];
            $intervalo = $actualizacion->created_at->diffInMinutes($anterior->created_at);
            
            // Si el intervalo es válido, incluir esta actualización
            if ($intervalo <= $limiteIntervalo) {
                $validas->push($actualizacion);
            } else {
                // Si encontramos un intervalo inválido, parar (las anteriores también serían inválidas)
                break;
            }
        }
        
        return $validas->reverse()->values(); // Ordenar de más antiguo a más reciente
    }
    
    /**
     * Clasifica cada actualización como "cambio" o "sin cambio"
     */
    private function clasificarCambiosPrecio($actualizacionesValidas)
    {
        $clasificaciones = [];
        
        foreach ($actualizacionesValidas as $index => $actualizacion) {
            if ($index === 0) {
                // La más antigua no tiene anterior para comparar
                $clasificaciones[] = [
                    'actualizacion' => $actualizacion,
                    'tipo' => 'sin_cambio', // Por defecto
                    'peso' => $this->obtenerPeso($actualizacion->created_at)
                ];
                continue;
            }
            
            $anterior = $actualizacionesValidas[$index - 1];
            $diferenciaPorcentual = $this->calcularDiferenciaPorcentual(
                $anterior->precio_total,
                $actualizacion->precio_total
            );
            
            $tipo = $diferenciaPorcentual > self::MARGEN_TOLERANCIA_PORCENTAJE 
                ? 'cambio' 
                : 'sin_cambio';
            
            $clasificaciones[] = [
                'actualizacion' => $actualizacion,
                'tipo' => $tipo,
                'peso' => $this->obtenerPeso($actualizacion->created_at),
                'diferencia_porcentual' => $diferenciaPorcentual
            ];
        }
        
        return array_reverse($clasificaciones); // De más reciente a más antiguo
    }
    
    /**
     * Calcula diferencia porcentual entre dos precios
     */
    private function calcularDiferenciaPorcentual($precioAnterior, $precioNuevo)
    {
        if ($precioAnterior == 0) {
            return 100; // Si precio anterior es 0, considerar cambio total
        }
        
        return abs(($precioNuevo - $precioAnterior) / $precioAnterior * 100);
    }
    
    /**
     * Obtiene el peso temporal según los días desde hoy
     */
    private function obtenerPeso($fecha)
    {
        $diasDesdeHoy = Carbon::now()->diffInDays($fecha);
        
        if ($diasDesdeHoy <= self::PESO_RECIENTE_DIAS) {
            return self::PESO_RECIENTE;
        } elseif ($diasDesdeHoy <= self::PESO_MEDIO_DIAS) {
            return self::PESO_MEDIO;
        } else {
            return self::PESO_ANTIGUO;
        }
    }
    
    /**
     * Detecta movimiento consecutivo y calcula nueva frecuencia
     */
    private function detectarMovimientoYCalcularFrecuencia($clasificaciones, $frecuenciaActual, $esModoConservador)
    {
        $contadorCambios = 0;
        $contadorSinCambios = 0;
        $puntosCambios = 0;
        $puntosSinCambios = 0;
        
        foreach ($clasificaciones as $clasificacion) {
            if ($clasificacion['tipo'] === 'cambio') {
                $contadorCambios++;
                $puntosCambios += $clasificacion['peso'];
                $contadorSinCambios = 0;
                $puntosSinCambios = 0;
                
                // Si tenemos suficientes cambios consecutivos (considerando pesos)
                if ($contadorCambios >= self::CAMBIOS_CONSECUTIVOS_MUCHO_MOVIMIENTO) {
                    $reduccionPorcentaje = self::REDUCCION_MUCHO_MOVIMIENTO_PORCENTAJE;
                    if ($esModoConservador) {
                        $reduccionPorcentaje *= self::FACTOR_CONSERVADOR;
                    }
                    $reduccion = $frecuenciaActual * ($reduccionPorcentaje / 100);
                    return max(self::FRECUENCIA_MINIMA_ABSOLUTA_MINUTOS, $frecuenciaActual - $reduccion);
                }
            } else {
                $contadorSinCambios++;
                $puntosSinCambios += $clasificacion['peso'];
                $contadorCambios = 0;
                $puntosCambios = 0;
                
                // Si tenemos suficientes sin cambios consecutivos (considerando pesos)
                if ($contadorSinCambios >= self::SIN_CAMBIOS_CONSECUTIVOS_POCO_MOVIMIENTO) {
                    $aumentoPorcentaje = self::AUMENTO_POCO_MOVIMIENTO_PORCENTAJE;
                    if ($esModoConservador) {
                        $aumentoPorcentaje *= self::FACTOR_CONSERVADOR;
                    }
                    $aumento = $frecuenciaActual * ($aumentoPorcentaje / 100);
                    return $frecuenciaActual + $aumento;
                }
            }
        }
        
        // Si no se detectó ningún patrón, mantener frecuencia actual
        return $frecuenciaActual;
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
            // Calcular frecuencia ANTES de crear el nuevo registro (sin contar el que vamos a crear)
            // Esto usa el historial existente sin incluir el registro que vamos a crear ahora
            $frecuenciaCalculada = $this->calcularFrecuencia($ofertaId);
            
            // Crear nuevo registro
            $registro = HistoricoTiempoActualizacionPrecioOferta::create([
                'oferta_id' => $ofertaId,
                'precio_total' => $precioTotal,
                'tipo_actualizacion' => $tipo,
                'frecuencia_aplicada_minutos' => $frecuenciaActual,
                'frecuencia_calculada_minutos' => $frecuenciaCalculada,
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

