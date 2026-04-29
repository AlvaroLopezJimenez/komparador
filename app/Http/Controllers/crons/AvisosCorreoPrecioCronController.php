<?php

namespace App\Http\Controllers\Crons;

use App\Http\Controllers\Controller;
use App\Models\Aviso;
use App\Models\CorreoAvisoPrecio;
use App\Models\OfertaProducto;

class AvisosCorreoPrecioCronController extends Controller
{
    public function __invoke(): int
    {
        $suscripciones = CorreoAvisoPrecio::query()
            ->with('producto')
            ->where(function ($query) {
                $query->whereNull('ultimo_envio_correo')
                    ->orWhere('ultimo_envio_correo', '<=', now()->subDays(7));
            })
            ->get();

        foreach ($suscripciones as $suscripcion) {
            $producto = $suscripcion->producto;
            if (!$producto) {
                continue;
            }

            $ofertas = OfertaProducto::query()
                ->where('producto_id', $producto->id)
                ->where('mostrar', 'si')
                ->whereNotNull('precio_unidad')
                ->get();

            $ofertasFiltradas = $this->filtrarOfertasPorEspecificaciones(
                $ofertas->all(),
                is_array($suscripcion->especificaciones_internas_seleccionadas)
                    ? $suscripcion->especificaciones_internas_seleccionadas
                    : []
            );

            if (empty($ofertasFiltradas)) {
                continue;
            }

            $precioMinimo = collect($ofertasFiltradas)
                ->map(function (OfertaProducto $oferta) {
                    return (float) $oferta->precio_unidad;
                })
                ->min();

            if ($precioMinimo > (float) $suscripcion->precio_limite) {
                continue;
            }

            $existeAviso = Aviso::query()
                ->where('avisoable_type', CorreoAvisoPrecio::class)
                ->where('avisoable_id', $suscripcion->id)
                ->where('oculto', false)
                ->exists();

            if ($existeAviso) {
                continue;
            }

            $texto = 'Aviso de correo pendiente - '
                . $suscripcion->correo
                . ' - limite: '
                . number_format((float) $suscripcion->precio_limite, 2, '.', '')
                . ' - precio actual: '
                . number_format((float) $precioMinimo, 2, '.', '');

            Aviso::create([
                'texto_aviso' => $texto,
                'fecha_aviso' => now(),
                'user_id' => 1,
                'avisoable_type' => CorreoAvisoPrecio::class,
                'avisoable_id' => $suscripcion->id,
                'oculto' => false,
            ]);
        }

        return 0;
    }

    /**
     * @param  OfertaProducto[]  $ofertas
     * @param  array<string, array<int, string|int>>  $seleccionadas
     * @return OfertaProducto[]
     */
    private function filtrarOfertasPorEspecificaciones(array $ofertas, array $seleccionadas): array
    {
        $seleccion = collect($seleccionadas)
            ->filter(function ($ids, $lineaId) {
                if (!is_array($ids) || $lineaId === 'precio_min' || $lineaId === 'precio_max') {
                    return false;
                }

                return count($ids) > 0;
            })
            ->map(function ($ids) {
                return array_values(array_map('strval', $ids));
            })
            ->toArray();

        if (empty($seleccion)) {
            return $ofertas;
        }

        return array_values(array_filter($ofertas, function (OfertaProducto $oferta) use ($seleccion) {
            $especificacionesOferta = is_array($oferta->especificaciones_internas)
                ? $oferta->especificaciones_internas
                : [];

            foreach ($seleccion as $lineaId => $sublineasSeleccionadas) {
                $sublineasOfertaRaw = $especificacionesOferta[$lineaId] ?? [];
                if (!is_array($sublineasOfertaRaw)) {
                    return false;
                }

                $sublineasOferta = array_map('strval', $sublineasOfertaRaw);
                if (count(array_intersect($sublineasSeleccionadas, $sublineasOferta)) === 0) {
                    return false;
                }
            }

            return true;
        }));
    }
}
