<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ofertas_producto', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained()->onDelete('cascade');
            $table->foreignId('tienda_id')->constrained()->onDelete('cascade');
            $table->foreignId('chollo_id')->nullable()->constrained()->onDelete('cascade');
            $table->decimal('unidades', 8, 2);
            $table->decimal('precio_total', 8, 2);
            $table->decimal('envio', 4, 2)->nullable();
            $table->dateTime('fecha_actualizacion_envio')->nullable();
            $table->unsignedInteger('frecuencia_actualizar_precio_minutos')->default(1440);
            $table->decimal('precio_unidad', 7, 3);
            $table->string('url'); // URL de la oferta concreta
            $table->string('variante')->nullable(); // Variante de la oferta (número, texto, etc.)
            $table->string('mostrar');
            $table->string('como_scrapear');
            $table->string('descuentos')->nullable();
            $table->json('especificaciones_internas')->nullable();
            $table->text('anotaciones_internas')->nullable();
            $table->dateTime('aviso')->nullable();
            $table->dateTime('fecha_inicio')->nullable();
            $table->dateTime('fecha_final')->nullable();
            $table->timestamp('comprobada')->nullable();
            $table->unsignedInteger('frecuencia_comprobacion_chollos_min')->nullable();
            $table->unsignedInteger('clicks')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ofertas_producto');
    }
};
