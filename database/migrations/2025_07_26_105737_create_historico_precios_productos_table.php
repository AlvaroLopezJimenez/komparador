<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('historico_precios_productos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained()->onDelete('cascade');
            $table->string('especificacion_interna_id')->nullable();
            $table->date('fecha');
            $table->decimal('precio_minimo', 8, 3);
            $table->timestamps();

            // Índice único que incluye especificacion_interna_id para permitir múltiples registros por producto (general + especificaciones)
            $table->unique(['producto_id', 'especificacion_interna_id', 'fecha'], 'historico_precios_productos_unique');
            
            // Índice para consultas rápidas por producto y especificación
            $table->index(['producto_id', 'especificacion_interna_id'], 'historico_precios_productos_producto_especificacion_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historico_precios_productos');
    }
};
