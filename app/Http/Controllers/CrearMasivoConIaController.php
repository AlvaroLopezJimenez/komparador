<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\Neo;
use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CrearMasivoConIaController extends Controller
{
    public function index()
    {
        $totalNeoAniadidaNo = Neo::where('aniadida', 'no')
            ->where(function ($q) {
                $q->whereNull('tienda_id')
                    ->orWhereDoesntHave('tienda', function ($tq) {
                        $tq->where('mostrar_tienda', 'no')
                            ->where('scrapear', 'no');
                    });
            })
            ->count();

        $totalNeoAniadidaNoSinUrl = Neo::where('aniadida', 'no')
            ->where(function ($q) {
                $q->whereNull('url_cipher')->orWhere('url_cipher', '');
            })
            ->count();

        $totalProductosNeoAniadidaNo = (int) Neo::where('aniadida', 'no')
            ->whereNotNull('url_cipher')
            ->where('url_cipher', '!=', '')
            ->whereNotNull('producto_id')
            ->selectRaw('count(distinct producto_id) as c')
            ->value('c');

        $totalCategoriasNeoAniadidaNo = (int) Neo::where('aniadida', 'no')
            ->whereNotNull('url_cipher')
            ->where('url_cipher', '!=', '')
            ->whereNotNull('categoria_id')
            ->selectRaw('count(distinct categoria_id) as c')
            ->value('c');

        $totalTiendasNeoAniadidaNo = (int) Neo::where('aniadida', 'no')
            ->whereNotNull('url_cipher')
            ->where('url_cipher', '!=', '')
            ->whereNotNull('tienda_id')
            ->selectRaw('count(distinct tienda_id) as c')
            ->value('c');

        $categoriasRaiz = Categoria::categoriasRaizConConteosAdministracion();

        return view('admin.neo.crear-masivo-ia', compact(
            'totalNeoAniadidaNo',
            'totalNeoAniadidaNoSinUrl',
            'totalProductosNeoAniadidaNo',
            'totalCategoriasNeoAniadidaNo',
            'totalTiendasNeoAniadidaNo',
            'categoriasRaiz'
        ));
    }

    public function palabrasClave(Request $request)
    {
        $validated = $request->validate([
            'url' => 'required|string|max:2048',
            'titulo_producto' => 'nullable|string|max:500',
            'motivo' => 'nullable|string|max:1000',
            'chatgpt_model' => 'nullable|string|max:64',
        ]);

        $url = trim($validated['url']);
        $titulo = trim((string) ($validated['titulo_producto'] ?? ''));
        $motivo = trim((string) ($validated['motivo'] ?? ''));

        $prompt = "Necesito buscar en una base de datos interna el producto correcto para esta URL.\n\n"
            . "URL: {$url}\n"
            . ($titulo !== '' ? "Producto candidato rechazado o dudoso: {$titulo}\n" : '')
            . ($motivo !== '' ? "Motivo/contexto: {$motivo}\n" : '')
            . "\nDevuelve maximo 3 palabras clave utiles para buscar el producto en la base de datos. "
            . "Usa palabras del modelo real, marca o memoria/capacidad. No inventes nada.\n\n"
            . "RESPONDE SOLO JSON:\n"
            . "{\"palabras\":[\"palabra1\",\"palabra2\",\"palabra3\"],\"motivo\":\"breve\"}";

        [$data, $raw] = $this->llamarChatgptJson($prompt, $validated['chatgpt_model'] ?? null, 600);
        $palabras = $this->limpiarPalabrasClave($data['palabras'] ?? []);
        if ($palabras === []) {
            $palabras = $this->palabrasFallbackDesdeUrl($url);
        }

        return response()->json([
            'success' => true,
            'palabras' => array_slice($palabras, 0, 3),
            'prompt' => $prompt,
            'raw_content' => $raw,
            'motivo' => is_string($data['motivo'] ?? null) ? $data['motivo'] : null,
        ]);
    }

    public function elegirProducto(Request $request)
    {
        $validated = $request->validate([
            'url' => 'required|string|max:2048',
            'productos' => 'required|array',
            'productos.*.id' => 'required|integer|exists:productos,id',
            'productos.*.texto_completo' => 'nullable|string|max:1000',
            'chatgpt_model' => 'nullable|string|max:64',
        ]);

        $productos = collect($validated['productos'])
            ->take(10)
            ->map(function (array $producto) {
                return [
                    'id' => (int) $producto['id'],
                    'texto_completo' => (string) ($producto['texto_completo'] ?? ''),
                ];
            })
            ->values()
            ->all();

        $prompt = "Elige cual de estos productos internos corresponde a la URL. "
            . "Si ninguno encaja claramente, devuelve producto_id null. No inventes.\n\n"
            . "URL: {$validated['url']}\n"
            . "Productos candidatos: " . json_encode($productos, JSON_UNESCAPED_UNICODE) . "\n\n"
            . "RESPONDE SOLO JSON:\n"
            . "{\"producto_id\":123,\"motivo\":\"breve\"}";

        [$data, $raw] = $this->llamarChatgptJson($prompt, $validated['chatgpt_model'] ?? null, 700);
        $productoId = $data['producto_id'] ?? null;
        $idsValidos = collect($productos)->pluck('id')->all();
        if ($productoId !== null && !in_array((int) $productoId, $idsValidos, true)) {
            $productoId = null;
        }

        return response()->json([
            'success' => true,
            'producto_id' => $productoId !== null ? (int) $productoId : null,
            'prompt' => $prompt,
            'raw_content' => $raw,
            'motivo' => is_string($data['motivo'] ?? null) ? $data['motivo'] : null,
        ]);
    }

    public function completarEspecificaciones(Request $request)
    {
        $validated = $request->validate([
            'url' => 'required|string|max:2048',
            'producto_id' => 'required|integer|exists:productos,id',
            'especificaciones' => 'nullable|array',
            'chatgpt_model' => 'nullable|string|max:64',
        ]);

        $producto = Producto::findOrFail((int) $validated['producto_id'], ['id', 'nombre', 'marca', 'modelo', 'talla']);
        $especificaciones = $validated['especificaciones'] ?? [];
        $grupos = $this->normalizarGruposEspecificaciones($especificaciones);

        if ($grupos === []) {
            return response()->json([
                'success' => true,
                'selecciones' => [],
                'prompt' => null,
                'raw_content' => null,
                'motivo' => 'El producto no tiene especificaciones internas para completar.',
            ]);
        }

        $productoTexto = trim($producto->nombre . ' - ' . ($producto->marca ?? '') . ' - ' . ($producto->modelo ?? '') . ' - ' . ($producto->talla ?? ''), ' -');
        $prompt = "Para esta URL y este producto interno, marca especificaciones internas.\n\n"
            . "URL: {$validated['url']}\n"
            . "Producto interno: {$productoTexto}\n"
            . "Grupos y opciones disponibles: " . json_encode($grupos, JSON_UNESCAPED_UNICODE) . "\n\n"
            . "Reglas: marca como maximo UNA opcion por grupo. Si no estas seguro en un grupo, omitelo. "
            . "No inventes opciones, usa solo ids existentes.\n\n"
            . "RESPONDE SOLO JSON:\n"
            . "{\"selecciones\":{\"grupo_id\":[\"opcion_id\"]},\"motivo\":\"breve\"}";

        [$data, $raw] = $this->llamarChatgptJson($prompt, $validated['chatgpt_model'] ?? null, 1200);
        $selecciones = $this->filtrarSelecciones($data['selecciones'] ?? [], $grupos);

        return response()->json([
            'success' => true,
            'selecciones' => $selecciones,
            'prompt' => $prompt,
            'raw_content' => $raw,
            'motivo' => is_string($data['motivo'] ?? null) ? $data['motivo'] : null,
        ]);
    }

    private function llamarChatgptJson(string $prompt, ?string $model = null, int $maxTokens = 1000): array
    {
        $apiKey = config('services.openai.api_key');
        if (!$apiKey) {
            return [[], null];
        }

        try {
            $client = \OpenAI::client($apiKey);
            $response = $client->chat()->create([
                'model' => $model && trim($model) !== '' ? trim($model) : 'gpt-4o',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Responde siempre solo con un objeto JSON valido. Si no estas seguro, devuelve null o omite el dato; no inventes.',
                    ],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'temperature' => 0.1,
                'max_tokens' => $maxTokens,
                'response_format' => ['type' => 'json_object'],
            ]);
        } catch (\Throwable $e) {
            Log::warning('Crear masivo IA: error llamando OpenAI', [
                'error' => $e->getMessage(),
            ]);

            return [[], null];
        }

        $raw = $response->choices[0]->message->content ?? '';
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            return [[], $raw];
        }

        return [$data, $raw];
    }

    private function limpiarPalabrasClave($palabras): array
    {
        if (!is_array($palabras)) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map(function ($palabra) {
            $palabra = trim((string) $palabra);
            $palabra = preg_replace('/[^\pL\pN\-\+ ]/u', '', $palabra) ?? '';

            return $palabra !== '' ? mb_substr($palabra, 0, 60, 'UTF-8') : '';
        }, $palabras))));
    }

    private function palabrasFallbackDesdeUrl(string $url): array
    {
        $path = parse_url($url, PHP_URL_PATH) ?: $url;
        $segmentos = array_values(array_filter(explode('/', trim($path, '/'))));
        $slug = (string) end($segmentos);
        if ($slug === '' && $path !== '') {
            $slug = $path;
        }

        $tokens = array_values(array_filter(explode('-', mb_strtolower($slug, 'UTF-8')), function ($token) {
            return mb_strlen($token, 'UTF-8') >= 2;
        }));

        usort($tokens, function ($a, $b) {
            $aFuerte = preg_match('/\d/', $a) ? 1 : 0;
            $bFuerte = preg_match('/\d/', $b) ? 1 : 0;
            if ($aFuerte !== $bFuerte) {
                return $bFuerte <=> $aFuerte;
            }

            return mb_strlen($b, 'UTF-8') <=> mb_strlen($a, 'UTF-8');
        });

        return array_slice(array_values(array_unique($tokens)), 0, 3);
    }

    private function normalizarGruposEspecificaciones(array $especificaciones): array
    {
        $filtros = $especificaciones['filtros'] ?? [];
        if (!is_array($filtros)) {
            return [];
        }

        $grupos = [];
        foreach ($filtros as $filtro) {
            $grupoId = (string) ($filtro['id'] ?? '');
            if ($grupoId === '') {
                continue;
            }

            $opciones = [];
            foreach (($filtro['subprincipales'] ?? []) as $sub) {
                $opcionId = (string) ($sub['id'] ?? '');
                $texto = trim((string) ($sub['texto'] ?? ''));
                if ($opcionId === '' || $texto === '') {
                    continue;
                }
                $opciones[] = [
                    'id' => $opcionId,
                    'texto' => $texto,
                ];
            }

            if ($opciones !== []) {
                $grupos[] = [
                    'id' => $grupoId,
                    'texto' => trim((string) ($filtro['texto'] ?? $grupoId)),
                    'opciones' => $opciones,
                ];
            }
        }

        return $grupos;
    }

    private function filtrarSelecciones($selecciones, array $grupos): array
    {
        if (!is_array($selecciones)) {
            return [];
        }

        $idsValidos = [];
        foreach ($grupos as $grupo) {
            $gid = (string) ($grupo['id'] ?? '');
            $idsValidos[$gid] = [];
            foreach (($grupo['opciones'] ?? []) as $opcion) {
                $idsValidos[$gid][(string) ($opcion['id'] ?? '')] = true;
            }
        }

        $out = [];
        foreach ($selecciones as $grupoId => $valor) {
            $gid = (string) $grupoId;
            if (!isset($idsValidos[$gid])) {
                continue;
            }

            $valores = is_array($valor) ? array_values($valor) : [$valor];
            foreach ($valores as $opcionId) {
                $oid = (string) $opcionId;
                if ($oid !== '' && isset($idsValidos[$gid][$oid])) {
                    $out[$gid] = [$oid];
                    break;
                }
            }
        }

        return $out;
    }
}
