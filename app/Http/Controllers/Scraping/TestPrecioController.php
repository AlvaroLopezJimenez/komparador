<?php

namespace App\Http\Controllers\Scraping;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Tienda;
use Illuminate\Validation\ValidationException;

class TestPrecioController extends Controller
{
    /**
     * Mostrar la vista de testing de precios
     */
    public function index()
    {
        $tiendas = $this->obtenerTiendasDisponibles();
        $controladoresTiendas = $this->obtenerControladoresTiendas();

        return view('admin.scraping.test-precio', compact('tiendas', 'controladoresTiendas'));
    }

    /**
     * Procesar una URL para obtener el precio.
     * Siempre responde JSON con detalle del fallo (aunque APP_DEBUG esté off).
     */
    public function procesarUrl(Request $request)
    {
        $tiempoInicio = microtime(true);

        try {
            $this->limpiarLogApiSeguro();

            $request->validate([
                'url' => 'required|string',
                'tienda' => 'required|string'
            ]);

            $scrapingController = new ScrapingController();
            $scrapingRequest = new \Illuminate\Http\Request();
            $scrapingRequest->headers->set('Accept', 'application/json');
            $scrapingRequest->merge([
                'url' => $request->input('url'),
                'tienda' => $request->input('tienda'),
                'variante' => $request->input('variante') ?? null
            ]);

            $response = $scrapingController->obtenerPrecio($scrapingRequest);
            $responseData = method_exists($response, 'getData')
                ? $response->getData(true)
                : ['success' => false, 'error' => 'Respuesta no JSON del scraping'];

            if (!is_array($responseData)) {
                $responseData = ['success' => false, 'error' => 'Respuesta de scraping no es array', 'raw' => $responseData];
            }

            $tiempoRespuesta = round((microtime(true) - $tiempoInicio) * 1000, 2);
            $responseData['tiempo_respuesta'] = $tiempoRespuesta;
            $responseData['api_log'] = $this->obtenerLogApiSeguro();

            $ok = !empty($responseData['success']);

            return response()->json([
                'success' => $ok,
                'data' => $responseData,
                'error' => $ok ? null : ($responseData['error'] ?? 'Scraping sin éxito (sin campo error)'),
                'debug' => $responseData['debug'] ?? null,
                'api_log' => $responseData['api_log'],
                'tiempo_respuesta' => $tiempoRespuesta,
                'http_status_scraping' => method_exists($response, 'getStatusCode') ? $response->getStatusCode() : null,
            ], $ok ? 200 : 200); // 200 siempre para que el front lea el JSON aunque falle el scraping

        } catch (ValidationException $e) {
            return $this->jsonError(
                'Validación: ' . collect($e->errors())->flatten()->implode(' '),
                $tiempoInicio,
                [
                    'exception' => ValidationException::class,
                    'validation_errors' => $e->errors(),
                ],
                422
            );
        } catch (\Throwable $e) {
            return $this->jsonError(
                $e->getMessage() !== '' ? $e->getMessage() : 'Excepción sin mensaje',
                $tiempoInicio,
                [
                    'exception' => get_class($e),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => collect($e->getTrace())->take(8)->map(function ($frame) {
                        return [
                            'file' => $frame['file'] ?? null,
                            'line' => $frame['line'] ?? null,
                            'function' => $frame['function'] ?? null,
                            'class' => $frame['class'] ?? null,
                        ];
                    })->values()->all(),
                ],
                500
            );
        }
    }

    /**
     * @param  array<string, mixed>  $debug
     */
    private function jsonError(string $error, float $tiempoInicio, array $debug = [], int $status = 500)
    {
        $tiempoRespuesta = round((microtime(true) - $tiempoInicio) * 1000, 2);

        return response()->json([
            'success' => false,
            'error' => $error,
            'message' => $error, // por si el front u otras capas miran "message"
            'debug' => $debug,
            'api_log' => $this->obtenerLogApiSeguro(),
            'tiempo_respuesta' => $tiempoRespuesta,
            'data' => [
                'success' => false,
                'error' => $error,
                'debug' => $debug,
                'api_log' => $this->obtenerLogApiSeguro(),
                'tiempo_respuesta' => $tiempoRespuesta,
            ],
        ], $status);
    }

    private function limpiarLogApiSeguro(): void
    {
        try {
            if (method_exists(PeticionApiHTMLController::class, 'clearUltimoLogApi')) {
                PeticionApiHTMLController::clearUltimoLogApi();
            }
        } catch (\Throwable $e) {
            // no tumbar el test por el log
        }
    }

    private function obtenerLogApiSeguro(): ?array
    {
        try {
            if (method_exists(PeticionApiHTMLController::class, 'getUltimoLogApi')) {
                return PeticionApiHTMLController::getUltimoLogApi();
            }
        } catch (\Throwable $e) {
            return ['error_leyendo_log' => $e->getMessage()];
        }

        return null;
    }

    /**
     * Obtener lista de tiendas disponibles
     */
    private function obtenerTiendasDisponibles()
    {
        return Tienda::query()
            ->select('nombre')
            ->orderBy('nombre', 'asc')
            ->pluck('nombre')
            ->toArray();
    }

    /**
     * Obtener lista de controladores de tiendas disponibles (misma lógica que DiagnosticoController).
     */
    private function obtenerControladoresTiendas()
    {
        $tiendasPath = app_path('Http/Controllers/Scraping/Tiendas');
        $controladores = [];

        if (file_exists($tiendasPath)) {
            $archivos = scandir($tiendasPath);

            foreach ($archivos as $archivo) {
                if (pathinfo($archivo, PATHINFO_EXTENSION) === 'php' &&
                    $archivo !== 'PlantillaTiendaController.php' &&
                    $archivo !== 'INSTRUCCIONES_TIENDAS.txt') {

                    $nombreTienda = str_replace('Controller.php', '', $archivo);
                    $controladores[] = $nombreTienda;
                }
            }
        }

        sort($controladores);
        return $controladores;
    }
}
