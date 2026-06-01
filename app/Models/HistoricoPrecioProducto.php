<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class HistoricoPrecioProducto extends Model
{
    /** Rebaja mínima (%) respecto al mínimo histórico de referencia para considerar precio hot */
    public const REBAJA_MINIMA_PCT_HOT = 1;

    /**
     * Días consecutivos (sin contar hoy) al mismo precio que la oferta actual.
     * Por encima de este umbral el precio bajo se considera oferta ya pasada de moda.
     */
    public const DIAS_RACHA_OFERTA_PASADA_HOT = 10;

    protected $table = 'historico_precios_productos';

    protected $fillable = [
        'producto_id',
        'especificacion_interna_id',
        'fecha',
        'precio_minimo',
    ];

    /**
     * Precio mínimo de referencia en los últimos N meses (excluye solo precio_minimo = 0).
     * Excluye hoy y días consecutivos previos con el mismo precio_minimo que $precioActualParaExcluirRacha.
     */
    public static function precioMinimoReferenciaUltimosMeses(
        int $productoId,
        ?string $especificacionInternaId = null,
        int $meses = 3,
        ?float $precioActualParaExcluirRacha = null
    ): ?float {
        return static::calcularReferenciaHistorica(
            $productoId,
            $especificacionInternaId,
            $meses,
            $precioActualParaExcluirRacha
        )['precio_minimo'];
    }

    /**
     * @return array{
     *     precio_minimo: ?float,
     *     fechas_excluidas: list<string>,
     *     filas_con_precio: int,
     *     filas_usadas_en_referencia: int
     * }
     */
    public static function calcularReferenciaHistorica(
        int $productoId,
        ?string $especificacionInternaId = null,
        int $meses = 3,
        ?float $precioActualParaExcluirRacha = null
    ): array {
        $desde = Carbon::now()->subMonths($meses)->startOfDay();
        $precioPorFecha = static::mapaPrecioMinimoPorFecha($productoId, $especificacionInternaId, $desde);
        $fechasExcluidas = static::fechasExcluidasDesdeMapa($precioPorFecha, $desde, $precioActualParaExcluirRacha);

        $excluidasLookup = array_fill_keys($fechasExcluidas, true);
        $min = null;
        $filasUsadas = 0;

        foreach ($precioPorFecha as $fecha => $precio) {
            if (isset($excluidasLookup[$fecha])) {
                continue;
            }
            $filasUsadas++;
            if ($min === null || $precio < $min) {
                $min = $precio;
            }
        }

        return [
            'precio_minimo' => $min,
            'fechas_excluidas' => $fechasExcluidas,
            'filas_con_precio' => count($precioPorFecha),
            'filas_usadas_en_referencia' => $filasUsadas,
        ];
    }

    /**
     * Fechas a excluir: siempre hoy; además ayer, anteayer… mientras el histórico tenga el mismo precio que el actual.
     *
     * @return list<string> Fechas Y-m-d
     */
    public static function fechasExcluidasRachaPrecioActual(
        int $productoId,
        ?string $especificacionInternaId,
        ?Carbon $desde = null,
        ?float $precioActual = null
    ): array {
        $desde = ($desde ?? Carbon::now()->subMonths(3))->copy()->startOfDay();
        $precioPorFecha = static::mapaPrecioMinimoPorFecha($productoId, $especificacionInternaId, $desde);

        return static::fechasExcluidasDesdeMapa($precioPorFecha, $desde, $precioActual);
    }

    /**
     * Días consecutivos anteriores a hoy con el mismo precio_minimo que la oferta actual.
     */
    public static function diasConsecutivosPrecioActualAntesDeHoy(
        int $productoId,
        ?string $especificacionInternaId = null,
        ?float $precioActual = null,
        int $meses = 3
    ): int {
        if ($precioActual === null) {
            return 0;
        }

        $fechasExcluidas = static::fechasExcluidasRachaPrecioActual(
            $productoId,
            $especificacionInternaId,
            Carbon::now()->subMonths($meses)->startOfDay(),
            $precioActual
        );

        $hoy = Carbon::today()->toDateString();
        $dias = 0;

        foreach ($fechasExcluidas as $fecha) {
            if ($fecha !== $hoy) {
                $dias++;
            }
        }

        return $dias;
    }

    /**
     * Oferta con precio bajo sostenido demasiado tiempo: no debe entrar en precios hot.
     */
    public static function esOfertaPasadaDeModaParaHot(
        int $productoId,
        ?string $especificacionInternaId = null,
        ?float $precioActual = null,
        int $meses = 3
    ): bool {
        return static::diasConsecutivosPrecioActualAntesDeHoy(
            $productoId,
            $especificacionInternaId,
            $precioActual,
            $meses
        ) > static::DIAS_RACHA_OFERTA_PASADA_HOT;
    }

    /**
     * @param  array<string, float>  $precioPorFecha  Y-m-d => precio_minimo
     * @return list<string>
     */
    public static function fechasExcluidasDesdeMapa(
        array $precioPorFecha,
        Carbon $desde,
        ?float $precioActual = null
    ): array {
        $fechasExcluir = [];
        $fecha = Carbon::today();
        $precioRedondeado = $precioActual !== null ? round($precioActual, 3) : null;

        while ($fecha->gte($desde)) {
            $fechaStr = $fecha->toDateString();

            if ($fecha->isToday()) {
                $fechasExcluir[] = $fechaStr;
                $fecha->subDay();
                continue;
            }

            if ($precioRedondeado === null) {
                break;
            }

            if (!isset($precioPorFecha[$fechaStr])) {
                break;
            }

            if (!static::preciosCoinciden($precioPorFecha[$fechaStr], $precioRedondeado)) {
                break;
            }

            $fechasExcluir[] = $fechaStr;
            $fecha->subDay();
        }

        return $fechasExcluir;
    }

    public static function preciosCoinciden(float $precioA, float $precioB): bool
    {
        return round($precioA, 3) === round($precioB, 3);
    }

    /**
     * @return array<string, float> Y-m-d => precio_minimo
     */
    public static function mapaPrecioMinimoPorFecha(
        int $productoId,
        ?string $especificacionInternaId,
        Carbon $desde
    ): array {
        $mapa = [];

        foreach (static::filasVentana($productoId, $especificacionInternaId, $desde) as $fila) {
            $fechaStr = $fila->fecha instanceof \DateTimeInterface
                ? $fila->fecha->format('Y-m-d')
                : Carbon::parse($fila->fecha)->toDateString();
            $mapa[$fechaStr] = (float) $fila->precio_minimo;
        }

        return $mapa;
    }

    /**
     * @return Collection<int, self>
     */
    public static function filasVentana(
        int $productoId,
        ?string $especificacionInternaId,
        Carbon $desde
    ): Collection {
        return static::queryHistoricoVentana($productoId, $especificacionInternaId, $desde)
            ->orderBy('fecha', 'desc')
            ->get(['fecha', 'precio_minimo']);
    }

    private static function queryHistoricoVentana(
        int $productoId,
        ?string $especificacionInternaId,
        Carbon $desde
    ): Builder {
        $query = static::query()
            ->where('producto_id', $productoId)
            ->where('fecha', '>=', $desde)
            ->where('precio_minimo', '>', 0)
            ->whereNotNull('precio_minimo');

        if ($especificacionInternaId === null) {
            $query->whereNull('especificacion_interna_id');
        } else {
            $query->where('especificacion_interna_id', $especificacionInternaId);
        }

        return $query;
    }
}
