<?php

namespace App\Http\Controllers\Scraping\Tiendas;

use App\Http\Controllers\Scraping\Tiendas\PlantillaTiendaController;
use App\Models\OfertaProducto;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

/**
 * Coolmod: extrae el precio desde el atributo data-itemprice en el HTML devuelto por la API.
 * Si detecta "Artículo no disponible", pone la oferta en mostrar-no y crea aviso a 4 días.
 */
class CoolmodController extends PlantillaTiendaController
{
    public function obtenerPrecio($url, $variante = null, $tienda = null, $oferta = null): JsonResponse
    {
        $resultado = $this->apiHTML->obtenerHTML($url, null, $tienda ? $tienda->api : null);

        if (!is_array($resultado) || empty($resultado['success']) || empty($resultado['html'])) {
            $msg = is_array($resultado) ? ($resultado['error'] ?? 'Error desconocido') : 'Respuesta inválida de la API';
            return response()->json(['success' => false, 'error' => 'No se pudo obtener el HTML de Coolmod: ' . $msg]);
        }

        $html = html_entity_decode((string)$resultado['html'], ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Detección sin stock: "Artículo no disponible"
        if ($this->esSinStock($html)) {
            if ($oferta && $oferta instanceof OfertaProducto) {
                $oferta->update(['mostrar' => 'no']);
                DB::table('avisos')->insert([
                    'texto_aviso'     => 'Sin stock 1a vez - Generado automaticamente',
                    'fecha_aviso'     => now()->addDays(4),
                    'user_id'         => 1,
                    'avisoable_type'  => OfertaProducto::class,
                    'avisoable_id'    => $oferta->id,
                    'oculto'          => 0,
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ]);
            }
            return response()->json(['success' => false, 'error' => 'Artículo no disponible en Coolmod.']);
        }

        $precio = $this->extraerPrecioDesdeDataItemprice($html);

        if ($precio === null) {
            return response()->json(['success' => false, 'error' => 'No se encontró data-itemprice en el HTML.']);
        }

        return response()->json(['success' => true, 'precio' => $precio]);
    }

    /**
     * Detecta si la página indica que el artículo no está disponible.
     */
    private function esSinStock(string $html): bool
    {
        return str_contains($html, 'Artículo no disponible');
    }

    /**
     * Extrae el precio del atributo data-itemprice (ej: data-itemprice="669.95")
     */
    private function extraerPrecioDesdeDataItemprice(string $html): ?float
    {
        if (preg_match('~data-itemprice\s*=\s*"([^"]+)"~i', $html, $m) && isset($m[1])) {
            return $this->normalizarImporte($m[1]);
        }
        if (preg_match("~data-itemprice\s*=\s*'([^']+)'~i", $html, $m) && isset($m[1])) {
            return $this->normalizarImporte($m[1]);
        }
        return null;
    }

    /** Normaliza el valor a float (acepta "669.95", "1.234,56", etc.) */
    private function normalizarImporte(string $importe): ?float
    {
        $importe = str_replace(["\xc2\xa0", "\xe2\x80\xaf", '&nbsp;'], ' ', $importe);
        $s = preg_replace('/[^\d\,\.]/u', '', $importe);
        if ($s === null || $s === '') return null;

        $tieneComa  = strpos($s, ',') !== false;
        $tienePunto = strpos($s, '.') !== false;

        if ($tieneComa && $tienePunto) {
            $lastComa = strrpos($s, ',');
            $lastDot  = strrpos($s, '.');
            if ($lastComa !== false && ($lastDot === false || $lastComa > $lastDot)) {
                $s = str_replace('.', '', $s);
                $s = str_replace(',', '.', $s);
            } else {
                $s = str_replace(',', '', $s);
            }
        } elseif ($tieneComa) {
            $s = str_replace(',', '.', $s);
        }

        if (!preg_match('/^\d+(\.\d+)?$/', $s)) return null;
        return (float)$s;
    }
}
