<?php

namespace App\Services;

use RuntimeException;
use Throwable;

class ConsultarNeoCifrado
{
    private const CIPHER = 'aes-256-gcm';
    private const PREFIX = 'neov2:';

    /**
     * Normaliza la URL para asegurar consistencia en lookup/cifrado.
     */
    public function normalizarUrl(string $url): string
    {
        return trim($url);
    }

    /**
     * Cifra la URL para almacenamiento (cifrado no determinista).
     */
    public function cifrarParaGuardar(string $urlPlano): string
    {
        $urlPlano = $this->normalizarUrl($urlPlano);

        if ($urlPlano === '') {
            return '';
        }

        $encryptKey = (string) config('anti-scraping.neo_encrypt_key', '');
        if ($encryptKey === '') {
            throw new RuntimeException('Falta anti-scraping.neo_encrypt_key para cifrar neo v2.');
        }

        $key = hash('sha256', $encryptKey, true);
        $ivLength = openssl_cipher_iv_length(self::CIPHER);
        $iv = random_bytes($ivLength);
        $tag = '';

        $cipherText = openssl_encrypt(
            $urlPlano,
            self::CIPHER,
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        if ($cipherText === false || $tag === '') {
            throw new RuntimeException('No se pudo cifrar neo v2.');
        }

        return self::PREFIX . base64_encode($iv . $tag . $cipherText);
    }

    /**
     * Descifra el valor almacenado.
     */
    public function descifrarGuardado(?string $valorCifrado): string
    {
        $valorCifrado = (string) ($valorCifrado ?? '');
        if ($valorCifrado === '') {
            return '';
        }

        if (!str_starts_with($valorCifrado, self::PREFIX)) {
            return $valorCifrado;
        }

        $encryptKey = (string) config('anti-scraping.neo_encrypt_key', '');
        if ($encryptKey === '') {
            throw new RuntimeException('Falta anti-scraping.neo_encrypt_key para descifrar neo v2.');
        }

        try {
            $payload = base64_decode(substr($valorCifrado, strlen(self::PREFIX)), true);
            if ($payload === false) {
                return $valorCifrado;
            }

            $ivLength = openssl_cipher_iv_length(self::CIPHER);
            $tagLength = 16;
            if (strlen($payload) <= ($ivLength + $tagLength)) {
                return $valorCifrado;
            }

            $iv = substr($payload, 0, $ivLength);
            $tag = substr($payload, $ivLength, $tagLength);
            $cipherText = substr($payload, $ivLength + $tagLength);
            $key = hash('sha256', $encryptKey, true);

            $plainText = openssl_decrypt(
                $cipherText,
                self::CIPHER,
                $key,
                OPENSSL_RAW_DATA,
                $iv,
                $tag
            );

            return $plainText === false ? $valorCifrado : $plainText;
        } catch (Throwable $e) {
            return $valorCifrado;
        }
    }

    /**
     * Hash determinista para búsquedas exactas.
     */
    public function hashLookup(string $urlPlano): string
    {
        $urlPlano = $this->normalizarUrl($urlPlano);
        if ($urlPlano === '') {
            return '';
        }

        $lookupKey = (string) config('anti-scraping.neo_lookup_key', '');
        if ($lookupKey === '') {
            throw new RuntimeException('Falta anti-scraping.neo_lookup_key para calcular lookup de neo.');
        }

        return hash_hmac('sha256', $urlPlano, $lookupKey);
    }

    /**
     * Construye los dos valores necesarios al guardar.
     *
     * @return array{neo_cipher: string, neo_lookup: string}
     */
    public function construirPayload(string $urlPlano): array
    {
        return [
            'neo_cipher' => $this->cifrarParaGuardar($urlPlano),
            'neo_lookup' => $this->hashLookup($urlPlano),
        ];
    }
}
