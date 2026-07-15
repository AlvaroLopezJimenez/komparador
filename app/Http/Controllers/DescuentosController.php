<?php

namespace App\Http\Controllers;

use App\Models\OfertaProducto;
use Illuminate\Support\Facades\Log;

class DescuentosController extends Controller
{
    public const SEPARADOR_DESCUENTOS = '||';

    /** Descuentos que el scraper de Carrefour detecta y gestiona automáticamente */
    public const DESCUENTOS_CARREFOUR = [
        '3x2',
        '2x1 - SoloCarrefour',
        '2a al 70',
        '2a al 50 - cheque - SoloCarrefour',
        '-20%',
    ];

    public const DESCUENTO_20_PORCIENTO = '-20%';

    /**
     * @return array<int, string>
     */
    public static function parseDescuentos(?string $descuentos): array
    {
        if ($descuentos === null || $descuentos === '') {
            return [];
        }

        return array_values(array_filter(
            array_map('trim', explode(self::SEPARADOR_DESCUENTOS, $descuentos)),
            fn (string $d) => $d !== ''
        ));
    }

    /**
     * @param array<int, string> $descuentos
     */
    public static function joinDescuentos(array $descuentos): ?string
    {
        $descuentos = array_values(array_filter(
            array_map('trim', $descuentos),
            fn (string $d) => $d !== ''
        ));

        return $descuentos === [] ? null : implode(self::SEPARADOR_DESCUENTOS, $descuentos);
    }

    public static function esDescuentoCarrefour(string $descuento): bool
    {
        return in_array($descuento, self::DESCUENTOS_CARREFOUR, true);
    }

    /**
     * @param array<int, string> $descuentos
     * @return array<int, string>
     */
    public static function filtrarDescuentosNoCarrefour(array $descuentos): array
    {
        return array_values(array_filter(
            $descuentos,
            fn (string $d) => !self::esDescuentoCarrefour($d)
        ));
    }

    public static function normalizarDescuento(string $descuento): string
    {
        $descuento = trim($descuento);

        if ($descuento === '2a al 50% - cheque - Solo Carrefour') {
            return '2a al 50 - cheque - SoloCarrefour';
        }

        if (preg_match('/^-20\s*%$/i', $descuento)) {
            return self::DESCUENTO_20_PORCIENTO;
        }

        if (preg_match('/^2a al (\d+)\s*-\s*cupon;(.+)$/i', $descuento, $m)) {
            return '2a al ' . (int) $m[1] . ' - cupon;' . trim($m[2]);
        }

        return $descuento;
    }

    public static function esDescuento2aCupon(string $descuento): bool
    {
        return (bool) preg_match('/^2a al \d+ - cupon;.+/i', $descuento);
    }

    /**
     * @return array{porcentaje: int, codigo: string}|null
     */
    public static function parseDescuento2aCupon(string $descuento): ?array
    {
        if (!preg_match('/^2a al (\d+) - cupon;(.+)$/i', $descuento, $m)) {
            return null;
        }

        return [
            'porcentaje' => (int) $m[1],
            'codigo' => trim($m[2]),
        ];
    }

    /** Descuentos que el scraper de Tiendanimal detecta y puede sustituir al re-scrapear */
    public static function esDescuentoScrapeadoTiendanimal(string $descuento): bool
    {
        if (self::esDescuento2aCupon($descuento)) {
            return true;
        }

        return (bool) preg_match('/^cupon;[^;]+;%\d+$/i', $descuento);
    }

    /**
     * @param array<int, string> $descuentos
     * @return array<int, string>
     */
    public static function filtrarDescuentosNoTiendanimal(array $descuentos): array
    {
        return array_values(array_filter(
            $descuentos,
            fn (string $d) => !self::esDescuentoScrapeadoTiendanimal($d)
        ));
    }

    /**
     * Aplica todos los descuentos de una oferta (soporta varios separados por ||)
     *
     * @param OfertaProducto $oferta La oferta original sin descuentos aplicados
     * @return OfertaProducto La oferta con descuentos aplicados (unidades, precio_total y precio_unidad ajustados)
     */
    public function aplicarDescuento(OfertaProducto $oferta): OfertaProducto
    {
        if (!is_null($oferta->chollo_id)) {
            return $oferta;
        }

        if (empty($oferta->descuentos)) {
            return $oferta;
        }

        $descuentos = self::parseDescuentos($oferta->descuentos);

        if ($descuentos === []) {
            return $oferta;
        }

        $ofertaConDescuento = $this->clonarOferta($oferta);

        foreach ($descuentos as $descuento) {
            $ofertaConDescuento = $this->aplicarUnDescuento($ofertaConDescuento, $descuento);
        }

        return $ofertaConDescuento;
    }

    /**
     * Aplica un único descuento sobre el estado actual de la oferta
     */
    private function aplicarUnDescuento(OfertaProducto $oferta, string $descuento): OfertaProducto
    {
        $ofertaConDescuento = $this->clonarOferta($oferta);

        if (preg_match('/^2a al (\d+) - cupon;/i', $descuento, $m2aCupon)) {
            $porcentajeDescuento = max(0, min(100, (int) $m2aCupon[1]));
            $factorPagado = (100 - $porcentajeDescuento) / 100;
            $precioUnidadOriginal = $oferta->unidades > 0
                ? $oferta->precio_total / $oferta->unidades
                : 0;
            $ofertaConDescuento->unidades = $oferta->unidades * 2;
            $ofertaConDescuento->precio_total = $oferta->precio_total
                + ($precioUnidadOriginal * $oferta->unidades * $factorPagado);
            $ofertaConDescuento->precio_unidad = $ofertaConDescuento->unidades > 0
                ? $ofertaConDescuento->precio_total / $ofertaConDescuento->unidades
                : 0;

            return $ofertaConDescuento;
        }

        if (strpos($descuento, 'cupon;') === 0) {
            if (strpos($descuento, 'cupon;%') === 0) {
                $porcentajeCupon = (float) str_replace('cupon;%', '', $descuento);
                $precioTotalConDescuento = $oferta->precio_total * (1 - $porcentajeCupon / 100);
            } else {
                $partes = explode(';', $descuento);
                $ultimaParte = count($partes) >= 3 ? $partes[count($partes) - 1] : '';
                if (str_starts_with($ultimaParte, '%')) {
                    $porcentajeCupon = (float) str_replace('%', '', $ultimaParte);
                    $precioTotalConDescuento = $oferta->precio_total * (1 - $porcentajeCupon / 100);
                } else {
                    $valorCupon = count($partes) === 3
                        ? (float) $partes[2]
                        : (float) str_replace('cupon;', '', $descuento);
                    $precioTotalConDescuento = max(0, $oferta->precio_total - $valorCupon);
                }
            }

            $ofertaConDescuento->precio_total = $precioTotalConDescuento;
            $ofertaConDescuento->precio_unidad = $oferta->unidades > 0
                ? round($precioTotalConDescuento / $oferta->unidades, 3)
                : 0;

            return $ofertaConDescuento;
        }

        if (strpos($descuento, 'CholloTienda1SoloCuponQueAplicaDescuento;') === 0) {
            $partes = explode(';', $descuento);
            $valorDescuento = null;
            $tipoDescuento = 'euros';

            for ($i = 0; $i < count($partes); $i++) {
                if ($partes[$i] === 'descuento' && isset($partes[$i + 1])) {
                    $valorDescuento = (float) $partes[$i + 1];
                }
                if ($partes[$i] === 'tipo_descuento' && isset($partes[$i + 1])) {
                    $tipoDescuento = $partes[$i + 1];
                }
            }

            if ($valorDescuento !== null && $valorDescuento > 0) {
                $precioTotalConDescuento = $tipoDescuento === 'porcentaje'
                    ? $oferta->precio_total * (1 - $valorDescuento / 100)
                    : max(0, $oferta->precio_total - $valorDescuento);

                $ofertaConDescuento->precio_total = $precioTotalConDescuento;
                $ofertaConDescuento->precio_unidad = $oferta->unidades > 0
                    ? round($precioTotalConDescuento / $oferta->unidades, 3)
                    : 0;
            }

            return $ofertaConDescuento;
        }

        if (strpos($descuento, 'CholloTienda;') === 0) {
            return $oferta;
        }

        switch ($descuento) {
            case '3x2':
                $ofertaConDescuento->unidades = $oferta->unidades * 3;
                $ofertaConDescuento->precio_total = $oferta->precio_total * 2;
                $ofertaConDescuento->precio_unidad = ($oferta->precio_total * 2) / ($oferta->unidades * 3);
                break;

            case '2x1 - SoloCarrefour':
                $ofertaConDescuento->unidades = $oferta->unidades * 2;
                $ofertaConDescuento->precio_total = $oferta->precio_total;
                $ofertaConDescuento->precio_unidad = $oferta->precio_total / ($oferta->unidades * 2);
                break;

            case '2a al 50 - cheque - SoloCarrefour':
                $ofertaConDescuento->unidades = $oferta->unidades * 2;
                $ofertaConDescuento->precio_total = $oferta->precio_total * 1.5;
                $ofertaConDescuento->precio_unidad = $ofertaConDescuento->precio_total / $ofertaConDescuento->unidades;
                break;

            case '2a al 70':
                $precioUnidadOriginal = $oferta->precio_total / $oferta->unidades;
                $ofertaConDescuento->unidades = $oferta->unidades * 2;
                $ofertaConDescuento->precio_total = $oferta->precio_total + ($precioUnidadOriginal * $oferta->unidades * 0.30);
                $ofertaConDescuento->precio_unidad = $ofertaConDescuento->precio_total / $ofertaConDescuento->unidades;
                break;

            case '2a al 50':
                $precioUnidadOriginal = $oferta->precio_total / $oferta->unidades;
                $ofertaConDescuento->unidades = $oferta->unidades * 2;
                $ofertaConDescuento->precio_total = $oferta->precio_total + ($precioUnidadOriginal * $oferta->unidades * 0.50);
                $ofertaConDescuento->precio_unidad = $ofertaConDescuento->precio_total / $ofertaConDescuento->unidades;
                break;

            case self::DESCUENTO_20_PORCIENTO:
                $precioTotalConDescuento = $oferta->precio_total * 0.80;
                $ofertaConDescuento->precio_total = $precioTotalConDescuento;
                $ofertaConDescuento->precio_unidad = $oferta->unidades > 0
                    ? round($precioTotalConDescuento / $oferta->unidades, 3)
                    : 0;
                break;

            case 'cupon':
            case 'rebaja':
            case 'promocion':
            case 'oferta_especial':
            case '+Juego':
                break;

            default:
                Log::warning('DescuentosController - Tipo de descuento desconocido:', [
                    'oferta_id' => $oferta->id,
                    'descuento' => $descuento,
                ]);
                break;
        }

        return $ofertaConDescuento;
    }

    private function clonarOferta(OfertaProducto $oferta): OfertaProducto
    {
        $copia = clone $oferta;
        $copia->tienda_id = $oferta->tienda_id;
        $copia->descuentos = $oferta->descuentos;

        if ($oferta->relationLoaded('tienda') && $oferta->tienda) {
            $copia->setRelation('tienda', $oferta->tienda);
        }

        return $copia;
    }

    /**
     * Aplica descuentos a una colección de ofertas y las ordena por precio_unidad
     *
     * @param \Illuminate\Database\Eloquent\Collection $ofertas Colección de ofertas
     * @return \Illuminate\Database\Eloquent\Collection Ofertas con descuentos aplicados y ordenadas
     */
    public function aplicarDescuentosYOrdenar($ofertas)
    {
        $ofertasConDescuentos = $ofertas->map(function ($oferta) {
            return $this->aplicarDescuento($oferta);
        });

        return $ofertasConDescuentos->sortBy('precio_unidad')->values();
    }

    /**
     * Obtiene el precio por unidad más bajo de una colección de ofertas después de aplicar descuentos
     *
     * @param \Illuminate\Database\Eloquent\Collection $ofertas Colección de ofertas
     * @return float|null Precio por unidad más bajo
     */
    public function obtenerPrecioUnidadMasBajo($ofertas): ?float
    {
        if ($ofertas->isEmpty()) {
            return null;
        }

        $ofertasConDescuentos = $this->aplicarDescuentosYOrdenar($ofertas);

        return $ofertasConDescuentos->first()->precio_unidad ?? null;
    }
}
