<?php

namespace App\Http\Controllers\Crons;

use App\Http\Controllers\Controller;
use App\Models\Aviso;
use App\Models\EjecucionGlobal;
use App\Models\OfertaProducto;
use App\Services\CalcularPrecioUnidad;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Scraping\ScrapingController;
use App\Services\CsvAwinOfertaService;
use App\Services\TiendaScrapingConfigResolver;

/**
 * Cron que procesa avisos de tipo OfertaProducto (sin stock) vencidos:
 * - Tienda debe tener avisos_sin_stock_scrapear_automatico = 'si'
 * - Intenta obtener precio vía ScrapingController (misma lógica que test-precio)
 * - Si no hay precio: aplaza el aviso según 1a/2a/3a... vez (mismos rangos que AvisoController::aplazar)
 * - Si hay precio: actualiza oferta (precio_total, precio_unidad, mostrar=si), borra el aviso sin stock y crea aviso tipo oferta "Oferta Resucitada"
 * - Si no hay precio: solo se aplaza el aviso (no se crea aviso)
 * - Límite: 50 avisos por ejecución
 * - Solo se procesan avisos cuyo texto contiene "Xa vez" (1a a 9a vez). Sin ese patrón o 10a+ se ignoran.
 */
class AvisosSinStockScrapearCronController extends Controller
{
    public const NOMBRE_EJECUCION_GLOBAL = 'cron_avisos_sin_stock_scrapear';

    private const LIMITE_AVISOS_POR_EJECUCION = 50;
    private const RETENCION_EJECUCIONES_DIAS = 30;

    /** A partir de esta vez (ej. 10a vez) el aviso no se toca y queda para revisión manual */
    private const VEZ_MAXIMA_PROCESAR = 9;

    public function __construct(
        private readonly CsvAwinOfertaService $csvAwinOfertaService,
        private readonly TiendaScrapingConfigResolver $tiendaScrapingConfigResolver,
    ) {}

    public function __invoke(): int
    {
        $inicio = now();
        $ejecucion = EjecucionGlobal::create([
            'inicio'         => $inicio,
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
                'candidatos_en_query'     => $avisos->count(),
                'omitido_sin_oferta'      => 0,
                'omitido_segunda_mano'    => 0,
                'omitido_tienda_flag'     => 0,
                'omitido_sin_patron_vez'  => 0,
                'omitido_vez_maxima'      => 0,
                'elegibles_antes_limite'  => 0,
                'elegibles_descartados_por_limite' => 0,
                'procesados'              => 0,
            ];

            $resultados = [];
            $precioAplicados = 0;
            $aplazados = 0;
            $avisosElegibles = [];

            foreach ($avisos as $aviso) {
                $oferta = $aviso->avisoable;
                if (!$oferta || !($oferta instanceof OfertaProducto)) {
                    $contadores['omitido_sin_oferta']++;
                    continue;
                }
                if (stripos((string) $aviso->texto_aviso, 'segunda mano') !== false) {
                    $contadores['omitido_segunda_mano']++;
                    continue;
                }
                $tienda = $oferta->tienda;
                if (!$tienda || $tienda->avisos_sin_stock_scrapear_automatico !== 'si') {
                    $contadores['omitido_tienda_flag']++;
                    continue;
                }
                if (!preg_match('/(\d+(?:\.\d+)?)\s*(?:a\s*)?vez/i', $aviso->texto_aviso, $m)) {
                    $contadores['omitido_sin_patron_vez']++;
                    continue;
                }
                $numeroVez = (float) $m[1];
                $esCsvAwin = $this->esOfertaCsvAwin($oferta);
                if ($esCsvAwin) {
                    if ($numeroVez > CsvAwinOfertaService::VEZ_MAXIMA_PROCESAR) {
                        $contadores['omitido_vez_maxima']++;
                        continue;
                    }
                } elseif ((int) $numeroVez > self::VEZ_MAXIMA_PROCESAR) {
                    $contadores['omitido_vez_maxima']++;
                    continue;
                }

                $avisosElegibles[] = [
                    'aviso' => $aviso,
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

            $ejecucion->update([
                'fin'             => now(),
                'total'           => $contadores['procesados'],
                'total_guardado'  => $precioAplicados,
                'total_errores'   => $aplazados,
                'log'             => $logBase,
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
     * @return array{accion: string, precio?: float, scraping: array<string, mixed>}
     */
    private function procesarAviso(Aviso $aviso, OfertaProducto $oferta): array
    {
        if ($this->esOfertaCsvAwin($oferta)) {
            return $this->procesarAvisoCsvAwin($aviso, $oferta);
        }

        $urlOferta = trim((string) $oferta->url);
        if ($urlOferta === '') {
            // Sin URL v2 no se puede scrapear; se aplaza el aviso.
            $this->aplazarAvisoSinStock($aviso);
            return [
                'accion' => 'aplazado',
                'scraping' => [
                    'success' => false,
                    'error' => 'Oferta sin url v2 descifrada (url_cipher/url_lookup).',
                ],
            ];
        }

        $scrapingController = new ScrapingController();
        $request = new Request([
            'url' => $urlOferta,
            'tienda' => $oferta->tienda->nombre,
            'variante' => $oferta->variante ?? null,
        ]);

        // No pasar $oferta para que los controladores de tienda NO creen un aviso "Sin stock 1a vez"
        // cuando no encuentren precio; nosotros actualizamos el aviso existente (2a→3a vez, etc.)
        $response = $scrapingController->obtenerPrecio($request, null);
        $data = $response->getData(true);

        $scrapingResumen = $this->resumirRespuestaScraping(is_array($data) ? $data : []);

        $success = !empty($data['success']) && isset($data['precio']) && is_numeric($data['precio']);
        $precioNuevo = $success ? (float) str_replace(',', '.', (string) $data['precio']) : null;

        if ($success && $precioNuevo !== null) {
            $this->aplicarPrecioYOfertaVisible($aviso, $oferta, $precioNuevo);
            return ['accion' => 'precio_aplicado', 'precio' => $precioNuevo, 'scraping' => $scrapingResumen];
        }
        $this->aplazarAvisoSinStock($aviso);
        return ['accion' => 'aplazado', 'scraping' => $scrapingResumen];
    }

    /**
     * @return array{accion: string, precio?: float, csv: array<string, mixed>}
     */
    private function procesarAvisoCsvAwin(Aviso $aviso, OfertaProducto $oferta): array
    {
        $oferta->loadMissing('tienda');
        $fila = $this->csvAwinOfertaService->buscarFilaPorOferta($oferta);

        $csvResumen = [
            'encontrado' => $fila !== null,
            'stock' => $fila?->stock,
            'precio' => $fila?->precio,
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
                'csv' => $csvResumen,
            ];
        }

        $this->aplazarAvisoSinStockCsvAwin($aviso);

        return [
            'accion' => 'aplazado',
            'csv' => $csvResumen,
        ];
    }

    private function esOfertaCsvAwin(OfertaProducto $oferta): bool
    {
        return $this->tiendaScrapingConfigResolver->resolverApiParaOferta($oferta)
            === TiendaScrapingConfigResolver::API_CSV_AWIN;
    }

    private function aplazarAvisoSinStockCsvAwin(Aviso $aviso): void
    {
        $calc = $this->csvAwinOfertaService->calcularSiguienteAplazamientoSinStock($aviso);
        $aviso->update([
            'texto_aviso' => $calc['nuevo_texto'],
            'fecha_aviso' => $calc['nueva_fecha'],
        ]);
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

    /**
     * Aplica el precio a la oferta, precio_unidad según unidad de medida, mostrar=si y elimina solo este aviso.
     */
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
            'precio_total' => round($precioTotal, 2),
            'precio_unidad' => $precioUnidad,
            'mostrar' => 'si',
        ]);

        $aviso->delete();

        // Aviso de tipo oferta: "Oferta Resucitada" con fecha actual
        Aviso::create([
            'texto_aviso' => 'Oferta Resucitada',
            'fecha_aviso' => now(),
            'user_id' => 1,
            'avisoable_type' => OfertaProducto::class,
            'avisoable_id' => $oferta->id,
            'oculto' => false,
        ]);
    }

    /**
     * Calcula el siguiente texto y fecha de aplazamiento (misma lógica que AvisoController::aplazar).
     *
     * @return array{nuevo_texto: string, nueva_fecha: Carbon}
     */
    private function calcularSiguienteAplazamiento(Aviso $aviso): array
    {
        $textoAviso = $aviso->texto_aviso;
        $numeroActual = 0;
        if (preg_match('/(\d+)\s*(?:a\s*)?vez/i', $textoAviso, $matches)) {
            $numeroActual = (int) $matches[1];
        }

        $fechaActual = Carbon::parse($aviso->fecha_aviso);

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

    /**
     * Aplaza el aviso según 1a/2a/3a... vez (misma lógica que AvisoController::aplazar).
     */
    private function aplazarAvisoSinStock(Aviso $aviso): void
    {
        $calc = $this->calcularSiguienteAplazamiento($aviso);
        $aviso->update([
            'texto_aviso' => $calc['nuevo_texto'],
            'fecha_aviso' => $calc['nueva_fecha'],
        ]);
    }

    /**
     * Borra ejecuciones antiguas de este cron para evitar crecimiento indefinido.
     */
    private function eliminarEjecucionesAntiguas(): void
    {
        try {
            EjecucionGlobal::query()
                ->where('nombre', self::NOMBRE_EJECUCION_GLOBAL)
                ->where('created_at', '<', now()->subDays(self::RETENCION_EJECUCIONES_DIAS))
                ->delete();
        } catch (\Throwable $e) {
            // No bloquear el cron por fallo en limpieza histórica.
        }
    }

}
