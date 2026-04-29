<?php

namespace App\Models;

use App\Services\ConsultarNeoCifrado;
use Illuminate\Database\Eloquent\Model;
use Throwable;

class Neo extends Model
{
    private const NEO_ENCRYPTION_PREFIX = 'encv1:';
    private const NEO_CIPHER = 'AES-256-CBC';

    protected $table = 'neo';

    public $timestamps = true;

    protected $fillable = [
        'oferta_id',
        'producto_id',
        'categoria_id',
        'tienda_id',
        'url',
        'url_cipher',
        'url_lookup',
        'neo',
        'neo_cipher',
        'neo_lookup',
        'aniadida',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Guarda "neo" cifrado en BD.
     */
    public function setNeoAttribute($value): void
    {
        $value = is_string($value) ? trim($value) : $value;

        // No persistir columna legacy `neo` (renombrada o retirada); solo v2.
        unset($this->attributes['neo']);

        if ($value === null || $value === '') {
            $this->attributes['neo_cipher'] = null;
            $this->attributes['neo_lookup'] = null;
            return;
        }
        $payload = app(ConsultarNeoCifrado::class)->construirPayload((string) $value);
        $this->attributes['neo_cipher'] = $payload['neo_cipher'];
        $this->attributes['neo_lookup'] = $payload['neo_lookup'];
    }

    /**
     * Devuelve "neo" descifrado.
     */
    public function getNeoAttribute($value): ?string
    {
        $cipherV2 = (string) ($this->attributes['neo_cipher'] ?? '');
        if ($cipherV2 !== '') {
            return app(ConsultarNeoCifrado::class)->descifrarGuardado($cipherV2);
        }

        return '';
    }

    /**
     * Guarda "url" en columnas v2 (url_cipher/url_lookup).
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
     * Devuelve "url" desde v2; fallback legacy si no hay columnas nuevas.
     */
    public function getUrlAttribute($value): ?string
    {
        $cipherV2 = (string) ($this->attributes['url_cipher'] ?? '');
        if ($cipherV2 !== '') {
            return app(ConsultarNeoCifrado::class)->descifrarGuardado($cipherV2);
        }

        return '';
    }

    /**
     * Valor cifrado para búsquedas exactas en BD (misma entrada => misma salida).
     */
    public static function encryptedNeoForLookup(string $plainText): string
    {
        return self::neoLookupHashV2($plainText);
    }

    /**
     * Igual que encryptedNeoForLookup pero usando un secreto explícito (p. ej. contraseña del formulario).
     */
    public static function encryptedNeoForLookupWithSecret(string $plainText, string $secret): string
    {
        $plainText = trim($plainText);
        if ($plainText === '') {
            return '';
        }
        if ($secret === '') {
            return '';
        }
        return hash_hmac('sha256', $plainText, $secret);
    }

    /**
     * Descifra el valor crudo de BD con el mismo algoritmo que el accessor, usando un secreto explícito.
     */
    public static function decryptNeoFromStoredWithSecret(string $storedValue, string $secret): string
    {
        return self::decryptNeoWithSecret($storedValue, $secret);
    }

    /**
     * Nuevo esquema: cifrado de almacenamiento no determinista.
     */
    public static function encryptedNeoForStorageV2(string $plainText): string
    {
        return app(ConsultarNeoCifrado::class)->cifrarParaGuardar($plainText);
    }

    /**
     * Nuevo esquema: lookup determinista para búsquedas exactas.
     */
    public static function neoLookupHashV2(string $plainText): string
    {
        return app(ConsultarNeoCifrado::class)->hashLookup($plainText);
    }

    private static function encryptNeo(string $plainText): string
    {
        return self::encryptNeoWithSecret($plainText, (string) config('anti-scraping.neoobjetivo_url_secret', ''));
    }

    private static function encryptNeoWithSecret(string $plainText, string $secret): string
    {
        if (self::isEncryptedValue($plainText)) {
            return $plainText;
        }

        if ($secret === '') {
            return $plainText;
        }

        $key = hash('sha256', $secret, true);
        $ivLength = openssl_cipher_iv_length(self::NEO_CIPHER);
        // IV determinista para permitir búsquedas por igualdad en columna neo.
        $iv = substr(hash_hmac('sha256', $plainText, $key, true), 0, $ivLength);

        $cipherText = openssl_encrypt($plainText, self::NEO_CIPHER, $key, OPENSSL_RAW_DATA, $iv);
        if ($cipherText === false) {
            return $plainText;
        }

        return self::NEO_ENCRYPTION_PREFIX . base64_encode($iv . $cipherText);
    }

    private static function decryptNeo(string $value): string
    {
        return self::decryptNeoWithSecret($value, (string) config('anti-scraping.neoobjetivo_url_secret', ''));
    }

    private static function decryptNeoWithSecret(string $value, string $secret): string
    {
        if (!self::isEncryptedValue($value)) {
            return $value;
        }

        if ($secret === '') {
            return $value;
        }

        try {
            $payload = base64_decode(substr($value, strlen(self::NEO_ENCRYPTION_PREFIX)), true);
            if ($payload === false) {
                return $value;
            }

            $ivLength = openssl_cipher_iv_length(self::NEO_CIPHER);
            if (strlen($payload) <= $ivLength) {
                return $value;
            }

            $iv = substr($payload, 0, $ivLength);
            $cipherText = substr($payload, $ivLength);
            $key = hash('sha256', $secret, true);

            $plainText = openssl_decrypt($cipherText, self::NEO_CIPHER, $key, OPENSSL_RAW_DATA, $iv);
            return $plainText === false ? $value : $plainText;
        } catch (Throwable $e) {
            return $value;
        }
    }

    private static function isEncryptedValue(string $value): bool
    {
        return str_starts_with($value, self::NEO_ENCRYPTION_PREFIX);
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
