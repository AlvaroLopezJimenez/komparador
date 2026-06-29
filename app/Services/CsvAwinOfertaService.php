<?php

namespace App\Services;

use App\Models\Aviso;
use App\Models\CsvOferta;
use App\Models\OfertaProducto;
use App\Models\Tienda;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class CsvAwinOfertaService
{
    public const DIAS_AVISO_SIN_STOCK = 1;

    public const INCREMENTO_VEZ_SIN_STOCK = 0.1;

    public const VEZ_MAXIMA_PROCESAR = 9.9;

    public function __construct(
        private readonly LimpiarUrlDeTiendas $limpiarUrlDeTiendas,
        private readonly ConsultarNeoCifrado $consultarNeoCifrado,
    ) {}

    public function obtenerPrecioJson(string $url, Tienda $tienda, ?OfertaProducto $oferta = null): JsonResponse
    {
        $fila = $this->buscarFila($tienda, $url);

        if ($fila === null) {
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

    public function tieneSinStock(CsvOferta $fila): bool
    {
        return (int) $fila->stock === 0;
    }

    public function tieneStock(CsvOferta $fila): bool
    {
        return (int) $fila->stock === 1;
    }

    public function marcarOfertaSinStock(OfertaProducto $oferta): void
    {
        $oferta->update(['mostrar' => 'no']);

        $yaExiste = Aviso::query()
            ->where('avisoable_type', OfertaProducto::class)
            ->where('avisoable_id', $oferta->id)
            ->where('texto_aviso', 'like', 'Sin stock%')
            ->exists();

        if ($yaExiste) {
            return;
        }

        DB::table('avisos')->insert([
            'texto_aviso' => 'Sin stock 1a vez - Generado automaticamente',
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
