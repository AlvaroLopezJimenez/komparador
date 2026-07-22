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
        '4x3',
        '2x1 - SoloCarrefour',
        '2a al 70',
        '2a al 50 - cheque - SoloCarrefour',
        '-20%',
        '-20% Cupon - Solo Carrefour',
    ];

    public const DESCUENTO_20_PORCIENTO = '-20%';
    public const DESCUENTO_20_CUPON_CARREFOUR = '-20% Cupon - Solo Carrefour';

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

        if ($descuento === '-20% Cupón - Solo Carrefour' || $descuento === '-20% Cupon - Solo Carrefour') {
            return self::DESCUENTO_20_CUPON_CARREFOUR;
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
     * @return OfertaProducto La oferta con multiplicador y descuentos aplicados
     */
    public function aplicarDescuento(OfertaProducto $oferta): OfertaProducto
    {
        $parsed = $this->parseTextoCantidadAlternativo($oferta->texto_cantidad_alternativo);
        $ofertaConMultiplicador = $this->aplicarMultiplicador($oferta, $parsed);
        $ofertaConDescuento = $this->clonarOferta($ofertaConMultiplicador);

        if (is_null($ofertaConDescuento->chollo_id) && !empty($ofertaConDescuento->descuentos)) {
            $descuentos = self::parseDescuentos($ofertaConDescuento->descuentos);
            if ($descuentos !== []) {
                $tieneCuponCarrefour = in_array(self::DESCUENTO_20_CUPON_CARREFOUR, $descuentos, true);
                $cuponCarrefourValor = 0;

                if ($tieneCuponCarrefour) {
                    $tiene2x1 = in_array('2x1 - SoloCarrefour', $descuentos, true);
                    $tiene2a50Cheque = in_array('2a al 50 - cheque - SoloCarrefour', $descuentos, true);
                    $multiplicadorDescuento = ($tiene2x1 || $tiene2a50Cheque) ? 2 : 1;

                    $cuponCarrefourValor = ($ofertaConDescuento->precio_total * $multiplicadorDescuento) * 0.20;
                    $descuentos = array_values(array_filter($descuentos, fn (string $d) => $d !== self::DESCUENTO_20_CUPON_CARREFOUR));
                }

                foreach ($descuentos as $descuento) {
                    $ofertaConDescuento = $this->aplicarUnDescuento($ofertaConDescuento, $descuento, $parsed);
                }

                if ($tieneCuponCarrefour) {
                    $ofertaConDescuento->precio_total = max(0, $ofertaConDescuento->precio_total - $cuponCarrefourValor);
                    $ofertaConDescuento->precio_unidad = $ofertaConDescuento->unidades > 0
                        ? round($ofertaConDescuento->precio_total / $ofertaConDescuento->unidades, 3)
                        : 0;
                }
            }
        }

        $this->aplicarTextoCantidadAlternativo($ofertaConDescuento, $parsed);

        return $ofertaConDescuento;
    }

    /**
     * Oferta con multiplicador aplicado (sin descuentos), para comparar o mostrar sin duplicar.
     */
    public function ofertaConMultiplicador(OfertaProducto $oferta): OfertaProducto
    {
        $parsed = $this->parseTextoCantidadAlternativo($oferta->texto_cantidad_alternativo);
        $resultado = $this->aplicarMultiplicador($oferta, $parsed);
        $this->aplicarTextoCantidadAlternativo($resultado, $parsed);

        return $resultado;
    }

    /**
     * Multiplica unidades, precio total y texto cantidad alternativo antes de aplicar descuentos.
     */
    private function aplicarMultiplicador(OfertaProducto $oferta, array &$parsed): OfertaProducto
    {
        $copia = $this->clonarOferta($oferta);
        $factor = $oferta->multiplicador;

        if ($factor === null || $factor === '' || (float) $factor <= 0) {
            return $copia;
        }

        $factor = (float) $factor;
        $copia->unidades = round((float) $oferta->unidades * $factor, 2);
        $copia->precio_total = round((float) $oferta->precio_total * $factor, 2);
        $copia->precio_unidad = $copia->unidades > 0
            ? round($copia->precio_total / $copia->unidades, 3)
            : 0;

        if ($parsed['num'] !== '' && is_numeric($parsed['num'])) {
            $parsed['num'] = (float) $parsed['num'] * $factor;
        }

        return $copia;
    }

    private function aplicarTextoCantidadAlternativo(OfertaProducto $oferta, array $parsed): void
    {
        if ($parsed['num'] !== '' || $parsed['txt'] !== '') {
            $oferta->texto_cantidad_alternativo = trim($parsed['num'] . ' ' . $parsed['txt']);
        } else {
            $oferta->texto_cantidad_alternativo = null;
        }
    }

    /**
     * Aplica un único descuento sobre el estado actual de la oferta
     */
    private function aplicarUnDescuento(OfertaProducto $oferta, string $descuento, array &$parsed): OfertaProducto
    {
        $ofertaConDescuento = $this->clonarOferta($oferta);

        if (preg_match('/^2a al (\d+) - cupon;/i', $descuento, $m2aCupon)) {
            $porcentajeDescuento = max(0, min(100, (int) $m2aCupon[1]));
            $factorPagado = (100 - $porcentajeDescuento) / 100;
            $precioUnidadOriginal = $oferta->unidades > 0
                ? $oferta->precio_total / $oferta->unidades
                : 0;
            $ofertaConDescuento->unidades = $oferta->unidades * 2;
            if ($parsed['num'] !== '' && is_numeric($parsed['num'])) {
                $parsed['num'] = $parsed['num'] * 2;
            }
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
                if ($parsed['num'] !== '' && is_numeric($parsed['num'])) {
                    $parsed['num'] = $parsed['num'] * 3;
                }
                $ofertaConDescuento->precio_total = $oferta->precio_total * 2;
                $ofertaConDescuento->precio_unidad = ($oferta->precio_total * 2) / ($oferta->unidades * 3);
                break;

            case '4x3':
                $ofertaConDescuento->unidades = $oferta->unidades * 4;
                if ($parsed['num'] !== '' && is_numeric($parsed['num'])) {
                    $parsed['num'] = $parsed['num'] * 4;
                }
                $ofertaConDescuento->precio_total = $oferta->precio_total * 3;
                $ofertaConDescuento->precio_unidad = ($oferta->precio_total * 3) / ($oferta->unidades * 4);
                break;

            case '2x1 - SoloCarrefour':
                $ofertaConDescuento->unidades = $oferta->unidades * 2;
                if ($parsed['num'] !== '' && is_numeric($parsed['num'])) {
                    $parsed['num'] = $parsed['num'] * 2;
                }
                $ofertaConDescuento->precio_total = $oferta->precio_total;
                $ofertaConDescuento->precio_unidad = $oferta->precio_total / ($oferta->unidades * 2);
                break;

            case '2a al 50 - cheque - SoloCarrefour':
                $ofertaConDescuento->unidades = $oferta->unidades * 2;
                if ($parsed['num'] !== '' && is_numeric($parsed['num'])) {
                    $parsed['num'] = $parsed['num'] * 2;
                }
                $ofertaConDescuento->precio_total = $oferta->precio_total * 1.5;
                $ofertaConDescuento->precio_unidad = $ofertaConDescuento->precio_total / $ofertaConDescuento->unidades;
                break;

            case '2a al 70':
                $precioUnidadOriginal = $oferta->precio_total / $oferta->unidades;
                $ofertaConDescuento->unidades = $oferta->unidades * 2;
                if ($parsed['num'] !== '' && is_numeric($parsed['num'])) {
                    $parsed['num'] = $parsed['num'] * 2;
                }
                $ofertaConDescuento->precio_total = $oferta->precio_total + ($precioUnidadOriginal * $oferta->unidades * 0.30);
                $ofertaConDescuento->precio_unidad = $ofertaConDescuento->precio_total / $ofertaConDescuento->unidades;
                break;

            case '2a al 50':
                $precioUnidadOriginal = $oferta->precio_total / $oferta->unidades;
                $ofertaConDescuento->unidades = $oferta->unidades * 2;
                if ($parsed['num'] !== '' && is_numeric($parsed['num'])) {
                    $parsed['num'] = $parsed['num'] * 2;
                }
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

    private function parseTextoCantidadAlternativo(?string $texto): array
    {
        if ($texto === null || $texto === '') {
            return ['num' => '', 'txt' => ''];
        }

        $decoded = json_decode($texto, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return [
                'num' => $decoded['num'] ?? '',
                'txt' => $decoded['txt'] ?? '',
            ];
        }

        // Fallback for old format (e.g., "6 Botellines 33 cl")
        if (preg_match('/^(\d+(?:[.,]\d+)?)\s*(.*)$/u', $texto, $matches)) {
            return [
                'num' => (float) str_replace(',', '.', $matches[1]),
                'txt' => trim($matches[2]),
            ];
        }

        return ['num' => '', 'txt' => $texto];
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
     * Aplica descuentos a una colección de ofertas y las ordena por precio_unidad.
     * Si un descuento modifica precio/unidades, se incluyen ambas versiones:
     * la original sin descuentos y la modificada con descuentos.
     *
     * @param \Illuminate\Support\Collection|\Illuminate\Database\Eloquent\Collection $ofertas
     * @return \Illuminate\Support\Collection
     */
    public function aplicarDescuentosYOrdenar($ofertas)
    {
        $resultado = collect();

        foreach ($ofertas as $oferta) {
            $ofertaConMultiplicador = $this->ofertaConMultiplicador($oferta);
            $ofertaConDescuento = $this->aplicarDescuento($oferta);

            if ($this->ofertaFueModificadaPorDescuento($ofertaConMultiplicador, $ofertaConDescuento)) {
                $ofertaSinDescuento = $this->clonarOferta($ofertaConMultiplicador);
                $ofertaSinDescuento->descuentos = '';
                $ofertaSinDescuento->setAttribute('descuentos', '');
                $resultado->push($ofertaSinDescuento);
                $resultado->push($ofertaConDescuento);
            } else {
                $resultado->push($ofertaConDescuento);
            }
        }

        return $resultado->sortBy('precio_unidad')->values();
    }

    /**
     * True si aplicar el descuento cambió precio o unidades respecto a la oferta original.
     */
    private function ofertaFueModificadaPorDescuento(OfertaProducto $original, OfertaProducto $conDescuento): bool
    {
        return round((float) $original->precio_total, 4) !== round((float) $conDescuento->precio_total, 4)
            || round((float) $original->unidades, 4) !== round((float) $conDescuento->unidades, 4)
            || round((float) $original->precio_unidad, 4) !== round((float) $conDescuento->precio_unidad, 4);
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
