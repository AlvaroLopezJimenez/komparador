<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Click;
use App\Models\OfertaProducto;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

/**
 * Job para guardar clicks de forma asíncrona
 * 
 * Este Job se ejecuta en background y se encarga de:
 * 1. Validar si la oferta existe
 * 2. Calcular la posición de la oferta
 * 3. Verificar si ya existe un click duplicado (IP + oferta + fecha)
 * 4. Guardar el click en la base de datos
 * 
 * Ventajas de usar Jobs:
 * - El usuario no espera a que se guarde el click
 * - Si hay error en el guardado, se reintenta automáticamente
 * - Se puede escalar procesando múltiples clicks en paralelo
 */
class GuardarClickJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Número máximo de intentos si falla el job
     */
    public $tries = 3;

    /**
     * Tiempo máximo que puede tardar el job (en segundos)
     */
    public $timeout = 30;

    /**
     * Datos necesarios para guardar el click
     */
    public $ofertaId;
    public $campana;
    public $ip;
    public $precioUnidad;

    /**
     * Create a new job instance.
     * 
     * @param int $ofertaId ID de la oferta clickeada
     * @param string|null $campana Nombre de la campaña (opcional)
     * @param string $ip Dirección IP del usuario
     * @param float $precioUnidad Precio por unidad de la oferta
     */
    public function __construct($ofertaId, $campana, $ip, $precioUnidad)
    {
        $this->ofertaId = $ofertaId;
        $this->campana = $campana;
        $this->ip = $ip;
        $this->precioUnidad = $precioUnidad;
    }

    /**
     * Execute the job.
     * 
     * Este método se ejecuta cuando el worker procesa el job
     * Aquí está toda la lógica que antes estaba en el método redirigir()
     */
    public function handle()
    {
        try {
            // 1. Buscar la oferta
            $oferta = OfertaProducto::find($this->ofertaId);
            
            // Si la oferta no existe, no podemos hacer nada
            if (!$oferta) {
                Log::warning("GuardarClickJob: Oferta no encontrada", [
                    'oferta_id' => $this->ofertaId,
                    'ip' => $this->ip
                ]);
                return;
            }

            // 2. Calcular la posición de la oferta (misma lógica que antes)
            $posicion = $this->calcularPosicionOferta($oferta);

            // 3. Verificar si ya existe un click duplicado (IP + oferta + fecha)
            $existe = Click::where('oferta_id', $this->ofertaId)
                ->where('ip', $this->ip)
                ->whereDate('created_at', now()->toDateString())
                ->exists();

            // 4. Si no existe duplicado, guardar el click SIN geolocalización
            // La geolocalización se añadirá después por el cron job
            if (!$existe) {
                Click::create([
                    'oferta_id' => $this->ofertaId,
                    'campaña' => $this->campana,
                    'ip' => $this->ip,
                    'precio_unidad' => $this->precioUnidad,
                    'posicion' => $posicion,
                    // NO guardamos geolocalización aquí - lo hará el cron
                    'ciudad' => null,
                    'latitud' => null,
                    'longitud' => null,
                ]);

                Log::info("Click guardado exitosamente (sin geolocalización)", [
                    'oferta_id' => $this->ofertaId,
                    'ip' => $this->ip,
                    'posicion' => $posicion
                ]);
            } else {
                Log::info("Click duplicado ignorado", [
                    'oferta_id' => $this->ofertaId,
                    'ip' => $this->ip
                ]);
            }

        } catch (\Exception $e) {
            // Si hay error, lo logueamos para debugging
            Log::error("Error en GuardarClickJob", [
                'oferta_id' => $this->ofertaId,
                'ip' => $this->ip,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Re-lanzamos la excepción para que Laravel reintente el job
            throw $e;
        }
    }

    /**
     * Calcula la posición de una oferta basándose en el precio por unidad
     * 
     * Esta es la misma lógica que estaba en el ClickController
     * La movemos aquí para que el Job sea independiente
     * 
     * @param OfertaProducto $oferta
     * @return int|null
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

    /**
     * Obtiene la geolocalización de una IP usando el servicio ip.guide
     * 
     * @param string $ip Dirección IP a consultar
     * @return array Array con ciudad, latitud y longitud
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
                
                if ($location) {
                    return [
                        'ciudad' => $location['city'] ?? null,
                        'latitud' => $location['latitude'] ?? null,
                        'longitud' => $location['longitude'] ?? null,
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
            'ciudad' => null,
            'latitud' => null,
            'longitud' => null,
        ];
    }

    /**
     * Método que se ejecuta cuando el job falla definitivamente
     * (después de todos los reintentos)
     */
    public function failed(\Throwable $exception)
    {
        Log::error("GuardarClickJob falló definitivamente", [
            'oferta_id' => $this->ofertaId,
            'ip' => $this->ip,
            'error' => $exception->getMessage()
        ]);
    }
}
