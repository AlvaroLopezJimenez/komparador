<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class EjecucionGlobal extends Model
{
    use HasFactory;

    private const MESES_RETENCION_EJECUCIONES = 1;

    protected $table = 'ejecuciones_global';

    protected $fillable = [
        'inicio',
        'fin',
        'nombre',
        'total',
        'total_guardado',
        'total_errores',
        'log',
    ];

    protected $casts = [
        'inicio' => 'datetime',
        'fin' => 'datetime',
        'log' => 'array',
    ];

    protected static function booted(): void
    {
        static::created(function (EjecucionGlobal $ejecucion) {
            $ejecucion->eliminarEjecucionesAntiguasDelMismoTipo();
        });
    }

    public function eliminarEjecucionesAntiguasDelMismoTipo(): void
    {
        if (empty($this->nombre) || empty($this->inicio)) {
            return;
        }

        try {
            static::where('nombre', $this->nombre)
                ->where($this->getKeyName(), '!=', $this->getKey())
                ->where('inicio', '<', now()->subMonths(self::MESES_RETENCION_EJECUCIONES))
                ->delete();
        } catch (\Throwable $e) {
            Log::warning('No se pudieron eliminar ejecuciones antiguas del mismo tipo', [
                'ejecucion_id' => $this->getKey(),
                'nombre' => $this->nombre,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
