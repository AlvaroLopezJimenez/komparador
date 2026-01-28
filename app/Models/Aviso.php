<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Aviso extends Model
{
    use HasFactory;

    protected $fillable = [
        'texto_aviso', 'fecha_aviso', 'user_id', 'avisoable_type', 'avisoable_id', 'oculto'
    ];

    protected $casts = [
        'fecha_aviso' => 'datetime',
        'oculto' => 'boolean'
    ];

    public function avisoable()
    {
        // Usar morphTo() normalmente, pero Laravel intentará cargar 'Interno' como clase
        // El problema se soluciona en los accessors y métodos que verifican el tipo antes de usar avisoable
        return $this->morphTo('avisoable', 'avisoable_type', 'avisoable_id');
    }
    
    /**
     * Sobrescribir getAttribute para interceptar accesos a 'avisoable' cuando el tipo es 'Interno' o 'AntiScraping'
     */
    public function getAttribute($key)
    {
        // Si se intenta acceder a 'avisoable' y el tipo es 'Interno' o 'AntiScraping', retornar null sin intentar cargar
        $tipo = $this->attributes['avisoable_type'] ?? null;
        if ($key === 'avisoable' && in_array($tipo, ['Interno', 'AntiScraping'])) {
            return null;
        }
        
        return parent::getAttribute($key);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scopes para filtrar avisos
    public function scopeVencidos($query)
    {
        return $query->where('fecha_aviso', '<=', now());
    }

    public function scopePendientes($query)
    {
        return $query->where('fecha_aviso', '>', now());
    }

    public function scopeVisibles($query)
    {
        return $query->where('oculto', false);
    }

    public function scopeOcultos($query)
    {
        return $query->where('oculto', true);
    }

    public function scopeVisiblesPorUsuario($query, $userId)
    {
        return $query->where(function($q) use ($userId) {
            $q->where('user_id', $userId)->orWhere('user_id', 1);
        });
    }

    // Métodos helper
    public function isVencido()
    {
        return $this->fecha_aviso <= now();
    }

    public function getElementoNombreAttribute()
    {
        // Si es un aviso interno ('Interno' o null)
        if ($this->avisoable_type === 'Interno' || ($this->avisoable_type === null && $this->avisoable_id === null)) {
            return 'Aviso Interno';
        }
        
        if ($this->avisoable) {
            switch ($this->avisoable_type) {
                case 'App\Models\Producto':
                    return $this->avisoable->nombre . ' - ' . $this->avisoable->marca . ' - ' . $this->avisoable->modelo . ' - ' . $this->avisoable->talla;
                case 'App\Models\OfertaProducto':
                    return $this->avisoable->producto->nombre . ' - ' . $this->avisoable->tienda->nombre;
                case 'App\Models\Chollo':
                    $titulo = $this->avisoable->titulo ?? 'Chollo';
                    $tienda = $this->avisoable->tienda->nombre ?? null;
                    return $tienda ? $titulo . ' - ' . $tienda : $titulo;
                case 'App\Models\Tienda':
                    return $this->avisoable->nombre;
                default:
                    return 'Elemento desconocido';
            }
        }
        return 'Elemento no encontrado';
    }

    public function getRutaEdicionAttribute()
    {
        // Si es un aviso interno ('Interno' o null)
        if ($this->avisoable_type === 'Interno' || ($this->avisoable_type === null && $this->avisoable_id === null)) {
            return '#';
        }
        
        switch ($this->avisoable_type) {
            case 'App\Models\Producto':
                return route('admin.productos.edit', $this->avisoable_id);
            case 'App\Models\OfertaProducto':
                return route('admin.ofertas.edit', $this->avisoable_id);
            case 'App\Models\Chollo':
                return route('admin.chollos.edit', $this->avisoable_id);
            case 'App\Models\Tienda':
                return route('admin.tiendas.edit', $this->avisoable_id);
            default:
                return '#';
        }
    }

    public function getTipoElementoAttribute()
    {
        // Si es un aviso interno ('Interno' o null)
        if ($this->avisoable_type === 'Interno' || ($this->avisoable_type === null && $this->avisoable_id === null)) {
            return 'Interno';
        }
        
        switch ($this->avisoable_type) {
            case 'App\Models\Producto':
                return 'Producto';
            case 'App\Models\OfertaProducto':
                return 'Oferta';
            case 'App\Models\Chollo':
                return 'Chollo';
            case 'App\Models\Tienda':
                return 'Tienda';
            default:
                return 'Desconocido';
        }
    }
}
