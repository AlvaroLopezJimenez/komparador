<?php

namespace App\Models;

use App\Services\ConsultarNeoCifrado;
use Illuminate\Database\Eloquent\Model;
use Throwable;

class Neoobjetivo extends Model
{
    /** Días sin revisar visitada para considerar un neoobjetivo pendiente (cron + API programa externo). */
    public const DIAS_SIN_REVISAR = 32;

    private const URL_ENCRYPTION_PREFIX = 'encv1:';
    private const URL_CIPHER = 'AES-256-CBC';

    public static function fechaLimiteVisitadaPendiente(): \Illuminate\Support\Carbon
    {
        return now()->subDays(self::DIAS_SIN_REVISAR);
    }

    /** Valor por defecto al crear neoobjetivo (queda pendiente en el siguiente ciclo). */
    public static function fechaVisitadaPorDefectoAlCrear(): \Illuminate\Support\Carbon
    {
        return self::fechaLimiteVisitadaPendiente();
    }

    /** Para pruebas manuales: un día más allá del umbral de revisión. */
    public static function fechaVisitadaPruebaManual(): \Illuminate\Support\Carbon
    {
        return now()->subDays(self::DIAS_SIN_REVISAR + 1);
    }

    protected $table = 'neoobjetivo';

    public $timestamps = true;

    protected $fillable = [
        'oferta_id',
        'producto_id',
        'categoria_id',
        'tienda_id',
        'url',
        'url_cipher',
        'url_lookup',
        'visitada',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'visitada' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Guarda la URL cifrada en BD usando SIGNED_URLS_SECRET.
     */
    public function setUrlAttribute($value): void
    {
        $value = is_string($value) ? trim($value) : $value;

        // No persistir columna legacy `url` (renombrada o retirada); solo v2.
        unset($this->attributes['url']);

        if ($value === null || $value === '') {
            $this->attributes['url_cipher'] = null;
            $this->attributes['url_lookup'] = null;
            return;
        }
        $payload = app(ConsultarNeoCifrado::class)->construirPayload((string) $value);
        $this->attributes['url_cipher'] = $payload['neo_cipher'];
        $this->attributes['url_lookup'] = $payload['neo_lookup'];
    }

    /**
     * Devuelve la URL descifrada para uso en formularios/código.
     */
    public function getUrlAttribute($value): ?string
    {
        $cipherV2 = (string) ($this->attributes['url_cipher'] ?? '');
        if ($cipherV2 !== '') {
            return app(ConsultarNeoCifrado::class)->descifrarGuardado($cipherV2);
        }

        return '';
    }

    private static function encryptUrl(string $plainText): string
    {
        if (self::isEncryptedValue($plainText)) {
            return $plainText;
        }

        $secret = (string) config('anti-scraping.neoobjetivo_url_secret', '');
        if ($secret === '') {
            return $plainText;
        }

        $key = hash('sha256', $secret, true);
        $ivLength = openssl_cipher_iv_length(self::URL_CIPHER);
        $iv = random_bytes($ivLength);

        $cipherText = openssl_encrypt($plainText, self::URL_CIPHER, $key, OPENSSL_RAW_DATA, $iv);
        if ($cipherText === false) {
            return $plainText;
        }

        return self::URL_ENCRYPTION_PREFIX . base64_encode($iv . $cipherText);
    }

    private static function decryptUrl(string $value): string
    {
        if (!self::isEncryptedValue($value)) {
            return $value;
        }

        $secret = (string) config('anti-scraping.neoobjetivo_url_secret', '');
        if ($secret === '') {
            return $value;
        }

        try {
            $payload = base64_decode(substr($value, strlen(self::URL_ENCRYPTION_PREFIX)), true);
            if ($payload === false) {
                return $value;
            }

            $ivLength = openssl_cipher_iv_length(self::URL_CIPHER);
            if (strlen($payload) <= $ivLength) {
                return $value;
            }

            $iv = substr($payload, 0, $ivLength);
            $cipherText = substr($payload, $ivLength);
            $key = hash('sha256', $secret, true);

            $plainText = openssl_decrypt($cipherText, self::URL_CIPHER, $key, OPENSSL_RAW_DATA, $iv);
            return $plainText === false ? $value : $plainText;
        } catch (Throwable $e) {
            return $value;
        }
    }

    private static function isEncryptedValue(string $value): bool
    {
        return str_starts_with($value, self::URL_ENCRYPTION_PREFIX);
    }

    /**
     * Referencia a ofertas_producto.
     */
    public function oferta()
    {
        return $this->belongsTo(OfertaProducto::class, 'oferta_id');
    }

    /**
     * Referencia a productos.
     */
    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    /**
     * Referencia a categorias.
     */
    public function categoria()
    {
        return $this->belongsTo(Categoria::class);
    }

    /**
     * Referencia a tiendas.
     */
    public function tienda()
    {
        return $this->belongsTo(Tienda::class);
    }
}
