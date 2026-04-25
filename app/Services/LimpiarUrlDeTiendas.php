<?php

namespace App\Services;

class LimpiarUrlDeTiendas
{
    /**
     * Limpia una URL con una normalización base común y reglas por tienda.
     * Único punto de verdad: actualizar aquí para que se aplique en formularios y backend.
     *
     * @param string $url URL a limpiar
     * @return string URL limpia
     */
    public function limpiar(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return $url;
        }

        $url = $this->limpiarGenerica($url);
        $url = $this->limpiarAmazon($url);
        $url = $this->limpiarPccomponentes($url);
        $url = $this->limpiarCoolmod($url);

        return $url;
    }

    /**
     * Indica si la URL es de Pccomponentes con # o ?refurbished (para aplicar precio 0 en el formulario de ofertas).
     *
     * @param string $url URL a comprobar (antes de limpiar)
     * @return bool
     */
    public function esPccomponentesConHashORefurbished(string $url): bool
    {
        $url = trim($url);
        if ($url === '') {
            return false;
        }
        $urlLower = mb_strtolower($url);
        if (!str_contains($urlLower, 'pccomponentes')) {
            return false;
        }
        if (str_contains($url, '#')) {
            return true;
        }
        return (bool) preg_match('/[?&]refurbished/i', $url);
    }

    /**
     * Normalización base para todas las tiendas:
     * - asegura esquema para parsear correctamente
     * - elimina query string y fragment
     * - mantiene scheme + host + path
     */
    protected function limpiarGenerica(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return $url;
        }

        if (!preg_match('#^https?://#i', $url)) {
            $url = 'https://' . $url;
        }

        $partes = parse_url($url);
        if (!is_array($partes)) {
            return preg_replace('/[?#].*$/', '', $url) ?? $url;
        }

        $scheme = $partes['scheme'] ?? 'https';
        $host = $partes['host'] ?? '';
        $path = $partes['path'] ?? '';
        if ($host === '') {
            return preg_replace('/[?#].*$/', '', $url) ?? $url;
        }

        return $scheme . '://' . $host . $path;
    }

    /**
     * Limpia URL de Amazon: deja solo https://www.amazon.{dominio}/dp/CODIGO
     */
    protected function limpiarAmazon(string $url): string
    {
        $amazonRegex = '#^https?://(www\.)?amazon\.(es|com|co\.uk|de|fr|it|ca|com\.au|co\.jp|in|com\.mx|com\.br|nl|se|pl|com\.tr|ae|sa|sg|com\.tw|com\.hk)/#i';
        if (!preg_match($amazonRegex, $url)) {
            return $url;
        }
        if (!preg_match('#amazon\.([a-z.]+)#i', $url, $dominioMatch)) {
            return $url;
        }
        $dominio = $dominioMatch[1];
        if (preg_match('#/dp/([A-Z0-9]+)#i', $url, $dpMatch)) {
            return "https://www.amazon.{$dominio}/dp/{$dpMatch[1]}";
        }
        if (preg_match('#/gp/product/([A-Z0-9]+)#i', $url, $gpMatch)) {
            return "https://www.amazon.{$dominio}/dp/{$gpMatch[1]}";
        }
        return $url;
    }

    /**
     * Limpia URL de Pccomponentes: quita # al final y parámetro ?refurbished / &refurbished
     */
    protected function limpiarPccomponentes(string $url): string
    {
        if ($url === '' || !str_contains(mb_strtolower($url), 'pccomponentes')) {
            return $url;
        }
        $urlLimpia = $url;
        if (str_ends_with($urlLimpia, '#')) {
            $urlLimpia = substr($urlLimpia, 0, -1);
        }
        $urlLimpia = preg_replace_callback('/\?refurbished(=[^&]*)?(&|$)/i', function ($m) {
            return $m[2] === '&' ? '?' : '';
        }, $urlLimpia);
        $urlLimpia = preg_replace_callback('/&refurbished(=[^&]*)?(&|$)/i', function ($m) {
            return $m[2] === '&' ? '&' : '';
        }, $urlLimpia);
        $urlLimpia = preg_replace('/[?&]$/', '', $urlLimpia);
        return $urlLimpia;
    }

    /**
     * Limpia URL de Coolmod: quita ? al final
     */
    protected function limpiarCoolmod(string $url): string
    {
        if ($url === '' || !str_contains(mb_strtolower($url), 'coolmod')) {
            return $url;
        }
        if (str_ends_with($url, '?')) {
            return substr($url, 0, -1);
        }
        return $url;
    }
}
