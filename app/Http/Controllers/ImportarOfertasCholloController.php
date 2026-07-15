<?php

namespace App\Http\Controllers;

use App\Models\Aviso;
use App\Models\OfertaProducto;
use App\Models\Producto;
use App\Models\ProductoOfertaMasBarataPorProducto;
use App\Models\Tienda;
use App\Models\UrlDescartada;
use App\Services\CalcularPrecioUnidad;
use App\Services\ConsultarNeoCifrado;
use App\Services\LimpiarUrlDeTiendas;
use App\Services\SacarPrimeraOfertaDeUnProductoAplicadoDescuentosYChollos;
use App\Services\TiendaScrapingConfigResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ImportarOfertasCholloController extends Controller
{
    public function index()
    {
        return view('admin.ofertas.importar-chollo');
    }

    public function analizar(Request $request)
    {
        $request->validate([
            'json_export' => 'required|string',
            'producto_id' => 'required|exists:productos,id',
        ]);

        $payload = $this->parsearJsonExport($request->input('json_export'));
        $producto = Producto::findOrFail((int) $request->producto_id);
        $todasLasTiendas = Tienda::all();

        $filas = [];
        foreach ($payload['ofertas'] as $indice => $ofertaOrigen) {
            $filas[] = $this->prepararFilaOferta($ofertaOrigen, $indice, $producto, $todasLasTiendas);
        }

        return response()->json([
            'success' => true,
            'producto' => [
                'id' => $producto->id,
                'nombre' => $producto->nombre,
                'unidadDeMedida' => $producto->unidadDeMedida,
            ],
            'origen' => $payload['meta'],
            'filas' => $filas,
        ]);
    }

    public function importar(Request $request)
    {
        $request->validate([
            'json_export' => 'required|string',
            'producto_id' => 'required|exists:productos,id',
            'indices' => 'required|array|min:1',
            'indices.*' => 'integer|min:0',
        ]);

        $payload = $this->parsearJsonExport($request->input('json_export'));
        $producto = Producto::findOrFail((int) $request->producto_id);
        $todasLasTiendas = Tienda::all();
        $indices = array_values(array_unique(array_map('intval', $request->input('indices'))));

        $creadas = 0;
        $errores = [];
        $ofertasOrigen = $payload['ofertas'];

        foreach ($indices as $indice) {
            if (!isset($ofertasOrigen[$indice])) {
                $errores[] = "Índice {$indice}: oferta no encontrada en el JSON.";
                continue;
            }

            $fila = $this->prepararFilaOferta($ofertasOrigen[$indice], $indice, $producto, $todasLasTiendas);
            if (!$fila['importable']) {
                $errores[] = "Índice {$indice}: {$fila['estado_texto']}";
                continue;
            }

            try {
                $this->crearOfertaDesdeFila($fila, $producto);
                $creadas++;
            } catch (\Throwable $e) {
                $errores[] = "Índice {$indice}: {$e->getMessage()}";
            }
        }

        if ($creadas > 0) {
            $this->recalcularOfertaMasBarata($producto);
        }

        return response()->json([
            'success' => $creadas > 0,
            'creadas' => $creadas,
            'errores' => $errores,
        ]);
    }

    private function parsearJsonExport(string $json): array
    {
        $decoded = json_decode(trim($json), true);
        if (!is_array($decoded)) {
            abort(422, 'El JSON no es válido.');
        }

        $ofertas = $decoded['ofertas'] ?? null;
        if (!is_array($ofertas) || count($ofertas) === 0) {
            abort(422, 'El JSON no contiene ofertas.');
        }

        return [
            'meta' => [
                'version' => $decoded['version'] ?? null,
                'origen' => $decoded['origen'] ?? null,
                'exportado_en' => $decoded['exportado_en'] ?? null,
                'producto_origen' => $decoded['producto'] ?? null,
            ],
            'ofertas' => array_values($ofertas),
        ];
    }

    private function prepararFilaOferta(array $ofertaOrigen, int $indice, Producto $producto, $todasLasTiendas): array
    {
        $url = trim((string) ($ofertaOrigen['url'] ?? ''));
        $tiendaOrigenNombre = trim((string) ($ofertaOrigen['tienda']['nombre'] ?? ''));
        $estado = 'ok';
        $estadoTexto = 'Lista para importar';

        if ($url === '') {
            return $this->filaNoImportable($indice, $ofertaOrigen, $estado, 'Sin URL', 'URL vacía');
        }

        $urlNorm = $this->normalizarUrl($url);
        if (UrlDescartada::where('url', $urlNorm)->exists()) {
            return $this->filaNoImportable($indice, $ofertaOrigen, 'url_descartada', 'URL descartada', 'URL descartada');
        }

        $urlLookup = app(ConsultarNeoCifrado::class)->hashLookup($urlNorm);
        if ($urlLookup && OfertaProducto::where('url_lookup', $urlLookup)->exists()) {
            return $this->filaNoImportable($indice, $ofertaOrigen, 'url_duplicada', 'URL ya existe', 'URL ya existe en komparador');
        }

        $tienda = $this->detectarTienda($ofertaOrigen, $url, $todasLasTiendas);
        if (!$tienda) {
            return $this->filaNoImportable(
                $indice,
                $ofertaOrigen,
                'tienda_no_encontrada',
                'Tienda no encontrada',
                'No se encontró tienda para: ' . ($tiendaOrigenNombre ?: $url)
            );
        }

        if ($producto->unidadDeMedida === 'unidadUnica') {
            $unidades = 1.0;
        } else {
            $unidades = (float) ($ofertaOrigen['unidades'] ?? 0);
            if ($unidades <= 0) {
                return $this->filaNoImportable($indice, $ofertaOrigen, 'sin_unidades', 'Sin unidades', 'Unidades inválidas');
            }
        }

        $precioTotal = (float) ($ofertaOrigen['precio_total'] ?? 0);
        $calcularPrecioUnidad = new CalcularPrecioUnidad();
        $precioUnidad = $calcularPrecioUnidad->calcular(
            $producto->unidadDeMedida ?? 'unidad',
            $precioTotal,
            $unidades
        );
        if ($precioUnidad === null) {
            $precioUnidad = $precioTotal / max(0.01, $unidades);
        }

        [$envio, $envioPlaceholderGratis] = $this->calcularEnvioDesdeTienda($tienda);
        $envioFinal = $envioPlaceholderGratis ? null : (is_numeric($envio) ? round((float) $envio, 2) : null);

        $resolverScraping = new TiendaScrapingConfigResolver();
        $frecuenciaMinutos = $resolverScraping->resolverFrecuenciaInicialOferta(
            $tienda,
            $producto->categoria_id ? (int) $producto->categoria_id : null
        );

        $precioCero = $precioTotal < 0.0001 || $precioUnidad < 0.0001;
        $mostrarOrigen = strtolower(trim((string) ($ofertaOrigen['mostrar'] ?? 'si')));
        $mostrar = ($precioCero || $mostrarOrigen === 'no') ? 'no' : 'si';

        return [
            'indice' => $indice,
            'importable' => true,
            'estado' => $estado,
            'estado_texto' => $estadoTexto,
            'url' => $url,
            'tienda_origen_nombre' => $tiendaOrigenNombre,
            'tienda_id' => $tienda->id,
            'tienda_nombre' => $tienda->nombre,
            'unidades' => $unidades,
            'precio_total' => round($precioTotal, 2),
            'precio_unidad' => $precioUnidad,
            'envio' => $envioFinal,
            'envio_texto' => $envioFinal === null ? 'Gratis' : number_format($envioFinal, 2, ',', '.') . ' €',
            'frecuencia_minutos' => $frecuenciaMinutos,
            'frecuencia_texto' => $this->formatearFrecuencia($frecuenciaMinutos),
            'mostrar' => $mostrar,
            'como_scrapear' => $this->obtenerComoScrapearTienda($tienda),
            'descuentos' => trim((string) ($ofertaOrigen['descuentos'] ?? '')),
            'variante' => $ofertaOrigen['variante'] ?? null,
            'especificaciones_internas' => $this->normalizarEspecificaciones($ofertaOrigen['especificaciones_internas'] ?? null),
            'anotaciones_internas' => $ofertaOrigen['anotaciones_internas'] ?? null,
            'seleccionada' => !$this->esTiendaMiravia($tienda->nombre, $url, $tiendaOrigenNombre),
        ];
    }

    private function filaNoImportable(int $indice, array $ofertaOrigen, string $estado, string $estadoTexto, string $detalle): array
    {
        $url = trim((string) ($ofertaOrigen['url'] ?? ''));
        $tiendaOrigenNombre = trim((string) ($ofertaOrigen['tienda']['nombre'] ?? ''));

        return [
            'indice' => $indice,
            'importable' => false,
            'seleccionada' => false,
            'estado' => $estado,
            'estado_texto' => $estadoTexto,
            'detalle' => $detalle,
            'url' => $url,
            'tienda_origen_nombre' => $tiendaOrigenNombre,
            'unidades' => $ofertaOrigen['unidades'] ?? null,
            'precio_total' => $ofertaOrigen['precio_total'] ?? null,
            'precio_unidad' => $ofertaOrigen['precio_unidad'] ?? null,
        ];
    }

    private function esTiendaMiravia(?string $tiendaNombre, string $url, ?string $tiendaOrigenNombre = null): bool
    {
        foreach ([$tiendaNombre, $tiendaOrigenNombre] as $nombre) {
            if ($nombre !== null && $nombre !== '' && stripos(trim($nombre), 'miravia') !== false) {
                return true;
            }
        }

        $urlLower = strtolower($url);

        return str_contains($urlLower, 'miravia.es')
            || str_contains($urlLower, 'miravia.com')
            || str_contains($urlLower, 'miravia.');
    }

    private function crearOfertaDesdeFila(array $fila, Producto $producto): OfertaProducto
    {
        $datos = [
            'producto_id' => $producto->id,
            'tienda_id' => $fila['tienda_id'],
            'unidades' => $fila['unidades'],
            'precio_total' => $fila['precio_total'],
            'precio_unidad' => $fila['precio_unidad'],
            'envio' => $fila['envio'],
            'url' => $fila['url'],
            'variante' => $fila['variante'],
            'descuentos' => $fila['descuentos'] ?? '',
            'mostrar' => $fila['mostrar'],
            'como_scrapear' => $fila['como_scrapear'],
            'anotaciones_internas' => $fila['anotaciones_internas'],
            'especificaciones_internas' => $fila['especificaciones_internas'],
            'frecuencia_actualizar_precio_minutos' => $fila['frecuencia_minutos'],
            'fecha_actualizacion_envio' => now(),
        ];

        $oferta = OfertaProducto::create($datos);

        if ($fila['mostrar'] === 'no' || $fila['precio_total'] < 0.0001) {
            Aviso::create([
                'texto_aviso' => 'Sin stock - 1a vez (importado desde chollo)',
                'fecha_aviso' => now()->addDays(4)->setTime(0, 1, 0),
                'user_id' => auth()->id() ?? 1,
                'avisoable_type' => OfertaProducto::class,
                'avisoable_id' => $oferta->id,
                'oculto' => false,
            ]);
        }

        return $oferta;
    }

    private function recalcularOfertaMasBarata(Producto $producto): void
    {
        try {
            $servicioOfertas = new SacarPrimeraOfertaDeUnProductoAplicadoDescuentosYChollos();
            $ofertaMasBarata = $servicioOfertas->obtener($producto);
            if (!$ofertaMasBarata) {
                return;
            }

            $ofertaOriginal = OfertaProducto::find($ofertaMasBarata->id);
            if (!$ofertaOriginal) {
                return;
            }

            ProductoOfertaMasBarataPorProducto::updateOrCreate(
                ['producto_id' => $producto->id],
                [
                    'oferta_id' => $ofertaOriginal->id,
                    'tienda_id' => $ofertaOriginal->tienda_id,
                    'precio_total' => $ofertaMasBarata->precio_total,
                    'precio_unidad' => $ofertaMasBarata->precio_unidad,
                    'unidades' => $ofertaOriginal->unidades,
                    'url' => $ofertaOriginal->url,
                ]
            );

            $precioReal = $ofertaMasBarata->precio_unidad;
            if ($precioReal > 0) {
                $producto->precio = $producto->unidadDeMedida === 'unidadMilesima'
                    ? round($precioReal, 3)
                    : $precioReal;
                $producto->save();
            }
        } catch (\Throwable $e) {
            \Log::error('Error recalcular oferta mas barata import chollo: ' . $e->getMessage());
        }
    }

    private function detectarTienda(array $ofertaOrigen, string $url, $todasLasTiendas): ?Tienda
    {
        $tienda = $this->detectarTiendaPorUrl($url, $todasLasTiendas);
        if ($tienda) {
            return $tienda;
        }

        $nombreOrigen = mb_strtolower(trim((string) ($ofertaOrigen['tienda']['nombre'] ?? '')));
        if ($nombreOrigen !== '') {
            foreach ($todasLasTiendas as $t) {
                if (mb_strtolower(trim((string) $t->nombre)) === $nombreOrigen) {
                    return $t;
                }
            }
        }

        $urlTiendaOrigen = trim((string) ($ofertaOrigen['tienda']['url'] ?? ''));
        if ($urlTiendaOrigen !== '') {
            return $this->detectarTiendaPorUrl($urlTiendaOrigen, $todasLasTiendas);
        }

        return null;
    }

    private function detectarTiendaPorUrl(string $url, $todasLasTiendas): ?Tienda
    {
        try {
            $urlParaParsear = trim($url);
            if ($urlParaParsear === '') {
                return null;
            }
            if (!preg_match('#^https?://#i', $urlParaParsear)) {
                $urlParaParsear = 'https://' . $urlParaParsear;
            }
            $parsed = parse_url($urlParaParsear);
            $hostUser = strtolower($parsed['host'] ?? '');
            $hostUser = preg_replace('/^www\./', '', $hostUser);
            if ($hostUser === '') {
                return null;
            }

            foreach ($todasLasTiendas as $t) {
                $tu = trim($t->url ?? '');
                if ($tu === '') {
                    continue;
                }
                $tu = preg_replace('#^https?://#i', '', $tu);
                $tu = preg_replace('/^www\./i', '', strtolower($tu));
                $tu = preg_replace('#/.*$#', '', $tu);
                $tu = rtrim($tu, '/');
                if ($tu === '' || !str_contains($tu, '.')) {
                    continue;
                }
                if ($hostUser === $tu || str_ends_with($hostUser, '.' . $tu) || str_ends_with($tu, '.' . $hostUser)) {
                    return $t;
                }
            }

            $mejor = null;
            $mejorLongitud = 0;
            foreach ($todasLasTiendas as $t) {
                foreach ($this->clavesHostTiendaDetectar($t) as $clave) {
                    if (strlen($clave) < 4) {
                        continue;
                    }
                    if (str_contains($hostUser, $clave) && strlen($clave) > $mejorLongitud) {
                        $mejor = $t;
                        $mejorLongitud = strlen($clave);
                    }
                }
            }

            return $mejor;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function normalizarClaveTiendaDetectar(string $texto): string
    {
        $s = Str::ascii(mb_strtolower(trim($texto)));

        return preg_replace('/[^a-z0-9]/', '', $s) ?? '';
    }

    private function clavesHostTiendaDetectar(Tienda $tienda): array
    {
        $claves = [];
        $nombre = $this->normalizarClaveTiendaDetectar((string) ($tienda->nombre ?? ''));
        if ($nombre !== '') {
            $claves[] = $nombre;
        }

        $url = trim((string) ($tienda->url ?? ''));
        if ($url !== '') {
            $url = preg_replace('#^https?://#i', '', $url);
            $url = preg_replace('/^www\./i', '', strtolower($url));
            $host = preg_replace('#/.*$#', '', $url);
            $host = preg_replace('/[^a-z0-9.]/', '', $host);
            if ($host !== '') {
                $claves[] = preg_replace('/[^a-z0-9]/', '', explode('.', $host)[0] ?? '') ?? '';
            }
        }

        return array_values(array_filter(array_unique($claves)));
    }

    private function calcularEnvioDesdeTienda(Tienda $tienda): array
    {
        $texto = trim((string) ($tienda->envio_gratis ?? ''));
        if ($texto === '') {
            $texto = trim((string) ($tienda->envio_normal ?? ''));
        }
        if ($texto === '') {
            return [null, false];
        }
        if (stripos($texto, 'gratis') !== false) {
            return [null, true];
        }

        $textoNorm = str_replace(['‚', '，', '٫', "\xC2\xA0"], [',', ',', ',', ' '], $texto);
        if (preg_match('/(\d+[.,]\d+)/', $textoNorm, $m) && isset($m[1])) {
            $valor = (float) str_replace(',', '.', $m[1]);

            return [$valor > 0 ? $valor : null, false];
        }
        if (preg_match('/(\d+[,.]?\d*)\s*€?/', $textoNorm, $m) && isset($m[1]) && $m[1] !== '') {
            $valor = (float) str_replace(',', '.', $m[1]);

            return [$valor > 0 ? $valor : null, false];
        }

        return [null, false];
    }

    private function obtenerComoScrapearTienda(Tienda $tienda): string
    {
        $cs = strtolower(trim($tienda->como_scrapear ?? 'manual'));

        return in_array($cs, ['automatico', 'manual', 'ambos']) ? ($cs === 'ambos' ? 'automatico' : $cs) : 'manual';
    }

    private function normalizarUrl(string $url): string
    {
        return app(LimpiarUrlDeTiendas::class)->limpiar($url);
    }

    private function normalizarEspecificaciones($valor): ?array
    {
        if (is_array($valor) && !empty($valor)) {
            return $valor;
        }
        if (is_string($valor) && $valor !== '') {
            $decoded = json_decode($valor, true);

            return is_array($decoded) && !empty($decoded) ? $decoded : null;
        }

        return null;
    }

    private function formatearFrecuencia(int $minutos): string
    {
        if ($minutos >= 1440 && $minutos % 1440 === 0) {
            $dias = $minutos / 1440;

            return $dias . ' día' . ($dias > 1 ? 's' : '');
        }
        if ($minutos >= 60 && $minutos % 60 === 0) {
            $horas = $minutos / 60;

            return $horas . ' h';
        }

        return $minutos . ' min';
    }
}
