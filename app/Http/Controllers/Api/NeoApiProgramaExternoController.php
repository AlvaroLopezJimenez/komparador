<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Neo;
use App\Models\Neoobjetivo;
use App\Models\OfertaProducto;
use App\Models\UrlDescartada;
use App\Services\ConsultarNeoCifrado;
use App\Services\LimpiarUrlDeTiendas;
use App\Services\NeoProgramaExternoRamaNeoUrlNormalizer;
use DateTimeInterface;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * API exclusiva del programa externo del flujo Neo (rama Neo + sincronización).
 * No modifica ni invoca el cron; replica reglas de negocio alineadas con él.
 */
class NeoApiProgramaExternoController extends Controller
{
    /**
     * GET neoobjetivos de rama Neo con visitada &gt; 7 días (URLs en claro).
     *
     * Query: limite (1–500, default 200)
     */
    public function neoobjetivosRamaNeoPendientes(Request $request): JsonResponse
    {
        $limite = min(500, max(1, (int) $request->query('limite', 200)));

        $candidatas = Neoobjetivo::query()
            ->where('visitada', '<', now()->subDays(7))
            ->whereNotNull('url_cipher')
            ->where('url_cipher', '!=', '')
            ->orderBy('visitada')
            ->limit(max($limite * 5, 500))
            ->get();

        $out = [];
        foreach ($candidatas as $n) {
            if (count($out) >= $limite) {
                break;
            }
            $url = trim((string) $n->url);
            if ($url === '' || strtolower($url) === 'no encontrado') {
                continue;
            }
            if (!NeoProgramaExternoRamaNeoUrlNormalizer::esRamaNeoObjetivoUrl($url)) {
                continue;
            }
            $out[] = [
                'id'            => $n->id,
                'url'           => $url,
                'visitada'      => $n->visitada?->format(DateTimeInterface::ATOM),
                'oferta_id'     => $n->oferta_id,
                'producto_id'   => $n->producto_id,
                'categoria_id'  => $n->categoria_id,
                'tienda_id'     => $n->tienda_id,
            ];
        }

        return response()->json([
            'ok'           => true,
            'total'        => count($out),
            'neoobjetivos' => $out,
        ]);
    }

    /**
     * POST comprobar si URLs (leadout / sucias) ya tienen fila neo con neo.neo y URL destino rellena.
     *
     * Body: { "urls": ["..."] } (máx. 100)
     */
    public function comprobarExisteNeoNeo(Request $request): JsonResponse
    {
        $data = $request->validate([
            'urls'   => ['required', 'array', 'min:1', 'max:100'],
            'urls.*' => ['required', 'string', 'max:2048'],
        ]);

        $resultados = [];
        foreach ($data['urls'] as $urlRaw) {
            $limpia = NeoProgramaExternoRamaNeoUrlNormalizer::limpiarHrefRelocatorLeadout($urlRaw);
            if ($limpia === '') {
                $limpia = app(LimpiarUrlDeTiendas::class)->limpiar(trim((string) $urlRaw));
            }

            $neoLookup = $limpia !== '' ? Neo::encryptedNeoForLookup($limpia) : '';
            $fila = ($neoLookup !== '') ? Neo::where('neo_lookup', $neoLookup)->first() : null;

            $tieneUrlDestino = $fila !== null && trim((string) $fila->url) !== '';
            $respuestaSi = $fila !== null && $tieneUrlDestino;

            $resultados[] = [
                'url_enviada'               => $urlRaw,
                'url_neo_neo_normalizada' => $limpia,
                'existe_en_neo_neo'         => $fila !== null,
                'tiene_url_destino'         => $tieneUrlDestino,
                'respuesta_si'              => $respuestaSi,
                'neo_id'                    => $fila?->id,
            ];
        }

        return response()->json([
            'ok'         => true,
            'resultados' => $resultados,
        ]);
    }

    /**
     * POST sincronizar fila neo con datos del programa externo (URL listado neoobjetivo + relocator + URL final).
     *
     * Body: { "neoobjetivo_url": "...", "neo_neo": "...", "neo_url": "..." }
     */
    public function sincronizarNeo(Request $request): JsonResponse
    {
        $data = $request->validate([
            'neoobjetivo_url' => ['required', 'string', 'max:2048'],
            'neo_neo'          => ['required', 'string', 'max:2048'],
            'neo_url'          => ['required', 'string', 'max:2048'],
        ]);

        $neoobjetivoUrl = trim($data['neoobjetivo_url']);
        $neoNeoLimpia = NeoProgramaExternoRamaNeoUrlNormalizer::limpiarHrefRelocatorLeadout($data['neo_neo']);
        $finalLimpia = app(LimpiarUrlDeTiendas::class)->limpiar($data['neo_url']);

        if ($neoNeoLimpia === '') {
            return response()->json([
                'ok'    => false,
                'error' => 'neo_neo quedó vacío tras normalizar.',
            ], 422);
        }
        if ($finalLimpia === '') {
            return response()->json([
                'ok'    => false,
                'error' => 'neo_url quedó vacía tras LimpiarUrlDeTiendas.',
            ], 422);
        }

        // URL destino no puede seguir apuntando a Idealo (p. ej. redirección no resuelta).
        if (str_contains(mb_strtolower($finalLimpia), 'idealo.es')) {
            return response()->json([
                'ok'                    => false,
                'error'                 => 'La URL destino (neo_url) tras limpiar sigue siendo de idealo.es; no se guarda en el sistema.',
                'neo_url_aceptada'      => false,
                'codigo_rechazo'        => 'neo_url_contiene_idealo',
                'neo_url_limpia'        => $finalLimpia,
            ], 422);
        }

        $lookupObj = app(ConsultarNeoCifrado::class)->hashLookup($neoobjetivoUrl);
        if ($lookupObj === '') {
            return response()->json([
                'ok'    => false,
                'error' => 'No se pudo calcular lookup de neoobjetivo_url.',
            ], 422);
        }

        $neoobjetivo = Neoobjetivo::where('url_lookup', $lookupObj)->first();
        if (!$neoobjetivo) {
            return response()->json([
                'ok'    => false,
                'error' => 'No se encontró neoobjetivo para la URL de listado indicada.',
            ], 422);
        }

        $neoNeoLookup = Neo::encryptedNeoForLookup($neoNeoLimpia);
        $finalLookup = Neo::encryptedNeoForLookup($finalLimpia);
        if ($neoNeoLookup === '' || $finalLookup === '') {
            return response()->json([
                'ok'    => false,
                'error' => 'No se pudo calcular lookup neo (revisa NEO_LOOKUP_KEY).',
            ], 503);
        }

        $rowNeo = Neo::where('neo_lookup', $neoNeoLookup)->first();
        $rowUrl = Neo::where('url_lookup', $finalLookup)->first();

        if ($rowNeo && $rowUrl && (int) $rowNeo->id !== (int) $rowUrl->id) {
            return response()->json([
                'ok'                 => false,
                'error'              => 'Conflicto: neo_neo y neo_url corresponden a filas neo distintas.',
                'neo_por_neo_neo_id' => $rowNeo->id,
                'neo_por_url_id'     => $rowUrl->id,
            ], 409);
        }

        if ($rowNeo) {
            $payload = $this->sincronizarCuandoExistePorNeoNeo($rowNeo, $neoobjetivo, $neoNeoLimpia, $finalLimpia);

            return response()->json($payload, !empty($payload['ok']) ? 200 : 422);
        }

        if ($rowUrl) {
            return response()->json(
                $this->sincronizarCuandoExisteSoloPorUrl($rowUrl, $neoobjetivo, $neoNeoLimpia, $finalLimpia)
            );
        }

        return response()->json(
            $this->sincronizarCrearFila($neoobjetivo, $neoNeoLimpia, $finalLimpia)
        );
    }

    /**
     * @return array{ok: bool, accion?: string, neo_id?: int, aniadida?: string, error?: string, url_destino_actual?: string}
     */
    private function sincronizarCuandoExistePorNeoNeo(
        Neo $row,
        Neoobjetivo $neoobjetivo,
        string $neoNeoLimpia,
        string $finalLimpia
    ): array {
        $urlDestinoVacia = trim((string) $row->url) === '';

        if ($urlDestinoVacia) {
            $row->url = $finalLimpia;
            $row->neo = $neoNeoLimpia;
            $this->complementarIdsNeoDesdeNeoobjetivo($row, $neoobjetivo);
            $row->aniadida = $this->resolverAniadida($finalLimpia);
            $row->save();

            return [
                'ok'       => true,
                'accion'   => 'actualizado_url_destino_vacia',
                'neo_id'   => $row->id,
                'aniadida' => $row->aniadida,
            ];
        }

        $urlActual = trim((string) $row->url);
        if ($urlActual !== $finalLimpia) {
            return [
                'ok'                  => false,
                'error'               => 'La fila neo ya tiene otra URL destino; no se sobrescribe.',
                'neo_id'              => $row->id,
                'url_destino_actual'  => $urlActual,
            ];
        }

        $row->neo = $neoNeoLimpia;
        $this->complementarIdsNeoDesdeNeoobjetivo($row, $neoobjetivo);
        $row->aniadida = $this->resolverAniadida($finalLimpia);
        $row->save();

        return [
            'ok'       => true,
            'accion'   => 'complementado_misma_url_destino',
            'neo_id'   => $row->id,
            'aniadida' => $row->aniadida,
        ];
    }

    /**
     * @return array{ok: bool, accion?: string, neo_id?: int, aniadida?: string}
     */
    private function sincronizarCuandoExisteSoloPorUrl(
        Neo $row,
        Neoobjetivo $neoobjetivo,
        string $neoNeoLimpia,
        string $finalLimpia
    ): array {
        $row->neo = $neoNeoLimpia;
        $this->complementarIdsNeoDesdeNeoobjetivo($row, $neoobjetivo);
        $row->aniadida = $this->resolverAniadida($finalLimpia);
        $row->save();

        return [
            'ok'       => true,
            'accion'   => 'actualizado_por_url_destino_existente',
            'neo_id'   => $row->id,
            'aniadida' => $row->aniadida,
        ];
    }

    /**
     * @return array{ok: bool, accion?: string, neo_id?: int, aniadida?: string}
     */
    private function sincronizarCrearFila(
        Neoobjetivo $neoobjetivo,
        string $neoNeoLimpia,
        string $finalLimpia
    ): array {
        $aniadida = $this->resolverAniadida($finalLimpia);
        $neo = Neo::create([
            'oferta_id'    => $neoobjetivo->oferta_id,
            'producto_id'  => $neoobjetivo->producto_id,
            'categoria_id' => $neoobjetivo->categoria_id,
            'tienda_id'    => $neoobjetivo->tienda_id,
            'url'          => $finalLimpia,
            'neo'          => $neoNeoLimpia,
            'aniadida'     => $aniadida,
        ]);

        return [
            'ok'       => true,
            'accion'   => 'creado',
            'neo_id'   => $neo->id,
            'aniadida' => $aniadida,
        ];
    }

    private function complementarIdsNeoDesdeNeoobjetivo(Neo $neo, Neoobjetivo $no): void
    {
        foreach (['oferta_id', 'producto_id', 'categoria_id', 'tienda_id'] as $campo) {
            if ($neo->{$campo} === null && $no->{$campo} !== null) {
                $neo->{$campo} = $no->{$campo};
            }
        }
    }

    private function resolverAniadida(string $finalUrlLimpia): string
    {
        $lookup = app(ConsultarNeoCifrado::class)->hashLookup($finalUrlLimpia);
        if ($lookup !== '' && OfertaProducto::where('url_lookup', $lookup)->exists()) {
            return 'si';
        }
        if (UrlDescartada::where('url', $finalUrlLimpia)->exists()) {
            return 'si';
        }

        return 'no';
    }
}
