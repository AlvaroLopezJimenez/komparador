<?php

namespace App\Http\Controllers\Productos\Historial;

use App\Http\Controllers\Controller;
use App\Models\HistoricoPrecioProducto;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RellenarEspaciosVaciosHistorialController extends Controller
{
    /**
     * Recorre todos los historiales (precio general y especificaciones) con huecos
     * y rellena los días vacíos con el precio anterior o, si no existe, el posterior.
     */
    public function ejecutar(Request $request): JsonResponse
    {
        @set_time_limit(0);

        $series = HistoricoPrecioProducto::query()
            ->select('producto_id', 'especificacion_interna_id')
            ->distinct()
            ->orderBy('producto_id')
            ->orderBy('especificacion_interna_id')
            ->get();

        $historialesProducto = 0;
        $historialesEspecificacion = 0;
        $huecosRellenados = 0;

        DB::transaction(function () use (
            $series,
            &$historialesProducto,
            &$historialesEspecificacion,
            &$huecosRellenados
        ) {
            foreach ($series as $fila) {
                $productoId = (int) $fila->producto_id;
                $specKey = $fila->especificacion_interna_id === null
                    ? null
                    : (string) $fila->especificacion_interna_id;

                if (!$this->serieTieneHuecos($productoId, $specKey)) {
                    continue;
                }

                $resultado = $this->rellenarSerie($productoId, $specKey);
                if ($resultado['total'] === 0) {
                    continue;
                }

                $huecosRellenados += $resultado['total'];

                if ($specKey === null) {
                    $historialesProducto++;
                } else {
                    $historialesEspecificacion++;
                }
            }
        });

        $message = $huecosRellenados > 0
            ? "Proceso completado: {$huecosRellenados} días rellenados en {$historialesProducto} historiales de producto y {$historialesEspecificacion} de especificación."
            : 'No se encontraron huecos que rellenar en ningún historial.';

        return response()->json([
            'status' => 'ok',
            'message' => $message,
            'historiales_producto' => $historialesProducto,
            'historiales_especificacion' => $historialesEspecificacion,
            'huecos_rellenados' => $huecosRellenados,
        ]);
    }

    private function serieTieneHuecos(int $productoId, ?string $especificacionId): bool
    {
        $mapa = $this->mapaPreciosSerie($productoId, $especificacionId);

        return count($this->detectarHuecos($mapa)) > 0;
    }

    /**
     * @return array<string, float> Y-m-d => precio_minimo
     */
    private function mapaPreciosSerie(int $productoId, ?string $especificacionId): array
    {
        $query = HistoricoPrecioProducto::query()
            ->where('producto_id', $productoId)
            ->whereNotNull('precio_minimo')
            ->where('precio_minimo', '>', 0);

        if ($especificacionId === null) {
            $query->whereNull('especificacion_interna_id');
        } else {
            $query->where('especificacion_interna_id', $especificacionId);
        }

        $mapa = [];
        foreach ($query->orderBy('fecha')->get(['fecha', 'precio_minimo']) as $fila) {
            $fechaStr = $fila->fecha instanceof \DateTimeInterface
                ? $fila->fecha->format('Y-m-d')
                : Carbon::parse($fila->fecha)->toDateString();
            $mapa[$fechaStr] = (float) $fila->precio_minimo;
        }

        return $mapa;
    }

    /**
     * @param  array<string, float>  $mapa
     * @return list<string>
     */
    private function detectarHuecos(array $mapa): array
    {
        if (count($mapa) < 2) {
            return [];
        }

        ksort($mapa);
        $fechas = array_keys($mapa);
        $inicio = Carbon::parse($fechas[0])->startOfDay();
        $fin = Carbon::parse($fechas[array_key_last($fechas)])->startOfDay();

        $huecos = [];
        for ($fecha = $inicio->copy(); $fecha->lte($fin); $fecha->addDay()) {
            $str = $fecha->toDateString();
            if (!isset($mapa[$str])) {
                $huecos[] = $str;
            }
        }

        return $huecos;
    }

    /**
     * @return array{total: int}
     */
    private function rellenarSerie(int $productoId, ?string $especificacionId): array
    {
        $mapa = $this->mapaPreciosSerie($productoId, $especificacionId);
        $huecos = $this->detectarHuecos($mapa);

        if ($huecos === []) {
            return ['total' => 0];
        }

        ksort($mapa);
        $fechas = array_keys($mapa);
        $inicio = Carbon::parse($fechas[0])->startOfDay();
        $fin = Carbon::parse($fechas[array_key_last($fechas)])->startOfDay();

        $total = 0;

        foreach ($huecos as $fechaHueco) {
            $vecino = $this->resolverPrecioVecino($fechaHueco, $mapa, $inicio, $fin);
            if ($vecino === null) {
                continue;
            }

            HistoricoPrecioProducto::updateOrCreate(
                [
                    'producto_id' => $productoId,
                    'especificacion_interna_id' => $especificacionId,
                    'fecha' => $fechaHueco,
                ],
                [
                    'precio_minimo' => $vecino['precio'],
                ]
            );

            $mapa[$fechaHueco] = $vecino['precio'];
            $total++;
        }

        return ['total' => $total];
    }

    /**
     * @param  array<string, float>  $mapa
     * @return array{precio: float, fuente: string, fecha_referencia: string}|null
     */
    private function resolverPrecioVecino(string $fechaHueco, array $mapa, Carbon $inicio, Carbon $fin): ?array
    {
        $fecha = Carbon::parse($fechaHueco)->startOfDay();

        $cursor = $fecha->copy();
        while ($cursor->gt($inicio)) {
            $cursor->subDay();
            $str = $cursor->toDateString();
            if (isset($mapa[$str])) {
                return [
                    'precio' => $mapa[$str],
                    'fuente' => 'anterior',
                    'fecha_referencia' => $str,
                ];
            }
        }

        $cursor = $fecha->copy();
        while ($cursor->lt($fin)) {
            $cursor->addDay();
            $str = $cursor->toDateString();
            if (isset($mapa[$str])) {
                return [
                    'precio' => $mapa[$str],
                    'fuente' => 'posterior',
                    'fecha_referencia' => $str,
                ];
            }
        }

        return null;
    }
}
