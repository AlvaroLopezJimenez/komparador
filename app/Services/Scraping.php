<?php

namespace App\Services;

use App\Http\Controllers\DescuentosController;
use App\Http\Controllers\Scraping\ScrapingController;
use App\Models\CorreoAvisoPrecio;
use App\Models\OfertaProducto;
use App\Models\Producto;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Lógica compartida de actualización de precios por scraping (cron web y programa externo).
 */
class Scraping
{
    public const API_NAVEGADOR_LOCAL = 'navegadorLocal';

    /** Nombre de ejecución del programa externo (independiente del cron web). */
    public const NOMBRE_EJECUCION_PROGRAMA_EXTERNO = 'ejecuciones_scrapear_ofertas_programa_externo';

    private const UMBRAL_CAMBIO_ANOMALO = 0.40;

    /**
     * Procesa una oferta: obtiene precio (vía tienda o HTML inyectado), valida, actualiza BD.
     *
     * @return array<string, mixed>
     */
    public function procesarOferta(OfertaProducto $oferta): array
    {
        $oferta->loadMissing(['tienda', 'producto']);

        $serviceTiempos = new TiemposActualizacionOfertasDinamicos();
        $serviceTiempos->calcularFrecuencia($oferta->id);

        $producto = Producto::find($oferta->producto_id);
        $servicioOfertas = new SacarPrimeraOfertaDeUnProductoAplicadoDescuentosYChollos();
        $ofertaMasBarataAntes = $producto ? $servicioOfertas->obtener($producto) : null;
        $ofertasAntes = $ofertaMasBarataAntes ? collect([$ofertaMasBarataAntes]) : collect();

        $scrapingController = new ScrapingController();
        $request = new Request();
        $request->merge([
            'url'      => $oferta->url,
            'tienda'   => $oferta->tienda->nombre,
            'variante' => $oferta->variante ?? null,
        ]);

        $response = $scrapingController->obtenerPrecio($request, $oferta);
        $responseData = $response->getData(true);

        $precioAnterior = $oferta->precio_total;

        if (!$responseData['success']) {
            return $this->resultadoBase($oferta, $precioAnterior, null, false, 'Error en el scraping: ' . ($responseData['error'] ?? 'Error desconocido'));
        }

        if (!isset($responseData['precio']) || !is_numeric($responseData['precio'])) {
            return $this->resultadoBase($oferta, $precioAnterior, null, false, 'Respuesta inválida del scraping: ' . json_encode($responseData));
        }

        $precioNuevo = round((float) str_replace(',', '.', $responseData['precio']), 2);

        $calcularPrecioUnidad = new CalcularPrecioUnidad();
        $precioUnidadNuevo = $calcularPrecioUnidad->calcular(
            $producto->unidadDeMedida ?? 'unidad',
            $precioNuevo,
            $oferta->unidades
        );
        if ($precioUnidadNuevo === null) {
            $precioUnidadNuevo = round($precioNuevo / $oferta->unidades, 2);
        }

        $anomalo = $this->detectarYRegistrarCambioAnomalo($oferta, $precioAnterior, $precioNuevo);
        if ($anomalo !== null) {
            return $anomalo;
        }

        $oferta->update([
            'precio_total'  => $precioNuevo,
            'precio_unidad' => $precioUnidadNuevo,
        ]);
        $oferta->touch();

        $serviceTiempos->registrarActualizacion($oferta->id, $precioNuevo, 'automatico');

        $ofertaMasBarataDespues = $producto ? $servicioOfertas->obtener($producto) : null;
        $ofertasDespues = $ofertaMasBarataDespues ? collect([$ofertaMasBarataDespues]) : collect();

        $cambiosDetectados = $this->aplicarCambiosProductoSiCorresponde(
            $oferta,
            $ofertasAntes,
            $ofertasDespues
        );

        return [
            'oferta_id'                => $oferta->id,
            'tienda_nombre'            => $oferta->tienda->nombre,
            'url'                      => $oferta->url,
            'variante'                 => $oferta->variante,
            'precio_anterior'          => $precioAnterior,
            'precio_nuevo'             => $precioNuevo,
            'success'                  => true,
            'error'                    => null,
            'cambios_detectados'       => $cambiosDetectados,
            'url_notificacion_llamada' => false,
            'ofertas_antes'            => $cambiosDetectados ? $ofertasAntes->toArray() : null,
            'ofertas_despues'          => $cambiosDetectados ? $ofertasDespues->toArray() : null,
        ];
    }

    public function calcularPrecioRealPorUnidad($oferta): ?float
    {
        $descuentosController = new DescuentosController();
        $ofertaConDescuento = $descuentosController->aplicarDescuento($oferta);

        return $ofertaConDescuento->precio_unidad;
    }

    public function obtenerOfertasElegibles(int $limit = 50): Collection
    {
        return $this->queryOfertasElegiblesBase($limit)
            ->whereHas('tienda', function ($query) {
                $query->where('scrapear', 'si')
                    ->where(function ($q) {
                        $q->whereNull('api')
                            ->orWhere('api', '!=', self::API_NAVEGADOR_LOCAL);
                    });
            })
            ->get();
    }

    public function obtenerOfertasElegiblesNavegadorLocal(int $limit = 50): Collection
    {
        return $this->queryOfertasElegiblesBase($limit)
            ->whereHas('tienda', function ($query) {
                $query->where('scrapear', 'si')
                    ->where('api', self::API_NAVEGADOR_LOCAL);
            })
            ->get();
    }

    protected function queryOfertasElegiblesBase(int $limit): Builder
    {
        return OfertaProducto::with(['producto', 'tienda'])
            ->where('mostrar', 'si')
            ->where('como_scrapear', 'automatico')
            ->whereNull('chollo_id')
            ->whereRaw('TIMESTAMPDIFF(MINUTE, updated_at, NOW()) >= frecuencia_actualizar_precio_minutos')
            ->orderByRaw('TIMESTAMPDIFF(MINUTE, updated_at, NOW()) DESC')
            ->limit($limit);
    }

    /**
     * @param  array<string, mixed>  $log
     * @param  array<string, int>  $estadisticas
     */
    public function registrarEjecucionProgramaExterno(
        array $log,
        array $estadisticas,
        \Carbon\Carbon $inicio,
        string $estado = 'completada',
        ?string $errorMensaje = null
    ): int {
        $totalOfertas = (int) ($estadisticas['total_ofertas'] ?? 0);
        $actualizadas = (int) ($estadisticas['actualizadas'] ?? 0);
        $errores = (int) ($estadisticas['errores'] ?? 0);
        $procesadas = (int) ($estadisticas['procesadas'] ?? $totalOfertas);

        $payloadLog = array_merge($log, [
            'origen'           => 'programa_externo_navegador',
            'estado'           => $estado,
            'estadisticas'     => $estadisticas,
            'programa_externo' => [
                'tipo'          => 'navegador_local',
                'error_mensaje' => $errorMensaje,
            ],
            'total_ofertas' => $totalOfertas,
            'actualizadas'  => $actualizadas,
            'errores'       => $errores,
            'procesadas'    => $procesadas,
        ]);

        $ejecucion = \App\Models\EjecucionGlobal::create([
            'inicio'         => $inicio,
            'fin'            => now(),
            'nombre'         => self::NOMBRE_EJECUCION_PROGRAMA_EXTERNO,
            'total'          => $procesadas,
            'total_guardado' => $actualizadas,
            'total_errores'  => $errores,
            'log'            => $payloadLog,
        ]);

        return $ejecucion->id;
    }

    /**
     * @return array<string, mixed>|null Si hay anomalía, devuelve el resultado y no se actualiza precio.
     */
    private function detectarYRegistrarCambioAnomalo(
        OfertaProducto $oferta,
        $precioAnterior,
        float $precioNuevo
    ): ?array {
        if (!is_numeric($precioAnterior) || $precioAnterior <= 0) {
            return null;
        }

        $bajadaRelativa = ($precioAnterior - $precioNuevo) / $precioAnterior;
        if ($bajadaRelativa >= self::UMBRAL_CAMBIO_ANOMALO) {
            $this->insertarAvisoAnomalo($oferta, $precioAnterior, $precioNuevo, $bajadaRelativa, true);
            $oferta->touch();

            return $this->resultadoAnomalo($oferta, $precioAnterior, $precioNuevo);
        }

        $subidaRelativa = ($precioNuevo - $precioAnterior) / $precioAnterior;
        if ($subidaRelativa >= self::UMBRAL_CAMBIO_ANOMALO) {
            $this->insertarAvisoAnomalo($oferta, $precioAnterior, $precioNuevo, $subidaRelativa, false);
            $oferta->touch();

            return $this->resultadoAnomalo($oferta, $precioAnterior, $precioNuevo);
        }

        return null;
    }

    private function insertarAvisoAnomalo(
        OfertaProducto $oferta,
        float $precioAnterior,
        float $precioNuevo,
        float $relativa,
        bool $esBajada
    ): void {
        $producto = Producto::find($oferta->producto_id);
        $nombreProducto = $producto ? $producto->nombre : ('Producto ID ' . $oferta->producto_id);
        $porcentajeStr = number_format($relativa * 100, 0);

        if ($esBajada) {
            $absoluta = max(0, $precioAnterior - $precioNuevo);
            $textoAviso = sprintf(
                "Bajada anómala (>40%%) en '%s': de %.2f€ a %.2f€ (−%.2f€, −%s%%). Tienda: %s | Variante: %s | URL: %s",
                $nombreProducto,
                $precioAnterior,
                $precioNuevo,
                $absoluta,
                $porcentajeStr,
                $oferta->tienda->nombre,
                $oferta->variante ?? '—',
                $oferta->url
            );
        } else {
            $absoluta = max(0, $precioNuevo - $precioAnterior);
            $textoAviso = sprintf(
                "Subida anómala (>40%%) en '%s': de %.2f€ a %.2f€ (+%.2f€, +%s%%). Tienda: %s | Variante: %s | URL: %s",
                $nombreProducto,
                $precioAnterior,
                $precioNuevo,
                $absoluta,
                $porcentajeStr,
                $oferta->tienda->nombre,
                $oferta->variante ?? '—',
                $oferta->url
            );
        }

        DB::table('avisos')->insert([
            'texto_aviso'    => $textoAviso,
            'fecha_aviso'    => now(),
            'user_id'        => 1,
            'avisoable_type' => OfertaProducto::class,
            'avisoable_id'   => $oferta->id,
            'oculto'         => 0,
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);
    }

    private function aplicarCambiosProductoSiCorresponde(
        OfertaProducto $oferta,
        Collection $ofertasAntes,
        Collection $ofertasDespues
    ): bool {
        $ofertaAntes = $ofertasAntes->first();
        $ofertaDespues = $ofertasDespues->first();

        $precioRealAntes = $ofertaAntes ? $this->calcularPrecioRealPorUnidad($ofertaAntes) : null;
        $precioRealDespues = $ofertaDespues ? $this->calcularPrecioRealPorUnidad($ofertaDespues) : null;

        $precioRealAntesNormalizado = $precioRealAntes !== null ? round((float) $precioRealAntes, 3) : null;
        $precioRealDespuesNormalizado = $precioRealDespues !== null ? round((float) $precioRealDespues, 3) : null;

        if (($ofertaAntes ? $ofertaAntes->id : null) === ($ofertaDespues ? $ofertaDespues->id : null)
            && $precioRealAntesNormalizado === $precioRealDespuesNormalizado) {
            return false;
        }

        $precioMasBajo = $precioRealDespues;
        $producto = Producto::find($oferta->producto_id);
        if (!$producto) {
            return true;
        }

        $precioAntiguoProducto = Producto::where('id', $oferta->producto_id)->value('precio');
        $producto->update(['precio' => $precioMasBajo]);

        $textoAviso = 'Precio actualizado producto ' . $producto->nombre . ' precio antiguo: ' . $precioAntiguoProducto . ', precio Nuevo: ' . $precioMasBajo;
        DB::table('avisos')->insert([
            'texto_aviso'    => $textoAviso,
            'fecha_aviso'    => now(),
            'user_id'        => 1,
            'avisoable_type' => Producto::class,
            'avisoable_id'   => $producto->id,
            'oculto'         => 0,
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        $alertasPendientes = CorreoAvisoPrecio::where('producto_id', $producto->id)
            ->where('precio_limite', '>=', $precioMasBajo)
            ->count();

        if ($alertasPendientes > 0) {
            $textoAviso .= ' | Alertas pendientes: ' . $alertasPendientes . ' correos';
            DB::table('avisos')->where('avisoable_type', Producto::class)
                ->where('avisoable_id', $producto->id)
                ->where('texto_aviso', 'LIKE', '%Precio actualizado producto%')
                ->orderBy('created_at', 'desc')
                ->limit(1)
                ->update(['texto_aviso' => $textoAviso]);
        }

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    private function resultadoBase(
        OfertaProducto $oferta,
        $precioAnterior,
        ?float $precioNuevo,
        bool $success,
        ?string $error
    ): array {
        return [
            'oferta_id'                => $oferta->id,
            'tienda_nombre'            => $oferta->tienda->nombre,
            'url'                      => $oferta->url,
            'variante'                 => $oferta->variante,
            'precio_anterior'          => $precioAnterior,
            'precio_nuevo'             => $precioNuevo,
            'success'                  => $success,
            'error'                    => $error,
            'cambios_detectados'       => false,
            'url_notificacion_llamada' => false,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function resultadoAnomalo(OfertaProducto $oferta, $precioAnterior, float $precioNuevo): array
    {
        return [
            'oferta_id'                => $oferta->id,
            'tienda_nombre'            => $oferta->tienda->nombre,
            'url'                      => $oferta->url,
            'variante'                 => $oferta->variante,
            'precio_anterior'          => $precioAnterior,
            'precio_nuevo'             => $precioNuevo,
            'success'                  => true,
            'error'                    => null,
            'cambios_detectados'       => false,
            'url_notificacion_llamada' => false,
        ];
    }
}
