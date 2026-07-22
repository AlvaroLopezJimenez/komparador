<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class Tienda extends Model
{
    protected $fillable = [
        'nombre',
        'envio_gratis',
        'envio_normal',
        'url',
        'url_imagen',
        'opiniones',
        'puntuacion',
        'url_opiniones',
        'anotaciones_internas',
        'aviso',
        'avisos_sin_stock_scrapear_automatico',
        'api',
        'api_productos',
        'url_csv',
        'mostrar_tienda',
        'scrapear',
        'como_scrapear',
        'frecuencia_minima_minutos',
        'frecuencia_maxima_minutos',
    ];

    /**
     * Enlaces de descarga CSV-Awin.
     * Formato nuevo: JSON array en BD → lista de strings al leer.
     * Formato legacy: una sola URL en texto plano → se normaliza a array de un elemento.
     *
     * @return list<string>
     */
    public function urlsCsv(): array
    {
        return self::normalizarUrlsCsvValor($this->attributes['url_csv'] ?? null);
    }

    public function tieneUrlsCsv(): bool
    {
        return $this->urlsCsv() !== [];
    }

    /**
     * Normaliza url_csv desde BD o request:
     * - null / vacío → []
     * - array → lista de URLs
     * - JSON array string → lista de URLs
     * - string con una URL (legacy) → [url]
     * - string multilínea → lista de URLs
     *
     * @return list<string>
     */
    public static function normalizarUrlsCsvValor(mixed $raw): array
    {
        if ($raw === null || $raw === '') {
            return [];
        }

        if (is_array($raw)) {
            $candidatos = $raw;
        } elseif (is_string($raw)) {
            $trimmed = trim($raw);
            if ($trimmed === '') {
                return [];
            }

            if (str_starts_with($trimmed, '[')) {
                $decoded = json_decode($trimmed, true);
                $candidatos = is_array($decoded) ? $decoded : [$trimmed];
            } elseif (preg_match('/\r\n|\r|\n/', $trimmed) === 1) {
                $candidatos = preg_split('/\r\n|\r|\n/', $trimmed) ?: [];
            } else {
                // Formato legacy: una sola URL en texto plano
                $candidatos = [$trimmed];
            }
        } else {
            return [];
        }

        $urls = [];
        foreach ($candidatos as $url) {
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

    /**
     * @return Attribute<list<string>|null, list<string>|string|null>
     */
    protected function urlCsv(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                $urls = self::normalizarUrlsCsvValor($value);

                return $urls === [] ? null : $urls;
            },
            set: function ($value) {
                $urls = self::normalizarUrlsCsvValor($value);

                return $urls === []
                    ? null
                    : json_encode(array_values($urls), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            },
        );
    }

    public function ofertas()
    {
        return $this->hasMany(\App\Models\OfertaProducto::class);
    }
    public function comisiones()
    {
        return $this->hasMany(\App\Models\ComisionCategoriaTienda::class);
    }

    public function scrapingPorCategoria()
    {
        return $this->hasMany(\App\Models\TiendaCategoriaApi::class);
    }

    public function normalizarComoScrapear(): string
    {
        $valor = strtolower(trim((string) ($this->como_scrapear ?? 'manual')));

        return in_array($valor, ['automatico', 'manual', 'ambos'], true) ? $valor : 'manual';
    }

    /**
     * @return list<string>
     */
    public function opcionesComoScrapearOferta(): array
    {
        $modo = $this->normalizarComoScrapear();

        return $modo === 'ambos' ? ['automatico', 'manual'] : [$modo];
    }

    public function esComoScrapearOfertaValido(?string $valor): bool
    {
        if ($valor === null || trim($valor) === '') {
            return false;
        }

        $normalizado = strtolower(trim($valor));

        return in_array($normalizado, $this->opcionesComoScrapearOferta(), true);
    }

    public function resolverComoScrapearOfertaPorDefecto(?string $preferido = null): string
    {
        $opciones = $this->opcionesComoScrapearOferta();
        $preferidoNormalizado = strtolower(trim((string) ($preferido ?? '')));

        if ($preferidoNormalizado !== '' && in_array($preferidoNormalizado, $opciones, true)) {
            return $preferidoNormalizado;
        }

        return $opciones[0];
    }
}
