<?php

namespace App\Models;

use App\Services\ConsultarNeoCifrado;
use Illuminate\Database\Eloquent\Model;

class OfertaProducto extends Model
{
    protected $table = 'ofertas_producto';
    
    public $timestamps = true;

    protected $fillable = [
        'producto_id',
        'tienda_id',
        'chollo_id',
        'unidades',
        'precio_total',
        'envio',
        'fecha_actualizacion_envio',
        'frecuencia_actualizar_precio_minutos',
        'precio_unidad',
        'url',
        'url_cipher',
        'url_lookup',
        'variante',
        'mostrar',
        'como_scrapear',
        'descuentos',
        'especificaciones_internas',
        'anotaciones_internas',
        'aviso',
        'fecha_inicio',
        'fecha_final',
        'comprobada',
        'frecuencia_comprobacion_chollos_min',
        'clicks',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'especificaciones_internas' => 'array',
        'aviso' => 'datetime',
        'fecha_inicio' => 'datetime',
        'fecha_final' => 'datetime',
        'comprobada' => 'datetime',
        'fecha_actualizacion_envio' => 'datetime',
    ];

    public function setUrlAttribute($value): void
    {
        $value = is_string($value) ? trim($value) : $value;

        // No persistir columna legacy `url` (puede no existir en BD); solo v2.
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

    public function getUrlAttribute($value): ?string
    {
        $cipherV2 = (string) ($this->attributes['url_cipher'] ?? '');
        if ($cipherV2 !== '') {
            return app(ConsultarNeoCifrado::class)->descifrarGuardado($cipherV2);
        }

        return '';
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    public function tienda()
    {
        return $this->belongsTo(Tienda::class);
    }

    public function chollo()
    {
        return $this->belongsTo(Chollo::class);
    }

    public function avisos()
    {
        return $this->morphMany(Aviso::class, 'avisoable');
    }
}
