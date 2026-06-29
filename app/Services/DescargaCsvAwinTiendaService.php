<?php

namespace App\Services;

use App\Models\Aviso;
use App\Models\CsvOferta;
use App\Models\Tienda;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DescargaCsvAwinTiendaService
{
    public const TIPO_FALLO_DESCARGA = 'descarga';
    public const TIPO_FALLO_DESCOMPRESION = 'descompresion';
    public const TIPO_FALLO_DIVISION = 'division';
    public const TIPO_FALLO_COLUMNAS = 'columnas';
    public const TIPO_FALLO_BASE_DATOS = 'base_datos';

    private const UMBRAL_DIVIDIR_BYTES = 50 * 1024 * 1024;
    private const TAMANO_PARTE_BYTES = 45 * 1024 * 1024;
    private const TAMANO_LOTE_UPSERT = 500;
    private const TIMEOUT_DESCARGA_SEGUNDOS = 900;
    /** Máximo admitido por csv_ofertas.precio (decimal 8,2) */
    private const PRECIO_MAXIMO_CSV = 999999.99;

    /** @var list<string> */
    private const COLUMNAS_REQUERIDAS = [
        'merchant_deep_link',
        'search_price',
        'delivery_cost',
        'in_stock',
    ];

    public function __construct(
        private readonly LimpiarUrlDeTiendas $limpiarUrlDeTiendas,
        private readonly ConsultarNeoCifrado $consultarNeoCifrado,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function procesarTienda(Tienda $tienda): array
    {
        $urls = $this->normalizarUrlsTienda($tienda);
        if ($urls === []) {
            return [
                'tienda_id' => $tienda->id,
                'tienda' => $tienda->nombre,
                'omitida' => true,
                'motivo' => 'Sin enlaces CSV configurados',
            ];
        }

        $resumen = [
            'tienda_id' => $tienda->id,
            'tienda' => $tienda->nombre,
            'urls_procesadas' => 0,
            'filas_insertadas_o_actualizadas' => 0,
            'filas_omitidas' => 0,
            'errores' => [],
        ];

        foreach ($urls as $indiceUrl => $urlDescarga) {
            $dirTrabajo = $this->crearDirectorioTrabajo($tienda->id, $indiceUrl);

            try {
                $archivoGz = $dirTrabajo . '/feed.csv.gz';
                $this->descargarArchivo($tienda, $urlDescarga, $archivoGz);

                $archivoCsv = $dirTrabajo . '/feed.csv';
                $this->descomprimirGz($tienda, $archivoGz, $archivoCsv);

                $partes = $this->dividirCsvSiNecesario($tienda, $archivoCsv, $dirTrabajo);

                foreach ($partes as $parte) {
                    $resultadoParte = $this->procesarArchivoCsv($tienda, $parte);
                    $resumen['filas_insertadas_o_actualizadas'] += $resultadoParte['procesadas'];
                    $resumen['filas_omitidas'] += $resultadoParte['omitidas'];
                }

                $resumen['urls_procesadas']++;
            } catch (\Throwable $e) {
                $mensaje = $e->getMessage();
                $resumen['errores'][] = $mensaje;
                Log::error('DescargaCsvAwinTiendaService: error procesando tienda', [
                    'tienda_id' => $tienda->id,
                    'url' => $urlDescarga,
                    'error' => $mensaje,
                ]);
            } finally {
                $this->limpiarDirectorio($dirTrabajo);
            }
        }

        return $resumen;
    }

    /**
     * @return list<string>
     */
    private function normalizarUrlsTienda(Tienda $tienda): array
    {
        $raw = $tienda->url_csv;
        if (!is_array($raw)) {
            return [];
        }

        $urls = [];
        foreach ($raw as $url) {
            if (!is_string($url)) {
                continue;
            }
            $url = trim($url);
            if ($url !== '') {
                $urls[] = $url;
            }
        }

        return array_values(array_unique($urls));
    }

    private function crearDirectorioTrabajo(int $tiendaId, int $indiceUrl): string
    {
        $dir = storage_path('app/csv-awin/tienda_' . $tiendaId . '_' . $indiceUrl . '_' . Str::random(8));
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new \RuntimeException('No se pudo crear el directorio temporal de trabajo.');
        }

        return $dir;
    }

    private function limpiarDirectorio(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $archivos = glob($dir . '/*') ?: [];
        foreach ($archivos as $archivo) {
            if (is_file($archivo)) {
                @unlink($archivo);
            }
        }
        @rmdir($dir);
    }

    private function descargarArchivo(Tienda $tienda, string $urlDescarga, string $destino): void
    {
        try {
            $response = Http::timeout(self::TIMEOUT_DESCARGA_SEGUNDOS)
                ->withOptions([
                    'sink' => $destino,
                    'allow_redirects' => true,
                ])
                ->get($urlDescarga);

            if (!$response->successful()) {
                throw new \RuntimeException('HTTP ' . $response->status());
            }

            if (!is_file($destino) || filesize($destino) === 0) {
                throw new \RuntimeException('El archivo descargado está vacío.');
            }
        } catch (\Throwable $e) {
            $this->registrarAvisoFallo(
                self::TIPO_FALLO_DESCARGA,
                $tienda,
                'No se pudo descargar el feed. URL: ' . $urlDescarga . ' — ' . $e->getMessage()
            );
            throw $e;
        }
    }

    private function descomprimirGz(Tienda $tienda, string $origenGz, string $destinoCsv): void
    {
        try {
            if (!is_file($origenGz)) {
                throw new \RuntimeException('No existe el archivo .gz descargado.');
            }

            $gz = @gzopen($origenGz, 'rb');
            if ($gz === false) {
                throw new \RuntimeException('No se pudo abrir el archivo .gz para lectura.');
            }

            $out = @fopen($destinoCsv, 'wb');
            if ($out === false) {
                gzclose($gz);
                throw new \RuntimeException('No se pudo crear el archivo CSV descomprimido.');
            }

            while (!gzeof($gz)) {
                $chunk = gzread($gz, 1024 * 1024);
                if ($chunk === false) {
                    gzclose($gz);
                    fclose($out);
                    @unlink($destinoCsv);
                    throw new \RuntimeException('Error leyendo datos comprimidos.');
                }
                if ($chunk !== '') {
                    fwrite($out, $chunk);
                }
            }

            gzclose($gz);
            fclose($out);

            if (!is_file($destinoCsv) || filesize($destinoCsv) === 0) {
                throw new \RuntimeException('El CSV descomprimido está vacío.');
            }
        } catch (\Throwable $e) {
            $this->registrarAvisoFallo(
                self::TIPO_FALLO_DESCOMPRESION,
                $tienda,
                'No se pudo descomprimir el .gz — ' . $e->getMessage()
            );
            throw $e;
        }
    }

    /**
     * @return list<string>
     */
    private function dividirCsvSiNecesario(Tienda $tienda, string $archivoCsv, string $dirTrabajo): array
    {
        $tamano = filesize($archivoCsv);
        if ($tamano === false) {
            throw new \RuntimeException('No se pudo obtener el tamaño del CSV.');
        }

        if ($tamano <= self::UMBRAL_DIVIDIR_BYTES) {
            return [$archivoCsv];
        }

        try {
            $partes = $this->dividirCsvEnPartes($archivoCsv, $dirTrabajo);
            if ($partes === []) {
                throw new \RuntimeException('No se generó ninguna parte al dividir el CSV.');
            }

            return $partes;
        } catch (\Throwable $e) {
            $this->registrarAvisoFallo(
                self::TIPO_FALLO_DIVISION,
                $tienda,
                'CSV de ' . $this->formatearBytes($tamano) . '. No se pudo dividir — ' . $e->getMessage()
            );
            throw $e;
        }
    }

    /**
     * @return list<string>
     */
    private function dividirCsvEnPartes(string $archivoCsv, string $dirTrabajo): array
    {
        $in = fopen($archivoCsv, 'rb');
        if ($in === false) {
            throw new \RuntimeException('No se pudo abrir el CSV para dividirlo.');
        }

        $header = fgetcsv($in);
        if ($header === false || $header === [null]) {
            fclose($in);
            throw new \RuntimeException('El CSV no tiene cabecera válida.');
        }

        $partes = [];
        $indiceParte = 0;
        $rutaParte = $dirTrabajo . '/parte_' . $indiceParte . '.csv';
        $out = fopen($rutaParte, 'wb');
        if ($out === false) {
            fclose($in);
            throw new \RuntimeException('No se pudo crear la primera parte del CSV.');
        }

        fputcsv($out, $header);
        $partes[] = $rutaParte;
        $tamanoParte = $this->estimarBytesFilaCsv($header);

        while (($fila = fgetcsv($in)) !== false) {
            $bytesFila = $this->estimarBytesFilaCsv($fila);

            if ($tamanoParte + $bytesFila > self::TAMANO_PARTE_BYTES && $tamanoParte > $this->estimarBytesFilaCsv($header)) {
                fclose($out);
                $indiceParte++;
                $rutaParte = $dirTrabajo . '/parte_' . $indiceParte . '.csv';
                $out = fopen($rutaParte, 'wb');
                if ($out === false) {
                    fclose($in);
                    throw new \RuntimeException('No se pudo crear una parte del CSV.');
                }
                fputcsv($out, $header);
                $partes[] = $rutaParte;
                $tamanoParte = $this->estimarBytesFilaCsv($header);
            }

            fputcsv($out, $fila);
            $tamanoParte += $bytesFila;
        }

        fclose($in);
        fclose($out);

        return $partes;
    }

    /**
     * @param array<int, string|null> $fila
     */
    private function estimarBytesFilaCsv(array $fila): int
    {
        $buffer = fopen('php://temp', 'r+');
        if ($buffer === false) {
            return 256;
        }

        fputcsv($buffer, $fila);
        rewind($buffer);
        $contenido = stream_get_contents($buffer) ?: '';
        fclose($buffer);

        return max(1, strlen($contenido));
    }

    /**
     * @return array{procesadas: int, omitidas: int}
     */
    private function procesarArchivoCsv(Tienda $tienda, string $rutaCsv): array
    {
        $handle = fopen($rutaCsv, 'rb');
        if ($handle === false) {
            throw new \RuntimeException('No se pudo abrir el CSV para procesarlo.');
        }

        $header = fgetcsv($handle);
        if ($header === false || $header === [null]) {
            fclose($handle);
            throw new \RuntimeException('El CSV no tiene cabecera.');
        }

        try {
            $mapaColumnas = $this->construirMapaColumnas($tienda, $header);
        } catch (\Throwable $e) {
            fclose($handle);
            throw $e;
        }

        $procesadas = 0;
        $omitidas = 0;
        $lote = [];
        $erroresLote = 0;
        $ultimoError = null;

        while (($fila = fgetcsv($handle)) !== false) {
            if ($fila === [null] || $fila === []) {
                $omitidas++;
                continue;
            }

            try {
                $registro = $this->mapearFila($tienda, $fila, $mapaColumnas);
                if ($registro === null) {
                    $omitidas++;
                    continue;
                }
                $lote[] = $registro;

                if (count($lote) >= self::TAMANO_LOTE_UPSERT) {
                    $resultadoLote = $this->guardarLote($tienda, $lote);
                    $procesadas += $resultadoLote['procesadas'];
                    $erroresLote += $resultadoLote['errores'];
                    if ($resultadoLote['ultimo_error'] !== null) {
                        $ultimoError = $resultadoLote['ultimo_error'];
                    }
                    $lote = [];
                }
            } catch (\Throwable $e) {
                $omitidas++;
                $erroresLote++;
                $ultimoError = $e->getMessage();
            }
        }

        fclose($handle);

        if ($lote !== []) {
            $resultadoLote = $this->guardarLote($tienda, $lote);
            $procesadas += $resultadoLote['procesadas'];
            $erroresLote += $resultadoLote['errores'];
            if ($resultadoLote['ultimo_error'] !== null) {
                $ultimoError = $resultadoLote['ultimo_error'];
            }
        }

        if ($erroresLote > 0) {
            $this->registrarAvisoFallo(
                self::TIPO_FALLO_BASE_DATOS,
                $tienda,
                'Errores al insertar/actualizar filas: ' . $erroresLote . '. Último error: ' . ($ultimoError ?? 'desconocido')
            );
        }

        return [
            'procesadas' => $procesadas,
            'omitidas' => $omitidas,
        ];
    }

    /**
     * @param array<int, string|null> $header
     * @return array<string, int>
     */
    private function construirMapaColumnas(Tienda $tienda, array $header): array
    {
        $mapa = [];
        foreach ($header as $indice => $nombreColumna) {
            $clave = strtolower(trim((string) $nombreColumna));
            if ($clave !== '') {
                $mapa[$clave] = (int) $indice;
            }
        }

        $faltantes = [];
        foreach (self::COLUMNAS_REQUERIDAS as $columna) {
            if (!array_key_exists($columna, $mapa)) {
                $faltantes[] = $columna;
            }
        }

        if ($faltantes !== []) {
            $this->registrarAvisoFallo(
                self::TIPO_FALLO_COLUMNAS,
                $tienda,
                'Faltan columnas requeridas: ' . implode(', ', $faltantes)
            );
            throw new \RuntimeException('Columnas requeridas no encontradas: ' . implode(', ', $faltantes));
        }

        return $mapa;
    }

    /**
     * @param array<int, string|null> $fila
     * @param array<string, int> $mapaColumnas
     * @return array<string, mixed>|null
     */
    private function mapearFila(Tienda $tienda, array $fila, array $mapaColumnas): ?array
    {
        $urlOriginal = trim((string) ($fila[$mapaColumnas['merchant_deep_link']] ?? ''));
        if ($urlOriginal === '') {
            return null;
        }

        $url = $this->limpiarUrlDeTiendas->limpiar($urlOriginal);
        if ($url === '') {
            return null;
        }

        $precio = $this->parsearDecimal($fila[$mapaColumnas['search_price']] ?? null);
        if ($precio !== null && $precio > self::PRECIO_MAXIMO_CSV) {
            return null;
        }

        $envio = $this->parsearDecimal($fila[$mapaColumnas['delivery_cost']] ?? null, true);
        $stock = $this->parsearStock($fila[$mapaColumnas['in_stock']] ?? null);

        return [
            'tienda_id' => $tienda->id,
            'url' => $url,
            'url_lookup' => $this->consultarNeoCifrado->hashLookup($url),
            'precio' => $precio,
            'envio' => $envio,
            'stock' => $stock,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    private function parsearDecimal(mixed $valor, bool $permitirVacio = false): ?float
    {
        $texto = trim((string) ($valor ?? ''));
        if ($texto === '') {
            return $permitirVacio ? null : null;
        }

        $texto = str_replace(',', '.', $texto);
        if (!is_numeric($texto)) {
            return null;
        }

        return round((float) $texto, 2);
    }

    private function parsearStock(mixed $valor): int
    {
        $texto = trim((string) ($valor ?? ''));
        if ($texto === '' || !is_numeric($texto)) {
            return 0;
        }

        return ((float) $texto) > 0 ? 1 : 0;
    }

    /**
     * @param list<array<string, mixed>> $lote
     * @return array{procesadas: int, errores: int, ultimo_error: ?string}
     */
    private function guardarLote(Tienda $tienda, array $lote): array
    {
        if ($lote === []) {
            return ['procesadas' => 0, 'errores' => 0, 'ultimo_error' => null];
        }

        try {
            DB::table((new CsvOferta())->getTable())->upsert(
                $lote,
                ['tienda_id', 'url_lookup'],
                ['url', 'precio', 'envio', 'stock', 'updated_at']
            );

            return [
                'procesadas' => count($lote),
                'errores' => 0,
                'ultimo_error' => null,
            ];
        } catch (\Throwable $e) {
            $procesadas = 0;
            $errores = 0;
            $ultimoError = $e->getMessage();

            foreach ($lote as $registro) {
                try {
                    CsvOferta::updateOrCreate(
                        [
                            'tienda_id' => $registro['tienda_id'],
                            'url_lookup' => $registro['url_lookup'],
                        ],
                        [
                            'url' => $registro['url'],
                            'precio' => $registro['precio'],
                            'envio' => $registro['envio'],
                            'stock' => $registro['stock'],
                        ]
                    );
                    $procesadas++;
                } catch (\Throwable $filaError) {
                    $errores++;
                    $ultimoError = $filaError->getMessage();
                }
            }

            if ($errores > 0) {
                $this->registrarAvisoFallo(
                    self::TIPO_FALLO_BASE_DATOS,
                    $tienda,
                    'Errores al insertar/actualizar filas: ' . $errores . '. Último error: ' . $ultimoError
                );
            }

            return [
                'procesadas' => $procesadas,
                'errores' => $errores,
                'ultimo_error' => $ultimoError,
            ];
        }
    }

    public function registrarAvisoFallo(string $tipoFallo, Tienda $tienda, string $detalle): void
    {
        $prefijo = $this->prefijoAviso($tipoFallo, $tienda);
        $texto = $prefijo . ' — ' . now()->format('Y-m-d H:i:s') . ' — ' . mb_substr($detalle, 0, 900);

        $aviso = Aviso::query()
            ->where('avisoable_type', 'Interno')
            ->where('texto_aviso', 'like', $prefijo . '%')
            ->orderByDesc('id')
            ->first();

        if ($aviso) {
            $aviso->update([
                'texto_aviso' => $texto,
                'fecha_aviso' => now(),
            ]);

            return;
        }

        Aviso::create([
            'texto_aviso' => $texto,
            'fecha_aviso' => now(),
            'user_id' => $this->userIdParaAvisosInternos(),
            'avisoable_type' => 'Interno',
            'avisoable_id' => 0,
            'oculto' => false,
        ]);
    }

    private function prefijoAviso(string $tipoFallo, Tienda $tienda): string
    {
        return '[CSV-Awin ' . $tipoFallo . '] Tienda ' . $tienda->nombre . ' (id ' . $tienda->id . ')';
    }

    private function userIdParaAvisosInternos(): int
    {
        return (int) (User::orderBy('id')->value('id') ?? 1);
    }

    private function formatearBytes(int|float $bytes): string
    {
        if ($bytes >= 1024 * 1024 * 1024) {
            return round($bytes / (1024 * 1024 * 1024), 2) . ' GB';
        }
        if ($bytes >= 1024 * 1024) {
            return round($bytes / (1024 * 1024), 2) . ' MB';
        }

        return round($bytes / 1024, 2) . ' KB';
    }
}
