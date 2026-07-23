<?php

namespace App\Http\Controllers\Scraping;

use App\Http\Controllers\Controller;
use App\Http\Controllers\DescuentosController;
use App\Models\OfertaProducto;
use App\Services\CsvAwinOfertaService;
use App\Services\TiendaScrapingConfigResolver;
use App\Support\UrlOfertaValidacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ScrapingController extends Controller
{
    /**
     * Punto de entrada principal para scraping de ofertas
     */
    public function obtenerPrecio(Request $request, $oferta = null)
    {
        try {
            // Validar datos de entrada
            $request->validate([
                'url' => UrlOfertaValidacion::rules(),
                'tienda' => 'required|string',
                'variante' => 'nullable|string',
                'producto_id' => 'nullable|integer|exists:productos,id',
            ]);

            $url = $request->input('url');
            $tienda = $request->input('tienda');
            $variante = $request->input('variante');

            // Obtener información de la tienda desde la base de datos
            $tiendaModel = \App\Models\Tienda::where('nombre', $tienda)->first();
            
            if (!$tiendaModel) {
                return response()->json([
                    'success' => false,
                    'error' => "Tienda no encontrada en la base de datos: {$tienda}"
                ]);
            }

            $apiForzada = $request->input('api_forzada');
            if (is_string($apiForzada) && $apiForzada !== '') {
                $apiEfectiva = $apiForzada;
            } else {
                $apiEfectiva = $tiendaModel->api;

                // API efectiva: categoría del producto si está configurada, si no la de la tienda
                $categoriaId = null;
                if ($oferta !== null) {
                    $oferta->loadMissing('producto');
                    $categoriaId = $oferta->producto?->categoria_id;
                } elseif ($request->filled('producto_id')) {
                    $categoriaId = \App\Models\Producto::whereKey($request->input('producto_id'))->value('categoria_id');
                }

                if ($categoriaId !== null || $oferta !== null) {
                    $apiEfectiva = app(TiendaScrapingConfigResolver::class)->resolverApi(
                        $tiendaModel,
                        $categoriaId !== null ? (int) $categoriaId : null
                    ) ?? $apiEfectiva;
                }
            }

            if ($apiEfectiva === TiendaScrapingConfigResolver::API_CSV_AWIN) {
                $response = app(CsvAwinOfertaService::class)->obtenerPrecioJson($url, $tiendaModel, $oferta);

                return $this->respuestaConDescuentosOfertaSiCorresponde($response, $oferta);
            }

            // Resolver controlador de tienda (acepta distintas capitalizaciones de archivo/clase)
            $resuelto = $this->resolverControladorTienda($tienda);
            if ($resuelto === null) {
                return response()->json([
                    'success' => false,
                    'error' => "Controlador no encontrado para la tienda: {$tienda}",
                    'debug' => [
                        'nombre_normalizado' => $this->normalizarNombreTienda($tienda),
                        'clase_esperada' => "App\\Http\\Controllers\\Scraping\\Tiendas\\"
                            . $this->normalizarNombreTienda($tienda) . 'Controller',
                        'pista' => 'Se buscó en app/Http/Controllers/Scraping/Tiendas/ ignorando mayúsculas/minúsculas del nombre de archivo.',
                    ],
                ]);
            }

            $claseControlador = $resuelto['clase'];
            $controladorTienda = new $claseControlador();

            if ($apiEfectiva !== null && $apiEfectiva !== $tiendaModel->api) {
                $tiendaModel = clone $tiendaModel;
                $tiendaModel->api = $apiEfectiva;
            }

            // Llamar al método obtenerPrecio del controlador de la tienda
            $response = $controladorTienda->obtenerPrecio($url, $variante, $tiendaModel, $oferta);

            return $this->respuestaConDescuentosOfertaSiCorresponde($response, $oferta, [
                'controlador_clase' => $resuelto['clase'],
                'controlador_archivo' => $resuelto['archivo'],
                'api_efectiva' => $apiEfectiva,
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            $mensajes = collect($e->errors())->flatten()->filter()->implode(' | ');

            return response()->json([
                'success' => false,
                'error' => 'Datos de entrada inválidos: ' . ($mensajes !== '' ? $mensajes : $e->getMessage()),
                'debug' => ['validation_errors' => $e->errors()],
            ], 422);
        } catch (\Throwable $e) {
            \Log::error('Error en ScrapingController: ' . $e->getMessage(), [
                'url' => $request->input('url'),
                'tienda' => $request->input('tienda'),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error en el scraping: ' . $e->getMessage(),
                'debug' => [
                    'exception' => get_class($e),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ],
            ], 500);
        }
    }

    /**
     * Tras un scraping con oferta, los controladores de tienda pueden haber actualizado
     * ofertas_producto.descuentos. Devolvemos ese estado al formulario sin tocar cada tienda.
     *
     * @param  mixed  $response
     * @param  array<string, mixed>  $debugExtra
     */
    private function respuestaConDescuentosOfertaSiCorresponde($response, $oferta, array $debugExtra = [])
    {
        if (!$response || !method_exists($response, 'getData')) {
            return response()->json([
                'success' => false,
                'error' => 'Respuesta inválida del controlador de tienda',
                'debug' => $debugExtra,
            ]);
        }

        $data = $response->getData(true);

        if ($oferta instanceof OfertaProducto && !empty($data['success'])) {
            $oferta->refresh();
            $descuentos = $oferta->descuentos ?? '';

            $data['descuentos'] = $descuentos;
            $data['descuentos_detectados'] = DescuentosController::parseDescuentos($descuentos);
            $data['descuentos_sincronizados'] = true;
        }

        if ($debugExtra !== []) {
            $data['debug'] = array_merge(is_array($data['debug'] ?? null) ? $data['debug'] : [], $debugExtra);
        }

        return response()->json($data);
    }

    /**
     * Resuelve la clase del controlador de tienda aceptando distintas capitalizaciones
     * del archivo (Farmaciasdirect / FarmaciasDirect / FarmaciasdIrect, etc.).
     *
     * @return array{clase: class-string, archivo: string}|null
     */
    private function resolverControladorTienda(string $tienda): ?array
    {
        $nombreNormalizado = $this->normalizarNombreTienda($tienda);
        $claseExacta = "App\\Http\\Controllers\\Scraping\\Tiendas\\{$nombreNormalizado}Controller";

        if (class_exists($claseExacta)) {
            try {
                $ref = new \ReflectionClass($claseExacta);
                return [
                    'clase' => $claseExacta,
                    'archivo' => $ref->getFileName() ?: ($nombreNormalizado . 'Controller.php'),
                ];
            } catch (\Throwable $e) {
                return [
                    'clase' => $claseExacta,
                    'archivo' => $nombreNormalizado . 'Controller.php',
                ];
            }
        }

        $dir = app_path('Http/Controllers/Scraping/Tiendas');
        if (!is_dir($dir)) {
            return null;
        }

        $esperado = strtolower($nombreNormalizado . 'controller.php');
        $archivoEncontrado = null;
        foreach (scandir($dir) ?: [] as $archivo) {
            if (!is_string($archivo) || $archivo === '.' || $archivo === '..') {
                continue;
            }
            if (strtolower($archivo) === $esperado) {
                $archivoEncontrado = $archivo;
                break;
            }
        }

        if ($archivoEncontrado === null) {
            return null;
        }

        $ruta = $dir . DIRECTORY_SEPARATOR . $archivoEncontrado;
        if (!is_file($ruta)) {
            return null;
        }

        try {
            require_once $ruta;
        } catch (\Throwable $e) {
            // Si el require falla, devolvemos null y el caller informa "no encontrado";
            // el error real lo captura el try del obtenerPrecio.
            throw $e;
        }

        // Tras cargar el archivo, la clase puede existir con otra capitalización
        // class_exists sin autoload para no disparar otra búsqueda Composer
        if (class_exists($claseExacta, false)) {
            return [
                'clase' => $claseExacta,
                'archivo' => $archivoEncontrado,
            ];
        }

        $base = preg_replace('/Controller\.php$/i', '', $archivoEncontrado);
        $claseDesdeArchivo = "App\\Http\\Controllers\\Scraping\\Tiendas\\{$base}Controller";
        if (class_exists($claseDesdeArchivo, false) || class_exists($claseDesdeArchivo)) {
            return [
                'clase' => $claseDesdeArchivo,
                'archivo' => $archivoEncontrado,
            ];
        }

        // Último recurso: buscar en clases declaradas del namespace Tiendas
        $prefijo = 'App\\Http\\Controllers\\Scraping\\Tiendas\\';
        foreach (get_declared_classes() as $clase) {
            if (strpos($clase, $prefijo) !== 0) {
                continue;
            }
            $short = substr($clase, strrpos($clase, '\\') + 1);
            if (strtolower($short) === strtolower($nombreNormalizado . 'Controller')) {
                return [
                    'clase' => $clase,
                    'archivo' => $archivoEncontrado,
                ];
            }
        }

        return null;
    }

    /**
     * Normalizar nombre de tienda para buscar el controlador correspondiente
     * Ejemplo: "EL Corte Inglés" -> "Elcorteingles"
     */
    private function normalizarNombreTienda($tienda)
    {
        // Convertir a minúsculas
        $normalizado = strtolower($tienda);

        // Eliminar espacios, acentos y caracteres especiales
        $normalizado = Str::ascii($normalizado);
        $normalizado = preg_replace('/[^a-z0-9]/', '', $normalizado);

        // Capitalizar primera letra
        $normalizado = ucfirst($normalizado);

        return $normalizado;
    }
}
