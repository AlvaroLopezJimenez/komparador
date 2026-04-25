<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Validación de URL para ofertas: igual que Laravel url, pero admite el carácter | en el path
 * (p. ej. Appinformatica). La URL se guarda tal cual; Str::isUrl solo se usa para comprobar.
 */
final class UrlOfertaValidacion
{
    public static function pasa(mixed $value): bool
    {
        if (! is_string($value)) {
            return false;
        }
        $value = trim($value);
        if ($value === '') {
            return false;
        }
        if (! preg_match('/^https?:\/\//i', $value)) {
            return false;
        }
        if (Str::isUrl($value)) {
            return true;
        }

        return Str::isUrl(str_replace('|', '%7C', $value));
    }

    /**
     * Reglas para $request->validate(['url' => ...]).
     *
     * @return array<int, string|\Closure>
     */
    public static function rules(): array
    {
        return [
            'required',
            'string',
            'max:255',
            function (string $attribute, mixed $value, \Closure $fail): void {
                if (! self::pasa($value)) {
                    $fail('La URL no es válida (se espera http o https).');
                }
            },
        ];
    }
}
