<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Click;
use App\Models\Producto;
use App\Models\OfertaProducto;
use Carbon\Carbon;

class ClicksTableSeeder extends Seeder
{
    /**
     * Array de ciudades españolas con sus coordenadas
     * Para generar datos realistas de geolocalización
     */
    private $ciudadesEspana = [
        ['ciudad' => 'Madrid', 'pais' => 'Spain', 'latitud' => 40.4168, 'longitud' => -3.7038],
        ['ciudad' => 'Barcelona', 'pais' => 'Spain', 'latitud' => 41.3851, 'longitud' => 2.1734],
        ['ciudad' => 'Valencia', 'pais' => 'Spain', 'latitud' => 39.4699, 'longitud' => -0.3763],
        ['ciudad' => 'Sevilla', 'pais' => 'Spain', 'latitud' => 37.3891, 'longitud' => -5.9845],
        ['ciudad' => 'Zaragoza', 'pais' => 'Spain', 'latitud' => 41.6488, 'longitud' => -0.8891],
        ['ciudad' => 'Málaga', 'pais' => 'Spain', 'latitud' => 36.7213, 'longitud' => -4.4214],
        ['ciudad' => 'Murcia', 'pais' => 'Spain', 'latitud' => 37.9922, 'longitud' => -1.1307],
        ['ciudad' => 'Palma', 'pais' => 'Spain', 'latitud' => 39.5696, 'longitud' => 2.6502],
        ['ciudad' => 'Las Palmas', 'pais' => 'Spain', 'latitud' => 28.1248, 'longitud' => -15.4300],
        ['ciudad' => 'Bilbao', 'pais' => 'Spain', 'latitud' => 43.2627, 'longitud' => -2.9253],
        ['ciudad' => 'Alicante', 'pais' => 'Spain', 'latitud' => 38.3452, 'longitud' => -0.4810],
        ['ciudad' => 'Córdoba', 'pais' => 'Spain', 'latitud' => 37.8882, 'longitud' => -4.7794],
        ['ciudad' => 'Valladolid', 'pais' => 'Spain', 'latitud' => 41.6523, 'longitud' => -4.7245],
        ['ciudad' => 'Vigo', 'pais' => 'Spain', 'latitud' => 42.2406, 'longitud' => -8.7207],
        ['ciudad' => 'Gijón', 'pais' => 'Spain', 'latitud' => 43.5453, 'longitud' => -5.6619],
        ['ciudad' => 'Hospitalet', 'pais' => 'Spain', 'latitud' => 41.3597, 'longitud' => 2.0994],
        ['ciudad' => 'A Coruña', 'pais' => 'Spain', 'latitud' => 43.3623, 'longitud' => -8.4115],
        ['ciudad' => 'Granada', 'pais' => 'Spain', 'latitud' => 37.1773, 'longitud' => -3.5986],
        ['ciudad' => 'Vitoria', 'pais' => 'Spain', 'latitud' => 42.8467, 'longitud' => -2.6716],
        ['ciudad' => 'Elche', 'pais' => 'Spain', 'latitud' => 38.2622, 'longitud' => -0.7011],
        ['ciudad' => 'Oviedo', 'pais' => 'Spain', 'latitud' => 43.3603, 'longitud' => -5.8448],
        ['ciudad' => 'Santa Cruz', 'pais' => 'Spain', 'latitud' => 28.4682, 'longitud' => -16.2549],
        ['ciudad' => 'Móstoles', 'pais' => 'Spain', 'latitud' => 40.3228, 'longitud' => -3.8647],
        ['ciudad' => 'Alcalá de Henares', 'pais' => 'Spain', 'latitud' => 40.4817, 'longitud' => -3.3641],
        ['ciudad' => 'Pamplona', 'pais' => 'Spain', 'latitud' => 42.8179, 'longitud' => -1.6442],
        ['ciudad' => 'Fuenlabrada', 'pais' => 'Spain', 'latitud' => 40.2842, 'longitud' => -3.7941],
        ['ciudad' => 'Almería', 'pais' => 'Spain', 'latitud' => 36.8306, 'longitud' => -2.4596],
        ['ciudad' => 'Leganés', 'pais' => 'Spain', 'latitud' => 40.3265, 'longitud' => -3.7588],
        ['ciudad' => 'Sabadell', 'pais' => 'Spain', 'latitud' => 41.5489, 'longitud' => 2.1074],
        ['ciudad' => 'Santander', 'pais' => 'Spain', 'latitud' => 43.4623, 'longitud' => -3.8099],
    ];

    public function run()
    {
        $producto = Producto::where('slug', 'dodot-bebe-seco-talla-4')->first();

        if (!$producto) {
            $this->command->warn('Producto no encontrado.');
            return;
        }

        $ofertas = OfertaProducto::where('producto_id', $producto->id)->get();

        $horas = [9, 10, 14, 18, 20];
        $minutos = [0, 5, 10, 15, 20, 25];

        foreach ($ofertas as $oferta) {
            foreach ($horas as $hora) {
                foreach ($minutos as $minuto) {
                    // Seleccionar una ciudad aleatoria para este click
                    $ciudadAleatoria = $this->ciudadesEspana[array_rand($this->ciudadesEspana)];
                    
                    // Añadir pequeña variación aleatoria a las coordenadas para simular
                    // diferentes ubicaciones dentro de la misma ciudad
                    $latitud = $ciudadAleatoria['latitud'] + (rand(-50, 50) / 10000); // ±0.005 grados
                    $longitud = $ciudadAleatoria['longitud'] + (rand(-50, 50) / 10000); // ±0.005 grados
                    
                    Click::create([
                        'oferta_id' => $oferta->id,
                        'campaña' => 'test' . rand(1, 3),
                        'ip' => '192.168.1.' . rand(1, 255),
                        'precio_unidad' => $oferta->precio_unidad,
                        'posicion' => rand(1, 5),
                        // Nuevos campos de geolocalización
                        'ciudad' => $ciudadAleatoria['ciudad'],
                        'pais' => $ciudadAleatoria['pais'],
                        'latitud' => $latitud,
                        'longitud' => $longitud,
                        'created_at' => Carbon::today()->setTime($hora, $minuto),
                        'updated_at' => now(),
                    ]);
                }
            }
        }

        $this->command->info('Clicks creados con datos de geolocalización españoles.');
    }
}
