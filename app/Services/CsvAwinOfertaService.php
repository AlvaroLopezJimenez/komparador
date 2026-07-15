<?php

namespace App\Services;

use App\Models\Aviso;
use App\Models\CsvOferta;
use App\Models\OfertaProducto;
use App\Models\Producto;
use App\Models\Tienda;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class CsvAwinOfertaService
{
    public const DIAS_AVISO_SIN_STOCK = 1;

    public const INCREMENTO_VEZ_SIN_STOCK = 0.1;

    public const VEZ_MAXIMA_PROCESAR = 9.9;

    public const TEXTO_AVISO_URL_CSV_NO_ENCONTRADA = 'No encontrada URL CSV 1a vez - Generado automaticamente';

    public const TEXTO_AVISO_SIN_STOCK = 'Sin stock 1a vez - Generado automaticamente';

    public const PREFIJO_AVISO_URL_CSV_NO_ENCONTRADA = 'No encontrada URL CSV';

    public const PREFIJO_AVISO_SIN_STOCK = 'Sin stock';

    /** @var list<string> */
    public const CAMPOS_CODIGOS_IDENTIFICADOR = ['ean', 'isbn', 'upc', 'mpn', 'gtin'];

    public function __construct(
        private readonly LimpiarUrlDeTiendas $limpiarUrlDeTiendas,
        private readonly ConsultarNeoCifrado $consultarNeoCifrado,
    ) {}

    public function obtenerPrecioJson(string $url, Tienda $tienda, ?OfertaProducto $oferta = null): JsonResponse
    {
        $fila = $this->buscarFila($tienda, $url);

        if ($fila === null) {
            if ($oferta instanceof OfertaProducto) {
                $this->marcarOfertaUrlCsvNoEncontrada($oferta);
            }

            return response()->json([
                'success' => false,
                'error' => 'Producto no encontrado en CSV-Awin.',
            ]);
        }

        if ($this->tieneSinStock($fila)) {
            if ($oferta instanceof OfertaProducto) {
                $this->marcarOfertaSinStock($oferta);
            }

            return response()->json([
                'success' => false,
                'error' => 'Sin stock según CSV-Awin.',
            ]);
        }

        if ($fila->precio === null) {
            return response()->json([
                'success' => false,
                'error' => 'Precio no disponible en CSV-Awin.',
            ]);
        }

        if ($oferta instanceof OfertaProducto) {
            $this->sincronizarEnvioOfertaSiDiferente($oferta, $fila);
        }

        return response()->json([
            'success' => true,
            'precio' => (float) $fila->precio,
            'envio' => $fila->envio !== null ? (float) $fila->envio : null,
        ]);
    }

    /**
     * Copia el envío del CSV a la oferta si el valor es distinto al actual.
     */
    public function sincronizarEnvioOfertaSiDiferente(OfertaProducto $oferta, CsvOferta $fila): bool
    {
        $envioNuevo = $this->normalizarEnvio($fila->envio !== null ? (float) $fila->envio : null);
        $envioActual = $this->normalizarEnvio(
            $oferta->envio !== null && $oferta->envio !== '' ? (float) $oferta->envio : null
        );

        if ($envioNuevo === $envioActual) {
            return false;
        }

        $oferta->update([
            'envio' => $envioNuevo,
            'fecha_actualizacion_envio' => now(),
        ]);

        return true;
    }

    private function normalizarEnvio(?float $valor): ?float
    {
        if ($valor === null) {
            return null;
        }

        return round($valor, 2);
    }

    public function buscarFila(Tienda $tienda, string $url): ?CsvOferta
    {
        $urlLimpia = $this->limpiarUrlDeTiendas->limpiar(trim($url));
        if ($urlLimpia === '') {
            return null;
        }

        $lookup = $this->consultarNeoCifrado->hashLookup($urlLimpia);

        return CsvOferta::query()
            ->where('tienda_id', $tienda->id)
            ->where('url_lookup', $lookup)
            ->first();
    }

    public function buscarFilaPorOferta(OfertaProducto $oferta): ?CsvOferta
    {
        $oferta->loadMissing('tienda');
        $tienda = $oferta->tienda;
        if ($tienda === null) {
            return null;
        }

        $url = trim((string) $oferta->url);
        if ($url === '') {
            return null;
        }

        return $this->buscarFila($tienda, $url);
    }

    /**
     * Tras un scraping normal: si hay fila CSV con otro precio, actualiza solo precio_total y precio_unidad.
     * Sin avisos, sin ocultar, sin tocar envío ni updated_at.
     */
    public function actualizarPrecioOfertaDesdeCsvSilencioso(OfertaProducto $oferta): bool
    {
        $fila = $this->buscarFilaPorOferta($oferta);
        if ($fila === null || $fila->precio === null) {
            return false;
        }

        $precioCsv = round((float) $fila->precio, 2);
        $precioActual = round((float) $oferta->precio_total, 2);
        if ($precioCsv === $precioActual) {
            return false;
        }

        $oferta->loadMissing('producto');
        $unidades = (float) $oferta->unidades;
        if ($unidades <= 0) {
            return false;
        }

        $calcularPrecioUnidad = new CalcularPrecioUnidad();
        $precioUnidadNuevo = $calcularPrecioUnidad->calcular(
            $oferta->producto->unidadDeMedida ?? 'unidad',
            $precioCsv,
            $unidades
        );
        if ($precioUnidadNuevo === null) {
            $precioUnidadNuevo = round($precioCsv / $unidades, 2);
        }

        DB::table('ofertas_producto')
            ->where('id', $oferta->id)
            ->update([
                'precio_total'  => $precioCsv,
                'precio_unidad' => $precioUnidadNuevo,
            ]);

        $oferta->precio_total = $precioCsv;
        $oferta->precio_unidad = $precioUnidadNuevo;

        return true;
    }

    /**
     * Añade al producto los códigos (ean, isbn, etc.) de una fila CSV si no estaban ya.
     */
    public function fusionarCodigosCsvEnProducto(Producto $producto, CsvOferta $fila): bool
    {
        $estructura = $this->normalizarEstructuraCodigosProducto($producto->ean_isbn_etc);
        if (! $this->fusionarCodigosCsvEnEstructura($estructura, $fila)) {
            return false;
        }

        $producto->ean_isbn_etc = $estructura;
        $producto->save();

        return true;
    }

    /**
     * @return array{ean: list<string>, isbn: list<string>, upc: list<string>, mpn: list<string>, gtin: list<string>}
     */
    public function normalizarEstructuraCodigosProducto(mixed $datos): array
    {
        $estructura = [];
        foreach (self::CAMPOS_CODIGOS_IDENTIFICADOR as $campo) {
            $estructura[$campo] = [];
        }

        if (! is_array($datos)) {
            return $estructura;
        }

        foreach (self::CAMPOS_CODIGOS_IDENTIFICADOR as $campo) {
            $valores = $datos[$campo] ?? [];
            if (! is_array($valores)) {
                $valores = $valores !== null && $valores !== '' ? [(string) $valores] : [];
            }
            foreach ($valores as $valor) {
                $valor = trim((string) $valor);
                if ($valor !== '' && ! in_array($valor, $estructura[$campo], true)) {
                    $estructura[$campo][] = $valor;
                }
            }
        }

        return $estructura;
    }

    /**
     * @param  array{ean: list<string>, isbn: list<string>, upc: list<string>, mpn: list<string>, gtin: list<string>}  $estructura
     */
    public function fusionarCodigosCsvEnEstructura(array &$estructura, CsvOferta $fila): bool
    {
        $antes = json_encode($estructura, JSON_UNESCAPED_UNICODE);

        foreach (self::CAMPOS_CODIGOS_IDENTIFICADOR as $campo) {
            $valor = trim((string) ($fila->{$campo} ?? ''));
            if ($valor === '') {
                continue;
            }
            if (strlen($valor) > 255) {
                $valor = substr($valor, 0, 255);
            }
            if (! in_array($valor, $estructura[$campo], true)) {
                $estructura[$campo][] = $valor;
            }
        }

        return json_encode($estructura, JSON_UNESCAPED_UNICODE) !== $antes;
    }

    public function tieneSinStock(CsvOferta $fila): bool
    {
        return (int) $fila->stock === 0;
    }

    public function tieneStock(CsvOferta $fila): bool
    {
        return (int) $fila->stock === 1;
    }

    public function esAvisoUrlCsvNoEncontrada(string $textoAviso): bool
    {
        return stripos($textoAviso, self::PREFIJO_AVISO_URL_CSV_NO_ENCONTRADA) !== false;
    }

    public function esAvisoSinStock(string $textoAviso): bool
    {
        return stripos($textoAviso, self::PREFIJO_AVISO_SIN_STOCK) !== false;
    }

    public function marcarOfertaUrlCsvNoEncontrada(OfertaProducto $oferta): void
    {
        if ($this->tieneAvisoSinStock($oferta) || $this->tieneAvisoUrlCsvNoEncontrada($oferta)) {
            return;
        }

        $oferta->update(['mostrar' => 'no']);

        $this->insertarAvisoOferta($oferta, self::TEXTO_AVISO_URL_CSV_NO_ENCONTRADA);
    }

    public function marcarOfertaSinStock(OfertaProducto $oferta): void
    {
        $oferta->update(['mostrar' => 'no']);

        $avisoUrlCsv = $this->buscarAvisoUrlCsvNoEncontrada($oferta);
        if ($avisoUrlCsv !== null) {
            $this->convertirAvisoUrlCsvASinStock($avisoUrlCsv, $oferta);

            return;
        }

        if ($this->tieneAvisoSinStock($oferta)) {
            return;
        }

        $this->insertarAvisoOferta($oferta, self::TEXTO_AVISO_SIN_STOCK);
    }

    public function convertirAvisoUrlCsvASinStock(Aviso $aviso, OfertaProducto $oferta): void
    {
        $oferta->update(['mostrar' => 'no']);
        $aviso->update([
            'texto_aviso' => self::TEXTO_AVISO_SIN_STOCK,
            'fecha_aviso' => now()->addDays(self::DIAS_AVISO_SIN_STOCK),
        ]);
    }

    private function tieneAvisoSinStock(OfertaProducto $oferta): bool
    {
        return Aviso::query()
            ->where('avisoable_type', OfertaProducto::class)
            ->where('avisoable_id', $oferta->id)
            ->where('texto_aviso', 'like', self::PREFIJO_AVISO_SIN_STOCK . '%')
            ->exists();
    }

    private function tieneAvisoUrlCsvNoEncontrada(OfertaProducto $oferta): bool
    {
        return Aviso::query()
            ->where('avisoable_type', OfertaProducto::class)
            ->where('avisoable_id', $oferta->id)
            ->where('texto_aviso', 'like', self::PREFIJO_AVISO_URL_CSV_NO_ENCONTRADA . '%')
            ->exists();
    }

    private function buscarAvisoUrlCsvNoEncontrada(OfertaProducto $oferta): ?Aviso
    {
        return Aviso::query()
            ->where('avisoable_type', OfertaProducto::class)
            ->where('avisoable_id', $oferta->id)
            ->where('texto_aviso', 'like', self::PREFIJO_AVISO_URL_CSV_NO_ENCONTRADA . '%')
            ->orderByDesc('id')
            ->first();
    }

    private function insertarAvisoOferta(OfertaProducto $oferta, string $textoAviso): void
    {
        DB::table('avisos')->insert([
            'texto_aviso' => $textoAviso,
            'fecha_aviso' => now()->addDays(self::DIAS_AVISO_SIN_STOCK),
            'user_id' => 1,
            'avisoable_type' => OfertaProducto::class,
            'avisoable_id' => $oferta->id,
            'oculto' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * @return array{nuevo_texto: string, nueva_fecha: \Carbon\Carbon}
     */
    public function calcularSiguienteAplazamientoSinStock(Aviso $aviso): array
    {
        $textoAviso = (string) $aviso->texto_aviso;
        $numeroActual = 0.0;

        if (preg_match('/(\d+(?:\.\d+)?)\s*(?:a\s*)?vez/i', $textoAviso, $matches)) {
            $numeroActual = (float) $matches[1];
        }

        if ($numeroActual <= 0) {
            $nuevoNumero = 1.0;
        } else {
            $nuevoNumero = round($numeroActual + self::INCREMENTO_VEZ_SIN_STOCK, 1);
        }

        $nuevoNumeroTexto = $this->formatearNumeroVez($nuevoNumero);
        $nuevoTexto = preg_replace(
            '/(\d+(?:\.\d+)?)\s*(?:a\s*)?vez/i',
            $nuevoNumeroTexto . 'a vez',
            $textoAviso,
            1
        );

        if ($nuevoTexto === null || $nuevoTexto === $textoAviso) {
            $nuevoTexto = trim($textoAviso) . ' - ' . $nuevoNumeroTexto . 'a vez';
        }

        return [
            'nuevo_texto' => $nuevoTexto,
            'nueva_fecha' => \Carbon\Carbon::parse($aviso->fecha_aviso)->addDay(),
        ];
    }

    private function formatearNumeroVez(float $numero): string
    {
        if (fmod($numero, 1.0) === 0.0) {
            return (string) (int) $numero;
        }

        return rtrim(rtrim(number_format($numero, 1, '.', ''), '0'), '.');
    }
}
