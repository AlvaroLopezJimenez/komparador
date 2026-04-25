<?php

namespace App\Models;

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
        'neo',
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

        if ($value === null || $value === '') {
            $this->attributes['neo'] = $value;
            return;
        }

        $this->attributes['neo'] = self::encryptNeo((string) $value);
    }

    /**
     * Devuelve "neo" descifrado.
     */
    public function getNeoAttribute($value): ?string
    {
        if (!is_string($value) || $value === '') {
            return $value;
        }

        return self::decryptNeo($value);
    }

    /**
     * Valor cifrado para búsquedas exactas en BD (misma entrada => misma salida).
     */
    public static function encryptedNeoForLookup(string $plainText): string
    {
        return self::encryptNeoWithSecret($plainText, (string) config('anti-scraping.neoobjetivo_url_secret', ''));
    }

    /**
     * Igual que encryptedNeoForLookup pero usando un secreto explícito (p. ej. contraseña del formulario).
     */
    public static function encryptedNeoForLookupWithSecret(string $plainText, string $secret): string
    {
        return self::encryptNeoWithSecret($plainText, $secret);
    }

    /**
     * Descifra el valor crudo de BD con el mismo algoritmo que el accessor, usando un secreto explícito.
     */
    public static function decryptNeoFromStoredWithSecret(string $storedValue, string $secret): string
    {
        return self::decryptNeoWithSecret($storedValue, $secret);
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
