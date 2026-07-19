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
    public const TIPO_FALLO_LIMPIEZA = 'limpieza';

    public const HORAS_ANTIGUEDAD_LIMPIEZA_DEFECTO = 2;

    private const UMBRAL_DIVIDIR_BYTES = 1000 * 1024 * 1024;
    private const TAMANO_PARTE_BYTES = 45 * 1024 * 1024;
    private const TAMANO_LOTE_UPSERT = 500;
    private const TIMEOUT_DESCARGA_SEGUNDOS = 900;
    /** Cada cuántos lotes guardados se escribe progreso en el log de ejecución */
    private const INTERVALO_LOTES_LOG_PROGRESO = 20;
    /** Máximo admitido por csv_ofertas.precio (decimal 8,2) */
    private const PRECIO_MAXIMO_CSV = 999999.99;

    /** Longitud máxima de columnas ean/isbn/upc/mpn/gtin en csv_ofertas */
    private const LONGITUD_MAXIMA_CODIGO_CSV = 255;

    /** @var list<string> */
    private const COLUMNAS_REQUERIDAS = [
        'merchant_deep_link',
        'search_price',
        'delivery_cost',
        'in_stock',
    ];

    /**
     * Columna en csv_ofertas => nombre de columna en el feed CSV Awin (cabecera en minúsculas).
     *
     * @var array<string, string>
     */
    private const MAPA_COLUMNAS_CODIGOS_CSV = [
        'ean' => 'ean',
        'isbn' => 'isbn',
        'upc' => 'upc',
        'mpn' => 'mpn',
        'gtin' => 'product_gtin',
    ];

    public function __construct(
        private readonly LimpiarUrlDeTiendas $limpiarUrlDeTiendas,
        private readonly ConsultarNeoCifrado $consultarNeoCifrado,
    ) {}

    /**
     * @param  (callable(string, array<string, mixed>): void)|null  $registrarPaso
     * @return array<string, mixed>
     */
    public function procesarTienda(Tienda $tienda, ?callable $registrarPaso = null): array
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

        $contextoTienda = [
            'tienda_id' => $tienda->id,
            'tienda' => $tienda->nombre,
        ];

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
            $contextoUrl = array_merge($contextoTienda, [
                'url_indice' => $indiceUrl,
                'url' => $urlDescarga,
            ]);

            try {
                $archivoGz = $dirTrabajo . '/feed.csv.gz';

                $this->registrarPaso($registrarPaso, 'descarga_inicio', array_merge($contextoUrl, [
                    'detalle' => 'Iniciando descarga del feed .gz',
                ]));
                $this->descargarArchivo($tienda, $urlDescarga, $archivoGz);
                $tamanoGz = (int) (is_file($archivoGz) ? filesize($archivoGz) : 0);
                $this->registrarPaso($registrarPaso, 'descarga_fin', array_merge($contextoUrl, [
                    'detalle' => 'Descarga del .gz completada',
                    'tamano_gz' => $tamanoGz,
                    'tamano_gz_legible' => $this->formatearBytes($tamanoGz),
                ]));

                $archivoCsv = $dirTrabajo . '/feed.csv';
                $this->registrarPaso($registrarPaso, 'descompresion_inicio', array_merge($contextoUrl, [
                    'detalle' => 'Descomprimiendo .gz a CSV',
                    'tamano_gz' => $tamanoGz,
                    'tamano_gz_legible' => $this->formatearBytes($tamanoGz),
                ]));
                $this->descomprimirGz($tienda, $archivoGz, $archivoCsv);
                $tamanoCsv = (int) (is_file($archivoCsv) ? filesize($archivoCsv) : 0);
                $this->registrarPaso($registrarPaso, 'descompresion_fin', array_merge($contextoUrl, [
                    'detalle' => 'Descompresión completada',
                    'tamano_gz' => $tamanoGz,
                    'tamano_gz_legible' => $this->formatearBytes($tamanoGz),
                    'tamano_csv' => $tamanoCsv,
                    'tamano_csv_legible' => $this->formatearBytes($tamanoCsv),
                ]));

                $partes = $this->dividirCsvSiNecesario($tienda, $archivoCsv, $dirTrabajo, $registrarPaso, $contextoUrl);
                $totalPartes = count($partes);

                foreach ($partes as $indiceParte => $parte) {
                    $tamanoParte = (int) (is_file($parte) ? filesize($parte) : 0);
                    $this->registrarPaso($registrarPaso, 'parte_inicio', array_merge($contextoUrl, [
                        'detalle' => 'Procesando parte ' . ($indiceParte + 1) . ' de ' . $totalPartes,
                        'parte' => $indiceParte + 1,
                        'total_partes' => $totalPartes,
                        'archivo' => basename($parte),
                        'tamano_parte' => $tamanoParte,
                        'tamano_parte_legible' => $this->formatearBytes($tamanoParte),
                    ]));

                    $resultadoParte = $this->procesarArchivoCsv(
                        $tienda,
                        $parte,
                        $registrarPaso,
                        $contextoUrl,
                        $indiceParte + 1,
                        $totalPartes,
                    );
                    $resumen['filas_insertadas_o_actualizadas'] += $resultadoParte['procesadas'];
                    $resumen['filas_omitidas'] += $resultadoParte['omitidas'];

                    $this->registrarPaso($registrarPaso, 'parte_fin', array_merge($contextoUrl, [
                        'detalle' => 'Parte ' . ($indiceParte + 1) . ' de ' . $totalPartes . ' guardada en BD',
                        'parte' => $indiceParte + 1,
                        'total_partes' => $totalPartes,
                        'archivo' => basename($parte),
                        'filas_guardadas' => $resultadoParte['procesadas'],
                        'filas_omitidas' => $resultadoParte['omitidas'],
                    ]));
                }

                $resumen['urls_procesadas']++;
                $this->registrarPaso($registrarPaso, 'url_completada', array_merge($contextoUrl, [
                    'detalle' => 'URL de feed procesada correctamente',
                    'filas_guardadas' => $resumen['filas_insertadas_o_actualizadas'],
                    'filas_omitidas' => $resumen['filas_omitidas'],
                ]));
            } catch (\Throwable $e) {
                $mensaje = $e->getMessage();
                $resumen['errores'][] = $mensaje;
                $this->registrarPaso($registrarPaso, 'error_tienda', array_merge($contextoUrl, [
                    'detalle' => 'Error procesando URL del feed',
                    'error' => $mensaje,
                ]));
                Log::error('DescargaCsvAwinTiendaService: error procesando tienda', [
                    'tienda_id' => $tienda->id,
                    'url' => $urlDescarga,
                    'error' => $mensaje,
                ]);
            } finally {
                $this->limpiarDirectorio($dirTrabajo, $tienda);
            }
        }

        $this->registrarPaso($registrarPaso, 'tienda_completada', array_merge($contextoTienda, [
            'detalle' => empty($resumen['errores']) ? 'Tienda procesada' : 'Tienda procesada con errores',
            'urls_procesadas' => $resumen['urls_procesadas'],
            'filas_guardadas' => $resumen['filas_insertadas_o_actualizadas'],
            'filas_omitidas' => $resumen['filas_omitidas'],
            'errores' => count($resumen['errores']),
        ]));

        return $resumen;
    }

    /**
     * @param  (callable(string, array<string, mixed>): void)|null  $registrarPaso
     * @param  array<string, mixed>  $contextoBase
     */
    private function registrarPaso(?callable $registrarPaso, string $paso, array $contextoBase = []): void
    {
        if ($registrarPaso === null) {
            return;
        }

        try {
            $registrarPaso($paso, $contextoBase);
        } catch (\Throwable $e) {
            Log::warning('DescargaCsvAwinTiendaService: error registrando paso de ejecución', [
                'paso' => $paso,
                'error' => $e->getMessage(),
            ]);
        }
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

    /**
     * Elimina directorios temporales en csv-awin con más de X horas sin modificación.
     *
     * @return array{eliminados: int, errores: int, bytes_liberados: int}
     */
    public function limpiarDirectoriosCsvAwinAntiguos(int $horas = self::HORAS_ANTIGUEDAD_LIMPIEZA_DEFECTO): array
    {
        $base = storage_path('app/csv-awin');
        $umbral = now()->subHours($horas)->getTimestamp();
        $eliminados = 0;
        $errores = 0;
        $bytesLiberados = 0;

        if (!is_dir($base)) {
            return [
                'eliminados' => 0,
                'errores' => 0,
                'bytes_liberados' => 0,
            ];
        }

        foreach (glob($base . '/tienda_*', GLOB_ONLYDIR) ?: [] as $dir) {
            $mtime = @filemtime($dir);
            if ($mtime === false || $mtime > $umbral) {
                continue;
            }

            $tamano = $this->calcularTamanoDirectorio($dir);
            if ($this->eliminarDirectorioRecursivo($dir)) {
                $eliminados++;
                $bytesLiberados += $tamano;
                continue;
            }

            $errores++;
            $this->registrarAvisoFallo(
                self::TIPO_FALLO_LIMPIEZA,
                null,
                'No se pudo eliminar directorio temporal antiguo (' . $horas . 'h+): ' . $dir
            );
        }

        return [
            'eliminados' => $eliminados,
            'errores' => $errores,
            'bytes_liberados' => $bytesLiberados,
        ];
    }

    private function limpiarDirectorio(string $dir, ?Tienda $tienda = null): void
    {
        if (!is_dir($dir)) {
            return;
        }

        if ($this->eliminarDirectorioRecursivo($dir)) {
            return;
        }

        $this->registrarAvisoFallo(
            self::TIPO_FALLO_LIMPIEZA,
            $tienda,
            'No se pudo eliminar el directorio temporal tras procesar: ' . $dir
        );
    }

    private function eliminarDirectorioRecursivo(string $dir): bool
    {
        if (!is_dir($dir)) {
            return true;
        }

        $ok = true;

        foreach (glob($dir . '/*') ?: [] as $ruta) {
            if (is_file($ruta) || is_link($ruta)) {
                if (!@unlink($ruta)) {
                    $ok = false;
                }
                continue;
            }

            if (is_dir($ruta) && !$this->eliminarDirectorioRecursivo($ruta)) {
                $ok = false;
            }
        }

        if (!@rmdir($dir)) {
            $ok = false;
        }

        return $ok && !is_dir($dir);
    }

    private function calcularTamanoDirectorio(string $dir): int
    {
        if (!is_dir($dir)) {
            return 0;
        }

        $total = 0;
        foreach (glob($dir . '/*') ?: [] as $ruta) {
            if (is_file($ruta)) {
                $total += (int) (@filesize($ruta) ?: 0);
                continue;
            }
            if (is_dir($ruta)) {
                $total += $this->calcularTamanoDirectorio($ruta);
            }
        }

        return $total;
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
     * @param  (callable(string, array<string, mixed>): void)|null  $registrarPaso
     * @param  array<string, mixed>  $contextoUrl
     * @return list<string>
     */
    private function dividirCsvSiNecesario(
        Tienda $tienda,
        string $archivoCsv,
        string $dirTrabajo,
        ?callable $registrarPaso = null,
        array $contextoUrl = [],
    ): array {
        $tamano = filesize($archivoCsv);
        if ($tamano === false) {
            throw new \RuntimeException('No se pudo obtener el tamaño del CSV.');
        }

        if ($tamano <= self::UMBRAL_DIVIDIR_BYTES) {
            $this->registrarPaso($registrarPaso, 'division_csv', array_merge($contextoUrl, [
                'detalle' => 'CSV sin dividir (por debajo del umbral de ' . $this->formatearBytes(self::UMBRAL_DIVIDIR_BYTES) . ')',
                'dividido' => false,
                'total_partes' => 1,
                'tamano_csv' => $tamano,
                'tamano_csv_legible' => $this->formatearBytes($tamano),
            ]));

            return [$archivoCsv];
        }

        try {
            $this->registrarPaso($registrarPaso, 'division_inicio', array_merge($contextoUrl, [
                'detalle' => 'Dividiendo CSV grande en partes',
                'tamano_csv' => $tamano,
                'tamano_csv_legible' => $this->formatearBytes($tamano),
                'umbral_division' => self::UMBRAL_DIVIDIR_BYTES,
                'umbral_division_legible' => $this->formatearBytes(self::UMBRAL_DIVIDIR_BYTES),
                'tamano_parte_objetivo' => self::TAMANO_PARTE_BYTES,
                'tamano_parte_objetivo_legible' => $this->formatearBytes(self::TAMANO_PARTE_BYTES),
            ]));

            $partes = $this->dividirCsvEnPartes($archivoCsv, $dirTrabajo);
            if ($partes === []) {
                throw new \RuntimeException('No se generó ninguna parte al dividir el CSV.');
            }

            $infoPartes = [];
            foreach ($partes as $indice => $rutaParte) {
                $bytesParte = (int) (is_file($rutaParte) ? filesize($rutaParte) : 0);
                $infoPartes[] = [
                    'parte' => $indice + 1,
                    'archivo' => basename($rutaParte),
                    'bytes' => $bytesParte,
                    'legible' => $this->formatearBytes($bytesParte),
                ];
            }

            $this->registrarPaso($registrarPaso, 'division_csv', array_merge($contextoUrl, [
                'detalle' => 'CSV dividido en ' . count($partes) . ' partes',
                'dividido' => true,
                'total_partes' => count($partes),
                'tamano_csv' => $tamano,
                'tamano_csv_legible' => $this->formatearBytes($tamano),
                'partes' => $infoPartes,
            ]));

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
        
        $bytesHeader = $this->estimarBytesFilaCsv($header);
        $tamanoParte = $bytesHeader;

        while (($fila = fgetcsv($in)) !== false) {
            $bytesFila = $this->estimarBytesFilaCsv($fila);

            if ($tamanoParte + $bytesFila > self::TAMANO_PARTE_BYTES && $tamanoParte > $bytesHeader) {
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
                $tamanoParte = $bytesHeader;
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
        $len = 0;
        foreach ($fila as $val) {
            if ($val !== null) {
                $vStr = (string) $val;
                $len += strlen($vStr);
                if (strpbrk($vStr, ",\"\r\n") !== false) {
                    $len += 2 + substr_count($vStr, '"');
                }
            }
        }
        return $len + count($fila);
    }

    /**
     * @param  (callable(string, array<string, mixed>): void)|null  $registrarPaso
     * @param  array<string, mixed>  $contextoUrl
     * @return array{procesadas: int, omitidas: int}
     */
    private function procesarArchivoCsv(
        Tienda $tienda,
        string $rutaCsv,
        ?callable $registrarPaso = null,
        array $contextoUrl = [],
        ?int $numeroParte = null,
        ?int $totalPartes = null,
    ): array {
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

        $tieneColumnaNombre = array_key_exists('product_name', $mapaColumnas);

        $procesadas = 0;
        $omitidas = 0;
        $lote = [];
        $erroresLote = 0;
        $ultimoError = null;
        $lotesGuardados = 0;

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
                    $resultadoLote = $this->guardarLote($tienda, $lote, $tieneColumnaNombre);
                    $procesadas += $resultadoLote['procesadas'];
                    $erroresLote += $resultadoLote['errores'];
                    if ($resultadoLote['ultimo_error'] !== null) {
                        $ultimoError = $resultadoLote['ultimo_error'];
                    }
                    $lote = [];
                    $lotesGuardados++;

                    if (
                        $registrarPaso !== null
                        && $lotesGuardados % self::INTERVALO_LOTES_LOG_PROGRESO === 0
                    ) {
                        $this->registrarPaso($registrarPaso, 'parte_guardado_progreso', array_merge($contextoUrl, [
                            'detalle' => 'Guardando parte en BD (progreso)',
                            'parte' => $numeroParte,
                            'total_partes' => $totalPartes,
                            'archivo' => basename($rutaCsv),
                            'lotes_guardados' => $lotesGuardados,
                            'filas_guardadas' => $procesadas,
                            'filas_omitidas' => $omitidas,
                            'errores_lote' => $erroresLote,
                        ]));
                    }
                }
            } catch (\Throwable $e) {
                $omitidas++;
                $erroresLote++;
                $ultimoError = $e->getMessage();
            }
        }

        fclose($handle);

        if ($lote !== []) {
            $resultadoLote = $this->guardarLote($tienda, $lote, $tieneColumnaNombre);
            $procesadas += $resultadoLote['procesadas'];
            $erroresLote += $resultadoLote['errores'];
            if ($resultadoLote['ultimo_error'] !== null) {
                $ultimoError = $resultadoLote['ultimo_error'];
            }
            $lotesGuardados++;
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
        $nombre = $this->parsearNombreProducto($fila, $mapaColumnas);

        return array_merge([
            'tienda_id' => $tienda->id,
            'url' => $url,
            'url_lookup' => $this->consultarNeoCifrado->hashLookup($url),
            'nombre' => $nombre,
            'precio' => $precio,
            'envio' => $envio,
            'stock' => $stock,
            'aniadida_neo' => 'no',
            'created_at' => now(),
            'updated_at' => now(),
        ], $this->mapearCodigosIdentificadorDesdeFilaCsv($fila, $mapaColumnas));
    }

    /**
     * @param  array<int, string|null>  $fila
     * @param  array<string, int>  $mapaColumnas
     * @return array{ean: ?string, isbn: ?string, upc: ?string, mpn: ?string, gtin: ?string}
     */
    private function mapearCodigosIdentificadorDesdeFilaCsv(array $fila, array $mapaColumnas): array
    {
        $codigos = [];

        foreach (self::MAPA_COLUMNAS_CODIGOS_CSV as $columnaBd => $columnaCsv) {
            $codigos[$columnaBd] = $this->parsearCodigoIdentificadorCsv(
                $fila,
                $mapaColumnas,
                $columnaCsv
            );
        }

        return $codigos;
    }

    /**
     * @param array<int, string|null> $fila
     * @param array<string, int> $mapaColumnas
     */
    private function parsearNombreProducto(array $fila, array $mapaColumnas): ?string
    {
        if (!array_key_exists('product_name', $mapaColumnas)) {
            return null;
        }

        $nombre = trim((string) ($fila[$mapaColumnas['product_name']] ?? ''));

        return $nombre !== '' ? $nombre : null;
    }

    /**
     * Valor tal como viene del CSV (solo trim). Sin quitar caracteres ni normalizar dígitos:
     * algunas tiendas envían varios códigos en un mismo campo.
     *
     * @param  array<int, string|null>  $fila
     * @param  array<string, int>  $mapaColumnas
     */
    private function parsearCodigoIdentificadorCsv(
        array $fila,
        array $mapaColumnas,
        string $columnaCsv,
    ): ?string {
        if (!array_key_exists($columnaCsv, $mapaColumnas)) {
            return null;
        }

        $valor = trim((string) ($fila[$mapaColumnas[$columnaCsv]] ?? ''));
        if ($valor === '' || $valor === '0') {
            return null;
        }

        if (strlen($valor) > self::LONGITUD_MAXIMA_CODIGO_CSV) {
            $valor = substr($valor, 0, self::LONGITUD_MAXIMA_CODIGO_CSV);
        }

        return $valor;
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
    private function guardarLote(Tienda $tienda, array $lote, bool $actualizarNombre = false): array
    {
        if ($lote === []) {
            return ['procesadas' => 0, 'errores' => 0, 'ultimo_error' => null];
        }

        $columnasActualizar = ['url', 'precio', 'envio', 'stock', 'ean', 'isbn', 'upc', 'mpn', 'gtin', 'updated_at'];
        if ($actualizarNombre) {
            $columnasActualizar[] = 'nombre';
        }

        try {
            DB::table((new CsvOferta())->getTable())->upsert(
                $lote,
                ['tienda_id', 'url_lookup'],
                $columnasActualizar
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
                    $datos = [
                        'url' => $registro['url'],
                        'precio' => $registro['precio'],
                        'envio' => $registro['envio'],
                        'stock' => $registro['stock'],
                        'ean' => $registro['ean'] ?? null,
                        'isbn' => $registro['isbn'] ?? null,
                        'upc' => $registro['upc'] ?? null,
                        'mpn' => $registro['mpn'] ?? null,
                        'gtin' => $registro['gtin'] ?? null,
                    ];
                    if ($actualizarNombre) {
                        $datos['nombre'] = $registro['nombre'] ?? null;
                    }

                    CsvOferta::updateOrCreate(
                        [
                            'tienda_id' => $registro['tienda_id'],
                            'url_lookup' => $registro['url_lookup'],
                        ],
                        $datos
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

    public function registrarAvisoFallo(string $tipoFallo, ?Tienda $tienda, string $detalle): void
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

    private function prefijoAviso(string $tipoFallo, ?Tienda $tienda): string
    {
        if ($tienda === null) {
            return '[CSV-Awin ' . $tipoFallo . ']';
        }

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
