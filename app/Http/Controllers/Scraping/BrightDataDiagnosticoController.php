<?php

namespace App\Http\Controllers\Scraping;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BrightDataDiagnosticoController extends Controller
{
    private $brightDataApiKey = '6309ae58c9c3d7dfc56309ad1f67ef1b11c02e4d688ef9b52173e571f16a913a';
    private $brightDataZone = 'chollopanales';
    private $brightDataApiUrl = 'https://api.brightdata.com/request';

    /**
     * Vista de diagnóstico de BrightData
     */
    public function index()
    {
        return view('admin.scraping.brightdata-diagnostico');
    }

    /**
     * Probar configuración de BrightData
     */
    public function probarConfiguracion(Request $request): JsonResponse
    {
        $url = $request->input('url', 'https://www.elcorteingles.es/supermercado/productos/alimentacion/');
        
        $resultados = [
            'configuracion' => $this->verificarConfiguracion(),
            'test_basico' => $this->testBasico($url),
            'test_con_render' => $this->testConRender($url),
            'test_headers' => $this->testConHeaders($url),
            'test_session' => $this->testConSession($url),
        ];

        return response()->json($resultados);
    }

    /**
     * Verificar configuración básica
     */
    private function verificarConfiguracion(): array
    {
        return [
            'api_key' => [
                'configurada' => !empty($this->brightDataApiKey),
                'longitud' => strlen($this->brightDataApiKey),
                'formato' => preg_match('/^[a-f0-9]{64}$/', $this->brightDataApiKey) ? 'válido' : 'formato incorrecto'
            ],
            'zone' => [
                'configurada' => !empty($this->brightDataZone),
                'nombre' => $this->brightDataZone,
                'longitud' => strlen($this->brightDataZone)
            ],
            'api_url' => [
                'configurada' => !empty($this->brightDataApiUrl),
                'url' => $this->brightDataApiUrl
            ]
        ];
    }

    /**
     * Test básico sin opciones adicionales
     */
    private function testBasico(string $url): array
    {
        $payload = [
            'zone' => $this->brightDataZone,
            'url' => $url,
            'format' => 'raw'
        ];

        try {
            $resp = Http::timeout(60)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->brightDataApiKey,
                    'Content-Type' => 'application/json',
                ])
                ->post($this->brightDataApiUrl, $payload);

            return [
                'status' => $resp->status(),
                'success' => $resp->successful(),
                'body_length' => strlen($resp->body()),
                'body_empty' => empty($resp->body()),
                'headers' => $resp->headers(),
                'error' => $resp->successful() ? null : $resp->body()
            ];
        } catch (\Throwable $e) {
            return [
                'error' => 'Exception: ' . $e->getMessage(),
                'status' => 'exception'
            ];
        }
    }

    /**
     * Test con render habilitado
     */
    private function testConRender(string $url): array
    {
        $payload = [
            'zone' => $this->brightDataZone,
            'url' => $url,
            'format' => 'raw',
            'render' => true,
            'headers' => [
                'Accept-Language' => 'es-ES,es;q=0.9',
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
            ]
        ];

        try {
            $resp = Http::timeout(120)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->brightDataApiKey,
                    'Content-Type' => 'application/json',
                ])
                ->post($this->brightDataApiUrl, $payload);

            return [
                'status' => $resp->status(),
                'success' => $resp->successful(),
                'body_length' => strlen($resp->body()),
                'body_empty' => empty($resp->body()),
                'headers' => $resp->headers(),
                'error' => $resp->successful() ? null : $resp->body()
            ];
        } catch (\Throwable $e) {
            return [
                'error' => 'Exception: ' . $e->getMessage(),
                'status' => 'exception'
            ];
        }
    }

    /**
     * Test con headers personalizados
     */
    private function testConHeaders(string $url): array
    {
        $payload = [
            'zone' => $this->brightDataZone,
            'url' => $url,
            'format' => 'raw',
            'headers' => [
                'Accept-Language' => 'es-ES,es;q=0.9',
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
            ]
        ];

        try {
            $resp = Http::timeout(90)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->brightDataApiKey,
                    'Content-Type' => 'application/json',
                ])
                ->post($this->brightDataApiUrl, $payload);

            return [
                'status' => $resp->status(),
                'success' => $resp->successful(),
                'body_length' => strlen($resp->body()),
                'body_empty' => empty($resp->body()),
                'headers' => $resp->headers(),
                'error' => $resp->successful() ? null : $resp->body()
            ];
        } catch (\Throwable $e) {
            return [
                'error' => 'Exception: ' . $e->getMessage(),
                'status' => 'exception'
            ];
        }
    }

    /**
     * Test con session_id
     */
    private function testConSession(string $url): array
    {
        $payload = [
            'zone' => $this->brightDataZone,
            'url' => $url,
            'format' => 'raw',
            'session_id' => 'test-session-' . time()
        ];

        try {
            $resp = Http::timeout(90)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->brightDataApiKey,
                    'Content-Type' => 'application/json',
                ])
                ->post($this->brightDataApiUrl, $payload);

            return [
                'status' => $resp->status(),
                'success' => $resp->successful(),
                'body_length' => strlen($resp->body()),
                'body_empty' => empty($resp->body()),
                'headers' => $resp->headers(),
                'error' => $resp->successful() ? null : $resp->body()
            ];
        } catch (\Throwable $e) {
            return [
                'error' => 'Exception: ' . $e->getMessage(),
                'status' => 'exception'
            ];
        }
    }

    /**
     * Obtener información de la zona de BrightData
     */
    public function obtenerInfoZona(): JsonResponse
    {
        try {
            $resp = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->brightDataApiKey,
                ])
                ->get('https://api.brightdata.com/zones');

            if ($resp->successful()) {
                $zones = $resp->json();
                $nuestraZona = collect($zones)->firstWhere('name', $this->brightDataZone);
                
                return response()->json([
                    'success' => true,
                    'zona_encontrada' => !empty($nuestraZona),
                    'zona_info' => $nuestraZona,
                    'todas_las_zonas' => $zones
                ]);
            }

            return response()->json([
                'success' => false,
                'error' => 'HTTP ' . $resp->status() . ': ' . $resp->body()
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => 'Exception: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Diagnóstico completo de BrightData
     */
    public function diagnosticoCompleto(): JsonResponse
    {
        $diagnostico = [
            'configuracion' => $this->verificarConfiguracion(),
            'info_zonas' => $this->obtenerInfoZona()->getData(true),
            'test_elcorteingles' => $this->testBasico('https://www.elcorteingles.es/supermercado/productos/alimentacion/'),
            'test_httpbin' => $this->testBasico('https://httpbin.org/html')
        ];

        return response()->json($diagnostico);
    }
}
