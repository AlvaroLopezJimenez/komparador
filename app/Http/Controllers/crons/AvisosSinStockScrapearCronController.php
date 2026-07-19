<?php

namespace App\Http\Controllers\Crons;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Scraping\ScrapingController;
use App\Models\Aviso;
use App\Models\EjecucionGlobal;
use App\Models\OfertaProducto;
use App\Services\CalcularPrecioUnidad;
use App\Services\CsvAwinOfertaService;
use App\Services\TiendaScrapingConfigResolver;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Cron que procesa avisos vencidos de oferta oculta (sin stock, URL CSV, 404, reacondicionado).
 * Los avisos de segunda mano se omiten (revisión manual).
 *
 * - Usa la API efectiva actual de la oferta (CSV-Awin o scraping).
 * - Si cambió la API, adapta el texto del aviso (p. ej. CSV 1.5a → Sin stock 2a, o Sin stock 2a → URL CSV 2.1a).
 * - Solo un aviso de estos motivos por oferta.
 * - Con precio/stock: actualiza oferta, mostrar=si, elimina aviso y crea «Oferta Resucitada».
 * - Sin stock/precio: aplaza con el calendario CSV (+0.1 vez, +1 día) o scraping (1a→2a→3a…).
 */
class AvisosSinStockScrapearCronController extends Controller
{
    public const NOMBRE_EJECUCION_GLOBAL = 'cron_avisos_sin_stock_scrapear';

    private const LIMITE_AVISOS_POR_EJECUCION = 50;

    private const RETENCION_EJECUCIONES_DIAS = 30;

    private const VEZ_MAXIMA_PROCESAR_SCRAPING = 9;

    private const MOTIVO_SIN_STOCK = 'sin_stock';

    private const MOTIVO_URL_CSV = 'url_csv';

    private const MOTIVO_404 = '404';

    private const MOTIVO_SEGUNDA_MANO = 'segunda_mano';

    private const MOTIVO_REACONDICIONADO = 'reacondicionado';

    public function __construct(
        private readonly CsvAwinOfertaService $csvAwinOfertaService,
        private readonly TiendaScrapingConfigResolver $tiendaScrapingConfigResolver,
    ) {}

    public function __invoke(): int
    {
        $ejecucion = EjecucionGlobal::create([
            'inicio'         => now(),
            'fin'            => null,
            'nombre'         => self::NOMBRE_EJECUCION_GLOBAL,
            'total'          => 0,
            'total_guardado' => 0,
            'total_errores'  => 0,
            'log'            => [
                'estado'      => 'running',
                'paso_actual' => 'consulta',
                'pasos'       => [
                    ['momento' => now()->toDateTimeString(), 'paso' => 'inicio', 'detalle' => 'Ejecución creada', 'contexto' => []],
                ],
            ],
        ]);

        try {
            $avisos = Aviso::query()
                ->where('avisoable_type', OfertaProducto::class)
                ->where('fecha_aviso', '<', now())
                ->orderBy('fecha_aviso')
                ->with(['avisoable.tienda', 'avisoable.producto'])
                ->get();

            $this->actualizarEjecucionPaso($ejecucion, 'iteracion', [
                'detalle' => 'Candidatos en query (todos los avisos vencidos)',
                'candidatos_en_query' => $avisos->count(),
            ]);

            $contadores = [
                'candidatos_en_query'              => $avisos->count(),
                'omitido_sin_oferta'               => 0,
                'omitido_segunda_mano'             => 0,
                'omitido_tipo_no_gestionable'      => 0,
                'omitido_tienda_flag'              => 0,
                'omitido_vez_maxima'               => 0,
                'elegibles_antes_limite'           => 0,
                'elegibles_descartados_por_limite' => 0,
                'procesados'                       => 0,
            ];

            $resultados = [];
            $precioAplicados = 0;
            $aplazados = 0;
            $avisosElegibles = [];

            foreach ($avisos as $aviso) {
                $oferta = $aviso->avisoable;
                if (! $oferta || ! ($oferta instanceof OfertaProducto)) {
                    $contadores['omitido_sin_oferta']++;
                    continue;
                }

                if (stripos((string) $aviso->texto_aviso, 'segunda mano') !== false) {
                    $contadores['omitido_segunda_mano']++;
                    continue;
                }

                if (! $this->esAvisoGestionableCron((string) $aviso->texto_aviso)) {
                    $contadores['omitido_tipo_no_gestionable']++;
                    continue;
                }

                $tienda = $oferta->tienda;
                if (! $tienda || $tienda->avisos_sin_stock_scrapear_automatico !== 'si') {
                    $contadores['omitido_tienda_flag']++;
                    continue;
                }

                $numeroVez = $this->extraerNumeroVez((string) $aviso->texto_aviso);
                if ($this->esOfertaCsvAwin($oferta)) {
                    if ($numeroVez > CsvAwinOfertaService::VEZ_MAXIMA_PROCESAR) {
                        $contadores['omitido_vez_maxima']++;
                        continue;
                    }
                } elseif ((int) $numeroVez > self::VEZ_MAXIMA_PROCESAR_SCRAPING) {
                    $contadores['omitido_vez_maxima']++;
                    continue;
                }

                $avisosElegibles[] = [
                    'aviso'  => $aviso,
                    'oferta' => $oferta,
                    'tienda' => $tienda,
                ];
            }

            $contadores['elegibles_antes_limite'] = count($avisosElegibles);
            $avisosAProcesar = array_slice($avisosElegibles, 0, self::LIMITE_AVISOS_POR_EJECUCION);
            $contadores['elegibles_descartados_por_limite'] = max(0, count($avisosElegibles) - count($avisosAProcesar));

            $this->actualizarEjecucionPaso($ejecucion, 'procesado', [
                'detalle' => 'Filtrados elegibles y aplicado límite final de ' . self::LIMITE_AVISOS_POR_EJECUCION,
                'elegibles_antes_limite' => $contadores['elegibles_antes_limite'],
                'elegibles_descartados_por_limite' => $contadores['elegibles_descartados_por_limite'],
            ]);

            foreach ($avisosAProcesar as $item) {
                /** @var Aviso $aviso */
                $aviso = $item['aviso'];
                /** @var OfertaProducto $oferta */
                $oferta = $item['oferta'];
                $tienda = $item['tienda'];

                $ret = $this->procesarAviso($aviso, $oferta);
                $contadores['procesados']++;
                if (($ret['accion'] ?? '') === 'precio_aplicado') {
                    $precioAplicados++;
                } else {
                    $aplazados++;
                }
                $resultados[] = array_merge($ret, [
                    'aviso_id'   => $aviso->id,
                    'oferta_id'  => $oferta->id,
                    'oferta_url' => $oferta->url,
                    'tienda'     => $tienda->nombre ?? null,
                ]);
            }

            $ejecucion->refresh();
            $logBase = is_array($ejecucion->log) ? $ejecucion->log : [];
            $logBase['estado'] = 'ok';
            $logBase['paso_actual'] = 'finalizado';
            $logBase['contadores'] = $contadores;
            $logBase['resultados'] = $resultados;
            $logBase['aplazados'] = $aplazados;
            $logBase['precio_aplicados'] = $precioAplicados;

            $ejecucion->update([
                'fin'            => now(),
                'total'          => $contadores['procesados'],
                'total_guardado' => $precioAplicados,
                // Aplazados = funcionamiento normal (sin stock / reintento), no son errores
                'total_errores'  => 0,
                'log'            => $logBase,
            ]);

            $this->eliminarEjecucionesAntiguas();

            return 0;
        } catch (\Throwable $e) {
            $this->actualizarEjecucionError($ejecucion, $e);
            $this->eliminarEjecucionesAntiguas();
            throw $e;
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function procesarAviso(Aviso $aviso, OfertaProducto $oferta): array
    {
        $this->consolidarAvisoUnicoOcultacion($oferta, $aviso);
        $this->reconciliarAvisoConApiActual($aviso, $oferta);
        $aviso->refresh();

        if ($this->esOfertaCsvAwin($oferta)) {
            return $this->procesarAvisoCsvAwin($aviso, $oferta);
        }

        return $this->procesarAvisoScraping($aviso, $oferta);
    }

    /**
     * @return array<string, mixed>
     */
    private function procesarAvisoScraping(Aviso $aviso, OfertaProducto $oferta): array
    {
        $urlOferta = trim((string) $oferta->url);
        if ($urlOferta === '') {
            $this->aplazarAvisoSegunTipo($aviso);

            return [
                'accion'   => 'aplazado',
                'scraping' => [
                    'success' => false,
                    'error'   => 'Oferta sin url v2 descifrada (url_cipher/url_lookup).',
                ],
            ];
        }

        $scrapingController = new ScrapingController();
        $request = new Request([
            'url'         => $urlOferta,
            'tienda'      => $oferta->tienda->nombre,
            'variante'    => $oferta->variante ?? null,
            'producto_id' => $oferta->producto_id,
        ]);

        // Sin $oferta: no crear avisos nuevos en el scraper; gestionamos el existente.
        $response = $scrapingController->obtenerPrecio($request, null);
        $data = $response->getData(true);
        $scrapingResumen = $this->resumirRespuestaScraping(is_array($data) ? $data : []);

        $success = ! empty($data['success']) && isset($data['precio']) && is_numeric($data['precio']);
        $precioNuevo = $success ? (float) str_replace(',', '.', (string) $data['precio']) : null;

        if ($success && $precioNuevo !== null) {
            $this->aplicarPrecioYOfertaVisible($aviso, $oferta, $precioNuevo);

            return ['accion' => 'precio_aplicado', 'precio' => $precioNuevo, 'scraping' => $scrapingResumen];
        }

        $oferta->update(['mostrar' => 'no']);
        $this->aplazarAvisoSegunTipo($aviso);

        return ['accion' => 'aplazado', 'scraping' => $scrapingResumen];
    }

    /**
     * @return array<string, mixed>
     */
    private function procesarAvisoCsvAwin(Aviso $aviso, OfertaProducto $oferta): array
    {
        $oferta->loadMissing('tienda');
        $fila = $this->csvAwinOfertaService->buscarFilaPorOferta($oferta);
        $textoAviso = (string) $aviso->texto_aviso;
        $esAvisoUrlNoEncontrada = $this->csvAwinOfertaService->esAvisoUrlCsvNoEncontrada($textoAviso);

        $csvResumen = [
            'encontrado' => $fila !== null,
            'stock'      => $fila?->stock,
            'precio'     => $fila?->precio,
        ];

        if (
            $fila !== null
            && $this->csvAwinOfertaService->tieneStock($fila)
            && $fila->precio !== null
        ) {
            $precioNuevo = (float) $fila->precio;
            $this->csvAwinOfertaService->sincronizarEnvioOfertaSiDiferente($oferta, $fila);
            $this->aplicarPrecioYOfertaVisible($aviso, $oferta, $precioNuevo);

            return [
                'accion' => 'precio_aplicado',
                'precio' => $precioNuevo,
                'csv'    => $csvResumen,
            ];
        }

        if (
            $fila !== null
            && $this->csvAwinOfertaService->tieneSinStock($fila)
            && $esAvisoUrlNoEncontrada
        ) {
            $this->csvAwinOfertaService->convertirAvisoUrlCsvASinStock($aviso, $oferta);

            return [
                'accion' => 'convertido_sin_stock',
                'csv'    => $csvResumen,
            ];
        }

        if ($fila !== null && $this->csvAwinOfertaService->tieneSinStock($fila)) {
            $oferta->update(['mostrar' => 'no']);
        }

        $this->aplazarAvisoSegunTipo($aviso);

        return [
            'accion' => 'aplazado',
            'csv'    => $csvResumen,
        ];
    }

    private function esOfertaCsvAwin(OfertaProducto $oferta): bool
    {
        return $this->tiendaScrapingConfigResolver->resolverApiParaOferta($oferta)
            === TiendaScrapingConfigResolver::API_CSV_AWIN;
    }

    private function esAvisoGestionableCron(string $textoAviso): bool
    {
        if (stripos($textoAviso, 'segunda mano') !== false) {
            return false;
        }

        if ($this->detectarMotivoAviso($textoAviso) === null) {
            return false;
        }

        return (bool) preg_match('/(\d+(?:\.\d+)?)\s*(?:a\s*)?vez/i', $textoAviso);
    }

    private function detectarMotivoAviso(string $textoAviso): ?string
    {
        $texto = mb_strtolower($textoAviso);

        if (str_contains($texto, mb_strtolower(CsvAwinOfertaService::PREFIJO_AVISO_URL_CSV_NO_ENCONTRADA))) {
            return self::MOTIVO_URL_CSV;
        }

        if (preg_match('/404/u', $textoAviso) === 1) {
            return self::MOTIVO_404;
        }

        if (str_contains($texto, 'segunda mano')) {
            return self::MOTIVO_SEGUNDA_MANO;
        }

        if (str_contains($texto, 'reacondicionado')) {
            return self::MOTIVO_REACONDICIONADO;
        }

        if (str_contains($texto, 'sin stock')) {
            return self::MOTIVO_SIN_STOCK;
        }

        return null;
    }

    private function extraerNumeroVez(string $textoAviso): float
    {
        if (preg_match('/(\d+(?:\.\d+)?)\s*(?:a\s*)?vez/i', $textoAviso, $matches)) {
            return (float) $matches[1];
        }

        return 0.0;
    }

    /**
     * Adapta el texto del aviso a la API efectiva actual (CSV ↔ scraping).
     */
    private function reconciliarAvisoConApiActual(Aviso $aviso, OfertaProducto $oferta): void
    {
        $texto = (string) $aviso->texto_aviso;
        $motivo = $this->detectarMotivoAviso($texto);
        if ($motivo === null) {
            return;
        }

        $vez = $this->extraerNumeroVez($texto);
        $apiEsCsv = $this->esOfertaCsvAwin($oferta);
        $nuevoTexto = null;

        if ($apiEsCsv) {
            if ($motivo === self::MOTIVO_URL_CSV) {
                return;
            }

            if ($motivo === self::MOTIVO_SIN_STOCK && $this->esTextoSinStockCsv($texto)) {
                return;
            }

            $vezCsv = $this->convertirVezScrapingACsv($vez);
            if ($motivo === self::MOTIVO_SIN_STOCK) {
                $nuevoTexto = $this->textoSinStockCsv($vezCsv);
            } else {
                $nuevoTexto = $this->textoUrlCsv($vezCsv);
            }
        } else {
            if ($motivo === self::MOTIVO_URL_CSV || $this->esTextoSinStockCsv($texto)) {
                $vezEntera = $this->convertirVezCsvAScraping($vez);
                $nuevoTexto = $this->textoSinStockScraping($vezEntera);
            } elseif (in_array($motivo, [self::MOTIVO_SIN_STOCK, self::MOTIVO_404, self::MOTIVO_SEGUNDA_MANO, self::MOTIVO_REACONDICIONADO], true)) {
                $nuevoTexto = $this->textoScrapingPorMotivo($motivo, max(1, (int) round($vez)), $texto);
            }
        }

        if ($nuevoTexto !== null && $nuevoTexto !== $texto) {
            $aviso->update(['texto_aviso' => $nuevoTexto]);
        }
    }

    /** Solo un aviso de ocultación (sin stock, CSV, 404, etc.) por oferta. */
    private function consolidarAvisoUnicoOcultacion(OfertaProducto $oferta, Aviso $avisoMantener): void
    {
        Aviso::query()
            ->where('avisoable_type', OfertaProducto::class)
            ->where('avisoable_id', $oferta->id)
            ->where('id', '!=', $avisoMantener->id)
            ->get()
            ->each(function (Aviso $otro) {
                if ($this->esAvisoGestionableCron((string) $otro->texto_aviso)) {
                    $otro->delete();
                }
            });
    }

    private function convertirVezCsvAScraping(float $vezCsv): int
    {
        return max(1, (int) ceil($vezCsv));
    }

    private function convertirVezScrapingACsv(float $vezScraping): float
    {
        $base = $vezScraping <= 0 ? 1.0 : $vezScraping;

        return round($base + CsvAwinOfertaService::INCREMENTO_VEZ_SIN_STOCK, 1);
    }

    private function textoUrlCsv(float $vez): string
    {
        return 'No encontrada URL CSV ' . $this->formatearNumeroVez($vez) . 'a vez - Generado automaticamente';
    }

    private function textoSinStockCsv(float $vez): string
    {
        return 'Sin stock ' . $this->formatearNumeroVez($vez) . 'a vez - Generado automaticamente';
    }

    private function textoSinStockScraping(int $vez): string
    {
        return 'Sin stock ' . $vez . 'a vez - Generado automaticamente';
    }

    private function textoScrapingPorMotivo(string $motivo, int $vez, string $textoOriginal): string
    {
        $texto = match ($motivo) {
            self::MOTIVO_404 => '404 - ' . $vez . 'a vez',
            self::MOTIVO_SEGUNDA_MANO => 'Segunda mano ' . $vez . 'a vez',
            self::MOTIVO_REACONDICIONADO => 'Reacondicionado ' . $vez . 'a vez - Generado automaticamente',
            default => $this->textoSinStockScraping($vez),
        };

        if (
            (str_contains($textoOriginal, 'Generado automaticamente') || str_contains($textoOriginal, 'GENERADO'))
            && ! str_contains($texto, 'Generado')
            && ! str_contains($texto, 'GENERADO')
        ) {
            return $texto . (str_contains(mb_strtoupper($textoOriginal), 'GENERADO AUTOMATICAMENTE')
                ? ' - GENERADO AUTOMATICAMENTE'
                : ' - Generado automaticamente');
        }

        return $texto;
    }

    private function esTextoSinStockCsv(string $textoAviso): bool
    {
        if (! str_contains(mb_strtolower($textoAviso), 'sin stock')) {
            return false;
        }

        return fmod($this->extraerNumeroVez($textoAviso), 1.0) !== 0.0;
    }

    private function usaAplazamientoCsv(string $textoAviso): bool
    {
        $motivo = $this->detectarMotivoAviso($textoAviso);

        return $motivo === self::MOTIVO_URL_CSV
            || ($motivo === self::MOTIVO_SIN_STOCK && $this->esTextoSinStockCsv($textoAviso));
    }

    private function aplazarAvisoSegunTipo(Aviso $aviso): void
    {
        if ($this->usaAplazamientoCsv((string) $aviso->texto_aviso)) {
            $calc = $this->csvAwinOfertaService->calcularSiguienteAplazamientoSinStock($aviso);
        } else {
            $calc = $this->calcularSiguienteAplazamientoScraping($aviso);
        }

        $aviso->update([
            'texto_aviso' => $calc['nuevo_texto'],
            'fecha_aviso' => $calc['nueva_fecha'],
        ]);
    }

    private function formatearNumeroVez(float $numero): string
    {
        if (fmod($numero, 1.0) === 0.0) {
            return (string) (int) $numero;
        }

        return rtrim(rtrim(number_format($numero, 1, '.', ''), '0'), '.');
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function resumirRespuestaScraping(array $data): array
    {
        $out = [];
        foreach (['success', 'precio', 'error', 'mensaje'] as $k) {
            if (array_key_exists($k, $data)) {
                $out[$k] = $data[$k];
            }
        }

        return $out;
    }

    private function actualizarEjecucionPaso(EjecucionGlobal $ejecucion, string $paso, array $contexto = []): void
    {
        try {
            $log = is_array($ejecucion->log) ? $ejecucion->log : [];
            $pasos = isset($log['pasos']) && is_array($log['pasos']) ? $log['pasos'] : [];
            $detalle = $contexto['detalle'] ?? null;
            unset($contexto['detalle']);

            $pasos[] = [
                'momento'  => now()->toDateTimeString(),
                'paso'     => $paso,
                'detalle'  => $detalle,
                'contexto' => $contexto,
            ];

            $log['estado'] = 'running';
            $log['paso_actual'] = $paso;
            $log['pasos'] = $pasos;

            $ejecucion->update(['log' => $log]);
        } catch (\Throwable $e) {
            //
        }
    }

    private function actualizarEjecucionError(EjecucionGlobal $ejecucion, \Throwable $e): void
    {
        try {
            $log = is_array($ejecucion->log) ? $ejecucion->log : [];
            $pasoActual = $log['paso_actual'] ?? 'desconocido';
            $pasos = isset($log['pasos']) && is_array($log['pasos']) ? $log['pasos'] : [];
            $pasos[] = [
                'momento'  => now()->toDateTimeString(),
                'paso'     => 'error',
                'detalle'  => 'Excepción no controlada',
                'contexto' => ['paso_en_el_que_fallo' => $pasoActual, 'error' => $e->getMessage()],
            ];

            $log['estado'] = 'error';
            $log['paso_actual'] = 'error';
            $log['error'] = ['mensaje' => $e->getMessage(), 'tipo' => get_class($e)];
            $log['pasos'] = $pasos;

            $ejecucion->update([
                'fin'           => now(),
                'total_errores' => (int) $ejecucion->total_errores + 1,
                'log'           => $log,
            ]);
        } catch (\Throwable $inner) {
            //
        }
    }

    private function aplicarPrecioYOfertaVisible(Aviso $aviso, OfertaProducto $oferta, float $precioTotal): void
    {
        $producto = $oferta->producto;
        $unidadDeMedida = $producto && $producto->unidadDeMedida ? $producto->unidadDeMedida : 'unidad';
        $unidades = (float) $oferta->unidades;
        if ($unidades <= 0) {
            $unidades = 1.0;
        }

        $calcularPrecioUnidad = new CalcularPrecioUnidad();
        $precioUnidad = $calcularPrecioUnidad->calcular($unidadDeMedida, $precioTotal, $unidades);
        if ($precioUnidad === null) {
            $precioUnidad = round($precioTotal / $unidades, 2);
        }

        $oferta->update([
            'precio_total'  => round($precioTotal, 2),
            'precio_unidad' => $precioUnidad,
            'mostrar'       => 'si',
        ]);

        $aviso->delete();

        Aviso::create([
            'texto_aviso'    => 'Oferta Resucitada',
            'fecha_aviso'    => now(),
            'user_id'        => 1,
            'avisoable_type' => OfertaProducto::class,
            'avisoable_id'   => $oferta->id,
            'oculto'         => false,
        ]);
    }

    /**
     * @return array{nuevo_texto: string, nueva_fecha: Carbon}
     */
    private function calcularSiguienteAplazamientoScraping(Aviso $aviso): array
    {
        $textoAviso = (string) $aviso->texto_aviso;
        $numeroActual = 0;
        if (preg_match('/(\d+)\s*(?:a\s*)?vez/i', $textoAviso, $matches)) {
            $numeroActual = (int) $matches[1];
        }

        $fechaActual = now();

        switch ($numeroActual) {
            case 0:
                $nuevaFecha = $fechaActual->copy()->addDay();
                $nuevoNumero = 1;
                break;
            case 1:
                $nuevaFecha = $fechaActual->copy()->addDays(3);
                $nuevoNumero = 2;
                break;
            case 2:
                $nuevaFecha = $fechaActual->copy()->addWeek();
                $nuevoNumero = 3;
                break;
            case 3:
                $nuevaFecha = $fechaActual->copy()->addWeek();
                $nuevoNumero = 4;
                break;
            case 4:
                $nuevaFecha = $fechaActual->copy()->addWeeks(2);
                $nuevoNumero = 5;
                break;
            default:
                $nuevaFecha = $fechaActual->copy()->addMonth();
                $nuevoNumero = $numeroActual + 1;
                break;
        }

        $nuevoTexto = preg_replace('/(\d+)\s*(?:a\s*)?vez/i', $nuevoNumero . 'a vez', $textoAviso, 1);
        if ($numeroActual === 0 && $nuevoTexto === $textoAviso) {
            $nuevoTexto = $textoAviso . ' - 1a vez';
        }

        return [
            'nuevo_texto' => $nuevoTexto,
            'nueva_fecha' => $nuevaFecha,
        ];
    }

    private function eliminarEjecucionesAntiguas(): void
    {
        try {
            EjecucionGlobal::query()
                ->where('nombre', self::NOMBRE_EJECUCION_GLOBAL)
                ->where('created_at', '<', now()->subDays(self::RETENCION_EJECUCIONES_DIAS))
                ->delete();
        } catch (\Throwable $e) {
            //
        }
    }
}
