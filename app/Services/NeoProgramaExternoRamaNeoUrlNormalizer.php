<?php

namespace App\Services;

/**
 * Normalización de hrefs leadout / relocator de la rama Neo para el programa externo.
 * Lógica equivalente a la del cron pero independiente (no se importa el controlador del cron).
 */
class NeoProgramaExternoRamaNeoUrlNormalizer
{
    public static function subcadenaMarcaRamaNeoEnUrl(): string
    {
        return (string) base64_decode('aWRlYWxv', true);
    }

    public static function esRamaNeoObjetivoUrl(string $url): bool
    {
        $url = trim($url);

        return $url !== '' && stripos($url, self::subcadenaMarcaRamaNeoEnUrl()) !== false;
    }

    private static function ramaNeoHostWww(): string
    {
        return (string) base64_decode('d3d3LmlkZWFsby5lcw==', true);
    }

    private static function ramaNeoOrigenHttps(): string
    {
        return (string) base64_decode('aHR0cHM6Ly93d3cuaWRlYWxvLmVz', true);
    }

    /**
     * URL absoluta con solo offerKey y type en query (misma regla que el cron para leadouts).
     */
    public static function limpiarHrefRelocatorLeadout(string $href): string
    {
        $href = trim($href);
        if ($href === '') {
            return '';
        }
        if (!str_starts_with($href, 'http')) {
            $base = self::ramaNeoOrigenHttps();
            $href = $base . (str_starts_with($href, '/') ? '' : '/') . $href;
        }
        $parts = parse_url($href);
        if ($parts === false) {
            return $href;
        }
        $scheme = $parts['scheme'] ?? 'https';
        $host = $parts['host'] ?? self::ramaNeoHostWww();
        $path = $parts['path'] ?? '/';
        $query = $parts['query'] ?? '';
        parse_str($query, $params);
        $paramsLimpios = array_intersect_key($params, array_flip(['offerKey', 'type']));
        $queryLimpia = http_build_query($paramsLimpios, '', '&', \PHP_QUERY_RFC3986);
        $base = $scheme . '://' . $host . $path;

        return $queryLimpia !== '' ? $base . '?' . $queryLimpia : $base;
    }
}
